<?php
include('../header.php');
date_default_timezone_set("Asia/Calcutta");
?>
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

<style>
    /* Premium Table Header color matching screenshot */
    #dispatchTableHead th {
        background-color: #23b185 !important; /* Vibrant teal/green from screenshot */
        color: white !important;
        text-align: left;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 13px;
        letter-spacing: 0.5px;
        vertical-align: middle;
        border: none !important;
    }

    #dispatchplandetails {
        border-collapse: collapse;
        width: 100%;
        min-width: 1200px; /* Ensures columns stay properly aligned and scroll horizontally instead of clipping */
    }

    #dispatchplandetails td {
        vertical-align: middle;
        font-size: 14px;
        padding: 12px 10px;
    }
    
    .table-responsive {
        border-radius: 8px;
        overflow-x: auto; /* Enable horizontal scrolling on smaller viewports */
        border: 1px solid #dee2e6;
    }
    
    .table-hover tbody tr:hover {
        background-color: rgba(35, 177, 133, 0.05) !important;
    }

    /* Missing location badge/button styling */
    .location-missing-btn {
        background: #a1c4fd;
        color: #0b3d75;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        text-align: center;
        display: inline-block;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    /* Week navigator buttons matching colors from screenshot */
    #dispatchPrevWeekBtn {
        background-color: #f3a614 !important; /* Orange/Yellow from screenshot */
        color: black !important;
        border: none;
        padding: 8px 16px;
        font-weight: 600;
        border-radius: 6px;
        transition: background-color 0.2s;
    }
    #dispatchPrevWeekBtn:hover {
        background-color: #d9910d !important;
    }

    #dispatchNextWeekBtn {
        background-color: #0c8c5e !important; /* Green from screenshot */
        color: white !important;
        border: none;
        padding: 8px 16px;
        font-weight: 600;
        border-radius: 6px;
        transition: background-color 0.2s;
    }
    #dispatchNextWeekBtn:hover {
        background-color: #096d49 !important;
    }

    #btnMarkDispatched {
        background-color: #0c8c5e !important;
        color: white !important;
        border: none;
        padding: 8px 16px;
        font-weight: 600;
        border-radius: 6px;
        transition: opacity 0.2s, background-color 0.2s;
    }
    #btnMarkDispatched:hover:not(:disabled) {
        background-color: #096d49 !important;
    }
    #btnMarkDispatched:disabled {
        opacity: 0.65;
        cursor: not-allowed;
    }

    #dispatchTableHead th.text-center {
        text-align: center !important;
    }

    /* Select controls */
    .form-control, .form-select {
        border-radius: 6px;
        border: 1px solid #ced4da;
        padding: 8px 12px;
        font-weight: 500;
    }

    /* Date display format styling */
    .date-highlight {
        font-weight: 500;
    }

    /* Custom Checkbox Styling to match modern look */
    .form-check-input {
        border-radius: 4px;
        border: 1.5px solid #23b185;
        width: 18px;
        height: 18px;
        cursor: pointer;
    }
    .form-check-input:checked {
        background-color: #0c8c5e !important;
        border-color: #0c8c5e !important;
    }
    .form-check-input:focus {
        border-color: #23b185;
        box-shadow: 0 0 0 0.25rem rgba(35, 177, 133, 0.25);
    }

    /* Reset global scaled/margin checkbox styles from header.php inside our dispatch table to prevent overlapping */
    #dispatchplandetails input[type="checkbox"].form-check-input {
        transform: none !important;
        -webkit-transform: none !important;
        -ms-transform: none !important;
        margin: 0 !important;
        position: relative !important;
        display: inline-block !important;
    }

    /* Enforce strict width and centering on checkbox column cells */
    #dispatchplandetails th:first-child,
    #dispatchplandetails td:first-child {
        width: 50px !important;
        min-width: 50px !important;
        max-width: 50px !important;
        text-align: center !important;
        vertical-align: middle !important;
    }

    /* Responsive title sizing */
    @media (max-width: 768px) {
        h3.page-title {
            font-size: 18px !important;
        }
    }

    /* Color styles for row highlight based on week status & pending boxes */
    #dispatchplandetails tr.row-pending-alert > td,
    .row-pending-alert {
        background-color: #fde8e8 !important; /* light red */
    }
    #dispatchplandetails tr.row-pending-alert:hover > td {
        background-color: #fcd4d4 !important;
    }
    .badge-pending-alert {
        background-color: #f8d7da !important; /* light red */
        color: #842029 !important; /* dark red */
        border: 1px solid #f5c2c7 !important;
    }
    .row-past-due {
        background-color: #fcebeb !important; /* light red/pink */
    }
    .row-current-week {
        background-color: #fff8e1 !important; /* light yellow */
    }
    .row-next-week {
        background-color: #e8f9f0 !important; /* light green */
    }

    /* Box Status Filter Cards */
    .status-filter-card {
        position: relative;
        border-radius: 12px;
        padding: 14px 18px;
        cursor: pointer;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        border: 2px solid transparent;
        background: #ffffff;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.04);
        user-select: none;
    }
    .status-filter-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 14px rgba(0, 0, 0, 0.08);
    }
    .card-status-title {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: block;
        margin-bottom: 2px;
    }
    .card-status-value {
        font-size: 24px;
        font-weight: 800;
        line-height: 1.1;
    }
    .card-status-sub {
        font-size: 11px;
        font-weight: 600;
        opacity: 0.85;
    }
    .card-status-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }

    /* 1. Total Boxes Card */
    .status-filter-card.card-total {
        background-color: #f0f4ff;
        border-color: #dbeafe;
    }
    .status-filter-card.card-total .card-status-title { color: #1e40af; }
    .status-filter-card.card-total .card-status-value { color: #1d4ed8; }
    .status-filter-card.card-total .card-status-sub { color: #3b82f6; }
    .status-filter-card.card-total .card-status-icon {
        background-color: #dbeafe;
        color: #1d4ed8;
    }
    .status-filter-card.card-total.active {
        border-color: #2563eb !important;
        background-color: #e0e7ff !important;
        animation: pulseTotal 2s infinite ease-in-out;
    }

    /* 2. Allocated Card */
    .status-filter-card.card-allocated {
        background-color: #ecfdf5;
        border-color: #d1fae5;
    }
    .status-filter-card.card-allocated .card-status-title { color: #065f46; }
    .status-filter-card.card-allocated .card-status-value { color: #047857; }
    .status-filter-card.card-allocated .card-status-sub { color: #059669; }
    .status-filter-card.card-allocated .card-status-icon {
        background-color: #d1fae5;
        color: #047857;
    }
    .status-filter-card.card-allocated.active {
        border-color: #059669 !important;
        background-color: #dcfce7 !important;
        animation: pulseAllocated 2s infinite ease-in-out;
    }

    /* 3. Received at Polyhouse Card */
    .status-filter-card.card-received {
        background-color: #f0fdfa;
        border-color: #ccfbf1;
    }
    .status-filter-card.card-received .card-status-title { color: #0f766e; }
    .status-filter-card.card-received .card-status-value { color: #0891b2; }
    .status-filter-card.card-received .card-status-sub { color: #0e7490; }
    .status-filter-card.card-received .card-status-icon {
        background-color: #ccfbf1;
        color: #0891b2;
    }
    .status-filter-card.card-received.active {
        border-color: #0891b2 !important;
        background-color: #cffafe !important;
        animation: pulseReceived 2s infinite ease-in-out;
    }

    /* 4. Pending Card */
    .status-filter-card.card-pending {
        background-color: #fef2f2;
        border-color: #fee2e2;
    }
    .status-filter-card.card-pending .card-status-title { color: #991b1b; }
    .status-filter-card.card-pending .card-status-value { color: #dc2626; }
    .status-filter-card.card-pending .card-status-sub { color: #b91c1c; }
    .status-filter-card.card-pending .card-status-icon {
        background-color: #fee2e2;
        color: #dc2626;
    }
    .status-filter-card.card-pending.active {
        border-color: #dc2626 !important;
        background-color: #fde8e8 !important;
        animation: pulsePending 2s infinite ease-in-out;
    }

    /* 5. Old Pending Card */
    .status-filter-card.card-oldpending {
        background-color: #faf5ff;
        border-color: #f3e8ff;
    }
    .status-filter-card.card-oldpending .card-status-title { color: #6b21a8; }
    .status-filter-card.card-oldpending .card-status-value { color: #7e22ce; }
    .status-filter-card.card-oldpending .card-status-sub { color: #9333ea; }
    .status-filter-card.card-oldpending .card-status-icon {
        background-color: #f3e8ff;
        color: #7e22ce;
    }
    .status-filter-card.card-oldpending.active {
        border-color: #7e22ce !important;
        background-color: #f5f3ff !important;
        animation: pulseOldPending 2s infinite ease-in-out;
    }

    /* Pulse Border Glow Keyframe Animations */
    @keyframes pulseTotal {
        0%, 100% { box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.35), 0 4px 10px rgba(37, 99, 235, 0.15); }
        50% { box-shadow: 0 0 0 6px rgba(37, 99, 235, 0.65), 0 6px 16px rgba(37, 99, 235, 0.25); }
    }
    @keyframes pulseAllocated {
        0%, 100% { box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.35), 0 4px 10px rgba(5, 150, 105, 0.15); }
        50% { box-shadow: 0 0 0 6px rgba(5, 150, 105, 0.65), 0 6px 16px rgba(5, 150, 105, 0.25); }
    }
    @keyframes pulseReceived {
        0%, 100% { box-shadow: 0 0 0 3px rgba(8, 145, 178, 0.35), 0 4px 10px rgba(8, 145, 178, 0.15); }
        50% { box-shadow: 0 0 0 6px rgba(8, 145, 178, 0.65), 0 6px 16px rgba(8, 145, 178, 0.25); }
    }
    @keyframes pulsePending {
        0%, 100% { box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.35), 0 4px 10px rgba(220, 38, 38, 0.15); }
        50% { box-shadow: 0 0 0 6px rgba(220, 38, 38, 0.65), 0 6px 16px rgba(220, 38, 38, 0.25); }
    }
    @keyframes pulseOldPending {
        0%, 100% { box-shadow: 0 0 0 3px rgba(126, 34, 206, 0.35), 0 4px 10px rgba(126, 34, 206, 0.15); }
        50% { box-shadow: 0 0 0 6px rgba(126, 34, 206, 0.65), 0 6px 16px rgba(126, 34, 206, 0.25); }
    }
</style>

<div class="container-fluid my-4">
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h3 class="mb-0 text-dark font-weight-bold page-title" style="font-size: 24px; white-space: nowrap;">
                    Weekly Germination Box Opening Schedule
                </h3>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary" onclick="window.location.reload();">
                        <i class="fa fa-sync"></i> Refresh
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <!-- Unified strip layout for selectors and buttons -->
            <div class="d-flex flex-wrap align-items-end gap-3 mb-3 p-3 bg-light rounded-3 shadow-sm border">
                <div style="min-width: 140px; flex: 0.8;">
                    <label class="form-label fw-bold text-secondary small text-uppercase mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Select Year</label>
                    <select id="dispatchYearFilter" class="form-select" style="height: 42px;"></select>
                </div>
                <div style="min-width: 180px; flex: 1;">
                    <label class="form-label fw-bold text-secondary small text-uppercase mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Select Month</label>
                    <select id="dispatchMonthFilter" class="form-select" style="height: 42px;"></select>
                </div>
                <div style="min-width: 250px; flex: 1.5;">
                    <label class="form-label fw-bold text-secondary small text-uppercase mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Select Week</label>
                    <select id="dispatchWeekSelector" class="form-select" style="height: 42px;"></select>
                </div>
                <div class="d-flex gap-2 flex-wrap ms-md-auto align-items-center mt-2 mt-md-0">
                    <button class="btn btn-success" id="exportExcelBtn" style="height: 42px;">
                        <i class="fa fa-file-excel text-white me-1"></i> Export Excel
                    </button>
                    <button class="btn" id="dispatchPrevWeekBtn" style="height: 42px;">← Previous Week</button>
                    <button class="btn" id="dispatchNextWeekBtn" style="height: 42px;">Next Week →</button>
                </div>
            </div>
            
            <!-- Box Status Summary & Filter Cards -->
            <div class="row g-3 mb-3" id="boxStatusCardsRow">
                <div class="col-12 col-sm-6 col-md-4 col-xl" style="min-width: 200px;">
                    <div class="status-filter-card card-total active" data-filter="all">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="card-status-title">Total Boxes</span>
                                <h3 class="card-status-value mb-0" id="cardTotalBoxes">0</h3>
                                <span class="card-status-sub" id="cardTotalTrays">0 Trays</span>
                            </div>
                            <div class="card-status-icon">
                                <i class="fa fa-cubes"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-xl" style="min-width: 200px;">
                    <div class="status-filter-card card-allocated" data-filter="allocated">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="card-status-title">Allocated</span>
                                <h3 class="card-status-value mb-0" id="cardAllocatedBoxes">0</h3>
                                <span class="card-status-sub">Boxes Allocated</span>
                            </div>
                            <div class="card-status-icon">
                                <i class="fa fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-xl" style="min-width: 200px;">
                    <div class="status-filter-card card-received" data-filter="receivedatpolyhouse">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="card-status-title">Received at Polyhouse</span>
                                <h3 class="card-status-value mb-0" id="cardReceivedBoxes">0</h3>
                                <span class="card-status-sub">Ready for Move</span>
                            </div>
                            <div class="card-status-icon">
                                <i class="fa fa-warehouse"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-xl" style="min-width: 200px;">
                    <div class="status-filter-card card-pending" data-filter="pending">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="card-status-title">Pending</span>
                                <h3 class="card-status-value mb-0" id="cardPendingBoxes">0</h3>
                                <span class="card-status-sub">Awaiting Move</span>
                            </div>
                            <div class="card-status-icon">
                                <i class="fa fa-clock"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-xl" style="min-width: 200px;">
                    <div class="status-filter-card card-oldpending" data-filter="oldpending">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="card-status-title">Old Pending</span>
                                <h3 class="card-status-value mb-0" id="cardOldPendingBoxes">0</h3>
                                <span class="card-status-sub" id="cardOldPendingTrays">Prior Weeks</span>
                            </div>
                            <div class="card-status-icon">
                                <i class="fa fa-history"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-2">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="dispatchplandetails">
                            <thead id="dispatchTableHead">
                                <tr>
                                    <th class="text-center" style="width: 50px; vertical-align: middle;">S.No</th>
                                    <th>Sowing Location</th>
                                    <th>Polyhouse Name</th>
                                    <th>Order No & Product Name</th>
                                    <th>Packing Date</th>
                                    <th>Opening Date</th>
                                    <th>Variety of Seed</th>
                                    <th>QTY</th>
                                    <th>Allocation / Received</th>
                                    <th>Box Status</th>
                                </tr>
                            </thead>
                            <tbody id="scheduleTable">
                                <tr>
                                    <td colspan="10" class="text-center py-4 text-muted">
                                        Loading schedule...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('../footer.php'); ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script type="text/javascript" src="<?php echo $prefix; ?>krf-orderandsales/js/pjs_function.js"></script>

<script>
$(document).ready(function() {
    let globalWeekMap = {};
    let globalSortedWeeks = [];
    let globalMonths = [];
    let currentRawData = [];
    let currentWeekRange = "";
    let currentFilter = "all";
    loadActiveDispatchDates();
    
    function loadActiveDispatchDates() {
        let projectid = $("body").data("projectid");
        
        $.ajax({
            url: "modal/timetable_modal.php?action=GetActiveGerminationBoxOpeningDates",
            type: "POST",
            data: { projectid: projectid },
            dataType: "json",
            success: function(dates) {
                if (!Array.isArray(dates)) dates = [];
                
                // Map dates to Mondays (week starts)
                let weekMap = {};
                dates.forEach(item => {
                    if (!item.frsowingdate && !item.boxopeningdate) return;
                    let dateVal = item.boxopeningdate || item.frsowingdate;
                    let d = new Date(dateVal);
                    let day = d.getDay();
                    let diffToMonday = (day === 0) ? -6 : (1 - day);
                    let monday = new Date(d);
                    monday.setDate(d.getDate() + diffToMonday);
                    monday.setHours(0, 0, 0, 0);

                    let sunday = new Date(monday);
                    sunday.setDate(monday.getDate() + 6);
                    sunday.setHours(23, 59, 59, 999);

                    let ws = monday.toISOString().split("T")[0];
                    let we = sunday.toISOString().split("T")[0];
                    weekMap[ws] = we;
                });
                
                let today = new Date();
                let curYearStr = today.getFullYear().toString();
                let curMonthStr = today.toISOString().substring(0, 7); // yyyy-mm
                
                let tDay = today.getDay();
                let tDiff = (tDay === 0) ? -6 : (1 - tDay);
                let tMon = new Date(today);
                tMon.setDate(today.getDate() + tDiff);
                let tSun = new Date(tMon);
                tSun.setDate(tMon.getDate() + 6);
                let tWS = tMon.toISOString().split("T")[0];
                let tWE = tSun.toISOString().split("T")[0];
                let currentWeekRange = `${tWS} to ${tWE}`;
                
                if (!weekMap[tWS]) {
                    weekMap[tWS] = tWE;
                }
                
                let sortedWeeks = Object.keys(weekMap).sort();
                globalWeekMap = weekMap;
                globalSortedWeeks = sortedWeeks;
                
                // 1. Extract Unique Years & default to current year
                let years = new Set();
                sortedWeeks.forEach(ws => years.add(ws.substring(0, 4)));
                years.add(curYearStr);
                
                $("#dispatchYearFilter").html("");
                Array.from(years).sort().forEach(y => {
                    $("#dispatchYearFilter").append(`<option value="${y}">${y}</option>`);
                });
                $("#dispatchYearFilter").val(curYearStr);
                
                // 2. Extract Unique Months
                let months = new Set();
                sortedWeeks.forEach(ws => months.add(ws.substring(0, 7)));
                months.add(curMonthStr);
                globalMonths = Array.from(months).sort();

                // Populate Months for Current Year
                populateMonths(curYearStr, curMonthStr, currentWeekRange);
                
                // Setup Year change event
                $("#dispatchYearFilter").off("change").on("change", function() {
                    let selYear = $(this).val();
                    let todayM = new Date().toISOString().substring(0, 7);
                    let defMonth = (selYear === curYearStr) ? todayM : "first";
                    populateMonths(selYear, defMonth, "first");
                });

                // Setup Month change event
                $("#dispatchMonthFilter").off("change").on("change", function() {
                    populateWeeks(globalWeekMap, globalSortedWeeks, "first");
                });
            }
        });
    }

    function populateMonths(selectedYear, defaultMonth, defaultWeek) {
        let monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        let filteredMonths = globalMonths.filter(m => m.startsWith(selectedYear));
        
        // If no months found for that year in data, generate all 12 months
        if (filteredMonths.length === 0) {
            for (let i = 1; i <= 12; i++) {
                let miStr = (i < 10 ? '0' + i : '' + i);
                filteredMonths.push(`${selectedYear}-${miStr}`);
            }
        }
        
        $("#dispatchMonthFilter").html("");
        filteredMonths.forEach(m => {
            let [y, mi] = m.split("-");
            let mName = monthNames[parseInt(mi) - 1];
            let display = `${mName}-${y}`;
            $("#dispatchMonthFilter").append(`<option value="${m}">${display}</option>`);
        });
        
        let selMonth = defaultMonth;
        if (selMonth === "first" && filteredMonths.length > 0) {
            selMonth = filteredMonths[0];
        } else if (selMonth === "last" && filteredMonths.length > 0) {
            selMonth = filteredMonths[filteredMonths.length - 1];
        } else if ($("#dispatchMonthFilter").find(`option[value="${selMonth}"]`).length === 0) {
            selMonth = filteredMonths[0];
        }
        
        $("#dispatchMonthFilter").val(selMonth);
        populateWeeks(globalWeekMap, globalSortedWeeks, defaultWeek);
    }

    function populateWeeks(weekMap, sortedWeeks, defaultWeek) {
        let selectedMonth = $("#dispatchMonthFilter").val();
        let displayWeeks = sortedWeeks.filter(ws => {
            if (selectedMonth === "all") return true;
            return ws.substring(0, 7) === selectedMonth;
        });
        
        let $week = $("#dispatchWeekSelector");
        $week.html("");
        displayWeeks.forEach(ws => {
            let range = `${ws} to ${weekMap[ws]}`;
            $week.append(`<option value="${range}">${range}</option>`);
        });
        
        let selectedWeek = defaultWeek;
        if (selectedWeek === "current" || selectedWeek === "first" || selectedWeek === "last") {
            let today = new Date();
            let tDay = today.getDay();
            let tDiff = (tDay === 0) ? -6 : (1 - tDay);
            let tMon = new Date(today);
            tMon.setDate(today.getDate() + tDiff);
            let tSun = new Date(tMon);
            tSun.setDate(tMon.getDate() + 6);
            let tWS = tMon.toISOString().split("T")[0];
            let tWE = tSun.toISOString().split("T")[0];
            let currentWeekRange = `${tWS} to ${tWE}`;
            
            if (selectedWeek === "current") {
                selectedWeek = currentWeekRange;
            } else if (selectedWeek === "first" && displayWeeks.length > 0) {
                selectedWeek = `${displayWeeks[0]} to ${weekMap[displayWeeks[0]]}`;
            } else if (selectedWeek === "last" && displayWeeks.length > 0) {
                let lastIdx = displayWeeks.length - 1;
                selectedWeek = `${displayWeeks[lastIdx]} to ${weekMap[displayWeeks[lastIdx]]}`;
            }
        }
        
        if ($week.find(`option[value="${selectedWeek}"]`).length === 0) {
            if (displayWeeks.length > 0) {
                selectedWeek = `${displayWeeks[0]} to ${weekMap[displayWeeks[0]]}`;
            } else {
                let today = new Date();
                let tDay = today.getDay();
                let tDiff = (tDay === 0) ? -6 : (1 - tDay);
                let tMon = new Date(today);
                tMon.setDate(today.getDate() + tDiff);
                let tSun = new Date(tMon);
                tSun.setDate(tMon.getDate() + 6);
                let tWS = tMon.toISOString().split("T")[0];
                let tWE = tSun.toISOString().split("T")[0];
                let currentWeekRange = `${tWS} to ${tWE}`;
                
                $week.append(`<option value="${currentWeekRange}">${currentWeekRange}</option>`);
                selectedWeek = currentWeekRange;
            }
        }
        
        $week.val(selectedWeek);
        loadDispatchSchedule(selectedWeek);
    }

    $("#dispatchPrevWeekBtn").off("click").on("click", function() {
        let select = document.getElementById("dispatchWeekSelector");
        if (select && select.selectedIndex > 0) {
            select.selectedIndex = select.selectedIndex - 1;
            $(select).trigger("change");
        } else {
            // First week of month, move to previous month
            let monthSelect = document.getElementById("dispatchMonthFilter");
            if (monthSelect && monthSelect.selectedIndex > 0) {
                monthSelect.selectedIndex = monthSelect.selectedIndex - 1;
                populateWeeks(globalWeekMap, globalSortedWeeks, "last");
            } else {
                // First month of year, move to previous year
                let yearSelect = document.getElementById("dispatchYearFilter");
                if (yearSelect && yearSelect.selectedIndex > 0) {
                    yearSelect.selectedIndex = yearSelect.selectedIndex - 1;
                    populateMonths($(yearSelect).val(), "last", "last");
                }
            }
        }
    });

    $("#dispatchNextWeekBtn").off("click").on("click", function() {
        let select = document.getElementById("dispatchWeekSelector");
        if (select && select.selectedIndex < select.options.length - 1) {
            select.selectedIndex = select.selectedIndex + 1;
            $(select).trigger("change");
        } else {
            // Last week of month, move to next month
            let monthSelect = document.getElementById("dispatchMonthFilter");
            if (monthSelect && monthSelect.selectedIndex < monthSelect.options.length - 1) {
                monthSelect.selectedIndex = monthSelect.selectedIndex + 1;
                populateWeeks(globalWeekMap, globalSortedWeeks, "first");
            } else {
                // Last month of year, move to next year
                let yearSelect = document.getElementById("dispatchYearFilter");
                if (yearSelect && yearSelect.selectedIndex < yearSelect.options.length - 1) {
                    yearSelect.selectedIndex = yearSelect.selectedIndex + 1;
                    populateMonths($(yearSelect).val(), "first", "first");
                }
            }
        }
    });

    $("#dispatchWeekSelector").off("change").on("change", function() {
        loadDispatchSchedule($(this).val());
    });    function loadDispatchSchedule(weekRange) {
        let projectid = $("body").data("projectid");
        let [datefrom, dateto] = weekRange.split(" to ");
        
        $.ajax({
            url: "modal/timetable_modal.php?action=GetGerminationBoxOpeningData",
            type: "POST",
            data: {
                projectid: projectid,
                datefrom: datefrom,
                dateto: dateto
            },
            dataType: "json",
            success: function(res) {
                currentRawData = res.data ? res.data : (Array.isArray(res) ? res : []);
                currentWeekRange = weekRange;
                let oldPendingBoxes = res.old_pending_boxes !== undefined ? res.old_pending_boxes : 0;
                let oldPendingTrays = res.old_pending_trays !== undefined ? res.old_pending_trays : 0;
                updateStatusCards(currentRawData, oldPendingBoxes, oldPendingTrays);
                applyFilterAndRender();
            }
        });
    }

    function updateStatusCards(data, oldPendingBoxes, oldPendingTrays) {
        let totalBoxes = 0;
        let totalTrays = 0;
        let allocatedBoxes = 0;
        let receivedBoxes = 0;
        let pendingBoxes = 0;

        data.forEach(item => {
            totalBoxes += parseInt(item.box_count || 0);
            totalTrays += parseInt(item.total_trays || 0);
            allocatedBoxes += parseInt(item.allocated_boxes || 0);
            receivedBoxes += parseInt(item.received_polyhouse_boxes || 0);
            pendingBoxes += parseInt(item.pending_boxes || 0);
        });

        $("#cardTotalBoxes").text(totalBoxes);
        $("#cardTotalTrays").text(`${totalTrays} Trays`);
        $("#cardAllocatedBoxes").text(allocatedBoxes);
        $("#cardReceivedBoxes").text(receivedBoxes);
        $("#cardPendingBoxes").text(pendingBoxes);

        if (oldPendingBoxes !== undefined) {
            $("#cardOldPendingBoxes").text(oldPendingBoxes);
            $("#cardOldPendingTrays").text(`${oldPendingTrays || 0} Trays`);
        }
    }

    function loadOldPendingSchedule() {
        let projectid = $("body").data("projectid");
        let selectedWeek = $("#dispatchWeekSelector").val() || "";
        let [datefrom, dateto] = selectedWeek.split(" to ");
        let tbody = $("#scheduleTable");
        tbody.html(`<tr><td colspan="10" class="text-center py-4 text-muted"><i class="fa fa-spinner fa-spin me-2"></i>Loading Old Pending Boxes...</td></tr>`);
        
        $.ajax({
            url: "modal/timetable_modal.php?action=GetGerminationBoxOpeningData",
            type: "POST",
            data: {
                projectid: projectid,
                datefrom: datefrom,
                dateto: dateto,
                is_old_pending: 1
            },
            dataType: "json",
            success: function(res) {
                let oldData = res.data ? res.data : (Array.isArray(res) ? res : []);
                renderDispatchTable(oldData, `Prior to ${datefrom || 'Selected Week'} (Old Pending)`);
            }
        });
    }

    function applyFilterAndRender() {
        if (currentFilter === "oldpending") {
            loadOldPendingSchedule();
            return;
        }
        let filtered = currentRawData;
        if (currentFilter === "allocated") {
            filtered = currentRawData.filter(r => parseInt(r.allocated_boxes || 0) > 0);
        } else if (currentFilter === "receivedatpolyhouse") {
            filtered = currentRawData.filter(r => parseInt(r.received_polyhouse_boxes || 0) > 0);
        } else if (currentFilter === "pending") {
            filtered = currentRawData.filter(r => parseInt(r.pending_boxes || 0) > 0);
        }
        renderDispatchTable(filtered, currentWeekRange);
    }

    // Status filter card click
    $(document).on("click", ".status-filter-card", function() {
        $(".status-filter-card").removeClass("active");
        $(this).addClass("active");
        currentFilter = $(this).data("filter") || "all";
        applyFilterAndRender();
    });

    function renderDispatchTable(data, weekRange) {
        let tbody = $("#scheduleTable");
        tbody.html("");
        
        if (!Array.isArray(data) || data.length === 0) {
            let filterLabel = "entries";
            if (currentFilter === "allocated") filterLabel = "allocated box entries";
            else if (currentFilter === "receivedatpolyhouse") filterLabel = "received at polyhouse box entries";
            else if (currentFilter === "pending") filterLabel = "pending box entries";
            else if (currentFilter === "oldpending") filterLabel = "old pending box entries";

            tbody.append(`
                <tr>
                    <td colspan="10" class="text-center py-4 text-muted fw-bold">
                        <i class="fa fa-info-circle me-2"></i>No ${filterLabel} found for: ${weekRange}
                    </td>
                </tr>
            `);
            return;
        }
        
        let today = new Date();
        today.setHours(0, 0, 0, 0);
        let todayStr = today.toISOString().split("T")[0];
        
        // Sort by opening date ASC
        data.sort((a, b) => new Date(a.opening_date) - new Date(b.opening_date));
        
        data.forEach((row, index) => {
            // Highlight logic
            let rowClass = "";
            let dispDateStr = row.opening_date;
            let pendingCount = parseInt(row.pending_boxes || 0);
            
            if (pendingCount > 0) {
                rowClass = "row-pending-alert";
            } else if (dispDateStr < todayStr) {
                rowClass = "row-past-due";
            } else if (dispDateStr === todayStr) {
                rowClass = "row-current-week";
            } else {
                let [ws, we] = weekRange.split(" to ");
                if (dispDateStr >= ws && dispDateStr <= we) {
                    rowClass = "row-current-week";
                }
            }
            
            // Location column
            let locationCol = "";
            if (row.location_name && row.location_name !== "Location Missing") {
                locationCol = row.location_name;
            } else {
                locationCol = `<div class="location-missing-btn">Location Missing For Sowing</div>`;
            }
            
            // Polyhouse name column
            let polyhouseNameCol = row.polyhousename || "N/A";
            
            // Order No and Product Name in 1 Column
            let orderAndProductCol = `
                <div class="fw-bold text-dark">${row.productname}</div>
                <div class="text-muted small" style="font-size: 11px;">${row.stockorder_no || 'N/A'}</div>
            `;
            
            // Variety column
            let varietyCol = `${row.seed_name} (${row.roottype})`;
            
            // Format Dates
            let formattedSowingDate = changeDateFormatYmd_dMY_local(row.packing_date);
            let formattedDispatchDate = changeDateFormatYmd_dMY_local(row.opening_date);

            // Allocation & Received details column
            let allocReceivedCol = `
                <div class="d-flex flex-column gap-1" style="font-size: 11.5px; min-width: 175px;">
                    <div>
                        <span class="text-secondary fw-semibold">Allocated:</span>
                        <span class="fw-bold text-dark">${row.allocated_by || '-'}</span>
                        <div class="text-muted" style="font-size: 10.5px;">${row.formatted_allocated_at || '-'}</div>
                    </div>
                    <div class="border-top pt-1">
                        <span class="text-secondary fw-semibold">Received:</span>
                        <span class="fw-bold text-dark">${row.received_by || '-'}</span>
                        <div class="text-muted" style="font-size: 10.5px;">${row.formatted_received_at || '-'}</div>
                    </div>
                    <div class="border-top pt-1">
                        <span class="badge bg-light text-dark border">
                            <i class="fa fa-stopwatch text-primary me-1"></i>Duration: <b>${row.time_taken || '-'}</b>
                        </span>
                    </div>
                </div>
            `;
            
            tbody.append(`
                <tr class="${rowClass}">
                    <td class="text-center fw-bold text-secondary" style="vertical-align: middle;">${index + 1}</td>
                    <td>${locationCol}</td>
                    <td>${polyhouseNameCol}</td>
                    <td>${orderAndProductCol}</td>
                    <td class="fw-bold date-highlight">${formattedSowingDate}</td>
                    <td class="fw-bold date-highlight">${formattedDispatchDate}</td>
                    <td>${varietyCol}</td>
                    <td>${row.order_qty} / ${row.sowing_qty}</td>
                    <td>${allocReceivedCol}</td>
                    <td>
                        <div class="d-flex flex-column gap-1" style="font-size: 0.85rem;">
                            <span class="badge bg-primary text-white w-100 text-start">Total: ${row.box_count} Boxes / ${row.total_trays} Trays</span>
                            <span class="badge bg-success text-white w-100 text-start">Allocated: ${row.allocated_boxes || 0} Boxes</span>
                            <span class="badge bg-info text-dark w-100 text-start">Received at Polyhouse: ${row.received_polyhouse_boxes || 0} Boxes</span>
                            <span class="badge ${pendingCount > 0 ? 'badge-pending-alert' : 'bg-warning text-dark'} w-100 text-start">Pending: ${pendingCount} Boxes</span>
                        </div>
                    </td>
                </tr>
            `);
        });
    }

    // Local Date Formatter fallback helper
    function changeDateFormatYmd_dMY_local(dateStr) {
        if (!dateStr) return "";
        let parts = dateStr.split("-");
        if (parts.length !== 3) return dateStr;
        
        let months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        let y = parts[0];
        let m = parseInt(parts[1]) - 1;
        let d = parseInt(parts[2]);
        
        if (m >= 0 && m < 12) {
            let dStr = d < 10 ? '0' + d : d;
            return `${dStr}-${months[m]}-${y}`;
        }
        return dateStr;
    }

    // Export Table Data to Excel (.xlsx) Handler
    $(document).on("click", "#exportExcelBtn, #exportExcelBtnTop", function() {
        let dataToExport = [];
        if (currentFilter === "all") dataToExport = currentRawData;
        else if (currentFilter === "allocated") dataToExport = currentRawData.filter(r => parseInt(r.allocated_boxes || 0) > 0);
        else if (currentFilter === "receivedatpolyhouse") dataToExport = currentRawData.filter(r => parseInt(r.received_polyhouse_boxes || 0) > 0);
        else if (currentFilter === "pending") dataToExport = currentRawData.filter(r => parseInt(r.pending_boxes || 0) > 0);
        else dataToExport = currentRawData;

        if (!dataToExport || dataToExport.length === 0) {
            alert("No records available to export!");
            return;
        }

        let exportRows = dataToExport.map((item, idx) => ({
            "S.No": idx + 1,
            "Sowing Location": item.location_name || '',
            "Polyhouse Name": item.polyhousename || '',
            "Order No": item.stockorder_no || '',
            "Product Name": item.productname || '',
            "Packing Date": item.packing_date || '',
            "Opening Date": item.opening_date || '',
            "Variety of Seed": `${item.seed_name || ''} (${item.roottype || ''})`,
            "Order Qty": item.order_qty || 0,
            "Sowing Qty": item.sowing_qty || 0,
            "Allocated By": item.allocated_by || '-',
            "Allocated At": item.formatted_allocated_at || '-',
            "Received By": item.received_by || '-',
            "Received At": item.formatted_received_at || '-',
            "Time Taken (Alloc to Recv)": item.time_taken || '-',
            "Total Boxes": item.box_count || 0,
            "Total Trays": item.total_trays || 0,
            "Allocated Boxes": item.allocated_boxes || 0,
            "Received at Polyhouse Boxes": item.received_polyhouse_boxes || 0,
            "Pending Boxes": item.pending_boxes || 0
        }));

        let ws = XLSX.utils.json_to_sheet(exportRows);
        let wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Germination Schedule");
        let safeName = (currentWeekRange || "Schedule").replace(/[^a-zA-Z0-9_-]/g, '_');
        XLSX.writeFile(wb, `Germination_Box_Opening_${safeName}.xlsx`);
    });
});
</script>
