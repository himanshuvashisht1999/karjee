-- Purchase Item Master Table
CREATE TABLE IF NOT EXISTS `krf_purchase_item_master` (
    `item_id` INT AUTO_INCREMENT PRIMARY KEY,
    `item_name` VARCHAR(255) NOT NULL,
    `sku` VARCHAR(100) UNIQUE,
    `category_id` INT DEFAULT 0,
    `subcategory_id` INT DEFAULT 0,
    `unit` VARCHAR(50) COMMENT 'e.g. Kg, Pkt, Nos',
    `hsn_code` VARCHAR(20),
    `tax_percent` DECIMAL(5,2) DEFAULT 0.00,
    `purchase_price` DECIMAL(15,2) DEFAULT 0.00,
    `mrp` DECIMAL(15,2) DEFAULT 0.00,
    `min_stock` DECIMAL(15,2) DEFAULT 0.00,
    `description` TEXT,
    `status` ENUM('Active', 'Inactive') DEFAULT 'Active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (`category_id`),
    INDEX (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
