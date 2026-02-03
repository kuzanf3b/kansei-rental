<?php
// Transaksi Page - CRUD Operations

// Denda per hari keterlambatan (Rp 100.000)
$denda_per_hari = 100000;

// Biaya supir per hari (Rp 150.000)
$biaya_supir_per_hari = 150000;

// ============================================
// HELPER FUNCTIONS UNTUK VALIDASI
// ============================================

/**
 * Cek apakah mobil sudah dibooking pada rentang tanggal tertentu
 * @return array ['bentrok' => bool, 'transaksi' => array|null]
 */
function cekBookingBentrok($conn, $nopol, $tgl_ambil, $tgl_kembali, $exclude_id = null)
{
    $exclude_condition = $exclude_id ? "AND id_transaksi != '$exclude_id'" : "";

    // Cari transaksi yang bentrok (status bukan 'kembali')
    $query = "SELECT t.*, m.nama FROM tbl_transaksi t 
              LEFT JOIN tbl_member m ON t.nik = m.nik
              WHERE t.nopol = '$nopol' 
              AND t.status != 'kembali'
              AND (
                  (t.tgl_ambil <= '$tgl_kembali' AND t.tgl_kembali >= '$tgl_ambil')
              )
              $exclude_condition
              LIMIT 1";

    $result = mysqli_query($conn, $query);
    if (mysqli_num_rows($result) > 0) {
        return ['bentrok' => true, 'transaksi' => mysqli_fetch_assoc($result)];
    }
    return ['bentrok' => false, 'transaksi' => null];
}

/**
 * Cek apakah member memiliki transaksi yang belum lunas
 * @return array ['ada_tunggakan' => bool, 'detail' => array]
 */
function cekTunggakanMember($conn, $nik)
{
    // Cek transaksi dengan status 'ambil' yang sudah lewat tanggal kembali
    $transaksi_terlambat = db_get_row($conn, "SELECT COUNT(*) as total FROM tbl_transaksi 
        WHERE nik = '$nik' AND status = 'ambil' AND tgl_kembali < CURDATE()");

    // Cek pembayaran belum lunas
    $pembayaran_pending = db_get_row($conn, "SELECT COUNT(*) as total FROM tbl_bayar b
        JOIN tbl_kembali k ON b.id_kembali = k.id_kembali
        JOIN tbl_transaksi t ON k.id_transaksi = t.id_transaksi
        WHERE t.nik = '$nik' AND b.status = 'belum lunas'");

    $ada_tunggakan = ($transaksi_terlambat['total'] > 0) || ($pembayaran_pending['total'] > 0);

    return [
        'ada_tunggakan' => $ada_tunggakan,
        'detail' => [
            'transaksi_terlambat' => $transaksi_terlambat['total'],
            'pembayaran_pending' => $pembayaran_pending['total']
        ]
    ];
}

/**
 * Hitung total biaya termasuk supir
 */
function hitungTotalBiaya($harga_mobil, $tgl_ambil, $tgl_kembali, $dengan_supir, $biaya_supir_per_hari)
{
    $date1 = new DateTime($tgl_ambil);
    $date2 = new DateTime($tgl_kembali);
    $lama_hari = $date1->diff($date2)->days;
    if ($lama_hari < 1) $lama_hari = 1;

    $biaya_sewa = $harga_mobil * $lama_hari;
    $biaya_supir = $dengan_supir ? ($biaya_supir_per_hari * $lama_hari) : 0;

    return [
        'lama_hari' => $lama_hari,
        'biaya_sewa' => $biaya_sewa,
        'biaya_supir' => $biaya_supir,
        'total' => $biaya_sewa + $biaya_supir
    ];
}

// Error message untuk ditampilkan
$error_msg = '';
$success_msg = '';

// Cek message dari URL
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'booking_bentrok':
            $error_msg = 'Mobil sudah dibooking pada tanggal tersebut! Silakan pilih tanggal lain.';
            break;
        case 'member_tunggakan':
            $error_msg = 'Member memiliki tunggakan! Selesaikan transaksi sebelumnya terlebih dahulu.';
            break;
        case 'cancelled':
            $success_msg = 'Transaksi berhasil dibatalkan.';
            break;
        case 'cancel_failed':
            $error_msg = 'Transaksi tidak dapat dibatalkan. Hanya transaksi dengan status booking/approve yang bisa dibatalkan.';
            break;
    }
}

// ============================================
// HANDLE PEMBATALAN TRANSAKSI
// ============================================
if (isset($_GET['cancel'])) {
    $id = get($conn, 'cancel');
    $trans = db_get_row($conn, "SELECT * FROM tbl_transaksi WHERE id_transaksi='$id'");

    if ($trans) {
        $can_cancel = false;

        // Member hanya bisa cancel transaksi miliknya sendiri dengan status 'booking'
        if ($_SESSION['user_level'] == 'member') {
            if ($trans['nik'] == $_SESSION['user_id'] && $trans['status'] == 'booking') {
                $can_cancel = true;
            }
        } else {
            // Admin/petugas bisa cancel transaksi dengan status 'booking' atau 'approve'
            if (in_array($trans['status'], ['booking', 'approve'])) {
                $can_cancel = true;
            }
        }

        if ($can_cancel) {
            // Hapus transaksi
            db_delete($conn, 'tbl_transaksi', "id_transaksi='$id'");
            redirect('index.php?page=transaksi&msg=cancelled');
        } else {
            redirect('index.php?page=transaksi&msg=cancel_failed');
        }
    }
    redirect('index.php?page=transaksi');
}

// Handle Update Status
if (isset($_GET['update_status']) && $_SESSION['user_level'] != 'member') {
    $id = get($conn, 'update_status');
    $trans = db_get_row($conn, "SELECT * FROM tbl_transaksi WHERE id_transaksi='$id'");

    if ($trans) {
        $current_status = $trans['status'];
        $nopol = $trans['nopol'];

        // Tentukan status berikutnya (cycle: booking -> approve -> ambil -> kembali -> booking)
        $next_status = match ($current_status) {
            'booking' => 'approve',
            'approve' => 'ambil',
            'ambil' => 'kembali',
            'kembali' => 'booking',
            default => 'booking'
        };

        // Update status transaksi
        db_update($conn, 'tbl_transaksi', ['status' => $next_status], "id_transaksi='$id'");

        // Update status mobil berdasarkan status
        if ($next_status == 'ambil') {
            db_update($conn, 'tbl_mobil', ['status' => 'tidak'], "nopol='$nopol'");
        } elseif ($next_status == 'kembali') {
            db_update($conn, 'tbl_mobil', ['status' => 'tersedia'], "nopol='$nopol'");

            // Hitung denda jika terlambat
            $tgl_kembali = $trans['tgl_kembali'];
            $today = date('Y-m-d');

            if ($today > $tgl_kembali) {
                $date1 = new DateTime($tgl_kembali);
                $date2 = new DateTime($today);
                $diff = $date1->diff($date2)->days;
                $denda = $diff * $denda_per_hari;

                // Update denda di transaksi
                $total_asli = $trans['total'] - ($trans['denda'] ?? 0); // Total tanpa denda sebelumnya
                $new_total = $total_asli + $denda;
                $new_kekurangan = $new_total - $trans['downpayment'];
                db_update($conn, 'tbl_transaksi', [
                    'denda' => $denda,
                    'total' => $new_total,
                    'kekurangan' => $new_kekurangan
                ], "id_transaksi='$id'");
            }
        } elseif ($next_status == 'booking') {
            // Reset ke booking, mobil tetap tidak tersedia jika sebelumnya ambil
            if ($current_status == 'kembali') {
                // Reset denda jika kembali ke booking dari kembali
                $total_asli = $trans['total'] - ($trans['denda'] ?? 0);
                db_update($conn, 'tbl_transaksi', [
                    'denda' => 0,
                    'total' => $total_asli,
                    'kekurangan' => $total_asli - $trans['downpayment']
                ], "id_transaksi='$id'");
            }
        } elseif ($current_status == 'ambil' && $next_status != 'kembali') {
            db_update($conn, 'tbl_mobil', ['status' => 'tersedia'], "nopol='$nopol'");
        }
    }

    redirect('index.php?page=transaksi&msg=status_updated');
}

// Handle POST (Add/Edit)
if (is_post()) {
    $action = get_action();

    $nopol = post($conn, 'nopol');
    $tgl_ambil = post($conn, 'tgl_ambil');
    $tgl_kembali = post($conn, 'tgl_kembali');
    $dengan_supir = isset($_POST['supir']) ? 1 : 0;

    // Jika member, gunakan NIK dari session dan status default 'booking'
    $is_member = ($_SESSION['user_level'] == 'member');
    $nik = $is_member ? $_SESSION['user_id'] : post($conn, 'nik');
    $status = $is_member ? 'booking' : post($conn, 'status');

    // Get ID transaksi untuk edit (exclude dari cek bentrok)
    $edit_id = ($action == 'edit') ? post($conn, 'id_transaksi') : null;

    // ============================================
    // VALIDASI 1: Cek booking bentrok
    // ============================================
    $cek_bentrok = cekBookingBentrok($conn, $nopol, $tgl_ambil, $tgl_kembali, $edit_id);
    if ($cek_bentrok['bentrok']) {
        redirect('index.php?page=transaksi&msg=booking_bentrok');
    }

    // ============================================
    // VALIDASI 2: Cek tunggakan member
    // ============================================
    $cek_tunggakan = cekTunggakanMember($conn, $nik);
    if ($cek_tunggakan['ada_tunggakan'] && $action == 'add') {
        redirect('index.php?page=transaksi&msg=member_tunggakan');
    }

    // ============================================
    // HITUNG BIAYA DENGAN SUPIR
    // ============================================
    $mobil_data = db_get_row($conn, "SELECT harga FROM tbl_mobil WHERE nopol='$nopol'");
    $harga_mobil = $mobil_data ? $mobil_data['harga'] : 0;

    $perhitungan = hitungTotalBiaya($harga_mobil, $tgl_ambil, $tgl_kembali, $dengan_supir, $biaya_supir_per_hari);
    $total = $perhitungan['total'];
    $dp = post($conn, 'downpayment');

    $data = [
        'nik'         => $nik,
        'nopol'       => $nopol,
        'tgl_booking' => date('Y-m-d'), // Selalu tanggal hari ini
        'tgl_ambil'   => $tgl_ambil,
        'tgl_kembali' => $tgl_kembali,
        'supir'       => $dengan_supir,
        'total'       => $total,
        'downpayment' => $dp,
        'kekurangan'  => $total - $dp,
        'status'      => $status
    ];

    if ($action == 'add') {
        db_insert($conn, 'tbl_transaksi', $data);
        if ($status == 'ambil') {
            db_update($conn, 'tbl_mobil', ['status' => 'tidak'], "nopol='$nopol'");
        }
        redirect('index.php?page=transaksi&msg=added');
    }

    if ($action == 'edit' && $_SESSION['user_level'] != 'member') {
        $id = post($conn, 'id_transaksi');
        db_update($conn, 'tbl_transaksi', $data, "id_transaksi='$id'");

        if ($status == 'ambil') {
            db_update($conn, 'tbl_mobil', ['status' => 'tidak'], "nopol='$nopol'");
        } elseif ($status == 'kembali') {
            db_update($conn, 'tbl_mobil', ['status' => 'tersedia'], "nopol='$nopol'");
        }
        redirect('index.php?page=transaksi&msg=updated');
    }
}

// Handle Delete - Member tidak boleh hapus
if (isset($_GET['delete']) && $_SESSION['user_level'] != 'member') {
    $id = get($conn, 'delete');
    $trans = db_get_row($conn, "SELECT nopol FROM tbl_transaksi WHERE id_transaksi='$id'");

    if ($trans) {
        db_update($conn, 'tbl_mobil', ['status' => 'tersedia'], "nopol='{$trans['nopol']}'");
    }
    db_delete($conn, 'tbl_transaksi', "id_transaksi='$id'");
    redirect('index.php?page=transaksi&msg=deleted');
}

// Pagination & Data
$page_num = isset($_GET['p']) ? (int)$_GET['p'] : 1;

// Member hanya bisa melihat transaksinya sendiri
$is_member = ($_SESSION['user_level'] == 'member');
$where_member = $is_member ? "WHERE t.nik = '{$_SESSION['user_id']}'" : "";

// Hitung total untuk pagination
$count_query = $is_member
    ? "SELECT COUNT(*) as total FROM tbl_transaksi WHERE nik = '{$_SESSION['user_id']}'"
    : "SELECT COUNT(*) as total FROM tbl_transaksi";
$total_rows = db_get_row($conn, $count_query)['total'];
$total_pages = ceil($total_rows / $limit);
$offset = ($page_num - 1) * $limit;

$result = db_query($conn, "SELECT t.*, m.nama, mb.brand, mb.type 
    FROM tbl_transaksi t 
    LEFT JOIN tbl_member m ON t.nik = m.nik 
    LEFT JOIN tbl_mobil mb ON t.nopol = mb.nopol 
    $where_member
    ORDER BY t.id_transaksi DESC 
    LIMIT $offset, $limit");

// Dropdown data
$members = db_query($conn, "SELECT nik, nama FROM tbl_member ORDER BY nama");

// ============================================
// VALIDASI 4: Dropdown mobil dengan status real-time
// Tampilkan semua mobil dengan info ketersediaan
// ============================================
$mobils = db_query($conn, "SELECT m.nopol, m.brand, m.type, m.harga, m.status,
    (SELECT COUNT(*) FROM tbl_transaksi t WHERE t.nopol = m.nopol AND t.status IN ('booking', 'approve', 'ambil')) as ada_booking
    FROM tbl_mobil m ORDER BY m.brand");

// Get edit data
$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_data = db_get_row($conn, "SELECT * FROM tbl_transaksi WHERE id_transaksi='" . get($conn, 'edit') . "'");
}

// Stats
$booking = db_get_row($conn, "SELECT COUNT(*) as total FROM tbl_transaksi WHERE status='booking'")['total'];
$aktif = db_get_row($conn, "SELECT COUNT(*) as total FROM tbl_transaksi WHERE status IN ('ambil', 'approve')")['total'];
$selesai = db_get_row($conn, "SELECT COUNT(*) as total FROM tbl_transaksi WHERE status='kembali'")['total'];

// ============================================
// NOTIFIKASI/REMINDER
// ============================================
$notifikasi = [];

// Untuk member: cek transaksi yang mendekati tanggal kembali
if ($is_member) {
    $reminder_kembali = db_query($conn, "SELECT * FROM tbl_transaksi 
        WHERE nik = '{$_SESSION['user_id']}' 
        AND status = 'ambil' 
        AND tgl_kembali BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 2 DAY)
        ORDER BY tgl_kembali");
    while ($r = mysqli_fetch_assoc($reminder_kembali)) {
        $hari_tersisa = (strtotime($r['tgl_kembali']) - strtotime(date('Y-m-d'))) / (60 * 60 * 24);
        $notifikasi[] = [
            'type' => 'warning',
            'icon' => 'clock',
            'title' => 'Reminder Pengembalian',
            'message' => "Mobil #{$r['nopol']} harus dikembalikan " . ($hari_tersisa == 0 ? 'HARI INI' : "dalam $hari_tersisa hari") . " (" . date('d/m/Y', strtotime($r['tgl_kembali'])) . ")"
        ];
    }

    // Cek transaksi terlambat
    $terlambat = db_query($conn, "SELECT * FROM tbl_transaksi 
        WHERE nik = '{$_SESSION['user_id']}' 
        AND status = 'ambil' 
        AND tgl_kembali < CURDATE()
        ORDER BY tgl_kembali");
    while ($r = mysqli_fetch_assoc($terlambat)) {
        $hari_terlambat = (strtotime(date('Y-m-d')) - strtotime($r['tgl_kembali'])) / (60 * 60 * 24);
        $denda = $hari_terlambat * $denda_per_hari;
        $notifikasi[] = [
            'type' => 'danger',
            'icon' => 'exclamation-triangle',
            'title' => 'TERLAMBAT!',
            'message' => "Mobil #{$r['nopol']} terlambat {$hari_terlambat} hari. Denda: Rp " . number_format($denda, 0, ',', '.')
        ];
    }
} else {
    // Untuk admin/petugas: cek semua transaksi terlambat
    $terlambat_count = db_get_row($conn, "SELECT COUNT(*) as total FROM tbl_transaksi WHERE status = 'ambil' AND tgl_kembali < CURDATE()")['total'];
    if ($terlambat_count > 0) {
        $notifikasi[] = [
            'type' => 'danger',
            'icon' => 'exclamation-triangle',
            'title' => 'Perhatian!',
            'message' => "Ada {$terlambat_count} transaksi yang sudah melewati batas pengembalian."
        ];
    }

    // Booking menunggu approval
    if ($booking > 0) {
        $notifikasi[] = [
            'type' => 'info',
            'icon' => 'hourglass-split',
            'title' => 'Booking Pending',
            'message' => "Ada {$booking} booking menunggu approval."
        ];
    }
}
?>

<div class="container-fluid">
    <!-- Alert Messages & Notifications -->
    <?php if ($error_msg || $success_msg || !empty($notifikasi)): ?>
        <div class="notification-wrapper">
            <?php if ($error_msg): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle"></i>
                    <span><?= $error_msg ?></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($success_msg): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i>
                    <span><?= $success_msg ?></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Notifikasi/Reminder -->
            <?php foreach ($notifikasi as $notif): ?>
                <div class="alert alert-<?= $notif['type'] ?> alert-dismissible fade show" role="alert">
                    <i class="bi bi-<?= $notif['icon'] ?>"></i>
                    <span><strong><?= $notif['title'] ?>:</strong> <?= $notif['message'] ?></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="page-header-modern mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="d-flex align-items-center gap-3">
                    <div class="page-icon">
                        <i class="bi bi-cart-fill"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 fw-bold">Data Transaksi</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="index.php?page=dashboard">Dashboard</a></li>
                                <li class="breadcrumb-item active">Transaksi</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <button type="button" class="btn btn-primary btn-lg px-4" data-bs-toggle="modal" data-bs-target="#modalTransaksi">
                    <i class="bi bi-plus-circle me-2"></i>Transaksi Baru
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="mini-stat-card">
                <div class="mini-stat-icon bg-primary-gradient">
                    <i class="bi bi-receipt"></i>
                </div>
                <div class="mini-stat-content">
                    <h3><?= $total_rows ?></h3>
                    <span>Total Transaksi</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="mini-stat-card">
                <div class="mini-stat-icon bg-warning-gradient">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div class="mini-stat-content">
                    <h3><?= $booking ?></h3>
                    <span>Booking</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="mini-stat-card">
                <div class="mini-stat-icon bg-info-gradient">
                    <i class="bi bi-car-front-fill"></i>
                </div>
                <div class="mini-stat-content">
                    <h3><?= $aktif ?></h3>
                    <span>Sedang Berjalan</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="mini-stat-card">
                <div class="mini-stat-icon bg-success-gradient">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="mini-stat-content">
                    <h3><?= $selesai ?></h3>
                    <span>Selesai</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card card-modern">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-table fs-5"></i>
                <h5 class="mb-0">Daftar Transaksi</h5>
            </div>
            <span class="badge bg-primary rounded-pill px-3 py-2"><?= $total_rows ?> Data</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-modern mb-0">
                    <thead>
                        <tr>
                            <th><i class="bi bi-hash"></i> ID</th>
                            <th><i class="bi bi-person"></i> Member</th>
                            <th><i class="bi bi-car-front"></i> Mobil</th>
                            <th><i class="bi bi-calendar"></i> Periode</th>
                            <th><i class="bi bi-cash"></i> Total</th>
                            <th><i class="bi bi-toggle-on"></i> Status</th>
                            <th class="text-center"><i class="bi bi-gear"></i> Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><span class="badge-modern badge-primary">#<?= $row['id_transaksi'] ?></span></td>
                                    <td>
                                        <div class="table-info-cell">
                                            <div class="table-avatar">
                                                <?= strtoupper(substr($row['nama'] ?? 'U', 0, 1)) ?>
                                            </div>
                                            <div class="info-text">
                                                <h6><?= $row['nama'] ?? 'Unknown' ?></h6>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="table-info-cell">
                                            <div class="table-avatar success">
                                                <i class="bi bi-car-front-fill"></i>
                                            </div>
                                            <div class="info-text">
                                                <h6><?= $row['brand'] ?></h6>
                                                <small><?= $row['type'] ?> (<?= $row['nopol'] ?>)</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="date-range">
                                            <div class="mb-1">
                                                <i class="bi bi-box-arrow-right text-success me-1"></i>
                                                <span><?= date('d/m/Y', strtotime($row['tgl_ambil'])) ?></span>
                                            </div>
                                            <div>
                                                <i class="bi bi-box-arrow-in-left text-danger me-1"></i>
                                                <span><?= date('d/m/Y', strtotime($row['tgl_kembali'])) ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        // Hitung denda jika terlambat dan status masih 'ambil'
                                        $denda = isset($row['denda']) ? $row['denda'] : 0;
                                        $hari_terlambat = 0;
                                        $denda_sementara = 0;

                                        if ($row['status'] == 'ambil' && strtotime($row['tgl_kembali']) < strtotime(date('Y-m-d'))) {
                                            $date1 = new DateTime($row['tgl_kembali']);
                                            $date2 = new DateTime(date('Y-m-d'));
                                            $hari_terlambat = $date1->diff($date2)->days;
                                            $denda_sementara = $hari_terlambat * $denda_per_hari;
                                        }
                                        ?>
                                        <span class="currency">Rp <?= number_format($row['total'], 0, ',', '.') ?></span>
                                        <?php if ($denda > 0): ?>
                                            <br><small class="text-danger"><i class="bi bi-exclamation-triangle"></i> Denda: Rp <?= number_format($denda, 0, ',', '.') ?></small>
                                        <?php elseif ($denda_sementara > 0): ?>
                                            <br><small class="text-warning"><i class="bi bi-clock"></i> +Denda: Rp <?= number_format($denda_sementara, 0, ',', '.') ?> (<?= $hari_terlambat ?> hari)</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $status_class = match ($row['status']) {
                                            'booking' => 'badge-warning',
                                            'approve' => 'badge-info',
                                            'ambil' => 'badge-primary',
                                            'kembali' => 'badge-success',
                                            default => 'badge-secondary'
                                        };
                                        $status_icon = match ($row['status']) {
                                            'booking' => 'hourglass-split',
                                            'approve' => 'check-circle',
                                            'ambil' => 'car-front',
                                            'kembali' => 'check-all',
                                            default => 'question-circle'
                                        };

                                        // Next status untuk tooltip
                                        $next_status = match ($row['status']) {
                                            'booking' => 'Approve',
                                            'approve' => 'Ambil',
                                            'ambil' => 'Kembali',
                                            'kembali' => 'Booking',
                                            default => 'Booking'
                                        };
                                        ?>

                                        <?php if ($_SESSION['user_level'] != 'member'): ?>
                                            <a href="index.php?page=transaksi&update_status=<?= $row['id_transaksi'] ?>"
                                                class="btn-status <?= $status_class ?>"
                                                data-bs-toggle="tooltip"
                                                title="Klik untuk ubah ke <?= $next_status ?>">
                                                <i class="bi bi-<?= $status_icon ?>"></i>
                                                <?= ucfirst($row['status']) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="badge-modern <?= $status_class ?>">
                                                <i class="bi bi-<?= $status_icon ?> me-1"></i>
                                                <?= ucfirst($row['status']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($_SESSION['user_level'] != 'member'): ?>
                                            <?php if (in_array($row['status'], ['booking', 'approve'])): ?>
                                                <a href="index.php?page=transaksi&cancel=<?= $row['id_transaksi'] ?>"
                                                    class="btn-action btn-warning"
                                                    data-bs-toggle="tooltip"
                                                    title="Batalkan Transaksi"
                                                    data-confirm="Yakin batalkan transaksi ini?">
                                                    <i class="bi bi-x-circle-fill"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="index.php?page=transaksi&edit=<?= $row['id_transaksi'] ?>"
                                                class="btn-action btn-edit"
                                                data-bs-toggle="tooltip"
                                                title="Edit Transaksi">
                                                <i class="bi bi-pencil-fill"></i>
                                            </a>
                                            <a href="index.php?page=transaksi&delete=<?= $row['id_transaksi'] ?>"
                                                class="btn-action btn-delete"
                                                data-bs-toggle="tooltip"
                                                title="Hapus Transaksi"
                                                data-confirm="Yakin hapus data transaksi ini?">
                                                <i class="bi bi-trash-fill"></i>
                                            </a>
                                        <?php else: ?>
                                            <?php if ($row['status'] == 'booking'): ?>
                                                <a href="index.php?page=transaksi&cancel=<?= $row['id_transaksi'] ?>"
                                                    class="btn btn-sm btn-outline-danger"
                                                    data-confirm="Yakin batalkan booking ini?">
                                                    <i class="bi bi-x-circle me-1"></i>Batalkan
                                                </a>
                                            <?php else: ?>
                                                <span class="badge-modern badge-info">
                                                    <i class="bi bi-eye me-1"></i>Lihat
                                                </span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="no-data-cell">
                                    <i class="bi bi-inbox d-block"></i>
                                    <p class="mb-0">Tidak ada data transaksi</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="p-3">
                    <nav>
                        <ul class="pagination justify-content-center mb-0">
                            <li class="page-item <?= $page_num <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="index.php?page=transaksi&p=<?= $page_num - 1 ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $i == $page_num ? 'active' : '' ?>">
                                    <a class="page-link" href="index.php?page=transaksi&p=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page_num >= $total_pages ? 'disabled' : '' ?>">
                                <a class="page-link" href="index.php?page=transaksi&p=<?= $page_num + 1 ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Form -->
<div class="modal fade" id="modalTransaksi" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content modal-modern">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-icon">
                        <i class="bi bi-<?= $edit_data ? 'pencil-square' : 'cart-plus' ?>"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0"><?= $edit_data ? 'Edit Transaksi' : 'Transaksi Baru' ?></h5>
                        <small class="text-muted"><?= $edit_data ? 'Ubah data transaksi' : 'Buat transaksi penyewaan baru' ?></small>
                    </div>
                </div>
                <a href="index.php?page=transaksi" class="btn-close btn-close-white"></a>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="<?= $edit_data ? 'edit' : 'add' ?>">
                    <?php if ($edit_data): ?>
                        <input type="hidden" name="id_transaksi" value="<?= $edit_data['id_transaksi'] ?>">
                    <?php endif; ?>

                    <div class="row g-3">
                        <?php if ($_SESSION['user_level'] != 'member'): ?>
                            <div class="col-md-6">
                                <label class="form-label"><i class="bi bi-person me-1"></i>Member</label>
                                <select name="nik" class="form-select form-select-lg" required>
                                    <option value="">-- Pilih Member --</option>
                                    <?php
                                    mysqli_data_seek($members, 0);
                                    while ($m = mysqli_fetch_assoc($members)):
                                    ?>
                                        <option value="<?= $m['nik'] ?>" <?= ($edit_data && $edit_data['nik'] == $m['nik']) ? 'selected' : '' ?>>
                                            <?= $m['nama'] ?> (<?= $m['nik'] ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        <?php else: ?>
                            <div class="col-md-6">
                                <label class="form-label"><i class="bi bi-person me-1"></i>Member</label>
                                <input type="text" class="form-control form-control-lg" value="<?= $_SESSION['nama'] ?? $_SESSION['username'] ?>" readonly>
                            </div>
                        <?php endif; ?>

                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-car-front me-1"></i>Mobil</label>
                            <select name="nopol" class="form-select form-select-lg" required id="mobilSelect">
                                <option value="">-- Pilih Mobil --</option>
                                <?php
                                mysqli_data_seek($mobils, 0);
                                while ($mb = mysqli_fetch_assoc($mobils)):
                                    // Tentukan status ketersediaan
                                    $is_available = ($mb['status'] == 'tersedia' && $mb['ada_booking'] == 0);
                                    $is_selected = ($edit_data && $edit_data['nopol'] == $mb['nopol']);

                                    // Skip mobil tidak tersedia kecuali sedang di-edit
                                    if (!$is_available && !$is_selected && !$edit_data) continue;

                                    $status_label = $is_available ? '✓ Tersedia' : '✗ Tidak Tersedia';
                                ?>
                                    <option value="<?= $mb['nopol'] ?>"
                                        data-harga="<?= $mb['harga'] ?>"
                                        <?= $is_selected ? 'selected' : '' ?>
                                        <?= (!$is_available && !$is_selected) ? 'disabled' : '' ?>>
                                        <?= $mb['brand'] ?> <?= $mb['type'] ?> - Rp <?= number_format($mb['harga'], 0, ',', '.') ?>/hari
                                        <?= !$is_available ? ' [TIDAK TERSEDIA]' : '' ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <div id="mobil_availability_info" class="mt-1"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-calendar-event me-1"></i>Tgl Booking</label>
                            <input type="text" class="form-control form-control-lg" value="<?= date('d/m/Y') ?>" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-box-arrow-right me-1"></i>Tgl Ambil</label>
                            <input type="date" name="tgl_ambil" class="form-control form-control-lg" required id="tglAmbil"
                                value="<?= $edit_data ? $edit_data['tgl_ambil'] : '' ?>" min="<?= date('Y-m-d') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-box-arrow-in-left me-1"></i>Tgl Kembali</label>
                            <input type="date" name="tgl_kembali" class="form-control form-control-lg" required id="tglKembali"
                                value="<?= $edit_data ? $edit_data['tgl_kembali'] : '' ?>">
                        </div>

                        <div class="col-md-6">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" name="supir" id="supir" value="1"
                                    <?= ($edit_data && $edit_data['supir']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="supir">
                                    <i class="bi bi-person-badge me-1"></i>Dengan Supir (+Rp 150.000/hari)
                                </label>
                            </div>
                        </div>

                        <?php if ($_SESSION['user_level'] != 'member'): ?>
                            <div class="col-md-6">
                                <label class="form-label"><i class="bi bi-toggle-on me-1"></i>Status</label>
                                <select name="status" class="form-select form-select-lg" required>
                                    <option value="booking" <?= ($edit_data && $edit_data['status'] == 'booking') ? 'selected' : '' ?>>⏳ Booking</option>
                                    <option value="approve" <?= ($edit_data && $edit_data['status'] == 'approve') ? 'selected' : '' ?>>✓ Approve</option>
                                    <option value="ambil" <?= ($edit_data && $edit_data['status'] == 'ambil') ? 'selected' : '' ?>>🚗 Ambil</option>
                                    <option value="kembali" <?= ($edit_data && $edit_data['status'] == 'kembali') ? 'selected' : '' ?>>✅ Kembali</option>
                                </select>
                            </div>
                        <?php else: ?>
                            <div class="col-md-6">
                                <label class="form-label"><i class="bi bi-toggle-on me-1"></i>Status</label>
                                <input type="text" class="form-control form-control-lg" value="⏳ Booking" readonly>
                            </div>
                        <?php endif; ?>

                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-cash-stack me-1"></i>Total Biaya</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="total" class="form-control" required id="totalBiaya" readonly
                                    value="<?= $edit_data ? $edit_data['total'] : '0' ?>">
                            </div>
                            <div id="biaya_info" class="mt-1"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-wallet2 me-1"></i>Uang Muka :</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="downpayment" class="form-control" required min="0"
                                    value="<?= $edit_data ? $edit_data['downpayment'] : '0' ?>">
                            </div>
                            <small class="text-muted">Minimal 30% dari total biaya</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="index.php?page=transaksi" class="btn btn-secondary btn-lg px-4">
                        <i class="bi bi-x-circle me-1"></i>Batal
                    </a>
                    <button type="submit" class="btn btn-primary btn-lg px-4">
                        <i class="bi bi-check-circle me-1"></i><?= $edit_data ? 'Update' : 'Simpan' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($edit_data): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            new bootstrap.Modal(document.getElementById('modalTransaksi')).show();
        });
    </script>
<?php endif; ?>

<script>
    const biayaSupirPerHari = <?= $biaya_supir_per_hari ?>;

    // Auto calculate total dengan biaya supir
    function calculateTotal() {
        const mobilSelect = document.getElementById('mobilSelect');
        const tglAmbil = document.getElementById('tglAmbil');
        const tglKembali = document.getElementById('tglKembali');
        const supir = document.getElementById('supir');
        const totalInput = document.getElementById('totalBiaya');
        const infoContainer = document.getElementById('biaya_info');

        if (mobilSelect.value && tglAmbil.value && tglKembali.value) {
            const harga = parseFloat(mobilSelect.options[mobilSelect.selectedIndex].dataset.harga) || 0;
            const date1 = new Date(tglAmbil.value);
            const date2 = new Date(tglKembali.value);
            const diffTime = date2 - date1;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) || 1;

            const biayaSewa = harga * diffDays;
            const biayaSupir = supir.checked ? (biayaSupirPerHari * diffDays) : 0;
            const total = biayaSewa + biayaSupir;

            totalInput.value = total;

            // Tampilkan info rincian biaya
            if (infoContainer) {
                let infoHtml = `<small class="text-muted">`;
                infoHtml += `Sewa: Rp ${biayaSewa.toLocaleString('id-ID')} (${diffDays} hari × Rp ${harga.toLocaleString('id-ID')})`;
                if (supir.checked) {
                    infoHtml += `<br>Supir: Rp ${biayaSupir.toLocaleString('id-ID')} (${diffDays} hari × Rp ${biayaSupirPerHari.toLocaleString('id-ID')})`;
                }
                infoHtml += `</small>`;
                infoContainer.innerHTML = infoHtml;
            }
        }
    }

    // Validasi tanggal kembali harus setelah tanggal ambil
    function validateDates() {
        const tglAmbil = document.getElementById('tglAmbil');
        const tglKembali = document.getElementById('tglKembali');

        if (tglAmbil.value) {
            tglKembali.min = tglAmbil.value;

            // Jika tgl kembali sebelum tgl ambil, reset
            if (tglKembali.value && tglKembali.value < tglAmbil.value) {
                tglKembali.value = tglAmbil.value;
            }
        }
        calculateTotal();
    }

    document.getElementById('mobilSelect')?.addEventListener('change', calculateTotal);
    document.getElementById('tglAmbil')?.addEventListener('change', validateDates);
    document.getElementById('tglKembali')?.addEventListener('change', calculateTotal);
    document.getElementById('supir')?.addEventListener('change', calculateTotal);

    // Trigger calculation on page load if editing
    document.addEventListener('DOMContentLoaded', function() {
        calculateTotal();
    });
</script>