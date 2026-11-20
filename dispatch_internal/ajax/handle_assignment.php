<?php
include('../../connection.php');

 
//$ref_id = trim((string) ($_POST['ref_id'] ?? ''));
$ref_type = trim((string) ($_POST['ref_type'] ?? 'internal'));
$trip_id = $_POST['trip_id'] ?? ''; 

$projectid = (int) ($_POST['projectid'] ?? 0);

$vehicle_id =   ($_POST['vehicle_id'] ?? 0);
$vehicle_no = trim((string) ($_POST['vehicle_no'] ?? ''));

$driver_id =   ($_POST['driver_id'] ?? 0);
$driver_name = trim((string) ($_POST['driver_name'] ?? ''));
$driver_mobile = trim((string) ($_POST['driver_mobile'] ?? ''));

$dispatch_time = trim((string) ($_POST['dispatch_time'] ?? ''));

if ($dispatch_time === '') {
    $dispatch_time = date('Y-m-d H:i:s');
} else {
    $dispatch_time = date(
        'Y-m-d H:i:s',
        strtotime($dispatch_time)
    );
}

$remarks = trim((string) ($_POST['remarks'] ?? ''));
$purpose_to_move = trim((string) ($_POST['purpose_to_move'] ?? ''));

$freight_amount = (float) ($_POST['freight_amount'] ?? 0);
$freight_discountpercent = (float) ($_POST['freight_discountpercent'] ?? 0);
$payable_freightamount = (float) ($_POST['payable_freightamount'] ?? 0);

$itemsJson = $_POST['items_json'] ?? '[]';
$items_products = json_decode($itemsJson, true);

 

if (!is_array($items_products)) {
    $items_products = [];
}

$from_location_id = ($_POST['from_location_id'] ?? 0);
$from_location_name = trim((string) ($_POST['from_location_name'] ?? ''));
$from_lat = (float) ($_POST['from_lat'] ?? 0);
$from_lon = (float) ($_POST['from_lon'] ?? 0);

$to_location_id =   ($_POST['to_location_id'] ?? '');
$to_location_name = trim((string) ($_POST['to_location_name'] ?? ''));
$to_lat = (float) ($_POST['to_lat'] ?? 0);
$to_lon = (float) ($_POST['to_lon'] ?? 0);

$distance_km = (float) ($_POST['distance_km'] ?? 0);

$dispatchtype = 'Internal';
 
 

//|| empty($items)
    if (empty($from_location_id) || empty($to_location_id) || empty($vehicle_id) || empty($driver_id)  ) {
        echo json_encode(['status' => 'error', 'message' => 'Required fields missing.']);
        exit;
    }

    // Use existing trip ID if provided (for updates/autofill modification), otherwise generate new
    
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
         
            // Delete all existing items for this trip before re-inserting the updated set
            $krfconn->query("DELETE FROM krf_vehicle_travel_history WHERE trip_id = '$trip_id'");
        
    }
    $success = true;
    $errors = [];
    $insertSql = " INSERT INTO krf_vehicle_travel_history (
            projectid,  trip_id,  stockorder_no,  productid, vehicle_id,  vehicle_no,
            driver_name, driver_mobileno,  send_traycount, send_totalplantcount,
            send_remark, purpose_to_move,  freight_amount,  freight_discountpercent,   payable_freightamount,   dispatch_time,  status,  created_at,  from_location_id,  from_location_name, from_lat, from_lon,
            to_location_id,  to_location_name, to_lat, to_lon,  distance_km,
            distance_source,   dispatchtype, roottype,  sowinglotno_internal,
            polyhouseid, polyhousename,  barcode, depatchlocationid,  despatchlocationname,  driverid
        ) VALUES (  ?, ?, ?, ?,   ?, ?, ?, ?,
            ?, ?, ?, ?, ?,  ?, ?,  ?, 'Dispatched', NOW(),
            ?, ?, ?, ?, ?, ?, ?, ?,  ?, 'GPS', ?, ?, ?, ?, ?, ?, ?, ?, ?
        ) ";

    $updateSql = "UPDATE krf_polyhouse_plant_details
        SET  status = 'Internal Dispatch',  despatchlocation = ?,  despatchlocationid = ?,
            tripid = ?  WHERE barcode = ?  AND projectid = ?  ";
    $insertStmt = $krfconn->prepare($insertSql);
    $updateStmt = $krfconn->prepare($updateSql);
    if (!$insertStmt) {
        throw new RuntimeException(
            'Insert preparation failed: ' . $krfconn->error
        );
    }
    if (!$updateStmt) {
        $insertStmt->close();
        throw new RuntimeException(
            'Update preparation failed: ' . $krfconn->error
        );
    }
    $krfconn->begin_transaction();
    try {
        foreach ($items_products as $index => $data) {
            $qty = (int) ($data['loading_plants'] ?? 0);
            $trayCount = (int) ($data['loading_trays'] ?? 0);
            $productId = (int) ($data['productid'] ?? 0);

            $rootType = trim((string) ($data['roottype'] ?? ''));
            $stockOrderNo = trim((string) ($data['stockorder_no'] ?? ''));
            $sowingLotNo = trim((string) ($data['sowinglotno_internal'] ?? ''));
            $polyhouseId = trim((string) ($data['polyhouseid'] ?? ''));
            $polyhouseName = trim((string) ($data['polyhousename'] ?? ''));
            $barcodeNo = trim((string) ($data['barcode_no'] ?? ''));

            $despatchLocationId = trim(
                (string) ($data['despatch_location_id'] ?? '')
            );

            $despatchLocationName = trim(
                (string) ($data['despatch_location_name'] ?? '')
            );

            if ($barcodeNo === '') {
                throw new InvalidArgumentException(
                    'Barcode is missing for item ' . ($index + 1) . '.'
                );
            }

            if ($productId <= 0) {
                throw new InvalidArgumentException(
                    'Invalid product ID for barcode ' . $barcodeNo . '.'
                );
            }

            if ($trayCount <= 0 || $qty <= 0) {
                throw new InvalidArgumentException(
                    'Invalid tray or plant quantity for barcode ' . $barcodeNo . '.'
                );
            }

            $insertStmt->bind_param(
                'ssssssssssssssssssssssssssssssssss',
                $projectid,
                $trip_id,
                $stockOrderNo,
                $productId,
                $vehicle_id,
                $vehicle_no,
                $driver_name,
                $driver_mobile,
                $trayCount,
                $qty,
                $remarks,
                $purpose_to_move,
                $freight_amount,
                $freight_discountpercent,
                $payable_freightamount,
                $dispatch_time,
                $from_location_id,
                $from_location_name,
                $from_lat,
                $from_lon,
                $to_location_id,
                $to_location_name,
                $to_lat,
                $to_lon,
                $distance_km,
                $dispatchtype,
                $rootType,
                $sowingLotNo,
                $polyhouseId,
                $polyhouseName,
                $barcodeNo,
                $despatchLocationId,
                $despatchLocationName,
                $driver_id
            );

            if (!$insertStmt->execute()) {
                throw new RuntimeException(
                    'Travel-history insert failed for barcode ' .
                    $barcodeNo . ': ' .
                    $insertStmt->error
                );
            }

            $updateStmt->bind_param(
                'ssssi',
                $despatchLocationName,
                $despatchLocationId,
                $trip_id,
                $barcodeNo,
                $projectid
            );

            if (!$updateStmt->execute()) {
                throw new RuntimeException(
                    'Plant-detail update failed for barcode ' .
                    $barcodeNo . ': ' .
                    $updateStmt->error
                );
            }

            if ($updateStmt->affected_rows === 0) {
                throw new RuntimeException(
                    'No plant-detail row was updated for barcode ' .
                    $barcodeNo . '.'
                );
            }
        }

        $krfconn->commit();

    } catch (Throwable $e) {
        $krfconn->rollback();

        $success = false;
        $errors[] = $e->getMessage();

    } finally {
        $insertStmt->close();
        $updateStmt->close();
    }

    if ($success) {
        echo json_encode(['status' => 'success', 'trip_id' => $trip_id]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Saving failed: ' . implode(", ", $errors)]);
    }
 ?>
