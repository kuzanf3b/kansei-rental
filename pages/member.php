<?php
// Member Page - CRUD Operations

// Handle POST (Add/Edit)
if (is_post()) {
    $action = get_action();

    $data = [
        'nik'    => post($conn, 'nik'),
        'nama'   => post($conn, 'nama'),
        'jk'     => post($conn, 'jk'),
        'telp'   => post($conn, 'telp'),
        'alamat' => post($conn, 'alamat'),
        'user'   => post($conn, 'user')
    ];

    if ($action == 'add') {
        $data['pass'] = password_hash($_POST['pass'], PASSWORD_DEFAULT);
        db_insert($conn, 'tbl_member', $data);
        redirect('index.php?page=member&msg=added');
    }

    if ($action == 'edit') {
        if (!empty($_POST['pass'])) {
            $data['pass'] = password_hash($_POST['pass'], PASSWORD_DEFAULT);
        }
        $old_nik = post($conn, 'old_nik');
        db_update($conn, 'tbl_member', $data, "nik='$old_nik'");
        redirect('index.php?page=member&msg=updated');
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $nik = get($conn, 'delete');
    $result = db_delete($conn, 'tbl_member', "nik='$nik'", [
        'table' => 'tbl_transaksi',
        'column' => 'nik',
        'value' => $nik
    ]);

    if ($result['success']) {
        redirect('index.php?page=member&msg=deleted');
    } else {
        redirect("index.php?page=member&msg=error_fk&detail={$result['reason']}");
    }
}

// Pagination & Data
$page_num = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$paging = paginate($conn, 'tbl_member', $limit, $page_num);
$total_rows = $paging['total'];
$total_pages = $paging['pages'];

$result = db_query($conn, "SELECT * FROM tbl_member ORDER BY nama LIMIT {$paging['offset']}, $limit");

// Get edit data
$edit_data = isset($_GET['edit'])
    ? db_get_row($conn, "SELECT * FROM tbl_member WHERE nik='" . get($conn, 'edit') . "'")
    : null;

// Stats
$laki = db_get_row($conn, "SELECT COUNT(*) as total FROM tbl_member WHERE jk='L'")['total'];
$perempuan = $total_rows - $laki;
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header-modern mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="d-flex align-items-center gap-3">
                    <div class="page-icon">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 fw-bold">Data Member</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="index.php?page=dashboard">Dashboard</a></li>
                                <li class="breadcrumb-item active">Data Member</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <button type="button" class="btn btn-primary btn-lg px-4" data-bs-toggle="modal" data-bs-target="#modalMember">
                    <i class="bi bi-person-plus me-2"></i>Tambah Member
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="mini-stat-card">
                <div class="mini-stat-icon bg-primary-gradient">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="mini-stat-content">
                    <h3><?= $total_rows ?></h3>
                    <span>Total Member</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="mini-stat-card">
                <div class="mini-stat-icon bg-info-gradient">
                    <i class="bi bi-gender-male"></i>
                </div>
                <div class="mini-stat-content">
                    <h3><?= $laki ?></h3>
                    <span>Laki-laki</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="mini-stat-card">
                <div class="mini-stat-icon bg-warning-gradient">
                    <i class="bi bi-gender-female"></i>
                </div>
                <div class="mini-stat-content">
                    <h3><?= $perempuan ?></h3>
                    <span>Perempuan</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="mini-stat-card">
                <div class="mini-stat-icon bg-success-gradient">
                    <i class="bi bi-person-check-fill"></i>
                </div>
                <div class="mini-stat-content">
                    <h3><?= $total_rows ?></h3>
                    <span>Aktif</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card card-modern">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-table fs-5"></i>
                <h5 class="mb-0">Daftar Member</h5>
            </div>
            <span class="badge bg-primary rounded-pill px-3 py-2"><?= $total_rows ?> Data</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-modern mb-0">
                    <thead>
                        <tr>
                            <th><i class="bi bi-person-vcard"></i> Member</th>
                            <th><i class="bi bi-gender-ambiguous"></i> JK</th>
                            <th><i class="bi bi-telephone"></i> Telepon</th>
                            <th><i class="bi bi-person"></i> Username</th>
                            <th class="text-center"><i class="bi bi-gear"></i> Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td>
                                        <div class="table-info-cell">
                                            <div class="table-avatar">
                                                <?= strtoupper(substr($row['nama'], 0, 1)) ?>
                                            </div>
                                            <div class="info-text">
                                                <h6><?= $row['nama'] ?></h6>
                                                <small>NIK: <?= $row['nik'] ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge-modern <?= $row['jk'] == 'L' ? 'badge-info' : 'badge-warning' ?>">
                                            <i class="bi bi-<?= $row['jk'] == 'L' ? 'gender-male' : 'gender-female' ?> me-1"></i>
                                            <?= $row['jk'] == 'L' ? 'Laki-laki' : 'Perempuan' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <i class="bi bi-phone text-muted me-1"></i>
                                        <?= $row['telp'] ?>
                                    </td>
                                    <td>
                                        <span class="badge-modern badge-primary">
                                            <i class="bi bi-at me-1"></i><?= $row['user'] ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="index.php?page=member&edit=<?= $row['nik'] ?>"
                                            class="btn-action btn-edit"
                                            data-bs-toggle="tooltip"
                                            title="Edit Member">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>
                                        <a href="index.php?page=member&delete=<?= $row['nik'] ?>"
                                            class="btn-action btn-delete"
                                            data-bs-toggle="tooltip"
                                            title="Hapus Member"
                                            onclick="return confirm('Yakin hapus data ini?')">
                                            <i class="bi bi-trash-fill"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="no-data-cell">
                                    <i class="bi bi-inbox d-block"></i>
                                    <p class="mb-0">Tidak ada data member</p>
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
                                <a class="page-link" href="index.php?page=member&p=<?= $page_num - 1 ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $i == $page_num ? 'active' : '' ?>">
                                    <a class="page-link" href="index.php?page=member&p=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page_num >= $total_pages ? 'disabled' : '' ?>">
                                <a class="page-link" href="index.php?page=member&p=<?= $page_num + 1 ?>">
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
<div class="modal fade" id="modalMember" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content modal-modern">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-icon">
                        <i class="bi bi-<?= $edit_data ? 'pencil-square' : 'person-plus' ?>"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0"><?= $edit_data ? 'Edit Member' : 'Tambah Member Baru' ?></h5>
                        <small class="text-muted"><?= $edit_data ? 'Ubah data member yang sudah ada' : 'Masukkan data member baru' ?></small>
                    </div>
                </div>
                <a href="index.php?page=member" class="btn-close btn-close-white"></a>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="<?= $edit_data ? 'edit' : 'add' ?>">
                    <?php if ($edit_data): ?>
                        <input type="hidden" name="old_nik" value="<?= $edit_data['nik'] ?>">
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-card-text me-1"></i>NIK</label>
                            <input type="number" name="nik" class="form-control form-control-lg" required
                                placeholder="Masukkan NIK"
                                value="<?= $edit_data ? $edit_data['nik'] : '' ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-person me-1"></i>Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control form-control-lg" required
                                placeholder="Masukkan nama lengkap"
                                value="<?= $edit_data ? $edit_data['nama'] : '' ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-gender-ambiguous me-1"></i>Jenis Kelamin</label>
                            <select name="jk" class="form-select form-select-lg" required>
                                <option value="">-- Pilih --</option>
                                <option value="L" <?= ($edit_data && $edit_data['jk'] == 'L') ? 'selected' : '' ?>>♂ Laki-laki</option>
                                <option value="P" <?= ($edit_data && $edit_data['jk'] == 'P') ? 'selected' : '' ?>>♀ Perempuan</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-telephone me-1"></i>No. Telepon</label>
                            <input type="text" name="telp" class="form-control form-control-lg" required
                                placeholder="08xxxxxxxxxx"
                                value="<?= $edit_data ? $edit_data['telp'] : '' ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label"><i class="bi bi-geo-alt me-1"></i>Alamat</label>
                            <textarea name="alamat" class="form-control" rows="3" required
                                placeholder="Masukkan alamat lengkap"><?= $edit_data ? $edit_data['alamat'] : '' ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-at me-1"></i>Username</label>
                            <input type="text" name="user" class="form-control form-control-lg" required
                                placeholder="Pilih username"
                                value="<?= $edit_data ? $edit_data['user'] : '' ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-lock me-1"></i>Password <?= $edit_data ? '(Kosongkan jika tidak diubah)' : '' ?></label>
                            <input type="password" name="pass" class="form-control form-control-lg"
                                <?= $edit_data ? '' : 'required' ?>
                                placeholder="<?= $edit_data ? '••••••••' : 'Masukkan password' ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="index.php?page=member" class="btn btn-secondary btn-lg px-4">
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
            new bootstrap.Modal(document.getElementById('modalMember')).show();
        });
    </script>
<?php endif; ?>