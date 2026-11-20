<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require 'connection.php';
echo "--- Karjee Farm DB Tables ---\n";
$res = $krfconn->query('SHOW TABLES');
while ($row = $res->fetch_row()) {
    echo $row[0] . "\n";
}
?>