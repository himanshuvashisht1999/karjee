<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require 'connection.php';
$res = $krfconn->query('SHOW TABLES LIKE "vendor_master"');
echo "vendor_master exists: " . ($res->num_rows > 0 ? "Yes" : "No") . "\n";
$res = $krfconn->query('SHOW TABLES LIKE "inventory_purchase_orders"');
echo "inventory_purchase_orders exists: " . ($res->num_rows > 0 ? "Yes" : "No") . "\n";
$res = $conn->query('SHOW TABLES LIKE "supplier_authorizedperson"');
echo "supplier_authorizedperson exists: " . ($res->num_rows > 0 ? "Yes" : "No") . "\n";
?>