<?php
// Mobil Page - Card Grid Display dengan CRUD untuk Admin dan Booking untuk Member

$is_member = ($_SESSION['user_level'] == 'member');

// Biaya supir per hari (Rp 150.000)
$biaya_supir_per_hari = 150000;

// ============================================
// HANDLE BOOKING DARI MEMBER (POST dari detail view)
// ============================================
if (is_post() && $is_member && get_action() == 'booking') {
    $nopol = post($conn, 'nopol');
    $tgl_ambil = post($conn, 'tgl_ambil');
    $tgl_kembali = post($conn, 'tgl_kembali');
    $dengan_supir = isset($_POST['supir']) ? 1 : 0;
    $dp = post($conn, 'downpayment');
    $nik = $_SESSION['user_id'];

    // Get harga mobil
    $mobil_data = db_get_row($conn, "SELECT harga FROM tbl_mobil WHERE nopol='$nopol'");
    $harga_mobil = $mobil_data ? $mobil_data['harga'] : 0;

    // Hitung lama hari
    $date1 = new DateTime($tgl_ambil);
    $date2 = new DateTime($tgl_kembali);
    $lama_hari = $date1->diff($date2)->days;
    if ($lama_hari < 1) $lama_hari = 1;

    // Hitung biaya
    $biaya_sewa = $harga_mobil * $lama_hari;
    $biaya_supir = $dengan_supir ? ($biaya_supir_per_hari * $lama_hari) : 0;
    $total = $biaya_sewa + $biaya_supir;

    $data = [
        'nik'         => $nik,
        'nopol'       => $nopol,
        'tgl_booking' => date('Y-m-d'),
        'tgl_ambil'   => $tgl_ambil,
        'tgl_kembali' => $tgl_kembali,
        'supir'       => $dengan_supir,
        'total'       => $total,
        'downpayment' => $dp,
        'kekurangan'  => $total - $dp,
        'status'      => 'booking'
    ];

    db_insert($conn, 'tbl_transaksi', $data);
    redirect('index.php?page=transaksi&msg=booking_success');
}

// Handle POST (Add/Edit) - Hanya admin dan petugas
if (is_post() && !$is_member) {
    $action = get_action();
    $nopol = post($conn, 'nopol');

    $data = [
        'nopol' => $nopol,
        'brand' => post($conn, 'brand'),
        'type'  => post($conn, 'type'),
        'tahun' => post($conn, 'tahun'),
        'harga' => post($conn, 'harga'),
        'status' => post($conn, 'status')
    ];

    // Handle foto upload
    if (!empty($_FILES['foto']['name'])) {
        $data['foto'] = upload_file('foto', 'uploads/mobil/');
    }

    if ($action == 'add') {
        db_insert($conn, 'tbl_mobil', $data);
        redirect('index.php?page=mobil&msg=added');
    }

    if ($action == 'edit') {
        $old_nopol = post($conn, 'old_nopol');
        unset($data['nopol']);
        if (empty($_FILES['foto']['name'])) unset($data['foto']);
        db_update($conn, 'tbl_mobil', $data, "nopol='$old_nopol'");
        redirect('index.php?page=mobil&msg=updated');
    }
}

// Handle Delete - Hanya admin dan petugas
if (isset($_GET['delete']) && !$is_member) {
    $nopol = get($conn, 'delete');
    $result = db_delete($conn, 'tbl_mobil', "nopol='$nopol'", [
        'table' => 'tbl_transaksi',
        'column' => 'nopol',
        'value' => $nopol
    ]);

    if ($result['success']) {
        redirect('index.php?page=mobil&msg=deleted');
    } else {
        redirect('index.php?page=mobil&msg=fk_error&ref=' . $result['reason']);
    }
}

// Check if viewing detail
$view_detail = isset($_GET['detail']) ? get($conn, 'detail') : null;

// Get mobil detail if viewing
if ($view_detail) {
    $mobil_detail = db_get_row($conn, "SELECT * FROM tbl_mobil WHERE nopol='$view_detail'");
    if (!$mobil_detail) {
        redirect('index.php?page=mobil');
    }
    // Get rental count
    $rental_count = db_get_row($conn, "SELECT COUNT(*) as total FROM tbl_transaksi WHERE nopol='$view_detail'")['total'];
}

// Pagination & Data
$page_num = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$items_per_page = 8; // 4x2 grid
$paging = paginate($conn, 'tbl_mobil', $items_per_page, $page_num);
$total_rows = $paging['total'];
$total_pages = $paging['pages'];

// Filter status
$status_filter = isset($_GET['status']) ? get($conn, 'status') : '';
$where_clause = $status_filter ? "WHERE status='$status_filter'" : "";

$result = db_query($conn, "SELECT * FROM tbl_mobil $where_clause ORDER BY brand, type LIMIT {$paging['offset']}, $items_per_page");

// Get edit data
$edit_data = isset($_GET['edit'])
    ? db_get_row($conn, "SELECT * FROM tbl_mobil WHERE nopol='" . get($conn, 'edit') . "'")
    : null;

$tersedia = db_get_row($conn, "SELECT COUNT(*) as total FROM tbl_mobil WHERE status='tersedia'")['total'];

// Member data untuk booking (jika member login)
if ($is_member) {
    $member_data = db_get_row($conn, "SELECT * FROM tbl_member WHERE nik='{$_SESSION['user_id']}'");
}
?>

<?php if ($view_detail): ?>
    <!-- DETAIL VIEW -->
    <div class="mobil-detail-page">
        <div class="detail-header">
            <a href="index.php?page=mobil" class="btn-back">
                <i class="bi bi-arrow-left"></i>
                <span>Kembali ke Daftar Mobil</span>
            </a>
        </div>

        <div class="detail-content">
            <div class="detail-left">
                <!-- Main Image -->
                <div class="detail-image-main">
                    <?php
                    $foto = !empty($mobil_detail['foto']) && file_exists('uploads/mobil/' . $mobil_detail['foto'])
                        ? 'uploads/mobil/' . $mobil_detail['foto']
                        : 'https://via.placeholder.com/600x400?text=' . urlencode($mobil_detail['brand'] . ' ' . $mobil_detail['type']);
                    ?>
                    <img src="<?= $foto ?>" alt="<?= $mobil_detail['brand'] . ' ' . $mobil_detail['type'] ?>">
                    <div class="image-badge <?= $mobil_detail['status'] == 'tersedia' ? 'available' : 'rented' ?>">
                        <?= ucfirst($mobil_detail['status']) ?>
                    </div>
                </div>

                <!-- Car Info -->
                <div class="detail-info-section">
                    <h2 class="detail-title"><?= $mobil_detail['brand'] . ' ' . $mobil_detail['type'] ?></h2>
                    <div class="detail-meta">
                        <span class="meta-item">
                            <i class="bi bi-star-fill"></i>
                            4.5 <small>(<?= $rental_count ?> rental)</small>
                        </span>
                        <span class="meta-item">
                            <i class="bi bi-credit-card-2-front"></i>
                            <?= $mobil_detail['nopol'] ?>
                        </span>
                    </div>
                </div>

                <!-- Key Information -->
                <div class="detail-info-card">
                    <h4><i class="bi bi-info-circle"></i> Informasi Kendaraan</h4>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-icon"><i class="bi bi-building"></i></div>
                            <div class="info-text">
                                <span>Brand</span>
                                <strong><?= $mobil_detail['brand'] ?></strong>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon"><i class="bi bi-car-front"></i></div>
                            <div class="info-text">
                                <span>Type</span>
                                <strong><?= $mobil_detail['type'] ?></strong>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon"><i class="bi bi-calendar3"></i></div>
                            <div class="info-text">
                                <span>Tahun</span>
                                <strong><?= $mobil_detail['tahun'] ?></strong>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon"><i class="bi bi-speedometer2"></i></div>
                            <div class="info-text">
                                <span>Kondisi</span>
                                <strong>Terawat</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="detail-right">
                <!-- Price & Booking Card -->
                <div class="booking-card">
                    <div class="price-section">
                        <span class="price-label">Harga Sewa Mobil</span>
                        <div class="price-value">
                            Rp <?= number_format($mobil_detail['harga'], 0, ',', '.') ?>
                            <span>/hari</span>
                        </div>
                    </div>

                    <?php if ($mobil_detail['status'] == 'tersedia'): ?>
                        <?php if ($is_member): ?>
                            <!-- FULL BOOKING FORM untuk Member -->
                            <form action="index.php?page=mobil&detail=<?= $mobil_detail['nopol'] ?>" method="POST" class="booking-form" id="bookingForm">
                                <input type="hidden" name="action" value="booking">
                                <input type="hidden" name="nopol" value="<?= $mobil_detail['nopol'] ?>">

                                <!-- Tanggal Booking (readonly) -->
                                <div class="form-section">
                                    <h5><i class="bi bi-calendar-check"></i> Informasi Booking</h5>

                                    <div class="form-group">
                                        <label><i class="bi bi-calendar-date"></i> Tanggal Booking</label>
                                        <input type="text" class="form-control" value="<?= date('d/m/Y') ?>" readonly>
                                        <small class="text-muted">Tanggal booking otomatis</small>
                                    </div>

                                    <div class="form-group">
                                        <label><i class="bi bi-calendar-event"></i> Tanggal Ambil <span class="text-danger">*</span></label>
                                        <input type="date" name="tgl_ambil" id="tgl_ambil" class="form-control"
                                            min="<?= date('Y-m-d') ?>" required onchange="hitungBiaya()">
                                    </div>

                                    <div class="form-group">
                                        <label><i class="bi bi-calendar-x"></i> Tanggal Kembali <span class="text-danger">*</span></label>
                                        <input type="date" name="tgl_kembali" id="tgl_kembali" class="form-control"
                                            min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required onchange="hitungBiaya()">
                                    </div>
                                </div>

                                <!-- Opsi Supir -->
                                <div class="form-section">
                                    <h5><i class="bi bi-person-badge"></i> Opsi Tambahan</h5>

                                    <div class="supir-option">
                                        <label class="supir-checkbox">
                                            <input type="checkbox" name="supir" id="supir" value="1" onchange="hitungBiaya()">
                                            <div class="supir-content">
                                                <div class="supir-icon">
                                                    <i class="bi bi-person-fill-gear"></i>
                                                </div>
                                                <div class="supir-info">
                                                    <strong>Dengan Supir</strong>
                                                    <span>Rp <?= number_format($biaya_supir_per_hari, 0, ',', '.') ?>/hari</span>
                                                </div>
                                                <div class="supir-check">
                                                    <i class="bi bi-check-circle-fill"></i>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- DP -->
                                <div class="form-section">
                                    <h5><i class="bi bi-cash-stack"></i> Pembayaran</h5>

                                    <div class="form-group">
                                        <label><i class="bi bi-wallet2"></i> Down Payment (DP) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="number" name="downpayment" id="downpayment" class="form-control"
                                                min="0" required placeholder="Masukkan jumlah DP" onchange="hitungBiaya()">
                                        </div>
                                        <small class="text-muted">Minimal DP 30% dari total biaya</small>
                                    </div>
                                </div>

                                <!-- Rincian Biaya -->
                                <div class="booking-summary">
                                    <h5><i class="bi bi-receipt"></i> Rincian Biaya</h5>
                                    <div class="summary-item">
                                        <span>Lama Sewa</span>
                                        <strong id="lama_hari">0 hari</strong>
                                    </div>
                                    <div class="summary-item">
                                        <span>Biaya Sewa Mobil</span>
                                        <strong id="biaya_sewa">Rp 0</strong>
                                    </div>
                                    <div class="summary-item" id="row_biaya_supir" style="display:none;">
                                        <span>Biaya Supir</span>
                                        <strong id="biaya_supir">Rp 0</strong>
                                    </div>
                                    <div class="summary-item total">
                                        <span>Total Biaya</span>
                                        <strong id="total_biaya">Rp 0</strong>
                                    </div>
                                    <div class="summary-item dp">
                                        <span>Down Payment</span>
                                        <strong id="dp_display">Rp 0</strong>
                                    </div>
                                    <div class="summary-item kekurangan">
                                        <span>Kekurangan</span>
                                        <strong id="kekurangan">Rp 0</strong>
                                    </div>
                                </div>

                                <!-- Informasi Penyewa -->
                                <div class="booking-info">
                                    <div class="info-row">
                                        <span>Penyewa</span>
                                        <strong><?= $member_data['nama'] ?? $_SESSION['nama'] ?></strong>
                                    </div>
                                    <div class="info-row">
                                        <span>NIK</span>
                                        <strong><?= $member_data['nik'] ?? '-' ?></strong>
                                    </div>
                                    <div class="info-row">
                                        <span>No. HP</span>
                                        <strong><?= $member_data['no_hp'] ?? '-' ?></strong>
                                    </div>
                                </div>

                                <button type="submit" class="btn-booking">
                                    <i class="bi bi-check-circle"></i>
                                    <span>Konfirmasi Booking</span>
                                </button>
                            </form>

                            <script>
                                const hargaMobil = <?= $mobil_detail['harga'] ?>;
                                const biayaSupirPerHari = <?= $biaya_supir_per_hari ?>;

                                function hitungBiaya() {
                                    const tglAmbil = document.getElementById('tgl_ambil').value;
                                    const tglKembali = document.getElementById('tgl_kembali').value;
                                    const denganSupir = document.getElementById('supir').checked;
                                    const dp = parseFloat(document.getElementById('downpayment').value) || 0;

                                    if (tglAmbil && tglKembali) {
                                        const date1 = new Date(tglAmbil);
                                        const date2 = new Date(tglKembali);
                                        let lamaHari = Math.ceil((date2 - date1) / (1000 * 60 * 60 * 24));
                                        if (lamaHari < 1) lamaHari = 1;

                                        const biayaSewa = hargaMobil * lamaHari;
                                        const biayaSupir = denganSupir ? (biayaSupirPerHari * lamaHari) : 0;
                                        const total = biayaSewa + biayaSupir;
                                        const kekurangan = total - dp;

                                        document.getElementById('lama_hari').textContent = lamaHari + ' hari';
                                        document.getElementById('biaya_sewa').textContent = 'Rp ' + biayaSewa.toLocaleString('id-ID');
                                        document.getElementById('biaya_supir').textContent = 'Rp ' + biayaSupir.toLocaleString('id-ID');
                                        document.getElementById('total_biaya').textContent = 'Rp ' + total.toLocaleString('id-ID');
                                        document.getElementById('dp_display').textContent = 'Rp ' + dp.toLocaleString('id-ID');
                                        document.getElementById('kekurangan').textContent = 'Rp ' + kekurangan.toLocaleString('id-ID');

                                        document.getElementById('row_biaya_supir').style.display = denganSupir ? 'flex' : 'none';
                                    }
                                }
                            </script>
                        <?php else: ?>
                            <!-- Admin/Staff View -->
                            <div class="admin-actions">
                                <a href="index.php?page=transaksi&book=<?= $mobil_detail['nopol'] ?>" class="btn-booking">
                                    <i class="bi bi-plus-circle"></i>
                                    <span>Buat Transaksi</span>
                                </a>
                                <a href="index.php?page=mobil&edit=<?= $mobil_detail['nopol'] ?>" class="btn-edit-detail">
                                    <i class="bi bi-pencil"></i>
                                    <span>Edit Mobil</span>
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="not-available">
                            <i class="bi bi-x-circle"></i>
                            <span>Mobil sedang disewa</span>
                            <small>Silakan pilih mobil lain yang tersedia</small>
                        </div>
                    <?php endif; ?>

                    <!-- Features -->
                    <div class="features-list">
                        <h5><i class="bi bi-shield-check"></i> Keunggulan</h5>
                        <ul>
                            <li><i class="bi bi-check-circle-fill"></i> Asuransi lengkap</li>
                            <li><i class="bi bi-check-circle-fill"></i> Mobil terawat</li>
                            <li><i class="bi bi-check-circle-fill"></i> Dokumen lengkap</li>
                            <li><i class="bi bi-check-circle-fill"></i> Bebas banjir & kecelakaan</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- GRID VIEW -->
    <div class="mobil-grid-page">
        <!-- Page Header -->
        <div class="grid-header">
            <div class="header-left">
                <h1><i class="bi bi-car-front-fill"></i> Koleksi Mobil</h1>
                <p>Pilih mobil impian Anda dari koleksi <?= $total_rows ?> armada kami</p>
            </div>
            <div class="header-right">
                <?php if (!$is_member): ?>
                    <button type="button" class="btn-add-mobil" data-bs-toggle="modal" data-bs-target="#modalMobil">
                        <i class="bi bi-plus-circle"></i>
                        <span>Tambah Mobil</span>
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filter & Stats -->
        <div class="filter-bar">
            <div class="filter-stats">
                <div class="stat-chip">
                    <i class="bi bi-car-front"></i>
                    <span><?= $total_rows ?> Total</span>
                </div>
                <div class="stat-chip available">
                    <i class="bi bi-check-circle"></i>
                    <span><?= $tersedia ?> Tersedia</span>
                </div>
                <div class="stat-chip rented">
                    <i class="bi bi-x-circle"></i>
                    <span><?= $total_rows - $tersedia ?> Disewa</span>
                </div>
            </div>
            <div class="filter-actions">
                <a href="index.php?page=mobil" class="filter-btn <?= !$status_filter ? 'active' : '' ?>">Semua</a>
                <a href="index.php?page=mobil&status=tersedia" class="filter-btn <?= $status_filter == 'tersedia' ? 'active' : '' ?>">Tersedia</a>
                <a href="index.php?page=mobil&status=tidak" class="filter-btn <?= $status_filter == 'tidak' ? 'active' : '' ?>">Disewa</a>
            </div>
        </div>

        <!-- Cars Grid -->
        <div class="cars-grid-container">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($mobil = mysqli_fetch_assoc($result)):
                    $foto = !empty($mobil['foto']) && file_exists('uploads/mobil/' . $mobil['foto'])
                        ? 'uploads/mobil/' . $mobil['foto']
                        : 'https://via.placeholder.com/300x200?text=' . urlencode($mobil['brand']);
                    $statusClass = $mobil['status'] == 'tersedia' ? 'available' : 'rented';
                    // Get rental count for this car
                    $car_rentals = db_get_row($conn, "SELECT COUNT(*) as total FROM tbl_transaksi WHERE nopol='{$mobil['nopol']}'")['total'];
                ?>
                    <div class="car-card-grid">
                        <div class="car-badge-grid <?= $statusClass ?>">
                            <?= $mobil['brand'] ?>
                        </div>
                        <div class="car-favorite-grid">
                            <i class="bi bi-heart"></i>
                        </div>

                        <a href="index.php?page=mobil&detail=<?= $mobil['nopol'] ?>" class="car-image-grid">
                            <img src="<?= $foto ?>" alt="<?= $mobil['brand'] . ' ' . $mobil['type'] ?>">
                        </a>

                        <div class="car-content-grid">
                            <a href="index.php?page=mobil&detail=<?= $mobil['nopol'] ?>" class="car-name-grid">
                                <?= $mobil['brand'] . ' ' . $mobil['type'] ?>
                            </a>

                            <div class="car-rating-grid">
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-half"></i>
                                <span>4.5 (<?= $car_rentals ?> rental)</span>
                            </div>

                            <div class="car-specs-grid">
                                <div class="spec-item">
                                    <i class="bi bi-calendar3"></i>
                                    <span><?= $mobil['tahun'] ?></span>
                                </div>
                                <div class="spec-item">
                                    <i class="bi bi-credit-card-2-front"></i>
                                    <span><?= $mobil['nopol'] ?></span>
                                </div>
                            </div>

                            <div class="car-footer-grid">
                                <div class="car-price-grid">
                                    <span class="price">Rp <?= number_format($mobil['harga'], 0, ',', '.') ?></span>
                                    <span class="period">/hari</span>
                                </div>

                                <?php if ($mobil['status'] == 'tersedia'): ?>
                                    <a href="index.php?page=mobil&detail=<?= $mobil['nopol'] ?>" class="btn-detail-grid">
                                        <span>Detail</span>
                                        <i class="bi bi-arrow-right"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="btn-detail-grid disabled">Disewa</span>
                                <?php endif; ?>
                            </div>

                            <?php if (!$is_member): ?>
                                <div class="admin-actions-grid">
                                    <a href="index.php?page=mobil&edit=<?= $mobil['nopol'] ?>" class="action-btn edit" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="index.php?page=mobil&delete=<?= $mobil['nopol'] ?>"
                                        class="action-btn delete"
                                        title="Hapus"
                                        data-confirm="Yakin hapus mobil <?= $mobil['brand'] . ' ' . $mobil['type'] ?>?">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-cars-grid">
                    <i class="bi bi-car-front"></i>
                    <h3>Tidak ada mobil ditemukan</h3>
                    <p>Coba ubah filter pencarian Anda</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination-grid">
                <a href="index.php?page=mobil&p=<?= max(1, $page_num - 1) ?><?= $status_filter ? '&status=' . $status_filter : '' ?>"
                    class="page-btn <?= $page_num <= 1 ? 'disabled' : '' ?>">
                    <i class="bi bi-chevron-left"></i>
                </a>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="index.php?page=mobil&p=<?= $i ?><?= $status_filter ? '&status=' . $status_filter : '' ?>"
                        class="page-btn <?= $i == $page_num ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
                <a href="index.php?page=mobil&p=<?= min($total_pages, $page_num + 1) ?><?= $status_filter ? '&status=' . $status_filter : '' ?>"
                    class="page-btn <?= $page_num >= $total_pages ? 'disabled' : '' ?>">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Form untuk Admin -->
    <?php if (!$is_member): ?>
        <div class="modal fade" id="modalMobil" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content modal-modern">
                    <div class="modal-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="modal-icon">
                                <i class="bi bi-<?= $edit_data ? 'pencil-square' : 'car-front-fill' ?>"></i>
                            </div>
                            <div>
                                <h5 class="modal-title mb-0"><?= $edit_data ? 'Edit Mobil' : 'Tambah Mobil Baru' ?></h5>
                                <small class="text-muted"><?= $edit_data ? 'Ubah data mobil yang sudah ada' : 'Masukkan data mobil baru' ?></small>
                            </div>
                        </div>
                        <a href="index.php?page=mobil" class="btn-close btn-close-white"></a>
                    </div>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="<?= $edit_data ? 'edit' : 'add' ?>">
                            <?php if ($edit_data): ?>
                                <input type="hidden" name="old_nopol" value="<?= $edit_data['nopol'] ?>">
                            <?php endif; ?>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label"><i class="bi bi-card-text me-1"></i>No. Polisi</label>
                                    <input type="text" name="nopol" class="form-control form-control-lg" required
                                        placeholder="B 1234 ABC"
                                        value="<?= $edit_data ? $edit_data['nopol'] : '' ?>"
                                        <?= $edit_data ? 'readonly' : '' ?>>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label"><i class="bi bi-building me-1"></i>Brand</label>
                                    <input type="text" name="brand" class="form-control form-control-lg" required
                                        placeholder="Toyota, Honda, dll"
                                        value="<?= $edit_data ? $edit_data['brand'] : '' ?>">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label"><i class="bi bi-car-front me-1"></i>Type</label>
                                    <input type="text" name="type" class="form-control form-control-lg" required
                                        placeholder="Avanza, Jazz, dll"
                                        value="<?= $edit_data ? $edit_data['type'] : '' ?>">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label"><i class="bi bi-calendar me-1"></i>Tahun</label>
                                    <input type="number" name="tahun" class="form-control form-control-lg" required min="1800" max="2030"
                                        placeholder="2024"
                                        value="<?= $edit_data ? $edit_data['tahun'] : date('Y') ?>">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label"><i class="bi bi-cash me-1"></i>Harga Sewa/Hari</label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" name="harga" class="form-control" required
                                            placeholder="350000"
                                            value="<?= $edit_data ? $edit_data['harga'] : '' ?>">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label"><i class="bi bi-toggle-on me-1"></i>Status</label>
                                    <select name="status" class="form-select form-select-lg" required>
                                        <option value="tersedia" <?= ($edit_data && $edit_data['status'] == 'tersedia') ? 'selected' : '' ?>>✓ Tersedia</option>
                                        <option value="tidak" <?= ($edit_data && $edit_data['status'] == 'tidak') ? 'selected' : '' ?>>✗ Tidak Tersedia</option>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label"><i class="bi bi-image me-1"></i>Foto Mobil</label>
                                    <input type="file" name="foto" class="form-control form-control-lg" accept="image/*">
                                    <?php if ($edit_data && $edit_data['foto']): ?>
                                        <small class="text-muted mt-1 d-block">
                                            <i class="bi bi-check-circle text-success me-1"></i>Foto saat ini: <?= $edit_data['foto'] ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <a href="index.php?page=mobil" class="btn btn-secondary btn-lg px-4">
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
                    new bootstrap.Modal(document.getElementById('modalMobil')).show();
                });
            </script>
        <?php endif; ?>
    <?php endif; ?>

<?php endif; ?>