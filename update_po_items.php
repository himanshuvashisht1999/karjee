<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require 'connection.php';
$sql = file_get_contents('purchaseorder/update_po_items_table.sql');
if ($krfconn->multi_query($sql)) {
    echo "PO items table updated successfully.\n";
} else {
    echo "Error: " . $krfconn->error . "\n";
}
?>