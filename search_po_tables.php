<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require 'connection.php';
$dbs = [$krfconn => "KRF", $conn => "Master"];
foreach ($dbs as $db => $name) {
    echo "--- $name DB ---\n";
    $res = $db->query("SHOW TABLES");
    while ($row = $res->fetch_row()) {
        if (stripos($row[0], "purchase") !== false || stripos($row[0], "po") !== false) {
            echo "Table: " . $row[0] . "\n";
        }
    }
}
?>