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

        DB::unprepared('DROP TRIGGER IF EXISTS after_order_items_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS after_products_update_stok');
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER after_products_update_stok
            AFTER UPDATE ON products
            FOR EACH ROW
            BEGIN
                IF NEW.stock <> OLD.stock THEN
                    INSERT INTO stock_logs
                        (product_id, user_id, transaction_id, type, quantity_change, stock_before, stock_after, reference, note, created_at, updated_at)
                    VALUES
                        (
                            NEW.id,
                            @silaturizky_user_id,
                            @silaturizky_transaction_id,
                            COALESCE(
                                @silaturizky_stock_type,
                                IF(NEW.stock > OLD.stock, 'incoming', 'outgoing')
                            ),
                            NEW.stock - OLD.stock,
                            OLD.stock,
                            NEW.stock,
                            CONCAT('stock-update:', NEW.id, ':', UUID()),
                            IF(NEW.stock > OLD.stock, 'Stok masuk', 'Stok keluar'),
                            NOW(),
                            NOW()
                        );
                END IF;
            END
        SQL);

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

        DB::unprepared('DROP TRIGGER IF EXISTS after_products_update_stok');
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER after_products_update_stok
            AFTER UPDATE ON products
            FOR EACH ROW
            BEGIN
                IF NEW.stock > OLD.stock THEN
                    INSERT INTO stock_logs
                        (product_id, type, quantity_change, stock_before, stock_after, reference, note, created_at, updated_at)
                    VALUES
                        (NEW.id, 'incoming', NEW.stock - OLD.stock, OLD.stock, NEW.stock,
                         CONCAT('trigger-in:', NEW.id, ':', UUID()), 'Stok masuk melalui perubahan stok produk', NOW(), NOW());
                END IF;
            END
        SQL);
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER after_order_items_insert
            AFTER INSERT ON transaction_details
            FOR EACH ROW
            BEGIN
                INSERT INTO stock_logs
                    (product_id, user_id, transaction_id, type, quantity_change, stock_before, stock_after, reference, note, created_at, updated_at)
                SELECT NEW.product_id, COALESCE(t.handled_by, t.user_id), NEW.transaction_id,
                    IF(t.status IN ('pending', 'pending_pickup'), 'reservation', 'sale'),
                    -NEW.quantity, p.stock, p.stock - NEW.quantity,
                    CONCAT('detail:', NEW.id), CONCAT('Penjualan ', t.transaction_number), NOW(), NOW()
                FROM transactions t
                JOIN products p ON p.id = NEW.product_id
                WHERE t.id = NEW.transaction_id;
            END
        SQL);
        DB::unprepared('DROP PROCEDURE IF EXISTS laporan_harian');
        DB::unprepared(<<<'SQL'
            CREATE PROCEDURE laporan_harian(IN p_tgl DATE)
            BEGIN
                SELECT
                    COALESCE((SELECT SUM(total) FROM transactions
                        WHERE status = 'completed' AND DATE(created_at) = p_tgl), 0) AS omzet,
                    COALESCE((SELECT SUM(hitung_laba(subtotal, purchase_price * quantity))
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