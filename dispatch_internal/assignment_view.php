<?php
    include('../header.php');
    date_default_timezone_set("Asia/Calcutta");
    $tripid = $_GET['tripid'] ?? '';
    $sids = $_GET['sids'] ?? '';
    $sord = $_GET['sord'] ?? '';
    $sloc = $_GET['sloc'] ?? '';
 
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
    <div class="row mb-4">
        <div class="col-md-12">
          <!--   <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo ($type == 'internal' || $type == 'edittripid') ? 'internaldispatch.php' : 'index.php'; ?>?k=<?php echo $key; ?><?php echo (isset($_GET['iframe']) && $_GET['iframe'] == 'true') ? '&iframe=true' : ''; ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active">Assignment</li>
                </ol>
            </nav> -->
      <!--       <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <h2 class="fw-bold text-slate-900 mb-0">Vehicle & Driver Assignment</h2>
                <a href="<?php echo ($type == 'internal' || $type == 'edittripid') ? 'internaldispatch.php' : 'index.php'; ?>?k=<?php echo $key; ?><?php echo (isset($_GET['iframe']) && $_GET['iframe'] == 'true') ? '&iframe=true' : ''; ?>" class="btn-back">
                    <i class="fa fa-arrow-left"></i>
                    <span>Back to Dashboard</span>
                </a>
            </div> -->
             
           <!--  <div class="alert alert-warning border-0 shadow-sm rounded-16 d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2" style="background: #fffbeb; border: 1px solid #fef3c7;">
                    <div class="d-flex align-items-center">
                        <i class="fa fa-file-pdf fa-2x me-3 text-warning"></i>
                        <div>
                            <div class="fw-bold text-amber-900">Documents Already Generated</div>
                            <div class="small text-amber-700">The Gate Pass or Delivery Note has already been created for Trip <span class="fw-bold"><?php echo $trip_id; ?></span>. Updating these details may require re-generating the documents.</div>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        
                    </div>
            </div>  -->
        </div>
    </div>

  <!-- <form id="frm_assignment">   -->
         
     <h3 class="fw-bold mb-4 text-slate-800">
    <span class="showordno"></span>
     
</h3>
        <div class="row g-4">
            <!-- Resource Selection -->
            <div class="col-lg-5">
                <div class="glass-card p-4">
                    <h5 class="fw-bold mb-4 text-slate-800"><i class="fa fa-truck me-2 text-primary"></i>Resource Details</h5>
                    
                    <!-- Where From (Source Location) -->
                    <div class="mb-3">
                        <label class="form-label">Where From (Source Location)</label>
                        <select class="form-select form-control" name="from_location_id" id="from_location_id" required>
                            <option value="">-- Choose Source --</option>
                              
                        </select>
                    </div>

                    <!-- Where to Move (Destination Location) --> 
                    <div class="mb-3">
                        <label class="form-label">Where to  last Destination (Location)</label>
                        <select class="form-select form-control" name="to_location_id" id="to_location_id" required>
                            <option value="">-- Choose Destination --</option>
                            
                        </select>
                    </div> 

                   <div class="mb-4">
                        <label class="form-label">Dispatch Date &amp; Time</label>

                        <input type="datetime-local"  class="form-control" name="dispatch_time" id = "dispatch_time" value="<?php echo date('Y-m-d\TH:i'); ?>"
                            required
                        >
                    </div>
                     
                    <div class="mb-3" id="distance_info_container">
                        <span class="small text-slate-500">Calculated Distance: </span>
                        <span class="fw-bold text-slate-800" id="distance_helper"> </span>
                    </div> 
 
                     <div class="mb-3">
                        <label class="form-label">Select Vehicle</label>
                        <select class="form-select form-control" name="vehicle_id" id="vehicle_id" required>
                            <option value="">-- Choose Vehicle --</option>
 
                        </select>
                    </div> 
                    <div id="vehicle_capacity_container" class="mb-3 p-3 rounded-16 border d-none" style="background: rgba(79, 70, 229, 0.04); border-color: rgba(79, 70, 229, 0.15); backdrop-filter: blur(8px);">
                        <div class="row text-center g-0">
                            <div class="col-4 border-end">
                                <div class="x-small text-slate-500 text-uppercase fw-bold" style="font-size: 0.7rem;">Capacity</div>
                                <div class="fw-bold text-slate-800 mt-1" id="v_cap_display" style="font-size: 0.95rem;">0 / 0</div>
                                <div class="x-small text-slate-400" style="font-size: 0.65rem;">Trays / Plants</div>
                            </div>
                            <div class="col-4 border-end">
                                <div class="x-small text-slate-500 text-uppercase fw-bold" style="font-size: 0.7rem;">Assigned</div>
                                <div class="fw-bold text-warning mt-1" id="v_assigned_display" style="font-size: 0.95rem;">0 / 0</div>
                                <div class="x-small text-slate-400" style="font-size: 0.65rem;">Active Trips</div>
                            </div>
                            <div class="col-4">
                                <div class="x-small text-slate-500 text-uppercase fw-bold" style="font-size: 0.7rem;">Allocatable</div>
                                <div class="fw-bold text-success mt-1" id="v_allocatable_display" style="font-size: 0.95rem;">0 / 0</div>
                                <div class="x-small text-slate-400" style="font-size: 0.65rem;">Remaining</div>
                            </div>
                        </div>
                        <div id="v_capacity_warning" class="mt-2 text-danger fw-bold text-center d-none" style="font-size: 0.75rem;">
                            <i class="fa fa-exclamation-triangle me-1"></i> Loaded qty exceeds vehicle capacity!
                        </div>
                    </div>

                    <!-- Today's Trips for Vehicle Info Box -->
                    <div id="today_trips_container" class="mb-3 p-3 rounded-16 border d-none" style="background: rgba(14, 165, 233, 0.04); border-color: rgba(14, 165, 233, 0.15);">
                        <div class="fw-bold text-info mb-2 small"><i class="fa fa-route me-1"></i> Today's Assigned Trips:</div>
                        <div id="today_trips_list" style="font-size: 0.75rem;"></div>
                    </div>

                    <!-- Select Driver -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label mb-0">Select Driver</label>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-link p-0 text-primary" id="btn_add_driver" title="Add New Driver" style="text-decoration: none; font-size: 0.8rem; font-weight: 600;">
                                    <i class="fa fa-plus"></i> Add Driver
                                </button>
                                <button type="button" class="btn btn-sm btn-link p-0 text-secondary" id="btn_refresh_drivers" title="Refresh List" style="text-decoration: none; font-size: 0.8rem; font-weight: 600;">
                                    <i class="fa fa-refresh"></i> Refresh
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

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Freight Amount (INR)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white">₹</span>
                                <input type="number" step="0.01" class="form-control freight-calc" name="freight_amount" id="freight_amount" 
                                    value="" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Discount (%)</label>
                            <div class="input-group">
                                <input type="number" step="0.1" class="form-control freight-calc" name="freight_discountpercent" id="freight_discountpercent" 
                                    value="">
                                <span class="input-group-text bg-white">%</span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Payable Freight Amount</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">₹</span>
                            <input type="number" step="0.01" class="form-control bg-light" name="payable_freightamount" id="payable_freightamount" 
                                value="" readonly>
                        </div>
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
                        <label class="form-label fw-bold">Destination Location</label>
                        <select class="form-select form-control" name="despatch_location_id" id="despatch_location_id" >
                            <option value="">-- Choose Source Location --</option>
                            
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


<!-- Modal for Adding New Driver -->
<div class="modal fade" id="addDriverModal" tabindex="-1" role="dialog" aria-labelledby="addDriverModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
            <div class="modal-header bg-light" style="border-bottom: 1px solid #f1f5f9; border-top-left-radius: 16px; border-top-right-radius: 16px; padding: 16px 24px;">
                <h5 class="modal-title fw-bold text-slate-800" id="addDriverModalLabel">
                    <i class="fa fa-user-plus me-2 text-indigo-600"></i>Add New Driver Details
                </h5>
                <button type="button" class="close btn" data-dismiss="modal" aria-label="Close" style="font-size: 1.25rem; font-weight: 700; color: #94a3b8; outline: none; box-shadow: none;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <!-- <form id="form_add_driver"> -->
                <div class="modal-body" style="padding: 24px;">
                    <div class="mb-3">
                        <label for="modal_driver_name" class="form-label" style="font-weight: 600; color: #475569; font-size: 0.85rem; text-transform: uppercase;">Driver Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="modal_driver_name" name="drivername" required placeholder="Enter driver name" style="border-radius: 12px; padding: 10px 16px;">
                    </div>
                    <div class="mb-3">
                        <label for="modal_driver_mobile" class="form-label" style="font-weight: 600; color: #475569; font-size: 0.85rem; text-transform: uppercase;">Mobile Number</label>
                        <input type="text" class="form-control" id="modal_driver_mobile" name="drivermobileno" placeholder="Enter mobile number" style="border-radius: 12px; padding: 10px 16px;">
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #f1f5f9; padding: 16px 24px; display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" style="border-radius: 12px; padding: 8px 16px; font-weight: 600;">Close</button>
                    <button   class="btn btn-save savedriver" style="border-radius: 12px; padding: 8px 24px; font-weight: 600; background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: white; border: none; box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.4);">
                        <i class="fa fa-save me-1"></i> Save Driver
                    </button>
                </div>
            <!-- </form> -->
        </div>
    </div>
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
    var exist_tripid = "<?php echo $tripid; ?>";
    var fromLocationId  = "<?php echo $sloc; ?>"; 
    //$(".showordno").text("<?php echo $sord; ?>")
    
    loadAssignmentMasters(exist_tripid, {
        fromLocationId: fromLocationId
    });
     $(document).on('input', '.freight-calc', function() {
        let amount = parseFloat($('#freight_amount').val()) || 0;
        let discount = parseFloat($('#freight_discountpercent').val()) || 0;
        let payable = amount - (amount * (discount / 100));
        $('#payable_freightamount').val(payable.toFixed(2));
    });
    $(document).on('click', '.btn-delete-barcode', function () {
        const itemKey = String($(this).data('item-key') || '');
        const barcode = String($(this).data('barcode') || '');

        Swal.fire({
            icon: 'warning',
            title: 'Delete Scanned Tray?',
            html:
                'Barcode <strong>' +
                escapeHtml(barcode) +
                '</strong> will be removed.',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Delete',
            cancelButtonText: 'Cancel'
        }).then(function (result) {
            if (result.isConfirmed) {
                deleteScannedBarcode(itemKey, barcode);
            }
        });
    });
    $(document).on('click', '.btn-show-barcodes', function () {
        const itemKey = String($(this).data('item-key') || '');

        showBarcodeDetails(itemKey);
    });
 
    $(document).on('click', '#finalsave_loading', function () { 
        var ex_tripid = "<?php echo $tripid ?? ''; ?>";
        if (!$('#from_location_id').val()){
            alert("Source Location can't be empty");
            $('#from_location_id').focus();
            return false;
        }
        else if (!$('#to_location_id').val()){
            alert("Destination Location can't be empty");
            $('#from_location_id').focus();
            return false;
        }  else if (!$('[name="dispatch_time"]').val()){
            alert("dispatch_time can't be empty");
            $('[name="dispatch_time"]').focus();
            return false;
        } else if (!$('#vehicle_id').val()){
            alert("Vehicle  can't be empty");
            $('#vehicle_id').focus();
            return false;
        } else if (!$('#purpose_to_move').val()){
            alert("Purpose To Move can't be empty");
            $('#purpose_to_move').focus();
            return false;
        }

        const formData = new FormData();

        formData.append('trip_id', ex_tripid);
        formData.append('ref_type', 'internal');
        formData.append('projectid', $('#hidden_projectid').val());
        formData.append('vehicle_id', $('#vehicle_id').val());
        formData.append('vehicle_no', $('#vehicle_id option:selected').text().trim());
        formData.append('driver_id', $('#driver_id').val());
        formData.append('driver_name', $('#driver_id option:selected').text().trim());
        formData.append('dispatch_time', $('[name="dispatch_time"]').val());
        formData.append('remarks', $('[name="remarks"]').val().trim());
        formData.append('purpose_to_move', $('#purpose_to_move').val());
        formData.append('freight_amount', $('#freight_amount').val());
        formData.append('freight_discountpercent', $('#freight_discountpercent').val());
        formData.append('payable_freightamount', $('#payable_freightamount').val());
        const $from = $('#from_location_id option:selected');
        formData.append('from_location_id', $from.val());
        formData.append('from_location_name', $from.text().trim());
        formData.append('from_lat', $from.data('lat'));
        formData.append('from_lon', $from.data('lng'));

        const $to = $('#to_location_id option:selected');
        formData.append('to_location_id', $to.val());
        formData.append('to_location_name', $to.text().trim());
        formData.append('to_lat', $to.data('lat'));
        formData.append('to_lon', $to.data('lng'));


        formData.append('distance_km', $('#distance_helper').text().trim());

        const items = getScannedLoadingItemsForSave();

        if (items.length === 0) {

            Swal.fire({
                icon: 'warning',
                title: 'No Scanned Items',
                text: 'Please scan at least one tray before generating the trip.'
            });

            return;
        }

        formData.append(
            'items_json',
            JSON.stringify(items)
        );

        console.log([...formData.entries()]);

        submitAssignmentForm(formData);

    });

      $('.savedriver').on('click', function(e) { 
       
        let drivername = $('#modal_driver_name').val().trim();
        let drivermobileno = $('#modal_driver_mobile').val().trim();
        let projectid = $("#hidden_projectid").val() || 0;


        if (!drivername) {
            Swal.fire('Error', 'Driver name is required.', 'error');
            return;
        }

        $.ajax({
            url: 'ajax/handle_driver.php',
            type: 'POST',
            data: {
                action: 'add_driver',
                drivername: drivername,
                drivermobileno: drivermobileno,
                projectid: projectid
            },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success' && res.driver) {
                    Swal.fire('Saved!', 'Driver added successfully.', 'success');
                    
                    // Add new option to dropdown and select it
                    let opt = $('<option>')
                        .val(res.driver.driverid)
                        .text(res.driver.drivername)
                        .attr('data-name', res.driver.drivername)
                        .attr('data-mobile', res.driver.drivermobileno)
                        .prop('selected', true);
                    
                    $('#driver_id').append(opt).trigger('change');
                    
                    // Clear input form & Close Modal
                    
                    $('#addDriverModal').modal('hide');
                } else {
                    Swal.fire('Error', res.message || 'Failed to save driver.', 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Something went wrong.', 'error');
            }
        });
    });

    $(document).on('change', '#from_location_id', function () {
         updateDestinationLocations(true);
         updateDestinationLocations_despatchlocation(true);
    });



    $(document).on('change', '#to_location_id', function () {
        const fromLocationId = String($('#from_location_id').val() || '');
        const toLocationId = String($(this).val() || '');

        if (
            fromLocationId !== '' &&
            toLocationId !== '' &&
            fromLocationId === toLocationId
        ) {
            $(this).val('').trigger('change');

            Swal.fire({
                icon: 'warning',
                title: 'Same Location Not Allowed',
                text: 'Please choose a destination different from the source location.',
                confirmButtonColor: '#4f46e5'
            });
        }
    });

    $(document).on('change', '#despatch_location_id', function () {
        const fromLocationId = String($('#from_location_id').val() || '');
        const toLocationId = String($(this).val() || '');

        if (
            fromLocationId !== '' &&
            toLocationId !== '' &&
            fromLocationId === toLocationId
        ) {
            $(this).val('').trigger('change');

            Swal.fire({
                icon: 'warning',
                title: 'Same Location Not Allowed',
                text: 'Please choose a destination different from the source location.',
                confirmButtonColor: '#4f46e5'
            });
        }
    });

    $('#vehicle_id, #from_location_id, #to_location_id').on('change', function() {
        updateDistanceAndFreight();
    });

    $('#btn_add_driver').on('click', function() {
        $('#addDriverModal').modal('show');
    });
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


function populateSelect(options) {
    const {
        selector,
        rows,
        placeholder = '-- Select --',
        valueField = 'id',
        textField = 'name',
        selectedValue = '',
        dataFields = {}
    } = options;

    const $select = $(selector);

    $select.empty();

    $select.append(
        $('<option>', {
            value: '',
            text: placeholder
        })
    );

    $.each(rows || [], function (_, row) {
        const value = row[valueField] ?? '';
        const text = row[textField] ?? '';

        const $option = $('<option>', {
            value: value,
            text: text
        });

        $.each(dataFields, function (dataName, rowField) {
            $option.attr(
                'data-' + dataName,
                row[rowField] ?? ''
            );
        });

        if (
            String(value) === String(selectedValue) ||
            row.selected === true
        ) {
            $option.prop('selected', true);
        }

        $select.append($option);
    });

    $select.trigger('change');
}


function loadAssignmentMasters(tripid,selectedValues = {}) {
    const projectid = parseInt($("#hidden_projectid").val(), 10) || 0;
 
    if (projectid <= 0) {
        Swal.fire(
            'Error',
            'Valid project ID is required.',
            'error'
        );
        return;
    }

    const $masterSelects = $(
        '#from_location_id, ' +
        '#to_location_id, ' +
        '#despatch_location_id, ' +
        '#vehicle_id, ' +
        '#driver_id, ' +
        '#purpose_to_move'
    );
    $("#from_location_id").prop("disabled",true);

    $.ajax({
        url: 'ajax/handle_pso.php',
        type: 'POST',
        dataType: 'json',

        data: {
            action: 'loadingmasters',
            projectid: projectid,
             tripid: tripid

        },

        beforeSend: function () {
            //$masterSelects.prop('disabled', true);
        },

        success: function (response) {
            console.log('Master response:', response);

            if (!response || response.status !== true) {
                Swal.fire(
                    'Error',
                    response?.message || 'Unable to load master data.',
                    'error'
                );
                return;
            }

            const data = response.data || {};
            const existingTrip = Array.isArray(data.existingtrip)
                ? data.existingtrip
                : [];

            populateSelect({
                selector: '#from_location_id',
                rows: data.locations || [],
                placeholder: '-- Choose Source --',
                selectedValue: selectedValues.fromLocationId || '',
                dataFields: {
                    name: 'name',
                    lat: 'latitude',
                    lng: 'longitude'
                }
            });

            populateSelect({
                selector: '#to_location_id',
                rows: data.locations || [],
                placeholder: '-- Choose Destination --',
                selectedValue: selectedValues.toLocationId || '',
                dataFields: {
                    name: 'name',
                    lat: 'latitude',
                    lng: 'longitude'
                }
            });

            populateSelect({
                selector: '#despatch_location_id',
                rows: data.locations || [],
                placeholder: '-- Choose Destination --',
                selectedValue: selectedValues.toLocationId || '',
                dataFields: {
                    name: 'name',
                    lat: 'latitude',
                    lng: 'longitude'
                }
            });

            populateSelect({
                selector: '#vehicle_id',
                rows: data.vehicles || [],
                placeholder: '-- Choose Vehicle --',
                textField: 'label',
                selectedValue: selectedValues.vehicleId || '',
                dataFields: {
                    no: 'number',
                    name: 'name'
                }
            });

            populateSelect({
                selector: '#driver_id',
                rows: data.drivers || [],
                placeholder: '-- Choose Driver --',
                selectedValue: selectedValues.driverId || '',
                dataFields: {
                    name: 'name',
                    mobile: 'mobile'
                }
            });

            populateSelect({
                selector: '#purpose_to_move',
                rows: data.purposes || [],
                placeholder: '-- Choose Purpose --',
                selectedValue: selectedValues.purpose || ''
            });
            if(existingTrip.length >0){
     
                    fillExistingTripHeader(existingTrip);
            }


        },

        error: function (xhr, status, error) {
            // console.error('AJAX status:', status);
            // console.error('AJAX error:', error);
            // console.error('PHP response:', xhr.responseText);

            let message = 'Unable to load assignment data.';

            if (xhr.responseJSON?.message) {
                message = xhr.responseJSON.message;
            } else if (xhr.responseText) {
                message = xhr.responseText.substring(0, 300);
            }

            Swal.fire('Error', message, 'error');
        },

        // complete: function () {
        //     $masterSelects.prop('disabled', false);
        // }
    });
}

function updateDestinationLocations(showMessage = false) {
    const fromLocationId = String($('#from_location_id').val() || '');
    const $toLocation = $('#to_location_id');
    const currentToLocationId = String($toLocation.val() || '');

    // Enable all destination options first
    $toLocation.find('option').prop('disabled', false);

    if (fromLocationId === '') {
        return;
    }

    // Disable same location in destination dropdown
    $toLocation
        .find('option')
        .filter(function () {
            return String($(this).val()) === fromLocationId;
        })
        .prop('disabled', true);

    // If same destination was already selected, reset it
    if (currentToLocationId === fromLocationId) {
        $toLocation.val('').trigger('change');

        if (showMessage) {
            Swal.fire({
                icon: 'warning',
                title: 'Same Location Not Allowed',
                text: 'Source and destination locations cannot be the same.',
                confirmButtonColor: '#4f46e5'
            });
        }
    }
}

function updateDestinationLocations_despatchlocation(showMessage = false) {
    const fromLocationId = String($('#from_location_id').val() || '');
    const $toLocation = $('#despatch_location_id');
    const currentToLocationId = String($toLocation.val() || '');

    // Enable all destination options first
    $toLocation.find('option').prop('disabled', false);

    if (fromLocationId === '') {
        return;
    }

    // Disable same location in destination dropdown
    $toLocation
        .find('option')
        .filter(function () {
            return String($(this).val()) === fromLocationId;
        })
        .prop('disabled', true);

    // If same destination was already selected, reset it
    if (currentToLocationId === fromLocationId) {
        $toLocation.val('').trigger('change');

        if (showMessage) {
            Swal.fire({
                icon: 'warning',
                title: 'Same Location Not Allowed',
                text: 'Source and Despach locations cannot be the same.',
                confirmButtonColor: '#4f46e5'
            });
        }
    }
}

   function calculateHaversineDistance(lat1, lon1, lat2, lon2) {
        if (!lat1 || !lon1 || !lat2 || !lon2) return 0;
        const R = 6371; // Radius of the earth in km
        const dLat = deg2rad(lat2 - lat1);
        const dLon = deg2rad(lon2 - lon1);
        const a = 
            Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) * 
            Math.sin(dLon / 2) * Math.sin(dLon / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    }

    function deg2rad(deg) {
        return deg * (Math.PI / 180);
    }


  function updateDistanceAndFreight() { 
        activeTripIdGlobal = '';
        let vehicle_id = $('#vehicle_id').val();
        let from_opt = $('#from_location_id option:selected');
        if (vehicle_id && !$('#from_location_id').val()){
            alert ("first choose Source location");
            return false;
        }
        if (vehicle_id && !$('#to_location_id').val()){
            alert ("first choose Destination location");
            return false;
        }
        sourcelocation =$('#from_location_id').val();
        let from_name = from_opt.data('name') || '';
        let from_lat = parseFloat(from_opt.data('lat')) || 0;
        let from_lng = parseFloat(from_opt.data('lng')) || 0;
        
        $('#from_location_name').val(from_name);
        $('#source_lat').val(from_lat || '');
        $('#source_lng').val(from_lng || '');
        
        let to_lat = 0;
        let to_lng = 0;
        let to_location_id_val = $('#to_location_id').val();
        
        if ($('#to_location_id').is('select') && to_location_id_val) {
            let selectedOpt = $('#to_location_id option:selected');
            let to_name = selectedOpt.data('name') || '';
            to_lat = parseFloat(selectedOpt.data('lat')) || 0;
            to_lng = parseFloat(selectedOpt.data('lng')) || 0;
            
            $('#to_location_name').val(to_name);
            $('#to_lat').val(to_lat || '');
            $('#to_lon').val(to_lng || '');
        } else {
            to_lat = parseFloat($('#to_lat').val()) || 0;
            to_lng = parseFloat($('#to_lon').val()) || 0;
        }
        
        let distance = 0;
        if (from_lat && from_lng && to_lat && to_lng) {
            distance = calculateHaversineDistance(from_lat, from_lng, to_lat, to_lng);
            distance = Math.round(distance * 100) / 100; // round to 2 decimals
        }
        
        $('#distance_km').val(distance);
        $('#distance_helper').text(distance > 0 ? distance + ' km' : '0 km');
        
      
        if (!vehicle_id) {
            $('#vehicle_capacity_container').slideUp(300, function() {
                $(this).addClass('d-none');
            });
            $('#today_trips_container').slideUp(300, function() {
                $(this).addClass('d-none');
            });
            return;
        } 

        // Fetch predefined/calculated freight
        $.ajax({
            url: 'ajax/handle_pso.php',
            type: 'POST',
            data: { 
                action: 'get_freight_details', 
                psono: '<?php echo $id; ?>',
                vehicle_id: vehicle_id,
                distance: distance,
                sourcelocation:sourcelocation,
                trip_id: '<?php echo $tripid ?? ''; ?>'
            },
            dataType: 'json',
            success: function(res) {
                if (res.status == 'success' && res.data) {
                    if ($('#freight_amount').data('freightamt')){                        
                        $('#freight_amount').val($('#freight_amount').data('freightamt'));
                        $('#freight_discountpercent').val($('#freight_discountpercent').data('freightdis'));
                        $('#payable_freightamount').val($('#payable_freightamount').data('payableamt'));
                        $('#freight_amount').removeData('freightamt');
                        $('#freight_discountpercent').removeData('freightdis');
                        $('#payable_freightamount').removeData('payableamt');

                    }else{
                        $('#freight_amount').val(res.data.freight_amount);
                        $('#freight_discountpercent').val(res.data.freight_discountpercent);
                        $('#payable_freightamount').val(res.data.payable_freightamount);

                    }
                    
                    // Trigger calculation trigger
                    $('.freight-calc').first().trigger('input');

                    // Show vehicle capacity details
                    let cap_trays = parseInt(res.data.tray_capacity) || 0;
                    let cap_plants = parseInt(res.data.plant_capacity) || 0;
                    let assigned_trays = parseInt(res.data.active_trays) || 0;
                    let assigned_plants = parseInt(res.data.active_plants) || 0;
                    
                    let total_assigned_trays = parseInt(res.data.total_assigned_trays) || 0;
                    let total_assigned_plants = parseInt(res.data.total_assigned_plants) || 0;

                    vehicleCapacityTraysGlobal = cap_trays;
                    //vehicleCapacityPlantsGlobal = cap_plants;
                    
                    allocatableTraysGlobal = Math.max(0, cap_trays - assigned_trays);
                    allocatablePlantsGlobal = Math.max(0, cap_plants - assigned_plants);

                    //$('#v_cap_display').text(cap_trays + ' / ' + cap_plants);
                    $('#v_cap_display').text(cap_plants);
                    $('#v_assigned_display').text(total_assigned_trays + ' / ' + total_assigned_plants);
                    
                    // Highlight remaining capacity
                    let remaining_trays = Math.max(0, cap_trays - total_assigned_trays);
                    let remaining_plants = Math.max(0, cap_plants - total_assigned_plants);
                    // $('#v_allocatable_display').text(remaining_trays + ' / ' + remaining_plants);
                    $('#v_allocatable_display').text(remaining_plants);
                    vehicleCapacityPlantsGlobal = remaining_plants;
                    
                    
                    $('#vehicle_capacity_container').removeClass('d-none').hide().slideDown(300);
                    
                    // Handle Today's Trips
                    let trips_html = '';
                    let activeTripId = '';
                    if (res.data.today_trips && res.data.today_trips.length > 0) {
                        res.data.today_trips.forEach(function(t) {
                            let statusClass = 'text-warning';
                            if (t.status === 'Delivered') statusClass = 'text-success';
                            else if (t.status === 'Cancelled') statusClass = 'text-danger';
                            
                            if ((t.status === 'Dispatched' || t.status === 'In Transit' || t.status === 'In-Transit') && !activeTripId) {
                                activeTripId = t.trip_id;
                            }
                            
                            trips_html += `<div class="mb-2 pb-2 border-bottom">
                                <div class="d-flex justify-content-between font-weight-bold">
                                    <span>Trip: ${t.trip_id} <span class="badge badge-light border">${t.time}</span></span>
                                    <span class="${statusClass}">${t.status}</span>
                                </div>
                                <div class="text-muted" style="font-size: 0.7rem;">
                                    Route: ${t.from} &rarr; ${t.to} <br>
                                    Qty: ${t.trays} Trays (${parseInt(t.plants).toLocaleString()} Plants) | Driver: ${t.driver_name}
                                </div>
                            </div>`;
                        });
                        $('#today_trips_list').html(trips_html);
                        $('#today_trips_container').removeClass('d-none').hide().slideDown(300);
                        
                        // Auto-select driver if assigned for today
                        if (res.data.today_driver_id) {
                            $('#driver_id').val(res.data.today_driver_id).trigger('change');
                        }
                    } else {
                        $('#today_trips_container').slideUp(300, function() {
                            $(this).addClass('d-none');
                        });
                    }
                    activeTripIdGlobal = activeTripId;

                    // Check warnings
                    checkCapacityWarning();
                } else if (res.status == 'Plants are currently loading') {
                    alert(res.message);
                     $("#vehicle_id").val('').trigger('change');

                }else {
                    console.log('Freight lookup skip or no result:', res.message);
                }
            }, error: function (xhr, textStatus, errorThrown) {
                console.error('AJAX request failed');
                console.error('HTTP status:', xhr.status);
                console.error('Status text:', xhr.statusText);
                console.error('jQuery status:', textStatus);
                console.error('Error thrown:', errorThrown);
                console.error('Raw PHP response:', xhr.responseText);

                let message = 'Unable to load freight details.';

                if (textStatus === 'parsererror') {
                    message =
                        'PHP returned invalid JSON. Check the browser console ' +
                        'for the raw PHP response.';
                } else if (xhr.status === 404) {
                    message = 'PHP file not found: ajax/handle_pso.php';
                } else if (xhr.status === 500) {
                    message =
                        'Internal PHP server error. Check PHP error log.';
                } else if (xhr.status === 0) {
                    message =
                        'Request was blocked or the server is unavailable.';
                }

                alert(message);
            },

            complete: function (xhr, textStatus) {
                console.log('AJAX completed with status:', textStatus);
            }
        });
         
    }

    function checkCapacityWarning() { 
        let totalLoadedTrays = 0;
        let totalLoadedPlants = 0; 
        $('.loading-trays-input').each(function() {
            totalLoadedTrays += parseInt($(this).val()) || 0;
        });
        $('.loading-plants-input').each(function() {
            totalLoadedPlants += parseInt($(this).val()) || 0;
        });
        
        // Update total loading display in verification card footer
        $('#total_loading_trays').text(totalLoadedTrays);
        $('#total_loading_plants').text(totalLoadedPlants.toLocaleString());

        let plantCapacity = parseFloat($('#v_cap_display').text()) || 0;
        let remainingPlants = plantCapacity - totalLoadedPlants;

        $('#v_assigned_display').text(totalLoadedTrays + ' / ' + totalLoadedPlants);
        $('#v_allocatable_display').text(remainingPlants);
        
        let vehicle_selected = $('#vehicle_id').val();
        if (!vehicle_selected) {
            $('#v_capacity_warning').addClass('d-none');
            $('#btn_submit_assignment').prop('disabled', false);
            return;
        }

        let exceedsTrays = (vehicleCapacityTraysGlobal > 0 && totalLoadedTrays > allocatableTraysGlobal);
        /*let exceedsPlants = (vehicleCapacityPlantsGlobal > 0 && totalLoadedPlants > allocatablePlantsGlobal);*/

        let exceedsPlants = (vehicleCapacityPlantsGlobal > 0 && totalLoadedPlants > vehicleCapacityPlantsGlobal);

        if (exceedsTrays || exceedsPlants) {
            $('#v_capacity_warning').removeClass('d-none');
            $('#v_allocatable_display').removeClass('text-success').addClass('text-danger');
            $('#btn_submit_assignment').prop('disabled', true);
        } else {
            $('#v_capacity_warning').addClass('d-none');
            $('#v_allocatable_display').removeClass('text-danger').addClass('text-success');
            $('#btn_submit_assignment').prop('disabled', false);
        } 
                    
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

        var sord  = "<?php echo $sord; ?>"; 
        $.ajax({
            url:   'ajax/handle_pso.php',
            type: 'POST',
            dataType: 'json',

            data: {
                action: 'fetchingqrcodedata',
                qrcode: barcode,
                companyid: companyid,
                subcompanyid: subcompanyid,
                projectid: projectid,
                locationid: sourceLocationId,
                stockorder :sord
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


    /**
     * Normalize PHP response field names.
     */
    /*function normalizeScannedItem(row, barcode) {
        const trayCount = parseInt(
            row.tray_count ??
            row.trays ??
            row.total_trays ??
            1,
            10
        ) || 1;

        const qtyPerTray = parseFloat(
            row.qty_per_tray ??
            row.qtypertray ??
            0
        ) || 0;

        const plantCount = parseInt(
            row.plant_count ??
            row.qty ??
            row.total_plants ??
            row.qtypertray ??
            0,
            10
        ) || Math.round(trayCount * qtyPerTray);

        return {
            barcode: barcode,

            stockorder_no:
                String(row.stockorder_no || ''),

            productid:
                String(row.productid || ''),

            product_name:
                String(
                    row.product_name ||
                    row.productname ||
                    'Unknown Product'
                ),

            roottype:
                String(row.roottype || ''),

            locationid:
                String(
                    row.locationid ||
                    row.LocationId ||
                    ''
                ),

            polyhouseid:
                String(row.polyhouseid || ''),

            polyhousename:
                String(
                    row.polyhousename ||
                    row.polyhouse_name ||
                    ''
                ),

            sowinglotno_internal:
                String(
                    row.sowinglotno_internal || ''
                ),

            sowingdate_internal:
                String(
                    row.sowingdate_internal ||
                    row.sowingdate ||
                    ''
                ),

            qty_per_tray: qtyPerTray,
            trays: trayCount,
            qty: plantCount,

            barcodes: [barcode]
        };
    }*/

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
/*
    function getScannedLoadingItemsForSave() {
        const items = [];

        $('#items_container .scanned-item-row')
            .each(function () {
                const $row = $(this);
                const itemKey =
                    $row.data('item-key');

                const storedItem =
                    scannedLoadingItems[itemKey];

                if (!storedItem) {
                    return;
                }

                const loadingTrays =
                    parseInt(
                        $row
                            .find(
                                '.loading-trays-input'
                            )
                            .val(),
                        10
                    ) || 0;

                const loadingPlants =
                    parseInt(
                        $row
                            .find(
                                '.loading-plants-input'
                            )
                            .val(),
                        10
                    ) || 0;

                if (loadingTrays <= 0) {
                    return;
                }

                items.push({
                    stockorder_no:
                        storedItem.stockorder_no,

                    productid:
                        storedItem.productid,

                    roottype:
                        storedItem.roottype,

                    locationid:
                        storedItem.locationid,

                    polyhouseid:
                        storedItem.polyhouseid,

                    polyhousename:
                        storedItem.polyhousename,

                    sowinglotno_internal:
                        storedItem.sowinglotno_internal,

                    sowingdate_internal:
                        storedItem.sowingdate_internal,

                    qty_per_tray:
                        storedItem.qty_per_tray,

                    loading_trays:
                        loadingTrays,

                    loading_plants:
                        loadingPlants,

                    barcodes:
                        storedItem.barcodes
                });
            });

        return items;
    }*/

    function getScannedLoadingItemsForSave() {
    const items = [];

    $('#items_container .scanned-item-row').each(function () {
        const $row = $(this);

        const itemKey = String(
            $row.data('item-key') || ''
        );

        const storedItem = scannedLoadingItems[itemKey];

        if (!storedItem) {
            return;
        }

        const loadingTrays =
            parseInt(
                $row.find('.loading-trays-input').val(),
                10
            ) || 0;

        const loadingPlants =
            parseInt(
                $row.find('.loading-plants-input').val(),
                10
            ) || 0;

        if (loadingTrays <= 0) {
            return;
        }

        const barcodes = Array.isArray(storedItem.barcodes)
            ? storedItem.barcodes
            : [];

        if (barcodes.length === 0) {
            return;
        }

        barcodes.forEach(function (barcodeItem) {
            let barcodeNo = '';
            let barcodePlants = 0;

            /*
             * Supports both barcode formats:
             *
             * Object:
             * {
             *     barcode: "KRF000001",
             *     plants: 210
             * }
             *
             * String:
             * "KRF000001"
             */
            if (
                barcodeItem !== null &&
                typeof barcodeItem === 'object'
            ) {
                barcodeNo = String(
                    barcodeItem.barcode || ''
                ).trim();

                barcodePlants =
                    parseInt(
                        barcodeItem.plants,
                        10
                    ) || 0;
            } else {
                barcodeNo = String(
                    barcodeItem || ''
                ).trim();

                barcodePlants =
                    parseInt(
                        storedItem.qty_per_tray,
                        10
                    ) || 0;
            }

            if (!barcodeNo) {
                return;
            }

            /*
             * One barcode represents one tray.
             */
            items.push({
                stockorder_no:
                    String(
                        storedItem.stockorder_no || ''
                    ),

                productid:
                    String(
                        storedItem.productid || ''
                    ),

                product_name:
                    String(
                        storedItem.product_name || ''
                    ),

                roottype:
                    String(
                        storedItem.roottype || ''
                    ),

                locationid:
                    String(
                        storedItem.locationid || ''
                    ),

                polyhouseid:
                    String(
                        storedItem.polyhouseid || ''
                    ),

                polyhousename:
                    String(
                        storedItem.polyhousename || ''
                    ),

                sowinglotno_internal:
                    String(
                        storedItem.sowinglotno_internal || ''
                    ),

                sowingdate_internal:
                    String(
                        storedItem.sowingdate_internal || ''
                    ),

                qty_per_tray:
                    parseFloat(
                        storedItem.qty_per_tray
                    ) || 0,

                loading_trays: 1,

                loading_plants:
                    barcodePlants > 0
                        ? barcodePlants
                        : (
                            parseInt(
                                storedItem.qty_per_tray,
                                10
                            ) || 0
                        ),

                barcode_no: barcodeNo,

                despatch_location_id:
                    storedItem.despatch_location_id,

                despatch_location_name:
                    storedItem.despatch_location_name
            });
        });
    });

    return items;
}
function submitAssignmentForm(formData) {
    $.ajax({
        url: 'ajax/handle_assignment.php',
        type: 'POST',
        data: formData,
        dataType: 'json',

        // Required when sending FormData
        processData: false,
        contentType: false,

        beforeSend: function () {
            $('#finalsave_loading')
                .prop('disabled', true)
                .html(
                    '<i class="fa fa-spinner fa-spin me-1"></i> Saving...'
                );
        },

        success: function (res) {
            console.log('Save response:', res);

            if (res.status === 'success') {
                Swal.fire(
                    'Success!',
                    'Trip generated: ' + res.trip_id,
                    'success'
                ).then(function () {
                    window.location.href =
                        'dispatch_docs.php?k=<?php echo $key; ?>' +
                        '&trip=' + encodeURIComponent(res.trip_id) +
                        '<?php echo (isset($_GET['iframe']) && $_GET['iframe'] == 'true') ? '&iframe=true' : ''; ?>';
                });

                return;
            }

            Swal.fire(
                'Error',
                res.message || 'Unable to generate trip.',
                'error'
            );
        },

        error: function (xhr, status, error) {
            console.error('AJAX status:', status);
            console.error('AJAX error:', error);
            console.error('PHP response:', xhr.responseText);

            let message = 'Unable to save trip details.';

            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            } else if (xhr.responseText) {
                message = xhr.responseText.substring(0, 500);
            }

            Swal.fire(
                'Error',
                message,
                'error'
            );
        },

        complete: function () {
            $('#finalsave_loading')
                .prop('disabled', false)
                .html(
                    '<i class="fa fa-check-circle me-1"></i> Generate Trip'
                );
        }
    });
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

function deleteScannedBarcode(itemKey, barcode) {
    const item = scannedLoadingItems[itemKey];

    if (
        !item ||
        !Array.isArray(item.barcodes)
    ) {
        return;
    }

    const barcodeIndex = item.barcodes.findIndex(function (barcodeItem) {
        const barcodeValue =
            typeof barcodeItem === 'object'
                ? barcodeItem.barcode
                : barcodeItem;

        return String(barcodeValue) === String(barcode);
    });

    if (barcodeIndex === -1) {
        Swal.fire({
            icon: 'error',
            title: 'Barcode Not Found',
            text: 'The selected barcode is no longer available.'
        });

        return;
    }

    const removedItem = item.barcodes[barcodeIndex];

    const removedBarcode =
        typeof removedItem === 'object'
            ? removedItem.barcode
            : removedItem;

    const removedPlants =
        typeof removedItem === 'object'
            ? parseInt(removedItem.plants, 10) || 0
            : parseInt(item.qty_per_tray, 10) || 0;

    // Remove barcode from grouped item
    item.barcodes.splice(barcodeIndex, 1);

    // Allow barcode to be scanned again
    scannedBarcodes = scannedBarcodes.filter(function (value) {
        return String(value) !== String(removedBarcode);
    });

    // Reduce tray and plant quantities
    item.trays = Math.max(
        0,
        (parseInt(item.trays, 10) || 0) - 1
    );

    item.qty = Math.max(
        0,
        (parseInt(item.qty, 10) || 0) - removedPlants
    );

    if (item.barcodes.length === 0 || item.trays <= 0) {
        delete scannedLoadingItems[itemKey];

        $('#loading_item_' + createSafeDomId(itemKey)).remove();

        $('#barcodeDetailsModal').modal('hide');
    } else {
        updateExistingLoadingRow(itemKey, item);
        renderBarcodeDetailsTable(itemKey);
    }

    updateLoadingSummary();

    Swal.fire({
        icon: 'success',
        title: 'Barcode Deleted',
        text: 'The scanned tray has been removed successfully.',
        timer: 1300,
        showConfirmButton: false
    });
}

function fillExistingTripHeader(tripHeader) { 
    if (
        !tripHeader ||
        Object.keys(tripHeader).length === 0
    ) {
        return;
    }
    $('#from_location_id').val(tripHeader[0]['dispatched_from_location_id'] ).prop("disabled",true);

    $('#to_location_id').val(tripHeader[0]['lastlocation_id']) ;
    $('#vehicle_id').val(tripHeader[0].active_vehicle_id || '').trigger('change');


    $('#hidden_trip_id').val(
        tripHeader[0]['active_vehicle_id'] || ''
    );

    $('[name="dispatch_time"]').val(
        
            tripHeader[0]['dispatch_time']
    );

    $('[name="remarks"]').val(
        tripHeader[0]['send_remark']   ||
        ''
    );

   let freightAmt = parseFloat(tripHeader[0]['freight_amount']) || 0;
    let freightDis = parseFloat(tripHeader[0]['freight_discountpercent']) || 0;
    let payableamt = parseFloat(tripHeader[0]['payable_freightamount']) || 0;


                         

    $('#freight_amount').val(freightAmt).data('freightamt', freightAmt);
    $('#freight_discountpercent').val(freightDis).data('freightdis', freightDis);
    $('#payable_freightamount').val(payableamt).data('payableamt', payableamt);
   

    $('#purpose_to_move').val(tripHeader[0]['purpose_to_move']);
    $('#driver_id').val(tripHeader[0]['driverid']);

    const distance =
        parseFloat(
            tripHeader[0]['distance_km']
        ) || 0;

    $('#distance_helper').text(
        distance + ' km'
    );

    $('#distance_km').val(distance);
    loadExistingTripItems(tripHeader);
}

function loadExistingTripItems(existingTrip) {
    /*
     * Clear previous data before loading edit information.
     */
    scannedBarcodes = [];
    scannedLoadingItems = {};

    $('#items_container .scanned-item-row').remove();

    if (
        !Array.isArray(existingTrip) ||
        existingTrip.length === 0
    ) {
        return;
    }

    existingTrip.forEach(function (row) {
        const barcode = String(
            row.barcode || 
            ''
        ).trim();

        if (!barcode) {
            return;
        }

        /*
         * Avoid repeated barcode rows from the database.
         */
        if (scannedBarcodes.includes(barcode)) {
            return;
        }

        scannedBarcodes.push(barcode);

        const dispatchLocationId = String(
            row.depatchlocationid ||  ''
        );

        const dispatchLocationName = String( 
            row.despatchlocationname ||
            ''
        );

        const item = normalizeExistingTripItem(
            row,
            barcode,
            dispatchLocationId,
            dispatchLocationName
        );

        addOrUpdateLoadingItem(item);
    });

    checkCapacityWarning();
}

 function normalizeExistingTripItem(
    row,
    barcode,
    dispatchLocationId,
    dispatchLocationName
) {
    const qtyPerTray =
        parseFloat(
            row.qty_per_tray ??
            row.qtypertray ??
            row.send_totalplantcount ??
            0
        ) || 0;

    const plantCount =
        parseInt(
            row.loading_plants ??
            row.send_totalplantcount ??
            row.qty ??
            row.qtypertray ??
            qtyPerTray ??
            0,
            10
        ) || 0;

    return {
        barcode: String(barcode || ''),

        stockorder_no: String(
            row.stockorder_no || ''
        ),

        productid: String(
            row.productid ||
            row.product_id ||
            ''
        ),

        product_name: String(
            row.product_name ||
            row.productname ||
            'Unknown Product'
        ),

        roottype: String(
            row.roottype || ''
        ),

        locationid: String(
            row.dispatched_from_location_id ||
            row.from_location_id ||
            row.LocationId ||
            row.locationid ||
            ''
        ),

        polyhouseid: String(
            row.polyhouseid || ''
        ),

        polyhousename: String(
            row.polyhousename ||
            row.polyhouse_name ||
            ''
        ),

        sowinglotno_internal: String(
            row.sowinglotno_internal || ''
        ),

        sowingdate_internal: String(
            row.sowingdate_internal ||
            row.sowingdate ||
            ''
        ),

        qty_per_tray: qtyPerTray,

        trays: 1,

        qty: plantCount,

        despatch_location_id: String(
            dispatchLocationId || ''
        ),

        despatch_location_name: String(
            dispatchLocationName || ''
        ),

        barcodes: [
            {
                barcode: String(barcode || ''),
                plants: plantCount
            }
        ]
    };
}
</script>
