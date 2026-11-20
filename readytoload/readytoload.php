<?php
/**
 * Ready To Load - Management Dashboard & Scanner
 * Table Source: `karjeefarm`.`krf_sales_order_items` (status = 'readytoload')
 * Path: /readytoload/readytoload.php
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
  <title>Ready to Load - Plant Tray Scanner & Order Dispatch | Karjee Farm</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

  <!-- Modern Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@600;700;800&display=swap" rel="stylesheet">
  
  <!-- FontAwesome & Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

  <!-- SweetAlert2 & HTML5-QRCode Scanner -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

  <!-- Module Styling -->
  <link rel="stylesheet" href="css/readytoload.css?v=<?= time() ?>">
  <script>
    window.loginRoleId = <?= (int)$loginroleid ?>;
    window.loginName = <?= json_encode($loginname) ?>;
  </script>
</head>
<body>

<div class="rtl-page-wrap">

  <!-- Hero Header -->
  <div class="rtl-hero">
    <div>
      <h1><i class="fa fa-truck-loading"></i> Ready to Load Dashboard</h1>
    </div>
    <div class="rtl-hero-actions" style="display: flex; gap: 8px; align-items: center;">
      <button type="button" class="btn btn-sm btn-light font-weight-bold" onclick="loadOrdersList();" title="Refresh list" style="padding:7px 14px; border-radius:8px; font-size:0.875rem;">
        <i class="fa fa-sync-alt mr-1"></i> Refresh
      </button>
    </div>
  </div>

  <!-- KPI Metric Cards -->
  <div class="kpi-row">
    <div class="kpi-card">
      <div class="kpi-icon-wrap kpi-icon-purple">
        <i class="fa fa-file-invoice"></i>
      </div>
      <div class="kpi-content">
        <span class="kpi-title">Ready Orders</span>
        <span class="kpi-value" id="kpiTotalOrders">0</span>
        <span class="kpi-sub">Provisional orders waiting</span>
      </div>
    </div>

    <div class="kpi-card">
      <div class="kpi-icon-wrap kpi-icon-green">
        <i class="fa fa-seedling"></i>
      </div>
      <div class="kpi-content">
        <span class="kpi-title">Total Plants</span>
        <span class="kpi-value" id="kpiTotalPlants">0</span>
        <span class="kpi-sub">Plants to be loaded</span>
      </div>
    </div>

    <div class="kpi-card">
      <div class="kpi-icon-wrap kpi-icon-amber">
        <i class="fa fa-th"></i>
      </div>
      <div class="kpi-content">
        <span class="kpi-title">Estimated Trays</span>
        <span class="kpi-value" id="kpiTotalTrays">0</span>
        <span class="kpi-sub">Required cavity trays</span>
      </div>
    </div>

    <div class="kpi-card">
      <div class="kpi-icon-wrap kpi-icon-blue">
        <i class="fa fa-check-double"></i>
      </div>
      <div class="kpi-content">
        <span class="kpi-title">Trays Loaded</span>
        <span class="kpi-value" id="kpiLoadedTrays">0</span>
        <span class="kpi-sub">Already scanned & verified</span>
      </div>
    </div>
  </div>

  <!-- Live Search & Advanced Filter Toolbar -->
  <div class="filter-card">
    <div class="filter-main-bar">
      <!-- Search Input -->
      <div class="search-bar-wrap search-wrapper">
        <i class="fa fa-search search-bar-icon"></i>
        <input type="text" id="filterSearch" class="search-bar-input" placeholder="Search Invoice No, Order No (PSO / SO), Product ID, Client Name, Crop..." autocomplete="off" style="padding-left: 44px !important;">
        <button type="button" id="btnClearSearch" class="btn-clear-search" title="Clear Search" style="display: none;">
          <i class="fa fa-times-circle"></i>
        </button>
      </div>

      <!-- Quick Action Buttons -->
      <div class="filter-quick-actions">
        <!-- Small Cards / View Toggle -->
        <button type="button" id="btnToggleCompactView" class="btn-filter-pill" onclick="toggleCompactCardsView();" title="Toggle Compact / Small Cards View">
          <i class="fa fa-table-cells-large mr-1"></i> <span id="lblCompactView">Small Cards</span>
        </button>
        <!-- Reset All Filters -->
        <button type="button" id="btnResetFilters" class="btn-filter-pill btn-reset-pill" onclick="resetAllFilters();" title="Reset all filters to default">
          <i class="fa fa-rotate-left mr-1"></i> Reset
        </button>
      </div>
    </div>

    <!-- Filter Dropdowns & Pills Row -->
    <div class="filter-options-bar">
      <!-- Status Filter -->
      <div class="filter-item-wrap">
        <label for="filterStatus" class="filter-item-label"><i class="fa fa-filter text-primary mr-1"></i> Status:</label>
        <select id="filterStatus" class="filter-control-select" onchange="applyFilters();">
          <option value="readytoload" selected>Ready to Load (Pending)</option>
          <option value="loaded">Loaded Orders (Ready to Dispatch)</option>
          <option value="dispatched">Dispatched Orders</option>
          <option value="all">All Orders</option>
        </select>
      </div>

      <!-- Year Filter as per table krf_polyhouse_plant_details -->
      <div class="filter-item-wrap">
        <label for="filterYear" class="filter-item-label"><i class="fa fa-calendar-alt text-primary mr-1"></i> Year (Polyhouse):</label>
        <select id="filterYear" class="filter-control-select" onchange="applyFilters();">
          <option value="all">All Years</option>
          <?php
          $qYears = $krfconn->query("
              SELECT DISTINCT yr FROM (
                  SELECT YEAR(created_at) as yr FROM krf_polyhouse_plant_details WHERE created_at IS NOT NULL AND created_at != '0000-00-00 00:00:00'
                  UNION
                  SELECT YEAR(dispatchat) as yr FROM krf_polyhouse_plant_details WHERE dispatchat IS NOT NULL AND dispatchat != '0000-00-00 00:00:00'
                  UNION
                  SELECT YEAR(sowing_date) as yr FROM krf_polyhouse_plant_details WHERE sowing_date IS NOT NULL AND sowing_date != '0000-00-00'
                  UNION
                  SELECT YEAR(planted_date) as yr FROM krf_polyhouse_plant_details WHERE planted_date IS NOT NULL AND planted_date != '0000-00-00'
              ) t WHERE yr > 2000 ORDER BY yr DESC
          ");
          $foundYears = [];
          if ($qYears) {
              while ($yRow = $qYears->fetch_assoc()) {
                  $yVal = (int)$yRow['yr'];
                  $foundYears[] = $yVal;
                  echo "<option value='{$yVal}'>{$yVal}</option>";
              }
          }
          if (empty($foundYears)) {
              echo "<option value='2026'>2026</option>";
          }
          ?>
        </select>
      </div>

      <!-- Suggested Filter: Polyhouse -->
      <div class="filter-item-wrap">
        <label for="filterPolyhouse" class="filter-item-label"><i class="fa fa-warehouse text-info mr-1"></i> Polyhouse:</label>
        <select id="filterPolyhouse" class="filter-control-select" onchange="applyFilters();">
          <option value="">All Polyhouses</option>
          <?php
          $qPhm = $krfconn->query("SELECT polyhouseid, polyhousename FROM krf_polyhouse_master ORDER BY polyhouseid ASC");
          if ($qPhm) {
              while ($phRow = $qPhm->fetch_assoc()) {
                  echo "<option value='" . htmlspecialchars($phRow['polyhouseid']) . "'>" . htmlspecialchars($phRow['polyhousename']) . "</option>";
              }
          }
          ?>
        </select>
      </div>

      <!-- Suggested Filter: Crop / Variety -->
      <div class="filter-item-wrap">
        <label for="filterCrop" class="filter-item-label"><i class="fa fa-seedling text-success mr-1"></i> Crop / Variety:</label>
        <select id="filterCrop" class="filter-control-select" onchange="applyFilters();">
          <option value="">All Crops</option>
        </select>
      </div>

      <!-- Suggested Filter: Mismatch Warning Quick Filter Button -->
      <div class="filter-item-wrap filter-mismatch-wrap">
        <button type="button" id="btnFilterMismatch" class="btn-mismatch-pill" onclick="toggleMismatchFilter();" title="Filter orders having quantity or status discrepancies">
          <i class="fa fa-triangle-exclamation text-danger mr-1"></i>
          <span>Mismatched Orders</span>
          <span class="mismatch-badge-count" id="lblMismatchCount" style="display:none;">0</span>
        </button>
      </div>
    </div>
  </div>

  <!-- Dynamic Orders List Container (Rendered by readytoload.js) -->
  <div id="ordersContainer">
    <!-- Populated via AJAX -->
  </div>

</div>

<!-- =============================================================
     SCANNER MODAL POPUP (Exact layout & features of Screenshot)
     ============================================================= -->
<div class="scan-modal-overlay" id="scannerModalOverlay">
  <div class="scan-modal-dialog">
    <!-- Mobile App Bottom Sheet Drag Indicator -->
    <div class="modal-drag-handle d-md-none"></div>
    
    <!-- Modal Header -->
    <div class="scan-modal-header">
      <h3 class="scan-modal-title">
        <i class="fa fa-camera text-primary"></i> Scan Plant Tray QR / Barcode
      </h3>
      <div class="d-flex align-items-center gap-2">
        <span class="batch-mode-badge" style="background:#ecfdf5; color:#065f46; border:1px solid #10b981; font-weight:700;"><i class="fa fa-qrcode mr-1 text-success"></i>1-Tray Verification Mode</span>
        <button type="button" class="modal-close-btn" onclick="closeScannerModal();" title="Close Popup">
          <i class="fa fa-times"></i>
        </button>
      </div>
    </div>

    <!-- Modal Body -->
    <div class="scan-modal-body">
      
      <!-- Scanner Dock Card -->
      <div class="modal-scan-card">
        <div class="card-head">
          <h2><i class="fa fa-qrcode text-primary"></i> Tray Scanner Input</h2>
          <span class="badge bg-light text-dark border">Batch Gun / Camera</span>
        </div>

        <!-- Mode Tabs -->
        <div class="scanner-tabs">
          <button type="button" class="scanner-tab-btn active" id="tabManual" onclick="switchScannerTab('manual');">
            <i class="fa fa-barcode"></i> <span>Manual / Gun</span>
          </button>
          <button type="button" class="scanner-tab-btn" id="tabCamera" onclick="switchScannerTab('camera');">
            <i class="fa fa-video"></i> <span>Camera Scanner</span>
          </button>
        </div>

        <!-- Mode 1: Manual / Barcode Gun -->
        <div id="sectionManual">
          <!-- Scan Target Mode: Billed Beds vs Free Bonus -->
          <div class="d-flex align-items-center gap-2 mb-2 p-1 rounded" id="scanTargetModeSelector" style="background: #f8fafc; border: 1.5px solid #e2e8f0;">
            <button type="button" class="btn btn-sm flex-fill font-weight-bold" id="btnScanModeBilled" onclick="switchScanTargetMode('billed');" style="background:#2563eb; color:#fff; font-size:12px; border-radius:6px; padding:6px 10px; transition:all 0.2s;">
              <i class="fa fa-boxes mr-1"></i> Billed Order Beds (1-Tray Verification)
            </button>
            <button type="button" class="btn btn-sm flex-fill font-weight-bold" id="btnScanModeBonus" onclick="switchScanTargetMode('bonus');" style="display:none; background:#ffffff; color:#15803d; border:1.5px solid #86efac; font-size:12px; border-radius:6px; padding:6px 10px; transition:all 0.2s;">
              <i class="fa fa-gift mr-1"></i> Free Bonus Tray <span id="lblScanModeBonusBadge" class="badge" style="background:#15803d; color:#fff; font-size:10.5px; margin-left:4px; padding:2px 6px;">0 Left</span>
            </button>
          </div>
          <form id="formManualScan" onsubmit="event.preventDefault();">
            <div class="search-input-group mb-2">
              <input type="text" id="inputBarcodeQuery" class="scan-input" placeholder="Scan or enter Tray Barcode" autocomplete="off" autofocus>
              <button type="submit" class="btn-scan-action" id="btnAddTrayAction">
                <i class="fa fa-plus-circle"></i> <span>Add Tray</span>
              </button>
            </div>
          </form>
          <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 6px;">
            <small class="text-muted" style="font-size: 0.78rem;">
              <i class="fa fa-info-circle text-primary mr-1"></i> Scan or enter <b>Tray Barcodes only</b>. <span class="text-danger font-weight-bold"></span>
            </small>
            <button type="button" class="btn btn-sm btn-outline-danger" id="btnClearScan" onclick="clearScanInput();" style="font-size:0.75rem; padding:3px 8px; border-radius:6px; white-space:nowrap;">
              <i class="fa fa-eraser mr-1"></i> Clear Input
            </button>
          </div>
        </div>

        <!-- Mode 2: Camera Scanner Viewport -->
        <div id="sectionCamera" style="display:none;">
          <div id="qrCameraViewport"></div>
          <div class="d-flex gap-2 flex-wrap mt-2 justify-content-center">
            <button type="button" class="btn btn-sm btn-primary" id="btnToggleCamera" onclick="toggleCamera();">
              <i class="fa fa-play mr-1"></i> Start Camera
            </button>
          </div>
          <div id="cameraStatusMsg" class="small text-muted mt-2 text-center" style="font-size:0.8rem;">
            <i class="fa fa-info-circle text-primary mr-1"></i> Point camera directly at the <b>Tray Barcode tag</b>.
          </div>
        </div>
      </div>

      <!-- Live Order Context Strip -->
      <div id="batchSummaryBox" class="batch-summary-strip">
        <div class="batch-summary-main">
          <div class="batch-product-title" id="lblBatchProduct">-</div>
          <div class="batch-meta-tags">
            <span class="batch-chip" id="lblBatchLocationTag"><i class="fa fa-warehouse text-info"></i> <span id="lblBatchLocation">-</span></span>
            <span class="batch-chip chip-rts" id="lblBatchRtsTag" style="display:none;"><i class="fa fa-boxes-stacked text-success"></i> <span id="lblBatchRts">-</span></span>
            <span class="batch-chip chip-shift" id="lblBatchShiftTag"><i class="fa fa-exchange-alt text-purple"></i> <span id="lblBatchShift">-</span></span>
            <span class="batch-chip chip-disp" id="lblBatchDispTag" style="display:none;"><i class="fa fa-truck text-success"></i> <span id="lblBatchDisp">-</span></span>
            <span class="batch-chip chip-loaded" id="lblBatchLoadedTag" onclick="loadExistingLoadedTrays()" title="Click to view already loaded trays in grid for inspection or undo"><i class="fa fa-truck-loading text-primary"></i> <span id="lblBatchLoaded">-</span></span>
            <span class="batch-chip chip-pending" id="lblBatchPendingTag" style="display:none !important;"><i class="fa fa-hourglass-half text-warning"></i> <span id="lblBatchPending">-</span></span>
          </div>
        </div>
        <div class="batch-stats-group">
          <div class="stat-badge-pill stat-pill-trays">
            <i class="fa fa-th"></i> <span id="lblCountScannedTrays">0</span> Scanned
          </div>
          <div class="stat-badge-pill stat-pill-plants">
            <i class="fa fa-seedling"></i> <span id="lblCountScannedPlants">0</span> Plants
          </div>
          <div class="stat-badge-pill stat-pill-remaining" id="pillRemainingToLoad" style="display:none;" title="Remaining trays required after this batch">
            <i class="fa fa-hourglass-end mr-1"></i> <span id="lblRemainingAfterBatch">0 Left</span>
          </div>
          <button type="button" class="btn btn-sm btn-outline-danger btn-clear-grid" onclick="clearAllScanned();" title="Clear Scanned Trays List">
            <i class="fa fa-trash-alt mr-1"></i> Clear Grid
          </button>
        </div>
      </div>

      <!-- Allocated Beds 1-Tray Verification Checklist -->
      <div id="bedVerificationCard" class="modal-scan-card mb-3" style="display:none; padding:12px 14px; background:#f8fafc; border:1.5px solid #cbd5e1; border-radius:10px;">
        <div class="d-flex align-items-center justify-content-between mb-2 pb-2" style="border-bottom:1px solid #e2e8f0;">
          <div class="d-flex align-items-center gap-2">
            <span class="badge" style="background:#1e40af; color:#fff; font-size:12px; padding:4px 9px; border-radius:6px; font-weight:700;">
              <i class="fa fa-list-check mr-1"></i> Bed Verification Checklist
            </span>
            <span class="text-muted" style="font-size:0.83rem;">Scan <b>1 Verification Tray</b> for each allocated bed</span>
          </div>
          <div id="lblBedVerificationProgress" class="badge" style="background:#fef3c7; color:#92400e; border:1px solid #f59e0b; font-size:11.5px; font-weight:700; padding:4px 9px;">
            0 of 2 Beds Verified
          </div>
        </div>
        <div id="bedVerificationListContainer" class="d-flex flex-column" style="gap:8px;">
          <!-- Rendered dynamically by readytoload.js -->
        </div>
      </div>

      <!-- Free Bonus Plant Verification Card -->
      <div id="bonusVerificationCard" class="modal-scan-card mb-3" style="display:none; padding:12px 14px; background:#f0fdf4; border:1.5px solid #86efac; border-radius:10px;">
        <div class="d-flex align-items-center justify-content-between mb-2 pb-2" style="border-bottom:1px solid #bbf7d0;">
          <div class="d-flex align-items-center gap-2">
            <span class="badge" style="background:#15803d; color:#fff; font-size:12px; padding:4px 9px; border-radius:6px; font-weight:700;">
              <i class="fa fa-gift mr-1"></i> Free Bonus Plant Verification
            </span>
            <span class="text-muted" style="font-size:0.83rem;">Required: <strong id="lblBonusTargetQty" class="text-dark">0</strong> Plants</span>
          </div>
          <div id="lblBonusVerificationProgress" class="badge" style="background:#fef3c7; color:#92400e; border:1px solid #f59e0b; font-size:11.5px; font-weight:700; padding:4px 9px;">
            0 of 0 Plants Verified
          </div>
        </div>
        <div id="bonusTraysListContainer" class="d-flex flex-column" style="gap:8px;">
          <!-- Rendered dynamically by readytoload.js -->
        </div>
      </div>

      <!-- Scanned Trays Grid (Table) -->
      <div class="modal-scan-card" id="scannedTraysCard" style="padding: 1rem;">
        <div class="card-head" style="margin-bottom: 0.75rem;">
          <h2><i class="fa fa-table-list text-primary"></i> Scanned Trays Grid</h2>
          <div class="d-flex align-items-center gap-2">
            <span id="autoSaveStatusBadge" class="auto-save-status-pill" style="display:none;"></span>
            <span id="lblGridCounter" class="badge" style="display:inline-flex; align-items:center; gap:6px; font-size:1.05rem; font-weight:700; color:#1e40af; background:#eff6ff; border:1.5px solid #bfdbfe; padding:6px 14px; border-radius:20px; box-shadow:0 1px 3px rgba(37,99,235,0.12);">
              <i class="fa fa-layer-group text-primary"></i> 0 trays scanned
            </span>
          </div>
        </div>

        <!-- Empty Grid Notice -->
        <div id="emptyGridPlaceholder" class="empty-grid-box" style="text-align:center; padding:1.75rem 1rem;">
          <i class="fa fa-barcode fa-3x text-muted mb-2" style="opacity:0.35;"></i>
          <h4 class="font-weight-bold text-dark mb-1" style="font-size:1.05rem;">Scan 1 Verification Tray Per Bed</h4>
          <p class="text-muted mx-auto mb-0" style="max-width:440px; font-size:0.86rem;">
            Please scan <b>1 tray for each allocated bed</b> to verify stock. Once all allocated beds are verified, you can confirm loading and update order status immediately.
          </p>
        </div>

        <!-- Dynamic Trays Table (Desktop & Tablet) -->
        <div id="traysTableContainer" class="trays-grid-wrap d-none d-md-block" style="display:none;">
          <table class="trays-grid-table">
            <thead>
              <tr>
                <th style="width:45px;">#</th>
                <th>Tray Barcode</th>
                <th>Order / PSO</th>
                <th>Product & Variety</th>
                <th>Bed & Polyhouse</th>
                <th>Plants/Tray</th>
                <th>Current Status</th>
                <th class="text-center" style="width:80px;">Action</th>
              </tr>
            </thead>
            <tbody id="tblScannedTraysBody">
              <!-- Populated dynamically via readytoload.js -->
            </tbody>
          </table>
        </div>

        <!-- Dynamic Trays Card List (Mobile App Preview Layout) -->
        <div id="traysMobileCardsContainer" class="trays-mobile-cards-list d-md-none" style="display:none;">
          <!-- Populated dynamically via readytoload.js for mobile view -->
        </div>
      </div>

      <!-- Bottom Clearance Spacer to ensure extra scroll room above fixed footer on mobile -->
      <div class="modal-bottom-scroll-spacer"></div>

    </div>

    <!-- Modal Footer Actions -->
    <div class="scan-modal-footer">
      <button type="button" class="btn-modal-cancel" onclick="closeScannerModal();">
        <i class="fa fa-times mr-1"></i> Cancel
      </button>
      <button type="button" class="btn-modal-confirm" id="btnConfirmLoadAction" onclick="submitLoadedTrays();" disabled>
        <span class="d-none d-sm-inline"><i class="fa fa-truck-fast mr-1"></i> Confirm & Load (1-Tray Verified)</span>
        <span class="d-inline d-sm-none"><i class="fa fa-truck-fast mr-1"></i> Update status</span>
      </button>
      <button type="button" class="btn-modal-confirm" id="btnModalUpdateStatusAction" onclick="triggerModalUpdateStatus();" style="display:none; background: linear-gradient(135deg, #059669 0%, #047857 100%);" title="All trays loaded - Confirm load & update order status">
        <i class="fa fa-truck-fast mr-1"></i> Update status
      </button>
    </div>

  </div>
</div>

<!-- =============================================================
     LOADED TRAYS POPUP MODAL (Inspection & Single / All Tray Undo)
     ============================================================= -->
<div class="scan-modal-overlay" id="loadedTraysModalOverlay">
  <div class="scan-modal-dialog" style="max-width: 900px;">
    <!-- Mobile Drag Handle -->
    <div class="modal-drag-handle d-md-none"></div>

    <!-- Modal Header -->
    <div class="scan-modal-header">
      <h3 class="scan-modal-title">
        <i class="fa fa-boxes-stacked text-primary mr-2"></i> Loaded Trays Management
      </h3>
      <div class="d-flex align-items-center gap-2">
        <?php if ($loginroleid < 2): ?>
        <button type="button" class="btn btn-sm btn-danger font-weight-bold" id="btnModalUndoAllTrays" onclick="undoAllLoadedTrays();" style="border-radius: 8px;">
          <i class="fa fa-rotate-left mr-1"></i> Undo All Trays
        </button>
        <?php endif; ?>
        <button type="button" class="modal-close-btn" onclick="closeLoadedTraysModal();" title="Close Popup">
          <i class="fa fa-times"></i>
        </button>
      </div>
    </div>

    <!-- Modal Body -->
    <div class="scan-modal-body" style="padding: 1.25rem;">
      <!-- Order & Crop Details Context Strip -->
      <div class="batch-summary-strip mb-3">
        <div class="batch-summary-main">
          <div class="batch-product-title" id="lblUndoModalProduct">-</div>
          <div class="batch-meta-tags">
            <span class="batch-chip"><i class="fa fa-file-invoice text-primary mr-1"></i> <span id="lblUndoModalPsono">-</span></span>
            <span class="batch-chip"><i class="fa fa-user text-secondary mr-1"></i> <span id="lblUndoModalClient">-</span></span>
            <span class="batch-chip chip-rts"><i class="fa fa-boxes-stacked text-success mr-1"></i> <span id="lblUndoModalRts">-</span></span>
            <span class="batch-chip chip-shift" id="lblUndoModalShiftTag" style="display:none;"><i class="fa fa-exchange-alt text-purple mr-1"></i> <span id="lblUndoModalShift">-</span></span>
            <span class="batch-chip chip-disp" id="lblUndoModalDispTag" style="display:none;"><i class="fa fa-truck text-success mr-1"></i> <span id="lblUndoModalDisp">-</span></span>
          </div>
        </div>
        <div class="batch-stats-group">
          <div class="stat-badge-pill stat-pill-trays">
            <i class="fa fa-th mr-1"></i> <span id="lblUndoModalLoadedTrays">0</span> Trays
          </div>
          <div class="stat-badge-pill stat-pill-plants">
            <i class="fa fa-seedling mr-1"></i> <span id="lblUndoModalLoadedPlants">0</span> Plants
          </div>
        </div>
      </div>

      <!-- Loaded Trays Grid -->
      <div class="modal-scan-card" style="padding: 1rem;">
        <div class="card-head" style="margin-bottom: 0.75rem;">
          <h2><i class="fa fa-table-list text-primary mr-1"></i> Currently Loaded Trays</h2>
          <span class="text-muted" style="font-size:0.82rem;" id="lblUndoGridCounter">0 trays</span>
        </div>

        <!-- Empty Placeholder -->
        <div id="undoModalEmptyPlaceholder" class="empty-grid-box" style="display:none; text-align:center; padding:2rem 1rem;">
          <i class="fa fa-box-open fa-3x text-muted mb-2" style="opacity:0.35;"></i>
          <h4 class="font-weight-bold text-dark mb-1" style="font-size:1.05rem;">No Loaded Trays Found</h4>
          <p class="text-muted mx-auto mb-0" style="max-width:380px; font-size:0.86rem;">
            There are currently no trays marked as loaded for this order item.
          </p>
        </div>

        <!-- Table Container -->
        <div id="undoModalTableContainer" class="trays-grid-wrap">
          <table class="trays-grid-table">
            <thead>
              <tr>
                <th style="width:40px;">#</th>
                <th>Tray Barcode</th>
                <th>Updated Table</th>
                <th>Polyhouse & Bed</th>
                <th>Plants</th>
                <th>Loaded At & By</th>
                <th>Status</th>
                <?php if ($loginroleid < 2): ?>
                <th class="text-center" style="width:90px;" id="thUndoAction">Action</th>
                <?php endif; ?>
              </tr>
            </thead>
            <tbody id="tblUndoModalTraysBody">
              <!-- Loaded rows populated dynamically -->
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal Footer Actions -->
    <div class="scan-modal-footer">
      <button type="button" class="btn-modal-cancel" onclick="closeLoadedTraysModal();">
        <i class="fa fa-times mr-1"></i> Close
      </button>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="js/readytoload.js?v=<?= time() ?>"></script>

<?php
if (!isset($_GET['dz'])) {
    require_once(__DIR__ . '/../footer.php');
}
?>
</body>
</html>
