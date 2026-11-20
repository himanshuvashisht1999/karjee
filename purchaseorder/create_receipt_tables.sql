-- Location data is now fetched from hrmplus.hrm_attendancelocationmaster

CREATE TABLE IF NOT EXISTS `inventory_stock_location` (
  `seed_id` INT NOT NULL,
  `location_id` INT NOT NULL,
  `quantity` DECIMAL(15,2) DEFAULT 0,
  `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`seed_id`, `location_id`)
);

CREATE TABLE IF NOT EXISTS `inventory_transactions_log` (
  `log_id` INT AUTO_INCREMENT PRIMARY KEY,
  `seed_id` INT NOT NULL,
  `location_id` INT NOT NULL,
  `qty_change` DECIMAL(15,2) NOT NULL,
  `trans_type` ENUM('IN', 'OUT', 'ADJ', 'TRANSFER') NOT NULL,
  `reference_type` VARCHAR(50),
  `reference_id` INT,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `purchase_receipts` (
  `receipt_id` INT AUTO_INCREMENT PRIMARY KEY,
  `po_id` INT,
  `invoice_no` VARCHAR(50),
  `invoice_date` DATE,
  `received_date` DATE,
  `received_by` VARCHAR(100),
  `location_id` INT,
  `total_amount` DECIMAL(15,2),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `purchase_receipt_items` (
  `receipt_item_id` INT AUTO_INCREMENT PRIMARY KEY,
  `receipt_id` INT NOT NULL,
  `seed_id` INT NOT NULL,
  `unit_id` INT DEFAULT 0,
  `quantity` DECIMAL(15,2) NOT NULL,
  `unit_price` DECIMAL(15,2) NOT NULL,
  `total_price` DECIMAL(15,2) NOT NULL
);
