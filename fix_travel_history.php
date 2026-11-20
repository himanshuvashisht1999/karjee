<?php
require 'connection.php';
$cols = $krfconn->query("SHOW COLUMNS FROM krf_vehicle_travel_history");
$existing = []; while($row = $cols->fetch_assoc()) { $existing[] = $row['Field']; }

if (!in_array('projectid', $existing)) {
    $krfconn->query("ALTER TABLE krf_vehicle_travel_history ADD COLUMN projectid INT DEFAULT 0 AFTER id");
    echo "SUCCESS: projectid column added.\n";
} else {
    echo "INFO: projectid already exists.\n";
}

if (!in_array('deliverydate', $existing)) {
    $krfconn->query("ALTER TABLE krf_vehicle_travel_history ADD COLUMN deliverydate DATE DEFAULT NULL");
    echo "SUCCESS: deliverydate column added.\n";
}

if (!in_array('vehicle_id', $existing)) {
    $krfconn->query("ALTER TABLE krf_vehicle_travel_history ADD COLUMN vehicle_id INT DEFAULT 0");
    echo "SUCCESS: vehicle_id column added.\n";
}
?>
