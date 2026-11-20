<?php
require('connection.php');

$queries = [
    "CREATE TABLE IF NOT EXISTS krf_vehicle_master (
        vehicleid INT AUTO_INCREMENT PRIMARY KEY,
        projectid INT DEFAULT 0,
        vehiclename VARCHAR(255) NOT NULL,
        status ENUM('Active', 'Inactive') DEFAULT 'Active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;",

    "CREATE TABLE IF NOT EXISTS krf_freight_master (
        chargeid INT AUTO_INCREMENT PRIMARY KEY,
        projectid INT DEFAULT 0,
        dist_min INT NOT NULL,
        dist_max INT NOT NULL,
        vehicleid INT NOT NULL,
        freight_charge DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (vehicleid) REFERENCES krf_vehicle_master(vehicleid) ON DELETE CASCADE
    ) ENGINE=InnoDB;"
];

foreach ($queries as $sql) {
    if (!$krfconn->query($sql)) {
        echo "Error creating table: " . $krfconn->error . "\n";
    }
}

// Check if data already exists
$check = $krfconn->query("SELECT COUNT(*) as count FROM krf_vehicle_master");
$row = $check->fetch_assoc();

if ($row['count'] == 0) {
    // Insert initial vehicles
    $vehicles = ['छोटा हाथी (CHHOTA HATHI)', 'पिकअप (PICKUP)', '1109'];
    $vehicleIds = [];
    foreach ($vehicles as $v) {
        $stmt = $krfconn->prepare("INSERT INTO krf_vehicle_master (vehiclename) VALUES (?)");
        $stmt->bind_param("s", $v);
        $stmt->execute();
        $vehicleIds[$v] = $stmt->insert_id;
    }

    // Insert freight charges from image
    $data = [
        // [min, max, chhota_hathi, pickup, 1109]
        [0, 10, 500, 800, 2500],
        [11, 20, 700, 1500, 2500],
        [21, 30, 800, 1700, 3000],
        [31, 40, 1200, 2000, 3500],
        [41, 50, 1700, 2300, 4000],
        [51, 60, 1800, 2500, 4000],
        [61, 70, 2200, 3000, 5000],
        [71, 80, 2200, 3000, 5000],
        [81, 90, 2500, 3500, 5500],
        [91, 100, 3000, 4000, 7000],
        [101, 9999, 0, 0, 0] // Placeholder for 'Above 100'
    ];

    foreach ($data as $range) {
        // Chhota Hathi
        $stmt = $krfconn->prepare("INSERT INTO krf_freight_master (dist_min, dist_max, vehicleid, freight_charge) VALUES (?, ?, ?, ?)");
        $v1 = $vehicleIds['छोटा हाथी (CHHOTA HATHI)'];
        $stmt->bind_param("iiid", $range[0], $range[1], $v1, $range[2]);
        $stmt->execute();

        // Pickup
        $v2 = $vehicleIds['पिकअप (PICKUP)'];
        $stmt->bind_param("iiid", $range[0], $range[1], $v2, $range[3]);
        $stmt->execute();

        // 1109
        $v3 = $vehicleIds['1109'];
        $stmt->bind_param("iiid", $range[0], $range[1], $v3, $range[4]);
        $stmt->execute();
    }
    echo "Initial data seeded successfully.\n";
} else {
    echo "Tables already exist and contain data.\n";
}
?>