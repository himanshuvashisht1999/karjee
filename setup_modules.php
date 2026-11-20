<?php
include "connection.php";

$sql = "CREATE TABLE IF NOT EXISTS whatsapp_modulemaster (
    module_id INT AUTO_INCREMENT PRIMARY KEY,
    module_code VARCHAR(100) NOT NULL UNIQUE,
    module_name VARCHAR(255) NOT NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($krfconn->query($sql) === TRUE) {
    echo "Table whatsapp_modulemaster created successfully\n";
    
    // Insert initial data
    $initialData = [
        ['PURCHASE', 'Purchase & Stock Alerts'],
        ['SOWING', 'Sowing Operations'],
        ['GERMINATION', 'Germination Box Movement'],
        ['ALLOCATION', 'Germination Box Allocation'],
        ['ORDER', 'Order Management'],
        ['PAYMENT', 'Payment Processing'],
        ['INVENTORY', 'Inventory Alerts'],
        ['LEAVE', 'Leave Management']
    ];
    
    foreach ($initialData as $data) {
        $stmt = $krfconn->prepare("INSERT IGNORE INTO whatsapp_modulemaster (module_code, module_name) VALUES (?, ?)");
        $stmt->bind_param("ss", $data[0], $data[1]);
        $stmt->execute();
    }
    echo "Initial data inserted.\n";
} else {
    echo "Error creating table: " . $krfconn->error . "\n";
}

$krfconn->close();
?>
