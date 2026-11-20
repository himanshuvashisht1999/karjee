<?php
include "../connection.php";

echo "<h3>Initializing Location-based Inventory Schema...</h3>";

$queries = [
    // 1. Locations Table
    "CREATE TABLE IF NOT EXISTS inventory_locations (
        location_id INT AUTO_INCREMENT PRIMARY KEY,
        location_name VARCHAR(100) NOT NULL UNIQUE,
        details TEXT,
        is_active ENUM('Yes','No') DEFAULT 'Yes'
    )",

    // Seed Data for Default Location
    "INSERT IGNORE INTO inventory_locations (location_name, details) VALUES ('Main Warehouse', 'Primary storage location')",

    // 2. Modify Stock Table to include Location
    // (We will rename/alter the previous 'inventory_stock' or create a new one 'inventory_stock_location')
    // For cleaner migration, we will create a new table and migrate data if needed.
    "CREATE TABLE IF NOT EXISTS inventory_stock_location (
        stock_loc_id INT AUTO_INCREMENT PRIMARY KEY,
        seed_id INT NOT NULL,
        location_id INT NOT NULL,
        quantity DECIMAL(15,2) DEFAULT 0.00,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_item_loc (seed_id, location_id),
        FOREIGN KEY (location_id) REFERENCES inventory_locations(location_id)
    )",

    // 3. Transactions Table (Enhanced)
    "CREATE TABLE IF NOT EXISTS inventory_transactions_log (
        trans_id INT AUTO_INCREMENT PRIMARY KEY,
        seed_id INT NOT NULL,
        location_id INT NOT NULL,
        qty_change DECIMAL(15,2) NOT NULL,
        trans_type ENUM('IN', 'OUT', 'TRANSFER', 'ADJUST') NOT NULL, 
        reference_type VARCHAR(50), 
        reference_id VARCHAR(50),
        notes TEXT,
        trans_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(seed_id),
        INDEX(location_id)
    )",

    // 4. Update PO Table for Status Workflow
    "ALTER TABLE inventory_purchase_orders MODIFY COLUMN status ENUM('Draft', 'Sent', 'Partial', 'Received', 'Closed', 'Cancelled') DEFAULT 'Draft'"
];

foreach ($queries as $sql) {
    if ($krfconn->query($sql) === TRUE) {
        echo "<div style='color:green'>Query Executed Successfully</div>";
    } else {
        // Ignore "Duplicate column" errors if running multiple times
        echo "<div style='color:grey'>Note: " . $krfconn->error . "</div>";
    }
}

echo "<br><strong>Advanced Schema Setup Completed.</strong>";
?>