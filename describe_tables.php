<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require 'connection.php';
$res = $krfconn->query("DESCRIBE plant_category_master");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
$res = $krfconn->query("DESCRIBE inventory_purchase_item_master");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
?>