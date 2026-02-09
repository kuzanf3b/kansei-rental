<?php
// Dashboard Page - Home Landing Page Style (Member) / Simple Dashboard (Admin/Petugas)

$is_member = ($_SESSION['user_level'] == 'member');
$nama_lengkap = $_SESSION['nama'] ?? $_SESSION['username'];

// Get statistics
$stat_mobil = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_mobil");
$mobil_total = $stat_mobil ? mysqli_fetch_assoc($stat_mobil)['total'] : 0;

$stat_tersedia = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_mobil WHERE status='tersedia'");
$mobil_tersedia = $stat_tersedia ? mysqli_fetch_assoc($stat_tersedia)['total'] : 0;

$stat_member = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_member");
$member_total = $stat_member ? mysqli_fetch_assoc($stat_member)['total'] : 0;

$stat_transaksi_total = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_transaksi");
$transaksi_total = $stat_transaksi_total ? mysqli_fetch_assoc($stat_transaksi_total)['total'] : 0;

$stat_transaksi_selesai = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_transaksi WHERE status='kembali'");
$transaksi_selesai = $stat_transaksi_selesai ? mysqli_fetch_assoc($stat_transaksi_selesai)['total'] : 0;

// Get all mobil for carousel - simple query without JOIN
$mobil_array = [];
$mobil_list = mysqli_query($conn, "SELECT * FROM tbl_mobil ORDER BY nopol");

if ($mobil_list && mysqli_num_rows($mobil_list) > 0) {
    while ($m = mysqli_fetch_assoc($mobil_list)) {
        // Get rental count for each car
        $count_query = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM tbl_transaksi WHERE nopol='" . mysqli_real_escape_string($conn, $m['nopol']) . "'");
        $m['total_sewa'] = $count_query ? mysqli_fetch_assoc($count_query)['cnt'] : 0;
        $mobil_array[] = $m;
    }
    // Sort by total_sewa descending
    usort($mobil_array, function ($a, $b) {
        return $b['total_sewa'] - $a['total_sewa'];
    });
}
?>

<?php if ($is_member): ?>
    <!--         ====
     MEMBER VIEW - Full Landing Page
             ==== -->
    <div class="home-page">
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-content">
                <div class="hero-badge">
                    <i class="bi bi-car-front-fill"></i>
                    <span>Selamat Datang di Rental JDM!</span>
                </div>
                <h1 class="hero-title">
                    Halo, <span class="highlight"><?= $nama_lengkap ?></span>!
                </h1>
                <p class="hero-subtitle">
                    Nikmati pengalaman rental mobil JDM terbaik dengan koleksi mobil Jepang legendaris.
                    Pelayanan profesional dan harga terjangkau untuk perjalanan Anda.
                </p>
                <div class="hero-buttons">
                    <a href="index.php?page=mobil" class="btn-hero primary">
                        <span>Lihat Mobil</span>
                        <i class="bi bi-arrow-right"></i>
                    </a>
                    <a href="index.php?page=transaksi" class="btn-hero secondary">
                        <span>Transaksi Saya</span>
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <div class="hero-trust">
                    <div class="trust-avatars">
                        <div class="trust-avatar" style="background: linear-gradient(135deg, #7aa2f7, #bb9af7);">J</div>
                        <div class="trust-avatar" style="background: linear-gradient(135deg, #9ece6a, #73daca);">D</div>
                        <div class="trust-avatar" style="background: linear-gradient(135deg, #f7768e, #ff9e64);">M</div>
                    </div>
                    <div class="trust-info">
                        <strong><?= $member_total ?>+ Member</strong>
                        <span>Sudah Bergabung</span>
                    </div>
                </div>
            </div>
            <div class="hero-image">
                <div class="hero-car-bg"></div>
                <img src="https://images.unsplash.com/photo-1592198084033-aade902d1aae?w=600&h=400&fit=crop" alt="JDM Car" class="hero-car">
                <div class="hero-floating-card top">
                    <i class="bi bi-shield-check"></i>
                    <div>
                        <strong>100%</strong>
                        <span>Terpercaya</span>
                    </div>
                </div>
                <div class="hero-floating-card bottom">
                    <i class="bi bi-car-front-fill"></i>
                    <div>
                        <strong><?= $mobil_tersedia ?>+</strong>
                        <span>Mobil Tersedia</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- About Section -->
        <section class="about-section">
            <div class="about-images">
                <div class="about-img-grid">
                    <div class="about-img main">
                        <img src="https://images.unsplash.com/photo-1560958089-b8a1929cea89?w=400&h=500&fit=crop" alt="Customer Happy">
                    </div>
                    <div class="about-img secondary">
                        <img src="https://images.unsplash.com/photo-1449965408869-eaa3f722e40d?w=400&h=300&fit=crop" alt="Car Service">
                    </div>
                </div>
                <div class="about-experience-badge">
                    <span class="exp-number">5+</span>
                    <span class="exp-text">Tahun<br>Pengalaman</span>
                </div>
            </div>
            <div class="about-content">
                <div class="section-badge">
                    <i class="bi bi-info-circle"></i>
                    <span>Tentang Kami</span>
                </div>
                <h2 class="section-title">
                    Kami Menyediakan <span class="highlight">Layanan Rental</span> Mobil Berkualitas
                </h2>
                <p class="section-desc">
                    Rental JDM adalah layanan rental mobil yang mengkhususkan diri pada mobil-mobil Jepang berkualitas tinggi.
                    Dengan armada yang terawat dan tim profesional, kami siap memberikan pengalaman berkendara terbaik untuk Anda.
                </p>
                <div class="about-features">
                    <div class="about-feature">
                        <div class="feature-icon">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <div class="feature-info">
                            <h4>Harga Terjangkau</h4>
                            <p>Harga kompetitif dengan kualitas pelayanan terbaik.</p>
                        </div>
                    </div>
                    <div class="about-feature">
                        <div class="feature-icon">
                            <i class="bi bi-headset"></i>
                        </div>
                        <div class="feature-info">
                            <h4>Layanan 24/7</h4>
                            <p>Tim support siap membantu kapan saja Anda butuhkan.</p>
                        </div>
                    </div>
                </div>
                <div class="about-checklist">
                    <div class="check-item"><i class="bi bi-check-circle-fill"></i> Mobil Terawat</div>
                    <div class="check-item"><i class="bi bi-check-circle-fill"></i> Proses Cepat</div>
                    <div class="check-item"><i class="bi bi-check-circle-fill"></i> Asuransi Lengkap</div>
                    <div class="check-item"><i class="bi bi-check-circle-fill"></i> Harga Transparan</div>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="stats-section">
            <div class="stats-bg"></div>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="bi bi-car-front"></i>
                    </div>
                    <div class="stat-number"><?= $mobil_total ?><sup>+</sup></div>
                    <div class="stat-label">Total Armada</div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="stat-number"><?= $member_total ?><sup>+</sup></div>
                    <div class="stat-label">Member Aktif</div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="stat-number"><?= $mobil_tersedia ?><sup>+</sup></div>
                    <div class="stat-label">Mobil Tersedia</div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div class="stat-number"><?= $transaksi_selesai ?><sup>+</sup></div>
                    <div class="stat-label">Transaksi Selesai</div>
                </div>
            </div>
        </section>

        <!-- How It Works Section -->
        <section class="steps-section">
            <div class="section-header">
                <div class="section-badge">
                    <i class="bi bi-gear"></i>
                    <span>Cara Kerja</span>
                </div>
                <h2 class="section-title">
                    Langkah Mudah Untuk <span class="highlight">Memulai Rental</span>
                </h2>
            </div>
            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-number">01</div>
                    <div class="step-icon">
                        <i class="bi bi-search"></i>
                    </div>
                    <h3>Pilih Mobil</h3>
                    <p>Jelajahi koleksi mobil JDM kami dan pilih yang sesuai dengan kebutuhan Anda.</p>
                </div>
                <div class="step-card">
                    <div class="step-number">02</div>
                    <div class="step-icon">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <h3>Booking Online</h3>
                    <p>Lakukan booking secara online dengan mudah melalui sistem kami.</p>
                </div>
                <div class="step-card">
                    <div class="step-number">03</div>
                    <div class="step-icon">
                        <i class="bi bi-car-front-fill"></i>
                    </div>
                    <h3>Ambil Mobil</h3>
                    <p>Datang ke lokasi kami, selesaikan pembayaran, dan nikmati perjalanan Anda!</p>
                </div>
            </div>
        </section>

        <!-- Popular Cars Carousel Section -->
        <section class="cars-section">
            <div class="section-header">
                <div class="section-badge">
                    <i class="bi bi-star"></i>
                    <span>Mobil Populer</span>
                </div>
                <h2 class="section-title">
                    Pilihan <span class="highlight">Mobil Terbaik</span> Kami
                </h2>
                <p class="section-subtitle">Scroll untuk melihat semua koleksi mobil JDM kami</p>
            </div>

            <!-- Infinite Carousel -->
            <div class="cars-carousel-wrapper">
                <div class="cars-carousel" id="carsCarouselMember">
                    <?php if (count($mobil_array) > 0): ?>
                        <?php
                        // Duplicate items for infinite scroll effect
                        $all_mobil = array_merge($mobil_array, $mobil_array, $mobil_array);
                        foreach ($all_mobil as $mobil):
                            $foto = !empty($mobil['foto']) ? 'uploads/mobil/' . $mobil['foto'] : 'https://via.placeholder.com/300x200?text=' . urlencode($mobil['brand']);
                            $statusClass = $mobil['status'] == 'tersedia' ? 'available' : 'rented';
                        ?>
                            <div class="car-card">
                                <div class="car-badge <?= $statusClass ?>">
                                    <?= ucfirst($mobil['status']) ?>
                                </div>
                                <div class="car-favorite">
                                    <i class="bi bi-heart"></i>
                                </div>
                                <div class="car-image">
                                    <img src="<?= $foto ?>" alt="<?= $mobil['brand'] . ' ' . $mobil['type'] ?>" loading="lazy">
                                </div>
                                <div class="car-info">
                                    <h3 class="car-name"><?= $mobil['brand'] . ' ' . $mobil['type'] ?></h3>
                                    <div class="car-rating">
                                        <i class="bi bi-star-fill"></i>
                                        <i class="bi bi-star-fill"></i>
                                        <i class="bi bi-star-fill"></i>
                                        <i class="bi bi-star-fill"></i>
                                        <i class="bi bi-star-half"></i>
                                        <span>4.5 (<?= $mobil['total_sewa'] ?> rental)</span>
                                    </div>
                                    <div class="car-specs">
                                        <div class="spec">
                                            <i class="bi bi-calendar3"></i>
                                            <span><?= $mobil['tahun'] ?></span>
                                        </div>
                                        <div class="spec">
                                            <i class="bi bi-credit-card-2-front"></i>
                                            <span><?= $mobil['nopol'] ?></span>
                                        </div>
                                    </div>
                                    <div class="car-footer">
                                        <div class="car-price">
                                            <span class="price">Rp <?= number_format($mobil['harga'], 0, ',', '.') ?></span>
                                            <span class="period">/hari</span>
                                        </div>
                                        <?php if ($mobil['status'] == 'tersedia'): ?>
                                            <a href="index.php?page=mobil&detail=<?= $mobil['nopol'] ?>" class="btn-book">
                                                <span>Booking</span>
                                                <i class="bi bi-arrow-right"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="btn-book disabled">Disewa</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-cars">
                            <i class="bi bi-car-front"></i>
                            <p>Belum ada mobil tersedia</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Carousel Controls -->
            <div class="carousel-controls">
                <button class="carousel-btn prev" onclick="scrollCarouselMember(-1)" title="Previous">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <div class="carousel-indicator">
                    <span>Geser untuk melihat lebih banyak</span>
                    <i class="bi bi-arrow-left-right"></i>
                </div>
                <button class="carousel-btn next" onclick="scrollCarouselMember(1)" title="Next">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>

            <div class="cars-cta">
                <a href="index.php?page=mobil" class="btn-hero primary">
                    <span>Lihat Semua Mobil</span>
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </section>
    </div>

    <script>
        // Carousel functionality for Member Dashboard
        const carouselMember = document.getElementById('carsCarouselMember');
        let isDownMember = false;
        let startXMember;
        let scrollLeftMember;
        let autoScrollIntervalMember;
        let scrollSpeedMember = 1;
        let isPausedMember = false;

        // Auto scroll function
        function startAutoScrollMember() {
            autoScrollIntervalMember = setInterval(() => {
                if (!isPausedMember && carouselMember) {
                    carouselMember.scrollLeft += scrollSpeedMember;

                    // Infinite scroll logic
                    const maxScroll = carouselMember.scrollWidth - carouselMember.clientWidth;
                    if (carouselMember.scrollLeft >= maxScroll - 10) {
                        carouselMember.scrollLeft = carouselMember.scrollLeft / 3;
                    }
                    if (carouselMember.scrollLeft <= 10) {
                        carouselMember.scrollLeft = carouselMember.scrollLeft + (maxScroll / 3);
                    }
                }
            }, 30);
        }

        // Manual scroll with buttons
        function scrollCarouselMember(direction) {
            const scrollAmount = 350;
            carouselMember.scrollBy({
                left: direction * scrollAmount,
                behavior: 'smooth'
            });
        }

        // Mouse/touch events for drag scrolling
        if (carouselMember) {
            carouselMember.addEventListener('mousedown', (e) => {
                isDownMember = true;
                isPausedMember = true;
                carouselMember.classList.add('active');
                startXMember = e.pageX - carouselMember.offsetLeft;
                scrollLeftMember = carouselMember.scrollLeft;
            });

            carouselMember.addEventListener('mouseleave', () => {
                isDownMember = false;
                carouselMember.classList.remove('active');
                setTimeout(() => {
                    isPausedMember = false;
                }, 2000);
            });

            carouselMember.addEventListener('mouseup', () => {
                isDownMember = false;
                carouselMember.classList.remove('active');
                setTimeout(() => {
                    isPausedMember = false;
                }, 2000);
            });

            carouselMember.addEventListener('mousemove', (e) => {
                if (!isDownMember) return;
                e.preventDefault();
                const x = e.pageX - carouselMember.offsetLeft;
                const walk = (x - startXMember) * 2;
                carouselMember.scrollLeft = scrollLeftMember - walk;
            });

            // Touch events for mobile
            carouselMember.addEventListener('touchstart', () => {
                isPausedMember = true;
            });

            carouselMember.addEventListener('touchend', () => {
                setTimeout(() => {
                    isPausedMember = false;
                }, 2000);
            });

            // Pause on hover
            carouselMember.addEventListener('mouseenter', () => {
                isPausedMember = true;
            });

            carouselMember.addEventListener('mouseleave', () => {
                if (!isDownMember) {
                    setTimeout(() => {
                        isPausedMember = false;
                    }, 1000);
                }
            });

            // Start auto scroll
            startAutoScrollMember();
        }
    </script>

<?php else: ?>
    <!--         ====
     ADMIN/PETUGAS VIEW - Simple Dashboard
             ==== -->
    <div class="admin-dashboard">
        <!-- Welcome Section -->
        <section class="admin-welcome">
            <div class="welcome-content">
                <div class="welcome-icon">
                    <i class="bi bi-emoji-smile"></i>
                </div>
                <div class="welcome-text">
                    <h1>Selamat Datang, <span class="highlight"><?= $nama_lengkap ?></span>!</h1>
                    <p>Anda login sebagai <strong><?= ucfirst($_SESSION['user_level']) ?></strong>. Kelola rental mobil dengan mudah melalui dashboard ini.</p>
                </div>
            </div>
            <div class="welcome-date">
                <i class="bi bi-calendar3"></i>
                <span><?= date('l, d F Y') ?></span>
            </div>
        </section>

        <!-- Quick Stats -->
        <section class="admin-stats">
            <div class="admin-stat-card">
                <div class="stat-icon primary">
                    <i class="bi bi-car-front-fill"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $mobil_total ?></h3>
                    <span>Total Mobil</span>
                </div>
            </div>
            <div class="admin-stat-card">
                <div class="stat-icon success">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $mobil_tersedia ?></h3>
                    <span>Mobil Tersedia</span>
                </div>
            </div>
            <div class="admin-stat-card">
                <div class="stat-icon warning">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $mobil_total - $mobil_tersedia ?></h3>
                    <span>Mobil Disewa</span>
                </div>
            </div>
            <div class="admin-stat-card">
                <div class="stat-icon info">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $member_total ?></h3>
                    <span>Member</span>
                </div>
            </div>
        </section>

        <!-- Popular Cars Section -->
        <section class="admin-popular-cars">
            <div class="section-header-admin">
                <h2><i class="bi bi-star-fill"></i> Mobil Populer</h2>
                <a href="index.php?page=mobil" class="btn-view-all">
                    Lihat Semua <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="admin-cars-grid">
                <?php
                // Use only first 4 cars for admin view
                $admin_mobil = array_slice($mobil_array, 0, 4);
                if (count($admin_mobil) > 0): ?>
                    <?php foreach ($admin_mobil as $mobil):
                        $foto = !empty($mobil['foto']) ? 'uploads/mobil/' . $mobil['foto'] : 'https://via.placeholder.com/300x200?text=' . urlencode($mobil['brand']);
                        $statusClass = $mobil['status'] == 'tersedia' ? 'available' : 'rented';
                    ?>
                        <div class="admin-car-card">
                            <div class="car-image-admin">
                                <img src="<?= $foto ?>" alt="<?= $mobil['brand'] . ' ' . $mobil['type'] ?>">
                                <div class="car-status <?= $statusClass ?>">
                                    <?= ucfirst($mobil['status']) ?>
                                </div>
                            </div>
                            <div class="car-info-admin">
                                <h4><?= $mobil['brand'] . ' ' . $mobil['type'] ?></h4>
                                <div class="car-meta">
                                    <span><i class="bi bi-credit-card-2-front"></i> <?= $mobil['nopol'] ?></span>
                                    <span><i class="bi bi-calendar3"></i> <?= $mobil['tahun'] ?></span>
                                </div>
                                <div class="car-price-admin">
                                    Rp <?= number_format($mobil['harga'], 0, ',', '.') ?>/hari
                                </div>
                                <div class="car-rental-count">
                                    <i class="bi bi-graph-up"></i> <?= $mobil['total_sewa'] ?> kali disewa
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-cars-admin">
                        <i class="bi bi-car-front"></i>
                        <p>Belum ada data mobil</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Quick Actions -->
        <section class="quick-actions">
            <h3><i class="bi bi-lightning-fill"></i> Aksi Cepat</h3>
            <div class="actions-grid">
                <a href="index.php?page=transaksi" class="action-card">
                    <i class="bi bi-plus-circle-fill"></i>
                    <span>Transaksi Baru</span>
                </a>
                <a href="index.php?page=mobil" class="action-card">
                    <i class="bi bi-car-front-fill"></i>
                    <span>Kelola Mobil</span>
                </a>
                <a href="index.php?page=member" class="action-card">
                    <i class="bi bi-people-fill"></i>
                    <span>Kelola Member</span>
                </a>
                <a href="index.php?page=kembali" class="action-card">
                    <i class="bi bi-arrow-return-left"></i>
                    <span>Pengembalian</span>
                </a>
            </div>
        </section>
    </div>
<?php endif; ?>