<?php
require('connection.php');

$queries = [
    "ALTER TABLE krf_vehicle_master ADD COLUMN IF NOT EXISTS vehicleno VARCHAR(50) AFTER vehiclename;",
    "ALTER TABLE krf_vehicle_master ADD COLUMN IF NOT EXISTS vehicleownername VARCHAR(255) AFTER vehicleno;"
];

foreach ($queries as $sql) {
    if (!$krfconn->query($sql)) {
        echo "Error altering table: " . $krfconn->error . "\n";
    } else {
        echo "Query executed successfully: " . $sql . "\n";
    }
}
?>