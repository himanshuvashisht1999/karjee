<?php
if(isset($_GET['vw']) && $_GET['vw'] == 1){
    $key = $_GET['k'] ?? '';
    $rooturl = "";
}else{
    require('header.php');
}
?>
<?php if (isset($_GET['vw']) && $_GET['vw'] == 1): ?>
<!-- Standalone / Iframe assets fallback -->
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/plugins/tabler-icons/tabler-icons.min.css">
<link rel="stylesheet" href="assets/plugins/select2/css/select2.min.css">
<link rel="stylesheet" href="assets/plugins/fontawesome/css/all.min.css">
<style>
    body {
        background-color: #f8fafc;
        padding: 6px;
    }
    #employeesalarycontainer {
        padding: 0 !important;
        margin: 0 !important;
        max-width: 100% !important;
    }
</style>
<?php endif; ?>

<style>
    /* =========================================================
       GLOBAL & COMPACT CONTAINER STYLING
       ========================================================= */
    #employeesalarycontainer {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        color: #1e293b;
    }

    /* Page Header Card: Compact, Light Background & Dark Text */
    .leave-header-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
        padding: 10px 18px;
        margin-bottom: 12px;
        color: #0f172a;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
    }
    .leave-header-title {
        font-size: 1.05rem;
        font-weight: 700;
        letter-spacing: -0.2px;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
        color: #0f172a !important;
    }
    .leave-header-title i {
        color: #2563eb;
    }

    /* Navigation Tabs Container: Compact */
    .leave-mode-tabs-container {
        background: #ffffff;
        padding: 4px;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
        margin-bottom: 12px;
    }
    .leave-modern-tabs {
        display: flex;
        gap: 6px;
        border: none;
        margin: 0;
        padding: 0;
    }
    .leave-modern-tabs .nav-item {
        flex: 1;
    }
    .leave-modern-tabs .nav-link {
        width: 100%;
        border-radius: 8px;
        font-weight: 700;
        font-size: 12.5px;
        padding: 7px 14px;
        color: #475569;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: all 0.2s ease-in-out;
        cursor: pointer;
    }
    .leave-modern-tabs .nav-link:hover {
        color: #0f172a;
        background: #f1f5f9;
        border-color: #cbd5e1;
    }
    .leave-modern-tabs .nav-link.active {
        color: #ffffff !important;
        background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%) !important;
        border-color: #2563eb !important;
        box-shadow: 0 3px 10px rgba(37, 99, 235, 0.25) !important;
    }
    .leave-modern-tabs .nav-link.active i {
        color: #ffffff !important;
    }
    .leave-modern-tabs .nav-link.active .badge {
        background: rgba(255, 255, 255, 0.28) !important;
        color: #ffffff !important;
    }

    /* Filter Card: Compact */
    .filter-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
        margin-bottom: 12px;
        overflow: hidden;
    }
    .filter-header-bar {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 7px 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 8px;
    }
    .filter-label {
        font-weight: 600;
        color: #475569;
        font-size: 11px;
        margin-bottom: 3px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
        gap: 3px;
    }
    .form-control, .form-select {
        border-radius: 7px !important;
        border: 1px solid #cbd5e1 !important;
        padding: 0 8px !important;
        font-size: 12.5px !important;
        color: #1e293b !important;
        background-color: #f8fafc !important;
        height: 33px !important;
        line-height: 31px !important;
        transition: all 0.2s ease-in-out;
    }
    .form-control:focus, .form-select:focus {
        border-color: #2563eb !important;
        background-color: #ffffff !important;
        box-shadow: 0 0 0 2.5px rgba(37, 99, 235, 0.12) !important;
        outline: none;
    }

    /* Date Mode Switcher Buttons: Compact & Distinct Active Background */
    .date-mode-btn-group {
        background: #f1f5f9;
        padding: 2px;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        display: flex;
        gap: 3px;
        width: 100%;
        height: 33px;
    }
    .date-mode-btn {
        border: none !important;
        border-radius: 6px !important;
        font-size: 11.5px !important;
        font-weight: 600 !important;
        padding: 4px 8px !important;
        height: 27px !important;
        color: #475569 !important;
        background: transparent !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        transition: all 0.2s ease-in-out;
        flex: 1;
        cursor: pointer;
    }
    .date-mode-btn:hover {
        color: #0f172a !important;
        background: rgba(255, 255, 255, 0.6) !important;
    }
    .date-mode-btn-group .btn-check:checked + .date-mode-btn {
        background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%) !important;
        color: #ffffff !important;
        box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3) !important;
    }
    .date-mode-btn-group .btn-check:checked + .date-mode-btn i {
        color: #ffffff !important;
    }

    /* Select2 Compact Tweaks */
    .select2-container--default .select2-selection--single {
        height: 33px !important;
        border-radius: 7px !important;
        border: 1px solid #cbd5e1 !important;
        background-color: #f8fafc !important;
        display: flex !important;
        align-items: center !important;
    }
    .select2-container--default .select2-selection--single:focus,
    .select2-container--default.select2-container--open .select2-selection--single {
        border-color: #2563eb !important;
        background-color: #ffffff !important;
        box-shadow: 0 0 0 2.5px rgba(37, 99, 235, 0.12) !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 31px !important;
        color: #1e293b !important;
        font-size: 12.5px !important;
        padding-left: 8px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 31px !important;
        right: 8px !important;
    }
    .select2-dropdown {
        border-radius: 8px !important;
        border: 1px solid #cbd5e1 !important;
        box-shadow: 0 6px 18px rgba(0,0,0,0.08) !important;
        z-index: 9999 !important;
    }

    /* =========================================================
       SUMMARY KPI CARDS & LEAVE TYPE CARDS: COMPACT & ANIMATED
       ========================================================= */
    /* Top Overall KPI Mini Cards */
    .kpi-mini-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 6px 10px;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease-in-out;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.02);
        height: 100%;
    }
    .kpi-mini-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.05);
    }
    .kpi-icon {
        width: 28px;
        height: 28px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        flex-shrink: 0;
    }
    .kpi-total .kpi-icon { background: #eff6ff; color: #2563eb; }
    .kpi-days .kpi-icon { background: #f0fdf4; color: #16a34a; }
    .kpi-emp .kpi-icon { background: #f5f3ff; color: #7c3aed; }
    .kpi-approved .kpi-icon { background: #ecfdf5; color: #059669; }
    .kpi-pending .kpi-icon { background: #fffbeb; color: #d97706; }
    .kpi-rejected .kpi-icon { background: #fff1f2; color: #e11d48; }

    .kpi-data {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }
    .kpi-value {
        font-size: 14.5px;
        font-weight: 700;
        line-height: 1.15;
        color: #0f172a;
    }
    .kpi-label {
        font-size: 9.5px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-top: 1px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Keyframe animation for active card border */
    @keyframes activeBorderPulse {
        0% {
            border-color: #2563eb;
            box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.45), 0 3px 10px rgba(37, 99, 235, 0.18);
        }
        50% {
            border-color: #1d4ed8;
            box-shadow: 0 0 0 3.5px rgba(37, 99, 235, 0.22), 0 5px 14px rgba(37, 99, 235, 0.28);
        }
        100% {
            border-color: #2563eb;
            box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.45), 0 3px 10px rgba(37, 99, 235, 0.18);
        }
    }

    /* Individual Leave Type Summary Card: Compact & Reduced Size */
    .leave-type-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 8px 12px;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        position: relative;
        overflow: hidden;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
        border-left: 3.5px solid #94a3b8;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        height: 100%;
    }
    .leave-type-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 14px rgba(0, 0, 0, 0.06);
        border-color: #cbd5e1;
    }
    
    /* Distinct Background and Animated Border when Card is Active */
    .leave-type-card.active-filter {
        background: linear-gradient(135deg, #f0f7ff 0%, #e0effe 100%) !important;
        border: 1.5px solid #2563eb !important;
        border-left: 4.5px solid #1d4ed8 !important;
        animation: activeBorderPulse 2s infinite ease-in-out !important;
        transform: translateY(-2px);
    }
    .leave-type-card.active-filter .card-stats-grid {
        background: #ffffff !important;
        border: 1px solid #bfdbfe !important;
    }
    .leave-type-card.active-filter .type-name {
        color: #1e40af !important;
        font-weight: 700 !important;
    }

    .leave-type-card.theme-blue { border-left-color: #3b82f6; }
    .leave-type-card.theme-cyan { border-left-color: #06b6d4; }
    .leave-type-card.theme-amber { border-left-color: #f59e0b; }
    .leave-type-card.theme-emerald { border-left-color: #10b981; }
    .leave-type-card.theme-purple { border-left-color: #8b5cf6; }
    .leave-type-card.theme-rose { border-left-color: #f43f5e; }
    .leave-type-card.theme-indigo { border-left-color: #6366f1; }

    .type-icon-box {
        width: 24px;
        height: 24px;
        border-radius: 5px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        background: #f1f5f9;
        color: #475569;
        flex-shrink: 0;
    }
    .theme-blue .type-icon-box { background: #eff6ff; color: #2563eb; }
    .theme-cyan .type-icon-box { background: #ecfeff; color: #0891b2; }
    .theme-amber .type-icon-box { background: #fffbeb; color: #d97706; }
    .theme-emerald .type-icon-box { background: #ecfdf5; color: #059669; }
    .theme-purple .type-icon-box { background: #f5f3ff; color: #7c3aed; }
    .theme-rose .type-icon-box { background: #fff1f2; color: #e11d48; }
    .theme-indigo .type-icon-box { background: #eef2ff; color: #4f46e5; }

    .type-name {
        font-size: 12px;
        font-weight: 600;
        color: #1e293b;
        letter-spacing: -0.2px;
    }
    .days-badge {
        font-size: 10px;
        font-weight: 700;
        background: #f1f5f9;
        color: #334155;
        border-radius: 5px;
        padding: 2px 6px;
    }
    .leave-type-card.active-filter .days-badge {
        background: #2563eb;
        color: #ffffff;
    }
    .card-stats-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 6px;
        margin: 5px 0;
        padding: 4px 8px;
        background: #f8fafc;
        border-radius: 6px;
    }
    .stat-box {
        display: flex;
        flex-direction: column;
    }
    .stat-num {
        font-size: 11.5px;
        font-weight: 600;
        line-height: 1.2;
    }
    .stat-lbl {
        font-size: 9px;
        font-weight: 500;
        margin-top: 1px;
    }

    /* Sub-status pills */
    .mini-status-pill {
        font-size: 8.5px;
        font-weight: 600;
        padding: 1px 4px;
        border-radius: 3px;
        display: inline-flex;
        align-items: center;
        gap: 2px;
    }
    .pill-approved { background: #dcfce7; color: #166534; }
    .pill-pending { background: #fef3c7; color: #92400e; }
    .pill-rejected { background: #fee2e2; color: #991b1b; }

    /* =========================================================
       TABLE & ACTION CONTAINER STYLING: COMPACT
       ========================================================= */
    .table-container-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
    }
    .table-responsive {
        border-radius: 0;
        margin: 0;
    }
    .table-custom {
        margin-bottom: 0;
        border-collapse: collapse;
        width: 100%;
    }
    .table-custom thead th {
        background-color: #f8fafc !important;
        color: #475569 !important;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 10.5px;
        letter-spacing: 0.5px;
        padding: 8px 12px;
        border-bottom: 2px solid #e2e8f0;
        border-top: none;
        white-space: nowrap;
        vertical-align: middle;
    }
    .table-custom tbody td {
        padding: 8px 12px;
        vertical-align: middle;
        color: #334155;
        font-size: 12.5px;
        border-bottom: 1px solid #f1f5f9;
        transition: background-color 0.15s ease-in-out;
    }
    .table-custom tbody tr:hover td {
        background-color: #f8fafc;
    }
    .table-custom tbody tr:last-child td {
        border-bottom: none;
    }

    /* User Avatar: Compact */
    .emp-avatar {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        color: #ffffff;
        font-weight: 700;
        font-size: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        text-transform: uppercase;
    }

    /* Badges */
    .badge-leave-type {
        background-color: #f1f5f9;
        color: #334155;
        border: 1px solid #e2e8f0;
        font-weight: 600;
        font-size: 11px;
        padding: 3px 8px;
        border-radius: 5px;
        display: inline-flex;
        align-items: center;
        gap: 3px;
    }
    .badge-status {
        font-size: 11px;
        font-weight: 600;
        padding: 3px 9px;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .badge-approved { background-color: #d1fae5; color: #065f46; }
    .badge-rejected { background-color: #fee2e2; color: #991b1b; }
    .badge-pending { background-color: #fef3c7; color: #92400e; }

    /* Sticky Bottom Actions */
    .sticky-actions-bar {
        position: sticky;
        bottom: 12px;
        z-index: 1020;
        margin-top: 14px;
        display: flex;
        justify-content: center;
    }
    .action-panel-pill {
        background: rgba(255, 255, 255, 0.96);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        padding: 6px 16px;
        border-radius: 40px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
        border: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .btn-action-approve {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: #ffffff;
        border: none;
        font-weight: 600;
        font-size: 12px;
        padding: 6px 16px;
        border-radius: 24px;
        box-shadow: 0 3px 8px rgba(16, 185, 129, 0.25);
        transition: all 0.2s ease-in-out;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .btn-action-approve:hover {
        color: #ffffff;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35);
    }
    .btn-action-reject {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: #ffffff;
        border: none;
        font-weight: 600;
        font-size: 12px;
        padding: 6px 16px;
        border-radius: 24px;
        box-shadow: 0 3px 8px rgba(239, 68, 68, 0.25);
        transition: all 0.2s ease-in-out;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .btn-action-reject:hover {
        color: #ffffff;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.35);
    }
    .btn-action-change {
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        color: #ffffff;
        border: none;
        font-weight: 600;
        font-size: 12px;
        padding: 6px 16px;
        border-radius: 24px;
        box-shadow: 0 3px 8px rgba(79, 70, 229, 0.28);
        transition: all 0.2s ease-in-out;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .btn-action-change:hover {
        color: #ffffff;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.38);
    }

    /* Checkbox */
    input[type="checkbox"].form-check-input-custom,
    input[type="checkbox"].sltalllvapprlindivisula,
    input[type="checkbox"]#sltalllvapprl {
        width: 16px;
        height: 16px;
        border-radius: 4px;
        border: 1.5px solid #94a3b8;
        cursor: pointer;
        transition: all 0.15s ease-in-out;
    }
    input[type="checkbox"]:checked {
        background-color: #2563eb;
        border-color: #2563eb;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .leave-header-card {
            padding: 10px 14px;
        }
        .leave-header-title {
            font-size: 1rem;
        }
        .leave-modern-tabs .nav-link {
            font-size: 11.5px !important;
            padding: 6px 10px !important;
        }
        .filter-card .card-body {
            padding: 10px !important;
        }
        .kpi-mini-card {
            padding: 5px 8px;
        }
        .kpi-value {
            font-size: 13.5px;
        }
        .kpi-label {
            font-size: 8.5px;
        }
        .table-custom thead th, .table-custom tbody td {
            padding: 7px 8px !important;
            font-size: 11.5px !important;
        }
        .sticky-actions-bar {
            left: 8px;
            right: 8px;
            bottom: 8px;
        }
        .action-panel-pill {
            width: 100%;
            justify-content: center;
            border-radius: 12px;
            padding: 6px 10px;
            flex-wrap: wrap;
            gap: 6px;
        }
        .btn-action-approve, .btn-action-reject, .btn-action-change {
            font-size: 11px !important;
            padding: 5px 10px !important;
            flex: 1;
            white-space: nowrap;
        }
    }
</style>

<div class="container-fluid mt-2 mb-3 px-2 px-md-3" id="employeesalarycontainer">
    
    <!-- Top Header Banner: Compact, Light Background & Dark Text -->
    <div class="leave-header-card">
        <div>
            <h4 class="leave-header-title" id="pageMainHeading">
                <i class="ti ti-hourglass-low fs-18 text-warning"></i> Pending Leave for Approval
            </h4>
            <!-- Hidden on mobile viewport (viewport < 768px) -->
            <div class="text-muted fs-11 mt-0.5 d-none d-md-block">
                Explore, analyze, and manage employee leave approvals with smart multi-filtering
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-primary fw-bold px-2.5 py-1 shadow-sm" id="btnReloadLeaveData" title="Refresh records" style="border-radius: 7px; height: 32px; font-size: 12px;">
                <i class="ti ti-refresh me-1"></i> <span>Refresh</span>
            </button>
        </div>
    </div>

    <!-- Smart Data Filters Card: Compact Size -->
    <div class="filter-card shadow-sm">
        <div class="filter-header-bar">
            <div class="d-flex align-items-center gap-1.5">
                <i class="ti ti-adjustments-horizontal text-primary fs-16"></i>
                <span class="fw-bold fs-13 text-dark">Smart Data Filters</span>
                <span class="badge bg-primary-subtle text-primary ms-1 fs-10" id="activeFilterCountBadge">All Active</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary py-0.5 px-2 fs-11" id="btnResetAllFilters" title="Clear all sub-filters">
                    <i class="ti ti-rotate me-1"></i>Reset Filters
                </button>
            </div>
        </div>

        <div class="card-body p-2 p-md-3">
            <!-- Row 1: Date Boundaries (Year, Date Mode, Month Selector or Custom Range) -->
            <div class="row g-2 align-items-end pb-2 mb-2 border-bottom">
                <!-- Year -->
                <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                    <label class="filter-label"><i class="ti ti-calendar text-primary"></i>Year</label>
                    <select class="form-select fw-bold" id="yearfilter">
                        <!-- Populated dynamically via GetLeaveYearsList -->
                    </select>
                </div>

                <!-- Date Mode Toggle: By Month vs Custom Date with high-contrast active background -->
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <label class="filter-label"><i class="ti ti-calendar-time text-primary"></i>Date Mode</label>
                    <div class="date-mode-btn-group" role="group">
                        <input type="radio" class="btn-check" name="dateModeRadio" id="dateModeMonth" value="month" checked autocomplete="off">
                        <label class="date-mode-btn" for="dateModeMonth">
                            <i class="ti ti-calendar-month me-1"></i>By Month
                        </label>

                        <input type="radio" class="btn-check" name="dateModeRadio" id="dateModeCustom" value="custom" autocomplete="off">
                        <label class="date-mode-btn" for="dateModeCustom">
                            <i class="ti ti-calendar-event me-1"></i>Custom Date
                        </label>
                    </div>
                </div>

                <!-- Month Dropdown (shown when dateMode == 'month') -->
                <div class="col-12 col-sm-12 col-md-5 col-lg-4" id="monthFilterContainer">
                    <label class="filter-label"><i class="ti ti-list-details text-primary"></i>Select Month</label>
                    <select class="form-select" id="monthFilterSelect">
                        <option value="all">All Months (Whole Year)</option>
                        <option value="01">01 - January</option>
                        <option value="02">02 - February</option>
                        <option value="03">03 - March</option>
                        <option value="04">04 - April</option>
                        <option value="05">05 - May</option>
                        <option value="06">06 - June</option>
                        <option value="07">07 - July</option>
                        <option value="08">08 - August</option>
                        <option value="09">09 - September</option>
                        <option value="10">10 - October</option>
                        <option value="11">11 - November</option>
                        <option value="12">12 - December</option>
                    </select>
                </div>

                <!-- Custom Date Range (shown when dateMode == 'custom') -->
                <div class="col-12 col-sm-12 col-md-5 col-lg-7 d-none" id="customDateRangeContainer">
                    <div class="row g-1.5 align-items-end">
                        <div class="col-12 col-sm-5">
                            <label class="filter-label">From Date</label>
                            <input type="date" class="form-control" id="customDateFrom">
                        </div>
                        <div class="col-12 col-sm-5">
                            <label class="filter-label">To Date</label>
                            <input type="date" class="form-control" id="customDateTo">
                        </div>
                        <div class="col-12 col-sm-2">
                            <button type="button" class="btn btn-primary w-100 fw-bold" id="btnApplyCustomDate" style="height:33px; font-size:12px;" title="Apply Date Range">
                                <i class="ti ti-check"></i> <span class="d-inline d-sm-none">Apply</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 2: Cascading Filters: Leave Type, Leave Status, Employee Name, Live Search -->
            <div class="row g-2 align-items-end">
                <!-- Leave Type (as shown in grid) -->
                <div class="col-12 col-sm-6 col-md-3 col-lg-3">
                    <label class="filter-label"><i class="ti ti-category text-primary"></i>Leave Type</label>
                    <select class="form-select" id="filterLeaveType">
                        <option value="all">All Leave Types</option>
                    </select>
                </div>

                <!-- Leave Status (Pending, Approved, Rejected) -->
                <div class="col-12 col-sm-6 col-md-3 col-lg-2" id="filterStatusContainer">
                    <label class="filter-label"><i class="ti ti-flag text-primary"></i>Leave Status</label>
                    <select class="form-select" id="filterLeaveStatus">
                        <option value="all">All Statuses</option>
                        <option value="Pending">Pending</option>
                        <option value="Approved">Approved</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </div>

                <!-- Employee Name -->
                <div class="col-12 col-sm-6 col-md-3 col-lg-4">
                    <label class="filter-label"><i class="ti ti-user text-primary"></i>Employee Name</label>
                    <select class="form-select select2" id="EmployeeListdrp" style="width: 100%;">
                        <option value="all">All Employees</option>
                    </select>
                </div>

                <!-- Live Search Box -->
                <div class="col-12 col-sm-6 col-md-3 col-lg-3">
                    <label class="filter-label"><i class="ti ti-search text-primary"></i>Search Keyword</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted" style="border-radius: 7px 0 0 7px; height: 33px; padding: 0 8px;">
                            <i class="ti ti-search fs-14"></i>
                        </span>
                        <input type="search" class="form-control border-start-0 ps-0" placeholder="Type to search..." id="searchrptlvap">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs: 'Pending Leave' & 'Leave History' Converted into Tabs After Smart Data Filters -->
    <div class="leave-mode-tabs-container mb-3">
        <ul class="nav nav-pills leave-modern-tabs" id="leaveModeTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pendingleaveapproval" type="button" role="tab" aria-selected="true">
                    <i class="ti ti-hourglass-low me-1 fs-15 text-warning"></i>
                    <span>Pending Leave</span>
                    <span class="badge bg-danger ms-1.5" id="pendingCounterBadge">0</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="historyofleaveapproval" type="button" role="tab" aria-selected="false">
                    <i class="ti ti-history me-1 fs-15 text-primary"></i>
                    <span>Leave History</span>
                    <span class="badge bg-secondary ms-1.5" id="historyCounterBadge">0</span>
                </button>
            </li>
        </ul>
    </div>

    <!-- Summary Cards Section: Global KPI Strip & Leave Type Dynamic Breakdown Cards -->
    <div id="leaveSummarySection" class="mb-3">
        <!-- Top Overall KPI Mini Metrics Strip: Compact -->
        <div class="row g-1.5 g-md-2 mb-2" id="overallMetricsRow">
            <!-- Dynamically injected: Total Requests, Leave Days, Employees, Approved, Pending, Rejected -->
        </div>

        <!-- Leave Type Summary Cards (Breakdown with employee count & leaves count) -->
        <div class="d-flex align-items-center justify-content-between mb-1.5">
            <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-1 fs-13">
                <i class="ti ti-layout-grid text-primary"></i> Leave Type Summary
                <span class="badge bg-secondary-subtle text-secondary fs-10" id="leaveTypeSummaryCount">0 Types</span>
            </h6>
            <small class="text-muted fs-11 d-none d-sm-inline">
                <i class="ti ti-click me-0.5"></i>Click card to filter &bull; Click active to reset
            </small>
        </div>
        
        <div class="row g-2" id="leaveTypeCardsRow">
            <!-- Dynamically populated leave type summary cards -->
        </div>
    </div>

    <!-- Main Data Table Container: Compact -->
    <div class="table-container-card shadow-sm mb-3">
        <div class="py-2 px-3 bg-light border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="fw-bold text-dark fs-12 d-flex align-items-center gap-1.5">
                <i class="ti ti-list text-primary fs-15"></i>
                <span id="gridResultsSummary">Showing records...</span>
            </div>
            <div class="text-muted fs-11" id="gridDatePeriodLabel">
                <!-- Date span display -->
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-custom table-hover" id="leaveapprovaltbl">
                <thead>
                    <tr>
                        <th class="selectAlth text-center" style="width: 48px;">
                            <div class="d-flex flex-column align-items-center gap-1">
                                <span class="fs-10">All</span>
                                <input type="checkbox" class="msselectall form-check-input-custom" id="sltalllvapprl">
                            </div>
                        </th>
                        <th>Employee</th>
                        <th>Apply Date</th>
                        <th>Leave Type</th>
                        <th>Leave Period</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th>Remark / Reason</th>
                        <th class="text-center" style="width: 70px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <div class="spinner-border text-primary spinner-border-sm mb-1" role="status"></div>
                            <p class="mb-0 fs-12">Loading leave requests...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Sticky Floating Action Panel for Pending Approvals: Shown ONLY if selected count > 0 -->
    <div class="sticky-actions-bar d-none" id="leaveactionbtndiv">
        <div class="action-panel-pill">
            <button class="btn btn-action-approve" id="approvedleavebtn" onclick="UpdateAppliedLeaveStatus('Approved', 'leaveapprovaltbl', 'approvedleavebtn')">
                <i class="ti ti-check me-1"></i> Approve Selected (<span id="approveSelectedCount">0</span>)
            </button>
            <button class="btn btn-action-reject" id="rejectleavebtn" onclick="UpdateAppliedLeaveStatus('Rejected', 'leaveapprovaltbl', 'rejectleavebtn')">
                <i class="ti ti-x me-1"></i> Reject Selected (<span id="rejectSelectedCount">0</span>)
            </button>
            <button class="btn btn-action-change d-none" id="changeleavetypebtn" onclick="window.openChangeLeaveTypeModal()">
                <i class="ti ti-arrows-exchange me-1"></i> Change Leave Type (<span id="changeLeaveSelectedCount">0</span>)
            </button>
        </div>
    </div>

    <!-- Modal: Change Leave Type for Selected Applications -->
    <div class="modal fade" id="changeLeaveTypeModal" tabindex="-1" aria-labelledby="changeLeaveTypeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow border-0" style="border-radius: 14px; overflow: hidden;">
                <div class="modal-header py-2.5 px-3 bg-light border-bottom">
                    <h5 class="modal-title fs-14 fw-bold text-dark d-flex align-items-center gap-1.5" id="changeLeaveTypeModalLabel">
                        <i class="ti ti-arrows-exchange text-primary fs-18"></i> Change Leave Type for Selected
                    </h5>
                    <button type="button" class="btn-close fs-11" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <!-- Info banner of selection -->
                    <div class="p-2.5 rounded-3 mb-3" style="background: #eff6ff; border: 1px solid #bfdbfe;">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="fs-12 text-muted">Current Leave Type:</span>
                            <span class="badge bg-primary text-white fs-11 fw-bold" id="modalCurrentLeaveType">—</span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="fs-12 text-muted">Selected Applications:</span>
                            <span class="fs-12 fw-bold text-dark" id="modalSelectedCount">0</span>
                        </div>
                        <div class="d-flex align-items-start justify-content-between">
                            <span class="fs-12 text-muted">Employees:</span>
                            <span class="fs-11 fw-semibold text-dark text-end text-truncate" style="max-width: 250px;" id="modalAffectedEmployees">—</span>
                        </div>
                    </div>

                    <!-- Form Controls -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark fs-12 mb-1">
                            Select New Leave Type <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="modalNewLeaveTypeSelect" style="height: 38px; font-size: 13px;">
                            <option value="">-- Choose New Leave Type --</option>
                        </select>
                        <small class="text-muted fs-11 mt-1 d-block">
                            <i class="ti ti-info-circle me-0.5"></i>All selected applications will be updated to this leave type.
                        </small>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold text-secondary fs-12 mb-1">
                            Reason / Remark (Optional)
                        </label>
                        <textarea class="form-control" id="modalChangeLeaveRemark" rows="2" style="font-size: 12.5px;" placeholder="Optional reason for changing leave type..."></textarea>
                    </div>
                </div>
                <div class="modal-footer py-2 px-3 bg-light border-top">
                    <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal" style="height: 34px; font-size: 12px; border-radius: 7px;">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-sm btn-primary fw-bold px-3 shadow-sm" id="btnSubmitChangeLeaveType" style="height: 34px; font-size: 12px; border-radius: 7px;">
                        <i class="ti ti-check me-1"></i> Update Leave Type
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

<?php
if(isset($_GET['vw']) && $_GET['vw'] == 1){
    // Standalone fallback scripts
    echo '<script src="assets/js/jquery-3.7.1.min.js"></script>';
    echo '<script src="assets/js/bootstrap.bundle.min.js"></script>';
    echo '<script src="assets/plugins/select2/js/select2.min.js"></script>';
    echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.2/dist/sweetalert2.all.min.js"></script>';
    echo '<script src="assets/js/hrm.js"></script>';
}else{
    require('footer.php'); 
}
?>

<script type="text/javascript">
(function() {
    // -------------------------------------------------------------
    // STATE MANAGEMENT: DEFAULT PENDING LEAVE SHOW FOR APPROVAL
    // -------------------------------------------------------------
    let companyid = $("body").data("companyid");
    let subcompanyid = $("body").data("subcompanyid");
    let projectid = $("body").data("projectid");
    
    // Default mode: "pending" as requested
    let currentMode = "pending";
    
    // If URL explicitly requests history tab, honor it
    let urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get("tab") === "history") {
        currentMode = "history";
    }

    // In-memory data store for the currently loaded date range
    let rawLeaveData = [];

    // Master list of all available leave types for company
    window.companyLeaveTypesList = [];

    // Active sub-filters
    let activeFilters = {
        employeeId: "all",
        leaveType: "all",
        status: (currentMode === "pending") ? "Pending" : "all",
        search: ""
    };

    // Flag to prevent recursive dropdown updates during cascading
    let isUpdatingDropdowns = false;

    // -------------------------------------------------------------
    // HELPER FUNCTIONS
    // -------------------------------------------------------------
    function formatToDMY(dateStr) {
        if (!dateStr || dateStr === '0000-00-00') return '—';
        let datePart = dateStr.split(' ')[0];
        let parts = datePart.split('-');
        if (parts.length === 3) {
            return `${parts[2]}-${parts[1]}-${parts[0]}`;
        }
        return dateStr;
    }

    function formatApplyDateTime(dateStr) {
        if (!dateStr || dateStr === '0000-00-00 00:00:00') return '<span class="text-muted">—</span>';
        
        let parts = dateStr.trim().split(' ');
        let rawDate = parts[0] || '';
        let rawTime = parts[1] || '';
        
        let dateFormatted = formatToDMY(rawDate);
        let timeFormatted = '';
        if (rawTime && rawTime !== '00:00:00') {
            let tParts = rawTime.split(':');
            if (tParts.length >= 2) {
                timeFormatted = `${tParts[0]}:${tParts[1]}`;
            }
        }
        
        if (timeFormatted) {
            return `
                <div class="d-flex flex-column">
                    <span class="text-dark fw-semibold" style="font-size: 12px;">${dateFormatted}</span>
                    <span class="fw-medium" style="font-size: 10.5px; color: #2563eb;">
                        <i class="ti ti-clock me-0.5"></i>${timeFormatted}
                    </span>
                </div>
            `;
        }
        return `<span class="text-dark fw-semibold" style="font-size: 12px;">${dateFormatted}</span>`;
    }

    // Uniform Leave Type Display matching grid requirement:
    // e.g. "Casual Leave (CL)" or "Sick Leave (SL)"
    function getLeaveTypeDisplayName(row) {
        if (!row) return 'Other';
        let name = (row.leaveName || '').trim();
        let code = (row.LeaveType || '').trim();
        if (name && code) {
            if (name.toLowerCase() === code.toLowerCase()) {
                return name;
            }
            return `${name} (${code})`;
        }
        return name || code || 'Other';
    }

    function getInitials(name) {
        if (!name) return 'EM';
        let parts = name.trim().split(' ');
        if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }

    // -------------------------------------------------------------
    // DATE RANGE COMPUTATION
    // -------------------------------------------------------------
    function computeActiveDateRange() {
        let mode = $('input[name="dateModeRadio"]:checked').val() || "month";
        let selectedYear = parseInt($("#yearfilter").val()) || new Date().getFullYear();

        if (mode === "month") {
            let selectedMonth = $("#monthFilterSelect").val() || "all";
            if (selectedMonth === "all") {
                return {
                    from: `${selectedYear}-01-01`,
                    to: `${selectedYear}-12-31`,
                    label: `Year ${selectedYear} (Jan 01 - Dec 31)`
                };
            } else {
                let m = parseInt(selectedMonth, 10);
                let mm = m < 10 ? `0${m}` : `${m}`;
                let lastDay = new Date(selectedYear, m, 0).getDate();
                let dd = lastDay < 10 ? `0${lastDay}` : `${lastDay}`;
                let monthNames = ["", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
                return {
                    from: `${selectedYear}-${mm}-01`,
                    to: `${selectedYear}-${mm}-${dd}`,
                    label: `${monthNames[m]} ${selectedYear} (01-${dd})`
                };
            }
        } else {
            let from = $("#customDateFrom").val();
            let to = $("#customDateTo").val();
            if (!from || !to) {
                let m = new Date().getMonth() + 1;
                let mm = m < 10 ? `0${m}` : `${m}`;
                let lastDay = new Date(selectedYear, m, 0).getDate();
                from = from || `${selectedYear}-${mm}-01`;
                to = to || `${selectedYear}-${mm}-${lastDay}`;
                $("#customDateFrom").val(from);
                $("#customDateTo").val(to);
            }
            return {
                from: from,
                to: to,
                label: `Custom: ${formatToDMY(from)} to ${formatToDMY(to)}`
            };
        }
    }

    // -------------------------------------------------------------
    // INITIALIZATION & YEAR FETCH
    // -------------------------------------------------------------
    function initYearFilter(callback) {
        let currentYear = new Date().getFullYear();
        let baseurl = $("body").data("routurl") || "";
        let projId = $("body").data("projectid") || "";

        $.post(baseurl + "modal/hrm_modal.php?action=GetLeaveYearsList", {
            projectid: projId
        }, function (raw) {
            let years = [];
            try {
                years = (typeof raw === "object") ? raw : JSON.parse(raw);
            } catch (e) {}

            if (!Array.isArray(years) || years.length === 0) {
                years = [currentYear, currentYear - 1, currentYear - 2];
            }

            let yearSelect = $("#yearfilter");
            yearSelect.empty();

            years.forEach(function (y) {
                let selected = (parseInt(y) === currentYear) ? 'selected' : '';
                yearSelect.append(`<option value="${y}" ${selected}>${y}</option>`);
            });

            if (yearSelect.find(`option[value="${currentYear}"]`).length > 0) {
                yearSelect.val(currentYear);
            } else {
                yearSelect.val(years[0]);
            }

            if (typeof callback === 'function') callback();
        }).fail(function () {
            let yearSelect = $("#yearfilter");
            yearSelect.empty().append(`<option value="${currentYear}" selected>${currentYear}</option>`);
            if (typeof callback === 'function') callback();
        });
    }

    // Load full list of company leave types from LeaveTypeMaster
    function loadCompanyLeaveTypes(callback) {
        let baseurl = $("body").data("routurl") || "";
        let compId = $("body").data("companyid") || "";
        $.post(baseurl + "modal/hrm_modal.php?action=LeaveTypeMaster_callback", {
            companyid: compId
        }, function(res) {
            let list = [];
            try {
                list = (typeof res === "object") ? res : JSON.parse(res);
            } catch(e) {}
            window.companyLeaveTypesList = Array.isArray(list) ? list : [];
            if (typeof callback === 'function') callback(window.companyLeaveTypesList);
        }).fail(function() {
            window.companyLeaveTypesList = [];
            if (typeof callback === 'function') callback([]);
        });
    }

    // -------------------------------------------------------------
    // DATA FETCHING
    // -------------------------------------------------------------
    function loadLeaveData() {
        let range = computeActiveDateRange();
        $("#gridDatePeriodLabel").html(`<i class="ti ti-calendar me-1"></i>Period: <strong>${range.label}</strong>`);

        // Show table loading state
        $("#leaveapprovaltbl tbody").html(`
            <tr>
                <td colspan="9" class="text-center py-4 text-muted">
                    <div class="spinner-border text-primary spinner-border-sm mb-1" role="status"></div>
                    <div class="fw-semibold fs-12">Fetching records from ${range.from} to ${range.to}...</div>
                </td>
            </tr>
        `);

        // Show cards loading state
        $("#leaveTypeCardsRow").html(`
            <div class="col-12 text-center py-3 text-muted">
                <div class="spinner-border spinner-border-sm text-primary mb-1"></div>
                <div class="small fs-11">Computing summary...</div>
            </div>
        `);

        // Fetch criteria:
        // In History mode: fetch all records (Pending, Approved, Rejected)
        // In Pending mode: fetch records with Status = Pending (including null or empty '')
        let sortby = (currentMode === "pending") ? "Status" : "";
        let sortbyvalue = (currentMode === "pending") ? "Pending" : "";

        GetEmployeeLeavesData_Callback(range.from, range.to, sortby, sortbyvalue, function (res) {
            let data = [];
            try {
                data = (typeof res === "object") ? res : JSON.parse(res);
            } catch(e) {
                data = [];
            }
            if (!Array.isArray(data)) data = [];

            // Normalize status: if empty, null, or unknown -> Pending
            data.forEach(function(r) {
                let st = String(r.Status || '').trim().toLowerCase();
                if (st === 'approved') {
                    r.Status = 'Approved';
                } else if (st === 'rejected') {
                    r.Status = 'Rejected';
                } else {
                    r.Status = 'Pending';
                }
            });

            rawLeaveData = data;

            // Reset sub-filters on fresh load
            activeFilters.employeeId = "all";
            activeFilters.leaveType = "all";
            activeFilters.status = (currentMode === "pending") ? "Pending" : "all";
            activeFilters.search = "";
            $("#searchrptlvap").val("");

            // Update UI presentation for current mode
            updateModePresentation();

            // Run faceted filter update and render
            applyFiltersAndRender();
        });

        // Update tab counter badges (Pending vs History) for current date range
        updateTabCounterBadges(range.from, range.to);
    }

    // Dynamic badge counters for both tabs based on date period
    function updateTabCounterBadges(from, to) {
        let baseurl = $("body").data("routurl") || "";
        let projId = $("body").data("projectid") || "";
        $.post(baseurl + "modal/hrm_modal.php?action=GetLeaveCounts", {
            projectid: projId,
            datefrom: from,
            dateto: to
        }, function(res) {
            let c = null;
            try {
                c = (typeof res === "object") ? res : JSON.parse(res);
            } catch(e) {}
            if (c) {
                if (c.pending !== undefined) $("#pendingCounterBadge").text(c.pending);
                if (c.history !== undefined) $("#historyCounterBadge").text(c.history);
            }
        }, 'json');
    }

    // Helper: Checks if row status matches filter (handles null, empty string, or 'Pending')
    function isRowMatchingStatus(row, filterStatus) {
        if (!filterStatus || filterStatus === "all") return true;
        let rowSt = String(row.Status || '').trim().toLowerCase();
        let targetSt = String(filterStatus).trim().toLowerCase();
        if (targetSt === 'pending') {
            return rowSt === '' || rowSt === 'null' || rowSt === 'pending';
        }
        return rowSt === targetSt;
    }

    // -------------------------------------------------------------
    // MODE SWITCHING (Pending vs History)
    // -------------------------------------------------------------
    function updateModePresentation() {
        if (currentMode === "pending") {
            $("#pageMainHeading").html('<i class="ti ti-hourglass-low fs-18 text-warning"></i> Pending Leave for Approval');
            $("#pendingleaveapproval").addClass("active").attr("aria-selected", "true");
            $("#historyofleaveapproval").removeClass("active").attr("aria-selected", "false");
            $(".selectAlth").show();
            // Initially hide action buttons until user selects entries
            $("#leaveactionbtndiv").addClass("d-none");
            $("#filterStatusContainer").hide(); // In pending mode, all are pending
            $("#pendingCounterBadge").text(rawLeaveData.length);
        } else {
            $("#pageMainHeading").html('<i class="ti ti-history fs-18 text-primary"></i> History of Leave Approval');
            $("#historyofleaveapproval").addClass("active").attr("aria-selected", "true");
            $("#pendingleaveapproval").removeClass("active").attr("aria-selected", "false");
            $(".selectAlth").show(); // Checkboxes enabled in history mode too so approved leaves can change leave type
            $("#leaveactionbtndiv").addClass("d-none");
            $("#filterStatusContainer").show(); // Full status selector visible
            
            let pCount = rawLeaveData.filter(function(r) { return isRowMatchingStatus(r, 'Pending'); }).length;
            $("#pendingCounterBadge").text(pCount);
            $("#historyCounterBadge").text(rawLeaveData.length);
        }
    }

    // -------------------------------------------------------------
    // CASCADING FACETED FILTER ENGINE
    // -------------------------------------------------------------
    function applyFiltersAndRender(triggerSource) {
        // 1. Calculate candidates for Employee Filter:
        let candidatesForEmp = rawLeaveData.filter(function(row) {
            let matchLt = (activeFilters.leaveType === "all" || getLeaveTypeDisplayName(row) === activeFilters.leaveType);
            let matchSt = isRowMatchingStatus(row, activeFilters.status);
            return matchLt && matchSt;
        });

        // 2. Calculate candidates for Leave Type Filter & Leave Type Summary Cards:
        // (Filtered by Employee and Status, but NOT by Leave Type itself so ALL cards stay visible!)
        let candidatesForLt = rawLeaveData.filter(function(row) {
            let matchEmp = (activeFilters.employeeId === "all" || String(row.EmployeeID) === String(activeFilters.employeeId));
            let matchSt = isRowMatchingStatus(row, activeFilters.status);
            return matchEmp && matchSt;
        });

        // 3. Calculate candidates for Status Filter:
        let candidatesForSt = rawLeaveData.filter(function(row) {
            let matchEmp = (activeFilters.employeeId === "all" || String(row.EmployeeID) === String(activeFilters.employeeId));
            let matchLt = (activeFilters.leaveType === "all" || getLeaveTypeDisplayName(row) === activeFilters.leaveType);
            return matchEmp && matchLt;
        });

        // Re-populate dropdowns with current valid counts & preserve or safely reset selections
        updateEmployeeDropdown(candidatesForEmp);
        updateLeaveTypeDropdown(candidatesForLt);
        updateStatusDropdown(candidatesForSt);

        // 4. Compute Final Filtered Records matching all criteria + text search for Grid
        let finalRecords = rawLeaveData.filter(function(row) {
            let matchEmp = (activeFilters.employeeId === "all" || String(row.EmployeeID) === String(activeFilters.employeeId));
            let matchLt = (activeFilters.leaveType === "all" || getLeaveTypeDisplayName(row) === activeFilters.leaveType);
            let matchSt = isRowMatchingStatus(row, activeFilters.status);
            
            let matchSearch = true;
            if (activeFilters.search) {
                let q = activeFilters.search.toLowerCase().trim();
                let haystack = [
                    row.EmployeeName || '',
                    row.EmployeeID || '',
                    getLeaveTypeDisplayName(row),
                    row.Status || '',
                    row.Remarks || '',
                    formatToDMY(row.LeaveStartDate),
                    formatToDMY(row.LeaveEndDate)
                ].join(' ').toLowerCase();
                matchSearch = haystack.indexOf(q) > -1;
            }
            return matchEmp && matchLt && matchSt && matchSearch;
        });

        // 5. Render UI:
        // Summary KPIs reflect the final records
        renderSummaryKPIs(finalRecords);
        // Leave Type Summary Cards use candidatesForLt so NO cards are hidden when clicking one!
        renderLeaveTypeSummaryCards(candidatesForLt);
        // Grid displays final matching records
        renderLeaveTable(finalRecords);
        updateActiveFilterBadge();
    }

    // Update Employee Dropdown with cascading options
    function updateEmployeeDropdown(candidates) {
        let empMap = new Map();
        candidates.forEach(function(r) {
            if (r.EmployeeID) {
                let id = String(r.EmployeeID);
                if (!empMap.has(id)) {
                    empMap.set(id, { id: id, name: r.EmployeeName || id, count: 0 });
                }
                empMap.get(id).count++;
            }
        });

        // If currently selected employee no longer exists in candidates, reset to "all"
        if (activeFilters.employeeId !== "all" && !empMap.has(String(activeFilters.employeeId))) {
            activeFilters.employeeId = "all";
        }

        let optionsHtml = `<option value="all">All Employees (${candidates.length})</option>`;
        let sortedEmps = Array.from(empMap.values()).sort(function(a, b) {
            return a.name.localeCompare(b.name);
        });

        sortedEmps.forEach(function(e) {
            let isSelected = (String(e.id) === String(activeFilters.employeeId)) ? 'selected' : '';
            optionsHtml += `<option value="${e.id}" ${isSelected}>${e.name} (${e.count})</option>`;
        });

        isUpdatingDropdowns = true;
        let $drop = $("#EmployeeListdrp");
        $drop.html(optionsHtml);
        $drop.val(activeFilters.employeeId);

        if ($.fn.select2) {
            if (!$drop.hasClass("select2-hidden-accessible")) {
                $drop.select2({
                    placeholder: "Search & Select Employee",
                    allowClear: false,
                    width: '100%'
                });
            } else {
                $drop.trigger('change.select2');
            }
        }
        isUpdatingDropdowns = false;
    }

    // Update Leave Type Dropdown with cascading options
    function updateLeaveTypeDropdown(candidates) {
        let typeMap = new Map();
        candidates.forEach(function(r) {
            let t = getLeaveTypeDisplayName(r);
            typeMap.set(t, (typeMap.get(t) || 0) + 1);
        });

        if (activeFilters.leaveType !== "all" && !typeMap.has(activeFilters.leaveType)) {
            activeFilters.leaveType = "all";
        }

        let optionsHtml = `<option value="all">All Leave Types (${candidates.length})</option>`;
        let sortedTypes = Array.from(typeMap.keys()).sort();

        sortedTypes.forEach(function(t) {
            let cnt = typeMap.get(t);
            let isSelected = (t === activeFilters.leaveType) ? 'selected' : '';
            optionsHtml += `<option value="${t}" ${isSelected}>${t} (${cnt})</option>`;
        });

        isUpdatingDropdowns = true;
        let $drop = $("#filterLeaveType");
        $drop.html(optionsHtml);
        $drop.val(activeFilters.leaveType);
        isUpdatingDropdowns = false;
    }

    // Update Status Dropdown with cascading options
    function updateStatusDropdown(candidates) {
        let statusCounts = { Pending: 0, Approved: 0, Rejected: 0 };
        candidates.forEach(function(r) {
            let st = String(r.Status || '').trim().toLowerCase();
            if (st === '' || st === 'null' || st === 'pending') {
                statusCounts.Pending++;
            } else if (r.Status && statusCounts[r.Status] !== undefined) {
                statusCounts[r.Status]++;
            }
        });

        let optionsHtml = `<option value="all">All Statuses (${candidates.length})</option>`;
        ['Pending', 'Approved', 'Rejected'].forEach(function(st) {
            let cnt = statusCounts[st] || 0;
            let isSelected = (activeFilters.status === st) ? 'selected' : '';
            optionsHtml += `<option value="${st}" ${isSelected}>${st} (${cnt})</option>`;
        });

        isUpdatingDropdowns = true;
        let $drop = $("#filterLeaveStatus");
        $drop.html(optionsHtml);
        $drop.val(activeFilters.status);
        isUpdatingDropdowns = false;
    }

    function updateActiveFilterBadge() {
        let activeCount = 0;
        if (activeFilters.leaveType !== "all") activeCount++;
        if (activeFilters.status !== "all" && currentMode !== "pending") activeCount++;
        if (activeFilters.employeeId !== "all") activeCount++;
        if (activeFilters.search) activeCount++;

        let badge = $("#activeFilterCountBadge");
        if (activeCount === 0) {
            badge.removeClass("bg-warning text-dark").addClass("bg-primary-subtle text-primary").text("All Active");
        } else {
            badge.removeClass("bg-primary-subtle text-primary").addClass("bg-warning text-dark").text(`${activeCount} Filter${activeCount > 1 ? 's' : ''} Applied`);
        }
    }

    // -------------------------------------------------------------
    // SUMMARY CARDS RENDERING: COMPACT
    // -------------------------------------------------------------
    function renderSummaryKPIs(records) {
        let totalReq = records.length;
        let totalDays = 0;
        let uniqueEmps = new Set();
        let appCount = 0;
        let pendCount = 0;
        let rejCount = 0;

        records.forEach(function(r) {
            let days = parseFloat(r.TotalLeaveDays);
            if (isNaN(days) || days <= 0) days = 1;
            totalDays += days;
            if (r.EmployeeID) uniqueEmps.add(r.EmployeeID);

            if (r.Status === "Approved") appCount++;
            else if (r.Status === "Rejected") rejCount++;
            else pendCount++;
        });

        totalDays = Math.round(totalDays * 10) / 10;

        let kpiHtml = `
            <div class="col-6 col-sm-4 col-lg-2">
                <div class="kpi-mini-card kpi-total">
                    <div class="kpi-icon"><i class="ti ti-file-text"></i></div>
                    <div class="kpi-data">
                        <span class="kpi-value">${totalReq}</span>
                        <span class="kpi-label">Requests</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-sm-4 col-lg-2">
                <div class="kpi-mini-card kpi-days">
                    <div class="kpi-icon"><i class="ti ti-calendar-event"></i></div>
                    <div class="kpi-data">
                        <span class="kpi-value text-success">${totalDays}</span>
                        <span class="kpi-label">Total Days</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-sm-4 col-lg-2">
                <div class="kpi-mini-card kpi-emp">
                    <div class="kpi-icon"><i class="ti ti-users"></i></div>
                    <div class="kpi-data">
                        <span class="kpi-value text-primary">${uniqueEmps.size}</span>
                        <span class="kpi-label">Employees</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-sm-4 col-lg-2">
                <div class="kpi-mini-card kpi-approved">
                    <div class="kpi-icon"><i class="ti ti-circle-check"></i></div>
                    <div class="kpi-data">
                        <span class="kpi-value text-success">${appCount}</span>
                        <span class="kpi-label">Approved</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-sm-4 col-lg-2">
                <div class="kpi-mini-card kpi-pending">
                    <div class="kpi-icon"><i class="ti ti-clock"></i></div>
                    <div class="kpi-data">
                        <span class="kpi-value text-warning">${pendCount}</span>
                        <span class="kpi-label">Pending</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-sm-4 col-lg-2">
                <div class="kpi-mini-card kpi-rejected">
                    <div class="kpi-icon"><i class="ti ti-circle-x"></i></div>
                    <div class="kpi-data">
                        <span class="kpi-value text-danger">${rejCount}</span>
                        <span class="kpi-label">Rejected</span>
                    </div>
                </div>
            </div>
        `;
        $("#overallMetricsRow").html(kpiHtml);
    }

    // Leave Type Summary Cards: NEVER HIDES OTHER CARDS! Shows all types with active animated border
    function renderLeaveTypeSummaryCards(records) {
        let typeMap = new Map();

        records.forEach(function(r) {
            let days = parseFloat(r.TotalLeaveDays);
            if (isNaN(days) || days <= 0) days = 1;
            let tName = getLeaveTypeDisplayName(r);

            if (!typeMap.has(tName)) {
                typeMap.set(tName, {
                    name: tName,
                    totalDays: 0,
                    requestCount: 0,
                    employees: new Set(),
                    approved: 0,
                    pending: 0,
                    rejected: 0
                });
            }
            let tData = typeMap.get(tName);
            tData.totalDays += days;
            tData.requestCount++;
            if (r.EmployeeID) tData.employees.add(r.EmployeeID);
            if (r.Status === "Approved") tData.approved++;
            else if (r.Status === "Rejected") tData.rejected++;
            else tData.pending++;
        });

        $("#leaveTypeSummaryCount").text(`${typeMap.size} Type${typeMap.size !== 1 ? 's' : ''}`);

        if (typeMap.size === 0) {
            $("#leaveTypeCardsRow").html(`
                <div class="col-12">
                    <div class="p-3 text-center text-muted bg-white rounded-3 border" style="border: 2px dashed #e2e8f0 !important;">
                        <i class="ti ti-file-search fs-20 mb-1 text-secondary"></i>
                        <div class="fw-semibold fs-12">No leave types match the active filter criteria.</div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-1 py-0.5 px-2 fs-11" onclick="window.resetLeaveSubFilters()">
                            <i class="ti ti-rotate me-1"></i>Reset Sub-Filters
                        </button>
                    </div>
                </div>
            `);
            return;
        }

        let sortedTypes = Array.from(typeMap.values()).sort(function(a, b) {
            return b.totalDays - a.totalDays;
        });

        let cardsHtml = "";
        sortedTypes.forEach(function(t) {
            let tDays = Math.round(t.totalDays * 10) / 10;
            let empCount = t.employees.size;
            let isCurrentActive = (activeFilters.leaveType === t.name);

            // Contextual icon & palette based on leave name
            let iconClass = "ti ti-calendar-event";
            let colorTheme = "theme-blue";
            let lname = t.name.toLowerCase();
            if (lname.includes("casual") || lname.includes("cl")) {
                iconClass = "ti ti-sun";
                colorTheme = "theme-cyan";
            } else if (lname.includes("sick") || lname.includes("sl") || lname.includes("medical")) {
                iconClass = "ti ti-first-aid-kit";
                colorTheme = "theme-amber";
            } else if (lname.includes("earned") || lname.includes("el") || lname.includes("annual")) {
                iconClass = "ti ti-award";
                colorTheme = "theme-emerald";
            } else if (lname.includes("maternity") || lname.includes("paternity")) {
                iconClass = "ti ti-baby-carriage";
                colorTheme = "theme-purple";
            } else if (lname.includes("loss of pay") || lname.includes("lop")) {
                iconClass = "ti ti-wallet-off";
                colorTheme = "theme-rose";
            } else if (lname.includes("compensatory") || lname.includes("comp")) {
                iconClass = "ti ti-clock-check";
                colorTheme = "theme-indigo";
            }

            let escapedName = t.name.replace(/\\/g, "\\\\").replace(/'/g, "\\'");

            // Compact cards with animated glowing border and distinct background when active
            cardsHtml += `
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="leave-type-card ${colorTheme} ${isCurrentActive ? 'active-filter' : ''}" 
                         onclick="window.toggleLeaveTypeFilter('${escapedName}')"
                         title="Click to ${isCurrentActive ? 'clear filter' : 'filter grid by ' + t.name}">
                        
                        <div class="d-flex align-items-center justify-content-between gap-1.5 mb-1">
                            <div class="d-flex align-items-center gap-1.5 text-truncate">
                                <div class="type-icon-box">
                                    <i class="${iconClass}"></i>
                                </div>
                                <span class="type-name text-truncate" title="${t.name}">
                                    ${t.name}
                                </span>
                            </div>
                            <span class="days-badge">
                                ${tDays} ${tDays === 1 ? 'Day' : 'Days'}
                            </span>
                        </div>

                        <div class="card-stats-grid">
                            <div class="stat-box">
                                <span class="stat-num text-dark">
                                    <i class="ti ti-users me-0.5 text-primary"></i>${empCount}
                                </span>
                                <span class="stat-lbl text-muted">${empCount === 1 ? 'Employee' : 'Employees'}</span>
                            </div>
                            <div class="stat-box">
                                <span class="stat-num text-dark">
                                    <i class="ti ti-file-text me-0.5 text-secondary"></i>${t.requestCount}
                                </span>
                                <span class="stat-lbl text-muted">${t.requestCount === 1 ? 'Request' : 'Requests'}</span>
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between pt-1 mt-0.5 border-top">
                            <div class="d-flex gap-1">
                                ${t.approved > 0 ? `<span class="mini-status-pill pill-approved" title="${t.approved} Approved"><i class="ti ti-check"></i> ${t.approved}</span>` : ''}
                                ${t.pending > 0 ? `<span class="mini-status-pill pill-pending" title="${t.pending} Pending"><i class="ti ti-clock"></i> ${t.pending}</span>` : ''}
                                ${t.rejected > 0 ? `<span class="mini-status-pill pill-rejected" title="${t.rejected} Rejected"><i class="ti ti-x"></i> ${t.rejected}</span>` : ''}
                            </div>
                            ${isCurrentActive ? `
                                <span class="badge bg-primary text-white fs-10 py-0.5 px-1.5 rounded-pill shadow-sm">
                                    <i class="ti ti-check me-0.5"></i> Active
                                </span>
                            ` : `
                                <small class="text-primary fw-semibold fs-11">Filter <i class="ti ti-arrow-narrow-right"></i></small>
                            `}
                        </div>
                    </div>
                </div>
            `;
        });

        $("#leaveTypeCardsRow").html(cardsHtml);
    }

    // Toggle filter by clicking Leave Type summary card
    window.toggleLeaveTypeFilter = function(typeName) {
        if (activeFilters.leaveType === typeName) {
            activeFilters.leaveType = "all";
        } else {
            activeFilters.leaveType = typeName;
        }
        applyFiltersAndRender("summaryCard");
    };

    // Helper: Collect all checked visible leave rows from table
    function getSelectedLeaveRows() {
        let list = [];
        $("#leaveapprovaltbl tbody tr:visible").each(function () {
            if ($(this).find("td[colspan]").length === 0) {
                let cb = $(this).find(".sltalllvapprlindivisula:not(:disabled)");
                if (cb.is(":checked")) {
                    list.push({
                        EmployeeID: $(this).data("employeeid"),
                        LeaveID: $(this).data("leaveid"),
                        EmployeeName: $(this).data("employeename"),
                        LeaveType: $(this).data("leavetype"),
                        LeaveTypeDisplay: $(this).data("leavetypedisplay")
                    });
                }
            }
        });
        return list;
    }

    // -------------------------------------------------------------
    // CHANGE LEAVE TYPE MODAL & LOGIC
    // -------------------------------------------------------------
    window.openChangeLeaveTypeModal = function() {
        let selectedRows = getSelectedLeaveRows();
        if (selectedRows.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Selection',
                text: 'Please select at least one leave request.'
            });
            return;
        }

        let distinctTypes = new Set(selectedRows.map(function(r) { return r.LeaveType; }));
        if (distinctTypes.size > 1) {
            Swal.fire({
                icon: 'warning',
                title: 'Mixed Leave Types',
                text: 'Please select entries of the same leave type to change their leave type.'
            });
            return;
        }

        let currentCode = selectedRows[0].LeaveType;
        let currentDisplay = selectedRows[0].LeaveTypeDisplay || currentCode;
        let empNames = Array.from(new Set(selectedRows.map(function(r) { return r.EmployeeName; }))).join(', ');

        $("#modalCurrentLeaveType").text(currentDisplay);
        $("#modalSelectedCount").text(selectedRows.length);
        $("#modalAffectedEmployees").text(empNames || 'Selected employees');
        $("#modalChangeLeaveRemark").val("");

        function populateDropdown(list) {
            let opts = '<option value="">-- Choose New Leave Type --</option>';
            if (Array.isArray(list) && list.length > 0) {
                list.forEach(function(item) {
                    if (item.leave_code && item.leave_code !== currentCode) {
                        opts += `<option value="${item.leave_code}">${item.leave_name} (${item.leave_code})</option>`;
                    }
                });
            }
            $("#modalNewLeaveTypeSelect").html(opts);
        }

        if (window.companyLeaveTypesList && window.companyLeaveTypesList.length > 0) {
            populateDropdown(window.companyLeaveTypesList);
        } else {
            loadCompanyLeaveTypes(function(list) {
                populateDropdown(list);
            });
        }

        let modalEl = document.getElementById('changeLeaveTypeModal');
        let modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modalInstance.show();
    };

    // Submit handler for Change Leave Type
    $(document).on("click", "#btnSubmitChangeLeaveType", function() {
        let newType = $("#modalNewLeaveTypeSelect").val();
        if (!newType) {
            Swal.fire({
                icon: 'warning',
                title: 'Selection Required',
                text: 'Please select a new leave type.'
            });
            return;
        }

        let selectedRows = getSelectedLeaveRows();
        if (selectedRows.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Selection',
                text: 'Please select at least one leave request.'
            });
            return;
        }

        let remark = $("#modalChangeLeaveRemark").val().trim();
        let baseurl = $("body").data("routurl") || "";
        let compId = $("body").data("companyid") || "";
        let subcompId = $("body").data("subcompanyid") || "";
        let projId = $("body").data("projectid") || "";
        let logId = $("body").data("loginid") || "";
        let logName = $("body").data("loginname") || "";

        let $btn = $(this);
        $btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Updating...');

        $.ajax({
            type: "POST",
            url: baseurl + "modal/hrm_modal.php?action=ChangeAppliedLeaveType",
            data: {
                companyid: compId,
                subcompanyid: subcompId,
                projectid: projId,
                loginid: logId,
                loginname: logName,
                newLeaveType: newType,
                change_remark: remark,
                empLeaveData: JSON.stringify(selectedRows)
            },
            dataType: "json",
            success: function(res) {
                $btn.prop("disabled", false).html('<i class="ti ti-check me-1"></i> Update Leave Type');
                if (res && res.status === "success") {
                    let modalEl = document.getElementById('changeLeaveTypeModal');
                    let modalInstance = bootstrap.Modal.getInstance(modalEl);
                    if (modalInstance) modalInstance.hide();

                    // Immediately update local records in memory so summary cards and table update seamlessly
                    let newTypeName = newType;
                    if (Array.isArray(window.companyLeaveTypesList)) {
                        let match = window.companyLeaveTypesList.find(function(lt) {
                            return String(lt.leave_code).toUpperCase() === String(newType).toUpperCase();
                        });
                        if (match && match.leave_name) {
                            newTypeName = match.leave_name;
                        }
                    }

                    let selectedLeaveIds = new Set(selectedRows.map(function(r) { return String(r.LeaveID); }));
                    rawLeaveData.forEach(function(r) {
                        if (selectedLeaveIds.has(String(r.LeaveID))) {
                            r.LeaveType = newType;
                            r.leaveName = newTypeName;
                            if (remark) {
                                r.Remarks = (r.Remarks ? r.Remarks + " " : "") + `[Leave type changed to ${newType} by ${logName}: ${remark}]`;
                            }
                        }
                    });

                    // Reset leave type filter to 'all' so newly updated records are immediately visible
                    activeFilters.leaveType = "all";
                    $("#leaveTypeFilterSelect").val("all");

                    // Instantly re-render summary cards and table without requiring full page refresh
                    applyFiltersAndRender();
                    updateMasterCheckboxState();

                    Swal.fire({
                        icon: 'success',
                        title: 'Updated!',
                        text: res.message || 'Leave type updated successfully.',
                        timer: 2000,
                        showConfirmButton: false
                    });

                    // Fetch fresh sync from server in background
                    loadLeaveData();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Update Failed',
                        text: (res && res.message) ? res.message : 'Unable to update leave type.'
                    });
                }
            },
            error: function(xhr, status, err) {
                $btn.prop("disabled", false).html('<i class="ti ti-check me-1"></i> Update Leave Type');
                let msg = 'Server communication error while updating leave type.';
                if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                } else if (xhr && xhr.responseText) {
                    try {
                        let parsed = JSON.parse(xhr.responseText);
                        if (parsed && parsed.message) msg = parsed.message;
                    } catch(e) {}
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: msg
                });
            }
        });
    });

    // -------------------------------------------------------------
    // DATA TABLE RENDERING: COMPACT
    // -------------------------------------------------------------
    function renderLeaveTable(records) {
        let isPending = (currentMode === "pending");
        let baseurl = $("body").data("routurl") || "";

        $("#gridResultsSummary").html(`Showing <strong>${records.length}</strong> record${records.length !== 1 ? 's' : ''}`);

        if (!Array.isArray(records) || records.length === 0) {
            let emptyMsg = isPending ? "No pending leave applications found for approval." : "No leave records found matching the filter criteria.";
            $("#leaveapprovaltbl tbody").html(`
                <tr>
                    <td colspan="9" class="text-center py-4 text-muted">
                        <i class="ti ti-inbox fs-26 mb-1 text-secondary d-block"></i>
                        <div class="fw-semibold fs-13">${emptyMsg}</div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-1.5 py-0.5 px-2 fs-11" onclick="window.resetLeaveSubFilters()">
                            <i class="ti ti-rotate me-1"></i>Reset All Filters
                        </button>
                    </td>
                </tr>
            `);
            updateMasterCheckboxState();
            return;
        }

        let rowsHtml = "";
        records.forEach(function(row) {
            let applyDate = formatApplyDateTime(row.created_at);
            let startDate = formatToDMY(row.LeaveStartDate);
            let endDate = formatToDMY(row.LeaveEndDate);
            let leaveTypeDisplay = getLeaveTypeDisplayName(row);
            let days = parseFloat(row.TotalLeaveDays);
            if (isNaN(days) || days <= 0) days = 1;

            // Status Badge: If unknown / empty / pending -> show Pending badge
            let sVal = String(row.Status || '').trim().toLowerCase();
            let statusBadge = "";
            if (sVal === "approved") {
                statusBadge = `<span class="badge badge-status badge-approved"><i class="ti ti-circle-check"></i> Approved</span>`;
            } else if (sVal === "rejected") {
                statusBadge = `<span class="badge badge-status badge-rejected"><i class="ti ti-circle-x"></i> Rejected</span>`;
            } else {
                statusBadge = `<span class="badge badge-status badge-pending"><i class="ti ti-clock"></i> Pending</span>`;
            }

            // Attachment link if available
            let attachmentBtn = '<span class="text-muted fs-11">—</span>';
            if (row.attFilepath && row.attFilepath.trim() !== '') {
                let fileUrl = baseurl + row.attFilepath;
                attachmentBtn = `
                    <a href="${fileUrl}" target="_blank" class="btn btn-sm btn-outline-primary py-0.5 px-1.5 fs-11" title="View uploaded document">
                        <i class="ti ti-paperclip"></i> View
                    </a>
                `;
            }

            let initials = getInitials(row.EmployeeName);

            rowsHtml += `
                <tr 
                    data-employeeid="${row.EmployeeID}" 
                    data-employeename="${row.EmployeeName || ''}" 
                    data-leaveid="${row.LeaveID}"
                    data-startdate="${row.LeaveStartDate}"
                    data-enddate="${row.LeaveEndDate}"
                    data-status="${row.Status}"
                    data-leavetype="${row.LeaveType}"
                    data-leavetypedisplay="${leaveTypeDisplay}"
                >
                    <td class="selectAlth text-center" style="width: 48px;">
                        <input type="checkbox" class="sltalllvapprlindivisula form-check-input-custom">
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-1.5">
                            <div class="emp-avatar">${initials}</div>
                            <div>
                                <strong class="text-dark d-block text-nowrap fs-12">${row.EmployeeName || 'Unknown'}</strong>
                                <small class="text-muted fs-10">ID: ${row.EmployeeID || '—'}</small>
                            </div>
                        </div>
                    </td>
                    <td>${applyDate}</td>
                    <td>
                        <span class="badge-leave-type text-nowrap" title="${leaveTypeDisplay}">
                            <i class="ti ti-tag text-primary"></i> ${leaveTypeDisplay}
                        </span>
                    </td>
                    <td>
                        <div class="d-flex flex-column text-nowrap">
                            <span class="text-success fw-semibold fs-11"><i class="ti ti-calendar me-0.5"></i>${startDate}</span>
                            <span class="text-danger fw-semibold fs-11"><i class="ti ti-arrow-right me-0.5"></i>${endDate}</span>
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border fw-bold fs-11">
                            ${days} ${days === 1 ? 'Day' : 'Days'}
                        </span>
                    </td>
                    <td>${statusBadge}</td>
                    <td>
                        <small class="text-muted d-block fs-11" style="max-width: 200px; line-height: 1.25;" title="${row.Remarks || ''}">
                            ${row.Remarks ? row.Remarks : '<span class="text-muted">—</span>'}
                        </small>
                    </td>
                    <td class="text-center">${attachmentBtn}</td>
                </tr>
            `;
        });

        $("#leaveapprovaltbl tbody").html(rowsHtml);
        updateMasterCheckboxState();
    }

    // -------------------------------------------------------------
    // MASTER CHECKBOX SELECTION & DYNAMIC ACTION BUTTON BAR
    // "all three button show after if select entry count >0 than show other wise hide"
    // "add 1 more Change Leave Type Selected if selected entry of same leave type"
    // -------------------------------------------------------------
    $(document).on("change", "#sltalllvapprl", function () {
        let isChecked = $(this).prop("checked");
        $("#leaveapprovaltbl tbody tr:visible").each(function () {
            if ($(this).find("td[colspan]").length === 0) {
                $(this).find(".sltalllvapprlindivisula:not(:disabled)").prop("checked", isChecked);
            }
        });
        updateMasterCheckboxState();
    });

    $(document).on("change", ".sltalllvapprlindivisula", function () {
        updateMasterCheckboxState();
    });

    function updateMasterCheckboxState() {
        let visibleRows = $("#leaveapprovaltbl tbody tr:visible").filter(function () {
            return $(this).find("td[colspan]").length === 0;
        });

        let totalVisible = 0;
        let checkedVisible = 0;
        let selectedRows = [];

        visibleRows.each(function () {
            let cb = $(this).find(".sltalllvapprlindivisula:not(:disabled)");
            if (cb.length > 0) {
                totalVisible++;
                if (cb.is(":checked")) {
                    checkedVisible++;
                    selectedRows.push({
                        EmployeeID: $(this).data("employeeid"),
                        LeaveID: $(this).data("leaveid"),
                        EmployeeName: $(this).data("employeename"),
                        LeaveType: $(this).data("leavetype"),
                        LeaveTypeDisplay: $(this).data("leavetypedisplay")
                    });
                }
            }
        });

        // Master checkbox state
        let master = $("#sltalllvapprl");
        if (totalVisible === 0) {
            master.prop("checked", false);
            master.prop("indeterminate", false);
        } else {
            master.prop("checked", totalVisible === checkedVisible);
            master.prop("indeterminate", checkedVisible > 0 && checkedVisible < totalVisible);
        }

        // Action Buttons Visibility logic:
        // "all three button show after if select entry count >0 than show other wise hide"
        // "and also approved leave also can change the leave type"
        if (checkedVisible > 0) {
            let distinctTypes = new Set(selectedRows.map(function(r) { return r.LeaveType; }));
            let isSameLeaveType = (distinctTypes.size === 1);

            if (currentMode === "pending") {
                // In Pending mode: show Approve & Reject, plus Change Leave Type if same leave type
                $("#leaveactionbtndiv").removeClass("d-none");
                $("#approveleavebtn").removeClass("d-none");
                $("#rejectleavebtn").removeClass("d-none");
                $("#approveSelectedCount").text(checkedVisible);
                $("#rejectSelectedCount").text(checkedVisible);

                if (isSameLeaveType) {
                    $("#changeleavetypebtn").removeClass("d-none");
                    $("#changeLeaveSelectedCount").text(checkedVisible);
                } else {
                    $("#changeleavetypebtn").addClass("d-none");
                }
            } else {
                // In History mode (Approved / Rejected leaves):
                // Hide Approve & Reject, show Change Leave Type if same leave type
                $("#approveleavebtn").addClass("d-none");
                $("#rejectleavebtn").addClass("d-none");

                if (isSameLeaveType) {
                    $("#leaveactionbtndiv").removeClass("d-none");
                    $("#changeleavetypebtn").removeClass("d-none");
                    $("#changeLeaveSelectedCount").text(checkedVisible);
                } else {
                    $("#leaveactionbtndiv").addClass("d-none");
                    $("#changeleavetypebtn").addClass("d-none");
                }
            }
        } else {
            // Hide action panel when count == 0
            $("#leaveactionbtndiv").addClass("d-none");
            $("#changeleavetypebtn").addClass("d-none");
        }
    }

    // -------------------------------------------------------------
    // RESET FILTER HELPERS
    // -------------------------------------------------------------
    window.resetLeaveSubFilters = function() {
        activeFilters.employeeId = "all";
        activeFilters.leaveType = "all";
        activeFilters.status = (currentMode === "pending") ? "Pending" : "all";
        activeFilters.search = "";
        $("#searchrptlvap").val("");
        applyFiltersAndRender("reset");
    };

    $(document).on("click", "#btnResetAllFilters", function() {
        window.resetLeaveSubFilters();
    });

    // -------------------------------------------------------------
    // EVENT LISTENERS: FILTERS & CONTROLS
    // -------------------------------------------------------------
    // Employee Dropdown change
    $(document).on("change", "#EmployeeListdrp", function() {
        if (isUpdatingDropdowns) return;
        activeFilters.employeeId = $(this).val() || "all";
        applyFiltersAndRender("employee");
    });

    // Leave Type Dropdown change
    $(document).on("change", "#filterLeaveType", function() {
        if (isUpdatingDropdowns) return;
        activeFilters.leaveType = $(this).val() || "all";
        applyFiltersAndRender("leaveType");
    });

    // Status Dropdown change
    $(document).on("change", "#filterLeaveStatus", function() {
        if (isUpdatingDropdowns) return;
        activeFilters.status = $(this).val() || "all";
        applyFiltersAndRender("status");
    });

    // Live search input
    $(document).on("input", "#searchrptlvap", function() {
        activeFilters.search = $(this).val() || "";
        applyFiltersAndRender("search");
    });

    // Year Dropdown change
    $(document).on("change", "#yearfilter", function() {
        loadLeaveData();
    });

    // Date Mode Switch (Month vs Custom Date)
    $(document).on("change", 'input[name="dateModeRadio"]', function() {
        let mode = $(this).val();
        if (mode === "month") {
            $("#monthFilterContainer").removeClass("d-none");
            $("#customDateRangeContainer").addClass("d-none");
            loadLeaveData();
        } else {
            $("#monthFilterContainer").addClass("d-none");
            $("#customDateRangeContainer").removeClass("d-none");
            // Set default date range if empty
            let y = $("#yearfilter").val() || new Date().getFullYear();
            let m = new Date().getMonth() + 1;
            let mm = m < 10 ? `0${m}` : `${m}`;
            let lastDay = new Date(y, m, 0).getDate();
            if (!$("#customDateFrom").val()) $("#customDateFrom").val(`${y}-${mm}-01`);
            if (!$("#customDateTo").val()) $("#customDateTo").val(`${y}-${mm}-${lastDay}`);
            loadLeaveData();
        }
    });

    // Month Selector change
    $(document).on("change", "#monthFilterSelect", function() {
        loadLeaveData();
    });

    // Custom Date Apply
    $(document).on("click", "#btnApplyCustomDate", function() {
        loadLeaveData();
    });

    // Navigation Tabs Click: 'Pending Leave' & 'Leave History'
    $(document).on("click", "#pendingleaveapproval", function() {
        currentMode = "pending";
        loadLeaveData();
    });

    $(document).on("click", "#historyofleaveapproval", function() {
        currentMode = "history";
        loadLeaveData();
    });

    // Reload Button
    $(document).on("click", "#btnReloadLeaveData", function() {
        loadLeaveData();
    });

    // -------------------------------------------------------------
    // ON PAGE READY
    // -------------------------------------------------------------
    $(document).ready(function() {
        if ($.fn.select2) {
            $("#EmployeeListdrp").select2({
                placeholder: "Search & Select Employee",
                allowClear: false,
                width: '100%'
            });
        }

        // Preload company leave types list
        loadCompanyLeaveTypes();

        initYearFilter(function() {
            loadLeaveData();
        });
    });

})();
</script>
