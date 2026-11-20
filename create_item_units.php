<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require 'connection.php';
$sql = file_get_contents('purchaseorder/create_item_units_table.sql');
if ($krfconn->multi_query($sql)) {
    echo "Units table created successfully.\n";
} else {
    echo "Error: " . $krfconn->error . "\n";
}
?>