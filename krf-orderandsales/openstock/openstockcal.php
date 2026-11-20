<?php
/**
 * openstockcal.php
 * Interactive Product-Wise Open Stock Calculation & Production Order Drilldown Dashboard
 * 
 * Strict Constraint Compliance:
 * - Only connection file used from outside the directory.
 * - Zero external PHP functions used; self-contained frontend and AJAX requests pointing to modal/openstockcal_modal.php.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(0);
ini_set('display_errors', '0');
date_default_timezone_set('Asia/Kolkata');

$hasKey = isset($_GET['k']) && !empty($_GET['k']);
if ($hasKey && file_exists(__DIR__ . '/../header.php')) {
    require('../header.php');
} else {
    require_once(__DIR__ . '/../../connection.php');
}

$loginid      = $_SESSION['loginid'] ?? $_SESSION['userid'] ?? 'admin';
$loginname    = $_SESSION['loginname'] ?? $_SESSION['username'] ?? $_SESSION['name'] ?? 'Admin';
$companyid    = (int)($_SESSION['companyid'] ?? 1);
$subcompanyid = (int)($_SESSION['subcompanyid'] ?? 1);
$projectid    = (int)($_SESSION['projectid'] ?? 17);
if ($projectid <= 0) $projectid = 17;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Open Stock Calculation | Karjee Farm</title>

    <!-- Google Fonts & FontAwesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <!-- Bootstrap 5, DataTables & SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

    <style>
        :root {
            --brand-primary: #10b981;
            --brand-primary-dark: #059669;
            --brand-primary-light: #ecfdf5;
            --brand-dark: #0f172a;
            --brand-slate: #1e293b;
            --brand-muted: #64748b;
            --brand-border: #e2e8f0;
            --brand-bg: #f8fafc;
            --brand-card-bg: #ffffff;
            --brand-accent: #6366f1;
            --brand-warning: #f59e0b;
            --brand-danger: #ef4444;
            --brand-info: #0ea5e9;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--brand-bg);
            color: #334155;
            min-height: 100vh;
        }

        .mono {
            font-family: 'JetBrains Mono', monospace;
        }

        /* Compact Light Header */
        .page-header {
            background: #ffffff;
            color: var(--brand-dark);
            padding: 0.85rem 1.25rem;
            border-radius: 0.75rem;
            margin-bottom: 1rem;
            border: 1px solid var(--brand-border);
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04);
        }

        .page-header h1 {
            font-size: 1.25rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.02em;
            margin-bottom: 0.15rem;
        }

        .page-header p {
            color: #64748b;
            font-size: 0.775rem;
            line-height: 1.35;
        }

        /* Compact Small KPI Cards */
        .kpi-card {
            background: var(--brand-card-bg);
            border-radius: 0.75rem;
            border: 1px solid var(--brand-border);
            padding: 0.65rem 0.95rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
            height: 100%;
        }

        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.07);
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 3px;
            height: 100%;
        }

        .kpi-card.kpi-primary::before { background: var(--brand-primary); }
        .kpi-card.kpi-info::before { background: var(--brand-info); }
        .kpi-card.kpi-warning::before { background: var(--brand-warning); }
        .kpi-card.kpi-accent::before { background: var(--brand-accent); }

        .kpi-title {
            font-size: 0.7rem;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.04em;
            color: var(--brand-muted);
            margin-bottom: 0.2rem;
        }

        .kpi-value {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--brand-dark);
            line-height: 1.15;
            font-family: 'JetBrains Mono', monospace;
        }

        .kpi-sub {
            font-size: 0.72rem;
            color: var(--brand-muted);
            margin-top: 0.2rem;
            line-height: 1.25;
        }

        /* Data Matrix Card */
        .main-card {
            background: #ffffff;
            border-radius: 0.75rem;
            border: 1px solid var(--brand-border);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            padding: 1rem 1.25rem;
        }

        /* Table Styling */
        .table-custom {
            width: 100% !important;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table-custom thead th {
            background: #f1f5f9;
            color: #475569;
            font-size: 0.775rem;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.04em;
            padding: 0.85rem 1rem;
            border-bottom: 2px solid var(--brand-border);
            white-space: nowrap;
        }

        .table-custom tbody tr {
            transition: all 0.15s ease;
            cursor: pointer;
        }

        .table-custom tbody tr:hover {
            background-color: #f8fafc !important;
        }

        .table-custom tbody td {
            padding: 0.9rem 1rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--brand-border);
            font-size: 0.875rem;
        }

        /* Status & Count Badges */
        .badge-count {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.75rem;
            padding: 0.2rem 0.55rem;
            border-radius: 0.375rem;
            font-weight: 600;
        }

        .badge-soft-success {
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }

        .badge-soft-warning {
            background: #fffbeb;
            color: #d97706;
            border: 1px solid #fde68a;
        }

        .badge-soft-danger {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .badge-soft-info {
            background: #f0f9ff;
            color: #0284c7;
            border: 1px solid #bae6fd;
        }

        .badge-soft-purple {
            background: #f5f3ff;
            color: #7c3aed;
            border: 1px solid #ddd6fe;
        }

        /* Modals */
        .modal-custom .modal-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #ffffff;
            border-top-left-radius: 1rem;
            border-top-right-radius: 1rem;
            padding: 1.25rem 1.75rem;
        }

        .modal-custom .modal-content {
            border-radius: 1rem;
            border: none;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .nav-tabs-custom {
            border-bottom: 2px solid var(--brand-border);
            gap: 0.5rem;
        }

        .nav-tabs-custom .nav-link {
            border: none;
            color: var(--brand-muted);
            font-weight: 600;
            font-size: 0.85rem;
            padding: 0.75rem 1.25rem;
            border-radius: 0.5rem 0.5rem 0 0;
            transition: all 0.2s ease;
        }

        .nav-tabs-custom .nav-link:hover {
            color: var(--brand-dark);
            background: #f1f5f9;
        }

        .nav-tabs-custom .nav-link.active {
            color: var(--brand-primary-dark);
            background: var(--brand-primary-light);
            border-bottom: 2px solid var(--brand-primary);
        }

        .search-pill {
            border-radius: 2rem;
            padding-left: 2.25rem;
            background: #ffffff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'%3E%3C/path%3E%3C/svg%3E") no-repeat 0.75rem center / 1rem 1rem;
            border: 1px solid var(--brand-border);
        }

        .search-pill:focus {
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
            border-color: var(--brand-primary);
        }

        .loader-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(2px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 50;
            border-radius: 1rem;
        }
    </style>
</head>
<body class="p-2 p-md-3">

<div class="container-fluid px-0">

    <!-- 1. HEADER SECTION (Light Theme & Compact Size) -->
    <div class="page-header d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2">
        <div>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-0.5">
                    <i class="fa fa-layer-group me-1"></i> Nursery Live Inventory
                </span>
                <span class="text-muted small">Project ID: #<?= $projectid ?></span>
            </div>
            <h1 class="mb-0">Product Open Stock Calculation</h1>
            <p class="mb-0">
                Real-time stock reconciliation across Production Stock Orders, Active Polyhouse Bed Trays, Consumed Orders, Confirmed Sale Allocations & Dispatched Invoices.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2 mt-2 mt-sm-0 align-self-sm-center flex-shrink-0">
            <button class="btn btn-outline-secondary btn-sm rounded-pill px-3" id="btnRefresh" title="Refresh Live Data">
                <i class="fa fa-sync-alt me-1"></i> Refresh
            </button>
            <button class="btn btn-success btn-sm rounded-pill px-3" id="btnExportCsv" title="Export Table as CSV">
                <i class="fa fa-file-excel me-1"></i> Export Data
            </button>
        </div>
    </div>

    <!-- 2. KPI METRICS CARDS (Small & Compact Grid) -->
    <div class="row g-2 g-md-3 mb-3">
        <!-- KPI 1: Production Orders -->
        <div class="col-6 col-lg-3">
            <div class="kpi-card kpi-accent">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="kpi-title">Production Stock Orders</span>
                    <i class="fa fa-clipboard-list text-primary opacity-75 small"></i>
                </div>
                <div class="kpi-value text-dark" id="kpiProductionQty">0</div>
                <div class="kpi-sub">
                    <span class="text-dark fw-bold" id="kpiActiveBatches">0</span> Active Batches
                </div>
            </div>
        </div>

        <!-- KPI 2: Active Bed Plants -->
        <div class="col-6 col-lg-3">
            <div class="kpi-card kpi-info">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="kpi-title">Active Plants on Beds</span>
                    <i class="fa fa-seedling text-info opacity-75 small"></i>
                </div>
                <div class="kpi-value text-info" id="kpiBedPlants">0</div>
                <div class="kpi-sub">
                    In <span class="fw-bold text-dark">Polyhouse & Ready-to-Sale</span>
                </div>
            </div>
        </div>

        <!-- KPI 3: Committed / Reserved -->
        <div class="col-6 col-lg-3">
            <div class="kpi-card kpi-warning">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="kpi-title">Assigned & Allocated</span>
                    <i class="fa fa-lock text-warning opacity-75 small"></i>
                </div>
                <div class="kpi-value text-warning" id="kpiCommittedQty">0</div>
                <div class="kpi-sub">
                    <span>Asgn:</span> <span class="fw-bold text-dark" id="kpiAssignedQty">0</span> |
                    <span>Alloc:</span> <span class="fw-bold text-dark" id="kpiAllocatedQty">0</span>
                </div>
            </div>
        </div>

        <!-- KPI 4: Net Available Open Stock -->
        <div class="col-6 col-lg-3">
            <div class="kpi-card kpi-primary">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="kpi-title">Physical Open Stock</span>
                    <i class="fa fa-check-circle text-success opacity-75 small"></i>
                </div>
                <div class="kpi-value text-success" id="kpiPhysicalOpen">0</div>
                <div class="kpi-sub">
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-1.5 py-0">Ready for Sale Now</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. MAIN TABLE CARD -->
    <div class="main-card position-relative">
        <div id="tableLoader" class="loader-overlay" style="display: none;">
            <div class="text-center">
                <div class="spinner-border text-success" role="status"></div>
                <div class="fw-bold text-dark mt-2 small">Calculating Live Open Stock Matrix...</div>
            </div>
        </div>

        <!-- Table Toolbar Filters -->
        <div class="row g-2 align-items-center mb-3">
            <!-- Product ID / Name Selector -->
            <div class="col-12 col-sm-6 col-md-4 col-xl-3">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light text-muted border-end-0">
                        <i class="fa fa-tag"></i>
                    </span>
                    <select id="productFilterSelect" class="form-select form-select-sm border-start-0" style="border-radius: 0 0.5rem 0.5rem 0;">
                        <option value="all">🔍 All Products (Filter by ID)</option>
                    </select>
                </div>
            </div>

            <!-- Quick Text Search -->
            <div class="col-12 col-sm-6 col-md-3 col-xl-3">
                <input type="text" class="form-control form-control-sm search-pill" id="customSearch" placeholder="Type Product ID, Name, SKU...">
            </div>

            <!-- Grafted / Non-Grafted Filter Toggle -->
            <div class="col-auto">
                <div class="btn-group btn-group-sm" role="group" id="graftedFilterGroup">
                    <button type="button" class="btn btn-outline-secondary active" data-grafted="all">All Types</button>
                    <button type="button" class="btn btn-outline-success" data-grafted="grafted">
                        <i class="fa fa-seedling me-1"></i> Grafted
                    </button>
                    <button type="button" class="btn btn-outline-info" data-grafted="nongrafted">
                        <i class="fa fa-leaf me-1"></i> Non-Grafted
                    </button>
                </div>
            </div>

            <!-- Stock Status Filter Toggle -->
            <div class="col-auto ms-xl-auto">
                <div class="btn-group btn-group-sm" role="group" id="stockFilterGroup">
                    <button type="button" class="btn btn-outline-secondary active" data-filter="all">All Stock</button>
                    <button type="button" class="btn btn-outline-success" data-filter="available">
                        <i class="fa fa-check me-1"></i> Available
                    </button>
                    <button type="button" class="btn btn-outline-primary" data-filter="in_bed">
                        <i class="fa fa-seedling me-1"></i> On Beds
                    </button>
                    <button type="button" class="btn btn-outline-danger" data-filter="exhausted">
                        <i class="fa fa-ban me-1"></i> Zero Stock
                    </button>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="table-responsive">
            <table class="table table-custom table-hover align-middle" id="openStockTable">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Product Details</th>
                        <th class="text-end">Production Target</th>
                        <th class="text-end">Physical Bed Plants</th>
                        <th class="text-end">Assigned Qty</th>
                        <th class="text-end">Allocated (Sale)</th>
                        <th class="text-end">Dispatched</th>
                        <th class="text-end">Physical Open Stock</th>
                        <th class="text-end">Book Open Stock</th>
                        <th class="text-center" style="width: 100px;">Action</th>
                    </tr>
                </thead>
                <tbody id="openStockTableBody">
                    <!-- Populated via AJAX -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 1: PRODUCT PRODUCTION STOCK ORDERS LIST -->
<!-- ========================================================================= -->
<div class="modal fade modal-custom" id="modalProductOrders" tabindex="-1" aria-labelledby="modalProductOrdersTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="modalProductOrdersTitle">
                        <i class="fa fa-boxes-stacked me-2"></i> Production Batches Drilldown
                    </h5>
                    <div class="small text-white-50" id="modalProductOrdersSubtitle">Loading product info...</div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4">
                <!-- Summary Badges in Modal -->
                <div class="d-flex flex-wrap gap-2 mb-3 p-2 bg-light rounded-3 border">
                    <span class="badge bg-white text-dark border p-2">
                        <i class="fa fa-hashtag text-muted me-1"></i> Batches: <strong id="mBatchCount">0</strong>
                    </span>
                    <span class="badge bg-white text-dark border p-2">
                        <i class="fa fa-layer-group text-primary me-1"></i> Total Target: <strong id="mBatchTotalQty">0</strong>
                    </span>
                    <span class="badge bg-white text-info border p-2">
                        <i class="fa fa-seedling text-info me-1"></i> Bed Plants: <strong id="mBatchBedQty">0</strong>
                    </span>
                    <span class="badge bg-white text-warning border p-2">
                        <i class="fa fa-lock text-warning me-1"></i> Assigned: <strong id="mBatchAssignQty">0</strong>
                    </span>
                    <span class="badge bg-white text-success border p-2">
                        <i class="fa fa-check-circle text-success me-1"></i> Available Physical: <strong id="mBatchAvailQty">0</strong>
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-hover table-custom" id="tableProductOrders">
                        <thead>
                            <tr>
                                <th>Stock Order No</th>
                                <th>Client / Type</th>
                                <th>Sowing Date</th>
                                <th>Expected Ready</th>
                                <th class="text-end">Target Qty</th>
                                <th class="text-end">Bed Plants</th>
                                <th class="text-end">Assigned</th>
                                <th class="text-end">Allocated</th>
                                <th class="text-end">Dispatched</th>
                                <th class="text-end">Physical Available</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Details</th>
                            </tr>
                        </thead>
                        <tbody id="tableProductOrdersBody">
                            <!-- Populated via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 2: BATCH DEEP DRILLDOWN (TABS: BEDS, ASSIGNED, ALLOCATIONS, INVOICES) -->
<!-- ========================================================================= -->
<div class="modal fade modal-custom" id="modalBatchDetails" tabindex="-1" aria-labelledby="modalBatchDetailsTitle" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="modalBatchDetailsTitle">
                        <i class="fa fa-layer-group me-2"></i> Batch Stockorder Deep Inspection
                    </h5>
                    <div class="small text-white-50" id="modalBatchDetailsSubtitle">Order Details</div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4">
                
                <!-- Batch Metric Header Pills -->
                <div class="row g-2 mb-3">
                    <div class="col-6 col-md-3">
                        <div class="p-2 border rounded-3 bg-light text-center">
                            <span class="text-muted small d-block">Target Order Qty</span>
                            <span class="fw-bold fs-6 mono text-dark" id="bMetricOrderQty">0</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 border rounded-3 bg-light text-center">
                            <span class="text-muted small d-block">Bed Living Plants</span>
                            <span class="fw-bold fs-6 mono text-info" id="bMetricBedPlants">0</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 border rounded-3 bg-light text-center">
                            <span class="text-muted small d-block">Assigned Orders</span>
                            <span class="fw-bold fs-6 mono text-warning" id="bMetricAssigned">0</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 border rounded-3 bg-light text-center">
                            <span class="text-muted small d-block">Allocated & Dispatched</span>
                            <span class="fw-bold fs-6 mono text-success" id="bMetricDisp">0</span>
                        </div>
                    </div>
                </div>

                <!-- Navigation Tabs -->
                <ul class="nav nav-tabs nav-tabs-custom mb-3" id="batchTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-trays-link" data-bs-toggle="tab" data-bs-target="#tab-trays" type="button" role="tab">
                            <i class="fa fa-table-cells me-1"></i> Bed Trays Inventory <span class="badge bg-secondary ms-1" id="badgeTrayCount">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-consumed-link" data-bs-toggle="tab" data-bs-target="#tab-consumed" type="button" role="tab">
                            <i class="fa fa-user-check me-1"></i> Assigned Orders <span class="badge bg-secondary ms-1" id="badgeConsCount">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-alloc-link" data-bs-toggle="tab" data-bs-target="#tab-alloc" type="button" role="tab">
                            <i class="fa fa-handshake me-1"></i> Ready Stock Allocations <span class="badge bg-secondary ms-1" id="badgeAllocCount">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-invoices-link" data-bs-toggle="tab" data-bs-target="#tab-invoices" type="button" role="tab">
                            <i class="fa fa-file-invoice me-1"></i> Invoices & Dispatches <span class="badge bg-secondary ms-1" id="badgeInvoicesCount">0</span>
                        </button>
                    </li>
                </ul>

                <!-- Tabs Content -->
                <div class="tab-content" id="batchTabsContent">
                    
                    <!-- Tab 1: Bed Trays -->
                    <div class="tab-pane fade show active" id="tab-trays" role="tabpanel">
                        <div class="alert alert-light border small py-2 mb-2">
                            <i class="fa fa-info-circle text-primary me-1"></i>
                            Showing physical living trays in beds. Active inventory is filtered strictly for status 
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">Polyhouse</span> and 
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Readytosale</span>.
                        </div>
                        <div class="table-responsive" style="max-height: 400px;">
                            <table class="table table-sm table-custom align-middle">
                                <thead class="sticky-top">
                                    <tr>
                                        <th>Polyhouse</th>
                                        <th>Bed / Rack</th>
                                        <th>Tray Code</th>
                                        <th>Growth / Health</th>
                                        <th class="text-end">Qty/Tray</th>
                                        <th class="text-end">Failures</th>
                                        <th class="text-end">Net Plants</th>
                                        <th class="text-center">Status</th>
                                        <th>Planted Date</th>
                                    </tr>
                                </thead>
                                <tbody id="batchTraysBody">
                                    <!-- Populated via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 2: Assigned Orders -->
                    <div class="tab-pane fade" id="tab-consumed" role="tabpanel">
                        <div class="table-responsive" style="max-height: 400px;">
                            <table class="table table-sm table-custom align-middle">
                                <thead class="sticky-top">
                                    <tr>
                                        <th>PSONO</th>
                                        <th>Client Name</th>
                                        <th class="text-end">Consumed Qty</th>
                                        <th class="text-end">Dispatched</th>
                                        <th class="text-center">Confirm Status</th>
                                        <th class="text-center">Working Status</th>
                                        <th>Allocated Date</th>
                                        <th>Remark</th>
                                    </tr>
                                </thead>
                                <tbody id="batchConsumedBody">
                                    <!-- Populated via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 3: Ready Stock Allocations -->
                    <div class="tab-pane fade" id="tab-alloc" role="tabpanel">
                        <div class="table-responsive" style="max-height: 400px;">
                            <table class="table table-sm table-custom align-middle">
                                <thead class="sticky-top">
                                    <tr>
                                        <th>Client Name</th>
                                        <th>Client ID</th>
                                        <th class="text-end">Allocated Qty</th>
                                        <th class="text-end">Healthy Plants</th>
                                        <th class="text-end">Dispatched</th>
                                        <th class="text-center">Status</th>
                                        <th>Date Created</th>
                                    </tr>
                                </thead>
                                <tbody id="batchAllocBody">
                                    <!-- Populated via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 4: Invoices & Dispatches -->
                    <div class="tab-pane fade" id="tab-invoices" role="tabpanel">
                        <div class="table-responsive" style="max-height: 400px;">
                            <table class="table table-sm table-custom align-middle">
                                <thead class="sticky-top">
                                    <tr>
                                        <th>Invoice No</th>
                                        <th>Invoice Date</th>
                                        <th>Client Name</th>
                                        <th>PSONO</th>
                                        <th class="text-end">Dispatched Plants</th>
                                        <th class="text-end">Rate</th>
                                        <th class="text-end">Amount (₹)</th>
                                        <th>Vehicle No</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="batchInvoicesBody">
                                    <!-- Populated via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">Close Inspection</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
(function($) {
    'use strict';

    // Target endpoint inside modal directory
    const API_URL = 'modal/openstockcal_modal.php';

    let openStockDataTable = null;
    let currentStockFilter = 'all';
    let currentGraftedFilter = 'all';
    let currentProductIdFilter = 'all';
    let cachedProducts = [];

    // Format numbers with commas
    function fmt(val) {
        val = parseInt(val, 10);
        return isNaN(val) ? '0' : val.toLocaleString('en-IN');
    }

    // Load Open Stock Summary Data
    function loadOpenStockSummary() {
        $('#tableLoader').show();

        $.ajax({
            url: API_URL,
            type: 'GET',
            dataType: 'json',
            data: {
                action: 'get_open_stock_summary',
                stock_filter: currentStockFilter,
                grafted_filter: currentGraftedFilter,
                product_id: (currentProductIdFilter !== 'all' && currentProductIdFilter) ? currentProductIdFilter : 0
            },
            success: function(resp) {
                $('#tableLoader').hide();
                if (!resp || !resp.status) {
                    Swal.fire('Error', resp.message || 'Failed to fetch open stock data', 'error');
                    return;
                }

                cachedProducts = resp.data.products || [];
                renderKPICards(resp.data.kpi);
                renderMainTable(cachedProducts);
            },
            error: function(xhr, status, err) {
                $('#tableLoader').hide();
                console.error(err);
                Swal.fire('Network Error', 'Unable to reach backend stock service', 'error');
            }
        });
    }

    // Load Product List into Dropdown
    function loadProductsDropdown() {
        $.ajax({
            url: API_URL,
            type: 'GET',
            dataType: 'json',
            data: { action: 'get_products_dropdown' },
            success: function(resp) {
                if (resp && resp.status && resp.data) {
                    const $sel = $('#productFilterSelect');
                    $sel.find('option:not(:first)').remove();
                    resp.data.forEach(function(item) {
                        const graftBadgeText = item.is_grafted === 1 ? ' [Grafted]' : ' [Non-Grafted]';
                        $sel.append(`<option value="${item.id}">#${item.id} - ${item.name}${graftBadgeText}</option>`);
                    });
                }
            }
        });
    }

    // Render KPI Metrics
    function renderKPICards(kpi) {
        if (!kpi) return;
        $('#kpiProductionQty').text(fmt(kpi.total_production_qty));
        $('#kpiActiveBatches').text(fmt(kpi.total_products));
        $('#kpiBedPlants').text(fmt(kpi.total_bed_plants));
        $('#kpiCommittedQty').text(fmt(kpi.total_assigned_qty + kpi.total_allocated_qty));
        $('#kpiAssignedQty').text(fmt(kpi.total_assigned_qty));
        $('#kpiAllocatedQty').text(fmt(kpi.total_allocated_qty));
        $('#kpiPhysicalOpen').text(fmt(kpi.total_physical_open));
    }

    // Render Master Table
    function renderMainTable(products) {
        if ($.fn.DataTable.isDataTable('#openStockTable')) {
            $('#openStockTable').DataTable().destroy();
        }

        const $tbody = $('#openStockTableBody').empty();

        if (!products || products.length === 0) {
            $tbody.html('<tr><td colspan="10" class="text-center py-4 text-muted">No products found matching the criteria.</td></tr>');
            return;
        }

        products.forEach(function(p, idx) {
            const physOpen = parseInt(p.physical_open_stock, 10) || 0;
            const bookOpen = parseInt(p.book_open_stock, 10) || 0;

            let physBadge = 'badge-soft-danger';
            if (physOpen > 1000) {
                physBadge = 'badge-soft-success';
            } else if (physOpen > 0) {
                physBadge = 'badge-soft-warning';
            }

            const isGrafted = p.is_grafted === 1;
            const graftBadge = isGrafted 
                ? '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 ms-1" style="font-size: 10px;"><i class="fa fa-seedling me-1"></i>Grafted</span>'
                : '<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 ms-1" style="font-size: 10px;">Non-Grafted</span>';

            const tr = `
                <tr data-product-id="${p.product_id}">
                    <td class="text-muted fw-bold">${idx + 1}</td>
                    <td>
                        <div>
                            <span class="fw-bold text-dark">${p.product_name}</span>
                            ${graftBadge}
                        </div>
                        <div class="small text-muted mono mt-1">
                            <span class="badge bg-light text-secondary border me-1">${p.category_id || 'CAT'}</span>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 me-1">ID: #${p.product_id}</span>
                            ${p.product_sku ? `<span class="text-muted">${p.product_sku}</span>` : ''}
                        </div>
                    </td>
                    <td class="text-end">
                        <span class="mono fw-bold text-dark">${fmt(p.total_production_qty)}</span>
                        <div><span class="badge bg-light text-muted border badge-count">${p.active_orders_count} batches</span></div>
                    </td>
                    <td class="text-end">
                        <span class="mono fw-bold text-info">${fmt(p.total_bed_plants)}</span>
                        <div><span class="badge badge-soft-info badge-count">${p.active_trays_count} trays</span></div>
                    </td>
                    <td class="text-end">
                        <span class="mono fw-semibold text-warning">${fmt(p.total_assigned_qty)}</span>
                    </td>
                    <td class="text-end">
                        <span class="mono fw-semibold text-primary">${fmt(p.total_allocated_qty)}</span>
                    </td>
                    <td class="text-end">
                        <span class="mono text-muted">${fmt(p.total_dispatched_qty)}</span>
                    </td>
                    <td class="text-end">
                        <span class="badge ${physBadge} badge-count fs-6">${fmt(physOpen)}</span>
                    </td>
                    <td class="text-end">
                        <span class="mono fw-bold text-dark">${fmt(bookOpen)}</span>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-outline-success btn-sm rounded-pill btn-view-batches" data-product-id="${p.product_id}" data-product-name="${p.product_name}">
                            <i class="fa fa-folder-open me-1"></i> Batches
                        </button>
                    </td>
                </tr>
            `;
            $tbody.append(tr);
        });

        openStockDataTable = $('#openStockTable').DataTable({
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            ordering: true,
            order: [[7, 'desc']], // Default sort by Physical Open Stock
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search table..."
            },
            dom: '<"d-flex justify-content-between align-items-center mb-2"l>rt<"d-flex justify-content-between align-items-center mt-3"ip>'
        });
    }

    // Row Click or Button Click: Open Modal 1 (Product Batches)
    $(document).on('click', '#openStockTableBody tr, .btn-view-batches', function(e) {
        if ($(e.target).closest('a, input, select').length) return;

        const productId = $(this).data('product-id');
        if (!productId) return;

        loadProductOrdersModal(productId);
    });

    // Load Product Orders Modal Data
    function loadProductOrdersModal(productId) {
        $('#tableProductOrdersBody').html('<tr><td colspan="12" class="text-center py-4"><div class="spinner-border text-success spinner-border-sm me-2"></div> Loading production batches...</td></tr>');
        $('#modalProductOrders').modal('show');

        $.ajax({
            url: API_URL,
            type: 'GET',
            dataType: 'json',
            data: {
                action: 'get_product_stockorders',
                product_id: productId
            },
            success: function(resp) {
                if (!resp || !resp.status) {
                    $('#tableProductOrdersBody').html(`<tr><td colspan="12" class="text-danger text-center py-3">${resp.message || 'Failed to load batches'}</td></tr>`);
                    return;
                }

                const p = resp.data.product || {};
                const orders = resp.data.orders || [];

                $('#modalProductOrdersTitle').html(`<i class="fa fa-boxes-stacked text-success me-2"></i> ${p.ProductName || 'Product Batches'}`);
                $('#modalProductOrdersSubtitle').text(`SKU: ${p.sku || 'N/A'} | Product ID: #${p.ProductID}`);

                let totTarget = 0, totBed = 0, totAssign = 0, totAvail = 0;

                const $tbody = $('#tableProductOrdersBody').empty();

                if (orders.length === 0) {
                    $tbody.html('<tr><td colspan="12" class="text-center py-4 text-muted">No active production batches found for this product.</td></tr>');
                    return;
                }

                orders.forEach(function(ord) {
                    totTarget += ord.order_qty;
                    totBed += ord.bed_plants;
                    totAssign += ord.assigned_qty;
                    totAvail += ord.physical_open_stock;

                    const tr = `
                        <tr class="cursor-pointer batch-row" data-stockorder-no="${ord.stockorder_no}">
                            <td>
                                <strong class="text-success mono">${ord.stockorder_no}</strong>
                                ${ord.psono ? `<div class="small text-muted mono">${ord.psono}</div>` : ''}
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">${ord.client_name || 'Stock Batch'}</div>
                                <div class="small text-muted">${ord.clientid || 'Internal'}</div>
                            </td>
                            <td class="mono small">${ord.sowing_date || '-'}</td>
                            <td class="mono small fw-bold">${ord.expected_delivery_date || '-'}</td>
                            <td class="text-end mono fw-bold">${fmt(ord.order_qty)}</td>
                            <td class="text-end mono fw-bold text-info">${fmt(ord.bed_plants)}</td>
                            <td class="text-end mono text-warning">${fmt(ord.assigned_qty)}</td>
                            <td class="text-end mono text-primary">${fmt(ord.allocated_qty)}</td>
                            <td class="text-end mono text-muted">${fmt(ord.dispatched_qty)}</td>
                            <td class="text-end">
                                <span class="badge ${ord.physical_open_stock > 0 ? 'badge-soft-success' : 'badge-soft-danger'} badge-count">
                                    ${fmt(ord.physical_open_stock)}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border small">${ord.status || 'Active'}</span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary rounded-pill px-3 btn-inspect-batch" data-stockorder-no="${ord.stockorder_no}">
                                    <i class="fa fa-search me-1"></i> Inspect
                                </button>
                            </td>
                        </tr>
                    `;
                    $tbody.append(tr);
                });

                // Set summary counters
                $('#mBatchCount').text(orders.length);
                $('#mBatchTotalQty').text(fmt(totTarget));
                $('#mBatchBedQty').text(fmt(totBed));
                $('#mBatchAssignQty').text(fmt(totAssign));
                $('#mBatchAvailQty').text(fmt(totAvail));
            },
            error: function() {
                $('#tableProductOrdersBody').html('<tr><td colspan="12" class="text-danger text-center py-3">Error fetching order details.</td></tr>');
            }
        });
    }

    // Click on Batch Row: Open Modal 2 (Deep Drilldown)
    $(document).on('click', '.batch-row, .btn-inspect-batch', function(e) {
        e.stopPropagation();
        const stkNo = $(this).data('stockorder-no');
        if (!stkNo) return;

        loadBatchDeepDetailsModal(stkNo);
    });

    // Load Deep Drilldown Details for a Batch
    function loadBatchDeepDetailsModal(stockOrderNo) {
        $('#modalBatchDetailsTitle').html(`<i class="fa fa-layer-group text-success me-2"></i> Inspecting Batch: <span class="mono">${stockOrderNo}</span>`);
        $('#modalBatchDetailsSubtitle').text('Loading nursery bed trays & consumption details...');

        $('#batchTraysBody, #batchConsumedBody, #batchAllocBody, #batchInvoicesBody').html('<tr><td colspan="10" class="text-center py-4"><div class="spinner-border spinner-border-sm text-success me-2"></div> Fetching data...</td></tr>');
        
        $('#modalBatchDetails').modal('show');

        $.ajax({
            url: API_URL,
            type: 'GET',
            dataType: 'json',
            data: {
                action: 'get_stockorder_deep_details',
                stockorder_no: stockOrderNo
            },
            success: function(resp) {
                if (!resp || !resp.status) {
                    Swal.fire('Error', resp.message || 'Failed to fetch batch details', 'error');
                    return;
                }

                const data = resp.data;
                const hdr = data.header || {};
                const tSum = data.tray_summary || {};
                const totals = data.totals || {};

                $('#modalBatchDetailsSubtitle').text(`${hdr.ProductName || ''} | Sown: ${hdr.sowing_date_disp || '-'} | Delivery Date: ${hdr.ready_date_disp || '-'}`);

                // Set metric pills
                $('#bMetricOrderQty').text(fmt(totals.order_qty));
                $('#bMetricBedPlants').text(fmt(totals.bed_active_qty));
                $('#bMetricAssigned').text(fmt(totals.consumed_qty));
                $('#bMetricDisp').text(fmt(totals.allocated_qty + totals.dispatched_qty));

                // 1. Render Trays Tab
                const trays = data.trays || [];
                $('#badgeTrayCount').text(trays.length);
                const $tBody = $('#batchTraysBody').empty();

                if (trays.length === 0) {
                    $tBody.html('<tr><td colspan="9" class="text-center py-4 text-muted">No tray records found in polyhouse beds for this batch.</td></tr>');
                } else {
                    trays.forEach(function(t) {
                        const st = (t.status || '').toLowerCase();
                        let stBadge = 'bg-secondary';
                        if (st === 'readytosale') stBadge = 'bg-success';
                        else if (st === 'polyhouse') stBadge = 'bg-primary';

                        const tr = `
                            <tr>
                                <td><span class="badge bg-light text-dark border">PH-${t.polyhouseid}</span></td>
                                <td><strong>${t.bed_no || '-'}</strong> <span class="text-muted small">(${t.bedrackname || '-'})</span></td>
                                <td class="mono fw-bold">${t.tray_code || 'N/A'}</td>
                                <td>
                                    <span class="small">${t.growth_stage || '-'}</span> | 
                                    <span class="badge bg-light text-success border">${t.health_status || 'Healthy'}</span>
                                </td>
                                <td class="text-end mono">${t.qtypertray}</td>
                                <td class="text-end mono text-danger">${t.failurecount}</td>
                                <td class="text-end mono fw-bold text-success">${fmt(t.net_plants)}</td>
                                <td class="text-center"><span class="badge ${stBadge}">${t.status}</span></td>
                                <td class="small text-muted mono">${t.planted_date || '-'}</td>
                            </tr>
                        `;
                        $tBody.append(tr);
                    });
                }

                // 2. Render Consumed Orders Tab
                const consumed = data.consumed_orders || [];
                $('#badgeConsCount').text(consumed.length);
                const $cBody = $('#batchConsumedBody').empty();

                if (consumed.length === 0) {
                    $cBody.html('<tr><td colspan="8" class="text-center py-4 text-muted">No client orders assigned against this batch.</td></tr>');
                } else {
                    consumed.forEach(function(c) {
                        const tr = `
                            <tr>
                                <td class="mono fw-bold text-primary">${c.psono || '-'}</td>
                                <td><strong>${c.client_name || 'Client Order'}</strong></td>
                                <td class="text-end mono fw-bold">${fmt(c.consumed_qty)}</td>
                                <td class="text-end mono text-muted">${fmt(c.dispatchedqty)}</td>
                                <td class="text-center"><span class="badge badge-soft-success">${c.confirmstatus}</span></td>
                                <td class="text-center"><span class="badge bg-light text-dark border">${c.status}</span></td>
                                <td class="small text-muted mono">${c.created_at || '-'}</td>
                                <td class="small text-muted">${c.remark || '-'}</td>
                            </tr>
                        `;
                        $cBody.append(tr);
                    });
                }

                // 3. Render Allocations Tab
                const allocs = data.allocations || [];
                $('#badgeAllocCount').text(allocs.length);
                const $aBody = $('#batchAllocBody').empty();

                if (allocs.length === 0) {
                    $aBody.html('<tr><td colspan="7" class="text-center py-4 text-muted">No confirmed sale allocations recorded.</td></tr>');
                } else {
                    allocs.forEach(function(a) {
                        const tr = `
                            <tr>
                                <td><strong>${a.client_name || '-'}</strong></td>
                                <td class="mono small text-muted">${a.clientid || '-'}</td>
                                <td class="text-end mono fw-bold text-primary">${fmt(a.allocate_ordqty)}</td>
                                <td class="text-end mono text-success">${fmt(a.healthyplant)}</td>
                                <td class="text-end mono text-muted">${fmt(a.dispatchqty)}</td>
                                <td class="text-center"><span class="badge badge-soft-info">${a.status}</span></td>
                                <td class="small text-muted mono">${a.created_at || '-'}</td>
                            </tr>
                        `;
                        $aBody.append(tr);
                    });
                }

                // 4. Render Invoices Tab
                const invoices = data.invoices || [];
                $('#badgeInvoicesCount').text(invoices.length);
                const $iBody = $('#batchInvoicesBody').empty();

                if (invoices.length === 0) {
                    $iBody.html('<tr><td colspan="9" class="text-center py-4 text-muted">No dispatched invoice items recorded for this batch.</td></tr>');
                } else {
                    invoices.forEach(function(inv) {
                        const tr = `
                            <tr>
                                <td class="mono fw-bold text-dark">${inv.invoiceno}</td>
                                <td class="small mono">${inv.invoicedate || '-'}</td>
                                <td><strong>${inv.clientname}</strong></td>
                                <td class="mono small text-muted">${inv.psono || '-'}</td>
                                <td class="text-end mono fw-bold text-success">${fmt(inv.dispatch_plants)}</td>
                                <td class="text-end mono">${inv.rate ? '₹' + inv.rate : '-'}</td>
                                <td class="text-end mono fw-bold">${inv.amount ? '₹' + fmt(inv.amount) : '-'}</td>
                                <td class="mono small">${inv.vehicleno || '-'}</td>
                                <td class="text-center"><span class="badge bg-success">Dispatched</span></td>
                            </tr>
                        `;
                        $iBody.append(tr);
                    });
                }
            },
            error: function() {
                Swal.fire('Error', 'Failed to retrieve batch deep details', 'error');
            }
        });
    }

    // Search bar filter
    $('#customSearch').on('keyup', function() {
        if (openStockDataTable) {
            openStockDataTable.search(this.value).draw();
        }
    });

    // Stock availability filter buttons
    $('#stockFilterGroup button').on('click', function() {
        $('#stockFilterGroup button').removeClass('active');
        $(this).addClass('active');
        currentStockFilter = $(this).data('filter');
        loadOpenStockSummary();
    });

    // Grafted / Non-Grafted filter buttons
    $('#graftedFilterGroup button').on('click', function() {
        $('#graftedFilterGroup button').removeClass('active');
        $(this).addClass('active');
        currentGraftedFilter = $(this).data('grafted');
        loadOpenStockSummary();
    });

    // Product ID Select dropdown filter
    $('#productFilterSelect').on('change', function() {
        currentProductIdFilter = $(this).val();
        loadOpenStockSummary();
    });

    // Refresh Button
    $('#btnRefresh').on('click', function() {
        loadOpenStockSummary();
        loadProductsDropdown();
    });

    // Export Table as CSV
    $('#btnExportCsv').on('click', function() {
        if (!cachedProducts || cachedProducts.length === 0) {
            Swal.fire('Info', 'No data available to export', 'info');
            return;
        }

        let csv = 'Product ID,Product Name,Grafted Status,SKU,Category,Production Target,Bed Living Plants,Assigned Qty,Allocated Qty,Dispatched Qty,Physical Open Stock,Book Open Stock\n';
        cachedProducts.forEach(function(p) {
            const row = [
                `"${p.product_id}"`,
                `"${(p.product_name || '').replace(/"/g, '""')}"`,
                `"${p.is_grafted === 1 ? 'Grafted' : 'Non-Grafted'}"`,
                `"${p.product_sku || ''}"`,
                `"${p.category_id || ''}"`,
                p.total_production_qty,
                p.total_bed_plants,
                p.total_assigned_qty,
                p.total_allocated_qty,
                p.total_dispatched_qty,
                p.physical_open_stock,
                p.book_open_stock
            ];
            csv += row.join(',') + '\n';
        });

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', `Product_Open_Stock_${new Date().toISOString().slice(0,10)}.csv`);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });

    // Initial Load
    $(document).ready(function() {
        loadProductsDropdown();
        loadOpenStockSummary();
    });

})(jQuery);
</script>

</body>
</html>
