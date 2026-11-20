<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require 'connection.php';
$res = $krfconn->query("SHOW DATABASES");
while ($row = $res->fetch_row())
    echo $row[0] . "\n";
?>