<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Kolkata');

// -------------------------------------------------------------
// Check Session & Authentication
// -------------------------------------------------------------
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
    require('../../connection.php');
} else {
    require('../../header.php');
}
$loginroleid = isset($loginroleid) ? (int)$loginroleid : (int)($_SESSION['loginroleid'] ?? 4);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Bed Tray Re-Verification & Resolution | Karjee Farm</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

  <!-- Fonts & Icons -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  
  <!-- jQuery, Bootstrap (if not in header), SweetAlert2 & HTML5 QR Scanner -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

  <!-- Module CSS -->
  <link rel="stylesheet" href="css/tray_reverify.css?v=<?= time() ?>">
</head>
<body>

<div class="reverify-page-wrap">

  <!-- Hero Header -->
  <div class="reverify-hero d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h1><i class="fa fa-clipboard-check"></i> Bed Tray Re-Verification & Resolution</h1>
      <p>Audit occupied trays in any polyhouse bed, mark discrepancies to <code>reverify</code>, and resolve via Shifting, Removal, Dispatch, or Verification OK.</p>
    </div>
    <div>
      <a href="../verifiedtraymovement/index.php" class="btn btn-sm btn-outline-light font-weight-bold" style="border-radius:8px; padding:6px 14px; text-decoration:none;">
        <i class="fa fa-truck-moving mr-1"></i> Tray Movement Scanner
      </a>
    </div>
  </div>

  <!-- Filter & Bed Selector Card -->
  <div class="reverify-card">
    <div class="card-head">
      <h2><i class="fa fa-filter text-primary"></i> 1. Select Bed for Audit</h2>
      <span class="badge" style="background:#fef3c7; color:#92400e; font-weight:700; font-size:0.75rem; border-radius:6px; padding:4px 8px;">
        Bed Inventory Mode
      </span>
    </div>

    <div class="filter-form-grid">
      <div>
        <label class="form-label-custom">Location</label>
        <select id="selectFilterLocation" class="form-select-custom">
          <option value="">Loading locations...</option>
        </select>
      </div>

      <div>
        <label class="form-label-custom">Polyhouse <span class="text-danger">*</span></label>
        <select id="selectFilterPolyhouse" class="form-select-custom">
          <option value="">-- Select Polyhouse --</option>
        </select>
      </div>

      <div>
        <label class="form-label-custom">Bed Number <span class="text-danger">*</span></label>
        <select id="selectFilterBed" class="form-select-custom" disabled>
          <option value="">-- Select Polyhouse First --</option>
        </select>
      </div>
    </div>
  </div>

  <!-- Barcode / QR / Order Search Card -->
  <div class="reverify-card">
    <div class="card-head">
      <h2><i class="fa fa-search text-primary"></i> 2. Quick Search: Order Number / Tray Barcode / Bed QR</h2>
      <span class="text-muted" style="font-size:0.8rem;">Search by Order No (e.g. KFP2638), Tray Barcode, or Bed QR</span>
    </div>

    <!-- Mode Selector Tabs -->
    <div class="scanner-toggle-bar">
      <button type="button" class="scanner-tab-btn active" id="tabManual">
        <i class="fa fa-search"></i> <span>Order / Barcode Search</span>
      </button>
      <button type="button" class="scanner-tab-btn" id="tabCamera">
        <i class="fa fa-video"></i> <span>Camera Scanner</span>
      </button>
    </div>

    <!-- Manual Search -->
    <div id="sectionManual">
      <form id="formManualScan">
        <div class="search-input-group mb-2">
          <input type="text" id="inputBarcodeQuery" class="scan-input" placeholder="Enter Order No (e.g. KFP2638, RSO...), Tray Barcode, or Bed QR" autocomplete="off" autofocus>
          <button type="submit" class="btn-scan-action" id="btnSearchBarcode">
            <i class="fa fa-search"></i> <span>Search & Find Bed</span>
          </button>
        </div>
      </form>
      <div class="d-flex justify-content-between align-items-center">
        <small class="text-muted" style="font-size:0.78rem;">
          <i class="fa fa-info-circle text-primary mr-1"></i> Enter <b>Order No</b> (e.g. <code>KFP2638</code>), <b>Tray Barcode</b>, or <b>Bed QR</b> to find and auto-open the bed.
        </small>
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearInput()" style="font-size:0.75rem; padding:2px 8px; border-radius:6px;">
          <i class="fa fa-eraser mr-1"></i> Clear
        </button>
      </div>
    </div>

    <!-- Camera Scanner -->
    <div id="sectionCamera" style="display:none;">
      <div class="camera-viewport-wrap">
        <div id="qrCameraViewport"></div>
        <div class="scanner-laser" id="scannerLaserLine" style="display:none;"></div>
      </div>
      <div class="d-flex gap-2 flex-wrap mt-2 justify-content-center">
        <button type="button" class="btn btn-sm btn-primary" id="btnToggleCamera">
          <i class="fa fa-play mr-1"></i> Start Camera
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnSwitchCamera">
          <i class="fa fa-sync-alt mr-1"></i> Flip Camera
        </button>
      </div>
    </div>
  </div>

  <!-- Loading State -->
  <div id="loadingIndicator" class="empty-box" style="display:none;">
    <i class="fa fa-spinner fa-spin fa-3x text-primary mb-2"></i>
    <h5 class="font-weight-bold">Loading Bed Data...</h5>
    <p class="text-muted small">Fetching occupied and re-verify trays from server.</p>
  </div>

  <!-- Empty Initial Prompt -->
  <div id="emptyStatePrompt" class="empty-box">
    <i class="fa fa-layer-group fa-3x text-muted mb-3" style="opacity:0.4;"></i>
    <h5 class="font-weight-bold text-dark">No Bed Selected</h5>
    <p class="text-muted mx-auto" style="max-width:440px; font-size:0.88rem;">
      Please select a <b>Polyhouse</b> and <b>Bed Number</b> above, or scan a <b>Tray Barcode</b> to view and manage its bed trays.
    </p>
  </div>

  <!-- Content Wrap (Shown when bed is loaded) -->
  <div id="panelContentWrap" style="display:none;">

    <!-- Bed Summary KPI Strip -->
    <div id="bedSummaryStrip" class="bed-summary-strip">
      <div class="bed-info-chips">
        <div class="bed-chip">
          <i class="fa fa-warehouse text-primary"></i> <span>Polyhouse: <strong id="lblBedLocation">-</strong></span>
        </div>
        <div class="bed-chip">
          <i class="fa fa-th-large text-warning"></i> <span>Bed: <strong id="lblBedNumber">-</strong></span>
        </div>
        <div class="bed-chip" id="chipSearchedOrder" style="display:none; background:#eef2ff; border-color:#c7d2fe;">
          <i class="fa fa-receipt text-primary"></i> <span>Order: <strong id="lblSearchedOrderTag" class="text-primary">-</strong></span>
        </div>
      </div>

      <div class="bed-stats-group">
        <div class="stat-pill stat-pill-occupied" id="pillOccupiedStats">
          <i class="fa fa-boxes"></i> <span id="lblOccupiedTraysCount">0</span> Occupied (<span id="lblOccupiedPlantsCount">0</span> Plants)
        </div>
        <div class="stat-pill stat-pill-reverify" id="pillReverifyStats">
          <i class="fa fa-clipboard-check"></i> <span id="lblReverifyTraysCount">0</span> Re-verify (<span id="lblReverifyPlantsCount">0</span> Plants)
        </div>
      </div>
    </div>

    <!-- Navigation Tabs: Occupied Trays vs Re-verify Queue -->
    <div class="nav-tabs-custom">
      <button type="button" class="nav-tab-link active" data-tab="tabOccupied">
        <i class="fa fa-boxes"></i> Occupied Trays in Bed 
        <span class="nav-tab-badge badge-occupied" id="badgeOccupiedTabCount">0</span>
      </button>
      <button type="button" class="nav-tab-link" data-tab="tabReverify">
        <i class="fa fa-clipboard-check"></i> Pending Re-verify Queue 
        <span class="nav-tab-badge badge-reverify" id="badgeReverifyTabCount">0</span>
      </button>
    </div>

    <!-- TAB 1: OCCUPIED TRAYS IN BED -->
    <div id="panelOccupiedView" class="reverify-card">
      
      <!-- Order-Level Action Cards Section -->
      <div class="mb-3">
        <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="small font-weight-bold text-dark text-uppercase" id="lblOrdersSectionTitle" style="letter-spacing:0.03em;">
              <i class="fa fa-layer-group text-primary mr-1"></i> Order Details:
            </span>
          </div>
          <span class="badge bg-light text-muted border font-weight-normal" style="font-size:0.75rem;">
            <i class="fa fa-shield-alt text-success mr-1"></i> Strict Rule: Re-verification applies to all trays of an order
          </span>
        </div>
        <div id="boxBedOrdersList" class="orders-bed-grid">
          <!-- Populated dynamically with Order cards & "Mark All Trays as Re-verify" buttons -->
        </div>
      </div>

      <!-- In-Bed Order Filter & Live Search Bar -->
      <div class="order-filter-toolbar">
        <div class="filter-group">
          <span class="filter-label"><i class="fa fa-filter text-primary"></i> Filter Table by Order:</span>
          <select id="selectInBedOrderFilter" class="form-select form-select-sm" style="width:auto; min-width:220px; font-weight:700;">
            <option value="">-- All Orders in Bed --</option>
          </select>
        </div>
        <div class="d-flex align-items-center gap-1">
          <div class="input-group input-group-sm" style="max-width:250px;">
            <span class="input-group-text bg-white border-end-0"><i class="fa fa-search text-muted"></i></span>
            <input type="text" id="inputInBedSearch" class="form-control border-start-0" placeholder="Filter barcode, order...">
          </div>
          <button type="button" class="btn btn-sm btn-outline-secondary" id="btnClearInBedSearch" style="display:none; font-size:0.75rem; padding:4px 8px;" title="Clear filter">
            <i class="fa fa-times"></i>
          </button>
        </div>
      </div>

      <!-- Order Context Action Banner -->
      <div class="order-context-banner state-idle" id="barOrderAction">
        <div class="d-flex align-items-center gap-2" id="boxOrderBannerLeft">
          <span class="selected-count" id="lblOccupiedSelectedCount">
            <i class="fa fa-info-circle text-primary mr-1"></i> Select an order above to mark its occupied trays as <code>reverify</code>.
          </span>
        </div>
        <div id="boxOrderBannerRight">
          <button type="button" class="btn-mark-reverify" id="btnMarkOrderReverify" disabled>
            <i class="fa fa-clipboard-check mr-1"></i> <span id="lblMarkOrderBtnText">Mark All Trays of Order as Re-verify</span>
          </button>
        </div>
      </div>

      <div class="table-responsive">
        <table class="trays-grid-table">
          <thead>
            <tr>
              <th style="width:45px;">#</th>
              <th>Tray Barcode</th>
              <th>Product & Variety</th>
              <th>Stock Order / PSO</th>
              <th>Plants / Tray</th>
              <th>Current Status</th>
              <?php if ($loginroleid < 3): ?>
              <th style="text-align:center; min-width:130px;">Action</th>
              <?php endif; ?>
            </tr>
          </thead>
          <tbody id="tblOccupiedTraysBody">
          </tbody>
        </table>
      </div>
    </div>

    <!-- TAB 2: PENDING RE-VERIFY QUEUE & RESOLUTION -->
    <div id="panelReverifyView" class="reverify-card" style="display:none;">
      
      <!-- Resolution Action Bar -->
      <div class="batch-action-bar">
        <div class="d-flex align-items-center gap-2">
          <span class="selected-count" id="lblReverifySelectedCount">0 tray(s) selected</span>
        </div>
        <div class="resolution-btn-group">
          <!-- Physical Scan Audit Trigger -->
          <button type="button" class="btn-res-audit" id="btnStartPhysicalAudit" title="Audit by physically scanning present trays in bed">
            <i class="fa fa-qrcode mr-1"></i> Start Physical Scan Audit
          </button>

          <!-- 1. Empty After Shifting -->
          <button type="button" class="btn-res-empty btn-res-action" id="btnResEmptyAfterShifting" title="Mark Empty After Shifting and vacate bed">
            <i class="fa fa-boxes"></i> Empty After Shifting
          </button>

          <!-- 2. Move for Grafting -->
          <button type="button" class="btn-res-grafting btn-res-action" id="btnResMoveGrafting" title="Move trays for Grafting">
            <i class="fa fa-cut"></i> Move for Grafting
          </button>

          <!-- 3. Dispatch -->
          <button type="button" class="btn-res-dispatch btn-res-action" id="btnResDispatch" title="Resolve via Final or Internal Dispatch">
            <i class="fa fa-truck"></i> Dispatch
          </button>

          <!-- 4. Restore / Verify OK -->
          <button type="button" class="btn-res-restore btn-res-action" id="btnResRestore" title="Physical verification passed: restore to Previous Status">
            <i class="fa fa-check-circle"></i> Verify OK (Restore)
          </button>
        </div>
      </div>

      <div class="table-responsive">
        <table class="trays-grid-table">
          <thead>
            <tr>
              <th style="width:45px;">#</th>
              <th>Tray Barcode</th>
              <th>Product & Variety</th>
              <th>Stock Order / PSO</th>
              <th>Plants / Tray</th>
              <th>Status & Auditor</th>
              <?php if ($loginroleid < 3): ?>
              <th style="text-align:center; min-width:130px;">Action</th>
              <?php endif; ?>
            </tr>
          </thead>
          <tbody id="tblReverifyTraysBody">
          </tbody>
        </table>
      </div>
    </div>

  </div>

</div>

<!-- ============================================================= -->
<!-- Physical Bed Audit Modal (Present vs. Missing Reconciliation) -->
<!-- ============================================================= -->
<div class="modal fade" id="modalPhysicalAudit" tabindex="-1" aria-labelledby="modalPhysicalAuditLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content audit-modal-content">
      
      <!-- Modal Header -->
      <div class="modal-header audit-modal-header d-flex justify-content-between align-items-center">
        <div>
          <h5 class="modal-title" id="modalPhysicalAuditLabel">
            <i class="fa fa-clipboard-check text-warning"></i> Physical Bed Audit: Present vs. Missing Reconciliation
          </h5>
          <div class="audit-sub-info">
            Polyhouse: <span class="pill-tag" id="lblAuditPolyhouse">-</span> &nbsp;|&nbsp; 
            Bed: <span class="pill-tag" id="lblAuditBed" style="color:#fde047;">-</span> &nbsp;|&nbsp; 
            Auditing Order: <span class="pill-tag" id="lblAuditOrder">All Re-verify Trays</span>
          </div>
        </div>
        <button type="button" class="close text-white btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close" onclick="window.closeAuditModal()" style="font-size:1.6rem; opacity:0.9; background:none; border:none; color:#fff; cursor:pointer;">&times;</button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body">
        
        <!-- Live Audit KPI Strip -->
        <div class="audit-kpi-grid">
          <div class="audit-kpi-card kpi-total">
            <div class="kpi-icon"><i class="fa fa-layer-group"></i></div>
            <div>
              <div class="kpi-val" id="lblAuditTotalCount">0</div>
              <div class="kpi-lbl">Total Trays in Audit Queue</div>
            </div>
          </div>
          <div class="audit-kpi-card kpi-present">
            <div class="kpi-icon"><i class="fa fa-check-circle"></i></div>
            <div>
              <div class="kpi-val text-success" id="lblAuditPresentCount">0</div>
              <div class="kpi-lbl">Physically Present (Scanned in Bed)</div>
            </div>
          </div>
          <div class="audit-kpi-card kpi-missing">
            <div class="kpi-icon"><i class="fa fa-question-circle"></i></div>
            <div>
              <div class="kpi-val text-warning" id="lblAuditMissingCount">0</div>
              <div class="kpi-lbl">Unscanned / Missing from Bed</div>
            </div>
          </div>
        </div>

        <!-- Rapid Barcode Scanner Box -->
        <div class="audit-scanner-box">
          <div class="scan-header">
            <div class="scan-title">
              <span class="pulse-indicator"></span>
              <i class="fa fa-barcode text-primary mr-1"></i> Scan Barcode of Physical Tray in Bed:
            </div>
            <div class="d-flex align-items-center gap-2">
              <button type="button" class="btn btn-sm btn-outline-secondary" id="btnToggleAuditCamera" style="font-size:0.8rem; padding:4px 12px; border-radius:6px; font-weight:700;">
                <i class="fa fa-camera mr-1"></i> Use Camera
              </button>
              <span class="badge bg-light text-muted border font-weight-normal" style="font-size:0.75rem;">
                <i class="fa fa-keyboard text-info mr-1"></i> Barcode Gun / Keyboard Mode Active
              </span>
            </div>
          </div>

          <!-- Camera Viewport (hidden by default) -->
          <div id="auditCameraWrapper" class="mb-2" style="display:none;">
            <div class="camera-viewport-wrap mx-auto" style="max-height:220px;">
              <div id="auditQrCameraViewport"></div>
            </div>
          </div>

          <!-- Fast Scan Input -->
          <form id="formAuditScanner">
            <div class="audit-scan-input-group">
              <input type="text" id="inputAuditScanBarcode" class="audit-scan-input" placeholder="Scan tray barcode here (e.g. TRY2609233E3B...)" autocomplete="off">
              <button type="submit" class="btn-audit-submit" id="btnManualAuditSubmit">
                <i class="fa fa-check"></i> Verify Present
              </button>
            </div>
          </form>
          <div id="auditScanFeedback" class="audit-feedback-line text-muted">
            <i class="fa fa-info-circle text-primary"></i> Auditor: Walk along the bed and scan each physical tray barcode you see.
          </div>
        </div>

        <!-- Physically Present Trays List -->
        <div class="audit-split-row">
          
          <!-- Physically Present (Scanned) -->
          <div class="audit-split-panel panel-present w-100">
            <div class="audit-split-header">
              <div class="audit-split-title">
                <i class="fa fa-check-circle"></i> Physically Present in Bed (<span id="lblAuditListPresentCount">0</span>)
              </div>
              <span class="badge bg-success text-white" style="font-size:0.75rem; padding:4px 8px;">
                Will Restore to Previous Status
              </span>
            </div>
            <div class="audit-trays-list-wrap" id="listAuditPresent">
              <!-- Present Trays Items -->
            </div>
          </div>

        </div>

      </div>

      <!-- Modal Footer -->
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary font-weight-bold px-3" data-bs-dismiss="modal" data-dismiss="modal" onclick="window.closeAuditModal()">
          <i class="fa fa-times mr-1"></i> Close / Pause Audit
        </button>
        <button type="button" class="btn btn-success font-weight-bold px-4 py-2" id="btnFinishAuditReconcile" style="font-size:0.95rem; box-shadow: 0 4px 12px -2px rgba(5, 150, 105, 0.4);">
          <i class="fa fa-balance-scale mr-1"></i> Finish Audit & Reconcile (<span id="lblReconcileSummaryBtn">0 Present / 0 Missing</span>)
        </button>
      </div>

    </div>
  </div>
</div>

<?php 
if (!isset($_GET['dz'])) {
    require('../../footer.php'); 
}
?>

<script>
  window.LOGIN_ROLE_ID = <?= json_encode((int)$loginroleid) ?>;
</script>
<!-- Custom Module JS -->
<script src="js/tray_reverify.js?v=<?= time() ?>"></script>
</body>
</html>
