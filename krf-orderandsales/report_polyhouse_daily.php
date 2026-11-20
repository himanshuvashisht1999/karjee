<?php
include('../header.php');
?>
<!-- Outfit Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
    body {
        font-family: 'Outfit', sans-serif;
        background-color: #f3f4f6;
        color: #1f2937;
    }

    .report-container {
        max-width: 1440px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }

    /* --- Custom Header style --- */
    .report-header-card {
        background: linear-gradient(135deg, #4f46e5 0%, #06b6d4 100%);
        border-radius: 20px;
        padding: 1.5rem 2rem;
        color: white;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(79, 70, 229, 0.15);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1.5rem;
    }

    .report-header-card h3 {
        font-weight: 700;
        margin: 0;
        letter-spacing: -0.5px;
    }

    /* Date controls styling */
    .date-controller-wrapper {
        display: flex;
        align-items: center;
        background: rgba(255, 255, 255, 0.2);
        padding: 6px;
        border-radius: 14px;
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .date-btn {
        background: transparent;
        border: none;
        color: white;
        width: 38px;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        transition: all 0.2s;
    }

    .date-btn:hover {
        background: rgba(255, 255, 255, 0.25);
        color: #fff;
    }

    .date-input {
        background: transparent;
        border: none;
        color: white;
        text-align: center;
        font-weight: 600;
        font-size: 1rem;
        outline: none;
        width: 140px;
        cursor: pointer;
    }

    /* Webkit calendar white color override */
    .date-input::-webkit-calendar-picker-indicator {
        filter: invert(1);
    }

    /* --- Tabular Strips Design --- */
    .table-responsive-wrapper {
        background: white;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
        padding: 1.5rem;
        border: 1px solid #e5e7eb;
        overflow-x: auto;
    }

    .report-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 8px; /* space between row strips */
    }

    .report-table th {
        background-color: #f9fafb;
        color: #4b5563;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
        padding: 1rem;
        border: none;
    }

    .report-table th:first-child {
        border-radius: 12px 0 0 12px;
    }
    .report-table th:last-child {
        border-radius: 0 12px 12px 0;
    }

    .report-row-strip {
        background-color: #ffffff;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .report-row-strip:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(79, 70, 229, 0.08);
    }

    .report-row-strip td {
        padding: 1.25rem 1rem;
        border-top: 1px solid #f3f4f6;
        border-bottom: 1px solid #f3f4f6;
        font-size: 0.9rem;
        color: #374151;
        vertical-align: middle;
    }

    .report-row-strip td:first-child {
        border-left: 6px solid #4f46e5;
        border-top-left-radius: 14px;
        border-bottom-left-radius: 14px;
        font-weight: 700;
        color: #9ca3af;
    }

    .report-row-strip td:last-child {
        border-right: 1px solid #f3f4f6;
        border-top-right-radius: 14px;
        border-bottom-right-radius: 14px;
    }

    /* Badges & Text styles */
    .badge-premium {
        padding: 6px 12px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        display: inline-block;
        white-space: nowrap;
    }

    .badge-bed { background-color: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
    .badge-scion { background-color: #fdf2f8; color: #9d174d; border: 1px solid #fbcfe8; }
    .badge-root { background-color: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
    .badge-tray { background-color: #f5f3ff; color: #5b21b6; border: 1px solid #ddd6fe; }
    .badge-so { background-color: #f0f9ff; color: #0369a1; border: 1px solid #bae6fd; text-transform: none; padding: 4px 8px; }

    .badge-status-pending { background-color: #fef3c7; color: #92400e; }
    .badge-status-dispatched { background-color: #d1fae5; color: #065f46; }
    .badge-status-notdispatched { background-color: #fee2e2; color: #991b1b; }

    .trays-val {
        color: #ffffff !important;
    }
    .plants-val {
        color: #ffffff !important;
    }

    .text-val { font-weight: 600; }
    .filter-card {
        background: white;
        border-radius: 16px;
        padding: 1.25rem 1.5rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
        border: 1px solid #e5e7eb;
        margin-bottom: 1.5rem;
    }
    .filter-select {
        border-radius: 10px;
        height: 42px !important;
        border-color: #d1d5db;
        display: block !important;
        width: 100% !important;
        box-sizing: border-box !important;
        background-color: #ffffff !important;
        padding-left: 12px !important;
        padding-right: 28px !important;
    }
    .search-input {
        border-radius: 0 10px 10px 0;
        height: 42px !important;
        border-color: #d1d5db;
        box-sizing: border-box !important;
        background-color: #ffffff !important;
    }
    .date-controller-wrapper {
        display: flex;
        align-items: center;
        background: #f3f4f6;
        padding: 4px;
        border-radius: 10px;
        border: 1px solid #d1d5db;
        height: 42px;
        box-sizing: border-box;
    }
    .date-btn {
        background: transparent;
        border: none;
        color: #4b5563;
        width: 34px;
        height: 34px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        transition: all 0.2s;
    }
    .date-btn:hover {
        background: rgba(0, 0, 0, 0.05);
        color: #1f2937;
    }
    .date-input {
        background: transparent;
        border: none;
        color: #1f2937;
        text-align: center;
        font-weight: 600;
        font-size: 0.9rem;
        outline: none;
        width: 130px;
        cursor: pointer;
    }
    .date-input::-webkit-calendar-picker-indicator {
        filter: none;
    }
    .btn-export {
        border-radius: 10px;
        height: 42px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0 1.25rem;
        transition: all 0.2s;
    }
</style>

<div class="report-container" data-routurl="<?php echo $prefix; ?>" data-projectid="<?php echo $projectid; ?>">
    <!-- Header Card -->
    <div class="report-header-card shadow">
        <div>
            <h3><i class="bi bi-file-earmark-bar-graph-fill me-2"></i>Polyhouse Daily Register</h3>
            <p class="mb-0 text-white-50">Tracking daily polyhouse records</p>
        </div>
    </div>

    <!-- Filters Strip -->
    <div class="filter-card shadow-sm">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-semibold text-secondary" style="font-size: 0.85rem; text-transform: uppercase; margin-bottom: 0.4rem;">Location Name</label>
                <select id="locationFilter" class="form-select filter-select">
                    <option value="" disabled>Select Locations</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold text-secondary" style="font-size: 0.85rem; text-transform: uppercase; margin-bottom: 0.4rem;">Polyhouse Name</label>
                <select id="polyhouseFilter" class="form-select filter-select">
                    <option value="" disabled>Select Polyhouses</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold text-secondary" style="font-size: 0.85rem; text-transform: uppercase; margin-bottom: 0.4rem;">Date Selector</label>
                <div class="date-controller-wrapper">
                    <button class="date-btn" id="prevDayBtn" type="button" title="Previous Day">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <input type="date" class="date-input" id="reportDatePicker" value="<?php echo date('Y-m-d'); ?>">
                    <button class="date-btn" id="nextDayBtn" type="button" title="Next Day">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Container -->
    <div id="reportSummaryWrapper"></div>

    <!-- Actions & Info Card (just before Table Card) -->
    <div class="filter-card shadow-sm" id="reportActionsCard" style="margin-top: -0.5rem; padding: 1rem 1.5rem; display: none;">
        <div class="row align-items-center g-3">
            <div class="col-md-6">
                <h5 class="mb-2 text-primary fw-bold" id="dynamicReportTitle">Daily Report: <?php echo date('d-m-Y'); ?></h5>
                <div class="input-group" style="max-width: 300px;">
                    <span class="input-group-text bg-white border-end-0" style="border-radius: 8px 0 0 8px; border-color: #d1d5db; height: 36px; padding: 0 10px;"><i class="bi bi-search text-muted" style="font-size: 0.85rem;"></i></span>
                    <input type="text" id="reportSearch" class="form-control border-start-0" style="border-radius: 0 8px 8px 0; height: 36px; font-size: 0.85rem; padding: 0 10px;" placeholder="Search inside report...">
                </div>
            </div>
            <div class="col-md-6 text-end">
                <button class="btn btn-primary btn-sm me-2" id="updateBedTraysBtn" style="border-radius: 8px; font-weight: 600; padding: 0.4rem 0.8rem; font-size: 0.85rem; height: 36px;">
                    <i class="bi bi-pencil-square me-1"></i> Update Bed Trays
                </button>
                <button class="btn btn-success btn-sm me-2" id="exportExcelBtn" style="border-radius: 8px; font-weight: 600; padding: 0.4rem 0.8rem; font-size: 0.85rem; height: 36px;">
                    <i class="bi bi-file-earmark-excel-fill me-1"></i> Export Excel
                </button>
                <button class="btn btn-danger btn-sm" id="exportPdfBtn" style="border-radius: 8px; font-weight: 600; padding: 0.4rem 0.8rem; font-size: 0.85rem; height: 36px;">
                    <i class="bi bi-file-earmark-pdf-fill me-1"></i> Export PDF
                </button>
            </div>
        </div>
    </div>

    <!-- Daily report table container -->
    <div id="reportTableWrapper">
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading report...</span>
            </div>
        </div>
    </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript" src="<?php echo $prefix;?>krf-orderandsales/js/pjs_function.js"></script>

<script>
$(document).ready(function() {
    const rooturl = $(".report-container").data("routurl");
    const projectid = $(".report-container").data("projectid");
    let currentReportDetails = [];
    let selectedGroupTotalTrays = 0;

    function validateTotalTrays() {
        let currentSum = 0;
        $("#modalBedTableBody tr.bed-row").each(function() {
            const qty = parseInt($(this).find(".qty-input").val()) || 0;
            currentSum += qty;
        });

        $("#modalCurrentTotalTrays").text(currentSum);
        if (currentSum > selectedGroupTotalTrays) {
            $("#modalTotalTraysAlert").show().html(`<i class="bi bi-exclamation-triangle-fill me-2"></i><strong>Warning:</strong> Total input trays (${currentSum}) exceeds the selected order's limit of ${selectedGroupTotalTrays} trays.`);
            $("#modalSubmitUpdateBtn").prop("disabled", true);
            $("#modalCurrentTotalTrays").addClass("text-danger").removeClass("text-success");
        } else {
            $("#modalTotalTraysAlert").hide();
            $("#modalSubmitUpdateBtn").prop("disabled", false);
            $("#modalCurrentTotalTrays").addClass("text-success").removeClass("text-danger");
        }
    }

    function LoadLocations() {
        const companyid = $("body").data("companyid");
        const subcompanyid = $("body").data("subcompanyid");
        $.ajax({
            url: rooturl + "krf-orderandsales/modal/product_modal.php?action=GetProductionLocationList",
            type: "POST",
            data: { projectid: projectid, companyid: companyid, subcompanyid: subcompanyid },
            dataType: "json",
            success: function (res) {
                // console.log(res);
                let options = '<option value="" disabled selected>Select Location</option>';
                if (res && res.length > 0) {
                    res.forEach(loc => {
                        options += `<option value="${loc.LocationId}">${loc.LocationName}</option>`;
                    });
                }
                $("#locationFilter").html(options).trigger('change');
            }
        });
    }

    $(document).on("change", "#locationFilter", function (e) {
        $("#polyhouseFilter").empty();
        const locId = $(this).val();
        if (!locId) {
            $("#polyhouseFilter").html('<option value="" disabled selected>Select Polyhouse</option>').trigger('change');
            loadDailyReport();
            return;
        }
        GetPolihouseDetailsList('locationid', locId, '', 'polyhouseFilter');
        loadDailyReport();
    });

    $(document).on("change", "#polyhouseFilter", function () {
        loadDailyReport();
    });

    function loadDailyReport() {
        const locId = $("#locationFilter").val();
        const phId = $("#polyhouseFilter").val();
        const reportdate = $("#reportDatePicker").val();

        // Update Dynamic Title
        if(reportdate) {
            let parts = reportdate.split('-');
            if(parts.length === 3) {
                $("#dynamicReportTitle").text("Daily Report: " + parts[2] + "-" + parts[1] + "-" + parts[0]);
            }
        }

        if (!locId || !phId || !reportdate) {
            $("#reportActionsCard").hide();
            $("#reportSummaryWrapper").empty();
            $("#reportTableWrapper").html(`
                <div class="text-center py-5 bg-white rounded-4 shadow-sm border">
                    <i class="bi bi-info-circle text-primary" style="font-size: 3rem;"></i>
                    <h4 class="mt-3 font-weight-bold">Please Select Location, Polyhouse & Date</h4>
                    <p class="text-muted">Ensure all three filters (Location, Polyhouse, and Date) are selected to display tabular report data.</p>
                </div>
            `);
            return;
        }

        $("#reportActionsCard").hide();
        $("#reportSummaryWrapper").empty();
        $("#reportTableWrapper").html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading report...</span>
                </div>
            </div>
        `);

        $.ajax({
            url: rooturl + "krf-orderandsales/modal/reporting.php?action=GetPolyhouseDailyReport",
            type: "POST",
            data: { projectid: projectid, reportdate: reportdate, locationid: locId, polyhouseid: phId },
            dataType: "json",
            success: function(res) {
                if(res.status === 'success' && res.details.length > 0) {
                    $("#reportActionsCard").show();
                    currentReportDetails = res.details;
                    
                    let uniqueBeds = new Set();
                    let uniqueReceivers = new Set();
                    let totalTrays = 0;
                    let totalPlants = 0;
                    res.details.forEach(item => {
                        if (item.bed_no && item.bed_no !== 'N/A') {
                            uniqueBeds.add(item.bed_no);
                        }
                        if (item.box_received_by && item.box_received_by !== 'N/A') {
                            uniqueReceivers.add(item.box_received_by);
                        }
                        totalTrays += parseInt(item.no_of_trays) || 0;
                        totalPlants += parseInt(item.no_of_plants) || 0;
                    });
                    let totalBedsUsed = uniqueBeds.size;
                    let boxReceivedBy = uniqueReceivers.size > 0 ? Array.from(uniqueReceivers).join(", ") : "N/A";
                    let selectedLocation = $("#locationFilter option:selected").text() || "N/A";
                    let selectedPolyhouse = $("#polyhouseFilter option:selected").text() || "N/A";

                    let summaryHtml = `
                        <div class="filter-card shadow-sm mb-4" style="padding: 1.5rem; border-radius: 16px; background-color: #ffffff; border: 1px solid #e5e7eb !important;">
                            <span id="summaryBoxReceivedBy" style="display: none;">${boxReceivedBy}</span>
                            <div class="row g-4 align-items-center">
                                <!-- Details Column -->
                                <div class="col-lg-6 border-lg-end" style="border-right: 1px solid #e5e7eb;">
                                    <div class="row g-3">
                                        <div class="col-sm-4">
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 36px; height: 36px; background-color: #f5f3ff; color: #5b21b6; flex-shrink: 0;">
                                                    <i class="bi bi-geo-alt-fill" style="font-size: 1.1rem;"></i>
                                                </div>
                                                <div>
                                                    <div class="text-muted small text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Location</div>
                                                    <div class="fw-semibold text-dark" style="font-size: 0.9rem; word-break: break-all;">${selectedLocation}</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 36px; height: 36px; background-color: #fdf2f8; color: #9d174d; flex-shrink: 0;">
                                                    <i class="bi bi-house-door-fill" style="font-size: 1.1rem;"></i>
                                                </div>
                                                <div>
                                                    <div class="text-muted small text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Polyhouse</div>
                                                    <div class="fw-semibold text-dark" style="font-size: 0.9rem; word-break: break-all;">${selectedPolyhouse}</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 36px; height: 36px; background-color: #fffbeb; color: #d97706; flex-shrink: 0;">
                                                    <i class="bi bi-person-check-fill" style="font-size: 1.1rem;"></i>
                                                </div>
                                                <div>
                                                    <div class="text-muted small text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Received By</div>
                                                    <div class="fw-semibold text-dark" style="font-size: 0.9rem; word-break: break-all;">${boxReceivedBy}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Metrics Column -->
                                <div class="col-lg-6">
                                    <div class="row g-3">
                                        <div class="col-sm-4">
                                            <div class="d-flex align-items-center justify-content-lg-center">
                                                <div class="rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 36px; height: 36px; background-color: #eff6ff; color: #1e40af; flex-shrink: 0;">
                                                    <i class="bi bi-grid-3x3-gap-fill" style="font-size: 1.1rem;"></i>
                                                </div>
                                                <div>
                                                    <div class="text-muted small text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Beds Used</div>
                                                    <div class="fw-bold text-dark" style="font-size: 1.1rem;">${totalBedsUsed}</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="d-flex align-items-center justify-content-lg-center">
                                                <div class="rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 36px; height: 36px; background-color: #f3f4f6; color: #4b5563; flex-shrink: 0;">
                                                    <i class="bi bi-box-seam-fill" style="font-size: 1.1rem;"></i>
                                                </div>
                                                <div>
                                                    <div class="text-muted small text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Total Trays</div>
                                                    <div class="fw-bold text-dark" style="font-size: 1.1rem;">${totalTrays}</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="d-flex align-items-center justify-content-lg-center">
                                                <div class="rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 36px; height: 36px; background-color: #ecfdf5; color: #065f46; flex-shrink: 0;">
                                                    <i class="bi bi-flower1" style="font-size: 1.1rem;"></i>
                                                </div>
                                                <div>
                                                    <div class="text-muted small text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Total Plants</div>
                                                    <div class="fw-bold text-dark" style="font-size: 1.1rem;">${totalPlants}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    $("#reportSummaryWrapper").html(summaryHtml);

                    let tableHtml = `
                        <div class="table-responsive-wrapper">
                            <table class="report-table" id="dailyReportTable">
                                <thead>
                                    <tr>
                                        <th style="width: 60px;">S.No</th>
                                        <th>Bed No</th>
                                        <th>Sowing Date / Loc</th>
                                        <th>Product / Client Name</th>
                                        <th>Type</th>
                                        <th>Tray Type</th>
                                        <th class="text-center">Trays & Plants</th>
                                        <th>Verification</th>
                                        <th class="text-center">Fail %</th>
                                        <th>Dispatch Date</th>
                                        <th>Status</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                    `;

                    res.details.forEach(item => {
                        let typeBadge = item.type.toLowerCase() === 'root' ? 'badge-root' : 'badge-scion';
                        let dispatchBadge = 'badge-status-pending';
                        if(item.dispatch_status === 'Dispatched') {
                            dispatchBadge = 'badge-status-dispatched';
                        } else if(item.dispatch_status === 'Not Dispatched') {
                            dispatchBadge = 'badge-status-notdispatched';
                        }

                        tableHtml += `
                            <tr class="report-row-strip shadow-sm">
                                <td>${item.sn}</td>
                                <td><span class="badge-premium badge-bed">${item.bed_no}</span></td>
                                <td>
                                    <div class="mb-1"><span class="badge-premium badge-so">SO: ${item.stockorder_no}</span></div>
                                    <div class="text-val sowing-date-val">${item.sowing_date}</div>
                                    <div class="text-muted loc-val" style="font-size: 0.8rem;"><i class="bi bi-geo-alt-fill me-1"></i>${item.sowing_location}</div>
                                </td>
                                <td>
                                    <div class="text-val text-primary product-val">${item.product_name}</div>
                                    <div class="text-secondary client-val" style="font-size: 0.85rem;"><i class="bi bi-person-fill me-1"></i>${item.client_name}</div>
                                </td>
                                <td><span class="badge-premium type-val ${typeBadge}">${item.type}</span></td>
                                <td><span class="badge-premium tray-val badge-tray">${item.tray_type}</span></td>
                                <td class="text-center">
                                    <span class="badge bg-secondary me-1 trays-val" title="Total Trays">${item.no_of_trays} T</span>
                                    <span class="badge bg-success plants-val" title="Total Plants">${item.no_of_plants} P</span>
                                </td>
                                <td><span class="text-val verify-val">${item.physical_verification}</span></td>
                                <td class="text-center text-val text-danger failure-val">${item.germination_failure}</td>
                                <td><span class="text-val dispatch-date-val">${item.dispatch_date}</span></td>
                                <td><span class="badge-premium status-val ${dispatchBadge}">${item.dispatch_status}</span></td>
                                <td><div class="text-muted remarks-val" style="font-size: 0.85rem; max-width: 200px; word-wrap: break-word;">${item.remarks || '-'}</div></td>
                            </tr>
                        `;
                    });

                    tableHtml += `
                                </tbody>
                            </table>
                        </div>
                    `;
                    $("#reportTableWrapper").html(tableHtml);
                    // Trigger search in case filter term exists
                    $("#reportSearch").trigger("keyup");
                } else {
                    $("#reportActionsCard").hide();
                    $("#reportSummaryWrapper").empty();
                    $("#reportTableWrapper").html(`
                        <div class="text-center py-5 bg-white rounded-4 shadow-sm border">
                            <i class="bi bi-clipboard2-x text-muted" style="font-size: 3rem;"></i>
                            <h4 class="mt-3 font-weight-bold">No Records Found</h4>
                            <p class="text-muted">There are no daily polyhouse allocation records for the selected filters.</p>
                        </div>
                    `);
                }
            },
            error: function() {
                $("#reportActionsCard").hide();
                $("#reportSummaryWrapper").empty();
                $("#reportTableWrapper").html(`
                    <div class="alert alert-danger text-center m-4">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> Failed to fetch report data. Please try again.
                    </div>
                `);
            }
        });
    }

    // Initialize Page
    LoadLocations();
    // const initialDate = $("#reportDatePicker").val();
    // loadDailyReport(initialDate);

    // Event listeners
    $("#reportDatePicker").on("change", function() {
        loadDailyReport();
    });

    $("#prevDayBtn").on("click", function() {
        let currentDate = new Date($("#reportDatePicker").val());
        currentDate.setDate(currentDate.getDate() - 1);
        let dateString = currentDate.toISOString().split('T')[0];
        $("#reportDatePicker").val(dateString).trigger("change");
    });

    $("#nextDayBtn").on("click", function() {
        let currentDate = new Date($("#reportDatePicker").val());
        currentDate.setDate(currentDate.getDate() + 1);
        let dateString = currentDate.toISOString().split('T')[0];
        $("#reportDatePicker").val(dateString).trigger("change");
    });

    // Real-time local search filter implementation
    $("#reportSearch").on("keyup", function() {
        let val = $(this).val().toLowerCase();
        $("#dailyReportTable tbody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(val) > -1);
        });
    });

    // Excel Export implementation
    $("#exportExcelBtn").on("click", function() {
        if ($("#dailyReportTable tbody tr").length === 0) {
            Swal.fire("Warning", "No data available to export!", "warning");
            return;
        }
        let csv = [];
        let dateVal = $("#reportDatePicker").val();
        let formattedDate = "";
        if (dateVal) {
            let parts = dateVal.split('-');
            if (parts.length === 3) {
                formattedDate = parts[2] + "-" + parts[1] + "-" + parts[0];
            }
        }
        if (!formattedDate) {
            formattedDate = dateVal;
        }

        let selectedLocation = $("#locationFilter option:selected").text() || "N/A";
        let selectedPolyhouse = $("#polyhouseFilter option:selected").text() || "N/A";
        let boxReceivedBy = $("#summaryBoxReceivedBy").text() || "N/A";
        
        let totalTrays = 0;
        let totalPlants = 0;
        let uniqueBeds = new Set();
        $("#dailyReportTable tbody tr:visible").each(function() {
            uniqueBeds.add($(this).find(".badge-bed").text().trim());
            totalTrays += parseInt($(this).find(".trays-val").text().trim().replace(" T", "")) || 0;
            totalPlants += parseInt($(this).find(".plants-val").text().trim().replace(" P", "")) || 0;
        });
        let totalBedsUsed = uniqueBeds.size;

        csv.push(`"Polyhouse Daily Report - ${formattedDate}"`);
        csv.push(`"Location: ${selectedLocation}","Polyhouse: ${selectedPolyhouse}","Box Received By: ${boxReceivedBy}"`);
        csv.push(`"Total Beds Used: ${totalBedsUsed}","Total Trays: ${totalTrays}","Total Plants: ${totalPlants}"`);
        csv.push("");
        let headers = ["S.No", "Bed No", "Sowing Date", "Sowing Location", "Product Name", "Client Name", "Type", "Tray Type", "Trays", "Plants", "Verification", "Fail %", "Dispatch Date", "Status", "Remarks"];
        csv.push(headers.join(","));

        $("#dailyReportTable tbody tr:visible").each(function(idx) {
            let row = [];
            row.push(idx + 1);
            row.push('"' + $(this).find(".badge-bed").text().trim() + '"');
            row.push('"' + $(this).find(".sowing-date-val").text().trim() + '"');
            row.push('"' + $(this).find(".loc-val").text().trim() + '"');
            row.push('"' + $(this).find(".product-val").text().trim() + '"');
            row.push('"' + $(this).find(".client-val").text().trim() + '"');
            row.push('"' + $(this).find(".type-val").text().trim() + '"');
            row.push('"' + $(this).find(".tray-val").text().trim() + '"');
            row.push('"' + $(this).find(".trays-val").text().trim().replace(" T", "") + '"');
            row.push('"' + $(this).find(".plants-val").text().trim().replace(" P", "") + '"');
            row.push('"' + $(this).find(".verify-val").text().trim() + '"');
            row.push('"' + $(this).find(".failure-val").text().trim() + '"');
            row.push('"' + $(this).find(".dispatch-date-val").text().trim() + '"');
            row.push('"' + $(this).find(".status-val").text().trim() + '"');
            row.push('"' + $(this).find(".remarks-val").text().trim() + '"');
            csv.push(row.join(","));
        });

        let csvString = csv.join("\n");
        let filename = "Polyhouse_Daily_Report_" + dateVal + ".csv";
        let link = document.createElement("a");
        link.setAttribute("href", "data:text/csv;charset=utf-8," + encodeURIComponent(csvString));
        link.setAttribute("download", filename);
        link.style.visibility = "hidden";
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });

    // PDF Export implementation using loaded jsPDF / AutoTable
    $("#exportPdfBtn").on("click", function() {
        if ($("#dailyReportTable tbody tr").length === 0) {
            alert("No data available to export!");
            return;
        }
        
        let dateVal = $("#reportDatePicker").val();
        let formattedDate = "";
        if (dateVal) {
            let parts = dateVal.split('-');
            if (parts.length === 3) {
                formattedDate = parts[2] + "-" + parts[1] + "-" + parts[0];
            }
        }
        if (!formattedDate) {
            formattedDate = dateVal;
        }
        
        // Dynamic detection of jsPDF object context
        let doc;
        if (window.jspdf && window.jspdf.jsPDF) {
            doc = new window.jspdf.jsPDF('l', 'pt', 'a4');
        } else if (typeof jsPDF !== 'undefined') {
            doc = new jsPDF('l', 'pt', 'a4');
        } else {
            Swal.fire("Warning", "PDF generation library is still loading. Please wait a moment and try again.", "warning");
            return;
        }

        let selectedLocation = $("#locationFilter option:selected").text() || "N/A";
        let selectedPolyhouse = $("#polyhouseFilter option:selected").text() || "N/A";
        let boxReceivedBy = $("#summaryBoxReceivedBy").text() || "N/A";
        
        let totalTrays = 0;
        let totalPlants = 0;
        let uniqueBeds = new Set();
        $("#dailyReportTable tbody tr:visible").each(function() {
            uniqueBeds.add($(this).find(".badge-bed").text().trim());
            totalTrays += parseInt($(this).find(".trays-val").text().trim().replace(" T", "")) || 0;
            totalPlants += parseInt($(this).find(".plants-val").text().trim().replace(" P", "")) || 0;
        });
        let totalBedsUsed = uniqueBeds.size;

        let pageWidth = doc.internal.pageSize.getWidth() || doc.internal.pageSize.width || 842;
        
        doc.setFontSize(16);
        doc.text("Polyhouse Daily Report", 40, 40);
        doc.text("Date: " + formattedDate, pageWidth - 40, 40, { align: 'right' });
        
        doc.setFontSize(10);
        doc.text(`Location: ${selectedLocation}   |   Polyhouse: ${selectedPolyhouse}   |   Box Received By: ${boxReceivedBy}`, 40, 60);
        doc.text(`Total Beds Used: ${totalBedsUsed}   |   Total Trays: ${totalTrays}   |   Total Plants: ${totalPlants}`, 40, 75);

        let head = [["S.No", "Bed No", "Sowing Date/Loc", "Product/Client", "Type", "Tray Type", "Trays", "Plants", "Verify", "Fail %", "Dispatch", "Status", "Remarks"]];
        let body = [];

        $("#dailyReportTable tbody tr:visible").each(function(idx) {
            let row = [];
            row.push(idx + 1);
            row.push($(this).find(".badge-bed").text().trim());
            row.push($(this).find(".sowing-date-val").text().trim() + "\n" + $(this).find(".loc-val").text().trim());
            row.push($(this).find(".product-val").text().trim() + "\n" + $(this).find(".client-val").text().trim());
            row.push($(this).find(".type-val").text().trim());
            row.push($(this).find(".tray-val").text().trim());
            row.push($(this).find(".trays-val").text().trim().replace(" T", ""));
            row.push($(this).find(".plants-val").text().trim().replace(" P", ""));
            row.push($(this).find(".verify-val").text().trim());
            row.push($(this).find(".failure-val").text().trim());
            row.push($(this).find(".dispatch-date-val").text().trim());
            row.push($(this).find(".status-val").text().trim());
            row.push($(this).find(".remarks-val").text().trim());
            body.push(row);
        });

        doc.autoTable({
            head: head,
            body: body,
            startY: 90,
            theme: 'striped',
            styles: { fontSize: 8, cellPadding: 4 },
            headStyles: { fillColor: [79, 70, 229] }
        });

        doc.save("Polyhouse_Daily_Report_" + dateVal + ".pdf");
    });

    $(document).on("click", "#updateBedTraysBtn", function() {
        const groups = {};
        currentReportDetails.forEach(item => {
            const key = `${item.stockorder_no}_${item.product_id}_${item.type}_${item.sowinglotno_internal}`;
            if (!groups[key]) {
                groups[key] = {
                    stockorder_no: item.stockorder_no,
                    product_id: item.product_id,
                    product_name: item.product_name,
                    type: item.type,
                    sowinglotno_internal: item.sowinglotno_internal,
                    label: `SO: ${item.stockorder_no} | Product: ${item.product_name} | Type: ${item.type} | Lot: ${item.sowinglotno_internal}`
                };
            }
        });

        let options = '<option value="" disabled selected>Select Group</option>';
        Object.keys(groups).forEach(key => {
            options += `<option value="${key}">${groups[key].label}</option>`;
        });
        $("#modalGroupSelector").html(options);
        $("#modalBedTableBody").empty();
        $("#modalBedListWrapper").hide();
        $("#modalSubmitUpdateBtn").prop("disabled", true);

        // Open modal
        const myModal = new bootstrap.Modal(document.getElementById('updateBedTraysModal'));
        myModal.show();
    });

    $(document).on("change", "#modalGroupSelector", function() {
        const selectedKey = $(this).val();
        if (!selectedKey) return;

        const parts = selectedKey.split('_');
        const so = parts[0];
        const prodId = parts[1];
        const type = parts[2];
        const lot = parts[3];

        // Filter items
        const groupItems = currentReportDetails.filter(item => 
            item.stockorder_no === so && 
            String(item.product_id) === String(prodId) && 
            item.type === type && 
            item.sowinglotno_internal === lot
        );

        // Sum current trays per bed
        const bedMap = {};
        let groupTotalTrays = 0;
        groupItems.forEach(item => {
            const bed = item.bed_no;
            const trays = parseInt(item.no_of_trays) || 0;
            bedMap[bed] = (bedMap[bed] || 0) + trays;
            groupTotalTrays += trays;
        });

        selectedGroupTotalTrays = groupTotalTrays;
        $("#modalMaxTotalTrays").text(selectedGroupTotalTrays);

        let rowsHtml = '';
        Object.keys(bedMap).forEach(bed => {
            rowsHtml += `
                <tr class="bed-row">
                    <td>
                        <input type="text" class="form-control bed-no-input" value="${bed === 'N/A' ? '' : bed}" placeholder="e.g. BED-1">
                    </td>
                    <td>
                        <span class="fw-bold text-secondary">${bedMap[bed]}</span>
                    </td>
                    <td>
                        <input type="number" class="form-control qty-input" value="${bedMap[bed]}" min="1" required>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-outline-danger btn-sm remove-row-btn" title="Remove Bed">
                            <i class="bi bi-trash-fill"></i>
                        </button>
                    </td>
                </tr>
            `;
        });

        $("#modalBedTableBody").html(rowsHtml);
        $("#modalBedListWrapper").show();
        validateTotalTrays();
    });

    $(document).on("click", "#modalAddBedRowBtn", function() {
        const rowHtml = `
            <tr class="bed-row">
                <td>
                    <input type="text" class="form-control bed-no-input" placeholder="e.g. BED-1">
                </td>
                <td>
                    <span class="fw-bold text-secondary">0</span>
                </td>
                <td>
                    <input type="number" class="form-control qty-input" value="1" min="1" required>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm remove-row-btn" title="Remove Bed">
                        <i class="bi bi-trash-fill"></i>
                    </button>
                </td>
            </tr>
        `;
        $("#modalBedTableBody").append(rowHtml);
        validateTotalTrays();
    });

    $(document).on("click", ".remove-row-btn", function() {
        $(this).closest("tr").remove();
        validateTotalTrays();
    });

    $(document).on("input change", ".qty-input", function() {
        validateTotalTrays();
    });

    $(document).on("click", "#modalSubmitUpdateBtn", function() {
        const selectedKey = $("#modalGroupSelector").val();
        if (!selectedKey) return;

        const parts = selectedKey.split('_');
        const so = parts[0];
        const prodId = parts[1];
        const type = parts[2];
        const lot = parts[3];

        const beds = [];
        let hasError = false;
        $("#modalBedTableBody tr.bed-row").each(function() {
            const bedNo = $(this).find(".bed-no-input").val().trim();
            const qty = parseInt($(this).find(".qty-input").val()) || 0;
            if (!bedNo) {
                Swal.fire("Warning", "Bed No cannot be empty.", "warning");
                hasError = true;
                return false;
            }
            if (qty <= 0) {
                Swal.fire("Warning", "Real Trays quantity must be greater than 0.", "warning");
                hasError = true;
                return false;
            }
            beds.push({ bed_no: bedNo, qty: qty });
        });

        if (hasError) return;

        const reportdate = $("#reportDatePicker").val();
        const polyhouseid = $("#polyhouseFilter").val();

        $("#modalSubmitUpdateBtn").prop("disabled", true).text("Updating...");

        $.ajax({
            url: rooturl + "krf-orderandsales/modal/reporting.php?action=UpdateBedTrays",
            type: "POST",
            data: {
                projectid: projectid,
                reportdate: reportdate,
                polyhouseid: polyhouseid,
                stockorder_no: so,
                productid: prodId,
                roottype: type,
                sowinglotno_internal: lot,
                beds: JSON.stringify(beds)
            },
            dataType: "json",
            success: function(res) {
                if (res.status === 'success') {
                    Swal.fire("Success!", res.message, "success");
                    // Close Modal
                    const modalEl = document.getElementById('updateBedTraysModal');
                    const modalInstance = bootstrap.Modal.getInstance(modalEl);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                    // Reload daily report
                    loadDailyReport();
                } else {
                    Swal.fire("Error", res.message, "error");
                    $("#modalSubmitUpdateBtn").prop("disabled", false).text("Update Bed Trays");
                }
            },
            error: function() {
                Swal.fire("Error", "Failed to communicate with database. Please try again.", "error");
                $("#modalSubmitUpdateBtn").prop("disabled", false).text("Update Bed Trays");
            }
        });
    });

    $(document).on("click", '[data-bs-dismiss="modal"], [data-dismiss="modal"]', function() {
        $("#updateBedTraysModal").modal("hide");
    });
});
</script>

<!-- Modal for updating Bed Trays -->
<div class="modal fade" id="updateBedTraysModal" tabindex="-1" role="dialog" aria-labelledby="updateBedTraysModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border-radius: 16px;">
            <div class="modal-header bg-light" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold text-dark" id="updateBedTraysModalLabel"><i class="bi bi-pencil-square me-2 text-primary"></i>Update Bed Trays</h5>
                <button type="button" class="close btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close" style="background: none; border: none; font-size: 1.5rem; line-height: 1;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-4">
                    <label class="form-label fw-bold text-secondary" style="font-size: 0.85rem; text-transform: uppercase;">Select Stock Order / Product Group</label>
                    <select id="modalGroupSelector" class="form-select filter-select">
                        <option value="" disabled selected>Select Group</option>
                    </select>
                </div>
                <div id="modalBedListWrapper" style="display: none;">
                    <div class="alert alert-danger" id="modalTotalTraysAlert" style="display: none; padding: 10px 14px; font-size: 0.85rem; border-radius: 8px; margin-bottom: 15px;"></div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0 text-primary">
                            <i class="bi bi-grid-3x3-gap-fill me-1"></i>Beds and Tray Quantities
                            <span class="badge bg-warning ms-2" style="font-size: 0.75rem;">Trays: <span id="modalCurrentTotalTrays">0</span> / <span id="modalMaxTotalTrays">0</span></span>
                        </h6>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="modalAddBedRowBtn">
                            <i class="bi bi-plus-lg me-1"></i>Add Bed Row
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle" id="modalBedTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Bed No</th>
                                    <th>Current Trays</th>
                                    <th>Real Trays (Input)</th>
                                    <th style="width: 80px;" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="modalBedTableBody">
                                <!-- Dynamically populated rows -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-dismiss="modal" style="border-radius: 10px;">Cancel</button>
                <button type="button" class="btn btn-primary" id="modalSubmitUpdateBtn" style="border-radius: 10px;" disabled>Update Bed Trays</button>
            </div>
        </div>
    </div>
</div>

<?php
include('../footer.php');
?>
