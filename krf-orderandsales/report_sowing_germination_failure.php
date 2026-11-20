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
        padding: 1.5rem 1rem;
    }

    /* --- Custom Header style --- */
    .report-header-card {
        background: linear-gradient(135deg, #ef4444 0%, #f97316 100%);
        border-radius: 20px;
        padding: 1.5rem 2rem;
        color: white;
        margin-bottom: 1.5rem;
        box-shadow: 0 10px 30px rgba(239, 68, 68, 0.15);
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

    /* Modern Responsive Filter Card */
    .filter-card {
        background: #ffffff;
        padding: 1.5rem;
        border-radius: 18px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        margin-bottom: 1.75rem;
        border: 1px solid #e2e8f0;
    }

    .form-group-custom {
        display: flex;
        flex-direction: column;
        width: 100%;
    }

    .form-label-custom {
        font-size: 0.78rem;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.45rem;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .form-control, .form-select {
        border-radius: 10px;
        border: 1.5px solid #cbd5e1;
        padding: 0.55rem 0.85rem;
        font-size: 0.92rem;
        color: #1e293b;
        background-color: #f8fafc;
        height: 44px;
        transition: all 0.2s ease-in-out;
        width: 100%;
    }

    .form-control:focus, .form-select:focus {
        border-color: #ef4444;
        background-color: #ffffff;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12);
        outline: none;
    }

    .btn-action-custom {
        height: 44px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 0.92rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
    }

    .btn-danger-custom {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: #ffffff !important;
        border: none;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);
    }

    .btn-danger-custom:hover {
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(239, 68, 68, 0.35);
    }

    .btn-reset-custom {
        background: #f1f5f9;
        color: #475569;
        border: 1.5px solid #cbd5e1;
    }

    .btn-reset-custom:hover {
        background: #e2e8f0;
        color: #1e293b;
        border-color: #94a3b8;
    }

    .btn-indigo {
        background-color: #6366f1;
        color: white;
        border: none;
    }

    .btn-indigo:hover {
        background-color: #4f46e5;
        color: white;
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
        border-spacing: 0;
        margin-bottom: 0;
    }

    .report-table th {
        background-color: #f9fafb;
        color: #4b5563;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 1rem;
        border-bottom: 2px solid #f3f4f6;
        white-space: nowrap;
    }

    .report-table td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f3f4f6;
        font-size: 0.9rem;
    }

    .report-table tr:last-child td {
        border-bottom: none;
    }

    .stockorder-pill {
        background: #eff6ff;
        color: #1d4ed8;
        padding: 0.25rem 0.6rem;
        border-radius: 6px;
        font-weight: 700;
        font-size: 0.8rem;
        border: 1px solid #bfdbfe;
        display: inline-block;
    }

    .qty-badge {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        color: #111827;
        padding: 0.25rem 0.5rem;
        border-radius: 6px;
        font-weight: 600;
    }

    .failure-badge {
        padding: 0.25rem 0.5rem;
        border-radius: 6px;
        font-weight: 700;
    }

    .failure-low {
        background-color: #d1fae5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }

    .failure-medium {
        background-color: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
    }

    .failure-high {
        background-color: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    /* Expand button */
    .btn-expand {
        padding: 0.35rem 0.75rem;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.8rem;
        transition: all 0.2s;
    }

    /* Sub table styling */
    .sub-table-header {
        background-color: #f3f4f6 !important;
        font-size: 0.75rem !important;
    }

    .sub-table th, .sub-table td {
        white-space: nowrap !important;
        vertical-align: middle !important;
    }

    .sub-table td {
        padding: 0.65rem 1rem !important;
        font-size: 0.85rem !important;
    }

    @media (max-width: 768px) {
        .report-container {
            padding: 0.75rem 0.5rem;
        }
        .report-header-card {
            padding: 1.25rem 1rem;
            border-radius: 16px;
        }
        .filter-card {
            padding: 1rem;
            border-radius: 14px;
        }
        .table-responsive-wrapper {
            padding: 0.75rem;
            border-radius: 14px;
        }
        .form-control, .form-select, .btn-action-custom {
            height: 42px;
            font-size: 0.88rem;
        }
    }
</style>

<div class="report-container" data-routurl="<?php echo $prefix; ?>" data-projectid="<?php echo $projectid; ?>">
    <!-- Header Card -->
    <div class="report-header-card">
        <div>
            <h3><i class="bi bi-exclamation-triangle-fill me-2"></i>Sowing Germination Failure Report</h3>
            <p class="mb-0 opacity-75 small">Analyze germination failure rates tracked production stock order wise.</p>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="filter-card">
        <form id="filterForm">
            <!-- Row 1: Primary Dimensions -->
            <div class="row g-3">
                <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                    <div class="form-group-custom">
                        <label class="form-label-custom"><i class="bi bi-calendar-event text-danger"></i> Order Year</label>
                        <select id="filter_year" class="form-select">
                            <option value="">Loading...</option>
                        </select>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-md-3 col-lg-3">
                    <div class="form-group-custom">
                        <label class="form-label-custom"><i class="bi bi-geo-alt text-primary"></i> Location</label>
                        <select id="filter_location" class="form-select">
                            <option value="">All Locations</option>
                        </select>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-md-3 col-lg-4">
                    <div class="form-group-custom">
                        <label class="form-label-custom"><i class="bi bi-houses text-success"></i> Polyhouse Name</label>
                        <select id="filter_polyhouse" class="form-select">
                            <option value="">All Polyhouses</option>
                        </select>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-md-3 col-lg-3">
                    <div class="form-group-custom">
                        <label class="form-label-custom"><i class="bi bi-grid-3x3 text-warning"></i> Bed</label>
                        <select id="filter_bed" class="form-select">
                            <option value="" selected>All Beds</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Row 2: Search, Date Range & Action Buttons -->
            <div class="row g-3 mt-1 align-items-end">
                <div class="col-12 col-md-4 col-lg-4">
                    <div class="form-group-custom">
                        <label class="form-label-custom"><i class="bi bi-search text-secondary"></i> Search Order / Product</label>
                        <input type="text" id="searchVal" class="form-control" placeholder="Type stockorder no or product...">
                    </div>
                </div>

                <div class="col-6 col-md-2 col-lg-2">
                    <div class="form-group-custom">
                        <label class="form-label-custom"><i class="bi bi-calendar-range text-info"></i> Date From</label>
                        <input type="date" id="datefrom" class="form-control">
                    </div>
                </div>

                <div class="col-6 col-md-2 col-lg-2">
                    <div class="form-group-custom">
                        <label class="form-label-custom"><i class="bi bi-calendar-range text-info"></i> Date To</label>
                        <input type="date" id="dateto" class="form-control">
                    </div>
                </div>

                <div class="col-12 col-md-4 col-lg-4">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-danger-custom btn-action-custom flex-grow-1" id="btnFilter">
                            <i class="bi bi-search"></i> Filter
                        </button>
                        <button type="button" class="btn btn-reset-custom btn-action-custom flex-grow-1" id="btnReset">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Grid Table -->
    <div class="table-responsive-wrapper">
        <table class="table report-table align-middle" id="failureReportTable">
            <thead>
                <tr>
                    <th style="width: 50px;">S.No.</th>
                    <th>Production Date</th>
                    <th>Production Stockorder No / Product Name</th>
                    <th>Production Order / Total Sowing QTY</th>
                    <th>Expected Delivery Date</th>
                    <th class="text-center">Germination Failure (%)</th>
                    <th style="width: 120px;" class="text-center">Status / Action</th>
                </tr>
            </thead>
            <tbody id="reportTbody">
                <tr>
                    <td colspan="7" class="text-center py-5">
                        <div class="spinner-border text-danger" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script src="<?php echo $prefix; ?>js/xlsx.full.min.js"></script>
<script>
$(document).ready(function() {
    const rooturl = $(".report-container").data("routurl");
    const projectid = $(".report-container").data("projectid");
    let globalPolyhouses = [];

    function loadFilterOptions() {
        $.ajax({
            url: rooturl + "krf-orderandsales/modal/reporting.php?action=GetGerminationFilterOptions",
            type: "POST",
            data: { projectid: projectid },
            dataType: "json",
            success: function(res) {
                if (res.status === 'success') {
                    // 1. Order Year dropdown
                    let yearHtml = '';
                    const currentYear = new Date().getFullYear();
                    let selectedYear = '';
                    if (res.years && res.years.length > 0) {
                        res.years.forEach((yr, idx) => {
                            let isSelected = (yr == currentYear || idx === 0) && !selectedYear;
                            if (isSelected) selectedYear = yr;
                            yearHtml += `<option value="${yr}" ${isSelected ? 'selected' : ''}>${yr}</option>`;
                        });
                    } else {
                        yearHtml = `<option value="${currentYear}" selected>${currentYear}</option>`;
                    }
                    $("#filter_year").html(yearHtml);

                    // 2. Location dropdown
                    let locHtml = '<option value="">All Locations</option>';
                    if (res.locations && res.locations.length > 0) {
                        res.locations.forEach(loc => {
                            locHtml += `<option value="${loc.LocationId}">${loc.LocationName}</option>`;
                        });
                    }
                    $("#filter_location").html(locHtml);

                    // 3. Polyhouses
                    globalPolyhouses = res.polyhouses || [];
                    populatePolyhouses();

                    // Initial Load
                    loadReport();
                } else {
                    loadReport();
                }
            },
            error: function() {
                loadReport();
            }
        });
    }

    function populatePolyhouses() {
        const locId = $("#filter_location").val();
        let phHtml = '<option value="">All Polyhouses</option>';
        
        globalPolyhouses.forEach(ph => {
            if (!locId || ph.locationid == locId) {
                phHtml += `<option value="${ph.polyhouseid}">${ph.polyhousename}</option>`;
            }
        });
        $("#filter_polyhouse").html(phHtml);
        populateBeds();
    }

    function populateBeds() {
        const phId = $("#filter_polyhouse").val();
        let bedHtml = '<option value="" selected>All Beds</option>';

        if (phId) {
            const phObj = globalPolyhouses.find(p => p.polyhouseid == phId);
            if (phObj && phObj.beds && phObj.beds.length > 0) {
                phObj.beds.forEach(bedName => {
                    bedHtml += `<option value="${bedName}">${bedName}</option>`;
                });
            }
        }
        $("#filter_bed").html(bedHtml);
    }

    $("#filter_location").on("change", function() {
        populatePolyhouses();
    });

    $("#filter_polyhouse").on("change", function() {
        populateBeds();
    });

    function loadReport() {
        $("#reportTbody").html(`
            <tr>
                <td colspan="7" class="text-center py-5">
                    <div class="spinner-border text-danger" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </td>
            </tr>
        `);

        const year = $("#filter_year").val();
        const locationid = $("#filter_location").val();
        const polyhouseid = $("#filter_polyhouse").val();
        const bed_no = $("#filter_bed").val();
        const datefrom = $("#datefrom").val();
        const dateto = $("#dateto").val();

        $.ajax({
            url: rooturl + "krf-orderandsales/modal/reporting.php?action=GetSowingGerminationFailureList",
            type: "POST",
            data: { 
                projectid: projectid, 
                year: year,
                locationid: locationid,
                polyhouseid: polyhouseid,
                bed_no: bed_no,
                datefrom: datefrom, 
                dateto: dateto 
            },
            dataType: "json",
            success: function(res) {
                if (res.status === 'success') {
                    renderTable(res.details);
                } else {
                    $("#reportTbody").html(`<tr><td colspan="7" class="text-center text-danger py-4">${res.message || 'Error loading report data'}</td></tr>`);
                }
            },
            error: function() {
                $("#reportTbody").html(`<tr><td colspan="7" class="text-center text-danger py-4">Network error loading report data.</td></tr>`);
            }
        });
    }

    function renderTable(details) {
        if (!details || details.length === 0) {
            $("#reportTbody").html(`<tr><td colspan="7" class="text-center text-muted py-5"><i class="bi bi-info-circle fa-2x mb-2 d-block"></i> No records found for the selected criteria.</td></tr>`);
            return;
        }

        let html = '';
        const search = ($("#searchVal").val() || '').trim().toLowerCase();

        details.forEach(item => {
            // Apply client-side search filter
            if (search !== '') {
                const stockMatch = (item.stockorder_no || '').toLowerCase().includes(search);
                const productMatch = (item.product_name || '').toLowerCase().includes(search);
                if (!stockMatch && !productMatch) return;
            }

            // Style failure badge based on percentage
            let badgeClass = 'failure-low';
            if (item.fail_percent >= 15) {
                badgeClass = 'failure-high';
            } else if (item.fail_percent >= 5) {
                badgeClass = 'failure-medium';
            }

            html += `
                <tr id="row_${item.stockorder_no}">
                    <td>${item.sn}</td>
                    <td>${item.production_date}</td>
                    <td>
                        <span class="stockorder-pill mb-1">${item.stockorder_no}</span>
                        <div class="fw-bold text-dark" style="font-size:0.95rem;">${item.product_name}</div>
                    </td>
                    <td>
                        <div class="mb-1"><span class="text-muted small">Order:</span> <span class="qty-badge">${item.order_qty}</span></div>
                        <div><span class="text-muted small">Sowing:</span> <span class="qty-badge">${item.total_sowing_qty}</span></div>
                    </td>
                    <td>${item.expected_delivery_date}</td>
                    <td class="text-center">
                        <span class="failure-badge ${badgeClass}">${item.fail_percent}%</span>
                        <div class="text-muted small mt-1" style="font-size:0.75rem;">${item.failed_plants} / ${item.fail_trays_capacity} Plants</div>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-indigo btn-expand" data-id="${item.stockorder_no}">
                            Expand <i class="bi bi-chevron-down ms-1"></i>
                        </button>
                    </td>
                </tr>
                <tr id="details_${item.stockorder_no}" class="details-row" style="display: none;">
                    <td colspan="7" class="p-3 bg-light">
                        <div class="expand-details-wrapper p-3 border rounded bg-white shadow-sm">
                            <div class="text-center py-3 loading-details">
                                <div class="spinner-border spinner-border-sm text-indigo" role="status">
                                    <span class="visually-hidden">Loading details...</span>
                                </div>
                            </div>
                            <div class="details-content" style="display: none;">
                                <!-- Expand details table loaded dynamically -->
                            </div>
                        </div>
                    </td>
                </tr>
            `;
        });

        if (html === '') {
            $("#reportTbody").html(`<tr><td colspan="7" class="text-center text-muted py-5"><i class="bi bi-info-circle fa-2x mb-2 d-block"></i> No records match the search keyword.</td></tr>`);
        } else {
            $("#reportTbody").html(html);
        }
    }

    // Expand/Collapse Row Handler
    $(document).on("click", ".btn-expand", function() {
        const orderNo = $(this).data("id");
        const btn = $(this);
        const detailsRow = $(`#details_${orderNo}`);
        const wrapper = detailsRow.find(".expand-details-wrapper");

        if (detailsRow.is(":visible")) {
            detailsRow.slideUp(200);
            btn.html(`Expand <i class="bi bi-chevron-down ms-1"></i>`);
        } else {
            // Re-fetch details to apply active filters
            detailsRow.show();
            wrapper.find(".loading-details").show();
            wrapper.find(".details-content").hide();

            const year = $("#filter_year").val();
            const locationid = $("#filter_location").val();
            const polyhouseid = $("#filter_polyhouse").val();
            const bed_no = $("#filter_bed").val();
            const datefrom = $("#datefrom").val();
            const dateto = $("#dateto").val();

            $.ajax({
                url: rooturl + "krf-orderandsales/modal/reporting.php?action=GetSowingGerminationFailureDetails",
                type: "POST",
                data: { 
                    projectid: projectid, 
                    stockorder_no: orderNo,
                    year: year,
                    locationid: locationid,
                    polyhouseid: polyhouseid,
                    bed_no: bed_no,
                    datefrom: datefrom,
                    dateto: dateto
                },
                dataType: "json",
                success: function(res) {
                    wrapper.find(".loading-details").hide();
                    if (res.status === 'success' && res.details.length > 0) {
                        let tableHtml = `
                            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                                <h6 class="fw-bold mb-0 text-indigo"><i class="bi bi-info-circle-fill me-1"></i> Sowing Batch Wise Germination Failure Details</h6>
                                <button type="button" class="btn btn-sm btn-success fw-bold d-inline-flex align-items-center gap-1.5 shadow-sm btn-export-batch-excel" data-orderno="${orderNo}" style="border-radius: 8px; padding: 5px 14px;">
                                    <i class="bi bi-file-earmark-excel-fill"></i> Export Excel
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered sub-table mb-0 align-middle">
                                    <thead>
                                        <tr class="sub-table-header">
                                            <th>Seed Lot No</th>
                                            <th>Sowing Date</th>
                                            <th>Root Type</th>
                                            <th>Polyhouse / Bed</th>
                                            <th class="text-center">Total Plants / Trays</th>
                                            <th class="text-center">Failed Plants / Trays</th>
                                            <th class="text-center">Germination (Failure %)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;
                        res.details.forEach(det => {
                            let subBadge = 'failure-low';
                            if (det.fail_percent >= 15) {
                                subBadge = 'failure-high';
                            } else if (det.fail_percent >= 5) {
                                subBadge = 'failure-medium';
                            }

                            let seedLotNo = det.lotno && det.lotno !== '' ? det.lotno : (det.sowinglotno_internal || 'N/A');

                            let failedColHtml = (det.failed_plants > 0 || det.failed_trays > 0)
                                ? `<div class="d-inline-flex align-items-center justify-content-center gap-2"><span class="fw-bold text-danger">${det.failed_plants.toLocaleString()}</span> <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">${det.failed_trays} Trays</span></div>`
                                : `<div class="d-inline-flex align-items-center justify-content-center gap-2"><span class="text-muted">0</span> <span class="badge bg-light text-muted border px-2 py-1">0 Trays</span></div>`;

                            tableHtml += `
                                <tr>
                                    <td class="fw-bold text-indigo">${seedLotNo}</td>
                                    <td>${det.sowingdate_internal}</td>
                                    <td><span class="badge bg-secondary text-white">${det.roottype}</span></td>
                                    <td>${det.polyhousename} / <span class="fw-bold text-primary">${det.bed_no}</span></td>
                                    <td class="text-center">
                                        <div class="d-inline-flex align-items-center justify-content-center gap-2">
                                            <span class="fw-bold text-dark">${det.total_plants.toLocaleString()}</span>
                                            <span class="badge bg-light text-secondary border px-2 py-1">${det.total_trays} Trays</span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        ${failedColHtml}
                                    </td>
                                    <td class="text-center">
                                        <div class="d-inline-flex align-items-center justify-content-center gap-3">
                                            <span class="fw-semibold ${det.failed_plants > 0 ? 'text-danger' : 'text-muted'} pe-1">${det.failed_plants.toLocaleString()} / ${det.fail_trays_capacity.toLocaleString()}</span>
                                            <span class="failure-badge ${subBadge}">${det.fail_percent}%</span>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        });
                        tableHtml += `
                                    </tbody>
                                </table>
                            </div>
                        `;
                        wrapper.find(".details-content").html(tableHtml).show();
                        wrapper.find(".btn-export-batch-excel").data("details", res.details);
                        btn.html(`Collapse <i class="bi bi-chevron-up ms-1"></i>`);
                    } else {
                        wrapper.find(".details-content").html(`<div class="text-center text-muted py-3">No sowing failure details found.</div>`).show();
                        btn.html(`Collapse <i class="bi bi-chevron-up ms-1"></i>`);
                    }
                },
                error: function() {
                    wrapper.find(".loading-details").hide();
                    wrapper.find(".details-content").html(`<div class="text-center text-danger py-3">Error fetching details from database.</div>`).show();
                    btn.html(`Collapse <i class="bi bi-chevron-up ms-1"></i>`);
                }
            });
        }
    });

    // Export Batch Details to Excel Handler
    $(document).on("click", ".btn-export-batch-excel", function() {
        const orderNo = $(this).data("orderno") || 'Batch';
        const details = $(this).data("details");
        if (!details || details.length === 0) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'info', title: 'No Data', text: 'No batch details available to export.' });
            } else {
                alert('No batch details available to export.');
            }
            return;
        }

        const exportRows = details.map((d, idx) => ({
            "S.No.": idx + 1,
            "Stock Order No": orderNo,
            "Seed Lot No": d.lotno && d.lotno !== '' ? d.lotno : (d.sowinglotno_internal || 'N/A'),
            "Sowing Date": d.sowingdate_internal,
            "Root Type": d.roottype,
            "Polyhouse": d.polyhousename,
            "Bed No": d.bed_no,
            "Total Plants": d.total_plants,
            "Total Trays": d.total_trays,
            "Failed Plants": d.failed_plants,
            "Failed Trays": d.failed_trays,
            "Plants of Failed Trays": d.fail_trays_capacity,
            "Failure %": d.fail_percent + "%"
        }));

        const dateStr = new Date().toISOString().slice(0, 10);
        const filename = `Germination_Failure_Details_${orderNo}_${dateStr}`;

        if (typeof XLSX !== 'undefined') {
            const ws = XLSX.utils.json_to_sheet(exportRows);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "Failure Details");
            XLSX.writeFile(wb, `${filename}.xlsx`);
        } else {
            let headers = Object.keys(exportRows[0]);
            let csvContent = "\uFEFF" + headers.join(",") + "\r\n";
            exportRows.forEach(row => {
                let rowVals = headers.map(h => `"${String(row[h] !== undefined && row[h] !== null ? row[h] : '').replace(/"/g, '""')}"`);
                csvContent += rowVals.join(",") + "\r\n";
            });
            let blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
            let url = URL.createObjectURL(blob);
            let link = document.createElement("a");
            link.setAttribute("href", url);
            link.setAttribute("download", `${filename}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Export Downloaded',
                text: `Exported ${details.length} batch record(s) to Excel successfully!`,
                timer: 2000,
                showConfirmButton: false
            });
        }
    });

    // Apply Filter click
    $("#btnFilter").click(function() {
        loadReport();
    });

    // Reset Form Click
    $("#btnReset").click(function() {
        $("#filterForm")[0].reset();
        $("#filter_location").val('').trigger('change');
        $("#filter_polyhouse").val('').trigger('change');
        $("#filter_bed").val('');
        loadReport();
    });

    // Quick client-side filter on search input
    $("#searchVal").on("keyup", function() {
        const search = $(this).val().trim().toLowerCase();
        $("#reportTbody tr[id^='row_']").each(function() {
            const rowText = $(this).text().toLowerCase();
            const orderNo = $(this).attr('id').replace('row_', '');
            if (rowText.includes(search)) {
                $(this).show();
            } else {
                $(this).hide();
                $(`#details_${orderNo}`).hide();
            }
        });
    });

    // Load filter options on startup
    loadFilterOptions();
});
</script>
<?php
include('../footer.php');
?>
