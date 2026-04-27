<?php
session_start();

// KONEKSI DATABASE
$host = "localhost";
$user = "root";
$pass = "";
$db   = "rental_jdm";

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// HELPER FUNCTIONS


// Escape input untuk keamanan SQL

function esc($conn, $value)
{
    return mysqli_real_escape_string($conn, $value);
}


// Ambil nilai POST dengan escape

function post($conn, $key, $default = '')
{
    return isset($_POST[$key]) ? esc($conn, $_POST[$key]) : $default;
}


// Ambil nilai GET dengan escape  

function get($conn, $key, $default = '')
{
    return isset($_GET[$key]) ? esc($conn, $_GET[$key]) : $default;
}


// Redirect menggunakan JavaScript

function redirect($url)
{
    echo "<script>window.location='$url';</script>";
    exit;
}


// Jalankan query INSERT

function db_insert($conn, $table, $data)
{
    $columns = implode(', ', array_keys($data));
    $values = "'" . implode("', '", array_values($data)) . "'";
    $query = "INSERT INTO $table ($columns) VALUES ($values)";
    return mysqli_query($conn, $query);
}


// Jalankan query UPDATE

function db_update($conn, $table, $data, $where)
{
    $set = [];
    foreach ($data as $key => $value) {
        $set[] = "$key='$value'";
    }
    $query = "UPDATE $table SET " . implode(', ', $set) . " WHERE $where";
    return mysqli_query($conn, $query);
}


// Jalankan query DELETE dengan cek FK

function db_delete($conn, $table, $where, $fk_check = null)
{
    // Cek foreign key jika ada
    if ($fk_check) {
        $check = mysqli_query($conn, "SELECT COUNT(*) as total FROM {$fk_check['table']} WHERE {$fk_check['column']}='{$fk_check['value']}'");
        if (mysqli_fetch_assoc($check)['total'] > 0) {
            return ['success' => false, 'reason' => $fk_check['table']];
        }
    }
    mysqli_query($conn, "DELETE FROM $table WHERE $where");
    return ['success' => true];
}


// Ambil satu baris data

function db_get_row($conn, $query)
{
    $result = mysqli_query($conn, $query);
    return $result ? mysqli_fetch_assoc($result) : null;
}


// Ambil semua data dengan query

function db_query($conn, $query)
{
    return mysqli_query($conn, $query);
}


// Hitung total rows

function db_count($conn, $table)
{
    return db_get_row($conn, "SELECT COUNT(*) as total FROM $table")['total'];
}


// Setup pagination

function paginate($conn, $table, $limit, $page_num)
{
    $total = db_count($conn, $table);
    $offset = ($page_num - 1) * $limit;
    return [
        'total' => $total,
        'pages' => ceil($total / $limit),
        'offset' => $offset,
        'current' => $page_num
    ];
}


// Cek apakah request POST

function is_post()
{
    return $_SERVER['REQUEST_METHOD'] == 'POST';
}


// Ambil action dari form

function get_action()
{
    return $_POST['action'] ?? '';
}


// Upload file

function upload_file($field, $target_dir)
{
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] != 0) {
        return '';
    }

    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $filename = time() . '_' . basename($_FILES[$field]['name']);
    move_uploaded_file($_FILES[$field]['tmp_name'], $target_dir . $filename);
    return $filename;
}

// PAGINATION CONFIG
$limit = 5;

// AUTH CHECK
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Pages yang tidak perlu login (public pages)
$public_pages = ['login', 'register', 'home'];

// Pages yang memerlukan login
$protected_pages = ['dashboard', 'mobil', 'member', 'transaksi', 'kembali', 'bayar', 'user'];

// Cek apakah user adalah guest (belum login)
$is_guest = !isset($_SESSION['user_id']);

// Handle logout
if ($page == 'logout') {
    session_destroy();
    echo "<script>window.location='index.php?page=home';</script>";
    exit;
}

// Jika guest mencoba akses halaman protected, redirect ke login
if ($is_guest && in_array($page, $protected_pages)) {
    echo "<script>window.location='index.php?page=login';</script>";
    exit;
}

// Jika sudah login tapi akses login/register, redirect ke dashboard
if (isset($_SESSION['user_id']) && in_array($page, ['login', 'register'])) {
    echo "<script>window.location='index.php?page=dashboard';</script>";
    exit;
}

// Jika sudah login tapi akses home, redirect ke dashboard
if (isset($_SESSION['user_id']) && $page == 'home') {
    echo "<script>window.location='index.php?page=dashboard';</script>";
    exit;
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RKansei Rental - <?= ucfirst($page) ?></title>
    <link rel="icon" type="image/svg+xml" href="./assets/car-white.svg" />

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="./assets/css/base.css">
    <link rel="stylesheet" href="./assets/css/layout.css">
    <link rel="stylesheet" href="./assets/css/auth.css">
    <link rel="stylesheet" href="./assets/css/components.css">
    <link rel="stylesheet" href="./assets/css/tables.css">
    <link rel="stylesheet" href="./assets/css/dashboard.css">
    <link rel="stylesheet" href="./assets/css/views.css">
    <link rel="stylesheet" href="./assets/css/modals.css">

    <!-- Initialize theme before page renders to prevent flash -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
</head>

<body class="<?= in_array($page, ['login', 'register']) ? '' : 'dashboard-body' ?>">

    <?php if (in_array($page, ['login', 'register'])): ?>
        <!-- Auth Pages (Login/Register) -->
        <?php include "pages/{$page}.php"; ?>
    <?php elseif ($page == 'home' && $is_guest): ?>
        <!-- Guest Home Page with Navbar -->
        <nav class="top-navbar">
            <button class="hamburger" onclick="toggleNavMenu()">
                <i class="bi bi-list"></i>
            </button>

            <a href="index.php?page=home" class="logo">
                <i class="bi bi-car-front-fill"></i>
                <span>Kansei Rental</span>
            </a>

            <div class="nav-menu" id="navMenu">
                <a class="nav-link active" href="index.php?page=home">
                    <i class="bi bi-house-fill"></i><span>Home</span>
                </a>
            </div>

            <div class="user-section">
                <button class="theme-switcher" id="themeSwitcher" onclick="toggleTheme()" title="Toggle Dark/Light Mode">
                    <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
                </button>
                <a href="index.php?page=login" class="btn-auth-nav">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <span>Login</span>
                </a>
                <a href="index.php?page=register" class="btn-auth-nav register">
                    <i class="bi bi-person-plus"></i>
                    <span>Register</span>
                </a>
            </div>
        </nav>

        <div class="main-content">
            <?php include "pages/home.php"; ?>
        </div>
    <?php else: ?>
        <?php if ($_SESSION['user_level'] != 'member'): ?>
            <!-- Admin & Petugas - Sidebar -->
            <div class="sidebar" id="sidebarMenu">
                <a href="index.php?page=dashboard" class="logo">
                    <i class="bi bi-car-front-fill"></i>
                    <span>Kansei Rental</span>
                </a>

                <!-- Navigation Menu -->
                <div class="nav-menu">
                    <a class="nav-link <?= $page == 'dashboard' ? 'active' : '' ?>" href="index.php?page=dashboard">
                        <i class="bi bi-house-fill"></i><span>Dashboard</span>
                    </a>
                    <a class="nav-link <?= $page == 'mobil' ? 'active' : '' ?>" href="index.php?page=mobil">
                        <i class="bi bi-car-front-fill"></i><span>Data Mobil</span>
                    </a>
                    <a class="nav-link <?= $page == 'member' ? 'active' : '' ?>" href="index.php?page=member">
                        <i class="bi bi-people-fill"></i><span>Data Member</span>
                    </a>
                    <a class="nav-link <?= $page == 'transaksi' ? 'active' : '' ?>" href="index.php?page=transaksi">
                        <i class="bi bi-receipt"></i><span>Data Transaksi</span>
                    </a>
                    <a class="nav-link <?= $page == 'kembali' ? 'active' : '' ?>" href="index.php?page=kembali">
                        <i class="bi bi-arrow-return-left"></i><span>Pengembalian</span>
                    </a>
                    <a class="nav-link <?= $page == 'bayar' ? 'active' : '' ?>" href="index.php?page=bayar">
                        <i class="bi bi-cash-stack"></i><span>Pembayaran</span>
                    </a>
                    <?php if ($_SESSION['user_level'] == 'admin'): ?>
                        <a class="nav-link <?= $page == 'user' ? 'active' : '' ?>" href="index.php?page=user">
                            <i class="bi bi-person-gear"></i><span>Data User</span>
                        </a>
                    <?php endif; ?>
                </div>

                <div class="user-section">
                    <div class="user-profile">
                        <div class="user-avatar" title="<?= $_SESSION['username'] ?>">
                            <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                        </div>
                        <div class="user-info">
                            <span class="username"><?= $_SESSION['nama'] ?? $_SESSION['username'] ?></span>
                            <span class="role"><span class="online-badge"></span><?= ucfirst($_SESSION['user_level']) ?></span>
                        </div>
                    </div>
                    <div class="user-actions">
                        <button class="theme-switcher" id="themeSwitcherSidebar" onclick="toggleTheme()" title="Toggle Dark/Light Mode">
                            <i class="bi bi-moon-stars-fill" id="themeIconSidebar"></i>
                        </button>
                        <a href="index.php?page=logout" class="btn-logout" title="Logout">
                            <i class="bi bi-box-arrow-left"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Hamburger Menu for Mobile (Sidebar) -->
            <button class="hamburger" onclick="toggleSidebarMenu()" style="position: fixed; top: 15px; left: 15px; z-index: 1003; background: var(--bg-card); border-radius: var(--radius-md); padding: 8px;">
                <i class="bi bi-list" style="color: var(--text-primary);"></i>
            </button>

            <!-- Main Content -->
            <div class="main-content has-sidebar">
                <?php
                // Include page content
                $allowed_pages = ['dashboard', 'mobil', 'member', 'transaksi', 'kembali', 'bayar', 'user'];

                if (in_array($page, $allowed_pages)) {
                    include "pages/{$page}.php";
                } else {
                    include "pages/dashboard.php";
                }
                ?>
            </div>

        <?php else: ?>
            <!-- Logged In User (Member) - Top Navbar -->
            <nav class="top-navbar">
                <!-- Hamburger Menu for Mobile -->
                <button class="hamburger" onclick="toggleNavMenu()">
                    <i class="bi bi-list"></i>
                </button>

                <a href="index.php?page=dashboard" class="logo">
                    <i class="bi bi-car-front-fill"></i>
                    <span>Kansei Rental</span>
                </a>

                <!-- Navigation Menu -->
                <div class="nav-menu" id="navMenu">
                    <a class="nav-link <?= $page == 'dashboard' ? 'active' : '' ?>" href="index.php?page=dashboard">
                        <i class="bi bi-house-fill"></i><span>Home</span>
                    </a>
                    <a class="nav-link <?= $page == 'mobil' ? 'active' : '' ?>" href="index.php?page=mobil">
                        <i class="bi bi-car-front-fill"></i><span>Mobil</span>
                    </a>
                    <a class="nav-link <?= $page == 'transaksi' ? 'active' : '' ?>" href="index.php?page=transaksi">
                        <i class="bi bi-receipt"></i><span>Transaksi Saya</span>
                    </a>
                </div>

                <div class="user-section">
                    <button class="theme-switcher" id="themeSwitcher" onclick="toggleTheme()" title="Toggle Dark/Light Mode">
                        <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
                    </button>
                    <div class="user-info">
                        <span class="username"><?= $_SESSION['nama'] ?? $_SESSION['username'] ?></span>
                        <span class="role"><span class="online-badge"></span><?= ucfirst($_SESSION['user_level']) ?></span>
                    </div>
                    <div class="user-avatar" title="<?= $_SESSION['username'] ?>">
                        <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                    </div>
                    <a href="index.php?page=logout" class="btn-logout" title="Logout">
                        <i class="bi bi-box-arrow-left"></i>
                    </a>
                </div>
            </nav>

            <!-- Main Content -->
            <div class="main-content">
                <?php
                // Include page content
                $allowed_pages = ['dashboard', 'mobil', 'transaksi'];

                if (in_array($page, $allowed_pages)) {
                    include "pages/{$page}.php";
                } else {
                    include "pages/dashboard.php";
                }
                ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="./assets/js/main.js"></script>
    <script src="./assets/js/modals.js"></script>

    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="liveToast" class="toast text-white" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="4000">
            <div class="toast-header text-white border-0" style="background: transparent;">
                <i class="bi me-2" id="toastIcon"></i>
                <strong class="me-auto" id="toastTitle">Notifikasi</strong>
                <small class="text-white-50">Baru saja</small>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body" id="toastMessage">
                Pesan notifikasi...
            </div>
        </div>
    </div>

    <!-- Confirm Modal Popup -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 380px;">
            <div class="modal-content confirm-modal-content">
                <button type="button" class="confirm-close-btn" data-bs-dismiss="modal" aria-label="Close">
                    <i class="bi bi-x-lg"></i>
                </button>
                <div class="confirm-modal-body">
                    <div class="confirm-icon-wrapper">
                        <div class="confirm-icon-bg"></div>
                        <div class="confirm-icon">
                            <i class="bi bi-trash3" id="confirmModalIcon"></i>
                        </div>
                    </div>
                    <h4 class="confirm-title" id="confirmModalTitle">Hapus Data?</h4>
                    <p class="confirm-message" id="confirmMessage">Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.</p>
                    <div class="confirm-actions">
                        <button type="button" class="confirm-btn confirm-btn-cancel" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg"></i>
                            <span>Batal</span>
                        </button>
                        <a href="#" class="confirm-btn confirm-btn-delete" id="confirmAction">
                            <i class="bi bi-trash3"></i>
                            <span>Ya, Hapus</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
<?php
mysqli_close($conn);
?>

