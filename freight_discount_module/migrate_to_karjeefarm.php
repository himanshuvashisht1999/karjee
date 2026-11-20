<?php
require_once __DIR__ . '/../connection.php';

echo "1. Checking karjeefarm database...\n";

$createSql = "CREATE TABLE IF NOT EXISTS `karjeefarm`.`krf_freight_discount_plan` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `projectid` INT(11) DEFAULT 1,
  `companyid` INT(11) DEFAULT 1,
  `subcompanyid` INT(11) DEFAULT 1,
  `plan_code` VARCHAR(50) NOT NULL COMMENT 'Unique identifier code (e.g. FDR-DLR-GRAFT)',
  `plan_name` VARCHAR(150) NOT NULL COMMENT 'Descriptive title of discount scheme',
  `customer_type` ENUM('Dealer','Farmer','All') NOT NULL DEFAULT 'Farmer' COMMENT 'Target customer segment',
  `plant_type` ENUM('Grafted','Nongrafted','Both') NOT NULL DEFAULT 'Grafted' COMMENT 'Target plant/crop category',
  `discount_type` ENUM('Percentage','Fixed_Amount','Per_Plant','Per_KM') NOT NULL DEFAULT 'Percentage',
  `discount_value` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `max_discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Upper cap on total discount per order (0.00 = Unlimited)',
  `min_order_qty` INT(11) NOT NULL DEFAULT 0 COMMENT 'Minimum order plant quantity required to qualify',
  `max_order_qty` INT(11) NOT NULL DEFAULT 0 COMMENT 'Maximum order plant quantity limit (0 = Unlimited)',
  `min_freight_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Minimum freight invoice value needed to trigger discount',
  `min_distance_km` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Minimum delivery distance in KM',
  `max_distance_km` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Maximum delivery distance in KM',
  `stateid` VARCHAR(50) NOT NULL DEFAULT 'ALL' COMMENT 'ALL or specific state ID from master_states',
  `statename` VARCHAR(100) DEFAULT 'All States',
  `districtid` VARCHAR(50) NOT NULL DEFAULT 'ALL' COMMENT 'ALL or specific district ID from master_districts',
  `districtname` VARCHAR(100) DEFAULT 'All Districts',
  `valid_from` DATE NOT NULL COMMENT 'Start date of discount validity',
  `valid_to` DATE NOT NULL COMMENT 'End date of discount validity',
  `status` ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  `priority` INT(11) NOT NULL DEFAULT 1 COMMENT 'Evaluation priority order (higher executes first)',
  `description` TEXT DEFAULT NULL,
  `terms_conditions` TEXT DEFAULT NULL,
  `created_by` VARCHAR(100) DEFAULT 'Admin',
  `updated_by` VARCHAR(100) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `tedit` VARCHAR(50) DEFAULT '' COMMENT 'Flag for soft-delete status (deleted)',
  PRIMARY KEY (`id`),
  KEY `idx_customer_plant_status` (`customer_type`, `plant_type`, `status`),
  KEY `idx_validity` (`valid_from`, `valid_to`),
  KEY `idx_state_district` (`stateid`, `districtid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if (!$krfconn->query($createSql)) {
    die("Error creating table in karjeefarm: " . $krfconn->error . "\n");
}
echo "Table created/verified in karjeefarm.\n";

echo "2. Copying data from krf_accounting to karjeefarm...\n";
$krfconn->query("TRUNCATE TABLE `karjeefarm`.`krf_freight_discount_plan`");
$copySql = "INSERT INTO `karjeefarm`.`krf_freight_discount_plan` 
    SELECT * FROM `krf_accounting`.`krf_freight_discount_plan`";

if ($krfconn->query($copySql)) {
    echo "Data copied successfully.\n";
} else {
    echo "Copy note: " . $krfconn->error . "\n";
}

$countRes = $krfconn->query("SELECT COUNT(*) as c FROM `karjeefarm`.`krf_freight_discount_plan`");
$row = $countRes->fetch_assoc();
echo "Total rows now in karjeefarm.krf_freight_discount_plan: " . $row['c'] . "\n";
