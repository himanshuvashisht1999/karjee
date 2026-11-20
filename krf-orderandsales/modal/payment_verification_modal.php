<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ob_start();
error_reporting(0);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'error',
            'message' => 'PHP Server Error: ' . $error['message'] . ' in ' . basename($error['file']) . ' on line ' . $error['line']
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }
});

if (!function_exists('sendJsonResponse')) {
    function sendJsonResponse($data) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }
}

if (!function_exists('getRejectionColumnsClause')) {
    function getRejectionColumnsClause($krfconn) {
        static $clause = null;
        if ($clause !== null) return $clause;
        $hasCols = false;
        if ($krfconn) {
            try {
                $chk = $krfconn->query("SHOW COLUMNS FROM advance_payment_transactions LIKE 'rejection_remark'");
                if ($chk && $chk->num_rows > 0) {
                    $hasCols = true;
                } else {
                    // Automatically add the rejection metadata columns if missing
                    @$krfconn->query("ALTER TABLE advance_payment_transactions ADD COLUMN rejection_remark TEXT NULL");
                    @$krfconn->query("ALTER TABLE advance_payment_transactions ADD COLUMN rejectedby VARCHAR(205) NULL");
                    @$krfconn->query("ALTER TABLE advance_payment_transactions ADD COLUMN rejectedat DATETIME NULL");
                    @$krfconn->query("ALTER TABLE advance_payment_transactions MODIFY COLUMN verification ENUM('verified', 'unverified', 'rejected') NOT NULL DEFAULT 'unverified'");
                    $chk2 = $krfconn->query("SHOW COLUMNS FROM advance_payment_transactions LIKE 'rejection_remark'");
                    if ($chk2 && $chk2->num_rows > 0) {
                        $hasCols = true;
                    }
                }
            } catch (Throwable $e) {
                $hasCols = false;
            }
        }
        if ($hasCols) {
            $clause = "apt.rejection_remark, apt.rejectedby, apt.rejectedat,";
        } else {
            $clause = "NULL AS rejection_remark, NULL AS rejectedby, NULL AS rejectedat,";
        }
        return $clause;
    }
}

require_once('../../connection.php');
require('../../loginCell/functions/login_functions.php');
include("../functions/orders_functions.php");
include("../functions/product_functions.php");
include("../functions/client_functions.php");
if (file_exists('../../whatsappnotification/whatsappsmsfunctions.php')) {
    include_once('../../whatsappnotification/whatsappsmsfunctions.php');
}
if (file_exists('../functions/pdf_generation_functions.php')) {
    include_once('../functions/pdf_generation_functions.php');
}

// Re-enforce error suppression in case any included file enabled display_errors
error_reporting(0);
ini_set('display_errors', '0');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action == "get_filter_data") {
    $projectid = $_POST['projectid'] ?? 0;
    
    // 1. Fetch Products
    $products = [];
    $prodSql = "SELECT ProductID, ProductName, IsGrafted FROM product_master WHERE (Status = 'active' OR Status = 'Active')";
    if (!empty($projectid)) {
        $prodSql .= " AND (ProjectId = '{$projectid}' OR ProjectId = 0 OR ProjectId IS NULL)";
    }
    $prodSql .= " ORDER BY ProductName ASC";
    $pRes = $krfconn->query($prodSql);
    if ($pRes) {
        while ($pRow = $pRes->fetch_assoc()) {
            $rawName = $pRow['ProductName'] ?? '';
            $displayName = preg_replace('/^(NonGrafted|Non-Grafted)\s*[-:]?\s*/i', '', $rawName);
            $products[] = [
                'productid' => $pRow['ProductID'],
                'productname' => $displayName,
                'rawname' => $rawName,
                'isgrafted' => $pRow['IsGrafted'] ?? ''
            ];
        }
    }

    // 2. Fetch Clients who have advance payment transactions
    $clients = [];
    $pIdEsc = !empty($projectid) ? $krfconn->real_escape_string($projectid) : '';
    $projCond = !empty($pIdEsc) ? "AND (apt.projectid = '{$pIdEsc}' OR apt.projectid IS NULL OR apt.projectid = 0)" : "";

    $clientSql = "SELECT DISTINCT 
        COALESCE(apt.clientid, pso.clientid, '') AS clientid, 
        COALESCE(apt.clientname, pso.clientname, cm.client_name, '') AS clientname, 
        COALESCE(pso.mobileno, cm.phones, '') AS mobileno 
        FROM advance_payment_transactions apt
        LEFT JOIN krf_provisional_sales_order pso ON pso.psono = apt.invoiceno
        LEFT JOIN krf_client_master cm ON cm.client_id = apt.clientid
        WHERE (apt.tedit IS NULL OR apt.tedit = '') 
          AND NULLIF(TRIM(COALESCE(apt.clientname, pso.clientname, cm.client_name, '')), '') IS NOT NULL
          {$projCond}
        ORDER BY clientname ASC";
    $cRes = $krfconn->query($clientSql);
    if ($cRes) {
        while ($cRow = $cRes->fetch_assoc()) {
            if (!empty($cRow['clientname'])) {
                $clients[] = [
                    'clientid' => $cRow['clientid'],
                    'clientname' => $cRow['clientname'],
                    'mobileno' => $cRow['mobileno'] ?? ''
                ];
            }
        }
    }

    sendJsonResponse([
        'status' => 'success',
        'products' => $products,
        'clients' => $clients
    ]);
}

if ($action == "get_utr_details") {
    $utrnumber = trim($_POST['utrnumber'] ?? $_GET['utrnumber'] ?? '');
    $projectid = $_POST['projectid'] ?? $_GET['projectid'] ?? 0;

    if (empty($utrnumber)) {
        sendJsonResponse(['status' => 'error', 'message' => 'UTR number is required']);
    }

    $utrEsc = $krfconn->real_escape_string($utrnumber);
    $where = "apt.utrnumber = '{$utrEsc}' AND (apt.tedit IS NULL OR apt.tedit = '')";
    $rejectionCols = getRejectionColumnsClause($krfconn);

    $query = "
        SELECT 
            apt.sno,
            apt.companyid,
            apt.subcompanyid,
            apt.projectid,
            apt.clientid,
            COALESCE(apt.clientname, pso.clientname, cm.client_name, 'Unknown Client') AS clientname,
            apt.advanceamount,
            apt.utrnumber,
            apt.paymentdate,
            apt.purposeofpayment,
            apt.invoiceno,
            apt.sourceofpayment,
            apt.receivedtoaccountid,
            apt.paymentref_no,
            apt.receivedby,
            apt.receivedlocation,
            apt.receiptpath,
            apt.remarks,
            {$rejectionCols}
            apt.allocated_datetime,
            apt.allocatedby,
            apt.ledgerstatus,
            COALESCE(apt.verification, 'unverified') AS verification,
            apt.verificationby,
            apt.verificationat,
            pso.created_at AS pso_created_at,
            pso.created_by AS pso_created_by,
            pso.expecteddelivery AS pso_expecteddelivery,
            pso.status AS pso_status,
            pso.mobileno AS pso_mobileno,
            pso.districtname AS pso_districtname,
            pso.statename AS pso_statename
        FROM advance_payment_transactions apt
        LEFT JOIN krf_provisional_sales_order pso ON pso.psono = apt.invoiceno
        LEFT JOIN krf_client_master cm ON cm.client_id = apt.clientid
        WHERE {$where}
        ORDER BY apt.sno DESC
    ";

    $res = $krfconn->query($query);
    $transactions = [];
    $totalAmount = 0;
    $distinctOrders = [];

    // Cache user map
    $userMap = [];
    if (isset($authconn) && $authconn) {
        $uRes = $authconn->query("SELECT loginid, loginname FROM login_user_master");
        if ($uRes) {
            while ($uRow = $uRes->fetch_assoc()) {
                $userMap[$uRow['loginid']] = $uRow['loginname'];
            }
        }
    }

    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $amt = (float)($row['advanceamount'] ?? 0);
            $totalAmount += $amt;
            $orderNo = trim($row['invoiceno'] ?? '');
            if (!empty($orderNo) && !in_array($orderNo, $distinctOrders)) {
                $distinctOrders[] = $orderNo;
            }

            // Resolve items for this order
            $items = [];
            if (!empty($orderNo)) {
                $iStmt = $krfconn->prepare("SELECT id, product_id, product_name, quantity, rate, amount, expecteddeliverydate FROM krf_provisional_sales_order_items WHERE psono = ? ORDER BY id ASC");
                if ($iStmt) {
                    $iStmt->bind_param('s', $orderNo);
                    $iStmt->execute();
                    $iRes = $iStmt->get_result();
                    while ($iRow = $iRes->fetch_assoc()) {
                        $cleanName = preg_replace('/^(NonGrafted|Non-Grafted)\s*[-:]?\s*/i', '', $iRow['product_name'] ?? '');
                        $items[] = [
                            'product_id' => $iRow['product_id'],
                            'product_name' => $cleanName,
                            'full_product_name' => $iRow['product_name'],
                            'quantity' => (float)$iRow['quantity'],
                            'rate' => (float)$iRow['rate'],
                            'amount' => (float)$iRow['amount'],
                            'expecteddeliverydate' => !empty($iRow['expecteddeliverydate']) ? date('d-M-Y', strtotime($iRow['expecteddeliverydate'])) : ''
                        ];
                    }
                    $iStmt->close();
                }
            }

            $rawCreatedBy = trim($row['pso_created_by'] ?? ($row['allocatedby'] ?? ''));
            $row['pso_created_by_name'] = $userMap[$rawCreatedBy] ?? ($rawCreatedBy ?: 'Admin');
            $row['formatted_paymentdate'] = !empty($row['paymentdate']) ? date('d-M-Y', strtotime($row['paymentdate'])) : 'N/A';
            $row['formatted_pso_created_at'] = !empty($row['pso_created_at']) ? date('d-M-Y h:i A', strtotime($row['pso_created_at'])) : (!empty($row['allocated_datetime']) ? date('d-M-Y h:i A', strtotime($row['allocated_datetime'])) : 'N/A');
            $row['formatted_verificationat'] = !empty($row['verificationat']) ? date('d-M-Y h:i A', strtotime($row['verificationat'])) : '';
            $row['formatted_rejectedat'] = !empty($row['rejectedat']) ? date('d-M-Y h:i A', strtotime($row['rejectedat'])) : '';
            $row['advanceamount_formatted'] = '₹' . number_format($amt, 2);
            $row['items'] = $items;

            $transactions[] = $row;
        }
    }

    sendJsonResponse([
        'status' => 'success',
        'utrnumber' => (string)$utrnumber,
        'total_entries' => count($transactions),
        'total_amount' => $totalAmount,
        'total_amount_formatted' => '₹' . number_format($totalAmount, 2),
        'orders_count' => count($distinctOrders),
        'transactions' => $transactions
    ]);
}

if ($action == "fetch_payments") {
    $projectid = $_POST['projectid'] ?? '';
    $productid = trim($_POST['productid'] ?? '');
    $clientid = trim($_POST['clientid'] ?? '');
    $psodate = trim($_POST['psodate'] ?? '');
    $date_from = trim($_POST['date_from'] ?? '');
    $date_to = trim($_POST['date_to'] ?? '');
    $verification_status = trim($_POST['verification_status'] ?? 'unverified');
    $search = trim($_POST['search'] ?? '');
    $is_all_time = intval($_POST['is_all_time'] ?? 0);

    // Default Date Range: If no date filter is passed and not explicitly All Time,
    // default to current month. If current month has 0 unverified entries, default to previous month!
    $curMonthStart = date('Y-m-01');
    $curMonthEnd = date('Y-m-t');
    $prevMonthStart = date('Y-m-01', strtotime('first day of last month'));
    $prevMonthEnd = date('Y-m-t', strtotime('last day of last month'));
    $appliedPreset = '';

    if (empty($date_from) && empty($date_to) && empty($is_all_time) && empty($search) && empty($psodate)) {
        $pIdEsc = !empty($projectid) ? $krfconn->real_escape_string($projectid) : '';
        $pCond = !empty($pIdEsc) ? "AND (apt.projectid = '{$pIdEsc}' OR apt.projectid IS NULL OR apt.projectid = 0)" : "";
        $curUnvRes = $krfconn->query("SELECT COUNT(*) as c FROM advance_payment_transactions apt WHERE (apt.tedit IS NULL OR apt.tedit = '') AND apt.paymentdate >= '{$curMonthStart}' AND apt.paymentdate <= '{$curMonthEnd}' AND (apt.verification = 'unverified' OR apt.verification IS NULL OR apt.verification = '') {$pCond}");
        $curUnvCount = ($curUnvRes && $r = $curUnvRes->fetch_assoc()) ? intval($r['c']) : 0;

        if ($curUnvCount > 0) {
            $date_from = $curMonthStart;
            $date_to = $curMonthEnd;
            $appliedPreset = 'this_month';
        } else {
            $date_from = $prevMonthStart;
            $date_to = $prevMonthEnd;
            $appliedPreset = 'prev_month';
        }
    }

    $whereConditions = ["(apt.tedit IS NULL OR apt.tedit = '')"];

    if (!empty($projectid)) {
        $pIdEsc = $krfconn->real_escape_string($projectid);
        $whereConditions[] = "(apt.projectid = '{$pIdEsc}' OR apt.projectid IS NULL OR apt.projectid = 0)";
    }

    if (!empty($clientid)) {
        $cIdEsc = $krfconn->real_escape_string($clientid);
        $whereConditions[] = "(apt.clientid = '{$cIdEsc}' OR pso.clientid = '{$cIdEsc}')";
    }

    if (!empty($psodate)) {
        $psoDateEsc = $krfconn->real_escape_string($psodate);
        $whereConditions[] = "DATE(pso.created_at) = '{$psoDateEsc}'";
    }

    if (!empty($date_from)) {
        $dFromEsc = $krfconn->real_escape_string($date_from);
        $whereConditions[] = "apt.paymentdate >= '{$dFromEsc}'";
    }

    if (!empty($date_to)) {
        $dToEsc = $krfconn->real_escape_string($date_to);
        $whereConditions[] = "apt.paymentdate <= '{$dToEsc}'";
    }

    $baseWhereClause = implode(" AND ", $whereConditions);

    // 1. FAST STATS AGGREGATE QUERY (computes exact badge numbers across all tabs in ~5ms)
    $todayDate = date('Y-m-d');
    $statsQuery = "
        SELECT 
            COUNT(*) AS total_count,
            COALESCE(SUM(apt.advanceamount), 0) AS total_amount,
            SUM(CASE WHEN LOWER(COALESCE(apt.verification, 'unverified')) = 'unverified' THEN 1 ELSE 0 END) AS unverified_count,
            COALESCE(SUM(CASE WHEN LOWER(COALESCE(apt.verification, 'unverified')) = 'unverified' THEN apt.advanceamount ELSE 0 END), 0) AS unverified_amount,
            SUM(CASE WHEN LOWER(COALESCE(apt.verification, 'unverified')) = 'verified' THEN 1 ELSE 0 END) AS verified_count,
            COALESCE(SUM(CASE WHEN LOWER(COALESCE(apt.verification, 'unverified')) = 'verified' THEN apt.advanceamount ELSE 0 END), 0) AS verified_amount,
            SUM(CASE WHEN LOWER(COALESCE(apt.verification, 'unverified')) = 'rejected' THEN 1 ELSE 0 END) AS rejected_count,
            COALESCE(SUM(CASE WHEN LOWER(COALESCE(apt.verification, 'unverified')) = 'rejected' THEN apt.advanceamount ELSE 0 END), 0) AS rejected_amount,
            SUM(CASE WHEN apt.paymentdate = '{$todayDate}' THEN 1 ELSE 0 END) AS today_count,
            COALESCE(SUM(CASE WHEN apt.paymentdate = '{$todayDate}' THEN apt.advanceamount ELSE 0 END), 0) AS today_amount
        FROM advance_payment_transactions apt
        LEFT JOIN krf_provisional_sales_order pso ON pso.psono = apt.invoiceno
        WHERE {$baseWhereClause}
    ";
    $statsRes = $krfconn->query($statsQuery);
    $statsRow = $statsRes ? $statsRes->fetch_assoc() : [];

    $totalCount = intval($statsRow['total_count'] ?? 0);
    $totalAmount = floatval($statsRow['total_amount'] ?? 0);
    $unverifiedCount = intval($statsRow['unverified_count'] ?? 0);
    $unverifiedAmount = floatval($statsRow['unverified_amount'] ?? 0);
    $verifiedCount = intval($statsRow['verified_count'] ?? 0);
    $verifiedAmount = floatval($statsRow['verified_amount'] ?? 0);
    $rejectedCount = intval($statsRow['rejected_count'] ?? 0);
    $rejectedAmount = floatval($statsRow['rejected_amount'] ?? 0);
    $todayCount = intval($statsRow['today_count'] ?? 0);
    $todayAmount = floatval($statsRow['today_amount'] ?? 0);

    // 2. MAIN QUERY FOR REQUESTED TAB (filters in SQL for maximum speed & minimal memory)
    $rowWhereConditions = $whereConditions;

    if ($verification_status === 'unverified') {
        $rowWhereConditions[] = "(apt.verification = 'unverified' OR apt.verification IS NULL OR apt.verification = '')";
    } else if ($verification_status === 'verified') {
        $rowWhereConditions[] = "apt.verification = 'verified'";
    } else if ($verification_status === 'rejected') {
        $rowWhereConditions[] = "apt.verification = 'rejected'";
    }
    // If 'all', no status filter is appended

    if (!empty($productid)) {
        $prodEsc = $krfconn->real_escape_string($productid);
        $rowWhereConditions[] = "FIND_IN_SET('{$prodEsc}', item_sum.product_ids)";
    }

    // Search filter across multiple fields
    if (!empty($search)) {
        $sEsc = $krfconn->real_escape_string($search);
        $rowWhereConditions[] = "(
            apt.invoiceno LIKE '%{$sEsc}%' OR 
            apt.clientname LIKE '%{$sEsc}%' OR 
            pso.clientname LIKE '%{$sEsc}%' OR 
            apt.utrnumber LIKE '%{$sEsc}%' OR 
            apt.sourceofpayment LIKE '%{$sEsc}%' OR 
            apt.paymentref_no LIKE '%{$sEsc}%' OR 
            apt.receivedby LIKE '%{$sEsc}%' OR 
            apt.receivedlocation LIKE '%{$sEsc}%' OR 
            apt.remarks LIKE '%{$sEsc}%' OR 
            pso.mobileno LIKE '%{$sEsc}%' OR 
            item_sum.product_names LIKE '%{$sEsc}%'
        )";
    }

    $whereClause = implode(" AND ", $rowWhereConditions);
    $rejectionCols = getRejectionColumnsClause($krfconn);

    $query = "
        SELECT 
            apt.sno,
            apt.companyid,
            apt.subcompanyid,
            apt.projectid,
            apt.clientid,
            COALESCE(apt.clientname, pso.clientname, 'Unknown Client') AS clientname,
            apt.advanceamount,
            apt.utrnumber,
            COALESCE(utr_map.utr_entry_count, 1) AS utr_entry_count,
            apt.paymentdate,
            apt.purposeofpayment,
            apt.invoiceno,
            apt.sourceofpayment,
            apt.receivedtoaccountid,
            apt.paymentref_no,
            apt.receivedby,
            apt.receivedlocation,
            apt.receiptpath,
            apt.remarks,
            {$rejectionCols}
            apt.allocated_datetime,
            apt.allocatedby,
            apt.ledgerstatus,
            COALESCE(apt.verification, 'unverified') AS verification,
            apt.verificationby,
            apt.verificationat,
            pso.created_at AS pso_created_at,
            pso.created_by AS pso_created_by,
            pso.expecteddelivery AS pso_expecteddelivery,
            pso.status AS pso_status,
            pso.mobileno AS pso_mobileno,
            pso.districtname AS pso_districtname,
            pso.statename AS pso_statename,
            COALESCE(item_sum.total_order_amount, 0) AS pso_total_amount,
            COALESCE(adv_sum.total_paid_amount, apt.advanceamount, 0) AS paid_amount,
            item_sum.product_names,
            item_sum.product_ids,
            item_sum.total_plants
        FROM advance_payment_transactions apt
        LEFT JOIN krf_provisional_sales_order pso ON pso.psono = apt.invoiceno
        LEFT JOIN (
            SELECT 
                psono, 
                SUM(amount) AS total_order_amount,
                SUM(quantity) AS total_plants,
                GROUP_CONCAT(DISTINCT product_name SEPARATOR ' | ') AS product_names,
                GROUP_CONCAT(DISTINCT product_id SEPARATOR ',') AS product_ids
            FROM krf_provisional_sales_order_items
            GROUP BY psono
        ) item_sum ON item_sum.psono = apt.invoiceno
        LEFT JOIN (
            SELECT invoiceno, SUM(advanceamount) AS total_paid_amount
            FROM advance_payment_transactions
            WHERE (tedit IS NULL OR tedit = '') AND NULLIF(TRIM(invoiceno), '') IS NOT NULL
            GROUP BY invoiceno
        ) adv_sum ON adv_sum.invoiceno = apt.invoiceno
        LEFT JOIN (
            SELECT utrnumber, COUNT(*) AS utr_entry_count
            FROM advance_payment_transactions
            WHERE NULLIF(TRIM(utrnumber), '') IS NOT NULL AND (tedit IS NULL OR tedit = '')
            GROUP BY utrnumber
        ) utr_map ON utr_map.utrnumber = apt.utrnumber
        WHERE {$whereClause}
        ORDER BY apt.sno DESC
    ";

    try {
        $result = $krfconn->query($query);
    } catch (Throwable $e) {
        // If query failed (e.g. schema/column mismatch), fallback with NULL rejection columns
        $fallbackQuery = str_replace($rejectionCols, "NULL AS rejection_remark, NULL AS rejectedby, NULL AS rejectedat,", $query);
        try {
            $result = $krfconn->query($fallbackQuery);
        } catch (Throwable $e2) {
            sendJsonResponse([
                'status' => 'error',
                'message' => 'Query execution failed: ' . $e2->getMessage()
            ]);
        }
    }
    $payments = [];

    // Cache user names map for fast resolution
    $userMap = [];
    if (isset($authconn) && $authconn) {
        $uRes = $authconn->query("SELECT loginid, loginname FROM login_user_master");
        if ($uRes) {
            while ($uRow = $uRes->fetch_assoc()) {
                $userMap[$uRow['loginid']] = $uRow['loginname'];
            }
        }
    }

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $amt = (float)($row['advanceamount'] ?? 0);

            // Resolve Generated By Name
            $rawCreatedBy = trim($row['pso_created_by'] ?? ($row['allocatedby'] ?? ''));
            $resolvedCreatedBy = $userMap[$rawCreatedBy] ?? $rawCreatedBy;
            if (empty($resolvedCreatedBy)) {
                $resolvedCreatedBy = 'Admin';
            }
            $row['pso_created_by_name'] = $resolvedCreatedBy;

            // Clean up paths & formatting
            $row['formatted_paymentdate'] = !empty($row['paymentdate']) ? date('d-m-Y', strtotime($row['paymentdate'])) : 'N/A';
            $row['formatted_pso_created_at'] = !empty($row['pso_created_at']) ? date('d-m-Y h:i A', strtotime($row['pso_created_at'])) : (!empty($row['allocated_datetime']) ? date('d-m-Y h:i A', strtotime($row['allocated_datetime'])) : 'N/A');
            $row['formatted_verificationat'] = !empty($row['verificationat']) ? date('d-m-Y h:i A', strtotime($row['verificationat'])) : '';
            $row['formatted_rejectedat'] = !empty($row['rejectedat']) ? date('d-m-Y h:i A', strtotime($row['rejectedat'])) : '';
            $row['advanceamount_formatted'] = '₹' . number_format($amt, 2);
            $row['pso_total_amount_formatted'] = '₹' . number_format((float)$row['pso_total_amount'], 2);

            $payments[] = $row;
        }
    }

    sendJsonResponse([
        'status' => 'success',
        'data' => $payments,
        'applied_date_from' => $date_from,
        'applied_date_to' => $date_to,
        'applied_preset' => $appliedPreset,
        'stats' => [
            'total_count' => $totalCount,
            'total_amount' => $totalAmount,
            'total_amount_formatted' => '₹' . number_format($totalAmount, 2),
            'unverified_count' => $unverifiedCount,
            'unverified_amount' => $unverifiedAmount,
            'unverified_amount_formatted' => '₹' . number_format($unverifiedAmount, 2),
            'verified_count' => $verifiedCount,
            'verified_amount' => $verifiedAmount,
            'verified_amount_formatted' => '₹' . number_format($verifiedAmount, 2),
            'rejected_count' => $rejectedCount,
            'rejected_amount' => $rejectedAmount,
            'rejected_amount_formatted' => '₹' . number_format($rejectedAmount, 2),
            'today_count' => $todayCount,
            'today_amount' => $todayAmount,
            'today_amount_formatted' => '₹' . number_format($todayAmount, 2)
        ]
    ]);
}

/**
 * Send WhatsApp Notification with Final Sale Invoice & Product Details upon Payment Verification
 * Accepts $psono (provisional order no), $sno (payment ID), and $loginname (verifier)
 */
function sendPaymentVerifiedSalesInvoiceWhatsApp($krfconn, $psono = '', $sno = 0, $loginname = '') {
    global $authconn, $conn;
    $db = (isset($krfconn) && $krfconn) ? $krfconn : $conn;
    if (!$db) return false;

    // Handle backward compatibility if single numeric parameter was passed ($sno)
    if (is_numeric($psono) && intval($psono) > 0 && empty($sno)) {
        $sno = intval($psono);
        $psono = '';
    }
    $psono = trim((string)$psono);
    $sno = intval($sno);

    if (empty($psono) && $sno <= 0) return false;

    try {
        $data = null;

        // 1. Fetch by sno if available, otherwise by psono
        if ($sno > 0) {
            $stmt = $db->prepare("
                SELECT 
                    apt.sno,
                    COALESCE(apt.companyid, pso.companyid, 19) AS companyid,
                    COALESCE(apt.subcompanyid, pso.subcompanyid, 12) AS subcompanyid,
                    COALESCE(apt.projectid, pso.projectid, 17) AS projectid,
                    COALESCE(apt.clientid, pso.clientid) AS clientid,
                    COALESCE(apt.clientname, pso.clientname, cm.client_name, 'Farmer') AS clientname,
                    apt.advanceamount,
                    apt.utrnumber,
                    apt.paymentdate,
                    apt.sourceofpayment,
                    apt.paymentref_no,
                    COALESCE(apt.invoiceno, pso.psono, '') AS psono,
                    apt.receiptpath,
                    COALESCE(pso.mobileno, cm.phones, '') AS mobileno,
                    pso.created_at AS pso_date,
                    pso.expecteddelivery,
                    pso.statename,
                    pso.districtname,
                    COALESCE(pso_sum.total_order_amount, 0) AS pso_total_amount
                FROM advance_payment_transactions apt
                LEFT JOIN krf_provisional_sales_order pso ON pso.psono = apt.invoiceno
                LEFT JOIN krf_client_master cm ON cm.client_id = COALESCE(apt.clientid, pso.clientid)
                LEFT JOIN (
                    SELECT psono, SUM(amount) AS total_order_amount 
                    FROM krf_provisional_sales_order_items 
                    GROUP BY psono
                ) pso_sum ON (pso_sum.psono = apt.invoiceno OR (pso.psono IS NOT NULL AND pso_sum.psono = pso.psono))
                WHERE apt.sno = ? AND (apt.tedit IS NULL OR apt.tedit = '')
                LIMIT 1
            ");
            if ($stmt) {
                $stmt->bind_param("i", $sno);
                $stmt->execute();
                $res = $stmt->get_result();
                $data = $res->fetch_assoc();
                $stmt->close();
            }
        }

        // 2. If data not found by sno or sno was not provided, fetch directly using psono
        if (!$data && !empty($psono)) {
            $stmt = $db->prepare("
                SELECT 
                    COALESCE(apt.sno, 0) AS sno,
                    COALESCE(pso.companyid, apt.companyid, 19) AS companyid,
                    COALESCE(pso.subcompanyid, apt.subcompanyid, 12) AS subcompanyid,
                    COALESCE(pso.projectid, apt.projectid, 17) AS projectid,
                    COALESCE(pso.clientid, apt.clientid) AS clientid,
                    COALESCE(pso.clientname, apt.clientname, cm.client_name, 'Farmer') AS clientname,
                    COALESCE(apt.advanceamount, pso_adv.total_adv, 0) AS advanceamount,
                    COALESCE(apt.utrnumber, '') AS utrnumber,
                    COALESCE(apt.paymentdate, CURDATE()) AS paymentdate,
                    COALESCE(apt.sourceofpayment, 'Cash') AS sourceofpayment,
                    COALESCE(apt.paymentref_no, '') AS paymentref_no,
                    pso.psono AS psono,
                    COALESCE(apt.receiptpath, '') AS receiptpath,
                    COALESCE(pso.mobileno, cm.phones, '') AS mobileno,
                    pso.created_at AS pso_date,
                    pso.expecteddelivery,
                    pso.statename,
                    pso.districtname,
                    COALESCE(pso_sum.total_order_amount, 0) AS pso_total_amount
                FROM krf_provisional_sales_order pso
                LEFT JOIN (
                    SELECT * FROM advance_payment_transactions 
                    WHERE invoiceno = ? AND (tedit IS NULL OR tedit = '')
                    ORDER BY sno DESC LIMIT 1
                ) apt ON apt.invoiceno = pso.psono
                LEFT JOIN (
                    SELECT invoiceno, SUM(advanceamount) AS total_adv 
                    FROM advance_payment_transactions 
                    WHERE (tedit IS NULL OR tedit = '') 
                    GROUP BY invoiceno
                ) pso_adv ON pso_adv.invoiceno = pso.psono
                LEFT JOIN krf_client_master cm ON cm.client_id = pso.clientid
                LEFT JOIN (
                    SELECT psono, SUM(amount) AS total_order_amount 
                    FROM krf_provisional_sales_order_items 
                    GROUP BY psono
                ) pso_sum ON pso_sum.psono = pso.psono
                WHERE pso.psono = ?
                LIMIT 1
            ");
            if ($stmt) {
                $stmt->bind_param("ss", $psono, $psono);
                $stmt->execute();
                $res = $stmt->get_result();
                $data = $res->fetch_assoc();
                $stmt->close();
            }
        }

        if (!$data) return false;

        $psono = $data['psono'] ?? $psono;
        $clientid = $data['clientid'] ?? '';
        $clientname = trim($data['clientname'] ?? 'Farmer');
        $mobileno = trim($data['mobileno'] ?? '');
        $advanceamount = (float)($data['advanceamount'] ?? 0);
        $sourceofpayment = $data['sourceofpayment'] ?: 'Cash';
        $utrnumber = $data['utrnumber'] ?: ($data['paymentref_no'] ?: 'N/A');
        $paymentdate = !empty($data['paymentdate']) ? date('d-m-Y', strtotime($data['paymentdate'])) : date('d-m-Y');
        $companyid = $data['companyid'] ?? 19;
        $subcompanyid = $data['subcompanyid'] ?? 12;
        $projectid = $data['projectid'] ?? 17;
        $psoTotalAmount = (float)($data['pso_total_amount'] ?? 0);

        // 2. Fetch Ordered Products with item breakdown
        $items = [];
        $totalPlants = 0;
        if (!empty($psono)) {
            $stmtItems = $db->prepare("SELECT product_name, quantity, rate, amount, expecteddeliverydate FROM krf_provisional_sales_order_items WHERE psono = ? ORDER BY id ASC");
            if ($stmtItems) {
                $stmtItems->bind_param("s", $psono);
                $stmtItems->execute();
                $iRes = $stmtItems->get_result();
                while ($iRow = $iRes->fetch_assoc()) {
                    $items[] = $iRow;
                    $totalPlants += (int)($iRow['quantity'] ?? 0);
                }
                $stmtItems->close();
            }
        }

        // 3. Check for Confirmed Final Sales Order (sono) or convert provisional order if not converted yet
        $sono = '';
        if (!empty($psono)) {
            $stmtSono = $db->prepare("SELECT sono, grand_total, total_amount FROM krf_sales_order WHERE psono = ? ORDER BY id DESC LIMIT 1");
            if ($stmtSono) {
                $stmtSono->bind_param("s", $psono);
                $stmtSono->execute();
                $sRes = $stmtSono->get_result();
                if ($sRow = $sRes->fetch_assoc()) {
                    $sono = $sRow['sono'];
                    $gTot = (float)($sRow['grand_total'] ?: $sRow['total_amount']);
                    if ($gTot > 0) {
                        $psoTotalAmount = $gTot;
                    }
                }
                $stmtSono->close();
            }

            // If not yet converted to sales order, convert now upon verification
            if (empty($sono) && function_exists('createSalesOrderFromProvisional')) {
                $convRes = createSalesOrderFromProvisional($db, $psono, $loginname);
                if (is_array($convRes) && !empty($convRes['sono'])) {
                    $sono = $convRes['sono'];
                }

                $stmtSono = $db->prepare("SELECT sono, grand_total, total_amount FROM krf_sales_order WHERE psono = ? ORDER BY id DESC LIMIT 1");
                if ($stmtSono) {
                    $stmtSono->bind_param("s", $psono);
                    $stmtSono->execute();
                    $sRes = $stmtSono->get_result();
                    if ($sRow = $sRes->fetch_assoc()) {
                        $sono = $sRow['sono'];
                        $gTot = (float)($sRow['grand_total'] ?: $sRow['total_amount']);
                        if ($gTot > 0) {
                            $psoTotalAmount = $gTot;
                        }
                    }
                    $stmtSono->close();
                }
            }
        }

        // 4. Generate or locate PDF invoice attachment
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? '') == 443) ? "https://" : "http://";
        $domainName = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $pdf_url = '';
        $pdf_filename = '';

        if (!empty($sono)) {
            $relPath = 'attachementfile/' . $companyid . '/' . $subcompanyid . '/' . $projectid . '/salesorder_bills/' . $sono . '.pdf';
            $fullPath = dirname(dirname(__DIR__)) . '/' . $relPath;
            if (!file_exists($fullPath) && function_exists('createSalesOrderPDF')) {
                if (!is_dir(dirname($fullPath))) {
                    mkdir(dirname($fullPath), 0777, true);
                }
                @createSalesOrderPDF($conn, $authconn, $db, $companyid, $subcompanyid, $projectid, $sono, 'F', $fullPath);
            }
            if (file_exists($fullPath)) {
                $pdf_url = $protocol . $domainName . '/krf_amanaccounting/' . $relPath;
                $pdf_filename = "Invoice_{$sono}.pdf";
            }
        } else if (!empty($psono)) {
            $relPath = 'attachementfile/' . $companyid . '/' . $subcompanyid . '/' . $projectid . '/provisionalorder_bills/' . $psono . '.pdf';
            $fullPath = dirname(dirname(__DIR__)) . '/' . $relPath;
            if (!file_exists($fullPath) && function_exists('createProvisionalOrderPDF')) {
                if (!is_dir(dirname($fullPath))) {
                    mkdir(dirname($fullPath), 0777, true);
                }
                @createProvisionalOrderPDF($conn, $authconn, $db, $companyid, $subcompanyid, $projectid, $psono, 'F', $fullPath);
            }
            if (file_exists($fullPath)) {
                $pdf_url = $protocol . $domainName . '/krf_amanaccounting/' . $relPath;
                $pdf_filename = "Provisional_Order_{$psono}.pdf";
            }
        }

        // 5. Compose WhatsApp Message with Full Product Details
        $orderTitle = !empty($sono) ? "Sale Invoice #{$sono}" : (!empty($psono) ? "Order #{$psono}" : "Payment Record #{$sno}");

        $msg = "🌱 *Karjee Farm – Payment Verified & Sale Invoice*\n\n";
        $msg .= "Dear *{$clientname}*,\n";
        $msg .= "Your advance payment has been successfully *VERIFIED* for {$orderTitle}.\n\n";

        $msg .= "💳 *Payment Details:*\n";
        $msg .= "• Verified Amount: *₹" . number_format($advanceamount, 2) . "*\n";
        $msg .= "• Payment Mode: *{$sourceofpayment}*\n";
        if (!empty($utrnumber) && $utrnumber !== 'N/A') {
            $msg .= "• Txn / UTR No: *{$utrnumber}*\n";
        }
        $msg .= "• Payment Date: *{$paymentdate}*\n\n";

        $msg .= "📋 *Order Summary:*\n";
        if (!empty($sono)) {
            $msg .= "• Final Invoice No: *#{$sono}*\n";
        }
        if (!empty($psono)) {
            $msg .= "• Provisional Order No: *#{$psono}*\n";
        }
        if ($psoTotalAmount > 0) {
            $msg .= "• Total Order Value: *₹" . number_format($psoTotalAmount, 2) . "*\n";
        }
        $msg .= "\n";

        if (!empty($items)) {
            $msg .= "📦 *Product Details:*\n";
            $itemIdx = 1;
            foreach ($items as $item) {
                $pName = trim($item['product_name'] ?? 'Product');
                $pQty = (int)($item['quantity'] ?? 0);
                $pRate = (float)($item['rate'] ?? 0);
                $pAmt = (float)($item['amount'] ?? ($pQty * $pRate));
                $expDelivery = !empty($item['expecteddeliverydate']) ? date('d-m-Y', strtotime($item['expecteddeliverydate'])) : '';

                $msg .= "{$itemIdx}. *{$pName}*\n";
                $msg .= "   Qty: *{$pQty} Plants* | Rate: ₹" . number_format($pRate, 2) . " | Amount: *₹" . number_format($pAmt, 2) . "*";
                if ($expDelivery) {
                    $msg .= "\n   Exp. Delivery: *{$expDelivery}*";
                }
                $msg .= "\n";
                $itemIdx++;
            }
            if ($totalPlants > 0) {
                $msg .= "🌱 *Total Plants:* {$totalPlants}\n";
            }
            $msg .= "\n";
        }

        if (!empty($pdf_url)) {
            $msg .= "📄 *Invoice PDF is attached with this message.*\n\n";
        }

        $msg .= "Thank you for choosing Karjee Farm! 🌾\n";
        $msg .= "_For any assistance, please feel free to reach out to us._";

        // 6. Enqueue and Send WhatsApp
        $centreid = 1;
        $smsinfo = function_exists('whatsappuseridpassword') ? whatsappuseridpassword($db, $centreid) : [];
        if (!empty($smsinfo) && isset($smsinfo[0])) {
            $sKey = $smsinfo[0];
            $sourceid = $sKey['sourceid'] ?? '';
            $whatsappno = $sKey['whatsappno'] ?? '';
            $apikey = $sKey['apikey'] ?? '';
            $accesstoken = $sKey['accesstoken'] ?? '';

            if (!empty($sourceid) && !empty($whatsappno) && !empty($apikey) && !empty($accesstoken)) {
                $recipients = [];
                if (!empty($mobileno) && strlen($mobileno) >= 10) {
                    $recipients[] = $mobileno;
                }
                if (function_exists('getRecipientsByModuleForSowing')) {
                    $deptRecipients = getRecipientsByModuleForSowing($db, $centreid, 'ORDER');
                    if (is_array($deptRecipients)) {
                        foreach ($deptRecipients as $dRow) {
                            $dNum = is_array($dRow) ? ($dRow['mobile_no'] ?? '') : $dRow;
                            if ($dNum) $recipients[] = $dNum;
                        }
                    }
                }
                
                $recipients = array_unique($recipients);

                $msg_title = "Payment Verified - {$orderTitle}";
                $msg_type = !empty($pdf_url) ? "textwithattachment" : "text";

                foreach ($recipients as $toNum) {
                    $toNum = trim($toNum);
                    if (empty($toNum) || strlen($toNum) < 10) continue;

                    if (function_exists('readyto_sentwhatsapp_message')) {
                        readyto_sentwhatsapp_message(
                            $db,
                            $sourceid,
                            $whatsappno,
                            $toNum,
                            $accesstoken,
                            $apikey,
                            $msg_title,
                            $msg,
                            $clientid,
                            $msg_type,
                            $pdf_url,
                            $pdf_filename
                        );
                    }
                }

                return true;
            }
        }
    } catch (Exception $e) {
        error_log("Error in sendPaymentVerifiedSalesInvoiceWhatsApp: " . $e->getMessage());
    }

    return false;
}

if ($action == "verify_payment") {
    $sno = intval($_POST['sno'] ?? 0);
    $psono = trim($_POST['psono'] ?? '');
    $loginname = trim($_POST['loginname'] ?? ($_SESSION['loginname'] ?? 'Admin'));

    if ($sno <= 0) {
        sendJsonResponse(['status' => 'error', 'message' => 'Invalid payment record ID.']);
    }

    $stmt = $krfconn->prepare("UPDATE advance_payment_transactions SET verification = 'verified', verificationby = ?, verificationat = NOW(), rejection_remark = NULL, rejectedby = NULL, rejectedat = NULL WHERE sno = ? AND (tedit IS NULL OR tedit = '')");
    if (!$stmt) {
        sendJsonResponse(['status' => 'error', 'message' => 'DB Prepare Error: ' . $krfconn->error]);
    }
    $stmt->bind_param("si", $loginname, $sno);
    if ($stmt->execute()) {
        $stmt->close();

        // Resolve psono if not passed in $_POST
        $targetPsono = $psono;
        if (empty($targetPsono)) {
            $chkPso = $krfconn->query("SELECT invoiceno FROM advance_payment_transactions WHERE sno = {$sno} AND (tedit IS NULL OR tedit = '') LIMIT 1");
            if ($chkPso && $pRow = $chkPso->fetch_assoc()) {
                $targetPsono = trim($pRow['invoiceno'] ?? '');
            }
        }

        // Update provisional sales order status to "Confirmed" if not already Confirmed or Cancelled
        if (!empty($targetPsono)) {
            $escPsono = $krfconn->real_escape_string($targetPsono);
            $krfconn->query("UPDATE krf_provisional_sales_order SET status = 'Confirmed', updated_at = NOW() WHERE psono = '{$escPsono}' AND status != 'Confirmed' AND status != 'Cancelled'");
            $krfconn->query("UPDATE krf_provisional_sales_order_items SET status = 'Confirmed' WHERE psono = '{$escPsono}' AND (status IS NULL OR status = '' OR (status != 'Confirmed' AND status != 'Cancelled'))");
            $krfconn->query("UPDATE krf_sales_order_items soi INNER JOIN krf_provisional_sales_order_items poi ON soi.psono = poi.psono AND (soi.stockorder_no = poi.stockorder_no OR (soi.product_id = poi.product_id AND soi.quantity = poi.quantity)) SET soi.roundoffamount = poi.roundoffamount WHERE soi.psono = '{$escPsono}'");
            $krfconn->query("UPDATE krf_production_stockorder SET status = 'Confirmed', updated_at = NOW() WHERE psono = '{$escPsono}' AND (LOWER(status) = 'unconfirmed' OR status = '' OR status IS NULL)");
            $krfconn->query("UPDATE krf_productionorder_consumed SET confirmstatus = 'Confirmed' WHERE psono = '{$escPsono}' AND (LOWER(confirmstatus) = 'unconfirmed' OR LOWER(confirmstatus) = 'pending' OR confirmstatus = '' OR confirmstatus IS NULL)");
        }

        // Send WhatsApp Message with Final Sale Invoice & Product Details using psono and sno
        $waSent = sendPaymentVerifiedSalesInvoiceWhatsApp($krfconn, $targetPsono, $sno, $loginname);
        $waMsg = $waSent ? " WhatsApp invoice with product details sent to client." : "";
        sendJsonResponse(['status' => 'success', 'message' => 'Payment verified successfully.' . $waMsg]);} else {
        sendJsonResponse(['status' => 'error', 'message' => 'Failed to verify payment: ' . $stmt->error]);$stmt->close();
    }
    exit;
}

if ($action == "unverify_payment") {
    $sno = intval($_POST['sno'] ?? 0);

    if ($sno <= 0) {
        sendJsonResponse(['status' => 'error', 'message' => 'Invalid payment record ID.']);
    }

    $hasCols = (getRejectionColumnsClause($krfconn) !== "NULL AS rejection_remark, NULL AS rejectedby, NULL AS rejectedat,");
    $unverifySql = $hasCols 
        ? "UPDATE advance_payment_transactions SET verification = 'unverified', verificationby = NULL, verificationat = NULL, rejection_remark = NULL, rejectedby = NULL, rejectedat = NULL WHERE sno = ? AND (tedit IS NULL OR tedit = '')"
        : "UPDATE advance_payment_transactions SET verification = 'unverified', verificationby = NULL, verificationat = NULL WHERE sno = ? AND (tedit IS NULL OR tedit = '')";
    $stmt = $krfconn->prepare($unverifySql);
    if (!$stmt) {
        sendJsonResponse(['status' => 'error', 'message' => 'DB Prepare Error: ' . $krfconn->error]);
    }
    $stmt->bind_param("i", $sno);
    if ($stmt->execute()) {
        // Revert status to Pending Approval / Draft if no other verified payments exist for this order
        $chkPso = $krfconn->query("SELECT invoiceno FROM advance_payment_transactions WHERE sno = {$sno} AND (tedit IS NULL OR tedit = '') LIMIT 1");
        if ($chkPso && $pRow = $chkPso->fetch_assoc()) {
            $invNo = trim($pRow['invoiceno'] ?? '');
            if (!empty($invNo)) {
                $escInv = $krfconn->real_escape_string($invNo);
                $othVer = $krfconn->query("SELECT COUNT(*) AS vcnt FROM advance_payment_transactions WHERE invoiceno = '{$escInv}' AND sno != {$sno} AND verification = 'verified' AND (tedit IS NULL OR tedit = '')");
                $vcnt = ($othVer && $vr = $othVer->fetch_assoc()) ? intval($vr['vcnt']) : 0;
                if ($vcnt === 0) {
                    $krfconn->query("UPDATE krf_provisional_sales_order SET status = 'Pending Approval', updated_at = NOW() WHERE psono = '{$escInv}' AND status = 'Confirmed'");
                }
            }
        }
        sendJsonResponse(['status' => 'success', 'message' => 'Payment marked as unverified.']);} else {
        sendJsonResponse(['status' => 'error', 'message' => 'Failed to revert verification: ' . $stmt->error]);}
    $stmt->close();
    exit;
}

if ($action == "batch_verify") {
    $snos = $_POST['snos'] ?? [];
    $loginname = trim($_POST['loginname'] ?? ($_SESSION['loginname'] ?? 'Admin'));

    if (empty($snos) || !is_array($snos)) {
        sendJsonResponse(['status' => 'error', 'message' => 'No payments selected for batch verification.']);
    }

    $cleanIds = array_map('intval', $snos);
    $cleanIds = array_filter($cleanIds, function($id) { return $id > 0; });

    if (empty($cleanIds)) {
        sendJsonResponse(['status' => 'error', 'message' => 'Invalid payment IDs.']);
    }

    $idList = implode(',', $cleanIds);
    $loginEsc = $krfconn->real_escape_string($loginname);
    $hasCols = (getRejectionColumnsClause($krfconn) !== "NULL AS rejection_remark, NULL AS rejectedby, NULL AS rejectedat,");
    $clearRejection = $hasCols ? ", rejection_remark = NULL, rejectedby = NULL, rejectedat = NULL" : "";

    $updateSql = "UPDATE advance_payment_transactions SET verification = 'verified', verificationby = '{$loginEsc}', verificationat = NOW(){$clearRejection} WHERE sno IN ({$idList}) AND (tedit IS NULL OR tedit = '')";
    if ($krfconn->query($updateSql)) {
        $count = count($cleanIds);

        // Update all associated provisional orders to "Confirmed"
        $krfconn->query("UPDATE krf_provisional_sales_order pso
                         JOIN advance_payment_transactions apt ON apt.invoiceno = pso.psono AND (apt.tedit IS NULL OR apt.tedit = '')
                         SET pso.status = 'Confirmed', pso.updated_at = NOW()
                         WHERE apt.sno IN ({$idList}) AND pso.status != 'Confirmed' AND pso.status != 'Cancelled'");
        $krfconn->query("UPDATE krf_production_stockorder pso
                         JOIN advance_payment_transactions apt ON apt.invoiceno = pso.psono AND (apt.tedit IS NULL OR apt.tedit = '')
                         SET pso.status = 'Confirmed', pso.updated_at = NOW()
                         WHERE apt.sno IN ({$idList}) AND (LOWER(pso.status) = 'unconfirmed' OR pso.status = '' OR pso.status IS NULL)");
        $krfconn->query("UPDATE krf_productionorder_consumed poc
                         JOIN advance_payment_transactions apt ON apt.invoiceno = poc.psono AND (apt.tedit IS NULL OR apt.tedit = '')
                         SET poc.confirmstatus = 'Confirmed'
                         WHERE apt.sno IN ({$idList}) AND (LOWER(poc.confirmstatus) = 'unconfirmed' OR LOWER(poc.confirmstatus) = 'pending' OR poc.confirmstatus = '' OR poc.confirmstatus IS NULL)");

        // Dispatch WhatsApp notifications for each verified payment
        foreach ($cleanIds as $vSno) {
            sendPaymentVerifiedSalesInvoiceWhatsApp($krfconn, '', $vSno, $loginname);
        }
        sendJsonResponse(['status' => 'success', 'message' => "Successfully verified {$count} payment(s) and dispatched WhatsApp invoice notifications."]);} else {
        sendJsonResponse(['status' => 'error', 'message' => 'Failed to batch verify: ' . $krfconn->error]);}
    exit;
}

if ($action == "reject_payment") {
    $sno = intval($_POST['sno'] ?? 0);
    $rejection_remark = trim($_POST['rejection_remark'] ?? '');
    $loginname = trim($_POST['loginname'] ?? ($_SESSION['loginname'] ?? 'Admin'));

    if ($sno <= 0) {
        sendJsonResponse(['status' => 'error', 'message' => 'Invalid payment record ID.']);
    }

    if (empty($rejection_remark)) {
        sendJsonResponse(['status' => 'error', 'message' => 'Rejection remark is required. Please provide a reason for rejecting this payment.']);
    }

    getRejectionColumnsClause($krfconn);
    $chkCol = $krfconn->query("SHOW COLUMNS FROM advance_payment_transactions LIKE 'rejection_remark'");
    if (!$chkCol || $chkCol->num_rows == 0) {
        sendJsonResponse([
            'status' => 'error',
            'message' => 'Database update required: Table advance_payment_transactions is missing column rejection_remark. Please execute ALTER TABLE advance_payment_transactions ADD COLUMN rejection_remark TEXT NULL, ADD COLUMN rejectedby VARCHAR(205) NULL, ADD COLUMN rejectedat DATETIME NULL;'
        ]);
    }

    $stmt = $krfconn->prepare("UPDATE advance_payment_transactions SET verification = 'rejected', rejection_remark = ?, rejectedby = ?, rejectedat = NOW() WHERE sno = ? AND (tedit IS NULL OR tedit = '')");
    if (!$stmt) {
        sendJsonResponse(['status' => 'error', 'message' => 'DB Prepare Error: ' . $krfconn->error]);
    }
    $stmt->bind_param("ssi", $rejection_remark, $loginname, $sno);
    if ($stmt->execute()) {
        $stmt->close();

        // Check associated provisional sales order
        $chkPso = $krfconn->query("SELECT invoiceno FROM advance_payment_transactions WHERE sno = {$sno} AND (tedit IS NULL OR tedit = '') LIMIT 1");
        if ($chkPso && $pRow = $chkPso->fetch_assoc()) {
            $invNo = trim($pRow['invoiceno'] ?? '');
            if (!empty($invNo)) {
                $escInv = $krfconn->real_escape_string($invNo);
                $othVer = $krfconn->query("SELECT COUNT(*) AS vcnt FROM advance_payment_transactions WHERE invoiceno = '{$escInv}' AND sno != {$sno} AND verification = 'verified' AND (tedit IS NULL OR tedit = '')");
                $vcnt = ($othVer && $vr = $othVer->fetch_assoc()) ? intval($vr['vcnt']) : 0;
                if ($vcnt === 0) {
                    $krfconn->query("UPDATE krf_provisional_sales_order SET status = 'Pending Approval', updated_at = NOW() WHERE psono = '{$escInv}' AND status = 'Confirmed'");
                }
            }
        }

        sendJsonResponse(['status' => 'success', 'message' => 'Payment has been rejected with remark successfully.']);
    } else {
        sendJsonResponse(['status' => 'error', 'message' => 'Failed to reject payment: ' . $stmt->error]);
        $stmt->close();
    }
    exit;
}

if ($action == "update_receipt") {
    $sno = intval($_POST['sno'] ?? 0);
    $psono = trim($_POST['psono'] ?? '');
    $companyid = $_POST['companyid'] ?? 19;
    $subcompanyid = $_POST['subcompanyid'] ?? 12;
    $projectid = $_POST['projectid'] ?? 17;
    $verify_now = trim($_POST['verify_now'] ?? 'no');
    $loginname = trim($_POST['loginname'] ?? ($_SESSION['loginname'] ?? 'Admin'));
    $utrnumber = trim($_POST['utrnumber'] ?? '');
    $paymentdate = trim($_POST['paymentdate'] ?? '');
    $sourceofpayment = trim($_POST['sourceofpayment'] ?? '');
    $receivedby = trim($_POST['receivedby'] ?? '');
    $receivedlocation = trim($_POST['receivedlocation'] ?? '');
    $advanceamount = isset($_POST['advanceamount']) ? floatval($_POST['advanceamount']) : null;
    if ($sno <= 0) {
        sendJsonResponse(['status' => 'error', 'message' => 'Invalid payment record ID.']);
    }

    // Resolve transaction details from DB if missing
    $chkTrans = $krfconn->query("SELECT companyid, subcompanyid, projectid, invoiceno, receiptpath FROM advance_payment_transactions WHERE sno = {$sno} AND (tedit IS NULL OR tedit = '') LIMIT 1");
    $existingTransRow = ($chkTrans && $chkTrans->num_rows > 0) ? $chkTrans->fetch_assoc() : null;
    if ($existingTransRow) {
        if (empty($psono) && !empty($existingTransRow['invoiceno'])) {
            $psono = trim($existingTransRow['invoiceno']);
        }
        if (!empty($existingTransRow['companyid'])) {
            $companyid = $existingTransRow['companyid'];
        }
        if (!empty($existingTransRow['subcompanyid'])) {
            $subcompanyid = $existingTransRow['subcompanyid'];
        }
        if (!empty($existingTransRow['projectid']) && empty($_POST['projectid'])) {
            $projectid = $existingTransRow['projectid'];
        }
    }

    // Strict mandatory field validations
    if ($advanceamount !== null && $advanceamount <= 0) {
        sendJsonResponse(['status' => 'error', 'message' => 'Amount received must be greater than 0.']);
    }
    if (empty($paymentdate)) {
        sendJsonResponse(['status' => 'error', 'message' => 'Transaction Date is required. Please provide a valid date.']);
    }

    // Normalize payment date to Y-m-d format for database safety
    $parsedDate = strtotime($paymentdate);
    if ($parsedDate !== false) {
        $paymentdate = date('Y-m-d', $parsedDate);
    }

    if (empty($sourceofpayment)) {
        sendJsonResponse(['status' => 'error', 'message' => 'Mode of payment is required.']);
    }
    if (strtolower($sourceofpayment) === 'cash') {
        if (empty($receivedby)) {
            sendJsonResponse(['status' => 'error', 'message' => 'Received By (Person Name) is required for Cash payment.']);
        }
        if (empty($receivedlocation)) {
            sendJsonResponse(['status' => 'error', 'message' => 'Received Location is required for Cash payment.']);
        }
    } else {
        if (empty($utrnumber)) {
            sendJsonResponse(['status' => 'error', 'message' => "Transaction No / UTR No is required for {$sourceofpayment} payment."]);
        }
    }

    $dbPath = null;
    $hasNewFile = (isset($_FILES['receiptfile']) && $_FILES['receiptfile']['error'] === UPLOAD_ERR_OK);

    if ($hasNewFile) {
        $targetDir = '../../attachementfile/' . $companyid . '/' . $subcompanyid . '/' . $projectid . '/OrderPaymentReceipt/' . date('Y-m-d') . "/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $ext = strtolower(pathinfo($_FILES['receiptfile']['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
        if (!in_array($ext, $allowedExts)) {
            sendJsonResponse(['status' => 'error', 'message' => 'Invalid file format. Only JPG, PNG, WEBP, and PDF files are allowed.']);
        }

        $filename = time() . '_' . rand(1000, 9999) . '.' . $ext;
        $targetPath = $targetDir . $filename;

        if (move_uploaded_file($_FILES['receiptfile']['tmp_name'], $targetPath)) {
            $dbPath = 'attachementfile/' . $companyid . '/' . $subcompanyid . '/' . $projectid . '/OrderPaymentReceipt/' . date('Y-m-d') . '/' . $filename;
        } else {
            sendJsonResponse(['status' => 'error', 'message' => 'Failed to move uploaded receipt to destination folder.']);
        }
    } else {
        // Check if an existing document is present on the payment record
        $chkRes = $krfconn->query("SELECT receiptpath FROM advance_payment_transactions WHERE sno = {$sno} AND (tedit IS NULL OR tedit = '') LIMIT 1");
        $existingReceipt = ($chkRes && $r = $chkRes->fetch_assoc()) ? trim($r['receiptpath'] ?? '') : '';
        if (empty($existingReceipt)) {
            sendJsonResponse(['status' => 'error', 'message' => 'Payment proof / receipt document is compulsory. Please select and upload a receipt image or PDF.']);
        }
    }

    try {
        // Build update parameters
        $setClauses = [];

        // Check & match provisional sales order if invoiceno / psono exists
        $matchedOrder = null;
        if (!empty($psono)) {
            $escPso = $krfconn->real_escape_string($psono);
            $psoRes = $krfconn->query("SELECT psono, clientid, clientname, '' as total_amount, status FROM krf_provisional_sales_order WHERE psono = '{$escPso}' LIMIT 1");
            if ($psoRes && $psoRow = $psoRes->fetch_assoc()) {
                $matchedOrder = $psoRow;
                if (!empty($psoRow['clientid'])) {
                    $setClauses[] = "clientid = '" . $krfconn->real_escape_string($psoRow['clientid']) . "'";
                }
                if (!empty($psoRow['clientname'])) {
                    $setClauses[] = "clientname = '" . $krfconn->real_escape_string($psoRow['clientname']) . "'";
                }
                $setClauses[] = "invoiceno = '{$escPso}'";
            }
        }

        if ($advanceamount !== null && $advanceamount > 0) {
            $setClauses[] = "advanceamount = " . floatval($advanceamount);
        }

        if (!empty($sourceofpayment)) {
            $setClauses[] = "sourceofpayment = '" . $krfconn->real_escape_string($sourceofpayment) . "'";
        }

        if (isset($_POST['utrnumber'])) {
            $setClauses[] = "utrnumber = '" . $krfconn->real_escape_string($utrnumber) . "'";
        }

        if (!empty($paymentdate)) {
            $setClauses[] = "paymentdate = '" . $krfconn->real_escape_string($paymentdate) . "'";
        }

        if (isset($_POST['receivedby'])) {
            $setClauses[] = "receivedby = '" . $krfconn->real_escape_string($receivedby) . "'";
        }

        if (isset($_POST['receivedlocation'])) {
            $setClauses[] = "receivedlocation = '" . $krfconn->real_escape_string($receivedlocation) . "'";
        }

        if ($dbPath !== null) {
            $setClauses[] = "receiptpath = '" . $krfconn->real_escape_string($dbPath) . "'";
        }

        if ($verify_now === 'yes') {
            $loginEsc = $krfconn->real_escape_string($loginname);
            $setClauses[] = "verification = 'verified'";
            $setClauses[] = "verificationby = '{$loginEsc}'";
            $setClauses[] = "verificationat = NOW()";
            $hasCols = (getRejectionColumnsClause($krfconn) !== "NULL AS rejection_remark, NULL AS rejectedby, NULL AS rejectedat,");
            if ($hasCols) {
                $setClauses[] = "rejection_remark = NULL";
                $setClauses[] = "rejectedby = NULL";
                $setClauses[] = "rejectedat = NULL";
            }
        }

        if (empty($setClauses)) {
            sendJsonResponse(['status' => 'error', 'message' => 'No changes provided to update.']);
        }

        $sql = "UPDATE advance_payment_transactions SET " . implode(", ", $setClauses) . " WHERE sno = " . intval($sno) . " AND (tedit IS NULL OR tedit = '')";
        $updateResult = $krfconn->query($sql);

        if ($updateResult) {
            if ($verify_now === 'yes') {
                // Resolve psono if not provided
                $targetPsono = $psono;
                if (empty($targetPsono)) {
                    $chkPso = $krfconn->query("SELECT invoiceno FROM advance_payment_transactions WHERE sno = {$sno} AND (tedit IS NULL OR tedit = '') LIMIT 1");
                    if ($chkPso && $pRow = $chkPso->fetch_assoc()) {
                        $targetPsono = trim($pRow['invoiceno'] ?? '');
                    }
                }

                // Update provisional sales order status to "Confirmed"
                if (!empty($targetPsono)) {
                    $escPsono = $krfconn->real_escape_string($targetPsono);
                    $krfconn->query("UPDATE krf_provisional_sales_order SET status = 'Confirmed', updated_at = NOW() WHERE psono = '{$escPsono}' AND status != 'Confirmed' AND status != 'Cancelled'");
                    $krfconn->query("UPDATE krf_provisional_sales_order_items SET status = 'Confirmed' WHERE psono = '{$escPsono}' AND (status IS NULL OR status = '' OR (status != 'Confirmed' AND status != 'Cancelled'))");
                    $krfconn->query("UPDATE krf_sales_order_items soi INNER JOIN krf_provisional_sales_order_items poi ON soi.psono = poi.psono AND (soi.stockorder_no = poi.stockorder_no OR (soi.product_id = poi.product_id AND soi.quantity = poi.quantity)) SET soi.roundoffamount = poi.roundoffamount WHERE soi.psono = '{$escPsono}'");
                    $krfconn->query("UPDATE krf_production_stockorder SET status = 'Confirmed', updated_at = NOW() WHERE psono = '{$escPsono}' AND (LOWER(status) = 'unconfirmed' OR status = '' OR status IS NULL)");
                    $krfconn->query("UPDATE krf_productionorder_consumed SET confirmstatus = 'Confirmed' WHERE psono = '{$escPsono}' AND (LOWER(confirmstatus) = 'unconfirmed' OR LOWER(confirmstatus) = 'pending' OR confirmstatus = '' OR confirmstatus IS NULL)");
                }

                // Send WhatsApp Notification with Final Sale Invoice & Product Details using psono, sno, and loginname
                $waMsg = "";
                try {
                    $waSent = sendPaymentVerifiedSalesInvoiceWhatsApp($krfconn, $targetPsono, $sno, $loginname);
                    if ($waSent) {
                        $waMsg = " WhatsApp invoice with product details sent to client.";
                    }
                } catch (Throwable $e) {
                    error_log("WhatsApp dispatch error: " . $e->getMessage());
                }

                $msg = 'Transaction details confirmed & payment successfully verified!' . $waMsg;
            } else {
                // Update Details Only: If the payment was already verified, maintain Confirmed; if unverified, ensure order is Pending Approval
                if (empty($targetPsono)) {
                    $chkPso = $krfconn->query("SELECT invoiceno FROM advance_payment_transactions WHERE sno = {$sno} AND (tedit IS NULL OR tedit = '') LIMIT 1");
                    if ($chkPso && $pRow = $chkPso->fetch_assoc()) {
                        $targetPsono = trim($pRow['invoiceno'] ?? '');
                    }
                }
                if (!empty($targetPsono)) {
                    $escPsono = $krfconn->real_escape_string($targetPsono);
                    $chkVer = $krfconn->query("SELECT verification FROM advance_payment_transactions WHERE sno = {$sno} AND (tedit IS NULL OR tedit = '') LIMIT 1");
                    $isAlreadyVer = ($chkVer && $vr = $chkVer->fetch_assoc()) ? (strtolower($vr['verification'] ?? '') === 'verified') : false;
                    if ($isAlreadyVer) {
                        $krfconn->query("UPDATE krf_provisional_sales_order SET status = 'Confirmed', updated_at = NOW() WHERE psono = '{$escPsono}' AND status != 'Confirmed' AND status != 'Cancelled'");
                    }
                }
                $msg = 'Transaction details and receipt updated successfully!';
            }
            sendJsonResponse([
                'status' => 'success', 
                'message' => $msg, 
                'receiptpath' => $dbPath,
                'matched_order' => $matchedOrder ? [
                    'psono' => $matchedOrder['psono'],
                    'clientname' => $matchedOrder['clientname'],
                    'total_amount' => $matchedOrder['total_amount']
                ] : null
            ]);
        } else {
            sendJsonResponse(['status' => 'error', 'message' => 'Database update error: ' . $krfconn->error]);
        }
    } catch (Throwable $e) {
        sendJsonResponse(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
    }
    exit;
}

if ($action == "sync_order_status" || $action == "reverify_payment") {
    $sno = intval($_POST['sno'] ?? 0);
    $psono = trim($_POST['psono'] ?? '');
    $loginname = trim($_POST['loginname'] ?? ($_SESSION['loginname'] ?? 'Admin'));

    if (empty($psono) && $sno > 0) {
        $chkPso = $krfconn->query("SELECT invoiceno FROM advance_payment_transactions WHERE sno = {$sno} AND (tedit IS NULL OR tedit = '') LIMIT 1");
        if ($chkPso && $pRow = $chkPso->fetch_assoc()) {
            $psono = trim($pRow['invoiceno'] ?? '');
        }
    }

    if (empty($psono)) {
        sendJsonResponse(['status' => 'error', 'message' => 'Provisional order number is required for syncing.']);
    }

    $escPsono = $krfconn->real_escape_string($psono);
    $loginEsc = $krfconn->real_escape_string($loginname);

    // 1. Ensure advance_payment_transactions is marked verified
    if ($sno > 0) {
        $krfconn->query("UPDATE advance_payment_transactions SET verification = 'verified', verificationby = IFNULL(verificationby, '{$loginEsc}'), verificationat = IFNULL(verificationat, NOW()) WHERE sno = {$sno} AND (tedit IS NULL OR tedit = '')");
    } else {
        $krfconn->query("UPDATE advance_payment_transactions SET verification = 'verified', verificationby = IFNULL(verificationby, '{$loginEsc}'), verificationat = IFNULL(verificationat, NOW()) WHERE invoiceno = '{$escPsono}' AND (tedit IS NULL OR tedit = '')");
    }

    // 2. Sync krf_provisional_sales_order to Confirmed
    $krfconn->query("UPDATE krf_provisional_sales_order SET status = 'Confirmed', updated_at = NOW() WHERE psono = '{$escPsono}' AND status != 'Cancelled'");

    // 3. Sync krf_production_stockorder to Confirmed
    $krfconn->query("UPDATE krf_production_stockorder SET status = 'Confirmed', updated_at = NOW() WHERE psono = '{$escPsono}' AND (LOWER(status) NOT IN ('cancelled', 'remove', 'removed') OR status IS NULL OR status = '')");

    // 4. Sync krf_productionorder_consumed to Confirmed
    $krfconn->query("UPDATE krf_productionorder_consumed SET confirmstatus = 'Confirmed' WHERE psono = '{$escPsono}' AND LOWER(confirmstatus) != 'cancelled'");

    sendJsonResponse([
        'status' => 'success',
        'message' => "Order #{$psono}, Production Stock Orders & Consumed Orders successfully re-verified and updated to 'Confirmed'!"
    ]);
}

if ($action == "sync_all_verified_orders") {
    $loginname = trim($_POST['loginname'] ?? ($_SESSION['loginname'] ?? 'Admin'));
    $loginEsc = $krfconn->real_escape_string($loginname);

    // 1. Sync all provisional sales orders with verified payments
    $krfconn->query("
        UPDATE krf_provisional_sales_order pso
        JOIN advance_payment_transactions apt ON apt.invoiceno = pso.psono
        SET pso.status = 'Confirmed', pso.updated_at = NOW()
        WHERE apt.verification = 'verified'
          AND (apt.tedit IS NULL OR apt.tedit = '')
          AND pso.status != 'Confirmed'
          AND pso.status != 'Cancelled'
    ");
    $syncedPso = $krfconn->affected_rows;

    // 2. Sync all production stock orders
    $krfconn->query("
        UPDATE krf_production_stockorder psto
        JOIN advance_payment_transactions apt ON apt.invoiceno = psto.psono
        SET psto.status = 'Confirmed', psto.updated_at = NOW()
        WHERE apt.verification = 'verified'
          AND (apt.tedit IS NULL OR apt.tedit = '')
          AND psto.status != 'Confirmed'
          AND LOWER(psto.status) NOT IN ('cancelled', 'remove', 'removed')
    ");
    $syncedStock = $krfconn->affected_rows;

    // 3. Sync all productionorder_consumed
    $krfconn->query("
        UPDATE krf_productionorder_consumed poc
        JOIN advance_payment_transactions apt ON apt.invoiceno = poc.psono
        SET poc.confirmstatus = 'Confirmed'
        WHERE apt.verification = 'verified'
          AND (apt.tedit IS NULL OR apt.tedit = '')
          AND poc.confirmstatus != 'Confirmed'
          AND LOWER(poc.confirmstatus) != 'cancelled'
    ");
    $syncedConsumed = $krfconn->affected_rows;

    sendJsonResponse([
        'status' => 'success',
        'message' => "Sync complete! Successfully updated {$syncedPso} provisional orders, {$syncedStock} production stock orders, and {$syncedConsumed} consumed order records."
    ]);
}

if ($action == "export_payments") {
    $projectid = $_GET['projectid'] ?? $_POST['projectid'] ?? '';
    $productid = trim($_GET['productid'] ?? $_POST['productid'] ?? '');
    $clientid = trim($_GET['clientid'] ?? $_POST['clientid'] ?? '');
    $psodate = trim($_GET['psodate'] ?? $_POST['psodate'] ?? '');
    $date_from = trim($_GET['date_from'] ?? $_POST['date_from'] ?? '');
    $date_to = trim($_GET['date_to'] ?? $_POST['date_to'] ?? '');
    $verification_status = trim($_GET['verification_status'] ?? $_POST['verification_status'] ?? 'all');
    $search = trim($_GET['search'] ?? $_POST['search'] ?? '');

    $whereConditions = ["(apt.tedit IS NULL OR apt.tedit = '')"];

    if (!empty($projectid)) {
        $pIdEsc = $krfconn->real_escape_string($projectid);
        $whereConditions[] = "(apt.projectid = '{$pIdEsc}' OR apt.projectid IS NULL OR apt.projectid = 0)";
    }

    if (!empty($clientid)) {
        $cIdEsc = $krfconn->real_escape_string($clientid);
        $whereConditions[] = "(apt.clientid = '{$cIdEsc}' OR pso.clientid = '{$cIdEsc}')";
    }

    if (!empty($psodate)) {
        $psoDateEsc = $krfconn->real_escape_string($psodate);
        $whereConditions[] = "DATE(pso.created_at) = '{$psoDateEsc}'";
    }

    if (!empty($date_from)) {
        $dFromEsc = $krfconn->real_escape_string($date_from);
        $whereConditions[] = "apt.paymentdate >= '{$dFromEsc}'";
    }

    if (!empty($date_to)) {
        $dToEsc = $krfconn->real_escape_string($date_to);
        $whereConditions[] = "apt.paymentdate <= '{$dToEsc}'";
    }

    if (!empty($productid)) {
        $prodEsc = $krfconn->real_escape_string($productid);
        $whereConditions[] = "FIND_IN_SET('{$prodEsc}', item_sum.product_ids)";
    }

    if (!empty($search)) {
        $sEsc = $krfconn->real_escape_string($search);
        $whereConditions[] = "(
            apt.invoiceno LIKE '%{$sEsc}%' OR 
            apt.clientname LIKE '%{$sEsc}%' OR 
            apt.utrnumber LIKE '%{$sEsc}%' OR 
            apt.paymentref_no LIKE '%{$sEsc}%' OR 
            apt.receivedby LIKE '%{$sEsc}%' OR 
            apt.receivedlocation LIKE '%{$sEsc}%' OR 
            apt.remarks LIKE '%{$sEsc}%' OR 
            pso.mobileno LIKE '%{$sEsc}%' OR 
            item_sum.product_names LIKE '%{$sEsc}%'
        )";
    }

    $whereClause = implode(" AND ", $whereConditions);
    $rejectionCols = getRejectionColumnsClause($krfconn);

    $query = "
        SELECT 
            apt.sno,
            apt.clientid,
            COALESCE(apt.clientname, pso.clientname, 'Unknown Client') AS clientname,
            apt.advanceamount,
            apt.utrnumber,
            apt.paymentdate,
            apt.purposeofpayment,
            apt.invoiceno,
            apt.sourceofpayment,
            apt.receivedtoaccountid,
            apt.paymentref_no,
            apt.receivedby,
            apt.receivedlocation,
            apt.receiptpath,
            apt.remarks,
            {$rejectionCols}
            COALESCE(apt.verification, 'unverified') AS verification,
            apt.verificationby,
            apt.verificationat,
            pso.created_at AS pso_created_at,
            pso.created_by AS pso_created_by,
            pso.expecteddelivery AS pso_expecteddelivery,
            pso.status AS pso_status,
            pso.mobileno AS pso_mobileno,
            pso.districtname AS pso_districtname,
            pso.statename AS pso_statename,
            COALESCE(item_sum.total_order_amount, 0) AS pso_total_amount,
            item_sum.product_names,
            item_sum.total_plants
        FROM advance_payment_transactions apt
        LEFT JOIN krf_provisional_sales_order pso ON pso.psono = apt.invoiceno
        LEFT JOIN (
            SELECT 
                psono, 
                SUM(amount) AS total_order_amount,
                SUM(quantity) AS total_plants,
                GROUP_CONCAT(DISTINCT product_name SEPARATOR ' | ') AS product_names,
                GROUP_CONCAT(DISTINCT product_id SEPARATOR ',') AS product_ids
            FROM krf_provisional_sales_order_items
            GROUP BY psono
        ) item_sum ON item_sum.psono = apt.invoiceno
        WHERE {$whereClause}
        ORDER BY apt.sno DESC
    ";

    try {
        $result = $krfconn->query($query);
    } catch (Throwable $e) {
        // If query failed (e.g. schema/column mismatch), fallback with NULL rejection columns
        $fallbackQuery = str_replace($rejectionCols, "NULL AS rejection_remark, NULL AS rejectedby, NULL AS rejectedat,", $query);
        try {
            $result = $krfconn->query($fallbackQuery);
        } catch (Throwable $e2) {
            sendJsonResponse([
                'status' => 'error',
                'message' => 'Query execution failed: ' . $e2->getMessage()
            ]);
        }
    }
    $tabSuffix = ($verification_status === 'unverified') ? 'Unverified' : (($verification_status === 'verified') ? 'Verified' : (($verification_status === 'rejected') ? 'Rejected' : 'All'));
    $filename = "Payment_Verification_Report_{$tabSuffix}_" . date('Y-m-d_His') . ".csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    // Output UTF-8 BOM so Excel opens with proper encoding
    fputs($output, "\xEF\xBB\xBF");

    // Cache user names map for fast resolution
    $userMap = [];
    if (isset($authconn) && $authconn) {
        $uRes = $authconn->query("SELECT loginid, loginname FROM login_user_master");
        if ($uRes) {
            while ($uRow = $uRes->fetch_assoc()) {
                $userMap[$uRow['loginid']] = $uRow['loginname'];
            }
        }
    }

    // CSV Header Row (Separate Columns, with Verification & Rejection details at the end)
    fputcsv($output, [
        'Sr No',
        'Provisional Order No',
        'Generated By',
        'Generated At (Order Date & Time)',
        'Client ID',
        'Client Name',
        'Client Mobile No',
        'District',
        'State',
        'Amount Received (INR)',
        'Mode of Payment',
        'UTR / Ref / Txn No',
        'Transaction Date',
        'Received By',
        'Received Location',
        'Purpose of Payment',
        'Order Total Amount (INR)',
        'Total Order Plants',
        'Ordered Products List',
        'Remarks / Notes',
        'Verification Status',
        'Verified By',
        'Verification Date & Time',
        'Rejection Remark',
        'Rejected By',
        'Rejection Date & Time'
    ]);

    $srNo = 1;
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $verStatus = strtolower($row['verification'] ?? 'unverified');
            $isVerified = ($verStatus === 'verified');
            $isRejected = ($verStatus === 'rejected');
            if ($verification_status === 'unverified' && ($isVerified || $isRejected)) continue;
            if ($verification_status === 'verified' && !$isVerified) continue;
            if ($verification_status === 'rejected' && !$isRejected) continue;

            $rawCreatedBy = trim($row['pso_created_by'] ?? ($row['allocatedby'] ?? ''));
            $resolvedCreatedBy = $userMap[$rawCreatedBy] ?? $rawCreatedBy;
            if (empty($resolvedCreatedBy)) {
                $resolvedCreatedBy = 'Admin';
            }

            $statusText = $isVerified ? 'Verified' : ($isRejected ? 'Rejected' : 'Unverified');
            $psoNo = !empty($row['invoiceno']) ? '#' . $row['invoiceno'] : 'Direct / General Advance';
            $orderDate = !empty($row['pso_created_at']) ? date('d-m-Y h:i A', strtotime($row['pso_created_at'])) : (!empty($row['allocated_datetime']) ? date('d-m-Y h:i A', strtotime($row['allocated_datetime'])) : 'N/A');
            $txnDate = !empty($row['paymentdate']) ? date('d-m-Y', strtotime($row['paymentdate'])) : '';
            $verifiedAt = !empty($row['verificationat']) ? date('d-m-Y h:i A', strtotime($row['verificationat'])) : 'N/A';
            $rejectedAt = !empty($row['rejectedat']) ? date('d-m-Y h:i A', strtotime($row['rejectedat'])) : 'N/A';
            $products = !empty($row['product_names']) ? str_replace('|', ', ', $row['product_names']) : 'General Advance';

            fputcsv($output, [
                $srNo++,
                $psoNo,
                $resolvedCreatedBy,
                $orderDate,
                $row['clientid'] ?? '',
                $row['clientname'] ?? '',
                $row['pso_mobileno'] ?? '',
                $row['pso_districtname'] ?? '',
                $row['pso_statename'] ?? '',
                number_format((float)($row['advanceamount'] ?? 0), 2, '.', ''),
                $row['sourceofpayment'] ?? 'Cash',
                $row['utrnumber'] ?: ($row['paymentref_no'] ?? ''),
                $txnDate,
                $row['receivedby'] ?? '',
                $row['receivedlocation'] ?? '',
                $row['purposeofpayment'] ?? 'Advance Payment',
                number_format((float)($row['pso_total_amount'] ?? 0), 2, '.', ''),
                $row['total_plants'] ?? '0',
                $products,
                $row['remarks'] ?? '',
                $statusText,
                $row['verificationby'] ?: 'N/A',
                $verifiedAt,
                $row['rejection_remark'] ?? '',
                $row['rejectedby'] ?: 'N/A',
                $rejectedAt
            ]);
        }
    }
    fclose($output);
    exit;
}

sendJsonResponse(['status' => 'error', 'message' => 'Invalid action request.']);