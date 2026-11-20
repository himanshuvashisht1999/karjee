<?php
require 'connection.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data provided.']);
    exit;
}

$orderid = $krfconn->real_escape_string($data['orderid']);
$lat     = $krfconn->real_escape_string($data['lat']);
$lon     = $krfconn->real_escape_string($data['lon']);
$full_address = $krfconn->real_escape_string($data['full_address']);

// We will update the deliveryaddress column. If latitude/longitude columns exist in your table, 
// they can be added here. For now, we update the address string.
// We also use psono or sono as the order indicator.

$update_query = "UPDATE krf_deliveryaddress_location 
                SET deliveryaddress = '$full_address', 
                    latitude = '$lat', 
                    longitude = '$lon' 
                WHERE psono = '$orderid' OR id = '$orderid'";

if ($krfconn->query($update_query)) {
    if ($krfconn->affected_rows > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Location updated successfully.']);
    } else {
        // If no rows updated, maybe try an insert or just check if it exists
        echo json_encode(['status' => 'error', 'message' => 'No record found with this Order ID to update.']);
    }
} else {
    // If the columns latitude/longitude don't exist, we fallback to just address
    if (strpos($krfconn->error, 'Unknown column') !== false) {
        $update_fallback = "UPDATE krf_deliveryaddress_location 
                           SET deliveryaddress = '$full_address [Coords: $lat, $lon]' 
                           WHERE psono = '$orderid' OR id = '$orderid'";
        if ($krfconn->query($update_fallback)) {
              echo json_encode(['status' => 'success', 'message' => 'Location (Address Only) updated successfully.']);
        } else {
              echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $krfconn->error]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $krfconn->error]);
    }
}
?>
