<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require 'connection.php';
$sql = file_get_contents('purchaseorder/update_item_master.sql');
if ($krfconn->multi_query($sql)) {
    echo "Columns added successfully.\n";
} else {
    echo "Error: " . $krfconn->error . "\n";
}
?>