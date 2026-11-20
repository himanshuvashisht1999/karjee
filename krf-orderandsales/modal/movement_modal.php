<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(0);
ini_set('display_errors', '0');
date_default_timezone_set('Asia/Kolkata');

require_once(__DIR__ . '/../../connection.php');
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

/**
 * Helper to fetch and merge all beds configured in polyhouse master + distinct beds from plant records
 */
function getBedsForPolyhouse($krfconn, $projectid, $polyhouseid) {
    $beds = [];
    if (!empty($polyhouseid)) {
        // 1. Check krf_polyhouse_master beddetails
        $phstmt = $krfconn->prepare("SELECT beddetails FROM krf_polyhouse_master WHERE (projectid = ? OR ? = 0 OR projectid = 17) AND (polyhouseid = ? OR polyhousename = ?) LIMIT 1");
        if ($phstmt) {
            $phstmt->bind_param('iiss', $projectid, $projectid, $polyhouseid, $polyhouseid);
            $phstmt->execute();
            $phRes = $phstmt->get_result()->fetch_assoc();
            if ($phRes && !empty($phRes['beddetails'])) {
                $decoded = is_array($phRes['beddetails']) ? $phRes['beddetails'] : json_decode($phRes['beddetails'], true);
                if (is_array($decoded)) {
                    foreach ($decoded as $b) {
                        $bName = trim($b['bedname'] ?? $b['name'] ?? $b['bed_no'] ?? '');
                        if (!empty($bName) && !in_array($bName, $beds)) {
                            $beds[] = $bName;
                        }
                    }
                }
            }
            $phstmt->close();
        }

        // 2. Also check krf_polyhouse_plant_details for existing bed numbers
        $plstmt = $krfconn->prepare("SELECT DISTINCT bed_no FROM krf_polyhouse_plant_details WHERE (projectid = ? OR ? = 0 OR projectid = 17) AND (polyhouseid = ? OR polyhouseid = '') AND bed_no IS NOT NULL AND TRIM(bed_no) <> ''");
        if ($plstmt) {
            $plstmt->bind_param('iis', $projectid, $projectid, $polyhouseid);
            $plstmt->execute();
            $plRes = $plstmt->get_result();
            while ($plr = $plRes->fetch_assoc()) {
                $bName = trim($plr['bed_no']);
                if (!empty($bName) && !in_array($bName, $beds)) {
                    $beds[] = $bName;
                }
            }
            $plstmt->close();
        }
    }

    if (!empty($beds)) {
        natsort($beds);
    }
    return array_values($beds);
}

/**
 * Helper to fetch all active polyhouses in the project
 */
function getPolyhousesList($krfconn, $projectid) {
    $list = [];
    $res = $krfconn->query("SELECT polyhouseid, polyhousename, locationid FROM krf_polyhouse_master WHERE (projectid = '{$projectid}' OR '{$projectid}' = '0' OR projectid = '17') AND (status = 'Active' OR status IS NULL) ORDER BY polyhouseid ASC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $list[] = [
                'polyhouseid' => $row['polyhouseid'],
                'polyhousename' => $row['polyhousename'] ?: ('PH-' . $row['polyhouseid']),
                'locationid' => $row['locationid']
            ];
        }
    }
    return $list;
}

if (!function_exists('calculateActualPlantInTray')) {
    /**
     * Calculate actual plants in a tray based on formula:
     * actual_plant = qtypertray - failurecount - dispatchqty
     *
     * @param int|array|object $qtyOrTray Capacity / qtypertray OR tray row array / object
     * @param int $failureCount Failure / dead / unhealthy count (used if first param is numeric)
     * @param int $dispatchQty Dispatched plant count (used if first param is numeric)
     * @return int Actual available healthy plants in the tray (minimum 0)
     */
    function calculateActualPlantInTray($qtyOrTray, $failureCount = 0, $dispatchQty = 0) {
        if (!$qtyOrTray && $qtyOrTray !== 0) return 0;
        if (is_array($qtyOrTray)) {
            $qtypertray   = (int)($qtyOrTray['qtypertray'] ?? $qtyOrTray['cavity_count'] ?? 0);
            $failurecount = (int)($qtyOrTray['failurecount'] ?? 0);
            $dispatchqty  = (int)($qtyOrTray['dispatchqty'] ?? 0);
        } elseif (is_object($qtyOrTray)) {
            $qtypertray   = (int)($qtyOrTray->qtypertray ?? $qtyOrTray->cavity_count ?? 0);
            $failurecount = (int)($qtyOrTray->failurecount ?? 0);
            $dispatchqty  = (int)($qtyOrTray->dispatchqty ?? 0);
        } else {
            $qtypertray   = (int)$qtyOrTray;
            $failurecount = (int)$failureCount;
            $dispatchqty  = (int)$dispatchQty;
        }

        $actual = $qtypertray - $failurecount - $dispatchqty;
        return max(0, $actual);
    }
}


// Action to get polyhouses list on demand
if ($action === 'get_polyhouses') {
    outJson(['status' => 'success', 'data' => getPolyhousesList($krfconn, $projectid)]);
}

// Action to get beds for a specific polyhouse on demand
if ($action === 'get_polyhouse_beds') {
    $phId = trim($_REQUEST['polyhouseid'] ?? '');
    outJson(['status' => 'success', 'beds' => getBedsForPolyhouse($krfconn, $projectid, $phId)]);
}

function getOpenSaleLocationsList($krfconn, $projectid) {
    $list = [];
    $res = $krfconn->query("SELECT polyhouseid, polyhousename, locationid, type, structure_type 
                            FROM krf_polyhouse_master 
                            WHERE (projectid = '{$projectid}' OR '{$projectid}' = '0' OR projectid = '17') 
                              AND (type = 'OpenSale' OR structure_type = 'OpenSale' OR polyhousename LIKE '%Open%Sale%') 
                              AND (status = 'Active' OR status IS NULL) 
                            ORDER BY polyhouseid ASC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $rawLoc = !empty($row['polyhousename']) ? $row['polyhousename'] : ('Open Sale ' . $row['polyhouseid']);
            $cleanEnum = 'Karjee';
            if (stripos($rawLoc, 'Mansoon') !== false) {
                $cleanEnum = 'Mansoon';
            } elseif (stripos($rawLoc, 'Sargw') !== false || stripos($rawLoc, 'Sargaw') !== false) {
                $cleanEnum = 'Sargawa';
            } elseif (stripos($rawLoc, 'Karjee') !== false) {
                $cleanEnum = 'Karjee';
            }
            $list[] = [
                'polyhouseid'   => $row['polyhouseid'],
                'polyhousename' => $rawLoc,
                'locationid'    => $row['locationid'],
                'enum_location' => $cleanEnum,
                'type'          => $row['type'],
                'structure_type'=> $row['structure_type']
            ];
        }
    }
    return $list;
}

// Action to get open sale locations on demand
if ($action === 'get_opensale_locations') {
    outJson(['status' => 'success', 'data' => getOpenSaleLocationsList($krfconn, $projectid)]);
}

// -------------------------------------------------------------
// ACTION 1: SCAN QR CODE / LOOKUP BED & PLANT DETAILS
// -------------------------------------------------------------
if ($action === 'scan_qr') {
    $rawQuery = trim($_REQUEST['query'] ?? $_REQUEST['barcode'] ?? $_REQUEST['raw_query'] ?? '');
    if (empty($rawQuery)) {
        outJson(['status' => 'error', 'message' => 'Please provide a valid Barcode / QR Code to scan.']);
    }

    // Clean up query if JSON, URL or has control characters
    $cleanedQuery = $rawQuery;
    
    // Check if JSON payload (e.g. from 2D QR scanners)
    $decodedJson = @json_decode($cleanedQuery, true);
    if (is_array($decodedJson)) {
        $cleanedQuery = $decodedJson['barcode'] ?? $decodedJson['bedqrcode'] ?? $decodedJson['stockorder_no'] ?? $decodedJson['psono'] ?? $decodedJson['qr_text'] ?? $decodedJson['code'] ?? $decodedJson['bed_no'] ?? $cleanedQuery;
    }

    // Check if URL (e.g. A4 print slip link or mobile scan link)
    if (strpos($cleanedQuery, 'http') === 0 || strpos($cleanedQuery, '://') !== false) {
        $parsedUrl = parse_url($cleanedQuery);
        if (!empty($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $urlParams);
            $cleanedQuery = $urlParams['barcode'] ?? $urlParams['stockorder_no'] ?? $urlParams['stockorder'] ?? $urlParams['psono'] ?? $urlParams['bedqrcode'] ?? $urlParams['code'] ?? $urlParams['bed_no'] ?? $cleanedQuery;
        }
    }
    
    // Strip non-printable / zero-width / BOM characters and trim
    $cleanedQuery = preg_replace('/[\x00-\x1F\x7F\xC2\xA0]/u', '', $cleanedQuery);
    $cleanedQuery = trim($cleanedQuery);

    // Prepare search query variants for RSO / OS Order ID, Bed QR Code, and Lot No.
    $rawClean = trim($cleanedQuery);
    $cleanNoPrefix = preg_replace('/^(RSO|OS)[-\s]*/i', '', $rawClean);
    $cleanAlphanum = preg_replace('/[^a-zA-Z0-9]/', '', $rawClean);
    $cleanNoPrefixAlphanum = preg_replace('/[^a-zA-Z0-9]/', '', $cleanNoPrefix);

    $candidates = [
        $rawClean,
        $cleanNoPrefix,
        'RSO-' . $cleanNoPrefix,
        'OS-'  . $cleanNoPrefix,
        'RSO'  . $cleanNoPrefix,
        'OS'   . $cleanNoPrefix,
        $cleanAlphanum,
        'RSO-' . $cleanNoPrefixAlphanum
    ];
    $candidates = array_values(array_unique(array_filter($candidates)));

    $found = null;

    // Step 1: Search by RSO / OS Order ID, Bed QR Code, or Lot/Batch in krf_polyhouse_plant_details
    if (!empty($candidates)) {
        $inPlaceholders = implode(',', array_fill(0, count($candidates), '?'));
        $soSql = "
            SELECT 
                p.polyhouseplantid,
                p.barcode,
                p.bedqrcode,
                p.bed_no,
                p.polyhouseid,
                p.locationid,
                p.projectid,
                p.stockorder_no,
                p.psono,
                p.productid,
                p.productname,
                p.variety,
                p.tray_id,
                p.tray_code,
                p.qtypertray,
                p.failurecount,
                p.dispatchqty,
                p.status,
                p.workingstatus,
                p.growth_stage,
                p.health_status,
                p.movementremark,
                p.move_at,
                p.updated_at,
                COALESCE(NULLIF(TRIM(p.lotno),''), NULLIF(TRIM(p.sowinglotno_internal),''), NULLIF(TRIM(p.batchno),''), '') AS seed_lot_no,
                COALESCE(p.sowingdate_internal, p.sowing_date) AS sowing_date,
                p.dispatchdate_internal,
                p.created_at
            FROM krf_polyhouse_plant_details p
            WHERE (p.projectid = ? OR ? = 0 OR p.projectid = 17)
              AND (
                    p.stockorder_no IN ($inPlaceholders)
                 OR p.psono IN ($inPlaceholders)
                 OR p.bedqrcode IN ($inPlaceholders)
                 OR p.sowinglotno_internal IN ($inPlaceholders)
                 OR p.lotno IN ($inPlaceholders)
              )
            ORDER BY 
                (p.status IN ('Readytosale', 'OpenStock')) DESC,
                (p.growth_stage = 'ReadyToSale') DESC,
                (p.status NOT IN ('Final Dispatch', 'Removed', 'Empty After Shifting')) DESC,
                (p.stockorder_no LIKE 'RSO%' OR p.stockorder_no LIKE 'OS%') DESC,
                p.polyhouseplantid DESC
            LIMIT 1
        ";
        $soStmt = $krfconn->prepare($soSql);
        if ($soStmt) {
            $types = 'ii' . str_repeat('s', count($candidates) * 5);
            $params = array_merge([$projectid, $projectid], $candidates, $candidates, $candidates, $candidates, $candidates);
            $soStmt->bind_param($types, ...$params);
            $soStmt->execute();
            $found = $soStmt->get_result()->fetch_assoc();
            $soStmt->close();
        }
    }

    // Step 2: Fallback check against krf_production_stockorder master
    if (!$found && !empty($candidates)) {
        $inPlaceholders = implode(',', array_fill(0, count($candidates), '?'));
        $soMasterSql = "
            SELECT 
                p.polyhouseplantid,
                p.barcode,
                p.bedqrcode,
                p.bed_no,
                p.polyhouseid,
                p.locationid,
                p.projectid,
                p.stockorder_no,
                p.psono,
                p.productid,
                p.productname,
                p.variety,
                p.tray_id,
                p.tray_code,
                p.qtypertray,
                p.failurecount,
                p.dispatchqty,
                p.status,
                p.workingstatus,
                p.growth_stage,
                p.health_status,
                p.movementremark,
                p.move_at,
                p.updated_at,
                COALESCE(NULLIF(TRIM(p.lotno),''), NULLIF(TRIM(p.sowinglotno_internal),''), NULLIF(TRIM(p.batchno),''), '') AS seed_lot_no,
                COALESCE(p.sowingdate_internal, p.sowing_date) AS sowing_date,
                p.dispatchdate_internal,
                p.created_at
            FROM krf_production_stockorder so
            INNER JOIN krf_polyhouse_plant_details p ON (
                p.stockorder_no = so.stockorder_no 
             OR p.psono = so.psono 
             OR p.stockorder_no = CONCAT('RSO-', so.stockorder_no)
             OR p.bedqrcode = so.stockorder_no
            )
            WHERE (so.stockorder_no IN ($inPlaceholders) OR so.psono IN ($inPlaceholders))
            ORDER BY 
                (p.status IN ('Readytosale', 'OpenStock')) DESC,
                (p.growth_stage = 'ReadyToSale') DESC,
                (p.status NOT IN ('Final Dispatch', 'Removed', 'Empty After Shifting')) DESC,
                p.polyhouseplantid DESC
            LIMIT 1
        ";
        $mStmt = $krfconn->prepare($soMasterSql);
        if ($mStmt) {
            $types = str_repeat('s', count($candidates) * 2);
            $params = array_merge($candidates, $candidates);
            $mStmt->bind_param($types, ...$params);
            $mStmt->execute();
            $found = $mStmt->get_result()->fetch_assoc();
            $mStmt->close();
        }
    }

    // Step 3: Check if query is a physical individual tray barcode
    if (!$found) {
        $barcodeSql = "
            SELECT 
                p.polyhouseplantid,
                p.barcode,
                p.bedqrcode,
                p.bed_no,
                p.polyhouseid,
                p.locationid,
                p.projectid,
                p.stockorder_no,
                p.psono,
                p.productid,
                p.productname,
                p.variety,
                p.tray_id,
                p.tray_code,
                p.qtypertray,
                p.failurecount,
                p.dispatchqty,
                p.status,
                p.workingstatus,
                p.growth_stage,
                p.health_status,
                p.movementremark,
                p.move_at,
                p.updated_at,
                COALESCE(NULLIF(TRIM(p.lotno),''), NULLIF(TRIM(p.sowinglotno_internal),''), NULLIF(TRIM(p.batchno),''), '') AS seed_lot_no,
                COALESCE(p.sowingdate_internal, p.sowing_date) AS sowing_date,
                p.dispatchdate_internal,
                p.created_at
            FROM krf_polyhouse_plant_details p
            WHERE (p.projectid = ? OR ? = 0 OR p.projectid = 17)
              AND p.barcode = ?
            ORDER BY (p.status IN ('Readytosale', 'OpenStock')) DESC, (p.growth_stage = 'ReadyToSale') DESC, p.polyhouseplantid DESC
            LIMIT 1
        ";
        $bstmt = $krfconn->prepare($barcodeSql);
        if ($bstmt) {
            $bstmt->bind_param('iis', $projectid, $projectid, $cleanedQuery);
            $bstmt->execute();
            $found = $bstmt->get_result()->fetch_assoc();
            $bstmt->close();
        }
    }

    if (!$found) {
        outJson([
            'status' => 'error',
            'message' => 'No active plant records found matching Order QR / Barcode: "' . htmlspecialchars($rawQuery) . '". Please scan a valid RSO / OS Order QR code (e.g. ' . htmlspecialchars($cleanedQuery) . ').'
        ]);
    }

    $currStatus = trim($found['status'] ?? '');
    $isAllowedStatus = in_array($currStatus, ['Readytosale', 'OpenStock']);

    $targetBedNo    = trim($found['bed_no'] ?? '');
    $targetLocId    = trim($found['locationid'] ?? '');
    $targetPhId     = trim($found['polyhouseid'] ?? '');
    $targetSoNo     = trim($found['stockorder_no'] ?? '');
    $targetPsoNo    = trim($found['psono'] ?? '');
    $targetProdId   = trim($found['productid'] ?? '');
    $targetTrayId   = (int)($found['tray_id'] ?? 0);

    // 2. Fetch full status breakdown and active trays count in this bed & stock order/product
    $statusBreakdown = [];
    $activeTrays = 0;
    $availReadyTrays = 0;
    $availOpenTrays = 0;
    $polyhouseGrowthTrays = 0;

    $cntSql = "
        SELECT 
            COALESCE(NULLIF(TRIM(status), ''), 'Polyhouse') AS status_group,
            COUNT(*) as tray_count
        FROM krf_polyhouse_plant_details
        WHERE (projectid = ? OR ? = 0 OR projectid = 17)
          AND bed_no = ?
          AND (locationid = ? OR ? = '')
          AND (polyhouseid = ? OR ? = '')
          AND (stockorder_no = ? OR ? = '')
          AND (productid = ? OR ? = '')
        GROUP BY status_group
    ";
    $cstmt = $krfconn->prepare($cntSql);
    if ($cstmt) {
        $cstmt->bind_param('iisssssssss', 
            $projectid, $projectid,
            $targetBedNo, 
            $targetLocId, $targetLocId, 
            $targetPhId, $targetPhId, 
            $targetSoNo, $targetSoNo, 
            $targetProdId, $targetProdId
        );
        $cstmt->execute();
        $cntRes = $cstmt->get_result();
        while ($cr = $cntRes->fetch_assoc()) {
            $st = trim($cr['status_group']);
            $tc = (int)($cr['tray_count'] ?? 0);
            $statusBreakdown[$st] = $tc;

            $stLower = strtolower($st);
            if ($stLower === 'readytosale' || $stLower === 'ready to sale') {
                $availReadyTrays += $tc;
                $activeTrays += $tc;
            } elseif ($stLower === 'openstock' || $stLower === 'open stock') {
                $availOpenTrays += $tc;
                $activeTrays += $tc;
            } elseif ($stLower === 'polyhouse') {
                $polyhouseGrowthTrays += $tc;
                $activeTrays += $tc;
            }
        }
        $cstmt->close();
    }

    $occupiedTrays = $activeTrays;
    $availCount = $activeTrays;

    // If active trays exist in this bed/order, but the scanned tray row has a non-active / completed status,
    // load an active tray row so all product details, cavity, and status represent active plants
    if ($activeTrays > 0 && !in_array(strtolower($currStatus), ['polyhouse', 'readytosale', 'openstock'])) {
        $actStmt = $krfconn->prepare("
            SELECT p.*,
                   COALESCE(NULLIF(TRIM(p.lotno),''), NULLIF(TRIM(p.sowinglotno_internal),''), NULLIF(TRIM(p.batchno),''), '') AS seed_lot_no,
                   COALESCE(p.sowingdate_internal, p.sowing_date) AS sowing_date,
                   p.dispatchdate_internal
            FROM krf_polyhouse_plant_details p
            WHERE (p.projectid = ? OR ? = 0 OR p.projectid = 17)
              AND p.bed_no = ?
              AND (p.stockorder_no = ? OR ? = '')
              AND (p.productid = ? OR ? = '')
              AND (LOWER(p.status) IN ('readytosale', 'openstock', 'polyhouse') OR p.status = '' OR p.status IS NULL)
            ORDER BY (LOWER(p.status) IN ('readytosale', 'openstock')) DESC, p.polyhouseplantid DESC
            LIMIT 1
        ");
        if ($actStmt) {
            $actStmt->bind_param('iisssss', $projectid, $projectid, $targetBedNo, $targetSoNo, $targetSoNo, $targetProdId, $targetProdId);
            $actStmt->execute();
            $actRow = $actStmt->get_result()->fetch_assoc();
            $actStmt->close();
            if ($actRow) {
                $found = $actRow;
                $currStatus = trim($found['status'] ?? 'Polyhouse');
            }
        }
    }

    // 3. Fetch Location Name
    $locationName = $targetLocId ?: 'N/A';
    if (!empty($targetLocId) && isset($hrmconn)) {
        $lstmt = $hrmconn->prepare("SELECT locationname FROM hrm_AttendanceLocationMaster WHERE projectid = ? AND locationid = ? LIMIT 1");
        if ($lstmt) {
            $pidStr = (string)$projectid;
            $lstmt->bind_param('ss', $pidStr, $targetLocId);
            $lstmt->execute();
            $lr = $lstmt->get_result()->fetch_assoc();
            if ($lr && !empty($lr['locationname'])) $locationName = $lr['locationname'];
            $lstmt->close();
        }
    }

    // 4. Fetch Polyhouse Name
    $polyhouseName = $targetPhId ?: 'N/A';
    if (!empty($targetPhId)) {
        $phstmt = $krfconn->prepare("SELECT polyhousename FROM krf_polyhouse_master WHERE projectid = ? AND (polyhouseid = ? OR polyhousename = ?) LIMIT 1");
        if ($phstmt) {
            $phstmt->bind_param('iss', $projectid, $targetPhId, $targetPhId);
            $phstmt->execute();
            $pr = $phstmt->get_result()->fetch_assoc();
            if ($pr && !empty($pr['polyhousename'])) $polyhouseName = $pr['polyhousename'];
            $phstmt->close();
        }
    }

    // 5. Calculate Actual Plant in Tray using Formula: qtypertray - failurecount - dispatchqty
    $trayName = $found['tray_code'] ?: 'Standard Tray';
    $actualPlantCount = calculateActualPlantInTray($found);
    $qtyPerTrayVal = (int)($found['qtypertray'] ?: 126);

    // Actual individual tray status from krf_polyhouse_plant_details
    $trayStatus = $currStatus ?: 'Polyhouse';
    $isReadyToSale = (strcasecmp($trayStatus, 'Readytosale') === 0 || strcasecmp($found['growth_stage'] ?? '', 'ReadyToSale') === 0);

    outJson([
        'status' => 'success',
        'data' => [
            'polyhouseplantid'     => $found['polyhouseplantid'],
            'barcode'              => $found['barcode'] ?: $cleanedQuery,
            'bedqrcode'            => $found['bedqrcode'] ?: ($cleanedQuery ?: ''),
            'bed_no'               => $targetBedNo ?: 'Bed-N/A',
            'locationid'           => $targetLocId,
            'locationname'         => $locationName,
            'polyhouseid'          => $targetPhId,
            'polyhousename'        => $polyhouseName,
            'stockorder_no'        => $targetSoNo ?: 'N/A',
            'psono'                => $targetPsoNo ?: 'N/A',
            'productid'            => $targetProdId,
            'productname'          => $found['productname'] ?: 'Plant Product',
            'variety'              => $found['variety'] ?: '-',
            'seed_lot_no'          => $found['seed_lot_no'] ?: '-',
            'tray_id'              => $targetTrayId,
            'tray_name'            => $trayName,
            'qtypertray'           => $qtyPerTrayVal,
            'failurecount'         => (int)($found['failurecount'] ?? 0),
            'dispatchqty'          => (int)($found['dispatchqty'] ?? 0),
            'actual_plant_count'   => $actualPlantCount,
            'cavity_count'         => $actualPlantCount,
            'status'               => $trayStatus,
            'raw_status'           => $trayStatus,
            'is_ready_to_sale'     => $isReadyToSale,
            'growth_stage'         => $found['growth_stage'] ?: '-',
            'health_status'        => $found['health_status'] ?: 'Healthy',
            'sowing_date'          => !empty($found['sowing_date']) ? date('d-M-Y', strtotime($found['sowing_date'])) : 'N/A',
            'dispatch_date'        => !empty($found['dispatchdate_internal']) ? date('d-M-Y', strtotime($found['dispatchdate_internal'])) : 'N/A',
            'occupied_trays'       => $occupiedTrays,
            'ready_to_sale_trays'  => $availReadyTrays,
            'polyhouse_trays'      => $polyhouseGrowthTrays,
            'available_trays'      => $availCount,
            'available_ready_trays'=> $availReadyTrays,
            'available_open_trays' => $availOpenTrays,
            'available_plants'     => ($occupiedTrays * $actualPlantCount),
            'has_active_trays'     => ($activeTrays > 0),
            'active_trays'         => $activeTrays,
            'status_breakdown'     => $statusBreakdown,
            'total_trays_in_bed'   => array_sum($statusBreakdown),
            'movementremark'       => $found['movementremark'] ?: '',
            'move_at'              => $found['move_at'] ?: '',
            'updated_at'           => $found['updated_at'] ?: '',
            'polyhouse_beds'       => getBedsForPolyhouse($krfconn, $projectid, $targetPhId),
            'all_polyhouses'       => getPolyhousesList($krfconn, $projectid)
        ]
    ]);
}

// -------------------------------------------------------------
// ACTION 1.5: MARK TRAYS AS READY TO SALE
// -------------------------------------------------------------
if ($action === 'mark_ready_to_sale') {
    $mode          = trim($_POST['mode'] ?? '');
    if ($mode === 'scanned_only') {
        outJson(['status' => 'error', 'message' => 'Trays can only be marked as Ready to Sale when "Move All Trays in Bed" option is selected.']);
    }

    $bed_no        = trim($_POST['bed_no'] ?? '');
    $locationid    = trim($_POST['locationid'] ?? '');
    $polyhouseid   = trim($_POST['polyhouseid'] ?? '');
    $stockorder_no = trim($_POST['stockorder_no'] ?? '');
    $psono         = trim($_POST['psono'] ?? '');
    $productid     = trim($_POST['productid'] ?? '');
    $trays_to_mark = (int)($_POST['trays_to_mark'] ?? 0);

    $selected_barcodes = $_POST['selected_barcodes'] ?? [];
    if (is_string($selected_barcodes)) {
        $decoded = json_decode($selected_barcodes, true);
        $selected_barcodes = is_array($decoded) ? $decoded : array_filter(array_map('trim', explode(',', $selected_barcodes)));
    }
    $selected_plant_ids = $_POST['selected_plant_ids'] ?? [];
    if (is_string($selected_plant_ids)) {
        $decoded = json_decode($selected_plant_ids, true);
        $selected_plant_ids = is_array($decoded) ? $decoded : array_filter(array_map('intval', explode(',', $selected_plant_ids)));
    }

    $allCandidates = [];

    if (!empty($selected_plant_ids)) {
        $idList = implode(',', array_map('intval', $selected_plant_ids));
        $selectSql = "
            SELECT polyhouseplantid, barcode, bedqrcode, bed_no, polyhouseid, locationid, projectid, 
                   stockorder_no, psono, productid, productname, variety, tray_id, tray_code, qtypertray, 
                   sowingdate_internal, sowing_date, dispatchdate_internal, lotno, batchno, sowinglotno_internal,
                   status, growth_stage
            FROM krf_polyhouse_plant_details
            WHERE polyhouseplantid IN ({$idList})
              AND status NOT IN ('Final Dispatch', 'Internal Dispatch', 'Removed', 'Empty After Shifting')
            ORDER BY polyhouseplantid ASC
        ";
        $stmtRes = $krfconn->query($selectSql);
        if ($stmtRes) {
            while ($r = $stmtRes->fetch_assoc()) {
                $allCandidates[] = $r;
            }
        }
    } elseif (!empty($selected_barcodes)) {
        $escapedCodes = array_map(function($c) use ($krfconn) { return "'" . $krfconn->real_escape_string($c) . "'"; }, $selected_barcodes);
        $codeList = implode(',', $escapedCodes);
        $selectSql = "
            SELECT polyhouseplantid, barcode, bedqrcode, bed_no, polyhouseid, locationid, projectid, 
                   stockorder_no, psono, productid, productname, variety, tray_id, tray_code, qtypertray, 
                   sowingdate_internal, sowing_date, dispatchdate_internal, lotno, batchno, sowinglotno_internal,
                   status, growth_stage
            FROM krf_polyhouse_plant_details
            WHERE barcode IN ({$codeList})
              AND status NOT IN ('Final Dispatch', 'Internal Dispatch', 'Removed', 'Empty After Shifting')
            ORDER BY polyhouseplantid ASC
        ";
        $stmtRes = $krfconn->query($selectSql);
        if ($stmtRes) {
            while ($r = $stmtRes->fetch_assoc()) {
                $allCandidates[] = $r;
            }
        }
    } else {
        if (empty($bed_no) || empty($stockorder_no)) {
            outJson(['status' => 'error', 'message' => 'Missing Bed No or Stock Order No.']);
        }
        if ($trays_to_mark <= 0) {
            outJson(['status' => 'error', 'message' => 'Please enter at least 1 tray to mark as Ready to Sale.']);
        }

        $cleanBed = preg_replace('/^Bed[-\s]*/i', '', $bed_no);
        $bedWithPrefix = 'Bed-' . $cleanBed;
        $cleanSo = preg_replace('/^(RSO|OS|PSO)-/i', '', $stockorder_no);
        $soWithRso = 'RSO-' . $cleanSo;
        $soWithOs = 'OS-' . $cleanSo;

        // Find candidate records in this bed
        $selectSql = "
            SELECT polyhouseplantid, barcode, bedqrcode, bed_no, polyhouseid, locationid, projectid, 
                   stockorder_no, psono, productid, productname, variety, tray_id, tray_code, qtypertray, 
                   sowingdate_internal, sowing_date, dispatchdate_internal, lotno, batchno, sowinglotno_internal,
                   status, growth_stage
            FROM krf_polyhouse_plant_details
            WHERE (projectid = ? OR ? = 0 OR projectid = 17)
              AND (bed_no = ? OR bed_no = ? OR bed_no = ?)
              AND (stockorder_no = ? OR stockorder_no = ? OR stockorder_no = ? OR psono = ?)
              AND (productid = ? OR ? = '')
              AND status NOT IN ('Final Dispatch', 'Internal Dispatch', 'Removed', 'Empty After Shifting')
            ORDER BY polyhouseplantid ASC
        ";
        $stmt = $krfconn->prepare($selectSql);
        if (!$stmt) {
            outJson(['status' => 'error', 'message' => 'Query prepare failed: ' . $krfconn->error]);
        }
        $stmt->bind_param('iisssssssss', 
            $projectid, $projectid,
            $bed_no, $cleanBed, $bedWithPrefix,
            $stockorder_no, $soWithRso, $soWithOs, $psono,
            $productid, $productid
        );
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $allCandidates[] = $r;
        }
        $stmt->close();
    }

    if (empty($allCandidates)) {
        outJson(['status' => 'error', 'message' => "No plant records found for the selected tray(s) / bed."]);
    }

    // Separate candidate records by status
    $alreadyReady = [];
    $polyhouseRecords = [];
    $otherStatusRecords = [];

    foreach ($allCandidates as $r) {
        $st = strtolower(trim((string)$r['status']));
        $gs = strtolower(trim((string)$r['growth_stage']));
        if ($st === 'readytosale' || $st === 'ready to sale' || $gs === 'readytosale') {
            $alreadyReady[] = $r;
        } elseif ($st === 'polyhouse') {
            $polyhouseRecords[] = $r;
        } else {
            $otherStatusRecords[] = $r;
        }
    }

    // Check conditions
    if (empty($polyhouseRecords)) {
        // No trays in 'Polyhouse' status
        if (!empty($alreadyReady) && empty($otherStatusRecords)) {
            // All candidate trays are already marked Ready to Sale
            outJson([
                'status' => 'info',
                'message' => 'Trays already marked as Ready to Sale.'
            ]);
        } elseif (!empty($alreadyReady) && !empty($otherStatusRecords)) {
            $otherNames = array_unique(array_filter(array_map(function($r) { return trim($r['status'] ?: 'Unknown'); }, $otherStatusRecords)));
            outJson([
                'status' => 'error',
                'message' => "Trays already marked as Ready to Sale or not in Polyhouse status. Only trays with status 'Polyhouse' can be marked as Ready to Sale. Current status: " . implode(', ', $otherNames) . "."
            ]);
        } else {
            $otherNames = array_unique(array_filter(array_map(function($r) { return trim($r['status'] ?: 'Unknown'); }, $otherStatusRecords)));
            outJson([
                'status' => 'error',
                'message' => "Trays can only be marked as Ready to Sale if status is 'Polyhouse'. Current status: " . implode(', ', $otherNames) . "."
            ]);
        }
    }

    // Slice to trays_to_mark if specified in bed mode
    if ($trays_to_mark > 0 && $trays_to_mark < count($polyhouseRecords)) {
        $recordsToMark = array_slice($polyhouseRecords, 0, $trays_to_mark);
    } else {
        $recordsToMark = $polyhouseRecords;
    }

    $plantIds = [];
    $recordsToInsertRts = [];
    foreach ($recordsToMark as $r) {
        $plantIds[] = (int)$r['polyhouseplantid'];
        $recordsToInsertRts[] = $r;
    }

    if (empty($plantIds)) {
        outJson(['status' => 'error', 'message' => "No valid trays in Polyhouse status found to mark as Ready to Sale."]);
    }

    $krfconn->begin_transaction();
    try {
        $idList = implode(',', $plantIds);
        // 1. Update status and growth_stage in krf_polyhouse_plant_details
        $updSql = "
            UPDATE krf_polyhouse_plant_details
            SET status = 'Readytosale',
                growth_stage = 'ReadyToSale',
                movementremark = CONCAT(COALESCE(movementremark, ''), ' | Marked Ready to Sale on ', NOW(), ' by ', '{$loginname}'),
                updated_at = NOW()
            WHERE polyhouseplantid IN ({$idList})
        ";
        if (!$krfconn->query($updSql)) {
            throw new Exception("Failed to update plant status: " . $krfconn->error);
        }

        // 2. Fetch location name
        $locName = $locationid ?: 'N/A';
        if (!empty($locationid) && isset($hrmconn)) {
            $lstmt = $hrmconn->prepare("SELECT locationname FROM hrm_AttendanceLocationMaster WHERE projectid = ? AND locationid = ? LIMIT 1");
            if ($lstmt) {
                $pidStr = (string)$projectid;
                $lstmt->bind_param('ss', $pidStr, $locationid);
                $lstmt->execute();
                $lr = $lstmt->get_result()->fetch_assoc();
                if ($lr && !empty($lr['locationname'])) $locName = $lr['locationname'];
                $lstmt->close();
            }
        }

        // 3. Insert into krf_readytosale_stocks
        $rtsInsertSql = "
            INSERT INTO krf_readytosale_stocks (
                stockorder_no, psono, projectid, productid, productname,
                trayid, qtypertray, locationid, locationname, polyhouseplantid, barcode,
                bedqrcode, deliverydate, remarks, status, created_by, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Readytosale', ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE status = 'Readytosale', updated_at = NOW()
        ";
        $rstmt = $krfconn->prepare($rtsInsertSql);
        if ($rstmt) {
            foreach ($recordsToInsertRts as $rec) {
                $pPlantId = (string)$rec['polyhouseplantid'];
                $bCode = $rec['barcode'] ?: '';
                $bQr = $rec['bedqrcode'] ?: $rec['bed_no'];
                $delivDate = !empty($rec['dispatchdate_internal']) ? $rec['dispatchdate_internal'] : null;
                $rem = "Marked Ready to Sale via QR Scanner in " . $rec['bed_no'];
                $tId = (int)($rec['tray_id'] ?: 1);
                $qPerTray = (int)($rec['qtypertray'] ?: 126);

                $rstmt->bind_param('ssississsssssss',
                    $rec['stockorder_no'],
                    $rec['psono'],
                    $projectid,
                    $rec['productid'],
                    $rec['productname'],
                    $tId,
                    $qPerTray,
                    $rec['locationid'],
                    $locName,
                    $pPlantId,
                    $bCode,
                    $bQr,
                    $delivDate,
                    $rem,
                    $loginname
                );
                $rstmt->execute();
            }
            $rstmt->close();
        }

        $krfconn->commit();
        $targetBedLabel = !empty($bed_no) ? $bed_no : (!empty($recordsToMark[0]['bed_no']) ? $recordsToMark[0]['bed_no'] : '');
        $bedSuffix = !empty($targetBedLabel) ? " in {$targetBedLabel}" : "";
        $msg = count($plantIds) . " tray(s) successfully marked as Ready to Sale" . $bedSuffix . ".";
        if (!empty($alreadyReady)) {
            $msg .= " (" . count($alreadyReady) . " tray(s) were already marked as Ready to Sale).";
        }
        outJson([
            'status' => 'success',
            'message' => $msg,
            'marked_count' => count($plantIds)
        ]);
    } catch (Exception $e) {
        $krfconn->rollback();
        outJson(['status' => 'error', 'message' => 'Error marking trays: ' . $e->getMessage()]);
    }
}

// -------------------------------------------------------------
// ACTION 2: EXECUTE PLANT MOVEMENT
// -------------------------------------------------------------
if ($action === 'move_trays') {
    $bed_no          = trim($_POST['bed_no'] ?? '');
    $locationid      = trim($_POST['locationid'] ?? '');
    $polyhouseid     = trim($_POST['polyhouseid'] ?? '');
    $stockorder_no   = trim($_POST['stockorder_no'] ?? '');
    $productid       = trim($_POST['productid'] ?? '');
    $target_status   = trim($_POST['stock_status'] ?? ''); // Readytosale or OpenStock or both
    $trays_to_move   = (int)($_POST['trays_to_move'] ?? 0);
    $movement_reason = trim($_POST['movement_reason'] ?? '');
    $custom_narration= trim($_POST['custom_narration'] ?? '');
    $mode            = trim($_POST['mode'] ?? '');

    // Allowed movement reasons matching MySQL ENUM values (with backward-compatible aliases)
    $allowedReasons = [
        'Internal Dispatch',
        'Final Dispatch',
        'Move for Grafting',
        'Grafting',
        'Healing',
        'Hardening',
        'Clip Stick Removal',
        'Removed',
        'Add Trays',
        'Grafted',
        'Empty After Grafting',
        'Destroy After grafted',
        'Empty After Shifting',
        'Tray Free After Shifting',
        'Bed Shifting',
        'Polyhouse Shifting',
        'Move for Opensale',
        'opensale',
        'Remove After Over Age',
        'Tray Dead',
        'reverify'
    ];

    if ($trays_to_move <= 0) {
        outJson(['status' => 'error', 'message' => 'Please enter a valid number of trays (must be at least 1).']);
    }

    if (!in_array($movement_reason, $allowedReasons)) {
        outJson([
            'status' => 'error',
            'message' => 'Invalid movement reason selected. Please select one of the allowed reasons.'
        ]);
    }

    // Normalize alias names to exact DB ENUM values
    if ($movement_reason === 'Grafting') $movement_reason = 'Move for Grafting';
    if ($movement_reason === 'Destroy After grafted') $movement_reason = 'Empty After Grafting';
    if ($movement_reason === 'Tray Free After Shifting') $movement_reason = 'Empty After Shifting';
    if ($movement_reason === 'opensale') $movement_reason = 'Move for Opensale';

    // Role-based security check for "Removed" and "Add Trays" (only allowed if loginroleid < 3)
    if (($movement_reason === 'Removed' || $movement_reason === 'Add Trays') && $loginroleid >= 3) {
        outJson([
            'status' => 'error',
            'message' => 'Permission denied: Only Admin / Senior Management (Role ID < 3) can access ' . $movement_reason . '.'
        ]);
    }

    // Target bed / polyhouse parameters
    $target_bed_no               = trim($_POST['target_bed_no'] ?? $_POST['target_polyhouse_bed_no'] ?? '');
    $target_polyhouseid          = trim($_POST['target_polyhouseid'] ?? '');
    $open_sale_location          = trim($_POST['open_sale_location'] ?? '');
    $target_opensale_polyhouseid = trim($_POST['target_opensale_polyhouseid'] ?? '');
    $target_opensale_locationid  = trim($_POST['target_opensale_locationid'] ?? '');

    // Validation & resolution for Move for Opensale
    if ($movement_reason === 'Move for Opensale') {
        if (!empty($target_opensale_polyhouseid)) {
            $phEsc = $krfconn->real_escape_string($target_opensale_polyhouseid);
            $q = $krfconn->query("SELECT polyhouseid, polyhousename, locationid FROM krf_polyhouse_master WHERE polyhouseid = '{$phEsc}' LIMIT 1");
            if ($q && ($row = $q->fetch_assoc())) {
                $target_opensale_polyhouseid = (string)$row['polyhouseid'];
                $target_opensale_locationid  = (string)$row['locationid'];
                $open_sale_location          = (string)$row['polyhousename'];
            }
        } elseif (!empty($open_sale_location)) {
            $locEsc = $krfconn->real_escape_string($open_sale_location);
            $q = $krfconn->query("SELECT polyhouseid, polyhousename, locationid FROM krf_polyhouse_master WHERE polyhousename = '{$locEsc}' OR polyhousename LIKE '%{$locEsc}%' LIMIT 1");
            if ($q && ($row = $q->fetch_assoc())) {
                $target_opensale_polyhouseid = (string)$row['polyhouseid'];
                $target_opensale_locationid  = (string)$row['locationid'];
                $open_sale_location          = (string)$row['polyhousename'];
            }
        }
        if (empty($target_opensale_polyhouseid) && empty($open_sale_location)) {
            outJson(['status' => 'error', 'message' => 'Please select Which Open Sale Location to move trays.']);
        }
    }

    // Validation for Bed Shifting
    if ($movement_reason === 'Bed Shifting') {
        if ($mode === 'scanned_only') {
            outJson(['status' => 'error', 'message' => 'Bed Shifting can only be performed when "Move All Trays in Bed" option is selected.']);
        }
        if (empty($target_bed_no)) {
            outJson(['status' => 'error', 'message' => 'Please select destination Bed No. in this polyhouse to shift trays.']);
        }
        if (strcasecmp($target_bed_no, $bed_no) === 0) {
            outJson(['status' => 'error', 'message' => "Destination Bed cannot be the same as current Bed ({$bed_no})."]);
        }
    }

    // Validation for Polyhouse Shifting
    if ($movement_reason === 'Polyhouse Shifting') {
        if ($mode === 'scanned_only') {
            outJson(['status' => 'error', 'message' => 'Polyhouse Shifting can only be performed when "Move All Trays in Bed" option is selected.']);
        }
        if (empty($target_polyhouseid)) {
            outJson(['status' => 'error', 'message' => 'Please select a destination Polyhouse for Polyhouse Shifting.']);
        }
        if (empty($target_bed_no)) {
            outJson(['status' => 'error', 'message' => 'Please select a destination Bed No. for Polyhouse Shifting.']);
        }
    }

    // Validation for Hardening (Trays transferred to Polyhouse Bed for Hardening)
    if ($movement_reason === 'Hardening') {
        if (empty($target_polyhouseid)) {
            outJson(['status' => 'error', 'message' => 'Please select destination Polyhouse for Hardening.']);
        }
        if (empty($target_bed_no)) {
            outJson(['status' => 'error', 'message' => 'Please select destination Bed No in the target polyhouse for Hardening.']);
        }
    }

    // Narration is mandatory for Internal Dispatch and Final Dispatch
    if ($movement_reason === 'Internal Dispatch' && empty($custom_narration)) {
        outJson([
            'status' => 'error',
            'message' => 'Movement Narration is required for Internal Dispatch. Please specify where to move the plants (destination polyhouse/bed) and the movement reason.'
        ]);
    }

    if ($movement_reason === 'Final Dispatch' && empty($custom_narration)) {
        outJson([
            'status' => 'error',
            'message' => 'Movement Narration is required for Final Dispatch. Please enter Client Full Details, Order No., and Payment Clearance confirmation.'
        ]);
    }

    if (empty($bed_no)) {
        outJson(['status' => 'error', 'message' => 'Bed number is required for movement.']);
    }

    $nowDt = date('d-M-Y h:i A');
    $curDate = date('Y-m-d');

    // =========================================================
    // CASE A: ADD TRAYS (STOCK & INVENTORY INCREMENT)
    // =========================================================
    if ($movement_reason === 'Add Trays') {
        // Fetch a template plant record from this bed & order to replicate all master attributes
        $tmplSql = "SELECT * FROM krf_polyhouse_plant_details 
                    WHERE (projectid = '{$projectid}' OR '{$projectid}' = '0' OR projectid = '17') 
                      AND bed_no = '" . $krfconn->real_escape_string($bed_no) . "' 
                      AND (stockorder_no = '" . $krfconn->real_escape_string($stockorder_no) . "' OR psono = '" . $krfconn->real_escape_string($stockorder_no) . "') 
                    ORDER BY polyhouseplantid DESC LIMIT 1";
        $tmplRes = $krfconn->query($tmplSql);
        $tmpl = ($tmplRes && $tmplRes->num_rows > 0) ? $tmplRes->fetch_assoc() : null;

        if (!$tmpl) {
            $tmplSql2 = "SELECT * FROM krf_polyhouse_plant_details 
                         WHERE (projectid = '{$projectid}' OR '{$projectid}' = '0' OR projectid = '17') 
                           AND (stockorder_no = '" . $krfconn->real_escape_string($stockorder_no) . "' OR psono = '" . $krfconn->real_escape_string($stockorder_no) . "') 
                         ORDER BY polyhouseplantid DESC LIMIT 1";
            $tmplRes2 = $krfconn->query($tmplSql2);
            $tmpl = ($tmplRes2 && $tmplRes2->num_rows > 0) ? $tmplRes2->fetch_assoc() : null;
        }

        if (!$tmpl) {
            outJson(['status' => 'error', 'message' => 'Cannot add trays: No matching plant/order template record found in database.']);
        }

        $qtyPerTray = (int)($tmpl['qtypertray'] ?: 209);
        $totalPlantsToAdd = $trays_to_move * $qtyPerTray;
        $effSoNo = $tmpl['stockorder_no'] ?: $stockorder_no;
        $effPsoNo = $tmpl['psono'] ?: $stockorder_no;
        $effStatus = !empty($target_status) ? $target_status : ($tmpl['status'] ?: 'Readytosale');
        $effStage = ($effStatus === 'OpenStock') ? 'OpenStock' : 'ReadyToSale';
        $barcodePrefix = ($effStatus === 'OpenStock') ? 'OS' : 'RS';
        $datePrefix = date('ymd');

        $remarkLine = "[Added {$trays_to_move} tray(s) manually by {$loginname} on {$nowDt}" . (!empty($custom_narration) ? " | Note: " . $custom_narration : "") . "]";

        $krfconn->begin_transaction();
        try {
            // 1. Update krf_production_stockorder quantity
            $soEsc = $krfconn->real_escape_string($effSoNo);
            $psoEsc = $krfconn->real_escape_string($effPsoNo);
            $updSoSql = "UPDATE krf_production_stockorder 
                         SET quantity = quantity + {$totalPlantsToAdd}, 
                             sowingallotedqty = sowingallotedqty + {$totalPlantsToAdd}, 
                             updated_at = NOW() 
                         WHERE (projectid = '{$projectid}' OR '{$projectid}' = '0' OR projectid = '17') 
                           AND (stockorder_no = '{$soEsc}' OR psono = '{$psoEsc}')";
            $krfconn->query($updSoSql);

            // 2. Insert new rows into krf_polyhouse_plant_details
            $plantInsertSql = "INSERT INTO krf_polyhouse_plant_details (
                planted_date, sowing_date, sowingdate_internal, sowinglotno_internal,
                sowingbatchno, stockorder_no, psono, roottype,
                locationid, polyhouseid, projectid, lotno, batchno, barcode,
                productid, productname, variety, source_type, cultivation_type,
                tray_id, tray_code, qtypertray, tray_position, bedrackname, bed_no,
                growth_stage, health_status, status, workingstatus,
                dispatchdate_internal, expected_harvest_date,
                bedqrcode, movementremark,
                created_at, created_by, updated_at, updated_by
            ) VALUES (
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, 'Healthy', ?, 'Polyhouse',
                ?, ?,
                ?, ?,
                NOW(), ?, NOW(), ?
            )";

            $pStmt = $krfconn->prepare($plantInsertSql);
            if (!$pStmt) throw new Exception("Prepare plant insert failed: " . $krfconn->error);

            // 3. Prepare ready stock insert IF status is Readytosale
            $isReadyStock = ($effStatus === 'Readytosale');
            $rStmt = null;
            if ($isReadyStock) {
                $rtsInsertSql = "INSERT INTO krf_readytosale_stocks (
                    stockorder_no, psono, projectid, productid, productname,
                    trayid, qtypertray, locationid, locationname, polyhouseplantid, barcode,
                    bedqrcode, deliverydate, remarks, status, created_by, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, 'Readytosale', ?, NOW(), NOW()
                )";
                $rStmt = $krfconn->prepare($rtsInsertSql);
                if (!$rStmt) throw new Exception("Prepare ready stock insert failed: " . $krfconn->error);
            }

            // Location Name for ready stock
            $locNameForRts = $locationid ?: ($tmpl['locationid'] ?: 'N/A');

            for ($i = 1; $i <= $trays_to_move; $i++) {
                $newBarcode = $barcodePrefix . '-' . $datePrefix . strtoupper(substr(uniqid(), -4)) . str_pad((string)$i, 2, '0', STR_PAD_LEFT);
                $roottype = $tmpl['roottype'] ?: 'Root';
                $sourceType = $tmpl['source_type'] ?: 'Sowing';
                $cultivationType = $tmpl['cultivation_type'] ?: 'Tray';
                $trayIdInt = (int)($tmpl['tray_id'] ?: 1);

                $pStmt->bind_param(
                    'ssssssssssisssissssisisssssssssss',
                    $tmpl['planted_date'],
                    $tmpl['sowing_date'],
                    $tmpl['sowingdate_internal'],
                    $tmpl['sowinglotno_internal'],
                    $tmpl['sowingbatchno'],
                    $effSoNo,
                    $effPsoNo,
                    $roottype,
                    $tmpl['locationid'],
                    $tmpl['polyhouseid'],
                    $projectid,
                    $tmpl['lotno'],
                    $tmpl['batchno'],
                    $newBarcode,
                    $tmpl['productid'],
                    $tmpl['productname'],
                    $tmpl['variety'],
                    $sourceType,
                    $cultivationType,
                    $trayIdInt,
                    $tmpl['tray_code'],
                    $qtyPerTray,
                    $tmpl['tray_position'],
                    $tmpl['bedrackname'],
                    $bed_no,
                    $effStage,
                    $effStatus,
                    $tmpl['dispatchdate_internal'],
                    $tmpl['expected_harvest_date'],
                    $tmpl['bedqrcode'],
                    $remarkLine,
                    $loginname,
                    $loginname
                );

                if (!$pStmt->execute()) {
                    throw new Exception("Error inserting plant details: " . $pStmt->error);
                }
                $newPlantId = (string)$krfconn->insert_id;

                if ($isReadyStock && $rStmt) {
                    $rStmt->bind_param(
                        'ssissiissssssss',
                        $effSoNo,
                        $effPsoNo,
                        $projectid,
                        $tmpl['productid'],
                        $tmpl['productname'],
                        $trayIdInt,
                        $qtyPerTray,
                        $tmpl['locationid'],
                        $locNameForRts,
                        $newPlantId,
                        $newBarcode,
                        $tmpl['bedqrcode'],
                        $tmpl['dispatchdate_internal'],
                        $remarkLine,
                        $loginname
                    );
                    if (!$rStmt->execute()) {
                        throw new Exception("Error inserting ready stock: " . $rStmt->error);
                    }
                }
            }

            $pStmt->close();
            if ($rStmt) $rStmt->close();

            $krfconn->commit();

            outJson([
                'status'          => 'success',
                'message'         => "Successfully added {$trays_to_move} new tray(s) (" . number_format($totalPlantsToAdd) . " plants) to {$bed_no}, updated Stock Order quantity, and synced inventory records!",
                'moved_trays'     => $trays_to_move,
                'movement_reason' => 'Add Trays',
                'bed_no'          => $bed_no,
                'stockorder_no'   => $effSoNo,
                'productname'     => $tmpl['productname'] ?: 'Plant',
                'narration'       => $remarkLine,
                'moved_by'        => $loginname,
                'moved_at'        => $nowDt
            ]);

        } catch (Throwable $e) {
            $krfconn->rollback();
            outJson(['status' => 'error', 'message' => 'Add Trays transaction failed: ' . $e->getMessage()]);
        }
    }

    // =========================================================
    // CASE B: STANDARD MOVEMENTS, SHIFTING & REMOVAL
    // =========================================================

    $selected_barcodes = $_POST['selected_barcodes'] ?? [];
    if (is_string($selected_barcodes)) {
        $decoded = json_decode($selected_barcodes, true);
        $selected_barcodes = is_array($decoded) ? $decoded : array_filter(array_map('trim', explode(',', $selected_barcodes)));
    }
    $selected_plant_ids = $_POST['selected_plant_ids'] ?? [];
    if (is_string($selected_plant_ids)) {
        $decoded = json_decode($selected_plant_ids, true);
        $selected_plant_ids = is_array($decoded) ? $decoded : array_filter(array_map('intval', explode(',', $selected_plant_ids)));
    }

    $availableRows = [];

    if (!empty($selected_plant_ids)) {
        $idList = implode(',', array_map('intval', $selected_plant_ids));
        $statusClause = ($movement_reason === 'Bed Shifting' || $movement_reason === 'Polyhouse Shifting')
            ? "AND p.status IN ('Polyhouse', 'Readytosale')"
            : "AND p.status NOT IN ('Final Dispatch', 'Removed', 'Empty After Shifting')";
        $fetchSql = "SELECT p.polyhouseplantid, p.barcode, p.status, p.productname, p.qtypertray, p.bed_no, p.polyhouseid, p.stockorder_no, p.psono
                     FROM krf_polyhouse_plant_details p 
                     WHERE p.polyhouseplantid IN ({$idList})
                       {$statusClause}
                     ORDER BY p.polyhouseplantid ASC";
        $res = $krfconn->query($fetchSql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $availableRows[] = $row;
            }
        }
        $trays_to_move = count($availableRows);
    } elseif (!empty($selected_barcodes)) {
        $escapedCodes = array_map(function($c) use ($krfconn) { return "'" . $krfconn->real_escape_string($c) . "'"; }, $selected_barcodes);
        $codeList = implode(',', $escapedCodes);
        $statusClause = ($movement_reason === 'Bed Shifting' || $movement_reason === 'Polyhouse Shifting')
            ? "AND p.status IN ('Polyhouse', 'Readytosale')"
            : "AND p.status NOT IN ('Final Dispatch', 'Removed', 'Empty After Shifting')";
        $fetchSql = "SELECT p.polyhouseplantid, p.barcode, p.status, p.productname, p.qtypertray, p.bed_no, p.polyhouseid, p.stockorder_no, p.psono
                     FROM krf_polyhouse_plant_details p 
                     WHERE p.barcode IN ({$codeList})
                       {$statusClause}
                     ORDER BY p.polyhouseplantid ASC";
        $res = $krfconn->query($fetchSql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $availableRows[] = $row;
            }
        }
        $trays_to_move = count($availableRows);
    } else {
        // Determine eligible plant statuses based on movement reason
        if ($movement_reason === 'Final Dispatch') {
            $statusFilter = "p.status IN ('Readytosale', 'OpenStock')";
        } elseif (!empty($target_status) && in_array($target_status, ['Readytosale', 'OpenStock', 'Polyhouse'])) {
            $statusFilter = "p.status = '{$target_status}'";
        } elseif ($movement_reason === 'Bed Shifting' || $movement_reason === 'Polyhouse Shifting') {
            $statusFilter = "p.status IN ('Polyhouse', 'Readytosale', 'OpenStock')";
        } else {
            // For all plant movements (Move for Grafting, Healing, Hardening, Clip Stick Removal, Internal Dispatch, Removed, Remove After Over Age, etc.):
            // All active trays currently in the polyhouse bed (including 'Polyhouse' status) are eligible to move
            $statusFilter = "p.status NOT IN ('Final Dispatch', 'Removed', 'Remove After Over Age', 'Empty After Shifting', 'Empty After Grafting', 'Destroy After grafted', 'Tray Free After Shifting')";
        }

        $whereArr = [
            "p.projectid = '{$projectid}'",
            "p.bed_no = '" . $krfconn->real_escape_string($bed_no) . "'",
            $statusFilter
        ];
        if (!empty($locationid)) {
            $whereArr[] = "p.locationid = '" . $krfconn->real_escape_string($locationid) . "'";
        }
        if (!empty($polyhouseid)) {
            $whereArr[] = "p.polyhouseid = '" . $krfconn->real_escape_string($polyhouseid) . "'";
        }
        if (!empty($stockorder_no) && $stockorder_no !== 'N/A') {
            $whereArr[] = "p.stockorder_no = '" . $krfconn->real_escape_string($stockorder_no) . "'";
        }
        if (!empty($productid)) {
            $whereArr[] = "p.productid = '" . $krfconn->real_escape_string($productid) . "'";
        }

        $whereSql = implode(" AND ", $whereArr);

        // Fetch matching row IDs to move
        $fetchSql = "SELECT p.polyhouseplantid, p.barcode, p.status, p.productname, p.qtypertray, p.bed_no, p.polyhouseid, p.stockorder_no, p.psono
                     FROM krf_polyhouse_plant_details p 
                     WHERE {$whereSql} 
                     ORDER BY p.polyhouseplantid ASC";
        
        $res = $krfconn->query($fetchSql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $availableRows[] = $row;
            }
        }
    }

    $availCount = count($availableRows);
    if ($availCount <= 0) {
        if ($movement_reason === 'Bed Shifting' || $movement_reason === 'Polyhouse Shifting') {
            outJson([
                'status' => 'error',
                'message' => "No trays with status 'Polyhouse' or 'Ready to Sale' found in " . ($bed_no ?: 'selected scope') . " to shift."
            ]);
        }
        outJson([
            'status' => 'error',
            'message' => 'No active/eligible trays found in ' . ($bed_no ?: 'selected scope') . ' to move.'
        ]);
    }

    if ($movement_reason === 'Bed Shifting' || $movement_reason === 'Polyhouse Shifting') {
        $trays_to_move = $availCount;
    } elseif ($trays_to_move > $availCount) {
        outJson([
            'status' => 'error',
            'message' => "Requested to move {$trays_to_move} trays, but only {$availCount} tray(s) are available in {$bed_no}."
        ]);
    }

    // Pick the specific rows to move (LIMIT trays_to_move)
    $rowsToMove = array_slice($availableRows, 0, $trays_to_move);
    $targetPlantIds = array_column($rowsToMove, 'polyhouseplantid');
    $targetBarcodes = array_filter(array_column($rowsToMove, 'barcode'));

    // Enforce that Final Dispatch is ONLY allowed for trays that are already marked as Ready to Sale
    if ($movement_reason === 'Final Dispatch') {
        foreach ($rowsToMove as $r) {
            $rSt = trim($r['status'] ?? '');
            if (strcasecmp($rSt, 'Readytosale') !== 0) {
                outJson([
                    'status' => 'error',
                    'message' => "Tray {$r['barcode']} cannot be dispatched because its status is \"{$rSt}\". Only trays marked as \"Ready to Sale\" can undergo Final Dispatch. Please mark it as Ready to Sale first."
                ]);
            }
        }
    }

    // Format Movement Remark based on action type
    if ($movement_reason === 'Bed Shifting') {
        $remarkLine = "[Bed Shifted from {$bed_no} to {$target_bed_no} | {$trays_to_move} tray(s) by {$loginname} on {$nowDt}" . (!empty($custom_narration) ? " | Note: " . $custom_narration : "") . "]";
    } elseif ($movement_reason === 'Polyhouse Shifting') {
        $remarkLine = "[Polyhouse Shifted from PH-{$polyhouseid}/{$bed_no} to PH-{$target_polyhouseid}/{$target_bed_no} | {$trays_to_move} tray(s) by {$loginname} on {$nowDt}" . (!empty($custom_narration) ? " | Note: " . $custom_narration : "") . "]";
    } elseif ($movement_reason === 'Removed') {
        $remarkLine = "[Removed {$trays_to_move} tray(s) by {$loginname} on {$nowDt}" . (!empty($custom_narration) ? " | Note: " . $custom_narration : "") . "]";
    } else {
        $remarkLine = "[Moved to '{$movement_reason}' | {$trays_to_move} tray(s) by {$loginname} on {$nowDt}" . (!empty($custom_narration) ? " | Note: " . $custom_narration : "") . "]";
    }

    $krfconn->begin_transaction();
    try {
        $idListStr = implode(",", array_map('intval', $targetPlantIds));
        $remarkEsc = $krfconn->real_escape_string($remarkLine);
        $userEsc   = $krfconn->real_escape_string($loginname);

        if ($movement_reason === 'Bed Shifting') {
            $targetBedEsc = $krfconn->real_escape_string($target_bed_no);
            
            // 1. Update krf_polyhouse_plant_details: Update bed_no and bedrackname to target bed
            $updPlantSql = "
                UPDATE krf_polyhouse_plant_details
                SET 
                    bed_no = '{$targetBedEsc}',
                    bedrackname = '{$targetBedEsc}',
                    move_at = '{$curDate}',
                    movementremark = CONCAT(COALESCE(movementremark, ''), IF(COALESCE(movementremark, '') = '', '', '\n'), '{$remarkEsc}'),
                    updated_at = NOW(),
                    updated_by = '{$userEsc}'
                WHERE polyhouseplantid IN ({$idListStr})
            ";
            if (!$krfconn->query($updPlantSql)) {
                throw new Exception("Error updating bed shifting: " . $krfconn->error);
            }

            // 2. Update krf_readytosale_stocks remarks if present
            if (!empty($targetPlantIds) || !empty($targetBarcodes)) {
                $rtsConds = [];
                if (!empty($targetPlantIds)) $rtsConds[] = "polyhouseplantid IN ({$idListStr})";
                if (!empty($targetBarcodes)) {
                    $bcEscList = "'" . implode("','", array_map([$krfconn, 'real_escape_string'], $targetBarcodes)) . "'";
                    $rtsConds[] = "barcode IN ({$bcEscList})";
                }
                $rtsWhere = implode(" OR ", $rtsConds);
                $updRtsSql = "
                    UPDATE krf_readytosale_stocks
                    SET 
                        remarks = CONCAT(COALESCE(remarks, ''), IF(COALESCE(remarks, '') = '', '', ' | '), '{$remarkEsc}'),
                        updated_at = NOW()
                    WHERE ({$rtsWhere})
                ";
                $krfconn->query($updRtsSql);
            }

            $successMsg = "Successfully shifted {$trays_to_move} tray(s) from {$bed_no} to {$target_bed_no} in the same polyhouse!";

        } elseif ($movement_reason === 'Polyhouse Shifting') {
            $targetPhEsc  = $krfconn->real_escape_string($target_polyhouseid);
            $targetBedEsc = $krfconn->real_escape_string($target_bed_no);

            // 1. Update krf_polyhouse_plant_details: Update polyhouseid, bed_no & bedrackname
            $updPlantSql = "
                UPDATE krf_polyhouse_plant_details
                SET 
                    polyhouseid = '{$targetPhEsc}',
                    bed_no = '{$targetBedEsc}',
                    bedrackname = '{$targetBedEsc}',
                    move_at = '{$curDate}',
                    movementremark = CONCAT(COALESCE(movementremark, ''), IF(COALESCE(movementremark, '') = '', '', '\n'), '{$remarkEsc}'),
                    updated_at = NOW(),
                    updated_by = '{$userEsc}'
                WHERE polyhouseplantid IN ({$idListStr})
            ";
            if (!$krfconn->query($updPlantSql)) {
                throw new Exception("Error updating polyhouse shifting: " . $krfconn->error);
            }

            // 2. Update krf_readytosale_stocks
            if (!empty($targetPlantIds) || !empty($targetBarcodes)) {
                $rtsConds = [];
                if (!empty($targetPlantIds)) $rtsConds[] = "polyhouseplantid IN ({$idListStr})";
                if (!empty($targetBarcodes)) {
                    $bcEscList = "'" . implode("','", array_map([$krfconn, 'real_escape_string'], $targetBarcodes)) . "'";
                    $rtsConds[] = "barcode IN ({$bcEscList})";
                }
                $rtsWhere = implode(" OR ", $rtsConds);
                $updRtsSql = "
                    UPDATE krf_readytosale_stocks
                    SET 
                        remarks = CONCAT(COALESCE(remarks, ''), IF(COALESCE(remarks, '') = '', '', ' | '), '{$remarkEsc}'),
                        updated_at = NOW()
                    WHERE ({$rtsWhere})
                ";
                $krfconn->query($updRtsSql);
            }

            $successMsg = "Successfully shifted {$trays_to_move} tray(s) to Polyhouse {$target_polyhouseid}, {$target_bed_no}!";

        } elseif ($movement_reason === 'Hardening') {
            $targetPhEsc  = $krfconn->real_escape_string($target_polyhouseid);
            $targetBedEsc = $krfconn->real_escape_string($target_bed_no);

            // Fetch locationid of target polyhouse if available
            $targetLocId = '';
            if (!empty($target_polyhouseid)) {
                $phLocRes = $krfconn->query("SELECT locationid FROM krf_polyhouse_master WHERE polyhouseid = '{$targetPhEsc}' LIMIT 1");
                if ($phLocRes && $phLocRow = $phLocRes->fetch_assoc()) {
                    $targetLocId = $phLocRow['locationid'] ?? '';
                }
            }
            $locUpdateSql = !empty($targetLocId) ? "locationid = '{$krfconn->real_escape_string($targetLocId)}'," : "";

            // 1. Update krf_polyhouse_plant_details: Update polyhouseid, bed_no, bedrackname, status, workingstatus, and growth_stage
            $updPlantSql = "
                UPDATE krf_polyhouse_plant_details
                SET 
                    {$locUpdateSql}
                    polyhouseid = '{$targetPhEsc}',
                    bed_no = '{$targetBedEsc}',
                    bedrackname = '{$targetBedEsc}',
                    status = 'Hardening',
                    workingstatus = 'Hardening',
                    growth_stage = 'Hardening',
                    move_at = '{$curDate}',
                    movementremark = CONCAT(COALESCE(movementremark, ''), IF(COALESCE(movementremark, '') = '', '', '\n'), '{$remarkEsc}'),
                    updated_at = NOW(),
                    updated_by = '{$userEsc}'
                WHERE polyhouseplantid IN ({$idListStr})
            ";
            if (!$krfconn->query($updPlantSql)) {
                throw new Exception("Error updating hardening movement: " . $krfconn->error);
            }

            // 2. Update krf_readytosale_stocks: mark status as Hardening and update bedqrcode
            if (!empty($targetPlantIds) || !empty($targetBarcodes)) {
                $rtsConds = [];
                if (!empty($targetPlantIds)) $rtsConds[] = "polyhouseplantid IN ({$idListStr})";
                if (!empty($targetBarcodes)) {
                    $bcEscList = "'" . implode("','", array_map([$krfconn, 'real_escape_string'], $targetBarcodes)) . "'";
                    $rtsConds[] = "barcode IN ({$bcEscList})";
                }
                $rtsWhere = implode(" OR ", $rtsConds);
                $updRtsSql = "
                    UPDATE krf_readytosale_stocks
                    SET 
                        status = 'Hardening',
                        bedqrcode = '{$targetBedEsc}',
                        remarks = CONCAT(COALESCE(remarks, ''), IF(COALESCE(remarks, '') = '', '', ' | '), '{$remarkEsc}'),
                        updated_at = NOW()
                    WHERE ({$rtsWhere})
                ";
                $krfconn->query($updRtsSql);
            }

            $successMsg = "Successfully moved {$trays_to_move} tray(s) for Hardening to Polyhouse {$target_polyhouseid}, Bed {$target_bed_no}!";

        } elseif ($movement_reason === 'Removed' || $movement_reason === 'Remove After Over Age') {
            // Action for Removed / Remove After Over Age: Update plant details to Removed/Remove After Over Age, decrease stockorder qty, and delete from ready stock
            $qtyPerTray = (int)($rowsToMove[0]['qtypertray'] ?? 209);
            $plantsToRemove = $trays_to_move * $qtyPerTray;
            $soEsc = $krfconn->real_escape_string($stockorder_no);

            // 1. Update krf_production_stockorder: reduce quantity
            $soDownSql = "UPDATE krf_production_stockorder 
                          SET quantity = GREATEST(0, quantity - {$plantsToRemove}), 
                              sowingallotedqty = GREATEST(0, sowingallotedqty - {$plantsToRemove}), 
                              updated_at = NOW() 
                          WHERE (projectid = '{$projectid}' OR '{$projectid}' = '0' OR projectid = '17') 
                            AND (stockorder_no = '{$soEsc}' OR psono = '{$soEsc}')";
            $krfconn->query($soDownSql);

            // 2. Update krf_polyhouse_plant_details
            $reasonEsc = $krfconn->real_escape_string($movement_reason);
            $updPlantSql = "
                UPDATE krf_polyhouse_plant_details
                SET 
                    status = '{$reasonEsc}',
                    workingstatus = '{$reasonEsc}',
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
            if (!empty($targetPlantIds) || !empty($targetBarcodes)) {
                $rtsConds = [];
                if (!empty($targetPlantIds)) $rtsConds[] = "polyhouseplantid IN ({$idListStr})";
                if (!empty($targetBarcodes)) {
                    $bcEscList = "'" . implode("','", array_map([$krfconn, 'real_escape_string'], $targetBarcodes)) . "'";
                    $rtsConds[] = "barcode IN ({$bcEscList})";
                }
                $rtsWhere = implode(" OR ", $rtsConds);
                $delRtsSql = "DELETE FROM krf_readytosale_stocks WHERE ({$rtsWhere})";
                $krfconn->query($delRtsSql);

                // Also delete from krf_opensale_stocks if any
                $delOsSql = "DELETE FROM krf_opensale_stocks WHERE ({$rtsWhere})";
                $krfconn->query($delOsSql);
            }

            if ($movement_reason === 'Remove After Over Age') {
                $successMsg = "Successfully removed {$trays_to_move} over-aged tray(s) from {$bed_no}, reduced stock order quantity, and deleted corresponding ready stock records.";
            } else {
                $successMsg = "Successfully removed {$trays_to_move} tray(s) from {$bed_no}, reduced stock order quantity, and deleted corresponding ready stock records.";
            }

        } else {
            // General movement reasons: Update status, workingstatus, and growth_stage
            $reasonEsc = $krfconn->real_escape_string($movement_reason);

            // 1. If moving for Open Sale, capture TRUE source nursery origin BEFORE any updates occur
            $sourceSnapshots = [];
            if ($movement_reason === 'Move for Opensale') {
                $srcPlantsSql = "SELECT p.*, pm.polyhousename AS source_polyhousename 
                                 FROM krf_polyhouse_plant_details p 
                                 LEFT JOIN krf_polyhouse_master pm ON pm.polyhouseid = p.polyhouseid 
                                 WHERE p.polyhouseplantid IN ({$idListStr})";
                $srcRes = $krfconn->query($srcPlantsSql);
                if ($srcRes && $srcRes->num_rows > 0) {
                    while ($sRow = $srcRes->fetch_assoc()) {
                        $pId = (int)$sRow['polyhouseplantid'];

                        // Determine Source Location ID & Name
                        $srcLocId = (string)($sRow['locationid'] ?: ($locationid ?: ''));
                        $srcLocName = $srcLocId;
                        if (!empty($srcLocId) && isset($hrmconn)) {
                            $lstmt = $hrmconn->prepare("SELECT locationname FROM hrm_AttendanceLocationMaster WHERE (locationid = ? OR locationname = ?) LIMIT 1");
                            if ($lstmt) {
                                $lstmt->bind_param('ss', $srcLocId, $srcLocId);
                                $lstmt->execute();
                                $lr = $lstmt->get_result()->fetch_assoc();
                                if ($lr && !empty($lr['locationname'])) {
                                    $srcLocName = $lr['locationname'];
                                }
                                $lstmt->close();
                            }
                        }

                        // Determine Source Polyhouse ID & Name
                        $srcPhId = (string)($sRow['polyhouseid'] ?: ($polyhouseid ?: ''));
                        $srcPhName = (string)($sRow['source_polyhousename'] ?: '');
                        if (empty($srcPhName) && !empty($srcPhId)) {
                            $phstmt = $krfconn->prepare("SELECT polyhousename FROM krf_polyhouse_master WHERE polyhouseid = ? LIMIT 1");
                            if ($phstmt) {
                                $phstmt->bind_param('s', $srcPhId);
                                $phstmt->execute();
                                $pr = $phstmt->get_result()->fetch_assoc();
                                if ($pr && !empty($pr['polyhousename'])) {
                                    $srcPhName = $pr['polyhousename'];
                                }
                                $phstmt->close();
                            }
                        }

                        // Determine Source Bed
                        $srcBed = (string)($sRow['bed_no'] ?: ($bed_no ?: ''));

                        $sourceSnapshots[$pId] = [
                            'row'               => $sRow,
                            'from_locationid'   => $srcLocId,
                            'from_locationname' => $srcLocName,
                            'from_polyhouseid'  => $srcPhId,
                            'from_polyhousename'=> $srcPhName,
                            'from_bed_no'       => $srcBed,
                            'actual_plant_count'=> calculateActualPlantInTray($sRow)
                        ];
                    }
                }
            }

            // 2. Prepare polyhouse update for Open Sale
            $osPhUpd = "";
            if ($movement_reason === 'Move for Opensale' && !empty($target_opensale_polyhouseid)) {
                $targetOsPhEsc = $krfconn->real_escape_string($target_opensale_polyhouseid);
                $osPhUpd .= ", polyhouseid = '{$targetOsPhEsc}'";
                if (!empty($target_opensale_locationid)) {
                    $targetOsLocEsc = $krfconn->real_escape_string($target_opensale_locationid);
                    $osPhUpd .= ", locationid = '{$targetOsLocEsc}', movetolocation = '{$targetOsLocEsc}'";
                }
                $osPhUpd .= ", bed_no = 'OPEN RACK', bedrackname = 'OPEN RACK'";
            }

            // 3. Update krf_polyhouse_plant_details
            $updPlantSql = "
                UPDATE krf_polyhouse_plant_details
                SET 
                    status = '{$reasonEsc}',
                    workingstatus = '{$reasonEsc}',
                    growth_stage = '{$reasonEsc}',
                    move_at = '{$curDate}',
                    movementremark = CONCAT(COALESCE(movementremark, ''), IF(COALESCE(movementremark, '') = '', '', '\n'), '{$remarkEsc}'),
                    updated_at = NOW(),
                    updated_by = '{$userEsc}'
                    {$osPhUpd}
                WHERE polyhouseplantid IN ({$idListStr})
            ";
            if (!$krfconn->query($updPlantSql)) {
                throw new Exception("Error updating plant movement: " . $krfconn->error);
            }

            // 4. Update krf_readytosale_stocks
            if (!empty($targetPlantIds) || !empty($targetBarcodes)) {
                $rtsConds = [];
                if (!empty($targetPlantIds)) $rtsConds[] = "polyhouseplantid IN ({$idListStr})";
                if (!empty($targetBarcodes)) {
                    $bcEscList = "'" . implode("','", array_map([$krfconn, 'real_escape_string'], $targetBarcodes)) . "'";
                    $rtsConds[] = "barcode IN ({$bcEscList})";
                }
                $rtsWhere = implode(" OR ", $rtsConds);

                $updRtsSql = "
                    UPDATE krf_readytosale_stocks
                    SET 
                        status = '{$reasonEsc}',
                        remarks = CONCAT(COALESCE(remarks, ''), IF(COALESCE(remarks, '') = '', '', ' | '), '{$remarkEsc}'),
                        updated_at = NOW()
                    WHERE ({$rtsWhere}) AND status IN ('Readytosale', 'Ready to sale', 'ReadyToSale')
                ";
                $krfconn->query($updRtsSql);
            }

            // 5. Sync to krf_opensale_stocks using TRUE source nursery origin
            if ($movement_reason === 'Move for Opensale' && !empty($sourceSnapshots)) {
                $osStmt = $krfconn->prepare("
                    INSERT INTO krf_opensale_stocks (
                        polyhouseplantid, barcode, stockorder_no, psono, projectid, productid, productname, variety,
                        trayid, tray_code, qtypertray, actual_plant_count, from_locationid, from_locationname,
                        from_polyhouseid, from_polyhousename, from_bed_no, open_sale_location, opensale_locationid, movement_reason,
                        status, remarks, created_by, created_at, updated_at
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, 'Move for Opensale',
                        'OpenSale', ?, ?, NOW(), NOW()
                    ) ON DUPLICATE KEY UPDATE 
                        polyhouseplantid    = VALUES(polyhouseplantid),
                        status              = 'OpenSale',
                        open_sale_location  = VALUES(open_sale_location),
                        opensale_locationid = VALUES(opensale_locationid),
                        movement_reason     = VALUES(movement_reason),
                        actual_plant_count  = VALUES(actual_plant_count),
                        from_locationid     = VALUES(from_locationid),
                        from_locationname   = VALUES(from_locationname),
                        from_polyhouseid    = VALUES(from_polyhouseid),
                        from_polyhousename  = VALUES(from_polyhousename),
                        from_bed_no         = VALUES(from_bed_no),
                        remarks             = VALUES(remarks),
                        updated_at          = NOW()
                ");
                if ($osStmt) {
                    // Normalize open sale location to valid MySQL ENUM ('Karjee', 'Sargawa', 'Mansoon')
                    $cleanEnum = 'Karjee';
                    if (stripos($open_sale_location, 'Mansoon') !== false) {
                        $cleanEnum = 'Mansoon';
                    } elseif (stripos($open_sale_location, 'Sargw') !== false || stripos($open_sale_location, 'Sargaw') !== false) {
                        $cleanEnum = 'Sargawa';
                    } elseif (stripos($open_sale_location, 'Karjee') !== false) {
                        $cleanEnum = 'Karjee';
                    }

                    // Fallback for target open sale location ID if empty
                    if (empty($target_opensale_locationid)) {
                        if ($cleanEnum === 'Mansoon') {
                            $target_opensale_locationid = 'LOC6a4f396f08173';
                        } elseif ($cleanEnum === 'Sargawa') {
                            $target_opensale_locationid = 'LOC696b280faf104';
                        } else {
                            $target_opensale_locationid = 'LOC6905e0fd5ab05';
                        }
                    }

                    foreach ($sourceSnapshots as $snap) {
                        $pRow = $snap['row'];
                        $pPlantId = (int)$pRow['polyhouseplantid'];
                        $pBarcode = (string)$pRow['barcode'];
                        $pSoNo = (string)($pRow['stockorder_no'] ?? '');
                        $pPsoNo = (string)($pRow['psono'] ?? '');
                        $pProjId = (int)($pRow['projectid'] ?: 17);
                        $pProdId = (string)($pRow['productid'] ?? '');
                        $pProdName = (string)($pRow['productname'] ?? '');
                        $pVariety = (string)($pRow['variety'] ?? '');
                        $pTrayId = (int)($pRow['tray_id'] ?: 1);
                        $pTrayCode = (string)($pRow['tray_code'] ?? '');
                        $pQty = (int)($pRow['qtypertray'] ?: 0);
                        $pActualCount = $snap['actual_plant_count'];

                        // TRUE Source Origin
                        $pFromLocId    = $snap['from_locationid'];
                        $pFromLocName  = $snap['from_locationname'];
                        $pFromPolyId   = $snap['from_polyhouseid'];
                        $pFromPolyName = $snap['from_polyhousename'];
                        $pFromBedNo    = $snap['from_bed_no'];

                        $pRemark = $remarkLine;
                        $pCreatedBy = $loginname;

                        $osStmt->bind_param(
                            'isssisssisissssssssss',
                            $pPlantId, $pBarcode, $pSoNo, $pPsoNo, $pProjId, $pProdId, $pProdName, $pVariety,
                            $pTrayId, $pTrayCode, $pQty, $pActualCount, 
                            $pFromLocId, $pFromLocName,
                            $pFromPolyId, $pFromPolyName, $pFromBedNo, 
                            $cleanEnum, $target_opensale_locationid,
                            $pRemark, $pCreatedBy
                        );
                        $osStmt->execute();
                    }
                    $osStmt->close();
                }
            }

            $successMsg = "Successfully moved {$trays_to_move} tray(s) of " . ($rowsToMove[0]['productname'] ?? 'Plant') . " to '{$movement_reason}' from {$bed_no}!";
        }

        $krfconn->commit();

        $remainingCount = $availCount - $trays_to_move;
        $prodName = $rowsToMove[0]['productname'] ?? 'Plant';

        outJson([
            'status'          => 'success',
            'message'         => $successMsg,
            'moved_trays'     => $trays_to_move,
            'remaining_trays' => $remainingCount,
            'movement_reason' => $movement_reason,
            'bed_no'          => in_array($movement_reason, ['Bed Shifting', 'Polyhouse Shifting', 'Hardening']) ? $target_bed_no : $bed_no,
            'stockorder_no'   => $stockorder_no,
            'productname'     => $prodName,
            'narration'       => $remarkLine,
            'moved_by'        => $loginname,
            'moved_at'        => $nowDt
        ]);

    } catch (Throwable $e) {
        $krfconn->rollback();
        outJson([
            'status' => 'error',
            'message' => 'Movement transaction failed: ' . $e->getMessage()
        ]);
    }
}

// -------------------------------------------------------------
// ACTION 3: GET RECENT MOVEMENTS LOG (DATE & ORDER NO GROUPED WITH FILTERS)
// -------------------------------------------------------------
if ($action === 'recent_movements') {
    $limit = (int)($_REQUEST['limit'] ?? 100);
    if ($limit <= 0 || $limit > 500) $limit = 100;

    $order_no           = trim($_REQUEST['order_no'] ?? '');
    $product_name       = trim($_REQUEST['product_name'] ?? '');
    $destination_reason = trim($_REQUEST['destination_reason'] ?? $_REQUEST['movement_reason'] ?? $_REQUEST['status'] ?? '');
    $moved_by           = trim($_REQUEST['moved_by'] ?? '');
    $from_date          = trim($_REQUEST['from_date'] ?? '');
    $to_date            = trim($_REQUEST['to_date'] ?? '');

    $where = [
        "p.projectid = '{$projectid}'",
        "p.movementremark IS NOT NULL",
        "TRIM(p.movementremark) <> ''"
    ];

    if (!empty($order_no)) {
        $ordEsc = $krfconn->real_escape_string($order_no);
        $cleanOrd = preg_replace('/^(RSO|OS|PSO)-/i', '', $order_no);
        $cleanOrdEsc = $krfconn->real_escape_string($cleanOrd);
        $where[] = "(p.stockorder_no LIKE '%{$ordEsc}%' OR p.psono LIKE '%{$ordEsc}%' OR p.stockorder_no LIKE '%{$cleanOrdEsc}%' OR p.barcode LIKE '%{$ordEsc}%')";
    }

    if (!empty($product_name)) {
        $prodEsc = $krfconn->real_escape_string($product_name);
        $where[] = "p.productname = '{$prodEsc}'";
    }

    if (!empty($destination_reason)) {
        $reasonEsc = $krfconn->real_escape_string($destination_reason);
        $where[] = "(p.status = '{$reasonEsc}' OR p.movementremark LIKE '%{$reasonEsc}%')";
    }

    if (!empty($moved_by)) {
        $userEsc = $krfconn->real_escape_string($moved_by);
        $where[] = "p.updated_by = '{$userEsc}'";
    }

    if (!empty($from_date)) {
        $fromEsc = $krfconn->real_escape_string($from_date);
        $where[] = "DATE(p.updated_at) >= '{$fromEsc}'";
    }

    if (!empty($to_date)) {
        $toEsc = $krfconn->real_escape_string($to_date);
        $where[] = "DATE(p.updated_at) <= '{$toEsc}'";
    }

    $whereClause = implode(" AND ", $where);

    $sql = "
        SELECT 
            p.stockorder_no,
            p.psono,
            p.productname,
            p.variety,
            p.bed_no,
            p.polyhouseid,
            p.locationid,
            p.status,
            p.workingstatus,
            p.movementremark,
            p.updated_by,
            DATE(p.updated_at) as move_date,
            MAX(p.updated_at) as max_updated_at,
            COUNT(*) as tray_count,
            SUM(COALESCE(NULLIF(p.qtypertray, ''), 209)) as plant_count
        FROM krf_polyhouse_plant_details p
        WHERE {$whereClause}
        GROUP BY 
            DATE_FORMAT(p.updated_at, '%Y-%m-%d %H:%i'),
            p.stockorder_no,
            p.bed_no,
            p.status,
            p.movementremark,
            p.updated_by
        ORDER BY max_updated_at DESC
        LIMIT {$limit}
    ";

    $res = $krfconn->query($sql);
    $logs = [];
    $totalTrays = 0;
    $totalPlants = 0;

    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $trays = (int)$r['tray_count'];
            $plants = (int)$r['plant_count'];
            $totalTrays += $trays;
            $totalPlants += $plants;

            $logs[] = [
                'stockorder_no' => $r['stockorder_no'],
                'psono'         => $r['psono'],
                'productname'   => $r['productname'],
                'variety'       => $r['variety'],
                'bed_no'        => $r['bed_no'],
                'polyhouseid'   => $r['polyhouseid'],
                'locationid'    => $r['locationid'],
                'status'        => $r['status'],
                'tray_count'    => $trays,
                'plant_count'   => $plants,
                'movementremark'=> $r['movementremark'],
                'updated_at'    => !empty($r['max_updated_at']) ? date('d-M-Y h:i A', strtotime($r['max_updated_at'])) : '',
                'raw_date'      => $r['move_date'],
                'updated_by'    => $r['updated_by'] ?: 'Admin'
            ];
        }
    }

    // Fetch distinct filter options
    $prodOptions = [];
    $pRes = $krfconn->query("SELECT DISTINCT productname FROM krf_polyhouse_plant_details WHERE projectid = '{$projectid}' AND movementremark IS NOT NULL AND TRIM(movementremark) <> '' AND productname IS NOT NULL AND TRIM(productname) <> '' ORDER BY productname ASC");
    if ($pRes) {
        while ($pr = $pRes->fetch_assoc()) {
            $prodOptions[] = $pr['productname'];
        }
    }

    $reasonOptions = [];
    $rRes = $krfconn->query("SELECT DISTINCT status FROM krf_polyhouse_plant_details WHERE projectid = '{$projectid}' AND movementremark IS NOT NULL AND TRIM(movementremark) <> '' AND status IS NOT NULL AND TRIM(status) <> '' ORDER BY status ASC");
    if ($rRes) {
        while ($rr = $rRes->fetch_assoc()) {
            if (!in_array($rr['status'], $reasonOptions)) {
                $reasonOptions[] = $rr['status'];
            }
        }
    }
    // Add common movement reasons if not present
    $standardReasons = ['Bed Shifting', 'Polyhouse Shifting', 'Final Dispatch', 'Internal Dispatch', 'Readytosale', 'OpenStock', 'Clip Stick Removal', 'Removed', 'Grafted'];
    foreach ($standardReasons as $sr) {
        if (!in_array($sr, $reasonOptions)) {
            $reasonOptions[] = $sr;
        }
    }

    $userOptions = [];
    $uRes = $krfconn->query("SELECT DISTINCT updated_by FROM krf_polyhouse_plant_details WHERE projectid = '{$projectid}' AND movementremark IS NOT NULL AND TRIM(movementremark) <> '' AND updated_by IS NOT NULL AND TRIM(updated_by) <> '' ORDER BY updated_by ASC");
    if ($uRes) {
        while ($ur = $uRes->fetch_assoc()) {
            $userOptions[] = $ur['updated_by'];
        }
    }

    outJson([
        'status' => 'success',
        'data' => $logs,
        'summary' => [
            'total_rows'   => count($logs),
            'total_trays'  => $totalTrays,
            'total_plants' => $totalPlants
        ],
        'filter_options' => [
            'products' => $prodOptions,
            'reasons'  => $reasonOptions,
            'users'    => $userOptions
        ]
    ]);
}

outJson(['status' => 'error', 'message' => 'Invalid action parameter.']);
