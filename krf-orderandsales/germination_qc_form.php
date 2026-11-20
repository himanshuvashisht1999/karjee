<?php
date_default_timezone_set('Asia/Kolkata');
require('../header.php');
include('functions/orders_functions.php');
$mdid = "";
if (isset($_GET['mdid'])) {
  $mdid = $_GET['mdid'];
}
$dddr = loginmodulesdetails_frommaster($authconn, $companyid, $subcompanyid, $projectid, "moduleid", $mdid);
$groupid = "";
if (count($dddr) > 0) {
  $groupid = $dddr[0]['groupid'];
}
?>
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Google Fonts & Font Awesome -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<!-- jQuery, SweetAlert2, HTML5-QRCode v2.3.8 & jsQR Scanner -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>

<style>
  :root {
    --font-main: 'Plus Jakarta Sans', sans-serif;
    --font-mono: 'JetBrains Mono', monospace;
    --primary: #2563eb;
    --primary-dark: #1d4ed8;
    --primary-light: #eff6ff;
    --emerald: #10b981;
    --slate-900: #0f172a;
    --slate-800: #1e293b;
    --slate-700: #334155;
    --slate-600: #475569;
    --slate-500: #64748b;
    --slate-200: #e2e8f0;
    --slate-100: #f1f5f9;
    --card-radius: 16px;
    --shadow-soft: 0 4px 20px -2px rgba(15, 23, 42, 0.07), 0 2px 6px -2px rgba(15, 23, 42, 0.04);
  }

  body {
    font-family: var(--font-main);
    background-color: #f1f5f9;
    color: var(--slate-800);
    -webkit-font-smoothing: antialiased;
  }

  .container-main {
    max-width: 950px;
    margin: 0 auto;
    padding: 10px 15px 3.5rem;
  }

  /* Hero Header */
  .qc-hero {
    background: linear-gradient(135deg, #065f46 0%, #047857 50%, #10b981 100%);
    color: #ffffff;
    border-radius: var(--card-radius);
    padding: 1.5rem 1.75rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 10px 25px -5px rgba(4, 120, 87, 0.35);
    position: relative;
    overflow: hidden;
  }
  .qc-hero h1 {
    font-size: 1.45rem;
    font-weight: 800;
    margin: 0 0 0.35rem 0;
    display: flex;
    align-items: center;
    gap: 0.6rem;
  }
  .qc-hero p {
    margin: 0;
    font-size: 0.88rem;
    color: #d1fae5;
    max-width: 700px;
  }

  /* Card Panels */
  .qc-main-card {
    background: #ffffff;
    border-radius: var(--card-radius);
    border: 1px solid var(--slate-200);
    box-shadow: var(--shadow-soft);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
  }

  .card-head-custom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid var(--slate-100);
    padding-bottom: 0.85rem;
    margin-bottom: 1.25rem;
    flex-wrap: wrap;
    gap: 0.5rem;
  }
  .card-head-custom h2 {
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--slate-900);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  /* Scanner Mode Tabs */
  .scanner-tabs {
    display: flex;
    gap: 0.5rem;
    background: var(--slate-100);
    padding: 5px;
    border-radius: 12px;
    margin-bottom: 1.25rem;
  }
  .scanner-tab-btn {
    flex: 1;
    border: none;
    background: transparent;
    padding: 10px 16px;
    font-size: 0.92rem;
    font-weight: 700;
    color: var(--slate-600);
    border-radius: 9px;
    cursor: pointer;
    transition: all 0.15s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
  }
  .scanner-tab-btn.active {
    background: #ffffff;
    color: var(--primary);
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  }

  /* Manual / Barcode Gun Input Group */
  .search-input-group {
    display: flex;
    gap: 0.5rem;
    width: 100%;
    box-sizing: border-box;
    align-items: stretch;
  }
  .scan-input {
    flex: 1;
    width: 100%;
    min-height: 52px !important;
    height: 52px !important;
    line-height: normal !important;
    border: 2px solid var(--slate-200) !important;
    border-radius: 10px !important;
    padding: 10px 16px !important;
    font-size: 1.05rem !important;
    font-family: var(--font-mono) !important;
    font-weight: 700 !important;
    outline: none;
    box-sizing: border-box !important;
    display: block !important;
    background: #ffffff !important;
    color: var(--slate-900) !important;
    transition: border-color 0.2s, box-shadow 0.2s;
  }
  .scan-input:focus {
    border-color: var(--primary) !important;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.18) !important;
  }
  .btn-scan-action {
    min-height: 52px !important;
    height: 52px !important;
    padding: 0 1.5rem !important;
    border: none !important;
    border-radius: 10px !important;
    font-weight: 700 !important;
    font-size: 1rem !important;
    cursor: pointer;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 0.45rem !important;
    box-sizing: border-box !important;
    transition: all 0.2s ease;
    background: var(--primary) !important;
    color: #ffffff !important;
    white-space: nowrap !important;
  }
  .btn-scan-action:hover {
    background: var(--primary-dark) !important;
    transform: translateY(-1px);
  }

  /* Camera Viewport & Controls */
  #qrCameraViewport {
    width: 100%;
    min-height: 260px;
    background: #0f172a;
    border-radius: 12px;
    overflow: hidden;
    position: relative;
  }
  .camera-ctrl-bar {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.75rem;
    flex-wrap: wrap;
  }

  /* QC Result View Styles */
  #traydetailsformark {
    display: none;
  }
  .qc-summary-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    margin-bottom: 20px;
    overflow: hidden;
    border: 1px solid #eef2f7;
  }
  .qc-card-header {
    background: #f8faff;
    padding: 12px 20px;
    border-bottom: 1px solid #eef2f7;
    font-weight: 700;
    color: #334155;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 15px;
  }
  .qc-card-body {
    padding: 20px;
  }
  .info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
  }
  .info-item {
    display: flex;
    flex-direction: column;
  }
  .info-label {
    font-size: 12px;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
    font-weight: 600;
  }
  .info-value {
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
  }
  .metric-box {
    background: #f1f5f9;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
  }
  .metric-value {
    font-size: 24px;
    font-weight: 700;
    color: #2563eb;
    display: block;
  }
  .metric-label {
    font-size: 12px;
    color: #64748b;
  }
  .fail-tag {
    color: #ef4444;
  }
  .bed-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
  }

  @media (max-width: 768px) {
    .container-main {
      padding: 5px 10px 3rem !important;
    }
    .qc-hero {
      padding: 1.15rem 1rem;
    }
    .qc-hero h1 {
      font-size: 1.2rem;
    }
    .qc-main-card {
      padding: 1rem;
    }
    .search-input-group {
      display: flex !important;
      flex-direction: column !important;
      gap: 0.75rem !important;
      width: 100% !important;
    }
    .scan-input {
      width: 100% !important;
      min-height: 54px !important;
      height: 54px !important;
      font-size: 1.1rem !important;
      padding: 12px 16px !important;
      border-radius: 12px !important;
      box-sizing: border-box !important;
      display: block !important;
    }
    .btn-scan-action {
      width: 100% !important;
      min-height: 52px !important;
      height: 52px !important;
      font-size: 1.05rem !important;
      border-radius: 12px !important;
      justify-content: center !important;
      box-sizing: border-box !important;
    }
    .bed-stats-grid {
      grid-template-columns: repeat(2, 1fr) !important;
      gap: 8px;
    }
    .metric-box {
      padding: 10px !important;
    }
    .metric-value {
      font-size: 20px !important;
    }
    .metric-label {
      font-size: 11px !important;
    }
  }

  @media (max-width: 480px) {
    .bed-stats-grid {
      grid-template-columns: 1fr !important;
    }
  }
</style>

<div class="container-main">
  <!-- Hero Section -->
  <div class="qc-hero">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div>
        <h1><i class="fa fa-seedling"></i> Germination Monitoring & QC</h1>
        <p>Scan Tray QR code or enter Barcode to inspect germination rates, record failure counts, and track quality.</p>
      </div>
      <div>
        <button class="btn btn-sm btn-light text-success fw-bold shadow-sm" onclick="location.reload();" style="border-radius: 8px;">
          <i class="fas fa-sync-alt me-1"></i> Refresh
        </button>
      </div>
    </div>
  </div>

  <!-- Scanner Container Card -->
  <div class="qc-main-card" id="scannerCardContainer">
    <div class="card-head-custom">
      <h2><i class="fa fa-camera text-primary"></i> Scan Germination Tray QR / Barcode</h2>
      <span class="badge" id="badgeDeviceMode" style="background:#e0f2fe; color:#0369a1; font-weight:700; font-size:0.75rem; border-radius:6px; padding:5px 9px;">Auto Scanner Active</span>
    </div>

    <!-- Scanner Mode Tabs -->
    <div class="scanner-tabs">
      <button type="button" class="scanner-tab-btn active" id="tabManual">
        <i class="fa fa-barcode"></i> Barcode Gun / Text
      </button>
      <button type="button" class="scanner-tab-btn" id="tabCamera">
        <i class="fa fa-video"></i> Camera Scanner
      </button>
    </div>

    <!-- Mode 1: Manual / Barcode Gun Input -->
    <div id="sectionManual">
      <form id="formManualScan" onsubmit="event.preventDefault(); triggerManualLookup();">
        <div class="search-input-group mb-2">
          <input type="text" id="inputBarcodeQuery" class="scan-input" placeholder="Scan or enter Tray Barcode / QR..." autocomplete="off" autofocus>
          <button type="submit" class="btn-scan-action" id="btnSearchBarcode">
            <i class="fa fa-search"></i> Search
          </button>
        </div>
      </form>
      <small class="text-muted d-block" style="font-size: 0.8rem;">
        <i class="fa fa-info-circle me-1 text-primary"></i> Barcode scanner devices will auto-search upon reading QR codes.
      </small>
    </div>

    <!-- Mode 2: Camera Scanner Viewport -->
    <div id="sectionCamera" style="display:none;">
      <div id="qrCameraViewport"></div>
      <div class="camera-ctrl-bar">
        <button type="button" class="btn btn-sm btn-primary" id="btnToggleCamera">
          <i class="fa fa-play me-1"></i> Start Camera
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnSwitchCamera">
          <i class="fa fa-sync-alt me-1"></i> Flip Camera
        </button>
        <button type="button" class="btn btn-sm btn-outline-warning" id="btnToggleTorch" style="display:none;">
          <i class="fa fa-lightbulb me-1"></i> Torch
        </button>
        <button type="button" class="btn btn-sm btn-outline-info" onclick="openPhotoCapture('environment')" title="Take a live photo with camera">
          <i class="fa fa-camera me-1"></i> Snap Photo
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="openPhotoCapture('')" title="Upload QR image from gallery">
          <i class="fa fa-image me-1"></i> Upload Image
        </button>
        <input type="file" id="inputScanImageFile" accept="image/*" capture="environment" style="position:fixed; top:-2000px; left:-2000px; opacity:0; pointer-events:none;" onchange="handleQrImageUpload(this)">
      </div>
      <div id="cameraStatusMsg" class="small text-muted mt-2" style="font-size:0.82rem;">
        <i class="fa fa-info-circle text-primary me-1"></i> Point your phone camera directly at the tray QR code.
      </div>
    </div>

    <!-- Quick Reset Button -->
    <div class="mt-3 text-end">
      <button type="button" class="btn btn-sm btn-outline-danger" id="btnClearScan">
        <i class="fa fa-redo me-1"></i> Reset / Clear
      </button>
    </div>
  </div>

  <!-- Redesigned Result View -->
  <div id="traydetailsformark">
    <div class="mb-4">
      <button class="btn btn-primary btn-lg w-100 py-3 shadow-sm rounded-pill" id="backToScannerBtn" style="font-weight: 700; border: none; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
        <i class="bi bi-qr-code-scan me-2"></i> Scan Again / Back to Scanner
      </button>
    </div>

    <div id="trayInfo"></div>

    <div class="qc-summary-card mt-4">
      <div class="qc-card-header" style="background: #fff5f5; border-left: 4px solid #ef4444;">
        <i class="fas fa-exclamation-triangle text-danger"></i> Record Germination Failure
      </div>
      <div class="qc-card-body">
        <div class="alert alert-light border-0 py-2 mb-4" style="background:#fefce8; color:#854d0e; font-size:13px;">
          <strong>Note:</strong> Record only unhealthy or dead plants observed in this specific tray.
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label fw-bold" style="font-size:14px">Failed Plant Count</label>
            <input type="number" id="unhealthyplants" class="form-control form-control-lg" placeholder="0" min="0">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label fw-bold" style="font-size:14px">Remarks (if any)</label>
            <textarea id="traygrmRemark" class="form-control" rows="1" placeholder="Add observations..."></textarea>
          </div>
        </div>

        <div class="text-center mt-3">
          <button class="btn btn-danger btn-lg px-5 shadow-sm" id="savegerminationfailureBtn"
            onclick="UpdateGerminationFailure('unhealthyplants', 'traygrmRemark', 'savegerminationfailureBtn');">
            Update Failure Records
          </button>
        </div>
      </div>
    </div>

    <div id="noResult" class="alert alert-warning mt-4 text-center d-none">
      No tray details found.
    </div>
  </div>
</div>

<!-- Modal: History -->
<div class="modal fade" id="QcHistory_modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" style="max-width: 95%;">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fas fa-seedling"></i> Monitoring History</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"
          onclick="$('#QcHistory_modal').modal('hide')">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-3 mb-3 fw-bold">Product Name</div>
          <div class="col-md-9 mb-3"><span id="qch_productname"></span></div>
        </div>
        <div class="row">
          <div class="col-md-3 mb-3 fw-bold">Tray Name</div>
          <div class="col-md-9 mb-3"><span id="qch_trayname"></span></div>
        </div>
        <div class="row">
          <div class="col-md-3 mb-3 fw-bold">Grafting By/Date</div>
          <div class="col-md-9 mb-3"><span id="qch_graftedbydate"></span></div>
        </div>
        <div class="row">
          <div class="col-md-12 mb-3">
            <div class="table-responsive">
              <table class="table table-bordered table-mobile" id="qchistoryviewtbl">
                <thead>
                  <tr>
                    <th>S.No</th>
                    <th>Monitoring Date</th>
                    <th>Monitoring Status</th>
                    <th>Remark</th>
                    <th>Monitoring Done By</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Today Done -->
<div class="modal fade" id="TodayQcDone_modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" style="max-width: 95%;">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fas fa-seedling"></i> Today Completed Monitoring</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"
          onclick="$('#TodayQcDone_modal').modal('hide')">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-12 mb-3">
            <div class="table-responsive">
              <table class="table table-bordered table-mobile" id="qcstatusviewtbl">
                <thead>
                  <tr>
                    <th>S.No</th>
                    <th>Tray Name</th>
                    <th>Product Name</th>
                    <th>Sown Date</th>
                    <th>Last Monitored</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
include('../footer.php');
?>
<script type="text/javascript" src="<?php echo $prefix; ?>krf-orderandsales/js/pjs_function.js"></script>
<script>
$(document).ready(function () {
  let html5QrCode = null;
  let isCameraScanning = false;
  let currentFacingMode = "environment";
  let currentCameraId = null;
  let availableCameras = [];
  let isTorchOn = false;

  // Device Auto-Detection
  const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || window.innerWidth < 768;

  if (isMobile) {
    $('#badgeDeviceMode').text('Mobile Camera Mode');
  } else {
    $('#badgeDeviceMode').text('Scanner Gun / Desktop Mode');
  }

  // Audio Beep Feedback
  function playScanBeep() {
    try {
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.type = "sine";
      osc.frequency.setValueAtTime(850, ctx.currentTime);
      gain.gain.setValueAtTime(0.2, ctx.currentTime);
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.start();
      osc.stop(ctx.currentTime + 0.12);
    } catch(e) {}
  }

  // QR String Cleaner
  function cleanQrPayload(str) {
    if (!str) return '';
    str = String(str).trim();
    if (str.indexOf('{') === 0 && str.lastIndexOf('}') === str.length - 1) {
      try {
        const obj = JSON.parse(str);
        return obj.barcode || obj.bedqrcode || obj.stockorder_no || obj.psono || obj.code || obj.qr_text || obj.qr_code || str;
      } catch(e) {}
    }
    if (str.indexOf('http://') === 0 || str.indexOf('https://') === 0 || str.indexOf('://') !== -1) {
      try {
        const url = new URL(str, window.location.origin);
        const params = url.searchParams;
        return params.get('barcode') || params.get('stockorder_no') || params.get('stockorder') || params.get('psono') || params.get('bedqrcode') || params.get('code') || params.get('qr_code') || params.get('bed_no') || str;
      } catch(e) {}
    }
    return str.replace(/[\x00-\x1F\x7F\xC2\xA0]/g, '').trim();
  }

  // 1. Tab Switching (Barcode Gun vs Camera)
  $('#tabManual').on('click', function() {
    $(this).addClass('active');
    $('#tabCamera').removeClass('active');
    $('#sectionManual').show();
    $('#sectionCamera').hide();
    stopCamera();
    $('#inputBarcodeQuery').focus();
  });

  $('#tabCamera').on('click', function() {
    $(this).addClass('active');
    $('#tabManual').removeClass('active');
    $('#sectionManual').hide();
    $('#sectionCamera').show();
    currentFacingMode = "environment"; // Default to back camera
    currentCameraId = null;
    startCamera();
  });

  // 2. Camera QR Scanner Setup (Prioritizes Back Camera)
  async function startCamera(preferredId = null) {
    if (isCameraScanning) return;
    
    if (typeof Html5Qrcode === 'undefined') {
      Swal.fire('Error', 'QR Scanner library not loaded. Please check your internet connection.', 'error');
      return;
    }

    $('#cameraStatusMsg').html('<i class="fa fa-spinner fa-spin me-1"></i> Opening back camera...');
    $('#btnToggleCamera').prop('disabled', true);

    try {
      if (html5QrCode) {
        try {
          if (html5QrCode.isScanning) {
            await html5QrCode.stop();
          }
          html5QrCode.clear();
        } catch(e) {}
        html5QrCode = null;
      }

      html5QrCode = new Html5Qrcode("qrCameraViewport");

      const scanConfig = {
        fps: 15,
        qrbox: (viewfinderWidth, viewfinderHeight) => {
          const w = Math.floor(viewfinderWidth * 0.85);
          const h = Math.floor(viewfinderHeight * 0.75);
          return {
            width: Math.max(180, Math.min(320, w)),
            height: Math.max(180, Math.min(320, h))
          };
        },
        aspectRatio: 1.333333
      };

      const onScanSuccess = (decodedText) => {
        playScanBeep();
        stopCamera();
        const cleanPayload = cleanQrPayload(decodedText);
        $('#tabManual').click();
        $('#inputBarcodeQuery').val(cleanPayload);
        fetchGerminationTrayData(cleanPayload);
      };

      // Strategy 1: facingMode environment
      let cameraConfig = preferredId ? preferredId : { facingMode: "environment" };

      try {
        await html5QrCode.start(cameraConfig, scanConfig, onScanSuccess, () => {});
      } catch (firstErr) {
        console.warn("Direct facingMode: environment failed, querying device list:", firstErr);
        
        // Strategy 2: query devices and find back camera
        const devices = await Html5Qrcode.getCameras();
        if (devices && devices.length > 0) {
          availableCameras = devices;
          
          let backCam = devices.find(d => /back|rear|environment|0/i.test(d.label));
          if (!backCam) {
            const nonFront = devices.filter(d => !/front|selfie|user/i.test(d.label));
            backCam = nonFront.length > 0 ? nonFront[nonFront.length - 1] : devices[devices.length - 1];
          }
          
          const chosenId = backCam ? backCam.id : devices[0].id;
          currentCameraId = chosenId;
          await html5QrCode.start(chosenId, scanConfig, onScanSuccess, () => {});
        } else {
          throw firstErr;
        }
      }

      isCameraScanning = true;
      $('#btnToggleCamera').prop('disabled', false).html('<i class="fa fa-stop me-1"></i> Stop Camera').removeClass('btn-primary').addClass('btn-danger');
      $('#cameraStatusMsg').html('<i class="fa fa-check-circle text-success me-1"></i> Back camera active. Align QR code or Barcode inside viewfinder.');

      // Check available cameras to show flip button
      try {
        const devs = await Html5Qrcode.getCameras();
        if (devs && devs.length > 1) {
          availableCameras = devs;
          $('#btnSwitchCamera').show();
        }
      } catch(e){}

      // Check Torch capability
      try {
        const caps = html5QrCode.getRunningTrackCapabilities();
        if (caps && caps.torch) {
          $('#btnToggleTorch').show();
        } else {
          $('#btnToggleTorch').hide();
        }
      } catch(e) {
        $('#btnToggleTorch').hide();
      }

    } catch (err) {
      console.error("Camera start failed:", err);
      isCameraScanning = false;
      if (html5QrCode) {
        try { html5QrCode.clear(); } catch(e){}
        html5QrCode = null;
      }
      $('#btnToggleCamera').prop('disabled', false).html('<i class="fa fa-play me-1"></i> Start Camera').removeClass('btn-danger').addClass('btn-primary');
      $('#cameraStatusMsg').html('<span class="text-danger"><i class="fa fa-exclamation-triangle me-1"></i> Live camera failed. Tap "Snap Photo / Upload" to scan directly.</span>');

      let errMsg = 'Could not access the mobile back camera stream. Please check browser camera permissions or use the "Snap Photo / Upload" button below.';
      if (!window.isSecureContext && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
        errMsg = 'Mobile browsers require HTTPS for live camera video streaming. Please use the "Snap Photo / Upload" button to take a photo directly with your camera.';
      }

      Swal.fire({
        icon: 'info',
        title: 'Camera Access',
        text: errMsg,
        confirmButtonText: 'OK'
      });
    }
  }

  async function stopCamera() {
    if (html5QrCode && isCameraScanning) {
      try {
        if (html5QrCode.isScanning) {
          await html5QrCode.stop();
        }
        html5QrCode.clear();
      } catch (err) {
        console.warn("Camera stop error:", err);
      }
    }
    html5QrCode = null;
    isCameraScanning = false;
    $('#btnToggleCamera').prop('disabled', false).html('<i class="fa fa-play me-1"></i> Start Camera').removeClass('btn-danger').addClass('btn-primary');
    $('#btnToggleTorch').hide();
    $('#cameraStatusMsg').html('<i class="fa fa-info-circle text-primary me-1"></i> Point your phone camera directly at the QR Code tag.');
  }

  $('#btnToggleCamera').on('click', function() {
    if (isCameraScanning) {
      stopCamera();
    } else {
      currentFacingMode = "environment";
      startCamera();
    }
  });

  $('#btnSwitchCamera').on('click', async function() {
    if (availableCameras && availableCameras.length > 1) {
      let currIdx = availableCameras.findIndex(c => c.id === currentCameraId);
      let nextIdx = (currIdx + 1) % availableCameras.length;
      currentCameraId = availableCameras[nextIdx].id;
      if (isCameraScanning) {
        await stopCamera();
        await startCamera(currentCameraId);
      }
    } else {
      currentFacingMode = (currentFacingMode === "environment") ? "user" : "environment";
      currentCameraId = null;
      if (isCameraScanning) {
        await stopCamera();
        await startCamera();
      }
    }
  });

  $('#btnToggleTorch').on('click', function() {
    if (!html5QrCode || !isCameraScanning) return;
    isTorchOn = !isTorchOn;
    try {
      html5QrCode.applyVideoConstraints({
        advanced: [{ torch: isTorchOn }]
      }).then(() => {
        $(this).toggleClass('btn-warning btn-outline-warning');
      }).catch(e => {
        console.warn("Torch toggle not supported:", e);
      });
    } catch(e) {}
  });

  // Native Photo Snap / File Picker
  window.openPhotoCapture = function(mode) {
    const inp = document.getElementById('inputScanImageFile');
    if (!inp) return;
    if (mode === 'environment') {
      inp.setAttribute('capture', 'environment');
    } else {
      inp.removeAttribute('capture');
    }
    inp.value = '';
    inp.click();
  };

  function readFileAsDataURL(file) {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onload = () => resolve(reader.result);
      reader.onerror = reject;
      reader.readAsDataURL(file);
    });
  }

  function loadImageElement(src) {
    return new Promise((resolve, reject) => {
      const img = new Image();
      img.onload = () => resolve(img);
      img.onerror = reject;
      img.src = src;
    });
  }

  // Multi-Engine Image Decoder (Native BarcodeDetector + jsQR + Html5Qrcode)
  async function decodePhotoFile(file) {
    // Stage 1: Native BarcodeDetector
    if ('BarcodeDetector' in window) {
      try {
        const formats = ['qr_code', 'code_128', 'code_39', 'code_93', 'ean_13', 'ean_8', 'upc_a', 'upc_e', 'data_matrix', 'itf'];
        const detector = new BarcodeDetector({ formats: formats });
        const imgBitmap = await createImageBitmap(file);
        const results = await detector.detect(imgBitmap);
        if (results && results.length > 0 && results[0].rawValue) {
          console.log("Decoded via native BarcodeDetector:", results[0].rawValue);
          return results[0].rawValue;
        }
      } catch (bdErr) {
        console.warn("BarcodeDetector decode attempt:", bdErr);
      }
    }

    // Stage 2: Canvas Image Processing + Multi-pass jsQR
    try {
      const dataUrl = await readFileAsDataURL(file);
      const img = await loadImageElement(dataUrl);

      const maxDim = 1280;
      let w = img.width;
      let h = img.height;
      if (w > maxDim || h > maxDim) {
        if (w > h) {
          h = Math.round((h * maxDim) / w);
          w = maxDim;
        } else {
          w = Math.round((w * maxDim) / h);
          h = maxDim;
        }
      }

      const canvas = document.createElement('canvas');
      canvas.width = w;
      canvas.height = h;
      const ctx = canvas.getContext('2d');
      ctx.drawImage(img, 0, 0, w, h);

      if (typeof jsQR !== 'undefined') {
        // Pass 2A: Full Image Standard
        const imgData = ctx.getImageData(0, 0, w, h);
        let code = jsQR(imgData.data, w, h, { inversionAttempts: "dontInvert" }) ||
                   jsQR(imgData.data, w, h, { inversionAttempts: "attemptBoth" });
        if (code && code.data) {
          console.log("Decoded via jsQR (full):", code.data);
          return code.data;
        }

        // Pass 2B: Center Crop Focus Area (70% center region)
        const cropW = Math.round(w * 0.70);
        const cropH = Math.round(h * 0.70);
        const cropX = Math.round((w - cropW) / 2);
        const cropY = Math.round((h - cropH) / 2);
        const cropData = ctx.getImageData(cropX, cropY, cropW, cropH);
        let cropCode = jsQR(cropData.data, cropW, cropH, { inversionAttempts: "attemptBoth" });
        if (cropCode && cropCode.data) {
          console.log("Decoded via jsQR (crop):", cropCode.data);
          return cropCode.data;
        }

        // Pass 2C: High-Contrast Grayscale
        const grayData = ctx.getImageData(0, 0, w, h);
        const d = grayData.data;
        for (let i = 0; i < d.length; i += 4) {
          const lum = 0.299 * d[i] + 0.587 * d[i + 1] + 0.114 * d[i + 2];
          const val = lum > 120 ? 255 : 0;
          d[i] = val;
          d[i + 1] = val;
          d[i + 2] = val;
        }
        let grayCode = jsQR(grayData.data, w, h, { inversionAttempts: "attemptBoth" });
        if (grayCode && grayCode.data) {
          console.log("Decoded via jsQR (high-contrast):", grayCode.data);
          return grayCode.data;
        }
      }
    } catch (cvErr) {
      console.warn("Canvas / jsQR decode error:", cvErr);
    }

    // Stage 3: Html5Qrcode scanFile
    if (typeof Html5Qrcode !== 'undefined') {
      try {
        if (!html5QrCode) {
          html5QrCode = new Html5Qrcode("qrCameraViewport");
        }
        const resText = await html5QrCode.scanFile(file, false);
        if (resText) {
          console.log("Decoded via Html5Qrcode scanFile:", resText);
          return resText;
        }
      } catch (h5Err) {
        console.warn("Html5Qrcode scanFile error:", h5Err);
      }
    }

    return null;
  }

  // Handle Photo Snap / Upload
  window.handleQrImageUpload = async function(input) {
    if (!input.files || input.files.length === 0) return;
    const file = input.files[0];

    Swal.fire({
      title: 'Scanning Photo...',
      text: 'Decoding QR code from image...',
      allowOutsideClick: false,
      didOpen: () => { Swal.showLoading(); }
    });

    try {
      const decodedText = await decodePhotoFile(file);
      if (decodedText) {
        Swal.close();
        playScanBeep();
        const cleanPayload = cleanQrPayload(decodedText);
        $('#tabManual').click();
        $('#inputBarcodeQuery').val(cleanPayload);
        fetchGerminationTrayData(cleanPayload);
      } else {
        Swal.close();
        Swal.fire({
          icon: 'error',
          title: 'No Code Detected',
          text: 'Could not detect a clear QR Code in the photo. Please hold your camera closer to the tag, ensure good lighting, and take another photo.'
        });
      }
    } catch (err) {
      Swal.close();
      console.error("Photo scan error:", err);
      Swal.fire({
        icon: 'error',
        title: 'Scan Error',
        text: 'An error occurred while processing the photo: ' + (err.message || err)
      });
    } finally {
      input.value = '';
    }
  };

  // 3. Manual / Barcode Lookup
  window.triggerManualLookup = function() {
    const q = $('#inputBarcodeQuery').val().trim();
    if (!q) {
      Swal.fire('Input Required', 'Please scan or enter a Tray Barcode / QR code.', 'info');
      $('#inputBarcodeQuery').focus();
      return;
    }
    fetchGerminationTrayData(q);
  };

  // 4. Global Hardware Scanner Gun Keystroke Listener (Catches rapid scans anywhere on page)
  let barcodeBuffer = '';
  let lastKeyTime = Date.now();
  $(document).on('keydown', function(e) {
    const now = Date.now();
    const diff = now - lastKeyTime;
    lastKeyTime = now;

    // If typing manually with delay > 90ms, reset buffer
    if (diff > 90) {
      barcodeBuffer = '';
    }

    // Enter Key
    if (e.which === 13) {
      if (barcodeBuffer.length >= 3) {
        e.preventDefault();
        const scannedCode = barcodeBuffer.trim();
        barcodeBuffer = '';
        $('#tabManual').click();
        $('#inputBarcodeQuery').val(scannedCode);
        fetchGerminationTrayData(scannedCode);
      }
    } else if (e.key && e.key.length === 1 && !e.ctrlKey && !e.altKey && !e.metaKey) {
      barcodeBuffer += e.key;
    }
  });

  // 5. Backend Fetch & Render
  function fetchGerminationTrayData(qrCodeValue) {
    const cleanVal = cleanQrPayload(qrCodeValue);
    if (!cleanVal) return;

    var routurl = $("body").data("routurl") || '';
    var companyid = $("body").data("companyid") || 1;
    var subcompanyid = $("body").data("subcompanyid") || 1;
    var projectid = $("body").data("projectid") || 17;

    const $btn = $('#btnSearchBarcode');
    const origBtn = $btn.html();
    $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
    $('#inputBarcodeQuery').val(cleanVal);

    $.ajax({
      url: routurl + "krf-orderandsales/modal/grafting_qc_modal.php?action=fetchGerminationTrayData",
      method: "POST",
      dataType: "json",
      data: { qr_code: cleanVal, companyid: companyid, subcompanyid: subcompanyid, projectid: projectid },
      beforeSend: function () {
        $("#traydetailsformark").hide();
        $("#noResult").hide();
      },
      success: function (res) {
        $btn.prop('disabled', false).html(origBtn);

        if (res.status === "success" && res.data && res.data.length > 0) {
          stopCamera();
          $("#scannerCardContainer").hide();
          const d = res.data[0];

          $("#savegerminationfailureBtn").data("qrcode", cleanVal);
          $("#savegerminationfailureBtn").data("polyhouseplantid", d.polyhouseplantid);
          $("#traydetailsformark").slideDown(200);

          let allBedsHtml = '';
          let currentBedHtml = '';
          let hasOtherBeds = false;

          if (d.bed_stats_list && d.bed_stats_list.length > 0) {
            let currentBedIdent = String(d.bed_no || d.bedrackname || '').trim();
            d.bed_stats_list.forEach(function(bed) {
              let isCurrent = (bed.polyhouseid == d.polyhouseid && (bed.bed_no == currentBedIdent || bed.bedrackname == currentBedIdent));
              let actualBedName = bed.bed_no ? bed.bed_no : (bed.bedrackname ? bed.bedrackname : 'Unknown Bed');
              let bedNameDisplay = (bed.polyhousename ? bed.polyhousename + " - " : "") + actualBedName;
              let verifiedCnt = bed.verified_trays !== undefined ? bed.verified_trays : 0;
              let unverifiedCnt = bed.unverified_trays !== undefined ? bed.unverified_trays : 0;
              
              if (isCurrent) {
                currentBedHtml += `
                <div class="bed-strip border rounded p-3 mb-2" style="background-color: #f0f7ff; border-color: #cce3ff !important;">
                  <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                    <div class="fw-bold text-primary">
                      <i class="fas fa-layer-group me-2"></i> ${bedNameDisplay}
                      <span class="badge bg-success ms-2">Current</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                      <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                        <i class="fas fa-check-circle me-1"></i> Verified: ${verifiedCnt}
                      </span>
                      <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-1">
                        <i class="fas fa-clock me-1"></i> Unverified: ${unverifiedCnt}
                      </span>
                      <div class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                        <i class="fas fa-seedling me-1"></i> ${(bed.bed_total_plants || 0).toLocaleString()} Total Plants
                      </div>
                    </div>
                  </div>
                  <div class="bed-stats-grid mb-2">
                    <div class="metric-box bg-white">
                      <span class="metric-value text-primary">${bed.bed_total_trays}</span>
                      <span class="metric-label">Total Trays in Bed</span>
                    </div>
                    <div class="metric-box bg-white border border-success-subtle">
                      <span class="metric-value text-success">${verifiedCnt}</span>
                      <span class="metric-label">Verified Trays</span>
                    </div>
                    <div class="metric-box bg-white border border-warning-subtle">
                      <span class="metric-value text-warning" style="color:#d97706 !important;">${unverifiedCnt}</span>
                      <span class="metric-label">Unverified Trays</span>
                    </div>
                    <div class="metric-box bg-white">
                      <span class="metric-value" style="color:#64748b">${bed.bed_ten_percent}</span>
                      <span class="metric-label">10% Sampling Qty</span>
                    </div>
                  </div>
                  <div class="bed-stats-grid">
                    <div class="metric-box bg-white border border-danger-subtle">
                      <span class="metric-value text-dark">${(bed.bed_total_plants || 0).toLocaleString()}</span>
                      <span class="metric-label">Total Plants in Bed</span>
                    </div>
                    <div class="metric-box bg-white border border-danger-subtle">
                      <span class="metric-value fail-tag">${bed.bed_fail_trays}</span>
                      <span class="metric-label">Failed Trays (${bed.bed_fail_percent}%)</span>
                    </div>
                    <div class="metric-box bg-white border border-danger-subtle">
                      <span class="metric-value fail-tag">${(bed.bed_fail_plants || 0).toLocaleString()}</span>
                      <span class="metric-label">Failed Plants (${bed.bed_plant_fail_percent || 0}%)</span>
                    </div>
                    <div class="metric-box bg-white border border-danger-subtle">
                      <span class="metric-value text-danger" style="font-size:16px; font-weight:700;">${bed.bed_fail_trays} Trays (${(bed.bed_fail_plants || 0).toLocaleString()} Plts)</span>
                      <span class="metric-label">Full Fail Mark Trays/Plants</span>
                    </div>
                  </div>
                </div>`;
              } else {
                hasOtherBeds = true;
                allBedsHtml += `
                <div class="bed-strip other-bed-strip border rounded p-3 mb-2 bg-white" style="display:none;">
                  <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                    <div class="fw-bold text-dark">
                      <i class="fas fa-layer-group text-secondary me-2"></i> ${bedNameDisplay}
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                      <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                        <i class="fas fa-check-circle me-1"></i> Verified: ${verifiedCnt}
                      </span>
                      <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-1">
                        <i class="fas fa-clock me-1"></i> Unverified: ${unverifiedCnt}
                      </span>
                      <div class="badge bg-light text-secondary border px-2 py-1">
                        <i class="fas fa-seedling me-1"></i> ${(bed.bed_total_plants || 0).toLocaleString()} Total Plants
                      </div>
                    </div>
                  </div>
                  <div class="bed-stats-grid mb-2">
                    <div class="metric-box bg-light border-0">
                      <span class="metric-value text-primary">${bed.bed_total_trays}</span>
                      <span class="metric-label">Total Trays in Bed</span>
                    </div>
                    <div class="metric-box bg-light border border-success-subtle">
                      <span class="metric-value text-success">${verifiedCnt}</span>
                      <span class="metric-label">Verified Trays</span>
                    </div>
                    <div class="metric-box bg-light border border-warning-subtle">
                      <span class="metric-value text-warning" style="color:#d97706 !important;">${unverifiedCnt}</span>
                      <span class="metric-label">Unverified Trays</span>
                    </div>
                    <div class="metric-box bg-light border-0">
                      <span class="metric-value" style="color:#64748b">${bed.bed_ten_percent}</span>
                      <span class="metric-label">10% Sampling Qty</span>
                    </div>
                  </div>
                  <div class="bed-stats-grid">
                    <div class="metric-box bg-light border border-danger-subtle">
                      <span class="metric-value text-dark">${(bed.bed_total_plants || 0).toLocaleString()}</span>
                      <span class="metric-label">Total Plants in Bed</span>
                    </div>
                    <div class="metric-box bg-light border border-danger-subtle">
                      <span class="metric-value fail-tag">${bed.bed_fail_trays}</span>
                      <span class="metric-label">Failed Trays (${bed.bed_fail_percent}%)</span>
                    </div>
                    <div class="metric-box bg-light border border-danger-subtle">
                      <span class="metric-value fail-tag">${(bed.bed_fail_plants || 0).toLocaleString()}</span>
                      <span class="metric-label">Failed Plants (${bed.bed_plant_fail_percent || 0}%)</span>
                    </div>
                    <div class="metric-box bg-light border border-danger-subtle">
                      <span class="metric-value text-danger" style="font-size:16px; font-weight:700;">${bed.bed_fail_trays} Trays (${(bed.bed_fail_plants || 0).toLocaleString()} Plts)</span>
                      <span class="metric-label">Full Fail Mark Trays/Plants</span>
                    </div>
                  </div>
                </div>`;
              }
            });
          }

          let trayContent = `
          <!-- Order Details Card -->
          <div class="qc-summary-card">
            <div class="qc-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
              <div><i class="fas fa-file-invoice text-primary"></i> Order Details</div>
              <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                <i class="fas fa-calendar-alt me-1"></i> Sowing Date: ${d.sowingdate_internaldsp}
              </span>
            </div>
            <div class="qc-card-body">
              <div class="info-grid">
                <div class="info-item">
                  <span class="info-label">Order No</span>
                  <span class="info-value font-monospace">${d.orderno}</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Product</span>
                  <span class="info-value text-primary">${d.productname}</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Seed Name / Lot No</span>
                  <span class="info-value">${d.seedname || 'N/A'} / ${d.lotno || 'N/A'}</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Sowing Date (Internal)</span>
                  <span class="info-value text-primary font-weight-bold">${d.sowingdate_internaldsp} <small class="text-muted" style="font-size:12px;">(${d.sowingdate_internal})</small></span>
                </div>
                <div class="info-item">
                  <span class="info-label">Plan Qty / Sown Qty</span>
                  <span class="info-value">${d.order_plan_qty} / ${d.order_total_sown} Plants</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Germination Failure (Stats)</span>
                  <span class="info-value text-danger font-weight-bold">${d.order_total_fail} Plants (${d.order_fail_percent}%)</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Bed Statistics Card -->
          <div class="qc-summary-card">
            <div class="qc-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
              <div class="d-flex align-items-center gap-2">
                <i class="fas fa-layer-group text-success"></i>
                <span>Bed Statistics (Current Location)</span>
                <span class="badge bg-success-subtle text-success border border-success-subtle ms-2">
                  <i class="fas fa-calendar-alt me-1"></i> ${d.sowingdate_internaldsp}
                </span>
              </div>
              ${hasOtherBeds ? '<button type="button" class="btn btn-sm btn-outline-primary" onclick="$(\'.other-bed-strip\').slideToggle();">View Other Beds</button>' : ''}
            </div>
            <div class="qc-card-body p-3" style="background-color: #fafafa;">
              ${currentBedHtml}
              ${allBedsHtml}
            </div>
          </div>

          <!-- Total Germination Failure Summary Card -->
          <div class="qc-summary-card">
            <div class="qc-card-header bg-danger-subtle text-danger border-danger-subtle d-flex justify-content-between align-items-center flex-wrap gap-2">
              <div>
                <i class="fas fa-chart-pie me-2 text-danger"></i>
                <strong>Total Germination Failure Summary</strong>
                <span class="badge bg-light text-danger border border-danger-subtle ms-2">
                  <i class="fas fa-calendar-alt me-1"></i> Sowing Date: ${d.sowingdate_internaldsp}
                </span>
              </div>
              <span class="badge bg-danger text-white fs-6 px-3 py-1">${d.order_fail_percent}% Germination Failure</span>
            </div>
            <div class="qc-card-body p-3">
              <div class="bed-stats-grid mb-2">
                <div class="metric-box bg-white border">
                  <span class="metric-value text-primary">${d.order_total_trays}</span>
                  <span class="metric-label">Total Sown Trays</span>
                </div>
                <div class="metric-box bg-white border">
                  <span class="metric-value fail-tag">${d.order_fail_trays || 0}</span>
                  <span class="metric-label">Total Failed Trays</span>
                </div>
                <div class="metric-box bg-white border">
                  <span class="metric-value fail-tag">${d.order_tray_fail_percent || 0}%</span>
                  <span class="metric-label">Total Tray Failure %</span>
                </div>
                <div class="metric-box bg-white border">
                  <span class="metric-value text-danger" style="font-size:16px; font-weight:700;">${d.order_fail_trays || 0} Trays (${(d.order_fail_trays_qty || 0).toLocaleString()} Plts)</span>
                  <span class="metric-label">Full Fail Mark Trays/Capacity</span>
                </div>
              </div>
              <div class="bed-stats-grid">
                <div class="metric-box bg-white border">
                  <span class="metric-value text-dark">${d.order_total_sown.toLocaleString()}</span>
                  <span class="metric-label">Total Sown Plants</span>
                </div>
                <div class="metric-box bg-white border">
                  <span class="metric-value fail-tag">${d.order_total_fail.toLocaleString()}</span>
                  <span class="metric-label">Failed Plants (Germination)</span>
                </div>
                <div class="metric-box bg-danger-subtle border border-danger">
                  <span class="metric-value fail-tag fs-3">${d.order_fail_percent}%</span>
                  <span class="metric-label fw-bold text-danger">Germination Failure Rate %</span>
                </div>
                <div class="metric-box bg-white border">
                  <span class="metric-value text-success">${(d.order_total_sown - d.order_total_fail).toLocaleString()}</span>
                  <span class="metric-label">Healthy / Usable Plants</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Specific Tray Info -->
          <div class="qc-summary-card">
            <div class="qc-card-header">
              <i class="fas fa-tag"></i> Active Tray: ${d.trayname}
            </div>
            <div class="qc-card-body py-2">
              <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                  <span class="badge bg-light text-dark border me-2">Position: ${d.tray_position}</span>
                  <span class="badge bg-light text-dark border">Cavities: ${d.tray_cavitycount}</span>
                </div>
                <div>${d.qchistorybtn || ''}</div>
              </div>
            </div>
          </div>`;

          $("#trayInfo").html(trayContent);

          // Scroll to result view on mobile
          if (window.innerWidth < 768) {
            $('html, body').animate({
              scrollTop: $('#traydetailsformark').offset().top - 60
            }, 300);
          }

        } else {
          Swal.fire("Attention", res.message || "No germination tray details found.", "warning");
          $('#inputBarcodeQuery').focus();
        }
      },
      error: function(xhr, status, err) {
        $btn.prop('disabled', false).html(origBtn);
        Swal.fire("Server Error", "Unable to connect to server: " + (err || status), "error");
      }
    });
  }

  // 6. Reset & Back to Scanner Button Handlers
  $('#btnClearScan').on('click', function() {
    $('#inputBarcodeQuery').val('').focus();
    $('#traydetailsformark').hide();
    $('#trayInfo').empty();
  });

  $('#backToScannerBtn').on('click', function () {
    $("#traydetailsformark").hide();
    $("#scannerCardContainer").slideDown(200);

    if (isMobile) {
      $('#tabCamera').click();
    } else {
      $('#tabManual').click();
      $('#inputBarcodeQuery').val('').focus();
    }
  });

  // 7. Initial Auto-Selection Startup
  if (isMobile) {
    // Mobile device: switch to Camera Scanner tab
    $('#tabCamera').click();
  } else {
    // Desktop / Scanner Gun: select manual tab and focus input
    $('#tabManual').click();
    $('#inputBarcodeQuery').focus();
  }
});
</script>