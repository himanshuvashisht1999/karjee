<?php
    include('../header.php');
    date_default_timezone_set("Asia/Calcutta");
    $tripid = $_GET['tripid'] ?? '';
    $loc = $_GET['loc'] ?? '';
 
?>

<!-- Google Fonts: Outfit -->
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome & Bootstrap Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

<!-- ✅ QR Code Scanner Library -->
<script src="https://unpkg.com/html5-qrcode@2.3.7/html5-qrcode.min.js"></script>

<style>
    :root {
        --dispatch-primary: #4f46e5;
        --dispatch-secondary: #7c3aed;
        --glass-bg: rgba(255, 255, 255, 0.9);
    }

    .container-assign {
        padding: 24px;
        background: #f8fafc;
        min-height: calc(100vh - 120px);
    }

    .glass-card {
        background: var(--glass-bg);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.5);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    .form-label {
        font-weight: 600;
        color: #475569;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }

    .form-control, .form-select {
        border-radius: 12px;
        padding: 12px 16px;
        border: 1px solid #e2e8f0;
        transition: all 0.2s;
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--dispatch-primary);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }

    .pso-badge {
        background: #e0f2fe;
        color: #0369a1;
        padding: 4px 12px;
        border-radius: 50px;
        font-weight: 700;
        font-size: 0.9rem;
    }

    .btn-save {
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 12px 30px;
        font-weight: 600;
        transition: all 0.2s;
        box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.4);
    }

    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.5);
        color: white;
    }

    .btn-save:disabled {
        background: #cbd5e1 !important;
        color: #94a3b8 !important;
        box-shadow: none !important;
        cursor: not-allowed;
        transform: none !important;
        pointer-events: none;
    }

    .item-row {
        background: white;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 10px;
        border: 1px solid #f1f5f9;
        transition: border-color 0.2s;
    }

    .item-row:hover {
        border-color: var(--dispatch-primary);
    }

    .type-badge {
        font-size: 0.7rem;
        text-transform: uppercase;
        padding: 2px 8px;
        border-radius: 4px;
        margin-left: 10px;
    }

    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: white;
        color: #475569;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 8px 16px;
        font-weight: 600;
        font-size: 0.875rem;
        transition: all 0.2s;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        text-decoration: none;
    }

    .btn-back:hover {
        background: #f8fafc;
        color: var(--dispatch-primary);
        border-color: #cbd5e1;
        text-decoration: none;
        transform: translateX(-2px);
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
        border-color: var(--primary-blue);
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
        background: linear-gradient(to right, transparent, var(--primary-blue), transparent);
        box-shadow: 0 0 15px var(--primary-blue);
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
        border: 4px solid var(--primary-blue);
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
</style>

<div class="container-assign"> 
         

        <div class="row g-4">
            <!-- Resource Selection -->
            <div class="col-lg-5">
                <div class="glass-card p-4">
                   <!--  <h5 class="fw-bold mb-4 text-slate-800"><i class="fa fa-truck me-2 text-primary"></i>Resource Details</h5> --> 

                    <h3 class="fw-bold mb-4 text-slate-800">
                        <i class="fa fa-truck me-2 text-primary"></i>
                        Total Trays:
                        <span class="trip_totaltray">0</span>
                    </h3>

                     <h3 class="fw-bold mb-4 text-slate-800">
                        <i class="fa fa-truck me-2 text-primary"></i>
                        Total Plants:
                        <span class="trip_totalplants">0</span>
                    </h3>
                                        
                    <!-- Where From (Source Location) -->
                    <div class="mb-3">
                        <label class="form-label">Source (Location)</label>
                        <select class="form-select form-control" name="from_location_id" id="from_location_id" required>
                            <option value="">-- Choose Source --</option>
                              
                        </select>
                    </div>

                    <!-- Where to Move (Destination Location) --> 
                    <div class="mb-3">
                        <label class="form-label">Destination (Location)</label>
                        <select class="form-select form-control" name="to_location_id" id="to_location_id" required>
                            <option value="">-- Choose Destination --</option>
                            
                        </select>
                    </div> 

                   <div class="mb-4">
                        <label class="form-label">Dispatch Date &amp; Time</label>

                        <input type="datetime-local"  class="form-control" name="dispatch_time" id = "dispatch_time" 
                            required
                        >
                    </div>                
                    
 
                     <div class="mb-3">
                        <label class="form-label">Select Vehicle</label>
                        <select class="form-select form-control" name="vehicle_id" id="vehicle_id" required>
                            <option value="">-- Choose Vehicle --</option>
 
                        </select>
                    </div>  
 

                    <!-- Select Driver -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label mb-0">Select Driver</label>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-link p-0 text-primary" id="btn_add_driver" title="Add New Driver" style="text-decoration: none; font-size: 0.8rem; font-weight: 600;">
                                    <i class="fa fa-plus"></i> Add Driver
                                </button> 
                            </div>
                        </div>
                        <select class="form-select form-control" name="driver_id" id="driver_id" required>
                            <option value="">-- Choose Driver --</option> 
                        </select>
                             
                    </div>                  

                    <div class="mb-3">
                        <label class="form-label">Purpose to Move</label>
                        <select class="form-select form-control" name="purpose_to_move" id="purpose_to_move" required>
                            <option value="">-- Choose Purpose --</option>
                            
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Dispatch Remarks</label>
                        <textarea class="form-control" name="remarks" rows="2" placeholder="Enter dispatch remarks..."> </textarea>
                    </div>
 
 

                    <div class="text-center pt-3">
                        <button type="submit" id="finalsave_loading" class="btn btn-save w-100">
                            <i class="fa fa-check-circle me-2"></i>Generate Trip & Continue
                        </button>
                    </div>
                </div>
            </div>

            <!-- Item Selection / Verification -->
            <div class="col-lg-7">


                <div class="row g-4">
                    <div class="mb-1">
                        <label class="form-label fw-bold d-none">Destination Location</label>
                        <select class="form-select form-control" name="despatch_location_id" id="despatch_location_id" >
                            <option value="">-- Choose Source Location --</option>
                            
                        </select>
                    </div> 

                    <div class="mb-1">
                        <label class="form-label fw-bold">Polyhouse</label>
                        <select class="form-select form-control" name="loc_polyhouseid" id="loc_polyhouseid" >
                            <option value="">-- Choose Source polyhouse --</option>
                            
                        </select>
                    </div> 
                <!-- Scanner Section -->
                <div class="col-lg-12">
                    <div class="content-card h-100">
                      <!--   <div class="card-title-premium">
                            <i class="bi bi-qr-code-scan text-primary"></i>
                            <?php echo _t('box_verification'); ?>
                        </div> -->

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
                                <label class="filter-label text-start mb-1" style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted);">
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
                                <button id="startScan" class="btn btn-primary px-4 py-2"
                                    style="border-radius: 12px;"><?php echo _t('start'); ?></button>
                                <button id="stopScan" class="btn btn-danger px-4 py-2"
                                    style="display:none; border-radius: 12px;"><?php echo _t('stop'); ?></button>
                                <button id="toggleFlash" class="btn btn-warning px-4 py-2" style="border-radius: 12px;"><i
                                        class="bi bi-lightning-fill"></i></button>
                                <button id="ManualStartScan" class="btn btn-outline-secondary px-4 py-2"
                                    style="border-radius: 12px;"><?php echo _t('manual'); ?></button>
                                <button class="btn btn-outline-info px-4 py-2" onclick="window.location.reload();" style="border-radius: 12px;">
                                    <i class="fa fa-sync"></i>
                                </button>
                            </div>
                        </div>

                        <div id="manualQrCodeSearchDiv" style="display:none;">
                            <div class="input-group mb-3">
                                <span class="input-group-text bg-white" style="border-radius: 12px 0 0 12px;"><i
                                        class="bi bi-qr-code"></i></span>
                                <input type="text" id="manualQrCode" autocomplete="off" class="form-control"
                                    style="border-radius: 0 12px 12px 0; height: 50px;" placeholder="Scan or type QR code...">
                            </div>
                            <div class="d-flex gap-2">
                                <button id="fetchManualgbox" class="btn btn-primary w-100 py-3 fw-700"
                                    style="border-radius: 12px;"><?php echo _t('verify'); ?></button>
                                <button class="btn btn-outline-primary px-3" id="QRcodeStartScan"
                                    style="border-radius: 12px;"><i class="bi bi-camera"></i></button>
                            </div>
                        </div>

                        <div id="noResult" class="alert alert-danger mt-3 text-center"
                            style="display:none; border-radius:12px;">
                            <i class="bi bi-exclamation-triangle me-2"></i> <?php echo _t('no_matching_box'); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="barcodeDetailsModal"  tabindex="-1"
                    role="dialog"  aria-labelledby="barcodeDetailsModalLabel"  aria-hidden="true"  >
                    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                        <div class="modal-content">

                            <div class="modal-header">
                                <h5 class="modal-title" id="barcodeDetailsModalLabel">
                                    <i class="fa fa-qrcode me-2"></i>
                                    Scanned Barcode Details
                                </h5>

                                <button
                                    type="button"
                                    class="close btn"
                                    data-dismiss="modal"
                                    aria-label="Close"
                                >
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>

                            <div class="modal-body">
                                <input
                                    type="hidden"
                                    id="active_barcode_item_key"
                                    value=""
                                >

                                <div id="barcode_item_summary" class="mb-3"></div>

                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th style="width: 60px;">S.No.</th>
                                                <th>Barcode</th>
                                                <th style="width: 140px;">Plants</th>
                                                <th
                                                    class="text-center"
                                                    style="width: 100px;"
                                                >
                                                    Action
                                                </th>
                                            </tr>
                                        </thead>

                                        <tbody id="barcode_details_body"></tbody>
                                    </table>
                                </div>

                                <div
                                    id="no_barcode_message"
                                    class="alert alert-warning text-center mb-0"
                                    style="display: none;"
                                >
                                    No scanned barcodes are available.
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button
                                    type="button"
                                    class="btn btn-secondary"
                                    data-dismiss="modal"
                                >
                                    Close
                                </button>
                            </div>

                        </div>
                    </div>
                </div>
                <div class="glass-card p-4">
                    <h5 class="fw-bold mb-4 text-slate-800"><i class="fa fa-boxes me-2 text-primary"></i>Product Details</h5>
                                       
                    <div id="items_container"> 
                             <!-- Summary Total Loading Row -->
                             <div class="item-row summary-row bg-slate-50 border-top mt-3 pt-3 fw-bold" style="border-radius: 0 0 12px 12px; border-top: 2px dashed #cbd5e1 !important;">
                                 <div class="row align-items-center">
                                     <div class="col-md-5 text-end text-sm-start">
                                         <span class="text-uppercase small text-slate-500 fw-bold me-2">Total Loading Summary:</span>
                                     </div>
                                     <div class="col-md-3 col-sm-6">
                                         <div class="small text-slate-400 d-none">Total Target Available</div>
                                         <div class="fw-semibold text-slate-700"></div>
                                         <div class="fw-semibold text-indigo-600"> </div>
                                     </div>
                                     <div class="col-md-2 col-sm-3">
                                         <div class="small text-slate-400">Total Loading Trays</div>
                                         <span id="total_loading_trays" class="text-indigo-600 h5 mb-0 fw-bold">0</span> <span class="small text-slate-400">Trays</span>
                                     </div>
                                     <div class="col-md-2 col-sm-3">
                                         <div class="small text-slate-400">Total Loading Plants</div>
                                         <span id="total_loading_plants" class="text-indigo-600 h5 mb-0 fw-bold">0</span> <span class="small text-slate-400">Plants</span>
                                     </div>
                                 </div>
                             </div>
                              
                         
                    </div>
                </div>
            </div>
        </div>
      <!-- </form> -->
 </div>

 

<?php include('../footer.php'); ?>

<script>
    let qrReader = null;
    let isScanning = false;
    let torchOn = false;
    let scannedBoxes = [];
    let lastScannedCode = null;
    let scanLockTimeout = null;
    let activeBedNo = null;
    let activePhId = null;
    let isCameraListLoaded = false;
    let autoScanInterval = null;

    let scannedBarcodes = [];
    let scannedLoadingItems = {};
     let vehicleCapacityTraysGlobal =null;
     let vehicleCapacityPlantsGlobal =null;


$(document).ready(function () { 
    alert("hi");
    var exist_tripid = "<?php echo $tripid; ?>"; 
    var exist_loc = "<?php echo $loc; ?>";  
    debugger;
    loadAssignmentMastersReceive(exist_tripid,exist_loc);
    $("#fetchManualgbox").on("click", function () {
        const qrValue = $("#manualQrCode").val();
        if (qrValue) fetchloadingtray(qrValue);
    });

    $("#manualQrCode").on("keypress", function (e) {
        if (e.which === 13) fetchloadingtray($(this).val());
    });

    $("#startScan").on("click", initScanner);
    $("#stopScan").on("click", stopScanner);
    $("#ManualStartScan").on("click", function () {
        stopScanner();
        $("#qrcodescannerdiv").hide();
        $("#manualQrCodeSearchDiv").show().find("#manualQrCode").focus();
    });
    $("#QRcodeStartScan").on("click", function () {
        initScanner();
        $("#qrcodescannerdiv").show();
        $("#manualQrCodeSearchDiv").hide();
    });

    $("#toggleFlash").on("click", async function () {
        if (!qrReader || !isScanning) return;
        try {
            const track = qrReader.getRunningTrack();
            if (track) {
                torchOn = !torchOn;
                await track.applyConstraints({ advanced: [{ torch: torchOn }] });
            }
        } catch (err) { }
    });
    $("#captureScanBtn").off("click").on("click", function () {
        window.captureAndScanFrame();
    });


     window.captureAndScanFrame = function () {
            if (!qrReader || !isScanning) return;

            try {
                const video = document.querySelector("#reader video");
                if (!video) {
                    iziToast.error({ title: 'Error', message: 'Camera video stream not found.', position: 'topRight' });
                    return;
                }

                // Show analyzing state on status badge
                $("#scannerStatus")
                    .html('<i class="fa fa-spinner fa-spin text-white"></i><span>Analyzing Captured Frame...</span>')
                    .removeClass('bg-light text-secondary bg-warning text-dark bg-danger text-white bg-info text-white')
                    .addClass('bg-primary text-white');

                // Create offscreen canvas at native video stream resolution (1920x1080 extremely high quality!)
                const canvas = document.createElement("canvas");
                canvas.width = video.videoWidth || 1920;
                canvas.height = video.videoHeight || 1080;
                const ctx = canvas.getContext("2d");
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                // 1. Try native, hardware-accelerated BarcodeDetector (sub-millisecond instant client-side decode!)
                if (typeof BarcodeDetector !== "undefined") {
                    const detector = new BarcodeDetector({ formats: ['qr_code'] });
                    detector.detect(canvas)
                        .then(barcodes => {
                            if (barcodes && barcodes.length > 0) {
                                const decodedText = barcodes[0].rawValue;
                                console.log("Native BarcodeDetector success:", decodedText);
                                onScanSuccess(decodedText);
                            } else {
                                // Fallback to Html5Qrcode.scanFile
                                runScanFileFallback(canvas);
                            }
                        })
                        .catch(err => {
                            console.warn("Native BarcodeDetector failed, retrying with fallback:", err);
                            runScanFileFallback(canvas);
                        });
                } else {
                    // Fallback to Html5Qrcode.scanFile
                    runScanFileFallback(canvas);
                }

            } catch (e) {
                console.error("Capture scan failed:", e);
                iziToast.error({ title: 'Error', message: 'Photo capture failed.', position: 'topRight' });
            }
        };
        $("#captureScanBtn").off("click").on("click", function () {
            window.captureAndScanFrame();
        });

        // Premium Tap-to-Focus and Auto-Capture trigger on scanner container
        $("#reader").off("click").on("click", function () {
            if (qrReader && isScanning) {
                try {
                    const track = qrReader.getRunningTrack();
                    if (track && track.applyConstraints) {
                        track.applyConstraints({
                            advanced: [{ focusMode: "continuous" }]
                        });
                        // Visual feedback badge toast
                        iziToast.info({ 
                            title: 'Refocusing', 
                            message: 'Refocusing camera & capturing frame...', 
                            timeout: 800, 
                            position: 'bottomCenter',
                            animateInside: false,
                            transitionIn: 'fadeInUp',
                            transitionOut: 'fadeOutDown'
                        });
                    }
                } catch (e) { console.warn("Autofocus constraint not supported on this lens", e); }

                // Wait 350ms for lens to physically settle focus, then execute high-res capture scan!
                setTimeout(() => {
                    if (isScanning) {
                        window.captureAndScanFrame();
                    }
                }, 350);
            }
        });

        // Restart scanner on camera selection change
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

        // ✅ Default: Open environment back camera first on load
        // This prompts for camera permissions. Once granted, loadCameras() runs inside the .then() of start()
        initScanner(null);

});
function escapeHtml(value) {
    return $('<div>')
        .text(value == null ? '' : String(value))
        .html();
}


   
//scanner code
    let globalAudioCtx = null;
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

        // Tap/Click anywhere on the document to proactively unlock AudioContext (autoplay work-around)
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
                oscillator.stop(globalAudioCtx.currentTime + 0.12); // Clear, pleasant beep (120ms)
            } catch (e) { console.warn("Audio feedback failed:", e); }
        }

        /* --- Scanner Operations --- */
        // Load available cameras for multi-lens support (called post-permission grant)
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
                            // Simplify and clean labels for sleek UI
                            if (label.toLowerCase().includes('back') || label.toLowerCase().includes('environment') || label.toLowerCase().includes('rear')) {
                                label = `Rear Camera ${index + 1} 📷`;
                            } else if (label.toLowerCase().includes('front') || label.toLowerCase().includes('user')) {
                                label = `Front Camera ${index + 1} 🤳`;
                            }
                            options += `<option value="${camera.id}">${label}</option>`;
                        });
                        $("#cameraSelection").html(options);
                        isCameraListLoaded = true;
                        
                        // Show dropdown if multiple cameras exist
                        if (cameras.length > 1) {
                            $("#cameraSelectDiv").slideDown();
                        }
                        
                        // Try to match dropdown with the currently running track label
                        let activeCam = null;
                        if (activeTrackLabel) {
                            activeCam = cameras.find(c => c.label === activeTrackLabel);
                        }
                        
                        // Fallback: Smart selection of back/rear/environment camera
                        if (!activeCam) {
                            activeCam = cameras.find(c => {
                                const lbl = (c.label || '').toLowerCase();
                                return lbl.includes('back') || lbl.includes('environment') || lbl.includes('rear');
                            });
                        }
                        
                        // Heuristic fallback: pick first camera that is NOT front
                        if (!activeCam) {
                            activeCam = cameras.find(c => {
                                const lbl = (c.label || '').toLowerCase();
                                return !lbl.includes('front') && !lbl.includes('user');
                            });
                        }
                        
                        // Ultimate fallback: first camera
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
            
            // Instantiation once or re-use to prevent orphaned camera streams
            if (!qrReader) {
                qrReader = new Html5Qrcode("reader");
            }
            isScanning = true;

            // Determine active target camera details dynamically
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
            
            // Highly optimized config to prevent distortion and maximize scan rate on mobile
            const config = { 
                fps: 15, // 15 FPS is smooth and significantly reduces CPU/battery strain
                // Omitted qrbox constraint to enable full-frame scanning (like Google Lens!)
                // Do NOT force aspectRatio: 1.0 on video stream constraint to prevent stretching/distortion
                formatsToSupport: [ 
                    typeof Html5QrcodeSupportedFormats !== "undefined" 
                        ? Html5QrcodeSupportedFormats.QR_CODE 
                        : 0 
                ],
                experimentalFeatures: {
                    useBarCodeDetectorIfSupported: true // Extremely fast, hardware-accelerated scanning (like Google Lens)
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
                
                // Adjust overlay UI to match calculated qrbox
                const readerWidth = $("#reader").width();
                const boxSize = Math.floor(readerWidth * 0.75);
                $(".scan-region-highlight").css({
                    width: boxSize + "px",
                    height: boxSize + "px"
                });

                // Retrieve active track label to match in dropdown, and apply focus/zoom constraints
                let activeTrackLabel = null;
                try {
                    const track = qrReader.getRunningTrack();
                    if (track) {
                        activeTrackLabel = track.label;
                        
                        // Apply focus mode and zoom constraints post-start if supported
                        const capabilities = track.getCapabilities ? track.getCapabilities() : {};
                        const constraints = { advanced: [] };
                        
                        let appliedAny = false;
                        if (capabilities.focusMode && capabilities.focusMode.includes("continuous")) {
                            constraints.advanced.push({ focusMode: "continuous" });
                            appliedAny = true;
                        }
                        
                        // Auto-apply a slight zoom (e.g. 1.8x) if supported, so user doesn't have to hold camera too close to tiny stickers!
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

                // Fetch cameras post-permission grant and match active camera
                loadCameras(activeTrackLabel);

                // Start automatic background hardware-accelerated scanning loop
                startBackgroundNativeScan();

            }).catch(err => { 
                isScanning = false;
                console.error("Scanner Error:", err);
                
                // Fallback to default environment camera if specific camera id selection failed
                if (cameraId) {
                    console.log("Selected camera failed. Retrying with default environment camera...");
                    initScanner(null);
                } else {
                    iziToast.error({ title: 'Scanner Error', message: 'Could not access high-quality camera.', position: 'topRight' });
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

            // Update real-time status: Code successfully parsed!
            $("#scannerStatus")
                .html('<i class="bi bi-qr-code text-white"></i><span>QR Code Read: ' + decodedText + '</span>')
                .removeClass('bg-light text-secondary bg-warning text-dark bg-danger text-white bg-info text-white')
                .addClass('bg-success text-white');

            // Turn camera frame border GREEN (Captured state)
            $("#qrcodescannerdiv").removeClass('scanner-not-captured').addClass('scanner-captured');

            // Smart Capture Feedback
            playBeep();
            $("#successFlash").show().fadeOut(400);
            
            if (navigator.vibrate) navigator.vibrate(100);

            fetchloadingtray(decodedText);
        }

        function onScanFailure(error) { 
        }



        function runScanFileFallback(canvas) {
            // Downscale canvas to under 1-megapixel (e.g. max width 960px) before sending to JS engine.
            // This reduces pixel volume by 400%, speeding up pure JS ZXing parsing by 4x (sub-400ms)!
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
                        console.warn("Scan captured frame fallback failed:", err);
                        handleScanError();
                    });
            }, "image/png");
        }

        function handleScanError() {
            iziToast.error({ 
                title: 'Not Detected', 
                message: 'Google Lens scan failed. Move closer, adjust lighting and try again.', 
                position: 'topRight' 
            });
            // Reset state to scanning
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

            if (typeof BarcodeDetector === "undefined") {
                console.log("Native BarcodeDetector not supported in this browser. Relying on standard web decoder fallbacks.");
                return;
            }

            // Wait briefly for video stream element to be fully rendered in DOM
            let retries = 0;
            const findVideoAndStart = setInterval(() => {
                const video = document.querySelector("#reader video");
                if (video) {
                    clearInterval(findVideoAndStart);
                    
                    const detector = new BarcodeDetector({ formats: ['qr_code'] });
                    console.log("Started background hardware-accelerated QR auto-scanner.");
                    autoScanInterval = setInterval(() => {
                        // Check if scanning is active and we are not locked (to avoid multi-trigger duplicate races)
                        if (isScanning && !lastScannedCode) {
                            detector.detect(video)
                                .then(barcodes => {
                                    if (barcodes && barcodes.length > 0 && isScanning && !lastScannedCode) {
                                        const decodedText = barcodes[0].rawValue;
                                        console.log("Auto-scan native success:", decodedText);
                                        onScanSuccess(decodedText);
                                    }
                                })
                                .catch(err => { console.warn("Background auto-detect frame error:", err); });
                        }
                    }, 250); // Scan 4 times a second (completely seamless, zero CPU burden!)
                } else {
                    retries++;
                    if (retries > 20) { // Stop retrying after 4 seconds
                        clearInterval(findVideoAndStart);
                    }
                }
            }, 200);
        }
        //end scanner

 
    function getLoadingItemKey(item) {
        return [
            item.stockorder_no || '',
            item.productid || '',
            item.sowinglotno_internal || '',
            item.locationid || '',
            item.polyhouseid || '',
            item.despatch_location_id || ''
        ].join('|');
    }


    /**
     * Escape text before adding it into HTML.
     */
    function escapeHtml(value) {
        return $('<div>')
            .text(value == null ? '' : String(value))
            .html();
    }


    /**
     * Format number safely.
     */
    function formatNumber(value) {
        return (parseFloat(value) || 0).toLocaleString();
    }


    /**
     * Fetch scanned QR details.
     */
    function fetchloadingtray(qrCodeValue) {
        const barcode = String(qrCodeValue || '').trim();
        const sourceLocationId = String( $('#from_location_id').val() || '' );
        const despatchLocationId = String( $('#despatch_location_id').val() || '' );
        const despatchLocation = String( $('#despatch_location_id option:Selected').text() || '' );

        const vehicle_id = String( $('#vehicle_id').val() || '' );

        if (!sourceLocationId) {
            $('#from_location_id').focus();

            Swal.fire({
                icon: 'warning',
                title: 'Source Location Required',
                text: 'Please select the source location first.',
                confirmButtonColor: '#4f46e5'
            });

            return false;
        }
        if (!despatchLocationId) {
            $('#despatch_location_id').focus();

            Swal.fire({
                icon: 'warning',
                title: 'Destination Location Required',
                text: 'Please select the destination location first.',
                confirmButtonColor: '#4f46e5'
            });

            return false;
        }
        if (!vehicle_id) {
            $('#from_location_id').focus();

            Swal.fire({
                icon: 'warning',
                title: 'Vehicle Details Required',
                text: 'Please select the Vehicle Detail first.',
                confirmButtonColor: '#4f46e5'
            });

            return false;
        }

        if (!barcode) {
            Swal.fire({
                icon: 'warning',
                title: 'Invalid QR Code',
                text: 'The scanned QR code is empty.'
            });

            return false;
        }

        /*
         * Prevent duplicate barcode.
         */
        if (scannedBarcodes.includes(barcode)) {
            Swal.fire({
                icon: 'warning',
                title: 'Already Scanned',
                text: 'This barcode has already been scanned.',
                confirmButtonColor: '#4f46e5'
            });

            return false;
        }

        const rootUrl = String($('#hidden_rooturl').val() || '');
        const companyid = parseInt(
            $('#hidden_companyid').val(),
            10
        ) || 0;

        const subcompanyid = parseInt(
            $('#hidden_subcompanyid').val(),
            10
        ) || 0;

        const projectid = parseInt(
            $('#hidden_projectid').val(),
            10
        ) || 0; 
        exist_tripid = "<?php echo $tripid; ?>"; 
        $.ajax({
            url:   'ajax/handle_receive.php',
            type: 'POST',
            dataType: 'json',

            data: {
                action: 'fetchingqrcodedata',
                qrcode: barcode,
                companyid: companyid,
                subcompanyid: subcompanyid,
                projectid: projectid,
                despatchLocationId: despatchLocationId,
                tripid:exist_tripid
            },

            beforeSend: function () {
                $('#scannerStatus')
                    .html(
                        '<i class="fa fa-spinner fa-spin"></i>' +
                        '<span>Checking QR code...</span>'
                    );
            },

            success: function (response) {
                if (
                    !response ||
                    response.status !== true ||
                    !response.data
                ) {
                    Swal.fire({
                        icon: 'error',
                        title: 'QR Code Not Found',
                        text:
                            response?.message ||
                            'No matching tray was found.'
                    });

                    return;
                }

                /*
                 * Depending on PHP response, data may be:
                 * one object or an array.
                 */
                const rows = Array.isArray(response.data)
                    ? response.data
                    : [response.data];

                if (rows.length === 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'No Data Found',
                        text: 'No tray details were returned.'
                    });

                    return;
                }

                /*
                 * Register barcode only after successful response.
                 */
                scannedBarcodes.push(barcode);

                rows.forEach(function (row) {
                    const item = normalizeScannedItem(
                        row,
                        barcode,
                        despatchLocationId,
                        despatchLocation,

                    );

                    addOrUpdateLoadingItem(item);
                });

                updateLoadingSummary();

                $('#scannerStatus')
                    .html(
                        '<i class="fa fa-check-circle text-success"></i>' +
                        '<span>QR code added successfully.</span>'
                    );

                $('#manualQrCode').val('');

                playBeep();
            },

            error: function (xhr, status, error) {
                console.error('QR AJAX status:', status);
                console.error('QR AJAX error:', error);
                console.error(
                    'QR PHP response:',
                    xhr.responseText
                );

                let message = 'Unable to verify QR code.';

                if (xhr.responseJSON?.message) {
                    message = xhr.responseJSON.message;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Scanner Error',
                    text: message
                });
            }
        });

        return true;
    }

 

    function normalizeScannedItem(row,barcode,despatchLocationId, despatchLocation) {

        const trayCount = 1;

        const qtyPerTray =
            parseFloat(row.qty_per_tray || row.qtypertray || 0);

        const plantCount =
            parseInt(row.qty || row.qtypertray || 0, 10);

        return {

            barcode: barcode,

            stockorder_no: String(row.stockorder_no || ''),

            productid: String(row.productid || ''),

            product_name: String(
                row.product_name ||
                row.productname ||
                ''
            ),

            roottype: String(row.roottype || ''),

            locationid: String(row.LocationId || row.locationid || ''),

            polyhouseid: String(row.polyhouseid || ''),

            polyhousename: String(row.polyhousename || ''),

            sowinglotno_internal: String(row.sowinglotno_internal || ''),

            sowingdate_internal: String(row.sowingdate_internal || ''),

            qty_per_tray: qtyPerTray,

            trays: trayCount,

            qty: plantCount,

            /* New destination fields */

            despatch_location_id: String(despatchLocationId),

            despatch_location_name: String(despatchLocation),

            barcodes: [
                {
                    barcode: barcode,
                    plants: plantCount
                }
            ]
        };
    }


    /**
     * Add a new item or merge into an existing item.
     */
 function addOrUpdateLoadingItem(item) {
    const itemKey = getLoadingItemKey(item);

    if (scannedLoadingItems[itemKey]) {
        const existingItem = scannedLoadingItems[itemKey];

        if (!Array.isArray(existingItem.barcodes)) {
            existingItem.barcodes = [];
        }

        const barcodeValue = String(item.barcode || '').trim();

        const barcodeAlreadyExists = existingItem.barcodes.some(function (barcodeItem) {
            const existingBarcode =
                typeof barcodeItem === 'object'
                    ? barcodeItem.barcode
                    : barcodeItem;

            return String(existingBarcode) === barcodeValue;
        });

        if (barcodeAlreadyExists) {
            Swal.fire({
                icon: 'warning',
                title: 'Already Scanned',
                text: 'This barcode has already been added.'
            });

            return;
        }

        existingItem.trays =
            (parseInt(existingItem.trays, 10) || 0) +
            (parseInt(item.trays, 10) || 1);

        existingItem.qty =
            (parseInt(existingItem.qty, 10) || 0) +
            (parseInt(item.qty, 10) || 0);

        existingItem.barcodes.push({
            barcode: barcodeValue,
            plants: parseInt(item.qty, 10) || 0
        });

        updateExistingLoadingRow(itemKey, existingItem);

        return;
    }

    scannedLoadingItems[itemKey] = {
        ...item,

        despatch_location_id:
            String(item.despatch_location_id || ''),

        despatch_location_name:
            String(item.despatch_location_name || ''),

        trays:
            parseInt(item.trays, 10) || 1,

        qty:
            parseInt(item.qty, 10) || 0,

        barcodes: [
            {
                barcode: String(item.barcode || '').trim(),
                plants: parseInt(item.qty, 10) || 0
            }
        ]
    };

    appendLoadingRow(
        itemKey,
        scannedLoadingItems[itemKey]
    );
}


    /**
     * Create a new row inside #items_container.
     */
    function appendLoadingRow(itemKey, item) { 
        const safeKey = createSafeDomId(itemKey);

        const barcodes = Array.isArray(item.barcodes)
            ? item.barcodes
            : [];

        const trays = parseInt(item.trays, 10) || 0;
        const plants = parseInt(item.qty, 10) || 0;

        const itemName =
            escapeHtml(item.product_name || 'Unknown Product') +
            (
                item.stockorder_no
                    ? ' (' + escapeHtml(item.stockorder_no) + ')'
                    : ''
            );

        const html = `
            <div
                class="item-row scanned-item-row"
                id="loading_item_${safeKey}"
                data-item-key="${escapeHtml(itemKey)}"
                data-productid="${escapeHtml(item.productid || '')}"
                data-roottype="${escapeHtml(item.roottype || '')}"
                data-locationid="${escapeHtml(item.locationid || '')}"
                data-stockorder_no="${escapeHtml(item.stockorder_no || '')}"
                data-qty_per_tray="${parseFloat(item.qtypertray) ||parseFloat(item.qty_per_tray) || 0}"
                /*data-qty-per-tray="${parseFloat(item.qty_per_tray) || 0}"*/
                data-max-trays="${trays}"
                data-max-plants="${plants}"
                data-polyhouseid="${escapeHtml(item.polyhouseid || '')}"
                data-sowinglotno_internal="${escapeHtml(item.sowinglotno_internal || '')}"
                data-polyhousename="${escapeHtml(item.polyhousename || '')}"
            >
                <div class="row align-items-center">

                    <div class="col-md-5">
                        <div class="fw-bold">
                            ${itemName}
                        </div>

                        <div class="fw-semibold text-slate-700">
                            Sowing Date:
                            <span class="item-sowing-date">
                                ${escapeHtml(item.sowingdate_internal || 'N/A')}
                            </span>
                        </div>

                        <div class="fw-semibold text-slate-700">
                            Sowing Lot No:
                            <span class="item-sowing-lot">
                                ${escapeHtml(item.sowinglotno_internal || 'N/A')}
                            </span>
                        </div>

                        <div class="small text-muted mt-1 d-none">
                            Scanned Barcodes:
                            <span class="scanned-barcode-count">
                                ${barcodes.length}
                            </span>
                        </div>

                        <div class="mt-2 barcode-button-container">
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-primary btn-show-barcodes"
                                data-item-key="${escapeHtml(itemKey)}"
                            >
                                <i class="fa fa-qrcode me-1"></i>
                                Show Barcodes
                                (
                                <span class="barcode-count">
                                    ${barcodes.length}
                                </span>
                                )
                            </button>
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-6">
                        <div class="fw-semibold text-slate-700">
                            Poly House:
                            <span class="item-polyhouse-name">
                                ${escapeHtml(item.polyhousename || 'N/A')}
                            </span>
                        </div>

                        <div class="small text-slate-400">
                            Target / Available
                        </div>

                        <div class="fw-semibold text-slate-700">
                            <span class="available-trays">
                                ${formatNumber(trays)}
                            </span>
                            Trays
                        </div>

                        <div class="fw-semibold text-indigo-600">
                            <span class="available-plants">
                                ${formatNumber(plants)}
                            </span>
                            Plants
                        </div>
                    </div>


                    <div class="col-md-4 col-sm-3">
                        <div class="fw-bold">
                            Destination
                        </div>

                       <span class="despatch_location_name fw-bold"> ${item.despatch_location_name}
                            </span>
                    </div>

                    <div class="col-md-2 col-sm-3 ">
                        <div class="small text-slate-400">
                            Loading Trays
                        </div>

                        <input
                            type="number"
                            class="form-control form-control-sm loading-trays-input"
                            min="0"
                            max="${trays}"
                            value="${trays}"
                            onchange="checkCapacityWarning();"
                            readonly
                        >
                    </div>

                    <div class="col-md-2 col-sm-3 ">
                        <div class="small text-slate-400">
                            Loading Plants
                        </div>

                        <input
                            type="number"
                            class="form-control form-control-sm loading-plants-input"
                            min="0"
                            max="${plants}"
                            value="${plants}"
                            readonly
                        >

                        <input
                            type="hidden"
                            class="item-productid"
                            value="${escapeHtml(item.productid || '')}"
                        >

                        <input
                            type="hidden"
                            class="item-stockorder-no"
                            value="${escapeHtml(item.stockorder_no || '')}"
                        >

                        <input
                            type="hidden"
                            class="item-barcodes"
                            value="${escapeHtml(JSON.stringify(barcodes))}"
                        >

                        <input
                            type="hidden"
                            class="item-despatch_location_id"
                            value="${escapeHtml(JSON.stringify(item.despatch_location_name))}"
                        >
                        <input
                            type="hidden"
                            class="item-destinationLocation"
                            value="${escapeHtml(JSON.stringify(item.despatch_location_id))}"
                        >
                    </div>

                </div>
            </div>
        `;

        const $summaryRow = $('#items_container .summary-row');

        if ($summaryRow.length) {
            $summaryRow.before(html);
        } else {
            $('#items_container').append(html);
        }
    }


    /**
     * Update row when another barcode belongs to same group.
     */ 

    function updateExistingLoadingRow(itemKey, item) {
        const safeKey = createSafeDomId(itemKey);
        const $row = $('#loading_item_' + safeKey);

        if (!$row.length) {
            appendLoadingRow(itemKey, item);
            return;
        }

        const trays = parseInt(item.trays, 10) || 0;
        const plants = parseInt(item.qty, 10) || 0;

        const barcodes = Array.isArray(item.barcodes)
            ? item.barcodes
            : [];

        $row
            .attr('data-max-trays', trays)
            .attr('data-max-plants', plants);

        $row
            .find('.available-trays')
            .text(formatNumber(trays));

        $row
            .find('.available-plants')
            .text(formatNumber(plants));

        /*
         * Update barcode count.
         */
        $row
            .find('.barcode-count')
            .text(barcodes.length);

        /*
         * If Show Barcodes button is missing, create it.
         */
        if ($row.find('.btn-show-barcodes').length === 0) {
            const barcodeButtonHtml = `
                <div class="mt-2 barcode-button-container">
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-primary btn-show-barcodes"
                        data-item-key="${escapeHtml(itemKey)}"
                    >
                        <i class="fa fa-qrcode me-1"></i>
                        Show Barcodes
                        (<span class="barcode-count">${barcodes.length}</span>)
                    </button>
                </div>
            `;

            $row
                .find('.col-md-5')
                .first()
                .append(barcodeButtonHtml);
        } else {
            /*
             * Update key and count when button already exists.
             */
            $row
                .find('.btn-show-barcodes')
                .attr('data-item-key', itemKey);

            $row
                .find('.barcode-count')
                .text(barcodes.length);
        }

        $row
            .find('.loading-trays-input')
            .attr('max', trays)
            .val(trays);

        $row
            .find('.loading-plants-input')
            .attr('max', plants)
            .val(plants);

        $row
            .find('.item-barcodes')
            .val(JSON.stringify(barcodes));

         
    }


    /**
     * Convert grouping key into safe HTML ID.
     */
    function createSafeDomId(value) {
        return String(value)
            .replace(/[^a-zA-Z0-9_-]/g, '_');
    }


    /**
     * Recalculate visible totals.
     */
    function updateLoadingSummary() {
        let totalTrays = 0;
        let totalPlants = 0;

        Object.values(
            scannedLoadingItems
        ).forEach(function (item) {
            totalTrays += parseInt(
                item.trays,
                10
            ) || 0;

            totalPlants += parseInt(
                item.qty,
                10
            ) || 0;
        });

        $('#total_loading_trays')
            .text(totalTrays.toLocaleString());

        $('#total_loading_plants')
            .text(totalPlants.toLocaleString());

        /*
         * Run your vehicle capacity validation.
         */
        if (
            typeof checkCapacityWarning ===
            'function'
        ) {
            checkCapacityWarning();
        }
        $('#manualQrCode').val('')
    } 

function showBarcodeDetails(itemKey) {
    const item = scannedLoadingItems[itemKey];

    if (!item) {
        Swal.fire({
            icon: 'error',
            title: 'Item Not Found',
            text: 'Unable to find barcode details for this item.'
        });

        return;
    }

    $('#active_barcode_item_key').val(itemKey);

    $('#barcodeDetailsModalLabel').html(
        '<i class="fa fa-qrcode me-2"></i>' +
        'Scanned Barcodes - ' +
        escapeHtml(item.product_name || 'Item')
    );

    $('#barcode_item_summary').html(`
        <div class="alert alert-light border mb-0">
            <div class="row">
                <div class="col-md-4">
                    <strong>Stock Order:</strong><br>
                    ${escapeHtml(item.stockorder_no || 'N/A')}
                </div>

                <div class="col-md-4">
                    <strong>Sowing Lot:</strong><br>
                    ${escapeHtml(item.sowinglotno_internal || 'N/A')}
                </div>

                <div class="col-md-4">
                    <strong>Polyhouse:</strong><br>
                    ${escapeHtml(item.polyhousename || 'N/A')}
                </div>
            </div>
        </div>
    `);

    renderBarcodeDetailsTable(itemKey);

    $('#barcodeDetailsModal').modal('show');
}
function renderBarcodeDetailsTable(itemKey) {
    const item = scannedLoadingItems[itemKey];
    const $body = $('#barcode_details_body');

    $body.empty();

    if (
        !item ||
        !Array.isArray(item.barcodes) ||
        item.barcodes.length === 0
    ) {
        $('#no_barcode_message').show();
        $('.table-responsive').hide();
        return;
    }

    $('#no_barcode_message').hide();
    $('.table-responsive').show();

    item.barcodes.forEach(function (barcodeItem, index) {
        const barcodeValue =
            typeof barcodeItem === 'object'
                ? barcodeItem.barcode
                : barcodeItem;

        const plants =
            typeof barcodeItem === 'object'
                ? parseInt(barcodeItem.plants, 10) || 0
                : parseInt(item.qty_per_tray, 10) || 0;

        $body.append(`
            <tr>
                <td>${index + 1}</td>

                <td>
                    <span class="fw-bold">
                        ${escapeHtml(barcodeValue)}
                    </span>
                </td>

                <td>
                    ${plants.toLocaleString()}
                </td>

                <td class="text-center">
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-danger btn-delete-barcode"
                        data-item-key="${escapeHtml(itemKey)}"
                        data-barcode="${escapeHtml(barcodeValue)}"
                        title="Delete this scanned tray"
                    >
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
        `);
    });
} 

 
 function loadAssignmentMastersReceive(  tripId,  locationId ) {
    const projectId =  parseInt( $('#hidden_projectid').val(),  10 ) || 0;
    tripId = String(tripId || '').trim();
    locationId = String(locationId || '').trim();
    if (projectId <= 0) {
        Swal.fire(
            'Error',
            'Valid project ID is required.',
            'error'
        );
        return;
    }
    if (!tripId) {
        Swal.fire(
            'Error',
            'Trip ID is required.',
            'error'
        );
        return;
    }

    if (!locationId) {
        Swal.fire(
            'Error',
            'Receiving location is required.',
            'error'
        );
        return;
    } 
    const $masterSelects = $('#loc_polyhouseid');

    $.ajax({
        url: 'ajax/handle_receive.php',
        type: 'POST',
        dataType: 'json',

        data: {
            action: 'loadingmasters_receive',
            projectid: projectId,
            tripid: tripId,
            locationid: locationId
        },

        beforeSend: function () {
            $masterSelects.prop('disabled', true);
        },

        success: function (response) {
            console.log('Receive master response:', response);

            if (!response || response.status !== true) {
                Swal.fire(
                    'Error',
                    response?.message || 'Unable to load receiving data.',
                    'error'
                );
                return;
            }

            const data = response.data || {};

            const existingTrip = Array.isArray(data.existingtrip)
                ? data.existingtrip
                : [];

            if (existingTrip.length === 0) {
                Swal.fire(
                    'Error',
                    'No trip information was returned.',
                    'error'
                );
                return;
            }

            const tripHeader = existingTrip[0];

            const $polyhouse = $('#loc_polyhouseid');

            $polyhouse.empty();

            $polyhouse.append(
                '<option value="">-- Choose Receiving Polyhouse --</option>'
            );

            (data.polyhouse || []).forEach(function (row) {

                const selected =
                    String(row.id) === String(tripHeader.polyhouseid)
                        ? 'selected'
                        : '';

                $polyhouse.append(
                    `<option
                        value="${row.id}"
                        data-name="${row.name}"
                        ${selected}>
                        ${row.name}
                    </option>`
                );

            });

            fillReceiveTripHeader(tripHeader);
        }
    })
}
 
 function fillReceiveTripHeader(tripHeader) {
    if (!tripHeader) {
        return;
    }
    setReadonlySelectValue(
        '#from_location_id',
        tripHeader.from_location_id,
        tripHeader.from_location_name
    );
    setReadonlySelectValue(
        '#to_location_id',
        tripHeader.depatchlocationid,
        tripHeader.despatchlocationname
    );
    setReadonlySelectValue(
        '#despatch_location_id',
        tripHeader.depatchlocationid,
        tripHeader.despatchlocationname
    );



    setReadonlySelectValue(
        '#vehicle_id',
        tripHeader.active_vehicle_id,
        tripHeader.active_vehicle_no
    );
    setReadonlySelectValue(
        '#driver_id',
        tripHeader.driverid,
        tripHeader.active_driver_name
    );

    setReadonlySelectValue(
        '#purpose_to_move',
        tripHeader.purpose_to_move,
        tripHeader.purpose_to_move
    );
    $('#hidden_trip_id').val(
        tripHeader.active_trip_id || ''
    );
    $('[name="dispatch_time"]').val(
        tripHeader.dispatch_time || ''
    ).prop("disabled",true);
    $('[name="remarks"]').val(
        tripHeader.send_remark || ''
    ).prop("disabled",true);

  

    $('#total_sent_trays').text(
        parseInt(
            tripHeader.traycount,
            10
        ) || 0
    );

    $('#total_sent_plants').text(
        (
            parseInt(
                tripHeader.plantsent,
                10
            ) || 0
        ).toLocaleString()
    );
    $('.trip_totalplants').text(tripHeader.plantsent);
    $('.trip_totaltray').text(tripHeader.traycount);
}

function setReadonlySelectValue(selector, value, text) {

    const $select = $(selector);

    $select.empty();

    $select.append(
        $('<option>', {
            value: value,
            text: text,
            selected: true
        })
    );

    $select.val(value);

    $select.prop('disabled', true);
}
</script>
