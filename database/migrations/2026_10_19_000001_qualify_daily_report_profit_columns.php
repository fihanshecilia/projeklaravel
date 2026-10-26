<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::unprepared('DROP PROCEDURE IF EXISTS laporan_harian');
        DB::unprepared(<<<'SQL'
            CREATE PROCEDURE laporan_harian()
            BEGIN
                SELECT
                    COALESCE((
                        SELECT SUM(total)
                        FROM transactions
                        WHERE status = 'completed' AND DATE(created_at) = CURRENT_DATE()
                    ), 0) AS omzet,
                    COALESCE((
                        SELECT SUM(hitung_laba(d.subtotal, d.purchase_price * d.quantity))
                        FROM transaction_details d
                        JOIN transactions t ON t.id = d.transaction_id
                        WHERE t.status = 'completed' AND DATE(t.created_at) = CURRENT_DATE()
                    ), 0) AS laba_bersih,
                    (
                        SELECT COUNT(*)
                        FROM transactions
                        WHERE status = 'completed' AND DATE(created_at) = CURRENT_DATE()
                    ) AS total_transaksi;
            END
        SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::unprepared('DROP PROCEDURE IF EXISTS laporan_harian');
        DB::unprepared(<<<'SQL'
            CREATE PROCEDURE laporan_harian(IN p_tgl DATE)
            BEGIN
                SELECT
                    COALESCE((SELECT SUM(total) FROM transactions
                        WHERE status = 'completed' AND DATE(created_at) = p_tgl), 0) AS omzet,
                    COALESCE((SELECT SUM(hitung_laba(d.subtotal, d.purchase_price * d.quantity))
                        FROM transaction_details d
                        JOIN transactions t ON t.id = d.transaction_id
                        WHERE t.status = 'completed' AND DATE(t.created_at) = p_tgl), 0) AS laba_bersih,
                    (SELECT COUNT(*) FROM transactions
                        WHERE status = 'completed' AND DATE(created_at) = p_tgl) AS total_transaksi,
                    (SELECT product_name FROM transaction_details d
                        JOIN transactions t ON t.id = d.transaction_id
                        WHERE t.status = 'completed' AND DATE(t.created_at) = p_tgl
                        GROUP BY product_name ORDER BY SUM(d.quantity) DESC LIMIT 1) AS produk_terlaris;
            END
        SQL);
    }
};
