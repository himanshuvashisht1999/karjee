<?php
$psodate = "";
$clientname = "";
$mobileno = "";
$emailid = "";
$deliverydate = "";
$psoamount = "";

if (isset($_GET['dz'])) {
  require('../connection.php');
  require('../functions/function_master.php');
  include('functions/vendor_functions.php');
  include('functions/product_functions.php');
  include('functions/client_functions.php');
  $key = $_GET['k'];
  $key_decoded = base64url_decode($key);
  $key_parts = explode("|", $key_decoded);
  $checkkey = base64url_decode($key_parts[count($key_parts) - 1]);
  $actkey = "";
  for ($i = 0; $i < count($key_parts) - 1; $i++) {
    $actkey = $actkey ? $actkey . "|" . $key_parts[$i] : $key_parts[$i];
  }
  if ($actkey != $checkkey) {
    echo "<div class=\"col-md-12 text-center black\"><h3>OOPS!! Something went wrong. Please try again.</h3></div>";
    die;
  }
  $loginname = "";
  $loginid = $key_parts[0];
  $loginroleid = $key_parts[1];
  $companyid = $key_parts[2];
  $subcompanyid = $key_parts[3];
  $projectid = $key_parts[4];
  $logintype = $key_parts[5];
  $newkey = "x|x|" . $key_parts[2] . "|" . $key_parts[3] . "|" . $key_parts[4] . "|x";
  $encodednewkey = base64url_encode($newkey);
  $keycompany = base64url_encode($newkey . "|" . $encodednewkey);
  $cmpinfo = getcompanydetails($mconn, "companyid", $companyid);
  $companyname = "";
  if (count($cmpinfo) > 0) {
    foreach ($cmpinfo as $cmphkey) {
      $companyname = $cmphkey['company_name'];
      $countryid = $cmphkey['countryid'];
    }
  }

  $subcmpinfo = getsubcompanydetails($mconn, $companyid, $subcompanyid, "subcompanyid", $subcompanyid);
  $subcompanyname = "";
  if (count($subcmpinfo) > 0) {
    foreach ($subcmpinfo as $subcmphkey) {
      $subcompanyname = $subcmphkey['subcompanyname'];
    }
  }

  // Project Info
  $prj = getprojectdetails($conn, $companyid, $subcompanyid, "projectid", $projectid);
  $projectname = "";
  if (count($prj) > 0) {
    foreach ($prj as $pjkey) {
      $projectname = $pjkey['projectname'];
    }
  }

  $protocol = $_SERVER['PROTOCOL'] = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';
  $url = basename($_SERVER["PHP_SELF"]);
  $rooturl = $protocol . '://' . $_SERVER['HTTP_HOST'];
  $baseurl = $rooturl . str_replace($url, "", $_SERVER["PHP_SELF"]);
  $countryid = "";
  if ($_SERVER['HTTP_HOST'] == "localhost") {
    $prefix = $rooturl . "/krf_amanaccounting/";
  } else {
    $prefix = $rooturl . "/";
  }
  $rooturl = $prefix;
} else {
  require('../header.php');
  include('functions/orders_functions.php');
  include('functions/product_functions.php');
  include('functions/client_functions.php');
  if (!isset($rooturl) || empty($rooturl)) {
    $protocol = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';
    $rooturl = $protocol . '://' . $_SERVER['HTTP_HOST'] . ($_SERVER['HTTP_HOST'] == "localhost" ? "/krf_amanaccounting/" : "/");
  }
}

$mdid = $_GET['mdid'] ?? "";
$groupid = "";
if (isset($authconn) && $authconn && !empty($mdid)) {
  $dddr = loginmodulesdetails_frommaster($authconn, $companyid ?? '', $subcompanyid ?? '', $projectid ?? '', "moduleid", $mdid);
  if (count($dddr) > 0) {
    $groupid = $dddr[0]['groupid'];
  }
}

// Fetch Initial Stats
$dbConn = isset($krfconn) && $krfconn ? $krfconn : $conn;
$totalClients = 0;
$traderCount = 0;
$dealerCount = 0;
$farmerCount = 0;
$totalOrdersVal = 0;
$stateList = [];

$statQ = $dbConn->query("SELECT 
    COUNT(*) as total_clients,
    SUM(CASE WHEN is_trader = 1 OR is_trader = '1' THEN 1 ELSE 0 END) as total_traders,
    SUM(CASE WHEN is_dealer = 1 OR is_dealer = '1' THEN 1 ELSE 0 END) as total_dealers,
    SUM(CASE WHEN (IFNULL(is_trader,0)=0 AND IFNULL(is_dealer,0)=0) THEN 1 ELSE 0 END) as total_farmers
    FROM krf_client_master WHERE (tedit IS NULL OR tedit = '' OR tedit = '0')");

if ($statQ && $sRow = $statQ->fetch_assoc()) {
    $totalClients = (int)$sRow['total_clients'];
    $traderCount = (int)$sRow['total_traders'];
    $dealerCount = (int)$sRow['total_dealers'];
    $farmerCount = (int)$sRow['total_farmers'];
}

$soValQ = $dbConn->query("SELECT SUM(IFNULL(grand_total, total_amount)) as total_val FROM krf_sales_order");
if ($soValQ && $soRow = $soValQ->fetch_assoc()) {
    $totalOrdersVal = (float)$soRow['total_val'];
}

$statesQ = $dbConn->query("SELECT DISTINCT statename FROM krf_client_master WHERE IFNULL(statename,'') != '' AND (tedit IS NULL OR tedit = '' OR tedit = '0') ORDER BY statename ASC");
if ($statesQ) {
    while($st = $statesQ->fetch_assoc()) {
        $stateList[] = $st['statename'];
    }
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<style>
  :root {
    --client-primary: #3b82f6;
    --client-primary-dark: #1d4ed8;
    --client-accent: #6366f1;
    --client-bg: #f8fafc;
  }

  body {
    background-color: #f1f5f9;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  }

  .client-container {
    padding: 1.5rem;
    max-width: 98%;
    margin: 0 auto;
  }

  .client-card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
    background: #ffffff;
    overflow: hidden;
    margin-bottom: 2rem;
  }

  .client-card-header {
    background: linear-gradient(135deg, #1e40af 0%, #3b82f6 50%, #60a5fa 100%);
    color: #ffffff;
    padding: 1.5rem 1.75rem;
    border-bottom: none;
  }

  .stat-chip {
    background: rgba(255, 255, 255, 0.18);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: #ffffff;
    border-radius: 20px;
    padding: 6px 16px;
    font-size: 0.85rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
  }

  .stat-chip:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
  }

  .search-box-wrapper {
    position: relative;
    min-width: 260px;
    flex-grow: 1;
    max-width: 360px;
  }

  .search-box-wrapper .form-control {
    padding-left: 38px !important;
    padding-right: 30px !important;
    padding-top: 5px !important;
    padding-bottom: 5px !important;
    border-radius: 20px !important;
    border: 1px solid #cbd5e1 !important;
    font-size: 0.82rem !important;
    height: 36px !important;
    box-shadow: inset 0 1px 2px rgba(0,0,0,0.03);
    transition: all 0.2s ease-in-out;
  }

  .search-box-wrapper .form-control:focus {
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25) !important;
    background-color: #ffffff !important;
  }

  .search-box-wrapper .search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #64748b;
    font-size: 0.85rem;
    pointer-events: none;
    z-index: 5;
    line-height: 1;
  }

  .search-box-wrapper .clear-icon {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    cursor: pointer;
    display: none;
    font-size: 0.85rem;
    z-index: 5;
    transition: color 0.2s ease;
  }

  .search-box-wrapper .clear-icon:hover {
    color: #334155;
  }

  .toolbar-select {
    height: 36px !important;
    border-radius: 20px !important;
    font-size: 0.82rem !important;
    font-weight: 600 !important;
    padding: 4px 28px 4px 12px !important;
    border: 1px solid #cbd5e1 !important;
    background-color: #ffffff !important;
    cursor: pointer;
  }

  .toolbar-btn {
    height: 36px !important;
    border-radius: 20px !important;
    font-size: 0.82rem !important;
    font-weight: 600 !important;
    padding: 5px 14px !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    white-space: nowrap;
  }

  .table-responsive-wrapper {
    border-radius: 12px;
    overflow-x: auto;
    max-height: calc(100vh - 260px);
    border: 1px solid #e2e8f0;
    position: relative;
    background: #ffffff;
  }

  .custom-client-table {
    width: 100%;
    margin-bottom: 0;
    border-collapse: separate;
    border-spacing: 0;
    min-width: 960px;
  }

  .custom-client-table thead th {
    position: sticky;
    top: 0;
    background: #f8fafc;
    color: #475569;
    font-weight: 700;
    font-size: 0.74rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 0.75rem 0.8rem;
    border-bottom: 2px solid #cbd5e1;
    z-index: 10;
    white-space: nowrap;
  }

  .custom-client-table tbody td {
    padding: 0.65rem 0.8rem;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.82rem;
    color: #334155;
  }

  .custom-client-table tbody tr:hover {
    background-color: #f8fafc;
  }

  .avatar-circle {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.82rem;
    color: #ffffff;
    background: linear-gradient(135deg, #3b82f6, #6366f1);
    box-shadow: 0 2px 5px rgba(59, 130, 246, 0.25);
    flex-shrink: 0;
  }

  .badge-trader {
    background-color: #fef3c7;
    color: #d97706;
    border: 1px solid #fde68a;
    font-weight: 600;
    font-size: 0.72rem;
    padding: 3px 8px;
  }

  .badge-dealer {
    background-color: #ede9fe;
    color: #7c3aed;
    border: 1px solid #ddd6fe;
    font-weight: 600;
    font-size: 0.72rem;
    padding: 3px 8px;
  }

  .badge-farmer {
    background-color: #ecfdf5;
    color: #059669;
    border: 1px solid #a7f3d0;
    font-weight: 600;
    font-size: 0.72rem;
    padding: 3px 8px;
  }

  .badge-id {
    background-color: #e0f2fe;
    color: #0369a1;
    font-family: monospace;
    font-size: 0.8rem;
    padding: 3px 7px;
    border-radius: 6px;
    border: 1px solid #bae6fd;
    font-weight: 700;
  }

  .action-btn {
    width: 30px;
    height: 30px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: none;
    transition: all 0.2s;
    font-size: 0.8rem;
    margin: 0 2px;
    text-decoration: none;
  }

  .action-btn-view {
    background: #eff6ff;
    color: #2563eb;
  }
  .action-btn-view:hover {
    background: #2563eb;
    color: #ffffff;
  }

  .action-btn-edit {
    background: #fef9c3;
    color: #a16207;
  }
  .action-btn-edit:hover {
    background: #eab308;
    color: #ffffff;
  }

  .action-btn-del {
    background: #fee2e2;
    color: #dc2626;
  }
  .action-btn-del:hover {
    background: #dc2626;
    color: #ffffff;
  }

  .action-btn-wa {
    background: #dcfce7;
    color: #16a34a;
  }
  .action-btn-wa:hover {
    background: #16a34a;
    color: #ffffff;
  }

  .modal-header-custom {
    background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
    color: #ffffff;
    border-top-left-radius: calc(0.5rem - 1px);
    border-top-right-radius: calc(0.5rem - 1px);
    padding: 1rem 1.25rem;
  }

  .nav-tabs-custom .nav-link {
    color: #64748b;
    font-weight: 600;
    font-size: 0.84rem;
    border: none;
    border-bottom: 3px solid transparent;
    padding: 0.6rem 1rem;
    transition: all 0.2s ease;
  }

  .nav-tabs-custom .nav-link.active {
    color: #2563eb;
    border-bottom-color: #2563eb;
    background: transparent;
  }

  .info-group-title {
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #94a3b8;
    font-weight: 700;
    margin-bottom: 0.6rem;
    border-bottom: 1px solid #f1f5f9;
    padding-bottom: 0.3rem;
  }

  .detail-label {
    font-size: 0.78rem;
    color: #64748b;
    font-weight: 600;
    margin-bottom: 0.15rem;
  }

  .detail-val {
    font-size: 0.88rem;
    color: #1e293b;
    font-weight: 600;
    margin-bottom: 0.75rem;
  }

  .modal-content .form-label {
    display: block !important;
    width: 100% !important;
    margin-bottom: 0.35rem !important;
    font-weight: 600 !important;
    font-size: 0.8rem !important;
    color: #334155 !important;
    float: none !important;
    clear: both !important;
  }

  .modal-content .form-control,
  .modal-content .form-select {
    width: 100% !important;
    min-width: 100% !important;
    max-width: 100% !important;
    display: block !important;
    padding: 0.45rem 0.75rem !important;
    font-size: 0.85rem !important;
    line-height: 1.5 !important;
    min-height: 38px !important;
    border-radius: 6px !important;
    border: 1px solid #cbd5e1 !important;
    box-sizing: border-box !important;
    background-color: #ffffff !important;
  }

  .modal-content .form-control:focus,
  .modal-content .form-select:focus {
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2) !important;
  }

  .slab-table {
    width: 100%;
    margin-bottom: 0;
  }

  .slab-table thead th {
    font-size: 0.76rem !important;
    font-weight: 700 !important;
    letter-spacing: 0.03em;
    padding: 9px 8px !important;
    background-color: #f1f5f9 !important;
    color: #334155 !important;
    vertical-align: middle !important;
    text-transform: uppercase;
  }

  .slab-table tbody td {
    padding: 6px 6px !important;
    vertical-align: middle !important;
    background-color: #ffffff;
  }

  .slab-table .form-control,
  .slab-table .form-select {
    height: 38px !important;
    min-height: 38px !important;
    max-height: 38px !important;
    line-height: 1.5 !important;
    font-size: 0.85rem !important;
    padding: 6px 10px !important;
    border-radius: 6px !important;
    border: 1px solid #cbd5e1 !important;
    box-sizing: border-box !important;
    color: #1e293b !important;
    background-color: #ffffff !important;
  }

  .slab-table .form-select {
    padding-right: 26px !important;
    background-position: right 8px center !important;
    cursor: pointer !important;
  }

  .slab-table .btn-delete-tier {
    width: 38px !important;
    height: 38px !important;
    min-height: 38px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    border-radius: 6px !important;
    padding: 0 !important;
    color: #dc2626 !important;
    border: 1px solid #fca5a5 !important;
    background-color: #fef2f2 !important;
    transition: all 0.2s ease;
  }

  .slab-table .btn-delete-tier:hover {
    background-color: #dc2626 !important;
    color: #ffffff !important;
    border-color: #dc2626 !important;
  }

  .modal-content textarea.form-control {
    padding: 0.45rem 0.65rem !important;
  }

  .custom-checkbox-label {
    display: inline-flex !important;
    align-items: center !important;
    margin: 0 !important;
    padding: 0 !important;
    cursor: pointer !important;
    user-select: none !important;
    font-weight: 600 !important;
    font-size: 0.85rem !important;
    color: #1e293b !important;
    position: static !important;
  }

  .custom-checkbox-input {
    position: static !important;
    margin: 0 8px 0 0 !important;
    padding: 0 !important;
    width: 15px !important;
    height: 15px !important;
    min-width: 15px !important;
    max-width: 15px !important;
    min-height: 15px !important;
    max-height: 15px !important;
    transform: none !important;
    cursor: pointer !important;
    accent-color: #3b82f6 !important;
    flex-shrink: 0 !important;
    vertical-align: middle !important;
  }

  @media print {
    .no-print, .client-card-header .btn, .search-box-wrapper, .action-btn {
      display: none !important;
    }
    .table-responsive-wrapper {
      max-height: none !important;
      overflow: visible !important;
    }
  }
</style>

<div class="client-container">
  <!-- Main Card -->
  <div class="client-card">
    <div class="client-card-header">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
          <div class="d-flex align-items-center gap-2 mb-1">
            <i class="fas fa-users-viewfinder fs-3"></i>
            <h4 class="fw-bold mb-0 text-white">Client Master Directory</h4>
          </div>
          <p class="mb-0 text-white-50 small">Manage, view, and inspect 360° farmer, trader, and dealer client profiles & orders</p>
        </div>

        <!-- Quick KPI Stats Chips -->
        <div class="d-flex flex-wrap align-items-center gap-2">
          <div class="stat-chip" title="Total Registered Clients">
            <i class="fas fa-address-book"></i>
            <span>Total Clients: <strong id="statTotalClients"><?php echo $totalClients; ?></strong></span>
          </div>
          <div class="stat-chip" title="Registered Farmers">
            <i class="fas fa-user-tag"></i>
            <span>Farmers: <strong id="statFarmers"><?php echo $farmerCount; ?></strong></span>
          </div>
          <div class="stat-chip" title="Wholesalers & Traders">
            <i class="fas fa-store"></i>
            <span>Traders: <strong id="statTraders"><?php echo $traderCount; ?></strong></span>
          </div>
          <div class="stat-chip" title="Dealers">
            <i class="fas fa-handshake"></i>
            <span>Dealers: <strong id="statDealers"><?php echo $dealerCount; ?></strong></span>
          </div>
        </div>
      </div>

      <!-- Controls Toolbar -->
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-4 pt-3 border-top border-white border-opacity-25">
        <div class="d-flex flex-wrap align-items-center gap-2 flex-grow-1">
          <!-- Search Box -->
          <div class="search-box-wrapper">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="clientSearchInput" class="form-control" placeholder="Search by name, ID, phone, district, GST..." oninput="filterClients()">
            <i class="fas fa-times clear-icon" id="clearSearchBtn" onclick="clearSearch()"></i>
          </div>

          <!-- Type Filter -->
          <select id="typeFilter" class="form-select toolbar-select" style="width: 140px;" onchange="filterClients()">
            <option value="">All Types</option>
            <option value="farmer">🌾 Farmer / Direct</option>
            <option value="trader">🏢 Trader</option>
            <option value="dealer">🤝 Dealer</option>
          </select>

          <!-- State Filter -->
          <select id="stateFilter" class="form-select toolbar-select" style="width: 160px;" onchange="filterClients()">
            <option value="">All States</option>
            <?php foreach($stateList as $stName): ?>
              <option value="<?php echo htmlspecialchars($stName); ?>"><?php echo htmlspecialchars($stName); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <button class="btn btn-light fw-bold text-primary shadow-sm toolbar-btn" onclick="openAddClientModal()">
            <i class="fas fa-user-plus text-primary"></i> Add Client
          </button>
          <button class="btn btn-outline-light fw-bold shadow-sm toolbar-btn" onclick="exportClientExcel()">
            <i class="fas fa-file-excel text-success"></i> Export
          </button>
          <button class="btn btn-outline-light fw-bold shadow-sm toolbar-btn" onclick="window.print()">
            <i class="fas fa-print"></i> Print
          </button>
          <button class="btn btn-outline-light fw-bold shadow-sm toolbar-btn" onclick="loadClientData()" title="Refresh List">
            <i class="fas fa-sync-alt"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- Table Body Card -->
    <div class="p-3">
      <div class="d-flex justify-content-between align-items-center mb-2 px-1 text-muted small">
        <div>Showing <strong id="showingClientCount">0</strong> client records</div>
        <div id="filterIndicator" class="text-primary fw-semibold d-none">Filters active</div>
      </div>

      <div class="table-responsive-wrapper">
        <table class="custom-client-table" id="clientTable">
          <thead>
            <tr>
              <th style="width: 45px;" class="text-center">#</th>
              <th style="width: 95px;">Client ID</th>
              <th style="min-width: 220px;">Client Name & Type</th>
              <th style="min-width: 180px;">Contact (Phone / Email)</th>
              <th style="min-width: 170px;">Location (State / District)</th>
              <th style="min-width: 120px;">GST / VAT</th>
              <th style="min-width: 150px;">Bank Account</th>
              <th style="min-width: 105px;" class="text-center">Orders</th>
              <th style="min-width: 135px;" class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody id="clientTableBody">
            <tr>
              <td colspan="9" class="text-center py-5 text-muted">
                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Loading clients data...
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: View 360° Client Profile -->
<!-- ========================================================================= -->
<div class="modal fade" id="viewClientModal" tabindex="-1" aria-labelledby="viewClientModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
      <div class="modal-header-custom d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
          <div class="avatar-circle" id="viewAvatar" style="width: 46px; height: 46px; font-size: 1.2rem;">C</div>
          <div>
            <h5 class="modal-title fw-bold mb-0 text-white" id="viewClientName">Client Name</h5>
            <span class="badge bg-white text-primary fw-bold font-monospace mt-1" id="viewClientId">C00000</span>
            <span id="viewClientTypeBadge" class="ms-1"></span>
          </div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-light rounded-circle btn-close-modal" data-bs-dismiss="modal" data-dismiss="modal" onclick="$('#viewClientModal').modal('hide')" aria-label="Close">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <div class="modal-body p-0">
        <!-- Profile Navigation Tabs -->
        <ul class="nav nav-tabs nav-tabs-custom px-4 pt-2 bg-light border-bottom" role="tablist">
          <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-toggle="tab" data-bs-target="#tab-overview" href="#tab-overview" type="button">
              <i class="fas fa-id-card me-1 text-primary"></i> General Profile & Address
            </button>
          </li>
          <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-toggle="tab" data-bs-target="#tab-finance" href="#tab-finance" type="button">
              <i class="fas fa-building-columns me-1 text-success"></i> Bank & Contact Persons
            </button>
          </li>
          <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-toggle="tab" data-bs-target="#tab-discounts" href="#tab-discounts" type="button">
              <i class="fas fa-percent me-1 text-danger"></i> Plant Discount Policy
            </button>
          </li>
          <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-toggle="tab" data-bs-target="#tab-orders" href="#tab-orders" type="button">
              <i class="fas fa-shopping-basket me-1 text-warning"></i> Order History (<span id="viewOrderCountBadge">0</span>)
            </button>
          </li>
        </ul>

        <div class="tab-content p-4">
          <!-- Tab 1: General Overview -->
          <div class="tab-pane fade show active" id="tab-overview">
            <div class="row">
              <div class="col-md-6 border-end">
                <div class="info-group-title"><i class="fas fa-user-circle me-1"></i> Basic Identity</div>
                <div class="row">
                  <div class="col-sm-6">
                    <div class="detail-label">Client Name</div>
                    <div class="detail-val" id="v_client_name">-</div>
                  </div>
                  <div class="col-sm-6">
                    <div class="detail-label">Client Code / ID</div>
                    <div class="detail-val font-monospace text-primary" id="v_client_id">-</div>
                  </div>
                  <div class="col-sm-6">
                    <div class="detail-label">Primary Mobile / Phone</div>
                    <div class="detail-val" id="v_phone">-</div>
                  </div>
                  <div class="col-sm-6">
                    <div class="detail-label">Email Address</div>
                    <div class="detail-val" id="v_email">-</div>
                  </div>
                  <div class="col-sm-6">
                    <div class="detail-label">GST / VAT Number</div>
                    <div class="detail-val font-monospace" id="v_vat">-</div>
                  </div>
                  <div class="col-sm-6">
                    <div class="detail-label">Client Category</div>
                    <div class="detail-val" id="v_type">-</div>
                  </div>
                </div>
              </div>

              <div class="col-md-6 ps-md-4">
                <div class="info-group-title"><i class="fas fa-location-dot me-1"></i> Address & Geographic Details</div>
                <div class="row">
                  <div class="col-12">
                    <div class="detail-label">Invoicing / Billing Address</div>
                    <div class="detail-val" id="v_invoicing_address">-</div>
                  </div>
                  <div class="col-sm-6">
                    <div class="detail-label">State</div>
                    <div class="detail-val" id="v_statename">-</div>
                  </div>
                  <div class="col-sm-6">
                    <div class="detail-label">District</div>
                    <div class="detail-val" id="v_districtname">-</div>
                  </div>
                  <div class="col-sm-6">
                    <div class="detail-label">Tahseel / Block</div>
                    <div class="detail-val" id="v_tahseel">-</div>
                  </div>
                  <div class="col-sm-6">
                    <div class="detail-label">City / Village</div>
                    <div class="detail-val" id="v_cityname">-</div>
                  </div>
                  <div class="col-12">
                    <div class="detail-label">Company Website</div>
                    <div class="detail-val" id="v_website">-</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Tab 2: Bank & Contact Persons -->
          <div class="tab-pane fade" id="tab-finance">
            <div class="row">
              <div class="col-md-6 border-end">
                <div class="info-group-title"><i class="fas fa-building-columns me-1"></i> Bank Account Information</div>
                <div class="row">
                  <div class="col-sm-6">
                    <div class="detail-label">Bank Name</div>
                    <div class="detail-val text-primary" id="v_bankname">-</div>
                  </div>
                  <div class="col-sm-6">
                    <div class="detail-label">Account Number</div>
                    <div class="detail-val font-monospace" id="v_accountno">-</div>
                  </div>
                  <div class="col-sm-6">
                    <div class="detail-label">IFSC Code</div>
                    <div class="detail-val font-monospace" id="v_ifsccode">-</div>
                  </div>
                  <div class="col-sm-6">
                    <div class="detail-label">Account Holder Name</div>
                    <div class="detail-val" id="v_account_name">-</div>
                  </div>
                </div>
              </div>

              <div class="col-md-6 ps-md-4">
                <div class="info-group-title"><i class="fas fa-user-tie me-1"></i> Authorized Contact Persons</div>
                <div class="p-3 bg-light rounded-3 mb-3 border">
                  <h6 class="fw-bold text-dark mb-2 super-small text-uppercase"><i class="fas fa-file-invoice me-1 text-primary"></i> For Purchase Order (PO) Queries</h6>
                  <div class="row">
                    <div class="col-sm-4">
                      <div class="detail-label">Name</div>
                      <div class="detail-val mb-0" id="v_name_forpo">-</div>
                    </div>
                    <div class="col-sm-4">
                      <div class="detail-label">Contact</div>
                      <div class="detail-val mb-0" id="v_contact_forpo">-</div>
                    </div>
                    <div class="col-sm-4">
                      <div class="detail-label">Email</div>
                      <div class="detail-val mb-0" id="v_email_forpo">-</div>
                    </div>
                  </div>
                </div>

                <div class="p-3 bg-light rounded-3 border">
                  <h6 class="fw-bold text-dark mb-2 super-small text-uppercase"><i class="fas fa-money-check-dollar me-1 text-success"></i> For Accounts & Payment Queries</h6>
                  <div class="row">
                    <div class="col-sm-6">
                      <div class="detail-label">Name</div>
                      <div class="detail-val mb-0" id="v_name_foraccpay">-</div>
                    </div>
                    <div class="col-sm-6">
                      <div class="detail-label">Email</div>
                      <div class="detail-val mb-0" id="v_email_foraccpay">-</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Tab 3: Plant Discount Policies -->
          <div class="tab-pane fade" id="tab-discounts">
            <div class="row g-3">
              <!-- Non-Grafting Display Card (Full Width Individual Row) -->
              <div class="col-12">
                <div class="card border rounded-3 h-100 shadow-none">
                  <div class="card-header bg-success-subtle py-2 px-3 d-flex align-items-center justify-content-between">
                    <strong class="text-success small"><i class="fas fa-seedling me-1"></i> Non-Grafting Plants Discount</strong>
                    <span id="v_ng_status_badge"></span>
                  </div>
                  <div class="card-body p-3" id="v_ng_content">
                    <!-- Populated dynamically via JS -->
                  </div>
                </div>
              </div>

              <!-- Grafting Display Card (Full Width Individual Row) -->
              <div class="col-12">
                <div class="card border rounded-3 h-100 shadow-none">
                  <div class="card-header bg-primary-subtle py-2 px-3 d-flex align-items-center justify-content-between">
                    <strong class="text-primary small"><i class="fas fa-tree me-1"></i> Grafting Plants Discount</strong>
                    <span id="v_g_status_badge"></span>
                  </div>
                  <div class="card-body p-3" id="v_g_content">
                    <!-- Populated dynamically via JS -->
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Tab 4: Order History -->
          <div class="tab-pane fade" id="tab-orders">
            <div class="info-group-title d-flex justify-content-between align-items-center">
              <span><i class="fas fa-receipt me-1"></i> Confirmed Sales Orders</span>
              <span class="badge bg-primary rounded-pill" id="v_so_count">0 Orders</span>
            </div>
            <div class="table-responsive mb-4">
              <table class="table table-bordered table-sm align-middle text-center small">
                <thead class="table-light">
                  <tr>
                    <th>Order No (SO)</th>
                    <th>Ref PSO</th>
                    <th>Booking Date</th>
                    <th>Amount (₹)</th>
                    <th>Status</th>
                    <th>Delivery Address</th>
                  </tr>
                </thead>
                <tbody id="v_so_table_body">
                  <tr><td colspan="6" class="text-muted py-3">No sales orders found for this client.</td></tr>
                </tbody>
              </table>
            </div>

            <div class="info-group-title d-flex justify-content-between align-items-center">
              <span><i class="fas fa-clock me-1"></i> Provisional Orders (PSO)</span>
              <span class="badge bg-info rounded-pill" id="v_pso_count">0 Orders</span>
            </div>
            <div class="table-responsive">
              <table class="table table-bordered table-sm align-middle text-center small">
                <thead class="table-light">
                  <tr>
                    <th>PSO No</th>
                    <th>Booking Date</th>
                    <th>Expected Delivery</th>
                    <th>Status</th>
                    <th>Requirement Notes</th>
                  </tr>
                </thead>
                <tbody id="v_pso_table_body">
                  <tr><td colspan="5" class="text-muted py-3">No provisional orders found.</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer bg-light p-3">
        <button type="button" class="btn btn-outline-secondary px-4 fw-bold btn-close-modal" data-bs-dismiss="modal" data-dismiss="modal" onclick="$('#viewClientModal').modal('hide')">Close</button>
        <button type="button" class="btn btn-primary px-4 fw-bold" id="btnEditFromView" onclick="">
          <i class="fas fa-edit me-1"></i> Edit Client Profile
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: Add / Edit Client Master -->
<!-- ========================================================================= -->
<div class="modal fade" id="clientFormModal" tabindex="-1" aria-labelledby="clientFormModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
      <div class="modal-header-custom d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
          <i class="fas fa-user-pen fs-4"></i>
          <h5 class="modal-title fw-bold mb-0 text-white" id="clientFormModalLabel">Add New Client</h5>
        </div>
        <button type="button" class="btn btn-sm btn-outline-light rounded-circle btn-close-modal" data-bs-dismiss="modal" data-dismiss="modal" onclick="$('#clientFormModal').modal('hide')" aria-label="Close">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <form id="clientForm" onsubmit="saveClientForm(event)">
        <div class="modal-body p-4">
          <input type="hidden" id="f_client_id" name="client_id" value="">
          <input type="hidden" id="f_projectid" name="projectid" value="<?php echo $_SESSION['projectid'] ?? 17; ?>">
          <input type="hidden" id="f_companyid" name="companyid" value="<?php echo $_SESSION['companyid'] ?? 19; ?>">
          <input type="hidden" id="f_subcompanyid" name="subcompanyid" value="<?php echo $_SESSION['subcompanyid'] ?? 12; ?>">

          <!-- Client Types Checkbox -->
          <div class="p-3 bg-light rounded-3 mb-3 border d-flex flex-wrap align-items-center justify-content-between gap-3">
            <span class="fw-bold text-dark"><i class="fas fa-tags text-primary me-1"></i> Client Category Type:</span>
            <div class="d-flex align-items-center" style="gap: 28px;">
              <label class="custom-checkbox-label" for="f_is_trader" style="display: inline-flex !important; align-items: center !important; cursor: pointer; margin: 0; user-select: none;">
                <input type="checkbox" id="f_is_trader" name="is_trader" value="1" class="custom-checkbox-input" style="width: 15px !important; height: 15px !important; margin: 0 8px 0 0 !important; cursor: pointer; accent-color: #3b82f6 !important; transform: none !important; flex-shrink: 0;">
                <span class="fw-semibold text-dark" style="font-size: 0.86rem;">Trader / Wholesaler</span>
              </label>
              <label class="custom-checkbox-label" for="f_is_dealer" style="display: inline-flex !important; align-items: center !important; cursor: pointer; margin: 0; user-select: none;">
                <input type="checkbox" id="f_is_dealer" name="is_dealer" value="1" class="custom-checkbox-input" style="width: 15px !important; height: 15px !important; margin: 0 8px 0 0 !important; cursor: pointer; accent-color: #3b82f6 !important; transform: none !important; flex-shrink: 0;">
                <span class="fw-semibold text-dark" style="font-size: 0.86rem;">Authorized Dealer</span>
              </label>
            </div>
          </div>

          <!-- Basic Info -->
          <div class="info-group-title"><i class="fas fa-user me-1"></i> 1. Basic Details</div>
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold d-block">Client Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control w-100" id="f_client_name" name="client_name" placeholder="Enter Full Client Name" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold d-block">Mobile / Phone <span class="text-danger">*</span></label>
              <input type="tel" class="form-control w-100" id="f_phone" name="phone" placeholder="e.g. 9876543210" maxlength="15" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold d-block">Email Address</label>
              <input type="email" class="form-control w-100" id="f_email" name="email" placeholder="client@example.com">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold d-block">GSTIN / VAT Number</label>
              <input type="text" class="form-control font-monospace w-100" id="f_vat" name="vat" placeholder="e.g. 22AAAAA0000A1Z5">
            </div>
          </div>

          <!-- Address & Location -->
          <div class="info-group-title"><i class="fas fa-location-dot me-1"></i> 2. Location & Address</div>
          <div class="row g-3 mb-3">
            <div class="col-12">
              <label class="form-label small fw-bold d-block">Invoicing / Billing Address</label>
              <textarea class="form-control w-100" id="f_invoicing_address" name="invoicing_address" rows="2" placeholder="Full Postal / Village Address"></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold d-block">State</label>
              <select class="form-select w-100" id="f_statename" name="statename" onchange="onStateChange(this.value)" style="width: 100% !important; display: block !important;">
                <option value="">Select State</option>
              </select>
              <input type="hidden" id="f_stateid" name="stateid">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold d-block">District</label>
              <select class="form-select w-100" id="f_districtname" name="districtname" style="width: 100% !important; display: block !important;">
                <option value="">Select District</option>
              </select>
              <input type="hidden" id="f_districtid" name="districtid">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold d-block">Tahseel / Block</label>
              <input type="text" class="form-control w-100" id="f_tahseel" name="tahseel" placeholder="Enter Tahseel">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold d-block">City / Village</label>
              <input type="text" class="form-control w-100" id="f_cityname" name="cityname" placeholder="Enter City / Village">
            </div>
          </div>

          <!-- Banking Details -->
          <div class="info-group-title"><i class="fas fa-building-columns me-1"></i> 3. Bank & Financial Details</div>
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold d-block">Bank Name</label>
              <input type="text" class="form-control w-100" id="f_bankname" name="bankname" placeholder="e.g. State Bank of India">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold d-block">Account Number</label>
              <input type="text" class="form-control font-monospace w-100" id="f_accountno" name="accountno" placeholder="Bank Account Number">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold d-block">IFSC Code</label>
              <input type="text" class="form-control font-monospace text-uppercase w-100" id="f_ifsccode" name="ifsccode" placeholder="e.g. SBIN0001234">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold d-block">Account Holder Name</label>
              <input type="text" class="form-control w-100" id="f_account_name" name="account_name" placeholder="Account Name">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold d-block">Company Website</label>
              <input type="url" class="form-control w-100" id="f_comp_website" name="comp_website" placeholder="https://">
            </div>
          </div>

          <!-- Hidden input for serialized Discount JSON data -->
          <input type="hidden" id="f_discountpercent" name="discountpercent" value="">

          <!-- 4. Plant Discount Policies (Non-Grafting & Grafting) -->
          <div class="info-group-title d-flex justify-content-between align-items-center">
            <span><i class="fas fa-percent text-success me-1"></i> 4. Plant Discount Policies (Non-Grafting & Grafting)</span>
            <span class="badge bg-light text-muted border super-small">Slab & Per-Order Pricing</span>
          </div>
          <div class="row g-3 mb-3">
            <!-- Non-Grafting Card (Full Width Individual Row) -->
            <div class="col-12">
              <div class="card border rounded-3 shadow-none bg-light-subtle">
                <div class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                  <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-success-subtle text-success p-1"><i class="fas fa-seedling"></i></span>
                    <strong class="text-dark">🌱 Non-Grafting Plants Discount Policy</strong>
                  </div>
                  <label class="custom-checkbox-label m-0" style="font-size: 0.82rem;">
                    <input type="checkbox" id="ng_enabled" class="custom-checkbox-input" onchange="toggleDiscountSection('ng')"> Enable Policy
                  </label>
                </div>
                <div class="card-body p-3" id="ng_body">
                  <div class="row g-3 mb-3">
                    <div class="col-md-6">
                      <label class="form-label small fw-bold d-block">Applicable Rule</label>
                      <select class="form-select form-select-sm w-100" id="ng_applicable" onchange="onDiscountApplicableChange('ng')">
                        <option value="none" selected>No Discount</option>
                        <option value="everyorder">Every Order</option>
                        <option value="asperplane">As Per Plan (Quantity Slabs)</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label small fw-bold d-block">Calculation Type</label>
                      <select class="form-select form-select-sm w-100" id="ng_type" onchange="onDiscountTypeChange('ng')">
                        <option value="rate" selected>Rate (₹ / Plant)</option>
                        <option value="fixed">Fixed Amount (₹ / Order)</option>
                        <option value="percent">Percentage (%)</option>
                      </select>
                    </div>
                  </div>

                  <!-- Every Order Fields -->
                  <div id="ng_everyorder_block" class="row g-3 mb-2 d-none">
                    <div class="col-md-6">
                      <label class="form-label small fw-bold d-block">Discount Percentage (%)</label>
                      <input type="number" step="0.01" class="form-control form-control-sm w-100" id="ng_percent" placeholder="e.g. 5" value="">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label small fw-bold d-block">Discount Amount / Rate (₹)</label>
                      <input type="number" step="0.01" class="form-control form-control-sm w-100" id="ng_amount" placeholder="e.g. 10" value="">
                    </div>
                  </div>

                  <!-- Quantity Slabs Table (when As Per Plan) -->
                  <div id="ng_slabs_block" class="d-none">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <span class="small fw-bold text-dark text-uppercase"><i class="fas fa-layer-group me-1 text-success"></i> Quantity Slabs / Pricing Tiers</span>
                      <button type="button" class="btn btn-sm btn-outline-success py-1 px-3 fw-semibold" onclick="addSlabRow('ng')">
                        <i class="fas fa-plus me-1"></i> Add Quantity Tier
                      </button>
                    </div>
                    <div class="table-responsive border rounded-2 bg-white shadow-sm">
                      <table class="table table-bordered mb-0 align-middle text-center slab-table">
                        <thead class="table-light">
                          <tr>
                            <th style="width: 18%;">From Qty (Min)</th>
                            <th style="width: 18%;">To Qty (Max)</th>
                            <th style="width: 26%;">Discount Type</th>
                            <th style="width: 16%;">Rate / Amount (₹)</th>
                            <th style="width: 14%;">Discount (%)</th>
                            <th style="width: 8%;">Action</th>
                          </tr>
                        </thead>
                        <tbody id="ng_slabs_tbody">
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Grafting Card (Full Width Individual Row) -->
            <div class="col-12">
              <div class="card border rounded-3 shadow-none bg-light-subtle">
                <div class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                  <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary-subtle text-primary p-1"><i class="fas fa-tree"></i></span>
                    <strong class="text-dark">🌿 Grafting Plants Discount Policy</strong>
                  </div>
                  <label class="custom-checkbox-label m-0" style="font-size: 0.82rem;">
                    <input type="checkbox" id="g_enabled" class="custom-checkbox-input" onchange="toggleDiscountSection('g')"> Enable Policy
                  </label>
                </div>
                <div class="card-body p-3" id="g_body">
                  <div class="row g-3 mb-3">
                    <div class="col-md-6">
                      <label class="form-label small fw-bold d-block">Applicable Rule</label>
                      <select class="form-select form-select-sm w-100" id="g_applicable" onchange="onDiscountApplicableChange('g')">
                        <option value="none" selected>No Discount</option>
                        <option value="everyorder">Every Order</option>
                        <option value="asperplane">As Per Plan (Quantity Slabs)</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label small fw-bold d-block">Calculation Type</label>
                      <select class="form-select form-select-sm w-100" id="g_type" onchange="onDiscountTypeChange('g')">
                        <option value="rate" selected>Rate (₹ / Plant)</option>
                        <option value="fixed">Fixed Amount (₹ / Order)</option>
                        <option value="percent">Percentage (%)</option>
                      </select>
                    </div>
                  </div>

                  <!-- Every Order Fields -->
                  <div id="g_everyorder_block" class="row g-3 mb-2 d-none">
                    <div class="col-md-6">
                      <label class="form-label small fw-bold d-block">Discount Percentage (%)</label>
                      <input type="number" step="0.01" class="form-control form-control-sm w-100" id="g_percent" placeholder="0" value="">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label small fw-bold d-block">Discount Amount / Rate (₹)</label>
                      <input type="number" step="0.01" class="form-control form-control-sm w-100" id="g_amount" placeholder="0.25" value="">
                    </div>
                  </div>

                  <!-- Quantity Slabs Table (when As Per Plan) -->
                  <div id="g_slabs_block">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <span class="small fw-bold text-dark text-uppercase"><i class="fas fa-layer-group me-1 text-primary"></i> Quantity Slabs / Pricing Tiers</span>
                      <button type="button" class="btn btn-sm btn-outline-primary py-1 px-3 fw-semibold" onclick="addSlabRow('g')">
                        <i class="fas fa-plus me-1"></i> Add Quantity Tier
                      </button>
                    </div>
                    <div class="table-responsive border rounded-2 bg-white shadow-sm">
                      <table class="table table-bordered mb-0 align-middle text-center slab-table">
                        <thead class="table-light">
                          <tr>
                            <th style="width: 18%;">From Qty (Min)</th>
                            <th style="width: 18%;">To Qty (Max)</th>
                            <th style="width: 26%;">Discount Type</th>
                            <th style="width: 16%;">Rate / Amount (₹)</th>
                            <th style="width: 14%;">Discount (%)</th>
                            <th style="width: 8%;">Action</th>
                          </tr>
                        </thead>
                        <tbody id="g_slabs_tbody">
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Contact Persons -->
          <div class="info-group-title"><i class="fas fa-address-book me-1"></i> 5. Contact Persons</div>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label small fw-bold d-block">PO Contact Name</label>
              <input type="text" class="form-control w-100" id="f_name_forpo" name="name_forpo" placeholder="Name for PO">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold d-block">PO Contact Mobile</label>
              <input type="text" class="form-control w-100" id="f_contact_forpo" name="contact_forpo" placeholder="Contact for PO">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold d-block">PO Contact Email</label>
              <input type="email" class="form-control w-100" id="f_email_forpo" name="email_forpo" placeholder="Email for PO">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold d-block">Accounts Contact Name</label>
              <input type="text" class="form-control w-100" id="f_name_foraccpay" name="name_foraccpay" placeholder="Name for Accounts">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold d-block">Accounts Contact Email</label>
              <input type="email" class="form-control w-100" id="f_email_foraccpay" name="email_foraccpay" placeholder="Email for Accounts">
            </div>
          </div>
        </div>

        <div class="modal-footer bg-light p-3">
          <button type="button" class="btn btn-secondary px-4 fw-bold btn-close-modal" data-bs-dismiss="modal" data-dismiss="modal" onclick="$('#clientFormModal').modal('hide')">Cancel</button>
          <button type="submit" class="btn btn-primary px-5 fw-bold" id="btnSaveClient">
            <i class="fas fa-save me-1"></i> Save Client Record
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php 
if (file_exists('../footer.php')) {
  include('../footer.php'); 
}
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let globalClientData = [];
let statesMasterCache = [];

$(document).ready(function () {
  loadStatesMaster();
  loadClientData();

  // Universal close handler for modals across Bootstrap 4, Bootstrap 5 and custom environments
  $(document).on('click', '[data-bs-dismiss="modal"], [data-dismiss="modal"], .btn-close-modal', function (e) {
    e.preventDefault();
    let $m = $(this).closest('.modal');
    if ($m.length) {
      $m.modal('hide');
    }
    $('.modal').modal('hide');
    $('body').removeClass('modal-open').css('overflow', '');
    $('.modal-backdrop').remove();
  });

  // Universal tab switching handler
  $(document).on('click', '.nav-tabs-custom .nav-link', function (e) {
    e.preventDefault();
    let target = $(this).attr('data-bs-target') || $(this).attr('data-target') || $(this).attr('href');
    if (target) {
      $(this).closest('.nav-tabs').find('.nav-link').removeClass('active');
      $(this).addClass('active');
      $(this).closest('.modal-content').find('.tab-pane').removeClass('show active');
      $(target).addClass('show active');
    }
  });
});

// Load States Master for dropdowns
function loadStatesMaster() {
  $.post('modal/client_modal.php?action=getStatesFromMaster', function (res) {
    try {
      statesMasterCache = typeof res === 'string' ? JSON.parse(res) : res;
      let opts = '<option value="">Select State</option>';
      statesMasterCache.forEach(st => {
        opts += `<option value="${st.statename}" data-id="${st.stateid}">${st.statename}</option>`;
      });
      $('#f_statename').html(opts);
    } catch(e) { console.error('Failed to parse states', e); }
  });
}

function onStateChange(stateName, selectedDistrict = '') {
  if (!stateName) {
    $('#f_districtname').html('<option value="">Select District</option>');
    $('#f_stateid').val('');
    return;
  }

  let stObj = statesMasterCache.find(s => s.statename === stateName);
  if (stObj) {
    $('#f_stateid').val(stObj.stateid);
  }

  $.post('modal/client_modal.php?action=getDistrictsFromMaster', { state: stateName }, function (res) {
    try {
      let districts = typeof res === 'string' ? JSON.parse(res) : res;
      let opts = '<option value="">Select District</option>';
      districts.forEach(d => {
        let isSel = (selectedDistrict && (d.districtname === selectedDistrict || d.districtid === selectedDistrict)) ? 'selected' : '';
        opts += `<option value="${d.districtname}" data-id="${d.districtid}" ${isSel}>${d.districtname}</option>`;
      });
      $('#f_districtname').html(opts);
    } catch (e) { console.error('Failed to parse districts', e); }
  });
}

// Fetch and render client master list
function loadClientData() {
  $('#clientTableBody').html(`<tr><td colspan="9" class="text-center py-5 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Loading clients data...</td></tr>`);
  
  $.post('modal/client_modal.php?action=getAllClientsList', {
    projectid: '<?php echo $_SESSION['projectid'] ?? 17; ?>'
  }, function (res) {
    try {
      let resp = typeof res === 'string' ? JSON.parse(res) : res;
      if (resp.status === 'success') {
        globalClientData = resp.data || [];
        renderClientTable(globalClientData);
        updateKPIStats(globalClientData);
      } else {
        $('#clientTableBody').html(`<tr><td colspan="9" class="text-center py-4 text-danger"><i class="fas fa-exclamation-triangle me-1"></i> ${resp.message || 'Failed to load client data'}</td></tr>`);
      }
    } catch (e) {
      console.error(e);
      $('#clientTableBody').html(`<tr><td colspan="9" class="text-center py-4 text-danger">Invalid server response</td></tr>`);
    }
  });
}

function updateKPIStats(data) {
  let total = data.length;
  let traders = 0;
  let dealers = 0;
  let farmers = 0;

  data.forEach(c => {
    let isT = parseInt(c.is_trader) === 1;
    let isD = parseInt(c.is_dealer) === 1;
    if (isT) traders++;
    else if (isD) dealers++;
    else farmers++;
  });

  $('#statTotalClients').text(total);
  $('#statTraders').text(traders);
  $('#statDealers').text(dealers);
  $('#statFarmers').text(farmers);
}

function renderClientTable(data) {
  $('#showingClientCount').text(data.length);

  if (data.length === 0) {
    $('#clientTableBody').html(`<tr>
      <td colspan="9" class="text-center py-5 text-muted">
        <i class="fas fa-users-slash fs-1 d-block mb-2 opacity-25 text-primary"></i>
        <h6 class="fw-bold mb-1">No Clients Found</h6>
        <p class="small text-muted mb-0">Try changing your search keywords or filter criteria.</p>
      </td>
    </tr>`);
    return;
  }

  let html = '';
  data.forEach((c, idx) => {
    let initial = (c.client_name || 'C').charAt(0).toUpperCase();
    let isTrader = parseInt(c.is_trader) === 1;
    let isDealer = parseInt(c.is_dealer) === 1;

    let typeBadge = '';
    if (isTrader) {
      typeBadge = `<span class="badge badge-trader rounded-pill"><i class="fas fa-store me-1"></i>Trader</span>`;
    } else if (isDealer) {
      typeBadge = `<span class="badge badge-dealer rounded-pill"><i class="fas fa-handshake me-1"></i>Dealer</span>`;
    } else {
      typeBadge = `<span class="badge badge-farmer rounded-pill"><i class="fas fa-seedling me-1"></i>Farmer</span>`;
    }

    let phoneClean = (c.phones || '').replace(/[^0-9]/g, '');
    let phoneDisplay = c.phones ? `
      <div class="d-flex align-items-center mb-1" style="gap: 5px;">
        <i class="fas fa-phone text-primary opacity-75" style="font-size: 0.72rem; width: 13px;"></i>
        <strong class="text-dark" style="font-size: 0.82rem;">${c.phones}</strong>
      </div>
      ${c.client_email ? `
        <div class="d-flex align-items-center text-muted text-truncate" style="gap: 5px; max-width: 175px; font-size: 0.76rem;" title="${c.client_email}">
          <i class="fas fa-envelope text-info" style="font-size: 0.72rem; width: 13px;"></i>
          <span class="text-truncate">${c.client_email}</span>
        </div>` : ''}
    ` : `<span class="text-muted small">N/A</span>`;

    let locationDisplay = `
      <div class="fw-bold text-dark" style="font-size: 0.82rem;">${c.districtname || c.cityname || '-'}</div>
      <div class="d-flex align-items-center text-muted mt-1" style="gap: 4px; font-size: 0.76rem;">
        <i class="fas fa-map-marker-alt text-danger opacity-75" style="font-size: 0.72rem; width: 11px;"></i>
        <span>${c.statename || 'N/A'}</span>
      </div>
    `;

    let vatDisplay = c.vat ? `<span class="badge bg-light text-dark border font-monospace" style="font-size: 0.76rem; padding: 3px 6px;">${c.vat}</span>` : `<span class="text-muted small">-</span>`;

    let bankDisplay = (c.bankname || c.accountno) ? `
      <div class="fw-bold text-dark text-truncate" style="max-width: 145px; font-size: 0.8rem;" title="${c.bankname || ''}">${c.bankname || 'Bank'}</div>
      <div class="text-muted font-monospace text-truncate" style="max-width: 145px; font-size: 0.75rem;">${c.accountno ? 'A/C: ' + c.accountno : ''}</div>
    ` : `<span class="text-muted small">-</span>`;

    let soCount = parseInt(c.total_so_count) || 0;
    let soAmount = parseFloat(c.total_so_amount) || 0;
    let orderDisplay = `
      <div class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fw-bold" style="font-size: 0.76rem;">${soCount} Orders</div>
      ${soAmount > 0 ? `<div class="text-success fw-bold mt-1" style="font-size: 0.78rem;">₹${soAmount.toLocaleString(undefined, {minimumFractionDigits: 2})}</div>` : ''}
    `;

    let waBtn = phoneClean ? `
      <a href="https://wa.me/91${phoneClean}" target="_blank" class="action-btn action-btn-wa" title="WhatsApp Message">
        <i class="fab fa-whatsapp"></i>
      </a>
    ` : '';

    html += `
      <tr id="row_${c.client_id}">
        <td class="text-center font-monospace text-muted small">${idx + 1}</td>
        <td><span class="badge-id">${c.client_id}</span></td>
        <td>
          <div class="d-flex align-items-center" style="gap: 10px;">
            <div class="avatar-circle">${initial}</div>
            <div style="min-width: 0;">
              <div class="fw-bold text-dark text-truncate" style="max-width: 190px;" title="${c.client_name}">${c.client_name}</div>
              <div class="mt-1">${typeBadge}</div>
            </div>
          </div>
        </td>
        <td>${phoneDisplay}</td>
        <td>${locationDisplay}</td>
        <td>${vatDisplay}</td>
        <td>${bankDisplay}</td>
        <td class="text-center">${orderDisplay}</td>
        <td class="text-center text-nowrap">
          <div class="d-inline-flex align-items-center gap-1">
            <button type="button" class="action-btn action-btn-view" onclick="viewClientDetails('${c.client_id}')" title="View 360° Profile">
              <i class="fas fa-eye"></i>
            </button>
            ${waBtn}
            <button type="button" class="action-btn action-btn-edit" onclick="editClient('${c.client_id}')" title="Edit Client Record">
              <i class="fas fa-pen"></i>
            </button>
            <button type="button" class="action-btn action-btn-del" onclick="deleteClient('${c.client_id}', '${escapeQuotes(c.client_name)}')" title="Remove Client">
              <i class="fas fa-trash-alt"></i>
            </button>
          </div>
        </td>
      </tr>
    `;
  });

  $('#clientTableBody').html(html);
}

function escapeQuotes(str) {
  return (str || '').replace(/'/g, "\\'");
}

function filterClients() {
  let search = ($('#clientSearchInput').val() || '').toLowerCase().trim();
  let type = $('#typeFilter').val();
  let state = $('#stateFilter').val();

  if (search.length > 0) {
    $('#clearSearchBtn').show();
  } else {
    $('#clearSearchBtn').hide();
  }

  let filtered = globalClientData.filter(c => {
    let matchesSearch = true;
    if (search) {
      let combined = [
        c.client_id, c.client_name, c.phones, c.client_email,
        c.invoicing_address, c.districtname, c.statename, c.tahseel, c.cityname, c.vat
      ].filter(Boolean).join(' ').toLowerCase();
      matchesSearch = combined.includes(search);
    }

    let matchesType = true;
    if (type === 'trader') matchesType = (parseInt(c.is_trader) === 1);
    else if (type === 'dealer') matchesType = (parseInt(c.is_dealer) === 1);
    else if (type === 'farmer') matchesType = (parseInt(c.is_trader) !== 1 && parseInt(c.is_dealer) !== 1);

    let matchesState = true;
    if (state) {
      matchesState = (c.statename === state);
    }

    return matchesSearch && matchesType && matchesState;
  });

  if (search || type || state) {
    $('#filterIndicator').removeClass('d-none');
  } else {
    $('#filterIndicator').addClass('d-none');
  }

  renderClientTable(filtered);
}

function clearSearch() {
  $('#clientSearchInput').val('');
  $('#clearSearchBtn').hide();
  filterClients();
}

// -------------------------------------------------------------
// PLANT DISCOUNT CONFIGURATION LOGIC (Non-Grafting & Grafting)
// -------------------------------------------------------------
function toggleDiscountSection(prefix) {
  let isChecked = $(`#${prefix}_enabled`).is(':checked');
  if (isChecked) {
    $(`#${prefix}_body`).removeClass('opacity-50 pe-none');
  } else {
    $(`#${prefix}_body`).addClass('opacity-50 pe-none');
  }
}

function onDiscountApplicableChange(prefix) {
  let val = $(`#${prefix}_applicable`).val();
  if (val === 'everyorder') {
    $(`#${prefix}_everyorder_block`).removeClass('d-none');
    $(`#${prefix}_slabs_block`).addClass('d-none');
    onDiscountTypeChange(prefix);
  } else if (val === 'asperplane') {
    $(`#${prefix}_everyorder_block`).addClass('d-none');
    $(`#${prefix}_slabs_block`).removeClass('d-none');
    if ($(`#${prefix}_slabs_tbody tr`).length === 0) {
      addSlabRow(prefix);
    }
  } else {
    $(`#${prefix}_everyorder_block`).addClass('d-none');
    $(`#${prefix}_slabs_block`).addClass('d-none');
  }
}

function onDiscountTypeChange(prefix) {
  let type = $(`#${prefix}_type`).val();
  if (type === 'percent') {
    $(`#${prefix}_percent`).prop('disabled', false).removeClass('bg-light opacity-75');
    $(`#${prefix}_amount`).prop('disabled', true).addClass('bg-light opacity-75').val('');
  } else {
    // Fixed amount or Rate
    $(`#${prefix}_percent`).prop('disabled', true).addClass('bg-light opacity-75').val('');
    $(`#${prefix}_amount`).prop('disabled', false).removeClass('bg-light opacity-75');
  }
}

function onSlabTypeChange(selectElem) {
  let $tr = $(selectElem).closest('tr');
  let type = $(selectElem).val();
  if (type === 'percent') {
    $tr.find('.slab-percent').prop('disabled', false).removeClass('bg-light opacity-75');
    $tr.find('.slab-amount').prop('disabled', true).addClass('bg-light opacity-75').val('');
  } else {
    $tr.find('.slab-percent').prop('disabled', true).addClass('bg-light opacity-75').val('');
    $tr.find('.slab-amount').prop('disabled', false).removeClass('bg-light opacity-75');
  }
}

function addSlabRow(prefix, slab = null) {
  let minQty = slab ? (slab.min_qty !== undefined ? slab.min_qty : '') : '';
  let maxQty = slab ? (slab.max_qty !== undefined ? slab.max_qty : '') : '';
  let type = slab ? (slab.type || 'rate') : 'rate';
  let amount = slab ? (slab.amount !== undefined && slab.amount !== null && slab.amount !== 0 ? slab.amount : (slab.amount === 0 ? '0' : '')) : '';
  let percent = slab ? (slab.percent !== undefined && slab.percent !== null && slab.percent !== 0 ? slab.percent : (slab.percent === 0 ? '0' : '')) : '';

  let isPercent = (type === 'percent');

  let rowHtml = `
    <tr class="slab-row">
      <td>
        <input type="number" class="form-control slab-min w-100" placeholder="Min Qty" value="${minQty}">
      </td>
      <td>
        <input type="number" class="form-control slab-max w-100" placeholder="Max Qty" value="${maxQty}">
      </td>
      <td>
        <select class="form-select slab-type w-100" onchange="onSlabTypeChange(this)">
          <option value="rate" ${type === 'rate' ? 'selected' : ''}>Rate (₹ / Plant)</option>
          <option value="fixed" ${type === 'fixed' ? 'selected' : ''}>Fixed Amount (₹)</option>
          <option value="percent" ${type === 'percent' ? 'selected' : ''}>Percentage (%)</option>
        </select>
      </td>
      <td>
        <input type="number" step="0.01" class="form-control slab-amount w-100 ${isPercent ? 'bg-light opacity-75' : ''}" placeholder="₹ Amount" value="${isPercent ? '' : amount}" ${isPercent ? 'disabled' : ''}>
      </td>
      <td>
        <input type="number" step="0.01" class="form-control slab-percent w-100 ${!isPercent ? 'bg-light opacity-75' : ''}" placeholder="% Disc" value="${isPercent ? percent : ''}" ${!isPercent ? 'disabled' : ''}>
      </td>
      <td class="text-center">
        <button type="button" class="btn-delete-tier" onclick="removeSlabRow(this)" title="Remove Tier">
          <i class="fas fa-trash-alt"></i>
        </button>
      </td>
    </tr>
  `;
  $(`#${prefix}_slabs_tbody`).append(rowHtml);
}

function removeSlabRow(btn) {
  $(btn).closest('tr').remove();
}

function getDiscountDataJson() {
  let extractData = function(prefix) {
    let enabled = $(`#${prefix}_enabled`).is(':checked') ? 1 : 0;
    let applicable = $(`#${prefix}_applicable`).val();
    let type = $(`#${prefix}_type`).val();
    let pVal = $(`#${prefix}_percent`).val();
    let aVal = $(`#${prefix}_amount`).val();
    let percent = pVal !== '' ? (parseFloat(pVal) || 0) : 0;
    let amount = aVal !== '' ? (parseFloat(aVal) || 0) : 0;

    let slabs = [];
    if (enabled && applicable === 'asperplane') {
      $(`#${prefix}_slabs_tbody tr.slab-row`).each(function() {
        let minQty = $(this).find('.slab-min').val();
        let maxQty = $(this).find('.slab-max').val();
        let slabType = $(this).find('.slab-type').val();
        let sAmt = $(this).find('.slab-amount').val();
        let sPct = $(this).find('.slab-percent').val();
        let slabAmount = sAmt !== '' ? (parseFloat(sAmt) || 0) : 0;
        let slabPercent = sPct !== '' ? (parseFloat(sPct) || 0) : 0;
        if (minQty !== '' || maxQty !== '') {
          slabs.push({
            min_qty: minQty,
            max_qty: maxQty,
            type: slabType,
            amount: slabAmount,
            percent: slabPercent
          });
        }
      });
    }

    return {
      enabled: enabled,
      applicable: enabled ? applicable : 'none',
      type: type,
      percent: percent,
      amount: amount,
      slabs: slabs
    };
  };

  let ng = extractData('ng');
  let g = extractData('g');

  if (!ng.enabled && !g.enabled) {
    return null;
  }

  return {
    nongrafting: ng,
    grafting: g
  };
}

function populateDiscountForm(rawDiscount) {
  $(`#ng_slabs_tbody`).empty();
  $(`#g_slabs_tbody`).empty();

  let data = null;
  if (rawDiscount) {
    if (typeof rawDiscount === 'object') {
      data = rawDiscount;
    } else if (typeof rawDiscount === 'string' && rawDiscount.trim().startsWith('{')) {
      try { data = JSON.parse(rawDiscount); } catch(e) { console.error('Discount JSON parse error', e); }
    }
  }

  // Non-Grafting
  let ng = data && data.nongrafting ? data.nongrafting : null;
  if (ng && (ng.enabled === 1 || ng.enabled === true || (ng.applicable && ng.applicable !== 'none'))) {
    $('#ng_enabled').prop('checked', ng.enabled !== 0 && ng.enabled !== false);
    $('#ng_applicable').val(ng.applicable || 'everyorder');
    $('#ng_type').val(ng.type || 'rate');
    $('#ng_percent').val(ng.percent !== undefined && ng.percent !== null && ng.percent !== 0 ? ng.percent : (ng.percent === 0 ? '0' : ''));
    $('#ng_amount').val(ng.amount !== undefined && ng.amount !== null && ng.amount !== 0 ? ng.amount : (ng.amount === 0 ? '0' : ''));
    if (Array.isArray(ng.slabs) && ng.slabs.length > 0) {
      ng.slabs.forEach(s => addSlabRow('ng', s));
    }
  } else {
    // No saved discount - leave completely empty and unchecked
    $('#ng_enabled').prop('checked', false);
    $('#ng_applicable').val('none');
    $('#ng_type').val('rate');
    $('#ng_percent').val('');
    $('#ng_amount').val('');
  }

  // Grafting
  let g = data && data.grafting ? data.grafting : null;
  if (g && (g.enabled === 1 || g.enabled === true || (g.applicable && g.applicable !== 'none'))) {
    $('#g_enabled').prop('checked', g.enabled !== 0 && g.enabled !== false);
    $('#g_applicable').val(g.applicable || 'asperplane');
    $('#g_type').val(g.type || 'rate');
    $('#g_percent').val(g.percent !== undefined && g.percent !== null && g.percent !== 0 ? g.percent : (g.percent === 0 ? '0' : ''));
    $('#g_amount').val(g.amount !== undefined && g.amount !== null && g.amount !== 0 ? g.amount : (g.amount === 0 ? '0' : ''));
    if (Array.isArray(g.slabs) && g.slabs.length > 0) {
      g.slabs.forEach(s => addSlabRow('g', s));
    }
  } else {
    // No saved discount - leave completely empty and unchecked
    $('#g_enabled').prop('checked', false);
    $('#g_applicable').val('none');
    $('#g_type').val('rate');
    $('#g_percent').val('');
    $('#g_amount').val('');
  }

  toggleDiscountSection('ng');
  onDiscountApplicableChange('ng');
  onDiscountTypeChange('ng');
  toggleDiscountSection('g');
  onDiscountApplicableChange('g');
  onDiscountTypeChange('g');
}

function renderViewDiscountPolicies(rawDiscount) {
  let data = null;
  if (rawDiscount) {
    if (typeof rawDiscount === 'object') {
      data = rawDiscount;
    } else if (typeof rawDiscount === 'string' && rawDiscount.trim().startsWith('{')) {
      try { data = JSON.parse(rawDiscount); } catch(e) {}
    }
  }

  let renderCardContent = function(item, defaultTitle) {
    if (!item || (item.enabled === 0 || item.enabled === false) || item.applicable === 'none') {
      return {
        badge: '<span class="badge bg-secondary">Disabled / None</span>',
        html: '<div class="text-muted text-center py-4"><i class="fas fa-ban opacity-50 me-1"></i> No active discount policy configured.</div>'
      };
    }

    let badge = '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Active Policy</span>';
    let appText = item.applicable === 'asperplane' ? 'As Per Plan (Quantity Slabs)' : (item.applicable === 'everyorder' ? 'Every Order' : 'None');
    let typeText = item.type === 'rate' ? 'Rate (₹ / Plant)' : (item.type === 'percent' ? 'Percentage (%)' : 'Fixed Amount (₹ / Order)');

    let html = `
      <div class="row g-2 mb-3">
        <div class="col-6">
          <div class="detail-label">Applicable Rule</div>
          <div class="fw-bold text-dark small">${appText}</div>
        </div>
        <div class="col-6">
          <div class="detail-label">Calculation Type</div>
          <div class="fw-bold text-dark small">${typeText}</div>
        </div>
      </div>
    `;

    if (item.applicable === 'everyorder') {
      html += `
        <div class="p-3 bg-light rounded-2 border d-flex justify-content-around text-center">
          <div>
            <div class="super-small text-muted">Discount %</div>
            <div class="fw-bold text-primary fs-6">${item.percent || 0}%</div>
          </div>
          <div class="border-end"></div>
          <div>
            <div class="super-small text-muted">Discount Amount / Rate</div>
            <div class="fw-bold text-success fs-6">₹${item.amount || 0}</div>
          </div>
        </div>
      `;
    } else if (item.applicable === 'asperplane') {
      let slabs = item.slabs || [];
      if (slabs.length > 0) {
        html += `
          <div class="table-responsive border rounded-2">
            <table class="table table-sm table-bordered mb-0 text-center" style="font-size: 0.82rem;">
              <thead class="table-light">
                <tr>
                  <th>From Qty (Min)</th>
                  <th>To Qty (Max)</th>
                  <th>Discount Type</th>
                  <th>Rate / Amount (₹)</th>
                  <th>Discount (%)</th>
                </tr>
              </thead>
              <tbody>
        `;
        slabs.forEach(s => {
          let sType = s.type === 'rate' ? 'Rate (₹ / Plant)' : (s.type === 'percent' ? 'Percentage (%)' : 'Fixed Amount (₹)');
          let sVal = `₹${parseFloat(s.amount || 0).toFixed(2)}`;
          let pVal = `${parseFloat(s.percent || 0)}%`;
          html += `
            <tr>
              <td class="font-monospace fw-bold text-primary">${(parseInt(s.min_qty)||0).toLocaleString()}</td>
              <td class="font-monospace fw-bold text-primary">${(parseInt(s.max_qty)||0).toLocaleString()}</td>
              <td><span class="badge bg-light text-dark border">${sType}</span></td>
              <td class="fw-bold text-success">${sVal}</td>
              <td class="fw-bold text-dark">${pVal}</td>
            </tr>
          `;
        });
        html += `
              </tbody>
            </table>
          </div>
        `;
      } else {
        html += `<div class="text-muted small py-3 text-center">No quantity slab tiers configured.</div>`;
      }
    }

    return { badge: badge, html: html };
  };

  let ngRes = renderCardContent(data ? data.nongrafting : null, 'Non-Grafting');
  $('#v_ng_status_badge').html(ngRes.badge);
  $('#v_ng_content').html(ngRes.html);

  let gRes = renderCardContent(data ? data.grafting : null, 'Grafting');
  $('#v_g_status_badge').html(gRes.badge);
  $('#v_g_content').html(gRes.html);
}

// 360° Profile Details Modal
function viewClientDetails(clientId) {
  Swal.fire({
    title: 'Loading Profile...',
    allowOutsideClick: false,
    didOpen: () => { Swal.showLoading(); }
  });

  $.post('modal/client_modal.php?action=getClientDetailsFull', { client_id: clientId }, function (res) {
    Swal.close();
    try {
      let resp = typeof res === 'string' ? JSON.parse(res) : res;
      if (resp.status === 'success') {
        let c = resp.client;
        let initial = (c.client_name || 'C').charAt(0).toUpperCase();

        $('#viewAvatar').text(initial);
        $('#viewClientName').text(c.client_name);
        $('#viewClientId').text(c.client_id);

        let typeBadge = '';
        if (parseInt(c.is_trader) === 1) typeBadge = '<span class="badge bg-warning text-dark"><i class="fas fa-store me-1"></i>Trader</span>';
        else if (parseInt(c.is_dealer) === 1) typeBadge = '<span class="badge bg-info text-white"><i class="fas fa-handshake me-1"></i>Dealer</span>';
        else typeBadge = '<span class="badge bg-success text-white"><i class="fas fa-seedling me-1"></i>Farmer</span>';
        $('#viewClientTypeBadge').html(typeBadge);

        $('#v_client_name').text(c.client_name || '-');
        $('#v_client_id').text(c.client_id || '-');
        $('#v_phone').html(c.phones ? `<a href="tel:${c.phones}" class="text-decoration-none"><i class="fas fa-phone text-primary me-1"></i>${c.phones}</a>` : '-');
        $('#v_email').text(c.client_email || '-');
        $('#v_vat').text(c.vat || 'Unregistered / Not Provided');
        $('#v_type').text(parseInt(c.is_trader) === 1 ? 'Trader' : (parseInt(c.is_dealer) === 1 ? 'Dealer' : 'Direct Farmer'));

        $('#v_invoicing_address').text(c.invoicing_address || '-');
        $('#v_statename').text(c.statename || '-');
        $('#v_districtname').text(c.districtname || '-');
        $('#v_tahseel').text(c.tahseel || '-');
        $('#v_cityname').text(c.cityname || '-');
        $('#v_website').html(c.comp_website ? `<a href="${c.comp_website}" target="_blank">${c.comp_website}</a>` : '-');

        $('#v_bankname').text(c.bankname || '-');
        $('#v_accountno').text(c.accountno || '-');
        $('#v_ifsccode').text(c.ifsccode || '-');
        $('#v_account_name').text(c.account_name || '-');

        $('#v_name_forpo').text(c.name_forpo || '-');
        $('#v_contact_forpo').text(c.contact_forpo || '-');
        $('#v_email_forpo').text(c.email_forpo || '-');

        $('#v_name_foraccpay').text(c.name_foraccpay || '-');
        $('#v_email_foraccpay').text(c.email_foraccpay || '-');

        // Render Plant Discount Policies
        renderViewDiscountPolicies(c.discountpercent);

        // Render Sales Orders
        let soList = resp.sales_orders || [];
        $('#v_so_count').text(`${soList.length} Orders`);
        $('#viewOrderCountBadge').text(soList.length);

        if (soList.length > 0) {
          let soHtml = '';
          soList.forEach(so => {
            let stBadge = (so.status === 'Completed' || so.status === 'Delivered') ? 'success' : (so.status === 'Cancelled' ? 'danger' : 'primary');
            soHtml += `<tr>
              <td class="font-monospace fw-bold text-primary">${so.sono}</td>
              <td class="font-monospace text-muted">${so.psono || '-'}</td>
              <td>${so.order_date || '-'}</td>
              <td class="fw-bold text-dark">₹${(parseFloat(so.grand_total) || 0).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
              <td><span class="badge bg-${stBadge}">${so.status || 'Active'}</span></td>
              <td class="text-truncate small" style="max-width: 200px;">${so.delivery_address || '-'}</td>
            </tr>`;
          });
          $('#v_so_table_body').html(soHtml);
        } else {
          $('#v_so_table_body').html(`<tr><td colspan="6" class="text-muted py-3">No sales orders found for this client.</td></tr>`);
        }

        // Render Provisional Orders
        let psoList = resp.provisional_orders || [];
        $('#v_pso_count').text(`${psoList.length} Orders`);
        if (psoList.length > 0) {
          let psoHtml = '';
          psoList.forEach(p => {
            psoHtml += `<tr>
              <td class="font-monospace fw-bold text-info">${p.psono}</td>
              <td>${p.pso_date || '-'}</td>
              <td>${p.expected_delivery || '-'}</td>
              <td><span class="badge bg-secondary">${p.status || 'Pending'}</span></td>
              <td class="text-truncate small" style="max-width: 200px;">${p.requirementtxt || '-'}</td>
            </tr>`;
          });
          $('#v_pso_table_body').html(psoHtml);
        } else {
          $('#v_pso_table_body').html(`<tr><td colspan="5" class="text-muted py-3">No provisional orders found.</td></tr>`);
        }

        $('#btnEditFromView').attr('onclick', `$('#viewClientModal').modal('hide'); editClient('${c.client_id}');`);
        $('#viewClientModal').modal('show');
      } else {
        Swal.fire('Error', resp.message || 'Failed to load client profile', 'error');
      }
    } catch(e) {
      console.error(e);
      Swal.fire('Error', 'Invalid server response', 'error');
    }
  });
}

function openAddClientModal() {
  $('#clientForm')[0].reset();
  $('#f_client_id').val('');
  $('#f_is_trader').prop('checked', false);
  $('#f_is_dealer').prop('checked', false);
  $('#f_districtname').html('<option value="">Select District</option>');
  populateDiscountForm(null);
  $('#clientFormModalLabel').html('<i class="fas fa-user-plus me-2"></i>Add New Client');
  $('#clientFormModal').modal('show');
}

function editClient(clientId) {
  Swal.fire({
    title: 'Loading Data...',
    allowOutsideClick: false,
    didOpen: () => { Swal.showLoading(); }
  });

  $.post('modal/client_modal.php?action=getClientDetailsFull', { client_id: clientId }, function (res) {
    Swal.close();
    try {
      let resp = typeof res === 'string' ? JSON.parse(res) : res;
      if (resp.status === 'success') {
        let c = resp.client;
        $('#clientForm')[0].reset();

        $('#f_client_id').val(c.client_id);
        $('#f_client_name').val(c.client_name);
        $('#f_phone').val(c.phones);
        $('#f_email').val(c.client_email);
        $('#f_vat').val(c.vat);
        $('#f_invoicing_address').val(c.invoicing_address);
        $('#f_tahseel').val(c.tahseel);
        $('#f_cityname').val(c.cityname);

        $('#f_is_trader').prop('checked', parseInt(c.is_trader) === 1);
        $('#f_is_dealer').prop('checked', parseInt(c.is_dealer) === 1);

        $('#f_statename').val(c.statename);
        $('#f_stateid').val(c.stateid);
        onStateChange(c.statename, c.districtname);

        $('#f_bankname').val(c.bankname);
        $('#f_accountno').val(c.accountno);
        $('#f_ifsccode').val(c.ifsccode);
        $('#f_account_name').val(c.account_name);
        $('#f_comp_website').val(c.comp_website);

        // Populate Plant Discount Configuration
        populateDiscountForm(c.discountpercent);

        $('#f_name_forpo').val(c.name_forpo);
        $('#f_contact_forpo').val(c.contact_forpo);
        $('#f_email_forpo').val(c.email_forpo);

        $('#f_name_foraccpay').val(c.name_foraccpay);
        $('#f_email_foraccpay').val(c.email_foraccpay);

        $('#clientFormModalLabel').html(`<i class="fas fa-user-pen me-2"></i>Edit Client (#${c.client_id})`);
        $('#clientFormModal').modal('show');
      } else {
        Swal.fire('Error', resp.message || 'Client record not found', 'error');
      }
    } catch(e) {
      console.error(e);
      Swal.fire('Error', 'Invalid server response', 'error');
    }
  });
}

function saveClientForm(e) {
  e.preventDefault();

  // Serialize discount rules array to JSON
  let discountData = getDiscountDataJson();
  $('#f_discountpercent').val(discountData ? JSON.stringify(discountData) : '');

  let formData = $('#clientForm').serialize();

  Swal.fire({
    title: 'Saving Client Details...',
    allowOutsideClick: false,
    didOpen: () => { Swal.showLoading(); }
  });

  $.post('modal/client_modal.php?action=saveClientMasterFull', formData, function (res) {
    try {
      let resp = typeof res === 'string' ? JSON.parse(res) : res;
      if (resp.status === 'success') {
        Swal.fire('Saved!', resp.message || 'Client details saved successfully.', 'success');
        $('#clientFormModal').modal('hide');
        loadClientData();
      } else if (resp.status === 'warning') {
        Swal.fire('Notice', resp.message, 'warning');
      } else {
        Swal.fire('Error', resp.message || 'Failed to save client details.', 'error');
      }
    } catch(e) {
      console.error(e);
      Swal.fire('Error', 'Failed to parse response.', 'error');
    }
  });
}

function deleteClient(clientId, clientName) {
  Swal.fire({
    title: 'Remove Client?',
    html: `Are you sure you want to remove client <strong>${clientName}</strong> (#${clientId})?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#64748b',
    confirmButtonText: '<i class="fas fa-trash-alt me-1"></i> Yes, Remove'
  }).then((result) => {
    if (result.isConfirmed) {
      Swal.fire({
        title: 'Removing...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
      });

      $.post('modal/client_modal.php?action=deleteClientMaster', { client_id: clientId }, function (res) {
        try {
          let resp = typeof res === 'string' ? JSON.parse(res) : res;
          if (resp.status === 'success') {
            Swal.fire('Removed!', resp.message, 'success');
            $(`#row_${clientId}`).fadeOut(300, function() {
              $(this).remove();
              globalClientData = globalClientData.filter(c => c.client_id !== clientId);
              $('#showingClientCount').text(globalClientData.length);
              updateKPIStats(globalClientData);
            });
          } else {
            Swal.fire('Error', resp.message, 'error');
          }
        } catch(e) {
          console.error(e);
          Swal.fire('Error', 'Failed to remove client.', 'error');
        }
      });
    }
  });
}

// Export Table to CSV
function exportClientExcel() {
  if (!globalClientData || globalClientData.length === 0) {
    Swal.fire('No Data', 'There are no clients to export.', 'info');
    return;
  }

  let csv = [];
  let headers = ["Client ID", "Client Name", "Client Type", "Phone", "Email", "State", "District", "City", "Tahseel", "Address", "GST / VAT", "Bank Name", "Account No", "IFSC", "PO Contact", "Total SO Count", "Total SO Amount"];
  csv.push(headers.map(h => `"${h}"`).join(","));

  globalClientData.forEach(c => {
    let type = parseInt(c.is_trader) === 1 ? 'Trader' : (parseInt(c.is_dealer) === 1 ? 'Dealer' : 'Farmer');
    let row = [
      c.client_id || '',
      c.client_name || '',
      type,
      c.phones || '',
      c.client_email || '',
      c.statename || '',
      c.districtname || '',
      c.cityname || '',
      c.tahseel || '',
      c.invoicing_address || '',
      c.vat || '',
      c.bankname || '',
      c.accountno || '',
      c.ifsccode || '',
      c.name_forpo || '',
      c.total_so_count || '0',
      c.total_so_amount || '0'
    ];
    csv.push(row.map(val => `"${String(val).replace(/"/g, '""')}"`).join(","));
  });

  let csvFile = new Blob(["\ufeff" + csv.join("\n")], { type: "text/csv;charset=utf-8;" });
  let downloadLink = document.createElement("a");
  let todayStr = new Date().toISOString().slice(0, 10);
  downloadLink.download = `Client_Master_Report_${todayStr}.csv`;
  downloadLink.href = window.URL.createObjectURL(csvFile);
  downloadLink.style.display = "none";
  document.body.appendChild(downloadLink);
  downloadLink.click();
  document.body.removeChild(downloadLink);
}
</script>
