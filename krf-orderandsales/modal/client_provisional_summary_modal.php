<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../../connection.php');
require_once(__DIR__ . '/../../loginCell/functions/login_functions.php');
require_once(__DIR__ . '/../functions/orders_functions.php');
require_once(__DIR__ . '/../functions/client_functions.php');

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if ($action === 'GetClientOrdersPendingSummary') {
    $companyid = $_POST['companyid'] ?? ($_GET['companyid'] ?? 0);
    $subcompanyid = $_POST['subcompanyid'] ?? ($_GET['subcompanyid'] ?? 0);
    $projectid = $_POST['projectid'] ?? ($_GET['projectid'] ?? 0);
    $clientid = trim($_POST['clientid'] ?? ($_GET['clientid'] ?? ''));
    $mobileno = trim($_POST['mobileno'] ?? ($_GET['mobileno'] ?? ''));
    $clientname = trim($_POST['clientname'] ?? ($_GET['clientname'] ?? ''));

    $orders = [];
    
    // 1. Fetch orders by clientid if present
    if (!empty($clientid) && $clientid !== '0' && $clientid !== 'undefined') {
        $orders = getProvisionalSalesOrders($krfconn, $companyid, $subcompanyid, $projectid, "clientid", $clientid);
    }
    
    // 2. If no orders found or no clientid, try with mobileno
    if (empty($orders) && !empty($mobileno)) {
        $cleanMobile = preg_replace('/\D/', '', $mobileno);
        if (strlen($cleanMobile) >= 10) {
            $orders = getProvisionalSalesOrders($krfconn, $companyid, $subcompanyid, $projectid, "mobileno", substr($cleanMobile, -10));
        }
    }
    
    // 3. Fallback to clientname if still empty
    if (empty($orders) && !empty($clientname)) {
        $orders = getProvisionalSalesOrders($krfconn, $companyid, $subcompanyid, $projectid, "clientname", $clientname);
    }

    $draftList = [];
    $confirmedList = [];

    $draftTotal = 0.0;
    $draftPaid = 0.0;
    $draftPending = 0.0;

    $confirmedTotal = 0.0;
    $confirmedPaid = 0.0;
    $confirmedPending = 0.0;

    if (!empty($orders) && is_array($orders)) {
        foreach ($orders as $ord) {
            $status = trim($ord['status'] ?? 'Draft');
            $statusLower = strtolower($status);
            $ordPsono = trim($ord['psono'] ?? '');

            $totalAmt = floatval($ord['total_amount'] ?? 0);
            $paidAmt = floatval($ord['paid_amount'] ?? 0);

            // Check if active dispatched invoice or sales order exists for this provisional order
            $dispInvoiceno = '';
            $dispSono = '';
            $isDispatchedOrder = false;
            $dispStatus = '';

            if (!empty($ordPsono)) {
                $chkSi = $krfconn->query("SELECT invoiceno, payableamount, invoiceamount, status FROM sales_invoice WHERE psono = '" . $krfconn->real_escape_string($ordPsono) . "' AND LOWER(IFNULL(status, '')) != 'cancelled' ORDER BY id DESC LIMIT 1");
                if ($chkSi && $siR = $chkSi->fetch_assoc()) {
                    $isDispatchedOrder = true;
                    $dispInvoiceno = $siR['invoiceno'];
                    $dispStatus = strtolower(trim((string)($siR['status'] ?? '')));
                    $invAmt = floatval($siR['payableamount'] > 0 ? $siR['payableamount'] : $siR['invoiceamount']);
                    if ($invAmt > 0) {
                        $totalAmt = $invAmt;
                    }
                } else {
                    $chkSo = $krfconn->query("SELECT sono, dinvoiceno, total_amount, grand_total, status FROM krf_sales_order WHERE psono = '" . $krfconn->real_escape_string($ordPsono) . "' ORDER BY id DESC LIMIT 1");
                    if ($chkSo && $soR = $chkSo->fetch_assoc()) {
                        $dispSono = $soR['sono'] ?? '';
                        $dispInvoiceno = $soR['dinvoiceno'] ?? '';
                        $dispStatus = strtolower(trim((string)($soR['status'] ?? '')));
                        $soGrand = floatval($soR['grand_total'] > 0 ? $soR['grand_total'] : $soR['total_amount']);
                        if ($soGrand > 0) {
                            $totalAmt = $soGrand;
                            $isDispatchedOrder = true;
                        }
                    }
                }

                // If dispatched/sales order exists, fetch full payments across psono, sono, dinvoiceno
                if ($isDispatchedOrder) {
                    $pArr = ["'" . $krfconn->real_escape_string($ordPsono) . "'"];
                    if (!empty($dispSono)) $pArr[] = "'" . $krfconn->real_escape_string($dispSono) . "'";
                    if (!empty($dispInvoiceno)) $pArr[] = "'" . $krfconn->real_escape_string($dispInvoiceno) . "'";
                    $pIn = implode(',', array_unique($pArr));
                    $totPRes = $krfconn->query("SELECT IFNULL(SUM(advanceamount), 0) AS actual_paid FROM advance_payment_transactions WHERE invoiceno IN ({$pIn}) AND (tedit IS NULL OR tedit = '')");
                    if ($totPRes && $tpRow = $totPRes->fetch_assoc()) {
                        $paidAmt = floatval($tpRow['actual_paid'] ?? 0);
                    }
                }
            }

            // If full order dispatched or amount received is greater than or equal to sale invoice, pending balance is 0.00
            $isFullyDispatched = in_array($dispStatus, ['fulldispatch', 'completed', 'delivered'], true) || in_array($statusLower, ['fulldispatch', 'completed', 'delivered'], true);
            if ($isFullyDispatched || $paidAmt >= $totalAmt) {
                $balAmt = 0.0;
            } else {
                $balAmt = max(0.0, round($totalAmt - $paidAmt, 2));
            }

            $ord['total_amount'] = $totalAmt;
            $ord['paid_amount'] = $paidAmt;
            $ord['net_balance'] = $balAmt;
            $ord['is_dispatch_invoice'] = $isDispatchedOrder;
            $ord['dinvoiceno'] = $dispInvoiceno;
            $ord['sono'] = $dispSono;

            if ($statusLower === 'confirmed') {
                $confirmedList[] = $ord;
                $confirmedTotal += $totalAmt;
                $confirmedPaid += $paidAmt;
                $confirmedPending += $balAmt;
            } else if ($statusLower !== 'cancelled') {
                // Draft, Pending Approval, Emergency Sowing, etc.
                $draftList[] = $ord;
                $draftTotal += $totalAmt;
                $draftPaid += $paidAmt;
                $draftPending += $balAmt;
            }
        }
    }

    $totalPendingBalance = $draftPending + $confirmedPending;

    echo json_encode([
        'status' => 'success',
        'clientid' => $clientid,
        'clientname' => $clientname,
        'mobileno' => $mobileno,
        'total_pending_balance' => $totalPendingBalance,
        'formatted_total_pending' => number_format($totalPendingBalance, 2),
        'draft' => [
            'count' => count($draftList),
            'total_amount' => $draftTotal,
            'formatted_total_amount' => number_format($draftTotal, 2),
            'paid_amount' => $draftPaid,
            'formatted_paid_amount' => number_format($draftPaid, 2),
            'pending_balance' => $draftPending,
            'formatted_pending_balance' => number_format($draftPending, 2),
            'orders' => $draftList
        ],
        'confirmed' => [
            'count' => count($confirmedList),
            'total_amount' => $confirmedTotal,
            'formatted_total_amount' => number_format($confirmedTotal, 2),
            'paid_amount' => $confirmedPaid,
            'formatted_paid_amount' => number_format($confirmedPaid, 2),
            'pending_balance' => $confirmedPending,
            'formatted_pending_balance' => number_format($confirmedPending, 2),
            'orders' => $confirmedList
        ]
    ]);
    exit;
}

echo json_encode([
    'status' => 'error',
    'message' => 'Invalid action'
]);
exit;
