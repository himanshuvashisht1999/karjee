<?php
/**
 * Open Sale Stock Review & Inventory Report
 * Path: /krf-orderandsales/opensale_stock_review.php
 * Fully isolated dedicated module for reviewing Open Sale stocks.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Kolkata');

// Session & Authentication Verification
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

if (isset($_GET['dz'])) {
    require_once(__DIR__ . '/../connection.php');
} else {
    require_once(__DIR__ . '/../header.php');
}

$loginroleid = isset($loginroleid) ? (int)$loginroleid : (int)($_SESSION['loginroleid'] ?? 4);
$loginname   = $_SESSION['loginname'] ?? $_SESSION['username'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Open Sale Stock Complete Report | Karjee Farm</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

  <!-- Google Fonts & Font Awesome -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Module CSS -->
  <link rel="stylesheet" href="css/opensale_stock_review.css?v=<?= time() ?>">
</head>
<body>

<div class="osr-wrapper">

  <!-- Hero Header -->
  <div class="osr-hero">
    <div class="osr-hero-title">
      <h1>
        <i class="fa fa-boxes-stacked" style="color: #38bdf8;"></i>
        Open Sale Stock Review & Inventory Report
      </h1>
      <p>Real-time inventory levels, product varieties, and detailed tray movement tracking across Karjee, Sargawa & Mansoon Open Sale centers</p>
    </div>
    <div class="osr-hero-actions">
      <a href="../opensale_booking/index.php" class="osr-btn osr-btn-success" title="Go to POS Order Booking">
        <i class="fa fa-cash-register"></i> + New POS Sale
      </a>
      <a href="plant_qr_movement.php" class="osr-btn osr-btn-outline" title="Transfer more trays to Open Sale">
        <i class="fa fa-qrcode"></i> QR Tray Movement
      </a>
      <button type="button" class="osr-btn osr-btn-outline" id="btnExportCSV" title="Export report to CSV">
        <i class="fa fa-file-excel"></i> Export CSV
      </button>
      <button type="button" class="osr-btn osr-btn-outline" id="btnRefreshAll" title="Refresh Live Data">
        <i class="fa fa-rotate"></i> Refresh
      </button>
    </div>
  </div>

  <!-- KPI Metric Cards -->
  <div class="osr-kpi-grid">
    <div class="osr-kpi-card">
      <div class="osr-kpi-icon" style="background:#e0e7ff; color:#4338ca;">
        <i class="fa fa-layer-group"></i>
      </div>
      <div class="osr-kpi-meta">
        <div class="osr-kpi-label">Available Trays</div>
        <div class="osr-kpi-val" id="kpiActiveTrays">0</div>
        <div class="osr-kpi-sub">Ready in Open Sale</div>
      </div>
    </div>

    <div class="osr-kpi-card">
      <div class="osr-kpi-icon" style="background:#dcfce7; color:#15803d;">
        <i class="fa fa-seedling"></i>
      </div>
      <div class="osr-kpi-meta">
        <div class="osr-kpi-label">Available Plants</div>
        <div class="osr-kpi-val text-success" id="kpiActivePlants">0</div>
        <div class="osr-kpi-sub">Total healthy plants</div>
      </div>
    </div>

    <div class="osr-kpi-card">
      <div class="osr-kpi-icon" style="background:#e0f2fe; color:#0369a1;">
        <i class="fa fa-tags"></i>
      </div>
      <div class="osr-kpi-meta">
        <div class="osr-kpi-label">Products In Stock</div>
        <div class="osr-kpi-val" id="kpiActiveProducts">0</div>
        <div class="osr-kpi-sub">Distinct plant varieties</div>
      </div>
    </div>

    <div class="osr-kpi-card">
      <div class="osr-kpi-icon" style="background:#f1f5f9; color:#475569;">
        <i class="fa fa-cart-shopping"></i>
      </div>
      <div class="osr-kpi-meta">
        <div class="osr-kpi-label">Total Sold Plants</div>
        <div class="osr-kpi-val" id="kpiSoldPlants">0</div>
        <div class="osr-kpi-sub">Billed & dispatched</div>
      </div>
    </div>
  </div>

  <!-- Quick Center Breakdown Strip -->
  <div class="osr-center-strip">
    <div class="osr-center-title">
      <i class="fa fa-map-location-dot text-primary"></i>
      <strong>Open Sale Centers Breakdown:</strong>
      <span class="text-muted" style="font-size:0.8rem;">(Click any center to filter instantly)</span>
    </div>
    <div class="osr-center-badges">
      <span class="osr-center-pill karjee" data-location="Karjee" title="Filter Karjee Center">
        <i class="fa fa-warehouse"></i> <strong>Open Sale Karjee:</strong> <span id="badgeKarjeeStats">-</span>
      </span>
      <span class="osr-center-pill sargawa" data-location="Sargawa" title="Filter Sargawa Center">
        <i class="fa fa-warehouse"></i> <strong>Open Sale Sargwan:</strong> <span id="badgeSargawaStats">-</span>
      </span>
      <span class="osr-center-pill mansoon" data-location="Mansoon" title="Filter Mansoon Agro Center">
        <i class="fa fa-warehouse"></i> <strong>Open Sale Mansoon Agro:</strong> <span id="badgeMansoonStats">-</span>
      </span>
    </div>
  </div>

  <!-- Filter & Search Panel -->
  <div class="osr-card">
    <div class="osr-card-head">
      <h2><i class="fa fa-filter text-primary"></i> Filter & Search Records</h2>
      <div>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnResetFilters">
          <i class="fa fa-undo mr-1"></i> Reset Filters
        </button>
      </div>
    </div>
    <div class="osr-card-body">
      <div class="osr-filter-grid">
        <!-- Search -->
        <div class="osr-form-group">
          <label><i class="fa fa-search text-muted mr-1"></i> Search Keyword</label>
          <input type="text" id="filterSearch" class="osr-input" placeholder="Search barcode, product, batch, PSO...">
        </div>

        <!-- Center Filter -->
        <div class="osr-form-group">
          <label><i class="fa fa-store text-muted mr-1"></i> Open Sale Center</label>
          <select id="filterLocation" class="osr-select">
            <option value="">-- All Open Sale Centers --</option>
          </select>
        </div>

        <!-- Product Filter -->
        <div class="osr-form-group">
          <label><i class="fa fa-seedling text-muted mr-1"></i> Product Filter</label>
          <select id="filterProduct" class="osr-select">
            <option value="">-- All Products --</option>
          </select>
        </div>

        <!-- Stock Status Filter -->
        <div class="osr-form-group">
          <label><i class="fa fa-toggle-on text-muted mr-1"></i> Stock Status</label>
          <select id="filterStatus" class="osr-select">
            <option value="OpenSale" selected>Available (OpenSale)</option>
            <option value="Sold">Sold / Dispatched</option>
            <option value="All">All Statuses</option>
          </select>
        </div>

        <!-- Date Range -->
        <div class="osr-form-group">
          <label><i class="fa fa-calendar-day text-muted mr-1"></i> Date From</label>
          <input type="date" id="filterDateFrom" class="osr-input">
        </div>
        <div class="osr-form-group">
          <label><i class="fa fa-calendar-day text-muted mr-1"></i> Date To</label>
          <input type="date" id="filterDateTo" class="osr-input">
        </div>
      </div>
    </div>
  </div>

  <!-- Dual-View Tabs Section -->
  <div class="osr-card">
    <div class="osr-nav-tabs">
      <button type="button" class="osr-tab-btn active" data-tab="summary">
        <i class="fa fa-chart-pie"></i> Product Summary
        <span class="osr-tab-count" id="tabSummaryCount">0</span>
      </button>
      <button type="button" class="osr-tab-btn" data-tab="trays">
        <i class="fa fa-table-list"></i> Detailed Tray Register
        <span class="osr-tab-count" id="tabTraysCount">0</span>
      </button>
    </div>

    <!-- PANE 1: PRODUCT SUMMARY VIEW -->
    <div id="paneSummary" class="osr-card-body p-0">
      <div class="osr-table-responsive">
        <table class="osr-table">
          <thead>
            <tr>
              <th class="text-center" style="width: 45px;">#</th>
              <th>Product & Variety Name</th>
              <th>Tray Cavity</th>
              <th class="text-right">Price / Rate</th>
              <th class="text-center">Available Trays</th>
              <th class="text-right">Total Plants</th>
              <th class="text-center">Karjee</th>
              <th class="text-center">Sargwan</th>
              <th class="text-center">Mansoon Agro</th>
              <th class="text-center" style="width: 90px;">Action</th>
            </tr>
          </thead>
          <tbody id="tblGroupedBody">
            <tr>
              <td colspan="10" class="osr-loading">
                <i class="fa fa-spinner fa-spin text-primary"></i> Loading Open Sale Products...
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- PANE 2: DETAILED TRAY REGISTER VIEW -->
    <div id="paneTrays" class="osr-card-body p-0" style="display:none;">
      <div class="osr-table-responsive">
        <table class="osr-table">
          <thead>
            <tr>
              <th class="text-center" style="width: 40px;">#</th>
              <th>Tray Barcode</th>
              <th>Product / Variety</th>
              <th class="text-right">Plants / Capacity</th>
              <th>Open Sale Center</th>
              <th>Origin Polyhouse & Bed</th>
              <th>Stock Order / PSO</th>
              <th class="text-center">Status</th>
              <th>Moved Date & Staff</th>
              <th>Remarks</th>
            </tr>
          </thead>
          <tbody id="tblTraysBody">
            <tr>
              <td colspan="10" class="osr-loading">
                <i class="fa fa-spinner fa-spin text-primary"></i> Loading Detailed Trays...
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<?php 
if (!isset($_GET['dz'])) {
    require_once(__DIR__ . '/../footer.php'); 
}
?>

<!-- Custom Module JS -->
<script src="js/opensale_stock_review.js?v=<?= time() ?>"></script>
</body>
</html>
