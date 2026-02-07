<?php
// Home Page for Guest - Public Landing Page

// Get statistics
$stat_mobil = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_mobil");
$mobil_total = $stat_mobil ? mysqli_fetch_assoc($stat_mobil)['total'] : 0;

$stat_tersedia = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_mobil WHERE status='tersedia'");
$mobil_tersedia = $stat_tersedia ? mysqli_fetch_assoc($stat_tersedia)['total'] : 0;

$stat_member = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_member");
$member_total = $stat_member ? mysqli_fetch_assoc($stat_member)['total'] : 0;

$stat_transaksi = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_transaksi WHERE status='kembali'");
$transaksi_selesai = $stat_transaksi ? mysqli_fetch_assoc($stat_transaksi)['total'] : 0;

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

<!-- ============================================
     GUEST VIEW - Full Landing Page
     ============================================ -->
<div class="home-page">
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <div class="hero-badge">
                <i class="bi bi-car-front-fill"></i>
                <span>Rental Mobil JDM Premium</span>
            </div>
            <h1 class="hero-title">
                Temukan Mobil <span class="highlight">JDM Impian</span> Anda
            </h1>
            <p class="hero-subtitle">
                Nikmati pengalaman rental mobil JDM terbaik dengan koleksi mobil Jepang legendaris.
                Pelayanan profesional dan harga terjangkau untuk perjalanan Anda.
            </p>
            <div class="hero-buttons">
                <a href="index.php?page=login" class="btn-hero primary">
                    <span>Login untuk Booking</span>
                    <i class="bi bi-arrow-right"></i>
                </a>
                <a href="index.php?page=register" class="btn-hero secondary">
                    <span>Daftar Sekarang</span>
                    <i class="bi bi-person-plus"></i>
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
                    <i class="bi bi-person-plus"></i>
                </div>
                <h3>Daftar / Login</h3>
                <p>Buat akun atau login untuk mengakses layanan rental kami.</p>
            </div>
            <div class="step-card">
                <div class="step-number">02</div>
                <div class="step-icon">
                    <i class="bi bi-search"></i>
                </div>
                <h3>Pilih Mobil</h3>
                <p>Jelajahi koleksi mobil JDM kami dan pilih yang sesuai kebutuhan.</p>
            </div>
            <div class="step-card">
                <div class="step-number">03</div>
                <div class="step-icon">
                    <i class="bi bi-car-front-fill"></i>
                </div>
                <h3>Booking & Nikmati</h3>
                <p>Lakukan booking online dan nikmati perjalanan Anda!</p>
            </div>
        </div>
    </section>

    <!-- Popular Cars Carousel Section -->
    <section class="cars-section">
        <div class="section-header">
            <div class="section-badge">
                <i class="bi bi-star"></i>
                <span>Koleksi Kami</span>
            </div>
            <h2 class="section-title">
                Pilihan <span class="highlight">Mobil Terbaik</span> Kami
            </h2>
            <p class="section-subtitle">Scroll untuk melihat semua koleksi mobil JDM kami</p>
        </div>

        <!-- Infinite Carousel -->
        <div class="cars-carousel-wrapper">
            <div class="cars-carousel" id="carsCarousel">
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
                                    <a href="index.php?page=login" class="btn-book" onclick="showLoginAlert(event)">
                                        <span>Booking</span>
                                        <i class="bi bi-arrow-right"></i>
                                    </a>
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
            <button class="carousel-btn prev" onclick="scrollCarousel(-1)" title="Previous">
                <i class="bi bi-chevron-left"></i>
            </button>
            <div class="carousel-indicator">
                <span>Geser untuk melihat lebih banyak</span>
                <i class="bi bi-arrow-left-right"></i>
            </div>
            <button class="carousel-btn next" onclick="scrollCarousel(1)" title="Next">
                <i class="bi bi-chevron-right"></i>
            </button>
        </div>

        <div class="cars-cta">
            <a href="index.php?page=login" class="btn-hero primary">
                <span>Login untuk Booking</span>
                <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="cta-content">
            <h2>Siap Untuk Rental Mobil JDM?</h2>
            <p>Daftar sekarang dan nikmati koleksi mobil Jepang legendaris kami dengan harga terjangkau.</p>
            <div class="cta-buttons">
                <a href="index.php?page=register" class="btn-hero primary">
                    <span>Daftar Sekarang</span>
                    <i class="bi bi-arrow-right"></i>
                </a>
                <a href="index.php?page=login" class="btn-hero secondary">
                    <span>Sudah Punya Akun?</span>
                    <i class="bi bi-box-arrow-in-right"></i>
                </a>
            </div>
        </div>
    </section>
</div>

<!-- Login Alert Modal -->
<div class="modal fade" id="loginAlertModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px;">
            <div class="modal-body text-center" style="padding: 40px;">
                <div style="width: 80px; height: 80px; background: rgba(122, 162, 247, 0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                    <i class="bi bi-person-lock" style="font-size: 2rem; color: var(--primary-color);"></i>
                </div>
                <h4 style="color: var(--text-primary); margin-bottom: 12px;">Login Diperlukan</h4>
                <p style="color: var(--text-muted); margin-bottom: 24px;">Anda harus login terlebih dahulu untuk melakukan booking mobil.</p>
                <div style="display: flex; gap: 12px; justify-content: center;">
                    <button type="button" class="btn" data-bs-dismiss="modal" style="background: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border-color); padding: 10px 20px; border-radius: 8px;">
                        Nanti
                    </button>
                    <a href="index.php?page=login" class="btn" style="background: var(--primary-color); color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none;">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Login
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Show login alert modal
    function showLoginAlert(event) {
        event.preventDefault();
        const modal = new bootstrap.Modal(document.getElementById('loginAlertModal'));
        modal.show();
    }

    // Carousel functionality
    const carousel = document.getElementById('carsCarousel');
    let isDown = false;
    let startX;
    let scrollLeft;
    let autoScrollInterval;
    let scrollSpeed = 1;
    let isPaused = false;

    // Auto scroll function
    function startAutoScroll() {
        autoScrollInterval = setInterval(() => {
            if (!isPaused && carousel) {
                carousel.scrollLeft += scrollSpeed;

                // Infinite scroll logic
                const maxScroll = carousel.scrollWidth - carousel.clientWidth;
                if (carousel.scrollLeft >= maxScroll - 10) {
                    carousel.scrollLeft = carousel.scrollLeft / 3;
                }
                if (carousel.scrollLeft <= 10) {
                    carousel.scrollLeft = carousel.scrollLeft + (maxScroll / 3);
                }
            }
        }, 30);
    }

    // Manual scroll with buttons
    function scrollCarousel(direction) {
        const scrollAmount = 350;
        carousel.scrollBy({
            left: direction * scrollAmount,
            behavior: 'smooth'
        });
    }

    // Mouse/touch events for drag scrolling
    if (carousel) {
        carousel.addEventListener('mousedown', (e) => {
            isDown = true;
            isPaused = true;
            carousel.classList.add('active');
            startX = e.pageX - carousel.offsetLeft;
            scrollLeft = carousel.scrollLeft;
        });

        carousel.addEventListener('mouseleave', () => {
            isDown = false;
            carousel.classList.remove('active');
            setTimeout(() => {
                isPaused = false;
            }, 2000);
        });

        carousel.addEventListener('mouseup', () => {
            isDown = false;
            carousel.classList.remove('active');
            setTimeout(() => {
                isPaused = false;
            }, 2000);
        });

        carousel.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - carousel.offsetLeft;
            const walk = (x - startX) * 2;
            carousel.scrollLeft = scrollLeft - walk;
        });

        // Touch events for mobile
        carousel.addEventListener('touchstart', () => {
            isPaused = true;
        });

        carousel.addEventListener('touchend', () => {
            setTimeout(() => {
                isPaused = false;
            }, 2000);
        });

        // Pause on hover
        carousel.addEventListener('mouseenter', () => {
            isPaused = true;
        });

        carousel.addEventListener('mouseleave', () => {
            if (!isDown) {
                setTimeout(() => {
                    isPaused = false;
                }, 1000);
            }
        });

        // Start auto scroll
        startAutoScroll();
    }
</script>