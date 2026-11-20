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

/**
 * Helper to fetch all active OpenSale polyhouse locations
 */
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
            $list[] = [
                'polyhouseid'   => $row['polyhouseid'],
                'polyhousename' => $row['polyhousename'] ?: ('Open Sale ' . $row['polyhouseid']),
                'locationid'    => $row['locationid'],
                'type'          => $row['type'],
                'structure_type'=> $row['structure_type']
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

/**
 * Helper to insert or update krf_opensale_stocks for Open Sale movement.
 * Note: Per workflow specifications, destination row is NOT inserted into krf_polyhouse_plant_details.
 * Only krf_opensale_stocks is inserted/updated with source polyhouseplantid, resolved from_locationname,
 * and opensale_locationid.
 *
 * @param mysqli $krfconn
 * @param array $pRow Source plant row from krf_polyhouse_plant_details
 * @param string $targetPhId Target Open Sale polyhouseid (e.g. 17, 18, 19)
 * @param string $targetLocId Target Open Sale locationid (e.g. LOC6a4f396f08173)
 * @param string $openSaleLocName Name of Open Sale Center (e.g. Open Sale Mansoon Agro)
 * @param string $remarkLine Formatted movement remark
 * @param string $loginname Current logged-in user
 * @param string $curDate Current date (Y-m-d)
 * @param array $origLocationInfo Optional original location details (from_locationid, from_polyhouseid, from_bed_no)
 * @return array ['success' => bool, 'polyhouseplantid' => int]
 */
function insertOrSyncOpenSalePlantRow($krfconn, $pRow, $targetPhId, $targetLocId, $openSaleLocName, $remarkLine, $loginname, $curDate, $origLocationInfo = []) {
    global $hrmconn;

    $pBarcode = trim((string)($pRow['barcode'] ?? ''));
    if (empty($pBarcode)) {
        return ['success' => false, 'error' => 'Tray barcode is missing.'];
    }

    $sourcePlantId   = (int)($pRow['polyhouseplantid'] ?? 0);
    $targetPhId      = trim((string)$targetPhId);
    $targetLocId     = trim((string)$targetLocId);
    $openSaleLocName = trim((string)$openSaleLocName);

    // Resolve true location name for from_locationname (not locationid)
    $pFromLocId = (string)($origLocationInfo['locationid'] ?? $pRow['locationid'] ?? '');
    $pFromLocName = (string)($origLocationInfo['locationname'] ?? '');

    if (empty($pFromLocName) || $pFromLocName === $pFromLocId || stripos($pFromLocName, 'LOC') === 0) {
        if (!empty($pFromLocId) && isset($hrmconn) && $hrmconn) {
            $lstmt = $hrmconn->prepare("SELECT locationname FROM hrm_AttendanceLocationMaster WHERE locationid = ? LIMIT 1");
            if ($lstmt) {
                $lstmt->bind_param('s', $pFromLocId);
                $lstmt->execute();
                $lr = $lstmt->get_result()->fetch_assoc();
                $lstmt->close();
                if ($lr && !empty($lr['locationname'])) {
                    $pFromLocName = $lr['locationname'];
                }
            }
        }
    }
    if (empty($pFromLocName)) {
        $pFromLocName = $pFromLocId;
    }

    // Insert or update krf_opensale_stocks
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
            polyhouseplantid = VALUES(polyhouseplantid),
            status = 'OpenSale',
            open_sale_location = VALUES(open_sale_location),
            opensale_locationid = VALUES(opensale_locationid),
            movement_reason = VALUES(movement_reason),
            actual_plant_count = VALUES(actual_plant_count),
            from_locationid = VALUES(from_locationid),
            from_locationname = VALUES(from_locationname),
            from_polyhouseid = VALUES(from_polyhouseid),
            from_polyhousename = VALUES(from_polyhousename),
            from_bed_no = VALUES(from_bed_no),
            remarks = VALUES(remarks),
            updated_at = NOW()
    ");

    if (!$osStmt) {
        return ['success' => false, 'error' => "Prepare opensale stocks insert failed: " . $krfconn->error];
    }

    $pSoNo = (string)($pRow['stockorder_no'] ?? '');
    $pPsoNo = (string)($pRow['psono'] ?? '');
    $pProjId = (int)($pRow['projectid'] ?: 17);
    $pProdId = (string)($pRow['productid'] ?? '');
    $pProdName = (string)($pRow['productname'] ?? '');
    $pVariety = (string)($pRow['variety'] ?? '');
    $pTrayId = (int)($pRow['tray_id'] ?: 1);
    $pTrayCode = (string)($pRow['tray_code'] ?? '');
    $pQty = (int)($pRow['qtypertray'] ?: 0);
    $pActualCount = calculateActualPlantInTray($pRow);

    $pPolyId = (string)($origLocationInfo['polyhouseid'] ?? $pRow['polyhouseid'] ?? '');
    $pPolyName = (string)($origLocationInfo['polyhousename'] ?? $pRow['polyhousename'] ?? ('PH-' . $pPolyId));
    $pBedNo = (string)($origLocationInfo['bed_no'] ?? $pRow['bed_no'] ?? '');

    $pRemark = (string)$remarkLine;
    $pCreatedBy = (string)$loginname;

    $osTypes = "isssisssisissssssssss";
    $osStmt->bind_param(
        $osTypes,
        $sourcePlantId, $pBarcode, $pSoNo, $pPsoNo, $pProjId, $pProdId, $pProdName, $pVariety,
        $pTrayId, $pTrayCode, $pQty, $pActualCount, $pFromLocId, $pFromLocName,
        $pPolyId, $pPolyName, $pBedNo, $openSaleLocName, $targetLocId,
        $pRemark, $pCreatedBy
    );

    if (!$osStmt->execute()) {
        $err = $osStmt->error;
        $osStmt->close();
        return ['success' => false, 'error' => "Execute opensale stocks failed: " . $err];
    }
    $osStmt->close();

    return [
        'success'          => true,
        'polyhouseplantid' => $sourcePlantId
    ];
}

// Action to update/decrease actual plants in a tray
if ($action === 'update_tray_plant_count') {
    $barcode = trim($_REQUEST['barcode'] ?? '');
    $polyhouseplantid = (int)($_REQUEST['polyhouseplantid'] ?? 0);
    $newPlantCount = isset($_REQUEST['new_plant_count']) ? (int)$_REQUEST['new_plant_count'] : -1;

    if (empty($barcode) && $polyhouseplantid <= 0) {
        outJson(['status' => 'error', 'message' => 'Barcode or Polyhouse Plant ID is required.']);
    }

    if ($newPlantCount < 0) {
        outJson(['status' => 'error', 'message' => 'Plant count cannot be negative.']);
    }

    // Fetch current record
    $whereSql = $polyhouseplantid > 0 ? "polyhouseplantid = ?" : "barcode = ?";
    $paramType = $polyhouseplantid > 0 ? "i" : "s";
    $paramVal = $polyhouseplantid > 0 ? $polyhouseplantid : $barcode;

    $stmt = $krfconn->prepare("SELECT polyhouseplantid, barcode, qtypertray, failurecount, dispatchqty, status, productname FROM krf_polyhouse_plant_details WHERE $whereSql LIMIT 1");
    if (!$stmt) {
        outJson(['status' => 'error', 'message' => 'Database error: ' . $krfconn->error]);
    }
    $stmt->bind_param($paramType, $paramVal);
    $stmt->execute();
    $tray = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$tray) {
        outJson(['status' => 'error', 'message' => 'Tray record not found.']);
    }

    $currentActual = calculateActualPlantInTray($tray);
    $qtypertray = (int)($tray['qtypertray'] ?? 0);
    $dispatchqty = (int)($tray['dispatchqty'] ?? 0);

    // Rule: User can decrease, NOT increase the plant count
    if ($newPlantCount > $currentActual) {
        outJson([
            'status' => 'error',
            'message' => "Plant count can only be decreased. Current actual plant count is {$currentActual}, you entered {$newPlantCount}."
        ]);
    }

    // Calculate new failurecount:
    // formula: actual_plant = qtypertray - failurecount - dispatchqty
    // => failurecount = qtypertray - dispatchqty - new_plant_count
    $newFailureCount = max(0, $qtypertray - $dispatchqty - $newPlantCount);

    $updStmt = $krfconn->prepare("
        UPDATE krf_polyhouse_plant_details 
        SET failurecount = ?,
            failmarked_by = ?,
            failmarked_at = CURDATE(),
            updated_at = NOW(),
            updated_by = ?
        WHERE polyhouseplantid = ?
    ");
    if (!$updStmt) {
        outJson(['status' => 'error', 'message' => 'Update prepare error: ' . $krfconn->error]);
    }
    $updStmt->bind_param('issi', $newFailureCount, $loginname, $loginname, $tray['polyhouseplantid']);
    $res = $updStmt->execute();
    $updStmt->close();

    if ($res) {
        $tray['failurecount'] = $newFailureCount;
        $updatedActual = calculateActualPlantInTray($tray);

        outJson([
            'status' => 'success',
            'message' => "Plant count successfully updated to {$updatedActual} plants for tray {$tray['barcode']}.",
            'data' => [
                'polyhouseplantid'   => (int)$tray['polyhouseplantid'],
                'barcode'            => $tray['barcode'],
                'qtypertray'         => $qtypertray,
                'failurecount'       => $newFailureCount,
                'dispatchqty'        => $dispatchqty,
                'actual_plant_count' => $updatedActual
            ]
        ]);
    } else {
        outJson(['status' => 'error', 'message' => 'Failed to update plant count.']);
    }
}

// Action to get polyhouses list on demand
if ($action === 'get_polyhouses') {
    outJson(['status' => 'success', 'data' => getPolyhousesList($krfconn, $projectid)]);
}

// Action to get opensale locations list on demand
if ($action === 'get_opensale_locations') {
    outJson(['status' => 'success', 'data' => getOpenSaleLocationsList($krfconn, $projectid)]);
}

// Action to get beds for a specific polyhouse on demand
if ($action === 'get_polyhouse_beds') {
    $phId = trim($_REQUEST['polyhouseid'] ?? '');
    outJson(['status' => 'success', 'beds' => getBedsForPolyhouse($krfconn, $projectid, $phId)]);
}

// Action to check if product is Grafted in product_master
if ($action === 'check_product_grafted') {
    $prodId = trim($_REQUEST['productid'] ?? '');
    $isG = false;
    if (!empty($prodId)) {
        $pmStmt = $krfconn->prepare("SELECT ProductID, ProductName, IsGrafted, sku FROM product_master WHERE ProductID = ? LIMIT 1");
        if ($pmStmt) {
            $pmStmt->bind_param("s", $prodId);
            $pmStmt->execute();
            $pmRow = $pmStmt->get_result()->fetch_assoc();
            $pmStmt->close();
            if ($pmRow) {
                $isGVal = strtolower(trim($pmRow['IsGrafted'] ?? ''));
                $pName = strtolower(trim($pmRow['ProductName'] ?? ''));
                $skuVal = strtolower(trim($pmRow['sku'] ?? ''));
                if (strpos($isGVal, 'nongraft') !== false || strpos($pName, 'nongraft') !== false || strpos($skuVal, 'nongraft') !== false) {
                    $isG = false;
                } else if ($isGVal === 'grafted' || $isGVal === 'yes' || $isGVal === '1' || (strpos($pName, 'graft') !== false && strpos($pName, 'non') === false)) {
                    $isG = true;
                }
            }
        }
    }
    outJson(['status' => 'success', 'is_grafted' => $isG]);
}

// -------------------------------------------------------------
// ACTION: GET PENDING OPEN SALE TRAYS (MISSING OPEN SALE LOCATION ROW)
// -------------------------------------------------------------
if ($action === 'get_pending_opensale_trays') {
    // 1. Fetch Open Sale centers from master
    $osPhList = getOpenSaleLocationsList($krfconn, $projectid);
    $osPhIds = array_column($osPhList, 'polyhouseid');
    if (empty($osPhIds)) {
        outJson([
            'status' => 'success',
            'data' => [
                'trays' => [],
                'orders' => [],
                'products' => [],
                'opensale_locations' => [],
                'total_count' => 0
            ]
        ]);
    }
    $osPhIdStr = implode(',', array_map('intval', $osPhIds));

    // Optional filters
    $filterSo   = trim($_REQUEST['stockorder_no'] ?? '');
    $filterProd = trim($_REQUEST['productid'] ?? '');
    $search     = trim($_REQUEST['search'] ?? '');

    $whereConds = [
        "p.status = 'Move for Opensale'",
        "p.polyhouseid NOT IN ({$osPhIdStr})",
        "NOT EXISTS (
            SELECT 1 FROM krf_polyhouse_plant_details p2 
            WHERE p2.barcode = p.barcode 
              AND p2.polyhouseid IN ({$osPhIdStr})
        )"
    ];

    if (!empty($filterSo)) {
        $soEsc = $krfconn->real_escape_string($filterSo);
        $whereConds[] = "(p.stockorder_no = '{$soEsc}' OR p.psono = '{$soEsc}')";
    }
    if (!empty($filterProd)) {
        $prodEsc = $krfconn->real_escape_string($filterProd);
        $whereConds[] = "p.productid = '{$prodEsc}'";
    }
    if (!empty($search)) {
        $sEsc = $krfconn->real_escape_string($search);
        $whereConds[] = "(p.barcode LIKE '%{$sEsc}%' OR p.stockorder_no LIKE '%{$sEsc}%' OR p.psono LIKE '%{$sEsc}%' OR p.productname LIKE '%{$sEsc}%')";
    }

    $whereSql = implode(' AND ', $whereConds);

    // Fetch distinct orders for filter dropdown
    $ordersQ = "SELECT DISTINCT p.stockorder_no, p.psono 
                FROM krf_polyhouse_plant_details p 
                WHERE p.status = 'Move for Opensale' 
                  AND p.polyhouseid NOT IN ({$osPhIdStr})
                  AND NOT EXISTS (
                      SELECT 1 FROM krf_polyhouse_plant_details p2 
                      WHERE p2.barcode = p.barcode 
                        AND p2.polyhouseid IN ({$osPhIdStr})
                  )
                ORDER BY p.stockorder_no ASC";
    $ordRes = $krfconn->query($ordersQ);
    $orders = [];
    if ($ordRes) {
        while ($o = $ordRes->fetch_assoc()) {
            $so = trim($o['stockorder_no'] ?: $o['psono'] ?: '');
            if (!empty($so) && !in_array($so, $orders)) {
                $orders[] = $so;
            }
        }
    }

    // Fetch distinct products for filter dropdown
    $prodsQ = "SELECT DISTINCT p.productid, p.productname, p.variety 
               FROM krf_polyhouse_plant_details p 
               WHERE p.status = 'Move for Opensale' 
                 AND p.polyhouseid NOT IN ({$osPhIdStr})
                 AND NOT EXISTS (
                     SELECT 1 FROM krf_polyhouse_plant_details p2 
                     WHERE p2.barcode = p.barcode 
                       AND p2.polyhouseid IN ({$osPhIdStr})
                 )
               ORDER BY p.productname ASC";
    $pRes = $krfconn->query($prodsQ);
    $products = [];
    if ($pRes) {
        while ($pr = $pRes->fetch_assoc()) {
            $pId = trim($pr['productid'] ?? '');
            $pName = trim($pr['productname'] ?? '');
            if (!empty($pName)) {
                $products[] = [
                    'productid'   => $pId,
                    'productname' => $pName,
                    'variety'     => trim($pr['variety'] ?? '')
                ];
            }
        }
    }

    // Fetch pending trays
    $sql = "
        SELECT p.polyhouseplantid, p.barcode, p.stockorder_no, p.psono, p.productid, p.productname, p.variety,
               p.polyhouseid, p.bed_no, p.locationid, p.qtypertray, p.failurecount, p.dispatchqty,
               p.status, p.workingstatus, p.move_at, p.movementremark, p.created_at, p.updated_at,
               pm.polyhousename,
               s.open_sale_location, s.id AS opensale_stock_id, s.actual_plant_count AS stock_plant_count
        FROM krf_polyhouse_plant_details p
        LEFT JOIN krf_polyhouse_master pm ON pm.polyhouseid = p.polyhouseid
        LEFT JOIN krf_opensale_stocks s ON s.barcode = p.barcode
        WHERE {$whereSql}
        ORDER BY p.polyhouseplantid DESC
    ";
    $res = $krfconn->query($sql);
    $trays = [];
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $r['actual_plant_count'] = calculateActualPlantInTray($r);
            $r['polyhousename'] = $r['polyhousename'] ?: ('PH-' . $r['polyhouseid']);
            $trays[] = $r;
        }
    }

    outJson([
        'status' => 'success',
        'data' => [
            'trays'              => $trays,
            'orders'             => $orders,
            'products'           => $products,
            'opensale_locations' => $osPhList,
            'total_count'        => count($trays)
        ]
    ]);
}

// -------------------------------------------------------------
// ACTION: SYNC PREVIOUS OPEN SALE TRAYS INTO OPEN SALE LOCATION
// -------------------------------------------------------------
if ($action === 'sync_previous_opensale_trays') {
    $target_opensale_polyhouseid = trim($_POST['target_opensale_polyhouseid'] ?? '');
    $custom_narration = trim($_POST['custom_narration'] ?? '');

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

    if (empty($selected_plant_ids) && empty($selected_barcodes)) {
        outJson(['status' => 'error', 'message' => 'Please select at least 1 tray to sync.']);
    }
    if (empty($target_opensale_polyhouseid)) {
        outJson(['status' => 'error', 'message' => 'Please select destination Which Open Sale Location.']);
    }

    // Resolve Open Sale Location
    $phEsc = $krfconn->real_escape_string($target_opensale_polyhouseid);
    $q = $krfconn->query("SELECT polyhouseid, polyhousename, locationid FROM krf_polyhouse_master WHERE polyhouseid = '{$phEsc}' AND (type = 'OpenSale' OR structure_type = 'OpenSale' OR polyhousename LIKE '%Open%Sale%') LIMIT 1");
    if (!$q || $q->num_rows === 0) {
        outJson(['status' => 'error', 'message' => "Invalid Open Sale location selected: '{$target_opensale_polyhouseid}'."]);
    }
    $osPhRow = $q->fetch_assoc();
    $target_opensale_locationid = (string)$osPhRow['locationid'];
    $open_sale_location = (string)$osPhRow['polyhousename'];

    // Find source tray rows
    $whereConds = [];
    if (!empty($selected_plant_ids)) {
        $idListStr = implode(',', array_map('intval', $selected_plant_ids));
        $whereConds[] = "p.polyhouseplantid IN ({$idListStr})";
    }
    if (!empty($selected_barcodes)) {
        $bcEscList = "'" . implode("','", array_map([$krfconn, 'real_escape_string'], $selected_barcodes)) . "'";
        $whereConds[] = "p.barcode IN ({$bcEscList})";
    }
    $whereSql = implode(' OR ', $whereConds);

    $sql = "SELECT p.*, pm.polyhousename 
            FROM krf_polyhouse_plant_details p 
            LEFT JOIN krf_polyhouse_master pm ON pm.polyhouseid = p.polyhouseid 
            WHERE ({$whereSql}) AND p.status = 'Move for Opensale'";
    $res = $krfconn->query($sql);
    if (!$res || $res->num_rows === 0) {
        outJson(['status' => 'error', 'message' => 'No eligible pending trays found for the selection.']);
    }

    $krfconn->begin_transaction();
    try {
        $nowDt = date('d-M-Y h:i A');
        $curDate = date('Y-m-d');
        $remarkLine = "[Synced to Open Sale: {$open_sale_location} by {$loginname} on {$nowDt}" . (!empty($custom_narration) ? " | Note: " . $custom_narration : "") . "]";
        $syncedCount = 0;

        while ($pRow = $res->fetch_assoc()) {
            $origInfo = [
                'locationid'   => $pRow['locationid'],
                'locationname' => $pRow['locationid'],
                'polyhouseid'  => $pRow['polyhouseid'],
                'polyhousename'=> $pRow['polyhousename'] ?: ('PH-' . $pRow['polyhouseid']),
                'bed_no'       => $pRow['bed_no']
            ];

            $syncRes = insertOrSyncOpenSalePlantRow(
                $krfconn,
                $pRow,
                $target_opensale_polyhouseid,
                $target_opensale_locationid,
                $open_sale_location,
                $remarkLine,
                $loginname,
                $curDate,
                $origInfo
            );

            if (!$syncRes['success']) {
                throw new Exception("Error syncing tray {$pRow['barcode']}: " . ($syncRes['error'] ?? ''));
            }
            $syncedCount++;
        }

        $krfconn->commit();

        outJson([
            'status'             => 'success',
            'message'            => "Successfully inserted Open Sale row for {$syncedCount} tray(s) into {$open_sale_location}!",
            'synced_count'       => $syncedCount,
            'open_sale_location' => $open_sale_location
        ]);
    } catch (Exception $e) {
        $krfconn->rollback();
        outJson(['status' => 'error', 'message' => $e->getMessage()]);
    }
}


// -------------------------------------------------------------
// ACTION 1: SCAN TRAY BARCODE (STRICT TRAY BARCODE SCANNING)
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

    // Order IDs are strictly blocked on this page: Only Tray Barcodes are supported
    $isOrderQuery = (bool)preg_match('/^(RSO|OS|ORD|PSO|PO|SO|KFP|KF)[-\s]?/i', $cleanedQuery);
    if ($isOrderQuery) {
        outJson([
            'status' => 'error',
            'message' => 'Order ID "' . htmlspecialchars($cleanedQuery) . '" cannot be scanned or searched on this page. This page only accepts individual Tray Barcodes. Please use the "Plant Movement QR Scanner" page to scan Order IDs.'
        ]);
    }

    // Query matches physical Tray Barcode
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
        ORDER BY (p.status IN ('Readytosale', 'OpenStock','Polyhouse')) DESC, p.polyhouseplantid DESC
        LIMIT 1
    ";
    $bstmt = $krfconn->prepare($barcodeSql);
    $found = null;
    if ($bstmt) {
        $bstmt->bind_param('iis', $projectid, $projectid, $cleanedQuery);
        $bstmt->execute();
        $found = $bstmt->get_result()->fetch_assoc();
        $bstmt->close();
    }

    if (!$found) {
        outJson([
            'status' => 'error',
            'message' => 'No plant record found matching Tray Barcode: "' . htmlspecialchars($rawQuery) . '". Only individual Tray Barcodes are supported on this page.'
        ]);
    }
    $currStatus = trim($found['status'] ?? '');
    $currStatusClean = strtolower(str_replace(' ', '', $currStatus));
    // Status eligible for movement: ONLY 'Polyhouse' or 'Readytosale' (or empty/Polyhouse)
    $isAllowedStatus = in_array($currStatusClean, ['polyhouse', 'readytosale', '']);

    $targetBedNo    = trim($found['bed_no'] ?? '');
    $targetLocId    = trim($found['locationid'] ?? '');
    $targetPhId     = trim($found['polyhouseid'] ?? '');
    $targetSoNo     = trim($found['stockorder_no'] ?? '');
    $targetPsoNo    = trim($found['psono'] ?? '');
    $targetProdId   = trim($found['productid'] ?? '');
    $targetTrayId   = (int)($found['tray_id'] ?? 0);

    $isScannedTrayActive = $isAllowedStatus;
    $scannedTrayStatusWarning = '';

    // If scanned tray itself does not have status 'Polyhouse' or 'Readytosale', check if any eligible trays remain in this bed
    if (!$isScannedTrayActive) {
        $chkActiveSql = "
            SELECT COUNT(*) AS active_cnt 
            FROM krf_polyhouse_plant_details 
            WHERE (projectid = ? OR ? = 0 OR projectid = 17)
              AND bed_no = ?
              AND (locationid = ? OR ? = '')
              AND (polyhouseid = ? OR ? = '')
              AND (stockorder_no = ? OR ? = '')
              AND (productid = ? OR ? = '')
              AND (LOWER(REPLACE(TRIM(status), ' ', '')) IN ('polyhouse', 'readytosale') OR status = '' OR status IS NULL)
        ";
        $chkStmt = $krfconn->prepare($chkActiveSql);
        $activeInBedCnt = 0;
        if ($chkStmt) {
            $chkStmt->bind_param('iisssssssss',
                $projectid, $projectid,
                $targetBedNo,
                $targetLocId, $targetLocId,
                $targetPhId, $targetPhId,
                $targetSoNo, $targetSoNo,
                $targetProdId, $targetProdId
            );
            $chkStmt->execute();
            $chkRes = $chkStmt->get_result()->fetch_assoc();
            $activeInBedCnt = (int)($chkRes['active_cnt'] ?? 0);
            $chkStmt->close();
        }

        if ($activeInBedCnt <= 0) {
            outJson([
                'status' => 'already_moved',
                'is_terminal' => true,
                'message' => "Scanned tray ({$found['barcode']}) has status \"{$currStatus}\". Only trays with status 'Polyhouse' or 'Ready to Sale' can be moved, and no eligible trays remain in {$targetBedNo}.",
                'data' => [
                    'barcode' => $found['barcode'],
                    'status' => $currStatus,
                    'movement_reason' => $currStatus,
                    'bed_no' => $targetBedNo,
                    'stockorder_no' => $targetSoNo,
                    'productname' => $found['productname'],
                    'variety' => $found['variety'] ?? '',
                    'movementremark' => $found['movementremark'] ?: '',
                    'move_at' => $found['move_at'] ?: '',
                    'updated_at' => $found['updated_at'] ?: ''
                ]
            ]);
        } else {
            $scannedTrayStatusWarning = "Scanned tray ({$found['barcode']}) has status \"{$currStatus}\". Only trays with status 'Polyhouse' or 'Ready to Sale' can be moved. Showing {$activeInBedCnt} eligible tray(s) in {$targetBedNo} for movement.";
        }
    }

    // 2. Fetch occupied trays and status breakdown in this bed & stock order/product (ONLY Polyhouse or Ready to Sale)
    $availCount = 0;
    $availReadyTrays = 0;
    $availOpenTrays = 0;
    $polyhouseGrowthTrays = 0;
    $occupiedTrays = 0;
    $otherStatusCount = 0;

    $cntSql = "
        SELECT 
            status,
            growth_stage,
            COUNT(*) as tray_count
        FROM krf_polyhouse_plant_details
        WHERE (projectid = ? OR ? = 0 OR projectid = 17)
          AND bed_no = ?
          AND (locationid = ? OR ? = '')
          AND (polyhouseid = ? OR ? = '')
          AND (stockorder_no = ? OR ? = '')
          AND (productid = ? OR ? = '')
          AND (LOWER(REPLACE(TRIM(status), ' ', '')) IN ('polyhouse', 'readytosale') OR status = '' OR status IS NULL)
        GROUP BY status, growth_stage
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
            $st = trim($cr['status'] ?? '');
            $tc = (int)($cr['tray_count'] ?? 0);
            $stClean = strtolower(str_replace(' ', '', $st));

            if ($stClean === 'readytosale') {
                $availReadyTrays += $tc;
                $availCount += $tc;
                $occupiedTrays += $tc;
            } elseif ($stClean === 'polyhouse' || empty($st)) {
                $polyhouseGrowthTrays += $tc;
                $availCount += $tc;
                $occupiedTrays += $tc;
            }
        }
        $cstmt->close();
    }

    if ($occupiedTrays <= 0) {
        outJson([
            'status' => 'warning_status',
            'message' => 'Plant found in ' . ($targetBedNo ?: 'Bed') . ' with status "' . $currStatus . '", but no trays with status "Polyhouse" or "Ready to Sale" are available for movement in this bed.',
            'current_status' => $currStatus,
            'bed_no' => $targetBedNo,
            'stockorder_no' => $targetSoNo,
            'productname' => $found['productname']
        ]);
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

    // 6. Fetch all active trays in this bed for the order (with checkboxes for movement)
    $activeTraysList = [];
    $scannedBcode = $found['barcode'] ?: $cleanedQuery;
    $activeSql = "
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
            p.dispatchdate_internal
        FROM krf_polyhouse_plant_details p
        WHERE (p.projectid = ? OR ? = 0 OR p.projectid = 17)
          AND p.bed_no = ?
          AND (p.locationid = ? OR ? = '')
          AND (p.polyhouseid = ? OR ? = '')
          AND (p.stockorder_no = ? OR ? = '')
          AND (p.productid = ? OR ? = '')
          AND (LOWER(REPLACE(TRIM(p.status), ' ', '')) IN ('polyhouse', 'readytosale') OR p.status = '' OR p.status IS NULL)
        ORDER BY (p.barcode = ?) DESC, p.polyhouseplantid ASC
    ";
    $astmt = $krfconn->prepare($activeSql);
    if ($astmt) {
        $astmt->bind_param('iissssssssss', 
            $projectid, $projectid,
            $targetBedNo, 
            $targetLocId, $targetLocId, 
            $targetPhId, $targetPhId, 
            $targetSoNo, $targetSoNo, 
            $targetProdId, $targetProdId,
            $scannedBcode
        );
        $astmt->execute();
        $ares = $astmt->get_result();
        while ($ar = $ares->fetch_assoc()) {
            $arStatus = trim($ar['status'] ?? 'Polyhouse');
            $arReady = (strcasecmp($arStatus, 'Readytosale') === 0 || strcasecmp($ar['growth_stage'] ?? '', 'ReadyToSale') === 0);
            $arPlants = calculateActualPlantInTray($ar);
            $isThisScanned = ($ar['barcode'] === $scannedBcode);
            $activeTraysList[] = [
                'polyhouseplantid'   => (int)$ar['polyhouseplantid'],
                'barcode'            => $ar['barcode'],
                'bed_no'             => $ar['bed_no'],
                'locationid'         => $ar['locationid'],
                'locationname'       => $locationName,
                'polyhouseid'        => $ar['polyhouseid'],
                'polyhousename'      => $polyhouseName,
                'stockorder_no'      => $ar['stockorder_no'],
                'psono'              => $ar['psono'],
                'productid'          => $ar['productid'],
                'productname'        => $ar['productname'],
                'variety'            => $ar['variety'] ?: '-',
                'seed_lot_no'        => $ar['seed_lot_no'] ?: '-',
                'tray_id'            => (int)($ar['tray_id'] ?? 0),
                'tray_name'          => $ar['tray_code'] ?: 'Standard Tray',
                'qtypertray'         => (int)($ar['qtypertray'] ?: 126),
                'failurecount'       => (int)($ar['failurecount'] ?? 0),
                'dispatchqty'        => (int)($ar['dispatchqty'] ?? 0),
                'actual_plant_count' => $arPlants,
                'status'             => $arStatus,
                'is_ready_to_sale'   => $arReady,
                'growth_stage'       => $ar['growth_stage'] ?: '-',
                'health_status'      => $ar['health_status'] ?: 'Healthy',
                'sowing_date'        => !empty($ar['sowing_date']) ? date('d-M-Y', strtotime($ar['sowing_date'])) : 'N/A',
                'dispatch_date'      => !empty($ar['dispatchdate_internal']) ? date('d-M-Y', strtotime($ar['dispatchdate_internal'])) : 'N/A',
                'is_scanned'         => $isThisScanned,
                'is_checked'         => ($isThisScanned && $isScannedTrayActive)
            ];
        }
        $astmt->close();
    }

    // If activeTraysList is empty but found record exists and is active, include found
    if (empty($activeTraysList) && $isScannedTrayActive) {
        $activeTraysList[] = [
            'polyhouseplantid'   => (int)$found['polyhouseplantid'],
            'barcode'            => $found['barcode'],
            'bed_no'             => $targetBedNo,
            'locationid'         => $targetLocId,
            'locationname'       => $locationName,
            'polyhouseid'        => $targetPhId,
            'polyhousename'      => $polyhouseName,
            'stockorder_no'      => $targetSoNo,
            'psono'              => $targetPsoNo,
            'productid'          => $targetProdId,
            'productname'        => $found['productname'],
            'variety'            => $found['variety'] ?: '-',
            'seed_lot_no'        => $found['seed_lot_no'] ?: '-',
            'tray_id'            => $targetTrayId,
            'tray_name'          => $trayName,
            'qtypertray'         => $qtyPerTrayVal,
            'failurecount'       => (int)($found['failurecount'] ?? 0),
            'dispatchqty'        => (int)($found['dispatchqty'] ?? 0),
            'actual_plant_count' => $actualPlantCount,
            'status'             => $trayStatus,
            'is_ready_to_sale'   => $isReadyToSale,
            'growth_stage'       => $found['growth_stage'] ?: '-',
            'health_status'      => $found['health_status'] ?: 'Healthy',
            'sowing_date'        => !empty($found['sowing_date']) ? date('d-M-Y', strtotime($found['sowing_date'])) : 'N/A',
            'dispatch_date'      => !empty($found['dispatchdate_internal']) ? date('d-M-Y', strtotime($found['dispatchdate_internal'])) : 'N/A',
            'is_scanned'         => true,
            'is_checked'         => true
        ];
    }

    $activeTraysCount = count($activeTraysList);
    if ($activeTraysCount > 0) {
        $occupiedTrays = $activeTraysCount;
    }

    // Check from product_master if scanned trays' productid is for a Grafted product
    $isProductGrafted = false;
    $lookupProdId = !empty($targetProdId) ? $targetProdId : (!empty($activeTraysList[0]['productid']) ? $activeTraysList[0]['productid'] : '');
    if (!empty($lookupProdId)) {
        $pmStmt = $krfconn->prepare("SELECT ProductID, ProductName, IsGrafted, sku FROM product_master WHERE ProductID = ? LIMIT 1");
        if ($pmStmt) {
            $pmStmt->bind_param("s", $lookupProdId);
            $pmStmt->execute();
            $pmRow = $pmStmt->get_result()->fetch_assoc();
            $pmStmt->close();
            if ($pmRow) {
                $isGVal = strtolower(trim($pmRow['IsGrafted'] ?? ''));
                $pName = strtolower(trim($pmRow['ProductName'] ?? ''));
                $skuVal = strtolower(trim($pmRow['sku'] ?? ''));
                if (strpos($isGVal, 'nongraft') !== false || strpos($pName, 'nongraft') !== false || strpos($skuVal, 'nongraft') !== false) {
                    $isProductGrafted = false;
                } else if ($isGVal === 'grafted' || $isGVal === 'yes' || $isGVal === '1' || (strpos($pName, 'graft') !== false && strpos($pName, 'non') === false)) {
                    $isProductGrafted = true;
                }
            }
        }
    }

    // Attach is_grafted to each tray in activeTraysList
    foreach ($activeTraysList as &$atItem) {
        $atItem['is_grafted'] = $isProductGrafted;
    }
    unset($atItem);

    outJson([
        'status' => 'success',
        'scanned_tray_status_warning' => $scannedTrayStatusWarning,
        'data' => [
            'is_grafted' => $isProductGrafted,
            'scanned_tray' => [
                'polyhouseplantid'     => (int)$found['polyhouseplantid'],
                'barcode'              => $scannedBcode,
                'bed_no'               => $targetBedNo ?: 'Bed-N/A',
                'locationid'           => $targetLocId,
                'locationname'         => $locationName,
                'polyhouseid'          => $targetPhId,
                'polyhousename'        => $polyhouseName,
                'stockorder_no'        => $targetSoNo ?: 'N/A',
                'psono'                => $targetPsoNo ?: 'N/A',
                'productid'            => $targetProdId,
                'productname'          => $found['productname'] ?: 'Plant Product',
                'is_grafted'           => $isProductGrafted,
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
                'is_active'            => $isScannedTrayActive,
                'is_ready_to_sale'     => $isReadyToSale,
                'growth_stage'         => $found['growth_stage'] ?: '-',
                'health_status'        => $found['health_status'] ?: 'Healthy',
                'sowing_date'          => !empty($found['sowing_date']) ? date('d-M-Y', strtotime($found['sowing_date'])) : 'N/A',
                'dispatch_date'        => !empty($found['dispatchdate_internal']) ? date('d-M-Y', strtotime($found['dispatchdate_internal'])) : 'N/A',
                'movementremark'       => $found['movementremark'] ?: '',
                'move_at'              => $found['move_at'] ?: '',
                'updated_at'           => $found['updated_at'] ?: ''
            ],
            'is_grafted'           => $isProductGrafted,
            'polyhouseplantid'     => (int)$found['polyhouseplantid'],
            'barcode'              => $scannedBcode,
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
            'is_active'            => $isScannedTrayActive,
            'is_ready_to_sale'     => $isReadyToSale,
            'growth_stage'         => $found['growth_stage'] ?: '-',
            'health_status'        => $found['health_status'] ?: 'Healthy',
            'sowing_date'          => !empty($found['sowing_date']) ? date('d-M-Y', strtotime($found['sowing_date'])) : 'N/A',
            'dispatch_date'        => !empty($found['dispatchdate_internal']) ? date('d-M-Y', strtotime($found['dispatchdate_internal'])) : 'N/A',
            'occupied_trays'       => $occupiedTrays,
            'active_trays_count'   => $activeTraysCount,
            'ready_to_sale_trays'  => $availReadyTrays,
            'polyhouse_trays'      => $polyhouseGrowthTrays,
            'available_trays'      => $availCount,
            'available_ready_trays'=> $availReadyTrays,
            'available_open_trays' => $availOpenTrays,
            'available_plants'     => ($occupiedTrays * $actualPlantCount),
            'movementremark'       => $found['movementremark'] ?: '',
            'move_at'              => $found['move_at'] ?: '',
            'updated_at'           => $found['updated_at'] ?: '',
            'polyhouse_beds'       => getBedsForPolyhouse($krfconn, $projectid, $targetPhId),
            'all_polyhouses'       => getPolyhousesList($krfconn, $projectid),
            'opensale_locations'   => getOpenSaleLocationsList($krfconn, $projectid),
            'active_trays_list'    => $activeTraysList
        ]
    ]);
}


if ($action === 'mark_ready_to_sale') {
    $mode          = trim($_POST['mode'] ?? '');
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

    if ($mode === 'scanned_only' && empty($selected_plant_ids) && empty($selected_barcodes)) {
        outJson(['status' => 'error', 'message' => 'Please select at least 1 tray using checkboxes to mark as Ready to Sale.']);
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
              AND (LOWER(REPLACE(TRIM(status), ' ', '')) IN ('polyhouse', 'readytosale') OR status = '' OR status IS NULL)
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
              AND (LOWER(REPLACE(TRIM(status), ' ', '')) IN ('polyhouse', 'readytosale') OR status = '' OR status IS NULL)
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
              AND (LOWER(REPLACE(TRIM(status), ' ', '')) IN ('polyhouse', 'readytosale') OR status = '' OR status IS NULL)
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

    // Selected trays from checkboxes
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
    $hasSelectedTrays = (!empty($selected_plant_ids) || !empty($selected_barcodes));

    // Target bed / polyhouse parameters
    $target_bed_no               = trim($_POST['target_bed_no'] ?? $_POST['target_polyhouse_bed_no'] ?? '');
    $target_polyhouseid          = trim($_POST['target_polyhouseid'] ?? '');
    $open_sale_location          = trim($_POST['open_sale_location'] ?? '');
    $target_opensale_polyhouseid = trim($_POST['target_opensale_polyhouseid'] ?? '');
    $target_opensale_locationid  = trim($_POST['target_opensale_locationid'] ?? '');

    // Validation for Move for Opensale & resolve polyhouseid / locationid
    if ($movement_reason === 'Move for Opensale') {
        if ($mode === 'scanned_only' && !$hasSelectedTrays) {
            outJson(['status' => 'error', 'message' => 'Please select at least 1 checked tray for Move for Opensale.']);
        }
        if (empty($open_sale_location) && empty($target_opensale_polyhouseid)) {
            outJson(['status' => 'error', 'message' => 'Please select destination Open Sale Location.']);
        }

        // Strictly resolve polyhouseid and locationid from krf_polyhouse_master
        $osPhRow = null;
        if (!empty($target_opensale_polyhouseid)) {
            $phEsc = $krfconn->real_escape_string($target_opensale_polyhouseid);
            $q = $krfconn->query("SELECT polyhouseid, polyhousename, locationid FROM krf_polyhouse_master WHERE polyhouseid = '{$phEsc}' LIMIT 1");
            if ($q && $q->num_rows > 0) {
                $osPhRow = $q->fetch_assoc();
            }
        }
        if (!$osPhRow && !empty($open_sale_location)) {
            $locEsc = $krfconn->real_escape_string($open_sale_location);
            $q = $krfconn->query("SELECT polyhouseid, polyhousename, locationid FROM krf_polyhouse_master WHERE polyhousename = '{$locEsc}' LIMIT 1");
            if ($q && $q->num_rows > 0) {
                $osPhRow = $q->fetch_assoc();
            }
        }
        if (!$osPhRow && !empty($open_sale_location)) {
            $locEsc = $krfconn->real_escape_string($open_sale_location);
            $q = $krfconn->query("SELECT polyhouseid, polyhousename, locationid FROM krf_polyhouse_master WHERE polyhousename LIKE '%{$locEsc}%' AND (type = 'OpenSale' OR structure_type = 'OpenSale') LIMIT 1");
            if ($q && $q->num_rows > 0) {
                $osPhRow = $q->fetch_assoc();
            }
        }

        if ($osPhRow) {
            $target_opensale_polyhouseid = (string)$osPhRow['polyhouseid'];
            $target_opensale_locationid  = (string)$osPhRow['locationid'];
            $open_sale_location          = (string)$osPhRow['polyhousename'];
        } else {
            outJson(['status' => 'error', 'message' => "Invalid Open Sale location selected: '{$open_sale_location}'. Master record not found."]);
        }
    }

    // Validation for Bed Shifting
    if ($movement_reason === 'Bed Shifting') {
        if ($mode === 'scanned_only' && !$hasSelectedTrays) {
            outJson(['status' => 'error', 'message' => 'Please select at least 1 checked tray for Bed Shifting.']);
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
        if ($mode === 'scanned_only' && !$hasSelectedTrays) {
            outJson(['status' => 'error', 'message' => 'Please select at least 1 checked tray for Polyhouse Shifting.']);
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
        if ($mode === 'scanned_only' && !$hasSelectedTrays) {
            outJson(['status' => 'error', 'message' => 'Please select at least 1 checked tray for Hardening.']);
        }
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
        $statusClause = "AND (LOWER(REPLACE(TRIM(p.status), ' ', '')) IN ('polyhouse', 'readytosale') OR p.status = '' OR p.status IS NULL)";
        $fetchSql = "SELECT p.polyhouseplantid, p.barcode, p.status, p.productname, p.qtypertray, p.bed_no, p.polyhouseid, p.locationid, p.stockorder_no, p.psono
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
        $statusClause = "AND (LOWER(REPLACE(TRIM(p.status), ' ', '')) IN ('polyhouse', 'readytosale') OR p.status = '' OR p.status IS NULL)";
        $fetchSql = "SELECT p.polyhouseplantid, p.barcode, p.status, p.productname, p.qtypertray, p.bed_no, p.polyhouseid, p.locationid, p.stockorder_no, p.psono
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
        // Only trays with status 'Polyhouse' or 'Ready to Sale' are eligible for movement
        $statusFilter = "(LOWER(REPLACE(TRIM(p.status), ' ', '')) IN ('polyhouse', 'readytosale') OR p.status = '' OR p.status IS NULL)";

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
        $fetchSql = "SELECT p.polyhouseplantid, p.barcode, p.status, p.productname, p.qtypertray, p.bed_no, p.polyhouseid, p.locationid, p.stockorder_no, p.psono
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
        outJson([
            'status' => 'error',
            'message' => "No trays with status 'Polyhouse' or 'Ready to Sale' found in " . ($bed_no ?: 'selected scope') . " to move."
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
    } elseif ($movement_reason === 'Move for Opensale') {
        $remarkLine = "[Moved to Open Sale: {$open_sale_location} | {$trays_to_move} tray(s) by {$loginname} on {$nowDt}" . (!empty($custom_narration) ? " | Note: " . $custom_narration : "") . "]";
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

            // Note: For "Move for Opensale", only update the status column in krf_polyhouse_plant_details.
            // Do not alter polyhouseid, locationid, or bed_no.
            $osPhUpd = "";

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

            // Update krf_readytosale_stocks
            if (!empty($targetPlantIds) || !empty($targetBarcodes)) {
                $rtsConds = [];
                if (!empty($targetPlantIds)) $rtsConds[] = "polyhouseplantid IN ({$idListStr})";
                if (!empty($targetBarcodes)) {
                    $bcEscList = "'" . implode("','", array_map([$krfconn, 'real_escape_string'], $targetBarcodes)) . "'";
                    $rtsConds[] = "barcode IN ({$bcEscList})";
                }
                $rtsWhere = implode(" OR ", $rtsConds);

                $rtsLocUpd = "";
                if ($movement_reason === 'Move for Opensale' && !empty($target_opensale_locationid)) {
                    $rtsLocUpd = ", locationid = '{$krfconn->real_escape_string($target_opensale_locationid)}', locationname = '{$krfconn->real_escape_string($open_sale_location)}'";
                }

                $updRtsSql = "
                    UPDATE krf_readytosale_stocks
                    SET 
                        status = '{$reasonEsc}',
                        remarks = CONCAT(COALESCE(remarks, ''), IF(COALESCE(remarks, '') = '', '', ' | '), '{$remarkEsc}'),
                        updated_at = NOW()
                        {$rtsLocUpd}
                    WHERE ({$rtsWhere}) AND (status IN ('Readytosale', 'Ready to sale', 'ReadyToSale') OR LOWER(REPLACE(status, ' ', '')) = 'readytosale')
                ";
                $krfconn->query($updRtsSql);
            }

            // Sync to krf_opensale_stocks and insert Open Sale location row into krf_polyhouse_plant_details
            if ($movement_reason === 'Move for Opensale') {
                $origRowsById = [];
                foreach ($rowsToMove as $rOrig) {
                    $origRowsById[(int)$rOrig['polyhouseplantid']] = $rOrig;
                }

                $osPlantsSql = "SELECT p.*, pm.polyhousename 
                                FROM krf_polyhouse_plant_details p 
                                LEFT JOIN krf_polyhouse_master pm ON pm.polyhouseid = p.polyhouseid 
                                WHERE p.polyhouseplantid IN ({$idListStr})";
                $osRes = $krfconn->query($osPlantsSql);
                if ($osRes && $osRes->num_rows > 0) {
                    while ($pRow = $osRes->fetch_assoc()) {
                        $pPlantId = (int)$pRow['polyhouseplantid'];
                        $origR = $origRowsById[$pPlantId] ?? [];
                        $origInfo = [
                            'locationid'   => $origR['locationid'] ?? $pRow['locationid'] ?? '',
                            'locationname' => $origR['locationname'] ?? $origR['locationid'] ?? $pRow['locationid'] ?? '',
                            'polyhouseid'  => $origR['polyhouseid'] ?? $pRow['polyhouseid'] ?? '',
                            'polyhousename'=> $pRow['polyhousename'] ?: ('PH-' . ($origR['polyhouseid'] ?? $pRow['polyhouseid'] ?? '')),
                            'bed_no'       => $origR['bed_no'] ?? $pRow['bed_no'] ?? ''
                        ];
                        $syncRes = insertOrSyncOpenSalePlantRow(
                            $krfconn,
                            $pRow,
                            $target_opensale_polyhouseid,
                            $target_opensale_locationid,
                            $open_sale_location,
                            $remarkLine,
                            $loginname,
                            $curDate,
                            $origInfo
                        );
                        if (!$syncRes['success']) {
                            throw new Exception("Error creating open sale location row: " . ($syncRes['error'] ?? 'Unknown error'));
                        }
                    }
                }
            }

            if ($movement_reason === 'Move for Opensale') {
                $successMsg = "Successfully moved {$trays_to_move} tray(s) of " . ($rowsToMove[0]['productname'] ?? 'Plant') . " to Open Sale ({$open_sale_location})!";
            } else {
                $successMsg = "Successfully moved {$trays_to_move} tray(s) of " . ($rowsToMove[0]['productname'] ?? 'Plant') . " to '{$movement_reason}' from {$bed_no}!";
            }
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


outJson(['status' => 'error', 'message' => 'Invalid action parameter.']);
