<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require 'connection.php';
$res = $krfconn->query("DESCRIBE inventory_purchase_orders");
if (!$res) {
    echo "Error: " . $krfconn->error . "\n";
} else {
    echo "Table exists!\n";
}
?>