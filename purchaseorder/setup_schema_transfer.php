<?php
include "../connection.php";

echo "<h3>Initializing Warehouse Request Schema...</h3>";

$queries = [
    // 1. Warehouse Transfer Requests Table
    "CREATE TABLE IF NOT EXISTS inventory_transfer_requests (
        request_id INT AUTO_INCREMENT PRIMARY KEY,
        request_number VARCHAR(50) UNIQUE,
        from_location_id INT NOT NULL, -- Main Warehouse (Usually)
        to_location_id INT NOT NULL,   -- Sub Warehouse (Requesting)
        request_date DATE NOT NULL,
        status ENUM('Pending', 'Approved', 'Rejected', 'Completed') DEFAULT 'Pending',
        requested_by VARCHAR(100),
        remarks TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (from_location_id) REFERENCES inventory_locations(location_id),
        FOREIGN KEY (to_location_id) REFERENCES inventory_locations(location_id)
    )",

    // 2. Request Items Table
    "CREATE TABLE IF NOT EXISTS inventory_transfer_items (
        item_id INT AUTO_INCREMENT PRIMARY KEY,
        request_id INT NOT NULL,
        seed_id INT NOT NULL,
        quantity_requested DECIMAL(15,2) NOT NULL,
        quantity_approved DECIMAL(15,2) DEFAULT 0.00,
        FOREIGN KEY (request_id) REFERENCES inventory_transfer_requests(request_id) ON DELETE CASCADE
    )",

    // 3. Ensure Locations have types if needed (Optional but good)
    // We already have 'inventory_locations', let's stick to it.
];

foreach ($queries as $sql) {
    if ($krfconn->query($sql) === TRUE) {
        echo "<div style='color:green'>Query Executed Successfully</div>";
    } else {
        echo "<div style='color:grey'>Note: " . $krfconn->error . "</div>";
    }
}

echo "<br><strong>Warehouse Request Schema Setup Completed.</strong>";
?>