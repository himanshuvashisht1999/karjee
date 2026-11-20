<?php
include('../header.php');
date_default_timezone_set("Asia/Calcutta");

$logoPath = __DIR__ . '/../img/logo/logo.png';
$krfLogoBase64 = '';
if (file_exists($logoPath)) {
    $logoData = file_get_contents($logoPath);
    $krfLogoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
}
?>
<!-- Font Awesome 6 Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<!-- Module Dedicated CSS -->
<link href="css/order_tracking.css?v=<?php echo time(); ?>" rel="stylesheet" />

<!-- jQuery & Third-Party Libraries -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="container-fluid my-4" data-projectid="<?php echo htmlspecialchars($projectid ?? '0'); ?>" data-loginid="<?php echo htmlspecialchars($loginid ?? ''); ?>" data-loginname="<?php echo htmlspecialchars($_SESSION['username'] ?? $_SESSION['loginname'] ?? $loginname ?? 'Admin'); ?>">
    
    <!-- 1. Header Hero Banner -->
    <div class="header-hero-card mb-4 no-print">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-white text-teal d-flex align-items-center justify-content-center shadow-sm" style="width: 50px; height: 50px; color: #0d9488;">
                    <i class="fa fa-receipt fs-3"></i>
                </div>
                <div>
                    <h3 class="mb-0 fw-bold text-white" style="font-size: 22px;">
                        Customer 360° Payment & Order Lifecycle Tracker
                    </h3>
                    <div class="text-white-50 small mt-0.5" style="font-size: 12.5px;">
                        Search client to track orders, advance payments, sowing, and polyhouse allocations
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Search Box Card -->
    <div class="search-hero-box mb-4 no-print">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-md-5 col-lg-4">
                <label class="form-label fw-bold text-secondary small text-uppercase mb-1">
                    <i class="fa fa-user-check text-success me-1"></i> Quick Client Autocomplete
                </label>
                <select id="clientSearchSelect" class="form-select select2-client" style="width: 100%;">
                    <option value="">Search Client by Mobile / Name / ID...</option>
                </select>
            </div>
            <div class="col-12 col-md-5 col-lg-5">
                <label class="form-label fw-bold text-secondary small text-uppercase mb-1">
                    <i class="fa fa-phone text-primary me-1"></i> Or Enter Client Number / Order ID (PSONO)
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa fa-search"></i></span>
                    <input type="text" id="clientSearchInput" class="form-control border-start-0" placeholder="e.g. 9876543210 or Mukesh or KF-PS-00123" style="height: 44px; font-size: 14px; font-weight: 500;">
                </div>
            </div>
            <div class="col-12 col-md-2 col-lg-3 d-flex gap-2 align-items-end mt-auto" style="height: 72px;">
                <button type="button" class="btn btn-primary fw-bold flex-grow-1 shadow-sm" id="btnSearchClient" style="height: 44px; background-color: #0d9488; border-color: #0d9488;">
                    <i class="fa fa-magnifying-glass me-1.5"></i> Search Client
                </button>
                <button type="button" class="btn btn-outline-secondary px-3" id="btnResetSearch" title="Reset Search" style="height: 44px;">
                    <i class="fa fa-rotate-left"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- 3. Loading State -->
    <div id="searchLoadingState" class="text-center py-5 my-4" style="display: none;">
        <div class="spinner-border text-teal mb-3" style="width: 3.5rem; height: 3.5rem; color: #0d9488;" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <h5 class="fw-bold text-dark">Fetching Client History...</h5>
        <p class="text-muted small">Loading provisional orders, advance payments, and polyhouse allocations...</p>
    </div>

    <!-- 4. Empty State (Before Search) -->
    <div id="emptySearchState" class="card border-0 shadow-sm text-center py-5 my-3" style="border-radius: 12px; background: #ffffff;">
        <div class="card-body p-5">
            <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                <i class="fa fa-address-book fa-3x text-muted opacity-50"></i>
            </div>
            <h4 class="fw-bold text-dark">Search Client to View History</h4>
            <p class="text-muted mx-auto mb-4" style="max-width: 500px; font-size: 13.5px;">
                Enter client mobile number, name, or order ID to view all orders, advance payment records, sowing details, and polyhouse allocations.
            </p>
        </div>
    </div>

    <!-- 5. Client History Results Container (Populated via JS) -->
    <div id="clientResultsContainer" style="display: none;">

        <!-- Client Profile Header Banner -->
        <div class="client-profile-banner">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="client-avatar-badge" id="clientAvatarInitial">
                        C
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h4 class="mb-0 fw-bold text-dark" id="clientNameDisplay">Client Name</h4>
                            <span class="badge bg-light text-dark border font-monospace" id="clientIdDisplay">#0</span>
                        </div>
                        <div class="text-muted small mt-1 d-flex align-items-center gap-3 flex-wrap" style="font-size: 13px;">
                            <span><i class="fa fa-phone text-primary me-1"></i> <b id="clientMobileDisplay">-</b></span>
                            <span><i class="fa fa-map-marker-alt text-danger me-1"></i> <span id="clientLocationDisplay">-</span></span>
                        </div>
                        <div class="text-secondary small mt-0.5" id="clientAddressDisplay" style="font-size: 11.5px; display: none;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lifetime Summary KPIs -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3 col-lg-3">
                <div class="kpi-metric-card kpi-orders">
                    <span class="kpi-label">Lifetime Orders</span>
                    <h3 class="kpi-number mb-0" id="kpiTotalOrders">0</h3>
                    <span class="kpi-sub text-muted">Provisional & Sales Orders</span>
                </div>
            </div>
            <div class="col-6 col-md-3 col-lg-3">
                <div class="kpi-metric-card kpi-billed">
                    <span class="kpi-label">Total Order Value</span>
                    <h3 class="kpi-number mb-0" id="kpiTotalBilled">₹0</h3>
                    <span class="kpi-sub text-muted">Total Gross Booked</span>
                </div>
            </div>
            <div class="col-6 col-md-3 col-lg-3">
                <div class="kpi-metric-card kpi-paid">
                    <span class="kpi-label">Total Advance Received</span>
                    <h3 class="kpi-number mb-0" id="kpiTotalPaid">₹0</h3>
                    <span class="kpi-sub text-muted">Logged in Advance Payments</span>
                </div>
            </div>
            <div class="col-6 col-md-3 col-lg-3">
                <div class="kpi-metric-card kpi-balance">
                    <span class="kpi-label">Balance Outstanding</span>
                    <h3 class="kpi-number mb-0" id="kpiBalanceDue">₹0</h3>
                    <span class="kpi-sub text-muted">Lifetime Remaining Due</span>
                </div>
            </div>
        </div>

        <!-- Plants Lifecycle KPI Sub-Banner -->
        <div class="card mb-4 border bg-white shadow-sm" style="border-radius: 10px;">
            <div class="card-body p-3">
                <div class="row text-center g-2 align-items-center">
                    <div class="col-6 col-md-3 border-end">
                        <span class="text-secondary small fw-bold text-uppercase" style="font-size: 11px;">1. Plants Ordered</span>
                        <div class="fs-4 fw-bold text-primary mt-0.5" id="kpiPlantsOrdered">0</div>
                    </div>
                    <div class="col-6 col-md-3 border-end">
                        <span class="text-secondary small fw-bold text-uppercase" style="font-size: 11px;">2. Plants Sown</span>
                        <div class="fs-4 fw-bold text-success mt-0.5" id="kpiPlantsSown">0</div>
                    </div>
                    <div class="col-6 col-md-3 border-end">
                        <span class="text-secondary small fw-bold text-uppercase" style="font-size: 11px;">3. Allocated in Polyhouse</span>
                        <div class="fs-4 fw-bold" style="color: #7c3aed;" id="kpiPlantsAllocated">0</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="text-secondary small fw-bold text-uppercase" style="font-size: 11px;">4. Delivered / Dispatched</span>
                        <div class="fs-4 fw-bold text-dark mt-0.5" id="kpiPlantsDispatched">0</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 6. Provisional Orders Data Table (Order-Wise Rows) -->
        <div class="card border shadow-sm mb-4" style="border-radius: 12px; overflow: hidden;">
            <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa fa-list-ol fs-5" style="color: #0d9488;"></i>
                    <h5 class="mb-0 fw-bold text-dark">Provisional Orders & Rest Amount Summary</h5>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap no-print">
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-primary btn-order-filter active" data-filter="all">All Orders</button>
                        <button type="button" class="btn btn-outline-secondary btn-order-filter" data-filter="confirmed">Confirmed</button>
                        <button type="button" class="btn btn-outline-secondary btn-order-filter" data-filter="pending">Pending</button>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered mb-0 align-middle order-wise-table" id="provisionalOrdersTable" style="font-size: 12.5px;">
                        <thead class="table-light">
                            <tr class="text-nowrap text-secondary text-uppercase" style="font-size: 11px; letter-spacing: 0.4px;">
                                <th class="text-center" style="width: 40px;">#</th>
                                <th>Provisional Order No</th>
                                <th>Dates</th>
                                <th>Crops & Order Qty</th>
                                <th class="text-end">Total Amount (₹)</th>
                                <th class="text-end">Advance Paid (₹)</th>
                                <th class="text-end bg-light-danger" style="background-color: #fff1f2 !important; color: #be123c !important;">Rest Amount (₹)</th>
                                <th class="text-center">Order Status</th>
                            </tr>
                        </thead>
                        <tbody id="provisionalOrdersTableBody">
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">Search client to view provisional orders.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal: Receipt Viewer -->
<div class="modal fade" id="receiptViewerModal" tabindex="-1" aria-labelledby="receiptViewerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header py-2.5 px-3 bg-dark text-white d-flex justify-content-between align-items-center">
                <h6 class="modal-title fw-bold mb-0 text-white" id="receiptViewerModalLabel"><i class="fa fa-file-invoice me-1 text-warning"></i> Payment Receipt Document</h6>
                <button type="button" class="btn btn-sm btn-outline-light px-2" data-bs-dismiss="modal" data-dismiss="modal"><i class="fa fa-times"></i></button>
            </div>
            <div class="modal-body text-center p-3 bg-light">
                <img id="receiptViewerImage" src="" alt="Payment Receipt" class="img-fluid rounded shadow-sm" style="max-height: 75vh; object-fit: contain;">
            </div>
            <div class="modal-footer py-2 px-3 bg-light border-top">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php include('../footer.php'); ?>
<script type="text/javascript" src="<?php echo $prefix; ?>krf-orderandsales/js/pjs_function.js"></script>
<!-- Module Dedicated JavaScript (Inlined to completely eliminate browser caching) -->
<script type="text/javascript">
<?php include __DIR__ . '/js/order_tracking.js'; ?>
</script>
