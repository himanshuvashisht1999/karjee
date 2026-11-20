<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require 'connection.php';
$res = $krfconn->query("SHOW TABLES");
$tables = [];
while ($row = $res->fetch_array()) {
    $tables[] = $row[0];
}
echo implode("\n", $tables);
?>