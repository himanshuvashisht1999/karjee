<?php
require_once __DIR__ . '/../connection.php';

echo "=== krf_deliveryaddress_location ===\n";
$res = $krfconn->query("SELECT id, projectid, psono, sono, clientid, deliveryaddress, dest_city, dest_district, dest_state, distance, totaldistance, freedistance, deliverycharges, freightamt, transporttype, pickuptype, vehicleno, drivername, drivercontactno, deliveryqty, remark, tedit FROM krf_deliveryaddress_location WHERE psono='KF-PS-00448' OR sono='KF-PS-00448' OR psono='KF-CS-7165' OR sono='KF-CS-7165'");
while($r = $res->fetch_assoc()) {
    echo json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}

echo "=== krf_freightcharges ===\n";
$res2 = $krfconn->query("SELECT id, projectid, psono, sono, clientid, trip_id, destination_locationname, distance_km, vehicle_no, driver_name, driver_mobile, master_freight_amount, freight_amount, freight_discountpercent, freight_discountamount, payable_freightamount, status FROM krf_freightcharges WHERE psono='KF-PS-00448' OR sono='KF-PS-00448' OR psono='KF-CS-7165' OR sono='KF-CS-7165'");
while($r2 = $res2->fetch_assoc()) {
    echo json_encode($r2, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}
