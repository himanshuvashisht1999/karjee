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
    background: linear-gradient(135deg, #111827 0%, #1f2937 50%, #374151 100%);
    color: #fff;
    padding: 20px 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    box-shadow: 0 4px 20px rgba(17, 24, 39, 0.25);
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
    background: #4ade80;
    box-shadow: 0 0 0 2px rgba(74,222,128,.3);
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
.av-select-wrap select:focus { outline: none; border-color: var(--att-green); background: #fff; }
.av-filter-date {
    padding: 8px 10px 8px 32px;
    border: 1.5px solid var(--att-border); border-radius: 8px;
    font-size: 13px; background: #f9fafb;
    cursor: pointer;
    transition: border-color .2s;
    width: 100%;
}
.av-filter-date:focus { outline: none; border-color: var(--att-green); background: #fff; }
.av-btn-load {
    padding: 8px 22px;
    background: linear-gradient(135deg, #1b7a48, #22c55e);
    color: #fff; border: none; border-radius: 8px;
    font-size: 13px; font-weight: 600;
    cursor: pointer;
    display: flex; align-items: center; gap: 6px;
    transition: transform .15s, box-shadow .15s;
    box-shadow: 0 3px 10px rgba(27,122,72,.3);
    white-space: nowrap;
}
.av-btn-load:hover { transform: translateY(-1px); box-shadow: 0 5px 15px rgba(27,122,72,.4); }
.av-btn-refresh {
    padding: 8px 14px;
    background: #f3f4f6; color: #374151;
    border: 1.5px solid var(--att-border); border-radius: 8px;
    font-size: 13px; cursor: pointer;
    display: flex; align-items: center; gap: 5px;
    transition: background .15s;
}
.av-btn-refresh:hover { background: #e5e7eb; }

/* Custom Multiselect styles */
.av-multiselect-wrap {
    position: relative;
    user-select: none;
    width: 100%;
}
.av-multiselect-trigger {
    width: 100%;
    padding: 8px 10px 8px 32px;
    border: 1.5px solid var(--att-border);
    border-radius: 8px;
    font-size: 13px;
    background: #f9fafb;
    cursor: pointer;
    transition: border-color .2s, background .2s;
    display: flex;
    align-items: center;
    min-height: 38px;
    position: relative;
}
.av-multiselect-trigger:hover {
    border-color: var(--att-green);
}
.av-multiselect-wrap.open .av-multiselect-trigger {
    border-color: var(--att-green);
    background: #fff;
}
.av-multiselect-text {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    padding-right: 12px;
    font-weight: 500;
}
.av-multiselect-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    width: 100%;
    background: #fff;
    border: 1.5px solid var(--att-border);
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    margin-top: 4px;
    z-index: 1000;
    max-height: 250px;
    overflow-y: auto;
    border-top: 1.5px solid var(--att-border);
}
.av-multiselect-option {
    padding: 8px 12px;
    cursor: pointer;
    transition: background .15s;
}
.av-multiselect-option:hover {
    background: #f3f4f6;
}
.av-multiselect-options-list {
    max-height: 200px;
    overflow-y: auto;
    border-top: 1px solid var(--att-border);
}

/* Custom Checkbox Container */
.checkbox-container {
    display: flex;
    align-items: center;
    position: relative;
    padding-left: 26px;
    cursor: pointer;
    font-size: 13px;
    color: var(--att-dark);
    margin-bottom: 0;
    width: 100%;
    user-select: none;
}
.checkbox-container input {
    position: absolute;
    opacity: 0;
    cursor: pointer;
    height: 0;
    width: 0;
}
.checkmark {
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    height: 16px;
    width: 16px;
    background-color: #f3f4f6;
    border: 1.5px solid #d1d5db;
    border-radius: 4px;
    transition: background-color .15s, border-color .15s;
}
.checkbox-container:hover input ~ .checkmark {
    background-color: #e5e7eb;
    border-color: #9ca3af;
}
.checkbox-container input:checked ~ .checkmark {
    background-color: var(--att-green);
    border-color: var(--att-green);
}
.checkmark:after {
    content: "";
    position: absolute;
    display: none;
}
.checkbox-container input:checked ~ .checkmark:after {
    display: block;
}
.checkbox-container .checkmark:after {
    left: 4px;
    top: 1px;
    width: 5px;
    height: 9px;
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}
.option-text {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: inline-block;
    width: 100%;
}

/* ── Stats Row ──────────────────────────────────────────────────── */
.av-stats-row {
    display: flex; flex-wrap: wrap; gap: 12px;
    padding: 16px 28px;
}
.av-stat-card {
    flex: 1; min-width: 150px;
    background: #fff;
    border-radius: 12px;
    border: 1px solid var(--att-border);
    padding: 14px 18px;
    display: flex; align-items: center; gap: 12px;
    transition: all .2s ease;
    box-shadow: 0 1px 4px rgba(0,0,0,.06);
    cursor: pointer;
    user-select: none;
}
.av-stat-card:hover { 
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0,0,0,.1); 
    border-color: var(--att-green);
}
.av-stat-card.active {
    border: 2px solid var(--att-green);
    background: var(--att-green-lt);
    box-shadow: 0 4px 12px rgba(27,122,72,0.15);
}
.av-stat-icon {
    width: 40px; height: 40px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; flex-shrink: 0;
}
.av-stat-icon.green  { background: var(--att-green-lt);  color: var(--att-green); }
.av-stat-icon.blue   { background: var(--att-blue-lt);   color: var(--att-blue); }
.av-stat-icon.amber  { background: var(--att-amber-lt);  color: var(--att-amber); }
.av-stat-icon.red    { background: var(--att-red-lt);    color: var(--att-red); }
.av-stat-icon.gray   { background: #f3f4f6;              color: var(--att-gray); }
.av-stat-card.active-blue {
    border: 2px solid var(--att-blue, #1d4ed8);
    background: var(--att-blue-lt, #eff6ff);
    box-shadow: 0 4px 12px rgba(29,78,216,0.15);
}
.work-loc-summary-wrap {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 12px 18px;
    margin-bottom: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.av-summary-btn {
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    padding: 4px 14px;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    color: #475569;
}
.av-summary-btn:hover {
    border-color: #3b82f6;
    color: #1d4ed8;
    background: #eff6ff;
}
.av-summary-btn.active {
    background: #2563eb;
    color: #ffffff;
    border-color: #2563eb;
    box-shadow: 0 2px 6px rgba(37,99,235,0.25);
}
.av-summary-btn .badge-num {
    background: rgba(0,0,0,0.08);
    padding: 2px 7px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 700;
}
.av-summary-btn.active .badge-num {
    background: rgba(255,255,255,0.25);
    color: #ffffff;
}
.av-stat-num { font-size: 22px; font-weight: 700; color: #111827; line-height: 1; }
.av-stat-lbl { font-size: 11px; color: var(--att-gray); font-weight: 500; margin-top: 2px; }

/* ── Merged Card Grid ────────────────────────────────────────────── */
.av-stat-card-merged {
    flex: 2.2 !important;
    min-width: 290px !important;
    padding: 10px 14px !important;
}
.av-merged-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 2px 8px;
    margin-top: 3px;
}
.av-merged-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 10.5px;
    line-height: 1.3;
    overflow: hidden;
}
.av-merged-lbl {
    color: var(--att-gray);
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.av-merged-val {
    font-weight: 700;
    font-size: 11.5px;
    color: #111827;
    margin-left: 2px;
    white-space: nowrap;
}

/* ── Main Content ───────────────────────────────────────────────── */
.av-content { padding: 0 28px 28px; }

/* ── Section Header ─────────────────────────────────────────────── */
.av-section-head {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 12px; flex-wrap: wrap; gap: 8px;
}
.av-section-title {
    font-size: 14px; font-weight: 700; color: #111827;
    display: flex; align-items: center; gap: 7px;
}
.av-section-title span.badge-count {
    background: var(--att-green); color: #fff;
    border-radius: 20px; font-size: 11px;
    padding: 1px 8px; font-weight: 600;
}
.av-search-box {
    position: relative; display: flex;
}
.av-search-box i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--att-gray); font-size: 13px; }
.av-search-box input {
    padding: 6px 10px 6px 30px;
    border: 1.5px solid var(--att-border); border-radius: 8px;
    font-size: 12px; width: 200px; background: #f9fafb;
    transition: border-color .2s;
}
.av-search-box input:focus { outline: none; border-color: var(--att-green); background: #fff; }

/* ── Table Card ─────────────────────────────────────────────────── */
.av-table-card {
    background: var(--att-card-bg);
    border-radius: 14px;
    border: 1px solid var(--att-border);
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,.06);
    margin-bottom: 24px;
}
.av-table-wrap { overflow-x: auto; }
.av-table {
    width: 100%; border-collapse: collapse;
    font-size: 12.5px;
}
.av-table thead th {
    background: #f9fafb;
    padding: 10px 12px;
    text-align: left;
    font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .4px;
    color: var(--att-gray);
    border-bottom: 1.5px solid var(--att-border);
    white-space: nowrap;
}
.av-table tbody tr {
    border-bottom: 1px solid #f3f4f6;
    transition: background .1s;
}
.av-table tbody tr:hover { background: #f9fafb; }
.av-table tbody tr.av-swap-row td {
    background-color: #fff9db !important;
    border-top: 1.5px solid #d97706 !important;
    border-bottom: 1.5px solid #d97706 !important;
    box-shadow: 0 4px 6px -1px rgba(217, 119, 6, 0.15) !important;
}
.av-table tbody tr.av-swap-row td:first-child {
    border-left: 1.5px solid #d97706 !important;
}
.av-table tbody tr.av-swap-row td:last-child {
    border-right: 1.5px solid #d97706 !important;
}
.av-table tbody tr.av-swap-row:hover td {
    background-color: #fff3c4 !important;
}
.av-table tbody td {
    padding: 9px 12px; vertical-align: middle;
}

/* ── Labour Name Cell ───────────────────────────────────────────── */
.av-labour-link {
    text-decoration: none;
    color: inherit;
    display: block;
    cursor: pointer;
}
.av-labour-link:hover .av-labour-name {
    color: var(--att-green);
    text-decoration: underline;
}
.av-labour-name {
    font-weight: 600; color: #111827; font-size: 13px;
}
.av-labour-id {
    font-size: 10px; color: var(--att-gray); margin-top: 1px;
}

/* ── Time Badge ─────────────────────────────────────────────────── */
.av-time-badge {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 11.5px; font-weight: 600;
    padding: 3px 8px; border-radius: 6px;
}
.av-time-badge.checkin  { background: var(--att-green-lt);  color: var(--att-green); }
.av-time-badge.checkout { background: var(--att-blue-lt);   color: var(--att-blue); }
.av-time-badge.missing  { background: #f3f4f6; color: var(--att-gray); font-weight: 400; font-style: italic; }

/* ── Status Pill ────────────────────────────────────────────────── */
.av-pill {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 10.5px; font-weight: 700;
    padding: 2px 9px; border-radius: 20px;
    text-transform: uppercase; letter-spacing: .3px;
}
.av-pill.present   { background: var(--att-green-lt);  color: var(--att-green);  border: 1px solid #a7d9bb; }
.av-pill.checked-in{ background: #e0f2fe;               color: #0369a1;           border: 1px solid #bae6fd; }
.av-pill.absent    { background: var(--att-red-lt);     color: var(--att-red);    border: 1px solid #f9b8b8; }
.av-pill.norecord  { background: #f3f4f6;               color: var(--att-gray);   border: 1px solid var(--att-border); }

/* ── Working Hours ──────────────────────────────────────────────── */
.av-wh {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 11px; font-weight: 700; color: var(--att-blue);
    background: var(--att-blue-lt); border-radius: 20px;
    padding: 2px 9px;
}
.av-wh.na { color: var(--att-gray); background: #f3f4f6; font-weight: 400; }

/* ── Scanner Cell ───────────────────────────────────────────────── */
.av-scanner {
    font-size: 11px; color: var(--att-gray);
    max-width: 140px; overflow: hidden;
    text-overflow: ellipsis; white-space: nowrap;
}

/* ── Empty State ────────────────────────────────────────────────── */
.av-empty {
    text-align: center; padding: 48px 20px; color: var(--att-gray);
}
.av-empty i { font-size: 42px; opacity: .25; display: block; margin-bottom: 10px; }
.av-empty p { font-size: 13px; margin: 0; }

/* ── Loading Spinner ────────────────────────────────────────────── */
.av-loading {
    text-align: center; padding: 40px 20px; color: var(--att-green);
}
.av-spinner {
    width: 36px; height: 36px;
    border: 3px solid var(--att-green-lt);
    border-top-color: var(--att-green);
    border-radius: 50%;
    animation: spin .7s linear infinite;
    margin: 0 auto 12px;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Photo Thumb ────────────────────────────────────────────────── */
.av-photo {
    width: 34px; height: 34px; border-radius: 8px;
    object-fit: cover; border: 2px solid var(--att-border);
    cursor: pointer; transition: transform .15s;
}
.av-photo:hover { transform: scale(1.1); }
.av-no-photo {
    width: 34px; height: 34px; border-radius: 8px;
    background: #f3f4f6; display: inline-flex;
    align-items: center; justify-content: center;
    color: var(--att-gray); font-size: 16px;
    border: 2px solid var(--att-border);
}

/* ── Tab Row ─────────────────────────────────────────────────────── */
.av-tab-row {
    display: flex; gap: 4px;
    padding: 12px 28px 0;
    border-bottom: 2px solid var(--att-border);
    background: #fff;
}
.av-tab {
    padding: 8px 18px; font-size: 12.5px; font-weight: 600;
    border: none; background: none; cursor: pointer;
    color: var(--att-gray); border-radius: 8px 8px 0 0;
    position: relative; bottom: -2px;
    border-bottom: 2px solid transparent;
    transition: all .15s;
}
.av-tab:hover { color: var(--att-green); background: var(--att-green-lt); }
.av-tab.active { color: var(--att-green); border-bottom-color: var(--att-green); background: none; }

/* ── Responsive ─────────────────────────────────────────────────── */
@media (max-width: 640px) {
    .av-filters { padding: 12px 16px; }
    .av-stats-row { padding: 12px 16px; }
    .av-content { padding: 0 16px 20px; }
    .av-header { padding: 14px 16px; }
    .av-tab-row { padding: 10px 16px 0; overflow-x: auto; }
    .av-filter-group { min-width: 140px; }
}

/* Light Gender Badges with Black Text */
.badge-gender-m {
    background-color: #e0f2fe !important;
    color: #111827 !important;
    border: 1px solid #bae6fd !important;
    font-size: 8.5px;
    padding: 1px 5px;
    font-weight: 600;
}
.badge-gender-w {
    background-color: #fce7f3 !important;
    color: #111827 !important;
    border: 1px solid #fbcfe8 !important;
    font-size: 8.5px;
    padding: 1px 5px;
    font-weight: 600;
}
</style>

<!-- Page Wrapper -->
<div class="av-page">

    <!-- ── Header ─────────────────────────────────────────────────── -->
    <div class="av-header">
        <div class="av-header-left">
            <div class="av-header-icon"><i class="ti ti-calendar-check"></i></div>
            <div>
                <h2>Labour Attendance View</h2>
                <p><i class="ti ti-map-pin me-1"></i> Daily attendance by location &amp; QR scanner</p>
            </div>
        </div>
        <div class="av-live-badge">
            <span class="av-live-dot"></span>
            <span id="av-live-clock">—</span>
        </div>
    </div>

    <!-- ── Filter Bar ──────────────────────────────────────────────── -->
    <div class="av-filters">

        <!-- Location -->
        <div class="av-filter-group">
            <label class="av-filter-label"><i class="ti ti-map-pin me-1"></i> Location</label>
            <div class="av-select-wrap">
                <i class="av-icon ti ti-building-estate"></i>
                <select id="av-location">
                    <option value="">Loading locations…</option>
                </select>
            </div>
        </div>

        <!-- Attendance QR Location (polyhouse) -->
        <div class="av-filter-group">
            <label class="av-filter-label"><i class="ti ti-tree me-1"></i> Attendance QR Location</label>
            <div class="av-select-wrap" id="av-polyhouse-wrap">
                <i class="av-icon ti ti-map-2"></i>
                <select id="av-polyhouse">
                    <option value="all">All QR Locations</option>
                    <option value="main-gate">Main Gate</option>
                </select>
            </div>
        </div>

        <!-- Date -->
        <div class="av-filter-group">
            <label class="av-filter-label"><i class="ti ti-calendar me-1"></i> Date</label>
            <div class="av-select-wrap">
                <i class="av-icon ti ti-calendar"></i>
                <input type="date" id="av-date" class="av-filter-date" />
            </div>
        </div>

        <!-- Scan By -->
        <div class="av-filter-group">
            <label class="av-filter-label"><i class="ti ti-user me-1"></i> Scan By</label>
            <div class="av-select-wrap av-multiselect-wrap" id="scanby-multiselect">
                <div class="av-multiselect-trigger" id="scanby-trigger">
                    <i class="av-icon ti ti-user"></i>
                    <span class="av-multiselect-text">All Scanners</span>
                    <i class="ti ti-chevron-down ms-auto dropdown-chevron" style="font-size:12px; color:var(--att-gray);"></i>
                </div>
                <div class="av-multiselect-dropdown" id="scanby-dropdown" style="display:none;">
                    <div class="av-multiselect-option select-all-option">
                        <label class="checkbox-container">
                            <input type="checkbox" id="scanby-select-all" checked>
                            <span class="checkmark"></span>
                            <span class="option-text" style="font-weight:600;">All Scanners</span>
                        </label>
                    </div>
                    <div class="av-multiselect-options-list" id="scanby-options-container">
                        <!-- Dynamic checkboxes -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Load Button -->
        <div class="av-filter-group" style="justify-content:flex-end;">
            <label class="av-filter-label">&nbsp;</label>
            <div style="display:flex;gap:8px;">
                <button class="av-btn-load" id="av-btn-load" onclick="loadAttendanceView()">
                    <i class="ti ti-search"></i> Load Attendance
                </button>
                <button class="av-btn-refresh" id="av-btn-refresh" onclick="loadAttendanceView()" title="Refresh">
                    <i class="ti ti-refresh"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- ── Stats Row ───────────────────────────────────────────────── -->
    <div class="av-stats-row" id="av-stats-row">
        <div class="av-stat-card active" id="card-total" onclick="setStatFilter('total', this)">
            <div class="av-stat-icon green"><i class="ti ti-users"></i></div>
            <div>
                <div class="av-stat-num" id="stat-total-labour">—</div>
                <div class="av-stat-lbl" id="stat-lbl-total">Total Check-In Labour</div>
                <div class="mt-1 d-flex gap-1 flex-wrap">
                    <span class="badge badge-gender-m">M: <span id="stat-total-man">0</span></span>
                    <span class="badge badge-gender-w">W: <span id="stat-total-woman">0</span></span>
                </div>
            </div>
        </div>
        <div class="av-stat-card" id="card-work" onclick="setStatFilter('work_location', this)">
            <div class="av-stat-icon blue"><i class="ti ti-building-community"></i></div>
            <div>
                <div class="av-stat-num" id="stat-work-labour">—</div>
                <div class="av-stat-lbl" id="stat-lbl-work">Total Work Labour</div>
                <div class="mt-1 d-flex gap-1 flex-wrap">
                    <span class="badge badge-gender-m">M: <span id="stat-work-man">0</span></span>
                    <span class="badge badge-gender-w">W: <span id="stat-work-woman">0</span></span>
                </div>
            </div>
        </div>
        <div class="av-stat-card av-stat-card-merged" id="card-merged" onclick="setStatFilter('merged', this)">
            <div class="av-stat-icon green"><i class="ti ti-clipboard-check"></i></div>
            <div style="flex: 1; min-width: 0;">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="av-stat-lbl" style="font-weight: 700; color: #374151; margin: 0;" id="stat-lbl-merged">Attendance Breakdown</span>
                </div>
                <div class="av-merged-grid">
                    <div class="av-merged-item">
                        <span class="av-merged-lbl" title="Check-In + Manual">Total Labour :</span>
                        <span class="av-merged-val" id="stat-merged-total">—</span>
                    </div>
                    <div class="av-merged-item">
                        <span class="av-merged-lbl">Check-In :</span>
                        <span class="av-merged-val text-success" id="stat-merged-checkin">—</span>
                    </div>
                    <div class="av-merged-item">
                        <span class="av-merged-lbl">Check-Out :</span>
                        <span class="av-merged-val text-primary" id="stat-merged-checkout">—</span>
                    </div>
                    <div class="av-merged-item">
                        <span class="av-merged-lbl">Manual :</span>
                        <span class="av-merged-val" style="color:#7c3aed;" id="stat-merged-manual">—</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="av-stat-card" id="card-early" onclick="setStatFilter('early', this)">
            <div class="av-stat-icon red"><i class="ti ti-clock-off"></i></div>
            <div>
                <div class="av-stat-num" id="stat-early">—</div>
                <div class="av-stat-lbl">Urly Check-Out</div>
                <div class="mt-1 d-flex gap-1 flex-wrap">
                    <span class="badge badge-gender-m">M: <span id="stat-early-man">0</span></span>
                    <span class="badge badge-gender-w">W: <span id="stat-early-woman">0</span></span>
                </div>
            </div>
        </div>
        <div class="av-stat-card" id="card-swap" onclick="setStatFilter('swap', this)">
            <div class="av-stat-icon amber"><i class="ti ti-arrows-left-right"></i></div>
            <div>
                <div class="av-stat-num" id="stat-swap-labours">—</div>
                <div class="av-stat-lbl">Swap Labours</div>
                <div class="mt-1 d-flex gap-1 flex-wrap">
                    <span class="badge badge-gender-m">M: <span id="stat-swap-man">0</span></span>
                    <span class="badge badge-gender-w">W: <span id="stat-swap-woman">0</span></span>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Tabs ────────────────────────────────────────────────────── -->
    <div class="av-tab-row">
        <button class="av-tab active" onclick="switchTab('mn', this)">
            <i class="ti ti-gate me-1"></i> Main Gate <span class="badge-count" id="tab-mn-count" style="background:#1b7a48;">0</span>
        </button>
        <button class="av-tab" onclick="switchTab('dp', this)">
            <i class="ti ti-tree me-1"></i> QR Location <span class="badge-count" id="tab-dp-count" style="background:#1d4ed8;">0</span>
        </button>
        <button class="av-tab" onclick="switchTab('combined', this)">
            <i class="ti ti-layout-list me-1"></i> Combined View
        </button>
    </div>

    <!-- ── Content ─────────────────────────────────────────────────── -->
    <div class="av-content" style="padding-top:20px;">

        <!-- Work Location Summary Report Bar (Shown ONLY for Total Work Labour section) -->
        <div id="work-location-summary-wrap" style="display:none;" class="work-loc-summary-wrap">
            <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 font-12 fw-bold">
                        <i class="ti ti-report-analytics me-1"></i> Labour Location Wise Summary Report
                    </span>
                    <span class="text-muted font-12 fw-semibold" id="work-loc-summary-title">Work Location: All</span>
                </div>
                <span class="text-muted font-11" id="work-loc-summary-info"><i class="ti ti-info-circle me-1"></i>Click any badge to filter labours by home location below</span>
            </div>
            <div id="work-location-badges-container" class="d-flex flex-wrap gap-2 align-items-center pt-1">
                <!-- Dynamic location summary badges injected by JS -->
            </div>
        </div>

        <!-- Swap Labour Scanner Location Wise Summary Report Bar (Shown ONLY for Swap Labours section) -->
        <div id="swap-location-summary-wrap" style="display:none;" class="work-loc-summary-wrap" style="border-color: #fcd34d;">
            <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge rounded-pill px-3 py-1 font-12 fw-bold" style="background-color: #fef3c7; color: #b45309; border: 1px solid #fcd34d;">
                        <i class="ti ti-arrows-left-right me-1"></i> Swap Labour Scanner Location Wise Summary Report
                    </span>
                    <span class="text-muted font-12 fw-semibold" id="swap-loc-summary-title">Scanner Location: All Locations</span>
                </div>
                <span class="text-muted font-11"><i class="ti ti-info-circle me-1"></i>Click any badge to filter labours by scanner location below</span>
            </div>
            <div id="swap-location-badges-container" class="d-flex flex-wrap gap-2 align-items-center pt-1">
                <!-- Dynamic scanner location summary badges injected by JS -->
            </div>
        </div>

        <!-- Search & section head -->
        <div class="av-section-head">
            <div class="av-section-title d-flex align-items-center gap-2">
                <i class="ti ti-list" style="color:var(--att-green);"></i>
                <span>Attendance Records</span>
                <span class="badge-count" id="av-record-count">0</span>
                <span class="badge badge-gender-m" style="font-size: 10px; padding: 2px 6px;">M: <span id="av-record-man">0</span></span>
                <span class="badge badge-gender-w" style="font-size: 10px; padding: 2px 6px;">W: <span id="av-record-woman">0</span></span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="av-search-box">
                    <i class="ti ti-search"></i>
                    <input type="text" id="av-search" placeholder="Search labour name or ID…" oninput="filterTable()">
                </div>
                <button class="btn btn-primary d-inline-flex align-items-center gap-1" id="addMissingAttBtn" style="height:38px; font-weight:600; border-radius:8px; background-color:#4f46e5; border-color:#4f46e5; font-size:13px; padding: 0 16px;">
                    <i class="ti ti-plus fs-16"></i> Add Missing Attendance
                </button>
                <button class="av-btn-refresh" onclick="exportLabourExcel()" title="Export to Excel" style="height: 38px; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; font-weight: 600; color: #1b7a48; border-color: #a7d9bb; background: #e6f4ed;">
                    <i class="ti ti-file-export fs-16"></i> Export Excel
                </button>
            </div>
        </div>

        <!-- Table Card -->
        <div class="av-table-card">
            <div class="av-table-wrap">
                <table class="av-table" id="av-table">
                    <thead>
                        <tr id="av-thead-row">
                            <!-- dynamic headers injected by JS -->
                        </tr>
                    </thead>
                    <tbody id="av-tbody">
                        <tr>
                            <td colspan="12">
                                <div class="av-empty">
                                    <i class="ti ti-calendar-search"></i>
                                    <p>Select location &amp; date, then click <strong>Load Attendance</strong></p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div><!-- /av-content -->

</div><!-- /av-page -->

<!-- Add Missing Attendance Modal -->
<div class="modal fade" id="addMissingAttModal" tabindex="-1" aria-labelledby="addMissingAttModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.15);">
      <div class="modal-header" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; border-top-left-radius: 12px; border-top-right-radius: 12px;">
        <h5 class="modal-title" id="addMissingAttModalLabel" style="font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 8px;">
          <i class="ti ti-calendar-plus" style="color: #4f46e5; font-size: 20px;"></i> Add Missing Attendance
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="addMissingAttForm">
        <div class="modal-body" style="padding: 20px;">
          
          <!-- Search Labour -->
          <div class="mb-3 position-relative">
            <label class="form-label" style="font-weight: 600; color: #475569; font-size: 13px;">Select Labour <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text" style="background: #f8fafc; border-right: none; color: #94a3b8;"><i class="ti ti-user"></i></span>
              <input type="text" id="ma-labour-search" class="form-control" placeholder="Type name or ID to search..." style="border-left: none; border-radius: 0 8px 8px 0; font-size: 14px;" autocomplete="off" required>
            </div>
            <input type="hidden" id="ma-labour-id">
            
            <!-- Search Results Dropdown -->
            <div id="ma-search-results" class="list-group shadow-sm position-absolute w-100 mt-1 d-none" style="max-height: 200px; overflow-y: auto; z-index: 1050; border-radius: 8px; border: 1px solid #e2e8f0; background: white;">
              <!-- Dynamic results injected here -->
            </div>
          </div>

          <!-- Date Selector -->
          <div class="mb-3">
            <label class="form-label" style="font-weight: 600; color: #475569; font-size: 13px;">Attendance Date <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text" style="background: #f8fafc; border-right: none; color: #94a3b8;"><i class="ti ti-calendar"></i></span>
              <input type="date" id="ma-date" class="form-control" style="border-left: none; border-radius: 0 8px 8px 0; font-size: 14px;" required>
            </div>
          </div>

          <!-- Time Selectors -->
          <div class="row">
            <div class="col-6 mb-3">
              <label class="form-label" style="font-weight: 600; color: #475569; font-size: 13px;">Mark-In Time <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text" style="background: #f8fafc; border-right: none; color: #94a3b8;"><i class="ti ti-clock"></i></span>
                <input type="time" id="ma-in-time" class="form-control" value="09:00" style="border-left: none; border-radius: 0 8px 8px 0; font-size: 14px;" required>
              </div>
            </div>
            <div class="col-6 mb-3">
              <label class="form-label" style="font-weight: 600; color: #475569; font-size: 13px;">Mark-Out Time <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text" style="background: #f8fafc; border-right: none; color: #94a3b8;"><i class="ti ti-clock"></i></span>
                <input type="time" id="ma-out-time" class="form-control" value="18:00" style="border-left: none; border-radius: 0 8px 8px 0; font-size: 14px;" required>
              </div>
            </div>
          </div>

          <!-- Location Selector -->
          <div class="mb-3">
            <label class="form-label" style="font-weight: 600; color: #475569; font-size: 13px;">Location <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text" style="background: #f8fafc; border-right: none; color: #94a3b8;"><i class="ti ti-map-pin"></i></span>
              <select id="ma-location" class="form-select" style="border-left: none; border-radius: 0 8px 8px 0; font-size: 14px;" required>
                <!-- Dynamic locations injected here -->
              </select>
            </div>
          </div>

        </div>
        <div class="modal-footer" style="background: #f8fafc; border-top: 1px solid #e2e8f0; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px; padding: 12px 20px;">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="font-weight: 600; font-size: 13px; border-radius: 8px;">Cancel</button>
          <button type="submit" class="btn btn-primary" id="ma-submit-btn" style="background: #4f46e5; border-color: #4f46e5; font-weight: 600; font-size: 13px; border-radius: 8px; color: white;">
            <i class="ti ti-check me-1"></i> Save Attendance
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Labour Check-Out Modal -->
<div class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="checkoutModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.15);">
      <div class="modal-header" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; border-top-left-radius: 12px; border-top-right-radius: 12px;">
        <h5 class="modal-title" id="checkoutModalLabel" style="font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 8px;">
          <i class="ti ti-logout" style="color: #2563eb; font-size: 20px;"></i> Labour Check-Out
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="checkoutForm">
        <div class="modal-body" style="padding: 20px;">
          <input type="hidden" id="co-attendance-id" name="attendanceid">
          
          <!-- Labour Information Display -->
          <div class="mb-3 p-3 rounded" style="background: #f1f5f9; border: 1px solid #e2e8f0;">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <span class="text-muted font-12 fw-semibold">LABOUR NAME:</span>
              <span class="badge bg-primary text-white" id="co-labour-id-badge"></span>
            </div>
            <div class="fw-bold font-15 text-dark" id="co-labour-name"></div>
            <div class="d-flex gap-3 mt-2 text-muted font-12">
              <span><i class="ti ti-calendar me-1"></i><span id="co-att-date"></span></span>
              <span><i class="ti ti-clock me-1"></i>Check-In: <strong id="co-checkin-display" class="text-dark"></strong></span>
            </div>
          </div>

          <!-- Select Check-Out Time -->
          <div class="mb-3">
            <label class="form-label" style="font-weight: 600; color: #475569; font-size: 13px;">Choose Check-Out Time <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text" style="background: #f8fafc; border-right: none; color: #94a3b8;"><i class="ti ti-clock-play"></i></span>
              <input type="time" id="co-out-time" class="form-control" style="border-left: none; border-radius: 0 8px 8px 0; font-size: 14px;" required>
            </div>
          </div>

          <!-- Enter Remark -->
          <div class="mb-3">
            <label class="form-label" style="font-weight: 600; color: #475569; font-size: 13px;">Remark / Reason <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text" style="background: #f8fafc; border-right: none; color: #94a3b8;"><i class="ti ti-notes"></i></span>
              <input type="text" id="co-remark" class="form-control" placeholder="Enter check-out remark..." style="border-left: none; border-radius: 0 8px 8px 0; font-size: 14px;" required>
            </div>
          </div>

        </div>
        <div class="modal-footer" style="background: #f8fafc; border-top: 1px solid #e2e8f0; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px; padding: 12px 20px;">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="font-weight: 600; font-size: 13px; border-radius: 8px;">Cancel</button>
          <button type="submit" class="btn btn-primary" id="co-submit-btn" style="background: #2563eb; border-color: #2563eb; font-weight: 600; font-size: 13px; border-radius: 8px; color: white;">
            <i class="ti ti-check me-1"></i> Update Check-Out
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
if(isset($_GET['vw']) && $_GET['vw'] == 1){
    // no footer
} else {
    require('footer.php');
}
?>

<script>
/* ─── Helper Functions ───────────────────────────────────────────── */
function htmlEscape(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function formatDT(dt) {
    if (!dt) return "-";
    try {
        var d = new Date(String(dt).replace(" ", "T"));
        if (isNaN(d.getTime())) return dt;
        return d.toLocaleTimeString("en-IN", { hour: "2-digit", minute: "2-digit", second: "2-digit" });
    } catch (e) { return dt; }
}

/* ─── Global vars ────────────────────────────────────────────────── */
var routurl    = $('body').data('routurl') || '';
var companyid  = $('body').data('companyid');
var subcompanyid = $('body').data('subcompanyid');
var projectid  = $('body').data('projectid');
var mdid       = "<?php echo $mdid ?? ''; ?>";
var key        = $('body').data('key') || "<?php echo $key ?? ''; ?>";
var loginroleid = <?php echo isset($loginroleid) ? (int)$loginroleid : 99; ?>;
var loginname  = <?php echo isset($loginname) ? json_encode($loginname) : "'SYSTEM'"; ?>;

var currentTab = 'mn';       // 'mn' | 'dp' | 'combined'
var allRows    = [];          // raw data from API
var locationMap = {};
var currentHomeLocFilter = 'all';

/* ─── Live Clock ─────────────────────────────────────────────────── */
(function tick() {
    var el = document.getElementById('av-live-clock');
    if (!el) return;
    var now = new Date();
    var pad = n => String(n).padStart(2,'0');
    var days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    var mons = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    el.textContent = days[now.getDay()] + ' ' + now.getDate() + ' ' +
                     mons[now.getMonth()] + ' ' + now.getFullYear() +
                     '  ' + pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());
    setTimeout(tick, 1000);
})();

/* ─── Set today's date as default ─────────────────────────────────── */
$(document).ready(function () {
    var today = new Date();
    var y = today.getFullYear();
    var m = String(today.getMonth()+1).padStart(2,'0');
    var d = String(today.getDate()).padStart(2,'0');
    $('#av-date').val(y+'-'+m+'-'+d);

    /* Load locations */
    LabourAttendanceLocationList('all','all', function(res) {
        var opts = '<option value="all">All Locations</option>';
        if (res && res.length > 0) {
            for (var i = 0; i < res.length; i++) {
                locationMap[res[i].LocationId] = res[i].LocationName;
                opts += '<option value="' + res[i].LocationId + '">' + res[i].LocationName + '</option>';
            }
        }
        $('#av-location').html(opts);
        /* After locations loaded, load polyhouses */
        loadPolyhouses($('#av-location').val());
    });

    /* On location change — reload polyhouses and reset Scan By */
    $('#av-location').on('change', function () {
        resetScanByDropdown();
        loadPolyhouses($(this).val());
    });

    /* On QR location change — reset Scan By */
    $('#av-polyhouse').on('change', function () {
        resetScanByDropdown();
    });

    /* On date change — reset Scan By */
    $('#av-date').on('change', function () {
        resetScanByDropdown();
    });
});

function resetScanByDropdown() {
    selectedScanners.clear();
    $('#scanby-select-all').prop('checked', true);
    $('.scanby-checkbox').prop('checked', true);
    $('.av-multiselect-text').text('All Scanners');
}

/* ─── Load Polyhouses for a location ──────────────────────────────── */
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

/* ─── Load Attendance View ────────────────────────────────────────── */
function loadAttendanceView() {
    var locationid  = $('#av-location').val() || 'all';
    var polyhouseid = $('#av-polyhouse').val() || 'all';
    var attdate     = $('#av-date').val();
    var scanby      = 'all';

    if (!attdate) {
        Swal.fire({ icon: 'warning', title: 'Date Required', text: 'Please select a date.', confirmButtonColor: '#1b7a48' });
        return;
    }

    // Show loading
    $('#av-tbody').html('<tr><td colspan="14"><div class="av-loading"><div class="av-spinner"></div><p>Loading attendance data…</p></div></td></tr>');
    resetStats();

    $.ajax({
        type: 'POST',
        url: routurl + 'modal/hrm_laboursalarymodal.php?action=GetAttendanceViewData',
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
            Swal.fire({ icon: 'error', title: 'Request Failed', text: 'Could not load attendance data.' });
            resetTable();
        }
    });
}

/* ─── Tabs ────────────────────────────────────────────────────────── */
function switchTab(tab, el) {
    currentTab = tab;
    $('.av-tab').removeClass('active');
    $(el).addClass('active');
    renderTable(tab);
}

var currentStatFilter = 'total'; // Default active card is Card 1 ('total')
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

/* ─── Render Table ────────────────────────────────────────────────── */
function renderTable(tab) {
    var data = allRows;

    /* Filter by tab (only show labours who have marked attendance for the active tab) */
    if (tab === 'mn') {
        data = data.filter(function(r) { return r.checkintime_mn; });
    } else if (tab === 'dp') {
        data = data.filter(function(r) { return r.checkintime_dp; });
    } else {
        data = data.filter(function(r) { return r.checkintime_mn || r.checkintime_dp; });
    }

    /* Filter by clicked statistics card */
    if (currentStatFilter === 'total') {
        // Card 1: Check-In Labour of selected home location (checked in anywhere)
        var selectedLocation = $('#av-location').val() || 'all';
        data = data.filter(function(r) {
            var regLoc = r.labourlocationid || r.locationid || '';
            var ci     = (tab === 'mn') ? r.checkintime_mn 
                       : (tab === 'dp') ? r.checkintime_dp 
                       : (r.checkintime_mn || r.checkintime_dp);
            if (!ci) return false;
            if (selectedLocation === 'all') {
                return true;
            } else {
                return isSameLocation(regLoc, selectedLocation);
            }
        });

        if (currentCheckinLocFilter !== 'all') {
            data = data.filter(function(r) {
                var checkinLoc = (tab === 'mn') ? (r.checkinlocation_mn || '') 
                               : (tab === 'dp' ? (r.checkinlocation_dp || '') 
                               : (r.checkinlocation_mn || r.checkinlocation_dp || ''));
                return isSameLocation(checkinLoc, currentCheckinLocFilter);
            });
        }
    } else if (currentStatFilter === 'work_location' || currentStatFilter === 'merged') {
        // Card 2 / Merged Card: Working Labours in selected location (assigned to selectedLocation AND checked in at home location)
        var selectedLocation = $('#av-location').val() || 'all';
        data = data.filter(function(r) {
            var regLoc = r.labourlocationid || r.locationid || '';
            var checkinLoc = (tab === 'mn') ? (r.checkinlocation_mn || '') : (tab === 'dp' ? (r.checkinlocation_dp || '') : (r.checkinlocation_mn || r.checkinlocation_dp || ''));
            var ci = (tab === 'mn') ? r.checkintime_mn : (tab === 'dp' ? r.checkintime_dp : (r.checkintime_mn || r.checkintime_dp));
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
        // Present (Full) -> checked in and checked out, AND working hours >= 2 hours
        data = data.filter(function(r) {
            var ci = (tab === 'mn') ? r.checkintime_mn : (tab === 'dp' ? r.checkintime_dp : (r.checkintime_mn || r.checkintime_dp));
            var co = (tab === 'mn') ? r.checkouttime_mn : (tab === 'dp' ? r.checkouttime_dp : (r.checkouttime_mn || r.checkouttime_dp));
            var wh = (tab === 'mn') ? r.workinghours_mn : (tab === 'dp' ? r.workinghours_dp : (r.workinghours_mn || r.workinghours_dp));
            if (ci && co && wh && wh !== '00:00:00') {
                var parts = wh.split(':');
                var hrs = parseInt(parts[0] || 0) + (parseInt(parts[1] || 0) / 60);
                return hrs >= 2;
            }
            return false;
        });
    } else if (currentStatFilter === 'missing') {
        // Mark-Out Missing -> checked in but NOT checked out
        data = data.filter(function(r) {
            var ci = (tab === 'mn') ? r.checkintime_mn : (tab === 'dp' ? r.checkintime_dp : (r.checkintime_mn || r.checkintime_dp));
            var co = (tab === 'mn') ? r.checkouttime_mn : (tab === 'dp' ? r.checkouttime_dp : (r.checkouttime_mn || r.checkouttime_dp));
            return ci && !co;
        });
    } else if (currentStatFilter === 'early') {
        // Early Checkout -> checked out and working hours < 2 hours
        data = data.filter(function(r) {
            var co = (tab === 'mn') ? r.checkouttime_mn : (tab === 'dp' ? r.checkouttime_dp : (r.checkouttime_mn || r.checkouttime_dp));
            var wh = (tab === 'mn') ? r.workinghours_mn : (tab === 'dp' ? r.workinghours_dp : (r.workinghours_mn || r.workinghours_dp));
            if (co && wh && wh !== '00:00:00') {
                var parts = wh.split(':');
                var hrs = parseInt(parts[0] || 0) + (parseInt(parts[1] || 0) / 60);
                return hrs < 2;
            }
            return false;
        });
    } else if (currentStatFilter === 'swap') {
        // Swap Labours -> assigned to selectedLocation BUT checked in at a different location
        var selectedLocation = $('#av-location').val() || 'all';
        data = data.filter(function(r) {
            var regLoc = r.labourlocationid || r.locationid || '';
            var checkinLoc = (tab === 'mn') ? (r.checkinlocation_mn || '') : (tab === 'dp' ? (r.checkinlocation_dp || '') : (r.checkinlocation_mn || r.checkinlocation_dp || ''));
            var ci = (tab === 'mn') ? r.checkintime_mn : (tab === 'dp' ? r.checkintime_dp : (r.checkintime_mn || r.checkintime_dp));
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
                var scanLoc = (tab === 'mn') ? (r.checkinlocation_mn || '') 
                            : (tab === 'dp' ? (r.checkinlocation_dp || '') 
                            : (r.checkinlocation_mn || r.checkinlocation_dp || ''));
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
                return (inMN && selectedScanners.has(inMN)) || 
                       (outMN && selectedScanners.has(outMN));
            } else if (tab === 'dp') {
                return (inDP && selectedScanners.has(inDP)) || 
                       (outDP && selectedScanners.has(outDP));
            } else {
                return (inMN && selectedScanners.has(inMN)) || 
                       (outMN && selectedScanners.has(outMN)) || 
                       (inDP && selectedScanners.has(inDP)) || 
                       (outDP && selectedScanners.has(outDP));
            }
        });
    } else if (totalCheckboxes > 0 && checkedCheckboxes === 0) {
        data = [];
    }

    var searchVal = ($('#av-search').val() || '').toLowerCase().trim();

    /* Filter by search */
    if (searchVal) {
        data = data.filter(function(r) {
            return (r.FullName || '').toLowerCase().includes(searchVal) ||
                   (r.EmployeeID || '').toLowerCase().includes(searchVal);
        });
    }

    /* Build headers & rows by tab */
    var thead = '';
    var tbody = '';
    var count = 0;

    /* Shared base columns */
    var baseHead = '<th>#</th><th>Labour Name</th>';

    if (tab === 'mn') {
        /* Main Gate columns */
        thead = baseHead + '<th>Check-In (Main Gate)</th><th>Check-Out (Main Gate)</th><th>Working Hrs</th><th>Scanner Location</th><th>Status</th>';
        if (loginroleid <= 2) thead += '<th class="text-end pe-4 noexport">Action</th>';
        data.forEach(function(r, i) {
            var ci = r.checkintime_mn  ? formatDT(r.checkintime_mn)  : null;
            var co = r.checkouttime_mn ? formatDT(r.checkouttime_mn) : null;
            var wh = r.workinghours_mn || null;
            var sc = r.checkinscannerid_mn || r.checkinscannerid_mn || '—';
            var st = getStatusPill(ci, co, wh);

            var regLoc = r.labourlocationid || r.locationid;
            var isSwap = r.checkintime_mn && r.checkinlocation_mn && r.checkinlocation_mn.startsWith('LOC') && r.checkinlocation_mn !== regLoc;

            tbody += buildRow(i+1, r, ci, co, wh, sc, st, r.checkinby_mn, r.checkoutby_mn, isSwap);
            count++;
        });
    } else if (tab === 'dp') {
        /* QR Location columns */
        thead = baseHead + '<th>Check-In (QR Loc)</th><th>Check-Out (QR Loc)</th><th>Working Hrs</th><th>Scanner Location</th><th>Status</th>';
        if (loginroleid <= 2) thead += '<th class="text-end pe-4 noexport">Action</th>';
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
        /* Combined view */
        thead = baseHead + '<th>MN Check-In</th><th>MN Check-Out</th><th>MN Hrs</th><th>DP Check-In</th><th>DP Check-Out</th><th>DP Hrs</th><th>Status</th>';
        if (loginroleid <= 2) thead += '<th class="text-end pe-4 noexport">Action</th>';
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
            if (loginroleid <= 2) {
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

            tbody += '<tr' + rowClass + ' data-name="'+(r.FullName||'').toLowerCase()+'" data-id="'+(r.EmployeeID||'').toLowerCase()+'">' +
                '<td style="color:var(--att-gray);font-size:11px;">'+(i+1)+'</td>' +
                '<td><a href="../labourdash/index.php?empid=' + encodeURIComponent(r.EmployeeID) + '&k=' + encodeURIComponent(key) + '&mdid=' + encodeURIComponent(mdid) + '" class="av-labour-link"><div class="av-labour-name">'+htmlEscape(r.FullName || '—')+'</div><div class="av-labour-id">'+htmlEscape(r.EmployeeID || '')+'</div></a></td>' +
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

    var recMan = 0;
    var recWoman = 0;

    data.forEach(function(r) {
        var g = (r.Gender || r.gender || '').toString().trim().toLowerCase();
        if (g === 'female' || g === 'f' || g === 'woman' || g === 'women') {
            recWoman++;
        } else {
            recMan++;
        }
    });

    $('#av-thead-row').html(thead);
    $('#av-tbody').html(count > 0 ? tbody : '<tr><td colspan="14"><div class="av-empty"><i class="ti ti-user-off"></i><p>No attendance records found for the selected filters.</p></div></td></tr>');
    $('#av-record-count').text(count);
    $('#av-record-man').text(recMan);
    $('#av-record-woman').text(recWoman);

    /* Update tab counts */
    var mnCount = allRows.filter(r => r.checkintime_mn).length;
    var dpCount = allRows.filter(r => r.checkintime_dp).length;
    $('#tab-mn-count').text(mnCount);
    $('#tab-dp-count').text(dpCount);

    /* Update statistics dynamically based on this tab's unfiltered rows */
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

/* ─── Build table row ─────────────────────────────────────────────── */
function buildRow(idx, r, ci, co, wh, sc, statusPill, ci_by, co_by, isSwap) {
    var actionTd = '';
    if (loginroleid <= 2) {
        if (r.attendanceid) {
            var coBtn = '<button class="btn btn-sm btn-outline-primary me-1" onclick="openCheckoutModal(\'' + r.attendanceid + '\', \'' + htmlEscape(r.FullName || '') + '\', \'' + htmlEscape(r.EmployeeID || '') + '\', \'' + htmlEscape(r.attendancedate || '') + '\', \'' + (r.checkintime_mn || r.checkintime_dp || '') + '\', \'' + (r.checkouttime_mn || r.checkouttime_dp || '') + '\', \'' + htmlEscape(r.remark_mn || '') + '\')" title="Check-Out / Edit Remark"><i class="ti ti-logout"></i></button>';
            var delBtn = '<button class="btn btn-sm btn-outline-danger" onclick="removeAttendance(\'' + r.attendanceid + '\', \'' + htmlEscape(r.FullName || '') + '\')" title="Remove Attendance"><i class="ti ti-trash"></i></button>';
            actionTd = '<td class="text-end pe-4">' + coBtn + delBtn + '</td>';
        } else {
            actionTd = '<td class="text-end pe-4"><button class="btn btn-sm btn-outline-secondary me-1" disabled title="No Attendance Record"><i class="ti ti-logout"></i></button><button class="btn btn-sm btn-outline-secondary" disabled title="No Attendance to Remove"><i class="ti ti-trash"></i></button></td>';
        }
    }
    var rowClass = isSwap ? ' class="av-swap-row"' : '';
    var scannerBadge = (sc && sc !== '—') ? '<span class="badge bg-light text-dark border"><i class="ti ti-map-pin me-1"></i>' + htmlEscape(sc) + '</span>' : '—';

    return '<tr' + rowClass + ' data-name="'+(r.FullName||'').toLowerCase()+'" data-id="'+(r.EmployeeID||'').toLowerCase()+'">' +
        '<td style="color:var(--att-gray);font-size:11px;">'+idx+'</td>' +
        '<td><a href="../labourdash/index.php?empid=' + encodeURIComponent(r.EmployeeID) + '&k=' + encodeURIComponent(key) + '&mdid=' + encodeURIComponent(mdid) + '" class="av-labour-link"><div class="av-labour-name">'+htmlEscape(r.FullName || '—')+'</div><div class="av-labour-id">'+htmlEscape(r.EmployeeID||'')+'</div></a></td>' +
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

/* Helper to normalize and compare location IDs/codes/names */
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

/* ─── Stats Update ────────────────────────────────────────────────── */
function updateStatsForTab(tab, tabRows) {
    var selectedLocation = $('#av-location').val() || 'all';
    var selLocName = (selectedLocation !== 'all') ? ($('#av-location option:selected').text() || '') : '';

    if (selectedLocation !== 'all' && selLocName && selLocName !== 'All Locations') {
        $('#stat-lbl-total').text('Check-In Labour of ' + selLocName);
        $('#stat-lbl-work').text('Working Labours in ' + selLocName);
        $('#stat-lbl-merged').text('Attendance in ' + selLocName);
    } else {
        $('#stat-lbl-total').text('Total Check-In Labour');
        $('#stat-lbl-work').text('Total Work Labour');
        $('#stat-lbl-merged').text('Attendance Breakdown');
    }

    var total        = 0;
    var totalMan     = 0;
    var totalWoman   = 0;

    var workLoc      = 0;
    var workMan      = 0;
    var workWoman    = 0;

    var early        = 0;
    var earlyMan     = 0;
    var earlyWoman   = 0;

    var swap         = 0;
    var swapMan      = 0;
    var swapWoman    = 0;

    var mergedCheckin  = 0;
    var mergedCheckout = 0;
    var mergedManual   = 0;

    tabRows.forEach(function(r) {
        var g = (r.Gender || r.gender || '').toString().trim().toLowerCase();
        var isWoman = (g === 'female' || g === 'f' || g === 'woman' || g === 'women');

        var regLoc = r.labourlocationid || r.locationid || '';
        var checkinLoc = (tab === 'mn') ? (r.checkinlocation_mn || '') : (tab === 'dp' ? (r.checkinlocation_dp || '') : (r.checkinlocation_mn || r.checkinlocation_dp || ''));
        var ci = (tab === 'mn') ? r.checkintime_mn : (tab === 'dp') ? r.checkintime_dp : (r.checkintime_mn || r.checkintime_dp);
        var co = (tab === 'mn') ? r.checkouttime_mn : (tab === 'dp') ? r.checkouttime_dp : (r.checkouttime_mn || r.checkouttime_dp);
        var wh = (tab === 'mn') ? r.workinghours_mn : (tab === 'dp') ? r.workinghours_dp : (r.workinghours_mn || r.workinghours_dp);

        // 1. Card 1: Check-In Labour of selected home location (checked in anywhere)
        if (selectedLocation === 'all') {
            if (ci) {
                total++;
                if (isWoman) totalWoman++; else totalMan++;
            }
        } else {
            if (ci && isSameLocation(regLoc, selectedLocation)) {
                total++;
                if (isWoman) totalWoman++; else totalMan++;
            }
        }

        // 2. Card 2 & Merged Card: Working Labours in selected location (labours whose check-in location matches selectedLocation)
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
            if (isWoman) workWoman++; else workMan++;

            // Data breakdown for Merged Card
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
                    if (isWoman) earlyWoman++; else earlyMan++;
                }
            }
        }

        /* 5. Swap Labours: count labours involved in swap for selectedLocation */
        var isSwapRow = false;
        if (selectedLocation === 'all') {
            if (ci && checkinLoc !== '' && !isSameLocation(regLoc, checkinLoc)) {
                isSwapRow = true;
            }
        } else {
            var isOutgoingSwap = ci && isSameLocation(regLoc, selectedLocation) && checkinLoc !== '' && !isSameLocation(checkinLoc, selectedLocation);
            var isIncomingSwap = ci && isSameLocation(checkinLoc, selectedLocation) && !isSameLocation(regLoc, selectedLocation);
            if (isOutgoingSwap || isIncomingSwap) {
                isSwapRow = true;
            }
        }

        if (isSwapRow) {
            swap++;
            if (isWoman) swapWoman++; else swapMan++;
        }
    });

    var mergedTotal = mergedCheckin + mergedManual;

    $('#stat-total-labour').text(total);
    $('#stat-total-man').text(totalMan);
    $('#stat-total-woman').text(totalWoman);

    $('#stat-work-labour').text(workLoc);
    $('#stat-work-man').text(workMan);
    $('#stat-work-woman').text(workWoman);

    $('#stat-early').text(early);
    $('#stat-early-man').text(earlyMan);
    $('#stat-early-woman').text(earlyWoman);

    $('#stat-swap-labours').text(swap);
    $('#stat-swap-man').text(swapMan);
    $('#stat-swap-woman').text(swapWoman);

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
        var locName = locationMap[locId] || locId;
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
        var locName = locationMap[locId] || locId;
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
        : (locationMap[currentSwapScannerLocFilter] || currentSwapScannerLocFilter);

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
        var locName = locationMap[locId] || locId;
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
    $('#av-tbody').html('<tr><td colspan="14"><div class="av-empty"><i class="ti ti-calendar-off"></i><p>Failed to load data. Please try again.</p></div></td></tr>');
    $('#av-record-count').text(0);
}

/* ─── Search Filter ───────────────────────────────────────────────── */
function filterTable() {
    renderTable(currentTab);
}

/* ─── Auto-refresh every 60 seconds if on today ──────────────────── */
setInterval(function() {
    var today = new Date();
    var y = today.getFullYear(), m = String(today.getMonth()+1).padStart(2,'0'), d = String(today.getDate()).padStart(2,'0');
    if ($('#av-date').val() === y+'-'+m+'-'+d && allRows.length > 0) {
        loadAttendanceView();
    }
}, 60000);

/* ─── Export to Excel ────────────────────────────────────────────── */
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

    // Get title info
    const locationName = $('#av-location option:selected').text() || 'All Locations';
    const qrLocationName = $('#av-polyhouse option:selected').text() || 'All QR Locations';
    const selectedDate = $('#av-date').val() || '';
    const titleText = `Labour Attendance Report - ${locationName} (${qrLocationName}) - ${selectedDate}`;

    // Clone table
    const clone = table.cloneNode(true);

    // Modify clone headers to split Labour Name into Labour ID and Labour Name
    const headerRow = clone.querySelector("thead tr");
    if (headerRow) {
        const ths = Array.from(headerRow.querySelectorAll("th"));
        const nameTh = ths.find(th => th.textContent.trim().toLowerCase() === "labour name");
        if (nameTh) {
            const idTh = document.createElement("th");
            idTh.textContent = "Labour ID";
            const newNameTh = document.createElement("th");
            newNameTh.textContent = "Labour Name";
            
            // Replace the old th with the two new ths
            nameTh.replaceWith(idTh, newNameTh);
        }
    }

    // Modify clone rows to split Labour Name cell into Labour ID and Labour Name cells
    clone.querySelectorAll("tbody tr").forEach(row => {
        const nameLink = row.querySelector(".av-labour-link");
        if (nameLink) {
            const nameDiv = nameLink.querySelector(".av-labour-name");
            const idDiv = nameLink.querySelector(".av-labour-id");
            
            const labourName = nameDiv ? nameDiv.textContent.trim() : "";
            const labourId = idDiv ? idDiv.textContent.trim() : "";
            
            // Find the td parent of the link
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

    // Update colspan if there is a colspan cell (e.g. for empty state)
    clone.querySelectorAll("td[colspan]").forEach(td => {
        const currentColspan = parseInt(td.getAttribute("colspan") || 1);
        td.setAttribute("colspan", currentColspan + 1);
    });

    // Clean up content inside cloned cells for clean spreadsheet output
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

    // Convert to worksheet starting with title, summary row and empty row
    const totalRecs = $('#av-record-count').text() || '0';
    const manRecs = $('#av-record-man').text() || '0';
    const womanRecs = $('#av-record-woman').text() || '0';
    const summaryText = `Total Records: ${totalRecs} | Man (M): ${manRecs} | Women (W): ${womanRecs}`;

    const ws = XLSX.utils.aoa_to_sheet([[titleText], [summaryText], []]);

    // Append table starting at A4 (row 4 of the sheet)
    XLSX.utils.sheet_add_dom(ws, clone, { origin: "A4" });

    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Labour Attendance");

    XLSX.writeFile(wb, `Labour_Attendance_${selectedDate}.xlsx`);
}

/* ─── Add Missing Attendance Logic ───────────────────────────────── */
var projectLabourCache = null;
var locationMap = {};

$(document).on('click', '#addMissingAttBtn', function() {
    // 1. Reset Form
    $('#addMissingAttForm')[0].reset();
    $('#ma-labour-id').val('');
    $('#ma-search-results').addClass('d-none').empty();
    
    // 2. Pre-fill Date
    var currentDate = $('#av-date').val();
    $('#ma-date').val(currentDate || new Date().toISOString().split('T')[0]);
    
    // 3. Pre-fill Location Options
    var locOpts = '';
    $('#av-location option').each(function() {
        var val = $(this).val();
        var txt = $(this).text();
        if (val !== 'all') {
            locOpts += '<option value="' + val + '">' + txt + '</option>';
        }
    });
    $('#ma-location').html(locOpts);
    
    // 4. Default Location Selection
    var selectedLoc = $('#av-location').val();
    if (selectedLoc && selectedLoc !== 'all') {
        $('#ma-location').val(selectedLoc);
    }

    // 5. Open Modal
    $('#addMissingAttModal').modal('show');
    
    // 6. Fetch project active labours if not cached
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
                        locationMap = {};
                        data.locations.forEach(function(loc) {
                            locationMap[loc.LocationId] = loc.LocationName;
                        });
                    }
                }
            } catch(e) { console.error('Error loading project active labours', e); }
        });
    }
});

// Autocomplete Search logic
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

// Selection click
$(document).on('click', '.ma-item', function(e) {
    e.preventDefault();
    var id = $(this).attr('data-id');
    var name = $(this).attr('data-name');
    var locId = $(this).attr('data-locationid');
    
    $('#ma-labour-search').val(name + ' (ID: ' + id + ')');
    $('#ma-labour-id').val(id);
    
    // Auto-select location id
    if (locId && $('#ma-location option[value="' + locId + '"]').length > 0) {
        $('#ma-location').val(locId);
    }
    
    $('#ma-search-results').addClass('d-none').empty();
});

// Keyboard Navigation logic for Autocomplete Search
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
    
    if (e.which === 40) { // Arrow Down
        e.preventDefault();
        var nextIndex = activeIndex + 1;
        if (nextIndex >= items.length) {
            nextIndex = 0;
        }
        items.removeClass('bg-light');
        var newActive = items.eq(nextIndex).addClass('bg-light');
        scrollMaIntoView(resultsDiv, newActive);
    } else if (e.which === 38) { // Arrow Up
        e.preventDefault();
        var prevIndex = activeIndex - 1;
        if (prevIndex < 0) {
            prevIndex = items.length - 1;
        }
        items.removeClass('bg-light');
        var newActive = items.eq(prevIndex).addClass('bg-light');
        scrollMaIntoView(resultsDiv, newActive);
    } else if (e.which === 13) { // Enter Key
        if (activeItem.length > 0) {
            e.preventDefault();
            activeItem.trigger('click');
        }
    } else if (e.which === 27) { // Escape Key
        e.preventDefault();
        resultsDiv.addClass('d-none').empty();
    }
});

// Sync mouse hover with keyboard navigation active class
$(document).on('mouseenter', '.ma-item', function() {
    $('.ma-item').removeClass('bg-light');
    $(this).addClass('bg-light');
});

// Scroll helper to keep highlighted item in view
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

// Close results when clicking outside
$(document).on('click', function(e) {
    if (!$(e.target).closest('#ma-labour-search, #ma-search-results').length) {
        $('#ma-search-results').addClass('d-none');
    }
});

// Submit Form
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
    
    $.post("../labourdash/modal/labourdash_modal.php?action=MarkManualAttendance", {
        employeeID    : empId,
        projectid     : projectid,
        companyid     : companyid,
        subcompanyid  : subcompanyid,
        attendancedate: $('#ma-date').val(),
        checkin       : $('#ma-in-time').val(),
        checkout      : $('#ma-out-time').val(),
        locationName  : locName,
        locationId    : locId,
        loginname     : 'Admin'
    }, function(res) {
        $btn.prop('disabled', false).html(origHtml);
        try {
            var data = (typeof res === 'string') ? JSON.parse(res) : res;
            if (data.status === 'Success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Saved!',
                    text: 'Attendance has been recorded successfully.',
                    timer: 2000,
                    showConfirmButton: false
                });
                $('#addMissingAttModal').modal('hide');
                loadAttendanceView(); // Reload list
            } else {
                Swal.fire('Error', data.message || 'Could not record attendance.', 'error');
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
    
    // Determine which checkboxes should be checked:
    // If selectedScanners is empty, we check all of them by default.
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
    
    // Sync state
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

// Event listeners for multiselect
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

function removeAttendance(attendanceId, labourName) {
    if (!attendanceId) return;

    Swal.fire({
        title: 'Remove Attendance?',
        html: 'Are you sure you want to remove attendance for <strong>' + htmlEscape(labourName) + '</strong>?',
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
                text: 'Please wait while we remove the attendance record.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.post(routurl + 'modal/hrm_laboursalarymodal.php?action=RemoveAttendance', {
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
                            text: 'Attendance record has been successfully removed.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        loadAttendanceView(); // Reload the list and update stats
                    } else {
                        Swal.fire('Error', data.message || 'Could not remove attendance.', 'error');
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

    if (!attendanceId) {
        Swal.fire({ icon: 'warning', title: 'Invalid Record', text: 'Attendance ID is missing.' });
        return;
    }
    if (!outTime) {
        Swal.fire({ icon: 'warning', title: 'Check-Out Time Required', text: 'Please select check-out time.' });
        return;
    }
    if (!remark) {
        Swal.fire({ icon: 'warning', title: 'Remark Required', text: 'Please enter a check-out remark.' });
        return;
    }

    var $btn = $('#co-submit-btn');
    var origHtml = $btn.html();
    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Updating...');

    $.post(routurl + 'modal/hrm_laboursalarymodal.php?action=UpdateLabourCheckout', {
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
                    text: data.message || 'Check-out updated successfully.',
                    timer: 2000,
                    showConfirmButton: false
                });
                $('#checkoutModal').modal('hide');
                loadAttendanceView();
            } else {
                Swal.fire('Error', data.message || 'Could not update check-out.', 'error');
            }
        } catch(err) {
            Swal.fire('Error', 'Unexpected response from server.', 'error');
        }
    }).fail(function() {
        $btn.prop('disabled', false).html(origHtml);
        Swal.fire('Error', 'Server connection failed.', 'error');
    });
});
</script>
