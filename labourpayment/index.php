<?php
require('header.php');
?>
<!-- Premium Google Fonts & Dashboard Custom Styles -->
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    /* Premium Dashboard Stylesheet */
    :root {
        --dash-primary: #4f46e5;
        --dash-primary-light: #eef2ff;
        --dash-success: #10b981;
        --dash-success-light: #ecfdf5;
        --dash-warning: #f59e0b;
        --dash-warning-light: #fef3c7;
        --dash-danger: #ef4444;
        --dash-danger-light: #fef2f2;
        --dash-info: #06b6d4;
        --dash-info-light: #ecfeff;
        --dash-dark: #0f172a;
        --dash-card-bg: #ffffff;
        --dash-border: #e2e8f0;
        --dash-text-muted: #64748b;
    }

    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
        background-color: #f8fafc;
        color: #1e293b;
    }

    h1, h2, h3, h4, h5, h6, .card-title, .modal-title {
        font-family: 'Outfit', sans-serif;
    }

    /* Sticky Control Bar */
    .sticky-control-bar {
        position: sticky;
        top: 60px; /* Offset for main header */
        z-index: 100;
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border-bottom: 1px solid var(--dash-border);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        padding: 12px 24px;
        transition: all 0.3s ease;
    }
    
    .is-iframe .sticky-control-bar {
        top: 0px;
    }

    /* Filter Form Styles */
    .filter-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--dash-text-muted);
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }

    .form-select-premium, .daterange-premium {
        border: 1px solid var(--dash-border);
        border-radius: 10px;
        font-size: 13px;
        font-weight: 500;
        color: #334155;
        padding: 8px 12px;
        background-color: #ffffff;
        transition: all 0.2s ease;
    }

    .form-select-premium:focus, .daterange-premium:focus {
        border-color: var(--dash-primary);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
        outline: none;
    }

    /* KPI Cards */
    .kpi-card {
        background: var(--dash-card-bg);
        border: 1px solid var(--dash-border);
        border-radius: 16px;
        padding: 16px 20px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 135px;
        height: 100%;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }

    .kpi-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px -10px rgba(0, 0, 0, 0.08);
        border-color: #cbd5e1;
    }

    .kpi-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }

    .kpi-value {
        font-size: 20px;
        font-weight: 800;
        color: var(--dash-dark);
        margin-top: 6px;
        margin-bottom: 2px;
        line-height: 1.1;
    }

    .kpi-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--dash-text-muted);
        letter-spacing: 0.5px;
    }

    .kpi-change {
        font-size: 11px;
        font-weight: 600;
        margin-top: 8px;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    /* Colors and Gradients for Cards */
    .kpi-workforce { border-left: 4px solid var(--dash-primary); }
    .kpi-workforce .kpi-icon { background: var(--dash-primary-light); color: var(--dash-primary); }
    .kpi-present { border-left: 4px solid var(--dash-success); }
    .kpi-present .kpi-icon { background: var(--dash-success-light); color: var(--dash-success); }
    .kpi-absent { border-left: 4px solid var(--dash-danger); }
    .kpi-absent .kpi-icon { background: var(--dash-danger-light); color: var(--dash-danger); }
    .kpi-late { border-left: 4px solid var(--dash-warning); }
    .kpi-late .kpi-icon { background: var(--dash-warning-light); color: var(--dash-warning); }
    .kpi-ot { border-left: 4px solid var(--dash-info); }
    .kpi-ot .kpi-icon { background: var(--dash-info-light); color: var(--dash-info); }

    /* Location Cards */
    .location-card {
        background: var(--dash-card-bg);
        border: 1px solid var(--dash-border);
        border-radius: 16px;
        padding: 18px;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }

    .location-card:hover {
        transform: scale(1.02) translateY(-4px);
        border-color: var(--dash-primary);
        box-shadow: 0 16px 24px -10px rgba(79, 70, 229, 0.12);
    }

    .location-title {
        font-size: 16px;
        font-weight: 700;
        color: var(--dash-dark);
        margin-bottom: 12px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .location-metric {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 6px;
        font-size: 12px;
    }

    .location-metric-label {
        color: var(--dash-text-muted);
        font-weight: 500;
    }

    .location-metric-val {
        font-weight: 700;
        color: var(--dash-dark);
    }

    .location-progress {
        height: 6px;
        border-radius: 3px;
        background-color: #f1f5f9;
        overflow: hidden;
        margin-top: 10px;
    }

    .location-progress-bar {
        height: 100%;
        border-radius: 3px;
        transition: width 0.5s ease;
    }

    /* Charts and Containers */
    .dashboard-card {
        background: var(--dash-card-bg);
        border: 1px solid var(--dash-border);
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        margin-bottom: 24px;
        overflow: hidden;
    }

    .dashboard-card-header {
        padding: 16px 20px;
        border-bottom: 1px solid var(--dash-border);
        background-color: #fafbfc;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .dashboard-card-title {
        font-size: 15px;
        font-weight: 700;
        color: var(--dash-dark);
        margin: 0;
    }

    .dashboard-card-body {
        padding: 20px;
    }

    /* Tables Styles */
    .table-premium {
        font-size: 13px;
        vertical-align: middle !important;
    }

    .table-premium th {
        background-color: #f8fafc !important;
        color: var(--dash-text-muted) !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.5px;
        padding: 12px 16px !important;
        border-bottom: 1px solid var(--dash-border) !important;
    }

    .table-premium td {
        padding: 14px 16px !important;
        border-bottom: 1px solid #f1f5f9 !important;
    }

    .table-premium tbody tr:hover {
        background-color: #fcfdfe;
    }

    /* Badges & Status */
    .badge-premium {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .badge-present { background: var(--dash-success-light); color: var(--dash-success); }
    .badge-absent { background: var(--dash-danger-light); color: var(--dash-danger); }
    .badge-leave { background: var(--dash-info-light); color: var(--dash-info); }
    .badge-late { background: var(--dash-warning-light); color: #b45309; }
    .badge-checkedin { background: #f1f5f9; color: #475569; }
    .badge-early { background: #fff1f2; color: #be123c; }

    /* Actions and Buttons */
    .btn-premium-primary {
        background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
        color: #ffffff;
        font-weight: 600;
        font-size: 13px;
        padding: 8px 16px;
        border-radius: 10px;
        border: none;
        box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);
        transition: all 0.2s ease;
    }

    .btn-premium-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 12px -1px rgba(79, 70, 229, 0.3);
        color: #ffffff;
    }

    .btn-premium-outline {
        border: 1.5px solid var(--dash-border);
        background: #ffffff;
        color: #334155;
        font-weight: 600;
        font-size: 13px;
        padding: 8px 16px;
        border-radius: 10px;
        transition: all 0.2s ease;
    }

    .btn-premium-outline:hover {
        background-color: #f8fafc;
        border-color: #cbd5e1;
        color: #1e293b;
    }

    /* Drill-down Modal Styles */
    .modal-premium {
        border-radius: 20px;
        border: none;
    }
    
    .modal-premium .modal-header {
        border-bottom: 1px solid var(--dash-border);
        background: #f8fafc;
        border-radius: 20px 20px 0 0;
        padding: 20px 24px;
    }
    
    .modal-premium .modal-body {
        padding: 24px;
    }

    /* Chevron rotation for mobile filter collapse */
    [aria-expanded="true"] #collapseIcon {
        transform: rotate(180deg);
    }
    #collapseIcon {
        transition: transform 0.2s ease;
    }
    /* Modal Stats Card Styling */
    .av-stat-card-modal {
        background: #f9fafb;
        border: 1.5px solid var(--dash-border);
        transition: transform 0.15s, border-color 0.15s, box-shadow 0.15s, background-color 0.15s;
        cursor: pointer;
    }
    .av-stat-card-modal:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        background: #fff;
    }
    .av-stat-card-modal.active {
        background: #fff;
        box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    }
    #modalLocCardPresent:hover, #modalLocCardPresent.active {
        border-color: #22c55e !important;
    }
    #modalLocCardPresent.active {
        background: #f0fdf4;
    }
    #modalLocCardMan:hover, #modalLocCardMan.active {
        border-color: #3b82f6 !important;
    }
    #modalLocCardMan.active {
        background: #eff6ff;
    }
    #modalLocCardWomen:hover, #modalLocCardWomen.active {
        border-color: #ef4444 !important;
    }
    #modalLocCardWomen.active {
        background: #fef2f2;
    }
    #modalLocCardSwap:hover, #modalLocCardSwap.active {
        border-color: #f59e0b !important;
    }
    #modalLocCardSwap.active {
        background: #fffbeb;
    }
    .cursor-pointer {
        cursor: pointer;
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
</style>

<div class="container-fluid px-4 py-4 mt-2">
    <!-- Dashboard Intro Title -->
    <div class="row align-items-center mb-4">
        <div class="col-md-6">
            <h2 class="fw-bold text-dark mb-1">Labour Attendance Analysis</h2>
            <p class="text-muted small mb-0">Overview of turnout rates, absenteeism, shifts, and overtime tracking.</p>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <button id="btnPrintReport" class="btn btn-premium-outline me-2"><i class="ti ti-printer me-1"></i> Print Report</button>
            <button id="btnExportCSV" class="btn btn-premium-primary"><i class="ti ti-file-export me-1"></i> Export to CSV</button>
        </div>
    </div>

    <!-- Sticky Filters & Date Picker Bar -->
    <div class="sticky-control-bar rounded-4 mb-4">
        <!-- Toggle button for mobile -->
        <button class="btn btn-premium-outline d-md-none w-100 d-flex align-items-center justify-content-between py-2 border-0" type="button" data-bs-toggle="collapse" data-bs-target="#dashboardFilterCollapse" aria-expanded="false" aria-controls="dashboardFilterCollapse">
            <span class="fw-bold fs-13"><i class="ti ti-filter me-1 text-primary"></i> Filter Attendance Data</span>
            <i class="ti ti-chevron-down fs-16" id="collapseIcon"></i>
        </button>
        
        <div class="collapse d-md-block" id="dashboardFilterCollapse">
            <form id="dashboardFilterForm" class="row g-3 align-items-end mt-2 mt-md-0">
                <!-- Date range selection -->
                <div class="col-lg-3 col-md-6 col-sm-12">
                    <label class="filter-label">Date Range</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 rounded-start-10"><i class="ti ti-calendar text-muted"></i></span>
                        <input type="text" id="dashboardDaterange" class="form-control daterange-premium border-start-0 rounded-end-10 bg-white" readonly style="cursor: pointer;">
                    </div>
                </div>
                
                <!-- Location Filter -->
                <div class="col-lg-2 col-md-6 col-sm-6">
                    <label class="filter-label">Location</label>
                    <select id="filterLocation" class="form-select form-select-premium rounded-10">
                        <option value="all">All Sites</option>
                    </select>
                </div>
                
                <!-- QR Code Location Filter -->
                <div class="col-lg-3 col-md-6 col-sm-6">
                    <label class="filter-label">QR Code Location</label>
                    <select id="filterQRLocation" class="form-select form-select-premium rounded-10">
                        <option value="all">All QR Locations</option>
                        <option value="main-gate">Main-Gate</option>
                    </select>
                </div>
                
                <!-- Scan By Filter -->
                <div class="col-lg-3 col-md-6 col-sm-6">
                    <label class="filter-label">Scan By</label>
                    <select id="filterScanBy" class="form-select form-select-premium rounded-10">
                        <option value="all">All Operators</option>
                    </select>
                </div>

                <!-- Clear button -->
                <div class="col-lg-1 col-md-12 col-sm-12 text-end">
                    <button type="button" id="btnClearFilters" class="btn btn-premium-outline w-100 py-2 d-flex align-items-center justify-content-center" title="Reset Filters">
                        <i class="ti ti-rotate-clockwise fs-16"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- KPIs Metric Cards Row -->
    <div class="row g-3 mb-4">
        <!-- Total Workforce -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="kpi-card kpi-workforce">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="kpi-label">Total Workforce</div>
                        <div class="kpi-value" id="kpiTotalWorkforce">0</div>
                    </div>
                    <div class="kpi-icon"><i class="ti ti-users-group"></i></div>
                </div>
                <div class="kpi-change text-primary mt-auto"><i class="ti ti-activity me-1"></i>Active Registered</div>
            </div>
        </div>
        <!-- Present Today -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="kpi-card kpi-present">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="kpi-label">Present</div>
                        <div class="kpi-value" id="kpiPresentToday">0</div>
                    </div>
                    <div class="kpi-icon"><i class="ti ti-user-check"></i></div>
                </div>
                <div class="kpi-change text-success mt-auto" id="kpiPresentPct"><i class="ti ti-chart-pie me-1"></i>0% Turnout</div>
                <div class="kpi-breakup mt-1 pt-1 border-top" id="kpiPresentBreakup" style="font-size: 11px; font-weight: 600; color: #15803d; display: flex; justify-content: space-between; border-top: 1px dashed rgba(21, 128, 61, 0.25) !important;">
                    <span><i class="ti ti-qrcode me-1"></i>Punching: <strong id="kpiPresentPunching">0</strong></span>
                    <span><i class="ti ti-edit me-1"></i>Manual: <strong id="kpiPresentManual">0</strong></span>
                </div>
            </div>
        </div>
        <!-- Absent Today -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="kpi-card kpi-absent">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="kpi-label">Absent / Leave</div>
                        <div class="kpi-value" id="kpiAbsentToday">0</div>
                    </div>
                    <div class="kpi-icon"><i class="ti ti-user-minus"></i></div>
                </div>
                <div class="kpi-change text-danger mt-auto" id="kpiAbsentPct"><i class="ti ti-trending-up me-1"></i>0% Absenteeism</div>
            </div>
        </div>
        <!-- Late Today -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="kpi-card kpi-late">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="kpi-label">Late Arrivals</div>
                        <div class="kpi-value" id="kpiLateToday">0</div>
                    </div>
                    <div class="kpi-icon"><i class="ti ti-clock"></i></div>
                </div>
                <div class="kpi-change text-warning mt-auto"><i class="ti ti-alert-triangle me-1"></i>Past Shift Time</div>
            </div>
        </div>
        <!-- Checked In -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="kpi-card kpi-ot" style="border-left-color: var(--dash-info);">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="kpi-label">Active Checked-In</div>
                        <div class="kpi-value" id="kpiCheckedIn">0</div>
                    </div>
                    <div class="kpi-icon" style="background-color: var(--dash-info-light); color: var(--dash-info);"><i class="ti ti-door-enter"></i></div>
                </div>
                <div class="kpi-change text-info mt-auto"><i class="ti ti-run me-1"></i>Currently Working</div>
                <div class="kpi-breakup mt-1 pt-1 border-top" id="kpiCheckedInBreakup" style="font-size: 11px; font-weight: 600; color: #0284c7; display: flex; justify-content: space-between; border-top: 1px dashed rgba(2, 132, 199, 0.25) !important;">
                    <span><i class="ti ti-door-enter me-1"></i>Working: <strong id="kpiActiveWorking">0</strong></span>
                    <span><i class="ti ti-door-exit me-1"></i>Checked-Out: <strong id="kpiCheckedOut">0</strong></span>
                </div>
            </div>
        </div>
        <!-- Overtime Hours -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="kpi-card kpi-ot" style="border-left-color: #8b5cf6;">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="kpi-label">Period Overtime</div>
                        <div class="kpi-value" id="kpiTotalOT">0 Hrs</div>
                    </div>
                    <div class="kpi-icon" style="background-color: #f5f3ff; color: #8b5cf6;"><i class="ti ti-clock-bolt"></i></div>
                </div>
                <div class="kpi-change mt-auto" style="color: #8b5cf6;"><i class="ti ti-plus me-1"></i>OT Accumulated</div>
            </div>
        </div>
    </div>

    <!-- Location-wise Turnout Cards Section -->
    <div class="row mb-2">
        <div class="col-12">
            <h4 class="fw-bold mb-3 d-flex align-items-center">
                <span class="bg-primary text-white rounded-3 p-1 me-2" style="font-size: 14px;"><i class="ti ti-map-pin"></i></span>
                Location Turnout Report
            </h4>
        </div>
    </div>
    <div class="row g-3 mb-5" id="locationCardsContainer">
        <!-- Location cards dynamically injected -->
        <div class="col-12 text-center py-4" id="locationCardsLoader">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="text-muted mt-2 small">Loading location data...</p>
        </div>
    </div>

    <!-- Visual Analytics: Charts Section -->
    <div class="row mb-5">
        <!-- Attendance Trend Line Chart -->
        <div class="col-xl-6 col-lg-12">
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <h5 class="dashboard-card-title"><i class="ti ti-chart-line me-1 text-primary"></i> Attendance Trend (Present vs Absent)</h5>
                    <span class="badge bg-light text-muted fw-semibold">Period Trend</span>
                </div>
                <div class="dashboard-card-body">
                    <div id="chartTrend" style="min-height: 320px;"></div>
                </div>
            </div>
        </div>

        <!-- Status Distribution Donut Chart -->
        <div class="col-xl-3 col-lg-6">
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <h5 class="dashboard-card-title"><i class="ti ti-chart-donut me-1 text-success"></i> Status Distribution</h5>
                    <span class="badge bg-light text-muted fw-semibold" id="lblDistDate">Latest Date</span>
                </div>
                <div class="dashboard-card-body d-flex align-items-center justify-content-center" style="min-height: 340px;">
                    <div id="chartDistribution" style="width: 100%;"></div>
                </div>
            </div>
        </div>

        <!-- Turnout Comparisons Bar Chart -->
        <div class="col-xl-3 col-lg-6">
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <h5 class="dashboard-card-title"><i class="ti ti-chart-bar me-1 text-info"></i> Department/Site Turnout (%)</h5>
                    <span class="badge bg-light text-muted fw-semibold">Top Performers</span>
                </div>
                <div class="dashboard-card-body">
                    <div id="chartTurnout" style="min-height: 320px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Exception Report Table -->
    <div class="row g-4 mb-4">
        <div class="col-xl-5 col-lg-12">
            <div class="dashboard-card h-100">
                <div class="dashboard-card-header d-flex justify-content-between align-items-center">
                    <h5 class="dashboard-card-title text-danger"><i class="ti ti-alert-circle me-1"></i> Exception Report (Late & Anomalies)</h5>
                    <span class="badge bg-danger-subtle text-danger fw-semibold" id="lblExceptionsDate">Today</span>
                </div>
                <div class="dashboard-card-body p-0">
                    <div class="table-responsive">
                        <table id="tblExceptions" class="table table-premium table-hover mb-0 w-100">
                            <thead>
                                <tr>
                                    <th>Labour ID</th>
                                    <th>Name</th>
                                    <th>Shift</th>
                                    <th>Expected</th>
                                    <th>Punch-In</th>
                                    <th>Variance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Loading exceptions...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Shift & Overtime Summary Table -->
        <div class="col-xl-7 col-lg-12">
            <div class="dashboard-card h-100">
                <div class="dashboard-card-header d-flex justify-content-between align-items-center">
                    <h5 class="dashboard-card-title"><i class="ti ti-clock-bolt me-1 text-primary"></i> Working Hours & Overtime (OT) Summary</h5>
                    <span class="badge bg-primary-subtle text-primary fw-semibold">Payroll Integration</span>
                </div>
                <div class="dashboard-card-body p-0">
                    <div class="table-responsive">
                        <table id="tblOtSummary" class="table table-premium table-hover mb-0 w-100">
                            <thead>
                                <tr>
                                    <th>Labour Name</th>
                                    <th>Labour ID</th>
                                    <th>Regular Hrs</th>
                                    <th>Overtime Hrs</th>
                                    <th>Total Hrs</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Loading summaries...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Drill-down Location Details Modal -->
<div class="modal fade" id="locationDetailsModal" tabindex="-1" aria-labelledby="locationModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content modal-premium shadow-lg border-0">
            <div class="modal-header border-0 px-4 pt-4">
                <div>
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center" id="locationModalTitle">
                        <span class="bg-primary-subtle text-primary rounded-2 p-1 me-2" style="font-size: 16px;"><i class="ti ti-map-pin"></i></span>
                        Location Attendance Details
                    </h5>
                    <p class="text-muted small mb-0 mt-1" id="locationModalSub">Attendance log report details for the selected date.</p>
                </div>
                <div class="ms-auto d-flex align-items-center gap-2 me-3">
                    <button class="btn btn-sm btn-outline-success fw-bold d-flex align-items-center gap-1" onclick="exportModalLogs('excel')" style="border-radius: 8px;">
                        <i class="ti ti-file-excel"></i> Export Excel
                    </button>
                    <button class="btn btn-sm btn-outline-danger fw-bold d-flex align-items-center gap-1" onclick="exportModalLogs('pdf')" style="border-radius: 8px;">
                        <i class="ti ti-file-type-pdf"></i> Export PDF
                    </button>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 pb-4">
                <!-- Location Summary Mini-Cards (Filterable) -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="av-stat-card-modal p-3 rounded-4 border cursor-pointer active" id="modalLocCardPresent" onclick="filterModalLogs('present')">
                            <span class="small fw-semibold text-success d-block mb-1"><i class="ti ti-user-check me-1"></i> Total Present</span>
                            <h4 class="fw-bold mb-0 text-success" id="modalLocPresent">0</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="av-stat-card-modal p-3 rounded-4 border cursor-pointer" id="modalLocCardMan" onclick="filterModalLogs('man')">
                            <span class="small fw-semibold text-primary d-block mb-1"><i class="ti ti-man me-1"></i> Total Man</span>
                            <h4 class="fw-bold mb-0 text-primary" id="modalLocMan">0</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="av-stat-card-modal p-3 rounded-4 border cursor-pointer" id="modalLocCardWomen" onclick="filterModalLogs('women')">
                            <span class="small fw-semibold text-danger d-block mb-1"><i class="ti ti-woman me-1"></i> Total Women</span>
                            <h4 class="fw-bold mb-0 text-danger" id="modalLocWomen">0</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="av-stat-card-modal p-3 rounded-4 border cursor-pointer" id="modalLocCardSwap" onclick="filterModalLogs('swap')">
                            <span class="small fw-semibold d-block mb-1" style="color: #d97706;"><i class="ti ti-arrows-left-right me-1"></i> Swap Labour</span>
                            <h4 class="fw-bold mb-0" style="color: #d97706;" id="modalLocSwap">0</h4>
                        </div>
                    </div>
                </div>

                <!-- Swap Labour Scanner Location Wise Summary Report Bar in Modal -->
                <div id="modal-swap-location-summary-wrap" style="display:none;" class="p-3 border rounded-3 mb-3 bg-light" style="border-color: #fcd34d !important;">
                    <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge rounded-pill px-3 py-1 font-12 fw-bold" style="background-color: #fef3c7; color: #b45309; border: 1px solid #fcd34d;">
                                <i class="ti ti-arrows-left-right me-1"></i> Swap Labour Scanner Location Wise Summary Report
                            </span>
                            <span class="text-muted font-12 fw-semibold" id="modal-swap-loc-summary-title">Scanner Location: All Locations</span>
                        </div>
                        <span class="text-muted font-11"><i class="ti ti-info-circle me-1"></i>Click any badge to filter labours by scanner location below</span>
                    </div>
                    <div id="modal-swap-location-badges-container" class="d-flex flex-wrap gap-2 align-items-center pt-1">
                        <!-- Dynamic scanner location summary badges injected by JS -->
                    </div>
                </div>

                <!-- Datatable for Detailed Punch Logs -->
                <div class="table-responsive">
                    <table id="tblLocationDetails" class="table table-premium table-striped table-hover mb-0 w-100">
                        <thead>
                            <tr>
                                <th>Labour ID</th>
                                <th>Name</th>
                                <th>Mobile No</th>
                                <th>MN Check-In</th>
                                <th>MN Check-Out</th>
                                <th>DP Check-In</th>
                                <th>DP Check-Out</th>
                                <th>Working Hrs</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Logs dynamically loaded -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    // Read parameters from body attributes
    var key = $('body').data('key');
    var routurl = $('body').data('routurl');
    var companyid = $('body').data('companyid');
    var subcompanyid = $('body').data('subcompanyid');
    var projectid = $('body').data('projectid');
    var mdid = $('body').data('mdid');

    var dateFrom = moment().format('YYYY-MM-DD');
    var dateTo = moment().format('YYYY-MM-DD');
    
    // Global chart instances
    var chartTrendObj = null;
    var chartDistObj = null;
    var chartTurnoutObj = null;
    
    // Initialize date range picker
    $('#dashboardDaterange').daterangepicker({
        startDate: moment(),
        endDate: moment(),
        ranges: {
           'Today': [moment(), moment()],
           'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
           'Last 7 Days': [moment().subtract(6, 'days'), moment()],
           'Last 30 Days': [moment().subtract(29, 'days'), moment()],
           'This Month': [moment().startOf('month'), moment().endOf('month')],
           'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        },
        locale: {
            format: 'DD MMM YYYY'
        }
    }, function(start, end) {
        dateFrom = start.format('YYYY-MM-DD');
        dateTo = end.format('YYYY-MM-DD');
        loadDashboardData();
    });

    // Load filter configurations
    $.ajax({
        type: 'POST',
        url: routurl + 'modal/attendance_analysis_api.php?action=GetFilters',
        data: {
            projectid: projectid,
            companyid: companyid,
            subcompanyid: subcompanyid
        },
        success: function (res) {
            if (res.status === 'success') {
                // Populate locations
                var locHtml = '<option value="all">All Sites</option>';
                res.locations.forEach(function (l) {
                    locHtml += '<option value="' + l.LocationId + '">' + l.LocationName + '</option>';
                });
                $('#filterLocation').html(locHtml);

                // Populate Scan By Operators
                var opHtml = '<option value="all">All Operators</option>';
                if (res.operators && res.operators.length > 0) {
                    res.operators.forEach(function (op) {
                        opHtml += '<option value="' + op + '">' + op + '</option>';
                    });
                }
                $('#filterScanBy').html(opHtml);

                // Dynamically load QR Locations (Polyhouses) for the selected location
                loadQRLocations();
            }
        }
    });

    // Dynamically load QR Locations (Polyhouses) for the selected location
    function loadQRLocations(callback) {
        var locId = $('#filterLocation').val() || 'all';
        var sel = $('#filterQRLocation');
        
        // Reset options
        sel.empty().append('<option value="all">All QR Locations</option>');
        sel.append('<option value="main-gate">Main-Gate</option>');
        
        $.post(
            routurl + "modal/hrm_laboursalarymodal.php?action=GetPolyhousesByLocation",
            { projectid: projectid, locationid: locId },
            function (raw) {
                var data = [];
                try { data = JSON.parse(raw); } catch (e) {}
                if (data && data.length > 0) {
                    data.forEach(function (p) {
                        sel.append('<option value="' + p.polyhouseid + '">' + p.polyhousename + '</option>');
                    });
                }
                if (typeof callback === 'function') callback();
            }
        );
    }

    // Load main dashboard analytics
    function loadDashboardData() {
        var locationid = $('#filterLocation').val() || 'all';
        var qr_location = $('#filterQRLocation').val() || 'all';
        var scan_by = $('#filterScanBy').val() || 'all';

        var isSingleDay = (dateFrom === dateTo);
        var periodText = isSingleDay ? "Today" : "Latest Date";
        
        var dateLabel = isSingleDay ? moment(dateFrom).format('DD MMM YYYY') : moment(dateTo).format('DD MMM YYYY');
        $('#lblDistDate').text(dateLabel);
        $('#lblExceptionsDate').text(dateLabel);

        $.ajax({
            type: 'POST',
            url: routurl + 'modal/attendance_analysis_api.php?action=GetDashboardData',
            data: {
                projectid: projectid,
                companyid: companyid,
                subcompanyid: subcompanyid,
                date_from: dateFrom,
                date_to: dateTo,
                locationid: locationid,
                qr_location: qr_location,
                scan_by: scan_by
            },
            success: function (res) {
                if (res.status === 'success') {
                    // Update KPIs
                    $('#kpiTotalWorkforce').text(res.kpis.totalWorkforce);
                    $('#kpiPresentToday').text(res.kpis.present + ' (' + res.kpis.presentPct + '%)');
                    $('#kpiPresentPct').html('<i class="ti ti-chart-pie me-1"></i>' + res.kpis.presentPct + '% Turnout');
                    $('#kpiPresentPunching').text(res.kpis.presentPunching || 0);
                    $('#kpiPresentManual').text(res.kpis.presentManual || 0);
                    $('#kpiAbsentToday').text((res.kpis.absent + res.kpis.leave) + ' (' + res.kpis.absentPct + '%)');
                    $('#kpiAbsentPct').html('<i class="ti ti-trending-up me-1"></i>' + res.kpis.absentPct + '% Absenteeism');
                    $('#kpiLateToday').text(res.kpis.late);
                    $('#kpiCheckedIn').text(res.kpis.checkedIn);
                    $('#kpiActiveWorking').text(res.kpis.checkedIn || 0);
                    $('#kpiCheckedOut').text(res.kpis.checkedOut || 0);
                    $('#kpiTotalOT').text(parseFloat(res.kpis.totalOTHours).toFixed(1) + ' Hrs');

                    // Render Location Cards
                    renderLocationCards(res.locationCards);

                    // Render Charts
                    renderCharts(res.charts);

                    // Render Exception Table
                    renderExceptionsTable(res.exceptions);

                    // Render OT Summary Table
                    renderOtSummaryTable(res.otSummary);
                }
            }
        });
    }

    // Render Turnout Cards for Each Location
    function renderLocationCards(cards) {
        var container = $('#locationCardsContainer');
        container.empty();

        if (cards.length === 0) {
            container.html('<div class="col-12"><div class="alert alert-warning py-3">No location turnout reports found for the selected filters.</div></div>');
            return;
        }

        cards.forEach(function (c) {
            var color = '#10b981'; // Green
            if (c.turnoutRate < 50) color = '#ef4444'; // Red
            else if (c.turnoutRate < 80) color = '#f59e0b'; // Amber

            var workingCount = c.workingCount || 0;
            var workingMale = c.workingCountMale || 0;
            var workingFemale = c.workingCountFemale || 0;

            var otherCount = c.otherLocationCount || 0;
            var otherMale = c.otherLocationMale || 0;
            var otherFemale = c.otherLocationFemale || 0;

            var attCount = (typeof c.attendanceCount !== 'undefined') ? c.attendanceCount : (workingCount + otherCount);
            var attMale = (typeof c.attendanceCountMale !== 'undefined') ? c.attendanceCountMale : (workingMale + otherMale);
            var attFemale = (typeof c.attendanceCountFemale !== 'undefined') ? c.attendanceCountFemale : (workingFemale + otherFemale);

            var cardHtml = `
                <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12">
                    <div class="location-card" data-locationid="${c.locationid}">
                        <div class="location-title">
                            <span>${c.locationName}</span>
                            <span class="badge fs-11" style="background-color: ${color}20; color: ${color};">${c.turnoutRate}%</span>
                        </div>
                        <div class="location-metric">
                            <span class="location-metric-label">Workforce Size</span>
                            <span class="location-metric-val">${c.totalWorkforce} <span class="text-muted small fw-normal">(${c.totalWorkforceMale} Man | ${c.totalWorkforceFemale} Women)</span></span>
                        </div>
                        <div class="location-metric">
                            <span class="location-metric-label">Present Labours</span>
                            <span class="location-metric-val text-success">${c.presentCount} <span class="text-muted small fw-normal">(${c.presentCountMale} Man | ${c.presentCountFemale} Women)</span></span>
                        </div>
                        <div class="location-metric">
                            <span class="location-metric-label">Working Labours</span>
                            <span class="location-metric-val text-primary">${workingCount} <span class="text-muted small fw-normal">(${workingMale} Man | ${workingFemale} Women)</span></span>
                        </div>
                        <div class="location-metric">
                            <span class="location-metric-label">Scanned at Other Location</span>
                            <span class="location-metric-val text-info">${otherCount} <span class="text-muted small fw-normal">(${otherMale} Man | ${otherFemale} Women)</span></span>
                        </div>
                        <div class="location-metric">
                            <span class="location-metric-label">Attendance Count</span>
                            <span class="location-metric-val" style="color: #7c3aed; font-weight: 600;">${attCount} <span class="text-muted small fw-normal">(${attMale} Man | ${attFemale} Women)</span></span>
                        </div>
                        <div class="location-metric">
                            <span class="location-metric-label">Late Arrivals</span>
                            <span class="location-metric-val text-warning">${c.lateCount} <span class="text-muted small fw-normal">(${c.lateCountMale} Man | ${c.lateCountFemale} Women)</span></span>
                        </div>
                        <div class="location-metric">
                            <span class="location-metric-label">Absent / Leave</span>
                            <span class="location-metric-val text-danger">${c.absentCount} <span class="text-muted small fw-normal">(${c.absentCountMale} Man | ${c.absentCountFemale} Women)</span></span>
                        </div>
                        <div class="location-progress">
                            <div class="location-progress-bar" style="width: ${c.turnoutRate}%; background-color: ${color};"></div>
                        </div>
                    </div>
                </div>
            `;
            container.append(cardHtml);
        });

        // Add card click listener for drill-down modal
        $('.location-card').on('click', function () {
            var locid = $(this).data('locationid');
            openLocationDrillDown(locid);
        });
    }

    // Render Charts using ApexCharts
    function renderCharts(chartData) {
        // 1. Trend Chart
        var trendOptions = {
            series: [{
                name: 'Present Count',
                data: chartData.trend.present
            }, {
                name: 'Absent Count',
                data: chartData.trend.absent
            }],
            chart: {
                type: 'area',
                height: 300,
                toolbar: { show: false },
                zoom: { enabled: false }
            },
            colors: ['#4f46e5', '#ef4444'],
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2 },
            xaxis: {
                categories: chartData.trend.dates
            },
            tooltip: {
                x: { format: 'dd MMM' }
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.35,
                    opacityTo: 0.05,
                    stops: [0, 90, 100]
                }
            }
        };

        if (chartTrendObj) chartTrendObj.destroy();
        chartTrendObj = new ApexCharts(document.querySelector("#chartTrend"), trendOptions);
        chartTrendObj.render();

        // 2. Status Distribution Chart
        var distOptions = {
            series: chartData.distribution.values,
            chart: {
                type: 'donut',
                height: 320
            },
            labels: chartData.distribution.labels,
            colors: ['#10b981', '#f59e0b', '#be123c', '#475569', '#ef4444', '#06b6d4'],
            legend: {
                position: 'bottom',
                fontSize: '12px',
                fontFamily: 'Plus Jakarta Sans',
                markers: { radius: 12 }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '70%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total Present',
                                formatter: function (w) {
                                    // Total present is On Time + Late + Checked-In
                                    var vals = w.globals.seriesTotals;
                                    return vals[0] + vals[1] + vals[3];
                                }
                            }
                        }
                    }
                }
            },
            dataLabels: { enabled: false }
        };

        if (chartDistObj) chartDistObj.destroy();
        chartDistObj = new ApexCharts(document.querySelector("#chartDistribution"), distOptions);
        chartDistObj.render();

        // 3. Department Turnout Chart
        var turnoutOptions = {
            series: [{
                name: 'Turnout Rate (%)',
                data: chartData.turnout.values
            }],
            chart: {
                type: 'bar',
                height: 300,
                toolbar: { show: false }
            },
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    horizontal: true,
                    barHeight: '55%',
                    distributed: true
                }
            },
            colors: ['#06b6d4', '#3b82f6', '#10b981', '#6366f1', '#f59e0b', '#ec4899'],
            dataLabels: {
                enabled: true,
                formatter: function (val) {
                    return val + "%";
                },
                style: { fontSize: '10px' }
            },
            xaxis: {
                categories: chartData.turnout.categories,
                labels: {
                    formatter: function (val) { return val + "%"; }
                }
            },
            legend: { show: false }
        };

        if (chartTurnoutObj) chartTurnoutObj.destroy();
        chartTurnoutObj = new ApexCharts(document.querySelector("#chartTurnout"), turnoutOptions);
        chartTurnoutObj.render();
    }

    // Render Exceptions Table
    function renderExceptionsTable(exceptions) {
        var tbody = $('#tblExceptions tbody');
        tbody.empty();

        if (exceptions.length === 0) {
            tbody.html('<tr><td colspan="6" class="text-center py-4 text-muted"><i class="ti ti-circle-check text-success fs-20 d-block mb-1"></i> No exceptions recorded today!</td></tr>');
            return;
        }

        exceptions.forEach(function (e) {
            var badgeCls = 'badge-late';
            var statusLabel = 'Late';
            if (e.status == 'Early Markout') { badgeCls = 'badge-early'; statusLabel = 'Early Mark'; }
            else if (e.status == 'Checked-In') { badgeCls = 'badge-checkedin'; statusLabel = 'Active'; }
            
            var varText = e.variance > 0 ? e.variance + ' mins' : '—';

            var tr = `
                <tr>
                    <td class="fw-bold">${e.labourid}</td>
                    <td>${e.name}</td>
                    <td class="text-muted">${e.shift}</td>
                    <td class="text-muted">${e.expected_time}</td>
                    <td>${e.punch_in_time}</td>
                    <td><span class="badge-premium ${badgeCls}">${statusLabel} (${varText})</span></td>
                </tr>
            `;
            tbody.append(tr);
        });
    }

    // Render Overtime & Working Hours Summary Table
    function renderOtSummaryTable(otList) {
        var tbody = $('#tblOtSummary tbody');
        tbody.empty();

        if (otList.length === 0) {
            tbody.html('<tr><td colspan="6" class="text-center py-4 text-muted">No summaries found for this range.</td></tr>');
            return;
        }

        otList.forEach(function (ot) {
            var actionBtn = '—';
            if (ot.has_pending && ot.pending_ot_id) {
                actionBtn = `<button class="btn btn-sm btn-success btn-approve-ot px-3 py-1 fw-bold" style="border-radius: 8px;" data-id="${ot.pending_ot_id}">Approve OT</button>`;
            } else if (ot.ot_hours > 0) {
                actionBtn = `<span class="text-success small fw-bold"><i class="ti ti-check-circle"></i> Approved</span>`;
            }

            var tr = `
                <tr>
                    <td class="fw-semibold">${ot.name}</td>
                    <td class="text-muted">${ot.labourid}</td>
                    <td>${parseFloat(ot.regular_hours).toFixed(1)} Hrs</td>
                    <td class="fw-bold text-primary">${parseFloat(ot.ot_hours).toFixed(1)} Hrs</td>
                    <td>${parseFloat(ot.total_hours).toFixed(1)} Hrs</td>
                    <td>${actionBtn}</td>
                </tr>
            `;
            tbody.append(tr);
        });

        // OT Approval Button Event Listener
        $('.btn-approve-ot').off('click').on('click', function () {
            var attid = $(this).data('id');
            var btn = $(this);
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

            $.ajax({
                type: 'POST',
                url: routurl + 'modal/attendance_analysis_api.php?action=ApproveOT',
                data: {
                    attendanceid: attid,
                    status: 'approved'
                },
                success: function (res) {
                    if (res.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Approved!',
                            text: 'Overtime hours approved successfully.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        loadDashboardData();
                    } else {
                        btn.prop('disabled', false).html('Approve OT');
                        Swal.fire('Error', res.message || 'Verification failed.', 'error');
                    }
                },
                error: function () {
                    btn.prop('disabled', false).html('Approve OT');
                    Swal.fire('Error', 'API server error.', 'error');
                }
            });
        });
    }

    var modalActiveLogs = [];
    var modalFilteredLogs = [];
    var currentModalFilter = 'present';

    function updateModalCounts() {
        var presentCount = 0;
        var manCount = 0;
        var womenCount = 0;
        var swapCount = 0;

        modalActiveLogs.forEach(function (log) {
            var isPresent = (log.status === 'Present' || log.status === 'Late' || log.status === 'Checked-In' || log.status === 'Early Markout');
            if (isPresent) {
                presentCount++;
                
                var gender = (log.gender || '').toLowerCase().trim();
                if (gender === 'male' || gender === 'm') {
                    manCount++;
                } else if (gender === 'female' || gender === 'f' || gender === 'w' || gender === 'women') {
                    womenCount++;
                }
                
                if (log.is_other_location) {
                    swapCount++;
                }
            }
        });

        $('#modalLocPresent').text(presentCount);
        $('#modalLocMan').text(manCount);
        $('#modalLocWomen').text(womenCount);
        $('#modalLocSwap').text(swapCount);
    }

    var modalSwapScannerFilter = 'all';

    function renderModalTable(filterType) {
        currentModalFilter = filterType;
        $('.av-stat-card-modal').removeClass('active');
        
        var filtered = [];
        if (filterType === 'present') {
            $('#modal-swap-location-summary-wrap').hide();
            modalSwapScannerFilter = 'all';
            $('#modalLocCardPresent').addClass('active');
            filtered = modalActiveLogs.filter(function(log) {
                return (log.status === 'Present' || log.status === 'Late' || log.status === 'Checked-In' || log.status === 'Early Markout');
            });
        } else if (filterType === 'man') {
            $('#modal-swap-location-summary-wrap').hide();
            modalSwapScannerFilter = 'all';
            $('#modalLocCardMan').addClass('active');
            filtered = modalActiveLogs.filter(function(log) {
                var isPresent = (log.status === 'Present' || log.status === 'Late' || log.status === 'Checked-In' || log.status === 'Early Markout');
                var gender = (log.gender || '').toLowerCase().trim();
                var isMale = (gender === 'male' || gender === 'm');
                return isPresent && isMale;
            });
        } else if (filterType === 'women') {
            $('#modal-swap-location-summary-wrap').hide();
            modalSwapScannerFilter = 'all';
            $('#modalLocCardWomen').addClass('active');
            filtered = modalActiveLogs.filter(function(log) {
                var isPresent = (log.status === 'Present' || log.status === 'Late' || log.status === 'Checked-In' || log.status === 'Early Markout');
                var gender = (log.gender || '').toLowerCase().trim();
                var isFemale = (gender === 'female' || gender === 'f' || gender === 'w' || gender === 'women');
                return isPresent && isFemale;
            });
        } else if (filterType === 'swap') {
            $('#modalLocCardSwap').addClass('active');
            var allSwapLogs = modalActiveLogs.filter(function(log) {
                var isPresent = (log.status === 'Present' || log.status === 'Late' || log.status === 'Checked-In' || log.status === 'Early Markout');
                return isPresent && log.is_other_location;
            });

            renderModalSwapLocationSummary(allSwapLogs);
            $('#modal-swap-location-summary-wrap').show();

            if (modalSwapScannerFilter !== 'all') {
                filtered = allSwapLogs.filter(function(log) {
                    return (log.scan_location_name || 'Unknown') === modalSwapScannerFilter;
                });
            } else {
                filtered = allSwapLogs;
            }
        }

        modalFilteredLogs = filtered;

        var tbody = $('#tblLocationDetails tbody');
        tbody.empty();

        if (filtered.length === 0) {
            tbody.html('<tr><td colspan="9" class="text-center py-4 text-muted">No worker attendance records found matching this filter.</td></tr>');
            return;
        }

        filtered.forEach(function (l) {
            var statusCls = 'badge-absent';
            if (l.status === 'Present') statusCls = 'badge-premium badge-present';
            else if (l.status === 'Late') statusCls = 'badge-premium badge-late';
            else if (l.status === 'Checked-In') statusCls = 'badge-premium badge-checkedin';
            else if (l.status === 'Leave') statusCls = 'badge-premium badge-leave';
            else if (l.status === 'Early Markout') statusCls = 'badge-premium badge-early';
            else statusCls = 'badge-premium badge-absent';

            var otherLocBadge = '';
            if (l.is_other_location && l.scan_location_name) {
                otherLocBadge = `<br><span class="badge bg-info-subtle text-info mt-1" style="font-size: 10px; font-weight: 600;" title="Assigned location is different from scan location">Scanned: ${l.scan_location_name}</span>`;
            }

            var dateBadge = (l.att_date && dateFrom !== dateTo) ? `<br><span class="badge bg-secondary-subtle text-secondary mt-1" style="font-size: 10px; font-weight: 600;"><i class="ti ti-calendar me-1"></i>${moment(l.att_date).format('DD MMM YYYY')}</span>` : '';

            var tr = `
                <tr>
                    <td class="fw-bold">${l.labourid}</td>
                    <td class="fw-semibold">${l.name}${dateBadge}</td>
                    <td>${l.mobile}</td>
                    <td>${l.checkin_mn}</td>
                    <td>${l.checkout_mn}</td>
                    <td>${l.checkin_dp}</td>
                    <td>${l.checkout_dp}</td>
                    <td>${l.working_hours}</td>
                    <td><span class="${statusCls}">${l.status}</span>${otherLocBadge}</td>
                </tr>
            `;
            tbody.append(tr);
        });
    }

    function htmlEscape(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function renderModalSwapLocationSummary(swapLogs) {
        var selLocName = (modalSwapScannerFilter === 'all') ? 'All Locations' : modalSwapScannerFilter;
        $('#modal-swap-loc-summary-title').text('Scanner Location: ' + selLocName);

        var scannerLocCounts = {};
        var totalSwapCount = swapLogs.length;

        swapLogs.forEach(function(l) {
            var scanLoc = l.scan_location_name || 'Unknown';
            scannerLocCounts[scanLoc] = (scannerLocCounts[scanLoc] || 0) + 1;
        });

        var badgesHtml = '';
        var allActive = (modalSwapScannerFilter === 'all') ? 'active' : '';
        badgesHtml += '<button type="button" class="av-summary-btn ' + allActive + '" onclick="filterModalSwapScannerLoc(\'all\')">' +
            '<i class="ti ti-layout-grid"></i> All Scanner Locations <span class="badge-num">' + totalSwapCount + '</span></button>';

        Object.keys(scannerLocCounts).forEach(function(locName) {
            var count = scannerLocCounts[locName];
            var isSelected = (modalSwapScannerFilter === locName);
            var activeClass = isSelected ? 'active' : '';
            
            badgesHtml += '<button type="button" class="av-summary-btn ' + activeClass + '" onclick="filterModalSwapScannerLoc(\'' + htmlEscape(locName) + '\')">' +
                '<i class="ti ti-map-pin"></i> ' + htmlEscape(locName) + ' <span class="badge-num">' + count + '</span></button>';
        });

        $('#modal-swap-location-badges-container').html(badgesHtml);
    }

    window.filterModalSwapScannerLoc = function(locName) {
        modalSwapScannerFilter = locName;
        renderModalTable('swap');
    };

    window.filterModalLogs = function(filterType) {
        renderModalTable(filterType);
    };

    function exportToExcel(filename, rows) {
        var csv = [];
        // Headers
        csv.push(["Labour ID", "Name", "Mobile No", "MN Check-In", "MN Check-Out", "DP Check-In", "DP Check-Out", "Working Hrs", "Status", "Scan Location"].join(","));
        
        rows.forEach(function(l) {
            var scanLoc = l.is_other_location ? (l.scan_location_name || '') : '';
            var row = [
                l.labourid,
                `"${l.name.replace(/"/g, '""')}"`,
                l.mobile,
                l.checkin_mn,
                l.checkout_mn,
                l.checkin_dp,
                l.checkout_dp,
                l.working_hours,
                l.status,
                `"${scanLoc.replace(/"/g, '""')}"`
            ];
            csv.push(row.join(","));
        });
        
        var csvString = csv.join("\n");
        var blob = new Blob(["\uFEFF" + csvString], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement("a");
        var url = URL.createObjectURL(blob);
        link.setAttribute("href", url);
        link.setAttribute("download", filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function exportToPDF(title, subtitle, rows) {
        var win = window.open("", "_blank");
        var html = `
        <html>
        <head>
            <title>${title}</title>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
            <style>
                body { font-family: 'Plus Jakarta Sans', sans-serif; padding: 30px; }
                .header-area { border-bottom: 2px solid #333; margin-bottom: 20px; padding-bottom: 10px; }
                table { font-size: 12px; }
                th { background-color: #f3f4f6 !important; }
                @media print {
                    @page { margin: 1.5cm; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="header-area d-flex justify-content-between align-items-center">
               <div>
                   <h3 class="fw-bold m-0">${title}</h3>
                   <p class="text-muted m-0 small">${subtitle}</p>
               </div>
               <button class="btn btn-sm btn-primary no-print" onclick="window.print()">Print / Save PDF</button>
            </div>
            <table class="table table-bordered table-striped mt-3">
                <thead>
                    <tr>
                        <th>Labour ID</th>
                        <th>Name</th>
                        <th>Mobile No</th>
                        <th>MN Check-In</th>
                        <th>MN Check-Out</th>
                        <th>DP Check-In</th>
                        <th>DP Check-Out</th>
                        <th>Working Hrs</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        rows.forEach(function(l) {
            var scanLoc = l.is_other_location ? ` (Scanned: ${l.scan_location_name})` : '';
            html += `
                <tr>
                    <td class="fw-bold">${l.labourid}</td>
                    <td>${l.name}</td>
                    <td>${l.mobile}</td>
                    <td>${l.checkin_mn}</td>
                    <td>${l.checkout_mn}</td>
                    <td>${l.checkin_dp}</td>
                    <td>${l.checkout_dp}</td>
                    <td>${l.working_hours}</td>
                    <td>${l.status}${scanLoc}</td>
                </tr>
            `;
        });
        
        html += `
                </tbody>
            </table>
            <script>
                window.onload = function() {
                    setTimeout(function() {
                        window.print();
                    }, 500);
                }
            <\/script>
        </body>
        </html>
        `;
        
        win.document.write(html);
        win.document.close();
    }

    window.exportModalLogs = function(format) {
        var locName = $('#locationModalTitle').text().replace('- Attendance Details', '').trim();
        var dateStr = $('#locationModalSub').text().replace('Detailed attendance record log for ', '').trim();
        var filename = `${locName}_Attendance_${dateStr.replace(/ /g, '_')}`;
        
        if (format === 'excel') {
            exportToExcel(filename + '.csv', modalFilteredLogs);
        } else if (format === 'pdf') {
            var title = `${locName} - Attendance Details`;
            var subtitle = `Detailed attendance record log for ${dateStr} (${currentModalFilter.toUpperCase()} filter active)`;
            exportToPDF(title, subtitle, modalFilteredLogs);
        }
    };

    // Open Drill-down Location details Modal
    function openLocationDrillDown(locationid) {
        var qr_location = $('#filterQRLocation').val() || 'all';
        var scan_by = $('#filterScanBy').val() || 'all';

        $('#tblLocationDetails tbody').html('<tr><td colspan="9" class="text-center py-4"><div class="spinner-border text-primary spinner-border-sm" role="status"></div> Loading location records...</td></tr>');
        
        // Show modal
        var myModal = new bootstrap.Modal(document.getElementById('locationDetailsModal'));
        myModal.show();

        $.ajax({
            type: 'POST',
            url: routurl + 'modal/attendance_analysis_api.php?action=GetLocationDetails',
            data: {
                projectid: projectid,
                companyid: companyid,
                subcompanyid: subcompanyid,
                date_from: dateFrom,
                date_to: dateTo,
                qr_location: qr_location,
                scan_by: scan_by,
                target_locationid: locationid
            },
            success: function (res) {
                if (res.status === 'success') {
                    $('#locationModalTitle').html(`
                        <span class="bg-primary-subtle text-primary rounded-2 p-1 me-2" style="font-size: 16px;"><i class="ti ti-map-pin"></i></span>
                        ${res.locationName} - Attendance Details
                    `);
                    
                    var dateStr = (dateFrom === dateTo) ? moment(dateFrom).format('DD MMM YYYY') : (moment(dateFrom).format('DD MMM') + ' to ' + moment(dateTo).format('DD MMM YYYY'));
                    $('#locationModalSub').text("Detailed attendance record log for " + dateStr);

                    // Populate summary cards and render default table grid view
                    modalActiveLogs = res.logs;
                    updateModalCounts();
                    renderModalTable('present'); // Default present labour data show in grid
                }
            }
        });
    }

    // Trigger location change: reload QR locations, then load dashboard
    $('#filterLocation').on('change', function () {
        loadQRLocations(function() {
            loadDashboardData();
        });
    });

    // Trigger other filters change
    $('#filterQRLocation, #filterScanBy').on('change', function () {
        loadDashboardData();
    });

    // Clear Filters
    $('#btnClearFilters').on('click', function () {
        $('#filterLocation').val('all');
        loadQRLocations(function() {
            $('#filterQRLocation').val('all');
            $('#filterScanBy').val('all');
            $('#dashboardDaterange').data('daterangepicker').setStartDate(moment());
            $('#dashboardDaterange').data('daterangepicker').setEndDate(moment());
            dateFrom = moment().format('YYYY-MM-DD');
            dateTo = moment().format('YYYY-MM-DD');
            loadDashboardData();
        });
    });

    // Print report action
    $('#btnPrintReport').on('click', function () {
        window.print();
    });

    // Export to CSV Action
    $('#btnExportCSV').on('click', function () {
        var locationid = $('#filterLocation').val() || 'all';
        var qr_location = $('#filterQRLocation').val() || 'all';
        var scan_by = $('#filterScanBy').val() || 'all';

        $.ajax({
            type: 'POST',
            url: routurl + 'modal/attendance_analysis_api.php?action=GetDashboardData',
            data: {
                projectid: projectid,
                companyid: companyid,
                subcompanyid: subcompanyid,
                date_from: dateFrom,
                date_to: dateTo,
                locationid: locationid,
                qr_location: qr_location,
                scan_by: scan_by
            },
            success: function (res) {
                if (res.status === 'success') {
                    var csvContent = "data:text/csv;charset=utf-8,";
                    csvContent += "Labour Overtime & Work Hours Summary Report\n";
                    csvContent += "Period: " + dateFrom + " to " + dateTo + "\n\n";
                    csvContent += "Labour Name,Labour ID,Regular Hours,Overtime Hours,Total Hours\n";

                    res.otSummary.forEach(function (row) {
                        csvContent += `"${row.name}","${row.labourid}",${row.regular_hours.toFixed(2)},${row.ot_hours.toFixed(2)},${row.total_hours.toFixed(2)}\n`;
                    });

                    var encodedUri = encodeURI(csvContent);
                    var link = document.createElement("a");
                    link.setAttribute("href", encodedUri);
                    link.setAttribute("download", "Labour_Attendance_Hours_Summary_" + dateFrom + "_to_" + dateTo + ".csv");
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }
            }
        });
    });

    // Initial load
    loadDashboardData();
});
</script>

<?php
require('footer.php');
?>
