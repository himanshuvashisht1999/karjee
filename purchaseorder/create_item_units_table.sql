CREATE TABLE IF NOT EXISTS `krf_purchase_item_units` (
    `item_unit_id` INT AUTO_INCREMENT PRIMARY KEY,
    `item_id` INT NOT NULL,
    `unit` VARCHAR(50),
    `unit_size` VARCHAR(50),
    `pack_qty` DECIMAL(15,2) DEFAULT 1.00,
    `purchase_price` DECIMAL(15,2) DEFAULT 0.00,
    `mrp` DECIMAL(15,2) DEFAULT 0.00,
    `status` ENUM('Active', 'Inactive') DEFAULT 'Active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (`item_id`),
    FOREIGN KEY (`item_id`) REFERENCES `krf_purchase_item_master`(`item_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- We will keep the columns in master for now to avoid breaking existing queries, 
-- but we will transition logic to use the units table.
