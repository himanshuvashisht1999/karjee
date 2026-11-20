<?php
if (isset($_GET['dz'])) {
  require('../connection.php');
  require('../functions/function_master.php');
  include('functions/vendor_functions.php');
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
} else {
  include('../header.php');
}
?>

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Product Demands & Custom Enquiries</title>
  
  <!-- Fonts & Icons -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
  
  <!-- Select2 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
  
  <style>
    :root {
      --primary: #0f766e;
      --primary-hover: #115e59;
      --primary-soft: #f0fdfa;
      --secondary: #475569;
      --accent: #f59e0b;
      --bg-main: #f8fafc;
      --surface: #ffffff;
      --text-main: #0f172a;
      --text-muted: #64748b;
      --border-color: #e2e8f0;
      --radius-sm: 8px;
      --radius-md: 12px;
      --radius-lg: 16px;
      --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
      --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.07), 0 2px 4px -2px rgb(0 0 0 / 0.05);
      --shadow-lg: 0 10px 25px -3px rgb(0 0 0 / 0.08), 0 4px 6px -4px rgb(0 0 0 / 0.04);
    }

    body {
      background-color: var(--bg-main);
      font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      color: var(--text-main);
      letter-spacing: -0.01em;
    }

    /* 1. Header Banner & KPI Cards */
    .dashboard-banner {
      background: linear-gradient(135deg, #042f2e 0%, #0f766e 60%, #14b8a6 100%);
      border-radius: var(--radius-md);
      padding: 0.85rem 1.15rem;
      color: white;
      box-shadow: 0 6px 20px -4px rgba(15, 118, 110, 0.25);
      margin-bottom: 0.85rem;
      position: relative;
      overflow: hidden;
    }

    .dashboard-banner::after {
      content: '';
      position: absolute;
      top: -50%;
      right: -20%;
      width: 400px;
      height: 400px;
      background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
      border-radius: 50%;
      pointer-events: none;
    }

    .kpi-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
      gap: 0.5rem;
      width: 100%;
    }

    @media (min-width: 1200px) {
      .kpi-container {
        grid-template-columns: repeat(6, 1fr);
      }
    }

    .kpi-card {
      background: rgba(255, 255, 255, 0.12);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: var(--radius-sm);
      padding: 0.45rem 0.65rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .kpi-card:hover {
      background: rgba(255, 255, 255, 0.2);
      transform: translateY(-2px);
      border-color: rgba(255, 255, 255, 0.35);
    }

    .kpi-icon-wrap {
      width: 32px;
      height: 32px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.85rem;
      flex-shrink: 0;
    }

    .kpi-icon-total { background: rgba(245, 158, 11, 0.2); color: #fde047; }
    .kpi-icon-qty { background: rgba(56, 189, 248, 0.2); color: #7dd3fc; }
    .kpi-icon-pending { background: rgba(251, 191, 36, 0.2); color: #fcd34d; }
    .kpi-icon-review { background: rgba(129, 140, 248, 0.2); color: #c7d2fe; }
    .kpi-icon-converted { background: rgba(52, 211, 153, 0.2); color: #a7f3d0; }
    .kpi-icon-rejected { background: rgba(248, 113, 113, 0.2); color: #fca5a5; }

    .kpi-content .kpi-label {
      font-size: 0.66rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.4px;
      color: rgba(255, 255, 255, 0.8);
      margin-bottom: 1px;
      line-height: 1.1;
    }

    .kpi-content .kpi-val {
      font-size: 1rem;
      font-weight: 800;
      color: #ffffff;
      line-height: 1.1;
    }

    /* 2. Structured 3-Row Filter Toolbar Card */
    .filter-card {
      background: var(--surface);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-md);
      box-shadow: var(--shadow-sm);
      padding: 0.85rem 1.15rem;
      margin-bottom: 0.85rem;
    }

    .filter-card-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 0.6rem;
      padding-bottom: 0.4rem;
      border-bottom: 1px solid var(--border-color);
    }

    .filter-card-title {
      font-size: 0.85rem;
      font-weight: 700;
      color: var(--primary);
      display: flex;
      align-items: center;
      gap: 0.4rem;
      margin: 0;
    }

    .filter-row {
      margin-bottom: 0.55rem;
    }

    .filter-row:last-child {
      margin-bottom: 0;
    }

    .form-group-custom {
      display: flex;
      flex-direction: column;
    }

    .form-group-custom label {
      font-size: 0.74rem;
      font-weight: 600;
      color: #475569;
      margin-bottom: 0.2rem;
      display: flex;
      align-items: center;
      gap: 0.3rem;
    }

    .form-control-custom {
      height: 36px;
      border: 1.5px solid #cbd5e1;
      border-radius: var(--radius-sm);
      font-size: 0.84rem;
      font-weight: 500;
      color: #1e293b;
      padding: 0.35rem 0.65rem;
      background-color: #ffffff;
      transition: all 0.2s ease;
      width: 100%;
    }

    .form-control-custom:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.15);
      outline: none;
    }

    /* Custom Search Input */
    .search-wrap {
      position: relative;
      width: 100%;
    }

    .search-wrap .search-icon {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: #94a3b8;
      font-size: 0.85rem;
      pointer-events: none;
    }

    .search-wrap input {
      padding-left: 34px !important;
      padding-right: 32px !important;
    }

    .search-wrap .clear-btn {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      color: #94a3b8;
      cursor: pointer;
      display: none;
      font-size: 0.85rem;
    }

    .search-wrap .clear-btn:hover {
      color: #334155;
    }

    /* Select2 Multi-Select Refined */
    .select2-container {
      width: 100% !important;
    }

    .select2-container--default .select2-selection--multiple {
      border: 1.5px solid #cbd5e1 !important;
      border-radius: var(--radius-sm) !important;
      min-height: 36px !important;
      padding: 2px 6px !important;
      background-color: #ffffff !important;
      box-shadow: none !important;
      display: flex !important;
      flex-wrap: wrap !important;
      align-items: center !important;
    }

    .select2-container--default.select2-container--focus .select2-selection--multiple {
      border-color: var(--primary) !important;
      box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.15) !important;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__rendered {
      display: flex !important;
      flex-wrap: wrap !important;
      align-items: center !important;
      gap: 3px !important;
      padding: 0 !important;
      margin: 0 !important;
      width: 100% !important;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice {
      background: #ccfbf1 !important;
      border: 1px solid #99f6e4 !important;
      color: var(--primary) !important;
      font-weight: 600 !important;
      border-radius: 4px !important;
      padding: 1px 6px !important;
      font-size: 0.76rem !important;
      margin: 1px 0 !important;
      display: inline-flex !important;
      align-items: center !important;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
      color: var(--primary) !important;
      margin-right: 4px !important;
      font-weight: bold !important;
      border: none !important;
      background: none !important;
    }

    .select2-container--default .select2-selection--multiple .select2-search--inline {
      margin: 0 !important;
      padding: 0 !important;
      flex: 1 !important;
      min-width: 120px !important;
    }

    .select2-container--default .select2-selection--multiple .select2-search--inline .select2-search__field {
      margin: 0 !important;
      padding: 2px 0 !important;
      height: 28px !important;
      line-height: 28px !important;
      font-size: 0.84rem !important;
      color: #1e293b !important;
      width: 100% !important;
    }

    .select2-container--default .select2-selection--multiple .select2-search--inline .select2-search__field::placeholder {
      color: #94a3b8 !important;
    }

    .select2-dropdown {
      border: 1px solid #cbd5e1 !important;
      border-radius: 8px !important;
      box-shadow: var(--shadow-lg) !important;
      overflow: hidden !important;
      z-index: 1055 !important;
    }

    .select2-results__option--highlighted[aria-selected] {
      background-color: var(--primary) !important;
      color: white !important;
    }

    /* Buttons */
    .btn-action-primary {
      height: 36px;
      border-radius: var(--radius-sm);
      font-weight: 600;
      font-size: 0.82rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.4rem;
      padding: 0 1rem;
      transition: all 0.2s ease;
      box-shadow: var(--shadow-sm);
    }

    .btn-action-primary:hover {
      transform: translateY(-1px);
      box-shadow: var(--shadow-md);
    }

    /* 3. Table Card */
    .data-table-card {
      background: var(--surface);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow-md);
      overflow: hidden;
    }

    .custom-enquiry-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      margin-bottom: 0;
    }

    .custom-enquiry-table thead th {
      background-color: #f8fafc;
      color: #475569;
      font-size: 0.76rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.6px;
      padding: 14px 16px;
      border-bottom: 1.5px solid #e2e8f0;
      vertical-align: middle;
      white-space: nowrap;
    }

    .custom-enquiry-table tbody td {
      padding: 14px 16px;
      vertical-align: middle;
      border-bottom: 1px solid #f1f5f9;
      font-size: 0.88rem;
    }

    .custom-enquiry-table tbody tr {
      transition: background-color 0.15s ease;
    }

    .custom-enquiry-table tbody tr:hover {
      background-color: #f0fdfa;
    }

    .custom-enquiry-table tfoot td {
      padding: 16px 18px;
      background-color: #f8fafc;
      border-top: 2px solid #e2e8f0;
      font-weight: 600;
    }

    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: capitalize;
    }

    .status-pending { background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
    .status-in-review { background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
    .status-converted { background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .status-rejected { background-color: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

    .product-demanded-tag {
      background: #fef9c3;
      color: #854d0e;
      border: 1px solid #fde047;
      border-radius: 6px;
      padding: 4px 10px;
      font-weight: 600;
      display: inline-block;
      font-size: 0.85rem;
    }

    .btn-action-icon {
      width: 34px;
      height: 34px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 8px;
      padding: 0;
      transition: all 0.15s ease;
    }

    .btn-action-icon:hover {
      transform: scale(1.08);
    }
  </style>
</head>

<body data-companyid="<?php echo $companyid ?? ''; ?>" data-subcompanyid="<?php echo $subcompanyid ?? ''; ?>" data-projectid="<?php echo $projectid ?? ''; ?>" data-routurl="<?php echo $prefix ?? '../'; ?>">
  <div class="container-fluid py-2 px-3">
    
    <!-- 1. Executive Banner & Live KPI Metrics -->
    <div class="dashboard-banner">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
        <div>
          <h5 class="mb-1 text-white fw-bold d-flex align-items-center gap-2">
            <i class="fa fa-clipboard-list text-warning"></i> Product Demands & Custom Enquiries
          </h5>
          <p class="mb-0 text-white-50" style="font-size: 0.76rem;">View and manage customized client demand enquiries recorded outside standard catalog.</p>
        </div>
      </div>

      <!-- KPI Summary Cards Grid -->
      <div class="kpi-container">
        <div class="kpi-card" title="Total Filtered Enquiries">
          <div class="kpi-icon-wrap kpi-icon-total"><i class="fa fa-layer-group"></i></div>
          <div class="kpi-content">
            <div class="kpi-label">Total Enquiries</div>
            <div class="kpi-val" id="chipTotalEnquiries">0</div>
          </div>
        </div>

        <div class="kpi-card" title="Total Demanded Quantity">
          <div class="kpi-icon-wrap kpi-icon-qty"><i class="fa fa-boxes"></i></div>
          <div class="kpi-content">
            <div class="kpi-label">Demanded Qty</div>
            <div class="kpi-val" id="chipTotalQuantity">0</div>
          </div>
        </div>

        <div class="kpi-card" title="Pending Enquiries">
          <div class="kpi-icon-wrap kpi-icon-pending"><i class="fa fa-clock"></i></div>
          <div class="kpi-content">
            <div class="kpi-label">Pending</div>
            <div class="kpi-val" id="chipPendingEnquiries">0</div>
          </div>
        </div>

        <div class="kpi-card" title="In Review Enquiries">
          <div class="kpi-icon-wrap kpi-icon-review"><i class="fa fa-search"></i></div>
          <div class="kpi-content">
            <div class="kpi-label">In Review</div>
            <div class="kpi-val" id="chipInReviewEnquiries">0</div>
          </div>
        </div>

        <div class="kpi-card" title="Converted to Orders">
          <div class="kpi-icon-wrap kpi-icon-converted"><i class="fa fa-check-circle"></i></div>
          <div class="kpi-content">
            <div class="kpi-label">Converted</div>
            <div class="kpi-val" id="chipConvertedEnquiries">0</div>
          </div>
        </div>

        <div class="kpi-card" title="Rejected Enquiries">
          <div class="kpi-icon-wrap kpi-icon-rejected"><i class="fa fa-times-circle"></i></div>
          <div class="kpi-content">
            <div class="kpi-label">Rejected</div>
            <div class="kpi-val" id="chipRejectedEnquiries">0</div>
          </div>
        </div>
      </div>
    </div>

    <!-- 2. Responsive 3-Row Filter Toolbar Card -->
    <div class="filter-card">
      <div class="filter-card-header">
        <h6 class="filter-card-title">
          <i class="fa fa-sliders-h"></i> Search & Filters
        </h6>
        <small class="text-muted"><i class="fa fa-info-circle me-1"></i>Filters update table dynamically</small>
      </div>

      <!-- Row 1: Date From, Date To, Action Buttons (Export, Reset, Refresh) -->
      <div class="row g-3 align-items-end filter-row">
        <!-- Date From -->
        <div class="col-lg-3 col-md-6 col-sm-6 form-group-custom">
          <label><i class="fa fa-calendar-alt text-primary"></i> Date From</label>
          <input type="date" id="dateFromFilter" class="form-control-custom">
        </div>

        <!-- Date To -->
        <div class="col-lg-3 col-md-6 col-sm-6 form-group-custom">
          <label><i class="fa fa-calendar-alt text-primary"></i> Date To</label>
          <input type="date" id="dateToFilter" class="form-control-custom">
        </div>

        <!-- Action Buttons Area: Export CSV, Reset, Refresh -->
        <div class="col-lg-6 col-md-12 col-sm-12 d-flex align-items-end justify-content-lg-end gap-2">
          <!-- Export to CSV -->
          <button class="btn btn-success btn-action-primary" onclick="exportEnquiriesToCSV()" title="Export Filtered CSV">
            <i class="fa fa-file-excel"></i> Export CSV
          </button>

          <!-- Reset Filter -->
          <button class="btn btn-outline-secondary btn-action-primary" onclick="resetFilters()" title="Reset All Filters">
            <i class="fa fa-undo"></i> Reset
          </button>

          <!-- Refresh Server Data -->
          <button class="btn btn-outline-primary btn-action-primary" onclick="loadEnquiriesGrid()" title="Reload Data">
            <i class="fa fa-sync-alt" id="refreshIcon"></i> Refresh
          </button>
        </div>
      </div>

      <!-- Row 2: State, District, City, Status -->
      <div class="row g-3 align-items-end filter-row">
        <!-- State -->
        <div class="col-lg-3 col-md-6 col-sm-6 form-group-custom">
          <label><i class="fa fa-map text-primary"></i> State</label>
          <select id="stateFilter" class="form-control-custom form-select">
            <option value="">All States</option>
          </select>
        </div>

        <!-- District -->
        <div class="col-lg-3 col-md-6 col-sm-6 form-group-custom">
          <label><i class="fa fa-map-pin text-primary"></i> District</label>
          <select id="districtFilter" class="form-control-custom form-select">
            <option value="">All Districts</option>
          </select>
        </div>

        <!-- City -->
        <div class="col-lg-3 col-md-6 col-sm-6 form-group-custom">
          <label><i class="fa fa-city text-primary"></i> City</label>
          <select id="cityFilter" class="form-control-custom form-select">
            <option value="">All Cities</option>
          </select>
        </div>

        <!-- Status -->
        <div class="col-lg-3 col-md-6 col-sm-6 form-group-custom">
          <label><i class="fa fa-tag text-primary"></i> Status</label>
          <select id="statusFilter" class="form-control-custom form-select">
            <option value="">All Statuses</option>
            <option value="Pending">Pending</option>
            <option value="In Review">In Review</option>
            <option value="Converted">Converted</option>
            <option value="Rejected">Rejected</option>
          </select>
        </div>
      </div>

      <!-- Row 3: Product Name (Multi-Select), Search -->
      <div class="row g-3 align-items-end filter-row">
        <!-- Demanded Product (Multi-select) -->
        <div class="col-lg-6 col-md-12 form-group-custom">
          <label><i class="fa fa-box-open text-primary"></i> Product Name (Multi-Select)</label>
          <select id="productFilter" class="form-control-custom select2" multiple="multiple" data-placeholder="Choose Demanded Products...">
          </select>
        </div>

        <!-- Quick Search -->
        <div class="col-lg-6 col-md-12 form-group-custom">
          <label><i class="fa fa-search text-primary"></i> Quick Search</label>
          <div class="search-wrap">
            <i class="fa fa-search search-icon"></i>
            <input type="text" id="enquirySearchInput" class="form-control-custom" placeholder="Search Client, Mobile, Product, Remarks..." autocomplete="off">
            <i class="fa fa-times clear-btn" id="clearSearchBtn" title="Clear Search"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- 3. Enquiries Data Table Card -->
    <div class="data-table-card">
      <div class="table-responsive">
        <table class="table custom-enquiry-table align-middle" id="enquiriesTable">
          <thead>
            <tr>
              <th class="text-center" style="width: 60px;">S.No.</th>
              <th style="width: 140px;">Enquiry No & Date</th>
              <th style="min-width: 180px;">Client Info</th>
              <th style="min-width: 160px;">Location</th>
              <th style="min-width: 170px;">Demanded Product</th>
              <th style="width: 140px;">Expected Qty / Date</th>
              <th style="min-width: 150px;">Remarks</th>
              <th class="text-center" style="width: 110px;">Status</th>
              <th class="text-center" style="width: 110px;">Action</th>
            </tr>
          </thead>
          <tbody id="enquiryTableBody">
            <tr>
              <td colspan="9" class="text-center text-muted py-5">
                <i class="fa fa-spinner fa-spin fa-2x mb-2" style="color: #0d9488;"></i>
                <p class="mb-0">Loading enquiries...</p>
              </td>
            </tr>
          </tbody>
          <!-- Summary of Displayed Data Footer -->
          <tfoot id="enquiryTableFooter" style="display: none;">
            <tr class="table-light">
              <td colspan="5" class="text-end fw-bold text-secondary">
                <i class="fa fa-chart-pie me-1 text-primary"></i> Summary of Displayed Data (<span id="footTotalRows" class="text-dark">0</span> Enquiries):
              </td>
              <td class="fw-bold text-dark">
                <div><strong>Total Qty:</strong> <span id="footTotalQty" class="text-primary fs-6">0</span></div>
              </td>
              <td colspan="3">
                <div id="footStatusBreakdown" class="d-flex align-items-center gap-1 flex-wrap"></div>
              </td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

  </div>

  <!-- View Enquiry Details Modal -->
  <div class="modal fade" id="viewEnquiryModal" tabindex="-1" role="dialog" aria-labelledby="viewEnquiryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
      <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
        <div class="modal-header border-0 p-4 pb-3" style="background: linear-gradient(135deg, #042f2e 0%, #0f766e 100%); color: white;">
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle bg-white p-2 d-flex align-items-center justify-content-center text-primary shadow-sm" style="width: 44px; height: 44px; color: #0f766e !important;">
              <i class="fa fa-file-alt fa-lg"></i>
            </div>
            <div>
              <h5 class="modal-title fw-bold mb-0 text-white" id="viewEnquiryModalLabel">Enquiry Details</h5>
              <small class="text-white-50" id="viewModalSub">Tracking ID</small>
            </div>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4" id="viewEnquiryModalBody">
          <!-- Populated dynamically via JS -->
        </div>
        <div class="modal-footer border-0 p-3 bg-light">
          <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal" data-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Update Status Modal -->
  <div class="modal fade" id="updateStatusModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
        <div class="modal-header border-0 p-3 bg-warning text-dark">
          <h5 class="modal-title fw-bold mb-0"><i class="fa fa-edit me-2"></i>Update Enquiry Status</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="updateStatusForm">
          <input type="hidden" id="status_enquiry_id" value="">
          <div class="modal-body p-4">
            <div class="mb-3">
              <label class="form-label fw-bold small text-muted">Status</label>
              <select id="status_select_val" class="form-select" required>
                <option value="Pending">Pending</option>
                <option value="In Review">In Review</option>
                <option value="Converted">Converted to Order</option>
                <option value="Rejected">Rejected</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold small text-muted">Updated Remarks / Notes</label>
              <textarea id="status_remarks_val" class="form-control" rows="3" placeholder="Enter follow-up update notes..."></textarea>
            </div>
          </div>
          <div class="modal-footer border-0 p-3 bg-light">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal" data-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-warning fw-bold px-4" id="btnSaveStatus">Update Status</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <?php if (!isset($_GET['dz'])) { include('../footer.php'); } ?>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- Select2 JS -->
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

  <script>
    let globalEnquiries = [];
    let currentFilteredList = [];

    // Helper: Format Date string (YYYY-MM-DD or datetime) to dd-MM-yy (e.g., 19-Aug-26)
    function formatEnquiryDate(dateStr) {
      if (!dateStr || dateStr === '0000-00-00' || dateStr === '0000-00-00 00:00:00') return '-';
      const clean = dateStr.split(' ')[0].trim();
      const parts = clean.split('-');
      if (parts.length !== 3) return dateStr;
      const y = parts[0];
      const m = parts[1];
      const d = parts[2];

      const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
      const mIdx = parseInt(m, 10) - 1;
      const mName = (mIdx >= 0 && mIdx < 12) ? monthNames[mIdx] : m;

      // dd-MM-yy format (e.g. 19-Aug-26)
      const shortYear = y.length === 4 ? y.slice(-2) : y;
      return `${d}-${mName}-${shortYear}`;
    }

    // Set Default Date Range to Current Month (1st of month to last of month)
    function setDefaultDateRange() {
      const now = new Date();
      const year = now.getFullYear();
      const month = String(now.getMonth() + 1).padStart(2, '0');
      
      const firstDay = `${year}-${month}-01`;
      const lastDate = new Date(year, now.getMonth() + 1, 0).getDate();
      const lastDay = `${year}-${month}-${String(lastDate).padStart(2, '0')}`;
      
      $("#dateFromFilter").val(firstDay);
      $("#dateToFilter").val(lastDay);
    }

    function loadEnquiriesGrid() {
      const routurl = $("body").data("routurl") || "../";
      const companyid = $("body").data("companyid") || "";
      const subcompanyid = $("body").data("subcompanyid") || "";
      const projectid = $("body").data("projectid") || "";
      const datefrom = $("#dateFromFilter").val() || "";
      const dateto = $("#dateToFilter").val() || "";

      $("#refreshIcon").addClass("fa-spin");

      $.ajax({
        url: routurl + "krf-orderandsales/modal/client_modal.php?action=getAllProductEnquiries",
        type: "POST",
        dataType: "json",
        data: {
          companyid: companyid,
          subcompanyid: subcompanyid,
          projectid: projectid,
          datefrom: datefrom,
          dateto: dateto
        },
        success: function (data) {
          $("#refreshIcon").removeClass("fa-spin");
          globalEnquiries = data || [];
          populateDynamicFilters(globalEnquiries);
          applyAllFilters();
        },
        error: function (xhr, status, err) {
          $("#refreshIcon").removeClass("fa-spin");
          console.error("Failed to load enquiries:", err);
          $("#enquiryTableBody").html(`
            <tr>
              <td colspan="9" class="text-center text-danger py-4">
                <i class="fa fa-exclamation-triangle fa-2x mb-2"></i>
                <p>Failed to load enquiry data. Please try again.</p>
              </td>
            </tr>
          `);
          $("#enquiryTableFooter").hide();
        }
      });
    }

    // Populate Dynamic Filter Dropdowns (State, District, City, Product Multi-select)
    function populateDynamicFilters(list) {
      const currentState = $("#stateFilter").val() || "";
      const currentDistrict = $("#districtFilter").val() || "";
      const currentCity = $("#cityFilter").val() || "";
      const currentProducts = $("#productFilter").val() || [];

      const states = new Set();
      const districts = new Set();
      const cities = new Set();
      const products = new Set();

      list.forEach(item => {
        if (item.statename && item.statename.trim()) states.add(item.statename.trim());
        if (item.districtname && item.districtname.trim()) districts.add(item.districtname.trim());
        if (item.cityname && item.cityname.trim()) cities.add(item.cityname.trim());
        if (item.product_name && item.product_name.trim()) products.add(item.product_name.trim());
      });

      // Populate State Filter
      let stateOptions = '<option value="">All States</option>';
      Array.from(states).sort().forEach(st => {
        const sel = (st === currentState) ? 'selected' : '';
        stateOptions += `<option value="${st}" ${sel}>${st}</option>`;
      });
      $("#stateFilter").html(stateOptions);

      // Populate District Filter
      updateDistrictFilterOptions(list, currentState, currentDistrict);

      // Populate City Filter
      updateCityFilterOptions(list, currentState, currentDistrict, currentCity);

      // Populate Product Multi-Select Filter
      let productOptions = '';
      Array.from(products).sort().forEach(p => {
        const sel = currentProducts.includes(p) ? 'selected' : '';
        productOptions += `<option value="${p}" ${sel}>${p}</option>`;
      });
      $("#productFilter").html(productOptions).trigger('change.select2');
    }

    function updateDistrictFilterOptions(list, stateVal, selectedDistrict) {
      const districts = new Set();
      list.forEach(item => {
        if (!stateVal || (item.statename && item.statename.trim() === stateVal)) {
          if (item.districtname && item.districtname.trim()) districts.add(item.districtname.trim());
        }
      });

      let districtOptions = '<option value="">All Districts</option>';
      Array.from(districts).sort().forEach(dst => {
        const sel = (dst === selectedDistrict) ? 'selected' : '';
        districtOptions += `<option value="${dst}" ${sel}>${dst}</option>`;
      });
      $("#districtFilter").html(districtOptions);
    }

    function updateCityFilterOptions(list, stateVal, districtVal, selectedCity) {
      const cities = new Set();
      list.forEach(item => {
        const matchState = !stateVal || (item.statename && item.statename.trim() === stateVal);
        const matchDist = !districtVal || (item.districtname && item.districtname.trim() === districtVal);
        if (matchState && matchDist) {
          if (item.cityname && item.cityname.trim()) cities.add(item.cityname.trim());
        }
      });

      let cityOptions = '<option value="">All Cities</option>';
      Array.from(cities).sort().forEach(ct => {
        const sel = (ct === selectedCity) ? 'selected' : '';
        cityOptions += `<option value="${ct}" ${sel}>${ct}</option>`;
      });
      $("#cityFilter").html(cityOptions);
    }

    function applyAllFilters() {
      const searchTerm = ($("#enquirySearchInput").val() || "").toLowerCase().trim();
      const statusFilter = $("#statusFilter").val() || "";
      const stateFilter = $("#stateFilter").val() || "";
      const districtFilter = $("#districtFilter").val() || "";
      const cityFilter = $("#cityFilter").val() || "";
      const selectedProducts = $("#productFilter").val() || [];
      const dateFrom = $("#dateFromFilter").val() || "";
      const dateTo = $("#dateToFilter").val() || "";

      currentFilteredList = globalEnquiries.filter(function (item) {
        // Date range filter
        if (item.created_at) {
          const itemDate = item.created_at.substring(0, 10);
          if (dateFrom && itemDate < dateFrom) return false;
          if (dateTo && itemDate > dateTo) return false;
        }

        // Status filter
        if (statusFilter && item.status !== statusFilter) return false;

        // State filter
        if (stateFilter && (item.statename || '').trim() !== stateFilter) return false;

        // District filter
        if (districtFilter && (item.districtname || '').trim() !== districtFilter) return false;

        // City filter
        if (cityFilter && (item.cityname || '').trim() !== cityFilter) return false;

        // Product Name (Multiple Selection) filter
        if (selectedProducts && selectedProducts.length > 0) {
          const prodName = (item.product_name || '').trim();
          if (!selectedProducts.includes(prodName)) return false;
        }

        // Quick search text
        if (searchTerm) {
          const matched = (
            (item.enquiry_no && item.enquiry_no.toLowerCase().includes(searchTerm)) ||
            (item.clientname && item.clientname.toLowerCase().includes(searchTerm)) ||
            (item.mobileno && item.mobileno.toLowerCase().includes(searchTerm)) ||
            (item.product_name && item.product_name.toLowerCase().includes(searchTerm)) ||
            (item.cityname && item.cityname.toLowerCase().includes(searchTerm)) ||
            (item.districtname && item.districtname.toLowerCase().includes(searchTerm)) ||
            (item.statename && item.statename.toLowerCase().includes(searchTerm)) ||
            (item.address && item.address.toLowerCase().includes(searchTerm)) ||
            (item.remarks && item.remarks.toLowerCase().includes(searchTerm))
          );
          if (!matched) return false;
        }

        return true;
      });

      renderEnquiriesTable(currentFilteredList);
      updateSummaryChips(currentFilteredList);
    }

    function renderEnquiriesTable(list) {
      const $tbody = $("#enquiryTableBody");
      $tbody.empty();

      if (list.length === 0) {
        $tbody.html(`
          <tr>
            <td colspan="9" class="text-center text-muted py-5">
              <i class="fa fa-folder-open fa-2x text-secondary mb-2"></i>
              <p class="mb-0">No product enquiries found matching the selected filters.</p>
            </td>
          </tr>
        `);
        $("#enquiryTableFooter").hide();
        return;
      }

      let html = "";
      let totalQty = 0;
      let pendingCount = 0;
      let inReviewCount = 0;
      let convertedCount = 0;
      let rejectedCount = 0;

      list.forEach(function (row, idx) {
        let statusClass = "status-pending";
        let statusIcon = "fa-clock";
        const st = (row.status || 'Pending').trim();

        if (st === "In Review") {
          statusClass = "status-in-review";
          statusIcon = "fa-search";
          inReviewCount++;
        } else if (st === "Converted") {
          statusClass = "status-converted";
          statusIcon = "fa-check-circle";
          convertedCount++;
        } else if (st === "Rejected") {
          statusClass = "status-rejected";
          statusIcon = "fa-times-circle";
          rejectedCount++;
        } else {
          pendingCount++;
        }

        const rawQty = parseFloat(row.quantity) || 0;
        totalQty += rawQty;

        const formattedEnqDate = formatEnquiryDate(row.created_at);
        const formattedNeedDate = formatEnquiryDate(row.expected_date);
        const locationStr = [row.cityname, row.districtname, row.statename].filter(Boolean).join(", ") || "N/A";
        const displayQty = rawQty > 0 ? rawQty.toLocaleString() : (row.quantity || 'N/A');

        html += `
          <tr>
            <td class="text-center font-monospace text-muted">${idx + 1}</td>
            <td>
              <div class="fw-bold text-dark font-monospace">${row.enquiry_no || 'ENQ-' + row.id}</div>
              <small class="text-muted"><i class="fa fa-calendar-alt me-1"></i>${formattedEnqDate}</small>
            </td>
            <td>
              <div class="fw-bold text-dark">${row.clientname}</div>
              <div><a href="tel:${row.mobileno}" class="text-decoration-none text-primary small"><i class="fa fa-phone me-1"></i>${row.mobileno}</a></div>
              ${row.emailid ? `<small class="text-muted"><i class="fa fa-envelope me-1"></i>${row.emailid}</small>` : ''}
            </td>
            <td>
              <div class="fw-semibold text-secondary"><i class="fa fa-map-marker-alt text-danger me-1"></i>${locationStr}</div>
              ${row.address ? `<small class="text-muted text-truncate d-inline-block" style="max-width: 200px;" title="${row.address}">${row.address.replace(/\n/g, ', ')}</small>` : ''}
            </td>
            <td>
              <span class="product-demanded-tag"><i class="fa fa-box-open me-1"></i>${row.product_name}</span>
            </td>
            <td>
              <div><strong>Qty:</strong> ${displayQty}</div>
              <small class="text-muted"><strong>Need By:</strong> ${formattedNeedDate}</small>
            </td>
            <td>
              <span class="text-muted small" title="${row.remarks || ''}">${row.remarks ? (row.remarks.length > 50 ? row.remarks.substring(0, 50) + '...' : row.remarks) : '—'}</span>
            </td>
            <td class="text-center">
              <span class="status-badge ${statusClass}">
                <i class="fa ${statusIcon}"></i> ${row.status || 'Pending'}
              </span>
            </td>
            <td class="text-center">
              <div class="d-flex align-items-center justify-content-center gap-1">
                <button class="btn btn-sm btn-outline-primary btn-action-icon" onclick="viewEnquiryDetails(${row.id})" title="View Complete Details">
                  <i class="fa fa-eye"></i>
                </button>
                <button class="btn btn-sm btn-outline-warning btn-action-icon text-dark" onclick="openStatusUpdateModal(${row.id}, '${row.status || 'Pending'}', '${(row.remarks || '').replace(/'/g, "\\'")}')" title="Update Status">
                  <i class="fa fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger btn-action-icon" onclick="deleteEnquiry(${row.id})" title="Delete Enquiry">
                  <i class="fa fa-trash-alt"></i>
                </button>
              </div>
            </td>
          </tr>
        `;
      });

      $tbody.html(html);

      // Update Table Footer Summary
      $("#footTotalRows").text(list.length);
      $("#footTotalQty").text(totalQty.toLocaleString());
      let breakdownHtml = `
        <span class="badge bg-warning text-dark me-1"><i class="fa fa-clock me-1"></i>Pending: ${pendingCount}</span>
        <span class="badge bg-info text-white me-1"><i class="fa fa-search me-1"></i>In Review: ${inReviewCount}</span>
        <span class="badge bg-success text-white me-1"><i class="fa fa-check me-1"></i>Converted: ${convertedCount}</span>
        <span class="badge bg-danger text-white"><i class="fa fa-times me-1"></i>Rejected: ${rejectedCount}</span>
      `;
      $("#footStatusBreakdown").html(breakdownHtml);
      $("#enquiryTableFooter").show();
    }

    function updateSummaryChips(list) {
      $("#chipTotalEnquiries").text(list.length);
      
      let totalQty = 0;
      let pendingCount = 0;
      let inReviewCount = 0;
      let convertedCount = 0;
      let rejectedCount = 0;

      list.forEach(i => {
        totalQty += parseFloat(i.quantity) || 0;
        const st = (i.status || 'Pending').trim();
        if (st === 'In Review') inReviewCount++;
        else if (st === 'Converted') convertedCount++;
        else if (st === 'Rejected') rejectedCount++;
        else pendingCount++;
      });

      $("#chipTotalQuantity").text(totalQty.toLocaleString());
      $("#chipPendingEnquiries").text(pendingCount);
      $("#chipInReviewEnquiries").text(inReviewCount);
      $("#chipConvertedEnquiries").text(convertedCount);
      $("#chipRejectedEnquiries").text(rejectedCount);
    }

    function resetFilters() {
      $("#enquirySearchInput").val("");
      $("#clearSearchBtn").hide();
      $("#statusFilter").val("");
      $("#stateFilter").val("");
      $("#districtFilter").val("");
      $("#cityFilter").val("");
      $("#productFilter").val(null).trigger('change.select2');
      setDefaultDateRange();
      loadEnquiriesGrid();
    }

    function viewEnquiryDetails(id) {
      const item = globalEnquiries.find(i => i.id == id);
      if (!item) return;

      $("#viewModalSub").text("Tracking ID: " + (item.enquiry_no || item.id));

      const formattedEnqDate = formatEnquiryDate(item.created_at);
      const formattedNeedDate = formatEnquiryDate(item.expected_date);

      let content = `
        <div class="row g-3">
          <div class="col-md-6">
            <div class="p-3 rounded-3 border bg-light">
              <h6 class="fw-bold text-primary mb-3"><i class="fa fa-user me-1"></i> Client Details</h6>
              <p class="mb-1"><strong>Client Name:</strong> ${item.clientname}</p>
              <p class="mb-1"><strong>Mobile No:</strong> <a href="tel:${item.mobileno}">${item.mobileno}</a></p>
              <p class="mb-1"><strong>Email:</strong> ${item.emailid || 'N/A'}</p>
              <p class="mb-1"><strong>Client ID:</strong> ${item.clientid || 'New Client'}</p>
              <p class="mb-0"><strong>Enquiry Date:</strong> ${formattedEnqDate}</p>
            </div>
          </div>
          <div class="col-md-6">
            <div class="p-3 rounded-3 border bg-light">
              <h6 class="fw-bold text-primary mb-3"><i class="fa fa-map-marked-alt me-1"></i> Delivery & Geo Info</h6>
              <p class="mb-1"><strong>State:</strong> ${item.statename || 'N/A'}</p>
              <p class="mb-1"><strong>District:</strong> ${item.districtname || 'N/A'}</p>
              <p class="mb-1"><strong>Nearest City:</strong> ${item.cityname || 'N/A'}</p>
              <p class="mb-0"><strong>Full Address:</strong><br><span class="text-muted">${(item.address || 'N/A').replace(/\n/g, '<br>')}</span></p>
            </div>
          </div>
          <div class="col-md-12">
            <div class="p-3 rounded-3 border" style="background-color: #fffdf5; border-color: #fef08a !important;">
              <h6 class="fw-bold text-warning-emphasis mb-3"><i class="fa fa-box-open me-1"></i> Demanded Product Requirement</h6>
              <p class="mb-2 fs-5"><strong>Product Name:</strong> <span class="product-demanded-tag">${item.product_name}</span></p>
              <div class="row g-2 mb-2">
                <div class="col-md-6"><strong>Required Quantity:</strong> ${item.quantity ? Number(item.quantity).toLocaleString() : 'N/A'}</div>
                <div class="col-md-6"><strong>Expected Delivery Date:</strong> ${formattedNeedDate}</div>
              </div>
              <p class="mb-0"><strong>Specific Remarks / Preferences:</strong><br><span class="text-muted">${item.remarks || 'No additional remarks.'}</span></p>
            </div>
          </div>
        </div>
      `;

      $("#viewEnquiryModalBody").html(content);
      const modal = new bootstrap.Modal(document.getElementById('viewEnquiryModal'));
      modal.show();
    }

    function openStatusUpdateModal(id, currentStatus, currentRemarks) {
      $("#status_enquiry_id").val(id);
      $("#status_select_val").val(currentStatus || 'Pending');
      $("#status_remarks_val").val(currentRemarks || '');

      const modal = new bootstrap.Modal(document.getElementById('updateStatusModal'));
      modal.show();
    }

    $("#updateStatusForm").on("submit", function (e) {
      e.preventDefault();
      const id = $("#status_enquiry_id").val();
      const status = $("#status_select_val").val();
      const remarks = $("#status_remarks_val").val();
      const routurl = $("body").data("routurl") || "../";

      const $btn = $("#btnSaveStatus");
      $btn.prop("disabled", true).text("Updating...");

      $.ajax({
        url: routurl + "krf-orderandsales/modal/client_modal.php?action=updateProductEnquiryStatus",
        type: "POST",
        dataType: "json",
        data: { id: id, status: status, remarks: remarks },
        success: function (res) {
          $btn.prop("disabled", false).text("Update Status");
          if (res && res.status === "success") {
            const modalEl = document.getElementById('updateStatusModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
            Swal.fire({
              icon: "success",
              title: "Status Updated",
              text: res.message,
              timer: 1500,
              showConfirmButton: false
            });
            loadEnquiriesGrid();
          } else {
            Swal.fire("Error", res.message || "Failed to update status", "error");
          }
        },
        error: function () {
          $btn.prop("disabled", false).text("Update Status");
          Swal.fire("Error", "Server error while updating status", "error");
        }
      });
    });

    function deleteEnquiry(id) {
      Swal.fire({
        title: "Are you sure?",
        text: "This product enquiry record will be permanently deleted.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#ef4444",
        cancelButtonColor: "#64748b",
        confirmButtonText: "Yes, delete it"
      }).then((result) => {
        if (result.isConfirmed) {
          const routurl = $("body").data("routurl") || "../";
          $.ajax({
            url: routurl + "krf-orderandsales/modal/client_modal.php?action=deleteProductEnquiry",
            type: "POST",
            dataType: "json",
            data: { id: id },
            success: function (res) {
              if (res && res.status === "success") {
                Swal.fire({
                  icon: "success",
                  title: "Deleted!",
                  text: res.message,
                  timer: 1500,
                  showConfirmButton: false
                });
                loadEnquiriesGrid();
              } else {
                Swal.fire("Error", res.message || "Failed to delete", "error");
              }
            },
            error: function () {
              Swal.fire("Error", "Server error while deleting enquiry", "error");
            }
          });
        }
      });
    }

    function exportEnquiriesToCSV() {
      const listToExport = currentFilteredList.length > 0 ? currentFilteredList : globalEnquiries;
      if (!listToExport || listToExport.length === 0) {
        Swal.fire("No Data", "No enquiry records to export.", "info");
        return;
      }

      let csv = "Enquiry No,Enquiry Date,Client Name,Mobile,Email,State,District,City,Address,Product Name,Quantity,Expected Date,Remarks,Status\n";
      listToExport.forEach(function (r) {
        const row = [
          `"${r.enquiry_no || ''}"`,
          `"${formatEnquiryDate(r.created_at)}"`,
          `"${(r.clientname || '').replace(/"/g, '""')}"`,
          `"${r.mobileno || ''}"`,
          `"${r.emailid || ''}"`,
          `"${r.statename || ''}"`,
          `"${r.districtname || ''}"`,
          `"${r.cityname || ''}"`,
          `"${(r.address || '').replace(/"/g, '""').replace(/\n/g, ' ')}"`,
          `"${(r.product_name || '').replace(/"/g, '""')}"`,
          `"${r.quantity || ''}"`,
          `"${formatEnquiryDate(r.expected_date)}"`,
          `"${(r.remarks || '').replace(/"/g, '""')}"`,
          `"${r.status || 'Pending'}"`
        ];
        csv += row.join(",") + "\n";
      });

      const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
      const link = document.createElement("a");
      const url = URL.createObjectURL(blob);
      link.setAttribute("href", url);
      link.setAttribute("download", `Product_Enquiries_${new Date().toISOString().slice(0,10)}.csv`);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }

    $(document).ready(function () {
      // Initialize Select2 for multi-select product filter
      $('.select2').select2({
        closeOnSelect: false,
        allowClear: true
      });

      // Set default date range to current month
      setDefaultDateRange();

      // Load initial enquiries
      loadEnquiriesGrid();

      // Filter event handlers
      $("#dateFromFilter, #dateToFilter").on("change", function () {
        loadEnquiriesGrid();
      });

      $("#stateFilter").on("change", function () {
        const st = $(this).val();
        updateDistrictFilterOptions(globalEnquiries, st, "");
        updateCityFilterOptions(globalEnquiries, st, "", "");
        applyAllFilters();
      });

      $("#districtFilter").on("change", function () {
        const st = $("#stateFilter").val();
        const dst = $(this).val();
        updateCityFilterOptions(globalEnquiries, st, dst, "");
        applyAllFilters();
      });

      $("#cityFilter, #statusFilter").on("change", function () {
        applyAllFilters();
      });

      $("#productFilter").on("change", function () {
        applyAllFilters();
      });

      // Real-time search filter
      $("#enquirySearchInput").on("input", function () {
        const val = $(this).val();
        if (val) {
          $("#clearSearchBtn").show();
        } else {
          $("#clearSearchBtn").hide();
        }
        applyAllFilters();
      });

      $("#clearSearchBtn").on("click", function () {
        $("#enquirySearchInput").val("");
        $(this).hide();
        applyAllFilters();
      });
    });
  </script>
</body>
</html>
