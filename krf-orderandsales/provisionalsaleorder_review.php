<?php
date_default_timezone_set('Asia/Kolkata');
include('../header.php');
?>
<!-- Immediate Early Page Loader (SweetAlert2) & Early Select2 library -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.12.4/dist/sweetalert2.all.min.js"></script>
<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js"></script>
<?php
if (ob_get_level() > 0) {
    ob_flush();
}
flush();

include('functions/orders_functions.php');
include('functions/client_functions.php');

// Auto-cancel draft provisional orders exceeding 48 hours & release reserved stock
autoCancelExpiredDraftOrders($krfconn);

$psnolistDetailed = getPendingProvisionalSalesOrdersDetailed($krfconn, $authconn, $companyid, $subcompanyid, $projectid, "all", "all", $loginroleid, $loginid);

/* Results are pre-sorted newest first via SQL query (ORDER BY o.created_at DESC, o.psono DESC) */

// Fetch all production stock orders for project in one batch (including sowingstatus & scionsowingstatus)
$prodStockMap = [];
$stkRes = $krfconn->query("
    SELECT stockorder_no, psono, clientid, productid, product_name, quantity, sowingdate, scionsowingdate, status, workingstatus, sowingstatus, scionsowingstatus
    FROM krf_production_stockorder 
    WHERE (projectid = " . intval($projectid) . " OR projectid IS NULL OR projectid = 0)
      AND stockorder_no != ''
");
if ($stkRes) {
    while ($srow = $stkRes->fetch_assoc()) {
        $stkKey = trim((string)$srow['stockorder_no']);
        if (!empty($stkKey)) {
            $prodStockMap[$stkKey] = $srow;
        }
    }
}

// Fetch user names lookup map from auth database
$userLookupMap = [];
if (isset($authconn) && $authconn) {
    $uRes = $authconn->query("SELECT loginid, loginname FROM login_user_master");
    if ($uRes) {
        while ($uRow = $uRes->fetch_assoc()) {
            $lid = trim((string)$uRow['loginid']);
            $lname = trim((string)$uRow['loginname']);
            if (!empty($lid)) {
                $userLookupMap[$lid] = $lname;
            }
        }
    }
}

// Fetch latest payment details for provisional orders
$paymentDetailsMap = [];
$payRes = $krfconn->query("
    SELECT invoiceno, advanceamount, paymentdate, receivedby, allocatedby, allocated_datetime
    FROM advance_payment_transactions 
    WHERE invoiceno != '' AND (tedit IS NULL OR tedit = '')
    ORDER BY sno ASC
");
if ($payRes) {
    while ($pRow = $payRes->fetch_assoc()) {
        $pInv = trim((string)$pRow['invoiceno']);
        if (!empty($pInv)) {
            $paymentDetailsMap[$pInv] = $pRow;
        }
    }
}

// Fetch all production consumed records for project
$prodConsumedMap = [];
$consRes = $krfconn->query("
    SELECT psono, stockorderno, original_stockorder_no, consumed_qty
    FROM krf_productionorder_consumed 
    WHERE (projectid = " . intval($projectid) . " OR projectid IS NULL OR projectid = 0) AND IFNULL(tedit, '') = ''
");
if ($consRes) {
    while ($crow = $consRes->fetch_assoc()) {
        $p = trim((string)($crow['psono'] ?? ''));
        if (!empty($p)) {
            $prodConsumedMap[$p][] = $crow;
        }
    }
}

// Fetch all delivery addresses for the project indexed by psono
$delivLocations = [];
$delQuery = "SELECT psono, sono, clientid, deliveryaddress, dest_state, dest_district, dest_city, dest_pincode, distance, deliveryqty 
             FROM krf_deliveryaddress_location 
             WHERE (tedit IS NULL OR tedit='') AND projectid = " . intval($projectid);
$delRes = $krfconn->query($delQuery);
if ($delRes) {
    while ($drow = $delRes->fetch_assoc()) {
        $p = trim((string)($drow['psono'] ?? ''));
        if (!empty($p)) {
            $delivLocations[$p][] = $drow;
        }
    }
}

// Fetch all dispatched item and order records in batch
$itemDispatches = [];
$orderDispatches = [];

// 1. From krf_sales_order_items
$soiDispQ = "SELECT psono, product_id, stockorder_no, status, dispatchedqty, quantity, updateamended, invoicedate 
             FROM krf_sales_order_items 
             WHERE psono != '' AND (LOWER(status) IN ('dispatched', 'fulldispatch', 'partialdispatch') OR dispatchedqty > 0)";
$soiDispRes = $krfconn->query($soiDispQ);
if ($soiDispRes) {
    while ($dRow = $soiDispRes->fetch_assoc()) {
        $p = trim($dRow['psono']);
        $pid = trim(strval($dRow['product_id']));
        $stk = trim($dRow['stockorder_no']);
        $dDate = (!empty($dRow['updateamended']) && $dRow['updateamended'] !== '0000-00-00 00:00:00') ? $dRow['updateamended'] : $dRow['invoicedate'];
        $st = strtolower(trim($dRow['status']));
        $label = (in_array($st, ['dispatched', 'fulldispatch']) || (intval($dRow['dispatchedqty'] ?? 0) >= intval($dRow['quantity'] ?? 0) && intval($dRow['quantity'] ?? 0) > 0)) ? 'Dispatched' : 'Partial Dispatched';
        
        if ($pid !== '') {
            $itemDispatches[$p . '|' . $pid] = ['status' => $label, 'date' => $dDate];
        }
        if ($stk !== '') {
            $itemDispatches[$p . '|' . $stk] = ['status' => $label, 'date' => $dDate];
        }
    }
}

// 2. From krf_productionorder_consumed
$pocDispQ = "SELECT psono, product_id, stockorderno, status, dispatchedqty, consumed_qty, dispatchedat, updated_at 
             FROM krf_productionorder_consumed 
             WHERE psono != '' AND (LOWER(status) IN ('dispatched', 'fulldispatch', 'partialdispatch') OR dispatchedqty > 0) AND IFNULL(tedit, '') = ''";
$pocDispRes = $krfconn->query($pocDispQ);
if ($pocDispRes) {
    while ($dRow = $pocDispRes->fetch_assoc()) {
        $p = trim($dRow['psono']);
        $pid = trim(strval($dRow['product_id']));
        $stk = trim($dRow['stockorderno']);
        $dDate = (!empty($dRow['dispatchedat']) && $dRow['dispatchedat'] !== '0000-00-00 00:00:00') ? $dRow['dispatchedat'] : $dRow['updated_at'];
        $st = strtolower(trim($dRow['status']));
        $label = (in_array($st, ['dispatched', 'fulldispatch']) || (intval($dRow['dispatchedqty'] ?? 0) >= intval($dRow['consumed_qty'] ?? 0) && intval($dRow['consumed_qty'] ?? 0) > 0)) ? 'Dispatched' : 'Partial Dispatched';
        
        if ($pid !== '' && !isset($itemDispatches[$p . '|' . $pid])) {
            $itemDispatches[$p . '|' . $pid] = ['status' => $label, 'date' => $dDate];
        }
        if ($stk !== '' && !isset($itemDispatches[$p . '|' . $stk])) {
            $itemDispatches[$p . '|' . $stk] = ['status' => $label, 'date' => $dDate];
        }
    }
}

// 3. From krf_sales_order
$soDispQ = "SELECT psono, status, updated_at, invoicedate 
            FROM krf_sales_order 
            WHERE psono != '' AND LOWER(status) IN ('fulldispatch', 'partialdispatch', 'delivered', 'completed', 'dispatched')";
$soDispRes = $krfconn->query($soDispQ);
if ($soDispRes) {
    while ($dRow = $soDispRes->fetch_assoc()) {
        $p = trim($dRow['psono']);
        $dDate = (!empty($dRow['updated_at']) && $dRow['updated_at'] !== '0000-00-00 00:00:00') ? $dRow['updated_at'] : $dRow['invoicedate'];
        $st = strtolower(trim($dRow['status']));
        $label = ($st === 'partialdispatch') ? 'Partial Dispatched' : 'Dispatched';
        $orderDispatches[$p] = ['status' => $label, 'date' => $dDate];
    }
}

// 4. From sales_invoice
$siDispQ = "SELECT psono, invoicedate, updated_at, status 
            FROM sales_invoice 
            WHERE psono != '' AND LOWER(status) = 'dispatched'";
$siDispRes = $krfconn->query($siDispQ);
if ($siDispRes) {
    while ($dRow = $siDispRes->fetch_assoc()) {
        $p = trim($dRow['psono']);
        $dDate = (!empty($dRow['invoicedate']) && $dRow['invoicedate'] !== '0000-00-00') ? $dRow['invoicedate'] : $dRow['updated_at'];
        if (!isset($orderDispatches[$p])) {
            $orderDispatches[$p] = ['status' => 'Dispatched', 'date' => $dDate];
        }
    }
}

// Fetch mapping of psono -> sono from krf_sales_order
$psoToSoMap = [];
$soMapRes = $krfconn->query("SELECT psono, sono FROM krf_sales_order WHERE psono != ''");
if ($soMapRes) {
    while ($soRow = $soMapRes->fetch_assoc()) {
        $p = trim($soRow['psono']);
        if (!empty($p) && empty($psoToSoMap[$p])) {
            $psoToSoMap[$p] = trim($soRow['sono']);
        }
    }
}

/*
 * Build clean filter lists and available months
 */
function uniqueSortedFilterValues(array $values): array
{
    $unique = [];

    foreach ($values as $value) {
        $cleanValue = preg_replace('/\s+/u', ' ', trim((string)$value));
        if ($cleanValue === null || $cleanValue === '') {
            continue;
        }

        $key = function_exists('mb_strtolower')
            ? mb_strtolower($cleanValue, 'UTF-8')
            : strtolower($cleanValue);

        if (!array_key_exists($key, $unique)) {
            $unique[$key] = $cleanValue;
        }
    }

    $result = array_values($unique);
    usort($result, 'strnatcasecmp');

    return $result;
}

$availableMonths = []; // 'YYYY-MM' => 'Month Year'
$currentMonthKey = date('Y-m');
$uniqueOrderDates = [];
$uniqueDeliveryDates = [];
$initClientNames = [];
$initLoginNames = [];
$initStatuses = [];
$initProductNames = [];

foreach ($psnolistDetailed as $row) {
    $createdAt = trim((string)($row['created_at'] ?? ''));
    $createdTimestamp = $createdAt !== '' ? strtotime($createdAt) : false;
    $ym = ($createdTimestamp !== false) ? date('Y-m', $createdTimestamp) : '';
    if ($createdTimestamp !== false) {
        if (!isset($availableMonths[$ym])) {
            $availableMonths[$ym] = date('F Y', $createdTimestamp);
        }
    }

    // Default active card is Draft, in Current Month
    $rawStatus = trim((string)($row['status'] ?? ''));
    $itemStatus = trim((string)($row['item_status'] ?? ''));
    $rawStatusLower = strtolower($rawStatus);
    $itemStatusLower = strtolower($itemStatus);
    $isItemCancelled = in_array($rawStatusLower, ['cancelled', 'cancel', 'remove', 'removed']) || in_array($itemStatusLower, ['cancelled', 'cancel', 'remove', 'removed']);
    $isDefaultCard = !$isItemCancelled && !in_array($rawStatus, ['Confirmed', 'Pending Approval', 'Emergency Sowing']);
    $isDefaultMonth = ($ym === $currentMonthKey);

    if ($isDefaultCard && $isDefaultMonth) {
        if ($createdTimestamp !== false) {
            $uniqueOrderDates[date('Y-m-d', $createdTimestamp)] = true;
        }
        $deliveryDate = trim((string)($row['expecteddeliverydate'] ?? ''));
        if ($deliveryDate !== '' && $deliveryDate !== '0000-00-00') {
            $uniqueDeliveryDates[$deliveryDate] = true;
        }
        $cName = trim((string)($row['clientname'] ?? ''));
        if ($cName !== '') $initClientNames[$cName] = true;
        $lName = trim((string)($row['loginname'] ?? ''));
        if ($lName !== '') $initLoginNames[$lName] = true;
        $rawP = trim((string)($row['product_name'] ?? ''));
        if ($rawP !== '') {
            $cleanP = trim(preg_replace('/^(NonGrafted|Non-Grafted)\s*[-:]?\s*/i', '', $rawP));
            if ($cleanP !== '') $initProductNames[$cleanP] = true;
        }
        $initStatuses['Draft'] = true;
    }
}

krsort($availableMonths); // Newest month first
if (!isset($availableMonths[$currentMonthKey])) {
    $availableMonths = array_merge([$currentMonthKey => date('F Y')], $availableMonths);
}

$uniqueOrderDates = array_keys($uniqueOrderDates);
$uniqueDeliveryDates = array_keys($uniqueDeliveryDates);
rsort($uniqueOrderDates, SORT_STRING);
rsort($uniqueDeliveryDates, SORT_STRING);

$uniqueClientNames = array_keys($initClientNames);
natcasesort($uniqueClientNames);
$uniqueClientNames = array_values($uniqueClientNames);

$uniqueLoginNames = array_keys($initLoginNames);
natcasesort($uniqueLoginNames);
$uniqueLoginNames = array_values($uniqueLoginNames);

$filterStatuses = array_keys($initStatuses);
if (empty($filterStatuses)) {
    $filterStatuses = ['Draft'];
}

$uniqueProductNames = array_keys($initProductNames);
natcasesort($uniqueProductNames);
$uniqueProductNames = array_values($uniqueProductNames);

$mdid = $_GET['mdid'] ?? "";
$dddr = loginmodulesdetails_frommaster($authconn, $companyid, $subcompanyid, $projectid, "moduleid", $mdid);
$groupid = (count($dddr) > 0) ? $dddr[0]['groupid'] : "";

function getOrderGroupColor($psono) {
    if (empty($psono)) return "#ffffff";
    $hash = md5($psono);
    $r = (hexdec(substr($hash, 0, 2)) % 35) + 220; 
    $g = (hexdec(substr($hash, 2, 2)) % 35) + 220; 
    $b = (hexdec(substr($hash, 4, 2)) % 35) + 220; 
    return "rgb($r, $g, $b)";
}
?>

<style>
  :root {
    --primary-blue: #0284c7;
    --primary-blue-dark: #0369a1;
    --text-main: #1e293b;
    --text-muted: #64748b;
    --border-color: #e2e8f0;
    --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
    --transition: all 0.25s ease-in-out;
  }

  /* Autofill cleanup */
  input:-webkit-autofill,
  input:-webkit-autofill:hover, 
  input:-webkit-autofill:focus, 
  input:-webkit-autofill:active,
  select:-webkit-autofill {
      -webkit-box-shadow: 0 0 0 1000px #ffffff inset !important;
      -webkit-text-fill-color: #334155 !important;
      transition: background-color 5000s ease-in-out 0s;
  }

  .order-dashboard {
    padding: 1.5rem 2rem;
    background: #f8fafc;
    min-height: 100vh;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    color: var(--text-main);
  }

  /* Main Title Banner Card */
  .main-title-card {
    background: #ffffff;
    padding: 1.25rem 1.75rem;
    border-radius: 14px;
    box-shadow: var(--card-shadow);
    margin-bottom: 1.25rem;
    border-left: 6px solid #0284c7;
    border-top: 1px solid #f1f5f9;
    border-right: 1px solid #f1f5f9;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    animation: fadeInDown 0.4s ease-out;
  }

  .title-content {
    display: flex;
    align-items: center;
    gap: 1.1rem;
  }

  .title-icon-wrapper {
    background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
    color: #0284c7;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    font-size: 1.5rem;
    box-shadow: 0 4px 10px rgba(2, 132, 199, 0.15);
  }

  .title-text h4 {
    margin: 0;
    font-weight: 700;
    color: #0f172a;
    font-size: 1.55rem;
    letter-spacing: -0.4px;
    line-height: 1.2;
  }

  .title-text p {
    margin: 3px 0 0;
    color: #64748b;
    font-size: 0.92rem;
    font-weight: 400;
  }

  .btn-new-order {
    background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
    color: #ffffff !important;
    border: none;
    padding: 0.65rem 1.4rem;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.92rem;
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    transition: var(--transition);
    box-shadow: 0 4px 12px rgba(2, 132, 199, 0.28);
    cursor: pointer;
    text-decoration: none;
  }

  .btn-new-order:hover {
    background: linear-gradient(135deg, #0369a1 0%, #075985 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(2, 132, 199, 0.35);
    color: #ffffff !important;
  }

  /* Date / Month Filter Bar */
  .month-date-bar {
    background: #ffffff;
    padding: 0.9rem 1.4rem;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    border: 1px solid #e2e8f0;
    margin-bottom: 1.25rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
    animation: fadeInUp 0.4s ease-out;
  }

  .month-picker-container {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
  }

  .month-select-label {
    font-size: 0.82rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #475569;
    letter-spacing: 0.4px;
    display: flex;
    align-items: center;
    gap: 0.4rem;
  }

  .month-filter-select {
    padding: 0.45rem 1rem 0.45rem 0.85rem;
    border-radius: 8px;
    border: 1.5px solid #cbd5e1;
    font-size: 0.9rem;
    font-weight: 600;
    color: #0f172a;
    background-color: #f8fafc;
    cursor: pointer;
    transition: var(--transition);
    min-width: 240px;
  }

  .month-filter-select:focus {
    border-color: #0284c7;
    background-color: #ffffff;
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
    outline: none;
  }

  .custom-date-box {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.35rem 0.65rem;
    background: #f1f5f9;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
  }

  .custom-date-input {
    padding: 0.3rem 0.5rem;
    border-radius: 6px;
    border: 1px solid #cbd5e1;
    font-size: 0.84rem;
    font-weight: 500;
    background: #ffffff;
  }

  .btn-apply-custom {
    padding: 0.35rem 0.85rem;
    font-size: 0.82rem;
    font-weight: 600;
    border-radius: 6px;
    background: #0284c7;
    color: #ffffff;
    border: none;
    cursor: pointer;
    transition: var(--transition);
  }

  .btn-apply-custom:hover {
    background: #0369a1;
  }

  .viewing-indicator-badge {
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    color: #0369a1;
    padding: 0.45rem 0.85rem;
    border-radius: 8px;
    font-size: 0.84rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  /* Summary KPI Cards Grid - Compact Sleek Design */
  .summary-cards-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 0.6rem;
    margin-bottom: 0.85rem;
    animation: fadeInUp 0.5s ease-out;
  }

  @media (max-width: 1400px) {
    .summary-cards-grid {
      grid-template-columns: repeat(3, 1fr);
    }
  }
  @media (max-width: 768px) {
    .summary-cards-grid {
      grid-template-columns: repeat(2, 1fr);
    }
  }
  @media (max-width: 480px) {
    .summary-cards-grid {
      grid-template-columns: 1fr;
    }
  }

  .summary-kpi-card {
    position: relative;
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    padding: 0.55rem 0.75rem;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    overflow: hidden;
    user-select: none;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 74px;
  }

  .summary-kpi-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
    border-color: #cbd5e1;
  }

  .summary-kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
    transition: all 0.25s ease;
  }

  .card-pending::before { background: #f59e0b; }
  .card-payment-approval::before { background: #8b5cf6; }
  .card-confirmed::before { background: #10b981; }
  .card-emergency::before { background: #ef4444; }
  .card-cancelled::before { background: #64748b; }
  .card-all::before { background: #0284c7; }

  /* Animated Border & Light Background for ACTIVE Card */
  .summary-kpi-card.active {
    transform: translateY(-3px);
  }

  /* 1. Draft Card (Active by Default) */
  .summary-kpi-card.card-pending.active {
    background: linear-gradient(135deg, #fffdf5 0%, #fffbeb 100%) !important;
    border-color: #f59e0b !important;
    animation: animatedBorderAmber 2s infinite ease-in-out;
  }

  /* 2. Pending Payment Approval Card (Active) */
  .summary-kpi-card.card-payment-approval.active {
    background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%) !important;
    border-color: #8b5cf6 !important;
    animation: animatedBorderPurple 2s infinite ease-in-out;
  }

  /* 3. Confirmed Card (Active) */
  .summary-kpi-card.card-confirmed.active {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%) !important;
    border-color: #10b981 !important;
    animation: animatedBorderGreen 2s infinite ease-in-out;
  }

  /* 3. Emergency Sowing Approval Card (Active) */
  .summary-kpi-card.card-emergency.active {
    background: linear-gradient(135deg, #fff5f5 0%, #fee2e2 100%) !important;
    border-color: #ef4444 !important;
    animation: animatedBorderRed 2s infinite ease-in-out;
  }

  /* 4. Cancel Provisional Orders Card (Active) */
  .summary-kpi-card.card-cancelled.active {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%) !important;
    border-color: #64748b !important;
    animation: animatedBorderSlate 2s infinite ease-in-out;
  }

  /* 5. All Orders Card (Active) */
  .summary-kpi-card.card-all.active {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%) !important;
    border-color: #0284c7 !important;
    animation: animatedBorderBlue 2s infinite ease-in-out;
  }

  /* Keyframe Animations for Pulsing Borders */
  @keyframes animatedBorderAmber {
    0% {
      box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.45), 0 4px 14px rgba(245, 158, 11, 0.15);
      border-color: #f59e0b;
    }
    50% {
      box-shadow: 0 0 0 4.5px rgba(245, 158, 11, 0.25), 0 8px 24px rgba(245, 158, 11, 0.25);
      border-color: #d97706;
    }
    100% {
      box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.45), 0 4px 14px rgba(245, 158, 11, 0.15);
      border-color: #f59e0b;
    }
  }

  @keyframes animatedBorderPurple {
    0% {
      box-shadow: 0 0 0 0 rgba(139, 92, 246, 0.45), 0 4px 14px rgba(139, 92, 246, 0.15);
      border-color: #8b5cf6;
    }
    50% {
      box-shadow: 0 0 0 4.5px rgba(139, 92, 246, 0.25), 0 8px 24px rgba(139, 92, 246, 0.25);
      border-color: #7c3aed;
    }
    100% {
      box-shadow: 0 0 0 0 rgba(139, 92, 246, 0.45), 0 4px 14px rgba(139, 92, 246, 0.15);
      border-color: #8b5cf6;
    }
  }

  @keyframes animatedBorderGreen {
    0% {
      box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.45), 0 4px 14px rgba(16, 185, 129, 0.15);
      border-color: #10b981;
    }
    50% {
      box-shadow: 0 0 0 4.5px rgba(16, 185, 129, 0.25), 0 8px 24px rgba(16, 185, 129, 0.25);
      border-color: #059669;
    }
    100% {
      box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.45), 0 4px 14px rgba(16, 185, 129, 0.15);
      border-color: #10b981;
    }
  }

  @keyframes animatedBorderRed {
    0% {
      box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.45), 0 4px 14px rgba(239, 68, 68, 0.15);
      border-color: #ef4444;
    }
    50% {
      box-shadow: 0 0 0 4.5px rgba(239, 68, 68, 0.25), 0 8px 24px rgba(239, 68, 68, 0.25);
      border-color: #dc2626;
    }
    100% {
      box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.45), 0 4px 14px rgba(239, 68, 68, 0.15);
      border-color: #ef4444;
    }
  }

  @keyframes animatedBorderSlate {
    0% {
      box-shadow: 0 0 0 0 rgba(100, 116, 139, 0.45), 0 4px 14px rgba(100, 116, 139, 0.15);
      border-color: #64748b;
    }
    50% {
      box-shadow: 0 0 0 4.5px rgba(100, 116, 139, 0.25), 0 8px 24px rgba(100, 116, 139, 0.25);
      border-color: #475569;
    }
    100% {
      box-shadow: 0 0 0 0 rgba(100, 116, 139, 0.45), 0 4px 14px rgba(100, 116, 139, 0.15);
      border-color: #64748b;
    }
  }

  @keyframes animatedBorderBlue {
    0% {
      box-shadow: 0 0 0 0 rgba(2, 132, 199, 0.45), 0 4px 14px rgba(2, 132, 199, 0.15);
      border-color: #0284c7;
    }
    50% {
      box-shadow: 0 0 0 4.5px rgba(2, 132, 199, 0.25), 0 8px 24px rgba(2, 132, 199, 0.25);
      border-color: #0369a1;
    }
    100% {
      box-shadow: 0 0 0 0 rgba(2, 132, 199, 0.45), 0 4px 14px rgba(2, 132, 199, 0.15);
      border-color: #0284c7;
    }
  }

  .kpi-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.25rem;
  }

  .kpi-icon-pill {
    width: 28px;
    height: 28px;
    border-radius: 7px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
  }

  .card-pending .kpi-icon-pill { background: #fef3c7; color: #d97706; }
  .card-payment-approval .kpi-icon-pill { background: #ede9fe; color: #7c3aed; }
  .card-confirmed .kpi-icon-pill { background: #dcfce7; color: #059669; }
  .card-emergency .kpi-icon-pill { background: #fee2e2; color: #dc2626; }
  .card-cancelled .kpi-icon-pill { background: #e2e8f0; color: #475569; }
  .card-all .kpi-icon-pill { background: #e0f2fe; color: #0284c7; }

  .kpi-active-badge {
    display: none;
    align-items: center;
    gap: 3px;
    font-size: 0.58rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    padding: 1px 6px;
    border-radius: 9999px;
  }

  .summary-kpi-card.active .kpi-active-badge {
    display: inline-flex;
  }

  .card-pending.active .kpi-active-badge { background: #fef3c7; color: #b45309; }
  .card-payment-approval.active .kpi-active-badge { background: #ede9fe; color: #6d28d9; }
  .card-confirmed.active .kpi-active-badge { background: #dcfce7; color: #15803d; }
  .card-emergency.active .kpi-active-badge { background: #fee2e2; color: #b91c1c; }
  .card-cancelled.active .kpi-active-badge { background: #e2e8f0; color: #334155; }
  .card-all.active .kpi-active-badge { background: #e0f2fe; color: #0369a1; }

  .kpi-radar-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background-color: currentColor;
    animation: radarPulse 1.4s infinite;
  }

  @keyframes radarPulse {
    0% { transform: scale(0.95); opacity: 0.7; }
    50% { transform: scale(1.4); opacity: 1; }
    100% { transform: scale(0.95); opacity: 0.7; }
  }

  .kpi-title {
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    color: #64748b;
    margin-bottom: 0.12rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .kpi-count-number {
    font-size: 1.22rem;
    font-weight: 800;
    line-height: 1.1;
    color: #0f172a;
    font-feature-settings: "tnum";
    font-variant-numeric: tabular-nums;
  }

  .kpi-submetric {
    font-size: 0.7rem;
    font-weight: 600;
    color: #64748b;
    margin-top: 0.12rem;
    display: flex;
    align-items: center;
    gap: 0.2rem;
  }

  /* Filter Toolbar Dashboard Panel */
  .filter-dashboard-panel {
    background: #ffffff;
    padding: 0.85rem 1.15rem;
    border-radius: 12px;
    box-shadow: var(--card-shadow);
    margin-bottom: 1.15rem;
    border: 1px solid #e2e8f0;
    position: relative;
    z-index: 1020;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    animation: fadeInUp 0.4s ease-out;
  }

  /* Primary Filter Row: 4 Filters + Expand Button */
  .filter-primary-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr) auto;
    gap: 0.75rem;
    align-items: flex-end;
  }

  @media (max-width: 1200px) {
    .filter-primary-row {
      grid-template-columns: repeat(2, 1fr) auto;
    }
  }

  @media (max-width: 768px) {
    .filter-primary-row {
      grid-template-columns: 1fr;
    }
  }

  .filter-item {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    min-width: 0;
  }

  .filter-expand-col {
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
  }

  .btn-toggle-filters {
    height: 38px;
    padding: 0.45rem 0.95rem;
    border-radius: 8px;
    background: #f8fafc;
    border: 1.5px solid #cbd5e1;
    color: #334155;
    font-size: 0.82rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
    user-select: none;
  }

  .btn-toggle-filters:hover {
    background: #f1f5f9;
    border-color: #94a3b8;
    color: #0f172a;
  }

  .btn-toggle-filters.active, .btn-toggle-filters.has-active {
    background: #e0f2fe;
    border-color: #0284c7;
    color: #0369a1;
  }

  .btn-toggle-filters .toggle-chevron {
    transition: transform 0.25s ease;
  }

  .btn-toggle-filters.active .toggle-chevron {
    transform: rotate(180deg);
  }

  /* Expanded Filters Row: additional filters */
  .filter-expanded-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px dashed #e2e8f0;
    animation: fadeInDown 0.25s ease-out;
  }

  @media (max-width: 1200px) {
    .filter-expanded-row {
      grid-template-columns: repeat(2, 1fr);
    }
  }

  @media (max-width: 768px) {
    .filter-expanded-row {
      grid-template-columns: 1fr;
    }
  }

  /* Line 2: Search Input and Actions (Reset & Export) */
  .filter-actions-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid #f1f5f9;
  }

  @media (max-width: 768px) {
    .filter-actions-row {
      flex-direction: column;
      align-items: stretch;
    }
  }

  .search-input-col {
    flex: 1;
    min-width: 220px;
  }

  .toolbar-actions-col {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    flex-shrink: 0;
  }

  .btn-clear-search {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: #94a3b8;
    font-size: 0.85rem;
    cursor: pointer;
    padding: 2px 6px;
    border-radius: 50%;
    transition: color 0.15s ease;
  }
  .btn-clear-search:hover {
    color: #ef4444;
  }

  .filter-label {
    font-size: 0.76rem;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.4px;
  }

  .filter-control {
    width: 100%;
    padding: 0.55rem 0.75rem;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    font-size: 0.88rem;
    color: #1e293b;
    transition: var(--transition);
    background-color: #f8fafc;
  }

  .filter-control:focus {
    border-color: #0284c7;
    background-color: #ffffff;
    outline: none;
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
  }

  /* Prevent raw uninitialized select2 elements from flashing on screen */
  select.filter-control.select2:not(.select2-hidden-accessible) {
    display: none !important;
  }

  /* Select2 Container styling for Order Dashboard */
  .order-dashboard .select2-container {
    width: 100% !important;
  }

  .order-dashboard .select2-container--default .select2-selection--multiple {
    min-height: 38px !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 8px !important;
    background-color: #f8fafc !important;
    padding: 2px 6px !important;
    transition: var(--transition) !important;
  }

  .order-dashboard .select2-container--default.select2-container--focus .select2-selection--multiple {
    border-color: #0284c7 !important;
    background-color: #ffffff !important;
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12) !important;
  }

  .order-dashboard .select2-container--default .select2-selection--multiple .select2-selection__choice {
    background-color: #e0f2fe !important;
    border: 1px solid #bae6fd !important;
    color: #0369a1 !important;
    border-radius: 6px !important;
    padding: 2px 8px !important;
    font-size: 0.78rem !important;
    font-weight: 600 !important;
  }

  .order-dashboard .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
    color: #0284c7 !important;
    margin-right: 4px !important;
    font-weight: bold !important;
  }

  .order-dashboard .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
    color: #ef4444 !important;
  }

  .order-dashboard .select2-container--default .select2-search--inline .select2-search__field {
    margin-top: 5px !important;
    color: #1e293b !important;
    font-size: 0.86rem !important;
  }

  .order-dashboard .select2-container .select2-selection--multiple .select2-selection__placeholder {
    color: #94a3b8 !important;
    font-size: 0.86rem !important;
  }

  .search-wrapper {
    position: relative;
    width: 100%;
  }

  .search-wrapper i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 0.92rem;
  }

  .search-control {
    width: 100%;
    padding: 0.55rem 0.85rem 0.55rem 2.4rem;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    font-size: 0.9rem;
    transition: var(--transition);
    color: #1e293b;
    background-color: #f8fafc;
  }

  .search-control:focus {
    border-color: #0284c7;
    background-color: #ffffff;
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
    outline: none;
  }

  .btn-clear-filters {
    padding: 0.55rem 1.1rem;
    border-radius: 8px;
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    color: #475569;
    font-weight: 600;
    font-size: 0.84rem;
    cursor: pointer;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    height: 38px;
  }

  .btn-clear-filters:hover {
    background: #e2e8f0;
    color: #0f172a;
  }

  .btn-export {
    padding: 0.55rem 1.1rem;
    border-radius: 8px;
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #047857;
    font-weight: 600;
    font-size: 0.84rem;
    cursor: pointer;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    height: 38px;
    white-space: nowrap;
  }

  .btn-export:hover {
    background: #d1fae5;
    color: #065f46;
    border-color: #6ee7b7;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(16, 185, 129, 0.15);
  }

  .filter-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
    position: relative;
    z-index: 1030;
  }

  .filter-actions .dropdown {
    position: relative;
  }

  .filter-actions .dropdown-menu {
    position: absolute !important;
    z-index: 1060 !important;
    min-width: 220px !important;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15) !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 10px !important;
    padding: 6px 0 !important;
    background: #ffffff !important;
    margin-top: 4px !important;
  }

  .filter-actions .dropdown-item {
    display: flex !important;
    align-items: center !important;
    padding: 8px 16px !important;
    font-size: 0.86rem !important;
    font-weight: 500 !important;
    color: #334155 !important;
    text-decoration: none !important;
    transition: all 0.15s ease !important;
  }

  .filter-actions .dropdown-item i {
    width: 20px !important;
    font-size: 0.95rem !important;
    margin-right: 8px !important;
    text-align: center !important;
  }

  .filter-actions .dropdown-item:hover {
    background-color: #f1f5f9 !important;
    color: #0f172a !important;
  }

  /* Table Header & Rows */
  .strips-header {
    position: sticky !important;
    top: 0;
    z-index: 1000;
    display: grid;
    grid-template-columns: 42px 1.65fr 1.5fr 2.35fr 1.1fr 1fr 1.2fr 1.2fr;
    padding: 0.75rem 1.25rem;
    color: #475569;
    font-size: 0.74rem;
    text-transform: uppercase;
    font-weight: 700;
    letter-spacing: 0.5px;
    background: #e2e8f0 !important;
    border-radius: 10px;
    margin-bottom: 0.6rem;
    border: 1px solid #cbd5e1;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  }

  .order-strips-container {
    display: grid !important;
    grid-template-columns: minmax(0, 1fr) !important;
    align-items: start !important;
    row-gap: 0.65rem !important;
  }

  .order-strip {
    background: #ffffff;
    border-radius: 10px;
    padding: 0.85rem 1.25rem;
    display: grid;
    grid-template-columns: 42px 1.65fr 1.5fr 2.35fr 1.1fr 1fr 1.2fr 1.2fr;
    align-items: center;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    border: 1px solid #e2e8f0;
    position: relative;
    overflow: hidden;
    width: 100%;
    margin: 0 !important;
    transition: box-shadow 0.2s ease, border-color 0.2s ease;
    content-visibility: auto;
    contain-intrinsic-size: 1px 115px;
  }

  .order-strip:hover {
    box-shadow: 0 6px 14px rgba(0, 0, 0, 0.08);
    border-color: #94a3b8;
  }

  .order-strip[hidden] {
    display: none !important;
  }

  .order-strip::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 5px;
    background: #cbd5e1;
  }

  .order-strip.status-confirmed::before { background: #10b981; }
  .order-strip.status-cancelled {
    background-color: #fef2f2 !important;
    border-color: #fca5a5 !important;
  }
  .order-strip.status-cancelled::before { background: #ef4444; width: 6px; }
  .order-strip.status-pending-approval::before { background: #f59e0b; }
  .order-strip.status-emergency-sowing::before { background: #dc2626; }
  .order-strip.status-pending::before { background: #f59e0b; }

  .strip-item {
    min-width: 0;
    padding-right: 0.6rem;
    word-break: break-word;
  }

  .strip-label {
    font-size: 0.72rem;
    text-transform: uppercase;
    color: #64748b;
    font-weight: 700;
    margin-bottom: 0.2rem;
    display: none;
  }

  .strip-content {
    font-weight: 500;
    color: #1e293b;
    font-size: 0.88rem;
  }

  .order-no-pill {
    background: #ffffff;
    padding: 0.2rem 0.55rem;
    border-radius: 6px;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.82rem;
    font-weight: 700;
    border: 1px solid #cbd5e1;
    color: #0f172a;
    display: inline-block;
  }

  .status-badge {
    padding: 0.35rem 0.65rem;
    border-radius: 6px;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
  }

  .amount-display {
    color: #0369a1;
    font-weight: 800;
    font-size: 1rem;
    font-feature-settings: "tnum";
    font-variant-numeric: tabular-nums;
  }

  .action-buttons {
    display: flex;
    gap: 0.35rem;
    justify-content: flex-end;
  }

  .btn-strip {
    width: 32px;
    height: 32px;
    border-radius: 7px;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--transition);
    font-size: 0.82rem;
    cursor: pointer;
  }

  .btn-view { background: #e0f2fe; color: #0284c7; }
  .btn-view:hover { background: #0284c7; color: #ffffff; }

  .btn-edit { background: #f3e8ff; color: #7e22ce; }
  .btn-edit:hover { background: #7e22ce; color: #ffffff; }

  .btn-pdf { background: #fee2e2; color: #b91c1c; }
  .btn-pdf:hover { background: #b91c1c; color: #ffffff; }

  .btn-delete { background: #ffffff; border: 1px solid #fecaca; color: #dc2626; }
  .btn-delete:hover { background: #dc2626; color: #ffffff; border-color: #dc2626; }

  /* Confirmed Production Order Box */
  .confirmed-prod-card {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 0.45rem 0.65rem;
    margin-top: 0.45rem;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
  }

  .prod-meta-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.2rem 0.55rem;
    border-radius: 6px;
    font-size: 0.74rem;
    font-weight: 600;
  }

  .badge-self-production {
    background-color: #ecfdf5;
    color: #065f46;
    border: 1px solid #a7f3d0;
  }

  .badge-open-stock {
    background-color: #fffbeb;
    color: #92400e;
    border: 1px solid #fde68a;
  }

  /* Prominent Quantity Badge */
  .qty-badge-prominent {
    display: inline-flex;
    align-items: center;
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    border-radius: 8px;
    padding: 0.35rem 0.8rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    margin-top: 0.35rem;
  }

  .qty-badge-prominent .qty-label {
    font-size: 0.82rem;
    font-weight: 700;
    color: #64748b;
    margin-right: 0.45rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .qty-badge-prominent .qty-value {
    font-size: 1.25rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1;
    font-feature-settings: "tnum";
    font-variant-numeric: tabular-nums;
  }

  /* Delivery / Payment Footer */
  .strip-delivery-footer {
    grid-column: 1 / -1;
    margin-top: 0.65rem;
    padding-top: 0.65rem;
    border-top: 1px dashed #cbd5e1;
    font-size: 0.82rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.5rem;
  }

  .amount-box-wrapper {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    flex-wrap: wrap;
  }

  .amount-box-mini {
    display: flex;
    flex-direction: column;
    font-size: 0.76rem;
    color: #64748b;
  }

  .amount-box-mini strong {
    font-size: 0.88rem;
    font-weight: 700;
    color: #0f172a;
  }

  @media (max-width: 992px) {
    .strips-header { display: none; }
    .order-strip { grid-template-columns: 1fr 1fr; gap: 0.85rem; padding: 1rem; }
    .strip-label { display: block; }
    .action-buttons { grid-column: span 2; justify-content: flex-start; }
    .strip-delivery-footer { flex-direction: column; align-items: flex-start; }
  }

  @keyframes fadeInDown {
    from { opacity: 0; transform: translateY(-15px); }
    to { opacity: 1; transform: translateY(0); }
  }

  @keyframes fadeInUp {
    from { opacity: 0; transform: translateY(15px); }
    to { opacity: 1; transform: translateY(0); }
  /* Cancel Order Modal Checkbox resets - prevent scale(2) overflow and enforce centering inside td */
  #cancelOrderModal .cancel-order-table input[type="checkbox"],
  #cancelOrderModal input[type="checkbox"],
  .cancel-order-table input[type="checkbox"],
  input.cancel-item-chk,
  input#checkAllCancelItems {
    -webkit-transform: none !important;
    -moz-transform: none !important;
    -ms-transform: none !important;
    transform: none !important;
    margin: 0 auto !important;
    width: 17px !important;
    height: 17px !important;
    min-width: 17px !important;
    max-width: 17px !important;
    min-height: 17px !important;
    max-height: 17px !important;
    cursor: pointer !important;
    position: static !important;
    box-shadow: none !important;
    accent-color: #2563eb !important;
    display: inline-block !important;
    vertical-align: middle !important;
  }

  #cancelOrderModal .cancel-order-table th.col-checkbox,
  #cancelOrderModal .cancel-order-table td.col-checkbox,
  .cancel-order-table th.col-checkbox,
  .cancel-order-table td.col-checkbox {
    width: 44px !important;
    min-width: 44px !important;
    max-width: 48px !important;
    text-align: center !important;
    vertical-align: middle !important;
    padding: 6px 2px !important;
  }

  .cancel-chk-wrapper {
    display: flex !important;
    justify-content: center !important;
    align-items: center !important;
    width: 100% !important;
    height: 100% !important;
    min-height: 20px !important;
    margin: 0 !important;
    padding: 0 !important;
  }
</style>

<div class="container-main order-dashboard">

  <!-- Main Banner Card -->
  <div class="main-title-card">
    <div class="title-content">
      <div class="title-icon-wrapper shadow-xs">
        <i class="fa fa-file-invoice"></i>
      </div>
      <div class="title-text">
        <h4>Provisional Sales Orders</h4>
        <p>Review orders, track advance payments & manage production stock allocations</p>
      </div>
    </div>
    <div class="header-actions d-flex align-items-center gap-2">
      <button class="btn-new-order newprovisionalorder">
        <i class="fa fa-plus-circle"></i> New Order
      </button>
    </div>
  </div>

  <!-- Month & Date Filter Bar -->
  <div class="month-date-bar">
    <div class="month-picker-container">
      <span class="month-select-label">
        <i class="fa fa-calendar-alt text-primary"></i> Filter By Month:
      </span>
      <select id="provisionalMonthSelector" class="month-filter-control month-filter-select">
        <option value="current" selected>
          📅 Current Month (<?php echo date('F Y'); ?>)
        </option>
        <?php foreach ($availableMonths as $ymKey => $monthLabel): ?>
          <?php if ($ymKey !== $currentMonthKey): ?>
            <option value="<?php echo $ymKey; ?>">
              📅 <?php echo htmlspecialchars($monthLabel); ?>
            </option>
          <?php endif; ?>
        <?php endforeach; ?>
        <option value="all">🌐 All Months (Lifetime View)</option>
        <option value="custom">🛠️ Custom Date Range...</option>
      </select>

      <!-- Custom Date Range Controls (shown when custom is selected) -->
      <div id="customDateRangeBox" class="custom-date-box" style="display: none;">
        <span class="small fw-bold text-secondary">From:</span>
        <input type="date" id="customFromDate" class="custom-date-input" value="<?php echo date('Y-m-01'); ?>">
        <span class="small fw-bold text-secondary">To:</span>
        <input type="date" id="customToDate" class="custom-date-input" value="<?php echo date('Y-m-d'); ?>">
        <button type="button" id="btnApplyCustomDate" class="btn-apply-custom">
          <i class="fa fa-check"></i> Apply
        </button>
      </div>
    </div>

    <div class="viewing-indicator-badge shadow-xs">
      <i class="fa fa-filter text-primary"></i>
      <span>Selected Period: <strong id="activePeriodLabel" class="text-dark"><?php echo date('F Y'); ?></strong></span>
      <span class="badge bg-primary text-white ms-1" id="activePeriodCountBadge">0 Orders</span>
    </div>
  </div>

  <!-- Status Summary KPI Cards (6 Cards) -->
  <!-- Default Active Card: Draft -->
  <div class="summary-cards-grid" id="statusKpiCards">
    
    <!-- 1. Draft (DEFAULT ACTIVE) -->
    <div class="summary-kpi-card card-pending active" data-status-filter="Draft" title="Click to view draft provisional orders">
      <div class="kpi-card-header">
        <div class="kpi-icon-pill">
          <i class="fa fa-file-alt"></i>
        </div>
        <div class="kpi-active-badge">
          <span class="kpi-radar-dot"></span> Active
        </div>
      </div>
      <div>
        <div class="kpi-title">Draft</div>
        <div class="kpi-count-number" id="kpi_pending_count">0</div>
        <div class="kpi-submetric text-warning-emphasis" id="kpi_pending_amount">₹0.00</div>
      </div>
    </div>

    <!-- 2. Pending Payment Approval -->
    <div class="summary-kpi-card card-payment-approval" data-status-filter="Pending Payment Approval" title="Click to view orders pending payment approval">
      <div class="kpi-card-header">
        <div class="kpi-icon-pill">
          <i class="fa fa-file-invoice-dollar"></i>
        </div>
        <div class="kpi-active-badge">
          <span class="kpi-radar-dot"></span> Active
        </div>
      </div>
      <div>
        <div class="kpi-title">Pending Payment Approval</div>
        <div class="kpi-count-number" id="kpi_payment_approval_count">0</div>
        <div class="kpi-submetric" style="color: #7c3aed;" id="kpi_payment_approval_amount">₹0.00</div>
      </div>
    </div>

    <!-- 3. Confirm To Sale Order -->
    <div class="summary-kpi-card card-confirmed" data-status-filter="Confirmed" title="Click to view confirmed sale orders">
      <div class="kpi-card-header">
        <div class="kpi-icon-pill">
          <i class="fa fa-check-circle"></i>
        </div>
        <div class="kpi-active-badge">
          <span class="kpi-radar-dot"></span> Active
        </div>
      </div>
      <div>
        <div class="kpi-title">Confirm To Sale Order</div>
        <div class="kpi-count-number" id="kpi_confirmed_count">0</div>
        <div class="kpi-submetric text-success" id="kpi_confirmed_amount">₹0.00</div>
      </div>
    </div>

    <!-- 4. Emergency Sowing Approval -->
    <div class="summary-kpi-card card-emergency" data-status-filter="Pending Approval for Emergency Sowing" title="Click to view emergency sowing approval orders">
      <div class="kpi-card-header">
        <div class="kpi-icon-pill">
          <i class="fa fa-exclamation-triangle"></i>
        </div>
        <div class="kpi-active-badge">
          <span class="kpi-radar-dot"></span> Active
        </div>
      </div>
      <div>
        <div class="kpi-title">Emergency Sowing Approval</div>
        <div class="kpi-count-number" id="kpi_emergency_count">0</div>
        <div class="kpi-submetric text-danger" id="kpi_emergency_amount">₹0.00</div>
      </div>
    </div>

    <!-- 5. Cancel Provisional Orders -->
    <div class="summary-kpi-card card-cancelled" data-status-filter="Cancelled" title="Click to view cancelled provisional orders">
      <div class="kpi-card-header">
        <div class="kpi-icon-pill">
          <i class="fa fa-ban"></i>
        </div>
        <div class="kpi-active-badge">
          <span class="kpi-radar-dot"></span> Active
        </div>
      </div>
      <div>
        <div class="kpi-title">Cancel Provisional Orders</div>
        <div class="kpi-count-number" id="kpi_cancelled_count">0</div>
        <div class="kpi-submetric text-secondary" id="kpi_cancelled_amount">₹0.00</div>
      </div>
    </div>

    <!-- 6. All Orders -->
    <div class="summary-kpi-card card-all" data-status-filter="ALL" title="Click to view all provisional orders">
      <div class="kpi-card-header">
        <div class="kpi-icon-pill">
          <i class="fa fa-layer-group"></i>
        </div>
        <div class="kpi-active-badge">
          <span class="kpi-radar-dot"></span> Active
        </div>
      </div>
      <div>
        <div class="kpi-title">All Orders</div>
        <div class="kpi-count-number" id="kpi_all_count">0</div>
        <div class="kpi-submetric text-primary" id="kpi_all_amount">₹0.00</div>
      </div>
    </div>

  </div>

  <!-- Primary & Secondary Filter Toolbar Dashboard -->
  <div class="filter-dashboard-panel">
    
    <!-- Row 1: 4 Primary Filters + Expand Toggle Button -->
    <div class="filter-primary-row">
      <!-- 1. Order Date Filter -->
      <div class="filter-item">
        <label class="filter-label"><i class="fa fa-calendar-alt text-primary"></i> Order Date</label>
        <select id="orderDateFilter" class="filter-control select2" multiple="multiple" data-placeholder="All Dates">
          <?php foreach ($uniqueOrderDates as $oDate): ?>
            <option value="<?php echo $oDate; ?>">
              <?php echo date('d-M-Y', strtotime($oDate)); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- 2. Choose Client Filter -->
      <div class="filter-item">
        <label class="filter-label"><i class="fa fa-user text-primary"></i> Choose Client</label>
        <select id="clientFilter" class="filter-control select2" multiple="multiple" data-placeholder="All Clients">
          <?php foreach ($uniqueClientNames as $clientName): ?>
            <option value="<?php echo htmlspecialchars($clientName, ENT_QUOTES, 'UTF-8'); ?>">
              <?php echo htmlspecialchars($clientName, ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- 3. Delivery Date Filter -->
      <div class="filter-item">
        <label class="filter-label"><i class="fa fa-truck text-primary"></i> Delivery Date</label>
        <select id="deliverDateFilter" class="filter-control select2" multiple="multiple" data-placeholder="All Dates">
          <?php foreach ($uniqueDeliveryDates as $dDate): ?>
            <option value="<?php echo $dDate; ?>">
              <?php echo date('d-M-Y', strtotime($dDate)); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- 4. Status Filter -->
      <div class="filter-item">
        <label class="filter-label"><i class="fa fa-info-circle text-primary"></i> Status</label>
        <select id="statusFilter" class="filter-control select2" multiple="multiple" data-placeholder="All Statuses">
          <?php foreach ($filterStatuses as $filterStatus): ?>
            <option value="<?php echo htmlspecialchars($filterStatus, ENT_QUOTES, 'UTF-8'); ?>">
              <?php echo htmlspecialchars($filterStatus, ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Expand / More Filters Action Button -->
      <div class="filter-expand-col">
        <button type="button" id="toggleMoreFiltersBtn" class="btn-toggle-filters" title="Click to show more filters (Product, Sowing, Payment, Created By)">
          <i class="fa fa-sliders-h text-primary"></i>
          <span class="btn-toggle-text">More Filters</span>
          <span id="moreFiltersActiveBadge" class="badge bg-primary text-white ms-1" style="display:none; font-size: 0.68rem; padding: 2px 6px; border-radius: 9999px;">0</span>
          <i class="fa fa-chevron-down toggle-chevron ms-1 text-muted"></i>
        </button>
      </div>
    </div>

    <!-- Expanded Row: Additional Filters (Toggled via button) -->
    <div class="filter-expanded-row" id="expandedFiltersContainer" style="display: none;">
      <!-- 5. Choose Created By Filter -->
      <div class="filter-item">
        <label class="filter-label" for="loginFilter"><i class="fa fa-user-edit text-secondary"></i> Choose Created By</label>
        <select id="loginFilter" class="filter-control select2" multiple="multiple" data-placeholder="All Created By">
          <?php foreach ($uniqueLoginNames as $login): ?>
            <?php $login = trim($login); ?>
            <option value="<?php echo htmlspecialchars($login, ENT_QUOTES, 'UTF-8'); ?>">
              <?php echo htmlspecialchars($login, ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- 6. Product / Crop Filter -->
      <div class="filter-item">
        <label class="filter-label" for="productFilter"><i class="fa fa-seedling text-success"></i> Choose Product / Crop</label>
        <select id="productFilter" class="filter-control select2" multiple="multiple" data-placeholder="All Products">
          <?php foreach ($uniqueProductNames as $prodName): ?>
            <option value="<?php echo htmlspecialchars($prodName, ENT_QUOTES, 'UTF-8'); ?>">
              <?php echo htmlspecialchars($prodName, ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- 7. Sowing Status Filter -->
      <div class="filter-item">
        <label class="filter-label" for="sowingStatusFilter"><i class="fa fa-clock text-warning"></i> Sowing Status</label>
        <select id="sowingStatusFilter" class="filter-control select2" multiple="multiple" data-placeholder="All Sowing Statuses">
          <option value="Pending">Pending</option>
          <option value="In Progress">In Progress</option>
          <option value="Sowed">Sowed</option>
          <option value="Done">Done</option>
        </select>
      </div>

      <!-- 8. Payment Status Filter -->
      <div class="filter-item">
        <label class="filter-label" for="paymentStatusFilter"><i class="fa fa-money-bill-wave text-success"></i> Payment Status</label>
        <select id="paymentStatusFilter" class="filter-control select2" multiple="multiple" data-placeholder="All Payment Statuses">
          <option value="Full Paid">Full Paid</option>
          <option value="Partial Advance">Partial Advance</option>
          <option value="Unpaid">Unpaid / No Advance</option>
        </select>
      </div>
    </div>

    <!-- Row 2: Search Input and Actions (Reset Filters & Export) on separate line -->
    <div class="filter-actions-row">
      <!-- Search Input Column (Wide, intuitive, with clear icon) -->
      <div class="search-input-col">
        <div class="search-wrapper">
          <i class="fa fa-search"></i>
          <input type="text" id="orderSearchInput" class="search-control" placeholder="Search by client name, mobile no, order no (PSO), product, stock order..." autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
          <button type="button" id="clearSearchInputBtn" class="btn-clear-search" style="display:none;" title="Clear search">
            <i class="fa fa-times"></i>
          </button>
        </div>
      </div>

      <!-- Reset Filters & Export Toolbar Actions -->
      <div class="toolbar-actions-col">
        <button type="button" id="clearFilters" class="btn-clear-filters" title="Reset all filters">
          <i class="fa fa-redo-alt"></i> <span>Reset Filters</span>
        </button>

        <div class="dropdown">
          <button class="btn-export dropdown-toggle" type="button" id="exportOrdersDropdown" data-bs-toggle="dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fa fa-file-excel text-success me-1"></i> <span>Export</span> <span class="caret"></span>
          </button>
          <div class="dropdown-menu dropdown-menu-end shadow-lg" aria-labelledby="exportOrdersDropdown">
            <a class="dropdown-item" href="javascript:void(0)" onclick="exportProvisionalOrders('excel')">
              <i class="fa fa-file-excel text-success"></i> <span>Export to Excel (.csv)</span>
            </a>
            <a class="dropdown-item" href="javascript:void(0)" onclick="exportProvisionalOrders('csv')">
              <i class="fa fa-file-csv text-primary"></i> <span>Export to CSV</span>
            </a>
            <div class="dropdown-divider my-1"></div>
            <a class="dropdown-item" href="javascript:void(0)" onclick="window.print()">
              <i class="fa fa-print text-secondary"></i> <span>Print View</span>
            </a>
          </div>
        </div>
      </div>
    </div>

  </div>

  <!-- Table Column Headers -->
  <div class="strips-header">
    <div>#</div>
    <div>Date / Order</div>
    <div>Client Name</div>
    <div>Product</div>
    <div>Exp. Delivery</div>
    <div>Amount</div>
    <div>Status</div>
    <div class="text-end">Actions</div>
  </div>

  <!-- Orders Strips List Container -->
  <div class="order-strips-container" id="orderStripsList">
    <?php
    if (count($psnolistDetailed) > 0):
      $sno = 1;
      foreach ($psnolistDetailed as $row):
        $rawStatus = trim($row['status'] ?? '');
        $itemStatus = trim($row['item_status'] ?? '');
        $rawStatusLower = strtolower($rawStatus);
        $itemStatusLower = strtolower($itemStatus);
        $isCancelled = in_array($rawStatusLower, ['cancelled', 'cancel', 'remove', 'removed']) || in_array($itemStatusLower, ['cancelled', 'cancel', 'remove', 'removed']);

        if ($isCancelled) {
          $statusClass = 'status-cancelled';
          $statusText = 'Cancelled';
          $kpiCategory = 'cancelled';
        } else {
          $statusClass = match ($rawStatus) {
            'Confirmed' => 'status-confirmed',
            'Pending Approval' => 'status-pending-approval',
            'Emergency Sowing' => 'status-emergency-sowing',
            default => 'status-pending'
          };

          $statusText = match ($rawStatus) {
            'Confirmed' => 'Confirmed',
            'Pending Approval' => 'Pending Payment Approval',
            'Emergency Sowing' => 'Pending Approval for Emergency Sowing',
            default => 'Draft'
          };

          $kpiCategory = match ($rawStatus) {
            'Confirmed' => 'confirmed',
            'Pending Approval' => 'payment_approval',
            'Emergency Sowing' => 'emergency',
            default => 'draft'
          };
        }

        $groupColor = getOrderGroupColor($row['psono']);

        $readyToSaleBtn = (!$isCancelled && $rawStatus === 'Confirmed') ? 
          '<div class="mt-1"><span class="badge bg-success shadow-xs createsaleinvoice" style="cursor:pointer; font-size: 0.64rem;" data-psono="' . htmlspecialchars($row['psono']) . '"><i class="fa fa-check-circle"></i> Invoice</span></div>' : '';

        $pickupfrom = strtolower(trim($row['pickupfrom'] ?? ''));
        $psono = trim($row['psono'] ?? '');
        $orderLocations = $delivLocations[$psono] ?? [];
        
        $locDisplayList = [];
        foreach ($orderLocations as $loc) {
            $fullAddr = trim($loc['deliveryaddress'] ?? '');
            if (!empty($fullAddr)) {
                $geoParts = array_filter([$loc['dest_city'] ?? '', $loc['dest_district'] ?? '', $loc['dest_state'] ?? '', $loc['dest_pincode'] ?? '']);
                $geoStr = implode(', ', $geoParts);
                $locText = $fullAddr;
                if (!empty($geoStr) && stripos($fullAddr, $geoParts[0] ?? '') === false) {
                    $locText .= ' (' . $geoStr . ')';
                }
                if (!empty($loc['distance']) && floatval($loc['distance']) > 0) {
                    $locText .= ' [' . floatval($loc['distance']) . ' Km]';
                }
                if (!in_array($locText, $locDisplayList)) {
                    $locDisplayList[] = $locText;
                }
            }
        }

        $cleanProductName = preg_replace('/^(NonGrafted|Non-Grafted)\s*[-:]?\s*/i', '', $row['product_name'] ?? '');

        // Resolve Production Stock Order Info
        $itemStockNo = trim((string)($row['stockorder_no'] ?? ''));
        $linkedStock = null;
        if (!empty($itemStockNo) && isset($prodStockMap[$itemStockNo])) {
            $linkedStock = $prodStockMap[$itemStockNo];
        } elseif (!empty($prodConsumedMap[$psono])) {
            foreach ($prodConsumedMap[$psono] as $cons) {
                $cStk = trim((string)($cons['stockorderno'] ?? ''));
                if (!empty($cStk) && isset($prodStockMap[$cStk])) {
                    $linkedStock = $prodStockMap[$cStk];
                    $itemStockNo = $cStk;
                    break;
                }
            }
        }

        $prodStockNo = '';
        $sowingDateFormatted = '';
        $scionSowingFormatted = '';
        $isSelfCreated = false;
        $prodStockTypeLabel = '';

        if ($linkedStock) {
            $prodStockNo = trim((string)($linkedStock['stockorder_no'] ?? ''));
            $stkPsono = trim((string)($linkedStock['psono'] ?? ''));
            $isSelfCreated = (!empty($stkPsono) && strcasecmp($stkPsono, $psono) === 0);

            if (!empty($linkedStock['sowingdate']) && $linkedStock['sowingdate'] !== '0000-00-00') {
                $sowingDateFormatted = date('d-M-Y', strtotime($linkedStock['sowingdate']));
            }
            if (!empty($linkedStock['scionsowingdate']) && $linkedStock['scionsowingdate'] !== '0000-00-00') {
                $scionSowingFormatted = date('d-M-Y', strtotime($linkedStock['scionsowingdate']));
            }
            $prodStockTypeLabel = $isSelfCreated ? 'Created For Itself' : 'Consumed Open Stock';
        } elseif (!empty($itemStockNo)) {
            $prodStockNo = $itemStockNo;
            $prodStockTypeLabel = 'Linked Stock Order';
        }

        $createdDateIso = !empty($row['created_at']) ? date('Y-m-d', strtotime($row['created_at'])) : '';
        $createdMonthIso = !empty($row['created_at']) ? date('Y-m', strtotime($row['created_at'])) : '';

        // Resolve Dispatched Status & Date
        $isDispatched = false;
        $dispatchStatusLabel = '';
        $dispatchDateFormatted = '';
        $dispMatch = null;

        $productId = trim((string)($row['product_id'] ?? ''));
        if (!empty($productId) && isset($itemDispatches[$psono . '|' . $productId])) {
            $dispMatch = $itemDispatches[$psono . '|' . $productId];
        } elseif (!empty($itemStockNo) && isset($itemDispatches[$psono . '|' . $itemStockNo])) {
            $dispMatch = $itemDispatches[$psono . '|' . $itemStockNo];
        } elseif (!empty($prodStockNo) && isset($itemDispatches[$psono . '|' . $prodStockNo])) {
            $dispMatch = $itemDispatches[$psono . '|' . $prodStockNo];
        } elseif (isset($orderDispatches[$psono])) {
            $dispMatch = $orderDispatches[$psono];
        }

        if ($dispMatch && !empty($dispMatch['status'])) {
            $isDispatched = true;
            $dispatchStatusLabel = $dispMatch['status'];
            $rawDispDate = trim((string)($dispMatch['date'] ?? ''));
            if (!empty($rawDispDate) && $rawDispDate !== '0000-00-00' && $rawDispDate !== '0000-00-00 00:00:00') {
                $dispatchDateFormatted = date('d-M-Y', strtotime($rawDispDate));
            }
        }

        $balanceAmt = max(0, (float)($row['balanceamt'] ?? ($row['item_amount'] ?? 0)));
        $paymentAdviceUrl = "paymentadvicemanual.php?k=" . urlencode($key) . 
          "&psono=" . urlencode($row['psono']) . 
          "&cname=" . urlencode($row['clientname']) . 
          "&mob=" . urlencode($row['mobileno'] ?? '') . 
          "&clid=" . urlencode($row['clientid'] ?? '') . 
          "&amt=" . urlencode($balanceAmt > 0 ? number_format($balanceAmt, 2, '.', '') : '') . 
          "&purpose=paymentagainstinvoice" . 
          "&remarks=" . urlencode("Payment against Provisional Order #" . $row['psono']) . 
          (!empty($mdid) ? "&mdid=" . urlencode($mdid) : "") . 
          "&iframe=true";

        // Resolve Sowing Status Label for Filter
        $sowStatusVal = 'Pending';
        if ($isCancelled) {
            $sowStatusVal = 'Cancelled';
        } elseif (!empty($linkedStock)) {
            $sowStRaw = strtolower(trim((string)($linkedStock['sowingstatus'] ?? 'pending')));
            if (in_array($sowStRaw, ['sowed', 'done'])) {
                $sowStatusVal = 'Sowed';
            } elseif (in_array($sowStRaw, ['start', 'inprogress', 'in progress'])) {
                $sowStatusVal = 'In Progress';
            } else {
                $sowStatusVal = ucfirst($sowStRaw ?: 'Pending');
            }
        }

        // Resolve Payment Status Label for Filter
        $advanceAmt = (float)($row['advanceamt'] ?? 0);
        $orderTotAmt = (float)($row['orderamount'] ?? ($row['item_amount'] ?? 0));
        $payStatusVal = 'Unpaid';
        if ($balanceAmt <= 0 && $orderTotAmt > 0) {
            $payStatusVal = 'Full Paid';
        } elseif ($advanceAmt > 0) {
            $payStatusVal = 'Partial Advance';
        } else {
            $payStatusVal = 'Unpaid';
        }
        ?>
        <div class="order-strip <?php echo $statusClass; ?>"
             data-client="<?php echo htmlspecialchars($row['clientname'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
             data-client-name="<?php echo htmlspecialchars($row['clientname'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
             data-login="<?php echo htmlspecialchars(trim((string)($row['loginname'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>"
             data-deliver-date="<?php echo htmlspecialchars(trim((string)($row['expecteddeliverydate'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>"
             data-order-no="<?php echo htmlspecialchars($row['psono'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
             data-product-name="<?php echo htmlspecialchars($cleanProductName ?: ($row['product_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
             data-sowing-status="<?php echo htmlspecialchars($sowStatusVal, ENT_QUOTES, 'UTF-8'); ?>"
             data-payment-status="<?php echo htmlspecialchars($payStatusVal, ENT_QUOTES, 'UTF-8'); ?>"
             data-prod-stockno="<?php echo htmlspecialchars($prodStockNo, ENT_QUOTES, 'UTF-8'); ?>"
             data-order-date="<?php echo $createdDateIso; ?>"
             data-order-month="<?php echo $createdMonthIso; ?>"
             data-category="<?php echo $kpiCategory; ?>"
             data-status="<?php echo htmlspecialchars($isDispatched ? $dispatchStatusLabel : $statusText, ENT_QUOTES, 'UTF-8'); ?>"
             data-dispatched="<?php echo $isDispatched ? '1' : '0'; ?>"
             data-dispatch-date="<?php echo htmlspecialchars($dispatchDateFormatted, ENT_QUOTES, 'UTF-8'); ?>"
             data-item-amount="<?php echo (float)($row['item_amount'] ?? 0); ?>"
             data-order-amount="<?php echo (float)($row['orderamount'] ?? 0); ?>"
             data-balance-amount="<?php echo (float)($row['balanceamt'] ?? 0); ?>"
             data-mobile="<?php echo htmlspecialchars($row['mobileno'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
             style="background-color: <?php echo $groupColor; ?>;">
          
          <!-- Column 1: S.No -->
          <div class="strip-item">
            <span class="strip-label">#</span>
            <div class="strip-content text-muted small fw-bold row-serial-number"><?php echo str_pad($sno++, 2, '0', STR_PAD_LEFT); ?></div>
          </div>
          
          <!-- Column 2: Date / Order / Qty -->
          <div class="strip-item" data-order-date="<?php echo $createdDateIso; ?>">
            <span class="strip-label">Date / Order</span>
            <div class="strip-content d-flex flex-column align-items-start gap-1">
               <!-- Date Badge (Dark color text, Light background color) -->
               <span class="badge border shadow-xs" style="background-color: #f1f5f9; color: #0f172a; font-size: 0.74rem; font-weight: 700; border-color: #cbd5e1 !important; padding: 0.24rem 0.55rem; display: inline-flex; align-items: center; gap: 4px;">
                 <i class="fa fa-calendar-alt text-dark" style="font-size: 0.72rem;"></i> <?php echo !empty($row['created_at']) ? date('d-M-Y', strtotime($row['created_at'])) : 'N/A'; ?>
               </span>
               <span class="order-no-pill"><?php echo htmlspecialchars($row['psono']); ?></span>
               <span class="qty-badge-prominent" style="<?php echo $isCancelled ? 'opacity: 0.65; text-decoration: line-through;' : ''; ?> padding: 0.2rem 0.6rem; border-radius: 6px; margin-top: 0.15rem;">
                 <span class="qty-label" style="font-size: 0.72rem;">Qty:</span>
                 <span class="qty-value" style="font-size: 1.05rem;"><?php echo number_format($row['quantity'] ?? 0, 0); ?></span>
               </span>
               <?php echo $readyToSaleBtn; ?>
            </div>
          </div>

          <!-- Column 3: Client Name -->
          <div class="strip-item"
              data-client="<?php echo htmlspecialchars($row['clientname'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
              data-login="<?php echo htmlspecialchars(trim($row['loginname'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">  
            <span class="strip-label">Client</span>
            <div class="strip-content">
              <div class="fw-bold text-primary" style="font-size: 0.92rem;">
                <?php echo htmlspecialchars($row['clientname'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
              </div>
              <?php if (!empty($row['mobileno'])): ?>
                <div class="small text-secondary mt-0.5">
                  <i class="fa fa-phone-alt me-1" style="font-size: 0.72rem;"></i><?php echo htmlspecialchars($row['mobileno'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Column 4: Product + Confirmed Production Order details -->
          <div class="strip-item" data-deliver-date="<?php echo $row['expecteddeliverydate']; ?>">
            <span class="strip-label">Product</span>
            <div class="strip-content">
                <div class="fw-bold" style="line-height: 1.25; margin-bottom: 0.35rem; font-size: 0.94rem; color: <?php echo $isCancelled ? '#dc2626' : '#0f172a'; ?>;">
                  <?php echo htmlspecialchars(!empty($cleanProductName) ? $cleanProductName : ($row['product_name'] ?? 'N/A')); ?>
                  <?php if ($isCancelled): ?>
                    <span class="badge bg-danger text-white ms-1 shadow-xs" style="font-size: 0.65rem; vertical-align: middle;">
                      <i class="fa fa-ban me-1"></i>CANCELLED
                    </span>
                  <?php endif; ?>
                </div>
                <?php if ($isCancelled && !empty($row['item_remark'])): ?>
                  <div class="small text-danger fst-italic mb-1" style="font-size: 0.72rem; line-height: 1.3;">
                    <i class="fa fa-info-circle me-1"></i>Reason: <?php echo htmlspecialchars($row['item_remark']); ?>
                  </div>
                <?php endif; ?>

                <!-- Production Stock Order & Sowing Date Card (Confirmed, Draft, & Active Orders) -->
                <?php if (!$isCancelled && (!empty($prodStockNo) || $rawStatus === 'Confirmed')): ?>
                  <div class="confirmed-prod-card mt-1.5">
                    <div class="d-flex flex-wrap align-items-center gap-1.5">
                      <?php if (!empty($prodStockNo)): ?>
                        <span class="prod-meta-pill bg-dark text-white font-monospace shadow-xs" title="Production Stock Order No">
                          <i class="fa fa-cubes text-warning me-1"></i>PSO #<?php echo htmlspecialchars($prodStockNo); ?>
                        </span>
                      <?php else: ?>
                        <span class="prod-meta-pill bg-secondary text-white small" title="Production Stock Order">
                          <i class="fa fa-cubes me-1"></i>PSO Pending
                        </span>
                      <?php endif; ?>

                      <?php if (!empty($sowingDateFormatted)): ?>
                        <span class="prod-meta-pill bg-light text-dark border shadow-xs" title="Sowing Date">
                          <i class="fa fa-calendar-check text-success me-1"></i>Sowing: <strong><?php echo $sowingDateFormatted; ?></strong>
                        </span>
                      <?php endif; ?>

                      <?php if (!empty($scionSowingFormatted) && $scionSowingFormatted !== $sowingDateFormatted): ?>
                        <span class="prod-meta-pill bg-light text-dark border shadow-xs" title="Scion Sowing Date (Grafted)">
                          <i class="fa fa-seedling text-info me-1"></i>Scion: <strong><?php echo $scionSowingFormatted; ?></strong>
                        </span>
                      <?php endif; ?>

                      <?php if (!empty($linkedStock)): 
                        $sowStRaw = strtolower(trim((string)($linkedStock['sowingstatus'] ?? 'pending')));
                        $sowStLabel = ucfirst($sowStRaw ?: 'Pending');
                        $stkWorking = trim((string)($linkedStock['workingstatus'] ?? ''));
                        
                        $sowBadgeClass = 'bg-warning-subtle text-dark border-warning-subtle';
                        $sowIcon = 'fa-clock text-warning';
                        if (in_array($sowStRaw, ['sowed', 'done'])) {
                            $sowBadgeClass = 'bg-success-subtle text-success border-success-subtle';
                            $sowIcon = 'fa-check-circle text-success';
                        } elseif (in_array($sowStRaw, ['start', 'inprogress', 'in progress'])) {
                            $sowBadgeClass = 'bg-primary-subtle text-primary border-primary-subtle';
                            $sowIcon = 'fa-spinner fa-spin text-primary';
                        }
                      ?>
                        <span class="prod-meta-pill <?php echo $sowBadgeClass; ?> border shadow-xs" title="Sowing Status of Production Order">
                          <i class="fa <?php echo $sowIcon; ?> me-1"></i>Sowing Status: <strong><?php echo htmlspecialchars($sowStLabel); ?></strong><?php if (!empty($stkWorking) && strtolower($stkWorking) !== 'remove'): ?> <small class="text-muted">(<?php echo htmlspecialchars($stkWorking); ?>)</small><?php endif; ?>
                        </span>
                      <?php endif; ?>
                    </div>

                    <!-- Origin Indicator: Created For Itself VS Consumed Open Stock -->
                    <?php if (!empty($prodStockNo)): ?>
                      <div class="mt-1 d-flex align-items-center">
                        <?php if ($isSelfCreated): ?>
                          <span class="prod-meta-pill badge-self-production shadow-xs" title="Production Stock Order was created specifically for this client order itself">
                            <i class="fa fa-check-circle text-success me-1"></i>
                            <span><strong>Created for itself</strong> <small class="text-muted">(New Sowing)</small></span>
                          </span>
                        <?php else: ?>
                          <span class="prod-meta-pill badge-open-stock shadow-xs" title="Allocated / reserved from surplus open production stock orders">
                            <i class="fa fa-box-open text-warning me-1"></i>
                            <span><strong>Consumed open production stock orders</strong></span>
                          </span>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
            </div>
          </div>

          <!-- Column 5: Exp. Delivery -->
          <div class="strip-item">
            <span class="strip-label">Exp. Delivery</span>
            <div class="strip-content">
               <span class="fw-bold text-dark">
                 <?php echo ($row['expecteddeliverydate'] && $row['expecteddeliverydate'] != '0000-00-00') ? date('d-M-Y', strtotime($row['expecteddeliverydate'])) : 'N/A'; ?>
               </span>
            </div>
          </div>

          <!-- Column 6: Amount -->
          <div class="strip-item">
            <span class="strip-label">Amount</span>
            <div class="strip-content amount-display" style="<?php echo $isCancelled ? 'text-decoration: line-through; color: #94a3b8;' : ''; ?>">₹<?php echo number_format($row['item_amount'], 2); ?></div>
          </div>

          <!-- Column 7: Status -->
          <div class="strip-item">
            <span class="strip-label">Status</span>
            <div class="strip-content">
              <?php if ($isCancelled): ?>
                <span class="status-badge bg-danger text-white shadow-xs fw-bold" style="font-size: 0.72rem;">
                  <i class="fa fa-times-circle"></i> Cancelled
                </span>
                <?php if (!empty($row['item_remark'])): ?>
                  <div class="small text-danger mt-1 text-truncate" style="max-width: 140px; font-size: 0.68rem;" title="<?php echo htmlspecialchars($row['item_remark']); ?>">
                    <i class="fa fa-info-circle"></i> <?php echo htmlspecialchars($row['item_remark']); ?>
                  </div>
                <?php endif; ?>
              <?php elseif ($rawStatus === 'Emergency Sowing'): ?>
                <span class="status-badge bg-danger text-white shadow-xs fw-bold" style="font-size: 0.7rem; border: 1px solid #dc2626;">
                  <i class="fa fa-exclamation-triangle"></i> EMERGENCY SOWING
                </span>
              <?php elseif ($rawStatus === 'Pending Approval'): ?>
                <span class="status-badge text-white shadow-xs fw-bold" style="background-color: #8b5cf6; font-size: 0.7rem; border: 1px solid #7c3aed;">
                  <i class="fa fa-file-invoice-dollar"></i> PENDING PAYMENT APPROVAL
                </span>
              <?php elseif ($rawStatus === 'Confirmed' || $isDispatched): ?>
                <span class="status-badge bg-success text-white shadow-xs fw-bold" style="font-size: 0.72rem;">
                  <i class="fa fa-check-circle"></i> Confirmed
                </span>
                <?php if ($isDispatched): ?>
                  <div class="mt-1">
                    <span class="status-badge text-white shadow-xs fw-bold" style="font-size: 0.69rem; background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); padding: 0.22rem 0.55rem; display: inline-flex; align-items: center; gap: 4px;">
                      <i class="fa fa-truck" style="font-size: 0.68rem;"></i> <?php echo htmlspecialchars($dispatchStatusLabel); ?>
                    </span>
                    <?php if (!empty($dispatchDateFormatted)): ?>
                      <div class="small text-muted mt-0.5 fw-semibold" style="font-size: 0.68rem; line-height: 1.2;" title="Dispatched on <?php echo $dispatchDateFormatted; ?>">
                        <i class="fa fa-calendar-check text-primary me-1"></i><?php echo $dispatchDateFormatted; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              <?php else: // Draft ?>
                <a href="javascript:void(0);" 
                   class="status-badge bg-secondary text-white text-decoration-none shadow-xs open-payment-modal-btn" 
                   data-url="<?php echo htmlspecialchars($paymentAdviceUrl, ENT_QUOTES); ?>"
                   data-psono="<?php echo htmlspecialchars($row['psono'], ENT_QUOTES); ?>"
                   data-cname="<?php echo htmlspecialchars($row['clientname'], ENT_QUOTES); ?>"
                   style="cursor: pointer; font-size: 0.72rem; padding: 0.35rem 0.65rem; transition: all 0.2s ease;" 
                   title="Click to Record / Update Payment in Modal for <?php echo htmlspecialchars($row['psono']); ?>"
                   onmouseover="this.style.opacity='0.85'; this.style.transform='scale(1.03)';"
                   onmouseout="this.style.opacity='1'; this.style.transform='none';">
                  <i class="fa fa-edit me-1"></i> Update to Confirm <i class="fa fa-arrow-circle-right ms-1"></i>
                </a>
                <?php
                  $createdRaw = trim((string)($row['created_at'] ?? ''));
                  $createdTs = 0;
                  if (!empty($createdRaw)) {
                      $createdTs = (strpos($createdRaw, '+') !== false) 
                          ? strtotime($createdRaw) 
                          : strtotime($createdRaw . ' +05:30');
                  }
                  $expireTs = $createdTs ? ($createdTs + (48 * 3600)) : 0;
                ?>
                <div class="draft-timer-wrap mt-1" style="font-size: 0.68rem; line-height: 1;">
                  <span class="draft-countdown-pill badge bg-light text-dark border shadow-xs" 
                        data-expires-at="<?php echo $expireTs; ?>" 
                        data-psono="<?php echo htmlspecialchars($row['psono'], ENT_QUOTES); ?>"
                        style="font-size: 0.68rem; padding: 0.28rem 0.5rem; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                    <i class="fa fa-stopwatch text-secondary"></i>
                    <span class="timer-clock-text">Calculating...</span>
                  </span>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Column 8: Actions -->
          <?php
            $rowSono = $psoToSoMap[$psono] ?? '';
            $isOrderConfirmed = ($rawStatus === 'Confirmed' || !empty($rowSono));
          ?>
          <div class="action-buttons">
            <?php if (!$isCancelled && !$isDispatched): ?>
              <button class="btn btn-strip btn-edit editOrderBtn" data-psono="<?php echo htmlspecialchars($row['psono']); ?>" title="Edit Order">
                <i class="fa fa-pencil-alt"></i>
              </button>
            <?php endif; ?>
            <button class="btn btn-strip btn-pdf" onclick="downloadOrderPDF('<?php echo htmlspecialchars($row['psono']); ?>', <?php echo $isOrderConfirmed ? 'true' : 'false'; ?>, '<?php echo htmlspecialchars($rowSono); ?>');" title="<?php echo $isOrderConfirmed ? 'Download Final Sales Order PDF' : 'Download Provisional Order PDF'; ?>">
              <i class="fa fa-file-pdf"></i>
            </button>
            <?php if ($isCancelled && (int)$loginroleid <= 3): ?>
              <button class="btn btn-strip btn-warning undoOrderBtn" 
                      data-psono="<?php echo htmlspecialchars($row['psono'], ENT_QUOTES); ?>" 
                      data-item-id="<?php echo htmlspecialchars($row['item_id'] ?? '', ENT_QUOTES); ?>"
                      data-product-name="<?php echo htmlspecialchars($cleanProductName ?: ($row['product_name'] ?? ''), ENT_QUOTES); ?>"
                      title="Undo Rejected Order"
                      style="color: #b45309; background-color: #fef3c7; border: 1px solid #fde68a;">
                <i class="fa fa-undo"></i>
              </button>
            <?php endif; ?>
            <?php if (!$isCancelled && !$isDispatched && $loginroleid < 2): ?>
              <button class="btn btn-strip btn-delete removeOrderBtn" 
                      data-psono="<?php echo htmlspecialchars($row['psono']); ?>" 
                      data-item-id="<?php echo htmlspecialchars($row['item_id'] ?? ''); ?>"
                      data-product-name="<?php echo htmlspecialchars($cleanProductName ?: ($row['product_name'] ?? '')); ?>"
                      title="Remove / Cancel Order Item">
                <i class="fa fa-trash-alt"></i>
              </button>
            <?php endif; ?>
          </div>

          <!-- Full Width Delivery Address / Payment Details Footer -->
          <div class="strip-delivery-footer">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 w-100">
              
              <div class="amount-box-wrapper">
                <?php if (isset($row['orderamount'])): ?>
                  <div class="amount-box-mini">
                    <span>Order Total</span>
                    <strong>₹<?php echo number_format((float)$row['orderamount'], 2); ?></strong>
                  </div>
                  <div class="amount-box-mini">
                    <span>Received Amount</span>
                    <strong class="text-success">₹<?php echo number_format((float)($row['advanceamt'] ?? 0), 2); ?></strong>
                  </div>
                  <div class="amount-box-mini">
                    <span>Balance Pending</span>
                    <strong class="<?php echo ((float)($row['balanceamt'] ?? 0) > 0) ? 'text-danger' : 'text-secondary'; ?>">
                      ₹<?php echo number_format((float)($row['balanceamt'] ?? 0), 2); ?>
                    </strong>
                  </div>
                <?php endif; ?>

                <?php
                  $payInfo = $paymentDetailsMap[$psono] ?? null;
                  $payUserRaw = trim((string)($payInfo['allocatedby'] ?? ($payInfo['receivedby'] ?? '')));
                  $payUserName = $userLookupMap[$payUserRaw] ?? ($payUserRaw ?: 'Admin');
                  $rawPayDate = (!empty($payInfo['allocated_datetime']) && $payInfo['allocated_datetime'] !== '0000-00-00 00:00:00') 
                      ? $payInfo['allocated_datetime'] 
                      : ($payInfo['paymentdate'] ?? '');
                  $payDateFormatted = (!empty($rawPayDate) && $rawPayDate !== '0000-00-00') ? date('d-M-Y h:i A', strtotime($rawPayDate)) : '';

                  $isOrderUpdated = (!empty($row['updated_by']) || (!empty($row['updated_at']) && $row['updated_at'] !== '0000-00-00 00:00:00' && $row['updated_at'] !== ($row['created_at'] ?? '')));
                  $orderUpdatedByName = $row['updatedby_name'] ?: ($userLookupMap[$row['updated_by']] ?? ($row['updated_by'] ?: 'User'));
                ?>
                <span class="text-muted" style="font-size: 0.8rem;">
                  Created By <strong class="text-dark"><?php echo htmlspecialchars($row['loginname'] ?: ($row['created_by'] ?? 'User')); ?></strong>
                  <?php if (!empty($row['created_at']) && $row['created_at'] !== '0000-00-00 00:00:00'): ?>
                    on <strong><?php echo date('d-M-Y h:i A', strtotime($row['created_at'])); ?></strong>
                  <?php endif; ?>
                </span>

                <?php if (!empty($payInfo) && ((float)($row['advanceamt'] ?? 0) > 0 || !empty($payInfo['advanceamount']))): ?>
                  <span class="text-secondary" style="font-size: 0.8rem;">
                    | Payment Updated By <strong class="text-dark"><?php echo htmlspecialchars($payUserName); ?></strong>
                    <?php if (!empty($payDateFormatted)): ?>
                      on <strong><?php echo $payDateFormatted; ?></strong>
                    <?php endif; ?>
                  </span>
                <?php endif; ?>

                <?php if ($isOrderUpdated): ?>
                  <span class="text-secondary" style="font-size: 0.8rem;">
                    | Updated By <strong class="text-dark"><?php echo htmlspecialchars($orderUpdatedByName); ?></strong>
                    on <strong><?php echo date('d-M-Y h:i A', strtotime($row['updated_at'])); ?></strong>
                  </span>
                <?php endif; ?>
              </div>

              <!-- Delivery Mode & Address Info -->
              <div class="d-flex align-items-center gap-2 flex-wrap ms-auto">
                <?php if ($pickupfrom === 'pickup'): ?>
                  <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1" style="font-size: 0.76rem;">
                    <i class="fa fa-walking me-1"></i> Self Pickup
                  </span>
                  <span class="text-muted" style="font-size: 0.8rem;">
                    Client pickup date: <strong><?php echo (!empty($row['expecteddeliverydate']) && $row['expecteddeliverydate'] != '0000-00-00') ? date('d-M-Y', strtotime($row['expecteddeliverydate'])) : 'Pending'; ?></strong>
                  </span>

                <?php elseif ($pickupfrom === 'boxpacking'): ?>
                  <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1" style="font-size: 0.76rem;">
                    <i class="fa fa-box me-1"></i> Box Packing
                  </span>
                  <?php if (!empty($locDisplayList)): ?>
                    <span class="text-muted" style="font-size: 0.78rem;">Address:</span>
                    <span class="text-dark fw-medium" style="font-size: 0.82rem;">
                      <?php echo htmlspecialchars(implode('; ', $locDisplayList)); ?>
                    </span>
                  <?php endif; ?>

                <?php else: ?>
                  <?php if (!empty($locDisplayList)): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 0.76rem;">
                      <i class="fa fa-truck me-1"></i> Delivery
                    </span>
                    <span class="text-muted" style="font-size: 0.78rem;">Address:</span>
                    <span class="text-dark fw-medium" style="font-size: 0.82rem;">
                      <?php echo htmlspecialchars(implode('; ', $locDisplayList)); ?>
                    </span>
                  <?php else: ?>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" style="font-size: 0.76rem;">
                      <i class="fa fa-exclamation-triangle me-1"></i> Missing Address
                    </span>
                  <?php endif; ?>
                <?php endif; ?>

                <!-- Action Modals (Address / Payment) -->
                <!-- Undo Rejected Order button (only for loginroleid <= 3 and cancelled order/item) -->
                <?php if ($isCancelled && (int)$loginroleid <= 3): ?>
                  <button type="button"
                          class="btn btn-sm btn-outline-warning px-2 py-0.5 fw-bold shadow-xs d-inline-flex align-items-center gap-1 undoOrderBtn"
                          data-psono="<?php echo htmlspecialchars($row['psono'] ?? '', ENT_QUOTES); ?>"
                          data-item-id="<?php echo htmlspecialchars($row['item_id'] ?? '', ENT_QUOTES); ?>"
                          style="font-size:0.75rem; border-radius:6px; background-color:#fffbeb; border:1px solid #fde68a; color:#b45309; white-space:nowrap;">
                      <i class="fa fa-undo"></i> Undo Reject Order
                  </button>
                <?php endif; ?>

                <?php 
                  $canUpdateAddress = ((int)$loginroleid <= 3) || empty($row['updated_by']);
                  if ($canUpdateAddress) : 
                ?>
                  <button type="button"
                          onclick="openDeliveryAddressModal(
                              '<?php echo htmlspecialchars($row['clientid'] ?? '', ENT_QUOTES); ?>',
                              '<?php echo htmlspecialchars($row['psono'] ?? '', ENT_QUOTES); ?>',
                              '<?php echo htmlspecialchars($row['clientname'] ?? '', ENT_QUOTES); ?>',
                              '<?php echo date('d-m-Y', strtotime($row['created_at'])); ?>'
                          )"
                          class="btn btn-sm btn-outline-primary px-2 py-0.5 fw-bold shadow-xs d-inline-flex align-items-center gap-1"
                          style="font-size:0.75rem; border-radius:6px; background-color:#eff6ff; border:1px solid #bfdbfe; color:#1d4ed8; white-space:nowrap;">
                      <i class="fa fa-edit"></i> Update Address
                  </button>
                <?php endif; ?>

                <?php 
                  $isPendingForPayment = !in_array($rawStatus, ['Confirmed', 'Cancelled']);
                  if ($isPendingForPayment) : 
                ?>
                  <button type="button"
                          onclick="openOldOrderPaymentModal(
                              '<?php echo htmlspecialchars($row['psono'] ?? '', ENT_QUOTES); ?>',
                              '<?php echo htmlspecialchars($row['clientid'] ?? '', ENT_QUOTES); ?>',
                              '<?php echo htmlspecialchars($row['clientname'] ?? '', ENT_QUOTES); ?>',
                              '<?php echo htmlspecialchars($row['mobileno'] ?? '', ENT_QUOTES); ?>',
                              '<?php echo (float)($row['orderamount'] ?? 0); ?>',
                              '<?php echo (float)($row['advanceamt'] ?? 0); ?>',
                              '<?php echo (float)($row['balanceamt'] ?? 0); ?>'
                          )"
                          class="btn btn-sm btn-success px-2 py-0.5 fw-bold shadow-xs d-none align-items-center gap-1"
                          style="display:none !important; font-size:0.75rem; border-radius:6px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: 1px solid #059669; color:#fff; white-space:nowrap;"
                          title="Update Payment for Provisional Order <?php echo htmlspecialchars($row['psono']); ?>">
                      <i class="fa fa-money-bill-wave"></i> Old Order Payment update
                  </button>
                <?php endif; ?>
              </div>

            </div>
          </div>

        </div>
      <?php endforeach;
    else: ?>
      <div class="text-center py-5 bg-white rounded-3 shadow-xs border">
        <h5 class="text-muted fw-bold">No Provisional Orders Found</h5>
        <button class="btn btn-primary btn-sm mt-3 newprovisionalorder">
          <i class="fa fa-plus-circle me-1"></i> Create Provisional Order
        </button>
      </div>
    <?php endif; ?>

    <div id="noSearchResults" class="text-center py-5 bg-white rounded-3 shadow-xs border" hidden>
      <i class="fa fa-search fa-2x text-muted mb-2"></i>
      <h5 class="text-muted fw-bold">No Matching Orders Found</h5>
      <p class="text-secondary small mb-0">Try clearing filters or choosing a different month/status filter.</p>
    </div>
  </div>

</div>

<!-- Modal Logic Preserved -->
<div class="modal fade" id="viewOrderModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content shadow-lg border-0" style="border-radius: 14px; overflow: hidden;">
      <div class="modal-header bg-light py-2 px-3">
        <h5 class="modal-title font-weight-bold fs-16"><i class="fa fa-file-invoice text-primary me-2"></i>Order Details</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="$('#viewOrderModal').modal('hide')">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body p-3" id="viewOrderContent"></div>
    </div>
  </div>
</div>

<!-- Dedicated Cancel / Remove Provisional Order Modal -->
<div class="modal fade" id="cancelOrderModal" tabindex="-1" role="dialog" aria-labelledby="cancelOrderModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document" style="max-width: 840px;">
    <div class="modal-content shadow-lg border-0" style="border-radius: 14px; overflow: hidden;">
      <div class="modal-header bg-white border-bottom py-2.5 px-3 d-flex justify-content-between align-items-center">
        <h5 class="modal-title font-weight-bold fs-16 mb-0 text-danger d-flex align-items-center" id="cancelOrderModalTitle">
          <i class="fa fa-trash-alt text-danger me-2"></i>Cancel Provisional Order
        </h5>
        <button type="button" class="close text-muted" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" onclick="$('#cancelOrderModal').modal('hide')" style="background: none; border: none; font-size: 1.4rem; line-height: 1; cursor: pointer;">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body p-3" id="cancelOrderContent" style="max-height: 80vh; overflow-y: auto;">
        <div class="text-center py-5">
          <div class="spinner-border text-primary" role="status"></div>
          <p class="mt-2 text-muted fw-bold">Loading order details...</p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Dedicated Undo Rejected Provisional Order Modal (90% width, fully responsive) -->
<div class="modal fade" id="undoOrderModal" tabindex="-1" role="dialog" aria-labelledby="undoOrderModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-undo-90" role="document" style="max-width: 90vw; width: 90vw; margin: 2vh auto;">
    <div class="modal-content shadow-lg border-0" style="border-radius: 14px; overflow: hidden; height: 94vh; max-height: 94vh; display: flex; flex-direction: column;">
      <div class="modal-header bg-white border-bottom py-2.5 px-3 d-flex justify-content-between align-items-center" style="flex-shrink: 0;">
        <h5 class="modal-title font-weight-bold fs-16 mb-0 text-warning d-flex align-items-center" id="undoOrderModalTitle">
          <i class="fa fa-undo-alt text-warning me-2"></i>Undo Rejected Provisional Order
        </h5>
        <button type="button" class="close text-muted" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" onclick="$('#undoOrderModal').modal('hide')" style="background: none; border: none; font-size: 1.4rem; line-height: 1; cursor: pointer;">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body p-2 p-md-3" id="undoOrderContent" style="flex: 1 1 auto; overflow-y: auto;">
        <div class="text-center py-5">
          <div class="spinner-border text-warning" role="status"></div>
          <p class="mt-2 text-muted fw-bold">Loading order details...</p>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- Dedicated Full-Page Payment Advice / Update Payment Modal -->
<div class="modal fade" id="paymentAdviceFullModal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1060;">
  <div class="modal-dialog modal-fullscreen" style="max-width: 98vw; width: 98vw; height: 96vh; margin: 2vh auto;">
    <div class="modal-content shadow-lg border-0" style="border-radius: 14px; overflow: hidden; height: 100%; display: flex; flex-direction: column;">
      <div class="modal-header py-2.5 px-3 text-white d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); flex-shrink: 0;">
        <div class="d-flex align-items-center gap-2">
          <i class="fa fa-credit-card text-white fs-5"></i>
          <h5 class="modal-title font-weight-bold fs-16 mb-0 text-white">
            Update Payment &mdash; <span id="paymentAdviceOrderBadge" class="badge bg-white text-primary font-monospace ms-1"></span>
          </h5>
          <span id="paymentAdviceClientBadge" class="text-white-50 small ms-2"></span>
        </div>
        <div class="d-flex align-items-center gap-2">
          <button type="button" class="btn btn-sm btn-outline-light px-2.5 py-1" id="btnReloadPaymentIframe" title="Reload Payment Form">
            <i class="fa fa-sync-alt me-1"></i> Reload Form
          </button>
          <button type="button" class="close text-white" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" onclick="$('#paymentAdviceFullModal').modal('hide')" style="background: rgba(255,255,255,0.2); border: none; font-size: 1.4rem; line-height: 1; border-radius: 6px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      </div>
      <div class="modal-body p-0 position-relative bg-light" style="flex: 1 1 auto; height: calc(100% - 50px); overflow: hidden; background: #f8fafc;">
        <div id="paymentAdviceProgressBar" style="position: absolute; top: 0; left: 0; width: 100%; height: 3px; z-index: 10; display: none; background: #e2e8f0; overflow: hidden;">
          <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width: 100%; height: 3px;"></div>
        </div>
        <iframe id="paymentAdviceIframe" src="" style="width: 100%; height: 100%; border: none; display: block;" frameborder="0"></iframe>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="editOrderModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document" style="max-width: 90%;">
    <div class="modal-content shadow-lg border-0" style="border-radius: 14px; overflow: hidden;">
      <div class="modal-header bg-primary text-white py-2 px-3">
        <h5 class="modal-title fs-16" id="editOrderheader"><i class="fa fa-pencil-alt me-2"></i>Edit Provisional Sales Order</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" onclick="$('#editOrderModal').modal('hide')">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body p-3" id="editOrderContent"></div>
    </div>
  </div>
</div>

<!-- Dedicated Order Delivery & Freight Modal -->
<div class="modal fade" id="orderDeliveryFreightModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: 92vw;">
    <div class="modal-content shadow-lg border-0" style="border-radius: 16px; overflow: hidden;">
      <div class="modal-header bg-primary text-white py-2 px-3 d-flex justify-content-between align-items-center">
        <h5 class="modal-title fw-bold fs-15 mb-0"><i class="fa fa-truck-loading me-2"></i>Manage Delivery Address & Freight Charges</h5>
        <button type="button" class="close text-white" data-dismiss="modal" data-bs-dismiss="modal" onclick="$('#orderDeliveryFreightModal').modal('hide')" aria-label="Close" style="background: rgba(255,255,255,0.2); border: none; font-size: 1.4rem; line-height: 1; border-radius: 6px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body p-3" id="orderDeliveryFreightContent" style="min-height: 520px; max-height: 85vh; overflow-y: auto;">
        <div class="text-center py-5">
          <div class="spinner-border text-primary" role="status"></div>
          <p class="mt-2 text-muted fw-bold">Loading delivery and freight details...</p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Dedicated Old Order Payment Update Modal -->
<div class="modal fade" id="oldOrderPaymentModal" tabindex="-1" role="dialog" aria-labelledby="oldOrderPaymentModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
    <div class="modal-content shadow-lg border-0" style="border-radius: 16px; overflow: hidden;">
      <div class="modal-header bg-primary text-white py-2.5 px-3 d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);">
        <div>
          <h5 class="modal-title fw-bold fs-16 mb-0 text-white" id="oldOrderPaymentModalTitle">
            <i class="fa fa-receipt me-2"></i>Old Order Payment Update
          </h5>
          <small class="text-white-50" style="font-size: 0.78rem;">Update advance payment against provisional order</small>
        </div>
        <button type="button" class="close text-white" data-dismiss="modal" data-bs-dismiss="modal" onclick="$('#oldOrderPaymentModal').modal('hide')" aria-label="Close" style="background: rgba(255,255,255,0.2); border: none; font-size: 1.4rem; line-height: 1; border-radius: 6px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body p-3 p-md-4" style="max-height: 80vh; overflow-y: auto;">
        <!-- Order Summary Card -->
        <div class="p-3 mb-3 rounded-3 border bg-light shadow-xs" style="background-color: #f8fafc; border-color: #e2e8f0 !important;">
          <div class="row g-2 align-items-center">
            <div class="col-md-6">
              <div class="small text-muted" style="font-size: 0.78rem;">Provisional Order No</div>
              <div class="fw-bold fs-15 text-primary font-monospace" id="oop_psono_display">-</div>
            </div>
            <div class="col-md-6">
              <div class="small text-muted" style="font-size: 0.78rem;">Client Name & Mobile</div>
              <div class="fw-semibold text-dark" id="oop_client_display">-</div>
            </div>
          </div>
          <hr class="my-2 border-secondary-subtle">
          <div class="row g-2 text-center">
            <div class="col-4">
              <div class="small text-muted" style="font-size: 0.76rem;">Order Amount</div>
              <div class="fw-bold text-dark" id="oop_orderamt_display">₹0.00</div>
            </div>
            <div class="col-4">
              <div class="small text-muted" style="font-size: 0.76rem;">Received Amount</div>
              <div class="fw-bold text-success" id="oop_receivedamt_display">₹0.00</div>
            </div>
            <div class="col-4">
              <div class="small text-muted" style="font-size: 0.76rem;">Pending Balance</div>
              <div class="fw-bold text-danger" id="oop_balanceamt_display">₹0.00</div>
            </div>
          </div>
        </div>

        <!-- Payment Form -->
        <form id="oldOrderPaymentForm" enctype="multipart/form-data">
          <input type="hidden" id="oop_psono" name="psono">
          <input type="hidden" id="oop_clientid" name="clientid">
          <input type="hidden" id="oop_clientname" name="clientname">
          <input type="hidden" id="oop_clientmobileno" name="clientmobileno">
          <input type="hidden" id="oop_sourceofpayment" name="sourceofpayment" value="Cash">
          <input type="hidden" id="oop_purpose" name="purposeofamount" value="Advance Payment">

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-bold small text-secondary">Payment Date <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="oop_paymentdate" name="paymentdate" required>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-bold small text-secondary">Payment Amount (₹) <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text fw-bold bg-light">₹</span>
                <input type="number" step="0.01" min="1" class="form-control fw-bold text-success fs-15" id="oop_amount" name="amount" placeholder="Enter payment amount" required>
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-bold small text-secondary">Booking No <span class="text-muted fw-normal">(Optional)</span></label>
              <input type="text" class="form-control" id="oop_bookingno" name="bookingno" placeholder="Enter Booking Number">
            </div>

            <div class="col-md-6">
              <label class="form-label fw-bold small text-secondary">Upload Booking Receipt <span class="text-muted fw-normal">(Optional)</span></label>
              <input type="file" class="form-control" id="oop_bookingreceipt" name="bookingreceipt" accept="image/*,.pdf">
              <small class="text-muted" style="font-size: 0.74rem;">Attach booking receipt / confirmation slip</small>
            </div>

            <div class="col-12">
              <style>
                #oldOrderPaymentModal .oop-radio-group {
                  display: flex !important;
                  flex-direction: row !important;
                  align-items: center !important;
                  flex-wrap: wrap !important;
                  padding: 10px 16px !important;
                  background-color: #f8fafc !important;
                  border: 1px solid #cbd5e1 !important;
                  border-radius: 8px !important;
                  margin: 0 !important;
                }
                #oldOrderPaymentModal .oop-radio-item {
                  display: inline-flex !important;
                  align-items: center !important;
                  margin-right: 28px !important;
                  margin-top: 3px !important;
                  margin-bottom: 3px !important;
                  padding: 3px 6px !important;
                  cursor: pointer !important;
                  border-radius: 6px !important;
                  transition: background-color 0.15s ease !important;
                }
                #oldOrderPaymentModal .oop-radio-item:hover {
                  background-color: #e2e8f0 !important;
                }
                #oldOrderPaymentModal .oop-radio-item:last-child {
                  margin-right: 0 !important;
                }
                #oldOrderPaymentModal input[type="radio"].oop-radio-input {
                  width: 18px !important;
                  height: 18px !important;
                  margin: 0 10px 0 0 !important;
                  cursor: pointer !important;
                }
                #oldOrderPaymentModal .oop-radio-label-text {
                  display: inline-flex !important;
                  align-items: center !important;
                  font-size: 0.92rem !important;
                  font-weight: 600 !important;
                  color: #1e293b !important;
                }
              </style>
              <label class="form-label fw-bold small text-secondary mb-1 d-block">Payment Mode / Source <span class="text-danger">*</span></label>
              <div class="oop-radio-group">
                <label class="oop-radio-item" for="src_cash">
                  <input type="radio" class="oop-radio-input" name="sourceofpayment_radio" id="src_cash" value="Cash" checked style="accent-color: #10b981;" onchange="handlePaymentModeRadioChange(this.value)">
                  <span class="oop-radio-label-text">
                    <i class="fa fa-money-bill-wave text-success me-1"></i> Cash
                  </span>
                </label>

                <label class="oop-radio-item" for="src_upi">
                  <input type="radio" class="oop-radio-input" name="sourceofpayment_radio" id="src_upi" value="UPI" style="accent-color: #3b82f6;" onchange="handlePaymentModeRadioChange(this.value)">
                  <span class="oop-radio-label-text">
                    <i class="fa fa-qrcode text-primary me-1"></i> UPI
                  </span>
                </label>

                <label class="oop-radio-item" for="src_bank">
                  <input type="radio" class="oop-radio-input" name="sourceofpayment_radio" id="src_bank" value="Bank Transfer" style="accent-color: #0284c7;" onchange="handlePaymentModeRadioChange(this.value)">
                  <span class="oop-radio-label-text">
                    <i class="fa fa-university text-info me-1"></i> Bank Transfer
                  </span>
                </label>

                <label class="oop-radio-item" for="src_cheque">
                  <input type="radio" class="oop-radio-input" name="sourceofpayment_radio" id="src_cheque" value="Cheque" style="accent-color: #f59e0b;" onchange="handlePaymentModeRadioChange(this.value)">
                  <span class="oop-radio-label-text">
                    <i class="fa fa-money-check-alt text-warning me-1"></i> Cheque
                  </span>
                </label>
              </div>
            </div>

            <div class="col-12 m-0 p-0" id="oop_cash_section">
              <div class="row g-3 px-2 pt-2">
                <div class="col-md-6">
                  <label class="form-label fw-bold small text-secondary">Cash Received By</label>
                  <input type="text" class="form-control" id="oop_casgreceivedby" name="casgreceivedby" placeholder="Staff / Person name">
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold small text-secondary">Cash Received Place / Location</label>
                  <input type="text" class="form-control" id="oop_casgreceuvelocation" name="casgreceuvelocation" placeholder="e.g. Karjee Farm Office / Handover">
                </div>
              </div>
            </div>

            <div class="col-12 m-0 p-0" id="oop_bank_section" style="display: none;">
              <div class="row g-3 px-2 pt-2">
                <div class="col-md-6">
                  <label class="form-label fw-bold small text-secondary" id="oop_account_label">Received in Bank / Account</label>
                  <select class="form-control form-select" id="oop_receivedtoaccountid" name="receivedtoaccountid" style="width: 100%;">
                    <option value="">Select Bank / Account</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold small text-secondary" id="oop_ref_label">UTR / Txn Ref No</label>
                  <input type="text" class="form-control" id="oop_utrnumber" name="utrnumber" placeholder="Enter UTR / Transaction No">
                </div>
              </div>
            </div>

            <div class="col-12">
              <label class="form-label fw-bold small text-secondary">Payment Receipt / Screenshot <span class="text-muted fw-normal">(Optional)</span></label>
              <input type="file" class="form-control" id="oop_paymentreceipt" name="paymentreceipt" accept="image/*,.pdf">
              <small class="text-muted" style="font-size: 0.74rem;">Attach receipt screenshot or PDF if available</small>
            </div>

            <div class="col-12">
              <label class="form-label fw-bold small text-secondary">Remarks / Notes</label>
              <textarea class="form-control" id="oop_remarks" name="remarks" rows="2" placeholder="Enter any payment notes or remarks (optional)..."></textarea>
            </div>
          </div>
        </form>
      </div>

      <div class="modal-footer bg-light py-2 px-3 d-flex justify-content-between">
        <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal" onclick="$('#oldOrderPaymentModal').modal('hide')">
          <i class="fa fa-times me-1"></i> Close
        </button>
        <button type="button" class="btn btn-success fw-bold px-3 shadow-xs" id="btnSubmitOldOrderPayment" onclick="submitOldOrderAdvancePayment()">
          <i class="fa fa-check-circle me-1"></i> Submit Payment Update
        </button>
      </div>
    </div>
  </div>
</div>

<?php include('../footer.php'); ?>
<script type="text/javascript" src="<?php echo $prefix; ?>krf-orderandsales/js/pjs_function.js"></script>
<script type="text/javascript" src="<?php echo $prefix; ?>krf-orderandsales/js/invoice_calculation.js"></script>
<script>
  function openDeliveryAddressModal(clientId, orderId, clientname, createddate) {
    let companyid = $("body").data("companyid");
    let subcompanyid = $("body").data("subcompanyid");
    let projectid = $("body").data("projectid");
    let loginid = $("#hidden_loginid").val() || $("body").data("loginid") || "<?php echo htmlspecialchars($loginid, ENT_QUOTES); ?>";
    $("#orderDeliveryFreightContent").html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted fw-bold">Loading delivery and freight details...</p></div>');
    $("#orderDeliveryFreightModal").modal("show");
    let rooturl = $("#hidden_rooturl").val();

    $.ajax({
      url: "modal/fetch_order_delivery_freight.php",
      type: "POST",
      data: { orderid: orderId, clientid: clientId, clientname: clientname, companyid: companyid, subcompanyid: subcompanyid, projectid: projectid, rooturl: rooturl, createddate: createddate, loginid: loginid },
      success: function (data) {
        $("#orderDeliveryFreightContent").html(data);
      },
      error: function() {
        $("#orderDeliveryFreightContent").html('<div class="alert alert-danger p-3">Failed to load order delivery details.</div>');
      }
    });
  } 

  function openOldOrderPaymentModal(psono, clientid, clientname, mobileno, orderamount, advanceamt, balanceamt) {
    $("#oop_psono").val(psono || '');
    $("#oop_clientid").val(clientid || '');
    $("#oop_clientname").val(clientname || '');
    $("#oop_clientmobileno").val(mobileno || '');

    $("#oop_psono_display").text(psono || '-');
    $("#oop_client_display").text((clientname || '-') + (mobileno ? ` (${mobileno})` : ''));
    $("#oop_orderamt_display").text('₹' + Number(orderamount || 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $("#oop_receivedamt_display").text('₹' + Number(advanceamt || 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $("#oop_balanceamt_display").text('₹' + Number(balanceamt || 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));

    $("#oop_amount").val('');
    $("#oop_paymentdate").val('');
    $("#oop_casgreceivedby").val('');
    $("#oop_casgreceuvelocation").val('');
    $("#oop_bookingno").val('');
    $("#oop_bookingreceipt").val('');
    
    handlePaymentModeRadioChange('Cash');
    $("#oop_utrnumber").val('');
    $("#oop_remarks").val('');
    $("#oop_paymentreceipt").val('');

    loadAccountsForOldOrderPayment();
    $("#oldOrderPaymentModal").modal("show");
  }

  function handlePaymentModeRadioChange(mode) {
    $("#oop_sourceofpayment").val(mode);
    $(`input[name='sourceofpayment_radio'][value='${mode}']`).prop("checked", true);

    if (mode === 'Cash') {
      $("#oop_cash_section").show();
      $("#oop_bank_section").hide();
    } else {
      $("#oop_cash_section").hide();
      $("#oop_bank_section").show();

      if (mode === 'Cheque') {
        $("#oop_ref_label").html('Cheque No <span class="text-muted fw-normal">(If available)</span>');
        $("#oop_utrnumber").attr('placeholder', 'Enter Cheque Number');
      } else if (mode === 'UPI') {
        $("#oop_ref_label").html('UTR / Txn Ref No <span class="text-muted fw-normal">(If available)</span>');
        $("#oop_utrnumber").attr('placeholder', 'Enter UPI / UTR Transaction Reference No');
      } else {
        $("#oop_ref_label").html('UTR / Txn Ref No');
        $("#oop_utrnumber").attr('placeholder', 'Enter Bank Transaction / UTR Number');
      }
    }
  }

  function loadAccountsForOldOrderPayment(selectedAccId) {
    let companyid = $("body").data("companyid") || "<?php echo $companyid; ?>";
    let subcompanyid = $("body").data("subcompanyid") || "<?php echo $subcompanyid; ?>";
    let projectid = $("body").data("projectid") || "<?php echo $projectid; ?>";
    let routurl = $("body").data("routurl") || "<?php echo $prefix; ?>";

    let $dropdown = $("#oop_receivedtoaccountid");
    $dropdown.html('<option value="">Loading accounts...</option>');

    let fd = new FormData();
    fd.append("companyid", companyid);
    fd.append("subcompanyid", subcompanyid);
    fd.append("projectid", projectid);

    $.ajax({
      type: "POST",
      url: `${routurl}krf-orderandsales/modal/paymentadvice_modal.php?action=GetAccountdetailsFrommaster_option`,
      data: fd,
      contentType: false,
      processData: false,
      cache: false,
      dataType: "JSON",
      success: function (res) {
        $dropdown.empty();
        $dropdown.append('<option value="">Select Bank / Account</option>');
        if (Array.isArray(res) && res.length > 0) {
          res.forEach(item => {
            let label = (item.bankname || item.accountname || 'Account') + (item.accountno ? ` [${item.accountno}]` : '');
            $dropdown.append(`<option value="${item.accountid}">${label}</option>`);
          });
          if (selectedAccId) {
            $dropdown.val(selectedAccId);
          } else if (res.length === 1) {
            $dropdown.val(res[0].accountid);
          }
        }
      },
      error: function() {
        $dropdown.html('<option value="">Select Bank / Account</option>');
      }
    });
  }

  function submitOldOrderAdvancePayment() {
    let psono = $("#oop_psono").val();
    let clientid = $("#oop_clientid").val();
    let clientname = $("#oop_clientname").val();
    let clientmobileno = $("#oop_clientmobileno").val();
    let paymentdate = $("#oop_paymentdate").val();
    let amount = parseFloat($("#oop_amount").val() || 0);
    let bookingno = $("#oop_bookingno").val().trim();
    let sourceofpayment = $("#oop_sourceofpayment").val();
    let receivedtoaccountid = $("#oop_receivedtoaccountid").val();
    let utrnumber = $("#oop_utrnumber").val().trim();
    let casgreceivedby = $("#oop_casgreceivedby").val().trim();
    let casgreceuvelocation = $("#oop_casgreceuvelocation").val().trim();
    let purposeofamount = $("#oop_purpose").val().trim();
    let remarks = $("#oop_remarks").val().trim();
    let fileInput = document.getElementById("oop_paymentreceipt");
    let bookingReceiptInput = document.getElementById("oop_bookingreceipt");

    if (!psono) {
      if (typeof Swal !== 'undefined') {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Provisional Order number missing.' });
      } else {
        alert('Provisional Order number missing.');
      }
      return;
    }
    if (!paymentdate) {
      if (typeof Swal !== 'undefined') {
        Swal.fire({ icon: 'warning', title: 'Payment Date Required', text: 'Please select the payment date.' });
      } else {
        alert('Please select the payment date.');
      }
      return;
    }
    if (!amount || amount <= 0) {
      if (typeof Swal !== 'undefined') {
        Swal.fire({ icon: 'warning', title: 'Invalid Amount', text: 'Please enter a valid payment amount greater than 0.' });
      } else {
        alert('Please enter a valid payment amount greater than 0.');
      }
      return;
    }

    let companyid = $("body").data("companyid") || "<?php echo $companyid; ?>";
    let subcompanyid = $("body").data("subcompanyid") || "<?php echo $subcompanyid; ?>";
    let projectid = $("body").data("projectid") || "<?php echo $projectid; ?>";
    let loginid = $("#hidden_loginid").val() || $("body").data("loginid") || "<?php echo htmlspecialchars($loginid, ENT_QUOTES); ?>";
    let loginname = $("body").data("loginname") || "<?php echo htmlspecialchars($loginname, ENT_QUOTES); ?>";
    let routurl = $("body").data("routurl") || "<?php echo $prefix; ?>";

    let formData = new FormData();
    formData.append("companyid", companyid);
    formData.append("subcompanyid", subcompanyid);
    formData.append("projectid", projectid);
    formData.append("loginid", loginid);
    formData.append("loginname", loginname);
    formData.append("invoiceno", psono);
    formData.append("psono", psono);
    formData.append("clientid", clientid);
    formData.append("clientname", clientname);
    formData.append("clientmobileno", clientmobileno);
    formData.append("amount", amount);
    formData.append("paymentdate", paymentdate);
    formData.append("bookingno", bookingno);
    formData.append("sourceofpayment", sourceofpayment);
    formData.append("receivedtoaccountid", receivedtoaccountid);
    formData.append("utrnumber", utrnumber);
    formData.append("casgreceivedby", casgreceivedby);
    formData.append("casgreceuvelocation", casgreceuvelocation);
    formData.append("purposeofamount", purposeofamount);
    formData.append("remarks", remarks);

    if (fileInput && fileInput.files && fileInput.files.length > 0) {
      formData.append("paymentreceipt", fileInput.files[0]);
    }
    if (bookingReceiptInput && bookingReceiptInput.files && bookingReceiptInput.files.length > 0) {
      formData.append("bookingreceipt", bookingReceiptInput.files[0]);
    }

    let $btn = $("#btnSubmitOldOrderPayment");
    $btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-1"></span> Processing...');

    $.ajax({
      url: `${routurl}krf-orderandsales/modal/paymentadvice_modal.php?action=oldorderadvacepayment`,
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      cache: false,
      dataType: "json",
      success: function (res) {
        $btn.prop("disabled", false).html('<i class="fa fa-check-circle me-1"></i> Submit Payment Update');
        if (res && res.status === "success") {
          $("#oldOrderPaymentModal").modal("hide");
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: "success",
              title: "Payment Updated!",
              text: res.message || "Advance payment updated successfully.",
              confirmButtonColor: "#10b981",
              confirmButtonText: "OK"
            }).then(() => {
              window.location.reload();
            });
          } else {
            alert(res.message || "Advance payment updated successfully.");
            window.location.reload();
          }
        } else {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: "error",
              title: "Failed to Update Payment",
              text: (res && res.message) ? res.message : "An unexpected error occurred."
            });
          } else {
            alert((res && res.message) ? res.message : "An unexpected error occurred.");
          }
        }
      },
      error: function (xhr, status, error) {
        $btn.prop("disabled", false).html('<i class="fa fa-check-circle me-1"></i> Submit Payment Update');
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: "error",
            title: "Request Error",
            text: "Could not connect to server: " + error
          });
        } else {
          alert("Could not connect to server: " + error);
        }
      }
    });
  }

  // Active Status Filter (Defaults to 'Draft')
  window.__currentActiveStatusFilter = 'Draft';
  window.__currentMonthFilter = 'current'; // default: current month

  (function () {
    function normalizeOrderText(value) {
        return String(value || '')
            .toLowerCase()
            .replace(/\u00a0/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function selectedValues(dashboard, selector) {
        var select = dashboard.querySelector(selector);
        return select
            ? Array.prototype.map.call(select.selectedOptions, function (option) {
                return normalizeOrderText(option.value);
            })
            : [];
    }

    function resolveDashboard(sourceElement) {
        if (sourceElement && typeof sourceElement.closest === 'function') {
            var localDashboard = sourceElement.closest('.order-dashboard');
            if (localDashboard) return localDashboard;
        }

        var dashboards = document.querySelectorAll('.order-dashboard');
        for (var i = dashboards.length - 1; i >= 0; i--) {
            if (dashboards[i].offsetParent !== null) return dashboards[i];
        }
        return dashboards.length ? dashboards[dashboards.length - 1] : null;
    }

    // Recalculate summary KPI counts and amounts based on currently chosen Month / Date Range
    function updateKpiSummaryCounts(dashboard) {
        if (!dashboard) return;
        var monthVal = window.__currentMonthFilter || 'current';
        var currentMonthStr = '<?php echo $currentMonthKey; ?>'; // e.g. '2026-10'
        var fromDate = dashboard.querySelector('#customFromDate') ? dashboard.querySelector('#customFromDate').value : '';
        var toDate = dashboard.querySelector('#customToDate') ? dashboard.querySelector('#customToDate').value : '';

        var rows = dashboard.querySelectorAll('#orderStripsList > .order-strip');

        var counts = {
            pending: 0,
            payment_approval: 0,
            confirmed: 0,
            emergency: 0,
            cancelled: 0,
            all: 0
        };

        var amounts = {
            pending: 0,
            payment_approval: 0,
            confirmed: 0,
            emergency: 0,
            cancelled: 0,
            all: 0
        };

        var totalPeriodOrders = 0;

        Array.prototype.forEach.call(rows, function (row) {
            var rowMonth = row.getAttribute('data-order-month') || '';
            var rowDate = row.getAttribute('data-order-date') || '';
            var category = row.getAttribute('data-category') || 'draft';
            if (category === 'draft') category = 'pending';
            var itemAmt = parseFloat(row.getAttribute('data-item-amount') || 0);

            // Check if matches the selected month/date range
            var inDateRange = true;
            if (monthVal === 'current') {
                inDateRange = (rowMonth === currentMonthStr);
            } else if (monthVal === 'all') {
                inDateRange = true;
            } else if (monthVal === 'custom') {
                if (fromDate && rowDate < fromDate) inDateRange = false;
                if (toDate && rowDate > toDate) inDateRange = false;
            } else {
                inDateRange = (rowMonth === monthVal);
            }

            if (inDateRange) {
                totalPeriodOrders++;
                counts.all++;
                amounts.all += itemAmt;

                if (counts[category] !== undefined) {
                    counts[category]++;
                    amounts[category] += itemAmt;
                }
            }
        });

        // Format and update KPI cards
        var formatInr = function(num) {
            return '₹' + Number(num || 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        };

        var elPendingCount = dashboard.querySelector('#kpi_pending_count');
        var elPendingAmt = dashboard.querySelector('#kpi_pending_amount');
        if (elPendingCount) elPendingCount.textContent = counts.pending;
        if (elPendingAmt) elPendingAmt.textContent = formatInr(amounts.pending);

        var elPaymentApprovalCount = dashboard.querySelector('#kpi_payment_approval_count');
        var elPaymentApprovalAmt = dashboard.querySelector('#kpi_payment_approval_amount');
        if (elPaymentApprovalCount) elPaymentApprovalCount.textContent = counts.payment_approval;
        if (elPaymentApprovalAmt) elPaymentApprovalAmt.textContent = formatInr(amounts.payment_approval);

        var elConfirmedCount = dashboard.querySelector('#kpi_confirmed_count');
        var elConfirmedAmt = dashboard.querySelector('#kpi_confirmed_amount');
        if (elConfirmedCount) elConfirmedCount.textContent = counts.confirmed;
        if (elConfirmedAmt) elConfirmedAmt.textContent = formatInr(amounts.confirmed);

        var elEmergencyCount = dashboard.querySelector('#kpi_emergency_count');
        var elEmergencyAmt = dashboard.querySelector('#kpi_emergency_amount');
        if (elEmergencyCount) elEmergencyCount.textContent = counts.emergency;
        if (elEmergencyAmt) elEmergencyAmt.textContent = formatInr(amounts.emergency);

        var elCancelledCount = dashboard.querySelector('#kpi_cancelled_count');
        var elCancelledAmt = dashboard.querySelector('#kpi_cancelled_amount');
        if (elCancelledCount) elCancelledCount.textContent = counts.cancelled;
        if (elCancelledAmt) elCancelledAmt.textContent = formatInr(amounts.cancelled);

        var elAllCount = dashboard.querySelector('#kpi_all_count');
        var elAllAmt = dashboard.querySelector('#kpi_all_amount');
        if (elAllCount) elAllCount.textContent = counts.all;
        if (elAllAmt) elAllAmt.textContent = formatInr(amounts.all);

        var elPeriodCount = dashboard.querySelector('#periodOrderCountBadge');
        if (elPeriodCount) elPeriodCount.textContent = totalPeriodOrders + ' Orders';
        var elPeriodCountHeader = dashboard.querySelector('#activePeriodCountBadge');
        if (elPeriodCountHeader) elPeriodCountHeader.textContent = totalPeriodOrders + ' Orders';
    }

    window.__isUpdatingFilterDropdowns = false;

    function formatDisplayDate(dateStr) {
        if (!dateStr || dateStr === '0000-00-00' || dateStr === '0000-00-00 00:00:00') return '';
        var clean = String(dateStr).trim();
        if (/^[0-9]{4}-[0-9]{2}-[0-9]{2}/.test(clean)) {
            var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            var parts = clean.substring(0, 10).split('-');
            var year = parts[0];
            var monthIndex = parseInt(parts[1], 10) - 1;
            var day = parts[2];
            if (monthIndex >= 0 && monthIndex < 12) {
                return day + '-' + months[monthIndex] + '-' + year;
            }
        }
        return clean;
    }

    function syncSelectOptions(selectEl, optionsList, currentSelectedNormVals) {
        if (!selectEl) return;

        var currentOptions = selectEl.options;
        var changed = (currentOptions.length !== optionsList.length);
        if (!changed) {
            for (var i = 0; i < optionsList.length; i++) {
                if (currentOptions[i].value !== optionsList[i].value || currentOptions[i].textContent !== optionsList[i].text) {
                    changed = true;
                    break;
                }
            }
        }

        var validSelectedValues = [];
        for (var j = 0; j < optionsList.length; j++) {
            var opt = optionsList[j];
            if (currentSelectedNormVals.indexOf(normalizeOrderText(opt.value)) !== -1) {
                validSelectedValues.push(opt.value);
            }
        }

        if (!changed) {
            var currentVals = (window.jQuery ? $(selectEl).val() : []) || [];
            if (!Array.isArray(currentVals)) currentVals = currentVals ? [currentVals] : [];
            var selChanged = (currentVals.length !== validSelectedValues.length);
            if (!selChanged) {
                for (var k = 0; k < currentVals.length; k++) {
                    if (validSelectedValues.indexOf(currentVals[k]) === -1) {
                        selChanged = true;
                        break;
                    }
                }
            }
            if (selChanged && window.jQuery && $(selectEl).data('select2')) {
                $(selectEl).val(validSelectedValues).trigger('change.select2');
            }
            return;
        }

        selectEl.innerHTML = '';
        for (var m = 0; m < optionsList.length; m++) {
            var item = optionsList[m];
            var isSel = (currentSelectedNormVals.indexOf(normalizeOrderText(item.value)) !== -1);
            var optEl = document.createElement('option');
            optEl.value = item.value;
            optEl.textContent = item.text || item.value;
            if (isSel) optEl.selected = true;
            selectEl.appendChild(optEl);
        }

        if (window.jQuery && $(selectEl).data('select2')) {
            $(selectEl).val(validSelectedValues).trigger('change.select2');
        }
    }

    window.filterProvisionalOrders = function (sourceElement) {
        if (window.__isUpdatingFilterDropdowns) return;

        var dashboard = resolveDashboard(sourceElement);
        if (!dashboard) return;

        // 1. Recalculate summary KPI numbers for current date/month selection
        updateKpiSummaryCounts(dashboard);

        var searchInput = dashboard.querySelector('#orderSearchInput');
        var searchWords = normalizeOrderText(searchInput ? searchInput.value : '')
            .split(' ')
            .filter(Boolean);

        var selectedClients = selectedValues(dashboard, '#clientFilter');
        var selectedDeliveryDates = selectedValues(dashboard, '#deliverDateFilter');
        var selectedOrderDates = selectedValues(dashboard, '#orderDateFilter');
        var selectedStatuses = selectedValues(dashboard, '#statusFilter');
        var selectedLoginNames = selectedValues(dashboard, '#loginFilter');
        var selectedProducts = selectedValues(dashboard, '#productFilter');
        var selectedSowingStatuses = selectedValues(dashboard, '#sowingStatusFilter');
        var selectedPaymentStatuses = selectedValues(dashboard, '#paymentStatusFilter');

        var activeStatusFilter = window.__currentActiveStatusFilter || 'Draft';
        var monthVal = window.__currentMonthFilter || 'current';
        var currentMonthStr = '<?php echo $currentMonthKey; ?>'; // e.g. '2026-10'
        var fromDate = dashboard.querySelector('#customFromDate') ? dashboard.querySelector('#customFromDate').value : '';
        var toDate = dashboard.querySelector('#customToDate') ? dashboard.querySelector('#customToDate').value : '';

        var rows = dashboard.querySelectorAll('#orderStripsList > .order-strip');
        var visibleCount = 0;

        // Faceted option maps
        var orderDateMap = {};
        var clientMap = {};
        var deliverDateMap = {};
        var loginMap = {};
        var statusMap = {};
        var productMap = {};
        var sowingStatusMap = {};
        var paymentStatusMap = {};

        Array.prototype.forEach.call(rows, function (row) {
            var rowClientRaw = row.getAttribute('data-client') || row.getAttribute('data-client-name') || '';
            var rowLoginRaw = row.getAttribute('data-login') || '';
            var rowOrderDate = row.getAttribute('data-order-date') || '';
            var rowDeliverDate = row.getAttribute('data-deliver-date') || '';
            var rowStatusRaw = row.getAttribute('data-status') || '';
            var rowProductRaw = row.getAttribute('data-product-name') || '';
            var rowSowingRaw = row.getAttribute('data-sowing-status') || '';
            var rowPaymentRaw = row.getAttribute('data-payment-status') || '';
            var rowMonth = row.getAttribute('data-order-month') || '';
            var rowCat = row.getAttribute('data-category') || '';

            var rowClientNorm = normalizeOrderText(rowClientRaw);
            var rowLoginNorm = normalizeOrderText(rowLoginRaw);
            var rowOrderDateNorm = normalizeOrderText(rowOrderDate);
            var rowDeliverDateNorm = normalizeOrderText(rowDeliverDate);
            var rowStatusNorm = normalizeOrderText(rowStatusRaw);
            var rowProductNorm = normalizeOrderText(rowProductRaw);
            var rowSowingNorm = normalizeOrderText(rowSowingRaw);
            var rowPaymentNorm = normalizeOrderText(rowPaymentRaw);

            // Fast cached search text
            var rowSearchText = row._cachedSearchText;
            if (rowSearchText === undefined) {
                rowSearchText = normalizeOrderText([
                    rowClientRaw,
                    row.getAttribute('data-mobile') || '',
                    row.getAttribute('data-order-no') || '',
                    row.getAttribute('data-product-name') || '',
                    row.getAttribute('data-prod-stockno') || '',
                    row.getAttribute('data-dispatch-date') || '',
                    (row.getAttribute('data-dispatched') === '1' ? 'dispatched' : ''),
                    rowLoginRaw
                ].join(' '));
                row._cachedSearchText = rowSearchText;
            }

            // 1. Date / Month matching
            var matchesDate = true;
            if (monthVal === 'current') {
                matchesDate = (rowMonth === currentMonthStr);
            } else if (monthVal === 'all') {
                matchesDate = true;
            } else if (monthVal === 'custom') {
                if (fromDate && rowOrderDate < fromDate) matchesDate = false;
                if (toDate && rowOrderDate > toDate) matchesDate = false;
            } else {
                matchesDate = (rowMonth === monthVal);
            }
            if (!matchesDate) {
                row.style.setProperty('display', 'none', 'important');
                row.hidden = true;
                return;
            }

            // 2. Active KPI Card matching
            var matchesKpi = true;
            if (activeStatusFilter && activeStatusFilter !== 'ALL') {
                var normActive = normalizeOrderText(activeStatusFilter);
                if (normActive === 'draft' || normActive === 'pending for payment' || normActive === 'pending') {
                    matchesKpi = (rowCat === 'draft' || rowCat === 'pending' || rowStatusNorm === 'draft' || rowStatusNorm === 'pending for payment');
                } else if (normActive === 'pending payment approval' || normActive === 'pending approval') {
                    matchesKpi = (rowCat === 'payment_approval' || rowStatusNorm === 'pending payment approval' || rowStatusNorm === 'pending approval');
                } else if (normActive === 'confirmed') {
                    matchesKpi = (rowCat === 'confirmed' || rowStatusNorm === 'confirmed' || rowStatusNorm.indexOf('confirmed') !== -1 || rowCat === 'dispatched' || rowStatusNorm.indexOf('dispatched') !== -1 || row.getAttribute('data-dispatched') === '1');
                } else if (normActive === 'pending approval for emergency sowing' || normActive === 'emergency sowing' || normActive === 'emergency') {
                    matchesKpi = (rowCat === 'emergency' || rowStatusNorm === 'pending approval for emergency sowing' || rowStatusNorm === 'emergency sowing');
                } else if (normActive === 'cancelled') {
                    matchesKpi = (rowCat === 'cancelled' || rowStatusNorm === 'cancelled');
                } else {
                    matchesKpi = (rowStatusNorm === normActive);
                }
            }
            if (!matchesKpi) {
                row.style.setProperty('display', 'none', 'important');
                row.hidden = true;
                return;
            }

            // 3. Search query matching
            var matchesSearch = searchWords.length === 0 || searchWords.every(function (word) {
                return rowSearchText.indexOf(word) !== -1;
            });
            if (!matchesSearch) {
                row.style.setProperty('display', 'none', 'important');
                row.hidden = true;
                return;
            }

            // 4. Secondary filter conditions for this row
            var mClient = (selectedClients.length === 0 || selectedClients.indexOf(rowClientNorm) !== -1);
            var mOrderDate = (selectedOrderDates.length === 0 || selectedOrderDates.indexOf(rowOrderDateNorm) !== -1);
            var mDeliverDate = (selectedDeliveryDates.length === 0 || selectedDeliveryDates.indexOf(rowDeliverDateNorm) !== -1);
            var mLogin = (selectedLoginNames.length === 0 || selectedLoginNames.indexOf(rowLoginNorm) !== -1);
            var mStatus = (selectedStatuses.length === 0 || selectedStatuses.indexOf(rowStatusNorm) !== -1);
            var mProduct = (selectedProducts.length === 0 || selectedProducts.indexOf(rowProductNorm) !== -1);
            var mSowing = (selectedSowingStatuses.length === 0 || selectedSowingStatuses.indexOf(rowSowingNorm) !== -1);
            var mPayment = (selectedPaymentStatuses.length === 0 || selectedPaymentStatuses.indexOf(rowPaymentNorm) !== -1);

            // Faceted collection for each filter dropdown (matches ALL other filters):
            if (mClient && mDeliverDate && mLogin && mStatus && mProduct && mSowing && mPayment) {
                if (rowOrderDate && rowOrderDate !== '0000-00-00') {
                    orderDateMap[rowOrderDate] = formatDisplayDate(rowOrderDate);
                }
            }

            if (mOrderDate && mDeliverDate && mLogin && mStatus && mProduct && mSowing && mPayment) {
                if (rowClientRaw && rowClientRaw.trim()) {
                    var cName = rowClientRaw.trim();
                    clientMap[cName] = cName;
                }
            }

            if (mOrderDate && mClient && mLogin && mStatus && mProduct && mSowing && mPayment) {
                if (rowDeliverDate && rowDeliverDate !== '0000-00-00' && rowDeliverDate !== '0') {
                    deliverDateMap[rowDeliverDate] = formatDisplayDate(rowDeliverDate);
                }
            }

            if (mOrderDate && mClient && mDeliverDate && mStatus && mProduct && mSowing && mPayment) {
                if (rowLoginRaw && rowLoginRaw.trim()) {
                    var lName = rowLoginRaw.trim();
                    loginMap[lName] = lName;
                }
            }

            if (mOrderDate && mClient && mDeliverDate && mLogin && mProduct && mSowing && mPayment) {
                if (rowStatusRaw && rowStatusRaw.trim()) {
                    var sName = rowStatusRaw.trim();
                    statusMap[sName] = sName;
                }
                if (row.getAttribute('data-dispatched') === '1') {
                    statusMap['Dispatched'] = 'Dispatched';
                }
            }

            if (mOrderDate && mClient && mDeliverDate && mLogin && mStatus && mSowing && mPayment) {
                if (rowProductRaw && rowProductRaw.trim()) {
                    var pName = rowProductRaw.trim();
                    productMap[pName] = pName;
                }
            }

            if (mOrderDate && mClient && mDeliverDate && mLogin && mStatus && mProduct && mPayment) {
                if (rowSowingRaw && rowSowingRaw.trim()) {
                    var sowName = rowSowingRaw.trim();
                    sowingStatusMap[sowName] = sowName;
                }
            }

            if (mOrderDate && mClient && mDeliverDate && mLogin && mStatus && mProduct && mSowing) {
                if (rowPaymentRaw && rowPaymentRaw.trim()) {
                    var payName = rowPaymentRaw.trim();
                    paymentStatusMap[payName] = payName;
                }
            }

            // 5. Overall visibility of this row
            var shouldShow = (mClient && mOrderDate && mDeliverDate && mLogin && mStatus && mProduct && mSowing && mPayment);
            row.style.setProperty('display', shouldShow ? 'grid' : 'none', 'important');
            row.hidden = !shouldShow;

            if (shouldShow) {
                visibleCount++;
                var serialCell = row.querySelector('.strip-item:first-child .row-serial-number');
                if (serialCell) serialCell.textContent = String(visibleCount).padStart(2, '0');
            }
        });

        var noResults = dashboard.querySelector('#noSearchResults');
        if (noResults) {
            noResults.hidden = visibleCount !== 0;
            noResults.style.display = visibleCount === 0 ? 'block' : 'none';
        }

        // 6. Build sorted options lists and sync dropdowns
        var orderDateList = Object.keys(orderDateMap).sort().reverse().map(function (k) {
            return { value: k, text: orderDateMap[k] };
        });

        var clientList = Object.keys(clientMap).sort(function (a, b) {
            return a.localeCompare(b, undefined, { sensitivity: 'base' });
        }).map(function (k) {
            return { value: k, text: clientMap[k] };
        });

        var deliverDateList = Object.keys(deliverDateMap).sort().reverse().map(function (k) {
            return { value: k, text: deliverDateMap[k] };
        });

        var loginList = Object.keys(loginMap).sort(function (a, b) {
            return a.localeCompare(b, undefined, { sensitivity: 'base' });
        }).map(function (k) {
            return { value: k, text: loginMap[k] };
        });

        var statusOrder = [
            'Draft',
            'Pending for Payment',
            'Pending Payment Approval',
            'Confirmed',
            'Pending Approval for Emergency Sowing',
            'Cancelled'
        ];
        var statusList = Object.keys(statusMap).sort(function (a, b) {
            var ia = statusOrder.indexOf(a);
            var ib = statusOrder.indexOf(b);
            if (ia !== -1 && ib !== -1) return ia - ib;
            if (ia !== -1) return -1;
            if (ib !== -1) return 1;
            return a.localeCompare(b);
        }).map(function (k) {
            return { value: k, text: statusMap[k] };
        });

        var productList = Object.keys(productMap).sort(function (a, b) {
            return a.localeCompare(b, undefined, { sensitivity: 'base' });
        }).map(function (k) {
            return { value: k, text: productMap[k] };
        });

        var sowingOrder = ['Pending', 'In Progress', 'Sowed', 'Done'];
        var sowingStatusList = Object.keys(sowingStatusMap).sort(function (a, b) {
            var ia = sowingOrder.indexOf(a);
            var ib = sowingOrder.indexOf(b);
            if (ia !== -1 && ib !== -1) return ia - ib;
            return a.localeCompare(b);
        }).map(function (k) {
            return { value: k, text: sowingStatusMap[k] };
        });

        var paymentOrder = ['Full Paid', 'Partial Advance', 'Unpaid'];
        var paymentStatusList = Object.keys(paymentStatusMap).sort(function (a, b) {
            var ia = paymentOrder.indexOf(a);
            var ib = paymentOrder.indexOf(b);
            if (ia !== -1 && ib !== -1) return ia - ib;
            return a.localeCompare(b);
        }).map(function (k) {
            return { value: k, text: paymentStatusMap[k] === 'Unpaid' ? 'Unpaid / No Advance' : paymentStatusMap[k] };
        });

        window.__isUpdatingFilterDropdowns = true;
        try {
            syncSelectOptions(dashboard.querySelector('#orderDateFilter'), orderDateList, selectedOrderDates);
            syncSelectOptions(dashboard.querySelector('#clientFilter'), clientList, selectedClients);
            syncSelectOptions(dashboard.querySelector('#deliverDateFilter'), deliverDateList, selectedDeliveryDates);
            syncSelectOptions(dashboard.querySelector('#statusFilter'), statusList, selectedStatuses);
            syncSelectOptions(dashboard.querySelector('#loginFilter'), loginList, selectedLoginNames);
            syncSelectOptions(dashboard.querySelector('#productFilter'), productList, selectedProducts);
            syncSelectOptions(dashboard.querySelector('#sowingStatusFilter'), sowingStatusList, selectedSowingStatuses);
            syncSelectOptions(dashboard.querySelector('#paymentStatusFilter'), paymentStatusList, selectedPaymentStatuses);
        } finally {
            window.__isUpdatingFilterDropdowns = false;
        }

        // Update active badge on "More Filters" expand button
        var expandedActiveCount = selectedLoginNames.length + selectedProducts.length + selectedSowingStatuses.length + selectedPaymentStatuses.length;
        var moreBadge = dashboard.querySelector('#moreFiltersActiveBadge');
        var toggleBtn = dashboard.querySelector('#toggleMoreFiltersBtn');
        if (moreBadge) {
            moreBadge.textContent = expandedActiveCount;
            moreBadge.style.display = expandedActiveCount > 0 ? 'inline-block' : 'none';
        }
        if (toggleBtn) {
            if (expandedActiveCount > 0) {
                toggleBtn.classList.add('has-active');
            } else {
                toggleBtn.classList.remove('has-active');
            }
        }
    };

    // Event binding
    if (!window.__krfProvisionalFilterEventsBound) {
        window.__krfProvisionalFilterEventsBound = true;

        var orderSearchDebounceTimer = null;
        document.addEventListener('input', function (event) {
            if (window.__isUpdatingFilterDropdowns) return;
            if (event.target && event.target.matches('.order-dashboard #orderSearchInput')) {
                clearTimeout(orderSearchDebounceTimer);
                orderSearchDebounceTimer = setTimeout(function () {
                    window.filterProvisionalOrders(event.target);
                }, 200);
            }
        });

        document.addEventListener('change', function (event) {
            if (window.__isUpdatingFilterDropdowns) return;
            if (event.target && event.target.matches(
                '.order-dashboard #clientFilter, ' +
                '.order-dashboard #deliverDateFilter, ' +
                '.order-dashboard #orderDateFilter, ' +
                '.order-dashboard #statusFilter, ' +
                '.order-dashboard #loginFilter, ' +
                '.order-dashboard #productFilter, ' +
                '.order-dashboard #sowingStatusFilter, ' +
                '.order-dashboard #paymentStatusFilter'
            )) {
                if (!window.jQuery || !$(event.target).data('select2')) {
                    window.filterProvisionalOrders(event.target);
                }
            }
        });

        // Toggle More Filters expand/collapse
        document.addEventListener('click', function (event) {
            var toggleBtn = event.target.closest('#toggleMoreFiltersBtn');
            if (toggleBtn) {
                var container = document.getElementById('expandedFiltersContainer');
                if (container) {
                    var isVisible = container.style.display !== 'none';
                    if (isVisible) {
                        container.style.display = 'none';
                        toggleBtn.classList.remove('active');
                        var txt = toggleBtn.querySelector('.btn-toggle-text');
                        if (txt) txt.textContent = 'More Filters';
                    } else {
                        container.style.display = 'grid';
                        toggleBtn.classList.add('active');
                        var txt = toggleBtn.querySelector('.btn-toggle-text');
                        if (txt) txt.textContent = 'Fewer Filters';
                        // Re-trigger select2 container widths
                        if (window.jQuery && $.fn.select2) {
                            $('#expandedFiltersContainer .select2').trigger('change.select2');
                        }
                    }
                }
            }
        });

        // KPI Card click: filter by status & switch active card
        document.addEventListener('click', function (event) {
            var kpiCard = event.target.closest('.summary-kpi-card');
            if (kpiCard) {
                var dashboard = resolveDashboard(kpiCard);
                if (!dashboard) return;

                // Update active classes
                var allCards = dashboard.querySelectorAll('.summary-kpi-card');
                allCards.forEach(function(c) { c.classList.remove('active'); });
                kpiCard.classList.add('active');

                var filterVal = kpiCard.getAttribute('data-status-filter') || 'ALL';
                window.__currentActiveStatusFilter = filterVal;

                // Reset secondary dropdown filters when switching KPI cards
                var secondarySelects = dashboard.querySelectorAll(
                    '#clientFilter, #deliverDateFilter, #orderDateFilter, #statusFilter, #loginFilter, #productFilter, #sowingStatusFilter, #paymentStatusFilter'
                );
                window.__isUpdatingFilterDropdowns = true;
                Array.prototype.forEach.call(secondarySelects, function (select) {
                    Array.prototype.forEach.call(select.options, function (opt) {
                        opt.selected = false;
                    });
                    if (window.jQuery && $(select).data('select2')) {
                        $(select).val(null).trigger('change.select2');
                    }
                });
                window.__isUpdatingFilterDropdowns = false;

                window.filterProvisionalOrders(kpiCard);
            }
        });

        // Month Selector Change
        document.addEventListener('change', function (event) {
            if (window.__isUpdatingFilterDropdowns) return;
            if (event.target && event.target.matches('#provisionalMonthSelector')) {
                var val = event.target.value;
                window.__currentMonthFilter = val;
                var dashboard = resolveDashboard(event.target);
                var customBox = dashboard ? dashboard.querySelector('#customDateRangeBox') : null;
                var activeLabel = dashboard ? dashboard.querySelector('#activePeriodLabel') : null;

                if (val === 'custom') {
                    if (customBox) customBox.style.display = 'flex';
                    if (activeLabel) activeLabel.textContent = 'Custom Date Range';
                } else {
                    if (customBox) customBox.style.display = 'none';
                    if (activeLabel) {
                        var optText = event.target.options[event.target.selectedIndex].text.replace(/^📅\s*|🌐\s*|🛠️\s*/g, '');
                        activeLabel.textContent = optText;
                    }
                    window.filterProvisionalOrders(event.target);
                }
            }
        });

        // Apply Custom Date Range Button
        document.addEventListener('click', function (event) {
            if (window.__isUpdatingFilterDropdowns) return;
            var applyBtn = event.target.closest('#btnApplyCustomDate');
            if (applyBtn) {
                var dashboard = resolveDashboard(applyBtn);
                if (!dashboard) return;
                var from = dashboard.querySelector('#customFromDate').value;
                var to = dashboard.querySelector('#customToDate').value;
                var activeLabel = dashboard.querySelector('#activePeriodLabel');
                if (activeLabel && from && to) {
                    activeLabel.textContent = from + ' to ' + to;
                }
                window.filterProvisionalOrders(applyBtn);
            }
        });

        // Clear / Reset Filters Button
        document.addEventListener('click', function (event) {
            var clearButton = event.target.closest('.order-dashboard #clearFilters');
            if (!clearButton) return;

            var dashboard = clearButton.closest('.order-dashboard');
            var searchInput = dashboard.querySelector('#orderSearchInput');
            if (searchInput) searchInput.value = '';

            // Reset Month filter to current month
            var monthSelect = dashboard.querySelector('#provisionalMonthSelector');
            if (monthSelect) {
                monthSelect.value = 'current';
                window.__currentMonthFilter = 'current';
                var customBox = dashboard.querySelector('#customDateRangeBox');
                if (customBox) customBox.style.display = 'none';
                var activeLabel = dashboard.querySelector('#activePeriodLabel');
                if (activeLabel) activeLabel.textContent = '<?php echo date('F Y'); ?>';
            }

            // Reset Status KPI card to default: Draft
            var allCards = dashboard.querySelectorAll('.summary-kpi-card');
            allCards.forEach(function(c) { c.classList.remove('active'); });
            var pendingCard = dashboard.querySelector('.summary-kpi-card.card-pending');
            if (pendingCard) pendingCard.classList.add('active');
            window.__currentActiveStatusFilter = 'Draft';

            // Reset Select2 dropdowns
            var selects = dashboard.querySelectorAll(
                '#clientFilter, #deliverDateFilter, #orderDateFilter, #statusFilter, #loginFilter, #productFilter, #sowingStatusFilter, #paymentStatusFilter'
            );
            var clearSearchBtn = dashboard.querySelector('#clearSearchInputBtn');
            if (clearSearchBtn) clearSearchBtn.style.display = 'none';
            window.__isUpdatingFilterDropdowns = true;
            Array.prototype.forEach.call(selects, function (select) {
                Array.prototype.forEach.call(select.options, function (option) {
                    option.selected = false;
                });
                if (window.jQuery && $(select).data('select2')) {
                    $(select).val(null).trigger('change.select2');
                }
            });
            window.__isUpdatingFilterDropdowns = false;

            window.filterProvisionalOrders(clearButton);
        });
    }
  })();

  $(document).ready(function () {
    if ($.fn.select2) {
        $('.order-dashboard .select2').select2({
            width: '100%',
            closeOnSelect: false,
            templateResult: function (data) {
                return data.id ? $('<span>').text(data.text) : data.text;
            }
        });
    }

    var provisionalFilterSelector =
        '.order-dashboard #clientFilter, ' +
        '.order-dashboard #deliverDateFilter, ' +
        '.order-dashboard #orderDateFilter, ' +
        '.order-dashboard #statusFilter, ' +
        '.order-dashboard #loginFilter, ' +
        '.order-dashboard #productFilter, ' +
        '.order-dashboard #sowingStatusFilter, ' +
        '.order-dashboard #paymentStatusFilter';

    // Clear search button interaction
    var searchBox = document.querySelector('.order-dashboard #orderSearchInput');
    var clearSearchBoxBtn = document.querySelector('.order-dashboard #clearSearchInputBtn');
    if (searchBox && clearSearchBoxBtn) {
        searchBox.addEventListener('input', function() {
            clearSearchBoxBtn.style.display = this.value.trim() !== '' ? 'block' : 'none';
        });
        clearSearchBoxBtn.addEventListener('click', function() {
            searchBox.value = '';
            clearSearchBoxBtn.style.display = 'none';
            window.filterProvisionalOrders(searchBox);
            searchBox.focus();
        });
    }

    $(document)
        .off(
            'change.provisionalOrderFilters ' +
            'select2:select.provisionalOrderFilters ' +
            'select2:unselect.provisionalOrderFilters ' +
            'select2:clear.provisionalOrderFilters',
            provisionalFilterSelector
        )
        .on(
            'change.provisionalOrderFilters ' +
            'select2:select.provisionalOrderFilters ' +
            'select2:unselect.provisionalOrderFilters ' +
            'select2:clear.provisionalOrderFilters',
            provisionalFilterSelector,
            function () {
                if (!window.__isUpdatingFilterDropdowns) {
                    window.filterProvisionalOrders(this);
                }
            }
        );

    // Initial filter execution on page load (defaults to Current Month + Pending for Payment active)
    var initialSearch = document.querySelector('.order-dashboard #orderSearchInput');
    window.filterProvisionalOrders(initialSearch);

    // Close Swal loading dialog smoothly after initial render and filtering is complete
    if (typeof Swal !== 'undefined' && Swal.isVisible()) {
        setTimeout(function () {
            Swal.close();
        }, 150);
    }

    $(window).on('load', function () {
        if (typeof Swal !== 'undefined' && Swal.isVisible()) {
            Swal.close();
        }
    });

    // View Order
    $(document).on("click", ".viewOrderBtn", function () {
      let companyid = $("body").data("companyid");
      let subcompanyid = $("body").data("subcompanyid");
      let projectid = $("body").data("projectid");
      let psono = $(this).data("psono");
      $.ajax({
        url: "modal/fetch_order_view.php",
        type: "POST",
        data: { psono: psono, companyid: companyid, subcompanyid: subcompanyid, projectid: projectid },
        success: function (data) {
          $("#viewOrderContent").html(data);
          $("#viewOrderModal").modal("show");
        }
      });
    });

    // Edit Order - Redirect to unified Add/Edit form
    $(document).on("click", ".editOrderBtn", function () {
      let key = $("body").data("key");
      let routurl = $("body").data("routurl") || "";
      let psono = $(this).data("psono");
      window.location.href = routurl + "krf-orderandsales/addNewProvisionalsaleorder.php?mode=edit&psono=" + encodeURIComponent(psono) + "&k=" + encodeURIComponent(key) + "&mdid=<?php echo urlencode($mdid); ?>&iframe=true";
    });

    // createsaleinvoice
    $(document).on("click", ".createsaleinvoice", function () {
      let psono = $(this).data("psono");
      let key = $("body").data("key");
      let routurl = $("body").data("routurl");
      window.location.href = routurl + "krf-orderandsales/salesinvoiceAgainsProvisionalorder.php?k=" + key + "&mdid=<?php echo $mdid; ?>&psono=" + psono + "&iframe=true";
    });

    // newprovisionalorder
    $(document).on("click", ".newprovisionalorder", function () {
      let key = $("body").data("key");
      let routurl = $("body").data("routurl");
      window.location.href = routurl + "krf-orderandsales/addNewProvisionalsaleorder.php?k=" + key + "&mdid=<?php echo $mdid; ?>&psono=&iframe=true";
    });

    // Function to load the Cancel / Review Order Modal
    window.loadCancelOrderModal = function (psono, targetItemId) {
      if (!psono) return;
      let companyid = $("body").data("companyid") || 0;
      let subcompanyid = $("body").data("subcompanyid") || 0;
      let projectid = $("body").data("projectid") || 0;

      $("#cancelOrderContent").html(
        '<div class="text-center py-5">' +
        '  <div class="spinner-border text-primary" role="status"></div>' +
        '  <p class="mt-2 text-muted fw-bold">Loading order review details...</p>' +
        '</div>'
      );
      $("#cancelOrderModal").modal("show");

      $.ajax({
        url: "modal/fetch_order_cancel_view.php",
        type: "POST",
        data: {
          psono: psono,
          target_item_id: targetItemId || 0,
          companyid: companyid,
          subcompanyid: subcompanyid,
          projectid: projectid
        },
        success: function (html) {
          $("#cancelOrderContent").html(html);
          updateUnifiedCancelButton();
        },
        error: function () {
          $("#cancelOrderContent").html(
            '<div class="alert alert-danger m-3">' +
            '  <i class="fa fa-exclamation-triangle me-2"></i> Failed to load order details. Please try again.' +
            '</div>'
          );
        }
      });
    };

    // Open Cancel / Review Order Modal on Delete Icon click
    $(document).on("click", ".removeOrderBtn", function (e) {
      e.preventDefault();
      let psono = $(this).data("psono");
      let itemId = $(this).data("item-id");
      if (!psono) return;
      loadCancelOrderModal(psono, itemId);
    });

    // Update the single cancel button visibility, text, and icon based on checkbox selection
    function updateUnifiedCancelButton() {
      let total = $(".cancel-item-chk:not(:disabled)").length;
      let selectedBoxes = $(".cancel-item-chk:not(:disabled):checked");
      let checked = selectedBoxes.length;
      let $btn = $("#btnCancelOrderSubmit");
      let $text = $("#cancelBtnText");
      let $icon = $("#cancelBtnIcon");

      let totalActiveInOrder = parseInt($("#cancelOrderMeta").data("active-items") || $("#advanceSettlementSection").data("active-items") || $btn.data("active-items") || total);
      let lockedItemsCount = parseInt($("#cancelOrderMeta").data("locked-items") || $("#advanceSettlementSection").data("locked-items") || $btn.data("locked-items") || 0);

      // Whole order cancel is ONLY possible when NO items are locked AND all active items in the order are checked
      let isWholeCancel = (lockedItemsCount === 0 && checked > 0 && checked === totalActiveInOrder);

      let totalOrderAmount = parseFloat($("#advanceSettlementSection").data("total-order-amount") || 0);
      let totalReceived = parseFloat($("#advanceSettlementSection").data("total-received") || 0);

      let selectedCancelAmount = 0;
      selectedBoxes.each(function () {
        selectedCancelAmount += parseFloat($(this).data("amount") || 0);
      });
      let restOrderAmount = Math.max(0, totalOrderAmount - selectedCancelAmount);

      if (checked === 0) {
        // Hide button when no items are checked
        $btn.hide();
        $text.text("Cancel Selected Items (0)");
      } else {
        // Show and enable button when checked count > 0
        $btn.show().prop("disabled", false);
        if (isWholeCancel) {
          $icon.attr("class", "fa fa-ban me-1");
          $text.text(totalActiveInOrder > 1 ? "Cancel Whole Provisional Order (" + checked + ")" : "Cancel Selected Item (1)");
        } else {
          $icon.attr("class", "fa fa-trash-alt me-1");
          $text.text(checked === 1 ? "Cancel Selected Item (1)" : "Cancel Selected Items (" + checked + ")");
        }
      }

      // Dynamic Settlement Section Visibility & Handling
      let $settlementSection = $("#advanceSettlementSection");
      let $settlementRadioOptions = $("#settlementRadioOptions");
      let $settlementNotice = $("#settlementNotice");

      if ($settlementSection.length > 0 && totalReceived > 0) {
        $settlementSection.show();
        if (checked === 0) {
          // No items checked yet
          $settlementRadioOptions.hide();
          $settlementNotice.html(
            '<i class="fa fa-info-circle text-primary me-1"></i> An amount of <strong>₹' + totalReceived.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong> has been received against this order. Check item(s) above to review cancellation impact.'
          );
        } else if (isWholeCancel) {
          // Whole order is cancelled: no other products left in this order to adjust against
          $settlementRadioOptions.show();
          $("#cardSettleAdjust").hide();
          $("#cardSettleCreditNote").show();
          $("#cardSettleRefund").show();

          // If adjust was checked, switch to credit note
          if ($("#settleAdjust").is(":checked")) {
            $("#settleCreditNote").prop("checked", true);
          }
          $(".settlement-radio-card").removeClass("active-cn active-refund active-adjust");
          if ($("input[name='financial_settlement']:checked").val() === 'refund') {
            $("#cardSettleRefund").addClass("active-refund");
          } else {
            $("#cardSettleCreditNote").addClass("active-cn");
          }

          $settlementNotice.html(
            'The entire provisional order is being cancelled. An amount of <strong>₹' + totalReceived.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong> has been received against this order. Choose how this received amount should be settled:'
          );
        } else {
          // Partial order item removal: other products EXIST in the same order
          $settlementRadioOptions.show();
          $("#cardSettleAdjust").show();
          $("#cardSettleCreditNote").show();
          $("#cardSettleRefund").show();

          // Default to Adjust with Existing Order if neither CN nor Refund is explicitly selected
          if (!$("#settleCreditNote").is(":checked") && !$("#settleRefund").is(":checked")) {
            $("#settleAdjust").prop("checked", true);
          }
          $(".settlement-radio-card").removeClass("active-cn active-refund active-adjust");
          let currentVal = $("input[name='financial_settlement']:checked").val();
          if (currentVal === 'refund') {
            $("#cardSettleRefund").addClass("active-refund");
          } else if (currentVal === 'credit_note') {
            $("#cardSettleCreditNote").addClass("active-cn");
          } else {
            $("#cardSettleAdjust").addClass("active-adjust");
          }

          if (restOrderAmount >= totalReceived) {
            $settlementNotice.html(
              '<div class="alert alert-success py-2 px-3 mb-2 small border-success d-flex align-items-center">' +
              '  <i class="fa fa-check-circle text-success fs-5 me-2 flex-shrink-0"></i>' +
              '  <div><strong>No Credit Note or Refund Required:</strong> Rest of order amount (<strong>₹' + restOrderAmount.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong>) is greater than received amount (<strong>₹' + totalReceived.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong>). The advance payment remains applied against active items.</div>' +
              '</div>'
            );
          } else {
            let excessAmount = totalReceived - restOrderAmount;
            $settlementNotice.html(
              '<div class="alert alert-warning py-2 px-3 mb-2 small border-warning d-flex align-items-center">' +
              '  <i class="fa fa-exclamation-triangle text-warning fs-5 me-2 flex-shrink-0"></i>' +
              '  <div>Rest of order amount (<strong>₹' + restOrderAmount.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong>) is less than received amount (<strong>₹' + totalReceived.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong>). Remaining order covers ₹' + restOrderAmount.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ', leaving an excess advance of <strong>₹' + excessAmount.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong>. Choose settlement option:</div>' +
              '</div>'
            );
          }
        }
      }
    }

    // Toggle active border on settlement radio cards
    $(document).on("change", "input[name='financial_settlement']", function () {
      $(".settlement-radio-card").removeClass("active-cn active-refund active-adjust");
      let val = $(this).val();
      if (val === 'refund') {
        $(this).closest(".settlement-radio-card").addClass("active-refund");
      } else if (val === 'credit_note') {
        $(this).closest(".settlement-radio-card").addClass("active-cn");
      } else {
        $(this).closest(".settlement-radio-card").addClass("active-adjust");
      }
    });

    // Handle Check All / Uncheck All Cancel Items
    $(document).on("change", "#checkAllCancelItems", function () {
      let isChecked = $(this).is(":checked");
      $(".cancel-item-chk:not(:disabled)").prop("checked", isChecked);
      updateUnifiedCancelButton();
    });

    // Handle Individual Item Checkbox Change
    $(document).on("change", ".cancel-item-chk", function () {
      let total = $(".cancel-item-chk:not(:disabled)").length;
      let checked = $(".cancel-item-chk:not(:disabled):checked").length;
      $("#checkAllCancelItems").prop("checked", total > 0 && total === checked);
      updateUnifiedCancelButton();
    });

    // Helper: Execute Whole Order Cancellation via AJAX
    function executeCancelWholeOrder(psono, remark, projectid, btn) {
      let financial_action = $("input[name='financial_settlement']:checked").val() || 'credit_note';
      btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin me-1"></i> Cancelling Order...');
      $.ajax({
        url: "modal/product_modal.php?action=CancelWholeProvisionalOrder",
        type: "POST",
        dataType: "json",
        data: {
          psono: psono,
          remark: remark,
          projectid: projectid,
          financial_action: financial_action
        },
        success: function (res) {
          if (res.status === "success") {
            if (typeof Swal !== "undefined") {
              Swal.fire({
                icon: "success",
                title: "Order Cancelled",
                text: res.message || "Provisional order has been cancelled successfully.",
                confirmButtonColor: "#10b981"
              }).then(function () {
                $("#cancelOrderModal").modal("hide");
                window.location.reload();
              });
            } else {
              alert(res.message);
              window.location.reload();
            }
          } else {
            updateUnifiedCancelButton();
            if (typeof Swal !== "undefined") {
              Swal.fire({
                icon: "error",
                title: "Error",
                text: res.message || "Failed to cancel provisional order.",
                confirmButtonColor: "#ef4444"
              });
            } else {
              alert("Error: " + (res.message || "Failed to cancel provisional order."));
            }
          }
        },
        error: function () {
          updateUnifiedCancelButton();
          if (typeof Swal !== "undefined") {
            Swal.fire({
              icon: "error",
              title: "Network Error",
              text: "Could not reach server to cancel order.",
              confirmButtonColor: "#ef4444"
            });
          } else {
            alert("Network error. Please try again.");
          }
        }
      });
    }

    // Helper: Execute Selected Items Cancellation via AJAX
    function executeCancelSelectedItems(psono, selectedIds, remark, projectid, btn, finAction) {
      let financial_action = finAction || 'none';
      btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin me-1"></i> Cancelling Items...');
      $.ajax({
        url: "modal/product_modal.php?action=CancelProvisionalOrderItems",
        type: "POST",
        dataType: "json",
        data: {
          psono: psono,
          item_ids: selectedIds,
          remark: remark,
          projectid: projectid,
          financial_action: financial_action
        },
        success: function (res) {
          if (res.status === "success") {
            if (res.all_cancelled) {
              if (typeof Swal !== "undefined") {
                Swal.fire({
                  icon: "success",
                  title: "Order Cancelled",
                  text: "All items have been cancelled. The entire provisional order is now marked as Cancelled.",
                  confirmButtonColor: "#10b981"
                }).then(function () {
                  $("#cancelOrderModal").modal("hide");
                  window.location.reload();
                });
              } else {
                alert("All items cancelled. Order is now Cancelled.");
                window.location.reload();
              }
            } else {
              if (typeof Swal !== "undefined") {
                Swal.fire({
                  icon: "success",
                  title: "Item(s) Cancelled",
                  text: res.message || (selectedIds.length + " item(s) cancelled successfully."),
                  confirmButtonColor: "#10b981"
                }).then(function () {
                  $("#cancelOrderModal").modal("hide");
                  window.location.reload();
                });
              } else {
                alert(res.message || (selectedIds.length + " item(s) cancelled successfully."));
                window.location.reload();
              }
            }
          } else {
            updateUnifiedCancelButton();
            if (typeof Swal !== "undefined") {
              Swal.fire({
                icon: "error",
                title: "Error",
                text: res.message || "Failed to cancel selected items.",
                confirmButtonColor: "#ef4444"
              });
            } else {
              alert("Error: " + (res.message || "Failed to cancel selected items."));
            }
          }
        },
        error: function () {
          updateUnifiedCancelButton();
          if (typeof Swal !== "undefined") {
            Swal.fire({
              icon: "error",
              title: "Network Error",
              text: "Could not reach server to cancel items.",
              confirmButtonColor: "#ef4444"
            });
          } else {
            alert("Network error. Please try again.");
          }
        }
      });
    }

    // Unified Cancel Button: Single button manages both selected items and whole order
    $(document).on("click", "#btnCancelOrderSubmit", function (e) {
      e.preventDefault();
      let btn = $(this);
      let psono = btn.data("psono");
      let projectid = $("body").data("projectid") || 0;
      let remark = ($("#cancellationRemarkInput").val() || "").trim();

      if (!remark) {
        $("#cancellationRemarkInput").focus().css("border-color", "#ef4444");
        $("#cancellationRemarkError").show();
        if (typeof Swal !== "undefined") {
          Swal.fire({
            icon: "warning",
            title: "Cancellation Remark Required",
            text: "Please enter a cancellation remark before proceeding.",
            confirmButtonColor: "#ef4444"
          });
        } else {
          alert("Please enter a cancellation remark before proceeding.");
        }
        return;
      }

      $("#cancellationRemarkError").hide();
      $("#cancellationRemarkInput").css("border-color", "#fca5a5");

      let total = $(".cancel-item-chk:not(:disabled)").length;
      let selectedBoxes = $(".cancel-item-chk:not(:disabled):checked");
      let checkedCount = selectedBoxes.length;

      let selectedIds = [];
      let selectedNames = [];
      selectedBoxes.each(function () {
        selectedIds.push($(this).val());
        let prod = $(this).data("product");
        if (prod) selectedNames.push(prod);
      });

      let totalOrderAmount = parseFloat($("#advanceSettlementSection").data("total-order-amount") || 0);
      let totalReceived = parseFloat($("#advanceSettlementSection").data("total-received") || 0);
      let selectedCancelAmount = 0;
      selectedBoxes.each(function () {
        selectedCancelAmount += parseFloat($(this).data("amount") || 0);
      });
      let restOrderAmount = Math.max(0, totalOrderAmount - selectedCancelAmount);

      let totalActiveInOrder = parseInt($("#cancelOrderMeta").data("active-items") || $("#advanceSettlementSection").data("active-items") || btn.data("active-items") || total);
      let lockedItemsCount = parseInt($("#cancelOrderMeta").data("locked-items") || $("#advanceSettlementSection").data("locked-items") || btn.data("locked-items") || 0);
      let isWholeCancel = (lockedItemsCount === 0 && checkedCount > 0 && checkedCount === totalActiveInOrder);
      let isPartialWithExcess = (!isWholeCancel && totalReceived > 0 && restOrderAmount < totalReceived);
      let needsSettlement = (isWholeCancel && totalReceived > 0) || isPartialWithExcess;

      let financial_action = isWholeCancel ? 'credit_note' : 'adjust_order';
      let settlementHtml = '';
      if ($("input[name='financial_settlement']").length > 0 && totalReceived > 0) {
        financial_action = $("input[name='financial_settlement']:checked").val() || (isWholeCancel ? 'credit_note' : 'adjust_order');
        if (financial_action === 'refund') {
          settlementHtml = '<div class="alert alert-danger py-1.5 px-2 mt-2 mb-0 small text-start"><strong>Financial Settlement:</strong> <i class="fa fa-undo-alt text-danger ms-1"></i> Return Cash / Refund Amount to Customer</div>';
        } else if (financial_action === 'credit_note') {
          settlementHtml = '<div class="alert alert-info py-1.5 px-2 mt-2 mb-0 small text-start"><strong>Financial Settlement:</strong> <i class="fa fa-file-invoice text-primary ms-1"></i> Issue Credit Note (Credited to Client Ledger)</div>';
        } else {
          settlementHtml = '<div class="alert alert-success py-1.5 px-2 mt-2 mb-0 small text-start"><strong>Financial Settlement:</strong> <i class="fa fa-sync-alt text-success ms-1"></i> Adjust with Existing Order (Keep on remaining products; no Credit Note or Refund)</div>';
        }
      }

      // Case 1: 0 items checked -> prompt to confirm whole order cancellation or select items
      if (checkedCount === 0) {
        if (lockedItemsCount > 0) {
          if (typeof Swal !== "undefined") {
            Swal.fire({
              icon: "warning",
              title: "Select Item to Cancel",
              text: "Please select the item(s) you wish to cancel. The whole order cannot be cancelled because sowing has already started for other items.",
              confirmButtonColor: "#f59e0b"
            });
          } else {
            alert("Please select the item(s) you wish to cancel. The whole order cannot be cancelled because sowing has already started for other items.");
          }
          return;
        }

        if (typeof Swal !== "undefined") {
          Swal.fire({
            title: "Cancel Entire Provisional Order?",
            html: "No individual items are selected. Do you want to cancel the <strong>entire provisional order (" + psono + ")</strong>?<br>" + settlementHtml + "<div class='text-muted small mt-2 text-start'><strong>Remark:</strong> " + $("<div>").text(remark).html() + "</div>",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#ef4444",
            cancelButtonColor: "#64748b",
            confirmButtonText: "Yes, Cancel Entire Order",
            cancelButtonText: "No, Let Me Select Items"
          }).then(function (result) {
            if (result.isConfirmed) {
              executeCancelWholeOrder(psono, remark, projectid, btn);
            }
          });
        } else {
          if (confirm("No items selected. Cancel entire provisional order (" + psono + ")?\nRemark: " + remark)) {
            executeCancelWholeOrder(psono, remark, projectid, btn);
          }
        }
        return;
      }

      // Case 2: ALL items checked AND whole order can be cancelled
      if (isWholeCancel) {
        if (typeof Swal !== "undefined") {
          Swal.fire({
            title: "Cancel Whole Provisional Order?",
            html: "All " + totalActiveInOrder + " items are selected.<br><br>Are you sure you want to cancel the <strong>entire provisional order (" + psono + ")</strong>?<br>" + settlementHtml + "<div class='text-muted small mt-2 text-start'><strong>Remark:</strong> " + $("<div>").text(remark).html() + "</div>",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#ef4444",
            cancelButtonColor: "#64748b",
            confirmButtonText: "Yes, Cancel Whole Order",
            cancelButtonText: "No, Keep Order"
          }).then(function (result) {
            if (result.isConfirmed) {
              executeCancelWholeOrder(psono, remark, projectid, btn);
            }
          });
        } else {
          if (confirm("Cancel whole provisional order (" + psono + ")?\nRemark: " + remark)) {
            executeCancelWholeOrder(psono, remark, projectid, btn);
          }
        }
        return;
      }

      // Case 3: Partial items checked -> confirm selected items cancellation
      let itemsSummaryHtml = selectedNames.length > 0 
        ? "<ul class='text-start small text-muted ps-4 mb-2'>" + selectedNames.slice(0, 5).map(function(n) { return "<li>" + $("<div>").text(n).html() + "</li>"; }).join("") + (selectedNames.length > 5 ? "<li>...and " + (selectedNames.length - 5) + " more</li>" : "") + "</ul>" 
        : "";

      if (typeof Swal !== "undefined") {
        Swal.fire({
          title: "Cancel " + checkedCount + " Selected Item(s)?",
          html: itemsSummaryHtml + settlementHtml + "<div class='text-muted small mt-2'><strong>Remark:</strong> " + $("<div>").text(remark).html() + "</div>",
          icon: "warning",
          showCancelButton: true,
          confirmButtonColor: "#ef4444",
          cancelButtonColor: "#64748b",
          confirmButtonText: "Yes, Cancel Selected",
          cancelButtonText: "No, Keep Them"
        }).then(function (result) {
          if (result.isConfirmed) {
            executeCancelSelectedItems(psono, selectedIds, remark, projectid, btn, financial_action);
          }
        });
      } else {
        if (confirm("Cancel " + checkedCount + " selected item(s)?\nRemark: " + remark)) {
          executeCancelSelectedItems(psono, selectedIds, remark, projectid, btn, financial_action);
        }
      }
    });

        // Function to load the Undo / Restore Rejected Order Modal
    window.loadUndoOrderModal = function (psono, targetItemId) {
      if (!psono) return;
      let companyid = $("body").data("companyid") || 0;
      let subcompanyid = $("body").data("subcompanyid") || 0;
      let projectid = $("body").data("projectid") || 0;

      $("#undoOrderContent").html(
        '<div class="text-center py-5">' +
        '  <div class="spinner-border text-warning" role="status"></div>' +
        '  <p class="mt-2 text-muted fw-bold">Loading rejected order details for undo...</p>' +
        '</div>'
      );
      $("#undoOrderModal").modal("show");

      $.ajax({
        url: "modal/fetch_order_undo_view.php",
        type: "POST",
        data: {
          psono: psono,
          target_item_id: targetItemId || 0,
          companyid: companyid,
          subcompanyid: subcompanyid,
          projectid: projectid
        },
        success: function (html) {
          $("#undoOrderContent").html(html);
          updateUndoSubmitButton();
        },
        error: function () {
          $("#undoOrderContent").html(
            '<div class="alert alert-danger m-3">' +
            '  <i class="fa fa-exclamation-triangle me-2"></i> Failed to load rejected order details. Please try again.' +
            '</div>'
          );
        }
      });
    };

    // Open Undo Order Modal on Undo Button click
    $(document).on("click", ".undoOrderBtn", function (e) {
      e.preventDefault();
      let psono = $(this).data("psono");
      let itemId = $(this).data("item-id");
      if (!psono) return;
      loadUndoOrderModal(psono, itemId);
    });

    // Update Undo button state & text based on checkboxes
    function updateUndoSubmitButton() {
      let total = $(".undo-item-chk").length;
      let checked = $(".undo-item-chk:checked").length;
      let $btn = $(".btnUndoOrderSubmitAction, #btnUndoOrderSubmit");
      let $count = $(".undoBtnCountText");
      let $label = $(".undoBtnLabel");

      if (checked === 0) {
        $btn.prop("disabled", true).addClass("opacity-50");
        $label.text("Select Item(s) to Undo (0)");
        $count.text("0");
      } else {
        $btn.prop("disabled", false).removeClass("opacity-50");
        $label.html("Undo Rejection & Restore (<span class='undoBtnCountText'>" + checked + "</span>)");
        $count.text(checked);
      }
    }

    // Check All / Uncheck All Undo Items
    $(document).on("change", "#checkAllUndoItems", function () {
      let isChecked = $(this).is(":checked");
      $(".undo-item-chk").prop("checked", isChecked);
      updateUndoSubmitButton();
    });

    // Individual item checkbox change
    $(document).on("change", ".undo-item-chk", function () {
      let total = $(".undo-item-chk").length;
      let checked = $(".undo-item-chk:checked").length;
      $("#checkAllUndoItems").prop("checked", total > 0 && total === checked);
      updateUndoSubmitButton();
    });

    // Submit Undo Rejection Action (Handles both Mobile Top button and Bottom Footer button)
    $(document).on("click", ".btnUndoOrderSubmitAction, #btnUndoOrderSubmit", function (e) {
      e.preventDefault();
      let btn = $(this);
      let psono = btn.data("psono") || $("#btnUndoOrderSubmit").data("psono");
      let projectid = $("body").data("projectid") || 0;
      let remark = ($("#undoRemarkInput").val() || "").trim();

      if (!remark) {
        $("#undoRemarkInput").focus().css("border-color", "#ef4444");
        $("#undoRemarkError").show();
        if ($("#undoRemarkInput").length) {
          $("#undoRemarkInput")[0].scrollIntoView({ behavior: "smooth", block: "center" });
        }
        if (typeof Swal !== "undefined") {
          Swal.fire({
            icon: "warning",
            title: "Undo Reason Required",
            text: "Please enter a reason for undoing the rejection before proceeding.",
            confirmButtonColor: "#f59e0b"
          });
        } else {
          alert("Please enter a reason for undoing the rejection before proceeding.");
        }
        return;
      }

      $("#undoRemarkError").hide();
      $("#undoRemarkInput").css("border-color", "#fde047");

      let selectedBoxes = $(".undo-item-chk:checked");
      let checkedCount = selectedBoxes.length;

      if (checkedCount === 0) {
        if (typeof Swal !== "undefined") {
          Swal.fire({
            icon: "warning",
            title: "No Items Selected",
            text: "Please select at least one cancelled item to undo.",
            confirmButtonColor: "#f59e0b"
          });
        } else {
          alert("Please select at least one cancelled item to undo.");
        }
        return;
      }

      let selectedIds = [];
      let selectedNames = [];
      selectedBoxes.each(function () {
        selectedIds.push($(this).val());
        let prod = $(this).data("product");
        if (prod) selectedNames.push(prod);
      });

      let promptHtml = "Are you sure you want to undo rejection for <strong>" + checkedCount + " item(s)</strong> of Provisional Order <strong>" + psono + "</strong>?<br><br>" +
        "<div class='text-muted small text-start'><strong>Reason:</strong> " + $("<div>").text(remark).html() + "</div>";

      let executeUndo = function() {
        $(".btnUndoOrderSubmitAction, #btnUndoOrderSubmit").prop("disabled", true).html('<i class="fa fa-spinner fa-spin me-1"></i> Restoring Order...');
        $.ajax({
          url: "modal/product_modal.php?action=UndoProvisionalOrder",
          type: "POST",
          dataType: "json",
          data: {
            psono: psono,
            item_ids: selectedIds,
            remark: remark,
            projectid: projectid
          },
          success: function (res) {
            if (res.status === "success") {
              if (typeof Swal !== "undefined") {
                Swal.fire({
                  icon: "success",
                  title: "Order Restored",
                  text: res.message || "Provisional order rejection has been undone successfully.",
                  confirmButtonColor: "#10b981"
                }).then(function () {
                  $("#undoOrderModal").modal("hide");
                  window.location.reload();
                });
              } else {
                alert(res.message);
                window.location.reload();
              }
            } else {
              $(".btnUndoOrderSubmitAction, #btnUndoOrderSubmit").prop("disabled", false).html('<i class="fa fa-undo me-1"></i> Undo Rejection & Restore (' + checkedCount + ')');
              if (typeof Swal !== "undefined") {
                Swal.fire({
                  icon: "error",
                  title: "Error",
                  text: res.message || "Failed to restore provisional order.",
                  confirmButtonColor: "#ef4444"
                });
              } else {
                alert("Error: " + (res.message || "Failed to restore provisional order."));
              }
            }
          },
          error: function () {
            $(".btnUndoOrderSubmitAction, #btnUndoOrderSubmit").prop("disabled", false).html('<i class="fa fa-undo me-1"></i> Undo Rejection & Restore (' + checkedCount + ')');
            if (typeof Swal !== "undefined") {
              Swal.fire({
                icon: "error",
                title: "Network Error",
                text: "Could not reach server to restore order.",
                confirmButtonColor: "#ef4444"
              });
            } else {
              alert("Network error. Please try again.");
            }
          }
        });
      };

      if (typeof Swal !== "undefined") {
        Swal.fire({
          title: "Undo Rejection?",
          html: promptHtml,
          icon: "question",
          showCancelButton: true,
          confirmButtonColor: "#f59e0b",
          cancelButtonColor: "#64748b",
          confirmButtonText: "Yes, Undo Rejection",
          cancelButtonText: "Cancel"
        }).then(function (result) {
          if (result.isConfirmed) {
            executeUndo();
          }
        });
      } else {
        if (confirm("Undo rejection for " + checkedCount + " item(s) of order " + psono + "?\nReason: " + remark)) {
          executeUndo();
        }
      }
    });
    // Open Payment Advice / Update Payment in Full-Page Modal on clicking "Pending for Payment"
    let pamProgressTimer = null;
    $(document).on("click", ".open-payment-modal-btn", function (e) {
      e.preventDefault();
      let url = $(this).data("url");
      let psono = $(this).data("psono") || "";
      let cname = $(this).data("cname") || "";

      if (!url) return;

      $("#paymentAdviceOrderBadge").text(psono);
      $("#paymentAdviceClientBadge").text(cname ? "(" + cname + ")" : "");
      
      // Show slim top loading bar
      $("#paymentAdviceProgressBar").show();

      if (pamProgressTimer) clearTimeout(pamProgressTimer);
      // Auto-hide progress bar after 800ms
      pamProgressTimer = setTimeout(function () {
        $("#paymentAdviceProgressBar").fadeOut(150);
      }, 800);

      // Attach load handler BEFORE setting src
      $("#paymentAdviceIframe").off("load").on("load", function () {
        if (pamProgressTimer) clearTimeout(pamProgressTimer);
        $("#paymentAdviceProgressBar").fadeOut(150);
      });

      let finalUrl = url + (url.indexOf("?") === -1 ? "?" : "&") + "_v=" + Date.now();
      $("#paymentAdviceIframe").attr("src", finalUrl);
      $("#paymentAdviceFullModal").modal("show");
    });

    // Callback when payment is saved inside paymentadvicemanual.php iframe
    window.onPaymentAdviceModalSaved = function (res) {
      $("#paymentAdviceFullModal").modal("hide");
      if (typeof Swal !== "undefined") {
        Swal.fire({
          icon: "success",
          title: "Payment Recorded!",
          text: (res && res.message) ? res.message : "Payment advice recorded and provisional order updated.",
          timer: 2000,
          showConfirmButton: false
        }).then(function () {
          window.location.reload();
        });
      } else {
        alert((res && res.message) ? res.message : "Payment advice recorded successfully.");
        window.location.reload();
      }
    };

    // Reload Payment Iframe
    $(document).on("click", "#btnReloadPaymentIframe", function (e) {
      e.preventDefault();
      let iframe = document.getElementById("paymentAdviceIframe");
      if (iframe && iframe.contentWindow) {
        $("#paymentAdviceProgressBar").show();
        if (pamProgressTimer) clearTimeout(pamProgressTimer);
        pamProgressTimer = setTimeout(function () {
          $("#paymentAdviceProgressBar").fadeOut(150);
        }, 800);
        iframe.contentWindow.location.reload();
      }
    });

    // When payment modal is hidden, reset iframe src
    $("#paymentAdviceFullModal").on("hidden.bs.modal", function () {
      if (pamProgressTimer) clearTimeout(pamProgressTimer);
      $("#paymentAdviceProgressBar").hide();
      $("#paymentAdviceIframe").attr("src", "");
    });
  });

  // Export Provisional Sales Orders Data to Excel / CSV
  function exportProvisionalOrders(type) {
    let rows = [];
    let headers = [
      "S.No", 
      "Order Date", 
      "Order No", 
      "Client Name", 
      "Contact No", 
      "Created By", 
      "Product Name", 
      "Quantity", 
      "Exp. Delivery Date", 
      "Amount", 
      "Order Amount", 
      "Received Amount", 
      "Balance Amount", 
      "Status",
      "Production Stock Order No",
      "Sowing Date",
      "Stock Allocation Type"
    ];
    rows.push(headers.map(h => `"${h}"`).join(","));

    let $visibleStrips = $(".order-strip:visible");
    if ($visibleStrips.length === 0) {
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: "warning",
          title: "No Data Available",
          text: "There are no provisional order records matching your search/filter criteria."
        });
      } else {
        alert("There are no provisional order records matching your search/filter criteria.");
      }
      return;
    }

    $visibleStrips.each(function (index) {
      let $strip = $(this);
      let orderNo = String($strip.find(".order-no-pill").text() || "").trim();
      let orderDate = String($strip.attr("data-order-date") || $strip.find(".strip-item[data-order-date]").data("order-date") || "").trim();
      let clientName = String($strip.find(".strip-item[data-client]").data("client") || "").trim();
      let contactNo = String($strip.attr("data-mobile") ?? $strip.data("mobile") ?? "").trim();
      let createdBy = String($strip.find(".strip-item[data-login]").data("login") || "").trim();
      let productName = String($strip.find(".strip-item:eq(3) .fw-bold").text() || "").trim();
      let qty = String($strip.find(".strip-item:eq(3) .qty-value, .strip-item:eq(3) .badge").first().text() || "").trim().replace(/,/g, "");
      let expDelivery = String($strip.find(".strip-item:eq(4) .strip-content").text() || "").trim();
      let itemAmount = String($strip.find(".amount-display").text() || "").trim().replace(/[₹,]/g, "");
      let orderAmount = String($strip.find(".amount-box-mini:eq(0) strong").text() || "").trim().replace(/[₹,]/g, "");
      let receivedAmount = String($strip.find(".amount-box-mini:eq(1) strong").text() || "").trim().replace(/[₹,]/g, "");
      let balanceAmount = String($strip.find(".amount-box-mini:eq(2) strong").text() || "").trim().replace(/[₹,]/g, "");
      let status = String($strip.data("status") || $strip.find(".status-badge").text() || "").trim();

      let prodStockNo = String($strip.attr("data-prod-stockno") || "").trim();
      let sowingDate = String($strip.find(".confirmed-prod-card .fa-calendar-check").parent().find("strong").text() || "").trim();
      let allocType = String($strip.find(".confirmed-prod-card .badge-self-production, .confirmed-prod-card .badge-open-stock").text() || "").trim();

      let row = [
        index + 1,
        `"${orderDate.replace(/"/g, '""')}"`,
        `"${orderNo.replace(/"/g, '""')}"`,
        `"${clientName.replace(/"/g, '""')}"`,
        `"${contactNo.replace(/"/g, '""')}"`,
        `"${createdBy.replace(/"/g, '""')}"`,
        `"${productName.replace(/"/g, '""')}"`,
        qty,
        `"${expDelivery.replace(/"/g, '""')}"`,
        itemAmount,
        orderAmount,
        receivedAmount,
        balanceAmount,
        `"${status.replace(/"/g, '""')}"`,
        `"${prodStockNo.replace(/"/g, '""')}"`,
        `"${sowingDate.replace(/"/g, '""')}"`,
        `"${allocType.replace(/"/g, '""')}"`
      ];
      rows.push(row.join(","));
    });

    let csvContent = "\uFEFF" + rows.join("\r\n");
    let blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
    let url = URL.createObjectURL(blob);
    let link = document.createElement("a");
    let todayStr = new Date().toISOString().slice(0, 10);
    link.setAttribute("href", url);
    link.setAttribute("download", `Provisional_Sales_Orders_${todayStr}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    if (typeof Swal !== 'undefined') {
      Swal.fire({
        icon: "success",
        title: "Export Downloaded",
        text: `Exported ${$visibleStrips.length} provisional order record(s) successfully!`,
        timer: 2000,
        showConfirmButton: false
      });
    }
  }

  // Live 48-Hour Countdown Timer for Draft Orders
  (function initDraftCountdowns() {
    function updateCountdowns() {
      var now = Math.floor(Date.now() / 1000);
      var pills = document.querySelectorAll('.draft-countdown-pill');
      var expiredPsos = [];

      pills.forEach(function (pill) {
        var expiresAt = parseInt(pill.getAttribute('data-expires-at'), 10);
        var psono = pill.getAttribute('data-psono');
        var clockTextEl = pill.querySelector('.timer-clock-text');
        var iconEl = pill.querySelector('i');
        if (!expiresAt || !clockTextEl) return;

        var diff = expiresAt - now;

        if (diff <= 0) {
          // Expired: auto cancelled
          clockTextEl.textContent = 'Auto Canceled (48h Expired)';
          pill.className = 'draft-countdown-pill badge bg-danger text-white shadow-xs';
          if (iconEl) iconEl.className = 'fa fa-times-circle me-1';
          if (pill.getAttribute('data-auto-cancelled') !== '1') {
            pill.setAttribute('data-auto-cancelled', '1');
            expiredPsos.push(psono);
          }
        } else {
          // Safety cap: Countdown timer can never exceed 48 hours (48 * 3600 = 172800s)
          if (diff > 172800) {
            diff = 172800;
          }

          // Calculate hours, minutes, seconds remaining
          var hours = Math.floor(diff / 3600);
          var minutes = Math.floor((diff % 3600) / 60);
          var seconds = diff % 60;
          
          var formattedTime = (hours < 10 ? '0' : '') + hours + 'h ' + 
                              (minutes < 10 ? '0' : '') + minutes + 'm ' + 
                              (seconds < 10 ? '0' : '') + seconds + 's';
          clockTextEl.textContent = formattedTime + ' left';

          if (hours >= 12) {
            // Normal (>12h remaining)
            pill.className = 'draft-countdown-pill badge bg-light text-primary border shadow-xs';
            if (iconEl) iconEl.className = 'fa fa-stopwatch me-1 text-primary';
          } else if (hours >= 2) {
            // Warning (2h - 12h remaining)
            pill.className = 'draft-countdown-pill badge bg-warning text-dark border shadow-xs';
            if (iconEl) iconEl.className = 'fa fa-exclamation-triangle me-1 text-dark';
          } else {
            // Urgent (<2h remaining)
            pill.className = 'draft-countdown-pill badge bg-danger text-white shadow-xs';
            if (iconEl) iconEl.className = 'fa fa-fire me-1 text-white';
          }
        }
      });

      // If any order reached 0 on client side while viewing, trigger auto-cancel
      if (expiredPsos.length > 0) {
        $.ajax({
          url: 'modal/product_modal.php?action=AutoCancelExpiredDraftOrders',
          type: 'POST',
          dataType: 'json',
          data: { psono: expiredPsos[0] },
          success: function (res) {
            if (res && res.status === 'success') {
              console.log('Auto-cancelled expired draft orders:', res);
            }
          }
        });
      }
    }

    // Tick immediately and then every second
    updateCountdowns();
    setInterval(updateCountdowns, 1000);
  })();
</script>
