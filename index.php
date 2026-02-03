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
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Pages yang tidak perlu login
$public_pages = ['login', 'register'];

// Handle logout
if ($page == 'logout') {
    session_destroy();
    echo "<script>window.location='index.php?page=login';</script>";
    exit;
}

// Cek apakah sudah login
if (!isset($_SESSION['user_id']) && !in_array($page, $public_pages)) {
    echo "<script>window.location='index.php?page=login';</script>";
    exit;
}

// Jika sudah login tapi akses login/register, redirect ke dashboard
if (isset($_SESSION['user_id']) && in_array($page, $public_pages)) {
    echo "<script>window.location='index.php?page=dashboard';</script>";
    exit;
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RJDM - <?= ucfirst($page) ?></title>
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

    <!-- Initialize theme before page renders to prevent flash -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
</head>

<body class="<?= !in_array($page, $public_pages) ? 'dashboard-body' : '' ?>">

    <?php if (in_array($page, $public_pages)): ?>
        <!-- Public Pages (Login/Register) -->
        <?php include "pages/{$page}.php"; ?>
    <?php else: ?>
        <!-- Top Navbar -->
        <nav class="top-navbar">
            <a href="index.php?page=dashboard" class="logo">
                <i class="bi bi-car-front-fill"></i>
                <span>Rental JDM</span>
            </a>
            <div class="user-section">
                <!-- Theme Switcher -->
                <button class="theme-switcher" id="themeSwitcher" onclick="toggleTheme()" title="Toggle Dark/Light Mode">
                    <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
                </button>
                <div class="user-info">
                    <span class="username"><?= $_SESSION['nama'] ?? $_SESSION['username'] ?></span>
                    <span class="role"><span class="online-badge"></span><?= $_SESSION['username'] ?></span>
                </div>
                <div class="user-avatar">
                    <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                </div>
            </div>
        </nav>

        <!-- Hamburger Menu -->
        <button class="hamburger" onclick="toggleSidebar()">
            <i class="bi bi-list"></i>
        </button>

        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <nav class="nav flex-column">
                <a class="nav-link <?= $page == 'dashboard' ? 'active' : '' ?>" href="index.php?page=dashboard">
                    <i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span>
                </a>

                <div class="nav-divider"></div>

                <a class="nav-link <?= $page == 'mobil' ? 'active' : '' ?>" href="index.php?page=mobil">
                    <i class="bi bi-car-front-fill"></i><span>Data Mobil</span>
                </a>
                <a class="nav-link <?= $page == 'member' ? 'active' : '' ?>" href="index.php?page=member">
                    <i class="bi bi-people-fill"></i><span>Data Member</span>
                </a>

                <div class="nav-divider"></div>

                <a class="nav-link <?= $page == 'transaksi' ? 'active' : '' ?>" href="index.php?page=transaksi">
                    <i class="bi bi-cart-fill"></i><span>Transaksi</span>
                </a>
                <?php if ($_SESSION['user_level'] != 'member'): ?>
                    <a class="nav-link <?= $page == 'kembali' ? 'active' : '' ?>" href="index.php?page=kembali">
                        <i class="bi bi-arrow-return-left"></i><span>Pengembalian</span>
                    </a>
                    <a class="nav-link <?= $page == 'bayar' ? 'active' : '' ?>" href="index.php?page=bayar">
                        <i class="bi bi-cash-stack"></i><span>Pembayaran</span>
                    </a>
                <?php endif; ?>

                <?php if ($_SESSION['user_level'] == 'admin'): ?>
                    <div class="nav-divider"></div>
                    <a class="nav-link <?= $page == 'user' ? 'active' : '' ?>" href="index.php?page=user">
                        <i class="bi bi-person-gear"></i><span>User Admin</span>
                    </a>
                <?php endif; ?>
            </nav>

            <div class="logout-section">
                <a href="index.php?page=logout" class="btn-logout">
                    <i class="bi bi-box-arrow-left"></i><span>Logout</span>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
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
    <?php endif; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

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
            <div class="modal-content confirm-modal-content" style="background: var(--bg-card, #1f2335); border: 1px solid var(--border-color, #414868); border-radius: 16px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);">
                <button type="button" class="confirm-close-btn" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 16px; right: 16px; width: 32px; height: 32px; border: none; background: var(--bg-secondary, #1f2335); border-radius: 50%; color: var(--text-muted, #565f89); cursor: pointer; display: flex; align-items: center; justify-content: center; z-index: 10;">
                    <i class="bi bi-x-lg"></i>
                </button>
                <div class="confirm-modal-body" style="padding: 40px 32px 32px; text-align: center; background: var(--bg-card, #1f2335);">
                    <div class="confirm-icon-wrapper" style="position: relative; width: 80px; height: 80px; margin: 0 auto 24px;">
                        <div class="confirm-icon-bg" style="position: absolute; inset: 0; background: rgba(247, 118, 142, 0.15); border-radius: 50%;"></div>
                        <div class="confirm-icon" style="position: absolute; inset: 0; background: #f7768e; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: #ffffff; box-shadow: 0 8px 24px rgba(247, 118, 142, 0.4);">
                            <i class="bi bi-trash3"></i>
                        </div>
                    </div>
                    <h4 class="confirm-title" style="color: var(--text-primary, #c0caf5); font-weight: 700; font-size: 1.35rem; margin-bottom: 12px;">Hapus Data?</h4>
                    <p class="confirm-message" id="confirmMessage" style="color: var(--text-muted, #565f89); font-size: 0.95rem; line-height: 1.6; margin-bottom: 28px;">Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.</p>
                    <div class="confirm-actions" style="display: flex; gap: 12px; justify-content: center;">
                        <button type="button" class="confirm-btn confirm-btn-cancel" data-bs-dismiss="modal" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 24px; border-radius: 12px; font-weight: 600; font-size: 0.9rem; min-width: 120px; background: var(--bg-secondary, #1f2335); color: var(--text-primary, #c0caf5); border: 1px solid var(--border-color, #414868); cursor: pointer; transition: all 0.2s ease;">
                            <i class="bi bi-x-lg"></i>
                            <span>Batal</span>
                        </button>
                        <a href="#" class="confirm-btn confirm-btn-delete" id="confirmAction" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 24px; border-radius: 12px; font-weight: 600; font-size: 0.9rem; min-width: 120px; background: #f7768e; color: #ffffff; text-decoration: none; box-shadow: 0 4px 12px rgba(247, 118, 142, 0.3); transition: all 0.2s ease;">
                            <i class="bi bi-trash3"></i>
                            <span>Ya, Hapus</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize Tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl, {
                trigger: 'hover'
            });
        });

        // Toast Function
        function showToast(type, message) {
            const toastEl = document.getElementById('liveToast');
            const toastIcon = document.getElementById('toastIcon');
            const toastTitle = document.getElementById('toastTitle');
            const toastMessage = document.getElementById('toastMessage');

            // Remove previous classes
            toastEl.classList.remove('bg-success', 'bg-danger', 'bg-warning', 'bg-info');

            // Set based on type
            switch (type) {
                case 'success':
                    toastEl.classList.add('bg-success');
                    toastIcon.className = 'bi bi-check-circle-fill me-2';
                    toastTitle.textContent = 'Berhasil!';
                    break;
                case 'error':
                    toastEl.classList.add('bg-danger');
                    toastIcon.className = 'bi bi-x-circle-fill me-2';
                    toastTitle.textContent = 'Error!';
                    break;
                case 'warning':
                    toastEl.classList.add('bg-warning');
                    toastIcon.className = 'bi bi-exclamation-triangle-fill me-2';
                    toastTitle.textContent = 'Peringatan!';
                    break;
                case 'info':
                    toastEl.classList.add('bg-info');
                    toastIcon.className = 'bi bi-info-circle-fill me-2';
                    toastTitle.textContent = 'Info';
                    break;
            }

            toastMessage.textContent = message;

            const toast = new bootstrap.Toast(toastEl);
            toast.show();
        }

        // Check URL for messages
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const msg = urlParams.get('msg');

            if (msg) {
                let message = '';
                let type = 'success';

                switch (msg) {
                    case 'added':
                        message = 'Data berhasil ditambahkan!';
                        break;
                    case 'updated':
                        message = 'Data berhasil diupdate!';
                        break;
                    case 'deleted':
                        message = 'Data berhasil dihapus!';
                        break;
                    case 'error':
                        message = 'Terjadi kesalahan!';
                        type = 'error';
                        break;
                    case 'error_fk':
                        const detail = urlParams.get('detail');
                        message = 'Data tidak dapat dihapus karena masih digunakan di tabel ' + (detail || 'lain') + '!';
                        type = 'error';
                        break;
                    default:
                        message = msg;
                }

                showToast(type, message);

                // Clean URL
                const newUrl = window.location.href.split('?')[0] + '?page=' + urlParams.get('page');
                if (urlParams.get('p')) {
                    window.history.replaceState({}, document.title, newUrl + '&p=' + urlParams.get('p'));
                } else {
                    window.history.replaceState({}, document.title, newUrl);
                }
            }
        });

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebar');
            const hamburger = document.querySelector('.hamburger');

            if (sidebar && hamburger && window.innerWidth <= 991) {
                if (!sidebar.contains(e.target) && !hamburger.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });

        // Update datetime
        function updateDateTime() {
            const now = new Date();
            const options = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            };
            const dateTimeElement = document.getElementById('currentDateTime');
            if (dateTimeElement) {
                dateTimeElement.textContent = now.toLocaleDateString('id-ID', options);
            }
        }

        setInterval(updateDateTime, 60000);
        updateDateTime();

        // Theme Switcher Functions
        function initTheme() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
            updateThemeIcon(savedTheme);
        }

        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
        }

        function updateThemeIcon(theme) {
            const themeIcon = document.getElementById('themeIcon');
            if (themeIcon) {
                if (theme === 'light') {
                    themeIcon.className = 'bi bi-sun-fill';
                } else {
                    themeIcon.className = 'bi bi-moon-stars-fill';
                }
            }
        }

        // Initialize theme on page load
        initTheme();

        // Confirm Modal Handler
        document.addEventListener('DOMContentLoaded', function() {
            const confirmModal = document.getElementById('confirmModal');
            const confirmMessage = document.getElementById('confirmMessage');
            const confirmAction = document.getElementById('confirmAction');

            // Find all delete buttons with data-confirm attribute
            document.querySelectorAll('[data-confirm]').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const message = this.getAttribute('data-confirm') || 'Apakah Anda yakin?';
                    const href = this.getAttribute('href');

                    confirmMessage.textContent = message;
                    confirmAction.setAttribute('href', href);

                    const modal = new bootstrap.Modal(confirmModal);
                    modal.show();
                });
            });
        });
    </script>
</body>

</html>
<?php
mysqli_close($conn);
?>