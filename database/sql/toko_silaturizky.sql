-- MySQL 8 / MariaDB bootstrap for Toko Silaturizky.
-- Laravel installations should use `php artisan migrate --seed`; this file is
-- the equivalent standalone schema and routine setup for a fresh database.
CREATE DATABASE IF NOT EXISTS toko_silaturizky
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE toko_silaturizky;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(40) NULL,
    wa_number VARCHAR(40) NULL UNIQUE,
    alamat TEXT NULL,
    role VARCHAR(255) NOT NULL DEFAULT 'pembeli',
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    category_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    sku VARCHAR(255) NOT NULL UNIQUE,
    unit VARCHAR(30) NOT NULL DEFAULT 'pcs',
    purchase_price DECIMAL(15,2) NOT NULL DEFAULT 0,
    selling_price DECIMAL(15,2) NOT NULL DEFAULT 0,
    stock DECIMAL(12,3) NOT NULL DEFAULT 0,
    min_stock DECIMAL(12,3) NOT NULL DEFAULT 0,
    image_path VARCHAR(255) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT products_category_id_foreign FOREIGN KEY (category_id)
        REFERENCES categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS transactions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    handled_by BIGINT UNSIGNED NULL,
    transaction_number VARCHAR(255) NOT NULL UNIQUE,
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0,
    total DECIMAL(15,2) NOT NULL DEFAULT 0,
    total_cost DECIMAL(15,2) NOT NULL DEFAULT 0,
    amount_paid DECIMAL(15,2) NOT NULL DEFAULT 0,
    change_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    payment_method VARCHAR(40) NOT NULL DEFAULT 'cash',
    payment_status VARCHAR(30) NOT NULL DEFAULT 'paid',
    status VARCHAR(30) NOT NULL DEFAULT 'completed',
    completed_at TIMESTAMP NULL,
    notes TEXT NULL,
    customer_name VARCHAR(150) NULL,
    customer_phone VARCHAR(40) NULL,
    shipping_address TEXT NULL,
    pickup_note TEXT NULL,
    sales_channel VARCHAR(30) NOT NULL DEFAULT 'pos',
    delivery_type VARCHAR(20) NOT NULL DEFAULT 'pickup',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT transactions_user_id_foreign FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT transactions_handled_by_foreign FOREIGN KEY (handled_by)
        REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS transaction_details (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    transaction_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NULL,
    product_name VARCHAR(255) NOT NULL,
    unit VARCHAR(30) NOT NULL,
    quantity DECIMAL(12,3) NOT NULL,
    unit_price DECIMAL(15,2) NOT NULL,
    purchase_price DECIMAL(15,2) NOT NULL DEFAULT 0,
    subtotal DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT transaction_details_transaction_id_foreign FOREIGN KEY (transaction_id)
        REFERENCES transactions(id) ON DELETE CASCADE,
    CONSTRAINT transaction_details_product_id_foreign FOREIGN KEY (product_id)
        REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS stock_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    transaction_id BIGINT UNSIGNED NULL,
    type VARCHAR(30) NOT NULL,
    quantity_change DECIMAL(12,3) NOT NULL,
    stock_before DECIMAL(12,3) NOT NULL,
    stock_after DECIMAL(12,3) NOT NULL,
    reference VARCHAR(255) NULL UNIQUE,
    note TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT stock_logs_product_id_foreign FOREIGN KEY (product_id)
        REFERENCES products(id) ON DELETE SET NULL,
    CONSTRAINT stock_logs_user_id_foreign FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT stock_logs_transaction_id_foreign FOREIGN KEY (transaction_id)
        REFERENCES transactions(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    email VARCHAR(255) NOT NULL PRIMARY KEY,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(255) NOT NULL PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    payload LONGTEXT NOT NULL,
    last_activity INT NOT NULL,
    INDEX sessions_user_id_index (user_id),
    INDEX sessions_last_activity_index (last_activity)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cache (
    `key` VARCHAR(255) NOT NULL PRIMARY KEY,
    value MEDIUMTEXT NOT NULL,
    expiration INT NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cache_locks (
    `key` VARCHAR(255) NOT NULL PRIMARY KEY,
    owner VARCHAR(255) NOT NULL,
    expiration INT NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS jobs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    queue VARCHAR(255) NOT NULL,
    payload LONGTEXT NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL,
    reserved_at INT UNSIGNED NULL,
    available_at INT UNSIGNED NOT NULL,
    created_at INT UNSIGNED NOT NULL,
    INDEX jobs_queue_index (queue)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS job_batches (
    id VARCHAR(255) NOT NULL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    total_jobs INT NOT NULL,
    pending_jobs INT NOT NULL,
    failed_jobs INT NOT NULL,
    failed_job_ids LONGTEXT NOT NULL,
    options MEDIUMTEXT NULL,
    cancelled_at INT NULL,
    created_at INT NOT NULL,
    finished_at INT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS failed_jobs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(255) NOT NULL UNIQUE,
    connection TEXT NOT NULL,
    queue TEXT NOT NULL,
    payload LONGTEXT NOT NULL,
    exception LONGTEXT NOT NULL,
    failed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE OR REPLACE VIEW orders AS SELECT * FROM transactions;
CREATE OR REPLACE VIEW order_items AS SELECT * FROM transaction_details;
CREATE OR REPLACE VIEW view_laporan_penjualan_harian AS
SELECT
    DATE(created_at) AS tanggal,
    COUNT(id) AS total_transaksi,
    COALESCE(SUM(total), 0) AS omzet,
    COALESCE(SUM(total - total_cost), 0) AS laba_kotor
FROM transactions
WHERE status = 'completed'
GROUP BY DATE(created_at);

DROP FUNCTION IF EXISTS hitung_laba;
DROP PROCEDURE IF EXISTS laporan_harian;
DROP PROCEDURE IF EXISTS proses_checkout;
DROP TRIGGER IF EXISTS before_products_update;
DROP TRIGGER IF EXISTS after_products_update_stok;
DROP TRIGGER IF EXISTS after_order_items_insert;

DELIMITER $$

CREATE FUNCTION hitung_laba(p_jual DECIMAL(15,2), p_modal DECIMAL(15,2))
RETURNS DECIMAL(15,2) DETERMINISTIC
RETURN p_jual - p_modal$$

CREATE PROCEDURE laporan_harian()
BEGIN
    SELECT
        COALESCE((
            SELECT SUM(total) FROM transactions
            WHERE status = 'completed' AND DATE(created_at) = CURRENT_DATE()
        ), 0) AS omzet,
        COALESCE((
            SELECT SUM(hitung_laba(d.subtotal, d.purchase_price * d.quantity))
            FROM transaction_details d
            JOIN transactions t ON t.id = d.transaction_id
            WHERE t.status = 'completed' AND DATE(t.created_at) = CURRENT_DATE()
        ), 0) AS laba_bersih,
        (
            SELECT COUNT(*) FROM transactions
            WHERE status = 'completed' AND DATE(created_at) = CURRENT_DATE()
        ) AS total_transaksi;
END$$

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
END$$

CREATE TRIGGER before_products_update
BEFORE UPDATE ON products
FOR EACH ROW
BEGIN
    IF NEW.stock < 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Stok tidak mencukupi / tidak boleh minus!';
    END IF;
END$$

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
                COALESCE(@silaturizky_stock_type, IF(NEW.stock > OLD.stock, 'incoming', 'outgoing')),
                NEW.stock - OLD.stock,
                OLD.stock,
                NEW.stock,
                CONCAT('stock-update:', NEW.id, ':', UUID()),
                IF(NEW.stock > OLD.stock, 'Stok masuk', 'Stok keluar'),
                NOW(),
                NOW()
            );
    END IF;
END$$

DELIMITER ;
