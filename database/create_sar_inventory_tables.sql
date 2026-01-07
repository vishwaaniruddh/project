-- SAR Inventory Management System Database Schema
-- All tables use sar_inv_ prefix to avoid conflicts with existing inventory tables

-- ============================================================================
-- 1. WAREHOUSES TABLE
-- ============================================================================
CREATE TABLE IF NOT EXISTS `sar_inv_warehouses` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `code` VARCHAR(50) NOT NULL,
    `location` VARCHAR(255) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `capacity` DECIMAL(15,2) DEFAULT NULL,
    `status` ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    `company_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_warehouse_code_company` (`code`, `company_id`),
    INDEX `idx_company_id` (`company_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 2. PRODUCT CATEGORIES TABLE
-- ============================================================================
CREATE TABLE IF NOT EXISTS `sar_inv_product_categories` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `parent_id` INT DEFAULT NULL,
    `level` INT DEFAULT 0,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `company_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`parent_id`) REFERENCES `sar_inv_product_categories`(`id`) ON DELETE SET NULL,
    INDEX `idx_parent_id` (`parent_id`),
    INDEX `idx_company_id` (`company_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 3. PRODUCTS TABLE
-- ============================================================================
CREATE TABLE IF NOT EXISTS `sar_inv_products` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `sku` VARCHAR(100) NOT NULL,
    `category_id` INT DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `specifications` JSON DEFAULT NULL,
    `unit_of_measure` VARCHAR(50) DEFAULT NULL,
    `minimum_stock_level` INT DEFAULT 0,
    `status` ENUM('active', 'inactive', 'discontinued') DEFAULT 'active',
    `company_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_sku_company` (`sku`, `company_id`),
    FOREIGN KEY (`category_id`) REFERENCES `sar_inv_product_categories`(`id`) ON DELETE SET NULL,
    INDEX `idx_sku` (`sku`),
    INDEX `idx_category_id` (`category_id`),
    INDEX `idx_company_id` (`company_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_company_status` (`company_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 4. STOCK TABLE
-- ============================================================================
CREATE TABLE IF NOT EXISTS `sar_inv_stock` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `product_id` INT NOT NULL,
    `warehouse_id` INT NOT NULL,
    `quantity` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `reserved_quantity` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `version` INT NOT NULL DEFAULT 1,
    `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_product_warehouse` (`product_id`, `warehouse_id`),
    FOREIGN KEY (`product_id`) REFERENCES `sar_inv_products`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`warehouse_id`) REFERENCES `sar_inv_warehouses`(`id`) ON DELETE CASCADE,
    INDEX `idx_product_id` (`product_id`),
    INDEX `idx_warehouse_id` (`warehouse_id`),
    INDEX `idx_quantity` (`quantity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 5. STOCK ENTRIES TABLE
-- ============================================================================
CREATE TABLE IF NOT EXISTS `sar_inv_stock_entries` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `product_id` INT NOT NULL,
    `warehouse_id` INT NOT NULL,
    `quantity` DECIMAL(15,2) NOT NULL,
    `entry_type` ENUM('in', 'out') NOT NULL,
    `reference_type` VARCHAR(50) DEFAULT NULL,
    `reference_id` INT DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`product_id`) REFERENCES `sar_inv_products`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`warehouse_id`) REFERENCES `sar_inv_warehouses`(`id`) ON DELETE CASCADE,
    INDEX `idx_product_id` (`product_id`),
    INDEX `idx_warehouse_id` (`warehouse_id`),
    INDEX `idx_entry_type` (`entry_type`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_reference` (`reference_type`, `reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 6. DISPATCHES TABLE
-- ============================================================================
CREATE TABLE IF NOT EXISTS `sar_inv_dispatches` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `dispatch_number` VARCHAR(50) NOT NULL,
    `source_warehouse_id` INT NOT NULL,
    `destination_type` ENUM('warehouse', 'site', 'vendor', 'customer', 'other') NOT NULL,
    `destination_id` INT DEFAULT NULL,
    `destination_address` TEXT DEFAULT NULL,
    `status` ENUM('pending', 'approved', 'shipped', 'in_transit', 'delivered', 'cancelled') DEFAULT 'pending',
    `dispatch_date` DATE DEFAULT NULL,
    `received_date` DATE DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_dispatch_number` (`dispatch_number`),
    FOREIGN KEY (`source_warehouse_id`) REFERENCES `sar_inv_warehouses`(`id`) ON DELETE RESTRICT,
    INDEX `idx_dispatch_number` (`dispatch_number`),
    INDEX `idx_status` (`status`),
    INDEX `idx_source_warehouse_id` (`source_warehouse_id`),
    INDEX `idx_destination` (`destination_type`, `destination_id`),
    INDEX `idx_dispatch_date` (`dispatch_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 7. DISPATCH ITEMS TABLE
-- ============================================================================
CREATE TABLE IF NOT EXISTS `sar_inv_dispatch_items` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `dispatch_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `quantity` DECIMAL(15,2) NOT NULL,
    `received_quantity` DECIMAL(15,2) DEFAULT 0,
    `status` ENUM('pending', 'shipped', 'received', 'partial', 'cancelled') DEFAULT 'pending',
    `notes` TEXT DEFAULT NULL,
    FOREIGN KEY (`dispatch_id`) REFERENCES `sar_inv_dispatches`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `sar_inv_products`(`id`) ON DELETE RESTRICT,
    INDEX `idx_dispatch_id` (`dispatch_id`),
    INDEX `idx_product_id` (`product_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 8. TRANSFERS TABLE
-- ============================================================================
CREATE TABLE IF NOT EXISTS `sar_inv_transfers` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `transfer_number` VARCHAR(50) NOT NULL,
    `source_warehouse_id` INT NOT NULL,
    `destination_warehouse_id` INT NOT NULL,
    `status` ENUM('pending', 'approved', 'in_transit', 'received', 'cancelled') DEFAULT 'pending',
    `transfer_date` DATE DEFAULT NULL,
    `received_date` DATE DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_by` INT DEFAULT NULL,
    `approved_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_transfer_number` (`transfer_number`),
    FOREIGN KEY (`source_warehouse_id`) REFERENCES `sar_inv_warehouses`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`destination_warehouse_id`) REFERENCES `sar_inv_warehouses`(`id`) ON DELETE RESTRICT,
    INDEX `idx_transfer_number` (`transfer_number`),
    INDEX `idx_status` (`status`),
    INDEX `idx_source_warehouse_id` (`source_warehouse_id`),
    INDEX `idx_destination_warehouse_id` (`destination_warehouse_id`),
    INDEX `idx_transfer_date` (`transfer_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 9. TRANSFER ITEMS TABLE
-- ============================================================================
CREATE TABLE IF NOT EXISTS `sar_inv_transfer_items` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `transfer_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `quantity` DECIMAL(15,2) NOT NULL,
    `received_quantity` DECIMAL(15,2) DEFAULT 0,
    `status` ENUM('pending', 'in_transit', 'received', 'partial', 'cancelled') DEFAULT 'pending',
    `notes` TEXT DEFAULT NULL,
    FOREIGN KEY (`transfer_id`) REFERENCES `sar_inv_transfers`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `sar_inv_products`(`id`) ON DELETE RESTRICT,
    INDEX `idx_transfer_id` (`transfer_id`),
    INDEX `idx_product_id` (`product_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 10. ASSETS TABLE
-- ============================================================================
CREATE TABLE IF NOT EXISTS `sar_inv_assets` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `product_id` INT NOT NULL,
    `serial_number` VARCHAR(255) DEFAULT NULL,
    `barcode` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('available', 'dispatched', 'in_repair', 'retired', 'lost') DEFAULT 'available',
    `current_location_type` ENUM('warehouse', 'dispatch', 'repair', 'site', 'vendor', 'customer') DEFAULT 'warehouse',
    `current_location_id` INT DEFAULT NULL,
    `purchase_date` DATE DEFAULT NULL,
    `warranty_expiry` DATE DEFAULT NULL,
    `company_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_serial_number` (`serial_number`),
    UNIQUE KEY `uk_barcode` (`barcode`),
    FOREIGN KEY (`product_id`) REFERENCES `sar_inv_products`(`id`) ON DELETE RESTRICT,
    INDEX `idx_product_id` (`product_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_serial_number` (`serial_number`),
    INDEX `idx_barcode` (`barcode`),
    INDEX `idx_company_id` (`company_id`),
    INDEX `idx_location` (`current_location_type`, `current_location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 11. ASSET HISTORY TABLE
-- ============================================================================
CREATE TABLE IF NOT EXISTS `sar_inv_asset_history` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `asset_id` INT NOT NULL,
    `action_type` ENUM('created', 'moved', 'dispatched', 'received', 'repair_start', 'repair_end', 'retired', 'status_change') NOT NULL,
    `from_location_type` VARCHAR(50) DEFAULT NULL,
    `from_location_id` INT DEFAULT NULL,
    `to_location_type` VARCHAR(50) DEFAULT NULL,
    `to_location_id` INT DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`asset_id`) REFERENCES `sar_inv_assets`(`id`) ON DELETE CASCADE,
    INDEX `idx_asset_id` (`asset_id`),
    INDEX `idx_action_type` (`action_type`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 12. ITEM HISTORY TABLE
-- ============================================================================
CREATE TABLE IF NOT EXISTS `sar_inv_item_history` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `product_id` INT NOT NULL,
    `warehouse_id` INT DEFAULT NULL,
    `transaction_type` ENUM('stock_in', 'stock_out', 'adjustment', 'transfer_out', 'transfer_in', 'dispatch', 'return', 'reservation', 'release') NOT NULL,
    `quantity` DECIMAL(15,2) NOT NULL,
    `reference_type` VARCHAR(50) DEFAULT NULL,
    `reference_id` INT DEFAULT NULL,
    `balance_after` DECIMAL(15,2) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`product_id`) REFERENCES `sar_inv_products`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`warehouse_id`) REFERENCES `sar_inv_warehouses`(`id`) ON DELETE SET NULL,
    INDEX `idx_product_id` (`product_id`),
    INDEX `idx_warehouse_id` (`warehouse_id`),
    INDEX `idx_transaction_type` (`transaction_type`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_reference` (`reference_type`, `reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 13. REPAIRS TABLE
-- ============================================================================
CREATE TABLE IF NOT EXISTS `sar_inv_repairs` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `repair_number` VARCHAR(50) NOT NULL,
    `asset_id` INT NOT NULL,
    `status` ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    `issue_description` TEXT DEFAULT NULL,
    `diagnosis` TEXT DEFAULT NULL,
    `repair_notes` TEXT DEFAULT NULL,
    `cost` DECIMAL(15,2) DEFAULT 0,
    `vendor_id` INT DEFAULT NULL,
    `start_date` DATE DEFAULT NULL,
    `completion_date` DATE DEFAULT NULL,
    `created_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_repair_number` (`repair_number`),
    FOREIGN KEY (`asset_id`) REFERENCES `sar_inv_assets`(`id`) ON DELETE RESTRICT,
    INDEX `idx_repair_number` (`repair_number`),
    INDEX `idx_asset_id` (`asset_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_vendor_id` (`vendor_id`),
    INDEX `idx_start_date` (`start_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 14. MATERIAL MASTERS TABLE
-- ============================================================================
CREATE TABLE IF NOT EXISTS `sar_inv_material_masters` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `code` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `specifications` JSON DEFAULT NULL,
    `unit_of_measure` VARCHAR(50) DEFAULT NULL,
    `default_quantity` DECIMAL(15,2) DEFAULT 1,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `company_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_code_company` (`code`, `company_id`),
    INDEX `idx_code` (`code`),
    INDEX `idx_status` (`status`),
    INDEX `idx_company_id` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 15. MATERIAL REQUESTS TABLE
-- ============================================================================
CREATE TABLE IF NOT EXISTS `sar_inv_material_requests` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `request_number` VARCHAR(50) NOT NULL,
    `material_master_id` INT DEFAULT NULL,
    `product_id` INT DEFAULT NULL,
    `quantity` DECIMAL(15,2) NOT NULL,
    `status` ENUM('pending', 'approved', 'rejected', 'fulfilled', 'partially_fulfilled', 'cancelled') DEFAULT 'pending',
    `requester_id` INT DEFAULT NULL,
    `approver_id` INT DEFAULT NULL,
    `fulfilled_quantity` DECIMAL(15,2) DEFAULT 0,
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_request_number` (`request_number`),
    FOREIGN KEY (`material_master_id`) REFERENCES `sar_inv_material_masters`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`product_id`) REFERENCES `sar_inv_products`(`id`) ON DELETE SET NULL,
    INDEX `idx_request_number` (`request_number`),
    INDEX `idx_status` (`status`),
    INDEX `idx_requester_id` (`requester_id`),
    INDEX `idx_approver_id` (`approver_id`),
    INDEX `idx_material_master_id` (`material_master_id`),
    INDEX `idx_product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 16. AUDIT LOG TABLE
-- ============================================================================
CREATE TABLE IF NOT EXISTS `sar_inv_audit_log` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `table_name` VARCHAR(100) NOT NULL,
    `record_id` INT NOT NULL,
    `action` ENUM('create', 'update', 'delete') NOT NULL,
    `old_values` JSON DEFAULT NULL,
    `new_values` JSON DEFAULT NULL,
    `user_id` INT DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_table_name` (`table_name`),
    INDEX `idx_record_id` (`record_id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_action` (`action`),
    INDEX `idx_table_record` (`table_name`, `record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
