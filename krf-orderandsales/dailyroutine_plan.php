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
  include('functions/seed_functions.php');
  include('functions/dailyroutine_functions.php');
  $key = $_GET['k'];
  $key_decoded = base64url_decode($key);
  $key_parts = explode("|", $key_decoded);
  $checkkey = base64url_decode($key_parts[count($key_parts) - 1]);
  $actkey = "";
  for ($i = 0; $i < count($key_parts) - 1; $i++) {
    $actkey = $actkey ? $actkey . "|" . $key_parts[$i] : $key_parts[$i];
  }
  if ($actkey != $checkkey) {
    echo "<div class=\"col-md-12 text-center black\"><h3>OOPS!! Something went wrong. Please try again later.</h3></div>";
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
  $rooturl = $prefix;
} else {
  require('../header.php');
  include('functions/product_functions.php');
  include('functions/vendor_functions.php');
  include('functions/seed_functions.php');
  include('functions/dailyroutine_functions.php');
  $rooturl;
}

$mdid = $_GET['mdid'] ?? '';
$dddr = loginmodulesdetails_frommaster($authconn, $companyid, $subcompanyid, $projectid, "moduleid", $mdid);
$groupid = (count($dddr) > 0) ? $dddr[0]['groupid'] : '';
?>

<!-- FontAwesome & Google Fonts -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
  :root {
    --dr-primary: #0f766e;
    --dr-primary-dark: #115e59;
    --dr-primary-light: #ccfbf1;
    --dr-accent: #0284c7;
    --dr-bg-light: #f8fafc;
    --dr-border: #e2e8f0;
    --dr-text-main: #0f172a;
    --dr-text-muted: #64748b;
  }

  .daily-routine-container {
    font-family: 'Inter', sans-serif;
    background-color: #f1f5f9;
    min-height: 100vh;
    padding: 1.25rem 1.5rem 3rem;
  }

  /* ── Header Banner ── */
  .dr-main-banner {
    background: linear-gradient(135deg, #0f766e 0%, #115e59 55%, #0e7490 100%);
    color: #ffffff;
    padding: 1.35rem 1.75rem;
    border-radius: 16px;
    box-shadow: 0 10px 25px -5px rgba(15, 118, 110, 0.25);
    margin-bottom: 1.25rem;
    border: 1px solid rgba(255, 255, 255, 0.15);
  }

  .dr-banner-title {
    font-family: 'Outfit', sans-serif;
    font-size: 1.4rem;
    font-weight: 700;
    letter-spacing: -0.2px;
  }

  .dr-stat-badge {
    background: rgba(255, 255, 255, 0.16);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.25);
    color: #ffffff;
    border-radius: 20px;
    padding: 6px 14px;
    font-size: 0.825rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }

  .dr-banner-btn {
    border-radius: 20px !important;
    background: rgba(0, 0, 0, 0.25) !important;
    color: #ffffff !important;
    border: 1.5px solid rgba(255, 255, 255, 0.4) !important;
    font-size: 0.825rem !important;
    font-weight: 600 !important;
    padding: 6px 14px !important;
    transition: all 0.2s ease !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 6px !important;
  }
  .dr-banner-btn:hover, .dr-banner-btn:focus, .dr-banner-btn:active {
    background: rgba(0, 0, 0, 0.45) !important;
    color: #ffffff !important;
    border-color: rgba(255, 255, 255, 0.7) !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
  }
  .dr-banner-btn span {
    color: #ffffff !important;
  }

  /* ── Form Controls & Dropdown Selects ── */
  .dr-custom-select, .dr-form-control {
    height: 40px !important;
    font-size: 0.88rem !important;
    font-weight: 500 !important;
    color: #0f172a !important;
    border: 1.5px solid #cbd5e1 !important;
    border-radius: 8px !important;
    background-color: #ffffff !important;
    padding: 6px 12px !important;
    transition: all 0.2s ease !important;
    width: 100% !important;
  }

  .dr-custom-select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e") !important;
    background-repeat: no-repeat !important;
    background-position: right 0.75rem center !important;
    background-size: 16px 12px !important;
    appearance: none !important;
    -webkit-appearance: none !important;
    padding-right: 2.2rem !important;
    color: #0f172a !important;
  }

  .dr-custom-select:focus, .dr-form-control:focus {
    border-color: #0f766e !important;
    box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.15) !important;
    outline: none !important;
    color: #0f172a !important;
    background-color: #ffffff !important;
  }

  select option, .dr-custom-select option, .form-control option {
    color: #0f172a !important;
    background-color: #ffffff !important;
    font-weight: 500 !important;
    padding: 6px 10px !important;
  }

  .dr-field-label {
    font-size: 0.82rem;
    font-weight: 700;
    color: #334155;
    margin-bottom: 5px;
    display: block;
    text-transform: uppercase;
    letter-spacing: 0.3px;
  }

  /* ── Filter Card ── */
  .dr-card {
    background: #ffffff;
    border-radius: 14px;
    border: 1px solid var(--dr-border);
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.04);
    margin-bottom: 1.25rem;
  }

  .dr-table-wrap {
    overflow-x: auto;
    border-radius: 0 0 14px 14px;
  }

  .dr-table th {
    background-color: #f8fafc;
    color: #334155;
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #e2e8f0;
    padding: 12px 14px;
    vertical-align: middle;
  }

  .dr-table td {
    padding: 12px 14px;
    vertical-align: middle !important;
    font-size: 0.88rem;
    color: #1e293b;
    border-bottom: 1px solid #f1f5f9;
  }

  .dr-table tr:hover td {
    background-color: #f8fafc;
  }

  /* Badges & Tags */
  .badge-crop {
    background-color: #f0fdf4;
    color: #166534;
    border: 1px solid #bbf7d0;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 0.78rem;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin: 2px;
  }

  .badge-seed {
    background-color: #fefce8;
    color: #854d0e;
    border: 1px solid #fef08a;
    font-weight: 600;
    padding: 4px 9px;
    border-radius: 8px;
    font-size: 0.76rem;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin: 2px;
  }

  .badge-days {
    background: #f0fdfa;
    color: #0f766e;
    border: 1px solid #99f6e4;
    font-weight: 700;
    font-size: 0.8rem;
    padding: 4px 10px;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
  }

  .badge-setup-complete {
    background-color: #dcfce7 !important;
    color: #15803d !important;
    border: 1px solid #86efac !important;
    font-weight: 700;
    font-size: 0.78rem;
    padding: 4px 10px;
    border-radius: 20px;
    white-space: nowrap !important;
    display: inline-flex;
    align-items: center;
    gap: 4px;
  }

  .badge-setup-pending {
    background-color: #fef3c7 !important;
    color: #92400e !important;
    border: 1px solid #fde68a !important;
    font-weight: 700;
    font-size: 0.78rem;
    padding: 4px 10px;
    border-radius: 20px;
    white-space: nowrap !important;
    display: inline-flex;
    align-items: center;
    gap: 4px;
  }

  .badge-status-active {
    background-color: #ecfdf5 !important;
    color: #047857 !important;
    border: 1px solid #a7f3d0 !important;
    font-weight: 700;
    font-size: 0.78rem;
    padding: 4px 10px;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
  }

  .badge-status-draft {
    background-color: #fef9c3 !important;
    color: #854d0e !important;
    border: 1px solid #fef08a !important;
    font-weight: 700;
    font-size: 0.78rem;
    padding: 4px 10px;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
  }

  .badge-status-inactive {
    background-color: #f1f5f9 !important;
    color: #475569 !important;
    border: 1px solid #cbd5e1 !important;
    font-weight: 700;
    font-size: 0.78rem;
    padding: 4px 10px;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
  }

  .dr-action-btn {
    width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px !important;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    transition: all 0.2s ease;
    font-size: 0.85rem;
    margin: 0 2px;
    text-decoration: none !important;
  }

  .dr-action-btn-edit { color: #0284c7; border-color: #bae6fd; background: #f0f9ff; }
  .dr-action-btn-edit:hover { background: #0284c7; color: #ffffff; border-color: #0284c7; }
  .dr-action-btn-timeline { color: #0d9488; border-color: #99f6e4; background: #f0fdfa; }
  .dr-action-btn-timeline:hover { background: #0d9488; color: #ffffff; border-color: #0d9488; }
  .dr-action-btn-clone { color: #6366f1; border-color: #c7d2fe; background: #eef2ff; }
  .dr-action-btn-clone:hover { background: #6366f1; color: #ffffff; border-color: #6366f1; }
  .dr-action-btn-delete { color: #ef4444; border-color: #fecaca; background: #fef2f2; }
  .dr-action-btn-delete:hover { background: #ef4444; color: #ffffff; border-color: #ef4444; }

  /* ── Modal Custom Styling ── */
  .modal-xl-custom {
    max-width: 960px !important;
    width: 92% !important;
    margin: 1.75rem auto !important;
  }

  .modal-dialog {
    margin: 1.75rem auto;
  }

  .day-matrix-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    margin-bottom: 16px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
    transition: all 0.2s ease;
    overflow: hidden;
  }

  .day-matrix-card:hover {
    border-color: #0f766e;
    box-shadow: 0 4px 14px rgba(15, 118, 110, 0.1);
  }

  .day-matrix-card-header {
    background: #f8fafc;
    padding: 10px 14px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
  }

  .day-badge-header {
    background: linear-gradient(135deg, #0f766e 0%, #115e59 100%);
    color: #ffffff;
    font-weight: 800;
    border-radius: 8px;
    padding: 6px 14px;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
    white-space: nowrap;
    box-shadow: 0 2px 4px rgba(15, 118, 110, 0.25);
  }

  .dr-task-title-input {
    flex: 1;
    font-weight: 600;
    font-size: 0.88rem;
    color: #0f172a;
    border: 1.5px solid #cbd5e1 !important;
    border-radius: 8px !important;
    padding: 6px 12px !important;
    background: #ffffff !important;
    transition: all 0.2s ease;
  }

  .dr-task-title-input:focus {
    border-color: #0f766e !important;
    box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.15) !important;
  }

  .dr-field-label {
    font-weight: 700;
    font-size: 0.75rem;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
  }

  .day-form-grid {
    padding: 16px 18px !important;
  }

  .day-form-grid .row > [class*="col-"] {
    margin-bottom: 12px;
  }

  .dr-form-control, .dr-custom-select {
    height: 38px !important;
    font-size: 0.85rem !important;
    font-weight: 500 !important;
    color: #1e293b !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 8px !important;
    padding: 6px 12px !important;
    background-color: #ffffff !important;
    transition: all 0.2s ease !important;
    width: 100% !important;
  }

  .dr-custom-select {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e") !important;
    background-repeat: no-repeat !important;
    background-position: right 0.75rem center !important;
    background-size: 14px 10px !important;
    padding-right: 2rem !important;
    cursor: pointer;
  }

  .dr-form-control:focus, .dr-custom-select:focus {
    border-color: #0f766e !important;
    box-shadow: 0 0 0 2.5px rgba(15, 118, 110, 0.15) !important;
    outline: none !important;
  }

  .dr-btn-copy {
    background-color: #eff6ff !important;
    color: #1d4ed8 !important;
    border: 1px solid #bfdbfe !important;
    border-radius: 6px !important;
    font-size: 0.78rem !important;
    font-weight: 600 !important;
    padding: 4px 10px !important;
    transition: all 0.15s ease !important;
    display: inline-flex;
    align-items: center;
    gap: 4px;
  }

  .dr-btn-copy:hover {
    background-color: #dbeafe !important;
    border-color: #93c5fd !important;
    text-decoration: none;
  }

  .dr-btn-clear {
    background-color: #fef2f2 !important;
    color: #dc2626 !important;
    border: 1px solid #fecaca !important;
    border-radius: 6px !important;
    font-size: 0.78rem !important;
    font-weight: 600 !important;
    padding: 4px 10px !important;
    transition: all 0.15s ease !important;
    display: inline-flex;
    align-items: center;
  }

  .dr-btn-clear:hover {
    background-color: #fee2e2 !important;
    border-color: #fca5a5 !important;
    text-decoration: none;
  }

  .seed-check-chip {
    cursor: pointer;
    user-select: none;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    border-radius: 8px;
    padding: 6px 12px;
    font-size: 0.825rem;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin: 3px;
  }

  .seed-check-chip.selected {
    background-color: #0f766e;
    color: #ffffff;
    border-color: #0f766e;
  }

  .seed-check-chip input[type="checkbox"] {
    display: none;
  }

  .subcat-check-chip {
    cursor: pointer;
    user-select: none;
    border: 1.5px solid #93c5fd;
    background: #eff6ff;
    color: #1e40af;
    border-radius: 8px;
    padding: 6px 12px;
    font-size: 0.85rem;
    font-weight: 600;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin: 3px;
  }

  .subcat-check-chip.selected {
    background-color: #1d4ed8;
    color: #ffffff;
    border-color: #1d4ed8;
  }

  .subcat-check-chip input[type="checkbox"] {
    display: none;
  }

  .day-stepper-btn {
    width: 38px;
    height: 38px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.1rem;
  }
</style>

<div class="daily-routine-container" 
     data-projectid="<?php echo htmlspecialchars($projectid); ?>" 
     data-companyid="<?php echo htmlspecialchars($companyid); ?>" 
     data-subcompanyid="<?php echo htmlspecialchars($subcompanyid); ?>" 
     data-loginid="<?php echo htmlspecialchars($loginid); ?>" 
     data-routurl="<?php echo htmlspecialchars($rooturl); ?>">

  <!-- 1. Header Banner -->
  <div class="dr-main-banner">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1">
          <i class="fa-solid fa-calendar-check fs-3 text-white"></i>
          <span class="dr-banner-title">Daily Routine Plan Master</span>
        </div>
        <p class="mb-0 text-white-50 small">Configure day-wise crop nursery routines, irrigation, nutrition, spray, and hardening schedules by Subcategory and Seed IDs.</p>
      </div>

      <!-- KPI Summary Chips -->
      <div class="d-flex align-items-center flex-wrap gap-2">
        <div class="dr-stat-badge">
          <i class="fa-solid fa-list-check text-warning"></i> Total Plans: <span id="kpiTotalPlans">0</span>
        </div>
        <div class="dr-stat-badge">
          <i class="fa-solid fa-seedling text-success"></i> Subcategories: <span id="kpiTotalSubCats">0</span>
        </div>
        <div class="dr-stat-badge">
          <i class="fa-solid fa-wheat-awn text-info"></i> Seeds Mapped: <span id="kpiTotalSeeds">0</span>
        </div>
        <button type="button" class="btn dr-banner-btn shadow-sm" onclick="openManageDropdownsModal()">
          <i class="fa-solid fa-sliders text-warning"></i> <span>Dropdown Options</span>
        </button>
        <button type="button" class="btn btn-light fw-bold text-dark px-3 py-2 shadow-sm d-flex align-items-center gap-2" style="border-radius: 20px;" onclick="openRoutinePlanModal()">
          <i class="fa-solid fa-plus-circle text-success fs-5"></i> Create Routine Plan
        </button>
      </div>
    </div>
  </div>

  <!-- 2. Filter Bar Card -->
  <div class="dr-card p-3">
    <div class="row align-items-center g-2">
      <!-- Search -->
      <div class="col-md-4">
        <div class="input-group">
          <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-search text-muted"></i></span>
          <input type="search" class="form-control border-start-0" id="filterSearchInput" placeholder="Search by plan name, code, crop..." onkeyup="filterRoutinePlans()">
        </div>
      </div>

      <!-- Subcategory Filter Dropdown -->
      <div class="col-md-3">
        <select class="dr-custom-select" id="filterSubCategorySelect" onchange="loadRoutinePlans()">
          <option value="">-- All Subcategories --</option>
        </select>
      </div>

      <!-- Status Filter -->
      <div class="col-md-2">
        <select class="dr-custom-select" id="filterStatusSelect" onchange="loadRoutinePlans()">
          <option value="">All Statuses</option>
          <option value="Active" selected>Active</option>
          <option value="Inactive">Inactive</option>
          <option value="Draft">Draft</option>
        </select>
      </div>

      <!-- Actions -->
      <div class="col-md-3 text-end d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-outline-secondary btn-sm px-3 py-2 fw-semibold" onclick="loadRoutinePlans()" title="Refresh List">
          <i class="fa-solid fa-sync-alt" id="refreshSpin"></i> Refresh
        </button>
        <button type="button" class="btn btn-outline-success btn-sm px-3 py-2 fw-semibold" onclick="exportRoutinePlansToExcel()">
          <i class="fa-solid fa-file-excel me-1"></i> Export Excel
        </button>
      </div>
    </div>
  </div>

  <!-- 3. Routine Plans Master Table Card -->
  <div class="dr-card">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
      <h5 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-layer-group text-teal me-2" style="color: #0f766e;"></i>Configured Daily Routine Plans</h5>
      <span class="text-muted small">Showing <span id="showingPlansCount">0</span> plan(s)</span>
    </div>

    <div class="dr-table-wrap">
      <table class="table dr-table table-hover mb-0" id="dailyRoutinePlanTbl">
        <thead>
          <tr>
            <th class="text-center" style="width: 50px;">S.No</th>
            <th style="min-width: 120px;">Plan Code</th>
            <th style="min-width: 180px;">Plan Name</th>
            <th style="min-width: 200px;">Mapped Subcategories</th>
            <th style="min-width: 200px;">Mapped Seed IDs</th>
            <th class="text-center" style="width: 130px;">Plan Duration</th>
            <th class="text-center" style="width: 120px;">Days Setup</th>
            <th class="text-center" style="width: 100px;">Status</th>
            <th class="text-center" style="width: 160px;">Actions</th>
          </tr>
        </thead>
        <tbody id="dailyRoutinePlanTbody">
          <tr>
            <td colspan="9" class="text-center py-4 text-muted">
              <i class="fa-solid fa-spinner fa-spin me-2"></i> Loading daily routine plans...
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- ========================================================================= -->
<!-- 4. MODAL: Create / Edit Daily Routine Plan Builder (With Day Matrix)      -->
<!-- ========================================================================= -->
<div class="modal fade" id="routinePlanModal" tabindex="-1" role="dialog" aria-labelledby="routinePlanModalLabel" aria-hidden="true" data-backdrop="static" data-bs-backdrop="static">
  <div class="modal-dialog modal-xl modal-xl-custom modal-dialog-scrollable" role="document">
    <div class="modal-content border-0 shadow">
      
      <!-- Modal Header -->
      <div class="modal-header text-white" style="background: linear-gradient(135deg, #0f766e 0%, #115e59 100%);">
        <div class="d-flex align-items-center gap-2">
          <i class="fa-solid fa-calendar-days fs-4 me-2"></i>
          <div>
            <h5 class="modal-title fw-bold mb-0" id="routinePlanModalLabel">Daily Routine Plan Builder</h5>
            <small class="text-white-50">Map multi-subcategories, associate seed IDs, and configure day-wise routine activities</small>
          </div>
        </div>
        <button type="button" class="close text-white opacity-100" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" onclick="hideAppModal('routinePlanModal')" style="font-size: 1.7rem; color: #ffffff !important; opacity: 1 !important; text-shadow: none; cursor: pointer; border: none; background: transparent;">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body p-4" style="background-color: #f8fafc;">
        <form id="routinePlanForm" onsubmit="return false;">
          <input type="hidden" id="modalRoutinePlanID" value="0">
          <input type="hidden" id="modalPlanCode" value="">

          <!-- Section 1: Header Info & Subcategories / Seeds -->
          <div class="card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-body p-3">
              <h6 class="fw-bold text-dark mb-3 border-bottom pb-2">
                <i class="fa-solid fa-circle-info text-teal me-2" style="color: #0f766e;"></i>Step 1: Plan Profile & Target Crops
              </h6>
              
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="dr-field-label">Plan Name <span class="text-danger ms-1">*</span></label>
                  <input type="text" class="dr-form-control fw-semibold" id="modalPlanName" placeholder="e.g. Standard 25-Day Tomato & Brinjal Nursery Plan" required>
                </div>

                <div class="col-md-3">
                  <label class="dr-field-label">Plan Duration (Days) <span class="text-danger ms-1">*</span></label>
                  <div class="input-group">
                    <button class="btn btn-outline-secondary day-stepper-btn" type="button" onclick="adjustDurationDays(-1)" title="Decrease 1 Day">-</button>
                    <input type="number" class="form-control text-center fw-bold fs-6" id="modalTotalPlanDays" value="25" min="1" max="90" onchange="onPlanDaysInputChange()" style="height: 38px;">
                    <button class="btn btn-outline-secondary day-stepper-btn" type="button" onclick="adjustDurationDays(1)" title="Increase 1 Day">+</button>
                  </div>
                  <small class="text-muted" style="font-size: 0.72rem;">Default 25 days (adjustable 1-90)</small>
                </div>

                <div class="col-md-3">
                  <label class="dr-field-label">Status</label>
                  <select class="dr-custom-select fw-semibold" id="modalPlanStatus">
                    <option value="Active" selected>Active</option>
                    <option value="Inactive">Inactive</option>
                    <option value="Draft">Draft</option>
                  </select>
                </div>

                <!-- Multi-Subcategory Selector -->
                <div class="col-12">
                  <label class="form-label fw-semibold small text-muted d-flex justify-content-between align-items-center">
                    <span>Select Subcategories (One plan can cover multiple subcategories) <span class="text-danger">*</span></span>
                    <div>
                      <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none me-2" onclick="selectAllSubCats(true)">Select All</button>
                      <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-danger" onclick="selectAllSubCats(false)">Deselect All</button>
                    </div>
                  </label>
                  <div class="p-2 border rounded-3 bg-white" id="subcatChipsContainer" style="max-height: 120px; overflow-y: auto;">
                    <!-- Dynamically populated subcategory chips -->
                  </div>
                </div>

                <!-- Dynamic Seed IDs Selector -->
                <div class="col-12">
                  <label class="form-label fw-semibold small text-muted d-flex justify-content-between align-items-center">
                    <span>Select Mapped Seed IDs for this Routine Plan <span class="badge bg-warning text-dark ms-2" id="selectedSeedsCountBadge">0 selected</span></span>
                    <div>
                      <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none me-2" onclick="selectAllSeeds(true)">Select All Seeds</button>
                      <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-danger" onclick="selectAllSeeds(false)">Deselect All</button>
                    </div>
                  </label>
                  <div class="input-group input-group-sm mb-2">
                    <span class="input-group-text bg-white"><i class="fa-solid fa-filter text-muted"></i></span>
                    <input type="text" class="form-control" id="seedSearchFilterInput" placeholder="Quick search seeds by name, variety, brand..." onkeyup="filterSeedChips()">
                  </div>
                  <div class="p-2 border rounded-3 bg-white" id="seedChipsContainer" style="max-height: 140px; overflow-y: auto;">
                    <span class="text-muted small">Please select at least one subcategory above to load seeds.</span>
                  </div>
                </div>

                <div class="col-12">
                  <label class="dr-field-label">Description / Guidelines</label>
                  <textarea class="dr-form-control" id="modalPlanDescription" rows="2" style="height: auto !important;" placeholder="Enter general climate, temperature (26-28°C), RH (80-85%), and nursery care instructions..."></textarea>
                </div>
              </div>
            </div>
          </div>

          <!-- Section 2: Day-by-Day Routine Matrix -->
          <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-3">
              <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom pb-2 mb-3">
                <div>
                  <h6 class="fw-bold text-dark mb-0">
                    <i class="fa-solid fa-list-ol text-teal me-2" style="color: #0f766e;"></i>Step 2: Day-by-Day Routine Schedule
                  </h6>
                  <small class="text-muted">Configure operations for Day 1 to Day <span id="matrixTotalDaysLabel">25</span></small>
                </div>
                
                <div class="d-flex align-items-center gap-2">
                  <button type="button" class="btn btn-outline-primary btn-sm px-3 fw-semibold" onclick="loadStandard25DayPreset()">
                    <i class="fa-solid fa-magic-wand-sparkles me-1"></i> Auto-Fill 25-Day Nursery Template
                  </button>
                  <button type="button" class="btn btn-outline-secondary btn-sm px-2" onclick="clearAllDaysMatrix()" title="Clear day inputs">
                    <i class="fa-solid fa-eraser me-1"></i> Clear
                  </button>
                </div>
              </div>

              <!-- Matrix Container -->
              <div id="dayMatrixRowsContainer">
                <!-- Dynamically generated Day 1 to Day N cards/table -->
              </div>
            </div>
          </div>

        </form>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer bg-white border-top d-flex justify-content-between">
        <button type="button" class="btn btn-secondary px-4 fw-semibold" data-dismiss="modal" data-bs-dismiss="modal" onclick="hideAppModal('routinePlanModal')">Cancel</button>
        <button type="button" class="btn btn-success px-4 fw-bold shadow-sm d-flex align-items-center gap-2" style="background-color: #0f766e; border-color: #0f766e;" onclick="saveDailyRoutinePlan()">
          <i class="fa-solid fa-floppy-disk" id="savePlanSpin"></i> Save Daily Routine Plan
        </button>
      </div>

    </div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- 5. MODAL: View Routine Timeline & Schedule Chart                         -->
<!-- ========================================================================= -->
<div class="modal fade" id="viewTimelineModal" tabindex="-1" role="dialog" aria-labelledby="viewTimelineModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-xl-custom modal-dialog-scrollable" role="document">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-dark text-white">
        <div class="d-flex align-items-center gap-2">
          <i class="fa-solid fa-timeline text-info fs-4 me-2"></i>
          <div>
            <h5 class="modal-title fw-bold mb-0" id="viewTimelineModalLabel">Daily Routine Schedule Chart</h5>
            <small class="text-white-50" id="timelineSubHeader"></small>
          </div>
        </div>
        <button type="button" class="close text-white opacity-100" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" onclick="hideAppModal('viewTimelineModal')" style="font-size: 1.7rem; color: #ffffff !important; opacity: 1 !important; text-shadow: none; cursor: pointer; border: none; background: transparent;">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body p-4 bg-light" id="timelineContentBody">
        <!-- Rendered timeline table -->
      </div>

      <div class="modal-footer bg-white border-top">
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="printTimelineChart()">
          <i class="fa-solid fa-print me-1"></i> Print Schedule
        </button>
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" data-bs-dismiss="modal" onclick="hideAppModal('viewTimelineModal')">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- 6. MODAL: Clone / Duplicate Routine Plan                                 -->
<!-- ========================================================================= -->
<div class="modal fade" id="clonePlanModal" tabindex="-1" role="dialog" aria-labelledby="clonePlanModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-md" role="document">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title fw-bold"><i class="fa-solid fa-copy me-2"></i>Duplicate Routine Plan</h5>
        <button type="button" class="close text-white opacity-100" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" onclick="hideAppModal('clonePlanModal')" style="font-size: 1.7rem; color: #ffffff !important; opacity: 1 !important; text-shadow: none; cursor: pointer; border: none; background: transparent;">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body p-3">
        <input type="hidden" id="cloneSourcePlanID" value="0">
        <div class="mb-3">
          <label class="form-label fw-semibold small text-muted">New Plan Name</label>
          <input type="text" class="form-control" id="cloneNewPlanName" placeholder="Enter plan name">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold small text-muted">Target Subcategory</label>
          <select class="form-select" id="cloneTargetSubCategory">
            <!-- options -->
          </select>
        </div>
        <p class="text-muted small mb-0">This will create a new copy with all day-by-day operations prefilled.</p>
      </div>
      <div class="modal-footer bg-light border-top">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" data-bs-dismiss="modal" onclick="hideAppModal('clonePlanModal')">Cancel</button>
        <button type="button" class="btn btn-info text-white btn-sm fw-bold" onclick="submitClonePlan()">Duplicate Plan</button>
      </div>
    </div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- 7. MODAL: Dynamic Dropdown Options Manager (Single MySQL Table)           -->
<!-- ========================================================================= -->
<div class="modal fade" id="manageDropdownsModal" tabindex="-1" role="dialog" aria-labelledby="manageDropdownsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document" style="max-width: 800px;">
    <div class="modal-content border-0 shadow">
      
      <!-- Modal Header -->
      <div class="modal-header text-white" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
        <div class="d-flex align-items-center gap-2">
          <i class="fa-solid fa-sliders text-warning fs-4 me-2"></i>
          <div>
            <h5 class="modal-title fw-bold mb-0" id="manageDropdownsModalLabel">Manage Routine Dropdown Options</h5>
            <small class="text-white-50">Dynamically add or remove options stored in single MySQL table as JSON</small>
          </div>
        </div>
        <button type="button" class="close text-white opacity-100" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" onclick="hideAppModal('manageDropdownsModal')" style="font-size: 1.7rem; color: #ffffff !important; opacity: 1 !important; text-shadow: none; cursor: pointer; border: none; background: transparent;">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body p-4 bg-light">
        <!-- Navigation Pills for Dropdown Groups -->
        <ul class="nav nav-pills nav-fill mb-3 bg-white p-2 rounded-3 border shadow-sm" id="dropdownGroupTabs" role="tablist">
          <li class="nav-item">
            <a class="nav-link active fw-bold small" id="tab-growth_stage" href="javascript:void(0)" onclick="switchDropdownGroupTab('growth_stage')">
              <i class="fa-solid fa-seedling text-teal me-1" style="color: #0f766e;"></i> Growth Stages
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link fw-bold small" id="tab-activity_type" href="javascript:void(0)" onclick="switchDropdownGroupTab('activity_type')">
              <i class="fa-solid fa-bolt text-warning me-1"></i> Activity Types
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link fw-bold small" id="tab-application_method" href="javascript:void(0)" onclick="switchDropdownGroupTab('application_method')">
              <i class="fa-solid fa-shower text-info me-1"></i> Methods
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link fw-bold small" id="tab-time_slot" href="javascript:void(0)" onclick="switchDropdownGroupTab('time_slot')">
              <i class="fa-solid fa-clock text-primary me-1"></i> Time Slots
            </a>
          </li>
        </ul>

        <!-- Add New Option Input Strip -->
        <div class="card border-0 shadow-sm rounded-3 mb-3">
          <div class="card-body p-3">
            <label class="dr-field-label mb-2" id="currentGroupAddLabel">Add New Option to Growth Stages</label>
            <div class="input-group">
              <input type="text" class="form-control dr-form-control" id="newOptionInput" placeholder="Enter option name (e.g. UV-C Light Treatment, Root Aeration)..." onkeypress="if(event.keyCode==13){addNewDropdownOption();}">
              <button class="btn btn-success px-4 fw-bold shadow-sm d-flex align-items-center gap-2" type="button" onclick="addNewDropdownOption()" style="background-color: #0f766e; border-color: #0f766e;">
                <i class="fa-solid fa-plus" id="addOptionSpin"></i> Add Option
              </button>
            </div>
          </div>
        </div>

        <!-- Current Options List -->
        <div class="card border-0 shadow-sm rounded-3">
          <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark" id="currentGroupListTitle">Current Options List</h6>
            <span class="badge bg-secondary text-white" id="currentGroupCountBadge">0 options</span>
          </div>
          <div class="card-body p-0" style="max-height: 280px; overflow-y: auto;">
            <ul class="list-group list-group-flush" id="groupOptionsListUl">
              <!-- Dynamically populated options with Delete buttons -->
            </ul>
          </div>
        </div>

      </div>

      <!-- Modal Footer -->
      <div class="modal-footer bg-white border-top">
        <button type="button" class="btn btn-secondary px-4 fw-semibold" data-dismiss="modal" data-bs-dismiss="modal" onclick="hideAppModal('manageDropdownsModal')">Done</button>
      </div>

    </div>
  </div>
</div>

<!-- Load JS Module -->
<script src="<?php echo $rooturl; ?>krf-orderandsales/js/dailyroutine_plan.js?v=<?php echo time(); ?>"></script>

<?php
if (!isset($_GET['dz'])) {
  // footer inclusion if applicable
}
?>
