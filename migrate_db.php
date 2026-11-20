<?php
require('connection.php');

$queries = [
    "CREATE TABLE IF NOT EXISTS krf_invoice_gatepass (
      id int(11) NOT NULL AUTO_INCREMENT,
      projectid int(11) DEFAULT NULL,
      psono varchar(50) NOT NULL,
      clientid int(11) DEFAULT NULL,
      productid int(11) DEFAULT NULL,
      deliveryqty int(11) DEFAULT NULL,
      deliverydate date DEFAULT NULL,
      filepath varchar(500) DEFAULT NULL,
      created_at timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

    "CREATE TABLE IF NOT EXISTS krf_invoice_deliverynote (
      id int(11) NOT NULL AUTO_INCREMENT,
      projectid int(11) DEFAULT NULL,
      psono varchar(50) NOT NULL,
      clientid int(11) DEFAULT NULL,
      productid int(11) DEFAULT NULL,
      deliveryqty int(11) DEFAULT NULL,
      deliverydate date DEFAULT NULL,
      filepath varchar(500) DEFAULT NULL,
      created_at timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

    "ALTER TABLE krf_vehicle_travel_history 
     ADD COLUMN gatepass_id int(11) DEFAULT NULL,
     ADD COLUMN deliverynote_id int(11) DEFAULT NULL;"
];

foreach ($queries as $q) {
    if ($krfconn->query($q) === TRUE) {
        echo "Query success.\n";
    } else {
        echo "Query failed: " . $krfconn->error . "\n";
    }
}
echo "Migration complete.\n";
?>
