<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Kolkata');

// -------------------------------------------------------------
// Check Session & Auth
// -------------------------------------------------------------
if (!isset($_SESSION['sessionid']) || $_SESSION['sessionid'] !== session_id()) {
    $protocol = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';
    $rooturl = $protocol . '://' . $_SERVER['HTTP_HOST'];
    $prefix = ($_SERVER['HTTP_HOST'] == "localhost") ? $rooturl . "/krf_amanaccounting/" : $rooturl . "/";
    $requested_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    echo "<script>
        try {
            if (" . json_encode($requested_url) . " && !" . json_encode($requested_url) . ".includes('loginCell') && !" . json_encode($requested_url) . ".includes('logout')) {
                localStorage.setItem('last_active_module_url', " . json_encode($requested_url) . ");
            }
        } catch(e){}
        top.location.href = '" . $prefix . "loginCell/index.php';
    </script>";
    exit;
}

// =============================================================
// AJAX HANDLERS
// =============================================================
$action = trim($_REQUEST['action'] ?? '');

if (!empty($action)) {
    require_once(__DIR__ . '/../connection.php');
    header('Content-Type: application/json; charset=utf-8');

    $loginid     = $_SESSION['loginid'] ?? 'admin';
    $companyid   = (int)($_SESSION['companyid'] ?? 1);
    $subcompanyid= (int)($_SESSION['subcompanyid'] ?? 1);
    $projectid   = (int)($_SESSION['projectid'] ?? 17);
    $loginroleid = $_SESSION['loginroleid'] ?? '1';
    $logintype   = $_SESSION['logintype'] ?? 'admin';
    $loginname   = $_SESSION['loginname'] ?? 'Admin';

    if ($projectid <= 0) $projectid = 17;

    // 1. Get Dropdown Filter Options (Locations, Polyhouses, Beds)
    if ($action === 'get_filters') {
        $locations = [];
        $locSql = "SELECT locationid, locationname FROM hrm_AttendanceLocationMaster WHERE projectid = ? ORDER BY locationname ASC";
        $locStmt = $hrmconn->prepare($locSql);
        if ($locStmt) {
            $pidStr = (string)$projectid;
            $locStmt->bind_param('s', $pidStr);
            $locStmt->execute();
            $locRes = $locStmt->get_result();
            while ($lr = $locRes->fetch_assoc()) {
                $locations[] = ['id' => $lr['locationid'], 'name' => $lr['locationname']];
            }
            $locStmt->close();
        }

        $polyhouses = [];
        $phSql = "SELECT polyhouseid, polyhousename, locationid, beddetails FROM krf_polyhouse_master WHERE projectid = ? ORDER BY polyhousename ASC";
        $phStmt = $krfconn->prepare($phSql);
        if ($phStmt) {
            $phStmt->bind_param('i', $projectid);
            $phStmt->execute();
            $phRes = $phStmt->get_result();
            while ($pr = $phRes->fetch_assoc()) {
                $beds = [];
                $decoded = json_decode($pr['beddetails'] ?? '[]', true);
                if (is_array($decoded)) {
                    foreach ($decoded as $b) {
                        $bname = trim($b['bedname'] ?? '');
                        if ($bname !== '') $beds[] = $bname;
                    }
                }
                $polyhouses[] = [
                    'id' => $pr['polyhouseid'],
                    'name' => $pr['polyhousename'],
                    'locationid' => $pr['locationid'],
                    'beds' => $beds
                ];
            }
            $phStmt->close();
        }

        echo json_encode([
            'status' => 'success',
            'locations' => $locations,
            'polyhouses' => $polyhouses
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2. Scan / Search Lookup Handler
    if ($action === 'scan_lookup') {
        $queryRaw = trim($_REQUEST['query'] ?? '');
        $filterLoc = trim($_REQUEST['locationid'] ?? '');
        $filterPh = (int)($_REQUEST['polyhouseid'] ?? 0);
        $filterBed = trim($_REQUEST['bed_no'] ?? '');

        if ($queryRaw === '' && $filterBed === '') {
            echo json_encode(['status' => 'error', 'message' => 'Please enter or scan a barcode, QR code, Order No, or select a bed.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Token parsing if the QR code is a multi-line formatted slip
        $parsedBill = '';
        $parsedBed = '';
        $parsedProd = '';
        $parsedLot = '';

        if (strpos($queryRaw, "\n") !== false || strpos($queryRaw, ":") !== false) {
            $lines = explode("\n", $queryRaw);
            foreach ($lines as $line) {
                $line = trim($line);
                if (stripos($line, 'Bill No:') === 0 || stripos($line, 'Bill NO:') === 0) {
                    $parsedBill = trim(substr($line, 8));
                } elseif (stripos($line, 'Bed:') === 0) {
                    $parsedBed = trim(substr($line, 4));
                } elseif (stripos($line, 'Product:') === 0 || stripos($line, 'Product Name:') === 0) {
                    $colonPos = strpos($line, ':');
                    $parsedProd = trim(substr($line, $colonPos + 1));
                } elseif (stripos($line, 'Seed Lot No:') === 0 || stripos($line, 'Seed Lot NO:') === 0) {
                    $parsedLot = trim(substr($line, 12));
                }
            }
        }

        $cleanedQuery = trim($queryRaw);
        $cleanedQuery = trim($cleanedQuery, " \t\n\r\0\x0B,'\"");

        $searchTerms = array_filter(array_unique([
            $cleanedQuery,
            $parsedBill,
            $parsedBed,
            $parsedLot,
            $filterBed
        ]));

        $whereMatches = [];
        $params = [];
        $types = '';

        foreach ($searchTerms as $term) {
            if (empty($term)) continue;
            $whereMatches[] = "p.bedqrcode = ?";
            $whereMatches[] = "p.barcode = ?";
            $whereMatches[] = "p.stockorder_no = ?";
            $whereMatches[] = "p.psono = ?";
            $whereMatches[] = "p.bed_no = ?";
            $whereMatches[] = "p.lotno = ?";
            $whereMatches[] = "p.sowinglotno_internal = ?";
            $whereMatches[] = "p.batchno = ?";
            for ($i = 0; $i < 8; $i++) {
                $params[] = $term;
                $types .= 's';
            }
        }

        if (empty($whereMatches) && !empty($filterBed)) {
            $whereMatches[] = "p.bed_no = ?";
            $params[] = $filterBed;
            $types .= 's';
        }

        $foundTarget = null;

        if (!empty($whereMatches)) {
            $matchSql = "SELECT p.polyhouseplantid, p.barcode, p.bed_no, p.polyhouseid, p.locationid, p.stockorder_no, p.psono, p.productid, p.productname, p.bedqrcode 
                         FROM krf_polyhouse_plant_details p 
                         WHERE p.projectid = {$projectid} AND (" . implode(" OR ", $whereMatches) . ")
                         ORDER BY (p.bedqrcode = '{$krfconn->real_escape_string($cleanedQuery)}') DESC, (p.barcode = '{$krfconn->real_escape_string($cleanedQuery)}') DESC, p.polyhouseplantid DESC 
                         LIMIT 1";
            
            $stmt = $krfconn->prepare($matchSql);
            if ($stmt) {
                if (!empty($types)) {
                    $stmt->bind_param($types, ...$params);
                }
                $stmt->execute();
                $foundTarget = $stmt->get_result()->fetch_assoc();
                $stmt->close();
            }
        }

        // Fallback: Check if query is directly a Bed name (like "Bed-01" or "Bed 1")
        if (!$foundTarget && !empty($cleanedQuery)) {
            $bedCandidate = $cleanedQuery;
            if (is_numeric($bedCandidate)) {
                $bedCandidate = 'Bed-' . str_pad($bedCandidate, 2, '0', STR_PAD_LEFT);
            }
            $bedCheckSql = "SELECT p.polyhouseplantid, p.barcode, p.bed_no, p.polyhouseid, p.locationid, p.stockorder_no, p.psono, p.productid, p.productname 
                            FROM krf_polyhouse_plant_details p 
                            WHERE p.projectid = {$projectid} AND (p.bed_no = ? OR p.bed_no LIKE ?)
                            LIMIT 1";
            $bstmt = $krfconn->prepare($bedCheckSql);
            if ($bstmt) {
                $likeBed = "%" . $bedCandidate . "%";
                $bstmt->bind_param('ss', $bedCandidate, $likeBed);
                $bstmt->execute();
                $foundTarget = $bstmt->get_result()->fetch_assoc();
                $bstmt->close();
            }
        }

        if (!$foundTarget && !empty($filterBed)) {
            $foundTarget = [
                'bed_no' => $filterBed,
                'polyhouseid' => $filterPh,
                'locationid' => $filterLoc,
                'stockorder_no' => '',
                'barcode' => ''
            ];
        }

        if (!$foundTarget) {
            echo json_encode([
                'status' => 'error',
                'message' => 'No active plant, order, or bed records found matching: "' . htmlspecialchars($queryRaw) . '"'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $targetBedNo     = trim($foundTarget['bed_no'] ?? $filterBed);
        $targetLocationId= trim($foundTarget['locationid'] ?? $filterLoc);
        $targetPhId      = (int)($foundTarget['polyhouseid'] ?? $filterPh);
        $matchedBarcode  = trim($foundTarget['barcode'] ?? '');
        $matchedStockOrder= trim($foundTarget['stockorder_no'] ?? '');

        // Fetch Location Name
        $targetLocationName = $targetLocationId ?: 'N/A';
        if (!empty($targetLocationId) && isset($hrmconn)) {
            $lstmt = $hrmconn->prepare("SELECT locationname FROM hrm_AttendanceLocationMaster WHERE projectid = ? AND locationid = ? LIMIT 1");
            if ($lstmt) {
                $pidStr = (string)$projectid;
                $lstmt->bind_param('ss', $pidStr, $targetLocationId);
                $lstmt->execute();
                $lr = $lstmt->get_result()->fetch_assoc();
                if ($lr && !empty($lr['locationname'])) $targetLocationName = $lr['locationname'];
                $lstmt->close();
            }
        }

        // Fetch Polyhouse Name
        $targetPhName = "Polyhouse " . ($targetPhId ?: '1');
        if ($targetPhId > 0) {
            $phstmt = $krfconn->prepare("SELECT polyhousename FROM krf_polyhouse_master WHERE polyhouseid = ? LIMIT 1");
            if ($phstmt) {
                $phstmt->bind_param('i', $targetPhId);
                $phstmt->execute();
                $phr = $phstmt->get_result()->fetch_assoc();
                if ($phr && !empty($phr['polyhousename'])) $targetPhName = $phr['polyhousename'];
                $phstmt->close();
            }
        }

        // -------------------------------------------------------------
        // Fetch ALL Tray Records in this Whole Bed
        // -------------------------------------------------------------
        $bedWhere = ["p.projectid = {$projectid}"];
        if (!empty($targetLocationId)) {
            $locEsc = $krfconn->real_escape_string($targetLocationId);
            $bedWhere[] = "p.locationid = '{$locEsc}'";
        }
        if ($targetPhId > 0) {
            $bedWhere[] = "CAST(p.polyhouseid AS UNSIGNED) = {$targetPhId}";
        }
        if (!empty($targetBedNo)) {
            $bedEsc = $krfconn->real_escape_string($targetBedNo);
            $bedWhere[] = "p.bed_no = '{$bedEsc}'";
        }

        $bedWhereSql = implode(" AND ", $bedWhere);

        $traySql = "
            SELECT 
                p.polyhouseplantid,
                p.barcode,
                p.bed_no,
                p.polyhouseid,
                p.locationid,
                p.stockorder_no,
                p.psono,
                p.productid,
                p.productname,
                p.variety,
                p.qtypertray,
                p.tray_id,
                p.tray_code,
                p.tray_position,
                p.growth_stage,
                p.health_status,
                p.status,
                p.workingstatus,
                p.bedqrcode,
                COALESCE(NULLIF(TRIM(p.lotno),''), NULLIF(TRIM(p.sowinglotno_internal),''), NULLIF(TRIM(p.batchno),''), '') AS seed_lot_no,
                COALESCE(p.sowingdate_internal, p.sowing_date) AS sowingdate_internal,
                p.sowing_date,
                p.dispatchdate_internal,
                p.expected_harvest_date,
                p.bed_verify,
                p.bed_verifyat,
                p.bed_verifyby,
                p.failurecount,
                p.created_at,
                ord.quantity AS order_target_qty,
                ord.expected_delivery_date,
                ord.po_number,
                ord.clientid,
                ord.remarks AS order_remarks,
                cm.client_name,
                (CASE WHEN rts.id IS NOT NULL OR p.status = 'Readytosale' OR p.growth_stage = 'ReadyToSale' THEN 1 ELSE 0 END) AS is_ready_to_sale
            FROM krf_polyhouse_plant_details p
            LEFT JOIN krf_production_stockorder ord 
                ON ord.projectid = p.projectid 
               AND ord.stockorder_no = p.stockorder_no 
               AND ord.productid = p.productid
            LEFT JOIN krf_client_master cm 
                ON cm.client_id = ord.clientid 
               AND cm.ProjectId = p.projectid
            LEFT JOIN krf_readytosale_stocks rts 
                ON (rts.polyhouseplantid = p.polyhouseplantid OR (rts.barcode = p.barcode AND p.barcode IS NOT NULL AND p.barcode <> ''))
               AND rts.status IN ('Readytosale', 'Ready to sale', 'ReadyToSale')
            WHERE {$bedWhereSql}
            ORDER BY p.stockorder_no ASC, p.sowingdate_internal ASC, p.polyhouseplantid ASC
        ";

        $trayRes = $krfconn->query($traySql);
        $rawTrays = [];
        if ($trayRes) {
            while ($row = $trayRes->fetch_assoc()) {
                $rawTrays[] = $row;
            }
        }

        // Summary KPI Metrics for Whole Bed
        $totalTrays = count($rawTrays);
        $totalPlants = 0;
        $readyToSaleTrays = 0;
        $verifiedTrays = 0;
        $unverifiedTrays = 0;
        $germFailureTrays = 0;
        $germFailureQty = 0;

        $ordersMap = [];
        $dateWiseMap = [];

        foreach ($rawTrays as $tray) {
            $qty = (int)($tray['qtypertray'] ?: 209);
            $totalPlants += $qty;

            $isReady = (int)($tray['is_ready_to_sale'] ?? 0);
            if ($isReady) $readyToSaleTrays++;

            $isVerified = (!empty($tray['bed_verify']) && trim($tray['bed_verify']) !== '');
            if ($isVerified) $verifiedTrays++;
            else $unverifiedTrays++;

            if ($tray['health_status'] === 'Germination Failure' || $tray['status'] === 'Germination Failure') {
                $germFailureTrays++;
                $germFailureQty += (int)($tray['failurecount'] ?? 0);
            }

            // Order & Product Key
            $soNo = $tray['stockorder_no'] ?: 'N/A';
            $pId = $tray['productid'] ?: '0';
            $orderKey = $soNo . '__' . $pId;

            $rawProdName = trim($tray['productname'] ?? '');
            $rawVariety = trim($tray['variety'] ?? '');
            if (strpos($rawProdName, ',') !== false) {
                $parts = explode(',', $rawProdName, 2);
                $prodNameOnly = trim($parts[0]);
                $varietyOnly = trim($parts[1]);
            } else {
                $prodNameOnly = $rawProdName ?: 'Plant';
                $varietyOnly = (!empty($rawVariety) && $rawVariety !== $rawProdName) ? $rawVariety : '-';
            }
            if ((empty($varietyOnly) || $varietyOnly === '-') && !empty($rawVariety)) {
                $varietyOnly = $rawVariety;
            }

            $sowDateInt = !empty($tray['sowingdate_internal']) ? date('d-M-Y', strtotime($tray['sowingdate_internal'])) : ($tray['sowing_date'] ? date('d-M-Y', strtotime($tray['sowing_date'])) : '-');
            $dispDateInt = !empty($tray['dispatchdate_internal']) ? date('d-M-Y', strtotime($tray['dispatchdate_internal'])) : (!empty($tray['expected_delivery_date']) ? date('d-M-Y', strtotime($tray['expected_delivery_date'])) : '-');
            $harvestDate = !empty($tray['expected_harvest_date']) ? date('d-M-Y', strtotime($tray['expected_harvest_date'])) : '-';

            if (!isset($ordersMap[$orderKey])) {
                $ordersMap[$orderKey] = [
                    'order_key' => $orderKey,
                    'stockorder_no' => $soNo,
                    'psono' => $tray['psono'] ?: ($tray['po_number'] ?: 'N/A'),
                    'productid' => $pId,
                    'productname_full' => $rawProdName,
                    'product_name' => $prodNameOnly,
                    'variety' => $varietyOnly,
                    'seed_lot_no' => $tray['seed_lot_no'] ?: '-',
                    'bedqrcode' => $tray['bedqrcode'] ?: '-',
                    'client_name' => $tray['client_name'] ?: ($tray['clientid'] ?: 'Ready Stock'),
                    'sowingdate_internal' => $sowDateInt,
                    'dispatchdate_internal' => $dispDateInt,
                    'expected_harvest_date' => $harvestDate,
                    'growth_stage' => $tray['growth_stage'] ?: 'Polyhouse',
                    'health_status' => $tray['health_status'] ?: 'Normal',
                    'workingstatus' => $tray['workingstatus'] ?: 'Polyhouse',
                    'status' => $tray['status'] ?: 'Polyhouse',
                    'tray_id' => $tray['tray_id'],
                    'qtypertray' => $qty,
                    'tray_count' => 0,
                    'total_plants' => 0,
                    'ready_to_sale_trays' => 0,
                    'verified_trays' => 0,
                    'unverified_trays' => 0,
                    'is_scanned_match' => ($soNo === $matchedStockOrder || $tray['barcode'] === $matchedBarcode || $tray['bedqrcode'] === $cleanedQuery),
                    'print_slip_url' => "../polyhousetracking/modal/print_qrcode.php?projectid={$projectid}&locationid=" . urlencode($targetLocationId) . "&polyhouseid={$targetPhId}&bed_no=" . urlencode($targetBedNo) . "&stockorder_no=" . urlencode($soNo) . "&productid=" . urlencode($pId)
                ];
            }

            $ordersMap[$orderKey]['tray_count']++;
            $ordersMap[$orderKey]['total_plants'] += $qty;
            if ($isReady) $ordersMap[$orderKey]['ready_to_sale_trays']++;
            if ($isVerified) $ordersMap[$orderKey]['verified_trays']++;
            else $ordersMap[$orderKey]['unverified_trays']++;

            // Date-wise Grouping
            $dateGroupKey = $sowDateInt . ' -> ' . $dispDateInt;
            if (!isset($dateWiseMap[$dateGroupKey])) {
                $dateWiseMap[$dateGroupKey] = [
                    'date_group' => $dateGroupKey,
                    'sowing_date' => $sowDateInt,
                    'dispatch_date' => $dispDateInt,
                    'products' => [],
                    'tray_count' => 0,
                    'plant_count' => 0,
                    'ready_count' => 0
                ];
            }
            $dateWiseMap[$dateGroupKey]['tray_count']++;
            $dateWiseMap[$dateGroupKey]['plant_count'] += $qty;
            if ($isReady) $dateWiseMap[$dateGroupKey]['ready_count']++;
            if (!in_array($prodNameOnly . ' (' . $varietyOnly . ')', $dateWiseMap[$dateGroupKey]['products'])) {
                $dateWiseMap[$dateGroupKey]['products'][] = $prodNameOnly . ' (' . $varietyOnly . ')';
            }
        }

        // Structured list of Trays for detailed table
        $trayList = [];
        foreach ($rawTrays as $t) {
            $isV = (!empty($t['bed_verify']) && trim($t['bed_verify']) !== '');
            $sDate = !empty($t['sowingdate_internal']) ? date('d-M-Y', strtotime($t['sowingdate_internal'])) : '-';
            $dDate = !empty($t['dispatchdate_internal']) ? date('d-M-Y', strtotime($t['dispatchdate_internal'])) : '-';
            $vTime = !empty($t['bed_verifyat']) ? date('d-M-Y h:i A', strtotime($t['bed_verifyat'])) : '-';

            $trayList[] = [
                'id' => $t['polyhouseplantid'],
                'barcode' => $t['barcode'],
                'stockorder_no' => $t['stockorder_no'] ?: 'N/A',
                'psono' => $t['psono'] ?: 'N/A',
                'productname' => $t['productname'],
                'variety' => $t['variety'],
                'qty' => (int)($t['qtypertray'] ?: 209),
                'tray_position' => $t['tray_position'] ?: '-',
                'sowing_date' => $sDate,
                'dispatch_date' => $dDate,
                'growth_stage' => $t['growth_stage'] ?: 'Polyhouse',
                'health_status' => $t['health_status'] ?: 'Normal',
                'status' => $t['status'] ?: 'Polyhouse',
                'is_verified' => $isV,
                'verified_at' => $vTime,
                'verified_by' => $t['bed_verifyby'] ?: ($t['bed_verify'] ?: '-'),
                'is_scanned_barcode' => ($t['barcode'] === $matchedBarcode)
            ];
        }

        echo json_encode([
            'status' => 'success',
            'query' => $queryRaw,
            'bed_overview' => [
                'bed_no' => $targetBedNo,
                'locationid' => $targetLocationId,
                'locationname' => $targetLocationName,
                'polyhouseid' => $targetPhId,
                'polyhousename' => $targetPhName,
                'total_orders' => count($ordersMap),
                'total_trays' => $totalTrays,
                'total_plants' => $totalPlants,
                'ready_to_sale_trays' => $readyToSaleTrays,
                'verified_trays' => $verifiedTrays,
                'unverified_trays' => $unverifiedTrays,
                'germination_failure_trays' => $germFailureTrays,
                'germination_failure_qty' => $germFailureQty,
                'matched_barcode' => $matchedBarcode,
                'matched_stockorder' => $matchedStockOrder
            ],
            'production_orders' => array_values($ordersMap),
            'date_wise_summary' => array_values($dateWiseMap),
            'trays' => $trayList
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Invalid action'], JSON_UNESCAPED_UNICODE);
    exit;
}

// -------------------------------------------------------------
// Render Web Page
// -------------------------------------------------------------
$projectid = (int)($_SESSION['projectid'] ?? 17);
if ($projectid <= 0) $projectid = 17;

if (isset($_GET['dz']) || empty($_GET['k'])) {
    require_once(__DIR__ . '/../connection.php');
} else {
    require_once(__DIR__ . '/../header.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Polyhouse Bed & Order QR Scanner | Karjee Farm</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
  <meta name="theme-color" content="#4f46e5">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">

  <!-- Google Fonts: Plus Jakarta Sans & JetBrains Mono -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Font Awesome 6.5 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  
  <!-- Bootstrap 5.3 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <!-- HTML5 QR Code Scanner Library -->
  <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

  <style>
    :root {
      --font-main: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
      --font-mono: 'JetBrains Mono', monospace;
      
      --primary: #4f46e5;
      --primary-dark: #3730a3;
      --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
      --primary-glow: 0 8px 20px -4px rgba(79, 70, 229, 0.35);
      --primary-light: #eef2ff;
      --primary-border: #c7d2fe;

      --emerald: #10b981;
      --emerald-dark: #059669;
      --emerald-light: #ecfdf5;
      --emerald-border: #a7f3d0;

      --sky: #0284c7;
      --sky-light: #f0f9ff;
      --sky-border: #bae6fd;

      --amber: #f59e0b;
      --amber-dark: #d97706;
      --amber-light: #fffbeb;
      --amber-border: #fde68a;

      --purple: #8b5cf6;
      --purple-light: #f5f3ff;
      --purple-border: #ddd6fe;

      --rose: #ef4444;
      --rose-light: #fef2f2;
      --rose-border: #fecaca;

      --slate-900: #0f172a;
      --slate-800: #1e293b;
      --slate-700: #334155;
      --slate-600: #475569;
      --slate-500: #64748b;
      --slate-400: #94a3b8;
      --slate-200: #e2e8f0;
      --slate-100: #f1f5f9;
      --slate-50: #f8fafc;

      --card-radius: 18px;
      --shadow-subtle: 0 2px 10px rgba(15, 23, 42, 0.04);
      --shadow-card: 0 10px 25px -4px rgba(15, 23, 42, 0.06), 0 4px 10px -2px rgba(15, 23, 42, 0.02);
      --shadow-hover: 0 16px 32px -4px rgba(15, 23, 42, 0.1), 0 6px 16px -2px rgba(15, 23, 42, 0.04);
    }

    * {
      box-sizing: border-box;
    }

    body {
      font-family: var(--font-main);
      background: #f8fafc;
      color: var(--slate-900);
      margin: 0;
      padding-bottom: 90px;
      -webkit-font-smoothing: antialiased;
      -webkit-tap-highlight-color: transparent;
    }

    /* -------------------------------------------------------------
       Top Navigation & App Header
       ------------------------------------------------------------- */
    .scanner-app-header {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(12px);
      border-bottom: 1px solid var(--slate-200);
      padding: 1rem 1.25rem;
      position: sticky;
      top: 0;
      z-index: 100;
      box-shadow: 0 2px 12px rgba(15, 23, 42, 0.03);
    }

    .brand-icon-wrap {
      width: 44px;
      height: 44px;
      border-radius: 12px;
      background: var(--primary-gradient);
      color: #ffffff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.35rem;
      box-shadow: var(--primary-glow);
      flex-shrink: 0;
    }

    .status-pulse-dot {
      display: inline-block;
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: var(--emerald);
      box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
      animation: pulse-dot 2s infinite;
      margin-right: 4px;
      vertical-align: middle;
    }

    @keyframes pulse-dot {
      0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
      70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
      100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }

    .header-nav-btn {
      font-size: 0.84rem;
      font-weight: 600;
      padding: 0.55rem 0.95rem;
      border-radius: 10px;
      transition: all 0.2s ease;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      text-decoration: none;
    }
    .header-nav-btn:hover {
      transform: translateY(-1px);
    }

    /* -------------------------------------------------------------
       Main Container Layout
       ------------------------------------------------------------- */
    .main-container {
      max-width: 1420px;
      margin: 1.25rem auto 0 auto;
      padding: 0 1rem;
    }

    /* -------------------------------------------------------------
       Scanner Search Box & Controls
       ------------------------------------------------------------- */
    .command-center-card {
      background: #ffffff;
      border-radius: var(--card-radius);
      border: 1px solid var(--slate-200);
      box-shadow: var(--shadow-card);
      overflow: hidden;
      margin-bottom: 1.5rem;
      transition: all 0.25s ease;
    }

    .search-input-wrap {
      position: relative;
    }
    .search-input-main {
      font-family: var(--font-mono);
      font-size: 1.05rem;
      font-weight: 700;
      color: var(--slate-900);
      background: #ffffff;
      border: 2px solid var(--slate-200);
      border-radius: 14px;
      padding: 0.85rem 3.4rem 0.85rem 3rem;
      width: 100%;
      transition: all 0.2s ease;
    }
    .search-input-main:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.12);
      outline: none;
    }
    .search-input-main::placeholder {
      font-family: var(--font-main);
      font-weight: 500;
      color: var(--slate-400);
    }
    .search-leading-icon {
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      font-size: 1.25rem;
      color: var(--primary);
      pointer-events: none;
    }
    .search-clear-btn {
      position: absolute;
      right: 1rem;
      top: 50%;
      transform: translateY(-50%);
      background: var(--slate-100);
      border: none;
      color: var(--slate-500);
      width: 26px;
      height: 26px;
      border-radius: 50%;
      display: none;
      align-items: center;
      justify-content: center;
      font-size: 0.75rem;
      cursor: pointer;
      transition: all 0.15s;
    }
    .search-clear-btn:hover {
      background: var(--slate-200);
      color: var(--slate-800);
    }

    .btn-search-action {
      background: var(--primary-gradient);
      color: #ffffff;
      border: none;
      border-radius: 12px;
      font-weight: 700;
      font-size: 0.95rem;
      padding: 0.85rem 1.4rem;
      box-shadow: var(--primary-glow);
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.2s ease;
      white-space: nowrap;
    }
    .btn-search-action:hover {
      transform: translateY(-1px);
      box-shadow: 0 10px 24px -4px rgba(79, 70, 229, 0.45);
      color: #ffffff;
    }
    .btn-search-action:active {
      transform: scale(0.98);
    }

    .btn-camera-trigger {
      background: #ffffff;
      color: var(--primary);
      border: 1.5px solid var(--primary-border);
      border-radius: 12px;
      font-weight: 700;
      font-size: 0.95rem;
      padding: 0.85rem 1.2rem;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.2s ease;
      white-space: nowrap;
    }
    .btn-camera-trigger:hover {
      background: var(--primary-light);
      border-color: var(--primary);
      color: var(--primary-dark);
      transform: translateY(-1px);
    }
    .btn-camera-trigger:active {
      transform: scale(0.98);
    }

    /* -------------------------------------------------------------
       Quick Bed Filters & Chips
       ------------------------------------------------------------- */
    .filter-select-modern {
      border-radius: 10px;
      border: 1px solid var(--slate-200);
      padding: 0.48rem 0.85rem;
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--slate-800);
      background-color: #ffffff;
      transition: all 0.2s;
      width: 100%;
    }
    .filter-select-modern:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
      outline: none;
    }

    .quick-chips-wrapper {
      display: flex;
      align-items: center;
      gap: 6px;
      overflow-x: auto;
      padding: 4px 0 2px 0;
      scrollbar-width: none;
      -ms-overflow-style: none;
    }
    .quick-chips-wrapper::-webkit-scrollbar {
      display: none;
    }
    .quick-chip {
      background: var(--slate-50);
      color: var(--slate-700);
      border: 1px solid var(--slate-200);
      border-radius: 20px;
      padding: 4px 12px;
      font-size: 0.78rem;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      transition: all 0.15s ease;
      white-space: nowrap;
      flex-shrink: 0;
    }
    .quick-chip:hover {
      background: var(--primary);
      color: #ffffff;
      border-color: var(--primary);
      transform: translateY(-1px);
    }

    /* -------------------------------------------------------------
       Live Camera Scanner Viewport
       ------------------------------------------------------------- */
    .camera-hud-container {
      background: #090d16;
      border-radius: 18px;
      padding: 1rem;
      position: relative;
      border: 1px solid #1e293b;
      box-shadow: 0 20px 40px -8px rgba(0, 0, 0, 0.5);
      margin-top: 1rem;
      overflow: hidden;
    }
    .camera-viewfinder-box {
      width: 100%;
      max-width: 440px;
      margin: 0 auto;
      border-radius: 14px;
      overflow: hidden;
      position: relative;
      background: #000000;
      border: 2px solid #334155;
    }
    #reader {
      width: 100% !important;
      border: none !important;
    }
    #reader video {
      border-radius: 12px;
      object-fit: cover;
      max-height: 380px;
    }

    /* Animated Reticle Overlay */
    .scanner-reticle-overlay {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      pointer-events: none;
      z-index: 5;
    }
    .scanner-laser-line {
      position: absolute;
      left: 8%;
      right: 8%;
      height: 2px;
      background: linear-gradient(90deg, transparent, #6366f1, #a855f7, transparent);
      box-shadow: 0 0 12px 3px rgba(99, 102, 241, 0.8);
      animation: scan-laser 2.2s infinite ease-in-out;
    }
    @keyframes scan-laser {
      0% { top: 12%; opacity: 0.3; }
      50% { top: 88%; opacity: 1; }
      100% { top: 12%; opacity: 0.3; }
    }
    .reticle-corner {
      position: absolute;
      width: 22px;
      height: 22px;
      border-color: #6366f1;
      border-style: solid;
      border-width: 0;
    }
    .reticle-tl { top: 14px; left: 14px; border-top-width: 3px; border-left-width: 3px; border-top-left-radius: 6px; }
    .reticle-tr { top: 14px; right: 14px; border-top-width: 3px; border-right-width: 3px; border-top-right-radius: 6px; }
    .reticle-bl { bottom: 14px; left: 14px; border-bottom-width: 3px; border-left-width: 3px; border-bottom-left-radius: 6px; }
    .reticle-br { bottom: 14px; right: 14px; border-bottom-width: 3px; border-right-width: 3px; border-bottom-right-radius: 6px; }

    /* -------------------------------------------------------------
       Whole Bed Overview Banner & KPI Dashboard
       ------------------------------------------------------------- */
    .bed-overview-banner {
      background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
      border: 1px solid var(--slate-200);
      border-radius: var(--card-radius);
      box-shadow: var(--shadow-card);
      padding: 1.4rem;
      margin-bottom: 1.5rem;
    }

    .badge-bed-lg {
      background: var(--primary-gradient);
      color: #ffffff;
      font-family: var(--font-mono);
      font-size: 1.15rem;
      font-weight: 800;
      padding: 0.45rem 1rem;
      border-radius: 12px;
      letter-spacing: 0.5px;
      box-shadow: var(--primary-glow);
    }

    .query-tag-pill {
      background: var(--primary-light);
      color: var(--primary-dark);
      border: 1px solid var(--primary-border);
      border-radius: 10px;
      padding: 0.35rem 0.75rem;
      font-family: var(--font-mono);
      font-weight: 700;
      font-size: 0.88rem;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    /* KPI Cards */
    .kpi-tile {
      background: #ffffff;
      border-radius: 14px;
      border: 1px solid var(--slate-200);
      padding: 1rem;
      box-shadow: var(--shadow-subtle);
      transition: all 0.25s ease;
      position: relative;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      height: 100%;
    }
    .kpi-tile:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-hover);
    }
    .kpi-tile::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      height: 3px;
    }
    .kpi-tile-orders::after   { background: var(--primary); }
    .kpi-tile-trays::after    { background: var(--sky); }
    .kpi-tile-plants::after   { background: var(--emerald); }
    .kpi-tile-ready::after    { background: #16a34a; }
    .kpi-tile-verified::after { background: var(--purple); }
    .kpi-tile-unverified::after { background: var(--amber); }

    .kpi-tile-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 0.5rem;
    }
    .kpi-tile-icon {
      width: 38px;
      height: 38px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.15rem;
      flex-shrink: 0;
    }
    .kpi-tile-title {
      font-size: 0.72rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: var(--slate-500);
    }
    .kpi-tile-value {
      font-size: 1.55rem;
      font-weight: 800;
      line-height: 1.1;
      font-family: var(--font-mono);
      color: var(--slate-900);
      margin-top: 0.25rem;
    }
    .kpi-tile-sub {
      font-size: 0.75rem;
      color: var(--slate-500);
      margin-top: 0.25rem;
    }

    /* -------------------------------------------------------------
       Production Order Cards
       ------------------------------------------------------------- */
    .order-modern-card {
      background: #ffffff;
      border-radius: var(--card-radius);
      border: 1px solid var(--slate-200);
      box-shadow: var(--shadow-card);
      margin-bottom: 1.25rem;
      overflow: hidden;
      transition: all 0.2s ease;
    }
    .order-modern-card:hover {
      box-shadow: var(--shadow-hover);
      border-color: var(--slate-400);
    }
    .order-modern-card.scanned-highlight-card {
      border: 2px solid var(--primary);
      box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.12), var(--shadow-card);
    }

    .order-head-bar {
      background: var(--slate-50);
      padding: 0.85rem 1.25rem;
      border-bottom: 1px solid var(--slate-200);
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 0.75rem;
    }

    .badge-soft {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 4px 10px;
      border-radius: 8px;
      font-size: 0.76rem;
      font-weight: 700;
    }
    .badge-soft-primary { background: var(--primary-light); color: var(--primary-dark); border: 1px solid var(--primary-border); }
    .badge-soft-success { background: var(--emerald-light); color: var(--emerald-dark); border: 1px solid var(--emerald-border); }
    .badge-soft-warning { background: var(--amber-light); color: var(--amber-dark); border: 1px solid var(--amber-border); }
    .badge-soft-danger  { background: var(--rose-light); color: var(--rose); border: 1px solid var(--rose-border); }
    .badge-soft-purple  { background: var(--purple-light); color: var(--purple); border: 1px solid var(--purple-border); }
    .badge-soft-dark    { background: var(--slate-100); color: var(--slate-700); border: 1px solid var(--slate-200); }

    .print-slip-btn {
      background: #ffffff;
      color: var(--primary);
      border: 1px solid var(--primary-border);
      border-radius: 8px;
      font-size: 0.78rem;
      font-weight: 700;
      padding: 0.35rem 0.85rem;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all 0.15s ease;
    }
    .print-slip-btn:hover {
      background: var(--primary);
      color: #ffffff;
      border-color: var(--primary);
      transform: translateY(-1px);
    }

    /* -------------------------------------------------------------
       Date-Wise Summary Timeline Cards
       ------------------------------------------------------------- */
    .timeline-strip-card {
      background: #ffffff;
      border: 1px solid var(--slate-200);
      border-radius: 12px;
      padding: 0.85rem 1rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      transition: all 0.2s ease;
      height: 100%;
    }
    .timeline-strip-card:hover {
      border-color: var(--primary-border);
      box-shadow: var(--shadow-subtle);
    }

    /* -------------------------------------------------------------
       Tray Inventory Table & Mobile Cards View
       ------------------------------------------------------------- */
    .view-switch-btn {
      font-size: 0.78rem;
      font-weight: 700;
      padding: 0.35rem 0.75rem;
      border-radius: 8px;
      border: 1px solid var(--slate-200);
      background: #ffffff;
      color: var(--slate-600);
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      transition: all 0.15s;
    }
    .view-switch-btn.active {
      background: var(--primary);
      color: #ffffff;
      border-color: var(--primary);
    }

    .trays-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      font-size: 0.86rem;
    }
    .trays-table th {
      background: var(--slate-50);
      color: var(--slate-600);
      font-size: 0.72rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      padding: 11px 12px;
      border-bottom: 2px solid var(--slate-200);
      white-space: nowrap;
      position: sticky;
      top: 0;
      z-index: 10;
    }
    .trays-table td {
      padding: 10px 12px;
      border-bottom: 1px solid var(--slate-200);
      vertical-align: middle;
      color: var(--slate-800);
    }
    .trays-table tr:hover td {
      background: rgba(241, 245, 249, 0.6);
    }
    .row-highlight-scanned td {
      background: #fef9c3 !important;
      font-weight: 700;
    }

    /* Mobile Tray Card View */
    .mobile-tray-card {
      background: #ffffff;
      border-radius: 12px;
      border: 1px solid var(--slate-200);
      padding: 0.85rem;
      margin-bottom: 0.75rem;
      box-shadow: 0 1px 4px rgba(15, 23, 42, 0.04);
      transition: all 0.2s ease;
    }
    .mobile-tray-card.scanned-tray {
      border: 2px solid var(--primary);
      background: #fefce8;
    }

    /* -------------------------------------------------------------
       Mobile Floating Action Button (FAB)
       ------------------------------------------------------------- */
    .mobile-scan-fab {
      position: fixed;
      bottom: 22px;
      right: 20px;
      z-index: 1000;
      background: var(--primary-gradient);
      color: #ffffff;
      border: none;
      border-radius: 50px;
      padding: 0.85rem 1.35rem;
      font-weight: 700;
      font-size: 0.95rem;
      box-shadow: 0 8px 24px -2px rgba(79, 70, 229, 0.45);
      display: none;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    .mobile-scan-fab:active {
      transform: scale(0.95);
    }

    /* -------------------------------------------------------------
       Responsive Breakpoints
       ------------------------------------------------------------- */
    @media (max-width: 992px) {
      .scanner-app-header {
        padding: 0.75rem 1rem;
      }
      .brand-title-wrap h1 {
        font-size: 1.25rem !important;
      }
    }

    @media (max-width: 768px) {
      .main-container {
        padding: 0 0.65rem;
        margin-top: 0.85rem;
      }
      .command-center-card {
        border-radius: 14px;
        margin-bottom: 1rem;
      }
      .mobile-scan-fab {
        display: inline-flex;
      }
      .kpi-tile-value {
        font-size: 1.35rem;
      }
      .order-head-bar {
        padding: 0.75rem 0.85rem;
      }
      .bed-overview-banner {
        padding: 1rem;
        border-radius: 14px;
      }
      .badge-bed-lg {
        font-size: 0.95rem;
        padding: 0.35rem 0.75rem;
      }
    }

    @media (max-width: 576px) {
      .search-input-main {
        font-size: 0.95rem;
        padding: 0.75rem 2.8rem 0.75rem 2.5rem;
      }
      .search-leading-icon {
        font-size: 1.1rem;
        left: 0.75rem;
      }
      .btn-search-action,
      .btn-camera-trigger {
        width: 100%;
        justify-content: center;
        padding: 0.75rem 1rem;
      }
    }
  </style>
</head>
<body>

  <!-- =============================================================
       1. Top Sticky App Header
       ============================================================= -->
  <header class="scanner-app-header">
    <div class="container-fluid px-0" style="max-width: 1420px;">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        
        <!-- Left: Brand & Title -->
        <div class="d-flex align-items-center gap-2.5">
          <div class="brand-icon-wrap">
            <i class="fa fa-qrcode"></i>
          </div>
          <div class="brand-title-wrap">
            <div class="d-flex align-items-center gap-1.5 mb-0.5">
              <span class="badge bg-light text-dark border px-2 py-0.5 fw-bold" style="font-size: 0.68rem; letter-spacing: 0.5px;">
                <span class="status-pulse-dot"></span> LIVE SCANNER
              </span>
              <span class="badge px-2 py-0.5 fw-bold d-none d-sm-inline-block" style="background: var(--primary-light); color: var(--primary-dark); font-size: 0.68rem;">
                POLYHOUSE INTELLIGENCE
              </span>
            </div>
            <h1 class="h5 mb-0 fw-bold text-dark" style="letter-spacing: -0.3px; line-height: 1.2;">
              Bed & Order QR Scanner
            </h1>
          </div>
        </div>

        <!-- Right: Actions -->
        <div class="d-flex align-items-center gap-1.5 flex-wrap">
          <button type="button" class="btn btn-outline-primary header-nav-btn" onclick="toggleCameraScanner()" id="btnHeaderCameraToggle">
            <i class="fa fa-camera"></i> <span class="d-none d-sm-inline">Scan Camera</span>
          </button>
          <a href="../polyhousetracking/index.php" class="btn btn-outline-secondary header-nav-btn text-dark">
            <i class="fa fa-th-large text-primary"></i> <span class="d-none d-md-inline">Tracking Grid</span>
          </a>
          <button type="button" class="btn btn-light header-nav-btn border" onclick="resetAllViews()" title="Reset search and filters">
            <i class="fa fa-redo text-secondary"></i> <span class="d-none d-sm-inline">Reset</span>
          </button>
        </div>

      </div>
    </div>
  </header>

  <!-- =============================================================
       2. Main Content Container
       ============================================================= -->
  <main class="main-container">

    <!-- -----------------------------------------------------------
         COMMAND CENTER / SEARCH & SCAN CARD
         ----------------------------------------------------------- -->
    <section class="command-center-card p-3 p-md-4">
      <div class="row g-3 align-items-center">
        
        <!-- Search & Scan Input Column -->
        <div class="col-12 col-lg-8">
          <form id="formScanSearch" onsubmit="handleSearchSubmit(event)">
            <div class="d-flex justify-content-between align-items-center mb-1.5">
              <label for="scanInput" class="form-label fw-bold mb-0 text-dark" style="font-size: 0.88rem;">
                <i class="fa fa-barcode text-primary me-1"></i> Scan or Type QR Code / Barcode / Order No / Bed No
              </label>
              <span class="text-muted small d-none d-sm-inline"><i class="fa fa-keyboard me-1"></i> Auto-Enter Ready</span>
            </div>

            <!-- Unified Search Input Group -->
            <div class="search-input-wrap">
              <i class="fa fa-search search-leading-icon"></i>
              <input 
                type="text" 
                id="scanInput" 
                class="search-input-main" 
                placeholder="Scan QR barcode or enter RSO-1234568 / Bed-01..." 
                autofocus 
                autocomplete="off"
                oninput="onSearchInputChange()"
              >
              <button type="button" class="search-clear-btn" id="btnClearSearch" onclick="clearSearchInput()" title="Clear">
                <i class="fa fa-times"></i>
              </button>
            </div>

            <!-- Action Buttons for Mobile/Desktop -->
            <div class="d-flex align-items-center gap-2 mt-2.5 flex-wrap">
              <button class="btn btn-search-action flex-grow-1 flex-sm-grow-0" type="submit" id="btnSubmitSearch">
                <i class="fa fa-arrow-right"></i> Search Details
              </button>
              <button class="btn btn-camera-trigger flex-grow-1 flex-sm-grow-0" type="button" onclick="toggleCameraScanner()" id="btnActionCameraToggle">
                <i class="fa fa-camera"></i> Live Camera Scan
              </button>
            </div>
          </form>

          <!-- Direct Bed Filter Dropdowns -->
          <div class="mt-3 pt-3 border-top">
            <div class="row g-2 align-items-center">
              <div class="col-12 col-sm-auto">
                <span class="small fw-bold text-dark d-flex align-items-center gap-1">
                  <i class="fa fa-filter text-primary"></i> Direct Filter:
                </span>
              </div>
              <div class="col-12 col-sm-4 col-md-3">
                <select id="filterLocation" class="form-select form-select-sm filter-select-modern" onchange="onLocationChange()">
                  <option value="">All Locations</option>
                </select>
              </div>
              <div class="col-12 col-sm-4 col-md-4">
                <select id="filterPolyhouse" class="form-select form-select-sm filter-select-modern" onchange="onPolyhouseChange()">
                  <option value="">All Polyhouses</option>
                </select>
              </div>
              <div class="col-12 col-sm-4 col-md-3">
                <select id="filterBed" class="form-select form-select-sm filter-select-modern" onchange="onBedSelectChange()">
                  <option value="">Select Bed</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Quick Test Samples -->
          <div class="mt-2.5 d-flex align-items-center gap-2">
            <span class="text-muted small fw-bold flex-shrink-0"><i class="fa fa-bolt text-warning me-1"></i> Quick Test:</span>
            <div class="quick-chips-wrapper">
              <button type="button" class="quick-chip" onclick="quickSearch('RSO-1234568')"><i class="fa fa-tag text-primary"></i> RSO-1234568</button>
              <button type="button" class="quick-chip" onclick="quickSearch('RSO-260918-234434')"><i class="fa fa-tag text-primary"></i> RSO-260918-234434</button>
              <button type="button" class="quick-chip" onclick="quickSearch('Bed-01')"><i class="fa fa-layer-group text-success"></i> Bed-01</button>
              <button type="button" class="quick-chip" onclick="quickSearch('Bed-02')"><i class="fa fa-layer-group text-success"></i> Bed-02</button>
              <button type="button" class="quick-chip" onclick="quickSearch('Bed-03')"><i class="fa fa-layer-group text-success"></i> Bed-03</button>
            </div>
          </div>

        </div>

        <!-- Camera Scanner Side Column (or Collapsible HUD) -->
        <div class="col-12 col-lg-4 text-center">
          <div class="p-3 rounded-4 border text-center h-100 d-flex flex-column justify-content-center" style="background: var(--slate-50);">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="fw-bold text-dark small"><i class="fa fa-video text-primary me-1.5"></i> Camera QR Viewfinder</span>
              <span class="badge bg-light text-secondary border px-2 py-0.5" id="cameraStatusPill" style="font-size: 0.7rem;">Standby</span>
            </div>

            <!-- Standby message -->
            <div id="cameraStandbyBox" class="py-3">
              <div class="d-inline-flex p-3 rounded-circle mb-2" style="background: var(--primary-light); color: var(--primary);">
                <i class="fa fa-camera-retro fa-2x"></i>
              </div>
              <p class="small text-muted mb-2">Point phone or webcam camera at Tray QR or Bed slip.</p>
              <button type="button" class="btn btn-sm btn-primary px-3 py-1.5 fw-bold rounded-pill" onclick="startCameraScanner()">
                <i class="fa fa-play me-1"></i> Start Camera Scan
              </button>
            </div>

            <!-- Active Camera Viewport -->
            <div id="cameraActiveBox" style="display: none;">
              <div class="camera-hud-container">
                <div class="camera-viewfinder-box">
                  <div id="reader"></div>
                  <!-- Futuristic Reticle -->
                  <div class="scanner-reticle-overlay">
                    <div class="scanner-laser-line"></div>
                    <div class="reticle-corner reticle-tl"></div>
                    <div class="reticle-corner reticle-tr"></div>
                    <div class="reticle-corner reticle-bl"></div>
                    <div class="reticle-corner reticle-br"></div>
                  </div>
                </div>

                <!-- Camera Controls Toolbar -->
                <div class="d-flex align-items-center justify-content-center gap-2 mt-2.5">
                  <button type="button" class="btn btn-sm btn-dark text-white border-secondary rounded-pill px-3 py-1" onclick="switchCameraFacing()" id="btnSwitchCamera" title="Switch Camera">
                    <i class="fa fa-sync-alt me-1"></i> Flip Camera
                  </button>
                  <button type="button" class="btn btn-sm btn-danger rounded-pill px-3 py-1" onclick="stopCameraScanner()">
                    <i class="fa fa-stop me-1"></i> Close
                  </button>
                </div>
              </div>
            </div>

          </div>
        </div>

      </div>
    </section>

    <!-- -----------------------------------------------------------
         LOADING STATE SPINNER
         ----------------------------------------------------------- -->
    <div id="searchSpinner" class="text-center py-5 my-4" style="display: none;">
      <div class="spinner-border text-primary" role="status" style="width: 3.2rem; height: 3.2rem; border-width: 0.28rem;"></div>
      <h5 class="mt-3 fw-bold text-dark">Searching Polyhouse Records...</h5>
      <p class="text-muted small">Inspecting Bed stock, Production Orders, Varieties & Internal Dates</p>
    </div>

    <!-- -----------------------------------------------------------
         ERROR / EMPTY STATE ALERT
         ----------------------------------------------------------- -->
    <div id="alertBox" class="alert alert-warning border-0 shadow-sm rounded-4 p-4 text-center my-3" style="display:none; background: #fffbeb;">
      <div class="d-inline-flex p-3 rounded-circle mb-2" style="background: #fef3c7; color: #b45309;">
        <i class="fa fa-triangle-exclamation fa-2x"></i>
      </div>
      <h5 class="fw-bold text-dark mb-1" id="alertTitle">No Records Found</h5>
      <p class="text-muted mb-0 small" id="alertMsg">Please check the scanned code or try another search term.</p>
    </div>

    <!-- -----------------------------------------------------------
         RESULTS SECTION (Shown after successful lookup)
         ----------------------------------------------------------- -->
    <div id="scanResultsContainer" style="display: none;">

      <!-- =========================================================
           1. WHOLE BED OVERVIEW BANNER & KPIS
           ========================================================= -->
      <section class="bed-overview-banner">
        
        <!-- Header row -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pb-3 mb-3 border-bottom">
          <div>
            <div class="d-flex align-items-center gap-1.5 flex-wrap mb-1">
              <span class="badge-bed-lg" id="resBedBadge">Bed-01</span>
              <span class="badge bg-white text-dark border px-2.5 py-1.5 fw-bold" id="resPolyhouseBadge">Polyhouse 1</span>
              <span class="badge bg-white text-muted border px-2.5 py-1.5 fw-bold" id="resLocationBadge">Nursery Location</span>
            </div>
            <h2 class="h4 fw-bold text-dark mb-0 mt-1" id="resBedTitle">
              Whole Bed Intelligence: <span class="text-primary font-monospace" id="resBedName">Bed-01</span>
            </h2>
          </div>

          <div class="text-start text-sm-end">
            <div class="small text-muted fw-bold">Matched Scan Query</div>
            <div class="query-tag-pill mt-0.5" id="resScannedQueryWrap">
              <span id="resScannedQuery">-</span>
              <button type="button" class="btn btn-link p-0 text-primary ms-1" onclick="copyCurrentQuery()" title="Copy Query">
                <i class="fa fa-copy"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- KPI 6-Metric Grid -->
        <div class="row g-2 g-md-3">
          
          <!-- Orders in Bed -->
          <div class="col-6 col-md-4 col-xl-2">
            <div class="kpi-tile kpi-tile-orders">
              <div class="kpi-tile-head">
                <span class="kpi-tile-title">Orders in Bed</span>
                <div class="kpi-tile-icon" style="background: var(--primary-light); color: var(--primary);">
                  <i class="fa fa-boxes-packing"></i>
                </div>
              </div>
              <div class="kpi-tile-value" id="kpiTotalOrders">0</div>
              <div class="kpi-tile-sub">Active batches</div>
            </div>
          </div>

          <!-- Total Trays -->
          <div class="col-6 col-md-4 col-xl-2">
            <div class="kpi-tile kpi-tile-trays">
              <div class="kpi-tile-head">
                <span class="kpi-tile-title">Total Trays</span>
                <div class="kpi-tile-icon" style="background: var(--sky-light); color: var(--sky);">
                  <i class="fa fa-layer-group"></i>
                </div>
              </div>
              <div class="kpi-tile-value text-info" id="kpiTotalTrays">0</div>
              <div class="kpi-tile-sub">Trays allocated</div>
            </div>
          </div>

          <!-- Total Plants -->
          <div class="col-6 col-md-4 col-xl-2">
            <div class="kpi-tile kpi-tile-plants">
              <div class="kpi-tile-head">
                <span class="kpi-tile-title">Total Plants</span>
                <div class="kpi-tile-icon" style="background: var(--emerald-light); color: var(--emerald);">
                  <i class="fa fa-seedling"></i>
                </div>
              </div>
              <div class="kpi-tile-value text-success" id="kpiTotalPlants">0</div>
              <div class="kpi-tile-sub">Total saplings</div>
            </div>
          </div>

          <!-- Ready to Sale -->
          <div class="col-6 col-md-4 col-xl-2">
            <div class="kpi-tile kpi-tile-ready">
              <div class="kpi-tile-head">
                <span class="kpi-tile-title">Ready to Sale</span>
                <div class="kpi-tile-icon" style="background: #dcfce7; color: #16a34a;">
                  <i class="fa fa-check-circle"></i>
                </div>
              </div>
              <div class="kpi-tile-value text-success" id="kpiReadyTrays">0</div>
              <div class="kpi-tile-sub">Saleable trays</div>
            </div>
          </div>

          <!-- Verified Trays -->
          <div class="col-6 col-md-4 col-xl-2">
            <div class="kpi-tile kpi-tile-verified">
              <div class="kpi-tile-head">
                <span class="kpi-tile-title">Verified Trays</span>
                <div class="kpi-tile-icon" style="background: var(--purple-light); color: var(--purple);">
                  <i class="fa fa-shield-check"></i>
                </div>
              </div>
              <div class="kpi-tile-value" style="color: var(--purple);" id="kpiVerifiedTrays">0</div>
              <div class="kpi-tile-sub">QC verified</div>
            </div>
          </div>

          <!-- Unverified Trays -->
          <div class="col-6 col-md-4 col-xl-2">
            <div class="kpi-tile kpi-tile-unverified">
              <div class="kpi-tile-head">
                <span class="kpi-tile-title">Unverified</span>
                <div class="kpi-tile-icon" style="background: var(--amber-light); color: var(--amber);">
                  <i class="fa fa-clock"></i>
                </div>
              </div>
              <div class="kpi-tile-value text-warning" id="kpiUnverifiedTrays">0</div>
              <div class="kpi-tile-sub">Pending check</div>
            </div>
          </div>

        </div>
      </section>

      <!-- =========================================================
           2. PRODUCTION ORDERS & PRODUCTS BREAKDOWN
           ========================================================= -->
      <section class="mb-4">
        <div class="d-flex justify-content-between align-items-center mb-2.5">
          <h3 class="h5 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
            <i class="fa fa-boxes-stacked text-primary"></i> Production Orders in this Bed
          </h3>
          <span class="badge bg-light text-dark border px-2.5 py-1 fw-bold" id="ordersCountBadge">0 Orders</span>
        </div>

        <div id="ordersContainer">
          <!-- Populated dynamically via JS -->
        </div>
      </section>

      <!-- =========================================================
           3. INTERNAL DATE-WISE TIMELINE SUMMARY
           ========================================================= -->
      <section class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4" style="border: 1px solid var(--slate-200) !important;">
        <div class="card-header bg-white p-3 p-md-3.5 border-bottom">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
              <h4 class="h6 fw-bold mb-0 text-dark">
                <i class="fa fa-calendar-alt text-primary me-1.5"></i> Internal Date-Wise Schedule & Batches
              </h4>
              <div class="small text-muted mt-0.5">Sowing Date (Internal) &rarr; Dispatch Date (Internal) groupings</div>
            </div>
            <span class="badge bg-light text-muted border px-2 py-1 small fw-bold" id="dateGroupCountBadge">0 Groups</span>
          </div>
        </div>
        <div class="card-body p-3 p-md-3.5" style="background: var(--slate-50);" id="dateWisePillsContainer">
          <!-- Populated dynamically via JS -->
        </div>
      </section>

      <!-- =========================================================
           4. INDIVIDUAL TRAY INVENTORY (TABLE & MOBILE CARDS)
           ========================================================= -->
      <section class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4" style="border: 1px solid var(--slate-200) !important;">
        
        <!-- Controls & Filters Header -->
        <div class="card-header bg-white p-3 p-md-3.5 border-bottom">
          <div class="row g-2 align-items-center justify-content-between">
            <div class="col-12 col-md-5">
              <h4 class="h6 fw-bold mb-0 text-dark">
                <i class="fa fa-list-check text-primary me-1.5"></i> Individual Tray Inventory (<span id="trayTotalCountHeader">0</span>)
              </h4>
              <div class="small text-muted mt-0.5">Track every tray's barcode, position, and verification history</div>
            </div>

            <!-- Filters & View Switcher -->
            <div class="col-12 col-md-7">
              <div class="d-flex align-items-center justify-content-md-end gap-2 flex-wrap">
                <!-- Search Tray -->
                <div class="input-group input-group-sm" style="max-width: 200px;">
                  <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa fa-search"></i></span>
                  <input type="text" id="trayTableFilter" class="form-control border-start-0" placeholder="Filter barcode/variety..." onkeyup="filterTrayTable()">
                </div>

                <!-- Status Filter -->
                <select id="trayStatusFilter" class="form-select form-select-sm" style="max-width: 135px;" onchange="filterTrayTable()">
                  <option value="ALL">All Status</option>
                  <option value="VERIFIED">Verified</option>
                  <option value="UNVERIFIED">Unverified</option>
                </select>

                <!-- View Switcher -->
                <div class="d-inline-flex gap-1 border p-1 rounded-3 bg-light">
                  <button type="button" class="view-switch-btn active" id="btnViewTable" onclick="setTrayDisplayMode('table')" title="Table View">
                    <i class="fa fa-table"></i>
                  </button>
                  <button type="button" class="view-switch-btn" id="btnViewCards" onclick="setTrayDisplayMode('cards')" title="Card View">
                    <i class="fa fa-grip-vertical"></i>
                  </button>
                </div>
              </div>
            </div>

          </div>
        </div>

        <div class="card-body p-0">
          
          <!-- A. Desktop/Standard Table View -->
          <div class="table-responsive" id="trayTableViewWrapper">
            <table class="trays-table" id="trayDetailsTable">
              <thead>
                <tr>
                  <th style="width: 44px;">#</th>
                  <th>Tray Barcode</th>
                  <th>Production Order</th>
                  <th>Product & Variety</th>
                  <th>Qty</th>
                  <th>Position</th>
                  <th>Internal Sowing</th>
                  <th>Internal Dispatch</th>
                  <th>Growth Stage</th>
                  <th>Verification</th>
                  <th>Verified Time / By</th>
                </tr>
              </thead>
              <tbody id="trayDetailsTableBody">
                <!-- Dynamically populated -->
              </tbody>
            </table>
          </div>

          <!-- B. Mobile-Optimized Cards View -->
          <div id="trayCardsViewWrapper" class="p-3" style="display: none; background: var(--slate-50);">
            <div class="row g-2" id="trayCardsContainer">
              <!-- Dynamically populated -->
            </div>
          </div>

        </div>
      </section>

    </div>

  </main>

  <!-- =============================================================
       Mobile Floating Action Button (FAB)
       ============================================================= -->
  <button id="mobileScanFab" class="mobile-scan-fab" onclick="triggerQuickScan()">
    <i class="fa fa-camera"></i> <span>Scan QR</span>
  </button>

  <!-- Audio Beep Sound -->
  <audio id="scanBeepSound" preload="auto">
    <source src="https://actions.google.com/sounds/v1/alarms/beep_short.ogg" type="audio/ogg">
  </audio>

  <!-- jQuery & Bootstrap 5 Bundle JS -->
  <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
  <script>window.jQuery || document.write('<script src="../js/jquery.min.js"><\/script>')</script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <!-- =============================================================
       Controller Logic (AJAX, Scanner, Filtering, Mobile Enhancements)
       ============================================================= -->
  <script>
    const projectid = <?php echo (int)$projectid; ?>;
    let html5QrCode = null;
    let isCameraRunning = false;
    let currentCameraFacing = "environment"; // default back/environment camera
    let cachedFilterData = { locations: [], polyhouses: [] };
    let currentBedData = null;
    let trayDisplayMode = window.innerWidth <= 768 ? 'cards' : 'table';

    $(document).ready(function() {
      // Set initial tray display mode based on viewport
      updateTrayViewToggleUI();

      // Load Master Filters
      loadFilterMasters();

      // Check URL parameters for direct deep-linking
      const urlParams = new URLSearchParams(window.location.search);
      const qParam = urlParams.get('q') || urlParams.get('barcode') || urlParams.get('order') || urlParams.get('bed');
      if (qParam) {
        $('#scanInput').val(qParam);
        onSearchInputChange();
        performScanLookup(qParam);
      }

      // Responsive resize handler
      $(window).on('resize', function() {
        if (window.innerWidth <= 768 && trayDisplayMode === 'table') {
          setTrayDisplayMode('cards');
        }
      });
    });

    // -----------------------------------------------------------
    // Search Input Controls
    // -----------------------------------------------------------
    function onSearchInputChange() {
      const val = $('#scanInput').val().trim();
      if (val.length > 0) {
        $('#btnClearSearch').css('display', 'flex');
      } else {
        $('#btnClearSearch').hide();
      }
    }

    function clearSearchInput() {
      $('#scanInput').val('').focus();
      $('#btnClearSearch').hide();
    }

    function triggerQuickScan() {
      window.scrollTo({ top: 0, behavior: 'smooth' });
      startCameraScanner();
    }

    // -----------------------------------------------------------
    // Load Master Filters (Locations, Polyhouses, Beds)
    // -----------------------------------------------------------
    function loadFilterMasters() {
      $.post('scan_bed_qr_details.php?action=get_filters', { projectid }, function(res) {
        if (res.status === 'success') {
          cachedFilterData = res;
          
          let locOpts = '<option value="">All Locations</option>';
          (res.locations || []).forEach(l => {
            locOpts += `<option value="${esc(l.id)}">${esc(l.name)}</option>`;
          });
          $('#filterLocation').html(locOpts);

          populatePolyhouseOptions();
        }
      }, 'json');
    }

    function populatePolyhouseOptions(selectedLocId = '') {
      let phOpts = '<option value="">All Polyhouses</option>';
      (cachedFilterData.polyhouses || []).forEach(p => {
        if (!selectedLocId || p.locationid === selectedLocId) {
          phOpts += `<option value="${p.id}">${esc(p.name)}</option>`;
        }
      });
      $('#filterPolyhouse').html(phOpts);
      populateBedOptions();
    }

    function populateBedOptions(selectedPhId = 0) {
      let bedOpts = '<option value="">Select Bed</option>';
      if (selectedPhId > 0) {
        const ph = (cachedFilterData.polyhouses || []).find(p => p.id == selectedPhId);
        if (ph && ph.beds) {
          ph.beds.forEach(b => {
            bedOpts += `<option value="${esc(b)}">${esc(b)}</option>`;
          });
        }
      } else {
        const allBeds = new Set();
        (cachedFilterData.polyhouses || []).forEach(p => {
          if (p.beds) p.beds.forEach(b => allBeds.add(b));
        });
        allBeds.forEach(b => {
          bedOpts += `<option value="${esc(b)}">${esc(b)}</option>`;
        });
      }
      $('#filterBed').html(bedOpts);
    }

    function onLocationChange() {
      const locId = $('#filterLocation').val();
      populatePolyhouseOptions(locId);
    }

    function onPolyhouseChange() {
      const phId = parseInt($('#filterPolyhouse').val()) || 0;
      populateBedOptions(phId);
    }

    function onBedSelectChange() {
      const bedNo = $('#filterBed').val();
      if (bedNo) {
        $('#scanInput').val(bedNo);
        onSearchInputChange();
        performScanLookup(bedNo);
      }
    }

    function quickSearch(term) {
      $('#scanInput').val(term);
      onSearchInputChange();
      performScanLookup(term);
    }

    // -----------------------------------------------------------
    // Handle Form Submit
    // -----------------------------------------------------------
    function handleSearchSubmit(e) {
      if (e) e.preventDefault();
      const val = $('#scanInput').val().trim();
      if (!val) {
        Swal.fire({
          icon: 'info',
          title: 'Input Required',
          text: 'Please scan or type a QR barcode, Order No, or select a Bed No.',
          confirmButtonColor: '#4f46e5'
        });
        return;
      }
      performScanLookup(val);
    }

    // -----------------------------------------------------------
    // Core Scan Lookup AJAX Request
    // -----------------------------------------------------------
    function performScanLookup(query) {
      $('#searchSpinner').slideDown(150);
      $('#scanResultsContainer').hide();
      $('#alertBox').hide();

      const locId = $('#filterLocation').val();
      const phId = $('#filterPolyhouse').val();
      const bedNo = $('#filterBed').val();

      $.post('scan_bed_qr_details.php?action=scan_lookup', {
        query: query,
        locationid: locId,
        polyhouseid: phId,
        bed_no: bedNo,
        projectid: projectid
      }, function(res) {
        $('#searchSpinner').hide();
        if (res.status !== 'success') {
          $('#alertTitle').text('Scan Result Notice');
          $('#alertMsg').text(res.message || 'No records found matching this scan.');
          $('#alertBox').slideDown(200);
          return;
        }

        currentBedData = res;
        renderScanResults(res);
        playScanSuccessFeedback();

      }, 'json').fail(function() {
        $('#searchSpinner').hide();
        $('#alertTitle').text('Network Error');
        $('#alertMsg').text('Failed to connect to server. Please verify your connection.');
        $('#alertBox').slideDown(200);
      });
    }

    // -----------------------------------------------------------
    // Render Results
    // -----------------------------------------------------------
    function renderScanResults(data) {
      const ov = data.bed_overview || {};
      
      // 1. Bed Overview & Banner
      $('#resBedBadge').text(ov.bed_no || 'Bed');
      $('#resBedName').text(ov.bed_no || 'Bed');
      $('#resPolyhouseBadge').text(ov.polyhousename || 'Polyhouse');
      $('#resLocationBadge').text(ov.locationname || 'Location');
      $('#resScannedQuery').text(data.query || ov.matched_barcode || ov.matched_stockorder || '-');

      // 2. KPI Metrics
      $('#kpiTotalOrders').text(ov.total_orders || 0);
      $('#kpiTotalTrays').text(ov.total_trays || 0);
      $('#kpiTotalPlants').text((ov.total_plants || 0).toLocaleString());
      $('#kpiReadyTrays').text(ov.ready_to_sale_trays || 0);
      $('#kpiVerifiedTrays').text(ov.verified_trays || 0);
      $('#kpiUnverifiedTrays').text(ov.unverified_trays || 0);

      // 3. Production Orders Cards
      const orders = data.production_orders || [];
      $('#ordersCountBadge').text(`${orders.length} Order(s)`);

      let orderHtml = '';
      if (orders.length === 0) {
        orderHtml = '<div class="text-center py-4 text-muted bg-white rounded-4 border">No active production orders found in this bed.</div>';
      } else {
        orders.forEach(ord => {
          const isMatched = ord.is_scanned_match ? 'scanned-highlight-card' : '';
          const totalPl = ord.total_plants || 0;
          const readyPct = ord.tray_count > 0 ? Math.round((ord.ready_to_sale_trays / ord.tray_count) * 100) : 0;
          const verPct = ord.tray_count > 0 ? Math.round((ord.verified_trays / ord.tray_count) * 100) : 0;

          orderHtml += `
            <div class="order-modern-card ${isMatched}">
              <div class="order-head-bar">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                  <span class="badge text-white fw-bold px-2.5 py-1.5" style="background: var(--primary); font-size: 0.86rem; font-family: var(--font-mono);">
                    ${esc(ord.stockorder_no)}
                  </span>
                  <span class="text-muted font-monospace small">Bill: <strong>${esc(ord.psono)}</strong></span>
                  ${ord.bedqrcode && ord.bedqrcode !== '-' ? `
                    <span class="badge-soft badge-soft-primary font-monospace" title="Bed QR Code">
                      <i class="fa fa-qrcode"></i> ${esc(ord.bedqrcode)}
                    </span>
                  ` : ''}
                  ${ord.is_scanned_match ? `
                    <span class="badge bg-warning text-dark fw-bold px-2 py-1" style="font-size:0.75rem;">
                      <i class="fa fa-bullseye me-1"></i> Scanned Target
                    </span>
                  ` : ''}
                </div>

                <div class="d-flex align-items-center gap-2 flex-wrap">
                  <span class="badge-soft badge-soft-dark"><i class="fa fa-user"></i> ${esc(ord.client_name)}</span>
                  <a href="${esc(ord.print_slip_url)}" target="_blank" class="print-slip-btn" title="Open and print A4 QR Slip">
                    <i class="fa fa-print"></i> Print A4 QR Slip
                  </a>
                </div>
              </div>

              <div class="p-3 p-md-3.5">
                <div class="row g-3">
                  
                  <!-- Product & Variety -->
                  <div class="col-12 col-md-6 col-lg-4">
                    <div class="text-muted small fw-bold text-uppercase" style="font-size: 0.72rem;">
                      <i class="fa fa-leaf text-primary me-1"></i> Product & Variety
                    </div>
                    <div class="fw-bold text-dark fs-6 mt-1">${esc(ord.product_name)}</div>
                    <div class="text-primary fw-bold small">${esc(ord.variety)}</div>
                    
                    <div class="d-flex align-items-center gap-1.5 mt-2 flex-wrap">
                      <span class="badge-soft badge-soft-danger font-monospace" title="Seed Lot">
                        Lot: ${esc(ord.seed_lot_no)}
                      </span>
                      <span class="badge-soft badge-soft-primary">
                        Stage: ${esc(ord.growth_stage)}
                      </span>
                    </div>
                  </div>

                  <!-- Internal Dates Timeline -->
                  <div class="col-12 col-md-6 col-lg-4 border-start-md">
                    <div class="text-muted small fw-bold text-uppercase" style="font-size: 0.72rem;">
                      <i class="fa fa-calendar-alt text-primary me-1"></i> Internal Sowing & Dispatch
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mt-2">
                      <span class="text-muted small">Internal Sowing Date:</span>
                      <span class="fw-bold text-dark font-monospace">${esc(ord.sowingdate_internal)}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                      <span class="text-muted small">Internal Dispatch:</span>
                      <span class="fw-bold text-success font-monospace">${esc(ord.dispatchdate_internal)}</span>
                    </div>
                  </div>

                  <!-- Trays, QTY & Progress Breakdown -->
                  <div class="col-12 col-lg-4 border-start-lg">
                    <div class="text-muted small fw-bold text-uppercase" style="font-size: 0.72rem;">
                      <i class="fa fa-boxes-packing text-primary me-1"></i> Trays & Inventory Meter
                    </div>
                    
                    <div class="d-flex align-items-baseline gap-2 mt-1">
                      <span class="fs-5 fw-bold text-dark font-monospace">${ord.tray_count} Trays</span>
                      <span class="text-muted small">(${totalPl.toLocaleString()} Plants @ ${ord.qtypertray}/tray)</span>
                    </div>

                    <!-- Micro Progress Bar -->
                    <div class="progress mt-2" style="height: 6px; border-radius: 4px; background: var(--slate-100);">
                      <div class="progress-bar bg-success" role="progressbar" style="width: ${readyPct}%;" title="Ready to sale: ${readyPct}%"></div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-1" style="font-size: 0.72rem;">
                      <span class="text-success fw-bold">Ready: ${ord.ready_to_sale_trays} (${readyPct}%)</span>
                      <span class="text-purple fw-bold" style="color: var(--purple);">Verified: ${ord.verified_trays} (${verPct}%)</span>
                      <span class="text-warning fw-bold">Pending: ${ord.unverified_trays}</span>
                    </div>
                  </div>

                </div>
              </div>
            </div>
          `;
        });
      }
      $('#ordersContainer').html(orderHtml);

      // 4. Date-Wise Summary Pills
      const dateSummaries = data.date_wise_summary || [];
      $('#dateGroupCountBadge').text(`${dateSummaries.length} Date Group(s)`);

      let datePillsHtml = '<div class="row g-2">';
      if (dateSummaries.length === 0) {
        datePillsHtml += '<div class="col-12 text-center text-muted py-2 small">No date groupings recorded.</div>';
      } else {
        dateSummaries.forEach(ds => {
          datePillsHtml += `
            <div class="col-12 col-md-6 col-lg-4">
              <div class="timeline-strip-card">
                <div>
                  <div class="small fw-bold text-dark font-monospace">
                    <i class="fa fa-calendar-check text-primary me-1"></i> ${esc(ds.sowing_date)} &nbsp;&rarr;&nbsp; <span class="text-success">${esc(ds.dispatch_date)}</span>
                  </div>
                  <div class="text-muted mt-0.5" style="font-size: 0.78rem;">
                    ${esc(ds.products.join(', '))}
                  </div>
                </div>
                <div class="text-end flex-shrink-0">
                  <span class="badge" style="background: var(--primary-light); color: var(--primary-dark); font-size: 0.82rem; font-family: var(--font-mono); font-weight:700;">
                    ${ds.tray_count} Trays
                  </span>
                </div>
              </div>
            </div>
          `;
        });
      }
      datePillsHtml += '</div>';
      $('#dateWisePillsContainer').html(datePillsHtml);

      // 5. Individual Trays
      const trays = data.trays || [];
      $('#trayTotalCountHeader').text(trays.length);
      renderTrayDetails(trays);

      $('#scanResultsContainer').fadeIn(250);
    }

    // -----------------------------------------------------------
    // Render Individual Trays (Table & Cards)
    // -----------------------------------------------------------
    function renderTrayDetails(trays) {
      let tbodyHtml = '';
      let cardsHtml = '';

      if (!trays || trays.length === 0) {
        tbodyHtml = '<tr><td colspan="11" class="text-center py-4 text-muted">No individual trays found in this bed.</td></tr>';
        cardsHtml = '<div class="col-12 text-center py-4 text-muted">No individual trays found in this bed.</div>';
      } else {
        trays.forEach((t, idx) => {
          const isScanned = t.is_scanned_barcode;
          const rowClass = isScanned ? 'row-highlight-scanned' : '';
          const cardClass = isScanned ? 'scanned-tray' : '';
          const verifyBadge = t.is_verified 
            ? '<span class="badge-soft badge-soft-success"><i class="fa fa-check"></i> Verified</span>'
            : '<span class="badge-soft badge-soft-warning"><i class="fa fa-clock"></i> Unverified</span>';

          // Table Row
          tbodyHtml += `
            <tr class="${rowClass}" data-barcode="${esc(t.barcode)}" data-product="${esc(t.productname)}" data-verified="${t.is_verified ? '1' : '0'}">
              <td class="text-muted small">${idx + 1}</td>
              <td class="font-monospace fw-bold text-dark">
                ${esc(t.barcode)}
                ${isScanned ? '<span class="badge bg-warning text-dark ms-1">Scanned</span>' : ''}
              </td>
              <td class="font-monospace fw-bold text-primary">${esc(t.stockorder_no)}</td>
              <td>
                <div class="fw-bold text-dark">${esc(t.productname)}</div>
                <div class="small text-muted">${esc(t.variety)}</div>
              </td>
              <td class="fw-bold">${t.qty}</td>
              <td class="text-muted font-monospace">${esc(t.tray_position)}</td>
              <td class="font-monospace small">${esc(t.sowing_date)}</td>
              <td class="text-success fw-bold font-monospace small">${esc(t.dispatch_date)}</td>
              <td><span class="badge-soft badge-soft-primary">${esc(t.growth_stage)}</span></td>
              <td>${verifyBadge}</td>
              <td class="small">
                ${t.is_verified ? `<div>${esc(t.verified_at)}</div><div class="text-muted">By: ${esc(t.verified_by)}</div>` : '<span class="text-muted">-</span>'}
              </td>
            </tr>
          `;

          // Mobile Card
          cardsHtml += `
            <div class="col-12 col-sm-6 tray-card-item" data-barcode="${esc(t.barcode)}" data-product="${esc(t.productname)}" data-verified="${t.is_verified ? '1' : '0'}">
              <div class="mobile-tray-card ${cardClass}">
                <div class="d-flex justify-content-between align-items-center mb-1.5">
                  <div class="d-flex align-items-center gap-1.5">
                    <span class="badge bg-dark text-white font-monospace small">#${idx + 1}</span>
                    <span class="font-monospace fw-bold text-dark" style="font-size: 0.95rem;">${esc(t.barcode)}</span>
                    <button type="button" class="btn btn-link p-0 text-muted" onclick="copyBarcode('${esc(t.barcode)}')" title="Copy Barcode">
                      <i class="fa fa-copy"></i>
                    </button>
                  </div>
                  <div>${verifyBadge}</div>
                </div>

                <div class="d-flex justify-content-between align-items-baseline">
                  <div>
                    <span class="badge-soft badge-soft-primary font-monospace">${esc(t.stockorder_no)}</span>
                    <span class="fw-bold text-dark ms-1">${esc(t.productname)}</span>
                    <span class="small text-muted">(${esc(t.variety)})</span>
                  </div>
                  <div class="fw-bold font-monospace text-dark">${t.qty} <span class="text-muted small">plants</span></div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top small">
                  <div>
                    <span class="text-muted">Pos:</span> <strong class="font-monospace">${esc(t.tray_position)}</strong>
                  </div>
                  <div>
                    <span class="text-muted">Sow:</span> <strong class="font-monospace">${esc(t.sowing_date)}</strong>
                  </div>
                  <div>
                    <span class="text-muted">Disp:</span> <strong class="text-success font-monospace">${esc(t.dispatch_date)}</strong>
                  </div>
                </div>

                ${t.is_verified ? `
                  <div class="mt-1.5 text-muted" style="font-size: 0.72rem;">
                    <i class="fa fa-shield-check text-purple me-1"></i> Verified ${esc(t.verified_at)} by ${esc(t.verified_by)}
                  </div>
                ` : ''}
              </div>
            </div>
          `;
        });
      }

      $('#trayDetailsTableBody').html(tbodyHtml);
      $('#trayCardsContainer').html(cardsHtml);

      // Apply active display mode
      updateTrayViewToggleUI();
    }

    function setTrayDisplayMode(mode) {
      trayDisplayMode = mode;
      updateTrayViewToggleUI();
    }

    function updateTrayViewToggleUI() {
      if (trayDisplayMode === 'cards') {
        $('#trayTableViewWrapper').hide();
        $('#trayCardsViewWrapper').show();
        $('#btnViewCards').addClass('active');
        $('#btnViewTable').removeClass('active');
      } else {
        $('#trayCardsViewWrapper').hide();
        $('#trayTableViewWrapper').show();
        $('#btnViewTable').addClass('active');
        $('#btnViewCards').removeClass('active');
      }
    }

    // -----------------------------------------------------------
    // Live Tray Search / Filtering
    // -----------------------------------------------------------
    function filterTrayTable() {
      const q = $('#trayTableFilter').val().toLowerCase().trim();
      const statusF = $('#trayStatusFilter').val();

      // Filter Table Rows
      $('#trayDetailsTableBody tr').each(function() {
        const $el = $(this);
        const barcode = ($el.data('barcode') || '').toString().toLowerCase();
        const product = ($el.data('product') || '').toString().toLowerCase();
        const isVer = ($el.data('verified') || '0').toString();

        let matchesText = true;
        if (q) matchesText = barcode.includes(q) || product.includes(q);

        let matchesStatus = true;
        if (statusF === 'VERIFIED') matchesStatus = (isVer === '1');
        else if (statusF === 'UNVERIFIED') matchesStatus = (isVer === '0');

        $el.toggle(matchesText && matchesStatus);
      });

      // Filter Mobile Cards
      $('#trayCardsContainer .tray-card-item').each(function() {
        const $el = $(this);
        const barcode = ($el.data('barcode') || '').toString().toLowerCase();
        const product = ($el.data('product') || '').toString().toLowerCase();
        const isVer = ($el.data('verified') || '0').toString();

        let matchesText = true;
        if (q) matchesText = barcode.includes(q) || product.includes(q);

        let matchesStatus = true;
        if (statusF === 'VERIFIED') matchesStatus = (isVer === '1');
        else if (statusF === 'UNVERIFIED') matchesStatus = (isVer === '0');

        $el.toggle(matchesText && matchesStatus);
      });
    }

    // -----------------------------------------------------------
    // Live Camera QR Scanner (Html5Qrcode)
    // -----------------------------------------------------------
    function toggleCameraScanner() {
      if (isCameraRunning) {
        stopCameraScanner();
      } else {
        startCameraScanner();
      }
    }

    function startCameraScanner() {
      $('#cameraStandbyBox').hide();
      $('#cameraActiveBox').slideDown(200);
      $('#cameraStatusPill').removeClass('bg-light text-secondary').addClass('bg-success text-white').text('ACTIVE');
      $('#btnHeaderCameraToggle').html('<i class="fa fa-stop"></i> <span class="d-none d-sm-inline">Stop Camera</span>');
      $('#btnActionCameraToggle').html('<i class="fa fa-stop"></i> Stop Camera');

      html5QrCode = new Html5Qrcode("reader");
      const config = { 
        fps: 15, 
        qrbox: function(viewfinderWidth, viewfinderHeight) {
          const minEdge = Math.min(viewfinderWidth, viewfinderHeight);
          const qrboxEdge = Math.floor(minEdge * 0.75);
          return { width: Math.max(220, qrboxEdge), height: Math.max(220, qrboxEdge) };
        } 
      };

      html5QrCode.start(
        { facingMode: currentCameraFacing },
        config,
        onQrCodeScannedSuccess,
        (errorMessage) => { /* ignore per-frame decode errors */ }
      ).then(() => {
        isCameraRunning = true;
      }).catch(err => {
        console.error("Unable to start camera scanner", err);
        Swal.fire({
          icon: 'error',
          title: 'Camera Access Error',
          text: 'Unable to open camera. Please grant camera permission or use USB Barcode scanner.',
          confirmButtonColor: '#4f46e5'
        });
        stopCameraScanner();
      });
    }

    function stopCameraScanner() {
      if (html5QrCode && isCameraRunning) {
        html5QrCode.stop().then(() => {
          html5QrCode.clear();
          isCameraRunning = false;
        }).catch(err => console.error(err));
      }
      $('#cameraActiveBox').slideUp(200);
      $('#cameraStandbyBox').show();
      $('#cameraStatusPill').removeClass('bg-success text-white').addClass('bg-light text-secondary').text('Standby');
      $('#btnHeaderCameraToggle').html('<i class="fa fa-camera"></i> <span class="d-none d-sm-inline">Scan Camera</span>');
      $('#btnActionCameraToggle').html('<i class="fa fa-camera"></i> Live Camera Scan');
      isCameraRunning = false;
    }

    function switchCameraFacing() {
      currentCameraFacing = currentCameraFacing === "environment" ? "user" : "environment";
      if (isCameraRunning) {
        stopCameraScanner();
        setTimeout(startCameraScanner, 300);
      }
    }

    function onQrCodeScannedSuccess(decodedText) {
      if (!decodedText) return;
      $('#scanInput').val(decodedText);
      onSearchInputChange();
      playScanSuccessFeedback();
      stopCameraScanner();
      performScanLookup(decodedText);
    }

    function playScanSuccessFeedback() {
      // 1. Audio Beep
      try {
        const audio = document.getElementById('scanBeepSound');
        if (audio) {
          audio.currentTime = 0;
          audio.play().catch(e => {});
        }
      } catch(e){}

      // 2. Mobile Haptic Vibration
      try {
        if (navigator.vibrate) {
          navigator.vibrate([70]);
        }
      } catch(e){}
    }

    // -----------------------------------------------------------
    // Utilities: Reset, Copy, Escaping
    // -----------------------------------------------------------
    function resetAllViews() {
      $('#scanInput').val('');
      onSearchInputChange();
      $('#filterLocation').val('');
      $('#filterPolyhouse').val('');
      $('#filterBed').val('');
      $('#scanResultsContainer').hide();
      $('#alertBox').hide();
      $('#searchSpinner').hide();
      stopCameraScanner();
    }

    function copyCurrentQuery() {
      const q = $('#resScannedQuery').text().trim();
      copyToClipboard(q, 'Scanned Query copied!');
    }

    function copyBarcode(barcode) {
      copyToClipboard(barcode, `Barcode ${barcode} copied!`);
    }

    function copyToClipboard(text, successMsg) {
      if (!text || text === '-') return;
      navigator.clipboard.writeText(text).then(() => {
        const Toast = Swal.mixin({
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 1600,
          timerProgressBar: true
        });
        Toast.fire({
          icon: 'success',
          title: successMsg || 'Copied to clipboard!'
        });
      }).catch(err => {
        console.error('Copy failed', err);
      });
    }

    function esc(str) {
      if (str === null || str === undefined) return '';
      return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }
  </script>
</body>
</html>

