<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once(__DIR__ . '/../../connection.php');
if (isset($krfconn) && $krfconn) {
    @$krfconn->set_charset("utf8mb4");
}
require_once(__DIR__ . '/../../loginCell/functions/login_functions.php');
@include_once(__DIR__ . "/../../whatsappnotification/whatsappsmsfunctions.php");
date_default_timezone_set("Asia/Calcutta");

$action = $_POST['action'] ?? $_GET['action'] ?? '';

header('Content-Type: application/json; charset=utf-8');

if (!function_exists('cleanVal')) {
    function cleanVal($val) {
        return $val !== null ? trim(strval($val)) : '';
    }
}

// -------------------------------------------------------------
// 1. AUTOCOMPLETE / SEARCH CLIENTS
// -------------------------------------------------------------
if ($action == "SearchClients") {
    $term = trim($_POST['searchTerm'] ?? $_POST['q'] ?? $_GET['q'] ?? '');
    $projectid = intval($_POST['projectid'] ?? 0);
    
    $clients = [];
    $seenKeys = [];

    if (strlen($term) < 1) {
        // Return recent clients from PSO and Client Master
        $sql = "
            SELECT DISTINCT 
                pso.clientid AS clientid,
                pso.clientname AS clientname,
                pso.mobileno AS mobileno,
                pso.districtname AS districtname,
                pso.statename AS statename
            FROM krf_provisional_sales_order pso
            WHERE (pso.projectid = ? OR ? = 0)
              AND (pso.mobileno IS NOT NULL AND pso.mobileno != '')
            ORDER BY pso.id DESC
            LIMIT 20
        ";
        if ($stmt = $krfconn->prepare($sql)) {
            $stmt->bind_param("ii", $projectid, $projectid);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $mob = trim($row['mobileno'] ?? '');
                $cid = trim($row['clientid'] ?? '');
                $key = $mob ?: $cid;
                if (empty($key) || isset($seenKeys[$key])) continue;
                $seenKeys[$key] = true;

                $clients[] = [
                    'id' => $mob ?: $cid,
                    'clientid' => $cid,
                    'clientname' => $row['clientname'] ?: 'Client',
                    'mobileno' => $mob,
                    'location' => implode(', ', array_filter([$row['districtname'] ?? '', $row['statename'] ?? ''])),
                    'text' => ($row['clientname'] ?: 'Client') . ' (' . ($mob ?: 'No Mobile') . ')' . ($row['districtname'] ? ' - ' . $row['districtname'] : '')
                ];
            }
        }
    } else {
        $searchLike = "%{$term}%";
        
        // Search in Client Master
        $cmSql = "
            SELECT 
                client_id AS clientid,
                client_name AS clientname,
                phones AS mobileno,
                districtname,
                statename
            FROM krf_client_master
            WHERE phones LIKE ? OR client_name LIKE ? OR client_id LIKE ?
            LIMIT 25
        ";
        if ($stmt = $krfconn->prepare($cmSql)) {
            $stmt->bind_param("sss", $searchLike, $searchLike, $searchLike);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $mob = trim($row['mobileno'] ?? '');
                $cid = trim($row['clientid'] ?? '');
                $key = $mob ?: $cid;
                if (empty($key) || isset($seenKeys[$key])) continue;
                $seenKeys[$key] = true;

                $clients[] = [
                    'id' => $mob ?: $cid,
                    'clientid' => $cid,
                    'clientname' => $row['clientname'] ?: 'Client',
                    'mobileno' => $mob,
                    'location' => implode(', ', array_filter([$row['districtname'] ?? '', $row['statename'] ?? ''])),
                    'text' => ($row['clientname'] ?: 'Client') . ' (' . ($mob ?: 'No Mobile') . ')' . ($row['districtname'] ? ' - ' . $row['districtname'] : '')
                ];
            }
        }

        // Search in Provisional Sales Orders
        $psoSql = "
            SELECT DISTINCT
                clientid,
                clientname,
                mobileno,
                districtname,
                statename
            FROM krf_provisional_sales_order
            WHERE mobileno LIKE ? OR clientname LIKE ? OR clientid LIKE ? OR psono LIKE ?
            LIMIT 25
        ";
        if ($stmt = $krfconn->prepare($psoSql)) {
            $stmt->bind_param("ssss", $searchLike, $searchLike, $searchLike, $searchLike);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $mob = trim($row['mobileno'] ?? '');
                $cid = trim($row['clientid'] ?? '');
                $key = $mob ?: $cid;
                if (empty($key) || isset($seenKeys[$key])) continue;
                $seenKeys[$key] = true;

                $clients[] = [
                    'id' => $mob ?: $cid,
                    'clientid' => $cid,
                    'clientname' => $row['clientname'] ?: 'Client',
                    'mobileno' => $mob,
                    'location' => implode(', ', array_filter([$row['districtname'] ?? '', $row['statename'] ?? ''])),
                    'text' => ($row['clientname'] ?: 'Client') . ' (' . ($mob ?: 'No Mobile') . ')' . ($row['districtname'] ? ' - ' . $row['districtname'] : '')
                ];
            }
        }

        // Search in Advance Payments
        $aptSql = "
            SELECT DISTINCT
                clientid,
                clientname,
                invoiceno
            FROM advance_payment_transactions
            WHERE clientname LIKE ? OR clientid LIKE ? OR invoiceno LIKE ?
            LIMIT 25
        ";
        if ($stmt = $krfconn->prepare($aptSql)) {
            $stmt->bind_param("sss", $searchLike, $searchLike, $searchLike);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $cid = trim($row['clientid'] ?? '');
                $cname = trim($row['clientname'] ?? '');
                $key = $cid ?: $cname;
                if (empty($key) || isset($seenKeys[$key])) continue;
                $seenKeys[$key] = true;

                $clients[] = [
                    'id' => $cid ?: $cname,
                    'clientid' => $cid,
                    'clientname' => $cname ?: 'Client',
                    'mobileno' => '',
                    'location' => '',
                    'text' => ($cname ?: 'Client') . ' (ID: ' . ($cid ?: '-') . ')'
                ];
            }
        }
    }
    
    echo json_encode(['status' => 'success', 'results' => $clients]);
    exit;
}

// -------------------------------------------------------------
// 2. GET CLIENT FULL 360° PAYMENT & ORDER LIFECYCLE HISTORY
// -------------------------------------------------------------
if ($action == "GetClientFullHistory") {
    try {
        $searchKey = trim($_POST['searchKey'] ?? $_POST['clientNumber'] ?? $_GET['searchKey'] ?? '');
        $projectid = intval($_POST['projectid'] ?? 0);
        
        if (empty($searchKey)) {
            echo json_encode(['status' => 'error', 'message' => 'Please enter a client mobile number, name, or order ID to search.']);
            exit;
        }
    
    // Step 1: Identify Client Profile
    $clientProfile = null;
    $searchLike = "%{$searchKey}%";
    
    // Check in krf_client_master first
    $clientSql = "
        SELECT 
            client_id, client_name, phones, client_email, 
            statename, districtname, invoicing_address, vat, date_time
        FROM krf_client_master
        WHERE phones = ? OR client_id = ? OR phones LIKE ? OR client_name LIKE ?
        LIMIT 1
    ";
    if ($cStmt = $krfconn->prepare($clientSql)) {
        $cStmt->bind_param("ssss", $searchKey, $searchKey, $searchLike, $searchLike);
        $cStmt->execute();
        $cRes = $cStmt->get_result();
        if ($cRow = $cRes->fetch_assoc()) {
            $clientProfile = [
                'clientid' => $cRow['client_id'],
                'clientname' => $cRow['client_name'],
                'mobileno' => $cRow['phones'],
                'alternateno' => '',
                'email' => $cRow['client_email'],
                'statename' => $cRow['statename'],
                'districtname' => $cRow['districtname'],
                'address' => $cRow['invoicing_address'],
                'gstno' => $cRow['vat'],
                'created_at' => $cRow['date_time']
            ];
        }
    }
    
    // If not found in client master, check in krf_provisional_sales_order
    if (!$clientProfile) {
        $psoSearchSql = "
            SELECT 
                clientid, clientname, mobileno, statename, districtname, delivery_address, address, emailid, created_at
            FROM krf_provisional_sales_order
            WHERE mobileno = ? OR clientid = ? OR psono = ? OR mobileno LIKE ? OR clientname LIKE ?
            ORDER BY id DESC LIMIT 1
        ";
        if ($pStmt = $krfconn->prepare($psoSearchSql)) {
            $pStmt->bind_param("sssss", $searchKey, $searchKey, $searchKey, $searchLike, $searchLike);
            $pStmt->execute();
            $pRes = $pStmt->get_result();
            if ($pRow = $pRes->fetch_assoc()) {
                $clientProfile = [
                    'clientid' => $pRow['clientid'],
                    'clientname' => $pRow['clientname'],
                    'mobileno' => $pRow['mobileno'],
                    'alternateno' => '',
                    'email' => $pRow['emailid'],
                    'statename' => $pRow['statename'],
                    'districtname' => $pRow['districtname'],
                    'address' => $pRow['delivery_address'] ?: $pRow['address'],
                    'gstno' => '',
                    'created_at' => $pRow['created_at']
                ];
            }
        }
    }
    
    // If still not found, check in advance_payment_transactions
    if (!$clientProfile) {
        $aptSearchSql = "
            SELECT clientid, clientname, paymentdate
            FROM advance_payment_transactions
            WHERE invoiceno = ? OR clientid = ? OR clientname LIKE ?
            ORDER BY sno DESC LIMIT 1
        ";
        if ($aStmt = $krfconn->prepare($aptSearchSql)) {
            $aStmt->bind_param("sss", $searchKey, $searchKey, $searchLike);
            $aStmt->execute();
            $aRes = $aStmt->get_result();
            if ($aRow = $aRes->fetch_assoc()) {
                $clientProfile = [
                    'clientid' => $aRow['clientid'],
                    'clientname' => $aRow['clientname'],
                    'mobileno' => '',
                    'alternateno' => '',
                    'email' => '',
                    'statename' => '',
                    'districtname' => '',
                    'address' => '',
                    'gstno' => '',
                    'created_at' => $aRow['paymentdate']
                ];
            }
        }
    }
    
    if (!$clientProfile) {
        echo json_encode([
            'status' => 'not_found',
            'message' => "No client or order records found matching '{$searchKey}'."
        ]);
        exit;
    }
    
    $clientId = cleanVal($clientProfile['clientid'] ?? '');
    $clientMobile = cleanVal($clientProfile['mobileno'] ?? '');
    $clientName = cleanVal($clientProfile['clientname'] ?? '');
    
    // -------------------------------------------------------------
    // STEP 2: Fetch All Provisional Sales Orders (PSO) for this Client
    // -------------------------------------------------------------
    $psoList = [];
    $allPsonoNumbers = [];
    
    $psoQuery = "
        SELECT 
            pso.id,
            pso.psono,
            pso.clientid,
            pso.clientname,
            pso.mobileno,
            pso.address,
            pso.delivery_address,
            pso.statename,
            pso.districtname,
            pso.pickupfrom,
            pso.created_at,
            pso.created_by,
            pso.expecteddelivery,
            pso.status AS pso_status,
            pso.bookingno,
            pso.bookingfilepath,
            pso.remarks AS pso_remarks,
            DATE_FORMAT(pso.created_at, '%d-%b-%Y') AS formatted_booking_date,
            DATE_FORMAT(pso.expecteddelivery, '%d-%b-%Y') AS formatted_delivery_date
        FROM krf_provisional_sales_order pso
        WHERE (
            (pso.clientid = ? AND ? != '') 
            OR (pso.mobileno = ? AND ? != '') 
            OR (pso.clientname = ? AND ? != '')
            OR (pso.psono = ?)
        )
        ORDER BY pso.id DESC
    ";
    if ($pStmt = $krfconn->prepare($psoQuery)) {
        $pStmt->bind_param("sssssss", $clientId, $clientId, $clientMobile, $clientMobile, $clientName, $clientName, $searchKey);
        $pStmt->execute();
        $psoRes = $pStmt->get_result();
        
        while ($pRow = $psoRes->fetch_assoc()) {
            $psono = trim($pRow['psono'] ?? '');
            if ($psono) {
                $psoList[$psono] = $pRow;
                $allPsonoNumbers[] = $psono;
            }
        }
    }
    
    // Fetch confirmed sales orders from krf_sales_order
    $confirmedSalesOrders = [];
    $soQuery = "
        SELECT 
            so.id,
            so.sono,
            so.psono,
            so.clientid,
            so.clientname,
            so.mobileno,
            so.delivery_address,
            so.statename,
            so.districtname,
            so.created_at,
            so.total_amount,
            so.grand_total,
            so.status AS so_status,
            DATE_FORMAT(so.created_at, '%d-%b-%Y') AS formatted_so_date
        FROM krf_sales_order so
        WHERE (
            (so.clientid = ? AND ? != '') 
            OR (so.mobileno = ? AND ? != '') 
            OR (so.clientname = ? AND ? != '')
            OR (so.sono = ?)
            OR (so.psono = ?)
        )
        ORDER BY so.id DESC
    ";
    if ($sStmt = $krfconn->prepare($soQuery)) {
        $sStmt->bind_param("ssssssss", $clientId, $clientId, $clientMobile, $clientMobile, $clientName, $clientName, $searchKey, $searchKey);
        $sStmt->execute();
        $soRes = $sStmt->get_result();
        
        while ($soRow = $soRes->fetch_assoc()) {
            $psonoRef = trim($soRow['psono'] ?? '');
            $sono = trim($soRow['sono'] ?? '');
            if ($psonoRef && !in_array($psonoRef, $allPsonoNumbers)) {
                $allPsonoNumbers[] = $psonoRef;
            }
            $confirmedSalesOrders[$psonoRef ?: $sono] = $soRow;
        }
    }
    
    // -------------------------------------------------------------
    // STEP 3: Fetch Order Items for All PSONOs
    // -------------------------------------------------------------
    $psoItemsMap = [];
    if (!empty($allPsonoNumbers)) {
        $inClause = implode("','", array_map([$krfconn, 'real_escape_string'], $allPsonoNumbers));
        $itemSql = "
            SELECT 
                psoi.id,
                psoi.psono,
                psoi.product_id,
                psoi.product_name,
                psoi.quantity,
                psoi.rate,
                psoi.amount,
                psoi.stockorder_no,
                psoi.status AS item_status,
                psoi.expecteddeliverydate,
                DATE_FORMAT(psoi.expecteddeliverydate, '%d-%b-%Y') AS formatted_delivery_date
            FROM krf_provisional_sales_order_items psoi
            WHERE psoi.psono IN ('{$inClause}')
            ORDER BY psoi.id ASC
        ";
        $iRes = $krfconn->query($itemSql);
        if ($iRes) {
            while ($iRow = $iRes->fetch_assoc()) {
                $psoKey = trim($iRow['psono'] ?? '');
                if (!isset($psoItemsMap[$psoKey])) $psoItemsMap[$psoKey] = [];
                $psoItemsMap[$psoKey][] = $iRow;
            }
        }
    }
    
    // -------------------------------------------------------------
    // STEP 4: Fetch Advance Payments from `advance_payment_transactions`
    // -------------------------------------------------------------
    // Cache user names map from authconn (maps loginid and mobileno to loginname)
    $userMap = [];
    if (isset($authconn) && $authconn) {
        $uRes = @$authconn->query("SELECT loginid, loginname, mobileno FROM login_user_master");
        if ($uRes) {
            while ($uRow = $uRes->fetch_assoc()) {
                $lid = trim($uRow['loginid'] ?? '');
                $lname = trim($uRow['loginname'] ?? '');
                $mob = trim($uRow['mobileno'] ?? '');
                if ($lid !== '' && $lname !== '') {
                    $userMap[$lid] = $lname;
                    $userMap[strtolower($lid)] = $lname;
                }
                if ($mob !== '' && $lname !== '') {
                    $userMap[$mob] = $lname;
                }
            }
        }
    }

    $paymentsByOrder = [];
    $allClientPayments = [];
    $totalAdvancePaidLifetime = 0;
    
    $whereApt = "
        (
            (apt.clientid = '" . $krfconn->real_escape_string($clientId) . "' AND '" . $krfconn->real_escape_string($clientId) . "' != '') 
            OR (apt.clientname = '" . $krfconn->real_escape_string($clientName) . "' AND '" . $krfconn->real_escape_string($clientName) . "' != '')
    ";
    if (!empty($allPsonoNumbers)) {
        $inPso = implode("','", array_map([$krfconn, 'real_escape_string'], $allPsonoNumbers));
        $whereApt .= " OR apt.invoiceno IN ('{$inPso}') ";
    }
    $whereApt .= ")";
    
    $aptSql = "
        SELECT 
            apt.sno,
            apt.companyid,
            apt.projectid,
            apt.clientid,
            apt.clientname,
            apt.advanceamount,
            apt.utrnumber,
            apt.paymentdate,
            apt.purposeofpayment,
            apt.invoiceno,
            apt.sourceofpayment,
            apt.paymentref_no,
            apt.receivedby,
            apt.receivedlocation,
            apt.receiptpath,
            apt.remarks,
            apt.allocated_datetime,
            apt.allocatedby,
            apt.ledgerstatus,
            COALESCE(apt.verification, 'unverified') AS verification,
            apt.verificationby,
            apt.verificationat,
            DATE_FORMAT(apt.paymentdate, '%d-%b-%Y') AS formatted_payment_date,
            DATE_FORMAT(apt.allocated_datetime, '%d-%b-%Y %H:%i') AS formatted_created_at
        FROM advance_payment_transactions apt
        WHERE {$whereApt}
        ORDER BY apt.paymentdate DESC, apt.sno DESC
    ";
    $aptRes = $krfconn->query($aptSql);
    if ($aptRes) {
        while ($pRow = $aptRes->fetch_assoc()) {
            $amt = (float)($pRow['advanceamount'] ?? 0);
            $invNo = trim($pRow['invoiceno'] ?? '');
            
            // Resolve recorded/account user name (in which account/ID payment was recorded)
            $rawAllocated = trim($pRow['allocatedby'] ?? '');
            $rawReceived = trim($pRow['receivedby'] ?? '');
            $rawVerification = trim($pRow['verificationby'] ?? '');
            
            // Priority 1: The account user whose login ID recorded this transaction
            $recordedAccountUser = $userMap[$rawAllocated] ?? $userMap[strtolower($rawAllocated)] ?? '';
            
            // Priority 2: Check if receivedby resolves in user master
            $receivedAccountUser = $userMap[$rawReceived] ?? $userMap[strtolower($rawReceived)] ?? '';
            
            // Priority 3: Other resolution or direct values
            $resolvedVerification = $userMap[$rawVerification] ?? $userMap[strtolower($rawVerification)] ?? $rawVerification;

            $finalReceivedName = $recordedAccountUser ?: ($receivedAccountUser ?: ($rawAllocated ?: ($rawReceived ?: 'Admin')));
            $pRow['received_by_name'] = $finalReceivedName;
            $pRow['allocated_by_name'] = $recordedAccountUser ?: ($rawAllocated ?: 'Admin');
            $pRow['verification_by_name'] = $resolvedVerification ?: '';

            $allClientPayments[] = $pRow;
            $totalAdvancePaidLifetime += $amt;
            
            if ($invNo) {
                if (!isset($paymentsByOrder[$invNo])) $paymentsByOrder[$invNo] = [];
                $paymentsByOrder[$invNo][] = $pRow;
            } else {
                if (!isset($paymentsByOrder['__UNALLOCATED__'])) $paymentsByOrder['__UNALLOCATED__'] = [];
                $paymentsByOrder['__UNALLOCATED__'][] = $pRow;
            }
        }
    }
    
    // -------------------------------------------------------------
    // STEP 5: Fetch Sowing Records from `krf_polyhouse_sowingplant_details`
    // -------------------------------------------------------------
    $sowingByOrder = [];
    if (!empty($allPsonoNumbers)) {
        $inPso = implode("','", array_map([$krfconn, 'real_escape_string'], $allPsonoNumbers));
        $sowingSql = "
            SELECT 
                spd.psono,
                spd.stockorder_no,
                MAX(spd.sowingbatchno) AS sowingbatchno,
                MAX(spd.sowing_date) AS sowing_date,
                MAX(spd.orderqty) AS orderqty,
                MAX(spd.totalsowingqty) AS totalsowingqty,
                COUNT(spd.sowingplantid) AS total_trays,
                MAX(spd.traycavity) AS traycavity,
                MAX(spd.productid) AS productid,
                MAX(spd.productname) AS productname,
                MAX(spd.roottype) AS roottype,
                MAX(spd.lotno) AS lotno,
                MAX(spd.health_status) AS health_status,
                MAX(spd.workingstatus) AS workingstatus,
                MAX(spd.status) AS status,
                MAX(spd.remarks) AS remarks,
                DATE_FORMAT(MAX(spd.sowing_date), '%d-%b-%Y') AS formatted_sowing_date
            FROM krf_polyhouse_sowingplant_details spd
            WHERE spd.psono IN ('{$inPso}')
            GROUP BY spd.psono, spd.stockorder_no
            ORDER BY spd.psono DESC
        ";
        $sowRes = $krfconn->query($sowingSql);
        if ($sowRes) {
            while ($sRow = $sowRes->fetch_assoc()) {
                $pKey = trim($sRow['psono'] ?? '');
                if (!isset($sowingByOrder[$pKey])) $sowingByOrder[$pKey] = [];
                $sowingByOrder[$pKey][] = $sRow;
            }
        }
    }
    
    // -------------------------------------------------------------
    // STEP 6: Fetch Polyhouse Allocations & Dispatch from `krf_productionorder_consumed`
    // -------------------------------------------------------------
    $consumedByOrder = [];
    $allStockOrders = [];
    
    if (!empty($allPsonoNumbers)) {
        $inPso = implode("','", array_map([$krfconn, 'real_escape_string'], $allPsonoNumbers));
        $consumedSql = "
            SELECT 
                c.id AS consumed_id,
                c.psono,
                c.sono,
                c.stockorderno,
                c.allocate_healthyplant,
                c.allocate_unhealthyplant,
                c.dispatchedqty,
                GREATEST(0, (COALESCE(c.allocate_healthyplant, 0) - COALESCE(c.dispatchedqty, 0))) AS estimate_open_stock,
                c.confirmstatus,
                c.status AS consumed_status,
                c.allocatedat,
                c.allocatedby,
                c.remark AS consumed_remark,
                DATE_FORMAT(c.allocatedat, '%d-%b-%Y %H:%i') AS formatted_allocated_at
            FROM krf_productionorder_consumed c
            WHERE c.psono IN ('{$inPso}') OR c.sono IN ('{$inPso}')
            ORDER BY c.id DESC
        ";
        $cRes = $krfconn->query($consumedSql);
        if ($cRes) {
            while ($cRow = $cRes->fetch_assoc()) {
                $pKey = trim($cRow['psono'] ?? $cRow['sono'] ?? '');
                $stk = trim($cRow['stockorderno'] ?? '');
                if ($stk && !in_array($stk, $allStockOrders)) $allStockOrders[] = $stk;
                
                if ($pKey) {
                    if (!isset($consumedByOrder[$pKey])) $consumedByOrder[$pKey] = [];
                    $consumedByOrder[$pKey][] = $cRow;
                }
            }
        }
    }
    
    // -------------------------------------------------------------
    // STEP 7: Fetch Polyhouse & Bed Locations from `krf_polyhouse_plant_details`
    // -------------------------------------------------------------
    $polyhouseByStock = [];
    if (!empty($allStockOrders)) {
        $inStk = implode("','", array_map([$krfconn, 'real_escape_string'], $allStockOrders));
        $polySql = "
            SELECT 
                pd.stockorder_no,
                pd.polyhouseid,
                pm.polyhousename,
                GROUP_CONCAT(DISTINCT pd.bed_no ORDER BY pd.bed_no SEPARATOR ', ') AS beds,
                COUNT(pd.polyhouseplantid) AS tray_count,
                SUM(pd.qtypertray) AS total_plants
            FROM krf_polyhouse_plant_details pd
            LEFT JOIN krf_polyhouse_master pm ON pm.polyhouseid = pd.polyhouseid
            WHERE pd.stockorder_no IN ('{$inStk}')
            GROUP BY pd.stockorder_no, pd.polyhouseid, pm.polyhousename
        ";
        $pRes = $krfconn->query($polySql);
        if ($pRes) {
            while ($pRow = $pRes->fetch_assoc()) {
                $stkKey = trim($pRow['stockorder_no'] ?? '');
                if (!isset($polyhouseByStock[$stkKey])) $polyhouseByStock[$stkKey] = [];
                $polyhouseByStock[$stkKey][] = $pRow;
            }
        }
    }
    
    // -------------------------------------------------------------
    // STEP 8: Construct Comprehensive Order Cards Array & Summary KPIs
    // -------------------------------------------------------------
    $ordersCards = [];
    $totalOrdersCount = 0;
    $totalOrderValueLifetime = 0;
    $totalPlantsOrderedLifetime = 0;
    $totalPlantsSownLifetime = 0;
    $totalPlantsAllocatedLifetime = 0;
    $totalPlantsDispatchedLifetime = 0;
    
    $allOrderKeys = array_unique(array_merge($allPsonoNumbers, array_keys($confirmedSalesOrders)));
    if (empty($allOrderKeys) && !empty($allClientPayments)) {
        $allOrderKeys[] = '__PAYMENTS_ONLY__';
    }
    
    foreach ($allOrderKeys as $orderKey) {
        $psoData = $psoList[$orderKey] ?? null;
        $soData = $confirmedSalesOrders[$orderKey] ?? null;
        $items = $psoItemsMap[$orderKey] ?? [];
        $payments = $paymentsByOrder[$orderKey] ?? [];
        $sowings = $sowingByOrder[$orderKey] ?? [];
        $allocations = $consumedByOrder[$orderKey] ?? [];
        
        $psono = $orderKey !== '__PAYMENTS_ONLY__' ? $orderKey : 'General Client Payments';
        $sono = $soData['sono'] ?? '';
        
        // Calculate Order Totals
        $orderTotalAmount = 0;
        $orderTotalQty = 0;
        if (!empty($items)) {
            foreach ($items as $it) {
                $orderTotalAmount += (float)($it['amount'] ?? 0);
                $orderTotalQty += (int)($it['quantity'] ?? 0);
            }
        } elseif ($soData) {
            $orderTotalAmount = (float)($soData['grand_total'] ?: $soData['total_amount'] ?: 0);
        }
        
        // Calculate Payments against this order
        $orderTotalAdvancePaid = 0;
        foreach ($payments as $pay) {
            $orderTotalAdvancePaid += (float)($pay['advanceamount'] ?? 0);
        }
        
        // Calculate Sowing against this order
        $orderTotalSownQty = 0;
        $stockOrderNumbers = [];
        foreach ($sowings as $sow) {
            $orderTotalSownQty += (int)($sow['totalsowingqty'] ?? 0);
            $stk = trim($sow['stockorder_no'] ?? '');
            if ($stk && !in_array($stk, $stockOrderNumbers)) $stockOrderNumbers[] = $stk;
        }
        
        // Calculate Allocations & Dispatches
        $orderAllocatedPlants = 0;
        $orderDispatchedPlants = 0;
        $orderEstOpenStock = 0;
        foreach ($allocations as $alc) {
            $orderAllocatedPlants += (int)($alc['allocate_healthyplant'] ?? 0);
            $orderDispatchedPlants += (int)($alc['dispatchedqty'] ?? 0);
            $orderEstOpenStock += (int)($alc['estimate_open_stock'] ?? 0);
            $stk = trim($alc['stockorderno'] ?? '');
            if ($stk && !in_array($stk, $stockOrderNumbers)) $stockOrderNumbers[] = $stk;
        }
        
        // Polyhouse locations mapped for this order
        $polyhouseDetails = [];
        foreach ($stockOrderNumbers as $stk) {
            if (isset($polyhouseByStock[$stk])) {
                foreach ($polyhouseByStock[$stk] as $ploc) {
                    $polyhouseDetails[] = $ploc;
                }
            }
        }
        
        $balanceDue = max(0, $orderTotalAmount - $orderTotalAdvancePaid);
        $paidPercentage = ($orderTotalAmount > 0) ? min(100, round(($orderTotalAdvancePaid / $orderTotalAmount) * 100, 1)) : ($orderTotalAdvancePaid > 0 ? 100 : 0);
        
        if ($orderKey !== '__PAYMENTS_ONLY__') {
            $totalOrdersCount++;
            $totalOrderValueLifetime += $orderTotalAmount;
            $totalPlantsOrderedLifetime += $orderTotalQty;
            $totalPlantsSownLifetime += $orderTotalSownQty;
            $totalPlantsAllocatedLifetime += $orderAllocatedPlants;
            $totalPlantsDispatchedLifetime += $orderDispatchedPlants;
        }
        
        $ordersCards[] = [
            'psono' => $psono,
            'sono' => $sono,
            'booking_date' => $psoData['formatted_booking_date'] ?? $soData['formatted_so_date'] ?? '-',
            'delivery_date' => $psoData['formatted_delivery_date'] ?? '-',
            'status' => $psoData['pso_status'] ?? $soData['so_status'] ?? 'Active',
            'delivery_address' => $psoData['delivery_address'] ?? $psoData['address'] ?? $soData['delivery_address'] ?? $clientProfile['address'] ?? '-',
            'districtname' => $psoData['districtname'] ?? $soData['districtname'] ?? $clientProfile['districtname'] ?? '',
            'statename' => $psoData['statename'] ?? $soData['statename'] ?? $clientProfile['statename'] ?? '',
            'total_amount' => $orderTotalAmount,
            'advance_paid' => $orderTotalAdvancePaid,
            'balance_due' => $balanceDue,
            'paid_percentage' => $paidPercentage,
            'ordered_qty' => $orderTotalQty,
            'sown_qty' => $orderTotalSownQty,
            'allocated_qty' => $orderAllocatedPlants,
            'dispatched_qty' => $orderDispatchedPlants,
            'est_open_stock' => $orderEstOpenStock,
            'items' => $items,
            'payments' => $payments,
            'sowings' => $sowings,
            'allocations' => $allocations,
            'polyhouses' => $polyhouseDetails,
            'stock_order_numbers' => $stockOrderNumbers
        ];
    }
    
    // Add any unallocated payments box if present
    $unallocatedPayments = $paymentsByOrder['__UNALLOCATED__'] ?? [];
    
    $lifetimeBalanceOutstanding = max(0, $totalOrderValueLifetime - $totalAdvancePaidLifetime);
    
    echo json_encode([
        'status' => 'success',
        'client' => $clientProfile,
        'summary_kpis' => [
            'total_orders' => $totalOrdersCount,
            'total_order_value' => $totalOrderValueLifetime,
            'total_advance_paid' => $totalAdvancePaidLifetime,
            'total_balance_due' => $lifetimeBalanceOutstanding,
            'total_plants_ordered' => $totalPlantsOrderedLifetime,
            'total_plants_sown' => $totalPlantsSownLifetime,
            'total_plants_allocated' => $totalPlantsAllocatedLifetime,
            'total_plants_dispatched' => $totalPlantsDispatchedLifetime
        ],
        'orders_cards' => $ordersCards,
        'unallocated_payments' => $unallocatedPayments,
        'all_payments' => $allClientPayments
    ]);
    exit;
} catch (Throwable $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Server Error: ' . $e->getMessage()
    ]);
    exit;
}
}

echo json_encode(['status' => 'error', 'message' => "Invalid action requested: '{$action}'."]);
exit;
