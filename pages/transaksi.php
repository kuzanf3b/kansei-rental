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

$code

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

