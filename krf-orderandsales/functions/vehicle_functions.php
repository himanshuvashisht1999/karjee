<?php
function getVehicleData($conn, $projectid, $vehicleid = null)
{
    $data = [];
    $sql = "SELECT * FROM krf_vehicle_master WHERE projectid = ?";
    if ($vehicleid) {
        $sql .= " AND vehicleid = ?";
    }
    $sql .= " ORDER BY vehiclename ASC";

    $stmt = $conn->prepare($sql);
    if ($vehicleid) {
        $stmt->bind_param("ii", $projectid, $vehicleid);
    } else {
        $stmt->bind_param("i", $projectid);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    return $data;
}

function saveVehicleMaster($conn, $data)
{
    $projectid = (int) ($data['projectid'] ?? 0);
    $vehicleid = $data['vehicleid'] ?? '';
    $vehiclename = trim($data['vehiclename'] ?? '');
    $vehicleno = trim($data['vehicleno'] ?? '');
    $vehicleownername = trim($data['vehicleownername'] ?? '');
    $traycapacity = (int) ($data['traycapacity'] ?? 0);
    $plantscapacity = (int) ($data['plantscapacity'] ?? 0);
    $status = $data['status'] ?? 'Active';

    if (empty($vehiclename)) {
        return ["status" => "error", "message" => "Vehicle Name is mandatory."];
    }

    if (!empty($vehicleid)) {
        $stmt = $conn->prepare("UPDATE krf_vehicle_master SET vehiclename = ?, vehicleno = ?, vehicleownername = ?, traycapacity = ?, plantscapacity = ?, status = ? WHERE vehicleid = ? AND projectid = ?");
        if (!$stmt)
            return ["status" => "error", "message" => "Prepare failed: " . $conn->error];
        $stmt->bind_param("sssiisii", $vehiclename, $vehicleno, $vehicleownername, $traycapacity, $plantscapacity, $status, $vehicleid, $projectid);
    } else {
        $stmt = $conn->prepare("INSERT INTO krf_vehicle_master (projectid, vehiclename, vehicleno, vehicleownername, traycapacity, plantscapacity, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt)
            return ["status" => "error", "message" => "Prepare failed: " . $conn->error];
        $stmt->bind_param("isssiis", $projectid, $vehiclename, $vehicleno, $vehicleownername, $traycapacity, $plantscapacity, $status);
    }

    if ($stmt->execute()) {
        return ["status" => "success", "message" => "Vehicle details saved successfully."];
    } else {
        return ["status" => "error", "message" => "Database error: " . $stmt->error];
    }
}

function deleteVehicle($conn, $vehicleid, $projectid)
{
    $stmt = $conn->prepare("DELETE FROM krf_vehicle_master WHERE vehicleid = ? AND projectid = ?");
    $stmt->bind_param("ii", $vehicleid, $projectid);
    if ($stmt->execute()) {
        return ["status" => "success", "message" => "Vehicle deleted successfully."];
    } else {
        return ["status" => "error", "message" => "Error deleting vehicle."];
    }
}

function getFreightMatrix($conn, $projectid)
{
    // This will return an array grouped by distance ranges
    $ranges = [];
    $sql = "SELECT DISTINCT dist_min, dist_max FROM krf_freight_master WHERE projectid = ? ORDER BY dist_min ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $projectid);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $ranges[] = $r;
    }

    $vehicles = getVehicleData($conn, $projectid);

    $matrix = [];
    foreach ($ranges as $range) {
        $row = $range;
        $row['charges'] = [];

        // Fetch calculationtype for this distance slab
        $calc_stmt = $conn->prepare("SELECT calculationtype FROM krf_freight_master WHERE dist_min = ? AND dist_max = ? AND projectid = ? LIMIT 1");
        $calc_stmt->bind_param("iii", $range['dist_min'], $range['dist_max'], $projectid);
        $calc_stmt->execute();
        $calc_res = $calc_stmt->get_result()->fetch_assoc();
        $row['calculationtype'] = $calc_res['calculationtype'] ?? 'fixed';

        foreach ($vehicles as $v) {
            $sql = "SELECT chargeid, freight_charge, calculationtype FROM krf_freight_master WHERE dist_min = ? AND dist_max = ? AND vehicleid = ? AND projectid = ?";
            $stm = $conn->prepare($sql);
            $stm->bind_param("iiii", $range['dist_min'], $range['dist_max'], $v['vehicleid'], $projectid);
            $stm->execute();
            $result = $stm->get_result()->fetch_assoc();
            $row['charges'][$v['vehicleid']] = $result ? $result['freight_charge'] : 0;
            $row['chargeids'][$v['vehicleid']] = $result ? $result['chargeid'] : null;
        }
        $matrix[] = $row;
    }
    return ["matrix" => $matrix, "vehicles" => $vehicles];
}

function saveFreightCharge($conn, $data)
{
    $projectid = $data['projectid'] ?? 0;
    $dist_min = $data['dist_min'];
    $dist_max = $data['dist_max'];
    $vehicleid = $data['vehicleid'];
    $charge = $data['charge'];
    $calculationtype = !empty($data['calculationtype']) ? strtolower($data['calculationtype']) : 'fixed';
    if (!in_array($calculationtype, ['fixed', 'rate'])) {
        $calculationtype = 'fixed';
    }

    // Check if exists
    $stmt = $conn->prepare("SELECT chargeid FROM krf_freight_master WHERE dist_min = ? AND dist_max = ? AND vehicleid = ? AND projectid = ?");
    $stmt->bind_param("iiii", $dist_min, $dist_max, $vehicleid, $projectid);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    if ($res) {
        $upd = $conn->prepare("UPDATE krf_freight_master SET freight_charge = ?, calculationtype = ? WHERE chargeid = ?");
        $upd->bind_param("dsi", $charge, $calculationtype, $res['chargeid']);
        $upd->execute();
    } else {
        $ins = $conn->prepare("INSERT INTO krf_freight_master (projectid, dist_min, dist_max, vehicleid, freight_charge, calculationtype) VALUES (?, ?, ?, ?, ?, ?)");
        $ins->bind_param("iiiids", $projectid, $dist_min, $dist_max, $vehicleid, $charge, $calculationtype);
        $ins->execute();
    }
    return ["status" => "success"];
}

function getFreightSuggestion($conn, $projectid, $km, $totalQty = 0)
{
    $km = (float) $km;
    $projectid = (int) $projectid;
    $totalQty = (float) $totalQty;
    $vData = [];
    $range = null;
    $calcType="";

    // 1. Try to find an exact matching slab
    $stmt = $conn->prepare("SELECT DISTINCT dist_min, dist_max FROM krf_freight_master WHERE ? BETWEEN dist_min AND dist_max AND projectid = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("di", $km, $projectid);
        $stmt->execute();
        $range = $stmt->get_result()->fetch_assoc();
    }

    // 2. If no exact slab, look for an "Above X km" slab (where max dist is >= 9000)
    if (empty($range)) {
        $stmt = $conn->prepare("SELECT DISTINCT dist_min, dist_max FROM krf_freight_master WHERE ? >= dist_min AND dist_max >= 9000 AND projectid = ? ORDER BY dist_min DESC LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("di", $km, $projectid);
            $stmt->execute();
            $range = $stmt->get_result()->fetch_assoc();
        }
    }

    // 2b. If still no slab, fallback to highest defined range for this project if distance exceeds its min
    if (empty($range)) {
        $stmt = $conn->prepare("SELECT DISTINCT dist_min, dist_max FROM krf_freight_master WHERE projectid = ? ORDER BY dist_max DESC LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $projectid);
            $stmt->execute();
            $highestRange = $stmt->get_result()->fetch_assoc();
            if ($highestRange && $km >= (float)$highestRange['dist_min']) {
                $range = $highestRange;
            }
        }
    }

    // 3. Fetch all active vehicles and their charges for this specific range
    $dmin = $range ? (int) $range['dist_min'] : 0;
    $dmax = $range ? (int) $range['dist_max'] : 0;

    $sql = "SELECT v.vehicleid, v.vehiclename, v.vehicleno, v.vehicleownername, v.traycapacity, v.plantscapacity, IFNULL(f.freight_charge, 0) as raw_charge, IFNULL(f.calculationtype, 'fixed') as calculationtype 
            FROM krf_vehicle_master v 
            LEFT JOIN krf_freight_master f ON v.vehicleid = f.vehicleid AND f.dist_min = ? AND f.dist_max = ? AND f.projectid = ? 
            WHERE v.projectid = ? AND v.status = 'Active' 
            ORDER BY v.plantscapacity ASC";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("iiii", $dmin, $dmax, $projectid, $projectid);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $rawCharge = (float)$row['raw_charge'];
            $calcType = $row['calculationtype'] ?? 'fixed';

            // Fallback: If specific vehicle registration has no direct freight slab, check matching vehicle type by name
            if ($rawCharge <= 0 && !empty($row['vehiclename'])) {
                $vName = trim($row['vehiclename']);
                $fbStmt = $conn->prepare("
                    SELECT f2.freight_charge, f2.calculationtype 
                    FROM krf_freight_master f2
                    JOIN krf_vehicle_master vm2 ON f2.vehicleid = vm2.vehicleid
                    WHERE LOWER(TRIM(vm2.vehiclename)) = LOWER(?) 
                      AND f2.dist_min = ? AND f2.dist_max = ? AND f2.projectid = ? AND f2.freight_charge > 0
                    LIMIT 1
                ");
                if ($fbStmt) {
                    $fbStmt->bind_param("siii", $vName, $dmin, $dmax, $projectid);
                    $fbStmt->execute();
                    $fbRes = $fbStmt->get_result()->fetch_assoc();
                    $fbStmt->close();
                    if ($fbRes) {
                        $rawCharge = (float)$fbRes['freight_charge'];
                        $calcType = $fbRes['calculationtype'] ?? 'fixed';
                    }
                }
            }

            if ($calcType === 'rate') {
                $row['charge'] = round($rawCharge * $km, 2);
            } else {
                $row['charge'] = $rawCharge;
            }
            $vData[] = $row;
        }
    }

    // 4. Automatic vehicle selection logic based on distance & quantity
    $bestVehicle = null;
    $recommendedCharge = 0;
    $tripsNeeded = 1;

    if (!empty($vData)) {
        $validVehicles = array_filter($vData, function($v) {
            return (float)$v['charge'] > 0;
        });

        if (!empty($validVehicles)) {
            if ($totalQty > 0) {
                $capableVehicles = [];
                foreach ($validVehicles as $v) {
                    $cap = (float)$v['plantscapacity'];
                    if ($cap >= $totalQty) {
                        $capableVehicles[] = $v;
                    }
                }

                if (!empty($capableVehicles)) {
                    // Select vehicle with smallest plantscapacity that fits totalQty
                    usort($capableVehicles, function($a, $b) {
                        if ((float)$a['plantscapacity'] == (float)$b['plantscapacity']) {
                            return (float)$a['charge'] <=> (float)$b['charge'];
                        }
                        return (float)$a['plantscapacity'] <=> (float)$b['plantscapacity'];
                    });
                    $bestVehicle = $capableVehicles[0];
                    $recommendedCharge = (float)$bestVehicle['charge'];
                    $tripsNeeded = 1;
                } else {
                    // If totalQty exceeds all vehicle capacities, choose vehicle with max capacity and calculate trips needed
                    $maxCapVehicle = null;
                    $maxCap = 0;
                    foreach ($validVehicles as $v) {
                        $cap = (float)$v['plantscapacity'];
                        if ($cap > $maxCap) {
                            $maxCap = $cap;
                            $maxCapVehicle = $v;
                        }
                    }
                    if ($maxCapVehicle && $maxCap > 0) {
                        $bestVehicle = $maxCapVehicle;
                        $tripsNeeded = (int)ceil($totalQty / $maxCap);
                        $recommendedCharge = (float)$bestVehicle['charge'] * $tripsNeeded;
                    } else {
                        $bestVehicle = reset($validVehicles);
                        $recommendedCharge = (float)$bestVehicle['charge'];
                    }
                }
            } else {
                $bestVehicle = reset($validVehicles);
                $recommendedCharge = (float)$bestVehicle['charge'];
            }
        }
    }

    return [
        "status" => "success",
        "km" => $km,
        "total_qty" => $totalQty,
        "projectid" => $projectid,
        "range_found" => $range ? true : false,
        "range_label" => ($range ? ($range['dist_max'] >= 9000 ? "Above " . $range['dist_min'] . " km" : $range['dist_min'] . " - " . $range['dist_max'] . " km") : "No Slab Defined for " . $km . "km"),
        "best_vehicle" => $bestVehicle,
        "recommended_charge" => $recommendedCharge,
        "trips_needed" => $tripsNeeded,
        "suggestions" => $vData,
        "calcType"    =>$calcType  
    ];
}


function insertVehicleTravelDetails($conn, $data)
{
    // 🔹 Check duplicate
    $check = $conn->prepare("
        SELECT id 
        FROM krf_vehicle_travel_history 
        WHERE stockorder_no = ? AND trip_id = ?
        LIMIT 1
    ");
    $check->bind_param("ss", $data['stockorder_no'], $data['trip_id']);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows > 0) {
        return [
            "status" => "error",
            "message" => "Duplicate entry for this trip and stock order"
        ];
    }

    // 🔹 Insert
    $sql = "INSERT INTO krf_vehicle_travel_history (
        trip_id, stockorder_no, productid, roottype,
        send_traycount, send_traycavity, send_totalplantcount, send_remark,
        vehicle_id, vehicle_no, driver_name, driver_mobileno,
        from_location_id, from_location_name, from_lat, from_lon,
        to_location_id, to_location_name, to_lat, to_lon,
        distance_km, freight_amount, movefor,
        created_by
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "sssssiiisssssissddssssss",
        $data['trip_id'],
        $data['stockorder_no'],
        $data['productid'],
        $data['roottype'],
        $data['send_traycount'],
        $data['send_traycavity'],
        $data['send_totalplantcount'],
        $data['send_remark'],
        $data['vehicle_id'],
        $data['vehicle_no'],
        $data['driver_name'],
        $data['driver_mobileno'],
        $data['from_location_id'],
        $data['from_location_name'],
        $data['from_lat'],
        $data['from_lon'],
        $data['to_location_id'],
        $data['to_location_name'],
        $data['to_lat'],
        $data['to_lon'],
        $data['distance_km'],
        $data['freight_amount'],
        $data['movefor'],
        $data['created_by']
    );

    if ($stmt->execute()) {
        return ["status" => "success", "id" => $stmt->insert_id];
    }

    return ["status" => "error", "message" => $stmt->error];
}

function updateVehicleDetails($conn, $id, $vehicle_id, $vehicle_no)
{
    $sql = "UPDATE krf_vehicle_travel_history 
            SET vehicle_id = ?, vehicle_no = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isi", $vehicle_id, $vehicle_no, $id);

    if ($stmt->execute()) {
        return ["status" => "success"];
    }

    return ["status" => "error", "message" => $stmt->error];
}

function updateDriverDetails($conn, $id, $driver_name, $driver_mobileno)
{
    $sql = "UPDATE krf_vehicle_travel_history 
            SET driver_name = ?, driver_mobileno = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $driver_name, $driver_mobileno, $id);

    if ($stmt->execute()) {
        return ["status" => "success"];
    }

    return ["status" => "error", "message" => $stmt->error];
}
function dispatchVehicle($conn, $id, $loginid)
{
    $sql = "UPDATE krf_vehicle_travel_history 
            SET status = 'Dispatched',
                dispatch_time = NOW(),
                created_by = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $loginid, $id);

    if ($stmt->execute()) {
        return ["status" => "success", "message" => "Vehicle dispatched"];
    }

    return ["status" => "error", "message" => $stmt->error];
}
function receiveVehicle($conn, $id, $received_traycount, $received_totalplantcount, $remark)
{
    $sql = "UPDATE krf_vehicle_travel_history 
            SET received_traycount = ?,
                received_totalplantcount = ?,
                received_remark = ?,
                delivered_time = NOW(),
                status = 'Delivered'
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisi", $received_traycount, $received_totalplantcount, $remark, $id);

    if ($stmt->execute()) {
        return ["status" => "success"];
    }

    return ["status" => "error", "message" => $stmt->error];
}

if (!function_exists('getOrCreateTripIdForVehicle')) {
function getOrCreateTripIdForVehicle($conn, $projectId, $vehicleId, $vehicleNo, $deliveryDate, $newOrderPlants) {
    $vehicleId = intval($vehicleId);
    $projectId = intval($projectId);
    $newOrderPlants = intval($newOrderPlants);
    $deliveryDate = trim($deliveryDate);
    if (empty($deliveryDate)) {
        $deliveryDate = date('Y-m-d');
    }

    // 1. Fetch vehicle capacity & owner from krf_vehicle_master
    $vCap = 0;
    $vOwner = '';
    $vName = '';
    $vNo = $vehicleNo;
    
    $vStmt = $conn->prepare("SELECT vehicleid, vehiclename, vehicleno, vehicleownername, plantscapacity, traycapacity FROM krf_vehicle_master WHERE (vehicleid = ? OR vehicleno = ? OR vehiclename = ?) AND (projectid = ? OR projectid = 0 OR ? = 0) LIMIT 1");
    if ($vStmt) {
        $vStmt->bind_param("issii", $vehicleId, $vehicleNo, $vehicleNo, $projectId, $projectId);
        $vStmt->execute();
        $vRes = $vStmt->get_result()->fetch_assoc();
        $vStmt->close();
        if ($vRes) {
            $vCap = intval($vRes['plantscapacity'] ?? 0);
            $vOwner = trim($vRes['vehicleownername'] ?? '');
            $vName = trim($vRes['vehiclename'] ?? '');
            if (empty($vNo)) $vNo = trim($vRes['vehicleno'] ?? '');
            if ($vehicleId <= 0) $vehicleId = intval($vRes['vehicleid']);
        }
    }

    // 2. Check existing active trip for this vehicle on this delivery date in krf_vehicletrip
    $existingTrip = null;
    $tStmt = $conn->prepare("
        SELECT vt.trip_id, vt.trip_no, vt.vehicle_id, vt.vehicle_no, vt.driver_name, vt.driver_mobile_no, vt.driver_id,
               COALESCE(SUM(vth.send_total_plant_count), 0) AS trip_total_plants,
               COUNT(DISTINCT vth.dispatch_location_id) AS location_count,
               GROUP_CONCAT(DISTINCT vth.dispatch_location_name SEPARATOR ' | ') AS destinations
        FROM krf_vehicletrip vt
        LEFT JOIN krf_vehicle_travel_history vth ON vt.trip_id = vth.trip_id
        WHERE (vt.project_id = ? OR vt.project_id = '0' OR ? = 0)
          AND (vt.vehicle_id = ? OR (vt.vehicle_no = ? AND ? != ''))
          AND (vt.delivery_date = ? OR vt.move_for = ?)
          AND vt.status NOT IN ('Cancelled', 'Delivered')
        GROUP BY vt.trip_id, vt.trip_no, vt.vehicle_id, vt.vehicle_no, vt.driver_name, vt.driver_mobile_no, vt.driver_id
        ORDER BY vt.trip_id DESC
        LIMIT 1
    ");
    if ($tStmt) {
        $tStmt->bind_param("iiissss", $projectId, $projectId, $vehicleId, $vNo, $vNo, $deliveryDate, $deliveryDate);
        $tStmt->execute();
        $existingTrip = $tStmt->get_result()->fetch_assoc();
        $tStmt->close();
    }

    $tripNo = '';
    $numericTripId = 0;
    $isNewTrip = false;
    $currentTripPlants = 0;
    $locationCount = 0;

    if ($existingTrip && !empty($existingTrip['trip_no'])) {
        $currentTripPlants = intval($existingTrip['trip_total_plants'] ?? 0);
        $locationCount = intval($existingTrip['location_count'] ?? 0);
        $remainingCapacity = ($vCap > 0) ? ($vCap - $currentTripPlants) : 999999;
        
        if ($vCap <= 0 || $remainingCapacity >= $newOrderPlants) {
            $tripNo = $existingTrip['trip_no'];
            $numericTripId = intval($existingTrip['trip_id']);
            $isNewTrip = false;
        } else {
            $tripNo = 'TRIP-' . date('Ymd', strtotime($deliveryDate)) . '-' . strtoupper(substr(uniqid(), -4));
            $isNewTrip = true;
        }
    } else {
        $tripNo = 'TRIP-' . date('Ymd', strtotime($deliveryDate)) . '-' . strtoupper(substr(uniqid(), -4));
        $isNewTrip = true;
    }

    return [
        'trip_id' => $tripNo,
        'trip_no' => $tripNo,
        'numeric_trip_id' => $numericTripId,
        'is_new_trip' => $isNewTrip,
        'vehicle_id' => $vehicleId,
        'vehicle_name' => $vName,
        'vehicle_no' => $vNo,
        'vehicle_owner' => $vOwner,
        'plant_capacity' => $vCap,
        'current_trip_plants' => $currentTripPlants,
        'new_order_plants' => $newOrderPlants,
        'total_projected_plants' => $currentTripPlants + $newOrderPlants,
        'remaining_capacity' => ($vCap > 0) ? max(0, $vCap - ($currentTripPlants + $newOrderPlants)) : 0,
        'capacity_exceeded' => ($vCap > 0 && ($currentTripPlants + $newOrderPlants) > $vCap),
        'location_count' => $locationCount,
        'destinations' => $existingTrip['destinations'] ?? '',
        'existing_driver_name' => $existingTrip['driver_name'] ?? '',
        'existing_driver_mobile' => $existingTrip['driver_mobile_no'] ?? '',
        'existing_driver_id' => $existingTrip['driver_id'] ?? ''
    ];
}
}

if (!function_exists('upsertVehicleTripAndHistory')) {
function upsertVehicleTripAndHistory($conn, $tripData, $items = []) {
    $tripNo = trim($tripData['trip_no'] ?? $tripData['trip_id'] ?? '');
    $projectId = strval($tripData['project_id'] ?? $tripData['projectid'] ?? '0');
    $vendorId = intval($tripData['vehicle_vendor_id'] ?? $tripData['vendor_id'] ?? 0);
    $vehicleId = intval($tripData['vehicle_id'] ?? $tripData['vehicleid'] ?? 0);
    $vehicleNo = trim($tripData['vehicle_no'] ?? $tripData['vehicleno'] ?? '');
    $driverId = trim($tripData['driver_id'] ?? $tripData['driverid'] ?? '');
    $driverName = trim($tripData['driver_name'] ?? $tripData['drivername'] ?? '');
    $driverMobile = trim($tripData['driver_mobile_no'] ?? $tripData['driver_mobile'] ?? $tripData['drivercontactno'] ?? '');
    $fromLocId = trim($tripData['from_location_id'] ?? ("NURSERY-" . $projectId));
    $fromLocName = trim($tripData['from_location_name'] ?? "KRF Main Production & Dispatch Center");
    $fromLat = floatval($tripData['from_lat'] ?? 23.1234567);
    $fromLon = floatval($tripData['from_lon'] ?? 83.1234567);
    $toLocId = trim($tripData['to_location_id'] ?? $tripData['clientid'] ?? '');
    $toLocName = trim($tripData['to_location_name'] ?? $tripData['delivery_address'] ?? 'Client Delivery Location');
    $toLat = floatval($tripData['to_lat'] ?? 0.0);
    $toLon = floatval($tripData['to_lon'] ?? 0.0);
    $distanceKm = floatval($tripData['distance_km'] ?? $tripData['distance'] ?? 0);
    $distanceSource = trim($tripData['distance_source'] ?? 'API');
    $freightAmt = floatval($tripData['freight_amount'] ?? $tripData['freightamt'] ?? 0);
    $freightDiscPercent = floatval($tripData['freight_discount_percent'] ?? $tripData['freight_discountpercent'] ?? 0);
    $payableFreightAmt = floatval($tripData['payable_freight_amount'] ?? $tripData['payable_freightamount'] ?? $freightAmt);
    $purposeToMove = trim($tripData['purpose_to_move'] ?? 'Sale Dispatch');
    $rawMoveFor = trim($tripData['move_for'] ?? $tripData['delivery_date'] ?? $tripData['deliverydate'] ?? date('Y-m-d'));
    $moveFor = (!empty($rawMoveFor) && strtotime($rawMoveFor)) ? date('Y-m-d', strtotime($rawMoveFor)) : date('Y-m-d');
    $rawDelDate = trim($tripData['delivery_date'] ?? $tripData['deliverydate'] ?? $moveFor);
    $deliveryDate = (!empty($rawDelDate) && strtotime($rawDelDate)) ? date('Y-m-d', strtotime($rawDelDate)) : $moveFor;
    $status = trim($tripData['status'] ?? 'Planned');
    $remarks = trim($tripData['remarks'] ?? '');
    $createdBy = intval($tripData['created_by'] ?? $tripData['loginid'] ?? 1);

    if (empty($tripNo)) {
        $tripNo = 'TRIP-' . date('Ymd', strtotime($deliveryDate ?: date('Y-m-d'))) . '-' . strtoupper(substr(uniqid(), -4));
    }

    // 1. Find or Upsert in krf_vehicletrip
    $numericTripId = 0;
    $chkStmt = $conn->prepare("SELECT trip_id, status FROM krf_vehicletrip WHERE trip_no = ? LIMIT 1");
    if ($chkStmt) {
        $chkStmt->bind_param("s", $tripNo);
        $chkStmt->execute();
        $chkRes = $chkStmt->get_result()->fetch_assoc();
        $chkStmt->close();
        if ($chkRes) {
            $numericTripId = intval($chkRes['trip_id']);
        }
    }

    if ($numericTripId > 0) {
        // Update existing trip
        $updSql = "UPDATE krf_vehicletrip SET
            project_id = COALESCE(NULLIF(?, ''), project_id),
            vehicle_vendor_id = IF(? > 0, ?, vehicle_vendor_id),
            vehicle_id = IF(? > 0, ?, vehicle_id),
            vehicle_no = COALESCE(NULLIF(?, ''), vehicle_no),
            driver_id = COALESCE(NULLIF(?, ''), driver_id),
            driver_name = COALESCE(NULLIF(?, ''), driver_name),
            driver_mobile_no = COALESCE(NULLIF(?, ''), driver_mobile_no),
            to_location_id = COALESCE(NULLIF(?, ''), to_location_id),
            to_location_name = COALESCE(NULLIF(?, ''), to_location_name),
            to_lat = IF(? != 0, ?, to_lat),
            to_lon = IF(? != 0, ?, to_lon),
            distance_km = IF(? > 0, ?, distance_km),
            freight_amount = IF(? > 0, ?, freight_amount),
            freight_discount_percent = IF(? > 0, ?, freight_discount_percent),
            payable_freight_amount = IF(? > 0, ?, payable_freight_amount),
            move_for = COALESCE(NULLIF(?, ''), move_for),
            delivery_date = COALESCE(NULLIF(?, ''), delivery_date),
            status = COALESCE(NULLIF(?, ''), status),
            dispatch_time = IF(? = 'Dispatched' AND dispatch_time IS NULL, NOW(), dispatch_time),
            delivered_time = IF(? = 'Delivered' AND delivered_time IS NULL, NOW(), delivered_time),
            remarks = COALESCE(NULLIF(?, ''), remarks),
            updated_at = NOW()
        WHERE trip_id = ?";
        $updStmt = $conn->prepare($updSql);
        if ($updStmt) {
            $updStmt->bind_param(
                "siiiissssssddddddddddddssssssi",
                $projectId,
                $vendorId, $vendorId,
                $vehicleId, $vehicleId,
                $vehicleNo,
                $driverId,
                $driverName,
                $driverMobile,
                $toLocId,
                $toLocName,
                $toLat, $toLat,
                $toLon, $toLon,
                $distanceKm, $distanceKm,
                $freightAmt, $freightAmt,
                $freightDiscPercent, $freightDiscPercent,
                $payableFreightAmt, $payableFreightAmt,
                $moveFor,
                $deliveryDate,
                $status,
                $status,
                $status,
                $remarks,
                $numericTripId
            );
            $updStmt->execute();
            $updStmt->close();
        }
    } else {
        // Insert new trip
        $insSql = "INSERT INTO krf_vehicletrip (
            trip_no, project_id, vehicle_vendor_id, vehicle_id, vehicle_no, driver_id, driver_name, driver_mobile_no,
            from_location_id, from_location_name, from_lat, from_lon,
            to_location_id, to_location_name, to_lat, to_lon,
            distance_km, distance_source, freight_amount, freight_discount_percent, payable_freight_amount,
            purpose_to_move, dispatch_type, move_for, delivery_date, status, dispatch_time, remarks, created_by, created_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, IF(? = 'Dispatched', NOW(), NULL), ?, ?, NOW()
        )";
        $insStmt = $conn->prepare($insSql);
        if ($insStmt) {
            $insStmt->bind_param(
                "ssiissssssddssdddsdddsssssssi",
                $tripNo,
                $projectId,
                $vendorId,
                $vehicleId,
                $vehicleNo,
                $driverId,
                $driverName,
                $driverMobile,
                $fromLocId,
                $fromLocName,
                $fromLat,
                $fromLon,
                $toLocId,
                $toLocName,
                $toLat,
                $toLon,
                $distanceKm,
                $distanceSource,
                $freightAmt,
                $freightDiscPercent,
                $payableFreightAmt,
                $purposeToMove,
                $dispatchType,
                $moveFor,
                $deliveryDate,
                $status,
                $status,
                $remarks,
                $createdBy
            );
            $insStmt->execute();
            $numericTripId = intval($insStmt->insert_id);
            $insStmt->close();
        }
    }

    if ($numericTripId <= 0) {
        $getStmt = $conn->prepare("SELECT trip_id FROM krf_vehicletrip WHERE trip_no = ? LIMIT 1");
        if ($getStmt) {
            $getStmt->bind_param("s", $tripNo);
            $getStmt->execute();
            $getR = $getStmt->get_result()->fetch_assoc();
            $getStmt->close();
            if ($getR) $numericTripId = intval($getR['trip_id']);
        }
    }

    // 2. Insert into krf_vehicletrip_status_history
    if ($numericTripId > 0) {
        $chkSh = $conn->prepare("SELECT id FROM krf_vehicletrip_status_history WHERE trip_id = ? AND status = ? LIMIT 1");
        $needSh = true;
        if ($chkSh) {
            $chkSh->bind_param("is", $numericTripId, $status);
            $chkSh->execute();
            if ($chkSh->get_result()->num_rows > 0) $needSh = false;
            $chkSh->close();
        }
        if ($needSh) {
            $shStmt = $conn->prepare("INSERT INTO krf_vehicletrip_status_history (trip_id, status, remarks, created_by, created_at) VALUES (?, ?, ?, ?, NOW())");
            if ($shStmt) {
                $statusRemark = $remarks ?: ("Trip status updated to " . $status);
                $shStmt->bind_param("issi", $numericTripId, $status, $statusRemark, $createdBy);
                $shStmt->execute();
                $shStmt->close();
            }
        }
    }

    // 3. Insert or Update krf_vehicle_travel_history
    if ($numericTripId > 0 && !empty($items)) {
        foreach ($items as $it) {
            $psono = trim($it['psono'] ?? $tripData['psono'] ?? '');
            $stkNo = trim($it['stockorder_no'] ?? $it['stockorderno'] ?? $tripData['stockorder_no'] ?? '');
            $productId = intval($it['product_id'] ?? $it['productid'] ?? 0);
            $rootType = trim($it['root_type'] ?? $it['roottype'] ?? ($it['plant_type'] ?? 'Non-Grafted'));
            $sendPlants = intval($it['send_total_plant_count'] ?? $it['allocate_ordqty'] ?? $it['order_plants'] ?? 0);
            $sendCavity = intval($it['send_tray_cavity'] ?? 104);
            $sendTrays = ($sendCavity > 0 && $sendPlants > 0) ? intval(ceil($sendPlants / $sendCavity)) : intval($it['send_tray_count'] ?? 0);
            $sendRemark = trim($it['send_remark'] ?? $it['remark'] ?? $remarks);
            $sowLot = trim($it['sowing_lot_no_internal'] ?? $it['sowinglotno_internal'] ?? $stkNo);
            $polyId = intval($it['polyhouse_id'] ?? $it['polyhouseid'] ?? 0);
            $polyName = trim($it['polyhouse_name'] ?? $it['polyhousename'] ?? '');
            $barcode = trim($it['barcode'] ?? '');
            $dispLocId = trim($it['dispatch_location_id'] ?? $toLocId);
            $rawItemDel = trim($it['delivery_date'] ?? $deliveryDate);
            $itemDelDate = (!empty($rawItemDel) && strtotime($rawItemDel)) ? date('Y-m-d', strtotime($rawItemDel)) : $deliveryDate;

            // Check if travel history row already exists for this trip + psono/stockorder_no + product
            $vthId = 0;
            if (!empty($psono) || !empty($stkNo)) {
                $chkTh = $conn->prepare("SELECT id FROM krf_vehicle_travel_history WHERE trip_id = ? AND ((psono = ? AND ? != '') OR (stockorder_no = ? AND ? != '')) AND (product_id = ? OR ? = 0) LIMIT 1");
                if ($chkTh) {
                    $chkTh->bind_param("issssii", $numericTripId, $psono, $psono, $stkNo, $stkNo, $productId, $productId);
                    $chkTh->execute();
                    $thRes = $chkTh->get_result()->fetch_assoc();
                    $chkTh->close();
                    if ($thRes) $vthId = intval($thRes['id']);
                }
            }

            if ($vthId > 0) {
                $updTh = $conn->prepare("
                    UPDATE krf_vehicle_travel_history SET
                        project_id = ?,
                        product_id = IF(? > 0, ?, product_id),
                        root_type = COALESCE(NULLIF(?, ''), root_type),
                        send_tray_count = IF(? > 0, ?, send_tray_count),
                        send_tray_cavity = IF(? > 0, ?, send_tray_cavity),
                        send_total_plant_count = IF(? > 0, ?, send_total_plant_count),
                        send_remark = COALESCE(NULLIF(?, ''), send_remark),
                        purpose_to_move = ?,
                        sowing_lot_no_internal = COALESCE(NULLIF(?, ''), sowing_lot_no_internal),
                        polyhouse_id = IF(? > 0, ?, polyhouse_id),
                        polyhouse_name = COALESCE(NULLIF(?, ''), polyhouse_name),
                        barcode = COALESCE(NULLIF(?, ''), barcode),
                        dispatch_location_id = ?,
                        dispatch_location_name = ?,
                        delivery_date = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                if ($updTh) {
                    $updTh->bind_param(
                        "siisiiiiiisssiisssssi",
                        $projectId,
                        $productId, $productId,
                        $rootType,
                        $sendTrays, $sendTrays,
                        $sendCavity, $sendCavity,
                        $sendPlants, $sendPlants,
                        $sendRemark,
                        $purposeToMove,
                        $sowLot,
                        $polyId, $polyId,
                        $polyName,
                        $barcode,
                        $dispLocId,
                        $dispLocName,
                        $itemDelDate,
                        $vthId
                    );
                    $updTh->execute();
                    $updTh->close();
                }
            } else {
                $insTh = $conn->prepare("
                    INSERT INTO krf_vehicle_travel_history (
                        trip_id, project_id, psono, stockorder_no, product_id, root_type,
                        send_tray_count, send_tray_cavity, send_total_plant_count, send_remark,
                        purpose_to_move, sowing_lot_no_internal, polyhouse_id, polyhouse_name,
                        barcode, dispatch_location_id, dispatch_location_name, delivery_date, created_by, created_at
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, NOW()
                    )
                ");
                if ($insTh) {
                    $insTh->bind_param(
                        "isssisiiisssisssssi",
                        $numericTripId,
                        $projectId,
                        $psono,
                        $stkNo,
                        $productId,
                        $rootType,
                        $sendTrays,
                        $sendCavity,
                        $sendPlants,
                        $sendRemark,
                        $purposeToMove,
                        $sowLot,
                        $polyId,
                        $polyName,
                        $barcode,
                        $dispLocId,
                        $dispLocName,
                        $itemDelDate,
                        $createdBy
                    );
                    $insTh->execute();
                    $insTh->close();
                }
            }
        }
    }

    return [
        'trip_id' => $numericTripId,
        'trip_no' => $tripNo,
        'status' => $status
    ];
}
}
?>