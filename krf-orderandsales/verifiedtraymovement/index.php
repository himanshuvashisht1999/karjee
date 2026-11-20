<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Kolkata');

// -------------------------------------------------------------
// Check Session & Auth
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

if (!isset($loginroleid) || $loginroleid === "" || $loginroleid === null) {
    if (isset($_SESSION['loginroleid']) && $_SESSION['loginroleid'] !== "" && $_SESSION['loginroleid'] !== null) {
        $loginroleid = (int)$_SESSION['loginroleid'];
    } elseif (isset($key_parts[1]) && $key_parts[1] !== "") {
        $loginroleid = (int)$key_parts[1];
    } else {
        $loginroleid = 4;
    }
} else {
    $loginroleid = (int)$loginroleid;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Plant Tray QR Movement & Scanner | Karjee Farm</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

  <!-- Fonts & Icons -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  
  <!-- jQuery, SweetAlert2, HTML5 QR Code & jsQR Scanner -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>

  <!-- Custom Module CSS -->
  <link rel="stylesheet" href="css/verifiedtraymovement.css?v=<?= time() ?>">
</head>
<body>

<div class="move-page-wrap">
  
  <!-- Hero Section -->
  <div class="move-hero" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
    <div>
      <h1><i class="fa fa-qrcode"></i> Tray Movement QR Scanner</h1>
      <p>Scan tray QR or barcode to shift trays or mark Ready to Sell.</p>
    </div>
    <div style="position:relative; z-index:2;">
      <a href="../../opensale_booking/opensale_register.php" class="btn" style="background:linear-gradient(135deg, #10b981 0%, #059669 100%); color:#fff; font-weight:700; border-radius:8px; padding:9px 15px; text-decoration:none; display:inline-flex; align-items:center; gap:8px; box-shadow:0 2px 8px rgba(16,185,129,0.35); font-size:0.875rem; border:none;">
        <i class="fa fa-boxes-packing"></i> Open Sale Register & Return
      </a>
    </div>
  </div>

  <!-- Scanner Dock Card -->
  <div class="move-card">
    <div class="card-head">
      <h2><i class="fa fa-camera text-primary"></i> Scan Plant Tray QR / Barcode</h2>
      <span class="badge" style="background:#e0e7ff; color:#3730a3; font-weight:700; font-size:0.75rem; border-radius:6px; padding:4px 8px;">Multi-Tray Batch Mode</span>
    </div>

    <!-- Mode Tabs -->
    <div class="scanner-tabs">
      <button type="button" class="scanner-tab-btn active" id="tabManual">
        <i class="fa fa-barcode"></i> <span>Manual / Gun</span>
      </button>
      <button type="button" class="scanner-tab-btn" id="tabCamera">
        <i class="fa fa-video"></i> <span>Camera Scanner</span>
      </button>
    </div>

    <!-- Mode 1: Manual / Barcode Gun Input -->
    <div id="sectionManual">
      <form id="formManualScan" onsubmit="event.preventDefault(); triggerScanLookup();">
        <div class="search-input-group mb-2">
          <input type="text" id="inputBarcodeQuery" class="scan-input" placeholder="Scan or enter Tray Barcode" autocomplete="off">
          <button type="submit" class="btn-scan-action" id="btnSearchBarcode">
            <i class="fa fa-plus-circle"></i> <span>Add Tray</span>
          </button>
        </div>
      </form>
      <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 6px;">
        <small class="text-muted" style="font-size: 0.78rem;">
          <i class="fa fa-info-circle text-primary mr-1"></i> Scan or enter <b>Tray Barcodes only</b>. <span class="text-danger font-weight-bold"></span>
        </small>
        <button type="button" class="btn btn-sm btn-outline-danger" id="btnClearScan" onclick="clearInput()" style="font-size:0.75rem; padding:3px 8px; border-radius:6px; white-space:nowrap;">
          <i class="fa fa-eraser mr-1"></i> Clear Input
        </button>
      </div>
    </div>

    <!-- Mode 2: Camera Scanner Viewport -->
    <div id="sectionCamera" style="display:none;">
      <div class="camera-viewport-wrap">
        <div id="qrCameraViewport"></div>
        <div class="scanner-laser" id="scannerLaserLine" style="display:none;"></div>
        <div class="reticle-corner reticle-tl"></div>
        <div class="reticle-corner reticle-tr"></div>
        <div class="reticle-corner reticle-bl"></div>
        <div class="reticle-corner reticle-br"></div>
      </div>
      <div class="camera-actions-bar">
        <button type="button" class="btn btn-sm btn-primary btn-camera-ctrl" id="btnToggleCamera">
          <i class="fa fa-play mr-1"></i> Start Camera
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary btn-camera-ctrl" id="btnSwitchCamera">
          <i class="fa fa-sync-alt mr-1"></i> Flip Camera
        </button>
        <button type="button" class="btn btn-sm btn-outline-warning btn-camera-ctrl" id="btnToggleTorch" style="display:none;">
          <i class="fa fa-lightbulb mr-1"></i> Torch
        </button>
        <button type="button" class="btn btn-sm btn-outline-info btn-camera-ctrl" onclick="openPhotoCapture('environment')" title="Take a live photo with phone camera">
          <i class="fa fa-camera mr-1"></i> Snap Photo
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary btn-camera-ctrl" onclick="openPhotoCapture('')" title="Upload QR image or barcode photo from gallery">
          <i class="fa fa-image mr-1"></i> Upload Image
        </button>
        <input type="file" id="inputScanImageFile" accept="image/*" capture="environment" style="position:fixed; top:-2000px; left:-2000px; opacity:0; pointer-events:none;" onchange="handleQrImageUpload(this)">
      </div>
      <div id="cameraStatusMsg" class="small text-muted mt-2" style="font-size:0.8rem;">
        <i class="fa fa-info-circle text-primary mr-1"></i> Point your phone camera directly at the <b>Tray Barcode tag</b>. <span class="text-danger font-weight-bold">(Order IDs not allowed)</span>
      </div>
    </div>
  </div>

  <!-- 1-Line Scanned Tray Status Banner / Warning -->
  <div id="trayScanStatusAlert" class="tray-status-alert mb-2.5" style="display:none;">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div class="d-flex align-items-center gap-2 flex-wrap" id="trayScanStatusContent">
        <!-- Injected via JS -->
      </div>
      <button type="button" class="btn-close-status-alert" onclick="$('#trayScanStatusAlert').slideUp(150);" title="Dismiss">
        <i class="fa fa-times"></i>
      </button>
    </div>
  </div>

  <!-- Live Batch Summary Bar (Visible when trays are scanned) -->
  <div id="batchSummaryBox" class="batch-summary-strip" style="display:none;">
    <div class="batch-info-chips">
      <div class="batch-chip">
        <i class="fa fa-file-invoice text-primary"></i> <span>Order: <strong id="lblBatchOrder">-</strong></span>
      </div>
      <div class="batch-chip">
        <i class="fa fa-layer-group text-warning"></i> <span>Active in Bed: <strong id="lblBatchBedTotal">0 Trays</strong></span>
      </div>
      <div class="batch-chip">
        <i class="fa fa-warehouse text-info"></i> <span>Polyhouse: <strong id="lblBatchPolyhouse">0</strong></span>
      </div>
      <div class="batch-chip">
        <i class="fa fa-check-circle text-success"></i> <span>Ready to Sale: <strong id="lblBatchReady">0</strong></span>
      </div>
      <div class="batch-chip">
        <i class="fa fa-map-marker-alt text-danger"></i> <span>Location: <strong id="lblBatchBed">-</strong></span>
      </div>
      <div class="batch-chip batch-chip-full">
        <i class="fa fa-seedling text-success"></i> <span>Product: <strong id="lblBatchProduct">-</strong></span>
      </div>
    </div>
    <div class="batch-stats-group">
      <div class="stat-badge-pill stat-pill-trays" style="background:#e0e7ff; color:#3730a3; border:1px solid #c7d2fe;">
        <i class="fa fa-check-square"></i> <span id="lblCountCheckedTrays">0</span> / <span id="lblCountScannedTrays">0</span> Checked
      </div>
      <div class="stat-badge-pill stat-pill-plants">
        <i class="fa fa-leaf"></i> <span id="lblCountScannedPlants">0</span> Plants
      </div>
      <button type="button" class="btn btn-sm btn-outline-danger btn-clear-grid" onclick="clearAllScanned()" title="Clear Scanned Trays List">
        <i class="fa fa-trash-alt mr-1"></i> Clear Grid
      </button>
    </div>
  </div>

  <!-- Scanned Trays Grid (Table & Mobile Cards) -->
  <div class="move-card">
    <div class="card-head flex-wrap gap-2">
      <div>
        <h2 style="margin-bottom:2px;"><i class="fa fa-table-list text-primary"></i> Scanned Trays Grid</h2>
        <span class="text-muted" style="font-size:0.82rem;" id="lblGridCounter">0 active trays (0 checked)</span>
      </div>
    </div>

    <!-- Auto Select Trays Toolbar (Quick Movement Quantity Selector) -->
    <div id="trayAutoSelectToolbar" class="tray-auto-select-strip mb-3" style="display:none;">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2.5">
        <!-- Left Side: Label and Numeric Input -->
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <label for="inputAutoSelectCount" class="auto-select-label mb-0">
            <i class="fa fa-layer-group text-primary mr-1"></i> <b>How many trays to move?</b>
          </label>
          <div class="tray-count-input-wrap">
            <input type="number" id="inputAutoSelectCount" class="tray-count-input" min="0" placeholder="0" autocomplete="off" oninput="handleAutoSelectCountInput(this)" onkeypress="return (event.charCode >= 48 && event.charCode <= 57)">
            <span class="tray-count-suffix">Trays</span>
          </div>
        </div>

        <!-- Right Side: Suggested Quick Count Switch Buttons (Aligned to Right) -->
        <div class="d-flex align-items-center justify-content-end gap-1.5 flex-wrap ml-auto" id="boxSuggestedQuickBtns">
          <!-- Populated via JS -->
        </div>
      </div>
    </div>

    <!-- Empty Grid Notice -->
    <div id="emptyGridPlaceholder" class="empty-grid-box">
      <i class="fa fa-barcode fa-3x text-muted mb-2" style="opacity:0.35;"></i>
      <h4 class="font-weight-bold text-dark mb-1" style="font-size:1.05rem;">No Trays Scanned Yet</h4>
      <p class="text-muted mx-auto mb-0" style="max-width:380px; font-size:0.86rem;">
        Scan tray QR codes or enter barcodes using the scanner above. Each scanned tray will be collected here for batch movement.
      </p>
    </div>

    <!-- Dynamic Trays Container -->
    <div id="traysTableContainer" class="trays-grid-wrap" style="display:none;">
      
      <!-- Mobile Cards View (< 768px) -->
      <div id="tblScannedTraysCards" class="trays-cards-mobile-wrap" style="display:none;">
        <!-- Populated via JS -->
      </div>

      <!-- Desktop Table View (>= 768px) with Fixed Height (Max 10 rows visible at a time) -->
      <div class="trays-table-scroll-wrap table-responsive">
        <table class="trays-grid-table">
          <thead>
            <tr>
              <th style="width:48px; text-align:center;">
                <input type="checkbox" id="chkSelectAllTrays" class="tray-select-chk" onchange="handleSelectAllChange(this)" title="Check / Uncheck All">
              </th>
              <th style="width:45px; text-align:center;">#</th>
              <th>Tray Barcode</th>
              <th class="col-hide-field" style="display:none;">Order / PSO</th>
              <th class="col-hide-field" style="display:none;">Product & Variety</th>
              <th>Bed & Polyhouse</th>
              <!-- <th>Plants/Tray</th> -->
              <th>Current Status</th>
              <th class="text-center" style="width:80px;">Action</th>
            </tr>
          </thead>
          <tbody id="tblScannedTraysBody">
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Movement Action Panels (Mark as Ready to Sell & Execute Movement) -->
  <div id="panelMovementActions" style="display:none;">
    
    <!-- Quick Action: Mark Ready to Sell (Visible ONLY when active trays with status 'Polyhouse' > 0) -->
    <div class="action-panel-quick modern-rts-panel" id="panelQuickRts" style="display:none;">
      <div class="d-flex align-items-center gap-3">
        <div class="rts-icon-badge">
          <i class="fa fa-seedling"></i>
        </div>
        <div>
          <h3 class="quick-title"><i class="fa fa-check-circle text-success"></i> Mark as Ready to Sell</h3>
          <p class="quick-sub">
            Update active Polyhouse trays (<span id="lblQuickRtsScope" class="font-weight-bold">0 Trays</span>) to <b>Ready to Sale</b> status
          </p>
        </div>
      </div>
      <div>
        <button type="button" class="btn-action-rts" id="btnExecuteRts" onclick="triggerMarkReadyToSale()">
          <i class="fa fa-seedling"></i> Mark as Ready to Sell
        </button>
      </div>
    </div>

    <!-- Standard Movements & Shifting Form (Visible ONLY when checked trays count > 0) -->
    <div class="move-card modern-movement-panel" id="panelExecuteMovement" style="display:none;">
      <div class="card-head movement-head flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
          <div class="movement-icon-badge">
            <i class="fa fa-truck-moving text-primary"></i>
          </div>
          <div>
            <h2 class="mb-0 font-weight-bold" style="font-size:1.08rem;">Execute Plant Movement / Shifting</h2>
            <span class="text-muted" style="font-size:0.8rem;">Perform bed shift, polyhouse transfer, or status updates for selected trays</span>
          </div>
        </div>
        <div class="d-flex align-items-center">
          <span class="badge badge-selected-trays" id="lblSelectedTraysBadge">
            <i class="fa fa-check-circle mr-1"></i> <span id="lblCheckedTraysMoveCount">0</span> Selected
          </span>
        </div>
      </div>

      <!-- Mobile / Tablet Sticky Top Submit Button (Visible on mobile/tablet <992px, Hidden on Desktop) -->
      <div class="mobile-top-action-bar d-lg-none" id="mobileTopMovementAction">
        <button type="submit" form="formPlantMovement" class="btn-submit-movement modern-submit-btn btn-mobile-top-submit" id="btnSubmitMovementMobile">
          <i class="fa fa-truck-moving mr-1.5"></i> <span id="lblSubmitMovementTextMobile">Confirm & Move 0 Checked Trays</span>
        </button>
      </div>

      <form id="formPlantMovement" onsubmit="event.preventDefault(); submitMovement();">
        <div class="row g-4 movement-form-grid">
          
          <!-- Movement Reason -->
          <div class="col-md-6 col-12 movement-field-wrap">
            <label class="form-label-custom">
              <i class="fa fa-question-circle text-primary mr-1"></i> Why are you moving entered tray? <span class="text-danger">*</span>
            </label>
            <div class="custom-select-wrapper">
              <select id="selectMovementReason" name="movement_reason" class="form-select-custom modern-select" required onchange="handleMovementReasonChange()">
                <option value="">-- Select Movement Reason --</option>
                <option value="Polyhouse Shifting">Polyhouse Shifting</option>
                <option value="Bed Shifting">Bed Shifting</option>
                <?php if ($loginroleid < 3): ?>
                <option value="Move for Grafting">Move for Grafting</option>
                <option value="Healing">Healing</option>
                <option value="Hardening">Hardening</option>
                <option value="Clip Stick Removal">Clip Stick Removal</option>
                <option value="Empty After Grafting">Empty After Grafting</option>
                <?php endif; ?>
                <option value="Empty After Shifting">Empty After Shifting</option>
                <option value="Move for Opensale">Move for Opensale</option>
                <option value="Remove After Over Age">Remove After Over Age</option>
                <option value="Tray Dead">Tray Dead</option>
                <?php if ($loginroleid < 3): ?>
                <option value="Removed">Removed</option>
                <option value="Add Trays">Add Trays</option>
                <option value="Grafted">Grafted</option>
                <?php endif; ?>
              </select>
            </div>
          </div>

          <!-- Target Bed Selection (for Bed Shifting - Same Polyhouse) -->
          <div class="col-md-6 col-12 movement-field-wrap" id="boxTargetBed" style="display:none;">
            <label class="form-label-custom">
              <i class="fa fa-map-marker-alt text-success mr-1"></i> Target Bed No. (Same Polyhouse) <span class="text-danger">*</span>
            </label>
            <div class="custom-select-wrapper">
              <select id="selectTargetBed" name="target_bed_no" class="form-select-custom modern-select">
                <option value="">-- Select Destination Bed --</option>
              </select>
            </div>
          </div>

          <!-- Target Polyhouse (for Polyhouse Shifting) -->
          <div class="col-md-6 col-12 movement-field-wrap" id="boxTargetPolyhouse" style="display:none;">
            <label class="form-label-custom">
              <i class="fa fa-warehouse text-info mr-1"></i> Target Polyhouse <span class="text-danger">*</span>
            </label>
            <div class="custom-select-wrapper">
              <select id="selectTargetPolyhouse" name="target_polyhouseid" class="form-select-custom modern-select" onchange="handlePolyhouseChange()">
                <option value="">-- Select Destination Polyhouse --</option>
              </select>
            </div>
          </div>

          <!-- Target Bed in Destination Polyhouse -->
          <div class="col-md-6 col-12 movement-field-wrap" id="boxTargetPolyhouseBed" style="display:none;">
            <label class="form-label-custom">
              <i class="fa fa-layer-group text-warning mr-1"></i> Target Bed in Destination Polyhouse <span class="text-danger">*</span>
            </label>
            <div class="custom-select-wrapper">
              <select id="selectTargetPolyhouseBed" name="target_polyhouse_bed_no" class="form-select-custom modern-select">
                <option value="">-- Select Destination Bed --</option>
              </select>
            </div>
          </div>

          <!-- Target Open Sale Location (for Move for Opensale) -->
          <div class="col-md-6 col-12 movement-field-wrap" id="boxOpenSaleLocation" style="display:none;">
            <label class="form-label-custom">
              <i class="fa fa-shopping-cart text-success mr-1"></i> Which Open Sale Location? <span class="text-danger">*</span>
            </label>
            <div class="custom-select-wrapper">
              <select id="selectOpenSaleLocation" name="open_sale_location" class="form-select-custom modern-select">
                <option value="">-- Select Open Sale Location --</option>
              </select>
            </div>
          </div>

          <!-- Movement Narration -->
          <div class="col-12 movement-field-wrap">
            <label class="form-label-custom" id="lblMovementNarration">
              <i class="fa fa-comment-alt text-secondary mr-1"></i> Movement Narration / Remark <span class="text-muted font-weight-normal" id="lblNarrationReqStatus">(Optional)</span>
            </label>
            
            <div id="narrationNoticeBox" style="display:none; border-radius:8px; padding:10px 14px; margin-bottom:10px;">
              <div class="d-flex align-items-start" style="gap: 10px;">
                <i id="narrationNoticeIcon" class="fa fa-info-circle mt-0.5" style="font-size: 1.15rem;"></i>
                <div style="font-size: 0.86rem; line-height: 1.45;">
                  <strong id="narrationNoticeTitle" class="d-block mb-0.5"></strong>
                  <span id="narrationNoticeText"></span>
                </div>
              </div>
            </div>

            <textarea id="inputCustomNarration" name="custom_narration" class="form-textarea-custom modern-textarea" placeholder="Optional notes for movement record..."></textarea>
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-1.5">
              <small class="text-muted" style="font-size: 0.78rem;">
                <i class="fa fa-check-circle text-success mr-1"></i> System records your username and timestamp automatically in movement remarks.
              </small>
            </div>
          </div>

          <!-- Submit Button Centered -->
          <div class="col-12 mt-4 text-center">
            <button type="submit" class="btn-submit-movement modern-submit-btn" id="btnSubmitMovement">
              <i class="fa fa-truck-moving mr-1.5"></i> <span id="lblSubmitMovementText">Confirm & Move 0 Checked Trays</span>
            </button>
          </div>

        </div>
      </form>
    </div>

  </div>

</div>

<?php 
if (!isset($_GET['dz'])) {
    require('../../footer.php'); 
}
?>


<!-- Custom Module JS -->
<input type="hidden" id="hidden_loginroleid" value="<?= (int)$loginroleid ?>">
<script>
  window.userRoleId = <?= (int)$loginroleid ?>;
  console.log('[VerifiedTrayMovement] Initialized with userRoleId:', window.userRoleId, 'isAdmin (<3):', window.userRoleId < 3);
</script>
<script src="js/verifiedtraymovement.js?v=<?= time() ?>"></script>
</body>
</html>
