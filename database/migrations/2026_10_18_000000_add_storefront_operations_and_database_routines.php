<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('address')->nullable();
            $table->string('pin')->nullable();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('handled_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->decimal('total_cost', 15, 2)->default(0)->after('total');
            $table->string('delivery_type', 20)->default('pickup')->after('sales_channel');
        });

        Schema::table('transaction_details', function (Blueprint $table) {
            $table->decimal('purchase_price', 15, 2)->default(0)->after('unit_price');
        });

        Schema::create('carts', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 12, 3)->default(1);
            $table->timestamps();
            $table->unique(['user_id', 'product_id']);
        });

        DB::table('transaction_details')
            ->whereNotNull('product_id')
            ->update([
                'purchase_price' => DB::raw(
                    '(SELECT purchase_price FROM products WHERE products.id = transaction_details.product_id)',
                ),
            ]);

        DB::table('transactions')->update([
            'total_cost' => DB::raw(
                '(SELECT COALESCE(SUM(transaction_details.purchase_price * transaction_details.quantity), 0) '
                .'FROM transaction_details WHERE transaction_details.transaction_id = transactions.id)',
            ),
        ]);

        if (DB::connection()->getDriverName() === 'mysql') {
            $this->installMysqlRoutines();
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::unprepared('DROP TRIGGER IF EXISTS after_order_items_insert');
            DB::unprepared('DROP TRIGGER IF EXISTS after_products_update_stok');
            DB::unprepared('DROP TRIGGER IF EXISTS before_products_update');
            DB::unprepared('DROP PROCEDURE IF EXISTS proses_checkout');
            DB::unprepared('DROP PROCEDURE IF EXISTS laporan_harian');
            DB::unprepared('DROP FUNCTION IF EXISTS hitung_omzet_periode');
            DB::unprepared('DROP FUNCTION IF EXISTS hitung_laba');
        }

        Schema::dropIfExists('carts');

        Schema::table('transaction_details', function (Blueprint $table) {
            $table->dropColumn('purchase_price');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('handled_by');
            $table->dropColumn(['total_cost', 'delivery_type']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['address', 'pin']);
        });
    }

    private function installMysqlRoutines(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE FUNCTION hitung_laba(p_jual DECIMAL(15,2), p_modal DECIMAL(15,2))
            RETURNS DECIMAL(15,2) DETERMINISTIC
            RETURN p_jual - p_modal
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE FUNCTION hitung_omzet_periode(p_tgl_mulai DATE, p_tgl_selesai DATE)
            RETURNS DECIMAL(15,2) READS SQL DATA
            RETURN (
                SELECT COALESCE(SUM(total), 0)
                FROM transactions
                WHERE status = 'completed'
                  AND DATE(created_at) BETWEEN p_tgl_mulai AND p_tgl_selesai
            )
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE PROCEDURE proses_checkout(IN p_order_id BIGINT UNSIGNED)
            BEGIN
                DECLARE v_user_id BIGINT UNSIGNED DEFAULT NULL;
                DECLARE v_cart_count INT DEFAULT 0;
                DECLARE v_updated_count INT DEFAULT 0;

                START TRANSACTION;
                SELECT user_id INTO v_user_id
                FROM transactions WHERE id = p_order_id FOR UPDATE;

                IF v_user_id IS NULL THEN
                    ROLLBACK;
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pesanan atau pemilik keranjang tidak ditemukan.';
                END IF;

                SELECT COUNT(*) INTO v_cart_count FROM carts WHERE user_id = v_user_id;
                IF v_cart_count = 0 THEN
                    ROLLBACK;
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Keranjang kosong.';
                END IF;

                IF EXISTS (
                    SELECT 1 FROM carts c
                    JOIN products p ON p.id = c.product_id
                    WHERE c.user_id = v_user_id AND p.stock < c.quantity
                ) THEN
                    ROLLBACK;
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stok tidak mencukupi / tidak boleh minus!';
                END IF;

                INSERT INTO transaction_details
                    (transaction_id, product_id, product_name, unit, quantity, unit_price, purchase_price, subtotal, created_at, updated_at)
                SELECT p_order_id, p.id, p.name, p.unit, c.quantity, p.selling_price, p.purchase_price,
                    c.quantity * p.selling_price, NOW(), NOW()
                FROM carts c JOIN products p ON p.id = c.product_id
                WHERE c.user_id = v_user_id;

                UPDATE products p
                JOIN carts c ON c.product_id = p.id
                SET p.stock = p.stock - c.quantity
                WHERE c.user_id = v_user_id AND p.stock >= c.quantity;
                SET v_updated_count = ROW_COUNT();

                IF v_updated_count <> v_cart_count THEN
                    ROLLBACK;
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stok tidak mencukupi / tidak boleh minus!';
                END IF;

                UPDATE transactions
                SET subtotal = (SELECT COALESCE(SUM(subtotal), 0) FROM transaction_details WHERE transaction_id = p_order_id),
                    total = (SELECT COALESCE(SUM(subtotal), 0) FROM transaction_details WHERE transaction_id = p_order_id),
                    total_cost = (SELECT COALESCE(SUM(purchase_price * quantity), 0) FROM transaction_details WHERE transaction_id = p_order_id),
                    status = 'pending_pickup'
                WHERE id = p_order_id;

                DELETE FROM carts WHERE user_id = v_user_id;
                COMMIT;
            END
        SQL);

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

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER before_products_update
            BEFORE UPDATE ON products
            FOR EACH ROW
            BEGIN
                IF NEW.stock < 0 THEN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'Stok tidak mencukupi / tidak boleh minus!';
                END IF;
            END
        SQL);

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
    }
};
