<?php
// db_connect.php – central DB connection, leveraging root host-based connection.php
require_once __DIR__ . '/../../connection.php';

// Reuse main company master connection or auth connection
$krcconn = $mconn; 
?>
