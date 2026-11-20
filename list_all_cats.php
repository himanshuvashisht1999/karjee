<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require 'connection.php';
$res = $krfconn->query("SELECT * FROM plant_category_master");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
$res = $krfconn->query("SELECT * FROM plant_subcategory_master");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
?>