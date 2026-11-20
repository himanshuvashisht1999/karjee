ALTER TABLE `krf_purchase_item_master` 
ADD COLUMN `cgst` DECIMAL(5,2) DEFAULT 0.00 AFTER `hsn_code`,
ADD COLUMN `sgst` DECIMAL(5,2) DEFAULT 0.00 AFTER `cgst`,
ADD COLUMN `igst` DECIMAL(5,2) DEFAULT 0.00 AFTER `sgst`;
