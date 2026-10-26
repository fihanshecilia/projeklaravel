# Toko Silaturizky

Aplikasi toko sembako full-stack dengan storefront pelanggan, POS kasir, CRUD
admin, pengelolaan stok, laporan, dan backup database. Dibangun dengan Laravel
12, Filament 3, dan Vite.

## Kebutuhan

- PHP 8.2+ beserta ekstensi `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, dan `intl`
- Composer, Node.js/npm
- Laragon MySQL/MariaDB pada port `3307`
- `mysqldump` di `PATH`, atau atur `DB_DUMP_BINARY` ke lokasi executable

## Instalasi Laragon

1. Buat database kosong bernama `toko_silaturizky` di phpMyAdmin, atau jalankan:

   ```sql
   CREATE DATABASE toko_silaturizky CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. Pastikan `.env` menggunakan konfigurasi ini:

   ```dotenv
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3307
   DB_DATABASE=toko_silaturizky
   DB_USERNAME=root
   DB_PASSWORD=
   STORE_ADMIN_EMAIL=tokosilaturizky@gmail.com
   STORE_ADMIN_PASSWORD=silaturizkYToko
   ```

3. Jalankan dari PowerShell pada folder proyek:

   ```powershell
   composer install
   php artisan key:generate
   php artisan migrate --seed
   php artisan storage:link
   npm install
   npm run build
   php artisan serve
   ```

   Buka `http://127.0.0.1:8000`. Untuk pengembangan, gunakan `npm run dev`
   di terminal kedua. Seed awal membuat akun admin sesuai variabel
   `STORE_ADMIN_EMAIL` dan `STORE_ADMIN_PASSWORD`; ubah kata sandi sebelum
   aplikasi digunakan pada jaringan publik. Admin dapat menambahkan akun kasir
   dari menu **Pelanggan & pengguna**.

## Fitur dan rute

- `/` — katalog responsif, keranjang, checkout tamu, pilihan ambil/antar,
  serta pilihan COD/transfer/QRIS.
- `/pesanan/{nomor}` — detail dan pelacakan pesanan, termasuk konfirmasi WhatsApp.
- `/masuk` — satu halaman login. Pelanggan memakai Google SSO; admin/kasir
  memakai email dan kata sandi.
- `/pelanggan/pesanan` — riwayat pesanan bagi pelanggan yang login dengan Google.
- `/kasir` — POS, transaksi atomik, antrean pesanan online, dan cetak struk.
- `/admin` — CRUD produk, kategori, pengguna, serta daftar dan pengelolaan status
  pesanan online.
- `/admin/laporan` dan `/admin/laporan/stok` — omzet, laba, peringatan stok
  rendah, serta audit stok.
- `/admin/pengaturan/backup` — unduhan dump MySQL/MariaDB melalui `mysqldump`.

Atur `GOOGLE_CLIENT_ID` dan `GOOGLE_CLIENT_SECRET` di `.env`, lalu daftarkan
`/masuk/google/callback` sebagai redirect URI pada OAuth client Google.
`STORE_WHATSAPP_NUMBER` dan `STORE_ADDRESS` mengatur tautan konfirmasi order.
Transfer bank dan QRIS hanya dicatat sebagai metode pembayaran; payment gateway
tidak disertakan.

## Database dan transaksi

Jalankan migrasi Laravel untuk membuat tabel aplikasi dan stored routines.
`database/sql/toko_silaturizky.sql` menyediakan bootstrap SQL standalone untuk
MySQL/MariaDB baru; jangan impor skrip bootstrap itu ke database yang sudah
dikelola migrasi. Desain relasi ada di `database/ERD.md`.

Transaksi POS dan checkout memakai transaction database eksplisit, mengunci
produk dengan `FOR UPDATE`, memvalidasi stok, lalu commit atau rollback.
Trigger menolak stok negatif dan mencatat semua perubahan stok. Fungsi
`hitung_laba`, prosedur `laporan_harian()` dan `proses_checkout()` dipasang
untuk MySQL/MariaDB; SQLite digunakan untuk automated tests.

Backup admin menyertakan tabel, routines, triggers, dan events. Atur
`DB_DUMP_BINARY` bila executable Laragon tidak ditemukan otomatis.

## Verifikasi

```powershell
php artisan test
npm run build
```
