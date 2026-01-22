<?php
// Pembayaran Page - CRUD Operations

// Handle POST (Add/Edit)
if (is_post()) {
    $action = get_action();

    $data = [
        'tgl_bayar'   => post($conn, 'tgl_bayar'),
        'total_bayar' => post($conn, 'total_bayar'),
        'status'      => post($conn, 'status')
    ];

    if ($action == 'add') {
        $data['id_kembali'] = post($conn, 'id_kembali');
        db_insert($conn, 'tbl_bayar', $data);
        redirect('index.php?page=bayar&msg=added');
    }

    if ($action == 'edit') {
        $id = post($conn, 'id_bayar');
        db_update($conn, 'tbl_bayar', $data, "id_bayar='$id'");
        redirect('index.php?page=bayar&msg=updated');
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = get($conn, 'delete');
    db_delete($conn, 'tbl_bayar', "id_bayar='$id'");
    redirect('index.php?page=bayar&msg=deleted');
}

// Pagination & Data
$page_num = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$paging = paginate($conn, 'tbl_bayar', $limit, $page_num);
$total_rows = $paging['total'];
$total_pages = $paging['pages'];

$result = db_query($conn, "SELECT b.*, k.id_transaksi, t.nopol, m.nama, mb.brand, mb.type 
    FROM tbl_bayar b 
    LEFT JOIN tbl_kembali k ON b.id_kembali = k.id_kembali 
    LEFT JOIN tbl_transaksi t ON k.id_transaksi = t.id_transaksi 
    LEFT JOIN tbl_member m ON t.nik = m.nik 
    LEFT JOIN tbl_mobil mb ON t.nopol = mb.nopol 
    ORDER BY b.id_bayar DESC 
    LIMIT {$paging['offset']}, $limit");

// Dropdown - All Kembali (for payment)
$kembalians = db_query($conn, "SELECT k.*, t.id_transaksi, t.nopol, t.total, m.nama, mb.brand, mb.type 
    FROM tbl_kembali k 
    LEFT JOIN tbl_transaksi t ON k.id_transaksi = t.id_transaksi 
    LEFT JOIN tbl_member m ON t.nik = m.nik 
    LEFT JOIN tbl_mobil mb ON t.nopol = mb.nopol 
    ORDER BY k.id_kembali DESC");

// Get edit data
$edit_data = isset($_GET['edit'])
    ? db_get_row($conn, "SELECT * FROM tbl_bayar WHERE id_bayar='" . get($conn, 'edit') . "'")
    : null;

// Stats
$total_pembayaran = db_get_row($conn, "SELECT COALESCE(SUM(total_bayar), 0) as total FROM tbl_bayar")['total'];
$bayar_lunas = db_get_row($conn, "SELECT COUNT(*) as total FROM tbl_bayar WHERE status = 'lunas'")['total'];
$bayar_belum = db_get_row($conn, "SELECT COUNT(*) as total FROM tbl_bayar WHERE status = 'belum lunas'")['total'];
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header-modern mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="d-flex align-items-center gap-3">
                    <div class="page-icon">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 fw-bold">Data Pembayaran</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="index.php?page=dashboard">Dashboard</a></li>
                                <li class="breadcrumb-item active">Pembayaran</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <button type="button" class="btn btn-primary btn-lg px-4" data-bs-toggle="modal" data-bs-target="#modalBayar">
                    <i class="bi bi-plus-circle me-2"></i>Catat Pembayaran
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
                    <span>Total Pembayaran</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="mini-stat-card">
                <div class="mini-stat-icon bg-success-gradient">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="mini-stat-content">
                    <h3><?= $bayar_lunas ?></h3>
                    <span>Lunas</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="mini-stat-card">
                <div class="mini-stat-icon bg-warning-gradient">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div class="mini-stat-content">
                    <h3><?= $bayar_belum ?></h3>
                    <span>Belum Lunas</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="mini-stat-card">
                <div class="mini-stat-icon bg-warning-gradient">
                    <i class="bi bi-currency-dollar"></i>
                </div>
                <div class="mini-stat-content">
                    <h3>Rp <?= number_format($total_pembayaran / 1000000, 1) ?>M</h3>
                    <span>Total Pemasukan</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card card-modern">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-table fs-5"></i>
                <h5 class="mb-0">Daftar Pembayaran</h5>
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
                            <th><i class="bi bi-calendar"></i> Tgl Bayar</th>
                            <th><i class="bi bi-cash"></i> Total Bayar</th>
                            <th><i class="bi bi-check-circle"></i> Status</th>
                            <th class="text-center"><i class="bi bi-gear"></i> Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><span class="badge-modern badge-primary">#<?= $row['id_bayar'] ?></span></td>
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
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-calendar-check text-success me-2"></i>
                                            <span><?= date('d/m/Y', strtotime($row['tgl_bayar'])) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge-modern badge-success">
                                            <i class="bi bi-cash me-1"></i>
                                            Rp <?= number_format($row['total_bayar'], 0, ',', '.') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($row['status'] == 'lunas'): ?>
                                            <span class="badge-modern badge-success">
                                                <i class="bi bi-check-circle me-1"></i>Lunas
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-modern badge-warning">
                                                <i class="bi bi-hourglass-split me-1"></i>Belum Lunas
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="index.php?page=bayar&edit=<?= $row['id_bayar'] ?>"
                                            class="btn-action btn-edit"
                                            data-bs-toggle="tooltip"
                                            title="Edit">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>
                                        <a href="index.php?page=bayar&delete=<?= $row['id_bayar'] ?>"
                                            class="btn-action btn-delete"
                                            data-bs-toggle="tooltip"
                                            title="Hapus"
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
                                    <p class="mb-0">Tidak ada data pembayaran</p>
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
                                <a class="page-link" href="index.php?page=bayar&p=<?= $page_num - 1 ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $i == $page_num ? 'active' : '' ?>">
                                    <a class="page-link" href="index.php?page=bayar&p=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page_num >= $total_pages ? 'disabled' : '' ?>">
                                <a class="page-link" href="index.php?page=bayar&p=<?= $page_num + 1 ?>">
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
<div class="modal fade" id="modalBayar" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-modern">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-icon">
                        <i class="bi bi-<?= $edit_data ? 'pencil-square' : 'wallet2' ?>"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0"><?= $edit_data ? 'Edit Pembayaran' : 'Catat Pembayaran' ?></h5>
                        <small class="text-muted"><?= $edit_data ? 'Ubah data pembayaran' : 'Catat pembayaran baru' ?></small>
                    </div>
                </div>
                <a href="index.php?page=bayar" class="btn-close btn-close-white"></a>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="<?= $edit_data ? 'edit' : 'add' ?>">
                    <?php if ($edit_data): ?>
                        <input type="hidden" name="id_bayar" value="<?= $edit_data['id_bayar'] ?>">
                    <?php endif; ?>

                    <div class="row g-3">
                        <?php if (!$edit_data): ?>
                            <div class="col-12">
                                <label class="form-label"><i class="bi bi-receipt me-1"></i>Data Pengembalian</label>
                                <select name="id_kembali" class="form-select form-select-lg" required>
                                    <option value="">-- Pilih Pengembalian --</option>
                                    <?php while ($k = mysqli_fetch_assoc($kembalians)): ?>
                                        <option value="<?= $k['id_kembali'] ?>">
                                            #<?= $k['id_kembali'] ?> - <?= $k['nama'] ?> | <?= $k['brand'] ?> <?= $k['type'] ?> (<?= $k['nopol'] ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="col-12">
                            <label class="form-label"><i class="bi bi-calendar-check me-1"></i>Tanggal Pembayaran</label>
                            <input type="date" name="tgl_bayar" class="form-control form-control-lg" required
                                value="<?= $edit_data ? $edit_data['tgl_bayar'] : date('Y-m-d') ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label"><i class="bi bi-cash me-1"></i>Total Bayar</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="total_bayar" class="form-control" required min="1"
                                    placeholder="0"
                                    value="<?= $edit_data ? $edit_data['total_bayar'] : '' ?>">
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label"><i class="bi bi-check-circle me-1"></i>Status Pembayaran</label>
                            <select name="status" class="form-select form-select-lg" required>
                                <option value="">-- Pilih Status --</option>
                                <option value="lunas" <?= $edit_data && $edit_data['status'] == 'lunas' ? 'selected' : '' ?>>
                                    ✅ Lunas
                                </option>
                                <option value="belum lunas" <?= $edit_data && $edit_data['status'] == 'belum lunas' ? 'selected' : '' ?>>
                                    ⏳ Belum Lunas
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="index.php?page=bayar" class="btn btn-secondary btn-lg px-4">
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
            new bootstrap.Modal(document.getElementById('modalBayar')).show();
        });
    </script>
<?php endif; ?>