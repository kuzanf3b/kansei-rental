<?php
// Mobil Page - CRUD Operations

// Handle POST (Add/Edit)
if (is_post()) {
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

// Handle Delete
if (isset($_GET['delete'])) {
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

// Pagination & Data
$page_num = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$paging = paginate($conn, 'tbl_mobil', $limit, $page_num);
$total_rows = $paging['total'];
$total_pages = $paging['pages'];

$result = db_query($conn, "SELECT * FROM tbl_mobil ORDER BY nopol LIMIT {$paging['offset']}, $limit");

// Get edit data
$edit_data = isset($_GET['edit'])
    ? db_get_row($conn, "SELECT * FROM tbl_mobil WHERE nopol='" . get($conn, 'edit') . "'")
    : null;

$tersedia = db_get_row($conn, "SELECT COUNT(*) as total FROM tbl_mobil WHERE status='tersedia'")['total'];
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header-modern mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="d-flex align-items-center gap-3">
                    <div class="page-icon">
                        <i class="bi bi-car-front-fill"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 fw-bold">Data Mobil</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="index.php?page=dashboard">Dashboard</a></li>
                                <li class="breadcrumb-item active">Data Mobil</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <button type="button" class="btn btn-primary btn-lg px-4" data-bs-toggle="modal" data-bs-target="#modalMobil">
                    <i class="bi bi-plus-circle me-2"></i>Tambah Mobil
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="mini-stat-card">
                <div class="mini-stat-icon bg-primary-gradient">
                    <i class="bi bi-car-front-fill"></i>
                </div>
                <div class="mini-stat-content">
                    <h3><?= $total_rows ?></h3>
                    <span>Total Mobil</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="mini-stat-card">
                <div class="mini-stat-icon bg-success-gradient">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="mini-stat-content">
                    <h3><?= $tersedia ?></h3>
                    <span>Tersedia</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="mini-stat-card">
                <div class="mini-stat-icon bg-danger-gradient">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
                <div class="mini-stat-content">
                    <h3><?= $total_rows - $tersedia ?></h3>
                    <span>Disewa</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="mini-stat-card">
                <div class="mini-stat-icon bg-info-gradient">
                    <i class="bi bi-percent"></i>
                </div>
                <div class="mini-stat-content">
                    <h3><?= $total_rows > 0 ? round(($tersedia / $total_rows) * 100) : 0 ?>%</h3>
                    <span>Ketersediaan</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card card-modern">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-table fs-5"></i>
                <h5 class="mb-0">Daftar Mobil</h5>
            </div>
            <span class="badge bg-primary rounded-pill px-3 py-2"><?= $total_rows ?> Data</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-modern mb-0">
                    <thead>
                        <tr>
                            <th><i class="bi bi-image"></i> Foto</th>
                            <th><i class="bi bi-hash"></i> No. Polisi</th>
                            <th><i class="bi bi-car-front"></i> Mobil</th>
                            <th><i class="bi bi-calendar"></i> Tahun</th>
                            <th><i class="bi bi-cash"></i> Harga/Hari</th>
                            <th><i class="bi bi-toggle-on"></i> Status</th>
                            <th class="text-center"><i class="bi bi-gear"></i> Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($row['foto']) && file_exists('uploads/mobil/' . $row['foto'])): ?>
                                            <img src="uploads/mobil/<?= $row['foto'] ?>" alt="<?= $row['brand'] ?>"
                                                class="img-thumbnail" style="width: 80px; height: 60px; object-fit: cover; border-radius: 8px;">
                                        <?php else: ?>
                                            <div class="d-flex align-items-center justify-content-center bg-light rounded"
                                                style="width: 80px; height: 60px;">
                                                <i class="bi bi-car-front text-muted fs-4"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge-modern badge-primary"><?= $row['nopol'] ?></span>
                                    </td>
                                    <td>
                                        <div class="table-info-cell">
                                            <div class="table-avatar">
                                                <?= strtoupper(substr($row['brand'], 0, 1)) ?>
                                            </div>
                                            <div class="info-text">
                                                <h6><?= $row['brand'] ?></h6>
                                                <small><?= $row['type'] ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="year-badge"><?= $row['tahun'] ?></span>
                                    </td>
                                    <td><span class="currency">Rp <?= number_format($row['harga'], 0, ',', '.') ?></span></td>
                                    <td>
                                        <span class="badge-modern <?= $row['status'] == 'tersedia' ? 'badge-success' : 'badge-danger' ?>">
                                            <i class="bi bi-<?= $row['status'] == 'tersedia' ? 'check-circle' : 'x-circle' ?> me-1"></i>
                                            <?= ucfirst($row['status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="index.php?page=mobil&edit=<?= $row['nopol'] ?>"
                                            class="btn-action btn-edit"
                                            data-bs-toggle="tooltip"
                                            title="Edit Data">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>
                                        <a href="index.php?page=mobil&delete=<?= $row['nopol'] ?>"
                                            class="btn-action btn-delete"
                                            data-bs-toggle="tooltip"
                                            title="Hapus Data"
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
                                    <p class="mb-0">Tidak ada data mobil</p>
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
                                <a class="page-link" href="index.php?page=mobil&p=<?= $page_num - 1 ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $i == $page_num ? 'active' : '' ?>">
                                    <a class="page-link" href="index.php?page=mobil&p=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page_num >= $total_pages ? 'disabled' : '' ?>">
                                <a class="page-link" href="index.php?page=mobil&p=<?= $page_num + 1 ?>">
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
                                value="<?= $edit_data ? $edit_data['nopol'] : '' ?>">
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
                            <input type="number" name="tahun" class="form-control form-control-lg" required min="2000" max="2030"
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