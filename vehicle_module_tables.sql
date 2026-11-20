-- -----------------------------
-- Vehicle Master Table
-- -----------------------------
CREATE TABLE IF NOT EXISTS `krf_vehicle_master` (
    `vehicleid` INT AUTO_INCREMENT PRIMARY KEY,
    `projectid` INT DEFAULT 0 COMMENT 'Reference to project context',
    `vehiclename` VARCHAR(255) NOT NULL COMMENT 'Type of vehicle (e.g. Pickup)',
    `vehicleno` VARCHAR(50) DEFAULT NULL COMMENT 'Registration number',
    `vehicleownername` VARCHAR(255) DEFAULT NULL COMMENT 'Owner name',
    `traycapacity` INT DEFAULT 0 COMMENT 'Max tray capacity',
    `plantscapacity` INT DEFAULT 0 COMMENT 'Max plants capacity',
    `status` ENUM('Active', 'Inactive') DEFAULT 'Active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (`projectid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------
-- Freight Charges Matrix Table
-- -----------------------------
CREATE TABLE IF NOT EXISTS `krf_freight_master` (
    `chargeid` INT AUTO_INCREMENT PRIMARY KEY,
    `projectid` INT DEFAULT 0,
    `dist_min` INT NOT NULL COMMENT 'Minimum distance in KM',
    `dist_max` INT NOT NULL COMMENT 'Maximum distance in KM',
    `vehicleid` INT NOT NULL COMMENT 'FK from krf_vehicle_master',
    `freight_charge` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `calculationtype` VARCHAR(20) NOT NULL DEFAULT 'fixed' COMMENT 'fixed or rate',
    INDEX (`projectid`, `vehicleid`),
    CONSTRAINT `fk_vehicle_freight` 
        FOREIGN KEY (`vehicleid`) 
        REFERENCES `krf_vehicle_master`(`vehicleid`) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------
-- Initial Data Samples (Optional)
-- -----------------------------
-- INSERT INTO krf_vehicle_master (vehiclename) VALUES ('छोटा हाथी (CHHOTA HATHI)'), ('पिकअप (PICKUP)'), ('1109');
