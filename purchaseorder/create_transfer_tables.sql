CREATE TABLE IF NOT EXISTS `inventory_transfer_requests` (
  `request_id` INT AUTO_INCREMENT PRIMARY KEY,
  `request_number` VARCHAR(50) NOT NULL,
  `from_location_id` INT NOT NULL,
  `to_location_id` INT NOT NULL,
  `request_date` DATE NOT NULL,
  `remarks` TEXT DEFAULT NULL,
  `status` ENUM('Pending','Dispatched','Completed','Cancelled') DEFAULT 'Pending',
  `requested_by` VARCHAR(100) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `inventory_transfer_items` (
  `item_id` INT AUTO_INCREMENT PRIMARY KEY,
  `request_id` INT NOT NULL,
  `seed_id` INT NOT NULL,
  `quantity_requested` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `quantity_approved` DECIMAL(15,2) DEFAULT 0,
  FOREIGN KEY (`request_id`) REFERENCES `inventory_transfer_requests`(`request_id`) ON DELETE CASCADE
);
