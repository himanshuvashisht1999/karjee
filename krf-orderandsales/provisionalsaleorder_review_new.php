<?php
// Export requests must not emit dashboard HTML before download headers.
$krfPsoExportRequest = isset($_GET['pso_export']);
if ($krfPsoExportRequest) ob_start();
include('../header.php');
include('functions/orders_functions.php');
include('functions/client_functions.php');

  
include_once('functions/orders_dashboard_paged.php');
$dashboardFilters = [
 'search' => trim((string)($_GET['pso_search'] ?? '')),
 'order_date' => (array)($_GET['pso_order_date'] ?? []),
 'delivery_date' => (array)($_GET['pso_delivery_date'] ?? []),
 'client' => (array)($_GET['pso_client'] ?? []),
 'status' => (array)($_GET['pso_status'] ?? []),
 'created_by' => (array)($_GET['pso_created_by'] ?? [])
];
// Export uses the same authenticated page context, filters and SQL reader as the dashboard.
// Each batch contains up to 250 complete PSOs; no browser DOM scraping.
if ($krfPsoExportRequest) {
    $mode = (string)($_GET['pso_export'] ?? '');
    $scope = (string)($_GET['pso_scope'] ?? 'all');
    if (!in_array($mode, ['csv', 'print'], true) || !in_array($scope, ['all', 'page'], true)) {
        http_response_code(400); exit('Invalid export option');
    }
    $pageRequested = max(1, (int)($_GET['pso_page'] ?? 1));
    $first = getPendingProvisionalSalesOrdersPage250($krfconn, $authconn, $companyid, $subcompanyid, $projectid, $dashboardFilters, $scope === 'page' ? $pageRequested : 1, 250);
    if (!empty($first['error'])) { http_response_code(500); exit('Unable to retrieve export records. Check PHP error log.'); }
    // Flush the header/navigation output from ../header.php before producing the file.
    while (ob_get_level() > 0) ob_end_clean();
    $filename = 'KRF_Provisional_Orders_' . date('Y-m-d') . ($scope === 'page' ? '_Page_' . $first['page'] : '_All');
    $columns = ['S.No', 'Order Date', 'PSO No', 'Client Name', 'Mobile', 'Created By', 'Product', 'Quantity', 'Expected Delivery', 'Item Amount', 'Order Amount', 'Received Amount', 'Balance Amount', 'Status'];
    $safeCell = static function ($value) {
        $v = (string)($value ?? '');
        // Prevent spreadsheet formula injection from user-controlled text.
        if (preg_match('/^[\s]*[=+@-]/u', $v) && !is_numeric(trim($v))) $v = "'" . $v;
        return $v;
    };
    $number = 0;
    $writeBatch = static function ($rows, $write) use (&$number) {
        foreach ($rows as $r) {
            $number++;
            $write([
                $number, substr((string)($r['created_at'] ?? ''), 0, 10), $r['psono'] ?? '',
                $r['clientname'] ?? '', $r['mobileno'] ?? '', $r['loginname'] ?? '',
                $r['product_name'] ?? '', $r['quantity'] ?? '', $r['expecteddeliverydate'] ?? '',
                $r['item_amount'] ?? 0, $r['orderamount'] ?? 0, $r['advanceamt'] ?? 0,
                $r['balanceamt'] ?? 0, $r['status'] ?? ''
            ]);
        }
    };
    if ($mode === 'csv') {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        header('Cache-Control: no-store');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $columns);
        $emit = static function ($cells) use ($out, $safeCell) { fputcsv($out, array_map($safeCell, $cells)); };
    } else {
        header('Content-Type: text/html; charset=UTF-8');
        header('Cache-Control: no-store');
        echo '<!doctype html><html><head><meta charset="utf-8"><title>' . htmlspecialchars($filename, ENT_QUOTES, 'UTF-8') . '</title>';
        echo '<style>body{font:12px Arial,sans-serif;margin:20px;color:#172b45}h2{margin-bottom:6px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #ccd5e1;padding:5px;text-align:left;vertical-align:top}th{background:#e9f0f8}tr{break-inside:avoid}@page{size:landscape;margin:12mm}@media print{thead{display:table-header-group}}</style></head><body>';
        echo '<h2>Karjee Farms — Provisional Sales Orders</h2><p>Generated: ' . date('d-m-Y H:i') . ' · ' . ($scope === 'page' ? 'Current page' : 'All matching orders') . '</p>';
        echo '<table><thead><tr>';
        foreach ($columns as $column) echo '<th>' . htmlspecialchars($column, ENT_QUOTES, 'UTF-8') . '</th>';
        echo '</tr></thead><tbody>';
        $emit = static function ($cells) { echo '<tr>'; foreach ($cells as $cell) echo '<td>' . htmlspecialchars((string)($cell ?? ''), ENT_QUOTES, 'UTF-8') . '</td>'; echo '</tr>'; };
    }
    $writeBatch($first['rows'], $emit);
    if ($scope === 'all') {
        $pages = (int)$first['pages'];
        for ($pg = 2; $pg <= $pages; $pg++) {
            $batch = getPendingProvisionalSalesOrdersPage250($krfconn, $authconn, $companyid, $subcompanyid, $projectid, $dashboardFilters, $pg, 250);
            if (!empty($batch['error'])) { error_log('KRF export stopped at page ' . $pg); break; }
            $writeBatch($batch['rows'], $emit);
            if ($mode === 'csv') fflush($out);
        }
    }
    if ($mode === 'csv') fclose($out); else echo '</tbody></table></body></html>';
    exit;
}
$dashboardPage = getPendingProvisionalSalesOrdersPage250($krfconn,$authconn,$companyid,$subcompanyid,$projectid,$dashboardFilters,(int)($_GET['pso_page']??1),250);
$psnolistDetailed = $dashboardPage['rows'];
$dashboardSelected = static fn($key,$value) => in_array((string)$value,array_map('strval',(array)($dashboardFilters[$key]??[])),true) ? ' selected' : '';
$dashboardUrl = static function($pageNumber) { $query=$_GET; $query['pso_page']=$pageNumber; return '?'.http_build_query($query); };
/*
 * Build clean filter lists from the orders that are actually displayed.
 * Values are trimmed, duplicate names are removed case-insensitively and
 * text filters are sorted A-Z.
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

$uniqueOrderDates=[]; $uniqueDeliveryDates=[]; $uniqueClientNames=[]; $uniqueLoginNames=[];
$optionWhere="companyid='".$krfconn->real_escape_string((string)$companyid)."' AND subcompanyid='".$krfconn->real_escape_string((string)$subcompanyid)."' AND projectid=".(int)$projectid;
$optionQuery=$krfconn->query("SELECT DISTINCT DATE(created_at) d,clientname,created_by FROM krf_provisional_sales_order WHERE $optionWhere");
$creatorIds=[];
if($optionQuery) while($r=$optionQuery->fetch_assoc()) {
 if(!empty($r['d'])) $uniqueOrderDates[$r['d']]=true;
 if(trim((string)$r['clientname'])!=='') $uniqueClientNames[$r['clientname']]=true;
 if(trim((string)$r['created_by'])!=='') $creatorIds[$r['created_by']]=true;
}
$uniqueOrderDates=array_keys($uniqueOrderDates); rsort($uniqueOrderDates);
$uniqueClientNames=uniqueSortedFilterValues(array_keys($uniqueClientNames));
$creatorNames=[];
if($creatorIds) {
 $ids=array_keys($creatorIds); $ph=implode(',',array_fill(0,count($ids),'?'));
 $stmt=$authconn->prepare("SELECT loginid,loginname FROM login_user_master WHERE loginid IN ($ph)");
 if($stmt) { $stmt->bind_param(str_repeat('s',count($ids)),...$ids); $stmt->execute(); $res=$stmt->get_result(); while($u=$res->fetch_assoc()) $creatorNames[$u['loginid']]=$u['loginname']; $stmt->close(); }
}
$uniqueLoginNames=$creatorNames;
$deliveryQuery=$krfconn->query("SELECT DISTINCT i.expecteddeliverydate d FROM krf_provisional_sales_order_items i INNER JOIN krf_provisional_sales_order o ON o.psono=i.psono WHERE o.companyid='".$krfconn->real_escape_string((string)$companyid)."' AND o.subcompanyid='".$krfconn->real_escape_string((string)$subcompanyid)."' AND o.projectid=".(int)$projectid." AND i.expecteddeliverydate IS NOT NULL AND i.expecteddeliverydate <> '0000-00-00' ORDER BY d DESC");
if($deliveryQuery) while($r=$deliveryQuery->fetch_assoc()) if(!empty($r['d'])) $uniqueDeliveryDates[]=$r['d'];
$filterStatuses = uniqueSortedFilterValues([
    'Confirmed',
    'Emergency Sowing',
    'Pending Approval',
    'Draft',
    'Cancelled'
]);

// Fetch all delivery addresses for the project indexed by psono
$delivLocations = [];
$pagePsonos=array_values(array_unique(array_filter(array_column($psnolistDetailed,'psono'))));
if($pagePsonos) {
 $ph=implode(',',array_fill(0,count($pagePsonos),'?'));
 $delQuery="SELECT psono,sono,clientid,deliveryaddress,dest_state,dest_district,dest_city,dest_pincode,distance,deliveryqty FROM krf_deliveryaddress_location WHERE (tedit IS NULL OR tedit='') AND projectid = ? AND psono IN ($ph)";
 $stmt=$krfconn->prepare($delQuery);
 if($stmt) {
  $args=array_merge([(int)$projectid],$pagePsonos);
  $stmt->bind_param('i'.str_repeat('s',count($pagePsonos)),...$args);
  $stmt->execute(); $delRes=$stmt->get_result();
  while($drow=$delRes->fetch_assoc()) { $p=trim((string)($drow['psono']??'')); if($p!=='') $delivLocations[$p][]=$drow; }
  $stmt->close();
 }
}

$mdid = $_GET['mdid'] ?? "";
$dddr = loginmodulesdetails_frommaster($authconn, $companyid, $subcompanyid, $projectid, "moduleid", $mdid);
$groupid = (count($dddr) > 0) ? $dddr[0]['groupid'] : "";

function getOrderGroupColor($psono) {
    if (empty($psono)) return "#ffffff";
    $hash = md5($psono);
    $r = (hexdec(substr($hash, 0, 2)) % 40) + 215; 
    $g = (hexdec(substr($hash, 2, 2)) % 40) + 215; 
    $b = (hexdec(substr($hash, 4, 2)) % 40) + 215; 
    return "rgb($r, $g, $b)";
}
?>

<style>
  :root {
    --primary-blue: #007bff;
    --text-main: #333333;
    --text-muted: #888888;
    --bg-light: #f4f6f9;
    --card-shadow: 0 2px 10px rgba(0,0,0,0.05);
    --transition: all 0.3s ease;
  }

  /* Disable Browser Autofill background highlight */
  input:-webkit-autofill,
  input:-webkit-autofill:hover, 
  input:-webkit-autofill:focus, 
  input:-webkit-autofill:active,
  select:-webkit-autofill,
  select:-webkit-autofill:hover,
  select:-webkit-autofill:focus {
      -webkit-box-shadow: 0 0 0 1000px #ffffff inset !important;
      -webkit-text-fill-color: #334155 !important;
      transition: background-color 5000s ease-in-out 0s;
  }

  .order-dashboard {
    padding: 2rem;
    background: #f8f9fa;
    min-height: 100vh;
    font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
  }

  /* Main Header Card */
  .main-title-card {
    background: white;
    padding: 1.5rem 2rem;
    border-radius: 12px;
    box-shadow: var(--card-shadow);
    margin-bottom: 1rem;
    border-left: 6px solid var(--primary-blue);
    display: flex;
    justify-content: space-between;
    align-items: center;
    animation: fadeInDown 0.5s ease-out;
  }

  .title-content {
    display: flex;
    align-items: center;
    gap: 1.25rem;
  }


  .title-icon-wrapper {
    background: #e7f1ff;
    color: var(--primary-blue);
    width: 42px;
    height: 42px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    font-size: 1.4rem;
  }

  .title-text h4 {
    margin: 0;
    font-weight: 400;
    color: var(--text-main);
    font-size: 1.8rem;
    letter-spacing: -0.5px;
  }

  .title-text p {
    margin: 4px 0 0;
    color: var(--text-muted);
    font-size: 1.1rem;
    font-weight: 300;
  }

  /* Search & Actions Strip */
  .search-action-strip {
    background: white;
    padding: 1rem 1.5rem;
    border-radius: 12px;
    box-shadow: var(--card-shadow);
    margin-bottom: 2rem;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 1rem;
    animation: fadeInUp 0.5s ease-out;
  }

  .search-wrapper {
    position: relative;
    flex: 1;
    max-width: 400px;
  }

  .search-wrapper i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #a0aec0;
    font-size: 0.9rem;
  }

  .search-control {
    width: 100%;
    padding: 0.6rem 1rem 0.6rem 2.8rem;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    font-size: 0.95rem;
    transition: var(--transition);
    color: #4a5568;
  }

  .search-control:focus {
    border-color: var(--primary-blue);
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
    outline: none;
  }

  .btn-new-order {
    background: var(--primary-blue);
    color: white;
    border: none;
    padding: 0.65rem 1.25rem;
    border-radius: 8px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.6rem;
    transition: var(--transition);
    box-shadow: 0 4px 6px rgba(0, 123, 255, 0.15);
  }

  .btn-new-order:hover {
    background: #0069d9;
    transform: translateY(-1px);
    box-shadow: 0 6px 12px rgba(0, 123, 255, 0.2);
    color: white;
  }

  /* Filter Strip Styles */
  .filter-strip {
    background: white;
    padding: 1.25rem 1.5rem;
    border-radius: 12px;
    box-shadow: var(--card-shadow);
    margin-bottom: 1.5rem;
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
    align-items: flex-end;
    animation: fadeInUp 0.5s ease-out;
    border: 1px solid #edf2f7;
    position: relative;
    z-index: 1020;
  }

  .filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    flex: 1;
    min-width: 180px;
  }

  .filter-label {
    font-size: 0.8rem;
    font-weight: 700;
    color: #4a5568;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .filter-control {
    width: 100%;
    padding: 0.6rem 0.75rem;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    font-size: 0.9rem;
    color: #2d3748;
    transition: var(--transition);
    background-color: #f8fafc;
  }

  .filter-control:focus {
    border-color: var(--primary-blue);
    background-color: white;
    outline: none;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
  }

  .btn-clear-filters {
    padding: 0.6rem 1.25rem;
    border-radius: 8px;
    background: #f7fafc;
    border: 1px solid #e2e8f0;
    color: #718096;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    height: 40px;
  }

  .btn-clear-filters:hover {
    background: #edf2f7;
    color: #2d3748;
  }

  .btn-export {
    padding: 0.6rem 1.25rem;
    border-radius: 8px;
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #065f46;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    height: 40px;
    white-space: nowrap;
  }

  .btn-export:hover {
    background: #d1fae5;
    color: #047857;
    border-color: #6ee7b7;
    transform: translateY(-1px);
    box-shadow: 0 4px 6px rgba(16, 185, 129, 0.15);
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
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.18) !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 10px !important;
    padding: 6px 0 !important;
    background: #ffffff !important;
    margin-top: 4px !important;
  }

  .filter-actions .dropdown-item {
    display: flex !important;
    align-items: center !important;
    padding: 9px 16px !important;
    font-size: 0.88rem !important;
    font-weight: 500 !important;
    color: #334155 !important;
    text-decoration: none !important;
    line-height: 1.4 !important;
    transition: all 0.15s ease !important;
  }

  .filter-actions .dropdown-item i {
    width: 20px !important;
    font-size: 1rem !important;
    margin-right: 10px !important;
    text-align: center !important;
    flex-shrink: 0 !important;
    display: inline-block !important;
  }

  .filter-actions .dropdown-item:hover {
    background-color: #f1f5f9 !important;
    color: #0f172a !important;
  }


  @keyframes fadeInDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
  }

  @keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
  }

  .order-strips-container {
    display: grid !important;
    grid-template-columns: minmax(0, 1fr) !important;
    align-items: start !important;
    align-content: start !important;
    row-gap: 0.5rem !important;
    height: auto !important;
    min-height: 0 !important;
}
.order-strip {
    background: white;
    border-radius: 8px;
    padding: 0.75rem 1.25rem;

    display: grid;
    grid-template-columns:
        40px
        1.5fr
        1.5fr
        2fr
        1fr
        1fr
        1fr
        1.2fr;

    align-items: center;

    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    border: 1px solid #edf2f7;

    position: relative;
    overflow: hidden;
    flex: 0 0 auto !important;
    width: 100%;
    margin: 0 !important;

    transition: none !important;
    transform: none !important;
}
  .order-strip:hover {
    transform: none !important;
    transition: none !important;

    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
    border-color: #cbd5e0;
}
.order-strip[hidden] {
    display: none !important;
}
  .order-strip::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: #cbd5e0; }
  .order-strip.status-confirmed::before { background: #48bb78; }
  .order-strip.status-cancelled::before { background: #f56565; }
  .order-strip.status-pending-approval::before { background: #f59e0b; }
  .order-strip.status-emergency-sowing::before { background: #dc2626; }
  .strip-item { min-width: 0; padding-right: 0.5rem; word-break: break-word; }
  .strip-label { font-size: 0.75rem; text-transform: uppercase; color: #718096; font-weight: 600; margin-bottom: 0.25rem; display: none; }
  .strip-content { font-weight: 500; color: #333; font-size: 0.9rem; }
  .order-no-pill { background: #f7fafc; padding: 0.15rem 0.5rem; border-radius: 9999px; font-family: monospace; font-size: 0.8rem; border: 1px solid #e2e8f0; }
  .status-badge { padding: 0.3rem 0.6rem; border-radius: 6px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: white; }
  .amount-display { color: #2c5282; font-weight: 700; font-size: 1rem; }
  .action-buttons { display: flex; gap: 0.35rem; justify-content: flex-end; }
  .btn-strip { width: 32px; height: 32px; border-radius: 6px; border: none; display: flex; align-items: center; justify-content: center; transition: var(--transition); font-size: 0.8rem; }
  .btn-view { background: #ebf8ff; color: #3182ce; } .btn-view:hover { background: #3182ce; color: white; }
  .btn-edit { background: #faf5ff; color: #805ad5; } .btn-edit:hover { background: #805ad5; color: white; }
  .btn-pdf { background: #fff5f5; color: #e53e3e; } .btn-pdf:hover { background: #e53e3e; color: white; }
  .btn-delete { background: white; border: 1px solid #fed7d7; color: #e53e3e; } .btn-delete:hover { background: #e53e3e; color: white; }

  .strips-header {
    position: sticky !important;
    top: 0;
    z-index: 1000;
    display: grid; grid-template-columns: 40px 1.5fr 1.5fr 2fr 1fr 1fr 1fr 1.2fr;
    padding: 0.75rem 1.25rem; color: #718096; font-size: 0.75rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;
    background: #e9ecef !important; border-radius: 8px; margin-bottom: 0.5rem; border: 1px solid #f1f1f1;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  }

  .strip-delivery-footer {
    grid-column: 1 / -1;
    margin-top: 0.6rem;
    padding-top: 0.55rem;
    border-top: 1px dashed rgba(0, 0, 0, 0.12);
    font-size: 0.84rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.5rem;
  }

  @media (max-width: 992px) {
    .strips-header { display: none; }
    .order-strip { grid-template-columns: 1fr 1fr; gap: 1rem; }
    .strip-label { display: block; }
    .action-buttons { grid-column: span 2; justify-content: center; }
  }
/* Compact floating navigation; does not cover the full dashboard width. */
.pso-sticky-pagination{position:fixed;right:clamp(12px,2vw,28px);bottom:calc(12px + env(safe-area-inset-bottom,0px));left:auto;width:max-content;max-width:calc(100vw - 24px);z-index:1010;background:rgba(255,255,255,.97);border:1px solid #dbe5f2;border-radius:14px;box-shadow:0 8px 28px rgba(29,51,85,.18);padding:7px;display:flex;align-items:center;gap:6px;backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px)}
.pso-sticky-pagination .pso-sticky-actions{display:flex;align-items:center;justify-content:center;gap:6px;min-width:0}
.pso-sticky-pagination .pso-page-link{display:inline-flex;align-items:center;justify-content:center;gap:4px;min-height:36px;min-width:84px;padding:7px 11px;border:1px solid #d4e2f8;border-radius:9px;color:#1265d8;background:#f7faff;font-size:13px;line-height:1.2;font-weight:700;text-decoration:none;white-space:nowrap;transition:background .15s,box-shadow .15s}
.pso-sticky-pagination a.pso-page-link:hover{background:#e9f2ff;text-decoration:none;box-shadow:0 2px 7px rgba(13,110,253,.12)}
.pso-sticky-pagination .pso-page-link.next{background:#0d6efd;border-color:#0d6efd;color:#fff}
.pso-sticky-pagination a.pso-page-link.next:hover{background:#0757d2;color:#fff}
.pso-sticky-pagination .pso-page-link.disabled{border-color:#e8edf3;color:#a0aab8;background:#f5f7fa;cursor:default}
.pso-sticky-pagination .pso-page-info{font-weight:700;color:#33445e;font-size:13px;white-space:nowrap;padding:0 9px;font-variant-numeric:tabular-nums}
.order-dashboard:has(.pso-sticky-pagination){padding-bottom:88px}
@media(max-width:575px){.pso-sticky-pagination{right:8px;bottom:calc(8px + env(safe-area-inset-bottom,0px));padding:5px;border-radius:12px;max-width:calc(100vw - 16px)}.pso-sticky-pagination .pso-sticky-actions{gap:4px}.pso-sticky-pagination .pso-page-link{min-width:60px;min-height:36px;padding:7px 8px;font-size:12px}.pso-sticky-pagination .pso-page-info{font-size:12px;padding:0 5px}.order-dashboard:has(.pso-sticky-pagination){padding-bottom:80px}}

/* PSO pagination: polished desktop/mobile navigation; scoped to dashboard only */
.order-dashboard .pso-top-pagination{display:flex;align-items:center;justify-content:flex-end;gap:12px;flex-wrap:wrap;margin:14px 0 12px;padding:12px 16px;background:#fff;border:1px solid #e5ebf3;border-radius:12px;box-shadow:0 2px 9px rgba(24,53,92,.04)}
.order-dashboard .pso-results-summary{color:#53647a;font-size:13px;line-height:1.4}
.order-dashboard .pso-results-summary strong{font-size:15px;color:#1d3556}
.order-dashboard .pso-results-separator{padding:0 5px;color:#a6b1c1}
.order-dashboard .pso-top-controls{display:flex;align-items:center;justify-content:flex-end;gap:8px;min-width:0}
.order-dashboard .pso-nav-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;min-height:36px;padding:7px 13px;border:1px solid #d8e4f4;border-radius:9px;background:#f7faff;color:#1461bd;text-decoration:none;font-size:13px;font-weight:650;white-space:nowrap;transition:background .15s,border-color .15s}
.order-dashboard a.pso-nav-btn:hover{background:#eaf3ff;color:#084b9a;text-decoration:none;border-color:#bad3f6}
.order-dashboard .pso-nav-btn.is-primary{background:#0d6efd;color:#fff;border-color:#0d6efd}
.order-dashboard a.pso-nav-btn.is-primary:hover{background:#0757d2;color:#fff}
.order-dashboard .pso-nav-btn.is-disabled{background:#f7f9fc;color:#9ca9b9;border-color:#ebeff5;cursor:default}
.order-dashboard .pso-top-page{font-size:13px;color:#586b82;white-space:nowrap;padding:0 6px;font-variant-numeric:tabular-nums}
.order-dashboard .pso-top-page strong{color:#223e63}
.pso-sticky-pagination{box-shadow:0 5px 20px rgba(25,54,88,.14);border-radius:12px}
.pso-sticky-pagination .pso-page-link{min-width:76px}
@media(max-width:680px){
 .order-dashboard .pso-top-pagination{padding:10px 12px;gap:10px}
 .order-dashboard .pso-results-summary{width:100%;font-size:12px}
 .order-dashboard .pso-top-controls{width:100%;justify-content:space-between;gap:5px}
 .order-dashboard .pso-nav-btn{padding:7px 9px;min-height:34px;font-size:12px}
 .order-dashboard .pso-top-page{padding:0 2px;font-size:12px}
 .pso-sticky-pagination{right:8px;max-width:calc(100vw - 16px)}
}
@media(max-width:360px){.order-dashboard .pso-nav-btn{padding:6px 7px}.order-dashboard .pso-top-page{font-size:11px}}
</style>

<div class="container-main order-dashboard">
  <div class="main-title-card">
    <div class="title-content">
      <div class="title-icon-wrapper shadow-sm">
        <i class="fa fa-file-invoice"></i>
      </div>
      <div class="title-text">
        <h4>Provisional Sales Orders</h4>
        <p>Track and manage your upcoming client orders</p>
      </div>
    </div>
      <div class="header-actions">
        <button class="btn-new-order newprovisionalorder">
          <i class="fa fa-plus-circle"></i> New Order
        </button>
      </div>
  </div>

  <div class="filter-strip">
    
    <div class="filter-group">
      <label class="filter-label">Order Date</label>
      <select id="orderDateFilter" class="filter-control select2" multiple="multiple" data-placeholder="All Dates">
        <?php foreach ($uniqueOrderDates as $oDate): ?>
          <option value="<?php echo $oDate; ?>"<?php echo $dashboardSelected('order_date',$oDate); ?>>
            <?php echo date('d-M-Y', strtotime($oDate)); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="filter-group">
      <label class="filter-label">Choose Client</label>
      <select id="clientFilter" class="filter-control select2" multiple="multiple" data-placeholder="All Clients">
        <?php foreach ($uniqueClientNames as $clientName): ?>
          <option value="<?php echo htmlspecialchars($clientName, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $dashboardSelected('client',$clientName); ?>>
            <?php echo htmlspecialchars($clientName, ENT_QUOTES, 'UTF-8'); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="filter-group">
      <label class="filter-label">Delivery Date</label>
      <select id="deliverDateFilter" class="filter-control select2" multiple="multiple" data-placeholder="All Dates">
        <?php foreach ($uniqueDeliveryDates as $dDate): ?>
          <option value="<?php echo $dDate; ?>"<?php echo $dashboardSelected('delivery_date',$dDate); ?>>
            <?php echo date('d-M-Y', strtotime($dDate)); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>


<div class="filter-group">
    <label class="filter-label" for="loginFilter">Choose Created By</label>

    <select
        id="loginFilter"
        class="filter-control select2"
        multiple
        data-placeholder="All Created By"
    >
        <?php foreach ($uniqueLoginNames as $creatorId => $login): ?>
            <option value="<?= htmlspecialchars((string)$creatorId, ENT_QUOTES, 'UTF-8') ?>"<?= $dashboardSelected('created_by',$creatorId) ?>>
                <?= htmlspecialchars(trim($login), ENT_QUOTES, 'UTF-8') ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>


    <div class="filter-group">
      <label class="filter-label">Status</label>
      <select id="statusFilter" class="filter-control select2" multiple="multiple" data-placeholder="All Statuses">
        <?php foreach ($filterStatuses as $filterStatus): ?>
          <option value="<?php echo htmlspecialchars($filterStatus, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $dashboardSelected('status',$filterStatus); ?>>
            <?php echo htmlspecialchars($filterStatus, ENT_QUOTES, 'UTF-8'); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="filter-group">
      <label class="filter-label">Search</label>
      <div class="search-wrapper" style="max-width: 100%;">
        <i class="fa fa-search"></i>
        <input type="text" id="orderSearchInput" class="search-control" placeholder="Search client, mobile, order or product..." autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" value="<?php echo htmlspecialchars($dashboardFilters['search'],ENT_QUOTES,'UTF-8'); ?>">
      </div>
    </div>
    <div class="filter-actions">
      <button type="button" id="clearFilters" class="btn-clear-filters">
        <i class="fa fa-times-circle"></i> Clear Filters
      </button>
      <div class="dropdown">
        <button class="btn-export dropdown-toggle" type="button" id="exportOrdersDropdown" data-bs-toggle="dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <i class="fa fa-file-excel text-success me-1"></i> Export <span class="caret"></span>
        </button>
        <div class="dropdown-menu dropdown-menu-right dropdown-menu-end shadow-lg" aria-labelledby="exportOrdersDropdown">
          <a class="dropdown-item" href="javascript:void(0)" onclick="exportPsoServer('csv','all')"><i class="fa fa-file-excel text-success"></i> Export All Matching (Excel / CSV)</a>
          <a class="dropdown-item" href="javascript:void(0)" onclick="exportPsoServer('csv','page')"><i class="fa fa-file-csv text-primary"></i> Export Current Page (Excel / CSV)</a>
          <div class="dropdown-divider my-1"></div>
          <a class="dropdown-item" href="javascript:void(0)" onclick="exportPsoServer('print','all')"><i class="fa fa-file-pdf text-danger"></i> All Matching — Print / Save PDF</a>
          <a class="dropdown-item" href="javascript:void(0)" onclick="exportPsoServer('print','page')"><i class="fa fa-print text-secondary"></i> Current Page — Print / Save PDF</a>
        </div>
      </div>
    </div>


  </div>

  <div class="strips-header">
    <div>#</div>
    <div>Date/Order</div>
    <div>Client Name</div>
    <div>Product / Qty</div>
    <div>Exp. Delivery</div>
    <div>Amount</div>
    <div>Status</div>
    <div class="text-end">Actions</div>
  </div>

  <nav class="pso-top-pagination" aria-label="Order list pagination">
    <div class="pso-top-controls">
      <?php if ($dashboardPage['page'] > 1): ?>
        <a class="pso-nav-btn" href="<?= htmlspecialchars($dashboardUrl($dashboardPage['page']-1), ENT_QUOTES, 'UTF-8') ?>" aria-label="Previous page">&#8249; <span>Previous</span></a>
      <?php else: ?><span class="pso-nav-btn is-disabled" aria-disabled="true">&#8249; <span>Previous</span></span><?php endif; ?>
      <span class="pso-top-page" aria-current="page">Page <strong><?= (int)$dashboardPage['page'] ?></strong> of <?= (int)$dashboardPage['pages'] ?></span>
      <?php if ($dashboardPage['page'] < $dashboardPage['pages']): ?>
        <a class="pso-nav-btn is-primary" href="<?= htmlspecialchars($dashboardUrl($dashboardPage['page']+1), ENT_QUOTES, 'UTF-8') ?>" aria-label="Next page"><span>Next</span> &#8250;</a>
      <?php else: ?><span class="pso-nav-btn is-disabled" aria-disabled="true"><span>Next</span> &#8250;</span><?php endif; ?>
    </div>
  </nav>
<div class="order-strips-container" id="orderStripsList">
    <?php
    if (count($psnolistDetailed) > 0):
      $sno = 1;
      foreach ($psnolistDetailed as $row):
        $statusClass = match ($row['status']) {
          'Confirmed' => 'status-confirmed',
          'Cancelled' => 'status-cancelled',
          'Pending Approval' => 'status-pending-approval',
          'Emergency Sowing' => 'status-emergency-sowing',
          default => 'status-pending'
        };

        $statusText = match ($row['status']) {
          'Confirmed' => 'Confirmed',
          'Cancelled' => 'Cancelled',
          'Pending Approval' => 'Pending Approval',
          'Emergency Sowing' => 'Emergency Sowing',
          default => 'Pending for Payment'
        };

        $groupColor = getOrderGroupColor($row['psono']);

        $readyToSaleBtn = ($row['status'] === 'Confirmed') ? 
          '<div class="mt-1"><span class="badge bg-success shadow-sm createsaleinvoice" style="cursor:pointer; font-size: 0.6rem;" data-psono="' . $row['psono'] . '"><i class="fa fa-check-circle"></i> Invoice</span></div>' : '';

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

        $paymentAdviceUrl = "paymentadvicemanual.php?k=" . urlencode($key) . 
          "&psono=" . urlencode($row['psono']) . 
          "&cname=" . urlencode($row['clientname']) . 
          "&mob=" . urlencode($row['mobileno'] ?? '') . 
          "&clid=" . urlencode($row['clientid'] ?? '') . 
          (!empty($mdid) ? "&mdid=" . urlencode($mdid) : "") . 
          "&iframe=true";
        ?>
        <div class="order-strip <?php echo $statusClass; ?>"
             data-client-name="<?php echo htmlspecialchars($row['clientname'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
             data-order-no="<?php echo htmlspecialchars($row['psono'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
             data-product-name="<?php echo htmlspecialchars($cleanProductName ?: ($row['product_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
             style="background-color: <?php echo $groupColor; ?>;"
             data-status="<?php echo htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8'); ?>"
             data-mobile="<?php echo htmlspecialchars($row['mobileno'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
          <div class="strip-item">
            <span class="strip-label">#</span>
            <div class="strip-content text-muted small"><?php echo str_pad($sno++, 2, '0', STR_PAD_LEFT); ?></div>
          </div>
          
          <div class="strip-item" data-order-date="<?php echo date('Y-m-d', strtotime($row['created_at'])); ?>">
            <span class="strip-label">Date/Order</span>
            <div class="strip-content">
               <div class="small text-muted mb-1"><?php echo date('d-M-Y', strtotime($row['created_at'])); ?></div>
               <span class="order-no-pill"><?php echo htmlspecialchars($row['psono']); ?></span>
               <?php echo $readyToSaleBtn; ?>
            </div>
          </div>

          <div
              class="strip-item"
              data-client="<?php echo htmlspecialchars($row['clientname'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
              data-login="<?php echo htmlspecialchars(trim($row['loginname'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
          >  
            <span class="strip-label">Client</span>
             <div class="strip-content text-primary font-weight-bold">
                  <?php echo htmlspecialchars($row['clientname'] ?? '', ENT_QUOTES, 'UTF-8'); ?>

                  <?php if (!empty($row['mobileno'])) { ?>
                      (<?php echo htmlspecialchars($row['mobileno'], ENT_QUOTES, 'UTF-8'); ?>)
                  <?php } ?>
              </div>
          </div>

          <div class="strip-item" data-deliver-date="<?php echo $row['expecteddeliverydate']; ?>">
            <span class="strip-label">Product / Qty</span>
            <div class="strip-content">
                <div class="font-weight-bold" style="line-height: 1.25; margin-bottom: 0.2rem;"><?php echo htmlspecialchars(!empty($cleanProductName) ? $cleanProductName : ($row['product_name'] ?? 'N/A')); ?></div>
                <div class="small mt-1 d-flex gap-3">
                    <span>Qty: <span class="badge bg-light text-dark border"><?php echo number_format($row['quantity'], 0); ?></span></span>
                </div>
            </div>
          </div>

          <div class="strip-item">
            <span class="strip-label">Exp. Delivery</span>
            <div class="strip-content">
               <span class="font-weight-bold"><?php echo ($row['expecteddeliverydate'] && $row['expecteddeliverydate'] != '0000-00-00') ? date('d-M-Y', strtotime($row['expecteddeliverydate'])) : 'N/A'; ?></span>
            </div>
          </div>

          <div class="strip-item">
            <span class="strip-label">Amount</span>
            <div class="strip-content amount-display">₹<?php echo number_format($row['item_amount'], 2); ?></div>
          </div>

          <div class="strip-item">
            <span class="strip-label">Status</span>
            <div class="strip-content">
              <?php if ($row['status'] === 'Emergency Sowing'): ?>
                <span class="status-badge bg-danger text-white shadow-xs fw-bold d-inline-flex align-items-center gap-1" style="font-size: 0.72rem; padding: 0.35rem 0.65rem; border: 1px solid #dc2626; letter-spacing: 0.3px;">
                  <i class="fa fa-exclamation-triangle"></i> PENDING APPROVAL FOR EMERGENCY SOWING
                </span>
              <?php elseif ($row['status'] === 'Pending Approval'): ?>
                <span class="status-badge bg-warning text-dark shadow-xs fw-bold d-inline-flex align-items-center gap-1" style="font-size: 0.72rem; padding: 0.35rem 0.65rem; border: 1px solid #f59e0b; letter-spacing: 0.3px;">
                  <i class="fa fa-clock"></i> PENDING APPROVAL
                </span>
              <?php elseif ($row['status'] === 'Confirmed'): ?>
                <span class="status-badge bg-success">
                  Confirmed
                </span>
              <?php elseif ($row['status'] === 'Cancelled'): ?>
                <span class="status-badge bg-danger">
                  Cancelled
                </span>
              <?php else: // Draft or Pending for Payment ?>
                <a href="<?php echo $paymentAdviceUrl; ?>" 
                   class="status-badge bg-secondary text-white text-decoration-none d-inline-flex align-items-center gap-1 shadow-sm" 
                   style="cursor: pointer; font-size: 0.72rem; padding: 0.35rem 0.65rem; transition: all 0.2s ease;" 
                   title="Click to Record Payment in Payment Advice for <?php echo htmlspecialchars($row['psono']); ?>"
                   onmouseover="this.style.opacity='0.85'; this.style.transform='scale(1.04)';"
                   onmouseout="this.style.opacity='1'; this.style.transform='none';">
                  Pending for Payment <i class="fa fa-arrow-circle-right ms-1" style="font-size: 0.75rem;"></i>
                </a>
              <?php endif; ?>
            </div>
          </div>

          <div class="action-buttons">
            <button class="btn btn-strip btn-view viewOrderBtn" data-psono="<?php echo $row['psono']; ?>" title="View Order">
              <i class="fa fa-eye"></i>
            </button>
            <?php if (trim($row['status'] ?? '') === 'Draft'): ?>
                    <a class="btn btn-strip btn-edit"
                       href="<?php echo htmlspecialchars(
                           $rooturl . 'krf_provisional_sale_order_edit/edit_provisional_order.php?mode=edit&' .
                           http_build_query([
                               'k' => $key,
                               'mdid' => $mdid,
                               'orderno' => $row['psono'],
                               'iframe' => 'true'
                           ]),
                           ENT_QUOTES,
                           'UTF-8'
                       ); ?>"
                       title="Edit Order">
                        <i class="fa fa-pencil-alt"></i>
                    </a>
                <?php endif; ?>

            <button class="btn btn-strip btn-pdf" onclick="downloadOrderPDF('<?php echo $row['psono']; ?>', <?php echo ($row['status'] === 'Confirmed') ? 'true' : 'false'; ?>);" title="<?php echo ($row['status'] === 'Confirmed') ? 'Download Final Sales Order PDF' : 'Download Provisional Order PDF'; ?>">
              <i class="fa fa-file-pdf"></i>
            </button>
            <?php if ($loginroleid < 2 && $row['status'] !== 'Confirmed'): ?>
              <!-- <button class="btn btn-strip btn-delete removeOrderBtn" data-psono="<?php echo $row['psono']; ?>" title="Remove Order">
                <i class="fa fa-trash-alt"></i>
              </button> -->
            <?php endif; ?>
          </div>

          <!-- Full Width Delivery Address / Pickup Footer -->
          <div class="strip-delivery-footer">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 w-100">
              <?php if (isset($row['orderamount'])): ?>
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 w-100"> 
                      <div class="amount-box col-md-3">
                          <span>Order Amount</span>
                          <strong> ₹<?= number_format((float)$row['orderamount'], 2) ?>
                          </strong>
                      </div>
                      <div class="amount-box col-md-3">
                          <span>Received Amount</span>
                          <strong class="text-success"> ₹<?= number_format( (float)($row['advanceamt'] ?? 0), 2 ) ?>
                          </strong>
                      </div>

                      <div class="amount-box col-md-3">
                          <span>Balance Amount</span>
                          <strong class="text-danger">
                              ₹<?= number_format( (float)($row['balanceamt'] ?? 0), 2) ?>
                          </strong>
                      </div>
                  </div> 
              <?php endif; ?>
              
                <?php if (!empty($row['loginname'])): ?>
                    <span>Created By <strong><?php echo htmlspecialchars($row['loginname']); ?></strong></span>
                <?php endif; ?>
                <?php if (!empty($row['updated_by']) && !empty($row['updated_at']) && $row['updated_at'] != '0000-00-00 00:00:00'): ?>
                    <span class="text-secondary" style="font-size: 0.83rem;">
                        | Last Updated By <strong><?php echo htmlspecialchars($row['updatedby_name'] ?: $row['updated_by']); ?></strong>
                        at <strong><?php echo date('d-M-Y h:i A', strtotime($row['updated_at'])); ?></strong>
                    </span>
                <?php endif; ?>
                <?php if ($pickupfrom === 'pickup'): ?>
                  <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1" style="font-size: 0.78rem;">
                    <i class="fa fa-walking me-1"></i> Self Pickup by Client
                  </span>
                  <span class="text-muted" style="font-size: 0.83rem;">
                    Pickup by client as mentioned delivery date<?php if (!empty($row['expecteddeliverydate']) && $row['expecteddeliverydate'] != '0000-00-00'): ?>: <strong><?php echo date('d-M-Y', strtotime($row['expecteddeliverydate'])); ?></strong><?php endif; ?>
                  </span>

                <?php elseif ($pickupfrom === 'boxpacking'): ?>
                  <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1" style="font-size: 0.78rem;">
                    <i class="fa fa-box me-1"></i> Box Packing
                  </span>
                  <?php if (!empty($locDisplayList)): ?>
                    <span class="fw-bold text-secondary" style="font-size: 0.8rem;">Delivery Address:</span>
                    <span class="text-dark fw-medium" style="font-size: 0.84rem;">
                      <?php echo htmlspecialchars(implode('; ', $locDisplayList)); ?>
                    </span>
                  <?php else: ?>
                    <span class="text-muted" style="font-size: 0.83rem;">Box Packing for dispatch.</span>
                  <?php endif; ?>
                <?php else: ?>
                  <?php if (!empty($locDisplayList)): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 0.78rem;">
                      <i class="fa fa-truck me-1"></i> Company Delivery
                    </span>
                    <span class="fw-bold text-secondary" style="font-size: 0.8rem;">Delivery Address:</span>
                    <span class="text-dark fw-medium" style="font-size: 0.84rem;">
                      <?php echo htmlspecialchars(implode('; ', $locDisplayList)); ?>
                    </span>
                  <?php else: ?>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" style="font-size: 0.78rem;">
                      <i class="fa fa-exclamation-triangle me-1"></i> Missing Delivery Address
                    </span>
                    <span class="text-danger fw-semibold" style="font-size: 0.82rem;">
                      Delivery mode is Company Delivery, but no delivery location is recorded.
                    </span>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
               <div class="d-flex align-items-center gap-2 ms-auto mt-1">
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
                              class="btn btn-sm btn-outline-primary px-2 py-1 fw-bold shadow-sm d-inline-flex align-items-center gap-1"
                              style="font-size:0.76rem; border-radius:6px; background-color:#eff6ff; border:1px solid #bfdbfe; color:#1d4ed8; white-space:nowrap;">

                          <i class="fa fa-edit"></i> Update Address

                      </button>

                   <?php endif; ?>

                  <?php 
                    // Show "Old Order Payment update" button only for pending for payment orders
                    $isPendingForPayment = !in_array($row['status'], ['Confirmed', 'Cancelled']);
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
                              class="btn btn-sm btn-success px-2.5 py-1 fw-bold shadow-sm d-inline-flex align-items-center gap-1"
                              style="font-size:0.76rem; border-radius:6px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: 1px solid #059669; color:#fff; white-space:nowrap;"
                              title="Update Payment for Provisional Order <?php echo htmlspecialchars($row['psono']); ?>">

                          <i class="fa fa-money-bill-wave"></i> Old Order Payment update

                      </button>
                  <?php endif; ?>
              </div>
            </div>
          </div>
      <?php endforeach;
    else: ?>
      <div class="text-center py-5 bg-white rounded-3 shadow-sm border">
        <h5 class="text-muted">No Orders Found</h5>
        <a href="finalsaleorder_review_new.php?k=<?php echo $key; ?>&mdid=<?php echo $mdid; ?>" class="btn btn-outline-primary btn-sm mt-3">View Sale Orders</a>
      </div>
    <?php endif; ?>
    <div id="noSearchResults" class="text-center py-5 bg-white rounded-3 shadow-sm border" hidden>
      <h5 class="text-muted">No Matching Orders Found</h5>
    </div>
  </div>
</div>


<?php if (($dashboardPage['pages'] ?? 1) > 1): ?>
<nav class="pso-sticky-pagination" aria-label="Provisional sales order pagination">
  <div class="pso-sticky-actions">
    <?php if ($dashboardPage['page'] > 1): ?>
      <a class="pso-page-link" aria-label="Previous page" href="<?= htmlspecialchars($dashboardUrl($dashboardPage['page']-1), ENT_QUOTES, 'UTF-8') ?>">Previous</a>
    <?php else: ?><span class="pso-page-link disabled" aria-disabled="true">Previous</span><?php endif; ?>
    <span class="pso-page-info"><?= (int)$dashboardPage['page'] ?> / <?= (int)$dashboardPage['pages'] ?></span>
    <?php if ($dashboardPage['page'] < $dashboardPage['pages']): ?>
      <a class="pso-page-link next" aria-label="Next page" href="<?= htmlspecialchars($dashboardUrl($dashboardPage['page']+1), ENT_QUOTES, 'UTF-8') ?>">Next</a>
    <?php else: ?><span class="pso-page-link disabled" aria-disabled="true">Next</span><?php endif; ?>
  </div>
</nav>
<?php endif; ?>

<!-- Modal Logic Preserved -->
<div class="modal fade" id="viewOrderModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content shadow-lg border-0">
      <div class="modal-header bg-light">
        <h5 class="modal-title font-weight-bold">Order Details</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="$('#viewOrderModal').modal('hide')">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="viewOrderContent"></div>
    </div>
  </div>
</div>

<div class="modal fade" id="editOrderModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document" style="max-width: 90%;">
    <div class="modal-content shadow-lg border-0">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="editOrderheader">Edit Provisional Sales Order</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" onclick="$('#editOrderModal').modal('hide')">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="editOrderContent"></div>
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
            <!-- Row 1: Payment Date & Amount -->
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

            <!-- Row 2: Booking No & Upload Booking Receipt -->
            <div class="col-md-6">
              <label class="form-label fw-bold small text-secondary">Booking No <span class="text-muted fw-normal">(Optional)</span></label>
              <input type="text" class="form-control" id="oop_bookingno" name="bookingno" placeholder="Enter Booking Number">
            </div>

            <div class="col-md-6">
              <label class="form-label fw-bold small text-secondary">Upload Booking Receipt <span class="text-muted fw-normal">(Optional)</span></label>
              <input type="file" class="form-control" id="oop_bookingreceipt" name="bookingreceipt" accept="image/*,.pdf">
              <small class="text-muted" style="font-size: 0.74rem;">Attach booking receipt / confirmation slip</small>
            </div>

            <!-- Row 3: Payment Mode / Source as Single Row Radio Options -->
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
                  position: static !important;
                  display: inline-block !important;
                  -webkit-appearance: radio !important;
                  -moz-appearance: radio !important;
                  appearance: radio !important;
                  width: 18px !important;
                  height: 18px !important;
                  min-width: 18px !important;
                  min-height: 18px !important;
                  max-width: 18px !important;
                  max-height: 18px !important;
                  margin: 0 10px 0 0 !important;
                  padding: 0 !important;
                  vertical-align: middle !important;
                  cursor: pointer !important;
                  flex-shrink: 0 !important;
                  float: none !important;
                  box-shadow: none !important;
                  transform: none !important;
                }
                #oldOrderPaymentModal .oop-radio-label-text {
                  display: inline-flex !important;
                  align-items: center !important;
                  font-size: 0.92rem !important;
                  font-weight: 600 !important;
                  color: #1e293b !important;
                  line-height: 1.2 !important;
                  cursor: pointer !important;
                  margin: 0 !important;
                  padding: 0 0 0 2px !important;
                  user-select: none !important;
                }
                #oldOrderPaymentModal .oop-radio-label-text i {
                  margin-right: 7px !important;
                  font-size: 1rem !important;
                }
              </style>
              <label class="form-label fw-bold small text-secondary mb-1 d-block">Payment Mode / Source <span class="text-danger">*</span></label>
              <div class="oop-radio-group">
                <label class="oop-radio-item" for="src_cash">
                  <input type="radio" class="oop-radio-input" name="sourceofpayment_radio" id="src_cash" value="Cash" checked style="accent-color: #10b981;" onchange="handlePaymentModeRadioChange(this.value)">
                  <span class="oop-radio-label-text">
                    <i class="fa fa-money-bill-wave text-success"></i> Cash
                  </span>
                </label>

                <label class="oop-radio-item" for="src_upi">
                  <input type="radio" class="oop-radio-input" name="sourceofpayment_radio" id="src_upi" value="UPI" style="accent-color: #3b82f6;" onchange="handlePaymentModeRadioChange(this.value)">
                  <span class="oop-radio-label-text">
                    <i class="fa fa-qrcode text-primary"></i> UPI
                  </span>
                </label>

                <label class="oop-radio-item" for="src_bank">
                  <input type="radio" class="oop-radio-input" name="sourceofpayment_radio" id="src_bank" value="Bank Transfer" style="accent-color: #0284c7;" onchange="handlePaymentModeRadioChange(this.value)">
                  <span class="oop-radio-label-text">
                    <i class="fa fa-university text-info"></i> Bank Transfer
                  </span>
                </label>

                <label class="oop-radio-item" for="src_cheque">
                  <input type="radio" class="oop-radio-input" name="sourceofpayment_radio" id="src_cheque" value="Cheque" style="accent-color: #f59e0b;" onchange="handlePaymentModeRadioChange(this.value)">
                  <span class="oop-radio-label-text">
                    <i class="fa fa-money-check-alt text-warning"></i> Cheque
                  </span>
                </label>
              </div>
            </div>

            <!-- Row 4A: If Cash - Cash Received By & Cash Received Place -->
            <div class="col-12 m-0 p-0" id="oop_cash_section">
              <div class="row g-3 px-2 pt-2">
                <div class="col-md-6">
                  <label class="form-label fw-bold small text-secondary">Cash Received By</label>
                  <input type="text" class="form-control" id="oop_casgreceivedby" name="casgreceivedby" placeholder="Staff / Person name">
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold small text-secondary">Cash Received Place / Location</label>
                  <input type="text" class="form-control" id="oop_casgreceuvelocation" name="casgreceuvelocation" placeholder="e.g. Karjee Farm Office / Direct Handover">
                </div>
              </div>
            </div>

            <!-- Row 4B: If UPI / Bank Transfer / Cheque - Bank Account & UTR/Cheque No -->
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
                  <input type="text" class="form-control" id="oop_utrnumber" name="utrnumber" placeholder="Enter UTR / Transaction No (if available)">
                </div>
              </div>
            </div>

            <!-- Row 5: Optional Payment Receipt Upload -->
            <div class="col-12">
              <label class="form-label fw-bold small text-secondary">Payment Receipt / Screenshot <span class="text-muted fw-normal">(Optional)</span></label>
              <input type="file" class="form-control" id="oop_paymentreceipt" name="paymentreceipt" accept="image/*,.pdf">
              <small class="text-muted" style="font-size: 0.74rem;">Attach receipt screenshot or PDF if available</small>
            </div>

            <!-- Row 6: Remarks -->
            <div class="col-12">
              <label class="form-label fw-bold small text-secondary">Remarks / Notes</label>
              <textarea class="form-control" id="oop_remarks" name="remarks" rows="2" placeholder="Enter any payment notes or remarks (optional)..."></textarea>
            </div>
          </div>
        </form>
      </div>

      <div class="modal-footer bg-light py-2.5 px-3 d-flex justify-content-between">
        <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal" onclick="$('#oldOrderPaymentModal').modal('hide')">
          <i class="fa fa-times me-1"></i> Close
        </button>
        <button type="button" class="btn btn-success fw-bold px-3 shadow-sm" id="btnSubmitOldOrderPayment" onclick="submitOldOrderAdvancePayment()">
          <i class="fa fa-check-circle me-1"></i> Submit Payment Update
        </button>
      </div>
    </div>
  </div>
</div>

<?php include('../footer.php'); ?>
<!-- Invisible same-tab print target. Browser print dialog opens after report finishes loading. -->
<iframe id="psoDirectPrintFrame" title="Provisional orders print" aria-hidden="true" tabindex="-1" style="position:fixed;width:0;height:0;border:0;opacity:0;pointer-events:none" onload="psoDirectPrintReady(this)"></iframe>
<iframe id="psoDownloadFrame" title="Export download" style="display:none" aria-hidden="true"></iframe>

<script type="text/javascript" src="<?php echo $prefix; ?>krf-orderandsales/js/pjs_function.js"></script>
<script type="text/javascript" src="<?php echo $prefix; ?>krf-orderandsales/js/invoice_calculation.js"></script>
<script> 
 function openDeliveryAddressModal(clientId, orderId, clientname, createddate) {
    const current = new URL(window.location.href);
    const root = String($('body').data('routurl') || '../').replace(/\/?$/, '/');
    const target = new URL(root + 'krf_provisional_sale_order_edit/order.php', window.location.href);
    target.searchParams.set('mode', 'address');
    target.searchParams.set('orderno', orderId);
    target.searchParams.set('k', current.searchParams.get('k') || String($('body').data('key') || ''));
    target.searchParams.set('mdid', current.searchParams.get('mdid') || '');
    target.searchParams.set('iframe', 'true');
    window.location.href = target.toString();
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
    
    // Default to Cash mode
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

    // Server-side filters: searches every PSO, including those beyond the current page.
    var timer;
    window.filterProvisionalOrders = function(sourceElement) {
        clearTimeout(timer);
        timer=setTimeout(function(){
            var dashboard=resolveDashboard(sourceElement); if(!dashboard) return;
            var url=new URL(window.location.href); var p=url.searchParams;
            ['pso_order_date[]','pso_client[]','pso_delivery_date[]','pso_created_by[]','pso_status[]'].forEach(function(k){p.delete(k);});
            [['#orderDateFilter','pso_order_date[]'],['#clientFilter','pso_client[]'],['#deliverDateFilter','pso_delivery_date[]'],['#loginFilter','pso_created_by[]'],['#statusFilter','pso_status[]']].forEach(function(pair){
                var el=dashboard.querySelector(pair[0]); if(el) Array.from(el.selectedOptions).forEach(function(opt){p.append(pair[1],opt.value);});
            });
            var input=dashboard.querySelector('#orderSearchInput');
            if(input && input.value.trim()) p.set('pso_search',input.value.trim()); else p.delete('pso_search');
            p.delete('pso_page'); window.location.href=url.toString();
        },sourceElement && sourceElement.id==='orderSearchInput'?550:120);
    };
    if(!window.__krfPsoServerEvents) {
        window.__krfPsoServerEvents=true;
        document.addEventListener('input',function(e){if(e.target.matches('.order-dashboard #orderSearchInput')) window.filterProvisionalOrders(e.target);});
        document.addEventListener('change',function(e){if(e.target.matches('.order-dashboard #clientFilter,.order-dashboard #deliverDateFilter,.order-dashboard #orderDateFilter,.order-dashboard #statusFilter,.order-dashboard #loginFilter')) window.filterProvisionalOrders(e.target);});
        document.addEventListener('click',function(e){var clear=e.target.closest('.order-dashboard #clearFilters');if(!clear)return; e.preventDefault(); var u=new URL(window.location.href);Array.from(u.searchParams.keys()).forEach(function(k){if(k.startsWith('pso_'))u.searchParams.delete(k);});window.location.href=u.toString();});
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

    /* Select2 uses jQuery events; native addEventListener('change') alone
       does not receive every Select2 select/unselect action. */
    var provisionalFilterSelector =
        '.order-dashboard #clientFilter, ' +
        '.order-dashboard #deliverDateFilter, ' +
        '.order-dashboard #orderDateFilter, ' +
        '.order-dashboard #statusFilter, ' +
        '.order-dashboard #loginFilter';

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
                window.filterProvisionalOrders(this);
            }
        );

    var initialSearch = document.querySelector('.order-dashboard #orderSearchInput');
    // Results are already filtered in SQL.


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

    // newprovisionalorder - Redirect to unified Add/Edit form
    $(document).on("click", ".newprovisionalorder", function () {
      let key = $("body").data("key");
      let routurl = $("body").data("routurl") || "";
      window.location.href = routurl + "krf-orderandsales/addNewProvisionalsaleorder.php?mode=add&k=" + encodeURIComponent(key) + "&mdid=<?php echo urlencode($mdid); ?>&iframe=true";
    });

    // Remove Order commit by ajayon 06-10-2026  for delete
    // $(document).on("click", ".removeOrderBtn", function () {
    //   let psono = $(this).data("psono");
    //   let projectid = $("body").data("projectid");
      
    //   if (confirm("Are you sure you want to remove this provisional order (" + psono + ")? This will mark it as cancelled.")) {
    //     $.ajax({
    //       url: "modal/product_modal.php?action=DeleteProvisionalOrder",
    //       type: "POST",
    //       data: { psono: psono, projectid: projectid },
    //       success: function (response) {
    //         let res = JSON.parse(response);
    //         if (res.status === 'success') {
    //           alert(res.message);
    //           window.location.reload();
    //         } else {
    //           alert("Error: " + res.message);
    //         }
    //       }
    //     });
    //   }
    // });
  });

  let psoPrintRequested = false;
  function psoDirectPrintReady(frame) {
    if (!psoPrintRequested) return;
    psoPrintRequested = false;
    try {
      const report = frame.contentWindow;
      report.focus();
      report.print();
    } catch (error) {
      console.error('PSO print failed:', error);
      alert('Unable to open the print dialog. Please try again.');
    }
  }
  function exportPsoServer(format, scope) {
    const url = new URL(window.location.href);
    url.searchParams.set('pso_export', format);
    url.searchParams.set('pso_scope', scope);
    if (scope === 'all') url.searchParams.delete('pso_page');
    if (format === 'print') {
      const frame = document.getElementById('psoDirectPrintFrame');
      psoPrintRequested = true;
      frame.src = url.toString();
    } else {
      document.getElementById('psoDownloadFrame').src = url.toString();
    }
  }

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
      "Status"
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
      let orderDate = String($strip.find(".strip-item[data-order-date]").data("order-date") || $strip.find(".strip-item:eq(1) .small").text() || "").trim();
      let clientName = String($strip.find(".strip-item[data-client]").data("client") || "").trim();
      let contactNo = String($strip.attr("data-mobile") ?? $strip.data("mobile") ?? "").trim();
      let createdBy = String($strip.find(".strip-item[data-login]").data("login") || "").trim();
      let productName = String($strip.find(".strip-item:eq(3) .font-weight-bold").text() || "").trim();
      let qty = String($strip.find(".strip-item:eq(3) .badge").text() || "").trim().replace(/,/g, "");
      let expDelivery = String($strip.find(".strip-item:eq(4) .strip-content").text() || "").trim();
      let itemAmount = String($strip.find(".amount-display").text() || "").trim().replace(/[₹,]/g, "");
      let orderAmount = String($strip.find(".amount-box:eq(0) strong").text() || "").trim().replace(/[₹,]/g, "");
      let receivedAmount = String($strip.find(".amount-box:eq(1) strong").text() || "").trim().replace(/[₹,]/g, "");
      let balanceAmount = String($strip.find(".amount-box:eq(2) strong").text() || "").trim().replace(/[₹,]/g, "");
      let status = String($strip.data("status") || $strip.find(".status-badge").text() || "").trim();

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
        `"${status.replace(/"/g, '""')}"`
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
</script>
