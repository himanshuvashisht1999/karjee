<?php
include "../connection.php";

echo "<h3>Initializing Purchase Order & Inventory Database Schema...</h3>";

$queries = [
    "CREATE TABLE IF NOT EXISTS inventory_purchase_orders (
        po_id INT AUTO_INCREMENT PRIMARY KEY,
        po_number VARCHAR(50) UNIQUE,
        vendor_id VARCHAR(50) NOT NULL,
        po_date DATE NOT NULL,
        expected_date DATE,
        status ENUM('Draft', 'Sent', 'Received', 'Cancelled') DEFAULT 'Draft',
        total_amount DECIMAL(15,2) DEFAULT 0.00,
        remarks TEXT,
        created_by VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    "CREATE TABLE IF NOT EXISTS inventory_purchase_order_items (
        po_item_id INT AUTO_INCREMENT PRIMARY KEY,
        po_id INT NOT NULL,
        seed_id INT NOT NULL,
        quantity DECIMAL(15,2) NOT NULL,
        unit_price DECIMAL(15,2) NOT NULL,
        total_price DECIMAL(15,2) NOT NULL,
        received_qty DECIMAL(15,2) DEFAULT 0.00,
        INDEX (po_id),
        FOREIGN KEY (po_id) REFERENCES inventory_purchase_orders(po_id) ON DELETE CASCADE
    )",

    "CREATE TABLE IF NOT EXISTS inventory_stock (
        stock_id INT AUTO_INCREMENT PRIMARY KEY,
        seed_id INT UNIQUE NOT NULL,
        current_quantity DECIMAL(15,2) DEFAULT 0.00,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX(seed_id)
    )",

    "CREATE TABLE IF NOT EXISTS inventory_transactions (
        trans_id INT AUTO_INCREMENT PRIMARY KEY,
        seed_id INT NOT NULL,
        qty_change DECIMAL(15,2) NOT NULL,
        trans_type ENUM('IN', 'OUT', 'ADJUST') NOT NULL,
        reference_type VARCHAR(50), -- e.g. 'PO', 'SALES', 'ADJUSTMENT'
        reference_id VARCHAR(50), -- e.g. PO Number
        notes TEXT,
        trans_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(seed_id)
    )"
];

foreach ($queries as $sql) {
    if ($krfconn->query($sql) === TRUE) {
        echo "<div style='color:green'>Query Executed Successfully</div>";
    } else {
        echo "<div style='color:red'>Error: " . $krfconn->error . "</div>";
    }
}

echo "<br><strong>Database setup completed. You can delete this file now.</strong>";
?>