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
<!-- jQuery (Required for Select2 and other logic) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Modern Libraries -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- QR Code Scanner -->
<script src="https://unpkg.com/html5-qrcode@2.3.7/html5-qrcode.min.js"></script>
<!-- Select2 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- Vehicle Calculator Functions -->
<script src="js/vehicle_calculator.js"></script>

<style>
    :root {
        --primary-glass: rgba(255, 255, 255, 0.9);
        --accent-color: #0d6efd;
        --success-btn: #198754;
        --danger-btn: #dc3545;
        --glass-border: rgba(255, 255, 255, 0.3);
        --shadow-elevation: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
    }

    body {
        background: #f0f2f5;
        min-height: 100vh;
    }

    .container-main {
        max-width: 900px;
        margin: 0 auto;
        padding: 20px 15px 3.5rem;
    }

    .glass-card {
        background: var(--primary-glass);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        border-radius: 20px;
        border: 1px solid var(--glass-border);
        box-shadow: var(--shadow-elevation);
        padding: 24px;
        margin-bottom: 2.5rem !important;
        transition: all 0.3s ease;
    }

    .header-section {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        flex-wrap: wrap;
        gap: 15px;
    }

    .header-title h3 {
        font-weight: 800;
        color: #1a202c;
        margin: 0;
        font-size: 1.5rem;
    }

    .header-title small {
        display: block;
        color: #718096;
        font-size: 0.85rem;
    }

    /* Segmented Control */
    .method-selector {
        background: #edf2f7;
        padding: 4px;
        border-radius: 12px;
        display: flex;
        margin-bottom: 25px;
    }

    .method-btn {
        flex: 1;
        border: none;
        padding: 10px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.2s;
        background: transparent;
        color: #4a5568;
    }

    .method-btn.active {
        background: white;
        color: var(--accent-color);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    }

  /* --- Scanner Interface --- */
  .scanner-wrapper {
      background: #f8fafc;
      border-radius: 30px;
      padding: 2rem;
      border: 2px solid #e2e8f0;
      text-align: center;
      margin-bottom: 1.5rem;
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
  }

  .scanner-active {
      border-color: var(--accent-color);
      background: #fff;
      box-shadow: 0 20px 50px rgba(13, 110, 253, 0.1);
  }

  .reader-container {
      position: relative;
      width: 100%;
      max-width: 320px;
      margin: 0 auto;
      border-radius: 24px;
      overflow: hidden;
      background: #000;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
  }

  #reader {
      width: 100% !important;
      border: none !important;
  }

  #reader video {
      border-radius: 24px;
      object-fit: cover;
  }

  /* Custom Scanner Overlay */
  .scanner-overlay {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      z-index: 5;
      pointer-events: none;
      display: flex;
      align-items: center;
      justify-content: center;
  }

  .scan-region-highlight {
      position: absolute;
      width: 250px;
      height: 250px;
      border: 2px solid rgba(255, 255, 255, 0.4);
      border-radius: 24px;
      box-shadow: 0 0 0 4000px rgba(0, 0, 0, 0.5); /* Scrim effect */
  }

  .scan-line {
      position: absolute;
      width: 100%;
      height: 4px;
      background: linear-gradient(to right, transparent, var(--accent-color), transparent);
      box-shadow: 0 0 15px var(--accent-color);
      top: 0;
      z-index: 10;
      animation: scanMove 2.5s ease-in-out infinite;
  }

  @keyframes scanMove {
      0% { top: 10%; opacity: 0; }
      20% { opacity: 1; }
      80% { opacity: 1; }
      100% { top: 90%; opacity: 0; }
  }

  .scanner-corner {
      position: absolute;
      width: 30px;
      height: 30px;
      border: 4px solid var(--accent-color);
      z-index: 11;
  }

  .corner-tl { top: 0; left: 0; border-right: none; border-bottom: none; border-top-left-radius: 12px; }
  .corner-tr { top: 0; right: 0; border-left: none; border-bottom: none; border-top-right-radius: 12px; }
  .corner-bl { bottom: 0; left: 0; border-right: none; border-top: none; border-bottom-left-radius: 12px; }
  .corner-br { bottom: 0; right: 0; border-left: none; border-top: none; border-bottom-right-radius: 12px; }

  .scan-success-flash {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(34, 197, 94, 0.3);
      z-index: 20;
      display: none;
      animation: flashAnim 0.5s ease-out;
  }

  @keyframes flashAnim {
      0% { opacity: 1; }
      100% { opacity: 0; }
  }

  /* Dynamic Scanner State Borders */
  .scanner-wrapper.scanner-not-captured {
      border-color: #ef4444 !important; /* Vivid Red */
      box-shadow: 0 20px 50px rgba(239, 68, 68, 0.15) !important;
  }
  .scanner-wrapper.scanner-not-captured .scanner-corner {
      border-color: #ef4444 !important;
  }
  .scanner-wrapper.scanner-not-captured .scan-line {
      background: linear-gradient(to right, transparent, #ef4444, transparent) !important;
      box-shadow: 0 0 15px #ef4444 !important;
  }

  .scanner-wrapper.scanner-captured {
      border-color: #10b981 !important; /* Emerald Green */
      box-shadow: 0 20px 50px rgba(16, 185, 129, 0.2) !important;
      background: #f0fdf4 !important;
  }
  .scanner-wrapper.scanner-captured .scanner-corner {
      border-color: #10b981 !important;
  }
  .scanner-wrapper.scanner-captured .scan-line {
      background: linear-gradient(to right, transparent, #10b981, transparent) !important;
      box-shadow: 0 0 15px #10b981 !important;
  }

    .btn-action {
        border-radius: 12px;
        padding: 12px 25px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: transform 0.2s;
    }

    .input-premium {
        border-radius: 12px;
        padding: 12px 16px;
        border: 2px solid #e2e8f0;
        font-weight: 500;
        transition: border-color 0.2s;
    }

    .input-premium:focus {
        border-color: var(--accent-color);
        box-shadow: none;
    }

    .info-summary {
        background: #f8fafc;
        border-radius: 15px;
        padding: 20px;
        border: 1px solid #e2e8f0;
        margin-top: 20px;
    }

    .info-item {
        margin-bottom: 12px;
        display: flex;
        justify-content: space-between;
        border-bottom: 1px dashed #e2e8f0;
        padding-bottom: 8px;
    }

    .info-label {
        font-weight: 600;
        color: #64748b;
    }

    .info-value {
        font-weight: 700;
        color: #1e293b;
    }

    .form-label {
        font-weight: 700;
        color: #4a5568;
        font-size: 0.9rem;
        margin-bottom: 8px;
    }
    
    .select2-container--default .select2-selection--single {
        border-radius: 12px;
        height: 50px;
        padding: 10px;
        border: 2px solid #e2e8f0;
    }
</style>

<div class="container-main">
    <!-- Header -->
    <div class="header-section">
        <div class="header-title">
            <h3>🌱 Plant Movement Tracking</h3>
            <small><i class="far fa-clock me-1"></i><?php echo date('d-m-Y h:i:s A'); ?></small>
        </div>
        <div class="header-actions">
            <a href="../../modulesCell/indexdisplay.php?k=<?php echo $key; ?>&grpid=<?php echo $groupid; ?>&mdid=<?php echo $mdid; ?>" class="btn btn-outline-secondary rounded-pill btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
            <button class="btn btn-primary rounded-pill btn-sm" onclick="location.reload();">
                <i class="fas fa-sync-alt me-1"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Selection Area -->
    <div class="glass-card">
        <div class="method-selector">
            <button type="button" class="method-btn active" id="btnScanner">
                <i class="fas fa-qrcode me-2"></i>Scan Tray
            </button>
            <button type="button" class="method-btn" id="btnManual">
                <i class="fas fa-list me-2"></i>Choose Order
            </button>
        </div>

        <!-- Scanner View -->
        <div id="scannerView" class="text-center">
          <div id="qrcodescannerdiv" class="scanner-wrapper">
            <div class="reader-container">
              <div id="reader"></div>
              <div class="scanner-overlay" id="scannerOverlay" style="display:none;">
                <div class="scan-region-highlight">
                  <div class="scan-line"></div>
                  <div class="scanner-corner corner-tl"></div>
                  <div class="scanner-corner corner-tr"></div>
                  <div class="scanner-corner corner-bl"></div>
                  <div class="scanner-corner corner-br"></div>
                </div>
                <div class="scan-success-flash" id="successFlash"></div>
              </div>
            </div>

            <!-- Sleek Camera Selection Dropdown -->
            <div id="cameraSelectDiv" class="mt-3 px-3 mx-auto" style="max-width: 320px; display: none;">
              <label class="filter-label text-start mb-1" style="font-size: 0.75rem; font-weight: 600; color: #64748b;">
                <i class="bi bi-camera-fill me-1 text-primary"></i> Choose Camera
              </label>
              <select id="cameraSelection" class="form-select border-primary-subtle shadow-sm" style="border-radius: 12px; height: 45px; font-size: 0.9rem; font-weight: 500;"></select>
            </div>

            <!-- Sleek Real-Time Scanner Status Message -->
            <div id="scannerStatusDiv" class="mt-3 text-center" style="display:none;">
              <span id="scannerStatus" class="badge bg-light text-secondary shadow-sm border py-2 px-3" style="border-radius: 20px; font-size: 0.85rem; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
                <i class="bi bi-broadcast text-danger pulse"></i><span>Scanner Active: Looking for QR Code...</span>
              </span>
            </div>

            <!-- Google Lens Mode Shutter Button -->
            <div id="captureScanBtnDiv" class="mt-3 px-3 mx-auto" style="max-width: 320px; display: none;">
              <button id="captureScanBtn" class="btn btn-success w-100 py-3 fw-bold shadow-sm" style="border-radius: 16px; border: none; background: linear-gradient(135deg, #10b981 0%, #059669 100%); font-size: 0.95rem; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                <i class="bi bi-camera-fill fs-5"></i>Capture & Scan (Google Lens Mode)
              </button>
            </div>

            <div class="mt-4 d-flex justify-content-center gap-2">
              <button id="startScan" class="btn btn-primary px-4 py-2" style="border-radius: 12px;">Start</button>
              <button id="stopScan" class="btn btn-danger px-4 py-2" style="display:none; border-radius: 12px;">Stop</button>
              <button id="toggleFlash" class="btn btn-warning px-4 py-2" style="border-radius: 12px;"><i class="bi bi-lightning-fill"></i></button>
              <button class="btn btn-outline-info px-4 py-2" onclick="window.location.reload();" style="border-radius: 12px;">
                <i class="fa fa-sync"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- Manual Selection View -->
        <div id="manualView" class="d-none">
            <label class="form-label">Select Production Stock Order No</label>
            <select id="stockOrderSelect" class="form-control input-premium w-100" style="width: 100%;">
                <option value="">Loading Orders...</option>
            </select>
        </div>
    </div>

    <!-- Details Summary Area -->
    <div id="detailsArea" class="glass-card d-none">
        <h5 class="fw-bold mb-3">📍 Production Stock Order Details</h5>
        <div class="info-summary" id="infoSummary">
            <!-- Dynamic Content -->
        </div>

        <hr class="my-4">

        <h5 class="fw-bold mb-3">🔄 Stage Movement</h5>
        <form id="movementForm">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Current Stage</label>
                    <input type="text" id="from_stage" class="form-control input-premium" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Move To Stage</label>
                    <select id="to_stage" name="to_stage" class="form-select input-premium" required>
                        <option value="">Select Stage</option>
                        <option data-stagefor="all" value="Germination">Germination</option>
                        <option data-stagefor="all" value="Polyhouse">Polyhouse</option>
                        <option data-stagefor="Grafted" value="Grafting">Grafting</option>
                        <option data-stagefor="Grafted" value="Hardening">Hardening</option>
                        <option data-stagefor="Grafted" value="2ndHardening">2nd Hardening</option>
                        <option data-stagefor="Grafted" value="ClipRemoval">Clip Removal</option>
                        <option data-stagefor="all" value="ReadytoSale">Ready to Sale</option>
                        <!-- <option data-stagefor="all" value="Rejected">Rejected</option> -->
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Move Date</label>
                    <input type="date" id="move_at" name="move_at" class="form-control input-premium" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Next Move Date</label>
                    <input type="date" id="next_move_date" name="next_move_date" class="form-control input-premium">
                </div>
                <!-- Location & Polyhouse Section -->
                <div class="col-md-6">
                    <label class="form-label">Location Move From</label>
                    <select id="locationSelect_from" class="form-select input-premium">
                        <option value="">Select Location</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Location Move To</label>
                    <select id="locationSelect" class="form-select input-premium">
                        <option value="">Select Location</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Polyhouse / Chamber</label>
                    <select id="polyhouseSelect" class="form-select input-premium">
                        <option value="">Select Location First</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Is Transport Required?</label>
                    <select id="is_transport" name="is_transport" class="form-select input-premium">
                        <option value="No">No</option>
                        <option value="Yes">Yes</option>
                    </select>
                </div>
                <div class="col-md-6 d-none">
                    <label class="form-label">Confirm Trays to Move</label>
                    <input type="text" id="trays_to_move" class="form-control input-premium" readonly value="0">
                </div>
            </div>
            
            <div class="mt-4 d-grid">
                <input type="hidden" id="active_qrcode">
                <input type="hidden" id="active_stockorder">
                <input type="hidden" id="active_roottype">
                <button type="button" class="btn btn-action btn-success shadow-lg" onclick="savePlantMove()">
                    <i class="fas fa-check-circle"></i> Confirm & Save Movement
                </button>
            </div>
        </form>
    </div>
</div>

<?php
include('../footer.php');
?>

<script>
    // Configuration from PHP/Context
    let routurl = $("body").data("routurl");
    let projectid = $("body").data("projectid");
    let loginid = $("body").data("loginid");

    let qrReader = null;
    let isScanning = false;
    let lastScannedCode = null;
    let scanLockTimeout = null;
    let autoScanInterval = null;
    let torchOn = false;
    let globalAudioCtx = null;
    let isCameraListLoaded = false;

    $(document).ready(function() {
        initScanner(null);
        loadProductionOrders();
        loadLocations();

        // Switch Methods
        $('#btnScanner').click(function() {
            $('.method-btn').removeClass('active');
            $(this).addClass('active');
            $('#scannerView').removeClass('d-none');
            $('#manualView').addClass('d-none');
            initScanner(null);
        });

        $('#btnManual').click(function() {
            $('.method-btn').removeClass('active');
            $(this).addClass('active');
            $('#manualView').removeClass('d-none');
            $('#scannerView').addClass('d-none');
            stopScanner();
        });

        // Manual selection change
        $('#stockOrderSelect').on('change', function() {
          const val = $(this).val();
          // 🔹 Get selected option
          const roottype = $(this).find(':selected').data('roottype');
          const producttype = $(this).find(':selected').data('producttype');
          filterStageOptions(producttype);
          if (val) {
              fetchMoveDetails('', val, roottype);
          }
      });

        function filterStageOptions(producttype) {
        $("#to_stage option").each(function () {
            const stageFor = $(this).data("stagefor");
            if (producttype !== "Grafted") {
                // 🔹 Show only "all"
                if (stageFor === "all" || stageFor === undefined) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            } else {
                // 🔹 Show all options
                $(this).show();
            }
        });
        // 🔹 Reset selection
        $("#to_stage").val("");
        }

      // Location change -> Load Polyhouses & Calculate Distance
      $('#locationSelect').on('change', function() {
          const locid = $(this).val();
          if(locid) {
              loadPolyhouses(locid);
              
              // Only auto-calc if transport is required AND we have source coordinates
              /*const from_opt = $('#locationSelect_from').find(':selected');
              if($('#is_transport').val() === 'Yes' && from_opt.val()) {
                  const lat1 = parseFloat(from_opt.data('lat'));
                  const lon1 = parseFloat(from_opt.data('lon'));
                  const lat2 = parseFloat($(this).find(':selected').data('lat'));
                  const lon2 = parseFloat($(this).find(':selected').data('lon'));

                  if(!isNaN(lat1) && !isNaN(lon1) && !isNaN(lat2) && !isNaN(lon2)) {
                      const distance = getDistanceInKm(lat1, lon1, lat2, lon2);
                      calculateFreightSuggestions(distance, projectid, routurl, '#freight_amount');
                  }
              }*/
          } else {
              $('#polyhouseSelect').html('<option value="">Select Location First</option>');
          }
      });

      // Transport requirement change
      /*$('#is_transport').on('change', function() {
          if($(this).val() === 'Yes') {
              $('#freight_div').removeClass('d-none');
              $('#transport_details').removeClass('d-none');
              // Calculate Freight using provided utility
              if(typeof calculateFreightSuggestions === 'function') {
                  // ... distance calc logic ...
              }
          } else {
              $('#freight_div').addClass('d-none');
              $('#transport_details').addClass('d-none');
              $('#freight_amount').val(0);
          }
      });*/

      // Simplified Transport Toggle (Reactive)
      $('#is_transport').on('change', function() {
          if($(this).val() === 'Yes') {
              // Trigger auto-distance if locations already selected
              if($('#locationSelect').val() && $('#locationSelect_from').val()) {
                  triggerFreightCalc();
              }
          }
      });

      function triggerFreightCalc() {
          const from_opt = $('#locationSelect_from').find(':selected');
          const to_opt = $('#locationSelect').find(':selected');
          
          if(from_opt.val() && to_opt.val()) {
              const lat1 = parseFloat(from_opt.data('lat'));
              const lon1 = parseFloat(from_opt.data('lon'));
              const lat2 = parseFloat(to_opt.data('lat'));
              const lon2 = parseFloat(to_opt.data('lon'));

              if(!isNaN(lat1) && !isNaN(lon1) && !isNaN(lat2) && !isNaN(lon2)) {
                  const distance = getDistanceInKm(lat1, lon1, lat2, lon2);
                  if(typeof calculateFreightSuggestions === 'function') {
                      calculateFreightSuggestions(distance, projectid, routurl, '#freight_amount');
                  }
              }
          }
      }

      // Re-trigger calc when "Move To" changes if transport is Yes
      $('#locationSelect').on('change', function() {
          if($('#is_transport').val() === 'Yes') {
             triggerFreightCalc();
          }
      });
    });

    // alert(getDistanceInKm('23.1223', '83.1994', '23.0488', '83.1903'));

    function initAudioContext() {
        try {
            if (!globalAudioCtx) {
                globalAudioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }
            if (globalAudioCtx && globalAudioCtx.state === 'suspended') {
                globalAudioCtx.resume();
            }
        } catch (e) { console.warn("Failed to initialize AudioContext:", e); }
    }

    $(document).on("click touchstart", function () {
        initAudioContext();
    });

    function playBeep() {
        try {
            initAudioContext();
            if (!globalAudioCtx) return;
            
            const oscillator = globalAudioCtx.createOscillator();
            const gainNode = globalAudioCtx.createGain();
            oscillator.connect(gainNode);
            gainNode.connect(globalAudioCtx.destination);
            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(880, globalAudioCtx.currentTime);
            gainNode.gain.setValueAtTime(0.1, globalAudioCtx.currentTime);
            oscillator.start();
            oscillator.stop(globalAudioCtx.currentTime + 0.12);
        } catch (e) { console.warn("Audio feedback failed:", e); }
    }

    function loadCameras(activeTrackLabel = null) {
        if (isCameraListLoaded) {
            if (activeTrackLabel) {
                Html5Qrcode.getCameras().then(cameras => {
                    const activeCam = cameras.find(c => c.label === activeTrackLabel);
                    if (activeCam) {
                        $("#cameraSelection").val(activeCam.id);
                    }
                }).catch(() => {});
            }
            return Promise.resolve();
        }

        return new Promise((resolve, reject) => {
            if (typeof Html5Qrcode === "undefined") {
                reject("Html5Qrcode not defined");
                return;
            }
            Html5Qrcode.getCameras().then(cameras => {
                if (cameras && cameras.length > 0) {
                    let options = '';
                    cameras.forEach((camera, index) => {
                        let label = camera.label || `Camera ${index + 1}`;
                        if (label.toLowerCase().includes('back') || label.toLowerCase().includes('environment') || label.toLowerCase().includes('rear')) {
                            label = `Rear Camera ${index + 1} 📷`;
                        } else if (label.toLowerCase().includes('front') || label.toLowerCase().includes('user')) {
                            label = `Front Camera ${index + 1} 🤳`;
                        }
                        options += `<option value="${camera.id}">${label}</option>`;
                    });
                    $("#cameraSelection").html(options);
                    isCameraListLoaded = true;
                    
                    if (cameras.length > 1) {
                        $("#cameraSelectDiv").slideDown();
                    }
                    
                    let activeCam = null;
                    if (activeTrackLabel) {
                        activeCam = cameras.find(c => c.label === activeTrackLabel);
                    }
                    
                    if (!activeCam) {
                        activeCam = cameras.find(c => {
                            const lbl = (c.label || '').toLowerCase();
                            return lbl.includes('back') || lbl.includes('environment') || lbl.includes('rear');
                        });
                    }
                    
                    if (!activeCam) {
                        activeCam = cameras.find(c => {
                            const lbl = (c.label || '').toLowerCase();
                            return !lbl.includes('front') && !lbl.includes('user');
                        });
                    }
                    
                    if (!activeCam && cameras.length > 0) {
                        activeCam = cameras[0];
                    }
                    
                    if (activeCam) {
                        $("#cameraSelection").val(activeCam.id);
                    }
                    resolve(cameras);
                } else {
                    resolve([]);
                }
            }).catch(err => {
                console.warn("Unable to query cameras:", err);
                resolve([]);
            });
        });
    }

    function initScanner(cameraId = null) {
        if (typeof Html5Qrcode === "undefined") return;
        if (isScanning) return;
        
        if (!qrReader) {
            qrReader = new Html5Qrcode("reader");
        }
        isScanning = true;

        let cameraTarget = { facingMode: "environment" };
        let videoConstraintsObj = {
            facingMode: "environment",
            width: { ideal: 1920 },
            height: { ideal: 1080 }
        };

        if (cameraId) {
            cameraTarget = cameraId;
            videoConstraintsObj = {
                deviceId: cameraId,
                width: { ideal: 1920 },
                height: { ideal: 1080 }
            };
        } else {
            const selectedCameraId = $("#cameraSelection").val();
            if (selectedCameraId) {
                cameraTarget = selectedCameraId;
                videoConstraintsObj = {
                    deviceId: selectedCameraId,
                    width: { ideal: 1920 },
                    height: { ideal: 1080 }
                };
            }
        }
        
        const config = { 
            fps: 15,
            formatsToSupport: [ 
                typeof Html5QrcodeSupportedFormats !== "undefined" 
                    ? Html5QrcodeSupportedFormats.QR_CODE 
                    : 0 
            ],
            experimentalFeatures: {
                useBarCodeDetectorIfSupported: true
            },
            videoConstraints: videoConstraintsObj
        };

        qrReader.start(
            cameraTarget,
            config,
            onScanSuccess,
            onScanFailure
        ).then(() => {
            $("#startScan").hide();
            $("#stopScan").show();
            $("#qrcodescannerdiv").addClass('scanner-active scanner-not-captured').removeClass('scanner-captured');
            $("#scannerOverlay").fadeIn();
            $("#scannerStatusDiv").slideDown();
            $("#captureScanBtnDiv").slideDown();
            $("#scannerStatus").html('<i class="bi bi-broadcast text-danger pulse"></i><span>Scanner Active: Looking for QR Code...</span>').removeClass('bg-success text-white bg-warning text-dark bg-danger text-white bg-info text-white').addClass('bg-light text-secondary');
            
            const readerWidth = $("#reader").width();
            const boxSize = Math.floor(readerWidth * 0.75);
            $(".scan-region-highlight").css({
                width: boxSize + "px",
                height: boxSize + "px"
            });

            let activeTrackLabel = null;
            try {
                const track = qrReader.getRunningTrack();
                if (track) {
                    activeTrackLabel = track.label;
                    const capabilities = track.getCapabilities ? track.getCapabilities() : {};
                    const constraints = { advanced: [] };
                    
                    let appliedAny = false;
                    if (capabilities.focusMode && capabilities.focusMode.includes("continuous")) {
                        constraints.advanced.push({ focusMode: "continuous" });
                        appliedAny = true;
                    }
                    
                    if (capabilities.zoom) {
                        let minZoom = capabilities.zoom.min || 1;
                        let maxZoom = capabilities.zoom.max || 1;
                        let idealZoom = Math.min(Math.max(1.8, minZoom), maxZoom);
                        constraints.advanced.push({ zoom: idealZoom });
                        appliedAny = true;
                    }
                    
                    if (appliedAny && track.applyConstraints) {
                        track.applyConstraints(constraints);
                    }
                }
            } catch (e) { console.warn("Failed to apply track capabilities:", e); }

            loadCameras(activeTrackLabel);
            startBackgroundNativeScan();

        }).catch(err => { 
            isScanning = false;
            console.error("Scanner Error:", err);
            
            if (cameraId) {
                console.log("Selected camera failed. Retrying with default environment camera...");
                initScanner(null);
            } else {
                Swal.fire('Scanner Error', 'Could not access high-quality camera.', 'error');
            }
        });
    }

    window.stopScanner = function () {
        if (autoScanInterval) {
            clearInterval(autoScanInterval);
            autoScanInterval = null;
        }
        if (qrReader && isScanning) {
            return qrReader.stop().then(() => {
                isScanning = false;
                $("#startScan").show();
                $("#stopScan").hide();
                $("#qrcodescannerdiv").removeClass('scanner-active scanner-not-captured scanner-captured');
                $("#scannerOverlay").hide();
                $("#scannerStatusDiv").slideUp();
                $("#captureScanBtnDiv").slideUp();
            }).catch((err) => { 
                console.error("Stop failed:", err);
                isScanning = false;
            });
        }
        return Promise.resolve();
    };

    function onScanSuccess(decodedText) {
        if (decodedText === lastScannedCode) return;
        lastScannedCode = decodedText;
        clearTimeout(scanLockTimeout);
        scanLockTimeout = setTimeout(() => { lastScannedCode = null; }, 2000);

        $("#scannerStatus")
            .html('<i class="bi bi-qr-code text-white"></i><span>QR Code Read: ' + decodedText + '</span>')
            .removeClass('bg-light text-secondary bg-warning text-dark bg-danger text-white bg-info text-white')
            .addClass('bg-success text-white');

        $("#qrcodescannerdiv").removeClass('scanner-not-captured').addClass('scanner-captured');

        playBeep();
        $("#successFlash").show().fadeOut(400);
        
        if (navigator.vibrate) navigator.vibrate(100);

        stopScanner().then(() => {
            fetchMoveDetails(decodedText, '', '');
        });
    }

    function onScanFailure(error) { }

    $(document).ready(function() {
        $("#toggleFlash").off("click").on("click", async function () {
            if (!qrReader || !isScanning) return;
            try {
                const track = qrReader.getRunningTrack();
                if (track) {
                    torchOn = !torchOn;
                    await track.applyConstraints({ advanced: [{ torch: torchOn }] });
                }
            } catch (err) { }
        });

        $("#startScan").off("click").on("click", function() {
            initScanner(null);
        });
        
        $("#stopScan").off("click").on("click", stopScanner);

        $("#captureScanBtn").off("click").on("click", function () {
            window.captureAndScanFrame();
        });

        $("#reader").off("click").on("click", function () {
            if (qrReader && isScanning) {
                try {
                    const track = qrReader.getRunningTrack();
                    if (track && track.applyConstraints) {
                        track.applyConstraints({
                            advanced: [{ focusMode: "continuous" }]
                        });
                    }
                } catch (e) { console.warn("Autofocus constraint not supported on this lens", e); }

                setTimeout(() => {
                    if (isScanning) {
                        window.captureAndScanFrame();
                    }
                }, 350);
            }
        });

        $("#cameraSelection").off("change").on("change", function () {
            const selectedId = $(this).val();
            if (isScanning && selectedId) {
                stopScanner().then(() => {
                    initScanner(selectedId);
                });
            } else if (selectedId) {
                initScanner(selectedId);
            }
        });
    });

    window.captureAndScanFrame = function () {
        if (!qrReader || !isScanning) return;

        try {
            const video = document.querySelector("#reader video");
            if (!video) return;

            $("#scannerStatus")
                .html('<i class="fa fa-spinner fa-spin text-white"></i><span>Analyzing Captured Frame...</span>')
                .removeClass('bg-light text-secondary bg-warning text-dark bg-danger text-white bg-info text-white')
                .addClass('bg-primary text-white');

            const canvas = document.createElement("canvas");
            canvas.width = video.videoWidth || 1920;
            canvas.height = video.videoHeight || 1080;
            const ctx = canvas.getContext("2d");
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            if (typeof BarcodeDetector !== "undefined") {
                const detector = new BarcodeDetector({ formats: ['qr_code'] });
                detector.detect(canvas)
                    .then(barcodes => {
                        if (barcodes && barcodes.length > 0) {
                            const decodedText = barcodes[0].rawValue;
                            onScanSuccess(decodedText);
                        } else {
                            runScanFileFallback(canvas);
                        }
                    })
                    .catch(err => {
                        runScanFileFallback(canvas);
                    });
            } else {
                runScanFileFallback(canvas);
            }
        } catch (e) {
            console.error("Capture scan failed:", e);
        }
    };

    function runScanFileFallback(canvas) {
        let targetWidth = canvas.width;
        let targetHeight = canvas.height;
        if (targetWidth > 960) {
            const ratio = 960 / targetWidth;
            targetWidth = 960;
            targetHeight = Math.floor(targetHeight * ratio);
        }

        const downscaleCanvas = document.createElement("canvas");
        downscaleCanvas.width = targetWidth;
        downscaleCanvas.height = targetHeight;
        const downscaleCtx = downscaleCanvas.getContext("2d");
        downscaleCtx.drawImage(canvas, 0, 0, targetWidth, targetHeight);

        downscaleCanvas.toBlob((blob) => {
            if (!blob) {
                handleScanError();
                return;
            }
            const file = new File([blob], "captured-frame.png", { type: "image/png" });

            qrReader.scanFile(file, false)
                .then(decodedText => {
                    onScanSuccess(decodedText);
                })
                .catch(err => {
                    handleScanError();
                });
        }, "image/png");
    }

    function handleScanError() {
        Swal.fire({
            icon: 'warning',
            title: 'Not Detected',
            text: 'Google Lens scan failed. Move closer, adjust lighting and try again.',
            timer: 2000,
            showConfirmButton: false
        });
        $("#scannerStatus")
            .html('<i class="bi bi-broadcast text-danger pulse"></i><span>Scanner Active: Looking for QR Code...</span>')
            .removeClass('bg-primary text-white')
            .addClass('bg-light text-secondary');
    }

    function startBackgroundNativeScan() {
        if (autoScanInterval) {
            clearInterval(autoScanInterval);
            autoScanInterval = null;
        }

        if (typeof BarcodeDetector === "undefined") return;

        let retries = 0;
        const findVideoAndStart = setInterval(() => {
            const video = document.querySelector("#reader video");
            if (video) {
                clearInterval(findVideoAndStart);
                const detector = new BarcodeDetector({ formats: ['qr_code'] });
                autoScanInterval = setInterval(() => {
                    if (isScanning && !lastScannedCode) {
                        detector.detect(video)
                            .then(barcodes => {
                                if (barcodes && barcodes.length > 0 && isScanning && !lastScannedCode) {
                                    onScanSuccess(barcodes[0].rawValue);
                                }
                            })
                            .catch(err => {});
                    }
                }, 250);
            } else {
                retries++;
                if (retries > 20) {
                    clearInterval(findVideoAndStart);
                }
            }
        }, 200);
    }

    function loadLocations() {
        $.ajax({
            url: routurl + 'krf-orderandsales/modal/product_modal.php?action=GetProductionLocationList',
            type: 'POST',
            data: { 
                projectid: projectid,
                companyid: $("body").data("companyid"),
                subcompanyid: $("body").data("subcompanyid")
            },
            dataType: 'JSON',
            success: function(res) {
              // console.log(res);
                let html = '<option value="">Select Location</option>';
                if(res && res.length) {
                    res.forEach(item => {
                        const opt = `<option 
                            data-lat="${item.OfficeLat || 0}" 
                            data-lon="${item.OfficeLng || 0}" 
                            value="${item.LocationId}">${item.LocationName}</option>`;
                        html += opt;
                    });
                }
                $('#locationSelect').html(html);
                $('#locationSelect_from').html(html);
            }
        });
    }

    function loadPolyhouses(locationid) {
        $.ajax({
            url: routurl + 'krf-orderandsales/modal/polihouse_modal.php?action=GetPolihouseDetails',
            type: 'POST',
            data: { 
                projectid: projectid,
                shortby: 'locationid',
                shortbyvalue: locationid
            },
            dataType: 'JSON',
            success: function(res) {
                let html = '<option value="">Select Polyhouse</option>';
                if(res && res.length) {
                    res.forEach(item => {
                        html += `<option value="${item.polyhouseid}">${item.polyhousename}</option>`;
                    });
                }
                $('#polyhouseSelect').html(html);
            }
        });
    }

    function loadProductionOrders() {
        $.ajax({
            url: routurl + 'krf-orderandsales/modal/polihouse_modal.php?action=GetProductionOrdersForMovement',
            type: 'POST',
            data: { projectid: projectid},
            dataType: 'JSON',
            success: function(res) {
              // console.log(res);
                let html = '<option value="">-- Choose Order --</option>';
                if(res && res.length) {
                    res.forEach(item => {
                        html += `<option data-roottype="${item.roottype}" data-producttype="${item.producttype}" value="${item.stockorder_no}">${item.stockorder_no} - ${item.roottype} - (${item.productname})</option>`;
                    });
                }
                $('#stockOrderSelect').html(html);
                $('#stockOrderSelect').select2({
                    placeholder: "Search Order No..."
                });
            }
        });
    }

    function fetchMoveDetails(qr, sono, roottype) {
        Swal.fire({
            title: 'Fetching Data...',
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url: routurl + 'krf-orderandsales/modal/polihouse_modal.php?action=GetMovementDetails',
            type: 'POST',
            data: { 
                projectid: projectid,
                qrcode: qr,
                stockorder_no: sono,
                roottype: roottype
            },
            dataType: 'JSON',
            success: function(res) {
                Swal.close();
                if(res.status === 'success') {
                    displayDetails(res.data);
                } else {
                    Swal.fire('Notice', res.message, 'info');
                }
            },
            error: function() {
                Swal.fire('Error', 'Network error while fetching data', 'error');
            }
        });
    }

    function displayDetails(data) {
        $('#active_qrcode').val(data.barcode || '');
        $('#active_stockorder').val(data.stockorder_no || '');
        
        let html = `
            <div class="row">
                <div class="col-md-6">
                    <div class="info-item">
                        <span class="info-label">Product & Order No</span>
                        <span class="info-value text-primary">${data.productname} (${data.stockorder_no})</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-item">
                        <span class="info-label">Order Qty</span>
                        <span class="info-value">${data.total_qty || 0} Plants</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-item">
                        <span class="info-label">Delivery Date</span>
                        <span class="info-value text-danger">${data.expected_delivery_date || 'N/A'}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-item">
                        <span class="info-label">Client Name</span>
                        <span class="info-value">${data.clientname || 'N/A'}</span>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="info-item">
                        <span class="info-label">Rootype</span>
                        <span class="info-value text-success">${data.roottype || 'N/A'}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-item">
                        <span class="info-label">Current Stage</span>
                        <span class="info-value badge bg-info text-dark px-3 rounded-pill">${data.workingstatus || 'Initial'}</span>
                    </div>
                </div>
            </div>
        `;

        // Grafted Logic
        if (data.product_type === 'Grafted' && data.component_details) {
            html += `
                <hr class="my-4">
                <div class="row g-4">
                    <!-- Root Tray Details -->
                    <div class="col-md-6">
                        <div class="p-3 border rounded-4 bg-white h-100">
                            <h6 class="fw-bold text-success mb-3"><i class="fas fa-leaf me-2"></i>Root Tray Details</h6>
                            ${renderComponentDetails(data.component_details.Root)}
                        </div>
                    </div>
                    <!-- Scion Tray Details -->
                    <div class="col-md-6">
                        <div class="p-3 border rounded-4 bg-white h-100">
                            <h6 class="fw-bold text-primary mb-3"><i class="fas fa-seedling me-2"></i>Scion Tray Details</h6>
                            ${renderComponentDetails(data.component_details.Scion)}
                        </div>
                    </div>
                </div>
            `;
        }
        
        

        $('#infoSummary').html(html);
        $('#from_stage').val(data.workingstatus || '');
        $('#trays_to_move').val(data.total_trays || 0);

        // Store active keys for submission
        $('#active_qrcode').val(data.barcode || '');
        $('#active_stockorder').val(data.stockorder_no || '');
        $('#active_roottype').val(data.roottype || '');
        
        // Auto-select "Location From"
        if(data.locationid) {
            $('#locationSelect_from').val(data.locationid).trigger('change');
        }

        $('#detailsArea').removeClass('d-none');
        
        // Scroll to form
        $('html, body').animate({
            scrollTop: $("#detailsArea").offset().top - 20
        }, 500);
    }

    function renderComponentDetails(details) {
        if (!details || details.length === 0) return '<p class="text-muted small">No components found.</p>';
        let list = '';
        details.forEach(box => {
            list += `
                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                    <div>
                        <div class="fw-bold">${box.bed_no}</div>
                        <small class="text-muted">${box.tray_position}</small>
                    </div>
                    <div class="badge bg-light text-dark border">
                        ${box.total_qty} plants (${box.tray_count} trays)
                    </div>
                </div>
            `;
        });
        return list;
    }

    function savePlantMove() {
        const to_stage = $('#to_stage').val();
        if(!to_stage) {
            Swal.fire('Wait', 'Select a target movement area', 'warning');
            return;
        }

        const payload = {
            projectid: projectid,
            loginid: loginid,
            qrcode: $('#active_qrcode').val(),
            stockorder_no: $('#active_stockorder').val(),
            roottype: $('#active_roottype').val(),
            to_stage: to_stage,
            move_date: $('#move_at').val(),
            next_move_date: $('#next_move_date').val(),
            polyhouseid: $('#polyhouseSelect').val(),
            is_transport: $('#is_transport').val(),
            from_location_id: $('#locationSelect_from').val(),
            from_location_name: $('#locationSelect_from').find(':selected').text(),
            from_lat: $('#locationSelect_from').find(':selected').data('lat'),
            from_lon: $('#locationSelect_from').find(':selected').data('lon'),            
            to_locationid: $('#locationSelect').val(),
            to_location_name: $('#locationSelect').find(':selected').text(),
            to_lat: $('#locationSelect').find(':selected').data('lat'),
            to_lon: $('#locationSelect').find(':selected').data('lon')
        };

        Swal.fire({
            title: 'Updating Stage...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url: routurl + 'krf-orderandsales/modal/polihouse_modal.php?action=SavePlantMovement',
            type: 'POST',
            data: payload,
            dataType: 'JSON',
            success: function(res) {
                if(res.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Move Confirmed',
                        text: 'Plant stage and status updated successfully.',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Update Failed', res.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Communication failure with server', 'error');
            }
        });
    }
</script>
