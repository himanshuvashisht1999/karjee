<?php
if(isset($_GET['vw']) && $_GET['vw'] == 1){
    $key    = $_GET['k'];
    $rooturl = "";
    $mdid    = "";
    if(isset($_GET['mdid'])) { $mdid = $_GET['mdid']; }
} else {
    require('header.php');
}
?>
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
/* ── Page Variables ─────────────────────────────────────────────── */
:root {
    --att-green:   #1b7a48;
    --att-green-lt:#e6f4ed;
    --att-blue:    #1d4ed8;
    --att-blue-lt: #eef3fc;
    --att-amber:   #92400e;
    --att-amber-lt:#fffbeb;
    --att-red:     #c0392b;
    --att-red-lt:  #fdf0f0;
    --att-gray:    #6b7280;
    --att-border:  #e5e7eb;
    --att-card-bg: #ffffff;
    --att-page-bg: #f3f4f6;
}

/* ── Layout ─────────────────────────────────────────────────────── */
.av-page { font-family: 'Inter', sans-serif; background: var(--att-page-bg); min-height: 100vh; padding: 0; }

/* ── Header Bar ─────────────────────────────────────────────────── */
.av-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
    color: #fff;
    padding: 20px 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    box-shadow: 0 4px 20px rgba(15, 23, 42, 0.25);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}
.av-header-left { display: flex; align-items: center; gap: 14px; }
.av-header-icon {
    width: 48px; height: 48px;
    background: rgba(255,255,255,.08);
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px; backdrop-filter: blur(4px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    color: #38bdf8;
}
.av-header h2 { font-family: 'Outfit', sans-serif; font-size: 20px; font-weight: 700; margin: 0; color: #fff; }
.av-header p  { font-size: 12px; opacity: .8; margin: 0; color: #cbd5e1; }
.av-live-badge {
    display: flex; align-items: center; gap: 8px;
    background: rgba(56, 189, 248, 0.1);
    border: 1px solid rgba(56, 189, 248, 0.2);
    border-radius: 20px;
    padding: 6px 16px;
    font-size: 12px; font-weight: 600;
    backdrop-filter: blur(4px);
    color: #38bdf8;
}
.av-live-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #38bdf8;
    box-shadow: 0 0 0 2px rgba(56,189,248,.3);
    animation: pulse 1.5s infinite;
}
@keyframes pulse { 0%,100% { opacity:1; } 50% { opacity:.5; } }

/* ── Filter Bar ─────────────────────────────────────────────────── */
.av-filters {
    background: #fff;
    padding: 16px 28px;
    border-bottom: 1px solid var(--att-border);
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
    align-items: flex-end;
}
.av-filter-group { display: flex; flex-direction: column; gap: 4px; min-width: 180px; }
.av-filter-label {
    font-size: 11px; font-weight: 600; text-transform: uppercase;
    letter-spacing: .5px; color: var(--att-gray);
}
.av-select-wrap { position: relative; }
.av-select-wrap i.av-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--att-gray); font-size: 14px; pointer-events: none; }
.av-select-wrap select {
    width: 100%; padding: 8px 10px 8px 32px;
    border: 1.5px solid var(--att-border); border-radius: 8px;
    font-size: 13px; background: #f9fafb;
    appearance: none; cursor: pointer;
    transition: border-color .2s;
}
.av-select-wrap select:focus { border-color: #38bdf8; outline: none; background: #fff; }

.av-input-wrap { position: relative; }
.av-input-wrap i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--att-gray); font-size: 14px; pointer-events: none; }
.av-input-wrap input {
    width: 100%; padding: 8px 10px 8px 32px;
    border: 1.5px solid var(--att-border); border-radius: 8px;
    font-size: 13px; background: #f9fafb;
    transition: border-color .2s;
}
.av-input-wrap input:focus { border-color: #38bdf8; outline: none; background: #fff; }

/* ── Multiselect ── */
.av-multiselect-wrap { position: relative; min-width: 180px; }
.av-multiselect-trigger {
    width: 100%; padding: 8px 30px 8px 32px;
    border: 1.5px solid var(--att-border); border-radius: 8px;
    font-size: 13px; background: #f9fafb;
    cursor: pointer; display: flex; align-items: center; justify-content: space-between;
    user-select: none; transition: border-color .2s;
}
.av-multiselect-trigger i.av-icon-left { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--att-gray); font-size: 14px; pointer-events: none; }
.av-multiselect-trigger i.av-icon-arrow { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: var(--att-gray); font-size: 12px; pointer-events: none; transition: transform 0.2s; }
.av-multiselect-wrap.open .av-multiselect-trigger { border-color: #38bdf8; background: #fff; }
.av-multiselect-wrap.open .av-icon-arrow { transform: translateY(-50%) rotate(180deg); }

.av-multiselect-dropdown {
    position: absolute; top: calc(100% + 4px); left: 0; right: 0;
    background: #fff; border: 1.5px solid var(--att-border);
    border-radius: 8px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
    z-index: 100; max-height: 250px; overflow-y: auto; display: none; padding: 6px 0;
}
.av-multiselect-option { padding: 6px 12px; transition: background 0.15s; }
.av-multiselect-option:hover { background: #f1f5f9; }

.av-multiselect-option label.checkbox-container {
    display: flex; align-items: center; gap: 8px; font-size: 13px; color: #334155;
    cursor: pointer; margin: 0; width: 100%; user-select: none;
}
.av-multiselect-option input[type="checkbox"] { width: 16px; height: 16px; accent-color: #0ea5e9; cursor: pointer; }

/* ── Stats Bar ───────────────────────────────────────────────────── */
.av-stats-bar {
    padding: 16px 28px 0;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    gap: 12px;
}
.av-stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 14px 16px;
    border: 1px solid var(--att-border);
    box-shadow: 0 1px 3px rgba(0,0,0,.05);
    display: flex; align-items: center; gap: 12px;
    cursor: pointer; transition: transform .15s, box-shadow .15s, border-color .15s;
}
.av-stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.08); }
.av-stat-card.active { border-color: var(--att-green); background: var(--att-green-lt); }
.av-stat-card.active-blue { border-color: var(--att-blue); background: var(--att-blue-lt); }
.av-stat-icon {
    width: 40px; height: 40px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; flex-shrink: 0;
}
.av-stat-icon.green  { background: var(--att-green-lt); color: var(--att-green); }
.av-stat-icon.blue   { background: var(--att-blue-lt);  color: var(--att-blue); }
.av-stat-icon.amber  { background: var(--att-amber-lt); color: var(--att-amber); }
.av-stat-icon.red    { background: var(--att-red-lt);   color: var(--att-red); }
.av-stat-val   { font-family: 'Outfit', sans-serif; font-size: 20px; font-weight: 700; line-height: 1.1; color: #111827; }
.av-stat-lbl   { font-size: 11px; font-weight: 500; color: var(--att-gray); margin-top: 2px; }

/* ── Content Card ───────────────────────────────────────────────── */
.av-card {
    margin: 16px 28px 28px;
    background: #fff;
    border-radius: 14px;
    border: 1px solid var(--att-border);
    box-shadow: 0 2px 8px rgba(0,0,0,.04);
    overflow: hidden;
}
.av-card-hdr {
    padding: 14px 20px;
    border-bottom: 1px solid var(--att-border);
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 10px; background: #fafafa;
}

/* ── Tabs ── */
.av-tabs { display: flex; gap: 6px; background: #e2e8f0; padding: 4px; border-radius: 10px; }
.av-tab {
    padding: 6px 14px; border-radius: 7px; border: none;
    font-size: 12px; font-weight: 600; cursor: pointer;
    background: transparent; color: #475569;
    transition: all .15s; display: flex; align-items: center; gap: 6px;
}
.av-tab.active { background: #fff; color: #0f172a; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
.av-tab-count {
    background: #e2e8f0; color: #475569;
    font-size: 10px; font-weight: 700;
    padding: 1px 6px; border-radius: 10px;
}
.av-tab.active .av-tab-count { background: #0ea5e9; color: #fff; }

/* ── Summary Badges Row ── */
.av-summary-bar {
    padding: 12px 20px;
    background: #f8fafc;
    border-bottom: 1px solid var(--att-border);
    display: flex; flex-direction: column; gap: 8px;
}
.av-summary-title { font-size: 12px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 6px; }
.av-summary-info { font-size: 11px; color: #64748b; font-weight: 400; margin-left: 4px; }
.av-summary-badges { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
.av-summary-btn {
    display: inline-flex; align-items: center; gap: 6px;
    background: #fff; border: 1px solid #cbd5e1;
    border-radius: 20px; padding: 4px 12px;
    font-size: 11px; font-weight: 600; color: #334155;
    cursor: pointer; transition: all 0.15s ease;
}
.av-summary-btn:hover { border-color: #0ea5e9; color: #0ea5e9; background: #f0f9ff; }
.av-summary-btn.active { background: #0ea5e9; border-color: #0ea5e9; color: #fff; }
.av-summary-btn .badge-num {
    background: rgba(0,0,0,0.06); padding: 1px 6px; border-radius: 10px; font-size: 10px;
}
.av-summary-btn.active .badge-num { background: rgba(255,255,255,0.25); color: #fff; }

/* ── Table ──────────────────────────────────────────────────────── */
.av-tbl-wrap { overflow-x: auto; }
table.av-tbl { width: 100%; border-collapse: collapse; font-size: 13px; }
table.av-tbl th {
    background: #f8fafc; padding: 10px 14px;
    font-size: 11px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .5px; color: #475569;
    border-bottom: 1.5px solid var(--att-border);
    white-space: nowrap; text-align: left;
}
table.av-tbl td {
    padding: 10px 14px; border-bottom: 1px solid var(--att-border);
    vertical-align: middle; color: #1e293b; white-space: nowrap;
}
table.av-tbl tbody tr:hover { background: #f8fafc; }

.av-labour-link { text-decoration: none; color: inherit; display: block; }
.av-labour-name { font-weight: 600; color: #0f172a; line-height: 1.2; }
.av-labour-id   { font-size: 11px; color: var(--att-gray); }

/* Badges & Pills */
.av-pill {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 9px; border-radius: 12px;
    font-size: 11px; font-weight: 600;
}
.av-pill.green  { background: var(--att-green-lt); color: var(--att-green); }
.av-pill.blue   { background: var(--att-blue-lt);  color: var(--att-blue); }
.av-pill.amber  { background: var(--att-amber-lt); color: var(--att-amber); }
.av-pill.red    { background: var(--att-red-lt);   color: var(--att-red); }
.av-pill.gray   { background: #f1f5f9; color: #475569; }

.av-time-badge {
    font-size: 11px; font-family: monospace; font-weight: 600;
}
.av-wh { font-weight: 600; color: var(--att-blue); }

.av-swap-row { background-color: #fffbeb !important; }
.av-swap-row:hover { background-color: #fef3c7 !important; }

/* Empty & Loading states */
.av-empty { text-align: center; padding: 48px 16px; color: var(--att-gray); }
.av-empty i { font-size: 36px; margin-bottom: 8px; opacity: .5; }
.av-loading { text-align: center; padding: 48px 16px; color: var(--att-gray); }
.av-spinner {
    width: 28px; height: 28px; border: 3px solid #cbd5e1;
    border-top-color: #0ea5e9; border-radius: 50%;
    animation: spin .8s linear infinite; margin: 0 auto 12px;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Responsiveness */
@media (max-width: 768px) {
    .av-header { padding: 16px; }
    .av-filters { padding: 12px 16px; }
    .av-stats-bar { padding: 12px 16px 0; }
    .av-card { margin: 12px 16px 20px; }
}
</style>

<div class="av-page">
    <!-- Header -->
    <div class="av-header">
        <div class="av-header-left">
            <div class="av-header-icon">
                <i class="ti ti-clock-check"></i>
            </div>
            <div>
                <h2>Labour OT Attendance View</h2>
                <p>Monitor real-time overtime check-ins, check-outs, scanner locations & OT purposes</p>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="av-live-badge">
                <div class="av-live-dot"></div>
                <span>LIVE OVERTIME MONITOR</span>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="av-filters">
        <div class="av-filter-group">
            <label class="av-filter-label">Attendance Date</label>
            <div class="av-input-wrap">
                <i class="ti ti-calendar"></i>
                <input type="date" id="av-date" value="<?php echo date('Y-m-d'); ?>">
            </div>
        </div>

        <div class="av-filter-group">
            <label class="av-filter-label">Labour Home Location</label>
            <div class="av-select-wrap">
                <i class="ti ti-building-community av-icon"></i>
                <select id="av-location">
                    <option value="all">All Locations</option>
                </select>
            </div>
        </div>

        <div class="av-filter-group">
            <label class="av-filter-label">QR Scanner Location</label>
            <div class="av-select-wrap">
                <i class="ti ti-qrcode av-icon"></i>
                <select id="av-polyhouse">
                    <option value="all">All QR Locations</option>
                </select>
            </div>
        </div>

        <!-- Scan By Filter Dropdown -->
        <div class="av-filter-group">
            <label class="av-filter-label">Scanned By</label>
            <div class="av-multiselect-wrap" id="scanby-multiselect">
                <div class="av-multiselect-trigger" id="scanby-trigger">
                    <i class="ti ti-user-check av-icon-left"></i>
                    <span class="av-multiselect-text">All Scanners</span>
                    <i class="ti ti-chevron-down av-icon-arrow"></i>
                </div>
                <div class="av-multiselect-dropdown" id="scanby-dropdown">
                    <div class="av-multiselect-option border-bottom pb-2 mb-1">
                        <label class="checkbox-container fw-bold">
                            <input type="checkbox" id="scanby-select-all" checked>
                            <span class="checkmark"></span>
                            <span class="option-text">Select All Scanners</span>
                        </label>
                    </div>
                    <div id="scanby-options-container">
                        <!-- Populated dynamically -->
                    </div>
                </div>
            </div>
        </div>

        <div class="av-filter-group" style="min-width: 220px; flex-grow: 1;">
            <label class="av-filter-label">Search Labour</label>
            <div class="av-input-wrap">
                <i class="ti ti-search"></i>
                <input type="text" id="av-search" placeholder="Search by name or ID..." onkeyup="filterTable()">
            </div>
        </div>

        <div style="display: flex; gap: 8px; margin-left: auto;">
            <button class="btn btn-primary btn-sm rounded-2 px-3 py-2 fw-bold" onclick="loadAttendanceView()" style="display: flex; align-items: center; gap: 6px;">
                <i class="ti ti-refresh"></i> Refresh
            </button>
            <button class="btn btn-outline-success btn-sm rounded-2 px-3 py-2 fw-bold" onclick="exportLabourExcel()" style="display: flex; align-items: center; gap: 6px;">
                <i class="ti ti-file-spreadsheet"></i> Export Excel
            </button>
            <?php if (isset($loginroleid) && $loginroleid <= 3) { ?>
            <button class="btn btn-outline-primary btn-sm rounded-2 px-3 py-2 fw-bold" id="addMissingAttBtn" style="display: flex; align-items: center; gap: 6px;">
                <i class="ti ti-plus"></i> Add Missing OT
            </button>
            <?php } ?>
        </div>
    </div>

    <!-- Statistics Bar -->
    <div class="av-stats-bar">
        <div class="av-stat-card active" onclick="setStatFilter('total', this)">
            <div class="av-stat-icon green"><i class="ti ti-users"></i></div>
            <div>
                <div class="av-stat-val" id="stat-total-labour">—</div>
                <div class="av-stat-lbl" id="stat-lbl-total">Total OT Check-In Labour</div>
            </div>
        </div>

        <div class="av-stat-card" onclick="setStatFilter('work_location', this)">
            <div class="av-stat-icon blue"><i class="ti ti-building-community"></i></div>
            <div>
                <div class="av-stat-val" id="stat-work-labour">—</div>
                <div class="av-stat-lbl" id="stat-lbl-work">Working OT Labours</div>
            </div>
        </div>

        <div class="av-stat-card" onclick="setStatFilter('merged', this)">
            <div class="av-stat-icon green"><i class="ti ti-clock-check"></i></div>
            <div>
                <div class="av-stat-val" id="stat-merged-total">—</div>
                <div class="av-stat-lbl" id="stat-lbl-merged">OT Attendance Breakdown</div>
            </div>
        </div>

        <div class="av-stat-card" onclick="setStatFilter('early', this)">
            <div class="av-stat-icon amber"><i class="ti ti-clock"></i></div>
            <div>
                <div class="av-stat-val" id="stat-early">—</div>
                <div class="av-stat-lbl">< 2 Hrs OT</div>
            </div>
        </div>

        <div class="av-stat-card" onclick="setStatFilter('swap', this)">
            <div class="av-stat-icon red"><i class="ti ti-arrows-exchange"></i></div>
            <div>
                <div class="av-stat-val" id="stat-swap-labours">—</div>
                <div class="av-stat-lbl">Swap OT Labours</div>
            </div>
        </div>
    </div>

    <!-- Main Content Card -->
    <div class="av-card">
        <div class="av-card-hdr">
            <div class="av-tabs">
                <button class="av-tab active" onclick="switchTab('all', this)">
                    <i class="ti ti-layout-grid"></i> All OT Records
                </button>
                <button class="av-tab" onclick="switchTab('mn', this)">
                    <i class="ti ti-door-enter"></i> Main Gate OT <span class="av-tab-count" id="tab-mn-count">0</span>
                </button>
                <button class="av-tab" onclick="switchTab('dp', this)">
                    <i class="ti ti-qrcode"></i> Polyhouse / QR OT <span class="av-tab-count" id="tab-dp-count">0</span>
                </button>
            </div>

            <div style="font-size: 12px; color: var(--att-gray); font-weight: 500;">
                Showing <strong id="av-record-count" style="color: #0f172a;">0</strong> records
            </div>
        </div>

        <!-- Dynamic Work Location Breakdown Report Bar -->
        <div class="av-summary-bar" id="work-location-summary-wrap" style="display:none;">
            <div class="av-summary-title">
                <i class="ti ti-chart-bar text-primary"></i>
                <span id="work-loc-summary-title">Work Location Summary</span>
                <span class="av-summary-info" id="work-loc-summary-info"></span>
            </div>
            <div class="av-summary-badges" id="work-location-badges-container">
                <!-- Dynamic location summary badges -->
            </div>
        </div>

        <!-- Dynamic Swap Location Breakdown Report Bar -->
        <div class="av-summary-bar" id="swap-location-summary-wrap" style="display:none;">
            <div class="av-summary-title">
                <i class="ti ti-arrows-exchange text-danger"></i>
                <span id="swap-loc-summary-title">Swap Scanner Location Summary</span>
                <span class="av-summary-info"><i class="ti ti-info-circle me-1"></i>Click any scanner location badge to filter swapped labours checked-in at that location</span>
            </div>
            <div class="av-summary-badges" id="swap-location-badges-container">
                <!-- Dynamic swap badges -->
            </div>
        </div>

        <div class="av-tbl-wrap">
            <table class="av-tbl" id="av-table">
                <thead>
                    <tr id="av-thead-row">
                        <!-- Dynamic columns by tab -->
                    </tr>
                </thead>
                <tbody id="av-tbody">
                    <tr>
                        <td colspan="14">
                            <div class="av-loading">
                                <div class="av-spinner"></div>
                                <p>Loading overtime attendance data…</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Check-Out Modal -->
<div class="modal fade" id="checkoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <form id="checkoutForm">
                <div class="modal-header bg-primary text-white py-3">
                    <div class="d-flex align-items-center">
                        <i class="ti ti-logout me-2 fs-4"></i>
                        <h5 class="modal-title mb-0 text-white fw-bold">Manual Check-Out (OT)</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="co-attendance-id">
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <h6 class="mb-0 fw-bold text-dark" id="co-labour-name">—</h6>
                            <span class="badge bg-secondary" id="co-labour-id-badge">ID: —</span>
                        </div>
                        <div class="small text-muted">
                            <span><i class="ti ti-calendar me-1"></i>Date: <strong id="co-att-date" class="text-dark">—</strong></span>
                            <span class="ms-3"><i class="ti ti-login me-1 text-success"></i>Check-In: <strong id="co-checkin-display" class="text-success">—</strong></span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark fs-13 mb-1">
                            <i class="ti ti-clock me-1 text-primary"></i> Select Check-Out Time <span class="text-danger">*</span>
                        </label>
                        <input type="time" class="form-control form-control-lg fw-bold" id="co-out-time" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark fs-13 mb-1">
                            <i class="ti ti-notes me-1 text-secondary"></i> Check-Out Remark <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" rows="3" id="co-remark" placeholder="Enter reason for manual check-out..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-3 border-top">
                    <button type="button" class="btn btn-outline-secondary px-4 me-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm" id="co-submit-btn">
                        <i class="ti ti-device-floppy me-1"></i> Update Check-Out
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Missing Attendance Modal -->
<div class="modal fade" id="addMissingAttModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <form id="addMissingAttForm">
                <div class="modal-header bg-primary text-white py-3">
                    <div class="d-flex align-items-center">
                        <i class="ti ti-user-plus me-2 fs-4"></i>
                        <h5 class="modal-title mb-0 text-white fw-bold">Add Missing OT Attendance</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- Labour Autocomplete Search -->
                        <div class="col-md-12 mb-1 position-relative">
                            <label class="form-label fw-bold text-dark fs-13 mb-1">
                                <i class="ti ti-search me-1 text-primary"></i> Search & Select Labour <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="ma-labour-search" placeholder="Type labour name or ID to search..." autocomplete="off" required>
                            <input type="hidden" id="ma-labour-id">
                            <div id="ma-search-results" class="list-group shadow-sm position-absolute w-100 d-none" style="z-index: 1050; max-height: 200px; overflow-y: auto; top: 100%;"></div>
                        </div>

                        <!-- Date -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-dark fs-13 mb-1">
                                <i class="ti ti-calendar me-1 text-primary"></i> Attendance Date <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control" id="ma-date" required>
                        </div>

                        <!-- Check-In Time -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-dark fs-13 mb-1">
                                <i class="ti ti-login me-1 text-success"></i> Check-In Time <span class="text-danger">*</span>
                            </label>
                            <input type="time" class="form-control" id="ma-in-time" required>
                        </div>

                        <!-- Check-Out Time -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-dark fs-13 mb-1">
                                <i class="ti ti-logout me-1 text-danger"></i> Check-Out Time (Optional)
                            </label>
                            <input type="time" class="form-control" id="ma-out-time">
                        </div>

                        <!-- Location -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark fs-13 mb-1">
                                <i class="ti ti-building-community me-1 text-info"></i> Attendance Location <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="ma-location" required>
                                <option value="">Select Location</option>
                            </select>
                        </div>

                        <!-- OT Purpose -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark fs-13 mb-1">
                                <i class="ti ti-notes me-1 text-warning"></i> OT Purpose / Work <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="ma-purposeofot" required>
                                <option value="General Overtime">General Overtime</option>
                                <option value="Extra Shift">Extra Shift</option>
                                <option value="Crop Harvesting">Crop Harvesting</option>
                                <option value="Packing & Sorting">Packing & Sorting</option>
                                <option value="Irrigation & Spray">Irrigation & Spray</option>
                                <option value="Maintenance Work">Maintenance Work</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-3 border-top">
                    <button type="button" class="btn btn-outline-secondary px-4 me-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm" id="ma-submit-btn">
                        <i class="ti ti-device-floppy me-1"></i> Record OT Attendance
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if(!isset($_GET['vw']) || $_GET['vw'] != 1){ require('footer.php'); } ?>

<script type="text/javascript">
var routurl      = $("body").data("routurl")      || "../";
var companyid    = $("body").data("companyid")    || "";
var subcompanyid = $("body").data("subcompanyid") || "";
var projectid    = $("body").data("projectid")    || "";
var loginroleid  = parseInt($("body").data("loginroleid") || "99");
var loginname    = $("body").data("loginname")    || "Admin";
var key          = $("body").data("key")          || "";
var mdid         = $("body").data("mdid")         || "";

var allRows    = [];
var currentTab = 'all';

$(document).ready(function() {
    LabourAttendanceLocationList('all', 'all', function(res) {
        var filterOpts = '<option value="all">All Locations</option>';
        var modalOpts = '<option value="">Select Location</option>';
        if (res && res.length > 0) {
            for (var i = 0; i < res.length; i++) {
                var locId = res[i].LocationId;
                var locName = res[i].LocationName;
                locationMap[locId] = locName;
                filterOpts += '<option value="' + locId + '">' + locName + '</option>';
                modalOpts += '<option value="' + locId + '">' + locName + '</option>';
            }
        }
        $('#av-location').html(filterOpts);
        $('#ma-location').html(modalOpts);
        loadPolyhouses($('#av-location').val());
    });

    loadAttendanceView();

    $('#av-location').on('change', function() {
        var loc = $(this).val();
        loadPolyhouses(loc);
        resetScanByDropdown();
        loadAttendanceView();
    });

    $('#av-polyhouse, #av-date').on('change', function() {
        resetScanByDropdown();
        loadAttendanceView();
    });
});

function resetScanByDropdown() {
    selectedScanners.clear();
    $('#scanby-select-all').prop('checked', true);
    $('.scanby-checkbox').prop('checked', true);
    $('.av-multiselect-text').text('All Scanners');
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
            for (var i = 0; i < data.length; i++) {
                opts += '<option value="' + data[i].polyhouseid + '">' + data[i].polyhousename + '</option>';
            }
            $('#av-polyhouse').html(opts);
        },
        error: function() {
            $('#av-polyhouse').html('<option value="all">All QR Locations</option><option value="main-gate">Main Gate</option>');
        }
    });
}

function loadAttendanceView() {
    var locationid  = $('#av-location').val() || 'all';
    var polyhouseid = $('#av-polyhouse').val() || 'all';
    var attdate     = $('#av-date').val();
    var scanby      = 'all';

    if (!attdate) {
        Swal.fire({ icon: 'warning', title: 'Date Required', text: 'Please select a date.', confirmButtonColor: '#1b7a48' });
        return;
    }

    $('#av-tbody').html('<tr><td colspan="14"><div class="av-loading"><div class="av-spinner"></div><p>Loading OT attendance data…</p></div></td></tr>');
    resetStats();

    $.ajax({
        type: 'POST',
        url: routurl + 'modal/hrm_laboursalarymodal.php?action=GetOTAttendanceViewData',
        data: {
            companyid:    companyid,
            subcompanyid: subcompanyid,
            projectid:    projectid,
            locationid:   locationid,
            polyhouseid:  polyhouseid,
            attdate:      attdate,
            scanby:       scanby
        },
        success: function(res) {
            var data = [];
            try { data = (typeof res === 'string') ? JSON.parse(res) : res; } catch(e) {
                console.error('Parse error', e, res);
            }
            if (data.status && data.status === 'error') {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                resetTable();
                return;
            }
            allRows = data;
            populateScanByDropdown(data);
            updateStatsForTab(currentTab, allRows);
            renderTable(currentTab);
        },
        error: function(xhr) {
            Swal.fire({ icon: 'error', title: 'Request Failed', text: 'Could not load OT attendance data.' });
            resetTable();
        }
    });
}

function switchTab(tab, el) {
    currentTab = tab;
    $('.av-tab').removeClass('active');
    $(el).addClass('active');
    renderTable(tab);
}

var currentStatFilter = 'total';
var currentHomeLocFilter = 'all';
var currentCheckinLocFilter = 'all';
var currentSwapScannerLocFilter = 'all';

function setStatFilter(filter, el) {
    $('.av-stat-card').removeClass('active active-blue');
    currentHomeLocFilter = 'all';
    currentCheckinLocFilter = 'all';
    currentSwapScannerLocFilter = 'all';
    if (currentStatFilter === filter) {
        currentStatFilter = 'all';
        $('#work-location-summary-wrap').hide();
        $('#swap-location-summary-wrap').hide();
    } else {
        currentStatFilter = filter;
        if (filter === 'total') {
            $(el).addClass('active');
            $('#work-location-summary-wrap').show();
            $('#swap-location-summary-wrap').hide();
        } else if (filter === 'work_location') {
            $(el).addClass('active-blue');
            $('#work-location-summary-wrap').show();
            $('#swap-location-summary-wrap').hide();
        } else if (filter === 'merged') {
            $(el).addClass('active');
            $('#work-location-summary-wrap').show();
            $('#swap-location-summary-wrap').hide();
        } else if (filter === 'swap') {
            $(el).addClass('active');
            $('#work-location-summary-wrap').hide();
            $('#swap-location-summary-wrap').show();
        } else {
            $(el).addClass('active');
            $('#work-location-summary-wrap').hide();
            $('#swap-location-summary-wrap').hide();
        }
    }
    updateStatsForTab(currentTab, allRows);
    renderTable(currentTab);
}

function renderTable(tab) {
    var data = allRows;

    if (tab === 'mn') {
        data = data.filter(function(r) { return r.checkintime_mn; });
    } else if (tab === 'dp') {
        data = data.filter(function(r) { return r.checkintime_dp; });
    } else {
        data = data.filter(function(r) { return r.checkintime_mn || r.checkintime_dp; });
    }

    if (currentStatFilter === 'total') {
        var selectedLocation = $('#av-location').val() || 'all';
        data = data.filter(function(r) {
            var regLoc = r.labourlocationid || r.locationid || '';
            var ci     = (tab === 'mn') ? r.checkintime_mn : (tab === 'dp') ? r.checkintime_dp : (r.checkintime_mn || r.checkintime_dp);
            if (!ci) return false;
            if (selectedLocation === 'all') {
                return true;
            } else {
                return isSameLocation(regLoc, selectedLocation);
            }
        });

        if (currentCheckinLocFilter !== 'all') {
            data = data.filter(function(r) {
                var checkinLoc = (tab === 'mn') ? (r.checkinlocation_mn || '') : (tab === 'dp' ? (r.checkinlocation_dp || '') : (r.checkinlocation_mn || r.checkinlocation_dp || ''));
                return isSameLocation(checkinLoc, currentCheckinLocFilter);
            });
        }
    } else if (currentStatFilter === 'work_location' || currentStatFilter === 'merged') {
        var selectedLocation = $('#av-location').val() || 'all';
        data = data.filter(function(r) {
            var regLoc = r.labourlocationid || r.locationid || '';
            var checkinLoc = (tab === 'mn') ? (r.checkinlocation_mn || '') : (tab === 'dp' ? (r.checkinlocation_dp || '') : (r.checkinlocation_mn || r.checkinlocation_dp || ''));
            var ci = (tab === 'mn') ? r.checkintime_mn : (tab === 'dp') ? r.checkintime_dp : (r.checkintime_mn || r.checkintime_dp);
            if (!ci) return false;
            if (selectedLocation === 'all') {
                return true;
            } else {
                return isSameLocation(checkinLoc, selectedLocation) || (checkinLoc === '' && isSameLocation(regLoc, selectedLocation));
            }
        });

        if (currentHomeLocFilter !== 'all') {
            data = data.filter(function(r) {
                var homeLoc = r.labourlocationid || r.locationid || 'Unknown';
                return isSameLocation(homeLoc, currentHomeLocFilter);
            });
        }
    } else if (currentStatFilter === 'present') {
        data = data.filter(function(r) {
            var ci = (tab === 'mn') ? r.checkintime_mn : (tab === 'dp') ? r.checkintime_dp : (r.checkintime_mn || r.checkintime_dp);
            var co = (tab === 'mn') ? r.checkouttime_mn : (tab === 'dp') ? r.checkouttime_dp : (r.checkouttime_mn || r.checkouttime_dp);
            var wh = (tab === 'mn') ? r.workinghours_mn : (tab === 'dp') ? r.workinghours_dp : (r.workinghours_mn || r.workinghours_dp);
            if (ci && co && wh && wh !== '00:00:00') {
                var parts = wh.split(':');
                var hrs = parseInt(parts[0] || 0) + (parseInt(parts[1] || 0) / 60);
                return hrs >= 2;
            }
            return false;
        });
    } else if (currentStatFilter === 'missing') {
        data = data.filter(function(r) {
            var ci = (tab === 'mn') ? r.checkintime_mn : (tab === 'dp') ? r.checkintime_dp : (r.checkintime_mn || r.checkintime_dp);
            var co = (tab === 'mn') ? r.checkouttime_mn : (tab === 'dp') ? r.checkouttime_dp : (r.checkouttime_mn || r.checkouttime_dp);
            return ci && !co;
        });
    } else if (currentStatFilter === 'early') {
        data = data.filter(function(r) {
            var co = (tab === 'mn') ? r.checkouttime_mn : (tab === 'dp') ? r.checkouttime_dp : (r.checkouttime_mn || r.checkouttime_dp);
            var wh = (tab === 'mn') ? r.workinghours_mn : (tab === 'dp') ? r.workinghours_dp : (r.workinghours_mn || r.workinghours_dp);
            if (co && wh && wh !== '00:00:00') {
                var parts = wh.split(':');
                var hrs = parseInt(parts[0] || 0) + (parseInt(parts[1] || 0) / 60);
                return hrs < 2;
            }
            return false;
        });
    } else if (currentStatFilter === 'swap') {
        var selectedLocation = $('#av-location').val() || 'all';
        data = data.filter(function(r) {
            var regLoc = r.labourlocationid || r.locationid || '';
            var checkinLoc = (tab === 'mn') ? (r.checkinlocation_mn || '') : (tab === 'dp' ? (r.checkinlocation_dp || '') : (r.checkinlocation_mn || r.checkinlocation_dp || ''));
            var ci = (tab === 'mn') ? r.checkintime_mn : (tab === 'dp') ? r.checkintime_dp : (r.checkintime_mn || r.checkintime_dp);
            if (!ci) return false;
            if (selectedLocation === 'all') {
                return checkinLoc !== '' && !isSameLocation(regLoc, checkinLoc);
            } else {
                var isOutgoingSwap = isSameLocation(regLoc, selectedLocation) && checkinLoc !== '' && !isSameLocation(checkinLoc, selectedLocation);
                var isIncomingSwap = isSameLocation(checkinLoc, selectedLocation) && !isSameLocation(regLoc, selectedLocation);
                return isOutgoingSwap || isIncomingSwap;
            }
        });

        renderSwapLocationSummaryReport(tab, data);

        if (currentSwapScannerLocFilter !== 'all') {
            data = data.filter(function(r) {
                var scanLoc = (tab === 'mn') ? (r.checkinlocation_mn || '') : (tab === 'dp' ? (r.checkinlocation_dp || '') : (r.checkinlocation_mn || r.checkinlocation_dp || ''));
                return isSameLocation(scanLoc, currentSwapScannerLocFilter);
            });
        }
    }

    var totalCheckboxes = $('.scanby-checkbox').length;
    var checkedCheckboxes = $('.scanby-checkbox:checked').length;
    
    if (checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes) {
        data = data.filter(function(r) {
            var inMN = r.checkinby_mn ? r.checkinby_mn.trim() : null;
            var outMN = r.checkoutby_mn ? r.checkoutby_mn.trim() : null;
            var inDP = r.checkinby_dp ? r.checkinby_dp.trim() : null;
            var outDP = r.checkoutby_dp ? r.checkoutby_dp.trim() : null;
            
            if (tab === 'mn') {
                return (inMN && selectedScanners.has(inMN)) || (outMN && selectedScanners.has(outMN));
            } else if (tab === 'dp') {
                return (inDP && selectedScanners.has(inDP)) || (outDP && selectedScanners.has(outDP));
            } else {
                return (inMN && selectedScanners.has(inMN)) || (outMN && selectedScanners.has(outMN)) || (inDP && selectedScanners.has(inDP)) || (outDP && selectedScanners.has(outDP));
            }
        });
    } else if (totalCheckboxes > 0 && checkedCheckboxes === 0) {
        data = [];
    }

    var searchVal = ($('#av-search').val() || '').toLowerCase().trim();

    if (searchVal) {
        data = data.filter(function(r) {
            return (r.FullName || '').toLowerCase().includes(searchVal) || (r.EmployeeID || '').toLowerCase().includes(searchVal);
        });
    }

    var thead = '';
    var tbody = '';
    var count = 0;
    var baseHead = '<th>#</th><th>Labour Name</th><th>OT Purpose</th>';

    if (tab === 'mn') {
        thead = baseHead + '<th>Check-In (Main Gate OT)</th><th>Check-Out (Main Gate OT)</th><th>OT Working Hrs</th><th>Scanner Location</th><th>Status</th>';
        if (loginroleid <= 3) thead += '<th class="text-end pe-4 noexport">Action</th>';
        data.forEach(function(r, i) {
            var ci = r.checkintime_mn  ? formatDT(r.checkintime_mn)  : null;
            var co = r.checkouttime_mn ? formatDT(r.checkouttime_mn) : null;
            var wh = r.workinghours_mn || null;
            var sc = r.checkinscannerid_mn || '—';
            var st = getStatusPill(ci, co, wh);

            var regLoc = r.labourlocationid || r.locationid;
            var isSwap = r.checkintime_mn && r.checkinlocation_mn && r.checkinlocation_mn.startsWith('LOC') && r.checkinlocation_mn !== regLoc;

            tbody += buildRow(i+1, r, ci, co, wh, sc, st, r.checkinby_mn, r.checkoutby_mn, isSwap);
            count++;
        });
    } else if (tab === 'dp') {
        thead = baseHead + '<th>Check-In (QR Loc OT)</th><th>Check-Out (QR Loc OT)</th><th>OT Working Hrs</th><th>Scanner Location</th><th>Status</th>';
        if (loginroleid <= 3) thead += '<th class="text-end pe-4 noexport">Action</th>';
        data.forEach(function(r, i) {
            var ci = r.checkintime_dp  ? formatDT(r.checkintime_dp)  : null;
            var co = r.checkouttime_dp ? formatDT(r.checkouttime_dp) : null;
            var wh = r.workinghours_dp || null;
            var sc = r.checkinscannerid_dp || '—';
            var st = getStatusPill(ci, co, wh);

            var regLoc = r.labourlocationid || r.locationid;
            var isSwap = r.checkintime_dp && r.checkinlocation_dp && r.checkinlocation_dp.startsWith('LOC') && r.checkinlocation_dp !== regLoc;

            tbody += buildRow(i+1, r, ci, co, wh, sc, st, r.checkinby_dp, r.checkoutby_dp, isSwap);
            count++;
        });
    } else {
        thead = baseHead + '<th>MN Check-In</th><th>MN Check-Out</th><th>MN Hrs</th><th>DP Check-In</th><th>DP Check-Out</th><th>DP Hrs</th><th>Status</th>';
        if (loginroleid <= 3) thead += '<th class="text-end pe-4 noexport">Action</th>';
        data.forEach(function(r, i) {
            var ciMN = r.checkintime_mn  ? formatDT(r.checkintime_mn)  : null;
            var coMN = r.checkouttime_mn ? formatDT(r.checkouttime_mn) : null;
            var whMN = r.workinghours_mn || null;
            var ciDP = r.checkintime_dp  ? formatDT(r.checkintime_dp)  : null;
            var coDP = r.checkouttime_dp ? formatDT(r.checkouttime_dp) : null;
            var whDP = r.workinghours_dp || null;
            var overallCI = ciMN || ciDP;
            var overallCO = coMN || coDP;
            var overallWH = whMN || whDP;
            var st = getStatusPill(overallCI, overallCO, overallWH);
            var actionTd = '';
            if (loginroleid <= 3) {
                if (r.attendanceid) {
                    var coBtn = '<button class="btn btn-sm btn-outline-primary me-1" onclick="openCheckoutModal(\'' + r.attendanceid + '\', \'' + htmlEscape(r.FullName || '') + '\', \'' + htmlEscape(r.EmployeeID || '') + '\', \'' + htmlEscape(r.attendancedate || '') + '\', \'' + (r.checkintime_mn || r.checkintime_dp || '') + '\', \'' + (r.checkouttime_mn || r.checkouttime_dp || '') + '\', \'' + htmlEscape(r.remark_mn || '') + '\')" title="Check-Out / Edit Remark"><i class="ti ti-logout"></i></button>';
                    var delBtn = '<button class="btn btn-sm btn-outline-danger" onclick="removeAttendance(\'' + r.attendanceid + '\', \'' + htmlEscape(r.FullName || '') + '\')" title="Remove Attendance"><i class="ti ti-trash"></i></button>';
                    actionTd = '<td class="text-end pe-4">' + coBtn + delBtn + '</td>';
                } else {
                    actionTd = '<td class="text-end pe-4"><button class="btn btn-sm btn-outline-secondary me-1" disabled title="No Attendance Record"><i class="ti ti-logout"></i></button><button class="btn btn-sm btn-outline-secondary" disabled title="No Attendance to Remove"><i class="ti ti-trash"></i></button></td>';
                }
            }

            var regLoc = r.labourlocationid || r.locationid;
            var isSwapMN = r.checkintime_mn && r.checkinlocation_mn && r.checkinlocation_mn.startsWith('LOC') && r.checkinlocation_mn !== regLoc;
            var isSwapDP = r.checkintime_dp && r.checkinlocation_dp && r.checkinlocation_dp.startsWith('LOC') && r.checkinlocation_dp !== regLoc;
            var isSwap = isSwapMN || isSwapDP;
            var rowClass = isSwap ? ' class="av-swap-row"' : '';
            var otPurposeBadge = r.purposeofot ? '<span class="badge bg-warning-subtle text-warning-emphasis fw-semibold"><i class="ti ti-notes me-1"></i>' + htmlEscape(r.purposeofot) + '</span>' : '<span class="text-muted small">General OT</span>';

            tbody += '<tr' + rowClass + ' data-name="'+(r.FullName||'').toLowerCase()+'" data-id="'+(r.EmployeeID||'').toLowerCase()+'">' +
                '<td style="color:var(--att-gray);font-size:11px;">'+(i+1)+'</td>' +
                '<td><a href="../labourdash/index.php?empid=' + encodeURIComponent(r.EmployeeID) + '&k=' + encodeURIComponent(key) + '&mdid=' + encodeURIComponent(mdid) + '" class="av-labour-link"><div class="av-labour-name">'+htmlEscape(r.FullName || '—')+'</div><div class="av-labour-id">'+htmlEscape(r.EmployeeID || '')+'</div></a></td>' +
                '<td>' + otPurposeBadge + '</td>' +
                '<td>'+timeBadge(ciMN,'checkin', r.checkinby_mn)+'</td>' +
                '<td>'+timeBadge(coMN,'checkout', r.checkoutby_mn, r.remark_mn)+'</td>' +
                '<td>'+whBadge(whMN)+'</td>' +
                '<td>'+timeBadge(ciDP,'checkin', r.checkinby_dp)+'</td>' +
                '<td>'+timeBadge(coDP,'checkout', r.checkoutby_dp, r.remark_mn)+'</td>' +
                '<td>'+whBadge(whDP)+'</td>' +
                '<td>'+st+'</td>' +
                actionTd +
                '</tr>';
            count++;
        });
    }

    $('#av-thead-row').html(thead);
    $('#av-tbody').html(count > 0 ? tbody : '<tr><td colspan="14"><div class="av-empty"><i class="ti ti-user-off"></i><p>No OT attendance records found for the selected filters.</p></div></td></tr>');
    $('#av-record-count').text(count);

    var mnCount = allRows.filter(r => r.checkintime_mn).length;
    var dpCount = allRows.filter(r => r.checkintime_dp).length;
    $('#tab-mn-count').text(mnCount);
    $('#tab-dp-count').text(dpCount);

    var tabRows = allRows;
    if (tab === 'mn') {
        tabRows = tabRows.filter(function(r) { return r.checkintime_mn; });
    } else if (tab === 'dp') {
        tabRows = tabRows.filter(function(r) { return r.checkintime_dp; });
    } else {
        tabRows = tabRows.filter(function(r) { return r.checkintime_mn || r.checkintime_dp; });
    }
    updateStatsForTab(tab, tabRows);
}

function buildRow(idx, r, ci, co, wh, sc, statusPill, ci_by, co_by, isSwap) {
    var actionTd = '';
    if (loginroleid <= 3) {
        if (r.attendanceid) {
            var coBtn = '<button class="btn btn-sm btn-outline-primary me-1" onclick="openCheckoutModal(\'' + r.attendanceid + '\', \'' + htmlEscape(r.FullName || '') + '\', \'' + htmlEscape(r.EmployeeID || '') + '\', \'' + htmlEscape(r.attendancedate || '') + '\', \'' + (r.checkintime_mn || r.checkintime_dp || '') + '\', \'' + (r.checkouttime_mn || r.checkouttime_dp || '') + '\', \'' + htmlEscape(r.remark_mn || '') + '\')" title="Check-Out / Edit Remark"><i class="ti ti-logout"></i></button>';
            var delBtn = '<button class="btn btn-sm btn-outline-danger" onclick="removeAttendance(\'' + r.attendanceid + '\', \'' + htmlEscape(r.FullName || '') + '\')" title="Remove OT Attendance"><i class="ti ti-trash"></i></button>';
            actionTd = '<td class="text-end pe-4">' + coBtn + delBtn + '</td>';
        } else {
            actionTd = '<td class="text-end pe-4"><button class="btn btn-sm btn-outline-secondary me-1" disabled title="No Attendance Record"><i class="ti ti-logout"></i></button><button class="btn btn-sm btn-outline-secondary" disabled title="No Attendance to Remove"><i class="ti ti-trash"></i></button></td>';
        }
    }
    var rowClass = isSwap ? ' class="av-swap-row"' : '';
    var scannerBadge = (sc && sc !== '—') ? '<span class="badge bg-light text-dark border"><i class="ti ti-map-pin me-1"></i>' + htmlEscape(sc) + '</span>' : '—';
    var otPurposeBadge = r.purposeofot ? '<span class="badge bg-warning-subtle text-warning-emphasis fw-semibold"><i class="ti ti-notes me-1"></i>' + htmlEscape(r.purposeofot) + '</span>' : '<span class="text-muted small">General OT</span>';

    return '<tr' + rowClass + ' data-name="'+(r.FullName||'').toLowerCase()+'" data-id="'+(r.EmployeeID||'').toLowerCase()+'">' +
        '<td style="color:var(--att-gray);font-size:11px;">'+idx+'</td>' +
        '<td><a href="../labourdash/index.php?empid=' + encodeURIComponent(r.EmployeeID) + '&k=' + encodeURIComponent(key) + '&mdid=' + encodeURIComponent(mdid) + '" class="av-labour-link"><div class="av-labour-name">'+htmlEscape(r.FullName || '—')+'</div><div class="av-labour-id">'+htmlEscape(r.EmployeeID||'')+'</div></a></td>' +
        '<td>' + otPurposeBadge + '</td>' +
        '<td>'+timeBadge(ci, 'checkin', ci_by)+'</td>' +
        '<td>'+timeBadge(co, 'checkout', co_by, r.remark_mn || r.remark_dp || r.remark)+'</td>' +
        '<td class="fw-semibold text-dark">'+(wh ? wh + ' hrs' : '—')+'</td>' +
        '<td>'+scannerBadge+'</td>' +
        '<td>'+statusPill+'</td>' +
        actionTd +
        '</tr>';
}

function timeBadge(timeStr, type, scanBy, remark) {
    if (!timeStr || timeStr === '-') {
        return '<span class="text-muted small">—</span>';
    }
    var isCheckin = (type === 'checkin');
    var badgeClass = isCheckin ? 'bg-success bg-opacity-10 text-success border border-success' : 'bg-primary bg-opacity-10 text-primary border border-primary';
    var icon = isCheckin ? 'ti-login' : 'ti-logout';
    var title = scanBy ? ('Scanned by: ' + scanBy) : '';
    if (remark) title += (title ? ' | Remark: ' : 'Remark: ') + remark;

    var byHtml = scanBy ? ('<div class="av-scanby-subtext text-muted mt-1" style="font-size:11px; font-weight:500; color:#555;"><i class="ti ti-user-check me-1" style="font-size:11px;"></i>' + htmlEscape(scanBy) + '</div>') : '';

    return '<div class="d-inline-flex flex-column align-items-start">' +
        '<span class="badge ' + badgeClass + ' av-time-badge px-2 py-1" title="' + htmlEscape(title) + '">' +
        '<i class="ti ' + icon + ' me-1"></i>' + htmlEscape(timeStr) +
        '</span>' +
        byHtml +
        '</div>';
}

function whBadge(wh) {
    return wh ? '<span class="av-wh">' + htmlEscape(wh) + ' hrs</span>' : '<span class="text-muted small">—</span>';
}

function getStatusPill(ci, co, wh) {
    if (ci && co) {
        return '<span class="badge bg-success"><i class="ti ti-check me-1"></i>Completed</span>';
    } else if (ci) {
        return '<span class="badge bg-warning text-dark"><i class="ti ti-clock me-1"></i>Checked-In</span>';
    } else {
        return '<span class="badge bg-secondary"><i class="ti ti-user-x me-1"></i>Pending</span>';
    }
}

function isManualAttendance(r, tab) {
    if (!r) return false;
    
    var checkMN = function(r) {
        if (r.ismanual_mn == 1 || r.ismanual_mn == '1') return true;
        if (r.checkinscannerid_mn && String(r.checkinscannerid_mn).toLowerCase().includes('manual')) return true;
        if (r.sourcein_mn && String(r.sourcein_mn).toLowerCase().includes('manual')) return true;
        if (r.checkinby_mn && String(r.checkinby_mn).toLowerCase().includes('manual')) return true;
        return false;
    };

    var checkDP = function(r) {
        if (r.ismanual_dp == 1 || r.ismanual_dp == '1') return true;
        if (r.checkinscannerid_dp && String(r.checkinscannerid_dp).toLowerCase().includes('manual')) return true;
        if (r.sourcein_dp && String(r.sourcein_dp).toLowerCase().includes('manual')) return true;
        if (r.checkinby_dp && String(r.checkinby_dp).toLowerCase().includes('manual')) return true;
        return false;
    };

    if (tab === 'mn') {
        return checkMN(r);
    } else if (tab === 'dp') {
        return checkDP(r);
    } else {
        return checkMN(r) || checkDP(r);
    }
}

function getLocationCanonicalId(loc) {
    if (!loc || loc === '' || loc === 'all') return 'all';
    var str = String(loc).trim().toLowerCase();
    
    var canonicalId = null;
    $('#av-location option').each(function() {
        var optVal = String($(this).val()).trim().toLowerCase();
        var optText = String($(this).text()).trim().toLowerCase();
        if (optVal && optVal !== 'all' && optVal !== '') {
            var paddedVal = 'loc-' + optVal.padStart(2, '0');
            var rawVal = 'loc-' + optVal;
            if (str === optVal || str === optText || str === paddedVal || str === rawVal) {
                canonicalId = optVal;
                return false;
            }
            if (optText && (str.indexOf(optText) > -1 || optText.indexOf(str) > -1)) {
                canonicalId = optVal;
                return false;
            }
        }
    });
    
    if (canonicalId) return canonicalId;
    return str.replace(/^loc-0*/, '').replace(/^location\s*/, '');
}

function isSameLocation(locA, locB) {
    if (!locA || !locB) return false;
    if (locA === 'all' || locB === 'all') return true;
    var idA = getLocationCanonicalId(locA);
    var idB = getLocationCanonicalId(locB);
    return idA === idB;
}

function updateStatsForTab(tab, tabRows) {
    var selectedLocation = $('#av-location').val() || 'all';
    var selLocName = (selectedLocation !== 'all') ? ($('#av-location option:selected').text() || '') : '';

    if (selectedLocation !== 'all' && selLocName && selLocName !== 'All Locations') {
        $('#stat-lbl-total').text('Check-In OT Labour of ' + selLocName);
        $('#stat-lbl-work').text('Working OT Labours in ' + selLocName);
        $('#stat-lbl-merged').text('OT Attendance in ' + selLocName);
    } else {
        $('#stat-lbl-total').text('Total OT Check-In Labour');
        $('#stat-lbl-work').text('Total OT Work Labour');
        $('#stat-lbl-merged').text('OT Attendance Breakdown');
    }

    var total    = 0;
    var workLoc  = 0;
    var early    = 0;
    var swap     = 0;

    var mergedCheckin  = 0;
    var mergedCheckout = 0;
    var mergedManual   = 0;

    tabRows.forEach(function(r) {
        var regLoc = r.labourlocationid || r.locationid || '';
        var checkinLoc = (tab === 'mn') ? (r.checkinlocation_mn || '') : (tab === 'dp' ? (r.checkinlocation_dp || '') : (r.checkinlocation_mn || r.checkinlocation_dp || ''));
        var ci = (tab === 'mn') ? r.checkintime_mn : (tab === 'dp') ? r.checkintime_dp : (r.checkintime_mn || r.checkintime_dp);
        var co = (tab === 'mn') ? r.checkouttime_mn : (tab === 'dp') ? r.checkouttime_dp : (r.checkouttime_mn || r.checkouttime_dp);
        var wh = (tab === 'mn') ? r.workinghours_mn : (tab === 'dp') ? r.workinghours_dp : (r.workinghours_mn || r.workinghours_dp);

        if (selectedLocation === 'all') {
            if (ci) total++;
        } else {
            if (ci && isSameLocation(regLoc, selectedLocation)) total++;
        }

        var isWorkingInLoc = false;
        if (selectedLocation === 'all') {
            if (ci) isWorkingInLoc = true;
        } else {
            if (ci && (isSameLocation(checkinLoc, selectedLocation) || (checkinLoc === '' && isSameLocation(regLoc, selectedLocation)))) {
                isWorkingInLoc = true;
            }
        }

        if (isWorkingInLoc) {
            workLoc++;

            if (isManualAttendance(r, tab)) {
                mergedManual++;
            } else {
                mergedCheckin++;
            }
            if (co) {
                mergedCheckout++;
            }
        }

        if (isWorkingInLoc && ci && co) {
            if (wh && wh !== '00:00:00') {
                var parts = wh.split(':');
                var hrs = parseInt(parts[0] || 0) + (parseInt(parts[1] || 0) / 60);
                if (hrs < 2) {
                    early++;
                }
            }
        }

        if (selectedLocation === 'all') {
            if (ci && checkinLoc !== '' && !isSameLocation(regLoc, checkinLoc)) {
                swap++;
            }
        } else {
            var isOutgoingSwap = ci && isSameLocation(regLoc, selectedLocation) && checkinLoc !== '' && !isSameLocation(checkinLoc, selectedLocation);
            var isIncomingSwap = ci && isSameLocation(checkinLoc, selectedLocation) && !isSameLocation(regLoc, selectedLocation);
            if (isOutgoingSwap || isIncomingSwap) {
                swap++;
            }
        }
    });

    var mergedTotal = mergedCheckin + mergedManual;

    $('#stat-total-labour').text(total);
    $('#stat-work-labour').text(workLoc);
    $('#stat-early').text(early);
    $('#stat-swap-labours').text(swap);

    $('#stat-merged-total').text(mergedTotal);
    $('#stat-merged-checkin').text(mergedCheckin);
    $('#stat-merged-checkout').text(mergedCheckout);
    $('#stat-merged-manual').text(mergedManual);

    if (currentStatFilter === 'total') {
        $('#work-location-summary-wrap').show();
        $('#swap-location-summary-wrap').hide();
        renderTotalLocationSummaryReport(tab, tabRows);
    } else if (currentStatFilter === 'work_location' || currentStatFilter === 'merged') {
        $('#work-location-summary-wrap').show();
        $('#swap-location-summary-wrap').hide();
        renderWorkLocationSummaryReport(tab, tabRows);
    } else if (currentStatFilter === 'swap') {
        $('#work-location-summary-wrap').hide();
        $('#swap-location-summary-wrap').show();
        renderSwapLocationSummaryReport(tab, tabRows);
    } else {
        $('#work-location-summary-wrap').hide();
        $('#swap-location-summary-wrap').hide();
    }
}

function getLocationName(locId) {
    if (!locId || locId === '' || locId === 'all') return 'All Locations';
    if (locationMap[locId]) return locationMap[locId];
    
    var trimmed = String(locId).trim();
    if (locationMap[trimmed]) return locationMap[trimmed];

    var lower = trimmed.toLowerCase();
    for (var k in locationMap) {
        if (String(k).trim().toLowerCase() === lower) {
            return locationMap[k];
        }
    }

    var canonicalId = getLocationCanonicalId(locId);
    if (locationMap[canonicalId]) return locationMap[canonicalId];

    return locId;
}

function renderTotalLocationSummaryReport(tab, tabRows) {
    var selectedLocation = $('#av-location').val() || 'all';
    var selLocName = $('#av-location option:selected').text() || 'All Locations';
    $('#work-loc-summary-title').text('Home Location: ' + selLocName);
    $('#work-loc-summary-info').html('<i class="ti ti-info-circle me-1"></i>Click any badge to filter labours by check-in location below');

    var homeRows = tabRows.filter(function(r) {
        var regLoc = r.labourlocationid || r.locationid || '';
        var ci = (tab === 'mn') ? r.checkintime_mn 
               : (tab === 'dp') ? r.checkintime_dp 
               : (r.checkintime_mn || r.checkintime_dp);
        if (!ci) return false;
        if (selectedLocation === 'all') {
            return true;
        } else {
            return regLoc === selectedLocation;
        }
    });

    var checkinLocCounts = {};
    var totalCount = homeRows.length;

    homeRows.forEach(function(r) {
        var cLoc = (tab === 'mn') ? (r.checkinlocation_mn || 'Unknown') 
                 : (tab === 'dp' ? (r.checkinlocation_dp || 'Unknown') 
                 : (r.checkinlocation_mn || r.checkinlocation_dp || 'Unknown'));
        checkinLocCounts[cLoc] = (checkinLocCounts[cLoc] || 0) + 1;
    });

    var badgesHtml = '';
    var allActive = (currentCheckinLocFilter === 'all') ? 'active' : '';
    badgesHtml += '<button type="button" class="av-summary-btn ' + allActive + '" onclick="filterByCheckinLoc(\'all\')">' +
        '<i class="ti ti-layout-grid"></i> All Check-In Locations <span class="badge-num">' + totalCount + '</span></button>';

    Object.keys(checkinLocCounts).forEach(function(locId) {
        var locName = getLocationName(locId);
        var count = checkinLocCounts[locId];
        var isSelected = (currentCheckinLocFilter === locId);
        var activeClass = isSelected ? 'active' : '';
        
        badgesHtml += '<button type="button" class="av-summary-btn ' + activeClass + '" onclick="filterByCheckinLoc(\'' + htmlEscape(locId) + '\')">' +
            '<i class="ti ti-map-pin"></i> ' + htmlEscape(locName) + ' <span class="badge-num">' + count + '</span></button>';
    });

    $('#work-location-badges-container').html(badgesHtml);
}

function filterByCheckinLoc(locId) {
    currentCheckinLocFilter = locId;
    renderTable(currentTab);
}

function renderWorkLocationSummaryReport(tab, tabRows) {
    var selectedLocation = $('#av-location').val() || 'all';
    var selLocName = $('#av-location option:selected').text() || 'All Locations';
    $('#work-loc-summary-title').text('Work Location: ' + selLocName);
    $('#work-loc-summary-info').html('<i class="ti ti-info-circle me-1"></i>Click any badge to filter labours by home location below');

    var workingRows = tabRows.filter(function(r) {
        var checkinLoc = (tab === 'mn') ? (r.checkinlocation_mn || '') : (tab === 'dp' ? (r.checkinlocation_dp || '') : (r.checkinlocation_mn || r.checkinlocation_dp || ''));
        if (selectedLocation === 'all') {
            return checkinLoc !== '';
        } else {
            return checkinLoc === selectedLocation;
        }
    });

    var homeLocCounts = {};
    var totalCount = workingRows.length;

    workingRows.forEach(function(r) {
        var hLoc = r.labourlocationid || r.locationid || 'Unknown';
        homeLocCounts[hLoc] = (homeLocCounts[hLoc] || 0) + 1;
    });

    var badgesHtml = '';
    var allActive = (currentHomeLocFilter === 'all') ? 'active' : '';
    badgesHtml += '<button type="button" class="av-summary-btn ' + allActive + '" onclick="filterByHomeLoc(\'all\')">' +
        '<i class="ti ti-layout-grid"></i> All Home Locations <span class="badge-num">' + totalCount + '</span></button>';

    Object.keys(homeLocCounts).forEach(function(locId) {
        var locName = getLocationName(locId);
        var count = homeLocCounts[locId];
        var isSelected = (currentHomeLocFilter === locId);
        var activeClass = isSelected ? 'active' : '';
        
        badgesHtml += '<button type="button" class="av-summary-btn ' + activeClass + '" onclick="filterByHomeLoc(\'' + htmlEscape(locId) + '\')">' +
            '<i class="ti ti-map-pin"></i> ' + htmlEscape(locName) + ' <span class="badge-num">' + count + '</span></button>';
    });

    $('#work-location-badges-container').html(badgesHtml);
}

function filterByHomeLoc(locId) {
    currentHomeLocFilter = locId;
    renderTable(currentTab);
}

function renderSwapLocationSummaryReport(tab, swapRows) {
    var selLocName = (currentSwapScannerLocFilter === 'all') 
        ? 'All Locations' 
        : getLocationName(currentSwapScannerLocFilter);

    $('#swap-loc-summary-title').text('Scanner Location: ' + selLocName);

    var scannerLocCounts = {};
    var totalSwapCount = swapRows.length;

    swapRows.forEach(function(r) {
        var scanLoc = (tab === 'mn') ? (r.checkinlocation_mn || 'Unknown') 
                    : (tab === 'dp' ? (r.checkinlocation_dp || 'Unknown') 
                    : (r.checkinlocation_mn || r.checkinlocation_dp || 'Unknown'));
        if (scanLoc) {
            scannerLocCounts[scanLoc] = (scannerLocCounts[scanLoc] || 0) + 1;
        }
    });

    var badgesHtml = '';
    var allActive = (currentSwapScannerLocFilter === 'all') ? 'active' : '';
    badgesHtml += '<button type="button" class="av-summary-btn ' + allActive + '" onclick="filterBySwapScannerLoc(\'all\')">' +
        '<i class="ti ti-layout-grid"></i> All Scanner Locations <span class="badge-num">' + totalSwapCount + '</span></button>';

    Object.keys(scannerLocCounts).forEach(function(locId) {
        var locName = getLocationName(locId);
        var count = scannerLocCounts[locId];
        var isSelected = (currentSwapScannerLocFilter === locId);
        var activeClass = isSelected ? 'active' : '';
        
        badgesHtml += '<button type="button" class="av-summary-btn ' + activeClass + '" onclick="filterBySwapScannerLoc(\'' + htmlEscape(locId) + '\')">' +
            '<i class="ti ti-map-pin"></i> ' + htmlEscape(locName) + ' <span class="badge-num">' + count + '</span></button>';
    });

    $('#swap-location-badges-container').html(badgesHtml);
}

function filterBySwapScannerLoc(locId) {
    currentSwapScannerLocFilter = locId;
    renderTable(currentTab);
}

function resetStats() {
    currentHomeLocFilter = 'all';
    currentCheckinLocFilter = 'all';
    currentSwapScannerLocFilter = 'all';
    $('#stat-total-labour, #stat-work-labour, #stat-early, #stat-swap-labours').text('—');
    $('#stat-merged-total, #stat-merged-checkin, #stat-merged-checkout, #stat-merged-manual').text('—');
    $('#work-location-summary-wrap').hide();
    $('#swap-location-summary-wrap').hide();
}

function resetTable() {
    $('#av-tbody').html('<tr><td colspan="14"><div class="av-empty"><i class="ti ti-calendar-off"></i><p>Failed to load OT data. Please try again.</p></div></td></tr>');
    $('#av-record-count').text(0);
}

function filterTable() {
    renderTable(currentTab);
}

setInterval(function() {
    var today = new Date();
    var y = today.getFullYear(), m = String(today.getMonth()+1).padStart(2,'0'), d = String(today.getDate()).padStart(2,'0');
    if ($('#av-date').val() === y+'-'+m+'-'+d && allRows.length > 0) {
        loadAttendanceView();
    }
}, 60000);

function exportLabourExcel() {
    if (typeof XLSX === "undefined") {
        var script = document.createElement('script');
        script.src = "https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js";
        script.onload = function() {
            executeExcelExport();
        };
        document.head.appendChild(script);
    } else {
        executeExcelExport();
    }
}

function executeExcelExport() {
    const table = document.getElementById("av-table");
    if (!table) return;

    const locationName = $('#av-location option:selected').text() || 'All Locations';
    const qrLocationName = $('#av-polyhouse option:selected').text() || 'All QR Locations';
    const selectedDate = $('#av-date').val() || '';
    const titleText = `Labour OT Attendance Report - ${locationName} (${qrLocationName}) - ${selectedDate}`;

    const clone = table.cloneNode(true);

    const headerRow = clone.querySelector("thead tr");
    if (headerRow) {
        const ths = Array.from(headerRow.querySelectorAll("th"));
        const nameTh = ths.find(th => th.textContent.trim().toLowerCase() === "labour name");
        if (nameTh) {
            const idTh = document.createElement("th");
            idTh.textContent = "Labour ID";
            const newNameTh = document.createElement("th");
            newNameTh.textContent = "Labour Name";
            
            nameTh.replaceWith(idTh, newNameTh);
        }
    }

    clone.querySelectorAll("tbody tr").forEach(row => {
        const nameLink = row.querySelector(".av-labour-link");
        if (nameLink) {
            const nameDiv = nameLink.querySelector(".av-labour-name");
            const idDiv = nameLink.querySelector(".av-labour-id");
            
            const labourName = nameDiv ? nameDiv.textContent.trim() : "";
            const labourId = idDiv ? idDiv.textContent.trim() : "";
            
            const td = nameLink.closest("td");
            if (td) {
                const idTd = document.createElement("td");
                idTd.textContent = labourId;
                const nameTd = document.createElement("td");
                nameTd.textContent = labourName;
                
                td.replaceWith(idTd, nameTd);
            }
        }
    });

    clone.querySelectorAll("td[colspan]").forEach(td => {
        const currentColspan = parseInt(td.getAttribute("colspan") || 1);
        td.setAttribute("colspan", currentColspan + 1);
    });

    clone.querySelectorAll("td").forEach(td => {
        const timeBadge = td.querySelector(".av-time-badge");
        if (timeBadge) {
            td.textContent = timeBadge.textContent.replace('—', '').trim();
        }
        const pill = td.querySelector(".av-pill");
        if (pill) {
            td.textContent = pill.textContent.trim();
        }
        const wh = td.querySelector(".av-wh");
        if (wh) {
            td.textContent = wh.textContent.trim();
        }
    });

    const ws = XLSX.utils.aoa_to_sheet([[titleText], []]);
    XLSX.utils.sheet_add_dom(ws, clone, { origin: "A3" });

    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Labour OT Attendance");

    XLSX.writeFile(wb, `Labour_OT_Attendance_${selectedDate}.xlsx`);
}

/* ─── Add Missing OT Attendance Logic ────────────────────────────── */
var projectLabourCache = null;
var locationMap = {};

$(document).on('click', '#addMissingAttBtn', function() {
    $('#addMissingAttForm')[0].reset();
    $('#ma-labour-id').val('');
    $('#ma-search-results').addClass('d-none').empty();
    
    var currentDate = $('#av-date').val();
    $('#ma-date').val(currentDate || new Date().toISOString().split('T')[0]);
    
    var locOpts = '<option value="">Select Location</option>';
    if (Object.keys(locationMap).length > 0) {
        for (var locId in locationMap) {
            locOpts += '<option value="' + htmlEscape(locId) + '">' + htmlEscape(locationMap[locId]) + '</option>';
        }
    } else {
        $('#av-location option').each(function() {
            var val = $(this).val();
            var txt = $(this).text();
            if (val && val !== 'all') {
                locOpts += '<option value="' + htmlEscape(val) + '">' + htmlEscape(txt) + '</option>';
            }
        });
    }
    $('#ma-location').html(locOpts);
    
    var selectedLoc = $('#av-location').val();
    if (selectedLoc && selectedLoc !== 'all') {
        $('#ma-location').val(selectedLoc);
    }

    $('#addMissingAttModal').modal('show');
    
    if (!projectLabourCache) {
        $.post(routurl + "modal/hrm_laboursalarymodal.php?action=LoadLabourlist", {
            companyid    : companyid,
            subcompanyid : subcompanyid,
            projectid    : projectid,
            shortby      : "allactive"
        }, function(res) {
            try {
                var data = (typeof res === 'string') ? JSON.parse(res) : res;
                if (data && data.labours) {
                    projectLabourCache = data.labours;
                    if (data.locations) {
                        data.locations.forEach(function(loc) {
                            if (loc.LocationId && loc.LocationName) {
                                locationMap[loc.LocationId] = loc.LocationName;
                            }
                        });
                        // Update dropdown if opened
                        var updatedOpts = '<option value="">Select Location</option>';
                        for (var id in locationMap) {
                            updatedOpts += '<option value="' + htmlEscape(id) + '">' + htmlEscape(locationMap[id]) + '</option>';
                        }
                        $('#ma-location').html(updatedOpts);
                    }
                }
            } catch(e) { console.error('Error loading project active labours', e); }
        });
    }
});

$(document).on('input', '#ma-labour-search', function() {
    var val = $(this).val().toLowerCase().trim();
    var resultsDiv = $('#ma-search-results');
    resultsDiv.empty();
    
    if (!val) {
        resultsDiv.addClass('d-none');
        return;
    }
    
    if (!projectLabourCache || projectLabourCache.length === 0) {
        resultsDiv.removeClass('d-none').html('<div class="list-group-item text-muted">No labour data available.</div>');
        return;
    }
    
    var matches = projectLabourCache.filter(function(l) {
        var fullName = (l.FullName || l.FirstName + ' ' + l.LastName).toLowerCase();
        var empId = (l.EmployeeID || '').toLowerCase();
        return fullName.includes(val) || empId.includes(val);
    });
    
    if (matches.length === 0) {
        resultsDiv.removeClass('d-none').html('<div class="list-group-item text-muted">No matching labour found.</div>');
        return;
    }
    
    var html = '';
    matches.slice(0, 15).forEach(function(l) {
        var name = l.FullName || (l.FirstName + ' ' + (l.LastName || ''));
        var locName = locationMap[l.locationid] || 'No Location';
        html += '<button type="button" class="list-group-item list-group-item-action py-2 ma-item" data-id="' + l.EmployeeID + '" data-name="' + name + '" data-locationid="' + (l.locationid || '') + '" style="font-size: 13px; text-align: left; border: none; background: transparent; display: flex; justify-content: space-between; align-items: center; gap: 12px;">' +
                  '<span><strong>' + name + '</strong> (ID: ' + l.EmployeeID + ')</span>' +
                  '<span class="badge bg-light text-dark border" style="font-size: 11px; font-weight: 500;">' + locName + '</span>' +
                '</button>';
    });
    resultsDiv.removeClass('d-none').html(html);
});

$(document).on('click', '.ma-item', function(e) {
    e.preventDefault();
    var id = $(this).attr('data-id');
    var name = $(this).attr('data-name');
    var locId = $(this).attr('data-locationid');
    
    $('#ma-labour-search').val(name + ' (ID: ' + id + ')');
    $('#ma-labour-id').val(id);
    
    if (locId && $('#ma-location option[value="' + locId + '"]').length > 0) {
        $('#ma-location').val(locId);
    }
    
    $('#ma-search-results').addClass('d-none').empty();
});

$(document).on('keydown', '#ma-labour-search', function(e) {
    var resultsDiv = $('#ma-search-results');
    if (resultsDiv.hasClass('d-none')) {
        return;
    }
    
    var items = resultsDiv.find('.ma-item');
    if (items.length === 0) {
        return;
    }
    
    var activeItem = resultsDiv.find('.ma-item.bg-light');
    var activeIndex = items.index(activeItem);
    
    if (e.which === 40) {
        e.preventDefault();
        var nextIndex = activeIndex + 1;
        if (nextIndex >= items.length) {
            nextIndex = 0;
        }
        items.removeClass('bg-light');
        var newActive = items.eq(nextIndex).addClass('bg-light');
        scrollMaIntoView(resultsDiv, newActive);
    } else if (e.which === 38) {
        e.preventDefault();
        var prevIndex = activeIndex - 1;
        if (prevIndex < 0) {
            prevIndex = items.length - 1;
        }
        items.removeClass('bg-light');
        var newActive = items.eq(prevIndex).addClass('bg-light');
        scrollMaIntoView(resultsDiv, newActive);
    } else if (e.which === 13) {
        if (activeItem.length > 0) {
            e.preventDefault();
            activeItem.trigger('click');
        }
    } else if (e.which === 27) {
        e.preventDefault();
        resultsDiv.addClass('d-none').empty();
    }
});

$(document).on('mouseenter', '.ma-item', function() {
    $('.ma-item').removeClass('bg-light');
    $(this).addClass('bg-light');
});

function scrollMaIntoView(container, element) {
    if (container.length === 0 || element.length === 0) return;
    var containerTop = container.scrollTop();
    var containerHeight = container.height();
    var elemTop = element[0].offsetTop;
    var elemBottom = elemTop + element.outerHeight();
    
    if (elemTop < containerTop) {
        container.scrollTop(elemTop);
    } else if (elemBottom > containerTop + containerHeight) {
        container.scrollTop(elemBottom - containerHeight);
    }
}

$(document).on('click', function(e) {
    if (!$(e.target).closest('#ma-labour-search, #ma-search-results').length) {
        $('#ma-search-results').addClass('d-none');
    }
});

// Submit Manual OT Form
$(document).on('submit', '#addMissingAttForm', function(e) {
    e.preventDefault();
    var empId = $('#ma-labour-id').val();
    if (!empId) {
        Swal.fire({ icon: 'warning', title: 'Select Labour', text: 'Please search and select a labour from the dropdown list.' });
        return;
    }
    
    var $btn = $('#ma-submit-btn');
    var origHtml = $btn.html();
    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');
    
    var locName = $('#ma-location option:selected').text() || 'Manual';
    var locId = $('#ma-location').val() || '';
    var purposeofot = $('#ma-purposeofot').val() || 'General Overtime';
    
    $.post("modal/hrm_laboursalarymodal.php?action=MarkManualOTAttendance", {
        employeeID    : empId,
        projectid     : projectid,
        companyid     : companyid,
        subcompanyid  : subcompanyid,
        attendancedate: $('#ma-date').val(),
        checkin       : $('#ma-in-time').val(),
        checkout      : $('#ma-out-time').val(),
        locationName  : locName,
        locationId    : locId,
        purposeofot   : purposeofot,
        loginname     : loginname
    }, function(res) {
        $btn.prop('disabled', false).html(origHtml);
        try {
            var data = (typeof res === 'string') ? JSON.parse(res) : res;
            if (data.status === 'Success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Saved!',
                    text: 'OT Attendance has been recorded successfully.',
                    timer: 2000,
                    showConfirmButton: false
                });
                $('#addMissingAttModal').modal('hide');
                loadAttendanceView();
            } else {
                Swal.fire('Error', data.message || 'Could not record OT attendance.', 'error');
            }
        } catch(e) {
            Swal.fire('Error', 'Unexpected server response.', 'error');
        }
    }).fail(function() {
        $btn.prop('disabled', false).html(origHtml);
        Swal.fire('Error', 'Server connection failed.', 'error');
    });
});

var selectedScanners = new Set();

function populateScanByDropdown(data) {
    var scanbySet = new Set();
    data.forEach(function(r) {
        if (r.checkinby_mn && r.checkinby_mn.trim() !== '') scanbySet.add(r.checkinby_mn.trim());
        if (r.checkoutby_mn && r.checkoutby_mn.trim() !== '') scanbySet.add(r.checkoutby_mn.trim());
        if (r.checkinby_dp && r.checkinby_dp.trim() !== '') scanbySet.add(r.checkinby_dp.trim());
        if (r.checkoutby_dp && r.checkoutby_dp.trim() !== '') scanbySet.add(r.checkoutby_dp.trim());
    });
    
    var sortedNames = Array.from(scanbySet).sort();
    var allCheckedDefault = selectedScanners.size === 0;
    
    var optsHtml = '';
    sortedNames.forEach(function(name) {
        var isChecked = allCheckedDefault || selectedScanners.has(name);
        optsHtml += `
        <div class="av-multiselect-option">
            <label class="checkbox-container">
                <input type="checkbox" class="scanby-checkbox" value="${htmlEscape(name)}" ${isChecked ? 'checked' : ''}>
                <span class="checkmark"></span>
                <span class="option-text">${htmlEscape(name)}</span>
            </label>
        </div>`;
    });
    
    $('#scanby-options-container').html(optsHtml);
    updateScanbyMultiselectState();
}

function updateScanbyMultiselectState() {
    var total = $('.scanby-checkbox').length;
    var checked = $('.scanby-checkbox:checked').length;
    
    selectedScanners.clear();
    $('.scanby-checkbox:checked').each(function() {
        selectedScanners.add($(this).val());
    });
    
    if (checked === 0) {
        $('#scanby-select-all').prop('checked', false);
        $('.av-multiselect-text').text('No Scanners Selected');
    } else if (checked === total) {
        $('#scanby-select-all').prop('checked', true);
        $('.av-multiselect-text').text('All Scanners');
    } else {
        $('#scanby-select-all').prop('checked', false);
        if (checked === 1) {
            $('.av-multiselect-text').text($('.scanby-checkbox:checked').val());
        } else {
            $('.av-multiselect-text').text(checked + ' Scanners Selected');
        }
    }
}

$(document).on('click', '#scanby-trigger', function(e) {
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

$(document).on('change', '#scanby-select-all', function() {
    var isChecked = $(this).is(':checked');
    $('.scanby-checkbox').prop('checked', isChecked);
    updateScanbyMultiselectState();
    renderTable(currentTab);
});

$(document).on('change', '.scanby-checkbox', function() {
    updateScanbyMultiselectState();
    renderTable(currentTab);
});

function removeAttendance(attendanceId, labourName) {
    if (!attendanceId) return;

    Swal.fire({
        title: 'Remove OT Attendance?',
        html: 'Are you sure you want to remove OT attendance for <strong>' + htmlEscape(labourName || 'this labour') + '</strong>?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, Remove It!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Removing...',
                text: 'Please wait while we remove the OT attendance record.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.post(routurl + 'modal/hrm_laboursalarymodal.php?action=RemoveOTAttendance', {
                attendanceid: attendanceId,
                loginname: loginname,
                localdatetime: getLocalDateTimeString()
            }, function(res) {
                Swal.close();
                try {
                    var data = (typeof res === 'string') ? JSON.parse(res) : res;
                    if (data.status === 'Success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Removed!',
                            text: 'OT Attendance record has been successfully removed.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        loadAttendanceView();
                    } else {
                        Swal.fire('Error', data.message || 'Could not remove OT attendance.', 'error');
                    }
                } catch(e) {
                    Swal.fire('Error', 'Unexpected server response.', 'error');
                }
            }).fail(function() {
                Swal.close();
                Swal.fire('Error', 'Server connection failed.', 'error');
            });
        }
    });
}

function openCheckoutModal(attendanceId, labourName, labourId, attDate, checkinTime, checkoutTime, remark) {
    if (!attendanceId) return;

    $('#co-attendance-id').val(attendanceId);
    $('#co-labour-name').text(labourName || '—');
    $('#co-labour-id-badge').text(labourId ? 'ID: ' + labourId : '');
    $('#co-att-date').text(attDate || '');
    $('#co-checkin-display').text(formatDT(checkinTime) || 'N/A');

    var outTimeVal = '';
    if (checkoutTime && checkoutTime !== '0000-00-00 00:00:00') {
        var formatted = formatDT(checkoutTime);
        if (formatted) outTimeVal = formatted;
    }
    if (!outTimeVal) {
        var now = new Date();
        var hh = String(now.getHours()).padStart(2, '0');
        var mm = String(now.getMinutes()).padStart(2, '0');
        outTimeVal = hh + ':' + mm;
    }
    $('#co-out-time').val(outTimeVal);
    $('#co-remark').val(remark || '');

    $('#checkoutModal').modal('show');
}

$(document).on('submit', '#checkoutForm', function(e) {
    e.preventDefault();
    var attendanceId = $('#co-attendance-id').val();
    var outTime = $('#co-out-time').val();
    var remark = $('#co-remark').val().trim();

    if (!attendanceId || !outTime || !remark) {
        Swal.fire({ icon: 'warning', title: 'Missing Details', text: 'Please fill all required fields.' });
        return;
    }

    var $btn = $('#co-submit-btn');
    var origHtml = $btn.html();
    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Updating...');

    $.post(routurl + 'modal/hrm_laboursalarymodal.php?action=UpdateOTLabourCheckout', {
        attendanceid: attendanceId,
        checkouttime: outTime,
        remark: remark,
        loginname: loginname
    }, function(res) {
        $btn.prop('disabled', false).html(origHtml);
        try {
            var data = (typeof res === 'string') ? JSON.parse(res) : res;
            if (data.status === 'Success' || data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Updated!',
                    text: data.message || 'OT Check-out updated successfully.',
                    timer: 2000,
                    showConfirmButton: false
                });
                $('#checkoutModal').modal('hide');
                loadAttendanceView();
            } else {
                Swal.fire('Error', data.message || 'Could not update OT check-out.', 'error');
            }
        } catch(err) {
            Swal.fire('Error', 'Unexpected response from server.', 'error');
        }
    }).fail(function() {
        $btn.prop('disabled', false).html(origHtml);
        Swal.fire('Error', 'Server connection failed.', 'error');
    });
});

function formatDT(str) {
    if (!str || str === '0000-00-00 00:00:00') return null;
    var parts = str.split(' ');
    if (parts.length < 2) return str;
    var t = parts[1].split(':');
    return t[0] + ':' + t[1];
}

function htmlEscape(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function getLocalDateTimeString() {
    var now = new Date();
    var y = now.getFullYear();
    var m = String(now.getMonth() + 1).padStart(2, '0');
    var d = String(now.getDate()).padStart(2, '0');
    var hh = String(now.getHours()).padStart(2, '0');
    var mm = String(now.getMinutes()).padStart(2, '0');
    var ss = String(now.getSeconds()).padStart(2, '0');
    return y + '-' + m + '-' + d + ' ' + hh + ':' + mm + ':' + ss;
}
</script>
