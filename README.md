# Rental JDM

Aplikasi web **Rental JDM** untuk pengelolaan penyewaan kendaraan bertema JDM (Japanese Domestic Market). Dibangun menggunakan **PHP** dan **CSS**.

## Fitur (contoh — sesuaikan)
- Manajemen data kendaraan (tambah/ubah/hapus)
- Manajemen pelanggan
- Pencatatan transaksi sewa & pengembalian
- Perhitungan total biaya sewa (berdasarkan durasi, denda, dll.)
- Halaman admin & laporan (opsional)

## Teknologi
- **PHP** (backend)
- **CSS** (tampilan)
- **Database**: MySQL/MariaDB *(sesuaikan jika berbeda)*

## Persyaratan
- PHP 7.4+ *(sesuaikan dengan versi yang dipakai)*
- Web server (Apache/Nginx)
- MySQL/MariaDB *(jika menggunakan database)*
- Git

## Cara Menjalankan (Local)
1. Clone repository:
   ```bash
   git clone https://github.com/kuzanf3b/rental-jdm.git
   cd rental-jdm
   ```

2. Siapkan database *(jika ada)*:
   - Buat database, misalnya: `rental_jdm`
   - Import file SQL jika tersedia (misalnya `database.sql`) *(sesuaikan nama filenya)*

3. Konfigurasi koneksi database:
   - Cari file konfigurasi (misalnya `config.php`, `koneksi.php`, atau `.env`) *(sesuaikan)*
   - Isi host, user, password, dan nama database

4. Jalankan via web server:
   - Jika pakai XAMPP/Laragon: taruh folder project ke `htdocs/www` lalu akses:
     - `http://localhost/rental-jdm`
   - Atau gunakan built-in server PHP:
     ```bash
     php -S localhost:8000
     ```
     Lalu buka `http://localhost:8000`

## Struktur Folder (opsional)
> Sesuaikan dengan struktur sebenarnya.
- `assets/` — file CSS/gambar/js
- `pages/` — halaman aplikasi
- `config/` — konfigurasi
- `index.php` — entry point aplikasi

## Akun Login (opsional)
> Isi jika aplikasi memiliki autentikasi.
- Admin: `admin / admin` *(contoh)*
- User: `user / user` *(contoh)*

## Kontribusi
Kontribusi terbuka. Silakan:
1. Fork repo ini
2. Buat branch fitur: `git checkout -b fitur/namafitur`
3. Commit perubahan: `git commit -m "Tambah fitur ..."`
4. Push ke branch: `git push origin fitur/namafitur`
5. Buat Pull Request

## Lisensi
Tentukan lisensi project (MIT/Apache-2.0/dll). *(opsional)*
