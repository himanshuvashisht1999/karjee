<?php
include('../header.php');
include('functions/product_functions.php');
include('functions/vendor_functions.php');
include('functions/seed_functions.php');
date_default_timezone_set("Asia/Calcutta");
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
        --primary-blue: #0d6efd;
        --secondary-blue: #6ea8fe;
        --primary-green: #1b9e77;
        --secondary-green: #4ae0b1;
        --accent-orange: #fda085;
        --glass-bg: rgba(255, 255, 255, 0.9);
        --glass-border: rgba(255, 255, 255, 0.2);
        --card-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.08);
        --text-dark: #1e293b;
        --text-muted: #64748b;
    }

    body {
        font-family: 'Outfit', sans-serif;
        background-color: #f0f4f8;
        color: var(--text-dark);
    }

    .premium-container {
        padding: 1rem;
        max-width: 1400px;
        margin: auto;
        padding-bottom: 120px;
    }

    /* --- Page Header --- */
    .page-header-card {
        background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
        border-radius: 20px;
        padding: 0.75rem 1.5rem;
        color: white;
        margin-bottom: 1.5rem;
        box-shadow: 0 10px 25px rgba(13, 110, 253, 0.2);
        display: flex;
        justify-content: space-between;
        align-items: center;
        border: none;
    }

    .page-header-card h5 {
        font-weight: 700;
        margin: 0;
        letter-spacing: -0.5px;
        text-transform: uppercase;
        font-size: 1.15rem;
    }

    .btn-refresh {
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 12px;
        font-weight: 500;
        font-size: 0.9rem;
        transition: all 0.3s ease;
    }

    .btn-refresh:hover {
        background: white;
        color: var(--primary-blue);
        transform: translateY(-2px);
    }

    /* --- Content Cards --- */
    .content-card {
        background: var(--glass-bg);
        backdrop-filter: blur(12px);
        border: 1px solid var(--glass-border);
        border-radius: 24px;
        padding: 1.5rem;
        box-shadow: var(--card-shadow);
        margin-bottom: 2.5rem !important;
    }

    .card-title-premium {
        font-size: 1rem;
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 1.25rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .filter-label {
        font-size: 0.7rem;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        margin-bottom: 0.5rem;
        display: block;
    }

    /* --- Scanner Interface --- */
    .scanner-wrapper {
        background: #f8fafc;
        border-radius: 24px;
        padding: 1.5rem;
        border: 2px dashed #cbd5e1;
        text-align: center;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
    }

    .scanner-active {
        border-color: var(--primary-blue);
        background: #f0f7ff;
    }

    #reader {
        width: 100%;
        max-width: 300px;
        margin: 0 auto;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        border: none !important;
    }

    /* --- Table Design --- */
    .premium-table-container {
        border-radius: 20px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        background: white;
    }

    .scanned-boxes-table {
        width: 100%;
        border-collapse: collapse;
    }

    .scanned-boxes-table th {
        background: #f8fafc;
        padding: 12px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--text-muted);
        text-align: left;
        border-bottom: 2px solid #e2e8f0;
    }

    .scanned-boxes-table td {
        padding: 12px;
        font-size: 0.85rem;
        border-bottom: 1px solid #f1f5f9;
    }

    .total-row {
        background: #f0f7ff;
        font-weight: 700;
    }

    /* Info grid */
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }

    .info-item {
        background: #f8fafc;
        padding: 0.75rem 1rem;
        border-radius: 12px;
    }

    .info-label {
        font-size: 0.7rem;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        margin-bottom: 0.25rem;
    }

    .info-value {
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--text-dark);
    }

    /* --- Modal Redesign --- */
    .modal-content.premium-modal {
        border-radius: 24px !important;
        border: none !important;
        padding: 0.75rem !important;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15) !important;
    }

    .modal-header.no-border {
        border: none !important;
        padding: 0.5rem 1rem 0 !important;
    }

    .modal-title-custom {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 700;
        font-size: 1.1rem;
        color: #1e293b;
    }

    .modal-title-custom i {
        font-size: 1.25rem;
        color: #0d6efd;
    }

    .btn-close-custom {
        background-color: #f1f5f9 !important;
        border: none !important;
        width: 32px !important;
        height: 32px !important;
        border-radius: 50% !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 1rem !important;
        color: #64748b !important;
        transition: all 0.2s ease !important;
        position: absolute !important;
        right: 15px !important;
        top: 15px !important;
        z-index: 1060 !important;
    }

    .btn-close-custom:hover {
        opacity: 1 !important;
        transform: scale(1.1) rotate(90deg);
    }

    .modal-subtitle {
        color: #64748b;
        font-size: 0.85rem;
        margin-bottom: 1rem;
        margin-left: 2.2rem;
    }

    /* Custom Checkbox Styling (Multiple Selection) */
    .box-select-check-custom {
        width: 18px !important;
        height: 18px !important;
        border: 2px solid #3b82f6 !important;
        margin-right: 12px !important;
        cursor: pointer !important;
        appearance: none !important;
        border-radius: 6px !important;
        position: relative !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        background: white !important;
    }

    .box-select-check-custom:checked {
        background-color: #3b82f6 !important;
        border-color: #3b82f6 !important;
    }

    .box-select-check-custom:checked::after {
        content: "\F26E" !important;
        /* Bootstrap Bi-Check */
        font-family: "bootstrap-icons" !important;
        position: absolute !important;
        top: 50% !important;
        left: 50% !important;
        transform: translate(-50%, -50%) !important;
        color: white !important;
        font-size: 11px !important;
    }

    .box-select-check-custom:active {
        transform: scale(0.9);
    }

    .confirm-btn-premium {
        background: #0d6efd !important;
        color: white !important;
        border: none !important;
        border-radius: 10px !important;
        padding: 0.6rem 1.2rem !important;
        font-weight: 700 !important;
        font-size: 0.85rem !important;
        transition: all 0.3s ease !important;
        box-shadow: 0 4px 10px rgba(13, 110, 253, 0.15) !important;
    }

    .confirm-btn-premium:hover {
        background: #0b5ed7 !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 8px 20px rgba(13, 110, 253, 0.3) !important;
    }

    .position-radio-group {
        display: flex;
        background: #f1f5f9;
        padding: 4px;
        border-radius: 12px;
        gap: 4px;
    }

    .position-radio-item {
        flex: 1;
    }

    .position-radio-item input {
        display: none;
    }

    .position-radio-item label {
        display: block;
        text-align: center;
        padding: 6px 4px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.75rem;
        color: #64748b;
        transition: all 0.2s ease;
        margin-bottom: 0;
    }

    .position-radio-item input#posStart:checked+label {
        background: #ffc107 !important;
        /* Yellow */
        color: #000 !important;
        box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
    }

    .position-radio-item input#posMiddle:checked+label {
        background: #0d6efd !important;
        /* Blue */
        color: #fff !important;
        box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
    }

    .position-radio-item input#posEnd:checked+label {
        background: #198754 !important;
        /* Green (Default) */
        color: #fff !important;
        box-shadow: 0 4px 12px rgba(25, 135, 84, 0.3);
    }

    .tray-pills-badge {
        background: #0d6efd !important;
        color: white !important;
        border-radius: 50px !important;
        padding: 5px 15px !important;
        font-weight: 700 !important;
        font-size: 0.8rem !important;
    }

    .hover-bg {
        transition: background 0.2s ease;
    }

    .hover-bg:hover {
        background: #f8fafc !important;
    }
    /* --- Scanner Overlay & Animations (Ported from germinationbox_receivedbygroom.php) --- */
    .reader-container {
        position: relative;
        width: 100%;
        max-width: 320px;
        margin: 0 auto;
        border-radius: 14px;
        overflow: hidden;
        background: #000;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
    }

    #reader video {
        border-radius: 14px;
        object-fit: cover;
    }

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
        width: 240px;
        height: 240px;
        border: 2px solid rgba(255, 255, 255, 0.4);
        border-radius: 20px;
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
        width: 25px;
        height: 25px;
        border: 4px solid var(--primary-blue);
        z-index: 11;
    }

    .corner-tl { top: 0; left: 0; border-right: none; border-bottom: none; border-top-left-radius: 8px; }
    .corner-tr { top: 0; right: 0; border-left: none; border-bottom: none; border-top-right-radius: 8px; }
    .corner-bl { bottom: 0; left: 0; border-right: none; border-top: none; border-bottom-left-radius: 8px; }
    .corner-br { bottom: 0; right: 0; border-left: none; border-top: none; border-bottom-right-radius: 8px; }

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

<div class="premium-container">
    <div id="printparameterdiv"></div>

    <!-- Page Header -->
    <div class="page-header-card shadow-lg">
        <div class="d-flex align-items-center gap-3">
            <div class="header-icon-circle bg-white text-primary d-flex align-items-center justify-content-center rounded-circle"
                style="width: 40px; height: 40px; font-size: 1.25rem;">
                <i class="bi bi-box-arrow-in-down"></i>
            </div>
            <h5><?php echo _t('Grafted Tray Received at Polyhouse'); ?></h5>
        </div>
        <button class="btn btn-refresh" onclick="window.location.reload();">
            <i class="fa fa-sync me-2"></i> <?php echo _t('refresh'); ?>
        </button>
    </div>

    <div class="row g-4">
        <!-- Scanner Section -->
        <div class="col-lg-12">
            <div class="content-card h-100">
                <div class="card-title-premium">
                    <i class="bi bi-qr-code-scan text-primary"></i>
                    Tray Verification
                </div>

                <div id="qrcodescannerdiv" class="scanner-wrapper scanner-not-captured">
                    <div class="reader-container mb-3">
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
                    <div id="cameraSelectDiv" class="mt-2 px-2 mx-auto" style="max-width: 320px; display: none;">
                        <label class="filter-label text-start mb-1" style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted);">
                            <i class="bi bi-camera-fill me-1 text-primary"></i> Choose Camera
                        </label>
                        <select id="cameraSelection" class="form-select border-primary-subtle shadow-sm" style="border-radius: 10px; height: 38px; font-size: 0.85rem; font-weight: 500;"></select>
                    </div>

                    <!-- Sleek Real-Time Scanner Status Message -->
                    <div id="scannerStatusDiv" class="mt-2 text-center" style="display:none;">
                        <span id="scannerStatus" class="badge bg-light text-secondary shadow-sm border py-2 px-3" style="border-radius: 20px; font-size: 0.85rem; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
                            <i class="bi bi-broadcast text-danger pulse"></i><span>Scanner Active: Looking for QR Code...</span>
                        </span>
                    </div>

                    <div class="mt-3 d-flex justify-content-center gap-2">
                        <button id="startScan" class="btn btn-primary px-4 py-2"
                            style="border-radius: 12px;"><?php echo _t('start'); ?></button>
                        <button id="stopScan" class="btn btn-danger px-4 py-2"
                            style="display:none; border-radius: 12px;"><?php echo _t('stop'); ?></button>
                        <button id="toggleFlash" class="btn btn-warning px-4 py-2" style="border-radius: 12px;"><i
                                class="bi bi-lightning-fill"></i></button>
                        <button id="ManualStartScan" class="btn btn-outline-secondary px-4 py-2"
                            style="border-radius: 12px;"><?php echo _t('manual'); ?></button>
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
                        <button id="fetchManual" class="btn btn-primary w-100 py-3 fw-700"
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

        <!-- Scanned Batch Summary -->
        <div class="col-lg-12">
            <div id="BoxDetailsInfo" class="content-card h-100">
                <div class="card-title-premium border-bottom pb-2 mb-3">
                    <i class="bi bi-list-check text-primary"></i> Scanned Boxes Details
                </div>

                <div id="scannedBatchSummary">
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-qr-code-scan d-block mb-2" style="font-size: 2rem;"></i>
                        No trays scanned yet.
                    </div>
                </div>

                <span id="totalTraysSum" class="d-none">0</span>

                <div id="batchActionDiv" class="mt-4" >
                    <div class="d-grid gap-2 mb-3 d-none">
                        <button class="btn btn-outline-info py-2 fw-600 shadow-sm" style="border-radius:12px;"
                            id="viewCapacityBtn">
                            <i class="bi bi-building-check me-2"></i> SELECT TARGET & VIEW CAPACITY
                        </button>
                    </div>

                    <!-- Target Selection & Capacity View -->
                    <div id="polyhouseCapacitySection" class="bg-light p-3 rounded-4 mb-3" style="display:none;">
                        <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                            <i class="bi bi-geo-alt text-primary"></i> Target Polyhouse Selection
                        </h6>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="info-label">Polyhouse Location</label>
                                <select id="targetLocationList" class="form-select border-primary-subtle"
                                    style="border-radius:10px;"></select>
                            </div>
                            <div class="col-md-6">
                                <label class="info-label">Target Polyhouse</label>
                                <select id="targetPolyhouseList" class="form-select border-primary-subtle"
                                    style="border-radius:10px;">
                                    <option value="">Select Polyhouse</option>
                                </select>
                            </div>
                        </div>

                        <div id="capacityGridDiv" class="mt-3 table-responsive" style="max-height: 200px;">
                            <!-- Bed wise table will be injected here -->
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <!-- <button class="btn btn-success py-3 fw-700 shadow-sm" style="border-radius:16px;"
                            id="submitVerificationBatch">
                            <i class="bi bi-cloud-upload me-2"></i> UPDATE RECEIVED STATUS FOR ALL
                        </button> -->
                        <!-- <button class="btn btn-outline-danger py-2" style="border-radius:12px;" id="clearScannedBoxes">
                            <i class="bi bi-trash me-2"></i> Clear List
                        </button> -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: View Bed Details -->
<div class="modal fade" id="viewBedDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content premium-modal">
            <button type="button" class="btn-close-custom" data-bs-dismiss="modal" aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
            <div class="modal-header no-border">
                <h5 class="modal-title-custom">
                    <i class="bi bi-info-circle"></i>
                    Bed Details: <span id="modalBedNameLbl"></span>
                </h5>
            </div>
            <div class="modal-body">
                <div id="bedDetailsContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-info" role="status"></div>
                        <p class="mt-2 text-muted">Loading plant details...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Allocate to Bed -->
<div class="modal fade" id="allocateToBedModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content premium-modal">
            <button type="button" class="btn-close-custom" data-bs-dismiss="modal" aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
            <div class="modal-header no-border">
                <h5 class="modal-title-custom">
                    <i class="bi bi-plus-circle"></i>
                    Allocate to <span id="allocBedNameLbl"></span>
                </h5>
            </div>
            <div class="modal-body">
                <p class="modal-subtitle">Select Trays from your scanned list to allocate to this bed <span  class ="text-primary fw-bold" id ="reserverallocatetray"> </span>.</p>

               <div  class="mb-3" > 
                 <label class="info-label mb-2 ms-1 ">No Of Tray to Be Allocated <span class="text-danger">*</span></label>
                 <input type="text"  name="bed_qty" id="bed_qty"  maxlength="5"  inputmode="numeric"  autocomplete="off" >

                </div> 

                <div class="mb-4">
                    <label class="info-label mb-2 ms-1">Position in Bed <span class="text-danger">*</span></label>
                    <div class="position-radio-group">
                        <div class="position-radio-item">
                            <input type="radio" name="trayPosition" id="posStart" value="Start">
                            <label for="posStart">Start</label>
                        </div>
                        <div class="position-radio-item">
                            <input type="radio" name="trayPosition" id="posMiddle" value="Middle">
                            <label for="posMiddle">Middle</label>
                        </div>
                        <div class="position-radio-item">
                            <input type="radio" name="trayPosition" id="posEnd" value="End">
                            <label for="posEnd">End</label>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-center">
                    <button class="btn confirm-btn-premium w-100" id="confirmBedAllocation">
                        CONFIRM ALLOCATION
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('../footer.php'); ?>
<script type="text/javascript" src="<?php echo $prefix; ?>krf-orderandsales/js/pjs_function.js"></script>

<script>
    let qrReader = null;
    let isScanning = false;
    let torchOn = false;
    let scannedBoxes = [];
    let lastScannedCode = null;
    let scanLockTimeout = null;
    let activeBedNo = null;
    let activePhId = null;
    let activeBedRemaining = 0;
    let isCameraListLoaded = false;
    let globalAudioCtx = null;
    let finalallocatedtray=0;

    $(document).ready(function () { 
        const projectid = $("body").data("projectid");

        // Explicit Close Handler for Custom Close Buttons
        $(document).on("click", ".btn-close-custom", function () {
            $(this).closest(".modal").modal("hide");
        });

        // LoadProductionLocations(projectid, '', 'targetLocationList'); // For capacity check
        $("#bed_qty").on("input", function () {
                this.value = this.value
                    .replace(/[^0-9]/g, '') // only integers
                    .slice(0, 5);           // maximum 5 digits
            });
  
        $("#viewCapacityBtn").on("click", function () {
            $("#polyhouseCapacitySection").slideToggle();
        });

        $(document).on("change", "#targetLocationList", function () {
            const locId = $(this).val();
            activeBedNo = null;
            activePhId = null;
            activeBedRemaining = 0;

            if (!locId) {
                $("#targetPolyhouseList").html('<option value="">Select Polyhouse</option>');
                $("#capacityGridDiv").empty();
                return;
            }

            const initialLocationId = String($(this).data("initial-locationid") || "");
            const initialPolyhouseId = String($(this).data("initial-polyhouseid") || "");
            const polyid = String(locId) === initialLocationId
                ? initialPolyhouseId
                : "";

            GetPolihouseDetailsList('locationid', locId, polyid, 'targetPolyhouseList');
        });

        $(document).on("change", "#targetPolyhouseList", function () {
            const phId = $(this).val();
            const locId = $("#targetLocationList").val();
            activeBedNo = null;
            activePhId = null;
            activeBedRemaining = 0;

            if (!phId) {
                $("#capacityGridDiv").empty();
                return;
            }
            fetchPolyhouseCapacity(phId, locId);
        });

        function fetchPolyhouseCapacity(phId, locId) {
            const routurl = $("body").data("routurl");
            const projectid = $("body").data("projectid");

            $.ajax({
                url: routurl + "krf-orderandsales/modal/polihouse_modal.php?action=PolyhouseCapacitydetails",
                type: "POST",
                data: { projectid, locationid: locId, polyhouseid: phId },
                dataType: "json",
                success: function (res) {
                    if (!res || !res.bed_wise_details) return;

                    let html = `<table class="table table-sm table-bordered mt-2" style="font-size:0.8rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Bed Name/No</th>
                                <th>Trays Capacity</th>
                                <th>Trays Exist in Bed</th>
                                <th>Available Tray Space</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>`;

                    res.bed_wise_details.forEach(bed => {
                        let productInfo = "";
                        if (bed.products && bed.products.length > 0) {
                            productInfo = `<div class="mt-1 border-top pt-1" style="font-size:0.7rem; line-height:1.2;">`;
                            bed.products.forEach(p => {
                                productInfo += `<div class="text-primary-emphasis opacity-75 font-italic">${p.productname}: <b>${p.count}</b></div>`;
                            });
                            productInfo += `</div>`;
                        }

                        html += `<tr>
                            <td>
                                <div class="fw-bold">${bed.bed_no}</div>
                                ${productInfo}
                            </td>
                            <td>${bed.total_capacity}</td>
                            <td class="text-danger">${bed.used_trays}</td>
                            <td class="text-success fw-bold">${bed.remaining_trays}</td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <button class="btn btn-xs btn-primary allocate-to-bed-btn" title="Allocate to this bed"
                                        data-phid="${phId}" data-bedno="${bed.bed_no}" data-rem="${bed.remaining_trays}">
                                        <i class="bi bi-plus-circle"></i>
                                    </button>
                                    <button class="btn btn-xs btn-info view-bed-details-btn" title="View bed details"
                                        data-phid="${phId}" data-bedno="${bed.bed_no}">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>`;
                    });

                    html += `</tbody><tfoot>
                        <tr class="table-info fw-bold">
                            <td>TOTAL</td>
                            <td>${res.total_tray_capacity}</td>
                            <td class="text-danger">${res.total_tray_exists}</td>
                            <td class="text-success">${res.remaining_total_tray}</td>
                            <td></td>
                        </tr>
                    </tfoot></table>`;
                    $("#capacityGridDiv").html(html);
                }
            });
        }

        /* --- Bed Action Handlers --- */
        $(document).on("click", ".view-bed-details-btn", function () {
            const phId = $(this).data("phid");
            const bedNo = $(this).data("bedno");
            const routurl = $("body").data("routurl");
            const projectid = $("body").data("projectid");

            $("#modalBedNameLbl").text(bedNo);
            $("#viewBedDetailsModal").modal("show");
            $("#bedDetailsContent").html('<div class="text-center"><div class="spinner-border text-info"></div></div>');

            $.ajax({
                url: routurl + "krf-orderandsales/modal/polihouse_modal.php?action=GetBedWisePlantDetails",
                type: "POST",
                data: { projectid, polyhouseid: phId, bed_no: bedNo },
                dataType: "json",
                success: function (res) {
                    if (res.status === "success" && res.data.length > 0) {
                        let table = `<table class="table table-sm table-hover">
                            <thead><tr><th>Stock Order</th><th>Product</th><th>Trays</th><th>Date</th></tr></thead><tbody>`;
                        res.data.forEach(d => {
                            let date = d.planted_date ? new Date(d.planted_date).toLocaleDateString() : '-';
                            table += `<tr><td>${d.stockorder_no}</td><td>${d.productname}</td><td>${d.tray_count}</td><td>${date}</td></tr>`;
                        });
                        table += `</tbody></table>`;
                        $("#bedDetailsContent").html(table);
                    } else {
                        $("#bedDetailsContent").html('<p class="text-center text-muted">No plant data found for this bed.</p>');
                    }
                }
            });
        });

        $(document).on("click", ".allocate-to-bed-btn", function () {
            if (scannedBoxes.length === 0) {
                iziToast.warning({ title: 'No Scanned Box', message: 'Please scan a box first.', position: 'topRight' });
                return;
            }

            activeBedNo = $(this).data("bedno");
            activePhId = $(this).data("phid");
            const remaining = $(this).data("rem");
            activeBedRemaining = Number(remaining || 0);
            const batch = scannedBoxes[0];
            const leftqty = Number(batch.total_trays || 0) - Number(finalallocatedtray);

            $("#reserverallocatetray").text(leftqty);
            $("#bed_qty").val(leftqty);

            $("#allocBedNameLbl").text(activeBedNo);
            $("input[name='trayPosition']").prop("checked", false); // Default not selected any button

            $("#allocateToBedModal").modal("show");
        });
 


        $("#confirmBedAllocation").on("click", async function () {
            const bedQty = Number($("#bed_qty").val() || 0);
debugger;
            const batch = scannedBoxes[0] || null;
            const totalQty = batch
                ? Number(batch.total_trays || 0)
                : 0;

            if (bedQty <= 0) {
                alert("Please enter Bed Allocation Qty");
                $("#bed_qty").focus();
                return false;
            }

            if (bedQty > totalQty) {
                alert(
                    "Bed Allocation Qty Can't Be greater than Total Available Tray Qty"
                );
                $("#bed_qty").focus();
                return false;
            }

            const trayPosition =
                $("input[name='trayPosition']:checked").val();

            if (!trayPosition) {
                iziToast.error({
                    title: 'Selection Missing',
                    message:
                        'Please select a Position (Start, Middle, or End). It is mandatory.',
                    position: 'topRight'
                });
                return false;
            }

            const targetLocationId = $("#targetLocationList").val();
            const targetPolyhouseId = $("#targetPolyhouseList").val();

            if (!targetLocationId || !targetPolyhouseId || !activeBedNo || !activePhId) {
                iziToast.error({
                    title: 'Target Missing',
                    message: 'Please select a target location, polyhouse and bed.',
                    position: 'topRight'
                });
                return false;
            }

            if (String(activePhId) !== String(targetPolyhouseId)) {
                iziToast.error({
                    title: 'Bed Selection Required',
                    message: 'Please select a bed from the currently selected polyhouse.',
                    position: 'topRight'
                });
                return false;
            }

            const warnings = [];

            if (
                String(targetLocationId) !== String(batch.locationid)  
            ) {
                warnings.push(
                    'The selected destination is different from the tray batch current location/polyhouse.'
                );
            }

            if (bedQty > activeBedRemaining) {
                warnings.push(
                    `Bed accommodation is ${activeBedRemaining} tray(s), but you entered ${bedQty}.`
                );
            }

            if (warnings.length > 0) {
                const warningResult = await Swal.fire({
                    title: 'Allocation Warning',
                    html: warnings.map(message => `<div class="text-start mb-2">• ${message}</div>`).join('') +
                          '<div class="text-start fw-bold mt-3">Do you still want to continue?</div>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Continue',
                    cancelButtonText: 'Review Selection'
                });

                if (!warningResult.isConfirmed) {
                    return false;
                }
            }

            const routurl = $("body").data("routurl");
            const projectid = $("body").data("projectid");
            const loginid = $("body").data("loginid");

            const fd = new FormData();

            fd.append("projectid", projectid);
            fd.append("loginid", loginid);
            fd.append("bedqty", bedQty);
            fd.append("polyhouseid", targetPolyhouseId); 
            fd.append("target_locationid", targetLocationId);
            fd.append("bedrackname", activeBedNo);
            fd.append("placeofrow", trayPosition);
            fd.append("gfailurestatus", "Healthy");
            fd.append("stockorder_no", batch.stockorder_no);
            fd.append("productid", batch.productid); 
            fd.append("Barcode", batch.scanned_barcode); 

            try {

                const response = await fetch(
                    "modal/receving.php?action=UpdateToPolyhouse",
                    {
                        method: 'POST',
                        body: fd
                    }
                );

                const res = await response.json();

                if (res.status === "success") {

                    iziToast.success({
                        title: 'Success',
                        message: res.message,
                        position: 'topRight'
                    });


                    batch.total_trays = Number(res.data.remaining_trays || 0);
                    batch.total_qty = Number(res.data.remaining_qty || 0);


                    // Reset quantity
                    $("#bed_qty").val("");


                    // Reset position
                    $("input[name='trayPosition']")
                        .prop("checked", false);


                    // Close allocation modal
                    $("#allocateToBedModal").modal("hide");


                    // If nothing remains
                    if (batch.total_trays === 0) {
                        scannedBoxes = [];
                    }


                    // Re-render remaining trays
                    renderScannedBoxes();


                    // Optional: refresh polyhouse capacity
                    if (activePhId) {

                        const locId =
                            $("#targetLocationList").val();

                        fetchPolyhouseCapacity(
                            activePhId,
                            locId
                        );
                    }
                } else {

                    Swal.fire(
                        'Error',
                        res.message,
                        'error'
                    );
                }

            } catch (err) {

                console.error(err);

                Swal.fire(
                    'Error',
                    'Failed to communicate with server',
                    'error'
                );
            }
        });

        /* --- Scanner Operations --- */
        // Pleasant high-quality audio beep using Web Audio API (highly reliable on all mobile browsers)
        function playBeep() {
            try {
                if (!globalAudioCtx) {
                    globalAudioCtx = new (window.AudioContext || window.webkitAudioContext)();
                }
                if (globalAudioCtx.state === 'suspended') {
                    globalAudioCtx.resume();
                }
                const osc = globalAudioCtx.createOscillator();
                const gain = globalAudioCtx.createGain();
                osc.connect(gain);
                gain.connect(globalAudioCtx.destination);
                osc.type = 'sine';
                osc.frequency.setValueAtTime(880, globalAudioCtx.currentTime); // A5 note (sweet, high pitched beep)
                gain.gain.setValueAtTime(0.1, globalAudioCtx.currentTime);
                osc.start();
                osc.stop(globalAudioCtx.currentTime + 0.12); // Clear sweet beep
            } catch (e) {
                console.warn("Audio feedback failed:", e);
            }
        }

        // Load available cameras for multi-lens selection support
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

                        // Heuristic fallback for back/rear camera
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
                $("#scannerStatus").html('<i class="bi bi-broadcast text-danger pulse"></i><span>Scanner Active: Looking for QR Code...</span>').removeClass('bg-success text-white bg-warning text-dark bg-danger text-white bg-info text-white').addClass('bg-light text-secondary');

                // Apply camera zoom & focus
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

                        // Auto zoom 1.8x to scan smaller stickers easily
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
                } catch (e) {
                    console.warn("Failed to apply track capabilities:", e);
                }

                loadCameras(activeTrackLabel);

            }).catch(err => {
                isScanning = false;
                console.error("Scanner Error:", err);
                if (cameraId) {
                    initScanner(null);
                } else {
                    Swal.fire('Camera Error', 'Could not access high-quality camera.', 'warning');
                }
            });
        }

        window.stopScanner = function () {
            if (qrReader && isScanning) {
                qrReader.stop().then(() => {
                    isScanning = false;
                    $("#startScan").show();
                    $("#stopScan").hide();
                    $("#qrcodescannerdiv").removeClass('scanner-active scanner-not-captured scanner-captured');
                    $("#scannerOverlay").hide();
                    $("#scannerStatusDiv").slideUp();
                }).catch(err => {
                    console.warn("Stop failed", err);
                    isScanning = false;
                });
            }
            $("#qrcodescannerdiv").removeClass('scanner-active');
        };

        function onScanSuccess(decodedText) {
            if (decodedText === lastScannedCode) return;
            lastScannedCode = decodedText;
            clearTimeout(scanLockTimeout);
            scanLockTimeout = setTimeout(() => { lastScannedCode = null; }, 3000);

            $("#scannerStatus")
                .html('<i class="bi bi-qr-code text-white"></i><span>QR Code Read: ' + decodedText + '</span>')
                .removeClass('bg-light text-secondary bg-warning text-dark bg-danger text-white bg-info text-white')
                .addClass('bg-success text-white');

            $("#qrcodescannerdiv").removeClass('scanner-not-captured').addClass('scanner-captured');

            playBeep();
            $("#successFlash").show().fadeOut(400);

            if (navigator.vibrate) navigator.vibrate(100);

            setTimeout(() => {
                if (isScanning) {
                    $("#qrcodescannerdiv").removeClass('scanner-captured').addClass('scanner-not-captured');
                    $("#scannerStatus").html('<i class="bi bi-broadcast text-danger pulse"></i><span>Scanner Active: Looking for QR Code...</span>').removeClass('bg-success text-white').addClass('bg-light text-secondary');
                }
            }, 1500);

            fetchNewtry_directly(decodedText);
        }

        function onScanFailure(error) { }

        $("#cameraSelection").on("change", function () {
            const selectedCameraId = $(this).val();
            if (qrReader && isScanning) {
                qrReader.stop().then(() => {
                    isScanning = false;
                    initScanner(selectedCameraId);
                });
            }
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

        function fetchNewtry_directly(qrCodeValue) {
            $("#targetLocationList").empty();
            qrCodeValue = qrCodeValue.trim();
            const routurl = $("body").data("routurl");
            const companyid = $("body").data("companyid");
            const subcompanyid = $("body").data("subcompanyid");
            const projectid = $("body").data("projectid");

            if (scannedBoxes.some(b => b.scanned_barcode === qrCodeValue)) {
                iziToast.warning({ title: 'Duplicate', message: 'Tray already in list.', position: 'topRight' });
                return;
            }

            $.ajax({ 

                url: "modal/receving.php?action=barcodedgrafted",
                type: "POST",
                dataType: "json",
                data: { qrcode: qrCodeValue,  companyid, subcompanyid, projectid },
                success: function (res) {
                    if (!res || res.status === "error"  ) {
                        const errMsg = (res && res.message) ? res.message : 'Invalid Barcode.';
                        iziToast.error({ title: 'Error', message: errMsg, position: 'topRight' });
                        return;
                    }
 

                    scannedBoxes.push(res.data);
                    renderScannedBoxes();
                    $("#manualQrCode").val("");

                    /* Suggest the scanned batch location once; user may change it. */
                    if (scannedBoxes.length === 1) {
                         $("#targetLocationList").empty().append(
                                `<option value="${res.data.locationid}">${res.data.locationname}</option>`
                            );
                        $("#targetLocationList")
                            .data("initial-locationid", res.data.locationid)
                            .val(res.data.locationid)
                            .trigger("change");

                    }
                }
            });
        }

        function renderScannedBoxes() {
            if (scannedBoxes.length === 0) {
                $("#scannedBatchSummary").html(`
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-qr-code-scan d-block mb-2" style="font-size: 2rem;"></i>
                        No trays scanned yet.
                    </div>
                `);
                $("#totalTraysSum").text("0");
                $("#batchActionDiv").hide();
                return;
            }

            const batch = scannedBoxes[0];
            const totalTrays = Number(batch.total_trays || 0);

            $("#scannedBatchSummary").html(`
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3">
                        <div class="row g-3">
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="small text-muted">Product</div>
                                <div class="fw-bold text-primary">${batch.productname || "-"}</div>
                                <div class="small text-muted">Product ID: ${batch.productid || "-"}</div>
                            </div>
                            <div class="col-6 col-md-3 col-lg-2">
                                <div class="small text-muted">Stock Order</div>
                                <div class="fw-semibold">${batch.stockorder_no || "-"}</div>
                            </div>
                            <div class="col-6 col-md-3 col-lg-2">
                                <div class="small text-muted">Scanned Barcode</div>
                                <div class="fw-semibold text-break">${batch.scanned_barcode || "-"}</div>
                            </div>
                            <div class="col-6 col-md-3 col-lg-2">
                                <div class="small text-muted">Root Type</div>
                                <div class="fw-semibold">${batch.roottype || "N/A"}</div>
                            </div>
                            
                            
                            <div class="col-6 col-md-3">
                                <div class="small text-muted">Available Trays</div>
                                <div class="fs-5 fw-bold">${totalTrays.toLocaleString()}</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="small text-muted">Total Quantity</div>
                                <div class="fs-5 fw-bold text-success">
                                    ${Number(batch.total_qty || 0).toLocaleString()}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `);

            $("#totalTraysSum").text(totalTrays);
            finalallocatedtray = 0;
            $("#batchActionDiv").show(); 
            $("#polyhouseCapacitySection").stop(true, true).slideDown();
        }

        $(document).on("click", ".remove-box", function () {
            scannedBoxes.splice($(this).data("index"), 1);
            renderScannedBoxes();
        });

        $("#clearScannedBoxes").on("click", function () {
            scannedBoxes = [];
            renderScannedBoxes();
        });

        $("#fetchManual").on("click", function () {
            const qrValue = $("#manualQrCode").val();
            if (qrValue) fetchNewtry_directly(qrValue);
        });

        $("#manualQrCode").on("keypress", function (e) {
            if (e.which === 13) fetchNewtry_directly($(this).val());
        });

     /*   $("#startScan").on("click", initScanner);
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
*/
        $("#startScan").on("click", function (e) {
            e.preventDefault();
            initScanner();
        });

        $("#stopScan").on("click", function (e) {
            e.preventDefault();
            stopScanner();
        });

        $("#ManualStartScan").on("click", function (e) {
            e.preventDefault();

            stopScanner();

            $("#qrcodescannerdiv").hide();
            $("#manualQrCodeSearchDiv")
                .show()
                .find("#manualQrCode")
                .focus();
        });

        $("#QRcodeStartScan").on("click", function (e) {
            e.preventDefault();

            $("#qrcodescannerdiv").show();
            $("#manualQrCodeSearchDiv").hide();

            initScanner();
        });
        /* --- Final Batch Submission --- */
        $("#submitVerificationBatch").on("click", async function () {
            const polyhouseid = $("#targetPolyhouseList").val();
            if (!polyhouseid) {
                iziToast.error({ title: 'Select Polyhouse', message: 'Target polyhouse is required.', position: 'topRight' });
                $("#polyhouseCapacitySection").slideDown();
                return;
            }

            const confirm = await Swal.fire({
                title: 'Verify Batch?',
                text: `Process ${scannedBoxes.length} boxes into the selected polyhouse?`,
                icon: 'question',
                showCancelButton: true
            });

            if (!confirm.isConfirmed) return;

            const routurl = $("body").data("routurl");
            const companyid = $("body").data("companyid");
            const subcompanyid = $("body").data("subcompanyid");
            const projectid = $("body").data("projectid");
            const loginid = $("body").data("loginid");

            Swal.fire({ title: 'Processing...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

            let successCount = 0;
            for (const box of scannedBoxes) {
                const fd = new FormData();
                fd.append("companyid", companyid);
                fd.append("subcompanyid", subcompanyid);
                fd.append("projectid", projectid);
                fd.append("loginid", loginid);
                fd.append("boxid", box.boxid);
                fd.append("stockorderno", box.stockorderno);
                fd.append("polyhouseid", polyhouseid);
                fd.append("allocation_mode", "single");

                try {
                    const response = await fetch(routurl + "krf-orderandsales/modal/polihouse_modal.php?action=SubmitGerminationBoxVerificationForPolyhouse", {
                        method: 'POST',
                        body: fd
                    });
                    const res = await response.json();
                    if (res.status === "success") successCount++;
                } catch (err) { }
            }

            Swal.close();
            Swal.fire({ title: 'Complete', text: `Verified ${successCount} boxes.`, icon: 'success' }).then(() => {
                scannedBoxes = [];
                renderScannedBoxes();
                $("#polyhouseCapacitySection").slideUp();
                $("#capacityGridDiv").empty();
            });
        });
    });
</script>
```
