<?php
// Login Page

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    // Check in tbl_user (admin/petugas)
    $query = "SELECT * FROM tbl_user WHERE user = '$username'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        if (password_verify($password, $user['pass'])) {
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['username'] = $user['user'];
            $_SESSION['user_level'] = $user['lvl'];
            $_SESSION['user_type'] = 'admin';

            echo "<script>window.location='index.php?page=dashboard';</script>";
            exit;
        } else {
            $error = 'Password salah!';
        }
    } else {
        // Check in tbl_member
        $query = "SELECT * FROM tbl_member WHERE user = '$username'";
        $result = mysqli_query($conn, $query);

        if (mysqli_num_rows($result) > 0) {
            $member = mysqli_fetch_assoc($result);
            if (password_verify($password, $member['pass'])) {
                $_SESSION['user_id'] = $member['nik'];
                $_SESSION['username'] = $member['user'];
                $_SESSION['user_level'] = 'member';
                $_SESSION['user_type'] = 'member';
                $_SESSION['nama'] = $member['nama'];

                echo "<script>window.location='index.php?page=dashboard';</script>";
                exit;
            } else {
                $error = 'Password salah!';
            }
        } else {
            $error = 'Username tidak ditemukan!';
        }
    }
}
?>

<div class="auth-wrapper">
    <!-- Theme Switcher for Auth Pages -->
    <button class="theme-switcher auth-theme-switcher" id="themeSwitcherAuth" onclick="toggleTheme()" title="Toggle Dark/Light Mode">
        <i class="bi bi-moon-stars-fill" id="themeIconAuth"></i>
    </button>

    <div class="auth-card animate__animated animate__fadeInUp">
        <div class="auth-logo">
            <i class="bi bi-car-front-fill"></i>
            <h2>Rental JDM</h2>
            <p class="text-muted">Masuk ke akun Anda</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i><?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'registered'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>Registrasi berhasil! Silakan login.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-4">
                <label class="form-label fw-semibold">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                </div>
            </div>

            <div class="d-grid mb-4">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Login
                </button>
            </div>
        </form>

        <div class="text-center">
            <p class="text-muted mb-0">Belum punya akun?
                <a href="index.php?page=register" class="text-decoration-none fw-semibold" style="color: var(--primary-color);">
                    Daftar Sekarang
                </a>
            </p>
        </div>

        <hr class="my-4">

        <div class="text-center">
            <small class="text-muted">
                <i class="bi bi-shield-check me-1"></i>
                Rental Mobil JDM Terpercaya
            </small>
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