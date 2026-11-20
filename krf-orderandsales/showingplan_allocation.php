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
    $modulename = $dddr[0]['modulename'];
}
?>
<!-- Google Fonts: Outfit & Plus Jakarta Sans -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome & Bootstrap Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
    :root {
        --primary-green: #1b9e77;
        --secondary-green: #34d399;
        --dark-green: #065f46;
        --light-green: #ecfdf5;
        --accent-indigo: #4f46e5;
        --accent-orange: #f59e0b;
        --glass-bg: rgba(255, 255, 255, 0.95);
        --glass-border: rgba(226, 232, 240, 0.8);
        --card-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.06);
        --text-dark: #0f172a;
        --text-muted: #64748b;
        --border-color: #e2e8f0;
    }

    body {
        font-family: 'Plus Jakarta Sans', 'Outfit', sans-serif;
        background-color: #f1f5f9;
        color: var(--text-dark);
    }

    .premium-container {
        padding: 0.75rem;
        max-width: 1480px;
        margin: auto;
    }

    /* --- Page Header Card --- */
    .page-header-card {
        background: linear-gradient(135deg, #107e5e 0%, #10b981 100%);
        border-radius: 16px;
        padding: 0.85rem 1.25rem;
        color: white;
        margin-bottom: 0.85rem;
        box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.3);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.75rem;
    }

    .page-header-card h5 {
        font-weight: 800;
        margin: 0;
        letter-spacing: -0.3px;
        font-size: 1.2rem;
    }

    .header-icon-circle {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(8px);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        color: white;
    }

    .btn-header-action {
        background: rgba(255, 255, 255, 0.18);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.35);
        color: white;
        padding: 0.45rem 0.9rem;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.82rem;
        transition: all 0.25s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .btn-header-action:hover {
        background: white;
        color: var(--primary-green);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    /* --- Summary KPI Cards --- */
    .kpi-summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: 0.75rem;
        margin-bottom: 0.85rem;
    }

    .kpi-card-summary {
        background: white;
        border-radius: 14px;
        padding: 0.85rem 1rem;
        border: 1px solid var(--border-color);
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .kpi-card-summary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.08);
    }

    .kpi-content small {
        display: block;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--text-muted);
        letter-spacing: 0.5px;
        margin-bottom: 2px;
    }

    .kpi-content h4 {
        margin: 0;
        font-size: 1.45rem;
        font-weight: 800;
        color: var(--text-dark);
        line-height: 1.1;
    }

    .kpi-badge-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
    }

    .bg-icon-warning { background: #fef3c7; color: #d97706; }
    .bg-icon-success { background: #d1fae5; color: #059669; }
    .bg-icon-info { background: #e0f2fe; color: #0284c7; }
    .bg-icon-purple { background: #f3e8ff; color: #7e22ce; }

    /* --- Content Card --- */
    .content-card {
        background: white;
        border-radius: 18px;
        padding: 1.15rem;
        box-shadow: var(--card-shadow);
        border: 1px solid var(--border-color);
        margin-bottom: 1.25rem;
    }

    /* --- Filter & Action Bar --- */
    .filter-toolbar {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 0.75rem 1rem;
        margin-bottom: 1rem;
        display: flex;
        flex-direction: column;
        gap: 0.65rem;
    }

    .filter-pills-scroll {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        overflow-x: auto;
        padding-bottom: 4px;
        scrollbar-width: thin;
    }

    .date-filter-pill {
        white-space: nowrap;
        background: white;
        border: 1.5px solid #cbd5e1;
        color: #334155;
        border-radius: 20px;
        padding: 0.35rem 0.85rem;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .date-filter-pill:hover {
        border-color: var(--primary-green);
        color: var(--primary-green);
        background: var(--light-green);
    }

    .date-filter-pill.active {
        background: var(--primary-green);
        border-color: var(--primary-green);
        color: white;
        box-shadow: 0 4px 10px rgba(27, 158, 119, 0.3);
    }

    .date-filter-pill .pill-badge {
        background: rgba(0, 0, 0, 0.08);
        border-radius: 10px;
        padding: 1px 6px;
        font-size: 0.7rem;
    }

    .date-filter-pill.active .pill-badge {
        background: rgba(255, 255, 255, 0.3);
        color: white;
    }

    .search-input-group {
        position: relative;
        flex: 1;
        min-width: 260px;
    }

    .search-input-group i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
        font-size: 0.95rem;
    }

    .search-input-control {
        width: 100%;
        padding: 0.45rem 0.85rem 0.45rem 2.2rem;
        border-radius: 10px;
        border: 1.5px solid #cbd5e1;
        font-size: 0.85rem;
        font-weight: 500;
        transition: all 0.2s ease;
        background: white;
    }

    .search-input-control:focus {
        border-color: var(--primary-green);
        box-shadow: 0 0 0 3px rgba(27, 158, 119, 0.15);
        outline: none;
    }

    /* --- Date Group Header --- */
    .date-group-section {
        margin-bottom: 1.25rem;
    }

    .date-group-header {
        background: linear-gradient(90deg, #f1f5f9 0%, #ffffff 100%);
        border-left: 5px solid var(--primary-green);
        border-radius: 10px;
        padding: 0.55rem 1rem;
        margin-bottom: 0.65rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.5rem;
        border-top: 1px solid #e2e8f0;
        border-right: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
    }

    .date-group-title {
        font-size: 0.95rem;
        font-weight: 800;
        color: var(--text-dark);
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
    }

    .date-group-badges {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }

    /* --- Strips Layout --- */
    .strips-header {
        position: sticky !important;
        top: 68px !important;
        z-index: 99;
        display: grid;
        grid-template-columns: 110px 1.4fr 1fr 1.3fr 1fr 1.6fr 1.2fr 1.2fr;
        padding: 0.65rem 1.15rem;
        color: #475569;
        font-size: 0.72rem;
        text-transform: uppercase;
        font-weight: 800;
        letter-spacing: 0.5px;
        background: #e2e8f0 !important;
        border-radius: 10px;
        margin-bottom: 0.6rem;
        align-items: center;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
    }

    .order-strip-card {
        background: white;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        margin-bottom: 0.65rem;
        transition: all 0.25s ease;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.03);
        overflow: hidden;
    }

    .order-strip-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.07);
        transform: translateY(-1px);
    }

    .order-strip-main {
        display: grid;
        grid-template-columns: 110px 1.4fr 1fr 1.3fr 1fr 1.6fr 1.2fr 1.2fr;
        align-items: center;
        padding: 0.75rem 1.15rem;
        text-align: center;
        gap: 0.5rem;
    }

    .btn-allocate-action {
        background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
        color: white;
        border: none;
        padding: 0.42rem 0.85rem;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.8rem;
        transition: all 0.25s ease;
        box-shadow: 0 4px 10px rgba(79, 70, 229, 0.25);
        display: inline-flex;
        align-items: center;
        gap: 5px;
        width: 100%;
        justify-content: center;
    }

    .btn-allocate-action:hover {
        transform: scale(1.03);
        box-shadow: 0 6px 15px rgba(79, 70, 229, 0.35);
        color: white;
    }

    .btn-expand-allocated {
        font-size: 0.73rem;
        font-weight: 700;
        padding: 0.28rem 0.65rem;
        border-radius: 8px;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
    }

    .btn-expand-allocated .chevron-icon {
        transition: transform 0.25s ease;
    }

    .btn-expand-allocated[aria-expanded="true"] .chevron-icon {
        transform: rotate(180deg);
    }

    /* --- Expandable Details Container --- */
    .allocated-expand-details {
        background: #f8fafc;
        border-top: 1px dashed #cbd5e1;
        padding: 0.85rem 1.15rem;
        border-bottom-left-radius: 12px;
        border-bottom-right-radius: 12px;
    }

    .expand-header-banner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 0.65rem;
    }

    .expand-header-title {
        font-size: 0.82rem;
        font-weight: 800;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .allocated-boxes-table {
        width: 100%;
        font-size: 0.78rem;
        background: white;
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        margin: 0;
    }

    .allocated-boxes-table th {
        background: #f1f5f9;
        color: #475569;
        font-weight: 700;
        padding: 0.5rem 0.75rem;
        font-size: 0.72rem;
        text-transform: uppercase;
        border-bottom: 1px solid #e2e8f0;
    }

    .allocated-boxes-table td {
        padding: 0.5rem 0.75rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        color: #1e293b;
    }

    .allocated-boxes-table tr:last-child td {
        border-bottom: none;
    }

    .qr-badge {
        font-family: monospace;
        font-weight: 700;
        background: #e0f2fe;
        color: #0369a1;
        border: 1px solid #bae6fd;
        padding: 2px 7px;
        border-radius: 6px;
        display: inline-block;
    }

    .polyhouse-badge {
        font-weight: 700;
        background: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
        padding: 3px 8px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    /* Modal Tweaks */
    .allocation-modal-content {
        border: none;
        border-radius: 20px;
        overflow: hidden;
    }

    .allocation-modal-header {
        background: linear-gradient(135deg, #107e5e 0%, #10b981 100%);
        padding: 1rem;
        border: none;
    }

    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
        gap: 0.65rem;
        margin-bottom: 0.85rem;
    }

    .kpi-mini-card {
        background: white;
        border-radius: 12px;
        padding: 0.5rem 0.35rem;
        text-align: center;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
        border: 1px solid #f1f5f9;
    }

    .kpi-mini-card small {
        display: block;
        color: var(--text-muted);
        font-weight: 700;
        margin-bottom: 2px;
        text-transform: uppercase;
        font-size: 0.68rem;
    }

    .kpi-mini-card h4 {
        margin: 0;
        font-weight: 800;
        color: var(--primary-green);
        font-size: 1.25rem;
    }

    .pending-boxes-chips-bar {
        background: white;
        border-radius: 12px;
        padding: 0.65rem 0.85rem;
        border: 1px solid #e2e8f0;
        margin-bottom: 0.85rem;
    }

    .box-chip {
        cursor: pointer;
        transition: all 0.2s ease;
        border: 1.5px solid #cbd5e1;
        background: #f8fafc;
        border-radius: 8px;
        padding: 4px 8px;
        font-size: 0.78rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .box-chip:hover {
        border-color: var(--primary-green);
        background: #ecfdf5;
        color: var(--primary-green);
        transform: translateY(-1px);
    }

    .scanner-interface {
        background: #f8fafc;
        border-radius: 16px;
        padding: 1.15rem;
        border: 2px dashed #cbd5e1;
        text-align: center;
        transition: all 0.3s ease;
    }

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

    #AllocationReaderId video {
        border-radius: 14px;
        object-fit: cover;
    }

    .scanner-overlay {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        z-index: 5;
        pointer-events: none;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .scan-region-highlight {
        position: absolute;
        width: 230px;
        height: 230px;
        border: 2px solid rgba(255, 255, 255, 0.4);
        border-radius: 20px;
        box-shadow: 0 0 0 4000px rgba(0, 0, 0, 0.5);
    }

    .scan-line {
        position: absolute;
        width: 100%;
        height: 4px;
        background: linear-gradient(to right, transparent, var(--primary-green), transparent);
        box-shadow: 0 0 15px var(--primary-green);
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
        width: 22px;
        height: 22px;
        border: 4px solid var(--primary-green);
        z-index: 11;
    }

    .corner-tl { top: 0; left: 0; border-right: none; border-bottom: none; border-top-left-radius: 8px; }
    .corner-tr { top: 0; right: 0; border-left: none; border-bottom: none; border-top-right-radius: 8px; }
    .corner-bl { bottom: 0; left: 0; border-right: none; border-top: none; border-bottom-left-radius: 8px; }
    .corner-br { bottom: 0; right: 0; border-left: none; border-top: none; border-bottom-right-radius: 8px; }

    .scan-success-flash {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(34, 197, 94, 0.3);
        z-index: 20;
        display: none;
        animation: flashAnim 0.5s ease-out;
    }

    @keyframes flashAnim {
        0% { opacity: 1; }
        100% { opacity: 0; }
    }

    .submit-btn-premium {
        background: linear-gradient(135deg, var(--primary-green) 0%, var(--secondary-green) 100%);
        border: none;
        color: white;
        padding: 0.6rem 1.75rem;
        border-radius: 12px;
        font-weight: 700;
        font-size: 0.95rem;
        box-shadow: 0 10px 20px rgba(27, 158, 119, 0.2);
        transition: all 0.25s ease;
    }

    .submit-btn-premium:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 25px rgba(27, 158, 119, 0.3);
    }

    /* Modal Width on Desktop */
    @media (min-width: 992px) {
        .modal-90w {
            width: 96%;
            max-width: 1400px;
        }
    }

    /* --- Responsive Mobile View --- */
    @media (max-width: 991.98px) {
        .strips-header {
            display: none !important;
        }

        .order-strip-main {
            grid-template-columns: 1fr 1fr;
            text-align: left;
            gap: 0.75rem;
            padding: 1rem;
        }

        .strip-item.action-col {
            grid-column: span 2;
            margin-bottom: 0.25rem;
        }

        .strip-item.product-col {
            grid-column: span 2;
        }

        .strip-item.allocated-col {
            grid-column: span 2;
        }
    }
</style>

<div class="premium-container">
    <div id="printparameterdiv"></div>
    <select id="GboxOpeningDateList" style="display:none;"></select>

    <!-- Page Header Card -->
    <div class="page-header-card">
        <div class="d-flex align-items-center gap-3">
            <div class="header-icon-circle">
                <i class="bi bi-box-seam"></i>
            </div>
            <div>
                <h5>Allocate Germinated Boxes to Polyhouse</h5>
                <div class="small opacity-85">Real-time box allocation, multi-date tracking & polyhouse assignment</div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-header-action" onclick="openDirectScanner();">
                <i class="bi bi-qr-code-scan"></i> Quick QR Scan
            </button>
            <button class="btn btn-header-action" id="btnRefreshWorkspace" onclick="reloadAllocationWorkspace();">
                <i class="fa fa-sync" id="refreshIcon"></i> Refresh Workspace
            </button>
        </div>
    </div>

    <!-- KPI Summary Dashboard -->
    <div class="kpi-summary-grid">
        <div class="kpi-card-summary">
            <div class="kpi-content">
                <small>Pending Boxes</small>
                <h4 id="kpiTotalPendingBoxes" class="text-warning-emphasis">0</h4>
            </div>
            <div class="kpi-badge-icon bg-icon-warning">
                <i class="bi bi-box-seam"></i>
            </div>
        </div>
        <div class="kpi-card-summary">
            <div class="kpi-content">
                <small>Pending Trays</small>
                <h4 id="kpiTotalPendingTrays" class="text-success">0</h4>
            </div>
            <div class="kpi-badge-icon bg-icon-success">
                <i class="bi bi-grid-3x3"></i>
            </div>
        </div>
        <div class="kpi-card-summary">
            <div class="kpi-content">
                <small>Active Opening Dates</small>
                <h4 id="kpiTotalDates" class="text-info">0</h4>
            </div>
            <div class="kpi-badge-icon bg-icon-info">
                <i class="bi bi-calendar3-event"></i>
            </div>
        </div>
        <div class="kpi-card-summary">
            <div class="kpi-content">
                <small>Allocated Boxes</small>
                <h4 id="kpiTotalAllocatedBoxes" class="text-purple">0</h4>
            </div>
            <div class="kpi-badge-icon bg-icon-purple">
                <i class="bi bi-houses"></i>
            </div>
        </div>
    </div>

    <!-- Main Content Card -->
    <div class="content-card">
        <!-- Filter Toolbar -->
        <div class="filter-toolbar">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="search-input-group">
                    <i class="bi bi-search"></i>
                    <input type="text" id="filterSearchInput" class="search-input-control" placeholder="Search Order No (#KFP), Product Name, Root Type, Polyhouse or QR Code...">
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-sm btn-outline-secondary rounded-3 fw-bold" id="btnToggleAllAllocated">
                        <i class="bi bi-arrows-expand me-1"></i> Expand All Polyhouse Details
                    </button>
                    <span class="text-muted small d-none d-lg-inline">
                        <i class="bi bi-sort-down me-1 text-primary"></i> Sorted by Opening Date (Newest First)
                    </span>
                </div>
            </div>

            <!-- Date Filter Pills (Opening Date Wise DESC) -->
            <div class="d-flex align-items-center gap-2 pt-1 border-top">
                <span class="small fw-bold text-muted text-uppercase" style="font-size: 0.7rem; white-space: nowrap;">
                    <i class="bi bi-funnel me-1"></i> Date Filter:
                </span>
                <div class="filter-pills-scroll" id="dateFilterPillsContainer">
                    <button class="date-filter-pill active" data-date="all">
                        <i class="bi bi-layers-fill"></i> All Dates <span class="pill-badge" id="pillAllCount">0</span>
                    </button>
                    <!-- Injected dynamically by JS -->
                </div>
            </div>
        </div>

        <!-- Desktop Header Columns -->
        <div class="strips-header">
            <div>Action</div>
            <div>Product Name</div>
            <div>Order Number</div>
            <div>PK Date / OP Date</div>
            <div>Total Boxes</div>
            <div>Allocated Boxes & Polyhouse</div>
            <div>Received By</div>
            <div>Allocated By</div>
        </div>

        <!-- Grouped Order Strips Container -->
        <div class="order-strips-container" id="gboxesallocatetopolyhouseList">
            <div class="text-center py-5">
                <div class="spinner-border text-success" role="status"></div>
                <div class="text-muted small mt-2">Loading pending allocation boxes...</div>
            </div>
        </div>
    </div>
</div>

<!-- ALLOCATION MODAL -->
<div class="modal fade" id="allocationToPHModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-90w modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content allocation-modal-content">

            <div class="modal-header allocation-modal-header text-white border-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-white text-success p-3 rounded-4 shadow-sm">
                        <i class="bi bi-qr-code-scan fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold">Live Polyhouse Allocation System</h5>
                        <div class="small opacity-85">
                            <span class="me-3"><i class="bi bi-tag-fill me-1"></i> <span id="productNamemdalc">...</span></span>
                            <span class="me-3"><i class="bi bi-hash me-1"></i> <span id="productOrderNoalc">...</span></span>
                            <span class="me-3"><i class="bi bi-layers-fill me-1"></i> <span id="roottypemdalc">...</span></span>
                            <span><i class="bi bi-calendar-event me-1"></i> <span id="openingDatemdalc">...</span></span>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" onclick="stopScanner()"></button>
            </div>

            <div class="modal-body p-3 p-md-4 bg-light">
                <!-- Pending Boxes Quick-Pick Bar in this Batch (Collapsed by Default) -->
                <div class="pending-boxes-chips-bar shadow-sm" id="modalPendingBoxesChipsBar" style="display:none;">
                    <div class="d-flex align-items-center justify-content-between py-1 cursor-pointer" 
                         id="headingPendingBoxes" 
                         data-bs-toggle="collapse" 
                         data-bs-target="#modalPendingBoxesCollapse" 
                         aria-expanded="false" 
                         aria-controls="modalPendingBoxesCollapse"
                         style="cursor: pointer; user-select: none;">
                        <span class="small fw-bold text-muted text-uppercase" style="font-size: 0.73rem;">
                            <i class="bi bi-boxes me-1 text-primary"></i> Pending Boxes in this Batch (Click to Select):
                        </span>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-bold" id="modalPendingBoxesCountBadge" style="font-size: 0.72rem;">0 boxes</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 fw-semibold" style="font-size: 0.72rem; border-radius: 8px;">
                                <span id="togglePendingText">Show Boxes</span>
                                <i class="bi bi-chevron-down ms-1" id="togglePendingIcon"></i>
                            </button>
                        </div>
                    </div>
                    <div class="collapse mt-2 pt-2 border-top" id="modalPendingBoxesCollapse">
                        <div class="d-flex flex-wrap gap-2" id="modalPendingBoxesChipsContainer" style="max-height: 220px; overflow-y: auto;"></div>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Left Side: Scanner -->
                    <div class="col-lg-6">
                        <div id="allocationqrcodescannerdiv" class="scanner-interface h-100 shadow-sm border-white">
                            <div class="mb-3 text-muted">
                                <i class="bi bi-camera fs-2 d-block mb-1 text-primary"></i>
                                <span class="fw-bold small">Optical Scanner Engine</span>
                            </div>

                            <div class="reader-container mb-3">
                                <div id="AllocationReaderId"></div>
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

                            <!-- Camera Selection Dropdown -->
                            <div id="cameraSelectDiv" class="mt-2 px-2 mx-auto" style="max-width: 320px; display: none;">
                                <label class="filter-label text-start mb-1" style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted);">
                                    <i class="bi bi-camera-fill me-1 text-primary"></i> Choose Camera
                                </label>
                                <select id="cameraSelection" class="form-select border-primary-subtle shadow-sm" style="border-radius: 10px; height: 38px; font-size: 0.85rem; font-weight: 500;"></select>
                            </div>

                            <!-- Real-Time Scanner Status Message -->
                            <div id="scannerStatusDiv" class="mt-2 text-center" style="display:none;">
                                <span id="scannerStatus" class="badge bg-light text-secondary shadow-sm border py-2 px-3" style="border-radius: 20px; font-size: 0.85rem; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
                                    <i class="bi bi-broadcast text-danger pulse"></i><span>Scanner Active: Looking for QR Code...</span>
                                </span>
                            </div>

                            <div class="d-flex justify-content-center gap-2 mt-3">
                                <button id="AllocationstartScan" class="btn btn-success btn-lg rounded-4 px-4">
                                    <i class="bi bi-play-fill me-2"></i>Activate Camera
                                </button>
                                <button id="AllocationstopScan" class="btn btn-danger btn-lg rounded-4 px-4" style="display:none;">
                                    <i class="bi bi-stop-fill me-2"></i>Turn Off
                                </button>
                            </div>

                            <button id="ManualAllocationstartScan" class="btn btn-link text-primary mt-3 text-decoration-none fw-bold small">
                                <i class="bi bi-keyboard me-1"></i> Prefer Manual Entry?
                            </button>
                        </div>
                    </div>

                    <!-- Right Side: Details & Input -->
                    <div class="col-lg-6">
                        <!-- Manual Entry -->
                        <div id="manualQrCodeSearchDiv" class="manual-entry-card shadow-sm border-0 p-3 mb-3 bg-white rounded-4 border" style="display:none;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="m-0 fw-bold text-warning-emphasis"><i class="fa fa-keyboard me-2"></i>Manual QR Capture</h6>
                                <button id="StartMobileScanner" class="btn btn-warning btn-sm rounded-3 fw-bold">
                                    <i class="bi bi-camera me-1"></i> Return to Camera
                                </button>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-2 border-end-0 rounded-start-4">
                                    <i class="bi bi-upc-scan text-warning"></i>
                                </span>
                                <input type="text" id="manualQrCode" class="form-control border-2 border-start-0 py-3 input-glass" placeholder="Scan or Type QR Code (e.g. B6424E3)...">
                                <button id="fetchManual" class="btn btn-warning rounded-end-4 px-4 fw-bold">FETCH</button>
                            </div>
                        </div>

                        <!-- Live Interaction Area -->
                        <div id="liveFeedbackArea" class="mt-0">
                            <!-- Dashboard Stats -->
                            <div class="kpi-grid">
                                <div class="kpi-mini-card">
                                    <small>Box Allocation</small>
                                    <h4 id="GerminatedBoxCount">0 / 0</h4>
                                </div>
                                <div class="kpi-mini-card">
                                    <small>Trays in Box</small>
                                    <h4 id="germTrayCountinbox" class="text-info">0</h4>
                                </div>
                                <div class="kpi-mini-card">
                                    <small>Status</small>
                                    <h4 id="statusalc" class="text-warning text-uppercase">PENDING</h4>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            <div class="progress-wrapper shadow-sm border border-white mb-3 p-3 bg-white rounded-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold small text-muted">Batch Allocation Progress</span>
                                    <span id="allocationPercentTxt" class="badge bg-success-subtle text-success rounded-pill fw-bold">0%</span>
                                </div>
                                <div class="progress" style="height: 10px; border-radius: 8px;">
                                    <div id="allocationProgressBar" class="progress-bar bg-success progress-bar-striped progress-bar-animated" style="width:0%"></div>
                                </div>
                            </div>

                            <div id="noResult" class="alert alert-warning border-0 rounded-4 text-center p-3 d-none">
                                <i class="bi bi-exclamation-triangle fs-4 d-block mb-1"></i>
                                No matching germination box found. Please verify QR code.
                            </div>

                            <!-- Dynamic Box Details & Polyhouse Target Input -->
                            <div id="AllocationBoxDetailsInfoToPH" style="display:none;"></div>
                        </div>
                    </div>
                </div>

                <!-- BEEP AUDIO -->
                <audio id="beepSound" preload="auto">
                    <source src="https://actions.google.com/sounds/v1/ui/confirmation_tone.ogg" type="audio/ogg">
                </audio>
            </div>

            <div class="modal-footer bg-white border-0 py-3 px-4 shadow-sm" style="display:none;" id="allocationModalFooter">
                <button id="unlockScanner" class="btn btn-outline-secondary rounded-4 px-4 fw-bold">
                    <i class="bi bi-unlock me-2"></i>New Scan
                </button>
                <button id="toggleFlash" class="btn btn-outline-warning rounded-4 px-4 fw-bold">
                    <i class="bi bi-flashlight me-2"></i>Torch
                </button>
            </div>

        </div>
    </div>
</div>

<?php include('../footer.php'); ?>
<script type="text/javascript" src="<?php echo $prefix; ?>krf-orderandsales/js/pjs_function.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- QR Scanner (html5-qrcode) -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>
    const currentProjectId = "<?php echo (int)($projectid ?? 0); ?>";
    const currentCompanyId = "<?php echo (int)($companyid ?? 0); ?>";
    const currentSubCompanyId = "<?php echo (int)($subcompanyid ?? 0); ?>";
    const currentLoginId = "<?php echo (int)($_SESSION['loginid'] ?? ($loginid ?? 0)); ?>";
    let qrReader = null;
    let isScanning = false;
    let scanLocked = false;
    let torchOn = false;
    let isCameraListLoaded = false;
    let globalAudioCtx = null;
    let lastScannedCode = null;
    let scanLockTimeout = null;

    // Cache of full allocation data for instantaneous search and pill filtering
    let masterAllocationData = [];
    let activeFilterDate = 'all';

    // -------------------------------------------------------------
    // 1. Core Fetch & Render Function (Opening Date Wise DESC)
    // -------------------------------------------------------------
    function loadAllocationWorkspace(selectedDate = 'all') {
        const projectid = currentProjectId || $("body").data("projectid") || $("#hidden_projectid").val() || 0;
        const companyid = currentCompanyId || $("body").data("companyid") || $("#hidden_companyid").val() || 0;
        const subcompanyid = currentSubCompanyId || $("body").data("subcompanyid") || $("#hidden_subcompanyid").val() || 0;

        $("#refreshIcon").addClass("fa-spin");

        $.ajax({
            type: 'POST',
            url: 'modal/product_modal.php?action=GetPendingAllocationGBoxesDataWithDate',
            data: {
                companyid: companyid,
                subcompanyid: subcompanyid,
                projectid: projectid,
                datefrom: selectedDate,
                dateto: selectedDate
            },
            dataType: 'json',
            success: function (res) {
                $("#refreshIcon").removeClass("fa-spin");
                masterAllocationData = Array.isArray(res) ? res : [];
                updateKPISummaries(masterAllocationData);
                renderDateFilterPills(masterAllocationData);
                renderGroupedAllocationStrips(masterAllocationData);
            },
            error: function (xhr, status, error) {
                console.warn("Direct relative AJAX failed, attempting fallback...", status, error);
                // Fallback attempt via pjs_function
                GetPendingAllocationGBoxesDataWithDate(selectedDate, selectedDate, function (fallbackRes) {
                    $("#refreshIcon").removeClass("fa-spin");
                    if (Array.isArray(fallbackRes)) {
                        masterAllocationData = fallbackRes;
                        updateKPISummaries(masterAllocationData);
                        renderDateFilterPills(masterAllocationData);
                        renderGroupedAllocationStrips(masterAllocationData);
                    } else {
                        $("#gboxesallocatetopolyhouseList").html(`
                            <div class="text-center py-5 text-danger">
                                <i class="bi bi-exclamation-octagon fs-1 d-block mb-2"></i>
                                Failed to load pending allocation data. Please click Refresh Workspace.
                            </div>
                        `);
                    }
                });
            }
        });
    }

    function reloadAllocationWorkspace() {
        loadAllocationWorkspace(activeFilterDate);
    }

    // -------------------------------------------------------------
    // 2. KPI Summary Calculation
    // -------------------------------------------------------------
    function updateKPISummaries(data) {
        let totalPendingBoxes = 0;
        let totalPendingTrays = 0;
        let totalAllocatedBoxes = 0;
        let distinctDates = new Set();

        data.forEach(item => {
            totalPendingBoxes += parseInt(item.pending_boxes_count || 0);
            totalPendingTrays += parseInt(item.pending_trays_count || 0);
            totalAllocatedBoxes += parseInt(item.allocated_boxes_count || 0);
            if (item.boxopeningdate) {
                distinctDates.add(item.boxopeningdate);
            }
        });

        $("#kpiTotalPendingBoxes").text(totalPendingBoxes.toLocaleString());
        $("#kpiTotalPendingTrays").text(totalPendingTrays.toLocaleString());
        $("#kpiTotalDates").text(distinctDates.size + ' Dates');
        $("#kpiTotalAllocatedBoxes").text(totalAllocatedBoxes.toLocaleString());
        $("#pillAllCount").text(totalPendingBoxes);
    }

    // -------------------------------------------------------------
    // 3. Render Date Filter Pills (Opening Date Wise DESC)
    // -------------------------------------------------------------
    function renderDateFilterPills(data) {
        // Group pending counts by date
        const dateMap = {};
        data.forEach(item => {
            const date = item.boxopeningdate;
            if (!date) return;
            if (!dateMap[date]) {
                dateMap[date] = {
                    date: date,
                    dsp: item.boxopeningdate_dsp || date,
                    day: item.boxopeningdate_day || '',
                    pendingCount: 0
                };
            }
            dateMap[date].pendingCount += parseInt(item.pending_boxes_count || 0);
        });

        // Sorted by date DESC
        const sortedDates = Object.keys(dateMap).sort().reverse();

        let pillsHtml = `
            <button class="date-filter-pill ${activeFilterDate === 'all' ? 'active' : ''}" data-date="all">
                <i class="bi bi-layers-fill"></i> All Dates <span class="pill-badge">${$("#kpiTotalPendingBoxes").text()}</span>
            </button>
        `;

        sortedDates.forEach(d => {
            const info = dateMap[d];
            const isActive = activeFilterDate === d ? 'active' : '';
            pillsHtml += `
                <button class="date-filter-pill ${isActive}" data-date="${d}">
                    <i class="bi bi-calendar-event"></i> ${info.dsp} <span class="pill-badge">${info.pendingCount}</span>
                </button>
            `;
        });

        $("#dateFilterPillsContainer").html(pillsHtml);
    }

    // -------------------------------------------------------------
    // 4. Render Grouped Strips with Expandable Polyhouse Details
    // -------------------------------------------------------------
    function renderGroupedAllocationStrips(data) {
        const searchVal = $("#filterSearchInput").val().toLowerCase().trim();

        // Filter by date pill if not 'all'
        let filtered = data;
        if (activeFilterDate !== 'all') {
            filtered = filtered.filter(item => item.boxopeningdate === activeFilterDate);
        }

        // Filter by search query
        if (searchVal) {
            filtered = filtered.filter(item => {
                const searchStr = `${item.stockorderno} ${item.productname} ${item.roottype} ${item.sowinglotno_internal} ${item.allocated_polyhouses} ${item.boxopeningdate_dsp}`.toLowerCase();
                if (searchStr.includes(searchVal)) return true;
                // Check in pending boxes QR codes
                if (Array.isArray(item.boxes) && item.boxes.some(b => (b.qrcode || '').toLowerCase().includes(searchVal))) return true;
                // Check in allocated boxes QR codes or polyhouses
                if (Array.isArray(item.allocated_boxes) && item.allocated_boxes.some(b => (b.qrcode || '').toLowerCase().includes(searchVal) || (b.polyhousename || '').toLowerCase().includes(searchVal))) return true;
                return false;
            });
        }

        if (filtered.length === 0) {
            $("#gboxesallocatetopolyhouseList").html(`
                <div class="text-center py-5">
                    <i class="bi bi-inbox fs-1 d-block mb-2 text-muted opacity-50"></i>
                    <h6 class="fw-bold text-muted">No pending allocation boxes found</h6>
                    <small class="text-muted">${searchVal ? 'Try modifying your search criteria' : 'All boxes have been allocated or no records exist'}</small>
                </div>
            `);
            return;
        }

        // Group by boxopeningdate (already in DESC order)
        const dateGroups = {};
        filtered.forEach(item => {
            const date = item.boxopeningdate || 'No Date';
            if (!dateGroups[date]) {
                dateGroups[date] = [];
            }
            dateGroups[date].push(item);
        });

        let outputHtml = '';
        let globalIndex = 0;

        Object.keys(dateGroups).forEach(dateKey => {
            const itemsInDate = dateGroups[dateKey];
            const dateDsp = itemsInDate[0].boxopeningdate_dsp || dateKey;
            const dateDay = itemsInDate[0].boxopeningdate_day || '';
            
            let datePendingBoxes = 0;
            let datePendingTrays = 0;
            let dateAllocatedBoxes = 0;
            itemsInDate.forEach(it => {
                datePendingBoxes += parseInt(it.pending_boxes_count || 0);
                datePendingTrays += parseInt(it.pending_trays_count || 0);
                dateAllocatedBoxes += parseInt(it.allocated_boxes_count || 0);
            });

            outputHtml += `
                <div class="date-group-section">
                    <!-- Date Header Banner -->
                    <div class="date-group-header">
                        <div class="date-group-title">
                            <i class="bi bi-calendar-check-fill text-success fs-5"></i>
                            <span>${dateDsp}</span>
                            ${dateDay ? `<span class="badge bg-light text-secondary border fw-600">${dateDay}</span>` : ''}
                        </div>
                        <div class="date-group-badges">
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle py-1 px-2 fw-700">
                                <i class="bi bi-box-seam me-1"></i>${datePendingBoxes} Pending (${datePendingTrays} Trays)
                            </span>
                            ${dateAllocatedBoxes > 0 ? `
                                <span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2 fw-700">
                                    <i class="bi bi-check2-circle me-1"></i>${dateAllocatedBoxes} Allocated
                                </span>` : ''}
                            <span class="badge bg-light text-dark border py-1 px-2 fw-600">
                                ${itemsInDate.length} ${itemsInDate.length === 1 ? 'Order' : 'Orders'}
                            </span>
                        </div>
                    </div>
            `;

            // Render strips under this date
            itemsInDate.forEach(item => {
                globalIndex++;
                const boxesdetails = Array.isArray(item.boxes) ? item.boxes : [];
                const allocatedBoxes = Array.isArray(item.allocated_boxes) ? item.allocated_boxes : [];

                const actionBtn = item.pending_boxes_count > 0
                    ? `<button class="btn btn-allocate-action allocateghbtntd" 
                               data-gboxdata="${encodeURIComponent(JSON.stringify(boxesdetails))}"
                               data-allocateddata="${encodeURIComponent(JSON.stringify(allocatedBoxes))}">
                            <i class="bi bi-arrow-right-circle"></i> Allocate (${item.pending_boxes_count})
                       </button>`
                    : `<span class="badge bg-secondary-subtle text-secondary px-3 py-2 rounded-3 fw-bold">Completed</span>`;

                // Allocated boxes expand button
                let allocatedDisplayHtml = '';
                if (allocatedBoxes.length > 0) {
                    allocatedDisplayHtml = `
                        <div>
                            <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold px-2 py-1">
                                <i class="bi bi-check-circle me-1"></i>${item.allocated_boxes_count} Allocated (${item.allocated_trays_count} Trays)
                            </span>
                            ${item.allocated_polyhouses ? `
                                <div class="small text-primary fw-bold text-truncate mt-1" title="${item.allocated_polyhouses}">
                                    <i class="bi bi-house-door me-1"></i>${item.allocated_polyhouses}
                                </div>` : ''}
                            <button class="btn btn-sm btn-outline-primary btn-expand-allocated mt-1 w-100" 
                                    type="button" 
                                    data-bs-toggle="collapse" 
                                    data-bs-target="#expandAllocDetails_${globalIndex}" 
                                    aria-expanded="false">
                                <i class="bi bi-diagram-3"></i> View Polyhouse Details 
                                <i class="bi bi-chevron-down chevron-icon"></i>
                            </button>
                        </div>
                    `;
                } else {
                    allocatedDisplayHtml = `
                        <span class="badge bg-light text-muted border px-2 py-1">0 Allocated</span>
                        <small class="text-muted d-block mt-1">Pending allocation</small>
                    `;
                }

                // Render allocated box details table if any
                let allocatedDetailsTableHtml = '';
                if (allocatedBoxes.length > 0) {
                    let rowsHtml = '';
                    allocatedBoxes.forEach((ab, abIdx) => {
                        const isDiffDate = ab.boxopeningdate && ab.boxopeningdate !== item.boxopeningdate;
                        rowsHtml += `
                            <tr>
                                <td><b>#${abIdx + 1}</b></td>
                                <td>
                                    <span class="qr-badge"><i class="bi bi-qr-code me-1"></i>${ab.qrcode}</span>
                                    <small class="text-muted d-block" style="font-size: 0.68rem;">Box #${ab.boxid}</small>
                                </td>
                                <td>
                                    <span class="fw-bold ${isDiffDate ? 'text-purple' : 'text-dark'}">${ab.boxopeningdate_dsp || ab.boxopeningdate}</span>
                                    ${isDiffDate ? `<span class="badge bg-purple-subtle text-purple ms-1" style="font-size: 0.65rem;">Alternate Date</span>` : ''}
                                </td>
                                <td>
                                    <span class="polyhouse-badge">
                                        <i class="bi bi-house-fill"></i> ${ab.polyhousename}
                                    </span>
                                    ${ab.physicallocation ? `<small class="text-muted d-block" style="font-size: 0.68rem;">Loc: ${ab.physicallocation}</small>` : ''}
                                </td>
                                <td><span class="badge bg-light text-dark border fw-bold">${ab.traycountinbox} Trays</span></td>
                                <td>
                                    <span class="badge ${ab.status === 'ReceivedAtPolyhouse' ? 'bg-success' : 'bg-primary'} text-white" style="font-size: 0.7rem;">
                                        ${ab.status}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold small">${ab.allocated_by}</div>
                                    <small class="text-muted" style="font-size: 0.68rem;">${ab.received_at}</small>
                                </td>
                            </tr>
                        `;
                    });

                    allocatedDetailsTableHtml = `
                        <div class="collapse allocated-expand-details" id="expandAllocDetails_${globalIndex}">
                            <div class="expand-header-banner">
                                <div class="expand-header-title">
                                    <i class="bi bi-houses-fill text-success fs-6"></i>
                                    <span>Allocated Boxes with Assigned Polyhouse (#${item.stockorderno})</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    ${item.has_multiple_dates ? `
                                        <span class="badge bg-purple-subtle text-purple border border-purple-subtle py-1 px-2">
                                            <i class="bi bi-calendar3-range me-1"></i>Multi-Date Allocation (${item.allocated_dates})
                                        </span>` : ''}
                                    <span class="badge bg-light text-dark border py-1 px-2">
                                        Total: ${allocatedBoxes.length} Boxes (${item.allocated_trays_count} Trays)
                                    </span>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table allocated-boxes-table table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Box QR / ID</th>
                                            <th>Opening Date</th>
                                            <th>Target Polyhouse</th>
                                            <th>Trays</th>
                                            <th>Status</th>
                                            <th>Allocated By / Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${rowsHtml}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    `;
                }

                outputHtml += `
                    <div class="order-strip-card allocation-data-row"
                         data-productionstockorderno="${item.stockorderno}"
                         data-productid="${item.productid}"
                         data-noofboxes="${item.total_boxes_count}"
                         data-pendingboxes="${item.pending_boxes_count}"
                         data-allocatedboxes="${item.allocated_boxes_count}"
                         data-traycountinbox="${item.traycountinbox}"
                         data-productname="${item.productname}"
                         data-roottype="${item.roottype || ''}"
                         data-boxopeningdate="${item.boxopeningdate}"
                         data-boxopeningdatedsp="${item.boxopeningdate_dsp}">
                        
                        <div class="order-strip-main">
                            <div class="strip-item action-col">${actionBtn}</div>

                            <div class="strip-item product-col text-start">
                                <div class="fw-bold text-dark" style="font-size: 0.9rem;">${item.productname}</div>
                                <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                                    ${item.roottype ? `<span class="badge ${item.roottype === 'Root' ? 'bg-info-subtle text-info border border-info-subtle' : 'bg-primary-subtle text-primary border border-primary-subtle'} fw-bold" style="font-size: 0.7rem; padding: 2px 7px;">${item.roottype}</span>` : ''}
                                    <span class="small text-muted" style="font-size: 0.72rem;">Lot: <b>${item.sowinglotno_internal}</b></span>
                                </div>
                            </div>

                            <div class="strip-item order-no-col">
                                <span class="badge bg-light text-dark border fw-bold fs-7 py-2 px-2.5">
                                    #${item.stockorderno}
                                </span>
                            </div>

                            <div class="strip-item dates-col text-center">
                                <div class="small text-muted" style="font-size: 0.75rem;">PK: <b>${item.boxpackingdate_dsp}</b></div>
                                <div class="small text-success fw-bold" style="font-size: 0.8rem;">OP: ${item.boxopeningdate_dsp}</div>
                                ${item.has_multiple_dates ? `
                                    <span class="badge bg-purple-subtle text-purple border mt-1" style="font-size: 0.65rem;" title="Order spans multiple dates">
                                        <i class="bi bi-calendar3-range me-1"></i>Multi-Date
                                    </span>` : ''}
                            </div>

                            <div class="strip-item total-boxes-col">
                                <span class="badge bg-light text-dark border fw-bold px-3 py-1.5" style="font-size: 0.85rem;">
                                    ${item.total_boxes_count}
                                </span>
                                <div class="small text-warning-emphasis fw-bold mt-1" style="font-size: 0.72rem;">
                                    ${item.pending_boxes_count} Pending (${item.pending_trays_count} Trays)
                                </div>
                            </div>

                            <div class="strip-item allocated-col">
                                ${allocatedDisplayHtml}
                            </div>

                            <div class="strip-item received-by-col small text-muted">
                                <div class="fw-600 text-dark">${item.received_by || '-'}</div>
                                <small style="font-size: 0.68rem;">After Sowing</small>
                            </div>

                            <div class="strip-item allocated-by-col small text-muted">
                                <div class="fw-600 text-dark">${item.allocated_by || '-'}</div>
                                <small style="font-size: 0.68rem;">Polyhouse Alloc</small>
                            </div>
                        </div>

                        ${allocatedDetailsTableHtml}
                    </div>
                `;
            });

            outputHtml += `</div>`; // Close date-group-section
        });

        $("#gboxesallocatetopolyhouseList").html(outputHtml);
    }

    // -------------------------------------------------------------
    // 5. Open Allocation Modal & Preload Quick Selection Chips
    // -------------------------------------------------------------
    $(document).on("click", ".allocateghbtntd", function () {
        const row = $(this).closest(".order-strip-card");

        // Clear Modal Data
        $("#AllocationBoxDetailsInfoToPH").hide().empty();
        $("#noResult").addClass('d-none');
        $("#statusalc").text("PENDING").removeClass('text-success text-danger text-primary').addClass('text-warning');
        $("#allocationProgressBar").css("width", "0%");
        $("#allocationPercentTxt").text("0%");
        $("#manualQrCode").val('');
        $("#polyhousecapacitydetailsdiv").html("");

        const productName = row.data("productname");
        const orderNo = row.data("productionstockorderno");
        const rootType = row.data("roottype") || 'N/A';
        const openingDate = row.data("boxopeningdatedsp") || '';

        $("#productNamemdalc").text(productName);
        $("#productOrderNoalc").text(orderNo);
        $("#roottypemdalc").text(rootType);
        $("#openingDatemdalc").text(openingDate);

        const pendingCount = parseInt(row.data("pendingboxes") || 0);
        const allocatedCount = parseInt(row.data("allocatedboxes") || 0);
        const totalCount = parseInt(row.data("noofboxes") || 1);
        const trayCount = parseInt(row.data("traycountinbox") || 0);

        $("#GerminatedBoxCount").text(`${allocatedCount} / ${totalCount}`);
        $("#germTrayCountinbox").text(trayCount);

        $("#updateationboxinpolyhouse").data('stockorderno', orderNo).data('roottype', rootType);

        updateAllocationProgress(allocatedCount, totalCount);

        // Load Pending Box Chips for Quick 1-Click Fill
        let rawGboxData = $(this).attr("data-gboxdata");
        let pendingBoxes = [];
        try {
            if (rawGboxData) {
                pendingBoxes = JSON.parse(decodeURIComponent(rawGboxData));
            }
        } catch(e) {
            console.warn("Failed to parse box details:", e);
        }

        if (Array.isArray(pendingBoxes) && pendingBoxes.length > 0) {
            let chipsHtml = '';
            pendingBoxes.forEach((bx, idx) => {
                chipsHtml += `
                    <button type="button" class="box-chip btn-quick-select-box" data-qrcode="${bx.qrcode}" data-boxid="${bx.boxid}">
                        <i class="bi bi-box me-1 text-success"></i>
                        <span>#${idx + 1} <b>${bx.qrcode}</b></span>
                        <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.65rem;">${bx.traycountinbox} Trays</span>
                    </button>
                `;
            });
            $("#modalPendingBoxesChipsContainer").html(chipsHtml);
            $("#modalPendingBoxesCountBadge").text(`${pendingBoxes.length} boxes pending`);
            $("#modalPendingBoxesCollapse").removeClass("show");
            $("#headingPendingBoxes").attr("aria-expanded", "false");
            $("#togglePendingText").text("Show Boxes");
            $("#togglePendingIcon").removeClass("bi-chevron-up").addClass("bi-chevron-down");
            $("#modalPendingBoxesChipsBar").slideDown(200);
        } else {
            $("#modalPendingBoxesChipsBar").hide();
        }

        $("#allocationToPHModal").modal("show");

        setTimeout(() => {
            if (window.innerWidth < 768) {
                $("#AllocationstartScan").click();
            }
        }, 500);
    });

    // Listeners for pending boxes collapse toggling
    $(document).on("show.bs.collapse", "#modalPendingBoxesCollapse", function () {
        $("#togglePendingText").text("Hide Boxes");
        $("#togglePendingIcon").removeClass("bi-chevron-down").addClass("bi-chevron-up");
        $("#headingPendingBoxes").attr("aria-expanded", "true");
    });

    $(document).on("hide.bs.collapse", "#modalPendingBoxesCollapse", function () {
        $("#togglePendingText").text("Show Boxes");
        $("#togglePendingIcon").removeClass("bi-chevron-up").addClass("bi-chevron-down");
        $("#headingPendingBoxes").attr("aria-expanded", "false");
    });

    // Quick select box from modal chips
    $(document).on("click", ".btn-quick-select-box", function () {
        const qr = $(this).data("qrcode");
        if (qr) {
            $(".btn-quick-select-box").removeClass("border-success bg-success-subtle text-success");
            $(this).addClass("border-success bg-success-subtle text-success");
            fetchGerminationBoxData(qr);
        }
    });

    function openDirectScanner() {
        $("#productNamemdalc").text("Optical Scan Mode");
        $("#productOrderNoalc").text("Scan Any Box QR");
        $("#roottypemdalc").text("Auto-Detect");
        $("#openingDatemdalc").text("Today");
        $("#modalPendingBoxesChipsBar").hide();
        $("#allocationToPHModal").modal("show");
        setTimeout(() => {
            $("#AllocationstartScan").click();
        }, 400);
    }

    // -------------------------------------------------------------
    // 6. Real-Time Search & Date Filter Pill Listeners
    // -------------------------------------------------------------
    $(document).on("input", "#filterSearchInput", function () {
        renderGroupedAllocationStrips(masterAllocationData);
    });

    $(document).on("click", ".date-filter-pill", function () {
        $(".date-filter-pill").removeClass("active");
        $(this).addClass("active");
        activeFilterDate = $(this).data("date");
        renderGroupedAllocationStrips(masterAllocationData);
    });

    let allExpanded = false;
    $(document).on("click", "#btnToggleAllAllocated", function () {
        allExpanded = !allExpanded;
        if (allExpanded) {
            $(".allocated-expand-details").collapse("show");
            $(this).html('<i class="bi bi-arrows-collapse me-1"></i> Collapse All Polyhouse Details');
        } else {
            $(".allocated-expand-details").collapse("hide");
            $(this).html('<i class="bi bi-arrows-expand me-1"></i> Expand All Polyhouse Details');
        }
    });

    // -------------------------------------------------------------
    // 7. QR Code Fetch Data & Polyhouse Selection
    // -------------------------------------------------------------
    function fetchGerminationBoxData(qrCodeValue) {
        if (!qrCodeValue) {
            $("#noResult").removeClass('d-none').show().text("Invalid QR Code.");
            return;
        }

        let routurl = $("body").data("routurl");
        let companyid = $("body").data("companyid");
        let subcompanyid = $("body").data("subcompanyid");
        let projectid = $("body").data("projectid");
        let polyhouseid = $("#PolyhouseTemplate").val();

        $.ajax({
            url: "modal/polihouse_modal.php?action=GetGerminationBoxDataWithQRCode",
            type: "POST",
            dataType: "json",
            data: { qrcode: qrCodeValue, companyid, subcompanyid, projectid, polyhouseid },
            beforeSend: function () {
                $("#AllocationBoxDetailsInfoToPH").hide().empty();
                $("#noResult").addClass('d-none');
                $("#statusalc").text("PROCESSING...").removeClass('text-warning text-success text-danger').addClass('text-primary');
            },
            success: function (res) {
                if (!res || res.status === "error" || !res.boxid) {
                    $("#noResult").removeClass('d-none').show().html(`<i class="bi bi-x-circle me-2"></i> No active box found for this QR code.`);
                    $("#statusalc").text("FAILED").removeClass('text-primary text-success').addClass('text-danger');
                    return;
                }

                let traydata = `
                    <div class="box-info-display bg-white p-3 rounded-4 shadow-sm border border-success-subtle mt-2 animate__animated animate__fadeInUp">
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                            <h6 class="fw-bold m-0 small text-success">
                                <i class="bi bi-check-circle-fill me-2"></i>Box Verified: #${res.boxid} (${res.qrcode || qrCodeValue})
                            </h6>
                            <span class="badge bg-success-subtle text-success rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">Ready for Allocation</span>
                        </div>
                        
                        <div class="row g-2">
                            <div class="col-6 col-md-4">
                                <div class="bg-light p-2 rounded-3 border text-center">
                                    <div class="small text-muted mb-1 fw-bold text-uppercase" style="font-size: 0.65rem;">Product</div>
                                    <div class="fw-bold text-dark text-truncate" style="font-size: 0.8rem;">${res.productname}</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="bg-light p-2 rounded-3 border text-center">
                                    <div class="small text-muted mb-1 fw-bold text-uppercase" style="font-size: 0.65rem;">Order No</div>
                                    <div class="fw-bold text-dark" style="font-size: 0.85rem;">#${res.stockorderno}</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="bg-light p-2 rounded-3 border text-center">
                                    <div class="small text-muted mb-1 fw-bold text-uppercase" style="font-size: 0.65rem;">Root Type / Variety</div>
                                    <div class="fw-bold text-primary" style="font-size: 0.8rem;">${res.roottype || 'N/A'} (${res.seedname || 'N/A'})</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="bg-light p-2 rounded-3 border text-center">
                                    <div class="small text-muted mb-1 fw-bold text-uppercase" style="font-size: 0.65rem;">Trays in Box</div>
                                    <div class="fw-bold text-success" style="font-size: 1.1rem;">${res.traycountinbox}</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="bg-light p-2 rounded-3 border text-center">
                                    <div class="small text-muted mb-1 fw-bold text-uppercase" style="font-size: 0.65rem;">Batch Progress</div>
                                    <div class="fw-bold text-primary" style="font-size: 0.95rem;">${res.allocatedbox} / ${res.totalbox} Boxes</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="bg-light p-2 rounded-3 border text-center">
                                    <div class="small text-muted mb-1 fw-bold text-uppercase" style="font-size: 0.65rem;">Opening Date</div>
                                    <div class="fw-bold text-dark" style="font-size: 0.85rem;">${res.boxopeningdatedsp || 'N/A'}</div>
                                </div>
                            </div>

                            <div class="col-md-6 mt-3">
                                <div class="p-2.5 bg-warning-subtle text-warning-emphasis rounded-3 border border-warning-subtle">
                                    <h6 class="fw-bold mb-1" style="font-size: 0.78rem;"><i class="bi bi-geo-alt-fill me-1"></i>Target Location</h6>
                                    <select class="form-select form-select-sm border-0 shadow-none bg-white py-1.5 rounded-2 fw-semibold" id="confirmLocationlist"></select>
                                </div>
                            </div>

                            <div class="col-md-6 mt-3">
                                <div class="p-2.5 bg-primary-subtle text-primary rounded-3 border border-primary-subtle">
                                    <h6 class="fw-bold mb-1" style="font-size: 0.78rem;"><i class="bi bi-house-door-fill me-1"></i>Target Polyhouse</h6>
                                    <select class="form-select form-select-sm border-0 shadow-none bg-white py-1.5 rounded-2 fw-semibold" id="confirmPolyhouselist"></select>
                                </div>
                            </div>
                            
                            <div id="polyhousecapacitydetailsdiv" class="col-12 mt-2"></div>
                        </div>

                        <div class="text-center mt-3 pt-2">
                            <button class="submit-btn-premium w-100 py-2.5" 
                                    data-boxid="${res.boxid}" 
                                    data-qrcode="${res.qrcode || qrCodeValue}"
                                    data-boxbatchdate="${res.boxbatchdate || ''}" 
                                    data-stockorderno="${res.stockorderno}" 
                                    data-roottype="${res.roottype || ''}"
                                    data-sowinglotnointernal="${res.sowinglotno_internal || ''}" 
                                    data-traycount="${res.traycountinbox || 0}"
                                    id="updateationboxinpolyhouse" 
                                    onclick="submitBoxAllocation()">
                                <i class="bi bi-check2-circle me-2"></i>Finalize Box Allocation to Polyhouse
                            </button>
                        </div>
                    </div>
                `;

                if (res.roottype) {
                    $("#roottypemdalc").text(res.roottype);
                }

                $("#statusalc").text("VERIFIED").removeClass('text-primary text-danger').addClass('text-success');
                $("#noResult").addClass('d-none');
                $("#AllocationBoxDetailsInfoToPH").html(traydata).show();
                $("#allocationqrcodescannerdiv").removeClass('scanner-active');
                $("#allocationModalFooter").show();

                // Load Locations and auto select
                LoadProductionLocations(projectid, res.sowinglocationid || '', 'confirmLocationlist');

                if (!res.sowinglocationid || res.sowinglocationid == "0") {
                    GetPolihouseDetailsList('all', 'all', '', 'confirmPolyhouselist');
                }
            },
            error: function () {
                $("#noResult").removeClass('d-none').show().text("Server error. Try again.");
                $("#statusalc").text("ERROR").removeClass('text-primary').addClass('text-danger');
            }
        });
    }

    // Submit box allocation and smoothly update workspace
    function submitBoxAllocation() {
        const polyhouseid = $("#confirmPolyhouselist").val();
        const polyhouseName = $("#confirmPolyhouselist option:selected").text().trim() || "Selected Polyhouse";
        const locationName = $("#confirmLocationlist option:selected").text().trim() || "";
        const $btn = $("#updateationboxinpolyhouse");
        const boxid = $btn.data("boxid");
        const qrcode = $btn.data("qrcode") || lastScannedCode || '';
        const stockorderno = $btn.data("stockorderno");
        const roottype = $btn.data("roottype") || $("#roottypemdalc").text().trim() || '';
        const boxbatchdate = $btn.data("boxbatchdate");
        const sowinglotnointernal = $btn.data("sowinglotnointernal");
        const trayCount = $btn.data("traycount") || 0;

        if (!polyhouseid) {
            Swal.fire({
                icon: "warning",
                title: "Choose Polyhouse",
                text: "Please select a target polyhouse to complete allocation."
            });
            return;
        }

        // Count pending boxes in this batch from quick-select chips
        const pendingChips = $("#modalPendingBoxesChipsContainer .btn-quick-select-box");
        const pendingCount = pendingChips.length;

        // If batch has multiple pending boxes, prompt before saving
        if (pendingCount > 1) {
            Swal.fire({
                title: `<span class="text-success"><i class="bi bi-patch-question-fill me-2"></i>Allocate to Polyhouse?</span>`,
                html: `
                    <div class="text-start" style="font-size: 0.92rem;">
                        <div class="p-3 bg-light rounded-3 border mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted small">Target Polyhouse:</span>
                                <span class="fw-bold text-success"><i class="bi bi-house-door-fill me-1"></i>${polyhouseName} ${locationName ? `(${locationName})` : ''}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted small">Order Number:</span>
                                <span class="fw-bold text-dark">#${stockorderno}</span>
                            </div>
                            ${roottype && roottype !== 'N/A' ? `
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted small">Root Type / Bundle:</span>
                                <span class="badge ${roottype === 'Root' ? 'bg-info-subtle text-info border border-info-subtle' : 'bg-primary-subtle text-primary border border-primary-subtle'} fw-bold" style="font-size: 0.78rem;">${roottype}</span>
                            </div>` : ''}
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted small">Current Scanned Box:</span>
                                <span class="fw-bold text-dark"><i class="bi bi-box-seam me-1"></i>#${boxid} (${qrcode}) - ${trayCount} Trays</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted small">Pending ${roottype && roottype !== 'N/A' ? roottype + ' ' : ''}Boxes:</span>
                                <span class="badge bg-warning-subtle text-warning-emphasis fw-bold">${pendingCount} Boxes Pending Allocation</span>
                            </div>
                        </div>
                        <p class="mb-2 fw-semibold text-dark">How would you like to allocate this <b>${roottype && roottype !== 'N/A' ? roottype : ''}</b> bundle to <u>${polyhouseName}</u>?</p>
                        <p class="text-muted small mb-0">Select <b>"Allocate ALL"</b> to allocate all ${pendingCount} pending <b>${roottype && roottype !== 'N/A' ? roottype + ' ' : ''}</b>boxes of Order #${stockorderno} to this polyhouse, or <b>"Only This Box"</b> to allocate just Box #${boxid}.</p>
                    </div>
                `,
                icon: "question",
                showDenyButton: true,
                showCancelButton: true,
                confirmButtonText: `<i class="bi bi-boxes me-1"></i> Allocate ALL (${pendingCount} Pending ${roottype && roottype !== 'N/A' ? roottype + ' ' : ''}Boxes)`,
                denyButtonText: `<i class="bi bi-box me-1"></i> Allocate ONLY Box #${boxid}`,
                cancelButtonText: `Cancel`,
                confirmButtonColor: '#198754',
                denyButtonColor: '#0d6efd',
                cancelButtonColor: '#6c757d',
                customClass: {
                    actions: 'gap-2 flex-wrap',
                    confirmButton: 'btn btn-success px-3 py-2 rounded-3 fw-bold',
                    denyButton: 'btn btn-primary px-3 py-2 rounded-3 fw-bold',
                    cancelButton: 'btn btn-secondary px-3 py-2 rounded-3'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    executeBoxAllocation('all', polyhouseid, boxid, stockorderno, roottype, boxbatchdate, sowinglotnointernal, qrcode);
                } else if (result.isDenied) {
                    executeBoxAllocation('single', polyhouseid, boxid, stockorderno, roottype, boxbatchdate, sowinglotnointernal, qrcode);
                }
            });
        } else {
            // Only 1 box pending or single direct scan
            executeBoxAllocation('single', polyhouseid, boxid, stockorderno, roottype, boxbatchdate, sowinglotnointernal, qrcode);
        }
    }

    function executeBoxAllocation(mode, polyhouseid, boxid, stockorderno, roottype, boxbatchdate, sowinglotnointernal, qrcode) {
        const $btn = $("#updateationboxinpolyhouse");
        const companyid = currentCompanyId || $("body").data("companyid") || 0;
        const subcompanyid = currentSubCompanyId || $("body").data("subcompanyid") || 0;
        const projectid = currentProjectId || $("body").data("projectid") || 0;
        const loginid = currentLoginId || $("body").data("loginid") || 0;

        const formData = new FormData();
        formData.append('companyid', companyid);
        formData.append('subcompanyid', subcompanyid);
        formData.append('projectid', projectid);
        formData.append('loginid', loginid);
        formData.append('polyhouseid', polyhouseid);
        formData.append('boxid', boxid);
        formData.append('stockorderno', stockorderno);
        formData.append('roottype', roottype || '');
        formData.append('boxbatchdate', boxbatchdate || '');
        formData.append('sowinglotnointernal', sowinglotnointernal || '');
        formData.append('allocation_mode', mode);

        $btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-2"></span>Allocating...');

        $.ajax({
            type: "POST",
            url: "modal/polihouse_modal.php?action=UpdateAllocationofGerminationBoxData",
            data: formData,
            contentType: false,
            processData: false,
            dataType: "JSON",
            success: function (res) {
                $btn.prop("disabled", false).html('<i class="bi bi-check2-circle me-2"></i>Finalize Box Allocation to Polyhouse');
                if (res.status === "success") {
                    playBeep();
                    Swal.fire({
                        title: "Allocation Successful!",
                        text: res.message || "Germination box allocated to polyhouse successfully.",
                        icon: "success",
                        timer: 2500,
                        showConfirmButton: true
                    });

                    if (mode === 'all') {
                        // All pending boxes of batch allocated!
                        $("#modalPendingBoxesChipsContainer").empty();
                        $("#modalPendingBoxesCountBadge").text("0 boxes pending");
                        $("#modalPendingBoxesChipsBar").slideUp(200);

                        const total = parseInt(res.data?.total_boxes || 0);
                        const allocated = parseInt(res.data?.allocated_boxes || total);
                        $("#GerminatedBoxCount").text(`${allocated} / ${total}`);
                        updateAllocationProgress(allocated, total);
                        $("#statusalc").text("ALLOCATED").removeClass('text-warning text-primary text-danger').addClass('text-success');

                        $("#AllocationBoxDetailsInfoToPH").slideUp(200, function() { $(this).empty(); });
                        $("#allocationModalFooter").hide();
                    } else {
                        // Single box allocated! Remove this chip
                        if (qrcode) {
                            $(`#modalPendingBoxesChipsContainer .btn-quick-select-box[data-qrcode="${qrcode}"]`).remove();
                        } else if (boxid) {
                            $(`#modalPendingBoxesChipsContainer .btn-quick-select-box[data-boxid="${boxid}"]`).remove();
                        }

                        const remaining = $("#modalPendingBoxesChipsContainer .btn-quick-select-box").length;
                        $("#modalPendingBoxesCountBadge").text(`${remaining} boxes pending`);
                        if (remaining === 0) {
                            $("#modalPendingBoxesChipsBar").slideUp(200);
                        }

                        if (res.data) {
                            const total = parseInt(res.data.total_boxes || 0);
                            const allocated = parseInt(res.data.allocated_boxes || 0);
                            $("#GerminatedBoxCount").text(`${allocated} / ${total}`);
                            updateAllocationProgress(allocated, total);
                        }

                        // Clear and reset modal for next box scan
                        $("#unlockScanner").click();
                    }

                    // Instantly reload workspace data without forcing full page reload
                    reloadAllocationWorkspace();
                } else {
                    Swal.fire({
                        title: "Allocation Failed",
                        text: res.message || "Could not allocate box.",
                        icon: "error"
                    });
                }
            },
            error: function () {
                $btn.prop("disabled", false).html('<i class="bi bi-check2-circle me-2"></i>Finalize Box Allocation to Polyhouse');
                Swal.fire({
                    title: "Network Error",
                    text: "Could not communicate with server.",
                    icon: "error"
                });
            }
        });
    }

    $(document).on("change", "#confirmLocationlist", function () {
        const locationid = $(this).val();
        $("#polyhousecapacitydetailsdiv").html("");
        if (!locationid) {
            $("#confirmPolyhouselist").empty().html('<option value="">Select Location First</option>');
            return;
        }
        GetPolihouseDetailsList('locationid', locationid, '', 'confirmPolyhouselist');
    });

    $(document).on("change", "#confirmPolyhouselist", function () {
        const polyhouseid = $(this).val();
        if (!polyhouseid) {
            $("#polyhousecapacitydetailsdiv").html("");
            return;
        }
        PolyhouseTrayUsageDetailsFromPolyhousePlant(polyhouseid, function (plyres) {
            if (!plyres || !plyres.polyhouseid) {
                $("#polyhousecapacitydetailsdiv").html(`<div class="alert alert-danger py-2 small">No capacity data found</div>`);
                return;
            }
            renderPolyhouseCapacityDetails(plyres, 'polyhousecapacitydetailsdiv');
        });
    });

    function LoadProductionLocations(projectid, selectedId, dropdownid) {
        let companyid = $("body").data("companyid");
        let subcompanyid = $("body").data("subcompanyid");
        let routurl = $("body").data("routurl");

        $.ajax({
            url: "modal/product_modal.php?action=GetProductionLocationList",
            type: "POST",
            data: { projectid: projectid, companyid: companyid, subcompanyid: subcompanyid },
            dataType: "json",
            success: function (res) {
                let options = '<option value="">Choose Location...</option>';
                if (Array.isArray(res)) {
                    res.forEach(function (loc) {
                        options += `<option value="${loc.LocationId}">${loc.LocationName}</option>`;
                    });
                }
                $("#" + dropdownid).html(options);
                if (selectedId && selectedId != 0 && selectedId != "0") {
                    $("#" + dropdownid).val(selectedId).trigger("change");
                }
            }
        });
    }

    // -------------------------------------------------------------
    // 8. Optical QR Scanner Control
    // -------------------------------------------------------------
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
            osc.frequency.setValueAtTime(880, globalAudioCtx.currentTime);
            gain.gain.setValueAtTime(0.1, globalAudioCtx.currentTime);
            osc.start();
            osc.stop(globalAudioCtx.currentTime + 0.12);
        } catch (e) {
            console.warn("Audio feedback failed:", e);
        }
    }

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

        return new Promise((resolve) => {
            if (typeof Html5Qrcode === "undefined") {
                resolve([]);
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

                    let activeCam = cameras.find(c => {
                        const lbl = (c.label || '').toLowerCase();
                        return lbl.includes('back') || lbl.includes('environment') || lbl.includes('rear');
                    }) || cameras[0];

                    if (activeCam) {
                        $("#cameraSelection").val(activeCam.id);
                    }
                    resolve(cameras);
                } else {
                    resolve([]);
                }
            }).catch(() => resolve([]));
        });
    }

    window.initScanner = function (cameraId = null) {
        if (typeof Html5Qrcode === "undefined") {
            alert("QR Scanner library not loaded.");
            return;
        }
        if (isScanning) return;

        if (!qrReader) {
            qrReader = new Html5Qrcode("AllocationReaderId");
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
            $("#AllocationstartScan").hide();
            $("#AllocationstopScan").show();
            $("#scannerOverlay").fadeIn();
            $("#scannerStatusDiv").slideDown();
            $("#scannerStatus").html('<i class="bi bi-broadcast text-danger pulse"></i><span>Scanner Active: Looking for QR Code...</span>');

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
                Swal.fire('Camera Error', 'Could not access camera.', 'warning');
            }
        });
    };

    window.stopScanner = function () {
        if (qrReader && isScanning) {
            qrReader.stop().then(() => {
                isScanning = false;
                $("#AllocationstartScan").show();
                $("#AllocationstopScan").hide();
                $("#scannerOverlay").hide();
                $("#scannerStatusDiv").slideUp();
            }).catch(err => {
                console.warn("Stop failed", err);
                isScanning = false;
            });
        }
    };

    function onScanSuccess(decodedText) {
        if (scanLocked) return;
        scanLocked = true;

        if (decodedText === lastScannedCode) return;
        lastScannedCode = decodedText;
        clearTimeout(scanLockTimeout);
        scanLockTimeout = setTimeout(() => { lastScannedCode = null; }, 2000);

        $("#scannerStatus")
            .html('<i class="bi bi-qr-code text-white"></i><span>QR Code Read: ' + decodedText + '</span>')
            .removeClass('bg-light text-secondary')
            .addClass('bg-success text-white');

        playBeep();
        $("#successFlash").show().fadeOut(400);

        if (navigator.vibrate) navigator.vibrate(100);

        if (qrReader && isScanning) {
            qrReader.stop().then(() => {
                isScanning = false;
                $("#AllocationstartScan").prop("disabled", true);
                $("#AllocationstopScan").hide();
                $("#scannerOverlay").hide();
                $("#scannerStatusDiv").slideUp();
            }).catch(() => {
                isScanning = false;
            });
        }

        fetchGerminationBoxData(decodedText);
    }

    function onScanFailure(error) { }

    $("#AllocationstartScan").on("click", function () {
        initScanner();
        $("#allocationqrcodescannerdiv").show();
        $("#manualQrCodeSearchDiv").hide();
    });

    $("#cameraSelection").on("change", function () {
        const selectedCameraId = $(this).val();
        if (qrReader && isScanning) {
            qrReader.stop().then(() => {
                isScanning = false;
                initScanner(selectedCameraId);
            });
        }
    });

    $("#AllocationstopScan").on("click", function () {
        stopScanner();
    });

    $("#ManualAllocationstartScan").on("click", function () {
        if (qrReader && isScanning) qrReader.stop().then(() => { isScanning = false; });
        $("#allocationqrcodescannerdiv").hide();
        $("#manualQrCodeSearchDiv").show();
        $("#allocationModalFooter").show();
        $("#manualQrCode").focus();
    });

    $("#StartMobileScanner").on("click", function () {
        $("#manualQrCodeSearchDiv").hide();
        $("#allocationqrcodescannerdiv").show();
        $("#AllocationstartScan").click();
    });

    $("#fetchManual").on("click", function () {
        const qrValue = $("#manualQrCode").val().trim();
        fetchGerminationBoxData(qrValue);
        $("#manualQrCode").val('');
    });

    $("#manualQrCode").on("keypress", function (e) {
        if (e.which === 13) $("#fetchManual").click();
    });

    $("#unlockScanner").on("click", function () {
        scanLocked = false;
        $("#AllocationstartScan").prop("disabled", false);
        $("#AllocationBoxDetailsInfoToPH").hide().empty();
        $("#noResult").addClass('d-none');
        $("#statusalc").text("PENDING").removeClass('text-success text-danger text-primary').addClass('text-warning');

        if ($("#manualQrCodeSearchDiv").is(":visible")) {
            $("#manualQrCode").val('').focus();
        } else {
            $("#AllocationstartScan").click();
        }
    });

    function updateAllocationProgress(allocated, total) {
        allocated = parseInt(allocated) || 0;
        total = parseInt(total) || 1;
        let percent = Math.round((allocated / total) * 100);

        $("#allocationProgressBar").css("width", percent + "%");
        $("#allocationPercentTxt").text(percent + "%");

        if (percent === 100) {
            $("#statusalc").text("COMPLETED").removeClass('text-warning text-primary').addClass('text-success');
        } else {
            $("#statusalc").text("PENDING").removeClass('text-success text-primary').addClass('text-warning');
        }
    }

    $("#toggleFlash").on("click", async function () {
        if (!qrReader || !isScanning) return;
        try {
            const track = qrReader.getRunningTrack();
            if (!track) return;
            const caps = track.getCapabilities ? track.getCapabilities() : {};
            if (!caps.torch) return;
            torchOn = !torchOn;
            await track.applyConstraints({ advanced: [{ torch: torchOn }] });
        } catch (err) { console.warn("Torch failed", err); }
    });

    // -------------------------------------------------------------
    // 9. Document Ready Initialization
    // -------------------------------------------------------------
    $(document).ready(function () {
        // Automatically load ALL pending allocation boxes on page load!
        // No need to select a date first!
        loadAllocationWorkspace('all');

        GetPolihouseDetailsList('all', 'all', '', 'PolyhouseTemplate');
    });
</script>
