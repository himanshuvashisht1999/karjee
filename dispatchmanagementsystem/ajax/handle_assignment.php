<?php
include('../../connection.php');

$action = $_POST['action'] ?? '';

if ($action == 'save_assignment') {
    $ref_id = $_POST['ref_id'] ?? '';
    $ref_type = $_POST['ref_type'] ?? 'client';
    $projectid = $_POST['projectid'] ?? 1;
    $vehicle_id = $_POST['vehicle_id'] ?? '';
    $vehicle_no = $_POST['vehicle_no'] ?? '';
    $driver_id = $_POST['driver_id'] ?? '';
    $driver_name = $_POST['driver_name'] ?? '';
    $driver_mobile = $_POST['driver_mobile'] ?? '';
    $dispatch_time = $_POST['dispatch_time'] ?? date('Y-m-d H:i:s');
    $remarks = $_POST['remarks'] ?? '';
    $purpose_to_move = $_POST['purpose_to_move'] ?? '';
    $freight_amount = $_POST['freight_amount'] ?? 0;
    $freight_discountpercent = $_POST['freight_discountpercent'] ?? 0;
    $payable_freightamount = $_POST['payable_freightamount'] ?? 0;
    $items = $_POST['items'] ?? [];


$itemsJson = $_POST['items_json'] ?? '[]';

$items_products = json_decode($itemsJson, true);



//|| empty($items)
    if (empty($ref_id) || empty($vehicle_id) || empty($driver_id) || empty($items_products) ) {
        echo json_encode(['status' => 'error', 'message' => 'Required fields missing.']);
        exit;
    }

    // Use existing trip ID if provided (for updates/autofill modification), otherwise generate new
    $trip_id = $_POST['trip_id'] ?? '';
    $is_edit = $_POST['is_edit'] ?? '0';
    if (empty($trip_id)) {
        $trip_id = 'TRIP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
    } else { 
        $gp_file = "../../attachementfile/dispatch/gate_pass_$trip_id.pdf";
        $dn_file = "../../attachementfile/dispatch/delivery_note_$trip_id.pdf";
        if (file_exists($gp_file)) {
            @unlink($gp_file);
        }
        if (file_exists($dn_file)) {
            @unlink($dn_file);
        }
        // Also delete the final sale invoice PDF for this trip if it exists
        $si_files = glob("../../attachementfile/invoice/*/*INV-$trip_id.pdf");
        if ($si_files) {
            foreach ($si_files as $f) {
                if (file_exists($f)) {
                    @unlink($f);
                }
            }
        }
        // Also delete any existing freight invoice PDFs related to the psonos / stockorders of this trip
        $p_res = $krfconn->query("SELECT psono, stockorder_no FROM krf_vehicle_travel_history WHERE trip_id = '$trip_id'");
        if ($p_res) {
            while ($p_row = $p_res->fetch_assoc()) {
                $ref = !empty($p_row['psono']) ? $p_row['psono'] : $p_row['stockorder_no'];
                $ref_clean = str_replace("/", "_", $ref);
                $fi_files = glob("../../attachementfile/freight_invoice/*/*" . $ref_clean . "*.pdf");
                if ($fi_files) {
                    foreach ($fi_files as $f) {
                        if (file_exists($f)) {
                            @unlink($f);
                        }
                    }
                }
            }
        }
        if ($is_edit == '1') {
            // Delete all existing items for this trip before re-inserting the updated set
            $krfconn->query("DELETE FROM krf_vehicle_travel_history WHERE trip_id = '$trip_id'");
        }
    }
    $success = true;
    $errors = [];
    // Map reference ID to correct column
    $col_name = ($ref_type == 'internal') ? 'stockorder_no' : 'psono';
    $ref_val = $krfconn->real_escape_string($ref_id);

    $from_location_id = $_POST['from_location_id'] ?? null;
    $from_location_name = $_POST['from_location_name'] ?? null;
    $from_lat = $_POST['from_lat'] ?? null;
    $from_lon = $_POST['from_lon'] ?? null;
    $to_location_id = $_POST['to_location_id'] ?? null;
    $to_location_name = $_POST['to_location_name'] ?? null;
    $to_lat = $_POST['to_lat'] ?? null;
    $to_lon = $_POST['to_lon'] ?? null;
    $distance_km = $_POST['distance_km'] ?? 0;

    $dispatchtype = ($ref_type == 'internal') ? 'Internal' : 'Outside';

    //foreach ($items as $item_id => $data) {
    foreach ($items_products as $data) {
        $qty = $data['loading_plants']; //$data['qty'];
        $tray_count = $data['loading_trays'];//$data['tray_count'] ?? 0;
        $productid = $data['productid'];
        

        $roottype = trim($data['roottype']);
       // $from_location_id = trim($data['locationid']); //source
        $stockorder_no = trim($data['stockorder_no']);
        $sowinglotno_internal= trim($data['sowinglotno_internal']);
        $polyhouseid= trim($data['polyhouseid']);
        $polyhousename= trim($data['polyhousename']);
         
        $item_ref_val = $ref_val;
        if ($ref_type == 'internal' && !empty($data['stockorder_no'])) {
            $item_ref_val = $krfconn->real_escape_string($data['stockorder_no']);
        } else if ($ref_type == 'client' && !empty($data['psono'])) {
            $item_ref_val = $krfconn->real_escape_string($data['psono']);
        }
        
        $purpose_to_move_escaped = $krfconn->real_escape_string($purpose_to_move);
        $polyhousename = $krfconn->real_escape_string($polyhousename);
        
        // Insert into krf_vehicle_travel_history
        // Note: Using 'Dispatched' as initial status for gate pass generation readiness
          $query = "INSERT INTO krf_vehicle_travel_history (
                    projectid, trip_id,stockorder_no, productid, 
                    vehicle_id, vehicle_no, driver_name, driver_mobileno, 
                    send_traycount, send_totalplantcount, send_remark, purpose_to_move, freight_amount, 
                    freight_discountpercent, payable_freightamount, 
                    dispatch_time, status, created_at,
                    from_location_id, from_location_name, from_lat, from_lon,
                    to_location_id, to_location_name, to_lat, to_lon,
                    distance_km, distance_source, dispatchtype ,roottype ,sowinglotno_internal ,polyhouseid,polyhousename
                  ) VALUES (
                    '$projectid', '$trip_id', '$stockorder_no', '$productid',
                    '$vehicle_id', '$vehicle_no', '$driver_name', '$driver_mobile',
                    '$tray_count', '$qty', '$remarks', '$purpose_to_move_escaped', '$freight_amount', 
                    '$freight_discountpercent', '$payable_freightamount', 
                    '$dispatch_time', 'Dispatched', NOW(),
                    " . ($from_location_id !== null && $from_location_id !== '' ? "'$from_location_id'" : "NULL") . ",
                    " . ($from_location_name !== null ? "'$from_location_name'" : "NULL") . ",
                    " . ($from_lat !== null && $from_lat !== '' ? "'$from_lat'" : "NULL") . ",
                    " . ($from_lon !== null && $from_lon !== '' ? "'$from_lon'" : "NULL") . ",
                    " . ($to_location_id !== null && $to_location_id !== '' ? "'$to_location_id'" : "NULL") . ",
                    " . ($to_location_name !== null ? "'$to_location_name'" : "NULL") . ",
                    " . ($to_lat !== null && $to_lat !== '' ? "'$to_lat'" : "NULL") . ",
                    " . ($to_lon !== null && $to_lon !== '' ? "'$to_lon'" : "NULL") . ",
                    '$distance_km', 'GPS', '$dispatchtype' , '$roottype' , '$sowinglotno_internal' ,  '$polyhouseid' , '$polyhousename'
                  )";
        
        if (!$krfconn->query($query)) {
            $success = false;
            $errors[] = $krfconn->error;
        }
    }

    if ($success) {
        echo json_encode(['status' => 'success', 'trip_id' => $trip_id]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Saving failed: ' . implode(", ", $errors)]);
    }
}
?>
