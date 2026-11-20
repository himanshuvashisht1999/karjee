<?php
require 'connection.php';
header('Content-Type: application/json');
// Get POST data (JSON)
$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'No data received']);
    exit;
}
$orderid = mysqli_real_escape_string($krfconn, $data['orderid']);
$clientid = mysqli_real_escape_string($krfconn, $data['clientid']);
$lat = mysqli_real_escape_string($krfconn, $data['lat']);
$lon = mysqli_real_escape_string($krfconn, $data['lon']);
$state = mysqli_real_escape_string($krfconn, $data['state']);
$district = mysqli_real_escape_string($krfconn, $data['district']);
$city = mysqli_real_escape_string($krfconn, $data['city']);
$pincode = mysqli_real_escape_string($krfconn, $data['pincode']);
$full_address = mysqli_real_escape_string($krfconn, $data['full_address']);

// Update the krf_deliveryaddress_location table
// We check for psono (order id) AND clientid
$updateQuery = "UPDATE krf_deliveryaddress_location 
                SET delivery_address = '$full_address',
                    dest_latitude = '$lat',
                    dest_longitude = '$lon',
                    dest_state = '$state',
                    dest_district = '$district',
                    dest_city = '$city',
                    dest_pincode = '$pincode',
                    updated_at = NOW()
                WHERE (psono = '$orderid' OR id = '$orderid') AND clientid = '$clientid'";

if (mysqli_query($krfconn, $updateQuery)) {
    if (mysqli_affected_rows($krfconn) > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Delivery address updated successfully']);
    } else {
        echo json_encode(['status' => 'info', 'message' => 'No changes made or record not found']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($krfconn)]);
}
?>