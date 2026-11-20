<?php
// Order editor helpers only. Retained function bodies preserve the supplied logic.
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
