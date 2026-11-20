<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$loginid = "";
$loginroleid = "";
$companyid = "";
$subcompanyid = "";
$projectid = "";
$loginname = "";
$key = $_GET['k'] ?? '';
$mdid = $_GET['mdid'] ?? '';
$dz = $_GET['dz'] ?? '';

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443 ? "https://" : "http://";
$url = basename($_SERVER["PHP_SELF"]);
$rooturl = $protocol . $_SERVER['HTTP_HOST'];
if ($_SERVER['HTTP_HOST'] == "localhost" || $_SERVER['HTTP_HOST'] == "192.168.1.65") {
  $prefix = $rooturl . "/krf_amanaccounting/";
} else {
  $prefix = $rooturl . "/";
}
$rooturl = $prefix;

$use_standalone_layout = false;

if (!empty($dz) || empty($key)) {
  $use_standalone_layout = true;
  if (file_exists(dirname(__DIR__) . '/connection.php')) {
    require_once(dirname(__DIR__) . '/connection.php');
  }
  if (file_exists(dirname(__DIR__) . '/functions/function_master.php')) {
    include_once(dirname(__DIR__) . '/functions/function_master.php');
  }
  if (file_exists(__DIR__ . '/functions/orders_functions.php')) {
    include_once(__DIR__ . '/functions/orders_functions.php');
  }

  if (!empty($key)) {
    $key_decoded = base64url_decode($key);
    $key_parts = explode("|", $key_decoded);
    $checkkey = base64url_decode($key_parts[count($key_parts) - 1] ?? '');
    $actkey = "";
    for ($i = 0; $i < count($key_parts) - 1; $i++) {
      $actkey = $actkey ? $actkey . "|" . $key_parts[$i] : $key_parts[$i];
    }
    if ($actkey == $checkkey) {
      $loginid = $key_parts[0] ?? '';
      $loginroleid = $key_parts[1] ?? '';
      $companyid = $key_parts[2] ?? '';
      $subcompanyid = $key_parts[3] ?? '';
      $projectid = $key_parts[4] ?? '';
    }
  }

  if (empty($loginid) && !empty($_SESSION['loginid'])) $loginid = $_SESSION['loginid'];
  if (empty($loginroleid) && !empty($_SESSION['loginroleid'])) $loginroleid = $_SESSION['loginroleid'];
  if (empty($companyid) && !empty($_SESSION['companyid'])) $companyid = $_SESSION['companyid'];
  if (empty($subcompanyid) && !empty($_SESSION['subcompanyid'])) $subcompanyid = $_SESSION['subcompanyid'];
  if (empty($projectid) && !empty($_SESSION['projectid'])) $projectid = $_SESSION['projectid'];
  if (empty($loginname) && !empty($_SESSION['loginname'])) $loginname = $_SESSION['loginname'];
} else {
  $hide_heading = true;
  require('../header.php');
  if (file_exists(__DIR__ . '/functions/orders_functions.php')) {
    include_once(__DIR__ . '/functions/orders_functions.php');
  }
}

if (empty($projectid)) $projectid = $_SESSION['projectid'] ?? 17;
if (empty($companyid)) $companyid = $_SESSION['companyid'] ?? 19;
if (empty($subcompanyid)) $subcompanyid = $_SESSION['subcompanyid'] ?? 12;

// Load user master lookup map
$users_map = [];
if (isset($authconn)) {
    $resU = $authconn->query("SELECT loginid, loginname, mobileno FROM login_user_master");
    if ($resU) {
        while($u = $resU->fetch_assoc()) {
            if (!empty($u['loginid'])) $users_map[$u['loginid']] = $u['loginname'];
            if (!empty($u['mobileno'])) $users_map[$u['mobileno']] = $u['loginname'];
            if (!empty($u['loginname'])) $users_map[strtolower($u['loginname'])] = $u['loginname'];
        }
    }
}

// -------------------------------------------------------------
// Date Filter Handling (Default: Today)
// -------------------------------------------------------------
$daterange_preset = $_GET['daterange_preset'] ?? 'today';
$today = date('Y-m-d');

switch ($daterange_preset) {
    case 'yesterday':
        $datefrom = date('Y-m-d', strtotime('-1 day'));
        $dateto = date('Y-m-d', strtotime('-1 day'));
        break;
    case 'last_7_days':
        $datefrom = date('Y-m-d', strtotime('-6 days'));
        $dateto = $today;
        break;
    case 'last_15_days':
        $datefrom = date('Y-m-d', strtotime('-14 days'));
        $dateto = $today;
        break;
    case 'current_month':
        $datefrom = date('Y-m-01');
        $dateto = date('Y-m-t');
        break;
    case 'previous_month':
        $datefrom = date('Y-m-01', strtotime('first day of last month'));
        $dateto = date('Y-m-t', strtotime('last day of last month'));
        break;
    case 'custom':
        $datefrom = !empty($_GET['datefrom']) ? $_GET['datefrom'] : $today;
        $dateto = !empty($_GET['dateto']) ? $_GET['dateto'] : $today;
        break;
    case 'today':
    default:
        $daterange_preset = 'today';
        $datefrom = $today;
        $dateto = $today;
        break;
}

// Format display dates
$display_datefrom = date('d M Y', strtotime($datefrom));
$display_dateto = date('d M Y', strtotime($dateto));

// -------------------------------------------------------------
// Fetch and Aggregate Transactions
// -------------------------------------------------------------
$esc_datefrom = $krfconn->real_escape_string($datefrom);
$esc_dateto = $krfconn->real_escape_string($dateto);

$sql = "SELECT sno, paymentdate, advanceamount, utrnumber, sourceofpayment, purposeofpayment, receivedby, allocatedby, allocated_datetime, clientname, invoiceno, verification, verificationby 
        FROM advance_payment_transactions 
        WHERE paymentdate BETWEEN '{$esc_datefrom}' AND '{$esc_dateto}' 
          AND (tedit IS NULL OR tedit = '')
        ORDER BY paymentdate DESC, sno DESC";

$res = $krfconn->query($sql);

$matrix = [];
$users_list = [];
$types_list = [];
$user_totals = [];
$type_totals = [];
$cell_records = [];

$total_collected_amount = 0.00;
$total_transactions_count = 0;
$cash_total_amount = 0.00;
$upi_total_amount = 0.00;
$bank_total_amount = 0.00;
$other_total_amount = 0.00;

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $rawType = trim($row['sourceofpayment'] ?? '');
        $uType = strtoupper($rawType);
        
        if (empty($rawType)) {
            $type = 'Not Specified';
        } elseif ($uType === 'UPI') {
            $type = 'UPI';
        } elseif ($uType === 'CASH') {
            $type = 'Cash';
        } elseif (in_array($uType, ['BANK TRANSFER', 'BANK', 'NEFT', 'RTGS', 'IMPS'])) {
            $type = 'Bank Transfer';
        } elseif (in_array($uType, ['CHEQUE', 'CHECK'])) {
            $type = 'Cheque';
        } else {
            $type = ucwords(strtolower($rawType));
        }

        // Determine user who updated/entered payment
        $raw_user = trim($row['allocatedby'] ?? '');
        if (empty($raw_user)) $raw_user = trim($row['receivedby'] ?? '');
        if (empty($raw_user)) $raw_user = trim($row['verificationby'] ?? '');

        if (empty($raw_user)) {
            $display_user = 'System / Unassigned';
        } elseif (isset($users_map[$raw_user])) {
            $display_user = $users_map[$raw_user];
        } elseif (isset($users_map[strtolower($raw_user)])) {
            $display_user = $users_map[strtolower($raw_user)];
        } else {
            $display_user = ucwords(strtolower($raw_user));
        }

        $amt = floatval($row['advanceamount'] ?? 0);

        if (!in_array($display_user, $users_list)) {
            $users_list[] = $display_user;
        }
        if (!in_array($type, $types_list)) {
            $types_list[] = $type;
        }

        if (!isset($matrix[$type][$display_user])) {
            $matrix[$type][$display_user] = ['amount' => 0.00, 'count' => 0];
        }
        $matrix[$type][$display_user]['amount'] += $amt;
        $matrix[$type][$display_user]['count'] += 1;

        if (!isset($user_totals[$display_user])) {
            $user_totals[$display_user] = ['amount' => 0.00, 'count' => 0];
        }
        $user_totals[$display_user]['amount'] += $amt;
        $user_totals[$display_user]['count'] += 1;

        if (!isset($type_totals[$type])) {
            $type_totals[$type] = ['amount' => 0.00, 'count' => 0];
        }
        $type_totals[$type]['amount'] += $amt;
        $type_totals[$type]['count'] += 1;

        $total_collected_amount += $amt;
        $total_transactions_count += 1;

        if ($type === 'Cash') $cash_total_amount += $amt;
        elseif ($type === 'UPI') $upi_total_amount += $amt;
        elseif ($type === 'Bank Transfer') $bank_total_amount += $amt;
        else $other_total_amount += $amt;

        // Store cell details for modal drill-down
        if (!isset($cell_records[$type][$display_user])) {
            $cell_records[$type][$display_user] = [];
        }
        $cell_records[$type][$display_user][] = [
            'sno' => $row['sno'],
            'date' => $row['paymentdate'],
            'client' => $row['clientname'] ?: 'N/A',
            'order' => $row['invoiceno'] ?: 'N/A',
            'amount' => $amt,
            'utr' => $row['utrnumber'] ?: 'N/A',
            'verification' => $row['verification'] ?: 'unverified'
        ];
    }
}

// Sort user names and transaction types
sort($users_list, SORT_NATURAL | SORT_FLAG_CASE);
sort($types_list, SORT_NATURAL | SORT_FLAG_CASE);

// Find top collector
$top_user = 'None';
$top_user_amt = 0;
foreach ($user_totals as $u => $val) {
    if ($val['amount'] > $top_user_amt) {
        $top_user_amt = $val['amount'];
        $top_user = $u;
    }
}

// Build query parameter passthroughs
$nav_params = [];
if (!empty($key)) $nav_params[] = "k=" . urlencode($key);
if (!empty($mdid)) $nav_params[] = "mdid=" . urlencode($mdid);
if (!empty($dz)) $nav_params[] = "dz=" . urlencode($dz);
$nav_extra = !empty($nav_params) ? "?" . implode("&", $nav_params) : "";
?>

<?php if ($use_standalone_layout): ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment Summary Matrix | Karjee Farm</title>

  <!-- Google Fonts & Bootstrap -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<?php else: ?>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<?php endif; ?>

<script>
  if (window.self !== window.top) {
    document.documentElement.classList.add('is-iframe');
    if (document.body) document.body.classList.add('is-iframe');
  }
</script>
<style>
  :root {
    --primary: #2563eb;
    --primary-dark: #1e40af;
    --secondary: #64748b;
    --accent: #0ea5e9;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --purple: #8b5cf6;
    --bg-page: #f8fafc;
    --card-bg: #ffffff;
    --card-border: #e2e8f0;
  }

  body {
    font-family: 'Inter', sans-serif;
    background: var(--bg-page);
    color: #1e293b;
    margin: 0;
    padding: 0;
    min-height: 100vh;
  }

  html.is-iframe .navbar,
  html.is-iframe nav.navbar,
  html.is-iframe .navbar-premium,
  html.is-iframe .company-header-strip,
  body.is-iframe .navbar,
  body.is-iframe nav.navbar,
  body.is-iframe .navbar-premium,
  body.is-iframe .company-header-strip,
  .is-iframe .navbar,
  .is-iframe nav.navbar,
  .is-iframe .navbar-premium,
  .is-iframe .company-header-strip {
    display: none !important;
  }

  .font-heading {
    font-family: 'Outfit', sans-serif;
  }

  .kpi-card {
    background: #ffffff;
    border: 1px solid var(--card-border);
    border-radius: 14px;
    padding: 16px 20px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
    transition: transform 0.2s, box-shadow 0.2s;
    height: 100%;
  }

  .kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
  }

  .kpi-icon-box {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
  }

  .summary-card {
    background: #ffffff;
    border: 1px solid var(--card-border);
    border-radius: 14px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
    overflow: hidden;
  }

  .filter-panel {
    background: #ffffff;
    border: 1px solid var(--card-border);
    border-radius: 14px;
    padding: 16px 20px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
  }

  /* Matrix Table Styling with specific padding overrides */
  .matrix-table-container {
    width: 100%;
    overflow-x: auto;
  }

  .summary-card table.matrix-table {
    width: 100%;
    margin-bottom: 0;
    border-collapse: separate;
    border-spacing: 0;
  }

  .summary-card table.matrix-table thead th {
    background: #f1f5f9 !important;
    color: #334155 !important;
    font-family: 'Outfit', sans-serif;
    font-weight: 700 !important;
    font-size: 0.82rem !important;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #cbd5e1 !important;
    border-top: none !important;
    padding: 14px 16px !important;
    white-space: nowrap;
    text-align: right;
  }

  .summary-card table.matrix-table thead th:first-child {
    text-align: left;
    padding-left: 24px !important;
    position: sticky;
    left: 0;
    background: #f1f5f9 !important;
    z-index: 2;
    min-width: 200px;
  }

  .summary-card table.matrix-table thead th:last-child {
    padding-right: 24px !important;
    background: #e2e8f0 !important;
    color: #1e293b !important;
  }

  .summary-card table.matrix-table tbody td {
    padding: 14px 16px !important;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9 !important;
    border-top: none !important;
    font-size: 0.90rem !important;
    text-align: right;
    white-space: nowrap;
  }

  .summary-card table.matrix-table tbody td:first-child {
    text-align: left;
    padding-left: 24px !important;
    font-weight: 600;
    position: sticky;
    left: 0;
    background: #ffffff;
    z-index: 1;
    border-right: 1px solid #f1f5f9;
  }

  .summary-card table.matrix-table tbody tr:hover td:first-child {
    background: #f8fafc !important;
  }

  .summary-card table.matrix-table tbody tr:hover td {
    background: #f8fafc !important;
  }

  .summary-card table.matrix-table tbody td:last-child {
    padding-right: 24px !important;
    font-weight: 700;
    background: #f8fafc;
    color: #0f172a;
    border-left: 1px solid #e2e8f0;
  }

  .summary-card table.matrix-table tfoot th,
  .summary-card table.matrix-table tfoot td {
    padding: 16px 16px !important;
    vertical-align: middle;
    background: #0f172a !important;
    color: #ffffff !important;
    font-family: 'Outfit', sans-serif;
    font-size: 0.95rem !important;
    font-weight: 700 !important;
    border-top: 2px solid #334155 !important;
    text-align: right;
    white-space: nowrap;
  }

  .summary-card table.matrix-table tfoot th:first-child,
  .summary-card table.matrix-table tfoot td:first-child {
    text-align: left;
    padding-left: 24px !important;
    position: sticky;
    left: 0;
    z-index: 2;
    background: #0f172a !important;
  }

  .summary-card table.matrix-table tfoot td:last-child {
    padding-right: 24px !important;
    background: #0284c7 !important;
    color: #ffffff !important;
  }

  .cell-amt {
    font-weight: 700;
    font-family: 'Outfit', sans-serif;
    color: #0f172a;
    display: block;
  }

  .cell-count {
    font-size: 0.72rem;
    color: #64748b;
    margin-top: 2px;
  }

  .cell-clickable {
    cursor: pointer;
    transition: all 0.15s ease-in-out;
    border-radius: 6px;
    padding: 4px 6px;
  }

  .cell-clickable:hover {
    background: #e0f2fe;
    color: #0284c7;
  }

  .cell-clickable:hover .cell-amt {
    color: #0284c7;
  }

  .type-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    font-size: 0.90rem;
  }

  .type-icon-circle {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
  }

  .btn-nav-custom {
    font-size: 0.85rem;
    font-weight: 600;
    border-radius: 8px;
    padding: 7px 14px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
    text-decoration: none;
  }

  @media print {
    body {
      background: #ffffff !important;
      padding: 0 !important;
    }
    .no-print, .filter-panel, .btn-nav-custom {
      display: none !important;
    }
    .summary-card {
      box-shadow: none !important;
      border: 1px solid #000000 !important;
    }
  }
</style>

<?php if ($use_standalone_layout): ?>
</head>
<body>
<?php endif; ?>

<div class="container-fluid px-3 px-md-4 py-4" style="max-width: 1480px; margin: 0 auto;">

  <!-- Page Header -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <div class="bg-primary text-white rounded-3 p-2 d-inline-flex">
          <i class="fa-solid fa-chart-pie fa-fw"></i>
        </div>
        <h3 class="font-heading fw-bold mb-0 text-dark">Payment Summary Matrix</h3>
      </div>
      <p class="text-muted small mb-0">
        Transaction type breakdown against update users for 
        <span class="badge bg-light text-dark border px-2 py-1 ms-1 fw-semibold">
          <i class="fa-regular fa-calendar me-1 text-primary"></i> <?= htmlspecialchars($display_datefrom) ?> - <?= htmlspecialchars($display_dateto) ?>
        </span>
      </p>
    </div>

    <!-- Quick Navigation Links -->
    <div class="d-flex flex-wrap align-items-center gap-2 no-print">
      <button onclick="window.print()" class="btn btn-outline-secondary btn-nav-custom">
        <i class="fa-solid fa-print"></i> Print
      </button>
      <button onclick="exportMatrixToCSV()" class="btn btn-outline-success btn-nav-custom">
        <i class="fa-solid fa-file-excel"></i> Export CSV
      </button>
      <a href="paymenthistory_review.php<?= $nav_extra ?>" class="btn btn-outline-primary btn-nav-custom">
        <i class="fa-solid fa-clock-rotate-left"></i> Receipt History
      </a>
      <a href="receivedpayment_verification.php<?= $nav_extra ?>" class="btn btn-outline-info btn-nav-custom">
        <i class="fa-solid fa-shield-halved"></i> Verification
      </a>
      <a href="paymentadvicemanual.php<?= $nav_extra ?>" class="btn btn-primary btn-nav-custom text-white">
        <i class="fa-solid fa-circle-plus"></i> New Payment Advice
      </a>
    </div>
  </div>

  <!-- KPI Metrics Row -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
      <div class="kpi-card d-flex align-items-center justify-content-between">
        <div>
          <span class="text-muted small fw-semibold text-uppercase">Total Collection</span>
          <h4 class="font-heading fw-bold text-primary mb-0 mt-1">₹<?= number_format($total_collected_amount, 2) ?></h4>
          <span class="badge bg-primary-subtle text-primary mt-1" style="font-size: 0.75rem;">
            <?= number_format($total_transactions_count) ?> Transactions
          </span>
        </div>
        <div class="kpi-icon-box bg-primary-subtle text-primary">
          <i class="fa-solid fa-sack-dollar"></i>
        </div>
      </div>
    </div>

    <div class="col-6 col-lg-3">
      <div class="kpi-card d-flex align-items-center justify-content-between">
        <div>
          <span class="text-muted small fw-semibold text-uppercase">UPI / Digital</span>
          <h4 class="font-heading fw-bold text-success mb-0 mt-1">₹<?= number_format($upi_total_amount, 2) ?></h4>
          <span class="text-muted small">
            <?= $total_collected_amount > 0 ? round(($upi_total_amount / $total_collected_amount) * 100, 1) : 0 ?>% of total
          </span>
        </div>
        <div class="kpi-icon-box bg-success-subtle text-success">
          <i class="fa-solid fa-mobile-screen-button"></i>
        </div>
      </div>
    </div>

    <div class="col-6 col-lg-3">
      <div class="kpi-card d-flex align-items-center justify-content-between">
        <div>
          <span class="text-muted small fw-semibold text-uppercase">Cash Receipts</span>
          <h4 class="font-heading fw-bold text-warning mb-0 mt-1">₹<?= number_format($cash_total_amount, 2) ?></h4>
          <span class="text-muted small">
            <?= $total_collected_amount > 0 ? round(($cash_total_amount / $total_collected_amount) * 100, 1) : 0 ?>% of total
          </span>
        </div>
        <div class="kpi-icon-box bg-warning-subtle text-warning">
          <i class="fa-solid fa-money-bill-wave"></i>
        </div>
      </div>
    </div>

    <div class="col-6 col-lg-3">
      <div class="kpi-card d-flex align-items-center justify-content-between">
        <div>
          <span class="text-muted small fw-semibold text-uppercase">Top Collector</span>
          <h5 class="font-heading fw-bold text-dark text-truncate mb-0 mt-1" style="max-width: 140px;" title="<?= htmlspecialchars($top_user) ?>">
            <?= htmlspecialchars($top_user) ?>
          </h5>
          <span class="text-success small fw-semibold">₹<?= number_format($top_user_amt, 2) ?></span>
        </div>
        <div class="kpi-icon-box bg-info-subtle text-info">
          <i class="fa-solid fa-user-check"></i>
        </div>
      </div>
    </div>
  </div>

  <!-- Filter Section -->
  <div class="filter-panel mb-4 no-print">
    <form method="GET" id="filterSummaryForm" class="row g-3 align-items-end">
      <?php if (!empty($key)): ?><input type="hidden" name="k" value="<?= htmlspecialchars($key) ?>"><?php endif; ?>
      <?php if (!empty($mdid)): ?><input type="hidden" name="mdid" value="<?= htmlspecialchars($mdid) ?>"><?php endif; ?>
      <?php if (!empty($dz)): ?><input type="hidden" name="dz" value="<?= htmlspecialchars($dz) ?>"><?php endif; ?>

      <div class="col-12 col-md-3">
        <label class="form-label fw-bold small text-muted text-uppercase mb-1">
          <i class="fa-regular fa-calendar-days text-primary me-1"></i> Date Range
        </label>
        <select name="daterange_preset" id="daterange_preset" class="form-select fw-semibold" onchange="handlePresetChange(this.value)">
          <option value="today" <?= $daterange_preset === 'today' ? 'selected' : '' ?>>Today</option>
          <option value="yesterday" <?= $daterange_preset === 'yesterday' ? 'selected' : '' ?>>Yesterday</option>
          <option value="last_7_days" <?= $daterange_preset === 'last_7_days' ? 'selected' : '' ?>>Last 7 Days</option>
          <option value="last_15_days" <?= $daterange_preset === 'last_15_days' ? 'selected' : '' ?>>Last 15 Days</option>
          <option value="current_month" <?= $daterange_preset === 'current_month' ? 'selected' : '' ?>>Current Month</option>
          <option value="previous_month" <?= $daterange_preset === 'previous_month' ? 'selected' : '' ?>>Previous Month</option>
          <option value="custom" <?= $daterange_preset === 'custom' ? 'selected' : '' ?>>Custom Range</option>
        </select>
      </div>

      <div class="col-6 col-md-3" id="dateFromGroup">
        <label class="form-label fw-bold small text-muted text-uppercase mb-1">Date From</label>
        <input type="date" name="datefrom" id="datefrom" class="form-control" value="<?= htmlspecialchars($datefrom) ?>">
      </div>

      <div class="col-6 col-md-3" id="dateToGroup">
        <label class="form-label fw-bold small text-muted text-uppercase mb-1">Date To</label>
        <input type="date" name="dateto" id="dateto" class="form-control" value="<?= htmlspecialchars($dateto) ?>">
      </div>

      <div class="col-12 col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary w-100 fw-semibold">
          <i class="fa-solid fa-filter me-1"></i> Apply Filter
        </button>
        <a href="paymentsummary.php<?= $nav_extra ?>" class="btn btn-outline-secondary" title="Reset Filters">
          <i class="fa-solid fa-rotate-left"></i>
        </a>
      </div>
    </form>
  </div>

  <!-- Matrix Summary Card -->
  <div class="summary-card">
    <div class="card-header bg-white px-4 py-3 d-flex justify-content-between align-items-center border-bottom">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid fa-table-cells text-primary"></i>
        <h5 class="font-heading fw-bold mb-0 text-dark">Transaction Type × Update By Matrix</h5>
      </div>
      <div class="small text-muted">
        Showing <span class="fw-bold text-dark"><?= count($types_list) ?></span> Payment Types & <span class="fw-bold text-dark"><?= count($users_list) ?></span> Users
      </div>
    </div>

    <div class="matrix-table-container">
      <table class="table matrix-table" id="matrixTable">
        <thead>
          <tr>
            <th>
              <i class="fa-solid fa-arrow-down-wide-short text-primary me-1"></i> TRANSACTION TYPE
            </th>
            <?php foreach ($users_list as $user): ?>
              <th>
                <i class="fa-regular fa-user text-secondary me-1"></i> <?= htmlspecialchars($user) ?>
              </th>
            <?php endforeach; ?>
            <th>
              <i class="fa-solid fa-calculator text-dark me-1"></i> TOTAL AMOUNT
            </th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($types_list)): ?>
            <tr>
              <td colspan="<?= count($users_list) + 2 ?>" class="text-center py-5 text-muted">
                <i class="fa-regular fa-folder-open fa-3x mb-3 text-secondary opacity-50 d-block"></i>
                <h6 class="fw-bold">No payment transactions found in selected date range.</h6>
                <p class="small mb-0">Try expanding your date filter or selecting another date preset.</p>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($types_list as $type): ?>
              <tr>
                <td>
                  <div class="type-badge">
                    <?php if ($type === 'Cash'): ?>
                      <span class="type-icon-circle bg-warning-subtle text-warning"><i class="fa-solid fa-money-bill-1-wave"></i></span>
                    <?php elseif ($type === 'UPI'): ?>
                      <span class="type-icon-circle bg-success-subtle text-success"><i class="fa-solid fa-qrcode"></i></span>
                    <?php elseif ($type === 'Bank Transfer'): ?>
                      <span class="type-icon-circle bg-info-subtle text-info"><i class="fa-solid fa-building-columns"></i></span>
                    <?php elseif ($type === 'Cheque'): ?>
                      <span class="type-icon-circle bg-secondary-subtle text-secondary"><i class="fa-solid fa-money-check"></i></span>
                    <?php else: ?>
                      <span class="type-icon-circle bg-light text-dark"><i class="fa-solid fa-credit-card"></i></span>
                    <?php endif; ?>
                    <span><?= htmlspecialchars($type) ?></span>
                  </div>
                </td>

                <?php foreach ($users_list as $user): 
                  $cell = $matrix[$type][$user] ?? null;
                  $amt = $cell ? $cell['amount'] : 0.00;
                  $cnt = $cell ? $cell['count'] : 0;
                ?>
                  <td>
                    <?php if ($cnt > 0): ?>
                      <div class="cell-clickable" onclick="showDrillDownModal('<?= htmlspecialchars(addslashes($type)) ?>', '<?= htmlspecialchars(addslashes($user)) ?>')">
                        <span class="cell-amt">₹<?= number_format($amt, 2) ?></span>
                        <span class="cell-count"><i class="fa-regular fa-file-lines me-1"></i><?= $cnt ?> txn<?= $cnt > 1 ? 's' : '' ?></span>
                      </div>
                    <?php else: ?>
                      <span class="text-muted opacity-50">-</span>
                    <?php endif; ?>
                  </td>
                <?php endforeach; ?>

                <!-- Row Total -->
                <?php 
                  $tAmt = $type_totals[$type]['amount'] ?? 0;
                  $tCnt = $type_totals[$type]['count'] ?? 0;
                ?>
                <td>
                  <span class="cell-amt text-primary">₹<?= number_format($tAmt, 2) ?></span>
                  <span class="cell-count text-muted fw-semibold"><?= $tCnt ?> total txn<?= $tCnt > 1 ? 's' : '' ?></span>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>

        <?php if (!empty($types_list)): ?>
          <tfoot>
            <tr>
              <th>
                <i class="fa-solid fa-sigma text-white me-1"></i> GRAND TOTAL
              </th>
              <?php foreach ($users_list as $user): 
                $uAmt = $user_totals[$user]['amount'] ?? 0;
                $uCnt = $user_totals[$user]['count'] ?? 0;
              ?>
                <td>
                  <span class="d-block font-heading">₹<?= number_format($uAmt, 2) ?></span>
                  <span class="small opacity-75 fw-normal"><?= $uCnt ?> txns</span>
                </td>
              <?php endforeach; ?>
              
              <!-- Total Overall -->
              <td>
                <span class="d-block font-heading text-white">₹<?= number_format($total_collected_amount, 2) ?></span>
                <span class="small text-white opacity-90 fw-normal"><?= $total_transactions_count ?> Total Txns</span>
              </td>
            </tr>
          </tfoot>
        <?php endif; ?>
      </table>
    </div>
  </div>

</div>

<!-- Drill Down Details Modal -->
<div class="modal fade" id="drillDownModal" tabindex="-1" aria-labelledby="drillDownModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light">
        <div>
          <h5 class="modal-title font-heading fw-bold" id="drillDownModalLabel">Transaction Breakdown</h5>
          <p class="text-muted small mb-0" id="drillDownSubtitle"></p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <div class="table-responsive">
          <table class="table table-hover table-striped align-middle mb-0" id="drillDownTable">
            <thead class="table-light">
              <tr>
                <th class="ps-3">DATE</th>
                <th>CLIENT NAME</th>
                <th>ORDER / PSO</th>
                <th>UTR / REF</th>
                <th>STATUS</th>
                <th class="text-end pe-3">AMOUNT</th>
              </tr>
            </thead>
            <tbody id="drillDownTableBody">
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer bg-light d-flex justify-content-between">
        <div class="fw-bold" id="drillDownModalTotal"></div>
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Embedded JS Data for Drilldown & Filtering -->
<script>
  const cellRecords = <?= json_encode($cell_records) ?>;

  function handlePresetChange(preset) {
    const today = new Date();
    const formatDate = (d) => {
      const year = d.getFullYear();
      const month = String(d.getMonth() + 1).padStart(2, '0');
      const day = String(d.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    };

    let fromStr = formatDate(today);
    let toStr = formatDate(today);

    if (preset === 'today') {
      fromStr = formatDate(today);
      toStr = formatDate(today);
    } else if (preset === 'yesterday') {
      const y = new Date();
      y.setDate(today.getDate() - 1);
      fromStr = formatDate(y);
      toStr = formatDate(y);
    } else if (preset === 'last_7_days') {
      const d = new Date();
      d.setDate(today.getDate() - 6);
      fromStr = formatDate(d);
      toStr = formatDate(today);
    } else if (preset === 'last_15_days') {
      const d = new Date();
      d.setDate(today.getDate() - 14);
      fromStr = formatDate(d);
      toStr = formatDate(today);
    } else if (preset === 'current_month') {
      const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
      const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
      fromStr = formatDate(firstDay);
      toStr = formatDate(lastDay);
    } else if (preset === 'previous_month') {
      const firstDay = new Date(today.getFullYear(), today.getMonth() - 1, 1);
      const lastDay = new Date(today.getFullYear(), today.getMonth(), 0);
      fromStr = formatDate(firstDay);
      toStr = formatDate(lastDay);
    }

    if (preset !== 'custom') {
      document.getElementById('datefrom').value = fromStr;
      document.getElementById('dateto').value = toStr;
    }
  }

  function showDrillDownModal(type, user) {
    const list = (cellRecords[type] && cellRecords[type][user]) ? cellRecords[type][user] : [];
    document.getElementById('drillDownModalLabel').innerText = `${type} — ${user}`;
    document.getElementById('drillDownSubtitle').innerText = `${list.length} transaction(s) recorded in selected date range`;

    const tbody = document.getElementById('drillDownTableBody');
    tbody.innerHTML = '';

    let total = 0;
    if (list.length === 0) {
      tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No transaction entries found.</td></tr>';
    } else {
      list.forEach(item => {
        total += parseFloat(item.amount);
        const tr = document.createElement('tr');
        
        let statusBadge = item.verification === 'verified' 
          ? '<span class="badge bg-success-subtle text-success border border-success"><i class="fa-solid fa-check me-1"></i>Verified</span>'
          : '<span class="badge bg-warning-subtle text-warning border border-warning"><i class="fa-solid fa-clock me-1"></i>Unverified</span>';

        tr.innerHTML = `
          <td class="ps-3 fw-semibold">${item.date}</td>
          <td>${item.client}</td>
          <td><span class="badge bg-light text-dark border">${item.order}</span></td>
          <td><code class="text-muted">${item.utr}</code></td>
          <td>${statusBadge}</td>
          <td class="text-end pe-3 fw-bold text-dark">₹${parseFloat(item.amount).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
        `;
        tbody.appendChild(tr);
      });
    }

    document.getElementById('drillDownModalTotal').innerText = `Total: ₹${total.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;

    const modal = new bootstrap.Modal(document.getElementById('drillDownModal'));
    modal.show();
  }

  function exportMatrixToCSV() {
    const table = document.getElementById('matrixTable');
    let csv = [];
    
    // Headers
    const headers = [];
    table.querySelectorAll('thead th').forEach(th => {
      headers.push(`"${th.innerText.replace(/(\r\n|\n|\r)/gm, ' ').trim()}"`);
    });
    csv.push(headers.join(','));

    // Rows
    table.querySelectorAll('tbody tr').forEach(tr => {
      const row = [];
      tr.querySelectorAll('td').forEach(td => {
        let text = td.innerText.replace(/(\r\n|\n|\r)/gm, ' ').replace('₹', '').replace(/,/g, '').trim();
        row.push(`"${text}"`);
      });
      if (row.length > 0) csv.push(row.join(','));
    });

    // Footer
    const foot = [];
    table.querySelectorAll('tfoot th, tfoot td').forEach(td => {
      let text = td.innerText.replace(/(\r\n|\n|\r)/gm, ' ').replace('₹', '').replace(/,/g, '').trim();
      foot.push(`"${text}"`);
    });
    if (foot.length > 0) csv.push(foot.join(','));

    // Download
    const blob = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.setAttribute('download', `Payment_Summary_${document.getElementById('datefrom').value}_to_${document.getElementById('dateto').value}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }
</script>

<?php if ($use_standalone_layout): ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php endif; ?>
