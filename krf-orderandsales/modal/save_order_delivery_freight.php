<?php
error_reporting(E_ALL);
require('../../connection.php');
if (isset($krfconn) && $krfconn) {
    @$krfconn->set_charset("utf8mb4");
}
include("../functions/orders_functions.php");
include("../functions/product_functions.php");

header('Content-Type: application/json');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request data.']);
    exit;
}

$orderid = mysqli_real_escape_string($krfconn, trim($data['orderid'] ?? ''));
$clientid = mysqli_real_escape_string($krfconn, trim($data['clientid'] ?? ''));
$projectid = intval($data['projectid'] ?? 0);
$pickupfrom = strtolower(trim($data['pickupfrom'] ?? 'delivered'));
if ($pickupfrom !== 'pickup' && $pickupfrom !== 'delivered') {
    $pickupfrom = 'delivered';
}

$dest_latitude = mysqli_real_escape_string($krfconn, trim($data['dest_latitude'] ?? ''));
$dest_longitude = mysqli_real_escape_string($krfconn, trim($data['dest_longitude'] ?? ''));
$dest_state = mysqli_real_escape_string($krfconn, trim($data['dest_state'] ?? ''));
$dest_district = mysqli_real_escape_string($krfconn, trim($data['dest_district'] ?? ''));
$dest_city = mysqli_real_escape_string($krfconn, trim($data['dest_city'] ?? ''));
$dest_pincode = mysqli_real_escape_string($krfconn, trim($data['dest_pincode'] ?? ''));
$delivery_address = mysqli_real_escape_string($krfconn, trim($data['delivery_address'] ?? ''));
$distance = floatval($data['distance'] ?? 0);

$freight_amount = floatval($data['freight_amount'] ?? 0);
$freight_discountpercent = floatval($data['freight_discountpercent'] ?? 0);
$freight_remarks = mysqli_real_escape_string($krfconn, trim($data['freight_remarks'] ?? ''));
$zerocharge = (strtolower(trim($data['zerocharge'] ?? 'no')) === 'yes') ? 'yes' : 'no';

if (empty($orderid)) {
    echo json_encode(['status' => 'error', 'message' => 'Order reference is missing.']);
    exit;
}

if ($pickupfrom === 'delivered' && empty($delivery_address)) {
    echo json_encode(['status' => 'error', 'message' => 'Delivery address is required when Company Delivery is selected.']);
    exit;
}

try {
    // 1. Update pickupfrom and address in krf_provisional_sales_order / krf_sales_order
    $addrToSave = ($pickupfrom === 'pickup') ? '' : $delivery_address;
    $stateToSave = ($pickupfrom === 'pickup') ? '' : $dest_state;
    $districtToSave = ($pickupfrom === 'pickup') ? '' : $dest_district;

    mysqli_query($krfconn, "UPDATE krf_provisional_sales_order 
                            SET pickupfrom = '$pickupfrom', 
                                delivery_address = '$addrToSave',
                                statename = '$stateToSave',
                                districtname = '$districtToSave'
                            WHERE psono = '$orderid'");

    mysqli_query($krfconn, "UPDATE krf_sales_order 
                            SET delivery_address = '$addrToSave',
                                statename = '$stateToSave',
                                districtname = '$districtToSave'
                            WHERE sono = '$orderid' OR psono = '$orderid'");

    // 2. Manage Delivery Address in krf_deliveryaddress_location if delivered
    if ($pickupfrom === 'delivered') {
        $chkQuery = "SELECT id FROM krf_deliveryaddress_location WHERE (psono = '$orderid' OR sono = '$orderid') AND (tedit IS NULL OR tedit = '') LIMIT 1";
        $chkRes = mysqli_query($krfconn, $chkQuery);

        if ($chkRes && mysqli_num_rows($chkRes) > 0) {
            $existingRow = mysqli_fetch_assoc($chkRes);
            $locId = intval($existingRow['id']);
            $updQuery = "UPDATE krf_deliveryaddress_location 
                         SET deliveryaddress = '$delivery_address',
                             dest_latitude = '$dest_latitude',
                             dest_longitude = '$dest_longitude',
                             dest_state = '$dest_state',
                             dest_district = '$dest_district',
                             dest_city = '$dest_city',
                             dest_pincode = '$dest_pincode',
                             distance = '$distance',
                             updated_at = NOW()
                         WHERE id = $locId";
            mysqli_query($krfconn, $updQuery);
        } else {
            $insQuery = "INSERT INTO krf_deliveryaddress_location 
                         (projectid, psono, sono, clientid, deliveryaddress, dest_latitude, dest_longitude, dest_state, dest_district, dest_city, dest_pincode, distance, status, created_at, updated_at)
                         VALUES ('$projectid', '$orderid', '$orderid', '$clientid', '$delivery_address', '$dest_latitude', '$dest_longitude', '$dest_state', '$dest_district', '$dest_city', '$dest_pincode', '$distance', 'Pending', NOW(), NOW())";
            mysqli_query($krfconn, $insQuery);
        }
    }

    // 3. Save Freight Charges using standard saveFreightCharges function
    $sonoParam = $orderid;
    $psonoParam = $orderid;
    $saveFreightResp = saveFreightCharges($krfconn, $projectid, $psonoParam, $sonoParam, $clientid, $freight_amount, $freight_discountpercent, $freight_remarks, 'Admin', $zerocharge);

    echo json_encode([
        'status' => 'success', 
        'message' => 'Delivery details and freight charges updated successfully.'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
