# Kansei Rental

Aplikasi web **Kansei Rental** untuk pengelolaan penyewaan kendaraan bertema Kansei Rental (Japanese Domestic Market). Dibangun menggunakan **PHP**, **CSS**, **JS**.

## Fitur (contoh — sesuaikan)

- Manajemen data kendaraan (tambah/ubah/hapus)
- Manajemen pelanggan
- Pencatatan transaksi sewa & pengembalian
- Perhitungan total biaya sewa (berdasarkan durasi, denda, dll.)
- Halaman admin & laporan

## Teknologi

- **PHP** (backend)
- **CSS** (tampilan)
- **JS** (logic simple)
- **Database**: MySQL/MariaDB

## Persyaratan

- PHP 7.4+ _(sesuaikan dengan versi yang dipakai)_
- Web server (Apache/Nginx)
- MySQL/MariaDB
- Git

## Cara Menjalankan (Local)

1. Clone repository:

   ```bash
   git clone https://github.com/kuzanf3b/rental-Kansei Rental.git
   cd rental-Kansei Rental
   ```

2. Siapkan database _(jika ada)_:
   - Buat database, misalnya: `rental_jdm`
   - Import file SQL (`rental_jdm.sql`)

3. Konfigurasi koneksi database:
   - Cari file konfigurasi (`index.php`)
   - Isi host, user, password, dan nama database

4. Jalankan via web server:
   - Jika pakai XAMPP/Laragon/WAMP: taruh folder project ke `/www` lalu akses:
     - `http://localhost/rental-Kansei Rental`
   - Atau gunakan built-in server PHP:

     ```bash
     php -S localhost:8000
     ```

     Lalu buka `http://localhost:8000`

## Struktur Folder (opsional)

- `assets/css` — CSS folder
- `assets/js` — JS folder
- `pages/` — halaman aplikasi
- `index.php` — entry point aplikasi

## Akun Login (opsional)

- Admin: `admin / admin`
- User: `user / user`

## Kontribusi

Kontribusi terbuka. Silakan:

1. Fork repo ini
2. Buat branch fitur: `git checkout -b fitur/namafitur`
3. Commit perubahan: `git commit -m "Tambah fitur ..."`
4. Push ke branch: `git push origin fitur/namafitur`
5. Buat Pull Request


