<?php
// Pengembalian Page - CRUD Operations
// Halaman ini hanya untuk admin dan petugas
if ($_SESSION['user_level'] == 'member') {
    redirect('index.php?page=dashboard');
}

// Handle POST (Add/Edit)
if (is_post()) {
    $action = get_action();

    $data = [
        'tgl_kembali'   => post($conn, 'tgl_kembali'),
        'kondisi_mobil' => post($conn, 'kondisi_mobil'),
        'denda'         => post($conn, 'denda')
    ];

    if ($action == 'add') {
        $id_transaksi = post($conn, 'id_transaksi');
        $data['id_transaksi'] = $id_transaksi;

        db_insert($conn, 'tbl_kembali', $data);

        // Update status transaksi & mobil
        db_update($conn, 'tbl_transaksi', ['status' => 'kembali'], "id_transaksi='$id_transaksi'");
        $trans = db_get_row($conn, "SELECT nopol FROM tbl_transaksi WHERE id_transaksi='$id_transaksi'");
        if ($trans) {
            db_update($conn, 'tbl_mobil', ['status' => 'tersedia'], "nopol='{$trans['nopol']}'");
        }
        redirect('index.php?page=kembali&msg=added');
    }

    if ($action == 'edit') {
        $id = post($conn, 'id_kembali');
        db_update($conn, 'tbl_kembali', $data, "id_kembali='$id'");
        redirect('index.php?page=kembali&msg=updated');
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = get($conn, 'delete');
    $kembali = db_get_row($conn, "SELECT id_transaksi FROM tbl_kembali WHERE id_kembali='$id'");

    if ($kembali) {
        db_update($conn, 'tbl_transaksi', ['status' => 'ambil'], "id_transaksi='{$kembali['id_transaksi']}'");
        $trans = db_get_row($conn, "SELECT nopol FROM tbl_transaksi WHERE id_transaksi='{$kembali['id_transaksi']}'");
        if ($trans) {
            db_update($conn, 'tbl_mobil', ['status' => 'tidak'], "nopol='{$trans['nopol']}'");
        }
    }
    db_delete($conn, 'tbl_kembali', "id_kembali='$id'");
    redirect('index.php?page=kembali&msg=deleted');
}

// Pagination & Data
$page_num = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$paging = paginate($conn, 'tbl_kembali', $limit, $page_num);
$total_rows = $paging['total'];
$total_pages = $paging['pages'];

$result = db_query($conn, "SELECT k.*, t.nopol, t.tgl_ambil, t.tgl_kembali as tgl_seharusnya, m.nama, mb.brand, mb.type 
    FROM tbl_kembali k 
    LEFT JOIN tbl_transaksi t ON k.id_transaksi = t.id_transaksi 
    LEFT JOIN tbl_member m ON t.nik = m.nik 
    LEFT JOIN tbl_mobil mb ON t.nopol = mb.nopol 
    ORDER BY k.id_kembali DESC 
    LIMIT {$paging['offset']}, $limit");

// Dropdown - Transaksi yang belum dikembalikan
$transaksis = db_query($conn, "SELECT t.*, m.nama, mb.brand, mb.type 
    FROM tbl_transaksi t 
    LEFT JOIN tbl_member m ON t.nik = m.nik 
    LEFT JOIN tbl_mobil mb ON t.nopol = mb.nopol 
    WHERE t.status = 'ambil' 
    ORDER BY t.id_transaksi DESC");

// Get edit data
$edit_data = isset($_GET['edit'])
    ? db_get_row($conn, "SELECT * FROM tbl_kembali WHERE id_kembali='" . get($conn, 'edit') . "'")
    : null;

// Stats
$total_denda = db_get_row($conn, "SELECT COALESCE(SUM(denda), 0) as total FROM tbl_kembali")['total'];
$dengan_denda = db_get_row($conn, "SELECT COUNT(*) as total FROM tbl_kembali WHERE denda > 0")['total'];
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header-modern mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="d-flex align-items-center gap-3">
                    <div class="page-icon">
                        <i class="bi bi-arrow-return-left"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 fw-bold">Data Pengembalian</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="index.php?page=dashboard">Dashboard</a></li>
                                <li class="breadcrumb-item active">Pengembalian</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <button type="button" class="btn btn-primary btn-lg px-4" data-bs-toggle="modal" data-bs-target="#modalKembali">
                    <i class="bi bi-plus-circle me-2"></i>Catat Pengembalian
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="mini-stat-card">
                <div class="mini-stat-icon bg-primary-gradient">
                    <i class="bi bi-arrow-return-left"></i>
                </div>
                <div class="mini-stat-content">
                    <h3><?= $total_rows ?></h3>
                    <span>Total Pengembalian</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="mini-stat-card">
                <div class="mini-stat-icon bg-success-gradient">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="mini-stat-content">
                    <h3><?= $total_rows - $dengan_denda ?></h3>
                    <span>Tanpa Denda</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="mini-stat-card">
                <div class="mini-stat-icon bg-danger-gradient">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <div class="mini-stat-content">
                    <h3><?= $dengan_denda ?></h3>
                    <span>Dengan Denda</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="mini-stat-card">
                <div class="mini-stat-icon bg-warning-gradient">
                    <i class="bi bi-cash-coin"></i>
                </div>
                <div class="mini-stat-content">
                    <h3>Rp <?= number_format($total_denda / 1000, 0) ?>K</h3>
                    <span>Total Denda</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card card-modern">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-table fs-5"></i>
                <h5 class="mb-0">Daftar Pengembalian</h5>
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
                            <th><i class="bi bi-calendar"></i> Tgl Kembali</th>
                            <th><i class="bi bi-clipboard"></i> Kondisi</th>
                            <th><i class="bi bi-exclamation-triangle"></i> Denda</th>
                            <th class="text-center"><i class="bi bi-gear"></i> Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><span class="badge-modern badge-primary">#<?= $row['id_kembali'] ?></span></td>
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
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-calendar-check text-success me-2"></i>
                                            <span><?= date('d/m/Y', strtotime($row['tgl_kembali'])) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span data-bs-toggle="tooltip" title="<?= $row['kondisi_mobil'] ?>">
                                            <?= strlen($row['kondisi_mobil']) > 25 ? substr($row['kondisi_mobil'], 0, 25) . '...' : $row['kondisi_mobil'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($row['denda'] > 0): ?>
                                            <span class="badge-modern badge-danger">
                                                <i class="bi bi-exclamation-circle me-1"></i>
                                                Rp <?= number_format($row['denda'], 0, ',', '.') ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-modern badge-success">
                                                <i class="bi bi-check-circle me-1"></i>Tidak ada
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="index.php?page=kembali&edit=<?= $row['id_kembali'] ?>"
                                            class="btn-action btn-edit"
                                            data-bs-toggle="tooltip"
                                            title="Edit">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>
                                        <a href="index.php?page=kembali&delete=<?= $row['id_kembali'] ?>"
                                            class="btn-action btn-delete"
                                            data-bs-toggle="tooltip"
                                            title="Hapus"
                                            data-confirm="Yakin hapus data pengembalian ini?">
                                            <i class="bi bi-trash-fill"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="no-data-cell">
                                    <i class="bi bi-inbox d-block"></i>
                                    <p class="mb-0">Tidak ada data pengembalian</p>
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
                                <a class="page-link" href="index.php?page=kembali&p=<?= $page_num - 1 ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $i == $page_num ? 'active' : '' ?>">
                                    <a class="page-link" href="index.php?page=kembali&p=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page_num >= $total_pages ? 'disabled' : '' ?>">
                                <a class="page-link" href="index.php?page=kembali&p=<?= $page_num + 1 ?>">
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
<div class="modal fade" id="modalKembali" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-modern">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-icon">
                        <i class="bi bi-<?= $edit_data ? 'pencil-square' : 'arrow-return-left' ?>"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0"><?= $edit_data ? 'Edit Pengembalian' : 'Catat Pengembalian' ?></h5>
                        <small class="text-muted"><?= $edit_data ? 'Ubah data pengembalian' : 'Catat pengembalian mobil' ?></small>
                    </div>
                </div>
                <a href="index.php?page=kembali" class="btn-close btn-close-white"></a>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="<?= $edit_data ? 'edit' : 'add' ?>">
                    <?php if ($edit_data): ?>
                        <input type="hidden" name="id_kembali" value="<?= $edit_data['id_kembali'] ?>">
                    <?php endif; ?>

                    <div class="row g-3">
                        <?php if (!$edit_data): ?>
                            <div class="col-12">
                                <label class="form-label"><i class="bi bi-receipt me-1"></i>Transaksi</label>
                                <select name="id_transaksi" class="form-select form-select-lg" required>
                                    <option value="">-- Pilih Transaksi --</option>
                                    <?php while ($t = mysqli_fetch_assoc($transaksis)): ?>
                                        <option value="<?= $t['id_transaksi'] ?>">
                                            #<?= $t['id_transaksi'] ?> - <?= $t['nama'] ?> | <?= $t['brand'] ?> <?= $t['type'] ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="col-12">
                            <label class="form-label"><i class="bi bi-calendar-check me-1"></i>Tanggal Pengembalian</label>
                            <input type="date" name="tgl_kembali" class="form-control form-control-lg" required
                                value="<?= $edit_data ? $edit_data['tgl_kembali'] : date('Y-m-d') ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label"><i class="bi bi-clipboard-check me-1"></i>Kondisi Mobil</label>
                            <textarea name="kondisi_mobil" class="form-control" rows="3" required
                                placeholder="Jelaskan kondisi mobil saat dikembalikan..."><?= $edit_data ? $edit_data['kondisi_mobil'] : '' ?></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label"><i class="bi bi-exclamation-triangle me-1"></i>Denda (Rp)</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="denda" class="form-control" required min="0"
                                    placeholder="0"
                                    value="<?= $edit_data ? $edit_data['denda'] : '0' ?>">
                            </div>
                            <small class="text-muted">Isi 0 jika tidak ada denda</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="index.php?page=kembali" class="btn btn-secondary btn-lg px-4">
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
            new bootstrap.Modal(document.getElementById('modalKembali')).show();
        });
    </script>
<?php endif; ?>