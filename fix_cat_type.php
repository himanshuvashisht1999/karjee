<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require 'connection.php';
$sql = file_get_contents('purchaseorder/fix_category_id_type.sql');
if ($krfconn->query($sql)) {
    echo "Category ID type updated successfully.\n";
} else {
    echo "Error: " . $krfconn->error . "\n";
}
?>