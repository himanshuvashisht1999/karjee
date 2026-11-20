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
        padding-bottom: 120px; /* Generous bottom spacing for mobile view scrolling */
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
        margin-bottom: 1.5rem;
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
            <h5><?php echo _t('germination_box_received'); ?></h5>
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
                    <?php echo _t('box_verification'); ?>
                </div>

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

        <!-- Scanned Items Table Section -->
        <div class="col-lg-12">
            <div id="BoxDetailsInfo" class="content-card h-100">
                <div class="card-title-premium border-bottom pb-2 mb-3">
                    <i class="bi bi-list-check text-primary"></i> Scanned Boxes Details
                </div>

                <div class="table-responsive" style="max-height: 250px;">
                    <table class="scanned-boxes-table mt-4" id="scannedGBoxesTable">
                        <thead>
                            <tr>
                                <th>Product / Stock Order</th>
                                <th>Box ID</th>
                                <th>Trays</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">
                                    <i class="bi bi-qr-code-scan d-block mb-2" style="font-size: 2rem;"></i>
                                    No boxes scanned yet.
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="total-row" style="display:none;">
                                <td colspan="2" class="text-end">Total Trays Scanned:</td>
                                <td id="totalTraysSum">0</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>

                </div>

                <div id="batchActionDiv" class="mt-4" style="display:none;">
                    <!-- Target Selection & Capacity View -->
                    <div id="polyhouseCapacitySection" class="bg-light p-3 rounded-4 mb-3">
                        <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                            <i class="bi bi-geo-alt text-primary"></i> Germinated Box Received At Germiniation Room
                        </h6>

                        <div class="row g-2 mb-3">
                            <div class="col-md-12">
                                <label class="info-label">Sowing Location</label>
                                <select id="targetLocationList" class="form-select border-primary-subtle"
                                    style="border-radius:10px;"></select>
                            </div>
                            <div class="col-md-12">
                                <div class="d-flex justify-content-center">
                                    <button class="btn confirm-btn-premium w-50" id="confirmGboxReceivedbtn">
                                        CONFIRM RECEIVED
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
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
    let isCameraListLoaded = false;
    let autoScanInterval = null;

    $(document).ready(function () {
        const projectid = $("body").data("projectid");

        // Explicit Close Handler for Custom Close Buttons
        $(document).on("click", ".btn-close-custom", function () {
            $(this).closest(".modal").modal("hide");
        });

        LoadProductionLocations(projectid, '', 'targetLocationList'); // For capacity check

        function LoadProductionLocations(projectid, selectedId, dropdownid) {
            let companyid = $("body").data("companyid");
            let subcompanyid = $("body").data("subcompanyid");
            let routurl = $("body").data("routurl");
            $.ajax({
                url: routurl + "krf-orderandsales/modal/product_modal.php?action=GetProductionLocationList",
                type: "POST",
                data: { projectid: projectid, companyid: companyid, subcompanyid: subcompanyid },
                dataType: "json",
                success: function (res) {
                    let options = '<option value="">All Locations</option>';
                    let defaultLocId = "";
                    if (Array.isArray(res)) {
                        res.forEach(function (loc) {
                            options += `<option value="${loc.LocationId}">${loc.LocationName}</option>`;
                            let locNameUpper = String(loc.LocationName).toUpperCase();
                            if (locNameUpper.includes("GERMINATION") || locNameUpper.includes("G-ROOM") || locNameUpper.includes("GROOM") || locNameUpper.includes("G ROOM")) {
                                defaultLocId = loc.LocationId;
                            }
                        });
                    }
                    $("#" + dropdownid).html(options);
                    let targetVal = selectedId || defaultLocId;
                    if (targetVal) {
                        $("#" + dropdownid).val(targetVal).trigger("change");
                    }
                }
            });
        }

        $("#confirmGboxReceivedbtn").on("click", async function () {

            const routurl = $("body").data("routurl");
            const companyid = $("body").data("companyid");
            const subcompanyid = $("body").data("subcompanyid");
            const projectid = $("body").data("projectid");
            const loginid = $("body").data("loginid");

            const boxIds = [];
            $("#scannedGBoxesTable tbody tr").each(function () {
                // Find column 2 (Box ID) - it usually has # prefix
                const boxIdText = $(this).find("td:nth-child(2)").text().replace("#", "").trim();
                if (boxIdText && boxIdText !== "No boxes scanned yet.") {
                    boxIds.push(boxIdText);
                }
            });

            if (boxIds.length === 0) {
                iziToast.error({ title: 'Missing Data', message: 'No boxes scanned to confirm.', position: 'topRight' });
                return false;
            }

            const sowinglocationid = $("#targetLocationList").val();
            if (!sowinglocationid) {
                iziToast.error({ title: 'Selection Missing', message: 'Please select a Sowing Location.', position: 'topRight' });
                return false;
            }

            const fd = new FormData();
            fd.append("companyid", companyid);
            fd.append("subcompanyid", subcompanyid);
            fd.append("projectid", projectid);
            fd.append("loginid", loginid);
            fd.append("sowinglocationid", sowinglocationid);
            fd.append("boxids", JSON.stringify(boxIds));

            try {
                const response = await fetch(routurl + "krf-orderandsales/modal/polihouse_modal.php?action=UpdateReceivedGboxatGroom", {
                    method: 'POST',
                    body: fd
                });
                const res = await response.json();
                if (res.status === "success") {
                    iziToast.success({ title: 'Success', message: res.message, position: 'topRight' });
                    // Clear scanned boxes
                    scannedBoxes = [];
                    renderScannedBoxes();
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Failed to communicate with server', 'error');
            }
        });

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

            fetchNewGerminationBox(decodedText);
        }

        function onScanFailure(error) { }

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

        function fetchNewGerminationBox(qrCodeValue) {
            const routurl = $("body").data("routurl");
            const companyid = $("body").data("companyid");
            const subcompanyid = $("body").data("subcompanyid");
            const projectid = $("body").data("projectid");

            if (scannedBoxes.some(b => b.qrcode === qrCodeValue)) {
                iziToast.warning({ title: 'Duplicate', message: 'Box already in list.', position: 'topRight' });
                $("#scannerStatus")
                    .html('<i class="bi bi-exclamation-circle text-dark"></i><span>Duplicate Code: ' + qrCodeValue + '</span>')
                    .removeClass('bg-light text-secondary bg-success text-white bg-danger text-white bg-info text-white')
                    .addClass('bg-warning text-dark');
                
                // Automatically return to scanner active status (and RED border) after 2.5 seconds
                setTimeout(() => {
                    if (isScanning) {
                        $("#scannerStatus")
                            .html('<i class="bi bi-broadcast text-danger pulse"></i><span>Scanner Active: Looking for QR Code...</span>')
                            .removeClass('bg-success text-white bg-warning text-dark bg-danger text-white bg-info text-white')
                            .addClass('bg-light text-secondary');
                        $("#qrcodescannerdiv").removeClass('scanner-captured').addClass('scanner-not-captured');
                    }
                }, 2500);
                return;
            }

            $.ajax({
                url: routurl + "krf-orderandsales/modal/polihouse_modal.php?action=GerminationBoxDataAfterSowing",
                type: "POST",
                dataType: "json",
                data: { qrcode: qrCodeValue, companyid, subcompanyid, projectid },
                success: function (res) {
                    if (!res || res.status === "error" || !res.boxid) {
                        iziToast.error({ title: 'Error', message: 'Invalid box QR.', position: 'topRight' });
                        $("#scannerStatus")
                            .html('<i class="bi bi-x-circle text-white"></i><span>Invalid Code: ' + qrCodeValue + '</span>')
                            .removeClass('bg-light text-secondary bg-success text-white bg-warning text-dark bg-info text-white')
                            .addClass('bg-danger text-white');
                    } else if (res.receivedstatus === 'ReceivedAtPolyhouse') {
                        iziToast.info({ title: 'Already Received', message: 'This box is verified.', position: 'topRight' });
                        $("#scannerStatus")
                            .html('<i class="bi bi-info-circle text-white"></i><span>Already Verified: ' + qrCodeValue + '</span>')
                            .removeClass('bg-light text-secondary bg-success text-white bg-warning text-dark bg-danger text-white')
                            .addClass('bg-info text-white');
                    } else {
                        // Success: Box found and verified!
                        res.qrcode = qrCodeValue;
                        scannedBoxes.push(res);
                        renderScannedBoxes();
                        $("#manualQrCode").val("");

                        $("#scannerStatus")
                            .html('<i class="bi bi-check-circle-fill text-white"></i><span>Added Box ID: #' + res.boxid + '</span>')
                            .removeClass('bg-light text-secondary bg-warning text-dark bg-danger text-white bg-info text-white')
                            .addClass('bg-success text-white');

                        /* Auto-set Target Location if first scan */
                        if (scannedBoxes.length === 1 && !$("#targetLocationList").val()) {
                            $("#targetLocationList").val(res.polyhouse_locationid).trigger("change");
                        }
                    }

                    // Automatically return to scanner active status (and RED border) after 2.5 seconds
                    setTimeout(() => {
                        if (isScanning) {
                            $("#scannerStatus")
                                .html('<i class="bi bi-broadcast text-danger pulse"></i><span>Scanner Active: Looking for QR Code...</span>')
                                .removeClass('bg-success text-white bg-warning text-dark bg-danger text-white bg-info text-white')
                                .addClass('bg-light text-secondary');
                            $("#qrcodescannerdiv").removeClass('scanner-captured').addClass('scanner-not-captured');
                        }
                    }, 2500);
                },
                error: function() {
                    iziToast.error({ title: 'Connection Error', message: 'Failed to query box data from server.', position: 'topRight' });
                    $("#scannerStatus")
                        .html('<i class="bi bi-cloud-slash text-white"></i><span>Server Communication Error!</span>')
                        .removeClass('bg-light text-secondary bg-success text-white bg-warning text-dark bg-info text-white')
                        .addClass('bg-danger text-white');

                    // Automatically return to scanner active status (and RED border) after 2.5 seconds
                    setTimeout(() => {
                        if (isScanning) {
                            $("#scannerStatus")
                                .html('<i class="bi bi-broadcast text-danger pulse"></i><span>Scanner Active: Looking for QR Code...</span>')
                                .removeClass('bg-success text-white bg-warning text-dark bg-danger text-white bg-info text-white')
                                .addClass('bg-light text-secondary');
                            $("#qrcodescannerdiv").removeClass('scanner-captured').addClass('scanner-not-captured');
                        }
                    }, 2500);
                }
            });
        }

        function renderScannedBoxes() {
            const $tbody = $("#scannedGBoxesTable tbody").empty();
            let totalTrays = 0;

            if (scannedBoxes.length === 0) {
                $tbody.append('<tr><td colspan="4" class="text-center py-4 text-muted">No boxes scanned yet.</td></tr>');
                $("#batchActionDiv").hide();
                $(".total-row").hide();
                return;
            }

            scannedBoxes.forEach((box, index) => {
                totalTrays += parseInt(box.traycountinbox) || 0;
                $tbody.append(`
                    <tr>
                        <td>
                            <div class="fw-bold text-primary">${box.productname}</div>
                            <div class="small text-muted">#${box.stockorderno}</div>
                        </td>
                        <td>#${box.boxid}</td>
                        <td>${box.traycountinbox}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-danger remove-box" data-index="${index}"><i class="bi bi-x"></i></button>
                        </td>
                    </tr>
                `);
            });

            $("#totalTraysSum").text(totalTrays);
            $(".total-row").show();
            $("#batchActionDiv").show();
        }

        $(document).on("click", ".remove-box", function () {
            scannedBoxes.splice($(this).data("index"), 1);
            renderScannedBoxes();
        });


        $("#fetchManual").on("click", function () {
            const qrValue = $("#manualQrCode").val();
            if (qrValue) fetchNewGerminationBox(qrValue);
        });

        $("#manualQrCode").on("keypress", function (e) {
            if (e.which === 13) fetchNewGerminationBox($(this).val());
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

        // Bind explicit shutter button
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
</script>
```