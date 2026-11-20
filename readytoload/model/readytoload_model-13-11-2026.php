<?php
/**
 * Ready To Load - Backend Model & AJAX Controller
 * Table Source: `karjeefarm`.`krf_sales_order_items`
 * Path: /readytoload/model/readytoload_model.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(1);
ini_set('display_errors', '0');
date_default_timezone_set('Asia/Kolkata');

require_once(__DIR__ . '/../../connection.php');
mysqli_report(MYSQLI_REPORT_OFF);

set_exception_handler(function(Throwable $e) {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode([
        'status' => 'error',
        'message' => 'Server Error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
});

header('Content-Type: application/json; charset=utf-8');

$loginid      = $_SESSION['loginid'] ?? $_SESSION['userid'] ?? 'admin';
$loginname    = $_SESSION['loginname'] ?? $_SESSION['username'] ?? $_SESSION['name'] ?? 'Admin';
$loginroleid  = (int)($_SESSION['loginroleid'] ?? 4);
$companyid    = (int)($_SESSION['companyid'] ?? 1);
$subcompanyid = (int)($_SESSION['subcompanyid'] ?? 1);
$projectid    = (int)($_SESSION['projectid'] ?? 17);
if ($projectid <= 0) $projectid = 17;

$action = trim($_REQUEST['action'] ?? '');

function outJson($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Map polyhouse id to name
function getPolyhouseNameMap($krfconn) {
    static $polyMap = null;
    if ($polyMap !== null) return $polyMap;
    $polyMap = [];
    $res = $krfconn->query("SELECT polyhouseid, polyhousename, physicallocation FROM krf_polyhouse_master");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $name = trim($r['polyhousename'] ?: ('PH-' . $r['polyhouseid']));
            if (!empty($r['physicallocation'])) {
                $name .= ' (' . trim($r['physicallocation']) . ')';
            }
            $polyMap[$r['polyhouseid']] = $name;
            $polyMap[strval($r['polyhouseid'])] = $name;
        }
    }
    return $polyMap;
}

// Helper: Extract clean normalized variety token from product name or variety column
function extractNormalizedVariety($productName, $variety = '') {
    $v = trim($variety ?: '');
    if (!empty($v) && strcasecmp($v, 'N/A') !== 0 && strcasecmp($v, '-') !== 0 && strcasecmp($v, '0') !== 0) {
        return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $v));
    }
    if (preg_match('/\(([^)]+)\)/', $productName, $m)) {
        return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $m[1]));
    }
    if (strpos($productName, ',') !== false) {
        $parts = explode(',', $productName);
        return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', end($parts)));
    }
    if (strpos($productName, '-') !== false) {
        $parts = explode('-', $productName);
        return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', end($parts)));
    }
    return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $productName));
}

// Helper: Check if two crops have the same variety
function isSameCropVariety($targetProduct, $targetVariety, $trayProduct, $trayVariety) {
    $normTarget = extractNormalizedVariety($targetProduct, $targetVariety);
    $normTray   = extractNormalizedVariety($trayProduct, $trayVariety);

    if (!empty($normTarget) && !empty($normTray)) {
        if ($normTarget === $normTray) return true;
        if (strlen($normTarget) >= 4 && strlen($normTray) >= 4) {
            if (strpos($normTarget, $normTray) !== false || strpos($normTray, $normTarget) !== false) return true;
        }
    }

    $cleanTrayV = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $trayVariety));
    $cleanTargetP = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $targetProduct));
    if (!empty($cleanTrayV) && strlen($cleanTrayV) >= 4 && strpos($cleanTargetP, $cleanTrayV) !== false) {
        return true;
    }

    $cleanTargetV = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $targetVariety));
    $cleanTrayP = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $trayProduct));
    if (!empty($cleanTargetV) && strlen($cleanTargetV) >= 4 && strpos($cleanTrayP, $cleanTargetV) !== false) {
        return true;
    }

    if (!empty($cleanTargetP) && !empty($cleanTrayP) && $cleanTargetP === $cleanTrayP) {
        return true;
    }

    return false;
}

// Helper: Fetch Delivery / Dispatch date for an order or batch
function getOrderDeliveryDate($conn, $psono, $stockorder = '') {
    if (empty($psono) && empty($stockorder)) return null;
    $psoEsc = mysqli_real_escape_string($conn, $psono);
    $stkEsc = mysqli_real_escape_string($conn, $stockorder);
    
    // Check sales order items
    $q = $conn->query("
        SELECT deliverydate 
        FROM krf_sales_order_items 
        WHERE (psono = '{$psoEsc}' OR (stockorder_no = '{$stkEsc}' AND '{$stkEsc}' != ''))
          AND deliverydate IS NOT NULL AND deliverydate != '0000-00-00'
        ORDER BY id ASC LIMIT 1
    ");
    if ($q && $r = $q->fetch_assoc()) {
        return $r['deliverydate'];
    }
    return null;
}

// Helper: Get active tray details in a specific bed and stockorder
function getBedActiveTrayDetails($conn, $stockOrder, $bedNo, $polyhouseId = 0, $targetPsono = '') {
    $escStock = mysqli_real_escape_string($conn, $stockOrder);
    $escBed = mysqli_real_escape_string($conn, $bedNo);
    $escTgtPso = mysqli_real_escape_string($conn, $targetPsono);
    $polyCond = $polyhouseId > 0 ? "AND polyhouseid = " . (int)$polyhouseId : "";

    $sql = "
        SELECT 
            COUNT(*) as total_trays,
            SUM(GREATEST(0, COALESCE(NULLIF(qtypertray, ''), 126) - COALESCE(failurecount, 0))) as total_plants,
            SUM(CASE 
                WHEN LOWER(TRIM(status)) IN ('final dispatch', 'dispatched', 'fulldispatch', 'tray dead', 'removed', 'empty after shifting', 'tray free after shifting', 'empty after grafting', 'remove after over age') THEN 1 
                ELSE 0 
            END) as dead_or_dispatched_trays,
            SUM(CASE 
                WHEN LOWER(TRIM(status)) IN ('loaded', 'readytodispatch', 'ready to dispatch') THEN 1 
                ELSE 0 
            END) as loaded_trays,
            SUM(CASE 
                WHEN LOWER(TRIM(status)) IN ('polyhouse', 'readytosale', 'ready to sale', 'ready to sell')
                 AND LOWER(TRIM(status)) NOT IN ('empty after shifting', 'tray free after shifting', 'empty after grafting', 'tray dead', 'removed', 'final dispatch', 'dispatched')
                 AND (dispd_agst_psono IS NULL OR dispd_agst_psono = '' " . (!empty($escTgtPso) ? "OR dispd_agst_psono = '{$escTgtPso}'" : "") . ") THEN 1 
                ELSE 0 
            END) as available_active_trays,
            SUM(CASE 
                WHEN LOWER(TRIM(status)) IN ('polyhouse', 'readytosale', 'ready to sale', 'ready to sell')
                 AND LOWER(TRIM(status)) NOT IN ('empty after shifting', 'tray free after shifting', 'empty after grafting', 'tray dead', 'removed', 'final dispatch', 'dispatched')
                 AND (dispd_agst_psono IS NULL OR dispd_agst_psono = '' " . (!empty($escTgtPso) ? "OR dispd_agst_psono = '{$escTgtPso}'" : "") . ") THEN GREATEST(0, COALESCE(NULLIF(qtypertray, ''), 126) - COALESCE(failurecount, 0)) 
                ELSE 0 
            END) as available_active_plants
        FROM krf_polyhouse_plant_details
        WHERE (stockorder_no = '{$escStock}' OR dispd_agst_stockorderno = '{$escStock}')
          AND (bed_no = '{$escBed}' OR REPLACE(LOWER(bed_no), ' ', '') = REPLACE(LOWER('{$escBed}'), ' ', ''))
          {$polyCond}
    ";
    $res = $conn->query($sql);
    if ($res && $row = $res->fetch_assoc()) {
        return [
            'total_trays'             => (int)($row['total_trays'] ?? 0),
            'total_plants'            => (int)($row['total_plants'] ?? 0),
            'dead_dispatched_trays'   => (int)($row['dead_or_dispatched_trays'] ?? 0),
            'loaded_trays'            => (int)($row['loaded_trays'] ?? 0),
            'available_active_trays'  => (int)($row['available_active_trays'] ?? 0),
            'available_active_plants' => (int)($row['available_active_plants'] ?? 0)
        ];
    }
    return [
        'total_trays'             => 0,
        'total_plants'            => 0,
        'dead_dispatched_trays'   => 0,
        'loaded_trays'            => 0,
        'available_active_trays'  => 0,
        'available_active_plants' => 0
    ];
}

// Helper: Fetch origin batch farmer details, delivery date, stock status, and multi-client dispatch breakdown
function getBatchAllocationAndDispatchSummary($conn, $originStockOrder, $originPso = '') {
    $escStock = mysqli_real_escape_string($conn, $originStockOrder);
    $escPso = mysqli_real_escape_string($conn, $originPso);

    // 1. Resolve origin client details & delivery date
    $clientInfo = [];
    if (!empty($escStock) || !empty($escPso)) {
        $psoCond = !empty($escPso) ? "OR soi.psono = '{$escPso}'" : "";
        $qClient = $conn->query("
            SELECT 
                COALESCE(NULLIF(TRIM(soi.clientid), ''), NULLIF(TRIM(pso.clientid), ''), NULLIF(TRIM(so.clientid), ''), '') as clientid,
                COALESCE(NULLIF(TRIM(so.clientname), ''), NULLIF(TRIM(pso.clientname), ''), NULLIF(TRIM(cm.client_name), ''), 'Unknown Farmer') as clientname,
                COALESCE(NULLIF(TRIM(pso.mobileno), ''), NULLIF(TRIM(so.mobileno), ''), NULLIF(TRIM(cm.phones), ''), '') as phone,
                COALESCE(NULLIF(TRIM(soi.deliverydate), '0000-00-00'), NULLIF(TRIM(pso.expecteddelivery), '0000-00-00'), '') as deliverydate,
                soi.quantity as order_qty
            FROM krf_sales_order_items soi
            LEFT JOIN krf_sales_order so ON (soi.sono = so.sono OR (NULLIF(TRIM(soi.psono), '') IS NOT NULL AND soi.psono = so.psono))
            LEFT JOIN krf_provisional_sales_order pso ON (soi.psono = pso.psono)
            LEFT JOIN krf_client_master cm ON (soi.clientid = cm.client_id OR pso.clientid = cm.client_id)
            WHERE (soi.stockorder_no = '{$escStock}' {$psoCond})
            ORDER BY soi.id ASC LIMIT 1
        ");
        if ($qClient && $qClient->num_rows > 0) {
            $clientInfo = $qClient->fetch_assoc();
        }
    }

    $delivDate = $clientInfo['deliverydate'] ?? '';
    $formattedDeliv = '';
    if (!empty($delivDate) && $delivDate !== '0000-00-00') {
        $ts = strtotime($delivDate);
        $formattedDeliv = $ts ? date('d-M-Y', $ts) : $delivDate;
    }

    // 2. Query all trays for this batch in krf_polyhouse_plant_details
    $totalTrays = 0;
    $totalPlants = 0;
    $availableTrays = 0;
    $dispatchedTrays = 0;
    $loadedTrays = 0;
    $clientDispatches = [];

    if (!empty($escStock)) {
        $qTrays = $conn->query("
            SELECT 
                status,
                COALESCE(NULLIF(TRIM(dispd_agst_clientid), ''), '') as disp_clientid,
                COALESCE(NULLIF(TRIM(dispd_agst_psono), ''), '') as disp_psono,
                COUNT(*) as tray_count,
                SUM(COALESCE(NULLIF(qtypertray, ''), 126)) as plant_count
            FROM krf_polyhouse_plant_details
            WHERE stockorder_no = '{$escStock}'
            GROUP BY status, disp_clientid, disp_psono
        ");

        $clientNameCache = [];
        if (!empty($clientInfo['clientid'])) {
            $clientNameCache[$clientInfo['clientid']] = $clientInfo['clientname'];
        }

        if ($qTrays) {
            while ($r = $qTrays->fetch_assoc()) {
                $stLower = strtolower(trim($r['status']));
                $tc = (int)$r['tray_count'];
                $pc = (int)$r['plant_count'];
                $totalTrays += $tc;
                $totalPlants += $pc;

                $isDisp = in_array($stLower, ['final dispatch', 'dispatched']);
                $isLd = in_array($stLower, ['loaded', 'readytodispatch', 'ready to dispatch']);

                if ($isDisp) {
                    $dispatchedTrays += $tc;
                } elseif ($isLd) {
                    $loadedTrays += $tc;
                } else {
                    $availableTrays += $tc;
                }

                // Track dispatched or loaded trays
                if ($isDisp || $isLd) {
                    $targetCId = $r['disp_clientid'] ?: ($clientInfo['clientid'] ?? '');
                    $targetPso = $r['disp_psono'] ?: ($originPso ?: '');

                    $targetCName = 'Unknown Client';
                    if (!empty($targetCId)) {
                        if (!isset($clientNameCache[$targetCId])) {
                            $qCn = $conn->query("SELECT COALESCE(NULLIF(TRIM(client_name), ''), client_id) as cname FROM krf_client_master WHERE client_id = '{$targetCId}' LIMIT 1");
                            $clientNameCache[$targetCId] = ($qCn && $cRow = $qCn->fetch_assoc()) ? $cRow['cname'] : $targetCId;
                        }
                        $targetCName = $clientNameCache[$targetCId];
                    } elseif (!empty($targetPso)) {
                        $qPsoCn = $conn->query("SELECT COALESCE(NULLIF(TRIM(clientname), ''), clientid) as cname FROM krf_provisional_sales_order WHERE psono = '{$targetPso}' LIMIT 1");
                        $targetCName = ($qPsoCn && $pRow = $qPsoCn->fetch_assoc()) ? $pRow['cname'] : 'Order ' . $targetPso;
                    }

                    $key = $targetCId . '_' . $targetPso . '_' . ($isDisp ? 'Dispatched' : 'Loaded');
                    if (!isset($clientDispatches[$key])) {
                        $clientDispatches[$key] = [
                            'client_id'   => $targetCId,
                            'client_name' => $targetCName,
                            'psono'       => $targetPso,
                            'status'      => $isDisp ? 'Dispatched' : 'Loaded',
                            'tray_count'  => 0,
                            'plant_count' => 0,
                            'is_original' => (!empty($clientInfo['clientid']) && $targetCId === $clientInfo['clientid'])
                        ];
                    }
                    $clientDispatches[$key]['tray_count'] += $tc;
                    $clientDispatches[$key]['plant_count'] += $pc;
                }
            }
        }
    }

    return [
        'origin_client_id'         => $clientInfo['clientid'] ?? '',
        'origin_client_name'       => $clientInfo['clientname'] ?? 'Unknown Farmer',
        'origin_client_phone'      => $clientInfo['phone'] ?? '',
        'origin_delivery_date'     => $formattedDeliv,
        'origin_order_qty'         => (int)($clientInfo['order_qty'] ?? 0),
        'total_batch_trays'        => $totalTrays,
        'total_batch_plants'       => $totalPlants,
        'available_trays'          => $availableTrays,
        'already_dispatched_trays' => $dispatchedTrays,
        'already_loaded_trays'     => $loadedTrays,
        'dispatches'               => array_values($clientDispatches)
    ];
}

// Helper: Resolve client/farmer name for a given PSONO or Client ID
if (!function_exists('getClientNameByPso')) {
function getClientNameByPso($conn, $pso, $clientId = '') {
    $pso = trim($pso ?? '');
    $clientId = trim($clientId ?? '');
    if (empty($pso) && empty($clientId)) {
        return 'Unknown Client';
    }

    // 1. If clientId is provided, check krf_client_master first
    if (!empty($clientId)) {
        $escCId = mysqli_real_escape_string($conn, $clientId);
        $q = $conn->query("SELECT COALESCE(NULLIF(TRIM(client_name), ''), client_id) as cname FROM krf_client_master WHERE client_id = '{$escCId}' LIMIT 1");
        if ($q && $row = $q->fetch_assoc()) {
            if (!empty(trim($row['cname'] ?? ''))) {
                return trim($row['cname']);
            }
        }
    }

    // 2. Lookup by psono across provisional order, sales order, invoice, and client master
    if (!empty($pso)) {
        $escPso = mysqli_real_escape_string($conn, $pso);
        $q = $conn->query("
            SELECT 
                COALESCE(
                    NULLIF(TRIM(pso.clientname), ''), 
                    NULLIF(TRIM(so.clientname), ''), 
                    NULLIF(TRIM(si.clientname), ''), 
                    NULLIF(TRIM(cm.client_name), ''),
                    ''
                ) as cname
            FROM (SELECT '{$escPso}' as psono) t
            LEFT JOIN krf_provisional_sales_order pso ON pso.psono = t.psono
            LEFT JOIN krf_sales_order so ON (so.psono = t.psono OR so.sono = t.psono)
            LEFT JOIN sales_invoice si ON si.psono = t.psono
            LEFT JOIN krf_client_master cm ON (cm.client_id = pso.clientid OR cm.client_id = so.clientid)
            LIMIT 1
        ");
        if ($q && $row = $q->fetch_assoc()) {
            if (!empty(trim($row['cname'] ?? ''))) {
                return trim($row['cname']);
            }
        }
    }

    return !empty($clientId) ? $clientId : (!empty($pso) ? "Client ({$pso})" : "Other Client");
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

    // Priority: Check krf_readystock_allocation for exact allocated trays & plants
    $qRsa = $krfconn->query("
        SELECT allocate_ordqty, healthyplant, allocatedbed, isshifted, shiftingqty
        FROM krf_readystock_allocation
        WHERE psono = '{$escPso}' AND LOWER(IFNULL(status, '')) != 'cancel' AND (tedit IS NULL OR tedit = '')
        ORDER BY id DESC LIMIT 1
    ");
    if ($qRsa && $rRsa = $qRsa->fetch_assoc()) {
        $rsaAllocQty = (int)($rRsa['allocate_ordqty'] ?? $rRsa['healthyplant'] ?? 0);
        if ($rsaAllocQty > 0) {
            $reqPlants = $rsaAllocQty;
        }
        $bedArr = json_decode($rRsa['allocatedbed'] ?? '', true);
        $estOrderTrays = ($reqPlants > 0) ? (int)ceil($reqPlants / $cap) : 0;
        if (is_array($bedArr) && !empty($bedArr)) {
            $sumBeds = 0;
            foreach ($bedArr as $b) {
                $sumBeds += (int)($b['tray_count'] ?? 0);
            }
            if ($sumBeds > 0 && $sumBeds <= ceil($estOrderTrays * 1.25)) {
                $reqTrays = $sumBeds;
            } else {
                $reqTrays = $estOrderTrays;
            }
        }
        if ($reqTrays <= 0 && $reqPlants > 0) {
            $reqTrays = (int)ceil($reqPlants / $cap);
        }
    }

    if ($alreadyCount >= $reqTrays && $reqTrays > 0) {
        return ['status' => 'already_loaded', 'loaded' => $alreadyCount, 'required' => $reqTrays];
    }

    $traysNeeded = max(0, $reqTrays - $alreadyCount);
    if ($traysNeeded <= 0) return ['status' => 'no_trays_needed'];

    $escBatches = "'" . implode("','", array_map(function($b) use ($krfconn) { return mysqli_real_escape_string($krfconn, $b); }, $batches)) . "'";

    // Select active trays from krf_polyhouse_plant_details for the RSO/SO batch
    $sql = "
        SELECT polyhouseplantid, barcode, polyhouseid, bed_no, productname, productid, stockorder_no, qtypertray, status, failurecount
        FROM krf_polyhouse_plant_details
        WHERE (stockorder_no IN ({$escBatches}) OR dispd_agst_stockorderno IN ({$escBatches}) OR psono IN ({$escBatches}))
          AND status IN ('readytosale', 'ReadyToSale', 'Ready to sale', 'polyhouse', 'Polyhouse')
          AND status NOT IN ('final dispatch', 'dispatched', 'fulldispatch', 'Final Dispatch', 'Dispatched', 'FullDispatch', 'tray dead', 'removed', 'empty after shifting', 'tray free after shifting', 'empty after grafting', 'remove after over age')
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

if ($action === 'get_orders') {
    $searchQuery     = trim($_REQUEST['search'] ?? '');
    $fromDate        = trim($_REQUEST['from_date'] ?? '');
    $toDate          = trim($_REQUEST['to_date'] ?? '');
    $statusFilter    = trim($_REQUEST['status'] ?? $_REQUEST['filter_status'] ?? 'readytoload');
    $yearFilter      = trim($_REQUEST['year'] ?? 'all');
    $polyhouseFilter = trim($_REQUEST['polyhouse'] ?? '');
    $cropFilter      = trim($_REQUEST['crop'] ?? '');
    $mismatchOnly    = (int)($_REQUEST['mismatch_only'] ?? 0);

    $whereClauses = [];

    // Exclude non-crop packing charges always
    $whereClauses[] = "(sii.productid != 'BOX_PACKING' AND sii.productname NOT LIKE '%Box Packing Charges%')";

    // Status filter: readytoload (default), loaded, dispatched, all
    $sFilterLower = strtolower($statusFilter);
    if ($sFilterLower === 'loaded') {
        $whereClauses[] = "(
            LOWER(TRIM(si.status)) IN ('readytodispatch', 'loaded') 
            OR LOWER(TRIM(sii.status)) IN ('readytodispatch', 'loaded') 
            OR LOWER(TRIM(COALESCE(soi.actionstatus, ''))) = 'loaded'
        )";
    } elseif ($sFilterLower === 'dispatched') {
        $whereClauses[] = "(
            LOWER(TRIM(si.status)) IN ('dispatched', 'fulldispatch', 'final dispatch') 
            OR LOWER(TRIM(sii.status)) IN ('dispatched', 'fulldispatch', 'final dispatch')
        )";
    } elseif ($sFilterLower === 'all') {
        $whereClauses[] = "(
            LOWER(TRIM(COALESCE(si.status, ''))) NOT IN ('cancelled', 'rejected') 
            AND LOWER(TRIM(COALESCE(sii.status, ''))) NOT IN ('cancelled', 'rejected')
        )";
    } else {
        // default: readytoload - strictly exclude any dispatched or cancelled invoices/items
        $whereClauses[] = "(
            (LOWER(TRIM(si.status)) = 'readytoload' OR LOWER(TRIM(sii.status)) = 'readytoload')
            AND LOWER(TRIM(COALESCE(si.status, ''))) NOT IN ('dispatched', 'fulldispatch', 'final dispatch', 'cancelled', 'rejected')
            AND LOWER(TRIM(COALESCE(sii.status, ''))) NOT IN ('dispatched', 'fulldispatch', 'final dispatch', 'cancelled', 'rejected')
        )";
    }

    // Exclude replaced / archived invoice & invoice items (marked with tedit)
    $whereClauses[] = "(si.tedit IS NULL OR si.tedit = '')";
    $whereClauses[] = "(sii.tedit IS NULL OR sii.tedit = '')";

    // Year filter: as per invoice dates or delivery dates
    if (!empty($yearFilter) && strtolower($yearFilter) !== 'all') {
        $safeYear = (int)$yearFilter;
        if ($safeYear > 2000) {
            $whereClauses[] = "(YEAR(si.invoicedate) = {$safeYear} OR YEAR(sii.deliverydate) = {$safeYear})";
        }
    }

    // Polyhouse filter
    if (!empty($polyhouseFilter)) {
        $safePh = mysqli_real_escape_string($krfconn, $polyhouseFilter);
        $whereClauses[] = "(
            sii.psono IN (
                SELECT DISTINCT psono FROM krf_polyhouse_plant_details 
                WHERE polyhouseid = '{$safePh}' AND psono IS NOT NULL AND psono != ''
            )
        )";
    }

    // Crop / Variety filter
    if (!empty($cropFilter)) {
        $safeCrop = mysqli_real_escape_string($krfconn, $cropFilter);
        $whereClauses[] = "(sii.productname LIKE '%{$safeCrop}%')";
    }

    // Search query: psono, sono, invoiceno, clientname, productname, productid, stockorder_no
    if (!empty($searchQuery)) {
        $safeSearch = mysqli_real_escape_string($krfconn, $searchQuery);
        $whereClauses[] = "(
            sii.psono LIKE '%$safeSearch%' 
            OR sii.sono LIKE '%$safeSearch%' 
            OR sii.invoiceno LIKE '%$safeSearch%'
            OR si.invoiceno LIKE '%$safeSearch%'
            OR sii.productname LIKE '%$safeSearch%' 
            OR sii.productid LIKE '%$safeSearch%'
            OR sii.stockorder_no LIKE '%$safeSearch%' 
            OR si.clientname LIKE '%$safeSearch%'
        )";
    }

    // Date range filter (on invoicedate or deliverydate)
    if (!empty($fromDate)) {
        $safeFrom = mysqli_real_escape_string($krfconn, $fromDate);
        $whereClauses[] = "(si.invoicedate >= '$safeFrom' OR sii.deliverydate >= '$safeFrom')";
    }
    if (!empty($toDate)) {
        $safeTo = mysqli_real_escape_string($krfconn, $toDate);
        $whereClauses[] = "(si.invoicedate <= '$safeTo' OR sii.deliverydate <= '$safeTo')";
    }

    $whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

    // Pre-fetch box packing summary (if any) grouped by invoiceno / psono
    $boxPackingMap = [];
    $bpSql = "
        SELECT invoiceno, psono, SUM(quantity) as box_count 
        FROM sales_invoice_items 
        WHERE (productid = 'BOX_PACKING' OR productname LIKE '%Box Packing Charges%')
          AND LOWER(TRIM(COALESCE(status, ''))) NOT IN ('cancelled', 'rejected')
        GROUP BY invoiceno, psono
    ";
    $bpRes = $krfconn->query($bpSql);
    if ($bpRes) {
        while ($bpRow = $bpRes->fetch_assoc()) {
            $invKey = trim($bpRow['invoiceno'] ?? '');
            $pKey = trim($bpRow['psono'] ?? '');
            if (!empty($invKey)) $boxPackingMap[$invKey] = (int)$bpRow['box_count'];
            if (!empty($pKey) && !isset($boxPackingMap[$pKey])) $boxPackingMap[$pKey] = (int)$bpRow['box_count'];
        }
    }

    // 1. Optimized Main Query with primary key join
    $sql = "
        SELECT 
            sii.id AS sii_id,
            sii.invoice_id,
            sii.invoiceno,
            si.invoicedate,
            si.scheduled_dispatch_date,
            sii.psono,
            sii.sono,
            sii.productid AS product_id,
            sii.product_sku,
            sii.productname AS product_name,
            sii.stockorder_no,
            sii.original_soi_id,
            sii.quantity AS invoice_item_qty,
            COALESCE(NULLIF(soi.quantity, 0), sii.quantity) AS original_order_qty,
            sii.quantity,
            sii.rate,
            sii.taxableamount,
            sii.amount,
            sii.healthyqty,
            sii.unhealthyqty,
            sii.shiftingqty,
            sii.total_dispatch_plants,
            sii.status AS sii_status,
            si.status AS si_status,
            si.status,
            COALESCE(NULLIF(TRIM(si.clientname), ''), NULLIF(TRIM(soi.clientid), ''), 'Valued Client') AS display_client_name,
            COALESCE(NULLIF(TRIM(si.clientid), ''), NULLIF(TRIM(soi.clientid), ''), '') AS resolved_client_id,
            COALESCE(NULLIF(TRIM(si.mobileno), ''), '') AS client_phone,
            COALESCE(NULLIF(TRIM(si.delivery_address), ''), '') AS delivery_address,
            COALESCE(NULLIF(TRIM(si.districtname), ''), '') AS districtname,
            COALESCE(NULLIF(TRIM(si.statename), ''), '') AS statename,
            COALESCE(NULLIF(TRIM(sii.deliverydate), '0000-00-00'), NULLIF(TRIM(si.scheduled_dispatch_date), '0000-00-00'), si.invoicedate) AS deliverydate,
            COALESCE(soi.id, sii.original_soi_id, sii.id) AS id,
            COALESCE(soi.reservedqty, sii.quantity) AS reservedqty,
            soi.balancereservedqty,
            COALESCE(soi.allocate_ordqty, sii.quantity) AS allocate_ordqty,
            soi.isshifted,
            COALESCE(soi.healthyplant, sii.healthyqty) AS healthyplant,
            COALESCE(soi.unhealthyplant, sii.unhealthyqty) AS unhealthyplant,
            soi.dispatchedqty,
            soi.actionstatus
        FROM sales_invoice_items sii
        INNER JOIN sales_invoice si ON sii.invoice_id = si.id
        LEFT JOIN krf_sales_order_items soi ON (
            (sii.original_soi_id > 0 AND sii.original_soi_id = soi.id) 
            OR ((sii.original_soi_id IS NULL OR sii.original_soi_id = 0) AND soi.psono = sii.psono AND soi.product_id = sii.productid AND (soi.tedit IS NULL OR soi.tedit = ''))
        )
        $whereSql
        ORDER BY si.id DESC, sii.id ASC
    ";

    $res = $krfconn->query($sql);
    if (!$res) {
        outJson(['status' => 'error', 'message' => 'Database Query Error: ' . $krfconn->error]);
    }

    $allRows = [];
    $psoList = [];
    $allStockList = [];
    while ($r = $res->fetch_assoc()) {
        $allRows[] = $r;
        $p = trim($r['psono'] ?? '');
        if (!empty($p)) $psoList[$p] = $p;
        $stk = trim($r['stockorder_no'] ?? '');
        if (!empty($stk)) {
            foreach (explode(',', $stk) as $s) {
                $s = trim($s);
                if (!empty($s) && $s !== '-' && $s !== '0' && $s !== 'Not Allocated') {
                    $allStockList[$s] = $s;
                }
            }
        }
    }

    $escPsoArr = array_map(function($p) use ($krfconn) { return mysqli_real_escape_string($krfconn, $p); }, array_values($psoList));
    $escPsos = !empty($escPsoArr) ? "'" . implode("','", $escPsoArr) . "'" : "''";

    // 2. Batch Dispatched Invoice Quantities strictly for matched PSONOs
    $invoiceDispMap = [];
    if (!empty($psoList)) {
        $invDispSql = "
            SELECT 
                sii.original_soi_id,
                sii.stockorder_no,
                sii.psono,
                sii.productid,
                COALESCE(SUM(sii.quantity), 0) AS total_disp_plants
            FROM sales_invoice_items sii
            INNER JOIN sales_invoice si ON sii.invoice_id = si.id
            WHERE sii.psono IN ($escPsos)
              AND (LOWER(TRIM(sii.status)) IN ('dispatched', 'fulldispatch', 'final dispatch') OR LOWER(TRIM(si.status)) IN ('dispatched', 'fulldispatch', 'final dispatch'))
              AND sii.productid != 'BOX_PACKING'
              AND sii.productname NOT LIKE '%Box Packing Charges%'
            GROUP BY sii.original_soi_id, sii.stockorder_no, sii.psono, sii.productid
        ";
        $invDispRes = $krfconn->query($invDispSql);
        if ($invDispRes) {
            while ($dRow = $invDispRes->fetch_assoc()) {
                $psoKey = trim($dRow['psono']);
                $stkKey = trim($dRow['stockorder_no'] ?? '');
                $origIdKey = (int)($dRow['original_soi_id'] ?? 0);
                $prodKey = trim($dRow['productid'] ?? '');
                $qtyVal = (int)$dRow['total_disp_plants'];

                if ($origIdKey > 0) {
                    $invoiceDispMap['soi_' . $origIdKey] = ($invoiceDispMap['soi_' . $origIdKey] ?? 0) + $qtyVal;
                }
                if (!empty($stkKey) && $stkKey !== '-' && $stkKey !== '0' && $stkKey !== 'Not Allocated') {
                    $invoiceDispMap['stk_' . $psoKey . '_' . $stkKey] = ($invoiceDispMap['stk_' . $psoKey . '_' . $stkKey] ?? 0) + $qtyVal;
                }
                $invoiceDispMap['prod_' . $psoKey . '_' . $prodKey] = ($invoiceDispMap['prod_' . $psoKey . '_' . $prodKey] ?? 0) + $qtyVal;
            }
        }
    }

    // 3. Batch Active Ready Stock Allocations (krf_readystock_allocation)
    $rsaByPso = [];
    $rsaByPsoProdId = [];
    $rsaByPsoProdName = [];
    $rsaByPsoStock = [];
    $rsaByPsoProdStock = [];
    if (!empty($psoList)) {
        $rsaQ = $krfconn->query("
            SELECT * 
            FROM krf_readystock_allocation 
            WHERE (tedit IS NULL OR tedit = '') 
              AND status = 'allocated' 
              AND psono IN ($escPsos)
            ORDER BY id ASC
        ");
        if ($rsaQ) {
            while ($r = $rsaQ->fetch_assoc()) {
                $p = trim($r['psono']);
                $pid = trim(strval($r['productid'] ?? ''));
                $pname = trim(strval($r['productname'] ?? ''));
                $rsaByPso[$p] = $r;
                if ($pid !== '') $rsaByPsoProdId[$p . '_' . $pid] = $r;
                if ($pname !== '') $rsaByPsoProdName[$p . '_' . $pname] = $r;
                $astk = trim($r['allocate_stockorder_no'] ?: ($r['stockorder_no'] ?: ''));
                if (!empty($astk) && $astk !== '-' && $astk !== '0') {
                    $allStockList[$astk] = $astk;
                    $rsaByPsoStock[$p . '_' . $astk] = $r;
                    if ($pid !== '') $rsaByPsoProdStock[$p . '_' . $pid . '_' . $astk] = $r;
                }
            }
        }
    }

    // 4. Batch Production Order Consumed (krf_productionorder_consumed)
    $pocByPso = [];
    $pocByPsoProdId = [];
    $pocByPsoProdName = [];
    if (!empty($psoList)) {
        $pocQ = $krfconn->query("
            SELECT original_stockorder_no, original_orderqty, allocate_healthyplant, allocate_unhealthyplant, 
                   isshifted, percentextrashifting, extraqtyforshifting, consumed_qty, stockorderno, 
                   allocated_stockorderno, allocated_psono, remark, allocatedby, allocatedat, dispatchedqty,
                   psono, product_id, product_name
            FROM krf_productionorder_consumed 
            WHERE psono IN ($escPsos) 
              AND IFNULL(tedit, '') = ''
            ORDER BY id ASC
        ");
        if ($pocQ) {
            while ($pRow = $pocQ->fetch_assoc()) {
                $p = trim($pRow['psono']);
                $pid = trim(strval($pRow['product_id'] ?? ''));
                $pname = trim(strval($pRow['product_name'] ?? ''));
                $pocByPso[$p][] = $pRow;
                if ($pid !== '') $pocByPsoProdId[$p . '_' . $pid][] = $pRow;
                if ($pname !== '') $pocByPsoProdName[$p . '_' . $pname][] = $pRow;
                $bAlloc = trim($pRow['allocated_stockorderno'] ?: ($pRow['stockorderno'] ?: ''));
                if (!empty($bAlloc) && $bAlloc !== '-' && $bAlloc !== '0' && $bAlloc !== 'Not Allocated') {
                    $allStockList[$bAlloc] = $bAlloc;
                }
            }
        }
    }

    // 5. Batch Loaded Trays from Polyhouse & Ready to Sale
    $loadedTraysByPso = [];
    if (!empty($psoList)) {
        $batchLoadedSql = "
            SELECT 
                t.matched_pso,
                t.stockorder_no,
                t.dispd_agst_stockorderno,
                t.productname,
                t.bed_no,
                t.polyhouseid,
                t.barcode,
                t.unique_tray_id,
                t.tray_plants
            FROM (
                SELECT 
                    CASE WHEN dispd_agst_psono IN ($escPsos) THEN dispd_agst_psono ELSE psono END as matched_pso,
                    stockorder_no,
                    dispd_agst_stockorderno,
                    productname,
                    bed_no,
                    polyhouseid,
                    barcode,
                    CAST(COALESCE(NULLIF(barcode, ''), CONCAT('ID_', polyhouseplantid)) AS CHAR(100)) as unique_tray_id,
                    MAX(COALESCE(NULLIF(qtypertray, ''), 126)) as tray_plants
                FROM krf_polyhouse_plant_details
                WHERE (dispd_agst_psono IN ($escPsos) OR psono IN ($escPsos))
                  AND status IN ('Loaded', 'ReadyToDispatch', 'readytodispatch', 'loaded', 'Ready to dispatch')
                GROUP BY matched_pso, stockorder_no, dispd_agst_stockorderno, productname, bed_no, polyhouseid, barcode, unique_tray_id
                UNION
                SELECT 
                    CASE WHEN dispd_agst_psono IN ($escPsos) THEN dispd_agst_psono ELSE psono END as matched_pso,
                    stockorder_no,
                    dispd_agst_stockorderno,
                    productname,
                    '' as bed_no,
                    0 as polyhouseid,
                    barcode,
                    CAST(COALESCE(NULLIF(barcode, ''), CONCAT('RTS_', id)) AS CHAR(100)) as unique_tray_id,
                    MAX(COALESCE(NULLIF(qtypertray, ''), 126)) as tray_plants
                FROM krf_readytosale_stocks
                WHERE (dispd_agst_psono IN ($escPsos) OR psono IN ($escPsos))
                  AND status IN ('Loaded', 'ReadyToDispatch', 'readytodispatch', 'loaded', 'Ready to dispatch')
                GROUP BY matched_pso, stockorder_no, dispd_agst_stockorderno, productname, barcode, unique_tray_id
            ) t
        ";
        $bRes = $krfconn->query($batchLoadedSql);
        if ($bRes) {
            while ($lr = $bRes->fetch_assoc()) {
                $p = $lr['matched_pso'];
                $loadedTraysByPso[$p][] = $lr;
            }
        }
    }

    // 6. Batch Active Bed Locations
    $activeBedsByPso = [];
    if (!empty($psoList)) {
        $activeBedSql = "
            SELECT 
                CASE WHEN dispd_agst_psono IN ($escPsos) THEN dispd_agst_psono ELSE psono END as matched_pso,
                stockorder_no,
                productname,
                bed_no,
                polyhouseid,
                COUNT(*) as tray_count,
                COALESCE(qtypertray, 126) as capacity
            FROM krf_polyhouse_plant_details
            WHERE (psono IN ($escPsos) OR dispd_agst_psono IN ($escPsos))
              AND status IN ('readytosale', 'ReadyToSale', 'Ready to sale', 'polyhouse', 'Polyhouse')
              AND status NOT IN ('final dispatch', 'dispatched', 'fulldispatch', 'Final Dispatch', 'Dispatched', 'FullDispatch', 'removed', 'empty after shifting', 'tray free after shifting', 'empty after grafting', 'remove after over age', 'tray dead')
            GROUP BY matched_pso, stockorder_no, productname, bed_no, polyhouseid
        ";
        $abRes = $krfconn->query($activeBedSql);
        if ($abRes) {
            while ($abr = $abRes->fetch_assoc()) {
                $activeBedsByPso[$abr['matched_pso']][] = $abr;
            }
        }
    }

    // 7. Batch RTS Counts
    $rtsCountsByPso = [];
    if (!empty($psoList)) {
        $rtsBatchSql = "
            SELECT 
                CASE WHEN dispd_agst_psono IN ($escPsos) THEN dispd_agst_psono ELSE psono END as matched_pso,
                stockorder_no,
                productname,
                COUNT(*) as rts_trays,
                COALESCE(SUM(qtypertray), 0) as rts_plants
            FROM krf_polyhouse_plant_details
            WHERE (psono IN ($escPsos) OR dispd_agst_psono IN ($escPsos))
              AND status IN ('readytosale', 'ReadyToSale', 'Ready to sale', 'ready to sell')
            GROUP BY matched_pso, stockorder_no, productname
        ";
        $rtsBatchRes = $krfconn->query($rtsBatchSql);
        if ($rtsBatchRes) {
            while ($rr = $rtsBatchRes->fetch_assoc()) {
                $rtsCountsByPso[$rr['matched_pso']][] = $rr;
            }
        }
    }

    // 8. Batch Polyhouse Beds by Stock Batch
    $stockBedsByBatch = [];
    $stockList = array_values(array_filter($allStockList));
    if (!empty($stockList)) {
        $escBatches = "'" . implode("','", array_map(function($b) use ($krfconn) { return mysqli_real_escape_string($krfconn, $b); }, $stockList)) . "'";
        $bBedQ = $krfconn->query("
            SELECT 
                stockorder_no,
                polyhouseid, 
                bed_no, 
                COUNT(*) as tray_count, 
                COALESCE(SUM(COALESCE(qtypertray, 126) - COALESCE(failurecount, 0)), 0) as net_plants,
                COALESCE(qtypertray, 126) as capacity
            FROM krf_polyhouse_plant_details
            WHERE stockorder_no IN ($escBatches)
              AND status IN ('polyhouse', 'Polyhouse', 'readytosale', 'ReadyToSale', 'ready to sale', 'ready to sell')
              AND status NOT IN ('final dispatch', 'dispatched', 'fulldispatch', 'Final Dispatch', 'Dispatched', 'FullDispatch', 'removed', 'empty after shifting', 'tray free after shifting', 'empty after grafting', 'remove after over age', 'tray dead')
              AND IFNULL(tedit, '') = ''
            GROUP BY stockorder_no, bed_no, polyhouseid
            ORDER BY tray_count DESC
        ");
        if ($bBedQ) {
            while ($br = $bBedQ->fetch_assoc()) {
                $stockBedsByBatch[$br['stockorder_no']][] = $br;
            }
        }
    }

    // 9. Batch Undo History
    $undoHistByPso = [];
    if (!empty($psoList)) {
        $undoHistQ = $krfconn->query("
            SELECT psono, transaction_no, activity_type, reason_for_movement 
            FROM krf_traymovement_history 
            WHERE psono IN ($escPsos) 
            ORDER BY movement_id DESC
        ");
        if ($undoHistQ) {
            while ($uRow = $undoHistQ->fetch_assoc()) {
                $p = $uRow['psono'];
                if (!isset($undoHistByPso[$p])) {
                    $undoHistByPso[$p] = $uRow;
                }
            }
        }
    }

    $polyMap = getPolyhouseNameMap($krfconn);

    // Group rows by Invoice Number / PSONO
    $grouped = [];
    $seenPsoProductItems = [];
    $totalOverallPlants = 0;
    $totalOverallTrays = 0;
    $totalOverallAllocatedPlants = 0;
    $totalOverallAllocatedTrays = 0;
    $totalLoadedTraysCount = 0;
    $userIdsToResolve = [];

    foreach ($allRows as $row) {
        $psoKey = trim($row['psono'] ?: ($row['invoiceno'] ?: ('NO-PSO-' . $row['id'])));
        
        $psoParam = trim($row['psono'] ?? '');
        $prodIdParam = trim(strval($row['product_id'] ?? ''));
        $prodNameParam = trim(strval($row['product_name'] ?? ''));
        $siiStock = trim($row['stockorder_no'] ?? '');

        // Reset all item-level variables for clean iteration across products
        $effectiveStockNo = '';
        $origStock = '';
        $allocStock = '';
        $isSwapped = false;
        $rsaRow = null;
        $activeRsaStockNo = '';
        $activeRsaProdId = '';
        $activeRsaProdName = '';

        // Deduplication: prevent the same product item from showing multiple times under the same order card
        // Note: Query is ORDER BY si.id DESC, sii.id ASC, so the latest active invoice item is processed first
        $psoProdDedupeKey = $psoKey . '_' . (!empty($prodIdParam) ? $prodIdParam : $prodNameParam);
        if (isset($seenPsoProductItems[$psoProdDedupeKey])) {
            continue;
        }
        $seenPsoProductItems[$psoProdDedupeKey] = true;

        // Active allocation lookup from prefetched RSA (product-specific isolation)
        if (!empty($psoParam)) {
            if (!empty($prodIdParam) && !empty($siiStock) && isset($rsaByPsoProdStock[$psoParam . '_' . $prodIdParam . '_' . $siiStock])) {
                $rsaRow = $rsaByPsoProdStock[$psoParam . '_' . $prodIdParam . '_' . $siiStock];
            } elseif (!empty($prodIdParam) && isset($rsaByPsoProdId[$psoParam . '_' . $prodIdParam])) {
                $rsaRow = $rsaByPsoProdId[$psoParam . '_' . $prodIdParam];
            } elseif (!empty($prodNameParam) && isset($rsaByPsoProdName[$psoParam . '_' . $prodNameParam])) {
                $rsaRow = $rsaByPsoProdName[$psoParam . '_' . $prodNameParam];
            } elseif (!empty($siiStock) && isset($rsaByPsoStock[$psoParam . '_' . $siiStock])) {
                $rsaRow = $rsaByPsoStock[$psoParam . '_' . $siiStock];
            } elseif (isset($rsaByPso[$psoParam])) {
                $rsaRow = $rsaByPso[$psoParam];
            }
            if ($rsaRow) {
                $activeRsaStockNo = trim($rsaRow['allocate_stockorder_no'] ?: ($rsaRow['stockorder_no'] ?: ''));
                $activeRsaProdId = trim(strval($rsaRow['productid'] ?? ''));
                $activeRsaProdName = trim(strval($rsaRow['productname'] ?? ''));
            }
        }

        // Fetch all allocated batches from prefetched POC
        $allocatedBatches = [];
        $pocAllAllocatedStockNos = [];
        $pocConsumedSum = 0;
        $pocRemark = '';
        $pocAllocatedBy = '';
        $pocAllocatedAt = '';
        $pocIsShifted = 0;
        $pocExtraShifting = 0;
        $pocDispatchedQty = 0;
        $pocOrigStock = '';
        $pocHealthy = 0;

        $pocList = [];
        if (!empty($psoParam)) {
            if (!empty($prodIdParam) && isset($pocByPsoProdId[$psoParam . '_' . $prodIdParam])) {
                $pocList = $pocByPsoProdId[$psoParam . '_' . $prodIdParam];
            } elseif (!empty($prodNameParam) && isset($pocByPsoProdName[$psoParam . '_' . $prodNameParam])) {
                $pocList = $pocByPsoProdName[$psoParam . '_' . $prodNameParam];
            } elseif (isset($pocByPso[$psoParam])) {
                $pocList = $pocByPso[$psoParam];
            }

            foreach ($pocList as $pRow) {
                $bAlloc = trim($pRow['allocated_stockorderno'] ?: ($pRow['stockorderno'] ?: ''));
                $bConsumed = (float)($pRow['consumed_qty'] ?? 0);
                $pocConsumedSum += $bConsumed;
                if (!empty($pRow['original_stockorder_no']) && empty($pocOrigStock)) {
                    $pocOrigStock = trim($pRow['original_stockorder_no']);
                }
                if (!empty($pRow['remark'])) $pocRemark = $pRow['remark'];
                if (!empty($pRow['allocatedby'])) $pocAllocatedBy = $pRow['allocatedby'];
                if (!empty($pRow['allocatedat'])) $pocAllocatedAt = $pRow['allocatedat'];
                if ((int)($pRow['isshifted'] ?? 0) === 1) $pocIsShifted = 1;
                if ((int)($pRow['extraqtyforshifting'] ?? 0) > 0) $pocExtraShifting = (int)$pRow['extraqtyforshifting'];
                if ((int)($pRow['dispatchedqty'] ?? 0) > 0) $pocDispatchedQty += (int)$pRow['dispatchedqty'];
                if ((int)($pRow['allocate_healthyplant'] ?? 0) > 0) $pocHealthy = (int)$pRow['allocate_healthyplant'];

                if (!empty($bAlloc) && $bAlloc !== 'Not Allocated' && $bAlloc !== '0' && $bAlloc !== '-') {
                    $pocAllAllocatedStockNos[] = $bAlloc;
                    $foundExistingBatch = false;
                    foreach ($allocatedBatches as &$exAb) {
                        if ($exAb['stockorder_no'] === $bAlloc) {
                            $exAb['allocated_qty'] += $bConsumed;
                            $exAb['consumed_qty'] += $bConsumed;
                            $foundExistingBatch = true;
                            break;
                        }
                    }
                    unset($exAb);
                    if (!$foundExistingBatch) {
                        $allocatedBatches[] = [
                            'stockorder_no' => $bAlloc,
                            'allocated_qty' => $bConsumed,
                            'consumed_qty'  => $bConsumed,
                            'psono'         => $pRow['allocated_psono'] ?? '',
                            'remark'        => $pRow['remark'] ?? '',
                            'allocatedby'   => $pRow['allocatedby'] ?? '',
                            'allocatedat'   => $pRow['allocatedat'] ?? ''
                        ];
                    }
                }
            }
        }

        // Priority to active allocation in krf_readystock_allocation (strictly isolates product allocation)
        if (!empty($activeRsaStockNo)) {
            $pocAllAllocatedStockNos = [$activeRsaStockNo];
            $bAllocQty = (float)($rsaRow['allocate_ordqty'] ?: ($rsaRow['healthyplant'] ?: $row['quantity']));
            $allocatedBatches = [[
                'stockorder_no' => $activeRsaStockNo,
                'allocated_qty' => $bAllocQty,
                'consumed_qty'  => $bAllocQty,
                'psono'         => $psoParam,
                'remark'        => $rsaRow['teditremark'] ?? '',
                'allocatedby'   => $rsaRow['allocatedby'] ?? $pocAllocatedBy,
                'allocatedat'   => $rsaRow['allocatedat'] ?? $pocAllocatedAt
            ]];
        }

        // Fallback for comma-separated stockorder_no if no poc records were found
        if (empty($allocatedBatches) && !empty($siiStock)) {
            $parts = array_filter(array_map('trim', explode(',', $siiStock)));
            foreach ($parts as $p) {
                if ($p !== 'Not Allocated' && $p !== '-' && $p !== '0') {
                    $pocAllAllocatedStockNos[] = $p;
                    $allocatedBatches[] = [
                        'stockorder_no' => $p,
                        'allocated_qty' => 0,
                        'consumed_qty'  => 0,
                        'psono'         => '',
                        'remark'        => ''
                    ];
                }
            }
        }

        $pocAllAllocatedStockNos = array_values(array_unique(array_filter($pocAllAllocatedStockNos)));
        $origStock = !empty($pocOrigStock) ? $pocOrigStock : trim($row['stockorder_no'] ?: '');
        $allocStock = !empty($pocAllAllocatedStockNos) ? implode(', ', $pocAllAllocatedStockNos) : trim($row['stockorder_no'] ?: '');
        if (empty($allocStock)) $allocStock = $origStock;

        // Swapped check: allocated stock differs from original sown stock
        $isSwapped = false;
        if (!empty($origStock) && $origStock !== 'N/A' && $origStock !== '-' && $origStock !== '0') {
            foreach ($pocAllAllocatedStockNos as $stkNo) {
                if ($stkNo !== $origStock && $stkNo !== '0' && $stkNo !== '-') {
                    $isSwapped = true;
                    break;
                }
            }
        }
        $effectiveStockNo = !empty($allocStock) ? $allocStock : $origStock;

        if (!empty($pocAllocatedBy)) {
            $userIdsToResolve[] = trim($pocAllocatedBy);
        }

        $rawInvDate = $row['invoicedate'] ?? '';
        $formattedInvDate = (!empty($rawInvDate) && $rawInvDate !== '0000-00-00') ? (strtotime($rawInvDate) ? date('d-M-Y', strtotime($rawInvDate)) : $rawInvDate) : '-';
        $invNumStr = trim($row['invoiceno'] ?? '');

        if (!isset($grouped[$psoKey])) {
            $rawDelivery = $row['deliverydate'] ?: ($rawInvDate ?: date('Y-m-d'));
            $formattedDelivery = '';
            if (!empty($rawDelivery) && $rawDelivery !== '0000-00-00') {
                $ts = strtotime($rawDelivery);
                $formattedDelivery = $ts ? date('d-M-Y', $ts) : $rawDelivery;
            }

            $grouped[$psoKey] = [
                'group_key'              => $psoKey,
                'invoiceno'              => $invNumStr,
                'invoicedate'            => $rawInvDate,
                'formatted_invoicedate'  => $formattedInvDate,
                'invoices'               => [],
                'invoice_list'           => [],
                'psono'                  => $row['psono'],
                'sono'                   => $row['sono'],
                'stockorder_no'          => $effectiveStockNo ?: ($row['stockorder_no'] ?: ''),
                'effective_stockorder_no'=> $effectiveStockNo ?: ($row['stockorder_no'] ?: ''),
                'original_stockorder_no' => $origStock,
                'allocated_stockorder_no'=> $allocStock,
                'allocated_batches'      => $allocatedBatches,
                'is_swapped'             => $isSwapped,
                'allocation_remark'      => $pocRemark,
                'allocated_by_id'        => $pocAllocatedBy,
                'allocated_by_name'      => '',
                'allocated_at'           => $pocAllocatedAt,
                'formatted_allocated_at' => '',
                'box_packing_count'      => $boxPackingMap[$invNumStr] ?? ($boxPackingMap[$row['psono']] ?? 0),
                'clientid'               => $row['resolved_client_id'] ?: $row['clientid'],
                'clientname'             => $row['display_client_name'] ?: 'Valued Client',
                'phone'                  => $row['client_phone'],
                'address'                => $row['delivery_address'],
                'district'               => $row['districtname'],
                'state'                  => $row['statename'],
                'deliverydate'           => $formattedDelivery,
                'items'                  => [],
                'total_order_plants'     => 0,
                'total_order_trays'      => 0,
                'total_allocated_plants' => 0,
                'total_allocated_trays'  => 0,
                'total_ready_to_sell_plants' => 0,
                'total_ready_to_sell_trays'  => 0,
                'total_loaded_plants'    => 0,
                'total_loaded_trays'     => 0,
                'total_dispatched_plants'=> 0,
                'total_dispatched_trays' => 0,
                'is_all_loaded'          => true,
            ];
        } else {
            if ($isSwapped) {
                $grouped[$psoKey]['is_swapped'] = true;
            }
            foreach ($allocatedBatches as $ab) {
                $existsInGroup = false;
                foreach ($grouped[$psoKey]['allocated_batches'] as $gab) {
                    if ($gab['stockorder_no'] === $ab['stockorder_no']) {
                        $existsInGroup = true;
                        break;
                    }
                }
                if (!$existsInGroup) {
                    $grouped[$psoKey]['allocated_batches'][] = $ab;
                }
            }
            $allGrpStocks = array_filter(array_unique(array_merge(
                array_map('trim', explode(',', $grouped[$psoKey]['stockorder_no'])),
                array_map('trim', explode(',', $effectiveStockNo))
            )));
            if (!empty($allGrpStocks)) {
                $grouped[$psoKey]['stockorder_no'] = implode(', ', $allGrpStocks);
                $grouped[$psoKey]['effective_stockorder_no'] = implode(', ', $allGrpStocks);
                $grouped[$psoKey]['allocated_stockorder_no'] = implode(', ', $allGrpStocks);
            }
            if (!empty($pocRemark)) $grouped[$psoKey]['allocation_remark'] = $pocRemark;
            if (!empty($pocAllocatedBy)) $grouped[$psoKey]['allocated_by_id'] = $pocAllocatedBy;
            if (!empty($pocAllocatedAt)) $grouped[$psoKey]['allocated_at'] = $pocAllocatedAt;
        }

        if (!empty($invNumStr) && !isset($grouped[$psoKey]['invoices'][$invNumStr])) {
            $grouped[$psoKey]['invoices'][$invNumStr] = [
                'invoiceno'             => $invNumStr,
                'invoicedate'           => $rawInvDate,
                'formatted_invoicedate' => $formattedInvDate
            ];
        }

        if (empty($grouped[$psoKey]['stockorder_no']) && !empty($effectiveStockNo)) {
            $grouped[$psoKey]['stockorder_no'] = $effectiveStockNo;
            $grouped[$psoKey]['effective_stockorder_no'] = $effectiveStockNo;
        }

        // Collect all distinct batch numbers relevant to this item
        $itemBatches = array_values(array_unique(array_filter(array_merge(
            $pocAllAllocatedStockNos,
            array_filter(array_map('trim', explode(',', $effectiveStockNo))),
            array_filter(array_map('trim', explode(',', $origStock))),
            array_filter(array_map('trim', explode(',', $siiStock)))
        ))));

        $loadedTrays = 0;
        $loadedPlants = 0;
        $dispatchedTrays = 0;
        $dispatchedPlants = 0;
        $activeBedList = [];

        $clientIdParam = trim($row['resolved_client_id'] ?? ($row['clientid'] ?? ''));

        $qty = (int)$row['quantity'];
        $originalOrderQty = (int)($row['original_order_qty'] ?? $qty);
        $isShiftedOrderRow = ($row['isshifted'] === 'yes' || $row['isshifted'] === '1' || $row['isshifted'] === 1 || $pocIsShifted == 1);
        $trayCapDivisor = $isShiftedOrderRow ? 125 : 126;
        $estTrays = ($qty > 0) ? (int)ceil($qty / $trayCapDivisor) : 0;
        if ($estTrays < 1 && $qty > 0) $estTrays = 1;
        $originalOrderTrays = ($originalOrderQty > 0) ? (int)ceil($originalOrderQty / $trayCapDivisor) : $estTrays;

        // Dispatched status for this invoice line item is strictly determined by sales_invoice_items / sales_invoice status
        $isItemDispatched = (
            in_array(strtolower(trim($row['sii_status'] ?? '')), ['dispatched', 'fulldispatch', 'final dispatch']) 
            || in_array(strtolower(trim($row['si_status'] ?? ($row['status'] ?? ''))), ['dispatched', 'fulldispatch', 'final dispatch'])
        );

        // If user is viewing Ready to Load tab, fully dispatched items must not be displayed
        if ($sFilterLower === 'readytoload' && $isItemDispatched) {
            continue;
        }
        $origSoiId = (int)($row['original_soi_id'] ?? 0);
        $stkVal = trim($row['stockorder_no'] ?? '');
        $priorDispatchedPlants = 0;
        if ($origSoiId > 0 && isset($invoiceDispMap['soi_' . $origSoiId])) {
            $priorDispatchedPlants = (int)$invoiceDispMap['soi_' . $origSoiId];
        } elseif (!empty($stkVal) && isset($invoiceDispMap['stk_' . $psoParam . '_' . $stkVal])) {
            $priorDispatchedPlants = (int)$invoiceDispMap['stk_' . $psoParam . '_' . $stkVal];
        }
        $priorDispatchedTrays = ($priorDispatchedPlants > 0) ? (int)ceil($priorDispatchedPlants / $trayCapDivisor) : 0;

        if ($isItemDispatched) {
            $dispatchedPlants = $qty;
            $dispatchedTrays = ($qty > 0) ? (int)ceil($qty / $trayCapDivisor) : 0;
            $loadedTrays = 0;
            $loadedPlants = 0;
        } else {
            // For a pending / readytoload invoice item, its own dispatched count is 0
            $dispatchedPlants = 0;
            $dispatchedTrays = 0;
        }

        // In-memory loaded trays calculation from prefetched batch
        $itemLoadedTraysList = [];
        if (!empty($psoParam) && !$isItemDispatched) {
            $allLoadedForPso = $loadedTraysByPso[$psoParam] ?? [];
            $seenUniqueTrayIds = [];
            foreach ($allLoadedForPso as $tRow) {
                $uId = $tRow['unique_tray_id'];
                if (isset($seenUniqueTrayIds[$uId])) continue;

                $matchesStock = false;
                if (!empty($itemBatches)) {
                    $tStk = trim($tRow['stockorder_no'] ?? '');
                    $tDisp = trim($tRow['dispd_agst_stockorderno'] ?? '');
                    $tProd = trim($tRow['productname'] ?? '');
                    if (in_array($tStk, $itemBatches) || in_array($tDisp, $itemBatches) || (!empty($prodNameParam) && $tProd === $prodNameParam)) {
                        $matchesStock = true;
                    }
                } else {
                    if (empty($prodNameParam) || trim($tRow['productname'] ?? '') === $prodNameParam) {
                        $matchesStock = true;
                    }
                }

                if ($matchesStock) {
                    $seenUniqueTrayIds[$uId] = true;
                    $loadedTrays++;
                    $loadedPlants += (int)($tRow['tray_plants'] ?: 126);
                    $itemLoadedTraysList[] = $tRow;
                }
            }

            // AUTO-MARK RSO / SO ORDERS: If 0 loaded trays, auto-mark active trays from RSO/SO batch as Loaded
            $isRsoStock = preg_match('/^(RSO|SO)-/i', $effectiveStockNo) || preg_match('/^(RSO|SO)-/i', $origStock) || preg_match('/^(RSO|SO)-/i', $row['stockorder_no'] ?? '');
            $isManuallyUndone = (strtolower(trim($row['actionstatus'] ?? '')) === 'unloaded');
            if (!$isManuallyUndone && !empty($psoParam) && $isRsoStock) {
                $uRow = $undoHistByPso[$psoParam] ?? null;
                if ($uRow) {
                    if (strpos($uRow['transaction_no'] ?? '', 'UNDO-') === 0 || stripos($uRow['reason_for_movement'] ?? '', 'undo') !== false) {
                        $isManuallyUndone = true;
                    }
                }
            }

            if ($loadedTrays == 0 && $isRsoStock && !$isManuallyUndone) {
                $targetStk = !empty($effectiveStockNo) ? $effectiveStockNo : (!empty($origStock) ? $origStock : ($row['stockorder_no'] ?? ''));
                autoMarkRsoTraysAsLoaded($krfconn, $psoParam, $targetStk, (int)$row['quantity'], $clientIdParam, $_SESSION['username'] ?? 'system', $row['invoiceno'] ?? '');
                
                // Re-query loaded trays so UI displays accurate loaded count immediately
                $escPso = mysqli_real_escape_string($krfconn, $psoParam);
                $chkLoadedSql = "
                    SELECT 
                        COUNT(DISTINCT t.unique_tray_id) as loadedtray,
                        COALESCE(SUM(t.tray_plants), 0) as loadedplants
                    FROM (
                        SELECT 
                            CAST(COALESCE(NULLIF(barcode, ''), CONCAT('ID_', polyhouseplantid)) AS CHAR(100)) as unique_tray_id,
                            MAX(COALESCE(NULLIF(qtypertray, ''), 126)) as tray_plants
                        FROM krf_polyhouse_plant_details
                        WHERE (dispd_agst_psono = '{$escPso}' OR (psono = '{$escPso}' AND (dispd_agst_psono IS NULL OR dispd_agst_psono = '' OR dispd_agst_psono = '{$escPso}')))
                          AND status IN ('Loaded', 'ReadyToDispatch', 'readytodispatch', 'loaded', 'Ready to dispatch')
                        GROUP BY unique_tray_id
                    ) t
                ";
                $chkRes = $krfconn->query($chkLoadedSql);
                if ($chkRes && $chkRow = $chkRes->fetch_assoc()) {
                    $loadedTrays = (int)($chkRow['loadedtray'] ?? 0);
                    $loadedPlants = (int)($chkRow['loadedplants'] ?? 0);
                }
            }
        }

        // Active bed locations from prefetched batch
        $typicalCapacity = 126;
        $allBedsForPso = $activeBedsByPso[$psoParam] ?? [];
        $bedCountLimit = 0;
        foreach ($allBedsForPso as $br) {
            if ($bedCountLimit >= 15) break;
            $matchesBed = false;
            if (!empty($itemBatches) && in_array(trim($br['stockorder_no']), $itemBatches)) {
                $matchesBed = true;
            } elseif (empty($prodNameParam) || trim($br['productname']) === $prodNameParam) {
                $matchesBed = true;
            }
            if ($matchesBed) {
                $bedCountLimit++;
                $phName = $polyMap[$br['polyhouseid']] ?? ('PH ' . $br['polyhouseid']);
                $activeBedList[] = [
                    'bed_no'     => $br['bed_no'] ?: 'Bed-?',
                    'polyhouse'  => $phName,
                    'tray_count' => (int)$br['tray_count']
                ];
                if ((int)$br['capacity'] > 0) {
                    $typicalCapacity = (int)$br['capacity'];
                }
            }
        }

        if ($typicalCapacity > 0 && !$isShiftedOrderRow) {
            $trayCapDivisor = $typicalCapacity;
            $estTrays = ($qty > 0) ? (int)ceil($qty / $trayCapDivisor) : 0;
            if ($estTrays < 1 && $qty > 0) $estTrays = 1;
            if ($isItemDispatched) {
                $dispatchedTrays = ($dispatchedPlants > 0) ? (int)ceil($dispatchedPlants / $trayCapDivisor) : 0;
            }
        }

        // Calculate total active bed plants and trays
        $activeBedTrays = 0;
        foreach ($activeBedList as $b) {
            $activeBedTrays += (int)$b['tray_count'];
        }

        // Calculate Allocated Quantity: prioritize customer order's allocate_ordqty / pocConsumedSum
        $soiAlloc    = (int)($row['allocate_ordqty'] ?? 0);
        $orderQty    = (int)($row['quantity'] ?? 0);

        if ($soiAlloc > 0) {
            $allocatedPlants = $soiAlloc;
        } elseif ($pocConsumedSum > 0) {
            $allocatedPlants = (int)$pocConsumedSum;
        } elseif ($pocHealthy > 0 && ($orderQty <= 0 || $pocHealthy <= $orderQty)) {
            $allocatedPlants = $pocHealthy;
        } else {
            $allocatedPlants = $orderQty;
        }
        $allocatedTrays = ($allocatedPlants > 0) ? (int)ceil($allocatedPlants / $trayCapDivisor) : $estTrays;

        // Fetch Ready to Sell trays and plants count from prefetched batch
        $readyToSellTrays = 0;
        $readyToSellPlants = 0;
        $allRtsForPso = $rtsCountsByPso[$psoParam] ?? [];
        foreach ($allRtsForPso as $rr) {
            $matchesRts = false;
            if (!empty($itemBatches) && in_array(trim($rr['stockorder_no']), $itemBatches)) {
                $matchesRts = true;
            } elseif (empty($prodNameParam) || trim($rr['productname']) === $prodNameParam) {
                $matchesRts = true;
            }
            if ($matchesRts) {
                $readyToSellTrays += (int)$rr['rts_trays'];
                $readyToSellPlants += (int)$rr['rts_plants'];
            }
        }

        // Target to load is based on allocated qty if available, otherwise total order qty
        $targetToLoadTrays = ($allocatedTrays > 0) ? $allocatedTrays : $estTrays;
        $targetToLoadPlants = $targetToLoadTrays * 126; // Physical capacity of all trays combined (126 per tray)

        // Fetch Shifted and Unshifted plant counts from krf_readystock_allocation or fallback
        $rsaIsShifted = null;
        $rsaHealthy = 0;
        $rsaPercentExtra = 0;
        $rsaShiftingQty = 0;
        $shiftedPlantsCount = 0;
        $unshiftedPlantsCount = 0;
        $rsaAllocatedBedRaw = '';
        $rsaAllocatedBedDetail = [];
        $rsaSumTrayCount = 0;
        $rsaSumPlants = 0;
        $rsaBedArray = [];

        $invoiceNeedsUpdate = false;
        $invoiceUpdateMsg = '';

        if ($rsaRow) {
            if (empty($activeRsaStockNo)) {
                $activeRsaStockNo = trim($rsaRow['allocate_stockorder_no'] ?: ($rsaRow['stockorder_no'] ?: ''));
            }
            if (empty($activeRsaProdId)) {
                $activeRsaProdId = trim(strval($rsaRow['productid'] ?? ''));
            }
            if (empty($activeRsaProdName)) {
                $activeRsaProdName = trim(strval($rsaRow['productname'] ?? ''));
            }

            $rsaAllocatedBedRaw = strval($rsaRow['allocatedbed'] ?? '');
            if (!empty($rsaAllocatedBedRaw)) {
                $decodedBeds = json_decode($rsaAllocatedBedRaw, true);
                if (is_array($decodedBeds)) {
                    $rsaBedArray = $decodedBeds;
                    foreach ($decodedBeds as $bItem) {
                        if (isset($bItem['tray_count'])) {
                            $rsaSumTrayCount += (int)$bItem['tray_count'];
                        }
                        if (isset($bItem['plants'])) {
                            $rsaSumPlants += (int)$bItem['plants'];
                        }
                    }
                }
            }

            $rsaHealthy = (int)($rsaRow['allocate_ordqty'] ?? 0);
            if ($rsaHealthy <= 0) {
                $rsaHealthy = (int)($rsaRow['healthyplant'] ?? 0);
            }
            if ($allocatedPlants > 0 && $rsaHealthy > $allocatedPlants) {
                $rsaHealthy = $allocatedPlants;
            }
            $rsaPercentExtra = (float)($rsaRow['percentextrashifting'] ?? 0);
            $rsaShiftingQty = (int)($rsaRow['shiftingqty'] ?? 0);

            $isShiftedVal = ($rsaRow['isshifted'] == 1 || $rsaRow['isshifted'] === '1' || strtolower(strval($rsaRow['isshifted'])) === 'yes' || $isShiftedOrderRow);
            $rsaIsShifted = $isShiftedVal ? 'yes' : 'no';
            $currCapDivisor = $isShiftedVal ? 125 : 126;

            // Determine order's allocated quantity strictly bounded by provisional/sales order
            $orderAllocPlants = ($soiAlloc > 0) ? $soiAlloc : (($pocConsumedSum > 0) ? (int)$pocConsumedSum : $orderQty);
            if ($orderAllocPlants <= 0) {
                $orderAllocPlants = $orderQty;
            }
            $targetOrderWithExtra = $isShiftedVal ? $orderAllocPlants : ($orderAllocPlants + $rsaShiftingQty);
            $expectedOrderTrays = ($targetOrderWithExtra > 0) ? (int)ceil($targetOrderWithExtra / $currCapDivisor) : $estTrays;
            $isRsaSpecificToThisOrder = (!empty($rsaRow['psono']) && trim($rsaRow['psono']) === $psoParam);

            // Priority: Sum tray_count from allocatedbed JSON array if present AND specific to this order (not full bed stock)
            if ($isRsaSpecificToThisOrder && $rsaSumTrayCount > 0 && $rsaSumTrayCount <= ceil($expectedOrderTrays * 1.25)) {
                $allocatedTrays = $rsaSumTrayCount;
                $allocatedPlants = ($rsaSumPlants > 0 && $rsaSumPlants <= ceil($orderAllocPlants * 1.25)) ? $rsaSumPlants : $orderAllocPlants;
            } else {
                // If array data represents full nursery bed (e.g. 156 trays for 18,900 plants), calculate strictly as per allocated order quantity:
                $allocatedPlants = $orderAllocPlants;
                $allocatedTrays = $expectedOrderTrays;
            }

            $targetToLoadTrays = $allocatedTrays;
            $targetToLoadPlants = $allocatedPlants;

            if ($isShiftedVal) {
                $shiftedPlantsCount = ($rsaHealthy > 0 && $rsaHealthy <= $allocatedPlants) ? $rsaHealthy : $allocatedPlants;
                $unshiftedPlantsCount = 0;
            } else {
                $shiftedPlantsCount = 0;
                $unshiftedPlantsCount = (($rsaHealthy > 0 && $rsaHealthy <= $allocatedPlants) ? $rsaHealthy : $allocatedPlants) + $rsaShiftingQty;
            }
        }

        // DYNAMIC BED-WISE ALLOCATED PLANTS RESOLUTION & ACTIVE POLYHOUSE STOCK VERIFICATION
        $seenBedKeys = [];
        $activePhTraysTotal = 0;
        $activePhPlantsTotal = 0;

        // Collect all target allocated batches
        $targetStockBatches = [];
        if (!empty($allocatedBatches) && count($allocatedBatches) > 0) {
            foreach ($allocatedBatches as $ab) {
                $bNoStr = trim($ab['stockorder_no'] ?? '');
                if (!empty($bNoStr)) {
                    $bQty = floatval($ab['allocated_qty'] ?: $ab['consumed_qty'] ?: 0);
                    $targetStockBatches[$bNoStr] = ($bQty > 0) ? $bQty : (float)$allocatedPlants;
                }
            }
        }
        if (empty($targetStockBatches)) {
            $fallbackBatches = array_filter(array_unique(array_merge(
                array_filter(array_map('trim', explode(',', $effectiveStockNo))),
                array_filter(array_map('trim', explode(',', $siiStock)))
            )));
            foreach ($fallbackBatches as $fb) {
                $targetStockBatches[$fb] = (float)$allocatedPlants;
            }
        }

        // 1. Calculate active polyhouse stock totals across all relevant batches for capacity checks
        if (!empty($targetStockBatches)) {
            foreach ($targetStockBatches as $bNo => $bPlants) {
                $bedRows = $stockBedsByBatch[$bNo] ?? [];
                foreach ($bedRows as $br) {
                    $activePhTraysTotal += (int)$br['tray_count'];
                    $activePhPlantsTotal += (int)$br['net_plants'];
                }
            }
        }

        // 2. Priority 1: Use authoritative explicit beds allocated in krf_readystock_allocation if available
        if (!empty($rsaBedArray)) {
            $resolvedBatchStockNo = !empty($activeRsaStockNo) ? $activeRsaStockNo : (!empty($effectiveStockNo) ? $effectiveStockNo : '');
            foreach ($rsaBedArray as $bItem) {
                $bBedName = trim($bItem['bed_name'] ?? ($bItem['bed_no'] ?? ''));
                if (empty($bBedName)) continue;
                $bPhId = $bItem['polyhouse_id'] ?? ($bItem['polyhouseid'] ?? 0);
                $bPhName = $bItem['polyhouse_name'] ?? ($polyMap[$bPhId] ?? ('PH ' . $bPhId));
                $bTrays = (int)($bItem['tray_count'] ?? 0);
                $bPlants = (int)($bItem['plants'] ?? ($bItem['total_plants'] ?? ($bTrays * $trayCapDivisor)));
                if ($bPlants <= 0 && $bTrays > 0) {
                    $bPlants = $bTrays * $trayCapDivisor;
                }
                if ($bTrays <= 0 && $bPlants > 0) {
                    $bTrays = (int)ceil($bPlants / $trayCapDivisor);
                }

                $bedKey = $resolvedBatchStockNo . '_' . $bPhId . '_' . $bBedName;
                if (isset($seenBedKeys[$bedKey])) {
                    foreach ($rsaAllocatedBedDetail as &$exBd) {
                        if ($exBd['stockorder_no'] === $resolvedBatchStockNo && $exBd['bed_name'] === $bBedName && $exBd['polyhouse_id'] == $bPhId) {
                            $exBd['allocated_qty'] += $bPlants;
                            $exBd['plants'] += $bPlants;
                            $exBd['tray_count'] = (int)ceil($exBd['plants'] / $trayCapDivisor);
                            break;
                        }
                    }
                    unset($exBd);
                    continue;
                }
                $seenBedKeys[$bedKey] = true;

                // Check if a tray from this specific batch AND bed is already verified/loaded
                $isBedVerified = false;
                $verifiedBarcode = '';
                foreach ($itemLoadedTraysList as $ilTray) {
                    if (trim($ilTray['bed_no']) === $bBedName && (trim($ilTray['stockorder_no']) === $resolvedBatchStockNo || trim($ilTray['dispd_agst_stockorderno']) === $resolvedBatchStockNo)) {
                        $isBedVerified = true;
                        $verifiedBarcode = $ilTray['barcode'] ?: '';
                        break;
                    }
                }

                $rsaAllocatedBedDetail[] = [
                    'stockorder_no'    => $resolvedBatchStockNo,
                    'polyhouse_id'     => $bPhId,
                    'polyhouse_name'   => $bPhName,
                    'bed_name'         => $bBedName,
                    'allocated_qty'    => $bPlants,
                    'plants'           => $bPlants,
                    'tray_count'       => $bTrays,
                    'is_verified'      => $isBedVerified,
                    'verified_barcode' => $verifiedBarcode
                ];
            }
        }

        // 3. Priority 2 (Fallback): If no explicit beds in krf_readystock_allocation, assign from active polyhouse beds
        if (empty($rsaAllocatedBedDetail) && !empty($targetStockBatches)) {
            foreach ($targetStockBatches as $bNo => $bPlants) {
                if ($bPlants <= 0) $bPlants = (float)$allocatedPlants;
                $bedRows = $stockBedsByBatch[$bNo] ?? [];
                if (!empty($bedRows)) {
                    $remainingPlantsNeeded = (int)$bPlants;
                    foreach ($bedRows as $br) {
                        if ($remainingPlantsNeeded <= 0) {
                            break;
                        }

                        $phName = $polyMap[$br['polyhouseid']] ?? ('PH ' . $br['polyhouseid']);
                        $bedName = $br['bed_no'] ?: 'Bed-?';
                        $trayCap = (int)($br['capacity'] ?: $trayCapDivisor ?: 126);
                        $bedNetPlants = (int)$br['net_plants'];
                        if ($bedNetPlants <= 0) $bedNetPlants = (int)$br['tray_count'] * $trayCap;

                        // Allocate only what is needed from this bed, up to its capacity
                        if ($bedNetPlants >= $remainingPlantsNeeded || count($bedRows) === 1) {
                            $thisBedPlants = $remainingPlantsNeeded;
                        } else {
                            $thisBedPlants = min($remainingPlantsNeeded, $bedNetPlants);
                            if ($thisBedPlants <= 0) $thisBedPlants = $remainingPlantsNeeded;
                        }

                        $thisBedTrays = (int)ceil($thisBedPlants / $trayCapDivisor);
                        if ($thisBedTrays <= 0) $thisBedTrays = 1;

                        $bedKey = $bNo . '_' . $br['polyhouseid'] . '_' . $bedName;
                        if (isset($seenBedKeys[$bedKey])) {
                            foreach ($rsaAllocatedBedDetail as &$exBd) {
                                if ($exBd['stockorder_no'] === $bNo && $exBd['bed_name'] === $bedName && $exBd['polyhouse_id'] == $br['polyhouseid']) {
                                    $exBd['allocated_qty'] += $thisBedPlants;
                                    $exBd['plants'] += $thisBedPlants;
                                    $exBd['tray_count'] = (int)ceil($exBd['plants'] / $trayCapDivisor);
                                    break;
                                }
                            }
                            unset($exBd);
                            $remainingPlantsNeeded -= $thisBedPlants;
                            continue;
                        }
                        $seenBedKeys[$bedKey] = true;

                        // Check if a tray from this specific batch AND bed is already verified/loaded
                        $isBedVerified = false;
                        $verifiedBarcode = '';
                        foreach ($itemLoadedTraysList as $ilTray) {
                            if (trim($ilTray['bed_no']) === $bedName && (trim($ilTray['stockorder_no']) === $bNo || trim($ilTray['dispd_agst_stockorderno']) === $bNo)) {
                                $isBedVerified = true;
                                $verifiedBarcode = $ilTray['barcode'] ?: '';
                                break;
                            }
                        }

                        $rsaAllocatedBedDetail[] = [
                            'stockorder_no'    => $bNo,
                            'polyhouse_id'     => $br['polyhouseid'],
                            'polyhouse_name'   => $phName,
                            'bed_name'         => $bedName,
                            'allocated_qty'    => $thisBedPlants,
                            'plants'           => $thisBedPlants,
                            'tray_count'       => $thisBedTrays,
                            'is_verified'      => $isBedVerified,
                            'verified_barcode' => $verifiedBarcode
                        ];

                        $remainingPlantsNeeded -= $thisBedPlants;
                    }
                }
            }
        }

        // Active Stock availability verification
        $hasPolyhouseBed = !empty($rsaAllocatedBedDetail);
        $totalAvailTrays = $activePhTraysTotal + $loadedTrays;
        $totalAvailPlants = $activePhPlantsTotal + $loadedPlants;
        $targetTraysReq = ($allocatedTrays > 0) ? $allocatedTrays : $estTrays;
        $targetPlantsReq = ($allocatedPlants > 0) ? $allocatedPlants : $qty;

        $hasSufficientActiveQty = ($totalAvailTrays >= $targetTraysReq || $totalAvailPlants >= $targetPlantsReq);
        $isStockAvailableInPolyhouse = ($hasSufficientActiveQty && $hasPolyhouseBed);

        $stockWarning = '';
        if (!$isStockAvailableInPolyhouse) {
            if (!$hasPolyhouseBed && $totalAvailTrays == 0) {
                $stockWarning = "Re-allocate it due to qty not available in this polyhouse. No active trays found in polyhouse for batch " . implode(', ', array_keys($targetStockBatches)) . ".";
            } elseif (!$hasSufficientActiveQty) {
                $stockWarning = "Re-allocate it due to qty not available in this polyhouse (Allocated: {$targetTraysReq} Trays / " . number_format($targetPlantsReq) . " Plants, Active in Polyhouse: {$totalAvailTrays} Trays / " . number_format($totalAvailPlants) . " Plants).";
            } else {
                $stockWarning = "Re-allocate it: Polyhouse and Bed No not clearly assigned.";
            }
        }

        // Multi-bed verification & exact tray match
        $allBedsVerified = ($loadedTrays >= 1);
        if (!empty($rsaAllocatedBedDetail) && count($rsaAllocatedBedDetail) > 0) {
            $allBedsVerified = true;
            foreach ($rsaAllocatedBedDetail as $bd) {
                if (empty($bd['is_verified'])) {
                    $allBedsVerified = false;
                    break;
                }
            }
        }

        // Exact match rule: loaded trays in polyhouse must equal allocated trays
        $isItemExactMatched = ($allocatedTrays > 0 && $loadedTrays === $allocatedTrays);
        $isItemLoaded = $isItemExactMatched;
        $effectiveLoadedTrays = $loadedTrays;
        $effectiveLoadedPlants = $loadedPlants;
        $pendingTrays = max(0, $allocatedTrays - $loadedTrays);
        $pendingPlants = max(0, $allocatedPlants - $loadedPlants);

        if ($isItemDispatched) {
            $isItemExactMatched = true;
            $isItemLoaded = true;
            $pendingTrays = 0;
            $pendingPlants = 0;
        } elseif (!$isItemExactMatched) {
            $grouped[$psoKey]['is_all_loaded'] = false;
        }

        // Fallback to soi / poc if rsa record was not found
        if ($rsaIsShifted === null) {
            $isShiftedBool = ($row['isshifted'] === 'yes' || $row['isshifted'] === '1' || $row['isshifted'] === 1 || $pocIsShifted == 1);
            $rsaIsShifted = $isShiftedBool ? 'yes' : 'no';
            if ($isShiftedBool) {
                $shiftedPlantsCount = $allocatedPlants > 0 ? $allocatedPlants : $qty;
                $unshiftedPlantsCount = 0;
            } else {
                $shiftedPlantsCount = 0;
                $unshiftedPlantsCount = $allocatedPlants > 0 ? $allocatedPlants : $qty;
            }
            $rsaShiftingQty = (int)($pocExtraShifting ?: ($row['shiftingqty'] ?? 0));
        }

        $healthyPlant = ($row['healthyplant'] !== null && $row['healthyplant'] !== '') ? (int)$row['healthyplant'] : ($rsaHealthy > 0 ? $rsaHealthy : null);
        $unhealthyPlant = ($row['unhealthyplant'] !== null && $row['unhealthyplant'] !== '') ? (int)$row['unhealthyplant'] : null;

        $itemStatusLower = strtolower(trim($row['sii_status'] ?: ($row['si_status'] ?: 'readytoload')));
        $itemActionLower = strtolower(trim($row['actionstatus'] ?? ''));
        $isMarkedLoaded = in_array($itemStatusLower, ['readytodispatch', 'loaded']) || $itemActionLower === 'loaded';
        $isMarkedDispatched = $isItemDispatched || in_array($itemStatusLower, ['dispatched', 'fulldispatch', 'final dispatch']);

        $isItemMismatch = false;
        $itemMismatchReasons = [];

        if (!$isItemDispatched) {
            // Condition 1: Loaded trays do not equal allocated trays
            if ($loadedTrays > 0 && $loadedTrays !== $allocatedTrays && $allocatedTrays > 0) {
                $isItemMismatch = true;
                $itemMismatchReasons[] = "Loaded ({$loadedTrays} Trays) != Allocated ({$allocatedTrays} Trays)";
            }
            if ($isMarkedLoaded && !$isItemExactMatched) {
                $isItemMismatch = true;
                $itemMismatchReasons[] = "Status is Loaded but polyhouse trays ({$loadedTrays}) != Allocated ({$allocatedTrays})";
            }

            // Condition 2: Marked dispatched, but dispatched plants/trays don't match
            if ($isMarkedDispatched) {
                if ($dispatchedPlants != $allocatedPlants && $allocatedPlants > 0) {
                    $isItemMismatch = true;
                    $itemMismatchReasons[] = "Dispatched (" . number_format($dispatchedPlants) . " Plants) != Allocated (" . number_format($allocatedPlants) . " Plants)";
                }
                if ($dispatchedTrays == 0 && $allocatedTrays > 0) {
                    $isItemMismatch = true;
                    $itemMismatchReasons[] = "Dispatched trays count is 0 in plant records";
                }
            }

            // Condition 3: Over-loaded (loaded exceeds allocated)
            if ($loadedTrays > $allocatedTrays && $allocatedTrays > 0) {
                $isItemMismatch = true;
                $itemMismatchReasons[] = "Over-Loaded: {$loadedTrays} Trays Loaded exceeds {$allocatedTrays} Allocated";
            }

            // Condition 4: Zero allocation with positive order qty
            if ($allocatedPlants == 0 && $qty > 0) {
                $isItemMismatch = true;
                $itemMismatchReasons[] = "Unallocated: 0 Plants Allocated for Order of " . number_format($qty);
            }

            // Condition 5: Stock unavailable in polyhouse or bed not assigned
            if (!$isStockAvailableInPolyhouse) {
                $isItemMismatch = true;
                $itemMismatchReasons[] = $stockWarning;
            }
        }

        $itemData = [
            'item_id'                => (int)$row['id'],
            'sii_id'                 => (int)($row['sii_id'] ?? 0),
            'invoiceno'              => $row['invoiceno'] ?? '',
            'invoicedate'            => $rawInvDate,
            'formatted_invoicedate'  => $formattedInvDate,
            'sono'                   => $row['sono'],
            'psono'                  => $row['psono'],
            'product_id'             => (!empty($activeRsaProdId) && (empty($prodIdParam) || $activeRsaProdId === $prodIdParam)) ? $activeRsaProdId : strval($row['product_id'] ?? ''),
            'stockorder_no'          => $effectiveStockNo ?: '-',
            'effective_stockorder_no'=> $effectiveStockNo,
            'original_stockorder_no' => $origStock,
            'allocated_stockorder_no'=> $allocStock,
            'allocated_batches'      => $allocatedBatches,
            'is_multi_batch'         => count($allocatedBatches) > 1,
            'is_swapped'             => $isSwapped,
            'allocation_remark'      => $pocRemark,
            'allocated_by_id'        => $pocAllocatedBy,
            'allocated_by_name'      => '',
            'allocated_at'           => $pocAllocatedAt,
            'formatted_allocated_at' => '',
            'clientid'               => $clientIdParam,
            'product_sku'            => $row['product_sku'] ?: '-',
            'product_name'           => (!empty($activeRsaProdName) && (empty($prodIdParam) || $activeRsaProdId === $prodIdParam)) ? $activeRsaProdName : $row['product_name'],
            'invoice_needs_update'   => false,
            'invoice_update_msg'     => '',
            'invoice_stockorder_no'  => trim($row['stockorder_no'] ?? ''),
            'old_stockorder_no'      => trim($row['stockorder_no'] ?? ''),
            'new_stockorder_no'      => $activeRsaStockNo,
            'new_product_id'         => $activeRsaProdId,
            'new_product_name'       => $activeRsaProdName,
            'quantity'               => $qty,
            'invoice_qty'            => $qty,
            'invoice_trays'          => $estTrays,
            'order_qty'              => $originalOrderQty,
            'order_trays'            => $originalOrderTrays,
            'original_order_qty'     => $originalOrderQty,
            'original_order_trays'   => $originalOrderTrays,
            'allocate_ordqty'        => (int)($row['allocate_ordqty'] ?? 0),
            'allocated_qty'          => $allocatedPlants,
            'allocated_trays'        => $allocatedTrays,
            'is_stock_available'     => $isStockAvailableInPolyhouse,
            'active_ph_trays'        => $activePhTraysTotal,
            'active_ph_plants'       => $activePhPlantsTotal,
            'total_available_trays'  => $totalAvailTrays,
            'total_available_plants' => $totalAvailPlants,
            'has_polyhouse_bed'      => $hasPolyhouseBed,
            'stock_warning'          => $stockWarning,
            'ready_to_sell_trays'    => $readyToSellTrays,
            'ready_to_sell_plants'   => $readyToSellPlants,
            'active_bed_trays'       => $activeBedTrays,
            'rate'                   => (float)$row['rate'],
            'amount'                 => (float)$row['amount'],
            'deliverydate'           => (!empty($row['deliverydate']) && $row['deliverydate'] !== '0000-00-00') ? (strtotime($row['deliverydate']) ? date('d-M-Y', strtotime($row['deliverydate'])) : $row['deliverydate']) : '-',
            'status'                 => $isItemDispatched ? 'dispatched' : (!empty($row['sii_status']) ? $row['sii_status'] : ($row['status'] ?: 'readytoload')),
            'sii_status'             => $row['sii_status'] ?? '',
            'actionstatus'           => $isItemDispatched ? 'FullDispatch' : ($row['actionstatus'] ?: ''),
            'is_dispatched'          => $isItemDispatched,
            'isshifted'              => $rsaIsShifted,
            'shifted_plants'         => $shiftedPlantsCount,
            'unshifted_plants'       => $unshiftedPlantsCount,
            'percentextrashifting'   => $rsaPercentExtra,
            'shiftingqty'            => $rsaShiftingQty,
            'healthyplant'           => $healthyPlant,
            'unhealthyplant'         => $unhealthyPlant,
            'typical_capacity'       => $typicalCapacity,
            'est_trays'              => $estTrays,
            'dispatched_trays'       => $dispatchedTrays,
            'dispatched_plants'      => $dispatchedPlants,
            'prior_dispatched_trays' => $priorDispatchedTrays,
            'prior_dispatched_plants'=> $priorDispatchedPlants,
            'loaded_trays'           => $loadedTrays,
            'loaded_plants'          => $loadedPlants,
            'actual_scanned_trays'   => $loadedTrays,
            'is_verified_by_tray'    => $isItemExactMatched,
            'pending_trays'          => $pendingTrays,
            'pending_plants'         => $pendingPlants,
            'is_loaded'              => $isItemLoaded,
            'is_exact_matched'       => $isItemExactMatched,
            'allocatedbed'           => $rsaAllocatedBedRaw,
            'allocatedbed_source'    => ($rsaSumTrayCount > 0 ? 'allocatedbed_json' : 'allocate_ordqty'),
            'allocatedbed_has_json'  => ($rsaSumTrayCount > 0),
            'allocated_beds_detail'  => $rsaAllocatedBedDetail,
            'all_beds_verified'      => $isItemExactMatched,
            'active_beds'            => $activeBedList,
            'is_mismatch'            => $isItemMismatch,
            'mismatch_reasons'       => $itemMismatchReasons,
            'mismatch_summary'       => implode(' • ', $itemMismatchReasons),
            'is_rso'                 => (bool)$isRsoStock,
            'is_undone'              => (bool)$isManuallyUndone
        ];

        if (!isset($grouped[$psoKey]['has_mismatch'])) {
            $grouped[$psoKey]['has_mismatch'] = false;
            $grouped[$psoKey]['mismatch_reasons'] = [];
            $grouped[$psoKey]['mismatch_count'] = 0;
        }
        if ($isItemMismatch) {
            $grouped[$psoKey]['has_mismatch'] = true;
            $grouped[$psoKey]['mismatch_count']++;
            $grouped[$psoKey]['mismatch_reasons'] = array_merge($grouped[$psoKey]['mismatch_reasons'], $itemMismatchReasons);
        }

        $grouped[$psoKey]['items'][] = $itemData;
        $grouped[$psoKey]['total_order_plants']          += $qty;
        $grouped[$psoKey]['total_order_trays']           += $estTrays;
        $grouped[$psoKey]['total_allocated_plants']      += $allocatedPlants;
        $grouped[$psoKey]['total_allocated_trays']       += $allocatedTrays;
        $grouped[$psoKey]['total_ready_to_sell_plants']  += $readyToSellPlants;
        $grouped[$psoKey]['total_ready_to_sell_trays']   += $readyToSellTrays;
        $grouped[$psoKey]['total_loaded_plants']         += $effectiveLoadedPlants;
        $grouped[$psoKey]['total_loaded_trays']          += $effectiveLoadedTrays;
        $grouped[$psoKey]['total_dispatched_plants']     += $dispatchedPlants;
        $grouped[$psoKey]['total_dispatched_trays']      += $dispatchedTrays;
        $grouped[$psoKey]['total_prior_dispatched_plants'] = ($grouped[$psoKey]['total_prior_dispatched_plants'] ?? 0) + $priorDispatchedPlants;
        $grouped[$psoKey]['total_prior_dispatched_trays']  = ($grouped[$psoKey]['total_prior_dispatched_trays'] ?? 0) + $priorDispatchedTrays;

        $totalOverallPlants += $qty;
        $totalOverallTrays += $estTrays;
        $totalOverallAllocatedPlants += $allocatedPlants;
        $totalOverallAllocatedTrays += $allocatedTrays;
        $totalLoadedTraysCount += $loadedTrays;
    }

    // Resolve user names for allocatedby
    $userMap = ['8871765657' => 'Niteesh Mahant'];
    $userIds = array_values(array_unique(array_filter($userIdsToResolve)));
    if (!empty($userIds) && isset($authconn) && $authconn) {
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $uTypes = str_repeat('s', count($userIds) * 2);
        $uBinds = array_merge($userIds, $userIds);
        $uSql = "SELECT loginid, mobileno, loginname FROM login_user_master WHERE loginid IN ($placeholders) OR mobileno IN ($placeholders)";
        $uStmt = $authconn->prepare($uSql);
        if ($uStmt) {
            $uStmt->bind_param($uTypes, ...$uBinds);
            $uStmt->execute();
            $uRes = $uStmt->get_result();
            while ($uRow = $uRes->fetch_assoc()) {
                if (!empty($uRow['loginid'])) $userMap[trim($uRow['loginid'])] = trim($uRow['loginname']);
                if (!empty($uRow['mobileno'])) $userMap[trim($uRow['mobileno'])] = trim($uRow['loginname']);
            }
            $uStmt->close();
        }
    }

    // Attach resolved user names and formatted dates
    $totalMismatchedOrders = 0;
    foreach ($grouped as &$g) {
        if (!empty($g['invoices'])) {
            $g['invoice_list'] = array_values($g['invoices']);
            $g['invoiceno'] = implode(', ', array_keys($g['invoices']));
        }
        $gAllocId = trim($g['allocated_by_id'] ?? '');
        $g['allocated_by_name'] = $userMap[$gAllocId] ?? ($gAllocId === '8871765657' ? 'Niteesh Mahant' : ($gAllocId ?: '-'));
        $g['formatted_allocated_at'] = (!empty($g['allocated_at']) && $g['allocated_at'] !== '0000-00-00 00:00:00') ? date('d-M-Y H:i', strtotime($g['allocated_at'])) : '';
        
        $g['mismatch_summary'] = implode(' • ', array_unique($g['mismatch_reasons'] ?? []));
        if (!empty($g['has_mismatch'])) {
            $totalMismatchedOrders++;
        }

        foreach ($g['items'] as &$it) {
            $itAllocId = trim($it['allocated_by_id'] ?? '');
            $it['allocated_by_name'] = $userMap[$itAllocId] ?? ($itAllocId === '8871765657' ? 'Niteesh Mahant' : ($itAllocId ?: '-'));
            $it['formatted_allocated_at'] = (!empty($it['allocated_at']) && $it['allocated_at'] !== '0000-00-00 00:00:00') ? date('d-M-Y H:i', strtotime($it['allocated_at'])) : '';
        }
    }
    unset($g, $it);

    // In Ready to Load view, filter out any orders whose items are already fully dispatched
    if ($sFilterLower === 'readytoload') {
        $grouped = array_filter($grouped, function($g) {
            return !empty($g['items']);
        });
    }

    if ($mismatchOnly === 1) {
        $grouped = array_filter($grouped, function($g) {
            return !empty($g['has_mismatch']);
        });
    }

    // Query available years directly from sales_invoice & sales_invoice_items
    $availableYears = [];
    $yrQ = $krfconn->query("
        SELECT DISTINCT YEAR(invoicedate) as yr FROM sales_invoice WHERE invoicedate IS NOT NULL AND invoicedate > '2020-01-01'
        UNION
        SELECT DISTINCT YEAR(deliverydate) as yr FROM sales_invoice_items WHERE deliverydate IS NOT NULL AND deliverydate > '2020-01-01'
        ORDER BY yr DESC
    ");
    if ($yrQ) {
        while ($yrR = $yrQ->fetch_assoc()) {
            $availableYears[] = (int)$yrR['yr'];
        }
    }
    if (empty($availableYears)) {
        $availableYears = [(int)date('Y')];
    }


    outJson([
        'status'          => 'success',
        'data'            => array_values($grouped),
        'available_years' => $availableYears,
        'metrics' => [
            'total_orders'            => count($grouped),
            'total_plants'            => $totalOverallPlants,
            'total_trays'             => $totalOverallTrays,
            'total_allocated_plants'  => $totalOverallAllocatedPlants,
            'total_allocated_trays'   => $totalOverallAllocatedTrays,
            'total_loaded_trays'      => $totalLoadedTraysCount,
            'total_mismatched_orders' => $totalMismatchedOrders,
            'filter_status'           => $statusFilter,
            'filter_year'             => $yearFilter
        ]
    ]);
}


// -------------------------------------------------------------
// 2. ACTION: SCAN TRAY QR / BARCODE LOOKUP
// -------------------------------------------------------------
if ($action === 'scan_tray') {
    $rawBarcode       = trim($_REQUEST['barcode'] ?? $_REQUEST['query'] ?? '');
    $targetPsono      = trim($_REQUEST['psono'] ?? '');
    $targetProduct    = trim($_REQUEST['product_name'] ?? '');
    $targetStockorder = trim($_REQUEST['stockorder_no'] ?? '');
    $targetVariety    = trim($_REQUEST['variety'] ?? '');
    $targetSono       = trim($_REQUEST['sono'] ?? '');
    $targetClientId   = trim($_REQUEST['clientid'] ?? '');
    $targetClientName = trim($_REQUEST['clientname'] ?? '');
    $targetItemId     = (int)($_REQUEST['item_id'] ?? 0);

    if (empty($rawBarcode)) {
        outJson(['status' => 'error', 'message' => 'Please enter or scan a Tray Barcode.']);
    }

    // Clean barcode
    $cleanBarcode = preg_replace('/[\x00-\x1F\x7F\xC2\xA0]/u', '', $rawBarcode);
    $cleanBarcode = trim($cleanBarcode);

    $escBc = mysqli_real_escape_string($krfconn, $cleanBarcode);
    $found = null;
    $isPrefixRsoOrSo = (bool)preg_match('/^(RSO|SO)-/i', $cleanBarcode);

    // 1. First, attempt direct barcode lookup in krf_polyhouse_plant_details
    $sqlDirect = "
        SELECT 
            p.polyhouseplantid,
            p.barcode,
            p.bedqrcode,
            p.bed_no,
            p.polyhouseid,
            p.locationid,
            p.projectid,
            p.stockorder_no,
            p.dispd_agst_stockorderno,
            p.dispd_agst_psono,
            p.dispd_agst_clientid,
            p.psono,
            p.productid,
            p.productname,
            p.variety,
            p.tray_id,
            p.tray_code,
            COALESCE(p.qtypertray, 126) as qtypertray,
            p.status,
            p.workingstatus,
            p.growth_stage,
            p.health_status,
            p.movementremark,
            p.move_at,
            p.updated_at
        FROM krf_polyhouse_plant_details p
        WHERE p.barcode = '{$escBc}'
        ORDER BY 
            CASE 
                WHEN LOWER(TRIM(p.status)) IN ('readytosale', 'ready to sale', 'polyhouse') THEN 1
                WHEN LOWER(TRIM(p.status)) IN ('loaded', 'readytodispatch') THEN 2
                ELSE 3
            END ASC,
            p.polyhouseplantid DESC
        LIMIT 1
    ";
    $resDirect = $krfconn->query($sqlDirect);
    if ($resDirect && $resDirect->num_rows > 0) {
        $found = $resDirect->fetch_assoc();
    }

    // If direct barcode wasn't found in krf_polyhouse_plant_details, check krf_readytosale_stocks
    if (!$found) {
        $qRtsDirect = $krfconn->query("
            SELECT 
                r.id as polyhouseplantid,
                r.barcode,
                '' as bedqrcode,
                r.bed_no,
                r.polyhouseid,
                r.locationid,
                r.projectid,
                r.stockorder_no,
                r.dispd_agst_stockorderno,
                r.dispd_agst_psono,
                r.dispd_agst_clientid,
                r.psono,
                r.productid,
                r.productname,
                r.variety,
                r.tray_id,
                r.tray_code,
                COALESCE(r.qtypertray, 126) as qtypertray,
                r.status,
                r.workingstatus,
                r.growth_stage,
                r.health_status,
                r.movementremark,
                r.move_at,
                r.updated_at
            FROM krf_readytosale_stocks r
            WHERE r.barcode = '{$escBc}'
            ORDER BY 
                CASE 
                    WHEN r.status IN ('Final Dispatch', 'Internal Dispatch', 'Dispatched', 'FullDispatch', 'fulldispatch', 'Tray Dead', 'Removed') THEN 2
                    ELSE 1
                END ASC,
                r.id DESC
            LIMIT 1
        ");
        if ($qRtsDirect && $qRtsDirect->num_rows > 0) {
            $found = $qRtsDirect->fetch_assoc();
        }
    }

    // 2. If not found by direct barcode, check if it's a Stock Order QR, Bed QR, or Bed Number
    if (!$found) {
        $stkMatches = [$cleanBarcode];
        if (preg_match('/^(RSO|SO|KFP|KF)[-_]?(\d+)$/i', $cleanBarcode, $m)) {
            $num = $m[2];
            $stkMatches[] = 'KFP' . $num;
            $stkMatches[] = 'KF' . $num;
            $stkMatches[] = 'RSO-' . $num;
            $stkMatches[] = 'SO-' . $num;
            $stkMatches[] = $num;
        }
        $escStkList = "'" . implode("','", array_map(function($s) use ($krfconn) { return mysqli_real_escape_string($krfconn, $s); }, array_unique($stkMatches))) . "'";

        // Extra constraints prioritizing target order's stock and product
        $extraOrderCond = "";
        if (!empty($targetStockorder)) {
            $escTgtStk = mysqli_real_escape_string($krfconn, $targetStockorder);
            $extraOrderCond .= " AND (p.stockorder_no = '{$escTgtStk}' OR p.dispd_agst_stockorderno = '{$escTgtStk}')";
        }
        if (!empty($targetProduct)) {
            $escTgtProd = mysqli_real_escape_string($krfconn, $targetProduct);
            $extraOrderCond .= " AND (p.productname = '{$escTgtProd}' OR '{$escTgtProd}' = '')";
        }

        $sqlBatchOrBed = "
            SELECT 
                p.polyhouseplantid,
                p.barcode,
                p.bedqrcode,
                p.bed_no,
                p.polyhouseid,
                p.locationid,
                p.projectid,
                p.stockorder_no,
                p.dispd_agst_stockorderno,
                p.dispd_agst_psono,
                p.dispd_agst_clientid,
                p.psono,
                p.productid,
                p.productname,
                p.variety,
                p.tray_id,
                p.tray_code,
                COALESCE(p.qtypertray, 126) as qtypertray,
                p.status,
                p.workingstatus,
                p.growth_stage,
                p.health_status,
                p.movementremark,
                p.move_at,
                p.updated_at
            FROM krf_polyhouse_plant_details p
            WHERE (
                p.stockorder_no IN ({$escStkList}) 
                OR p.dispd_agst_stockorderno IN ({$escStkList}) 
                OR p.bedqrcode = '{$escBc}' 
                OR p.bed_no = '{$escBc}'
                OR REPLACE(LOWER(p.bed_no), ' ', '') = REPLACE(LOWER('{$escBc}'), ' ', '')
            )
            {$extraOrderCond}
            AND LOWER(TRIM(p.status)) IN ('readytosale', 'ready to sale', 'polyhouse')
            AND LOWER(TRIM(p.status)) NOT IN ('final dispatch', 'dispatched', 'fulldispatch', 'removed', 'empty after shifting', 'tray free after shifting', 'empty after grafting', 'remove after over age', 'tray dead', 'loaded', 'readytodispatch')
            ORDER BY 
                CASE 
                    WHEN LOWER(TRIM(p.status)) IN ('readytosale', 'ready to sale') THEN 1
                    ELSE 2
                END ASC,
                p.polyhouseplantid ASC
            LIMIT 1
        ";
        $resBatch = $krfconn->query($sqlBatchOrBed);
        if ($resBatch && $resBatch->num_rows > 0) {
            $found = $resBatch->fetch_assoc();
            $found['scanned_code'] = $cleanBarcode;
        } else {
            // Broad search without extraOrderCond
            $sqlBatchAny = "
                SELECT 
                    p.polyhouseplantid,
                    p.barcode,
                    p.bedqrcode,
                    p.bed_no,
                    p.polyhouseid,
                    p.locationid,
                    p.projectid,
                    p.stockorder_no,
                    p.dispd_agst_stockorderno,
                    p.dispd_agst_psono,
                    p.dispd_agst_clientid,
                    p.psono,
                    p.productid,
                    p.productname,
                    p.variety,
                    p.tray_id,
                    p.tray_code,
                    COALESCE(p.qtypertray, 126) as qtypertray,
                    p.status,
                    p.workingstatus,
                    p.growth_stage,
                    p.health_status,
                    p.movementremark,
                    p.move_at,
                    p.updated_at
                FROM krf_polyhouse_plant_details p
                WHERE (
                    p.stockorder_no IN ({$escStkList}) 
                    OR p.dispd_agst_stockorderno IN ({$escStkList}) 
                    OR p.bedqrcode = '{$escBc}' 
                    OR p.bed_no = '{$escBc}'
                    OR REPLACE(LOWER(p.bed_no), ' ', '') = REPLACE(LOWER('{$escBc}'), ' ', '')
                )
                AND LOWER(TRIM(p.status)) IN ('readytosale', 'ready to sale', 'polyhouse')
                AND LOWER(TRIM(p.status)) NOT IN ('final dispatch', 'dispatched', 'fulldispatch', 'removed', 'empty after shifting', 'tray free after shifting', 'empty after grafting', 'remove after over age', 'tray dead', 'loaded', 'readytodispatch')
                ORDER BY 
                    CASE 
                        WHEN LOWER(TRIM(p.status)) IN ('readytosale', 'ready to sale') THEN 1
                        ELSE 2
                    END ASC,
                    p.polyhouseplantid ASC
                LIMIT 1
            ";
            $resBatchAny = $krfconn->query($sqlBatchAny);
            if ($resBatchAny && $resBatchAny->num_rows > 0) {
                $found = $resBatchAny->fetch_assoc();
                $found['scanned_code'] = $cleanBarcode;
            }
        }
    }

    if (!$found) {
        $msgType = $isPrefixRsoOrSo ? 'Batch / Stock Order "' : 'Barcode "';
        outJson([
            'status' => 'error',
            'message' => 'No plant tray found matching ' . $msgType . htmlspecialchars($cleanBarcode) . '". Please make sure you are scanning a valid tag.'
        ]);
    }

    // Strict Status Validation: Check if tray is already dispatched, inactive, or loaded for another order
    $foundStatus = trim($found['status'] ?? '');
    $foundStatusLower = strtolower($foundStatus);
    $existingPso = !empty($found['dispd_agst_psono']) ? trim($found['dispd_agst_psono']) : trim($found['psono'] ?? '');
    $existingClientId = !empty($found['dispd_agst_clientid']) ? trim($found['dispd_agst_clientid']) : '';

    $isDispatched = in_array($foundStatusLower, ['final dispatch', 'internal dispatch', 'dispatched', 'fulldispatch', 'full dispatch']);
    $isDeadOrRemoved = in_array($foundStatusLower, ['tray dead', 'removed', 'empty after shifting', 'tray free after shifting', 'empty after grafting', 'remove after over age'])
        || !in_array($foundStatusLower, ['readytosale', 'ready to sale', 'ready to sell', 'polyhouse', 'loaded', 'readytodispatch', 'ready to dispatch']);
    $isLoadedForOther = in_array($foundStatusLower, ['loaded', 'readytodispatch', 'ready to dispatch']) 
        && (!empty($existingPso) && !empty($targetPsono) && strcasecmp($existingPso, $targetPsono) !== 0);

    if ($isDispatched) {
        $clientName = getClientNameByPso($krfconn, $existingPso, $existingClientId);
        $orderTxt = !empty($existingPso) ? " (Order: {$existingPso})" : "";
        outJson([
            'status'      => 'error',
            'error_type'  => 'tray_dispatched',
            'client_name' => $clientName,
            'psono'       => $existingPso,
            'barcode'     => $cleanBarcode,
            'message'     => "Tray '{$cleanBarcode}' is already DISPATCHED to {$clientName}{$orderTxt}. Dispatched trays cannot be loaded again."
        ]);
    }

    if ($isLoadedForOther) {
        $clientName = getClientNameByPso($krfconn, $existingPso, $existingClientId);
        outJson([
            'status'      => 'error',
            'error_type'  => 'tray_allocated_to_other',
            'client_name' => $clientName,
            'psono'       => $existingPso,
            'barcode'     => $cleanBarcode,
            'message'     => "Tray '{$cleanBarcode}' is already ALLOCATED / LOADED for {$clientName} (Order: {$existingPso}, Status: {$foundStatus}). A tray cannot be loaded or allocated multiple times."
        ]);
    }

    if ($isDeadOrRemoved) {
        outJson([
            'status'      => 'error',
            'error_type'  => 'tray_inactive',
            'barcode'     => $cleanBarcode,
            'message'     => "Tray '{$cleanBarcode}' is marked as '{$foundStatus}'. Only active trays with status 'Readytosale' or 'Polyhouse' can be loaded."
        ]);
    }

    $isLoadedForThisOrder = in_array($foundStatusLower, ['loaded', 'readytodispatch', 'ready to dispatch'])
        && (!empty($existingPso) && !empty($targetPsono) && strcasecmp($existingPso, $targetPsono) === 0);

    if ($isLoadedForThisOrder) {
        outJson([
            'status' => 'already_loaded',
            'message' => "Tray '{$cleanBarcode}' is already loaded for Order {$targetPsono}.",
            'data' => [
                'barcode'        => $cleanBarcode,
                'psono'          => $targetPsono,
                'current_status' => $foundStatus
            ]
        ]);
    }

    $trayStock   = trim($found['stockorder_no'] ?: $found['dispd_agst_stockorderno'] ?: '');
    $trayBed     = trim($found['bed_no'] ?: '');
    $trayPolyId  = (int)($found['polyhouseid'] ?? 0);
    $polyMap     = getPolyhouseNameMap($krfconn);
    $polyhouseName = $polyMap[$trayPolyId] ?? ('PH ' . $trayPolyId);

    // Retrieve active tray details in bed and stock order where scanned tray exists
    $bedActiveDetails = getBedActiveTrayDetails($krfconn, $trayStock, $trayBed, $trayPolyId, $targetPsono);

    // Track whether the individual scanned tray was loaded for another order (it serves as an indicator/reference)
    $scannedTrayLoadedForOtherOrder = false;
    if (in_array($found['status'], ['Loaded', 'ReadyToDispatch'])) {
        $loadedPso = $found['dispd_agst_psono'] ?: ($found['psono'] ?: '');
        if (!empty($loadedPso) && !empty($targetPsono) && strcasecmp(trim($loadedPso), trim($targetPsono)) !== 0) {
            $scannedTrayLoadedForOtherOrder = true;
        }
    }

    // Resolve missing target order details if needed
    if (!empty($targetPsono) && (empty($targetProduct) || empty($targetClientId) || empty($targetClientName) || empty($targetVariety))) {
        $escTgtPso = mysqli_real_escape_string($krfconn, $targetPsono);
        $itemFilter = ($targetItemId > 0) ? "AND soi.id = {$targetItemId}" : "";
        $psoQ = $krfconn->query("
            SELECT 
                soi.sono,
                soi.clientid,
                COALESCE(NULLIF(TRIM(so.clientname), ''), NULLIF(TRIM(pso.clientname), ''), cm.client_name, '') as clientname,
                soi.product_name,
                soi.stockorder_no
            FROM krf_sales_order_items soi
            LEFT JOIN krf_sales_order so ON soi.sono = so.sono
            LEFT JOIN krf_provisional_sales_order pso ON soi.psono = pso.psono
            LEFT JOIN krf_client_master cm ON soi.clientid = cm.client_id
            WHERE soi.psono = '{$escTgtPso}' {$itemFilter}
            LIMIT 1
        ");
        if ($psoQ && $psoRow = $psoQ->fetch_assoc()) {
            if (empty($targetSono) && !empty($psoRow['sono'])) $targetSono = $psoRow['sono'];
            if (empty($targetClientId) && !empty($psoRow['clientid'])) $targetClientId = $psoRow['clientid'];
            if (empty($targetClientName) && !empty($psoRow['clientname'])) $targetClientName = $psoRow['clientname'];
            if (empty($targetProduct) && !empty($psoRow['product_name'])) $targetProduct = $psoRow['product_name'];
            if (empty($targetStockorder) && !empty($psoRow['stockorder_no'])) $targetStockorder = $psoRow['stockorder_no'];
        }
    }

    if (empty($targetVariety) && !empty($targetProduct)) {
        $targetVariety = extractNormalizedVariety($targetProduct);
    }

    // Match validation with target order / product
    $trayStock   = trim($found['stockorder_no'] ?? '');
    $trayPso     = trim($found['psono'] ?? '');
    $targetStock = trim($targetStockorder ?? '');
    $targetPso   = trim($targetPsono ?? '');

    // Strict Loading Limit Validation: Prevent scanning additional trays if order is already fully loaded
    if (!empty($targetPso)) {
        $escTgtPso = mysqli_real_escape_string($krfconn, $targetPso);
        if ($targetItemId > 0) {
            $itemCond = "AND (soi.id = {$targetItemId} OR soi.id IN (SELECT original_soi_id FROM sales_invoice_items WHERE id = {$targetItemId} AND original_soi_id > 0))";
        } elseif (!empty($targetProduct)) {
            $escP = mysqli_real_escape_string($krfconn, $targetProduct);
            $itemCond = "AND soi.product_name = '{$escP}'";
        } elseif (!empty($targetStockorder)) {
            $stkParts = array_filter(array_map('trim', explode(',', $targetStockorder)));
            $escParts = array_map(function($s) use ($krfconn) { return "'" . mysqli_real_escape_string($krfconn, $s) . "'"; }, $stkParts);
            $inList = !empty($escParts) ? implode(',', $escParts) : "''";
            $escS = mysqli_real_escape_string($krfconn, $targetStockorder);
            $itemCond = "AND (soi.stockorder_no = '{$escS}' OR soi.stockorder_no IN ({$inList}) OR poc.stockorderno IN ({$inList}) OR poc.allocated_stockorderno IN ({$inList}))";
        } else {
            $itemCond = "";
        }
        $qTgtLimit = $krfconn->query("
            SELECT 
                soi.id,
                soi.product_id,
                soi.product_name,
                soi.stockorder_no,
                soi.quantity,
                soi.allocate_ordqty,
                soi.isshifted AS soi_isshifted,
                poc.stockorderno AS poc_stockorderno,
                poc.allocated_stockorderno AS poc_allocated_stockorderno,
                poc.allocate_healthyplant,
                poc.consumed_qty,
                poc.isshifted AS poc_isshifted
            FROM krf_sales_order_items soi
            LEFT JOIN krf_productionorder_consumed poc ON (
                (NULLIF(TRIM(soi.psono), '') IS NOT NULL AND soi.psono = poc.psono AND (
                    (NULLIF(TRIM(soi.product_id), '') IS NOT NULL AND soi.product_id = poc.product_id)
                    OR (NULLIF(TRIM(soi.stockorder_no), '') IS NOT NULL AND (soi.stockorder_no = poc.stockorderno OR soi.stockorder_no = poc.allocated_stockorderno))
                ))
                OR (NULLIF(TRIM(soi.sono), '') IS NOT NULL AND soi.sono = poc.sono AND NULLIF(TRIM(soi.stockorder_no), '') IS NOT NULL AND soi.stockorder_no = poc.stockorderno)
            ) AND IFNULL(poc.tedit, '') = ''
            WHERE (soi.psono = '{$escTgtPso}' OR soi.sono = '{$escTgtPso}') {$itemCond}
            LIMIT 1
        ");

        if ($qTgtLimit && $tgtLimitRow = $qTgtLimit->fetch_assoc()) {
            $soiAlloc    = (int)($tgtLimitRow['allocate_ordqty'] ?? 0);
            $pocConsumed = (int)($tgtLimitRow['consumed_qty'] ?? 0);
            $pocH        = (int)($tgtLimitRow['allocate_healthyplant'] ?? 0);
            $orderQty    = (int)($tgtLimitRow['quantity'] ?? 0);

            if ($soiAlloc > 0) {
                $targetQty = $soiAlloc;
            } elseif ($pocConsumed > 0) {
                $targetQty = $pocConsumed;
            } elseif ($pocH > 0 && ($orderQty <= 0 || $pocH <= $orderQty)) {
                $targetQty = $pocH;
            } else {
                $targetQty = $orderQty;
            }
            $cap = ((int)($tgtLimitRow['poc_isshifted'] ?? 0) === 1 || ($tgtLimitRow['soi_isshifted'] ?? '') === 'yes') ? 125 : 126;
            $maxAllowedTrays = ($targetQty > 0) ? (int)ceil($targetQty / $cap) : 0;

            if ($maxAllowedTrays > 0) {
                $escProdName = mysqli_real_escape_string($krfconn, $targetProduct ?: ($tgtLimitRow['product_name'] ?? ''));
                $escProdId = mysqli_real_escape_string($krfconn, $tgtLimitRow['product_id'] ?? '');
                $escStockNo = mysqli_real_escape_string($krfconn, $targetStockorder ?: ($tgtLimitRow['stockorder_no'] ?? ''));
                $escOrigStock = mysqli_real_escape_string($krfconn, $tgtLimitRow['poc_stockorderno'] ?? '');
                $escAllocStock = mysqli_real_escape_string($krfconn, $tgtLimitRow['poc_allocated_stockorderno'] ?? '');

                $itemFilterSql = "";
                if (!empty($escProdName)) {
                    $itemFilterSql = "AND (productname = '{$escProdName}'" . (!empty($escProdId) ? " OR productid = '{$escProdId}'" : "") . ")";
                } elseif (!empty($escStockNo) || !empty($escAllocStock)) {
                    $rawStk = array_merge(explode(',', $escStockNo), explode(',', $escOrigStock), explode(',', $escAllocStock));
                    $stkList = array_values(array_filter(array_map('trim', $rawStk)));
                    $stkIn = "'" . implode("','", array_map(function($s) use ($krfconn) { return mysqli_real_escape_string($krfconn, $s); }, $stkList)) . "'";
                    $itemFilterSql = "AND (dispd_agst_stockorderno IN ({$stkIn}) OR stockorder_no IN ({$stkIn}))";
                }

                $qCurLoaded = $krfconn->query("
                    SELECT COUNT(DISTINCT COALESCE(NULLIF(barcode, ''), CONCAT('ID_', polyhouseplantid))) as loaded_count
                    FROM krf_polyhouse_plant_details
                    WHERE (dispd_agst_psono = '{$escTgtPso}' OR (psono = '{$escTgtPso}' AND (dispd_agst_psono IS NULL OR dispd_agst_psono = '' OR dispd_agst_psono = '{$escTgtPso}')))
                      AND status IN ('Loaded', 'ReadyToDispatch')
                      {$itemFilterSql}
                ");
                $curLoadedCount = $qCurLoaded ? (int)($qCurLoaded->fetch_assoc()['loaded_count'] ?? 0) : 0;

                $isTrayAlreadyLoadedForThisOrder = (
                    in_array($found['status'], ['Loaded', 'ReadyToDispatch'])
                    && (
                        (!empty($found['dispd_agst_psono']) && strcasecmp(trim($found['dispd_agst_psono']), trim($targetPsono)) === 0)
                        || (empty($found['dispd_agst_psono']) && !empty($found['psono']) && strcasecmp(trim($found['psono']), trim($targetPsono)) === 0)
                    )
                );

                if ($curLoadedCount >= $maxAllowedTrays && !$isTrayAlreadyLoadedForThisOrder) {
                    outJson([
                        'status'  => 'error',
                        'message' => "Order item is already fully loaded ({$curLoadedCount} of {$maxAllowedTrays} required trays already loaded). Cannot load additional trays."
                    ]);
                }
            }
        }
    }

    // Check if crop variety is the SAME
    $isSameVariety = isSameCropVariety($targetProduct, $targetVariety, $found['productname'], $found['variety']);

    // Check if the tray belongs to an authorized allocated batch or has already been allocated to this order
    $isAuthorizedBatch = false;

    // 1. Direct match on PSO or already dispatched/assigned to this PSO
    if (!empty($targetPso)) {
        if (!empty($trayPso) && strcasecmp($trayPso, $targetPso) === 0) {
            $isAuthorizedBatch = true;
        }
        if (!empty($found['dispd_agst_psono']) && strcasecmp(trim($found['dispd_agst_psono']), $targetPso) === 0) {
            $isAuthorizedBatch = true;
        }
    }

    // 2. Tray stock order matches the target allocated stock order (e.g. KFP4373 or multi-batch KFP3059, KFP9877)
    if (!$isAuthorizedBatch && !empty($targetStock) && (!empty($trayStock) || !empty($cleanBarcode))) {
        $targetStockArr = array_filter(array_map('trim', explode(',', $targetStock)));
        $upperTargetArr = array_map('strtoupper', $targetStockArr);

        if (in_array(strtoupper($trayStock), $upperTargetArr, true)) {
            $isAuthorizedBatch = true;
        }
        if (!empty($found['dispd_agst_stockorderno'])) {
            $dispdStock = strtoupper(trim($found['dispd_agst_stockorderno']));
            if (in_array($dispdStock, $upperTargetArr, true)) {
                $isAuthorizedBatch = true;
            }
        }
        if (!$isAuthorizedBatch && in_array(strtoupper($cleanBarcode), $upperTargetArr, true)) {
            $isAuthorizedBatch = true;
        }
        if (!$isAuthorizedBatch) {
            foreach ($upperTargetArr as $tStk) {
                $numT = preg_replace('/\D/', '', $tStk);
                $numS = preg_replace('/\D/', '', $trayStock);
                $numB = preg_replace('/\D/', '', $cleanBarcode);
                if (!empty($numT) && ($numT === $numS || $numT === $numB)) {
                    $isAuthorizedBatch = true;
                    break;
                }
            }
        }
    }

    // 3. Check in krf_productionorder_consumed or krf_sales_order_items if tray's batch is allocated to targetPso
    if (!$isAuthorizedBatch && !empty($targetPso) && !empty($trayStock)) {
        $escTargetPso = mysqli_real_escape_string($krfconn, $targetPso);
        $escTrayStock = mysqli_real_escape_string($krfconn, $trayStock);
        $chkAlloc = $krfconn->query("
            SELECT id FROM krf_productionorder_consumed 
            WHERE psono = '{$escTargetPso}' 
              AND (stockorderno = '{$escTrayStock}' OR allocated_stockorderno = '{$escTrayStock}')
              AND IFNULL(tedit, '') = ''
            LIMIT 1
        ");
        if ($chkAlloc && $chkAlloc->num_rows > 0) {
            $isAuthorizedBatch = true;
        } else {
            $chkSoi = $krfconn->query("
                SELECT id FROM krf_sales_order_items 
                WHERE (psono = '{$escTargetPso}' OR (NULLIF(TRIM(sono), '') IS NOT NULL AND sono = '{$escTargetPso}'))
                  AND (
                      stockorder_no = '{$escTrayStock}'
                      OR FIND_IN_SET('{$escTrayStock}', REPLACE(stockorder_no, ' ', ''))
                      OR stockorder_no LIKE '%{$escTrayStock}%'
                  )
                LIMIT 1
            ");
            if ($chkSoi && $chkSoi->num_rows > 0) {
                $isAuthorizedBatch = true;
            }
        }
    }

    // Check if the order crop is shifted from krf_productionorder_consumed
    $isOrderShifted = false;
    $lookupPso = !empty($targetPsono) ? $targetPsono : ($found['psono'] ?: '');
    if (!empty($lookupPso)) {
        $chkShiftSql = "
            SELECT isshifted, extraqtyforshifting 
            FROM krf_productionorder_consumed 
            WHERE psono = ? 
              AND (
                  product_id = ? 
                  OR stockorderno = ? 
                  OR allocated_stockorderno = ? 
                  OR product_name LIKE ?
                  OR ? = ''
              )
              AND IFNULL(tedit, '') = ''
            LIMIT 1
        ";
        $stmtShift = $krfconn->prepare($chkShiftSql);
        if ($stmtShift) {
            $prodId = strval($found['productid'] ?: '');
            $stkNo = strval($found['stockorder_no'] ?: $targetStockorder);
            $pNameLike = '%' . trim($found['productname'] ?: $targetProduct) . '%';
            $stmtShift->bind_param('ssssss', $lookupPso, $prodId, $stkNo, $stkNo, $pNameLike, $prodId);
            $stmtShift->execute();
            $shiftRes = $stmtShift->get_result()->fetch_assoc();
            if ($shiftRes) {
                $isOrderShifted = ($shiftRes['isshifted'] == 1 || $shiftRes['isshifted'] === '1' || strtolower(strval($shiftRes['isshifted'])) === 'yes');
            }
            $stmtShift->close();
        }
    }

    $effQtyPerTray = (int)$found['qtypertray'];
    if ($isOrderShifted) {
        $effQtyPerTray = 125;
    }

    // =========================================================
    // BRANCH 1: VARIETY MISMATCH (DIFFERENT CROP VARIETY)
    // =========================================================
    if (!$isSameVariety) {
        $escTgtPso = mysqli_real_escape_string($krfconn, $targetPso);
        $escBarcode = mysqli_real_escape_string($krfconn, $cleanBarcode);
        $escScannedStock = mysqli_real_escape_string($krfconn, $trayStock);
        $stockCond = !empty($escScannedStock) ? "OR (scanned_stockorderno = '{$escScannedStock}' AND scanned_stockorderno != '')" : "";

        // Query krf_dispatch_variety_approval_requests for this barcode OR batch for target order
        $appQ = $krfconn->query("
            SELECT * FROM krf_dispatch_variety_approval_requests 
            WHERE target_psono = '{$escTgtPso}' 
              AND (barcode = '{$escBarcode}' {$stockCond})
            ORDER BY 
              CASE WHEN barcode = '{$escBarcode}' THEN 1 ELSE 2 END,
              CASE WHEN status = 'approved' THEN 1 WHEN status = 'pending' THEN 2 ELSE 3 END,
              id DESC 
            LIMIT 1
        ");
        $appRow = ($appQ && $appQ->num_rows > 0) ? $appQ->fetch_assoc() : null;

        // Resolve dates and scanned order
        $targetDeliveryDate = getOrderDeliveryDate($krfconn, $targetPso, $targetStock);
        $scannedOriginPso   = trim($found['psono'] ?: '');
        $scannedOriginSono  = '';
        if (!empty($scannedOriginPso)) {
            $qSono = $krfconn->query("SELECT sono FROM krf_sales_order_items WHERE psono = '{$scannedOriginPso}' LIMIT 1");
            if ($qSono && $sRow = $qSono->fetch_assoc()) {
                $scannedOriginSono = $sRow['sono'];
            }
        }
        $scannedDeliveryDate = getOrderDeliveryDate($krfconn, $scannedOriginPso, $trayStock);
        if (empty($scannedDeliveryDate) && !empty($found['dispatchdate_internal'])) {
            $scannedDeliveryDate = $found['dispatchdate_internal'];
        }

        if ($appRow && $appRow['status'] === 'approved') {
            // Manager has approved this variety change! Allow loading.
            outJson([
                'status' => 'success',
                'is_approved_variety_change' => true,
                'message' => 'Approved Variety Change verified (Request: ' . $appRow['request_no'] . '). Tray is authorized for loading.',
                'data' => [
                    'polyhouseplantid'          => (int)$found['polyhouseplantid'],
                    'barcode'                   => $found['barcode'],
                    'psono'                     => $targetPso ?: ($found['psono'] ?: $targetPsono),
                    'stockorder_no'             => $targetStock ?: ($found['stockorder_no'] ?: $targetStockorder),
                    'productname'               => $found['productname'] ?: $targetProduct,
                    'variety'                   => $found['variety'] ?: '',
                    'bed_no'                    => $found['bed_no'] ?: 'Bed-?',
                    'polyhouseid'               => $found['polyhouseid'],
                    'polyhousename'             => $polyhouseName,
                    'qtypertray'                => $effQtyPerTray,
                    'is_shifted'                => $isOrderShifted,
                    'current_status'            => $found['status'] ?: 'Polyhouse',
                    'health_status'             => $found['health_status'] ?: 'Healthy',
                    'is_approved_variety_change'=> true,
                    'approval_request_no'       => $appRow['request_no'],
                    'approved_by'               => $appRow['approved_by'],
                    'approved_at'               => $appRow['approved_at'],
                    'approval_remarks'          => $appRow['approval_remarks'],
                    'is_swapped'                => false,
                    'mismatch_warning'          => ''
                ]
            ]);
        } elseif ($appRow && $appRow['status'] === 'pending') {
            // Request is pending manager approval
            outJson([
                'status' => 'approval_pending',
                'message' => 'A variety change request (' . $appRow['request_no'] . ') for this batch / tray is currently PENDING approval from the manager.',
                'data' => [
                    'barcode'              => $cleanBarcode,
                    'request_no'           => $appRow['request_no'],
                    'requested_by'         => $appRow['requested_by'],
                    'requested_at'         => $appRow['requested_at'],
                    'reason'               => $appRow['reason'],
                    'target_productname'   => $appRow['target_productname'],
                    'scanned_productname'  => $appRow['scanned_productname'],
                    'scanned_stockorderno' => $appRow['scanned_stockorderno'] ?: $trayStock,
                    'scanned_psono'        => $appRow['scanned_psono'] ?: $scannedOriginPso,
                    'target_psono'         => $appRow['target_psono'] ?: $targetPso,
                    'target_deliverydate'  => $appRow['target_deliverydate'] ?: $targetDeliveryDate,
                    'scanned_deliverydate' => $appRow['scanned_deliverydate'] ?: $scannedDeliveryDate
                ]
            ]);
        } elseif ($appRow && $appRow['status'] === 'rejected') {
            // Request was rejected by manager
            outJson([
                'status' => 'approval_rejected',
                'message' => 'The variety change request (' . $appRow['request_no'] . ') for this batch / tray was REJECTED by ' . htmlspecialchars($appRow['approved_by'] ?: 'Manager') . '. Reason: ' . htmlspecialchars($appRow['approval_remarks'] ?: 'Rejected'),
                'data' => [
                    'barcode'          => $cleanBarcode,
                    'request_no'       => $appRow['request_no'],
                    'approved_by'      => $appRow['approved_by'],
                    'approval_remarks' => $appRow['approval_remarks']
                ]
            ]);
        } else {
            // No approval request exists yet - require approval request submission
            outJson([
                'status' => 'variety_mismatch_approval_required',
                'message' => 'Variety Mismatch Detected! Target order expects ' . htmlspecialchars($targetProduct) . ', but scanned tray is ' . htmlspecialchars($found['productname']) . ($found['variety'] ? ' (' . htmlspecialchars($found['variety']) . ')' : '') . '. Manager approval is required before loading this tray.',
                'data' => [
                    'barcode'              => $cleanBarcode,
                    'polyhouseplantid'     => (int)$found['polyhouseplantid'],
                    'target_psono'         => $targetPso,
                    'target_sono'          => $targetSono,
                    'target_clientid'      => $targetClientId,
                    'target_clientname'    => $targetClientName,
                    'target_stockorderno'  => $targetStock,
                    'target_productname'   => $targetProduct,
                    'target_variety'       => $targetVariety,
                    'target_deliverydate'  => $targetDeliveryDate,
                    'scanned_psono'        => $scannedOriginPso,
                    'scanned_sono'         => $scannedOriginSono,
                    'scanned_stockorderno' => $found['stockorder_no'] ?: '',
                    'scanned_productname'  => $found['productname'] ?: '',
                    'scanned_variety'      => $found['variety'] ?: '',
                    'scanned_deliverydate' => $scannedDeliveryDate,
                    'tray_polyhouse_id'    => (int)$found['polyhouseid'],
                    'tray_polyhouse_name'  => $polyhouseName,
                    'tray_bed_no'          => $found['bed_no'] ?: '',
                    'qtypertray'           => $effQtyPerTray,
                    'bed_active_trays'     => (int)($bedActiveDetails['available_active_trays'] ?? 0),
                    'bed_total_trays'      => (int)($bedActiveDetails['total_trays'] ?? 0),
                    'bed_active_plants'    => (int)($bedActiveDetails['available_active_plants'] ?? 0)
                ]
            ]);
        }
    }

    // =========================================================
    // BRANCH 2: SAME VARIETY, BUT BATCH / ORDER MISMATCH (OTHER CLIENT'S BATCH)
    // =========================================================
    if (!$isAuthorizedBatch) {
        $originBatch = trim($found['stockorder_no'] ?: '');
        $originPso   = trim($found['psono'] ?: '');
        $batchSummary = getBatchAllocationAndDispatchSummary($krfconn, $originBatch, $originPso);

        outJson([
            'status' => 'order_mismatch_same_variety',
            'message' => 'Scanned tray belongs to Batch ' . ($originBatch ?: '-') . ' (Farmer: ' . ($batchSummary['origin_client_name'] ?: 'Other Client') . ', Order ' . ($originPso ?: '-') . '), but current loading is for Order ' . $targetPso . '. Crop varieties match (' . ($found['productname'] ?: $targetProduct) . '). Active in Bed ' . ($trayBed ?: '') . ': ' . ($bedActiveDetails['available_active_trays'] ?? 0) . ' Trays.',
            'data' => [
                'polyhouseplantid'         => (int)$found['polyhouseplantid'],
                'barcode'                  => $cleanBarcode,
                'psono'                    => $targetPso,
                'stockorder_no'            => $targetStock,
                'productname'              => $found['productname'] ?: $targetProduct,
                'variety'                  => $found['variety'] ?: '',
                'bed_no'                   => $found['bed_no'] ?: 'Bed-?',
                'polyhouseid'              => $found['polyhouseid'],
                'polyhousename'            => $polyhouseName,
                'qtypertray'               => $effQtyPerTray,
                'is_shifted'               => $isOrderShifted,
                'current_status'           => $found['status'] ?: 'Polyhouse',
                'health_status'            => $found['health_status'] ?: 'Healthy',
                'is_swapped'               => true,
                'origin_stockorderno'      => $originBatch,
                'origin_psono'             => $originPso,
                'origin_client_id'         => $batchSummary['origin_client_id'] ?? '',
                'origin_client_name'       => $batchSummary['origin_client_name'] ?? 'Unknown Farmer',
                'origin_client_phone'      => $batchSummary['origin_client_phone'] ?? '',
                'origin_delivery_date'     => $batchSummary['origin_delivery_date'] ?? '',
                'origin_order_qty'         => (int)($batchSummary['origin_order_qty'] ?? 0),
                'total_batch_trays'        => (int)($batchSummary['total_batch_trays'] ?? 0),
                'total_batch_plants'       => (int)($batchSummary['total_batch_plants'] ?? 0),
                'available_trays'          => (int)($batchSummary['available_trays'] ?? 0),
                'already_dispatched_trays' => (int)($batchSummary['already_dispatched_trays'] ?? 0),
                'already_loaded_trays'     => (int)($batchSummary['already_loaded_trays'] ?? 0),
                'bed_active_trays'         => (int)($bedActiveDetails['available_active_trays'] ?? 0),
                'bed_total_trays'          => (int)($bedActiveDetails['total_trays'] ?? 0),
                'bed_active_plants'        => (int)($bedActiveDetails['available_active_plants'] ?? 0),
                'dispatches'               => $batchSummary['dispatches'] ?? [],
                'target_stockorderno'      => $targetStock,
                'target_psono'             => $targetPso,
                'mismatch_warning'         => ''
            ]
        ]);
    }

    // =========================================================
    // BRANCH 3: STANDARD NORMAL SCAN (MATCHING BATCH & VARIETY)
    // =========================================================
    $response = [
        'status' => 'success',
        'data' => [
            'polyhouseplantid' => (int)$found['polyhouseplantid'],
            'barcode'          => !empty($found['barcode']) ? $found['barcode'] : $cleanBarcode,
            'scanned_code'     => $cleanBarcode,
            'real_barcode'     => $found['barcode'],
            'psono'            => $found['psono'] ?: $targetPsono,
            'stockorder_no'    => $found['stockorder_no'] ?: $targetStockorder,
            'real_stockorder'  => $found['stockorder_no'],
            'productname'      => $found['productname'] ?: $targetProduct,
            'variety'          => $found['variety'] ?: '',
            'bed_no'           => $found['bed_no'] ?: 'Bed-?',
            'polyhouseid'      => $found['polyhouseid'],
            'polyhousename'    => $polyhouseName,
            'qtypertray'       => $effQtyPerTray,
            'is_shifted'       => $isOrderShifted,
            'current_status'   => $found['status'] ?: 'Polyhouse',
            'health_status'    => $found['health_status'] ?: 'Healthy',
            'is_swapped'       => false,
            'bed_active_trays'  => (int)($bedActiveDetails['available_active_trays'] ?? 0),
            'bed_total_trays'   => (int)($bedActiveDetails['total_trays'] ?? 0),
            'bed_active_plants' => (int)($bedActiveDetails['available_active_plants'] ?? 0),
            'mismatch_warning' => ''
        ]
    ];

    // Autoload required active trays for this bed (Whole Bed Auto-Load on 1-Tray Scan)
    $allocBedsDetail = [];
    if (!empty($_REQUEST['allocated_beds_detail'])) {
        $decodedBeds = is_string($_REQUEST['allocated_beds_detail']) ? json_decode($_REQUEST['allocated_beds_detail'], true) : $_REQUEST['allocated_beds_detail'];
        if (is_array($decodedBeds)) $allocBedsDetail = $decodedBeds;
    }

    $targetAllocBed = $trayBed;
    $targetAllocPoly = $trayPolyId;
    $bedNeededTrays = 0;
    $bedNeededPlants = 0;

    if (!empty($allocBedsDetail)) {
        $foundAllocEntry = null;
        foreach ($allocBedsDetail as $bEntry) {
            $bBed = trim($bEntry['actual_bed_name'] ?? $bEntry['bed_name'] ?? '');
            $bStock = trim($bEntry['actual_stockorder_no'] ?? $bEntry['stockorder_no'] ?? '');
            $bedMatch = (!empty($bBed) && !empty($trayBed) && (strcasecmp($bBed, $trayBed) === 0 || preg_replace('/\D/', '', $bBed) === preg_replace('/\D/', '', $trayBed)));
            $stockMatch = (empty($bStock) || empty($trayStock) || strcasecmp($bStock, $trayStock) === 0 || preg_replace('/\D/', '', $bStock) === preg_replace('/\D/', '', $trayStock));
            if ($bedMatch && $stockMatch) {
                $foundAllocEntry = $bEntry;
                break;
            }
        }
        if (!$foundAllocEntry && count($allocBedsDetail) === 1) {
            $foundAllocEntry = $allocBedsDetail[0];
        }
        if ($foundAllocEntry) {
            $targetAllocBed = trim($foundAllocEntry['actual_bed_name'] ?? $foundAllocEntry['bed_name'] ?? $trayBed);
            $targetAllocPoly = (int)($foundAllocEntry['polyhouse_id'] ?? $trayPolyId);
            $bedNeededTrays = (int)($foundAllocEntry['tray_count'] ?? 0);
            $bedNeededPlants = (int)($foundAllocEntry['plants'] ?? 0);
        }
    }

    if ($bedNeededTrays <= 0) {
        $bedNeededTrays = (int)($_REQUEST['required_trays'] ?? 0);
    }
    if ($bedNeededTrays <= 0 && isset($maxAllowedTrays) && $maxAllowedTrays > 0) {
        $bedNeededTrays = $maxAllowedTrays;
    }
    if ($bedNeededTrays <= 0) {
        $bedNeededTrays = 1;
    }

    // Verify active tray and plant count in target allocated bed & polyhouse
    $targetBedActive = getBedActiveTrayDetails($krfconn, $trayStock, $targetAllocBed, $targetAllocPoly, $targetPsono);
    $availActiveTrays = (int)($targetBedActive['available_active_trays'] ?? 0);
    $availActivePlants = (int)($targetBedActive['available_active_plants'] ?? 0);
    $phTargetName = $polyMap[$targetAllocPoly] ?? ('PH ' . $targetAllocPoly);

    if ($availActiveTrays < $bedNeededTrays || ($bedNeededPlants > 0 && $availActivePlants < $bedNeededPlants)) {
        outJson([
            'status' => 'error',
            'message' => "Cannot load allocated tray in this polyhouse and bed! (Allocated Required: {$bedNeededTrays} Trays / " . number_format($bedNeededPlants) . " Plants, Available in {$phTargetName} - {$targetAllocBed}: {$availActiveTrays} Trays / " . number_format($availActivePlants) . " Plants). Please re-allocate."
        ]);
    }

    $autoloadedTrays = [];
    if ($bedNeededTrays > 0 && !empty($targetAllocBed)) {
        $escBedName = mysqli_real_escape_string($krfconn, $targetAllocBed);
        $scannedPlantId = (int)$found['polyhouseplantid'];
        $escPolyId = (int)$targetAllocPoly;
        $escStock = mysqli_real_escape_string($krfconn, $trayStock);
        $escTgtPso = mysqli_real_escape_string($krfconn, $targetPsono);

        $polyCond = ($escPolyId > 0) ? "AND polyhouseid = {$escPolyId}" : "";
        $stockCond = !empty($escStock) ? "AND (stockorder_no = '{$escStock}' OR dispd_agst_stockorderno = '{$escStock}')" : "";

        $escCleanBc = mysqli_real_escape_string($krfconn, $cleanBarcode);
        $qBedActive = $krfconn->query("
            SELECT 
                MAX(polyhouseplantid) as polyhouseplantid,
                barcode,
                MAX(psono) as psono,
                MAX(dispd_agst_psono) as dispd_agst_psono,
                MAX(stockorder_no) as stockorder_no,
                MAX(dispd_agst_stockorderno) as dispd_agst_stockorderno,
                MAX(productname) as productname,
                MAX(variety) as variety,
                MAX(bed_no) as bed_no,
                MAX(polyhouseid) as polyhouseid,
                MAX(qtypertray) as qtypertray,
                MAX(failurecount) as failurecount,
                MAX(status) as status,
                MAX(health_status) as health_status,
                MAX(movementremark) as movementremark
            FROM krf_polyhouse_plant_details
            WHERE bed_no = '{$escBedName}'
              {$polyCond}
              {$stockCond}
              AND (
                  LOWER(TRIM(status)) IN ('polyhouse', 'readytosale', 'ready to sale', 'ready to sell')
                  OR (LOWER(TRIM(status)) IN ('loaded', 'readytodispatch') AND (dispd_agst_psono = '{$escTgtPso}' OR psono = '{$escTgtPso}'))
              )
              AND LOWER(TRIM(status)) NOT IN ('final dispatch', 'dispatched', 'fulldispatch', 'tray dead', 'removed', 'empty after shifting', 'tray free after shifting', 'empty after grafting', 'remove after over age')
              AND (dispd_agst_psono IS NULL OR dispd_agst_psono = '' OR dispd_agst_psono = '{$escTgtPso}')
              AND barcode IS NOT NULL AND barcode != ''
            GROUP BY barcode
            ORDER BY 
              CASE WHEN LOWER(TRIM(status)) IN ('loaded', 'readytodispatch') THEN 0 ELSE 1 END,
              polyhouseplantid ASC
            LIMIT {$bedNeededTrays}
        ");

        $seenBcs = [];
        if ($qBedActive && $qBedActive->num_rows > 0) {
            while ($bRow = $qBedActive->fetch_assoc()) {
                $bCode = trim($bRow['barcode'] ?? '');
                $upperB = strtoupper($bCode);
                if (empty($upperB) || isset($seenBcs[$upperB])) continue;
                $seenBcs[$upperB] = true;

                $autoloadedTrays[] = [
                    'polyhouseplantid' => (int)$bRow['polyhouseplantid'],
                    'barcode'          => $bRow['barcode'],
                    'scanned_code'     => ($bRow['barcode'] === $cleanBarcode || $bRow['polyhouseplantid'] == $scannedPlantId) ? $cleanBarcode : $bRow['barcode'],
                    'real_barcode'     => $bRow['barcode'],
                    'psono'            => $targetPsono ?: ($bRow['dispd_agst_psono'] ?: $bRow['psono']),
                    'stockorder_no'    => $bRow['dispd_agst_stockorderno'] ?: ($bRow['stockorder_no'] ?: $targetStockorder),
                    'real_stockorder'  => $bRow['stockorder_no'],
                    'productname'      => $bRow['productname'] ?: ($found['productname'] ?: $targetProduct),
                    'variety'          => $bRow['variety'] ?: ($found['variety'] ?: ''),
                    'bed_no'           => $bRow['bed_no'] ?: $targetAllocBed,
                    'polyhouseid'      => (int)$bRow['polyhouseid'],
                    'polyhousename'    => $phTargetName,
                    'qtypertray'       => (int)($bRow['qtypertray'] ?: $effQtyPerTray),
                    'is_shifted'       => $isOrderShifted,
                    'current_status'   => $bRow['status'] ?: 'Polyhouse',
                    'health_status'    => $bRow['health_status'] ?: 'Healthy',
                    'is_swapped'       => false,
                    'mismatch_warning' => ''
                ];
                if (count($autoloadedTrays) >= $bedNeededTrays) {
                    break;
                }
            }
        }

        // Only include scanned tray if it is legitimately available (not loaded for another order)
        if (empty($scannedTrayLoadedForOtherOrder)) {
            $scannedPlantId = (int)($found['polyhouseplantid'] ?? 0);
            $scannedRealBarcode = trim($found['barcode'] ?? $cleanBarcode);
            $scannedIncluded = false;
            foreach ($autoloadedTrays as $at) {
                if ((int)($at['polyhouseplantid'] ?? 0) === $scannedPlantId || 
                    (!empty($at['barcode']) && strcasecmp(trim($at['barcode']), $scannedRealBarcode) === 0) ||
                    (!empty($at['real_barcode']) && strcasecmp(trim($at['real_barcode']), $scannedRealBarcode) === 0)) {
                    $scannedIncluded = true;
                    break;
                }
            }
            if (!$scannedIncluded && !empty($scannedRealBarcode) && !$isPrefixRsoOrSo) {
                array_unshift($autoloadedTrays, $response['data']);
                if (count($autoloadedTrays) > $bedNeededTrays) {
                    array_pop($autoloadedTrays);
                }
            }
        }
    }

    if (empty($autoloadedTrays)) {
        $autoloadedTrays = [$response['data']];
    }

    $response['data']['autoloaded_trays'] = $autoloadedTrays;
    $response['data']['autoloaded_count'] = count($autoloadedTrays);

    outJson($response);
}

// -------------------------------------------------------------
// 3. ACTION: CONFIRM & UPDATE LOADED TRAYS
// -------------------------------------------------------------
if ($action === 'confirm_load') {
    @set_time_limit(180);
    $psono         = trim($_REQUEST['psono'] ?? '');
    $itemId        = (int)($_REQUEST['item_id'] ?? 0);
    $invoiceno     = trim($_REQUEST['invoiceno'] ?? '');
    $productId     = trim($_REQUEST['product_id'] ?? '');
    $productName   = trim($_REQUEST['product_name'] ?? '');
    $traysJson     = $_REQUEST['trays'] ?? '[]';
    $loadingNotes  = trim($_REQUEST['loading_notes'] ?? '');
    $vehicleNo     = trim($_REQUEST['vehicle_no'] ?? '');

    $rawTrays = is_array($traysJson) ? $traysJson : json_decode($traysJson, true);
    if (!is_array($rawTrays) || empty($rawTrays)) {
        outJson(['status' => 'error', 'message' => 'No trays provided to load. Please scan at least one tray.']);
    }

    // Deduplicate incoming trays payload by barcode to avoid duplicate submissions
    $trays = [];
    $seenPayloadBarcodes = [];
    foreach ($rawTrays as $t) {
        $bc = is_array($t) ? trim($t['barcode'] ?? '') : trim($t);
        $normBc = strtoupper($bc);
        if (!empty($normBc) && !isset($seenPayloadBarcodes[$normBc])) {
            $seenPayloadBarcodes[$normBc] = true;
            $trays[] = $t;
        }
    }

    // Parse bonus trays payload if provided
    $bonusTraysRaw = $_REQUEST['bonus_trays'] ?? '';
    $bonusTraysMap = [];
    if (!empty($bonusTraysRaw)) {
        $decodedBonus = is_string($bonusTraysRaw) ? json_decode($bonusTraysRaw, true) : (is_array($bonusTraysRaw) ? $bonusTraysRaw : []);
        if (is_array($decodedBonus)) {
            foreach ($decodedBonus as $bt) {
                $bBc = strtoupper(trim($bt['barcode'] ?? ''));
                if (!empty($bBc)) {
                    $bonusTraysMap[$bBc] = [
                        'barcode'   => $bBc,
                        'qty_taken' => (int)($bt['qty_taken'] ?? $bt['plants'] ?? 0),
                        'capacity'  => (int)($bt['capacity'] ?? 126),
                        'bed_no'    => trim($bt['bed_no'] ?? '')
                    ];
                }
            }
        }
    }

    // Merge any bonus trays into $trays if not already present
    foreach ($bonusTraysMap as $bBc => $bData) {
        $foundInTrays = false;
        foreach ($trays as $t) {
            $bc = is_array($t) ? trim($t['barcode'] ?? '') : trim($t);
            if (strtoupper($bc) === $bBc) {
                $foundInTrays = true;
                break;
            }
        }
        if (!$foundInTrays) {
            $trays[] = [
                'barcode'  => $bBc,
                'is_bonus' => true,
                'plants'   => $bData['qty_taken']
            ];
        }
    }

    if (empty($trays)) {
        outJson(['status' => 'error', 'message' => 'No valid tray barcodes found in request.']);
    }

    // Extract and deduplicate valid barcodes
    $allBarcodesList = [];
    $escBarcodesList = [];
    foreach ($trays as $tRow) {
        $bc = is_array($tRow) ? trim($tRow['barcode'] ?? '') : trim($tRow);
        if (!empty($bc)) {
            $allBarcodesList[] = $bc;
            $escBarcodesList[] = "'" . mysqli_real_escape_string($krfconn, $bc) . "'";
        }
    }

    if (empty($allBarcodesList)) {
        outJson(['status' => 'error', 'message' => 'No valid tray barcodes found in request.']);
    }

    // Strict validation: Verify none of the trays are already Loaded, Dispatched, or Inactive
    // Batch query optimization: run single query instead of 320+ sequential queries
    $foundRestricted = [];
    if (!empty($escBarcodesList)) {
        $inSql = implode(',', array_unique($escBarcodesList));
        $chkSt = $krfconn->query("
            SELECT polyhouseplantid, barcode, status, dispd_agst_psono, dispd_agst_clientid, psono 
            FROM krf_polyhouse_plant_details 
            WHERE barcode IN ({$inSql}) 
              AND LOWER(TRIM(status)) IN ('loaded', 'readytodispatch', 'final dispatch', 'internal dispatch', 'dispatched', 'fulldispatch', 'tray dead', 'removed', 'empty after shifting', 'tray free after shifting', 'empty after grafting', 'remove after over age')
        ");
        if ($chkSt && $chkSt->num_rows > 0) {
            while ($r = $chkSt->fetch_assoc()) {
                $foundRestricted[strtoupper(trim($r['barcode']))] = $r;
            }
        }

        // For any barcodes not restricted in KPPD, check RTS in batch
        $missingBcs = [];
        foreach ($allBarcodesList as $bc) {
            $ubc = strtoupper($bc);
            if (!isset($foundRestricted[$ubc])) {
                $missingBcs[] = "'" . mysqli_real_escape_string($krfconn, $bc) . "'";
            }
        }
        if (!empty($missingBcs)) {
            $inMissing = implode(',', array_unique($missingBcs));
            $chkRts = $krfconn->query("
                SELECT id as polyhouseplantid, barcode, status, dispd_agst_psono, dispd_agst_clientid, psono 
                FROM krf_readytosale_stocks 
                WHERE barcode IN ({$inMissing}) 
                  AND status IN ('Loaded', 'ReadyToDispatch', 'Final Dispatch', 'Internal Dispatch', 'Dispatched', 'FullDispatch', 'fulldispatch', 'Tray Dead', 'Removed')
            ");
            if ($chkRts && $chkRts->num_rows > 0) {
                while ($r = $chkRts->fetch_assoc()) {
                    $foundRestricted[strtoupper(trim($r['barcode']))] = $r;
                }
            }
        }
    }

    foreach ($allBarcodesList as $bc) {
        $ubc = strtoupper($bc);
        if (isset($foundRestricted[$ubc])) {
            $rSt = $foundRestricted[$ubc];
            $existingPso = !empty($rSt['dispd_agst_psono']) ? trim($rSt['dispd_agst_psono']) : trim($rSt['psono'] ?? '');
            $existingCId = !empty($rSt['dispd_agst_clientid']) ? trim($rSt['dispd_agst_clientid']) : '';
            $stLower = strtolower(trim($rSt['status']));
            $isAlreadyDispatched = in_array($stLower, ['final dispatch', 'internal dispatch', 'dispatched', 'fulldispatch', 'full dispatch']);
            $isOtherOrder = (!empty($existingPso) && !empty($psono) && strcasecmp(trim($existingPso), trim($psono)) !== 0);
            $isDeadOrRemoved = in_array($stLower, ['tray dead', 'removed', 'empty after shifting', 'tray free after shifting', 'empty after grafting', 'remove after over age']);

            if ($isAlreadyDispatched) {
                $clientName = getClientNameByPso($krfconn, $existingPso, $existingCId);
                $psoInfo = !empty($existingPso) ? " (Order: {$existingPso})" : "";
                outJson([
                    'status'      => 'error',
                    'error_type'  => 'tray_dispatched',
                    'client_name' => $clientName,
                    'psono'       => $existingPso,
                    'barcode'     => $bc,
                    'message'     => "Tray '{$bc}' is already DISPATCHED to {$clientName}{$psoInfo}. Dispatched trays cannot be loaded again."
                ]);
            }

            if ($isOtherOrder) {
                $clientName = getClientNameByPso($krfconn, $existingPso, $existingCId);
                outJson([
                    'status'      => 'error',
                    'error_type'  => 'tray_allocated_to_other',
                    'client_name' => $clientName,
                    'psono'       => $existingPso,
                    'barcode'     => $bc,
                    'message'     => "Tray '{$bc}' is already ALLOCATED / LOADED for {$clientName} (Order: {$existingPso}, Status: {$rSt['status']}). A tray cannot be loaded or allocated multiple times."
                ]);
            }

            if ($isDeadOrRemoved) {
                outJson([
                    'status'      => 'error',
                    'error_type'  => 'tray_inactive',
                    'barcode'     => $bc,
                    'message'     => "Tray '{$bc}' is marked as '{$rSt['status']}'. Only active trays can be loaded."
                ]);
            }
        }
    }

    try {
        // Begin database transaction
        $krfconn->begin_transaction();

        $loadedCount = 0;
        $totalLoadedPlants = 0;
        $updatedBarcodes = [];

        // Resolve client ID and default stock order number for the target sales order
        $clientId = '';
        $orderStockOrderNo = '';
        $orderProductId = '';
        $orderProductName = '';

        if ($itemId > 0) {
            $csQ = $krfconn->prepare("
                SELECT 
                    COALESCE(NULLIF(so.clientid, ''), NULLIF(pso.clientid, ''), '') AS clientid,
                    soi.stockorder_no,
                    soi.psono,
                    soi.product_id,
                    soi.product_name
                FROM krf_sales_order_items soi
                LEFT JOIN krf_sales_order so ON soi.sono = so.sono
                LEFT JOIN krf_provisional_sales_order pso ON soi.psono = pso.psono
                WHERE soi.id = ? LIMIT 1
            ");
            if ($csQ) {
                $csQ->bind_param('i', $itemId);
                $csQ->execute();
                $csRes = $csQ->get_result()->fetch_assoc();
                if ($csRes) {
                    if (!empty($csRes['clientid'])) $clientId = trim($csRes['clientid']);
                    if (!empty($csRes['stockorder_no'])) $orderStockOrderNo = trim($csRes['stockorder_no']);
                    if (empty($psono) && !empty($csRes['psono'])) $psono = trim($csRes['psono']);
                    if (!empty($csRes['product_id'])) $orderProductId = trim(strval($csRes['product_id']));
                    if (!empty($csRes['product_name'])) $orderProductName = trim($csRes['product_name']);
                }
                $csQ->close();
            }
        }

        if (empty($clientId) && !empty($psono)) {
            $cpQ = $krfconn->prepare("SELECT clientid FROM krf_provisional_sales_order WHERE psono = ? LIMIT 1");
            if ($cpQ) {
                $cpQ->bind_param('s', $psono);
                $cpQ->execute();
                $cpRes = $cpQ->get_result()->fetch_assoc();
                if (!empty($cpRes['clientid'])) $clientId = trim($cpRes['clientid']);
                $cpQ->close();
            }
        }

        if (empty($clientId) && !empty($psono)) {
            $csoQ = $krfconn->prepare("SELECT clientid FROM krf_sales_order WHERE psono = ? LIMIT 1");
            if ($csoQ) {
                $csoQ->bind_param('s', $psono);
                $csoQ->execute();
                $csoRes = $csoQ->get_result()->fetch_assoc();
                if (!empty($csoRes['clientid'])) $clientId = trim($csoRes['clientid']);
                $csoQ->close();
            }
        }

        if (empty($orderStockOrderNo) && !empty($psono)) {
            $soiQ = $krfconn->prepare("SELECT stockorder_no FROM krf_sales_order_items WHERE psono = ? AND stockorder_no IS NOT NULL AND stockorder_no != '' LIMIT 1");
            if ($soiQ) {
                $soiQ->bind_param('s', $psono);
                $soiQ->execute();
                $soiRes = $soiQ->get_result()->fetch_assoc();
                if (!empty($soiRes['stockorder_no'])) $orderStockOrderNo = trim($soiRes['stockorder_no']);
                $soiQ->close();
            }
        }

        // Strict Loading Limit Validation: Prevent loading more trays than required
        if (!empty($psono)) {
            $escPso = mysqli_real_escape_string($krfconn, $psono);
            $escProdReq = mysqli_real_escape_string($krfconn, $productId ?: $orderProductId);
            $escProdNameReq = mysqli_real_escape_string($krfconn, $productName ?: $orderProductName);

            $itemCond = ($itemId > 0)
                ? "AND (soi.id = {$itemId} OR soi.id IN (SELECT original_soi_id FROM sales_invoice_items WHERE id = {$itemId} AND original_soi_id > 0))"
                : (!empty($escProdReq) || !empty($escProdNameReq)
                    ? "AND (" . (!empty($escProdReq) ? "soi.product_id = '{$escProdReq}'" : "") . (!empty($escProdReq) && !empty($escProdNameReq) ? " OR " : "") . (!empty($escProdNameReq) ? "soi.product_name = '{$escProdNameReq}'" : "") . ")"
                    : "");

            $qTgtLimit = $krfconn->query("
                SELECT 
                    soi.id,
                    soi.product_id,
                    soi.product_name,
                    soi.stockorder_no,
                    soi.quantity,
                    soi.allocate_ordqty,
                    soi.isshifted AS soi_isshifted,
                    poc.stockorderno AS poc_stockorderno,
                    poc.allocated_stockorderno AS poc_allocated_stockorderno,
                    poc.allocate_healthyplant,
                    poc.consumed_qty,
                    poc.isshifted AS poc_isshifted
                FROM krf_sales_order_items soi
                LEFT JOIN krf_productionorder_consumed poc ON (
                    (NULLIF(TRIM(soi.psono), '') IS NOT NULL AND soi.psono = poc.psono AND (
                        (NULLIF(TRIM(soi.product_id), '') IS NOT NULL AND soi.product_id = poc.product_id)
                        OR (NULLIF(TRIM(soi.stockorder_no), '') IS NOT NULL AND (soi.stockorder_no = poc.stockorderno OR soi.stockorder_no = poc.allocated_stockorderno))
                    ))
                    OR (NULLIF(TRIM(soi.sono), '') IS NOT NULL AND soi.sono = poc.sono AND NULLIF(TRIM(soi.stockorder_no), '') IS NOT NULL AND soi.stockorder_no = poc.stockorderno)
                ) AND IFNULL(poc.tedit, '') = ''
                WHERE (soi.psono = '{$escPso}' OR soi.sono = '{$escPso}') {$itemCond}
                LIMIT 1
            ");

            if ($qTgtLimit && $tgtLimitRow = $qTgtLimit->fetch_assoc()) {
                $soiAlloc    = (int)($tgtLimitRow['allocate_ordqty'] ?? 0);
                $pocConsumed = (int)($tgtLimitRow['consumed_qty'] ?? 0);
                $pocH        = (int)($tgtLimitRow['allocate_healthyplant'] ?? 0);
                $orderQty    = (int)($tgtLimitRow['quantity'] ?? 0);

                if ($soiAlloc > 0) {
                    $targetQty = $soiAlloc;
                } elseif ($pocConsumed > 0) {
                    $targetQty = $pocConsumed;
                } elseif ($pocH > 0 && ($orderQty <= 0 || $pocH <= $orderQty)) {
                    $targetQty = $pocH;
                } else {
                    $targetQty = $orderQty;
                }
                $cap = ((int)($tgtLimitRow['poc_isshifted'] ?? 0) === 1 || ($tgtLimitRow['soi_isshifted'] ?? '') === 'yes') ? 125 : 126;
                $maxAllowedTrays = ($targetQty > 0) ? (int)ceil($targetQty / $cap) : 0;

                // Priority: Check krf_readystock_allocation for this specific product
                $chkPId = !empty($tgtLimitRow['product_id']) ? $tgtLimitRow['product_id'] : $orderProductId;
                $chkPName = !empty($tgtLimitRow['product_name']) ? $tgtLimitRow['product_name'] : $orderProductName;
                $escPIdLim = mysqli_real_escape_string($krfconn, $chkPId);
                $escPNameLim = mysqli_real_escape_string($krfconn, $chkPName);
                $prodRsaFilter = "";
                if (!empty($escPIdLim) && !empty($escPNameLim)) {
                    $prodRsaFilter = " AND (productid = '{$escPIdLim}' OR productname = '{$escPNameLim}')";
                } elseif (!empty($escPIdLim)) {
                    $prodRsaFilter = " AND productid = '{$escPIdLim}'";
                } elseif (!empty($escPNameLim)) {
                    $prodRsaFilter = " AND productname = '{$escPNameLim}'";
                }

                $qRsaLimit = $krfconn->query("
                    SELECT allocate_ordqty, healthyplant, allocatedbed, isshifted, shiftingqty
                    FROM krf_readystock_allocation
                    WHERE (NULLIF(TRIM(psono), '') IS NOT NULL AND psono = '{$escPso}')
                      AND LOWER(IFNULL(status, '')) != 'cancel'
                      AND (tedit IS NULL OR tedit = '')
                      {$prodRsaFilter}
                    ORDER BY id DESC LIMIT 1
                ");
                if ($qRsaLimit && $rRsa = $qRsaLimit->fetch_assoc()) {
                    $rsaAllocQty = (int)($rRsa['allocate_ordqty'] ?? $rRsa['healthyplant'] ?? 0);
                    if ($rsaAllocQty > 0) {
                        $targetQty = $rsaAllocQty;
                    }
                    if ($rRsa['isshifted'] == 1 || $rRsa['isshifted'] === '1' || strtolower(strval($rRsa['isshifted'])) === 'yes') {
                        $cap = 125;
                    }
                    $calcRsaTrays = ($targetQty > 0) ? (int)ceil($targetQty / $cap) : $maxAllowedTrays;
                    $rawBed = trim($rRsa['allocatedbed'] ?? '');
                    if (!empty($rawBed)) {
                        $dec = json_decode($rawBed, true);
                        if (is_array($dec)) {
                            $sumTrays = 0;
                            foreach ($dec as $b) {
                                $bName = trim($b['bed_name'] ?? '');
                                if (!empty($bName) && !preg_match('/^(RSO|KFP|KF-PS|PSO|KF-|SO-|PO-|BATCH-)/i', $bName) && !in_array(strtolower($bName), ['karjee', 'n/a', 'na', 'none', '-', 'bed'])) {
                                    $sumTrays += (int)($b['tray_count'] ?? 0);
                                }
                            }
                            if ($sumTrays > 0 && ($calcRsaTrays <= 0 || $sumTrays <= ceil($calcRsaTrays * 1.25))) {
                                $maxAllowedTrays = $sumTrays;
                            } elseif ($calcRsaTrays > 0) {
                                $maxAllowedTrays = $calcRsaTrays;
                            }
                        }
                    } elseif ($calcRsaTrays > 0) {
                        $maxAllowedTrays = $calcRsaTrays;
                    }
                }

                if ($maxAllowedTrays > 0) {
                    $escProdName = mysqli_real_escape_string($krfconn, $tgtLimitRow['product_name'] ?? '');
                    $escProdId = mysqli_real_escape_string($krfconn, $tgtLimitRow['product_id'] ?? '');
                    $escStockNo = mysqli_real_escape_string($krfconn, $tgtLimitRow['stockorder_no'] ?? '');
                    $escOrigStock = mysqli_real_escape_string($krfconn, $tgtLimitRow['poc_stockorderno'] ?? '');
                    $escAllocStock = mysqli_real_escape_string($krfconn, $tgtLimitRow['poc_allocated_stockorderno'] ?? '');

                    $itemFilterSql = "";
                    if (!empty($escProdId) && !empty($escProdName)) {
                        $itemFilterSql = "AND (productname = '{$escProdName}' OR productid = '{$escProdId}')";
                    } elseif (!empty($escProdId)) {
                        $itemFilterSql = "AND productid = '{$escProdId}'";
                    } elseif (!empty($escProdName)) {
                        $itemFilterSql = "AND productname = '{$escProdName}'";
                    } elseif (!empty($escStockNo) || !empty($escAllocStock)) {
                        $rawStk = array_merge(explode(',', $escStockNo), explode(',', $escOrigStock), explode(',', $escAllocStock));
                        $stkList = array_values(array_filter(array_map('trim', $rawStk)));
                        $stkIn = "'" . implode("','", array_map(function($s) use ($krfconn) { return mysqli_real_escape_string($krfconn, $s); }, $stkList)) . "'";
                        $itemFilterSql = "AND (dispd_agst_stockorderno IN ({$stkIn}) OR stockorder_no IN ({$stkIn}))";
                    }

                    $qCurLoaded = $krfconn->query("
                        SELECT COUNT(DISTINCT COALESCE(NULLIF(barcode, ''), CONCAT('ID_', polyhouseplantid))) as loaded_count
                        FROM krf_polyhouse_plant_details
                        WHERE (dispd_agst_psono = '{$escPso}' OR (psono = '{$escPso}' AND (dispd_agst_psono IS NULL OR dispd_agst_psono = '' OR dispd_agst_psono = '{$escPso}')))
                          AND status IN ('Loaded', 'ReadyToDispatch')
                          {$itemFilterSql}
                    ");
                    $curLoadedCount = $qCurLoaded ? (int)($qCurLoaded->fetch_assoc()['loaded_count'] ?? 0) : 0;
                    $newTraysCount = count($trays);

                    if ($curLoadedCount + $newTraysCount > $maxAllowedTrays && empty($_REQUEST['single_tray_verification'])) {
                        $krfconn->rollback();
                        $pendingCanLoad = max(0, $maxAllowedTrays - $curLoadedCount);
                        outJson([
                            'status'  => 'error',
                            'message' => "Loading limit exceeded! Order item only requires {$maxAllowedTrays} tray(s) total ({$curLoadedCount} already loaded, {$pendingCanLoad} pending). You cannot load {$newTraysCount} more tray(s)."
                        ]);
                    }
                }
            }
        }

        // Prepare statement to update krf_polyhouse_plant_details
        $updPlantStmt = $krfconn->prepare("
            UPDATE krf_polyhouse_plant_details
            SET 
                status = 'Loaded',
                movementremark = ?,
                dispatchqty = ?,
                dispatchat = NOW(),
                dispatchby = ?,
                dispd_agst_clientid = ?,
                dispd_agst_stockorderno = ?,
                dispd_agst_psono = ?,
                move_at = NOW(),
                updated_at = NOW(),
                updated_by = ?
            WHERE polyhouseplantid = ?
        ");

        // Prepare statement to update krf_readytosale_stocks
        $updRtsStmt = $krfconn->prepare("
            UPDATE krf_readytosale_stocks
            SET 
                dispatchqty = ?,
                dispatchat = NOW(),
                dispatchby = ?,
                dispd_agst_clientid = ?,
                dispd_agst_stockorderno = ?,
                dispd_agst_psono = ?,
                loaded_qty = ?,
                loaded_at = NOW(),
                loaded_by = ?,
                status = 'Loaded',
                updated_at = NOW()
            WHERE barcode = ?
        ");

        // Prepare statement to insert into krf_readytosale_stocks if not existing
        $insRtsStmt = $krfconn->prepare("
            INSERT INTO krf_readytosale_stocks (
                stockorder_no,
                psono,
                projectid,
                productid,
                productname,
                trayid,
                qtypertray,
                locationid,
                polyhouseplantid,
                barcode,
                bedqrcode,
                status,
                created_by,
                created_at,
                updated_at,
                dispatchqty,
                dispatchat,
                dispatchby,
                dispd_agst_clientid,
                dispd_agst_stockorderno,
                dispd_agst_psono,
                loaded_qty,
                loaded_at,
                loaded_by
            ) VALUES (
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, 'Loaded', ?, NOW(), NOW(),
                ?, NOW(), ?, ?,
                ?, ?, ?, NOW(), ?
            )
        ");

        $histStmt = $krfconn->prepare("
            INSERT INTO krf_traymovement_history (
                transaction_no,
                projectid,
                psono,
                stockorder_no,
                productname,
                activity_type,
                barcode,
                from_polyhouse_id,
                from_bed_no,
                previous_status,
                current_status,
                live_plant_count,
                reason_for_movement,
                movement_date,
                movement_time,
                created_by,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Loaded', ?, ?, CURDATE(), CURTIME(), ?, NOW())
        ");

        $txNo = 'LOAD-' . date('YmdHis') . '-' . rand(100, 999);
        $remarkText = 'Loaded for Order ' . $psono . (!empty($invoiceno) ? (' | Inv: ' . $invoiceno) : '') . (!empty($vehicleNo) ? (' | Vehicle: ' . $vehicleNo) : '');

        // Check if shifted for this order in krf_productionorder_consumed
        $isShiftedOrder = false;
        if (!empty($psono)) {
            $chkShiftSql = "SELECT isshifted, extraqtyforshifting FROM krf_productionorder_consumed WHERE psono = ? AND IFNULL(tedit, '') = '' LIMIT 1";
            $stmtS = $krfconn->prepare($chkShiftSql);
            if ($stmtS) {
                $stmtS->bind_param('s', $psono);
                $stmtS->execute();
                $sR = $stmtS->get_result()->fetch_assoc();
                if ($sR && ($sR['isshifted'] == 1 || $sR['isshifted'] === '1' || strtolower(strval($sR['isshifted'])) === 'yes')) {
                    $isShiftedOrder = true;
                }
                $stmtS->close();
            }
        }

        $swappedBatchesMap = [];

        // Batch pre-fetch all tray details and RTS status in 2 quick queries instead of 640+ serial queries
        $allTrayData = [];
        $allEscBcs = [];
        foreach ($trays as $t) {
            $bc = is_array($t) ? trim($t['barcode'] ?? '') : trim($t);
            if (!empty($bc)) {
                $allEscBcs[] = "'" . mysqli_real_escape_string($krfconn, $bc) . "'";
            }
        }
        if (!empty($allEscBcs)) {
            $inSql = implode(',', array_unique($allEscBcs));
            $q = $krfconn->query("
                SELECT 
                    polyhouseplantid,
                    projectid,
                    productid,
                    productname,
                    tray_id,
                    polyhouseid,
                    bed_no,
                    bedqrcode,
                    status,
                    qtypertray,
                    failurecount,
                    stockorder_no,
                    psono,
                    barcode 
                FROM krf_polyhouse_plant_details 
                WHERE barcode IN ({$inSql})
            ");
            if ($q) {
                while ($row = $q->fetch_assoc()) {
                    $bcUpper = strtoupper(trim($row['barcode']));
                    if (!isset($allTrayData[$bcUpper])) {
                        $allTrayData[$bcUpper] = $row;
                    }
                }
            }
        }

        // Pre-fetch existing RTS IDs for all barcodes
        $rtsExistsMap = [];
        if (!empty($allEscBcs)) {
            $inSql = implode(',', array_unique($allEscBcs));
            $qRts = $krfconn->query("SELECT id, barcode FROM krf_readytosale_stocks WHERE barcode IN ({$inSql})");
            if ($qRts) {
                while ($rRow = $qRts->fetch_assoc()) {
                    $rtsExistsMap[strtoupper(trim($rRow['barcode']))] = $rRow['id'];
                }
            }
        }

        // Batch cleanup: Check if any barcode in $allEscBcs has duplicate rows in KPPD
        if (!empty($allEscBcs)) {
            $inSql = implode(',', array_unique($allEscBcs));
            $dupCheck = $krfconn->query("
                SELECT barcode 
                FROM krf_polyhouse_plant_details 
                WHERE barcode IN ({$inSql}) 
                GROUP BY barcode 
                HAVING COUNT(*) > 1
            ");
            if ($dupCheck && $dupCheck->num_rows > 0) {
                while ($dupRow = $dupCheck->fetch_assoc()) {
                    $bcDup = strtoupper(trim($dupRow['barcode']));
                    if (isset($allTrayData[$bcDup])) {
                        $keepId = (int)$allTrayData[$bcDup]['polyhouseplantid'];
                        $escB = mysqli_real_escape_string($krfconn, $dupRow['barcode']);
                        $krfconn->query("DELETE FROM krf_polyhouse_plant_details WHERE barcode = '{$escB}' AND polyhouseplantid != {$keepId}");
                    }
                }
            }
        }

        foreach ($trays as $t) {
            $barcode = is_array($t) ? trim($t['barcode'] ?? '') : trim($t);
            if (empty($barcode)) continue;

            $isSwapped          = is_array($t) && !empty($t['is_swapped']);
            $originStockOrder   = is_array($t) && !empty($t['origin_stockorderno']) ? trim($t['origin_stockorderno']) : '';
            $originPso          = is_array($t) && !empty($t['origin_psono']) ? trim($t['origin_psono']) : '';
            $isApprovedVariety  = is_array($t) && !empty($t['is_approved_variety_change']);
            $appReqNo           = is_array($t) && !empty($t['approval_request_no']) ? trim($t['approval_request_no']) : '';

            // Fetch current tray info from pre-fetched batch map
            $curr = $allTrayData[strtoupper($barcode)] ?? null;

            if ($curr) {
                $prevStatus = $curr['status'] ?: 'Polyhouse';
                $qtyPerTray = (int)($curr['qtypertray'] ?: 126);
                $failureCount = (int)($curr['failurecount'] ?: 0);
                $trayDispatchQty = ($failureCount > 0 && $failureCount < $qtyPerTray) ? ($qtyPerTray - $failureCount) : $qtyPerTray;
                $phId = (int)$curr['polyhouseid'];
                $bedNo = $curr['bed_no'] ?: '';
                $prodName = $curr['productname'] ?: '';
                $trayOriginStockOrder = trim($curr['stockorder_no'] ?: $orderStockOrderNo);
                $dispdAgstStockOrder  = (!empty($orderStockOrderNo) && strpos($orderStockOrderNo, ',') === false) ? $orderStockOrderNo : (!empty($curr['stockorder_no']) ? trim($curr['stockorder_no']) : $orderStockOrderNo);
                $targetPsono = trim($psono ?: ($curr['psono'] ?: ''));
                $upperBc = strtoupper($barcode);
                $isBonusTray = isset($bonusTraysMap[$upperBc]);
                $bonusPlantsTaken = $isBonusTray ? $bonusTraysMap[$upperBc]['qty_taken'] : 0;
                if ($isBonusTray && $bonusPlantsTaken > 0) {
                    $trayDispatchQty = $bonusPlantsTaken;
                    $strDispatchQty = strval($bonusPlantsTaken);
                }

                // Determine movement remark
                if ($isBonusTray && $bonusPlantsTaken > 0) {
                    $trayRemark = "Free Bonus ({$bonusPlantsTaken} plants) | Loaded for Order " . $targetPsono . (!empty($vehicleNo) ? (' | Vehicle: ' . $vehicleNo) : '');
                } elseif ($isApprovedVariety) {
                    $trayRemark = "Approved Variety Change (" . (!empty($appReqNo) ? $appReqNo : 'Approved') . ") | Loaded for Order " . $targetPsono . (!empty($vehicleNo) ? (' | Vehicle: ' . $vehicleNo) : '');
                } elseif ($isSwapped) {
                    $trayRemark = "Swapped from Batch " . (!empty($originStockOrder) ? $originStockOrder : $trayOriginStockOrder) . " (Order " . (!empty($originPso) ? $originPso : $curr['psono']) . ") to Order " . $targetPsono . (!empty($vehicleNo) ? (' | Vehicle: ' . $vehicleNo) : '');
                } else {
                    $trayRemark = $remarkText;
                }

                // 1. Update primary row in krf_polyhouse_plant_details
                $primaryPlantId = (int)$curr['polyhouseplantid'];
                if ($updPlantStmt) {
                    $updPlantStmt->bind_param(
                        'sssssssi',
                        $trayRemark,
                        $strDispatchQty,
                        $loginname,
                        $clientId,
                        $dispdAgstStockOrder,
                        $targetPsono,
                        $loginname,
                        $primaryPlantId
                    );
                    $updPlantStmt->execute();
                }

                // 2. Update or Insert krf_readytosale_stocks
                $rtsId = $rtsExistsMap[strtoupper($barcode)] ?? null;

                if ($rtsId) {
                    if ($updRtsStmt) {
                        $updRtsStmt->bind_param(
                            'sssssiss',
                            $strDispatchQty,
                            $loginname,
                            $clientId,
                            $dispdAgstStockOrder,
                            $targetPsono,
                            $trayDispatchQty,
                            $loginname,
                            $barcode
                        );
                        $updRtsStmt->execute();
                    }
                } else {
                    if ($insRtsStmt) {
                        $pId = (int)($curr['projectid'] ?: $projectid);
                        $prodId = strval($curr['productid'] ?: '');
                        $trayId = (int)($curr['tray_id'] ?: 1);
                        $locId = strval($curr['polyhouseid'] ?: '');
                        $polyPlantId = strval($curr['polyhouseplantid'] ?: '');
                        $bedQr = strval($curr['bedqrcode'] ?: $curr['bed_no'] ?: '');

                        $insRtsStmt->bind_param(
                            'ssissiissssssssssis',
                            $trayOriginStockOrder,
                            $targetPsono,
                            $pId,
                            $prodId,
                            $prodName,
                            $trayId,
                            $qtyPerTray,
                            $locId,
                            $polyPlantId,
                            $barcode,
                            $bedQr,
                            $loginname,
                            $strDispatchQty,
                            $loginname,
                            $clientId,
                            $dispdAgstStockOrder,
                            $targetPsono,
                            $trayDispatchQty,
                            $loginname
                        );
                        $insRtsStmt->execute();
                    }
                }

                // 3. If swapped: collect and aggregate swapped batch allocation details
                if ($isSwapped) {
                    $swappedBatch = !empty($originStockOrder) ? $originStockOrder : $trayOriginStockOrder;
                    $swappedPso = !empty($originPso) ? $originPso : ($curr['psono'] ?: '');
                    if (!empty($swappedBatch) && strcasecmp($swappedBatch, $dispdAgstStockOrder) !== 0) {
                        if (!isset($swappedBatchesMap[$swappedBatch])) {
                            $swappedBatchesMap[$swappedBatch] = [
                                'batch'       => $swappedBatch,
                                'origin_pso'  => $swappedPso,
                                'product_id'  => strval($curr['productid'] ?: $orderProductId),
                                'product_name'=> $prodName ?: $orderProductName,
                                'tray_count'  => 0,
                                'plants'      => 0
                            ];
                        }
                        $swappedBatchesMap[$swappedBatch]['tray_count']++;
                        $swappedBatchesMap[$swappedBatch]['plants'] += $trayDispatchQty;
                    }
                }

                // 4. If approved variety change: mark is_loaded = 1 in krf_dispatch_variety_approval_requests
                if ($isApprovedVariety) {
                    $escBarcode = mysqli_real_escape_string($krfconn, $barcode);
                    $escTargetPso = mysqli_real_escape_string($krfconn, $targetPsono);
                    $escOriginStock = mysqli_real_escape_string($krfconn, $trayOriginStockOrder);
                    $stockCond = !empty($escOriginStock) ? "OR (scanned_stockorderno = '{$escOriginStock}' AND scanned_stockorderno != '')" : "";
                    $krfconn->query("
                        UPDATE krf_dispatch_variety_approval_requests 
                        SET 
                            is_loaded = 1,
                            loaded_at = NOW(),
                            updated_at = NOW()
                        WHERE (barcode = '{$escBarcode}' {$stockCond})
                          AND target_psono = '{$escTargetPso}' 
                          AND status = 'approved'
                    ");
                }

                // 5. Insert movement history
                if ($histStmt) {
                    $histActivityType = $isBonusTray ? 'FREE_BONUS_DISPATCH' : ($isApprovedVariety ? 'VARIETY_CHANGE_DISPATCH' : ($isSwapped ? 'SWAPPED_DISPATCH' : 'INTERNAL_DISPATCH'));
                    $histNotes = !empty($loadingNotes) ? $loadingNotes : $trayRemark;

                    $histStmt->bind_param(
                        'sisssssississ',
                        $txNo,
                        $projectid,
                        $targetPsono,
                        $trayOriginStockOrder,
                        $prodName,
                        $histActivityType,
                        $barcode,
                        $phId,
                        $bedNo,
                        $prevStatus,
                        $trayDispatchQty,
                        $histNotes,
                        $loginname
                    );
                    $histStmt->execute();
                }

                $loadedCount++;
                $totalLoadedPlants += $trayDispatchQty;
                $updatedBarcodes[] = $barcode;
            }
        }

        if ($fetchQ) $fetchQ->close();
        if ($chkRts) $chkRts->close();
        if ($updPlantStmt) $updPlantStmt->close();
        if ($updRtsStmt) $updRtsStmt->close();
        if ($insRtsStmt) $insRtsStmt->close();
        if ($histStmt) $histStmt->close();

        // -------------------------------------------------------------
        // 3. If swapped: update krf_productionorder_consumed and krf_readystock_allocation
        // Only update 'krf_readystock_allocation' if multiple client data allocate while loading tray.
        // If multiple client data allocate, multiple corresponding entries are maintained in krf_readystock_allocation.
        // -------------------------------------------------------------
        if (!empty($swappedBatchesMap)) {
            $allSwappedBatches = array_keys($swappedBatchesMap);
            $allSwappedBatchesStr = implode(', ', $allSwappedBatches);
            $allOriginPsos = array_unique(array_filter(array_column($swappedBatchesMap, 'origin_pso')));
            $allOriginPsosStr = implode(', ', $allOriginPsos);

            $notesArr = [];
            foreach ($swappedBatchesMap as $b => $bData) {
                $notesArr[] = "Batch {$b}" . (!empty($bData['origin_pso']) ? " (Order {$bData['origin_pso']})" : "") . ": {$bData['plants']} plants";
            }
            $swapSummaryNote = "Swapped with " . implode(" | ", $notesArr);

            $escTargetPso = mysqli_real_escape_string($krfconn, $psono);
            $escSwappedBatches = mysqli_real_escape_string($krfconn, $allSwappedBatchesStr);
            $escSwappedPsos = mysqli_real_escape_string($krfconn, $allOriginPsosStr);
            $escSwapSummary = mysqli_real_escape_string($krfconn, $swapSummaryNote);
            $targetStock = !empty($orderStockOrderNo) ? $orderStockOrderNo : $allSwappedBatches[0];
            $escTargetStock = mysqli_real_escape_string($krfconn, $targetStock);

            $targetProdId = strval($orderProductId ?? '');
            $targetProdName = strval($orderProductName ?? '');
            if (empty($targetProdId) && !empty($swappedBatchesMap)) {
                $firstBatchInfo = reset($swappedBatchesMap);
                $targetProdId = $firstBatchInfo['product_id'];
                $targetProdName = $firstBatchInfo['product_name'];
            }
            $escProdId = mysqli_real_escape_string($krfconn, $targetProdId);
            $escProdName = mysqli_real_escape_string($krfconn, $targetProdName);

            // 1. Update Target Order in krf_productionorder_consumed
            $krfconn->query("
                UPDATE krf_productionorder_consumed 
                SET 
                    allocated_stockorderno = '{$escSwappedBatches}',
                    allocated_psono = '{$escSwappedPsos}',
                    remark = CASE 
                        WHEN remark IS NULL OR remark = '' THEN '{$escSwapSummary}'
                        WHEN remark NOT LIKE '%{$escSwappedBatches}%' THEN CONCAT(remark, ' | ', '{$escSwapSummary}')
                        ELSE remark 
                    END,
                    allocatedby = '{$loginname}',
                    allocatedat = NOW(),
                    updated_at = NOW()
                WHERE (psono = '{$escTargetPso}' OR (NULLIF(TRIM(sono), '') IS NOT NULL AND sono = '{$escTargetPso}'))
                  AND (
                      stockorderno = '{$escTargetStock}' 
                      OR allocated_stockorderno = '{$escTargetStock}' 
                      OR product_id = '{$escProdId}'
                      OR '{$escTargetStock}' = ''
                  )
                  AND IFNULL(tedit, '') = ''
            ");

            // 2. Update Origin Orders in krf_productionorder_consumed
            foreach ($swappedBatchesMap as $b => $bData) {
                $escOrigPso = mysqli_real_escape_string($krfconn, $bData['origin_pso']);
                $escOrigBatch = mysqli_real_escape_string($krfconn, $b);
                $origNote = mysqli_real_escape_string($krfconn, "{$bData['plants']} plants swapped to Order {$psono}");
                if (!empty($escOrigPso) || !empty($escOrigBatch)) {
                    $krfconn->query("
                        UPDATE krf_productionorder_consumed 
                        SET 
                            remark = CASE 
                                WHEN remark IS NULL OR remark = '' THEN '{$origNote}'
                                WHEN remark NOT LIKE '%swapped to Order {$escTargetPso}%' THEN CONCAT(remark, ' | ', '{$origNote}')
                                ELSE remark 
                            END,
                            updated_at = NOW()
                        WHERE (psono = '{$escOrigPso}' OR stockorderno = '{$escOrigBatch}')
                          AND psono != '{$escTargetPso}'
                          AND IFNULL(tedit, '') = ''
                    ");
                }
            }

            // 3. Upsert Target Client into krf_readystock_allocation
            $totalSwappedPlants = array_sum(array_column($swappedBatchesMap, 'plants'));
            $escClientId = mysqli_real_escape_string($krfconn, $clientId);
            $targetAllocPlants = ($totalLoadedPlants > 0) ? $totalLoadedPlants : $totalSwappedPlants;

            $krfconn->query("
                INSERT INTO krf_readystock_allocation (
                    projectid,
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
                    created_at,
                    created_by,
                    updated_at,
                    updated_by,
                    status
                ) VALUES (
                    {$projectid},
                    '{$escTargetStock}',
                    '{$escSwappedBatches}',
                    '{$escClientId}',
                    '{$escProdId}',
                    '{$escProdName}',
                    {$targetAllocPlants},
                    {$targetAllocPlants},
                    0,
                    " . ($isShiftedOrder ? 1 : 0) . ",
                    0,
                    0,
                    NOW(),
                    '{$loginname}',
                    NOW(),
                    '{$loginname}',
                    'allocated'
                )
                ON DUPLICATE KEY UPDATE
                    allocate_stockorder_no = '{$escSwappedBatches}',
                    clientid = '{$escClientId}',
                    productname = '{$escProdName}',
                    allocate_ordqty = {$targetAllocPlants},
                    healthyplant = {$targetAllocPlants},
                    updated_at = NOW(),
                    updated_by = '{$loginname}',
                    status = 'allocated'
            ");

            // 4. Update / Upsert Origin Client(s) in krf_readystock_allocation so multiple entries exist for multiple clients
            foreach ($swappedBatchesMap as $b => $bData) {
                $escOrigBatch = mysqli_real_escape_string($krfconn, $b);
                $escOrigPso = mysqli_real_escape_string($krfconn, $bData['origin_pso']);
                $swPlants = (int)$bData['plants'];

                // Find origin client id and order metadata
                $qOrigClient = $krfconn->query("
                    SELECT 
                        COALESCE(NULLIF(TRIM(soi.clientid), ''), NULLIF(TRIM(pso.clientid), ''), NULLIF(TRIM(psto.clientid), ''), '') AS clientid,
                        COALESCE(NULLIF(TRIM(soi.stockorder_no), ''), NULLIF(TRIM(psto.stockorder_no), ''), '{$escOrigBatch}') AS orig_stockorder,
                        COALESCE(soi.product_id, psto.productid, '{$escProdId}') AS orig_prod_id,
                        COALESCE(soi.product_name, psto.product_name, '{$escProdName}') AS orig_prod_name
                    FROM krf_sales_order_items soi
                    LEFT JOIN krf_provisional_sales_order pso ON soi.psono = pso.psono
                    LEFT JOIN krf_production_stockorder psto ON (psto.stockorder_no = '{$escOrigBatch}' OR psto.psono = '{$escOrigPso}')
                    WHERE (soi.stockorder_no = '{$escOrigBatch}' OR soi.psono = '{$escOrigPso}')
                    LIMIT 1
                ");
                $origInfo = ($qOrigClient && $qOrigClient->num_rows > 0) ? $qOrigClient->fetch_assoc() : null;
                $origClientId = trim($origInfo['clientid'] ?? '');
                $origStockOrder = trim($origInfo['orig_stockorder'] ?? $b);
                $origProdId = trim(strval($origInfo['orig_prod_id'] ?? $escProdId));
                $origProdName = trim($origInfo['orig_prod_name'] ?? $escProdName);

                if (!empty($origClientId) && strcasecmp($origClientId, $clientId) !== 0) {
                    $escOrigClientId = mysqli_real_escape_string($krfconn, $origClientId);
                    $escOrigStock = mysqli_real_escape_string($krfconn, $origStockOrder);
                    $escOrigPId = mysqli_real_escape_string($krfconn, $origProdId);
                    $escOrigPName = mysqli_real_escape_string($krfconn, $origProdName);

                    // Check if origin client already has an allocation row
                    $chkOrigRsa = $krfconn->query("
                        SELECT id, allocate_ordqty, healthyplant 
                        FROM krf_readystock_allocation 
                        WHERE (stockorder_no = '{$escOrigStock}' OR stockorder_no = '{$escOrigBatch}')
                          AND clientid = '{$escOrigClientId}'
                          AND (productid = '{$escOrigPId}' OR productname = '{$escOrigPName}')
                        LIMIT 1
                    ");

                    if ($chkOrigRsa && $chkOrigRsa->num_rows > 0) {
                        // Update existing origin allocation row: reduce by swapped plants
                        $krfconn->query("
                            UPDATE krf_readystock_allocation 
                            SET 
                                healthyplant = GREATEST(0, healthyplant - {$swPlants}),
                                allocate_ordqty = GREATEST(0, allocate_ordqty - {$swPlants}),
                                updated_at = NOW(),
                                updated_by = '{$loginname}'
                            WHERE (stockorder_no = '{$escOrigStock}' OR stockorder_no = '{$escOrigBatch}')
                              AND clientid = '{$escOrigClientId}'
                              AND (productid = '{$escOrigPId}' OR productname = '{$escOrigPName}')
                        ");
                    } else {
                        // Origin client didn't have an RSA row yet; query original batch quantity and insert remaining
                        $qPsto = $krfconn->query("SELECT quantity FROM krf_production_stockorder WHERE stockorder_no = '{$escOrigBatch}' LIMIT 1");
                        $batchQty = ($qPsto && $rPsto = $qPsto->fetch_assoc()) ? (int)$rPsto['quantity'] : 0;
                        $remQty = max(0, $batchQty - $swPlants);

                        $krfconn->query("
                            INSERT INTO krf_readystock_allocation (
                                projectid,
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
                                created_at,
                                created_by,
                                updated_at,
                                updated_by,
                                status
                            ) VALUES (
                                {$projectid},
                                '{$escOrigStock}',
                                '{$escOrigBatch}',
                                '{$escOrigClientId}',
                                '{$escOrigPId}',
                                '{$escOrigPName}',
                                {$remQty},
                                {$remQty},
                                0,
                                1,
                                0,
                                0,
                                NOW(),
                                '{$loginname}',
                                NOW(),
                                '{$loginname}',
                                'allocated'
                            )
                            ON DUPLICATE KEY UPDATE
                                healthyplant = GREATEST(0, healthyplant - {$swPlants}),
                                allocate_ordqty = GREATEST(0, allocate_ordqty - {$swPlants}),
                                updated_at = NOW(),
                                updated_by = '{$loginname}'
                        ");
                    }
                }
            }
        }

        // Check sales order item and update loaded action status
        if ($itemId > 0) {
            $chkItem = $krfconn->query("
                SELECT 
                    soi.quantity, 
                    soi.allocate_ordqty, 
                    soi.psono, 
                    soi.stockorder_no,
                    poc.allocate_healthyplant
                FROM krf_sales_order_items soi
                LEFT JOIN krf_productionorder_consumed poc ON (
                    (NULLIF(TRIM(soi.psono), '') IS NOT NULL AND soi.psono = poc.psono AND (
                        (NULLIF(TRIM(soi.product_id), '') IS NOT NULL AND soi.product_id = poc.product_id)
                        OR (NULLIF(TRIM(soi.stockorder_no), '') IS NOT NULL AND (soi.stockorder_no = poc.stockorderno OR soi.stockorder_no = poc.allocated_stockorderno))
                    ))
                    OR (NULLIF(TRIM(soi.sono), '') IS NOT NULL AND soi.sono = poc.sono AND NULLIF(TRIM(soi.stockorder_no), '') IS NOT NULL AND soi.stockorder_no = poc.stockorderno)
                ) AND IFNULL(poc.tedit, '') = ''
                WHERE soi.id = {$itemId} LIMIT 1
            ");
            if ($chkItem && $iRow = $chkItem->fetch_assoc()) {
                $pocHealthy = (int)($iRow['allocate_healthyplant'] ?? 0);
                if ($pocHealthy > 0) {
                    $targetQty = $pocHealthy;
                } elseif ((int)($iRow['allocate_ordqty'] ?? 0) > 0) {
                    $targetQty = (int)$iRow['allocate_ordqty'];
                } else {
                    $targetQty = (int)$iRow['quantity'];
                }
                $sPso = mysqli_real_escape_string($krfconn, $iRow['psono']);
                $sStock = mysqli_real_escape_string($krfconn, $iRow['stockorder_no']);
                $sAllocStock = mysqli_real_escape_string($krfconn, $iRow['allocated_stockorderno'] ?? '');
                $sProdId = mysqli_real_escape_string($krfconn, $iRow['product_id'] ?? '');

                $chkLd = $krfconn->query("
                    SELECT COALESCE(SUM(t.tray_plants), 0) as total_loaded 
                    FROM (
                        SELECT 
                            CAST(COALESCE(NULLIF(barcode, ''), CONCAT('ID_', polyhouseplantid)) AS CHAR(100)) as unique_tray_id,
                            MAX(COALESCE(NULLIF(qtypertray, ''), 126)) as tray_plants
                        FROM krf_polyhouse_plant_details 
                        WHERE (dispd_agst_psono = '{$sPso}' OR psono = '{$sPso}')
                          AND (
                              dispd_agst_stockorderno = '{$sStock}' 
                              OR stockorder_no = '{$sStock}'
                              " . (!empty($sAllocStock) ? "OR dispd_agst_stockorderno = '{$sAllocStock}' OR stockorder_no = '{$sAllocStock}'" : "") . "
                              " . (!empty($sProdId) ? "OR productid = '{$sProdId}'" : "") . "
                          )
                          AND status IN ('Loaded', 'ReadyToDispatch')
                        GROUP BY CAST(COALESCE(NULLIF(barcode, ''), CONCAT('ID_', polyhouseplantid)) AS CHAR(100))
                    ) t
                ");
                $totalLoadedSoFar = 0;
                if ($chkLd && $ldR = $chkLd->fetch_assoc()) {
                    $totalLoadedSoFar = (int)$ldR['total_loaded'];
                }
                
                $isSingleVerification = !empty($_REQUEST['single_tray_verification']) || $loadedCount >= 1;
                $newActionStatus = ($totalLoadedSoFar >= $targetQty || $isSingleVerification) ? 'Loaded' : 'Partially Loaded';
                $krfconn->query("
                    UPDATE krf_sales_order_items 
                    SET 
                        actionstatus = '{$newActionStatus}',
                        updateamended = NOW(),
                        ammendedby = '{$loginname}'
                    WHERE (id = {$itemId} OR id IN (SELECT COALESCE(original_soi_id, 0) FROM sales_invoice_items WHERE id = {$itemId}))
                ");
            }
        }

        // If bonus trays were verified, calculate total bonus plants allocated
        $totalBonusPlantsVerified = 0;
        foreach ($bonusTraysMap as $bInfo) {
            $totalBonusPlantsVerified += (int)($bInfo['qty_taken'] ?? 0);
        }

        // Accept explicit bonus_plants passed from client if greater (e.g. auto-bonus from verified beds)
        $reqBonusPlants = isset($_REQUEST['bonus_plants']) ? (int)$_REQUEST['bonus_plants'] : 0;
        if ($reqBonusPlants > $totalBonusPlantsVerified) {
            $totalBonusPlantsVerified = $reqBonusPlants;
        }

        if ($totalBonusPlantsVerified > 0) {
            $krfconn->query("
                UPDATE krf_productionorder_consumed 
                SET 
                    extraqtyforshifting = {$totalBonusPlantsVerified},
                    consumed_qty = COALESCE(allocate_healthyplant, consumed_qty),
                    updated_at = NOW() 
                WHERE psono = '{$psono}'
            ");
            if ($itemId > 0) {
                $krfconn->query("
                    UPDATE krf_sales_order_items 
                    SET shiftingqty = {$totalBonusPlantsVerified} 
                    WHERE (id = {$itemId} OR id IN (SELECT COALESCE(original_soi_id, 0) FROM sales_invoice_items WHERE id = {$itemId}))
                ");
            } else {
                $krfconn->query("
                    UPDATE krf_sales_order_items 
                    SET shiftingqty = {$totalBonusPlantsVerified} 
                    WHERE psono = '{$psono}' AND product_id != 'BOX_PACKING' AND product_name NOT LIKE '%Box Packing%'
                ");
            }
        } elseif ($isShiftedOrder) {
            $cntRes = $krfconn->query("
                SELECT COUNT(DISTINCT COALESCE(NULLIF(barcode, ''), CONCAT('ID_', polyhouseplantid))) as total_loaded_trays 
                FROM krf_polyhouse_plant_details 
                WHERE (dispd_agst_psono = '{$psono}' OR psono = '{$psono}')
                  AND status IN ('Loaded', 'ReadyToDispatch')
            ");
            $totalLoadedTraysCount = $loadedCount;
            if ($cntRes && $cRow = $cntRes->fetch_assoc()) {
                $totalLoadedTraysCount = (int)$cRow['total_loaded_trays'];
            }

            // In single tray verification mode, ensure total order trays are used for shifting bonus
            $qOrdTrays = $krfconn->query("
                SELECT COALESCE(NULLIF(est_trays, 0), CEIL(quantity / 125)) as ord_trays 
                FROM krf_sales_order_items 
                WHERE (psono = '{$psono}' " . ($itemId > 0 ? "OR id = {$itemId}" : "") . ")
                  AND product_id != 'BOX_PACKING' 
                ORDER BY (id = {$itemId}) DESC 
                LIMIT 1
            ");
            if ($qOrdTrays && $otR = $qOrdTrays->fetch_assoc()) {
                $expectedTrays = (int)$otR['ord_trays'];
                if ($expectedTrays > $totalLoadedTraysCount) {
                    $totalLoadedTraysCount = $expectedTrays;
                }
            }

            $krfconn->query("
                UPDATE krf_productionorder_consumed 
                SET 
                    extraqtyforshifting = {$totalLoadedTraysCount},
                    consumed_qty = COALESCE(allocate_healthyplant, consumed_qty),
                    updated_at = NOW() 
                WHERE psono = '{$psono}'
            ");
            if ($itemId > 0) {
                $krfconn->query("
                    UPDATE krf_sales_order_items 
                    SET shiftingqty = {$totalLoadedTraysCount} 
                    WHERE (id = {$itemId} OR id IN (SELECT COALESCE(original_soi_id, 0) FROM sales_invoice_items WHERE id = {$itemId}))
                ");
            } else {
                $krfconn->query("
                    UPDATE krf_sales_order_items 
                    SET shiftingqty = {$totalLoadedTraysCount} 
                    WHERE psono = '{$psono}' AND product_id != 'BOX_PACKING' AND product_name NOT LIKE '%Box Packing%'
                ");
            }
        }

        $krfconn->commit();

        outJson([
            'status'         => 'success',
            'message'        => "Successfully confirmed loading for {$loadedCount} tray(s) ({$totalLoadedPlants} plants)!",
            'batch_id'       => $txNo,
            'loaded_count'   => $loadedCount,
            'loaded_plants'  => $totalLoadedPlants,
            'trays'          => $updatedBarcodes,
            'psono'          => $psono
        ]);
    } catch (Throwable $e) {
        $krfconn->rollback();
        outJson([
            'status'  => 'error',
            'message' => 'Database error during confirmation: ' . $e->getMessage()
        ]);
    }
}

// -------------------------------------------------------------
// 2.1 ACTION: CREATE VARIETY CHANGE APPROVAL REQUEST
// -------------------------------------------------------------
if ($action === 'create_variety_approval_request') {
    $barcode            = trim($_REQUEST['barcode'] ?? '');
    $polyhouseplantid   = (int)($_REQUEST['polyhouseplantid'] ?? 0);
    $targetPsono        = trim($_REQUEST['target_psono'] ?? '');
    $targetSono         = trim($_REQUEST['target_sono'] ?? '');
    $targetClientId     = trim($_REQUEST['target_clientid'] ?? '');
    $targetClientName   = trim($_REQUEST['target_clientname'] ?? '');
    $targetStockorder   = trim($_REQUEST['target_stockorderno'] ?? '');
    $targetProductName  = trim($_REQUEST['target_productname'] ?? '');
    $targetVariety      = trim($_REQUEST['target_variety'] ?? '');
    $targetDeliveryDate = trim($_REQUEST['target_deliverydate'] ?? '');
    $scannedPsono       = trim($_REQUEST['scanned_psono'] ?? '');
    $scannedSono        = trim($_REQUEST['scanned_sono'] ?? '');
    $scannedStockorder  = trim($_REQUEST['scanned_stockorderno'] ?? '');
    $scannedProductName = trim($_REQUEST['scanned_productname'] ?? '');
    $scannedVariety     = trim($_REQUEST['scanned_variety'] ?? '');
    $scannedDeliveryDate= trim($_REQUEST['scanned_deliverydate'] ?? '');
    $trayPolyhouseId    = (int)($_REQUEST['tray_polyhouse_id'] ?? 0);
    $trayPolyhouseName  = trim($_REQUEST['tray_polyhouse_name'] ?? '');
    $trayBedNo          = trim($_REQUEST['tray_bed_no'] ?? '');
    $qtyPerTray         = (int)($_REQUEST['qtypertray'] ?? 126);
    $reason             = trim($_REQUEST['reason'] ?? '');

    if (empty($barcode) || empty($targetPsono)) {
        outJson(['status' => 'error', 'message' => 'Barcode and Target Order are required.']);
    }

    if (empty($reason)) {
        outJson(['status' => 'error', 'message' => 'Please provide a reason for the approval request.']);
    }

    // Auto-resolve scanned origin PSO, SO and batch if missing
    if (empty($scannedPsono) || empty($scannedStockorder)) {
        $escBarcode = mysqli_real_escape_string($krfconn, $barcode);
        $qP = $krfconn->query("SELECT psono, stockorder_no, productname, variety, polyhouseid, bed_no FROM krf_polyhouse_plant_details WHERE barcode = '{$escBarcode}' LIMIT 1");
        if ($qP && $pRow = $qP->fetch_assoc()) {
            if (empty($scannedPsono)) $scannedPsono = trim($pRow['psono'] ?: '');
            if (empty($scannedStockorder)) $scannedStockorder = trim($pRow['stockorder_no'] ?: '');
            if (empty($scannedProductName)) $scannedProductName = trim($pRow['productname'] ?: '');
            if (empty($scannedVariety)) $scannedVariety = trim($pRow['variety'] ?: '');
            if ($trayPolyhouseId <= 0) $trayPolyhouseId = (int)$pRow['polyhouseid'];
            if (empty($trayBedNo)) $trayBedNo = trim($pRow['bed_no'] ?: '');
        }
    }

    if (empty($scannedSono) && !empty($scannedPsono)) {
        $escScannedPso = mysqli_real_escape_string($krfconn, $scannedPsono);
        $qS = $krfconn->query("SELECT sono FROM krf_sales_order_items WHERE psono = '{$escScannedPso}' LIMIT 1");
        if ($qS && $sRow = $qS->fetch_assoc()) {
            $scannedSono = trim($sRow['sono'] ?: '');
        }
    }

    // Auto-resolve missing dates
    if (empty($targetDeliveryDate)) {
        $targetDeliveryDate = getOrderDeliveryDate($krfconn, $targetPsono, $targetStockorder);
    }
    if (empty($scannedDeliveryDate)) {
        $scannedDeliveryDate = getOrderDeliveryDate($krfconn, $scannedPsono, $scannedStockorder);
    }
    $valTargetDeliveryDate = (!empty($targetDeliveryDate) && $targetDeliveryDate !== '0000-00-00') ? $targetDeliveryDate : null;
    $valScannedDeliveryDate = (!empty($scannedDeliveryDate) && $scannedDeliveryDate !== '0000-00-00') ? $scannedDeliveryDate : null;

    // Check if an existing pending request is already waiting for target order and this barcode OR batch
    $stockCond = !empty($scannedStockorder) ? "OR (scanned_stockorderno = ? AND scanned_stockorderno != '')" : "";
    $chkExisting = $krfconn->prepare("
        SELECT id, request_no, status, requested_at 
        FROM krf_dispatch_variety_approval_requests 
        WHERE target_psono = ? 
          AND (barcode = ? {$stockCond})
          AND status = 'pending' 
        LIMIT 1
    ");
    if (!empty($scannedStockorder)) {
        $chkExisting->bind_param('sss', $targetPsono, $barcode, $scannedStockorder);
    } else {
        $chkExisting->bind_param('ss', $targetPsono, $barcode);
    }
    $chkExisting->execute();
    $existRow = $chkExisting->get_result()->fetch_assoc();
    $chkExisting->close();

    if ($existRow) {
        outJson([
            'status' => 'success',
            'message' => 'A pending approval request already exists for this batch / tray.',
            'request_no' => $existRow['request_no'],
            'request_id' => (int)$existRow['id']
        ]);
    }

    // Generate Request Number: REQ-YYYYMMDD-XXXX
    $datePrefix = 'REQ-' . date('Ymd');
    $seqRes = $krfconn->query("
        SELECT COUNT(*) as cnt 
        FROM krf_dispatch_variety_approval_requests 
        WHERE request_no LIKE '{$datePrefix}%'
    ");
    $seq = 1;
    if ($seqRes && $sRow = $seqRes->fetch_assoc()) {
        $seq = ((int)$sRow['cnt']) + 1;
    }
    $requestNo = $datePrefix . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);

    $insStmt = $krfconn->prepare("
        INSERT INTO krf_dispatch_variety_approval_requests (
            request_no,
            barcode,
            polyhouseplantid,
            target_psono,
            target_sono,
            target_clientid,
            target_clientname,
            target_stockorderno,
            target_productname,
            target_variety,
            target_deliverydate,
            scanned_psono,
            scanned_sono,
            scanned_stockorderno,
            scanned_productname,
            scanned_variety,
            scanned_deliverydate,
            tray_polyhouse_id,
            tray_polyhouse_name,
            tray_bed_no,
            qtypertray,
            reason,
            status,
            requested_by,
            requested_at,
            created_at,
            updated_at
        ) VALUES (
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, 'pending', ?,
            NOW(), NOW(), NOW()
        )
    ");

    if (!$insStmt) {
        outJson(['status' => 'error', 'message' => 'Failed to prepare request statement: ' . $krfconn->error]);
    }

    $insStmt->bind_param(
        'ssisssssssssssssisisss',
        $requestNo,
        $barcode,
        $polyhouseplantid,
        $targetPsono,
        $targetSono,
        $targetClientId,
        $targetClientName,
        $targetStockorder,
        $targetProductName,
        $targetVariety,
        $valTargetDeliveryDate,
        $scannedPsono,
        $scannedSono,
        $scannedStockorder,
        $scannedProductName,
        $scannedVariety,
        $valScannedDeliveryDate,
        $trayPolyhouseId,
        $trayPolyhouseName,
        $trayBedNo,
        $qtyPerTray,
        $reason,
        $loginname
    );

    if ($insStmt->execute()) {
        $newId = $insStmt->insert_id;
        $insStmt->close();
        outJson([
            'status' => 'success',
            'message' => 'Variety change approval request submitted successfully.',
            'request_no' => $requestNo,
            'request_id' => $newId
        ]);
    } else {
        $err = $insStmt->error;
        $insStmt->close();
        outJson(['status' => 'error', 'message' => 'Failed to save approval request: ' . $err]);
    }
}

// -------------------------------------------------------------
// 2.2 ACTION: GET PENDING VARIETY APPROVALS & KPIS
// -------------------------------------------------------------
if ($action === 'get_pending_approvals') {
    $statusFilter = trim($_REQUEST['status'] ?? 'all');
    $search       = trim($_REQUEST['search'] ?? '');
    $fromDate     = trim($_REQUEST['from_date'] ?? '');
    $toDate       = trim($_REQUEST['to_date'] ?? '');

    $where = [];
    if (!empty($statusFilter) && $statusFilter !== 'all') {
        $safeStatus = mysqli_real_escape_string($krfconn, $statusFilter);
        $where[] = "r.status = '{$safeStatus}'";
    }

    if (!empty($search)) {
        $safeSearch = mysqli_real_escape_string($krfconn, $search);
        $where[] = "(
            r.request_no LIKE '%{$safeSearch}%'
            OR r.barcode LIKE '%{$safeSearch}%'
            OR r.target_psono LIKE '%{$safeSearch}%'
            OR r.target_sono LIKE '%{$safeSearch}%'
            OR r.target_clientname LIKE '%{$safeSearch}%'
            OR r.target_productname LIKE '%{$safeSearch}%'
            OR r.scanned_productname LIKE '%{$safeSearch}%'
            OR r.requested_by LIKE '%{$safeSearch}%'
        )";
    }

    if (!empty($fromDate)) {
        $safeFrom = mysqli_real_escape_string($krfconn, $fromDate);
        $where[] = "DATE(r.requested_at) >= '{$safeFrom}'";
    }
    if (!empty($toDate)) {
        $safeTo = mysqli_real_escape_string($krfconn, $toDate);
        $where[] = "DATE(r.requested_at) <= '{$safeTo}'";
    }

    $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

    $sql = "
        SELECT 
            r.*,
            DATE_FORMAT(r.target_deliverydate, '%d-%b-%Y') as formatted_target_deliverydate,
            DATE_FORMAT(r.scanned_deliverydate, '%d-%b-%Y') as formatted_scanned_deliverydate,
            DATE_FORMAT(r.requested_at, '%d-%b-%Y %h:%i %p') as formatted_requested_at,
            DATE_FORMAT(r.approved_at, '%d-%b-%Y %h:%i %p') as formatted_approved_at,
            DATE_FORMAT(r.loaded_at, '%d-%b-%Y %h:%i %p') as formatted_loaded_at
        FROM krf_dispatch_variety_approval_requests r
        {$whereSql}
        ORDER BY r.id DESC
    ";

    $res = $krfconn->query($sql);
    $requests = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            // Resolve missing scanned origin PSO / SO / batch for legacy rows
            if (empty($row['scanned_psono'])) {
                $bCode = mysqli_real_escape_string($krfconn, $row['barcode']);
                $qPl = $krfconn->query("SELECT psono, stockorder_no FROM krf_polyhouse_plant_details WHERE barcode = '{$bCode}' LIMIT 1");
                if ($qPl && $plR = $qPl->fetch_assoc()) {
                    $row['scanned_psono'] = $plR['psono'];
                    if (empty($row['scanned_stockorderno'])) {
                        $row['scanned_stockorderno'] = $plR['stockorder_no'];
                    }
                }
            }
            if (empty($row['scanned_sono']) && !empty($row['scanned_psono'])) {
                $sPso = mysqli_real_escape_string($krfconn, $row['scanned_psono']);
                $qSo = $krfconn->query("SELECT sono FROM krf_sales_order_items WHERE psono = '{$sPso}' LIMIT 1");
                if ($qSo && $soR = $qSo->fetch_assoc()) {
                    $row['scanned_sono'] = $soR['sono'];
                }
            }

            // Fallback delivery date resolution for legacy rows
            if (empty($row['target_deliverydate']) || $row['target_deliverydate'] === '0000-00-00') {
                $row['target_deliverydate'] = getOrderDeliveryDate($krfconn, $row['target_psono'], $row['target_stockorderno']);
            }
            if (empty($row['scanned_deliverydate']) || $row['scanned_deliverydate'] === '0000-00-00') {
                $row['scanned_deliverydate'] = getOrderDeliveryDate($krfconn, $row['scanned_psono'], $row['scanned_stockorderno']);
            }

            if (!empty($row['target_deliverydate']) && $row['target_deliverydate'] !== '0000-00-00') {
                $row['formatted_target_deliverydate'] = date('d-M-Y', strtotime($row['target_deliverydate']));
            } else {
                $row['formatted_target_deliverydate'] = '-';
            }
            if (!empty($row['scanned_deliverydate']) && $row['scanned_deliverydate'] !== '0000-00-00') {
                $row['formatted_scanned_deliverydate'] = date('d-M-Y', strtotime($row['scanned_deliverydate']));
            } else {
                $row['formatted_scanned_deliverydate'] = '-';
            }

            $requests[] = $row;
        }
    }

    // KPI Counts
    $kpiRes = $krfconn->query("
        SELECT 
            COUNT(*) as total_count,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
            COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_count,
            COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_count
        FROM krf_dispatch_variety_approval_requests
    ");
    $kpis = [
        'total' => 0,
        'pending' => 0,
        'approved' => 0,
        'rejected' => 0
    ];
    if ($kpiRes && $kRow = $kpiRes->fetch_assoc()) {
        $kpis['total'] = (int)$kRow['total_count'];
        $kpis['pending'] = (int)$kRow['pending_count'];
        $kpis['approved'] = (int)$kRow['approved_count'];
        $kpis['rejected'] = (int)$kRow['rejected_count'];
    }

    outJson([
        'status' => 'success',
        'data' => $requests,
        'kpis' => $kpis
    ]);
}

// -------------------------------------------------------------
// 2.3 ACTION: APPROVE OR REJECT VARIETY CHANGE REQUEST
// -------------------------------------------------------------
if ($action === 'action_variety_approval') {
    $requestId = (int)($_REQUEST['request_id'] ?? 0);
    $decision  = strtolower(trim($_REQUEST['decision'] ?? ''));
    $remarks   = trim($_REQUEST['remarks'] ?? '');

    if ($requestId <= 0 || !in_array($decision, ['approved', 'rejected'])) {
        outJson(['status' => 'error', 'message' => 'Invalid request ID or decision. Must be approved or rejected.']);
    }

    $chk = $krfconn->query("SELECT id, status, request_no, barcode, target_psono FROM krf_dispatch_variety_approval_requests WHERE id = {$requestId} LIMIT 1");
    if (!$chk || $chk->num_rows === 0) {
        outJson(['status' => 'error', 'message' => 'Approval request not found.']);
    }
    $req = $chk->fetch_assoc();

    $upd = $krfconn->prepare("
        UPDATE krf_dispatch_variety_approval_requests
        SET 
            status = ?,
            approved_by = ?,
            approved_at = NOW(),
            approval_remarks = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    $upd->bind_param('sssi', $decision, $loginname, $remarks, $requestId);
    if ($upd->execute()) {
        $upd->close();
        outJson([
            'status' => 'success',
            'message' => 'Request ' . $req['request_no'] . ' has been ' . strtoupper($decision) . ' successfully.',
            'decision' => $decision,
            'request_no' => $req['request_no']
        ]);
    } else {
        $err = $upd->error;
        $upd->close();
        outJson(['status' => 'error', 'message' => 'Failed to update approval decision: ' . $err]);
    }
}

// -------------------------------------------------------------
// 2.4 ACTION: GET LIVE PENDING APPROVALS COUNT (FOR BADGE)
// -------------------------------------------------------------
if ($action === 'get_pending_approval_count') {
    $cRes = $krfconn->query("SELECT COUNT(*) as cnt FROM krf_dispatch_variety_approval_requests WHERE status = 'pending'");
    $cnt = 0;
    if ($cRes && $row = $cRes->fetch_assoc()) {
        $cnt = (int)$row['cnt'];
    }
    outJson(['status' => 'success', 'pending_count' => $cnt]);
}

// -------------------------------------------------------------
// 3.0 ACTION: AUTO-LOAD RSO TRAYS (EXPLICIT USER TRIGGER)
// Explicitly marks active trays from RSO/SO batch as Loaded
// -------------------------------------------------------------
if ($action === 'auto_load_rso_trays') {
    $psono = trim($_REQUEST['psono'] ?? '');
    $stockOrderNo = trim($_REQUEST['stockorder_no'] ?? '');
    $clientId = trim($_REQUEST['clientid'] ?? '');
    $qty = (int)($_REQUEST['quantity'] ?? 0);
    $invoiceno = trim($_REQUEST['invoiceno'] ?? '');
    $loginname = $_SESSION['loginname'] ?? $_SESSION['username'] ?? 'admin';

    // Authorization: Role ID must be < 2
    if ($loginroleid >= 2) {
        outJson(['status' => 'error', 'message' => 'Permission Denied: Only Administrator (loginroleid < 2) can auto-load trays.']);
    }

    if (empty($psono)) {
        outJson(['status' => 'error', 'message' => 'PSO number is required.']);
    }

    if (empty($stockOrderNo)) {
        $escP = mysqli_real_escape_string($krfconn, $psono);
        $q = $krfconn->query("SELECT stockorder_no, quantity, clientid FROM krf_sales_order_items WHERE psono = '{$escP}' LIMIT 1");
        if ($q && $r = $q->fetch_assoc()) {
            $stockOrderNo = $r['stockorder_no'];
            if ($qty <= 0) $qty = (int)$r['quantity'];
            if (empty($clientId)) $clientId = $r['clientid'];
        }
    }

    $res = autoMarkRsoTraysAsLoaded($krfconn, $psono, $stockOrderNo, $qty, $clientId, $loginname, $invoiceno);
    if ($res['status'] === 'success') {
        outJson([
            'status' => 'success',
            'message' => 'Successfully loaded ' . ($res['loaded_trays'] ?? 0) . ' tray(s) for ' . $psono,
            'loaded_trays' => $res['loaded_trays'] ?? 0
        ]);
    } else {
        outJson([
            'status' => 'error',
            'message' => $res['message'] ?? 'Could not auto-load trays for this order.'
        ]);
    }
}

// -------------------------------------------------------------
// 3.1 ACTION: UNDO LOADED TRAY STATUS
// Reverts a previously loaded tray back to its pre-loaded status:
//   - krf_polyhouse_plant_details (Loaded -> Readytosale/Allocated, clear dispatch columns)
//   - krf_readytosale_stocks (Loaded -> Readytosale, reset dispatch/loaded columns)
//   - krf_traymovement_history (audit entry for undo)
// -------------------------------------------------------------
if ($action === 'undo_tray_load') {
    $barcode = trim($_REQUEST['barcode'] ?? '');
    $targetPsono = trim($_REQUEST['psono'] ?? '');

    // Authorization: Role ID must be < 2
    if ($loginroleid >= 2) {
        outJson(['status' => 'error', 'message' => 'Permission Denied: Only Administrator (loginroleid < 2) can undo loaded trays.']);
    }

    if (empty($barcode)) {
        outJson(['status' => 'error', 'message' => 'Tray barcode is required for undo action.']);
    }

    try {
        $krfconn->begin_transaction();

        // 1. Fetch current status & details from krf_polyhouse_plant_details
        $chkQ = $krfconn->prepare("
            SELECT 
                polyhouseplantid,
                projectid,
                productid,
                productname,
                tray_id,
                polyhouseid,
                bed_no,
                bedqrcode,
                status,
                qtypertray,
                failurecount,
                stockorder_no,
                psono,
                dispd_agst_psono,
                dispd_agst_stockorderno,
                dispd_agst_clientid,
                dispatchqty
            FROM krf_polyhouse_plant_details 
            WHERE barcode = ? LIMIT 1
        ");
        $currPh = null;
        if ($chkQ) {
            $chkQ->bind_param('s', $barcode);
            $chkQ->execute();
            $currPh = $chkQ->get_result()->fetch_assoc();
            $chkQ->close();
        }

        // Also fetch from krf_readytosale_stocks
        $chkRts = $krfconn->prepare("
            SELECT 
                id,
                polyhouseplantid,
                projectid,
                productid,
                productname,
                trayid,
                locationid as polyhouseid,
                bedqrcode as bed_no,
                status,
                qtypertray,
                stockorder_no,
                psono,
                dispd_agst_psono,
                dispd_agst_stockorderno,
                dispd_agst_clientid,
                dispatchqty
            FROM krf_readytosale_stocks 
            WHERE barcode = ? LIMIT 1
        ");
        $currRts = null;
        if ($chkRts) {
            $chkRts->bind_param('s', $barcode);
            $chkRts->execute();
            $currRts = $chkRts->get_result()->fetch_assoc();
            $chkRts->close();
        }

        if (!$currPh && !$currRts) {
            $krfconn->rollback();
            outJson(['status' => 'error', 'message' => "Tray '{$barcode}' not found in polyhouse or ready to sale records."]);
        }

        $curr = $currPh ?: $currRts;
        $isPhLoaded = $currPh && in_array(strtolower(trim($currPh['status'])), ['loaded', 'readytodispatch', 'ready to dispatch']);
        $isRtsLoaded = $currRts && in_array(strtolower(trim($currRts['status'])), ['loaded', 'readytodispatch', 'ready to dispatch']);

        if (!$isPhLoaded && !$isRtsLoaded) {
            $krfconn->rollback();
            outJson([
                'status'  => 'error', 
                'message' => "Cannot undo load: Tray '{$barcode}' is currently not in 'Loaded' status."
            ]);
        }

        // 2. Find previous status from history before loading
        $revertedStatus = 'Readytosale';
        $histCheck = $krfconn->prepare("
            SELECT previous_status 
            FROM krf_traymovement_history 
            WHERE barcode = ? AND activity_type IN ('INTERNAL_DISPATCH', 'AUTO_LOAD_RSO', 'SCAN_LOAD')
            ORDER BY movement_id DESC LIMIT 1
        ");
        if ($histCheck) {
            $histCheck->bind_param('s', $barcode);
            $histCheck->execute();
            $hRow = $histCheck->get_result()->fetch_assoc();
            if ($hRow && !empty($hRow['previous_status'])) {
                $prev = trim($hRow['previous_status']);
                if (in_array($prev, ['Readytosale', 'Allocated', 'Polyhouse', 'ReadyToAllocated'])) {
                    $revertedStatus = $prev;
                }
            }
            $histCheck->close();
        }

        $loginUser = $_SESSION['loginname'] ?? $_SESSION['username'] ?? 'User';
        $psoForRemark = !empty($targetPsono) ? $targetPsono : ($curr['dispd_agst_psono'] ?: $curr['psono']);
        $undoRemark = "Load undone for Order {$psoForRemark} by {$loginUser}";

        // 3. Update krf_polyhouse_plant_details
        $updPlant = $krfconn->prepare("
            UPDATE krf_polyhouse_plant_details
            SET 
                status = ?,
                workingstatus = ?,
                movementremark = ?,
                dispatchqty = NULL,
                dispatchat = NULL,
                dispatchby = NULL,
                dispd_agst_clientid = NULL,
                dispd_agst_stockorderno = NULL,
                dispd_agst_psono = NULL,
                updated_at = NOW(),
                updated_by = ?
            WHERE barcode = ?
        ");
        if ($updPlant) {
            $updPlant->bind_param('sssss', $revertedStatus, $revertedStatus, $undoRemark, $loginUser, $barcode);
            $updPlant->execute();
            $updPlant->close();
        }

        // 4. Update krf_readytosale_stocks
        $updRts = $krfconn->prepare("
            UPDATE krf_readytosale_stocks
            SET 
                status = 'Readytosale',
                dispatchqty = '0',
                dispatchat = NULL,
                dispatchby = NULL,
                dispd_agst_clientid = NULL,
                dispd_agst_stockorderno = NULL,
                dispd_agst_psono = NULL,
                loaded_qty = 0,
                loaded_at = NULL,
                loaded_by = NULL,
                available_qty = qtypertray,
                updated_at = NOW()
            WHERE barcode = ?
        ");
        if ($updRts) {
            $updRts->bind_param('s', $barcode);
            $updRts->execute();
            $updRts->close();
        }

        // 5. Log undo event in krf_traymovement_history
        $histStmt = $krfconn->prepare("
            INSERT INTO krf_traymovement_history (
                transaction_no,
                projectid,
                psono,
                stockorder_no,
                productname,
                activity_type,
                barcode,
                from_polyhouse_id,
                from_bed_no,
                previous_status,
                current_status,
                live_plant_count,
                reason_for_movement,
                movement_date,
                movement_time,
                created_by,
                created_at
            ) VALUES (?, ?, ?, ?, ?, 'INTERNAL_DISPATCH', ?, ?, ?, 'Loaded', ?, ?, ?, CURDATE(), CURTIME(), ?, NOW())
        ");

        if ($histStmt) {
            $undoTxNo = 'UNDO-' . date('YmdHis') . '-' . rand(100, 999);
            $projId = (int)($curr['projectid'] ?? 0);
            $origPsono = $curr['psono'] ?: '';
            $origStockNo = $curr['stockorder_no'] ?: '';
            $prodName = $curr['productname'] ?: '';
            $phId = (int)($curr['polyhouseid'] ?? 0);
            $bedNo = $curr['bed_no'] ?: '';
            $qtyPlants = (int)($curr['qtypertray'] ?: 126);

            $histStmt->bind_param(
                'sissssisissi',
                $undoTxNo,
                $projId,
                $origPsono,
                $origStockNo,
                $prodName,
                $barcode,
                $phId,
                $bedNo,
                $revertedStatus,
                $qtyPlants,
                $undoRemark,
                $loginUser
            );
            $histStmt->execute();
            $histStmt->close();
        }

        // 6. Recalculate remaining loaded trays & update sales order item action status
        $escPso = mysqli_real_escape_string($krfconn, $psoForRemark);
        $chkRem = $krfconn->query("
            SELECT 
                COUNT(DISTINCT t.unique_tray_id) as rem_trays,
                COALESCE(SUM(t.tray_plants), 0) as rem_plants
            FROM (
                SELECT 
                    CAST(COALESCE(NULLIF(barcode, ''), CONCAT('PH_', polyhouseplantid)) AS CHAR(100)) as unique_tray_id,
                    MAX(COALESCE(NULLIF(qtypertray, ''), 126)) as tray_plants
                FROM krf_polyhouse_plant_details
                WHERE (dispd_agst_psono = '{$escPso}' OR psono = '{$escPso}')
                  AND status IN ('Loaded', 'ReadyToDispatch')
                GROUP BY CAST(COALESCE(NULLIF(barcode, ''), CONCAT('PH_', polyhouseplantid)) AS CHAR(100))
                UNION
                SELECT 
                    CAST(COALESCE(NULLIF(barcode, ''), CONCAT('RTS_', id)) AS CHAR(100)) as unique_tray_id,
                    MAX(COALESCE(NULLIF(qtypertray, ''), 126)) as tray_plants
                FROM krf_readytosale_stocks
                WHERE (dispd_agst_psono = '{$escPso}' OR psono = '{$escPso}')
                  AND status IN ('Loaded', 'ReadyToDispatch')
                GROUP BY CAST(COALESCE(NULLIF(barcode, ''), CONCAT('RTS_', id)) AS CHAR(100))
            ) t
        ");
        $remTrays = 0;
        $remPlants = 0;
        if ($chkRem && $remR = $chkRem->fetch_assoc()) {
            $remTrays = (int)$remR['rem_trays'];
            $remPlants = (int)$remR['rem_plants'];
        }

        $stockForQuery = mysqli_real_escape_string($krfconn, $curr['dispd_agst_stockorderno'] ?: $curr['stockorder_no']);
        $chkItem = $krfconn->query("
            SELECT id, quantity, allocate_ordqty 
            FROM krf_sales_order_items 
            WHERE psono = '{$escPso}' 
              AND (stockorder_no = '{$stockForQuery}' OR '{$stockForQuery}' = '' OR 1=1)
            ORDER BY id ASC LIMIT 1
        ");
        $itId = 0;
        $targQty = 0;
        if ($chkItem && $itRow = $chkItem->fetch_assoc()) {
            $itId = (int)$itRow['id'];
            $targQty = (int)($itRow['allocate_ordqty'] > 0 ? $itRow['allocate_ordqty'] : $itRow['quantity']);
        }

        $newActionStatus = ($remTrays > 0) ? (($remPlants >= $targQty && $targQty > 0) ? 'Loaded' : 'Partially Loaded') : 'Unloaded';

        if ($itId > 0) {
            $krfconn->query("
                UPDATE krf_sales_order_items 
                SET 
                    actionstatus = '{$newActionStatus}',
                    updateamended = NOW(),
                    ammendedby = '{$loginUser}',
                    shiftingqty = IF(isshifted = 'yes' OR isshifted = '1' OR isshifted = 1, {$remTrays}, shiftingqty)
                WHERE id = {$itId}
            ");
        } else {
            $krfconn->query("
                UPDATE krf_sales_order_items 
                SET 
                    actionstatus = '{$newActionStatus}',
                    updateamended = NOW(),
                    ammendedby = '{$loginUser}',
                    shiftingqty = IF(isshifted = 'yes' OR isshifted = '1' OR isshifted = 1, {$remTrays}, shiftingqty)
                WHERE psono = '{$escPso}' AND product_id != 'BOX_PACKING' AND product_name NOT LIKE '%Box Packing%'
            ");
        }

        // Update extraqtyforshifting for shifted orders to remaining loaded trays count
        $krfconn->query("
            UPDATE krf_productionorder_consumed
            SET 
                extraqtyforshifting = {$remTrays},
                consumed_qty = COALESCE(allocate_healthyplant, consumed_qty),
                updated_at = NOW()
            WHERE (psono = '{$escPso}' OR sono = '{$escPso}') AND isshifted = 1 AND IFNULL(tedit, '') = ''
        ");

        $krfconn->commit();

        outJson([
            'status'          => 'success',
            'message'         => "Tray {$barcode} has been successfully reverted from Loaded to {$revertedStatus}.",
            'barcode'         => $barcode,
            'reverted_status' => $revertedStatus,
            'qtypertray'      => (int)($curr['qtypertray'] ?: 126),
            'remaining_trays' => $remTrays,
            'remaining_plants'=> $remPlants
        ]);

    } catch (Throwable $e) {
        $krfconn->rollback();
        outJson([
            'status'  => 'error',
            'message' => 'Database error during undo: ' . $e->getMessage()
        ]);
    }
}

// -------------------------------------------------------------
// 3.1.2 ACTION: UNDO ALL LOADED TRAYS FOR AN ORDER ITEM
// Reverts all loaded trays for this item back to Readytosale in one transaction
// -------------------------------------------------------------
if ($action === 'undo_all_item_trays') {
    $psono = trim($_REQUEST['psono'] ?? '');
    $stockOrderNo = trim($_REQUEST['stockorder_no'] ?? '');
    $clientId = trim($_REQUEST['clientid'] ?? '');
    $itemId = (int)($_REQUEST['item_id'] ?? 0);
    $productId = trim($_REQUEST['product_id'] ?? '');
    $productName = trim($_REQUEST['product_name'] ?? '');
    $barcodesParam = $_REQUEST['barcodes'] ?? null;

    $givenBarcodes = [];
    if (!empty($barcodesParam)) {
        $rawBc = is_array($barcodesParam) ? $barcodesParam : json_decode($barcodesParam, true);
        if (is_array($rawBc)) {
            $givenBarcodes = array_values(array_unique(array_filter(array_map('trim', $rawBc))));
        }
    }

    // Authorization: Role ID must be < 2
    if ($loginroleid >= 2) {
        outJson(['status' => 'error', 'message' => 'Permission Denied: Only Administrator (loginroleid < 2) can undo all loaded trays.']);
    }

    if (empty($psono) && empty($givenBarcodes)) {
        outJson(['status' => 'error', 'message' => 'PSO number is required to undo all trays.']);
    }

    if ($itemId > 0 && (empty($productId) || empty($productName))) {
        $qItem = $krfconn->query("SELECT product_id, product_name, stockorder_no FROM krf_sales_order_items WHERE id = {$itemId} LIMIT 1");
        if ($qItem && $rItem = $qItem->fetch_assoc()) {
            if (empty($productId) && !empty($rItem['product_id'])) $productId = trim(strval($rItem['product_id']));
            if (empty($productName) && !empty($rItem['product_name'])) $productName = trim($rItem['product_name']);
            if (empty($stockOrderNo) && !empty($rItem['stockorder_no'])) $stockOrderNo = trim($rItem['stockorder_no']);
        }
    }

    try {
        $krfconn->begin_transaction();
        $loginUser = $_SESSION['loginname'] ?? $_SESSION['username'] ?? 'User';

        $escPso = mysqli_real_escape_string($krfconn, $psono);
        $escStock = mysqli_real_escape_string($krfconn, $stockOrderNo);

        if (!empty($givenBarcodes)) {
            $bcListEsc = "'" . implode("','", array_map(function($b) use ($krfconn) {
                return mysqli_real_escape_string($krfconn, $b);
            }, $givenBarcodes)) . "'";
            $whereClausePh = "p.barcode IN ({$bcListEsc})";
            $whereClauseRts = "r.barcode IN ({$bcListEsc})";
        } else {
            $stkParts = array_filter(array_map('trim', explode(',', $stockOrderNo)));
            $stockCondSqlPh = "";
            $stockCondSqlRts = "";
            if (!empty($stkParts)) {
                $escList = array_map(function($s) use ($krfconn) { return "'" . mysqli_real_escape_string($krfconn, $s) . "'"; }, $stkParts);
                $inStk = implode(',', $escList);
                $stockCondSqlPh = " AND (p.dispd_agst_stockorderno IN ({$inStk}) OR p.stockorder_no IN ({$inStk}))";
                $stockCondSqlRts = " AND (r.dispd_agst_stockorderno IN ({$inStk}) OR r.stockorder_no IN ({$inStk}))";
            }

            $prodCondSqlPh = "";
            $prodCondSqlRts = "";
            $escProd = mysqli_real_escape_string($krfconn, $productName);
            $escPIdU = mysqli_real_escape_string($krfconn, $productId);
            if (!empty($escPIdU) && !empty($escProd)) {
                $prodCondSqlPh = " AND (p.productid = '{$escPIdU}' OR p.productname = '{$escProd}' OR p.productname LIKE '%{$escProd}%')";
                $prodCondSqlRts = " AND (r.productid = '{$escPIdU}' OR r.productname = '{$escProd}' OR r.productname LIKE '%{$escProd}%')";
            } elseif (!empty($escPIdU)) {
                $prodCondSqlPh = " AND p.productid = '{$escPIdU}'";
                $prodCondSqlRts = " AND r.productid = '{$escPIdU}'";
            } elseif (!empty($escProd)) {
                $prodCondSqlPh = " AND (p.productname = '{$escProd}' OR p.productname LIKE '%{$escProd}%')";
                $prodCondSqlRts = " AND (r.productname = '{$escProd}' OR r.productname LIKE '%{$escProd}%')";
            }

            $whereClausePh = "(p.dispd_agst_psono = '{$escPso}' OR (p.psono = '{$escPso}' AND (p.dispd_agst_psono IS NULL OR p.dispd_agst_psono = ''){$stockCondSqlPh})) {$prodCondSqlPh}";
            $whereClauseRts = "(r.dispd_agst_psono = '{$escPso}' OR (r.psono = '{$escPso}' AND (r.dispd_agst_psono IS NULL OR r.dispd_agst_psono = ''){$stockCondSqlRts})) {$prodCondSqlRts}";
        }

        $allTrays = [];

        // 1. Collect loaded trays from krf_polyhouse_plant_details
        $trayQueryPh = $krfconn->query("
            SELECT 
                MAX(p.polyhouseplantid) as polyhouseplantid, 
                p.barcode, 
                MAX(p.psono) as psono, 
                MAX(p.stockorder_no) as stockorder_no, 
                MAX(p.dispd_agst_psono) as dispd_agst_psono, 
                MAX(p.productname) as productname, 
                MAX(p.polyhouseid) as polyhouseid, 
                MAX(p.bed_no) as bed_no, 
                MAX(COALESCE(p.qtypertray, 126)) as qtypertray, 
                MAX(p.projectid) as projectid
            FROM krf_polyhouse_plant_details p
            WHERE {$whereClausePh}
              AND LOWER(TRIM(p.status)) IN ('loaded', 'readytodispatch', 'ready to dispatch')
              AND p.barcode IS NOT NULL AND p.barcode != ''
            GROUP BY p.barcode
        ");
        if ($trayQueryPh) {
            while ($curr = $trayQueryPh->fetch_assoc()) {
                $allTrays[trim($curr['barcode'])] = $curr;
            }
        }

        // 2. Collect loaded trays from krf_readytosale_stocks
        $trayQueryRts = $krfconn->query("
            SELECT 
                MAX(r.polyhouseplantid) as polyhouseplantid, 
                r.barcode, 
                MAX(r.psono) as psono, 
                MAX(r.stockorder_no) as stockorder_no, 
                MAX(r.dispd_agst_psono) as dispd_agst_psono, 
                MAX(r.productname) as productname, 
                MAX(r.locationid) as polyhouseid, 
                MAX(r.bedqrcode) as bed_no, 
                MAX(COALESCE(r.qtypertray, 126)) as qtypertray, 
                MAX(r.projectid) as projectid
            FROM krf_readytosale_stocks r
            WHERE {$whereClauseRts}
              AND LOWER(TRIM(r.status)) IN ('loaded', 'readytodispatch', 'ready to dispatch')
              AND r.barcode IS NOT NULL AND r.barcode != ''
            GROUP BY r.barcode
        ");
        if ($trayQueryRts) {
            while ($curr = $trayQueryRts->fetch_assoc()) {
                $bCode = trim($curr['barcode']);
                if (!isset($allTrays[$bCode])) {
                    $allTrays[$bCode] = $curr;
                }
            }
        }

        $undoneBarcodes = [];
        $totalUndonePlants = 0;

        if (!empty($allTrays)) {
            $undoRemark = "All loaded trays undone for Order {$psono} by {$loginUser}";
            $undoTxNo = 'UNDO-ALL-' . date('YmdHis') . '-' . rand(100, 999);

            foreach ($allTrays as $bCode => $curr) {
                $undoneBarcodes[] = $bCode;
                $trayPlants = (int)($curr['qtypertray'] ?: 126);
                $totalUndonePlants += $trayPlants;

                // Revert krf_polyhouse_plant_details
                $krfconn->query("
                    UPDATE krf_polyhouse_plant_details
                    SET 
                        status = 'Readytosale',
                        workingstatus = 'Readytosale',
                        movementremark = '{$undoRemark}',
                        dispatchqty = NULL,
                        dispatchat = NULL,
                        dispatchby = NULL,
                        dispd_agst_clientid = NULL,
                        dispd_agst_stockorderno = NULL,
                        dispd_agst_psono = NULL,
                        updated_at = NOW(),
                        updated_by = '{$loginUser}'
                    WHERE barcode = '{$bCode}'
                ");

                // Revert krf_readytosale_stocks
                $krfconn->query("
                    UPDATE krf_readytosale_stocks
                    SET 
                        status = 'Readytosale',
                        dispatchqty = '0',
                        dispatchat = NULL,
                        dispatchby = NULL,
                        dispd_agst_clientid = NULL,
                        dispd_agst_stockorderno = NULL,
                        dispd_agst_psono = NULL,
                        loaded_qty = 0,
                        loaded_at = NULL,
                        loaded_by = NULL,
                        available_qty = qtypertray,
                        updated_at = NOW()
                    WHERE barcode = '{$bCode}'
                ");

                // History log
                $pId = (int)($curr['projectid'] ?? 0);
                $phId = (int)($curr['polyhouseid'] ?? 0);
                $bNo = $curr['bed_no'] ?: '';
                $pNm = mysqli_real_escape_string($krfconn, $curr['productname'] ?: '');
                $krfconn->query("
                    INSERT INTO krf_traymovement_history (
                        transaction_no, projectid, psono, stockorder_no, productname,
                        activity_type, barcode, from_polyhouse_id, from_bed_no,
                        previous_status, current_status, live_plant_count, reason_for_movement,
                        movement_date, movement_time, created_by, created_at
                    ) VALUES (
                        '{$undoTxNo}', {$pId}, '{$psono}', '{$stockOrderNo}', '{$pNm}',
                        'INTERNAL_DISPATCH', '{$bCode}', {$phId}, '{$bNo}',
                        'Loaded', 'Readytosale', {$trayPlants}, '{$undoRemark}',
                        CURDATE(), CURTIME(), '{$loginUser}', NOW()
                    )
                ");
            }
        }

        // Reset actionstatus & shiftingqty in krf_sales_order_items (mark as Unloaded so auto-load does not revert manual undo)
        if ($itemId > 0) {
            $krfconn->query("
                UPDATE krf_sales_order_items
                SET 
                    actionstatus = 'Unloaded',
                    shiftingqty = 0,
                    updateamended = NOW(),
                    ammendedby = '{$loginUser}'
                WHERE (id = {$itemId} OR id IN (SELECT COALESCE(original_soi_id, 0) FROM sales_invoice_items WHERE id = {$itemId}))
            ");
        } else {
            $krfconn->query("
                UPDATE krf_sales_order_items
                SET 
                    actionstatus = 'Unloaded',
                    shiftingqty = 0,
                    updateamended = NOW(),
                    ammendedby = '{$loginUser}'
                WHERE psono = '{$escPso}'
            ");
        }

        // Reset extraqtyforshifting & consumed_qty in krf_productionorder_consumed
        $krfconn->query("
            UPDATE krf_productionorder_consumed
            SET 
                extraqtyforshifting = 0,
                consumed_qty = COALESCE(allocate_healthyplant, consumed_qty),
                updated_at = NOW()
            WHERE (psono = '{$escPso}' OR sono = '{$escPso}') AND IFNULL(tedit, '') = ''
        ");

        $krfconn->commit();

        outJson([
            'status'          => 'success',
            'message'         => 'Successfully undone all ' . count($undoneBarcodes) . ' loaded tray(s) in polyhouse & ready to sale records!',
            'undone_count'    => count($undoneBarcodes),
            'undone_plants'   => $totalUndonePlants,
            'undone_barcodes' => $undoneBarcodes
        ]);

    } catch (Throwable $e) {
        $krfconn->rollback();
        outJson([
            'status'  => 'error',
            'message' => 'Error undoing all trays: ' . $e->getMessage()
        ]);
    }
}

// -------------------------------------------------------------
// 3.2 ACTION: GET ALREADY LOADED TRAYS FOR AN ORDER ITEM
// -------------------------------------------------------------
if ($action === 'get_item_loaded_trays') {
    $psono = trim($_REQUEST['psono'] ?? '');
    $productName = trim($_REQUEST['product_name'] ?? '');
    $productId = trim($_REQUEST['product_id'] ?? '');
    $clientId = trim($_REQUEST['clientid'] ?? '');
    $stockOrderNo = trim($_REQUEST['stockorder_no'] ?? '');
    $itemId = (int)($_REQUEST['item_id'] ?? 0);

    if ($itemId > 0 && (empty($productId) || empty($productName))) {
        $qItem = $krfconn->query("SELECT product_id, product_name, stockorder_no FROM krf_sales_order_items WHERE id = {$itemId} LIMIT 1");
        if ($qItem && $rItem = $qItem->fetch_assoc()) {
            if (empty($productId) && !empty($rItem['product_id'])) $productId = trim(strval($rItem['product_id']));
            if (empty($productName) && !empty($rItem['product_name'])) $productName = trim($rItem['product_name']);
            if (empty($stockOrderNo) && !empty($rItem['stockorder_no'])) $stockOrderNo = trim($rItem['stockorder_no']);
        }
    }

    if (empty($psono)) {
        outJson(['status' => 'error', 'message' => 'Order PSO number is required.']);
    }

    $polyMap = getPolyhouseNameMap($krfconn);

    // Check if shifted for this order in krf_productionorder_consumed
    $isOrderShifted = false;
    if (!empty($psono)) {
        $chkS = $krfconn->query("SELECT isshifted FROM krf_productionorder_consumed WHERE psono = '" . mysqli_real_escape_string($krfconn, $psono) . "' AND IFNULL(tedit, '') = '' LIMIT 1");
        if ($chkS && $sr = $chkS->fetch_assoc()) {
            $isOrderShifted = ($sr['isshifted'] == 1 || $sr['isshifted'] === '1' || strtolower(strval($sr['isshifted'])) === 'yes');
        }
    }

    $stkParts = array_filter(array_map('trim', explode(',', $stockOrderNo)));
    $stockCondSqlPh = "";
    $stockCondSqlRts = "";
    if (!empty($stkParts)) {
        $escList = array_map(function($s) use ($krfconn) { return "'" . mysqli_real_escape_string($krfconn, $s) . "'"; }, $stkParts);
        $inStk = implode(',', $escList);
        $stockCondSqlPh = " AND (p.dispd_agst_stockorderno IN ({$inStk}) OR p.stockorder_no IN ({$inStk}))";
        $stockCondSqlRts = " AND (r.dispd_agst_stockorderno IN ({$inStk}) OR r.stockorder_no IN ({$inStk}))";
    }

    $prodCondSqlPh = "";
    $prodCondSqlRts = "";
    $escProd = mysqli_real_escape_string($krfconn, $productName);
    $escPIdG = mysqli_real_escape_string($krfconn, $productId);
    if (!empty($escPIdG) && !empty($escProd)) {
        $prodCondSqlPh = " AND (p.productid = '{$escPIdG}' OR p.productname = '{$escProd}' OR p.productname LIKE '%{$escProd}%')";
        $prodCondSqlRts = " AND (r.productid = '{$escPIdG}' OR r.productname = '{$escProd}' OR r.productname LIKE '%{$escProd}%')";
    } elseif (!empty($escPIdG)) {
        $prodCondSqlPh = " AND p.productid = '{$escPIdG}'";
        $prodCondSqlRts = " AND r.productid = '{$escPIdG}'";
    } elseif (!empty($escProd)) {
        $prodCondSqlPh = " AND (p.productname = '{$escProd}' OR p.productname LIKE '%{$escProd}%')";
        $prodCondSqlRts = " AND (r.productname = '{$escProd}' OR r.productname LIKE '%{$escProd}%')";
    }

    $escPso = mysqli_real_escape_string($krfconn, $psono);
    $traysMap = [];

    // 1. Query krf_polyhouse_plant_details
    $sqlPh = "
        SELECT 
            p.polyhouseplantid,
            p.barcode,
            COALESCE(NULLIF(p.dispd_agst_psono, ''), p.psono) as psono,
            COALESCE(NULLIF(p.dispd_agst_stockorderno, ''), p.stockorder_no) as stockorder_no,
            p.dispd_agst_psono,
            p.dispd_agst_stockorderno,
            p.dispd_agst_clientid,
            p.productname,
            p.variety,
            p.bed_no,
            p.polyhouseid,
            COALESCE(p.qtypertray, 126) as qtypertray,
            p.status,
            p.health_status,
            p.movementremark,
            p.dispatchat,
            p.dispatchby
        FROM krf_polyhouse_plant_details p
        WHERE (
            p.dispd_agst_psono = '{$escPso}'
            OR (p.psono = '{$escPso}' AND (p.dispd_agst_psono IS NULL OR p.dispd_agst_psono = ''){$stockCondSqlPh})
        )
        AND LOWER(TRIM(p.status)) IN ('loaded', 'readytodispatch', 'ready to dispatch')
        AND p.barcode IS NOT NULL AND p.barcode != ''
        {$prodCondSqlPh}
        ORDER BY p.polyhouseplantid DESC
    ";
    $resPh = $krfconn->query($sqlPh);
    if ($resPh) {
        while ($row = $resPh->fetch_assoc()) {
            $bc = trim($row['barcode']);
            $phName = $polyMap[$row['polyhouseid']] ?? ('PH ' . $row['polyhouseid']);
            $effQty = $isOrderShifted ? 125 : (int)($row['qtypertray'] ?: 126);
            $dispAtFormatted = !empty($row['dispatchat']) ? date('d-M-Y H:i', strtotime($row['dispatchat'])) : '-';

            $traysMap[$bc] = [
                'polyhouseplantid' => (int)$row['polyhouseplantid'],
                'barcode'          => $bc,
                'psono'            => $row['dispd_agst_psono'] ?: $row['psono'],
                'stockorder_no'    => $row['dispd_agst_stockorderno'] ?: $row['stockorder_no'],
                'productname'      => $row['productname'],
                'variety'          => $row['variety'] ?: '',
                'bed_no'           => $row['bed_no'] ?: 'Bed-?',
                'polyhouseid'      => $row['polyhouseid'],
                'polyhousename'    => $phName,
                'qtypertray'       => $effQty,
                'current_status'   => $row['status'] ?: 'Loaded',
                'health_status'    => $row['health_status'] ?: 'Healthy',
                'movementremark'   => $row['movementremark'] ?: '',
                'dispatchat'       => $dispAtFormatted,
                'dispatchby'       => $row['dispatchby'] ?: 'Admin',
                'is_shifted'       => $isOrderShifted,
                'in_polyhouse'     => true,
                'in_readytosale'   => false
            ];
        }
    }

    // 2. Query krf_readytosale_stocks
    $sqlRts = "
        SELECT 
            r.id,
            r.polyhouseplantid,
            r.barcode,
            COALESCE(NULLIF(r.dispd_agst_psono, ''), r.psono) as psono,
            COALESCE(NULLIF(r.dispd_agst_stockorderno, ''), r.stockorder_no) as stockorder_no,
            r.dispd_agst_psono,
            r.dispd_agst_stockorderno,
            r.dispd_agst_clientid,
            r.productname,
            r.bedqrcode as bed_no,
            r.locationid as polyhouseid,
            COALESCE(r.qtypertray, 126) as qtypertray,
            r.status,
            COALESCE(r.dispatchat, r.loaded_at) as dispatchat,
            COALESCE(r.dispatchby, r.loaded_by) as dispatchby
        FROM krf_readytosale_stocks r
        WHERE (
            r.dispd_agst_psono = '{$escPso}'
            OR (r.psono = '{$escPso}' AND (r.dispd_agst_psono IS NULL OR r.dispd_agst_psono = ''){$stockCondSqlRts})
        )
        AND LOWER(TRIM(r.status)) IN ('loaded', 'readytodispatch', 'ready to dispatch')
        AND r.barcode IS NOT NULL AND r.barcode != ''
        {$prodCondSqlRts}
        ORDER BY r.id DESC
    ";
    $resRts = $krfconn->query($sqlRts);
    if ($resRts) {
        while ($row = $resRts->fetch_assoc()) {
            $bc = trim($row['barcode']);
            $phName = $polyMap[$row['polyhouseid']] ?? ('PH ' . $row['polyhouseid']);
            $effQty = $isOrderShifted ? 125 : (int)($row['qtypertray'] ?: 126);
            $dispAtFormatted = !empty($row['dispatchat']) ? date('d-M-Y H:i', strtotime($row['dispatchat'])) : '-';

            if (isset($traysMap[$bc])) {
                $traysMap[$bc]['in_readytosale'] = true;
                if ($traysMap[$bc]['dispatchat'] === '-' && $dispAtFormatted !== '-') {
                    $traysMap[$bc]['dispatchat'] = $dispAtFormatted;
                }
            } else {
                $traysMap[$bc] = [
                    'polyhouseplantid' => (int)($row['polyhouseplantid'] ?: $row['id']),
                    'barcode'          => $bc,
                    'psono'            => $row['dispd_agst_psono'] ?: $row['psono'],
                    'stockorder_no'    => $row['dispd_agst_stockorderno'] ?: $row['stockorder_no'],
                    'productname'      => $row['productname'],
                    'variety'          => '',
                    'bed_no'           => $row['bed_no'] ?: 'Bed-?',
                    'polyhouseid'      => $row['polyhouseid'],
                    'polyhousename'    => $phName,
                    'qtypertray'       => $effQty,
                    'current_status'   => $row['status'] ?: 'Loaded',
                    'health_status'    => 'Healthy',
                    'movementremark'   => '',
                    'dispatchat'       => $dispAtFormatted,
                    'dispatchby'       => $row['dispatchby'] ?: 'Admin',
                    'is_shifted'       => $isOrderShifted,
                    'in_polyhouse'     => false,
                    'in_readytosale'   => true
                ];
            }
        }
    }

    $loadedTrays = [];
    foreach ($traysMap as $bc => $t) {
        if ($t['in_polyhouse'] && $t['in_readytosale']) {
            $sourceTable = 'both';
            $sourceLabel = 'krf_polyhouse_plant_details & krf_readytosale_stocks';
        } elseif ($t['in_polyhouse']) {
            $sourceTable = 'krf_polyhouse_plant_details';
            $sourceLabel = 'krf_polyhouse_plant_details';
        } else {
            $sourceTable = 'krf_readytosale_stocks';
            $sourceLabel = 'krf_readytosale_stocks';
        }

        $t['source_table'] = $sourceTable;
        $t['source_label'] = $sourceLabel;
        $loadedTrays[] = $t;
    }

    outJson([
        'status'         => 'success',
        'trays'          => $loadedTrays,
        'count'          => count($loadedTrays),
        'is_shifted'     => $isOrderShifted
    ]);
}

// -------------------------------------------------------------
// 3.3 ACTION: RE-VERIFY ALLOCATION & LOADING STATUS
// Re-verifies allocated trays in krf_readystock_allocation (allocatedbed / allocate_ordqty)
// vs loaded trays in krf_polyhouse_plant_details
// -------------------------------------------------------------
if ($action === 'reverify_allocation_loading') {
    $psono = trim($_REQUEST['psono'] ?? '');
    $invoiceno = trim($_REQUEST['invoiceno'] ?? '');
    $stockOrderNo = trim($_REQUEST['stockorder_no'] ?? '');
    $productId = trim($_REQUEST['product_id'] ?? '');
    $productName = trim($_REQUEST['product_name'] ?? '');
    $itemId = (int)($_REQUEST['item_id'] ?? 0);

    if ($itemId > 0 && (empty($productId) || empty($productName))) {
        $qItem = $krfconn->query("SELECT product_id, product_name, stockorder_no, psono, invoiceno FROM krf_sales_order_items WHERE id = {$itemId} LIMIT 1");
        if ($qItem && $rItem = $qItem->fetch_assoc()) {
            if (empty($productId) && !empty($rItem['product_id'])) $productId = trim(strval($rItem['product_id']));
            if (empty($productName) && !empty($rItem['product_name'])) $productName = trim($rItem['product_name']);
            if (empty($stockOrderNo) && !empty($rItem['stockorder_no'])) $stockOrderNo = trim($rItem['stockorder_no']);
            if (empty($psono) && !empty($rItem['psono'])) $psono = trim($rItem['psono']);
            if (empty($invoiceno) && !empty($rItem['invoiceno'])) $invoiceno = trim($rItem['invoiceno']);
        }
    }

    if (empty($psono) && empty($invoiceno)) {
        outJson(['status' => 'error', 'message' => 'Order No (psono) or Invoice No is required.']);
    }

    $escPso = mysqli_real_escape_string($krfconn, $psono);
    $escInv = mysqli_real_escape_string($krfconn, $invoiceno);
    $escStock = mysqli_real_escape_string($krfconn, $stockOrderNo);
    $escPId = mysqli_real_escape_string($krfconn, $productId);
    $escPName = mysqli_real_escape_string($krfconn, $productName);

    // Build product filter for krf_readystock_allocation
    $rsaProdFilter = "";
    if (!empty($escPId) && !empty($escPName)) {
        $rsaProdFilter = " AND (productid = '{$escPId}' OR productname = '{$escPName}')";
    } elseif (!empty($escPId)) {
        $rsaProdFilter = " AND productid = '{$escPId}'";
    } elseif (!empty($escPName)) {
        $rsaProdFilter = " AND productname = '{$escPName}'";
    }

    // 1. Fetch allocated trays from krf_readystock_allocation strictly as per product
    $rsaRow = null;
    if (!empty($escPso)) {
        $qRsaActive = $krfconn->query("
            SELECT id, psono, stockorder_no, allocate_stockorder_no, productid, productname, isshifted, healthyplant, allocate_ordqty, allocatedbed
            FROM krf_readystock_allocation
            WHERE (tedit IS NULL OR tedit = '')
              AND status = 'allocated'
              AND psono = '{$escPso}'
              {$rsaProdFilter}
            ORDER BY id DESC LIMIT 1
        ");
        if ($qRsaActive && $qRsaActive->num_rows > 0) {
            $rsaRow = $qRsaActive->fetch_assoc();
        }
    }
    if (!$rsaRow) {
        $rsaSql = "
            SELECT 
                id, psono, stockorder_no, allocate_stockorder_no, productid, productname,
                isshifted, healthyplant, allocate_ordqty, allocatedbed
            FROM krf_readystock_allocation
            WHERE (
                (NULLIF(TRIM(psono), '') IS NOT NULL AND psono = '{$escPso}')
                OR (NULLIF(TRIM('{$escStock}'), '') IS NOT NULL AND (stockorder_no = '{$escStock}' OR allocate_stockorder_no = '{$escStock}'))
            )
            {$rsaProdFilter}
            AND LOWER(IFNULL(status, '')) != 'cancel'
            AND (tedit IS NULL OR tedit = '')
            ORDER BY (psono = '{$escPso}') DESC, id DESC
            LIMIT 1
        ";
        $rsaRes = $krfconn->query($rsaSql);
        $rsaRow = ($rsaRes && $rsaRes->num_rows > 0) ? $rsaRes->fetch_assoc() : null;
    }
    if ($rsaRow) {
        $actStk = trim($rsaRow['allocate_stockorder_no'] ?: ($rsaRow['stockorder_no'] ?: ''));
        if (!empty($actStk)) {
            $escStock = mysqli_real_escape_string($krfconn, $actStk);
        }
    }

    $isShiftedVal = false;
    $rawBed = '';
    $sumBedTrays = 0;
    $sumBedPlants = 0;
    $bedArray = [];

    if ($rsaRow) {
        $isShiftedVal = ($rsaRow['isshifted'] == 1 || $rsaRow['isshifted'] === '1' || strtolower(strval($rsaRow['isshifted'])) === 'yes');
        $rawBed = trim($rsaRow['allocatedbed'] ?? '');
        if (!empty($rawBed)) {
            $dec = json_decode($rawBed, true);
            if (is_array($dec)) {
                $bedArray = $dec;
                foreach ($dec as $b) {
                    $bName = trim($b['bed_name'] ?? '');
                    if (!empty($bName) && !preg_match('/^(RSO|KFP|KF-PS|PSO|KF-|SO-|PO-|BATCH-)/i', $bName) && !in_array(strtolower($bName), ['karjee', 'n/a', 'na', 'none', '-', 'bed'])) {
                        if (isset($b['tray_count'])) {
                            $sumBedTrays += (int)$b['tray_count'];
                        }
                        if (isset($b['plants'])) {
                            $sumBedPlants += (int)$b['plants'];
                        }
                    }
                }
            }
        }
    }

    $allocQty = 0;
    $soiItemCond = ($itemId > 0)
        ? "AND id = {$itemId}"
        : (!empty($escPId) && !empty($escPName)
            ? "AND (product_id = '{$escPId}' OR product_name = '{$escPName}')"
            : (!empty($escPId) ? "AND product_id = '{$escPId}'" : (!empty($escPName) ? "AND product_name = '{$escPName}'" : "")));

    $qSoi = $krfconn->query("
        SELECT allocate_ordqty, quantity, isshifted 
        FROM krf_sales_order_items 
        WHERE (psono = '{$escPso}' OR (NULLIF(TRIM(invoiceno), '') IS NOT NULL AND invoiceno = '{$escInv}'))
          {$soiItemCond}
        LIMIT 1
    ");
    if ($qSoi && $soiRow = $qSoi->fetch_assoc()) {
        $allocQty = (int)($soiRow['allocate_ordqty'] ?? 0);
        if ($allocQty <= 0) {
            $allocQty = (int)($soiRow['quantity'] ?? 0);
        }
        if ($soiRow['isshifted'] === 'yes' || $soiRow['isshifted'] === '1' || $soiRow['isshifted'] === 1) {
            $isShiftedVal = true;
        }
    }
    if ($allocQty <= 0 && $rsaRow) {
        $allocQty = (int)($rsaRow['allocate_ordqty'] ?? 0);
        if ($allocQty <= 0) {
            $allocQty = (int)($rsaRow['healthyplant'] ?? 0);
        }
    }
    if ($allocQty <= 0) {
        $pocProdCond = (!empty($escPId) && !empty($escPName))
            ? "AND (product_id = '{$escPId}' OR product_name = '{$escPName}')"
            : (!empty($escPId) ? "AND product_id = '{$escPId}'" : (!empty($escPName) ? "AND product_name = '{$escPName}'" : ""));
        $qPoc = $krfconn->query("
            SELECT consumed_qty, allocate_healthyplant, isshifted
            FROM krf_productionorder_consumed
            WHERE psono = '{$escPso}' AND IFNULL(tedit, '') = ''
            {$pocProdCond}
            LIMIT 1
        ");
        if ($qPoc && $pocRow = $qPoc->fetch_assoc()) {
            $allocQty = (int)($pocRow['consumed_qty'] ?? 0);
            if ($allocQty <= 0) $allocQty = (int)($pocRow['allocate_healthyplant'] ?? 0);
            if ((int)($pocRow['isshifted'] ?? 0) === 1) $isShiftedVal = true;
        }
    }

    $trayCap = $isShiftedVal ? 125 : 126;
    $expectedOrderTrays = ($allocQty > 0) ? (int)ceil($allocQty / $trayCap) : 0;
    $isRsaSpecificToThisOrder = (!empty($rsaRow['psono']) && trim($rsaRow['psono']) === $escPso);

    $allocatedTrays = 0;
    $allocatedPlants = 0;
    $source = 'allocatedbed_json';

    // Priority: Sum tray_count from allocatedbed JSON array ONLY if specific and proportional to this order
    if ($isRsaSpecificToThisOrder && $sumBedTrays > 0 && $sumBedTrays <= ceil($expectedOrderTrays * 1.25)) {
        $allocatedTrays = $sumBedTrays;
        $allocatedPlants = ($sumBedPlants > 0 && $sumBedPlants <= ceil($allocQty * 1.25)) ? $sumBedPlants : $allocQty;
    } else {
        $source = 'allocate_ordqty';
        $allocatedPlants = $allocQty;
        $allocatedTrays = $expectedOrderTrays;
    }

    // 2. Fetch loaded trays in krf_polyhouse_plant_details and krf_readytosale_stocks as per product
    $loadedCondProd = "";
    if (!empty($escPId) && !empty($escPName)) {
        $loadedCondProd = " AND (productid = '{$escPId}' OR productname = '{$escPName}'" . (!empty($escStock) ? " OR stockorder_no = '{$escStock}' OR dispd_agst_stockorderno = '{$escStock}'" : "") . ")";
    } elseif (!empty($escPId)) {
        $loadedCondProd = " AND (productid = '{$escPId}'" . (!empty($escStock) ? " OR stockorder_no = '{$escStock}' OR dispd_agst_stockorderno = '{$escStock}'" : "") . ")";
    } elseif (!empty($escPName)) {
        $loadedCondProd = " AND (productname = '{$escPName}'" . (!empty($escStock) ? " OR stockorder_no = '{$escStock}' OR dispd_agst_stockorderno = '{$escStock}'" : "") . ")";
    }

    $qLoaded = $krfconn->query("
        SELECT 
            COUNT(DISTINCT t.unique_tray_id) as loaded_trays,
            COALESCE(SUM(t.tray_plants), 0) as loaded_plants
        FROM (
            SELECT 
                CAST(COALESCE(NULLIF(barcode, ''), CONCAT('ID_', polyhouseplantid)) AS CHAR(100)) as unique_tray_id,
                MAX(COALESCE(NULLIF(qtypertray, ''), 126)) as tray_plants
            FROM krf_polyhouse_plant_details
            WHERE (dispd_agst_psono = '{$escPso}' OR (psono = '{$escPso}' AND (dispd_agst_psono IS NULL OR dispd_agst_psono = '' OR dispd_agst_psono = '{$escPso}')))
              AND LOWER(TRIM(status)) IN ('loaded', 'readytodispatch', 'ready to dispatch')
              {$loadedCondProd}
            GROUP BY CAST(COALESCE(NULLIF(barcode, ''), CONCAT('ID_', polyhouseplantid)) AS CHAR(100))
            UNION
            SELECT 
                CAST(COALESCE(NULLIF(barcode, ''), CONCAT('RTS_', id)) AS CHAR(100)) as unique_tray_id,
                MAX(COALESCE(NULLIF(qtypertray, ''), 126)) as tray_plants
            FROM krf_readytosale_stocks
            WHERE (dispd_agst_psono = '{$escPso}' OR (psono = '{$escPso}' AND (dispd_agst_psono IS NULL OR dispd_agst_psono = '' OR dispd_agst_psono = '{$escPso}')))
              AND LOWER(TRIM(status)) IN ('loaded', 'readytodispatch', 'ready to dispatch')
              {$loadedCondProd}
            GROUP BY CAST(COALESCE(NULLIF(barcode, ''), CONCAT('RTS_', id)) AS CHAR(100))
        ) t
    ");
    $loadedRow = ($qLoaded) ? $qLoaded->fetch_assoc() : null;
    $loadedTrays = (int)($loadedRow['loaded_trays'] ?? 0);
    $loadedPlants = (int)($loadedRow['loaded_plants'] ?? 0);

    $isMatched = ($allocatedTrays > 0 && $loadedTrays === $allocatedTrays);
    $pendingTrays = max(0, $allocatedTrays - $loadedTrays);

    outJson([
        'status'              => 'success',
        'is_matched'          => $isMatched,
        'allocated_trays'     => $allocatedTrays,
        'loaded_trays'        => $loadedTrays,
        'pending_trays'       => $pendingTrays,
        'allocated_plants'    => $allocatedPlants,
        'loaded_plants'       => $loadedPlants,
        'allocatedbed_raw'    => $rawBed,
        'allocatedbed_source' => $source,
        'allocatedbed_detail' => $bedArray,
        'is_shifted'          => $isShiftedVal
    ]);
}

// -------------------------------------------------------------
// 4. ACTION: CONFIRM FINAL DISPATCH FOR ORDER
// Updates:
//   - krf_polyhouse_plant_details (status 'Loaded' -> 'Final Dispatch')
//   - krf_productionorder_consumed (status -> 'dispatched')
//   - krf_sales_order_items (status 'readytoload' -> 'Final Dispatch')
// -------------------------------------------------------------
if ($action === 'confirm_final_dispatch') {
    $psono = trim($_REQUEST['psono'] ?? '');
    $invoiceno = trim($_REQUEST['invoiceno'] ?? '');
    $driverNotes = trim($_REQUEST['notes'] ?? '');
    $partialAction = trim($_REQUEST['partial_action'] ?? 'keep_in_readytoload'); // 'keep_in_readytoload' or 'release_to_stock'
    $productId = trim($_REQUEST['product_id'] ?? '');
    $productName = trim($_REQUEST['product_name'] ?? '');
    $itemId = (int)($_REQUEST['item_id'] ?? 0);
    $stockOrderNo = trim($_REQUEST['stockorder_no'] ?? '');

    if ($itemId > 0 && (empty($productId) || empty($productName) || empty($psono))) {
        $qItem = $krfconn->query("SELECT product_id, product_name, stockorder_no, psono, invoiceno FROM krf_sales_order_items WHERE id = {$itemId} LIMIT 1");
        if ($qItem && $rItem = $qItem->fetch_assoc()) {
            if (empty($productId) && !empty($rItem['product_id'])) $productId = trim(strval($rItem['product_id']));
            if (empty($productName) && !empty($rItem['product_name'])) $productName = trim($rItem['product_name']);
            if (empty($stockOrderNo) && !empty($rItem['stockorder_no'])) $stockOrderNo = trim($rItem['stockorder_no']);
            if (empty($psono) && !empty($rItem['psono'])) $psono = trim($rItem['psono']);
            if (empty($invoiceno) && !empty($rItem['invoiceno'])) $invoiceno = trim($rItem['invoiceno']);
        }
    }

    $escInv = mysqli_real_escape_string($krfconn, $invoiceno);
    $escPso = mysqli_real_escape_string($krfconn, $psono);
    $escPId = mysqli_real_escape_string($krfconn, $productId);
    $escPName = mysqli_real_escape_string($krfconn, $productName);
    $escStock = mysqli_real_escape_string($krfconn, $stockOrderNo);

    if (empty($psono) && empty($invoiceno)) {
        outJson(['status' => 'error', 'message' => 'Provisional Sales Order No (psono) or Invoice No is required.']);
    }

    $prodFilterPoly = "";
    if (!empty($escPId) && !empty($escPName)) {
        $prodFilterPoly = " AND (productid = '{$escPId}' OR productname = '{$escPName}'" . (!empty($escStock) ? " OR stockorder_no = '{$escStock}' OR dispd_agst_stockorderno = '{$escStock}'" : "") . ")";
    } elseif (!empty($escPId)) {
        $prodFilterPoly = " AND (productid = '{$escPId}'" . (!empty($escStock) ? " OR stockorder_no = '{$escStock}' OR dispd_agst_stockorderno = '{$escStock}'" : "") . ")";
    } elseif (!empty($escPName)) {
        $prodFilterPoly = " AND (productname = '{$escPName}'" . (!empty($escStock) ? " OR stockorder_no = '{$escStock}' OR dispd_agst_stockorderno = '{$escStock}'" : "") . ")";
    }

    $krfconn->begin_transaction();

    try {
        // 1. Fetch loaded plant trays for this psono and product to log into history
        // Resolve active allocated batches for this order and product from krf_readystock_allocation
        $activeAllocBatches = [];
        $qRsaBatches = $krfconn->query("
            SELECT stockorder_no, allocate_stockorder_no 
            FROM krf_readystock_allocation 
            WHERE psono = '{$escPso}' 
              AND LOWER(IFNULL(status, '')) != 'cancel' 
              AND (tedit IS NULL OR tedit = '')
              " . (!empty($escPId) ? "AND (productid = '{$escPId}' OR productname = '{$escPName}')" : (!empty($escPName) ? "AND productname = '{$escPName}'" : "")) . "
        ");
        if ($qRsaBatches && $qRsaBatches->num_rows > 0) {
            while ($rb = $qRsaBatches->fetch_assoc()) {
                if (!empty($rb['allocate_stockorder_no'])) $activeAllocBatches[] = trim($rb['allocate_stockorder_no']);
                if (!empty($rb['stockorder_no'])) $activeAllocBatches[] = trim($rb['stockorder_no']);
            }
        }
        if (!empty($stockOrderNo)) {
            foreach (explode(',', $stockOrderNo) as $sb) {
                $sb = trim($sb);
                if (!empty($sb)) $activeAllocBatches[] = $sb;
            }
        }
        $activeAllocBatches = array_values(array_unique(array_filter($activeAllocBatches)));

        // Auto-undo any orphan loaded trays for this psono from older/replaced batches back to Readytosale
        if (!empty($activeAllocBatches)) {
            $escOldList = "'" . implode("','", array_map(function($b) use ($krfconn) { return mysqli_real_escape_string($krfconn, $b); }, $activeAllocBatches)) . "'";
            $batchNotCond = " AND (stockorder_no NOT IN ({$escOldList}) AND IFNULL(dispd_agst_stockorderno, '') NOT IN ({$escOldList}))";
            $prodMatchCond = !empty($escPId) ? " AND (productid = '{$escPId}' OR productname = '{$escPName}')" : (!empty($escPName) ? " AND productname = '{$escPName}'" : "");

            $updOldPh = "
                UPDATE krf_polyhouse_plant_details
                SET status = 'Readytosale', workingstatus = 'Readytosale',
                    dispd_agst_psono = NULL, dispd_agst_stockorderno = NULL, dispd_agst_clientid = NULL,
                    dispatchqty = NULL, dispatchat = NULL, dispatchby = NULL,
                    movementremark = CONCAT('Auto-undone load on re-allocation by ', '{$loginname}'),
                    updated_at = NOW(), updated_by = '{$loginname}'
                WHERE (psono = '{$escPso}' OR dispd_agst_psono = '{$escPso}')
                  AND status IN ('Loaded', 'ReadyToDispatch')
                  {$batchNotCond}
                  {$prodMatchCond}
            ";
            $krfconn->query($updOldPh);

            $updOldRts = "
                UPDATE krf_readytosale_stocks
                SET status = 'Readytosale', dispatchqty = '0',
                    dispd_agst_psono = NULL, dispd_agst_stockorderno = NULL, dispd_agst_clientid = NULL,
                    dispatchat = NULL, dispatchby = NULL, loaded_at = NULL, loaded_by = NULL,
                    updated_at = NOW()
                WHERE (psono = '{$escPso}' OR dispd_agst_psono = '{$escPso}')
                  AND status IN ('Loaded', 'ReadyToDispatch')
                  {$batchNotCond}
                  {$prodMatchCond}
            ";
            $krfconn->query($updOldRts);
        }

        // 1. Fetch loaded plant trays for this psono and product across both tables (matching get_item_loaded_trays)
        $loadedTraysMap = [];
        $stockBatchCondPh = "";
        $stockBatchCondRts = "";
        if (!empty($activeAllocBatches)) {
            $escList = "'" . implode("','", array_map(function($b) use ($krfconn) { return mysqli_real_escape_string($krfconn, $b); }, $activeAllocBatches)) . "'";
            $stockBatchCondPh = " AND (dispd_agst_stockorderno IN ({$escList}) OR stockorder_no IN ({$escList}))";
            $stockBatchCondRts = " AND (dispd_agst_stockorderno IN ({$escList}) OR stockorder_no IN ({$escList}))";
        }

        $prodCondPh = "";
        $prodCondRts = "";
        if (!empty($escPId) && !empty($escPName)) {
            $prodCondPh = " AND (productid = '{$escPId}' OR productname = '{$escPName}')";
            $prodCondRts = " AND (productid = '{$escPId}' OR productname = '{$escPName}')";
        } elseif (!empty($escPId)) {
            $prodCondPh = " AND productid = '{$escPId}'";
            $prodCondRts = " AND productid = '{$escPId}'";
        } elseif (!empty($escPName)) {
            $prodCondPh = " AND productname = '{$escPName}'";
            $prodCondRts = " AND productname = '{$escPName}'";
        }

        // Query 1: krf_polyhouse_plant_details
        $sqlPh = "
            SELECT polyhouseplantid, barcode, polyhouseid, bed_no, productname, productid, stockorder_no, qtypertray, status
            FROM krf_polyhouse_plant_details
            WHERE (dispd_agst_psono = '{$escPso}' OR (psono = '{$escPso}' AND (dispd_agst_psono IS NULL OR dispd_agst_psono = '' OR dispd_agst_psono = '{$escPso}') {$stockBatchCondPh}))
              AND status IN ('Loaded', 'ReadyToDispatch')
              {$prodCondPh}
              {$stockBatchCondPh}
            ORDER BY polyhouseplantid DESC
        ";
        $resPh = $krfconn->query($sqlPh);
        if ($resPh) {
            while ($row = $resPh->fetch_assoc()) {
                $bc = trim($row['barcode'] ?? '');
                $key = !empty($bc) ? $bc : ('PH_' . $row['polyhouseplantid']);
                $loadedTraysMap[$key] = $row;
            }
        }

        // Query 2: krf_readytosale_stocks
        $sqlRts = "
            SELECT id, polyhouseplantid, barcode, locationid as polyhouseid, bedqrcode as bed_no, productname, productid, stockorder_no, qtypertray, status
            FROM krf_readytosale_stocks
            WHERE (dispd_agst_psono = '{$escPso}' OR (psono = '{$escPso}' AND (dispd_agst_psono IS NULL OR dispd_agst_psono = '' OR dispd_agst_psono = '{$escPso}') {$stockBatchCondRts}))
              AND status IN ('Loaded', 'ReadyToDispatch')
              {$prodCondRts}
              {$stockBatchCondRts}
            ORDER BY id DESC
        ";
        $resRts = $krfconn->query($sqlRts);
        if ($resRts) {
            while ($row = $resRts->fetch_assoc()) {
                $bc = trim($row['barcode'] ?? '');
                $key = !empty($bc) ? $bc : ('RTS_' . $row['id']);
                if (!isset($loadedTraysMap[$key])) {
                    $row['polyhouseplantid'] = $row['polyhouseplantid'] ?: $row['id'];
                    $loadedTraysMap[$key] = $row;
                }
            }
        }

        $loadedTrays = array_values($loadedTraysMap);

        // Strict Server-Side Validation: Cannot confirm if 0 trays are loaded
        if (empty($loadedTrays)) {
            // Check if RSO / SO order and attempt auto-load of active trays
            $qStock = $krfconn->query("
                SELECT stockorder_no, quantity, clientid, invoiceno 
                FROM sales_invoice_items 
                WHERE psono = '{$escPso}' AND stockorder_no IS NOT NULL AND stockorder_no != ''
                " . (!empty($escPId) ? "AND (productid = '{$escPId}' OR productname = '{$escPName}')" : "") . "
                LIMIT 1
            ");
            if (!$qStock || $qStock->num_rows === 0) {
                $qStock = $krfconn->query("
                    SELECT stockorder_no, quantity, clientid 
                    FROM krf_sales_order_items 
                    WHERE psono = '{$escPso}' AND stockorder_no IS NOT NULL AND stockorder_no != ''
                    " . (!empty($escPId) ? "AND (product_id = '{$escPId}' OR product_name = '{$escPName}')" : "") . "
                    LIMIT 1
                ");
            }
            if ($qStock && $stkRow = $qStock->fetch_assoc()) {
                $chkStk = trim($stkRow['stockorder_no'] ?? '');
                if (preg_match('/^(RSO|SO)-/i', $chkStk)) {
                    $autoRes = autoMarkRsoTraysAsLoaded($krfconn, $psono, $chkStk, (int)($stkRow['quantity'] ?? 0), $stkRow['clientid'] ?? '', $loginname, $stkRow['invoiceno'] ?? $invoiceno);
                    if ($autoRes['status'] === 'success') {
                        // Re-query loaded trays
                        $chkStmt2 = $krfconn->prepare("
                            SELECT polyhouseplantid, barcode, polyhouseid, bed_no, productname, productid, stockorder_no, qtypertray, status 
                            FROM krf_polyhouse_plant_details 
                            WHERE (psono = ? OR dispd_agst_psono = ?) AND status IN ('Loaded', 'ReadyToDispatch')
                            {$prodFilterPoly}
                        ");
                        if ($chkStmt2) {
                            $chkStmt2->bind_param('ss', $psono, $psono);
                            $chkStmt2->execute();
                            $r2 = $chkStmt2->get_result();
                            while ($row2 = $r2->fetch_assoc()) {
                                $loadedTrays[] = $row2;
                            }
                            $chkStmt2->close();
                        }
                    }
                }
            }
        }

        if (empty($loadedTrays)) {
            $krfconn->rollback();
            $prodTxt = !empty($productName) ? " for {$productName}" : "";
            outJson([
                'status'  => 'error',
                'message' => "Status cannot be updated! Order {$psono}{$prodTxt} currently has 0 trays recorded as loaded. Please load trays before confirming."
            ]);
        }

        $loadedTraysCount = count($loadedTrays);
        $actualLoadedPlants = 0;
        $loadedBarcodes = [];
        $loadedPolyIds = [];

        foreach ($loadedTrays as $tray) {
            $pts = (int)($tray['qtypertray'] ?: 126);
            $actualLoadedPlants += $pts;
            if (!empty($tray['barcode'])) {
                $loadedBarcodes[] = trim($tray['barcode']);
            }
            if (!empty($tray['polyhouseplantid'])) {
                $loadedPolyIds[] = (int)$tray['polyhouseplantid'];
            }
        }
        $loadedPolyIds = array_values(array_unique(array_filter($loadedPolyIds)));

        // Strict Server-Side Validation: User CANNOT update status until ALL trays are loaded!
        $invItemProdCond = "";
        if ($itemId > 0) {
            $invItemProdCond = " AND (id = {$itemId} OR (productid = '{$escPId}' OR productname = '{$escPName}'))";
        } elseif (!empty($escPId) && !empty($escPName)) {
            $invItemProdCond = " AND (productid = '{$escPId}' OR productname = '{$escPName}')";
        } elseif (!empty($escPId)) {
            $invItemProdCond = " AND productid = '{$escPId}'";
        } elseif (!empty($escPName)) {
            $invItemProdCond = " AND productname = '{$escPName}'";
        }

        if (!empty($escInv)) {
            $qInvTarget = $krfconn->query("
                SELECT SUM(quantity) as tot_qty 
                FROM sales_invoice_items 
                WHERE invoiceno = '{$escInv}' 
                  AND LOWER(TRIM(COALESCE(status, ''))) = 'readytoload'
                  {$invItemProdCond}
            ");
            if ($qInvTarget && $rowInvT = $qInvTarget->fetch_assoc()) {
                $invReqPlants = (int)($rowInvT['tot_qty'] ?? 0);
                $invReqTrays = ($invReqPlants > 0) ? (int)ceil($invReqPlants / 126) : 0;
                // In 1-tray verification flow, having at least 1 loaded tray verifies the batch
                if ($invReqTrays > 0 && $loadedTraysCount <= 0) {
                    $krfconn->rollback();
                    $prodTxt = !empty($productName) ? " for {$productName}" : "";
                    outJson([
                        'status'  => 'error',
                        'message' => "Status cannot be updated! Invoice {$invoiceno}{$prodTxt} has no verification trays loaded. Please scan and load at least 1 verification tray before updating status to Dispatched."
                    ]);
                }
            }
        }

        // 2. Fetch active crop items to calculate actual allocated trays & plants strictly as per product
        // Excludes box packing charges and cancelled/rejected items
        $itemCondSoi = "";
        if ($itemId > 0) {
            $itemCondSoi = " AND (soi.id = {$itemId} OR soi.id IN (SELECT COALESCE(original_soi_id, 0) FROM sales_invoice_items WHERE id = {$itemId} AND original_soi_id > 0)" . (!empty($escPId) ? " OR soi.product_id = '{$escPId}'" : "") . (!empty($escPName) ? " OR soi.product_name = '{$escPName}'" : "") . ")";
        } elseif (!empty($escPId) && !empty($escPName)) {
            $itemCondSoi = " AND (soi.product_id = '{$escPId}' OR soi.product_name = '{$escPName}')";
        } elseif (!empty($escPId)) {
            $itemCondSoi = " AND soi.product_id = '{$escPId}'";
        } elseif (!empty($escPName)) {
            $itemCondSoi = " AND soi.product_name = '{$escPName}'";
        }

        $qAllocItems = $krfconn->query("
            SELECT 
                soi.id,
                soi.sono,
                soi.psono,
                soi.stockorder_no,
                soi.projectid,
                soi.clientid,
                soi.product_sku,
                soi.product_id,
                soi.product_name,
                soi.quantity AS original_sown_qty,
                soi.rate,
                soi.taxableamount,
                soi.cgst_percent,
                soi.cgst_amount,
                soi.sgst_percent,
                soi.sgst_amount,
                soi.igst_percent,
                soi.igst_amount,
                soi.tax_amount,
                soi.amount,
                soi.deliverydate,
                soi.confirmdeliverydate,
                soi.allocate_ordqty,
                soi.isshifted AS soi_isshifted,
                poc.id AS poc_id,
                poc.allocate_healthyplant,
                poc.extraqtyforshifting,
                poc.consumed_qty,
                poc.isshifted AS poc_isshifted
            FROM krf_sales_order_items soi
            LEFT JOIN krf_productionorder_consumed poc ON (
                (NULLIF(TRIM(soi.psono), '') IS NOT NULL AND soi.psono = poc.psono AND (
                    (NULLIF(TRIM(soi.product_id), '') IS NOT NULL AND soi.product_id = poc.product_id)
                    OR (NULLIF(TRIM(soi.stockorder_no), '') IS NOT NULL AND (soi.stockorder_no = poc.stockorderno OR soi.stockorder_no = poc.allocated_stockorderno))
                ))
                OR (NULLIF(TRIM(soi.sono), '') IS NOT NULL AND soi.sono = poc.sono AND NULLIF(TRIM(soi.stockorder_no), '') IS NOT NULL AND soi.stockorder_no = poc.stockorderno)
            ) AND IFNULL(poc.tedit, '') = ''
            LEFT JOIN krf_sales_order so ON (soi.sono = so.sono OR (NULLIF(TRIM(soi.psono), '') IS NOT NULL AND soi.psono = so.psono))
            WHERE (soi.psono = '{$escPso}' OR (NULLIF(TRIM(soi.sono), '') IS NOT NULL AND soi.sono = '{$escPso}'))
              AND soi.product_id != 'BOX_PACKING'
              AND soi.product_name NOT LIKE '%Box Packing Charges%'
              AND (
                  LOWER(TRIM(soi.status)) IN ('readytoload', 'ready to load', 'readytodispatch', 'ready to dispatch', 'partialdispatch', 'allocated', 'confirmed')
                  OR (
                      (NULLIF(TRIM(soi.invoiceno), '') IS NOT NULL OR so.invoicetype = 'dispatchinvoice')
                      AND LOWER(TRIM(COALESCE(soi.status, ''))) NOT IN ('dispatched', 'final dispatch', 'fulldispatch', 'cancelled', 'rejected')
                  )
              )
              {$itemCondSoi}
            ORDER BY soi.id ASC
        ");

        $itemsList = [];
        $totalAllocatedTrays = 0;
        $totalAllocatedPlants = 0;

        if ($qAllocItems && $qAllocItems->num_rows > 0) {
            while ($it = $qAllocItems->fetch_assoc()) {
                $soiAlloc = (int)($it['allocate_ordqty'] ?? 0);
                $pocConsumed = (int)($it['consumed_qty'] ?? 0);
                $pocHealthy = (int)($it['allocate_healthyplant'] ?? 0);
                $origQty = (int)($it['original_sown_qty'] ?? 0);

                if ($soiAlloc > 0) {
                    $allocPlants = $soiAlloc;
                } elseif ($pocConsumed > 0) {
                    $allocPlants = $pocConsumed;
                } elseif ($pocHealthy > 0 && ($origQty <= 0 || $pocHealthy <= $origQty)) {
                    $allocPlants = $pocHealthy;
                } else {
                    $allocPlants = $origQty;
                }
                $cap = ((int)($it['poc_isshifted'] ?? 0) === 1 || ($it['soi_isshifted'] ?? '') === 'yes') ? 125 : 126;
                $trays = ($allocPlants > 0) ? (int)ceil($allocPlants / $cap) : 0;
                $it['calc_alloc_plants'] = $allocPlants;
                $it['calc_alloc_trays'] = $trays;
                $itemsList[] = $it;
                $totalAllocatedTrays += $trays;
                $totalAllocatedPlants += $allocPlants;
            }
        }

        // Fallback: If no items returned with above filter, query all non-cancelled items for this order and product
        if ($totalAllocatedTrays <= 0) {
            $qFallback = $krfconn->query("
                SELECT 
                    soi.id,
                    soi.sono,
                    soi.psono,
                    soi.stockorder_no,
                    soi.projectid,
                    soi.clientid,
                    soi.product_sku,
                    soi.product_id,
                    soi.product_name,
                    soi.quantity AS original_sown_qty,
                    soi.rate,
                    soi.taxableamount,
                    soi.cgst_percent,
                    soi.cgst_amount,
                    soi.sgst_percent,
                    soi.sgst_amount,
                    soi.igst_percent,
                    soi.igst_amount,
                    soi.tax_amount,
                    soi.amount,
                    soi.deliverydate,
                    soi.confirmdeliverydate,
                    soi.allocate_ordqty,
                    soi.isshifted AS soi_isshifted,
                    poc.id AS poc_id,
                    poc.allocate_healthyplant,
                    poc.extraqtyforshifting,
                    poc.consumed_qty,
                    poc.isshifted AS poc_isshifted
                FROM krf_sales_order_items soi
                LEFT JOIN krf_productionorder_consumed poc ON (
                    (NULLIF(TRIM(soi.psono), '') IS NOT NULL AND soi.psono = poc.psono AND (
                        (NULLIF(TRIM(soi.product_id), '') IS NOT NULL AND soi.product_id = poc.product_id)
                        OR (NULLIF(TRIM(soi.stockorder_no), '') IS NOT NULL AND (soi.stockorder_no = poc.stockorderno OR soi.stockorder_no = poc.allocated_stockorderno))
                    ))
                    OR (NULLIF(TRIM(soi.sono), '') IS NOT NULL AND soi.sono = poc.sono AND NULLIF(TRIM(soi.stockorder_no), '') IS NOT NULL AND soi.stockorder_no = poc.stockorderno)
                ) AND IFNULL(poc.tedit, '') = ''
                WHERE (soi.psono = '{$escPso}' OR (NULLIF(TRIM(soi.sono), '') IS NOT NULL AND soi.sono = '{$escPso}'))
                  AND soi.product_id != 'BOX_PACKING'
                  AND soi.product_name NOT LIKE '%Box Packing Charges%'
                  AND LOWER(TRIM(COALESCE(soi.status, ''))) NOT IN ('cancelled', 'rejected')
                  {$itemCondSoi}
                ORDER BY soi.id ASC
            ");
            if ($qFallback) {
                while ($it = $qFallback->fetch_assoc()) {
                    $soiAlloc = (int)($it['allocate_ordqty'] ?? 0);
                    $pocConsumed = (int)($it['consumed_qty'] ?? 0);
                    $pocHealthy = (int)($it['allocate_healthyplant'] ?? 0);
                    $origQty = (int)($it['original_sown_qty'] ?? 0);

                    if ($soiAlloc > 0) {
                        $allocPlants = $soiAlloc;
                    } elseif ($pocConsumed > 0) {
                        $allocPlants = $pocConsumed;
                    } elseif ($pocHealthy > 0 && ($origQty <= 0 || $pocHealthy <= $origQty)) {
                        $allocPlants = $pocHealthy;
                    } else {
                        $allocPlants = $origQty;
                    }
                    $cap = ((int)($it['poc_isshifted'] ?? 0) === 1 || ($it['soi_isshifted'] ?? '') === 'yes') ? 125 : 126;
                    $trays = ($allocPlants > 0) ? (int)ceil($allocPlants / $cap) : 0;
                    $it['calc_alloc_plants'] = $allocPlants;
                    $it['calc_alloc_trays'] = $trays;
                    $itemsList[] = $it;
                    $totalAllocatedTrays += $trays;
                    $totalAllocatedPlants += $allocPlants;
                }
            }
        }

        // Re-verify against krf_readystock_allocation: allocatedbed JSON array or allocate_ordqty
        // ONLY match the specific order psono and product (prevent cross-product / cross-order pollution)
        $rsaProdFilter = "";
        if (!empty($escPId) && !empty($escPName)) {
            $rsaProdFilter = " AND (productid = '{$escPId}' OR productname = '{$escPName}')";
        } elseif (!empty($escPId)) {
            $rsaProdFilter = " AND productid = '{$escPId}'";
        } elseif (!empty($escPName)) {
            $rsaProdFilter = " AND productname = '{$escPName}'";
        }

        $qRsaBeds = $krfconn->query("
            SELECT allocatedbed, allocate_ordqty, healthyplant, isshifted, shiftingqty
            FROM krf_readystock_allocation
            WHERE (NULLIF(TRIM(psono), '') IS NOT NULL AND psono = '{$escPso}')
              AND LOWER(IFNULL(status, '')) != 'cancel'
              AND (tedit IS NULL OR tedit = '')
              {$rsaProdFilter}
            ORDER BY id DESC
        ");
        $rsaSumBeds = 0;
        $rsaSumPlants = 0;
        $rsaOrdQty = 0;
        $rsaShiftingQty = 0;
        $rsaIsShifted = false;
        if ($qRsaBeds && $qRsaBeds->num_rows > 0) {
            while ($rRow = $qRsaBeds->fetch_assoc()) {
                $rsaOrdQty += (int)($rRow['allocate_ordqty'] ?? $rRow['healthyplant'] ?? 0);
                $rsaShiftingQty += (int)($rRow['shiftingqty'] ?? 0);
                if ($rRow['isshifted'] == 1 || $rRow['isshifted'] === '1' || strtolower(strval($rRow['isshifted'])) === 'yes') {
                    $rsaIsShifted = true;
                }
                $rawBed = trim($rRow['allocatedbed'] ?? '');
                if (!empty($rawBed)) {
                    $dec = json_decode($rawBed, true);
                    if (is_array($dec)) {
                        foreach ($dec as $b) {
                            $bName = trim($b['bed_name'] ?? '');
                            // Skip phantom beds named after batch numbers or Karjee
                            if (!empty($bName) && !preg_match('/^(RSO|KFP|KF-PS|PSO|KF-|SO-|PO-|BATCH-)/i', $bName) && !in_array(strtolower($bName), ['karjee', 'n/a', 'na', 'none', '-', 'bed'])) {
                                $rsaSumBeds += (int)($b['tray_count'] ?? 0);
                                $rsaSumPlants += (int)($b['plants'] ?? 0);
                            }
                        }
                    }
                }
            }
        }

        // Apply proportionality and bounds check:
        // Tray count from allocatedbed must be proportional to this order item's quantity, not the whole batch!
        $targetReqPlants = $rsaIsShifted ? $rsaOrdQty : ($rsaOrdQty + $rsaShiftingQty);
        $currCapDivisor = $rsaIsShifted ? 125 : 126;
        $capTrays = ($totalAllocatedTrays > 0) ? $totalAllocatedTrays : ($targetReqPlants > 0 ? (int)ceil($targetReqPlants / $currCapDivisor) : 0);
        $capPlants = ($totalAllocatedPlants > 0) ? $totalAllocatedPlants : $targetReqPlants;

        if ($rsaSumBeds > 0 && ($capTrays <= 0 || $rsaSumBeds <= ceil($capTrays * 1.25))) {
            $totalAllocatedTrays = $rsaSumBeds;
            if ($rsaSumPlants > 0 && ($capPlants <= 0 || $rsaSumPlants <= ceil($capPlants * 1.25))) {
                $totalAllocatedPlants = $rsaSumPlants;
            }
        } elseif ($capTrays > 0) {
            $totalAllocatedTrays = $capTrays;
            if ($capPlants > 0) {
                $totalAllocatedPlants = $capPlants;
            }
        }

        // Strict Server-Side Validation: Loaded trays in polyhouse MUST equal allocated trays as per product!
        if ($totalAllocatedTrays > 0 && $loadedTraysCount !== $totalAllocatedTrays) {
            $krfconn->rollback();
            $prodLabel = !empty($productName) ? " for {$productName}" : "";
            outJson([
                'status'          => 'mismatch_error',
                'message'         => "Status cannot be updated to Dispatched{$prodLabel}! Loaded trays in polyhouse ({$loadedTraysCount}) does not match allocated trays ({$totalAllocatedTrays}) as per krf_readystock_allocation. Please load all allocated trays or undo loaded trays.",
                'allocated_trays' => $totalAllocatedTrays,
                'loaded_trays'    => $loadedTraysCount,
                'pending_trays'   => max(0, $totalAllocatedTrays - $loadedTraysCount),
                'can_undo'        => true,
                'can_load'        => true
            ]);
        }

        $isPartial = ($loadedTraysCount < $totalAllocatedTrays);
        $salesOrderStatus = $isPartial ? 'PartialDispatch' : 'FullDispatch';
        $remainingPlants = max(0, $totalAllocatedPlants - $actualLoadedPlants);
        $remainingTrays  = max(0, $totalAllocatedTrays - $loadedTraysCount);
        $effectiveDispatchedPlants = ($totalAllocatedPlants > 0) ? $totalAllocatedPlants : $actualLoadedPlants;

        // 3. Update krf_polyhouse_plant_details: loaded trays -> 'Final Dispatch'
        $polyWhereClauses = [];
        if (!empty($loadedPolyIds)) {
            $polyWhereClauses[] = "polyhouseplantid IN (" . implode(',', $loadedPolyIds) . ")";
        }
        if (!empty($loadedBarcodes)) {
            $bcEscList = "'" . implode("','", array_map(function($b) use ($krfconn) {
                return mysqli_real_escape_string($krfconn, $b);
            }, $loadedBarcodes)) . "'";
            $polyWhereClauses[] = "barcode IN ({$bcEscList})";
        }
        if (empty($polyWhereClauses)) {
            $polyWhereClauses[] = "((psono = '{$escPso}' OR dispd_agst_psono = '{$escPso}') AND status IN ('Loaded', 'ReadyToDispatch') {$prodFilterPoly})";
        }
        $updPolySql = "
            UPDATE krf_polyhouse_plant_details 
            SET 
                status = 'Final Dispatch',
                workingstatus = 'Final Dispatch',
                move_at = CURDATE(),
                dispatchdate_internal = CURDATE(),
                dispatchat = NOW(),
                dispatchby = '{$loginname}',
                updated_at = NOW(),
                updated_by = '{$loginname}'
            WHERE " . implode(" OR ", $polyWhereClauses) . "
        ";
        $krfconn->query($updPolySql);
        $updPolyCount = $krfconn->affected_rows;

        // 4. Update krf_readytosale_stocks: loaded trays -> 'Dispatched'
        $updRtsCount = 0;
        if (!empty($loadedPolyIds)) {
            $polyIdListStr = implode(',', $loadedPolyIds);
            $updRtsSql = "
                UPDATE krf_readytosale_stocks 
                SET 
                    status = 'Dispatched',
                    dispatchby = '{$loginname}',
                    dispatchat = NOW(),
                    updated_at = NOW()
                WHERE polyhouseplantid IN ({$polyIdListStr})
            ";
            $krfconn->query($updRtsSql);
            $updRtsCount += $krfconn->affected_rows;
        }

        // Also update by barcode for loaded trays
        if (!empty($loadedBarcodes)) {
            $bcListStr = "'" . implode("','", array_map(function($b) use ($krfconn) {
                return mysqli_real_escape_string($krfconn, $b);
            }, $loadedBarcodes)) . "'";
            $krfconn->query("
                UPDATE krf_readytosale_stocks 
                SET 
                    status = 'Dispatched',
                    dispatchby = '{$loginname}',
                    dispatchat = NOW(),
                    updated_at = NOW()
                WHERE barcode IN ({$bcListStr})
            ");
            $updRtsCount += $krfconn->affected_rows;
        }

        // 5. Insert history movement logs for each loaded tray
        $histStmt = $krfconn->prepare("
            INSERT INTO krf_traymovement_history (
                transaction_no,
                companyid,
                subcompanyid,
                projectid,
                psono,
                stockorder_no,
                productname,
                activity_type,
                barcode,
                from_polyhouse_id,
                from_bed_no,
                previous_status,
                current_status,
                live_plant_count,
                reason_for_movement,
                movement_date,
                movement_time,
                created_by,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'INTERNAL_DISPATCH', ?, ?, ?, ?, 'Final Dispatch', ?, ?, CURDATE(), CURTIME(), ?, NOW())
        ");

        $txNo = 'DISP-' . date('YmdHis') . '-' . rand(100, 999);
        $histRemark = 'Ready to Dispatch for Order ' . $psono . (!empty($productName) ? (' (' . $productName . ')') : '') . (!empty($driverNotes) ? (' | ' . $driverNotes) : '');

        if ($histStmt) {
            foreach ($loadedTrays as $tray) {
                $phId = (int)($tray['polyhouseid'] ?? 0);
                $bedNo = $tray['bed_no'] ?: '';
                $pName = $tray['productname'] ?: '';
                $soNo = $tray['stockorder_no'] ?: '';
                $bCode = $tray['barcode'] ?: '';
                $prevSt = $tray['status'] ?: 'Loaded';
                $plants = (int)($tray['qtypertray'] ?: 126);

                $histStmt->bind_param(
                    'siiissssississ',
                    $txNo,
                    $companyid,
                    $subcompanyid,
                    $projectid,
                    $psono,
                    $soNo,
                    $pName,
                    $bCode,
                    $phId,
                    $bedNo,
                    $prevSt,
                    $plants,
                    $histRemark,
                    $loginname
                );
                $histStmt->execute();
            }
            $histStmt->close();
        }

        // 6. Find related krf_readystock_allocation rows strictly as per product
        $rsaIds = [];
        $rsaProdFilterFind = "";
        if (!empty($escPId) && !empty($escPName)) {
            $rsaProdFilterFind = " AND (rsa.productid = '{$escPId}' OR rsa.productname = '{$escPName}')";
        } elseif (!empty($escPId)) {
            $rsaProdFilterFind = " AND rsa.productid = '{$escPId}'";
        } elseif (!empty($escPName)) {
            $rsaProdFilterFind = " AND rsa.productname = '{$escPName}'";
        }

        $findRsaStmt = $krfconn->prepare("
            SELECT rsa.id
            FROM krf_readystock_allocation rsa
            INNER JOIN krf_productionorder_consumed poc ON (
                (
                    rsa.stockorder_no = poc.stockorderno 
                    OR rsa.stockorder_no = poc.allocated_stockorderno
                    OR (NULLIF(TRIM(rsa.allocate_stockorder_no), '') IS NOT NULL AND (rsa.allocate_stockorder_no = poc.allocated_stockorderno OR rsa.allocate_stockorder_no = poc.stockorderno))
                )
                AND (rsa.productid = poc.product_id OR rsa.productname = poc.product_name)
                AND IFNULL(poc.tedit, '') = ''
                AND LOWER(IFNULL(rsa.status, '')) != 'cancel'
                AND (rsa.tedit IS NULL OR rsa.tedit = '')
            )
            LEFT JOIN krf_provisional_sales_order pso ON poc.psono = pso.psono
            WHERE poc.psono = ?
              AND (NULLIF(TRIM(rsa.clientid), '') IS NULL OR rsa.clientid = pso.clientid OR rsa.stockorder_no = poc.stockorderno OR rsa.stockorder_no = poc.allocated_stockorderno)
              {$rsaProdFilterFind}
        ");
        if ($findRsaStmt) {
            $findRsaStmt->bind_param('s', $psono);
            $findRsaStmt->execute();
            $rsaRes = $findRsaStmt->get_result();
            while ($rsaR = $rsaRes->fetch_assoc()) {
                if (!empty($rsaR['id'])) {
                    $rsaIds[] = (int)$rsaR['id'];
                }
            }
            $findRsaStmt->close();
        }

        // Fallback: Check via krf_sales_order_items if not found
        if (empty($rsaIds)) {
            $findRsaSoi = $krfconn->prepare("
                SELECT rsa.id
                FROM krf_readystock_allocation rsa
                INNER JOIN krf_sales_order_items soi ON (
                    (rsa.stockorder_no = soi.stockorder_no OR rsa.allocate_stockorder_no = soi.stockorder_no)
                    AND (rsa.productid = soi.product_id OR rsa.productname = soi.product_name)
                    AND (NULLIF(TRIM(rsa.clientid), '') IS NULL OR rsa.clientid = soi.clientid OR rsa.stockorder_no = soi.stockorder_no)
                )
                WHERE (soi.psono = ? OR soi.sono = ?)
                  AND LOWER(IFNULL(rsa.status, '')) != 'cancel'
                  AND (rsa.tedit IS NULL OR rsa.tedit = '')
                  {$rsaProdFilterFind}
            ");
            if ($findRsaSoi) {
                $findRsaSoi->bind_param('ss', $psono, $psono);
                $findRsaSoi->execute();
                $rsaRes2 = $findRsaSoi->get_result();
                while ($rsaR2 = $rsaRes2->fetch_assoc()) {
                    if (!empty($rsaR2['id'])) {
                        $rsaIds[] = (int)$rsaR2['id'];
                    }
                }
                $findRsaSoi->close();
            }
        }
        $rsaIds = array_values(array_unique(array_filter($rsaIds)));
        $rsaIdList = !empty($rsaIds) ? implode(',', $rsaIds) : '';

        $updConsumedCount = 0;
        $updSoiCount = 0;
        $updRsaCount = 0;
        $releasedTraysCount = 0;

        // -------------------------------------------------------------
        // 7. BRANCH EXECUTION: FULL DISPATCH vs PARTIAL DISPATCH
        // -------------------------------------------------------------

        if (!$isPartial) {
            // =========================================================
            // CASE 1: FULL DISPATCH (All trays loaded for this product)
            // =========================================================

            $pocProdCond = (!empty($escPId) && !empty($escPName))
                ? " AND (product_id = '{$escPId}' OR product_name = '{$escPName}')"
                : (!empty($escPId) ? " AND product_id = '{$escPId}'" : (!empty($escPName) ? " AND product_name = '{$escPName}'" : ""));

            $krfconn->query("
                UPDATE krf_productionorder_consumed 
                SET 
                    status = 'FullDispatch',
                    dispatchedqty = COALESCE(allocate_healthyplant, consumed_qty, 0),
                    dispatchedby = '{$loginname}',
                    dispatchedat = NOW(),
                    updated_at = NOW()
                WHERE (psono = '{$escPso}' OR (NULLIF(TRIM(sono), '') IS NOT NULL AND sono = '{$escPso}'))
                  AND IFNULL(tedit, '') = ''
                  {$pocProdCond}
            ");
            $updConsumedCount = $krfconn->affected_rows;

            // 2. Update krf_sales_order_items: status -> 'FullDispatch'
            $soiIdList = [];
            foreach ($itemsList as $it) {
                if (!empty($it['id'])) $soiIdList[] = (int)$it['id'];
            }
            $soiIdList = array_values(array_unique(array_filter($soiIdList)));
            $soiWhereClause = !empty($soiIdList) 
                ? ("soi.id IN (" . implode(',', $soiIdList) . ")") 
                : "((soi.psono = ? OR (NULLIF(TRIM(soi.sono), '') IS NOT NULL AND soi.sono = ?)) {$itemCondSoi})";

            $updSoiStmt = $krfconn->prepare("
                UPDATE krf_sales_order_items soi
                SET 
                    soi.dispatchedqty = COALESCE(soi.dispatchedqty, 0) + ?,
                    soi.status = CASE WHEN (COALESCE(soi.dispatchedqty, 0) + ?) >= soi.quantity THEN 'FullDispatch' ELSE 'PartialDispatch' END,
                    soi.actionstatus = CASE WHEN (COALESCE(soi.dispatchedqty, 0) + ?) >= soi.quantity THEN 'FullDispatch' ELSE 'PartialDispatch' END,
                    soi.ammendedby = ?,
                    soi.updateamended = NOW()
                WHERE {$soiWhereClause} 
                  AND LOWER(TRIM(COALESCE(soi.status, ''))) NOT IN ('cancelled', 'rejected')
            ");
            if ($updSoiStmt) {
                if (!empty($soiIdList)) {
                    $updSoiStmt->bind_param('iiis', $effectiveDispatchedPlants, $effectiveDispatchedPlants, $effectiveDispatchedPlants, $loginname);
                } else {
                    $updSoiStmt->bind_param('iiisss', $effectiveDispatchedPlants, $effectiveDispatchedPlants, $effectiveDispatchedPlants, $loginname, $psono, $psono);
                }
                $updSoiStmt->execute();
                $updSoiCount = $updSoiStmt->affected_rows;
                $updSoiStmt->close();
            }

            // 3. Update krf_readystock_allocation: status -> 'dispatched'
            if (!empty($rsaIdList)) {
                $krfconn->query("
                    UPDATE krf_readystock_allocation 
                    SET 
                        status = 'dispatched',
                        dispatchqty = {$effectiveDispatchedPlants},
                        updated_at = NOW(),
                        updated_by = '{$loginname}'
                    WHERE id IN ({$rsaIdList})
                ");
                $updRsaCount = $krfconn->affected_rows;
            }

            // 4. Update sales_invoice & sales_invoice_items: status -> 'dispatched'
            $invWhere = (!empty($escInv)) ? "invoiceno = '{$escInv}'" : "(psono = '{$escPso}' OR sono = '{$escPso}')";
            $krfconn->query("
                UPDATE sales_invoice_items 
                SET status = 'dispatched', updated_at = NOW() 
                WHERE {$invWhere} {$invItemProdCond}
            ");

            // Check if all items in invoice are now dispatched
            $chkRemainInv = $krfconn->query("
                SELECT COUNT(*) as cnt 
                FROM sales_invoice_items 
                WHERE {$invWhere} 
                  AND LOWER(TRIM(COALESCE(status, ''))) NOT IN ('dispatched', 'final dispatch', 'fulldispatch', 'cancelled', 'rejected')
            ");
            $remInvCount = ($chkRemainInv) ? (int)($chkRemainInv->fetch_assoc()['cnt'] ?? 0) : 0;
            $invStatusToSet = ($remInvCount === 0) ? 'dispatched' : 'readytodispatch';
            $krfconn->query("
                UPDATE sales_invoice 
                SET status = '{$invStatusToSet}', updated_by = '{$loginname}', updated_at = NOW() 
                WHERE {$invWhere}
            ");

            // Check if all items in overall sales order are now dispatched
            $chkRemainSo = $krfconn->query("
                SELECT COUNT(*) as cnt 
                FROM krf_sales_order_items 
                WHERE (psono = '{$escPso}' OR (NULLIF(TRIM(sono), '') IS NOT NULL AND sono = '{$escPso}'))
                  AND product_id != 'BOX_PACKING'
                  AND product_name NOT LIKE '%Box Packing Charges%'
                  AND LOWER(TRIM(COALESCE(status, ''))) NOT IN ('dispatched', 'final dispatch', 'fulldispatch', 'cancelled', 'rejected')
            ");
            $remSoCount = ($chkRemainSo) ? (int)($chkRemainSo->fetch_assoc()['cnt'] ?? 0) : 0;
            $salesOrderStatus = ($remSoCount === 0) ? 'FullDispatch' : 'PartialDispatch';

        } else if ($partialAction === 'keep_in_readytoload') {
            // =========================================================
            // CASE 2: PARTIAL DISPATCH - CHOICE 1: KEEP REST IN READY TO LOAD
            // Dispatches loaded portion (Trip 1) and keeps remaining balance open (Trip 2)
            // NO NEW ROW INSERTED - Modifies existing record in place
            // =========================================================
            $allocatedTotal = ($totalAllocatedPlants > 0) ? $totalAllocatedPlants : 1;

            foreach ($itemsList as $it) {
                $itemId = (int)$it['id'];
                $itemAlloc = (int)$it['calc_alloc_plants'];
                
                // Proportion of loaded plants for this line item
                if (count($itemsList) === 1) {
                    $itemLoaded = $actualLoadedPlants;
                    $itemRem    = $remainingPlants;
                } else {
                    $itemLoaded = min($itemAlloc, (int)round(($itemAlloc / $allocatedTotal) * $actualLoadedPlants));
                    $itemRem    = max(0, $itemAlloc - $itemLoaded);
                }

                // Update existing row in place (NO INSERT)
                $krfconn->query("
                    UPDATE krf_sales_order_items 
                    SET 
                        allocate_ordqty = {$itemRem},
                        healthyplant = {$itemRem},
                        dispatchedqty = COALESCE(dispatchedqty, 0) + {$itemLoaded},
                        status = 'PartialDispatch',
                        actionstatus = 'PartialLoaded',
                        ammendedby = '{$loginname}',
                        updateamended = NOW()
                    WHERE (id = {$itemId} OR id IN (SELECT COALESCE(original_soi_id, 0) FROM sales_invoice_items WHERE id = {$itemId}))
                ");
                $updSoiCount++;

                $pocId = (int)($it['poc_id'] ?? 0);
                if ($pocId > 0) {
                    $krfconn->query("
                        UPDATE krf_productionorder_consumed 
                        SET 
                            status = 'PartialDispatch',
                            consumed_qty = {$itemRem},
                            allocate_healthyplant = {$itemRem},
                            dispatchedqty = COALESCE(dispatchedqty, 0) + {$itemLoaded},
                            dispatchedby = '{$loginname}',
                            dispatchedat = NOW(),
                            updated_at = NOW()
                        WHERE id = {$pocId}
                          AND IFNULL(tedit, '') = ''
                    ");
                    $updConsumedCount++;
                }
            }

            // 2. Fallback: Update all rows for psono in krf_productionorder_consumed:
            // Status -> 'PartialDispatch'
            $krfconn->query("
                UPDATE krf_productionorder_consumed 
                SET 
                    status = 'PartialDispatch',
                    dispatchedby = '{$loginname}',
                    dispatchedat = NOW(),
                    updated_at = NOW()
                WHERE (psono = '{$escPso}' OR (NULLIF(TRIM(sono), '') IS NOT NULL AND sono = '{$escPso}'))
                  AND IFNULL(tedit, '') = ''
            ");
            if ($updConsumedCount === 0) {
                $updConsumedCount = $krfconn->affected_rows;
            }

            // 3. Update krf_readystock_allocation: status -> 'readytodispatch'
            if (!empty($rsaIdList)) {
                $krfconn->query("
                    UPDATE krf_readystock_allocation 
                    SET 
                        allocate_ordqty = {$remainingPlants},
                        healthyplant = {$remainingPlants},
                        dispatchqty = COALESCE(dispatchqty, 0) + {$actualLoadedPlants},
                        status = 'readytodispatch',
                        updated_at = NOW(),
                        updated_by = '{$loginname}'
                    WHERE id IN ({$rsaIdList})
                ");
                $updRsaCount = $krfconn->affected_rows;
            }

        } else {
            // =========================================================
            // CASE 3: PARTIAL DISPATCH - CHOICE 2: RELEASE TO READY STOCK
            // Unlinks remaining trays & frees allocation so other orders can take them
            // NO NEW ROW INSERTED - Finalizes existing item to actual loaded plants
            // =========================================================

            // 1. Find all unloaded trays for this PSO to log to history and release
            $qUnloaded = $krfconn->query("
                SELECT polyhouseplantid, barcode, polyhouseid, bed_no, productname, stockorder_no, qtypertray, status 
                FROM krf_polyhouse_plant_details 
                WHERE (psono = '{$escPso}' OR dispd_agst_psono = '{$escPso}')
                  AND status NOT IN ('Loaded', 'Final Dispatch', 'ReadyToDispatch', 'Removed', 'Empty After Shifting')
            ");
            if ($qUnloaded && $qUnloaded->num_rows > 0) {
                $relHistStmt = $krfconn->prepare("
                    INSERT INTO krf_traymovement_history (
                        transaction_no,
                        companyid,
                        subcompanyid,
                        projectid,
                        psono,
                        stockorder_no,
                        productname,
                        activity_type,
                        barcode,
                        from_polyhouse_id,
                        from_bed_no,
                        previous_status,
                        current_status,
                        live_plant_count,
                        reason_for_movement,
                        movement_date,
                        movement_time,
                        created_by,
                        created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'STOCK_RELEASE', ?, ?, ?, ?, 'Readytosale', ?, 'Released remaining balance back to ready stock', CURDATE(), CURTIME(), ?, NOW())
                ");

                while ($uTray = $qUnloaded->fetch_assoc()) {
                    $releasedTraysCount++;
                    if ($relHistStmt) {
                        $phId = (int)($uTray['polyhouseid'] ?? 0);
                        $bedNo = $uTray['bed_no'] ?: '';
                        $pName = $uTray['productname'] ?: '';
                        $soNo = $uTray['stockorder_no'] ?: '';
                        $bCode = $uTray['barcode'] ?: '';
                        $prevSt = $uTray['status'] ?: 'Readytosale';
                        $plants = (int)($uTray['qtypertray'] ?: 126);

                        $relHistStmt->bind_param(
                            'siiissssissis',
                            $txNo,
                            $companyid,
                            $subcompanyid,
                            $projectid,
                            $psono,
                            $soNo,
                            $pName,
                            $bCode,
                            $phId,
                            $bedNo,
                            $prevSt,
                            $plants,
                            $loginname
                        );
                        $relHistStmt->execute();
                    }
                }
                if ($relHistStmt) $relHistStmt->close();
            }

            // 2. Free unloaded trays in krf_polyhouse_plant_details
            $krfconn->query("
                UPDATE krf_polyhouse_plant_details 
                SET 
                    dispd_agst_psono = NULL,
                    dispd_agst_clientid = NULL,
                    dispd_agst_stockorderno = NULL,
                    status = 'Readytosale',
                    workingstatus = 'Readytosale',
                    movementremark = CONCAT_WS(' | ', NULLIF(movementremark, ''), 'Released back to stock from order {$escPso}'),
                    updated_at = NOW(),
                    updated_by = '{$loginname}'
                WHERE (psono = '{$escPso}' OR dispd_agst_psono = '{$escPso}')
                  AND status NOT IN ('Loaded', 'Final Dispatch', 'ReadyToDispatch', 'Removed', 'Empty After Shifting')
            ");

            // 3. Free unloaded trays in krf_readytosale_stocks
            $krfconn->query("
                UPDATE krf_readytosale_stocks 
                SET 
                    dispd_agst_psono = NULL,
                    dispd_agst_clientid = NULL,
                    dispd_agst_stockorderno = NULL,
                    status = 'Readytosale',
                    loaded_qty = 0,
                    dispatchqty = 0,
                    updated_at = NOW()
                WHERE (psono = '{$escPso}' OR dispd_agst_psono = '{$escPso}')
                  AND status NOT IN ('ReadyToDispatch', 'Final Dispatch')
            ");

            // 4. Update krf_sales_order_items: Finalize item to actual loaded quantity only
            $allocatedTotal = ($totalAllocatedPlants > 0) ? $totalAllocatedPlants : 1;
            foreach ($itemsList as $it) {
                $itemId = (int)$it['id'];
                $itemAlloc = (int)$it['calc_alloc_plants'];
                
                if (count($itemsList) === 1) {
                    $itemLoaded = $actualLoadedPlants;
                } else {
                    $itemLoaded = min($itemAlloc, (int)round(($itemAlloc / $allocatedTotal) * $actualLoadedPlants));
                }

                $qOrig = $krfconn->query("SELECT rate, cgst_percent, sgst_percent, igst_percent FROM krf_sales_order_items WHERE id = {$itemId} LIMIT 1");
                $rate = 0; $cgstP = 0; $sgstP = 0; $igstP = 0;
                if ($qOrig && $oR = $qOrig->fetch_assoc()) {
                    $rate = (float)($oR['rate'] ?: 0);
                    $cgstP = (float)($oR['cgst_percent'] ?: 0);
                    $sgstP = (float)($oR['sgst_percent'] ?: 0);
                    $igstP = (float)($oR['igst_percent'] ?: 0);
                }
                $dispTaxable = round($rate * $itemLoaded, 2);
                $dispCgstA = round(($dispTaxable * $cgstP) / 100, 2);
                $dispSgstA = round(($dispTaxable * $sgstP) / 100, 2);
                $dispIgstA = round(($dispTaxable * $igstP) / 100, 2);
                $dispTaxA = $dispCgstA + $dispSgstA + $dispIgstA;
                $dispTotAmt = round($dispTaxable + $dispTaxA, 2);

                $krfconn->query("
                    UPDATE krf_sales_order_items 
                    SET 
                        quantity = {$itemLoaded},
                        allocate_ordqty = {$itemLoaded},
                        healthyplant = {$itemLoaded},
                        dispatchedqty = {$itemLoaded},
                        status = 'PartialDispatch',
                        actionstatus = 'ReadyToDispatch',
                        taxableamount = {$dispTaxable},
                        cgst_amount = {$dispCgstA},
                        sgst_amount = {$dispSgstA},
                        igst_amount = {$dispIgstA},
                        tax_amount = {$dispTaxA},
                        amount = {$dispTotAmt},
                        ammendedby = '{$loginname}',
                        updateamended = NOW()
                    WHERE (id = {$itemId} OR id IN (SELECT COALESCE(original_soi_id, 0) FROM sales_invoice_items WHERE id = {$itemId}))
                ");
                $updSoiCount++;

                $pocId = (int)($it['poc_id'] ?? 0);
                if ($pocId > 0) {
                    $krfconn->query("
                        UPDATE krf_productionorder_consumed 
                        SET 
                            status = 'PartialDispatch',
                            consumed_qty = {$itemLoaded},
                            allocate_healthyplant = {$itemLoaded},
                            dispatchedqty = {$itemLoaded},
                            dispatchedby = '{$loginname}',
                            dispatchedat = NOW(),
                            updated_at = NOW()
                        WHERE id = {$pocId}
                          AND IFNULL(tedit, '') = ''
                    ");
                    $updConsumedCount++;
                }
            }

            // 5. Update krf_productionorder_consumed:
            // Status -> 'PartialDispatch'
            // Reduce consumed_qty & allocate_healthyplant to exact loaded plants
            // This immediately frees the remaining batch quantity in timetable_finaldispatch.php!
            $krfconn->query("
                UPDATE krf_productionorder_consumed 
                SET 
                    status = 'PartialDispatch',
                    dispatchedby = '{$loginname}',
                    dispatchedat = NOW(),
                    updated_at = NOW()
                WHERE (psono = '{$escPso}' OR (NULLIF(TRIM(sono), '') IS NOT NULL AND sono = '{$escPso}'))
                  AND IFNULL(tedit, '') = ''
            ");
            if ($updConsumedCount === 0) {
                $updConsumedCount = $krfconn->affected_rows;
            }

            // 6. Update krf_readystock_allocation: status -> 'readytodispatch'
            if (!empty($rsaIdList)) {
                $krfconn->query("
                    UPDATE krf_readystock_allocation 
                    SET 
                        status = 'readytodispatch',
                        allocate_ordqty = {$actualLoadedPlants},
                        healthyplant = {$actualLoadedPlants},
                        dispatchqty = {$actualLoadedPlants},
                        updated_at = NOW(),
                        updated_by = '{$loginname}'
                    WHERE id IN ({$rsaIdList})
                ");
                $updRsaCount = $krfconn->affected_rows;
            }
        }

        // -------------------------------------------------------------
        // 8. APPEND REMARKS & UPDATE STATUS IN krf_sales_order & krf_production_stockorder
        // -------------------------------------------------------------
        $timestampStr = date('d-M-Y H:i');
        if (!$isPartial) {
            $remarkPrefix = "Full Dispatch ({$loadedTraysCount} Trays / {$actualLoadedPlants} Plants Loaded)";
        } else if ($partialAction === 'keep_in_readytoload') {
            $remarkPrefix = "Partial Dispatch ({$loadedTraysCount}/{$totalAllocatedTrays} Trays Loaded - Remaining {$remainingTrays} Trays / {$remainingPlants} Plants kept in Ready to Load for next trip)";
        } else {
            $remarkPrefix = "Partial Dispatch ({$loadedTraysCount}/{$totalAllocatedTrays} Trays Loaded - Remaining {$remainingTrays} Trays / {$remainingPlants} Plants released back to ready stock for re-allocation)";
        }

        $remarkBody = !empty($driverNotes) ? ($remarkPrefix . ' | ' . $driverNotes) : $remarkPrefix;
        $formattedRemark = "[{$timestampStr}] {$loginname}: {$remarkBody}";
        $escRemark = mysqli_real_escape_string($krfconn, $formattedRemark);

        $soNumbers = [];
        foreach ($itemsList as $it) {
            if (!empty($it['sono'])) {
                $soNumbers[] = trim($it['sono']);
            }
        }
        $soNumbers = array_values(array_unique(array_filter($soNumbers)));
        $soCond = !empty($soNumbers) ? (" OR sono IN ('" . implode("','", array_map(function($s) use ($krfconn) { return mysqli_real_escape_string($krfconn, $s); }, $soNumbers)) . "')") : "";

        // Update krf_sales_order: status -> 'PartialDispatch' or 'FullDispatch'
        $krfconn->query("
            UPDATE krf_sales_order 
            SET 
                status = '{$salesOrderStatus}',
                remarks = CONCAT_WS('\n', NULLIF(TRIM(remarks), ''), '{$escRemark}'),
                updated_at = NOW(),
                updated_by = '{$loginname}'
            WHERE psono = '{$escPso}' OR (NULLIF(TRIM(sono), '') IS NOT NULL AND sono = '{$escPso}') {$soCond}
        ");

        // Find all related stockorder_no values to update in krf_production_stockorder
        $relatedStockOrders = [];
        foreach ($loadedTrays as $lt) {
            if (!empty($lt['stockorder_no'])) {
                $relatedStockOrders[] = trim($lt['stockorder_no']);
            }
        }
        $qSoiStk = $krfconn->query("SELECT DISTINCT stockorder_no FROM krf_sales_order_items WHERE psono = '{$escPso}' AND NULLIF(TRIM(stockorder_no), '') IS NOT NULL");
        if ($qSoiStk) {
            while ($r = $qSoiStk->fetch_assoc()) {
                $relatedStockOrders[] = trim($r['stockorder_no']);
            }
        }
        $qPocStk = $krfconn->query("SELECT DISTINCT stockorderno, allocated_stockorderno FROM krf_productionorder_consumed WHERE psono = '{$escPso}' AND IFNULL(tedit, '') = ''");
        if ($qPocStk) {
            while ($r = $qPocStk->fetch_assoc()) {
                if (!empty($r['stockorderno'])) $relatedStockOrders[] = trim($r['stockorderno']);
                if (!empty($r['allocated_stockorderno'])) $relatedStockOrders[] = trim($r['allocated_stockorderno']);
            }
        }
        $relatedStockOrders = array_values(array_unique(array_filter($relatedStockOrders)));

        if (!empty($relatedStockOrders)) {
            $stkList = "'" . implode("','", array_map(function($s) use ($krfconn) {
                return mysqli_real_escape_string($krfconn, $s);
            }, $relatedStockOrders)) . "'";

            $krfconn->query("
                UPDATE krf_production_stockorder 
                SET 
                    remarks = CONCAT_WS('\n', NULLIF(TRIM(remarks), ''), '{$escRemark}'),
                    updated_at = NOW()
                WHERE stockorder_no IN ({$stkList}) OR psono = '{$escPso}'
            ");
        } else {
            $krfconn->query("
                UPDATE krf_production_stockorder 
                SET 
                    remarks = CONCAT_WS('\n', NULLIF(TRIM(remarks), ''), '{$escRemark}'),
                    updated_at = NOW()
                WHERE psono = '{$escPso}'
            ");
        }

        // Ensure sales_invoice & sales_invoice_items status is updated to 'dispatched' for this trip (per product)
        $invWhereFinal = (!empty($escInv)) ? "invoiceno = '{$escInv}'" : "(psono = '{$escPso}' OR sono = '{$escPso}')";
        $krfconn->query("
            UPDATE sales_invoice_items 
            SET status = 'dispatched', updated_at = NOW() 
            WHERE {$invWhereFinal} {$invItemProdCond}
        ");

        $chkRemainInvFinal = $krfconn->query("
            SELECT COUNT(*) as cnt 
            FROM sales_invoice_items 
            WHERE {$invWhereFinal} 
              AND LOWER(TRIM(COALESCE(status, ''))) NOT IN ('dispatched', 'final dispatch', 'fulldispatch', 'cancelled', 'rejected')
        ");
        $remInvCountFinal = ($chkRemainInvFinal) ? (int)($chkRemainInvFinal->fetch_assoc()['cnt'] ?? 0) : 0;
        $invFinalStatus = ($remInvCountFinal === 0) ? 'dispatched' : 'readytodispatch';
        $krfconn->query("
            UPDATE sales_invoice 
            SET status = '{$invFinalStatus}', updated_by = '{$loginname}', updated_at = NOW() 
            WHERE {$invWhereFinal}
        ");

        $krfconn->commit();

        $successMessage = !$isPartial 
            ? "Order {$psono} status updated to Dispatched (Full Dispatch: {$loadedTraysCount} trays / {$actualLoadedPlants} plants) successfully!"
            : ($partialAction === 'keep_in_readytoload'
                ? "Partial Dispatch confirmed for {$loadedTraysCount} tray(s) ({$actualLoadedPlants} plants)! The remaining {$remainingTrays} tray(s) ({$remainingPlants} plants) remain open in Ready to Load for the next trip."
                : "Partial Dispatch confirmed for {$loadedTraysCount} tray(s) ({$actualLoadedPlants} plants)! The remaining {$remainingTrays} tray(s) ({$remainingPlants} plants) have been released back to Ready Stock and are now available for re-allocation.");

        outJson([
            'status'                  => 'success',
            'message'                 => $successMessage,
            'sales_order_status'      => $salesOrderStatus,
            'partial_action'          => $partialAction,
            'is_partial'              => $isPartial,
            'loaded_trays'            => $loadedTraysCount,
            'loaded_plants'           => $actualLoadedPlants,
            'allocated_trays'         => $totalAllocatedTrays,
            'allocated_plants'        => $totalAllocatedPlants,
            'remaining_trays'         => $remainingTrays,
            'remaining_plants'        => $remainingPlants,
            'released_trays_count'    => $releasedTraysCount,
            'polyhouse_trays_updated' => count($loadedTrays),
            'consumed_updated'        => $updConsumedCount,
            'so_items_updated'        => $updSoiCount,
            'readytosale_updated'     => $updRtsCount,
            'readystock_allocation_updated' => $updRsaCount,
            'batch_id'                => $txNo
        ]);

    } catch (Exception $ex) {
        $krfconn->rollback();
        outJson(['status' => 'error', 'message' => 'Failed to update order status: ' . $ex->getMessage()]);
    }
}

// -------------------------------------------------------------
// 5. ACTION: REVERT ORDER ACTION (Revert Dispatched/Loaded to Ready to Load)
// -------------------------------------------------------------
if ($action === 'revert_order_action') {
    if ($loginroleid >= 2) {
        outJson(['status' => 'error', 'message' => 'Permission Denied: Only Administrator (loginroleid < 2) can revert order actions.']);
    }

    $psono = trim($_REQUEST['psono'] ?? '');
    $invoiceno = trim($_REQUEST['invoiceno'] ?? '');

    if (empty($psono) && empty($invoiceno)) {
        outJson(['status' => 'error', 'message' => 'PSO Number or Invoice Number is required to revert.']);
    }

    $escPso = mysqli_real_escape_string($krfconn, $psono);
    $escInv = mysqli_real_escape_string($krfconn, $invoiceno);

    $krfconn->begin_transaction();
    try {
        // 1. Revert sales_invoice & sales_invoice_items
        if (!empty($escInv)) {
            $krfconn->query("UPDATE sales_invoice SET status = 'readytoload' WHERE invoiceno = '{$escInv}'");
            $krfconn->query("UPDATE sales_invoice_items SET status = 'readytoload' WHERE invoiceno = '{$escInv}' AND productid != 'BOX_PACKING'");
        }

        // 2. Revert krf_sales_order_items & krf_polyhouse_plant_details
        if (!empty($escPso)) {
            $krfconn->query("UPDATE sales_invoice_items SET status = 'readytoload' WHERE psono = '{$escPso}' AND productid != 'BOX_PACKING'");
            $krfconn->query("UPDATE krf_sales_order_items SET status = 'readytoload', actionstatus = 'readytoload' WHERE psono = '{$escPso}'");
            
            // Revert Final Dispatch trays back to Loaded
            $krfconn->query("UPDATE krf_polyhouse_plant_details SET status = 'Loaded' WHERE (psono = '{$escPso}' OR dispd_agst_psono = '{$escPso}') AND status IN ('Final Dispatch', 'Dispatched')");
            $krfconn->query("UPDATE krf_readytosale_stocks SET status = 'Loaded' WHERE (psono = '{$escPso}' OR dispd_agst_psono = '{$escPso}') AND status IN ('Final Dispatch', 'Dispatched')");
        }

        // 3. Log history
        $targetIdent = !empty($escPso) ? $escPso : $escInv;
        $krfconn->query("
            INSERT INTO krf_traymovement_history 
            (barcode, from_location, to_location, from_status, to_status, moved_by, moved_at, remark)
            VALUES 
            ('{$targetIdent}', 'Updated Order', 'readytoload', 'reverted', 'readytoload', '{$loginname}', NOW(), 'Order action reverted to Ready to Load by {$loginname}')
        ");

        $krfconn->commit();
        outJson([
            'status'  => 'success',
            'message' => "Order {$psono} {$invoiceno} status reverted back to Ready to Load."
        ]);
    } catch (Throwable $e) {
        $krfconn->rollback();
        outJson([
            'status'  => 'error',
            'message' => 'Failed to revert order action: ' . $e->getMessage()
        ]);
    }
}

// -------------------------------------------------------------
// 6. ACTION: TOGGLE TEST ITEM AS 'readytoload' (Developer Helper)
// -------------------------------------------------------------
if ($action === 'set_test_readytoload') {
    $itemId = (int)($_REQUEST['item_id'] ?? 0);
    $newStatus = trim($_REQUEST['new_status'] ?? 'readytoload');

    if ($itemId <= 0) {
        // Pick the top item to test
        $res = $krfconn->query("SELECT id, psono, product_name, status FROM krf_sales_order_items WHERE psono IS NOT NULL AND psono != '' LIMIT 1");
        if ($res && $row = $res->fetch_assoc()) {
            $itemId = (int)$row['id'];
        }
    }

    if ($itemId > 0) {
        $safeSt = mysqli_real_escape_string($krfconn, $newStatus);
        $krfconn->query("UPDATE krf_sales_order_items SET status = '{$safeSt}' WHERE id = {$itemId}");
        outJson([
            'status' => 'success',
            'message' => "Order item #{$itemId} status set to '{$newStatus}'.",
            'item_id' => $itemId
        ]);
    } else {
        outJson(['status' => 'error', 'message' => 'No order item found to update.']);
    }
}

// Default fallback
outJson(['status' => 'error', 'message' => 'Invalid action: ' . htmlspecialchars($action)]);
