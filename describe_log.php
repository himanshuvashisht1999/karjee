<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require 'connection.php';
$res = $krfconn->query("DESCRIBE inventory_transactions_log");
while ($row = $res->fetch_assoc())
    print_r($row);
?>