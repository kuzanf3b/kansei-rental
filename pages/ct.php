<div class="container-fluid mb-5">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold" style="color: var(--text-primary);">Penerapan Computational Thinking</h2>
            <p class="text-muted">Konsep berpikir komputasional yang diterapkan di balik layar pembangunan aplikasi Kansei Rental.</p>
        </div>
    </div>

    <div class="row row-cols-1 g-4">
        <!-- Decomposition -->
        <div class="col">
            <div class="card h-100 shadow-sm" style="background: var(--bg-card); border-left: 4px solid var(--primary-color);">
                <div class="card-body">
                    <h5 class="card-title fw-bold text-primary mb-3"><i class="bi bi-puzzle me-2"></i>1. Problem Decomposition (Dekomposisi)</h5>
                    <p class="card-text" style="color: var(--text-secondary);">
                        Aplikasi rental mobil yang kompleks dipecah menjadi modul-modul yang lebih kecil dan mudah dikelola. Sistem kami memecahnya melalui routing terstruktur pada <code>index.php</code> yang memuat halaman dari folder <code>pages/</code> (seperti <code>mobil.php</code>, <code>transaksi.php</code>, <code>member.php</code>). File aset (CSS/JS) juga dikelompokkan secara terpisah, dan koneksi database diisolasi dalam <code>config/database.php</code>.
                    </p>
                </div>
            </div>
        </div>

        <!-- Pattern Recognition -->
        <div class="col">
            <div class="card h-100 shadow-sm" style="background: var(--bg-card); border-left: 4px solid var(--info-color);">
                <div class="card-body">
                    <h5 class="card-title fw-bold text-info mb-3"><i class="bi bi-grid-1x2 me-2"></i>2. Pattern Recognition (Pengenalan Pola)</h5>
                    <p class="card-text" style="color: var(--text-secondary);">
                        Dalam desain visual dan tabel data, terdapat pola desain yang sering berulang. Kami menemukan pola tersebut dan menerapkan <strong>reusable styling</strong> (menggunakan Bootstrap 5 dan CSS Variables untuk mode gelap). Penggunaan pola card di halaman transaksi dan katalog mobil dibuat satu kali dengan struktur tag HTML yang berulang hanya isinya (melalui Data array dari database).
                    </p>
                </div>
            </div>
        </div>

        <!-- Abstraction -->
        <div class="col">
            <div class="card h-100 shadow-sm" style="background: var(--bg-card); border-left: 4px solid var(--warning-color);">
                <div class="card-body">
                    <h5 class="card-title fw-bold text-warning mb-3"><i class="bi bi-layer-backward me-2"></i>3. Abstraction (Abstraksi)</h5>
                    <p class="card-text" style="color: var(--text-secondary);">
                        Kompleksitas query MySQL (<code>SELECT, INSERT, UPDATE, JOIN</code>) diabstraksikan dan disembunyikan di layer Controller / backend setiap halamannya. Pengguna hanya melihat tombol "Selesai" atau "Booking", sementara logika PHP memproses perhitungan denda dan durasi sewa di balik layar tanpa menunjukkan rincian teknisnya. Router utama menyembunyikan path asli .php dengan parameter <code>?page=...</code>.
                    </p>
                </div>
            </div>
        </div>

        <!-- Algorithm Design -->
        <div class="col">
            <div class="card h-100 shadow-sm" style="background: var(--bg-card); border-left: 4px solid var(--success-color);">
                <div class="card-body">
                    <h5 class="card-title fw-bold text-success mb-3"><i class="bi bi-gear-wide-connected me-2"></i>4. Algorithm Design (Algoritma Desain)</h5>
                    <p class="card-text" style="color: var(--text-secondary);">Langkah-langkah logis dan berurutan untuk menyelesaikan masalah. Misalnya, <strong>Algoritma Penyewaan Kendaraan</strong>:<br/>
                        <ol class="mt-2 text-muted">
                            <li>Member mendaftar dan masuk ke akun.</li>
                            <li>Sistem memverifikasi login session.</li>
                            <li>Member menyewa kendaraan dan memasukkan tanggal penyewaan.</li>
                            <li>Status transaksi masuk ke antrian <code>Booking</code>.</li>
                            <li>Petugas memeriksa dan <code>Approve</code>.</li>
                            <li>Saat mobil dikembalikan, sistem membandingkan Tanggal Pengembalian dengan batas Akhir. Jika lebih, otomatis denda dikalkulasi dan ditagihkan.</li>
                        </ol>
                    </p>
                </div>
            </div>
        </div>

        <!-- User Flow -->
        <div class="col">
            <div class="card h-100 shadow-sm" style="background: var(--bg-card); border-left: 4px solid var(--danger-color);">
                <div class="card-body">
                    <h5 class="card-title fw-bold text-danger mb-3"><i class="bi bi-diagram-3 me-2"></i>5. User Flow</h5>
                    <p class="card-text" style="color: var(--text-secondary);">
                        Alur perjalanan pengguna dari awal mengunjungi situs hingga mendapat tujuan mereka:
                        <ul class="text-muted mt-2">
                            <li><strong>Untuk Member (Penyewa):</strong> Login &rarr; Halaman Home / Katalog Mobil &rarr; Form Pemesanan &rarr; Cek Status Transaksi & Riwayat &rarr; Logout.</li>
                            <li><strong>Untuk Admin / Petugas:</strong> Login &rarr; Dashboard (Melihat Laporan) &rarr; Manajemen Data Masters (Mobil & User) &rarr; Validasi & Proses Transaksi &rarr; Logout.</li>
                        </ul>
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>
