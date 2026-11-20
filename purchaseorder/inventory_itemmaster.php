<?php include "../connection.php"; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Item Master Catalog - Inventory Management</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #e2f2fe 0%, #dbeafe 100%);
            --accent-green: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --accent-blue: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            --glass-bg: rgba(255, 255, 255, 0.95);
            --card-shadow: 0 4px 12px rgba(5, 44, 101, 0.05);
            --border-radius: 12px;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #f1f5f9;
            color: #1e293b;
            font-size: 0.88rem;
            letter-spacing: -0.01em;
            padding-bottom: 3rem;
        }

        .font-monospace, .sku-pill, .stock-sku, .stock-num {
            font-family: 'JetBrains Mono', monospace !important;
        }

        /* Hero Header */
        .hero-header {
            background: linear-gradient(135deg, #e2f2fe 0%, #dbeafe 100%);
            border: 1px solid #cbd5e1;
            border-radius: var(--border-radius);
            padding: 0.6rem 1rem;
            color: #052c65;
            box-shadow: 0 4px 12px rgba(5, 44, 101, 0.05);
            margin-bottom: 0.65rem;
            position: relative;
            overflow: hidden;
        }

        .hero-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.1) 0%, rgba(255, 255, 255, 0) 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .hero-title {
            font-weight: 700;
            font-size: 1.25rem;
            color: #052c65;
            letter-spacing: -0.3px;
            margin-bottom: 0.1rem;
        }

        .hero-subtitle {
            font-size: 0.78rem;
            color: #334155;
            margin-bottom: 0;
        }

        /* KPI Stat Cards */
        .kpi-card {
            background: #ffffff;
            border-radius: 10px;
            padding: 0.5rem 0.75rem;
            box-shadow: var(--card-shadow);
            border: 1px solid #cbd5e1;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.65rem;
        }

        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
            border-color: #94a3b8;
        }

        .kpi-icon-box {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .kpi-blue { background: #eff6ff; color: #2563eb; }
        .kpi-green { background: #ecfdf5; color: #059669; }
        .kpi-purple { background: #f5f3ff; color: #7c3aed; }

        .kpi-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: #052c65;
            line-height: 1.1;
        }

        .kpi-label {
            font-size: 0.72rem;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        /* Filter Control Card */
        .filter-card {
            background: #ffffff;
            border-radius: 10px;
            padding: 0.5rem 0.75rem;
            box-shadow: var(--card-shadow);
            border: 1px solid #cbd5e1;
            margin-bottom: 0.65rem;
        }

        .search-input-group .form-control {
            border-radius: 8px;
            padding-left: 2.3rem;
            border: 1px solid #cbd5e1;
            height: 36px;
            font-size: 0.85rem;
        }

        .search-input-group .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
        }

        .search-icon-inside {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 0.85rem;
            z-index: 10;
        }

        /* Item Master Table Card */
        .table-card {
            background: #ffffff;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            border: 1px solid #cbd5e1;
            overflow: hidden;
        }

        .table-header-dark {
            background: linear-gradient(135deg, #e2f2fe 0%, #dbeafe 100%);
            color: #052c65;
            border-bottom: 1px solid #cbd5e1;
            padding: 0.45rem 0.85rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .table-custom {
            margin-bottom: 0;
        }

        .table-custom th {
            background: #e2f2fe;
            color: #052c65;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 0.35rem 0.6rem;
            border-bottom: 2px solid #cbd5e1;
        }

        .table-custom td {
            padding: 0.3rem 0.6rem;
            font-size: 0.75rem;
            vertical-align: middle;
            border-bottom: 1px solid #e2e8f0;
        }

        .table-custom tbody tr:hover {
            background-color: #f8fafc;
        }

        /* Styled Pills & Badges */
        .sku-pill {
            background: #1e293b;
            color: #ffffff;
            font-weight: 700;
            font-size: 0.78rem;
            padding: 0.3rem 0.7rem;
            border-radius: 6px;
            letter-spacing: 0.5px;
            display: inline-block;
        }

        .category-pill {
            background: #e0f2fe;
            color: #0369a1;
            font-weight: 600;
            font-size: 0.7rem;
            padding: 0.15rem 0.55rem;
            border-radius: 12px;
            border: 1px solid #bae6fd;
            display: inline-block;
        }

        .unit-pill {
            background: #f1f5f9;
            color: #334155;
            font-weight: 600;
            font-size: 0.7rem;
            padding: 0.15rem 0.45rem;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            display: inline-block;
        }

        .badge-variation-count {
            background: #64748b;
            color: #fff;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 0.15rem 0.35rem;
            border-radius: 5px;
        }

        .price-tag {
            font-weight: 700;
            color: #059669;
            font-size: 0.8rem;
        }

        .badge-status {
            font-weight: 600;
            font-size: 0.7rem;
            padding: 0.15rem 0.5rem;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
        }

        .badge-status.active {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        .badge-status.inactive {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .item-photo-thumb {
            width: 28px;
            height: 28px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
        }

        .item-photo-placeholder {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            background: #f1f5f9;
            border: 1px dashed #cbd5e1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .item-name-title {
            font-size: 0.82rem;
            letter-spacing: -0.2px;
        }

        .super-small {
            font-size: 0.7rem;
        }

        /* Buttons */
        .btn-add-item {
            background: var(--accent-green);
            color: #ffffff;
            font-weight: 600;
            border-radius: 10px;
            padding: 0.55rem 1.35rem;
            border: none;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);
            transition: all 0.3s ease;
        }

        .btn-add-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
            color: #ffffff;
        }

        .btn-action-edit {
            background: #0284c7;
            color: #ffffff;
            font-weight: 600;
            border: 1px solid #0284c7;
            border-radius: 5px;
            padding: 0.15rem 0.42rem;
            font-size: 0.68rem;
            transition: all 0.2s ease;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }

        .btn-action-edit:hover {
            background: #0369a1;
            color: #ffffff;
            border-color: #0369a1;
            transform: translateY(-1px);
        }

        .btn-action-deactivate {
            background: #fff7ed;
            color: #c2410c;
            border: 1px solid #fed7aa;
            font-weight: 600;
            border-radius: 5px;
            padding: 0.15rem 0.42rem;
            font-size: 0.68rem;
            transition: all 0.2s ease;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }

        .btn-action-deactivate:hover {
            background: #ea580c;
            color: #ffffff;
            border-color: #ea580c;
            transform: translateY(-1px);
        }

        .btn-action-activate {
            background: #f0fdf4;
            color: #15803d;
            border: 1px solid #bbf7d0;
            font-weight: 600;
            border-radius: 5px;
            padding: 0.15rem 0.42rem;
            font-size: 0.68rem;
            transition: all 0.2s ease;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }

        .btn-action-activate:hover {
            background: #16a34a;
            color: #ffffff;
            border-color: #16a34a;
            transform: translateY(-1px);
        }

        .btn-action-delete {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
            font-weight: 600;
            border-radius: 5px;
            padding: 0.15rem 0.42rem;
            font-size: 0.68rem;
            transition: all 0.2s ease;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }

        .btn-action-delete:hover {
            background: #dc2626;
            color: #ffffff;
            border-color: #dc2626;
            transform: translateY(-1px);
        }

        /* Modals */
        .modal-content {
            border-radius: 16px;
            border: none;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .modal-header-solid-blue {
            background-color: #2563eb;
            color: #ffffff;
            padding: 1rem 1.5rem;
        }

        .modal-footer {
            border-top: 1px solid #e2e8f0;
            background-color: #f8fafc;
            padding: 0.75rem 1.5rem;
        }

        .form-label {
            color: #334155;
            font-weight: 600;
            font-size: 0.8rem;
            margin-bottom: 0.35rem;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            font-size: 0.85rem;
            padding: 0.45rem 0.75rem;
            transition: all 0.2s ease;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
            font-weight: 600;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
            color: #1e293b;
        }

        .table-mini thead th {
            background: #f8fafc;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
            padding: 0.4rem 0.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .table-mini tbody td {
            padding: 0.3rem 0.4rem;
        }

        .section-separator {
            border-bottom: 2px dashed #e2e8f0;
            margin: 1.25rem 0;
        }
    </style>
</head>

<body>

    <div class="container-fluid py-4 px-4">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h4 class="page-title mb-1"><i class="fas fa-boxes-stacked me-2 text-primary"></i>Purchase Item Master
                </h4>
                <p class="text-muted small mb-0">Manage master inventory purchase catalogue, variants, units, and
                    pricing</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary btn-sm rounded-2 px-3 py-1 fw-semibold" onclick="fetchItemMaster()" title="Refresh Item Master Data">
                    <i class="fas fa-sync-alt me-1"></i> Refresh
                </button>
                <button class="btn btn-add-item" onclick="openNewItemModal()">
                    <i class="fas fa-plus-circle me-1"></i> Add New Item
                </button>
            </div>
        </div>

        <!-- Metric Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="kpi-card">
                    <div class="kpi-icon-box kpi-blue">
                        <i class="fas fa-box"></i>
                    </div>
                    <div>
                        <div class="kpi-value" id="kpiTotalItems">0</div>
                        <div class="kpi-label">Total Purchase Items</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="kpi-card">
                    <div class="kpi-icon-box kpi-green">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <div class="kpi-value" id="kpiActiveItems">0</div>
                        <div class="kpi-label">Active Items</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="kpi-card">
                    <div class="kpi-icon-box kpi-purple">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div>
                        <div class="kpi-value" id="kpiTotalCategories">0</div>
                        <div class="kpi-label">Active Categories</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Control Card -->
        <div class="filter-card">
            <div class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="position-relative search-input-group">
                        <i class="fas fa-search search-icon-inside"></i>
                        <input type="text" id="filterSearch" class="form-control" placeholder="Search by Item Name, SKU Code, or HSN Code...">
                    </div>
                </div>
                <div class="col-md-4">
                    <select id="filterCategory" class="form-select form-select-sm" style="height: 36px; border-radius: 8px;" onchange="fetchItemMaster()">
                        <option value="">All Categories</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <select id="filterStatus" class="form-select form-select-sm" style="height: 36px; border-radius: 8px;" onchange="fetchItemMaster()">
                        <option value="">All Statuses</option>
                        <option value="Active">Active Only</option>
                        <option value="Inactive">Inactive Only</option>
                    </select>
                    <button class="btn btn-sm btn-outline-secondary rounded-2 px-3" onclick="resetFilters()" title="Reset Filters">
                        <i class="fas fa-undo"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Main Card: Purchase Item Master List -->
        <div class="table-card">
            <div class="table-header-dark">
                <span class="fw-bold fs-6" style="color:#052c65;"><i class="fas fa-list me-2"></i> Master Product Items</span>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill" id="recordCountBadge">0 Records</span>
            </div>
            <div class="table-responsive">
                <table class="table align-middle table-custom" id="itemMasterTable">
                    <thead>
                        <tr>
                            <th style="width: 50px;" class="text-center">Photo</th>
                            <th>Item Name & Category</th>
                            <th style="width: 170px;">Unit & Pack Size</th>
                            <th style="width: 120px;">Purchase Price</th>
                            <th style="width: 85px;">Status</th>
                            <th style="width: 215px; min-width: 215px;" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="itemsTableBody">
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                                Loading Item Master records...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- NEW / EDIT ITEM MODAL -->
    <div class="modal fade" id="newItemModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 1200px;">
            <div class="modal-content">
                <div class="modal-header modal-header-solid-blue">
                    <h5 class="modal-title fw-bold text-white mb-0" id="modalTitle">
                        <i class="fas fa-plus-circle me-2"></i>Add New Purchase Item
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="newItemForm" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="save_item">
                        <input type="hidden" name="edit_item_id" id="edit_item_id">

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Item / Product Name *</label>
                                <input type="text" class="form-control" name="item_name" required id="pItemName" placeholder="e.g. Tomato Seeds Hybrid 500g">
                                <div id="itemNameCheckMsg" class="mt-1"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">SKU / Item Code</label>
                                <input type="text" class="form-control" name="sku" id="pSku" placeholder="Auto-generated if blank">
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label mb-0">Category *</label>
                                    <div>
                                        <button class="btn btn-link btn-sm p-0 text-primary text-decoration-none me-2" type="button" onclick="refreshCats()" title="Refresh Categories">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                        <button class="btn btn-link btn-sm p-0 text-success text-decoration-none fw-bold" type="button" onclick="openAddCatModal()">
                                            <i class="fas fa-plus-circle me-1"></i>Add New
                                        </button>
                                    </div>
                                </div>
                                <select class="form-select" name="category_id" id="newItemCat" required onchange="loadSubCats(this.value)">
                                    <option value="">Select Category</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label mb-0">Sub Category</label>
                                    <div>
                                        <button class="btn btn-link btn-sm p-0 text-primary text-decoration-none me-2" type="button" onclick="refreshSubCats()" title="Refresh SubCategories">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                        <button class="btn btn-link btn-sm p-0 text-success text-decoration-none fw-bold" type="button" onclick="openAddSubCatModal()">
                                            <i class="fas fa-plus-circle me-1"></i>Add New
                                        </button>
                                    </div>
                                </div>
                                <select class="form-select" name="subcategory_id" id="newItemSubCat">
                                    <option value="">Select Category First</option>
                                </select>
                            </div>

                            <!-- UOM Master Selection Section -->
                            <div class="col-12">
                                <div class="uom-settings-card">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <h6 class="fw-bold text-dark mb-0"><i class="fas fa-ruler-combined me-2 text-primary"></i>UOM Master Settings</h6>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 small">Central UOM Definition</span>
                                    </div>
                                    <small class="text-muted d-block mb-3">Establishes central inventory valuation unit (Base UOM) vs default PO & Sales transaction units.</small>
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label">Base UOM *</label>
                                            <select class="form-select uom-select" name="base_uom" id="pBaseUom" required>
                                                <option value="">Select Base UOM</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Purchase UOM *</label>
                                            <select class="form-select uom-select" name="purchase_uom" id="pPurchaseUom" required>
                                                <option value="">Select Purchase UOM</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Sale UOM *</label>
                                            <select class="form-select uom-select" name="sale_uom" id="pSaleUom" required>
                                                <option value="">Select Sale UOM</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Conversion Factor</label>
                                            <input type="number" step="0.0001" class="form-control" name="conversion_factor" id="pConversionFactor" value="1.0000" placeholder="1.0000">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">HSN Code</label>
                                <input type="text" class="form-control" name="hsn_code" id="pHsn" placeholder="e.g. 12099190">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">CGST %</label>
                                <input type="number" step="0.01" class="form-control" name="cgst" id="pCgst" value="0.00">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">SGST %</label>
                                <input type="number" step="0.01" class="form-control" name="sgst" id="pSgst" value="0.00">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">IGST %</label>
                                <input type="number" step="0.01" class="form-control" name="igst" id="pIgst" value="0.00">
                            </div>
                            <div class="col-12">
                                <div class="uom-settings-card">
                                    <h6 class="fw-bold text-dark mb-3"><i class="fas fa-sliders me-2 text-primary"></i>Inventory Control & Tracking Settings</h6>
                                    <div class="row g-3 align-items-center">
                                        <div class="col-md-3">
                                            <label class="form-label">Minimum Stock Level</label>
                                            <input type="number" step="0.01" class="form-control" name="min_stock" id="pMinStock" value="0.00" placeholder="0.00">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label d-block text-secondary">Batch Control</label>
                                            <div class="form-check form-switch pt-1">
                                                <input class="form-check-input" type="checkbox" role="switch" name="batch_required" value="1" id="pBatchRequired">
                                                <label class="form-check-label fw-bold small text-dark ms-1" for="pBatchRequired">
                                                    <i class="fas fa-boxes-stacked text-secondary me-1"></i> Batch Required
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label d-block text-secondary">Expiry Control</label>
                                            <div class="form-check form-switch pt-1">
                                                <input class="form-check-input" type="checkbox" role="switch" name="expiry_required" value="1" id="pExpiryRequired">
                                                <label class="form-check-label fw-bold small text-dark ms-1" for="pExpiryRequired">
                                                    <i class="fas fa-calendar-times text-danger me-1"></i> Expiry Required
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label d-block text-secondary">Serial Control</label>
                                            <div class="form-check form-switch pt-1">
                                                <input class="form-check-input" type="checkbox" role="switch" name="serial_required" value="1" id="pSerialRequired">
                                                <label class="form-check-label fw-bold small text-dark ms-1" for="pSerialRequired">
                                                    <i class="fas fa-barcode text-primary me-1"></i> Serial No. Required
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Item Photo (Optional)</label>
                                <input type="file" class="form-control" name="item_photo" id="pPhoto" accept="image/*">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Brief Description</label>
                                <textarea class="form-control" name="description" id="pDesc" rows="2" placeholder="Item details, specifications..."></textarea>
                            </div>

                            <!-- Item Packaging, Weight & Price Details Section -->
                            <div class="col-12">
                                <div class="border-top pt-3 mt-3 mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div>
                                        <h6 class="fw-bold text-primary mb-0"><i class="fas fa-boxes-packing me-2"></i>Packaging Variants & Price List (Multiple Pack Sizes / Varieties)</h6>
                                        <small class="text-muted d-block mt-1"><i class="fas fa-info-circle me-1 text-info"></i> Add multiple pack sizes/varieties (e.g. 10g, 50g, 500g Box) for this product with specific purchase prices & MRP.</small>
                                    </div>
                                    <button type="button" class="btn btn-outline-primary btn-sm rounded-2 fw-semibold" onclick="addUnitRow()"><i class="fas fa-plus me-1"></i> Add Packaging / Size</button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 22%;">Packaging Unit</th>
                                                <th style="width: 22%;">Pack Size / Weight (e.g. 500g)</th>
                                                <th style="width: 15%;">Qty / Pack</th>
                                                <th style="width: 18%;">Pur. Price (₹)</th>
                                                <th style="width: 18%;">MRP (₹)</th>
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
                <div class="modal-footer bg-light px-4">
                    <button type="button" class="btn btn-secondary px-4 rounded-3 fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success px-4 rounded-3 fw-bold" onclick="saveNewItem()">Save Item Master</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Add Category Modal -->
    <div class="modal fade" id="addCatModal" tabindex="-1" style="z-index: 2000;">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h6 class="modal-title fw-bold">Add New Category</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Category Name</label>
                        <input type="text" class="form-control" id="newCatName" placeholder="e.g. Fertilizers">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary btn-sm w-100 fw-bold" onclick="saveQuickCat()">Save Category</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Add SubCategory Modal -->
    <div class="modal fade" id="addSubCatModal" tabindex="-1" style="z-index: 2000;">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h6 class="modal-title fw-bold">Add New SubCategory</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Selected Category</label>
                        <input type="text" class="form-control" id="targetCatName" readonly disabled>
                        <input type="hidden" id="targetCatId">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">SubCategory Name</label>
                        <input type="text" class="form-control" id="newSubCatName" placeholder="e.g. Organic">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary btn-sm w-100 fw-bold" onclick="saveQuickSubCat()">Save SubCategory</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JS Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        var cachedUomOptions = '';

        $(document).ready(function () {
            fetchItemMaster();
            refreshCats();
            loadUomOptions();

            // Real-time filter events
            $("#filterSearch").on("keyup search", function () {
                fetchItemMaster();
            });

            // Live Item Name Duplicate / Similarity Check
            let itemNameCheckTimeout = null;
            $('#pItemName').on('input', function () {
                const val = $(this).val().trim();
                const itemId = $('#edit_item_id').val() || 0;
                const msgDiv = $('#itemNameCheckMsg');

                if (val.length < 2) {
                    msgDiv.html('');
                    $('#pItemName').removeClass('is-invalid is-valid');
                    return;
                }

                clearTimeout(itemNameCheckTimeout);
                itemNameCheckTimeout = setTimeout(function () {
                    $.ajax({
                        url: 'ajax_po.php',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'check_item_name',
                            name: val,
                            current_id: itemId
                        },
                        success: function (res) {
                            if (res && res.status === 'success') {
                                if (res.is_exact) {
                                    $('#pItemName').addClass('is-invalid').removeClass('is-valid');
                                    let itemSku = res.exact_item.sku ? ` (SKU: <b>${res.exact_item.sku}</b>)` : '';
                                    msgDiv.html(`<div class="alert alert-danger py-1 px-2 mb-0 small" style="font-size: 0.78rem;"><i class="fas fa-exclamation-triangle me-1"></i> Warning: Item with exact name already exists: <b>${res.exact_item.item_name}</b>${itemSku}</div>`);
                                } else if (res.count > 0) {
                                    $('#pItemName').removeClass('is-invalid is-valid');
                                    let matchList = res.matches.map(m => `<b>${m.name}</b>` + (m.sku ? ` (${m.sku})` : '')).join(', ');
                                    msgDiv.html(`<div class="alert alert-warning py-1 px-2 mb-0 small" style="font-size: 0.78rem;"><i class="fas fa-info-circle me-1"></i> Similar item(s) found in master: ${matchList}</div>`);
                                } else {
                                    $('#pItemName').removeClass('is-invalid').addClass('is-valid');
                                    msgDiv.html(`<div class="text-success small fw-semibold" style="font-size: 0.78rem;"><i class="fas fa-check-circle me-1"></i> Item Name is unique and available</div>`);
                                }
                            }
                        }
                    });
                }, 250);
            });

            // GST Input logic
            $('#pCgst, #pSgst').on('input', function () {
                let cgst = parseFloat($('#pCgst').val()) || 0;
                let sgst = parseFloat($('#pSgst').val()) || 0;
                if (cgst > 0 || sgst > 0) {
                    $('#pIgst').val('0.00');
                    $('#pSgst').prop('readonly', false);
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

        const unitOptions = `
            <option value="">Select UOM</option>
            <option value="BAG">Bag (BAG)</option>
            <option value="BOX">Box (BOX)</option>
            <option value="BTL">Bottle (BTL)</option>
            <option value="BDL">Bundle (BDL)</option>
            <option value="CAN">Can (CAN)</option>
            <option value="GM">Gram (GM)</option>
            <option value="KG">Kilogram (KG)</option>
            <option value="LTR">Litre (LTR)</option>
            <option value="NOS">Number (NOS)</option>
            <option value="PCS">Piece (PCS)</option>
            <option value="PKT">Packet (PKT)</option>
            <option value="SET">Set (SET)</option>
        `;

        function fetchItemMaster() {
            const search = $("#filterSearch").val();
            const categoryId = $("#filterCategory").val();
            const status = $("#filterStatus").val();

            $.ajax({
                url: 'ajax_po.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'list_item_master',
                    search: search,
                    category_id: categoryId,
                    status: status
                },
                success: function (res) {
                    if (res && res.rows) {
                        $('#itemsTableBody').html(res.rows);
                    }
                    if (res && res.stats) {
                        $('#kpiTotalItems').text(res.stats.total || 0);
                        $('#kpiActiveItems').text(res.stats.active || 0);
                        $('#kpiTotalCategories').text(res.stats.categories || 0);
                        $('#recordCountBadge').text((res.stats.total || 0) + ' Records');
                    }
                },
                error: function (xhr, status, error) {
                    console.error("fetchItemMaster Error:", status, error, xhr.responseText);
                    $('#itemsTableBody').html(`<tr><td colspan="6" class="text-center text-danger py-4"><i class="fas fa-exclamation-triangle me-1"></i> Error loading data. Please check server logs or refresh.</td></tr>`);
                }
            });
        }

        function resetFilters() {
            $("#filterSearch").val('');
            $("#filterCategory").val('');
            $("#filterStatus").val('');
            fetchItemMaster();
        }

        function addUnitRow(data = null) {
            let rowId = Date.now() + Math.random().toString().replace('.', '');
            let optionsHtml = (cachedUomOptions && cachedUomOptions.trim() !== '') ? cachedUomOptions : unitOptions;
            let defaultSelectedUom = data ? data.unit : ($('#pPurchaseUom').val() || $('#pBaseUom').val() || '');

            let html = `
            <tr id="unit_row_${rowId}">
                <td>
                    <select class="form-select form-select-sm u-unit-select" name="u_unit[]" required>
                        ${optionsHtml}
                    </select>
                </td>
                <td><input type="text" class="form-control form-control-sm" name="u_size[]" value="${data ? data.unit_size : ''}" placeholder="e.g. 10g, 500g, 1 Box"></td>
                <td><input type="number" step="0.01" class="form-control form-control-sm" name="u_pack_qty[]" value="${data ? data.pack_qty : '1.00'}"></td>
                <td><input type="number" step="0.01" class="form-control form-control-sm" name="u_pur_price[]" value="${data ? data.purchase_price : '0.00'}"></td>
                <td><input type="number" step="0.01" class="form-control form-control-sm" name="u_mrp[]" value="${data ? data.mrp : '0.00'}"></td>
                <td class="text-center">
                    <button type="button" class="btn btn-link btn-sm text-danger p-0 fw-bold fs-6" onclick="$('#unit_row_${rowId}').remove()"><i class="fas fa-times"></i></button>
                </td>
            </tr>`;
            $('#unitVariationRows').append(html);
            if (defaultSelectedUom) {
                $('#unit_row_' + rowId).find('select[name="u_unit[]"]').val(defaultSelectedUom);
            }
        }

        function openNewItemModal() {
            $('#newItemForm')[0].reset();
            $('#edit_item_id').val('');
            $('#pItemName').removeClass('is-invalid is-valid');
            $('#itemNameCheckMsg').html('');
            $('#newItemSubCat').html('<option value="">Select Category First</option>');
            $('#pBaseUom').val('');
            $('#pPurchaseUom').val('');
            $('#pSaleUom').val('');
            $('#pConversionFactor').val('1.0000');
            $('#pMinStock').val('0.00');
            $('#pBatchRequired').prop('checked', false);
            $('#pExpiryRequired').prop('checked', false);
            $('#pSerialRequired').prop('checked', false);
            $('#unitVariationRows').empty();
            $('#pSgst').prop('readonly', false);
            addUnitRow();
            $('#modalTitle').html('<i class="fas fa-plus-circle me-2"></i>Add New Purchase Item');
            var modal = bootstrap.Modal.getInstance(document.getElementById('newItemModal')) || new bootstrap.Modal(document.getElementById('newItemModal'));
            modal.show();
        }

        function loadUomOptions() {
            $.post('ajax_po.php', { action: 'list_uoms' }, function (res) {
                if (res && res.options) {
                    cachedUomOptions = res.options;
                    $('#pBaseUom, #pPurchaseUom, #pSaleUom').html(res.options);
                    $('.u-unit-select').each(function () {
                        let currVal = $(this).val();
                        $(this).html(res.options).val(currVal);
                    });
                }
            }, 'json');
        }

        function editItem(id) {
            $.post('ajax_po.php', { action: 'get_item_details', item_id: id }, function (res) {
                if (res.status === 'success') {
                    let item = res.data;
                    $('#edit_item_id').val(item.item_id);
                    $('#pItemName').val(item.item_name).removeClass('is-invalid is-valid');
                    $('#itemNameCheckMsg').html('');
                    $('#pSku').val(item.sku);
                    $('#newItemCat').val(item.category_id).trigger('change');

                    setTimeout(() => {
                        $('#newItemSubCat').val(item.subcategory_id).trigger('change');
                    }, 500);

                    $('#pHsn').val(item.hsn_code);
                    $('#pCgst').val(item.cgst);
                    $('#pSgst').val(item.sgst);
                    $('#pIgst').val(item.igst);
                    $('#pPhoto').val('');
                    $('#pMinStock').val(item.min_stock);
                    $('#pBatchRequired').prop('checked', parseInt(item.batch_required) === 1);
                    $('#pExpiryRequired').prop('checked', parseInt(item.expiry_required) === 1);
                    $('#pSerialRequired').prop('checked', parseInt(item.serial_required) === 1);
                    $('#pDesc').val(item.description);

                    // Set UOM master fields
                    $('#pBaseUom').val(item.base_uom);
                    $('#pPurchaseUom').val(item.purchase_uom);
                    $('#pSaleUom').val(item.sale_uom);
                    $('#pConversionFactor').val(item.conversion_factor || '1.0000');

                    $('#pCgst').trigger('input');

                    $('#unitVariationRows').empty();
                    if (item.units && item.units.length > 0) {
                        item.units.forEach(u => addUnitRow(u));
                    } else {
                        addUnitRow();
                    }

                    $('#modalTitle').html('<i class="fas fa-edit me-2"></i>Edit Purchase Item (#' + item.item_id + ')');
                    var modal = bootstrap.Modal.getInstance(document.getElementById('newItemModal')) || new bootstrap.Modal(document.getElementById('newItemModal'));
                    modal.show();
                }
            }, 'json');
        }

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
                        var modalEl = document.getElementById('newItemModal');
                        var modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();
                        form[0].reset();
                        fetchItemMaster();
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }
            });
        }

        function openAddCatModal() {
            $('#newCatName').val('');
            var modal = bootstrap.Modal.getInstance(document.getElementById('addCatModal')) || new bootstrap.Modal(document.getElementById('addCatModal'));
            modal.show();
        }

        function saveQuickCat() {
            let name = $('#newCatName').val().trim();
            if (!name) return;
            $.post('ajax_po.php', { action: 'save_quick_cat', name: name }, function (res) {
                if (res.status === 'success') {
                    Swal.fire('Added', 'Category created', 'success');
                    var modal = bootstrap.Modal.getInstance(document.getElementById('addCatModal'));
                    if (modal) modal.hide();
                    refreshCats(res.new_id);
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            }, 'json');
        }

        function refreshCats(selectedId = null) {
            $.post('ajax_po.php', { action: 'list_cats' }, function (res) {
                $('#newItemCat').html(res.options);
                $('#filterCategory').html('<option value="">All Categories</option>' + res.options);
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
            var modal = bootstrap.Modal.getInstance(document.getElementById('addSubCatModal')) || new bootstrap.Modal(document.getElementById('addSubCatModal'));
            modal.show();
        }

        function saveQuickSubCat() {
            let name = $('#newSubCatName').val().trim();
            let catId = $('#targetCatId').val();
            if (!name || !catId) return;
            $.post('ajax_po.php', { action: 'save_quick_subcat', name: name, category_id: catId }, function (res) {
                if (res.status === 'success') {
                    Swal.fire('Added', 'SubCategory created', 'success');
                    var modal = bootstrap.Modal.getInstance(document.getElementById('addSubCatModal'));
                    if (modal) modal.hide();
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

        function toggleItemStatus(itemId, newStatus) {
            let actionText = newStatus === 'Active' ? 'activate' : 'deactivate';
            let btnColor   = newStatus === 'Active' ? '#10b981' : '#f59e0b';
            Swal.fire({
                title: (newStatus === 'Active' ? 'Activate Item?' : 'Deactivate Item?'),
                text: `Are you sure you want to ${actionText} this item?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: btnColor,
                cancelButtonColor: '#6b7280',
                confirmButtonText: `Yes, ${actionText}`
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('ajax_po.php', { action: 'toggle_item_status', item_id: itemId, status: newStatus }, function (res) {
                        if (res.status === 'success') {
                            Swal.fire({ icon: 'success', title: 'Updated', text: res.message, timer: 1500, showConfirmButton: false });
                            fetchItemMaster();
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    }, 'json');
                }
            });
        }

        function deleteItemMaster(itemId, itemName) {
            Swal.fire({
                title: 'Delete Item?',
                text: `Are you sure you want to permanently delete "${itemName || 'this item'}"? This action cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete permanently'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('ajax_po.php', { action: 'delete_item_master', item_id: itemId }, function (res) {
                        if (res.status === 'success') {
                            Swal.fire({ icon: 'success', title: 'Deleted', text: res.message, timer: 1500, showConfirmButton: false });
                            fetchItemMaster();
                        } else {
                            Swal.fire('Cannot Delete', res.message, 'error');
                        }
                    }, 'json');
                }
            });
        }
    </script>
</body>

</html>
