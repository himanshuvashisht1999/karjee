<?php
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443 ? "https://" : "http://";
$url = basename($_SERVER["PHP_SELF"]);
$rooturl = $protocol . $_SERVER['HTTP_HOST'];
if ($_SERVER['HTTP_HOST'] == "localhost") {
  $prefix = $rooturl . "/krf_amanaccounting/";
} else {
  $prefix = $rooturl . "/";
}
$rooturl = $prefix;

$loginid = "";
$loginroleid = "";
$companyid = "";
$subcompanyid = "";
$projectid = "";
$loginname = "";

if (isset($_GET['dz'])) {
  require('../connection.php');
  require('../functions/function_master.php');
  include('functions/orders_functions.php');
  $key = $_GET['k'] ?? '';
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
  $loginid = $key_parts[0] ?? '';
  $loginroleid = $key_parts[1] ?? '';
  $companyid = $key_parts[2] ?? '';
  $subcompanyid = $key_parts[3] ?? '';
  $projectid = $key_parts[4] ?? '';

  if (empty($loginname) && !empty($loginid)) {
    $logininfo = loginuserdetailsfrommaster($authconn, $loginid, "loginid", $loginid);
    if (count($logininfo) > 0) {
      foreach ($logininfo as $lgkey) {
        $loginname = $lgkey['loginname'];
        if (empty($loginroleid)) $loginroleid = $lgkey['loginroleid'];
      }
    }
  }
} else {
  // If session or direct inclusion
  if (file_exists('../connection.php')) {
    require('../connection.php');
  } else if (file_exists('../../connection.php')) {
    require('../../connection.php');
  }
  if (file_exists('../header.php')) {
    require('../header.php');
  }
  if (file_exists('functions/orders_functions.php')) {
    include('functions/orders_functions.php');
  }
}

if (empty($loginname)) {
  $loginname = $_SESSION['loginname'] ?? 'Admin User';
}
if (empty($projectid)) {
  $projectid = $_SESSION['projectid'] ?? 17;
}
if (empty($companyid)) {
  $companyid = $_SESSION['companyid'] ?? 19;
}
if (empty($subcompanyid)) {
  $subcompanyid = $_SESSION['subcompanyid'] ?? 12;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Received Payment Verification | Karjee Farm</title>

  <!-- Google Fonts: Inter & Outfit -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- FontAwesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Select2 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <!-- SweetAlert2 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <!-- iziToast CSS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/css/iziToast.min.css" />

  <style>
    :root {
      --primary-color: #3b82f6;
      --primary-dark: #1d4ed8;
      --success-color: #10b981;
      --warning-color: #f59e0b;
      --danger-color: #ef4444;
      --dark-color: #1e293b;
      --card-border-radius: 16px;
      --bg-gradient: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    }

    body {
      font-family: 'Inter', sans-serif;
      background: var(--bg-gradient);
      color: #334155;
      min-height: 100vh;
      padding-bottom: 80px;
    }

    h1, h2, h3, h4, h5, h6, .font-heading {
      font-family: 'Outfit', sans-serif;
    }

    /* Modern Glass Card */
    .glass-card {
      background: rgba(255, 255, 255, 0.92);
      backdrop-filter: blur(12px);
      border: 1px solid rgba(226, 232, 240, 0.8);
      border-radius: var(--card-border-radius);
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
    }

    /* Metric Cards */
    .stat-card {
      border-radius: var(--card-border-radius);
      border: none;
      transition: all 0.3s ease;
      overflow: hidden;
      position: relative;
    }
    .stat-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 15px 30px rgba(0, 0, 0, 0.08);
    }
    .stat-card .icon-bubble {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.4rem;
    }

    /* Payment Card Grid */
    .payment-card {
      border-radius: 14px;
      border: 1px solid #e2e8f0;
      background: #ffffff;
      transition: all 0.25s ease-in-out;
      position: relative;
    }
    .payment-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 24px -6px rgba(0, 0, 0, 0.08);
      border-color: #cbd5e1;
    }
    .payment-card.unverified-card {
      border-left: 5px solid #f59e0b;
    }
    .payment-card.verified-card {
      border-left: 5px solid #10b981;
      background: #fafcfb;
    }

    /* Status Badges */
    .status-badge-unverified {
      background: #fef3c7;
      color: #b45309;
      border: 1px solid #fde68a;
      font-weight: 600;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 0.78rem;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }
    .status-badge-verified {
      background: #d1fae5;
      color: #047857;
      border: 1px solid #a7f3d0;
      font-weight: 600;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 0.78rem;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }

    .chk-wrapper {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 26px;
      height: 26px;
      border-radius: 6px;
      background: #ffffff;
      border: 1px solid #cbd5e1;
      margin-right: 4px;
      flex-shrink: 0;
    }
    .payment-select-chk {
      position: static !important;
      margin: 0 !important;
      width: 16px !important;
      height: 16px !important;
      min-width: 16px !important;
      min-height: 16px !important;
      cursor: pointer !important;
      display: inline-block !important;
      vertical-align: middle !important;
      accent-color: #3b82f6;
      float: none !important;
    }

    .pulse-dot {
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: #f59e0b;
      animation: pulse 1.5s infinite;
    }
    @keyframes pulse {
      0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7); }
      70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(245, 158, 11, 0); }
      100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
    }

    /* Filter Nav Pills */
    .nav-tabs-custom {
      border-bottom: 2px solid #e2e8f0;
      gap: 8px;
    }
    .nav-tabs-custom .nav-link {
      border: none;
      font-weight: 600;
      color: #64748b;
      padding: 10px 18px;
      border-radius: 8px;
      transition: all 0.2s ease;
    }
    .nav-tabs-custom .nav-link:hover {
      background: #f1f5f9;
      color: #1e293b;
    }
    .nav-tabs-custom .nav-link.active {
      color: var(--primary-dark);
      background: #eff6ff;
      border-bottom: 2px solid var(--primary-color);
    }

    /* Thumbnail Receipt */
    .receipt-thumb {
      width: 60px;
      height: 60px;
      object-fit: cover;
      border-radius: 8px;
      border: 1px solid #cbd5e1;
      cursor: pointer;
      transition: transform 0.2s ease;
    }
    .receipt-thumb:hover {
      transform: scale(1.06);
    }
    .pdf-preview-box {
      width: 60px;
      height: 60px;
      border-radius: 8px;
      background: #fee2e2;
      border: 1px solid #fecaca;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      color: #ef4444;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.2s;
    }
    .pdf-preview-box:hover {
      background: #fca5a5;
      color: #b91c1c;
      transform: scale(1.05);
    }

    /* Select2 custom tweak */
    .select2-container .select2-selection--single {
      height: 38px !important;
      border: 1px solid #cbd5e1 !important;
      border-radius: 8px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
      line-height: 36px !important;
      padding-left: 12px !important;
      font-size: 0.9rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
      height: 36px !important;
    }

    .amount-display {
      font-family: 'Outfit', sans-serif;
      font-size: 1.35rem;
      font-weight: 700;
      color: #0f172a;
    }
  
    @media (min-width: 768px) {
      .border-end-md {
        border-right: 1px solid #e2e8f0 !important;
      }
    }

    @media (min-width: 992px) {
      .border-end-lg {
        border-right: 1px solid #e2e8f0 !important;
      }
    }

  </style>
</head>

<body data-projectid="<?php echo $projectid; ?>" data-companyid="<?php echo $companyid; ?>" data-subcompanyid="<?php echo $subcompanyid; ?>" data-loginname="<?php echo htmlspecialchars($loginname); ?>" data-rooturl="<?php echo $rooturl; ?>">

  <div class="container-fluid px-4 py-4 max-w-7xl">
    
    <!-- Top Header & Breadcrumb -->
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 pb-2 border-bottom">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1">
          <span class="badge bg-primary px-2.5 py-1.5"><i class="fa fa-shield-check me-1"></i> VERIFICATION CELL</span>
          <span class="text-muted small">Project ID: #<?php echo $projectid; ?></span>
        </div>
        <h3 class="fw-bold text-dark mb-0">Received Payment Verification</h3>
        <p class="text-secondary small mb-0">Review, verify and manage advance payment receipts logged for provisional sales orders.</p>
      </div>

      <div class="d-flex align-items-center gap-2 mt-2 mt-md-0">
        <button type="button" class="btn btn-outline-secondary btn-sm rounded-3 shadow-sm px-3" onclick="resetFilters()">
          <i class="fa fa-rotate-right me-1"></i> Reset Filters
        </button>
        <button type="button" class="btn btn-primary btn-sm rounded-3 shadow-sm px-3" onclick="loadPayments()">
          <i class="fa fa-sync-alt me-1"></i> Refresh
        </button>
      </div>
    </div>

    <!-- 1. Top Summary Metric: Single Unified Card -->
    <div class="card glass-card shadow-sm border-0 mb-4 overflow-hidden">
      <div class="card-body p-3 p-md-4">
        <div class="row g-3 g-lg-0 align-items-center">
          
          <!-- Metric 1: Total Received -->
          <div class="col-12 col-sm-6 col-lg-3 pe-lg-4 border-end-lg">
            <div class="d-flex align-items-center justify-content-between p-2">
              <div>
                <span class="text-muted small text-uppercase fw-bold ls-1" style="font-size: 0.75rem;">
                  <i class="fa fa-circle text-primary me-1" style="font-size: 0.55rem;"></i> Total Received
                </span>
                <h4 class="amount-display text-primary mb-0 mt-1" id="stat_total_amt">₹0.00</h4>
                <small class="text-secondary fw-semibold" id="stat_total_cnt">0 Transactions</small>
              </div>
              <div class="icon-bubble bg-primary-subtle text-primary ms-2">
                <i class="fa fa-wallet"></i>
              </div>
            </div>
          </div>

          <!-- Metric 2: Pending Verification -->
          <div class="col-12 col-sm-6 col-lg-3 px-lg-4 border-end-lg">
            <div class="d-flex align-items-center justify-content-between p-2">
              <div>
                <span class="text-muted small text-uppercase fw-bold ls-1" style="font-size: 0.75rem;">
                  <i class="fa fa-circle text-warning me-1" style="font-size: 0.55rem;"></i> Pending Verification
                </span>
                <h4 class="amount-display text-warning mb-0 mt-1" id="stat_unverified_amt">₹0.00</h4>
                <small class="text-warning fw-bold" id="stat_unverified_cnt">0 Unverified</small>
              </div>
              <div class="icon-bubble bg-warning-subtle text-warning ms-2">
                <i class="fa fa-hourglass-half"></i>
              </div>
            </div>
          </div>

          <!-- Metric 3: Verified Payments -->
          <div class="col-12 col-sm-6 col-lg-3 px-lg-4 border-end-lg">
            <div class="d-flex align-items-center justify-content-between p-2">
              <div>
                <span class="text-muted small text-uppercase fw-bold ls-1" style="font-size: 0.75rem;">
                  <i class="fa fa-circle text-success me-1" style="font-size: 0.55rem;"></i> Verified Payments
                </span>
                <h4 class="amount-display text-success mb-0 mt-1" id="stat_verified_amt">₹0.00</h4>
                <small class="text-success fw-bold" id="stat_verified_cnt">0 Verified</small>
              </div>
              <div class="icon-bubble bg-success-subtle text-success ms-2">
                <i class="fa fa-check-double"></i>
              </div>
            </div>
          </div>

          <!-- Metric 4: Today's Collection -->
          <div class="col-12 col-sm-6 col-lg-3 ps-lg-4">
            <div class="d-flex align-items-center justify-content-between p-2">
              <div>
                <span class="text-muted small text-uppercase fw-bold ls-1" style="font-size: 0.75rem;">
                  <i class="fa fa-circle text-info me-1" style="font-size: 0.55rem;"></i> Today's Collection
                </span>
                <h4 class="amount-display text-info mb-0 mt-1" id="stat_today_amt">₹0.00</h4>
                <small class="text-secondary fw-semibold" id="stat_today_cnt">0 Today</small>
              </div>
              <div class="icon-bubble bg-info-subtle text-info ms-2">
                <i class="fa fa-calendar-day"></i>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- 2. Filter Bar Box (Before Grid) -->
    <div class="glass-card p-3 p-md-4 mb-4">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
          <i class="fa fa-filter text-primary"></i> Filter Payments
        </h6>
        <div class="btn-group btn-group-sm" role="group">
          <button type="button" class="btn btn-outline-secondary" onclick="setDatePreset('today')">Today</button>
          <button type="button" class="btn btn-outline-secondary" onclick="setDatePreset('week')">This Week</button>
          <button type="button" class="btn btn-outline-secondary" onclick="setDatePreset('month')">This Month</button>
          <button type="button" class="btn btn-outline-secondary active" onclick="setDatePreset('all')">All Time</button>
        </div>
      </div>

      <div class="row g-3">
        <!-- 1. Product Name Filter -->
        <div class="col-12 col-sm-6 col-md-3">
          <label class="form-label small fw-semibold text-secondary mb-1">
            <i class="fa fa-seedling text-success me-1"></i> Product Name
          </label>
          <select id="filter_product" class="form-select select2" style="width: 100%;">
            <option value="">All Products</option>
          </select>
        </div>

        <!-- 2. Client Filter -->
        <div class="col-12 col-sm-6 col-md-3">
          <label class="form-label small fw-semibold text-secondary mb-1">
            <i class="fa fa-user text-primary me-1"></i> Client
          </label>
          <select id="filter_client" class="form-select select2" style="width: 100%;">
            <option value="">All Clients</option>
          </select>
        </div>

        <!-- 3. Provisional Order Date Filter -->
        <div class="col-12 col-sm-6 col-md-2">
          <label class="form-label small fw-semibold text-secondary mb-1">
            <i class="fa fa-file-invoice text-info me-1"></i> Provisional Order Date
          </label>
          <input type="date" id="filter_psodate" class="form-control form-control-sm" style="height: 38px;">
        </div>

        <!-- 4. Date Range: Payment Date From & To -->
        <div class="col-12 col-sm-6 col-md-4">
          <label class="form-label small fw-semibold text-secondary mb-1">
            <i class="fa fa-calendar text-warning me-1"></i> Payment Date Range
          </label>
          <div class="input-group input-group-sm">
            <input type="date" id="filter_date_from" class="form-control" style="height: 38px;" title="From Date">
            <span class="input-group-text bg-light text-muted">to</span>
            <input type="date" id="filter_date_to" class="form-control" style="height: 38px;" title="To Date">
          </div>
        </div>

        <!-- Search Text Box -->
        <div class="col-12 col-md-6">
          <div class="input-group">
            <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa fa-search"></i></span>
            <input type="text" id="filter_search" class="form-control border-start-0 ps-0" placeholder="Search by PSO #, UTR Number, Client Name, Received By, Remarks...">
          </div>
        </div>

        <!-- Search Action Buttons -->
        <div class="col-12 col-md-6 d-flex align-items-center gap-2">
          <button type="button" class="btn btn-primary flex-grow-1 fw-semibold rounded-3 shadow-sm" onclick="loadPayments()" style="height: 38px;">
            <i class="fa fa-search me-1"></i> Apply Filters
          </button>
          <button type="button" class="btn btn-outline-secondary rounded-3" onclick="resetFilters()" style="height: 38px; min-width: 85px;" title="Reset all filters">
            <i class="fa fa-undo me-1"></i> Reset
          </button>
          <button type="button" class="btn btn-success fw-semibold rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-1.5 text-nowrap" onclick="exportPaymentsToExcel()" style="height: 38px; min-width: 140px;" title="Export all payments to Excel with separate columns">
            <i class="fa fa-file-excel"></i> Export Excel
          </button>
        </div>
      </div>
    </div>

    <!-- 3. Tab Switcher & Action Toolbar -->
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-3">
      <ul class="nav nav-tabs nav-tabs-custom mb-0" id="paymentTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="tab-unverified" data-status="unverified" onclick="switchStatusTab('unverified')">
            <i class="fa fa-clock me-1 text-warning"></i> Unverified Payments 
            <span class="badge bg-warning text-dark ms-1" id="badge_tab_unverified">0</span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="tab-verified" data-status="verified" onclick="switchStatusTab('verified')">
            <i class="fa fa-check-circle me-1 text-success"></i> Verified Payments 
            <span class="badge bg-success ms-1" id="badge_tab_verified">0</span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="tab-all" data-status="all" onclick="switchStatusTab('all')">
            <i class="fa fa-layer-group me-1 text-primary"></i> All Transactions
            <span class="badge bg-secondary ms-1" id="badge_tab_all">0</span>
          </button>
        </li>
      </ul>

      <!-- Right Action Group: Batch Toolbar + Excel Export Button -->
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <!-- Batch Verification Toolbar -->
        <div id="batchActionToolbar" class="d-none d-flex align-items-center gap-3 bg-white px-3 py-1.5 rounded-3 shadow-sm border">
          <div class="d-flex align-items-center gap-2">
            <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)" style="width: 17px; height: 17px; cursor: pointer; accent-color: #10b981; margin: 0;">
            <label for="selectAllCheckbox" class="small fw-semibold text-secondary mb-0 user-select-none" style="cursor: pointer;">Select All</label>
          </div>
          <button type="button" class="btn btn-success btn-sm rounded-pill px-3 shadow-sm d-flex align-items-center gap-1.5" onclick="batchVerifySelected()">
            <i class="fa fa-check-double"></i> <span>Verify Selected (<strong id="selectedCount">0</strong>)</span>
          </button>
        </div>

        <button type="button" class="btn btn-outline-success btn-sm rounded-3 shadow-2xs fw-semibold px-3 py-1.5 d-flex align-items-center gap-1.5 bg-white" onclick="exportPaymentsToExcel()" title="Export payments to Excel with separate columns">
          <i class="fa fa-file-excel text-success fs-14"></i> <span>Export Excel</span>
        </button>
      </div>
    </div>

    <!-- 4. Dynamic Cards Container -->
    <div id="loadingSpinner" class="text-center py-5 d-none">
      <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="text-muted mt-2 fw-semibold">Fetching payment transactions...</p>
    </div>

    <div class="row g-3" id="paymentCardsGrid">
      <!-- Dynamic Payment Cards will be injected here via JavaScript -->
    </div>

    <!-- Empty State -->
    <div id="emptyStateContainer" class="glass-card text-center py-5 d-none">
      <div class="text-muted mb-3">
        <i class="fa fa-receipt fa-4x text-secondary opacity-50"></i>
      </div>
      <h5 class="fw-bold text-dark">No Payment Records Found</h5>
      <p class="text-secondary small max-w-md mx-auto">No advance payments match the current filter criteria. Try adjusting the date range or clearing your search filters.</p>
      <button type="button" class="btn btn-outline-primary btn-sm px-4 rounded-3" onclick="resetFilters()">
        <i class="fa fa-undo me-1"></i> Reset Filters
      </button>
    </div>

  </div>

  <!-- Fullscreen / Zoom Receipt Modal -->
  <div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content rounded-4 border-0 shadow-lg">
        <div class="modal-header bg-dark text-white border-0 py-3">
          <h5 class="modal-title font-heading fw-bold" id="receiptModalLabel">
            <i class="fa fa-receipt me-2 text-warning"></i> Payment Receipt Attachment
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-3 text-center bg-light" style="min-height: 400px;">
          <div id="receiptModalContent" class="d-flex align-items-center justify-content-center h-100">
            <!-- Image or PDF Embed injected dynamically -->
          </div>
        </div>
        <div class="modal-footer bg-white border-0 py-2 d-flex justify-content-between">
          <a id="receiptDownloadBtn" href="#" target="_blank" download class="btn btn-outline-primary btn-sm px-3 rounded-3">
            <i class="fa fa-download me-1"></i> Download File
          </a>
          <button type="button" class="btn btn-secondary btn-sm px-4 rounded-3" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Unified Payment Details, Receipt Preview & Verification Modal -->
  <div class="modal fade" id="paymentVerificationModal" tabindex="-1" aria-labelledby="paymentVerificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
        <div class="modal-header text-white border-0 py-3 px-4" style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%) !important;">
          <h5 class="modal-title font-heading fw-bold d-flex align-items-center gap-2" id="paymentVerificationModalLabel">
            <i class="fa fa-shield-alt text-warning"></i> <span>Review & Verify Payment</span>
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        
        <div class="modal-body p-4 bg-light">
          <input type="hidden" id="modal_payment_sno" value="">
          <input type="hidden" id="modal_payment_psono" value="">

          <div class="row g-4">
            <!-- Left Column: Summary & Transaction Details Inputs -->
            <div class="col-12 col-lg-7">

              <!-- Transaction Summary Header Card with explicit padding -->
              <div class="bg-white rounded-3 border mb-4 shadow-sm" style="padding: 16px 20px;">
                <div class="d-flex justify-content-between align-items-center pb-2.5 mb-2 border-bottom">
                  <span class="text-muted small fw-semibold d-flex align-items-center"><i class="fa fa-user text-primary me-2 fs-13"></i>Client:</span>
                  <strong class="text-dark fs-14" id="modal_clientname">-</strong>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2 mb-2 border-bottom">
                  <span class="text-muted small fw-semibold d-flex align-items-center"><i class="fa fa-receipt text-info me-2 fs-13"></i>Provisional Order:</span>
                  <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2.5 py-1" id="modal_orderno">-</span>
                </div>
                <div class="d-flex justify-content-between align-items-center pt-2">
                  <span class="text-muted small fw-semibold d-flex align-items-center"><i class="fa fa-info-circle text-secondary me-2 fs-13"></i>Current Status:</span>
                  <span id="modal_current_status_badge">-</span>
                </div>
              </div>

              <!-- Transaction Fields Grid with clean padding and labels -->
              <div class="row g-3 mb-4">
                <!-- Amount Received -->
                <div class="col-12 col-sm-6">
                  <label class="form-label fw-bold small text-dark mb-1.5">
                    <i class="fa fa-rupee-sign text-success me-1"></i> Amount Received (₹) <span class="text-danger">*</span>
                  </label>
                  <input type="number" step="0.01" id="modal_advanceamount" class="form-control fw-bold text-success" placeholder="0.00">
                </div>

                <!-- Mode of Payment / Transaction Type -->
                <div class="col-12 col-sm-6">
                  <label class="form-label fw-bold small text-dark mb-1.5">
                    <i class="fa fa-money-bill-wave text-success me-1"></i> Mode of Payment <span class="text-danger">*</span>
                  </label>
                  <select id="modal_sourceofpayment" class="form-select font-heading fw-semibold" onchange="handlePaymentModeChange()">
                    <option value="UPI">UPI</option>
                    <option value="Cash">Cash</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="NEFT/RTGS">NEFT / RTGS</option>
                    <option value="Cheque">Cheque</option>
                    <option value="Card">Card / POS</option>
                    <option value="Other">Other</option>
                  </select>
                </div>

                <!-- Txn No / UTR No -->
                <div class="col-12 col-sm-6">
                  <label class="form-label fw-bold small text-dark mb-1.5">
                    <i class="fa fa-hashtag text-primary me-1"></i> Txn. No / UTR No <span class="text-danger" id="modal_utr_required_star">*</span>
                  </label>
                  <input type="text" id="modal_utrnumber" class="form-control font-monospace" placeholder="e.g. 023321814862">
                </div>

                <!-- Transaction Date -->
                <div class="col-12 col-sm-6">
                  <label class="form-label fw-bold small text-dark mb-1.5">
                    <i class="fa fa-calendar-alt text-info me-1"></i> Transaction Date <span class="text-danger">*</span>
                  </label>
                  <input type="date" id="modal_paymentdate" class="form-control">
                </div>
              </div>

              <!-- Upload / Replace Attachment Section -->
              <div class="bg-white rounded-3 border mb-3 shadow-2xs" style="padding: 16px 20px;">
                <label class="form-label fw-bold small text-dark mb-2 d-flex justify-content-between align-items-center">
                  <span><i class="fa fa-cloud-upload-alt text-primary me-2"></i> Replace / Upload Document</span>
                  <span class="text-muted fw-normal small">(Optional if unchanged)</span>
                </label>
                <input type="file" id="modal_receipt_file" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.webp,.pdf" onchange="previewModalReceipt(this)">
                <div class="d-flex justify-content-between align-items-center mt-2">
                  <small class="text-muted" style="font-size: 0.75rem;">JPG, PNG, WEBP, PDF (Max 10MB)</small>
                  <small id="modal_upload_file_name" class="text-primary fw-semibold" style="font-size: 0.75rem;"></small>
                </div>
              </div>

              <!-- Validation Warning Box -->
              <div id="modal_validation_alert" class="alert alert-danger p-2.5 px-3 mt-3 mb-0 small d-none rounded-3 shadow-2xs">
                <div class="d-flex align-items-start gap-2">
                  <i class="fa fa-exclamation-triangle fs-6 mt-0.5 text-danger"></i>
                  <div>
                    <strong>Verification Blocked:</strong>
                    <div id="modal_validation_msg"></div>
                  </div>
                </div>
              </div>

            </div>

            <!-- Right Column: Live Receipt Attachment Preview Screen -->
            <div class="col-12 col-lg-5">
              <div class="card h-100 border rounded-3 bg-white shadow-2xs d-flex flex-column">
                <div class="card-header bg-light py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                  <span class="fw-bold small text-dark">
                    <i class="fa fa-file-invoice text-primary me-1.5"></i> Receipt Document Preview
                  </span>
                  <button type="button" class="btn btn-outline-secondary btn-xs rounded-2 px-2 py-0.5" id="btnReceiptZoom" onclick="zoomCurrentReceipt()" title="Open Fullscreen Preview">
                    <i class="fa fa-expand me-1"></i> Zoom
                  </button>
                </div>
                <div class="card-body p-3 d-flex align-items-center justify-content-center text-center overflow-auto" id="modal_receipt_preview_area" style="min-height: 280px; max-height: 380px; background: #f8fafc;">
                  <!-- Dynamic preview loaded here -->
                </div>
                <div class="card-footer bg-white py-1.5 px-3 border-top text-center" id="modal_receipt_preview_footer">
                  <small class="text-muted" id="modal_receipt_preview_status">-</small>
                </div>
              </div>
            </div>

          </div>
        </div>

        <div class="modal-footer bg-white border-0 py-3 px-4 d-flex flex-wrap align-items-center justify-content-between gap-2">
          <button type="button" class="btn btn-outline-secondary btn-sm px-3.5 rounded-3" data-bs-dismiss="modal">Cancel</button>
          
          <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-primary btn-sm px-3.5 rounded-3 shadow-sm fw-semibold" id="btnModalUpdateOnly" onclick="submitPaymentModal(false)">
              <i class="fa fa-save me-1"></i> Update Details Only
            </button>
            <button type="button" class="btn btn-success btn-sm px-4 rounded-3 shadow-sm fw-semibold" id="btnModalVerify" onclick="submitPaymentModal(true)">
              <i class="fa fa-check-circle me-1"></i> Confirm & Verify
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js"></script>
  <!-- SheetJS / XLSX for Excel Export -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

  <script>
    let currentStatusTab = 'unverified';
    let allPaymentsData = [];
    const projectid = $('body').data('projectid') || 17;
    const loginname = $('body').data('loginname') || 'Admin';
    const rooturl = $('body').data('rooturl') || '';

    $(document).ready(function() {
      // Initialize Select2 dropdowns
      $('.select2').select2({
        placeholder: "Choose an option",
        allowClear: true
      });

      // Fetch filter metadata (products & clients)
      loadFilterMetadata();

      // Initial Payment Load
      loadPayments();

      // Live search typing listener with debounce
      let searchTimeout;
      $("#filter_search").on("keyup input", function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
          loadPayments();
        }, 400);
      });
    });

    // 1. Fetch Products & Clients for Filter Dropdowns
    function loadFilterMetadata() {
      $.ajax({
        url: "modal/payment_verification_modal.php?action=get_filter_data",
        type: "POST",
        data: { projectid: projectid },
        dataType: "json",
        success: function(res) {
          if (res.status === 'success') {
            // Populate Products
            let prodOptions = '<option value="">All Products</option>';
            if (res.products && res.products.length) {
              res.products.forEach(p => {
                prodOptions += `<option value="${p.productid}">${p.productname} ${p.isgrafted ? '(' + p.isgrafted + ')' : ''}</option>`;
              });
            }
            $('#filter_product').html(prodOptions).trigger('change.select2');

            // Populate Clients
            let clientOptions = '<option value="">All Clients</option>';
            if (res.clients && res.clients.length) {
              res.clients.forEach(c => {
                clientOptions += `<option value="${c.clientid}">${c.clientname} ${c.mobileno ? '(' + c.mobileno + ')' : ''}</option>`;
              });
            }
            $('#filter_client').html(clientOptions).trigger('change.select2');
          }
        }
      });
    }

    // 2. Fetch & Render Payment Cards
    function loadPayments() {
      const productid = $('#filter_product').val() || '';
      const clientid = $('#filter_client').val() || '';
      const psodate = $('#filter_psodate').val() || '';
      const date_from = $('#filter_date_from').val() || '';
      const date_to = $('#filter_date_to').val() || '';
      const search = $('#filter_search').val() || '';

      $('#loadingSpinner').removeClass('d-none');
      $('#paymentCardsGrid').empty();
      $('#emptyStateContainer').addClass('d-none');

      $.ajax({
        url: "modal/payment_verification_modal.php?action=fetch_payments",
        type: "POST",
        data: {
          projectid: projectid,
          productid: productid,
          clientid: clientid,
          psodate: psodate,
          date_from: date_from,
          date_to: date_to,
          verification_status: currentStatusTab,
          search: search
        },
        dataType: "json",
        success: function(res) {
          $('#loadingSpinner').addClass('d-none');
          if (res.status === 'success') {
            allPaymentsData = res.data || [];
            updateSummaryStats(res.stats);
            renderPaymentCards(allPaymentsData);
          } else {
            Swal.fire('Error', res.message || 'Failed to fetch payments', 'error');
          }
        },
        error: function() {
          $('#loadingSpinner').addClass('d-none');
          Swal.fire('Error', 'Server communication failure while loading payments.', 'error');
        }
      });
    }

    // 3. Render Metric Cards
    function updateSummaryStats(stats) {
      if (!stats) return;
      $('#stat_total_amt').text(stats.total_amount_formatted);
      $('#stat_total_cnt').text(`${stats.total_count} Transactions`);

      $('#stat_unverified_amt').text(stats.unverified_amount_formatted);
      $('#stat_unverified_cnt').text(`${stats.unverified_count} Pending Verification`);
      $('#badge_tab_unverified').text(stats.unverified_count);

      $('#stat_verified_amt').text(stats.verified_amount_formatted);
      $('#stat_verified_cnt').text(`${stats.verified_count} Verified`);
      $('#badge_tab_verified').text(stats.verified_count);

      $('#stat_today_amt').text(stats.today_amount_formatted);
      $('#stat_today_cnt').text(`${stats.today_count} Received Today`);

      $('#badge_tab_all').text(stats.total_count);

      if (currentStatusTab === 'unverified' && stats.unverified_count > 0) {
        $('#batchActionToolbar').removeClass('d-none');
      } else {
        $('#batchActionToolbar').addClass('d-none');
      }
    }

    // 4. Render Dynamic Cards Grid
    function renderPaymentCards(payments) {
      const $grid = $('#paymentCardsGrid');
      $grid.empty();
      $('#selectAllCheckbox').prop('checked', false);
      updateSelectedCount();

      if (!payments || payments.length === 0) {
        $('#emptyStateContainer').removeClass('d-none');
        return;
      }

      payments.forEach(p => {
        const isVerified = (p.verification || '').toLowerCase() === 'verified';
        const cardClass = isVerified ? 'verified-card' : 'unverified-card';

        // Payment Mode Badge
        let modeBadge = '';
        const src = (p.sourceofpayment || '').toLowerCase();
        if (src.includes('upi')) {
          modeBadge = '<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1"><i class="fa fa-mobile-alt me-1"></i>UPI</span>';
        } else if (src.includes('cash')) {
          modeBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="fa fa-money-bill-wave me-1"></i>Cash</span>';
        } else {
          modeBadge = `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1"><i class="fa fa-university me-1"></i>${p.sourceofpayment || 'Bank'}</span>`;
        }

        // Receipt Thumbnail or Icon & Replace/Upload Action
        let receiptHtml = '';
        if (p.receiptpath && p.receiptpath.trim() !== '') {
          const cleanPath = p.receiptpath.replace(/^(\.\.\/)+/, '');
          const fileExt = cleanPath.split('.').pop().toLowerCase();
          const fullPath = rooturl + cleanPath;

          if (['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(fileExt)) {
            receiptHtml = `
              <div class="position-relative d-inline-block">
                <img src="${fullPath}" class="receipt-thumb shadow-sm" title="Click to view full receipt" onclick="openReceiptModal('${fullPath}', 'image')" onerror="this.onerror=null; this.src='https://placehold.co/60x60/f8fafc/94a3b8?text=Receipt'; this.title='Image not found. Click edit to upload/replace.';" alt="Receipt">
                <button type="button" class="btn btn-warning btn-xs rounded-circle position-absolute bottom-0 end-0 shadow-sm border border-white" style="width: 22px; height: 22px; padding: 0; transform: translate(25%, 25%); font-size: 10px; display: inline-flex; align-items: center; justify-content: center;" title="Edit transaction details & receipt" onclick="openPaymentModal(${p.sno}, false)">
                  <i class="fa fa-pen"></i>
                </button>
              </div>
            `;
          } else if (fileExt === 'pdf') {
            receiptHtml = `
              <div class="position-relative d-inline-block">
                <div class="pdf-preview-box shadow-sm" title="Click to view PDF" onclick="openReceiptModal('${fullPath}', 'pdf')">
                  <i class="fa fa-file-pdf fa-2x"></i>
                  <span style="font-size:0.65rem; font-weight:700;">PDF</span>
                </div>
                <button type="button" class="btn btn-warning btn-xs rounded-circle position-absolute bottom-0 end-0 shadow-sm border border-white" style="width: 22px; height: 22px; padding: 0; transform: translate(25%, 25%); font-size: 10px; display: inline-flex; align-items: center; justify-content: center;" title="Edit transaction details & receipt" onclick="openPaymentModal(${p.sno}, false)">
                  <i class="fa fa-pen"></i>
                </button>
              </div>
            `;
          } else {
            receiptHtml = `
              <div class="position-relative d-inline-block">
                <a href="${fullPath}" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="fa fa-paperclip me-1"></i>File</a>
                <button type="button" class="btn btn-warning btn-xs rounded-circle position-absolute bottom-0 end-0 shadow-sm border border-white" style="width: 22px; height: 22px; padding: 0; transform: translate(25%, 25%); font-size: 10px; display: inline-flex; align-items: center; justify-content: center;" title="Edit transaction details & receipt" onclick="openPaymentModal(${p.sno}, false)">
                  <i class="fa fa-pen"></i>
                </button>
              </div>
            `;
          }
        } else {
          receiptHtml = `
            <button type="button" class="btn btn-outline-primary btn-sm rounded-3 py-1 px-2.5 shadow-2xs d-inline-flex align-items-center gap-1.5" style="font-size: 0.78rem;" title="Upload receipt & edit details" onclick="openPaymentModal(${p.sno}, false)">
              <i class="fa fa-upload text-primary"></i> <span>Upload Receipt</span>
            </button>
          `;
        }

        // Linked Products Badges
        let productsHtml = '';
        if (p.product_names) {
          const pList = p.product_names.split('|');
          productsHtml = pList.map(name => `<span class="badge bg-light text-dark border px-2 py-1 mb-1 me-1 font-monospace" style="font-size: 0.75rem;">${name.trim()}</span>`).join('');
        }

        const html = `
          <div class="col-12">
            <div class="card payment-card ${cardClass} shadow-sm mb-3">
              <div class="card-body p-3.5">
                
                <!-- Top Row: Status, Txn. Type, Txn. No / UTR, Txn. Date, Order Badge -->
                <div class="d-flex flex-wrap align-items-center justify-content-between p-2.5 px-3 mb-3 rounded-3 bg-light border gap-2">
                  <div class="d-flex align-items-center gap-2 flex-wrap">
                    ${!isVerified ? `
                      <div class="chk-wrapper">
                        <input type="checkbox" class="payment-select-chk" value="${p.sno}" onchange="updateSelectedCount()">
                      </div>
                    ` : ''}
                    ${isVerified ? `
                      <span class="status-badge-verified"><i class="fa fa-check-circle"></i> Verified</span>
                    ` : `
                      <span class="status-badge-unverified"><span class="pulse-dot"></span> Unverified</span>
                    `}

                    <!-- Txn. Type -->
                    <span class="badge bg-white text-dark border px-2.5 py-1.5 shadow-2xs" style="font-size: 0.82rem;">
                      <i class="fa fa-money-bill-wave text-success me-1"></i> Txn. Type: <strong>${p.sourceofpayment || 'Cash'}</strong>
                    </span>

                    <!-- Txn. No / UTR No -->
                    <span class="badge bg-white text-dark border px-2.5 py-1.5 shadow-2xs font-monospace" style="font-size: 0.82rem;">
                      <i class="fa fa-hashtag text-primary me-1"></i> Txn. No/UTR No: <strong class="${p.utrnumber ? 'text-primary' : 'text-dark'}">${p.utrnumber || p.paymentref_no || (src.includes('cash') ? 'CASH' : 'N/A')}</strong>
                    </span>

                    <!-- Txn. Date -->
                    <span class="badge bg-white text-dark border px-2.5 py-1.5 shadow-2xs" style="font-size: 0.82rem;">
                      <i class="fa fa-calendar-alt text-info me-1"></i> Txn. Date: <strong>${p.formatted_paymentdate}</strong>
                    </span>
                  </div>

                  <div class="d-flex align-items-center gap-2 flex-wrap">
                    ${p.invoiceno ? `<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1.5 font-monospace fw-bold" style="font-size: 0.84rem;"><i class="fa fa-receipt me-1"></i>Provisional Order: #${p.invoiceno}</span>` : '<span class="badge bg-light text-muted border px-2.5 py-1.5">Direct / General Advance</span>'}
                    ${isVerified && p.verificationby ? `
                      <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5" style="font-size: 0.8rem;">
                        <i class="fa fa-user-check me-1"></i> Verified by <strong>${p.verificationby}</strong> ${p.formatted_verificationat ? `(${p.formatted_verificationat})` : ''}
                      </span>
                    ` : ''}
                  </div>
                </div>

                <!-- Main Content Horizontal Columns -->
                <div class="row g-3 align-items-center">

                  <!-- Col 1: Transaction Amount (Spacious Amount Highlight) -->
                  <div class="col-12 col-md-3 border-end-md pe-md-3">
                    <div class="p-3 rounded-3 bg-light border text-center text-md-start h-100 d-flex flex-column justify-content-center">
                      <span class="text-muted small fw-bold text-uppercase d-block" style="font-size: 0.72rem; letter-spacing: 0.5px;">Amount Received</span>
                      <div class="amount-display text-success my-1" style="font-size: 1.55rem; font-weight: 800;">${p.advanceamount_formatted}</div>
                      <div class="small text-muted" style="font-size: 0.78rem;">
                        <i class="fa fa-check-circle text-success me-1"></i> Advance Payment
                      </div>
                    </div>
                  </div>

                  <!-- Col 2: Client & Collector Information -->
                  <div class="col-12 col-md-3 border-end-md px-md-3">
                    <div>
                      <div class="fw-bold text-dark fs-6 d-flex align-items-center gap-1.5">
                        <i class="fa fa-user-circle text-primary"></i> ${p.clientname}
                      </div>
                      <div class="small text-muted d-flex align-items-center flex-wrap gap-2 mt-1.5">
                        ${p.pso_mobileno ? `<span><i class="fa fa-phone text-secondary me-1"></i>${p.pso_mobileno}</span>` : ''}
                        ${p.pso_districtname ? `<span><i class="fa fa-map-marker-alt text-danger me-1"></i>${p.pso_districtname}${p.pso_statename ? ', ' + p.pso_statename : ''}</span>` : ''}
                      </div>

                      <div class="mt-2.5 pt-2 border-top" style="font-size: 0.82rem;">
                        <div class="text-muted"><i class="fa fa-id-badge text-secondary me-1"></i>Received By: <span class="fw-semibold text-dark">${p.receivedby || 'N/A'} ${p.receivedlocation ? '(' + p.receivedlocation + ')' : ''}</span></div>
                      </div>
                    </div>
                  </div>

                  <!-- Col 3: Provisional Order Summary & Products -->
                  <div class="col-12 col-md-4 border-end-md px-md-3">
                    <div style="font-size: 0.82rem;">
                      <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted"><i class="fa fa-clock text-secondary me-1"></i>Order Date: <strong>${p.formatted_pso_created_at || 'N/A'}</strong></span>
                        ${p.pso_total_amount && parseFloat(p.pso_total_amount) > 0 ? `
                          <span class="text-muted">Order Total: <strong class="text-primary fs-13">${p.pso_total_amount_formatted}</strong></span>
                        ` : ''}
                      </div>

                      <div class="mt-1">
                        <span class="text-muted small d-block mb-1.5"><i class="fa fa-boxes me-1 text-info"></i>Products in Order:</span>
                        <div class="d-flex flex-wrap gap-1">${productsHtml || '<span class="badge bg-light text-muted border">General Advance</span>'}</div>
                      </div>

                      ${p.remarks ? `
                        <div class="alert alert-light border p-1.5 px-2 mb-0 mt-2 small text-secondary fst-italic" style="font-size: 0.78rem;">
                          <i class="fa fa-comment-dots text-warning me-1"></i> "${p.remarks}"
                        </div>
                      ` : ''}
                    </div>
                  </div>

                  <!-- Col 4: Receipt Attachment & Verification Action -->
                  <div class="col-12 col-md-2 ps-md-3 d-flex flex-column align-items-center justify-content-center text-center gap-2.5">
                    <div>
                      ${receiptHtml}
                    </div>

                    <div>
                      ${!isVerified ? `
                        <button type="button" class="btn btn-success btn-sm fw-semibold rounded-3 shadow-sm px-3.5 py-1.5 d-flex align-items-center gap-1.5 text-nowrap" onclick="openPaymentModal(${p.sno}, true)">
                          <i class="fa fa-check"></i> Verify
                        </button>
                      ` : `
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-3 px-3 py-1.5 d-flex align-items-center gap-1 text-nowrap" title="Revert to Unverified" onclick="unverifySinglePayment(${p.sno})">
                          <i class="fa fa-undo"></i> Revert
                        </button>
                      `}
                    </div>
                  </div>

                </div>

              </div>
            </div>
          </div>
        `;
        $grid.append(html);
      });
    }

    // 5. Verification Actions (Single & Batch)
    function unverifySinglePayment(sno) {
      Swal.fire({
        title: 'Revert Verification?',
        text: 'Are you sure you want to revert this payment to Unverified status?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa fa-undo me-1"></i> Yes, Revert'
      }).then((result) => {
        if (result.isConfirmed) {
          $.ajax({
            url: "modal/payment_verification_modal.php?action=unverify_payment",
            type: "POST",
            data: { sno: sno },
            dataType: "json",
            success: function(res) {
              if (res.status === 'success') {
                iziToast.warning({
                  title: 'Reverted!',
                  message: res.message,
                  position: 'topRight'
                });
                loadPayments();
              } else {
                Swal.fire('Error', res.message, 'error');
              }
            }
          });
        }
      });
    }

    function batchVerifySelected() {
      const selectedIds = [];
      $('.payment-select-chk:checked').each(function() {
        selectedIds.push($(this).val());
      });

      if (selectedIds.length === 0) {
        Swal.fire('Selection Required', 'Please select at least one unverified payment card.', 'info');
        return;
      }

      Swal.fire({
        title: `Verify ${selectedIds.length} Payment(s)?`,
        text: `You are about to mark ${selectedIds.length} selected transaction(s) as Verified.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#64748b',
        confirmButtonText: `<i class="fa fa-check-double me-1"></i> Verify All (${selectedIds.length})`
      }).then((result) => {
        if (result.isConfirmed) {
          $.ajax({
            url: "modal/payment_verification_modal.php?action=batch_verify",
            type: "POST",
            data: { snos: selectedIds, loginname: loginname },
            dataType: "json",
            success: function(res) {
              if (res.status === 'success') {
                iziToast.success({
                  title: 'Batch Completed!',
                  message: res.message,
                  position: 'topRight'
                });
                loadPayments();
              } else {
                Swal.fire('Error', res.message, 'error');
              }
            }
          });
        }
      });
    }

    // 6. Selection Helpers
    function toggleSelectAll(masterChk) {
      const isChecked = $(masterChk).is(':checked');
      $('.payment-select-chk').prop('checked', isChecked);
      updateSelectedCount();
    }

    function updateSelectedCount() {
      const cnt = $('.payment-select-chk:checked').length;
      $('#selectedCount').text(cnt);
    }

    // 7. Tab Switching
    function switchStatusTab(status) {
      currentStatusTab = status;
      $('.nav-tabs-custom .nav-link').removeClass('active');
      $(`#tab-${status}`).addClass('active');
      loadPayments();
    }

    // 8. Quick Date Preset Helper
    function setDatePreset(preset) {
      $('.btn-group button').removeClass('active');
      $(event.target).addClass('active');

      const today = new Date();
      const formatDate = (d) => d.toISOString().split('T')[0];

      if (preset === 'today') {
        const todayStr = formatDate(today);
        $('#filter_date_from').val(todayStr);
        $('#filter_date_to').val(todayStr);
      } else if (preset === 'week') {
        const firstDay = new Date(today.setDate(today.getDate() - today.getDay() + 1));
        const lastDay = new Date(today.setDate(today.getDate() - today.getDay() + 7));
        $('#filter_date_from').val(formatDate(firstDay));
        $('#filter_date_to').val(formatDate(lastDay));
      } else if (preset === 'month') {
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        $('#filter_date_from').val(formatDate(firstDay));
        $('#filter_date_to').val(formatDate(lastDay));
      } else {
        // all time
        $('#filter_date_from').val('');
        $('#filter_date_to').val('');
      }
      loadPayments();
    }

    function resetFilters() {
      $('#filter_product').val('').trigger('change.select2');
      $('#filter_client').val('').trigger('change.select2');
      $('#filter_psodate').val('');
      $('#filter_date_from').val('');
      $('#filter_date_to').val('');
      $('#filter_search').val('');
      setDatePreset('all');
      loadPayments();
    }

    // 9. Excel Export Handler (Separate Column for Every Field)
    function exportPaymentsToExcel() {
      if (!allPaymentsData || allPaymentsData.length === 0) {
        Swal.fire('No Data to Export', 'There are no payment records matching your current filter criteria.', 'info');
        return;
      }

      // Convert payments array to structured table data with separate columns
      const exportRows = allPaymentsData.map((p, index) => {
        const isVerified = (p.verification || '').toLowerCase() === 'verified';
        const cleanProducts = p.product_names ? p.product_names.replace(/\|/g, ', ') : 'General Advance';

        return {
          "Sr No": index + 1,
          "Provisional Order No": p.invoiceno ? '#' + p.invoiceno : 'Direct / General Advance',
          "Order Date & Time": p.formatted_pso_created_at || 'N/A',
          "Client ID": p.clientid || '',
          "Client Name": p.clientname || 'Unknown Client',
          "Client Mobile No": p.pso_mobileno || '',
          "District": p.pso_districtname || '',
          "State": p.pso_statename || '',
          "Amount Received (INR)": parseFloat(p.advanceamount || 0),
          "Mode of Payment": p.sourceofpayment || 'Cash',
          "UTR / Ref / Txn No": p.utrnumber || p.paymentref_no || '',
          "Transaction Date": p.formatted_paymentdate || '',
          "Received By": p.receivedby || '',
          "Received Location": p.receivedlocation || '',
          "Purpose of Payment": p.purposeofpayment || 'Advance Payment',
          "Order Total Amount (INR)": parseFloat(p.pso_total_amount || 0),
          "Total Order Plants": p.total_plants || 0,
          "Ordered Products List": cleanProducts,
          "Remarks / Notes": p.remarks || '',
          "Verification Status": isVerified ? "Verified" : "Unverified",
          "Verified By": p.verificationby || 'N/A',
          "Verification Date & Time": p.formatted_verificationat || 'N/A'
        };
      });

      const tabTitle = currentStatusTab === 'unverified' ? 'Unverified_Payments' : (currentStatusTab === 'verified' ? 'Verified_Payments' : 'All_Transactions');
      const nowStr = new Date().toISOString().slice(0, 10);
      const filename = `KRF_Received_${tabTitle}_${nowStr}.xlsx`;

      if (typeof XLSX !== 'undefined') {
        const ws = XLSX.utils.json_to_sheet(exportRows);

        // Auto-fit column widths based on cell content lengths
        const colWidths = [];
        const headerKeys = Object.keys(exportRows[0]);
        headerKeys.forEach((key) => {
          let maxLen = key.length;
          exportRows.forEach(row => {
            const val = row[key] !== null && row[key] !== undefined ? String(row[key]) : '';
            if (val.length > maxLen) {
              maxLen = Math.min(val.length, 50);
            }
          });
          colWidths.push({ wch: Math.max(maxLen + 3, 12) });
        });
        ws['!cols'] = colWidths;

        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Payments");
        XLSX.writeFile(wb, filename);

        iziToast.success({
          title: 'Excel Export Complete!',
          message: `${exportRows.length} payment record(s) exported to Excel file.`,
          position: 'topRight'
        });
      } else {
        // Fallback: UTF-8 BOM CSV Export
        exportFallbackCSV(exportRows, `KRF_Received_${tabTitle}_${nowStr}.csv`);
      }
    }

    function exportFallbackCSV(rows, filename) {
      if (!rows || !rows.length) return;
      const headers = Object.keys(rows[0]);
      let csvContent = "\uFEFF"; // UTF-8 BOM
      csvContent += headers.map(h => `"${h.replace(/"/g, '""')}"`).join(",") + "\r\n";

      rows.forEach(row => {
        const rowStr = headers.map(h => {
          const val = row[h] !== null && row[h] !== undefined ? String(row[h]) : '';
          return `"${val.replace(/"/g, '""')}"`;
        }).join(",");
        csvContent += rowStr + "\r\n";
      });

      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement("a");
      const url = URL.createObjectURL(blob);
      link.setAttribute("href", url);
      link.setAttribute("download", filename);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);

      iziToast.success({
        title: 'Export Complete!',
        message: `${rows.length} payment records exported to file.`,
        position: 'topRight'
      });
    }

    // 10. Receipt Modal Zoom & View
    function openReceiptModal(url, type) {
      const $content = $('#receiptModalContent');
      $content.empty();
      $('#receiptDownloadBtn').attr('href', url);

      if (type === 'image') {
        $content.html(`<img src="${url}" class="img-fluid rounded-3 shadow" style="max-height: 70vh; object-fit: contain;" alt="Receipt">`);
      } else if (type === 'pdf') {
        $content.html(`<iframe src="${url}" style="width: 100%; height: 70vh; border: none; border-radius: 8px;"></iframe>`);
      }
      const modal = new bootstrap.Modal(document.getElementById('receiptModal'));
      modal.show();
    }

    // 10. Unified Verification & Details Modal Controller
    let currentModalReceiptUrl = '';
    let currentModalReceiptType = '';

    function openPaymentModal(sno, isForVerify = false) {
      const p = allPaymentsData.find(item => item.sno == sno);
      if (!p) {
        Swal.fire('Error', 'Payment record not found.', 'error');
        return;
      }

      $('#modal_payment_sno').val(p.sno);
      $('#modal_payment_psono').val(p.invoiceno || '');
      $('#modal_clientname').text(p.clientname || 'Unknown Client');
      $('#modal_orderno').text(p.invoiceno ? '#' + p.invoiceno : 'Direct / General Advance');
      
      const isVerified = (p.verification || '').toLowerCase() === 'verified';
      $('#modal_current_status_badge').html(
        isVerified 
          ? '<span class="status-badge-verified py-0.5"><i class="fa fa-check-circle"></i> Verified</span>' 
          : '<span class="status-badge-unverified py-0.5"><span class="pulse-dot"></span> Unverified</span>'
      );

      // Amount
      const rawAmt = parseFloat(p.advanceamount) || 0;
      $('#modal_advanceamount').val(rawAmt > 0 ? rawAmt : '');

      // Payment Mode
      let mode = (p.sourceofpayment || '').trim();
      if (mode.toLowerCase().includes('upi')) mode = 'UPI';
      else if (mode.toLowerCase().includes('cash')) mode = 'Cash';
      else if (mode.toLowerCase().includes('neft') || mode.toLowerCase().includes('rtgs')) mode = 'NEFT/RTGS';
      else if (mode.toLowerCase().includes('cheque')) mode = 'Cheque';
      else if (mode.toLowerCase().includes('card') || mode.toLowerCase().includes('pos')) mode = 'Card';
      else if (mode.toLowerCase().includes('bank')) mode = 'Bank Transfer';
      else if (!mode) mode = 'UPI';
      $('#modal_sourceofpayment').val(mode);

      // UTR & Date
      const cleanUtr = (p.utrnumber && p.utrnumber !== 'N/A') ? p.utrnumber : (p.paymentref_no && p.paymentref_no !== 'N/A' ? p.paymentref_no : '');
      $('#modal_utrnumber').val(cleanUtr);
      $('#modal_paymentdate').val(p.paymentdate || '');

      // Reset file input & label
      $('#modal_receipt_file').val('');
      $('#modal_upload_file_name').text('');
      $('#modal_validation_alert').addClass('d-none');

      // Populate Live Receipt Preview Screen
      currentModalReceiptUrl = '';
      currentModalReceiptType = '';

      if (p.receiptpath && p.receiptpath.trim() !== '') {
        const cleanPath = p.receiptpath.replace(/^(\.\.\/)+/, '');
        const fileExt = cleanPath.split('.').pop().toLowerCase();
        const fullPath = rooturl + cleanPath;
        currentModalReceiptUrl = fullPath;

        if (['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(fileExt)) {
          currentModalReceiptType = 'image';
          $('#modal_receipt_preview_area').html(`
            <div class="position-relative w-100 h-100 d-flex align-items-center justify-content-center">
              <img src="${fullPath}" class="img-fluid rounded-2 shadow-2xs cursor-pointer" style="max-height: 320px; max-width: 100%; object-fit: contain;" onclick="openReceiptModal('${fullPath}', 'image')" title="Click to view full screen" alt="Receipt">
            </div>
          `);
          $('#modal_receipt_preview_status').html('<span class="text-success small fw-semibold"><i class="fa fa-check-circle me-1"></i>Current receipt loaded. Click image or Zoom button for full size.</span>');
          $('#btnReceiptZoom').show();
        } else if (fileExt === 'pdf') {
          currentModalReceiptType = 'pdf';
          $('#modal_receipt_preview_area').html(`
            <iframe src="${fullPath}" style="width: 100%; height: 320px; border: none; border-radius: 8px;"></iframe>
          `);
          $('#modal_receipt_preview_status').html('<span class="text-danger small fw-semibold"><i class="fa fa-file-pdf me-1"></i>PDF receipt attached. (<a href="' + fullPath + '" target="_blank" class="text-primary text-decoration-none">Open in New Tab</a>)</span>');
          $('#btnReceiptZoom').show();
        } else {
          currentModalReceiptType = 'file';
          $('#modal_receipt_preview_area').html(`
            <div class="p-4 text-center">
              <i class="fa fa-file-alt text-secondary fa-4x mb-2"></i>
              <div class="text-dark fw-semibold mt-1">Receipt Attachment File</div>
              <a href="${fullPath}" target="_blank" class="btn btn-outline-primary btn-sm mt-3 px-3 rounded-3"><i class="fa fa-download me-1"></i>Download Attachment</a>
            </div>
          `);
          $('#modal_receipt_preview_status').html('<span class="text-muted small"><i class="fa fa-paperclip me-1"></i>Generic document attached</span>');
          $('#btnReceiptZoom').hide();
        }
      } else {
        $('#modal_receipt_preview_area').html(`
          <div class="p-4 text-center text-muted">
            <i class="fa fa-file-invoice text-secondary fa-3x mb-2" style="opacity: 0.45;"></i>
            <div class="fw-semibold text-secondary fs-14">No Receipt Attached</div>
            <small class="text-muted d-block mt-1">You can attach a receipt image/PDF from the file chooser.</small>
          </div>
        `);
        $('#modal_receipt_preview_status').html('<span class="text-danger small fst-italic"><i class="fa fa-times-circle me-1"></i>No document uploaded yet</span>');
        $('#btnReceiptZoom').hide();
      }

      handlePaymentModeChange();

      if (isForVerify) {
        $('#paymentVerificationModalLabel').html('<i class="fa fa-shield-alt text-warning me-2"></i> Review & Verify Payment');
        $('#btnModalVerify').html('<i class="fa fa-check-circle me-1"></i> Confirm & Verify').show();
      } else {
        $('#paymentVerificationModalLabel').html('<i class="fa fa-edit text-warning me-2"></i> Edit Transaction Details & Receipt');
        $('#btnModalVerify').html('<i class="fa fa-check-double me-1"></i> Save & Verify').show();
      }

      const modal = new bootstrap.Modal(document.getElementById('paymentVerificationModal'));
      modal.show();
    }

    function zoomCurrentReceipt() {
      if (currentModalReceiptUrl) {
        openReceiptModal(currentModalReceiptUrl, currentModalReceiptType || 'image');
      }
    }

    function handlePaymentModeChange() {
      const mode = ($('#modal_sourceofpayment').val() || '').toLowerCase();
      if (mode === 'cash') {
        $('#modal_utr_required_star').hide();
        $('#modal_utrnumber').attr('placeholder', 'Optional for Cash (e.g. CASH)');
        if (!$('#modal_utrnumber').val()) {
          $('#modal_utrnumber').val('CASH');
        }
      } else {
        $('#modal_utr_required_star').show();
        $('#modal_utrnumber').attr('placeholder', 'e.g. 023321814862 or Txn Ref No.');
        if ($('#modal_utrnumber').val() === 'CASH') {
          $('#modal_utrnumber').val('');
        }
      }
    }

    function previewModalReceipt(input) {
      if (input.files && input.files[0]) {
        const file = input.files[0];
        const fileName = file.name;
        const fileSize = (file.size / 1024).toFixed(1) + ' KB';
        const fileExt = fileName.split('.').pop().toLowerCase();

        $('#modal_upload_file_name').text(`${fileName} (${fileSize})`);

        if (['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(fileExt)) {
          const reader = new FileReader();
          reader.onload = function(e) {
            currentModalReceiptUrl = e.target.result;
            currentModalReceiptType = 'image';
            $('#modal_receipt_preview_area').html(`
              <div class="position-relative w-100 h-100 d-flex align-items-center justify-content-center">
                <img src="${e.target.result}" class="img-fluid rounded-2 shadow-2xs cursor-pointer" style="max-height: 320px; max-width: 100%; object-fit: contain;" onclick="openReceiptModal('${e.target.result}', 'image')" title="Click to view full screen" alt="Receipt">
              </div>
            `);
            $('#modal_receipt_preview_status').html(`<span class="text-primary small fw-semibold"><i class="fa fa-cloud-upload-alt me-1"></i>New image preview ready: <strong>${fileName}</strong></span>`);
            $('#btnReceiptZoom').show();
          };
          reader.readAsDataURL(file);
        } else if (fileExt === 'pdf') {
          currentModalReceiptUrl = '';
          currentModalReceiptType = 'pdf';
          $('#modal_receipt_preview_area').html(`
            <div class="p-4 text-center text-danger">
              <i class="fa fa-file-pdf fa-4x mb-2"></i>
              <div class="fw-bold fs-14">${fileName}</div>
              <small class="text-muted">PDF document selected & ready for upload (${fileSize})</small>
            </div>
          `);
          $('#modal_receipt_preview_status').html(`<span class="text-primary small fw-semibold"><i class="fa fa-cloud-upload-alt me-1"></i>New PDF document selected</span>`);
          $('#btnReceiptZoom').hide();
        } else {
          currentModalReceiptUrl = '';
          currentModalReceiptType = 'file';
          $('#modal_receipt_preview_area').html(`
            <div class="p-4 text-center text-secondary">
              <i class="fa fa-file fa-4x mb-2"></i>
              <div class="fw-bold fs-14">${fileName}</div>
              <small class="text-muted">File ready to upload (${fileSize})</small>
            </div>
          `);
          $('#modal_receipt_preview_status').html(`<span class="text-primary small fw-semibold"><i class="fa fa-cloud-upload-alt me-1"></i>New file selected: ${fileName}</span>`);
          $('#btnReceiptZoom').hide();
        }
      }
    }

    function submitPaymentModal(verifyNow) {
      const sno = $('#modal_payment_sno').val();
      const psono = ($('#modal_payment_psono').val() || '').trim();
      const advanceamount = parseFloat($('#modal_advanceamount').val());
      const sourceofpayment = ($('#modal_sourceofpayment').val() || '').trim();
      const utrnumber = ($('#modal_utrnumber').val() || '').trim();
      const paymentdate = ($('#modal_paymentdate').val() || '').trim();
      const fileInput = document.getElementById('modal_receipt_file');

      $('#modal_validation_alert').addClass('d-none');

      // Comprehensive validation
      if (!sno) {
        showModalValidation('Payment record identifier is missing.');
        return;
      }

      if (isNaN(advanceamount) || advanceamount <= 0) {
        showModalValidation('Please enter a valid Amount Received greater than 0.');
        $('#modal_advanceamount').focus();
        return;
      }

      if (!sourceofpayment) {
        showModalValidation('Please select a Mode of Payment / Transaction Type.');
        $('#modal_sourceofpayment').focus();
        return;
      }

      if (!paymentdate) {
        showModalValidation('Please select a valid Transaction Date.');
        $('#modal_paymentdate').focus();
        return;
      }

      if (verifyNow) {
        if (sourceofpayment.toLowerCase() !== 'cash' && !utrnumber) {
          showModalValidation(`Transaction No / UTR No is required to verify ${sourceofpayment} transactions.`);
          $('#modal_utrnumber').focus();
          return;
        }
      }

      const formData = new FormData();
      formData.append('action', 'update_receipt');
      formData.append('sno', sno);
      formData.append('psono', psono);
      formData.append('projectid', projectid);
      formData.append('loginname', loginname);
      formData.append('advanceamount', advanceamount);
      formData.append('sourceofpayment', sourceofpayment);
      formData.append('utrnumber', utrnumber || (sourceofpayment.toLowerCase() === 'cash' ? 'CASH' : ''));
      formData.append('paymentdate', paymentdate);
      formData.append('verify_now', verifyNow ? 'yes' : 'no');

      if (fileInput.files && fileInput.files.length > 0) {
        formData.append('receiptfile', fileInput.files[0]);
      }

      const btnId = verifyNow ? '#btnModalVerify' : '#btnModalUpdateOnly';
      const originalBtnText = $(btnId).html();
      $(btnId).prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Processing...');

      $.ajax({
        url: "modal/payment_verification_modal.php?action=update_receipt",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function(res) {
          $(btnId).prop('disabled', false).html(originalBtnText);
          if (res.status === 'success') {
            const modalEl = document.getElementById('paymentVerificationModal');
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) {
              modalInstance.hide();
            }

            iziToast.success({
              title: verifyNow ? 'Verified!' : 'Updated!',
              message: res.message,
              position: 'topRight'
            });

            loadPayments();
          } else {
            showModalValidation(res.message || 'Failed to update transaction.');
          }
        },
        error: function(xhr, status, error) {
          $(btnId).prop('disabled', false).html(originalBtnText);
          showModalValidation('Server error: ' + error);
        }
      });
    }

    function showModalValidation(msg) {
      $('#modal_validation_msg').text(msg);
      $('#modal_validation_alert').removeClass('d-none');
    }
  </script>
</body>

</html>
