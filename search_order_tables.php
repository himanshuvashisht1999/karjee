<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require 'connection.php';
$dbs = ['KRF' => $krfconn, 'Master' => $mconn];
foreach ($dbs as $name => $db) {
    echo "--- $name DB Tables --- \n";
    $res = $db->query("SHOW TABLES LIKE '%order%'");
    while ($row = $res->fetch_row())
        echo $row[0] . "\n";
}
?>