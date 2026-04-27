<?php
// Transaksi Page - Table View untuk Member, Kanban Board untuk Admin/Petugas
// File ini di-include dari index.php, jadi $conn dan $_SESSION sudah tersedia

$user = [
    'level' => $_SESSION['user_level'],
    'username' => $_SESSION['username'] ?? '',
    'nama' => $_SESSION['nama'] ?? $_SESSION['username'] ?? '',
    'nik' => ($_SESSION['user_level'] === 'member') ? $_SESSION['user_id'] : null
];
$is_member = ($user['level'] === 'member');

// Handle pesan sukses dari booking
$success_message = '';
$error_message = '';

if (isset($_GET['booking_success']) && $_GET['booking_success'] == 1) {
    $success_message = "Booking berhasil! Menunggu persetujuan admin.";
}

// Handle cancel transaksi (untuk member)
if (isset($_GET['cancel']) && $is_member) {
    $cancel_id = (int)$_GET['cancel'];

    $check_sql = "SELECT t.*, m.nopol FROM tbl_transaksi t 
                  JOIN tbl_mobil m ON t.nopol = m.nopol 
                  WHERE t.id_transaksi = ? AND t.nik = ? AND t.status IN ('booking', 'approve')";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $cancel_id, $user['nik']);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $trans_data = $check_result->fetch_assoc();

        $update_sql = "UPDATE tbl_transaksi SET status = 'kembali' WHERE id_transaksi = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $cancel_id);

        if ($update_stmt->execute()) {
            if ($trans_data['status'] == 'approve') {
                $mobil_sql = "UPDATE tbl_mobil SET status = 'tersedia' WHERE nopol = ?";
                $mobil_stmt = $conn->prepare($mobil_sql);
                $mobil_stmt->bind_param("s", $trans_data['nopol']);
                $mobil_stmt->execute();
            }
            $success_message = "Transaksi berhasil dibatalkan!";
        }
    }
}

// Handle update status (admin/petugas)
if (isset($_GET['update_status']) && !$is_member) {
    $trans_id = (int)$_GET['update_status'];

    $get_sql = "SELECT status, nopol FROM tbl_transaksi WHERE id_transaksi = ?";
    $get_stmt = $conn->prepare($get_sql);
    $get_stmt->bind_param("i", $trans_id);
    $get_stmt->execute();
    $current = $get_stmt->get_result()->fetch_assoc();

    if ($current) {
        $status_flow = ['booking' => 'approve', 'approve' => 'ambil'];

        if (isset($status_flow[$current['status']])) {
            $new_status = $status_flow[$current['status']];

            // Update status transaksi
            $update_sql = "UPDATE tbl_transaksi SET status = ? WHERE id_transaksi = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $new_status, $trans_id);

            if ($update_stmt->execute()) {
                // Jika status approve, update mobil jadi tidak tersedia
                if ($new_status === 'approve') {
                    $mobil_sql = "UPDATE tbl_mobil SET status = 'tidak' WHERE nopol = ?";
                    $mobil_stmt = $conn->prepare($mobil_sql);
                    $mobil_stmt->bind_param("s", $current['nopol']);
                    $mobil_stmt->execute();
                }
                $success_message = "Status transaksi berhasil diupdate menjadi " . ucfirst($new_status);
            }
        }
    }
}

// Handle hapus
if (isset($_GET['delete']) && !$is_member) {
    $delete_id = (int)$_GET['delete'];

    $get_mobil = "SELECT nopol, status FROM tbl_transaksi WHERE id_transaksi = ?";
    $mobil_stmt = $conn->prepare($get_mobil);
    $mobil_stmt->bind_param("i", $delete_id);
    $mobil_stmt->execute();
    $mobil_result = $mobil_stmt->get_result()->fetch_assoc();

    $sql = "DELETE FROM tbl_transaksi WHERE id_transaksi = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $delete_id);

    if ($stmt->execute()) {
        if ($mobil_result && in_array($mobil_result['status'], ['approve', 'ambil'])) {
            $update_mobil = "UPDATE tbl_mobil SET status = 'tersedia' WHERE nopol = ?";
            $update_stmt = $conn->prepare($update_mobil);
            $update_stmt->bind_param("s", $mobil_result['nopol']);
            $update_stmt->execute();
        }
        $success_message = "Transaksi berhasil dihapus!";
    }
}

// Pagination (only for member view)
$limit = 12;
$current_page = isset($_GET['pg']) ? (int)$_GET['pg'] : 1;
$offset = ($current_page - 1) * $limit;

// Query untuk menghitung total data
if ($is_member) {
    $count_sql = "SELECT COUNT(*) as total FROM tbl_transaksi WHERE nik = ?";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("i", $user['nik']);
    $count_stmt->execute();
} else {
    $count_sql = "SELECT COUNT(*) as total FROM tbl_transaksi";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->execute();
}
$total_data = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_data / $limit);

// Query untuk mengambil data transaksi
if ($is_member) {
    $sql = "SELECT t.*, m.brand, m.type, m.nopol as mobil_nopol, m.harga, m.foto, mb.nama as nama_member, mb.telp,
            k.tgl_kembali as tgl_kembali_aktual, k.kondisi_mobil, k.denda
            FROM tbl_transaksi t
            JOIN tbl_mobil m ON t.nopol = m.nopol
            JOIN tbl_member mb ON t.nik = mb.nik
            LEFT JOIN tbl_kembali k ON t.id_transaksi = k.id_transaksi
            WHERE t.nik = ?
            ORDER BY t.id_transaksi DESC
            LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $user['nik'], $limit, $offset);
} else {
    // Admin/Petugas: Get all data for Kanban Board (no pagination)
    $sql = "SELECT t.*, m.brand, m.type, m.nopol as mobil_nopol, m.harga, m.foto, mb.nama as nama_member, mb.telp, mb.alamat,
            k.tgl_kembali as tgl_kembali_aktual, k.kondisi_mobil, k.denda
            FROM tbl_transaksi t
            JOIN tbl_mobil m ON t.nopol = m.nopol
            JOIN tbl_member mb ON t.nik = mb.nik
            LEFT JOIN tbl_kembali k ON t.id_transaksi = k.id_transaksi
            ORDER BY t.id_transaksi DESC";
    $stmt = $conn->prepare($sql);
}
$stmt->execute();
$result = $stmt->get_result();

// Statistics
if ($is_member) {
    $stats_sql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'booking' THEN 1 ELSE 0 END) as booking,
        SUM(CASE WHEN status = 'approve' THEN 1 ELSE 0 END) as approve,
        SUM(CASE WHEN status = 'ambil' THEN 1 ELSE 0 END) as ambil,
        SUM(CASE WHEN status = 'kembali' THEN 1 ELSE 0 END) as kembali
        FROM tbl_transaksi WHERE nik = ?";
    $stats_stmt = $conn->prepare($stats_sql);
    $stats_stmt->bind_param("i", $user['nik']);
    $stats_stmt->execute();
} else {
    $stats_sql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'booking' THEN 1 ELSE 0 END) as booking,
        SUM(CASE WHEN status = 'approve' THEN 1 ELSE 0 END) as approve,
        SUM(CASE WHEN status = 'ambil' THEN 1 ELSE 0 END) as ambil,
        SUM(CASE WHEN status = 'kembali' THEN 1 ELSE 0 END) as kembali
        FROM tbl_transaksi";
    $stats_stmt = $conn->prepare($stats_sql);
    $stats_stmt->execute();
}
$stats = $stats_stmt->get_result()->fetch_assoc();
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo $is_member ? 'Transaksi Saya' : 'Kelola Transaksi'; ?></h1>
        <p class="page-subtitle"><?php echo $is_member ? 'Lihat riwayat transaksi rental Anda' : 'Kelola semua transaksi rental mobil'; ?></p>
    </div>
</div>

<!-- Alert Messages -->
<?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i><?php echo $success_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle me-2"></i><?php echo $error_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg">
        <div class="stat-card stat-total">
            <div class="stat-icon"><i class="bi bi-receipt"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?php echo $stats['total'] ?? 0; ?></span>
                <span class="stat-label">Total</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg">
        <div class="stat-card stat-booking">
            <div class="stat-icon"><i class="bi bi-clock"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?php echo $stats['booking'] ?? 0; ?></span>
                <span class="stat-label">Booking</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg">
        <div class="stat-card stat-approve">
            <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?php echo $stats['approve'] ?? 0; ?></span>
                <span class="stat-label">Approved</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg">
        <div class="stat-card stat-ambil">
            <div class="stat-icon"><i class="bi bi-car-front"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?php echo $stats['ambil'] ?? 0; ?></span>
                <span class="stat-label">Sedang Disewa</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg">
        <div class="stat-card stat-kembali">
            <div class="stat-icon"><i class="bi bi-check-all"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?php echo $stats['kembali'] ?? 0; ?></span>
                <span class="stat-label">Selesai</span>
            </div>
        </div>
    </div>
</div>


<div class="mb-4 mt-3 d-flex gap-2 flex-wrap" id="filterContainer">
    <button class="btn btn-primary filter-btn active" data-filter="all">Semua</button>
    <button class="btn btn-outline-primary filter-btn" data-filter="booking">Booking</button>
    <button class="btn btn-outline-primary filter-btn" data-filter="approve">Approved</button>
    <button class="btn btn-outline-primary filter-btn" data-filter="ambil">Sedang Disewa</button>
    <button class="btn btn-outline-primary filter-btn" data-filter="kembali">Selesai</button>
</div>

<!-- Transaction Cards (Grid Layout) -->
<div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
    <?php
    mysqli_data_seek($result, 0); // reset pointer
    if ($result->num_rows === 0): ?>
        <div class="col-12 w-100 mt-5">
            <div class="text-center p-5 rounded-4 shadow-sm" style="background: var(--bg-card); border: 1px solid var(--border-color);">
                <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                <h4 class="mt-3 text-muted">Belum ada transaksi</h4>
                <p class="text-muted">Data transaksi akan muncul di sini setelah aktivitas pemesanan dilakukan.</p>
            </div>
        </div>
    <?php else: ?>
        <?php while ($row = $result->fetch_assoc()): 
            $gambar = $row['foto'] ? 'uploads/mobil/' . $row['foto'] : 'assets/img/car-placeholder.jpg';
            $hari = 1;
            if ($row['tgl_ambil'] && $row['tgl_kembali']) {
                $date1 = new DateTime($row['tgl_ambil']);
                $date2 = new DateTime($row['tgl_kembali']);
                $interval = $date1->diff($date2);
                $hari = $interval->days + 1;
            }
            $status_class = ['booking' => 'warning', 'approve' => 'info', 'ambil' => 'primary', 'kembali' => 'success'];
            $class = $status_class[$row['status']] ?? 'secondary';
        ?>
        <div class="col card-col-item" data-status="<?php echo $row['status']; ?>">
            <div class="card h-100" style="border-top: 4px solid var(--<?php echo $class; ?>-color);">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-<?php echo $class; ?> fs-6 rounded-pill px-3 py-2 <?php echo ($class=='warning'||$class=='info')?'text-dark':'text-white'; ?>">
                            <?php echo ucfirst($row['status']); ?>
                        </span>
                        <?php if ($row['supir']): ?>
                            <span class="badge bg-secondary fs-7"><i class="bi bi-person-fill me-1"></i>Dengan Supir</span>
                        <?php else: ?>
                            <span class="badge bg-light text-dark border fs-7"><i class="bi bi-person-x-fill me-1"></i>Tanpa Supir</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="d-flex align-items-center mb-4 pb-3 border-bottom" style="border-color: var(--border-color) !important;">
                        <img src="<?php echo htmlspecialchars($gambar); ?>" alt="<?php echo htmlspecialchars($row['brand']); ?>" class="rounded-3 shadow-sm me-3" style="width: 85px; height: 65px; object-fit: cover;">
                        <div>
                            <h5 class="mb-1 fw-bold text-primary" style="color: var(--text-primary) !important;"><?php echo htmlspecialchars($row['brand'] . ' ' . $row['type']); ?></h5>
                            <span class="text-muted fs-7"><i class="bi bi-car-front ms-1"></i> <?php echo htmlspecialchars($row['mobil_nopol']); ?></span>
                        </div>
                    </div>
                    
                    <?php if (!$is_member): ?>
                    <div class="mb-3 p-2 rounded-3 text-white" style="background: var(--bg-highlight);">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 24px; height: 24px; font-size: 0.75rem;">
                                    <?php echo strtoupper(substr($row['nama_member'], 0, 1)); ?>
                                </div>
                                <span class="fw-semibold" style="font-size: 0.85rem; color: var(--text-primary);"><?php echo htmlspecialchars($row['nama_member']); ?></span>
                            </div>
                            <span class="text-muted" style="font-size: 0.75rem;"><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($row['telp']); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between align-items-center mb-2" style="font-size: 0.85rem; color: var(--text-secondary);">
                        <span><i class="bi bi-calendar-event me-1"></i>Ambil</span>
                        <span class="fw-medium text-end" style="color: var(--text-primary);"><?php echo $row['tgl_ambil'] ? date('d M Y', strtotime($row['tgl_ambil'])) : '-'; ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-4" style="font-size: 0.85rem; color: var(--text-secondary);">
                        <span><i class="bi bi-calendar-check me-1"></i>Kembali</span>
                        <span class="fw-medium text-end" style="color: var(--text-primary);"><?php echo $row['tgl_kembali'] ? date('d M Y', strtotime($row['tgl_kembali'])) : '-'; ?> (<?php echo $hari; ?> hari)</span>
                    </div>

                    <?php if ($row['status'] === 'kembali' && !empty($row['tgl_kembali_aktual'])): ?>
                        <div class="p-3 rounded-3 mb-4 mt-auto" style="background: var(--bg-input); border: 1px dashed var(--border-color);">
                            <div class="d-flex justify-content-between align-items-center mb-1" style="font-size: 0.8rem; color: var(--text-primary);">
                                <span>Dikembalikan:</span>
                                <span class="fw-semibold"><?php echo date('d M Y', strtotime($row['tgl_kembali_aktual'])); ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1" style="font-size: 0.8rem; color: var(--text-primary);">
                                <span class="text-muted">Kondisi:</span>
                                <span class="fw-semibold text-end"><?php echo htmlspecialchars($row['kondisi_mobil'] ?? '-'); ?></span>
                            </div>
                            <?php if ($row['denda'] > 0): ?>
                                <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top border-danger" style="font-size: 0.85rem;">
                                    <span class="text-danger fw-medium">Denda:</span>
                                    <span class="fw-bold text-danger">Rp <?php echo number_format($row['denda'], 0, ',', '.'); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="mt-auto"></div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-3" style="border-color: var(--border-color) !important;">
                        <span class="text-muted" style="font-size: 0.85rem;">Total Biaya</span>
                        <span class="fw-bold fs-5" style="color: var(--primary-color);">Rp <?php echo number_format($row['total'], 0, ',', '.'); ?></span>
                    </div>
                </div>
                
                <div class="card-footer bg-transparent p-3 border-top" style="border-color: var(--border-color) !important;">
                    <div class="d-flex gap-2">
                        <?php if ($is_member): ?>
                            <?php if (in_array($row['status'], ['booking', 'approve'])): ?>
                                <a href="index.php?page=transaksi&cancel=<?php echo $row['id_transaksi']; ?>"
                                   class="btn btn-outline-danger w-100 btn-sm py-2"
                                   onclick="return confirm('Yakin membatalkan transaksi <?php echo htmlspecialchars($row['brand'] . ' ' . $row['type']); ?>?');">
                                   <i class="bi bi-x-circle me-1"></i> Batalkan Booking
                                </a>
                            <?php else: ?>
                                <button class="btn btn-secondary w-100 btn-sm py-2 opacity-50" disabled><i class="bi bi-check-circle me-1"></i> Selesai</button>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php
                            $status_cfg = [
                                'booking' => ['next' => 'approve', 'action' => 'Setujui', 'btn_color' => 'warning'],
                                'approve' => ['next' => 'ambil', 'action' => 'Konfirmasi Ambil', 'btn_color' => 'info'],
                                'ambil' => ['next' => 'kembali', 'action' => 'Selesaikan', 'btn_color' => 'primary'],
                                'kembali' => ['next' => null, 'action' => null, 'btn_color' => 'success']
                            ];
                            $cfg = $status_cfg[$row['status']];
                            ?>
                            <?php if ($cfg['next']): ?>
                                <a href="index.php?page=transaksi&update_status=<?php echo $row['id_transaksi']; ?>"
                                   class="btn btn-<?php echo $cfg['btn_color']; ?> flex-grow-1 btn-sm py-2 text-<?php echo ($cfg['btn_color']=='warning'||$cfg['btn_color']=='info')?'dark':'white'; ?>"
                                   onclick="return confirm('Update status ke <?php echo ucfirst($cfg['next']); ?>?');">
                                   <i class="bi bi-arrow-right-circle me-1"></i> <?php echo $cfg['action']; ?>
                                </a>
                            <?php endif; ?>
                            
                            <a href="index.php?page=transaksi&delete=<?php echo $row['id_transaksi']; ?>"
                               class="btn btn-outline-danger btn-sm py-2 px-3"
                               onclick="return confirm('Hapus transaksi secara permanen?');"
                               title="Hapus">
                               <i class="bi bi-trash"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    <?php endif; ?>
</div>

<?php if ($is_member && (!isset($total_pages) || (isset($total_pages) && $total_pages > 1))): ?>
<!-- Pagination (Member Only) -->
<div class="mt-5 d-flex justify-content-center">
    <nav>
        <ul class="pagination pagination-sm shadow-sm" style="--bs-pagination-bg: var(--bg-card); --bs-pagination-border-color: var(--border-color); --bs-pagination-color: var(--text-primary); --bs-pagination-hover-bg: var(--bg-hover); --bs-pagination-hover-border-color: var(--border-color); --bs-pagination-hover-color: var(--primary-color); --bs-pagination-active-bg: var(--primary-color); --bs-pagination-active-border-color: var(--primary-color);">
            <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=transaksi&pg=<?php echo $current_page - 1; ?>"><i class="bi bi-chevron-left"></i></a>
            </li>
            <?php 
            $tot_pg = isset($total_pages) ? $total_pages : 1;
            for ($i = 1; $i <= $tot_pg; $i++): ?>
                <li class="page-item <?php echo ($current_page == $i) ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=transaksi&pg=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?php echo ($current_page >= $tot_pg) ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=transaksi&pg=<?php echo $current_page + 1; ?>"><i class="bi bi-chevron-right"></i></a>
            </li>
        </ul>
    </nav>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    const cards = document.querySelectorAll('.card-col-item');

    filterButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            filterButtons.forEach(b => b.classList.remove('active', 'btn-primary'));
            filterButtons.forEach(b => b.classList.add('btn-outline-primary'));
            this.classList.remove('btn-outline-primary');
            this.classList.add('active', 'btn-primary');

            const filter = this.dataset.filter;
            cards.forEach(card => {
                if (filter === 'all' || card.dataset.status === filter) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
});
</script>
