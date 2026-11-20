<?php
include "../connection.php";

echo "<h3>Initializing Warehouse / Location Master Schema...</h3>";

$queries = [
    // 1. Warehouse/Location Master Table
    "CREATE TABLE IF NOT EXISTS inventory_locations (
        location_id INT AUTO_INCREMENT PRIMARY KEY,
        location_name VARCHAR(100) NOT NULL UNIQUE,
        location_type ENUM('Main', 'Sub', 'Store', 'Van') DEFAULT 'Sub' NOT NULL,
        contact_person VARCHAR(100),
        contact_number VARCHAR(20),
        address TEXT,
        is_active ENUM('Yes', 'No') DEFAULT 'Yes',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    // 2. Insert Default Main Warehouse if not exists
    "INSERT IGNORE INTO inventory_locations (location_name, location_type, is_active) VALUES ('Main Warehouse', 'Main', 'Yes')"
];

foreach ($queries as $sql) {
    if ($krfconn->query($sql) === TRUE) {
        echo "<div style='color:green'>Query Executed Successfully</div>";
    } else {
        echo "<div style='color:red'>Error: " . $krfconn->error . "</div>";
    }
}

echo "<br><strong>Warehouse Master Schema Setup Completed.</strong>";
?>