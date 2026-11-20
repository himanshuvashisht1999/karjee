<?php
/**
 * openstcoklive.php
 * Real-Time Warehouse Plant Inventory vs Customer Sales Orders Dashboard
 * 
 * Summary as per Product ID:
 * - Total Warehouse Plants (Ready for Sale, Growing, Total Available Stock)
 * - Customer Demand ('Confirmed' & 'Partial Dispatch' orders to be dispatched)
 * - Sufficiency Analysis (Net Balance, Status)
 * - Action (Full detailed order list modal)
 * - Location ID is a FILTER dropdown, NOT table headers.
 * - Dynamic Year List fetched from plant table.
 * - Date Range filter: default Full Year with Custom Date Range option.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(0);
ini_set('display_errors', '0');
date_default_timezone_set('Asia/Kolkata');

$hasKey = isset($_GET['k']) && !empty($_GET['k']);
if ($hasKey && file_exists(__DIR__ . '/../../header.php')) {
    require('../../header.php');
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
    <title>Live Stock vs Orders Summary | Karjee Farm</title>

    <!-- Google Fonts & FontAwesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Bootstrap 5, DataTables & SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.1/dist/sweetalert2.min.css">

    <style>
        :root {
            --brand-primary: #10b981;
            --brand-primary-dark: #059669;
            --brand-primary-light: #ecfdf5;
            --brand-accent: #6366f1;
            --brand-accent-light: #eef2ff;
            --brand-dark: #0f172a;
            --brand-slate: #1e293b;
            --brand-muted: #64748b;
            --brand-border: #e2e8f0;
            --brand-bg: #f8fafc;
            --brand-card-bg: #ffffff;
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

        /* Top Header */
        .page-header {
            background: #ffffff;
            border-radius: 12px;
            padding: 1.1rem 1.5rem;
            margin-bottom: 1.25rem;
            border: 1px solid var(--brand-border);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .page-title {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--brand-dark);
            letter-spacing: -0.02em;
            margin-bottom: 0.2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .page-subtitle {
            color: var(--brand-muted);
            font-size: 0.825rem;
            margin-bottom: 0;
        }

        /* KPI Cards */
        .kpi-card {
            background: #ffffff;
            border: 1px solid var(--brand-border);
            border-radius: 12px;
            padding: 1rem 1.15rem;
            position: relative;
            overflow: hidden;
            transition: all 0.2s ease-in-out;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            height: 100%;
        }

        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.08);
            border-color: #cbd5e1;
        }

        .kpi-icon-wrapper {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            flex-shrink: 0;
        }

        .kpi-title {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--brand-muted);
            margin-bottom: 0.25rem;
        }

        .kpi-value {
            font-size: 1.45rem;
            font-weight: 800;
            color: var(--brand-dark);
            line-height: 1.1;
            font-family: 'JetBrains Mono', monospace;
        }

        .kpi-subtext {
            font-size: 0.72rem;
            color: var(--brand-muted);
            margin-top: 0.35rem;
        }

        /* Filter Controls Bar */
        .filter-card {
            background: #ffffff;
            border: 1px solid var(--brand-border);
            border-radius: 12px;
            padding: 1.1rem 1.25rem;
            margin-bottom: 1.25rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        .form-label-custom {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: var(--brand-slate);
            margin-bottom: 0.35rem;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .form-control-custom, .form-select-custom {
            border: 1px solid var(--brand-border);
            border-radius: 8px;
            padding: 0.45rem 0.75rem;
            font-size: 0.85rem;
            color: var(--brand-dark);
            background-color: #f8fafc;
            transition: all 0.2s ease;
        }

        .form-control-custom:focus, .form-select-custom:focus {
            background-color: #ffffff;
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
            outline: none;
        }

        /* Table Styling */
        .table-card {
            background: #ffffff;
            border: 1px solid var(--brand-border);
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        /* Scroll-Y Container with at least 10 entries displayed before scrolling */
        .table-scroll-container {
            max-height: 520px;
            overflow-y: auto;
            overflow-x: auto;
            position: relative;
            border: 1px solid var(--brand-border);
            border-radius: 8px;
        }

        .table-custom {
            width: 100% !important;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.825rem;
            margin-bottom: 0 !important;
        }

        /* Sticky Table Header */
        .table-custom thead th {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #f8fafc;
            color: #1e293b;
            font-weight: 700;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 0.75rem 0.65rem;
            border-bottom: 2px solid #cbd5e1;
            white-space: nowrap;
            vertical-align: middle;
            box-shadow: 0 2px 3px rgba(0, 0, 0, 0.06);
            background-clip: padding-box;
        }

        /* Section color accents on header columns */
        .table-custom thead th.th-stock {
            background-color: #ecfdf5 !important;
            color: #065f46 !important;
            border-top: 3px solid #10b981;
            border-bottom-color: #6ee7b7 !important;
        }

        .table-custom thead th.th-demand {
            background-color: #fff7ed !important;
            color: #9a3412 !important;
            border-top: 3px solid #f59e0b;
            border-bottom-color: #fed7aa !important;
        }

        .table-custom thead th.th-analysis {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
            border-top: 3px solid #6366f1;
            border-bottom-color: #cbd5e1 !important;
        }

        /* Sticky Footer */
        .table-custom tfoot {
            position: sticky;
            bottom: 0;
            z-index: 10;
        }

        .table-custom tfoot th {
            position: sticky;
            bottom: 0;
            z-index: 10;
            background-color: #f8fafc !important;
            border-top: 2px solid #cbd5e1;
            box-shadow: 0 -2px 3px rgba(0, 0, 0, 0.04);
        }

        .table-custom tbody tr {
            cursor: pointer;
            transition: background-color 0.15s ease;
        }

        .table-custom tbody tr:hover td {
            background-color: #f1f5f9;
        }

        .table-custom tbody td {
            padding: 0.65rem 0.65rem;
            border-bottom: 1px solid var(--brand-border);
            vertical-align: middle;
        }

        /* Status Badges */
        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.3rem 0.65rem;
            border-radius: 6px;
            font-weight: 700;
            font-size: 0.72rem;
            letter-spacing: 0.02em;
            white-space: nowrap;
        }

        .badge-status-ready {
            background-color: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .badge-status-growing {
            background-color: #eff6ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }

        .badge-status-shortage {
            background-color: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .badge-status-surplus {
            background-color: #f0fdfa;
            color: #115e59;
            border: 1px solid #99f6e4;
        }

        .badge-status-zero {
            background-color: #fff1f2;
            color: #881337;
            border: 1px solid #ffe4e6;
        }

        /* Modal Customizations */
        .modal-custom .modal-content {
            border-radius: 14px;
            border: 1px solid var(--brand-border);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .modal-custom .modal-header {
            background-color: #f8fafc;
            border-bottom: 1px solid var(--brand-border);
            padding: 1rem 1.5rem;
            border-top-left-radius: 14px;
            border-top-right-radius: 14px;
        }

        .modal-custom .modal-title {
            font-weight: 800;
            font-size: 1.15rem;
            color: var(--brand-dark);
        }

        .nav-tabs-custom .nav-link {
            font-weight: 700;
            font-size: 0.85rem;
            color: var(--brand-muted);
            border: none;
            border-bottom: 2px solid transparent;
            padding: 0.6rem 1.1rem;
        }

        .nav-tabs-custom .nav-link.active {
            color: var(--brand-primary-dark);
            border-bottom-color: var(--brand-primary);
            background: transparent;
        }
    </style>
</head>
<body>

<div class="container-fluid px-3 py-3">

    <!-- Page Header -->
    <div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <div class="page-title">
                <i class="fa-solid fa-chart-pie text-success"></i>
                Warehouse Plant Stock vs Customer Demand Summary
            </div>
            <div class="page-subtitle d-flex flex-wrap align-items-center gap-2 mt-1">
                <span>Summary as per Product ID • Live Sufficiency Analysis</span>
                <span class="text-muted">•</span>
                <span class="text-primary fw-semibold" id="activeFilterSummary">All Locations • Full Year Range</span>
                <span class="text-muted">•</span>
                <span class="text-secondary"><i class="fa-regular fa-clock me-1"></i>Project: #<?= $projectid ?></span>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm btn-outline-primary" id="btnRefresh" title="Refresh Live Calculations">
                <i class="fa-solid fa-arrows-rotate me-1"></i> Refresh Data
            </button>
        </div>
    </div>

    <!-- KPI Summary Row -->
    <div class="row g-2 mb-3">
        <!-- KPI 1: Warehouse Total Available Stock -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="kpi-card">
                <div class="d-flex align-items-center gap-3">
                    <div class="kpi-icon-wrapper" style="background-color: #ecfdf5; color: #059669;">
                        <i class="fa-solid fa-warehouse"></i>
                    </div>
                    <div>
                        <div class="kpi-title">Warehouse Stock</div>
                        <div class="kpi-value text-success" id="kpiWarehouseStock">0</div>
                    </div>
                </div>
                <div class="kpi-subtext d-flex justify-content-between">
                    <span>Trays: <b id="kpiTotalTrays">0</b></span>
                    <span>Products: <b id="kpiTotalProducts">0</b></span>
                </div>
            </div>
        </div>

        <!-- KPI 2: Ready for Sale Stock -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="kpi-card">
                <div class="d-flex align-items-center gap-3">
                    <div class="kpi-icon-wrapper" style="background-color: #e0f2fe; color: #0284c7;">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <div>
                        <div class="kpi-title">Ready for Sale</div>
                        <div class="kpi-value text-primary" id="kpiReadyStock">0</div>
                    </div>
                </div>
                <div class="kpi-subtext">
                    <i class="fa-solid fa-truck-fast text-info me-1"></i> Immediate Fulfillment Ready
                </div>
            </div>
        </div>

        <!-- KPI 3: Growing in Polyhouse -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="kpi-card">
                <div class="d-flex align-items-center gap-3">
                    <div class="kpi-icon-wrapper" style="background-color: #f3e8ff; color: #7c3aed;">
                        <i class="fa-solid fa-seedling"></i>
                    </div>
                    <div>
                        <div class="kpi-title">Growing in Polyhouse</div>
                        <div class="kpi-value" style="color: #7c3aed;" id="kpiGrowingStock">0</div>
                    </div>
                </div>
                <div class="kpi-subtext">
                    <i class="fa-solid fa-sun text-warning me-1"></i> Available Growing Stock
                </div>
            </div>
        </div>

        <!-- KPI 4: Customer Order Demand -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="kpi-card">
                <div class="d-flex align-items-center gap-3">
                    <div class="kpi-icon-wrapper" style="background-color: #fff7ed; color: #ea580c;">
                        <i class="fa-solid fa-cart-flatbed-suitcase"></i>
                    </div>
                    <div>
                        <div class="kpi-title" id="kpiOrderTitle">Net to Dispatch</div>
                        <div class="kpi-value text-warning" id="kpiOrderDemand">0</div>
                    </div>
                </div>
                <div class="kpi-subtext" id="kpiOrderSubtext">
                    Confirmed & Partial Dispatch
                </div>
            </div>
        </div>

        <!-- KPI 5: Net Balance -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="kpi-card">
                <div class="d-flex align-items-center gap-3">
                    <div class="kpi-icon-wrapper" id="kpiBalanceIconWrapper" style="background-color: #f8fafc; color: #475569;">
                        <i class="fa-solid fa-scale-balanced" id="kpiBalanceIcon"></i>
                    </div>
                    <div>
                        <div class="kpi-title">Net Balance</div>
                        <div class="kpi-value" id="kpiNetBalance">0</div>
                    </div>
                </div>
                <div class="kpi-subtext" id="kpiBalanceSubtext">
                    (Warehouse Stock - Demand)
                </div>
            </div>
        </div>

        <!-- KPI 6: Shortage Alert Count -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="kpi-card" style="border-left: 4px solid var(--brand-danger);">
                <div class="d-flex align-items-center gap-3">
                    <div class="kpi-icon-wrapper" style="background-color: #fee2e2; color: #dc2626;">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div>
                        <div class="kpi-title text-danger">Plant Shortages</div>
                        <div class="kpi-value text-danger" id="kpiShortageCount">0</div>
                    </div>
                </div>
                <div class="kpi-subtext text-danger">
                    <span>Products with stock deficit</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Control Bar -->
    <div class="filter-card">
        <div class="row g-2 align-items-end">
            
            <!-- 1. Year Filter (Dynamic from Plant Table) -->
            <div class="col-xl-2 col-md-3 col-sm-6">
                <label class="form-label-custom">
                    <i class="fa-regular fa-calendar text-primary"></i> Year (Plant Table)
                </label>
                <select class="form-select form-select-custom" id="filterYear">
                    <option value="2026" selected>Loading Years...</option>
                </select>
            </div>

            <!-- 2. Date Range Mode (Full Year default vs Custom) -->
            <div class="col-xl-2 col-md-3 col-sm-6">
                <label class="form-label-custom">
                    <i class="fa-solid fa-calendar-week text-success"></i> Date Range Mode
                </label>
                <select class="form-select form-select-custom" id="filterDateMode">
                    <option value="full_year" selected>Full Year (Default)</option>
                    <option value="custom">Custom Date Range</option>
                    <option value="all_time">All Time (No Date Limit)</option>
                </select>
            </div>

            <!-- 3. Custom Date From & To (Toggled on Custom) -->
            <div class="col-xl-2 col-md-3 col-sm-6 custom-date-col d-none">
                <label class="form-label-custom">From Date</label>
                <input type="date" class="form-control form-control-custom" id="filterDateFrom">
            </div>

            <div class="col-xl-2 col-md-3 col-sm-6 custom-date-col d-none">
                <label class="form-label-custom">To Date</label>
                <input type="date" class="form-control form-control-custom" id="filterDateTo">
            </div>

            <!-- 4. Location Filter (Requirement: locationid is like filters user not table headers) -->
            <div class="col-xl-2 col-md-3 col-sm-6">
                <label class="form-label-custom">
                    <i class="fa-solid fa-location-dot text-info"></i> Polyhouse Location
                </label>
                <select class="form-select form-select-custom" id="filterLocation">
                    <option value="all" selected>All Locations (Loc 1 + Loc 2)</option>
                    <option value="LOC6905e0fd5ab05">Location 1: LOC6905e0fd5ab05 (9 PH)</option>
                    <option value="LOC696b280faf104">Location 2: LOC696b280faf104 (2 PH)</option>
                </select>
            </div>

            <!-- 5. Order Demand Filter -->
            <div class="col-xl-2 col-md-3 col-sm-6">
                <label class="form-label-custom">
                    <i class="fa-solid fa-cart-shopping text-warning"></i> Customer Demand
                </label>
                <select class="form-select form-select-custom" id="filterDemandType">
                    <option value="confirmed_partial" selected>Confirmed & Partial Dispatch</option>
                    <option value="confirmed_only">Confirmed Orders Only</option>
                    <option value="pending">All Pending Dispatch Orders</option>
                    <option value="all">All Booked Orders</option>
                </select>
            </div>

            <!-- 6. Sufficiency Status Filter -->
            <div class="col-xl-2 col-md-3 col-sm-6">
                <label class="form-label-custom">
                    <i class="fa-solid fa-shield-halved text-success"></i> Stock Status
                </label>
                <select class="form-select form-select-custom" id="filterStatus">
                    <option value="all">All Statuses</option>
                    <option value="sufficient">Sufficient (Ready or Growing)</option>
                    <option value="ready_sufficient">Ready Stock Sufficient</option>
                    <option value="growing_needed">Sufficient With Growing</option>
                    <option value="shortage">Shortage / Deficit Only</option>
                    <option value="surplus_only">Surplus Available (No Orders)</option>
                    <option value="zero_stock">Zero Stock (Demand Waiting)</option>
                </select>
            </div>

            <!-- 7. Search Bar -->
            <div class="col-xl-2 col-md-3 col-sm-12">
                <label class="form-label-custom">
                    <i class="fa-solid fa-magnifying-glass text-secondary"></i> Search Product
                </label>
                <div class="input-group">
                    <input type="text" class="form-control form-control-custom" id="filterSearch" placeholder="Name or ID...">
                    <button class="btn btn-outline-secondary btn-sm" type="button" id="btnClearSearch">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- Main Comparison Table: SUMMARY AS PER PRODUCT ID -->
    <div class="table-card">
        <div class="table-scroll-container">
            <table id="tblStockLive" class="table table-custom table-hover">
                <thead>
                    <tr>
                        <th>Plant Name & Variety</th>
                        <th>Type</th>

                        <!-- Total Warehouse Plants -->
                        <th class="th-stock">Ready to Sale</th>
                        <th class="th-stock">Growing Stock</th>
                        <th class="th-stock">Total Stock</th>

                        <!-- Customer Demand (Confirmed & Partial Dispatch) -->
                        <th class="th-demand">Net to Dispatch</th>
                        <th class="th-demand">Dispatched</th>
                        <th class="th-demand">Total Orders</th>
                        <th class="th-demand">Orders</th>

                        <!-- Sufficiency Analysis -->
                        <th class="th-analysis">Net Balance</th>
                        <th class="th-analysis">Sufficiency Status</th>

                        <!-- Action -->
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Populated dynamically via AJAX -->
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <th colspan="2" class="text-end">Grand Totals:</th>
                        <th class="mono text-primary" id="ftTotReady">0</th>
                        <th class="mono" id="ftTotGrowing">0</th>
                        <th class="mono text-success fw-extrabold fs-6" id="ftGrandStock">0</th>
                        <th class="mono text-warning fw-extrabold fs-6" id="ftGrandNetDemand">0</th>
                        <th class="mono text-muted" id="ftGrandDispatched">0</th>
                        <th class="mono" id="ftGrandTotalOrders">0</th>
                        <th class="mono text-center" id="ftGrandOrdersCount">0</th>
                        <th class="mono fw-extrabold fs-6" id="ftGrandNetBalance">0</th>
                        <th id="ftSufficiencySummary">Summary</th>
                        <th>-</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>

<!-- Full Order Details Drilldown Modal -->
<div class="modal fade modal-custom" id="modalProductDetails" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="modalProductTitle">
                        <i class="fa-solid fa-leaf text-success me-2"></i> Plant Details & Customer Orders
                    </h5>
                    <div class="text-muted small" id="modalProductSubtitle">Comprehensive customer sales orders and polyhouse plant locations</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <!-- Modal Body with Tabs -->
            <div class="modal-body p-3">
                
                <!-- Product Quick Stats Header inside Modal -->
                <div class="row g-2 mb-3">
                    <div class="col-md-3 col-6">
                        <div class="p-2 border rounded bg-light text-center">
                            <small class="text-muted text-uppercase fw-bold" style="font-size: 0.68rem;">Ready for Sale</small>
                            <div class="mono fw-bold fs-6 text-primary" id="mStatReady">0</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="p-2 border rounded bg-light text-center">
                            <small class="text-muted text-uppercase fw-bold" style="font-size: 0.68rem;">Total Warehouse Stock</small>
                            <div class="mono fw-bold fs-6 text-success" id="mStatStock">0</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="p-2 border rounded bg-light text-center">
                            <small class="text-muted text-uppercase fw-bold" style="font-size: 0.68rem;">Net to Dispatch</small>
                            <div class="mono fw-bold fs-6 text-warning" id="mStatDemand">0</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="p-2 border rounded bg-light text-center">
                            <small class="text-muted text-uppercase fw-bold" style="font-size: 0.68rem;">Net Balance</small>
                            <div class="mono fw-bold fs-6" id="mStatBalance">0</div>
                        </div>
                    </div>
                </div>

                <!-- Tabs: Customer Orders vs Polyhouse Trays -->
                <ul class="nav nav-tabs nav-tabs-custom mb-3" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" id="tabOrders-tab" data-bs-toggle="tab" data-bs-target="#tabOrders" type="button" role="tab">
                            <i class="fa-solid fa-file-invoice me-1 text-warning"></i> Customer Sales Orders List (<span id="mOrderCount">0</span>)
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="tabTrays-tab" data-bs-toggle="tab" data-bs-target="#tabTrays" type="button" role="tab">
                            <i class="fa-solid fa-cubes-stacked me-1 text-success"></i> Polyhouse Warehouse Trays (<span id="mTrayCount">0</span>)
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    
                    <!-- Tab 1: Detailed Customer Orders List -->
                    <div class="tab-pane fade show active" id="tabOrders" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped table-hover table-bordered" style="font-size: 0.8rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>SO No</th>
                                        <th>PSONO</th>
                                        <th>Client Name & ID</th>
                                        <th>Contact & Delivery Location</th>
                                        <th>Delivery Date</th>
                                        <th>Confirm Date</th>
                                        <th class="text-end">Order Qty</th>
                                        <th class="text-end">Dispatched</th>
                                        <th class="text-end">Net to Ship</th>
                                        <th>Status</th>
                                        <th>Rate</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody id="tblProductOrdersBody">
                                    <!-- Populated via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 2: Polyhouse Trays Breakdown -->
                    <div class="tab-pane fade" id="tabTrays" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped table-hover table-bordered" style="font-size: 0.8rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Location</th>
                                        <th>Polyhouse</th>
                                        <th>Bed / Rack / Row</th>
                                        <th>Tray Code / Barcode</th>
                                        <th>Sowing Date</th>
                                        <th>Status</th>
                                        <th>Health</th>
                                        <th class="text-end">Tray Qty</th>
                                        <th class="text-end">Failures</th>
                                        <th class="text-end">Available Plants</th>
                                    </tr>
                                </thead>
                                <tbody id="tblProductTraysBody">
                                    <!-- Populated via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.1/dist/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function() {
    const API_URL = 'modal/openstocklivemodal.php';
    let dataTableInstance = null;
    let liveDataCache = [];

    // Format numbers with commas
    function formatNum(num) {
        if (num === null || num === undefined || isNaN(num)) return '0';
        return parseInt(num, 10).toLocaleString('en-IN');
    }

    // 1. Fetch Year List Dynamically from Plant Table
    function loadYearsList() {
        $.ajax({
            url: API_URL,
            type: 'POST',
            dataType: 'json',
            data: { action: 'get_years_list' },
            success: function(resp) {
                if (resp.status && resp.data && resp.data.length > 0) {
                    const sel = $('#filterYear');
                    sel.empty();
                    resp.data.forEach((yr, idx) => {
                        sel.append(`<option value="${yr}" ${idx === 0 ? 'selected' : ''}>Year ${yr}</option>`);
                    });
                    sel.append('<option value="all">All Years</option>');
                    
                    // Sync dates and load initial summary
                    syncDateRange();
                    loadLiveStockSummary();
                } else {
                    loadLiveStockSummary();
                }
            },
            error: function() {
                loadLiveStockSummary();
            }
        });
    }

    // 2. Synchronize Date Range based on Year and Date Range Mode
    function syncDateRange() {
        const mode = $('#filterDateMode').val();
        const year = $('#filterYear').val();

        if (mode === 'full_year') {
            $('.custom-date-col').addClass('d-none');
            if (year && year !== 'all') {
                $('#filterDateFrom').val(`${year}-01-01`);
                $('#filterDateTo').val(`${year}-12-31`);
            } else {
                $('#filterDateFrom').val('');
                $('#filterDateTo').val('');
            }
        } else if (mode === 'custom') {
            $('.custom-date-col').removeClass('d-none');
            if (!$('#filterDateFrom').val()) {
                const yr = (year && year !== 'all') ? year : new Date().getFullYear();
                $('#filterDateFrom').val(`${yr}-01-01`);
                $('#filterDateTo').val(`${yr}-12-31`);
            }
        } else if (mode === 'all_time') {
            $('.custom-date-col').addClass('d-none');
            $('#filterDateFrom').val('');
            $('#filterDateTo').val('');
        }
    }

    // 3. Load Live Stock Summary as per Product ID
    function loadLiveStockSummary() {
        const demandType     = $('#filterDemandType').val();
        const statusFilter   = $('#filterStatus').val();
        const locationFilter = $('#filterLocation').val();
        const yearVal        = $('#filterYear').val();
        const dateMode       = $('#filterDateMode').val();
        const dateFrom       = (dateMode === 'all_time') ? '' : $('#filterDateFrom').val();
        const dateTo         = (dateMode === 'all_time') ? '' : $('#filterDateTo').val();
        const searchVal      = $('#filterSearch').val();

        // Update active filter summary badge
        let locText = "All Locations (Loc 1 + Loc 2)";
        if (locationFilter === 'LOC6905e0fd5ab05') locText = "Location 1 (LOC6905e0fd5ab05)";
        if (locationFilter === 'LOC696b280faf104') locText = "Location 2 (LOC696b280faf104)";

        let dateText = `Year ${yearVal} (Full Year)`;
        if (dateMode === 'custom') dateText = `${dateFrom} to ${dateTo}`;
        if (dateMode === 'all_time') dateText = "All Time";

        $('#activeFilterSummary').text(`${locText} • ${dateText}`);

        Swal.fire({
            title: 'Calculating Live Stock vs Orders...',
            text: 'Fetching polyhouse plants and orders...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url: API_URL,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_live_stock_summary',
                demand_type: demandType,
                status_filter: statusFilter,
                location_filter: locationFilter,
                year: yearVal,
                date_from: dateFrom,
                date_to: dateTo,
                search: searchVal
            },
            success: function(response) {
                Swal.close();
                if (!response.status) {
                    Swal.fire('Error', response.message || 'Failed to fetch stock data', 'error');
                    return;
                }

                liveDataCache = response.data || [];
                const kpi = response.meta || {};

                // Update KPI Cards
                $('#kpiWarehouseStock').text(formatNum(kpi.total_warehouse_stock));
                $('#kpiReadyStock').text(formatNum(kpi.total_ready_stock));
                $('#kpiGrowingStock').text(formatNum(kpi.total_growing_stock));
                $('#kpiOrderDemand').text(formatNum(kpi.total_order_demand));
                $('#kpiTotalTrays').text(formatNum(kpi.total_trays));
                $('#kpiTotalProducts').text(formatNum(kpi.total_products));
                $('#kpiShortageCount').text(formatNum(kpi.count_shortage + kpi.count_zero_stock));

                // Net Farm Balance Card
                const netBal = kpi.total_net_balance || 0;
                $('#kpiNetBalance').text((netBal >= 0 ? '+' : '') + formatNum(netBal));
                if (netBal >= 0) {
                    $('#kpiNetBalance').removeClass('text-danger').addClass('text-success');
                    $('#kpiBalanceIconWrapper').css({ 'background-color': '#ecfdf5', 'color': '#059669' });
                    $('#kpiBalanceSubtext').html('<span class="text-success"><i class="fa-solid fa-arrow-trend-up me-1"></i>Overall Farm Surplus</span>');
                } else {
                    $('#kpiNetBalance').removeClass('text-success').addClass('text-danger');
                    $('#kpiBalanceIconWrapper').css({ 'background-color': '#fee2e2', 'color': '#dc2626' });
                    $('#kpiBalanceSubtext').html('<span class="text-danger"><i class="fa-solid fa-arrow-trend-down me-1"></i>Overall Farm Shortage</span>');
                }

                // Render Table Rows
                renderTable(liveDataCache);
            },
            error: function(xhr, status, error) {
                Swal.close();
                Swal.fire('Connection Error', 'Failed to reach server: ' + error, 'error');
            }
        });
    }

    // 4. Render Data into Main DataTable (Summary as per Product ID)
    function renderTable(data) {
        if (dataTableInstance) {
            dataTableInstance.destroy();
        }

        const tbody = $('#tblStockLive tbody');
        tbody.empty();

        let sumTotReady = 0, sumTotGrow = 0, sumGrandStock = 0;
        let sumGrandNetDemand = 0, sumGrandDispatched = 0, sumGrandTotalOrders = 0, sumGrandOrdersCount = 0;
        let sumNetBalance = 0;

        data.forEach(item => {
            const prodId = item.product_id;
            const prodName = item.product_name;
            const isGrafted = parseInt(item.is_grafted, 10) === 1;

            const totReady = parseInt(item.ready_stock_qty, 10);
            const totGrow  = parseInt(item.growing_stock_qty, 10);
            const grandStock = parseInt(item.total_warehouse_stock, 10);

            const netDemand   = parseInt(item.net_to_dispatch_qty, 10);
            const dispatched  = parseInt(item.dispatched_qty, 10);
            const totalOrders = parseInt(item.total_order_qty, 10);
            const orderCount  = parseInt(item.active_orders_count, 10);

            const netBal    = parseInt(item.net_balance, 10);
            const statusCode = item.status_code;

            // Totals accumulation
            sumTotReady += totReady; sumTotGrow += totGrow; sumGrandStock += grandStock;
            sumGrandNetDemand += netDemand; sumGrandDispatched += dispatched; 
            sumGrandTotalOrders += totalOrders; sumGrandOrdersCount += orderCount;
            sumNetBalance += netBal;

            // Status Badge Formatting
            let badgeHtml = '';
            if (statusCode === 'sufficient_ready') {
                badgeHtml = `<span class="badge-status badge-status-ready"><i class="fa-solid fa-circle-check"></i> Ready Sufficient</span>`;
            } else if (statusCode === 'sufficient_grow') {
                badgeHtml = `<span class="badge-status badge-status-growing"><i class="fa-solid fa-leaf"></i> Sufficient w/ Growing</span>`;
            } else if (statusCode === 'shortage') {
                badgeHtml = `<span class="badge-status badge-status-shortage"><i class="fa-solid fa-circle-exclamation"></i> Shortage (-${formatNum(item.shortage_qty)})</span>`;
            } else if (statusCode === 'surplus_only') {
                badgeHtml = `<span class="badge-status badge-status-surplus"><i class="fa-solid fa-box-open"></i> Surplus (${formatNum(grandStock)})</span>`;
            } else if (statusCode === 'zero_stock') {
                badgeHtml = `<span class="badge-status badge-status-zero"><i class="fa-solid fa-triangle-exclamation"></i> Zero Stock (Demand ${formatNum(netDemand)})</span>`;
            } else {
                badgeHtml = `<span class="badge bg-secondary">Balanced</span>`;
            }

            // Net balance badge styling
            let netBalHtml = '';
            if (netBal > 0) {
                netBalHtml = `<span class="text-success fw-bold">+${formatNum(netBal)}</span>`;
            } else if (netBal < 0) {
                netBalHtml = `<span class="text-danger fw-bold">${formatNum(netBal)}</span>`;
            } else {
                netBalHtml = `<span class="text-muted fw-bold">0</span>`;
            }

            const tr = `
                <tr class="product-row" data-id="${prodId}" data-productid="${prodId}" data-name="${encodeURIComponent(prodName)}">
                    <!-- Plant Name & Variety -->
                    <td>
                        <div class="fw-bold text-dark">${prodName}</div>
                    </td>
                    <td>
                        ${isGrafted 
                            ? '<span class="badge bg-purple text-white" style="background-color: #8b5cf6; font-size: 0.68rem;">Grafted</span>' 
                            : '<span class="badge bg-light text-secondary border" style="font-size: 0.68rem;">Non-Grafted</span>'}
                    </td>

                    <!-- Total Warehouse Plants -->
                    <td class="mono text-end text-primary fw-semibold">${formatNum(totReady)}</td>
                    <td class="mono text-end text-muted">${formatNum(totGrow)}</td>
                    <td class="mono text-end fw-extrabold fs-7" style="color: #047857;">${formatNum(grandStock)}</td>

                    <!-- Customer Demand ('Confirmed, Partial Dispatch' to be dispatched) -->
                    <td class="mono text-end fw-extrabold text-warning fs-7">${formatNum(netDemand)}</td>
                    <td class="mono text-end text-muted">${formatNum(dispatched)}</td>
                    <td class="mono text-end">${formatNum(totalOrders)}</td>
                    <td class="mono text-center text-muted">${orderCount > 0 ? `<span class="badge bg-light text-dark border">${orderCount}</span>` : '-'}</td>

                    <!-- Sufficiency Analysis -->
                    <td class="mono text-end fs-7">${netBalHtml}</td>
                    <td>${badgeHtml}</td>

                    <!-- Action -->
                    <td class="text-center" onclick="event.stopPropagation();">
                        <button class="btn btn-sm btn-primary py-1 px-2 btn-open-drilldown" data-id="${prodId}" data-productid="${prodId}" data-name="${encodeURIComponent(prodName)}" title="Open Full Details">
                            <i class="fa-solid fa-folder-open me-1"></i> Details
                        </button>
                    </td>
                </tr>
            `;
            tbody.append(tr);
        });

        // Update Footer Totals
        $('#ftTotReady').text(formatNum(sumTotReady));
        $('#ftTotGrowing').text(formatNum(sumTotGrow));
        $('#ftGrandStock').text(formatNum(sumGrandStock));
        $('#ftGrandNetDemand').text(formatNum(sumGrandNetDemand));
        $('#ftGrandDispatched').text(formatNum(sumGrandDispatched));
        $('#ftGrandTotalOrders').text(formatNum(sumGrandTotalOrders));
        $('#ftGrandOrdersCount').text(formatNum(sumGrandOrdersCount));
        $('#ftGrandNetBalance').text((sumNetBalance >= 0 ? '+' : '') + formatNum(sumNetBalance));
        if (sumNetBalance >= 0) {
            $('#ftGrandNetBalance').removeClass('text-danger').addClass('text-success');
        } else {
            $('#ftGrandNetBalance').removeClass('text-success').addClass('text-danger');
        }

        // Initialize DataTable
        dataTableInstance = $('#tblStockLive').DataTable({
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            order: [[4, 'desc']], // Order by Total Stock descending
            dom: "<'row mb-2'<'col-sm-6 d-flex align-items-center gap-2'lB><'col-sm-6'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row mt-2'<'col-sm-5'i><'col-sm-7'p>>",
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: '<i class="fa-solid fa-file-excel me-1 text-success"></i> Excel',
                    className: 'btn btn-sm btn-outline-secondary',
                    title: 'Karjee_Farm_Warehouse_Stock_vs_Customer_Demand'
                },
                {
                    extend: 'csvHtml5',
                    text: '<i class="fa-solid fa-file-csv me-1 text-primary"></i> CSV',
                    className: 'btn btn-sm btn-outline-secondary',
                    title: 'Karjee_Farm_Warehouse_Stock_vs_Customer_Demand'
                },
                {
                    extend: 'print',
                    text: '<i class="fa-solid fa-print me-1 text-dark"></i> Print',
                    className: 'btn btn-sm btn-outline-secondary'
                }
            ],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search product in table..."
            }
        });
    }

    // 5. Click Row or Action to Open Full Detailed Order List
    $(document).on('click', '.product-row, .btn-open-drilldown', function(e) {
        e.stopPropagation();
        const prodId   = $(this).data('id');
        const prodName = decodeURIComponent($(this).data('name') || '');

        openProductDetailsModal(prodId, prodName);
    });

    function openProductDetailsModal(prodId, prodName) {
        const demandType = $('#filterDemandType').val();
        const dateMode   = $('#filterDateMode').val();
        const yearVal    = $('#filterYear').val();
        const dateFrom   = (dateMode === 'all_time') ? '' : $('#filterDateFrom').val();
        const dateTo     = (dateMode === 'all_time') ? '' : $('#filterDateTo').val();

        $('#modalProductTitle').html(`<i class="fa-solid fa-seedling text-success me-2"></i> ${prodName}`);
        $('#modalProductSubtitle').text(`Product ID #${prodId} • Full Customer Sales Orders & Polyhouse Inventory`);

        // Find product cache item for quick stats
        const cachedItem = liveDataCache.find(p => String(p.product_id) === String(prodId));
        if (cachedItem) {
            $('#mStatReady').text(formatNum(cachedItem.ready_stock_qty));
            $('#mStatStock').text(formatNum(cachedItem.total_warehouse_stock));
            $('#mStatDemand').text(formatNum(cachedItem.net_to_dispatch_qty));
            const bal = parseInt(cachedItem.net_balance, 10);
            $('#mStatBalance').text((bal >= 0 ? '+' : '') + formatNum(bal));
            if (bal >= 0) {
                $('#mStatBalance').removeClass('text-danger').addClass('text-success');
            } else {
                $('#mStatBalance').removeClass('text-success').addClass('text-danger');
            }
        }

        $('#tblProductOrdersBody').html('<tr><td colspan="13" class="text-center py-4"><i class="fa-solid fa-spinner fa-spin me-2"></i>Loading sales orders...</td></tr>');
        $('#tblProductTraysBody').html('<tr><td colspan="11" class="text-center py-4"><i class="fa-solid fa-spinner fa-spin me-2"></i>Loading polyhouse trays...</td></tr>');
        $('#modalProductDetails').modal('show');

        // Fetch Orders Drilldown
        $.ajax({
            url: API_URL,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_plant_orders_drilldown',
                product_id: prodId,
                demand_type: demandType,
                year: yearVal,
                date_from: dateFrom,
                date_to: dateTo
            },
            success: function(resp) {
                if (!resp.status) {
                    $('#tblProductOrdersBody').html(`<tr><td colspan="13" class="text-danger text-center">${resp.message}</td></tr>`);
                    return;
                }

                const orders = resp.data || [];
                const meta = resp.meta || {};
                $('#mOrderCount').text(orders.length);

                if (orders.length === 0) {
                    $('#tblProductOrdersBody').html('<tr><td colspan="13" class="text-center text-muted py-3">No matching customer orders found for this product.</td></tr>');
                    return;
                }

                let html = '';
                orders.forEach((o, i) => {
                    const balToShip = parseInt(o.balance_to_dispatch, 10);
                    let itemStatusBadge = '<span class="badge bg-secondary">Pending</span>';
                    if (o.item_status === 'Confirmed') {
                        itemStatusBadge = '<span class="badge bg-success">Confirmed</span>';
                    } else if (o.item_status === 'PartialDispatch') {
                        itemStatusBadge = '<span class="badge bg-info text-dark">Partial Dispatch</span>';
                    } else if (o.item_status === 'Allocated') {
                        itemStatusBadge = '<span class="badge bg-primary">Allocated</span>';
                    } else if (o.item_status === 'Dispatched' || o.item_status === 'FullDispatch') {
                        itemStatusBadge = '<span class="badge bg-dark">Dispatched</span>';
                    }

                    html += `
                        <tr>
                            <td class="text-center text-muted">${i + 1}</td>
                            <td class="mono fw-bold"><b>${o.sono || '-'}</b></td>
                            <td class="mono small text-secondary">${o.psono || '-'}</td>
                            <td>
                                <div class="fw-bold text-dark">${o.clientname || 'Unknown Client'}</div>
                                ${o.clientid ? `<small class="text-muted">Client ID: ${o.clientid}</small>` : ''}
                            </td>
                            <td>
                                <div><i class="fa-solid fa-phone me-1 small text-muted"></i>${o.mobileno || '-'}</div>
                                <div class="small text-muted">${o.districtname ? `${o.districtname}, ` : ''}${o.statename || ''}</div>
                                ${o.delivery_address ? `<div class="small text-muted text-truncate" style="max-width: 200px;" title="${o.delivery_address}">${o.delivery_address}</div>` : ''}
                            </td>
                            <td>${o.deliverydate || '-'}</td>
                            <td>${o.confirmdeliverydate ? `<b class="text-primary">${o.confirmdeliverydate}</b>` : '-'}</td>
                            <td class="text-end mono fw-bold">${formatNum(o.quantity)}</td>
                            <td class="text-end mono text-muted">${formatNum(o.dispatchedqty)}</td>
                            <td class="text-end mono fw-extrabold ${balToShip > 0 ? 'text-warning' : 'text-success'}">${formatNum(balToShip)}</td>
                            <td>${itemStatusBadge}</td>
                            <td class="text-end mono">₹${parseFloat(o.rate || 0).toFixed(2)}</td>
                            <td class="text-end mono fw-semibold">₹${formatNum(o.amount)}</td>
                        </tr>
                    `;
                });
                $('#tblProductOrdersBody').html(html);
            },
            error: function(xhr, status, error) {
                $('#tblProductOrdersBody').html(`<tr><td colspan="13" class="text-danger text-center">Failed to load orders: ${error}</td></tr>`);
            }
        });

        // Fetch Polyhouse Trays Drilldown
        $.ajax({
            url: API_URL,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_plant_polyhouse_drilldown',
                product_id: prodId,
                location_id: $('#filterLocation').val()
            },
            success: function(resp) {
                if (!resp.status) {
                    $('#tblProductTraysBody').html(`<tr><td colspan="11" class="text-danger text-center">${resp.message}</td></tr>`);
                    return;
                }

                const trays = resp.data || [];
                $('#mTrayCount').text(trays.length);

                if (trays.length === 0) {
                    $('#tblProductTraysBody').html('<tr><td colspan="11" class="text-center text-muted py-3">No active trays currently found in polyhouses.</td></tr>');
                    return;
                }

                let html = '';
                trays.forEach((t, i) => {
                    const isReady = t.status === 'Readytosale';
                    const statusBadge = isReady
                        ? '<span class="badge bg-success">Ready to Sale</span>'
                        : '<span class="badge bg-primary">Polyhouse (Growing)</span>';

                    const locBadge = t.locationid === 'LOC6905e0fd5ab05'
                        ? '<span class="badge bg-info text-dark">Location 1</span>'
                        : '<span class="badge" style="background-color: #7c3aed; color: #fff;">Location 2</span>';

                    html += `
                        <tr>
                            <td class="text-center text-muted">${i + 1}</td>
                            <td>${locBadge}</td>
                            <td><b>${t.polyhousename}</b></td>
                            <td>
                                Bed: <b>${t.bed_no || '-'}</b> 
                                ${t.rack_no ? `| Rack: ${t.rack_no}` : ''}
                                ${t.row_no ? `| Row: ${t.row_no}` : ''}
                            </td>
                            <td class="mono font-monospace">
                                ${t.tray_code ? `<code>${t.tray_code}</code>` : ''}
                                ${t.barcode ? `<span class="badge bg-light text-dark border ms-1">${t.barcode}</span>` : ''}
                            </td>
                            <td>${t.sowing_date || '-'}</td>
                            <td>${statusBadge}</td>
                            <td><span class="badge ${t.health_status === 'Healthy' ? 'bg-success' : 'bg-warning text-dark'}">${t.health_status}</span></td>
                            <td class="text-end mono">${formatNum(t.qtypertray)}</td>
                            <td class="text-end mono text-danger">${formatNum(t.failurecount)}</td>
                            <td class="text-end mono fw-bold text-success fs-7">${formatNum(t.available_plants)}</td>
                        </tr>
                    `;
                });
                $('#tblProductTraysBody').html(html);
            },
            error: function(xhr, status, error) {
                $('#tblProductTraysBody').html(`<tr><td colspan="11" class="text-danger text-center">Failed to load trays: ${error}</td></tr>`);
            }
        });
    }

    // Event Handlers for Filters
    $('#filterYear').on('change', function() {
        syncDateRange();
        loadLiveStockSummary();
    });

    $('#filterDateMode').on('change', function() {
        syncDateRange();
        loadLiveStockSummary();
    });

    $('#filterDateFrom, #filterDateTo').on('change', function() {
        loadLiveStockSummary();
    });

    $('#filterDemandType, #filterStatus, #filterLocation').on('change', function() {
        loadLiveStockSummary();
    });

    let searchTimeout = null;
    $('#filterSearch').on('keyup', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            loadLiveStockSummary();
        }, 400);
    });

    $('#btnClearSearch').on('click', function() {
        $('#filterSearch').val('');
        loadLiveStockSummary();
    });

    $('#btnRefresh').on('click', function() {
        loadLiveStockSummary();
    });

    // Start by loading Years dynamically from plant table
    loadYearsList();
});
</script>

</body>
</html>
