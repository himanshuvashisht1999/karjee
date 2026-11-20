<?php require_once dirname(__DIR__) . "/connection.php"; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order & Inventory Management</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
        rel="stylesheet" />

    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            --accent-green: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --accent-blue: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            --border-radius: 14px;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #f1f5f9;
            color: #1e293b;
            font-size: 0.88rem;
            letter-spacing: -0.01em;
            padding-bottom: 3rem;
        }

        .font-monospace, .sku-pill, .po-num-pill, .stock-sku, .stock-num {
            font-family: 'JetBrains Mono', monospace !important;
        }

        .table-card {
            background: #ffffff;
            border-radius: 14px;
            box-shadow: var(--card-shadow);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .table-header-dark {
            background: #1e293b;
            color: #ffffff;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .form-label {
            font-weight: 700 !important;
            font-size: 0.75rem !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
            color: #475569 !important;
            margin-bottom: 0.35rem !important;
        }

        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            font-size: 0.88rem;
            padding: 0.55rem 0.85rem;
            color: #1e293b;
            background-color: #ffffff;
        }

        .form-control:focus, .form-select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .form-select {
            text-overflow: ellipsis !important;
            overflow: hidden !important;
            white-space: nowrap !important;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            white-space: nowrap !important;
        }

        /* Table header & body text styling */
        .table thead th {
            font-size: 0.76rem !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.4px !important;
            color: #475569 !important;
            background-color: #f8fafc !important;
            padding: 0.55rem 0.5rem !important;
            vertical-align: middle !important;
            white-space: nowrap !important;
        }

        .table tbody td {
            font-size: 0.82rem !important;
            padding: 0.45rem 0.5rem !important;
            vertical-align: middle !important;
        }

        .table tbody .form-control {
            font-size: 0.8rem !important;
            padding: 0.3rem 0.45rem !important;
            height: 32px !important;
            min-height: 32px !important;
            border-radius: 6px !important;
        }

        .table tbody .form-select {
            font-size: 0.8rem !important;
            padding: 0.3rem 1.75rem 0.3rem 0.45rem !important;
            height: 32px !important;
            min-height: 32px !important;
            border-radius: 6px !important;
            text-overflow: ellipsis !important;
            overflow: hidden !important;
            white-space: nowrap !important;
        }

        .table tbody .select2-container--bootstrap-5 .select2-selection {
            min-height: 32px !important;
            height: 32px !important;
            font-size: 0.8rem !important;
            padding: 0.2rem 1.75rem 0.2rem 0.45rem !important;
            border-radius: 6px !important;
            position: relative !important;
        }

        .table tbody .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            font-size: 0.8rem !important;
            line-height: 24px !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            white-space: nowrap !important;
            display: block !important;
            max-width: 100% !important;
        }

        .table tbody .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
            position: absolute !important;
            top: 50% !important;
            right: 6px !important;
            transform: translateY(-50%) !important;
            width: 20px !important;
            height: 20px !important;
        }

        .uom-badge {
            font-size: 0.65rem !important;
            padding: 0.15rem 0.35rem !important;
            border-radius: 4px !important;
            background-color: #f8fafc !important;
            color: #475569 !important;
            border: 1px solid #cbd5e1 !important;
            display: inline-block !important;
            line-height: 1.2 !important;
            margin-top: 2px !important;
            margin-bottom: 2px !important;
        }

        .line-total, .rec-line-total {
            font-size: 0.88rem !important;
            font-weight: 700 !important;
        }

        .line-tax-detail, .rec-line-tax-detail {
            font-size: 0.68rem !important;
            color: #64748b !important;
            white-space: nowrap !important;
        }

        .nav-tabs .nav-link.active {
            color: #2563eb;
            border-top: 3px solid #2563eb;
            font-weight: bold;
        }

        .modal-header-solid-blue {
            background-color: #2563eb;
            color: #ffffff;
            padding: 1rem 1.5rem;
        }

        .modal-header-solid-blue .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.9;
        }

        .po-stat-card {
            cursor: pointer;
            transition: all 0.22s ease-in-out;
            user-select: none;
            position: relative;
        }
        .po-stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08) !important;
        }
        .po-stat-card.active-card {
            transform: translateY(-2px);
            box-shadow: 0 0 0 2.5px #3b82f6, 0 10px 20px rgba(59, 130, 246, 0.15) !important;
            background: #f8fafc !important;
        }
        .po-stat-card.active-card.border-warning {
            box-shadow: 0 0 0 2.5px #f59e0b, 0 10px 20px rgba(245, 158, 11, 0.15) !important;
        }
        .po-stat-card.active-card.border-success {
            box-shadow: 0 0 0 2.5px #10b981, 0 10px 20px rgba(16, 185, 129, 0.15) !important;
        }
        .po-stat-card.active-card.border-danger {
            box-shadow: 0 0 0 2.5px #ef4444, 0 10px 20px rgba(239, 68, 68, 0.15) !important;
        }
        .po-stat-card.active-card.border-primary {
            box-shadow: 0 0 0 2.5px #3b82f6, 0 10px 20px rgba(59, 130, 246, 0.15) !important;
        }
    </style>
</head>

<body>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="text-primary"><i class="fas fa-boxes me-2"></i> Purchase Order & Inventory System</h4>
            <button class="btn btn-outline-secondary btn-sm" onclick="location.reload()"><i class="fas fa-sync"></i>
                Refresh</button>
        </div>

        <ul class="nav nav-tabs mb-4" id="mainTab" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button"><i class="fas fa-list me-1"></i> Purchase Orders</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="create-tab" data-bs-toggle="tab" data-bs-target="#create" type="button"><i class="fas fa-plus-circle me-1"></i> Create PO</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="transfers-tab" data-bs-toggle="tab" data-bs-target="#transfers" type="button"><i class="fas fa-exchange-alt me-1"></i> Inventory Transfers</button>
            </li>
        </ul>

        <div class="tab-content">
            <!-- Tab 1: Orders List -->
            <div class="tab-pane fade show active" id="orders">
                <!-- KPI Stat Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-12 col-sm-6 col-lg">
                        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-primary border-4 po-stat-card" data-status="" onclick="setPoStatusFilter('')" title="Click to view All Orders">
                            <span class="text-muted small text-uppercase font-monospace fw-bold">Total Orders</span>
                            <h3 class="fw-bold text-dark mb-0 mt-1" id="statTotalOrders">0</h3>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg">
                        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-warning border-4 po-stat-card active-card" data-status="Draft" onclick="setPoStatusFilter('Draft')" title="Click to view Draft Orders">
                            <span class="text-muted small text-uppercase font-monospace fw-bold">Draft Orders</span>
                            <h3 class="fw-bold text-warning mb-0 mt-1" id="statDraftOrders">0</h3>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg">
                        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-success border-4 po-stat-card" data-status="Received" onclick="setPoStatusFilter('Received')" title="Click to view Received Orders">
                            <span class="text-muted small text-uppercase font-monospace fw-bold">Received Orders</span>
                            <h3 class="fw-bold text-success mb-0 mt-1" id="statReceivedOrders">0</h3>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg">
                        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-danger border-4 po-stat-card" data-status="Cancelled" onclick="setPoStatusFilter('Cancelled')" title="Click to view Cancelled Orders">
                            <span class="text-muted small text-uppercase font-monospace fw-bold">Cancelled Orders</span>
                            <h3 class="fw-bold text-danger mb-0 mt-1" id="statCancelledOrders">0</h3>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg">
                        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-info border-4 po-stat-card" data-status="" onclick="setPoStatusFilter('')" title="Total Order Value">
                            <span class="text-muted small text-uppercase font-monospace fw-bold">Total Order Value</span>
                            <h3 class="fw-bold text-primary mb-0 mt-1" id="statTotalValue">₹0.00</h3>
                        </div>
                    </div>
                </div>

                <div class="table-card">
                    <div class="table-header-dark">
                        <span class="fw-bold fs-6"><i class="fas fa-list me-2"></i> Purchase Orders Directory</span>
                        <span class="badge bg-primary px-3 py-2 rounded-pill"><i class="fas fa-clipboard-list me-1"></i>Orders List</span>
                    </div>
                    <div class="p-4">
                        <div class="row g-3 mb-4 align-items-center">
                            <div class="col-md-4">
                                <select id="filterStatus" class="form-select" onchange="handleFilterStatusChange()">
                                    <option value="">All Status</option>
                                    <option value="Draft" selected>Draft</option>
                                    <option value="Received">Received</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="fas fa-search"></i></span>
                                    <input type="text" id="filterSearch" class="form-control" placeholder="Search PO Number or Vendor..." onkeyup="loadPOs()">
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="poMainTable">
                                <thead class="table-light" id="poTableHead">
                                    <tr>
                                        <th>PO Number</th>
                                        <th>Vendor / Supplier</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="poTableBody"></tbody>
                                <tfoot id="poTableFoot" class="table-light border-top border-2"></tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Create PO -->
            <div class="tab-pane fade" id="create">
                <div class="table-card">
                    <div class="table-header-dark">
                        <span class="fw-bold fs-6"><i class="fas fa-file-invoice-dollar me-2"></i> New Purchase Order Form</span>
                        <span class="badge bg-primary px-3 py-2 rounded-pill"><i class="fas fa-plus-circle me-1"></i>Create PO</span>
                    </div>
                    <div class="p-4">
                        <form id="createPOForm">
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label mb-0">Vendor / Supplier <span class="text-danger">*</span></label>
                                        <div class="d-flex align-items-center gap-1">
                                            <button type="button" class="btn btn-sm btn-light border text-secondary px-2 py-0" style="font-size: 0.75rem; height: 24px;" onclick="loadVendors()" title="Refresh Vendors"><i class="fas fa-sync-alt"></i></button>
                                            <button type="button" class="btn btn-sm btn-success px-2 py-0 fw-bold d-flex align-items-center gap-1" style="font-size: 0.75rem; height: 24px;" onclick="openAddSupplierModal()" title="Add New Supplier"><i class="fas fa-plus"></i> Add</button>
                                        </div>
                                    </div>
                                    <select class="form-select" id="poVendor" required></select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">PO Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="poDate" required value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Expected Delivery</label>
                                    <input type="date" class="form-control" id="poExpectedDate">
                                </div>
                            </div>

                            <div class="border-top pt-3 mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <h6 class="fw-bold text-primary mb-0"><i class="fas fa-boxes-packing me-2"></i>Order Items & Unit Variants</h6>
                                    <small class="text-muted d-block mt-1"><i class="fas fa-info-circle me-1 text-info"></i> Select items and pack units. Base & Purchase UOM definitions will be loaded automatically.</small>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm rounded-2 fw-semibold" onclick="addItemRow()">
                                    <i class="fas fa-plus me-1"></i> Add Item Line
                                </button>
                            </div>

                            <div class="table-responsive mb-4">
                                <table class="table table-bordered table-sm align-middle" id="poItemsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 24%;">Item / Product</th>
                                            <th style="width: 18%;">Unit / Pack Variant</th>
                                            <th style="width: 13%;">Order UOM Mode</th>
                                            <th style="width: 8%;" class="text-center">Quantity</th>
                                            <th style="width: 10%;" class="text-end">Unit Price (₹)</th>
                                            <th style="width: 8%;" class="text-center">GST %</th>
                                            <th style="width: 15%;" class="text-end">Total (₹)</th>
                                            <th style="width: 4%;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="itemRows">
                                        <!-- Items added dynamically -->
                                    </tbody>
                                </table>
                            </div>

                            <div class="row justify-content-end">
                                <div class="col-md-5 col-lg-4">
                                    <div class="p-3 bg-light border rounded-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-secondary small fw-bold">Taxable Amount:</span>
                                            <span class="fw-semibold text-dark" id="poTaxableDisplay">₹0.00</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-secondary small fw-bold">Total GST:</span>
                                            <span class="fw-semibold text-warning-emphasis" id="poGstDisplay">₹0.00</span>
                                        </div>
                                        <div class="d-flex justify-content-between border-top pt-2">
                                            <span class="text-primary fw-bold">Grand Total (₹):</span>
                                            <h4 class="text-primary fw-bold mb-0" id="poTotalDisplay">₹0.00</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4 border-secondary opacity-25">
                            <div class="text-end">
                                <button type="button" class="btn btn-success px-4 py-2 rounded-3 fw-bold shadow-sm" onclick="submitPO()">
                                    <i class="fas fa-check-circle me-2"></i>Create Purchase Order
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Inventory Transfers -->
            <div class="tab-pane fade" id="transfers">
                <!-- KPI Stat Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-primary border-4">
                            <span class="text-muted small text-uppercase font-monospace fw-bold">Total Requests</span>
                            <h3 class="fw-bold text-dark mb-0 mt-1" id="statTotalTransfers">0</h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-warning border-4">
                            <span class="text-muted small text-uppercase font-monospace fw-bold">Pending Requests</span>
                            <h3 class="fw-bold text-warning mb-0 mt-1" id="statPendingTransfers">0</h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-info border-4">
                            <span class="text-muted small text-uppercase font-monospace fw-bold">Dispatched Requests</span>
                            <h3 class="fw-bold text-info mb-0 mt-1" id="statDispatchedTransfers">0</h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-success border-4">
                            <span class="text-muted small text-uppercase font-monospace fw-bold">Completed Transfers</span>
                            <h3 class="fw-bold text-success mb-0 mt-1" id="statCompletedTransfers">0</h3>
                        </div>
                    </div>
                </div>

                <div class="table-card">
                    <div class="table-header-dark">
                        <span class="fw-bold fs-6"><i class="fas fa-exchange-alt me-2"></i> Inventory Transfers Directory</span>
                        <button class="btn btn-warning btn-sm fw-bold px-3 py-2 rounded-pill shadow-sm" onclick="openRequestModal()">
                            <i class="fas fa-plus-circle me-1"></i> New Transfer Request
                        </button>
                    </div>
                    <div class="p-4">
                        <div class="row g-3 mb-4 align-items-center">
                            <div class="col-md-4">
                                <select id="filterTransferStatus" class="form-select" onchange="loadTransfers()">
                                    <option value="">All Status</option>
                                    <option value="Pending">Pending</option>
                                    <option value="Dispatched">Dispatched</option>
                                    <option value="Completed">Completed</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="fas fa-search"></i></span>
                                    <input type="text" id="filterTransferSearch" class="form-control" placeholder="Search Request # or Route..." onkeyup="loadTransfers()">
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Req #</th>
                                        <th>Route</th>
                                        <th>Transfer Qty</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="transferTableBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Request Creation Modal -->
    <div class="modal fade" id="requestModal" tabindex="-1">
        <div class="modal-dialog modal-xl" style="max-width: 95%;">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header modal-header-solid-blue">
                    <h5 class="modal-title fw-bold"><i class="fas fa-truck-loading me-2"></i> New Stock Transfer Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="transferForm">
                        <div class="mb-2">
                            <label class="form-label small">From (Source)</label>
                            <select class="form-select form-select-sm" name="from_location" id="transferFrom"></select>
                            <small class="text-muted" style="font-size:11px">Stock will be checked from here.</small>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">To (Destination)</label>
                            <select class="form-select form-select-sm" name="to_location" id="transferTo"></select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Select Item</label>
                            <div class="position-relative">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                                    <input type="text" id="transferItemSearch" class="form-control form-control-sm"
                                        placeholder="Type item name or SKU to search & add..." autocomplete="off">
                                    <button class="btn btn-outline-secondary" type="button" id="btnClearTransferItemSearch" style="display: none;" title="Clear">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <!-- Autocomplete Suggestions List -->
                                <div id="transferItemSuggestions" class="list-group position-absolute w-100 shadow"
                                    style="display: none; max-height: 220px; overflow-y: auto; z-index: 1060; top: 100%; border: 1px solid #ced4da; border-radius: 0.375rem; background: #fff;">
                                </div>
                            </div>
                            <div class="text-muted small mt-1" style="font-size: 11px;">
                                <i class="fas fa-keyboard me-1"></i>Type to search, use <kbd>↑</kbd> <kbd>↓</kbd> arrows to navigate, and <kbd>Enter</kbd> to select item.
                            </div>
                        </div>

                        <h6 class="mt-3 border-bottom pb-1 small">Items to Transfer</h6>
                        <div id="transferItemsList" class="mb-3"
                            style="min-height: 100px; border: 1px dashed #ccc; padding: 5px;">
                            <small class="text-muted d-block text-center mt-4">No items added</small>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small">Remarks</label>
                            <textarea class="form-control form-control-sm" name="remarks" rows="2"></textarea>
                        </div>

                        <button type="button" class="btn btn-warning w-100" onclick="submitTransferRequest()">Submit
                            Request</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Transfer Details/Approve Modal -->
    <div class="modal fade" id="transferModal" tabindex="-1">
        <div class="modal-dialog modal-xl" style="max-width: 95%;">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header modal-header-solid-blue">
                    <h5 class="modal-title fw-bold"><i class="fas fa-exchange-alt me-2"></i> Transfer Request Details & Dispatch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="approveReqId">
                    <div class="alert alert-light border">
                        <strong id="trRoute"></strong><br>
                        <small class="text-muted" id="trDate"></small>
                    </div>

                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th>Requested</th>
                                <th>Approved Qty</th> <!-- Only editable if pending -->
                                <th>Stock Status</th>
                            </tr>
                        </thead>
                        <tbody id="trItemsBody"></tbody>
                    </table>

                    <div id="approveActionSection" class="text-end d-none">
                        <hr>
                        <button class="btn btn-success" onclick="submitApproveTransfer()">Confirm Approval & Transfer
                            Stock</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="viewPOModal" tabindex="-1">
        <div class="modal-dialog modal-xl" style="max-width: 95%;">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header modal-header-solid-blue">
                    <h5 class="modal-title fw-bold"><i class="fas fa-file-invoice-dollar me-2"></i> Purchase Order Details & Tracking</h5>
                    <div class="ms-auto d-flex align-items-center gap-2">
                        <a id="modalPoPdfBtn" href="#" target="_blank" class="btn btn-sm btn-light border text-danger fw-bold"><i class="fas fa-file-pdf me-1"></i>Generate PDF</a>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                </div>
                <div class="modal-body p-4">
                    <div class="card bg-light border-0 rounded-3 p-3 mb-4">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <span class="text-muted super-small text-uppercase font-monospace fw-bold d-block">PO Number</span>
                                <span class="fw-bold text-dark font-monospace fs-6" id="viewPoNum"></span>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted super-small text-uppercase font-monospace fw-bold d-block">Vendor / Supplier</span>
                                <span class="fw-semibold text-secondary" id="viewVendor"></span>
                            </div>
                            <div class="col-md-3">
                                <span class="text-muted super-small text-uppercase font-monospace fw-bold d-block">PO Date</span>
                                <span class="fw-medium text-dark" id="viewDate"></span>
                            </div>
                            <div class="col-md-2 text-end">
                                <span class="text-muted super-small text-uppercase font-monospace fw-bold d-block mb-1">Status</span>
                                <span id="viewStatus" class="badge px-3 py-2 rounded-pill fs-6"></span>
                            </div>
                        </div>
                    </div>

                    <div id="viewReceiptsContainer"></div>

                    <div class="table-responsive mb-3">
                        <table class="table table-bordered table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 32%;">Item / Product & Tracking Settings</th>
                                    <th style="width: 14%;">Ordered Qty (UOM)</th>
                                    <th style="width: 10%;">Unit Price (₹)</th>
                                    <th style="width: 11%;">Taxable (₹)</th>
                                    <th style="width: 14%;">GST</th>
                                    <th style="width: 12%;">Total Price (₹)</th>
                                    <th style="width: 7%;">Qty Rec'd</th>
                                </tr>
                            </thead>
                            <tbody id="viewItemsBody"></tbody>
                            <tfoot id="viewItemsFoot" class="table-light fw-bold"></tfoot>
                        </table>
                    </div>

                    <!-- Receipt Interface -->
                    <div id="receiveSection" class="d-none mt-4 p-3 bg-light border rounded shadow-sm">
                        <h6 class="text-primary mb-3 border-bottom pb-2 d-flex justify-content-between">
                            <span><i class="fas fa-file-invoice me-2"></i>Purchase Invoice & Receipt Details</span>
                            <span class="small text-muted">Step 2: Update Inventory</span>
                        </h6>
                        <form id="receiveForm">
                            <input type="hidden" id="receivePoId">

                            <div class="row g-2 mb-3">
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Invoice Number</label>
                                    <input type="text" class="form-control form-control-sm border-primary"
                                        id="recInvoiceNo" placeholder="Ref/Bill #">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Invoice Date</label>
                                    <input type="date" class="form-control form-control-sm border-primary"
                                        id="recInvoiceDate" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Location</label>
                                    <div id="receiveLocationContainer"></div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Received Date</label>
                                    <input type="date" class="form-control form-control-sm" id="recDate"
                                        value="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered table-sm align-middle text-center" id="receiveTable">
                                    <thead class="table-dark small">
                                        <tr>
                                            <th style="width: 30%;">Item / Product</th>
                                            <th style="width: 8%;">Ordered</th>
                                            <th style="width: 12%;">Receiving</th>
                                            <th style="width: 13%;">Rate (₹)</th>
                                            <th style="width: 12%;">GST %</th>
                                            <th style="width: 20%;">Total (₹)</th>
                                            <th style="width: 5%;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="receiveInputsBody" class="small"></tbody>
                                    <tfoot>
                                        <tr class="table-light">
                                            <td colspan="7" class="text-start">
                                                <button type="button" class="btn btn-link btn-sm text-decoration-none"
                                                    onclick="addReceiveRow()">
                                                    <i class="fas fa-plus-circle"></i> Add Item not in PO
                                                </button>
                                            </td>
                                        </tr>
                                        <tr class="fw-bold">
                                            <td colspan="5" class="text-end">Invoice Total:</td>
                                            <td id="recTotalDisplay" class="text-end">₹0.00</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <div class="alert alert-info py-2 small mb-3">
                                <i class="fas fa-info-circle me-1"></i>
                                Note: You can change the quantity, rate, or GST % to match your physical invoice. New inventory
                                will be added based on "Receiving" column.
                            </div>

                            <button type="button" class="btn btn-success w-100 fw-bold" onclick="submitReceive()">
                                <i class="fas fa-check-double me-2"></i>Confirm Receipt & Finalize Invoice
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Invoice Document Preview Modal -->
    <div class="modal fade" id="invoicePreviewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header modal-header-solid-blue">
                    <h5 class="modal-title fw-bold"><i class="fas fa-file-invoice me-2"></i> Purchase Invoice Document Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4" id="printableInvoiceArea">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <div>
                            <h4 class="fw-bold text-primary mb-1">PURCHASE INVOICE & GOODS RECEIPT</h4>
                            <span class="text-muted font-monospace super-small">KRF AMAN ACCOUNTING SYSTEM</span>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill fw-bold fs-6">VERIFIED INVOICE</span>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3 border">
                                <span class="text-muted super-small text-uppercase font-monospace fw-bold d-block mb-1">Vendor / Supplier</span>
                                <h6 class="fw-bold text-dark mb-1" id="invVendorName">-</h6>
                                <span class="super-small text-muted d-block font-monospace" id="invPoNum">PO: -</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3 border">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <span class="text-muted super-small text-uppercase font-monospace fw-bold d-block">Invoice #</span>
                                        <span class="fw-bold text-dark font-monospace" id="invNo">-</span>
                                    </div>
                                    <div class="col-6 text-end">
                                        <span class="text-muted super-small text-uppercase font-monospace fw-bold d-block">Invoice Date</span>
                                        <span class="fw-bold text-dark" id="invDate">-</span>
                                    </div>
                                    <div class="col-6 mt-2">
                                        <span class="text-muted super-small text-uppercase font-monospace fw-bold d-block">Received Date</span>
                                        <span class="fw-bold text-dark" id="invRecDate">-</span>
                                    </div>
                                    <div class="col-6 text-end mt-2">
                                        <span class="text-muted super-small text-uppercase font-monospace fw-bold d-block">Warehouse Location</span>
                                        <span class="fw-bold text-dark" id="invLocation">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive mb-4">
                        <table class="table table-bordered align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Item / Description</th>
                                    <th style="width: 14%;">Received Qty</th>
                                    <th style="width: 14%;">Unit Price (₹)</th>
                                    <th style="width: 14%;">Taxable (₹)</th>
                                    <th style="width: 16%;">GST</th>
                                    <th style="width: 16%;">Line Total (₹)</th>
                                </tr>
                            </thead>
                            <tbody id="invItemsBody"></tbody>
                            <tfoot id="invItemsFoot">
                                <tr>
                                    <th colspan="5" class="text-end fw-bold">Grand Total Invoice Amount:</th>
                                    <th class="text-end fw-bold fs-5 text-success" id="invGrandTotal">₹0.00</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top">
                    <button type="button" class="btn btn-secondary btn-sm rounded-2" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i> Close</button>
                    <button type="button" class="btn btn-primary btn-sm rounded-2 fw-bold" onclick="printInvoiceDocument()"><i class="fas fa-print me-1"></i> Print Invoice</button>
                </div>
            </div>
        </div>
    </div>

    <!-- New Item Modal -->
    <div class="modal fade" id="newItemModal">
        <div class="modal-dialog modal-xl" style="max-width: 95%;">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Create New Item</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="newItemForm">
                        <input type="hidden" name="action" value="save_purchase_item">
                        <input type="hidden" id="edit_item_id" name="item_id" value="">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Item / Product Name *</label>
                                <input type="text" class="form-control" name="item_name" required id="pItemName">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">SKU / Item Code</label>
                                <input type="text" class="form-control" name="sku" id="pSku"
                                    placeholder="Auto-generated if blank">
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between">
                                    <label class="form-label">Category *</label>
                                    <div>
                                        <button class="btn btn-link btn-sm p-0 text-primary text-decoration-none me-2"
                                            type="button" onclick="refreshCats()" title="Refresh Categories"><i
                                                class="fas fa-sync-alt"></i></button>
                                        <button class="btn btn-link btn-sm p-0 text-success text-decoration-none"
                                            type="button" onclick="openAddCatModal()"><i class="fas fa-plus-circle"></i>
                                            Add
                                            New</button>
                                    </div>
                                </div>
                                <select class="form-select" name="category_id" id="newItemCat" required
                                    onchange="loadSubCats(this.value)">
                                    <option value="">Select</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between">
                                    <label class="form-label">Sub Category</label>
                                    <div>
                                        <button class="btn btn-link btn-sm p-0 text-primary text-decoration-none me-2"
                                            type="button" onclick="refreshSubCats()" title="Refresh SubCategories"><i
                                                class="fas fa-sync-alt"></i></button>
                                        <button class="btn btn-link btn-sm p-0 text-success text-decoration-none"
                                            type="button" onclick="openAddSubCatModal()"><i
                                                class="fas fa-plus-circle"></i>
                                            Add New</button>
                                    </div>
                                </div>
                                <select class="form-select" name="subcategory_id" id="newItemSubCat">
                                    <option value="">Select Category First</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">HSN Code</label>
                                <input type="text" class="form-control" name="hsn_code" id="pHsn">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">CGST %</label>
                                <input type="number" step="0.01" class="form-control" name="cgst" id="pCgst"
                                    value="0.00">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">SGST %</label>
                                <input type="number" step="0.01" class="form-control" name="sgst" id="pSgst"
                                    value="0.00">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">IGST %</label>
                                <input type="number" step="0.01" class="form-control" name="igst" id="pIgst"
                                    value="0.00">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Item Photo (Optional)</label>
                                <input type="file" class="form-control" name="item_photo" id="pPhoto" accept="image/*">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Min Stock level</label>
                                <input type="number" step="0.01" class="form-control" name="min_stock" id="pMinStock"
                                    value="0.00">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Brief Description</label>
                                <textarea class="form-control" name="description" id="pDesc" rows="2"></textarea>
                            </div>

                            <!-- Unit Variations Section -->
                            <div class="col-12">
                                <h6
                                    class="border-bottom pb-2 mt-3 mb-3 text-primary d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-layer-group me-2"></i>Unit & Price Variations</span>
                                    <button type="button" class="btn btn-outline-primary btn-xs"
                                        onclick="addUnitRow()"><i class="fas fa-plus"></i> Add Variation</button>
                                </h6>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 20%;">Unit</th>
                                                <th style="width: 20%;">Size (e.g. 500g)</th>
                                                <th style="width: 15%;">Qty/Pack</th>
                                                <th style="width: 20%;">Pur. Price (₹)</th>
                                                <th style="width: 20%;">MRP (₹)</th>
                                                <th style="width: 5%;"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="unitVariationRows">
                                            <!-- Dynamic rows here -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="saveNewItem()">Save New Item</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Add Category Modal -->
    <div class="modal fade" id="addCatModal" tabindex="-1" style="z-index: 2000;">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h6 class="modal-title">Add New Category</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small">Category Name</label>
                        <input type="text" class="form-control" id="newCatName">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary btn-sm w-100" onclick="saveQuickCat()">Save
                        Category</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Add SubCategory Modal -->
    <div class="modal fade" id="addSubCatModal" tabindex="-1" style="z-index: 2000;">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h6 class="modal-title">Add New SubCategory</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small">Selected Category</label>
                        <input type="text" class="form-control" id="targetCatName" readonly disabled>
                        <input type="hidden" id="targetCatId">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">SubCategory Name</label>
                        <input type="text" class="form-control" id="newSubCatName">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary btn-sm w-100" onclick="saveQuickSubCat()">Save
                        SubCategory</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add New Supplier Modal -->
    <div class="modal fade" id="addSupplierModal" tabindex="-1" style="z-index: 1060;">
        <div class="modal-dialog modal-xl" style="max-width: 98%;">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New Supplier / Vendor</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addSupplierForm">
                        <div class="row g-3">
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-1"><i class="fas fa-id-card me-1"></i>Basic Information</h6>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Supplier / Pay-To Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="supPaytoname" placeholder="Full supplier name" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Mobile No.</label>
                                <input type="text" class="form-control" id="supMobile" placeholder="10-digit mobile" maxlength="10">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Email Address</label>
                                <input type="email" class="form-control" id="supEmail" placeholder="supplier@example.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">GST Number</label>
                                <input type="text" class="form-control" id="supGst" placeholder="e.g. 22AAAAA0000A1Z5" maxlength="15">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">PAN Number</label>
                                <input type="text" class="form-control" id="supPan" placeholder="e.g. ABCDE1234F" maxlength="10">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold">Address</label>
                                <textarea class="form-control" id="supAddress" rows="2" placeholder="Full supplier address"></textarea>
                            </div>
                            <div class="col-12 mt-2">
                                <h6 class="text-primary border-bottom pb-1"><i class="fas fa-university me-1"></i>Bank Details</h6>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Bank Name</label>
                                <input type="text" class="form-control" id="supBankname" placeholder="e.g. SBI, HDFC">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Account Number</label>
                                <input type="text" class="form-control" id="supAccountno" placeholder="Bank account number">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">IFSC Code</label>
                                <input type="text" class="form-control" id="supIfsc" placeholder="e.g. SBIN0001234" maxlength="11">
                            </div>
                            <div class="col-12 mt-2">
                                <label class="form-label small fw-bold">Remarks</label>
                                <textarea class="form-control" id="supRemark" rows="2" placeholder="Any additional notes"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success px-4" onclick="saveNewSupplier()">
                        <i class="fas fa-save me-1"></i> Save Supplier
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Ledger / Stock History Modal -->
    <div class="modal fade" id="ledgerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content shadow-lg border-0 rounded-4">
                <div class="modal-header bg-light border-0 py-3">
                    <h5 class="modal-title fw-bold" id="ledgerTitle"><i class="fas fa-history me-2 text-primary"></i>Stock History</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3 py-2">Date & Time</th>
                                    <th class="py-2">Type</th>
                                    <th class="py-2 text-end">In Qty</th>
                                    <th class="py-2 text-end">Out Qty</th>
                                    <th class="pe-3 py-2 text-end">Ref / Note</th>
                                </tr>
                            </thead>
                            <tbody id="ledgerDataBody">
                                <!-- Populated via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        let itemsListOptions = "";

        function viewLedger(itemId, locId, itemName) {
            $('#ledgerTitle').html(`<i class='fas fa-history me-2 text-primary'></i> ${itemName} Stock History`);
            $('#ledgerDataBody').html('<tr><td colspan="5" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div> Loading stock history...</td></tr>');

            let modalEl = document.getElementById('ledgerModal');
            let modal = bootstrap.Modal.getInstance(modalEl);
            if (!modal) {
                modal = new bootstrap.Modal(modalEl);
            }
            modal.show();

            $.ajax({
                url: 'ajax_po.php',
                type: 'POST',
                data: {
                    action: 'get_stock_details',
                    seed_id: itemId,
                    location_id: locId
                },
                dataType: 'json',
                success: function (response) {
                    $('#ledgerDataBody').html(response.rows || '<tr><td colspan="5" class="text-center py-3 text-muted">No history available for this item.</td></tr>');
                },
                error: function () {
                    $('#ledgerDataBody').html('<tr><td colspan="5" class="text-center text-danger py-4">Failed to load stock history.</td></tr>');
                }
            });
        }

        // Initialize Select2 on any .item-select
        function initSelect2() {
            $('.item-select').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Select or Search Item',
                allowClear: true
            });
        }

        function loadLocations() {
            $.post('ajax_po.php', { action: 'list_locations' }, function (res) {
                $('#transferFrom').html(res.options).trigger('change');
                $('#transferTo').html(res.options).trigger('change');
            }, 'json');
        }

        $(document).ready(function () {
            // Init Transfer Dropdowns with Parent
            $('#transferFrom, #transferTo').select2({
                theme: 'bootstrap-5',
                width: '100%',
                dropdownParent: $('#requestModal'),
                placeholder: 'Select Location'
            });

            $('#filterStatus').val('Draft');
            loadPOs();
            loadVendors();
            loadItemsList();
            loadInventory();
            loadLocations();

            // Wait for items to load before adding first row to avoid empty select2
            // logic inside loadItemsList handles initial row

            // Load Modal Dropdowns once
            $.post('ajax_po.php', { action: 'list_cats' }, function (res) {
                $('#newItemCat').html(res.options);
            }, 'json');

            loadLocations();

            // Init Select2 for other dropdowns
            $('#poVendor').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Select Vendor'
            });

            $.fn.modal.Constructor.prototype.enforceFocus = function() {};

            $('#newItemCat').select2({ theme: 'bootstrap-5', width: '100%', dropdownParent: $('#newItemCat').parent(), placeholder: 'Select...' });
            $('#newItemSubCat').select2({ theme: 'bootstrap-5', width: '100%', dropdownParent: $('#newItemSubCat').parent(), placeholder: 'Select...' });
            $('#newItemVendor').select2({ theme: 'bootstrap-5', width: '100%', dropdownParent: $('#newItemVendor').parent(), placeholder: 'Select...' });

            // Preload Transfer Items
            $.post('ajax_po.php', { action: 'list_items' }, function (res) {
                if (res && res.items) {
                    cachedTransferItems = res.items;
                }
            }, 'json');

            // Tab listener using standard BS5
            var transferTab = document.getElementById('transfers-tab');
            if (transferTab) {
                transferTab.addEventListener('shown.bs.tab', function (event) {
                    if (typeof loadTransfers === 'function') loadTransfers();
                });
            }

            var itemsTab = document.getElementById('items-tab');
            if (itemsTab) {
                itemsTab.addEventListener('shown.bs.tab', function (event) {
                    if (typeof loadItemMaster === 'function') loadItemMaster();
                });
            }

            // GST Logic
            $('#pCgst').on('input', function () {
                let val = parseFloat($(this).val()) || 0;
                if (val > 0) {
                    $('#pSgst').val(val.toFixed(2)).prop('readonly', true);
                    $('#pIgst').val('0.00');
                } else {
                    $('#pSgst').prop('readonly', false);
                }
            });

            $('#pIgst').on('input', function () {
                let val = parseFloat($(this).val()) || 0;
                if (val > 0) {
                    $('#pCgst').val('0.00');
                    $('#pSgst').val('0.00').prop('readonly', false);
                }
            });
        });

        function openNewItemModal() {
            $('#newItemForm')[0].reset();
            $('#edit_item_id').val('');
            $('#newItemSubCat').html('<option value="">Select Category First</option>');
            $('#unitVariationRows').empty();
            $('#pSgst').prop('readonly', false); // Reset readonly
            addUnitRow(); // Add at least one row
            $('.modal-title', '#newItemModal').text('Add New Purchase Item');
            new bootstrap.Modal('#newItemModal').show();
        }

        const unitOptions = `
            <option value="Kg">Kg</option>
            <option value="Gm">Gm</option>
            <option value="mg">mg</option>
            <option value="Ton">Ton</option>
            <option value="Quintal">Quintal</option>
            <option value="Litre">Litre</option>
            <option value="mL">mL</option>
            <option value="Nos">Nos</option>
            <option value="Pcs">Pcs</option>
            <option value="Packet">Packet</option>
            <option value="Bag">Bag</option>
            <option value="Box">Box</option>
            <option value="Carton">Carton</option>
            <option value="Bundle">Bundle</option>
            <option value="Roll">Roll</option>
            <option value="Tray">Tray</option>
            <option value="Bottle">Bottle</option>
            <option value="Can">Can</option>
            <option value="Drum">Drum</option>
            <option value="Pouch">Pouch</option>
            <option value="Set">Set</option>
            <option value="Pair">Pair</option>
            <option value="Dozen">Dozen</option>
            <option value="Meter">Meter</option>
            <option value="Feet">Feet</option>
            <option value="Sq Ft">Sq Ft</option>
        `;

        function addUnitRow(data = null) {
            let rowId = Date.now() + Math.random();
            let html = `
            <tr id="unit_row_${rowId}">
                <td>
                    <select class="form-select form-select-sm" name="u_unit[]" required>
                        <option value="">Select</option>
                        ${unitOptions}
                    </select>
                </td>
                <td><input type="text" class="form-control form-control-sm" name="u_size[]" value="${data ? data.unit_size : ''}" placeholder="e.g. 500g"></td>
                <td><input type="number" step="0.01" class="form-control form-control-sm" name="u_pack_qty[]" value="${data ? data.pack_qty : '1.00'}"></td>
                <td><input type="number" step="0.01" class="form-control form-control-sm" name="u_pur_price[]" value="${data ? data.purchase_price : '0.00'}"></td>
                <td><input type="number" step="0.01" class="form-control form-control-sm" name="u_mrp[]" value="${data ? data.mrp : '0.00'}"></td>
                <td class="text-center">
                    <button type="button" class="btn btn-link btn-sm text-danger p-0" onclick="$('#unit_row_${rowId}').remove()"><i class="fas fa-times"></i></button>
                </td>
            </tr>`;
            $('#unitVariationRows').append(html);
            if (data) {
                $('#unit_row_' + rowId).find('select[name="u_unit[]"]').val(data.unit);
            }
        }

        function loadItemMaster() {
            $.post('ajax_po.php', { action: 'list_item_master' }, function (res) {
                $('#itemsTableBody').html(res.rows);
            }, 'json');
        }

        function editItem(id) {
            $.post('ajax_po.php', { action: 'get_item_details', item_id: id }, function (res) {
                if (res.status === 'success') {
                    let item = res.data;
                    $('#edit_item_id').val(item.item_id);
                    $('#pItemName').val(item.item_name);
                    $('#pSku').val(item.sku);
                    $('#newItemCat').val(item.category_id).trigger('change');

                    // Delay subcat selection to allow ajax load
                    setTimeout(() => {
                        $('#newItemSubCat').val(item.subcategory_id).trigger('change');
                    }, 500);

                    $('#pHsn').val(item.hsn_code);
                    $('#pCgst').val(item.cgst);
                    $('#pSgst').val(item.sgst);
                    $('#pIgst').val(item.igst);
                    $('#pPhoto').val(''); // Clear file input on edit
                    $('#pMinStock').val(item.min_stock);
                    $('#pDesc').val(item.description);

                    // Trigger GST logic to set readonly if needed
                    $('#pCgst').trigger('input');

                    // Handle units
                    $('#unitVariationRows').empty();
                    if (item.units && item.units.length > 0) {
                        item.units.forEach(u => addUnitRow(u));
                    } else {
                        addUnitRow();
                    }

                    $('.modal-title', '#newItemModal').text('Edit Purchase Item');
                    new bootstrap.Modal('#newItemModal').show();
                }
            }, 'json');
        }

        // Quick Add Category/SubCat Logic
        function openAddCatModal() {
            $('#newCatName').val('');
            new bootstrap.Modal('#addCatModal').show();
        }

        function saveQuickCat() {
            let name = $('#newCatName').val().trim();
            if (!name) return;
            $.post('ajax_po.php', { action: 'save_quick_cat', name: name }, function (res) {
                if (res.status === 'success') {
                    Swal.fire('Added', 'Category created', 'success');
                    $('#addCatModal').modal('hide');
                    refreshCats(res.new_id);
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            }, 'json');
        }

        function refreshCats(selectedId = null) {
            $.post('ajax_po.php', { action: 'list_cats' }, function (res) {
                $('#newItemCat').html(res.options);
                if (selectedId) {
                    $('#newItemCat').val(selectedId).trigger('change');
                }
            }, 'json');
        }

        function openAddSubCatModal() {
            let catId = $('#newItemCat').val();
            let catName = $('#newItemCat option:selected').text();
            if (!catId) {
                Swal.fire('Select Category', 'Please select a parent category first.', 'warning');
                return;
            }
            $('#targetCatId').val(catId);
            $('#targetCatName').val(catName);
            $('#newSubCatName').val('');
            new bootstrap.Modal('#addSubCatModal').show();
        }

        function saveQuickSubCat() {
            let name = $('#newSubCatName').val().trim();
            let catId = $('#targetCatId').val();
            if (!name || !catId) return;
            $.post('ajax_po.php', { action: 'save_quick_subcat', name: name, category_id: catId }, function (res) {
                if (res.status === 'success') {
                    Swal.fire('Added', 'SubCategory created', 'success');
                    $('#addSubCatModal').modal('hide');
                    loadSubCats(catId);
                    setTimeout(() => { $('#newItemSubCat').val(res.new_id).trigger('change'); }, 500);
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            }, 'json');
        }

        function refreshSubCats() {
            let catId = $('#newItemCat').val();
            if (catId) {
                loadSubCats(catId);
            } else {
                Swal.fire('Note', 'Select a category first to refresh sub-categories', 'info');
            }
        }

        function loadSubCats(catKb) {
            $.post('ajax_po.php', { action: 'list_subcats', cat_id: catKb }, function (res) {
                $('#newItemSubCat').html(res.options).trigger('change');
            }, 'json');
        }

        // Modified to populate dropdowns
        function loadVendors() {
            $.post('ajax_po.php', { action: 'list_vendors' }, function (res) {
                $('#poVendor').html(res.options).trigger('change');
                $('#newItemVendor').html(res.options).trigger('change'); // Also populate in New Item Modal
            }, 'json');
        }

        function openAddSupplierModal() {
            $('#addSupplierForm')[0].reset();
            new bootstrap.Modal('#addSupplierModal').show();
        }

        function saveNewSupplier() {
            let name = $('#supPaytoname').val().trim();
            if (!name) {
                Swal.fire('Required', 'Please enter the Supplier Name.', 'warning');
                return;
            }
            let data = {
                action: 'save_supplier',
                paytoname: name,
                mobileno: $('#supMobile').val().trim(),
                emailaddress: $('#supEmail').val().trim(),
                gstno: $('#supGst').val().trim(),
                panno: $('#supPan').val().trim(),
                supplieraddress: $('#supAddress').val().trim(),
                bankname: $('#supBankname').val().trim(),
                accountno: $('#supAccountno').val().trim(),
                ifsccode: $('#supIfsc').val().trim(),
                remark: $('#supRemark').val().trim()
            };
            $.post('ajax_po.php', data, function (res) {
                if (res.status === 'success') {
                    Swal.fire('Saved!', 'Supplier added successfully.', 'success');
                    $('#addSupplierModal').modal('hide');
                    loadVendors(); // Refresh vendor dropdown
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            }, 'json');
        }

        // Modified to handle Select2 re-init
        function loadItemsList() {
            $.post('ajax_po.php', { action: 'list_items' }, function (res) {
                if (res && res.items) {
                    cachedTransferItems = res.items;
                }
                let options = res.options; // Assuming <option>...
                // Add "Add New" option at top
                itemsListOptions = `<option value="NEW_ITEM" class="fw-bold text-success">+ ➕ Create New Item</option>` + options;

                $('.item-select').each(function () {
                    let val = $(this).val();
                    $(this).html(itemsListOptions).val(val);
                });

                if ($('#itemRows').children().length === 0) {
                    addItemRow();
                } else {
                    initSelect2(); // Re-init existing
                }
            }, 'json');
        }

        // Listen for item variations selection logic moved to handleItemChange
        /*
        $(document).on('change', '.item-select', function () {
            if ($(this).val() === 'NEW_ITEM') {
                $(this).val('').trigger('change'); // Reset selection
                openNewItemModal();
            }
        });
        */

        function saveNewItem() {
            let form = $('#newItemForm');
            if (!form[0].checkValidity()) {
                form[0].reportValidity();
                return;
            }

            let formData = new FormData(form[0]);

            $.ajax({
                url: 'ajax_po.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (res) {
                    if (res.status === 'success') {
                        Swal.fire('Saved', res.message, 'success');
                        $('#newItemModal').modal('hide');
                        form[0].reset();

                        loadItemsList();
                        if ($('#items-tab').hasClass('active')) {
                            loadItemMaster();
                        }
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }
            });
        }

        function addItemRow() {
            let rowId = Date.now();
            let html = `
            <tr class="item-row" id="row_${rowId}" data-conversion_factor="1.0000" data-pack_price="0.00" data-cgst="0" data-sgst="0" data-igst="0" data-gst_percent="0">
                <td>
                    <select class="form-select form-select-sm item-select" name="item_id[]" required onchange="handleItemChange(this, ${rowId})">
                        ${itemsListOptions}
                    </select>
                    <div class="uom-info-container mt-1" id="uom_info_${rowId}"></div>
                </td>
                <td>
                    <select class="form-select form-select-sm item-unit-select" name="item_unit_id[]" required onchange="handleUnitChange(this, ${rowId})">
                        <option value="">Select Item First</option>
                    </select>
                </td>
                <td>
                    <select class="form-select form-select-sm item-uom-mode" name="uom_mode[]" onchange="handleUomModeChange(this, ${rowId})">
                        <option value="BASE">Base UOM</option>
                        <option value="PURCHASE">Purchase UOM</option>
                    </select>
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm item-qty text-center" name="qty[]" min="0.01" step="0.01" value="1.00" oninput="calculateTotal()">
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm item-price text-end" name="price[]" min="0" step="0.01" value="0.00" oninput="calculateTotal()">
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm item-gst text-center" name="gst_percent[]" min="0" max="100" step="0.01" value="0.00" oninput="calculateTotal()">
                </td>
                <td class="text-end">
                    <span class="fw-bold line-total d-block">₹0.00</span>
                    <span class="super-small text-muted line-tax-detail">Taxable: ₹0.00 | GST: ₹0.00</span>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-link link-danger p-0 fw-bold fs-6" onclick="$('#row_${rowId}').remove(); calculateTotal();"><i class="fas fa-trash-alt"></i></button>
                </td>
            </tr>`;
            $('#itemRows').append(html);
            $('#row_' + rowId).find('.item-select').select2({ theme: 'bootstrap-5', width: '100%', placeholder: 'Select or Search Item' });
        }

        function handleItemChange(itemSelect, rowId) {
            let itemId = $(itemSelect).val();
            let unitSelect = $(`#row_${rowId} .item-unit-select`);
            let uomModeSelect = $(`#row_${rowId} .item-uom-mode`);
            let uomContainer = $(`#uom_info_${rowId}`);
            let gstInput = $(`#row_${rowId} .item-gst`);

            if (itemId === 'NEW_ITEM') {
                $(itemSelect).val('').trigger('change.select2');
                openNewItemModal();
                return;
            }

            if (!itemId) {
                unitSelect.html('<option value="">Select Item First</option>');
                uomContainer.empty();
                $(`#row_${rowId}`).data('conversion_factor', 1.0000).data('pack_price', 0.00).data('pack_qty', 1.00)
                    .data('cgst', 0).data('sgst', 0).data('igst', 0).data('gst_percent', 0);
                gstInput.val('0.00');
                calculateTotal();
                return;
            }

            $.post('ajax_po.php', { action: 'get_item_details', item_id: itemId }, function (res) {
                if (res.status === 'success') {
                    let units = res.data.units;
                    let baseUom = res.data.base_uom || 'Unit';
                    let purUom = res.data.purchase_uom || 'Box';
                    let factor = parseFloat(res.data.conversion_factor) || 1.0000;

                    let cgst = parseFloat(res.data.cgst) || 0;
                    let sgst = parseFloat(res.data.sgst) || 0;
                    let igst = parseFloat(res.data.igst) || 0;
                    let tax_percent = parseFloat(res.data.tax_percent) || 0;

                    let effectiveGst = 0;
                    if (igst > 0) {
                        effectiveGst = igst;
                    } else if (cgst > 0 || sgst > 0) {
                        effectiveGst = cgst + sgst;
                    } else if (tax_percent > 0) {
                        effectiveGst = tax_percent;
                    }

                    $(`#row_${rowId}`)
                        .data('base_uom', baseUom)
                        .data('purchase_uom', purUom)
                        .data('conversion_factor', factor)
                        .data('cgst', cgst)
                        .data('sgst', sgst)
                        .data('igst', igst)
                        .data('gst_percent', effectiveGst);

                    gstInput.val(effectiveGst.toFixed(2));

                    // Update UOM Mode option labels
                    uomModeSelect.html(`
                        <option value="BASE">Base UOM (${baseUom})</option>
                        <option value="PURCHASE">Purchase UOM (${purUom})</option>
                    `);

                    let options = '<option value="">Select Unit</option>';
                    if (units && units.length > 0) {
                        units.forEach(u => {
                            options += `<option value="${u.item_unit_id}" data-price="${u.purchase_price}" data-pack_qty="${u.pack_qty}">${u.unit} (${u.unit_size} - Pack: ${u.pack_qty}) - ₹${u.purchase_price}</option>`;
                        });
                    }
                    unitSelect.html(options);

                    // Auto select first unit if available
                    if (units && units.length > 0) {
                        unitSelect.val(units[0].item_unit_id).trigger('change');
                    }

                    // Show UOM Master Info Badge + GST Info
                    let gstBadgeText = effectiveGst > 0 ? `${effectiveGst}%` : '0%';
                    if (cgst > 0 || sgst > 0) {
                        gstBadgeText += ` (CGST: ${cgst}%, SGST: ${sgst}%)`;
                    } else if (igst > 0) {
                        gstBadgeText += ` (IGST: ${igst}%)`;
                    }

                    uomContainer.html(`
                        <span class="uom-badge me-1"><i class="fas fa-ruler me-1 text-primary"></i>Base: <strong>${baseUom}</strong></span>
                        <span class="uom-badge me-1"><i class="fas fa-shopping-bag me-1 text-success"></i>Pur: <strong>${purUom}</strong></span>
                        <span class="uom-badge me-1"><i class="fas fa-calculator me-1 text-info"></i>Factor: <strong>${factor}</strong></span>
                        <span class="uom-badge"><i class="fas fa-percent me-1 text-warning"></i>GST: <strong>${gstBadgeText}</strong></span>
                    `);

                    calculateTotal();
                }
            }, 'json');
        }

        function handleUnitChange(unitSelect, rowId) {
            let selectedOpt = $(unitSelect).find(':selected');
            let price = parseFloat(selectedOpt.data('price')) || 0;
            let packQty = parseFloat(selectedOpt.data('pack_qty')) || 1.0;

            $(`#row_${rowId}`).data('pack_price', price).data('pack_qty', packQty);
            handleUomModeChange($(`#row_${rowId} .item-uom-mode`), rowId);
        }

        function handleUomModeChange(modeSelect, rowId) {
            let mode = $(modeSelect).val();
            let selectedOpt = $(`#row_${rowId} .item-unit-select option:selected`);
            let selectedPrice = parseFloat(selectedOpt.data('price')) || 0;
            let selectedPackQty = parseFloat(selectedOpt.data('pack_qty')) || 1.0;

            let storedPackPrice = parseFloat($(`#row_${rowId}`).data('pack_price')) || 0;
            let packPrice = storedPackPrice > 0 ? storedPackPrice : selectedPrice;

            let factor = parseFloat($(`#row_${rowId}`).data('conversion_factor')) || 1.0;
            let effectiveFactor = (factor > 1) ? factor : (selectedPackQty > 1 ? selectedPackQty : 1.0);

            if (mode === 'PURCHASE') {
                let purPrice = packPrice * effectiveFactor;
                $(`#row_${rowId} .item-price`).val(purPrice.toFixed(2));
            } else {
                $(`#row_${rowId} .item-price`).val(packPrice.toFixed(2));
            }
            calculateTotal();
        }

        // ... LoadPOs, calculateTotal, submitPO, viewPO, receivePO, submitReceive ...

        function setPoStatusFilter(status) {
            $('.po-stat-card').removeClass('active-card');
            if (status !== '') {
                $(`.po-stat-card[data-status="${status}"]`).addClass('active-card');
            } else {
                $('.po-stat-card[data-status=""]').first().addClass('active-card');
            }
            $('#filterStatus').val(status);
            loadPOs();
        }

        function handleFilterStatusChange() {
            let status = $('#filterStatus').val();
            $('.po-stat-card').removeClass('active-card');
            if (status !== '') {
                $(`.po-stat-card[data-status="${status}"]`).addClass('active-card');
            } else {
                $('.po-stat-card[data-status=""]').first().addClass('active-card');
            }
            loadPOs();
        }

        function loadPOs() {
            $.post('ajax_po.php', {
                action: 'list_pos',
                status: $('#filterStatus').val(),
                search: $('#filterSearch').val()
            }, function (res) {
                if (res.headers) {
                    $('#poTableHead').html(res.headers);
                }
                $('#poTableBody').html(res.rows);
                if (res.footer) {
                    $('#poTableFoot').html(res.footer);
                } else {
                    $('#poTableFoot').empty();
                }
                if (res.stats) {
                    $('#statTotalOrders').text(res.stats.total || 0);
                    $('#statDraftOrders').text(res.stats.draft || 0);
                    $('#statReceivedOrders').text(res.stats.received || 0);
                    $('#statCancelledOrders').text(res.stats.cancelled || 0);
                    $('#statTotalValue').text(res.stats.total_val || '₹0.00');
                }
            }, 'json');
        }

        window.setPoStatusFilter = setPoStatusFilter;
        window.handleFilterStatusChange = handleFilterStatusChange;

        function loadInventory() {
            $.post('ajax_po.php', { action: 'list_inventory' }, function (res) {
                $('#inventoryTableBody').html(res.rows);
            }, 'json');
        }

        function calculateTotal() {
            let totalTaxable = 0;
            let totalGst = 0;
            let grandTotal = 0;

            $('.item-row').each(function () {
                let qty = parseFloat($(this).find('.item-qty').val()) || 0;
                let price = parseFloat($(this).find('.item-price').val()) || 0;
                let gstPercent = parseFloat($(this).find('.item-gst').val()) || 0;

                let taxable = qty * price;
                let gstAmt = taxable * (gstPercent / 100);
                let lineTotal = taxable + gstAmt;

                $(this).find('.line-total').text('₹' + lineTotal.toFixed(2));
                $(this).find('.line-tax-detail').html(`Taxable: ₹${taxable.toFixed(2)} | GST: ₹${gstAmt.toFixed(2)}`);

                totalTaxable += taxable;
                totalGst += gstAmt;
                grandTotal += lineTotal;
            });

            $('#poTaxableDisplay').text('₹' + totalTaxable.toFixed(2));
            $('#poGstDisplay').text('₹' + totalGst.toFixed(2));
            $('#poTotalDisplay').text('₹' + grandTotal.toFixed(2));
        }

        function submitPO() {
            // Validate form
            if (!$('#createPOForm')[0].checkValidity()) {
                $('#createPOForm')[0].reportValidity();
                return;
            }

            let items = [];
            $('.item-row').each(function () {
                let id = $(this).find('.item-select').val();
                let unit_id = $(this).find('.item-unit-select').val();
                let uom_mode = $(this).find('.item-uom-mode').val() || 'BASE';
                let conversion_factor = $(this).data('conversion_factor') || 1.0;
                let qty = parseFloat($(this).find('.item-qty').val()) || 0;
                let price = parseFloat($(this).find('.item-price').val()) || 0;
                let gst_percent = parseFloat($(this).find('.item-gst').val()) || 0;
                let cgst = parseFloat($(this).data('cgst')) || 0;
                let sgst = parseFloat($(this).data('sgst')) || 0;
                let igst = parseFloat($(this).data('igst')) || 0;

                let taxable = qty * price;
                let gst_amount = taxable * (gst_percent / 100);
                let total_price = taxable + gst_amount;

                if (id && id !== 'NEW_ITEM') {
                    items.push({ 
                        seed_id: id, 
                        unit_id: unit_id, 
                        uom_mode: uom_mode, 
                        conversion_factor: conversion_factor, 
                        qty: qty, 
                        price: price,
                        gst_percent: gst_percent,
                        cgst_percent: cgst,
                        sgst_percent: sgst,
                        igst_percent: igst,
                        taxable_amount: taxable,
                        gst_amount: gst_amount,
                        total_price: total_price
                    });
                }
            });

            if (items.length === 0) {
                Swal.fire('Error', 'Please add at least one item', 'error');
                return;
            }

            $.post('ajax_po.php', {
                action: 'create_po',
                vendor_id: $('#poVendor').val(),
                po_date: $('#poDate').val(),
                expected_date: $('#poExpectedDate').val(),
                items: JSON.stringify(items)
            }, function (res) {
                if (res.status == 'success') {
                    Swal.fire('Created!', res.message, 'success');
                    $('#createPOForm')[0].reset();
                    $('#itemRows').empty();
                    new bootstrap.Tab(document.querySelector('#orders-tab')).show();
                    loadPOs();
                    addItemRow(); // Refresh for next
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            }, 'json');
        }

        function deletePO(id, poNumber) {
            Swal.fire({
                title: 'Cancel Purchase Order?',
                html: `<div class="text-start">
                    <p class="mb-2 text-secondary small">Are you sure you want to cancel <strong>${poNumber || 'this Purchase Order'}</strong>? Please provide a remark / reason below:</p>
                    <textarea id="swal_cancel_remark" class="form-control" rows="3" placeholder="Enter reason / remark for cancellation (required)..." style="font-size: 0.9rem;"></textarea>
                </div>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="fas fa-ban me-1"></i> Yes, Cancel PO',
                cancelButtonText: 'Close',
                focusConfirm: false,
                preConfirm: () => {
                    const remark = ($('#swal_cancel_remark').val() || '').trim();
                    if (!remark) {
                        Swal.showValidationMessage('Please enter a cancellation remark.');
                        return false;
                    }
                    return { remark: remark };
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    const remark = result.value.remark;
                    $.post('ajax_po.php', { action: 'delete_po', po_id: id, remark: remark }, function (res) {
                        if (res.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Cancelled!',
                                text: res.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            loadPOs();
                        } else {
                            Swal.fire('Error', res.message || 'Failed to cancel Purchase Order.', 'error');
                        }
                    }, 'json').fail(function () {
                        Swal.fire('Error', 'Server communication error while cancelling Purchase Order.', 'error');
                    });
                }
            });
        }
        window.deletePO = deletePO;

        function viewPO(id) {
            $.post('ajax_po.php', { action: 'get_po', po_id: id }, function (res) {
                console.log(res);
                if (res.status == 'success') {
                    let po = res.data;
                    $('#viewPoNum').text(po.po_number);
                    $('#viewVendor').text(po.VendorName);
                    $('#viewDate').text(po.po_date);
                    let st = (po.status || 'Draft').toLowerCase();
                    let badgeClass = (st == 'received' ? 'success' : (st == 'cancelled' ? 'danger' : (st == 'partial' ? 'warning' : 'primary')));
                    $('#viewStatus').text(po.status).attr('class', 'badge bg-' + badgeClass);
                    if (st === 'draft') {
                        $('#modalPoPdfBtn').removeClass('d-none').attr('href', 'generate_po_pdf.php?po_id=' + id);
                    } else {
                        $('#modalPoPdfBtn').addClass('d-none');
                    }
                    $('#viewReceiptsContainer').html(po.receipts_html || '');
                    $('#viewItemsBody').html(po.items_html);
                    $('#receiveSection').addClass('d-none');
                    let modalEl = document.getElementById('viewPOModal');
                    let modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();
                } else {
                    Swal.fire('Error', res.message || 'PO details could not be loaded.', 'error');
                }
            }, 'json').fail(function () {
                Swal.fire('Error', 'Server communication failed while loading PO details.', 'error');
            });
        }

        function previewInvoice(receiptId) {
            $.post('ajax_po.php', { action: 'get_receipt_details', receipt_id: receiptId }, function (res) {
                if (res.status == 'success') {
                    let r = res.data;
                    $('#invVendorName').text(r.VendorName || 'Supplier');
                    $('#invPoNum').text('PO Reference: ' + (r.po_number || 'N/A'));
                    $('#invNo').text(r.invoice_no || 'N/A');
                    $('#invDate').text(r.invoice_date || 'N/A');
                    $('#invRecDate').text(r.received_date || 'N/A');
                    $('#invLocation').text(r.location_name || 'N/A');
                    $('#invItemsBody').html(r.items_html);
                    $('#invGrandTotal').text('₹' + (parseFloat(r.total_amount) || 0).toFixed(2));
                    let modalEl = document.getElementById('invoicePreviewModal');
                    let modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            }, 'json').fail(function () {
                Swal.fire('Error', 'Server communication failed while loading receipt details.', 'error');
            });
        }

        function printInvoiceDocument() {
            let printContents = document.getElementById('printableInvoiceArea').innerHTML;
            let win = window.open('', '', 'height=700,width=900');
            win.document.write('<html><head><title>Print Purchase Invoice</title>');
            win.document.write('<link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">');
            win.document.write('<style>body{padding:20px;font-family:sans-serif;} .badge{border:1px solid #ccc;padding:4px 8px;}</style>');
            win.document.write('</head><body>');
            win.document.write(printContents);
            win.document.write('</body></html>');
            win.document.close();
            win.focus();
            setTimeout(() => { win.print(); win.close(); }, 500);
        }

        window.previewInvoice = previewInvoice;
        window.printInvoiceDocument = printInvoiceDocument;

        function receivePO(id) {
            $.post('ajax_po.php', { action: 'get_po', po_id: id }, function (res) {
                if (res.status == 'success') {
                    let po = res.data;
                    $('#viewPoNum').text(po.po_number);
                    $('#viewVendor').text(po.VendorName);
                    $('#viewDate').text(po.po_date);
                    let badgeClass = (po.status == 'Received' ? 'success' : (po.status == 'Partial' ? 'warning' : 'primary'));
                    $('#viewStatus').text(po.status).attr('class', 'badge bg-' + badgeClass);
                    $('#modalPoPdfBtn').addClass('d-none');
                    $('#viewReceiptsContainer').html(po.receipts_html || '');
                    $('#viewItemsBody').html(po.items_html);

                    $('#receivePoId').val(id);
                    $('#recInvoiceNo').val('');
                    $('#receiveInputsBody').empty();

                    $.post('ajax_po.php', { action: 'list_locations' }, function (locRes) {
                        let locHtml = `<select class="form-select form-select-sm border-primary" id="receiveLocation" required>
                                ${locRes.options}
                            </select>`;
                        $('#receiveLocationContainer').html(locHtml);
                    }, 'json');

                    let html = "";
                    if (po.items && po.items.length > 0) {
                        po.items.forEach(item => {
                            let remaining = (parseFloat(item.quantity) || 0) - (parseFloat(item.received_qty) || 0);
                            if (remaining > 0) {
                                let uomModeText = item.uom_mode === 'PURCHASE' ? (item.purchase_uom || 'PURCHASE') : (item.base_uom || 'BASE');
                                let uomBadge = `<span class="badge bg-light text-dark border super-small me-1"><i class="fas fa-ruler-combined me-1 text-primary"></i>${uomModeText}</span>`;
                                let trackingInfo = "";
                                if (item.batch_required == 1) trackingInfo += `<span class="badge bg-info-subtle text-info border super-small me-1"><i class="fas fa-barcode me-1"></i>Batch Req</span>`;
                                if (item.expiry_required == 1) trackingInfo += `<span class="badge bg-danger-subtle text-danger border super-small me-1"><i class="fas fa-calendar-times me-1"></i>Expiry Req</span>`;
                                if (item.serial_required == 1) trackingInfo += `<span class="badge bg-secondary-subtle text-dark border super-small me-1"><i class="fas fa-hashtag me-1"></i>Serial Req</span>`;

                                let gstPercent = parseFloat(item.gst_percent) || 0;
                                if (gstPercent === 0) {
                                    let cgst = parseFloat(item.m_cgst) || 0;
                                    let sgst = parseFloat(item.m_sgst) || 0;
                                    let igst = parseFloat(item.m_igst) || 0;
                                    let tax_pct = parseFloat(item.m_tax_percent) || 0;
                                    if (igst > 0) gstPercent = igst;
                                    else if (cgst > 0 || sgst > 0) gstPercent = cgst + sgst;
                                    else if (tax_pct > 0) gstPercent = tax_pct;
                                }

                                html += `
                                <tr class="rec-row" data-po-item-id="${item.po_item_id}" data-cgst="${item.cgst_percent || 0}" data-sgst="${item.sgst_percent || 0}" data-igst="${item.igst_percent || 0}">
                                    <td class="text-start">
                                        <div class="fw-bold text-dark">${item.item_name} <small class="text-muted">(${item.sku})</small></div>
                                        <div class="mt-1">${uomBadge}${trackingInfo}</div>
                                        <small class="text-muted super-small">Ordered: <strong>${item.quantity}</strong> | Previously Rec'd: <strong>${item.received_qty}</strong></small>
                                    </td>
                                    <td><span class="badge bg-light text-dark border">${item.quantity}</span></td>
                                    <td><input type="number" class="form-control form-control-sm rec-qty" value="${remaining}" oninput="calculateRecTotal()" data-item-id="${item.item_id || item.matched_item_id || item.seed_id}" data-seed-id="${item.seed_id}" data-master-seed-id="${item.master_seed_id || ''}" data-unit-id="${item.unit_id}"></td>
                                    <td><input type="number" class="form-control form-control-sm rec-price" value="${item.unit_price}" oninput="calculateRecTotal()"></td>
                                    <td><input type="number" class="form-control form-control-sm rec-gst" value="${gstPercent.toFixed(2)}" oninput="calculateRecTotal()"></td>
                                    <td class="text-end">
                                        <span class="fw-bold rec-line-total d-block">₹0.00</span>
                                        <span class="super-small text-muted rec-line-tax-detail">Taxable: ₹0.00</span>
                                    </td>
                                    <td><button type="button" class="btn btn-link link-danger p-0" onclick="removeReceiveRow(this)" title="Remove item entry"><i class="fas fa-times"></i></button></td>
                                </tr>`;
                            }
                        });
                    }
                    $('#receiveInputsBody').html(html || '<tr><td colspan="7" class="text-muted py-3 text-center">No pending items to receive for this PO.</td></tr>');
                    $('#receiveSection').removeClass('d-none');
                    calculateRecTotal();

                    let modalEl = document.getElementById('viewPOModal');
                    let modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();
                } else {
                    Swal.fire('Error', res.message || 'PO details could not be loaded.', 'error');
                }
            }, 'json').fail(function () {
                Swal.fire('Error', 'Server communication failed while loading PO details.', 'error');
            });
        }

        window.viewPO = viewPO;
        window.receivePO = receivePO;

        function removeReceiveRow(btn) {
            let row = $(btn).closest('tr');
            let poItemId = row.data('po-item-id') || '';
            let poId = $('#receivePoId').val() || '';

            if (!poItemId) {
                // New unpersisted row, simply remove from DOM
                row.remove();
                calculateRecTotal();
                return;
            }

            // Persisted PO item entry in database
            Swal.fire({
                title: 'Remove Item from PO?',
                text: 'Are you sure you want to permanently remove this item from the Purchase Order?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="fas fa-trash-alt me-1"></i> Yes, Remove Item',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('ajax_po.php', {
                        action: 'delete_po_item',
                        po_item_id: poItemId,
                        po_id: poId
                    }, function (res) {
                        if (res.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Removed!',
                                text: res.message,
                                timer: 1500,
                                showConfirmButton: false
                            });
                            if (poId) {
                                receivePO(poId);
                                loadPOs();
                            } else {
                                row.remove();
                                calculateRecTotal();
                            }
                        } else {
                            Swal.fire('Cannot Remove', res.message || 'Failed to remove item.', 'error');
                        }
                    }, 'json').fail(function () {
                        Swal.fire('Error', 'Server communication error while removing item.', 'error');
                    });
                }
            });
        }
        window.removeReceiveRow = removeReceiveRow;

        function handleRecItemChange(select) {
            let itemId = $(select).val();
            let row = $(select).closest('tr');
            if (itemId === 'NEW_ITEM') {
                $(select).val('').trigger('change.select2');
                openNewItemModal();
                return;
            }
            if (itemId) {
                let optSeedId = $(select).find('option:selected').data('seed-id') || 0;
                row.data('item-id', itemId).data('seed-id', optSeedId);
                $.post('ajax_po.php', { action: 'get_item_details', item_id: itemId }, function (res) {
                    if (res.status === 'success') {
                        let sid = res.data.seed_id || optSeedId || 0;
                        let cgst = parseFloat(res.data.cgst) || 0;
                        let sgst = parseFloat(res.data.sgst) || 0;
                        let igst = parseFloat(res.data.igst) || 0;
                        let tax_percent = parseFloat(res.data.tax_percent) || 0;
                        let effGst = igst > 0 ? igst : ((cgst > 0 || sgst > 0) ? (cgst + sgst) : tax_percent);
                        row.find('.rec-gst').val(effGst.toFixed(2));
                        row.data('item-id', res.data.item_id || itemId).data('seed-id', sid).data('cgst', cgst).data('sgst', sgst).data('igst', igst);
                        calculateRecTotal();
                    }
                }, 'json');
            }
            calculateRecTotal();
        }

        function addReceiveRow() {
            let rowId = Date.now();
            let html = `
            <tr class="rec-row" data-po-item-id="" data-cgst="0" data-sgst="0" data-igst="0">
                <td>
                    <select class="form-select form-select-sm rec-item-select" onchange="handleRecItemChange(this)">
                        ${itemsListOptions}
                    </select>
                </td>
                <td>0</td>
                <td><input type="number" class="form-control form-control-sm rec-qty" value="1" oninput="calculateRecTotal()"></td>
                <td><input type="number" class="form-control form-control-sm rec-price" value="0.00" oninput="calculateRecTotal()"></td>
                <td><input type="number" class="form-control form-control-sm rec-gst" value="0.00" oninput="calculateRecTotal()"></td>
                <td class="text-end">
                    <span class="fw-bold rec-line-total d-block">₹0.00</span>
                    <span class="super-small text-muted rec-line-tax-detail">Taxable: ₹0.00</span>
                </td>
                <td><button type="button" class="btn btn-link link-danger p-0" onclick="removeReceiveRow(this)" title="Remove item"><i class="fas fa-times"></i></button></td>
            </tr>`;
            $('#receiveInputsBody').append(html);
            $('#receiveInputsBody tr:last .rec-item-select').select2({ theme: 'bootstrap-5', width: '100%', dropdownParent: $('#viewPOModal') });
        }

        function calculateRecTotal() {
            let totalTaxable = 0;
            let totalGst = 0;
            let total = 0;
            $('.rec-row').each(function () {
                let qty = parseFloat($(this).find('.rec-qty').val()) || 0;
                let price = parseFloat($(this).find('.rec-price').val()) || 0;
                let gstPercent = parseFloat($(this).find('.rec-gst').val()) || 0;
                let taxable = qty * price;
                let gstAmt = taxable * (gstPercent / 100);
                let lineTotal = taxable + gstAmt;

                $(this).find('.rec-line-total').text('₹' + lineTotal.toFixed(2));
                $(this).find('.rec-line-tax-detail').html(`Taxable: ₹${taxable.toFixed(2)} | GST: ₹${gstAmt.toFixed(2)}`);

                totalTaxable += taxable;
                totalGst += gstAmt;
                total += lineTotal;
            });
            $('#recTotalDisplay').html(`
                <span class="text-dark d-block">₹${total.toFixed(2)}</span>
                <span class="super-small text-muted fw-normal d-block">Taxable: ₹${totalTaxable.toFixed(2)} | GST: ₹${totalGst.toFixed(2)}</span>
            `);
        }

        function submitReceive() {
            let items = [];
            let isValid = true;

            $('.rec-row').each(function () {
                let po_item_id = $(this).data('po-item-id') || '';
                let qty = parseFloat($(this).find('.rec-qty').val()) || 0;
                let price = parseFloat($(this).find('.rec-price').val()) || 0;
                let gst_percent = parseFloat($(this).find('.rec-gst').val()) || 0;
                let cgst = parseFloat($(this).data('cgst')) || 0;
                let sgst = parseFloat($(this).data('sgst')) || 0;
                let igst = parseFloat($(this).data('igst')) || 0;
                let taxable = qty * price;
                let gst_amount = taxable * (gst_percent / 100);
                let total_price = taxable + gst_amount;

                let item_id = 0;
                let seed_id = 0;
                let unit_id = 0;

                if (po_item_id) {
                    item_id = $(this).find('.rec-qty').data('item-id') || $(this).find('.rec-qty').data('seed-id') || 0;
                    seed_id = $(this).find('.rec-qty').data('master-seed-id') || $(this).find('.rec-qty').data('seed-id') || 0;
                    unit_id = $(this).find('.rec-qty').data('unit-id') || 0;
                } else {
                    item_id = $(this).find('.rec-item-select').val() || $(this).data('item-id') || 0;
                    seed_id = $(this).find('.rec-item-select option:selected').data('seed-id') || $(this).data('seed-id') || 0;
                    if (!item_id) isValid = false;
                }

                if (qty > 0) {
                    items.push({
                        po_item_id: po_item_id,
                        item_id: item_id,
                        seed_id: seed_id,
                        unit_id: unit_id,
                        qty: qty,
                        price: price,
                        gst_percent: gst_percent,
                        cgst_percent: cgst,
                        sgst_percent: sgst,
                        igst_percent: igst,
                        taxable_amount: taxable,
                        gst_amount: gst_amount,
                        total_price: total_price
                    });
                }
            });

            if (!isValid || items.length === 0) {
                Swal.fire('Error', 'Please ensure all items are selected and have quantity.', 'warning');
                return;
            }

            let invoiceNo = $('#recInvoiceNo').val();
            if (!invoiceNo) {
                Swal.fire('Invoice Required', 'Please enter the Invoice Number from the vendor.', 'warning');
                return;
            }

            $.post('ajax_po.php', {
                action: 'receive_po',
                po_id: $('#receivePoId').val(),
                location_id: $('#receiveLocation').val(),
                invoice_no: invoiceNo,
                invoice_date: $('#recInvoiceDate').val(),
                received_date: $('#recDate').val(),
                items: JSON.stringify(items)
            }, function (res) {
                if (res.status == 'success') {
                    Swal.fire('Success!', res.message, 'success');
                    $('#viewPOModal').modal('hide');
                    loadPOs();
                    loadInventory();
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            }, 'json');
        }

        // --- Transfer Logic ---

        let transferItems = [];
        let cachedTransferItems = [];
        let transferItemFocusIdx = -1;

        // Expose to global scope explicitly
        window.openRequestModal = function () {
            var myModal = new bootstrap.Modal(document.getElementById('requestModal'));
            myModal.show();

            // reset form
            $('#transferForm')[0].reset();
            $('#transferItemSearch').val('');
            $('#btnClearTransferItemSearch').hide();
            $('#transferItemSuggestions').empty().hide();
            transferItemFocusIdx = -1;
            transferItems = [];
            renderTransferItems();

            setTimeout(function () {
                $('#transferItemSearch').focus();
            }, 400);
        }

        function checkStockAvailability(seedId, fromLocId, callback) {
            $.post('ajax_po.php', {
                action: 'get_stock',
                seed_id: seedId,
                location_id: fromLocId
            }, function (res) {
                callback(parseFloat(res.stock) || 0);
            }, 'json');
        }

        function renderTransferItemSuggestions(query) {
            const $list = $('#transferItemSuggestions');
            $list.empty();
            transferItemFocusIdx = -1;

            if (!Array.isArray(cachedTransferItems) || cachedTransferItems.length === 0) {
                $.post('ajax_po.php', { action: 'list_items' }, function (res) {
                    if (res && res.items) {
                        cachedTransferItems = res.items;
                        renderTransferItemSuggestions(query);
                    }
                }, 'json');
                $list.html('<div class="p-2 text-center text-muted small"><i class="fas fa-spinner fa-spin me-1"></i>Loading items...</div>').show();
                return;
            }

            const q = (query || '').trim().toLowerCase();
            const matches = cachedTransferItems.filter(item => {
                if (!q) return true;
                const name = (item.name || '').toLowerCase();
                const sku = (item.sku || '').toLowerCase();
                return name.indexOf(q) !== -1 || sku.indexOf(q) !== -1;
            }).slice(0, 30);

            if (matches.length === 0) {
                $list.html('<div class="p-2 text-center text-muted small"><i class="fas fa-info-circle me-1"></i>No items match search</div>').show();
                return;
            }

            matches.forEach(item => {
                const skuBadge = item.sku ? `<span class="badge bg-secondary ms-1">${item.sku}</span>` : '';
                const $item = $(`
                    <a href="javascript:void(0);" class="list-group-item list-group-item-action transfer-item-suggest py-2 px-3 border-bottom d-flex justify-content-between align-items-center" style="cursor: pointer;">
                        <div>
                            <span class="fw-semibold text-dark item-text-name">${item.name}</span>
                            ${skuBadge}
                        </div>
                        <span class="badge bg-light text-primary border"><i class="fas fa-plus me-1"></i>Add</span>
                    </a>
                `);
                $item.data('item', item);
                $list.append($item);
            });

            $list.show();
        }

        function selectTransferItem(item) {
            if (!item) return;
            let fromLocSelector = $('#transferFrom').val();

            if (!fromLocSelector) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Select Source First',
                    text: 'Please select the Source Warehouse (From Location) before selecting items to check stock.'
                });
                $('#transferFrom').select2('open');
                return;
            }

            checkStockAvailability(item.id, fromLocSelector, function (stock) {
                addTransferItem(item.id, item.text || (item.name + (item.sku ? ' (' + item.sku + ')' : '')), stock);
                $('#transferItemSearch').val('').focus();
                $('#btnClearTransferItemSearch').hide();
                $('#transferItemSuggestions').empty().hide();
            });
        }

        // Live input typing for item search
        $(document).on('input focus', '#transferItemSearch', function () {
            const val = $(this).val();
            if (val) {
                $('#btnClearTransferItemSearch').show();
            } else {
                $('#btnClearTransferItemSearch').hide();
            }
            renderTransferItemSuggestions(val);
        });

        // Clear button
        $(document).on('click', '#btnClearTransferItemSearch', function () {
            $('#transferItemSearch').val('').focus();
            $('#btnClearTransferItemSearch').hide();
            renderTransferItemSuggestions('');
        });

        // Arrow keys & Enter key navigation for autocomplete
        $(document).on('keydown', '#transferItemSearch', function (e) {
            const $list = $('#transferItemSuggestions');
            const $items = $list.find('.transfer-item-suggest');
            const count = $items.length;

            if (!$list.is(':visible') || count === 0) {
                if (e.which === 40 || e.which === 38) {
                    renderTransferItemSuggestions($(this).val());
                }
                return;
            }

            if (e.which === 40) { // Arrow Down
                e.preventDefault();
                transferItemFocusIdx++;
                if (transferItemFocusIdx >= count) transferItemFocusIdx = 0;
                $items.removeClass('active bg-primary text-white').find('.text-dark').removeClass('text-white');
                const $target = $items.eq(transferItemFocusIdx);
                $target.addClass('active bg-primary text-white').find('.text-dark').addClass('text-white');
                $target[0].scrollIntoView({ block: 'nearest' });
            } else if (e.which === 38) { // Arrow Up
                e.preventDefault();
                transferItemFocusIdx--;
                if (transferItemFocusIdx < 0) transferItemFocusIdx = count - 1;
                $items.removeClass('active bg-primary text-white').find('.text-dark').removeClass('text-white');
                const $target = $items.eq(transferItemFocusIdx);
                $target.addClass('active bg-primary text-white').find('.text-dark').addClass('text-white');
                $target[0].scrollIntoView({ block: 'nearest' });
            } else if (e.which === 13) { // Enter Key
                e.preventDefault();
                if (transferItemFocusIdx >= 0 && transferItemFocusIdx < count) {
                    const item = $items.eq(transferItemFocusIdx).data('item');
                    selectTransferItem(item);
                } else if (count > 0) {
                    const item = $items.eq(0).data('item');
                    selectTransferItem(item);
                }
            } else if (e.which === 27) { // Escape Key
                $list.hide();
            }
        });

        // Click on suggestion item
        $(document).on('click', '.transfer-item-suggest', function () {
            const item = $(this).data('item');
            selectTransferItem(item);
        });

        // Click outside closes suggestions
        $(document).on('click', function (e) {
            if (!$(e.target).closest('#transferItemSearch, #transferItemSuggestions, #btnClearTransferItemSearch').length) {
                $('#transferItemSuggestions').hide();
            }
        });

        // Re-check stock if source warehouse changes
        $(document).on('change', '#transferFrom', function () {
            let fromLoc = $(this).val();
            if (transferItems.length > 0 && fromLoc) {
                let pending = transferItems.length;
                transferItems.forEach(item => {
                    checkStockAvailability(item.seed_id, fromLoc, function (stock) {
                        item.stock = stock;
                        pending--;
                        if (pending === 0) renderTransferItems();
                    });
                });
            }
        });

        function addTransferItem(id, name, stock) {
            if (transferItems.find(i => i.seed_id == id)) return;
            transferItems.push({ seed_id: id, name: name, qty: 1, stock: stock });
            renderTransferItems();
        }

        function updateTransferQty(idx, val) {
            if (transferItems[idx]) {
                transferItems[idx].qty = val;
            }
        }

        function removeTransferItem(idx) {
            transferItems.splice(idx, 1);
            renderTransferItems();
        }

        function renderTransferItems() {
            let html = "";
            if (transferItems.length === 0) {
                html = '<small class="text-muted d-block text-center mt-4">No items added</small>';
            } else {
                transferItems.forEach((item, idx) => {
                    let stockBadge = item.stock > 0 ? `<span class="badge bg-success float-end">${item.stock} Avail</span>` : `<span class="badge bg-danger float-end">Out of Stock</span>`;

                    html += `<div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light border rounded">
                        <div style="width: 50%">
                            <small class="fw-bold d-block text-truncate" title="${item.name}">${item.name}</small>
                            ${stockBadge}
                        </div>
                        <div style="width: 35%">
                            <input type="number" class="form-control form-control-sm" value="${item.qty}" min="1" max="${item.stock}" onchange="updateTransferQty(${idx}, this.value)">
                            <small class="text-muted" style="font-size:10px">Max: ${item.stock}</small>
                        </div>
                        <div style="width: 10%" class="text-end">
                             <button type="button" class="btn btn-xs text-danger" onclick="removeTransferItem(${idx})"><i class="fas fa-times"></i></button>
                        </div>
                     </div>`;
                });
            }
            $('#transferItemsList').html(html);
        }

        function submitTransferRequest() {
            let from = $('#transferFrom').val();
            let to = $('#transferTo').val();

            if (!from || !to) { Swal.fire('Error', 'Select source and destination', 'warning'); return; }
            if (from == to) { Swal.fire('Error', 'Source and Destination cannot be same', 'warning'); return; }
            if (transferItems.length == 0) { Swal.fire('Error', 'Add at least one item', 'warning'); return; }

            // Check if any qty > stock (though input max helps, strict check)
            let exceed = transferItems.some(i => parseFloat(i.qty) > parseFloat(i.stock));
            if (exceed) {
                Swal.fire('Stock Error', 'You cannot request more than available stock.', 'error');
                return;
            }

            $.post('ajax_po.php', {
                action: 'save_transfer_request',
                from_location: from,
                to_location: to,
                remarks: $('[name="remarks"]').val(),
                items: JSON.stringify(transferItems)
            }, function (res) {
                if (res.status == 'success') {
                    Swal.fire('Submitted', res.message, 'success');
                    $('#requestModal').modal('hide'); // Close modal
                    transferItems = [];
                    renderTransferItems();
                    loadTransfers();
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            }, 'json');
        }

        function loadTransfers() {
            $.post('ajax_po.php', { action: 'list_transfers' }, function (res) {
                $('#transferTableBody').html(res.rows);
            }, 'json');
        }

        function viewTransfer(id) {
            $('#approveReqId').val(id);
            $.post('ajax_po.php', { action: 'get_transfer_details', id: id }, function (res) {
                if (res.status == 'success') {
                    let d = res.data;
                    $('#trRoute').html(`${d.FromName} <i class="fas fa-arrow-right"></i> ${d.ToName}`);
                    $('#trDate').text(`Date: ${d.request_date} | Status: ${d.status}`);

                    // Interactive table
                    let html = "";
                    d.items.forEach(item => {
                        let isPending = d.status == 'Pending';
                        let currentStock = parseFloat(item.SourceStock) || 0;
                        let requested = parseFloat(item.quantity_requested);

                        // Auto-Cap logic: default to min(requested, stock)
                        let defaultApprove = (isPending && currentStock < requested) ? currentStock : requested;
                        if (!isPending) defaultApprove = item.quantity_approved;

                        let input = isPending ?
                            `<input type="number" class="form-control form-control-sm approve-qty" 
                               data-id="${item.item_id}" 
                               data-stock="${currentStock}"
                               value="${defaultApprove}" 
                               max="${currentStock}" 
                               min="0" oninput="validateTransferStock(this)">
                            <div class="text-danger small d-none stock-err">Stock exceeded</div>`
                            : item.quantity_approved;

                        let stockBadge = currentStock >= requested ?
                            `<span class="badge bg-success">${currentStock} Available</span>` :
                            `<span class="badge bg-danger">${currentStock} Available (Low)</span>`;

                        html += `<tr>
                            <td>${item.SeedName}</td>
                            <td>${requested}</td>
                            <td>${input}</td>
                            <td>${stockBadge}</td>
                         </tr>`;
                    });

                    $('#trItemsBody').html(html);

                    if (d.status == 'Pending') $('#approveActionSection').removeClass('d-none');
                    else $('#approveActionSection').addClass('d-none');

                    new bootstrap.Modal('#transferModal').show();
                }
            }, 'json');
        }

        function validateTransferStock(input) {
            let val = parseFloat($(input).val()) || 0;
            let stock = parseFloat($(input).data('stock')) || 0;
            if (val > stock) {
                $(input).addClass('is-invalid');
                $(input).next('.stock-err').removeClass('d-none');
            } else {
                $(input).removeClass('is-invalid');
                $(input).next('.stock-err').addClass('d-none');
            }
        }

        function approveTransfer(id) {
            viewTransfer(id);
        }

        function submitApproveTransfer() {
            let items = {};
            let hasError = false;
            let errorDetails = [];

            $('.approve-qty').each(function () {
                let val = parseFloat($(this).val()) || 0;
                let stock = parseFloat($(this).data('stock')) || 0;
                let itemName = $(this).closest('tr').find('td:first').text().trim() || 'Item';

                if (val > stock) {
                    hasError = true;
                    $(this).addClass('is-invalid');
                    $(this).next('.stock-err').removeClass('d-none');
                    errorDetails.push(`• <b>${itemName}</b>: Want to dispatch <b>${val}</b>, but only <b>${stock}</b> available in source stock.`);
                } else {
                    $(this).removeClass('is-invalid');
                    $(this).next('.stock-err').addClass('d-none');
                }
                items[$(this).data('id')] = val;
            });

            if (hasError) {
                Swal.fire({
                    icon: 'error',
                    title: 'Insufficient Stock',
                    html: `You cannot approve more than the available stock in the Source Warehouse:<br><br><div class="text-start small p-2 bg-light rounded border">${errorDetails.join('<br>')}</div>`
                });
                return;
            }

            $.post('ajax_po.php', {
                action: 'approve_transfer',
                request_id: $('#approveReqId').val(),
                items: JSON.stringify(items)
            }, function (res) {
                if (res.status == 'success') {
                    Swal.fire('Approved', res.message, 'success');
                    $('#transferModal').modal('hide');
                    loadTransfers();
                    loadInventory(); // Update stock view
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            }, 'json');
        }

        function receiveTransfer(reqId) {
            Swal.fire({
                title: 'Confirm Receipt?',
                text: "Confirm that you have physically received these items into inventory.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                confirmButtonText: 'Yes, Receive Stock'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('ajax_po.php', { action: 'receive_transfer', request_id: reqId }, function (res) {
                        if (res.status == 'success') {
                            Swal.fire('Received!', res.message, 'success');
                            loadTransfers();
                            loadInventory();
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    }, 'json');
                }
            });
        }

        function loadTransfers() {
            $.post('ajax_po.php', {
                action: 'list_transfers',
                status: $('#filterTransferStatus').val(),
                search: $('#filterTransferSearch').val()
            }, function (res) {
                $('#transferTableBody').html(res.rows);
                if (res.stats) {
                    $('#statTotalTransfers').text(res.stats.total || 0);
                    $('#statPendingTransfers').text(res.stats.pending || 0);
                    $('#statDispatchedTransfers').text(res.stats.dispatched || 0);
                    $('#statCompletedTransfers').text(res.stats.completed || 0);
                }
            }, 'json');
        }

        window.loadTransfers = loadTransfers;
        window.viewTransfer = viewTransfer;
        window.approveTransfer = approveTransfer;
        window.receiveTransfer = receiveTransfer;
    </script>
</body>

</html>