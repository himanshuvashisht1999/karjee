<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (!isset($_SESSION['sessionid']) || $_SESSION['sessionid'] !== session_id()) {
  $protocol = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';
  $rooturl = $protocol . '://' . $_SERVER['HTTP_HOST'];
  if ($_SERVER['HTTP_HOST'] == "localhost") {
    $prefix = $rooturl . "/krf_amanaccounting/";
  } else {
    $prefix = $rooturl . "/";
  }
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
  require('../connection.php');
  require('../functions/hrm_FunctionMaster.php');
  require('../loginCell/functions/login_functions.php');
  require('../modulesCell/functions/module_functions.php');
  include('functions/product_functions.php');
  include('functions/vendor_functions.php');
  include('functions/dailyroutine_functions.php');
  
  $loginid = $_SESSION['loginid'] ?? 'admin';
  $companyid = $_SESSION['companyid'] ?? '1';
  $subcompanyid = $_SESSION['subcompanyid'] ?? '1';
  $projectid = $_SESSION['projectid'] ?? '1';
  $loginroleid = $_SESSION['loginroleid'] ?? '1';
  $logintype = $_SESSION['logintype'] ?? 'admin';
  $loginname = $_SESSION['loginname'] ?? 'Admin';
  
  $protocol = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';
  $url = basename($_SERVER["PHP_SELF"]);
  $rooturl = $protocol . '://' . $_SERVER['HTTP_HOST'];
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
  include('functions/dailyroutine_functions.php');
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
  :root {
    --dr-teal: #0f766e;
    --dr-teal-dark: #115e59;
    --dr-teal-light: #f0fdfa;
    --dr-border: #e2e8f0;
  }

  body {
    background-color: #f8fafc;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  }

  .bed-routine-container {
    padding: 20px;
    max-width: 1600px;
    margin: 0 auto;
  }

  /* ── Header Banner ── */
  .bed-banner {
    background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #115e59 100%);
    border-radius: 10px;
    padding: 12px 20px;
    color: #ffffff;
    margin-bottom: 15px;
    box-shadow: 0 4px 15px -3px rgba(15, 118, 110, 0.2);
  }

  .bed-banner h4 {
    font-size: 1.05rem;
    font-weight: 700;
  }

  .bed-banner p {
    font-size: 0.76rem;
    opacity: 0.85;
  }

  .dr-stat-badge {
    background: rgba(255, 255, 255, 0.16);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: 16px;
    padding: 4px 10px;
    font-size: 0.76rem;
    font-weight: 600;
    color: #ffffff;
    display: inline-flex;
    align-items: center;
    gap: 5px;
  }

  .dr-banner-btn {
    border-radius: 16px !important;
    background: rgba(0, 0, 0, 0.2) !important;
    color: #ffffff !important;
    border: 1px solid rgba(255, 255, 255, 0.35) !important;
    font-size: 0.76rem !important;
    font-weight: 600 !important;
    padding: 4px 10px !important;
    transition: all 0.2s ease !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 5px !important;
    text-decoration: none !important;
  }
  .dr-banner-btn:hover, .dr-banner-btn:focus, .dr-banner-btn:active {
    background: rgba(0, 0, 0, 0.45) !important;
    color: #ffffff !important;
    border-color: rgba(255, 255, 255, 0.7) !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
    text-decoration: none !important;
  }
  .dr-banner-btn span {
    color: #ffffff !important;
  }

  /* ── Cards & Containers ── */
  .bed-card {
    background: #ffffff;
    border: 1px solid var(--dr-border);
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    margin-bottom: 20px;
    overflow: hidden;
  }

  /* ── Table Styling ── */
  .bed-table-wrap {
    overflow-x: auto;
  }

  .bed-table {
    width: 100%;
    margin-bottom: 0;
    border-collapse: separate;
    border-spacing: 0;
  }

  .bed-table thead th {
    background-color: #f1f5f9;
    color: #334155;
    font-weight: 700;
    font-size: 0.74rem;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    padding: 9px 8px;
    border-bottom: 2px solid #cbd5e1;
    white-space: nowrap;
  }

  .bed-table td {
    padding: 8px 8px;
    vertical-align: middle !important;
    font-size: 0.84rem;
    color: #1e293b;
    border-bottom: 1px solid #e2e8f0 !important;
    background-color: inherit !important;
    transition: background-color 0.15s ease;
  }

  .bed-table tbody tr {
    background-color: #ffffff;
    transition: all 0.15s ease;
  }

  .bed-table tbody tr:hover {
    filter: brightness(0.96);
  }

  .bed-table tbody tr td:first-child {
    border-left: 5px solid transparent;
  }

  /* ── Pagination Bar ── */
  .dr-pagination-bar {
    background: #ffffff;
    border-top: 1px solid #e2e8f0;
    padding: 10px 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
  }

  .dr-page-btn {
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #334155;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
    text-decoration: none !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 32px;
    height: 30px;
  }

  .dr-page-btn:hover:not(:disabled) {
    background: #0f766e;
    color: #ffffff !important;
    border-color: #0f766e;
  }

  .dr-page-btn.active {
    background: #0f766e !important;
    color: #ffffff !important;
    border-color: #0f766e !important;
  }

  .dr-page-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
    background: #f8fafc;
  }

  /* ── Badges & Tags ── */
  .badge-bed {
    background-color: #f0fdfa;
    color: #0f766e;
    border: 1.5px solid #99f6e4;
    font-weight: 800;
    font-size: 0.82rem;
    padding: 3px 8px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
  }

  .badge-lot {
    background: #f8fafc;
    color: #334155;
    border: 1px solid #cbd5e1;
    font-family: monospace;
    font-weight: 700;
    font-size: 0.78rem;
    padding: 2px 6px;
    border-radius: 6px;
    display: inline-block;
  }

  .badge-trays {
    background: #ecfdf5;
    color: #065f46;
    border: 1px solid #a7f3d0;
    font-weight: 800;
    font-size: 0.78rem;
    padding: 3px 8px;
    border-radius: 20px;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    gap: 3px;
  }

  .badge-seed-tag {
    background-color: #fefce8;
    color: #854d0e;
    border: 1px solid #fef08a;
    font-weight: 600;
    padding: 2px 6px;
    border-radius: 6px;
    font-size: 0.74rem;
    display: inline-block;
  }

  .progress-day-bar {
    height: 5px;
    border-radius: 10px;
    background-color: #e2e8f0;
    overflow: hidden;
    margin-top: 3px;
  }

  .progress-day-fill {
    height: 100%;
    background: linear-gradient(90deg, #0f766e, #10b981);
    border-radius: 10px;
  }

  .badge-action-done {
    background-color: #dcfce7 !important;
    color: #15803d !important;
    border: 1px solid #86efac !important;
    font-weight: 700;
    font-size: 0.74rem;
    padding: 3px 8px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
  }

  .badge-action-pending {
    background-color: #fef3c7 !important;
    color: #92400e !important;
    border: 1px solid #fde68a !important;
    font-weight: 700;
    font-size: 0.74rem;
    padding: 3px 8px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
  }

  /* ── Form Controls & Selects ── */
  .dr-custom-select, .dr-form-control {
    height: 42px !important;
    font-size: 0.88rem !important;
    font-weight: 500 !important;
    color: #1e293b !important;
    border: 1.5px solid #cbd5e1 !important;
    border-radius: 8px !important;
    padding: 8px 14px !important;
    background-color: #ffffff !important;
    transition: all 0.2s ease-in-out !important;
    width: 100% !important;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04) !important;
  }

  .dr-form-control:focus, .dr-custom-select:focus {
    border-color: #0f766e !important;
    box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.15) !important;
    background-color: #ffffff !important;
    outline: none !important;
  }

  .dr-custom-select {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23334155' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e") !important;
    background-repeat: no-repeat !important;
    background-position: right 0.75rem center !important;
    background-size: 14px 10px !important;
    padding-right: 2.2rem !important;
    cursor: pointer;
  }

  select option, .dr-custom-select option, .form-control option {
    color: #0f172a !important;
    background-color: #ffffff !important;
    font-weight: 500 !important;
    padding: 6px 10px !important;
  }

  .dr-field-label {
    font-weight: 700;
    font-size: 0.76rem;
    color: #334155;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 7px !important;
    display: flex;
    align-items: center;
  }

  .dr-action-btn {
    width: 30px;
    height: 30px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 7px !important;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    transition: all 0.2s ease;
    font-size: 0.82rem;
    margin: 0 1px;
    text-decoration: none !important;
  }
  .dr-action-btn-done { color: #15803d; border-color: #86efac; background: #f0fdf4; }
  .dr-action-btn-done:hover { background: #15803d; color: #ffffff; }
  .dr-action-btn-locked { color: #16a34a; border-color: #bbf7d0; background: #f0fdf4; cursor: not-allowed; opacity: 0.85; }
  .dr-action-btn-view { color: #0f766e; border-color: #99f6e4; background: #f0fdfa; }
  .dr-action-btn-view:hover { background: #0f766e; color: #ffffff; }
  .dr-action-btn-history { color: #6366f1; border-color: #c7d2fe; background: #eef2ff; }
  .dr-action-btn-history:hover { background: #6366f1; color: #ffffff; }
  .dr-decision-btn {
    border-radius: 8px !important;
    font-weight: 700 !important;
    font-size: 0.85rem !important;
    padding: 10px 14px !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
  }
  #actionDecisionButtonGroup {
    display: flex !important;
    gap: 12px !important;
  }
  #actionDecisionButtonGroup .dr-decision-btn:not(:last-child) {
    margin-right: 8px;
  }
  .dr-decision-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
  }
  .dr-decision-btn.active {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
    transform: scale(1.02);
  }
  .dr-summary-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 12px 16px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.03);
  }
  .dr-quick-pill {
    font-size: 0.72rem;
    padding: 2px 8px;
    border-radius: 12px;
    cursor: pointer;
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #cbd5e1;
    transition: all 0.15s ease;
  }
  .dr-quick-pill:hover {
    background: #0f766e;
    color: #ffffff;
    border-color: #0f766e;
  }
</style>

<div class="bed-routine-container" 
     data-projectid="<?php echo htmlspecialchars($projectid ?? ''); ?>" 
     data-companyid="<?php echo htmlspecialchars($companyid ?? ''); ?>" 
     data-subcompanyid="<?php echo htmlspecialchars($subcompanyid ?? ''); ?>" 
     data-loginid="<?php echo htmlspecialchars($loginid ?? 'admin'); ?>" 
     data-routurl="<?php echo htmlspecialchars($rooturl ?? ''); ?>">

  <!-- 1. Header Banner -->
  <div class="bed-banner">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1">
          <i class="fa-solid fa-seedling text-white" style="font-size: 1.15rem;"></i>
          <h4 class="mb-0 fw-bold">Greenhouse Bed-Wise Daily Routine Activity</h4>
        </div>
        <p class="mb-0 text-white-50" style="font-size: 0.76rem;">
          Active polyhouse beds grouped by Sowing Lot No, Stock Order No, Root Type & Bed No with Seed ID routine tracking.
        </p>
      </div>

      <!-- KPI Summary Chips & Navigation -->
      <div class="d-flex align-items-center flex-wrap gap-2">
        <div class="dr-stat-badge">
          <i class="fa-solid fa-layer-group text-warning"></i> Active Beds: <span id="kpiTotalBeds">0</span>
        </div>
        <div class="dr-stat-badge">
          <i class="fa-solid fa-boxes-stacked text-success"></i> Trays: <span id="kpiTotalTrays">0</span>
        </div>
        <div class="dr-stat-badge">
          <i class="fa-solid fa-plant-wilt text-info"></i> Living Plants: <span id="kpiTotalPlants">0</span>
        </div>
        <a href="dailyroutine_plan.php" class="btn dr-banner-btn shadow-sm">
          <i class="fa-solid fa-calendar-check text-warning"></i> <span>Manage Routine Plans</span>
        </a>
      </div>
    </div>
  </div>

  <!-- 2. Filter Bar Card -->
  <div class="bed-card p-3">
    <div class="d-flex align-items-center flex-wrap gap-2">
      <!-- 1. Live Search -->
      <div class="flex-grow-1" style="min-width: 200px;">
        <div class="input-group">
          <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-search text-muted"></i></span>
          <input type="search" class="form-control border-start-0" id="filterBedSearchInput" placeholder="Search Lot, Order, Bed, Product, Seed..." onkeyup="filterBedGridRows()" style="height: 38px;">
        </div>
      </div>

      <!-- 2. Date Filter [default todate] -->
      <div style="width: 155px; min-width: 140px;">
        <div class="input-group">
          <span class="input-group-text bg-white border-end-0 text-muted px-2"><i class="fa-regular fa-calendar"></i></span>
          <input type="date" class="form-control border-start-0 px-2" id="filterRoutineDate" value="<?php echo date('Y-m-d'); ?>" onchange="onRoutineDateFilterChange()" style="height: 38px; font-size: 0.84rem;" title="Filter Routine Date">
        </div>
      </div>

      <!-- 3. Polyhouse Filter Dropdown [default all polyhouse] -->
      <div style="width: 165px; min-width: 140px;">
        <select class="dr-custom-select" id="filterPolyhouseSelect" onchange="onPolyhouseFilterChange()" style="height: 38px;">
          <option value="">-- All Polyhouses --</option>
        </select>
      </div>

      <!-- 4. Bed Filter Dropdown (of respective polyhouse) -->
      <div style="width: 150px; min-width: 130px;">
        <select class="dr-custom-select" id="filterBedNoSelect" onchange="onBedFilterChange()" style="height: 38px;">
          <option value="">-- All Beds --</option>
        </select>
      </div>

      <!-- 5. Root Type Filter Dropdown -->
      <div style="width: 145px; min-width: 130px;">
        <select class="dr-custom-select" id="filterRootTypeSelect" onchange="filterBedGridRows()" style="height: 38px;">
          <option value="">All Root Types</option>
        </select>
      </div>

      <!-- 6. Actions (Refresh & Export) -->
      <div class="d-flex align-items-center gap-2 ms-auto">
        <button type="button" class="btn btn-outline-secondary btn-sm px-3 py-2 fw-semibold d-inline-flex align-items-center gap-1" onclick="loadPolyhouseBedRoutines()" title="Refresh Bed Grid" style="height: 38px;">
          <i class="fa-solid fa-sync-alt" id="refreshBedSpin"></i> <span>Refresh</span>
        </button>
        <button type="button" class="btn btn-outline-success btn-sm px-3 py-2 fw-semibold d-inline-flex align-items-center gap-1" onclick="exportBedRoutinesToExcel()" title="Export Excel" style="height: 38px;">
          <i class="fa-solid fa-file-excel me-1"></i> <span>Export Excel</span>
        </button>
      </div>
    </div>
  </div>

  <!-- 3. Bed-Wise Routine Grid Table Card -->
  <div class="bed-card">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
      <h5 class="mb-0 fw-bold text-dark">
        <i class="fa-solid fa-table-cells text-teal me-2" style="color: #0f766e;"></i>Polyhouse Bed Activity Grid
      </h5>
      <span class="text-muted small">Showing <span id="showingBedsCount">0</span> active bed lot(s)</span>
    </div>

    <div class="bed-table-wrap">
      <table class="table bed-table table-hover mb-0" id="polyhouseBedRoutinesTbl">
        <thead>
          <tr>
            <th class="text-center" style="width: 35px;">S.No</th>
            <th style="width: 100px;">Bed & Location</th>
            <th style="width: 130px;">Sowing Lot & Order</th>
            <th style="width: 140px;">Product & Root</th>
            <th style="width: 130px;">Mapped Seed</th>
            <th class="text-center" style="width: 95px;">Trays / Plants</th>
            <th class="text-center" style="width: 125px;">Routine Progress</th>
            <th style="width: 170px;">Today's Activity</th>
            <th class="text-center" style="width: 120px;">Last Action</th>
            <th class="text-center" style="width: 80px;">Actions</th>
          </tr>
        </thead>
        <tbody id="polyhouseBedRoutinesTbody">
          <tr>
            <td colspan="10" class="text-center py-5 text-muted">
              <i class="fa-solid fa-spinner fa-spin me-2"></i> Loading greenhouse bed routines...
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- 4. Pagination Bar Footer -->
    <div class="dr-pagination-bar">
      <div class="d-flex align-items-center gap-2 small text-muted">
        <span>Showing <strong id="pageStartItem">1</strong> to <strong id="pageEndItem">20</strong> of <strong id="pageTotalItems">0</strong> entries</span>
        <select class="form-select form-select-sm" id="pageSizeSelect" style="width: auto; height: 30px; font-size: 0.78rem;" onchange="onPageSizeChange()">
          <option value="20" selected>20 per page</option>
          <option value="50">50 per page</option>
          <option value="100">100 per page</option>
          <option value="all">Show All</option>
        </select>
      </div>

      <div class="d-flex align-items-center gap-1" id="paginationControls">
        <!-- Rendered page buttons -->
      </div>
    </div>

  </div>

</div>

<!-- ========================================================================= -->
<!-- 4. MODAL: Mark Daily Routine Action as Done                               -->
<!-- ========================================================================= -->
<div class="modal fade" id="markActionModal" tabindex="-1" role="dialog" aria-labelledby="markActionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document" style="max-width: 860px;">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
      
      <!-- Modal Header -->
      <div class="modal-header text-white px-4 py-3 d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #0f766e 0%, #115e59 100%);">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-circle bg-white d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px; min-width: 38px;">
            <i class="fa-solid fa-clipboard-check fs-5" style="color: #0f766e;"></i>
          </div>
          <div>
            <h5 class="modal-title fw-bold mb-0 text-white" id="markActionModalLabel">Record Routine Action Execution</h5>
            <small class="text-white-50" id="markActionSubHeader">Log execution, reschedule or mark routine task for this bed</small>
          </div>
        </div>
        <button type="button" class="close text-white opacity-100" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" onclick="hideAppModal('markActionModal')" style="background: none; border: none; font-size: 1.7rem; color: #ffffff !important; opacity: 1 !important; line-height: 1; cursor: pointer; text-shadow: none; padding: 0 6px;">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body p-4" style="background-color: #f8fafc;">
        <form id="markActionForm" onsubmit="return false;">
          <input type="hidden" id="logModalLotNo" value="">
          <input type="hidden" id="logModalStockOrderNo" value="">
          <input type="hidden" id="logModalRootType" value="">
          <input type="hidden" id="logModalLocationId" value="">
          <input type="hidden" id="logModalBedNo" value="">
          <input type="hidden" id="logModalPolyhouseId" value="">
          <input type="hidden" id="logModalRoutinePlanId" value="0">
          <input type="hidden" id="logModalDayNumber" value="1">
          <input type="hidden" id="logModalExecutionStatus" value="Completed">

          <!-- Hidden inputs to hold scheduled action payload details for backend -->
          <input type="hidden" id="logActionType" value="Watering/Irrigation">
          <input type="hidden" id="logActionTitle" value="">
          <input type="hidden" id="logItemDetails" value="">
          <input type="hidden" id="logApplicationMethod" value="Foliar Spray">
          <input type="hidden" id="logActionDate" value="<?php echo date('Y-m-d'); ?>">

          <!-- 1. Context Summary Card -->
          <div class="dr-summary-card mb-3">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2 pb-2 border-bottom">
              <div class="d-flex align-items-center gap-2">
                <span id="logSummaryBedNo" class="badge-bed fs-6 py-1 px-2"></span>
                <span id="logSummaryPolyhouse" class="badge bg-light text-dark border"></span>
                <span id="logSummaryRootType"></span>
              </div>
              <div class="text-end">
                <span class="badge bg-teal text-white fw-bold px-2 py-1" style="background-color: #0f766e;" id="logSummaryDayAge"></span>
              </div>
            </div>
            
            <div class="row g-2 small">
              <div class="col-md-7">
                <div class="text-muted" style="font-size: 0.72rem;">PRODUCT / CROP:</div>
                <div id="logSummaryProductName" class="fw-bold text-dark text-truncate"></div>
                <div id="logSummarySeedName" class="text-secondary text-truncate mt-1" style="font-size: 0.76rem;"></div>
              </div>
              <div class="col-md-5 text-md-end">
                <div class="text-muted" style="font-size: 0.72rem;">SOWING LOT & ORDER:</div>
                <div id="logSummaryLotNo" class="font-monospace fw-semibold text-dark"></div>
                <div id="logSummaryOrderNo" class="text-muted small"></div>
              </div>
            </div>
          </div>

          <!-- 2. Scheduled Operation Card (Read-Only Specification) -->
          <div class="card border-0 shadow-sm rounded-3 bg-white mb-3" style="border-left: 4px solid #0f766e !important; overflow: hidden;">
            <div class="p-3 bg-light-subtle border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
              <div class="d-flex align-items-center gap-2">
                <span class="badge text-white px-2 py-1" style="background-color: #0f766e; font-size: 0.75rem;" id="logCardActivityType">Activity</span>
              </div>
              <span class="badge bg-white text-secondary border px-2 py-1" style="font-size: 0.72rem;" id="logCardStageName"><i class="fa-solid fa-seedling me-1 text-success"></i>Routine Stage</span>
            </div>
            
            <div class="p-3">
              <div class="text-muted mb-1" style="font-size: 0.70rem; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase;">
                <i class="fa-solid fa-list-check me-1 text-teal" style="color: #0f766e;"></i> Scheduled Task / Description
              </div>
              <h6 class="fw-bold text-dark mb-3" id="logCardTaskTitle" style="font-size: 1.05rem; line-height: 1.4;"></h6>
              
              <div class="row g-2">
                <div class="col-sm-6">
                  <div class="p-2 rounded-2 bg-light border h-100">
                    <div class="text-muted mb-1" style="font-size: 0.68rem; font-weight: 700; text-transform: uppercase;">
                      <i class="fa-solid fa-flask text-teal me-1" style="color: #0f766e;"></i> Item / Chemical & Dosage
                    </div>
                    <div class="fw-semibold text-dark small" id="logCardItemDetails">-</div>
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="p-2 rounded-2 bg-light border h-100">
                    <div class="text-muted mb-1" style="font-size: 0.68rem; font-weight: 700; text-transform: uppercase;">
                      <i class="fa-solid fa-spray-can text-teal me-1" style="color: #0f766e;"></i> Application Method
                    </div>
                    <div class="fw-semibold text-dark small" id="logCardAppMethod">-</div>
                  </div>
                </div>
                <div class="col-sm-6" id="logCardHeightCol" style="display: none;">
                  <div class="p-2 rounded-2 bg-light border h-100">
                    <div class="text-muted mb-1" style="font-size: 0.68rem; font-weight: 700; text-transform: uppercase;">
                      <i class="fa-solid fa-ruler-vertical text-teal me-1" style="color: #0f766e;"></i> Target Plant Height
                    </div>
                    <div class="fw-semibold text-dark small" id="logCardTargetHeight">-</div>
                  </div>
                </div>
                <div class="col-sm-6" id="logCardHealthCol" style="display: none;">
                  <div class="p-2 rounded-2 bg-light border h-100">
                    <div class="text-muted mb-1" style="font-size: 0.68rem; font-weight: 700; text-transform: uppercase;">
                      <i class="fa-solid fa-heart-pulse text-success me-1"></i> Target Health
                    </div>
                    <div class="fw-semibold text-dark small" id="logCardTargetHealth">-</div>
                  </div>
                </div>
              </div>

              <div class="mt-2 p-2 rounded-2 bg-warning-subtle text-dark border border-warning-subtle small" id="logCardSpecialNotesWrapper" style="display: none; font-size: 0.76rem;">
                <i class="fa-solid fa-lightbulb text-warning me-1"></i> <strong class="text-dark">Special & Climate Note:</strong> <span id="logCardSpecialNotes"></span>
              </div>
            </div>
          </div>

          <!-- 3. Action Execution Details Card (Operator Inputs) -->
          <div class="card border-0 shadow-sm rounded-3 bg-white p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="text-muted" style="font-size: 0.70rem; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase;">
                <i class="fa-solid fa-clipboard-user me-1 text-teal" style="color: #0f766e;"></i> Action Execution Details
              </div>
              <span class="badge bg-light text-secondary border fw-semibold" style="font-size: 0.72rem;">
                <i class="fa-regular fa-calendar-check me-1 text-teal" style="color: #0f766e;"></i> Execution Date: <strong class="text-dark ms-1"><?php echo date('d-m-Y'); ?></strong>
              </span>
            </div>
            <div class="row gy-3 gx-3">
              <!-- Execution Time Slot (Editable) -->
              <div class="col-md-6">
                <label class="dr-field-label"><i class="fa-regular fa-clock text-muted me-1"></i>Execution Time Slot <span class="text-danger ms-1">*</span></label>
                <select class="dr-custom-select fw-semibold" id="logTimeSlot">
                  <option value="Morning" selected>Morning</option>
                  <option value="Afternoon">Afternoon</option>
                  <option value="Evening">Evening</option>
                  <option value="All Day">All Day</option>
                </select>
              </div>

              <!-- Performed By -->
              <div class="col-md-6">
                <label class="dr-field-label"><i class="fa-regular fa-user text-muted me-1"></i>Performed By <span class="text-danger ms-1">*</span></label>
                <input type="text" class="dr-form-control fw-semibold" id="logActionTakenBy" value="<?php echo htmlspecialchars($loginname ?? 'admin'); ?>" required>
              </div>
            </div>
          </div>

          <!-- 4. Action Decision Selector (Placed at Bottom of Form) -->
          <div class="card border-0 shadow-sm rounded-3 mb-3 p-3 bg-white">
            <label class="dr-field-label mb-2 text-dark">
              <i class="fa-solid fa-list-check me-1 text-teal" style="color: #0f766e;"></i> SELECT ACTION DECISION <span class="text-danger ms-1">*</span>
            </label>
            <div class="d-flex gap-2" id="actionDecisionButtonGroup">
              <button type="button" class="btn btn-outline-success flex-fill dr-decision-btn active" id="btnChoiceCompleted" onclick="setActionDecision('Completed')">
                <i class="fa-solid fa-circle-check fs-6"></i> Completed
              </button>
              <button type="button" class="btn btn-outline-warning text-dark flex-fill dr-decision-btn" id="btnChoiceRescheduled" onclick="setActionDecision('Rescheduled')">
                <i class="fa-solid fa-calendar-days fs-6"></i> Reschedule
              </button>
              <button type="button" class="btn btn-outline-danger flex-fill dr-decision-btn" id="btnChoiceNotRequired" onclick="setActionDecision('Not Required')">
                <i class="fa-solid fa-ban fs-6"></i> Not Required
              </button>
            </div>
          </div>

          <!-- 5. Reschedule Date Field Group (Shown when Reschedule is selected) -->
          <div class="card border-0 shadow-sm rounded-3 mb-3 bg-white p-3" id="rescheduleDateGroup" style="display: none;">
            <div class="p-3 rounded-3 bg-warning-subtle border border-warning">
              <label class="dr-field-label text-dark mb-2 d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-calendar-plus text-warning me-1"></i>Reschedule to New Target Date <span class="text-danger ms-1">*</span></span>
                <div class="d-flex gap-1">
                  <span class="dr-quick-pill" onclick="setQuickRescheduleDate(1)">+1 Day</span>
                  <span class="dr-quick-pill" onclick="setQuickRescheduleDate(2)">+2 Days</span>
                  <span class="dr-quick-pill" onclick="setQuickRescheduleDate(7)">+1 Week</span>
                </div>
              </label>
              <input type="date" class="dr-form-control fw-bold border-warning bg-white" id="logRescheduleDate" min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
            </div>
          </div>

          <!-- 6. Supervisor Remarks / Notes Card -->
          <div class="card border-0 shadow-sm rounded-3 bg-white p-3">
            <label class="dr-field-label d-flex justify-content-between align-items-center" id="logRemarksLabel">
              <span><i class="fa-regular fa-comment-dots text-muted me-1"></i>Supervisor Remarks / Observations</span>
              <span class="badge bg-danger text-white px-2 py-1" id="remarksMandatoryBadge" style="display: none; font-size: 0.70rem;">
                <i class="fa-solid fa-triangle-exclamation me-1"></i>Mandatory for 'Not Required'
              </span>
            </label>
            <textarea class="dr-form-control" id="logRemarks" rows="2" style="height: auto !important; min-height: 70px;" placeholder="Enter any crop health observation, moisture level, or plant height..."></textarea>
          </div>

        </form>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer bg-white border-top px-4 py-3 d-flex justify-content-between">
        <button type="button" class="btn btn-light border px-4 fw-semibold text-secondary" data-dismiss="modal" data-bs-dismiss="modal" onclick="hideAppModal('markActionModal')">
          <i class="fa-solid fa-times me-1"></i> Cancel
        </button>
        <button type="button" class="btn btn-success px-4 py-2 fw-bold shadow-sm d-flex align-items-center gap-2" id="submitActionBtn" style="background-color: #0f766e; border-color: #0f766e;" onclick="submitMarkBedAction()">
          <i class="fa-solid fa-check" id="submitActionSpin"></i> <span>Confirm Action Done</span>
        </button>
      </div>

    </div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- 5. MODAL: View Bed Full Routine Timeline & Action Logs History            -->
<!-- ========================================================================= -->
<div class="modal fade" id="bedTimelineModal" tabindex="-1" role="dialog" aria-labelledby="bedTimelineModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document" style="max-width: 1000px;">
    <div class="modal-content border-0 shadow">
      
      <div class="modal-header bg-dark text-white d-flex justify-content-between align-items-center px-4 py-3">
        <div class="d-flex align-items-center gap-2">
          <i class="fa-solid fa-timeline text-info fs-4 me-2"></i>
          <div>
            <h5 class="modal-title fw-bold mb-0 text-white" id="bedTimelineModalLabel">Bed Routine Schedule & Action History</h5>
            <small class="text-white-50" id="bedTimelineSubHeader"></small>
          </div>
        </div>
        <div class="d-flex align-items-center">
          <button type="button" class="btn btn-danger btn-sm fw-bold shadow-sm d-flex align-items-center gap-2" id="btnDownloadActionHistoryPdf" onclick="downloadBedActionHistoryPdf()" style="background-color: #dc2626; border: 1px solid #b91c1c; padding: 6px 14px; font-size: 0.80rem; border-radius: 6px; margin-right: 14px; white-space: nowrap;">
            <i class="fa-solid fa-file-pdf fs-6"></i> <span>Download PDF</span>
          </button>
          <button type="button" class="close text-white opacity-100" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" onclick="hideAppModal('bedTimelineModal')" style="font-size: 1.6rem; color: #ffffff !important; opacity: 1 !important; text-shadow: none; cursor: pointer; border: none; background: transparent; line-height: 1; padding: 0 6px;">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
      </div>

      <div class="modal-body p-4 bg-light" id="bedTimelineContentBody">
        <!-- Rendered timeline and execution history tabs -->
      </div>

      <div class="modal-footer bg-white border-top">
        <button type="button" class="btn btn-secondary btn-sm px-4" data-dismiss="modal" data-bs-dismiss="modal" onclick="hideAppModal('bedTimelineModal')">Close</button>
      </div>

    </div>
  </div>
</div>

<!-- Load JS Module -->
<script src="<?php echo $rooturl; ?>krf-orderandsales/js/dailyroutine_bed_activity.js?v=<?php echo time(); ?>"></script>
