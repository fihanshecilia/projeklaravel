# Panduan Deployment — TOKO SILATURIZKY

## Kebutuhan Server
- PHP 8.3+ dengan ekstensi: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `intl`, `curl`, `zip`.
- MySQL 8 / MariaDB 10.6+.
- Composer 2, Node.js 20+, npm.
- `mysqldump` tersedia di PATH atau set `DB_DUMP_BINARY`.
- Web server Apache/Nginx dengan document root ke folder `public`.

## Langkah Publikasi
1. Upload source code (tanpa `vendor`, `node_modules`, `.env`).
2. Jalankan:
   ```bash
   composer install --no-dev --optimize-autoloader
   cp .env.example .env
   php artisan key:generate
   ```
3. Isi `.env` produksi:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL=https://domain-anda`
   - `DB_*` sesuai database hosting
   - `STORE_ADMIN_EMAIL`, `STORE_ADMIN_PASSWORD` (password unik minimal 12 karakter)
   - `FILESYSTEM_DISK=public`
4. Buat database kosong, lalu:
   ```bash
   php artisan migrate --force
   php artisan storage:link
   ```
5. Build asset:
   ```bash
   npm install
   npm run build
   ```
6. Optimasi:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
7. Pastikan folder `storage/` dan `bootstrap/cache/` writable.

## Keamanan
- Jangan aktifkan `APP_DEBUG=true` di produksi.
- Gunakan HTTPS dan cookie secure.
- Ganti password admin seed setelah login pertama.
- Aktifkan backup database berkala.
- Batasi akses server dan gunakan firewall.

## Hosting yang Kompatibel
- VPS/cloud: DigitalOcean, Vultr, Linode, AWS EC2, Google Cloud.
- Platform Laravel: Laravel Forge, Ploi, Cloudways.
- Shared hosting yang mendukung PHP 8.3 + MySQL + Composer (mis. Hostinger Business, Niagahoster, Dewaweb) dengan catatan fitur cron/queue manual.

## Catatan
- Website belum dipublikasikan; langkah di atas adalah panduan, bukan deployment otomatis.
- Jangan gunakan `migrate:fresh`, `db:wipe`, truncate, atau reset database pada data produksi.