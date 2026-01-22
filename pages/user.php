<?php
// User Page - CRUD Operations

// Handle POST (Add/Edit)
if (is_post()) {
    $action = get_action();

    $data = [
        'user' => post($conn, 'user'),
        'lvl'  => post($conn, 'lvl')
    ];

    if ($action == 'add') {
        $data['pass'] = password_hash(post($conn, 'pass'), PASSWORD_DEFAULT);
        db_insert($conn, 'tbl_user', $data);
        redirect('index.php?page=user&msg=added');
    }

    if ($action == 'edit') {
        $id = post($conn, 'id_user');
        $password = $_POST['pass'] ?? '';
        if (!empty($password)) {
            $data['pass'] = password_hash($password, PASSWORD_DEFAULT);
        }
        db_update($conn, 'tbl_user', $data, "id_user='$id'");
        redirect('index.php?page=user&msg=updated');
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = get($conn, 'delete');
    db_delete($conn, 'tbl_user', "id_user='$id'");
    redirect('index.php?page=user&msg=deleted');
}

// Pagination & Data
$page_num = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$paging = paginate($conn, 'tbl_user', $limit, $page_num);
$total_rows = $paging['total'];
$total_pages = $paging['pages'];

$result = db_query($conn, "SELECT * FROM tbl_user ORDER BY id_user DESC LIMIT {$paging['offset']}, $limit");

// Get edit data
$edit_data = isset($_GET['edit'])
    ? db_get_row($conn, "SELECT * FROM tbl_user WHERE id_user='" . get($conn, 'edit') . "'")
    : null;

// Stats
$total_admin = db_get_row($conn, "SELECT COUNT(*) as total FROM tbl_user WHERE lvl = 'admin'")['total'];
$total_petugas = db_get_row($conn, "SELECT COUNT(*) as total FROM tbl_user WHERE lvl = 'petugas'")['total'];
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
                        <h2 class="mb-0 fw-bold">Manajemen User</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="index.php?page=dashboard">Dashboard</a></li>
                                <li class="breadcrumb-item active">User</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <button type="button" class="btn btn-primary btn-lg px-4" data-bs-toggle="modal" data-bs-target="#modalUser">
                    <i class="bi bi-plus-circle me-2"></i>Tambah User
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4">
            <div class="mini-stat-card">
                <div class="mini-stat-icon bg-primary-gradient">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="mini-stat-content">
                    <h3><?= $total_rows ?></h3>
                    <span>Total User</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="mini-stat-card">
                <div class="mini-stat-icon bg-danger-gradient">
                    <i class="bi bi-shield-fill-check"></i>
                </div>
                <div class="mini-stat-content">
                    <h3><?= $total_admin ?></h3>
                    <span>Admin</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="mini-stat-card">
                <div class="mini-stat-icon bg-info-gradient">
                    <i class="bi bi-person-badge-fill"></i>
                </div>
                <div class="mini-stat-content">
                    <h3><?= $total_petugas ?></h3>
                    <span>Petugas</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card card-modern">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-table fs-5"></i>
                <h5 class="mb-0">Daftar User</h5>
            </div>
            <span class="badge bg-primary rounded-pill px-3 py-2"><?= $total_rows ?> Data</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-modern mb-0">
                    <thead>
                        <tr>
                            <th><i class="bi bi-hash"></i> ID</th>
                            <th><i class="bi bi-person-badge"></i> Username</th>
                            <th><i class="bi bi-shield"></i> Level</th>
                            <th class="text-center"><i class="bi bi-gear"></i> Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><span class="badge-modern badge-primary">#<?= $row['id_user'] ?></span></td>
                                    <td>
                                        <div class="table-info-cell">
                                            <div class="table-avatar" style="background: linear-gradient(135deg, <?= $row['lvl'] == 'admin' ? '#ef4444, #dc2626' : '#3b82f6, #2563eb' ?>);">
                                                <?= strtoupper(substr($row['user'], 0, 1)) ?>
                                            </div>
                                            <div class="info-text">
                                                <h6><?= $row['user'] ?></h6>
                                                <small>@<?= $row['user'] ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($row['lvl'] == 'admin'): ?>
                                            <span class="badge-modern badge-danger">
                                                <i class="bi bi-shield-fill-check me-1"></i>Admin
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-modern badge-info">
                                                <i class="bi bi-person-badge-fill me-1"></i>Petugas
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="index.php?page=user&edit=<?= $row['id_user'] ?>"
                                            class="btn-action btn-edit"
                                            data-bs-toggle="tooltip"
                                            title="Edit">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>
                                        <?php if ($row['id_user'] != $_SESSION['user']['id_user']): ?>
                                            <a href="index.php?page=user&delete=<?= $row['id_user'] ?>"
                                                class="btn-action btn-delete"
                                                data-bs-toggle="tooltip"
                                                title="Hapus"
                                                onclick="return confirm('Yakin hapus user ini?')">
                                                <i class="bi bi-trash-fill"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="btn-action" style="opacity: 0.3; cursor: not-allowed;"
                                                data-bs-toggle="tooltip"
                                                title="Tidak bisa hapus diri sendiri">
                                                <i class="bi bi-trash-fill"></i>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="no-data-cell">
                                    <i class="bi bi-inbox d-block"></i>
                                    <p class="mb-0">Tidak ada data user</p>
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
                                <a class="page-link" href="index.php?page=user&p=<?= $page_num - 1 ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $i == $page_num ? 'active' : '' ?>">
                                    <a class="page-link" href="index.php?page=user&p=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page_num >= $total_pages ? 'disabled' : '' ?>">
                                <a class="page-link" href="index.php?page=user&p=<?= $page_num + 1 ?>">
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
<div class="modal fade" id="modalUser" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-modern">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-icon">
                        <i class="bi bi-<?= $edit_data ? 'pencil-square' : 'person-plus-fill' ?>"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0"><?= $edit_data ? 'Edit User' : 'Tambah User Baru' ?></h5>
                        <small class="text-muted"><?= $edit_data ? 'Ubah data user' : 'Buat akun user baru' ?></small>
                    </div>
                </div>
                <a href="index.php?page=user" class="btn-close btn-close-white"></a>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="<?= $edit_data ? 'edit' : 'add' ?>">
                    <?php if ($edit_data): ?>
                        <input type="hidden" name="id_user" value="<?= $edit_data['id_user'] ?>">
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label"><i class="bi bi-at me-1"></i>Username</label>
                            <input type="text" name="user" class="form-control form-control-lg" required
                                placeholder="Masukkan username"
                                value="<?= $edit_data ? $edit_data['user'] : '' ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label">
                                <i class="bi bi-key me-1"></i>Password
                                <?php if ($edit_data): ?>
                                    <small class="text-muted">(kosongkan jika tidak ingin mengubah)</small>
                                <?php endif; ?>
                            </label>
                            <div class="input-group input-group-lg">
                                <input type="password" name="pass" id="passwordInput" class="form-control"
                                    placeholder="Masukkan password" <?= $edit_data ? '' : 'required' ?>>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="bi bi-eye" id="eyeIcon"></i>
                                </button>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label"><i class="bi bi-shield me-1"></i>Level</label>
                            <select name="lvl" class="form-select form-select-lg" required>
                                <option value="">-- Pilih Level --</option>
                                <option value="admin" <?= $edit_data && $edit_data['lvl'] == 'admin' ? 'selected' : '' ?>>
                                    🛡️ Admin
                                </option>
                                <option value="petugas" <?= $edit_data && $edit_data['lvl'] == 'petugas' ? 'selected' : '' ?>>
                                    👤 Petugas
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="index.php?page=user" class="btn btn-secondary btn-lg px-4">
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

<script>
    // Toggle Password Visibility
    document.getElementById('togglePassword').addEventListener('click', function() {
        const passwordInput = document.getElementById('passwordInput');
        const eyeIcon = document.getElementById('eyeIcon');
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeIcon.classList.remove('bi-eye');
            eyeIcon.classList.add('bi-eye-slash');
        } else {
            passwordInput.type = 'password';
            eyeIcon.classList.remove('bi-eye-slash');
            eyeIcon.classList.add('bi-eye');
        }
    });
</script>

<?php if ($edit_data): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            new bootstrap.Modal(document.getElementById('modalUser')).show();
        });
    </script>
<?php endif; ?>