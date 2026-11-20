-- ============================================================================
-- SQL TABLE SCRIPT: Freight Discount Plan Master (krf_freight_discount_plan)
-- Description: Maintains freight discount plans for Grafted & Non-Grafted crops,
--              with separate customizable plans for Dealers and Farmers.
-- Database: krf_accounting
-- ============================================================================

CREATE TABLE IF NOT EXISTS `krf_freight_discount_plan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `projectid` int(11) NOT NULL DEFAULT 1,
  `companyid` int(11) NOT NULL DEFAULT 1,
  `subcompanyid` int(11) NOT NULL DEFAULT 1,
  
  -- Plan Identity
  `plan_code` varchar(50) NOT NULL COMMENT 'Unique identifier e.g. FDP-DLR-GRAFT-01',
  `plan_name` varchar(200) NOT NULL COMMENT 'Human readable plan title',
  
  -- Target Segment
  `customer_type` enum('Dealer','Farmer','All') NOT NULL DEFAULT 'Farmer' COMMENT 'Target Customer: Dealer, Farmer, or All',
  `plant_type` enum('Grafted','Nongrafted','Both') NOT NULL DEFAULT 'Grafted' COMMENT 'Target Plant Category: Grafted, Nongrafted, Both',
  
  -- Discount Configuration
  `discount_type` enum('Percentage','Fixed_Amount','Per_Plant','Per_KM') NOT NULL DEFAULT 'Percentage' COMMENT 'Percentage (%), Fixed Amount (Rs), Per Plant (Rs/Plant), or Per KM (Rs/KM)',
  `discount_value` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Value based on discount_type',
  `max_discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Max discount cap in Rs (0.00 = Unlimited/No Cap)',
  
  -- Eligibility Criteria & Thresholds
  `min_order_qty` int(11) NOT NULL DEFAULT 0 COMMENT 'Minimum plant quantity required in order',
  `max_order_qty` int(11) NOT NULL DEFAULT 0 COMMENT 'Maximum plant quantity (0 = Unlimited)',
  `min_freight_amount` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Minimum total freight charges to qualify',
  `min_distance_km` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Minimum delivery distance in KM',
  `max_distance_km` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Maximum delivery distance in KM (0 = Unlimited)',
  
  -- Geographic Applicability (Optional / All)
  `stateid` varchar(50) DEFAULT 'ALL' COMMENT 'Applicable State ID or ALL',
  `statename` varchar(150) DEFAULT 'All States' COMMENT 'Applicable State Name',
  `districtid` varchar(50) DEFAULT 'ALL' COMMENT 'Applicable District ID or ALL',
  `districtname` varchar(150) DEFAULT 'All Districts' COMMENT 'Applicable District Name',
  
  -- Validity & Status
  `valid_from` date NOT NULL COMMENT 'Plan effective start date',
  `valid_to` date NOT NULL COMMENT 'Plan expiry date',
  `status` enum('Active','Inactive','Expired') NOT NULL DEFAULT 'Active' COMMENT 'Plan active status',
  `priority` int(11) NOT NULL DEFAULT 1 COMMENT 'Priority order (higher applies first if multiple match)',
  
  -- Additional Info
  `description` text DEFAULT NULL COMMENT 'Plan overview and internal notes',
  `terms_conditions` text DEFAULT NULL COMMENT 'Terms & conditions applicable',
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_by` varchar(100) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `tedit` varchar(50) DEFAULT NULL,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_plan_code` (`projectid`, `plan_code`),
  KEY `idx_plan_lookup` (`projectid`, `customer_type`, `plant_type`, `status`, `valid_from`, `valid_to`),
  KEY `idx_plan_status` (`status`, `valid_from`, `valid_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================================================
-- SAMPLE SEED DATA: Grafted & Non-Grafted Plans for Dealers & Farmers
-- ============================================================================

INSERT INTO `krf_freight_discount_plan` 
(`projectid`, `plan_code`, `plan_name`, `customer_type`, `plant_type`, `discount_type`, `discount_value`, `max_discount_amount`, `min_order_qty`, `max_order_qty`, `min_freight_amount`, `valid_from`, `valid_to`, `status`, `priority`, `description`) 
VALUES
(1, 'FDP-DLR-GRFT-01', 'Dealer Bulk Grafted Freight Subsidy', 'Dealer', 'Grafted', 'Percentage', 20.00, 5000.00, 5000, 0, 2000.00, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 'Active', 10, '20% freight discount on bulk Grafted plant orders for authorized dealers.'),
(1, 'FDP-DLR-NONG-01', 'Dealer Non-Grafted Volume Freight Plan', 'Dealer', 'Nongrafted', 'Per_Plant', 0.25, 3000.00, 3000, 0, 1000.00, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 'Active', 8, 'Rs 0.25 per plant freight subsidy for dealer Non-Grafted orders of min 3,000 plants.'),
(1, 'FDP-FRM-GRFT-01', 'Farmer Grafted Direct Freight Discount', 'Farmer', 'Grafted', 'Percentage', 15.00, 2500.00, 1000, 0, 1000.00, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 'Active', 9, '15% freight concession for direct farmer bookings of Grafted plants.'),
(1, 'FDP-FRM-NONG-01', 'Farmer Non-Grafted Standard Subsidy', 'Farmer', 'Nongrafted', 'Fixed_Amount', 500.00, 500.00, 1500, 0, 1500.00, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 'Active', 7, 'Flat Rs 500 freight discount for farmers purchasing 1,500+ Non-Grafted plants.')
ON DUPLICATE KEY UPDATE `updated_at` = CURRENT_TIMESTAMP;
