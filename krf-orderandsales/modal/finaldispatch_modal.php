<?php
error_reporting(1);
require('../../connection.php');
if (isset($krfconn) && $krfconn) {
    @$krfconn->set_charset("utf8mb4");
}
require('../../loginCell/functions/login_functions.php');
include("../../whatsappnotification/whatsappsmsfunctions.php");
include("../functions/orders_functions.php");
include("../functions/product_functions.php");
include("../functions/seed_functions.php");
date_default_timezone_set("Asia/Calcutta");

if (!function_exists('resolveRsoParentBatch')) {
    function resolveRsoParentBatch($krfconn, $stockOrderNo) {
        $stockOrderNo = trim($stockOrderNo);
        if (empty($stockOrderNo)) return null;

        // ONLY RSO or RTS batches can have an RSO parent batch!
        // Standard production batches (KFP..., etc.) DO NOT have an RSO parent batch!
        if (strncasecmp($stockOrderNo, 'RSO-', 4) !== 0 && 
            strncasecmp($stockOrderNo, 'RSO', 3) !== 0 && 
            strncasecmp($stockOrderNo, 'RTS-', 4) !== 0) {
            return null;
        }

        $escStk = $krfconn->real_escape_string($stockOrderNo);
        $psoQry = $krfconn->query("
            SELECT po_number, remarks, productid 
            FROM krf_production_stockorder 
            WHERE stockorder_no = '{$escStk}' LIMIT 1
        ");
        if (!$psoQry || $psoQry->num_rows === 0) {
            return null;
        }
        $psoRow = $psoQry->fetch_assoc();
        $poNumber = trim($psoRow['po_number'] ?? '');
        $remarks = trim($psoRow['remarks'] ?? '');

        $candidates = [];
        if (!empty($poNumber)) {
            $candidates[] = $poNumber;
            if (strncasecmp($poNumber, 'KFP', 3) !== 0) {
                $candidates[] = 'KFP' . $poNumber;
                $candidates[] = 'KFP-' . $poNumber;
            } else {
                $cleanPo = preg_replace('/^KFP[-=]?/i', '', $poNumber);
                if (!empty($cleanPo)) {
                    $candidates[] = 'KFP' . $cleanPo;
                    $candidates[] = $cleanPo;
                }
            }
        }

        $suffix = preg_replace('/^(RSO|RTS)[-]?/i', '', $stockOrderNo);
        if (!empty($suffix)) {
            $candidates[] = $suffix;
            if (strncasecmp($suffix, 'KFP', 3) !== 0) {
                $candidates[] = 'KFP' . $suffix;
            } else {
                $cleanSuffix = preg_replace('/^KFP[-=]?/i', '', $suffix);
                if (!empty($cleanSuffix)) {
                    $candidates[] = 'KFP' . $cleanSuffix;
                    $candidates[] = $cleanSuffix;
                }
            }
        }

        if (preg_match('/(?:Bill\s*No|Batch\s*No|Parent\s*Batch)\s*[:\-–]?\s*([A-Za-z0-9_\-]+)/i', $remarks, $m)) {
            $b = trim($m[1]);
            if (!empty($b)) {
                $candidates[] = $b;
                if (strncasecmp($b, 'KFP', 3) !== 0) {
                    $candidates[] = 'KFP' . $b;
                }
            }
        }

        $candidates = array_values(array_unique(array_filter($candidates)));

        // 1. Check which candidate has active trays in krf_polyhouse_plant_details
        foreach ($candidates as $cand) {
            $escCand = $krfconn->real_escape_string($cand);
            $chkPpd = $krfconn->query("
                SELECT COUNT(*) as total_trays,
                       SUM(CASE 
                           WHEN LOWER(TRIM(IFNULL(ppd.status, ''))) IN ('polyhouse', 'readytosale', 'partial dispatched', 'partialdispatched', 'openstock', 'loaded')
                             OR LOWER(TRIM(IFNULL(ppd.workingstatus, ''))) IN ('polyhouse', 'readytosale', 'partial dispatched', 'partialdispatched', 'loaded')
                           THEN GREATEST(0, CAST(IFNULL(ppd.qtypertray, 0) AS SIGNED) - CAST(IFNULL(ppd.failurecount, 0) AS SIGNED))
                           ELSE 0 
                       END) as avail_plants
                FROM krf_polyhouse_plant_details ppd
                WHERE ppd.stockorder_no = '{$escCand}'
                  AND (ppd.tedit IS NULL OR ppd.tedit = '')
            ");
            if ($chkPpd && ($ppdRow = $chkPpd->fetch_assoc())) {
                if (intval($ppdRow['total_trays'] ?? 0) > 0 && intval($ppdRow['avail_plants'] ?? 0) > 0) {
                    return [
                        'parent_batch' => $cand,
                        'total_trays'  => intval($ppdRow['total_trays']),
                        'avail_plants' => intval($ppdRow['avail_plants'])
                    ];
                }
            }
        }

        // 2. Fallback: Check which candidate exists in krf_production_stockorder
        foreach ($candidates as $cand) {
            $escCand = $krfconn->real_escape_string($cand);
            $chkPso = $krfconn->query("SELECT id FROM krf_production_stockorder WHERE stockorder_no = '{$escCand}' LIMIT 1");
            if ($chkPso && $chkPso->num_rows > 0) {
                return [
                    'parent_batch' => $cand,
                    'total_trays'  => 0,
                    'avail_plants' => 0
                ];
            }
        }

        return null;
    }
}

if (!function_exists('safeCleanupReadyStockConstraints')) {
    function safeCleanupReadyStockConstraints($krfconn) {
        if (!$krfconn) return;
        try {
            $chkIdx = $krfconn->query("SHOW INDEX FROM krf_readystock_allocation WHERE Key_name = 'uq_ready_stock_pool'");
            if ($chkIdx && $chkIdx->num_rows > 0) {
                $krfconn->query("ALTER TABLE krf_readystock_allocation DROP INDEX uq_ready_stock_pool");
            }
        } catch (Throwable $e) {}

        try {
            $chkIdx2 = $krfconn->query("SHOW INDEX FROM krf_ready_stock_reserved_qty WHERE Key_name = 'uq_ready_stock_pool'");
            if ($chkIdx2 && $chkIdx2->num_rows > 0) {
                $krfconn->query("ALTER TABLE krf_ready_stock_reserved_qty DROP INDEX uq_ready_stock_pool");
            }
        } catch (Throwable $e) {}

        try {
            $krfconn->query("ALTER TABLE krf_readystock_allocation MODIFY COLUMN allocatedbed TEXT NULL");
        } catch (Throwable $e) {}

        try {
            $krfconn->query("ALTER TABLE krf_readystock_allocation MODIFY COLUMN status VARCHAR(50) DEFAULT 'allocated'");
        } catch (Throwable $e) {}
    }
}

if (!function_exists('autoUndoPreviousLoadedTraysOnReallocation')) {
    function autoUndoPreviousLoadedTraysOnReallocation($krfconn, $psono, $newBatches = [], $productId = '', $productName = '', $loginUser = 'Admin') {
        if (empty($psono) || !$krfconn) return 0;
        
        $escPso = mysqli_real_escape_string($krfconn, $psono);
        $newBatches = array_values(array_unique(array_filter(array_map('trim', (array)$newBatches))));
        
        $batchExcludeCondPh = "";
        $batchExcludeCondRts = "";
        if (!empty($newBatches)) {
            $escList = "'" . implode("','", array_map(function($b) use ($krfconn) { return mysqli_real_escape_string($krfconn, $b); }, $newBatches)) . "'";
            $batchExcludeCondPh = " AND (stockorder_no NOT IN ({$escList}) AND IFNULL(dispd_agst_stockorderno, '') NOT IN ({$escList}))";
            $batchExcludeCondRts = " AND (stockorder_no NOT IN ({$escList}) AND IFNULL(dispd_agst_stockorderno, '') NOT IN ({$escList}))";
        }

        $prodCondPh = "";
        $prodCondRts = "";
        if (!empty($productId) && !empty($productName)) {
            $escPid = mysqli_real_escape_string($krfconn, $productId);
            $escPnm = mysqli_real_escape_string($krfconn, $productName);
            $prodCondPh = " AND (productid = '{$escPid}' OR productname = '{$escPnm}')";
            $prodCondRts = " AND (productid = '{$escPid}' OR productname = '{$escPnm}')";
        } elseif (!empty($productId)) {
            $escPid = mysqli_real_escape_string($krfconn, $productId);
            $prodCondPh = " AND productid = '{$escPid}'";
            $prodCondRts = " AND productid = '{$escPid}'";
        } elseif (!empty($productName)) {
            $escPnm = mysqli_real_escape_string($krfconn, $productName);
            $prodCondPh = " AND productname = '{$escPnm}'";
            $prodCondRts = " AND productname = '{$escPnm}'";
        }

        $undoRemark = "Load auto-undone to Readytosale due to re-allocation of Order {$psono} by {$loginUser}";
        $escRemark = mysqli_real_escape_string($krfconn, $undoRemark);
        $escUser = mysqli_real_escape_string($krfconn, $loginUser);

        // 1. Fetch trays being reverted from polyhouse for history logging
        $revertedTrays = [];
        $chkQ = $krfconn->query("
            SELECT polyhouseplantid, barcode, psono, stockorder_no, productname, polyhouseid, bed_no, qtypertray 
            FROM krf_polyhouse_plant_details
            WHERE (psono = '{$escPso}' OR dispd_agst_psono = '{$escPso}')
              AND LOWER(TRIM(status)) IN ('loaded', 'readytodispatch', 'ready to dispatch')
              {$prodCondPh}
              {$batchExcludeCondPh}
        ");
        if ($chkQ) {
            while ($r = $chkQ->fetch_assoc()) {
                $revertedTrays[] = $r;
            }
        }

        // 2. Revert in krf_polyhouse_plant_details
        $sqlPh = "
            UPDATE krf_polyhouse_plant_details
            SET 
                status = 'Readytosale',
                workingstatus = 'Readytosale',
                movementremark = '{$escRemark}',
                dispatchqty = NULL,
                dispatchat = NULL,
                dispatchby = NULL,
                dispd_agst_clientid = NULL,
                dispd_agst_stockorderno = NULL,
                dispd_agst_psono = NULL,
                updated_at = NOW(),
                updated_by = '{$escUser}'
            WHERE (psono = '{$escPso}' OR dispd_agst_psono = '{$escPso}')
              AND LOWER(TRIM(status)) IN ('loaded', 'readytodispatch', 'ready to dispatch')
              {$prodCondPh}
              {$batchExcludeCondPh}
        ";
        $krfconn->query($sqlPh);
        $phCount = $krfconn->affected_rows;

        // 3. Revert in krf_readytosale_stocks
        $sqlRts = "
            UPDATE krf_readytosale_stocks
            SET 
                status = 'Readytosale',
                dispatchqty = '0',
                dispatchat = NULL,
                dispatchby = NULL,
                dispd_agst_clientid = NULL,
                dispd_agst_stockorderno = NULL,
                dispd_agst_psono = NULL,
                loaded_at = NULL,
                loaded_by = NULL,
                updated_at = NOW()
            WHERE (psono = '{$escPso}' OR dispd_agst_psono = '{$escPso}')
              AND LOWER(TRIM(status)) IN ('loaded', 'readytodispatch', 'ready to dispatch')
              {$prodCondRts}
              {$batchExcludeCondRts}
        ";
        $krfconn->query($sqlRts);
        $rtsCount = $krfconn->affected_rows;

        // 4. Log audit records into krf_traymovement_history
        if (!empty($revertedTrays)) {
            $undoTx = 'UNDO-REALLOC-' . date('YmdHis') . '-' . substr(uniqid(), -4);
            foreach ($revertedTrays as $t) {
                try {
                    $pId = intval($t['polyhouseplantid'] ?? 0);
                    $bCode = mysqli_real_escape_string($krfconn, $t['barcode'] ?? '');
                    $pNm = mysqli_real_escape_string($krfconn, $t['productname'] ?? '');
                    $stk = mysqli_real_escape_string($krfconn, $t['stockorder_no'] ?? '');
                    $phId = intval($t['polyhouseid'] ?? 0);
                    $bNo = mysqli_real_escape_string($krfconn, $t['bed_no'] ?? '');
                    $pts = intval($t['qtypertray'] ?? 125);
                    $krfconn->query("
                        INSERT INTO krf_traymovement_history (
                            transaction_no, projectid, psono, stockorder_no, productname,
                            activity_type, barcode, sowingplantid, from_polyhouse_id, from_bed_no,
                            previous_status, current_status, live_plant_count, reason_for_movement,
                            movement_date, movement_time, created_by, created_at
                        ) VALUES (
                            '{$undoTx}', 17, '{$escPso}', '{$stk}', '{$pNm}',
                            'INTERNAL_DISPATCH', '{$bCode}', {$pId}, {$phId}, '{$bNo}',
                            'Loaded', 'Readytosale', {$pts}, '{$escRemark}',
                            CURDATE(), CURTIME(), '{$escUser}', NOW()
                        )
                    ");
                } catch (Throwable $eHistory) {
                    // Non-critical audit history failure must never abort re-allocation
                }
            }
        }

        return ($phCount + $rtsCount);
    }
}

if (!function_exists('syncInvoiceItemsOnReallocation')) {
    function syncInvoiceItemsOnReallocation($krfconn, $psono, $newBatches = [], $productId = '', $productName = '', $loginUser = 'Admin') {
        if (empty($psono) || empty($newBatches) || !$krfconn) return;

        $escPso = mysqli_real_escape_string($krfconn, $psono);
        $batchStr = implode(', ', array_unique(array_filter(array_map('trim', (array)$newBatches))));
        $escBatch = mysqli_real_escape_string($krfconn, $batchStr);

        $prodCond = "";
        if (!empty($productId) && !empty($productName)) {
            $escPid = mysqli_real_escape_string($krfconn, $productId);
            $escPnm = mysqli_real_escape_string($krfconn, $productName);
            $prodCond = " AND (productid = '{$escPid}' OR productname = '{$escPnm}')";
        } elseif (!empty($productId)) {
            $escPid = mysqli_real_escape_string($krfconn, $productId);
            $prodCond = " AND productid = '{$escPid}'";
        } elseif (!empty($productName)) {
            $escPnm = mysqli_real_escape_string($krfconn, $productName);
            $prodCond = " AND productname = '{$escPnm}'";
        }

        // Only update non-dispatched invoice items for this psono
        $sql = "
            UPDATE sales_invoice_items
            SET 
                stockorder_no = '{$escBatch}',
                updated_at = NOW()
            WHERE psono = '{$escPso}'
              AND LOWER(TRIM(status)) IN ('readytoload', 'readytodispatch', 'active')
              {$prodCond}
        ";
        @$krfconn->query($sql);
    }
}

if (!function_exists('saveOrUpdateVehicleTripAndHistory')) {
function saveOrUpdateVehicleTripAndHistory($krfconn, $params) {
    $projectid = intval($params['projectid'] ?? 0);
    $sono = trim($params['sono'] ?? '');
    $psono = trim($params['psono'] ?? '');
    $clientid = trim($params['clientid'] ?? '');
    $vehicle_id = intval($params['vehicle_id'] ?? 0);
    $vehicleno = trim($params['vehicleno'] ?? '');
    $driver_id = trim($params['driver_id'] ?? '');
    $drivername = trim($params['drivername'] ?? '');
    $drivermobileno = trim($params['drivermobileno'] ?? '');
    $deliverydate = trim($params['deliverydate'] ?? date('Y-m-d'));
    if (empty($deliverydate) || $deliverydate === '0000-00-00') {
        $deliverydate = date('Y-m-d');
    }
    $total_plants = intval($params['total_allocated_plants'] ?? 0);
    $remarks = trim($params['remarks'] ?? '');
    $trip_id = trim($params['trip_id'] ?? '');
    $loginid = trim($params['loginid'] ?? 'Admin');
    $status = trim($params['status'] ?? 'Planned');

    // Location, Coordinates, Distance, Freight & Dispatch Type
    $to_location_name = trim($params['to_location_name'] ?? '');
    $to_lat = (isset($params['to_lat']) && $params['to_lat'] !== '' && $params['to_lat'] !== null) ? floatval($params['to_lat']) : null;
    $to_lon = (isset($params['to_lon']) && $params['to_lon'] !== '' && $params['to_lon'] !== null) ? floatval($params['to_lon']) : null;
    $distance_km = (isset($params['distance_km']) && $params['distance_km'] !== '' && $params['distance_km'] !== null) ? floatval($params['distance_km']) : null;
    $distance_source = trim($params['distance_source'] ?? '');
    if (empty($distance_source) && !empty($to_lat)) {
        $distance_source = 'GPS';
    } elseif (empty($distance_source) && !empty($to_location_name)) {
        $distance_source = 'Manual';
    }
    $freight_amount = (isset($params['freight_amount']) && $params['freight_amount'] !== '' && $params['freight_amount'] !== null) ? floatval($params['freight_amount']) : null;
    $freight_discount_percent = (isset($params['freight_discount_percent']) && $params['freight_discount_percent'] !== '' && $params['freight_discount_percent'] !== null) ? floatval($params['freight_discount_percent']) : null;
    $payable_freight_amount = (isset($params['payable_freight_amount']) && $params['payable_freight_amount'] !== '' && $params['payable_freight_amount'] !== null) ? floatval($params['payable_freight_amount']) : null;
    $dispatch_type = trim($params['dispatch_type'] ?? '');
    if (empty($dispatch_type)) {
        if (strcasecmp($vehicleno, 'Self Pickup') === 0 || stripos($remarks, 'Self Pickup') !== false) {
            $dispatch_type = 'Self Pickup';
        } else {
            $dispatch_type = 'Delivered';
        }
    }

    // Extract trip_no from remarks if present (e.g. [Trip: TRIP-20261009-8612-1])
    if (empty($trip_id) && preg_match('/\[Trip:\s*([^\]]+)\]/', $remarks, $m)) {
        $trip_id = trim($m[1]);
    }

    // Check if an existing trip is already linked to this order in krf_vehicle_travel_history
    if (empty($trip_id)) {
        $chkOldVth = $krfconn->prepare("
            SELECT trip_id FROM krf_vehicle_travel_history 
            WHERE ((stockorder_no = ? AND ? != '') OR (psono = ? AND ? != '')) 
              AND status NOT IN ('Cancelled')
            ORDER BY id DESC LIMIT 1
        ");
        if ($chkOldVth) {
            $chkOldVth->bind_param("ssss", $sono, $sono, $psono, $psono);
            $chkOldVth->execute();
            $oldRow = $chkOldVth->get_result()->fetch_assoc();
            $chkOldVth->close();
            if ($oldRow && !empty($oldRow['trip_id'])) {
                $trip_id = trim($oldRow['trip_id']);
            }
        }
    }

    // If trip_id is still empty, look for an existing planned trip on this delivery date for this vehicle
    if (empty($trip_id)) {
        if (!empty($vehicleno) || $vehicle_id > 0) {
            $chkTrip = $krfconn->prepare("
                SELECT trip_no FROM krf_vehicletrip 
                WHERE ((vehicle_id = ? AND ? > 0) OR (vehicle_no = ? AND ? != ''))
                  AND delivery_date = ?
                  AND status NOT IN ('Cancelled', 'Delivered')
                ORDER BY trip_id DESC LIMIT 1
            ");
            $chkTrip->bind_param("iisss", $vehicle_id, $vehicle_id, $vehicleno, $vehicleno, $deliverydate);
            $chkTrip->execute();
            $tRow = $chkTrip->get_result()->fetch_assoc();
            $chkTrip->close();
            if ($tRow && !empty($tRow['trip_no'])) {
                $trip_id = $tRow['trip_no'];
            }
        }
    }

    // If still empty, generate a new trip_no
    if (empty($trip_id)) {
        $datePart = date('Ymd', strtotime($deliverydate));
        $cleanVeh = preg_replace('/[^A-Za-z0-9]/', '', $vehicleno);
        $vehSuffix = !empty($cleanVeh) ? substr($cleanVeh, -4) : 'PENDING';
        $trip_id = "TRIP-{$datePart}-{$vehSuffix}-" . rand(100, 999);
    }

    // 1. Find or Upsert krf_vehicletrip
    $chkVt = $krfconn->prepare("SELECT trip_id FROM krf_vehicletrip WHERE trip_no = ? LIMIT 1");
    $chkVt->bind_param("s", $trip_id);
    $chkVt->execute();
    $vtRes = $chkVt->get_result()->fetch_assoc();
    $chkVt->close();

    $trip_status = ($status === 'Dispatched') ? 'Dispatched' : 'Planned';

    if ($vtRes && !empty($vtRes['trip_id'])) {
        $updVt = $krfconn->prepare("
            UPDATE krf_vehicletrip SET
                project_id = COALESCE(NULLIF(?, 0), project_id),
                vehicle_id = IF(? > 0, ?, vehicle_id),
                vehicle_no = COALESCE(NULLIF(?, ''), vehicle_no),
                driver_id = COALESCE(NULLIF(?, ''), driver_id),
                driver_name = COALESCE(NULLIF(?, ''), driver_name),
                driver_mobile_no = COALESCE(NULLIF(?, ''), driver_mobile_no),
                to_location_name = COALESCE(NULLIF(?, ''), to_location_name),
                to_lat = COALESCE(?, to_lat),
                to_lon = COALESCE(?, to_lon),
                distance_km = COALESCE(?, distance_km),
                distance_source = COALESCE(NULLIF(?, ''), distance_source),
                freight_amount = COALESCE(?, freight_amount),
                freight_discount_percent = COALESCE(?, freight_discount_percent),
                payable_freight_amount = COALESCE(?, payable_freight_amount),
                dispatch_type = COALESCE(NULLIF(?, ''), dispatch_type),
                delivery_date = COALESCE(NULLIF(?, ''), delivery_date),
                move_for = COALESCE(NULLIF(?, ''), move_for),
                status = IF(? = 'Dispatched', 'Dispatched', status),
                remarks = COALESCE(NULLIF(?, ''), remarks),
                updated_at = NOW()
            WHERE trip_id = ?
        ");
        $updVt->bind_param(
            "iiisssssdddsdddsssssi",
            $projectid,
            $vehicle_id, $vehicle_id,
            $vehicleno,
            $driver_id,
            $drivername,
            $drivermobileno,
            $to_location_name,
            $to_lat,
            $to_lon,
            $distance_km,
            $distance_source,
            $freight_amount,
            $freight_discount_percent,
            $payable_freight_amount,
            $dispatch_type,
            $deliverydate,
            $deliverydate,
            $status,
            $remarks,
            $vtRes['trip_id']
        );
        $updVt->execute();
        $updVt->close();
    } else {
        $insVt = $krfconn->prepare("
            INSERT INTO krf_vehicletrip (
                trip_no, project_id, vehicle_id, vehicle_no, driver_id, driver_name, driver_mobile_no,
                to_location_name, to_lat, to_lon, distance_km, distance_source,
                freight_amount, freight_discount_percent, payable_freight_amount, dispatch_type,
                delivery_date, move_for, status, remarks, created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $insVt->bind_param(
            "siisssssdddsdddssssss",
            $trip_id,
            $projectid,
            $vehicle_id,
            $vehicleno,
            $driver_id,
            $drivername,
            $drivermobileno,
            $to_location_name,
            $to_lat,
            $to_lon,
            $distance_km,
            $distance_source,
            $freight_amount,
            $freight_discount_percent,
            $payable_freight_amount,
            $dispatch_type,
            $deliverydate,
            $deliverydate,
            $trip_status,
            $remarks,
            $loginid
        );
        $insVt->execute();
        $insVt->close();
    }

    // 2. Find or Upsert krf_vehicle_travel_history
    $chkVth = $krfconn->prepare("
        SELECT id FROM krf_vehicle_travel_history 
        WHERE ((stockorder_no = ? AND ? != '') OR (psono = ? AND ? != '')) 
        LIMIT 1
    ");
    $chkVth->bind_param("ssss", $sono, $sono, $psono, $psono);
    $chkVth->execute();
    $vthRes = $chkVth->get_result()->fetch_assoc();
    $chkVth->close();

    $history_status = ($status === 'Dispatched') ? 'Dispatched' : null;

    if ($vthRes && !empty($vthRes['id'])) {
        $updVth = $krfconn->prepare("
            UPDATE krf_vehicle_travel_history SET
                trip_id = ?,
                projectid = COALESCE(NULLIF(?, 0), projectid),
                psono = COALESCE(NULLIF(?, ''), psono),
                stockorder_no = COALESCE(NULLIF(?, ''), stockorder_no),
                vehicle_id = IF(? > 0, ?, vehicle_id),
                vehicle_no = COALESCE(NULLIF(?, ''), vehicle_no),
                driverid = COALESCE(NULLIF(?, ''), driverid),
                driver_name = COALESCE(NULLIF(?, ''), driver_name),
                driver_mobileno = COALESCE(NULLIF(?, ''), driver_mobileno),
                to_location_name = COALESCE(NULLIF(?, ''), to_location_name),
                to_lat = COALESCE(?, to_lat),
                to_lon = COALESCE(?, to_lon),
                distance_km = COALESCE(?, distance_km),
                distance_source = COALESCE(NULLIF(?, ''), distance_source),
                freight_amount = COALESCE(?, freight_amount),
                freight_discountpercent = COALESCE(?, freight_discountpercent),
                payable_freightamount = COALESCE(?, payable_freightamount),
                dispatchtype = COALESCE(NULLIF(?, ''), dispatchtype),
                send_totalplantcount = IF(? > 0, ?, send_totalplantcount),
                deliverydate = COALESCE(NULLIF(?, ''), deliverydate),
                status = IF(? = 'Dispatched', 'Dispatched', status),
                send_remark = COALESCE(NULLIF(?, ''), send_remark),
                updated_at = NOW()
            WHERE id = ?
        ");
        $updVth->bind_param(
            "sissiisssssdddsdddsiisssi",
            $trip_id,
            $projectid,
            $psono,
            $sono,
            $vehicle_id, $vehicle_id,
            $vehicleno,
            $driver_id,
            $drivername,
            $drivermobileno,
            $to_location_name,
            $to_lat,
            $to_lon,
            $distance_km,
            $distance_source,
            $freight_amount,
            $freight_discount_percent,
            $payable_freight_amount,
            $dispatch_type,
            $total_plants, $total_plants,
            $deliverydate,
            $status,
            $remarks,
            $vthRes['id']
        );
        $updVth->execute();
        $updVth->close();
    } else {
        $insVth = $krfconn->prepare("
            INSERT INTO krf_vehicle_travel_history (
                trip_id, projectid, psono, stockorder_no, vehicle_id, vehicle_no,
                driverid, driver_name, driver_mobileno,
                to_location_name, to_lat, to_lon, distance_km, distance_source,
                freight_amount, freight_discountpercent, payable_freightamount, dispatchtype,
                send_totalplantcount, deliverydate, status, send_remark, created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $insVth->bind_param(
            "sississsssdddsdddsissss",
            $trip_id,
            $projectid,
            $psono,
            $sono,
            $vehicle_id,
            $vehicleno,
            $driver_id,
            $drivername,
            $drivermobileno,
            $to_location_name,
            $to_lat,
            $to_lon,
            $distance_km,
            $distance_source,
            $freight_amount,
            $freight_discount_percent,
            $payable_freight_amount,
            $dispatch_type,
            $total_plants,
            $deliverydate,
            $history_status,
            $remarks,
            $loginid
        );
        $insVth->execute();
        $insVth->close();
    }

    return $trip_id;
}
}

// Helper: Auto-mark active ready stock trays as Loaded for RSO/SO orders
if (!function_exists('autoMarkRsoTraysAsLoaded')) {
function autoMarkRsoTraysAsLoaded($krfconn, $psono, $stkNo, $qty, $clientId, $loginname = 'admin', $invoiceno = '') {
    $cleanStk = trim($stkNo);
    if (!preg_match('/^(RSO|SO)-/i', $cleanStk)) {
        return ['status' => 'skipped', 'message' => 'Not an RSO or SO order'];
    }

    $batches = array_filter(array_map('trim', explode(',', $cleanStk)));
    if (empty($batches)) return ['status' => 'error', 'message' => 'No batches found'];

    $escPso = mysqli_real_escape_string($krfconn, $psono);
    
    // Check how many trays are already marked as Loaded for this order
    $qAlready = $krfconn->query("
        SELECT COUNT(*) as cnt 
        FROM krf_polyhouse_plant_details 
        WHERE (dispd_agst_psono = '{$escPso}' OR (psono = '{$escPso}' AND (dispd_agst_psono IS NULL OR dispd_agst_psono = '' OR dispd_agst_psono = '{$escPso}')))
          AND status IN ('Loaded', 'ReadyToDispatch')
    ");
    $alreadyCount = $qAlready ? (int)$qAlready->fetch_assoc()['cnt'] : 0;

    $reqPlants = (int)$qty;
    $cap = 125; // default capacity for shifting/ready stock
    $reqTrays = ($reqPlants > 0) ? (int)ceil($reqPlants / $cap) : 0;

    if ($alreadyCount >= $reqTrays && $reqTrays > 0) {
        return ['status' => 'already_loaded', 'loaded' => $alreadyCount, 'required' => $reqTrays];
    }

    $traysNeeded = max(0, $reqTrays - $alreadyCount);
    if ($traysNeeded <= 0) return ['status' => 'no_trays_needed'];

    $expandedBatches = $batches;
    foreach ($batches as $b) {
        if (strncasecmp($b, 'RSO-', 4) === 0 || strncasecmp($b, 'RTS-', 4) === 0 || strncasecmp($b, 'RSO', 3) === 0) {
            $pInfo = resolveRsoParentBatch($krfconn, $b);
            if ($pInfo && !empty($pInfo['parent_batch'])) {
                $expandedBatches[] = $pInfo['parent_batch'];
            }
        }
    }
    $expandedBatches = array_values(array_unique(array_filter($expandedBatches)));
    $escBatches = "'" . implode("','", array_map(function($b) use ($krfconn) { return mysqli_real_escape_string($krfconn, $b); }, $expandedBatches)) . "'";

    // Select active trays from krf_polyhouse_plant_details for the RSO/SO batch
    $sql = "
        SELECT polyhouseplantid, barcode, polyhouseid, bed_no, productname, productid, stockorder_no, qtypertray, status, failurecount
        FROM krf_polyhouse_plant_details
        WHERE (stockorder_no IN ({$escBatches}) OR dispd_agst_stockorderno IN ({$escBatches}) OR psono IN ({$escBatches}))
          AND LOWER(TRIM(status)) IN ('readytosale', 'openstock', 'polyhouse', 'allocated', 'readytodispatch', 'readytoallocated', 'clip stick removal')
          AND (dispd_agst_psono IS NULL OR dispd_agst_psono = '' OR dispd_agst_psono = '{$escPso}')
        ORDER BY 
            CASE WHEN LOWER(TRIM(status)) = 'readytosale' THEN 1 ELSE 2 END,
            polyhouseplantid ASC
        LIMIT {$traysNeeded}
    ";
    $res = $krfconn->query($sql);
    $selectedTrays = [];
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $selectedTrays[] = $r;
        }
    }

    if (empty($selectedTrays)) {
        return ['status' => 'no_active_trays_found', 'batches' => $batches];
    }

    $txNo = 'LOAD-AUTO-' . date('YmdHis') . '-' . rand(100, 999);
    $remarkText = 'Auto-loaded for Ready Stock Order ' . $psono . (!empty($invoiceno) ? (' | Inv: ' . $invoiceno) : '');

    $loadedCount = 0;
    foreach ($selectedTrays as $tray) {
        $plantId = (int)$tray['polyhouseplantid'];
        $barcode = trim($tray['barcode'] ?? '');
        $dispQty = (int)($tray['qtypertray'] ?: 126);
        $trayStock = trim($tray['stockorder_no'] ?: $cleanStk);
        $phId = (int)$tray['polyhouseid'];
        $bedNo = $tray['bed_no'] ?: '';
        $prodName = $tray['productname'] ?: '';
        $prevStatus = $tray['status'] ?: 'Readytosale';

        // 1. Update krf_polyhouse_plant_details
        $updSql = "
            UPDATE krf_polyhouse_plant_details
            SET 
                status = 'Loaded',
                workingstatus = 'Loaded',
                movementremark = '" . mysqli_real_escape_string($krfconn, $remarkText) . "',
                dispatchqty = '{$dispQty}',
                dispatchat = NOW(),
                dispatchby = '" . mysqli_real_escape_string($krfconn, $loginname) . "',
                dispd_agst_clientid = '" . mysqli_real_escape_string($krfconn, $clientId) . "',
                dispd_agst_stockorderno = '" . mysqli_real_escape_string($krfconn, $trayStock) . "',
                dispd_agst_psono = '{$escPso}',
                move_at = NOW(),
                updated_at = NOW(),
                updated_by = '" . mysqli_real_escape_string($krfconn, $loginname) . "'
            WHERE polyhouseplantid = {$plantId}
        ";
        $krfconn->query($updSql);

        // 2. Update krf_readytosale_stocks
        if (!empty($barcode)) {
            $escBc = mysqli_real_escape_string($krfconn, $barcode);
            $krfconn->query("
                UPDATE krf_readytosale_stocks
                SET 
                    dispatchqty = '{$dispQty}',
                    dispatchat = NOW(),
                    dispatchby = '" . mysqli_real_escape_string($krfconn, $loginname) . "',
                    dispd_agst_clientid = '" . mysqli_real_escape_string($krfconn, $clientId) . "',
                    dispd_agst_stockorderno = '" . mysqli_real_escape_string($krfconn, $trayStock) . "',
                    dispd_agst_psono = '{$escPso}',
                    loaded_qty = {$dispQty},
                    loaded_at = NOW(),
                    loaded_by = '" . mysqli_real_escape_string($krfconn, $loginname) . "',
                    status = 'Loaded',
                    updated_at = NOW()
                WHERE barcode = '{$escBc}'
            ");
        }

        // 3. Insert history
        $krfconn->query("
            INSERT INTO krf_traymovement_history (
                transaction_no, projectid, psono, stockorder_no, productname,
                activity_type, barcode, from_polyhouse_id, from_bed_no,
                previous_status, current_status, live_plant_count,
                reason_for_movement, movement_date, movement_time,
                created_by, created_at
            ) VALUES (
                '{$txNo}', 17, '{$escPso}', '" . mysqli_real_escape_string($krfconn, $trayStock) . "', '" . mysqli_real_escape_string($krfconn, $prodName) . "',
                'AUTO_LOAD_RSO', '" . mysqli_real_escape_string($krfconn, $barcode) . "', {$phId}, '" . mysqli_real_escape_string($krfconn, $bedNo) . "',
                '" . mysqli_real_escape_string($krfconn, $prevStatus) . "', 'Loaded', {$dispQty},
                '" . mysqli_real_escape_string($krfconn, $remarkText) . "', CURDATE(), CURTIME(),
                '" . mysqli_real_escape_string($krfconn, $loginname) . "', NOW()
            )
        ");

        $loadedCount++;
    }

    // 4. Update krf_sales_order_items actionstatus
    $krfconn->query("
        UPDATE krf_sales_order_items
        SET actionstatus = 'Loaded',
            updateamended = NOW()
        WHERE psono = '{$escPso}'
    ");

    return ['status' => 'success', 'loaded_trays' => $loadedCount];
}
}

// Helper: Resolve polyhouse name for a batch / bed
if (!function_exists('resolvePolyhouseName')) {
function resolvePolyhouseName($krfconn, $bStockNo, $allocatedBedsJson = '', $preferredLocation = '') {
    if (!empty($allocatedBedsJson)) {
        $dec = is_string($allocatedBedsJson) ? json_decode($allocatedBedsJson, true) : $allocatedBedsJson;
        if (is_array($dec) && !empty($dec[0]['polyhouse_name'])) {
            $phName = trim($dec[0]['polyhouse_name']);
            if ($phName !== '' && strcasecmp($phName, 'Main Polyhouse') !== 0 && strcasecmp($phName, 'N/A') !== 0) {
                return $phName;
            }
        }
    }
    if (!empty($preferredLocation) && strcasecmp($preferredLocation, 'Main Polyhouse') !== 0 && strcasecmp($preferredLocation, 'N/A') !== 0) {
        return trim($preferredLocation);
    }
    if (!empty($bStockNo)) {
        $stmt = $krfconn->prepare("
            SELECT COALESCE(NULLIF(TRIM(phm.polyhousename), ''), NULLIF(TRIM(ppd.locationid), '')) AS polyhouse_name
            FROM krf_polyhouse_plant_details ppd
            LEFT JOIN krf_polyhouse_master phm ON ppd.polyhouseid = phm.polyhouseid
            WHERE ppd.stockorder_no = ? AND (phm.polyhousename IS NOT NULL OR ppd.locationid IS NOT NULL)
            ORDER BY CASE WHEN phm.polyhousename IS NOT NULL THEN 1 ELSE 2 END, ppd.polyhouseplantid DESC
            LIMIT 1
        ");
        if ($stmt) {
            $stmt->bind_param("s", $bStockNo);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row && !empty($row['polyhouse_name'])) {
                return trim($row['polyhouse_name']);
            }
        }
        $stmt2 = $krfconn->prepare("
            SELECT COALESCE(NULLIF(TRIM(phm.polyhousename), ''), NULLIF(TRIM(psd.locationid), '')) AS polyhouse_name
            FROM krf_polyhouse_sowingplant_details psd
            LEFT JOIN krf_polyhouse_master phm ON psd.polyhouseid = phm.polyhouseid
            WHERE psd.stockorder_no = ? AND (phm.polyhousename IS NOT NULL OR psd.locationid IS NOT NULL)
            ORDER BY CASE WHEN phm.polyhousename IS NOT NULL THEN 1 ELSE 2 END, psd.sowingplantid DESC
            LIMIT 1
        ");
        if ($stmt2) {
            $stmt2->bind_param("s", $bStockNo);
            $stmt2->execute();
            $row2 = $stmt2->get_result()->fetch_assoc();
            $stmt2->close();
            if ($row2 && !empty($row2['polyhouse_name'])) {
                return trim($row2['polyhouse_name']);
            }
        }
        $stmt3 = $krfconn->prepare("
            SELECT locationname
            FROM krf_readytosale_stocks
            WHERE stockorder_no = ? AND locationname IS NOT NULL AND locationname != ''
            LIMIT 1
        ");
        if ($stmt3) {
            $stmt3->bind_param("s", $bStockNo);
            $stmt3->execute();
            $row3 = $stmt3->get_result()->fetch_assoc();
            $stmt3->close();
            if ($row3 && !empty($row3['locationname'])) {
                return trim($row3['locationname']);
            }
        }
    }
    if (!empty($allocatedBedsJson)) {
        $dec = is_string($allocatedBedsJson) ? json_decode($allocatedBedsJson, true) : $allocatedBedsJson;
        if (is_array($dec) && !empty($dec[0]['polyhouse_name'])) {
            return trim($dec[0]['polyhouse_name']);
        }
    }
    return 'Karjee';
}
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action == "GetActiveFinalDispatchDates") {
    $projectid = intval($_POST['projectid'] ?? 0);
    if ($projectid <= 0 && isset($_SESSION['projectid']) && intval($_SESSION['projectid']) > 0) {
        $projectid = intval($_SESSION['projectid']);
    }
    if ($projectid <= 0) {
        $projectid = 17;
    }
    
    // Fetch unique delivery/dispatch dates from sales order items and stock orders
    $sql = "
        SELECT DISTINCT soi.deliverydate AS dispatch_date
        FROM krf_sales_order_items soi
        JOIN krf_sales_order so ON soi.sono = so.sono
        WHERE (soi.projectid = ? OR so.projectid = ?)
          AND soi.deliverydate IS NOT NULL 
          AND soi.deliverydate != '0000-00-00'
        UNION
        SELECT DISTINCT pso.expected_delivery_date AS dispatch_date
        FROM krf_production_stockorder pso
        WHERE pso.projectid = ?
          AND pso.expected_delivery_date IS NOT NULL 
          AND pso.expected_delivery_date != '0000-00-00'
        ORDER BY dispatch_date ASC
    ";
    
    $stmt = $krfconn->prepare($sql);
    $stmt->bind_param("iii", $projectid, $projectid, $projectid);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $dates = [];
    while ($row = $res->fetch_assoc()) {
        if (!empty($row['dispatch_date'])) {
            $dates[] = ["dispatch_date" => $row['dispatch_date']];
        }
    }
    echo json_encode($dates);
    exit;

} else if ($action == "GetFinalDispatchScheduleData") {
    $projectid = intval($_POST['projectid'] ?? 0);
    if ($projectid <= 0 && isset($_SESSION['projectid']) && intval($_SESSION['projectid']) > 0) {
        $projectid = intval($_SESSION['projectid']);
    }
    if ($projectid <= 0) {
        $projectid = 17;
    }
    $dateFrom = trim($_POST['datefrom'] ?? '');
    $dateTo = trim($_POST['dateto'] ?? '');
    $isOldPending = (isset($_POST['is_old_pending']) && $_POST['is_old_pending'] == '1') || (isset($_POST['fetch_type']) && $_POST['fetch_type'] == 'old_pending');

    // Determine cutoff date for Old Pending (orders prior to current week / selected week)
    $curMonday = date('Y-m-d', strtotime('monday this week'));
    if ($isOldPending) {
        if (empty($dateFrom) || $dateFrom === 'all' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $dateFrom = $curMonday;
        }
    }

    // 1. Calculate Old Pending Summary (Prior to dateFrom or current week)
    $oldPendingCount = 0;
    $oldPendingQty = 0;
    $opCutoffDate = (!empty($dateFrom) && $dateFrom !== 'all' && $dateFrom >= $curMonday) ? $dateFrom : $curMonday;
    if (!empty($opCutoffDate)) {
        $oldPendingSql = "
            SELECT 
                COUNT(soi.id) AS old_pending_count,
                SUM(IFNULL(soi.quantity, 0)) AS old_pending_qty
            FROM krf_sales_order_items soi
            JOIN krf_sales_order so ON soi.sono = so.sono
            WHERE (soi.projectid = ? OR so.projectid = ?)
              AND soi.deliverydate < ?
              AND soi.deliverydate IS NOT NULL
              AND soi.deliverydate != '0000-00-00'
              AND (soi.tedit IS NULL OR soi.tedit = '' OR soi.tedit = '0')
              AND (so.tedit IS NULL OR so.tedit = '' OR so.tedit = '0')
              AND LOWER(IFNULL(so.status, '')) NOT IN ('delivered', 'completed', 'dispatched', 'fulldispatch', 'cancelled')
              AND LOWER(IFNULL(soi.status, '')) NOT IN ('allocated', 'partial allocated', 'partialallocated', 'delivered', 'completed', 'dispatched', 'cancelled')
              AND IFNULL(soi.allocate_ordqty, 0) <= 0
        ";
        $opStmt = $krfconn->prepare($oldPendingSql);
        if ($opStmt) {
            $opStmt->bind_param("iis", $projectid, $projectid, $opCutoffDate);
            $opStmt->execute();
            $opRow = $opStmt->get_result()->fetch_assoc();
            $oldPendingCount = (int)($opRow['old_pending_count'] ?? 0);
            $oldPendingQty = (int)($opRow['old_pending_qty'] ?? 0);
        }
    }

    // 2. Build Date Filter Condition
    $dateFilter = "AND soi.deliverydate BETWEEN ? AND ?";
    if ($isOldPending) {
        $dateFilter = "AND soi.deliverydate < ? 
          AND LOWER(IFNULL(so.status, '')) NOT IN ('delivered', 'completed', 'dispatched', 'fulldispatch', 'cancelled')
          AND LOWER(IFNULL(soi.status, '')) NOT IN ('allocated', 'partial allocated', 'partialallocated', 'delivered', 'completed', 'dispatched', 'cancelled')
          AND IFNULL(soi.allocate_ordqty, 0) <= 0";
    }

    // 3. Main Query: Sales Orders, Items, Consumed, and Stock Order details
    $sql = "
        SELECT 
            soi.id AS soi_id,
            soi.sono,
            soi.psono,
            soi.product_id,
            soi.product_name,
            rsa.productname AS rsa_productname,
            rsa.productid AS rsa_productid,
            rsa.allocatedbed AS rsa_allocatedbed,
            rsa.allocate_ordqty AS rsa_allocate_ordqty,
            rsa.dispatchqty AS rsa_dispatchqty,
            rsa.status AS rsa_status,
            rsa.invoice_number AS rsa_invoice_number,
            rsa.invoice_date AS rsa_invoice_date,
            COALESCE(NULLIF(TRIM(alloc_pso.product_name), ''), NULLIF(TRIM(pso.product_name), ''), NULLIF(TRIM(alloc_rts.productname), ''), NULLIF(TRIM(rsa.productname), ''), soi.product_name) AS allocated_product_name,
            COALESCE(NULLIF(TRIM(alloc_pso.productid), ''), NULLIF(TRIM(pso.productid), ''), NULLIF(TRIM(alloc_rts.productid), ''), NULLIF(TRIM(rsa.productid), ''), soi.product_id) AS allocated_product_id,
            soi.quantity AS order_qty,
            soi.deliverydate AS delivery_date,
            soi.confirmdeliverydate AS confirm_delivery_date,
            soi.confirmdeliverydateremark AS confirm_delivery_date_remark,
            IFNULL(soi.reservedqty, 0) AS reserved_qty,
            IFNULL(soi.allocate_ordqty, 0) AS allocate_ordqty,
            IFNULL(soi.balancereservedqty, IFNULL(soi.quantity, 0) - IFNULL(soi.reservedqty, 0)) AS balance_reserved_qty,
            IFNULL(soi.status, 'Pending') AS item_status,
            soi.stockorder_no AS soi_stockorderno,
            soi.invoiceno AS soi_invoiceno,
            soi.invoicedate AS soi_invoicedate,
            
            so.clientid,
            so.clientname,
            so.mobileno,
            so.statename,
            so.districtname,
            so.delivery_address,
            IFNULL(so.status, 'Pending') AS sales_order_status,
            so.created_at AS sales_order_created_at,
            so.created_by AS so_created_by,
            so.invoicetype,
            so.dinvoiceno,
            
            poc.id AS consumed_id,
            poc.stockorderno AS consumed_stockorderno,
            poc.allocated_stockorderno AS poc_allocated_stockorderno,
            poc.allocated_psono AS poc_allocated_psono,
            IFNULL(poc.consumed_qty, 0) AS consumed_qty,
            IFNULL(poc.confirmstatus, 'Pending') AS consumed_confirmstatus,
            poc.status AS consumed_status,
            poc.allocatedat AS allocated_at,
            poc.allocatedby AS allocated_by_id,
            poc.dispatchedat AS dispatched_at,
            poc.dispatchedby AS dispatched_by_id,
            poc.remark AS consumed_remark,
            poc.isshifted,
            poc.percentextrashifting,
            poc.extraqtyforshifting,
            poc.allocate_healthyplant,
            poc.allocate_unhealthyplant,
            
            pso.id AS pso_id,
            pso.clientid AS pso_clientid,
            pso.psono AS pso_psono,
            pso.remarks AS pso_remarks,
            pso.po_number AS pso_po_number,
            COALESCE(pso_cm.client_name, '') AS pso_client_name,
            COALESCE(pso_cm.is_trader, 0) AS pso_is_trader,
            COALESCE(NULLIF(poc.stockorderno, ''), NULLIF(soi.stockorder_no, ''), 'N/A') AS original_sown_stockorderno,
            poc.allocated_stockorderno,
            COALESCE(NULLIF(poc.allocated_psono, ''), alloc_pso.psono, '') AS allocated_psono,
            COALESCE(alloc_so.clientid, alloc_pso_so.clientid, '') AS allocated_clientid,
            COALESCE(alloc_so.clientname, alloc_pso_so.clientname, '') AS allocated_clientname,
            COALESCE(alloc_so.sono, alloc_pso_so.sono, '') AS allocated_sono,
            COALESCE(NULLIF(poc.allocated_stockorderno, ''), poc.stockorderno, soi.stockorder_no, pso.stockorder_no, 'Not Allocated') AS effective_stockorderno,
            pso.quantity AS pso_total_qty,
            pso.expected_delivery_date AS pso_expected_delivery_date,
            pso.sowingdate AS pso_sowingdate,
            pso.status AS pso_status,
            pso.workingstatus AS pso_workingstatus,
            pso.dispatchqty AS pso_dispatchqty,

            dl.id AS dl_id,
            dl.pickuptype AS dl_pickuptype,
            dl.deliveryaddress AS dl_deliveryaddress,
            dl.dest_state AS dl_dest_state,
            dl.dest_district AS dl_dest_district,
            dl.dest_city AS dl_dest_city,
            dl.dest_pincode AS dl_dest_pincode,
            dl.totaldistance AS dl_totaldistance,
            dl.freedistance AS dl_freedistance,
            dl.distance AS dl_distance,
            dl.freightamt AS dl_freightamt,
            dl.remark AS dl_remark,
            dl.boxperpack AS dl_boxperpack,
            dl.chargesperbox AS dl_chargesperbox,
            dl.cityid AS dl_cityid,
            dl.stateid AS dl_stateid,
            dl.districtid AS dl_districtid,
            dl.noofbox AS dl_noofbox,
            dl.totalamount AS dl_totalamount,
            dl.pickuppoint AS dl_pickuppoint,
            dl.deliverycharges AS dl_deliverycharges,
            dl.delivery_status AS dl_delivery_status,
            dl.vehicleno AS dl_vehicleno,
            dl.drivername AS dl_drivername,
            dl.drivercontactno AS dl_drivercontactno,

            vth.id AS ova_id,
            COALESCE(vth.vehicle_id, vt.vehicle_id, 0) AS ova_vehicle_id,
            COALESCE(vth.vehicle_no, vt.vehicle_no, '') AS ova_vehicleno,
            COALESCE(vm.vehiclename, '') AS ova_vehiclename,
            COALESCE(vm.vehicleownername, '') AS ova_vehicleownername,
            COALESCE(vth.driver_name, vt.driver_name, '') AS ova_drivername,
            COALESCE(vth.driver_mobileno, vt.driver_mobile_no, '') AS ova_drivermobileno,
            'vehicle_allocation' AS ova_allocation_source,
            COALESCE(vt.status, 'Allocated') AS ova_allocation_status

        FROM krf_sales_order_items soi
        JOIN krf_sales_order so ON soi.sono = so.sono
        LEFT JOIN krf_productionorder_consumed poc ON poc.id = (
            SELECT poc2.id 
            FROM krf_productionorder_consumed poc2 
            WHERE (poc2.psono = soi.psono OR (poc2.sono = soi.sono AND soi.sono != ''))
              AND poc2.product_id = soi.product_id
              AND IFNULL(poc2.tedit, '') = ''
            ORDER BY 
              CASE WHEN NULLIF(TRIM(soi.stockorder_no), '') IS NOT NULL AND poc2.stockorderno = soi.stockorder_no AND ROUND(poc2.consumed_qty, 0) = ROUND(soi.quantity, 0) THEN 1
                   WHEN NULLIF(TRIM(soi.stockorder_no), '') IS NOT NULL AND poc2.stockorderno = soi.stockorder_no THEN 2
                   WHEN ROUND(poc2.consumed_qty, 0) = ROUND(soi.quantity, 0) THEN 3
                   ELSE 4 END,
              CASE WHEN poc2.status = 'allocated' THEN 1 WHEN poc2.status = 'dispatched' THEN 2 ELSE 3 END,
              poc2.id DESC 
            LIMIT 1
        )
        LEFT JOIN krf_production_stockorder pso ON (COALESCE(NULLIF(poc.allocated_stockorderno, ''), poc.stockorderno, soi.stockorder_no) = pso.stockorder_no)
        LEFT JOIN krf_client_master pso_cm ON (pso_cm.client_id = pso.clientid)
        LEFT JOIN krf_sales_order alloc_so ON (alloc_so.psono = poc.allocated_psono AND poc.allocated_psono IS NOT NULL AND poc.allocated_psono != '')
        LEFT JOIN krf_production_stockorder alloc_pso ON (alloc_pso.stockorder_no = poc.allocated_stockorderno AND poc.allocated_stockorderno IS NOT NULL AND poc.allocated_stockorderno != '')
        LEFT JOIN krf_sales_order alloc_pso_so ON (alloc_pso_so.psono = alloc_pso.psono AND alloc_pso.psono IS NOT NULL AND alloc_pso.psono != '')
        LEFT JOIN (
            SELECT stockorder_no, productid, productname 
            FROM krf_readytosale_stocks 
            GROUP BY stockorder_no
        ) alloc_rts ON (alloc_rts.stockorder_no = COALESCE(NULLIF(poc.allocated_stockorderno, ''), poc.stockorderno, soi.stockorder_no))
        LEFT JOIN krf_readystock_allocation rsa ON rsa.id = (
            SELECT rsa2.id FROM krf_readystock_allocation rsa2 
            WHERE (
                (rsa2.psono = so.psono AND (rsa2.productid = soi.product_id OR rsa2.productname = soi.product_name))
                OR rsa2.allocate_stockorder_no = COALESCE(NULLIF(poc.allocated_stockorderno, ''), poc.stockorderno, soi.stockorder_no)
                OR rsa2.stockorder_no = COALESCE(NULLIF(poc.allocated_stockorderno, ''), poc.stockorderno, soi.stockorder_no)
                OR (rsa2.clientid = so.clientid AND (rsa2.productid = soi.product_id OR rsa2.productname = soi.product_name))
            )
            AND LOWER(IFNULL(rsa2.status, '')) != 'cancel'
            AND (rsa2.tedit IS NULL OR rsa2.tedit = '' OR rsa2.tedit = '0')
            ORDER BY 
              CASE WHEN rsa2.psono = so.psono AND (rsa2.productid = soi.product_id OR rsa2.productname = soi.product_name) THEN 1
                   WHEN rsa2.allocate_stockorder_no = COALESCE(NULLIF(poc.allocated_stockorderno, ''), poc.stockorderno, soi.stockorder_no) THEN 2 
                   WHEN rsa2.stockorder_no = COALESCE(NULLIF(poc.allocated_stockorderno, ''), poc.stockorderno, soi.stockorder_no) THEN 3 
                   ELSE 4 END,
              rsa2.id DESC 
            LIMIT 1
        )
        LEFT JOIN krf_vehicle_travel_history vth ON vth.id = (
            SELECT vth2.id 
            FROM krf_vehicle_travel_history vth2 
            WHERE (
                (COALESCE(vth2.stockorder_no, '') != '' AND (vth2.stockorder_no = so.sono OR vth2.stockorder_no = soi.stockorder_no)) OR 
                (COALESCE(vth2.psono, '') != '' AND vth2.psono = so.psono)
            )
            ORDER BY vth2.id DESC 
            LIMIT 1
        )
        LEFT JOIN krf_vehicletrip vt ON (vt.trip_no = vth.trip_id OR vt.trip_id = vth.trip_id)
        LEFT JOIN krf_vehicle_master vm ON (vm.vehicleid = COALESCE(vth.vehicle_id, vt.vehicle_id) AND vm.vehicleid > 0)
        LEFT JOIN krf_deliveryaddress_location dl ON dl.id = (
            SELECT d2.id 
            FROM krf_deliveryaddress_location d2 
            WHERE (d2.projectid = soi.projectid OR d2.projectid = so.projectid)
              AND (
                  (COALESCE(d2.psono,'') != '' AND COALESCE(d2.psono,'') = COALESCE(soi.psono,''))
                  OR (COALESCE(d2.sono,'') != '' AND COALESCE(d2.sono,'') = COALESCE(soi.sono,''))
              )
              AND (COALESCE(d2.productid,'') = '' OR COALESCE(d2.productid,'') = COALESCE(soi.product_id,''))
              AND (COALESCE(d2.clientid,'') = '' OR COALESCE(d2.clientid,'') = COALESCE(so.clientid,''))
            ORDER BY d2.id DESC 
            LIMIT 1
        )
        WHERE (soi.projectid = ? OR so.projectid = ?)
          AND soi.deliverydate IS NOT NULL 
          AND soi.deliverydate != '0000-00-00'
          AND (soi.tedit IS NULL OR soi.tedit = '' OR soi.tedit = '0')
          AND (so.tedit IS NULL OR so.tedit = '' OR so.tedit = '0')
          {$dateFilter}
        ORDER BY soi.deliverydate ASC, so.id DESC
    ";

    $stmt = $krfconn->prepare($sql);
    if ($isOldPending) {
        $stmt->bind_param("iis", $projectid, $projectid, $dateFrom);
    } else {
        $stmt->bind_param("iiss", $projectid, $projectid, $dateFrom, $dateTo);
    }
    $stmt->execute();
    $res = $stmt->get_result();

    $userIds = [];
    $data = [];
    $stockOrderNos = [];

    while ($row = $res->fetch_assoc()) {
        if (!empty($row['allocated_by_id'])) $userIds[] = $row['allocated_by_id'];
        if (!empty($row['dispatched_by_id'])) $userIds[] = $row['dispatched_by_id'];
        if (!empty($row['so_created_by'])) $userIds[] = $row['so_created_by'];
        
        $effStk = trim($row['effective_stockorderno'] ?? '');
        if (!empty($effStk) && $effStk !== 'Not Allocated') {
            $stockOrderNos[] = $effStk;
        }
        $pocAllocStk = trim($row['poc_allocated_stockorderno'] ?? '');
        if (!empty($pocAllocStk) && $pocAllocStk !== 'Not Allocated') {
            $stockOrderNos[] = $pocAllocStk;
        }
        $origStk = trim($row['consumed_stockorderno'] ?? $row['soi_stockorderno'] ?? '');
        if (!empty($origStk) && $origStk !== 'Not Allocated') {
            $stockOrderNos[] = $origStk;
        }

        $row['effective_vehicleno'] = !empty($row['ova_vehicleno']) ? $row['ova_vehicleno'] : ($row['dl_vehicleno'] ?? '');
        $row['effective_vehiclename'] = !empty($row['ova_vehiclename']) ? $row['ova_vehiclename'] : '';
        $row['effective_drivername'] = !empty($row['ova_drivername']) ? $row['ova_drivername'] : ($row['dl_drivername'] ?? '');
        $row['effective_drivermobile'] = !empty($row['ova_drivermobileno']) ? $row['ova_drivermobileno'] : ($row['dl_drivercontactno'] ?? '');
        $row['is_vehicle_allocated'] = !empty($row['effective_vehicleno']);

        $data[] = $row;
    }

    // 4. Batch Polyhouse Plants Summary for all active stock orders
    $plantMap = [];
    $stockOrderNos = array_values(array_unique($stockOrderNos));
    if (!empty($stockOrderNos)) {
        $inPlaceholders = implode(',', array_fill(0, count($stockOrderNos), '?'));
        $plantSql = "
            SELECT 
                ppd.stockorder_no,
                COUNT(ppd.polyhouseplantid) AS polyhouse_tray_count,
                SUM(CASE WHEN LOWER(IFNULL(ppd.health_status, '')) IN ('healthy','unhealthy') AND LOWER(IFNULL(ppd.status, '')) IN ('openstock', 'polyhouse', 'readytosale') THEN GREATEST(0, CAST(IFNULL(ppd.qtypertray, 0) AS SIGNED) - CAST(IFNULL(ppd.failurecount, 0) AS SIGNED)) ELSE 0 END) AS polyhouse_plant_qty,
                SUM(CASE WHEN LOWER(IFNULL(ppd.health_status, '')) IN ('healthy','unhealthy') AND (LOWER(IFNULL(ppd.status, '')) IN ('readytosale', 'openstock') OR LOWER(IFNULL(ppd.workingstatus, '')) IN ('readytosale', 'openstock')) THEN GREATEST(0, CAST(IFNULL(ppd.qtypertray, 0) AS SIGNED) - CAST(IFNULL(ppd.failurecount, 0) AS SIGNED)) ELSE 0 END) AS ready_plant_qty,
                SUM(CASE WHEN (LOWER(IFNULL(ppd.status, '')) IN ('final dispatch', 'dispatched', 'readytodispatch') OR LOWER(IFNULL(ppd.workingstatus, '')) IN ('final dispatch', 'dispatched', 'readytodispatch')) THEN GREATEST(0, CAST(IFNULL(ppd.qtypertray, 0) AS SIGNED) - CAST(IFNULL(ppd.failurecount, 0) AS SIGNED)) ELSE 0 END) AS dispatched_plant_qty,
                GROUP_CONCAT(DISTINCT ph.polyhousename SEPARATOR ', ') AS polyhouse_names,
                GROUP_CONCAT(DISTINCT NULLIF(TRIM(ppd.bedrackname), '') SEPARATOR ', ') AS bed_numbers
            FROM krf_polyhouse_plant_details ppd
            LEFT JOIN krf_polyhouse_master ph ON ppd.polyhouseid = ph.polyhouseid
            WHERE ppd.stockorder_no IN ($inPlaceholders)
            GROUP BY ppd.stockorder_no
        ";
        $pStmt = $krfconn->prepare($plantSql);
        if ($pStmt) {
            $pTypes = str_repeat('s', count($stockOrderNos));
            $pStmt->bind_param($pTypes, ...$stockOrderNos);
            $pStmt->execute();
            $pRes = $pStmt->get_result();
            while ($pRow = $pRes->fetch_assoc()) {
                $plantMap[$pRow['stockorder_no']] = $pRow;
            }
        }
    }

    // 5. Batch Dispatched Plants from sales_invoice_items (Commercial Dispatch Invoices)
    $dispatchedBySoiId = [];
    $dispatchedByPsonoProduct = [];
    $dispatchedBySonoProduct = [];

    $allSoiIds = array_values(array_filter(array_unique(array_map(function($r) { return intval($r['soi_id'] ?? 0); }, $data))));
    $allPsonos = array_values(array_filter(array_unique(array_map(function($r) { return trim($r['psono'] ?? ''); }, $data))));
    $allSonos = array_values(array_filter(array_unique(array_map(function($r) { return trim($r['sono'] ?? ''); }, $data))));

    if (!empty($allSoiIds) || !empty($allPsonos) || !empty($allSonos)) {
        $whereClauses = [];
        $types = "";
        $params = [];

        if (!empty($allSoiIds)) {
            $inSoi = implode(',', array_fill(0, count($allSoiIds), '?'));
            $whereClauses[] = "original_soi_id IN ($inSoi)";
            $types .= str_repeat('i', count($allSoiIds));
            foreach ($allSoiIds as $id) $params[] = $id;
        }
        if (!empty($allPsonos)) {
            $inPso = implode(',', array_fill(0, count($allPsonos), '?'));
            $whereClauses[] = "psono IN ($inPso)";
            $types .= str_repeat('s', count($allPsonos));
            foreach ($allPsonos as $p) $params[] = $p;
        }
        if (!empty($allSonos)) {
            $inSo = implode(',', array_fill(0, count($allSonos), '?'));
            $whereClauses[] = "sono IN ($inSo)";
            $types .= str_repeat('s', count($allSonos));
            foreach ($allSonos as $s) $params[] = $s;
        }

        $siiSql = "
            SELECT 
                original_soi_id,
                psono,
                sono,
                productid,
                SUM(quantity) AS total_dispatched_qty
            FROM sales_invoice_items
            WHERE (" . implode(' OR ', $whereClauses) . ")
              AND productid != 'BOX_PACKING'
              AND is_box_packing = 0
              AND LOWER(IFNULL(status, '')) != 'cancelled'
              AND (tedit IS NULL OR tedit = '' OR tedit = '0')
            GROUP BY original_soi_id, psono, sono, productid
        ";
        $siiStmt = $krfconn->prepare($siiSql);
        if ($siiStmt) {
            $siiStmt->bind_param($types, ...$params);
            $siiStmt->execute();
            $siiRes = $siiStmt->get_result();
            while ($siiRow = $siiRes->fetch_assoc()) {
                $qty = intval($siiRow['total_dispatched_qty'] ?? 0);
                $origId = intval($siiRow['original_soi_id'] ?? 0);
                $pso = trim($siiRow['psono'] ?? '');
                $so = trim($siiRow['sono'] ?? '');
                $prod = trim($siiRow['productid'] ?? '');

                if ($origId > 0) {
                    $dispatchedBySoiId[$origId] = ($dispatchedBySoiId[$origId] ?? 0) + $qty;
                }
                if (!empty($pso) && !empty($prod)) {
                    $k = $pso . '___' . $prod;
                    $dispatchedByPsonoProduct[$k] = ($dispatchedByPsonoProduct[$k] ?? 0) + $qty;
                }
                if (!empty($so) && !empty($prod)) {
                    $kSo = $so . '___' . $prod;
                    $dispatchedBySonoProduct[$kSo] = ($dispatchedBySonoProduct[$kSo] ?? 0) + $qty;
                }
            }
            $siiStmt->close();
        }
    }

    // 5b. Query active undispatched allocations from krf_readystock_allocation
    $activeAllocByPsonoProd = [];
    if (!empty($allPsonos)) {
        $inRsaPso = implode(',', array_fill(0, count($allPsonos), '?'));
        $rsaAllocSql = "
            SELECT 
                psono,
                productid,
                productname,
                SUM(CASE 
                    WHEN LOWER(IFNULL(status, '')) IN ('allocated', 'readytoload', 'readytodispatch')
                    THEN GREATEST(0, CAST(allocate_ordqty AS SIGNED) - CAST(dispatchqty AS SIGNED)) 
                    ELSE 0 
                END) AS active_undispatched_alloc
            FROM krf_readystock_allocation
            WHERE psono IN ($inRsaPso)
              AND LOWER(IFNULL(status, '')) NOT IN ('cancel', 'cancelled')
              AND (tedit IS NULL OR tedit = '' OR tedit = '0')
            GROUP BY psono, productid
        ";
        $rsaAllocStmt = $krfconn->prepare($rsaAllocSql);
        if ($rsaAllocStmt) {
            $rsaTypes = str_repeat('s', count($allPsonos));
            $rsaAllocStmt->bind_param($rsaTypes, ...$allPsonos);
            $rsaAllocStmt->execute();
            $rsaAllocRes = $rsaAllocStmt->get_result();
            while ($rsaAllocRow = $rsaAllocRes->fetch_assoc()) {
                $psoKey = trim($rsaAllocRow['psono'] ?? '') . '___' . trim(strval($rsaAllocRow['productid'] ?? ''));
                $activeAllocByPsonoProd[$psoKey] = intval($rsaAllocRow['active_undispatched_alloc'] ?? 0);
            }
            $rsaAllocStmt->close();
        }
    }

    // 6. Resolve User Names from Auth DB
    $userMap = [
        '8871765657' => 'Niteesh Mahant'
    ];
    if (!empty($userIds)) {
        $userIds = array_values(array_unique($userIds));
        $auth_db = $config['db_auth'];
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $uSql = "SELECT loginid, mobileno, loginname FROM login_user_master WHERE loginid IN ($placeholders) OR mobileno IN ($placeholders)";
        $uStmt = $authconn->prepare($uSql);
        if ($uStmt) {
            $types = str_repeat('s', count($userIds) * 2);
            $bindParams = array_merge($userIds, $userIds);
            $uStmt->bind_param($types, ...$bindParams);
            $uStmt->execute();
            $uRes = $uStmt->get_result();
            while ($uRow = $uRes->fetch_assoc()) {
                if (!empty($uRow['loginid'])) $userMap[$uRow['loginid']] = $uRow['loginname'];
                if (!empty($uRow['mobileno'])) $userMap[$uRow['mobileno']] = $uRow['loginname'];
            }
        }
    }

    // 6b. Check per-product cancellation and whole-psono cancellation
    $soiTotalCount = [];
    $soiCancelledCount = [];
    $cancelledSoiProducts = [];

    $psoiTotalCount = [];
    $psoiCancelledCount = [];
    $cancelledPsoiProducts = [];

    if (!empty($allPsonos)) {
        $inPsoCancel = implode(',', array_fill(0, count($allPsonos), '?'));
        $typesPso = str_repeat('s', count($allPsonos));

        // 1. Check all items for these psonos in krf_sales_order_items
        $cStmt1 = $krfconn->prepare("
            SELECT psono, product_id, product_name, status 
            FROM krf_sales_order_items 
            WHERE psono IN ($inPsoCancel)
              AND (tedit IS NULL OR tedit = '' OR tedit = '0')
        ");
        if ($cStmt1) {
            $cStmt1->bind_param($typesPso, ...$allPsonos);
            $cStmt1->execute();
            $cRes1 = $cStmt1->get_result();
            while ($cRow = $cRes1->fetch_assoc()) {
                $p = trim($cRow['psono'] ?? '');
                if ($p !== '') {
                    $soiTotalCount[$p] = ($soiTotalCount[$p] ?? 0) + 1;
                    $st = strtolower(trim($cRow['status'] ?? ''));
                    if (in_array($st, ['cancelled', 'cancel'])) {
                        $soiCancelledCount[$p] = ($soiCancelledCount[$p] ?? 0) + 1;
                        $pid = trim(strval($cRow['product_id'] ?? ''));
                        if ($pid !== '') {
                            $cancelledSoiProducts[$p . '___' . $pid] = true;
                        }
                        $pn = strtolower(trim($cRow['product_name'] ?? ''));
                        if ($pn !== '') {
                            $cancelledSoiProducts[$p . '___' . $pn] = true;
                        }
                    }
                }
            }
            $cStmt1->close();
        }

        // 2. Check all items for these psonos in krf_provisional_sales_order_items
        $cStmt2 = $krfconn->prepare("
            SELECT psono, product_id, product_name, status 
            FROM krf_provisional_sales_order_items 
            WHERE psono IN ($inPsoCancel)
              AND (tedit IS NULL OR tedit = '' OR tedit = '0')
        ");
        if ($cStmt2) {
            $cStmt2->bind_param($typesPso, ...$allPsonos);
            $cStmt2->execute();
            $cRes2 = $cStmt2->get_result();
            while ($cRow = $cRes2->fetch_assoc()) {
                $p = trim($cRow['psono'] ?? '');
                if ($p !== '') {
                    $psoiTotalCount[$p] = ($psoiTotalCount[$p] ?? 0) + 1;
                    $st = strtolower(trim($cRow['status'] ?? ''));
                    if (in_array($st, ['cancelled', 'cancel'])) {
                        $psoiCancelledCount[$p] = ($psoiCancelledCount[$p] ?? 0) + 1;
                        $pid = trim(strval($cRow['product_id'] ?? ''));
                        if ($pid !== '') {
                            $cancelledPsoiProducts[$p . '___' . $pid] = true;
                        }
                        $pn = strtolower(trim($cRow['product_name'] ?? ''));
                        if ($pn !== '') {
                            $cancelledPsoiProducts[$p . '___' . $pn] = true;
                        }
                    }
                }
            }
            $cStmt2->close();
        }
    }

    // 7. Merge Plant Info & Status Categorization
    foreach ($data as &$item) {
        $allocId = trim($item['allocated_by_id'] ?? '');
        $item['allocated_by_name'] = $userMap[$allocId] ?? ($allocId === '8871765657' ? 'Niteesh Mahant' : ($allocId ?: '-'));
        $item['so_created_by_name'] = $userMap[$item['so_created_by']] ?? $item['so_created_by'] ?? '-';
        $item['dispatched_by_name'] = $userMap[$item['dispatched_by_id']] ?? ($item['dispatched_by_id'] === '8871765657' ? 'Niteesh Mahant' : ($item['dispatched_by_id'] ?: '-'));
        
        $item['formatted_delivery_date'] = !empty($item['delivery_date']) ? date('d-M-Y', strtotime($item['delivery_date'])) : '-';
        $item['original_delivery_date'] = $item['delivery_date'] ?? '';
        $item['formatted_original_delivery_date'] = !empty($item['delivery_date']) ? date('d-M-Y', strtotime($item['delivery_date'])) : '-';
        $item['formatted_confirm_delivery_date'] = (!empty($item['confirm_delivery_date']) && $item['confirm_delivery_date'] !== '0000-00-00') ? date('d-M-Y', strtotime($item['confirm_delivery_date'])) : '-';
        $item['is_rescheduled'] = (!empty($item['confirm_delivery_date']) && $item['confirm_delivery_date'] !== '0000-00-00' && $item['confirm_delivery_date'] !== $item['delivery_date']);
        $item['formatted_sowing_date'] = !empty($item['pso_sowingdate']) ? date('d-M-Y', strtotime($item['pso_sowingdate'])) : '-';
        $item['formatted_pso_delivery_date'] = !empty($item['pso_expected_delivery_date']) ? date('d-M-Y', strtotime($item['pso_expected_delivery_date'])) : '-';
        $item['formatted_booking_date'] = !empty($item['sales_order_created_at']) && $item['sales_order_created_at'] !== '0000-00-00 00:00:00' ? date('d-M-Y', strtotime($item['sales_order_created_at'])) : '-';
        $item['formatted_allocated_at'] = !empty($item['allocated_at']) ? date('d-M-Y H:i', strtotime($item['allocated_at'])) : '-';
        $item['formatted_dispatched_at'] = !empty($item['dispatched_at']) ? date('d-M-Y H:i', strtotime($item['dispatched_at'])) : '-';

        $origStock = trim($item['original_sown_stockorderno'] ?? $item['stockorder_no'] ?? '');
        $allocStock = trim(!empty($item['allocated_stockorderno']) ? $item['allocated_stockorderno'] : ($item['effective_stockorderno'] ?? ''));
        $effStk = trim($item['effective_stockorderno'] ?? '');
        $psoClientId = trim($item['pso_clientid'] ?? '');
        $soClientId = trim($item['clientid'] ?? '');
        $psoPsono = trim($item['pso_psono'] ?? '');
        $soPsono = trim($item['psono'] ?? $item['soi_psono'] ?? '');
        $psoRemarks = trim($item['pso_remarks'] ?? '');
        $psoIsTrader = intval($item['pso_is_trader'] ?? 0);

        // Determine if effective stock order is Open Stock / Trader Production Order
        $isOpenStock = false;
        if (!empty($effStk) && $effStk !== 'Not Allocated' && $effStk !== '0') {
            if (
                strpos($effStk, 'RSO-') === 0 || 
                strpos($effStk, 'RTS-') === 0 || 
                strpos($effStk, 'READY-') === 0 ||
                $psoClientId === 'C00001' || 
                $psoIsTrader === 1 || 
                stripos($psoRemarks, 'Ready Stock') !== false ||
                ($psoPsono === $effStk && strpos($effStk, 'RSO-') === 0)
            ) {
                $isOpenStock = true;
            }
        }

        // Determine if effective stock order is Own Sown (sown specifically for this client/order)
        $isOwnSown = false;
        if (!$isOpenStock && !empty($effStk) && $effStk !== 'Not Allocated' && $effStk !== '0') {
            if (
                (!empty($psoPsono) && !empty($soPsono) && $psoPsono === $soPsono) ||
                (!empty($psoClientId) && !empty($soClientId) && $psoClientId === $soClientId && $psoClientId !== 'C00001')
            ) {
                $isOwnSown = true;
            }
        }

        // Determine if Swapped (sown for another client)
        $isSwapped = false;
        if (!$isOpenStock && !$isOwnSown && !empty($effStk) && $effStk !== 'Not Allocated' && $effStk !== '0') {
            $isSwapped = true;
        } else if (!empty($origStock) && !empty($allocStock) && $origStock !== 'N/A' && $allocStock !== 'Not Allocated' && $origStock !== '0' && $allocStock !== '0' && $origStock !== $allocStock && !$isOpenStock) {
            $isSwapped = true;
        }

        $item['is_open_stock'] = $isOpenStock ? 1 : 0;
        $item['is_own_sown'] = $isOwnSown ? 1 : 0;
        $item['is_swapped'] = $isSwapped ? 1 : 0;
        $item['stock_type_label'] = $isOpenStock ? 'Open Stock' : ($isOwnSown ? 'Own Sown' : ($isSwapped ? 'Swapped' : 'Unallocated'));
        $item['original_sown_stockorderno'] = $origStock;
        $item['allocated_stockorderno'] = $allocStock;

        $rawProductName = !empty($item['allocated_product_name']) ? $item['allocated_product_name'] : ($item['product_name'] ?? '');
        $item['clean_product_name'] = preg_replace('/^(NonGrafted|Non-Grafted)\s*[-:]?\s*/i', '', $rawProductName);
        $item['clean_booked_product_name'] = preg_replace('/^(NonGrafted|Non-Grafted)\s*[-:]?\s*/i', '', ($item['product_name'] ?? ''));
        $item['is_grafted'] = (stripos($rawProductName, 'grafted') !== false && stripos($rawProductName, 'nongrafted') === false && stripos($rawProductName, 'non-grafted') === false) ? 1 : 0;

        $stk = trim($item['effective_stockorderno'] ?? '');
        $origStk = trim($item['original_sown_stockorderno'] ?? '');
        $allocStk = trim($item['allocated_stockorderno'] ?? '');
        $soiStk = trim($item['soi_stockorderno'] ?? '');
        $pInfo = $plantMap[$stk] ?? $plantMap[$allocStk] ?? $plantMap[$origStk] ?? $plantMap[$soiStk] ?? [];
        $item['polyhouse_tray_count'] = (int)($pInfo['polyhouse_tray_count'] ?? 0);
        $item['polyhouse_plant_qty'] = (int)($pInfo['polyhouse_plant_qty'] ?? 0);
        $item['ready_plant_qty'] = (int)($pInfo['ready_plant_qty'] ?? 0);
        $item['polyhouse_dispatched_plant_qty'] = (int)($pInfo['dispatched_plant_qty'] ?? 0);
        $item['polyhouse_names'] = !empty($pInfo['polyhouse_names']) ? $pInfo['polyhouse_names'] : 'N/A';

        // Resolve allocated bed numbers from polyhouse plant details or krf_readystock_allocation.allocatedbed
        $resolvedBed = '';
        if (!empty($item['rsa_allocatedbed'])) {
            $rawBed = trim($item['rsa_allocatedbed']);
            if (strpos($rawBed, '[') === 0) {
                $decBeds = json_decode($rawBed, true);
                if (is_array($decBeds)) {
                    $bList = [];
                    foreach ($decBeds as $db) {
                        if (is_string($db)) $bList[] = $db;
                        elseif (is_array($db)) $bList[] = $db['bed_name'] ?? ($db['bed'] ?? ($db['bedrackname'] ?? ''));
                    }
                    $bList = array_filter(array_unique($bList));
                    if (!empty($bList)) $resolvedBed = implode(', ', $bList);
                }
            } else {
                $resolvedBed = $rawBed;
            }
        }
        $item['allocatedbed'] = !empty($item['rsa_allocatedbed']) ? $item['rsa_allocatedbed'] : '';
        $item['allocated_bed_numbers'] = $resolvedBed;
        if (!empty($resolvedBed)) {
            $item['bed_numbers'] = $resolvedBed;
        } else {
            $item['bed_numbers'] = !empty($pInfo['bed_numbers']) ? $pInfo['bed_numbers'] : '-';
        }

        // Dispatched Qty STRICTLY retrieved from sales_invoice_items
        $soiId = intval($item['soi_id'] ?? 0);
        $psono = trim($item['psono'] ?? '');
        $sono = trim($item['sono'] ?? '');
        $prodId = trim($item['product_id'] ?? '');

        $siDispatchedQty = 0;
        if ($soiId > 0 && isset($dispatchedBySoiId[$soiId])) {
            $siDispatchedQty = $dispatchedBySoiId[$soiId];
        } elseif (!empty($psono) && !empty($prodId) && isset($dispatchedByPsonoProduct[$psono . '___' . $prodId])) {
            $siDispatchedQty = $dispatchedByPsonoProduct[$psono . '___' . $prodId];
        } elseif (!empty($sono) && !empty($prodId) && isset($dispatchedBySonoProduct[$sono . '___' . $prodId])) {
            $siDispatchedQty = $dispatchedBySonoProduct[$sono . '___' . $prodId];
        }
        $item['dispatched_plant_qty'] = $siDispatchedQty;
        $item['polyhouse_names'] = $pInfo['polyhouse_names'] ?? 'N/A';

        // Categorize Status based on Workflow:
        // 1) if Dispatched > 0 && balance > 0 -> Partial Dispatched
        // 2) if Dispatched > 0 && balance <= 0 (or fully dispatched) -> Fully Dispatched
        // 3) if Consumed Status is 'readytodispatch' -> Ready to Dispatch
        // 4) if Dispatch Invoice is Prepared/Saved -> Ready to Loading
        // 5) if Reserved > 0 && Dispatched <= 0 -> Allocated (For Prepared Invoice)
        // 6) else -> Pending Allocation
        $orderQty = intval($item['order_qty'] ?? 0);
        $soiReserved = intval($item['reserved_qty'] ?? 0);
        $soiAlloc = intval($item['allocate_ordqty'] ?? 0);
        $pocConsumed = intval($item['consumed_qty'] ?? 0);
        $dispatchedPlantQty = intval($item['dispatched_plant_qty'] ?? 0);
        $itemStatus = strtolower(trim($item['item_status'] ?? ''));
        $consumedStatus = strtolower(trim($item['consumed_status'] ?? ''));
        $consumedConfirm = strtolower(trim($item['consumed_confirmstatus'] ?? ''));

        $soStatus = strtolower(trim($item['sales_order_status'] ?? ''));

        // Determine if THIS SPECIFIC LINE ITEM has been allocated
        // Do NOT rely on parent $soStatus or initial booking $consumedConfirm!
        $isItemAllocated = (
            $soiAlloc > 0 ||
            $soiReserved > 0 ||
            in_array($itemStatus, ['allocated', 'readytoload', 'readytodispatch', 'dispatched', 'loaded', 'final dispatch']) ||
            in_array($consumedStatus, ['allocated', 'readytoload', 'readytodispatch', 'dispatched', 'loaded']) ||
            (!empty($item['allocated_stockorderno']) && $item['allocated_stockorderno'] !== 'Not Allocated' && $item['allocated_stockorderno'] !== '0' && in_array($consumedStatus, ['allocated', 'readytoload', 'readytodispatch', 'dispatched']))
        );

        $reservedQty = 0;
        if ($isItemAllocated) {
            $reservedQty = max($soiReserved, $soiAlloc);
            if ($reservedQty <= 0 && in_array($consumedStatus, ['allocated', 'readytoload', 'readytodispatch', 'dispatched', 'loaded'])) {
                $reservedQty = $pocConsumed;
            }
        }
        $item['reserved_qty'] = $reservedQty;
        $balanceQty = max(0, $orderQty - $reservedQty);
        $item['balance_reserved_qty'] = $balanceQty;

        $soiInvoiceNo = trim($item['soi_invoiceno'] ?? '');
        $dinvNo = trim($item['dinvoiceno'] ?? '');
        $invType = strtolower(trim($item['invoicetype'] ?? ''));
        // An invoice is only applicable to this item if the item has its own invoice OR is already allocated with a dispatch invoice
        $hasPreparedInvoice = (!empty($soiInvoiceNo) || ($isItemAllocated && (!empty($dinvNo) || $invType === 'dispatchinvoice' || $invType === 'dispatch')));
        $polyhousePlantQty = intval($item['polyhouse_plant_qty'] ?? 0);
        $readyPlantQty = intval($item['ready_plant_qty'] ?? 0);
        $hasPolyhousePlants = ($polyhousePlantQty > 0 || $readyPlantQty > 0);

        $effectiveDispatched = 0;
        $isPhysicallyDispatched = (
            $itemStatus === 'dispatched' || 
            $consumedStatus === 'dispatched' || 
            $itemStatus === 'final dispatch' ||
            (!empty($item['dispatched_at']) && $item['dispatched_at'] !== '0000-00-00 00:00:00' && $item['dispatched_at'] !== '-')
        );

        // Derive true active undispatched allocation (plants physically in beds waiting to be dispatched)
        $rsaKey = (!empty($psono) && !empty($prodId)) ? ($psono . '___' . $prodId) : '';
        $activeUndispAlloc = 0;
        if (!empty($rsaKey) && isset($activeAllocByPsonoProd[$rsaKey])) {
            $activeUndispAlloc = $activeAllocByPsonoProd[$rsaKey];
        } elseif (!empty($item['rsa_status']) && in_array(strtolower(trim($item['rsa_status'])), ['allocated', 'readytoload', 'readytodispatch'])) {
            $activeUndispAlloc = max(0, intval($item['rsa_allocate_ordqty'] ?? 0) - intval($item['rsa_dispatchqty'] ?? 0));
        } elseif (in_array($consumedStatus, ['allocated', 'readytoload', 'readytodispatch', 'loaded']) && $consumedStatus !== 'dispatched') {
            $activeUndispAlloc = max(0, $pocConsumed - $dispatchedPlantQty);
        } elseif (($itemStatus === 'allocated' || $itemStatus === 'readytoload') && $itemStatus !== 'dispatched') {
            $activeUndispAlloc = max(0, max($soiAlloc, $soiReserved) - $dispatchedPlantQty);
        }

        if ($isPhysicallyDispatched) {
            $activeUndispAlloc = 0;
        }

        $rsaStatus = strtolower(trim($item['rsa_status'] ?? ''));
        $rsaInvNo = trim($item['rsa_invoice_number'] ?? '');
        $isRsaReadyToLoad = ($rsaStatus === 'readytoload' || (!empty($rsaInvNo) && $rsaStatus !== 'dispatched' && $rsaStatus !== 'cancel'));

        $remainingUninvoicedAllocQty = max(0, $activeUndispAlloc - $dispatchedPlantQty);
        $isFullyInvoiced = ($dispatchedPlantQty > 0 && $remainingUninvoicedAllocQty === 0);
        $hasPreparedInvoiceItems = ($dispatchedPlantQty > 0 || ($isItemAllocated && $hasPreparedInvoice) || $isRsaReadyToLoad);

        // Attach standardized calculation flags
        $item['allocated_qty'] = $activeUndispAlloc;
        $item['dispatched_plant_qty'] = $dispatchedPlantQty;
        $item['remaining_uninvoiced_alloc_qty'] = $remainingUninvoicedAllocQty;
        $item['is_fully_invoiced'] = $isFullyInvoiced ? 1 : 0;
        $item['is_physically_dispatched'] = $isPhysicallyDispatched ? 1 : 0;
        $item['has_prepared_invoice'] = $hasPreparedInvoiceItems ? 1 : 0;
        $item['is_rsa_ready_to_load'] = $isRsaReadyToLoad ? 1 : 0;

        // Cancellation Check:
        // 1. If whole psono is cancelled (all products cancelled or SO/PSO status is cancelled):
        //    Tag is_whole_order_cancelled = 1 so it is filtered out and NOT displayed in the grid at all.
        // 2. If psono has multiple products and only 1 product is cancelled:
        //    ONLY that particular product item gets is_cancelled = 1 and stops action in the Action column.
        //    Other non-cancelled products in that psono remain active (is_cancelled = 0) and CAN do actions!
        $psoStatus = strtolower(trim($item['pso_status'] ?? ''));
        $psonoVal = trim($item['psono'] ?? '');
        $prodIdVal = trim(strval($item['product_id'] ?? ''));
        $prodNameVal = strtolower(trim($item['product_name'] ?? ''));

        $isWholeOrderCancelled = (
            in_array($soStatus, ['cancelled', 'cancel']) ||
            in_array($psoStatus, ['cancelled', 'cancel']) ||
            (!empty($psonoVal) && !empty($soiTotalCount[$psonoVal]) && ($soiCancelledCount[$psonoVal] ?? 0) >= $soiTotalCount[$psonoVal]) ||
            (!empty($psonoVal) && !empty($psoiTotalCount[$psonoVal]) && ($psoiCancelledCount[$psonoVal] ?? 0) >= $psoiTotalCount[$psonoVal])
        );

        $kProdId = ($psonoVal !== '' && $prodIdVal !== '') ? ($psonoVal . '___' . $prodIdVal) : '';
        $kProdName = ($psonoVal !== '' && $prodNameVal !== '') ? ($psonoVal . '___' . $prodNameVal) : '';

        $isThisProductCancelled = (
            $isWholeOrderCancelled ||
            in_array($itemStatus, ['cancelled', 'cancel']) ||
            ($kProdId !== '' && !empty($cancelledSoiProducts[$kProdId])) ||
            ($kProdName !== '' && !empty($cancelledSoiProducts[$kProdName])) ||
            ($kProdId !== '' && !empty($cancelledPsoiProducts[$kProdId])) ||
            ($kProdName !== '' && !empty($cancelledPsoiProducts[$kProdName]))
        );

        $item['is_whole_order_cancelled'] = $isWholeOrderCancelled ? 1 : 0;
        $item['is_cancelled'] = $isThisProductCancelled ? 1 : 0;
        $item['cancel_reason'] = $isThisProductCancelled ? ($isWholeOrderCancelled ? 'Order is cancelled' : 'This product is cancelled') : '';

        if ($isThisProductCancelled) {
            $item['dispatch_status_code'] = 'cancelled';
            $item['dispatch_status_label'] = 'Order Cancelled';
            $item['reserved_qty'] = 0;
            $item['balance_reserved_qty'] = 0;
            $item['remaining_uninvoiced_alloc_qty'] = 0;
            $item['is_fully_invoiced'] = 0;
            $item['has_prepared_invoice'] = 0;
            $item['is_physically_dispatched'] = 0;
        } else if ($isPhysicallyDispatched) {
            $effectiveDispatched = ($dispatchedPlantQty > 0) ? $dispatchedPlantQty : max($orderQty, $reservedQty);
            if ($effectiveDispatched > 0 && $balanceQty > 0 && $effectiveDispatched < $orderQty) {
                $item['dispatch_status_code'] = 'partial_dispatched';
                $item['dispatch_status_label'] = 'Partially Dispatched';
            } else {
                $item['dispatch_status_code'] = 'dispatched';
                $item['dispatch_status_label'] = 'Fully Dispatched';
            }
        } else if ($isRsaReadyToLoad || $isFullyInvoiced || $itemStatus === 'readytoload' || $consumedStatus === 'readytoload') {
            // Ready to Loading (as per Allocation Master krf_readystock_allocation or fully invoiced)
            $item['dispatch_status_code'] = 'readytoload';
            $item['dispatch_status_label'] = 'Ready to Loading';
        } else if ($reservedQty > 0 && $remainingUninvoicedAllocQty > 0) {
            // Has allocated plants and invoice dispatched is 0 or less than allocated:
            // MUST STILL SHOW IN ALLOCATED TAB!
            $item['dispatch_status_code'] = 'allocated';
            $item['dispatch_status_label'] = ($dispatchedPlantQty > 0) ? 'Partially Invoiced (Allocated)' : 'Allocated (For Prepared Invoice)';
        } else if ($isItemAllocated && $consumedStatus === 'readytodispatch' && ($hasPolyhousePlants || ($item['polyhouse_tray_count'] ?? 0) > 0)) {
            $item['dispatch_status_code'] = 'readytodispatch';
            $item['dispatch_status_label'] = 'Ready to Dispatch';
        } else if ($isItemAllocated && $hasPreparedInvoiceItems) {
            $item['dispatch_status_code'] = 'readytoload';
            $item['dispatch_status_label'] = 'Ready to Loading';
        } else if ($reservedQty > 0 || $consumedStatus === 'allocated' || $itemStatus === 'allocated') {
            $item['dispatch_status_code'] = 'allocated';
            $item['dispatch_status_label'] = 'Allocated (For Prepared Invoice)';
        } else {
            $item['dispatch_status_code'] = 'pending';
            $item['dispatch_status_label'] = 'Pending Allocation';
        }
    }
    unset($item);

    // If whole psono products are cancelled than no need to display in the grid
    $data = array_values(array_filter($data, function($row) {
        return empty($row['is_whole_order_cancelled']);
    }));

    // PARTIAL ORDER SPLITTING FOR ACCURATE SCHEDULE TRACKING:
    // If an order item has partial fulfillment (e.g., Order 200,000, Dispatched/Allocated 30,000),
    // emit the fulfilled tranche (30,000) for Dispatched/Allocated/ReadyToLoad tab,
    // AND emit a companion pending tranche (170,000) for the Pending Allocation tab!
    $expandedData = [];
    foreach ($data as $item) {
        $orderQty = intval($item['order_qty'] ?? 0);
        $dispatchedQty = intval($item['dispatched_plant_qty'] ?? 0);
        $allocatedQty = intval($item['allocated_qty'] ?? 0);
        $isCancelled = !empty($item['is_cancelled']);

        $fulfilledQty = ($dispatchedQty > 0) ? $dispatchedQty : $allocatedQty;
        $pendingBalance = max(0, $orderQty - ($allocatedQty + $dispatchedQty));

        $totalSoOrderQty = $orderQty;
        $totalSoAllocQty = $allocatedQty;
        $totalSoDispQty = $dispatchedQty;
        $totalSoPendingQty = $pendingBalance;

        if (!$isCancelled && $fulfilledQty > 0 && $pendingBalance > 0) {
            // 1. Fulfilled Tranche (shows in Final Dispatched / Allocated / ReadyToLoad)
            $fulfilledItem = $item;
            $fulfilledItem['row_id'] = $item['soi_id'] . '_fulfilled';
            $fulfilledItem['original_total_order_qty'] = $orderQty;
            $fulfilledItem['fulfilled_qty'] = $fulfilledQty;
            $fulfilledItem['pending_balance_qty'] = $pendingBalance;
            $fulfilledItem['has_partial_pending'] = 1;
            $fulfilledItem['order_qty'] = $orderQty;
            $fulfilledItem['balance_reserved_qty'] = 0;
            $fulfilledItem['total_so_order_qty'] = $totalSoOrderQty;
            $fulfilledItem['total_so_allocated_qty'] = $totalSoAllocQty;
            $fulfilledItem['total_so_dispatched_qty'] = $totalSoDispQty;
            $fulfilledItem['total_so_pending_qty'] = $totalSoPendingQty;
            $expandedData[] = $fulfilledItem;

            // 2. Residual Pending Tranche (shows in Pending Allocation tab)
            $pendingItem = $item;
            $pendingItem['row_id'] = $item['soi_id'] . '_pending';
            $pendingItem['soi_id'] = $item['soi_id'] . '_pending';
            $pendingItem['parent_soi_id'] = $item['soi_id'];
            $pendingItem['real_soi_id'] = $item['soi_id'];
            $pendingItem['is_partial_pending'] = 1;
            $pendingItem['original_total_order_qty'] = $orderQty;
            $pendingItem['fulfilled_qty'] = $fulfilledQty;
            $pendingItem['order_qty'] = $pendingBalance; // e.g. 170,000 Plants
            $pendingItem['allocated_qty'] = 0;
            $pendingItem['reserved_qty'] = 0;
            $pendingItem['allocate_ordqty'] = 0;
            $pendingItem['balance_reserved_qty'] = $pendingBalance;
            $pendingItem['dispatched_plant_qty'] = 0;
            $pendingItem['is_physically_dispatched'] = 0;
            $pendingItem['has_prepared_invoice'] = 0;
            $pendingItem['total_so_order_qty'] = $totalSoOrderQty;
            $pendingItem['total_so_allocated_qty'] = $totalSoAllocQty;
            $pendingItem['total_so_dispatched_qty'] = $totalSoDispQty;
            $pendingItem['total_so_pending_qty'] = $totalSoPendingQty;
            $pendingItem['dispatch_status_code'] = 'pending';
            $pendingItem['dispatch_status_label'] = 'Pending Allocation';
            $pendingItem['effective_stockorderno'] = (!empty($item['original_sown_stockorderno']) && $item['original_sown_stockorderno'] !== 'N/A') ? $item['original_sown_stockorderno'] : ($item['soi_stockorderno'] ?? 'Not Allocated');
            $pendingItem['allocated_stockorderno'] = 'Not Allocated';
            $pendingItem['allocated_by_name'] = '-';
            $pendingItem['formatted_allocated_at'] = '-';
            $pendingItem['bed_numbers'] = '-';
            $pendingItem['rsa_allocatedbed'] = '';
            $pendingItem['allocated_bed_numbers'] = '';
            $pendingItem['partial_note'] = 'Remaining balance of ' . number_format($pendingBalance) . ' Plants (Original Order: ' . number_format($orderQty) . ')';
            $expandedData[] = $pendingItem;
        } else {
            $item['row_id'] = strval($item['soi_id']);
            $item['original_total_order_qty'] = $orderQty;
            $item['fulfilled_qty'] = $fulfilledQty;
            $item['pending_balance_qty'] = max(0, $pendingBalance);
            $item['total_so_order_qty'] = $totalSoOrderQty;
            $item['total_so_allocated_qty'] = $totalSoAllocQty;
            $item['total_so_dispatched_qty'] = $totalSoDispQty;
            $item['total_so_pending_qty'] = $totalSoPendingQty;
            $expandedData[] = $item;
        }
    }
    $data = $expandedData;

    echo json_encode([
        "status" => "success",
        "data" => $data,
        "old_pending_count" => $oldPendingCount,
        "old_pending_qty" => $oldPendingQty
    ]);
    exit;

} else if ($action == "GetOrderGrowthAndTrayDetails") {
    $projectid = intval($_POST['projectid'] ?? 0);
    $soiId = intval($_POST['soi_id'] ?? 0);
    $stockOrderNo = trim($_POST['stockorder_no'] ?? '');

    // 1. Get Sales Order Item Details
    $soiSql = "
        SELECT 
            soi.id AS soi_id,
            soi.sono,
            soi.psono,
            soi.product_id,
            soi.product_name,
            soi.quantity AS order_qty,
            soi.deliverydate AS delivery_date,
            IFNULL(soi.reservedqty, 0) AS reserved_qty,
            IFNULL(soi.balancereservedqty, IFNULL(soi.quantity, 0) - IFNULL(soi.reservedqty, 0)) AS balance_reserved_qty,
            IFNULL(soi.status, 'Pending') AS item_status,
            soi.stockorder_no AS soi_stockorderno,
            
            so.clientid,
            so.clientname,
            so.mobileno,
            so.statename,
            so.districtname,
            so.delivery_address,
            so.created_at AS sales_order_created_at,

            dl.id AS dl_id,
            dl.pickuptype AS dl_pickuptype,
            dl.deliveryaddress AS dl_deliveryaddress,
            dl.dest_state AS dl_dest_state,
            dl.dest_district AS dl_dest_district,
            dl.dest_city AS dl_dest_city,
            dl.dest_pincode AS dl_dest_pincode,
            dl.totaldistance AS dl_totaldistance,
            dl.freedistance AS dl_freedistance,
            dl.distance AS dl_distance,
            dl.freightamt AS dl_freightamt,
            dl.remark AS dl_remark,
            dl.boxperpack AS dl_boxperpack,
            dl.chargesperbox AS dl_chargesperbox,
            dl.cityid AS dl_cityid,
            dl.stateid AS dl_stateid,
            dl.districtid AS dl_districtid,
            dl.noofbox AS dl_noofbox,
            dl.totalamount AS dl_totalamount,
            dl.pickuppoint AS dl_pickuppoint,
            dl.deliverycharges AS dl_deliverycharges,
            dl.delivery_status AS dl_delivery_status
        FROM krf_sales_order_items soi
        JOIN krf_sales_order so ON soi.sono = so.sono
        LEFT JOIN krf_deliveryaddress_location dl ON dl.id = (
            SELECT d2.id 
            FROM krf_deliveryaddress_location d2 
            WHERE (d2.projectid = soi.projectid OR d2.projectid = so.projectid)
              AND (
                  (COALESCE(d2.psono,'') != '' AND COALESCE(d2.psono,'') = COALESCE(soi.psono,''))
                  OR (COALESCE(d2.sono,'') != '' AND COALESCE(d2.sono,'') = COALESCE(soi.sono,''))
              )
              AND (COALESCE(d2.productid,'') = '' OR COALESCE(d2.productid,'') = COALESCE(soi.product_id,''))
              AND (COALESCE(d2.clientid,'') = '' OR COALESCE(d2.clientid,'') = COALESCE(so.clientid,''))
            ORDER BY d2.id DESC 
            LIMIT 1
        )
        WHERE soi.id = ?
        LIMIT 1
    ";
    $stmt = $krfconn->prepare($soiSql);
    $stmt->bind_param("i", $soiId);
    $stmt->execute();
    $orderItem = $stmt->get_result()->fetch_assoc();

    if (!$orderItem) {
        echo json_encode(["status" => "error", "message" => "Sales order item not found."]);
        exit;
    }

    $rawProdName = $orderItem['product_name'] ?? '';
    $orderItem['clean_product_name'] = preg_replace('/^(NonGrafted|Non-Grafted)\s*[-:]?\s*/i', '', $rawProdName);
    $orderItem['is_grafted'] = (stripos($rawProdName, 'grafted') !== false && stripos($rawProdName, 'nongrafted') === false && stripos($rawProdName, 'non-grafted') === false) ? 1 : 0;
    $orderItem['formatted_delivery_date'] = !empty($orderItem['delivery_date']) ? date('d-M-Y', strtotime($orderItem['delivery_date'])) : '-';

    // 2. Resolve Stock Order No
    if (empty($stockOrderNo) || $stockOrderNo === 'Not Allocated') {
        $stockOrderNo = $orderItem['soi_stockorderno'] ?? '';
    }

    // 3. Get Stock Order Details & Allocation Info
    $stockOrderInfo = null;
    $allocationInfo = null;
    if (!empty($stockOrderNo) && $stockOrderNo !== 'Not Allocated') {
        $psoSql = "
            SELECT 
                pso.*,
                IFNULL(cm.is_trader, 0) AS is_trader,
                cm.client_name AS stock_client_name,
                IFNULL((
                    SELECT SUM(consumed_qty) 
                    FROM krf_productionorder_consumed 
                    WHERE stockorderno = pso.stockorder_no 
                      AND LOWER(IFNULL(confirmstatus, '')) NOT IN('cancelled','removed','remove','delete')
                      AND IFNULL(tedit, '') = ''
                ), 0) AS total_allocated_qty
            FROM krf_production_stockorder pso
            LEFT JOIN krf_client_master cm ON pso.clientid = cm.client_id
            WHERE pso.stockorder_no = ? AND (pso.projectid = ? OR ? = 0)
            LIMIT 1
        ";
        $pStmt = $krfconn->prepare($psoSql);
        $pStmt->bind_param("sii", $stockOrderNo, $projectid, $projectid);
        $pStmt->execute();
        $stockOrderInfo = $pStmt->get_result()->fetch_assoc();

        if ($stockOrderInfo) {
            $isTraderStock = (
                intval($stockOrderInfo['is_trader'] ?? 0) === 1 || 
                ($stockOrderInfo['clientid'] ?? '') === 'C00001' || 
                strpos($stockOrderNo, 'RSO-') === 0 || 
                strpos($stockOrderNo, 'RTS-') === 0 ||
                stripos($stockOrderInfo['remarks'] ?? '', 'Ready Stock') !== false
            );
            $stockOrderInfo['is_trader'] = $isTraderStock ? 1 : 0;
            $stockOrderInfo['order_type_label'] = $isTraderStock ? 'Trader Production Order' : 'Self Production Order';
            $stockOrderInfo['stock_type_label'] = $isTraderStock ? 'Open Stock' : 'Own Sown';
            $stockOrderInfo['formatted_sowing_date'] = !empty($stockOrderInfo['sowingdate']) ? date('d-M-Y', strtotime($stockOrderInfo['sowingdate'])) : '-';
            $stockOrderInfo['formatted_scion_sowing_date'] = !empty($stockOrderInfo['scionsowingdate']) ? date('d-M-Y', strtotime($stockOrderInfo['scionsowingdate'])) : '-';
            $stockOrderInfo['formatted_expected_delivery'] = !empty($stockOrderInfo['expected_delivery_date']) ? date('d-M-Y', strtotime($stockOrderInfo['expected_delivery_date'])) : '-';
            $rawStockProd = $stockOrderInfo['product_name'] ?? '';
            $stockOrderInfo['clean_product_name'] = preg_replace('/^(NonGrafted|Non-Grafted)\s*[-:]?\s*/i', '', $rawStockProd);
            
            $isGraftedStock = (stripos($rawStockProd, 'grafted') !== false && stripos($rawStockProd, 'nongrafted') === false && stripos($rawStockProd, 'non-grafted') === false);
            $stockOrderInfo['is_grafted'] = $isGraftedStock ? 1 : 0;

            // Sowing Status Resolution
            $rawSowStatus = strtolower(trim($stockOrderInfo['sowingstatus'] ?? ''));
            $rawScionStatus = strtolower(trim($stockOrderInfo['scionsowingstatus'] ?? ''));

            $mapSowStatus = function($st) {
                if ($st === 'sowed' || $st === 'done') {
                    return ['code' => 'sowed', 'label' => 'Sowed', 'badge_class' => 'bg-success-subtle text-success border border-success-subtle'];
                } else if ($st === 'start') {
                    return ['code' => 'start', 'label' => 'In Progress', 'badge_class' => 'bg-info-subtle text-info border border-info-subtle'];
                } else if ($st === 'remove' || $st === 'removed') {
                    return ['code' => 'cancelled', 'label' => 'Cancelled', 'badge_class' => 'bg-danger-subtle text-danger border border-danger-subtle'];
                } else {
                    return ['code' => 'pending', 'label' => 'Pending', 'badge_class' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle'];
                }
            };

            $sowStatusObj = $mapSowStatus($rawSowStatus);
            $scionStatusObj = $mapSowStatus($rawScionStatus);

            $stockOrderInfo['sowing_status_code'] = $sowStatusObj['code'];
            $stockOrderInfo['sowing_status_label'] = $sowStatusObj['label'];
            $stockOrderInfo['sowing_badge_class'] = $sowStatusObj['badge_class'];

            $stockOrderInfo['scion_sowing_status_code'] = $scionStatusObj['code'];
            $stockOrderInfo['scion_sowing_status_label'] = $scionStatusObj['label'];
            $stockOrderInfo['scion_sowing_badge_class'] = $scionStatusObj['badge_class'];

            // Query actual sown trays/cavity from krf_polyhouse_sowingplant_details
            $sownStmt = $krfconn->prepare("SELECT COUNT(*) AS actual_sown_trays, IFNULL(SUM(traycavity), 0) AS actual_sown_plants FROM krf_polyhouse_sowingplant_details WHERE stockorder_no = ? AND status <> 'UnConfirmSowing'");
            $sownStmt->bind_param("s", $stockOrderNo);
            $sownStmt->execute();
            $sownData = $sownStmt->get_result()->fetch_assoc();
            $stockOrderInfo['actual_sown_trays'] = intval($sownData['actual_sown_trays'] ?? 0);
            $stockOrderInfo['actual_sown_plants'] = intval($sownData['actual_sown_plants'] ?? 0);

            // Query planned sowing quantity from krf_polyhouse_sowingschedule
            $planStmt = $krfconn->prepare("SELECT IFNULL(SUM(sowingqty), 0) AS planned_sowing_qty FROM krf_polyhouse_sowingschedule WHERE stockorder_no = ? AND LOWER(TRIM(status)) NOT IN ('delete','cancel','cancelled','removed')");
            $planStmt->bind_param("s", $stockOrderNo);
            $planStmt->execute();
            $planData = $planStmt->get_result()->fetch_assoc();
            $stockOrderInfo['planned_sowing_qty'] = intval($planData['planned_sowing_qty'] ?? 0);
        }

        // Consumption / Allocation Record for current order item
        $pocSql = "
            SELECT poc.*
            FROM krf_productionorder_consumed poc
            WHERE ((poc.psono = ? AND ? != '') OR (poc.sono = ? AND ? != ''))
              AND poc.product_id = ?
              AND LOWER(IFNULL(poc.confirmstatus, '')) != 'cancelled'
              AND IFNULL(poc.tedit, '') = ''
            ORDER BY poc.id DESC
            LIMIT 1
        ";
        $pocStmt = $krfconn->prepare($pocSql);
        $pocStmt->bind_param("sssss", $orderItem['psono'], $orderItem['psono'], $orderItem['sono'], $orderItem['sono'], $orderItem['product_id']);
        $pocStmt->execute();
        $allocationInfo = $pocStmt->get_result()->fetch_assoc();
        if ($allocationInfo) {
            $allocationInfo['formatted_allocated_at'] = !empty($allocationInfo['allocatedat']) ? date('d-M-Y H:i', strtotime($allocationInfo['allocatedat'])) : (!empty($allocationInfo['created_at']) ? date('d-M-Y H:i', strtotime($allocationInfo['created_at'])) : '-');
            $allocationInfo['formatted_dispatched_at'] = !empty($allocationInfo['dispatchedat']) ? date('d-M-Y H:i', strtotime($allocationInfo['dispatchedat'])) : '-';
        }

        // Query allocatedbed from krf_readystock_allocation
        $rsaAllocStmt = $krfconn->prepare("
            SELECT allocatedbed, isshifted, percentextrashifting, shiftingqty 
            FROM krf_readystock_allocation 
            WHERE ((clientid = ? AND productid = ?)
               OR (stockorder_no = ? OR allocate_stockorder_no = ?))
              AND LOWER(IFNULL(status, '')) != 'cancel'
              AND (tedit IS NULL OR tedit = '')
            ORDER BY id DESC LIMIT 1
        ");
        if ($rsaAllocStmt) {
            $rsaAllocStmt->bind_param("ssss", $orderItem['clientid'], $orderItem['product_id'], $stockOrderNo, $stockOrderNo);
            $rsaAllocStmt->execute();
            $rsaAllocData = $rsaAllocStmt->get_result()->fetch_assoc();
            if ($rsaAllocData) {
                if ($allocationInfo) {
                    $allocationInfo['allocatedbed'] = $rsaAllocData['allocatedbed'];
                }
                $orderItem['allocatedbed'] = $rsaAllocData['allocatedbed'];
            }
            $rsaAllocStmt->close();
        }

        // Fetch all active allocated batches for THIS specific order item (same product and psono/sono)
        $itemAllocStmt = $krfconn->prepare("
            SELECT poc.*
            FROM krf_productionorder_consumed poc
            WHERE ((poc.psono = ? AND ? != '') OR (poc.sono = ? AND ? != ''))
              AND poc.product_id = ?
              AND LOWER(IFNULL(poc.confirmstatus, '')) != 'cancelled'
              AND IFNULL(poc.tedit, '') = ''
            ORDER BY poc.id ASC
        ");
        $itemAllocBatches = [];
        if ($itemAllocStmt) {
            $itemAllocStmt->bind_param("sssss", $orderItem['psono'], $orderItem['psono'], $orderItem['sono'], $orderItem['sono'], $orderItem['product_id']);
            $itemAllocStmt->execute();
            $itemAllocRes = $itemAllocStmt->get_result();
            while ($iaRow = $itemAllocRes->fetch_assoc()) {
                $bAlloc = trim($iaRow['allocated_stockorderno'] ?: ($iaRow['stockorderno'] ?: ''));
                if (!empty($bAlloc) && $bAlloc !== 'Not Allocated' && $bAlloc !== '0') {
                    $itemAllocBatches[] = [
                        'stockorder_no' => $bAlloc,
                        'allocated_qty' => floatval($iaRow['consumed_qty'] ?? 0),
                        'consumed_qty'  => floatval($iaRow['consumed_qty'] ?? 0),
                        'extra_qty'     => floatval($iaRow['extraqtyforshifting'] ?? 0),
                        'extraqtyforshifting' => floatval($iaRow['extraqtyforshifting'] ?? 0),
                        'psono'         => $iaRow['allocated_psono'] ?? '',
                        'remark'        => $iaRow['remark'] ?? ''
                    ];
                }
            }
            $itemAllocStmt->close();
        }
        $orderItem['allocated_batches'] = $itemAllocBatches;

        $isFullyDispatched = (
            strtolower(trim($orderItem['item_status'] ?? '')) === 'dispatched' || 
            strtolower(trim($orderItem['item_status'] ?? '')) === 'final dispatch' ||
            strtolower(trim($allocationInfo['status'] ?? '')) === 'dispatched' ||
            !empty($allocationInfo['dispatchedat'])
        );
        $orderItem['is_fully_dispatched'] = $isFullyDispatched;
        $orderItem['dispatched_date'] = !empty($allocationInfo['dispatchedat']) ? date('d-M-Y', strtotime($allocationInfo['dispatchedat'])) : (!empty($orderItem['delivery_date']) ? date('d-M-Y', strtotime($orderItem['delivery_date'])) : '-');
        $orderItem['dispatched_by'] = !empty($allocationInfo['dispatchedby']) ? $allocationInfo['dispatchedby'] : '';

        // If not dispatched, check if order has confirmed allocation in krf_productionorder_consumed
        if (!$isFullyDispatched && $allocationInfo && (strtolower(trim($allocationInfo['confirmstatus'] ?? '')) === 'confirmed' || floatval($allocationInfo['consumed_qty'] ?? 0) > 0)) {
            $orderItem['item_status'] = 'Allocated';
        }

        // Fetch all active confirmed allocations for this stock order
        $allAllocSql = "
            SELECT 
                poc.id,
                poc.psono,
                COALESCE(NULLIF(TRIM(poc.sono), ''), NULLIF(TRIM(so.sono), ''), '-') AS sono,
                poc.consumed_qty,
                poc.remark,
                poc.confirmstatus,
                poc.status AS poc_status,
                poc.dispatchedat,
                poc.dispatchedby,
                poc.dispatchedqty,
                poc.created_at,
                soi.id AS soi_id,
                soi.status AS soi_status,
                so.status AS so_status,
                COALESCE(NULLIF(TRIM(so.dinvoiceno), ''), NULLIF(TRIM(soi.invoiceno), ''), '') AS dinvoiceno,
                COALESCE(
                    NULLIF(TRIM(so.clientname), ''),
                    NULLIF(TRIM(pso.clientname), ''),
                    NULLIF(TRIM(cm1.client_name), ''),
                    NULLIF(TRIM(cm2.client_name), ''),
                    'Client N/A'
                ) AS clientname,
                COALESCE(soi.quantity, psoi.quantity, poc.consumed_qty, 0) AS order_qty,
                COALESCE(soi.deliverydate, psoi.expecteddeliverydate, pso.expecteddelivery, NULL) AS deliverydate
            FROM krf_productionorder_consumed poc
            LEFT JOIN krf_sales_order so ON (
                (NULLIF(TRIM(poc.sono), '') IS NOT NULL AND poc.sono = so.sono) 
                OR (NULLIF(TRIM(poc.psono), '') IS NOT NULL AND poc.psono = so.psono)
            )
            LEFT JOIN krf_provisional_sales_order pso ON (NULLIF(TRIM(poc.psono), '') IS NOT NULL AND poc.psono = pso.psono)
            LEFT JOIN krf_client_master cm1 ON pso.clientid = cm1.client_id
            LEFT JOIN krf_client_master cm2 ON so.clientid = cm2.client_id
            LEFT JOIN krf_sales_order_items soi ON soi.id = (
                SELECT soi2.id 
                FROM krf_sales_order_items soi2
                WHERE (
                    (NULLIF(TRIM(poc.sono), '') IS NOT NULL AND poc.sono = soi2.sono)
                    OR (NULLIF(TRIM(poc.psono), '') IS NOT NULL AND poc.psono = soi2.psono)
                )
                AND poc.product_id = soi2.product_id
                ORDER BY 
                  CASE WHEN NULLIF(TRIM(soi2.stockorder_no), '') IS NOT NULL 
                        AND soi2.stockorder_no IN (poc.stockorderno, poc.original_stockorder_no, poc.allocated_stockorderno) THEN 1
                       WHEN ROUND(soi2.quantity, 0) = ROUND(poc.consumed_qty, 0) THEN 2
                       ELSE 3 END,
                  soi2.id ASC
                LIMIT 1
            )
            LEFT JOIN krf_provisional_sales_order_items psoi ON psoi.id = (
                SELECT psoi2.id 
                FROM krf_provisional_sales_order_items psoi2
                WHERE NULLIF(TRIM(poc.psono), '') IS NOT NULL 
                  AND poc.psono = psoi2.psono 
                  AND poc.product_id = psoi2.product_id
                ORDER BY 
                  CASE WHEN NULLIF(TRIM(psoi2.stockorder_no), '') IS NOT NULL 
                        AND psoi2.stockorder_no IN (poc.stockorderno, poc.original_stockorder_no, poc.allocated_stockorderno) THEN 1
                       WHEN ROUND(psoi2.quantity, 0) = ROUND(poc.consumed_qty, 0) THEN 2
                       ELSE 3 END,
                  psoi2.id ASC
                LIMIT 1
            )
            WHERE (poc.stockorderno = ? OR poc.allocated_stockorderno = ?) 
              AND LOWER(IFNULL(poc.confirmstatus, '')) != 'cancelled'
              AND IFNULL(poc.tedit, '') = ''
            ORDER BY poc.id ASC
        ";
        $allAllocStmt = $krfconn->prepare($allAllocSql);
        $allAllocStmt->bind_param("ss", $stockOrderNo, $stockOrderNo);
        $allAllocStmt->execute();
        $allAllocRes = $allAllocStmt->get_result();

        $allocationsList = [];
        $totalStockAllocatedPlants = 0;
        while ($alRow = $allAllocRes->fetch_assoc()) {
            $cQty = floatval($alRow['consumed_qty'] ?? 0);
            $totalStockAllocatedPlants += $cQty;
            $alRow['consumed_qty'] = $cQty;
            $alRow['formatted_date'] = !empty($alRow['created_at']) ? date('d-M-Y', strtotime($alRow['created_at'])) : '-';
            $alRow['formatted_delivery'] = !empty($alRow['deliverydate']) ? date('d-M-Y', strtotime($alRow['deliverydate'])) : '-';

            // Resolve Dispatch Status for this allocated sales order
            $pocSt = strtolower(trim($alRow['poc_status'] ?? ''));
            $soiSt = strtolower(trim($alRow['soi_status'] ?? ''));
            $soSt = strtolower(trim($alRow['so_status'] ?? ''));
            $isDispatched = (
                $pocSt === 'dispatched' || $pocSt === 'fulldispatch' ||
                $soiSt === 'dispatched' || $soiSt === 'final dispatch' || $soiSt === 'completed' || $soiSt === 'delivered' ||
                $soSt === 'dispatched' || $soSt === 'fulldispatch' || $soSt === 'completed' ||
                !empty($alRow['dispatchedat'])
            );
            $isPartialDispatched = (
                !$isDispatched && (
                    $pocSt === 'partial_dispatched' || $soiSt === 'partial_dispatched' || $soSt === 'partialdispatch' ||
                    floatval($alRow['dispatchedqty'] ?? 0) > 0
                )
            );
            $hasInvoice = !empty($alRow['dinvoiceno']) || $pocSt === 'readytoload' || $soiSt === 'readytoload';

            if ($isDispatched) {
                $alRow['dispatch_status_code'] = 'dispatched';
                $alRow['dispatch_status_label'] = 'Dispatched';
                $alRow['formatted_dispatched_at'] = !empty($alRow['dispatchedat']) ? date('d-M-Y', strtotime($alRow['dispatchedat'])) : (!empty($alRow['deliverydate']) ? date('d-M-Y', strtotime($alRow['deliverydate'])) : '');
            } else if ($isPartialDispatched) {
                $alRow['dispatch_status_code'] = 'partial_dispatched';
                $alRow['dispatch_status_label'] = 'Partially Dispatched';
                $alRow['formatted_dispatched_at'] = !empty($alRow['dispatchedat']) ? date('d-M-Y', strtotime($alRow['dispatchedat'])) : '';
            } else if ($hasInvoice) {
                $alRow['dispatch_status_code'] = 'readytoload';
                $alRow['dispatch_status_label'] = 'Ready to Load';
                $alRow['formatted_dispatched_at'] = '';
            } else {
                $alRow['dispatch_status_code'] = 'pending';
                $alRow['dispatch_status_label'] = 'Pending Dispatch';
                $alRow['formatted_dispatched_at'] = '';
            }

            $allocationsList[] = $alRow;
        }
    }

    // 4. Fetch Tray Breakdown from krf_polyhouse_plant_details
    $trays = [];
    $polyhouseSummary = [];
    $bedSummary = [];
    $totalTrays = 0;
    $totalPlants = 0;
    $readyPlants = 0;
    $dispatchedPlants = 0;
    $growingPlants = 0;
    $missingReadyPlants = 0;
    $missingReadyTrays = 0;
    $totalFailureCount = 0;

    if (!empty($stockOrderNo) && $stockOrderNo !== 'Not Allocated') {
        $traySql = "
            SELECT 
                ppd.polyhouseplantid,
                ppd.stockorder_no,
                ppd.sowingplantid,
                ppd.productid,
                ppd.productname,
                ppd.roottype,
                ppd.qtypertray,
                ppd.failurecount,
                ppd.failerpercent,
                ppd.health_status,
                ppd.polyhouseid,
                ph.polyhousename,
                ppd.bedrackname,
                ppd.sowingdate_internal,
                ppd.dispatchdate_internal,
                ppd.status,
                ppd.workingstatus,
                ppd.created_at,
                DATEDIFF(NOW(), ppd.sowingdate_internal) AS growth_days,
                IFNULL(phloc.LocationName, 'N/A') AS location_name,
                tm.tray_name,
                COALESCE(tm.cavity_count, CAST(NULLIF(ppd.qtypertray, '') AS SIGNED), 0) AS cavity_count
            FROM krf_polyhouse_plant_details ppd
            LEFT JOIN krf_polyhouse_master ph ON ppd.polyhouseid = ph.polyhouseid
            LEFT JOIN krf_tray_master tm ON ppd.tray_id = tm.tray_id
            LEFT JOIN {$config['db_hrm']}.hrm_AttendanceLocationMaster phloc 
                ON COALESCE(NULLIF(NULLIF(ppd.locationid, '0'), ''), ph.locationid) = phloc.LocationId
            WHERE ppd.projectid = ? AND ppd.stockorder_no = ?
            ORDER BY ph.polyhousename ASC, ppd.bedrackname ASC, ppd.polyhouseplantid ASC
        ";
        $tStmt = $krfconn->prepare($traySql);
        $tStmt->bind_param("is", $projectid, $stockOrderNo);
        $tStmt->execute();
        $tRes = $tStmt->get_result();

        while ($tRow = $tRes->fetch_assoc()) {
            $grossQty = (int)($tRow['qtypertray'] ?? 0);
            $failQty = (int)($tRow['failurecount'] ?? 0);
            $netQty = max(0, $grossQty - $failQty);
            $health = trim($tRow['health_status'] ?? '');
            $isHealthy = (strtolower($health) === 'healthy');
            $st = strtolower(trim($tRow['status'] ?? ''));
            $wSt = strtolower(trim($tRow['workingstatus'] ?? ''));
            $phName = !empty($tRow['polyhousename']) ? $tRow['polyhousename'] : 'Unassigned PH';
            $bedName = !empty($tRow['bedrackname']) ? $tRow['bedrackname'] : 'Bed N/A';

            $totalTrays++;
            $totalFailureCount += $failQty;

            // Healthy Plants count based on health_status = 'Healthy'
            if ($isHealthy && $st !== 'removed') {
                $totalPlants += $netQty;
            }

            if ($st == 'final dispatch' || $wSt == 'final dispatch') {
                $dispatchedPlants += $netQty;
                $stageBadge = '<span class="badge bg-success">Dispatched</span>';
            } else if (in_array($st, ['readytosale', 'openstock']) || in_array($wSt, ['readytosale', 'openstock'])) {
                if ($isHealthy) {
                    $readyPlants += $netQty;
                }
                $stageBadge = '<span class="badge bg-info text-dark">Ready to Sale</span>';
            } else if ($st == 'hardening' || $wSt == 'hardening') {
                if ($isHealthy) {
                    $growingPlants += $netQty;
                    $missingReadyPlants += $netQty;
                    $missingReadyTrays++;
                }
                $stageBadge = '<span class="badge bg-warning text-dark">Hardening Stage</span>';
            } else {
                if ($isHealthy) {
                    $growingPlants += $netQty;
                    $missingReadyPlants += $netQty;
                    $missingReadyTrays++;
                }
                $stageBadge = '<span class="badge bg-primary">Polyhouse Growing</span>';
            }

            // Health Status Badge
            if ($isHealthy) {
                $healthBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fa fa-check-circle me-1"></i>Healthy</span>';
            } else if (empty($health)) {
                $healthBadge = '<span class="badge bg-light text-muted border">Unknown</span>';
            } else {
                $healthBadge = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">' . htmlspecialchars($health) . '</span>';
            }

            // Germination Failure %
            $failPercent = 0;
            if (!empty($tRow['failerpercent']) && floatval($tRow['failerpercent']) > 0) {
                $failPercent = round(floatval($tRow['failerpercent']), 2);
            } else if ($grossQty > 0 && $failQty > 0) {
                $failPercent = round(($failQty / $grossQty) * 100, 2);
            }

            $tRow['gross_qty'] = $grossQty;
            $tRow['failure_qty'] = $failQty;
            $tRow['fail_percent'] = $failPercent;
            $tRow['cavity_count'] = intval($tRow['cavity_count'] ?? $grossQty);
            $tRow['net_qty'] = $netQty;
            $tRow['is_healthy'] = $isHealthy ? 1 : 0;
            $tRow['stage_badge'] = $stageBadge;
            $tRow['health_badge'] = $healthBadge;
            $tRow['formatted_sowing_date'] = !empty($tRow['sowingdate_internal']) ? date('d-M-Y', strtotime($tRow['sowingdate_internal'])) : '-';
            $tRow['formatted_dispatch_date'] = !empty($tRow['dispatchdate_internal']) ? date('d-M-Y', strtotime($tRow['dispatchdate_internal'])) : '-';
            
            $trays[] = $tRow;

            // Polyhouse Summary
            if (!isset($polyhouseSummary[$phName])) {
                $polyhouseSummary[$phName] = [
                    'polyhousename' => $phName,
                    'tray_count' => 0,
                    'healthy_tray_count' => 0,
                    'unhealthy_tray_count' => 0,
                    'healthy_plant_count' => 0,
                    'unhealthy_plant_count' => 0,
                    'unhealthy_gross_qty' => 0,
                    'total_qty' => 0,
                    'ready_qty' => 0,
                    'healthy_qty' => 0,
                    'ready_tray_count' => 0,
                    'ready_plants' => 0,
                    'dispatched_tray_count' => 0,
                    'dispatched_plants' => 0,
                    'failure_qty' => 0,
                    'gross_qty' => 0,
                    'location_name' => $tRow['location_name']
                ];
            }
            $polyhouseSummary[$phName]['tray_count']++;
            $polyhouseSummary[$phName]['gross_qty'] += $grossQty;
            $polyhouseSummary[$phName]['failure_qty'] += $failQty;
            if ($isHealthy && $st !== 'removed') {
                $polyhouseSummary[$phName]['healthy_tray_count']++;
                $polyhouseSummary[$phName]['healthy_plant_count'] += $netQty;
                $polyhouseSummary[$phName]['healthy_qty'] += $netQty;
                $polyhouseSummary[$phName]['total_qty'] += $netQty;
            } else {
                $polyhouseSummary[$phName]['unhealthy_tray_count']++;
                $polyhouseSummary[$phName]['unhealthy_plant_count'] += $netQty;
                $polyhouseSummary[$phName]['unhealthy_gross_qty'] += $grossQty;
            }
            if ($isHealthy && in_array($st, ['readytosale', 'openstock'])) {
                $polyhouseSummary[$phName]['ready_tray_count']++;
                $polyhouseSummary[$phName]['ready_plants'] += $netQty;
                $polyhouseSummary[$phName]['ready_qty'] += $netQty;
            }
            if ($st == 'final dispatch' || $wSt == 'final dispatch') {
                $polyhouseSummary[$phName]['dispatched_tray_count']++;
                $polyhouseSummary[$phName]['dispatched_plants'] += $netQty;
            }

            // Bed Wise Summary
            $bedKey = $phName . '___' . $bedName;
            if (!isset($bedSummary[$bedKey])) {
                $bedSummary[$bedKey] = [
                    'polyhouseid' => $tRow['polyhouseid'],
                    'polyhousename' => $phName,
                    'bedrackname' => $bedName,
                    'location_name' => $tRow['location_name'],
                    'productname' => $tRow['productname'],
                    'stockorder_no' => $tRow['stockorder_no'],
                    'roottype' => $tRow['roottype'],
                    'cavity_count' => (int)($tRow['cavity_count'] ?? $grossQty),
                    'sowingdate_internal' => $tRow['sowingdate_internal'],
                    'formatted_sowing_date' => !empty($tRow['sowingdate_internal']) ? date('d-M-Y', strtotime($tRow['sowingdate_internal'])) : '-',
                    'growth_days' => $tRow['growth_days'],
                    'stage_badge' => $stageBadge,
                    'status' => $tRow['status'],
                    'workingstatus' => $tRow['workingstatus'],
                    'tray_count' => 0,
                    'healthy_tray_count' => 0,
                    'unhealthy_tray_count' => 0,
                    'healthy_plant_count' => 0,
                    'unhealthy_plant_count' => 0,
                    'unhealthy_gross_qty' => 0,
                    'total_qty' => 0,
                    'healthy_qty' => 0,
                    'ready_qty' => 0,
                    'ready_tray_count' => 0,
                    'ready_plants' => 0,
                    'dispatched_tray_count' => 0,
                    'dispatched_plants' => 0,
                    'failure_qty' => 0,
                    'gross_qty' => 0
                ];
            }
            $bedSummary[$bedKey]['tray_count']++;
            $bedSummary[$bedKey]['gross_qty'] += $grossQty;
            $bedSummary[$bedKey]['failure_qty'] += $failQty;
            if ($isHealthy && $st !== 'removed') {
                $bedSummary[$bedKey]['healthy_tray_count']++;
                $bedSummary[$bedKey]['healthy_plant_count'] += $netQty;
                $bedSummary[$bedKey]['healthy_qty'] += $netQty;
                $bedSummary[$bedKey]['total_qty'] += $netQty;
            } else {
                $bedSummary[$bedKey]['unhealthy_tray_count']++;
                $bedSummary[$bedKey]['unhealthy_plant_count'] += $netQty;
                $bedSummary[$bedKey]['unhealthy_gross_qty'] += $grossQty;
            }
            if ($isHealthy && in_array($st, ['readytosale', 'openstock'])) {
                $bedSummary[$bedKey]['ready_tray_count']++;
                $bedSummary[$bedKey]['ready_plants'] += $netQty;
                $bedSummary[$bedKey]['ready_qty'] += $netQty;
            }
            if ($st == 'final dispatch' || $wSt == 'final dispatch') {
                $bedSummary[$bedKey]['dispatched_tray_count']++;
                $bedSummary[$bedKey]['dispatched_plants'] += $netQty;
            }
        }

        // Calculate failure percentages for Polyhouses and Beds
        foreach ($polyhouseSummary as &$ph) {
            $ph['fail_percent'] = ($ph['gross_qty'] > 0 && $ph['failure_qty'] > 0) ? round(($ph['failure_qty'] / $ph['gross_qty']) * 100, 2) : 0;
            $ph['fail_tray_fail_percent'] = ($ph['unhealthy_gross_qty'] > 0 && $ph['failure_qty'] > 0) ? round(($ph['failure_qty'] / $ph['unhealthy_gross_qty']) * 100, 2) : 0;
        }
        unset($ph);

        foreach ($bedSummary as &$b) {
            $b['fail_percent'] = ($b['gross_qty'] > 0 && $b['failure_qty'] > 0) ? round(($b['failure_qty'] / $b['gross_qty']) * 100, 2) : 0;
            $b['fail_tray_fail_percent'] = ($b['unhealthy_gross_qty'] > 0 && $b['failure_qty'] > 0) ? round(($b['failure_qty'] / $b['unhealthy_gross_qty']) * 100, 2) : 0;
        }
        unset($b);

        // Distribute stock order allocations and calculate Open Stock (Healthy Plants - Allocated - Dispatched)
        $remAlloc = $totalStockAllocatedPlants;
        $totalAllocatedTrays = 0;
        $totalReadyToAllocatePlants = 0;
        $totalReadyToAllocateTrays = 0;

        foreach ($bedSummary as &$b) {
            $cavity = max(1, intval($b['cavity_count'] ?? 1));
            $bGrossHealthy = max(0, intval($b['healthy_plant_count'] ?? $b['healthy_qty'] ?? 0));
            $bDispatched = intval($b['dispatched_plants'] ?? 0);
            $bNetBeforeAlloc = max(0, $bGrossHealthy - $bDispatched);

            $bAllocPlants = min($bNetBeforeAlloc, $remAlloc);
            $remAlloc = max(0, $remAlloc - $bAllocPlants);
            
            $bAllocTrays = ($cavity > 0 && $bAllocPlants > 0) ? intval(round($bAllocPlants / $cavity)) : 0;
            $bReadyToAllocPlants = max(0, $bNetBeforeAlloc - $bAllocPlants);
            $bReadyToAllocTrays = ($cavity > 0 && $bReadyToAllocPlants > 0) ? intval(floor($bReadyToAllocPlants / $cavity)) : 0;

            $b['allocated_plants'] = $bAllocPlants;
            $b['allocated_tray_count'] = $bAllocTrays;
            $b['ready_to_allocate_plants'] = $bReadyToAllocPlants;
            $b['open_stock_plants'] = $bReadyToAllocPlants;
            $b['ready_to_allocate_tray_count'] = $bReadyToAllocTrays;

            $totalAllocatedTrays += $bAllocTrays;
            $totalReadyToAllocatePlants += $bReadyToAllocPlants;
            $totalReadyToAllocateTrays += $bReadyToAllocTrays;
        }
        unset($b);

        // Update Polyhouse Summary with allocated and open stock metrics
        $remAllocPh = $totalStockAllocatedPlants;
        foreach ($polyhouseSummary as &$ph) {
            $phGrossHealthy = max(0, intval($ph['healthy_plant_count'] ?? $ph['healthy_qty'] ?? 0));
            $phDispatched = intval($ph['dispatched_plants'] ?? 0);
            $phNetBeforeAlloc = max(0, $phGrossHealthy - $phDispatched);

            $phAllocPlants = min($phNetBeforeAlloc, $remAllocPh);
            $remAllocPh = max(0, $remAllocPh - $phAllocPlants);
            $phReadyToAllocPlants = max(0, $phNetBeforeAlloc - $phAllocPlants);

            $ph['allocated_plants'] = $phAllocPlants;
            $ph['ready_to_allocate_plants'] = $phReadyToAllocPlants;
            $ph['open_stock_plants'] = $phReadyToAllocPlants;
        }
        unset($ph);

        // Attach bed and polyhouse metrics to each individual tray row
        foreach ($trays as &$t) {
            $pKey = !empty($t['polyhousename']) ? $t['polyhousename'] : 'Unassigned PH';
            $bKey = $pKey . '___' . (!empty($t['bedrackname']) ? $t['bedrackname'] : 'Bed N/A');

            $bInfo = $bedSummary[$bKey] ?? [];
            $pInfo = $polyhouseSummary[$pKey] ?? [];

            $t['bed_total_trays'] = (int)($bInfo['tray_count'] ?? 0);
            $t['bed_total_plants'] = (int)($bInfo['healthy_qty'] ?? $bInfo['total_qty'] ?? 0);
            $t['bed_healthy_trays'] = (int)($bInfo['healthy_tray_count'] ?? 0);
            $t['bed_unhealthy_trays'] = (int)($bInfo['unhealthy_tray_count'] ?? 0);
            $t['bed_fail_percent'] = floatval($bInfo['fail_percent'] ?? 0);
            $t['bed_failure_qty'] = (int)($bInfo['failure_qty'] ?? 0);
            $t['bed_allocated_plants'] = (int)($bInfo['allocated_plants'] ?? 0);
            $t['bed_allocated_trays'] = (int)($bInfo['allocated_tray_count'] ?? 0);
            $t['bed_ready_to_allocate_plants'] = (int)($bInfo['ready_to_allocate_plants'] ?? 0);
            $t['bed_ready_to_allocate_trays'] = (int)($bInfo['ready_to_allocate_tray_count'] ?? 0);

            $t['ph_total_trays'] = (int)($pInfo['tray_count'] ?? 0);
            $t['ph_total_plants'] = (int)($pInfo['healthy_qty'] ?? $pInfo['total_qty'] ?? 0);
            $t['ph_healthy_trays'] = (int)($pInfo['healthy_tray_count'] ?? 0);
            $t['ph_unhealthy_trays'] = (int)($pInfo['unhealthy_tray_count'] ?? 0);
            $t['ph_fail_percent'] = floatval($pInfo['fail_percent'] ?? 0);
        }
        unset($t);
    }

    echo json_encode([
        "status" => "success",
        "order_item" => $orderItem,
        "stock_order" => $stockOrderInfo,
        "allocation" => $allocationInfo,
        "allocations" => $allocationsList ?? [],
        "summary" => [
            "total_trays" => $totalTrays,
            "total_plants" => $totalPlants,
            "ready_plants" => $readyPlants,
            "growing_plants" => $growingPlants,
            "missing_ready_to_sale_plants" => $missingReadyPlants,
            "missing_ready_to_sale_trays" => $missingReadyTrays,
            "dispatched_plants" => $dispatchedPlants,
            "allocated_plants" => $totalStockAllocatedPlants ?? 0,
            "allocated_trays" => $totalAllocatedTrays ?? 0,
            "ready_to_allocate_plants" => $totalReadyToAllocatePlants ?? $readyPlants,
            "ready_to_allocate_trays" => $totalReadyToAllocateTrays ?? 0,
            "total_failure_plants" => $totalFailureCount,
            "polyhouse_count" => count($polyhouseSummary),
            "bed_count" => count($bedSummary)
        ],
        "polyhouses" => array_values($polyhouseSummary),
        "beds" => array_values($bedSummary),
        "trays" => $trays
    ]);
    exit;

} else if ($action == "GetAvailableStockOrdersForProduct") {
    $projectid = intval($_POST['projectid'] ?? 0);
    $productId = trim($_POST['product_id'] ?? '');
    $allowAll = (isset($_POST['allow_all_products']) && $_POST['allow_all_products'] == '1');
    $deliveryDate = trim($_POST['delivery_date'] ?? '');
    $currentStockOrder = trim($_POST['current_stockorder'] ?? '');
    $targetClientId = trim($_POST['client_id'] ?? '');
    $targetPsono = trim($_POST['psono'] ?? '');
    $targetSono = trim($_POST['sono'] ?? '');

    if (empty($targetSono) && !empty($targetPsono)) {
        $soStmt = $krfconn->prepare("SELECT sono FROM krf_sales_order WHERE psono = ? LIMIT 1");
        if ($soStmt) {
            $soStmt->bind_param("s", $targetPsono);
            $soStmt->execute();
            $soRow = $soStmt->get_result()->fetch_assoc();
            if ($soRow && !empty($soRow['sono'])) {
                $targetSono = trim($soRow['sono']);
            }
            $soStmt->close();
        }
    }

    $escapedProdId = $krfconn->real_escape_string($productId);
    $types = "ii";
    $params = [$projectid, $projectid];

    $whereClause = "";
    $ownPsoClause = !empty($targetPsono) ? "OR pso.psono = ?" : "";
    $currStkClause = (!empty($currentStockOrder) && $currentStockOrder !== 'Not Allocated') ? "OR pso.stockorder_no = ?" : "";

    if ($allowAll) {
        // "All Varieties" mode:
        // Include target product batches, currently allocated batch, order's own psono, Ready Stock Orders (RSO),
        // and batches of other varieties within +/- 15 days window
        if (!empty($deliveryDate) && $deliveryDate !== '0000-00-00') {
            $dateFrom = date('Y-m-d', strtotime($deliveryDate . ' -15 days'));
            $dateTo = date('Y-m-d', strtotime($deliveryDate . ' +15 days'));
            $rsoDateFrom = date('Y-m-d', strtotime($deliveryDate . ' -35 days'));
            $whereClause = "AND (
                pso.productid = ?
                {$ownPsoClause}
                {$currStkClause}
                OR (pso.expected_delivery_date BETWEEN ? AND ?)
                OR (pso.stockorder_no LIKE 'RSO%' AND pso.expected_delivery_date >= ?)
            )";
            $types .= "s";
            $params[] = $productId;
            if (!empty($targetPsono)) {
                $types .= "s";
                $params[] = $targetPsono;
            }
            if (!empty($currentStockOrder) && $currentStockOrder !== 'Not Allocated') {
                $types .= "s";
                $params[] = $currentStockOrder;
            }
            $types .= "sss";
            $params[] = $dateFrom;
            $params[] = $dateTo;
            $params[] = $rsoDateFrom;
        }
    } else {
        // Default "Same Variety" mode:
        // Always include ALL active production batches and Ready Stock of this specific product!
        // Also include currentStockOrder and order's own psono if set
        if (!empty($productId) || !empty($targetPsono)) {
            $whereClause = "AND (" . (!empty($productId) ? "pso.productid = ?" : "1=0") . " {$ownPsoClause} {$currStkClause})";
            if (!empty($productId)) {
                $types .= "s";
                $params[] = $productId;
            }
            if (!empty($targetPsono)) {
                $types .= "s";
                $params[] = $targetPsono;
            }
            if (!empty($currentStockOrder) && $currentStockOrder !== 'Not Allocated') {
                $types .= "s";
                $params[] = $currentStockOrder;
            }
        }
    }

    $sql = "
        SELECT 
            pso.id,
            pso.stockorder_no,
            pso.psono,
            pso.clientid,
            pso.remarks,
            pso.productid,
            pso.product_name,
            pso.quantity AS total_qty,
            pso.expected_delivery_date,
            pso.sowingdate,
            pso.sowingstatus,
            pso.scionsowingdate,
            pso.scionsowingstatus,
            pso.status,
            pso.workingstatus,
            COALESCE(cm.is_trader, 0) AS is_trader,
            COALESCE(so.clientname, '') AS sown_client_name,
            COALESCE(so.clientid, '') AS sown_client_id,
            COALESCE(so.sono, '') AS sown_sono
        FROM krf_production_stockorder pso
        LEFT JOIN krf_client_master cm ON pso.clientid = cm.client_id
        LEFT JOIN krf_sales_order so ON (so.psono = pso.psono AND pso.psono IS NOT NULL AND pso.psono != '')
        WHERE (pso.projectid = ? OR ? = 0)
          {$whereClause}
          AND LOWER(IFNULL(pso.status, '')) NOT IN ('cancelled', 'removed', 'remove')
        ORDER BY 
          CASE WHEN pso.productid = '{$escapedProdId}' THEN 0 ELSE 1 END,
          CASE WHEN pso.stockorder_no LIKE 'RSO%' THEN 0 ELSE 1 END,
          pso.expected_delivery_date ASC, pso.id DESC
    ";

    $stmt = $krfconn->prepare($sql);
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();

    $stockOrders = [];
    $stockOrderNos = [];
    while ($row = $res->fetch_assoc()) {
        $row['assigned_qty'] = 0;
        $row['total_allocated_qty'] = 0;
        $row['allocated_to_clients'] = '';
        $row['allocated_client_names'] = '';
        $row['allocated_psono_list'] = '';
        $row['allocated_sono_list'] = '';
        $row['allocated_orders_details'] = [];
        $row['sown_order_no'] = $row['sown_sono'] ?: ($row['psono'] ?: '');
        $row['sown_order_qty'] = (int)($row['total_qty'] ?? 0);
        $row['polyhouse_ready_qty'] = 0;
        $row['ph_total_plants'] = 0;
        $row['ph_avail_plants'] = 0;
        $row['ph_consumed_plants'] = 0;
        $row['consumed_removed_qty'] = 0;
        $row['is_in_polyhouse'] = 0;
        $row['is_polyhouse_available'] = 0;
        $row['sowing_schedule_status'] = '';
        $row['sowing_schedule_remarks'] = '';
        $row['sowing_schedule_teditremark'] = '';
        $row['polyhouse_locations'] = '';
        $row['bed_numbers'] = '';
        $row['available_beds'] = [];
        $stockOrders[$row['stockorder_no']] = $row;
        $stockOrderNos[] = "'" . $krfconn->real_escape_string($row['stockorder_no']) . "'";
    }

    if (!empty($stockOrderNos)) {
        $inClause = implode(',', $stockOrderNos);
        
        $escTargetPsono = $krfconn->real_escape_string($targetPsono);
        $escTargetSono = $krfconn->real_escape_string($targetSono);

        // Fetch assigned quantities & client order details (ONLY confirmed orders, excluding current order's own reservation)
        $pocSql = "
            SELECT 
                COALESCE(NULLIF(poc.allocated_stockorderno, ''), poc.stockorderno) AS batch_no,
                poc.id AS poc_id,
                poc.psono,
                poc.sono,
                poc.product_id,
                poc.consumed_qty,
                poc.original_orderqty,
                COALESCE(NULLIF(TRIM(so.clientname), ''), NULLIF(TRIM(prov.clientname), ''), poc.sono, poc.psono, 'Client') AS client_name,
                so.sono AS so_number,
                prov.psono AS prov_psono
            FROM krf_productionorder_consumed poc
            LEFT JOIN krf_sales_order so ON (poc.sono = so.sono OR (poc.psono = so.psono AND poc.psono != ''))
            LEFT JOIN krf_provisional_sales_order prov ON (poc.psono = prov.psono)
            WHERE (poc.allocated_stockorderno IN ($inClause) OR ((poc.allocated_stockorderno IS NULL OR poc.allocated_stockorderno = '') AND poc.stockorderno IN ($inClause)))
              AND LOWER(TRIM(IFNULL(poc.confirmstatus, ''))) = 'confirmed'
              AND (prov.status IS NULL OR LOWER(prov.status) != 'draft')
              AND IFNULL(poc.tedit, '') = ''
            ORDER BY poc.id ASC
        ";
        $pocRes = $krfconn->query($pocSql);
        $pocRows = [];
        $allPsonos = [];
        $allSonos = [];

        if ($pocRes) {
            while ($r = $pocRes->fetch_assoc()) {
                $isTarget = false;
                if (!empty($escTargetPsono) && strcasecmp(trim($r['psono']), $escTargetPsono) === 0) $isTarget = true;
                if (!empty($escTargetSono) && strcasecmp(trim($r['sono']), $escTargetSono) === 0) $isTarget = true;
                if ($isTarget) continue; // Exclude current target order's own reservation

                $pocRows[] = $r;
                if (!empty($r['psono'])) $allPsonos[] = "'" . $krfconn->real_escape_string($r['psono']) . "'";
                if (!empty($r['sono'])) $allSonos[] = "'" . $krfconn->real_escape_string($r['sono']) . "'";
            }
        }

        // Query respective tables for confirmed Sales Order items and Provisional Sales Order items
        $soiMap = [];
        if (!empty($allSonos) || !empty($allPsonos)) {
            $whereParts = [];
            if (!empty($allSonos)) $whereParts[] = "sono IN (" . implode(',', array_unique($allSonos)) . ")";
            if (!empty($allPsonos)) $whereParts[] = "psono IN (" . implode(',', array_unique($allPsonos)) . ")";
            $soiSql = "SELECT sono, psono, product_id, stockorder_no, quantity FROM krf_sales_order_items WHERE (" . implode(' OR ', $whereParts) . ") AND (tedit IS NULL OR tedit = '')";
            $soiRes = $krfconn->query($soiSql);
            if ($soiRes) {
                while ($soi = $soiRes->fetch_assoc()) {
                    $key1 = ($soi['sono'] ?: '') . '|' . ($soi['product_id'] ?: '') . '|' . ($soi['stockorder_no'] ?: '');
                    $key2 = ($soi['psono'] ?: '') . '|' . ($soi['product_id'] ?: '') . '|' . ($soi['stockorder_no'] ?: '');
                    $key3 = ($soi['sono'] ?: '') . '|' . ($soi['product_id'] ?: '');
                    $key4 = ($soi['psono'] ?: '') . '|' . ($soi['product_id'] ?: '');
                    $q = (int)$soi['quantity'];
                    $soiMap[$key1] = $q;
                    $soiMap[$key2] = $q;
                    if (!isset($soiMap[$key3])) $soiMap[$key3] = $q;
                    if (!isset($soiMap[$key4])) $soiMap[$key4] = $q;
                }
            }
        }

        $psoiMap = [];
        if (!empty($allPsonos)) {
            $psoiSql = "SELECT psono, product_id, stockorder_no, quantity FROM krf_provisional_sales_order_items WHERE psono IN (" . implode(',', array_unique($allPsonos)) . ") AND (tedit IS NULL OR tedit = '')";
            $psoiRes = $krfconn->query($psoiSql);
            if ($psoiRes) {
                while ($psoi = $psoiRes->fetch_assoc()) {
                    $key1 = ($psoi['psono'] ?: '') . '|' . ($psoi['product_id'] ?: '') . '|' . ($psoi['stockorder_no'] ?: '');
                    $key2 = ($psoi['psono'] ?: '') . '|' . ($psoi['product_id'] ?: '');
                    $q = (int)$psoi['quantity'];
                    $psoiMap[$key1] = $q;
                    if (!isset($psoiMap[$key2])) $psoiMap[$key2] = $q;
                }
            }
        }

        $psoProdMap = [];
        if (!empty($allPsonos)) {
            $psoProdSql = "SELECT psono, productid, stockorder_no, quantity FROM krf_production_stockorder WHERE psono IN (" . implode(',', array_unique($allPsonos)) . ")";
            $psoProdRes = $krfconn->query($psoProdSql);
            if ($psoProdRes) {
                while ($psoP = $psoProdRes->fetch_assoc()) {
                    $key1 = ($psoP['psono'] ?: '') . '|' . ($psoP['productid'] ?: '') . '|' . ($psoP['stockorder_no'] ?: '');
                    $key2 = ($psoP['psono'] ?: '') . '|' . ($psoP['productid'] ?: '');
                    $q = (int)$psoP['quantity'];
                    $psoProdMap[$key1] = $q;
                    if (!isset($psoProdMap[$key2])) $psoProdMap[$key2] = $q;
                }
            }
        }

        $batchTotals = [];
        $batchDetails = [];
        $batchClientNames = [];
        $batchPsonos = [];
        $batchSonos = [];

        foreach ($pocRows as $r) {
            $bNo = $r['batch_no'];
            $cQty = (int)$r['consumed_qty'];
            $pId = $r['product_id'];
            $sono = trim($r['sono'] ?? '');
            $psono = trim($r['psono'] ?? '');

            // Respective order quantity resolution from respective tables:
            // 1. krf_sales_order_items
            // 2. krf_provisional_sales_order_items
            // 3. krf_production_stockorder
            // 4. poc.original_orderqty
            // 5. poc.consumed_qty
            $ordQty = 0;
            if (!empty($sono) && isset($soiMap["$sono|$pId|$bNo"])) $ordQty = $soiMap["$sono|$pId|$bNo"];
            else if (!empty($psono) && isset($soiMap["$psono|$pId|$bNo"])) $ordQty = $soiMap["$psono|$pId|$bNo"];
            else if (!empty($sono) && isset($soiMap["$sono|$pId"])) $ordQty = $soiMap["$sono|$pId"];
            else if (!empty($psono) && isset($soiMap["$psono|$pId"])) $ordQty = $soiMap["$psono|$pId"];
            else if (!empty($psono) && isset($psoiMap["$psono|$pId|$bNo"])) $ordQty = $psoiMap["$psono|$pId|$bNo"];
            else if (!empty($psono) && isset($psoiMap["$psono|$pId"])) $ordQty = $psoiMap["$psono|$pId"];
            else if (!empty($psono) && isset($psoProdMap["$psono|$pId|$bNo"])) $ordQty = $psoProdMap["$psono|$pId|$bNo"];
            else if (!empty($psono) && isset($psoProdMap["$psono|$pId"])) $ordQty = $psoProdMap["$psono|$pId"];
            else if ((int)$r['original_orderqty'] > 0) $ordQty = (int)$r['original_orderqty'];
            else $ordQty = $cQty;

            $orderNoDisplay = $sono ?: $psono;

            if (!isset($batchTotals[$bNo])) $batchTotals[$bNo] = 0;
            $batchTotals[$bNo] += $cQty;

            $batchDetails[$bNo][] = [
                'client_name' => $r['client_name'],
                'order_no'    => $orderNoDisplay,
                'sono'        => $sono,
                'psono'       => $psono,
                'order_qty'   => $ordQty,
                'alloc_qty'   => $cQty
            ];

            if (!empty($r['client_name'])) $batchClientNames[$bNo][$r['client_name']] = true;
            if (!empty($psono)) $batchPsonos[$bNo][$psono] = true;
            if (!empty($sono)) $batchSonos[$bNo][$sono] = true;
        }

        foreach ($batchTotals as $bNo => $tot) {
            if (isset($stockOrders[$bNo])) {
                $stockOrders[$bNo]['assigned_qty'] = $tot;
                $stockOrders[$bNo]['total_allocated_qty'] = $tot;

                $detailsList = $batchDetails[$bNo] ?? [];
                $stockOrders[$bNo]['allocated_orders_details'] = $detailsList;

                $strParts = [];
                foreach ($detailsList as $d) {
                    $ordLabel = $d['order_no'] ? " [{$d['order_no']}]" : "";
                    $strParts[] = "{$d['client_name']}{$ordLabel} (Ord: " . number_format($d['order_qty']) . " | Alloc: " . number_format($d['alloc_qty']) . ")";
                }
                $stockOrders[$bNo]['allocated_to_clients'] = implode(', ', $strParts);
                $stockOrders[$bNo]['allocated_client_names'] = isset($batchClientNames[$bNo]) ? implode(', ', array_keys($batchClientNames[$bNo])) : '';
                $stockOrders[$bNo]['allocated_psono_list'] = isset($batchPsonos[$bNo]) ? implode(', ', array_keys($batchPsonos[$bNo])) : '';
                $stockOrders[$bNo]['allocated_sono_list'] = isset($batchSonos[$bNo]) ? implode(', ', array_keys($batchSonos[$bNo])) : '';
            }
        }

        // Also check if batch itself was sown for an order and resolve its client order quantity
        foreach ($stockOrders as $stkNo => &$sOrder) {
            $batchPso = trim($sOrder['psono'] ?? '');
            $batchProdId = $sOrder['productid'] ?? '';
            if (!empty($batchPso) && !empty($batchProdId)) {
                if (isset($soiMap["$batchPso|$batchProdId|$stkNo"])) {
                    $sOrder['sown_order_qty'] = $soiMap["$batchPso|$batchProdId|$stkNo"];
                } elseif (isset($soiMap["$batchPso|$batchProdId"])) {
                    $sOrder['sown_order_qty'] = $soiMap["$batchPso|$batchProdId"];
                } elseif (isset($psoiMap["$batchPso|$batchProdId|$stkNo"])) {
                    $sOrder['sown_order_qty'] = $psoiMap["$batchPso|$batchProdId|$stkNo"];
                } elseif (isset($psoiMap["$batchPso|$batchProdId"])) {
                    $sOrder['sown_order_qty'] = $psoiMap["$batchPso|$batchProdId"];
                }
            }
        }
        unset($sOrder);

        // 1. Fetch Sowing Schedule details for all batches in $inClause
        $sowSql = "
            SELECT stockorder_no, status, remarks, tedit, teditremark
            FROM krf_polyhouse_sowingschedule
            WHERE stockorder_no IN ($inClause)
            ORDER BY CASE WHEN LOWER(IFNULL(status,'')) IN ('pending','inprogress') THEN 1 ELSE 2 END, sowingschedulid DESC
        ";
        $sowRes = $krfconn->query($sowSql);
        if ($sowRes) {
            while ($sRow = $sowRes->fetch_assoc()) {
                $stk = $sRow['stockorder_no'];
                if (isset($stockOrders[$stk]) && empty($stockOrders[$stk]['sowing_schedule_status'])) {
                    $stockOrders[$stk]['sowing_schedule_status'] = $sRow['status'] ?? '';
                    $stockOrders[$stk]['sowing_schedule_remarks'] = $sRow['remarks'] ?? '';
                    $stockOrders[$stk]['sowing_schedule_teditremark'] = $sRow['teditremark'] ?? '';
                }
            }
        }

        // 2. Fetch comprehensive Polyhouse Plant Details (Total in PH, Consumed/Removed, Available)
        // Consumed/Removed: status in ('Internal Dispatch', 'Clip Stick Removal', 'Removed', 'UseForCorrection', 'Move for Grafting', 'Empty After Grafting', 'Empty After Shifting', 'Remove After Over Age', 'Tray Dead', 'Move for Opensale')
        $ppdTotalsSql = "
            SELECT 
                ppd.stockorder_no,
                COUNT(ppd.polyhouseplantid) AS total_trays,
                SUM(CAST(IFNULL(ppd.qtypertray, 0) AS SIGNED)) AS ph_total_plants,
                SUM(CASE 
                    WHEN LOWER(TRIM(IFNULL(ppd.status, ''))) IN ('polyhouse', 'readytosale', 'partial dispatched', 'partialdispatched', 'openstock','loaded')
                      OR LOWER(TRIM(IFNULL(ppd.workingstatus, ''))) IN ('polyhouse', 'readytosale', 'partial dispatched', 'partialdispatched','loaded')
                    THEN GREATEST(0, CAST(IFNULL(ppd.qtypertray, 0) AS SIGNED) - CAST(IFNULL(ppd.failurecount, 0) AS SIGNED))
                    ELSE 0 
                END) AS ph_avail_plants,
                SUM(CASE 
                    WHEN LOWER(TRIM(IFNULL(ppd.status, ''))) IN (
                        'internal dispatch', 
                        'clip stick removal', 
                        'removed', 
                        'useforcorrection', 
                        'move for grafting', 
                        'empty after grafting', 
                        'empty after shifting', 
                        'remove after over age', 
                        'tray dead', 
                        'move for opensale'
                    )
                    THEN CAST(IFNULL(ppd.qtypertray, 0) AS SIGNED)
                    ELSE 0 
                END) AS ph_consumed_plants
            FROM krf_polyhouse_plant_details ppd
            WHERE ppd.stockorder_no IN ($inClause)
              AND (ppd.tedit IS NULL OR ppd.tedit = '')
            GROUP BY ppd.stockorder_no
        ";
        $ppdTotalsRes = $krfconn->query($ppdTotalsSql);
        if ($ppdTotalsRes) {
            while ($totRow = $ppdTotalsRes->fetch_assoc()) {
                $stkNo = $totRow['stockorder_no'];
                if (isset($stockOrders[$stkNo])) {
                    $stockOrders[$stkNo]['ph_total_plants'] = (int)$totRow['ph_total_plants'];
                    $stockOrders[$stkNo]['ph_avail_plants'] = (int)$totRow['ph_avail_plants'];
                    $stockOrders[$stkNo]['ph_consumed_plants'] = (int)$totRow['ph_consumed_plants'];
                    $stockOrders[$stkNo]['consumed_removed_qty'] = (int)$totRow['ph_consumed_plants'];
                    $stockOrders[$stkNo]['polyhouse_ready_qty'] = (int)$totRow['ph_avail_plants'];
                    $stockOrders[$stkNo]['is_in_polyhouse'] = ((int)$totRow['ph_total_plants'] > 0) ? 1 : 0;
                }
            }
        }

        // 3. Fetch Bed-Wise breakdown for available trays in polyhouse
        $ppdSql = "
            SELECT ppd.stockorder_no,
                   ppd.polyhouseid,
                   COALESCE(phm.polyhousename, CONCAT('PH-', ppd.polyhouseid)) AS polyhouse_name,
                   COALESCE(NULLIF(TRIM(ppd.bedrackname), ''), ppd.bed_no, 'Bed') AS bed_name,
                   MAX(ppd.tray_id) AS tray_id,
                   COALESCE(NULLIF(MAX(tm.cavity_count), 0), NULLIF(MAX(CAST(ppd.qtypertray AS SIGNED)), 0), 126) AS cavity_count,
                   COUNT(DISTINCT COALESCE(NULLIF(ppd.barcode, ''), NULLIF(ppd.tray_code, ''), ppd.polyhouseplantid)) AS tray_count,
                   SUM(CASE 
                       WHEN LOWER(TRIM(IFNULL(ppd.status, ''))) IN ('polyhouse', 'readytosale', 'partial dispatched', 'partialdispatched', 'openstock','loaded')
                         OR LOWER(TRIM(IFNULL(ppd.workingstatus, ''))) IN ('polyhouse', 'readytosale', 'partial dispatched', 'partialdispatched','loaded')
                       THEN GREATEST(0, CAST(IFNULL(ppd.qtypertray, 0) AS SIGNED) - CAST(IFNULL(ppd.failurecount, 0) AS SIGNED))
                       ELSE 0 
                   END) AS healthy_plants
            FROM krf_polyhouse_plant_details ppd
            LEFT JOIN krf_polyhouse_master phm ON ppd.polyhouseid = phm.polyhouseid
            LEFT JOIN krf_tray_master tm ON (ppd.tray_id = tm.tray_id AND (tm.tedit IS NULL OR tm.tedit = '') AND LOWER(tm.status) = 'active')
            WHERE ppd.stockorder_no IN ($inClause)
              AND (LOWER(TRIM(IFNULL(ppd.status, ''))) IN ('polyhouse', 'readytosale', 'partial dispatched', 'partialdispatched', 'openstock','loaded')
                   OR LOWER(TRIM(IFNULL(ppd.workingstatus, ''))) IN ('polyhouse', 'readytosale', 'partial dispatched', 'partialdispatched','loaded'))
              AND (ppd.tedit IS NULL OR ppd.tedit = '')
            GROUP BY ppd.stockorder_no, ppd.polyhouseid, COALESCE(NULLIF(TRIM(ppd.bedrackname), ''), ppd.bed_no)
            HAVING healthy_plants > 0
            ORDER BY ppd.stockorder_no ASC, phm.polyhousename ASC, bed_name ASC
        ";
        $ppdRes = $krfconn->query($ppdSql);
        if ($ppdRes) {
            $phLocMap = [];
            $bedNumMap = [];
            while ($ppdRow = $ppdRes->fetch_assoc()) {
                $stkNo = $ppdRow['stockorder_no'];
                if (isset($stockOrders[$stkNo])) {
                    if (!empty($ppdRow['polyhouse_name'])) {
                        $phLocMap[$stkNo][$ppdRow['polyhouse_name']] = true;
                    }
                    if (!empty($ppdRow['bed_name'])) {
                        $bedNumMap[$stkNo][$ppdRow['bed_name']] = true;
                    }
                    $stockOrders[$stkNo]['available_beds'][] = [
                        'polyhouse_id'   => $ppdRow['polyhouseid'],
                        'polyhouse_name' => $ppdRow['polyhouse_name'],
                        'bed_name'       => $ppdRow['bed_name'],
                        'tray_id'        => (int)($ppdRow['tray_id'] ?? 2),
                        'cavity_count'   => (int)($ppdRow['cavity_count'] ?? 126),
                        'tray_count'     => (int)$ppdRow['tray_count'],
                        'healthy_plants' => (int)$ppdRow['healthy_plants']
                    ];
                }
            }
            foreach ($phLocMap as $stkNo => $phList) {
                if (isset($stockOrders[$stkNo])) {
                    $stockOrders[$stkNo]['polyhouse_locations'] = implode(', ', array_keys($phList));
                }
            }
            foreach ($bedNumMap as $stkNo => $bList) {
                if (isset($stockOrders[$stkNo])) {
                    $validBedsOnly = array_filter(array_keys($bList), function($bn) use ($stkNo, $stockOrders) {
                        $b = trim((string)$bn);
                        if (empty($b)) return false;
                        if (strcasecmp($b, trim((string)$stkNo)) === 0) return false;
                        if (!empty($stockOrders[$stkNo]['psono']) && strcasecmp($b, trim((string)$stockOrders[$stkNo]['psono'])) === 0) return false;
                        if (preg_match('/^(RSO|KFP|KF-PS|PSO|KF-|SO-|PO-|BATCH-)/i', $b)) return false;
                        if (in_array(strtolower($b), ['n/a', 'na', 'none', '-', 'bed', 'null', 'undefined', 'karjee'])) return false;
                        return true;
                    });
                    $stockOrders[$stkNo]['bed_numbers'] = implode(', ', $validBedsOnly);
                }
            }
        }

        // 3b. For RSO batches that do not have direct physical trays in krf_polyhouse_plant_details:
        // Resolve their parent production batch and inherit physical polyhouse availability & beds
        // Strictly ONLY for RSO batches (strncasecmp($stkNo, 'RSO-', 4) === 0 || strncasecmp($stkNo, 'RTS-', 4) === 0 || strncasecmp($stkNo, 'RSO', 3) === 0)
        // Standard production batches without RSO are NEVER touched!
        foreach ($stockOrders as $stkNo => &$sOrder) {
            $isRsoBatch = (strncasecmp($stkNo, 'RSO-', 4) === 0 || strncasecmp($stkNo, 'RTS-', 4) === 0 || strncasecmp($stkNo, 'RSO', 3) === 0);
            if ($isRsoBatch && (int)($sOrder['ph_total_plants'] ?? 0) <= 0) {
                $rsoParentInfo = resolveRsoParentBatch($krfconn, $stkNo);
                if ($rsoParentInfo && !empty($rsoParentInfo['parent_batch'])) {
                    $pBatch = $rsoParentInfo['parent_batch'];
                    // If parent batch already loaded in $stockOrders, copy its PH availability & beds
                    if (isset($stockOrders[$pBatch]) && (int)$stockOrders[$pBatch]['ph_avail_plants'] > 0) {
                        $pOrder = $stockOrders[$pBatch];
                        $ordQ = (int)($sOrder['total_qty'] ?? 0);
                        $availInParent = (int)$pOrder['ph_avail_plants'];
                        $sOrder['ph_total_plants'] = ($ordQ > 0) ? min($ordQ, $availInParent) : $availInParent;
                        $sOrder['ph_avail_plants'] = $sOrder['ph_total_plants'];
                        $sOrder['polyhouse_ready_qty'] = $sOrder['ph_avail_plants'];
                        $sOrder['is_in_polyhouse'] = 1;
                        if (empty($sOrder['polyhouse_locations'])) {
                            $sOrder['polyhouse_locations'] = $pOrder['polyhouse_locations'];
                        }
                        if (empty($sOrder['bed_numbers'])) {
                            $sOrder['bed_numbers'] = $pOrder['bed_numbers'];
                        }
                        if (empty($sOrder['available_beds']) && !empty($pOrder['available_beds'])) {
                            $sOrder['available_beds'] = $pOrder['available_beds'];
                        }
                    } else {
                        // Parent batch is not in current $stockOrders list, query PPD directly for parent batch
                        $escParent = $krfconn->real_escape_string($pBatch);
                        $chkParentQry = "
                            SELECT 
                                COUNT(ppd.polyhouseplantid) AS total_trays,
                                SUM(CAST(IFNULL(ppd.qtypertray, 0) AS SIGNED)) AS ph_total_plants,
                                SUM(CASE 
                                    WHEN LOWER(TRIM(IFNULL(ppd.status, ''))) IN ('polyhouse', 'readytosale', 'partial dispatched', 'partialdispatched', 'openstock','loaded')
                                      OR LOWER(TRIM(IFNULL(ppd.workingstatus, ''))) IN ('polyhouse', 'readytosale', 'partial dispatched', 'partialdispatched','loaded')
                                    THEN GREATEST(0, CAST(IFNULL(ppd.qtypertray, 0) AS SIGNED) - CAST(IFNULL(ppd.failurecount, 0) AS SIGNED))
                                    ELSE 0 
                                END) AS ph_avail_plants
                            FROM krf_polyhouse_plant_details ppd
                            WHERE ppd.stockorder_no = '{$escParent}'
                              AND (ppd.tedit IS NULL OR ppd.tedit = '')
                        ";
                        $chkParentRes = $krfconn->query($chkParentQry);
                        if ($chkParentRes && ($pRow = $chkParentRes->fetch_assoc())) {
                            $pAvail = (int)($pRow['ph_avail_plants'] ?? 0);
                            if ($pAvail > 0) {
                                $ordQ = (int)($sOrder['total_qty'] ?? 0);
                                $sOrder['ph_total_plants'] = ($ordQ > 0) ? min($ordQ, $pAvail) : $pAvail;
                                $sOrder['ph_avail_plants'] = $sOrder['ph_total_plants'];
                                $sOrder['polyhouse_ready_qty'] = $sOrder['ph_avail_plants'];
                                $sOrder['is_in_polyhouse'] = 1;

                                $pBedQry = "
                                    SELECT ppd.polyhouseid,
                                           COALESCE(phm.polyhousename, CONCAT('PH-', ppd.polyhouseid)) AS polyhouse_name,
                                           COALESCE(NULLIF(TRIM(ppd.bedrackname), ''), ppd.bed_no, 'Bed') AS bed_name,
                                           MAX(ppd.tray_id) AS tray_id,
                                           COALESCE(NULLIF(MAX(tm.cavity_count), 0), NULLIF(MAX(CAST(ppd.qtypertray AS SIGNED)), 0), 126) AS cavity_count,
                                           COUNT(DISTINCT COALESCE(NULLIF(ppd.barcode, ''), NULLIF(ppd.tray_code, ''), ppd.polyhouseplantid)) AS tray_count,
                                           SUM(CASE 
                                               WHEN LOWER(TRIM(IFNULL(ppd.status, ''))) IN ('polyhouse', 'readytosale', 'partial dispatched', 'partialdispatched', 'openstock','loaded')
                                                 OR LOWER(TRIM(IFNULL(ppd.workingstatus, ''))) IN ('polyhouse', 'readytosale', 'partial dispatched', 'partialdispatched','loaded')
                                               THEN GREATEST(0, CAST(IFNULL(ppd.qtypertray, 0) AS SIGNED) - CAST(IFNULL(ppd.failurecount, 0) AS SIGNED))
                                               ELSE 0 
                                           END) AS healthy_plants
                                    FROM krf_polyhouse_plant_details ppd
                                    LEFT JOIN krf_polyhouse_master phm ON ppd.polyhouseid = phm.polyhouseid
                                    LEFT JOIN krf_tray_master tm ON (ppd.tray_id = tm.tray_id AND (tm.tedit IS NULL OR tm.tedit = '') AND LOWER(tm.status) = 'active')
                                    WHERE ppd.stockorder_no = '{$escParent}'
                                      AND (LOWER(TRIM(IFNULL(ppd.status, ''))) IN ('polyhouse', 'readytosale', 'partial dispatched', 'partialdispatched', 'openstock','loaded')
                                           OR LOWER(TRIM(IFNULL(ppd.workingstatus, ''))) IN ('polyhouse', 'readytosale', 'partial dispatched', 'partialdispatched','loaded'))
                                      AND (ppd.tedit IS NULL OR ppd.tedit = '')
                                    GROUP BY ppd.polyhouseid, COALESCE(NULLIF(TRIM(ppd.bedrackname), ''), ppd.bed_no)
                                    HAVING healthy_plants > 0
                                ";
                                $pBedRes = $krfconn->query($pBedQry);
                                if ($pBedRes) {
                                    $pLocs = [];
                                    $pBeds = [];
                                    while ($pbRow = $pBedRes->fetch_assoc()) {
                                        if (!empty($pbRow['polyhouse_name'])) $pLocs[$pbRow['polyhouse_name']] = true;
                                        if (!empty($pbRow['bed_name'])) $pBeds[$pbRow['bed_name']] = true;
                                        $sOrder['available_beds'][] = [
                                            'polyhouse_id'   => $pbRow['polyhouseid'],
                                            'polyhouse_name' => $pbRow['polyhouse_name'],
                                            'bed_name'       => $pbRow['bed_name'],
                                            'tray_id'        => (int)($pbRow['tray_id'] ?? 2),
                                            'cavity_count'   => (int)($pbRow['cavity_count'] ?? 126),
                                            'tray_count'     => (int)$pbRow['tray_count'],
                                            'healthy_plants' => (int)$pbRow['healthy_plants']
                                        ];
                                    }
                                    if (empty($sOrder['polyhouse_locations']) && !empty($pLocs)) {
                                        $sOrder['polyhouse_locations'] = implode(', ', array_keys($pLocs));
                                    }
                                    if (empty($sOrder['bed_numbers']) && !empty($pBeds)) {
                                        $sOrder['bed_numbers'] = implode(', ', array_keys($pBeds));
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        unset($sOrder);

        // Helper to validate genuine bed names (filter out batch numbers and placeholders)
        $fnIsValidBed = function($bedName, $stockNo = '', $psoNo = '') {
            $b = trim((string)$bedName);
            if (empty($b)) return false;
            if (!empty($stockNo) && strcasecmp($b, trim((string)$stockNo)) === 0) return false;
            if (!empty($psoNo) && strcasecmp($b, trim((string)$psoNo)) === 0) return false;
            if (preg_match('/^(RSO|KFP|KF-PS|PSO|KF-|SO-|PO-|BATCH-)/i', $b)) return false;
            if (in_array(strtolower($b), ['n/a', 'na', 'none', '-', 'bed', 'null', 'undefined', 'karjee'])) return false;
            return true;
        };

        // 4. Also check krf_readytosale_stocks for ready stock / open stock batches
        // ONLY query/use RTS beds if krf_polyhouse_plant_details has NO beds for this batch
        $rtsSql = "
            SELECT 
                COALESCE(NULLIF(rts.stockorder_no, ''), rts.psono) AS stk,
                COALESCE(NULLIF(TRIM(rts.bedqrcode), ''), '') AS bed_name,
                COALESCE(NULLIF(TRIM(rts.locationname), ''), 'Karjee') AS polyhouse_name,
                COUNT(rts.id) AS tray_count,
                SUM(CAST(IFNULL(rts.qtypertray, 0) AS SIGNED)) AS total_plants,
                SUM(CASE 
                    WHEN LOWER(TRIM(IFNULL(rts.status, ''))) IN ('readytosale', 'polyhouse', 'openstock', 'partial dispatched', 'partialdispatched','loaded')
                    THEN CAST(IFNULL(rts.qtypertray, 0) AS SIGNED)
                    ELSE 0 
                END) AS healthy_plants,
                SUM(CASE 
                    WHEN LOWER(TRIM(IFNULL(rts.status, ''))) NOT IN ('readytosale', 'polyhouse', 'openstock', 'partial dispatched', 'partialdispatched','loaded')
                    THEN CAST(IFNULL(rts.qtypertray, 0) AS SIGNED)
                    ELSE 0 
                END) AS consumed_plants
            FROM krf_readytosale_stocks rts
            WHERE (rts.stockorder_no IN ($inClause) OR rts.psono IN ($inClause))
              AND (rts.tedit IS NULL OR rts.tedit = '')
            GROUP BY stk, bed_name, polyhouse_name
        ";
        $rtsRes = $krfconn->query($rtsSql);
        if ($rtsRes) {
            while ($rtsRow = $rtsRes->fetch_assoc()) {
                $stk = $rtsRow['stk'];
                if (isset($stockOrders[$stk])) {
                    $bName = trim($rtsRow['bed_name']);
                    $phName = trim($rtsRow['polyhouse_name']);
                    $hPlants = (int)$rtsRow['healthy_plants'];
                    $totPlants = (int)$rtsRow['total_plants'];
                    $consPlants = (int)$rtsRow['consumed_plants'];
                    $tCount = (int)$rtsRow['tray_count'];

                    // NOTE: Polyhouse plants and beds must strictly and exclusively come from krf_polyhouse_plant_details.
                    // krf_readytosale_stocks (RTS) must NOT fake/overwrite ph_total_plants or ph_avail_plants.
                    if (!empty($phName) && strcasecmp($phName, 'Karjee') !== 0 && (empty($stockOrders[$stk]['polyhouse_locations']) || $stockOrders[$stk]['polyhouse_locations'] === 'N/A')) {
                        $stockOrders[$stk]['polyhouse_locations'] = $phName;
                    }
                }
            }
        }

        // Also check krf_readystock_allocation for allocatedbed & polyhouse
        // ONLY use as fallback if available_beds is still empty
        $rsaSql = "
            SELECT 
                COALESCE(NULLIF(rsa.allocate_stockorder_no, ''), rsa.stockorder_no) AS stk,
                COALESCE(NULLIF(TRIM(rsa.allocatedbed), ''), '') AS bed_name,
                COALESCE(NULLIF(TRIM(rsa.polyhouse), ''), '') AS polyhouse_name
            FROM krf_readystock_allocation rsa
            WHERE (rsa.allocate_stockorder_no IN ($inClause) OR rsa.stockorder_no IN ($inClause))
              AND (IFNULL(rsa.allocatedbed, '') != '' OR IFNULL(rsa.polyhouse, '') != '')
              AND LOWER(IFNULL(rsa.status, '')) != 'cancel'
              AND (rsa.tedit IS NULL OR rsa.tedit = '')
        ";
        $rsaRes = $krfconn->query($rsaSql);
        if ($rsaRes) {
            while ($rsaRow = $rsaRes->fetch_assoc()) {
                $stk = $rsaRow['stk'];
                if (isset($stockOrders[$stk])) {
                    $bName = trim($rsaRow['bed_name']);
                    $phName = trim($rsaRow['polyhouse_name']);
                    if (!empty($phName) && (empty($stockOrders[$stk]['polyhouse_locations']) || $stockOrders[$stk]['polyhouse_locations'] === 'N/A')) {
                        $stockOrders[$stk]['polyhouse_locations'] = $phName;
                    }
                    if (empty($stockOrders[$stk]['available_beds']) && !empty($bName)) {
                        $parsedBeds = [];
                        if (strpos($bName, '[') !== false && strpos($bName, ']') !== false) {
                            $dec = json_decode($bName, true);
                            if (is_array($dec)) {
                                foreach ($dec as $d) {
                                    if (is_array($d) && !empty($d['bed_name'])) $parsedBeds[] = trim($d['bed_name']);
                                    else if (is_string($d) && trim($d) !== '') $parsedBeds[] = trim($d);
                                }
                            }
                        }
                        if (empty($parsedBeds)) {
                            $parsedBeds = array_filter(array_map('trim', explode(',', $bName)));
                        }
                        foreach ($parsedBeds as $pb) {
                            if (!$fnIsValidBed($pb, $stk, $stockOrders[$stk]['psono'] ?? '')) continue;
                            $existingBeds = array_filter(array_map('trim', explode(',', $stockOrders[$stk]['bed_numbers'] ?? '')));
                            if (!in_array($pb, $existingBeds)) {
                                $existingBeds[] = $pb;
                                $stockOrders[$stk]['bed_numbers'] = implode(', ', $existingBeds);
                            }
                            $alreadyIn = false;
                            foreach ($stockOrders[$stk]['available_beds'] as $ab) {
                                if (strcasecmp($ab['bed_name'], $pb) === 0) {
                                    $alreadyIn = true;
                                    break;
                                }
                            }
                            if (!$alreadyIn) {
                                $stockOrders[$stk]['available_beds'][] = [
                                    'polyhouse_id'   => 0,
                                    'polyhouse_name' => $phName ?: ($stockOrders[$stk]['polyhouse_locations'] ?: 'Main Polyhouse'),
                                    'bed_name'       => $pb,
                                    'tray_id'        => 2,
                                    'cavity_count'   => 126,
                                    'tray_count'     => 0,
                                    'healthy_plants' => (int)($stockOrders[$stk]['polyhouse_ready_qty'] ?: $stockOrders[$stk]['total_qty'])
                                ];
                            }
                        }
                    }
                }
            }
        }

        // Remarks fallback for bed information (e.g. "[12 Trays added in Bed Bed-25]")
        foreach ($stockOrders as $stkNo => &$sOrder) {
            if (empty($sOrder['available_beds']) && !empty($sOrder['remarks'])) {
                if (preg_match_all('/(?:in\s+Bed|Bed)\s*[:\-–]?\s*(Bed\s*[-–]?\s*\d+|\d+)/i', $sOrder['remarks'], $m)) {
                    $foundBeds = [];
                    foreach ($m[1] as $b) {
                        $bClean = preg_replace('/^Bed\s*[-–]?\s*/i', '', trim($b));
                        if (!empty($bClean)) {
                            $foundBeds[] = 'Bed-' . $bClean;
                        }
                    }
                    $foundBeds = array_values(array_unique($foundBeds));
                    if (!empty($foundBeds)) {
                        $validFoundBeds = array_filter($foundBeds, function($fb) use ($fnIsValidBed, $stkNo, $sOrder) {
                            return $fnIsValidBed($fb, $stkNo, $sOrder['psono'] ?? '');
                        });
                        if (!empty($validFoundBeds)) {
                            $sOrder['bed_numbers'] = implode(', ', $validFoundBeds);
                            foreach ($validFoundBeds as $fb) {
                                $sOrder['available_beds'][] = [
                                    'polyhouse_id'   => 0,
                                    'polyhouse_name' => $sOrder['polyhouse_locations'] ?: 'Karjee',
                                    'bed_name'       => $fb,
                                    'tray_id'        => 2,
                                    'cavity_count'   => 126,
                                    'tray_count'     => 0,
                                    'healthy_plants' => (int)($sOrder['polyhouse_ready_qty'] ?: $sOrder['total_qty'])
                                ];
                            }
                        }
                    }
                }
            }
        }
        unset($sOrder);
    }

    $results = [];
    foreach ($stockOrders as $row) {
        $orderQty = (int)($row['total_qty'] ?? 0);
        $phTotal = (int)($row['ph_total_plants'] ?? 0);
        $phAvail = (int)($row['ph_avail_plants'] ?? 0);
        $phConsumed = (int)($row['ph_consumed_plants'] ?? 0);
        $assignedQty = (int)($row['assigned_qty'] ?? 0);

        // Avail Bal (Ready to Allocate):
        // If batch is not available in polyhouse (0 total plants or 0 available healthy plants in polyhouse), available balance is strictly 0!
        if ($phTotal <= 0 || $phAvail <= 0) {
            $availBal = 0;
        } else {
            if ($orderQty > $phTotal) {
                $baseQty = $orderQty;
            } else {
                $baseQty = $phTotal;
            }

            // Available balance can NEVER exceed actual physical available healthy plants in polyhouse!
            $availBal = min($phAvail, max(0, $baseQty - ($assignedQty + $phConsumed)));
        }

        $row['assigned_qty'] = $assignedQty;
        $row['total_allocated_qty'] = $assignedQty;
        $row['consumed_removed_qty'] = $phConsumed;
        $row['ph_consumed_plants'] = $phConsumed;
        $row['available_balance_qty'] = $availBal;
        $row['available_balance'] = $availBal;
        $row['polyhouse_ready_qty'] = $phAvail;
        $row['ph_total_plants'] = $phTotal;
        $row['ph_avail_plants'] = $phAvail;
        $row['is_in_polyhouse'] = ($phTotal > 0 && $phAvail > 0) ? 1 : 0;
        $row['is_polyhouse_available'] = ($phTotal > 0 && $phAvail > 0 && $availBal > 0) ? 1 : 0;

        $rawPName = $row['product_name'] ?? '';
        $row['clean_product_name'] = preg_replace('/^(NonGrafted|Non-Grafted)\s*[-:]?\s*/i', '', $rawPName);

        // Client's Own Production Order flag
        $isOwnOrder = false;
        if (!empty($targetClientId)) {
            if (strcasecmp(trim($row['clientid'] ?? ''), $targetClientId) === 0 || strcasecmp(trim($row['sown_client_id'] ?? ''), $targetClientId) === 0) {
                $isOwnOrder = true;
            }
        }
        if (!empty($targetPsono) && !empty($row['psono']) && strcasecmp(trim($row['psono']), $targetPsono) === 0) {
            $isOwnOrder = true;
        }
        if (!empty($targetSono) && !empty($row['sown_sono']) && strcasecmp(trim($row['sown_sono']), $targetSono) === 0) {
            $isOwnOrder = true;
        }
        $row['is_own_production_order'] = $isOwnOrder ? 1 : 0;

        $daysDiff = null;
        if (!empty($deliveryDate) && $deliveryDate !== '0000-00-00' && !empty($row['expected_delivery_date']) && $row['expected_delivery_date'] !== '0000-00-00') {
            $d1 = new DateTime($deliveryDate);
            $d2 = new DateTime($row['expected_delivery_date']);
            $daysDiff = (int)$d1->diff($d2)->days;
        }
        $row['delivery_days_diff'] = $daysDiff;
        $row['is_delivery_eligible'] = ($daysDiff === null || $daysDiff <= 10);
        $results[] = $row;
    }

    // Sort results: Client's Own Production Order FIRST, Current Allocated Batch NEXT, Same Product NEXT, Available Bal > 0 NEXT, then delivery date
    usort($results, function($a, $b) use ($productId, $currentStockOrder) {
        if ($a['is_own_production_order'] !== $b['is_own_production_order']) {
            return $b['is_own_production_order'] - $a['is_own_production_order'];
        }
        $aIsCurrent = (!empty($currentStockOrder) && $a['stockorder_no'] === $currentStockOrder) ? 1 : 0;
        $bIsCurrent = (!empty($currentStockOrder) && $b['stockorder_no'] === $currentStockOrder) ? 1 : 0;
        if ($aIsCurrent !== $bIsCurrent) {
            return $bIsCurrent - $aIsCurrent;
        }
        $aSameProd = ($a['productid'] == $productId) ? 1 : 0;
        $bSameProd = ($b['productid'] == $productId) ? 1 : 0;
        if ($aSameProd !== $bSameProd) {
            return $bSameProd - $aSameProd;
        }
        $aHasBal = ($a['available_balance_qty'] > 0) ? 1 : 0;
        $bHasBal = ($b['available_balance_qty'] > 0) ? 1 : 0;
        if ($aHasBal !== $bHasBal) {
            return $bHasBal - $aHasBal;
        }
        $dateA = $a['expected_delivery_date'] ?? '';
        $dateB = $b['expected_delivery_date'] ?? '';
        if ($dateA !== $dateB) {
            return strcmp($dateA, $dateB);
        }
        return ($b['id'] ?? 0) - ($a['id'] ?? 0);
    });

    echo json_encode(["status" => "success", "stock_orders" => $results]);
    exit;

} else if ($action == "UpdateAllocation") {
    $projectid = intval($_POST['projectid'] ?? 0);
    $soiId = intval($_POST['soi_id'] ?? 0);
    $psono = trim($_POST['psono'] ?? '');
    $sono = trim($_POST['sono'] ?? '');
    $clientId = trim($_POST['client_id'] ?? '');
    $productId = trim($_POST['product_id'] ?? '');
    $productName = trim($_POST['product_name'] ?? '');
    $stockOrderNo = trim($_POST['stockorder_no'] ?? '');
    $allocateStockOrderNo = trim($_POST['allocate_stockorder_no'] ?? $stockOrderNo);
    if (empty($allocateStockOrderNo)) {
        $allocateStockOrderNo = $stockOrderNo;
    }
    $allocatedQty = floatval($_POST['allocated_qty'] ?? 0);
    $origOrderQty = intval($_POST['order_qty'] ?? 0);
    $healthyPlant = intval($_POST['healthyplant'] ?? 0);
    $unhealthyPlant = intval($_POST['unhealthyplant'] ?? 0);
    $isShifted = intval($_POST['isshifted'] ?? 1);
    $percentExtraShifting = floatval($_POST['percentextrashifting'] ?? 0);
    $extraQtyForShifting = intval($_POST['extraqtyforshifting'] ?? 0);
    if ($isShifted === 1) {
        $extraQtyForShifting = 0;
        $percentExtraShifting = 0;
    }
    $remark = trim($_POST['remark'] ?? '');
    $loginid = trim($_POST['loginid'] ?? '');
    $allowOverride = (isset($_POST['allow_override']) && $_POST['allow_override'] == '1') || (isset($_POST['force_proceed']) && $_POST['force_proceed'] == '1');

    if (empty($loginid)) {
        $loginid = $_SESSION['loginid'] ?? $_SESSION['username'] ?? 'admin';
    }

    if ($soiId <= 0 && !empty($psono)) {
        $findSoi = $krfconn->prepare("SELECT id FROM krf_sales_order_items WHERE psono = ? ORDER BY id DESC LIMIT 1");
        if ($findSoi) {
            $findSoi->bind_param("s", $psono);
            $findSoi->execute();
            if ($fRow = $findSoi->get_result()->fetch_assoc()) {
                $soiId = intval($fRow['id']);
            }
            $findSoi->close();
        }
    }
    if ($soiId <= 0 && !empty($sono)) {
        $findSoiSono = $krfconn->prepare("SELECT id FROM krf_sales_order_items WHERE sono = ? ORDER BY id DESC LIMIT 1");
        if ($findSoiSono) {
            $findSoiSono->bind_param("s", $sono);
            $findSoiSono->execute();
            if ($fRowSono = $findSoiSono->get_result()->fetch_assoc()) {
                $soiId = intval($fRowSono['id']);
            }
            $findSoiSono->close();
        }
    }
    if (empty($stockOrderNo) && $soiId > 0) {
        $findStk = $krfconn->prepare("SELECT stockorder_no FROM krf_sales_order_items WHERE id = ? LIMIT 1");
        if ($findStk) {
            $findStk->bind_param("i", $soiId);
            $findStk->execute();
            if ($stkRow = $findStk->get_result()->fetch_assoc()) {
                $stockOrderNo = trim($stkRow['stockorder_no'] ?? '');
                $allocateStockOrderNo = $stockOrderNo;
            }
            $findStk->close();
        }
    }

    if ($soiId <= 0 || empty($stockOrderNo)) {
        echo json_encode(["status" => "error", "message" => "Sales Order Item ID and Stock Order No are required."]);
        exit;
    }

    // Guard against allocating cancelled product or whole cancelled order
    $checkCancelled = false;
    $cancelMsg = "";

    // 1. Check if whole order is cancelled
    if (!empty($psono)) {
        $escPso = $krfconn->real_escape_string($psono);
        $qSoH = $krfconn->query("SELECT status FROM krf_sales_order WHERE psono = '$escPso' LIMIT 1");
        if ($qSoH && $rSoH = $qSoH->fetch_assoc()) {
            if (in_array(strtolower(trim($rSoH['status'] ?? '')), ['cancelled', 'cancel'])) {
                $checkCancelled = true;
                $cancelMsg = "Sales Order is cancelled. Allocation cannot be performed.";
            }
        }
        if (!$checkCancelled) {
            $qPsoH = $krfconn->query("SELECT status FROM krf_provisional_sales_order WHERE psono = '$escPso' LIMIT 1");
            if ($qPsoH && $rPsoH = $qPsoH->fetch_assoc()) {
                if (in_array(strtolower(trim($rPsoH['status'] ?? '')), ['cancelled', 'cancel'])) {
                    $checkCancelled = true;
                    $cancelMsg = "Provisional Order is cancelled. Allocation cannot be performed.";
                }
            }
        }
    }

    // 2. Check if THIS SPECIFIC PRODUCT is cancelled in krf_sales_order_items or krf_provisional_sales_order_items
    if (!$checkCancelled && $soiId > 0) {
        $qItem = $krfconn->query("SELECT status, psono, product_id, product_name FROM krf_sales_order_items WHERE id = " . intval($soiId) . " LIMIT 1");
        if ($qItem && $rItem = $qItem->fetch_assoc()) {
            if (in_array(strtolower(trim($rItem['status'] ?? '')), ['cancelled', 'cancel'])) {
                $checkCancelled = true;
                $cancelMsg = "Product (" . ($rItem['product_name'] ?? '') . ") is cancelled in Sales Order. Allocation is stopped.";
            } else if (!empty($rItem['psono'])) {
                $escPso = $krfconn->real_escape_string($rItem['psono']);
                $pId = intval($rItem['product_id'] ?? 0);
                $escPName = $krfconn->real_escape_string($rItem['product_name'] ?? '');
                $qPsoi = $krfconn->query("
                    SELECT product_name FROM krf_provisional_sales_order_items 
                    WHERE psono = '$escPso' 
                      AND (product_id = '$pId' OR product_name = '$escPName') 
                      AND LOWER(TRIM(IFNULL(status, ''))) IN ('cancelled', 'cancel') 
                    LIMIT 1
                ");
                if ($qPsoi && $qPsoi->num_rows > 0) {
                    $checkCancelled = true;
                    $rC = $qPsoi->fetch_assoc();
                    $cancelMsg = "Product (" . ($rC['product_name'] ?? '') . ") is cancelled in Provisional Sales Order. Allocation is stopped.";
                }
            }
        }
    }
    if ($checkCancelled) {
        echo json_encode(["status" => "error", "message" => $cancelMsg ?: "This product is cancelled. Allocation is stopped."]);
        exit;
    }

    $originalStockOrderNo = '';
    $orderA_deliveryDate = '';
    $clientNameA = '';
    // Auto-resolve missing fields from krf_sales_order_items if needed
    if ($soiId > 0) {
        $soiStmt = $krfconn->prepare("
            SELECT soi.projectid, soi.clientid, soi.product_id, soi.product_name, soi.sono, soi.psono, soi.quantity, soi.stockorder_no, soi.deliverydate, so.clientname
            FROM krf_sales_order_items soi
            LEFT JOIN krf_sales_order so ON (soi.psono = so.psono OR soi.sono = so.sono)
            WHERE soi.id = ? LIMIT 1
        ");
        $soiStmt->bind_param("i", $soiId);
        $soiStmt->execute();
        if ($sRow = $soiStmt->get_result()->fetch_assoc()) {
            if ($projectid <= 0 && !empty($sRow['projectid'])) $projectid = intval($sRow['projectid']);
            if (empty($clientId) && !empty($sRow['clientid'])) $clientId = trim($sRow['clientid']);
            if (empty($productId) && !empty($sRow['product_id'])) $productId = trim($sRow['product_id']);
            if (empty($productName) && !empty($sRow['product_name'])) $productName = trim($sRow['product_name']);
            if (empty($sono) && !empty($sRow['sono'])) $sono = trim($sRow['sono']);
            if (empty($psono) && !empty($sRow['psono'])) $psono = trim($sRow['psono']);
            if ($origOrderQty <= 0 && !empty($sRow['quantity'])) $origOrderQty = intval($sRow['quantity']);
            $clientNameA = trim($sRow['clientname'] ?? '');
            $origStock = trim($sRow['stockorder_no'] ?? '');
            if (!empty($origStock) && $origStock !== '0' && $origStock !== 'Not Allocated') {
                $originalStockOrderNo = $origStock;
            }
            if (!empty($sRow['deliverydate']) && $sRow['deliverydate'] !== '0000-00-00') {
                $orderA_deliveryDate = $sRow['deliverydate'];
            }
        }
        $soiStmt->close();
    }

    if (empty($originalStockOrderNo) || $originalStockOrderNo === '0' || $originalStockOrderNo === 'Not Allocated') {
        $originalStockOrderNo = (!empty($stockOrderNo) && $stockOrderNo !== 'Not Allocated' && $stockOrderNo !== '0') ? $stockOrderNo : $allocateStockOrderNo;
    }

    if (empty($orderA_deliveryDate)) {
        $psoAStmt = $krfconn->prepare("SELECT expected_delivery_date FROM krf_production_stockorder WHERE stockorder_no = ? LIMIT 1");
        if ($psoAStmt) {
            $psoAStmt->bind_param("s", $originalStockOrderNo);
            $psoAStmt->execute();
            $pRowA = $psoAStmt->get_result()->fetch_assoc();
            if ($pRowA && !empty($pRowA['expected_delivery_date'])) {
                $orderA_deliveryDate = $pRowA['expected_delivery_date'];
            }
            $psoAStmt->close();
        }
    }

    if ($projectid <= 0 && isset($_SESSION['projectid']) && intval($_SESSION['projectid']) > 0) {
        $projectid = intval($_SESSION['projectid']);
    }

    // If clientid still not found, fetch from sales order
    if (empty($clientId) && !empty($sono)) {
        $cStmt = $krfconn->prepare("SELECT clientid, clientname FROM krf_sales_order WHERE sono = ? LIMIT 1");
        $cStmt->bind_param("s", $sono);
        $cStmt->execute();
        $cRow = $cStmt->get_result()->fetch_assoc();
        if ($cRow) {
            if (!empty($cRow['clientid'])) $clientId = trim($cRow['clientid']);
            if (empty($clientNameA) && !empty($cRow['clientname'])) $clientNameA = trim($cRow['clientname']);
        }
        $cStmt->close();
    }

    $intAllocQty = intval($allocatedQty);
    $isShiftedInt = intval($isShifted);
    $percentExtraShiftingInt = intval(round($percentExtraShifting));
    $shiftingQty = intval($extraQtyForShifting);
    $isShiftedStr = ($isShiftedInt === 1 || strtolower(strval($isShifted)) === 'yes') ? 'yes' : 'no';

    $allocatedBedRaw = trim($_POST['allocatedbed'] ?? '');

    // ----------------------------------------------------
    // MULTI-BATCH ALLOCATION HANDLER
    // ----------------------------------------------------
    $allocationsJson = trim($_POST['allocations_json'] ?? '');
    $allocationsList = !empty($allocationsJson) ? json_decode($allocationsJson, true) : null;

    // Strict validation: Total allocated quantity across batches cannot exceed original order quantity
    $totalRequestedAlloc = 0;
    if (!empty($allocationsList) && is_array($allocationsList) && count($allocationsList) > 0) {
        foreach ($allocationsList as $chkAllocItem) {
            $totalRequestedAlloc += intval($chkAllocItem['allocated_qty'] ?? 0);
        }
    } else {
        $totalRequestedAlloc = $intAllocQty;
    }

    if (!$allowOverride && $origOrderQty > 0 && $totalRequestedAlloc > $origOrderQty) {
        echo json_encode([
            "status" => "error",
            "message" => "Total allocated quantity ({$totalRequestedAlloc} plants) cannot exceed the sales order quantity ({$origOrderQty} plants). Please adjust batch allocations."
        ]);
        exit;
    }

    // Strict validation: Verify allocated_qty matches total plants in allocatedbed JSON array for each batch
    if (!empty($allocationsList) && is_array($allocationsList) && count($allocationsList) > 0) {
        foreach ($allocationsList as $chkAllocItem) {
            $bStock = trim($chkAllocItem['stockorder_no'] ?? '');
            $bQty = intval($chkAllocItem['allocated_qty'] ?? 0);
            if ($bQty <= 0) continue;

            $bedsArr = null;
            if (!empty($chkAllocItem['allocatedbed'])) {
                $bedsArr = is_array($chkAllocItem['allocatedbed']) ? $chkAllocItem['allocatedbed'] : json_decode($chkAllocItem['allocatedbed'], true);
            } elseif (!empty($chkAllocItem['allocated_beds'])) {
                $bedsArr = is_array($chkAllocItem['allocated_beds']) ? $chkAllocItem['allocated_beds'] : json_decode($chkAllocItem['allocated_beds'], true);
            }

            if (!empty($bedsArr) && is_array($bedsArr)) {
                $sumBedPlants = 0;
                foreach ($bedsArr as $bEntry) {
                    $sumBedPlants += intval($bEntry['plants'] ?? 0);
                }
                $bExtra = ($isShiftedInt === 1) ? 0 : intval($chkAllocItem['extra_qty'] ?? ($chkAllocItem['extraqtyforshifting'] ?? 0));
                if ($sumBedPlants !== $bQty && $sumBedPlants !== ($bQty + $bExtra)) {
                    echo json_encode([
                        "status" => "error",
                        "message" => "Allocation failed: Allocated quantity ({$bQty} plants) does not match total plants in allocated beds ({$sumBedPlants} plants) for batch {$bStock}. Available bed capacity is insufficient. Please correct the bed selection."
                    ]);
                    exit;
                }
            }
        }
    }

    if (!empty($allocationsList) && is_array($allocationsList) && count($allocationsList) > 0) {
        // Validate total extra plants ceiling if applicable
        if (!$allowOverride && $isShiftedInt !== 1 && $percentExtraShifting > 0 && $origOrderQty > 0) {
            $totalAllocatedExtraPlantsCheck = 0;
            foreach ($allocationsList as $chkAllocItem) {
                $totalAllocatedExtraPlantsCheck += intval($chkAllocItem['extra_qty'] ?? ($chkAllocItem['extraqtyforshifting'] ?? 0));
            }
            $targetMaxExtra = intval(round($origOrderQty * ($percentExtraShifting / 100)));
            if ($totalAllocatedExtraPlantsCheck > $targetMaxExtra) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Total extra plants ({$totalAllocatedExtraPlantsCheck}) cannot exceed the target of {$targetMaxExtra} plants ({$percentExtraShifting}% of order qty {$origOrderQty})."
                ]);
                exit;
            }
        }

        // Validate each batch's available balance before committing
        foreach ($allocationsList as $chkAllocItem) {
            $bStock = trim($chkAllocItem['stockorder_no'] ?? '');
            $bQty = intval($chkAllocItem['allocated_qty'] ?? 0);
            $bExtra = ($isShiftedInt === 1) ? 0 : intval($chkAllocItem['extra_qty'] ?? ($chkAllocItem['extraqtyforshifting'] ?? 0));
            $totalBatchRequested = $bQty + $bExtra;
            if (empty($bStock) || $totalBatchRequested <= 0) continue;

            // Strict verification: Batch MUST have healthy available plants in krf_polyhouse_plant_details
            $chkPpdSql = "
                SELECT 
                    COUNT(*) as total_trays,
                    SUM(CASE 
                        WHEN LOWER(TRIM(IFNULL(ppd.status, ''))) IN ('polyhouse', 'readytosale', 'partial dispatched', 'partialdispatched', 'openstock', 'loaded')
                          OR LOWER(TRIM(IFNULL(ppd.workingstatus, ''))) IN ('polyhouse', 'readytosale', 'partial dispatched', 'partialdispatched', 'loaded')
                        THEN GREATEST(0, CAST(IFNULL(ppd.qtypertray, 0) AS SIGNED) - CAST(IFNULL(ppd.failurecount, 0) AS SIGNED))
                        ELSE 0 
                    END) as avail_plants
                FROM krf_polyhouse_plant_details ppd
                WHERE ppd.stockorder_no = '" . $krfconn->real_escape_string($bStock) . "'
                  AND (ppd.tedit IS NULL OR ppd.tedit = '')
            ";
            $chkPpdRes = $krfconn->query($chkPpdSql);
            $ppdData = $chkPpdRes ? $chkPpdRes->fetch_assoc() : null;
            $ppdTot = intval($ppdData['total_trays'] ?? 0);
            $ppdAvail = intval($ppdData['avail_plants'] ?? 0);

            // Fallback 1: If direct plants in PH not found, resolve parent batch for RSO/RTS batches
            if (($ppdTot <= 0 || $ppdAvail <= 0) && (strncasecmp($bStock, 'RSO-', 4) === 0 || strncasecmp($bStock, 'RTS-', 4) === 0 || strncasecmp($bStock, 'RSO', 3) === 0)) {
                $rsoParentInfo = resolveRsoParentBatch($krfconn, $bStock);
                if ($rsoParentInfo && !empty($rsoParentInfo['parent_batch'])) {
                    $parentStock = $rsoParentInfo['parent_batch'];
                    $chkParentSql = "
                        SELECT 
                            COUNT(*) as total_trays,
                            SUM(CASE 
                                WHEN LOWER(TRIM(IFNULL(ppd.status, ''))) IN ('polyhouse', 'readytosale', 'partial dispatched', 'partialdispatched', 'openstock', 'loaded')
                                  OR LOWER(TRIM(IFNULL(ppd.workingstatus, ''))) IN ('polyhouse', 'readytosale', 'partial dispatched', 'partialdispatched', 'loaded')
                                THEN GREATEST(0, CAST(IFNULL(ppd.qtypertray, 0) AS SIGNED) - CAST(IFNULL(ppd.failurecount, 0) AS SIGNED))
                                ELSE 0 
                            END) as avail_plants
                        FROM krf_polyhouse_plant_details ppd
                        WHERE ppd.stockorder_no = '" . $krfconn->real_escape_string($parentStock) . "'
                          AND (ppd.tedit IS NULL OR ppd.tedit = '')
                    ";
                    $chkParentRes = $krfconn->query($chkParentSql);
                    $parentData = $chkParentRes ? $chkParentRes->fetch_assoc() : null;
                    if (intval($parentData['total_trays'] ?? 0) > 0 && intval($parentData['avail_plants'] ?? 0) > 0) {
                        $ppdTot = intval($parentData['total_trays']);
                        $ppdAvail = intval($parentData['avail_plants']);
                    }
                }
            }

            // Fallback 2: Check krf_polyhouse_sowingplant_details if plants/trays are in sowing/germination stage
            if ($ppdTot <= 0 || $ppdAvail <= 0) {
                $chkSpSql = "
                    SELECT 
                        COUNT(*) as total_trays,
                        SUM(GREATEST(0, CAST(IFNULL(spd.traycavity, 126) AS SIGNED))) as avail_plants
                    FROM krf_polyhouse_sowingplant_details spd
                    WHERE spd.stockorder_no = '" . $krfconn->real_escape_string($bStock) . "'
                      AND (spd.tedit IS NULL OR spd.tedit = '')
                      AND LOWER(TRIM(IFNULL(spd.status, ''))) NOT IN ('cancelled', 'cancel', 'discarded', 'dead')
                ";
                $chkSpRes = $krfconn->query($chkSpSql);
                $spData = $chkSpRes ? $chkSpRes->fetch_assoc() : null;
                if (intval($spData['total_trays'] ?? 0) > 0 && intval($spData['avail_plants'] ?? 0) > 0) {
                    $ppdTot = intval($spData['total_trays']);
                    $ppdAvail = intval($spData['avail_plants']);
                }
            }

            // Fallback 3: Check krf_production_stockorder if quantity exists and order is confirmed/active
            if ($ppdTot <= 0 || $ppdAvail <= 0) {
                $chkPsoSql = "
                    SELECT quantity as total_qty
                    FROM krf_production_stockorder
                    WHERE stockorder_no = '" . $krfconn->real_escape_string($bStock) . "'
                      AND (tedit IS NULL OR tedit = '')
                      AND LOWER(TRIM(IFNULL(status, ''))) NOT IN ('cancelled', 'cancel')
                    LIMIT 1
                ";
                $chkPsoRes = $krfconn->query($chkPsoSql);
                $psoData = $chkPsoRes ? $chkPsoRes->fetch_assoc() : null;
                if (intval($psoData['total_qty'] ?? 0) > 0) {
                    $ppdTot = 1;
                    $ppdAvail = intval($psoData['total_qty']);
                }
            }

            if ($ppdTot <= 0 || $ppdAvail <= 0) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Allocation failed: Plant not available in PH for batch {$bStock}. Cannot allocate from this batch."
                ]);
                exit;
            }

            $balStmt = $krfconn->prepare("
                SELECT pso.quantity AS total_qty,
                       (SELECT IFNULL(SUM(CASE WHEN LOWER(IFNULL(ppd.health_status, '')) = 'healthy' AND LOWER(IFNULL(ppd.status, '')) IN ('openstock', 'polyhouse', 'readytosale') THEN GREATEST(0, CAST(IFNULL(ppd.qtypertray, 0) AS SIGNED) - CAST(IFNULL(failurecount, 0) AS SIGNED)) ELSE 0 END), 0)
                        FROM krf_polyhouse_plant_details ppd
                        WHERE ppd.stockorder_no = pso.stockorder_no
                       ) AS polyhouse_ready_qty,
                       (SELECT IFNULL(SUM(poc.consumed_qty), 0)
                        FROM krf_productionorder_consumed poc
                        LEFT JOIN krf_provisional_sales_order prov ON (poc.psono = prov.psono)
                        WHERE (poc.allocated_stockorderno = pso.stockorder_no OR (IFNULL(poc.allocated_stockorderno, '') = '' AND poc.stockorderno = pso.stockorder_no))
                          AND (poc.psono != ? OR poc.psono IS NULL OR ? = '')
                          AND LOWER(IFNULL(poc.confirmstatus, '')) IN ('confirmed', 'pending')
                          AND LOWER(IFNULL(poc.status, '')) != 'dispatched'
                          AND (prov.status IS NULL OR LOWER(prov.status) != 'draft')
                          AND IFNULL(poc.tedit, '') = ''
                       ) AS other_allocated_qty
                FROM krf_production_stockorder pso
                WHERE pso.stockorder_no = ? LIMIT 1
            ");
            if ($balStmt) {
                $balStmt->bind_param("sss", $psono, $psono, $bStock);
                $balStmt->execute();
                $bRes = $balStmt->get_result()->fetch_assoc();
                $balStmt->close();
                if ($bRes) {
                    $psoTotal = intval($bRes['total_qty'] ?? 0);
                    $polyReady = intval($bRes['polyhouse_ready_qty'] ?? 0);
                    $effectiveCapacity = ($polyReady > 0) ? max($psoTotal, $polyReady) : $psoTotal;
                    $psoOtherAlloc = intval($bRes['other_allocated_qty'] ?? 0);
                    $availBal = max(0, $effectiveCapacity - $psoOtherAlloc);

                    if (!$allowOverride && $totalBatchRequested > $availBal) {
                        echo json_encode([
                            "status" => "error",
                            "message" => "Production batch {$bStock} only has available balance {$availBal} plants, but total requested (Base: {$bQty} + Extra: {$bExtra} = {$totalBatchRequested} plants) exceeds available balance. Please adjust quantity."
                        ]);
                        exit;
                    }
                }
            }
        }

        // Ensure legacy unique constraint uq_ready_stock_pool is dropped to allow multiple client allocations per batch
        safeCleanupReadyStockConstraints($krfconn);

        $krfconn->begin_transaction();
        try {
            // 1. Collect all newly allocated batch numbers
            $newAllocatedStockNos = [];
            foreach ($allocationsList as $itemAlloc) {
                $stk = trim($itemAlloc['stockorder_no'] ?? '');
                $bQ = floatval($itemAlloc['allocated_qty'] ?? 0);
                $bE = floatval($itemAlloc['extra_qty'] ?? ($itemAlloc['extraqtyforshifting'] ?? 0));
                if (!empty($stk) && ($bQ > 0 || $bE > 0)) {
                    $newAllocatedStockNos[] = $stk;
                }
            }
            $newAllocatedStockNos = array_values(array_unique($newAllocatedStockNos));

            // 2. Identify Own Production Order stock order numbers for this client and product
            $ownStockOrders = [];
            $ownPsoStmt = $krfconn->prepare("
                SELECT stockorder_no 
                FROM krf_production_stockorder 
                WHERE (psono = ? OR (clientid = ? AND clientid != '')) 
                  AND productid = ?
            ");
            if ($ownPsoStmt) {
                $ownPsoStmt->bind_param("sss", $psono, $clientId, $productId);
                $ownPsoStmt->execute();
                $ownPsoRes = $ownPsoStmt->get_result();
                while ($opRow = $ownPsoRes->fetch_assoc()) {
                    if (!empty($opRow['stockorder_no'])) {
                        $ownStockOrders[] = trim($opRow['stockorder_no']);
                    }
                }
                $ownPsoStmt->close();
            }

            // 3. Inspect existing active consumed records for this psono and product_id
            $existingPocRecords = [];
            $exPocStmt = $krfconn->prepare("
                SELECT id, stockorderno, allocated_stockorderno, consumed_qty, remark, psono, sono
                FROM krf_productionorder_consumed
                WHERE psono = ? AND product_id = ? AND LOWER(IFNULL(status, '')) != 'dispatched' AND IFNULL(tedit, '') = ''
            ");
            if ($exPocStmt) {
                $exPocStmt->bind_param("ss", $psono, $productId);
                $exPocStmt->execute();
                $exRes = $exPocStmt->get_result();
                while ($exR = $exRes->fetch_assoc()) {
                    $existingPocRecords[] = $exR;
                }
                $exPocStmt->close();
            }

            // Summary of new allocations for audit trail remarks
            $newAllocSummaryParts = [];
            foreach ($allocationsList as $itemAlloc) {
                $stk = trim($itemAlloc['stockorder_no'] ?? '');
                $q = floatval($itemAlloc['allocated_qty'] ?? 0);
                $ext = ($isShiftedInt === 1) ? 0 : intval($itemAlloc['extra_qty'] ?? ($itemAlloc['extraqtyforshifting'] ?? 0));
                if (!empty($stk) && ($q > 0 || $ext > 0)) {
                    $newAllocSummaryParts[] = $stk . " (" . intval($q) . ($ext > 0 ? " + " . $ext . " extra" : "") . ")";
                }
            }
            $newAllocSummaryStr = !empty($newAllocSummaryParts) ? implode(', ', $newAllocSummaryParts) : 'None';

            // 4. Map exact matches:
            // Match condition: Same production order batch AND same allocated quantity!
            $matchedExistingPocMap = []; // $bStockNo => existing POC row id
            $matchedExistingIds = [];

            foreach ($allocationsList as $itemAlloc) {
                $bStockNo = trim($itemAlloc['stockorder_no'] ?? '');
                $bAllocQty = floatval($itemAlloc['allocated_qty'] ?? 0);
                $bUserExtraQty = ($isShiftedInt === 1) ? 0 : intval($itemAlloc['extra_qty'] ?? ($itemAlloc['extraqtyforshifting'] ?? 0));
                if (empty($bStockNo) || ($bAllocQty <= 0 && $bUserExtraQty <= 0)) continue;

                foreach ($existingPocRecords as $exR) {
                    if (in_array($exR['id'], $matchedExistingIds)) continue;

                    $exStk = trim($exR['stockorderno'] ?? '');
                    $exAllocStk = trim($exR['allocated_stockorderno'] ?? '');
                    $isBatchMatch = ($exAllocStk === $bStockNo || (empty($exAllocStk) && $exStk === $bStockNo));
                    $isQtyMatch = (floatval($exR['consumed_qty']) === $bAllocQty);

                    if ($isBatchMatch && $isQtyMatch) {
                        // Same production order batch AND same allocated quantity -> Exact Match!
                        $matchedExistingPocMap[$bStockNo] = $exR['id'];
                        $matchedExistingIds[] = $exR['id'];
                        break;
                    }
                }
            }

            // 5. Any existing active record that was NOT an exact match has a difference:
            // (Either its batch was changed/swapped, or its quantity was changed, or it was unselected)
            // -> Mark the actual row with tedit = '1', confirmstatus = 'edited', ensure sono/psono are not NULL, and record teditremark!
            $updArchivePoc = $krfconn->prepare("
                UPDATE krf_productionorder_consumed 
                SET tedit = '1', 
                    confirmstatus = 'edited',
                    sono = CASE WHEN sono IS NULL OR sono = '' THEN ? ELSE sono END,
                    psono = CASE WHEN psono IS NULL OR psono = '' THEN ? ELSE psono END,
                    teditremark = ?, 
                    updated_at = NOW() 
                WHERE id = ?
            ");

            foreach ($existingPocRecords as $exR) {
                if (in_array($exR['id'], $matchedExistingIds)) continue;

                $exStk = trim($exR['stockorderno'] ?? '');
                $exAllocStk = trim($exR['allocated_stockorderno'] ?? '');
                $exBatch = !empty($exAllocStk) ? $exAllocStk : $exStk;
                $exQty = intval($exR['consumed_qty']);

                // Determine if this batch is still in the new allocation but with a different quantity
                $newAllocMatch = null;
                foreach ($allocationsList as $itemAlloc) {
                    if (trim($itemAlloc['stockorder_no'] ?? '') === $exBatch) {
                        $newAllocMatch = $itemAlloc;
                        break;
                    }
                }

                if ($newAllocMatch !== null) {
                    $newQty = intval($newAllocMatch['allocated_qty'] ?? 0);
                    $auditRemark = "Allocation quantity modified from " . number_format($exQty) . " to " . number_format($newQty) . " on " . date('d-M-Y H:i:s') . " by " . $loginid;
                } else {
                    $auditRemark = "Swapped/Replaced from " . $exBatch . " (" . number_format($exQty) . ") to " . $newAllocSummaryStr . " on " . date('d-M-Y H:i:s') . " by " . $loginid;
                }

                if (!empty($remark) && !str_starts_with($remark, "Allocated across")) {
                    $auditRemark .= " | Remark: " . $remark;
                }

                if ($updArchivePoc) {
                    $updArchivePoc->bind_param("sssi", $sono, $psono, $auditRemark, $exR['id']);
                    $updArchivePoc->execute();
                }
            }
            if ($updArchivePoc) $updArchivePoc->close();

            $totalAllocatedQty = 0;
            $totalAllocatedExtraPlants = 0;
            $allAllocatedStockNos = [];
            $allAllocatedPsonos = [];

            // 6. Find and archive existing active krf_readystock_allocation rows for this order / product
            $existingRsaRecords = [];
            $exRsaStmt = $krfconn->prepare("
                SELECT id, stockorder_no, allocate_stockorder_no, allocate_ordqty, polyhouse, psono, clientid, productid, productname
                FROM krf_readystock_allocation
                WHERE (
                    ((psono = ? AND ? != '') AND (productid = ? OR productname = ?))
                    OR ((clientid = ? AND ? != '') AND (productid = ? OR productname = ?) AND (psono IS NULL OR psono = '' OR psono = ?))
                )
                AND LOWER(IFNULL(status, '')) != 'cancel'
                AND (tedit IS NULL OR tedit = '')
            ");
            if ($exRsaStmt) {
                $exRsaStmt->bind_param("sssssssss", 
                    $psono, $psono, $productId, $productName,
                    $clientId, $clientId, $productId, $productName, $psono
                );
                $exRsaStmt->execute();
                $exRsaRes = $exRsaStmt->get_result();
                while ($exRsa = $exRsaRes->fetch_assoc()) {
                    $existingRsaRecords[] = $exRsa;
                }
                $exRsaStmt->close();
            }

            $updArchiveRsa = $krfconn->prepare("
                UPDATE krf_readystock_allocation
                SET tedit = '1',
                    status = 'cancel',
                    teditremark = ?,
                    updated_at = NOW(),
                    updated_by = ?
                WHERE id = ?
            ");
            foreach ($existingRsaRecords as $exRsa) {
                $exBatch = !empty($exRsa['allocate_stockorder_no']) ? $exRsa['allocate_stockorder_no'] : $exRsa['stockorder_no'];
                $exQty = intval($exRsa['allocate_ordqty']);
                
                // Check if this batch is still in the new allocation
                $newAllocMatch = null;
                foreach ($allocationsList as $itemAlloc) {
                    if (trim($itemAlloc['stockorder_no'] ?? '') === $exBatch) {
                        $newAllocMatch = $itemAlloc;
                        break;
                    }
                }
                
                if ($newAllocMatch !== null) {
                    $newQty = intval($newAllocMatch['allocated_qty'] ?? 0);
                    $rsaAuditRemark = "Allocation quantity modified from " . number_format($exQty) . " to " . number_format($newQty) . " on " . date('d-M-Y H:i:s') . " by " . $loginid;
                } else {
                    $rsaAuditRemark = "Re-allocated from " . $exBatch . " (" . number_format($exQty) . ") to " . $newAllocSummaryStr . " on " . date('d-M-Y H:i:s') . " by " . $loginid;
                }
                if (!empty($remark) && !str_starts_with($remark, "Allocated across")) {
                    $rsaAuditRemark .= " | Remark: " . $remark;
                }
                $rsaAuditRemark = substr($rsaAuditRemark, 0, 490);
                
                if ($updArchiveRsa) {
                    $updArchiveRsa->bind_param("ssi", $rsaAuditRemark, $loginid, $exRsa['id']);
                    $updArchiveRsa->execute();
                }
            }
            if ($updArchiveRsa) $updArchiveRsa->close();

            $insPocStmt = $krfconn->prepare("
                INSERT INTO krf_productionorder_consumed 
                (psono, sono, stockorderno, allocated_stockorderno, allocated_psono, product_id, product_name, consumed_qty, wastage_qty, projectid, created_by, created_at, confirmstatus, remark, allocate_healthyplant, allocate_unhealthyplant, isshifted, percentextrashifting, extraqtyforshifting, status, allocatedby, allocatedat, updated_at, tedit, teditremark)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0.000, ?, ?, NOW(), 'Confirmed', ?, ?, ?, ?, ?, ?, 'allocated', ?, NOW(), NOW(), NULL, NULL)
            ");
            $updActivePocStmt = $krfconn->prepare("
                UPDATE krf_productionorder_consumed 
                SET sono = ?,
                    allocated_stockorderno = ?,
                    allocated_psono = ?,
                    product_name = ?,
                    consumed_qty = ?,
                    remark = ?,
                    allocate_healthyplant = ?,
                    allocate_unhealthyplant = ?,
                    isshifted = ?,
                    percentextrashifting = ?,
                    extraqtyforshifting = ?,
                    status = 'allocated',
                    confirmstatus = 'Confirmed',
                    allocatedby = ?,
                    allocatedat = NOW(),
                    updated_at = NOW(),
                    tedit = NULL,
                    teditremark = NULL
                WHERE id = ?
            ");

            $rsaSql = "
                INSERT INTO krf_readystock_allocation (
                    projectid, psono, stockorder_no, allocate_stockorder_no, clientid, productid, productname,
                    allocate_ordqty, healthyplant, unhealthyplant, isshifted, percentextrashifting, shiftingqty,
                    allocatedbed, polyhouse,
                    created_at, created_by, updated_at, updated_by, status, tedit, teditremark
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?,
                    ?, ?,
                    NOW(), ?, NOW(), ?, 'allocated', NULL, NULL
                )
            ";
            $rsaStmt = $krfconn->prepare($rsaSql);

            foreach ($allocationsList as $itemAlloc) {
                $bStockNo = trim($itemAlloc['stockorder_no'] ?? '');
                $bAllocQty = floatval($itemAlloc['allocated_qty'] ?? 0);
                $bUserExtraQty = ($isShiftedInt === 1) ? 0 : intval($itemAlloc['extra_qty'] ?? ($itemAlloc['extraqtyforshifting'] ?? 0));
                if (empty($bStockNo) || ($bAllocQty <= 0 && $bUserExtraQty <= 0)) continue;

                $totalAllocatedQty += $bAllocQty;
                $allAllocatedStockNos[] = $bStockNo;

                $bPsono = trim($itemAlloc['psono'] ?? '');
                if (empty($bPsono)) {
                    $psoPsonoStmt = $krfconn->prepare("SELECT psono FROM krf_production_stockorder WHERE stockorder_no = ? LIMIT 1");
                    if ($psoPsonoStmt) {
                        $psoPsonoStmt->bind_param("s", $bStockNo);
                        $psoPsonoStmt->execute();
                        $pRow = $psoPsonoStmt->get_result()->fetch_assoc();
                        if ($pRow && !empty($pRow['psono'])) {
                            $bPsono = trim($pRow['psono']);
                        }
                        $psoPsonoStmt->close();
                    }
                }
                if (empty($bPsono)) $bPsono = $psono;
                $allAllocatedPsonos[] = $bPsono;

                $bIntQty = intval($bAllocQty);
                $bHealthy = $bIntQty;
                $bUnhealthy = 0;

                // Support per-batch Extra Plant specification from allocation table
                $bUserExtraQty = isset($itemAlloc['extra_qty']) ? intval($itemAlloc['extra_qty']) : (isset($itemAlloc['extraqtyforshifting']) ? intval($itemAlloc['extraqtyforshifting']) : -1);
                if ($isShiftedInt === 1) {
                    $bShiftingQty = intval(ceil($bIntQty / 125));
                } else if ($bUserExtraQty >= 0) {
                    $bShiftingQty = $bUserExtraQty;
                } else {
                    $bShiftingQty = intval(round($bIntQty * ($percentExtraShifting / 100)));
                }
                if ($isShiftedInt !== 1) {
                    $totalAllocatedExtraPlants += $bShiftingQty;
                }

                $bAllocatedBedJson = null;
                if (!empty($itemAlloc['allocated_beds'])) {
                    $bAllocatedBedJson = is_string($itemAlloc['allocated_beds']) ? $itemAlloc['allocated_beds'] : json_encode($itemAlloc['allocated_beds']);
                } elseif (!empty($itemAlloc['allocatedbed'])) {
                    $bAllocatedBedJson = is_string($itemAlloc['allocatedbed']) ? $itemAlloc['allocatedbed'] : json_encode($itemAlloc['allocatedbed']);
                } elseif (!empty($allocatedBedRaw)) {
                    $bAllocatedBedJson = $allocatedBedRaw;
                }

                if (empty($bAllocatedBedJson) || $bAllocatedBedJson === '[]' || $bAllocatedBedJson === 'null') {
                    $defCavity = 126;
                    $defaultTrayCount = ($isShiftedInt === 1) 
                        ? (int)ceil($bIntQty / max(1, $defCavity - 1))
                        : (int)ceil(($bIntQty + $bShiftingQty) / $defCavity);
                    $bAllocatedBedJson = json_encode([[
                        "polyhouse_id"   => "",
                        "polyhouse_name" => !empty($itemAlloc['polyhouse_locations']) ? $itemAlloc['polyhouse_locations'] : "Main Polyhouse",
                        "bed_name"       => !empty($itemAlloc['bed_numbers']) ? $itemAlloc['bed_numbers'] : "Bed",
                        "tray_count"     => $defaultTrayCount > 0 ? $defaultTrayCount : 20,
                        "plants"         => $bIntQty > 0 ? $bIntQty : 2000,
                        "total_plants"   => ($isShiftedInt === 1) ? $bIntQty : ($bIntQty + $bShiftingQty),
                        "extra_plants"   => ($isShiftedInt === 1) ? 0 : $bShiftingQty
                    ]]);
                }

                $bPolyhouse = resolvePolyhouseName($krfconn, $bStockNo, $bAllocatedBedJson, $itemAlloc['polyhouse_locations'] ?? '');
                if (!empty($bPolyhouse) && !empty($bAllocatedBedJson)) {
                    $dec = json_decode($bAllocatedBedJson, true);
                    if (is_array($dec) && !empty($dec)) {
                        $updatedDec = false;
                        foreach ($dec as &$bedEntry) {
                            if (empty($bedEntry['polyhouse_name']) || in_array($bedEntry['polyhouse_name'], ['Main Polyhouse', 'N/A'])) {
                                $bedEntry['polyhouse_name'] = $bPolyhouse;
                                $updatedDec = true;
                            }
                        }
                        unset($bedEntry);
                        if ($updatedDec) {
                            $bAllocatedBedJson = json_encode($dec);
                        }
                    }
                }

                $bPsonoForRsa = !empty($psono) ? $psono : (!empty($itemAlloc['psono']) ? trim($itemAlloc['psono']) : '');
                if (empty($bPsonoForRsa) && !empty($bPsono)) {
                    $bPsonoForRsa = $bPsono;
                }

                // Resolve the actual product of the allocated batch
                $bProdId = trim($itemAlloc['product_id'] ?? ($itemAlloc['productid'] ?? ''));
                $bProdName = trim($itemAlloc['product_name'] ?? ($itemAlloc['productname'] ?? ''));

                if (empty($bProdName) || empty($bProdId)) {
                    $bLookupStmt = $krfconn->prepare("SELECT productid, product_name FROM krf_production_stockorder WHERE stockorder_no = ? LIMIT 1");
                    if ($bLookupStmt) {
                        $bLookupStmt->bind_param("s", $bStockNo);
                        $bLookupStmt->execute();
                        $bLookupRow = $bLookupStmt->get_result()->fetch_assoc();
                        if ($bLookupRow) {
                            if (empty($bProdId) && !empty($bLookupRow['productid'])) $bProdId = trim($bLookupRow['productid']);
                            if (empty($bProdName) && !empty($bLookupRow['product_name'])) $bProdName = trim($bLookupRow['product_name']);
                        }
                        $bLookupStmt->close();
                    }
                }
                if (empty($bProdName) || empty($bProdId)) {
                    $bRtsStmt = $krfconn->prepare("SELECT productid, productname FROM krf_readytosale_stocks WHERE stockorder_no = ? LIMIT 1");
                    if ($bRtsStmt) {
                        $bRtsStmt->bind_param("s", $bStockNo);
                        $bRtsStmt->execute();
                        $bRtsRow = $bRtsStmt->get_result()->fetch_assoc();
                        if ($bRtsRow) {
                            if (empty($bProdId) && !empty($bRtsRow['productid'])) $bProdId = trim($bRtsRow['productid']);
                            if (empty($bProdName) && !empty($bRtsRow['productname'])) $bProdName = trim($bRtsRow['productname']);
                        }
                        $bRtsStmt->close();
                    }
                }
                if (empty($bProdId)) $bProdId = $productId;
                if (empty($bProdName)) $bProdName = $productName;

                if (isset($matchedExistingPocMap[$bStockNo]) && $updActivePocStmt) {
                    $exPocId = $matchedExistingPocMap[$bStockNo];
                    $updActivePocStmt->bind_param(
                        "ssssdsiiidisi",
                        $sono,
                        $bStockNo,
                        $bPsono,
                        $productName,
                        $bAllocQty,
                        $remark,
                        $bHealthy,
                        $bUnhealthy,
                        $isShiftedInt,
                        $percentExtraShifting,
                        $bShiftingQty,
                        $loginid,
                        $exPocId
                    );
                    $updActivePocStmt->execute();
                } else if ($insPocStmt) {
                    $insPocStmt->bind_param(
                        "sssssssdissiiidis",
                        $psono,
                        $sono,
                        $bStockNo,
                        $bStockNo,
                        $bPsono,
                        $productId,
                        $productName,
                        $bAllocQty,
                        $projectid,
                        $loginid,
                        $remark,
                        $bHealthy,
                        $bUnhealthy,
                        $isShiftedInt,
                        $percentExtraShifting,
                        $bShiftingQty,
                        $loginid
                    );
                    $insPocStmt->execute();
                }

                if ($rsaStmt) {
                    $rsaStmt->bind_param(
                        "issssssiiiiiissss",
                        $projectid,
                        $bPsonoForRsa,
                        $bStockNo,
                        $bStockNo,
                        $clientId,
                        $bProdId,
                        $bProdName,
                        $bIntQty,
                        $bHealthy,
                        $bUnhealthy,
                        $isShiftedInt,
                        $percentExtraShiftingInt,
                        $bShiftingQty,
                        $bAllocatedBedJson,
                        $bPolyhouse,
                        $loginid,
                        $loginid
                    );
                    $rsaStmt->execute();
                }
            }

            if ($insPocStmt) $insPocStmt->close();
            if ($updActivePocStmt) $updActivePocStmt->close();
            if ($rsaStmt) $rsaStmt->close();

            // Ensure any rows in krf_readystock_allocation for these allocated batches have psono synced
            if (!empty($psono) && !empty($allAllocatedStockNos)) {
                $escStocksList = "'" . implode("','", array_map([$krfconn, 'real_escape_string'], $allAllocatedStockNos)) . "'";
                $escPsono = $krfconn->real_escape_string($psono);
                $krfconn->query("
                    UPDATE krf_readystock_allocation 
                    SET psono = '{$escPsono}'
                    WHERE (stockorder_no IN ({$escStocksList}) OR allocate_stockorder_no IN ({$escStocksList}))
                      AND (psono IS NULL OR psono = '')
                ");
            }

            // Stale / previous readystock_allocation rows were already marked with tedit = '1' and teditremark (never deleted)

            $commaStockNos = implode(', ', array_unique($allAllocatedStockNos));
            $intTotalAlloc = intval($totalAllocatedQty);
            $totalShiftingQty = ($isShiftedInt === 1) ? 0 : (($totalAllocatedExtraPlants > 0) ? $totalAllocatedExtraPlants : ($shiftingQty > 0 ? $shiftingQty : intval(round($intTotalAlloc * ($percentExtraShifting / 100)))));
            $itemStatus = ($origOrderQty > 0 && $intTotalAlloc >= $origOrderQty) ? 'Allocated' : (($intTotalAlloc > 0) ? 'Partial Allocated' : 'Confirmed');

            // 2. Update krf_sales_order_items (preserve original quantity and stockorder_no intact)
            $updSoi = "
                UPDATE krf_sales_order_items soi
                SET soi.reservedqty = ?,
                    soi.allocate_ordqty = ?,
                    soi.balancereservedqty = GREATEST(0, CAST(IFNULL(soi.quantity, 0) AS SIGNED) - ?),
                    soi.healthyplant = ?,
                    soi.unhealthyplant = ?,
                    soi.isshifted = ?,
                    soi.percentextrashifting = ?,
                    soi.shiftingqty = ?,
                    soi.status = ?,
                    soi.ammendedby = ?,
                    soi.updateamended = NOW()
                WHERE soi.id = ?
            ";
            $soiStmt = $krfconn->prepare($updSoi);
            if ($soiStmt) {
                $soiStmt->bind_param(
                    "iiiiisdissi",
                    $intTotalAlloc,
                    $intTotalAlloc,
                    $intTotalAlloc,
                    $intTotalAlloc,
                    $unhealthyPlant,
                    $isShiftedStr,
                    $percentExtraShifting,
                    $totalShiftingQty,
                    $itemStatus,
                    $loginid,
                    $soiId
                );
                $soiStmt->execute();
                $soiStmt->close();
            }

            // 3. Update krf_sales_order status (Allocated if all items allocated, PartialAllocated if some remain unallocated)
            if (!empty($psono) || !empty($sono)) {
                $hasUnalloc = false;
                $chkItemsStmt = $krfconn->prepare("
                    SELECT COUNT(*) AS unalloc_count
                    FROM krf_sales_order_items
                    WHERE ((psono = ? AND ? != '') OR (sono = ? AND ? != ''))
                      AND id != ?
                      AND (allocate_ordqty IS NULL OR allocate_ordqty < quantity)
                      AND LOWER(IFNULL(status, '')) NOT IN ('delivered', 'completed', 'cancelled')
                ");
                if ($chkItemsStmt) {
                    $chkItemsStmt->bind_param("ssssi", $psono, $psono, $sono, $sono, $soiId);
                    $chkItemsStmt->execute();
                    $cRow = $chkItemsStmt->get_result()->fetch_assoc();
                    if ($cRow && intval($cRow['unalloc_count'] ?? 0) > 0) {
                        $hasUnalloc = true;
                    }
                    $chkItemsStmt->close();
                }

                $newSoStatus = ($hasUnalloc || ($origOrderQty > 0 && $intTotalAlloc < $origOrderQty)) ? 'PartialAllocated' : 'Allocated';
                if ($intTotalAlloc == 0 && $hasUnalloc) {
                    $newSoStatus = 'Confirmed';
                }

                $updSo = "
                    UPDATE krf_sales_order
                    SET status = ?,
                        updated_at = NOW(),
                        updated_by = ?
                    WHERE ((psono = ? AND ? != '') OR (sono = ? AND ? != ''))
                      AND LOWER(IFNULL(status, '')) NOT IN ('delivered', 'completed', 'dispatched')
                ";
                $soStmt = $krfconn->prepare($updSo);
                if ($soStmt) {
                    $soStmt->bind_param("ssssss", $newSoStatus, $loginid, $psono, $psono, $sono, $sono);
                    $soStmt->execute();
                    $soStmt->close();
                }
            }

            // Revert any previously loaded trays for this order that belong to old/replaced batches back to Readytosale
            autoUndoPreviousLoadedTraysOnReallocation($krfconn, $psono, $allAllocatedStockNos, $productId, $productName, $loginid);

            // Synchronize active dispatch invoice items to the newly allocated batch(es)
            syncInvoiceItemsOnReallocation($krfconn, $psono, $allAllocatedStockNos, $productId, $productName, $loginid);

            $krfconn->commit();

            $batchCount = count($allAllocatedStockNos);
            echo json_encode([
                "status" => "success",
                "message" => "Successfully allocated {$intTotalAlloc} plants across {$batchCount} production " . ($batchCount > 1 ? "batches" : "batch") . " ({$commaStockNos})."
            ]);
            exit;
        } catch (Throwable $e) {
            $krfconn->rollback();
            echo json_encode(["status" => "error", "message" => "Multi-batch allocation failed: " . $e->getMessage()]);
            exit;
        }
    }

    // Find if $allocateStockOrderNo is associated with another order (Order B)
    $orderB = null;
    if (!empty($allocateStockOrderNo) && $allocateStockOrderNo !== 'Not Allocated' && $allocateStockOrderNo !== $originalStockOrderNo) {
        // Step 1: Look for other active consumed record where this batch is allocated or sown
        $findPocSql = "
            SELECT poc.id, poc.psono, poc.sono, poc.product_id, poc.product_name, poc.consumed_qty, poc.stockorderno, poc.allocated_stockorderno
            FROM krf_productionorder_consumed poc
            WHERE (poc.allocated_stockorderno = ? OR (IFNULL(poc.allocated_stockorderno, '') = '' AND poc.stockorderno = ?))
              AND (poc.psono != ? OR poc.psono IS NULL OR ? = '')
              AND (poc.sono != ? OR poc.sono IS NULL OR ? = '')
              AND LOWER(IFNULL(poc.status, '')) != 'dispatched'
              AND IFNULL(poc.tedit, '') = ''
            ORDER BY poc.id DESC LIMIT 1
        ";
        $fStmt = $krfconn->prepare($findPocSql);
        $fStmt->bind_param("ssssss", $allocateStockOrderNo, $allocateStockOrderNo, $psono, $psono, $sono, $sono);
        $fStmt->execute();
        $pocB = $fStmt->get_result()->fetch_assoc();
        $fStmt->close();

        $bPsono = $pocB['psono'] ?? '';
        $bSono = $pocB['sono'] ?? '';

        // Step 2: If no psono in poc, look in krf_production_stockorder
        if (empty($bPsono)) {
            $psoBStmt = $krfconn->prepare("SELECT psono, clientid, productid, product_name, quantity, expected_delivery_date FROM krf_production_stockorder WHERE stockorder_no = ? LIMIT 1");
            if ($psoBStmt) {
                $psoBStmt->bind_param("s", $allocateStockOrderNo);
                $psoBStmt->execute();
                $psoBRow = $psoBStmt->get_result()->fetch_assoc();
                if ($psoBRow && !empty($psoBRow['psono']) && $psoBRow['psono'] !== $psono) {
                    $bPsono = trim($psoBRow['psono']);
                }
                $psoBStmt->close();
            }
        }

        // Step 3: Fetch Order B's sales order item details
        if (!empty($bPsono) || !empty($bSono) || !empty($allocateStockOrderNo)) {
            $soiBSql = "
                SELECT soi.id AS soi_id, soi.sono, soi.psono, soi.clientid, soi.product_id, soi.product_name, 
                       soi.quantity, soi.stockorder_no, soi.deliverydate, so.clientname, so.projectid
                FROM krf_sales_order_items soi
                LEFT JOIN krf_sales_order so ON (soi.psono = so.psono OR soi.sono = so.sono)
                WHERE (soi.stockorder_no = ? OR ((soi.psono = ? AND ? != '') AND (soi.product_id = ? OR ? = '')) OR ((soi.sono = ? AND ? != '') AND (soi.product_id = ? OR ? = '')))
                  AND soi.id != ?
                  AND (so.invoicetype = 'sale' OR so.invoicetype IS NULL OR so.invoicetype = '')
                ORDER BY (CASE WHEN soi.stockorder_no = ? THEN 1 WHEN soi.product_id = ? THEN 2 ELSE 3 END), soi.id DESC 
                LIMIT 1
            ";
            $soiBStmt = $krfconn->prepare($soiBSql);
            $soiBStmt->bind_param("sssssssssiss", 
                $allocateStockOrderNo, 
                $bPsono, $bPsono, $productId, $productId,
                $bSono, $bSono, $productId, $productId,
                $soiId,
                $allocateStockOrderNo, $productId
            );
            $soiBStmt->execute();
            $soiBRow = $soiBStmt->get_result()->fetch_assoc();
            $soiBStmt->close();

            if ($soiBRow) {
                $orderB = $soiBRow;
                $orderB['poc_id'] = $pocB['id'] ?? 0;
                $orderB['orig_stock'] = (!empty($soiBRow['stockorder_no']) && $soiBRow['stockorder_no'] !== '0' && $soiBRow['stockorder_no'] !== 'Not Allocated') 
                    ? $soiBRow['stockorder_no'] 
                    : $allocateStockOrderNo;

                if (empty($orderB['deliverydate']) || $orderB['deliverydate'] === '0000-00-00') {
                    $psoBDateStmt = $krfconn->prepare("SELECT expected_delivery_date FROM krf_production_stockorder WHERE stockorder_no = ? LIMIT 1");
                    if ($psoBDateStmt) {
                        $psoBDateStmt->bind_param("s", $allocateStockOrderNo);
                        $psoBDateStmt->execute();
                        $pRowB = $psoBDateStmt->get_result()->fetch_assoc();
                        if ($pRowB && !empty($pRowB['expected_delivery_date'])) {
                            $orderB['deliverydate'] = $pRowB['expected_delivery_date'];
                        }
                        $psoBDateStmt->close();
                    }
                }
            }
        }
    }

    $isMutualSwap = false;
    if ($orderB) {
        // Validation 1: Same variety required
        $cleanNameA = strtolower(trim(preg_replace('/^(NonGrafted|Non-Grafted)\s*[-:]?\s*/i', '', $productName)));
        $cleanNameB = strtolower(trim(preg_replace('/^(NonGrafted|Non-Grafted)\s*[-:]?\s*/i', '', $orderB['product_name'] ?? '')));
        $prodIdA = trim(strval($productId));
        $prodIdB = trim(strval($orderB['product_id'] ?? ''));

        if ($prodIdA !== $prodIdB && $cleanNameA !== $cleanNameB) {
            echo json_encode([
                "status" => "error", 
                "message" => "Cannot re-allocate / swap orders: Both orders must be of the same variety. Selected client variety is '{$productName}' while target order variety is '{$orderB['product_name']}'."
            ]);
            exit;
        }

        // Validation 2: Expected delivery date difference <= 10 days
        if (!empty($orderA_deliveryDate) && !empty($orderB['deliverydate']) && $orderA_deliveryDate !== '0000-00-00' && $orderB['deliverydate'] !== '0000-00-00') {
            $d1 = new DateTime($orderA_deliveryDate);
            $d2 = new DateTime($orderB['deliverydate']);
            $daysDiff = (int)$d1->diff($d2)->days;
            if ($daysDiff > 10) {
                echo json_encode([
                    "status" => "error", 
                    "message" => "Cannot re-allocate / swap orders: Expected delivery date difference is {$daysDiff} days ({$orderA_deliveryDate} vs {$orderB['deliverydate']}). Re-allocation is only permitted when the difference is 10 days or less."
                ]);
                exit;
            }
        }

        $isMutualSwap = true;
    }

    $intAllocQty = intval($allocatedQty);
    $isShiftedInt = intval($isShifted);
    $percentExtraShiftingInt = intval(round($percentExtraShifting));
    $shiftingQty = intval($extraQtyForShifting);
    $isShiftedStr = ($isShiftedInt === 1 || strtolower(strval($isShifted)) === 'yes') ? 'yes' : 'no';

    // Validation 3: Check Available Balance of Stock Order
    if (!empty($allocateStockOrderNo) && $allocateStockOrderNo !== 'Not Allocated' && $intAllocQty > 0) {
        $balStmt = $krfconn->prepare("
            SELECT pso.quantity AS total_qty,
                   (SELECT IFNULL(SUM(CASE WHEN LOWER(IFNULL(ppd.health_status, '')) = 'healthy' AND LOWER(IFNULL(ppd.status, '')) IN ('openstock', 'polyhouse', 'readytosale') THEN GREATEST(0, CAST(IFNULL(ppd.qtypertray, 0) AS SIGNED) - CAST(IFNULL(ppd.failurecount, 0) AS SIGNED)) ELSE 0 END), 0)
                    FROM krf_polyhouse_plant_details ppd
                    WHERE ppd.stockorder_no = pso.stockorder_no
                   ) AS polyhouse_ready_qty,
                   (SELECT IFNULL(SUM(poc.consumed_qty), 0)
                    FROM krf_productionorder_consumed poc
                    LEFT JOIN krf_provisional_sales_order prov ON (poc.psono = prov.psono)
                    WHERE (poc.allocated_stockorderno = pso.stockorder_no OR (IFNULL(poc.allocated_stockorderno, '') = '' AND poc.stockorderno = pso.stockorder_no))
                      AND (poc.psono != ? OR poc.psono IS NULL OR ? = '')
                      AND LOWER(IFNULL(poc.confirmstatus, '')) IN ('confirmed', 'pending')
                      AND LOWER(IFNULL(poc.status, '')) != 'dispatched'
                      AND (prov.status IS NULL OR LOWER(prov.status) != 'draft')
                      AND IFNULL(poc.tedit, '') = ''
                   ) AS other_allocated_qty
            FROM krf_production_stockorder pso
            WHERE pso.stockorder_no = ? LIMIT 1
        ");
        if ($balStmt) {
            $balStmt->bind_param("sss", $psono, $psono, $allocateStockOrderNo);
            $balStmt->execute();
            $bRes = $balStmt->get_result()->fetch_assoc();
            $balStmt->close();
            if ($bRes) {
                $psoTotal = intval($bRes['total_qty'] ?? 0);
                $polyReady = intval($bRes['polyhouse_ready_qty'] ?? 0);
                $effectiveCapacity = ($polyReady > 0) ? max($psoTotal, $polyReady) : $psoTotal;
                $psoOtherAlloc = intval($bRes['other_allocated_qty'] ?? 0);
                $availBal = max(0, $effectiveCapacity - $psoOtherAlloc);

                // Alert if requested quantity exceeds genuinely available balance
                if (!$allowOverride && $intAllocQty > $availBal) {
                    echo json_encode([
                        "status" => "error",
                        "message" => "Selected order {$allocateStockOrderNo} only has balance {$availBal} qty, but required is {$intAllocQty} qty. Selected order cannot fulfill the order. Please choose another order for allocation of {$intAllocQty} qty."
                    ]);
                    exit;
                }

                // Strict verification: Batch MUST have healthy available plants in krf_polyhouse_plant_details
                $chkSinglePpd = $krfconn->query("
                    SELECT COUNT(*) as total_trays,
                           SUM(CASE 
                               WHEN LOWER(TRIM(IFNULL(ppd.status, ''))) IN ('polyhouse', 'readytosale', 'partial dispatched', 'partialdispatched', 'openstock', 'loaded')
                                 OR LOWER(TRIM(IFNULL(ppd.workingstatus, ''))) IN ('polyhouse', 'readytosale', 'partial dispatched', 'partialdispatched', 'loaded')
                               THEN GREATEST(0, CAST(IFNULL(ppd.qtypertray, 0) AS SIGNED) - CAST(IFNULL(failurecount, 0) AS SIGNED))
                               ELSE 0 
                           END) as avail_plants
                    FROM krf_polyhouse_plant_details ppd
                    WHERE ppd.stockorder_no = '" . $krfconn->real_escape_string($allocateStockOrderNo) . "'
                      AND (ppd.tedit IS NULL OR ppd.tedit = '')
                ");
                $sData = $chkSinglePpd ? $chkSinglePpd->fetch_assoc() : null;
                $sTot = intval($sData['total_trays'] ?? 0);
                $sAvail = intval($sData['avail_plants'] ?? 0);

                // Fallback 1: If direct plants in PH not found, resolve parent batch for RSO/RTS batches
                if (($sTot <= 0 || $sAvail <= 0) && (strncasecmp($allocateStockOrderNo, 'RSO-', 4) === 0 || strncasecmp($allocateStockOrderNo, 'RTS-', 4) === 0 || strncasecmp($allocateStockOrderNo, 'RSO', 3) === 0)) {
                    $rsoParentInfo = resolveRsoParentBatch($krfconn, $allocateStockOrderNo);
                    if ($rsoParentInfo && !empty($rsoParentInfo['parent_batch'])) {
                        $parentStock = $rsoParentInfo['parent_batch'];
                        $chkParentSql = "
                            SELECT COUNT(*) as total_trays,
                                   SUM(CASE 
                                       WHEN LOWER(TRIM(IFNULL(ppd.status, ''))) IN ('polyhouse', 'readytosale', 'partial dispatched', 'partialdispatched', 'openstock', 'loaded')
                                         OR LOWER(TRIM(IFNULL(ppd.workingstatus, ''))) IN ('polyhouse', 'readytosale', 'partial dispatched', 'partialdispatched', 'loaded')
                                       THEN GREATEST(0, CAST(IFNULL(ppd.qtypertray, 0) AS SIGNED) - CAST(IFNULL(failurecount, 0) AS SIGNED))
                                       ELSE 0 
                                   END) as avail_plants
                            FROM krf_polyhouse_plant_details ppd
                            WHERE ppd.stockorder_no = '" . $krfconn->real_escape_string($parentStock) . "'
                              AND (ppd.tedit IS NULL OR ppd.tedit = '')
                        ";
                        $chkParentRes = $krfconn->query($chkParentSql);
                        $parentData = $chkParentRes ? $chkParentRes->fetch_assoc() : null;
                        if (intval($parentData['total_trays'] ?? 0) > 0 && intval($parentData['avail_plants'] ?? 0) > 0) {
                            $sTot = intval($parentData['total_trays']);
                            $sAvail = intval($parentData['avail_plants']);
                        }
                    }
                }

                // Fallback 2: Check krf_polyhouse_sowingplant_details if plants/trays are in sowing/germination stage
                if ($sTot <= 0 || $sAvail <= 0) {
                    $chkSpSql = "
                        SELECT COUNT(*) as total_trays,
                               SUM(GREATEST(0, CAST(IFNULL(spd.traycavity, 126) AS SIGNED))) as avail_plants
                        FROM krf_polyhouse_sowingplant_details spd
                        WHERE spd.stockorder_no = '" . $krfconn->real_escape_string($allocateStockOrderNo) . "'
                          AND (spd.tedit IS NULL OR spd.tedit = '')
                          AND LOWER(TRIM(IFNULL(spd.status, ''))) NOT IN ('cancelled', 'cancel', 'discarded', 'dead')
                    ";
                    $chkSpRes = $krfconn->query($chkSpSql);
                    $spData = $chkSpRes ? $chkSpRes->fetch_assoc() : null;
                    if (intval($spData['total_trays'] ?? 0) > 0 && intval($spData['avail_plants'] ?? 0) > 0) {
                        $sTot = intval($spData['total_trays']);
                        $sAvail = intval($spData['avail_plants']);
                    }
                }

                // Fallback 3: Check krf_production_stockorder if quantity exists and order is confirmed/active
                if ($sTot <= 0 || $sAvail <= 0) {
                    $chkPsoSql = "
                        SELECT quantity as total_qty
                        FROM krf_production_stockorder
                        WHERE stockorder_no = '" . $krfconn->real_escape_string($allocateStockOrderNo) . "'
                          AND (tedit IS NULL OR tedit = '')
                          AND LOWER(TRIM(IFNULL(status, ''))) NOT IN ('cancelled', 'cancel')
                        LIMIT 1
                    ";
                    $chkPsoRes = $krfconn->query($chkPsoSql);
                    $psoData = $chkPsoRes ? $chkPsoRes->fetch_assoc() : null;
                    if (intval($psoData['total_qty'] ?? 0) > 0) {
                        $sTot = 1;
                        $sAvail = intval($psoData['total_qty']);
                    }
                }

                if ($sTot <= 0 || $sAvail <= 0) {
                    echo json_encode([
                        "status" => "error",
                        "message" => "Allocation failed: Plant not available in PH for batch {$allocateStockOrderNo}. Cannot allocate against this batch."
                    ]);
                    exit;
                }
            }
        }
    }

    // Ensure legacy unique constraint uq_ready_stock_pool is dropped to allow multiple client allocations per batch
    safeCleanupReadyStockConstraints($krfconn);

    $krfconn->begin_transaction();
    try {
        // ----------------------------------------------------
        // 1. ORDER A: Update or Insert krf_productionorder_consumed
        // ----------------------------------------------------
        $chkSql = "SELECT id, stockorderno, allocated_stockorderno, psono, original_stockorder_no, original_orderqty FROM krf_productionorder_consumed WHERE psono = ? AND product_id = ? AND IFNULL(tedit, '') = '' LIMIT 1";
        $chkStmt = $krfconn->prepare($chkSql);
        $chkStmt->bind_param("ss", $psono, $productId);
        $chkStmt->execute();
        $chkRes = $chkStmt->get_result();

        // Resolve allocated_psono from krf_production_stockorder for $allocateStockOrderNo
        $allocatedPsono = trim($_POST['allocated_psono'] ?? '');
        if (empty($allocatedPsono) && !empty($allocateStockOrderNo) && $allocateStockOrderNo !== 'Not Allocated') {
            $psoPsonoStmt = $krfconn->prepare("SELECT psono FROM krf_production_stockorder WHERE stockorder_no = ? LIMIT 1");
            if ($psoPsonoStmt) {
                $psoPsonoStmt->bind_param("s", $allocateStockOrderNo);
                $psoPsonoStmt->execute();
                $pRow = $psoPsonoStmt->get_result()->fetch_assoc();
                if ($pRow && !empty($pRow['psono'])) {
                    $allocatedPsono = trim($pRow['psono']);
                }
                $psoPsonoStmt->close();
            }
        }
        if (empty($allocatedPsono)) {
            $allocatedPsono = $orderB ? ($orderB['psono'] ?? $allocateStockOrderNo) : $allocateStockOrderNo;
        }

        $existingStockOrderNo = '';
        $previousAllocatedStock = '';
        if ($chkRow = $chkRes->fetch_assoc()) {
            $pocId = $chkRow['id'];
            $existingStockOrderNo = trim($chkRow['stockorderno'] ?? '');
            $previousAllocatedStock = trim($chkRow['allocated_stockorderno'] ?? '');

            $updPoc = "
                UPDATE krf_productionorder_consumed 
                SET allocated_stockorderno = ?,
                    allocated_psono = ?,
                    sono = ?,
                    consumed_qty = ?,
                    remark = ?,
                    allocate_healthyplant = ?,
                    allocate_unhealthyplant = ?,
                    isshifted = ?,
                    percentextrashifting = ?,
                    extraqtyforshifting = ?,
                    status = 'allocated',
                    confirmstatus = 'Confirmed',
                    allocatedby = ?,
                    allocatedat = NOW(),
                    updated_at = NOW()
                WHERE id = ?
            ";
            $uStmt = $krfconn->prepare($updPoc);
            $uStmt->bind_param(
                "sssdsiiidisi",
                $allocateStockOrderNo,
                $allocatedPsono,
                $sono,
                $allocatedQty,
                $remark,
                $healthyPlant,
                $unhealthyPlant,
                $isShiftedInt,
                $percentExtraShifting,
                $extraQtyForShifting,
                $loginid,
                $pocId
            );
            $uStmt->execute();
            $uStmt->close();
        } else {
            $sownStock = (!empty($originalStockOrderNo) && $originalStockOrderNo !== 'Not Allocated') ? $originalStockOrderNo : $allocateStockOrderNo;
            $existingStockOrderNo = $sownStock;
            $insPoc = "
                INSERT INTO krf_productionorder_consumed 
                (psono, sono, stockorderno, allocated_stockorderno, allocated_psono, product_id, product_name, consumed_qty, wastage_qty, projectid, created_by, created_at, confirmstatus, remark, allocate_healthyplant, allocate_unhealthyplant, isshifted, percentextrashifting, extraqtyforshifting, status, allocatedby, allocatedat, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0.000, ?, ?, NOW(), 'Confirmed', ?, ?, ?, ?, ?, ?, 'allocated', ?, NOW(), NOW())
            ";
            $iStmt = $krfconn->prepare($insPoc);
            $iStmt->bind_param(
                "sssssssdisisiiids",
                $psono,
                $sono,
                $sownStock,
                $allocateStockOrderNo,
                $allocatedPsono,
                $productId,
                $productName,
                $allocatedQty,
                $projectid,
                $loginid,
                $remark,
                $healthyPlant,
                $unhealthyPlant,
                $isShiftedInt,
                $percentExtraShifting,
                $extraQtyForShifting,
                $loginid
            );
            $iStmt->execute();
            $iStmt->close();
        }
        $chkStmt->close();

        // ----------------------------------------------------
        // 2. ORDER A: Update krf_sales_order_items (preserve original quantity and stockorder_no intact)
        // ----------------------------------------------------
        $itemStatusA = ($origOrderQty > 0 && $intAllocQty >= $origOrderQty) ? 'Allocated' : (($intAllocQty > 0) ? 'Partial Allocated' : 'Confirmed');

        $updSoi = "
            UPDATE krf_sales_order_items soi
            SET soi.reservedqty = ?,
                soi.allocate_ordqty = ?,
                soi.balancereservedqty = GREATEST(0, CAST(IFNULL(soi.quantity, 0) AS SIGNED) - ?),
                soi.healthyplant = ?,
                soi.unhealthyplant = ?,
                soi.isshifted = ?,
                soi.percentextrashifting = ?,
                soi.shiftingqty = ?,
                soi.status = ?,
                soi.ammendedby = ?,
                soi.updateamended = NOW()
            WHERE soi.id = ?
        ";
        $soiStmt = $krfconn->prepare($updSoi);
        if (!$soiStmt) {
            throw new Exception("Failed to prepare update for krf_sales_order_items: " . $krfconn->error);
        }
        $soiStmt->bind_param(
            "iiiiisdissi",
            $intAllocQty,
            $intAllocQty,
            $intAllocQty,
            $healthyPlant,
            $unhealthyPlant,
            $isShiftedStr,
            $percentExtraShifting,
            $shiftingQty,
            $itemStatusA,
            $loginid,
            $soiId
        );
        $soiStmt->execute();
        $soiStmt->close();

        // ----------------------------------------------------
        // 2b. ORDER A: Update krf_sales_order status (Allocated if all items allocated, PartialAllocated if some remain unallocated)
        // ----------------------------------------------------
        if (!empty($psono) || !empty($sono)) {
            $hasUnalloc = false;
            $chkItemsStmt = $krfconn->prepare("
                SELECT COUNT(*) AS unalloc_count
                FROM krf_sales_order_items
                WHERE ((psono = ? AND ? != '') OR (sono = ? AND ? != ''))
                  AND id != ?
                  AND (allocate_ordqty IS NULL OR allocate_ordqty < quantity)
                  AND LOWER(IFNULL(status, '')) NOT IN ('delivered', 'completed', 'cancelled')
            ");
            if ($chkItemsStmt) {
                $chkItemsStmt->bind_param("ssssi", $psono, $psono, $sono, $sono, $soiId);
                $chkItemsStmt->execute();
                $cRow = $chkItemsStmt->get_result()->fetch_assoc();
                if ($cRow && intval($cRow['unalloc_count'] ?? 0) > 0) {
                    $hasUnalloc = true;
                }
                $chkItemsStmt->close();
            }

            $newSoStatus = ($hasUnalloc || ($origOrderQty > 0 && $intAllocQty < $origOrderQty)) ? 'PartialAllocated' : 'Allocated';
            if ($intAllocQty == 0 && $hasUnalloc) {
                $newSoStatus = 'Confirmed';
            }

            $updSo = "
                UPDATE krf_sales_order
                SET status = ?,
                    updated_at = NOW(),
                    updated_by = ?
                WHERE ((psono = ? AND ? != '') OR (sono = ? AND ? != ''))
                  AND LOWER(IFNULL(status, '')) NOT IN ('delivered', 'completed', 'dispatched')
            ";
            $soStmt = $krfconn->prepare($updSo);
            if ($soStmt) {
                $soStmt->bind_param("ssssss", $newSoStatus, $loginid, $psono, $psono, $sono, $sono);
                $soStmt->execute();
                $soStmt->close();
            }
        }

        // ----------------------------------------------------
        // 3. ORDER A: Insert into krf_readystock_allocation (with audit trail)
        // ----------------------------------------------------
        $stockOrderForRsaA = (!empty($existingStockOrderNo) && $existingStockOrderNo !== 'Not Allocated' && $existingStockOrderNo !== '0')
            ? $existingStockOrderNo 
            : ((!empty($originalStockOrderNo) && $originalStockOrderNo !== 'Not Allocated' && $originalStockOrderNo !== '0') 
                ? $originalStockOrderNo 
                : $allocateStockOrderNo);

        $allocatedBedForRsa = $allocatedBedRaw;
        if (empty($allocatedBedForRsa) || $allocatedBedForRsa === '[]' || $allocatedBedForRsa === 'null') {
            $defaultTrayCount = (int)ceil($intAllocQty / 126);
            $allocatedBedForRsa = json_encode([[
                "polyhouse_id"   => "",
                "polyhouse_name" => "Main Polyhouse",
                "bed_name"       => "Bed",
                "tray_count"     => $defaultTrayCount > 0 ? $defaultTrayCount : 20,
                "plants"         => $intAllocQty > 0 ? $intAllocQty : 2000
            ]]);
        } else {
            $decBeds = json_decode($allocatedBedForRsa, true);
            if (is_array($decBeds) && !empty($decBeds) && $intAllocQty > 0) {
                $sumBedPlants = 0;
                foreach ($decBeds as $bEntry) {
                    $sumBedPlants += intval($bEntry['plants'] ?? 0);
                }
                if ($sumBedPlants !== $intAllocQty) {
                    echo json_encode([
                        "status" => "error",
                        "message" => "Allocation failed: Allocated quantity ({$intAllocQty} plants) does not match total plants in allocated beds ({$sumBedPlants} plants). Available bed capacity is insufficient. Please correct the bed selection."
                    ]);
                    exit;
                }
            }
        }
        $polyhouseForRsa = resolvePolyhouseName($krfconn, $allocateStockOrderNo, $allocatedBedForRsa, '');
        if (!empty($polyhouseForRsa) && !empty($allocatedBedForRsa)) {
            $dec = json_decode($allocatedBedForRsa, true);
            if (is_array($dec) && !empty($dec)) {
                $updatedDec = false;
                foreach ($dec as &$bedEntry) {
                    if (empty($bedEntry['polyhouse_name']) || in_array($bedEntry['polyhouse_name'], ['Main Polyhouse', 'N/A'])) {
                        $bedEntry['polyhouse_name'] = $polyhouseForRsa;
                        $updatedDec = true;
                    }
                }
                unset($bedEntry);
                if ($updatedDec) {
                    $allocatedBedForRsa = json_encode($dec);
                }
            }
        }

        // Mark existing active krf_readystock_allocation rows for Order A with tedit = '1' and teditremark
        $existingRsaA = [];
        $exRsaAStmt = $krfconn->prepare("
            SELECT id, stockorder_no, allocate_stockorder_no, allocate_ordqty, polyhouse, psono, clientid, productid, productname
            FROM krf_readystock_allocation
            WHERE (
                ((psono = ? AND ? != '') AND (productid = ? OR productname = ?))
                OR ((clientid = ? AND ? != '') AND (productid = ? OR productname = ?) AND (psono IS NULL OR psono = '' OR psono = ?))
            )
            AND LOWER(IFNULL(status, '')) != 'cancel'
            AND (tedit IS NULL OR tedit = '')
        ");
        if ($exRsaAStmt) {
            $exRsaAStmt->bind_param("sssssssss", 
                $psono, $psono, $productId, $productName,
                $clientId, $clientId, $productId, $productName, $psono
            );
            $exRsaAStmt->execute();
            $exRsaARes = $exRsaAStmt->get_result();
            while ($rRow = $exRsaARes->fetch_assoc()) {
                $existingRsaA[] = $rRow;
            }
            $exRsaAStmt->close();
        }

        $updArchRsaA = $krfconn->prepare("
            UPDATE krf_readystock_allocation
            SET tedit = '1',
                status = 'cancel',
                teditremark = ?,
                updated_at = NOW(),
                updated_by = ?
            WHERE id = ?
        ");
        foreach ($existingRsaA as $exRsa) {
            $exBatch = !empty($exRsa['allocate_stockorder_no']) ? $exRsa['allocate_stockorder_no'] : $exRsa['stockorder_no'];
            $exQty = intval($exRsa['allocate_ordqty']);
            if ($exBatch === $allocateStockOrderNo) {
                $rsaAuditRemark = "Allocation quantity modified from " . number_format($exQty) . " to " . number_format($intAllocQty) . " on " . date('d-M-Y H:i:s') . " by " . $loginid;
            } else {
                $rsaAuditRemark = "Re-allocated from " . $exBatch . " (" . number_format($exQty) . ") to " . $allocateStockOrderNo . " (" . number_format($intAllocQty) . ") on " . date('d-M-Y H:i:s') . " by " . $loginid;
            }
            if (!empty($remark)) {
                $rsaAuditRemark .= " | Remark: " . $remark;
            }
            $rsaAuditRemark = substr($rsaAuditRemark, 0, 490);
            if ($updArchRsaA) {
                $updArchRsaA->bind_param("ssi", $rsaAuditRemark, $loginid, $exRsa['id']);
                $updArchRsaA->execute();
            }
        }
        if ($updArchRsaA) $updArchRsaA->close();

        $rsaSql = "
            INSERT INTO krf_readystock_allocation (
                projectid,
                psono,
                stockorder_no,
                allocate_stockorder_no,
                clientid,
                productid,
                productname,
                allocate_ordqty,
                healthyplant,
                unhealthyplant,
                isshifted,
                percentextrashifting,
                shiftingqty,
                allocatedbed,
                polyhouse,
                created_at,
                created_by,
                updated_at,
                updated_by,
                status,
                tedit,
                teditremark
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?,
                NOW(), ?, NOW(), ?, 'allocated', NULL, NULL
            )
        ";
        $rsaStmt = $krfconn->prepare($rsaSql);
        $rsaStmt->bind_param(
            "issssssiiiiiissss",
            $projectid,
            $psono,
            $stockOrderForRsaA,
            $allocateStockOrderNo,
            $clientId,
            $productId,
            $productName,
            $intAllocQty,
            $healthyPlant,
            $unhealthyPlant,
            $isShiftedInt,
            $percentExtraShiftingInt,
            $shiftingQty,
            $allocatedBedForRsa,
            $polyhouseForRsa,
            $loginid,
            $loginid
        );
        $rsaStmt->execute();

        // ----------------------------------------------------
        // 4. ORDER B: Handle Mutual Swap OR Release
        // ----------------------------------------------------
        $allocStockForB = '';
        $orderB_clientName = '';
        $orderB_client = '';
        if ($isMutualSwap && $orderB) {
            $orderB_soiId = intval($orderB['soi_id']);
            $orderB_psono = trim($orderB['psono'] ?? '');
            $orderB_sono = trim($orderB['sono'] ?? '');
            $orderB_client = trim($orderB['clientid'] ?? '');
            $orderB_clientName = trim($orderB['clientname'] ?? $orderB_client);
            $orderB_prodId = trim(strval($orderB['product_id'] ?? $productId));
            $orderB_prodName = trim($orderB['product_name'] ?? $productName);
            $orderB_origStock = trim($orderB['orig_stock'] ?? $allocateStockOrderNo);
            $orderB_qty = intval($orderB['quantity'] ?? $intAllocQty);
            $orderB_projectid = intval($orderB['projectid'] ?? $projectid);
            if ($orderB_projectid <= 0) $orderB_projectid = $projectid;

            // Order B gets allocated Order A's stock order ($stockOrderForRsaA)
            $allocStockForB = $stockOrderForRsaA;
            $allocPsonoForB = $psono;

            // 4.1 Update or Insert krf_productionorder_consumed for Order B
            $chkBStmt = $krfconn->prepare("SELECT id, sono, remark FROM krf_productionorder_consumed WHERE psono = ? AND product_id = ? AND IFNULL(tedit, '') = '' LIMIT 1");
            $chkBStmt->bind_param("ss", $orderB_psono, $orderB_prodId);
            $chkBStmt->execute();
            $chkBRes = $chkBStmt->get_result();

            $bRemark = "Swapped with {$clientNameA} ({$sono}) [Batch {$allocStockForB}]";
            $bZero = 0;
            $bOne = 1;

            if ($bRow = $chkBRes->fetch_assoc()) {
                $bPocId = $bRow['id'];
                $targetSonoB = !empty(trim($bRow['sono'] ?? '')) ? trim($bRow['sono']) : $orderB_sono;
                $existingBRemark = trim($bRow['remark'] ?? '');
                $combinedBRemark = !empty($existingBRemark) ? ($existingBRemark . " [{$bRemark}]") : "[{$bRemark}]";

                $updBPoc = "
                    UPDATE krf_productionorder_consumed 
                    SET allocated_stockorderno = ?,
                        allocated_psono = ?,
                        sono = ?,
                        consumed_qty = ?,
                        status = 'allocated',
                        confirmstatus = 'Confirmed',
                        remark = ?,
                        allocate_healthyplant = ?,
                        allocate_unhealthyplant = 0,
                        isshifted = 1,
                        percentextrashifting = 0,
                        extraqtyforshifting = 0,
                        allocatedby = ?,
                        allocatedat = NOW(),
                        updated_at = NOW()
                    WHERE id = ?
                ";
                $ubStmt = $krfconn->prepare($updBPoc);
                if (!$ubStmt) {
                    throw new Exception("Failed to prepare updBPoc in krf_productionorder_consumed: " . $krfconn->error);
                }
                $orderBQtyDec = floatval($orderB_qty);
                $ubStmt->bind_param(
                    "sssdsisi",
                    $allocStockForB,
                    $allocPsonoForB,
                    $targetSonoB,
                    $orderBQtyDec,
                    $combinedBRemark,
                    $orderB_qty,
                    $loginid,
                    $bPocId
                );
                $ubStmt->execute();
                $ubStmt->close();
            } else {
                $insBPoc = "
                    INSERT INTO krf_productionorder_consumed 
                    (psono, sono, stockorderno, allocated_stockorderno, allocated_psono, product_id, product_name, consumed_qty, wastage_qty, projectid, created_by, created_at, confirmstatus, remark, allocate_healthyplant, allocate_unhealthyplant, isshifted, percentextrashifting, extraqtyforshifting, status, allocatedby, allocatedat, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0.000, ?, ?, NOW(), 'Confirmed', ?, ?, 0, 1, 0, 0, 'allocated', ?, NOW(), NOW())
                ";
                $ibStmt = $krfconn->prepare($insBPoc);
                $orderBQtyDec = floatval($orderB_qty);
                $ibStmt->bind_param(
                    "sssssssdisisi",
                    $orderB_psono,
                    $orderB_sono,
                    $orderB_origStock,
                    $allocStockForB,
                    $allocPsonoForB,
                    $orderB_prodId,
                    $orderB_prodName,
                    $orderBQtyDec,
                    $orderB_projectid,
                    $loginid,
                    $bRemark,
                    $orderB_qty,
                    $loginid
                );
                $ibStmt->execute();
                $ibStmt->close();
            }
            $chkBStmt->close();

            // 4.2 Update krf_sales_order_items for Order B (preserve quantity intact)
            if ($orderB_soiId > 0) {
                $updBSoi = "
                    UPDATE krf_sales_order_items soi
                    SET soi.reservedqty = ?,
                        soi.allocate_ordqty = ?,
                        soi.balancereservedqty = GREATEST(0, CAST(IFNULL(soi.quantity, 0) AS SIGNED) - ?),
                        soi.healthyplant = ?,
                        soi.unhealthyplant = 0,
                        soi.isshifted = 'yes',
                        soi.percentextrashifting = 0,
                        soi.shiftingqty = 0,
                        soi.status = CASE 
                            WHEN ? >= IFNULL(soi.quantity, 0) THEN 'Allocated'
                            WHEN ? > 0 THEN 'Partial Allocated'
                            ELSE 'Confirmed'
                        END,
                        soi.ammendedby = ?,
                        soi.updateamended = NOW()
                    WHERE soi.id = ?
                ";
                $ubSoiStmt = $krfconn->prepare($updBSoi);
                if ($ubSoiStmt) {
                    $ubSoiStmt->bind_param("iiiiiiisi", $orderB_qty, $orderB_qty, $orderB_qty, $orderB_qty, $orderB_qty, $orderB_qty, $loginid, $orderB_soiId);
                    $ubSoiStmt->execute();
                    $ubSoiStmt->close();
                }
            }

            // 4.2b Update krf_sales_order for Order B (Allocated if all items allocated, PartialAllocated if some remain unallocated)
            if (!empty($orderB_psono) || !empty($orderB_sono)) {
                $hasUnallocB = false;
                $chkBStmt = $krfconn->prepare("
                    SELECT COUNT(*) AS unalloc_count
                    FROM krf_sales_order_items
                    WHERE ((psono = ? AND ? != '') OR (sono = ? AND ? != ''))
                      AND id != ?
                      AND (allocate_ordqty IS NULL OR allocate_ordqty = 0)
                      AND LOWER(IFNULL(status, '')) NOT IN ('allocated', 'dispatched', 'delivered', 'completed', 'cancelled')
                ");
                if ($chkBStmt) {
                    $chkBStmt->bind_param("ssssi", $orderB_psono, $orderB_psono, $orderB_sono, $orderB_sono, $orderB_soiId);
                    $chkBStmt->execute();
                    $bRow = $chkBStmt->get_result()->fetch_assoc();
                    if ($bRow && intval($bRow['unalloc_count'] ?? 0) > 0) {
                        $hasUnallocB = true;
                    }
                    $chkBStmt->close();
                }
                $newSoStatusB = $hasUnallocB ? 'PartialAllocated' : 'Allocated';

                $updSoB = "
                    UPDATE krf_sales_order
                    SET status = ?,
                        updated_at = NOW(),
                        updated_by = ?
                    WHERE ((psono = ? AND ? != '') OR (sono = ? AND ? != ''))
                      AND LOWER(IFNULL(status, '')) NOT IN ('delivered', 'completed', 'dispatched')
                ";
                $soBStmt = $krfconn->prepare($updSoB);
                if ($soBStmt) {
                    $soBStmt->bind_param("ssssss", $newSoStatusB, $loginid, $orderB_psono, $orderB_psono, $orderB_sono, $orderB_sono);
                    $soBStmt->execute();
                    $soBStmt->close();
                }
            }

            // 4.3 Archive Order B's existing active RSA rows and insert new record
            $existingRsaB = [];
            $exRsaBStmt = $krfconn->prepare("
                SELECT id, stockorder_no, allocate_stockorder_no, allocate_ordqty, polyhouse, psono, clientid, productid, productname
                FROM krf_readystock_allocation
                WHERE (
                    ((psono = ? AND ? != '') AND (productid = ? OR productname = ?))
                    OR ((clientid = ? AND ? != '') AND (productid = ? OR productname = ?) AND (psono IS NULL OR psono = '' OR psono = ?))
                )
                AND LOWER(IFNULL(status, '')) != 'cancel'
                AND (tedit IS NULL OR tedit = '')
            ");
            if ($exRsaBStmt) {
                $exRsaBStmt->bind_param("sssssssss", 
                    $orderB_psono, $orderB_psono, $orderB_prodId, $orderB_prodName,
                    $orderB_client, $orderB_client, $orderB_prodId, $orderB_prodName, $orderB_psono
                );
                $exRsaBStmt->execute();
                $exRsaBRes = $exRsaBStmt->get_result();
                while ($rRowB = $exRsaBRes->fetch_assoc()) {
                    $existingRsaB[] = $rRowB;
                }
                $exRsaBStmt->close();
            }

            $updArchRsaB = $krfconn->prepare("
                UPDATE krf_readystock_allocation
                SET tedit = '1',
                    status = 'cancel',
                    teditremark = ?,
                    updated_at = NOW(),
                    updated_by = ?
                WHERE id = ?
            ");
            foreach ($existingRsaB as $exRsa) {
                $rsaAuditRemarkB = "Swapped with {$clientNameA} ({$sono}) [Batch {$allocStockForB}] on " . date('d-M-Y H:i:s') . " by " . $loginid;
                $rsaAuditRemarkB = substr($rsaAuditRemarkB, 0, 490);
                if ($updArchRsaB) {
                    $updArchRsaB->bind_param("ssi", $rsaAuditRemarkB, $loginid, $exRsa['id']);
                    $updArchRsaB->execute();
                }
            }
            if ($updArchRsaB) $updArchRsaB->close();

            $orderB_polyhouse = resolvePolyhouseName($krfconn, $allocStockForB, '', '');
            $orderB_allocatedbed = json_encode([[
                "polyhouse_id"   => "",
                "polyhouse_name" => $orderB_polyhouse,
                "bed_name"       => "Bed",
                "tray_count"     => (int)ceil($orderB_qty / 100),
                "plants"         => $orderB_qty
            ]]);

            $rsaStmt->bind_param(
                "issssssiiiiiissss",
                $orderB_projectid,
                $orderB_psono,
                $orderB_origStock,
                $allocStockForB,
                $orderB_client,
                $orderB_prodId,
                $orderB_prodName,
                $orderB_qty,
                $orderB_qty,
                $bZero,
                $bOne,
                $bZero,
                $bZero,
                $orderB_allocatedbed,
                $orderB_polyhouse,
                $loginid,
                $loginid
            );
            $rsaStmt->execute();

        } else {
            // If not a mutual swap, and Order A previously held another client's batch, restore that batch back to its original owner
            if (!empty($previousAllocatedStock) && $previousAllocatedStock !== $allocateStockOrderNo && $previousAllocatedStock !== $orderA_origStock) {
                $chkPrevOwner = $krfconn->prepare("
                    SELECT pso.psono, pso.clientid, pso.productid, pso.product_name, pso.quantity, pso.projectid,
                           soi.id AS soi_id, soi.sono
                    FROM krf_production_stockorder pso
                    LEFT JOIN krf_sales_order_items soi ON (soi.psono = pso.psono OR soi.stockorder_no = pso.stockorder_no)
                    WHERE pso.stockorder_no = ? AND (pso.psono != ? OR pso.psono IS NULL OR ? = '')
                    ORDER BY soi.id DESC LIMIT 1
                ");
                $chkPrevOwner->bind_param("sss", $previousAllocatedStock, $psono, $psono);
                $chkPrevOwner->execute();
                $prevOwner = $chkPrevOwner->get_result()->fetch_assoc();
                $chkPrevOwner->close();

                if ($prevOwner && !empty($prevOwner['psono'])) {
                    $prevPsono = $prevOwner['psono'];
                    $prevQty = intval($prevOwner['quantity'] ?? $intAllocQty);
                    $prevQtyDec = floatval($prevQty);
                    $prevProjId = intval($prevOwner['projectid'] ?? $projectid);
                    if ($prevProjId <= 0) $prevProjId = $projectid;
                    $prevProdId = $prevOwner['productid'];
                    $prevProdName = $prevOwner['product_name'] ?? $productName;
                    $prevClient = $prevOwner['clientid'];
                    $prevSoiId = intval($prevOwner['soi_id'] ?? 0);
                    $bZero = 0;
                    $bOne = 1;

                    // Restore Order C's poc row
                    $updPrevPoc = "
                        UPDATE krf_productionorder_consumed 
                        SET allocated_stockorderno = ?,
                            allocated_psono = ?,
                            status = 'allocated',
                            confirmstatus = 'Confirmed',
                            consumed_qty = ?,
                            remark = CONCAT(CONVERT(IFNULL(remark,'') USING utf8mb4), ' [Restored batch ', ?, ']'),
                            allocatedby = ?,
                            allocatedat = NOW(),
                            updated_at = NOW()
                        WHERE (psono = ? OR stockorderno = ?) AND product_id = ?
                    ";
                    $uppStmt = $krfconn->prepare($updPrevPoc);
                    if (!$uppStmt) {
                        throw new Exception("Failed to prepare updPrevPoc in krf_productionorder_consumed: " . $krfconn->error);
                    }
                    $uppStmt->bind_param("ssdsssss", 
                        $previousAllocatedStock, $prevPsono, $prevQtyDec, $previousAllocatedStock, $loginid,
                        $prevPsono, $previousAllocatedStock, $prevProdId
                    );
                    $uppStmt->execute();
                    $uppStmt->close();

                    // Restore Order C's soi row (preserve quantity intact)
                    if ($prevSoiId > 0) {
                        $updPrevSoi = "
                            UPDATE krf_sales_order_items soi
                            SET soi.reservedqty = ?,
                                soi.allocate_ordqty = ?,
                                soi.balancereservedqty = GREATEST(0, CAST(IFNULL(soi.quantity, 0) AS SIGNED) - ?),
                                soi.healthyplant = ?,
                                soi.isshifted = 'yes',
                                soi.status = CASE 
                                    WHEN ? >= IFNULL(soi.quantity, 0) THEN 'Allocated'
                                    WHEN ? > 0 THEN 'Partial Allocated'
                                    ELSE 'Confirmed'
                                END,
                                soi.updateamended = NOW(),
                                soi.ammendedby = ?
                            WHERE soi.id = ?
                        ";
                        $upSoiStmt = $krfconn->prepare($updPrevSoi);
                        if ($upSoiStmt) {
                            $upSoiStmt->bind_param("iiiiiiisi", $prevQty, $prevQty, $prevQty, $prevQty, $prevQty, $prevQty, $loginid, $prevSoiId);
                            $upSoiStmt->execute();
                            $upSoiStmt->close();
                        }
                    }

                    // Archive Order C's existing active row
                    $krfconn->query("
                        UPDATE krf_readystock_allocation 
                        SET tedit = '1',
                            status = 'cancel',
                            teditremark = 'Restored batch {$previousAllocatedStock} on " . date('d-M-Y H:i:s') . " by " . mysqli_real_escape_string($krfconn, $loginid) . "',
                            updated_at = NOW(),
                            updated_by = '" . mysqli_real_escape_string($krfconn, $loginid) . "'
                        WHERE (psono = '" . mysqli_real_escape_string($krfconn, $prevPsono) . "' OR stockorder_no = '" . mysqli_real_escape_string($krfconn, $previousAllocatedStock) . "')
                          AND productid = '" . mysqli_real_escape_string($krfconn, $prevProdId) . "'
                          AND LOWER(IFNULL(status, '')) != 'cancel'
                          AND (tedit IS NULL OR tedit = '')
                    ");

                    // Restore Order C's rsa row
                    $prevPolyhouse = resolvePolyhouseName($krfconn, $previousAllocatedStock, '', '');
                    $prevAllocBed = json_encode([[
                        "polyhouse_id"   => "",
                        "polyhouse_name" => $prevPolyhouse,
                        "bed_name"       => "Bed",
                        "tray_count"     => (int)ceil($prevQty / 100),
                        "plants"         => $prevQty
                    ]]);
                    $rsaStmt->bind_param(
                        "issssssiiiiiissss",
                        $prevProjId,
                        $prevPsono,
                        $previousAllocatedStock,
                        $previousAllocatedStock,
                        $prevClient,
                        $prevProdId,
                        $prevProdName,
                        $prevQty,
                        $prevQty,
                        $bZero,
                        $bOne,
                        $bZero,
                        $bZero,
                        $prevAllocBed,
                        $prevPolyhouse,
                        $loginid,
                        $loginid
                    );
                    $rsaStmt->execute();
                }
            }

            if (!empty($allocateStockOrderNo) && $allocateStockOrderNo !== 'Not Allocated') {
                $findOtherSql = "
                    SELECT poc.id, poc.psono, poc.sono, poc.stockorderno, poc.product_id, poc.consumed_qty 
                    FROM krf_productionorder_consumed poc
                    WHERE (poc.allocated_stockorderno = ? OR (IFNULL(poc.allocated_stockorderno, '') = '' AND poc.stockorderno = ?))
                      AND (poc.psono != ? OR poc.psono IS NULL OR ? = '')
                      AND (poc.sono != ? OR poc.sono IS NULL OR ? = '')
                      AND LOWER(IFNULL(poc.status, '')) != 'dispatched'
                      AND IFNULL(poc.tedit, '') = ''
                ";
                $findOtherStmt = $krfconn->prepare($findOtherSql);
                $findOtherStmt->bind_param("ssssss", $allocateStockOrderNo, $allocateStockOrderNo, $psono, $psono, $sono, $sono);
                $findOtherStmt->execute();
                $otherRes = $findOtherStmt->get_result();
                while ($oRow = $otherRes->fetch_assoc()) {
                    $otherPocId = $oRow['id'];
                    $oStock = trim($oRow['stockorderno'] ?? '');
                    $oProdId = trim($oRow['product_id'] ?? '');

                    if (!empty($oStock) && $oStock !== $allocateStockOrderNo) {
                        // Restore other order to its own sown batch
                        $updOtherPoc = "
                            UPDATE krf_productionorder_consumed 
                            SET allocated_stockorderno = stockorderno,
                                allocated_psono = psono,
                                status = 'allocated',
                                confirmstatus = 'Confirmed',
                                remark = CONCAT(CONVERT(IFNULL(remark,'') USING utf8mb4), ' [Batch ', ?, ' returned to ', ?, ', restored to own batch]'),
                                updated_at = NOW()
                            WHERE id = ?
                        ";
                        $uopStmt = $krfconn->prepare($updOtherPoc);
                        if (!$uopStmt) {
                            throw new Exception("Failed to prepare updOtherPoc (restore) in krf_productionorder_consumed: " . $krfconn->error);
                        }
                        $uopStmt->bind_param("ssi", $allocateStockOrderNo, $sono, $otherPocId);
                        $uopStmt->execute();
                        $uopStmt->close();

                        // Also update krf_readystock_allocation for other order
                        $updOtherRsa = "
                            UPDATE krf_readystock_allocation 
                            SET allocate_stockorder_no = stockorder_no,
                                status = 'allocated',
                                updated_at = NOW(),
                                updated_by = ?
                            WHERE stockorder_no = ? AND (productid = ? OR ? = '')
                        ";
                        $uorStmt = $krfconn->prepare($updOtherRsa);
                        if ($uorStmt) {
                            $uorStmt->bind_param("ssss", $loginid, $oStock, $oProdId, $oProdId);
                            $uorStmt->execute();
                            $uorStmt->close();
                        }
                    } else {
                        // If $oStock === $allocateStockOrderNo, this order was originally sown for this batch.
                        // Since production stock orders can be large batches shared across multiple orders,
                        // do not wipe or reset other orders that legitimately share this production batch.
                    }
                }
                $findOtherStmt->close();
            }
        }

        $rsaStmt->close();

        // Revert any previously loaded trays for Order A that belong to old/replaced batches back to Readytosale
        autoUndoPreviousLoadedTraysOnReallocation($krfconn, $psono, [$allocateStockOrderNo], $productId, $productName, $loginid);

        // Synchronize active dispatch invoice items to the newly allocated batch
        syncInvoiceItemsOnReallocation($krfconn, $psono, [$allocateStockOrderNo], $productId, $productName, $loginid);

        if ($isMutualSwap && $orderB && !empty($orderB_psono) && !empty($allocStockForB)) {
            autoUndoPreviousLoadedTraysOnReallocation($krfconn, $orderB_psono, [$allocStockForB], $orderB_prodId, $orderB_prodName, $loginid);
            syncInvoiceItemsOnReallocation($krfconn, $orderB_psono, [$allocStockForB], $orderB_prodId, $orderB_prodName, $loginid);
        }

        $krfconn->commit();

        $successMsg = $isMutualSwap 
            ? "Mutual re-allocation saved successfully! Batch '{$allocateStockOrderNo}' allocated to {$clientNameA} ({$clientId}) and Batch '{$allocStockForB}' allocated to {$orderB_clientName} ({$orderB_client}). Both records updated in krf_readystock_allocation."
            : "Production stock order allocation saved successfully.";

        echo json_encode(["status" => "success", "message" => $successMsg]);
    } catch (Throwable $e) {
        $krfconn->rollback();
        echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
    }
    exit;

} else if ($action == "MarkFinalDispatched") {

    $projectid = intval($_POST['projectid'] ?? 0);
    $soiId = intval($_POST['soi_id'] ?? 0);
    $stockOrderNo = trim($_POST['stockorder_no'] ?? '');
    $dispatchQty = intval($_POST['dispatch_qty'] ?? 0);
    $loginid = trim($_POST['loginid'] ?? $_SESSION['loginname'] ?? 'Admin');
    $postIsOpenStock = isset($_POST['is_open_stock']) ? intval($_POST['is_open_stock']) : -1;
    $postIsOwnSown = isset($_POST['is_own_sown']) ? intval($_POST['is_own_sown']) : -1;
    $skipPlantDeduction = isset($_POST['skip_plant_deduction']) ? intval($_POST['skip_plant_deduction']) : 0;

    if ($soiId <= 0) {
        echo json_encode(["status" => "error", "message" => "Invalid Sales Order Item."]);
        exit;
    }

    $krfconn->begin_transaction();
    try {
        // 1. Fetch sales order item and parent sales order details
        $soiFetch = $krfconn->prepare("
            SELECT soi.*, 
                   so.companyid, so.subcompanyid, so.clientid AS so_clientid, so.clientname,
                   so.mobileno AS so_mobileno, so.emailid AS so_emailid, 
                   so.address AS invoicing_address, so.delivery_address AS so_delivery_address, 
                   so.districtname AS so_districtname, so.statename AS so_statename
            FROM krf_sales_order_items soi
            LEFT JOIN krf_sales_order so ON (so.psono = soi.psono OR so.sono = soi.sono)
            WHERE soi.id = ? 
            LIMIT 1
        ");
        if (!$soiFetch) {
            throw new Exception("Failed to prepare soiFetch query: " . $krfconn->error);
        }
        $soiFetch->bind_param("i", $soiId);
        $soiFetch->execute();
        $soiData = $soiFetch->get_result()->fetch_assoc();
        $soiFetch->close();

        if (!$soiData) {
            throw new Exception("Sales Order Item with ID #{$soiId} not found.");
        }

        $psono = trim($soiData['psono'] ?? '');
        $sono = trim($soiData['sono'] ?? '');

        // Guard against dispatching cancelled product or whole cancelled order
        $checkCancelled = false;
        $cancelMsg = "";
        $itemStatusVal = strtolower(trim($soiData['status'] ?? ''));
        $pId = intval($soiData['product_id'] ?? 0);
        $pName = trim($soiData['product_name'] ?? '');

        // 1. Check whole order status
        if (!empty($psono)) {
            $escPso = $krfconn->real_escape_string($psono);
            $qSoH = $krfconn->query("SELECT status FROM krf_sales_order WHERE psono = '$escPso' LIMIT 1");
            if ($qSoH && $rSoH = $qSoH->fetch_assoc()) {
                if (in_array(strtolower(trim($rSoH['status'] ?? '')), ['cancelled', 'cancel'])) {
                    $checkCancelled = true;
                    $cancelMsg = "Sales Order is cancelled. Dispatch cannot be performed.";
                }
            }
            if (!$checkCancelled) {
                $qPsoH = $krfconn->query("SELECT status FROM krf_provisional_sales_order WHERE psono = '$escPso' LIMIT 1");
                if ($qPsoH && $rPsoH = $qPsoH->fetch_assoc()) {
                    if (in_array(strtolower(trim($rPsoH['status'] ?? '')), ['cancelled', 'cancel'])) {
                        $checkCancelled = true;
                        $cancelMsg = "Provisional Order is cancelled. Dispatch cannot be performed.";
                    }
                }
            }
        }

        // 2. Check this specific product item
        if (!$checkCancelled) {
            if (in_array($itemStatusVal, ['cancelled', 'cancel'])) {
                $checkCancelled = true;
                $cancelMsg = "Product ($pName) is cancelled in Sales Order. Dispatch is stopped.";
            } else if (!empty($psono)) {
                $escPso = $krfconn->real_escape_string($psono);
                $escPName = $krfconn->real_escape_string($pName);
                $qPsoi = $krfconn->query("
                    SELECT product_name FROM krf_provisional_sales_order_items 
                    WHERE psono = '$escPso' 
                      AND (product_id = '$pId' OR product_name = '$escPName') 
                      AND LOWER(TRIM(IFNULL(status, ''))) IN ('cancelled', 'cancel') 
                    LIMIT 1
                ");
                if ($qPsoi && $qPsoi->num_rows > 0) {
                    $checkCancelled = true;
                    $rC = $qPsoi->fetch_assoc();
                    $cancelMsg = "Product (" . ($rC['product_name'] ?? $pName) . ") is cancelled in Provisional Sales Order. Dispatch is stopped.";
                }
            }
        }
        if ($checkCancelled) {
            throw new Exception($cancelMsg ?: "This product is cancelled. Dispatch cannot be performed.");
        }

        $productId = trim(strval($soiData['product_id'] ?? ''));
        $productName = trim($soiData['product_name'] ?? '');
        $itemQty = intval($soiData['quantity'] ?? 0);
        $effDispatchQty = ($dispatchQty > 0) ? $dispatchQty : $itemQty;
        $effStockOrder = !empty($stockOrderNo) ? $stockOrderNo : trim($soiData['stockorder_no'] ?? '');
        $clientId = trim($soiData['clientid'] ?? $soiData['so_clientid'] ?? '');
        $clientName = trim($soiData['clientname'] ?? 'Customer');
        $effProjectId = ($projectid > 0) ? $projectid : intval($soiData['projectid'] ?? 1);
        $companyId = intval($soiData['companyid'] ?? 1);
        $subCompanyId = intval($soiData['subcompanyid'] ?? 1);

        // Step 1: Update krf_sales_order_items
        $updSoi = "
            UPDATE krf_sales_order_items 
            SET status = 'Dispatched',
                dispatchedqty = ?,
                ammendedby = ?,
                updateamended = NOW()
            WHERE id = ?
        ";
        $sStmt = $krfconn->prepare($updSoi);
        if (!$sStmt) {
            throw new Exception("Failed to prepare updSoi query: " . $krfconn->error);
        }
        $sStmt->bind_param("isi", $effDispatchQty, $loginid, $soiId);
        $sStmt->execute();
        $sStmt->close();

        // Step 2: Update krf_productionorder_consumed
        $updPoc = "
            UPDATE krf_productionorder_consumed 
            SET status = 'dispatched',
                dispatchedqty = ?,
                dispatchedby = ?,
                dispatchedat = NOW(),
                updated_at = NOW()
            WHERE ((psono = ? AND product_id = ?) 
               OR (sono = ? AND stockorderno = ?))
              AND IFNULL(tedit, '') = ''
        ";
        $pocStmt = $krfconn->prepare($updPoc);
        if (!$pocStmt) {
            throw new Exception("Failed to prepare updPoc query: " . $krfconn->error);
        }
        $pocStmt->bind_param("isssss", $effDispatchQty, $loginid, $psono, $productId, $sono, $effStockOrder);
        $pocStmt->execute();
        $pocStmt->close();

        // Step 3: Update krf_readystock_allocation
        $updRsa = "
            UPDATE krf_readystock_allocation 
            SET status = 'dispatched',
                updated_by = ?,
                updated_at = NOW()
            WHERE (stockorder_no = ? OR allocate_stockorder_no = ?) 
              AND productid = ? 
              AND (projectid = ? OR ? = 0)
        ";
        $rsaDisStmt = $krfconn->prepare($updRsa);
        if (!$rsaDisStmt) {
            throw new Exception("Failed to prepare updRsa query: " . $krfconn->error);
        }
        $rsaDisStmt->bind_param("ssssii", $loginid, $effStockOrder, $effStockOrder, $productId, $effProjectId, $effProjectId);
        $rsaDisStmt->execute();
        $rsaDisStmt->close();

        // Step 4 & 5: Determine Batch Type: Own Sown vs OpenStock / Trader
        $isOwnSown = false;
        $isOpenStock = false;

        if (!empty($effStockOrder) && $effStockOrder !== 'Not Allocated') {
            $psoFetch = $krfconn->prepare("
                SELECT pso.psono, pso.clientid, pso.quantity, pso.dispatchqty, pso.remarks, pso.status, pso.workingstatus,
                       IFNULL(cm.is_trader, 0) AS is_trader
                FROM krf_production_stockorder pso
                LEFT JOIN krf_client_master cm ON (cm.client_id = pso.clientid AND (cm.tedit IS NULL OR cm.tedit = '' OR cm.tedit = '0'))
                WHERE pso.stockorder_no = ? AND (pso.projectid = ? OR ? = 0)
                LIMIT 1
            ");
            if ($psoFetch) {
                $psoFetch->bind_param("sii", $effStockOrder, $effProjectId, $effProjectId);
                $psoFetch->execute();
                $psoRow = $psoFetch->get_result()->fetch_assoc();
                $psoFetch->close();

                if ($psoRow) {
                    $psoPsono = trim($psoRow['psono'] ?? '');
                    $psoClientId = trim($psoRow['clientid'] ?? '');
                    $psoIsTrader = intval($psoRow['is_trader'] ?? 0);
                    $psoRemarks = trim($psoRow['remarks'] ?? '');

                    if (
                        $psoClientId === 'C00001' || 
                        $psoIsTrader === 1 || 
                        stripos($psoRemarks, 'Ready Stock') !== false ||
                        ($psoPsono === $effStockOrder && strpos($effStockOrder, 'RSO-') === 0)
                    ) {
                        $isOpenStock = true;
                    } else if (
                        (!empty($psoPsono) && !empty($psono) && $psoPsono === $psono) ||
                        (!empty($psoClientId) && !empty($clientId) && $psoClientId === $clientId && $psoClientId !== 'C00001')
                    ) {
                        $isOwnSown = true;
                    }
                }
            }

            if ($postIsOwnSown === 1) {
                $isOwnSown = true;
                $isOpenStock = false;
            } else if ($postIsOpenStock === 1) {
                $isOpenStock = true;
                $isOwnSown = false;
            }

            if ($isOwnSown) {
                // Case A: Client's Own Production Stock Order
                // Update krf_production_stockorder status to 'Dispatched' & workingstatus to 'Final Dispatch'
                $updPsoOwn = "
                    UPDATE krf_production_stockorder 
                    SET status = 'Dispatched',
                        workingstatus = 'Final Dispatch',
                        dispatchqty = IFNULL(dispatchqty, 0) + ?,
                        updated_at = NOW()
                    WHERE stockorder_no = ? AND (projectid = ? OR ? = 0)
                ";
                $psoOwnStmt = $krfconn->prepare($updPsoOwn);
                if ($psoOwnStmt) {
                    $psoOwnStmt->bind_param("isii", $effDispatchQty, $effStockOrder, $effProjectId, $effProjectId);
                    $psoOwnStmt->execute();
                    $psoOwnStmt->close();
                }

                // Update ALL active trays in krf_polyhouse_plant_details (only if plant deduction is not skipped)
                if ($skipPlantDeduction !== 1) {
                    $updPlantsOwn = "
                        UPDATE krf_polyhouse_plant_details 
                        SET status = 'Final Dispatch',
                            workingstatus = 'Final Dispatch',
                            dispatchqty = qtypertray,
                            dispatchby = ?,
                            dispatchat = NOW(),
                            dispd_agst_clientid = ?,
                            dispd_agst_psono = ?,
                            dispd_agst_stockorderno = stockorder_no,
                            updated_at = NOW(),
                            updated_by = ?
                        WHERE stockorder_no = ? AND (projectid = ? OR ? = 0)
                          AND LOWER(IFNULL(status, '')) IN ('readytosale', 'openstock', 'polyhouse', 'allocated', 'readytodispatch', 'readytoallocated', 'loading')
                    ";
                    $pOwnStmt = $krfconn->prepare($updPlantsOwn);
                    if ($pOwnStmt) {
                        $pOwnStmt->bind_param("sssssii", $loginid, $clientId, $psono, $loginid, $effStockOrder, $effProjectId, $effProjectId);
                        $pOwnStmt->execute();
                        $pOwnStmt->close();
                    }
                }

            } else {
                // Case B: Allocated from OpenStock (or Trader is_trader=1 / Swapped)
                if ($skipPlantDeduction !== 1) {
                    // Fetch active trays in batch to select only required number of trays
                    $traySql = "
                        SELECT polyhouseplantid, qtypertray 
                        FROM krf_polyhouse_plant_details 
                        WHERE stockorder_no = ? AND (projectid = ? OR ? = 0)
                          AND LOWER(IFNULL(status, '')) IN ('readytosale', 'openstock', 'polyhouse', 'allocated', 'readytodispatch', 'readytoallocated', 'loading')
                        ORDER BY polyhouseplantid ASC
                    ";
                    $trayStmt = $krfconn->prepare($traySql);
                    if ($trayStmt) {
                        $trayStmt->bind_param("sii", $effStockOrder, $effProjectId, $effProjectId);
                        $trayStmt->execute();
                        $trayRes = $trayStmt->get_result();

                        $accumulatedPlants = 0;
                        $targetTrayIds = [];
                        while ($tr = $trayRes->fetch_assoc()) {
                            $pid = intval($tr['polyhouseplantid']);
                            $trayCap = intval($tr['qtypertray'] ?? 126);
                            if ($trayCap <= 0) $trayCap = 126;
                            $targetTrayIds[] = $pid;
                            $accumulatedPlants += $trayCap;
                            if ($accumulatedPlants >= $effDispatchQty) {
                                break;
                            }
                        }
                        $trayStmt->close();

                        // Update ONLY those specific selected trays to Final Dispatch
                        if (!empty($targetTrayIds)) {
                            $inPlaceholders = implode(',', array_fill(0, count($targetTrayIds), '?'));
                            $updTargetTrays = "
                                UPDATE krf_polyhouse_plant_details 
                                SET status = 'Final Dispatch',
                                    workingstatus = 'Final Dispatch',
                                    dispatchqty = qtypertray,
                                    dispatchby = ?,
                                    dispatchat = NOW(),
                                    dispd_agst_clientid = ?,
                                    dispd_agst_psono = ?,
                                    dispd_agst_stockorderno = stockorder_no,
                                    updated_at = NOW(),
                                    updated_by = ?
                                WHERE polyhouseplantid IN ($inPlaceholders)
                            ";
                            $tStmt = $krfconn->prepare($updTargetTrays);
                            if ($tStmt) {
                                $types = "ssss" . str_repeat("i", count($targetTrayIds));
                                $bindParams = array_merge([$loginid, $clientId, $psono, $loginid], $targetTrayIds);
                                $tStmt->bind_param($types, ...$bindParams);
                                $tStmt->execute();
                                $tStmt->close();
                            }
                        }
                    }
                }

                // Update krf_production_stockorder: dispatchqty += qty
                // Set workingstatus = 'Final Dispatch' only if fully consumed (dispatchqty >= quantity)
                $updPsoOpen = "
                    UPDATE krf_production_stockorder 
                    SET dispatchqty = IFNULL(dispatchqty, 0) + ?,
                        workingstatus = IF(IFNULL(dispatchqty, 0) + ? >= quantity, 'Final Dispatch', workingstatus),
                        updated_at = NOW()
                    WHERE stockorder_no = ? AND (projectid = ? OR ? = 0)
                ";
                $psoOpenStmt = $krfconn->prepare($updPsoOpen);
                if ($psoOpenStmt) {
                    $psoOpenStmt->bind_param("iisii", $effDispatchQty, $effDispatchQty, $effStockOrder, $effProjectId, $effProjectId);
                    $psoOpenStmt->execute();
                    $psoOpenStmt->close();
                }
            }
        }

        // Check if all items in krf_sales_order_items for this order are dispatched
        $chkRemaining = $krfconn->prepare("
            SELECT COUNT(*) AS pending_count 
            FROM krf_sales_order_items 
            WHERE ((psono = ? AND ? != '') OR (sono = ? AND ? != ''))
              AND LOWER(IFNULL(status, '')) != 'dispatched'
        ");
        if ($chkRemaining) {
            $chkRemaining->bind_param("ssss", $psono, $psono, $sono, $sono);
            $chkRemaining->execute();
            $remRow = $chkRemaining->get_result()->fetch_assoc();
            $chkRemaining->close();

            if ($remRow && intval($remRow['pending_count'] ?? 0) === 0) {
                $updSoHeader = $krfconn->prepare("
                    UPDATE krf_sales_order 
                    SET status = 'FullDispatch',
                        updated_at = NOW(),
                        updated_by = ?
                    WHERE (psono = ? AND ? != '') OR (sono = ? AND ? != '')
                ");
                if ($updSoHeader) {
                    $updSoHeader->bind_param("sssss", $loginid, $psono, $psono, $sono, $sono);
                    $updSoHeader->execute();
                    $updSoHeader->close();
                }
            }
        }

        // Step 6: Insert / Update sales_invoice and sales_invoice_items
        $chkInv = $krfconn->prepare("
            SELECT id, invoiceno 
            FROM sales_invoice 
            WHERE ((psono = ? AND ? != '') OR (sono = ? AND ? != '')) 
            ORDER BY id DESC LIMIT 1
        ");
        if (!$chkInv) {
            throw new Exception("Failed to prepare chkInv query: " . $krfconn->error);
        }
        $chkInv->bind_param("ssss", $psono, $psono, $sono, $sono);
        $chkInv->execute();
        $invRow = $chkInv->get_result()->fetch_assoc();
        $chkInv->close();

        if ($invRow && !empty($invRow['id'])) {
            $invId = intval($invRow['id']);
            $existingInvNo = $invRow['invoiceno'];

            // Update sales_invoice header status to 'dispatched'
            $updInv = $krfconn->prepare("
                UPDATE sales_invoice 
                SET status = 'dispatched',
                    updated_by = ?,
                    updated_at = NOW() 
                WHERE id = ?
            ");
            if (!$updInv) {
                throw new Exception("Failed to prepare updInv query: " . $krfconn->error);
            }
            $updInv->bind_param("si", $loginid, $invId);
            $updInv->execute();
            $updInv->close();

            // Check if this item already has a line item in sales_invoice_items
            $chkSii = $krfconn->prepare("SELECT id FROM sales_invoice_items WHERE invoice_id = ? AND original_soi_id = ? LIMIT 1");
            if (!$chkSii) {
                throw new Exception("Failed to prepare chkSii query: " . $krfconn->error);
            }
            $chkSii->bind_param("ii", $invId, $soiId);
            $chkSii->execute();
            $siiRow = $chkSii->get_result()->fetch_assoc();
            $chkSii->close();

            if ($siiRow && !empty($siiRow['id'])) {
                $updInvItem = $krfconn->prepare("
                    UPDATE sales_invoice_items 
                    SET status = 'dispatched',
                        total_dispatch_plants = ?,
                        updated_at = NOW() 
                    WHERE id = ?
                ");
                if (!$updInvItem) {
                    throw new Exception("Failed to prepare updInvItem query: " . $krfconn->error);
                }
                $updInvItem->bind_param("ii", $effDispatchQty, $siiRow['id']);
                $updInvItem->execute();
                $updInvItem->close();
            } else {
                // Insert line item into existing invoice
                $rate = floatval($soiData['rate'] ?? 0);
                $taxableAmount = floatval($soiData['taxableamount'] ?? ($rate * $effDispatchQty));
                $taxAmount = floatval($soiData['tax_amount'] ?? 0);
                $amount = floatval($soiData['amount'] ?? ($taxableAmount + $taxAmount));
                $cgstPct = floatval($soiData['cgst_percent'] ?? 0);
                $cgstAmount = floatval($soiData['cgst_amount'] ?? 0);
                $sgstPct = floatval($soiData['sgst_percent'] ?? 0);
                $sgstAmount = floatval($soiData['sgst_amount'] ?? 0);
                $igstPct = floatval($soiData['igst_percent'] ?? 0);
                $igstAmount = floatval($soiData['igst_amount'] ?? 0);
                $discPct = floatval($soiData['discount_percent'] ?? 0);
                $discAmt = floatval($soiData['discount_amount'] ?? 0);
                $invDate = date('Y-m-d');

                $insItem = $krfconn->prepare("
                    INSERT INTO sales_invoice_items (
                        invoice_id, projectid, clientid, psono, sono, invoiceno, original_soi_id,
                        stockorder_no, productid, productname, quantity, healthyqty, total_dispatch_plants,
                        rate, discount_percent, discount_amount, taxableamount,
                        cgst_percent, cgst_amount, sgst_percent, sgst_amount, igst_percent, igst_amount,
                        tax_amount, amount, deliverydate, status, created_at
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, 'dispatched', NOW()
                    )
                ");
                if (!$insItem) {
                    throw new Exception("Failed to prepare insItem query: " . $krfconn->error);
                }
                $insItem->bind_param(
                    "iissssisssiiidddddddddddds",
                    $invId, $effProjectId, $clientId, $psono, $sono, $existingInvNo, $soiId,
                    $effStockOrder, $productId, $productName, $effDispatchQty, $effDispatchQty, $effDispatchQty,
                    $rate, $discPct, $discAmt, $taxableAmount,
                    $cgstPct, $cgstAmount, $sgstPct, $sgstAmount, $igstPct, $igstAmount,
                    $taxAmount, $amount, $invDate
                );
                $insItem->execute();
                $insItem->close();
            }
        } else {
            // Generate new dispatch invoice number
            $newInvNo = function_exists('generateDispatchInvoiceNo')
                ? generateDispatchInvoiceNo($krfconn, $psono, $sono)
                : ("KF/" . date('y') . '-' . (date('y') + 1) . '/' . time());

            $invSerialNo = 1;
            if (preg_match('/\/(\d+)$/', $newInvNo, $sm)) {
                $invSerialNo = intval($sm[1]);
            }

            $invDate = date('Y-m-d');
            $rate = floatval($soiData['rate'] ?? 0);
            $taxableAmount = floatval($soiData['taxableamount'] ?? ($rate * $effDispatchQty));
            $taxAmount = floatval($soiData['tax_amount'] ?? 0);
            $amount = floatval($soiData['amount'] ?? ($taxableAmount + $taxAmount));
            $cgstPct = floatval($soiData['cgst_percent'] ?? 0);
            $cgstAmount = floatval($soiData['cgst_amount'] ?? 0);
            $sgstPct = floatval($soiData['sgst_percent'] ?? 0);
            $sgstAmount = floatval($soiData['sgst_amount'] ?? 0);
            $igstPct = floatval($soiData['igst_percent'] ?? 0);
            $igstAmount = floatval($soiData['igst_amount'] ?? 0);
            $discPct = floatval($soiData['discount_percent'] ?? 0);
            $discAmt = floatval($soiData['discount_amount'] ?? 0);
            $invAddress = !empty($soiData['invoicing_address']) ? $soiData['invoicing_address'] : ($soiData['address'] ?? '');
            $delAddress = !empty($soiData['delivery_address']) ? $soiData['delivery_address'] : ($soiData['so_delivery_address'] ?? '');
            $district = !empty($soiData['districtname']) ? $soiData['districtname'] : ($soiData['so_districtname'] ?? '');
            $state = !empty($soiData['statename']) ? $soiData['statename'] : ($soiData['so_statename'] ?? '');
            $mobileNo = !empty($soiData['mobileno']) ? $soiData['mobileno'] : ($soiData['so_mobileno'] ?? '');
            $emailId = !empty($soiData['emailid']) ? $soiData['emailid'] : ($soiData['so_emailid'] ?? '');

            // Insert sales_invoice
            $insInv = $krfconn->prepare("
                INSERT INTO sales_invoice (
                    companyid, subcompanyid, projectid, psono, sono, invoicetype, invoiceno,
                    serialno, invoicedate, clientid, clientname, client_type, mobileno, emailid,
                    invoicing_address, delivery_address, districtname, statename,
                    taxableamount, cgst_amount, sgst_amount, igst_amount, tax_amount,
                    invoiceamount, payableamount, status, created_by, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?, 'dispatchinvoice', ?,
                    ?, ?, ?, ?, 'Farmer', ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, 'dispatched', ?, NOW()
                )
            ");
            if (!$insInv) {
                throw new Exception("Failed to prepare insInv query: " . $krfconn->error);
            }
            $insInv->bind_param(
                "iiisssisssssssssddddddds",
                $companyId, $subCompanyId, $effProjectId, $psono, $sono, $newInvNo,
                $invSerialNo, $invDate, $clientId, $clientName, $mobileNo, $emailId,
                $invAddress, $delAddress, $district, $state,
                $taxableAmount, $cgstAmount, $sgstAmount, $igstAmount, $taxAmount,
                $amount, $amount, $loginid
            );
            $insInv->execute();
            $newInvId = $insInv->insert_id;
            $insInv->close();

            // Insert sales_invoice_items
            $insItem = $krfconn->prepare("
                INSERT INTO sales_invoice_items (
                    invoice_id, projectid, clientid, psono, sono, invoiceno, original_soi_id,
                    stockorder_no, productid, productname, quantity, healthyqty, total_dispatch_plants,
                    rate, discount_percent, discount_amount, taxableamount,
                    cgst_percent, cgst_amount, sgst_percent, sgst_amount, igst_percent, igst_amount,
                    tax_amount, amount, deliverydate, status, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, 'dispatched', NOW()
                )
            ");
            if (!$insItem) {
                throw new Exception("Failed to prepare insItem query: " . $krfconn->error);
            }
            $insItem->bind_param(
                "iissssisssiiidddddddddddds",
                $newInvId, $effProjectId, $clientId, $psono, $sono, $newInvNo, $soiId,
                $effStockOrder, $productId, $productName, $effDispatchQty, $effDispatchQty, $effDispatchQty,
                $rate, $discPct, $discAmt, $taxableAmount,
                $cgstPct, $cgstAmount, $sgstPct, $sgstAmount, $igstPct, $igstAmount,
                $taxAmount, $amount, $invDate
            );
            $insItem->execute();
            $insItem->close();
        }

        $krfconn->commit();
        $successMsg = ($skipPlantDeduction === 1)
            ? "Order item marked as dispatched (without plant deduction) successfully."
            : "Order item marked as final dispatched successfully.";
        echo json_encode(["status" => "success", "message" => $successMsg]);
    } catch (Throwable $e) {
        $krfconn->rollback();
        echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
    }
    exit;

} else if ($action == "GetDispatchInvoiceDetails") {
    $projectid = intval($_POST['projectid'] ?? 0);
    $soiId = intval($_POST['soi_id'] ?? 0);
    $sono = trim($_POST['sono'] ?? '');
    $psono = trim($_POST['psono'] ?? '');

    if ($soiId <= 0 && empty($sono) && empty($psono)) {
        echo json_encode(["status" => "error", "message" => "Invalid Sales Order Item identifier."]);
        exit;
    }

    // 1. Fetch Sales Order header
    $soSql = "
        SELECT so.* 
        FROM krf_sales_order so
        WHERE (so.sono = ? AND ? != '') 
           OR (so.psono = ? AND ? != '') 
           OR so.sono = (SELECT soi.sono FROM krf_sales_order_items soi WHERE soi.id = ?)
        ORDER BY CASE WHEN so.invoicetype = 'sale' THEN 1 ELSE 2 END, so.id ASC
        LIMIT 1
    ";
    $soStmt = $krfconn->prepare($soSql);
    $soStmt->bind_param("ssssi", $sono, $sono, $psono, $psono, $soiId);
    $soStmt->execute();
    $orderData = $soStmt->get_result()->fetch_assoc();

    if (!$orderData) {
        $soiCheck = $krfconn->prepare("SELECT sono, psono FROM krf_sales_order_items WHERE id = ? LIMIT 1");
        $soiCheck->bind_param("i", $soiId);
        $soiCheck->execute();
        $chkRow = $soiCheck->get_result()->fetch_assoc();
        if ($chkRow) {
            $sono = $chkRow['sono'];
            $psono = $chkRow['psono'];
            $soStmt2 = $krfconn->prepare("SELECT * FROM krf_sales_order WHERE sono = ? OR psono = ? LIMIT 1");
            $soStmt2->bind_param("ss", $sono, $psono);
            $soStmt2->execute();
            $orderData = $soStmt2->get_result()->fetch_assoc();
        }
    }

    if (!$orderData) {
        echo json_encode(["status" => "error", "message" => "Sales order details not found."]);
        exit;
    }

    $effectiveSono = $orderData['sono'] ?? $sono;
    $effectivePsono = $orderData['psono'] ?? $psono;
    $clientId = trim($orderData['clientid'] ?? '');

    // Fallback: If clientid is empty in sales order header, retrieve from krf_provisional_sales_order
    if (empty($clientId) && !empty($effectivePsono)) {
        $psoCheck = $krfconn->prepare("SELECT clientid, clientname, mobileno, emailid, address, statename, districtname FROM krf_provisional_sales_order WHERE psono = ? LIMIT 1");
        if ($psoCheck) {
            $psoCheck->bind_param("s", $effectivePsono);
            $psoCheck->execute();
            $psoRow = $psoCheck->get_result()->fetch_assoc();
            $psoCheck->close();
            if ($psoRow) {
                if (!empty($psoRow['clientid'])) {
                    $clientId = trim($psoRow['clientid']);
                    $orderData['clientid'] = $clientId;
                }
                if (empty($orderData['clientname']) && !empty($psoRow['clientname'])) {
                    $orderData['clientname'] = $psoRow['clientname'];
                }
                if (empty($orderData['mobileno']) && !empty($psoRow['mobileno'])) {
                    $orderData['mobileno'] = $psoRow['mobileno'];
                }
                if (empty($orderData['emailid']) && !empty($psoRow['emailid'])) {
                    $orderData['emailid'] = $psoRow['emailid'];
                }
                if (empty($orderData['address']) && !empty($psoRow['address'])) {
                    $orderData['address'] = $psoRow['address'];
                }
            }
        }
    }

    // 2. Fetch Client Info from krf_client_master & Determine Customer Type
    $clientInfo = null;
    $customerType = 'Farmer';
    if (!empty($clientId)) {
        $cmStmt = $krfconn->prepare("
            SELECT client_id, client_name, is_dealer, is_trader, 
                   phones AS mobileno, client_email AS emailid, 
                   invoicing_address, statename, districtname, cityname, tahseel 
            FROM krf_client_master 
            WHERE (tedit IS NULL OR tedit = '' OR tedit = '0') AND client_id = ? 
            LIMIT 1
        ");
        if ($cmStmt) {
            $cmStmt->bind_param("s", $clientId);
            $cmStmt->execute();
            $clientInfo = $cmStmt->get_result()->fetch_assoc();
            $cmStmt->close();
        }
    }

    // Fallback 1: If client not found by client_id, attempt lookup by client_name
    if (!$clientInfo && !empty($orderData['clientname'])) {
        $cmNameStmt = $krfconn->prepare("
            SELECT client_id, client_name, is_dealer, is_trader, 
                   phones AS mobileno, client_email AS emailid, 
                   invoicing_address, statename, districtname, cityname, tahseel 
            FROM krf_client_master 
            WHERE (tedit IS NULL OR tedit = '' OR tedit = '0') AND LCASE(TRIM(client_name)) = LCASE(TRIM(?)) 
            LIMIT 1
        ");
        if ($cmNameStmt) {
            $cmNameStmt->bind_param("s", $orderData['clientname']);
            $cmNameStmt->execute();
            $clientInfo = $cmNameStmt->get_result()->fetch_assoc();
            $cmNameStmt->close();
        }
    }

    // Fallback 2: If still not found, attempt lookup by mobile number
    if (!$clientInfo && !empty($orderData['mobileno'])) {
        $cleanPhone = preg_replace('/[^0-9]/', '', $orderData['mobileno']);
        if (!empty($cleanPhone)) {
            $cmPhoneStmt = $krfconn->prepare("
                SELECT client_id, client_name, is_dealer, is_trader, 
                       phones AS mobileno, client_email AS emailid, 
                       invoicing_address, statename, districtname, cityname, tahseel 
                FROM krf_client_master 
                WHERE (tedit IS NULL OR tedit = '' OR tedit = '0') AND phones LIKE ? 
                LIMIT 1
            ");
            if ($cmPhoneStmt) {
                $phoneParam = "%" . $cleanPhone . "%";
                $cmPhoneStmt->bind_param("s", $phoneParam);
                $cmPhoneStmt->execute();
                $clientInfo = $cmPhoneStmt->get_result()->fetch_assoc();
                $cmPhoneStmt->close();
            }
        }
    }

    // Determine Customer Type
    if ($clientInfo) {
        if (!empty($clientInfo['is_dealer']) && ($clientInfo['is_dealer'] == 1 || $clientInfo['is_dealer'] === '1')) {
            $customerType = 'Dealer';
        } else if (!empty($clientInfo['is_trader']) && (strcasecmp($clientInfo['is_trader'], 'Trader') === 0 || $clientInfo['is_trader'] == 1 || $clientInfo['is_trader'] === '1')) {
            $customerType = 'Trader';
        }
    }

    // Resolve Invoicing Address strictly from Client Master, with fallback to city/dist/state, then order address
    $resolvedInvAddr = '';
    if ($clientInfo) {
        $resolvedInvAddr = trim($clientInfo['invoicing_address'] ?? '');
        if (empty($resolvedInvAddr)) {
            $parts = array_filter([
                $clientInfo['tahseel'] ?? '',
                $clientInfo['cityname'] ?? '',
                $clientInfo['districtname'] ?? '',
                $clientInfo['statename'] ?? ''
            ]);
            $resolvedInvAddr = implode(', ', $parts);
        }
    }
    if (empty($resolvedInvAddr)) {
        $resolvedInvAddr = trim($orderData['address'] ?? '');
    }
    if (empty($resolvedInvAddr) && !empty($orderData['delivery_address'])) {
        $resolvedInvAddr = trim($orderData['delivery_address']);
    }

    // Normalize whitespace and remove unprintable control characters
    $resolvedInvAddr = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $resolvedInvAddr);
    $resolvedInvAddr = trim(preg_replace('/\s+/u', ' ', $resolvedInvAddr));

    if (!$clientInfo) {
        $clientInfo = [
            'client_id' => $clientId,
            'client_name' => $orderData['clientname'] ?? '',
            'mobileno' => $orderData['mobileno'] ?? '',
            'emailid' => $orderData['emailid'] ?? '',
            'invoicing_address' => $resolvedInvAddr,
            'resolved_invoicing_address' => $resolvedInvAddr,
            'statename' => $orderData['statename'] ?? '',
            'districtname' => $orderData['districtname'] ?? '',
            'cityname' => $orderData['cityname'] ?? '',
            'is_dealer' => 0,
            'is_trader' => ''
        ];
    } else {
        $clientInfo['invoicing_address'] = !empty($resolvedInvAddr) ? $resolvedInvAddr : ($clientInfo['invoicing_address'] ?? '');
        $clientInfo['resolved_invoicing_address'] = $resolvedInvAddr;
        if (empty($clientInfo['mobileno']) && !empty($orderData['mobileno'])) {
            $clientInfo['mobileno'] = $orderData['mobileno'];
        }
        if (empty($clientInfo['emailid']) && !empty($orderData['emailid'])) {
            $clientInfo['emailid'] = $orderData['emailid'];
        }
        if (empty($clientInfo['client_name']) && !empty($orderData['clientname'])) {
            $clientInfo['client_name'] = $orderData['clientname'];
        }
    }

    if (empty($orderData['address']) && !empty($resolvedInvAddr)) {
        $orderData['address'] = $resolvedInvAddr;
    }
    $orderData['invoicing_address'] = $resolvedInvAddr;

    // Check if a specific past invoice was explicitly requested for editing
    $explicitEditInvNo = trim($_POST['edit_invoiceno'] ?? '');
    if (!empty($explicitEditInvNo)) {
        $savedDinvNo = $explicitEditInvNo;
        $orderData['dinvoiceno'] = $explicitEditInvNo;
        $savedDinvDate = null;
        $chkExplicitSi = $krfconn->prepare("SELECT invoicedate FROM sales_invoice WHERE invoiceno = ? LIMIT 1");
        if ($chkExplicitSi) {
            $chkExplicitSi->bind_param("s", $explicitEditInvNo);
            $chkExplicitSi->execute();
            $esiR = $chkExplicitSi->get_result()->fetch_assoc();
            $chkExplicitSi->close();
            if ($esiR && !empty($esiR['invoicedate'])) {
                $savedDinvDate = date('Y-m-d', strtotime($esiR['invoicedate']));
            }
        }
    } else {
        // Check if this order already has a saved dispatch invoice
        $savedDinvNo = trim($orderData['dinvoiceno'] ?? '');
        $savedDinvDate = !empty($orderData['invoicedate']) ? date('Y-m-d', strtotime($orderData['invoicedate'])) : null;
        if (empty($savedDinvNo) && !empty($effectivePsono)) {
            $chkInvStmt = $krfconn->prepare("SELECT invoiceno, invoicedate FROM krf_sales_order_items WHERE (psono = ? OR sono = ? OR sono LIKE CONCAT(?, '-%')) AND invoiceno IS NOT NULL AND invoiceno != '' ORDER BY id DESC LIMIT 1");
            if ($chkInvStmt) {
                $chkInvStmt->bind_param("sss", $effectivePsono, $effectiveSono, $effectiveSono);
                $chkInvStmt->execute();
                $invR = $chkInvStmt->get_result()->fetch_assoc();
                $chkInvStmt->close();
                if ($invR && !empty($invR['invoiceno'])) {
                    $savedDinvNo = trim($invR['invoiceno']);
                    $orderData['dinvoiceno'] = $savedDinvNo;
                    if (!empty($invR['invoicedate'])) {
                        $savedDinvDate = date('Y-m-d', strtotime($invR['invoicedate']));
                    }
                }
            }
        }

        // Also check sales_invoice if saved invoice number is still empty
        if (empty($savedDinvNo) && !empty($effectivePsono)) {
            $chkSi = $krfconn->prepare("SELECT invoiceno, invoicedate FROM sales_invoice WHERE (psono = ? OR sono = ? OR sono LIKE CONCAT(?, '-%')) AND status = 'active' ORDER BY id DESC LIMIT 1");
            if ($chkSi) {
                $chkSi->bind_param("sss", $effectivePsono, $effectiveSono, $effectiveSono);
                $chkSi->execute();
                $siR = $chkSi->get_result()->fetch_assoc();
                $chkSi->close();
                if ($siR && !empty($siR['invoiceno'])) {
                    $savedDinvNo = trim($siR['invoiceno']);
                    $orderData['dinvoiceno'] = $savedDinvNo;
                    if (!empty($siR['invoicedate'])) {
                        $savedDinvDate = date('Y-m-d', strtotime($siR['invoicedate']));
                    }
                }
            }
        }
    }

    // 2.9. Fetch ALL past generated invoices for this provisional sales order
    $pastInvoices = [];
    $pastSiSql = "
        SELECT 
            si.id,
            si.invoiceno,
            si.invoicedate,
            si.invoiceamount,
            si.payableamount,
            si.pickuptype,
            si.vehicleno,
            si.drivername,
            si.status,
            si.created_at,
            COALESCE(SUM(CASE WHEN (sii.productid != 'BOX_PACKING' AND sii.is_box_packing = 0) THEN sii.quantity ELSE 0 END), 0) AS total_plants,
            COALESCE(SUM(CASE WHEN (sii.productid = 'BOX_PACKING' OR sii.is_box_packing = 1) THEN sii.quantity ELSE 0 END), 0) AS box_count
        FROM sales_invoice si
        LEFT JOIN sales_invoice_items sii ON sii.invoice_id = si.id AND LOWER(IFNULL(sii.status, '')) != 'cancelled'
        WHERE (si.psono = ? OR si.sono = ? OR si.sono LIKE CONCAT(?, '-%'))
          AND LOWER(IFNULL(si.status, '')) != 'cancelled'
          AND (si.tedit IS NULL OR si.tedit = '' OR si.tedit = '0')
        GROUP BY si.id
        ORDER BY si.id DESC
    ";
    $pastSiStmt = $krfconn->prepare($pastSiSql);
    if ($pastSiStmt) {
        $pastSiStmt->bind_param("sss", $effectivePsono, $effectiveSono, $effectiveSono);
        $pastSiStmt->execute();
        $pastSiRes = $pastSiStmt->get_result();
        while ($psi = $pastSiRes->fetch_assoc()) {
            $psi['formatted_date'] = !empty($psi['invoicedate']) ? date('d-M-Y', strtotime($psi['invoicedate'])) : (!empty($psi['created_at']) ? date('d-M-Y', strtotime($psi['created_at'])) : '-');
            $pastInvoices[] = $psi;
        }
        $pastSiStmt->close();
    }

    // Query saved Box Packing line item from krf_sales_order_items
    $savedBoxPacking = [
        'enabled' => false,
        'box_count' => 0,
        'box_rate' => 100.00,
        'box_total' => 0.00,
        'box_capacity' => 500,
        'product_name' => ''
    ];
    $bpStmt = $krfconn->prepare("
        SELECT id, product_id, product_name, quantity, rate, taxableamount, amount, invoiceno 
        FROM krf_sales_order_items 
        WHERE (psono = ? OR sono = ? OR sono LIKE CONCAT(?, '-%'))
          AND (product_id = 'BOX_PACKING' OR product_sku = 'BOX-PACKING' OR product_name LIKE '%Box Packing%')
        ORDER BY id DESC LIMIT 1
    ");
    if ($bpStmt) {
        $bpStmt->bind_param("sss", $effectivePsono, $effectiveSono, $effectiveSono);
        $bpStmt->execute();
        $bpRow = $bpStmt->get_result()->fetch_assoc();
        $bpStmt->close();
        if ($bpRow && intval($bpRow['quantity'] ?? 0) > 0) {
            $bCount = intval($bpRow['quantity']);
            $bRate = floatval($bpRow['rate'] ?? 100.00);
            $bTotal = floatval($bpRow['taxableamount'] ?? $bpRow['amount'] ?? ($bCount * $bRate));
            $savedBoxPacking = [
                'enabled' => true,
                'box_count' => $bCount,
                'box_rate' => $bRate,
                'box_total' => $bTotal,
                'box_capacity' => 500,
                'product_name' => $bpRow['product_name'] ?? "Box Packing Charges ({$bCount} Boxes @ Rs {$bRate})"
            ];
        }
    }

    // 3. Fetch items in this Provisional Sales Order that have active allocations (status: allocated, readytoload, or readytodispatch) in krf_readystock_allocation
    $itemsSql = "
        SELECT soi.* 
        FROM krf_sales_order_items soi
        INNER JOIN krf_readystock_allocation rsa ON (
            ((rsa.psono = soi.psono AND soi.psono != '') OR (rsa.clientid = soi.clientid AND (soi.psono IS NULL OR soi.psono = '')))
            AND (rsa.productid = soi.product_id OR rsa.productname = soi.product_name)
            AND (rsa.tedit IS NULL OR rsa.tedit = '' OR rsa.tedit = '0')
            AND LOWER(IFNULL(rsa.status, '')) IN ('allocated', 'readytoload', 'readytodispatch')
        )
        LEFT JOIN krf_productionorder_consumed poc ON (
            poc.psono = soi.psono
            AND (poc.product_id = soi.product_id OR poc.product_name = soi.product_name)
            AND (poc.tedit IS NULL OR poc.tedit = '' OR poc.tedit = '0')
            AND LOWER(IFNULL(poc.confirmstatus, '')) != 'cancelled'
            AND LOWER(IFNULL(poc.status, '')) NOT IN ('cancelled', 'cancel')
        )
        WHERE ((soi.sono = ? AND ? != '') 
           OR (soi.psono = ? AND ? != '' AND (soi.invoiceno IS NULL OR soi.invoiceno = '')))
          AND (soi.tedit IS NULL OR soi.tedit = '' OR soi.tedit = '0')
          AND LOWER(IFNULL(soi.status, '')) NOT IN ('cancelled', 'cancel')
          AND soi.product_id != 'BOX_PACKING'
          AND soi.product_sku != 'BOX-PACKING'
          AND soi.product_name NOT LIKE '%Box Packing%'
        GROUP BY soi.id
        ORDER BY soi.id ASC
    ";
    $iStmt = $krfconn->prepare($itemsSql);
    $iStmt->bind_param("ssss", $effectiveSono, $effectiveSono, $effectivePsono, $effectivePsono);
    $iStmt->execute();
    $iRes = $iStmt->get_result();
    $items = [];
    $totalOriginalOrderQty = 0;
    $totalOriginalAllocQty = 0;
    $hasGrafted = false;
    $hasNongrafted = false;

    while ($item = $iRes->fetch_assoc()) {
        $pId = $item['product_id'];
        $stk = $item['stockorder_no'];
        $rawName = $item['product_name'] ?? '';
        $isGrafted = (stripos($rawName, 'grafted') !== false && stripos($rawName, 'nongrafted') === false && stripos($rawName, 'non-grafted') === false);
        if ($isGrafted) $hasGrafted = true; else $hasNongrafted = true;

        $item['clean_product_name'] = preg_replace('/^(NonGrafted|Non-Grafted)\s*[-:]?\s*/i', '', $rawName);
        $item['is_grafted'] = $isGrafted ? 1 : 0;
        $item['plant_type'] = $isGrafted ? 'Grafted' : 'Nongrafted';
        $item['formatted_delivery_date'] = !empty($item['deliverydate']) ? date('d-M-Y', strtotime($item['deliverydate'])) : '-';

        // Fetch consumed info strictly for THIS item's product / stock order from krf_productionorder_consumed
        $pocStmt = $krfconn->prepare("
            SELECT original_stockorder_no, original_orderqty, allocate_healthyplant, allocate_unhealthyplant, 
                   isshifted, percentextrashifting, extraqtyforshifting, consumed_qty, stockorderno, 
                   allocated_stockorderno, allocated_psono, product_name, product_id, status, remark
            FROM krf_productionorder_consumed 
            WHERE ((psono = ? AND ? != '') OR (sono = ? AND ? != ''))
              AND (product_id = ? OR (? != '' AND (stockorderno = ? OR allocated_stockorderno = ?)))
              AND LOWER(IFNULL(confirmstatus, '')) != 'cancelled'
              AND LOWER(IFNULL(status, '')) NOT IN ('cancelled', 'cancel')
              AND IFNULL(tedit, '') = ''
            ORDER BY 
              CASE WHEN LOWER(IFNULL(status, '')) IN ('allocated', 'readytoload', 'loaded', 'dispatched') THEN 1 ELSE 2 END,
              CASE WHEN NULLIF(TRIM(allocated_stockorderno), '') IS NOT NULL AND allocated_stockorderno = ? THEN 1
                   WHEN NULLIF(TRIM(stockorderno), '') IS NOT NULL AND stockorderno = ? THEN 2
                   ELSE 3 END,
              id DESC
        ");
        $poc = null;
        $allocatedBatches = [];
        $allAllocatedStockNos = [];
        if ($pocStmt) {
            $pocStmt->bind_param("ssssssssss", $effectivePsono, $effectivePsono, $effectiveSono, $effectiveSono, $pId, $stk, $stk, $stk, $stk, $stk);
            $pocStmt->execute();
            $pocRes = $pocStmt->get_result();
            while ($pRow = $pocRes->fetch_assoc()) {
                $pStatus = strtolower(trim($pRow['status'] ?? ''));
                $isRowAllocated = in_array($pStatus, ['allocated', 'readytoload', 'loaded', 'dispatched']) || (!empty($pRow['allocated_stockorderno']) && $pRow['allocated_stockorderno'] !== 'Not Allocated' && $pRow['allocated_stockorderno'] !== '0');
                if ($isRowAllocated) {
                    if (!$poc) $poc = $pRow;
                    $bAlloc = trim($pRow['allocated_stockorderno'] ?: ($pRow['stockorderno'] ?: ''));
                    if (!empty($bAlloc) && $bAlloc !== 'Not Allocated' && $bAlloc !== '0') {
                        $allAllocatedStockNos[] = $bAlloc;
                        $allocatedBatches[] = [
                            'stockorder_no' => $bAlloc,
                            'allocated_qty' => floatval($pRow['consumed_qty'] ?? 0),
                            'consumed_qty'  => floatval($pRow['consumed_qty'] ?? 0),
                            'extra_qty'     => floatval($pRow['extraqtyforshifting'] ?? 0),
                            'extraqtyforshifting' => floatval($pRow['extraqtyforshifting'] ?? 0),
                            'psono'         => $pRow['allocated_psono'] ?? '',
                            'remark'        => $pRow['remark'] ?? ''
                        ];
                    }
                }
            }
            $pocStmt->close();
        }

        // Fetch allocation info strictly for this order/client AND this product from krf_readystock_allocation
        $effLookupStk = !empty($poc['allocated_stockorderno']) ? $poc['allocated_stockorderno'] : (!empty($poc['stockorderno']) ? $poc['stockorderno'] : $stk);
        $rsaTotalAlloc = 0;
        $rsaHealthyTotal = 0;
        $rsaUnhealthyTotal = 0;
        $rsaShiftingTotal = 0;
        $rsaDispatchedTotal = 0;
        $rsaAllocatedBatches = [];
        $rsaBedsList = [];
        $rsaRows = [];
        $rsaAllAllocStockNos = [];

        // STRICT MATCH: If psono is available, match by psono ONLY! If not, fallback to clientid.
        $rsaWhere = !empty($effectivePsono) 
            ? "psono = ? AND (productid = ? OR productname = ?)"
            : "clientid = ? AND (productid = ? OR productname = ?)";
        $rsaParam1 = !empty($effectivePsono) ? $effectivePsono : $clientId;

        $rsaStmt = $krfconn->prepare("
            SELECT id, stockorder_no, allocate_stockorder_no, allocate_ordqty, healthyplant, unhealthyplant, 
                   isshifted, percentextrashifting, shiftingqty, productname, productid, allocatedbed, status, psono, dispatchqty
            FROM krf_readystock_allocation
            WHERE {$rsaWhere}
              AND LOWER(IFNULL(status, '')) IN ('allocated', 'readytoload', 'readytodispatch')
              AND (tedit IS NULL OR tedit = '' OR tedit = '0')
            ORDER BY id ASC
        ");
        $rsa = null;
        if ($rsaStmt) {
            $rsaStmt->bind_param("sss", $rsaParam1, $pId, $rawName);
            $rsaStmt->execute();
            $rsaRes = $rsaStmt->get_result();
            while ($rRow = $rsaRes->fetch_assoc()) {
                $rsaRows[] = $rRow;
                $bAllocQty = intval($rRow['allocate_ordqty'] ?? 0);
                if ($bAllocQty > 0) {
                    $rsaTotalAlloc += $bAllocQty;
                    $rsaHealthyTotal += intval($rRow['healthyplant'] ?? $bAllocQty);
                    $rsaUnhealthyTotal += intval($rRow['unhealthyplant'] ?? 0);
                    $rsaShiftingTotal += intval($rRow['shiftingqty'] ?? 0);
                    $rsaDispatchedTotal += intval($rRow['dispatchqty'] ?? 0);
                    $bStk = trim($rRow['allocate_stockorder_no'] ?: ($rRow['stockorder_no'] ?: ''));
                    if (!empty($bStk) && $bStk !== 'Not Allocated' && $bStk !== '0') {
                        $rsaAllAllocStockNos[] = $bStk;
                        $rsaAllocatedBatches[] = [
                            'stockorder_no' => $bStk,
                            'allocated_qty' => $bAllocQty,
                            'consumed_qty'  => $bAllocQty,
                            'extra_qty'     => floatval($rRow['shiftingqty'] ?? 0),
                            'extraqtyforshifting' => floatval($rRow['shiftingqty'] ?? 0),
                            'psono'         => $effectivePsono,
                            'remark'        => ''
                        ];
                    }
                    if (!empty($rRow['allocatedbed'])) {
                        $rsaBedsList[] = $rRow['allocatedbed'];
                    }
                }
            }
            $rsaStmt->close();
        }
        $rsa = !empty($rsaRows) ? $rsaRows[0] : null;

        // If RSA has allocated batches, prefer RSA batches and stock order numbers!
        if (!empty($rsaAllocatedBatches)) {
            $allocatedBatches = $rsaAllocatedBatches;
            $allAllocatedStockNos = array_values(array_unique(array_merge($allAllocatedStockNos, $rsaAllAllocStockNos)));
        } elseif (empty($allocatedBatches) && !empty($rsa) && !empty($rsa['allocate_stockorder_no']) && $rsa['allocate_stockorder_no'] !== 'Not Allocated' && $rsa['allocate_stockorder_no'] !== '0') {
            $allAllocatedStockNos[] = trim($rsa['allocate_stockorder_no']);
            $allocatedBatches[] = [
                'stockorder_no' => trim($rsa['allocate_stockorder_no']),
                'allocated_qty' => floatval($rsa['allocate_ordqty'] ?? 0),
                'consumed_qty'  => floatval($rsa['allocate_ordqty'] ?? 0),
                'extra_qty'     => floatval($rsa['shiftingqty'] ?? 0),
                'extraqtyforshifting' => floatval($rsa['shiftingqty'] ?? 0),
                'psono'         => $effectivePsono,
                'remark'        => ''
            ];
        }

        // Determine Allocated Stock Order Number vs Original Sown Stock Order Number
        $allocatedStockNo = '';
        if (!empty($allAllocatedStockNos)) {
            $allocatedStockNo = implode(', ', array_unique($allAllocatedStockNos));
        } elseif (!empty($poc['allocated_stockorderno']) && $poc['allocated_stockorderno'] !== 'Not Allocated' && $poc['allocated_stockorderno'] !== '0') {
            $allocatedStockNo = trim($poc['allocated_stockorderno']);
        } elseif (!empty($rsa['allocate_stockorder_no']) && $rsa['allocate_stockorder_no'] !== 'Not Allocated' && $rsa['allocate_stockorder_no'] !== '0') {
            $allocatedStockNo = trim($rsa['allocate_stockorder_no']);
        } elseif (!empty($item['allocate_stockorder_no']) && $item['allocate_stockorder_no'] !== 'Not Allocated' && $item['allocate_stockorder_no'] !== '0') {
            $allocatedStockNo = trim($item['allocate_stockorder_no']);
        } elseif (!empty($poc['stockorderno']) && $poc['stockorderno'] !== 'Not Allocated' && $poc['stockorderno'] !== '0') {
            $allocatedStockNo = trim($poc['stockorderno']);
        } else {
            $allocatedStockNo = trim($stk);
        }

        $origStockNo = '';
        if (!empty($poc['original_stockorder_no']) && $poc['original_stockorder_no'] !== 'Not Allocated' && $poc['original_stockorder_no'] !== '0') {
            $origStockNo = trim($poc['original_stockorder_no']);
        } elseif (!empty($poc['stockorderno']) && $poc['stockorderno'] !== 'Not Allocated' && $poc['stockorderno'] !== '0') {
            $origStockNo = trim($poc['stockorderno']);
        } elseif (!empty($rsa['stockorder_no']) && $rsa['stockorder_no'] !== 'Not Allocated' && $rsa['stockorder_no'] !== '0') {
            $origStockNo = trim($rsa['stockorder_no']);
        } else {
            $origStockNo = trim($stk);
        }

        $isSwapped = (!empty($allocatedStockNo) && !empty($origStockNo) && $allocatedStockNo !== $origStockNo && $allocatedStockNo !== 'Not Allocated' && $origStockNo !== 'Not Allocated');

        // Query Total Batch Quantity & Product from karjeefarm.krf_production_stockorder for the allocated batch
        $psoBatchQty = 0;
        $psoBatchProdName = '';
        $psoBatchProdId = '';
        if (!empty($allocatedStockNo) && $allocatedStockNo !== 'Not Allocated' && $allocatedStockNo !== '0') {
            $firstStk = trim(explode(',', $allocatedStockNo)[0]);
            $psoStmt = $krfconn->prepare("SELECT quantity, expected_delivery_date, sowingdate, status, product_name, productid FROM krf_production_stockorder WHERE stockorder_no = ? LIMIT 1");
            if ($psoStmt) {
                $psoStmt->bind_param("s", $firstStk);
                $psoStmt->execute();
                $psoRow = $psoStmt->get_result()->fetch_assoc();
                if ($psoRow) {
                    $psoBatchQty = intval($psoRow['quantity'] ?? 0);
                    $psoBatchProdName = trim($psoRow['product_name'] ?? '');
                    $psoBatchProdId = trim($psoRow['productid'] ?? '');
                }
                $psoStmt->close();
            }
            if (empty($psoBatchProdName)) {
                $rtsProdStmt = $krfconn->prepare("SELECT productid, productname FROM krf_readytosale_stocks WHERE stockorder_no = ? LIMIT 1");
                if ($rtsProdStmt) {
                    $rtsProdStmt->bind_param("s", $firstStk);
                    $rtsProdStmt->execute();
                    $rtsProdRow = $rtsProdStmt->get_result()->fetch_assoc();
                    if ($rtsProdRow) {
                        $psoBatchProdName = trim($rtsProdRow['productname'] ?? '');
                        $psoBatchProdId = trim($rtsProdRow['productid'] ?? '');
                    }
                    $rtsProdStmt->close();
                }
            }
        }

        // Query Total Allocated Quantity across all orders in karjeefarm.krf_readystock_allocation for this allocated batch
        $totalBatchAllocated = 0;
        if (!empty($allocatedStockNo) && $allocatedStockNo !== 'Not Allocated' && $allocatedStockNo !== '0') {
            $rsaTotalStmt = $krfconn->prepare("SELECT SUM(allocate_ordqty) AS total_allocated FROM krf_readystock_allocation WHERE allocate_stockorder_no = ? AND LOWER(IFNULL(status, '')) != 'cancel' AND (tedit IS NULL OR tedit = '' OR tedit = '0')");
            if ($rsaTotalStmt) {
                $rsaTotalStmt->bind_param("s", $allocatedStockNo);
                $rsaTotalStmt->execute();
                $rsaTotalRow = $rsaTotalStmt->get_result()->fetch_assoc();
                if ($rsaTotalRow && !empty($rsaTotalRow['total_allocated'])) {
                    $totalBatchAllocated = intval($rsaTotalRow['total_allocated']);
                }
                $rsaTotalStmt->close();
            }
            // Fallback: If no allocation in krf_readystock_allocation (e.g. own-sown directly allocated via consumed table), check krf_productionorder_consumed
            if ($totalBatchAllocated === 0) {
                $pocTotalStmt = $krfconn->prepare("SELECT SUM(consumed_qty) AS total_consumed FROM krf_productionorder_consumed WHERE (allocated_stockorderno = ? OR (stockorderno = ? AND (allocated_stockorderno IS NULL OR allocated_stockorderno = '' OR allocated_stockorderno = 'Not Allocated'))) AND LOWER(IFNULL(status, '')) != 'cancel' AND IFNULL(tedit, '') = ''");
                if ($pocTotalStmt) {
                    $pocTotalStmt->bind_param("ss", $allocatedStockNo, $allocatedStockNo);
                    $pocTotalStmt->execute();
                    $pocTotalRow = $pocTotalStmt->get_result()->fetch_assoc();
                    if ($pocTotalRow && !empty($pocTotalRow['total_consumed'])) {
                        $totalBatchAllocated = intval($pocTotalRow['total_consumed']);
                    }
                    $pocTotalStmt->close();
                }
            }
        }
        $restUnallocatedQty = max(0, $psoBatchQty - $totalBatchAllocated);

        // Resolve Allocated Product Name & ID upfront
        $allocProdName = !empty($psoBatchProdName) ? $psoBatchProdName : (!empty($rsa['productname']) ? trim($rsa['productname']) : (!empty($poc['product_name']) ? trim($poc['product_name']) : $item['product_name']));
        $allocProdId = !empty($psoBatchProdId) ? $psoBatchProdId : (!empty($rsa['productid']) ? trim($rsa['productid']) : (!empty($poc['product_id']) ? trim($poc['product_id']) : $item['product_id']));

        $origOrderQty = (!empty($poc['original_orderqty']) && intval($poc['original_orderqty']) > 0) ? intval($poc['original_orderqty']) : intval($item['quantity'] ?? 0);
        $baseOrderQty = max($origOrderQty, intval($item['quantity'] ?? 0));

        // Prioritize actual allocated quantity from krf_readystock_allocation (strictly across all active batches for this psono and product)
        $origAllocQty = 0;
        if ($rsaTotalAlloc > 0) {
            $origAllocQty = $rsaTotalAlloc;
        } elseif (!empty($allocatedBatches)) {
            $pocBatchSum = 0;
            foreach ($allocatedBatches as $b) {
                $pocBatchSum += intval($b['allocated_qty'] ?? $b['consumed_qty'] ?? 0);
            }
            if ($pocBatchSum > 0) {
                $origAllocQty = $pocBatchSum;
            }
        }
        if ($origAllocQty <= 0) {
            if (!empty($item['allocate_ordqty']) && intval($item['allocate_ordqty']) > 0) {
                $origAllocQty = intval($item['allocate_ordqty']);
            } elseif (!empty($item['reservedqty']) && intval($item['reservedqty']) > 0) {
                $origAllocQty = intval($item['reservedqty']);
            } elseif (!empty($poc['consumed_qty']) && intval($poc['consumed_qty']) > 0) {
                $origAllocQty = intval($poc['consumed_qty']);
            }
        }

        // Fetch past saved item details (rates, delivery date, stock order) FIRST before filtering
        $savedDispatchedItem = null;
        $lookupInvNo = !empty($explicitEditInvNo) ? $explicitEditInvNo : $savedDinvNo;

        // Query total quantity already invoiced/dispatched from sales_invoice_items (strictly excluding current invoice being edited)
        $itemSoiId = intval($item['id'] ?? 0);
        $dispatchedInvoiceQty = 0;
        $excludeInvClause = !empty($lookupInvNo) ? " AND invoiceno != '" . mysqli_real_escape_string($krfconn, $lookupInvNo) . "' " : "";
        $dispQtySql = "
            SELECT COALESCE(SUM(quantity), 0) AS total_dispatched_qty
            FROM sales_invoice_items
            WHERE (
                (original_soi_id = ? AND ? > 0) OR
                ((psono = ? OR sono = ? OR sono LIKE CONCAT(?, '-%')) AND (productid = ? OR productid = ?))
            )
              AND productid != 'BOX_PACKING'
              AND is_box_packing = 0
              AND LOWER(IFNULL(status, '')) != 'cancelled'
              {$excludeInvClause}
        ";
        $dispQtyStmt = $krfconn->prepare($dispQtySql);
        if ($dispQtyStmt) {
            $dispQtyStmt->bind_param("iisssss", $itemSoiId, $itemSoiId, $effectivePsono, $effectiveSono, $effectiveSono, $pId, $allocProdId);
            $dispQtyStmt->execute();
            $dqRow = $dispQtyStmt->get_result()->fetch_assoc();
            $dispatchedInvoiceQty = intval($dqRow['total_dispatched_qty'] ?? 0);
            $dispQtyStmt->close();
        }

        // Query total partially dispatched quantity against psono of respective productid across all sources
        $soiDispatchedQty = intval($item['dispatchedqty'] ?? 0);
        $partiallyDispatchedQty = max($dispatchedInvoiceQty, $rsaDispatchedTotal, $soiDispatchedQty);

        if (!empty($lookupInvNo)) {
            $siiCheck = $krfconn->prepare("
                SELECT id, sono, stockorder_no, quantity, rate, discount_percent, discount_amount, taxableamount, amount, 
                       quantity AS allocate_ordqty, healthyqty AS healthyplant, unhealthyqty AS unhealthyplant, 
                       isshifted, percentextrashifting, shiftingqty, 
                       deliverydate, invoiceno, status, remarks 
                FROM sales_invoice_items 
                WHERE invoiceno = ?
                  AND (productid = ? OR productid = ? OR original_soi_id = ?)
                  AND productid != 'BOX_PACKING'
                ORDER BY id DESC LIMIT 1
            ");
            if ($siiCheck) {
                $siiCheck->bind_param("sssi", $lookupInvNo, $pId, $allocProdId, $itemSoiId);
                $siiCheck->execute();
                $savedDispatchedItem = $siiCheck->get_result()->fetch_assoc();
                $siiCheck->close();
            }
            if (!$savedDispatchedItem) {
                $sdiStmt = $krfconn->prepare("
                    SELECT id, sono, stockorder_no, quantity, rate, discount_percent, discount_amount, taxableamount, amount, 
                           allocate_ordqty, healthyplant, unhealthyplant, isshifted, percentextrashifting, shiftingqty, 
                           deliverydate, invoiceno, invoicedate, status 
                    FROM krf_sales_order_items 
                    WHERE invoiceno = ?
                      AND (product_id = ? OR product_id = ?)
                      AND product_id != 'BOX_PACKING'
                    ORDER BY id DESC LIMIT 1
                ");
                if ($sdiStmt) {
                    $sdiStmt->bind_param("sss", $lookupInvNo, $pId, $allocProdId);
                    $sdiStmt->execute();
                    $savedDispatchedItem = $sdiStmt->get_result()->fetch_assoc();
                    $sdiStmt->close();
                }
            }
        }

        // STRICT ALLOCATION FILTER:
        // Only allocated products are displayed for prepared invoice!
        $isAllocated = ($origAllocQty > 0 || !empty($allocatedBatches) || (!empty($rsa) && intval($rsa['allocate_ordqty'] ?? 0) > 0) || strtolower(trim($item['status'] ?? '')) === 'allocated');
        $isCancelled = in_array(strtolower(trim($item['status'] ?? '')), ['cancelled', 'cancel']) && $origAllocQty <= 0;

        if (!empty($savedDispatchedItem)) {
            $isAllocated = true;
            $isCancelled = false;
        }

        if (!$isAllocated || $isCancelled) {
            continue; // Skip unallocated products! Prepared invoice strictly displays allocated products.
        }

        // Effective allocated quantity: strictly bills against the allocated quantity
        $effectiveAllocQty = ($origAllocQty > 0) ? $origAllocQty : (!empty($savedDispatchedItem) ? intval($savedDispatchedItem['quantity'] ?? $savedDispatchedItem['allocate_ordqty'] ?? 0) : 0);

        // Determine Total Order Allocation and Remaining Balance:
        // If $effectiveAllocQty is already reduced/split to residual tranche (< $partiallyDispatchedQty),
        // it already represents the remaining balance to be invoiced!
        if ($effectiveAllocQty > 0 && $partiallyDispatchedQty > 0 && $effectiveAllocQty < $partiallyDispatchedQty) {
            $totalOrderAllocQty = $effectiveAllocQty + $partiallyDispatchedQty;
            $remainingAllocQty = $effectiveAllocQty;
        } else {
            $totalOrderAllocQty = max($effectiveAllocQty, $effectiveAllocQty + $partiallyDispatchedQty);
            $remainingAllocQty = max(0, $effectiveAllocQty - $partiallyDispatchedQty);
        }

        // Shifting and buffer calculations
        $isShifted = (isset($poc['isshifted']) ? ($poc['isshifted'] == 1 || $poc['isshifted'] === 'yes' || $poc['isshifted'] === 'YES' || $poc['isshifted'] === '1') : (isset($rsa['isshifted']) ? ($rsa['isshifted'] == 1 || $rsa['isshifted'] === 'yes' || $rsa['isshifted'] === '1') : ($item['isshifted'] === 'yes' || $item['isshifted'] === 'YES' || $item['isshifted'] === '1' || $item['isshifted'] === 1)));
        $extraPct = $isShifted ? 0 : floatval(!empty($poc['percentextrashifting']) ? $poc['percentextrashifting'] : (!empty($rsa['percentextrashifting']) ? $rsa['percentextrashifting'] : ($item['percentextrashifting'] ?? 0)));
        $extraPlants = $isShifted ? 0 : intval(!empty($poc['extraqtyforshifting']) ? $poc['extraqtyforshifting'] : (!empty($rsa['shiftingqty']) ? $rsa['shiftingqty'] : ($item['shiftingqty'] ?? 0)));

        // Determine item quantities:
        // If explicitly editing a past invoice OR if whole order is already fully invoiced ($remainingAllocQty <= 0) and in edit mode:
        // load the exact invoiced quantity from $savedDispatchedItem!
        $isEditItem = (!empty($explicitEditInvNo) || ($remainingAllocQty <= 0 && !empty($lookupInvNo)));
        if ($isEditItem && $savedDispatchedItem) {
            $allocQty = intval($savedDispatchedItem['quantity'] ?? $savedDispatchedItem['allocate_ordqty'] ?? 0);
            $healthyPlants = intval($savedDispatchedItem['healthyqty'] ?? $savedDispatchedItem['healthyplant'] ?? $allocQty);
            $unhealthyPlants = intval($savedDispatchedItem['unhealthyqty'] ?? $savedDispatchedItem['unhealthyplant'] ?? 0);
        } else {
            // New invoice for remaining balance (defaults strictly to un-invoiced allocated quantity!)
            $allocQty = $remainingAllocQty;
            $healthyPlants = $remainingAllocQty;
            $unhealthyPlants = 0;
        }

        $pendingBalanceAllocQty = max(0, $baseOrderQty - $totalOrderAllocQty);
        $item['original_orderqty'] = $baseOrderQty;
        $item['original_allocqty'] = $effectiveAllocQty;
        $item['total_allocated_qty'] = $totalOrderAllocQty;
        $item['pending_balance_alloc_qty'] = $pendingBalanceAllocQty;
        $item['dispatched_invoice_qty'] = $dispatchedInvoiceQty;
        $item['partially_dispatched_qty'] = $partiallyDispatchedQty;
        $item['rsa_dispatched_qty'] = $rsaDispatchedTotal;
        $item['soi_dispatched_qty'] = $soiDispatchedQty;
        $item['remaining_allocqty'] = $remainingAllocQty;
        $item['allocate_ordqty'] = $allocQty;
        $item['healthyplant'] = $healthyPlants;
        $item['unhealthyplant'] = $unhealthyPlants;
        $item['is_invoice_edit_item'] = $isEditItem ? 1 : 0;
        $item['invoice_loaded_qty'] = $allocQty;
        $item['isshifted'] = $isShifted ? 'yes' : 'no';
        $item['percentextrashifting'] = $extraPct;
        $item['shiftingqty'] = $extraPlants;
        $item['original_stockorder_no'] = $origStockNo;
        $item['allocated_stockorder_no'] = $allocatedStockNo;
        $item['is_swapped'] = $isSwapped ? 1 : 0;
        $item['pso_batch_qty'] = $psoBatchQty;
        $item['total_batch_allocated_qty'] = $totalBatchAllocated;
        $item['rest_unallocated_qty'] = $restUnallocatedQty;

        if ($savedDispatchedItem) {
            if (isset($savedDispatchedItem['rate']) && floatval($savedDispatchedItem['rate']) > 0) {
                $item['rate'] = floatval($savedDispatchedItem['rate']);
            }
            if (isset($savedDispatchedItem['discount_percent'])) {
                $item['discount_percent'] = floatval($savedDispatchedItem['discount_percent']);
            }
            if (isset($savedDispatchedItem['discount_amount'])) {
                $item['discount_amount'] = floatval($savedDispatchedItem['discount_amount']);
            }
            if (isset($savedDispatchedItem['isshifted']) && $savedDispatchedItem['isshifted'] !== '') {
                $isShifted = ($savedDispatchedItem['isshifted'] === 'yes' || $savedDispatchedItem['isshifted'] === '1' || $savedDispatchedItem['isshifted'] === 1);
                $item['isshifted'] = $isShifted ? 'yes' : 'no';
            }
            if (isset($savedDispatchedItem['percentextrashifting'])) {
                $extraPct = floatval($savedDispatchedItem['percentextrashifting']);
                $item['percentextrashifting'] = $extraPct;
            }
            if (isset($savedDispatchedItem['shiftingqty'])) {
                $extraPlants = intval($savedDispatchedItem['shiftingqty']);
                $item['shiftingqty'] = $extraPlants;
            }
            if (!empty($savedDispatchedItem['deliverydate'])) {
                $item['deliverydate'] = $savedDispatchedItem['deliverydate'];
                $item['formatted_delivery_date'] = date('d-M-Y', strtotime($savedDispatchedItem['deliverydate']));
            }
            if (!empty($savedDispatchedItem['stockorder_no'])) {
                $allocatedStockNo = trim($savedDispatchedItem['stockorder_no']);
                $item['allocated_stockorder_no'] = $allocatedStockNo;
            }
            if (empty($savedDinvDate) && !empty($savedDispatchedItem['invoicedate'])) {
                $savedDinvDate = date('Y-m-d', strtotime($savedDispatchedItem['invoicedate']));
            }
        }

        // Shifting and buffer correction: buffer is strictly calculated on allocated dispatch quantity
        if ($isShifted) {
            $extraPlants = intval(ceil($allocQty / 125));
            $item['shiftingqty'] = $extraPlants;
            $item['percentextrashifting'] = 0;
        } else if ($extraPct > 0 && $allocQty > 0) {
            if ($extraPlants <= 0 || ($baseOrderQty > $allocQty && $extraPlants == round($baseOrderQty * ($extraPct / 100))) || $extraPlants > $allocQty) {
                $extraPlants = intval(round($allocQty * ($extraPct / 100)));
                $item['shiftingqty'] = $extraPlants;
            }
        }

        // Fetch polyhouse plant availability for the ACTIVE ALLOCATED BATCH!
        $targetStockNo = (!empty($allocatedStockNo) && $allocatedStockNo !== 'Not Allocated' && $allocatedStockNo !== '0') ? $allocatedStockNo : (!empty($origStockNo) ? $origStockNo : $stk);
        $phInfo = [
            'polyhouse_tray_count' => 0,
            'polyhouse_plant_qty' => 0,
            'ready_plant_qty' => 0,
            'polyhouse_names' => 'N/A',
            'bed_numbers' => ''
        ];
        if (!empty($targetStockNo) && $targetStockNo !== 'Not Allocated' && $targetStockNo !== '0') {
            $stkParts = array_filter(array_map('trim', explode(',', $targetStockNo)));
            if (!empty($stkParts)) {
                $escapedParts = array_map(function($s) use ($krfconn) { return "'" . mysqli_real_escape_string($krfconn, $s) . "'"; }, $stkParts);
                $phInList = implode(',', $escapedParts);
                $phSql = "
                    SELECT 
                        COUNT(ppd.polyhouseplantid) AS polyhouse_tray_count,
                        SUM(CASE WHEN LOWER(IFNULL(ppd.health_status, '')) IN ('healthy','unhealthy') AND LOWER(IFNULL(ppd.status, '')) IN ('openstock', 'polyhouse', 'readytosale') THEN GREATEST(0, CAST(IFNULL(ppd.qtypertray, 0) AS SIGNED) - CAST(IFNULL(ppd.failurecount, 0) AS SIGNED)) ELSE 0 END) AS polyhouse_plant_qty,
                        SUM(CASE WHEN LOWER(IFNULL(ppd.health_status, '')) IN ('healthy','unhealthy') AND (LOWER(IFNULL(ppd.status, '')) IN ('readytosale', 'openstock') OR LOWER(IFNULL(ppd.workingstatus, '')) IN ('readytosale', 'openstock')) THEN GREATEST(0, CAST(IFNULL(ppd.qtypertray, 0) AS SIGNED) - CAST(IFNULL(ppd.failurecount, 0) AS SIGNED)) ELSE 0 END) AS ready_plant_qty,
                        GROUP_CONCAT(DISTINCT ph.polyhousename SEPARATOR ', ') AS polyhouse_names,
                        GROUP_CONCAT(DISTINCT NULLIF(TRIM(ppd.bedrackname), '') SEPARATOR ', ') AS bed_numbers
                    FROM krf_polyhouse_plant_details ppd
                    LEFT JOIN krf_polyhouse_master ph ON ppd.polyhouseid = ph.polyhouseid
                    WHERE ppd.stockorder_no IN ({$phInList})
                ";
                $phResQ = $krfconn->query($phSql);
                if ($phResQ && $phRes = $phResQ->fetch_assoc()) {
                    $phInfo['polyhouse_tray_count'] = intval($phRes['polyhouse_tray_count'] ?? 0);
                    $phInfo['polyhouse_plant_qty'] = intval($phRes['polyhouse_plant_qty'] ?? 0);
                    $phInfo['ready_plant_qty'] = intval($phRes['ready_plant_qty'] ?? 0);
                    $phInfo['polyhouse_names'] = $phRes['polyhouse_names'] ?? 'N/A';
                    $phInfo['bed_numbers'] = $phRes['bed_numbers'] ?? '';
                }
            }
        }
        $item['allocated_batches'] = $allocatedBatches;
        $item['allocated_stockorder_no'] = $allocatedStockNo;
        $item['polyhouse_tray_count'] = $phInfo['polyhouse_tray_count'];
        $item['polyhouse_plant_qty'] = $phInfo['polyhouse_plant_qty'];
        $item['ready_plant_qty'] = $phInfo['ready_plant_qty'];
        $item['polyhouse_names'] = $phInfo['polyhouse_names'];

        // Resolve Allocated Product Name from production order batch, krf_readystock_allocation, or fallback
        $item['booked_product_name'] = $item['product_name'];
        $item['booked_product_id'] = $item['product_id'];
        $item['allocated_product_name'] = $allocProdName;
        $item['allocated_product_id'] = $allocProdId;
        // Invoiced item strictly reflects the allocated product name and ID
        $item['product_name'] = !empty($allocProdName) ? $allocProdName : $item['product_name'];
        $item['product_id'] = !empty($allocProdId) ? $allocProdId : $item['product_id'];
        $rawItemProd = $item['product_name'];
        $item['clean_product_name'] = preg_replace('/^(NonGrafted|Non-Grafted)\s*[-:]?\s*/i', '', $rawItemProd);
        $item['clean_booked_product_name'] = preg_replace('/^(NonGrafted|Non-Grafted)\s*[-:]?\s*/i', '', $item['booked_product_name'] ?? '');
        $item['is_grafted'] = (stripos($rawItemProd, 'grafted') !== false && stripos($rawItemProd, 'nongrafted') === false && stripos($rawItemProd, 'non-grafted') === false) ? 1 : 0;

        // Resolve allocated bed numbers from polyhouse plants or krf_readystock_allocation.allocatedbed
        $resolvedPhBed = '';
        if (!empty($rsaBedsList)) {
            $allBedStrings = [];
            foreach ($rsaBedsList as $rawBed) {
                $rawBed = trim($rawBed);
                if (empty($rawBed)) continue;
                if (strpos($rawBed, '[') === 0) {
                    $decBeds = json_decode($rawBed, true);
                    if (is_array($decBeds)) {
                        foreach ($decBeds as $db) {
                            if (is_string($db)) $allBedStrings[] = $db;
                            elseif (is_array($db)) $allBedStrings[] = $db['bed_name'] ?? ($db['bed'] ?? ($db['bedrackname'] ?? ''));
                        }
                    }
                } else {
                    $allBedStrings[] = $rawBed;
                }
            }
            $allBedStrings = array_filter(array_unique($allBedStrings));
            if (!empty($allBedStrings)) {
                $resolvedPhBed = implode(', ', $allBedStrings);
            }
        } elseif (!empty($rsa['allocatedbed'])) {
            $rawBed = trim($rsa['allocatedbed']);
            if (strpos($rawBed, '[') === 0) {
                $decBeds = json_decode($rawBed, true);
                if (is_array($decBeds)) {
                    $bList = [];
                    foreach ($decBeds as $db) {
                        if (is_string($db)) $bList[] = $db;
                        elseif (is_array($db)) $bList[] = $db['bed_name'] ?? ($db['bed'] ?? ($db['bedrackname'] ?? ''));
                    }
                    $bList = array_filter(array_unique($bList));
                    if (!empty($bList)) $resolvedPhBed = implode(', ', $bList);
                }
            } else {
                $resolvedPhBed = $rawBed;
            }
        }
        $item['allocatedbed'] = !empty($rsa['allocatedbed']) ? $rsa['allocatedbed'] : '';
        $item['allocated_bed_numbers'] = $resolvedPhBed;
        $item['bed_numbers'] = !empty($resolvedPhBed) ? $resolvedPhBed : (!empty($phInfo['bed_numbers']) ? $phInfo['bed_numbers'] : '-');

        // Billable Qty for invoicing is STRICTLY the Allocated Qty
        $billableQty = $allocQty;
        // Total Physical Dispatch Qty includes complimentary shifting plants if not shifted
        $dispatchQty = $isShifted ? $allocQty : ($allocQty + $extraPlants);

        $item['billable_qty'] = $billableQty;
        $item['dispatch_qty'] = $dispatchQty;

        $totalOriginalOrderQty += $origOrderQty;
        $totalOriginalAllocQty += $effectiveAllocQty;

        $items[] = $item;
    }

    $overallPlantType = ($hasGrafted && $hasNongrafted) ? 'Both' : ($hasGrafted ? 'Grafted' : 'Nongrafted');

    // 4. Fetch Delivery Location Info from krf_deliveryaddress_location
    $delLocSql = "
        SELECT id, psono, sono, clientid, productid, deliveryqty, deliverycharges, transporttype, 
               dest_state, dest_district, dest_city, dest_pincode, deliveryaddress, 
               totaldistance, freedistance, distance, remark, pickuppoint, pickuptype, 
               noofbox, freightamt, boxperpack, chargesperbox, stateid, districtid
        FROM krf_deliveryaddress_location 
        WHERE (psono = ? AND ? != '') OR (sono = ? AND ? != '')
        ORDER BY id DESC LIMIT 1
    ";
    $dlStmt = $krfconn->prepare($delLocSql);
    $dlStmt->bind_param("ssss", $effectivePsono, $effectivePsono, $effectiveSono, $effectiveSono);
    $dlStmt->execute();
    $delLoc = $dlStmt->get_result()->fetch_assoc();

    // Fallback: If box packing not found in line items, check delivery location table
    if (!$savedBoxPacking['enabled'] && !empty($delLoc) && intval($delLoc['noofbox'] ?? 0) > 0) {
        $bQty = intval($delLoc['noofbox']);
        $bRate = floatval($delLoc['chargesperbox'] ?? 100.00);
        if ($bRate <= 0) $bRate = 100.00;
        $savedBoxPacking = [
            'enabled' => true,
            'box_count' => $bQty,
            'box_rate' => $bRate,
            'box_total' => round($bQty * $bRate, 2),
            'box_capacity' => !empty($delLoc['boxperpack']) ? intval($delLoc['boxperpack']) : 500,
            'product_name' => "Box Packing Charges ({$bQty} Boxes @ Rs " . number_format($bRate, 0) . ")"
        ];
    }

    // 5. Fetch Freight snapshot from krf_freightcharges
    $fcSql = "
        SELECT freight_amount, freight_discountpercent, freight_discountamount, payable_freightamount, zerocharge, remarks
        FROM krf_freightcharges 
        WHERE (psono = ? AND ? != '') OR (sono = ? AND ? != '')
        ORDER BY id DESC LIMIT 1
    ";
    $fcStmt = $krfconn->prepare($fcSql);
    $fcStmt->bind_param("ssss", $effectivePsono, $effectivePsono, $effectiveSono, $effectiveSono);
    $fcStmt->execute();
    $freightData = $fcStmt->get_result()->fetch_assoc();

    // 6. Fetch Active Freight Discount Plans
    $plansSql = "
        SELECT id, plan_code, plan_name, customer_type, plant_type, discount_type, 
               discount_value, max_discount_amount, min_order_qty, max_order_qty, 
               min_freight_amount, min_distance_km, max_distance_km, 
               stateid, statename, districtid, districtname, valid_from, valid_to, priority, description, terms_conditions 
        FROM krf_freight_discount_plan 
        WHERE status = 'Active' AND valid_from <= CURDATE() AND valid_to >= CURDATE() AND (tedit IS NULL OR tedit = '')
        ORDER BY priority DESC, id DESC
    ";
    $plansRes = $krfconn->query($plansSql);
    $activeRules = [];
    if ($plansRes) {
        while ($pr = $plansRes->fetch_assoc()) {
            $activeRules[] = $pr;
        }
    }

    $orderData['formatted_delivery_date'] = !empty($orderData['deliverydate']) ? date('d-M-Y', strtotime($orderData['deliverydate'])) : '-';

    // 6. Fetch Existing Vehicle Allocation & Logistics Details from krf_vehicle_travel_history & krf_vehicletrip (PRIMARY)
    $vehAlloc = null;
    $vaStmt = $krfconn->prepare("
        SELECT 
            vth.id,
            vth.projectid,
            vth.stockorder_no AS sono,
            vth.psono,
            '' AS soi_id,
            '' AS dinvoiceno,
            COALESCE(vth.vehicle_id, vt.vehicle_id, 0) AS vehicle_id,
            COALESCE(vth.vehicle_no, vt.vehicle_no, '') AS vehicleno,
            COALESCE(vm.vehiclename, '') AS vehiclename,
            COALESCE(vm.vehicleownername, '') AS vehicleownername,
            COALESCE(vth.driverid, vt.driver_id, '') AS driver_id,
            COALESCE(vth.driver_name, vt.driver_name, '') AS drivername,
            COALESCE(vth.driver_mobileno, vt.driver_mobile_no, '') AS drivermobileno,
            COALESCE(vth.deliverydate, vt.delivery_date) AS scheduled_dispatch_date,
            COALESCE(vm.plantscapacity, 0) AS plantscapacity,
            COALESCE(vth.send_totalplantcount, 0) AS total_allocated_plants,
            'vehicle_allocation' AS allocation_source,
            COALESCE(vt.status, 'Allocated') AS allocation_status,
            COALESCE(vth.send_remark, vt.remarks, '') AS remarks,
            COALESCE(vth.created_by, vt.created_by, '') AS allocated_by,
            DATE_FORMAT(COALESCE(vth.created_at, vt.created_at), '%d-%b-%Y %h:%i %p') AS allocated_at_formatted,
            COALESCE(vt.trip_no, vth.trip_id, '') AS trip_id,
            COALESCE(NULLIF(vt.to_location_name, ''), NULLIF(vth.to_location_name, '')) AS to_location_name,
            COALESCE(vt.to_lat, vth.to_lat) AS to_lat,
            COALESCE(vt.to_lon, vth.to_lon) AS to_lon,
            COALESCE(vt.distance_km, vth.distance_km) AS distance_km,
            COALESCE(NULLIF(vt.distance_source, ''), NULLIF(vth.distance_source, '')) AS distance_source,
            COALESCE(vt.freight_amount, vth.freight_amount) AS trip_freight_amount,
            COALESCE(vt.freight_discount_percent, vth.freight_discountpercent) AS trip_discount_percent,
            COALESCE(vt.payable_freight_amount, vth.payable_freightamount) AS trip_payable_freight,
            COALESCE(NULLIF(vt.dispatch_type, ''), NULLIF(vth.dispatchtype, '')) AS dispatch_type
        FROM krf_vehicle_travel_history vth
        LEFT JOIN krf_vehicletrip vt ON (vt.trip_no = vth.trip_id OR vt.trip_id = vth.trip_id)
        LEFT JOIN krf_vehicle_master vm ON (vm.vehicleid = COALESCE(vth.vehicle_id, vt.vehicle_id) AND vm.vehicleid > 0)
        WHERE (vth.stockorder_no = ? AND ? != '') 
           OR (vth.psono = ? AND ? != '')
           OR (vth.stockorder_no = ? AND ? != '')
           OR (vth.psono = ? AND ? != '')
        ORDER BY vth.id DESC LIMIT 1
    ");
    $vaStmt->bind_param("ssssssss", $effectiveSono, $effectiveSono, $effectivePsono, $effectivePsono, $sono, $sono, $psono, $psono);
    $vaStmt->execute();
    $vehAlloc = $vaStmt->get_result()->fetch_assoc();

    // Priority 1: Check if delivery address and logistics exist in krf_vehicletrip / krf_vehicle_travel_history
    if ($vehAlloc && !empty($vehAlloc['to_location_name'])) {
        $tripAddr = trim($vehAlloc['to_location_name']);
        $tripDist = (isset($vehAlloc['distance_km']) && $vehAlloc['distance_km'] !== null) ? floatval($vehAlloc['distance_km']) : floatval($delLoc['distance'] ?? 0);
        $tripType = $vehAlloc['dispatch_type'] ?? '';
        $normTripType = 'delivered';
        if (strcasecmp($tripType, 'Self Pickup') === 0 || strcasecmp($vehAlloc['vehicleno'] ?? '', 'Self Pickup') === 0) {
            $normTripType = 'pickup';
        } elseif (strcasecmp($tripType, 'Box Packing') === 0) {
            $normTripType = 'boxpacking';
        }

        if (!$delLoc) {
            $delLoc = [
                'id' => 0,
                'psono' => $effectivePsono,
                'sono' => $effectiveSono,
                'clientid' => $clientId,
                'deliveryaddress' => $tripAddr,
                'dest_latitude' => $vehAlloc['to_lat'] ?? null,
                'dest_longitude' => $vehAlloc['to_lon'] ?? null,
                'distance' => $tripDist,
                'totaldistance' => $tripDist,
                'freedistance' => 0,
                'pickuptype' => $normTripType,
                'transporttype' => $tripType ?: 'Delivered',
                'deliverycharges' => floatval($vehAlloc['trip_freight_amount'] ?? 0),
                'freightamt' => floatval($vehAlloc['trip_payable_freight'] ?? 0),
                'dest_state' => $orderData['statename'] ?? ($clientInfo['statename'] ?? ''),
                'dest_district' => $orderData['districtname'] ?? ($clientInfo['districtname'] ?? ''),
                'dest_city' => $orderData['cityname'] ?? ($clientInfo['cityname'] ?? ''),
                'is_location_available' => true,
                'address_source' => 'krf_vehicletrip'
            ];
        } else {
            // Overlay address & distance from krf_vehicletrip as Primary
            $delLoc['deliveryaddress'] = $tripAddr;
            $delLoc['distance'] = $tripDist;
            $delLoc['totaldistance'] = $tripDist;
            if (!empty($vehAlloc['to_lat'])) $delLoc['dest_latitude'] = $vehAlloc['to_lat'];
            if (!empty($vehAlloc['to_lon'])) $delLoc['dest_longitude'] = $vehAlloc['to_lon'];
            if (!empty($tripType)) $delLoc['pickuptype'] = $normTripType;
            $delLoc['is_location_available'] = true;
            $delLoc['address_source'] = 'krf_vehicletrip';
        }

        // Also sync freight charges if trip has freight data and existing snapshot is empty
        if ((empty($freightData) || floatval($freightData['payable_freightamount'] ?? 0) <= 0) && floatval($vehAlloc['trip_payable_freight'] ?? 0) > 0) {
            if (!$freightData) $freightData = [];
            $freightData['freight_amount'] = floatval($vehAlloc['trip_freight_amount'] ?? 0);
            $freightData['freight_discountpercent'] = floatval($vehAlloc['trip_discount_percent'] ?? 0);
            $freightData['payable_freightamount'] = floatval($vehAlloc['trip_payable_freight'] ?? 0);
        }
    }

    // Priority 2 & 3: Fallback if deliveryaddress is still empty
    if ((!$delLoc || empty($delLoc['deliveryaddress'])) && !empty($resolvedInvAddr)) {
        if (!$delLoc) {
            $delLoc = [
                'id' => 0,
                'psono' => $effectivePsono,
                'sono' => $effectiveSono,
                'clientid' => $clientId,
                'deliveryaddress' => $resolvedInvAddr,
                'dest_latitude' => null,
                'dest_longitude' => null,
                'distance' => 0,
                'totaldistance' => 0,
                'freedistance' => 0,
                'pickuptype' => 'delivered',
                'transporttype' => 'Delivered',
                'deliverycharges' => 0,
                'freightamt' => 0,
                'dest_state' => $clientInfo['statename'] ?? ($orderData['statename'] ?? ''),
                'dest_district' => $clientInfo['districtname'] ?? ($orderData['districtname'] ?? ''),
                'dest_city' => $clientInfo['cityname'] ?? ($orderData['cityname'] ?? ''),
                'is_location_available' => true,
                'address_source' => 'client_master'
            ];
        } else {
            $delLoc['deliveryaddress'] = $resolvedInvAddr;
            if (empty($delLoc['dest_state'])) $delLoc['dest_state'] = $clientInfo['statename'] ?? ($orderData['statename'] ?? '');
            if (empty($delLoc['dest_district'])) $delLoc['dest_district'] = $clientInfo['districtname'] ?? ($orderData['districtname'] ?? '');
            if (empty($delLoc['dest_city'])) $delLoc['dest_city'] = $clientInfo['cityname'] ?? ($orderData['cityname'] ?? '');
            $delLoc['is_location_available'] = true;
            $delLoc['address_source'] = 'client_master';
        }
    }

    if ($delLoc && !empty($delLoc['deliveryaddress'])) {
        $orderData['delivery_address'] = $delLoc['deliveryaddress'];
        if (!empty($delLoc['dest_state'])) $orderData['statename'] = $delLoc['dest_state'];
        if (!empty($delLoc['dest_district'])) $orderData['districtname'] = $delLoc['dest_district'];
    }

    // Resolve allocated_by user name
    if ($vehAlloc && !empty($vehAlloc['allocated_by'])) {
        $uAlloc = $vehAlloc['allocated_by'];
        $uStmt = $authconn->prepare("SELECT loginname FROM login_user_master WHERE loginid = ? OR mobileno = ? LIMIT 1");
        if ($uStmt) {
            $uStmt->bind_param("ss", $uAlloc, $uAlloc);
            $uStmt->execute();
            $uRow = $uStmt->get_result()->fetch_assoc();
            if ($uRow && !empty($uRow['loginname'])) {
                $vehAlloc['allocated_by_name'] = $uRow['loginname'];
            }
        }
    }

    // Fallback: If not in vehicle travel history, check krf_deliveryaddress_location or self pickup
    $isPickupDelivery = ($delLoc && in_array(strtolower(trim($delLoc['pickuptype'] ?? '')), ['pickup', 'client_pickup', 'pickupbyclient']));
    if (!$vehAlloc && ($isPickupDelivery || !empty($delLoc['vehicleno']) || !empty($delLoc['drivername']))) {
        $vehAlloc = [
            'id' => 0,
            'vehicle_id' => 0,
            'vehicleno' => $isPickupDelivery ? 'Self Pickup' : ($delLoc['vehicleno'] ?? ''),
            'vehiclename' => $isPickupDelivery ? 'Self Pickup' : '',
            'vehicleownername' => $isPickupDelivery ? 'Self Pickup By Farmer/Client' : '',
            'driver_id' => 0,
            'drivername' => $isPickupDelivery ? 'Self (Farmer/Client)' : ($delLoc['drivername'] ?? ''),
            'drivermobileno' => $delLoc['drivercontactno'] ?? '',
            'scheduled_dispatch_date' => !empty($delLoc['dispatch_datetime']) ? date('Y-m-d', strtotime($delLoc['dispatch_datetime'])) : null,
            'plantscapacity' => 0,
            'allocation_source' => $isPickupDelivery ? 'client_pickup' : 'delivery_location',
            'allocation_status' => 'Allocated',
            'remarks' => $delLoc['remark'] ?? '',
            'allocated_by' => '',
            'allocated_by_name' => '',
            'allocated_at_formatted' => ''
        ];
    }

    // 7. Fetch active vehicles from krf_vehicle_master for selection dropdown
    $vehicles = [];
    $effProj = ($projectid > 0) ? $projectid : intval($orderData['projectid'] ?? 0);
    $vmSql = "SELECT vehicleid, projectid, vehicleno, vehiclename, vehicleownername, traycapacity, plantscapacity FROM krf_vehicle_master WHERE status = 'Active'";
    if ($effProj > 0) {
        $vmSql .= " AND (projectid = ? OR projectid = 0)";
        $vmStmt = $krfconn->prepare($vmSql);
        $vmStmt->bind_param("i", $effProj);
        $vmStmt->execute();
        $vmRes = $vmStmt->get_result();
    } else {
        $vmRes = $krfconn->query($vmSql);
    }
    if ($vmRes) {
        while ($vr = $vmRes->fetch_assoc()) {
            $vehicles[] = $vr;
        }
    }

    // Calculate sequential next_invoice_no for live preview & prefill ('KF/'.$currectfinancialyear.'/'maxcount)
    $generatedNextInvoiceNo = function_exists('generateDispatchInvoiceNo')
        ? generateDispatchInvoiceNo($krfconn, $effectivePsono, $effectiveSono, null, true)
        : (function_exists('getNewDispatchInvoiceNumber') ? getNewDispatchInvoiceNumber($krfconn, $effectivePsono, $effectiveSono, true) : "KF/" . date('y') . '-' . (date('y') + 1) . "/1");

    // Check if total order still has un-invoiced balance remaining
    $totalRemainingBalance = 0;
    foreach ($items as $chkBal) {
        $totalRemainingBalance += intval($chkBal['remaining_allocqty'] ?? 0);
    }

    $isEditMode = false;
    if (!empty($explicitEditInvNo)) {
        // Explicitly editing a past invoice
        $isEditMode = true;
        $nextInvoiceNo = $explicitEditInvNo;
        $savedDinvNo = $explicitEditInvNo;
        $orderData['dinvoiceno'] = $explicitEditInvNo;
    } elseif ($totalRemainingBalance > 0) {
        // New invoice for remaining balance! Do NOT reuse past invoice number
        $isEditMode = false;
        $savedDinvNo = '';
        $orderData['dinvoiceno'] = '';
        $savedDinvDate = date('Y-m-d');
        $nextInvoiceNo = $generatedNextInvoiceNo;
    } else {
        // Fully invoiced order: opening in edit mode
        $isEditMode = !empty($savedDinvNo);
        $nextInvoiceNo = !empty($savedDinvNo) ? $savedDinvNo : $generatedNextInvoiceNo;
    }

    echo json_encode([
        "status" => "success",
        "data" => [
            "order" => $orderData,
            "next_invoice_no" => $nextInvoiceNo,
            "default_invoice_date" => $savedDinvDate,
            "saved_dinvoiceno" => $savedDinvNo,
            "is_edit_mode" => $isEditMode,
            "past_invoices" => $pastInvoices,
            "saved_box_packing" => $savedBoxPacking,
            "items" => $items,
            "item_count" => count($items),
            "client_info" => $clientInfo,
            "customer_type" => $customerType,
            "overall_plant_type" => $overallPlantType,
            "delivery_location" => $delLoc,
            "freight_charges" => $freightData,
            "active_discount_rules" => $activeRules,
            "total_original_order_qty" => $totalOriginalOrderQty,
            "original_payable_freight" => floatval($freightData['payable_freightamount'] ?? ($delLoc['freightamt'] ?? 0)),
            "original_base_freight" => floatval($freightData['freight_amount'] ?? ($delLoc['deliverycharges'] ?? 0)),
            "original_discount_amount" => floatval($freightData['freight_discountamount'] ?? 0),
            "original_discount_remark" => $delLoc['remark'] ?? ($freightData['remarks'] ?? ''),
            "vehicle_allocation" => $vehAlloc,
            "available_vehicles" => $vehicles
        ]
    ]);
    exit;

} else if ($action == "GetOrderPaymentHistory") {
    $projectid = intval($_POST['projectid'] ?? 0);
    $psono = trim($_POST['psono'] ?? '');
    $sono = trim($_POST['sono'] ?? '');
    $clientId = trim($_POST['clientid'] ?? '');

    $paymentHistory = [];
    $totalPaidAmount = 0;
    $paySql = "
        SELECT sno, companyid, subcompanyid, projectid, clientid, clientname, 
               advanceamount, roundoffamount, otherdiscount, utrnumber, paymentdate, purposeofpayment, invoiceno, 
               sourceofpayment, receivedtoaccountid, paymentref_no, receivedby, 
               receivedlocation, receiptpath, remarks, verification, verificationby, 
               verificationat, allocated_datetime
        FROM advance_payment_transactions 
        WHERE ((invoiceno = ? AND ? != '') 
           OR (invoiceno = ? AND ? != ''))
          AND (tedit IS NULL OR tedit = '')
        ORDER BY paymentdate ASC, sno ASC
    ";
    $payStmt = $krfconn->prepare($paySql);
    if ($payStmt) {
        $payStmt->bind_param("ssss", $psono, $psono, $sono, $sono);
        $payStmt->execute();
        $pRes = $payStmt->get_result();
        $verifiedPaidAmount = 0;
        $unverifiedPaidAmount = 0;
        $unverifiedCount = 0;
        while ($pRow = $pRes->fetch_assoc()) {
            $amt = floatval($pRow['advanceamount'] ?? 0);
            $disc = floatval($pRow['otherdiscount'] ?? 0);
            $roundoff = floatval($pRow['roundoffamount'] ?? 0);
            $pRow['advanceamount'] = $amt;
            $pRow['otherdiscount'] = $disc;
            $pRow['roundoffamount'] = $roundoff;
            $pRow['formatted_payment_date'] = !empty($pRow['paymentdate']) ? date('d-M-Y', strtotime($pRow['paymentdate'])) : '-';
            
            $effectiveSettled = $amt + $disc;
            $totalPaidAmount += $effectiveSettled;
            $ver = strtolower(trim($pRow['verification'] ?? ''));
            if ($ver === 'verified') {
                $verifiedPaidAmount += $effectiveSettled;
            } else {
                $unverifiedPaidAmount += $effectiveSettled;
                $unverifiedCount++;
            }
            $paymentHistory[] = $pRow;
        }
        $payStmt->close();
    }

    echo json_encode([
        "status" => "success",
        "data" => [
            "payment_history" => $paymentHistory,
            "total_paid_amount" => $totalPaidAmount,
            "verified_paid_amount" => $verifiedPaidAmount,
            "unverified_paid_amount" => $unverifiedPaidAmount,
            "unverified_count" => $unverifiedCount,
            "count" => count($paymentHistory)
        ]
    ]);
    exit;

} else if ($action == "CheckPaymentConfirmationStatus") {
    $projectid = intval($_POST['projectid'] ?? 0);
    $psono = trim($_POST['psono'] ?? '');
    $sono = trim($_POST['sono'] ?? '');
    $clientId = trim($_POST['clientid'] ?? '');
    $grandTotal = floatval($_POST['grand_total'] ?? 0);

    $paymentHistory = [];
    $totalPaidAmount = 0;
    $verifiedPaidAmount = 0;
    $unverifiedPaidAmount = 0;
    $unverifiedCount = 0;
    $verifiedCount = 0;

    $paySql = "
        SELECT sno, companyid, subcompanyid, projectid, clientid, clientname, 
               advanceamount, roundoffamount, otherdiscount, utrnumber, paymentdate, purposeofpayment, invoiceno, 
               sourceofpayment, receivedtoaccountid, paymentref_no, receivedby, 
               receivedlocation, receiptpath, remarks, verification, verificationby, 
               verificationat, allocated_datetime
        FROM advance_payment_transactions 
        WHERE ((invoiceno = ? AND ? != '') 
           OR (invoiceno = ? AND ? != ''))
          AND (tedit IS NULL OR tedit = '')
        ORDER BY paymentdate ASC, sno ASC
    ";
    $payStmt = $krfconn->prepare($paySql);
    if ($payStmt) {
        $payStmt->bind_param("ssss", $psono, $psono, $sono, $sono);
        $payStmt->execute();
        $pRes = $payStmt->get_result();
        while ($pRow = $pRes->fetch_assoc()) {
            $amt = floatval($pRow['advanceamount'] ?? 0);
            $disc = floatval($pRow['otherdiscount'] ?? 0);
            $round = floatval($pRow['roundoffamount'] ?? 0);
            $pRow['advanceamount'] = $amt;
            $pRow['otherdiscount'] = $disc;
            $pRow['roundoffamount'] = $round;
            $pRow['formatted_payment_date'] = !empty($pRow['paymentdate']) ? date('d-M-Y', strtotime($pRow['paymentdate'])) : '-';
            $effectiveSettled = $amt + $disc;
            $totalPaidAmount += $effectiveSettled;
            $ver = strtolower(trim($pRow['verification'] ?? ''));
            if ($ver === 'verified') {
                $verifiedPaidAmount += $effectiveSettled;
                $verifiedCount++;
            } else {
                $unverifiedPaidAmount += $amt;
                $unverifiedCount++;
            }
            $paymentHistory[] = $pRow;
        }
        $payStmt->close();
    }

    $totalPaidAmount = round($totalPaidAmount, 2);
    $verifiedPaidAmount = round($verifiedPaidAmount, 2);
    $unverifiedPaidAmount = round($unverifiedPaidAmount, 2);
    $balanceAmount = round(max(0, $grandTotal - $totalPaidAmount), 2);
    $isBalanceClear = ($balanceAmount <= 0.01 && $grandTotal > 0);
    $hasUnconfirmed = ($unverifiedCount > 0 && $unverifiedPaidAmount > 0.01);
    $isAllConfirmed = (!$hasUnconfirmed && $isBalanceClear);
    $canDispatch = $isAllConfirmed;

    $statusType = "no_payments";
    if ($hasUnconfirmed) {
        $statusType = "unconfirmed_payments_exist";
    } else if ($balanceAmount > 0.01) {
        $statusType = "pending_balance";
    } else if ($isAllConfirmed) {
        $statusType = "all_confirmed";
    }

    echo json_encode([
        "status" => "success",
        "data" => [
            "is_all_confirmed" => $isAllConfirmed,
            "has_unconfirmed" => $hasUnconfirmed,
            "can_dispatch" => $canDispatch,
            "is_balance_clear" => $isBalanceClear,
            "status_type" => $statusType,
            "grand_total" => $grandTotal,
            "total_paid_amount" => $totalPaidAmount,
            "verified_paid_amount" => $verifiedPaidAmount,
            "unverified_paid_amount" => $unverifiedPaidAmount,
            "balance_amount" => $balanceAmount,
            "verified_count" => $verifiedCount,
            "unverified_count" => $unverifiedCount,
            "total_count" => count($paymentHistory),
            "payment_history" => $paymentHistory
        ]
    ]);
    exit;

} else if ($action == "CancelAdvancePaymentTransaction") {
    $sno = intval($_POST['sno'] ?? 0);
    $cancelRemarkInput = trim($_POST['cancel_remark'] ?? '');
    $loginid = $_SESSION['loginid'] ?? $_SESSION['username'] ?? $_SESSION['loginname'] ?? 'Admin';

    if ($sno <= 0) {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid payment transaction ID."
        ]);
        exit;
    }

    // Check if the record exists
    $chkStmt = $krfconn->prepare("SELECT sno, advanceamount, utrnumber, invoiceno, clientname FROM advance_payment_transactions WHERE sno = ? LIMIT 1");
    if (!$chkStmt) {
        echo json_encode([
            "status" => "error",
            "message" => "Database prepare failed: " . $krfconn->error
        ]);
        exit;
    }
    $chkStmt->bind_param("i", $sno);
    $chkStmt->execute();
    $tx = $chkStmt->get_result()->fetch_assoc();
    $chkStmt->close();

    if (!$tx) {
        echo json_encode([
            "status" => "error",
            "message" => "Payment transaction record not found."
        ]);
        exit;
    }

    $nowStr = date('d-M-Y H:i:s');
    if (empty($cancelRemarkInput)) {
        $cancelRemark = "Wrong payment cancelled by {$loginid} on {$nowStr}";
    } else {
        $cancelRemark = "{$cancelRemarkInput} (Cancelled by {$loginid} on {$nowStr})";
    }

    // Update tedit='1', teditremark, and transaction_type='Cancel'
    $updStmt = $krfconn->prepare("
        UPDATE advance_payment_transactions 
        SET tedit = '1', 
            teditremark = ?, 
            transaction_type = 'Cancel' 
        WHERE sno = ?
    ");
    if (!$updStmt) {
        echo json_encode([
            "status" => "error",
            "message" => "Prepare update failed: " . $krfconn->error
        ]);
        exit;
    }
    $updStmt->bind_param("si", $cancelRemark, $sno);
    $success = $updStmt->execute();
    $updStmt->close();

    if ($success) {
        echo json_encode([
            "status" => "success",
            "message" => "Payment transaction of ₹ " . number_format($tx['advanceamount'], 2) . " (Ref: " . ($tx['utrnumber'] ?: 'N/A') . ") cancelled successfully.",
            "sno" => $sno
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to cancel transaction: " . $krfconn->error
        ]);
    }
    exit;

} else if ($action == "GetOrderInvoicesList") {
    $psono = trim($_POST['psono'] ?? '');
    $sono = trim($_POST['sono'] ?? '');
    $soiId = intval($_POST['soi_id'] ?? 0);

    // If psono/sono empty but soiId provided, lookup
    if (empty($psono) && empty($sono) && $soiId > 0) {
        $soiCheck = $krfconn->prepare("SELECT sono, psono FROM krf_sales_order_items WHERE id = ? LIMIT 1");
        if ($soiCheck) {
            $soiCheck->bind_param("i", $soiId);
            $soiCheck->execute();
            $soiR = $soiCheck->get_result()->fetch_assoc();
            $soiCheck->close();
            if ($soiR) {
                $sono = $soiR['sono'] ?? '';
                $psono = $soiR['psono'] ?? '';
            }
        }
    }

    if (empty($psono) && empty($sono)) {
        echo json_encode(["status" => "error", "message" => "Sales order identifier required."]);
        exit;
    }

    // Query sales order / client details
    $orderInfo = null;
    $likeSono = (!empty($sono)) ? ($sono . '-%') : '';
    $soStmt = $krfconn->prepare("
        SELECT so.clientname, so.mobileno, so.psono, so.sono, so.created_at, so.address, so.clientid
        FROM krf_sales_order so
        WHERE (so.psono = ? AND ? != '') OR (so.sono = ? AND ? != '') OR (so.sono LIKE ? AND ? != '')
        ORDER BY so.id DESC
        LIMIT 1
    ");
    if ($soStmt) {
        $soStmt->bind_param("ssssss", $psono, $psono, $sono, $sono, $likeSono, $likeSono);
        $soStmt->execute();
        $orderInfo = $soStmt->get_result()->fetch_assoc();
        $soStmt->close();
    }
    if ((!$orderInfo || empty($orderInfo['clientname'])) && !empty($psono)) {
        $psoStmt = $krfconn->prepare("SELECT clientname, mobileno, psono, sono, clientid FROM krf_provisional_sales_order WHERE psono = ? LIMIT 1");
        if ($psoStmt) {
            $psoStmt->bind_param("s", $psono);
            $psoStmt->execute();
            $psoInfo = $psoStmt->get_result()->fetch_assoc();
            $psoStmt->close();
            if ($psoInfo) {
                if (!$orderInfo) {
                    $orderInfo = $psoInfo;
                } else {
                    $orderInfo['clientname'] = !empty($psoInfo['clientname']) ? $psoInfo['clientname'] : ($orderInfo['clientname'] ?? '');
                    $orderInfo['mobileno'] = !empty($psoInfo['mobileno']) ? $psoInfo['mobileno'] : ($orderInfo['mobileno'] ?? '');
                    $orderInfo['clientid'] = !empty($psoInfo['clientid']) ? $psoInfo['clientid'] : ($orderInfo['clientid'] ?? '');
                }
            }
        }
    }
    if ($orderInfo && empty($orderInfo['clientname']) && !empty($orderInfo['clientid'])) {
        $cmStmt = $krfconn->prepare("SELECT client_name, phones FROM krf_client_master WHERE client_id = ? LIMIT 1");
        if ($cmStmt) {
            $cmStmt->bind_param("s", $orderInfo['clientid']);
            $cmStmt->execute();
            $cmRow = $cmStmt->get_result()->fetch_assoc();
            $cmStmt->close();
            if ($cmRow) {
                $orderInfo['clientname'] = $cmRow['client_name'] ?? '';
                if (empty($orderInfo['mobileno'])) {
                    $orderInfo['mobileno'] = $cmRow['phones'] ?? '';
                }
            }
        }
    }

    // Fetch all active invoices for this psono / sono
    $invoices = [];
    $siSql = "
        SELECT 
            si.id,
            si.invoiceno,
            si.invoicedate,
            si.invoiceamount,
            si.payableamount,
            si.pickuptype,
            si.vehicleno,
            si.drivername,
            si.status,
            si.created_at,
            COALESCE(SUM(CASE WHEN (sii.productid != 'BOX_PACKING' AND sii.is_box_packing = 0) THEN sii.quantity ELSE 0 END), 0) AS total_plants,
            COALESCE(SUM(CASE WHEN (sii.productid = 'BOX_PACKING' OR sii.is_box_packing = 1) THEN sii.quantity ELSE 0 END), 0) AS box_count
        FROM sales_invoice si
        LEFT JOIN sales_invoice_items sii ON (sii.invoice_id = si.id OR sii.invoiceno = si.invoiceno) AND LOWER(IFNULL(sii.status, '')) != 'cancelled'
        WHERE ((si.psono = ? AND ? != '') OR (si.sono = ? AND ? != '') OR (si.sono LIKE ? AND ? != ''))
          AND LOWER(IFNULL(si.status, '')) != 'cancelled'
          AND (si.tedit IS NULL OR si.tedit = '' OR si.tedit = '0')
        GROUP BY si.id
        ORDER BY si.id DESC
    ";
    $siStmt = $krfconn->prepare($siSql);
    if ($siStmt) {
        $siStmt->bind_param("ssssss", $psono, $psono, $sono, $sono, $likeSono, $likeSono);
        $siStmt->execute();
        $siRes = $siStmt->get_result();
        while ($inv = $siRes->fetch_assoc()) {
            $inv['formatted_date'] = !empty($inv['invoicedate']) ? date('d-M-Y', strtotime($inv['invoicedate'])) : (!empty($inv['created_at']) ? date('d-M-Y', strtotime($inv['created_at'])) : '-');
            $invoices[] = $inv;
        }
        $siStmt->close();
    }

    echo json_encode([
        "status" => "success",
        "data" => [
            "order" => $orderInfo,
            "invoices" => $invoices,
            "count" => count($invoices)
        ]
    ]);
    exit;

} else if ($action == "UpdateDispatchInvoiceStep1Products") {
    $projectid = intval($_POST['projectid'] ?? 0);
    $psono = trim($_POST['psono'] ?? '');
    $sono = trim($_POST['sono'] ?? '');
    $clientname = trim($_POST['clientname'] ?? '');
    $emailid = trim($_POST['emailid'] ?? '');
    $mobileno = trim($_POST['mobileno'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $delivery_address = trim($_POST['delivery_address'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');
    $payment_terms = trim($_POST['payment_terms'] ?? '');
    $loginid = trim($_POST['loginid'] ?? 'Admin');

    $itemsJson = $_POST['items_json'] ?? '';
    $items = !empty($itemsJson) ? json_decode($itemsJson, true) : [];

    if (empty($items) || !is_array($items)) {
        echo json_encode(["status" => "error", "message" => "No product items provided to update."]);
        exit;
    }

    $krfconn->begin_transaction();
    try {
        $totalTaxable = 0;
        $totalTax = 0;
        $totalAmount = 0;

        foreach ($items as $it) {
            $itemId = intval($it['soi_id'] ?? 0);
            $pId = strval($it['product_id'] ?? '');
            $stkNo = trim($it['stockorder_no'] ?? '');
            $oQty = intval($it['order_qty'] ?? 0);
            $aQty = intval($it['allocate_ordqty'] ?? 0);
            $hPlant = intval($it['healthyplant'] ?? 0);
            $uPlant = intval($it['unhealthyplant'] ?? 0);
            $isSh = trim($it['isshifted'] ?? 'no');
            $isShStr = ($isSh === 'yes' || $isSh === '1' || $isSh === 1) ? 'yes' : 'no';
            $isShInt = ($isShStr === 'yes') ? 1 : 0;
            $pExtra = ($isShStr === 'yes') ? 0 : floatval($it['percentextrashifting'] ?? 0);
            $sQty = ($isShStr === 'yes') ? 0 : intval($it['shiftingqty'] ?? 0);

            $iRate = floatval($it['rate'] ?? 0);
            $iDiscPct = floatval($it['discount_percent'] ?? 0);
            $iDiscAmt = floatval($it['discount_amount'] ?? 0);
            $iTaxable = floatval($it['taxableamount'] ?? 0);
            $iCgstP = floatval($it['cgst_percent'] ?? 0);
            $iCgstA = floatval($it['cgst_amount'] ?? 0);
            $iSgstP = floatval($it['sgst_percent'] ?? 0);
            $iSgstA = floatval($it['sgst_amount'] ?? 0);
            $iIgstP = floatval($it['igst_percent'] ?? 0);
            $iIgstA = floatval($it['igst_amount'] ?? 0);
            $iTaxAmt = floatval($it['tax_amount'] ?? ($iCgstA + $iSgstA + $iIgstA));
            $iAmount = floatval($it['amount'] ?? ($iTaxable + $iTaxAmt));

            $totalTaxable += $iTaxable;
            $totalTax += $iTaxAmt;
            $totalAmount += $iAmount;

            // 1. Update provisional item in krf_sales_order_items
            $updSoi = $krfconn->prepare("
                UPDATE krf_sales_order_items SET
                    allocate_ordqty = ?,
                    healthyplant = ?,
                    unhealthyplant = ?,
                    isshifted = ?,
                    percentextrashifting = ?,
                    shiftingqty = ?,
                    rate = ?,
                    taxableamount = ?,
                    discount_percent = ?,
                    discount_amount = ?,
                    cgst_percent = ?,
                    cgst_amount = ?,
                    sgst_percent = ?,
                    sgst_amount = ?,
                    igst_percent = ?,
                    igst_amount = ?,
                    tax_amount = ?,
                    amount = ?,
                    ammendedby = ?,
                    updateamended = NOW()
                WHERE " . ($itemId > 0 ? "id = ?" : "(psono = ? AND stockorder_no = ?)") . "
            ");
            if ($updSoi) {
                if ($itemId > 0) {
                    $updSoi->bind_param(
                        "iiisdiddddddddddddsi",
                        $aQty,
                        $hPlant,
                        $uPlant,
                        $isShStr,
                        $pExtra,
                        $sQty,
                        $iRate,
                        $iTaxable,
                        $iDiscPct,
                        $iDiscAmt,
                        $iCgstP,
                        $iCgstA,
                        $iSgstP,
                        $iSgstA,
                        $iIgstP,
                        $iIgstA,
                        $iTaxAmt,
                        $iAmount,
                        $loginid,
                        $itemId
                    );
                } else {
                    $updSoi->bind_param(
                        "iiisdiddddddddddddsss",
                        $aQty,
                        $hPlant,
                        $uPlant,
                        $isShStr,
                        $pExtra,
                        $sQty,
                        $iRate,
                        $iTaxable,
                        $iDiscPct,
                        $iDiscAmt,
                        $iCgstP,
                        $iCgstA,
                        $iSgstP,
                        $iSgstA,
                        $iIgstP,
                        $iIgstA,
                        $iTaxAmt,
                        $iAmount,
                        $loginid,
                        $psono,
                        $stkNo
                    );
                }
                $updSoi->execute();
                $updSoi->close();
            }

            // Update delivery date if provided in item data
            if (!empty($it['deliverydate'])) {
                $itemDelDate = date('Y-m-d', strtotime($it['deliverydate']));
                $updDelDate = $itemId > 0 
                    ? $krfconn->prepare("UPDATE krf_sales_order_items SET deliverydate = ?, confirmdeliverydate = ?, ammendedby = ?, updateamended = NOW() WHERE id = ?")
                    : $krfconn->prepare("UPDATE krf_sales_order_items SET deliverydate = ?, confirmdeliverydate = ?, ammendedby = ?, updateamended = NOW() WHERE psono = ? AND stockorder_no = ?");
                if ($updDelDate) {
                    if ($itemId > 0) {
                        $updDelDate->bind_param("sssi", $itemDelDate, $itemDelDate, $loginid, $itemId);
                    } else {
                        $updDelDate->bind_param("sssss", $itemDelDate, $itemDelDate, $loginid, $psono, $stkNo);
                    }
                    $updDelDate->execute();
                    $updDelDate->close();
                }
            }

            // 2. Update consumed table (match by specific stock order number, not broadly by product_id)
            $updPoc = $krfconn->prepare("
                UPDATE krf_productionorder_consumed SET
                    consumed_qty = ?,
                    allocate_healthyplant = ?,
                    allocate_unhealthyplant = ?,
                    isshifted = ?,
                    percentextrashifting = ?,
                    extraqtyforshifting = ?,
                    updated_at = NOW()
                WHERE ((sono = ? AND stockorderno = ?) OR (psono = ? AND stockorderno = ?))
                   OR (allocated_stockorderno = ? AND (sono = ? OR psono = ?))
            ");
            if ($updPoc) {
                $updPoc->bind_param(
                    "diiidisssssss",
                    $aQty,
                    $hPlant,
                    $uPlant,
                    $isShInt,
                    $pExtra,
                    $sQty,
                    $sono,
                    $stkNo,
                    $psono,
                    $stkNo,
                    $stkNo,
                    $sono,
                    $psono
                );
                $updPoc->execute();
                $updPoc->close();
            }

            // Update krf_readystock_allocation for this batch/order
            $updRsaStep1 = $krfconn->prepare("
                UPDATE krf_readystock_allocation
                SET allocate_ordqty = ?,
                    healthyplant = ?,
                    unhealthyplant = ?,
                    isshifted = ?,
                    percentextrashifting = ?,
                    shiftingqty = ?,
                    status = CASE WHEN LOWER(IFNULL(status, '')) != 'dispatched' THEN 'readytoload' ELSE status END,
                    updated_at = NOW(),
                    updated_by = ?
                WHERE (
                    ((psono = ? AND ? != '') AND (productid = ? OR productname = ?))
                    OR ((allocate_stockorder_no = ? OR stockorder_no = ?) AND ? != '')
                )
                AND (tedit IS NULL OR tedit = '' OR tedit = '0')
                AND LOWER(IFNULL(status, '')) != 'cancel'
            ");
            if ($updRsaStep1) {
                $itProdName = !empty($it['product_name']) ? trim($it['product_name']) : '';
                $updRsaStep1->bind_param(
                    "iiisdissssssss",
                    $aQty,
                    $hPlant,
                    $uPlant,
                    $isShStr,
                    $pExtra,
                    $sQty,
                    $loginid,
                    $psono,
                    $psono,
                    $pId,
                    $itProdName,
                    $stkNo,
                    $stkNo,
                    $stkNo
                );
                $updRsaStep1->execute();
                $updRsaStep1->close();
            }
        }

        // 3. Update provisional sales order header (WITHOUT modifying invoicetype - keeps 'sale')
        // Pre-resolve header fallback values in PHP to avoid MySQL collation mismatch (utf8mb4 vs latin1) with COALESCE
        $curSoStmt = $krfconn->prepare("
            SELECT clientname, emailid, mobileno, address, delivery_address, remarks, payment_terms 
            FROM krf_sales_order 
            WHERE ((psono = ? AND ? != '') OR (sono = ? AND ? != ''))
              AND (invoicetype = 'sale' OR invoicetype IS NULL OR invoicetype = '')
            LIMIT 1
        ");
        $curSo = null;
        if ($curSoStmt) {
            $curSoStmt->bind_param("ssss", $psono, $psono, $sono, $sono);
            $curSoStmt->execute();
            $curSo = $curSoStmt->get_result()->fetch_assoc();
            $curSoStmt->close();
        }

        $effClientname = ($clientname !== '') ? $clientname : ($curSo['clientname'] ?? '');
        $effEmailid = ($emailid !== '') ? $emailid : ($curSo['emailid'] ?? '');
        $effMobileno = ($mobileno !== '') ? $mobileno : ($curSo['mobileno'] ?? '');
        $effAddress = ($address !== '') ? $address : ($curSo['address'] ?? '');
        $effDeliveryAddress = ($delivery_address !== '') ? $delivery_address : ($curSo['delivery_address'] ?? '');
        $effRemarks = ($remarks !== '') ? $remarks : ($curSo['remarks'] ?? '');
        $effPaymentTerms = ($payment_terms !== '') ? $payment_terms : ($curSo['payment_terms'] ?? '');

        // Fetch payable freight amount from krf_freightcharges in PHP to avoid any SQL COALESCE / subquery collation mismatch
        $payableFreight = 0.00;
        $fcStmt = $krfconn->prepare("
            SELECT payable_freightamount 
            FROM krf_freightcharges 
            WHERE (psono = ? AND ? != '') OR (sono = ? AND ? != '') 
            ORDER BY id DESC LIMIT 1
        ");
        if ($fcStmt) {
            $fcStmt->bind_param("ssss", $psono, $psono, $sono, $sono);
            $fcStmt->execute();
            $fcRow = $fcStmt->get_result()->fetch_assoc();
            if ($fcRow && isset($fcRow['payable_freightamount']) && is_numeric($fcRow['payable_freightamount'])) {
                $payableFreight = floatval($fcRow['payable_freightamount']);
            }
            $fcStmt->close();
        }
        $effGrandTotal = floatval($totalAmount) + $payableFreight;

        $updSo = $krfconn->prepare("
            UPDATE krf_sales_order SET
                clientname = ?,
                emailid = ?,
                mobileno = ?,
                address = ?,
                delivery_address = ?,
                remarks = ?,
                payment_terms = ?,
                total_amount = ?,
                tax_amount = ?,
                grand_total = ?,
                updated_by = ?,
                updated_at = NOW()
            WHERE ((psono = ? AND ? != '') OR (sono = ? AND ? != ''))
              AND (invoicetype = 'sale' OR invoicetype IS NULL OR invoicetype = '')
        ");
        if ($updSo) {
            $updSo->bind_param(
                "sssssssdddsssss",
                $effClientname,
                $effEmailid,
                $effMobileno,
                $effAddress,
                $effDeliveryAddress,
                $effRemarks,
                $effPaymentTerms,
                $totalTaxable,
                $totalTax,
                $effGrandTotal,
                $loginid,
                $psono, $psono, $sono, $sono
            );
            $updSo->execute();
            $updSo->close();
        }

        $krfconn->commit();
        echo json_encode([
            "status" => "success",
            "message" => "Product Items & Shifting Allocation details updated successfully.",
            "total_taxable" => $totalTaxable,
            "total_tax" => $totalTax,
            "total_amount" => $totalAmount
        ]);
    } catch (Throwable $e) {
        $krfconn->rollback();
        echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
    }
    exit;

} else if ($action == "UpdateDispatchInvoiceStep2Freight") {
    $projectid = intval($_POST['projectid'] ?? 0);
    $psono = trim($_POST['psono'] ?? '');
    $sono = trim($_POST['sono'] ?? '');
    $clientid = trim($_POST['clientid'] ?? '');
    $loginid = trim($_POST['loginid'] ?? 'Admin');

    $freight_charges = floatval($_POST['freight_charges'] ?? 0);
    $freight_disc_pct = floatval($_POST['freight_discount_percent'] ?? 0);
    $freight_disc_amt = floatval($_POST['freight_discount_amount'] ?? 0);
    $freight_narration = trim($_POST['freight_narration'] ?? $_POST['remarks'] ?? '');
    $free_dist = floatval($_POST['free_distance'] ?? 0);
    $total_dist = floatval($_POST['total_distance'] ?? 0);
    $payable_dist = floatval($_POST['payable_distance'] ?? 0);

    // Vehicle fields
    $vehicle_id = intval($_POST['vehicle_id'] ?? 0);
    $vehicleno = strtoupper(trim($_POST['vehicleno'] ?? ''));
    $vehiclename = trim($_POST['vehiclename'] ?? '');
    $vehicleownername = trim($_POST['vehicleownername'] ?? '');
    $driver_id = trim($_POST['driver_id'] ?? '');
    $drivername = trim($_POST['drivername'] ?? '');
    $drivermobileno = trim($_POST['drivermobileno'] ?? '');
    $scheduled_dispatch_date = !empty($_POST['scheduled_dispatch_date']) ? trim($_POST['scheduled_dispatch_date']) : null;
    $transport_remarks = trim($_POST['transport_remarks'] ?? '');

    $rawDelType = trim($_POST['dinv_delivery_type'] ?? $_POST['delivery_type'] ?? $_POST['pickuptype'] ?? 'delivered');
    $isPickupMode = in_array(strtolower($rawDelType), ['pickup', 'client_pickup', 'pickupbyclient']);
    $isBoxPackingMode = (strtolower($rawDelType) === 'boxpacking');
    $normPickupType = $isPickupMode ? 'pickup' : ($isBoxPackingMode ? 'boxpacking' : 'delivered');

    $krfconn->begin_transaction();
    try {
        // 1. Update krf_freightcharges
        $updFc = $krfconn->prepare("
            UPDATE krf_freightcharges SET
                payable_freightamount = ?,
                freight_amount = ?,
                freight_discountpercent = ?,
                freight_discountamount = ?,
                remarks = CASE WHEN ? != '' THEN ? ELSE remarks END,
                updated_at = NOW()
            WHERE (psono = ? AND ? != '') OR (sono = ? AND ? != '')
        ");
        if ($updFc) {
            $updFc->bind_param("ddddssssss", $freight_charges, $freight_charges, $freight_disc_pct, $freight_disc_amt, $freight_narration, $freight_narration, $psono, $psono, $sono, $sono);
            $updFc->execute();
            $updFc->close();
        }

        // 2. Update or Insert krf_deliveryaddress_location
        $chkDl = $krfconn->prepare("SELECT id, pickuptype FROM krf_deliveryaddress_location WHERE ((psono = ? AND ? != '') OR (sono = ? AND ? != '')) AND (tedit IS NULL OR tedit = '') ORDER BY id DESC LIMIT 1");
        $chkDl->bind_param("ssss", $psono, $psono, $sono, $sono);
        $chkDl->execute();
        $dlRow = $chkDl->get_result()->fetch_assoc();
        $chkDl->close();

        if ($dlRow && !empty($dlRow['id'])) {
            if ($isPickupMode) {
                $updDl = $krfconn->prepare("
                    UPDATE krf_deliveryaddress_location SET
                        pickuptype = 'pickup',
                        transporttype = 'Self Pickup By Farmer/Client',
                        vehicleno = 'Self Pickup',
                        distance = 0,
                        totaldistance = 0,
                        freedistance = 0,
                        freightamt = 0,
                        deliverycharges = 0,
                        remark = CASE WHEN ? != '' THEN ? ELSE remark END,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $updDl->bind_param("ssi", $freight_narration, $freight_narration, $dlRow['id']);
                $updDl->execute();
                $updDl->close();
            } else if ($isBoxPackingMode) {
                $updDl = $krfconn->prepare("
                    UPDATE krf_deliveryaddress_location SET
                        pickuptype = 'boxpacking',
                        freightamt = ?,
                        remark = CASE WHEN ? != '' THEN ? ELSE remark END,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $updDl->bind_param("dssi", $freight_charges, $freight_narration, $freight_narration, $dlRow['id']);
                $updDl->execute();
                $updDl->close();
            } else {
                $updDl = $krfconn->prepare("
                    UPDATE krf_deliveryaddress_location SET
                        pickuptype = 'delivered',
                        freedistance = CASE WHEN ? > 0 THEN ? ELSE freedistance END,
                        totaldistance = CASE WHEN ? > 0 THEN ? ELSE totaldistance END,
                        distance = CASE WHEN ? > 0 THEN ? ELSE distance END,
                        remark = CASE WHEN ? != '' THEN ? ELSE remark END,
                        freightamt = ?,
                        vehicleno = CASE WHEN ? != '' THEN ? ELSE vehicleno END,
                        drivername = CASE WHEN ? != '' THEN ? ELSE drivername END,
                        drivercontactno = CASE WHEN ? != '' THEN ? ELSE drivercontactno END,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $updDl->bind_param(
                    "ddddddssdssssssi",
                    $free_dist, $free_dist,
                    $total_dist, $total_dist,
                    $payable_dist, $payable_dist,
                    $freight_narration, $freight_narration,
                    $freight_charges,
                    $vehicleno, $vehicleno,
                    $drivername, $drivername,
                    $drivermobileno, $drivermobileno,
                    $dlRow['id']
                );
                $updDl->execute();
                $updDl->close();
            }
        }

        // 3. Save or update vehicle trip and history
        if ($isPickupMode) {
            saveOrUpdateVehicleTripAndHistory($krfconn, [
                'projectid' => $projectid,
                'sono' => $sono,
                'psono' => $psono,
                'clientid' => $clientid,
                'vehicle_id' => 0,
                'vehicleno' => 'Self Pickup',
                'driver_id' => '0',
                'drivername' => 'Self (Farmer/Client)',
                'drivermobileno' => '',
                'deliverydate' => $scheduled_dispatch_date,
                'total_allocated_plants' => 0,
                'remarks' => 'Self Pickup By Farmer/Client',
                'loginid' => $loginid,
                'status' => 'Planned'
            ]);
        } else if (!empty($vehicleno) || $vehicle_id > 0) {
            saveOrUpdateVehicleTripAndHistory($krfconn, [
                'projectid' => $projectid,
                'sono' => $sono,
                'psono' => $psono,
                'clientid' => $clientid,
                'vehicle_id' => $vehicle_id,
                'vehicleno' => $vehicleno,
                'driver_id' => $driver_id,
                'drivername' => $drivername,
                'drivermobileno' => $drivermobileno,
                'deliverydate' => $scheduled_dispatch_date,
                'total_allocated_plants' => 0,
                'remarks' => $transport_remarks,
                'loginid' => $loginid,
                'status' => 'Planned'
            ]);
        }

        // 4. Update provisional sales order remarks
        if (!empty($freight_narration)) {
            $updSoRem = $krfconn->prepare("UPDATE krf_sales_order SET remarks = ?, updated_at = NOW() WHERE ((psono = ? AND ? != '') OR (sono = ? AND ? != '')) AND invoicetype = 'sale'");
            if ($updSoRem) {
                $updSoRem->bind_param("sssss", $freight_narration, $psono, $psono, $sono, $sono);
                $updSoRem->execute();
                $updSoRem->close();
            }
        }

        // 5. Update items deliverydate and confirmdeliverydate if unified date or item dates sent
        $itemsDatesJson = $_POST['items_dates_json'] ?? '';
        $itemsDates = !empty($itemsDatesJson) ? json_decode($itemsDatesJson, true) : [];
        if (!empty($itemsDates) && is_array($itemsDates)) {
            foreach ($itemsDates as $idat) {
                $soiId = intval($idat['soi_id'] ?? 0);
                $pId = strval($idat['product_id'] ?? '');
                $dDate = !empty($idat['deliverydate']) ? date('Y-m-d', strtotime($idat['deliverydate'])) : '';
                if (!empty($dDate)) {
                    if ($soiId > 0) {
                        $updItemDate = $krfconn->prepare("UPDATE krf_sales_order_items SET deliverydate = ?, confirmdeliverydate = ?, ammendedby = ?, updateamended = NOW() WHERE id = ?");
                        if ($updItemDate) {
                            $updItemDate->bind_param("sssi", $dDate, $dDate, $loginid, $soiId);
                            $updItemDate->execute();
                            $updItemDate->close();
                        }
                    } else {
                        $updItemDate = $krfconn->prepare("UPDATE krf_sales_order_items SET deliverydate = ?, confirmdeliverydate = ?, ammendedby = ?, updateamended = NOW() WHERE psono = ? AND product_id = ?");
                        if ($updItemDate) {
                            $updItemDate->bind_param("ssss", $dDate, $dDate, $psono, $pId);
                            $updItemDate->execute();
                            $updItemDate->close();
                        }
                    }
                }
            }
        } else if (!empty($scheduled_dispatch_date)) {
            $parsedSchedDate = date('Y-m-d', strtotime($scheduled_dispatch_date));
            $updAllItemDates = $krfconn->prepare("UPDATE krf_sales_order_items SET deliverydate = ?, confirmdeliverydate = ?, ammendedby = ?, updateamended = NOW() WHERE ((psono = ? AND ? != '') OR (sono = ? AND ? != '')) AND (invoiceno IS NULL OR invoiceno = '')");
            if ($updAllItemDates) {
                $updAllItemDates->bind_param("sssssss", $parsedSchedDate, $parsedSchedDate, $loginid, $psono, $psono, $sono, $sono);
                $updAllItemDates->execute();
                $updAllItemDates->close();
            }
        }

        $krfconn->commit();
        echo json_encode([
            "status" => "success",
            "message" => "Freight Charge Details updated successfully.",
            "freight_charges" => $freight_charges,
            "pickuptype" => $normPickupType
        ]);
    } catch (Throwable $e) {
        $krfconn->rollback();
        echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
    }
    exit;

} else if ($action == "ReloadFreightAndVehicleAllocation" || $action == "UpdateFreightAndVehicleAllocation") {
    $projectid = intval($_POST['projectid'] ?? 0);
    $psono = trim($_POST['psono'] ?? '');
    $sono = trim($_POST['sono'] ?? '');
    $clientid = trim($_POST['clientid'] ?? '');
    $loginid = trim($_POST['loginid'] ?? 'Admin');

    $effectivePsono = $psono;
    $effectiveSono = $sono;

    if (empty($effectiveSono) && !empty($effectivePsono)) {
        $sQ = $krfconn->prepare("SELECT sono, clientid, projectid FROM krf_sales_order WHERE (psono = ? OR sono = ?) LIMIT 1");
        if ($sQ) {
            $sQ->bind_param("ss", $effectivePsono, $effectivePsono);
            $sQ->execute();
            $sR = $sQ->get_result()->fetch_assoc();
            if ($sR) {
                $effectiveSono = $sR['sono'] ?? '';
                if (empty($clientid)) $clientid = $sR['clientid'] ?? '';
                if ($projectid <= 0) $projectid = intval($sR['projectid'] ?? 0);
            }
            $sQ->close();
        }
    }
    if (empty($effectivePsono) && !empty($effectiveSono)) {
        $pQ = $krfconn->prepare("SELECT psono, clientid, projectid FROM krf_sales_order WHERE sono = ? LIMIT 1");
        if ($pQ) {
            $pQ->bind_param("s", $effectiveSono);
            $pQ->execute();
            $pR = $pQ->get_result()->fetch_assoc();
            if ($pR) {
                $effectivePsono = $pR['psono'] ?? '';
                if (empty($clientid)) $clientid = $pR['clientid'] ?? '';
                if ($projectid <= 0) $projectid = intval($pR['projectid'] ?? 0);
            }
            $pQ->close();
        }
    }

    // Check if update values are provided
    $isUpdate = isset($_POST['freight_amount']) || isset($_POST['payable_freight']) || isset($_POST['freight_charges']) || isset($_POST['vehicleno']) || isset($_POST['vehicle_id']) || isset($_POST['pickuptype']) || isset($_POST['dinv_delivery_type']);

    if ($isUpdate) {
        $freight_charges = floatval($_POST['freight_charges'] ?? $_POST['freight_amount'] ?? 0);
        $payable_freight = floatval($_POST['payable_freight'] ?? $_POST['payable_freightamount'] ?? $freight_charges);
        $freight_disc_pct = floatval($_POST['freight_discount_percent'] ?? 0);
        $freight_disc_amt = floatval($_POST['freight_discount_amount'] ?? 0);
        $freight_narration = trim($_POST['freight_narration'] ?? $_POST['remarks'] ?? '');
        $free_dist = floatval($_POST['free_distance'] ?? 0);
        $total_dist = floatval($_POST['total_distance'] ?? 0);
        $payable_dist = floatval($_POST['payable_distance'] ?? 0);

        $vehicle_id = intval($_POST['vehicle_id'] ?? 0);
        $vehicleno = strtoupper(trim($_POST['vehicleno'] ?? ''));
        $vehiclename = trim($_POST['vehiclename'] ?? '');
        $vehicleownername = trim($_POST['vehicleownername'] ?? '');
        $driver_id = trim($_POST['driver_id'] ?? '');
        $drivername = trim($_POST['drivername'] ?? '');
        $drivermobileno = trim($_POST['drivermobileno'] ?? '');
        $scheduled_dispatch_date = !empty($_POST['scheduled_dispatch_date']) ? trim($_POST['scheduled_dispatch_date']) : null;
        $transport_remarks = trim($_POST['transport_remarks'] ?? '');

        $rawDelType = trim($_POST['dinv_delivery_type'] ?? $_POST['delivery_type'] ?? $_POST['pickuptype'] ?? '');
        $isPickupMode = in_array(strtolower($rawDelType), ['pickup', 'client_pickup', 'pickupbyclient']);
        $isBoxPackingMode = (strtolower($rawDelType) === 'boxpacking');
        $normPickupType = $isPickupMode ? 'pickup' : ($isBoxPackingMode ? 'boxpacking' : 'delivered');

        // Update or insert krf_freightcharges
        if ($freight_charges >= 0) {
            $chkFc = $krfconn->prepare("SELECT id FROM krf_freightcharges WHERE ((psono = ? AND ? != '') OR (sono = ? AND ? != '')) ORDER BY id DESC LIMIT 1");
            $chkFc->bind_param("ssss", $effectivePsono, $effectivePsono, $effectiveSono, $effectiveSono);
            $chkFc->execute();
            $fcRow = $chkFc->get_result()->fetch_assoc();
            $chkFc->close();

            if ($fcRow && !empty($fcRow['id'])) {
                $updFc = $krfconn->prepare("
                    UPDATE krf_freightcharges SET
                        payable_freightamount = ?,
                        freight_amount = ?,
                        freight_discountpercent = ?,
                        freight_discountamount = ?,
                        remarks = CASE WHEN ? != '' THEN ? ELSE remarks END,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                if ($updFc) {
                    $updFc->bind_param("ddddssi", $payable_freight, $freight_charges, $freight_disc_pct, $freight_disc_amt, $freight_narration, $freight_narration, $fcRow['id']);
                    $updFc->execute();
                    $updFc->close();
                }
            } else {
                $insFc = $krfconn->prepare("
                    INSERT INTO krf_freightcharges (
                        projectid, psono, sono, freight_amount, freight_discountpercent, freight_discountamount, payable_freightamount, remarks, status, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Active', NOW())
                ");
                if ($insFc) {
                    $insFc->bind_param("issdddds", $projectid, $effectivePsono, $effectiveSono, $freight_charges, $freight_disc_pct, $freight_disc_amt, $payable_freight, $freight_narration);
                    $insFc->execute();
                    $insFc->close();
                }
            }
        }

        // Update krf_deliveryaddress_location
        $chkDl = $krfconn->prepare("SELECT id, pickuptype FROM krf_deliveryaddress_location WHERE ((psono = ? AND ? != '') OR (sono = ? AND ? != '')) AND (tedit IS NULL OR tedit = '') ORDER BY id DESC LIMIT 1");
        $chkDl->bind_param("ssss", $effectivePsono, $effectivePsono, $effectiveSono, $effectiveSono);
        $chkDl->execute();
        $dlRow = $chkDl->get_result()->fetch_assoc();
        $chkDl->close();

        if ($dlRow && !empty($dlRow['id'])) {
            if ($isPickupMode) {
                $updDl = $krfconn->prepare("
                    UPDATE krf_deliveryaddress_location SET
                        pickuptype = 'pickup',
                        transporttype = 'Self Pickup By Farmer/Client',
                        vehicleno = 'Self Pickup',
                        distance = 0,
                        totaldistance = 0,
                        freedistance = 0,
                        freightamt = 0,
                        deliverycharges = 0,
                        remark = CASE WHEN ? != '' THEN ? ELSE remark END,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                if ($updDl) {
                    $updDl->bind_param("ssi", $freight_narration, $freight_narration, $dlRow['id']);
                    $updDl->execute();
                    $updDl->close();
                }
            } else if ($isBoxPackingMode) {
                $updDl = $krfconn->prepare("
                    UPDATE krf_deliveryaddress_location SET
                        pickuptype = 'boxpacking',
                        freightamt = ?,
                        deliverycharges = ?,
                        remark = CASE WHEN ? != '' THEN ? ELSE remark END,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                if ($updDl) {
                    $updDl->bind_param("ddssi", $payable_freight, $freight_charges, $freight_narration, $freight_narration, $dlRow['id']);
                    $updDl->execute();
                    $updDl->close();
                }
            } else {
                $updDl = $krfconn->prepare("
                    UPDATE krf_deliveryaddress_location SET
                        pickuptype = 'delivered',
                        freedistance = CASE WHEN ? > 0 THEN ? ELSE freedistance END,
                        totaldistance = CASE WHEN ? > 0 THEN ? ELSE totaldistance END,
                        distance = CASE WHEN ? > 0 THEN ? ELSE distance END,
                        remark = CASE WHEN ? != '' THEN ? ELSE remark END,
                        freightamt = ?,
                        deliverycharges = ?,
                        vehicleno = CASE WHEN ? != '' THEN ? ELSE vehicleno END,
                        drivername = CASE WHEN ? != '' THEN ? ELSE drivername END,
                        drivercontactno = CASE WHEN ? != '' THEN ? ELSE drivercontactno END,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                if ($updDl) {
                    $updDl->bind_param(
                        "ddddddssddssssssi",
                        $free_dist, $free_dist,
                        $total_dist, $total_dist,
                        $payable_dist, $payable_dist,
                        $freight_narration, $freight_narration,
                        $payable_freight, $freight_charges,
                        $vehicleno, $vehicleno,
                        $drivername, $drivername,
                        $drivermobileno, $drivermobileno,
                        $dlRow['id']
                    );
                    $updDl->execute();
                    $updDl->close();
                }
            }
        }

        // Save or update vehicle trip and history
        if ($isPickupMode) {
            saveOrUpdateVehicleTripAndHistory($krfconn, [
                'projectid' => $projectid,
                'sono' => $effectiveSono,
                'psono' => $effectivePsono,
                'clientid' => $clientid,
                'vehicle_id' => 0,
                'vehicleno' => 'Self Pickup',
                'driver_id' => '0',
                'drivername' => 'Self (Farmer/Client)',
                'drivermobileno' => '',
                'deliverydate' => $scheduled_dispatch_date,
                'total_allocated_plants' => 0,
                'remarks' => 'Self Pickup By Farmer/Client',
                'loginid' => $loginid,
                'status' => 'Planned'
            ]);
        } else if (!empty($vehicleno) || $vehicle_id > 0) {
            saveOrUpdateVehicleTripAndHistory($krfconn, [
                'projectid' => $projectid,
                'sono' => $effectiveSono,
                'psono' => $effectivePsono,
                'clientid' => $clientid,
                'vehicle_id' => $vehicle_id,
                'vehicleno' => $vehicleno,
                'driver_id' => $driver_id,
                'drivername' => $drivername,
                'drivermobileno' => $drivermobileno,
                'deliverydate' => $scheduled_dispatch_date,
                'total_allocated_plants' => 0,
                'remarks' => $transport_remarks,
                'loginid' => $loginid,
                'status' => 'Planned'
            ]);
        }
    }

    // Query latest records to return
    // 1. Fetch latest delivery location
    $delLocSql = "
        SELECT id, psono, sono, clientid, productid, deliveryqty, deliverycharges, transporttype, 
               dest_state, dest_district, dest_city, dest_pincode, deliveryaddress, 
               totaldistance, freedistance, distance, remark, pickuppoint, pickuptype, 
               noofbox, freightamt, boxperpack, chargesperbox, stateid, districtid, vehicleno, drivername, drivercontactno, dispatch_datetime
        FROM krf_deliveryaddress_location 
        WHERE ((psono = ? AND ? != '') OR (sono = ? AND ? != '')) AND (tedit IS NULL OR tedit = '')
        ORDER BY id DESC LIMIT 1
    ";
    $dlStmt = $krfconn->prepare($delLocSql);
    $dlStmt->bind_param("ssss", $effectivePsono, $effectivePsono, $effectiveSono, $effectiveSono);
    $dlStmt->execute();
    $delLoc = $dlStmt->get_result()->fetch_assoc();
    $dlStmt->close();

    // Fetch all delivery locations
    $allLocs = [];
    $allLocsSql = "
        SELECT id, psono, sono, clientid, productid, deliveryqty, deliverycharges, transporttype, 
               dest_state, dest_district, dest_city, dest_pincode, deliveryaddress, 
               totaldistance, freedistance, distance, remark, pickuppoint, pickuptype, 
               noofbox, freightamt, boxperpack, chargesperbox, stateid, districtid, vehicleno, drivername, drivercontactno, dispatch_datetime
        FROM krf_deliveryaddress_location 
        WHERE ((psono = ? AND ? != '') OR (sono = ? AND ? != '')) AND (tedit IS NULL OR tedit = '')
        ORDER BY id ASC
    ";
    $allLocsStmt = $krfconn->prepare($allLocsSql);
    $allLocsStmt->bind_param("ssss", $effectivePsono, $effectivePsono, $effectiveSono, $effectiveSono);
    $allLocsStmt->execute();
    $allLocsRes = $allLocsStmt->get_result();
    while ($al = $allLocsRes->fetch_assoc()) {
        $allLocs[] = $al;
    }
    $allLocsStmt->close();

    // 2. Fetch latest freight charges
    $fcSql = "
        SELECT freight_amount, freight_discountpercent, freight_discountamount, payable_freightamount, zerocharge, remarks
        FROM krf_freightcharges 
        WHERE ((psono = ? AND ? != '') OR (sono = ? AND ? != ''))
        ORDER BY id DESC LIMIT 1
    ";
    $fcStmt = $krfconn->prepare($fcSql);
    $fcStmt->bind_param("ssss", $effectivePsono, $effectivePsono, $effectiveSono, $effectiveSono);
    $fcStmt->execute();
    $freightData = $fcStmt->get_result()->fetch_assoc();
    $fcStmt->close();

    // 3. Fetch latest vehicle allocation from krf_vehicle_travel_history & krf_vehicletrip
    $vehAlloc = null;
    $vaStmt = $krfconn->prepare("
        SELECT 
            vth.id,
            vth.projectid,
            vth.stockorder_no AS sono,
            vth.psono,
            '' AS soi_id,
            '' AS dinvoiceno,
            COALESCE(vth.vehicle_id, vt.vehicle_id, 0) AS vehicle_id,
            COALESCE(vth.vehicle_no, vt.vehicle_no, '') AS vehicleno,
            COALESCE(vm.vehiclename, '') AS vehiclename,
            COALESCE(vm.vehicleownername, '') AS vehicleownername,
            COALESCE(vth.driverid, vt.driver_id, '') AS driver_id,
            COALESCE(vth.driver_name, vt.driver_name, '') AS drivername,
            COALESCE(vth.driver_mobileno, vt.driver_mobile_no, '') AS drivermobileno,
            COALESCE(vth.deliverydate, vt.delivery_date) AS scheduled_dispatch_date,
            COALESCE(vm.plantscapacity, 0) AS plantscapacity,
            COALESCE(vth.send_totalplantcount, 0) AS total_allocated_plants,
            'vehicle_allocation' AS allocation_source,
            COALESCE(vt.status, 'Allocated') AS allocation_status,
            COALESCE(vth.send_remark, vt.remarks, '') AS remarks,
            COALESCE(vth.created_by, vt.created_by, '') AS allocated_by,
            DATE_FORMAT(COALESCE(vth.created_at, vt.created_at), '%d-%b-%Y %h:%i %p') AS allocated_at_formatted,
            COALESCE(vt.trip_no, vth.trip_id, '') AS trip_id
        FROM krf_vehicle_travel_history vth
        LEFT JOIN krf_vehicletrip vt ON (vt.trip_no = vth.trip_id OR vt.trip_id = vth.trip_id)
        LEFT JOIN krf_vehicle_master vm ON (vm.vehicleid = COALESCE(vth.vehicle_id, vt.vehicle_id) AND vm.vehicleid > 0)
        WHERE (vth.stockorder_no = ? AND ? != '') 
           OR (vth.psono = ? AND ? != '')
           OR (vth.stockorder_no = ? AND ? != '')
           OR (vth.psono = ? AND ? != '')
        ORDER BY vth.id DESC LIMIT 1
    ");
    $vaStmt->bind_param("ssssssss", $effectiveSono, $effectiveSono, $effectivePsono, $effectivePsono, $sono, $sono, $psono, $psono);
    $vaStmt->execute();
    $vehAlloc = $vaStmt->get_result()->fetch_assoc();
    $vaStmt->close();

    if ($vehAlloc && !empty($vehAlloc['allocated_by'])) {
        $uAlloc = $vehAlloc['allocated_by'];
        $uStmt = $authconn->prepare("SELECT loginname FROM login_user_master WHERE loginid = ? OR mobileno = ? LIMIT 1");
        if ($uStmt) {
            $uStmt->bind_param("ss", $uAlloc, $uAlloc);
            $uStmt->execute();
            $uRow = $uStmt->get_result()->fetch_assoc();
            if ($uRow && !empty($uRow['loginname'])) {
                $vehAlloc['allocated_by_name'] = $uRow['loginname'];
            }
            $uStmt->close();
        }
    }

    $isPickupDelivery = ($delLoc && in_array(strtolower(trim($delLoc['pickuptype'] ?? '')), ['pickup', 'client_pickup', 'pickupbyclient']));
    if (!$vehAlloc && ($isPickupDelivery || !empty($delLoc['vehicleno']) || !empty($delLoc['drivername']))) {
        $vehAlloc = [
            'id' => 0,
            'vehicle_id' => 0,
            'vehicleno' => $isPickupDelivery ? 'Self Pickup' : ($delLoc['vehicleno'] ?? ''),
            'vehiclename' => $isPickupDelivery ? 'Self Pickup' : '',
            'vehicleownername' => $isPickupDelivery ? 'Self Pickup By Farmer/Client' : '',
            'driver_id' => 0,
            'drivername' => $isPickupDelivery ? 'Self (Farmer/Client)' : ($delLoc['drivername'] ?? ''),
            'drivermobileno' => $delLoc['drivercontactno'] ?? '',
            'scheduled_dispatch_date' => !empty($delLoc['dispatch_datetime']) ? date('Y-m-d', strtotime($delLoc['dispatch_datetime'])) : null,
            'plantscapacity' => 0,
            'allocation_source' => $isPickupDelivery ? 'client_pickup' : 'delivery_location',
            'allocation_status' => 'Allocated',
            'remarks' => $delLoc['remark'] ?? '',
            'allocated_by' => '',
            'allocated_by_name' => '',
            'allocated_at_formatted' => ''
        ];
    }

    $origBase = floatval($freightData['freight_amount'] ?? ($delLoc['deliverycharges'] ?? ($delLoc['freightamt'] ?? 0)));
    $origPayable = floatval($freightData['payable_freightamount'] ?? ($delLoc['freightamt'] ?? $origBase));
    $origDiscPct = floatval($freightData['freight_discountpercent'] ?? 0);
    $origDiscAmt = floatval($freightData['freight_discountamount'] ?? 0);
    $origRemark = $delLoc['remark'] ?? ($freightData['remarks'] ?? '');

    echo json_encode([
        "status" => "success",
        "message" => "Freight and vehicle allocation reloaded successfully.",
        "data" => [
            "delivery_location" => $delLoc,
            "delivery_locations" => $allLocs,
            "freight_charges" => $freightData,
            "vehicle_allocation" => $vehAlloc,
            "original_base_freight" => $origBase,
            "original_payable_freight" => $origPayable,
            "original_discount_percent" => $origDiscPct,
            "original_discount_amount" => $origDiscAmt,
            "original_discount_remark" => $origRemark
        ]
    ]);
    exit;

} else if ($action == "SaveDispatchInvoice") {
    $projectid = intval($_POST['projectid'] ?? 0);
    $sono = trim($_POST['sono'] ?? '');
    $psono = trim($_POST['psono'] ?? '');
    $clientid = trim($_POST['clientid'] ?? '');
    $clientname = trim($_POST['clientname'] ?? '');
    $emailid = trim($_POST['emailid'] ?? '');
    $mobileno = trim($_POST['mobileno'] ?? '');
    $address = trim($_POST['address'] ?? $_POST['invoicing_address'] ?? '');
    $delivery_address = trim($_POST['delivery_address'] ?? '');
    $districtname = trim($_POST['districtname'] ?? '');
    $statename = trim($_POST['statename'] ?? '');
    $payment_terms = trim($_POST['payment_terms'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');
    $freight_charges = floatval($_POST['freight_charges'] ?? 0);
    $tax_amount = floatval($_POST['tax_amount'] ?? 0);
    $total_amount = floatval($_POST['total_amount'] ?? 0);
    $grand_total = floatval($_POST['grand_total'] ?? ($total_amount + $tax_amount + $freight_charges));
    $loginid = trim($_POST['loginid'] ?? 'Admin');

    // Vehicle Allocation fields
    $vehicle_id = intval($_POST['vehicle_id'] ?? 0);
    $vehicleno = strtoupper(trim($_POST['vehicleno'] ?? ''));
    $vehiclename = trim($_POST['vehiclename'] ?? '');
    $vehicleownername = trim($_POST['vehicleownername'] ?? '');
    $drivername = trim($_POST['drivername'] ?? '');
    $drivermobileno = trim($_POST['drivermobileno'] ?? '');
    $scheduled_dispatch_date = !empty($_POST['scheduled_dispatch_date']) ? trim($_POST['scheduled_dispatch_date']) : null;
    $transport_remarks = trim($_POST['transport_remarks'] ?? '');

    if (!empty($drivermobileno) && !preg_match('/^[6-9]\d{9}$/', $drivermobileno)) {
        echo json_encode(["status" => "error", "message" => "Please enter a valid 10-digit driver mobile number (e.g. 9876543210)."]);
        exit;
    }

    // Process Items
    $itemsJson = $_POST['items_json'] ?? '';
    $items = [];
    if (!empty($itemsJson)) {
        $items = json_decode($itemsJson, true) ?: [];
    }

    if (empty($items) && isset($_POST['soi_id'])) {
        $items[] = [
            'soi_id' => intval($_POST['soi_id']),
            'product_id' => strval($_POST['product_id'] ?? ''),
            'stockorder_no' => strval($_POST['stockorder_no'] ?? ''),
            'order_qty' => intval($_POST['order_qty'] ?? 0),
            'allocate_ordqty' => intval($_POST['allocate_ordqty'] ?? 0),
            'healthyplant' => intval($_POST['healthyplant'] ?? 0),
            'unhealthyplant' => intval($_POST['unhealthyplant'] ?? 0),
            'isshifted' => trim($_POST['isshifted'] ?? 'no'),
            'percentextrashifting' => floatval($_POST['percentextrashifting'] ?? 0),
            'shiftingqty' => intval($_POST['shiftingqty'] ?? 0),
            'rate' => floatval($_POST['rate'] ?? 0),
            'discount_percent' => floatval($_POST['discount_percent'] ?? 0),
            'discount_amount' => floatval($_POST['discount_amount'] ?? 0),
            'taxableamount' => floatval($_POST['taxableamount'] ?? 0),
            'cgst_percent' => floatval($_POST['cgst_percent'] ?? 0),
            'cgst_amount' => floatval($_POST['cgst_amount'] ?? 0),
            'sgst_percent' => floatval($_POST['sgst_percent'] ?? 0),
            'sgst_amount' => floatval($_POST['sgst_amount'] ?? 0),
            'igst_percent' => floatval($_POST['igst_percent'] ?? 0),
            'igst_amount' => floatval($_POST['igst_amount'] ?? 0),
            'freight_charges' => $freight_charges,
            'tax_amount' => $tax_amount,
            'amount' => floatval($_POST['amount'] ?? 0)
        ];
    }

    if (empty($sono) && empty($psono) && empty($items)) {
        echo json_encode(["status" => "error", "message" => "Invalid Sales Order Item or empty items list."]);
        exit;
    }

    // Strict Guard 1: Verify all payments with Account Department (no unverified payments & balance settled)
    $payChk = $krfconn->prepare("
        SELECT advanceamount, otherdiscount, roundoffamount, verification 
        FROM advance_payment_transactions 
        WHERE ((invoiceno = ? AND ? != '') 
           OR (invoiceno = ? AND ? != ''))
          AND (tedit IS NULL OR tedit = '')
    ");
    if ($payChk) {
        $payChk->bind_param("ssss", $psono, $psono, $sono, $sono);
        $payChk->execute();
        $payRes = $payChk->get_result();
        $unverifiedAmt = 0;
        $totalPaidAmt = 0;
        while ($pr = $payRes->fetch_assoc()) {
            $pAmt = floatval($pr['advanceamount'] ?? 0);
            $pDisc = floatval($pr['otherdiscount'] ?? 0);
            $totalPaidAmt += ($pAmt + $pDisc);
            if (strtolower(trim($pr['verification'] ?? '')) !== 'verified') {
                $unverifiedAmt += $pAmt;
            }
        }
        $payChk->close();

        if ($unverifiedAmt > 0.01) {
            echo json_encode([
                "status" => "error", 
                "message" => "Payment Verification Required: Found unverified payment of ₹ " . number_format($unverifiedAmt, 2) . ". Please contact Account Department to verify all payment transactions before generating dispatch invoice."
            ]);
            exit;
        }

        $balDue = round(max(0, $grand_total - $totalPaidAmt), 2);
        if ($balDue > 0.01) {
            echo json_encode([
                "status" => "error", 
                "message" => "Pending Balance Due: Cannot generate dispatch invoice while pending balance is ₹ " . number_format($balDue, 2) . ". Please clear the pending balance first."
            ]);
            exit;
        }
    }

    // Strict Guard 2: Verify polyhouse plant availability for every item
    foreach ($items as $chkIt) {
        $pId = strval($chkIt['product_id'] ?? '');
        $chkProd = trim($chkIt['product_name'] ?? ('Product #' . $pId));
        $isBoxPacking = !empty($chkIt['is_box_packing']) || ($pId === 'BOX_PACKING') || stripos($chkProd, 'Box Packing') !== false;
        if ($isBoxPacking) {
            continue; // Box packing charge is a service/packing line item, not a polyhouse crop plant
        }

        $chkStk = trim($chkIt['stockorder_no'] ?? '');
        
        if (empty($chkStk) || $chkStk === 'Not Allocated' || $chkStk === '0') {
            echo json_encode([
                "status" => "error", 
                "message" => "Cannot generate dispatch invoice: Item '{$chkProd}' is not allocated to any production stock order."
            ]);
            exit;
        }

        $stkList = array_filter(array_map('trim', explode(',', $chkStk)));
        $phQty = 0;
        $readyQty = 0;
        $trayCnt = 0;
        if (!empty($stkList)) {
            $escapedStks = array_map(function($s) use ($krfconn) { return "'" . mysqli_real_escape_string($krfconn, $s) . "'"; }, $stkList);
            $stkIn = implode(',', $escapedStks);
            $phResQ = $krfconn->query("
                SELECT 
                    COUNT(polyhouseplantid) AS polyhouse_tray_count,
                    SUM(CASE WHEN LOWER(IFNULL(health_status, '')) IN ('healthy','unhealthy') AND LOWER(IFNULL(status, '')) IN ('openstock', 'polyhouse', 'readytosale') THEN GREATEST(0, CAST(IFNULL(qtypertray, 0) AS SIGNED) - CAST(IFNULL(failurecount, 0) AS SIGNED)) ELSE 0 END) AS polyhouse_plant_qty,
                    SUM(CASE WHEN LOWER(IFNULL(health_status, '')) IN ('healthy','unhealthy') AND LOWER(IFNULL(status, '')) IN ('readytosale', 'openstock') THEN GREATEST(0, CAST(IFNULL(qtypertray, 0) AS SIGNED) - CAST(IFNULL(failurecount, 0) AS SIGNED)) ELSE 0 END) AS ready_plant_qty
                FROM krf_polyhouse_plant_details
                WHERE stockorder_no IN ({$stkIn})
            ");
            if ($phResQ && $phRes = $phResQ->fetch_assoc()) {
                $phQty = intval($phRes['polyhouse_plant_qty'] ?? 0);
                $readyQty = intval($phRes['ready_plant_qty'] ?? 0);
                $trayCnt = intval($phRes['polyhouse_tray_count'] ?? 0);
            }
        }

        if ($phQty <= 0 && $readyQty <= 0 && $trayCnt <= 0) {
            echo json_encode([
                "status" => "error", 
                "message" => "Plant(s) are NOT available in polyhouse for Stock Order [{$chkStk}] (Total Plants: 0)! Cannot generate dispatch invoice and move to Ready to Loading without plants physically present in polyhouse."
            ]);
            exit;
        }

        $allocQtyCheck = intval($chkIt['allocate_ordqty'] ?? 0);
        if ($allocQtyCheck <= 0) {
            echo json_encode([
                "status" => "error", 
                "message" => "Cannot generate dispatch invoice: Allocated quantity for item '{$chkProd}' must be greater than zero."
            ]);
            exit;
        }
    }

    $krfconn->begin_transaction();
    try {
        // Effective invoice date from form or fallback to now
        $rawInvDate = trim($_POST['invoicedate'] ?? '');
        $effInvoiceDate = (!empty($rawInvDate) && strtotime($rawInvDate)) ? date('Y-m-d H:i:s', strtotime($rawInvDate . ' ' . date('H:i:s'))) : date('Y-m-d H:i:s');

        // 1. Generate unique sequential dinvoiceno for this dispatch invoice
        $dinvoiceno = trim($_POST['invoiceno'] ?? '');
        $isUnique = false;
        if (!empty($dinvoiceno)) {
            $chkExist = $krfconn->prepare("
                SELECT id FROM krf_sales_order 
                WHERE dinvoiceno = ? 
                  AND (psono != ? AND ? != '') 
                  AND (sono != ? AND ? != '')
                UNION 
                SELECT id FROM krf_sales_order_items 
                WHERE invoiceno = ? 
                  AND (psono != ? AND ? != '') 
                  AND (sono NOT LIKE CONCAT(?, '%'))
                LIMIT 1
            ");
            if ($chkExist) {
                $chkExist->bind_param("sssssssss", $dinvoiceno, $psono, $psono, $sono, $sono, $dinvoiceno, $psono, $psono, $sono);
                $chkExist->execute();
                $exRow = $chkExist->get_result()->fetch_assoc();
                $chkExist->close();
                if (!$exRow) {
                    $isUnique = true;
                }
            }
        }
        if (!$isUnique || empty($dinvoiceno)) {
            $dinvoiceno = function_exists('generateDispatchInvoiceNo')
                ? generateDispatchInvoiceNo($krfconn, $psono, $sono, $effInvoiceDate)
                : (function_exists('getNewDispatchInvoiceNumber') ? getNewDispatchInvoiceNumber($krfconn, $psono, $sono) : "KF/" . date('y') . '-' . (date('y') + 1) . "/1");
        }

        // Clean up previous dispatched items & dispatch header for this invoice if editing
        $delOldItems = $krfconn->prepare("
            DELETE FROM krf_sales_order_items 
            WHERE invoiceno = ? 
              AND ((psono = ? AND ? != '') OR (sono LIKE CONCAT(?, '%')))
        ");
        if ($delOldItems) {
            $delOldItems->bind_param("ssss", $dinvoiceno, $psono, $psono, $sono);
            $delOldItems->execute();
            $delOldItems->close();
        }

        $delOldDinvSo = $krfconn->prepare("
            DELETE FROM krf_sales_order 
            WHERE invoicetype = 'dispatchinvoice' 
              AND dinvoiceno = ? 
              AND ((psono = ? AND ? != '') OR (sono LIKE CONCAT(?, '%')))
        ");
        if ($delOldDinvSo) {
            $delOldDinvSo->bind_param("ssss", $dinvoiceno, $psono, $psono, $sono);
            $delOldDinvSo->execute();
            $delOldDinvSo->close();
        }

        // Clean up previous records in sales_invoice_items and sales_invoice
        $delOldSalesInvItems = $krfconn->prepare("DELETE FROM sales_invoice_items WHERE invoiceno = ?");
        if ($delOldSalesInvItems) {
            $delOldSalesInvItems->bind_param("s", $dinvoiceno);
            $delOldSalesInvItems->execute();
            $delOldSalesInvItems->close();
        }

        $delOldSalesInv = $krfconn->prepare("DELETE FROM sales_invoice WHERE invoiceno = ?");
        if ($delOldSalesInv) {
            $delOldSalesInv->bind_param("s", $dinvoiceno);
            $delOldSalesInv->execute();
            $delOldSalesInv->close();
        }

        // Archive/supersede any earlier un-dispatched readytoload invoices for this psono
        if (!empty($psono)) {
            $updOldInv = $krfconn->prepare("
                UPDATE sales_invoice 
                SET tedit = '1', teditremark = CONCAT('Replaced by invoice ', ?) 
                WHERE psono = ? AND invoiceno != ? AND LOWER(TRIM(status)) = 'readytoload'
            ");
            if ($updOldInv) {
                $updOldInv->bind_param("sss", $dinvoiceno, $psono, $dinvoiceno);
                $updOldInv->execute();
                $updOldInv->close();
            }

            $updOldInvItems = $krfconn->prepare("
                UPDATE sales_invoice_items 
                SET tedit = '1', teditremark = CONCAT('Replaced by invoice ', ?) 
                WHERE psono = ? AND invoiceno != ? AND LOWER(TRIM(status)) = 'readytoload'
            ");
            if ($updOldInvItems) {
                $updOldInvItems->bind_param("sss", $dinvoiceno, $psono, $dinvoiceno);
                $updOldInvItems->execute();
                $updOldInvItems->close();
            }
        }

        // 2. Generate unique sono for this dispatch invoice
        $cleanInvSuffix = preg_replace('/[^A-Za-z0-9]/', '-', $dinvoiceno);
        $baseOrder = !empty($sono) ? $sono : $psono;
        $dispatchSono = $baseOrder . '-' . $cleanInvSuffix;
        if (strlen($dispatchSono) > 95) {
            $dispatchSono = substr($dispatchSono, 0, 95);
        }

        // Fetch base provisional order data to guarantee company / client fields
        $baseSoStmt = $krfconn->prepare("
            SELECT id, sono, psono, companyid, subcompanyid, projectid, clientid, clientname, emailid, address, delivery_address, districtname, statename, mobileno, payment_terms, remarks
            FROM krf_sales_order 
            WHERE (sono = ? AND ? != '') OR (psono = ? AND ? != '')
            ORDER BY CASE WHEN invoicetype = 'sale' THEN 1 ELSE 2 END, id ASC
            LIMIT 1
        ");
        $baseSoStmt->bind_param("ssss", $sono, $sono, $psono, $psono);
        $baseSoStmt->execute();
        $baseSo = $baseSoStmt->get_result()->fetch_assoc();
        $baseSoStmt->close();

        $effCompanyId = intval($baseSo['companyid'] ?? $_POST['companyid'] ?? 1);
        $effSubCompanyId = intval($baseSo['subcompanyid'] ?? $_POST['subcompanyid'] ?? 1);
        $effProjectId = ($projectid > 0) ? $projectid : intval($baseSo['projectid'] ?? 1);
        $effClientId = !empty($clientid) ? $clientid : ($baseSo['clientid'] ?? '');
        $effClientName = !empty($clientname) ? $clientname : ($baseSo['clientname'] ?? '');
        $effEmailId = !empty($emailid) ? $emailid : ($baseSo['emailid'] ?? '');
        $effMobileNo = !empty($mobileno) ? $mobileno : ($baseSo['mobileno'] ?? '');
        $effAddress = !empty($address) ? $address : ($baseSo['address'] ?? '');
        if (empty($effAddress) && !empty($effClientId)) {
            $cmAddrStmt = $krfconn->prepare("SELECT invoicing_address, statename, districtname, cityname, tahseel FROM krf_client_master WHERE (tedit IS NULL OR tedit = '' OR tedit = '0') AND client_id = ? LIMIT 1");
            if ($cmAddrStmt) {
                $cmAddrStmt->bind_param("s", $effClientId);
                $cmAddrStmt->execute();
                $cmAddrRow = $cmAddrStmt->get_result()->fetch_assoc();
                $cmAddrStmt->close();
                if ($cmAddrRow) {
                    $effAddress = trim($cmAddrRow['invoicing_address'] ?? '');
                    if (empty($effAddress)) {
                        $parts = array_filter([$cmAddrRow['tahseel'] ?? '', $cmAddrRow['cityname'] ?? '', $cmAddrRow['districtname'] ?? '', $cmAddrRow['statename'] ?? '']);
                        $effAddress = implode(', ', $parts);
                    }
                }
            }
        }
        $effDeliveryAddr = !empty($delivery_address) ? $delivery_address : ($baseSo['delivery_address'] ?? '');
        $effDistrict = !empty($districtname) ? $districtname : ($baseSo['districtname'] ?? '');
        $effState = !empty($statename) ? $statename : ($baseSo['statename'] ?? '');
        $effPaymentTerms = !empty($payment_terms) ? $payment_terms : ($baseSo['payment_terms'] ?? '');
        $effRemarks = !empty($remarks) ? $remarks : ($baseSo['remarks'] ?? '');

        // Extract integer serial number from dinvoiceno (e.g. KF/26-27/8 -> 8)
        $invoiceSerialNo = 0;
        if (preg_match('/\/(\d+)$/', $dinvoiceno, $dm)) {
            $invoiceSerialNo = intval($dm[1]);
        }

        // 3. UPSERT INTO krf_sales_order (UPDATE if order exists, or INSERT if none exists)
        if (!empty($baseSo['id'])) {
            $updSoSql = "
                UPDATE krf_sales_order SET 
                    dinvoiceno = ?,
                    serialno = ?,
                    invoicedate = ?,
                    total_amount = ?,
                    tax_amount = ?,
                    grand_total = ?,
                    delivery_address = ?,
                    districtname = ?,
                    statename = ?,
                    payment_terms = ?,
                    remarks = ?,
                    updated_at = NOW(),
                    updated_by = ?
                WHERE id = ?
            ";
            $updSoStmt = $krfconn->prepare($updSoSql);
            $updSoStmt->bind_param(
                "sisdddssssssi",
                $dinvoiceno,
                $invoiceSerialNo,
                $effInvoiceDate,
                $total_amount,
                $tax_amount,
                $grand_total,
                $effDeliveryAddr,
                $effDistrict,
                $effState,
                $effPaymentTerms,
                $effRemarks,
                $loginid,
                $baseSo['id']
            );
            $updSoStmt->execute();
            $updSoStmt->close();
            $newSalesOrderId = $baseSo['id'];
        } else {
            $insSoSql = "
                INSERT INTO krf_sales_order (
                    sono, psono, companyid, subcompanyid, projectid,
                    serialno, invoicetype, dinvoiceno, invoicedate, clientid, clientname, emailid,
                    address, delivery_address, districtname, statename, mobileno,
                    payment_terms, remarks, total_amount, tax_amount, grand_total,
                    status, created_by, created_at, updated_at, updated_by
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, 'dispatchinvoice', ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    'Pending', ?, NOW(), NOW(), ?
                )
            ";
            $insSoStmt = $krfconn->prepare($insSoSql);
            $insSoStmt->bind_param(
                "ssiiiissssssssssssdddss",
                $dispatchSono,
                $psono,
                $effCompanyId,
                $effSubCompanyId,
                $effProjectId,
                $invoiceSerialNo,
                $dinvoiceno,
                $effInvoiceDate,
                $effClientId,
                $effClientName,
                $effEmailId,
                $effAddress,
                $effDeliveryAddr,
                $effDistrict,
                $effState,
                $effMobileNo,
                $effPaymentTerms,
                $effRemarks,
                $total_amount,
                $tax_amount,
                $grand_total,
                $loginid,
                $loginid
            );
            $insSoStmt->execute();
            $newSalesOrderId = $insSoStmt->insert_id;
            $insSoStmt->close();
        }

        // Also ensure dinvoiceno, serialno, invoicedate, total_amount, grand_total on base provisional sales order
        $updBaseSo = $krfconn->prepare("UPDATE krf_sales_order SET dinvoiceno = ?, serialno = ?, invoicedate = ?, total_amount = ?, grand_total = ?, updated_at = NOW(), updated_by = ? WHERE (psono = ? AND ? != '') OR (sono = ? AND ? != '')");
        if ($updBaseSo) {
            $updBaseSo->bind_param("sisddsssss", $dinvoiceno, $invoiceSerialNo, $effInvoiceDate, $total_amount, $grand_total, $loginid, $psono, $psono, $sono, $sono);
            $updBaseSo->execute();
            $updBaseSo->close();
        }

        // Update serial tracking in krf_invoice_serial_settings
        if ($invoiceSerialNo > 0 && function_exists('updateInvoiceCurrentSerialNo')) {
            updateInvoiceCurrentSerialNo($krfconn, 'dispatch', $invoiceSerialNo, $effInvoiceDate, $effProjectId, $effCompanyId, $loginid);
        }

        // 3.5 Compute financial breakdown & INSERT into sales_invoice (New Dedicated Invoice Master)
        $sumTaxable = 0;
        $sumCgst = 0;
        $sumSgst = 0;
        $sumIgst = 0;
        $sumTax = 0;
        $sumOtherDiscount = 0;

        foreach ($items as $chkFin) {
            $isBpFin = !empty($chkFin['is_box_packing']) || (strval($chkFin['product_id'] ?? '') === 'BOX_PACKING') || stripos($chkFin['product_name'] ?? '', 'Box Packing') !== false;
            $iQtyFin = intval($isBpFin ? ($chkFin['order_qty'] ?? $chkFin['quantity'] ?? $chkFin['allocate_ordqty'] ?? 0) : ($chkFin['allocate_ordqty'] ?? 0));
            $iRtFin = floatval($chkFin['rate'] ?? ($isBpFin ? 100 : 0));
            $iTaxbFin = floatval($chkFin['taxableamount'] ?? ($iQtyFin * $iRtFin));
            $iCgstFin = floatval($chkFin['cgst_amount'] ?? 0);
            $iSgstFin = floatval($chkFin['sgst_amount'] ?? 0);
            $iIgstFin = floatval($chkFin['igst_amount'] ?? 0);
            $iTxFin = floatval($chkFin['tax_amount'] ?? ($iCgstFin + $iSgstFin + $iIgstFin));
            $iDisAmtFin = floatval($chkFin['discount_amount'] ?? 0);

            $sumTaxable += $iTaxbFin;
            $sumCgst += $iCgstFin;
            $sumSgst += $iSgstFin;
            $sumIgst += $iIgstFin;
            $sumTax += $iTxFin;
            $sumOtherDiscount += $iDisAmtFin;
        }

        $invPaidAmt = isset($totalPaidAmt) ? floatval($totalPaidAmt) : 0;
        $invBalanceAmt = max(0, round($grand_total - $invPaidAmt, 2));
        $effCustType = !empty($_POST['client_type']) ? trim($_POST['client_type']) : ($customerType ?? 'Farmer');
        $effGstin = !empty($_POST['gstin']) ? trim($_POST['gstin']) : '';
        $rawDelType = trim($_POST['dinv_delivery_type'] ?? $_POST['delivery_type'] ?? $_POST['pickuptype'] ?? 'delivered');
        $isPickupMode = in_array(strtolower($rawDelType), ['pickup', 'client_pickup', 'pickupbyclient']);
        $isBoxPackingMode = (strtolower($rawDelType) === 'boxpacking');
        $normPickupType = $isPickupMode ? 'pickup' : ($isBoxPackingMode ? 'boxpacking' : 'delivered');
        $roundoffAmt = 0.00;
        $effSchedDate = !empty($scheduled_dispatch_date) ? date('Y-m-d', strtotime($scheduled_dispatch_date)) : date('Y-m-d');

        $insSalesInvSql = "
            INSERT INTO sales_invoice (
                companyid, subcompanyid, projectid, psono, sono,
                invoicetype, invoiceno, serialno, invoicedate,
                clientid, clientname, client_type, gstin, mobileno, emailid,
                invoicing_address, delivery_address, districtname, statename, place_of_supply,
                taxableamount, cgst_amount, sgst_amount, igst_amount, tax_amount,
                otherdiscount, roundoffamount, invoiceamount, payableamount, paidamount, balanceamount,
                pickuptype, vehicleno, drivername, drivermobileno, scheduled_dispatch_date,
                payment_terms, remarks, status, created_by, updated_by
            ) VALUES (
                ?, ?, ?, ?, ?,
                'dispatchinvoice', ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, 'readytoload', ?, ?
            )
        ";
        $insSalesInvStmt = $krfconn->prepare($insSalesInvSql);
        $salesInvoiceId = 0;
        if ($insSalesInvStmt) {
            $insSalesInvStmt->bind_param(
                "iiisssissssssssssssdddddddddddsssssssss",
                $effCompanyId, $effSubCompanyId, $effProjectId, $psono, $dispatchSono,
                $dinvoiceno, $invoiceSerialNo, $effInvoiceDate,
                $effClientId, $effClientName, $effCustType, $effGstin, $effMobileNo, $effEmailId,
                $effAddress, $effDeliveryAddr, $effDistrict, $effState, $effState,
                $sumTaxable, $sumCgst, $sumSgst, $sumIgst, $sumTax,
                $sumOtherDiscount, $roundoffAmt, $grand_total, $grand_total, $invPaidAmt, $invBalanceAmt,
                $normPickupType, $vehicleno, $drivername, $drivermobileno, $effSchedDate,
                $effPaymentTerms, $effRemarks, $loginid, $loginid
            );
            $insSalesInvStmt->execute();
            $salesInvoiceId = $insSalesInvStmt->insert_id;
            $insSalesInvStmt->close();
        } else {
            throw new Exception("Failed to prepare sales_invoice insert: " . $krfconn->error);
        }

        // 4. Insert dispatched items into sales_invoice_items, krf_sales_order_items & update consumed table
        $cropItems = array_filter($items, function($it) {
            $pId = strval($it['product_id'] ?? '');
            return empty($it['is_box_packing']) && $pId !== 'BOX_PACKING' && stripos($it['product_name'] ?? '', 'Box Packing') === false;
        });
        $cropItemCount = count($cropItems);
        $totalDispatchedPlants = 0;
        $cropIdx = 0;

        foreach ($items as $idx => $it) {
            $itemId = intval($it['soi_id'] ?? 0);
            $pId = strval($it['product_id'] ?? '');
            $chkProdName = trim($it['product_name'] ?? ('Product #' . $pId));
            $isBoxPacking = !empty($it['is_box_packing']) || ($pId === 'BOX_PACKING') || stripos($chkProdName, 'Box Packing') !== false;

            if ($isBoxPacking) {
                $stkNo = '';
                $oQty = intval($it['order_qty'] ?? $it['quantity'] ?? $it['allocate_ordqty'] ?? 0);
                $aQty = $oQty;
                $hPlant = 0;
                $uPlant = 0;
                $isShStr = 'yes';
                $isShInt = 1;
                $pExtra = 0;
                $sQty = 0;
                $iRate = floatval($it['rate'] ?? 100);
                $iDiscPct = 0;
                $iDiscAmt = 0;
                $iTaxable = floatval($it['taxableamount'] ?? ($aQty * $iRate));
                $iCgstP = 0;
                $iCgstA = 0;
                $iSgstP = 0;
                $iSgstA = 0;
                $iIgstP = 0;
                $iIgstA = 0;
                $iTaxAmt = 0;
                $iItemFreight = 0.00;
                $iAmount = $iTaxable;
                $prodSku = 'BOX-PACKING';
                $prodName = !empty($it['product_name']) ? $it['product_name'] : ("Box Packing Charges (" . $aQty . " Boxes @ Rs " . number_format($iRate, 0) . ")");
                $delivDate = !empty($_POST['scheduled_dispatch_date']) ? date('Y-m-d', strtotime($_POST['scheduled_dispatch_date'])) : date('Y-m-d');
                $confirmDelivDate = $delivDate;
                $confirmRemark = '';
            } else {
                $stkNo = trim($it['stockorder_no'] ?? '');
                $oQty = intval($it['order_qty'] ?? 0);
                $aQty = intval($it['allocate_ordqty'] ?? 0);
                $hPlant = intval($it['healthyplant'] ?? 0);
                $uPlant = intval($it['unhealthyplant'] ?? 0);
                $isSh = trim($it['isshifted'] ?? 'no');
                $isShStr = ($isSh === 'yes' || $isSh === '1' || $isSh === 1) ? 'yes' : 'no';
                $isShInt = ($isShStr === 'yes') ? 1 : 0;
                $pExtra = ($isShStr === 'yes') ? 0 : floatval($it['percentextrashifting'] ?? 0);
                $sQty = ($isShStr === 'yes') ? 0 : intval($it['shiftingqty'] ?? 0);

                $iRate = floatval($it['rate'] ?? 0);
                $iDiscPct = floatval($it['discount_percent'] ?? 0);
                $iDiscAmt = floatval($it['discount_amount'] ?? 0);
                $iTaxable = floatval($it['taxableamount'] ?? 0);
                $iCgstP = floatval($it['cgst_percent'] ?? 0);
                $iCgstA = floatval($it['cgst_amount'] ?? 0);
                $iSgstP = floatval($it['sgst_percent'] ?? 0);
                $iSgstA = floatval($it['sgst_amount'] ?? 0);
                $iIgstP = floatval($it['igst_percent'] ?? 0);
                $iIgstA = floatval($it['igst_amount'] ?? 0);
                $iTaxAmt = floatval($it['tax_amount'] ?? ($iCgstA + $iSgstA + $iIgstA));
                $iItemFreight = 0.00; // Freight charges are order-level transport fees, not added into product items
                $iAmount = $iTaxable + $iTaxAmt; // Line amount is strictly taxable + tax

                $totalDispatchedPlants += $aQty;
                $cropIdx++;

                // Fetch details from original provisional item
                $origItem = null;
                if ($itemId > 0) {
                    $origItemStmt = $krfconn->prepare("SELECT product_sku, product_name, deliverydate, confirmdeliverydate, confirmdeliverydateremark FROM krf_sales_order_items WHERE id = ? LIMIT 1");
                    $origItemStmt->bind_param("i", $itemId);
                    $origItemStmt->execute();
                    $origItem = $origItemStmt->get_result()->fetch_assoc();
                    $origItemStmt->close();
                }

                $prodSku = $origItem['product_sku'] ?? '';
                $prodName = !empty($it['allocated_product_name']) ? trim($it['allocated_product_name']) : (!empty($it['product_name']) ? trim($it['product_name']) : ($origItem['product_name'] ?? ('Product #' . $pId)));
                if (!empty($it['allocated_product_id'])) {
                    $pId = trim($it['allocated_product_id']);
                }
                $delivDate = !empty($it['deliverydate']) ? date('Y-m-d', strtotime($it['deliverydate'])) : (!empty($_POST['scheduled_dispatch_date']) ? date('Y-m-d', strtotime($_POST['scheduled_dispatch_date'])) : (!empty($origItem['deliverydate']) ? $origItem['deliverydate'] : date('Y-m-d')));
                $confirmDelivDate = !empty($it['deliverydate']) ? $delivDate : (!empty($_POST['scheduled_dispatch_date']) ? $delivDate : (!empty($origItem['confirmdeliverydate']) ? $origItem['confirmdeliverydate'] : $delivDate));
                $confirmRemark = $origItem['confirmdeliverydateremark'] ?? '';
            }

            // INSERT into sales_invoice_items (Dedicated Commercial Invoice Item Master)
            $insSiiSql = "
                INSERT INTO sales_invoice_items (
                    invoice_id, projectid, clientid, psono, sono, invoiceno,
                    original_soi_id, stockorder_no, productid, product_sku, productname,
                    hsn_code, is_box_packing, quantity, healthyqty, unhealthyqty,
                    isshifted, percentextrashifting, shiftingqty, total_dispatch_plants,
                    rate, discount_percent, discount_amount, taxableamount,
                    cgst_percent, cgst_amount, sgst_percent, sgst_amount, igst_percent, igst_amount,
                    tax_amount, amount, deliverydate, status, remarks
                ) VALUES (
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, 'readytoload', ?
                )
            ";
            $insSiiStmt = $krfconn->prepare($insSiiSql);
            if ($insSiiStmt) {
                $isBpInt = $isBoxPacking ? 1 : 0;
                $totPlts = $aQty + $sQty;
                $hsnCode = '';
                $insSiiStmt->bind_param(
                    "iissssisssssiiiisdiiddddddddddddss",
                    $salesInvoiceId, $effProjectId, $effClientId, $psono, $dispatchSono, $dinvoiceno,
                    $itemId, $stkNo, $pId, $prodSku, $prodName,
                    $hsnCode, $isBpInt, $aQty, $hPlant, $uPlant,
                    $isShStr, $pExtra, $sQty, $totPlts,
                    $iRate, $iDiscPct, $iDiscAmt, $iTaxable,
                    $iCgstP, $iCgstA, $iSgstP, $iSgstA, $iIgstP, $iIgstA,
                    $iTaxAmt, $iAmount, $delivDate, $confirmRemark
                );
                $insSiiStmt->execute();
                $insSiiStmt->close();
            } else {
                throw new Exception("Failed to prepare sales_invoice_items insert: " . $krfconn->error);
            }

            // UPDATE original provisional item delivery date & status (crops only)
            if ($itemId > 0 && !$isBoxPacking) {
                $updOrigSoi = $krfconn->prepare("
                    UPDATE krf_sales_order_items SET
                        actionstatus = 'readytoload',
                        deliverydate = CASE WHEN ? != '' THEN ? ELSE deliverydate END,
                        confirmdeliverydate = CASE WHEN ? != '' THEN ? ELSE confirmdeliverydate END,
                        ammendedby = ?,
                        updateamended = NOW()
                    WHERE id = ?
                ");
                if ($updOrigSoi) {
                    $updOrigSoi->bind_param("sssssi", $delivDate, $delivDate, $confirmDelivDate, $confirmDelivDate, $loginid, $itemId);
                    $updOrigSoi->execute();
                    $updOrigSoi->close();
                }
            }

            // Update consumed table to readytoload (crops only)
            // Preserves individual batch allocations (e.g. 500 + 500) set by UpdateAllocation!
            if (!$isBoxPacking && !empty($stkNo)) {
                $updPoc = $krfconn->prepare("
                    UPDATE krf_productionorder_consumed SET
                        isshifted = ?,
                        percentextrashifting = ?,
                        extraqtyforshifting = ?,
                        status = 'readytoload',
                        updated_at = NOW()
                    WHERE ((psono = ? AND ? != '' AND (product_id = ? OR product_name = ?))
                       OR (sono = ? AND ? != '' AND (product_id = ? OR product_name = ?)))
                      AND IFNULL(tedit, '') = ''
                ");
                if ($updPoc) {
                    $updPoc->bind_param("idissssssss", $isShInt, $pExtra, $sQty, $psono, $psono, $pId, $prodName, $sono, $sono, $pId, $prodName);
                    $updPoc->execute();
                    $updPoc->close();
                }

                // Update krf_readystock_allocation to 'readytoload' and set invoice_number and invoice_date
                $invDateOnly = !empty($effInvoiceDate) ? date('Y-m-d', strtotime($effInvoiceDate)) : date('Y-m-d');
                $updRsaInv = $krfconn->prepare("
                    UPDATE krf_readystock_allocation
                    SET status = 'readytoload',
                        invoice_number = ?,
                        invoice_date = ?,
                        updated_at = NOW(),
                        updated_by = ?
                    WHERE (
                        ((psono = ? AND ? != '') AND (productid = ? OR productname = ?))
                        OR ((allocate_stockorder_no = ? OR stockorder_no = ?) AND ? != '')
                    )
                    AND (tedit IS NULL OR tedit = '' OR tedit = '0')
                    AND LOWER(IFNULL(status, '')) != 'cancel'
                ");
                if ($updRsaInv) {
                    $updRsaInv->bind_param(
                        "ssssssssss",
                        $dinvoiceno,
                        $invDateOnly,
                        $loginid,
                        $psono,
                        $psono,
                        $pId,
                        $prodName,
                        $stkNo,
                        $stkNo,
                        $stkNo
                    );
                    $updRsaInv->execute();
                    $updRsaInv->close();
                }
            }

            // AUTO-MARK RSO / SO ORDERS: Automatically mark active trays as Loaded
            if (!$isBoxPacking && !empty($stkNo) && preg_match('/^(RSO|SO)-/i', trim($stkNo))) {
                autoMarkRsoTraysAsLoaded($krfconn, $psono, $stkNo, $totPlts, $effClientId, $loginid, $dinvoiceno);
            }
        }

        // 5. Update krf_freightcharges & krf_deliveryaddress_location
        $freight_disc_pct = floatval($_POST['freight_discount_percent'] ?? 0);
        $freight_disc_amt = floatval($_POST['freight_discount_amount'] ?? 0);
        $freight_narration = trim($_POST['freight_narration'] ?? '');
        $free_dist = floatval($_POST['free_distance'] ?? 0);
        $total_dist = floatval($_POST['total_distance'] ?? 0);
        $payable_dist = floatval($_POST['payable_distance'] ?? 0);

        $rawFgJson = $_POST['freight_groups'] ?? '';
        $parsedFg = !empty($rawFgJson) ? json_decode($rawFgJson, true) : [];
        if (!empty($parsedFg) && is_array($parsedFg) && isset($parsedFg[0])) {
            $fg0 = $parsedFg[0];
            if ($freight_disc_pct <= 0 && !empty($fg0['discPct'])) $freight_disc_pct = floatval($fg0['discPct']);
            if ($freight_disc_amt <= 0 && !empty($fg0['discAmt'])) $freight_disc_amt = floatval($fg0['discAmt']);
            if (empty($freight_narration) && !empty($fg0['location']['remark'])) $freight_narration = trim($fg0['location']['remark']);
            if ($free_dist <= 0 && !empty($fg0['location']['freedistance'])) $free_dist = floatval($fg0['location']['freedistance']);
        }

        if ($freight_charges >= 0) {
            $updFc = "
                UPDATE krf_freightcharges SET
                    payable_freightamount = ?,
                    freight_amount = ?,
                    freight_discountpercent = ?,
                    freight_discountamount = ?,
                    remarks = CASE WHEN ? != '' THEN ? ELSE remarks END,
                    updated_at = NOW()
                WHERE (psono = ? AND ? != '') OR (sono = ? AND ? != '')
            ";
            $fcStmt = $krfconn->prepare($updFc);
            $fcStmt->bind_param("ddddssssss", $freight_charges, $freight_charges, $freight_disc_pct, $freight_disc_amt, $freight_narration, $freight_narration, $psono, $psono, $sono, $sono);
            $fcStmt->execute();
            $fcStmt->close();
        }

        $rawDelType = trim($_POST['dinv_delivery_type'] ?? $_POST['delivery_type'] ?? $_POST['pickuptype'] ?? '');
        if (empty($rawDelType) && !empty($parsedFg[0]['location']['pickuptype'])) {
            $rawDelType = trim($parsedFg[0]['location']['pickuptype']);
        }
        $isPickupMode = in_array(strtolower($rawDelType), ['pickup', 'client_pickup', 'pickupbyclient']);
        $isBoxPackingMode = (strtolower($rawDelType) === 'boxpacking');
        $normPickupType = $isPickupMode ? 'pickup' : ($isBoxPackingMode ? 'boxpacking' : 'delivered');

        // INSERT delivery location record for this new dispatch invoice
        $insPickType = $normPickupType;
        $insTransType = $isPickupMode ? 'Self Pickup By Farmer/Client' : ($isBoxPackingMode ? 'Box Packing' : 'Delivered');
        $insVehNo = $isPickupMode ? 'Self Pickup' : $vehicleno;
        $insDist = $isPickupMode ? 0 : $payable_dist;
        $insTotDist = $isPickupMode ? 0 : $total_dist;
        $insFreeDist = $isPickupMode ? 0 : $free_dist;
        $insFreightAmt = $isPickupMode ? 0 : $freight_charges;
        $payable_freight = (isset($_POST['payable_freight_amount']) && $_POST['payable_freight_amount'] !== '') ? floatval($_POST['payable_freight_amount']) : $insFreightAmt;
        $insAddr = $isPickupMode ? 'Self Pickup (Ex-Nursery / Farm Gate)' : $effDeliveryAddr;
        $firstItemProdId = !empty($items[0]['product_id']) ? strval($items[0]['product_id']) : '';

        $insDl = $krfconn->prepare("
            INSERT INTO krf_deliveryaddress_location (
                projectid, psono, sono, clientid, productid,
                pickuptype, transporttype, vehicleno, drivername, drivercontactno,
                deliveryaddress, dest_state, dest_district,
                distance, totaldistance, freedistance, freightamt, deliverycharges, remark,
                status, created_by, created_at
            ) VALUES (
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?, ?, 0, ?,
                'Pending', ?, NOW()
            )
        ");
        if ($insDl) {
            $insDl->bind_param(
                "issssssssssssddddss",
                $effProjectId, $psono, $dispatchSono, $effClientId, $firstItemProdId,
                $insPickType, $insTransType, $insVehNo, $drivername, $drivermobileno,
                $insAddr, $effState, $effDistrict,
                $insDist, $insTotDist, $insFreeDist, $insFreightAmt, $freight_narration,
                $loginid
            );
            $insDl->execute();
            $insDl->close();
        }

        // 6. Save or Update Vehicle Allocation in krf_vehicletrip & krf_vehicle_travel_history
        if ($isPickupMode) {
            saveOrUpdateVehicleTripAndHistory($krfconn, [
                'projectid' => $effProjectId,
                'sono' => $dispatchSono,
                'psono' => $psono,
                'clientid' => $effClientId,
                'vehicle_id' => 0,
                'vehicleno' => 'Self Pickup',
                'driver_id' => '0',
                'drivername' => 'Self (Farmer/Client)',
                'drivermobileno' => '',
                'to_location_name' => 'Self Pickup by Farmer/Client',
                'distance_km' => 0,
                'distance_source' => 'Manual',
                'freight_amount' => 0,
                'freight_discount_percent' => 0,
                'payable_freight_amount' => 0,
                'dispatch_type' => 'Self Pickup',
                'deliverydate' => $scheduled_dispatch_date,
                'total_allocated_plants' => 0,
                'remarks' => 'Self Pickup By Farmer/Client',
                'loginid' => $loginid,
                'status' => 'Dispatched'
            ]);
        } else if (!empty($vehicleno) || $vehicle_id > 0) {
            saveOrUpdateVehicleTripAndHistory($krfconn, [
                'projectid' => $effProjectId,
                'sono' => $dispatchSono,
                'psono' => $psono,
                'clientid' => $effClientId,
                'vehicle_id' => $vehicle_id,
                'vehicleno' => $vehicleno,
                'driver_id' => '',
                'drivername' => $drivername,
                'drivermobileno' => $drivermobileno,
                'to_location_name' => $insAddr,
                'to_lat' => (!empty($_POST['dest_latitude']) ? floatval($_POST['dest_latitude']) : null),
                'to_lon' => (!empty($_POST['dest_longitude']) ? floatval($_POST['dest_longitude']) : null),
                'distance_km' => $insDist,
                'distance_source' => (!empty($_POST['dest_latitude']) ? 'GPS' : 'Manual'),
                'freight_amount' => $insFreightAmt,
                'freight_discount_percent' => $freight_disc_pct,
                'payable_freight_amount' => $payable_freight,
                'dispatch_type' => ($normPickupType === 'boxpacking' ? 'Box Packing' : 'Delivered'),
                'deliverydate' => $scheduled_dispatch_date,
                'total_allocated_plants' => $totalDispatchedPlants,
                'remarks' => $transport_remarks,
                'loginid' => $loginid,
                'status' => 'Dispatched'
            ]);
        } else if (!empty($insAddr)) {
            saveOrUpdateVehicleTripAndHistory($krfconn, [
                'projectid' => $effProjectId,
                'sono' => $dispatchSono,
                'psono' => $psono,
                'clientid' => $effClientId,
                'vehicle_id' => 0,
                'vehicleno' => '',
                'driver_id' => '',
                'drivername' => '',
                'drivermobileno' => '',
                'to_location_name' => $insAddr,
                'to_lat' => (!empty($_POST['dest_latitude']) ? floatval($_POST['dest_latitude']) : null),
                'to_lon' => (!empty($_POST['dest_longitude']) ? floatval($_POST['dest_longitude']) : null),
                'distance_km' => $insDist,
                'distance_source' => (!empty($_POST['dest_latitude']) ? 'GPS' : 'Manual'),
                'freight_amount' => $insFreightAmt,
                'freight_discount_percent' => $freight_disc_pct,
                'payable_freight_amount' => $payable_freight,
                'dispatch_type' => ($normPickupType === 'boxpacking' ? 'Box Packing' : 'Delivered'),
                'deliverydate' => $scheduled_dispatch_date,
                'total_allocated_plants' => $totalDispatchedPlants,
                'remarks' => $transport_remarks,
                'loginid' => $loginid,
                'status' => 'Dispatched'
            ]);
        }

        $krfconn->commit();

        $itemCount = count($items);
        echo json_encode([
            "status" => "success",
            "message" => "Dispatch Invoice {$dinvoiceno} generated and saved successfully for {$itemCount} product(s).",
            "dinvoiceno" => $dinvoiceno,
            "sono" => $dispatchSono,
            "grand_total" => $grand_total
        ]);
    } catch (Throwable $e) {
        $krfconn->rollback();
        echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
    }
    exit;


} else if ($action == "GetVehicleAllocationOrders") {
    $projectid = intval($_POST['projectid'] ?? 0);
    $dateFrom = trim($_POST['date_from'] ?? '');
    $dateTo = trim($_POST['date_to'] ?? '');
    $filterStatus = trim($_POST['filter_status'] ?? 'all'); // 'pending_vehicle', 'allocated_vehicle', 'all'
    $searchTerm = trim($_POST['search'] ?? '');

    $sql = "
        SELECT 
            so.id AS so_id,
            so.sono,
            so.psono,
            so.projectid,
            so.clientid,
            so.clientname,
            so.mobileno,
            so.delivery_address,
            so.districtname,
            so.statename,
            so.invoicetype,
            so.dinvoiceno,
            MIN(soi.deliverydate) AS deliverydate,
            COUNT(soi.id) AS item_count,
            SUM(COALESCE(poc.consumed_qty, soi.allocate_ordqty, soi.reservedqty, soi.quantity, 0)) AS total_billable_qty,
            GROUP_CONCAT(DISTINCT soi.product_name SEPARATOR ', ') AS product_names,
            GROUP_CONCAT(DISTINCT COALESCE(poc.stockorderno, soi.stockorder_no) SEPARATOR ', ') AS stock_order_nos,

            vth.id AS allocation_id,
            COALESCE(vth.vehicle_id, vt.vehicle_id, 0) AS vehicle_id,
            COALESCE(vth.vehicle_no, vt.vehicle_no, '') AS vehicleno,
            COALESCE(vm.vehiclename, '') AS vehiclename,
            COALESCE(vm.vehicleownername, '') AS vehicleownername,
            COALESCE(vth.driverid, vt.driver_id, '') AS driver_id,
            COALESCE(vth.driver_name, vt.driver_name, '') AS drivername,
            COALESCE(vth.driver_mobileno, vt.driver_mobile_no, '') AS drivermobileno,
            COALESCE(vth.deliverydate, vt.delivery_date) AS scheduled_dispatch_date,
            COALESCE(vm.plantscapacity, 0) AS plantscapacity,
            'vehicle_page' AS allocation_source,
            COALESCE(vt.status, 'Allocated') AS allocation_status,
            COALESCE(vth.send_remark, vt.remarks, '') AS vehicle_remarks,
            COALESCE(vth.created_by, vt.created_by, '') AS allocated_by,
            DATE_FORMAT(COALESCE(vth.created_at, vt.created_at), '%d-%b-%Y %h:%i %p') AS allocated_at_formatted,
            COALESCE(vt.trip_no, vth.trip_id, '') AS trip_id,

            dl.pickuptype AS dl_pickuptype,
            dl.deliveryaddress AS dl_deliveryaddress,
            dl.dest_state AS dl_dest_state,
            dl.dest_district AS dl_dest_district,
            dl.dest_city AS dl_dest_city,
            dl.dest_pincode AS dl_dest_pincode,
            dl.totaldistance AS dl_totaldistance,
            dl.freedistance AS dl_freedistance,
            dl.distance AS dl_distance,
            dl.freightamt AS dl_freightamt,
            dl.deliverycharges AS dl_deliverycharges,
            dl.pickuppoint AS dl_pickuppoint,
            dl.remark AS dl_remark,
            dl.boxperpack AS dl_boxperpack,
            dl.chargesperbox AS dl_chargesperbox,
            dl.noofbox AS dl_noofbox,
            dl.totalamount AS dl_totalamount,
            dl.vehicleno AS dl_vehicleno,
            dl.drivername AS dl_drivername,
            dl.drivercontactno AS dl_drivercontactno

        FROM krf_sales_order so
        JOIN krf_sales_order_items soi ON soi.sono = so.sono
        LEFT JOIN krf_productionorder_consumed poc ON poc.id = (
            SELECT poc2.id 
            FROM krf_productionorder_consumed poc2 
            WHERE (poc2.psono = soi.psono OR (poc2.sono = soi.sono AND soi.sono != ''))
              AND poc2.product_id = soi.product_id
              AND IFNULL(poc2.tedit, '') = ''
            ORDER BY 
              CASE WHEN NULLIF(TRIM(soi.stockorder_no), '') IS NOT NULL AND poc2.stockorderno = soi.stockorder_no AND ROUND(poc2.consumed_qty, 0) = ROUND(soi.quantity, 0) THEN 1
                   WHEN NULLIF(TRIM(soi.stockorder_no), '') IS NOT NULL AND poc2.stockorderno = soi.stockorder_no THEN 2
                   WHEN ROUND(poc2.consumed_qty, 0) = ROUND(soi.quantity, 0) THEN 3
                   ELSE 4 END,
              CASE WHEN poc2.status = 'allocated' THEN 1 WHEN poc2.status = 'dispatched' THEN 2 ELSE 3 END,
              poc2.id DESC 
            LIMIT 1
        )
        LEFT JOIN krf_vehicle_travel_history vth ON vth.id = (
            SELECT vth2.id 
            FROM krf_vehicle_travel_history vth2 
            WHERE (
                (COALESCE(vth2.stockorder_no, '') != '' AND (vth2.stockorder_no = so.sono OR vth2.stockorder_no = soi.stockorder_no)) OR 
                (COALESCE(vth2.psono, '') != '' AND vth2.psono = so.psono)
            )
            ORDER BY vth2.id DESC 
            LIMIT 1
        )
        LEFT JOIN krf_vehicletrip vt ON (vt.trip_no = vth.trip_id OR vt.trip_id = vth.trip_id)
        LEFT JOIN krf_vehicle_master vm ON (vm.vehicleid = COALESCE(vth.vehicle_id, vt.vehicle_id) AND vm.vehicleid > 0)
        LEFT JOIN krf_deliveryaddress_location dl ON dl.id = (
            SELECT d2.id FROM krf_deliveryaddress_location d2 
            WHERE (d2.projectid = so.projectid OR d2.projectid = ?)
              AND ((COALESCE(d2.psono,'') != '' AND d2.psono = so.psono) OR (COALESCE(d2.sono,'') != '' AND d2.sono = so.sono))
            ORDER BY d2.id DESC LIMIT 1
        )
        WHERE (so.projectid = ? OR ? = 0)
          AND (
              (poc.consumed_qty > 0)
              OR (soi.allocate_ordqty > 0)
              OR (soi.reservedqty > 0)
              OR (soi.stockorder_no IS NOT NULL AND soi.stockorder_no != '' AND soi.stockorder_no != 'Not Allocated')
          )
          AND (so.dinvoiceno IS NULL OR so.dinvoiceno = '')
          AND LOWER(IFNULL(soi.status, '')) NOT IN ('dispatched', 'final dispatch')
          AND LOWER(IFNULL(poc.status, '')) NOT IN ('dispatched')
    ";

    $params = [$projectid, $projectid, $projectid];
    $types = "iii";

    if (!empty($dateFrom) && !empty($dateTo)) {
        $sql .= " AND soi.deliverydate BETWEEN ? AND ?";
        $params[] = $dateFrom;
        $params[] = $dateTo;
        $types .= "ss";
    }

    if (!empty($searchTerm)) {
        $term = "%{$searchTerm}%";
        $sql .= " AND (so.sono LIKE ? OR so.psono LIKE ? OR so.clientname LIKE ? OR so.districtname LIKE ? OR vth.vehicle_no LIKE ? OR dl.vehicleno LIKE ?)";
        $params[] = $term; $params[] = $term; $params[] = $term; $params[] = $term; $params[] = $term; $params[] = $term;
        $types .= "ssssss";
    }

    $sql .= " GROUP BY so.id, so.sono, so.psono ";

    $havingClauses = ["total_billable_qty > 0"];
    if ($filterStatus === 'pending_vehicle') {
        $havingClauses[] = "(vth.id IS NULL AND (dl.vehicleno IS NULL OR dl.vehicleno = ''))";
    } else if ($filterStatus === 'allocated_vehicle') {
        $havingClauses[] = "(vth.id IS NOT NULL OR (dl.vehicleno IS NOT NULL AND dl.vehicleno != ''))";
    }
    if (!empty($havingClauses)) {
        $sql .= " HAVING " . implode(" AND ", $havingClauses);
    }

    $sql .= " ORDER BY deliverydate ASC, so.id DESC ";

    $stmt = $krfconn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $orders = [];
    $totalPending = 0;
    $totalAllocated = 0;
    $allocatedUserIds = [];

    while ($row = $res->fetch_assoc()) {
        $row['effective_vehicleno'] = !empty($row['vehicleno']) ? $row['vehicleno'] : ($row['dl_vehicleno'] ?? '');
        $row['effective_vehiclename'] = !empty($row['vehiclename']) ? $row['vehiclename'] : '';
        $row['effective_drivername'] = !empty($row['drivername']) ? $row['drivername'] : ($row['dl_drivername'] ?? '');
        $row['effective_drivermobile'] = !empty($row['drivermobileno']) ? $row['drivermobileno'] : ($row['dl_drivercontactno'] ?? '');
        $row['is_vehicle_allocated'] = !empty($row['effective_vehicleno']);
        $row['formatted_delivery_date'] = !empty($row['deliverydate']) ? date('d-M-Y', strtotime($row['deliverydate'])) : '-';

        $rawProd = $row['product_names'] ?? '';
        $row['clean_product_name'] = preg_replace('/^(NonGrafted|Non-Grafted)\s*[-:]?\s*/i', '', $rawProd);
        $row['is_grafted'] = (stripos($rawProd, 'grafted') !== false && stripos($rawProd, 'nongrafted') === false && stripos($rawProd, 'non-grafted') === false) ? 1 : 0;

        if (!empty($row['allocated_by'])) {
            $allocatedUserIds[] = $row['allocated_by'];
        }

        if ($row['is_vehicle_allocated']) {
            $totalAllocated++;
        } else {
            $totalPending++;
        }

        $orders[] = $row;
    }

    // Resolve allocated_by user names
    $userMap = [];
    if (!empty($allocatedUserIds)) {
        $allocatedUserIds = array_values(array_unique($allocatedUserIds));
        $placeholders = implode(',', array_fill(0, count($allocatedUserIds), '?'));
        $uSql = "SELECT loginid, mobileno, loginname FROM login_user_master WHERE loginid IN ($placeholders) OR mobileno IN ($placeholders)";
        $uStmt = $authconn->prepare($uSql);
        if ($uStmt) {
            $types = str_repeat('s', count($allocatedUserIds) * 2);
            $bindParams = array_merge($allocatedUserIds, $allocatedUserIds);
            $uStmt->bind_param($types, ...$bindParams);
            $uStmt->execute();
            $uRes = $uStmt->get_result();
            while ($uRow = $uRes->fetch_assoc()) {
                if (!empty($uRow['loginid'])) $userMap[$uRow['loginid']] = $uRow['loginname'];
                if (!empty($uRow['mobileno'])) $userMap[$uRow['mobileno']] = $uRow['loginname'];
            }
        }
    }

    foreach ($orders as &$ord) {
        $allocBy = $ord['allocated_by'] ?? '';
        $ord['allocated_by_name'] = $userMap[$allocBy] ?? $allocBy;
    }
    unset($ord);

    echo json_encode([
        "status" => "success",
        "data" => $orders,
        "counts" => [
            "total" => count($orders),
            "pending" => $totalPending,
            "allocated" => $totalAllocated
        ]
    ]);
    exit;

} else if ($action == "SaveOrderVehicleAllocation") {
    $projectid = intval($_POST['projectid'] ?? 0);
    $sono = trim($_POST['sono'] ?? '');
    $psono = trim($_POST['psono'] ?? '');
    $clientid = trim($_POST['clientid'] ?? '');
    $vehicle_id = intval($_POST['vehicle_id'] ?? 0);
    $vehicleno = strtoupper(trim($_POST['vehicleno'] ?? ''));
    $vehiclename = trim($_POST['vehiclename'] ?? '');
    $vehicleownername = trim($_POST['vehicleownername'] ?? '');
    $drivername = trim($_POST['drivername'] ?? '');
    $drivermobileno = trim($_POST['drivermobileno'] ?? '');
    $scheduled_dispatch_date = !empty($_POST['scheduled_dispatch_date']) ? trim($_POST['scheduled_dispatch_date']) : null;
    $total_allocated_plants = intval($_POST['total_allocated_plants'] ?? 0);
    $remarks = trim($_POST['remarks'] ?? '');
    $loginid = trim($_POST['loginid'] ?? '');

    if (empty($sono) && empty($psono)) {
        echo json_encode(["status" => "error", "message" => "Sales order number is required."]);
        exit;
    }
    if (empty($vehicleno) && $vehicle_id <= 0) {
        echo json_encode(["status" => "error", "message" => "Vehicle Number is required."]);
        exit;
    }

    if (!empty($drivermobileno) && !preg_match('/^[6-9]\d{9}$/', $drivermobileno)) {
        echo json_encode(["status" => "error", "message" => "Please enter a valid 10-digit mobile number (e.g. 9876543210)."]);
        exit;
    }

    // Save or update in krf_vehicletrip & krf_vehicle_travel_history
    saveOrUpdateVehicleTripAndHistory($krfconn, [
        'projectid' => $projectid,
        'sono' => $sono,
        'psono' => $psono,
        'clientid' => $clientid,
        'vehicle_id' => $vehicle_id,
        'vehicleno' => $vehicleno,
        'driver_id' => '',
        'drivername' => $drivername,
        'drivermobileno' => $drivermobileno,
        'deliverydate' => $scheduled_dispatch_date,
        'total_allocated_plants' => $total_allocated_plants,
        'remarks' => $remarks,
        'loginid' => $loginid,
        'status' => 'Planned'
    ]);

    // Sync with krf_deliveryaddress_location
    $dispDt = !empty($scheduled_dispatch_date) ? ($scheduled_dispatch_date . ' 00:00:00') : null;
    $updDl = $krfconn->prepare("
        UPDATE krf_deliveryaddress_location SET
            vehicleno = ?,
            drivername = ?,
            drivercontactno = ?,
            dispatch_datetime = COALESCE(?, dispatch_datetime),
            updated_at = NOW()
        WHERE (psono = ? AND ? != '') OR (sono = ? AND ? != '')
    ");
    $updDl->bind_param("ssssssss", $vehicleno, $drivername, $drivermobileno, $dispDt, $psono, $psono, $sono, $sono);
    $updDl->execute();

    echo json_encode([
        "status" => "success",
        "message" => "Vehicle {$vehicleno} successfully allocated to Order {$sono}."
    ]);
    exit;

} else if ($action == "DeleteOrderVehicleAllocation") {
    $projectid = intval($_POST['projectid'] ?? 0);
    $sono = trim($_POST['sono'] ?? '');
    $psono = trim($_POST['psono'] ?? '');

    // 1. Find trip_id from krf_vehicle_travel_history
    $getTrip = $krfconn->prepare("SELECT DISTINCT trip_id FROM krf_vehicle_travel_history WHERE (stockorder_no = ? AND ? != '') OR (psono = ? AND ? != '')");
    $getTrip->bind_param("ssss", $sono, $sono, $psono, $psono);
    $getTrip->execute();
    $tripRes = $getTrip->get_result();
    $affectedTrips = [];
    while ($tr = $tripRes->fetch_assoc()) {
        if (!empty($tr['trip_id'])) {
            $affectedTrips[] = $tr['trip_id'];
        }
    }
    $getTrip->close();

    // 2. Delete from krf_vehicle_travel_history
    $del = $krfconn->prepare("DELETE FROM krf_vehicle_travel_history WHERE (stockorder_no = ? AND ? != '') OR (psono = ? AND ? != '')");
    $del->bind_param("ssss", $sono, $sono, $psono, $psono);
    $del->execute();
    $del->close();

    // 3. Cancel planned trip if no orders remain on it
    foreach ($affectedTrips as $tripNo) {
        $chkRemain = $krfconn->prepare("SELECT COUNT(id) AS cnt FROM krf_vehicle_travel_history WHERE trip_id = ?");
        $chkRemain->bind_param("s", $tripNo);
        $chkRemain->execute();
        $cntRow = $chkRemain->get_result()->fetch_assoc();
        $chkRemain->close();
        if ($cntRow && intval($cntRow['cnt']) === 0) {
            $updVt = $krfconn->prepare("UPDATE krf_vehicletrip SET status = 'Cancelled', updated_at = NOW() WHERE trip_no = ? AND status = 'Planned'");
            $updVt->bind_param("s", $tripNo);
            $updVt->execute();
            $updVt->close();
        }
    }

    // Clear in krf_deliveryaddress_location
    $updDl = $krfconn->prepare("
        UPDATE krf_deliveryaddress_location SET
            vehicleno = NULL,
            drivername = NULL,
            drivercontactno = NULL,
            updated_at = NOW()
        WHERE (psono = ? AND ? != '') OR (sono = ? AND ? != '')
    ");
    $updDl->bind_param("ssss", $psono, $psono, $sono, $sono);
    $updDl->execute();

    echo json_encode(["status" => "success", "message" => "Vehicle allocation removed."]);
    exit;

} else if ($action == "GetActiveVehiclesMaster") {
    $projectid = intval($_POST['projectid'] ?? 0);
    $sql = "SELECT vehicleid, projectid, vehicleno, vehiclename, vehicleownername, traycapacity, plantscapacity FROM krf_vehicle_master WHERE status = 'Active'";
    if ($projectid > 0) {
        $sql .= " AND (projectid = ? OR projectid = 0)";
        $stmt = $krfconn->prepare($sql);
        $stmt->bind_param("i", $projectid);
        $stmt->execute();
        $res = $stmt->get_result();
    } else {
        $res = $krfconn->query($sql);
    }
    $vehicles = [];
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $vehicles[] = $r;
        }
    }
    echo json_encode(["status" => "success", "data" => $vehicles]);
    exit;

} else if ($action == "GetMasterStates") {
    $sql = "SELECT stateid, statename FROM master_states WHERE (status = 'Active' OR IFNULL(status, '') = '') ORDER BY statename ASC";
    $res = $krfconn->query($sql);
    $states = [];
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $states[] = $r;
        }
    }
    echo json_encode(["status" => "success", "data" => $states]);
    exit;

} else if ($action == "GetMasterDistricts") {
    $state = trim($_POST['state'] ?? $_GET['state'] ?? '');
    $districts = [];

    if (!empty($state)) {
        $stmt = $krfconn->prepare("
            SELECT d.districtid, d.districtname, d.stateid 
            FROM master_districts d
            JOIN master_states s ON d.stateid = s.stateid
            WHERE (LOWER(TRIM(s.statename)) = LOWER(?) OR s.stateid = ?)
              AND (d.status = 'Active' OR IFNULL(d.status, '') = '')
            ORDER BY d.districtname ASC
        ");
        $stmt->bind_param("ss", $state, $state);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $districts[] = $r;
            }
        }
        $stmt->close();
    } else {
        $res = $krfconn->query("SELECT districtid, districtname, stateid FROM master_districts WHERE (status = 'Active' OR IFNULL(status, '') = '') ORDER BY districtname ASC");
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $districts[] = $r;
            }
        }
    }
    echo json_encode(["status" => "success", "data" => $districts]);
    exit;

} else if ($action == "GetVehicleAndDriverMasterData") {
    $projectid = intval($_POST['projectid'] ?? 0);

    // Vehicles & Transporters from krf_vehicle_master
    $vehSql = "SELECT vehicleid, projectid, vehicleno, vehiclename, vehicleownername, traycapacity, plantscapacity FROM krf_vehicle_master WHERE status = 'Active'";
    if ($projectid > 0) {
        $vehSql .= " AND (projectid = ? OR projectid = 0)";
        $stmt = $krfconn->prepare($vehSql);
        $stmt->bind_param("i", $projectid);
        $stmt->execute();
        $vRes = $stmt->get_result();
    } else {
        $vRes = $krfconn->query($vehSql);
    }

    $vehicles = [];
    $transporters = [];
    if ($vRes) {
        while ($v = $vRes->fetch_assoc()) {
            $vehicles[] = $v;
            $owner = trim($v['vehicleownername'] ?? '');
            if (!empty($owner) && !in_array($owner, $transporters)) {
                $transporters[] = $owner;
            }
        }
    }

    // Drivers from krf_vehicle_driver_master
    $drSql = "SELECT driverid, projectid, drivername, drivermobileno FROM krf_vehicle_driver_master WHERE status = 'Active'";
    if ($projectid > 0) {
        $drSql .= " AND (projectid = ? OR projectid = 0)";
        $stmtD = $krfconn->prepare($drSql);
        $stmtD->bind_param("i", $projectid);
        $stmtD->execute();
        $dRes = $stmtD->get_result();
    } else {
        $dRes = $krfconn->query($drSql);
    }

    $drivers = [];
    if ($dRes) {
        while ($d = $dRes->fetch_assoc()) {
            $drivers[] = $d;
        }
    }

    echo json_encode([
        "status"       => "success",
        "vehicles"     => $vehicles,
        "transporters" => $transporters,
        "drivers"      => $drivers
    ]);
    exit;

} else if ($action == "SaveManualDeliveryAddress") {
    $orderid = trim($_POST['orderid'] ?? '');
    $clientid = trim($_POST['clientid'] ?? '');
    $projectid = intval($_POST['projectid'] ?? 0);
    $state = trim($_POST['state'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $block = trim($_POST['block'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $distance_km = floatval($_POST['distance_km'] ?? 0);
    $total_qty = intval($_POST['total_qty'] ?? 0);
    $loginid = trim($_SESSION['loginname'] ?? $_SESSION['username'] ?? 'Admin');

    if (empty($orderid)) {
        echo json_encode(["status" => "error", "message" => "Order ID / PSO No is required."]);
        exit;
    }

    $full_address = implode(', ', array_filter([$city, $block, $district, $state, !empty($pincode) ? "PIN: {$pincode}" : '']));

    // 1. Check existing record in krf_deliveryaddress_location
    $chkStmt = $krfconn->prepare("SELECT id FROM krf_deliveryaddress_location WHERE (psono = ? OR sono = ?) AND (tedit IS NULL OR tedit = '') ORDER BY id DESC LIMIT 1");
    $chkStmt->bind_param("ss", $orderid, $orderid);
    $chkStmt->execute();
    $chkRes = $chkStmt->get_result();
    $existingLoc = ($chkRes && $chkRes->num_rows > 0) ? $chkRes->fetch_assoc() : null;
    $chkStmt->close();

    if ($existingLoc) {
        $locId = intval($existingLoc['id']);
        $updLoc = $krfconn->prepare("
            UPDATE krf_deliveryaddress_location 
            SET dest_state = ?, dest_district = ?, dest_city = ?, deliveryaddress = ?, 
                distance = ?, totaldistance = ?, freedistance = 0, pickuptype = 'delivered', 
                transporttype = 'Delivered', deliveryqty = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $updLoc->bind_param("ssssddii", $state, $district, $city, $full_address, $distance_km, $distance_km, $total_qty, $locId);
        $updLoc->execute();
        $updLoc->close();
    } else {
        $insLoc = $krfconn->prepare("
            INSERT INTO krf_deliveryaddress_location 
            (projectid, psono, clientid, dest_state, dest_district, dest_city, deliveryaddress, distance, totaldistance, freedistance, pickuptype, transporttype, deliveryqty, status, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 'delivered', 'Delivered', ?, 'Pending', ?, NOW())
        ");
        $insLoc->bind_param("issssssddis", $projectid, $orderid, $clientid, $state, $district, $city, $full_address, $distance_km, $distance_km, $total_qty, $loginid);
        $insLoc->execute();
        $insLoc->close();
    }

    // 2. Also update header tables for delivery address info
    $updPso = $krfconn->prepare("UPDATE krf_provisional_sales_order SET delivery_address = ?, districtname = ?, statename = ?, updated_at = NOW() WHERE psono = ?");
    if ($updPso) {
        $updPso->bind_param("ssss", $full_address, $district, $state, $orderid);
        $updPso->execute();
        $updPso->close();
    }

    $updSo = $krfconn->prepare("UPDATE krf_sales_order SET delivery_address = ?, districtname = ?, statename = ? WHERE psono = ? OR sono = ?");
    if ($updSo) {
        $updSo->bind_param("sssss", $full_address, $district, $state, $orderid, $orderid);
        $updSo->execute();
        $updSo->close();
    }

    // 3. Update or Insert into krf_vehicletrip & krf_vehicle_travel_history
    saveOrUpdateVehicleTripAndHistory($krfconn, [
        'projectid' => $projectid,
        'sono' => $orderid,
        'psono' => $orderid,
        'clientid' => $clientid,
        'to_location_name' => $full_address,
        'distance_km' => $distance_km,
        'distance_source' => 'Manual',
        'dispatch_type' => 'Delivered',
        'total_allocated_plants' => $total_qty,
        'loginid' => $loginid,
        'status' => 'Planned'
    ]);

    echo json_encode([
        "status" => "success",
        "message" => "Manual delivery address and distance saved successfully.",
        "data" => [
            "full_address" => $full_address,
            "calc_km" => $distance_km,
            "recommended_charge" => 0
        ]
    ]);
    exit;

} else if ($action == "SaveDeliveryLocationAddress") {
    $orderid = trim($_POST['orderid'] ?? '');
    $clientid = trim($_POST['clientid'] ?? '');
    $projectid = intval($_POST['projectid'] ?? 0);
    $state = trim($_POST['state'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $full_address = trim($_POST['full_address'] ?? '');
    $lat = (isset($_POST['lat']) && $_POST['lat'] !== '') ? floatval($_POST['lat']) : null;
    $lon = (isset($_POST['lon']) && $_POST['lon'] !== '') ? floatval($_POST['lon']) : null;
    $distance_km = floatval($_POST['distance_km'] ?? 0);
    $total_qty = intval($_POST['total_qty'] ?? 0);
    $source = trim($_POST['source'] ?? 'GPS');
    $loginid = trim($_SESSION['loginname'] ?? $_SESSION['username'] ?? 'Admin');

    if (empty($orderid)) {
        echo json_encode(["status" => "error", "message" => "Order ID / PSO No is required."]);
        exit;
    }

    if (empty($full_address)) {
        $full_address = implode(', ', array_filter([$city, $district, $state, !empty($pincode) ? "PIN: {$pincode}" : '']));
    }

    // 1. Update or Insert into krf_vehicletrip & krf_vehicle_travel_history
    saveOrUpdateVehicleTripAndHistory($krfconn, [
        'projectid' => $projectid,
        'sono' => $orderid,
        'psono' => $orderid,
        'clientid' => $clientid,
        'to_location_name' => $full_address,
        'to_lat' => $lat,
        'to_lon' => $lon,
        'distance_km' => $distance_km,
        'distance_source' => $source,
        'dispatch_type' => 'Delivered',
        'total_allocated_plants' => $total_qty,
        'loginid' => $loginid,
        'status' => 'Planned'
    ]);

    // 2. Check existing record in krf_deliveryaddress_location
    $chkStmt = $krfconn->prepare("SELECT id FROM krf_deliveryaddress_location WHERE (psono = ? OR sono = ?) AND (tedit IS NULL OR tedit = '') ORDER BY id DESC LIMIT 1");
    $chkStmt->bind_param("ss", $orderid, $orderid);
    $chkStmt->execute();
    $chkRes = $chkStmt->get_result();
    $existingLoc = ($chkRes && $chkRes->num_rows > 0) ? $chkRes->fetch_assoc() : null;
    $chkStmt->close();

    if ($existingLoc) {
        $locId = intval($existingLoc['id']);
        $updLoc = $krfconn->prepare("
            UPDATE krf_deliveryaddress_location 
            SET dest_state = ?, dest_district = ?, dest_city = ?, dest_pincode = ?, deliveryaddress = ?, 
                dest_latitude = ?, dest_longitude = ?, distance = ?, totaldistance = ?, freedistance = 0, 
                pickuptype = 'delivered', transporttype = 'Delivered', deliveryqty = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $updLoc->bind_param("sssssddddii", $state, $district, $city, $pincode, $full_address, $lat, $lon, $distance_km, $distance_km, $total_qty, $locId);
        $updLoc->execute();
        $updLoc->close();
    } else {
        $insLoc = $krfconn->prepare("
            INSERT INTO krf_deliveryaddress_location 
            (projectid, psono, clientid, dest_state, dest_district, dest_city, dest_pincode, deliveryaddress, dest_latitude, dest_longitude, distance, totaldistance, freedistance, pickuptype, transporttype, deliveryqty, status, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 'delivered', 'Delivered', ?, 'Pending', ?, NOW())
        ");
        $insLoc->bind_param("isssssssddddis", $projectid, $orderid, $clientid, $state, $district, $city, $pincode, $full_address, $lat, $lon, $distance_km, $distance_km, $total_qty, $loginid);
        $insLoc->execute();
        $insLoc->close();
    }

    // 3. Also update header tables
    $updPso = $krfconn->prepare("UPDATE krf_provisional_sales_order SET delivery_address = ?, districtname = ?, statename = ?, updated_at = NOW() WHERE psono = ?");
    if ($updPso) {
        $updPso->bind_param("ssss", $full_address, $district, $state, $orderid);
        $updPso->execute();
        $updPso->close();
    }

    $updSo = $krfconn->prepare("UPDATE krf_sales_order SET delivery_address = ?, districtname = ?, statename = ? WHERE psono = ? OR sono = ?");
    if ($updSo) {
        $updSo->bind_param("sssss", $full_address, $district, $state, $orderid, $orderid);
        $updSo->execute();
        $updSo->close();
    }

    echo json_encode([
        "status" => "success",
        "message" => "Delivery location address and coordinates saved to trip successfully.",
        "data" => [
            "full_address" => $full_address,
            "calc_km" => $distance_km,
            "lat" => $lat,
            "lon" => $lon
        ]
    ]);
    exit;

} else if ($action == "CheckVehicleTripCapacity") {
    $projectid = intval($_POST['projectid'] ?? 0);
    $vehicleid = intval($_POST['vehicleid'] ?? 0);
    $vehicleno = trim($_POST['vehicleno'] ?? '');
    $vehiclename = trim($_POST['vehiclename'] ?? '');
    $deliverydate = trim($_POST['deliverydate'] ?? '');
    $order_plants = intval($_POST['order_plants'] ?? 0);

    // 1. Fetch vehicle capacity
    $plant_capacity = 0;
    if ($vehicleid > 0 || !empty($vehicleno)) {
        $stmtV = $krfconn->prepare("SELECT vehicleid, vehicleno, vehiclename, vehicleownername, plantscapacity, traycapacity FROM krf_vehicle_master WHERE (vehicleid = ? AND ? > 0) OR (vehicleno = ? AND ? != '') LIMIT 1");
        $stmtV->bind_param("iiss", $vehicleid, $vehicleid, $vehicleno, $vehicleno);
        $stmtV->execute();
        $vRes = $stmtV->get_result();
        if ($vRes && $vRow = $vRes->fetch_assoc()) {
            $plant_capacity = intval($vRow['plantscapacity'] ?? 0);
        }
        $stmtV->close();
    }

    // 2. Check existing trip allocations for this vehicle and delivery date
    $current_trip_plants = 0;
    $location_count = 0;
    $existing_driver_name = '';
    $existing_driver_mobile = '';

    $existing_trip_no = '';
    if (!empty($deliverydate) && (!empty($vehicleno) || $vehicleid > 0)) {
        $stmtTrip = $krfconn->prepare("
            SELECT vt.trip_no, vt.driver_name, vt.driver_mobile_no, vt.remarks,
                   COALESCE(SUM(vth.send_totalplantcount), 0) as active_load,
                   COUNT(vth.id) as loc_count
            FROM krf_vehicletrip vt
            LEFT JOIN krf_vehicle_travel_history vth ON (vth.trip_id = vt.trip_no OR vth.trip_id = vt.trip_id)
            WHERE ((vt.vehicle_id = ? AND ? > 0) OR (vt.vehicle_no = ? AND ? != ''))
              AND vt.delivery_date = ?
              AND vt.status NOT IN ('Cancelled')
            GROUP BY vt.trip_no
            ORDER BY vt.trip_id DESC
            LIMIT 1
        ");
        $stmtTrip->bind_param("iisss", $vehicleid, $vehicleid, $vehicleno, $vehicleno, $deliverydate);
        $stmtTrip->execute();
        $tRes = $stmtTrip->get_result();
        if ($tRes && $tRow = $tRes->fetch_assoc()) {
            $current_trip_plants = intval($tRow['active_load'] ?? 0);
            $location_count = intval($tRow['loc_count'] ?? 0);
            $existing_driver_name = trim($tRow['driver_name'] ?? '');
            $existing_driver_mobile = trim($tRow['driver_mobile_no'] ?? '');
            $existing_trip_no = trim($tRow['trip_no'] ?? '');
        }
        $stmtTrip->close();
    }

    // Generate Trip ID dynamically
    $datePart = !empty($deliverydate) ? date('Ymd', strtotime($deliverydate)) : date('Ymd');
    $cleanVeh = preg_replace('/[^A-Za-z0-9]/', '', $vehicleno);
    $vehSuffix = !empty($cleanVeh) ? substr($cleanVeh, -4) : 'VEH';

    $total_projected = $current_trip_plants + $order_plants;
    $capacity_exceeded = ($plant_capacity > 0 && $total_projected > $plant_capacity);

    if ($current_trip_plants > 0 && !$capacity_exceeded) {
        $trip_id = !empty($existing_trip_no) ? $existing_trip_no : "TRIP-{$datePart}-{$vehSuffix}-1";
        $is_new_trip = false;
    } else if ($current_trip_plants > 0 && $capacity_exceeded) {
        $trip_id = "TRIP-{$datePart}-{$vehSuffix}-" . ($location_count + 1);
        $is_new_trip = true;
    } else {
        $trip_id = !empty($existing_trip_no) ? $existing_trip_no : "TRIP-{$datePart}-{$vehSuffix}-1";
        $is_new_trip = empty($existing_trip_no);
    }

    $remaining_capacity = ($plant_capacity > 0) ? max(0, $plant_capacity - $total_projected) : 999999;

    echo json_encode([
        "status" => "success",
        "data" => [
            "plant_capacity" => $plant_capacity,
            "current_trip_plants" => $current_trip_plants,
            "new_order_plants" => $order_plants,
            "total_projected_plants" => $total_projected,
            "remaining_capacity" => $remaining_capacity,
            "trip_id" => $trip_id,
            "is_new_trip" => $is_new_trip,
            "location_count" => $location_count,
            "capacity_exceeded" => $capacity_exceeded,
            "existing_driver_name" => $existing_driver_name,
            "existing_driver_mobile" => $existing_driver_mobile
        ]
    ]);
    exit;

} else if ($action == "SaveDeliveryVehicle") {
    $orderid = trim($_POST['orderid'] ?? '');
    $clientid = trim($_POST['clientid'] ?? '');
    $projectid = intval($_POST['projectid'] ?? 0);
    $vehicleid = intval($_POST['vehicleid'] ?? 0);
    $vehiclename = trim($_POST['vehiclename'] ?? '');
    $vehicleno = strtoupper(trim($_POST['vehicleno'] ?? ''));
    $transporter_name = trim($_POST['transporter_name'] ?? '');
    $driver_name = trim($_POST['driver_name'] ?? '');
    $driver_mobile = trim($_POST['driver_mobile'] ?? '');
    $driver_id = intval($_POST['driver_id'] ?? 0);
    $trip_id = trim($_POST['trip_id'] ?? '');
    $deliverydate = trim($_POST['deliverydate'] ?? '');
    $order_plants = intval($_POST['order_plants'] ?? 0);
    $distance = floatval($_POST['distance'] ?? 0);
    $freightamt = floatval($_POST['freightamt'] ?? 0);
    $loginid = trim($_SESSION['loginname'] ?? $_SESSION['username'] ?? 'Admin');

    if (empty($orderid)) {
        echo json_encode(["status" => "error", "message" => "Order ID (PSO/SO) is required."]);
        exit;
    }
    if (empty($vehicleno)) {
        echo json_encode(["status" => "error", "message" => "Vehicle Registration / Plate Number is required."]);
        exit;
    }
    if (empty($driver_name)) {
        echo json_encode(["status" => "error", "message" => "Driver Name is required."]);
        exit;
    }
    if (empty($driver_mobile)) {
        echo json_encode(["status" => "error", "message" => "Driver Mobile Number is required."]);
        exit;
    }

    if (empty($trip_id)) {
        $datePart = !empty($deliverydate) ? date('Ymd', strtotime($deliverydate)) : date('Ymd');
        $cleanVeh = preg_replace('/[^A-Za-z0-9]/', '', $vehicleno);
        $trip_id = "TRIP-{$datePart}-" . substr($cleanVeh, -4) . "-1";
    }

    $tripRemarks = "[Trip: {$trip_id}]";

    $delivery_address = trim($_POST['delivery_address'] ?? '');
    if (empty($delivery_address)) {
        $chkLoc = $krfconn->prepare("SELECT deliveryaddress, distance FROM krf_deliveryaddress_location WHERE (psono = ? OR sono = ?) AND (tedit IS NULL OR tedit = '') ORDER BY id DESC LIMIT 1");
        if ($chkLoc) {
            $chkLoc->bind_param("ss", $orderid, $orderid);
            $chkLoc->execute();
            $locRow = $chkLoc->get_result()->fetch_assoc();
            $chkLoc->close();
            if ($locRow) {
                $delivery_address = trim($locRow['deliveryaddress'] ?? '');
                if ($distance <= 0 && !empty($locRow['distance'])) {
                    $distance = floatval($locRow['distance']);
                }
            }
        }
    }

    // 1. Save or Update in krf_vehicletrip & krf_vehicle_travel_history
    saveOrUpdateVehicleTripAndHistory($krfconn, [
        'projectid' => $projectid,
        'sono' => $orderid,
        'psono' => $orderid,
        'clientid' => $clientid,
        'vehicle_id' => $vehicleid,
        'vehicleno' => $vehicleno,
        'driver_id' => strval($driver_id),
        'drivername' => $driver_name,
        'drivermobileno' => $driver_mobile,
        'deliverydate' => $deliverydate,
        'total_allocated_plants' => $order_plants,
        'to_location_name' => $delivery_address,
        'distance_km' => ($distance > 0 ? $distance : null),
        'freight_amount' => ($freightamt > 0 ? $freightamt : null),
        'dispatch_type' => 'Delivered',
        'remarks' => $tripRemarks,
        'trip_id' => $trip_id,
        'loginid' => $loginid,
        'status' => 'Planned'
    ]);

    // 2. Update krf_deliveryaddress_location
    $dispDt = !empty($deliverydate) ? ($deliverydate . ' 00:00:00') : null;
    $updDl = $krfconn->prepare("
        UPDATE krf_deliveryaddress_location SET
            vehicleno = ?,
            drivername = ?,
            drivercontactno = ?,
            transporttype = ?,
            freightamt = CASE WHEN ? > 0 THEN ? ELSE freightamt END,
            dispatch_datetime = COALESCE(?, dispatch_datetime),
            updated_at = NOW()
        WHERE (psono = ? AND ? != '') OR (sono = ? AND ? != '')
    ");
    $updDl->bind_param("ssssddsssss", $vehicleno, $driver_name, $driver_mobile, $vehiclename, $freightamt, $freightamt, $dispDt, $orderid, $orderid, $orderid, $orderid);
    $updDl->execute();
    $updDl->close();

    // 3. If new driver, insert into krf_vehicle_driver_master if not exists
    if (!empty($driver_name) && !empty($driver_mobile)) {
        $chkDr = $krfconn->prepare("SELECT driverid FROM krf_vehicle_driver_master WHERE drivermobileno = ? LIMIT 1");
        $chkDr->bind_param("s", $driver_mobile);
        $chkDr->execute();
        $drRow = $chkDr->get_result()->fetch_assoc();
        $chkDr->close();

        if (!$drRow) {
            $insDr = $krfconn->prepare("INSERT INTO krf_vehicle_driver_master (projectid, drivername, drivermobileno, status, created_at) VALUES (?, ?, ?, 'Active', NOW())");
            $insDr->bind_param("iss", $projectid, $driver_name, $driver_mobile);
            $insDr->execute();
            $insDr->close();
        }
    }

    echo json_encode([
        "status" => "success",
        "message" => "Vehicle and driver details saved successfully.",
        "data" => [
            "trip_id" => $trip_id,
            "vehicleno" => $vehicleno,
            "vehiclename" => $vehiclename,
            "drivername" => $driver_name,
            "drivermobileno" => $driver_mobile
        ]
    ]);
    exit;

} else if ($action == "ChangeDeliveryMode") {
    $orderid = trim($_POST['orderid'] ?? '');
    $clientid = trim($_POST['clientid'] ?? '');
    $projectid = intval($_POST['projectid'] ?? 0);
    $pickuptype = trim($_POST['pickuptype'] ?? 'delivered');

    if (empty($orderid)) {
        echo json_encode(["status" => "error", "message" => "Order ID is required."]);
        exit;
    }

    // 1. Check existing record in krf_deliveryaddress_location
    $chk = $krfconn->prepare("SELECT id FROM krf_deliveryaddress_location WHERE ((psono = ? AND ? != '') OR (sono = ? AND ? != '')) AND (tedit IS NULL OR tedit = '') ORDER BY id DESC LIMIT 1");
    $chk->bind_param("ssss", $orderid, $orderid, $orderid, $orderid);
    $chk->execute();
    $existing = $chk->get_result()->fetch_assoc();
    $chk->close();

    if ($pickuptype === 'pickup') {
        if ($existing && !empty($existing['id'])) {
            $upd = $krfconn->prepare("
                UPDATE krf_deliveryaddress_location SET
                    pickuptype = 'pickup',
                    transporttype = 'Self Pickup By Farmer/Client',
                    vehicleno = COALESCE(NULLIF(vehicleno, ''), 'Self Pickup'),
                    distance = 0,
                    totaldistance = 0,
                    freedistance = 0,
                    freightamt = 0,
                    deliverycharges = 0,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $upd->bind_param("i", $existing['id']);
            $upd->execute();
            $upd->close();
        } else {
            $ins = $krfconn->prepare("
                INSERT INTO krf_deliveryaddress_location (
                    projectid, psono, sono, clientid, pickuptype, transporttype, vehicleno,
                    deliveryaddress, distance, totaldistance, freedistance, freightamt, deliverycharges, status, created_at
                ) VALUES (
                    ?, ?, ?, ?, 'pickup', 'Self Pickup By Farmer/Client', 'Self Pickup',
                    'Self Pickup (Ex-Nursery / Farm Gate)', 0, 0, 0, 0, 0, 'Pending', NOW()
                )
            ");
            $ins->bind_param("isss", $projectid, $orderid, $orderid, $clientid);
            $ins->execute();
            $ins->close();
        }

        // Also ensure vehicle allocation for Self Pickup
        saveOrUpdateVehicleTripAndHistory($krfconn, [
            'projectid' => $projectid,
            'sono' => $orderid,
            'psono' => $orderid,
            'clientid' => $clientid,
            'vehicle_id' => 0,
            'vehicleno' => 'Self Pickup',
            'driver_id' => '0',
            'drivername' => 'Self (Farmer/Client)',
            'drivermobileno' => '',
            'deliverydate' => date('Y-m-d'),
            'total_allocated_plants' => 0,
            'remarks' => 'Self Pickup By Farmer/Client',
            'loginid' => 'Admin',
            'status' => 'Planned'
        ]);
    } else if ($pickuptype === 'boxpacking') {
        if ($existing && !empty($existing['id'])) {
            $upd = $krfconn->prepare("
                UPDATE krf_deliveryaddress_location SET
                    pickuptype = 'boxpacking',
                    transporttype = COALESCE(NULLIF(transporttype, ''), 'Box Packing'),
                    updated_at = NOW()
                WHERE id = ?
            ");
            $upd->bind_param("i", $existing['id']);
            $upd->execute();
            $upd->close();
        } else {
            $ins = $krfconn->prepare("
                INSERT INTO krf_deliveryaddress_location (
                    projectid, psono, sono, clientid, pickuptype, transporttype, status, created_at
                ) VALUES (
                    ?, ?, ?, ?, 'boxpacking', 'Box Packing', 'Pending', NOW()
                )
            ");
            $ins->bind_param("isss", $projectid, $orderid, $orderid, $clientid);
            $ins->execute();
            $ins->close();
        }
    } else {
        if ($existing && !empty($existing['id'])) {
            $upd = $krfconn->prepare("
                UPDATE krf_deliveryaddress_location SET
                    pickuptype = 'delivered',
                    transporttype = COALESCE(NULLIF(transporttype, ''), 'Delivered'),
                    updated_at = NOW()
                WHERE id = ?
            ");
            $upd->bind_param("i", $existing['id']);
            $upd->execute();
            $upd->close();
        } else {
            $ins = $krfconn->prepare("
                INSERT INTO krf_deliveryaddress_location (
                    projectid, psono, sono, clientid, pickuptype, transporttype, status, created_at
                ) VALUES (
                    ?, ?, ?, ?, 'delivered', 'Delivered', 'Pending', NOW()
                )
            ");
            $ins->bind_param("isss", $projectid, $orderid, $orderid, $clientid);
            $ins->execute();
            $ins->close();
        }
    }
    
    echo json_encode(["status" => "success", "message" => "Delivery mode updated to {$pickuptype}."]);
    exit;

} else if ($action == "SaveBoxPackingDelivery") {
    $orderid = trim($_POST['orderid'] ?? '');
    $clientid = trim($_POST['clientid'] ?? '');
    $projectid = intval($_POST['projectid'] ?? 0);
    $state = trim($_POST['state'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $plants_per_box = intval($_POST['plants_per_box'] ?? 0);
    $boxes = intval($_POST['boxes'] ?? 0);
    $charges_per_box = floatval($_POST['charges_per_box'] ?? 0);
    $total_freight = floatval($_POST['total_freight'] ?? 0);
    $transport_type = trim($_POST['transport_type'] ?? 'Box Packing');
    $remark = trim($_POST['remark'] ?? '');
    $total_qty = intval($_POST['total_qty'] ?? 0);
    $loginid = trim($_SESSION['loginname'] ?? $_SESSION['username'] ?? 'Admin');

    if (empty($orderid)) {
        echo json_encode(["status" => "error", "message" => "Order ID is required."]);
        exit;
    }

    $full_address = implode(', ', array_filter([$city, $district, $state]));

    // Check existing record
    $chk = $krfconn->prepare("SELECT id FROM krf_deliveryaddress_location WHERE (psono = ? OR sono = ?) AND (tedit IS NULL OR tedit = '') ORDER BY id DESC LIMIT 1");
    $chk->bind_param("ss", $orderid, $orderid);
    $chk->execute();
    $existing = $chk->get_result()->fetch_assoc();
    $chk->close();

    if ($existing && !empty($existing['id'])) {
        $locId = intval($existing['id']);
        $upd = $krfconn->prepare("
            UPDATE krf_deliveryaddress_location SET
                dest_state = ?,
                dest_district = ?,
                dest_city = ?,
                deliveryaddress = ?,
                boxperpack = ?,
                noofbox = ?,
                chargesperbox = ?,
                freightamt = ?,
                deliverycharges = ?,
                transporttype = ?,
                remark = ?,
                pickuptype = 'boxpacking',
                distance = 0,
                totaldistance = 0,
                freedistance = 0,
                deliveryqty = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $upd->bind_param("ssssiiddssiii", $state, $district, $city, $full_address, $plants_per_box, $boxes, $charges_per_box, $total_freight, $total_freight, $transport_type, $remark, $total_qty, $locId);
        $upd->execute();
        $upd->close();
    } else {
        $ins = $krfconn->prepare("
            INSERT INTO krf_deliveryaddress_location (
                projectid, psono, clientid, dest_state, dest_district, dest_city,
                deliveryaddress, boxperpack, noofbox, chargesperbox, freightamt,
                deliverycharges, transporttype, remark, pickuptype, distance,
                totaldistance, freedistance, deliveryqty, status, created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'boxpacking', 0, 0, 0, ?, 'Pending', ?, NOW())
        ");
        $ins->bind_param("issssssiiddsssis", $projectid, $orderid, $clientid, $state, $district, $city, $full_address, $plants_per_box, $boxes, $charges_per_box, $total_freight, $total_freight, $transport_type, $remark, $total_qty, $loginid);
        $ins->execute();
        $ins->close();
    }

    echo json_encode([
        "status" => "success",
        "message" => "Box packing delivery details saved successfully.",
        "data" => [
            "full_address" => $full_address,
            "total_freight" => $total_freight
        ]
    ]);
    exit;

} else if ($action == "UpdateRescheduleDeliveryDate") {
    $rawSoiId = trim($_POST['soi_id'] ?? '');
    $soi_id = intval($rawSoiId);
    $parentSoiId = intval($_POST['parent_soi_id'] ?? $soi_id);
    $pendingQty = intval($_POST['pending_qty'] ?? 0);
    $isPartialReschedule = (!empty($_POST['is_partial_pending']) || !empty($_POST['is_partial_reschedule']) || strpos($rawSoiId, '_pending') !== false || ($parentSoiId > 0 && $pendingQty > 0));

    $psono = trim($_POST['psono'] ?? '');
    $sono = trim($_POST['sono'] ?? '');
    $stockorder_no = trim($_POST['stockorder_no'] ?? '');
    $projectid = intval($_POST['projectid'] ?? 0);
    $new_delivery_date = trim($_POST['new_delivery_date'] ?? '');
    $remark = trim($_POST['remark'] ?? '');
    $loginid = trim($_POST['loginid'] ?? $_SESSION['loginname'] ?? 'Admin');
    $loginname = trim($_POST['loginname'] ?? $_SESSION['username'] ?? $loginid);

    if (empty($new_delivery_date)) {
        echo json_encode(["status" => "error", "message" => "New delivery date is required."]);
        exit;
    }

    $formattedNewDate = date('d-M-Y', strtotime($new_delivery_date));
    $timestampNow = date('d-M-Y H:i');
    $rescheduleLog = "Rescheduled to {$formattedNewDate} on {$timestampNow} by {$loginname}" . (!empty($remark) ? " (Reason: {$remark})" : "");

    // Check if this is a partial order reschedule (moving only the unfulfilled balance to "another day")
    if ($isPartialReschedule && $parentSoiId > 0) {
        $qParent = $krfconn->query("SELECT * FROM krf_sales_order_items WHERE id = $parentSoiId LIMIT 1");
        if ($qParent && $parentRow = $qParent->fetch_assoc()) {
            $parentTotalQty = intval($parentRow['quantity']);
            if ($pendingQty <= 0) {
                $dispatchedQ = intval($parentRow['dispatchedqty']);
                $allocQ = intval($parentRow['allocate_ordqty']);
                $fulfilledQ = ($dispatchedQ > 0) ? $dispatchedQ : $allocQ;
                $pendingQty = max(0, $parentTotalQty - $fulfilledQ);
            }
            $fulfilledQty = $parentTotalQty - $pendingQty;

            if ($fulfilledQty > 0 && $pendingQty > 0) {
                // Perform Delivery Tranche Split:
                // 1. Fulfilled tranche ($fulfilledQty) retains original delivery date & invoice
                $rate = floatval($parentRow['rate']);
                $fulfilledTaxable = round($rate * $fulfilledQty, 2);
                $origTaxRatio = ($parentTotalQty > 0) ? ($fulfilledQty / $parentTotalQty) : 1;
                $fulfilledCgst = round(floatval($parentRow['cgst_amount']) * $origTaxRatio, 2);
                $fulfilledSgst = round(floatval($parentRow['sgst_amount']) * $origTaxRatio, 2);
                $fulfilledIgst = round(floatval($parentRow['igst_amount']) * $origTaxRatio, 2);
                $fulfilledTax = $fulfilledCgst + $fulfilledSgst + $fulfilledIgst;
                $fulfilledAmt = round($fulfilledTaxable + $fulfilledTax, 2);

                $splitAuditNote = "Remaining balance of " . number_format($pendingQty) . " plants rescheduled to {$formattedNewDate}";
                $krfconn->query("
                    UPDATE krf_sales_order_items 
                    SET quantity = $fulfilledQty,
                        allocate_ordqty = $fulfilledQty,
                        reservedqty = $fulfilledQty,
                        taxableamount = $fulfilledTaxable,
                        cgst_amount = $fulfilledCgst,
                        sgst_amount = $fulfilledSgst,
                        igst_amount = $fulfilledIgst,
                        tax_amount = $fulfilledTax,
                        amount = $fulfilledAmt,
                        remark = CONCAT(IF(remark IS NULL OR remark = '', '', CONCAT(remark, ' | ')), '{$splitAuditNote}')
                    WHERE id = $parentSoiId
                ");

                // 2. Residual tranche ($pendingQty) scheduled for NEW DATE ("another day")
                $remTaxable = round($rate * $pendingQty, 2);
                $remTaxRatio = ($parentTotalQty > 0) ? ($pendingQty / $parentTotalQty) : 1;
                $remCgst = round(floatval($parentRow['cgst_amount']) * $remTaxRatio, 2);
                $remSgst = round(floatval($parentRow['sgst_amount']) * $remTaxRatio, 2);
                $remIgst = round(floatval($parentRow['igst_amount']) * $remTaxRatio, 2);
                $remTax = $remCgst + $remSgst + $remIgst;
                $remAmt = round($remTaxable + $remTax, 2);

                $insSql = "
                    INSERT INTO krf_sales_order_items (
                        serialno, sono, psono, stockorder_no, projectid, clientid, product_sku,
                        product_id, product_name, quantity, rate, taxableamount, cgst_percent, cgst_amount,
                        sgst_percent, sgst_amount, igst_percent, igst_amount, discount_percent, discount_amount,
                        freight_charges, tax_amount, amount, roundoffamount, deliverydate, confirmdeliverydate,
                        confirmdeliverydateremark, created_by, status, reservedqty, balancereservedqty,
                        allocate_ordqty, isshifted, percentextrashifting, shiftingqty, remark
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, 'Confirmed', 0, ?,
                        0, ?, ?, ?, ?
                    )
                ";
                $insStmt = $krfconn->prepare($insSql);
                $newSoiId = 0;
                if ($insStmt) {
                    $serialNo = intval($parentRow['serialno'] ?? 0);
                    $pSono = $parentRow['sono'] ?? '';
                    $pPsono = $parentRow['psono'] ?? '';
                    $pStkNo = $parentRow['stockorder_no'] ?? '';
                    $pProjId = intval($parentRow['projectid'] ?? 17);
                    $pClientId = $parentRow['clientid'] ?? '';
                    $pSku = $parentRow['product_sku'] ?? '';
                    $pProdId = $parentRow['product_id'] ?? '';
                    $pProdName = $parentRow['product_name'] ?? '';
                    $pCgstPct = floatval($parentRow['cgst_percent'] ?? 0);
                    $pSgstPct = floatval($parentRow['sgst_percent'] ?? 0);
                    $pIgstPct = floatval($parentRow['igst_percent'] ?? 0);
                    $pDiscPct = floatval($parentRow['discount_percent'] ?? 0);
                    $pDiscAmt = round(floatval($parentRow['discount_amount'] ?? 0) * $remTaxRatio, 2);
                    $pFreight = round(floatval($parentRow['freight_charges'] ?? 0) * $remTaxRatio, 2);
                    $pRoundOff = 0.00;
                    $pCreatedBy = $loginid ?: ($parentRow['created_by'] ?? 'admin');
                    $pIsShifted = $parentRow['isshifted'] ?? 'no';
                    $pPctExtra = floatval($parentRow['percentextrashifting'] ?? 0);
                    $pShiftingQty = 0;
                    $pRemRemark = "Remaining balance of " . number_format($pendingQty) . " plants split from initial order of " . number_format($parentTotalQty) . " plants (SOI #{$parentSoiId}) | " . $rescheduleLog;

                    $insStmt->bind_param(
                        "isssissssdddddddddddddddssssdsdds",
                        $serialNo, $pSono, $pPsono, $pStkNo, $pProjId, $pClientId, $pSku,
                        $pProdId, $pProdName, $pendingQty, $rate, $remTaxable, $pCgstPct, $remCgst,
                        $pSgstPct, $remSgst, $pIgstPct, $remIgst, $pDiscPct, $pDiscAmt,
                        $pFreight, $remTax, $remAmt, $pRoundOff, $new_delivery_date, $new_delivery_date, $rescheduleLog,
                        $pCreatedBy, $pendingQty, $pIsShifted, $pPctExtra, $pShiftingQty, $pRemRemark
                    );
                    $insStmt->execute();
                    $newSoiId = $insStmt->insert_id;
                    $insStmt->close();
                }

                // Insert pending consumed tracking record for the residual tranche
                $insPoc = $krfconn->prepare("
                    INSERT INTO krf_productionorder_consumed (
                        psono, sono, stockorderno, allocated_stockorderno, allocated_psono,
                        product_id, product_name, consumed_qty, wastage_qty, projectid,
                        created_by, created_at, confirmstatus, remark, status
                    ) VALUES (
                        ?, ?, ?, '', '',
                        ?, ?, ?, 0.000, ?,
                        ?, NOW(), 'Confirmed', ?, 'pending'
                    )
                ");
                if ($insPoc) {
                    $pocRemRemark = "Remaining unallocated balance of " . number_format($pendingQty) . " plants scheduled for {$formattedNewDate}";
                    $insPoc->bind_param("sssssdiss", $pPsono, $pSono, $pStkNo, $pProdId, $pProdName, $pendingQty, $pProjId, $loginid, $pocRemRemark);
                    $insPoc->execute();
                    $insPoc->close();
                }

                echo json_encode([
                    "status" => "success",
                    "message" => "Remaining balance of " . number_format($pendingQty) . " Plants re-scheduled to " . $formattedNewDate . " successfully. Fulfilled portion (" . number_format($fulfilledQty) . " Plants) remains unchanged on original date.",
                    "new_delivery_date" => $new_delivery_date,
                    "new_soi_id" => $newSoiId,
                    "reschedule_log" => $rescheduleLog
                ]);
                exit;
            }
        }
    }

    // 1. Update krf_sales_order_items
    if ($soi_id > 0) {
        $updSoi = $krfconn->prepare("
            UPDATE krf_sales_order_items 
            SET deliverydate = ?,
                confirmdeliverydate = ?,
                confirmdeliverydateremark = CONCAT(IF(confirmdeliverydateremark IS NULL OR confirmdeliverydateremark = '', '', CONCAT(confirmdeliverydateremark, ' | ')), ?),
                remark = CONCAT(IF(remark IS NULL OR remark = '', '', CONCAT(remark, ' | ')), ?)
            WHERE id = ?
        ");
        if ($updSoi) {
            $updSoi->bind_param("ssssi", $new_delivery_date, $new_delivery_date, $rescheduleLog, $rescheduleLog, $soi_id);
            $updSoi->execute();
            $updSoi->close();
        }
    } else if (!empty($sono) || !empty($psono)) {
        $updSoiAll = $krfconn->prepare("
            UPDATE krf_sales_order_items 
            SET deliverydate = ?,
                confirmdeliverydate = ?,
                confirmdeliverydateremark = CONCAT(IF(confirmdeliverydateremark IS NULL OR confirmdeliverydateremark = '', '', CONCAT(confirmdeliverydateremark, ' | ')), ?),
                remark = CONCAT(IF(remark IS NULL OR remark = '', '', CONCAT(remark, ' | ')), ?)
            WHERE (sono = ? AND ? != '') OR (psono = ? AND ? != '')
        ");
        if ($updSoiAll) {
            $updSoiAll->bind_param("ssssssss", $new_delivery_date, $new_delivery_date, $rescheduleLog, $rescheduleLog, $sono, $sono, $psono, $psono);
            $updSoiAll->execute();
            $updSoiAll->close();
        }
    }

    // 2. Update krf_provisional_sales_order (column is expecteddelivery)
    if (!empty($psono)) {
        $updPso = $krfconn->prepare("
            UPDATE krf_provisional_sales_order 
            SET expecteddelivery = ?,
                remarks = CONCAT(IF(remarks IS NULL OR remarks = '', '', CONCAT(remarks, ' | ')), ?)
            WHERE psono = ?
        ");
        if ($updPso) {
            $updPso->bind_param("sss", $new_delivery_date, $rescheduleLog, $psono);
            $updPso->execute();
            $updPso->close();
        }
    }

    // 3. Update krf_production_stockorder (columns expected_delivery_date, confirmdeliverydate, confirmdeliverydateremark, remarks)
    if (!empty($stockorder_no)) {
        $updStk = $krfconn->prepare("
            UPDATE krf_production_stockorder 
            SET expected_delivery_date = ?,
                confirmdeliverydate = ?,
                confirmdeliverydateremark = CONCAT(IF(confirmdeliverydateremark IS NULL OR confirmdeliverydateremark = '', '', CONCAT(confirmdeliverydateremark, ' | ')), ?),
                remarks = CONCAT(IF(remarks IS NULL OR remarks = '', '', CONCAT(remarks, ' | ')), ?)
            WHERE stockorder_no = ?
        ");
        if ($updStk) {
            $updStk->bind_param("sssss", $new_delivery_date, $new_delivery_date, $rescheduleLog, $rescheduleLog, $stockorder_no);
            $updStk->execute();
            $updStk->close();
        }
    }

    // 4. Update krf_sales_order remarks if sono is present
    if (!empty($sono)) {
        $updSo = $krfconn->prepare("
            UPDATE krf_sales_order 
            SET remarks = CONCAT(IF(remarks IS NULL OR remarks = '', '', CONCAT(remarks, ' | ')), ?)
            WHERE sono = ?
        ");
        if ($updSo) {
            $updSo->bind_param("ss", $rescheduleLog, $sono);
            $updSo->execute();
            $updSo->close();
        }
    }

    // 5. Update pending krf_deliveryaddress_location dispatch_datetime
    $newDispatchDt = $new_delivery_date . ' 00:00:00';
    $updDl = $krfconn->prepare("
        UPDATE krf_deliveryaddress_location 
        SET dispatch_datetime = ?,
            remark = CONCAT(IF(remark IS NULL OR remark = '', '', CONCAT(remark, ' | ')), ?)
        WHERE ((sono = ? AND ? != '') OR (psono = ? AND ? != ''))
          AND (delivery_status IS NULL OR delivery_status = 'Pending' OR delivery_status = '')
    ");
    if ($updDl) {
        $updDl->bind_param("ssssss", $newDispatchDt, $rescheduleLog, $sono, $sono, $psono, $psono);
        $updDl->execute();
        $updDl->close();
    }

    // 6. Update planned vehicle travel history
    $updVth = $krfconn->prepare("
        UPDATE krf_vehicle_travel_history 
        SET deliverydate = ?,
            send_remark = CONCAT(IF(send_remark IS NULL OR send_remark = '', '', CONCAT(send_remark, ' | ')), ?)
        WHERE ((stockorder_no = ? AND ? != '') OR (psono = ? AND ? != '') OR (stockorder_no = ? AND ? != ''))
          AND (status IS NULL OR status = 'Planned' OR status = '')
    ");
    if ($updVth) {
        $updVth->bind_param("ssssssss", $new_delivery_date, $rescheduleLog, $sono, $sono, $psono, $psono, $stockorder_no, $stockorder_no);
        $updVth->execute();
        $updVth->close();
    }

    echo json_encode([
        "status" => "success",
        "message" => "Delivery date re-scheduled to " . $formattedNewDate . " successfully.",
        "new_delivery_date" => $new_delivery_date,
        "formatted_delivery_date" => $formattedNewDate
    ]);
    exit;

} else if ($action == "AutoFillBalanceAsCash") {
    $projectid = intval($_POST['projectid'] ?? 0);
    $psono = trim($_POST['psono'] ?? '');
    $sono = trim($_POST['sono'] ?? '');
    $clientId = trim($_POST['clientid'] ?? '');
    $loginid = trim($_POST['loginid'] ?? 'adminsystem');
    $passedBalance = floatval($_POST['balance_amount'] ?? 0);
    $loginroleid = intval($_POST['loginroleid'] ?? $_SESSION['loginroleid'] ?? 99);

    if ($loginroleid >= 3) {
        echo json_encode(["status" => "error", "message" => "Access denied: Auto-fill cash is only permitted for users with role < 3."]);
        exit;
    }

    if (empty($psono) && empty($sono)) {
        echo json_encode(["status" => "error", "message" => "Order number (PSO/SO) is required."]);
        exit;
    }

    // 1. Calculate Order Total
    $orderTotal = 0;
    $companyid = 1;
    $subcompanyid = 1;
    $clientname = '';

    $sqlOrd = "SELECT companyid, subcompanyid, projectid, total_amount, grand_total, clientid, clientname FROM krf_sales_order WHERE (sono = ? AND ? != '') OR (psono = ? AND ? != '') LIMIT 1";
    $stmtOrd = $krfconn->prepare($sqlOrd);
    if ($stmtOrd) {
        $stmtOrd->bind_param("ssss", $sono, $sono, $psono, $psono);
        $stmtOrd->execute();
        $ordRow = $stmtOrd->get_result()->fetch_assoc();
        $stmtOrd->close();

        if ($ordRow) {
            $orderTotal = floatval($ordRow['grand_total'] ?? $ordRow['total_amount'] ?? 0);
            $companyid = intval($ordRow['companyid'] ?? 1);
            $subcompanyid = intval($ordRow['subcompanyid'] ?? 1);
            if ($projectid <= 0 && !empty($ordRow['projectid'])) $projectid = intval($ordRow['projectid']);
            if (empty($clientId)) $clientId = trim($ordRow['clientid'] ?? '');
            if (empty($clientname)) $clientname = trim($ordRow['clientname'] ?? '');
        }
    }

    if (empty($clientname) || $projectid <= 0) {
        $sqlPso = "SELECT companyid, subcompanyid, projectid, clientid, clientname FROM krf_provisional_sales_order WHERE psono = ? LIMIT 1";
        $stmtPso = $krfconn->prepare($sqlPso);
        if ($stmtPso) {
            $stmtPso->bind_param("s", $psono);
            $stmtPso->execute();
            $psoRow = $stmtPso->get_result()->fetch_assoc();
            $stmtPso->close();

            if ($psoRow) {
                if ($companyid <= 1 && !empty($psoRow['companyid'])) $companyid = intval($psoRow['companyid']);
                if ($subcompanyid <= 1 && !empty($psoRow['subcompanyid'])) $subcompanyid = intval($psoRow['subcompanyid']);
                if ($projectid <= 0 && !empty($psoRow['projectid'])) $projectid = intval($psoRow['projectid']);
                if (empty($clientId)) $clientId = trim($psoRow['clientid'] ?? '');
                if (empty($clientname)) $clientname = trim($psoRow['clientname'] ?? '');
            }
        }
    }

    if ($orderTotal <= 0) {
        $itemSql = "SELECT SUM(amount) as item_total FROM krf_sales_order_items WHERE (sono = ? AND ? != '') OR (psono = ? AND ? != '')";
        $stmtIt = $krfconn->prepare($itemSql);
        if ($stmtIt) {
            $stmtIt->bind_param("ssss", $sono, $sono, $psono, $psono);
            $stmtIt->execute();
            $itRow = $stmtIt->get_result()->fetch_assoc();
            $orderTotal = floatval($itRow['item_total'] ?? 0);
            $stmtIt->close();
        }
    }

    // 2. Fetch already paid amount
    $totalPaid = 0;
    $paySql = "
        SELECT IFNULL(SUM(advanceamount), 0) as total_paid, IFNULL(SUM(otherdiscount), 0) as total_disc
        FROM advance_payment_transactions 
        WHERE ((invoiceno = ? AND ? != '') 
           OR (invoiceno = ? AND ? != ''))
          AND (tedit IS NULL OR tedit = '')
    ";
    $payStmt = $krfconn->prepare($paySql);
    if ($payStmt) {
        $payStmt->bind_param("ssss", $psono, $psono, $sono, $sono);
        $payStmt->execute();
        $pRow = $payStmt->get_result()->fetch_assoc();
        if ($pRow) {
            $totalPaid = floatval($pRow['total_paid'] ?? 0) + floatval($pRow['total_disc'] ?? 0);
        }
        $payStmt->close();
    }

    $balance = ($passedBalance > 0) ? $passedBalance : round(max(0, $orderTotal - $totalPaid), 2);
    if ($balance <= 0) {
        echo json_encode(["status" => "error", "message" => "Order balance is already clear (₹ 0.00 Due)."]);
        exit;
    }

    // 3. Fetch Client Name if still empty
    if (empty($clientname) && !empty($clientId)) {
        $cStmt = $krfconn->prepare("SELECT clientname, companyid, subcompanyid, projectid FROM master_clients WHERE clientid = ? LIMIT 1");
        if ($cStmt) {
            $cStmt->bind_param("s", $clientId);
            $cStmt->execute();
            $cRes = $cStmt->get_result();
            if ($cRes && $cRow = $cRes->fetch_assoc()) {
                $clientname = $cRow['clientname'] ?? '';
                if ($companyid <= 1 && !empty($cRow['companyid'])) $companyid = intval($cRow['companyid']);
                if ($subcompanyid <= 1 && !empty($cRow['subcompanyid'])) $subcompanyid = intval($cRow['subcompanyid']);
                if ($projectid <= 0 && !empty($cRow['projectid'])) $projectid = intval($cRow['projectid']);
            }
            $cStmt->close();
        }
    }

    // 4. Insert Cash Transaction
    $invNo = !empty($sono) ? $sono : $psono;
    $utr = "CASH-AUTO-" . date('YmdHis');
    $purpose = "paymentagainstinvoice";
    $remarks = "Auto-filled pending balance as Cash by {$loginid}";
    $today = date('Y-m-d');
    $receivedBy = !empty($loginid) ? $loginid : 'Admin';

    $insSql = "
        INSERT INTO advance_payment_transactions 
        (companyid, subcompanyid, projectid, clientid, clientname, advanceamount, utrnumber, invoiceno, paymentdate, purposeofpayment, sourceofpayment, receivedtoaccountid, receivedby, receivedlocation, remarks, verification, verificationby, verificationat, allocated_datetime, transaction_type) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'paymentagainstinvoice', 'Cash', '0', ?, 'Farm / Office', ?, 'verified', ?, NOW(), NOW(), 'Receipt')
    ";
    $insStmt = $krfconn->prepare($insSql);
    if (!$insStmt) {
        echo json_encode(["status" => "error", "message" => "SQL prepare failed: " . $krfconn->error]);
        exit;
    }
    $insStmt->bind_param("ssssssssssss", $companyid, $subcompanyid, $projectid, $clientId, $clientname, $balance, $utr, $invNo, $today, $receivedBy, $remarks, $loginid);
    $res = $insStmt->execute();
    if (!$res) {
        $err = $insStmt->error;
        $insStmt->close();
        echo json_encode(["status" => "error", "message" => "Insert failed: " . $err]);
        exit;
    }
    $insStmt->close();

    echo json_encode([
        "status" => "success",
        "message" => "Pending balance of ₹ " . number_format($balance, 2) . " auto-filled as Cash by {$loginid}."
    ]);
    exit;

} else if ($action == "SetInvoicePdfPreviewData") {
    $payloadRaw = $_POST['payload'] ?? '';
    $payload = json_decode($payloadRaw, true);
    if (!is_array($payload)) {
        echo json_encode(["status" => "error", "message" => "Invalid payload data."]);
        exit;
    }
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $token = bin2hex(random_bytes(16));
    if (!isset($_SESSION['dinv_preview_data'])) {
        $_SESSION['dinv_preview_data'] = [];
    }
    $_SESSION['dinv_preview_data'][$token] = $payload;
    echo json_encode(["status" => "success", "token" => $token]);
    exit;

} else {
    echo json_encode(["status" => "error", "message" => "Invalid API action."]);
    exit;
}
?>
