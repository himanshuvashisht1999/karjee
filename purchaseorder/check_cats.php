<?php
include "../connection.php";
$cats = $krfconn->query("SELECT * FROM plant_category_master LIMIT 1");
$subcats = $krfconn->query("SELECT * FROM plant_subcategory_master LIMIT 1");
print_r($cats->fetch_assoc());
print_r($subcats->fetch_assoc());
?>