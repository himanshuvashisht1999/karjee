<?php
include('../../connection.php');

$action = $_POST['action'] ?? '';

if ($action == 'complete_delivery') {
    $trip_id = $_POST['trip_id'] ?? '';
    $received_qty = $_POST['received_qty'] ?? 0;
    $damage_qty = $_POST['damage_qty'] ?? 0;
    $receiver_name = $_POST['receiver_name'] ?? '';

    if (empty($trip_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Trip ID missing.']);
        exit;
    }

    // Handle File Upload
    $proof_path = '';
    if (isset($_FILES['proof_file']) && $_FILES['proof_file']['error'] == 0) {
        $dir = '../../attachementfile/dispatch/proof/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        
        $filename = 'proof_' . $trip_id . '_' . time() . '.' . pathinfo($_FILES['proof_file']['name'], PATHINFO_EXTENSION);
        if (move_uploaded_file($_FILES['proof_file']['tmp_name'], $dir . $filename)) {
            $proof_path = 'attachementfile/dispatch/proof/' . $filename;
        }
    }

    // Update krf_vehicle_travel_history
    // Note: Since a trip might have multiple items, we update all items for that trip_id
    $query = "UPDATE krf_vehicle_travel_history 
              SET status = 'Delivered', 
                  received_totalplantcount = '$received_qty', 
                  damaged_qty = '$damage_qty',
                  received_remark = '$receiver_name',
                  delivered_time = NOW(),
                  updated_at = NOW()
              WHERE trip_id = '$trip_id'";

    if ($krfconn->query($query)) {
        echo json_encode(['status' => 'success', 'message' => 'Delivery completed successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Update failed: ' . $krfconn->error]);
    }
}
?>
