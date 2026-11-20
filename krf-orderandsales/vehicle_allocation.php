<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('../header.php');
date_default_timezone_set("Asia/Calcutta");

$logoPath = __DIR__ . '/../img/logo/logo.png';
$krfLogoBase64 = '';
if (file_exists($logoPath)) {
    $logoData = file_get_contents($logoPath);
    $krfLogoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
}
?>
<!-- Bootstrap CSS & Select2 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<style>
    /* Consistent design matching timetable_finaldispatch.php */
    body {
        background-color: #f8fafc;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }

    /* Print Styles for PDF / Direct Print on A4 Landscape */
    @page {
        size: A4 landscape;
        margin: 8mm 8mm 8mm 8mm;
    }
    @media print {
        html, body {
            width: 100% !important;
            height: auto !important;
            background: #ffffff !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body * {
            visibility: hidden !important;
        }
        #vaPdfPrintableDocument, #vaPdfPrintableDocument * {
            visibility: visible !important;
        }
        #vaPdfPrintableDocument {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            box-shadow: none !important;
            border: none !important;
            background: white !important;
        }
        table {
            page-break-inside: auto !important;
        }
        thead {
            display: table-header-group !important;
        }
        tr {
            page-break-inside: avoid !important;
            page-break-after: auto !important;
        }
        .modal, .modal-backdrop {
            position: static !important;
            display: block !important;
            background: none !important;
        }
        .modal-dialog {
            margin: 0 !important;
            max-width: 100% !important;
        }
        .modal-content, .modal-body {
            padding: 0 !important;
            border: none !important;
            background: none !important;
            overflow: visible !important;
            max-height: none !important;
        }
        .modal-header, .modal-footer {
            display: none !important;
        }
        @page {
            size: landscape;
            margin: 6mm;
        }
    }

    /* Page container */
    .va-main-card {
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        border: 1px solid #e2e8f0;
        background: #ffffff;
        margin-bottom: 25px;
    }

    /* Table Header styling */
    #dispatchTableHead th {
        background-color: #23b185 !important;
        color: white !important;
        text-align: left;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 11.5px;
        letter-spacing: 0.5px;
        vertical-align: middle;
        border: none !important;
        position: sticky;
        top: 0;
        z-index: 5;
        padding: 8px 10px;
    }

    #vaOrdersTable {
        border-collapse: collapse;
        width: 100%;
        min-width: 1240px;
        margin-bottom: 0;
        table-layout: fixed;
    }

    #vaOrdersTable th {
        white-space: nowrap;
    }

    #vaOrdersTable td {
        vertical-align: top;
        font-size: 12.5px;
        line-height: 1.35;
        padding: 6px 10px;
        border-bottom: 1px solid #edf2f7;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    /* Active filter visual cues matching timetable */
    .filter-applied,
    .filter-applied .select2-selection,
    .select2-container.filter-applied .select2-selection,
    .select2-container.filter-applied .select2-selection--single,
    .form-select.filter-applied {
        border-color: #23b185 !important;
        background-color: #f0fdf4 !important;
        color: #166534 !important;
        box-shadow: 0 0 0 2px rgba(35, 177, 133, 0.25) !important;
    }
    .select2-container.filter-applied .select2-selection__rendered {
        color: #166534 !important;
        font-weight: 600 !important;
    }
    .input-group.filter-applied {
        box-shadow: 0 0 0 2px rgba(35, 177, 133, 0.25) !important;
        border-radius: 6px !important;
    }
    .input-group.filter-applied .form-control,
    .input-group.filter-applied .input-group-text,
    .input-group.filter-applied .btn {
        border-color: #23b185 !important;
        background-color: #f0fdf4 !important;
        color: #166534 !important;
    }

    .dispatch-table-responsive {
        max-height: 650px;
        overflow-y: auto;
        overflow-x: auto;
        border-radius: 8px;
        border: 1px solid #dee2e6;
        scrollbar-width: thin;
        scrollbar-color: #23b185 #f1f3f5;
    }
    .dispatch-table-responsive::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }
    .dispatch-table-responsive::-webkit-scrollbar-thumb {
        background-color: #23b185;
        border-radius: 4px;
    }
    .dispatch-table-responsive::-webkit-scrollbar-track {
        background: #f1f3f5;
    }

    .table-hover tbody tr:hover {
        background-color: rgba(35, 177, 133, 0.05) !important;
    }

    /* Week navigator buttons */
    #vaPrevWeekBtn {
        background-color: #f3a614 !important;
        color: black !important;
        border: none;
        padding: 8px 16px;
        font-weight: 600;
        border-radius: 6px;
        transition: background-color 0.2s;
    }
    #vaPrevWeekBtn:hover {
        background-color: #d9910d !important;
    }

    #vaNextWeekBtn {
        background-color: #0c8c5e !important;
        color: white !important;
        border: none;
        padding: 8px 16px;
        font-weight: 600;
        border-radius: 6px;
        transition: background-color 0.2s;
    }
    #vaNextWeekBtn:hover {
        background-color: #096d49 !important;
    }

    /* Summary Card Base Styles */
    .status-filter-card {
        border-radius: 12px;
        padding: 14px 18px;
        cursor: pointer;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        border: 2px solid transparent;
        box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        position: relative;
        overflow: hidden;
        user-select: none;
        height: 100%;
    }
    .status-filter-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 14px rgba(0,0,0,0.08);
    }
    .status-filter-card .card-status-title {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        margin-bottom: 2px;
        display: block;
    }
    .status-filter-card .card-status-value {
        font-size: 24px;
        font-weight: 800;
        line-height: 1.1;
    }
    .status-filter-card .card-status-sub {
        font-size: 11.5px;
        font-weight: 500;
        margin-top: 2px;
        display: block;
    }
    .status-filter-card .card-status-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    /* Card Themes */
    /* 1. Total Card */
    .status-filter-card.card-total {
        background-color: #eff6ff;
        border-color: #bfdbfe;
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
        box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.3);
    }

    /* 2. Pending Vehicle Card */
    .status-filter-card.card-pending {
        background-color: #fffbeb;
        border-color: #fde68a;
    }
    .status-filter-card.card-pending .card-status-title { color: #92400e; }
    .status-filter-card.card-pending .card-status-value { color: #b45309; }
    .status-filter-card.card-pending .card-status-sub { color: #d97706; }
    .status-filter-card.card-pending .card-status-icon {
        background-color: #fef3c7;
        color: #b45309;
    }
    .status-filter-card.card-pending.active {
        border-color: #d97706 !important;
        background-color: #fef3c7 !important;
        box-shadow: 0 0 0 2px rgba(217, 119, 6, 0.3);
    }

    /* 3. Vehicle Allocated Card */
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
        box-shadow: 0 0 0 2px rgba(5, 150, 105, 0.3);
    }

    .plate-badge {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-weight: 800;
        letter-spacing: 0.8px;
        padding: 4px 10px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
</style>

<div class="container-fluid px-3 py-3" data-projectid="<?php echo htmlspecialchars($projectid ?? '0'); ?>" data-loginid="<?php echo htmlspecialchars($loginid ?? ''); ?>">

    <!-- Main Card -->
    <div class="row">
        <div class="col-12">
            <div class="va-main-card">
                
                <!-- 1. Card Header -->
                <div class="card-header bg-white py-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                                <i class="fa fa-truck fs-5"></i>
                            </div>
                            <div>
                                <h4 class="mb-0 fw-bold text-dark d-flex align-items-center" style="font-size: 19px;">
                                    Vehicle Allocation & Logistics Dashboard
                                </h4>
                                <div class="text-muted small" style="font-size: 12px;">
                                    Pre-allocate vehicles and drivers for allocated production orders prior to invoice generation
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <a href="timetable_finaldispatch.php?k=<?php echo urlencode($_GET['k'] ?? ''); ?>" class="btn btn-outline-success fw-bold" style="height: 40px; display: inline-flex; align-items: center;">
                                <i class="fa fa-calendar-alt me-1"></i> Final Dispatch Timetable
                            </a>
                            <button type="button" class="btn btn-success fw-bold" id="btnRefreshData" style="height: 40px; display: inline-flex; align-items: center;">
                                <i class="fa fa-sync-alt me-1"></i> Refresh Orders
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body p-3">

                    <!-- 2. Filter Bar: Year, Month, Week & Navigation -->
                    <div class="d-flex flex-wrap align-items-end gap-3 mb-3 p-3 bg-light rounded-3 shadow-sm border">
                        <div style="min-width: 140px; flex: 0.8;">
                            <label class="form-label fw-bold text-secondary small text-uppercase mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Select Year</label>
                            <select id="vaYearFilter" class="form-select" style="height: 42px;"></select>
                        </div>
                        <div style="min-width: 220px; flex: 1.2;">
                            <label class="form-label fw-bold text-secondary small text-uppercase mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Select Month</label>
                            <select id="vaMonthFilter" class="form-select" style="height: 42px;"></select>
                        </div>
                        <div class="d-flex gap-2 flex-wrap ms-md-auto align-items-center mt-2 mt-md-0">
                            <button type="button" class="btn btn-success fw-bold" id="btnExportExcelWeekBar" style="height: 42px; background-color: #107c41; border-color: #107c41; display: inline-flex; align-items: center;" title="Export filtered orders to Excel spreadsheet">
                                <i class="fa fa-file-excel me-1 text-white"></i> Excel
                            </button>
                            <button type="button" class="btn btn-danger fw-bold" id="btnExportPdfWeekBar" style="height: 42px; background-color: #dc2626; border-color: #dc2626; display: inline-flex; align-items: center;" title="Open printable PDF report preview with print & download">
                                <i class="fa fa-file-pdf me-1 text-white"></i> Print / PDF
                            </button>
                        </div>
                    </div>

                    <!-- 3. KPI Filter Summary Cards -->
                    <div class="row g-3 mb-3">
                        <!-- Total Allocated Orders -->
                        <div class="col-12 col-md-4">
                            <div class="status-filter-card card-total active" data-filter="all" id="cardFilterAll">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="card-status-title">Total Allocated Orders</span>
                                        <h3 class="card-status-value mb-0" id="kpiTotalOrders">0</h3>
                                        <span class="card-status-sub" id="kpiTotalPlants">0 Plants Total</span>
                                    </div>
                                    <div class="card-status-icon">
                                        <i class="fa fa-shopping-cart"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pending Vehicle Allocation -->
                        <div class="col-12 col-md-4">
                            <div class="status-filter-card card-pending" data-filter="pending_vehicle" id="cardFilterPending">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="card-status-title">Pending Vehicle Allocation</span>
                                        <h3 class="card-status-value mb-0" id="kpiPendingOrders">0</h3>
                                        <span class="card-status-sub">Awaiting Vehicle Assignment</span>
                                    </div>
                                    <div class="card-status-icon">
                                        <i class="fa fa-clock"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Vehicle Allocated -->
                        <div class="col-12 col-md-4">
                            <div class="status-filter-card card-allocated" data-filter="allocated_vehicle" id="cardFilterAllocated">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="card-status-title">Vehicle Allocated</span>
                                        <h3 class="card-status-value mb-0" id="kpiAllocatedOrders">0</h3>
                                        <span class="card-status-sub">Ready for Invoice / Dispatch</span>
                                    </div>
                                    <div class="card-status-icon">
                                        <i class="fa fa-truck"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 4. In-Table Secondary Dynamic Filters & Search Card -->
                    <div class="card mb-3 border bg-white shadow-sm" style="border-radius: 10px;">
                        <div class="card-body p-3">
                            <div class="row g-2 align-items-end">
                                <!-- 1. Delivery Date -->
                                <div class="col-12 col-sm-6 col-md-3">
                                    <label class="form-label fw-bold text-secondary small text-uppercase mb-1" style="font-size: 11px;">
                                        <i class="fa fa-calendar-day text-success me-1"></i> Delivery Date
                                    </label>
                                    <select id="tableDeliveryDateFilter" class="form-select select2-filter" style="width: 100%;">
                                        <option value="all">All Dates</option>
                                    </select>
                                </div>

                                <!-- 2. Client / Sales Order -->
                                <div class="col-12 col-sm-6 col-md-3">
                                    <label class="form-label fw-bold text-secondary small text-uppercase mb-1" style="font-size: 11px;">
                                        <i class="fa fa-user text-primary me-1"></i> Client / Sales Order
                                    </label>
                                    <select id="tableClientFilter" class="form-select select2-filter" style="width: 100%;">
                                        <option value="all">All Clients & Orders</option>
                                    </select>
                                </div>

                                <!-- 3. Product Name -->
                                <div class="col-12 col-sm-6 col-md-3">
                                    <label class="form-label fw-bold text-secondary small text-uppercase mb-1" style="font-size: 11px;">
                                        <i class="fa fa-seedling text-teal me-1" style="color: #0d9488;"></i> Product Name
                                    </label>
                                    <select id="tableProductFilter" class="form-select select2-filter" style="width: 100%;">
                                        <option value="all">All Products</option>
                                    </select>
                                </div>

                                <!-- 4. Vehicle Status -->
                                <div class="col-12 col-sm-6 col-md-3">
                                    <label class="form-label fw-bold text-secondary small text-uppercase mb-1" style="font-size: 11px;">
                                        <i class="fa fa-truck text-warning me-1"></i> Vehicle Status
                                    </label>
                                    <select id="tableVehicleStatusFilter" class="form-select select2-filter" style="width: 100%;">
                                        <option value="all">All Vehicle Statuses</option>
                                        <option value="pending_vehicle">Pending Vehicle Assignment</option>
                                        <option value="allocated_vehicle">Vehicle Allocated</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Bottom row: Row counter, quick search input, Export buttons and Reset Filters button -->
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3 pt-2 border-top">
                                <div class="text-muted small">
                                    <i class="fa fa-list-check text-success me-1"></i> Showing <span class="badge bg-light text-dark border fw-bold px-2 py-1" id="showingCount">0</span> of <span class="fw-bold" id="totalCount">0</span> orders
                                </div>
                                <div class="flex-grow-1" style="min-width: 280px; max-width: 520px;">
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0 px-3"><i class="fa fa-search text-muted"></i></span>
                                        <input type="text" id="vaSearchInput" class="form-control border-start-0 border-end-0" style="height: 38px; font-size: 13.5px;" placeholder="Search by Client, SO No, Vehicle Plate, District...">
                                        <button class="btn btn-white bg-white border-start-0 border text-secondary" type="button" id="btnVaClearSearch" title="Clear Search" style="display: none; height: 38px;">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <button type="button" class="btn btn-outline-danger" id="btnResetFilters" style="height: 38px; padding: 0 14px;" title="Reset all applied filters">
                                        <i class="fa fa-rotate-left me-1"></i> Reset Filters
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 5. Orders Table -->
                    <div class="table-responsive dispatch-table-responsive shadow-sm">
                        <table class="table table-hover mb-0" id="vaOrdersTable">
                            <thead id="dispatchTableHead">
                                <tr>
                                    <th style="width: 45px; text-align: center;">S.NO</th>
                                    <th style="width: 115px;">DELIVERY DATE</th>
                                    <th style="width: 250px;">SALES ORDER & CLIENT</th>
                                    <th style="width: 215px;">ORDER & PLANTS</th>
                                    <th style="width: 260px;">DELIVERY & FREIGHT CHARGES</th>
                                    <th style="width: 255px;">VEHICLE & DRIVER ASSIGNMENT</th>
                                    <th style="width: 105px; text-align: center;">ACTION</th>
                                </tr>
                            </thead>
                            <tbody id="vaOrdersTbody">
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="fa fa-spinner fa-spin fa-2x text-success mb-2"></i>
                                        <div class="fw-bold">Loading allocated orders for vehicle assignment...</div>
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

<!-- Modal: Allocate / Edit Vehicle -->
<div class="modal fade" id="vehicleAllocModal" tabindex="-1" role="dialog" aria-labelledby="vehicleAllocModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header py-3 d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%); color: white;">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-white text-primary d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="fa fa-truck fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0" id="vehicleAllocModalLabel">
                            Allocate Vehicle to Order
                        </h5>
                        <div class="text-white-50 small" style="font-size: 11.5px;">Pre-assign vehicle & driver for sales order delivery</div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <form id="vehicleAllocForm">
                    <input type="hidden" id="vam_projectid" name="projectid">
                    <input type="hidden" id="vam_sono" name="sono">
                    <input type="hidden" id="vam_psono" name="psono">
                    <input type="hidden" id="vam_clientid" name="clientid">
                    <input type="hidden" id="vam_total_plants" name="total_allocated_plants">

                    <!-- Order Snapshot Box -->
                    <div class="card border-0 shadow-sm rounded-3 mb-3 bg-white">
                        <div class="card-body p-3">
                            <div class="row g-2 align-items-center">
                                <div class="col-md-6 border-end">
                                    <div class="text-muted small fw-bold" style="font-size: 11px;">CLIENT & DESTINATION</div>
                                    <div class="fw-bold text-dark fs-6" id="vam_display_client">-</div>
                                    <div class="text-secondary small" id="vam_display_destination">-</div>
                                </div>
                                <div class="col-md-6 ps-md-3">
                                    <div class="text-muted small fw-bold" style="font-size: 11px;">SALES ORDER & LOAD</div>
                                    <div class="fw-bold text-primary font-monospace" id="vam_display_sono">-</div>
                                    <div class="fw-bold text-success fs-6" id="vam_display_qty">-</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Vehicle Assignment Form -->
                    <div class="card border-0 shadow-sm rounded-3 mb-3 bg-white">
                        <div class="card-header bg-white py-2 px-3 border-bottom fw-bold text-dark">
                            <i class="fa fa-truck text-primary me-2"></i> Vehicle & Driver Details
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-3">
                                <!-- Select From Master -->
                                <div class="col-md-6">
                                    <label class="form-label text-dark small fw-bold mb-1" for="vam_vehicle_select">
                                        Select Vehicle (From Registered Fleet)
                                    </label>
                                    <select class="form-select form-select-sm" id="vam_vehicle_select" name="vehicle_id">
                                        <option value="">-- Choose Vehicle or Custom --</option>
                                    </select>
                                </div>

                                <!-- Registration Plate -->
                                <div class="col-md-6">
                                    <label class="form-label text-dark small fw-bold mb-1" for="vam_vehicleno">
                                        Vehicle Registration No <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control form-control-sm fw-bold text-uppercase" id="vam_vehicleno" name="vehicleno" placeholder="e.g. CG-0256588" required>
                                </div>

                                <!-- Vehicle Capacity Live Checker -->
                                <div class="col-12" id="vamCapacityNoticeBox" style="display: none;">
                                    <div class="alert alert-info py-2 px-3 mb-0 small d-flex align-items-center gap-2" id="vamCapacityNotice">
                                        <i class="fa fa-info-circle"></i>
                                        <span id="vamCapacityNoticeText">-</span>
                                    </div>
                                </div>

                                <!-- Vehicle Name / Type -->
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold mb-1" for="vam_vehiclename">
                                        Vehicle Type / Model
                                    </label>
                                    <input type="text" class="form-control form-control-sm" id="vam_vehiclename" name="vehiclename" placeholder="e.g. Tata Ace, Pickup, 1109">
                                </div>

                                <!-- Owner / Transporter -->
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold mb-1" for="vam_vehicleownername">
                                        Owner / Transporter Name
                                    </label>
                                    <input type="text" class="form-control form-control-sm" id="vam_vehicleownername" name="vehicleownername" placeholder="Transporter name">
                                </div>

                                <!-- Driver Name -->
                                <div class="col-md-6">
                                    <label class="form-label text-dark small fw-bold mb-1" for="vam_drivername">
                                        Driver Full Name
                                    </label>
                                    <input type="text" class="form-control form-control-sm" id="vam_drivername" name="drivername" placeholder="Driver name">
                                </div>

                                <!-- Driver Mobile -->
                                <div class="col-md-6">
                                    <label class="form-label text-dark small fw-bold mb-1" for="vam_drivermobileno">
                                        Driver Mobile No <span class="text-muted fw-normal">(10 digits)</span>
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light text-muted"><i class="fa fa-phone"></i></span>
                                        <input type="tel" class="form-control form-control-sm" id="vam_drivermobileno" name="drivermobileno" maxlength="10" placeholder="e.g. 9876543210">
                                    </div>
                                    <div class="text-danger" id="vamMobileFeedback" style="display:none; font-size: 11px; margin-top: 3px;">
                                        <i class="fa fa-exclamation-circle me-1"></i>Must be a valid 10-digit mobile number starting with 6, 7, 8, or 9.
                                    </div>
                                </div>

                                <!-- Scheduled Dispatch Date -->
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold mb-1" for="vam_scheduled_dispatch_date">
                                        Scheduled Dispatch Date
                                    </label>
                                    <input type="date" class="form-control form-control-sm" id="vam_scheduled_dispatch_date" name="scheduled_dispatch_date">
                                </div>

                                <!-- Remarks -->
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold mb-1" for="vam_remarks">
                                        Transport & Route Remarks
                                    </label>
                                    <input type="text" class="form-control form-control-sm" id="vam_remarks" name="remarks" placeholder="Optional notes for dispatch team">
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-white py-2 px-4 border-top d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success fw-bold px-4" id="vamSaveBtn">
                    <i class="fa fa-check-circle me-1"></i> Confirm Vehicle Allocation
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: PDF & Print Preview -->
<div class="modal fade" id="vaPdfPreviewModal" tabindex="-1" role="dialog" aria-labelledby="vaPdfPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document" style="max-width: 95vw;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header py-2.5 px-4 d-flex justify-content-between align-items-center bg-dark text-white">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa fa-file-pdf text-danger fs-5"></i>
                    <div>
                        <h6 class="modal-title fw-bold mb-0 text-white" id="vaPdfPreviewModalLabel">Vehicle Allocation & Logistics Report Preview</h6>
                        <span class="text-white-50 small" id="vaPdfPreviewSubtitle" style="font-size: 11.5px;">A4 Landscape Print / PDF Document</span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-primary px-3 fw-bold" id="btnPdfModalPrint">
                        <i class="fa fa-print me-1"></i> Print
                    </button>
                    <button type="button" class="btn btn-sm btn-danger px-3 fw-bold" id="btnPdfModalDownload">
                        <i class="fa fa-download me-1"></i> Download PDF
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-light px-2.5" data-bs-dismiss="modal" data-dismiss="modal">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="modal-body p-2 p-md-3" style="background-color: #525659; overflow-y: auto; max-height: 80vh;">
                <!-- Printable Paper Container (Full-width A4 Landscape) -->
                <div id="vaPdfPrintableDocument" class="bg-white shadow-lg mx-auto p-3 p-md-4" style="width: 100%; max-width: 1080px; min-height: 300px; color: #1e293b; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; border-radius: 4px; box-sizing: border-box;">
                    <!-- Dynamically populated via JS -->
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-4 border-top d-flex justify-content-between align-items-center">
                <span class="text-muted small" id="vaPdfFooterCount">Total orders in report: 0</span>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary btn-sm px-3 fw-bold" id="btnPdfModalPrintBottom">
                        <i class="fa fa-print me-1"></i> Print Report
                    </button>
                    <button type="button" class="btn btn-danger btn-sm px-3 fw-bold" id="btnPdfModalDownloadBottom">
                        <i class="fa fa-download me-1"></i> Download PDF
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('../footer.php'); ?>

<script>
$(document).ready(function() {
    let globalWeekMap = {};
    let globalSortedWeeks = [];
    let currentRawOrders = [];
    let currentFilteredOrders = [];
    let currentFilter = "all";
    let cachedVehicles = [];

    let projectid = <?php echo json_encode($projectid ?? 0); ?>;
    let loginid = <?php echo json_encode($loginid ?? ''); ?>;
    let krfCompanyLogo = <?php echo json_encode($krfLogoBase64 ?? ''); ?>;

    // Universal Modal Dismiss Handler (works for Bootstrap 4, Bootstrap 5, and custom modals)
    $(document).on("click", '[data-bs-dismiss="modal"], [data-dismiss="modal"], .close-modal-btn', function(e) {
        e.preventDefault();
        hideAllocModal();
        hidePdfPreviewModal();
    });

    function showAllocModal() {
        try {
            let el = document.getElementById('vehicleAllocModal');
            if (window.bootstrap && bootstrap.Modal) {
                let bsModal = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
                bsModal.show();
                return;
            }
        } catch(e){}
        $("#vehicleAllocModal").modal("show");
    }

    function hideAllocModal() {
        try {
            let el = document.getElementById('vehicleAllocModal');
            if (window.bootstrap && bootstrap.Modal) {
                let bsModal = bootstrap.Modal.getInstance(el);
                if (bsModal) { bsModal.hide(); }
            }
        } catch(e){}
        $("#vehicleAllocModal").modal("hide");
        setTimeout(function() {
            if (!$(".modal.show").length) {
                $(".modal-backdrop").remove();
                $("body").removeClass("modal-open").css("padding-right", "");
            }
        }, 200);
    }

    function showPdfPreviewModal() {
        try {
            let el = document.getElementById('vaPdfPreviewModal');
            if (window.bootstrap && bootstrap.Modal) {
                let bsModal = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
                bsModal.show();
                return;
            }
        } catch(e){}
        $("#vaPdfPreviewModal").modal("show");
    }

    function hidePdfPreviewModal() {
        try {
            let el = document.getElementById('vaPdfPreviewModal');
            if (window.bootstrap && bootstrap.Modal) {
                let bsModal = bootstrap.Modal.getInstance(el);
                if (bsModal) { bsModal.hide(); }
            }
        } catch(e){}
        $("#vaPdfPreviewModal").modal("hide");
        setTimeout(function() {
            if (!$(".modal.show").length) {
                $(".modal-backdrop").remove();
                $("body").removeClass("modal-open").css("padding-right", "");
            }
        }, 200);
    }

    // 1. Initialize Dates & Weeks
    loadActiveDates();
    loadActiveVehiclesMaster();

    function loadActiveDates() {
        $.ajax({
            url: "modal/finaldispatch_modal.php?action=GetActiveFinalDispatchDates",
            type: "POST",
            data: { projectid: projectid },
            dataType: "json",
            success: function(dates) {
                if (!Array.isArray(dates)) dates = [];
                let weekMap = {};
                dates.forEach(item => {
                    if (!item.dispatch_date) return;
                    let d = new Date(item.dispatch_date);
                    if (isNaN(d.getTime())) return;
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
                let tDay = today.getDay();
                let tDiff = (tDay === 0) ? -6 : (1 - tDay);
                let tMon = new Date(today);
                tMon.setDate(today.getDate() + tDiff);
                let tSun = new Date(tMon);
                tSun.setDate(tMon.getDate() + 6);
                let tWS = tMon.toISOString().split("T")[0];
                let tWE = tSun.toISOString().split("T")[0];

                if (!weekMap[tWS]) {
                    weekMap[tWS] = tWE;
                }

                globalWeekMap = weekMap;
                globalSortedWeeks = Object.keys(weekMap).sort();

                // Populate Years & Months
                let years = new Set();
                globalSortedWeeks.forEach(ws => years.add(ws.substring(0, 4)));
                years.add(today.getFullYear().toString());

                let yearSel = $("#vaYearFilter");
                yearSel.empty();
                Array.from(years).sort().reverse().forEach(y => {
                    yearSel.append(`<option value="${y}">${y}</option>`);
                });
                yearSel.val(today.getFullYear().toString());

                populateMonths(today.getFullYear().toString());
            }
        });
    }

    function populateMonths(selectedYear) {
        let monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        let monthSel = $("#vaMonthFilter");
        monthSel.empty();
        monthSel.append(`<option value="all">All Months (${selectedYear})</option>`);

        let curMonth = ("0" + (new Date().getMonth() + 1)).slice(-2);
        for (let m = 1; m <= 12; m++) {
            let mStr = ("0" + m).slice(-2);
            let val = `${selectedYear}-${mStr}`;
            monthSel.append(`<option value="${val}">${monthNames[m - 1]} ${selectedYear}</option>`);
        }
        let initialMonth = `${selectedYear}-${curMonth}`;
        monthSel.val(initialMonth);

        loadOrdersForSelectedMonth();
    }

    function formatDateDisplay(dStr) {
        if (!dStr) return '';
        let parts = dStr.split('-');
        if (parts.length === 3) {
            let months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
            return `${parts[2]}-${months[parseInt(parts[1]) - 1]}-${parts[0]}`;
        }
        return dStr;
    }

    $("#vaYearFilter").on("change", function() {
        populateMonths($(this).val());
    });

    $("#vaMonthFilter").on("change", function() {
        loadOrdersForSelectedMonth();
    });

    $("#btnRefreshData").on("click", function() {
        loadOrdersForSelectedMonth();
    });

    // 2. Fetch Orders from Backend by Month
    function loadOrdersForSelectedMonth() {
        let selectedYear = $("#vaYearFilter").val() || new Date().getFullYear().toString();
        let monthVal = $("#vaMonthFilter").val();
        let dateFrom = '';
        let dateTo = '';

        if (!monthVal || monthVal === 'all') {
            dateFrom = `${selectedYear}-01-01`;
            dateTo = `${selectedYear}-12-31`;
        } else {
            let parts = monthVal.split('-');
            let year = parseInt(parts[0], 10);
            let month = parseInt(parts[1], 10);
            let lastDay = new Date(year, month, 0).getDate();
            let lastDayStr = ("0" + lastDay).slice(-2);
            dateFrom = `${monthVal}-01`;
            dateTo = `${monthVal}-${lastDayStr}`;
        }

        $("#vaOrdersTbody").html(`
            <tr>
                <td colspan="7" class="text-center py-4 text-muted">
                    <i class="fa fa-spinner fa-spin fa-2x text-success mb-2"></i>
                    <div class="fw-bold">Fetching allocated sales orders...</div>
                </td>
            </tr>
        `);

        $.ajax({
            url: "modal/finaldispatch_modal.php?action=GetVehicleAllocationOrders",
            type: "POST",
            data: {
                projectid: projectid,
                date_from: dateFrom,
                date_to: dateTo,
                filter_status: 'all'
            },
            dataType: "json",
            success: function(res) {
                if (res.status === "success") {
                    currentRawOrders = res.data || [];
                    renderKPICards(res.counts || {});
                    applyFiltersAndRender(true);
                } else {
                    $("#vaOrdersTbody").html(`<tr><td colspan="7" class="text-center text-danger py-4">${res.message || 'Failed to load orders'}</td></tr>`);
                }
            },
            error: function() {
                $("#vaOrdersTbody").html(`<tr><td colspan="7" class="text-center text-danger py-4">Error fetching orders from server.</td></tr>`);
            }
        });
    }

    function renderKPICards(counts) {
        let total = counts.total || currentRawOrders.length;
        let pending = counts.pending || 0;
        let allocated = counts.allocated || 0;

        let totalPlants = currentRawOrders.reduce((sum, ord) => sum + parseInt(ord.total_billable_qty || 0), 0);

        $("#kpiTotalOrders").text(total);
        $("#kpiTotalPlants").text(`${totalPlants.toLocaleString()} Plants Total`);
        $("#kpiPendingOrders").text(pending);
        $("#kpiAllocatedOrders").text(allocated);
    }

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatProductNameClean(rawName) {
        if (!rawName) return '';
        return rawName.replace(/^(NonGrafted|Non-Grafted)\s*[-:]?\s*/i, '');
    }

    function isProductGrafted(rawName) {
        if (!rawName) return false;
        return (/grafted/i.test(rawName) && !/nongrafted|non-grafted/i.test(rawName));
    }

    function getDayName(dateStr) {
        if (!dateStr) return '';
        let d = new Date(dateStr);
        if (isNaN(d.getTime())) return '';
        let days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        return days[d.getDay()];
    }

    function formatFreightAndDeliveryColumn(row) {
        if (!row) return '-';
        let rawType = (row.dl_pickuptype || '').trim();
        let normType = rawType.toLowerCase().replace(/[-_ ]/g, '');

        let freightAmt = parseFloat(row.dl_freightamt || 0);
        let deliveryCharges = parseFloat(row.dl_deliverycharges || 0);
        let totalAmt = parseFloat(row.dl_totalamount || 0);
        let totalDist = parseFloat(row.dl_totaldistance || 0);
        let freeDist = parseFloat(row.dl_freedistance || 0);

        // 1. Pickup / Delivery Type Badge
        let typeBadge = '';
        if (normType === 'pickup' || normType === 'pickbyclient' || normType === 'selfpickup') {
            typeBadge = `<span class="badge bg-secondary text-white px-1.5 py-0.5" style="font-size: 10px;"><i class="fa fa-user-check me-1"></i>Self Pickup</span>`;
        } else if (normType === 'boxpacking' || normType === 'box') {
            typeBadge = `<span class="badge bg-warning text-dark px-1.5 py-0.5" style="font-size: 10px;"><i class="fa fa-box me-1"></i>Box Packing</span>`;
        } else if (normType === 'delivered' || normType === 'companytransport' || normType === 'deliver') {
            typeBadge = `<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-1.5 py-0.5" style="font-size: 10px;"><i class="fa fa-truck me-1"></i>Delivered by Farm</span>`;
        } else {
            typeBadge = `<span class="badge bg-light text-secondary border px-1.5 py-0.5" style="font-size: 10px;"><i class="fa fa-map-marker-alt me-1"></i>Delivery</span>`;
        }

        // 2. Freight & Delivery Charges badges
        let chargesList = [];
        if (freightAmt > 0) {
            chargesList.push(`<span class="badge bg-success-subtle text-success border border-success-subtle fw-bold" style="font-size: 11px;"><i class="fa fa-rupee-sign me-0.5"></i>Freight: ₹${freightAmt.toLocaleString()}</span>`);
        }
        if (deliveryCharges > 0) {
            chargesList.push(`<span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold" style="font-size: 11px;"><i class="fa fa-rupee-sign me-0.5"></i>Delivery: ₹${deliveryCharges.toLocaleString()}</span>`);
        }
        if (totalAmt > 0 && freightAmt <= 0 && deliveryCharges <= 0) {
            chargesList.push(`<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-bold" style="font-size: 11px;"><i class="fa fa-rupee-sign me-0.5"></i>Packing: ₹${totalAmt.toLocaleString()}</span>`);
        }

        let chargesHtml = chargesList.length > 0 ? chargesList.join(' ') : `<span class="text-muted small" style="font-size: 10.5px;">₹0 (Free / Included)</span>`;

        // 3. Distance
        let distStr = '';
        if (totalDist > 0) {
            distStr = `<span class="text-secondary small" style="font-size: 10.5px;"><b>Dist:</b> ${totalDist} km${freeDist > 0 ? ` (${freeDist} free)` : ''}</span>`;
        }

        // 4. Delivery address / Destination landmark (compact, truncated)
        let addr = row.dl_deliveryaddress || row.delivery_address || '';
        if (!addr && (row.dl_dest_city || row.dl_dest_district)) {
            addr = [row.dl_dest_city, row.dl_dest_district, row.dl_dest_state].filter(Boolean).join(', ');
        }
        let addrHtml = addr ? `<div class="text-muted text-truncate small" style="font-size: 10.5px; max-width: 250px;" title="${escapeHtml(addr)}"><i class="fa fa-map-pin text-danger me-0.5"></i>${escapeHtml(addr)}</div>` : '';

        // Point or remark
        if (row.dl_pickuppoint) {
            addrHtml += `<div class="text-muted text-truncate small" style="font-size: 10px; max-width: 250px;" title="${escapeHtml(row.dl_pickuppoint)}"><b>Point:</b> ${escapeHtml(row.dl_pickuppoint)}</div>`;
        } else if (row.dl_remark) {
            addrHtml += `<div class="text-muted fst-italic text-truncate small" style="font-size: 10px; max-width: 250px;" title="${escapeHtml(row.dl_remark)}"><i class="fa fa-comment text-info me-0.5"></i>${escapeHtml(row.dl_remark)}</div>`;
        }

        return `
            <div class="d-flex align-items-center justify-content-between gap-1 mb-0.5 flex-wrap">
                ${typeBadge}
                ${distStr}
            </div>
            <div class="mb-0.5">
                ${chargesHtml}
            </div>
            ${addrHtml}
        `;
    }

    // Active visual indicators on filters
    function updateFilterActiveVisuals() {
        const filters = [
            '#tableDeliveryDateFilter',
            '#tableClientFilter',
            '#tableProductFilter',
            '#tableVehicleStatusFilter'
        ];

        filters.forEach(selId => {
            let $el = $(selId);
            let val = $el.val();
            let isActive = val && val !== 'all' && val !== '';
            let $select2Container = $el.next('.select2-container');
            let $selection = $select2Container.find('.select2-selection');

            if (isActive) {
                $el.addClass('filter-applied');
                $select2Container.addClass('filter-applied');
                $selection.addClass('filter-applied');
            } else {
                $el.removeClass('filter-applied');
                $select2Container.removeClass('filter-applied');
                $selection.removeClass('filter-applied');
            }
        });

        // Search Input
        let searchVal = ($('#vaSearchInput').val() || '').trim();
        let $searchGroup = $('#vaSearchInput').closest('.input-group');
        if (searchVal !== '') {
            $searchGroup.addClass('filter-applied');
        } else {
            $searchGroup.removeClass('filter-applied');
        }
    }

    // Initialize Select2 on dropdowns
    function initSelect2Filters() {
        if ($.fn.select2) {
            $(".select2-filter").each(function() {
                if ($(this).hasClass("select2-hidden-accessible")) {
                    $(this).select2("destroy");
                }
                $(this).select2({
                    theme: "bootstrap-5",
                    width: "100%",
                    placeholder: $(this).find("option:first").text()
                });
            });
        }
        updateFilterActiveVisuals();
    }

    let isUpdatingFilters = false;

    // Filter computation
    function getFilteredDataset(excludeType = null) {
        let list = currentRawOrders;

        // 1. Delivery Date filter
        if (excludeType !== 'delivery_date') {
            let selDate = $("#tableDeliveryDateFilter").val();
            if (selDate && selDate !== "all") {
                list = list.filter(ord => (ord.deliverydate === selDate || ord.formatted_delivery_date === selDate));
            }
        }

        // 2. Client / Sales Order filter
        if (excludeType !== 'client') {
            let selClient = $("#tableClientFilter").val();
            if (selClient && selClient !== "all") {
                let target = selClient.trim().toLowerCase();
                list = list.filter(ord => {
                    let cName = (ord.clientname || '').trim().toLowerCase();
                    let sono = (ord.sono || '').trim().toLowerCase();
                    let psono = (ord.psono || '').trim().toLowerCase();
                    return (cName === target || cName.includes(target) || sono.includes(target) || psono.includes(target));
                });
            }
        }

        // 3. Product filter
        if (excludeType !== 'product') {
            let selProduct = $("#tableProductFilter").val();
            if (selProduct && selProduct !== "all") {
                let target = selProduct.trim().toLowerCase();
                list = list.filter(ord => {
                    let cleanP = formatProductNameClean(ord.clean_product_name || ord.product_names || '').trim().toLowerCase();
                    let rawP = (ord.product_names || '').trim().toLowerCase();
                    return (cleanP === target || cleanP.includes(target) || rawP.includes(target));
                });
            }
        }

        // 4. Vehicle Status filter
        if (excludeType !== 'status') {
            let statusVal = $("#tableVehicleStatusFilter").val();
            let effectiveStatus = (currentFilter !== "all") ? currentFilter : statusVal;
            if (effectiveStatus === 'pending_vehicle') {
                list = list.filter(ord => !ord.is_vehicle_allocated);
            } else if (effectiveStatus === 'allocated_vehicle') {
                list = list.filter(ord => ord.is_vehicle_allocated);
            }
        }

        // 5. Search Keyword
        if (excludeType !== 'search') {
            let search = ($("#vaSearchInput").val() || '').trim().toLowerCase();
            if (search) {
                list = list.filter(ord => {
                    let cleanProd = formatProductNameClean(ord.clean_product_name || ord.product_names || '');
                    let dlText = `${ord.dl_pickuptype || ''} ${ord.dl_deliveryaddress || ''} ${ord.dl_dest_state || ''} ${ord.dl_dest_district || ''} ${ord.dl_dest_city || ''} ${ord.dl_pickuppoint || ''} ${ord.dl_remark || ''}`;
                    let haystack = [
                        ord.sono,
                        ord.psono,
                        ord.clientname,
                        ord.mobileno,
                        ord.districtname,
                        ord.statename,
                        ord.clean_product_name,
                        ord.product_names,
                        cleanProd,
                        ord.stock_order_nos,
                        ord.effective_vehicleno,
                        ord.effective_vehiclename,
                        ord.effective_drivername,
                        ord.effective_drivermobile,
                        ord.allocated_by_name,
                        ord.vehicle_remarks,
                        ord.formatted_delivery_date,
                        dlText
                    ].filter(Boolean).join(' ').toLowerCase();
                    return haystack.includes(search);
                });
            }
        }

        return list;
    }

    function populateDynamicFilters() {
        isUpdatingFilters = true;

        let curDate = $("#tableDeliveryDateFilter").val() || "all";
        let curClient = $("#tableClientFilter").val() || "all";
        let curProduct = $("#tableProductFilter").val() || "all";
        let curStatus = $("#tableVehicleStatusFilter").val() || "all";

        // 1. Delivery Dates
        let dateData = getFilteredDataset('delivery_date');
        let dateMap = {};
        dateData.forEach(ord => {
            let d = ord.deliverydate;
            let fd = ord.formatted_delivery_date || formatDateDisplay(d);
            if (d && fd) {
                dateMap[d] = { formatted: fd, count: (dateMap[d] ? dateMap[d].count + 1 : 1) };
            }
        });
        let sortedDates = Object.keys(dateMap).sort();
        let dateHtml = `<option value="all">All Dates (${sortedDates.length})</option>`;
        sortedDates.forEach(d => {
            let isSel = (curDate === d) ? 'selected' : '';
            dateHtml += `<option value="${d}" ${isSel}>${dateMap[d].formatted} (${dateMap[d].count})</option>`;
        });
        $("#tableDeliveryDateFilter").html(dateHtml);
        if (!dateMap[curDate] && curDate !== "all") {
            $("#tableDeliveryDateFilter").val("all");
        }

        // 2. Clients & Orders
        let clientData = getFilteredDataset('client');
        let clientMap = {};
        clientData.forEach(ord => {
            let cName = (ord.clientname || '').trim();
            let sono = (ord.sono || '').trim();
            if (cName) {
                if (!clientMap[cName]) {
                    clientMap[cName] = { name: cName, sonos: new Set(), count: 0 };
                }
                clientMap[cName].count++;
                if (sono) clientMap[cName].sonos.add(sono);
            }
        });
        let sortedClients = Object.keys(clientMap).sort();
        let clientHtml = `<option value="all">All Clients & Orders (${sortedClients.length})</option>`;
        sortedClients.forEach(c => {
            let obj = clientMap[c];
            let sonoList = Array.from(obj.sonos).join(', ');
            let label = sonoList ? `${obj.name} (SO: ${sonoList})` : obj.name;
            let isSel = (curClient === c) ? 'selected' : '';
            clientHtml += `<option value="${escapeHtml(c)}" ${isSel}>${escapeHtml(label)}</option>`;
        });
        $("#tableClientFilter").html(clientHtml);
        if (!clientMap[curClient] && curClient !== "all") {
            $("#tableClientFilter").val("all");
        }

        // 3. Products
        let prodData = getFilteredDataset('product');
        let prodMap = {};
        prodData.forEach(ord => {
            let pName = formatProductNameClean(ord.clean_product_name || ord.product_names || '').trim();
            if (pName) {
                prodMap[pName] = (prodMap[pName] || 0) + 1;
            }
        });
        let sortedProds = Object.keys(prodMap).sort();
        let prodHtml = `<option value="all">All Products (${sortedProds.length})</option>`;
        sortedProds.forEach(p => {
            let isSel = (curProduct === p) ? 'selected' : '';
            prodHtml += `<option value="${escapeHtml(p)}" ${isSel}>${escapeHtml(p)} (${prodMap[p]})</option>`;
        });
        $("#tableProductFilter").html(prodHtml);
        if (!prodMap[curProduct] && curProduct !== "all") {
            $("#tableProductFilter").val("all");
        }

        // 4. Vehicle Status counts
        let statusData = getFilteredDataset('status');
        let totalStat = statusData.length;
        let pendingStat = statusData.filter(o => !o.is_vehicle_allocated).length;
        let allocStat = statusData.filter(o => o.is_vehicle_allocated).length;

        let effStatusVal = (currentFilter !== "all") ? currentFilter : curStatus;
        let statusHtml = `
            <option value="all" ${effStatusVal === 'all' ? 'selected' : ''}>All Vehicle Statuses (${totalStat})</option>
            <option value="pending_vehicle" ${effStatusVal === 'pending_vehicle' ? 'selected' : ''}>Pending Vehicle Assignment (${pendingStat})</option>
            <option value="allocated_vehicle" ${effStatusVal === 'allocated_vehicle' ? 'selected' : ''}>Vehicle Allocated (${allocStat})</option>
        `;
        $("#tableVehicleStatusFilter").html(statusHtml);

        initSelect2Filters();
        isUpdatingFilters = false;
    }

    function applyFiltersAndRender(updateDropdowns = true) {
        let filtered = getFilteredDataset();

        if (updateDropdowns) {
            populateDynamicFilters();
        }

        updateFilterActiveVisuals();

        // Toggle clear search button [x]
        let searchVal = ($("#vaSearchInput").val() || '').trim();
        if (searchVal !== '') {
            $("#btnVaClearSearch").show();
        } else {
            $("#btnVaClearSearch").hide();
        }

        $("#showingCount").text(filtered.length);
        $("#totalCount").text(currentRawOrders.length);

        renderOrdersTableRows(filtered);
    }

    // Dynamic Filter Change Handlers
    $("#tableDeliveryDateFilter, #tableClientFilter, #tableProductFilter").on("change", function() {
        if (isUpdatingFilters) return;
        applyFiltersAndRender(true);
    });

    // Vehicle Status dropdown change
    $("#tableVehicleStatusFilter").on("change", function() {
        if (isUpdatingFilters) return;
        let val = $(this).val();
        currentFilter = val;
        $(".status-filter-card").removeClass("active");
        if (val === 'pending_vehicle') {
            $("#cardFilterPending").addClass("active");
        } else if (val === 'allocated_vehicle') {
            $("#cardFilterAllocated").addClass("active");
        } else {
            $("#cardFilterAll").addClass("active");
        }
        applyFiltersAndRender(true);
    });

    // KPI Card Click Filter
    $(".status-filter-card").on("click", function() {
        $(".status-filter-card").removeClass("active");
        $(this).addClass("active");
        currentFilter = $(this).data("filter") || "all";

        isUpdatingFilters = true;
        $("#tableVehicleStatusFilter").val(currentFilter);
        if ($.fn.select2 && $("#tableVehicleStatusFilter").hasClass("select2-hidden-accessible")) {
            $("#tableVehicleStatusFilter").trigger("change.select2");
        }
        isUpdatingFilters = false;

        applyFiltersAndRender(true);
    });

    // Real-time search filter
    $("#vaSearchInput").on("input keyup", function() {
        applyFiltersAndRender(true);
    });

    // Clear search button [x]
    $("#btnVaClearSearch").on("click", function() {
        $("#vaSearchInput").val('');
        $(this).hide();
        applyFiltersAndRender(true);
    });

    // Reset Filters button
    $("#btnResetFilters").on("click", function() {
        isUpdatingFilters = true;
        $("#tableDeliveryDateFilter").val("all");
        $("#tableClientFilter").val("all");
        $("#tableProductFilter").val("all");
        $("#tableVehicleStatusFilter").val("all");

        if ($.fn.select2) {
            $("#tableDeliveryDateFilter, #tableClientFilter, #tableProductFilter, #tableVehicleStatusFilter").trigger("change.select2");
        }

        $("#vaSearchInput").val("");
        $("#btnVaClearSearch").hide();

        currentFilter = "all";
        $(".status-filter-card").removeClass("active");
        $("#cardFilterAll").addClass("active");
        isUpdatingFilters = false;

        applyFiltersAndRender(true);
    });

    // Render Table Rows
    function renderOrdersTableRows(filtered) {
        currentFilteredOrders = filtered;
        let tbody = $("#vaOrdersTbody");
        tbody.empty();

        if (filtered.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        <i class="fa fa-info-circle fa-2x mb-2 text-secondary opacity-50"></i>
                        <h6 class="fw-bold text-dark">No Orders Found</h6>
                        <div class="small">No orders match the selected week and filter criteria.</div>
                    </td>
                </tr>
            `);
            return;
        }

        filtered.forEach((ord, idx) => {
            let isAllocated = ord.is_vehicle_allocated;

            // 1. Delivery Date column
            let dayName = getDayName(ord.deliverydate);
            let dateCol = `
                <div class="fw-bold text-dark font-monospace" style="font-size: 12.5px;">
                    <i class="fa fa-calendar-day text-success me-1"></i>${ord.formatted_delivery_date}
                </div>
                ${dayName ? `<span class="badge bg-light text-secondary border px-1.5 py-0" style="font-size: 10.5px;">${dayName}</span>` : ''}
            `;

            // 2. Client & Sales Order Details column (Compact, 3 lines)
            let clientCol = `
                <div class="fw-bold text-dark text-truncate" style="font-size: 13.5px;" title="${escapeHtml(ord.clientname || 'N/A')}">
                    ${escapeHtml(ord.clientname || 'N/A')}
                </div>
                <div class="d-flex align-items-center gap-1 my-0.5 flex-wrap">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-1.5 py-0.5" style="font-size: 11px;">SO: ${escapeHtml(ord.sono || '-')}</span>
                    ${ord.psono ? `<span class="badge bg-light text-secondary border font-monospace px-1.5 py-0.5" style="font-size: 10px;">PSO: ${escapeHtml(ord.psono)}</span>` : ''}
                </div>
                <div class="text-secondary small d-flex align-items-center gap-2 flex-wrap" style="font-size: 11px;">
                    <span><i class="fa fa-phone text-primary me-0.5"></i><a href="tel:${escapeHtml(ord.mobileno)}" class="text-decoration-none text-secondary">${escapeHtml(ord.mobileno || '-')}</a></span>
                    ${ord.districtname ? `<span><i class="fa fa-map-marker-alt text-danger me-0.5"></i>${escapeHtml(ord.districtname)}</span>` : ''}
                </div>
            `;

            // 3. Order & Plants Details column (Compact, 3 lines)
            let billableQty = parseInt(ord.total_billable_qty || 0);
            let cleanProd = formatProductNameClean(ord.clean_product_name || ord.product_names || 'Crop');
            let isGrafted = isProductGrafted(ord.product_names || '');
            let orderCol = `
                <div class="d-flex align-items-center gap-1 mb-0.5">
                    <span class="badge bg-success fw-bold px-2 py-0.5 shadow-sm" style="font-size: 12px;">
                        <i class="fa fa-leaf me-1"></i>${billableQty.toLocaleString()} Plants
                    </span>
                    ${isGrafted ? 
                        '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-1 py-0.5" style="font-size: 10px;">Grafted</span>' : 
                        '<span class="badge bg-secondary-subtle text-secondary border px-1 py-0.5" style="font-size: 10px;">Non-Grafted</span>'
                    }
                </div>
                <div class="fw-bold text-dark text-truncate" style="font-size: 12px;" title="${escapeHtml(cleanProd)}">
                    <i class="fa fa-seedling text-success me-1"></i>${escapeHtml(cleanProd)}
                </div>
                ${ord.stock_order_nos ? `<div class="text-muted small" style="font-size: 10.5px;">Stock: <b class="text-primary font-monospace">${escapeHtml(ord.stock_order_nos)}</b></div>` : ''}
            `;

            // 4. Delivery & Freight Charges column (NEW)
            let freightCol = formatFreightAndDeliveryColumn(ord);

            // 5. Vehicle & Driver Assignment column (Compact, 3 lines)
            let vehicleCol = '';
            if (isAllocated) {
                let vehNo = escapeHtml(ord.effective_vehicleno);
                let vehName = ord.effective_vehiclename ? ` — ${escapeHtml(ord.effective_vehiclename)}` : '';
                let driver = ord.effective_drivername ? `<b>Driver:</b> ${escapeHtml(ord.effective_drivername)}` : '';
                let mobile = ord.effective_drivermobile ? ` (<a href="tel:${escapeHtml(ord.effective_drivermobile)}" class="text-decoration-none fw-semibold">${escapeHtml(ord.effective_drivermobile)}</a>)` : '';
                let schedDate = ord.scheduled_dispatch_date ? formatDateDisplay(ord.scheduled_dispatch_date) : '';
                let allocBy = escapeHtml(ord.allocated_by_name || ord.allocated_by || 'Logistics');
                let allocAt = ord.allocated_at_formatted ? `(${escapeHtml(ord.allocated_at_formatted)})` : '';
                let remarks = ord.vehicle_remarks ? `<div class="text-muted fst-italic text-truncate small" style="font-size: 10px; max-width: 250px;" title="${escapeHtml(ord.vehicle_remarks)}"><i class="fa fa-comment-dots text-primary me-0.5"></i>${escapeHtml(ord.vehicle_remarks)}</div>` : '';

                vehicleCol = `
                    <div class="mb-0.5">
                        <span class="badge bg-success-subtle text-success border border-success-subtle plate-badge py-0.5 px-2" style="font-size: 11.5px;">
                            <i class="fa fa-truck me-1"></i> ${vehNo}${vehName}
                        </span>
                    </div>
                    <div class="small text-dark text-truncate" style="font-size: 11.5px;">
                        ${driver}${mobile}
                    </div>
                    <div class="text-muted small d-flex align-items-center gap-1 flex-wrap" style="font-size: 10.5px;">
                        ${schedDate ? `<span><i class="fa fa-clock text-info me-0.5"></i>${schedDate}</span>` : ''}
                        <span>By: <b class="text-dark">${allocBy}</b> ${allocAt}</span>
                    </div>
                    ${remarks}
                `;
            } else {
                vehicleCol = `
                    <div class="d-inline-flex align-items-center gap-1.5 px-2 py-0.5 rounded border border-warning-subtle bg-warning-subtle text-warning-emphasis">
                        <i class="fa fa-exclamation-triangle text-warning" style="font-size: 12px;"></i>
                        <span class="fw-bold" style="font-size: 11.5px;">Pending Vehicle</span>
                    </div>
                    <div class="text-muted small mt-0.5" style="font-size: 10.5px;">Awaiting logistics allocation</div>
                `;
            }

            // 6. Action Buttons
            let actionBtn = '';
            if (isAllocated) {
                actionBtn = `
                    <div class="d-flex flex-column gap-1 align-items-center">
                        <button type="button" class="btn btn-sm btn-outline-primary fw-bold open-vehicle-alloc-modal-btn w-100 py-0.5 px-1 shadow-sm" style="font-size: 11.5px;" data-index="${idx}">
                            <i class="fa fa-edit me-0.5"></i> Edit
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-vehicle-alloc-btn w-100 py-0.5 px-1" style="font-size: 11px;" data-sono="${escapeHtml(ord.sono || '')}" data-psono="${escapeHtml(ord.psono || '')}" title="Remove vehicle assignment">
                            <i class="fa fa-trash-alt me-0.5"></i> Remove
                        </button>
                    </div>
                `;
            } else {
                actionBtn = `
                    <button type="button" class="btn btn-sm btn-primary fw-bold open-vehicle-alloc-modal-btn w-100 py-1 px-1.5 shadow-sm" style="font-size: 11.5px;" data-index="${idx}">
                        <i class="fa fa-truck me-1"></i> Allocate
                    </button>
                `;
            }

            tbody.append(`
                <tr>
                    <td class="text-center fw-bold text-secondary py-2">${idx + 1}</td>
                    <td class="py-2">${dateCol}</td>
                    <td class="py-2">${clientCol}</td>
                    <td class="py-2">${orderCol}</td>
                    <td class="py-2">${freightCol}</td>
                    <td class="py-2">${vehicleCol}</td>
                    <td class="text-center py-2">${actionBtn}</td>
                </tr>
            `);
        });
    }

    // 3. Load Active Vehicles Master for Modal
    function loadActiveVehiclesMaster() {
        $.ajax({
            url: "modal/finaldispatch_modal.php?action=GetActiveVehiclesMaster",
            type: "POST",
            data: { projectid: projectid },
            dataType: "json",
            success: function(res) {
                if (res.status === "success") {
                    cachedVehicles = res.data || [];
                    populateVehicleModalDropdown();
                }
            }
        });
    }

    function populateVehicleModalDropdown() {
        let sel = $("#vam_vehicle_select");
        sel.empty().append('<option value="">-- Choose Registered Vehicle or Custom --</option>');
        cachedVehicles.forEach(v => {
            let capText = parseInt(v.plantscapacity) > 0 ? ` (${parseInt(v.plantscapacity).toLocaleString()} cap)` : '';
            sel.append(`<option value="${v.vehicleid}" data-no="${v.vehicleno || ''}" data-name="${v.vehiclename || ''}" data-owner="${v.vehicleownername || ''}" data-cap="${v.plantscapacity || 0}">${v.vehiclename || 'Vehicle'} - ${v.vehicleno || 'No Plate'}${capText}</option>`);
        });
        sel.append('<option value="custom">[+] Enter Custom / Market Vehicle</option>');
    }

    // Open Allocation Modal using array index
    $(document).on("click", ".open-vehicle-alloc-modal-btn", function(e) {
        e.preventDefault();
        let idx = $(this).data("index");
        let row = currentFilteredOrders[idx];
        if (!row) {
            Swal.fire("Error", "Order details could not be retrieved. Please refresh.", "error");
            return;
        }

        $("#vehicleAllocForm")[0].reset();
        $("#vam_projectid").val(projectid);
        $("#vam_sono").val(ordVal(row.sono));
        $("#vam_psono").val(ordVal(row.psono));
        $("#vam_clientid").val(ordVal(row.clientid));

        let totalPlants = parseInt(row.total_billable_qty || 0);
        $("#vam_total_plants").val(totalPlants);

        // Display Header Box
        $("#vam_display_client").text(row.clientname || 'Client');
        $("#vam_display_destination").text(`${row.districtname || ''}, ${row.statename || ''} ${row.delivery_address ? '(' + row.delivery_address + ')' : ''}`);
        $("#vam_display_sono").text(`SO: ${row.sono || '-'} | PSONO: ${row.psono || '-'}`);
        $("#vam_display_qty").html(`${totalPlants.toLocaleString()} Plants <span class="badge bg-success-subtle text-success ms-1">Ready for Vehicle</span>`);

        populateVehicleModalDropdown();

        // Check if already allocated
        let vSelect = $("#vam_vehicle_select");
        if (row.vehicle_id && vSelect.find(`option[value="${row.vehicle_id}"]`).length) {
            vSelect.val(row.vehicle_id);
        } else if (row.effective_vehicleno) {
            vSelect.val('custom');
        } else {
            vSelect.val('');
        }

        $("#vam_vehicleno").val(row.effective_vehicleno || '');
        $("#vam_vehiclename").val(row.effective_vehiclename || '');
        $("#vam_vehicleownername").val(row.vehicleownername || '');
        $("#vam_drivername").val(row.effective_drivername || '');
        $("#vam_drivermobileno").val(row.effective_drivermobile || '');
        $("#vam_scheduled_dispatch_date").val(row.scheduled_dispatch_date || new Date().toISOString().split('T')[0]);
        $("#vam_remarks").val(row.vehicle_remarks || '');

        checkVehicleCapacity(totalPlants);

        showAllocModal();
    });

    function ordVal(val) {
        return (val && val !== 'null' && val !== 'undefined') ? val : '';
    }

    // Vehicle dropdown change handler
    $("#vam_vehicle_select").on("change", function() {
        let val = $(this).val();
        let opt = $(this).find("option:selected");
        if (val === 'custom') {
            $("#vam_vehicleno").val('').focus();
            $("#vam_vehiclename").val('');
            $("#vam_vehicleownername").val('');
            $("#vamCapacityNoticeBox").hide();
        } else if (val) {
            $("#vam_vehicleno").val(opt.data("no") || '');
            $("#vam_vehiclename").val(opt.data("name") || '');
            $("#vam_vehicleownername").val(opt.data("owner") || '');
            let cap = parseInt(opt.data("cap") || 0);
            let totalPlants = parseInt($("#vam_total_plants").val() || 0);
            showCapacityAlert(totalPlants, cap);
        } else {
            $("#vamCapacityNoticeBox").hide();
        }
    });

    function checkVehicleCapacity(totalPlants) {
        let opt = $("#vam_vehicle_select").find("option:selected");
        let cap = parseInt(opt.data("cap") || 0);
        if (cap > 0) {
            showCapacityAlert(totalPlants, cap);
        } else {
            $("#vamCapacityNoticeBox").hide();
        }
    }

    function showCapacityAlert(orderPlants, vehicleCap) {
        let box = $("#vamCapacityNoticeBox");
        let notice = $("#vamCapacityNotice");
        let text = $("#vamCapacityNoticeText");
        box.show();

        if (vehicleCap >= orderPlants) {
            notice.removeClass("alert-danger alert-warning").addClass("alert-success");
            text.html(`<b>Capacity Fit:</b> Vehicle capacity (${vehicleCap.toLocaleString()} plants) easily accommodates order load (${orderPlants.toLocaleString()} plants).`);
        } else {
            notice.removeClass("alert-success").addClass("alert-danger");
            text.html(`<b>Capacity Alert:</b> Order load (${orderPlants.toLocaleString()} plants) exceeds single vehicle capacity (${vehicleCap.toLocaleString()} plants). Multiple trips or larger vehicle required.`);
        }
    }

    // Mobile number real-time numeric filter & live validation
    $("#vam_drivermobileno").on("input", function() {
        this.value = this.value.replace(/\D/g, '').slice(0, 10);
        let val = this.value.trim();
        if (val.length === 10) {
            if (/^[6-9]\d{9}$/.test(val)) {
                $(this).removeClass("is-invalid").addClass("is-valid");
                $("#vamMobileFeedback").hide();
            } else {
                $(this).removeClass("is-valid").addClass("is-invalid");
                $("#vamMobileFeedback").show();
            }
        } else if (val.length > 0) {
            $(this).removeClass("is-valid").addClass("is-invalid");
            $("#vamMobileFeedback").show();
        } else {
            $(this).removeClass("is-invalid is-valid");
            $("#vamMobileFeedback").hide();
        }
    });

    // Save Vehicle Allocation
    $("#vamSaveBtn").on("click", function() {
        let btn = $(this);
        let vehNo = $("#vam_vehicleno").val().trim();
        if (!vehNo) {
            Swal.fire("Required Field", "Please enter or select a Vehicle Registration Number.", "warning");
            $("#vam_vehicleno").focus();
            return;
        }

        let driverMobile = $("#vam_drivermobileno").val().trim();
        if (driverMobile && !/^[6-9]\d{9}$/.test(driverMobile)) {
            Swal.fire({
                title: "Invalid Mobile Number",
                text: "Please enter a valid 10-digit driver mobile number starting with 6, 7, 8, or 9 (e.g. 9876543210).",
                icon: "warning",
                confirmButtonColor: "#f39c12"
            });
            $("#vam_drivermobileno").addClass("is-invalid").focus();
            $("#vamMobileFeedback").show();
            return;
        }

        let postData = $("#vehicleAllocForm").serializeArray();
        let payload = {};
        postData.forEach(item => { payload[item.name] = item.value; });
        payload.loginid = loginid;

        btn.prop("disabled", true).html(`<i class="fa fa-spinner fa-spin me-1"></i> Saving...`);

        $.ajax({
            url: "modal/finaldispatch_modal.php?action=SaveOrderVehicleAllocation",
            type: "POST",
            data: payload,
            dataType: "json",
            success: function(res) {
                btn.prop("disabled", false).html(`<i class="fa fa-check-circle me-1"></i> Confirm Vehicle Allocation`);
                if (res.status === "success") {
                    hideAllocModal();
                    Swal.fire({
                        title: "Vehicle Allocated!",
                        text: res.message || "Vehicle allocated successfully.",
                        icon: "success",
                        timer: 2000,
                        showConfirmButton: false
                    });
                    loadOrdersForSelectedWeek();
                } else {
                    Swal.fire("Error", res.message || "Failed to save allocation.", "error");
                }
            },
            error: function() {
                btn.prop("disabled", false).html(`<i class="fa fa-check-circle me-1"></i> Confirm Vehicle Allocation`);
                Swal.fire("Error", "Server error while saving vehicle allocation.", "error");
            }
        });
    });

    // Remove Vehicle Allocation
    $(document).on("click", ".remove-vehicle-alloc-btn", function() {
        let sono = $(this).data("sono");
        let psono = $(this).data("psono");

        Swal.fire({
            title: "Remove Vehicle Allocation?",
            text: `Are you sure you want to remove the allocated vehicle from order ${sono}?`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#dc3545",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "Yes, Remove"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "modal/finaldispatch_modal.php?action=DeleteOrderVehicleAllocation",
                    type: "POST",
                    data: {
                        projectid: projectid,
                        sono: sono,
                        psono: psono
                    },
                    dataType: "json",
                    success: function(res) {
                        if (res.status === "success") {
                            Swal.fire("Removed", res.message, "success");
                            loadOrdersForSelectedWeek();
                        } else {
                            Swal.fire("Error", res.message, "error");
                        }
                    },
                    error: function() {
                        Swal.fire("Error", "Server error while removing allocation.", "error");
                    }
                });
            }
        });
    });

    // ==========================================
    // EXPORT TO EXCEL FUNCTIONALITY
    // ==========================================
    $("#btnExportExcel, #btnExportExcelWeekBar").on("click", function() {
        exportOrdersToExcel();
    });

    function exportOrdersToExcel() {
        if (!currentFilteredOrders || currentFilteredOrders.length === 0) {
            Swal.fire("No Data", "No orders available in current view to export.", "warning");
            return;
        }

        let excelRows = [];
        currentFilteredOrders.forEach((ord, index) => {
            let isAlloc = ord.is_vehicle_allocated;
            let dayName = getDayName(ord.deliverydate);
            let billableQty = parseInt(ord.total_billable_qty || 0);
            let isGrafted = isProductGrafted(ord.product_names || '');
            let cleanProd = formatProductNameClean(ord.clean_product_name || ord.product_names || '');
            let pickupType = (ord.dl_pickuptype === 'client_pickup') ? 'Client Pickup' : ((ord.dl_pickuptype === 'boxpacking' || ord.dl_pickuptype === 'box') ? 'Box Packing' : 'Delivered by Farm');
            let freightAmt = parseFloat(ord.dl_freightamt || ord.dl_deliverycharges || 0);
            let totalDist = parseFloat(ord.dl_totaldistance || 0);
            let delAddr = ord.dl_deliveryaddress || ord.delivery_address || '';

            excelRows.push({
                "S.No": index + 1,
                "Delivery Date": ord.formatted_delivery_date || ord.deliverydate || '',
                "Day": dayName || '',
                "Client Name": ord.clientname || '',
                "Mobile Number": ord.mobileno || '',
                "District": ord.districtname || ord.dl_dest_district || '',
                "State": ord.statename || ord.dl_dest_state || '',
                "SO Number": ord.sono || '',
                "PSO Number": ord.psono || '',
                "Stock Order No": ord.stock_order_nos || '',
                "Product Name": cleanProd || '',
                "Crop Type": isGrafted ? 'Grafted' : 'Non-Grafted',
                "Order Qty (Plants)": billableQty,
                "Delivery Mode": pickupType,
                "Freight Charges (₹)": freightAmt,
                "Total Distance (km)": totalDist,
                "Delivery Address": delAddr,
                "Vehicle Registration No": isAlloc ? (ord.effective_vehicleno || '') : 'Pending',
                "Vehicle Type": ord.effective_vehiclename || '',
                "Owner / Transporter": ord.vehicleownername || '',
                "Driver Name": ord.effective_drivername || '',
                "Driver Contact": ord.effective_drivermobile || '',
                "Scheduled Dispatch Date": ord.scheduled_dispatch_date || '',
                "Vehicle Status": isAlloc ? 'Allocated' : 'Pending Vehicle',
                "Allocated By": ord.allocated_by_name || ord.allocated_by || '',
                "Allocated At": ord.allocated_at_formatted || '',
                "Logistics Remarks": ord.vehicle_remarks || ord.dl_remark || ''
            });
        });

        if (typeof XLSX === 'undefined') {
            Swal.fire("Error", "Excel export library not loaded. Please refresh the page.", "error");
            return;
        }

        let ws = XLSX.utils.json_to_sheet(excelRows);
        let colWidths = [
            { wch: 6 },  // S.No
            { wch: 14 }, // Delivery Date
            { wch: 11 }, // Day
            { wch: 22 }, // Client Name
            { wch: 14 }, // Mobile Number
            { wch: 16 }, // District
            { wch: 14 }, // State
            { wch: 14 }, // SO
            { wch: 14 }, // PSO
            { wch: 14 }, // Stock
            { wch: 28 }, // Product
            { wch: 13 }, // Crop Type
            { wch: 16 }, // Order Qty
            { wch: 18 }, // Delivery Mode
            { wch: 16 }, // Freight
            { wch: 16 }, // Distance
            { wch: 30 }, // Delivery Address
            { wch: 18 }, // Vehicle No
            { wch: 16 }, // Vehicle Type
            { wch: 18 }, // Owner
            { wch: 18 }, // Driver
            { wch: 15 }, // Driver Mobile
            { wch: 16 }, // Scheduled Date
            { wch: 16 }, // Status
            { wch: 18 }, // Allocated By
            { wch: 20 }, // Allocated At
            { wch: 25 }  // Remarks
        ];
        ws['!cols'] = colWidths;

        let wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Vehicle Allocations");

        let weekVal = ($("#vaWeekSelector").val() || 'Schedule').replace(/[^a-zA-Z0-9_-]/g, '_');
        let fileName = `Vehicle_Allocation_Schedule_${weekVal}.xlsx`;
        XLSX.writeFile(wb, fileName);

        Swal.fire({
            title: "Excel Exported!",
            text: `${excelRows.length} order(s) successfully exported to ${fileName}`,
            icon: "success",
            timer: 2000,
            showConfirmButton: false
        });
    }

    // ==========================================
    // PDF REPORT & PRINT PREVIEW FUNCTIONALITY
    // ==========================================
    $("#btnExportPdf, #btnExportPdfWeekBar").on("click", function() {
        if (!currentFilteredOrders || currentFilteredOrders.length === 0) {
            Swal.fire("No Data", "No orders available in current view to generate report.", "warning");
            return;
        }

        let docHtml = buildPdfPrintableDocumentHtml();
        $("#vaPdfPrintableDocument").html(docHtml);
        $("#vaPdfFooterCount").text(`Total orders in report: ${currentFilteredOrders.length}`);
        
        showPdfPreviewModal();
    });

    // Print PDF Document
    $("#btnPdfModalPrint, #btnPdfModalPrintBottom").on("click", function() {
        printPdfDocument();
    });

    function printPdfDocument() {
        let content = document.getElementById('vaPdfPrintableDocument');
        if (!content) return;

        let printWin = window.open('', '_blank', 'width=1120,height=800');
        if (!printWin) {
            window.print();
            return;
        }

        printWin.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Vehicle Allocation Report - Karjee Farm</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>
                    @page { 
                        size: A4 landscape; 
                        margin: 6mm 6mm 6mm 6mm; 
                    }
                    * {
                        box-sizing: border-box;
                    }
                    html, body { 
                        width: 100% !important;
                        height: auto !important;
                        background: #ffffff !important; 
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
                        padding: 0 !important; 
                        margin: 0 !important;
                        color: #0f172a; 
                        -webkit-print-color-adjust: exact !important;
                        print-color-adjust: exact !important;
                    }
                    table { 
                        border-collapse: collapse; 
                        width: 100% !important; 
                        table-layout: fixed;
                        page-break-inside: auto; 
                    }
                    thead { 
                        display: table-header-group; 
                    }
                    tr { 
                        page-break-inside: avoid; 
                        page-break-after: auto; 
                    }
                    th, td { 
                        border: 1px solid #cbd5e1 !important; 
                    }
                </style>
            </head>
            <body>
                <div style="width: 100%; margin: 0; padding: 0;">
                    ${content.innerHTML}
                </div>
                <script>
                    window.onload = function() {
                        window.focus();
                        window.print();
                        setTimeout(function() { window.close(); }, 1000);
                    };
                <\/script>
            </body>
            </html>
        `);
        printWin.document.close();
    }

    // Download PDF Document
    $("#btnPdfModalDownload, #btnPdfModalDownloadBottom").on("click", function() {
        downloadPdfDocument();
    });

    function downloadPdfDocument() {
        let element = document.getElementById('vaPdfPrintableDocument');
        if (!element) return;
        
        let weekVal = ($("#vaWeekSelector").val() || 'Schedule').replace(/[^a-zA-Z0-9_-]/g, '_');
        
        let btn = $("#btnPdfModalDownload, #btnPdfModalDownloadBottom");
        let origHtml = btn.html();
        btn.prop("disabled", true).html(`<i class="fa fa-spinner fa-spin me-1"></i> Downloading PDF...`);

        let opt = {
            margin: [5, 5, 5, 5],
            filename: `Vehicle_Allocation_A4_${weekVal}.pdf`,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { 
                scale: 2, 
                useCORS: true, 
                logging: false,
                backgroundColor: '#ffffff'
            },
            jsPDF: { 
                unit: 'mm', 
                format: 'a4', 
                orientation: 'landscape' 
            },
            pagebreak: { 
                mode: ['avoid-all', 'css', 'legacy']
            }
        };

        if (typeof html2pdf === 'undefined') {
            btn.prop("disabled", false).html(origHtml);
            Swal.fire("Note", "Opening standard print dialog. Please choose 'Save as PDF'.", "info");
            printPdfDocument();
            return;
        }

        html2pdf().set(opt).from(element).save().then(function() {
            btn.prop("disabled", false).html(origHtml);
            Swal.fire({
                title: "A4 PDF Downloaded",
                text: "Vehicle Allocation Report downloaded successfully in A4 landscape format.",
                icon: "success",
                timer: 2000,
                showConfirmButton: false
            });
        }).catch(function(err) {
            console.error("PDF generation error:", err);
            btn.prop("disabled", false).html(origHtml);
            Swal.fire({
                title: "Print / Save PDF",
                text: "Direct download encountered an issue. Opening print dialog to Save as PDF.",
                icon: "info",
                confirmButtonText: "Open Print Dialog"
            }).then(() => {
                printPdfDocument();
            });
        });
    }

    function buildPdfPrintableDocumentHtml() {
        let orders = currentFilteredOrders || [];
        let weekVal = $("#vaWeekSelector option:selected").text() || $("#vaWeekSelector").val() || 'Schedule';
        let yearVal = $("#vaYearFilter").val() || '';
        let monthText = $("#vaMonthFilter option:selected").text() || '';
        let generatedDate = new Date().toLocaleString('en-IN', { dateStyle: 'medium', timeStyle: 'short' });
        
        let totalOrders = orders.length;
        let totalPlants = orders.reduce((sum, o) => sum + parseInt(o.total_billable_qty || 0), 0);
        let allocatedCount = orders.filter(o => o.is_vehicle_allocated).length;
        let pendingCount = totalOrders - allocatedCount;

        let tableRowsHtml = '';
        if (orders.length === 0) {
            tableRowsHtml = `<tr><td colspan="7" style="text-align: center; padding: 24px; color: #64748b; font-weight: bold;">No orders found for the selected filter criteria.</td></tr>`;
        } else {
            orders.forEach((ord, index) => {
                let isAlloc = ord.is_vehicle_allocated;
                let dayName = getDayName(ord.deliverydate);
                let billableQty = parseInt(ord.total_billable_qty || 0);
                let isGrafted = isProductGrafted(ord.product_names || '');
                let cleanProd = formatProductNameClean(ord.clean_product_name || ord.product_names || '');
                
                // Delivery mode & freight
                let pickupType = (ord.dl_pickuptype === 'client_pickup') ? 'Client Pickup' : ((ord.dl_pickuptype === 'boxpacking' || ord.dl_pickuptype === 'box') ? 'Box Packing' : 'Delivered by Farm');
                let freightAmt = parseFloat(ord.dl_freightamt || ord.dl_deliverycharges || 0);
                let freightDisplay = (freightAmt > 0) ? `₹ ${freightAmt.toLocaleString()}` : 'Free / Included';
                let totalDist = parseFloat(ord.dl_totaldistance || 0);
                let distDisplay = totalDist > 0 ? ` (${totalDist} km)` : '';

                // Full Complete Address without truncation
                let fullAddr = (ord.dl_deliveryaddress || ord.delivery_address || '').trim();
                if (!fullAddr && (ord.dl_dest_city || ord.dl_dest_district)) {
                    fullAddr = [ord.dl_dest_city, ord.dl_dest_district, ord.dl_dest_state, ord.dl_dest_pincode].filter(Boolean).join(', ');
                }
                let pointHtml = ord.dl_pickuppoint ? `<div style="font-size: 9px; color: #475569; margin-top: 2px;"><b>Point:</b> ${escapeHtml(ord.dl_pickuppoint)}</div>` : '';
                let remarkHtml = ord.dl_remark ? `<div style="font-size: 9px; color: #64748b; font-style: italic; margin-top: 2px;">💬 ${escapeHtml(ord.dl_remark)}</div>` : '';
                let addrHtml = fullAddr ? `<div style="font-size: 9.5px; color: #334155; margin-top: 3px; line-height: 1.35; word-break: break-word; white-space: normal;"><span style="color: #ef4444;">📍</span> ${escapeHtml(fullAddr)}</div>` : '';

                // Vehicle details
                let vehHtml = '';
                if (isAlloc) {
                    let vehNo = escapeHtml(ord.effective_vehicleno || '');
                    let vehName = ord.effective_vehiclename ? ` (${escapeHtml(ord.effective_vehiclename)})` : '';
                    let driver = ord.effective_drivername ? `${escapeHtml(ord.effective_drivername)}` : '';
                    let mobile = ord.effective_drivermobile ? ` - ${escapeHtml(ord.effective_drivermobile)}` : '';
                    let schedDate = ord.scheduled_dispatch_date ? `<div style="font-size: 9.5px; color: #475569;"><b>Dispatch:</b> ${formatDateDisplay(ord.scheduled_dispatch_date)}</div>` : '';
                    let allocBy = ord.allocated_by_name || ord.allocated_by || '';

                    vehHtml = `
                        <div style="font-weight: 700; color: #166534; font-size: 11px;">
                            🚛 ${vehNo}${vehName}
                        </div>
                        <div style="font-size: 10.5px; color: #1e293b;">
                            <b>Driver:</b> ${driver}${mobile}
                        </div>
                        ${schedDate}
                        ${allocBy ? `<div style="font-size: 9px; color: #64748b;">By: ${escapeHtml(allocBy)}</div>` : ''}
                    `;
                } else {
                    vehHtml = `<span style="display: inline-block; padding: 2px 6px; font-size: 10px; font-weight: 600; color: #b45309; background-color: #fef3c7; border: 1px solid #fde68a; border-radius: 4px;">⚠️ Pending Vehicle</span>`;
                }

                let destLocation = [ord.districtname || ord.dl_dest_district, ord.statename || ord.dl_dest_state].filter(Boolean).join(', ');

                tableRowsHtml += `
                    <tr style="page-break-inside: avoid; border-bottom: 1px solid #cbd5e1; font-size: 11px;">
                        <td style="text-align: center; font-weight: bold; color: #64748b; padding: 6px 4px; vertical-align: top; width: 3.5%;">${index + 1}</td>
                        <td style="padding: 6px 6px; vertical-align: top; white-space: nowrap; width: 9.5%;">
                            <div style="font-weight: 700; color: #0f172a; font-size: 11.5px;">${ord.formatted_delivery_date}</div>
                            <div style="font-size: 10px; color: #64748b;">${dayName}</div>
                        </td>
                        <td style="padding: 6px 6px; vertical-align: top; width: 20%;">
                            <div style="font-weight: 700; color: #0f172a; font-size: 12px;">${escapeHtml(ord.clientname || 'N/A')}</div>
                            <div style="font-size: 10.5px; color: #475569; margin-top: 1px;">
                                <span style="font-weight: 600; color: #0284c7;">SO: ${escapeHtml(ord.sono || '-')}</span> ${ord.psono ? `| <span style="color: #64748b;">PSO: ${escapeHtml(ord.psono)}</span>` : ''}
                            </div>
                            <div style="font-size: 10px; color: #64748b; margin-top: 1px;">
                                📞 ${escapeHtml(ord.mobileno || '-')} ${destLocation ? `| 📍 ${escapeHtml(destLocation)}` : ''}
                            </div>
                        </td>
                        <td style="padding: 6px 6px; vertical-align: top; width: 18%;">
                            <div style="font-weight: 700; color: #0f172a; font-size: 11.5px;">${escapeHtml(cleanProd)}</div>
                            <div style="font-size: 10px; margin-top: 1px;">
                                <span style="display: inline-block; padding: 1px 5px; font-size: 9.5px; font-weight: 600; border-radius: 3px; ${isGrafted ? 'background-color: #fee2e2; color: #991b1b;' : 'background-color: #f1f5f9; color: #475569;'}">${isGrafted ? 'Grafted' : 'Non-Grafted'}</span>
                                ${ord.stock_order_nos ? `<span style="color: #0284c7; margin-left: 4px; font-family: monospace;">Stock: ${escapeHtml(ord.stock_order_nos)}</span>` : ''}
                            </div>
                        </td>
                        <td style="text-align: right; padding: 6px 6px; vertical-align: top; white-space: nowrap; width: 8%;">
                            <span style="font-weight: 800; font-size: 13px; color: #166534;">${billableQty.toLocaleString()}</span>
                            <div style="font-size: 9.5px; color: #64748b;">Plants</div>
                        </td>
                        <td style="padding: 6px 6px; vertical-align: top; width: 21%; word-break: break-word;">
                            <div style="font-weight: 600; color: #334155; font-size: 11px;">${pickupType}</div>
                            <div style="font-size: 10.5px; color: #166534; font-weight: 600;">${freightDisplay}${distDisplay}</div>
                            ${addrHtml}
                            ${pointHtml}
                            ${remarkHtml}
                        </td>
                        <td style="padding: 6px 6px; vertical-align: top; width: 20%;">
                            ${vehHtml}
                        </td>
                    </tr>
                `;
            });
        }

        let docHtml = `
            <div style="padding: 0; color: #0f172a; width: 100%;">
                <!-- Header -->
                <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #23b185; padding-bottom: 10px; margin-bottom: 10px;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            ${krfCompanyLogo ? `
                                <img src="${krfCompanyLogo}" alt="Karjee Farm" style="height: 46px; width: auto; max-width: 65px; object-fit: contain; vertical-align: middle;" />
                            ` : `
                                <img src="../img/logo/logo.png" alt="Karjee Farm" style="height: 46px; width: auto; max-width: 65px; object-fit: contain; vertical-align: middle;" />
                            `}
                            <div>
                                <h2 style="margin: 0; font-size: 21px; font-weight: 900; color: #0f172a; letter-spacing: 0.5px; text-transform: uppercase; line-height: 1.1;">KARJEE FARM</h2>
                                <div style="font-size: 12.5px; font-weight: 600; color: #047857; margin-top: 2px;">Logistics & Vehicle Allocation Dispatch Schedule</div>
                            </div>
                        </div>
                    </div>
                    <div style="text-align: right; font-size: 11px; color: #475569;">
                        <div><b>Week Period:</b> <span style="font-weight: 700; color: #0f172a;">${weekVal}</span></div>
                        <div><b>Month & Year:</b> ${monthText} (${yearVal})</div>
                        <div><b>Generated On:</b> ${generatedDate}</div>
                    </div>
                </div>

                <!-- Schedule Table -->
                <table style="width: 100%; table-layout: fixed; border-collapse: collapse; margin-bottom: 12px; border: 1px solid #cbd5e1;">
                    <thead style="display: table-header-group;">
                        <tr style="background-color: #23b185; color: white; font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.5px;">
                            <th style="padding: 7px 4px; text-align: center; width: 3.5%; border: 1px solid #23b185;">#</th>
                            <th style="padding: 7px 6px; text-align: left; width: 9.5%; border: 1px solid #23b185;">Delivery Date</th>
                            <th style="padding: 7px 6px; text-align: left; width: 20%; border: 1px solid #23b185;">Client & Sales Order</th>
                            <th style="padding: 7px 6px; text-align: left; width: 18%; border: 1px solid #23b185;">Product & Variety</th>
                            <th style="padding: 7px 6px; text-align: right; width: 8%; border: 1px solid #23b185;">Qty (Plants)</th>
                            <th style="padding: 7px 6px; text-align: left; width: 21%; border: 1px solid #23b185;">Delivery & Freight</th>
                            <th style="padding: 7px 6px; text-align: left; width: 20%; border: 1px solid #23b185;">Vehicle & Driver Assignment</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${tableRowsHtml}
                    </tbody>
                </table>
            </div>
        `;

        return docHtml;
    }

});
</script>
