<?php
// Register Page

$error = '';
$success = '';
$register_type = isset($_POST['register_type']) ? $_POST['register_type'] : 'member';
$active_tab = isset($_GET['type']) ? $_GET['type'] : 'member';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $register_type = $_POST['register_type'];
    $active_tab = $register_type;

    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validasi umum
    if ($password !== $confirm_password) {
        $error = 'Password dan konfirmasi password tidak sama!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } else {
        // Check if username exists in both tables
        $check_user1 = mysqli_query($conn, "SELECT user FROM tbl_member WHERE user = '$username'");
        $check_user2 = mysqli_query($conn, "SELECT user FROM tbl_user WHERE user = '$username'");

        if (mysqli_num_rows($check_user1) > 0 || mysqli_num_rows($check_user2) > 0) {
            $error = 'Username sudah digunakan!';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            if ($register_type == 'member') {
                // Register Member
                $nik = mysqli_real_escape_string($conn, $_POST['nik']);
                $nama = mysqli_real_escape_string($conn, $_POST['nama']);
                $jk = mysqli_real_escape_string($conn, $_POST['jk']);
                $telp = mysqli_real_escape_string($conn, $_POST['telp']);
                $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);

                // Check if NIK exists
                $check_nik = mysqli_query($conn, "SELECT nik FROM tbl_member WHERE nik = '$nik'");
                if (mysqli_num_rows($check_nik) > 0) {
                    $error = 'NIK sudah terdaftar!';
                } else {
                    $query = "INSERT INTO tbl_member (nik, nama, jk, telp, alamat, user, pass) 
                              VALUES ('$nik', '$nama', '$jk', '$telp', '$alamat', '$username', '$hashed_password')";

                    if (mysqli_query($conn, $query)) {
                        echo "<script>window.location='index.php?page=login&msg=registered';</script>";
                        exit;
                    } else {
                        $error = 'Terjadi kesalahan! Silakan coba lagi.';
                    }
                }
            } else {
                // Register User (Admin/Petugas)
                $level = mysqli_real_escape_string($conn, $_POST['level']);

                $query = "INSERT INTO tbl_user (user, pass, lvl) 
                          VALUES ('$username', '$hashed_password', '$level')";

                if (mysqli_query($conn, $query)) {
                    echo "<script>window.location='index.php?page=login&msg=registered';</script>";
                    exit;
                } else {
                    $error = 'Terjadi kesalahan! Silakan coba lagi.';
                }
            }
        }
    }
}
?>

<div class="auth-wrapper">
    <!-- Theme Switcher for Auth Pages -->
    <button class="theme-switcher auth-theme-switcher" id="themeSwitcherAuth" onclick="toggleTheme()" title="Toggle Dark/Light Mode">
        <i class="bi bi-moon-stars-fill" id="themeIconAuth"></i>
    </button>

    <div class="auth-card animate__animated animate__fadeInUp" style="max-width: 550px;">
        <div class="auth-logo">
            <i class="bi bi-person-plus-fill"></i>
            <h2>Daftar Akun</h2>
            <p class="text-muted">Bergabung dengan RJDM</p>
        </div>

        <!-- Tab Navigation -->
        <ul class="nav nav-pills nav-justified mb-4" id="registerTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $active_tab == 'member' ? 'active' : '' ?>" id="member-tab" data-bs-toggle="pill"
                    data-bs-target="#member-form" type="button" role="tab">
                    <i class="bi bi-people me-2"></i>Member
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $active_tab == 'user' ? 'active' : '' ?>" id="user-tab" data-bs-toggle="pill"
                    data-bs-target="#user-form" type="button" role="tab">
                    <i class="bi bi-person-gear me-2"></i>User
                </button>
            </li>
        </ul>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i><?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Tab Content -->
        <div class="tab-content" id="registerTabContent">
            <!-- Member Registration Form -->
            <div class="tab-pane fade <?= $active_tab == 'member' ? 'show active' : '' ?>" id="member-form" role="tabpanel">
                <form method="POST">
                    <input type="hidden" name="register_type" value="member">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">NIK</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-card-text"></i></span>
                                <input type="number" name="nik" class="form-control" placeholder="Masukkan NIK" required
                                    value="<?= isset($_POST['nik']) ? $_POST['nik'] : '' ?>">
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Nama Lengkap</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" name="nama" class="form-control" placeholder="Masukkan nama" required
                                    value="<?= isset($_POST['nama']) ? $_POST['nama'] : '' ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Jenis Kelamin</label>
                            <select name="jk" class="form-select" required>
                                <option value="">-- Pilih --</option>
                                <option value="L" <?= (isset($_POST['jk']) && $_POST['jk'] == 'L') ? 'selected' : '' ?>>Laki-laki</option>
                                <option value="P" <?= (isset($_POST['jk']) && $_POST['jk'] == 'P') ? 'selected' : '' ?>>Perempuan</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">No. Telepon</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-phone"></i></span>
                                <input type="text" name="telp" class="form-control" placeholder="08xxxxxxxxxx" required
                                    value="<?= isset($_POST['telp']) ? $_POST['telp'] : '' ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Alamat</label>
                        <textarea name="alamat" class="form-control" rows="2" placeholder="Masukkan alamat lengkap" required><?= isset($_POST['alamat']) ? $_POST['alamat'] : '' ?></textarea>
                    </div>

                    <hr class="my-4">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-at"></i></span>
                            <input type="text" name="username" class="form-control" placeholder="Pilih username" required
                                value="<?= (isset($_POST['username']) && $register_type == 'member') ? $_POST['username'] : '' ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password" class="form-control" placeholder="Min. 6 karakter" required>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Konfirmasi Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" name="confirm_password" class="form-control" placeholder="Ulangi password" required>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid mb-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-person-plus me-2"></i>Daftar sebagai Member
                        </button>
                    </div>
                </form>
            </div>

            <!-- User (Admin/Petugas) Registration Form -->
            <div class="tab-pane fade <?= $active_tab == 'user' ? 'show active' : '' ?>" id="user-form" role="tabpanel">
                <form method="POST">
                    <input type="hidden" name="register_type" value="user">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Level User</label>
                        <select name="level" class="form-select" required>
                            <option value="">-- Pilih Level --</option>
                            <option value="admin" <?= (isset($_POST['level']) && $_POST['level'] == 'admin') ? 'selected' : '' ?>>Admin</option>
                            <option value="petugas" <?= (isset($_POST['level']) && $_POST['level'] == 'petugas') ? 'selected' : '' ?>>Petugas</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-at"></i></span>
                            <input type="text" name="username" class="form-control" placeholder="Pilih username" required
                                value="<?= (isset($_POST['username']) && $register_type == 'user') ? $_POST['username'] : '' ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password" class="form-control" placeholder="Min. 6 karakter" required>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Konfirmasi Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" name="confirm_password" class="form-control" placeholder="Ulangi password" required>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid mb-4">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-person-gear me-2"></i>Daftar sebagai User
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="text-center">
            <p class="text-muted mb-0">Sudah punya akun?
                <a href="index.php?page=login" class="text-decoration-none fw-semibold" style="color: var(--primary-color);">
                    Login di sini
                </a>
            </p>
        </div>
    </div>
</div>

<script>
    // Theme Switcher for Auth Pages
    function toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateAuthThemeIcon(newTheme);
    }

    function updateAuthThemeIcon(theme) {
        const themeIcon = document.getElementById('themeIconAuth');
        if (themeIcon) {
            if (theme === 'light') {
                themeIcon.className = 'bi bi-sun-fill';
            } else {
                themeIcon.className = 'bi bi-moon-stars-fill';
            }
        }
    }

    // Initialize icon on load
    document.addEventListener('DOMContentLoaded', function() {
        const savedTheme = localStorage.getItem('theme') || 'dark';
        updateAuthThemeIcon(savedTheme);
    });
</script>