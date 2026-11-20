<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require 'connection.php';
function findTable($conn, $dbName)
{
    echo "--- $dbName Tables (filtered) ---\n";
    $res = $conn->query("SHOW TABLES LIKE '%purchase%'");
    while ($row = $res->fetch_row())
        echo $row[0] . "\n";
    $res = $conn->query("SHOW TABLES LIKE '%po%'");
    while ($row = $res->fetch_row())
        echo $row[0] . "\n";
}
findTable($krfconn, "Karjee Farm");
findTable($conn, "Master DB");
?>