<?php
require 'c:/xampp/htdocs/krf_amanaccounting/connection.php';
$res = $krfconn->query("SHOW COLUMNS FROM krf_polyhouse_sowingplant_details");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>
