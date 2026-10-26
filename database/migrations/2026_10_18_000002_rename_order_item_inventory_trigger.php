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

        DB::unprepared('DROP TRIGGER IF EXISTS after_transaction_details_insert');

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
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::unprepared('DROP TRIGGER IF EXISTS after_order_items_insert');
        }
    }
};
