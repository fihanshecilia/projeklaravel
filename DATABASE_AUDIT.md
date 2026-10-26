# Audit Database — TOKO SILATURIZKY

## Status Saat Ini
- Database lokal: `toko_silaturizky` (MySQL, port 3307).
- Migrasi terpasang: 20 (setelah `php artisan migrate`).
- Tabel utama dan jumlah data:
  - users: 4 (1 admin, 1 kasir uji, 2 pelanggan)
  - categories: 5
  - products: 5
  - transactions: 6
  - transaction_details: 13
  - stock_logs: 20
  - sessions: 10
  - carts: dihapus oleh migrasi `2026_10_24_234000` karena tidak dipakai (0 data)
- Duplikat email/telepon pengguna: tidak ditemukan.
- Akun admin seed cocok dengan `.env` (`tokosilaturizky@gmail.com`).

## Temuan
1. `carts` adalah tabel percobaan lama dan sudah kosong; migrasi terbaru menghapusnya.
2. Kolom `phone` pada `users` masih dipertahankan sebagai alias kompatibilitas untuk `wa_number`; jangan dihapus sebelum audit penggunaan storefront selesai.
3. `email_verified_at` belum dipakai oleh alur login email/password; dapat menjadi kandidat pembersihan setelah fitur verifikasi email diputuskan.
4. Tabel framework (`cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `sessions`, `password_reset_tokens`) diperlukan oleh Laravel dan tidak boleh dihapus.
5. Transaksi yang ada adalah data nyata/riwayat; tidak ada transaksi percobaan yang aman dihapus otomatis.

## Rencana Pembersihan (belum dieksekusi)
- Backup database terlebih dahulu (`mysqldump`/backup admin).
- Hanya setelah persetujuan, hapus data uji yang jelas (mis. transaksi dengan nomor uji) beserta detail dan stock log terkait.
- Hapus kolom legacy (`phone`, `email_verified_at`) hanya setelah seluruh kode dan test tidak lagi mereferensinya.
- Jangan jalankan `migrate:fresh`, `db:wipe`, truncate, atau reset database.
- Jangan hapus akun, histori, atau struktur database tanpa persetujuan.