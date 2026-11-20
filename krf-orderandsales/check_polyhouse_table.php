<?php
require('../connection.php');
$res = $krfconn->query("DESCRIBE krf_polyhouse_plant_details");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}

echo "\n--- Sample Data ---\n";
$s = $krfconn->query("SELECT * FROM krf_polyhouse_plant_details LIMIT 2");
while($r = $s->fetch_assoc()) {
    print_r($r);
}
?>
