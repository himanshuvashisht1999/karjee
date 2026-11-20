<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require 'connection.php';
$res = $krfconn->query("DESCRIBE krf_inventory_purchase_order_items");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
?>