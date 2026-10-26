<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            // Perbarui Stored Procedure proses_checkout agar tidak lagi bergantung pada tabel carts
            // dan tetap memenuhi kebutuhan transaksi database (START TRANSACTION, COMMIT, dan ROLLBACK).
            DB::unprepared('DROP PROCEDURE IF EXISTS proses_checkout');
            DB::unprepared(<<<'SQL'
                CREATE PROCEDURE proses_checkout(IN p_transaction_id BIGINT UNSIGNED)
                BEGIN
                    DECLARE v_status VARCHAR(50);
                    DECLARE v_item_count INT DEFAULT 0;
                    DECLARE v_insufficient_count INT DEFAULT 0;
                    DECLARE EXIT HANDLER FOR SQLEXCEPTION
                    BEGIN
                        ROLLBACK;
                        RESIGNAL;
                    END;

                    START TRANSACTION;

                    SELECT status INTO v_status
                    FROM transactions
                    WHERE id = p_transaction_id
                    FOR UPDATE;

                    IF v_status IS NULL THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Transaksi tidak ditemukan.';
                    END IF;

                    IF v_status = 'completed' THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Transaksi sudah berstatus selesai.';
                    END IF;

                    SELECT COUNT(*) INTO v_item_count
                    FROM transaction_details
                    WHERE transaction_id = p_transaction_id;

                    IF v_item_count = 0 THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Detail item transaksi kosong.';
                    END IF;

                    SELECT COUNT(*) INTO v_insufficient_count
                    FROM transaction_details td
                    JOIN products p ON p.id = td.product_id
                    WHERE td.transaction_id = p_transaction_id AND p.stock < td.quantity;

                    IF v_insufficient_count > 0 THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stok tidak mencukupi / tidak boleh minus!';
                    END IF;

                    UPDATE products p
                    JOIN transaction_details td ON td.product_id = p.id
                    SET p.stock = p.stock - td.quantity
                    WHERE td.transaction_id = p_transaction_id;

                    UPDATE transactions
                    SET status = 'completed',
                        payment_status = 'paid',
                        completed_at = COALESCE(completed_at, NOW()),
                        updated_at = NOW()
                    WHERE id = p_transaction_id;

                    COMMIT;
                END
            SQL);

            // Pasang database VIEW untuk kebutuhan pelaporan dan kompatibilitas query
            DB::unprepared('CREATE OR REPLACE VIEW orders AS SELECT * FROM transactions');
            DB::unprepared('CREATE OR REPLACE VIEW order_items AS SELECT * FROM transaction_details');
            DB::unprepared(<<<'SQL'
                CREATE OR REPLACE VIEW view_laporan_penjualan_harian AS
                SELECT
                    DATE(created_at) AS tanggal,
                    COUNT(id) AS total_transaksi,
                    COALESCE(SUM(total), 0) AS omzet,
                    COALESCE(SUM(total - total_cost), 0) AS laba_kotor
                FROM transactions
                WHERE status = 'completed'
                GROUP BY DATE(created_at)
            SQL);
        }

        // Hapus tabel carts yang merupakan fitur keranjang belanja online dan tidak lagi digunakan
        Schema::dropIfExists('carts');
    }

    public function down(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 12, 3)->default(1);
            $table->timestamps();
            $table->unique(['user_id', 'product_id']);
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::unprepared('DROP VIEW IF EXISTS view_laporan_penjualan_harian');
            DB::unprepared('DROP VIEW IF EXISTS order_items');
            DB::unprepared('DROP VIEW IF EXISTS orders');

            DB::unprepared('DROP PROCEDURE IF EXISTS proses_checkout');
            DB::unprepared(<<<'SQL'
                CREATE PROCEDURE proses_checkout(IN p_order_id BIGINT UNSIGNED)
                BEGIN
                    DECLARE v_user_id BIGINT UNSIGNED DEFAULT NULL;
                    DECLARE v_cart_count INT DEFAULT 0;
                    DECLARE v_updated_count INT DEFAULT 0;
                    DECLARE EXIT HANDLER FOR SQLEXCEPTION
                    BEGIN
                        ROLLBACK;
                        RESIGNAL;
                    END;

                    START TRANSACTION;
                    SELECT user_id INTO v_user_id
                    FROM transactions WHERE id = p_order_id FOR UPDATE;

                    IF v_user_id IS NULL THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pesanan atau pemilik keranjang tidak ditemukan.';
                    END IF;

                    SELECT COUNT(*) INTO v_cart_count FROM carts WHERE user_id = v_user_id;
                    IF v_cart_count = 0 THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Keranjang kosong.';
                    END IF;

                    IF EXISTS (
                        SELECT 1 FROM carts c
                        JOIN products p ON p.id = c.product_id
                        WHERE c.user_id = v_user_id AND p.stock < c.quantity
                    ) THEN
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
        }
    }
};
