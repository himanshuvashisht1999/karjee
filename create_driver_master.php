<?php
require 'connection.php';
$sql = "CREATE TABLE IF NOT EXISTS krf_vehicle_driver_master (
    driverid INT AUTO_INCREMENT PRIMARY KEY,
    projectid INT DEFAULT 0,
    drivername VARCHAR(255) NOT NULL,
    drivermobileno VARCHAR(20),
    status ENUM('Active','Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
if ($krfconn->query($sql)) {
    echo "SUCCESS: krf_vehicle_driver_master created or already exists.\n";
} else {
    echo "ERROR: " . $krfconn->error . "\n";
}
?>
