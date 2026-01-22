<?php
// Transaksi Page - CRUD Operations

// Handle POST (Add/Edit)
if (is_post()) {
    $action = get_action();

    $nopol = post($conn, 'nopol');
    $status = post($conn, 'status');
    $total = post($conn, 'total');
    $dp = post($conn, 'downpayment');

    $data = [
        'nik'         => post($conn, 'nik'),
        'nopol'       => $nopol,
        'tgl_booking' => post($conn, 'tgl_booking'),
        'tgl_ambil'   => post($conn, 'tgl_ambil'),
        'tgl_kembali' => post($conn, 'tgl_kembali'),
        'supir'       => isset($_POST['supir']) ? 1 : 0,
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

    if ($action == 'edit') {
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

// Handle Delete
if (isset($_GET['delete'])) {
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
$paging = paginate($conn, 'tbl_transaksi', $limit, $page_num);
$total_rows = $paging['total'];
$total_pages = $paging['pages'];

$result = db_query($conn, "SELECT t.*, m.nama, mb.brand, mb.type 
    FROM tbl_transaksi t 
    LEFT JOIN tbl_member m ON t.nik = m.nik 
    LEFT JOIN tbl_mobil mb ON t.nopol = mb.nopol 
    ORDER BY t.id_transaksi DESC 
    LIMIT {$paging['offset']}, $limit");

// Dropdown data
$members = db_query($conn, "SELECT nik, nama FROM tbl_member ORDER BY nama");
$mobils = db_query($conn, "SELECT nopol, brand, type, harga FROM tbl_mobil WHERE status='tersedia' ORDER BY brand");

// Get edit data
$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_data = db_get_row($conn, "SELECT * FROM tbl_transaksi WHERE id_transaksi='" . get($conn, 'edit') . "'");
    $mobils = db_query($conn, "SELECT nopol, brand, type, harga FROM tbl_mobil ORDER BY brand");
}

// Stats
$booking = db_get_row($conn, "SELECT COUNT(*) as total FROM tbl_transaksi WHERE status='booking'")['total'];
$aktif = db_get_row($conn, "SELECT COUNT(*) as total FROM tbl_transaksi WHERE status IN ('ambil', 'approve')")['total'];
$selesai = db_get_row($conn, "SELECT COUNT(*) as total FROM tbl_transaksi WHERE status='kembali'")['total'];
?>

<div class="container-fluid">
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
                                            <div class="table-avatar" style="background: linear-gradient(135deg, #10b981, #059669);">
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
                                    <td><span class="currency">Rp <?= number_format($row['total'], 0, ',', '.') ?></span></td>
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
                                        ?>
                                        <span class="badge-modern <?= $status_class ?>">
                                            <i class="bi bi-<?= $status_icon ?> me-1"></i>
                                            <?= ucfirst($row['status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
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
                                            onclick="return confirm('Yakin hapus data ini?')">
                                            <i class="bi bi-trash-fill"></i>
                                        </a>
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

                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-car-front me-1"></i>Mobil</label>
                            <select name="nopol" class="form-select form-select-lg" required id="mobilSelect">
                                <option value="">-- Pilih Mobil --</option>
                                <?php
                                mysqli_data_seek($mobils, 0);
                                while ($mb = mysqli_fetch_assoc($mobils)):
                                ?>
                                    <option value="<?= $mb['nopol'] ?>" data-harga="<?= $mb['harga'] ?>"
                                        <?= ($edit_data && $edit_data['nopol'] == $mb['nopol']) ? 'selected' : '' ?>>
                                        <?= $mb['brand'] ?> <?= $mb['type'] ?> - Rp <?= number_format($mb['harga'], 0, ',', '.') ?>/hari
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label"><i class="bi bi-calendar-event me-1"></i>Tgl Booking</label>
                            <input type="date" name="tgl_booking" class="form-control form-control-lg" required
                                value="<?= $edit_data ? $edit_data['tgl_booking'] : date('Y-m-d') ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label"><i class="bi bi-box-arrow-right me-1"></i>Tgl Ambil</label>
                            <input type="date" name="tgl_ambil" class="form-control form-control-lg" required id="tglAmbil"
                                value="<?= $edit_data ? $edit_data['tgl_ambil'] : '' ?>">
                        </div>

                        <div class="col-md-4">
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

                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-toggle-on me-1"></i>Status</label>
                            <select name="status" class="form-select form-select-lg" required>
                                <option value="booking" <?= ($edit_data && $edit_data['status'] == 'booking') ? 'selected' : '' ?>>⏳ Booking</option>
                                <option value="approve" <?= ($edit_data && $edit_data['status'] == 'approve') ? 'selected' : '' ?>>✓ Approve</option>
                                <option value="ambil" <?= ($edit_data && $edit_data['status'] == 'ambil') ? 'selected' : '' ?>>🚗 Ambil</option>
                                <option value="kembali" <?= ($edit_data && $edit_data['status'] == 'kembali') ? 'selected' : '' ?>>✅ Kembali</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-cash-stack me-1"></i>Total Biaya</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="total" class="form-control" required id="totalBiaya"
                                    value="<?= $edit_data ? $edit_data['total'] : '0' ?>">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-wallet2 me-1"></i>Down Payment</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="downpayment" class="form-control" required
                                    value="<?= $edit_data ? $edit_data['downpayment'] : '0' ?>">
                            </div>
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
    // Auto calculate total
    function calculateTotal() {
        const mobilSelect = document.getElementById('mobilSelect');
        const tglAmbil = document.getElementById('tglAmbil');
        const tglKembali = document.getElementById('tglKembali');
        const supir = document.getElementById('supir');
        const totalInput = document.getElementById('totalBiaya');

        if (mobilSelect.value && tglAmbil.value && tglKembali.value) {
            const harga = mobilSelect.options[mobilSelect.selectedIndex].dataset.harga || 0;
            const date1 = new Date(tglAmbil.value);
            const date2 = new Date(tglKembali.value);
            const diffTime = Math.abs(date2 - date1);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) || 1;

            let total = harga * diffDays;
            if (supir.checked) {
                total += 150000 * diffDays;
            }

            totalInput.value = total;
        }
    }

    document.getElementById('mobilSelect')?.addEventListener('change', calculateTotal);
    document.getElementById('tglAmbil')?.addEventListener('change', calculateTotal);
    document.getElementById('tglKembali')?.addEventListener('change', calculateTotal);
    document.getElementById('supir')?.addEventListener('change', calculateTotal);
</script>