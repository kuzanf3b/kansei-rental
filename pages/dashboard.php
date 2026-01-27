<?php
// Dashboard Page - Supabase Style with Tokyo Night Theme

$is_member = ($_SESSION['user_level'] == 'member');

// Get statistics - berbeda untuk member
$mobil_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_mobil"))['total'];
$mobil_tersedia = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_mobil WHERE status='tersedia'"))['total'];
$mobil_disewa = $mobil_total - $mobil_tersedia;
$member_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_member"))['total'];

// Untuk member, hanya tampilkan transaksi miliknya
if ($is_member) {
    $member_nik = $_SESSION['user_id'];
    $transaksi_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_transaksi WHERE nik='$member_nik'"))['total'];
    $transaksi_aktif = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_transaksi WHERE nik='$member_nik' AND status IN ('booking', 'approve', 'ambil')"))['total'];
    $transaksi_selesai = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_transaksi WHERE nik='$member_nik' AND status = 'kembali'"))['total'];
    $pending_booking = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_transaksi WHERE nik='$member_nik' AND status='booking'"))['total'];
    $perlu_kembali = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_transaksi WHERE nik='$member_nik' AND status='ambil' AND tgl_kembali < CURDATE()"))['total'];
    $pendapatan = 0;
    $belum_lunas = 0;
} else {
    $transaksi_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_transaksi"))['total'];
    $transaksi_aktif = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_transaksi WHERE status IN ('booking', 'approve', 'ambil')"))['total'];
    $transaksi_selesai = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_transaksi WHERE status = 'kembali'"))['total'];
    $pending_booking = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_transaksi WHERE status='booking'"))['total'];
    $perlu_kembali = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_transaksi WHERE status='ambil' AND tgl_kembali < CURDATE()"))['total'];
    // Get total pendapatan
    $pendapatan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total_bayar), 0) as total FROM tbl_bayar WHERE status='lunas'"))['total'];
    // Get pembayaran belum lunas
    $belum_lunas = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total_bayar), 0) as total FROM tbl_bayar WHERE status='belum lunas'"))['total'];
}

// Get mobil paling sering disewa
$mobil_populer = mysqli_query($conn, "SELECT mb.brand, mb.type, mb.nopol, COUNT(t.id_transaksi) as total_sewa 
                                       FROM tbl_mobil mb 
                                       LEFT JOIN tbl_transaksi t ON mb.nopol = t.nopol 
                                       GROUP BY mb.nopol 
                                       ORDER BY total_sewa DESC 
                                       LIMIT 5");

// Calculate percentages
$mobil_percentage = $mobil_total > 0 ? round(($mobil_tersedia / $mobil_total) * 100) : 0;
?>

<div class="supa-dashboard">
    <!-- Header -->
    <div class="supa-header">
        <h1>Selamat datang, <?= $_SESSION['username'] ?>!</h1>
        <p id="currentDateTime">Loading...</p>
    </div>

    <!-- Divider -->
    <div class="supa-divider"></div>

    <!-- Alerts -->
    <?php if ($pending_booking > 0 || $perlu_kembali > 0): ?>
        <div class="mb-4">
            <?php if ($pending_booking > 0): ?>
                <div class="supa-alert warning">
                    <i class="bi bi-clock-fill"></i>
                    <div class="supa-alert-content">
                        <strong><?= $pending_booking ?> Booking Menunggu <?= $is_member ? 'Diproses' : 'Persetujuan' ?></strong>
                        <span><?= $is_member ? 'Booking Anda sedang menunggu persetujuan' : 'Ada booking baru yang perlu di-approve' ?></span>
                    </div>
                    <a href="index.php?page=transaksi" class="btn btn-sm">Lihat</a>
                </div>
            <?php endif; ?>
            <?php if ($perlu_kembali > 0): ?>
                <div class="supa-alert danger">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div class="supa-alert-content">
                        <strong><?= $perlu_kembali ?> Mobil Terlambat Kembali</strong>
                        <span><?= $is_member ? 'Segera kembalikan mobil yang Anda sewa' : 'Ada mobil yang melewati batas waktu pengembalian' ?></span>
                    </div>
                    <a href="index.php?page=<?= $is_member ? 'transaksi' : 'kembali' ?>" class="btn btn-sm"><?= $is_member ? 'Lihat' : 'Proses' ?></a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Stats Grid -->
    <div class="supa-stats-grid">
        <div class="supa-stat-card">
            <div class="supa-stat-icon primary">
                <i class="bi bi-car-front"></i>
            </div>
            <div class="supa-stat-label"><?= $is_member ? 'Mobil Tersedia' : 'Total Mobil' ?></div>
            <div class="supa-stat-value"><?= $is_member ? $mobil_tersedia : $mobil_total ?></div>
            <div class="supa-stat-sub positive">
                <i class="bi bi-check-circle"></i>
                <?= $is_member ? 'Siap disewa' : $mobil_tersedia . ' tersedia' ?>
            </div>
        </div>

        <?php if (!$is_member): ?>
            <div class="supa-stat-card">
                <div class="supa-stat-icon success">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div class="supa-stat-label">Total Pendapatan</div>
                <div class="supa-stat-value">Rp <?= number_format($pendapatan / 1000000, 1) ?>M</div>
                <div class="supa-stat-sub positive">
                    <i class="bi bi-graph-up-arrow"></i>
                    Lunas terbayar
                </div>
            </div>

            <div class="supa-stat-card">
                <div class="supa-stat-icon warning">
                    <i class="bi bi-people"></i>
                </div>
                <div class="supa-stat-label">Total Member</div>
                <div class="supa-stat-value"><?= $member_total ?></div>
                <div class="supa-stat-sub">
                    <i class="bi bi-person-check"></i>
                    Member terdaftar
                </div>
            </div>
        <?php else: ?>
            <div class="supa-stat-card">
                <div class="supa-stat-icon success">
                    <i class="bi bi-receipt"></i>
                </div>
                <div class="supa-stat-label">Total Transaksi Saya</div>
                <div class="supa-stat-value"><?= $transaksi_total ?></div>
                <div class="supa-stat-sub positive">
                    <i class="bi bi-check-circle"></i>
                    <?= $transaksi_selesai ?> selesai
                </div>
            </div>

            <div class="supa-stat-card">
                <div class="supa-stat-icon warning">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div class="supa-stat-label">Menunggu Approval</div>
                <div class="supa-stat-value"><?= $pending_booking ?></div>
                <div class="supa-stat-sub">
                    <i class="bi bi-clock"></i>
                    Booking pending
                </div>
            </div>
        <?php endif; ?>

        <div class="supa-stat-card">
            <div class="supa-stat-icon info">
                <i class="bi bi-receipt"></i>
            </div>
            <div class="supa-stat-label">Transaksi Aktif</div>
            <div class="supa-stat-value"><?= $transaksi_aktif ?></div>
            <div class="supa-stat-sub">
                <i class="bi bi-arrow-repeat"></i>
                Sedang berjalan
            </div>
        </div>
    </div>

    <!-- Secondary Stats -->
    <div class="supa-stats-grid mb-4">
        <div class="supa-stat-card">
            <div class="supa-stat-icon purple">
                <i class="bi bi-<?= $is_member ? 'car-front' : 'key' ?>"></i>
            </div>
            <div class="supa-stat-label"><?= $is_member ? 'Transaksi Aktif' : 'Mobil Disewa' ?></div>
            <div class="supa-stat-value"><?= $is_member ? $transaksi_aktif : $mobil_disewa ?></div>
            <div class="supa-stat-sub">
                <i class="bi bi-<?= $is_member ? 'arrow-repeat' : 'car-front-fill' ?>"></i>
                <?= $is_member ? 'Sedang berjalan' : 'Sedang dipakai' ?>
            </div>
        </div>

        <?php if (!$is_member): ?>
            <div class="supa-stat-card">
                <div class="supa-stat-icon danger">
                    <i class="bi bi-credit-card"></i>
                </div>
                <div class="supa-stat-label">Belum Lunas</div>
                <div class="supa-stat-value">Rp <?= number_format($belum_lunas / 1000, 0) ?>K</div>
                <div class="supa-stat-sub negative">
                    <i class="bi bi-exclamation-circle"></i>
                    Perlu ditagih
                </div>
            </div>
        <?php endif; ?>

        <div class="supa-stat-card armada-card">
            <div class="supa-stat-label mb-3">Ketersediaan Armada</div>
            <div class="d-flex justify-content-between mb-2 armada-info">
                <span class="text-success">Tersedia: <?= $mobil_tersedia ?></span>
                <span class="text-danger">Disewa: <?= $mobil_disewa ?></span>
            </div>
            <div class="supa-progress">
                <div class="supa-progress-bar success" style="width: <?= $mobil_percentage ?>%;"></div>
            </div>
            <div class="armada-percent"><?= $mobil_percentage ?>% armada tersedia untuk disewa</div>
        </div>
    </div>

    <!-- Quick Actions -->
    <h5 class="supa-section-title"><i class="bi bi-lightning-charge"></i> Aksi Cepat</h5>
    <div class="supa-quick-actions">
        <a href="index.php?page=transaksi" class="supa-quick-btn">
            <i class="bi bi-plus-circle"></i>
            <?= $is_member ? 'Booking Mobil' : 'Transaksi Baru' ?>
        </a>
        <a href="index.php?page=mobil" class="supa-quick-btn">
            <i class="bi bi-car-front-fill"></i>
            <?= $is_member ? 'Lihat Mobil' : 'Tambah Mobil' ?>
        </a>
        <?php if (!$is_member): ?>
            <a href="index.php?page=member" class="supa-quick-btn">
                <i class="bi bi-person-plus"></i>
                Tambah Member
            </a>
            <a href="index.php?page=kembali" class="supa-quick-btn">
                <i class="bi bi-arrow-return-left"></i>
                Pengembalian
            </a>
            <a href="index.php?page=bayar" class="supa-quick-btn">
                <i class="bi bi-wallet2"></i>
                Pembayaran
            </a>
            <?php if ($_SESSION['user_level'] == 'admin'): ?>
                <a href="index.php?page=user" class="supa-quick-btn">
                    <i class="bi bi-gear"></i>
                    Pengaturan
                </a>
            <?php endif; ?>
        <?php else: ?>
            <a href="index.php?page=transaksi" class="supa-quick-btn">
                <i class="bi bi-list-check"></i>
                Riwayat Transaksi
            </a>
        <?php endif; ?>
    </div>

    <!-- Divider -->
    <div class="supa-divider"></div>

    <!-- Main Content Grid -->
    <div class="supa-grid supa-grid-2">
        <!-- Recent Transactions -->
        <div class="supa-card">
            <div class="supa-card-header">
                <h3><i class="bi bi-clock-history"></i> Transaksi Terbaru</h3>
                <a href="index.php?page=transaksi" class="supa-link-btn">
                    Lihat Semua <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="supa-card-body p-0">
                <div class="table-responsive">
                    <table class="supa-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Member</th>
                                <th>Mobil</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Member hanya lihat transaksinya sendiri
                            $trans_where = $is_member ? "WHERE t.nik = '{$_SESSION['user_id']}'" : "";
                            $query = "SELECT t.*, m.nama, mb.brand, mb.type 
                                      FROM tbl_transaksi t 
                                      LEFT JOIN tbl_member m ON t.nik = m.nik 
                                      LEFT JOIN tbl_mobil mb ON t.nopol = mb.nopol 
                                      $trans_where
                                      ORDER BY t.id_transaksi DESC LIMIT 5";
                            $result = mysqli_query($conn, $query);

                            if (mysqli_num_rows($result) > 0):
                                while ($row = mysqli_fetch_assoc($result)):
                                    $icon = match ($row['status']) {
                                        'booking' => 'clock',
                                        'approve' => 'check-circle',
                                        'ambil' => 'car-front',
                                        default => 'check-all'
                                    };
                            ?>
                                    <tr>
                                        <td><span class="text-muted">#<?= $row['id_transaksi'] ?></span></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="supa-avatar">
                                                    <?= strtoupper(substr($row['nama'], 0, 1)) ?>
                                                </div>
                                                <?= $row['nama'] ?>
                                            </div>
                                        </td>
                                        <td><?= $row['brand'] . ' ' . $row['type'] ?></td>
                                        <td><?= date('d M Y', strtotime($row['tgl_booking'])) ?></td>
                                        <td>
                                            <span class="supa-badge <?= $row['status'] ?>">
                                                <i class="bi bi-<?= $icon ?>"></i>
                                                <?= ucfirst($row['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php
                                endwhile;
                            else:
                                ?>
                                <tr>
                                    <td colspan="5">
                                        <div class="supa-empty">
                                            <i class="bi bi-inbox"></i>
                                            <p>Belum ada transaksi</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sidebar Cards -->
        <div class="d-flex flex-column gap-4">
            <!-- Popular Cars -->
            <div class="supa-card">
                <div class="supa-card-header">
                    <h3><i class="bi bi-star"></i> Mobil Populer</h3>
                </div>
                <div class="supa-card-body">
                    <?php if (mysqli_num_rows($mobil_populer) > 0): ?>
                        <?php $rank = 1;
                        while ($mp = mysqli_fetch_assoc($mobil_populer)):
                            $rankClass = match ($rank) {
                                1 => 'gold',
                                2 => 'silver',
                                3 => 'bronze',
                                default => 'default'
                            };
                        ?>
                            <div class="supa-rank-item">
                                <div class="supa-rank-num <?= $rankClass ?>"><?= $rank ?></div>
                                <div class="supa-rank-info">
                                    <h6><?= $mp['brand'] . ' ' . $mp['type'] ?></h6>
                                    <span><?= $mp['nopol'] ?></span>
                                </div>
                                <div class="supa-rank-count"><?= $mp['total_sewa'] ?>x</div>
                            </div>
                        <?php $rank++;
                        endwhile; ?>
                    <?php else: ?>
                        <div class="supa-empty">
                            <i class="bi bi-car-front"></i>
                            <p>Belum ada data</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- System Info -->
            <div class="supa-card">
                <div class="supa-card-header">
                    <h3><i class="bi bi-info-circle"></i> Informasi Sistem</h3>
                </div>
                <div class="supa-card-body p-0">
                    <ul class="supa-info-list px-4">
                        <li>
                            <span class="label">Versi Sistem</span>
                            <span class="value">v2.0.0</span>
                        </li>
                        <li>
                            <span class="label">Total Transaksi</span>
                            <span class="value"><?= $transaksi_total ?></span>
                        </li>
                        <li>
                            <span class="label">Transaksi Selesai</span>
                            <span class="value success"><?= $transaksi_selesai ?></span>
                        </li>
                        <li>
                            <span class="label">User Login</span>
                            <span class="value"><?= $_SESSION['username'] ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Update datetime
    function updateDateTime() {
        const now = new Date();
        const options = {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        };
        const dateTimeElement = document.getElementById('currentDateTime');
        if (dateTimeElement) {
            dateTimeElement.textContent = now.toLocaleDateString('id-ID', options);
        }
    }

    setInterval(updateDateTime, 60000);
    updateDateTime();
</script>