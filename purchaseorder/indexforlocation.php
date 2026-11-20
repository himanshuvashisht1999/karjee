<?php
require_once dirname(__DIR__) . "/connection.php";
require_once __DIR__ . "/transfer_functions.php";
require_once __DIR__ . "/po_functions_v2.php";

if (!isset($krfconn) || !($krfconn instanceof mysqli)) {
    $krfconn = (isset($conn) && ($conn instanceof mysqli)) ? $conn : null;
}

$locations = getLocations($krfconn);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Location Inventory & Transaction Ledger | KRF Inventory</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

    <style>
        :root {
            --primary-color: #16a34a;
            --primary-dark: #15803d;
            --secondary-color: #f8fafc;
            --card-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            --border-radius: 12px;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: var(--secondary-color);
            color: #1e293b;
            font-size: 0.88rem;
            letter-spacing: -0.01em;
            padding-bottom: 3rem;
        }

        .navbar {
            background: linear-gradient(135deg, #14532d 0%, #16a34a 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .card {
            border: 1px solid #e2e8f0;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            background: #ffffff;
        }

        .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid #edf2f7;
            padding: 1rem 1.25rem;
            border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
        }

        /* Nav Pills / Tabs */
        .nav-pills .nav-link {
            font-weight: 600;
            font-size: 0.85rem;
            color: #475569;
            padding: 0.55rem 1.1rem;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .nav-pills .nav-link.active {
            background-color: #16a34a;
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(22, 163, 74, 0.3);
        }

        .nav-pills .nav-link:hover:not(.active) {
            background-color: #f1f5f9;
            color: #0f172a;
        }

        /* Form Controls */
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
            padding: 0.5rem 0.85rem;
            color: #1e293b;
            background-color: #ffffff;
        }

        .form-control:focus, .form-select:focus {
            border-color: #16a34a;
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.15);
        }

        /* Table Styling */
        .table {
            font-size: 0.84rem;
        }

        .table thead th {
            background-color: #f8fafc !important;
            color: #475569 !important;
            font-weight: 700 !important;
            font-size: 0.72rem !important;
            letter-spacing: 0.06em !important;
            text-transform: uppercase !important;
            padding: 11px 14px !important;
            border-bottom: 2px solid #e2e8f0 !important;
            white-space: nowrap !important;
            vertical-align: middle !important;
        }

        .table tbody td {
            padding: 9px 14px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }

        .table tbody tr:hover {
            background-color: #f8fafc;
        }

        /* Typography & Mono pills */
        .font-monospace, .stock-sku, .stock-num, .sku-badge {
            font-family: 'JetBrains Mono', monospace;
        }

        .stock-sku {
            font-size: 0.78rem;
            font-weight: 700;
            color: #334155;
            background: #f1f5f9;
            padding: 2px 7px;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
            white-space: nowrap;
            display: inline-block;
        }

        .stock-item-name {
            font-weight: 600;
            color: #0f172a;
            font-size: 0.85rem;
            line-height: 1.35;
        }

        .stock-num {
            font-size: 0.85rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .stock-num-primary {
            color: #2563eb;
            font-weight: 700;
        }

        .stock-num-success {
            color: #059669;
            font-weight: 700;
        }

        /* Location Badges */
        .badge-all-locs {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
            font-size: 0.74rem;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 6px;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
        }

        .badge-single-loc {
            background: #f0fdf4;
            color: #15803d;
            border: 1px solid #bbf7d0;
            font-size: 0.74rem;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 6px;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
        }

        /* Action View Details Button */
        .btn-view-details {
            background: #ffffff;
            color: #16a34a;
            border: 1px solid #bbf7d0;
            font-weight: 600;
            font-size: 0.78rem;
            padding: 3.5px 10px;
            border-radius: 6px;
            transition: all 0.15s ease-in-out;
            white-space: nowrap;
        }

        .btn-view-details:hover {
            background: #16a34a;
            color: #ffffff;
            border-color: #16a34a;
            box-shadow: 0 2px 4px rgba(22, 163, 74, 0.2);
        }

        /* Compact 1-Liner KPI Metric Cards */
        .kpi-card-compact {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #ffffff;
            padding: 0.45rem 0.85rem;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.03);
            transition: all 0.15s ease;
            height: 100%;
            display: flex;
            align-items: center;
        }

        .kpi-card-compact:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.06);
        }

        .kpi-title-compact {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
            margin-bottom: 1px;
            line-height: 1.1;
        }

        .kpi-val-compact {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.15rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.1;
        }

        .kpi-icon-compact {
            width: 34px;
            height: 34px;
            min-width: 34px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="indexforlocation.php">
                <i class="fas fa-cubes me-2 fs-5"></i>
                <span>Location Inventory & Transaction Manager</span>
            </a>
            <div class="d-flex align-items-center gap-2">
                <a href="view_realtime_stock.php" class="btn btn-sm btn-outline-light border-0">
                    <i class="fas fa-chart-bar me-1"></i> Real-Time Stock
                </a>
                <a href="index.php" class="btn btn-sm btn-outline-light border-0">
                    <i class="fas fa-shopping-cart me-1"></i> PO Dashboard
                </a>
                <a href="inventory_itemmaster.php" class="btn btn-sm btn-outline-light border-0">
                    <i class="fas fa-boxes me-1"></i> Item Master
                </a>
                <span class="badge bg-white text-success fw-bold px-3 py-2 ms-2 rounded-pill shadow-sm">
                    <i class="fas fa-warehouse me-1"></i> Multi-Warehouse Panel
                </span>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4 px-4">
        <!-- Top Action & Filter Bar -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <div class="row g-3 align-items-center">
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label mb-1"><i class="fas fa-map-marker-alt text-success me-1"></i> Selected Location</label>
                        <select id="filterLocation" class="form-select fw-semibold text-dark">
                            <option value="all">🏢 All Locations (Consolidated Overview)</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?= htmlspecialchars($loc['location_id']) ?>">
                                    📍 <?= htmlspecialchars($loc['location_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label mb-1"><i class="fas fa-search text-muted me-1"></i> Search Item / Ref</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" id="tableSearch" class="form-control bg-light border-start-0" placeholder="Search by SKU, Name, Ref #...">
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label mb-1"><i class="fas fa-exchange-alt text-muted me-1"></i> Movement Type</label>
                        <select id="filterTransType" class="form-select">
                            <option value="all">All Movements (In & Out)</option>
                            <option value="in">🟢 Stock IN (Come / Inward)</option>
                            <option value="out">🔴 Stock OUT (Dispatched / Usage)</option>
                        </select>
                    </div>
                    <div class="col-lg-4 col-md-8 text-md-end pt-md-3">
                        <button class="btn btn-success shadow-sm px-3 fw-semibold me-2" onclick="openRequestModal()">
                            <i class="fas fa-plus-circle me-1"></i> New Stock Request
                        </button>
                        <button class="btn btn-outline-secondary shadow-sm px-3" onclick="refreshAllData()" title="Reload Data">
                            <i class="fas fa-sync-alt me-1"></i> Refresh
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Compact 1-Liner KPI Summary Bar -->
        <div class="row g-2 mb-3">
            <div class="col-xl-3 col-sm-6">
                <div class="kpi-card-compact border-start border-3 border-success">
                    <div class="d-flex align-items-center w-100 overflow-hidden">
                        <div class="kpi-icon-compact bg-success-subtle text-success me-2">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <div class="text-truncate">
                            <div class="kpi-title-compact">In-Stock Qty</div>
                            <div class="d-flex align-items-baseline gap-1">
                                <span class="kpi-val-compact text-success" id="statStockQty">-</span>
                                <small class="text-muted text-nowrap" id="statItemsCount">0 Items</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="kpi-card-compact border-start border-3 border-primary">
                    <div class="d-flex align-items-center w-100 overflow-hidden">
                        <div class="kpi-icon-compact bg-primary-subtle text-primary me-2">
                            <i class="fas fa-arrow-down"></i>
                        </div>
                        <div class="text-truncate">
                            <div class="kpi-title-compact">Total Inward (Come / IN)</div>
                            <div class="d-flex align-items-baseline gap-1">
                                <span class="kpi-val-compact text-primary" id="statInQty">-</span>
                                <small class="text-muted text-nowrap" id="statInCount">0 Logs</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="kpi-card-compact border-start border-3 border-danger">
                    <div class="d-flex align-items-center w-100 overflow-hidden">
                        <div class="kpi-icon-compact bg-danger-subtle text-danger me-2">
                            <i class="fas fa-arrow-up"></i>
                        </div>
                        <div class="text-truncate">
                            <div class="kpi-title-compact">Total Outward (OUT)</div>
                            <div class="d-flex align-items-baseline gap-1">
                                <span class="kpi-val-compact text-danger" id="statOutQty">-</span>
                                <small class="text-muted text-nowrap" id="statOutCount">0 Logs</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="kpi-card-compact border-start border-3 border-warning">
                    <div class="d-flex align-items-center w-100 overflow-hidden">
                        <div class="kpi-icon-compact bg-warning-subtle text-warning me-2">
                            <i class="fas fa-truck-moving"></i>
                        </div>
                        <div class="text-truncate">
                            <div class="kpi-title-compact">Active Transfers</div>
                            <div class="d-flex align-items-baseline gap-1">
                                <span class="kpi-val-compact text-warning-emphasis" id="statPendingTransfers">-</span>
                                <small class="text-muted text-nowrap" id="statTransfersSub">0 Active</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Card with Tabbed Views -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                <!-- Nav Tabs -->
                <ul class="nav nav-pills" id="inventoryTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-inventory-btn" data-bs-toggle="pill" data-bs-target="#tab-inventory" type="button" role="tab">
                            <i class="fas fa-layer-group me-2"></i> Location Stock Inventory
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-transactions-btn" data-bs-toggle="pill" data-bs-target="#tab-transactions" type="button" role="tab">
                            <i class="fas fa-exchange-alt me-2"></i> In & Out Transactions Ledger
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-transfers-btn" data-bs-toggle="pill" data-bs-target="#tab-transfers" type="button" role="tab">
                            <i class="fas fa-truck me-2"></i> Stock Transfer Activity
                        </button>
                    </li>
                </ul>

                <div class="d-flex align-items-center">
                    <span class="badge bg-light text-secondary border px-3 py-2 fw-semibold" id="activeLocationDisplay">
                        <i class="fas fa-warehouse text-success me-1"></i> Showing: All Locations
                    </span>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="tab-content" id="inventoryTabContent">
                    
                    <!-- TAB 1: Location Stock Inventory -->
                    <div class="tab-pane fade show active" id="tab-inventory" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-3" style="width: 14%;">SKU</th>
                                        <th style="width: 32%;">Item Description & Category</th>
                                        <th style="width: 26%;">Location / Warehouse</th>
                                        <th class="text-end" style="width: 16%;">Available Stock Qty</th>
                                        <th class="text-center" style="width: 12%;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="stockTableBody">
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="fas fa-spinner fa-spin me-2"></i> Loading stock inventory...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 2: In & Out Transactions Ledger -->
                    <div class="tab-pane fade" id="tab-transactions" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-3" style="width: 14%;">Date & Time</th>
                                        <th style="width: 25%;">Item Name & SKU</th>
                                        <th style="width: 15%;">Location</th>
                                        <th style="width: 12%;">Movement Type</th>
                                        <th class="text-end text-success" style="width: 10%;">IN (+ Come)</th>
                                        <th class="text-end text-danger" style="width: 10%;">OUT (-)</th>
                                        <th style="width: 14%;">Reference / Doc #</th>
                                        <th class="pe-3" style="width: 15%;">Notes / Remarks</th>
                                    </tr>
                                </thead>
                                <tbody id="transactionsTableBody">
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">
                                            <i class="fas fa-spinner fa-spin me-2"></i> Loading transactions ledger...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 3: Stock Transfer Activity -->
                    <div class="tab-pane fade" id="tab-transfers" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-3" style="width: 16%;">Req #</th>
                                        <th style="width: 28%;">Route (Source &rarr; Dest)</th>
                                        <th style="width: 16%;">Quantity / Items</th>
                                        <th style="width: 14%;">Date Requested</th>
                                        <th style="width: 12%;">Status</th>
                                        <th class="text-end pe-3" style="width: 14%;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="transferTableBody">
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="fas fa-spinner fa-spin me-2"></i> Loading transfer requests...
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

    <!-- Request Stock Modal -->
    <div class="modal fade" id="requestModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white py-3">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-box-open me-2"></i> Request Stock Transfer
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="transferForm">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">From (Source Warehouse)</label>
                                <select class="form-select" name="from_location" id="transferFrom"></select>
                                <div class="form-text small">Warehouse dispatching the stock</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">To (Destination Warehouse)</label>
                                <select class="form-select" name="to_location" id="transferTo"></select>
                                <div class="form-text small">Location receiving the stock</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Search & Add Item Needed</label>
                            <div class="position-relative">
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                                    <input type="text" id="transferItemSearch" class="form-control"
                                        placeholder="Type item name or SKU to search & add..." autocomplete="off">
                                    <button class="btn btn-outline-secondary" type="button" id="btnClearTransferItemSearch" style="display: none;" title="Clear">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <!-- Autocomplete Suggestions List -->
                                <div id="transferItemSuggestions" class="list-group position-absolute w-100 shadow"
                                    style="display: none; max-height: 220px; overflow-y: auto; z-index: 1060; top: 100%; border: 1px solid #cbd5e1; border-radius: 8px; background: #fff;">
                                </div>
                            </div>
                            <div class="text-muted small mt-1" style="font-size: 11px;">
                                <i class="fas fa-keyboard me-1"></i>Type to search, use <kbd>↑</kbd> <kbd>↓</kbd> arrows to navigate, and <kbd>Enter</kbd> to add item.
                            </div>
                        </div>

                        <div class="card bg-light border-0 mb-3">
                            <div class="card-body p-3">
                                <h6 class="form-label border-bottom pb-2 mb-2">Requested Items List</h6>
                                <div id="transferItemsList" style="min-height: 80px;">
                                    <small class="text-muted d-block text-center mt-3">No items added yet. Search above to add items.</small>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Remarks / Notes</label>
                            <textarea class="form-control" name="remarks" rows="2" placeholder="Urgency, specific requirement, or notes..."></textarea>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-success px-4 fw-semibold" onclick="submitTransferRequest()">
                                <i class="fas fa-paper-plane me-1"></i> Submit Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- View / Receive Transfer Details Modal -->
    <div class="modal fade" id="transferModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-dark text-white py-3">
                    <h5 class="modal-title fw-bold"><i class="fas fa-info-circle me-2"></i> Transfer Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-light border d-flex justify-content-between align-items-center mb-3">
                        <div><strong id="trRoute" class="fs-6 text-dark"></strong></div>
                        <div id="trDate" class="text-muted small fw-semibold"></div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle">
                            <thead class="table-light text-uppercase small">
                                <tr>
                                    <th>Item Details</th>
                                    <th class="text-end">Requested</th>
                                    <th class="text-end">Approved / Sent</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody id="trItemsBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Item Stock Detail & Transaction History Modal -->
    <div class="modal fade" id="itemStockDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
                <div class="modal-header bg-white border-bottom py-3 px-4">
                    <div class="d-flex align-items-center">
                        <div class="kpi-icon bg-success-subtle text-success me-3" style="width: 40px; height: 40px; font-size: 1.1rem;">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-dark mb-0" id="itemStockDetailModalTitle">Item Stock Breakdown</h5>
                            <span class="text-muted small" id="itemStockDetailModalSub">Item Details & Multi-Location Stock</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" style="background-color: #f8fafc;">
                    
                    <!-- 4 Compact 1-Liner KPI Cards in Modal -->
                    <div class="row g-2 mb-3">
                        <div class="col-md-3 col-6">
                            <div class="kpi-card-compact border-start border-3 border-primary">
                                <div class="overflow-hidden">
                                    <div class="kpi-title-compact">Purchased / IN</div>
                                    <div class="kpi-val-compact text-primary" id="modalKpiPurchased">0.00</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="kpi-card-compact border-start border-3 border-danger">
                                <div class="overflow-hidden">
                                    <div class="kpi-title-compact">Used / OUT</div>
                                    <div class="kpi-val-compact text-danger" id="modalKpiUsed">0.00</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="kpi-card-compact border-start border-3 border-success">
                                <div class="overflow-hidden">
                                    <div class="kpi-title-compact">Current Stock</div>
                                    <div class="kpi-val-compact text-success" id="modalKpiStock">0.00</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="kpi-card-compact border-start border-3 border-info">
                                <div class="overflow-hidden">
                                    <div class="kpi-title-compact">Net Balance</div>
                                    <div class="kpi-val-compact text-dark" id="modalKpiBalance">0.00</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Location-Wise Stock Breakdown Card -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-map-marker-alt text-success me-2"></i> Location-Wise Stock Distribution</h6>
                            <span class="badge bg-light text-secondary border small" id="modalLocationCountBadge">0 Locations</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light text-uppercase small">
                                        <tr>
                                            <th class="ps-3" style="width: 40%;">Warehouse / Location</th>
                                            <th class="text-end" style="width: 25%;">Available Stock Qty</th>
                                            <th class="text-end pe-3" style="width: 35%;">Last Stock Update</th>
                                        </tr>
                                    </thead>
                                    <tbody id="modalLocationTableBody">
                                        <tr>
                                            <td colspan="3" class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin me-2"></i> Loading location data...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Chronological Stock Movement / Transaction Log Card -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-history text-primary me-2"></i> Recent Stock Transactions (In & Out)</h6>
                            <span class="text-muted small">Latest 50 movements</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light text-uppercase small sticky-top">
                                        <tr>
                                            <th class="ps-3" style="width: 18%;">Date & Time</th>
                                            <th style="width: 22%;">Location</th>
                                            <th style="width: 16%;">Movement Type</th>
                                            <th class="text-end" style="width: 16%;">Qty Change</th>
                                            <th style="width: 14%;">Reference #</th>
                                            <th class="pe-3" style="width: 14%;">Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody id="modalTransactionTableBody">
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin me-2"></i> Loading transactions...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-white border-top py-2 px-4">
                    <button type="button" class="btn btn-secondary px-4 fw-semibold" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- Custom Transfer & Location Inventory Logic -->
    <script src="js/location_transfer.js"></script>
</body>
</html>