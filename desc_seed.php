<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require 'connection.php';
$res = $krfconn->query("DESCRIBE seed_master");
while ($row = $res->fetch_assoc()) {
    echo "Field: {$row['Field']}, Type: {$row['Type']}\n";
}
?>