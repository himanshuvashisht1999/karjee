<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(0);
ini_set('display_errors', '0');
date_default_timezone_set('Asia/Kolkata');

require_once(__DIR__ . '/../../../connection.php');
header('Content-Type: application/json; charset=utf-8');

$loginid      = $_SESSION['loginid'] ?? $_SESSION['userid'] ?? 'admin';
$loginname    = $_SESSION['loginname'] ?? $_SESSION['username'] ?? $_SESSION['name'] ?? 'Admin';
$loginroleid  = (int)($_SESSION['loginroleid'] ?? $_REQUEST['loginroleid'] ?? 4);
$companyid    = (int)($_SESSION['companyid'] ?? 1);
$subcompanyid = (int)($_SESSION['subcompanyid'] ?? 1);
$projectid    = (int)($_SESSION['projectid'] ?? 17);

if ($projectid <= 0) $projectid = 17;

$action = trim($_REQUEST['action'] ?? '');

function outJson($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($action)) {
    outJson(['status' => 'error', 'message' => 'No action specified.']);
}

// =========================================================================
// ACTION 1: GET DROPDOWN FILTER OPTIONS (Locations, Polyhouses, Beds)
// =========================================================================
if ($action === 'get_filters') {
    $locations = [];
    if (isset($hrmconn)) {
        $locSql = "SELECT locationid, locationname FROM hrm_AttendanceLocationMaster WHERE (projectid = ? OR ? = 0 OR projectid = '17') ORDER BY locationname ASC";
        $locStmt = $hrmconn->prepare($locSql);
        if ($locStmt) {
            $pidStr = (string)$projectid;
            $locStmt->bind_param('si', $pidStr, $projectid);
            $locStmt->execute();
            $locRes = $locStmt->get_result();
            while ($lr = $locRes->fetch_assoc()) {
                $locations[] = ['id' => $lr['locationid'], 'name' => $lr['locationname']];
            }
            $locStmt->close();
        }
    }

    $polyhouses = [];
    $phSql = "SELECT polyhouseid, polyhousename, locationid, beddetails FROM krf_polyhouse_master WHERE (projectid = ? OR ? = 0 OR projectid = 17) AND (status = 'Active' OR status IS NULL) ORDER BY polyhouseid ASC";
    $phStmt = $krfconn->prepare($phSql);
    if ($phStmt) {
        $phStmt->bind_param('ii', $projectid, $projectid);
        $phStmt->execute();
        $phRes = $phStmt->get_result();
        while ($pr = $phRes->fetch_assoc()) {
            $beds = [];
            $decoded = json_decode($pr['beddetails'] ?? '[]', true);
            if (is_array($decoded)) {
                foreach ($decoded as $b) {
                    $bname = trim($b['bedname'] ?? $b['name'] ?? $b['bed_no'] ?? '');
                    if ($bname !== '' && !in_array($bname, $beds)) $beds[] = $bname;
                }
            }

            // Also include any distinct beds existing in plant details
            $plBedsStmt = $krfconn->prepare("SELECT DISTINCT bed_no FROM krf_polyhouse_plant_details WHERE (projectid = ? OR ? = 0 OR projectid = 17) AND polyhouseid = ? AND bed_no IS NOT NULL AND TRIM(bed_no) <> '' AND (tedit IS NULL OR tedit = '')");
            if ($plBedsStmt) {
                $plBedsStmt->bind_param('iis', $projectid, $projectid, $pr['polyhouseid']);
                $plBedsStmt->execute();
                $bRes = $plBedsStmt->get_result();
                while ($br = $bRes->fetch_assoc()) {
                    $bname = trim($br['bed_no']);
                    if ($bname !== '' && !in_array($bname, $beds)) $beds[] = $bname;
                }
                $plBedsStmt->close();
            }

            if (!empty($beds)) natsort($beds);

            $polyhouses[] = [
                'id' => $pr['polyhouseid'],
                'name' => $pr['polyhousename'] ?: ('PH-' . $pr['polyhouseid']),
                'locationid' => $pr['locationid'],
                'beds' => array_values($beds)
            ];
        }
        $phStmt->close();
    }

    outJson([
        'status' => 'success',
        'locations' => $locations,
        'polyhouses' => $polyhouses
    ]);
}

// =========================================================================
// ACTION 2: GET OCCUPIED TRAYS & RE-VERIFY TRAYS FOR SELECTED BED
// =========================================================================
if ($action === 'get_bed_trays') {
    $polyhouseid = trim($_REQUEST['polyhouseid'] ?? '');
    $bed_no      = trim($_REQUEST['bed_no'] ?? '');
    $locationid  = trim($_REQUEST['locationid'] ?? '');

    if (empty($polyhouseid) || empty($bed_no)) {
        outJson(['status' => 'error', 'message' => 'Polyhouse and Bed Number are required.']);
    }

    $whereBase = "WHERE (p.projectid = '{$projectid}' OR '{$projectid}' = '0' OR p.projectid = '17')
                    AND (p.tedit IS NULL OR p.tedit = '')
                    AND p.polyhouseid = '" . $krfconn->real_escape_string($polyhouseid) . "'
                    AND p.bed_no = '" . $krfconn->real_escape_string($bed_no) . "'";

    if (!empty($locationid)) {
        $whereBase .= " AND (p.locationid = '" . $krfconn->real_escape_string($locationid) . "' OR p.locationid = '' OR p.locationid IS NULL)";
    }

    // 1. Fetch Occupied Trays (excluding terminal statuses and already reverify)
    $sqlOccupied = "
        SELECT 
            p.polyhouseplantid,
            p.barcode,
            p.stockorder_no,
            p.psono,
            p.productid,
            p.productname,
            p.variety,
            p.tray_id,
            p.tray_code,
            p.qtypertray,
            p.failurecount,
            p.growth_stage,
            p.health_status,
            p.status,
            p.workingstatus,
            p.bed_no,
            p.polyhouseid,
            p.locationid,
            p.sowing_date,
            p.sowingdate_internal,
            p.planted_date,
            p.created_at,
            p.movementremark,
            ord.clientid,
            cm.client_name,
            (CASE WHEN p.status = 'Readytosale' OR p.growth_stage = 'ReadyToSale' THEN 1 ELSE 0 END) AS is_rts
        FROM krf_polyhouse_plant_details p
        LEFT JOIN krf_production_stockorder ord 
            ON ord.projectid = p.projectid 
           AND ord.stockorder_no = p.stockorder_no 
           AND ord.productid = p.productid
           AND (ord.tedit IS NULL OR ord.tedit = '')
        LEFT JOIN krf_client_master cm 
            ON cm.client_id = ord.clientid 
           AND cm.ProjectId = p.projectid
        {$whereBase}
          AND p.status IN ('Polyhouse', 'Readytosale', 'Ready to sale', 'ReadyToSale', 'OpenStock')
          AND p.status NOT IN (
              'Final Dispatch', 'Internal Dispatch', 'Removed', 'Empty After Shifting',
              'Empty After Grafting', 'Destroy After grafted', 'Tray Free After Shifting', 'Tray Dead',
              'reverify', 'Reverify', 'reverify'
          )
        ORDER BY p.stockorder_no ASC, p.polyhouseplantid ASC
    ";

    $resOcc = $krfconn->query($sqlOccupied);
    $occupiedList = [];
    $totalOccupiedPlants = 0;
    if ($resOcc) {
        while ($row = $resOcc->fetch_assoc()) {
            $qty = (int)($row['qtypertray'] ?: 209);
            $fail = (int)($row['failurecount'] ?: 0);
            $actual = max(0, $qty - $fail);
            $row['actual_plants'] = $actual;
            $totalOccupiedPlants += $actual;
            $occupiedList[] = $row;
        }
    }

    // 2. Fetch Re-verify Trays (status = 'reverify' or 'Reverify' or 'reverify')
    $sqlReverify = "
        SELECT 
            p.polyhouseplantid,
            p.barcode,
            p.stockorder_no,
            p.psono,
            p.productid,
            p.productname,
            p.variety,
            p.tray_id,
            p.tray_code,
            p.qtypertray,
            p.failurecount,
            p.growth_stage,
            p.health_status,
            p.status,
            p.workingstatus,
            p.bed_no,
            p.polyhouseid,
            p.locationid,
            p.sowing_date,
            p.sowingdate_internal,
            p.planted_date,
            p.created_at,
            p.updated_at,
            p.updated_by,
            p.movementremark,
            ord.clientid,
            cm.client_name
        FROM krf_polyhouse_plant_details p
        LEFT JOIN krf_production_stockorder ord 
            ON ord.projectid = p.projectid 
           AND ord.stockorder_no = p.stockorder_no 
           AND ord.productid = p.productid
           AND (ord.tedit IS NULL OR ord.tedit = '')
        LEFT JOIN krf_client_master cm 
            ON cm.client_id = ord.clientid 
           AND cm.ProjectId = p.projectid
        {$whereBase}
          AND p.status IN ('reverify', 'Reverify', 'reverify')
        ORDER BY p.updated_at DESC, p.polyhouseplantid ASC
    ";

    $resRev = $krfconn->query($sqlReverify);
    $reverifyList = [];
    $totalReverifyPlants = 0;
    if ($resRev) {
        while ($row = $resRev->fetch_assoc()) {
            $qty = (int)($row['qtypertray'] ?: 209);
            $fail = (int)($row['failurecount'] ?: 0);
            $actual = max(0, $qty - $fail);
            $row['actual_plants'] = $actual;
            $totalReverifyPlants += $actual;
            $reverifyList[] = $row;
        }
    }

    // 3. Compute Orders Summary for Bed
    $ordersMap = [];
    foreach ($occupiedList as $t) {
        $ordKey = trim($t['stockorder_no'] ?: $t['psono'] ?: 'Unknown');
        if (!isset($ordersMap[$ordKey])) {
            $ordersMap[$ordKey] = [
                'order_no'        => $ordKey,
                'psono'           => trim($t['psono'] ?: ''),
                'productname'     => trim($t['productname'] ?: ''),
                'variety'         => trim($t['variety'] ?: ''),
                'occupied_count'  => 0,
                'occupied_plants' => 0,
                'reverify_count'  => 0,
                'reverify_plants' => 0,
                'client_name'     => trim($t['client_name'] ?: '')
            ];
        }
        $ordersMap[$ordKey]['occupied_count']++;
        $ordersMap[$ordKey]['occupied_plants'] += (int)($t['actual_plants'] ?: 0);
    }
    foreach ($reverifyList as $t) {
        $ordKey = trim($t['stockorder_no'] ?: $t['psono'] ?: 'Unknown');
        if (!isset($ordersMap[$ordKey])) {
            $ordersMap[$ordKey] = [
                'order_no'        => $ordKey,
                'psono'           => trim($t['psono'] ?: ''),
                'productname'     => trim($t['productname'] ?: ''),
                'variety'         => trim($t['variety'] ?: ''),
                'occupied_count'  => 0,
                'occupied_plants' => 0,
                'reverify_count'  => 0,
                'reverify_plants' => 0,
                'client_name'     => trim($t['client_name'] ?: '')
            ];
        }
        $ordersMap[$ordKey]['reverify_count']++;
        $ordersMap[$ordKey]['reverify_plants'] += (int)($t['actual_plants'] ?: 0);
    }

    outJson([
        'status' => 'success',
        'data' => [
            'polyhouseid'        => $polyhouseid,
            'bed_no'             => $bed_no,
            'occupied_trays'     => $occupiedList,
            'reverify_trays'     => $reverifyList,
            'occupied_count'     => count($occupiedList),
            'occupied_plants'    => $totalOccupiedPlants,
            'reverify_count'     => count($reverifyList),
            'reverify_plants'    => $totalReverifyPlants,
            'orders_summary'     => array_values($ordersMap)
        ]
    ]);
}

// =========================================================================
// ACTION 3: SCAN LOOKUP (Barcode, Bed QR, or Order Number)
// =========================================================================
if ($action === 'scan_lookup') {
    $query = trim($_REQUEST['query'] ?? '');
    if (empty($query)) {
        outJson(['status' => 'error', 'message' => 'Please enter or scan a Tray Barcode, Bed QR, or Order Number.']);
    }

    // Check if user scanned Bed QR slip
    $parsedBed = '';
    if (strpos($query, "\n") !== false || strpos($query, ":") !== false) {
        $lines = explode("\n", $query);
        foreach ($lines as $line) {
            $line = trim($line);
            if (stripos($line, 'Bed:') === 0) {
                $parsedBed = trim(substr($line, 4));
            }
        }
    }

    $cleanQuery = trim($query);
    $qEsc = $krfconn->real_escape_string($cleanQuery);
    $bedEsc = !empty($parsedBed) ? $krfconn->real_escape_string($parsedBed) : '';

    // 1. First priority: Check exact Tray Barcode or Bed QR
    $findSql = "
        SELECT 
            p.polyhouseplantid,
            p.barcode,
            p.polyhouseid,
            p.bed_no,
            p.locationid,
            p.status,
            p.productname,
            p.variety,
            p.stockorder_no,
            p.qtypertray
        FROM krf_polyhouse_plant_details p
        WHERE (p.projectid = '{$projectid}' OR '{$projectid}' = '0' OR p.projectid = '17')
          AND (p.tedit IS NULL OR p.tedit = '')
          AND (p.barcode = '{$qEsc}' OR p.bedqrcode = '{$qEsc}'" . (!empty($bedEsc) ? " OR p.bed_no = '{$bedEsc}'" : "") . ")
        LIMIT 1
    ";

    $fRes = $krfconn->query($findSql);
    if ($fRes && $row = $fRes->fetch_assoc()) {
        outJson([
            'status'     => 'success',
            'match_type' => ($row['barcode'] === $cleanQuery) ? 'tray' : 'bed',
            'data'       => $row
        ]);
    }

    // 2. Second priority: Search by Order Number / PSO No (Exact & Prefix Match)
    $orderSql = "
        SELECT 
            p.polyhouseid,
            COALESCE(ph.polyhousename, CONCAT('PH-', p.polyhouseid)) AS polyhousename,
            p.bed_no,
            p.locationid,
            p.stockorder_no,
            p.psono,
            p.productname,
            p.variety,
            COUNT(*) AS tray_count,
            SUM(CASE WHEN p.status = 'reverify' THEN 1 ELSE 0 END) AS reverify_count
        FROM krf_polyhouse_plant_details p
        LEFT JOIN krf_polyhouse_master ph ON ph.polyhouseid = p.polyhouseid AND (ph.tedit IS NULL OR ph.tedit = '')
        WHERE (p.projectid = '{$projectid}' OR '{$projectid}' = '0' OR p.projectid = '17')
          AND (p.tedit IS NULL OR p.tedit = '')
          AND (p.stockorder_no = '{$qEsc}' OR p.psono = '{$qEsc}' OR p.stockorder_no LIKE '{$qEsc}%' OR p.psono LIKE '{$qEsc}%')
          AND p.status IN ('Polyhouse', 'Readytosale', 'Ready to sale', 'ReadyToSale', 'OpenStock', 'reverify', 'Reverify', 'reverify')
        GROUP BY p.polyhouseid, p.bed_no, p.locationid, ph.polyhousename, p.stockorder_no, p.psono, p.productname, p.variety
        ORDER BY tray_count DESC
    ";

    $ordRes = $krfconn->query($orderSql);
    $orderBeds = [];
    $totalTraysInOrder = 0;
    $matchedOrderNo = '';

    if ($ordRes) {
        while ($ob = $ordRes->fetch_assoc()) {
            if (empty($matchedOrderNo)) {
                $matchedOrderNo = $ob['stockorder_no'] ?: $ob['psono'];
            }
            $tCount = (int)$ob['tray_count'];
            $totalTraysInOrder += $tCount;
            $orderBeds[] = [
                'polyhouseid'    => $ob['polyhouseid'],
                'polyhousename'  => $ob['polyhousename'],
                'bed_no'         => $ob['bed_no'],
                'locationid'     => $ob['locationid'],
                'stockorder_no'  => $ob['stockorder_no'],
                'psono'          => $ob['psono'],
                'productname'    => $ob['productname'],
                'variety'        => $ob['variety'],
                'tray_count'     => $tCount,
                'reverify_count' => (int)$ob['reverify_count']
            ];
        }
    }

    if (!empty($orderBeds)) {
        outJson([
            'status'              => 'success',
            'match_type'          => 'order',
            'order_no'            => $matchedOrderNo ?: $cleanQuery,
            'total_trays'         => $totalTraysInOrder,
            'primary_polyhouseid' => $orderBeds[0]['polyhouseid'],
            'primary_bed_no'      => $orderBeds[0]['bed_no'],
            'primary_locationid'  => $orderBeds[0]['locationid'],
            'beds'                => $orderBeds
        ]);
    }

    outJson([
        'status'  => 'error',
        'message' => "No active tray barcode, bed, or order found matching '{$cleanQuery}'."
    ]);
}

// =========================================================================
// ACTION 4: MARK ALL OCCUPIED TRAYS OF AN ORDER IN BED AS 'reverify'
// =========================================================================
if ($action === 'mark_reverify') {
    $orderNo     = trim($_POST['order_no'] ?? '');
    $polyhouseid = trim($_POST['polyhouseid'] ?? '');
    $bed_no      = trim($_POST['bed_no'] ?? '');
    $plantIds    = $_POST['plant_ids'] ?? [];
    $remark      = trim($_POST['remark'] ?? '');

    $cleanIds = [];

    // Prioritize Order-level processing (Rule: User marks ALL occupied trays of that order in this bed)
    if (!empty($orderNo) && !empty($polyhouseid) && !empty($bed_no)) {
        $ordEsc = $krfconn->real_escape_string($orderNo);
        $phEsc  = $krfconn->real_escape_string($polyhouseid);
        $bedEsc = $krfconn->real_escape_string($bed_no);

        $fetchSql = "
            SELECT polyhouseplantid 
            FROM krf_polyhouse_plant_details 
            WHERE (projectid = '{$projectid}' OR '{$projectid}' = '0' OR projectid = '17')
              AND (tedit IS NULL OR tedit = '')
              AND polyhouseid = '{$phEsc}' 
              AND bed_no = '{$bedEsc}' 
              AND (stockorder_no = '{$ordEsc}' OR psono = '{$ordEsc}')
              AND status IN ('Polyhouse', 'Readytosale', 'Ready to sale', 'ReadyToSale', 'OpenStock')
              AND status NOT IN (
                  'Final Dispatch', 'Internal Dispatch', 'Removed', 'Empty After Shifting',
                  'Empty After Grafting', 'Destroy After grafted', 'Tray Free After Shifting', 'Tray Dead',
                  'reverify', 'Reverify', 'reverify'
              )
        ";
        $fRes = $krfconn->query($fetchSql);
        if ($fRes) {
            while ($r = $fRes->fetch_assoc()) {
                $cleanIds[] = (int)$r['polyhouseplantid'];
            }
        }

        if (empty($cleanIds)) {
            outJson(['status' => 'error', 'message' => "No active occupied trays found for Order '{$orderNo}' in Bed {$bed_no}."]);
        }
    } elseif (is_array($plantIds) && !empty($plantIds)) {
        $cleanIds = array_filter(array_map('intval', $plantIds));
    }

    if (empty($cleanIds)) {
        outJson(['status' => 'error', 'message' => 'Please select an order to mark all its occupied trays for re-verification.']);
    }

    $nowDt = date('d-M-Y h:i A');
    $curDate = date('Y-m-d');
    $orderLabel = !empty($orderNo) ? "Order: {$orderNo} | " : "";
    $remarkLine = "[Marked for Re-verification ({$orderLabel}" . count($cleanIds) . " trays) by {$loginname} on {$nowDt}" . (!empty($remark) ? " | Reason: {$remark}" : "") . "]";
    $remarkEsc = $krfconn->real_escape_string($remarkLine);
    $userEsc = $krfconn->real_escape_string($loginname);

    $idListStr = implode(',', $cleanIds);

    $krfconn->begin_transaction();
    try {
        // 1. Update status to 'reverify' in krf_polyhouse_plant_details while preserving previous status
        $updSql = "
            UPDATE krf_polyhouse_plant_details
            SET 
                movementremark = CONCAT(COALESCE(movementremark, ''), IF(COALESCE(movementremark, '') = '', '', '\n'), '[PREV_STATUS:', status, '] {$remarkEsc}'),
                status = 'reverify',
                workingstatus = 'reverify',
                move_at = '{$curDate}',
                updated_at = NOW(),
                updated_by = '{$userEsc}'
            WHERE polyhouseplantid IN ({$idListStr})
        ";
        if (!$krfconn->query($updSql)) {
            throw new Exception("Failed to update status to reverify: " . $krfconn->error);
        }

        // 2. Put corresponding ready stock records on hold with re-verify remark
        $updRtsSql = "
            UPDATE krf_readytosale_stocks
            SET 
                remarks = CONCAT(COALESCE(remarks, ''), IF(COALESCE(remarks, '') = '', '', ' | '), '{$remarkEsc}'),
                updated_at = NOW()
            WHERE polyhouseplantid IN ({$idListStr})
        ";
        $krfconn->query($updRtsSql);

        $krfconn->commit();

        outJson([
            'status' => 'success',
            'message' => "All " . count($cleanIds) . " occupied tray(s){$orderMsg} successfully marked as 'reverify'!",
            'count' => count($cleanIds)
        ]);
    } catch (Exception $e) {
        $krfconn->rollback();
        outJson(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// =========================================================================
// ACTION 5: RESOLVE RE-VERIFY TRAYS -> 'Empty After Shifting'
// =========================================================================
if ($action === 'resolve_empty_shifting') {
    $plantIds = $_POST['plant_ids'] ?? [];
    $remark   = trim($_POST['remark'] ?? '');

    if (!is_array($plantIds) || empty($plantIds)) {
        outJson(['status' => 'error', 'message' => 'No trays selected to resolve as Empty After Shifting.']);
    }

    $cleanIds = array_filter(array_map('intval', $plantIds));
    if (empty($cleanIds)) {
        outJson(['status' => 'error', 'message' => 'Invalid tray IDs provided.']);
    }
    $idListStr = implode(',', $cleanIds);

    $nowDt = date('d-M-Y h:i A');
    $curDate = date('Y-m-d');
    $remarkLine = "[Status changed to 'Empty After Shifting' by {$loginname} on {$nowDt}" . (!empty($remark) ? " | Note: {$remark}" : "") . "]";
    $remarkEsc = $krfconn->real_escape_string($remarkLine);
    $userEsc = $krfconn->real_escape_string($loginname);

    $krfconn->begin_transaction();
    try {
        // 1. Update krf_polyhouse_plant_details
        $updSql = "
            UPDATE krf_polyhouse_plant_details
            SET 
                status = 'Empty After Shifting',
                workingstatus = 'Empty After Shifting',
                move_at = '{$curDate}',
                movementremark = CONCAT(COALESCE(movementremark, ''), IF(COALESCE(movementremark, '') = '', '', '\n'), '{$remarkEsc}'),
                updated_at = NOW(),
                updated_by = '{$userEsc}'
            WHERE polyhouseplantid IN ({$idListStr})
        ";
        if (!$krfconn->query($updSql)) {
            throw new Exception("Error resolving Empty After Shifting: " . $krfconn->error);
        }

        // 2. Update ready stocks
        $updRts = "
            UPDATE krf_readytosale_stocks
            SET 
                status = 'Empty After Shifting',
                remarks = CONCAT(COALESCE(remarks, ''), IF(COALESCE(remarks, '') = '', '', ' | '), '{$remarkEsc}'),
                updated_at = NOW()
            WHERE polyhouseplantid IN ({$idListStr})
        ";
        $krfconn->query($updRts);

        $krfconn->commit();

        outJson([
            'status' => 'success',
            'message' => count($cleanIds) . " tray(s) marked as 'Empty After Shifting' and vacated from bed.",
            'count' => count($cleanIds)
        ]);
    } catch (Exception $e) {
        $krfconn->rollback();
        outJson(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// =========================================================================
// ACTION 5B: RESOLVE RE-VERIFY TRAYS -> 'Move for Grafting'
// =========================================================================
if ($action === 'resolve_move_grafting') {
    $plantIds = $_POST['plant_ids'] ?? [];
    $remark   = trim($_POST['remark'] ?? '');

    if (empty($plantIds)) {
        outJson(['status' => 'error', 'message' => 'No trays selected to move for grafting.']);
    }

    $cleanIds = array_filter(array_map('intval', $plantIds));
    if (empty($cleanIds)) {
        outJson(['status' => 'error', 'message' => 'Invalid tray IDs provided.']);
    }
    $idListStr = implode(',', $cleanIds);

    $nowDt = date('d-M-Y h:i A');
    $curDate = date('Y-m-d');
    $remarkLine = "[Status changed to 'Move for Grafting' by {$loginname} on {$nowDt}" . (!empty($remark) ? " | Reason: {$remark}" : "") . "]";
    $remarkEsc = $krfconn->real_escape_string($remarkLine);
    $userEsc = $krfconn->real_escape_string($loginname);

    $krfconn->begin_transaction();
    try {
        $updSql = "
            UPDATE krf_polyhouse_plant_details
            SET 
                status = 'Move for Grafting',
                workingstatus = 'Move for Grafting',
                move_at = '{$curDate}',
                movementremark = CONCAT(COALESCE(movementremark, ''), IF(COALESCE(movementremark, '') = '', '', '\n'), '{$remarkEsc}'),
                updated_at = NOW(),
                updated_by = '{$userEsc}'
            WHERE polyhouseplantid IN ({$idListStr})
        ";
        if (!$krfconn->query($updSql)) {
            throw new Exception("Error updating trays to Move for Grafting: " . $krfconn->error);
        }

        $updRtsSql = "
            UPDATE krf_readytosale_stocks
            SET 
                status = 'Move for Grafting',
                remarks = CONCAT(COALESCE(remarks, ''), IF(COALESCE(remarks, '') = '', '', ' | '), '{$remarkEsc}'),
                updated_at = NOW()
            WHERE polyhouseplantid IN ({$idListStr})
        ";
        $krfconn->query($updRtsSql);

        $krfconn->commit();

        outJson([
            'status' => 'success',
            'message' => count($cleanIds) . " tray(s) marked as 'Move for Grafting'!",
            'count' => count($cleanIds)
        ]);
    } catch (Exception $e) {
        $krfconn->rollback();
        outJson(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// =========================================================================
// ACTION 6: RESOLVE RE-VERIFY TRAYS -> 'Removed' (Deduct Qty & Delete RTS)
// =========================================================================
if ($action === 'resolve_removed') {
    $plantIds = $_POST['plant_ids'] ?? [];
    $remark   = trim($_POST['remark'] ?? '');

    if (!is_array($plantIds) || empty($plantIds)) {
        outJson(['status' => 'error', 'message' => 'No trays selected to remove.']);
    }

    $cleanIds = array_filter(array_map('intval', $plantIds));
    if (empty($cleanIds)) {
        outJson(['status' => 'error', 'message' => 'Invalid tray IDs provided.']);
    }
    $idListStr = implode(',', $cleanIds);

    // Fetch details of trays being removed to calculate quantity deduction
    $fetchSql = "SELECT polyhouseplantid, barcode, stockorder_no, psono, qtypertray, failurecount 
                 FROM krf_polyhouse_plant_details 
                 WHERE polyhouseplantid IN ({$idListStr})";
    $fRes = $krfconn->query($fetchSql);
    $trayRows = [];
    $soDeductions = [];
    $barcodes = [];

    if ($fRes) {
        while ($r = $fRes->fetch_assoc()) {
            $trayRows[] = $r;
            if (!empty($r['barcode'])) $barcodes[] = $r['barcode'];

            $so = trim($r['stockorder_no'] ?: $r['psono']);
            $qty = (int)($r['qtypertray'] ?: 209);
            if (!empty($so)) {
                if (!isset($soDeductions[$so])) $soDeductions[$so] = 0;
                $soDeductions[$so] += $qty;
            }
        }
    }

    if (empty($trayRows)) {
        outJson(['status' => 'error', 'message' => 'Selected tray records not found in database.']);
    }

    $nowDt = date('d-M-Y h:i A');
    $curDate = date('Y-m-d');
    $remarkLine = "[Removed from Re-verify by {$loginname} on {$nowDt}" . (!empty($remark) ? " | Reason: {$remark}" : "") . "]";
    $remarkEsc = $krfconn->real_escape_string($remarkLine);
    $userEsc = $krfconn->real_escape_string($loginname);

    $krfconn->begin_transaction();
    try {
        // 1. Decrement quantity in krf_production_stockorder for each order
        foreach ($soDeductions as $soNo => $plantsToRemove) {
            $soEsc = $krfconn->real_escape_string($soNo);
            $soDownSql = "UPDATE krf_production_stockorder 
                          SET quantity = GREATEST(0, quantity - {$plantsToRemove}), 
                              sowingallotedqty = GREATEST(0, sowingallotedqty - {$plantsToRemove}), 
                              updated_at = NOW() 
                          WHERE (projectid = '{$projectid}' OR '{$projectid}' = '0' OR projectid = '17') 
                            AND (stockorder_no = '{$soEsc}' OR psono = '{$soEsc}')";
            $krfconn->query($soDownSql);
        }

        // 2. Update krf_polyhouse_plant_details to 'Removed'
        $updPlantSql = "
            UPDATE krf_polyhouse_plant_details
            SET 
                status = 'Removed',
                workingstatus = 'Removed',
                move_at = '{$curDate}',
                movementremark = CONCAT(COALESCE(movementremark, ''), IF(COALESCE(movementremark, '') = '', '', '\n'), '{$remarkEsc}'),
                updated_at = NOW(),
                updated_by = '{$userEsc}'
            WHERE polyhouseplantid IN ({$idListStr})
        ";
        if (!$krfconn->query($updPlantSql)) {
            throw new Exception("Error updating plant removal: " . $krfconn->error);
        }

        // 3. Delete active entries from krf_readytosale_stocks
        $rtsConds = ["polyhouseplantid IN ({$idListStr})"];
        if (!empty($barcodes)) {
            $bcEscList = "'" . implode("','", array_map([$krfconn, 'real_escape_string'], $barcodes)) . "'";
            $rtsConds[] = "barcode IN ({$bcEscList})";
        }
        $rtsWhere = implode(" OR ", $rtsConds);
        $krfconn->query("DELETE FROM krf_readytosale_stocks WHERE ({$rtsWhere})");

        $krfconn->commit();

        outJson([
            'status' => 'success',
            'message' => count($trayRows) . " tray(s) marked as 'Removed', stock order inventory adjusted, and ready stock cleared.",
            'count' => count($trayRows)
        ]);
    } catch (Exception $e) {
        $krfconn->rollback();
        outJson(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// =========================================================================
// ACTION 7: RESOLVE RE-VERIFY TRAYS -> 'Dispatch' (Final or Internal)
// =========================================================================
if ($action === 'resolve_dispatch') {
    $plantIds       = $_POST['plant_ids'] ?? [];
    $dispatchType   = trim($_POST['dispatch_type'] ?? 'Final Dispatch');
    $dispatchTarget = trim($_POST['dispatch_target'] ?? '');
    $remark         = trim($_POST['remark'] ?? '');

    if (!in_array($dispatchType, ['Final Dispatch', 'Internal Dispatch'])) {
        $dispatchType = 'Final Dispatch';
    }

    if (!is_array($plantIds) || empty($plantIds)) {
        outJson(['status' => 'error', 'message' => 'No trays selected for dispatch.']);
    }

    $cleanIds = array_filter(array_map('intval', $plantIds));
    if (empty($cleanIds)) {
        outJson(['status' => 'error', 'message' => 'Invalid tray IDs provided.']);
    }
    $idListStr = implode(',', $cleanIds);

    $nowDt = date('d-M-Y h:i A');
    $curDate = date('Y-m-d');
    $notePart = !empty($dispatchTarget) ? "Target: {$dispatchTarget}" : "";
    if (!empty($remark)) {
        $notePart .= ($notePart ? " | " : "") . "Note: {$remark}";
    }
    $remarkLine = "[Dispatched ({$dispatchType}) by {$loginname} on {$nowDt}" . (!empty($notePart) ? " | {$notePart}" : "") . "]";
    $remarkEsc = $krfconn->real_escape_string($remarkLine);
    $userEsc = $krfconn->real_escape_string($loginname);
    $dispEsc = $krfconn->real_escape_string($dispatchType);

    $krfconn->begin_transaction();
    try {
        $updPlantSql = "
            UPDATE krf_polyhouse_plant_details
            SET 
                status = '{$dispEsc}',
                workingstatus = '{$dispEsc}',
                move_at = '{$curDate}',
                movementremark = CONCAT(COALESCE(movementremark, ''), IF(COALESCE(movementremark, '') = '', '', '\n'), '{$remarkEsc}'),
                updated_at = NOW(),
                updated_by = '{$userEsc}'
            WHERE polyhouseplantid IN ({$idListStr})
        ";
        if (!$krfconn->query($updPlantSql)) {
            throw new Exception("Error updating dispatch: " . $krfconn->error);
        }

        // Update ready stocks
        $updRts = "
            UPDATE krf_readytosale_stocks
            SET 
                status = '{$dispEsc}',
                remarks = CONCAT(COALESCE(remarks, ''), IF(COALESCE(remarks, '') = '', '', ' | '), '{$remarkEsc}'),
                updated_at = NOW()
            WHERE polyhouseplantid IN ({$idListStr})
        ";
        $krfconn->query($updRts);

        $krfconn->commit();

        outJson([
            'status' => 'success',
            'message' => count($cleanIds) . " tray(s) successfully resolved to '{$dispatchType}'!",
            'count' => count($cleanIds)
        ]);
    } catch (Exception $e) {
        $krfconn->rollback();
        outJson(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// =========================================================================
// ACTION 8: RESTORE RE-VERIFY TRAYS -> Back to Active (Polyhouse / Readytosale)
// =========================================================================
if ($action === 'resolve_restore') {
    $plantIds     = $_POST['plant_ids'] ?? [];
    $targetStatus = trim($_POST['target_status'] ?? 'Polyhouse');
    $remark       = trim($_POST['remark'] ?? '');

    if (!in_array($targetStatus, ['Polyhouse', 'Readytosale'])) {
        $targetStatus = 'Polyhouse';
    }

    if (!is_array($plantIds) || empty($plantIds)) {
        outJson(['status' => 'error', 'message' => 'No trays selected to restore.']);
    }

    $cleanIds = array_filter(array_map('intval', $plantIds));
    if (empty($cleanIds)) {
        outJson(['status' => 'error', 'message' => 'Invalid tray IDs provided.']);
    }
    $idListStr = implode(',', $cleanIds);

    $nowDt = date('d-M-Y h:i A');
    $curDate = date('Y-m-d');
    $presentRemark = "[Physical Re-verification OK by {$loginname} on {$nowDt}" . (!empty($remark) ? " | Note: {$remark}" : "") . "]";
    $presRemarkEsc = $krfconn->real_escape_string($presentRemark);
    $userEsc = $krfconn->real_escape_string($loginname);

    $krfconn->begin_transaction();
    try {
        // Fetch each tray to determine its previous status from movementremark or readytosale_stocks
        $fPresSql = "
            SELECT p.polyhouseplantid, p.movementremark, rts.id as rts_id 
            FROM krf_polyhouse_plant_details p
            LEFT JOIN krf_readytosale_stocks rts ON rts.polyhouseplantid = p.polyhouseplantid
            WHERE p.polyhouseplantid IN ({$idListStr})
        ";
        $fPresRes = $krfconn->query($fPresSql);
        $restoreGroups = [
            'Polyhouse' => [],
            'Readytosale' => [],
            'OpenStock' => []
        ];

        if ($fPresRes) {
            while ($pr = $fPresRes->fetch_assoc()) {
                $pid = (int)$pr['polyhouseplantid'];
                $prevSt = '';
                if (preg_match('/\[PREV_STATUS:(.+?)\]/', $pr['movementremark'] ?? '', $m)) {
                    $c = trim($m[1]);
                    if (in_array($c, ['Ready to sale', 'ReadyToSale', 'Readytosale'])) {
                        $prevSt = 'Readytosale';
                    } elseif ($c === 'OpenStock') {
                        $prevSt = 'OpenStock';
                    } elseif ($c === 'Polyhouse') {
                        $prevSt = 'Polyhouse';
                    }
                }
                if (empty($prevSt)) {
                    $prevSt = !empty($pr['rts_id']) ? 'Readytosale' : 'Polyhouse';
                }
                if (!isset($restoreGroups[$prevSt])) {
                    $restoreGroups[$prevSt] = [];
                }
                $restoreGroups[$prevSt][] = $pid;
            }
        }

        foreach ($restoreGroups as $st => $pids) {
            if (empty($pids)) continue;
            $pStr = implode(',', $pids);
            $stEsc = $krfconn->real_escape_string($st);
            $updPlantSql = "
                UPDATE krf_polyhouse_plant_details
                SET 
                    status = '{$stEsc}',
                    workingstatus = '{$stEsc}',
                    movementremark = CONCAT(COALESCE(movementremark, ''), IF(COALESCE(movementremark, '') = '', '', '\n'), '{$presRemarkEsc} (Restored to {$st})'),
                    updated_at = NOW(),
                    updated_by = '{$userEsc}'
                WHERE polyhouseplantid IN ({$pStr})
            ";
            if (!$krfconn->query($updPlantSql)) {
                throw new Exception("Error restoring trays: " . $krfconn->error);
            }

            if ($st === 'Readytosale') {
                $updRts = "
                    UPDATE krf_readytosale_stocks
                    SET 
                        status = 'Readytosale',
                        remarks = CONCAT(COALESCE(remarks, ''), IF(COALESCE(remarks, '') = '', '', ' | '), '{$presRemarkEsc}'),
                        updated_at = NOW()
                    WHERE polyhouseplantid IN ({$pStr})
                ";
                $krfconn->query($updRts);
            }
        }

        $krfconn->commit();

        outJson([
            'status' => 'success',
            'message' => count($cleanIds) . " tray(s) successfully verified and restored to previous status!",
            'count' => count($cleanIds)
        ]);
    } catch (Exception $e) {
        $krfconn->rollback();
        outJson(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// =========================================================================
// ACTION 9: RECONCILE PHYSICAL AUDIT (Present Trays vs. Missing Trays)
// =========================================================================
if ($action === 'reconcile_physical_audit') {
    $presentIds        = $_POST['present_ids'] ?? [];
    $missingIds        = $_POST['missing_ids'] ?? [];
    $missingResolution = trim($_POST['missing_resolution'] ?? 'Empty After Shifting');
    $orderNo           = trim($_POST['order_no'] ?? '');
    $polyhouseid       = trim($_POST['polyhouseid'] ?? '');
    $bed_no            = trim($_POST['bed_no'] ?? '');
    $remark            = trim($_POST['remark'] ?? '');

    $cleanPresent = is_array($presentIds) ? array_filter(array_map('intval', $presentIds)) : [];
    $cleanMissing = is_array($missingIds) ? array_filter(array_map('intval', $missingIds)) : [];

    if (empty($cleanPresent) && empty($cleanMissing)) {
        outJson(['status' => 'error', 'message' => 'No trays were provided in the audit reconciliation.']);
    }

    if (!in_array($missingResolution, ['Empty After Shifting', 'Move for Grafting', 'Final Dispatch', 'Dispatch'])) {
        $missingResolution = 'Empty After Shifting';
    }
    if ($missingResolution === 'Dispatch') {
        $missingResolution = 'Final Dispatch';
    }

    $nowDt = date('d-M-Y h:i A');
    $curDate = date('Y-m-d');
    $userEsc = $krfconn->real_escape_string($loginname);
    $resEsc = $krfconn->real_escape_string($missingResolution);

    $krfconn->begin_transaction();
    try {
        // 1. Restore Physically Present Trays to PREVIOUS STATUS
        if (!empty($cleanPresent)) {
            $presIdStr = implode(',', $cleanPresent);
            $presentRemark = "[Physical Audit OK: Verified present in Bed-{$bed_no} by {$loginname} on {$nowDt}]";
            $presRemarkEsc = $krfconn->real_escape_string($presentRemark);

            // Fetch previous status of each present tray
            $fPresSql = "
                SELECT p.polyhouseplantid, p.movementremark, rts.id as rts_id 
                FROM krf_polyhouse_plant_details p
                LEFT JOIN krf_readytosale_stocks rts ON rts.polyhouseplantid = p.polyhouseplantid
                WHERE p.polyhouseplantid IN ({$presIdStr})
            ";
            $fPresRes = $krfconn->query($fPresSql);
            $restoreGroups = [
                'Polyhouse' => [],
                'Readytosale' => [],
                'OpenStock' => []
            ];

            if ($fPresRes) {
                while ($pr = $fPresRes->fetch_assoc()) {
                    $pid = (int)$pr['polyhouseplantid'];
                    $prevSt = '';
                    if (preg_match('/\[PREV_STATUS:(.+?)\]/', $pr['movementremark'] ?? '', $m)) {
                        $c = trim($m[1]);
                        if (in_array($c, ['Ready to sale', 'ReadyToSale', 'Readytosale'])) {
                            $prevSt = 'Readytosale';
                        } elseif ($c === 'OpenStock') {
                            $prevSt = 'OpenStock';
                        } elseif ($c === 'Polyhouse') {
                            $prevSt = 'Polyhouse';
                        }
                    }
                    if (empty($prevSt)) {
                        $prevSt = !empty($pr['rts_id']) ? 'Readytosale' : 'Polyhouse';
                    }
                    if (!isset($restoreGroups[$prevSt])) {
                        $restoreGroups[$prevSt] = [];
                    }
                    $restoreGroups[$prevSt][] = $pid;
                }
            }

            foreach ($restoreGroups as $st => $pids) {
                if (empty($pids)) continue;
                $pStr = implode(',', $pids);
                $stEsc = $krfconn->real_escape_string($st);
                $updPresSql = "
                    UPDATE krf_polyhouse_plant_details
                    SET 
                        status = '{$stEsc}',
                        workingstatus = '{$stEsc}',
                        movementremark = CONCAT(COALESCE(movementremark, ''), IF(COALESCE(movementremark, '') = '', '', '\n'), '{$presRemarkEsc} (Restored to {$st})'),
                        updated_at = NOW(),
                        updated_by = '{$userEsc}'
                    WHERE polyhouseplantid IN ({$pStr})
                ";
                if (!$krfconn->query($updPresSql)) {
                    throw new Exception("Error restoring verified present trays: " . $krfconn->error);
                }

                if ($st === 'Readytosale') {
                    $updPresRts = "
                        UPDATE krf_readytosale_stocks
                        SET 
                            status = 'Readytosale',
                            remarks = CONCAT(COALESCE(remarks, ''), IF(COALESCE(remarks, '') = '', '', ' | '), '{$presRemarkEsc}'),
                            updated_at = NOW()
                        WHERE polyhouseplantid IN ({$pStr})
                    ";
                    $krfconn->query($updPresRts);
                }
            }
        }

        // 2. Resolve Missing Trays (Unscanned): Can be split across resolutions or single resolution
        $missingGroupsRaw = $_POST['missing_groups'] ?? null;
        $missingGroups    = null;
        if (!empty($missingGroupsRaw)) {
            if (is_string($missingGroupsRaw)) {
                $missingGroups = json_decode($missingGroupsRaw, true);
            } elseif (is_array($missingGroupsRaw)) {
                $missingGroups = $missingGroupsRaw;
            }
        }

        $allowedResolutions = [
            'Empty After Shifting' => 'Empty After Shifting',
            'Move for Grafting'    => 'Move for Grafting',
            'Final Dispatch'       => 'Final Dispatch',
            'Dispatch'             => 'Final Dispatch'
        ];

        $reconcileSummary = [];

        if (is_array($missingGroups) && !empty($missingGroups)) {
            // MULTI-ACTION / SPLIT RESOLUTION MODE
            foreach ($missingGroups as $rawRes => $grpIds) {
                $stdRes = $allowedResolutions[$rawRes] ?? null;
                if (!$stdRes) continue;

                $cleanGrpIds = is_array($grpIds) ? array_filter(array_map('intval', $grpIds)) : [];
                if (empty($cleanGrpIds)) continue;

                $grpIdStr = implode(',', $cleanGrpIds);
                $reasonPart = !empty($remark) ? " | Reason: {$remark}" : "";
                $missingRemark = "[Physical Audit Discrepancy: Missing from Bed-{$bed_no}, resolved as '{$stdRes}' by {$loginname} on {$nowDt}{$reasonPart}]";
                $missRemarkEsc = $krfconn->real_escape_string($missingRemark);
                $resEsc = $krfconn->real_escape_string($stdRes);

                // Update krf_polyhouse_plant_details
                $updMissSql = "
                    UPDATE krf_polyhouse_plant_details
                    SET 
                        status = '{$resEsc}',
                        workingstatus = '{$resEsc}',
                        move_at = '{$curDate}',
                        movementremark = CONCAT(COALESCE(movementremark, ''), IF(COALESCE(movementremark, '') = '', '', '\n'), '{$missRemarkEsc}'),
                        updated_at = NOW(),
                        updated_by = '{$userEsc}'
                    WHERE polyhouseplantid IN ({$grpIdStr})
                ";
                if (!$krfconn->query($updMissSql)) {
                    throw new Exception("Error resolving missing trays as {$stdRes}: " . $krfconn->error);
                }

                // Update krf_readytosale_stocks
                $updMissRts = "
                    UPDATE krf_readytosale_stocks
                    SET 
                        status = '{$resEsc}',
                        remarks = CONCAT(COALESCE(remarks, ''), IF(COALESCE(remarks, '') = '', '', ' | '), '{$missRemarkEsc}'),
                        updated_at = NOW()
                    WHERE polyhouseplantid IN ({$grpIdStr})
                ";
                $krfconn->query($updMissRts);

                $reconcileSummary[] = count($cleanGrpIds) . " as '{$stdRes}'";
            }
        } else {
            // SINGLE ACTION FOR ALL MISSING TRAYS
            if (!empty($cleanMissing)) {
                if (!in_array($missingResolution, ['Empty After Shifting', 'Move for Grafting', 'Final Dispatch', 'Dispatch'])) {
                    $missingResolution = 'Empty After Shifting';
                }
                if ($missingResolution === 'Dispatch') {
                    $missingResolution = 'Final Dispatch';
                }
                $missIdStr = implode(',', $cleanMissing);
                $reasonPart = !empty($remark) ? " | Reason: {$remark}" : "";
                $missingRemark = "[Physical Audit Discrepancy: Missing from Bed-{$bed_no}, resolved as '{$missingResolution}' by {$loginname} on {$nowDt}{$reasonPart}]";
                $missRemarkEsc = $krfconn->real_escape_string($missingRemark);
                $resEsc = $krfconn->real_escape_string($missingResolution);

                $updMissSql = "
                    UPDATE krf_polyhouse_plant_details
                    SET 
                        status = '{$resEsc}',
                        workingstatus = '{$resEsc}',
                        move_at = '{$curDate}',
                        movementremark = CONCAT(COALESCE(movementremark, ''), IF(COALESCE(movementremark, '') = '', '', '\n'), '{$missRemarkEsc}'),
                        updated_at = NOW(),
                        updated_by = '{$userEsc}'
                    WHERE polyhouseplantid IN ({$missIdStr})
                ";
                if (!$krfconn->query($updMissSql)) {
                    throw new Exception("Error resolving missing trays as {$missingResolution}: " . $krfconn->error);
                }

                $updMissRts = "
                    UPDATE krf_readytosale_stocks
                    SET 
                        status = '{$resEsc}',
                        remarks = CONCAT(COALESCE(remarks, ''), IF(COALESCE(remarks, '') = '', '', ' | '), '{$missRemarkEsc}'),
                        updated_at = NOW()
                    WHERE polyhouseplantid IN ({$missIdStr})
                ";
                $krfconn->query($updMissRts);

                $reconcileSummary[] = count($cleanMissing) . " as '{$missingResolution}'";
            }
        }

        $krfconn->commit();

        $summaryText = !empty($reconcileSummary) 
            ? implode(', ', $reconcileSummary) 
            : (!empty($cleanMissing) ? (count($cleanMissing) . " as '{$missingResolution}'") : '0 missing');

        outJson([
            'status'        => 'success',
            'message'       => 'Physical audit reconciled! ' . count($cleanPresent) . ' tray(s) verified present, missing trays resolved: ' . $summaryText . '.',
            'present_count' => count($cleanPresent),
            'missing_count' => count($cleanMissing)
        ]);
    } catch (Exception $e) {
        $krfconn->rollback();
        outJson(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

outJson(['status' => 'error', 'message' => 'Unknown action.']);
