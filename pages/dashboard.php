<?php
// Dashboard Page - Enhanced Version

// Get statistics
$mobil_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_mobil"))['total'];
$mobil_tersedia = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_mobil WHERE status='tersedia'"))['total'];
$mobil_disewa = $mobil_total - $mobil_tersedia;
$member_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_member"))['total'];
$transaksi_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_transaksi"))['total'];
$transaksi_aktif = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_transaksi WHERE status IN ('booking', 'approve', 'ambil')"))['total'];
$transaksi_selesai = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_transaksi WHERE status = 'kembali'"))['total'];

// Get total pendapatan
$pendapatan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total_bayar), 0) as total FROM tbl_bayar WHERE status='lunas'"))['total'];

// Get pembayaran belum lunas
$belum_lunas = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total_bayar), 0) as total FROM tbl_bayar WHERE status='belum lunas'"))['total'];

// Get mobil paling sering disewa
$mobil_populer = mysqli_query($conn, "SELECT mb.brand, mb.type, mb.nopol, COUNT(t.id_transaksi) as total_sewa 
                                       FROM tbl_mobil mb 
                                       LEFT JOIN tbl_transaksi t ON mb.nopol = t.nopol 
                                       GROUP BY mb.nopol 
                                       ORDER BY total_sewa DESC 
                                       LIMIT 5");

// Get transaksi perlu diproses
$pending_booking = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_transaksi WHERE status='booking'"))['total'];
$perlu_kembali = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_transaksi WHERE status='ambil' AND tgl_kembali < CURDATE()"))['total'];

// Calculate percentages
$mobil_percentage = $mobil_total > 0 ? round(($mobil_tersedia / $mobil_total) * 100) : 0;
?>

<div class="container-fluid">
    <!-- Welcome Banner -->
    <div class="welcome-banner animate__animated animate__fadeIn">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2 style="color: #1a1b26;"><i class="bi bi-emoji-smile me-2"></i>Selamat Datang, <?= $_SESSION['username'] ?>!</h2>
                <p class="mb-3" style="color: #24283b;">Kelola bisnis rental mobil JDM Anda dengan mudah dan efisien.</p>
                <div class="datetime-display" style="color: #1a1b26;">
                    <i class="bi bi-calendar-event me-2"></i>
                    <span id="currentDateTime">Loading...</span>
                </div>
            </div>
            <div class="col-md-4 text-end d-none d-md-block">
                <i class="bi bi-car-front" style="font-size: 8rem; opacity: 0.2; color: #1a1b26;"></i>
            </div>
        </div>
    </div>

    <!-- Alert Notifications -->
    <?php if ($pending_booking > 0 || $perlu_kembali > 0): ?>
        <div class="row mb-4">
            <?php if ($pending_booking > 0): ?>
                <div class="col-md-6 mb-3">
                    <div class="alert alert-warning d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-3" style="font-size: 1.5rem;"></i>
                        <div>
                            <strong><?= $pending_booking ?> Booking Menunggu</strong><br>
                            <small>Ada booking yang perlu di-approve</small>
                        </div>
                        <a href="index.php?page=transaksi" class="btn btn-warning btn-sm ms-auto">Lihat</a>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($perlu_kembali > 0): ?>
                <div class="col-md-6 mb-3">
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="bi bi-clock-fill me-3" style="font-size: 1.5rem;"></i>
                        <div>
                            <strong><?= $perlu_kembali ?> Terlambat Kembali</strong><br>
                            <small>Ada mobil yang melewati batas pengembalian</small>
                        </div>
                        <a href="index.php?page=kembali" class="btn btn-danger btn-sm ms-auto">Proses</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="stat-card bg-primary-gradient animate__animated animate__fadeInUp">
                <div class="position-relative">
                    <h6 class="mb-1" style="color: #1a1b26;">Total Mobil</h6>
                    <h2 class="mb-2 fw-bold" style="color: #1a1b26;"><?= $mobil_total ?></h2>
                    <small style="color: #24283b;"><i class="bi bi-check-circle me-1"></i><?= $mobil_tersedia ?> tersedia</small>
                    <i class="bi bi-car-front stat-icon"></i>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="stat-card bg-success-gradient animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">
                <div class="position-relative">
                    <h6 class="mb-1" style="color: #1a1b26;">Total Pendapatan</h6>
                    <h2 class="mb-2 fw-bold" style="color: #1a1b26;">Rp <?= number_format($pendapatan / 1000000, 1) ?>M</h2>
                    <small style="color: #24283b;"><i class="bi bi-graph-up me-1"></i>Lunas terbayar</small>
                    <i class="bi bi-cash-stack stat-icon"></i>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="stat-card bg-warning-gradient animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
                <div class="position-relative">
                    <h6 class="mb-1" style="color: #1a1b26;">Total Member</h6>
                    <h2 class="mb-2 fw-bold" style="color: #1a1b26;"><?= $member_total ?></h2>
                    <small style="color: #24283b;"><i class="bi bi-person-check me-1"></i>Member terdaftar</small>
                    <i class="bi bi-people stat-icon"></i>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="stat-card bg-info-gradient animate__animated animate__fadeInUp" style="animation-delay: 0.3s;">
                <div class="position-relative">
                    <h6 class="mb-1" style="color: #1a1b26;">Transaksi Aktif</h6>
                    <h2 class="mb-2 fw-bold" style="color: #1a1b26;"><?= $transaksi_aktif ?></h2>
                    <small style="color: #24283b;"><i class="bi bi-clock me-1"></i>Sedang berjalan</small>
                    <i class="bi bi-cart-check stat-icon"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Second Row Stats -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="stat-card bg-purple-gradient animate__animated animate__fadeInUp" style="animation-delay: 0.4s;">
                <div class="position-relative">
                    <h6 class="mb-1" style="color: #1a1b26;">Mobil Disewa</h6>
                    <h2 class="mb-2 fw-bold" style="color: #1a1b26;"><?= $mobil_disewa ?></h2>
                    <small style="color: #24283b;"><i class="bi bi-key me-1"></i>Sedang dipakai</small>
                    <i class="bi bi-car-front-fill stat-icon"></i>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="stat-card bg-danger-gradient animate__animated animate__fadeInUp" style="animation-delay: 0.5s;">
                <div class="position-relative">
                    <h6 class="mb-1" style="color: #1a1b26;">Belum Lunas</h6>
                    <h2 class="mb-2 fw-bold" style="color: #1a1b26;">Rp <?= number_format($belum_lunas / 1000, 0) ?>K</h2>
                    <small style="color: #24283b;"><i class="bi bi-exclamation-circle me-1"></i>Perlu ditagih</small>
                    <i class="bi bi-credit-card stat-icon"></i>
                </div>
            </div>
        </div>

        <div class="col-xl-6 col-lg-4 col-md-12">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3"><i class="bi bi-bar-chart me-2"></i>Ketersediaan Mobil</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Tersedia: <?= $mobil_tersedia ?></span>
                        <span>Disewa: <?= $mobil_disewa ?></span>
                    </div>
                    <div class="progress" style="height: 12px;">
                        <div class="progress-bar bg-success" style="width: <?= $mobil_percentage ?>%"></div>
                        <div class="progress-bar bg-danger" style="width: <?= 100 - $mobil_percentage ?>%"></div>
                    </div>
                    <small class="text-muted mt-2 d-block"><?= $mobil_percentage ?>% mobil tersedia untuk disewa</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <h5 class="fw-bold mb-3"><i class="bi bi-lightning-charge me-2"></i>Aksi Cepat</h5>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <a href="index.php?page=transaksi" class="quick-action">
                <i class="bi bi-plus-circle"></i>
                <h6 class="mb-0">Transaksi Baru</h6>
            </a>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <a href="index.php?page=mobil" class="quick-action">
                <i class="bi bi-car-front-fill"></i>
                <h6 class="mb-0">Tambah Mobil</h6>
            </a>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <a href="index.php?page=member" class="quick-action">
                <i class="bi bi-person-plus"></i>
                <h6 class="mb-0">Tambah Member</h6>
            </a>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <a href="index.php?page=kembali" class="quick-action">
                <i class="bi bi-arrow-return-left"></i>
                <h6 class="mb-0">Pengembalian</h6>
            </a>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <a href="index.php?page=bayar" class="quick-action">
                <i class="bi bi-cash"></i>
                <h6 class="mb-0">Pembayaran</h6>
            </a>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <a href="index.php?page=user" class="quick-action">
                <i class="bi bi-gear"></i>
                <h6 class="mb-0">Pengaturan</h6>
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Recent Transactions -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0" style="color: #1a1b26;"><i class="bi bi-clock-history me-2"></i>Transaksi Terbaru</h5>
                    <a href="index.php?page=transaksi" class="btn btn-sm btn-light">Lihat Semua</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Member</th>
                                    <th>Mobil</th>
                                    <th>Tgl Booking</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query = "SELECT t.*, m.nama, mb.brand, mb.type 
                                          FROM tbl_transaksi t 
                                          LEFT JOIN tbl_member m ON t.nik = m.nik 
                                          LEFT JOIN tbl_mobil mb ON t.nopol = mb.nopol 
                                          ORDER BY t.id_transaksi DESC LIMIT 5";
                                $result = mysqli_query($conn, $query);

                                if (mysqli_num_rows($result) > 0):
                                    while ($row = mysqli_fetch_assoc($result)):
                                ?>
                                        <tr>
                                            <td><span class="badge bg-tn-secondary">#<?= $row['id_transaksi'] ?></span></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-circle bg-primary me-2">
                                                        <?= strtoupper(substr($row['nama'], 0, 1)) ?>
                                                    </div>
                                                    <?= $row['nama'] ?>
                                                </div>
                                            </td>
                                            <td><?= $row['brand'] . ' ' . $row['type'] ?></td>
                                            <td><?= date('d M Y', strtotime($row['tgl_booking'])) ?></td>
                                            <td>
                                                <span class="badge status-<?= $row['status'] ?>">
                                                    <i class="bi bi-<?=
                                                                    $row['status'] == 'booking' ? 'clock' : ($row['status'] == 'approve' ? 'check' : ($row['status'] == 'ambil' ? 'car-front' : 'check-all'))
                                                                    ?> me-1"></i>
                                                    <?= ucfirst($row['status']) ?>
                                                </span>
                                            </td>
                                            <td><strong>Rp <?= number_format($row['total'], 0, ',', '.') ?></strong></td>
                                        </tr>
                                    <?php
                                    endwhile;
                                else:
                                    ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                                            <p class="text-muted mb-0 mt-2">Belum ada transaksi</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Popular Cars & Info -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0" style="color: #1a1b26;"><i class="bi bi-star me-2"></i>Mobil Populer</h5>
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($mobil_populer) > 0): ?>
                        <?php $rank = 1;
                        while ($mp = mysqli_fetch_assoc($mobil_populer)): ?>
                            <div class="info-card mb-3">
                                <div class="info-icon <?= $rank == 1 ? 'bg-warning' : ($rank == 2 ? 'bg-secondary' : 'bg-light') ?>">
                                    <?= $rank ?>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0"><?= $mp['brand'] . ' ' . $mp['type'] ?></h6>
                                    <small class="text-muted"><?= $mp['nopol'] ?></small>
                                </div>
                                <span class="badge bg-tn-primary"><?= $mp['total_sewa'] ?>x</span>
                            </div>
                        <?php $rank++;
                        endwhile; ?>
                    <?php else: ?>
                        <p class="text-muted text-center mb-0">Belum ada data</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- System Info -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0" style="color: #1a1b26;"><i class="bi bi-info-circle me-2"></i>Informasi Sistem</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Versi Sistem</span>
                        <span class="fw-semibold">v2.0.0</span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Total Transaksi</span>
                        <span class="fw-semibold"><?= $transaksi_total ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Transaksi Selesai</span>
                        <span class="fw-semibold text-success"><?= $transaksi_selesai ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span class="text-muted">User Login</span>
                        <span class="fw-semibold"><?= $_SESSION['username'] ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>