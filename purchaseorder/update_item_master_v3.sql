ALTER TABLE `krf_purchase_item_master` 
ADD COLUMN `unit_size` VARCHAR(50) AFTER `unit`,
ADD COLUMN `pack_qty` DECIMAL(15,2) DEFAULT 1.00 AFTER `unit_size`,
ADD COLUMN `photo_path` VARCHAR(255) AFTER `description`;
