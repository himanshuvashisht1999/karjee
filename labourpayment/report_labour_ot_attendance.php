<?php
if (isset($_GET['vw']) && $_GET['vw'] == 1) {
    $key     = $_GET['k'] ?? '';
    $rooturl = "";
} else {
    require('header.php');
}
?>
<!-- Google Fonts & Tabler Icons -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">

<style>
:root {
    --ot-primary: #ea580c;
    --ot-primary-hover: #c2410c;
    --ot-dark: #0f172a;
    --ot-border: #e2e8f0;
    --ot-bg: #f8fafc;
}

body {
    font-family: 'Inter', sans-serif;
    color: #1e293b;
    background-color: #f1f5f9;
}

/* Scrollbar */
.ot-table-wrapper::-webkit-scrollbar { width: 8px; height: 8px; }
.ot-table-wrapper::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 4px; }
.ot-table-wrapper::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
.ot-table-wrapper::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
.ot-table-wrapper { scrollbar-width: thin; scrollbar-color: #cbd5e1 #f1f5f9; }

/* Filter Card */
.ot-filter-card {
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid var(--ot-border);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    padding: 16px 20px;
    margin-bottom: 16px;
}

.ot-filter-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: #64748b;
    margin-bottom: 5px;
    display: block;
}

.ot-input-wrap {
    position: relative;
    width: 100%;
}

.ot-input-wrap i.ot-icon-left {
    position: absolute;
    left: 11px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 14px;
    pointer-events: none;
}

.ot-form-control {
    width: 100%;
    height: 38px;
    padding: 6px 10px 6px 34px;
    border: 1.5px solid var(--ot-border);
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    color: #1e293b;
    background-color: #ffffff;
    transition: all 0.2s ease;
}

.ot-form-control:focus {
    border-color: var(--ot-primary);
    outline: none;
    box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.12);
}

.ot-select-wrap {
    position: relative;
    width: 100%;
}

.ot-select-wrap i.ot-icon-left {
    position: absolute;
    left: 11px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 14px;
    pointer-events: none;
}

.ot-select-wrap select {
    width: 100%;
    height: 38px;
    padding: 6px 30px 6px 34px;
    border: 1.5px solid var(--ot-border);
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    color: #1e293b;
    background: #ffffff;
    appearance: none;
    cursor: pointer;
    transition: all 0.2s ease;
}

.ot-select-wrap select:focus {
    border-color: var(--ot-primary);
    outline: none;
    box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.12);
}

.ot-select-wrap::after {
    content: "\ea5f";
    font-family: "tabler-icons";
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    pointer-events: none;
    font-size: 13px;
}

/* Multiselect Dropdown */
.ot-multiselect-wrap {
    position: relative;
    width: 100%;
}

.ot-multiselect-trigger {
    width: 100%;
    height: 38px;
    padding: 6px 28px 6px 34px;
    border: 1.5px solid var(--ot-border);
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    color: #1e293b;
    background: #ffffff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    user-select: none;
    transition: all 0.2s ease;
}

.ot-multiselect-trigger i.ot-icon-left {
    position: absolute;
    left: 11px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 14px;
    pointer-events: none;
}

.ot-multiselect-trigger i.ot-icon-arrow {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 12px;
    pointer-events: none;
    transition: transform 0.2s;
}

.ot-multiselect-wrap.open .ot-multiselect-trigger {
    border-color: var(--ot-primary);
    box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.12);
}

.ot-multiselect-wrap.open .ot-icon-arrow {
    transform: translateY(-50%) rotate(180deg);
}

.ot-multiselect-dropdown {
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    background: #ffffff;
    border: 1.5px solid var(--ot-border);
    border-radius: 8px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
    z-index: 1050;
    max-height: 240px;
    overflow-y: auto;
    display: none;
    padding: 6px 0;
}

.ot-multiselect-option {
    padding: 5px 12px;
    transition: background 0.15s;
}

.ot-multiselect-option:hover {
    background: #f8fafc;
}

.ot-multiselect-option label.checkbox-container {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12.5px;
    color: #334155;
    cursor: pointer;
    margin: 0;
    width: 100%;
    user-select: none;
}

.ot-multiselect-option input[type="checkbox"] {
    width: 15px;
    height: 15px;
    accent-color: var(--ot-primary);
    cursor: pointer;
}

/* Action Buttons */
.btn-ot-refresh {
    height: 38px;
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
    color: #ffffff !important;
    border: none;
    border-radius: 8px;
    padding: 0 18px;
    font-size: 13px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 2px 6px rgba(234, 88, 12, 0.25);
    transition: all 0.15s ease;
    white-space: nowrap;
}

.btn-ot-refresh:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(234, 88, 12, 0.35);
}

.btn-ot-excel {
    height: 38px;
    background: #ffffff;
    color: #15803d !important;
    border: 1.5px solid #22c55e;
    border-radius: 8px;
    padding: 0 18px;
    font-size: 13px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.15s ease;
    white-space: nowrap;
}

.btn-ot-excel:hover {
    background: #f0fdf4;
    border-color: #16a34a;
    color: #166534 !important;
    transform: translateY(-1px);
}

/* Light Gender Badges */
.badge-gender-m {
    background-color: #e0f2fe !important;
    color: #111827 !important;
    border: 1px solid #bae6fd !important;
    font-size: 8.5px;
    padding: 1px 5px;
    font-weight: 600;
    border-radius: 4px;
}

.badge-gender-w {
    background-color: #fce7f3 !important;
    color: #111827 !important;
    border: 1px solid #fbcfe8 !important;
    font-size: 8.5px;
    padding: 1px 5px;
    font-weight: 600;
    border-radius: 4px;
}

/* Compact Half-Size KPI Cards matching Screenshot 1 */
.ot-kpi-card-compact {
    background: #ffffff;
    border-radius: 10px;
    padding: 8px 12px;
    text-align: center;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    min-height: 72px;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.ot-kpi-card-compact:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.07);
}

.ot-card-border-orange { border: 2.5px solid #f97316 !important; }
.ot-card-border-green  { border: 2.5px solid #16a34a !important; }
.ot-card-border-blue   { border: 2.5px solid #2563eb !important; }
.ot-card-border-yellow { border: 2.5px solid #eab308 !important; }

.ot-kpi-compact-title {
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    margin-bottom: 2px;
}

.ot-kpi-compact-val {
    font-size: 19px;
    font-weight: 800;
    line-height: 1.15;
    margin-bottom: 2px;
}

.ot-kpi-compact-sub {
    font-size: 10px;
    font-weight: 600;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
}

/* Report Table */
.ot-table {
    width: 100%;
    margin-bottom: 0;
    font-size: 12px;
    white-space: nowrap;
}

.ot-table thead th {
    background-color: #f8fafc !important;
    color: #475569;
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 8px 10px;
    border-bottom: 1.5px solid #e2e8f0;
    vertical-align: middle;
}

.ot-table tbody td {
    padding: 7px 10px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
}

.ot-table tbody tr:hover {
    background-color: #f8fafc;
}
</style>

<div class="container-fluid mt-3 pb-5">

    <!-- Page Header Bar -->
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div>
            <h4 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2" style="font-family: 'Outfit', sans-serif;">
                <i class="ti ti-clock-check text-primary fs-22"></i> Labour OT Attendance Report
            </h4>
            <p class="text-muted small mb-0">Detailed overtime logs, scanner verification &amp; hours calculation report</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-white text-dark border px-3 py-2 fw-semibold shadow-sm font-12">
                <i class="ti ti-calendar me-1 text-primary"></i> <span id="ot_report_date_badge"><?php echo date('01-M-Y') . ' To ' . date('d-M-Y'); ?></span>
            </span>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="ot-filter-card">
        <!-- Top Row Filters -->
        <div class="row g-2">
            <!-- 1. From Date -->
            <div class="col-md-6 col-lg-2">
                <label class="ot-filter-label">FROM DATE</label>
                <div class="ot-input-wrap">
                    <i class="ti ti-calendar ot-icon-left"></i>
                    <input type="date" class="ot-form-control" id="ot_datefrom" value="<?php echo date('Y-m-01'); ?>">
                </div>
            </div>

            <!-- 2. To Date -->
            <div class="col-md-6 col-lg-2">
                <label class="ot-filter-label">TO DATE</label>
                <div class="ot-input-wrap">
                    <i class="ti ti-calendar ot-icon-left"></i>
                    <input type="date" class="ot-form-control" id="ot_dateto" value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>

            <!-- 3. Labour Home Location -->
            <div class="col-md-6 col-lg-3">
                <label class="ot-filter-label">LABOUR HOME LOCATION</label>
                <div class="ot-select-wrap">
                    <i class="ti ti-building-community ot-icon-left"></i>
                    <select id="ot_homelocation">
                        <option value="all">All Locations</option>
                    </select>
                </div>
            </div>

            <!-- 4. QR Scanner Location -->
            <div class="col-md-6 col-lg-3">
                <label class="ot-filter-label">QR SCANNER LOCATION</label>
                <div class="ot-select-wrap">
                    <i class="ti ti-qrcode ot-icon-left"></i>
                    <select id="ot_qrlocation">
                        <option value="all">All QR Locations</option>
                    </select>
                </div>
            </div>

            <!-- 5. Scanned By (Multiselect) -->
            <div class="col-md-12 col-lg-2">
                <label class="ot-filter-label">SCANNED BY</label>
                <div class="ot-multiselect-wrap" id="scanby-multiselect">
                    <div class="ot-multiselect-trigger" id="scanby-trigger">
                        <i class="ti ti-user-check ot-icon-left"></i>
                        <span class="ot-multiselect-text" id="scanby-trigger-text">All Scanners</span>
                        <i class="ti ti-chevron-down ot-icon-arrow"></i>
                    </div>
                    <div class="ot-multiselect-dropdown" id="scanby-dropdown">
                        <div class="ot-multiselect-option border-bottom pb-2 mb-1">
                            <label class="checkbox-container fw-bold">
                                <input type="checkbox" id="scanby-select-all" checked>
                                <span class="option-text ms-1">Select All Scanners</span>
                            </label>
                        </div>
                        <div id="scanby-options-container">
                            <!-- Populated dynamically -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Row: Search & Action Buttons -->
        <div class="row g-2 mt-1 align-items-end">
            <!-- Search Labour -->
            <div class="col-md-8 col-lg-9">
                <label class="ot-filter-label">SEARCH LABOUR</label>
                <div class="ot-input-wrap">
                    <i class="ti ti-search ot-icon-left"></i>
                    <input type="text" class="ot-form-control" id="ot_search" placeholder="Search by name or ID...">
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="col-md-4 col-lg-3 d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-ot-refresh" id="btnRefreshOT">
                    <i class="ti ti-refresh"></i> Refresh
                </button>
                <button type="button" class="btn btn-ot-excel" id="btnExportOTExcel" onclick="exportOTReportExcel()">
                    <i class="ti ti-file-type-xls"></i> Export Excel
                </button>
            </div>
        </div>
    </div>

    <!-- Compact Half-Size KPI Summary Cards (Matches Screenshot 1) -->
    <div class="row g-2 mb-3" id="ot_summary_cards">
        <!-- 1. Total OT Labours -->
        <div class="col-6 col-md-3">
            <div class="ot-kpi-card-compact ot-card-border-orange">
                <div class="ot-kpi-compact-title text-muted">
                    <i class="ti ti-users" style="color: #f97316;"></i> TOTAL OT LABOURS
                </div>
                <div class="ot-kpi-compact-val text-dark" id="kpi_total_ot">0</div>
                <div class="ot-kpi-compact-sub">
                    <span class="badge badge-gender-m">M: <span id="kpi_man_count">0</span></span>
                    <span class="badge badge-gender-w">W: <span id="kpi_female_count">0</span></span>
                </div>
            </div>
        </div>

        <!-- 2. Main Gate OT -->
        <div class="col-6 col-md-3">
            <div class="ot-kpi-card-compact ot-card-border-green">
                <div class="ot-kpi-compact-title text-muted">
                    <i class="ti ti-door-enter" style="color: #16a34a;"></i> MAIN GATE OT
                </div>
                <div class="ot-kpi-compact-val text-success" id="kpi_gate_ot">0</div>
                <div class="ot-kpi-compact-sub">
                    <span class="badge badge-gender-m">M: <span id="kpi_gate_man">0</span></span>
                    <span class="badge badge-gender-w">W: <span id="kpi_gate_female">0</span></span>
                </div>
            </div>
        </div>

        <!-- 3. QR Scanner OT -->
        <div class="col-6 col-md-3">
            <div class="ot-kpi-card-compact ot-card-border-blue">
                <div class="ot-kpi-compact-title text-muted">
                    <i class="ti ti-qrcode" style="color: #2563eb;"></i> QR SCANNER OT
                </div>
                <div class="ot-kpi-compact-val text-primary" id="kpi_qr_ot">0</div>
                <div class="ot-kpi-compact-sub">
                    <span class="badge badge-gender-m">M: <span id="kpi_qr_man">0</span></span>
                    <span class="badge badge-gender-w">W: <span id="kpi_qr_female">0</span></span>
                </div>
            </div>
        </div>

        <!-- 4. Total OT Hours -->
        <div class="col-6 col-md-3">
            <div class="ot-kpi-card-compact ot-card-border-yellow">
                <div class="ot-kpi-compact-title text-muted">
                    <i class="ti ti-clock" style="color: #ca8a04;"></i> TOTAL OT HOURS
                </div>
                <div class="ot-kpi-compact-val" style="color: #eab308;" id="kpi_total_hours">0.0h</div>
                <div class="ot-kpi-compact-sub">
                    Avg: <span id="kpi_avg_hours">0.0h</span> / Labour
                </div>
            </div>
        </div>
    </div>

    <!-- Report Table Card with Combined Columns -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-header bg-white py-2 px-3 d-flex align-items-center justify-content-between flex-wrap gap-2 border-bottom">
            <div class="d-flex align-items-center gap-2">
                <h5 class="mb-0 fs-13 fw-bold text-dark"><i class="ti ti-list me-1 text-primary"></i> OT Attendance Records</h5>
                <span class="badge bg-light text-dark border font-11" id="table_record_count">0 records</span>
            </div>
            <div class="btn-group btn-group-sm" role="group" id="filterTabGroup">
                <button type="button" class="btn btn-outline-secondary py-1 px-2 active" onclick="setTabFilter('all', this)">All</button>
                <button type="button" class="btn btn-outline-secondary py-1 px-2" onclick="setTabFilter('mn', this)">Main Gate</button>
                <button type="button" class="btn btn-outline-secondary py-1 px-2" onclick="setTabFilter('dp', this)">QR Locations</button>
                <button type="button" class="btn btn-outline-secondary py-1 px-2" onclick="setTabFilter('swap', this)">Swap OT</button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="ot-table-wrapper" style="max-height: 600px; overflow-y: auto;">
                <table class="table ot-table table-hover table-bordered mb-0" id="tblOTReport">
                    <thead class="sticky-top">
                        <tr>
                            <th class="text-center" style="width: 38px;">S.NO</th>
                            <th class="text-center" style="width: 85px;">DATE</th>
                            <th style="width: 180px;">LABOUR DETAILS</th>
                            <th style="width: 170px;">LOCATION</th>
                            <th class="text-center" style="width: 100px;">CHECK-IN / OUT</th>
                            <th class="text-center" style="width: 100px;">WORKING HOURS</th>
                            <th style="width: 140px;">OT PURPOSE / WORK</th>
                            <th style="width: 105px;">SCANNED BY</th>
                            <th class="text-center" style="width: 80px;">STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <div class="spinner-border spinner-border-sm text-primary me-1" role="status"></div> Loading OT Attendance Records...
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="table-light sticky-bottom fw-bold" id="tblOTReportFoot"></tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
if (!isset($_GET['vw']) || $_GET['vw'] != 1) {
    require('footer.php');
}
?>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
var routurl      = $("body").data("routurl")      || "../";
var projectid    = $("body").data("projectid")    || "";
var locationMap  = {};
var polyhouseMap = {};
var allOTData    = [];
var currentTab   = 'all';
var selectedScanners = new Set();

function initLocations() {
    if (typeof LabourAttendanceLocationList === 'function') {
        LabourAttendanceLocationList('all', 'all', function(res) {
            var opts = '<option value="all">All Locations</option>';
            if (res && res.length > 0) {
                for (var i = 0; i < res.length; i++) {
                    var locId = res[i].LocationId;
                    var locName = res[i].LocationName;
                    locationMap[locId] = locName;
                    opts += '<option value="' + locId + '">' + locName + '</option>';
                }
            }
            $('#ot_homelocation').html(opts);
            loadPolyhouses($('#ot_homelocation').val());
        });
    }
}

function loadPolyhouses(locationid) {
    $.ajax({
        type: 'POST',
        url: routurl + 'modal/hrm_laboursalarymodal.php?action=GetPolyhousesByLocation',
        data: { projectid: projectid, locationid: locationid || 'all' },
        success: function(res) {
            var data = [];
            try { data = (typeof res === 'string') ? JSON.parse(res) : res; } catch(e){}
            var opts = '<option value="all">All QR Locations</option><option value="main-gate">Main Gate</option>';
            polyhouseMap['main-gate'] = 'Main Gate';
            if (Array.isArray(data)) {
                for (var i = 0; i < data.length; i++) {
                    var pid = data[i].polyhouseid;
                    var pname = data[i].polyhousename;
                    polyhouseMap[pid] = pname;
                    opts += '<option value="' + pid + '">' + pname + '</option>';
                }
            }
            $('#ot_qrlocation').html(opts);
        },
        error: function() {
            $('#ot_qrlocation').html('<option value="all">All QR Locations</option><option value="main-gate">Main Gate</option>');
        }
    });
}

function fetchOTReport() {
    var datefrom = $('#ot_datefrom').val();
    var dateto   = $('#ot_dateto').val();
    var homeloc  = $('#ot_homelocation').val() || 'all';
    var qrloc    = $('#ot_qrlocation').val() || 'all';

    if (!datefrom || !dateto) return;

    var df = new Date(datefrom);
    var dt = new Date(dateto);
    var dispPeriod = df.getDate() + "-" + df.toLocaleString('default', {month:'short'}) + "-" + df.getFullYear() + " To " +
                     dt.getDate() + "-" + dt.toLocaleString('default', {month:'short'}) + "-" + dt.getFullYear();
    $('#ot_report_date_badge').text(dispPeriod);

    $('#tblOTReport tbody').html('<tr><td colspan="9" class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm text-primary me-1"></div> Fetching OT records...</td></tr>');
    $('#tblOTReportFoot').empty();

    $.ajax({
        type: 'POST',
        url: routurl + 'modal/hrm_laboursalarymodal.php?action=GetOTAttendanceViewData',
        data: {
            projectid:   projectid,
            datefrom:    datefrom,
            dateto:      dateto,
            locationid:  homeloc,
            polyhouseid: qrloc,
            scanby:      'all'
        },
        dataType: 'json',
        success: function(data) {
            allOTData = Array.isArray(data) ? data : [];
            populateScannerFilter(allOTData);
            renderOTReportTable();
        },
        error: function() {
            $('#tblOTReport tbody').html('<tr><td colspan="9" class="text-center text-danger py-4">Failed to load OT attendance data. Please try again.</td></tr>');
        }
    });
}

function populateScannerFilter(data) {
    var scanners = new Set();
    data.forEach(function(r) {
        var s = r.checkinby_mn || r.checkinby_dp || '';
        if (s.trim() !== '') scanners.add(s.trim());
    });

    var container = $('#scanby-options-container');
    container.empty();

    if (scanners.size === 0) {
        container.html('<div class="px-3 py-1 text-muted small">No scanners found</div>');
        selectedScanners.clear();
        $('#scanby-trigger-text').text('All Scanners');
        return;
    }

    selectedScanners.clear();
    scanners.forEach(function(sc) {
        selectedScanners.add(sc);
        var html = '<div class="ot-multiselect-option">' +
            '<label class="checkbox-container">' +
            '<input type="checkbox" class="scanby-cb" value="' + sc + '" checked> ' +
            '<span class="option-text ms-1">' + sc + '</span>' +
            '</label></div>';
        container.append(html);
    });

    $('#scanby-select-all').prop('checked', true);
    updateScannerTriggerText();

    $('.scanby-cb').on('change', function() {
        var val = $(this).val();
        if ($(this).is(':checked')) {
            selectedScanners.add(val);
        } else {
            selectedScanners.delete(val);
        }
        $('#scanby-select-all').prop('checked', selectedScanners.size === scanners.size);
        updateScannerTriggerText();
        renderOTReportTable();
    });
}

function updateScannerTriggerText() {
    var total = $('.scanby-cb').length;
    var count = selectedScanners.size;
    if (count === 0) {
        $('#scanby-trigger-text').text('No Scanners Selected');
    } else if (count === total) {
        $('#scanby-trigger-text').text('All Scanners (' + total + ')');
    } else {
        $('#scanby-trigger-text').text(count + ' Scanner(s) Selected');
    }
}

function setTabFilter(tab, el) {
    currentTab = tab;
    $('#filterTabGroup button').removeClass('active');
    $(el).addClass('active');
    renderOTReportTable();
}

function resolveLocName(locVal) {
    if (!locVal || locVal === '-' || locVal === '0000-00-00 00:00:00') return '-';
    if (locVal === 'main-gate') return 'Main Gate';
    if (locationMap[locVal]) return locationMap[locVal];
    if (polyhouseMap[locVal]) return polyhouseMap[locVal];
    return locVal;
}

function renderOTReportTable() {
    var searchVal = ($('#ot_search').val() || '').toLowerCase().trim();

    var filtered = allOTData.filter(function(r) {
        // Tab filtering
        var hasMN = (r.checkintime_mn && r.checkintime_mn !== '0000-00-00 00:00:00');
        var hasDP = (r.checkintime_dp && r.checkintime_dp !== '0000-00-00 00:00:00');
        var homeLoc = r.labourlocationid || r.locationid || '';
        var scanLoc = r.checkinlocation_mn || r.checkinlocation_dp || '';

        if (currentTab === 'mn' && !hasMN) return false;
        if (currentTab === 'dp' && !hasDP) return false;
        if (currentTab === 'swap') {
            if (!scanLoc || homeLoc === scanLoc) return false;
        }

        // Scanned by filter
        var scanBy = (r.checkinby_mn || r.checkinby_dp || '').trim();
        if (selectedScanners.size > 0 && scanBy && !selectedScanners.has(scanBy)) {
            return false;
        }

        // Search
        if (searchVal) {
            var name = (r.FullName || '').toLowerCase();
            var empId = (r.EmployeeID || r.labourid || '').toLowerCase();
            var purpose = (r.purposeofot || r.remark_mn || '').toLowerCase();
            var homeN = (r.HomeLocationName || '').toLowerCase();
            var scanN = (r.ScanLocationName || '').toLowerCase();
            if (name.indexOf(searchVal) === -1 && empId.indexOf(searchVal) === -1 && 
                purpose.indexOf(searchVal) === -1 && homeN.indexOf(searchVal) === -1 && scanN.indexOf(searchVal) === -1) {
                return false;
            }
        }

        return true;
    });

    // KPI Metrics calculation
    var totalCount = filtered.length;
    var manCount = 0, femaleCount = 0;
    var gateCount = 0, gateMan = 0, gateFemale = 0;
    var qrCount = 0, qrMan = 0, qrFemale = 0;
    var totalHoursDec = 0.0;

    var tbodyHtml = '';

    if (filtered.length === 0) {
        tbodyHtml = '<tr><td colspan="9" class="text-center text-muted py-4">No OT attendance records found matching filters.</td></tr>';
    } else {
        filtered.forEach(function(r, idx) {
            var g = (r.Gender || r.gender || '').toString().trim().toLowerCase();
            var isFemale = (g === 'female' || g === 'f' || g === 'woman' || g === 'women' || g === 'w');
            if (isFemale) femaleCount++; else manCount++;

            var hasMN = (r.checkintime_mn && r.checkintime_mn !== '0000-00-00 00:00:00');
            var hasDP = (r.checkintime_dp && r.checkintime_dp !== '0000-00-00 00:00:00');

            if (hasMN) {
                gateCount++;
                if (isFemale) gateFemale++; else gateMan++;
            }
            if (hasDP) {
                qrCount++;
                if (isFemale) qrFemale++; else qrMan++;
            }

            // In/Out Times & Hours
            var inTime = '', outTime = '', durStr = '', hrs = 0.0;
            var scanLocRaw = r.checkinlocation_mn || r.checkinlocation_dp || '';
            var scanLoc = r.ScanLocationName || resolveLocName(scanLocRaw);
            var homeLoc = r.HomeLocationName || resolveLocName(r.labourlocationid || r.locationid);
            var scanBy  = r.checkinby_mn || r.checkinby_dp || '-';

            if (r.workinghours_mn) {
                var p = r.workinghours_mn.split(':');
                hrs = maxNumber(hrs, parseInt(p[0]||0) + (parseInt(p[1]||0)/60) + (parseInt(p[2]||0)/3600));
            }
            if (r.workinghours_dp) {
                var p = r.workinghours_dp.split(':');
                hrs = maxNumber(hrs, parseInt(p[0]||0) + (parseInt(p[1]||0)/60) + (parseInt(p[2]||0)/3600));
            }
            totalHoursDec += hrs;

            if (hasMN) {
                inTime = r.checkintime_mn.substring(11, 16);
                outTime = (r.checkouttime_mn && r.checkouttime_mn !== '0000-00-00 00:00:00') ? r.checkouttime_mn.substring(11, 16) : '-';
            } else if (hasDP) {
                inTime = r.checkintime_dp.substring(11, 16);
                outTime = (r.checkouttime_dp && r.checkouttime_dp !== '0000-00-00 00:00:00') ? r.checkouttime_dp.substring(11, 16) : '-';
            }

            var hInt = Math.floor(hrs);
            var mInt = Math.round((hrs - hInt) * 60);
            durStr = hrs > 0 ? (hInt + 'h ' + mInt + 'm') : '-';

            var purpose = r.purposeofot || r.remark_mn || 'General OT';
            var statusBadge = '<span class="badge bg-soft-success text-success border px-2 py-1">Completed</span>';
            if (outTime === '-' || !outTime) {
                statusBadge = '<span class="badge bg-soft-warning text-warning border px-2 py-1">Working</span>';
            }

            var genderBadge = isFemale ? '<span class="badge badge-gender-w ms-1">W</span>' : '<span class="badge badge-gender-m ms-1">M</span>';

            var attDate = r.attendancedate ? r.attendancedate : '-';
            if (r.attendancedate && r.attendancedate.indexOf('-') > -1) {
                var dp = r.attendancedate.split('-');
                if (dp.length === 3) {
                    var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                    attDate = dp[2] + '-' + (months[parseInt(dp[1]) - 1] || dp[1]) + '-' + dp[0];
                }
            }

            // 1 Column for Labour (Name + Code + Gender)
            var labourHtml = '<div>' +
                '<div class="fw-bold text-dark text-truncate">' + r.FullName + ' ' + genderBadge + '</div>' +
                '<div class="text-secondary font-11"><i class="ti ti-id me-1"></i>' + (r.EmployeeID || r.labourid) + '</div>' +
                '</div>';

            // 1 Column for Locations (Home Location + Scan Location Name)
            var locationHtml = '<div>' +
                '<div class="fw-semibold text-dark font-12"><i class="ti ti-map-pin text-muted me-1"></i>' + homeLoc + '</div>' +
                '<div class="mt-1"><span class="badge bg-light text-dark border font-11"><i class="ti ti-qrcode text-primary me-1"></i>' + scanLoc + '</span></div>' +
                '</div>';

            // 1 Column for Check-In & Check-Out Times
            var timeHtml = '<div class="text-center">' +
                '<div class="text-success fw-bold font-12"><i class="ti ti-login me-1"></i>' + inTime + '</div>' +
                '<div class="text-danger fw-semibold font-11"><i class="ti ti-logout me-1"></i>' + outTime + '</div>' +
                '</div>';

            tbodyHtml += '<tr>' +
                '<td class="text-center align-middle">' + (idx + 1) + '</td>' +
                '<td class="text-center align-middle fw-semibold text-secondary" style="font-size: 11px;">' + attDate + '</td>' +
                '<td class="align-middle">' + labourHtml + '</td>' +
                '<td class="align-middle">' + locationHtml + '</td>' +
                '<td class="align-middle">' + timeHtml + '</td>' +
                '<td class="text-center align-middle fw-bold text-primary font-12">' + durStr + '<div class="text-muted font-10">(' + hrs.toFixed(1) + 'h)</div></td>' +
                '<td class="align-middle text-dark font-12">' + purpose + '</td>' +
                '<td class="align-middle text-muted small">' + scanBy + '</td>' +
                '<td class="text-center align-middle">' + statusBadge + '</td>' +
                '</tr>';
        });
    }

    $('#tblOTReport tbody').html(tbodyHtml);
    $('#table_record_count').text(totalCount + ' records');

    // Update KPI UI
    $('#kpi_total_ot').text(totalCount);
    $('#kpi_man_count').text(manCount);
    $('#kpi_female_count').text(femaleCount);

    $('#kpi_gate_ot').text(gateCount);
    $('#kpi_gate_man').text(gateMan);
    $('#kpi_gate_female').text(gateFemale);

    $('#kpi_qr_ot').text(qrCount);
    $('#kpi_qr_man').text(qrMan);
    $('#kpi_qr_female').text(qrFemale);

    $('#kpi_total_hours').text(totalHoursDec.toFixed(1) + 'h');
    var avgHrs = totalCount > 0 ? (totalHoursDec / totalCount).toFixed(1) : '0.0';
    $('#kpi_avg_hours').text(avgHrs + 'h');

    // Footer Total
    if (totalCount > 0) {
        var tfootHtml = '<tr>' +
            '<td colspan="5" class="text-end text-uppercase">TOTAL OVERTIME HOURS:</td>' +
            '<td class="text-center text-primary fs-13 fw-bold">' + totalHoursDec.toFixed(1) + ' Hours</td>' +
            '<td colspan="3"></td>' +
            '</tr>';
        $('#tblOTReportFoot').html(tfootHtml);
    } else {
        $('#tblOTReportFoot').empty();
    }
}

function maxNumber(a, b) {
    return a > b ? a : b;
}

// Separate columns on Excel Export as requested
function exportOTReportExcel() {
    if (!allOTData || allOTData.length === 0) {
        alert("No OT attendance records to export.");
        return;
    }

    var df = $('#ot_datefrom').val();
    var dt = $('#ot_dateto').val();

    var exportRows = [
        ["Labour Overtime (OT) Attendance Report (" + df + " To " + dt + ")"],
        [],
        [
            "S.No",
            "Attendance Date",
            "Labour ID",
            "Labour Name",
            "Gender",
            "Home Location",
            "Scan Location",
            "Check-In Time",
            "Check-Out Time",
            "OT Working Hours",
            "OT Hours (Dec)",
            "OT Purpose / Work",
            "Scanned By",
            "Status"
        ]
    ];

    allOTData.forEach(function(r, idx) {
        var g = (r.Gender || r.gender || '').toString().trim().toLowerCase();
        var genderStr = (g === 'female' || g === 'f' || g === 'woman' || g === 'women' || g === 'w') ? 'Female' : 'Male';

        var hasMN = (r.checkintime_mn && r.checkintime_mn !== '0000-00-00 00:00:00');
        var hasDP = (r.checkintime_dp && r.checkintime_dp !== '0000-00-00 00:00:00');

        var inTime = '', outTime = '', durStr = '', hrs = 0.0;
        var scanLocRaw = r.checkinlocation_mn || r.checkinlocation_dp || '';
        var scanLoc = r.ScanLocationName || resolveLocName(scanLocRaw);
        var homeLoc = r.HomeLocationName || resolveLocName(r.labourlocationid || r.locationid);
        var scanBy  = r.checkinby_mn || r.checkinby_dp || '-';

        if (r.workinghours_mn) {
            var p = r.workinghours_mn.split(':');
            hrs = maxNumber(hrs, parseInt(p[0]||0) + (parseInt(p[1]||0)/60) + (parseInt(p[2]||0)/3600));
        }
        if (r.workinghours_dp) {
            var p = r.workinghours_dp.split(':');
            hrs = maxNumber(hrs, parseInt(p[0]||0) + (parseInt(p[1]||0)/60) + (parseInt(p[2]||0)/3600));
        }

        if (hasMN) {
            inTime = r.checkintime_mn.substring(11, 16);
            outTime = (r.checkouttime_mn && r.checkouttime_mn !== '0000-00-00 00:00:00') ? r.checkouttime_mn.substring(11, 16) : '-';
        } else if (hasDP) {
            inTime = r.checkintime_dp.substring(11, 16);
            outTime = (r.checkouttime_dp && r.checkouttime_dp !== '0000-00-00 00:00:00') ? r.checkouttime_dp.substring(11, 16) : '-';
        }

        var hInt = Math.floor(hrs);
        var mInt = Math.round((hrs - hInt) * 60);
        durStr = hrs > 0 ? (hInt + 'h ' + mInt + 'm') : '-';

        var statusStr = (outTime === '-' || !outTime) ? 'Working' : 'Completed';
        var purpose   = r.purposeofot || r.remark_mn || 'General OT';

        exportRows.push([
            idx + 1,
            r.attendancedate || '-',
            r.EmployeeID || r.labourid || '',
            r.FullName || '',
            genderStr,
            homeLoc,
            scanLoc,
            inTime,
            outTime,
            durStr,
            hrs > 0 ? hrs.toFixed(2) : '0.00',
            purpose,
            scanBy,
            statusStr
        ]);
    });

    var ws = XLSX.utils.aoa_to_sheet(exportRows);
    var wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Labour_OT_Report");
    XLSX.writeFile(wb, "Labour_OT_Attendance_Report_" + df + "_to_" + dt + ".xlsx");
}

$(document).ready(function() {
    initLocations();

    // Trigger filter dropdown toggling
    $('#scanby-trigger').on('click', function(e) {
        e.stopPropagation();
        $('#scanby-multiselect').toggleClass('open');
        $('#scanby-dropdown').toggle();
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#scanby-multiselect').length) {
            $('#scanby-multiselect').removeClass('open');
            $('#scanby-dropdown').hide();
        }
    });

    $('#scanby-select-all').on('change', function() {
        var checked = $(this).is(':checked');
        $('.scanby-cb').prop('checked', checked).trigger('change');
    });

    $('#ot_homelocation').on('change', function() {
        loadPolyhouses($(this).val());
        fetchOTReport();
    });

    $('#ot_qrlocation, #ot_datefrom, #ot_dateto').on('change', function() {
        fetchOTReport();
    });

    $('#btnRefreshOT').on('click', function() {
        fetchOTReport();
    });

    $('#ot_search').on('keyup', function() {
        renderOTReportTable();
    });

    fetchOTReport();
});
</script>
