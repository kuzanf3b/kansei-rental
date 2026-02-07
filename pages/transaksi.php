<?php
// Transaksi Page - Grid View untuk Admin/Petugas, Table View untuk Member
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

// Pagination
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
    $sql = "SELECT t.*, m.brand, m.type, m.nopol as mobil_nopol, m.harga, m.foto, mb.nama as nama_member, mb.telp, mb.alamat,
            k.tgl_kembali as tgl_kembali_aktual, k.kondisi_mobil, k.denda
            FROM tbl_transaksi t
            JOIN tbl_mobil m ON t.nopol = m.nopol
            JOIN tbl_member mb ON t.nik = mb.nik
            LEFT JOIN tbl_kembali k ON t.id_transaksi = k.id_transaksi
            ORDER BY t.id_transaksi DESC
            LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $limit, $offset);
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

<?php if ($is_member): ?>
    <!-- Member View: Table -->
    <div class="content-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table data-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Mobil</th>
                            <th>Tanggal Ambil</th>
                            <th>Tanggal Kembali</th>
                            <th>Supir</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = $offset + 1;
                        while ($row = $result->fetch_assoc()):
                        ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['brand'] . ' ' . $row['type']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($row['mobil_nopol']); ?></small>
                                </td>
                                <td><?php echo $row['tgl_ambil'] ? date('d M Y', strtotime($row['tgl_ambil'])) : '-'; ?></td>
                                <td><?php echo $row['tgl_kembali'] ? date('d M Y', strtotime($row['tgl_kembali'])) : '-'; ?></td>
                                <td>
                                    <?php if ($row['supir']): ?>
                                        <span class="badge bg-info">Dengan Supir</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Tanpa Supir</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong>Rp <?php echo number_format($row['total'], 0, ',', '.'); ?></strong></td>
                                <td>
                                    <?php
                                    $status_class = [
                                        'booking' => 'warning',
                                        'approve' => 'info',
                                        'ambil' => 'primary',
                                        'kembali' => 'success'
                                    ];
                                    $class = $status_class[$row['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $class; ?>"><?php echo ucfirst($row['status']); ?></span>
                                </td>
                                <td>
                                    <?php if (in_array($row['status'], ['booking', 'approve'])): ?>
                                        <a href="index.php?page=transaksi&cancel=<?php echo $row['id_transaksi']; ?>"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Yakin ingin membatalkan transaksi ini?')">
                                            <i class="bi bi-x-circle"></i> Batalkan
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        <?php if ($result->num_rows === 0): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="bi bi-inbox text-muted" style="font-size: 2rem;"></i>
                                    <p class="text-muted mb-0 mt-2">Belum ada transaksi</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- Admin/Petugas View: Grid -->
    <div class="transaksi-grid-page">
        <div class="transaksi-grid">
            <?php
            while ($row = $result->fetch_assoc()):
                $gambar = $row['foto'] ? 'uploads/mobil/' . $row['foto'] : 'assets/img/car-placeholder.jpg';
                $status_class = [
                    'booking' => 'warning',
                    'approve' => 'info',
                    'ambil' => 'primary',
                    'kembali' => 'success'
                ];
                $class = $status_class[$row['status']] ?? 'secondary';

                $next_status = '';
                $status_action = '';
                switch ($row['status']) {
                    case 'booking':
                        $next_status = 'approve';
                        $status_action = 'Setujui Booking';
                        break;
                    case 'approve':
                        $next_status = 'ambil';
                        $status_action = 'Konfirmasi Pengambilan';
                        break;
                    case 'ambil':
                        $next_status = 'kembali';
                        $status_action = 'Catat Pengembalian';
                        break;
                }

                // Calculate days
                $hari = 1;
                if ($row['tgl_ambil'] && $row['tgl_kembali']) {
                    $date1 = new DateTime($row['tgl_ambil']);
                    $date2 = new DateTime($row['tgl_kembali']);
                    $interval = $date1->diff($date2);
                    $hari = $interval->days + 1;
                }
            ?>
                <div class="transaksi-card status-<?php echo $row['status']; ?>">
                    <!-- Car Image -->
                    <div class="transaksi-card-image">
                        <img src="<?php echo htmlspecialchars($gambar); ?>" alt="<?php echo htmlspecialchars($row['brand']); ?>">
                        <div class="status-badge badge-<?php echo $class; ?>">
                            <?php echo ucfirst($row['status']); ?>
                        </div>
                        <?php if ($row['supir']): ?>
                            <div class="supir-badge">
                                <i class="bi bi-person-fill"></i> Dengan Supir
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Card Body -->
                    <div class="transaksi-card-body">
                        <!-- Car Info -->
                        <div class="car-info">
                            <h3 class="car-name"><?php echo htmlspecialchars($row['brand'] . ' ' . $row['type']); ?></h3>
                            <span class="car-nopol"><?php echo htmlspecialchars($row['mobil_nopol']); ?></span>
                        </div>

                        <!-- Member Info -->
                        <div class="member-info">
                            <div class="member-avatar">
                                <?php echo strtoupper(substr($row['nama_member'], 0, 1)); ?>
                            </div>
                            <div class="member-details">
                                <span class="member-name"><?php echo htmlspecialchars($row['nama_member']); ?></span>
                                <span class="member-phone"><i class="bi bi-phone"></i> <?php echo htmlspecialchars($row['telp']); ?></span>
                            </div>
                        </div>

                        <!-- Rental Details -->
                        <div class="rental-details">
                            <div class="rental-dates">
                                <div class="date-item">
                                    <span class="date-label">Ambil</span>
                                    <span class="date-value"><?php echo $row['tgl_ambil'] ? date('d M Y', strtotime($row['tgl_ambil'])) : '-'; ?></span>
                                </div>
                                <div class="date-separator">
                                    <i class="bi bi-arrow-right"></i>
                                    <span><?php echo $hari; ?> hari</span>
                                </div>
                                <div class="date-item">
                                    <span class="date-label">Kembali</span>
                                    <span class="date-value"><?php echo $row['tgl_kembali'] ? date('d M Y', strtotime($row['tgl_kembali'])) : '-'; ?></span>
                                </div>
                            </div>
                            <div class="rental-total">
                                <span class="total-label">Total Biaya</span>
                                <span class="total-value">Rp <?php echo number_format($row['total'], 0, ',', '.'); ?></span>
                            </div>

                            <?php if ($row['status'] === 'kembali' && !empty($row['tgl_kembali_aktual'])): ?>
                                <!-- Return Info for Completed Transactions -->
                                <div class="return-info-box">
                                    <div class="return-info-header">
                                        <i class="bi bi-box-arrow-in-left"></i>
                                        <span>Info Pengembalian</span>
                                    </div>
                                    <div class="return-info-content">
                                        <div class="return-info-item">
                                            <span class="label">Dikembalikan</span>
                                            <span class="value"><?php echo date('d M Y', strtotime($row['tgl_kembali_aktual'])); ?></span>
                                        </div>
                                        <div class="return-info-item">
                                            <span class="label">Kondisi</span>
                                            <span class="value"><?php echo htmlspecialchars($row['kondisi_mobil'] ?? '-'); ?></span>
                                        </div>
                                        <?php if ($row['denda'] > 0): ?>
                                            <div class="return-info-item denda">
                                                <span class="label">Denda</span>
                                                <span class="value text-danger">Rp <?php echo number_format($row['denda'], 0, ',', '.'); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="return-info-item total">
                                            <span class="label">Total + Denda</span>
                                            <span class="value">Rp <?php echo number_format($row['total'] + ($row['denda'] ?? 0), 0, ',', '.'); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Card Actions -->
                    <div class="transaksi-card-actions">
                        <?php if (!empty($next_status)): ?>
                            <?php if ($row['status'] === 'ambil'): ?>
                                <a href="index.php?page=kembali&transaksi_id=<?php echo $row['id_transaksi']; ?>"
                                    class="btn-action btn-return">
                                    <i class="bi bi-box-arrow-in-left"></i>
                                    Catat Pengembalian
                                </a>
                            <?php else: ?>
                                <a href="index.php?page=transaksi&update_status=<?php echo $row['id_transaksi']; ?>"
                                    class="btn-action btn-status"
                                    onclick="return confirm('Ubah status ke <?php echo ucfirst($next_status); ?>?')">
                                    <i class="bi bi-arrow-right-circle"></i>
                                    <?php echo $status_action; ?>
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>

                        <div class="action-buttons">
                            <a href="index.php?page=transaksi&delete=<?php echo $row['id_transaksi']; ?>"
                                class="btn-icon btn-delete"
                                onclick="return confirm('Yakin ingin menghapus transaksi ini?')"
                                title="Hapus">
                                <i class="bi bi-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <?php if ($result->num_rows === 0): ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h3>Belum Ada Transaksi</h3>
                <p>Data transaksi akan muncul di sini setelah member melakukan booking.</p>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php if ($current_page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="index.php?page=transaksi&pg=<?php echo $current_page - 1; ?>">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                </li>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $i === $current_page ? 'active' : ''; ?>">
                    <a class="page-link" href="index.php?page=transaksi&pg=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>

            <?php if ($current_page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="index.php?page=transaksi&pg=<?php echo $current_page + 1; ?>">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
<?php endif; ?>