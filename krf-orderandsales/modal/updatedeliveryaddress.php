<?php
require '../../connection.php';
header('Content-Type: application/json');

$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'No data received']);
    exit;
}

$orderid = mysqli_real_escape_string($krfconn, $data['orderid'] ?? '');
$clientid = mysqli_real_escape_string($krfconn, $data['clientid'] ?? '');
$lat = mysqli_real_escape_string($krfconn, $data['lat'] ?? '');
$lon = mysqli_real_escape_string($krfconn, $data['lon'] ?? '');
$state = mysqli_real_escape_string($krfconn, $data['state'] ?? '');
$district = mysqli_real_escape_string($krfconn, $data['district'] ?? '');
$city = mysqli_real_escape_string($krfconn, $data['city'] ?? '');
$pincode = mysqli_real_escape_string($krfconn, $data['pincode'] ?? '');
$full_address = mysqli_real_escape_string($krfconn, $data['full_address'] ?? '');
$calc_km = mysqli_real_escape_string($krfconn, $data['calc_km'] ?? '0');
$distance = floatval($calc_km);

if (empty($full_address)) {
    echo json_encode(['status' => 'error', 'message' => 'Delivery address is required']);
    exit;
}

// Check if record exists in krf_deliveryaddress_location
$chkQuery = "SELECT id, projectid FROM krf_deliveryaddress_location WHERE (psono = '$orderid' OR sono = '$orderid' OR id = '$orderid')";
$chkRes = mysqli_query($krfconn, $chkQuery);

if ($chkRes && mysqli_num_rows($chkRes) > 0) {
    $updateQuery = "UPDATE krf_deliveryaddress_location 
                    SET deliveryaddress = '$full_address',
                        dest_latitude = '$lat',
                        dest_longitude = '$lon',
                        dest_state = '$state',
                        dest_district = '$district',
                        dest_city = '$city',
                        dest_pincode = '$pincode',
                        distance = '$distance',
                        totaldistance = '$distance',
                        updated_at = NOW()
                    WHERE (psono = '$orderid' OR sono = '$orderid' OR id = '$orderid')";
    mysqli_query($krfconn, $updateQuery);
} else {
    // Look up projectid and clientid from krf_provisional_sales_order or krf_sales_order
    $projId = 0;
    $psoQuery = "SELECT projectid, clientid FROM krf_provisional_sales_order WHERE psono = '$orderid' LIMIT 1";
    $psoRes = mysqli_query($krfconn, $psoQuery);
    if ($psoRes && $psoRow = mysqli_fetch_assoc($psoRes)) {
        $projId = $psoRow['projectid'];
        if (empty($clientid)) $clientid = $psoRow['clientid'];
    } else {
        $soQuery = "SELECT projectid, clientid FROM krf_sales_order WHERE (sono = '$orderid' OR psono = '$orderid') LIMIT 1";
        $soRes = mysqli_query($krfconn, $soQuery);
        if ($soRes && $soRow = mysqli_fetch_assoc($soRes)) {
            $projId = $soRow['projectid'];
            if (empty($clientid)) $clientid = $soRow['clientid'];
        }
    }

    $insertQuery = "INSERT INTO krf_deliveryaddress_location 
                    (projectid, psono, sono, clientid, deliveryaddress, dest_latitude, dest_longitude, dest_state, dest_district, dest_city, dest_pincode, distance, totaldistance, status, created_at, updated_at)
                    VALUES ('$projId', '$orderid', '$orderid', '$clientid', '$full_address', '$lat', '$lon', '$state', '$district', '$city', '$pincode', '$distance', '$distance', 'Pending', NOW(), NOW())";
    mysqli_query($krfconn, $insertQuery);
}

// Synchronize with krf_freightcharges table
$fcCheck = mysqli_query($krfconn, "SELECT id FROM krf_freightcharges WHERE (psono = '$orderid' OR sono = '$orderid')");
if ($fcCheck && mysqli_num_rows($fcCheck) > 0) {
    $latVal = ($lat !== '' ? "'$lat'" : "NULL");
    $lonVal = ($lon !== '' ? "'$lon'" : "NULL");
    $distVal = ($distance > 0 ? "'$distance'" : "distance_km");
    mysqli_query($krfconn, "UPDATE krf_freightcharges 
                            SET destination_locationname = '$full_address',
                                destination_lat = $latVal,
                                destination_lng = $lonVal,
                                distance_km = $distVal,
                                distance_source = 'map',
                                updated_at = NOW()
                            WHERE (psono = '$orderid' OR sono = '$orderid')");
}

// Also update delivery_address in orders tables and set pickupfrom = 'delivered'
mysqli_query($krfconn, "UPDATE krf_provisional_sales_order SET delivery_address = '$full_address', statename = '$state', districtname = '$district', pickupfrom = 'delivered' WHERE psono = '$orderid'");
mysqli_query($krfconn, "UPDATE krf_sales_order SET delivery_address = '$full_address', statename = '$state', districtname = '$district' WHERE (sono = '$orderid' OR psono = '$orderid')");

echo json_encode(['status' => 'success', 'message' => 'Delivery address saved successfully']);
?>