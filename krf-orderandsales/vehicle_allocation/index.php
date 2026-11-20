<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('../../header.php');
date_default_timezone_set("Asia/Calcutta");

$logoPath = __DIR__ . '/../../img/logo/logo.png';
$krfLogoBase64 = '';
if (file_exists($logoPath)) {
    $logoData = file_get_contents($logoPath);
    $krfLogoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
}

$projectid = intval($_SESSION['projectid'] ?? 17);
if ($projectid <= 0) {
    $projectid = 17;
}
$loginid = $_SESSION['loginid'] ?? $_SESSION['user_id'] ?? '';
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
<link rel="stylesheet" href="css/vehicle_allocation.css?v=<?= time() ?>">
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
                            <button type="button" class="btn btn-success fw-bold" id="btnRefreshData" style="height: 40px; display: inline-flex; align-items: center;">
                                <i class="fa fa-sync-alt me-1"></i> Refresh Orders
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body p-3">

                    <!-- 2. Filter Bar: Year, Month & Actions -->
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
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header py-3 px-4 d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #e0f2fe 0%, #dbeafe 100%); border-bottom: 1px solid #bae6fd;">
                <div class="d-flex align-items-center gap-2.5">
                    <div class="rounded-circle bg-white text-primary d-flex align-items-center justify-content-center shadow-xs" style="width: 38px; height: 38px; border: 1px solid #bae6fd;">
                        <i class="fa fa-truck fs-5 text-primary"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="vehicleAllocModalLabel" style="color: #0369a1 !important; font-size: 1.15rem;">
                            Allocate Vehicle to Order
                        </h5>
                        <div class="small fw-semibold" style="font-size: 11.5px; color: #0284c7 !important;">Pre-assign vehicle & driver for sales order delivery</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <form id="vehicleAllocForm">
                    <input type="hidden" id="vam_projectid" name="projectid">
                    <input type="hidden" id="vam_sono" name="sono">
                    <input type="hidden" id="vam_psono" name="psono">
                    <input type="hidden" id="vam_clientid" name="clientid">
                    <input type="hidden" id="vam_total_plants" name="total_allocated_plants">
                    <input type="hidden" id="vam_existing_trip_id" name="existing_trip_id">

                    <!-- Order Snapshot Box (Client, Product & Order Load) -->
                    <div class="card border-0 shadow-sm rounded-3 mb-3 bg-white">
                        <div class="card-body p-3">
                            <div class="row g-2 align-items-center">
                                <div class="col-md-4 order-snapshot-col-border">
                                    <div class="text-muted small fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">CLIENT & DESTINATION</div>
                                    <div class="fw-bold text-dark fs-6 text-truncate" id="vam_display_client">-</div>
                                    <div class="text-secondary small text-truncate" id="vam_display_destination">-</div>
                                </div>
                                <div class="col-md-4 order-snapshot-col-border ps-md-3">
                                    <div class="text-muted small fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">PLANT / PRODUCT NAME</div>
                                    <div class="fw-bold text-dark text-truncate" style="font-size: 13px;" id="vam_display_product">
                                        <i class="fa fa-seedling text-success me-1"></i><span>-</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-1.5 mt-0.5 flex-wrap" id="vam_display_product_meta">
                                        <span class="text-secondary small">-</span>
                                    </div>
                                </div>
                                <div class="col-md-4 ps-md-3">
                                    <div class="text-muted small fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">SALES ORDER & LOAD</div>
                                    <div class="fw-bold text-primary font-monospace" id="vam_display_sono">-</div>
                                    <div class="fw-bold text-success fs-6" id="vam_display_qty">-</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Allocation Balance Tracker (Live Multi-Vehicle Sync) -->
                    <div class="card border-0 shadow-sm rounded-3 mb-3 bg-white" id="vamBalanceCard">
                        <div class="card-body p-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle py-1.5 px-2" id="vamSummaryOrderTotalBox">
                                    <i class="fa fa-boxes-stacked me-1"></i> Order Total: <b id="vamSummaryTotalPlants">0</b> plants
                                </span>
                                <span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning-subtle py-1.5 px-2" id="vamSummaryDispatchedBox" style="display: none;">
                                    <i class="fa fa-truck-fast me-1"></i> Dispatched: <b id="vamSummaryDispatchedPlants">0</b> plants
                                </span>
                                <span class="badge bg-info bg-opacity-10 text-info-emphasis border border-info-subtle py-1.5 px-2" id="vamSummaryToAllocateBox" style="display: none;">
                                    <i class="fa fa-clipboard-check me-1"></i> To Allocate: <b id="vamSummaryToAllocatePlants">0</b> plants
                                </span>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle py-1.5 px-2">
                                    <i class="fa fa-truck-ramp-box me-1"></i> Allocated: <b id="vamSummaryAllocatedPlants">0</b> plants
                                </span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span id="vamBalanceStatusBadge" class="badge bg-success py-1.5 px-2.5">
                                    <i class="fa fa-check-circle me-1"></i> Balanced (100% Assigned)
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Dynamic Vehicle Groups Container -->
                    <div id="vamVehicleGroupsContainer" class="d-flex flex-column gap-3 mb-3">
                        <!-- Populated dynamically by vehicle_allocation.js -->
                    </div>

                    <!-- Add Another Vehicle Group Action Bar (Hidden: load splitting is handled via remaining plants button) -->
                    <div class="d-none justify-content-between align-items-center flex-wrap gap-2 p-2 px-3 mb-3 bg-white rounded-3 shadow-sm border border-dashed" id="vamAddVehicleActionBar" style="display: none !important;">
                        <div>
                            <div class="fw-bold text-dark small"><i class="fa fa-truck-moving text-primary me-1"></i> Multiple Vehicle Dispatch</div>
                            <div class="text-muted small" style="font-size: 11px;">If order exceeds single vehicle capacity, add another vehicle to split the load.</div>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm px-3 shadow-sm rounded-pill fw-bold" id="btnAddVehicleGroup">
                            <i class="fa fa-plus-circle me-1"></i> Add Another Vehicle / Group
                        </button>
                    </div>

                    <!-- Freight Charge & Delivery Route Details Card (Hidden until vehicle details are selected) -->
                    <div class="card border-0 shadow-sm rounded-3 mb-3 bg-white" id="vamFreightRouteSection" style="display: none;">
                        <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle p-1.5 rounded-circle">
                                    <i class="fa fa-truck-ramp-box"></i>
                                </span>
                                <div>
                                    <h6 class="fw-bold text-dark mb-0" style="font-size: 13.5px;">Freight Charge Details (Grouped by Address &amp; Date)</h6>
                                    <div class="text-muted small" style="font-size: 11px;">Invoice route distance, discount rules and payable freight breakdown</div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="small fw-bold text-muted text-nowrap"><i class="fa fa-truck text-primary me-1"></i>Delivery Type:</span>
                                <div id="vam_route_delivery_badge">
                                    <span class="badge bg-primary text-white px-3 py-1.5 rounded-2 fw-bold shadow-2xs" style="font-size: 11.5px;">
                                        <i class="fa fa-truck me-1"></i> Farm Delivery
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-2 p-md-3">
                            <!-- Rate Calculation Notice (when Rate/KM applies to onward + return journeys) -->
                            <div id="vam_spanratedbl_alert" class="alert alert-info py-2 px-3 mb-2.5 small fw-bold d-none border-info-subtle bg-info-subtle text-info-emphasis rounded-3 shadow-2xs" style="font-size: 11.5px;">
                                <i class="fa fa-info-circle me-1 text-primary"></i> <span id="vam_spanratedbl">Freight is calculated for both onward and return journeys.</span>
                            </div>

                            <!-- Freight Eligibility Rules / Discount Banner Container (if discount applied) -->
                            <div id="vam_f_discount_banner" class="mb-2.5" style="display: none;">
                                <div class="p-2.5 px-3 rounded-3 border border-success-subtle bg-success bg-opacity-10 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fa fa-badge-check text-success fs-5"></i>
                                        <div>
                                            <div class="fw-bold text-success small" id="vam_f_discount_title">Freight Discount Plan Applied</div>
                                            <div class="text-secondary small" style="font-size: 11px;" id="vam_f_discount_desc">-</div>
                                        </div>
                                    </div>
                                    <span class="badge bg-success text-white px-2.5 py-1 fw-bold" id="vam_f_discount_chip">Discount Applied</span>
                                </div>
                            </div>

                            <!-- Grouped Freight Table matching Step 2 of timetable_finaldispatch.php -->
                            <div class="table-responsive rounded-3 border bg-white mb-2 shadow-2xs">
                                <table class="table table-hover table-bordered align-middle mb-0" id="vamFreightTable" style="font-size: 0.85rem;">
                                    <thead class="bg-light text-uppercase fw-bold" style="font-size: 0.74rem; letter-spacing: 0.5px;">
                                        <tr class="text-dark">
                                            <th style="min-width: 220px;">CROP NAME / DESCRIPTION</th>
                                            <th style="width: 120px;" class="text-center">DELIVERY DATE</th>
                                            <th style="min-width: 240px;">DELIVERY LOCATION</th>
                                            <th style="width: 105px;" class="text-center">DISTANCE (KM)</th>
                                            <th style="min-width: 135px; width: 145px;" class="text-center">FREIGHT AMOUNT</th>
                                            <th style="min-width: 200px; width: 220px;" class="text-center">DISCOUNT (% &amp; AMOUNT)</th>
                                            <th style="min-width: 135px; width: 145px;" class="text-center">PAYABLE FREIGHT</th>
                                            <th style="width: 140px; min-width: 140px;" class="text-center">VEHICLE</th>
                                        </tr>
                                    </thead>
                                    <tbody id="vamFreightTableBody">
                                        <!-- Dynamically Rendered by JavaScript -->
                                    </tbody>
                                    <tfoot class="bg-light fw-bold" style="font-size: 0.85rem;">
                                        <tr>
                                            <td colspan="4" class="text-end pe-3">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="fw-bold text-primary small d-none" id="vam_spanratedbl_tfoot"></span>
                                                    <span class="fw-bold text-dark ms-auto">Grand Total Freight:</span>
                                                </div>
                                            </td>
                                            <td id="vam_tbl_base_freight_display" class="fw-bold font-monospace text-center">0.00</td>
                                            <td id="vam_tbl_disc_freight_display" class="text-success font-monospace text-center">0.00</td>
                                            <td id="vam_tbl_payable_freight_display" class="text-primary font-monospace fw-bold text-center">0.00</td>
                                            <td class="text-center"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <!-- Route & Delivery Remarks Box -->
                            <div id="vam_route_remarks_box" class="p-2 px-3 rounded-2 bg-light border text-muted small mt-2" style="font-size: 11.5px; display: none;">
                                <i class="fa fa-comment-dots text-primary me-1"></i> <b class="text-dark">Delivery Address Remarks:</b> <span id="vam_route_remarks_text"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Common Dispatch Schedule & Route Remarks (Order Level) -->
                    <div class="card border-0 shadow-sm rounded-3 mb-3 bg-white">
                        <div class="card-body p-3">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label text-dark small fw-bold mb-1" for="vam_scheduled_dispatch_date">
                                        <i class="fa fa-calendar-alt text-primary me-1"></i> Scheduled Dispatch Date <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" class="form-control form-control-sm" id="vam_scheduled_dispatch_date" name="scheduled_dispatch_date" required>
                                    <div class="text-muted small mt-1" style="font-size: 11px;">Applies to all assigned vehicle trips for this dispatch.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold mb-1" for="vam_remarks">
                                        <i class="fa fa-comment-dots text-secondary me-1"></i> Overall Dispatch / Route Remarks
                                    </label>
                                    <input type="text" class="form-control form-control-sm" id="vam_remarks" name="remarks" placeholder="Optional notes for dispatch team">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Global Datalists for Driver and Transporter Suggestions -->
                    <datalist id="vamTransporterDataList"></datalist>
                    <datalist id="vamDriverDataList"></datalist>
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

<!-- Modal 1: Location Coordinate & Road Distance (Google Maps / GPS) -->
<div class="modal fade" id="adddeliveryaddressModal" tabindex="-1" role="dialog" aria-labelledby="adddeliveryaddressModalLabel" aria-hidden="true" style="z-index: 1075;">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 95vw; margin: 10px auto;">
        <div class="modal-content shadow-lg border-0" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header bg-primary text-white py-2 px-3 d-flex justify-content-between align-items-center">
                <h5 class="modal-title fw-bold fs-15 text-white mb-0" id="adddeliveryaddressModalLabel">
                    <i class="fa fa-map-marker-alt me-2"></i> Update Delivery Address &amp; Calculate Road Distance
                </h5>
                <button type="button" class="btn btn-sm btn-outline-light close-modal-btn d-flex align-items-center justify-content-center" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close" style="width: 32px; height: 32px; border-radius: 6px; padding: 0; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.4); color: #ffffff;" title="Close Modal">
                    <i class="fa fa-times" style="font-size: 16px; color: #ffffff;"></i>
                </button>
            </div>
            <div class="modal-body p-2" style="min-height: 640px; overflow: hidden;">
                <div class="card p-2 mb-2 border-0 bg-light shadow-sm">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-6">
                            <label class="form-label fw-bold mb-1 small text-dark"><i class="fa fa-crosshairs text-primary me-1"></i>Coordinates (Latitude, Longitude):</label>
                            <input type="text" class="form-control form-control-sm border-primary-subtle shadow-sm" name="latlong" id="latlonginput" placeholder="e.g. 23.122173, 83.199275" />
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold mb-1 small text-muted">Latitude:</label>
                            <input type="text" class="form-control form-control-sm bg-white" name="latinput" id="latinput" placeholder="Lat" />
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold mb-1 small text-muted">Longitude:</label>
                            <input type="text" class="form-control form-control-sm bg-white" name="longinput" id="longinput" placeholder="Long" />
                        </div>
                    </div>
                </div>
                <div id="appenddeliveryaddressdiv" style="min-height: 620px; overflow: hidden;"></div> 
            </div>
        </div>
    </div>
</div>

<!-- Modal 2: Manual Delivery Address & Distance Entry (Exact same as timetable_finaldispatch.php) -->
<div class="modal fade" id="manualDeliveryAddressModal" tabindex="-1" role="dialog" aria-labelledby="manualDeliveryAddressModalLabel" aria-hidden="true" style="z-index: 1075;">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 580px; margin: 20px auto;">
    <div class="modal-content shadow-lg border-0" style="border-radius: 14px; overflow: hidden;">
      <div class="modal-header bg-primary text-white py-2.5 px-3 d-flex justify-content-between align-items-center">
        <h5 class="modal-title fw-bold fs-15 text-white mb-0" id="manualDeliveryAddressModalLabel">
          <i class="fa fa-map-marked-alt me-2"></i> Update Manual Delivery Address &amp; Distance
        </h5>
        <button type="button" class="btn btn-sm btn-outline-light close-modal-btn d-flex align-items-center justify-content-center" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close" style="width: 30px; height: 30px; border-radius: 6px; padding: 0; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.4); color: #ffffff;" title="Close Modal">
          <i class="fa fa-times" style="font-size: 15px; color: #ffffff;"></i>
        </button>
      </div>
      <form id="manualDeliveryAddressForm" autocomplete="off">
        <div class="modal-body p-3 bg-light">
          <input type="hidden" id="manual_addr_group_index" value="0">
          <input type="hidden" id="manual_addr_psono" value="">
          <input type="hidden" id="manual_addr_clientid" value="">
          <input type="hidden" id="manual_addr_projectid" value="">

          <div class="alert alert-info py-2 px-3 mb-2.5 small d-flex align-items-center gap-2 border-0 shadow-xs" style="border-radius: 8px;">
            <i class="fa fa-info-circle fs-6 text-primary"></i>
            <div>Fill in State, District, Village, and Approximate Distance to update <b>krf_deliveryaddress_location</b> and calculate freight.</div>
          </div>

          <div class="card border-0 shadow-sm p-3 mb-0" style="border-radius: 10px;">
            <div class="row g-2.5">
              <!-- 1. State (Master) -->
              <div class="col-md-6">
                <label class="form-label fw-bold small text-dark mb-1">State <span class="text-danger">*</span></label>
                <select class="form-select form-select-sm fw-bold border-secondary-subtle" id="manual_addr_state" name="state" required>
                  <option value="">-- Loading States... --</option>
                </select>
              </div>

              <!-- 2. District (Master) -->
              <div class="col-md-6">
                <label class="form-label fw-bold small text-dark mb-1">District <span class="text-danger">*</span></label>
                <select class="form-select form-select-sm fw-bold border-secondary-subtle" id="manual_addr_district" name="district" required>
                  <option value="">-- Select District --</option>
                </select>
              </div>

              <!-- 3. Block / Tahsil -->
              <div class="col-md-6">
                <label class="form-label fw-bold small text-dark mb-1">Block / Tahsil</label>
                <input type="text" class="form-control form-control-sm" id="manual_addr_block" name="block" placeholder="Enter Block or Tahsil">
              </div>

              <!-- 4. City / Village -->
              <div class="col-md-6">
                <label class="form-label fw-bold small text-dark mb-1">City / Village <span class="text-danger">*</span></label>
                <input type="text" class="form-control form-control-sm fw-bold" id="manual_addr_city" name="city" placeholder="Enter City or Village" required>
              </div>

              <!-- 5. Approx Distance (KM) -->
              <div class="col-md-7">
                <label class="form-label fw-bold small text-dark mb-1">Approx Distance (KM) <span class="text-danger">*</span></label>
                <div class="input-group input-group-sm">
                  <input type="number" step="0.1" min="0" class="form-control form-control-sm fw-bold text-primary fs-6" id="manual_addr_distance" name="distance_km" placeholder="e.g. 85" required>
                  <span class="input-group-text bg-white fw-bold text-muted">KM</span>
                </div>
              </div>

              <!-- Pincode (Optional) -->
              <div class="col-md-5">
                <label class="form-label fw-bold small text-dark mb-1">Pincode</label>
                <input type="text" class="form-control form-control-sm" id="manual_addr_pincode" name="pincode" placeholder="e.g. 492001" maxlength="6">
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer py-2 px-3 bg-white border-top d-flex justify-content-between">
          <button type="button" class="btn btn-sm btn-secondary px-3" data-bs-dismiss="modal" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-sm btn-success fw-bold px-3 shadow-xs" id="btn_save_manual_address">
            <i class="fa fa-check-circle me-1"></i> Save &amp; Refresh Distance
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include('../../footer.php'); ?>

<script>
    window.VA_PROJECT_ID = <?= json_encode($projectid ?? 0); ?>;
    window.VA_LOGIN_ID = <?= json_encode($loginid ?? ''); ?>;
    window.VA_COMPANY_LOGO = <?= json_encode($krfLogoBase64 ?? ''); ?>;
</script>
<script src="../js/vehicle_calculator.js?v=<?php echo time(); ?>"></script>
<script src="js/vehicle_allocation.js?v=<?php echo time(); ?>"></script>