-- ============================================================
-- Hani ERP - Full Database Migration (v1.x → v2.0.1)
-- ============================================================
-- Idempotent: safe to run multiple times on any database state.
-- Compatible with MySQL 5.7+ and MariaDB 10.x.

USE `u905425928_hani`;
SET NAMES utf8mb4;
-- No IF NOT EXISTS for columns/indexes (unsupported in MySQL 5.x).
-- Uses: DROP INDEX IF EXISTS + CREATE INDEX for indexes,
--       INFORMATION_SCHEMA prepared statements for columns & FKs.
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
START TRANSACTION;

-- ============================================================
-- 1. BASE TABLES
-- ============================================================

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `branch_id` int(11) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `branches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `warehouses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `address` text DEFAULT NULL,
  `branch_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `units` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `barcode` varchar(50) NOT NULL,
  `name` varchar(200) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `purchase_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `selling_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `wholesale_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `special_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `barcode` (`barcode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `area` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `sales_rep_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `purchase_invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) NOT NULL,
  `date` date NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `payment_type` enum('cash','credit','vodafone','instapay') NOT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_number` (`invoice_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `purchase_invoice_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` decimal(10,3) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total` decimal(10,3) GENERATED ALWAYS AS (quantity * unit_price) STORED,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sales_invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) NOT NULL,
  `date` date NOT NULL,
  `customer_id` int(11) NOT NULL,
  `payment_type` enum('cash','installment','credit','vodafone','instapay','bank') NOT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `paid_upfront` decimal(10,2) DEFAULT 0.00,
  `remaining_installment` decimal(10,2) DEFAULT 0.00,
  `warehouse_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_number` (`invoice_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sales_invoice_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` decimal(10,3) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total` decimal(10,3) GENERATED ALWAYS AS (quantity * unit_price) STORED,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `installments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sales_invoice_id` int(11) NOT NULL,
  `installment_number` int(11) NOT NULL,
  `due_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','partial','paid') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `installment_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `installment_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `received_by` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expense_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `category_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expense_vouchers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `voucher_number` varchar(50) NOT NULL,
  `expense_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_type` enum('cash','vodafone','instapay','bank') NOT NULL,
  `branch_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `date` date NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `voucher_number` (`voucher_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `company_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `company_name` varchar(200) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `facebook` varchar(200) DEFAULT NULL,
  `instagram` varchar(200) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_data` text DEFAULT NULL,
  `new_data` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `return_invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) NOT NULL,
  `date` date NOT NULL,
  `customer_id` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_number` (`invoice_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `return_invoice_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `return_invoice_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` decimal(10,3) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total` decimal(10,3) GENERATED ALWAYS AS (quantity * unit_price) STORED,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. STOCK & SALES REP TABLES
-- ============================================================

CREATE TABLE IF NOT EXISTS `stock_movements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `type` enum('in','out') NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `reference` varchar(50) DEFAULT NULL,
  `reference_id` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `current_stock` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `quantity` decimal(10,3) NOT NULL DEFAULT '0.000',
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_product_warehouse` (`product_id`,`warehouse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `warehouse_transfers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transfer_number` varchar(50) NOT NULL,
  `from_warehouse_id` int(11) NOT NULL,
  `to_warehouse_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `transfer_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transfer_number` (`transfer_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sales_rep_stock` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sales_rep_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT '0.00',
  `assigned_from_warehouse_id` int(11) NOT NULL,
  `assigned_date` date NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sales_rep_product` (`sales_rep_id`,`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sales_rep_sales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sales_rep_stock_id` int(11) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `sale_date` date NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `price_type` enum('selling','wholesale','special') NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sales_rep_return` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sales_rep_stock_id` int(11) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `return_to_warehouse_id` int(11) NOT NULL,
  `return_date` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. OPENING BALANCE TABLES
-- ============================================================

CREATE TABLE IF NOT EXISTS `product_opening_balance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT '0.00',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total` decimal(10,2) GENERATED ALWAYS AS (quantity * price) STORED,
  `date` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `supplier_opening_balance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `customer_opening_balance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `installment_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','partial','paid') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `customer_opening_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `opening_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `received_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `opening_id` (`opening_id`),
  KEY `received_by` (`received_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. PAYMENTS TABLES
-- ============================================================

CREATE TABLE IF NOT EXISTS `supplier_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_type` enum('cash','vodafone','instapay','bank') DEFAULT 'cash',
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. COMMISSION TABLES
-- ============================================================

CREATE TABLE IF NOT EXISTS `sales_commissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sale_id` INT NOT NULL,
  `sale_item_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `quantity` DECIMAL(10,3) NOT NULL,
  `commission_amount_per_unit` DECIMAL(10,2) NOT NULL,
  `total_commission` DECIMAL(10,2) NOT NULL,
  `commission_date` DATE NOT NULL,
  `status` ENUM('pending','paid','cancelled') DEFAULT 'pending',
  `paid_at` TIMESTAMP NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `collection_commissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `installment_id` INT NOT NULL,
  `installment_payment_id` INT NULL,
  `sale_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `customer_id` INT NOT NULL,
  `customer_name` VARCHAR(255) NOT NULL,
  `collected_amount` DECIMAL(10,2) NOT NULL,
  `commission_rate` DECIMAL(5,2) NOT NULL,
  `commission_amount` DECIMAL(10,2) NOT NULL,
  `collection_date` DATE NOT NULL,
  `status` ENUM('pending','paid','cancelled') DEFAULT 'pending',
  `paid_at` TIMESTAMP NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `commission_payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `payment_date` DATE NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_type` ENUM('cash','transfer','vodafone','instapay') DEFAULT 'cash',
  `period_from` DATE NOT NULL,
  `period_to` DATE NOT NULL,
  `notes` TEXT NULL,
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. SECURITY & MIGRATION TABLES
-- ============================================================

CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `username` varchar(100) NOT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `version_migrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `version` varchar(20) NOT NULL,
  `zip_file` varchar(255) NOT NULL,
  `zip_hash` varchar(64) DEFAULT NULL,
  `status` enum('pending','running','completed','failed','rolled_back') NOT NULL DEFAULT 'pending',
  `backup_path` varchar(255) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `executed_by` int(11) DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. ADD MISSING COLUMNS (idempotent via INFORMATION_SCHEMA)
-- ============================================================

SET @db = DATABASE();

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_schema = @db AND table_name = 'users' AND column_name = 'last_login');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE users ADD COLUMN last_login datetime DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_schema = @db AND table_name = 'users' AND column_name = 'collection_commission_rate');
SET @sql = IF(@col_exists = 0, "ALTER TABLE users ADD COLUMN collection_commission_rate DECIMAL(5,2) DEFAULT 0.00 COMMENT 'نسبة عمولة التحصيل % من الأقساط المحصّلة'", 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_schema = @db AND table_name = 'products' AND column_name = 'commission_amount');
SET @sql = IF(@col_exists = 0, "ALTER TABLE products ADD COLUMN commission_amount DECIMAL(10,2) DEFAULT 0.00 COMMENT 'مبلغ العمولة الثابت للمندوب عند بيع قطعة واحدة'", 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_schema = @db AND table_name = 'sales_invoices' AND column_name = 'sales_rep_id');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE sales_invoices ADD COLUMN sales_rep_id INT NULL AFTER customer_id', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_schema = @db AND table_name = 'sales_invoices' AND column_name = 'branch_id');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE sales_invoices ADD COLUMN branch_id int(11) DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_schema = @db AND table_name = 'purchase_invoices' AND column_name = 'warehouse_id');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE purchase_invoices ADD COLUMN warehouse_id INT NULL AFTER supplier_id', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_schema = @db AND table_name = 'installment_payments' AND column_name = 'payment_method');
SET @sql = IF(@col_exists = 0, "ALTER TABLE installment_payments ADD COLUMN payment_method varchar(50) DEFAULT 'cash'", 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- customer_opening_balance columns
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_schema = @db AND table_name = 'customer_opening_balance' AND column_name = 'paid_amount');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE customer_opening_balance ADD COLUMN paid_amount decimal(10,2) NOT NULL DEFAULT 0.00 AFTER amount', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_schema = @db AND table_name = 'customer_opening_balance' AND column_name = 'status');
SET @sql = IF(@col_exists = 0, "ALTER TABLE customer_opening_balance ADD COLUMN status enum('pending','partial','paid') NOT NULL DEFAULT 'pending' AFTER paid_amount", 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- company_settings columns
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_schema = @db AND table_name = 'company_settings' AND column_name = 'key');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE company_settings ADD COLUMN `key` varchar(100) NOT NULL DEFAULT \'\' AFTER `id`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_schema = @db AND table_name = 'company_settings' AND column_name = 'value');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE company_settings ADD COLUMN `value` text DEFAULT NULL AFTER `key`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_schema = @db AND table_name = 'company_settings' AND column_name = 'whatsapp');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE company_settings ADD COLUMN `whatsapp` varchar(20) DEFAULT NULL AFTER `address`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_schema = @db AND table_name = 'company_settings' AND column_name = 'facebook');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE company_settings ADD COLUMN `facebook` varchar(200) DEFAULT NULL AFTER `whatsapp`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_schema = @db AND table_name = 'company_settings' AND column_name = 'instagram');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE company_settings ADD COLUMN `instagram` varchar(200) DEFAULT NULL AFTER `facebook`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_schema = @db AND table_name = 'company_settings' AND column_name = 'logo_path');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE company_settings ADD COLUMN `logo_path` varchar(255) DEFAULT NULL AFTER `instagram`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- also add UNIQUE KEY on `key` for existing tables
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'company_settings' AND index_name = 'key');
SET @sql = IF(@idx_exists = 0, 'ALTER TABLE company_settings ADD UNIQUE INDEX `key` (`key`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- company_settings extra columns
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_schema = @db AND table_name = 'company_settings' AND column_name = 'auto_updates');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE company_settings ADD COLUMN auto_updates tinyint(1) NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_schema = @db AND table_name = 'company_settings' AND column_name = 'auto_check_updates');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE company_settings ADD COLUMN auto_check_updates tinyint(1) NOT NULL DEFAULT 1', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_schema = @db AND table_name = 'company_settings' AND column_name = 'notify_admin_update');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE company_settings ADD COLUMN notify_admin_update tinyint(1) NOT NULL DEFAULT 1', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_schema = @db AND table_name = 'company_settings' AND column_name = 'max_backups');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE company_settings ADD COLUMN max_backups int(11) NOT NULL DEFAULT 5', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_schema = @db AND table_name = 'company_settings' AND column_name = 'max_update_size');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE company_settings ADD COLUMN max_update_size int(11) NOT NULL DEFAULT 512', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_schema = @db AND table_name = 'company_settings' AND column_name = 'app_version');
SET @sql = IF(@col_exists = 0, "ALTER TABLE company_settings ADD COLUMN app_version varchar(20) NOT NULL DEFAULT '1.0.0'", 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_schema = @db AND table_name = 'company_settings' AND column_name = 'last_update_at');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE company_settings ADD COLUMN last_update_at datetime DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_schema = @db AND table_name = 'company_settings' AND column_name = 'last_check_at');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE company_settings ADD COLUMN last_check_at datetime DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_schema = @db AND table_name = 'company_settings' AND column_name = 'backup_count');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE company_settings ADD COLUMN backup_count int(11) NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- 8. INDEXES (idempotent via INFORMATION_SCHEMA)  (MySQL 5.7+)
-- ============================================================

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'users' AND index_name = 'idx_branch');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_branch ON users(branch_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'branches' AND index_name = 'manager_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX manager_id ON branches(manager_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'warehouses' AND index_name = 'branch_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX branch_id ON warehouses(branch_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'products' AND index_name = 'unit_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX unit_id ON products(unit_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'suppliers' AND index_name = 'name');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX name ON suppliers(name)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'customers' AND index_name = 'sales_rep_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX sales_rep_id ON customers(sales_rep_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'purchase_invoices' AND index_name = 'supplier_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX supplier_id ON purchase_invoices(supplier_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'purchase_invoices' AND index_name = 'created_by');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX created_by ON purchase_invoices(created_by)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'purchase_invoices' AND index_name = 'idx_supplier_date');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_supplier_date ON purchase_invoices(supplier_id, `date`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'purchase_invoice_items' AND index_name = 'invoice_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX invoice_id ON purchase_invoice_items(invoice_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'purchase_invoice_items' AND index_name = 'product_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX product_id ON purchase_invoice_items(product_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'purchase_invoice_items' AND index_name = 'idx_purchase_product');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_purchase_product ON purchase_invoice_items(invoice_id, product_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'sales_invoices' AND index_name = 'customer_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX customer_id ON sales_invoices(customer_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'sales_invoices' AND index_name = 'warehouse_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX warehouse_id ON sales_invoices(warehouse_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'sales_invoices' AND index_name = 'created_by');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX created_by ON sales_invoices(created_by)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'sales_invoices' AND index_name = 'idx_customer_date');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_customer_date ON sales_invoices(customer_id, `date`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'sales_invoices' AND index_name = 'idx_branch_si');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_branch_si ON sales_invoices(branch_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'sales_invoices' AND index_name = 'idx_branch_date');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_branch_date ON sales_invoices(branch_id, `date`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'sales_invoice_items' AND index_name = 'invoice_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX invoice_id ON sales_invoice_items(invoice_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'sales_invoice_items' AND index_name = 'product_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX product_id ON sales_invoice_items(product_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'sales_invoice_items' AND index_name = 'idx_invoice_product');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_invoice_product ON sales_invoice_items(invoice_id, product_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'installments' AND index_name = 'sales_invoice_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX sales_invoice_id ON installments(sales_invoice_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'installments' AND index_name = 'idx_status_due');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_status_due ON installments(status, due_date)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'installment_payments' AND index_name = 'installment_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX installment_id ON installment_payments(installment_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'installment_payments' AND index_name = 'received_by');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX received_by ON installment_payments(received_by)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'expenses' AND index_name = 'category_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX category_id ON expenses(category_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'expenses' AND index_name = 'branch_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX branch_id ON expenses(branch_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'expense_vouchers' AND index_name = 'expense_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX expense_id ON expense_vouchers(expense_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'expense_vouchers' AND index_name = 'branch_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX branch_id ON expense_vouchers(branch_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'expense_vouchers' AND index_name = 'created_by');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX created_by ON expense_vouchers(created_by)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'audit_log' AND index_name = 'user_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX user_id ON audit_log(user_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'audit_log' AND index_name = 'idx_user_action_time');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_user_action_time ON audit_log(user_id, `action`, created_at)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'audit_log' AND index_name = 'idx_table_record');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_table_record ON audit_log(table_name, record_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'stock_movements' AND index_name = 'product_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX product_id ON stock_movements(product_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'stock_movements' AND index_name = 'warehouse_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX warehouse_id ON stock_movements(warehouse_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'stock_movements' AND index_name = 'idx_product_warehouse');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_product_warehouse ON stock_movements(product_id, warehouse_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'stock_movements' AND index_name = 'idx_reference');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_reference ON stock_movements(reference, reference_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'stock_movements' AND index_name = 'idx_created');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_created ON stock_movements(created_at)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'supplier_payments' AND index_name = 'supplier_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX supplier_id ON supplier_payments(supplier_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'supplier_payments' AND index_name = 'created_by');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX created_by ON supplier_payments(created_by)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'warehouse_transfers' AND index_name = 'from_warehouse_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX from_warehouse_id ON warehouse_transfers(from_warehouse_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'warehouse_transfers' AND index_name = 'to_warehouse_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX to_warehouse_id ON warehouse_transfers(to_warehouse_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'warehouse_transfers' AND index_name = 'product_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX product_id ON warehouse_transfers(product_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'warehouse_transfers' AND index_name = 'created_by');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX created_by ON warehouse_transfers(created_by)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'sales_rep_stock' AND index_name = 'sales_rep_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX sales_rep_id ON sales_rep_stock(sales_rep_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'sales_rep_stock' AND index_name = 'product_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX product_id ON sales_rep_stock(product_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'sales_rep_stock' AND index_name = 'assigned_from_warehouse_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX assigned_from_warehouse_id ON sales_rep_stock(assigned_from_warehouse_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'sales_rep_sales' AND index_name = 'sales_rep_stock_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX sales_rep_stock_id ON sales_rep_sales(sales_rep_stock_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'sales_rep_sales' AND index_name = 'customer_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX customer_id ON sales_rep_sales(customer_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'sales_rep_return' AND index_name = 'sales_rep_stock_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX sales_rep_stock_id ON sales_rep_return(sales_rep_stock_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'sales_rep_return' AND index_name = 'return_to_warehouse_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX return_to_warehouse_id ON sales_rep_return(return_to_warehouse_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'product_opening_balance' AND index_name = 'product_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX product_id ON product_opening_balance(product_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'product_opening_balance' AND index_name = 'warehouse_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX warehouse_id ON product_opening_balance(warehouse_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'supplier_opening_balance' AND index_name = 'supplier_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX supplier_id ON supplier_opening_balance(supplier_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'customer_opening_balance' AND index_name = 'customer_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX customer_id ON customer_opening_balance(customer_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'sales_commissions' AND index_name = 'idx_user');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_user ON sales_commissions(user_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'sales_commissions' AND index_name = 'idx_sale');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_sale ON sales_commissions(sale_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'sales_commissions' AND index_name = 'idx_date');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_date ON sales_commissions(commission_date)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'sales_commissions' AND index_name = 'idx_status');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_status ON sales_commissions(status)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'collection_commissions' AND index_name = 'idx_user');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_user ON collection_commissions(user_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'collection_commissions' AND index_name = 'idx_installment');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_installment ON collection_commissions(installment_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'collection_commissions' AND index_name = 'idx_date');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_date ON collection_commissions(collection_date)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'collection_commissions' AND index_name = 'idx_status');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_status ON collection_commissions(status)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'commission_payments' AND index_name = 'idx_user');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_user ON commission_payments(user_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'commission_payments' AND index_name = 'idx_date');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_date ON commission_payments(payment_date)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'current_stock' AND index_name = 'idx_product');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_product ON current_stock(product_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'current_stock' AND index_name = 'idx_warehouse');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_warehouse ON current_stock(warehouse_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'login_attempts' AND index_name = 'idx_ip');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_ip ON login_attempts(ip_address)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'login_attempts' AND index_name = 'idx_time');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_time ON login_attempts(attempted_at)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'version_migrations' AND index_name = 'executed_by');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX executed_by ON version_migrations(executed_by)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'version_migrations' AND index_name = 'idx_status');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_status ON version_migrations(status)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'sales_commissions' AND index_name = 'idx_sales_commissions_user_status');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_sales_commissions_user_status ON sales_commissions(user_id, status)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'collection_commissions' AND index_name = 'idx_collection_commissions_user_status');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_collection_commissions_user_status ON collection_commissions(user_id, status)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'purchase_invoices' AND index_name = 'idx_purchase_invoices_warehouse');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_purchase_invoices_warehouse ON purchase_invoices(warehouse_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- 9. DEFAULT DATA (inserted once)
-- ============================================================

INSERT IGNORE INTO `units` (`id`, `name`) VALUES
(1, 'قطعة'),
(2, 'كيلو'),
(3, 'علبة'),
(4, 'كرتونة');

INSERT IGNORE INTO `users` (`id`, `full_name`, `username`, `password`, `role`) VALUES
(1, 'المدير العام', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

INSERT IGNORE INTO `company_settings` (`id`, `key`, `company_name`, `phone`, `address`) VALUES
(1, '', 'شركتي', '01000000000', 'العنوان');

UPDATE `company_settings` SET `app_version` = '1.0.0' WHERE `id` = 1;

-- ============================================================
-- 10. RBAC SYSTEM
-- ============================================================

CREATE TABLE IF NOT EXISTS `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `module` varchar(50) NOT NULL,
  `action` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `idx_module` (`module`),
  KEY `idx_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `role_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_role_permission` (`role_id`,`permission_id`),
  KEY `idx_permission` (`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `type` enum('grant','deny') NOT NULL DEFAULT 'grant',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_permission` (`user_id`,`permission_id`),
  KEY `idx_permission` (`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_branches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_branch` (`user_id`,`branch_id`),
  KEY `idx_branch` (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `permission_audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `changed_by` int(11) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `old_role_id` int(11) DEFAULT NULL,
  `new_role_id` int(11) DEFAULT NULL,
  `added_permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `removed_permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add role_id and can_view_all_branches to users
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_schema = @db AND table_name = 'users' AND column_name = 'role_id');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE users ADD COLUMN role_id int(11) DEFAULT NULL AFTER branch_id', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_schema = @db AND table_name = 'users' AND column_name = 'can_view_all_branches');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE users ADD COLUMN can_view_all_branches tinyint(1) NOT NULL DEFAULT 0 AFTER role_id', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'users' AND index_name = 'idx_role');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_role ON users(role_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- 10b. DEFAULT RBAC DATA
-- ============================================================

INSERT IGNORE INTO `roles` (`id`, `name`, `display_name`, `description`, `is_system`) VALUES
(1, 'admin', 'مدير النظام', 'صلاحيات كاملة - يرى جميع الفروع', 1),
(2, 'branch_manager', 'مدير فرع', 'يدير فرعه بالكامل - مبيعات، مشتريات، عملاء، موردين، موظفين، تقارير', 1),
(3, 'sales_rep', 'مندوب مبيعات', 'إنشاء فواتير بيع وأقساط، عهدة، عمولات', 1),
(4, 'collector', 'محصل', 'تحصيل الأقساط، تسجيل المقبوضات', 1);

INSERT IGNORE INTO `permissions` (`id`, `name`, `display_name`, `module`, `action`) VALUES
(1, 'users.view', 'عرض المستخدمين', 'users', 'view'),
(2, 'users.create', 'إنشاء مستخدم', 'users', 'create'),
(3, 'users.edit', 'تعديل مستخدم', 'users', 'edit'),
(4, 'users.delete', 'حذف مستخدم', 'users', 'delete'),
(5, 'branches.view', 'عرض الفروع', 'branches', 'view'),
(6, 'branches.create', 'إنشاء فرع', 'branches', 'create'),
(7, 'branches.edit', 'تعديل فرع', 'branches', 'edit'),
(8, 'branches.delete', 'حذف فرع', 'branches', 'delete'),
(9, 'products.view', 'عرض المواد', 'products', 'view'),
(10, 'products.create', 'إنشاء مادة', 'products', 'create'),
(11, 'products.edit', 'تعديل مادة', 'products', 'edit'),
(12, 'products.delete', 'حذف مادة', 'products', 'delete'),
(13, 'warehouses.view', 'عرض المخازن', 'warehouses', 'view'),
(14, 'warehouses.create', 'إنشاء مخزن', 'warehouses', 'create'),
(15, 'warehouses.edit', 'تعديل مخزن', 'warehouses', 'edit'),
(16, 'warehouses.delete', 'حذف مخزن', 'warehouses', 'delete'),
(17, 'units.view', 'عرض الوحدات', 'units', 'view'),
(18, 'units.create', 'إنشاء وحدة', 'units', 'create'),
(19, 'units.edit', 'تعديل وحدة', 'units', 'edit'),
(20, 'units.delete', 'حذف وحدة', 'units', 'delete'),
(21, 'suppliers.view', 'عرض الموردين', 'suppliers', 'view'),
(22, 'suppliers.create', 'إنشاء مورد', 'suppliers', 'create'),
(23, 'suppliers.edit', 'تعديل مورد', 'suppliers', 'edit'),
(24, 'suppliers.delete', 'حذف مورد', 'suppliers', 'delete'),
(25, 'purchases.view', 'عرض المشتريات', 'purchases', 'view'),
(26, 'purchases.create', 'إنشاء فاتورة مشتريات', 'purchases', 'create'),
(27, 'purchases.edit', 'تعديل فاتورة مشتريات', 'purchases', 'edit'),
(28, 'customers.view', 'عرض العملاء', 'customers', 'view'),
(29, 'customers.create', 'إنشاء عميل', 'customers', 'create'),
(30, 'customers.edit', 'تعديل عميل', 'customers', 'edit'),
(31, 'customers.delete', 'حذف عميل', 'customers', 'delete'),
(32, 'sales.view', 'عرض فواتير البيع', 'sales', 'view'),
(33, 'sales.create', 'إنشاء فاتورة بيع', 'sales', 'create'),
(34, 'sales.edit', 'تعديل فاتورة بيع', 'sales', 'edit'),
(35, 'sales.delete', 'حذف فاتورة بيع', 'sales', 'delete'),
(36, 'installments.view', 'عرض الأقساط', 'installments', 'view'),
(37, 'installments.create', 'إنشاء قسط', 'installments', 'create'),
(38, 'installments.collect', 'تحصيل قسط', 'installments', 'collect'),
(39, 'installments.edit', 'تعديل قسط', 'installments', 'edit'),
(40, 'installments.delete', 'حذف قسط', 'installments', 'delete'),
(41, 'payments.view', 'عرض المقبوضات', 'payments', 'view'),
(42, 'payments.create', 'تسجيل مقبوض', 'payments', 'create'),
(43, 'expenses.view', 'عرض المصروفات', 'expenses', 'view'),
(44, 'expenses.create', 'إنشاء مصروف', 'expenses', 'create'),
(45, 'expenses.edit', 'تعديل مصروف', 'expenses', 'edit'),
(46, 'salesrep.view', 'عرض عهد المندوبين', 'salesrep', 'view'),
(47, 'salesrep.assign', 'إسناد عهدة', 'salesrep', 'assign'),
(48, 'salesrep.record_sale', 'تسجيل بيع من عهدة', 'salesrep', 'record_sale'),
(49, 'salesrep.return', 'استرداد عهدة', 'salesrep', 'return'),
(50, 'commissions.view', 'عرض العمولات', 'commissions', 'view'),
(51, 'commissions.pay', 'دفع عمولة', 'commissions', 'pay'),
(52, 'reports.view', 'عرض التقارير', 'reports', 'view'),
(53, 'settings.view', 'عرض الإعدادات', 'settings', 'view'),
(54, 'settings.edit', 'تعديل الإعدادات', 'settings', 'edit'),
(55, 'updates.view', 'عرض التحديثات', 'updates', 'view'),
(56, 'updates.execute', 'تنفيذ التحديثات', 'updates', 'execute');

-- Admin: all permissions
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, id FROM permissions;

-- Branch manager: all except certain system functions
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 2, id FROM permissions
WHERE name NOT IN ('branches.create','branches.edit','branches.delete','settings.edit','updates.execute');

-- Sales rep
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 3, id FROM permissions
WHERE name IN ('products.view','products.create','customers.view','customers.create','sales.view','sales.create','installments.view','installments.collect','salesrep.view','salesrep.record_sale','commissions.view','reports.view');

-- Collector
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 4, id FROM permissions
WHERE name IN ('customers.view','customers.search','installments.view','installments.collect','payments.view','payments.create','commissions.view','reports.view');

-- Migrate old role system to new RBAC
UPDATE users u JOIN roles r ON (u.role = 'admin' AND r.name = 'admin') SET u.role_id = r.id WHERE u.role_id IS NULL AND u.role = 'admin';
UPDATE users u JOIN roles r ON (u.role = 'user' AND r.name = 'sales_rep') SET u.role_id = r.id WHERE u.role_id IS NULL AND u.role = 'user';

-- ============================================================
-- 11. FOREIGN KEY CONSTRAINTS (via INFORMATION_SCHEMA checks)
-- ============================================================

SET @fk_name = 'fk_users_branch';
SET @exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = @db AND TABLE_NAME = 'users' AND CONSTRAINT_NAME = @fk_name);
SET @sql = IF(@exists = 0, 'ALTER TABLE users ADD CONSTRAINT fk_users_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_name = 'fk_branches_manager';
SET @exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = @db AND TABLE_NAME = 'branches' AND CONSTRAINT_NAME = @fk_name);
SET @sql = IF(@exists = 0, 'ALTER TABLE branches ADD CONSTRAINT fk_branches_manager FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_name = 'fk_warehouses_branch';
SET @exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = @db AND TABLE_NAME = 'warehouses' AND CONSTRAINT_NAME = @fk_name);
SET @sql = IF(@exists = 0, 'ALTER TABLE warehouses ADD CONSTRAINT fk_warehouses_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_name = 'fk_products_unit';
SET @exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = @db AND TABLE_NAME = 'products' AND CONSTRAINT_NAME = @fk_name);
SET @sql = IF(@exists = 0, 'ALTER TABLE products ADD CONSTRAINT fk_products_unit FOREIGN KEY (unit_id) REFERENCES units(id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_name = 'fk_pob_product';
SET @exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = @db AND TABLE_NAME = 'product_opening_balance' AND CONSTRAINT_NAME = @fk_name);
SET @sql = IF(@exists = 0, 'ALTER TABLE product_opening_balance ADD CONSTRAINT fk_pob_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_name = 'fk_pob_warehouse';
SET @exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = @db AND TABLE_NAME = 'product_opening_balance' AND CONSTRAINT_NAME = @fk_name);
SET @sql = IF(@exists = 0, 'ALTER TABLE product_opening_balance ADD CONSTRAINT fk_pob_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_name = 'fk_sob_supplier';
SET @exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = @db AND TABLE_NAME = 'supplier_opening_balance' AND CONSTRAINT_NAME = @fk_name);
SET @sql = IF(@exists = 0, 'ALTER TABLE supplier_opening_balance ADD CONSTRAINT fk_sob_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_name = 'fk_pi_supplier';
SET @exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = @db AND TABLE_NAME = 'purchase_invoices' AND CONSTRAINT_NAME = @fk_name);
SET @sql = IF(@exists = 0, 'ALTER TABLE purchase_invoices ADD CONSTRAINT fk_pi_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_name = 'fk_pi_user';
SET @exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = @db AND TABLE_NAME = 'purchase_invoices' AND CONSTRAINT_NAME = @fk_name);
SET @sql = IF(@exists = 0, 'ALTER TABLE purchase_invoices ADD CONSTRAINT fk_pi_user FOREIGN KEY (created_by) REFERENCES users(id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_name = 'fk_pii_invoice';
SET @exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = @db AND TABLE_NAME = 'purchase_invoice_items' AND CONSTRAINT_NAME = @fk_name);
SET @sql = IF(@exists = 0, 'ALTER TABLE purchase_invoice_items ADD CONSTRAINT fk_pii_invoice FOREIGN KEY (invoice_id) REFERENCES purchase_invoices(id) ON DELETE CASCADE', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_name = 'fk_pii_product';
SET @exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = @db AND TABLE_NAME = 'purchase_invoice_items' AND CONSTRAINT_NAME = @fk_name);
SET @sql = IF(@exists = 0, 'ALTER TABLE purchase_invoice_items ADD CONSTRAINT fk_pii_product FOREIGN KEY (product_id) REFERENCES products(id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_name = 'fk_customers_rep';
SET @exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = @db AND TABLE_NAME = 'customers' AND CONSTRAINT_NAME = @fk_name);
SET @sql = IF(@exists = 0, 'ALTER TABLE customers ADD CONSTRAINT fk_customers_rep FOREIGN KEY (sales_rep_id) REFERENCES users(id) ON DELETE SET NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_name = 'fk_cob_customer';
SET @exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = @db AND TABLE_NAME = 'customer_opening_balance' AND CONSTRAINT_NAME = @fk_name);
SET @sql = IF(@exists = 0, 'ALTER TABLE customer_opening_balance ADD CONSTRAINT fk_cob_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_name = 'fk_si_customer';
SET @exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = @db AND TABLE_NAME = 'sales_invoices' AND CONSTRAINT_NAME = @fk_name);
SET @sql = IF(@exists = 0, 'ALTER TABLE sales_invoices ADD CONSTRAINT fk_si_customer FOREIGN KEY (customer_id) REFERENCES customers(id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_name = 'fk_si_warehouse';
SET @exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = @db AND TABLE_NAME = 'sales_invoices' AND CONSTRAINT_NAME = @fk_name);
SET @sql = IF(@exists = 0, 'ALTER TABLE sales_invoices ADD CONSTRAINT fk_si_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_name = 'fk_si_user';
SET @exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = @db AND TABLE_NAME = 'sales_invoices' AND CONSTRAINT_NAME = @fk_name);
SET @sql = IF(@exists = 0, 'ALTER TABLE sales_invoices ADD CONSTRAINT fk_si_user FOREIGN KEY (created_by) REFERENCES users(id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_name = 'fk_sii_invoice';
SET @exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = @db AND TABLE_NAME = 'sales_invoice_items' AND CONSTRAINT_NAME = @fk_name);
SET @sql = IF(@exists = 0, 'ALTER TABLE sales_invoice_items ADD CONSTRAINT fk_sii_invoice FOREIGN KEY (invoice_id) REFERENCES sales_invoices(id) ON DELETE CASCADE', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_name = 'fk_sii_product';
SET @exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = @db AND TABLE_NAME = 'sales_invoice_items' AND CONSTRAINT_NAME = @fk_name);
SET @sql = IF(@exists = 0, 'ALTER TABLE sales_invoice_items ADD CONSTRAINT fk_sii_product FOREIGN KEY (product_id) REFERENCES products(id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_name = 'fk_installments_invoice';
SET @exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = @db AND TABLE_NAME = 'installments' AND CONSTRAINT_NAME = @fk_name);
SET @sql = IF(@exists = 0, 'ALTER TABLE installments ADD CONSTRAINT fk_installments_invoice FOREIGN KEY (sales_invoice_id) REFERENCES sales_invoices(id) ON DELETE CASCADE', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_name = 'fk_ip_installment';
SET @exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = @db AND TABLE_NAME = 'installment_payments' AND CONSTRAINT_NAME = @fk_name);
SET @sql = IF(@exists = 0, 'ALTER TABLE installment_payments ADD CONSTRAINT fk_ip_installment FOREIGN KEY (installment_id) REFERENCES installments(id) ON DELETE CASCADE', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_name = 'fk_ip_user';
SET @exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = @db AND TABLE_NAME = 'installment_payments' AND CONSTRAINT_NAME = @fk_name);
SET @sql = IF(@exists = 0, 'ALTER TABLE installment_payments ADD CONSTRAINT fk_ip_user FOREIGN KEY (received_by) REFERENCES users(id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- customer_opening_payments indexes & FK
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'customer_opening_payments' AND index_name = 'opening_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX opening_id ON customer_opening_payments(opening_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = @db AND table_name = 'customer_opening_payments' AND index_name = 'received_by');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX received_by ON customer_opening_payments(received_by)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_name = 'fk_cop_opening';
SET @exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = @db AND TABLE_NAME = 'customer_opening_payments' AND CONSTRAINT_NAME = @fk_name);
SET @sql = IF(@exists = 0, 'ALTER TABLE customer_opening_payments ADD CONSTRAINT fk_cop_opening FOREIGN KEY (opening_id) REFERENCES customer_opening_balance(id) ON DELETE CASCADE', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_name = 'fk_cop_user';
SET @exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = @db AND TABLE_NAME = 'customer_opening_payments' AND CONSTRAINT_NAME = @fk_name);
SET @sql = IF(@exists = 0, 'ALTER TABLE customer_opening_payments ADD CONSTRAINT fk_cop_user FOREIGN KEY (received_by) REFERENCES users(id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- 12. ADDITIONAL FKS from v2.0.1 (not covered above)
-- ============================================================

SET @fk_name = 'fk_purchase_invoices_warehouse';
SET @exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = @db AND TABLE_NAME = 'purchase_invoices' AND CONSTRAINT_NAME = @fk_name);
SET @sql = IF(@exists = 0, 'ALTER TABLE purchase_invoices ADD CONSTRAINT fk_purchase_invoices_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE SET NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_name = 'fk_sales_commissions_sale';
SET @exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = @db AND TABLE_NAME = 'sales_commissions' AND CONSTRAINT_NAME = @fk_name);
SET @sql = IF(@exists = 0, 'ALTER TABLE sales_commissions ADD CONSTRAINT fk_sales_commissions_sale FOREIGN KEY (sale_id) REFERENCES sales_invoices(id) ON DELETE CASCADE', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- 13. FIX STUCK MIGRATIONS
-- ============================================================

UPDATE version_migrations SET status = 'failed', completed_at = NOW() WHERE status = 'running';

-- ============================================================
-- 14. SAVE SCHEMA VERSION
-- ============================================================

INSERT INTO company_settings (`key`, `value`) VALUES ('schema_version', '2.0.1')
ON DUPLICATE KEY UPDATE `value` = '2.0.1';

COMMIT;
