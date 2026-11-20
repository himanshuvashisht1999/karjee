<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require 'connection.php';
$res = $krfconn->query("SHOW TABLES");
while ($row = $res->fetch_array())
    echo $row[0] . "\n";
?>