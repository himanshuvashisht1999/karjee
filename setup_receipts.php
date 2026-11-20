<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require 'connection.php';
$sql = file_get_contents('purchaseorder/create_receipt_tables.sql');
if ($krfconn->multi_query($sql)) {
    do {
        if ($res = $krfconn->store_result()) {
            $res->free();
        }
    } while ($krfconn->more_results() && $krfconn->next_result());
    echo "Tables created successfully.\n";
} else {
    echo "Error: " . $krfconn->error . "\n";
}
?>