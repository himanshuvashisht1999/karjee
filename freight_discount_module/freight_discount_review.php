<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$key = $_GET['k'] ?? '';
$mdid = $_GET['mdid'] ?? '';
$isDz = isset($_GET['dz']);

// Suppress duplicate headers/strips from header.php
$hide_header = true;
$hide_heading = true;

if ($isDz) {
    require_once(__DIR__ . '/../connection.php');
    require_once(__DIR__ . '/../functions/function_master.php');
    if (!empty($key)) {
        $key_decoded = base64url_decode($key);
        $key_parts = explode("|", $key_decoded);
        $loginid = $key_parts[0] ?? '';
        $loginroleid = $key_parts[1] ?? '';
        $companyid = $key_parts[2] ?? 1;
        $subcompanyid = $key_parts[3] ?? 1;
        $projectid = $key_parts[4] ?? 1;
    }
} else {
    require_once(__DIR__ . '/../header.php');
}
?>
<!-- Styles and Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<style>
/* Suppress duplicate top navbar & dashboard strip */
.navbar-premium,
.company-header-strip,
body > nav.navbar,
body > .company-header-strip {
    display: none !important;
}

body {
    background-color: #f8fafc !important;
    padding-bottom: 20px !important;
}

:root {
    --fd-primary: #10b981;
    --fd-primary-dark: #059669;
    --fd-dealer: #2563eb;
    --fd-dealer-bg: #eff6ff;
    --fd-dealer-border: #bfdbfe;
    --fd-farmer: #059669;
    --fd-farmer-bg: #ecfdf5;
    --fd-farmer-border: #a7f3d0;
    --fd-grafted: #7c3aed;
    --fd-grafted-bg: #f5f3ff;
    --fd-grafted-border: #ddd6fe;
    --fd-nongrafted: #d97706;
    --fd-nongrafted-bg: #fffbeb;
    --fd-nongrafted-border: #fde68a;
    --fd-border: #e2e8f0;
    --fd-text-main: #1e293b;
    --fd-text-muted: #64748b;
}

.fd-wrapper {
    padding: 16px 20px;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    color: var(--fd-text-main);
    background-color: transparent;
}

/* Header Card (Light Aesthetic) */
.fd-header-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 18px 24px;
    color: #1e293b;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.04);
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}

.fd-header-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

.fd-header-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #e0f2fe 0%, #dbeafe 100%);
    border: 1px solid #bae6fd;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    color: #0284c7;
    box-shadow: 0 2px 6px rgba(2, 132, 199, 0.12);
}

.fd-header-card h2 {
    font-family: 'Outfit', sans-serif;
    font-size: 20px;
    font-weight: 700;
    margin: 0;
    color: #0f172a;
    letter-spacing: -0.01em;
}

.fd-header-card p {
    margin: 3px 0 0 0;
    font-size: 13px;
    color: #64748b;
}

.fd-header-right {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-left: auto;
    flex-wrap: wrap;
}

.btn-calc-light {
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    color: #334155;
    font-weight: 600;
    font-size: 13px;
    border-radius: 8px;
    padding: 8px 14px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s ease;
}
.btn-calc-light:hover {
    background: #f1f5f9;
    border-color: #94a3b8;
    color: #0f172a;
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(0,0,0,0.05);
}

/* Animated Add New Plan Button */
@keyframes fdPulseShadow {
    0% {
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.45), 0 4px 12px rgba(16, 185, 129, 0.25);
    }
    70% {
        box-shadow: 0 0 0 9px rgba(16, 185, 129, 0), 0 4px 12px rgba(16, 185, 129, 0.25);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0), 0 4px 12px rgba(16, 185, 129, 0.25);
    }
}

@keyframes fdShine {
    0% { left: -75%; }
    25% { left: 125%; }
    100% { left: 125%; }
}

.btn-animated-add-plan {
    position: relative;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
    color: #ffffff !important;
    border: none !important;
    border-radius: 9px !important;
    padding: 8px 18px !important;
    font-size: 13.5px !important;
    font-weight: 600 !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 7px !important;
    cursor: pointer;
    animation: fdPulseShadow 2.4s infinite cubic-bezier(0.4, 0, 0.6, 1);
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-animated-add-plan::before {
    content: '';
    position: absolute;
    top: 0;
    left: -75%;
    width: 50%;
    height: 100%;
    background: linear-gradient(120deg, transparent, rgba(255, 255, 255, 0.35), transparent);
    transform: skewX(-25deg);
    animation: fdShine 3.5s infinite;
}

.btn-animated-add-plan:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(16, 185, 129, 0.45) !important;
    background: linear-gradient(135deg, #059669 0%, #047857 100%) !important;
    color: #ffffff !important;
    text-decoration: none;
}

.btn-animated-add-plan:active {
    transform: translateY(0);
}

.btn-animated-add-plan .plan-icon-spin {
    transition: transform 0.3s ease;
}

.btn-animated-add-plan:hover .plan-icon-spin {
    transform: rotate(90deg) scale(1.15);
}

/* KPI Summary Cards Grid */
.fd-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}

.fd-kpi-card {
    background: #ffffff;
    border: 1px solid var(--fd-border);
    border-radius: 12px;
    padding: 16px 18px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    display: flex;
    align-items: center;
    gap: 14px;
    transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
    cursor: pointer;
}

.fd-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.06);
    border-color: #cbd5e1;
}

.fd-kpi-icon {
    width: 46px;
    height: 46px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}

.fd-kpi-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    color: var(--fd-text-muted);
    letter-spacing: 0.5px;
    margin-bottom: 2px;
}

.fd-kpi-value {
    font-size: 22px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
}

.fd-kpi-sub {
    font-size: 11.5px;
    color: var(--fd-text-muted);
    margin-top: 4px;
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}

/* Main Table Container */
.fd-card {
    background: #ffffff;
    border: 1px solid var(--fd-border);
    border-radius: 14px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.03);
    overflow: hidden;
}

.fd-card-header {
    background: #ffffff;
    padding: 16px 20px;
    border-bottom: 1px solid var(--fd-border);
}

.fd-filters-wrap {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

/* Fixed Search Box without Icon Overlap */
.fd-search-box {
    position: relative;
    min-width: 280px;
    flex: 1;
}

.fd-search-box i.search-icon {
    position: absolute !important;
    left: 15px !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    color: #94a3b8 !important;
    font-size: 15px !important;
    pointer-events: none !important;
    z-index: 10 !important;
    margin: 0 !important;
}

.fd-search-box input,
.fd-search-box input[type="text"],
.fd-search-box input[placeholder*='Search' i],
.fd-search-box input[placeholder*='search' i],
#filter-search {
    width: 100% !important;
    height: 38px !important;
    padding: 8px 14px 8px 44px !important;
    border: 1.5px solid var(--fd-border) !important;
    border-radius: 8px !important;
    font-size: 13px !important;
    color: #1e293b !important;
    background: #ffffff !important;
    transition: all 0.2s ease !important;
    outline: none !important;
    line-height: normal !important;
}

.fd-search-box input:focus,
#filter-search:focus {
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
    background: #ffffff !important;
}

.fd-search-box input::placeholder,
#filter-search::placeholder {
    color: #94a3b8 !important;
    font-size: 12.5px !important;
    opacity: 1 !important;
}

.fd-select {
    height: 38px;
    padding: 6px 12px;
    border: 1.5px solid var(--fd-border);
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    color: #334155;
    background-color: #fff;
    cursor: pointer;
    min-width: 155px;
    outline: none;
    transition: border-color 0.2s;
}

.fd-select:focus {
    border-color: #3b82f6;
}

/* Badges */
.badge-customer {
    font-size: 11px;
    font-weight: 700;
    padding: 4px 8px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
}
.badge-dealer { background: var(--fd-dealer-bg); color: var(--fd-dealer); border: 1px solid var(--fd-dealer-border); }
.badge-farmer { background: var(--fd-farmer-bg); color: var(--fd-farmer); border: 1px solid var(--fd-farmer-border); }
.badge-all-cust { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }

.badge-crop {
    font-size: 11px;
    font-weight: 700;
    padding: 4px 8px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
}
.badge-grafted { background: var(--fd-grafted-bg); color: var(--fd-grafted); border: 1px solid var(--fd-grafted-border); }
.badge-nongrafted { background: var(--fd-nongrafted-bg); color: var(--fd-nongrafted); border: 1px solid var(--fd-nongrafted-border); }
.badge-both-crops { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }

/* Table matching screenshot */
.fd-table {
    width: 100%;
    margin-bottom: 0;
    border-collapse: collapse;
}

.fd-table thead th {
    background: #e0f2fe !important;
    color: #0369a1 !important;
    font-size: 11px !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    padding: 12px 14px !important;
    border: 1px solid #bae6fd !important;
    border-bottom: 2px solid #7dd3fc !important;
    white-space: nowrap !important;
    vertical-align: middle !important;
}

.fd-table tbody td {
    padding: 12px 14px !important;
    vertical-align: middle !important;
    border: 1px solid #e2e8f0 !important;
    font-size: 12.5px !important;
    color: #1e293b !important;
}

.fd-table tbody tr:hover {
    background-color: #f8fafc !important;
}

.plan-code-badge {
    font-family: monospace;
    font-weight: 700;
    font-size: 11.5px;
    color: #0284c7;
    background: #e0f2fe;
    border: 1px solid #bae6fd;
    padding: 2px 6px;
    border-radius: 4px;
    display: inline-block;
    white-space: nowrap;
}

.plan-title-link {
    font-weight: 600;
    color: #0f172a;
    text-decoration: none;
    display: block;
    margin-top: 2px;
}

.plan-title-link:hover {
    color: #2563eb;
    text-decoration: underline;
}

.btn-action-icon {
    width: 30px;
    height: 30px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    font-size: 13px;
    transition: all 0.15s;
}

.empty-state {
    padding: 40px 20px;
    text-align: center;
    color: #64748b;
}
.empty-state i { font-size: 40px; color: #cbd5e1; margin-bottom: 10px; display: block; }
</style>

<div class="fd-wrapper">

    <!-- Page Header -->
    <div class="fd-header-card">
        <div class="fd-header-left">
            <div class="fd-header-icon">
                <i class="fa fa-truck"></i>
            </div>
            <div>
                <h2>Freight Discount Plan Master</h2>
                <p>Maintain separate freight discount rules for Grafted &amp; Non-Grafted crops across Dealers &amp; Farmers</p>
            </div>
        </div>
        <div class="fd-header-right">
            <button type="button" class="btn btn-calc-light shadow-sm" onclick="openDiscountSimulatorModal()">
                <i class="fa fa-calculator text-primary"></i> Discount Calculator
            </button>
            <button type="button" class="btn-animated-add-plan" onclick="openAddPlanModal()">
                <i class="fa fa-plus-circle plan-icon-spin"></i> Add New Plan
            </button>
        </div>
    </div>

    <!-- KPI Summary Stats Grid -->
    <div class="fd-kpi-grid">
        <div class="fd-kpi-card" onclick="filterBySegment('All', 'All')" title="Click to view all plans">
            <div class="fd-kpi-icon" style="background:#f1f5f9; color:#475569;">
                <i class="fa fa-layer-group"></i>
            </div>
            <div class="fd-kpi-info">
                <div class="fd-kpi-label">Total Plans</div>
                <div class="fd-kpi-value" id="kpi-total-plans">0</div>
                <div class="fd-kpi-sub"><span class="text-success font-weight-bold" id="kpi-active-plans">0</span> Active in System</div>
            </div>
        </div>

        <div class="fd-kpi-card" onclick="filterBySegment('Dealer', 'All')" title="Click to filter Dealer plans">
            <div class="fd-kpi-icon" style="background:#eff6ff; color:#2563eb;">
                <i class="fa fa-store"></i>
            </div>
            <div class="fd-kpi-info">
                <div class="fd-kpi-label">Dealer Plans</div>
                <div class="fd-kpi-value" id="kpi-dealer-total">0</div>
                <div class="fd-kpi-sub">
                    <span class="badge-crop badge-grafted" id="kpi-dealer-grafted">0 Grafted</span>
                    <span class="badge-crop badge-nongrafted" id="kpi-dealer-nongrafted">0 Non-Graft</span>
                </div>
            </div>
        </div>

        <div class="fd-kpi-card" onclick="filterBySegment('Farmer', 'All')" title="Click to filter Farmer plans">
            <div class="fd-kpi-icon" style="background:#ecfdf5; color:#059669;">
                <i class="fa fa-tractor"></i>
            </div>
            <div class="fd-kpi-info">
                <div class="fd-kpi-label">Farmer Plans</div>
                <div class="fd-kpi-value" id="kpi-farmer-total">0</div>
                <div class="fd-kpi-sub">
                    <span class="badge-crop badge-grafted" id="kpi-farmer-grafted">0 Grafted</span>
                    <span class="badge-crop badge-nongrafted" id="kpi-farmer-nongrafted">0 Non-Graft</span>
                </div>
            </div>
        </div>

        <div class="fd-kpi-card" onclick="filterBySegment('All', 'Grafted')" title="Click to filter Grafted crops">
            <div class="fd-kpi-icon" style="background:#f5f3ff; color:#7c3aed;">
                <i class="fa fa-seedling"></i>
            </div>
            <div class="fd-kpi-info">
                <div class="fd-kpi-label">Grafted vs Non-Grafted</div>
                <div class="fd-kpi-value" id="kpi-grafted-total">0</div>
                <div class="fd-kpi-sub">Active Crop Schemes</div>
            </div>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="fd-card">
        <div class="fd-card-header">
            <div class="fd-filters-wrap">
                <!-- Search Box -->
                <div class="fd-search-box">
                    <i class="fa fa-search search-icon"></i>
                    <input type="text" id="filter-search" placeholder="Search plan title, code, state, district..." oninput="debounceFetchPlans()">
                </div>

                <!-- Customer Type Filter -->
                <select id="filter-customer" class="fd-select" onchange="fetchPlans()">
                    <option value="All">All Customer Types</option>
                    <option value="Dealer">🏢 Dealer Plans Only</option>
                    <option value="Farmer">👨‍🌾 Farmer Plans Only</option>
                </select>

                <!-- Plant Type Filter -->
                <select id="filter-plant" class="fd-select" onchange="fetchPlans()">
                    <option value="All">All Crop Types</option>
                    <option value="Grafted">🌿 Grafted Crops</option>
                    <option value="Nongrafted">🌱 Non-Grafted Crops</option>
                    <option value="Both">🌿+🌱 Both Categories</option>
                </select>

                <!-- Status Filter -->
                <select id="filter-status" class="fd-select" onchange="fetchPlans()">
                    <option value="All">All Statuses</option>
                    <option value="Active" selected>🟢 Active Only</option>
                    <option value="Inactive">⚪ Inactive</option>
                    <option value="Expired">🔴 Expired</option>
                </select>

                <!-- Action Buttons -->
                <button type="button" class="btn btn-outline-secondary btn-sm fw-bold px-3 py-2" onclick="resetFilters()" title="Reset Filters">
                    <i class="fa fa-undo mr-1"></i> Reset
                </button>
                <button type="button" class="btn btn-outline-success btn-sm fw-bold px-3 py-2 ml-auto" onclick="exportPlansExcel()" title="Export Plans to Excel">
                    <i class="fa fa-file-excel mr-1"></i> Export Excel
                </button>
            </div>
        </div>

        <!-- Table Responsive -->
        <div class="table-responsive">
            <table class="fd-table" id="plansTable">
                <thead>
                    <tr>
                        <th width="45" class="text-center">#</th>
                        <th style="min-width:200px;">Plan Details</th>
                        <th style="min-width:110px;">Customer</th>
                        <th style="min-width:120px;">Crop Category</th>
                        <th style="min-width:130px;">Discount Rule</th>
                        <th style="min-width:130px;">Qualifications</th>
                        <th style="min-width:130px;">Region</th>
                        <th style="min-width:130px;">Validity</th>
                        <th width="90" class="text-center">Status</th>
                        <th width="110" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="plansTableBody">
                    <tr>
                        <td colspan="10" class="empty-state">
                            <div class="spinner-border text-primary mb-2" role="status"></div>
                            <div>Loading freight discount plans...</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal: Add / Edit Plan -->
<div class="modal fade" id="planModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
            <div class="modal-header bg-dark text-white p-3 px-4" style="border-radius: 14px 14px 0 0;">
                <h5 class="modal-title font-weight-bold d-flex align-items-center gap-2" id="planModalTitle">
                    <i class="fa fa-plus-circle text-success mr-2"></i> Add Freight Discount Plan
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="modalPlanForm" onsubmit="event.preventDefault(); submitModalPlan();">
                <input type="hidden" name="id" id="m_plan_id" value="0">
                <input type="hidden" name="companyid" id="m_companyid" value="">
                <input type="hidden" name="subcompanyid" id="m_subcompanyid" value="">
                <input type="hidden" name="projectid" id="m_projectid" value="">
                <input type="hidden" name="loginname" id="m_loginname" value="">
                <input type="hidden" name="plan_name" id="m_plan_name" value="">
                <input type="hidden" name="plan_code" id="m_plan_code" value="">
                <input type="hidden" name="statename" id="m_statename" value="All States">
                <input type="hidden" name="districtname" id="m_districtname" value="All Districts">

                <div class="modal-body p-4" style="max-height:75vh; overflow-y:auto;">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold small text-muted">Customer Segment <span class="text-danger">*</span></label>
                            <select class="form-control form-select" name="customer_type" id="m_customer_type" required onchange="autoGenerateModalPlanNameAndCode()">
                                <option value="Farmer" selected>👨‍🌾 Farmer (Individual Plan)</option>
                                <option value="Dealer">🏢 Dealer (Individual Plan)</option>
                                <option value="All">🌐 All Customers (Both Dealer &amp; Farmer)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold small text-muted">Crop / Plant Category <span class="text-danger">*</span></label>
                            <select class="form-control form-select" name="plant_type" id="m_plant_type" required onchange="autoGenerateModalPlanNameAndCode()">
                                <option value="Grafted" selected>🌿 Grafted Plants</option>
                                <option value="Nongrafted">🌱 Non-Grafted Plants</option>
                                <option value="Both">🌿+🌱 Both Categories</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold small text-muted">Discount Rule Type <span class="text-danger">*</span></label>
                            <select class="form-control form-select" name="discount_type" id="m_discount_type" required onchange="handleModalDiscountTypeChange()">
                                <option value="Percentage">Percentage (%) on Freight</option>
                                <option value="Fixed_Amount">Flat Fixed Amount (₹)</option>
                                <option value="Per_Plant">Per Plant Subsidy (₹/Plant)</option>
                                <option value="Per_KM">Per KM Subsidy (₹/KM)</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold small text-muted">Discount Value <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0.01" class="form-control" name="discount_value" id="m_discount_value" value="15.00" required>
                                <div class="input-group-append">
                                    <span class="input-group-text font-weight-bold" id="m_discount_unit">%</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold small text-muted">Max Discount Cap (₹) <small>(0 = Unlimited)</small></label>
                            <input type="number" step="0.01" min="0" class="form-control" name="max_discount_amount" id="m_max_discount_amount" value="0.00">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="font-weight-bold small text-muted">Min Order Qty (Plants)</label>
                            <input type="number" min="0" class="form-control" name="min_order_qty" id="m_min_order_qty" value="1000" placeholder="0 = No Min">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="font-weight-bold small text-muted">Max Order Qty (Plants)</label>
                            <input type="number" min="0" class="form-control" name="max_order_qty" id="m_max_order_qty" value="0" placeholder="0 = Unlimited">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="font-weight-bold small text-muted">Min Distance (KM)</label>
                            <input type="number" min="0" step="1" class="form-control" name="min_distance_km" id="m_min_distance_km" value="0" placeholder="0 = No Min KM">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="font-weight-bold small text-muted">Min Freight Amount (₹)</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="min_freight_amount" id="m_min_freight_amount" value="1000.00">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold small text-muted">Applicable State</label>
                            <select class="form-control form-select" name="stateid" id="m_stateid" onchange="handleModalStateChange()">
                                <option value="ALL">All States (Pan-India)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold small text-muted">Applicable District</label>
                            <select class="form-control form-select" name="districtid" id="m_districtid" onchange="handleModalDistrictChange()">
                                <option value="ALL">All Districts</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold small text-muted">Valid From <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="valid_from" id="m_valid_from" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold small text-muted">Valid To <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="valid_to" id="m_valid_to" value="<?php echo date('Y-m-d', strtotime('+1 year')); ?>" required>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="font-weight-bold small text-muted">Status</label>
                            <select class="form-control form-select" name="status" id="m_status">
                                <option value="Active" selected>Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="font-weight-bold small text-muted">Priority</label>
                            <input type="number" class="form-control" name="priority" id="m_priority" value="5" min="1">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="font-weight-bold small text-muted">Plan Description</label>
                        <textarea class="form-control" name="description" id="m_description" rows="2" placeholder="Notes regarding this discount scheme..."></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light p-3 px-4" style="border-radius: 0 0 14px 14px;">
                    <button type="button" class="btn btn-secondary btn-sm font-weight-bold" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm font-weight-bold px-4" id="btn-modal-save">
                        <i class="fa fa-save mr-1"></i> Save Plan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: View Plan Breakdown -->
<div class="modal fade" id="planDetailsModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
            <div class="modal-header bg-dark text-white p-3 px-4" style="border-radius: 14px 14px 0 0;">
                <h5 class="modal-title font-weight-bold d-flex align-items-center gap-2">
                    <i class="fa fa-info-circle text-info mr-2"></i> Plan Specifications &amp; Breakdown
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4" id="planDetailsModalBody">
                <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
            </div>
            <div class="modal-footer bg-light p-3 px-4" style="border-radius: 0 0 14px 14px;">
                <button type="button" class="btn btn-secondary btn-sm font-weight-bold" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary btn-sm font-weight-bold" id="btn-modal-edit-action">
                    <i class="fa fa-edit mr-1"></i> Edit Plan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Live Discount Simulator -->
<div class="modal fade" id="simulatorModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
            <div class="modal-header bg-primary text-white p-3 px-4" style="border-radius: 14px 14px 0 0;">
                <h5 class="modal-title font-weight-bold d-flex align-items-center gap-2">
                    <i class="fa fa-calculator mr-2"></i> Freight Discount Test Calculator
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-3">Simulate and verify how discount plans match and calculate discounts on orders.</p>
                <form id="simForm" onsubmit="event.preventDefault(); runSimulation();">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold small text-muted">Customer Segment</label>
                            <select class="form-control" id="sim-customer-type">
                                <option value="Farmer">👨‍🌾 Farmer</option>
                                <option value="Dealer">🏢 Dealer</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold small text-muted">Crop / Plant Category</label>
                            <select class="form-control" id="sim-plant-type">
                                <option value="Grafted">🌿 Grafted Plants</option>
                                <option value="Nongrafted">🌱 Non-Grafted Plants</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold small text-muted">Order Plant Qty (Pcs)</label>
                            <input type="number" class="form-control" id="sim-order-qty" value="5000" min="1">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold small text-muted">Estimated Freight Charges (₹)</label>
                            <input type="number" class="form-control" id="sim-freight-amount" value="3500" min="0" step="0.01">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="font-weight-bold small text-muted">Delivery Distance (KM)</label>
                        <input type="number" class="form-control" id="sim-distance-km" value="120" min="0">
                    </div>
                    <button type="submit" class="btn btn-primary btn-block font-weight-bold">
                        <i class="fa fa-bolt mr-1"></i> Calculate Discount
                    </button>
                </form>

                <div id="simResult" class="mt-4" style="display:none;"></div>
            </div>
        </div>
    </div>
</div>

<script>
var key = "<?php echo $key; ?>";
var mdid = "<?php echo $mdid; ?>";
var isDz = <?php echo $isDz ? 'true' : 'false'; ?>;
var queryParams = "k=" + encodeURIComponent(key) + "&mdid=" + encodeURIComponent(mdid) + (isDz ? "&dz=1" : "");

var rawPlansData = [];
var searchTimer = null;
var globalStates = [];

$(document).ready(function() {
    loadGlobalStates();
    fetchPlans();
});

function loadGlobalStates() {
    $.ajax({
        url: 'modal/freight_discount_modal.php?action=get_states',
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            if (res.status && res.data) {
                globalStates = res.data;
                var opts = '<option value="ALL">All States (Pan-India)</option>';
                res.data.forEach(function(s) {
                    opts += '<option value="' + s.stateid + '">' + s.statename + '</option>';
                });
                $('#m_stateid').html(opts);
            }
        }
    });
}

function debounceFetchPlans() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(fetchPlans, 300);
}

function fetchPlans() {
    var search = $('#filter-search').val();
    var custType = $('#filter-customer').val();
    var plantType = $('#filter-plant').val();
    var status = $('#filter-status').val();

    $.ajax({
        url: 'modal/freight_discount_modal.php?action=fetch_plans',
        type: 'POST',
        data: {
            search: search,
            customer_type: custType,
            plant_type: plantType,
            status: status
        },
        dataType: 'json',
        success: function(res) {
            if (res.status) {
                rawPlansData = res.data.plans || [];
                renderTable(rawPlansData);
                renderKPIs(res.data.stats || {});
            } else {
                $('#plansTableBody').html('<tr><td colspan="10" class="empty-state"><i class="fa fa-exclamation-triangle text-danger"></i><div>' + (res.message || 'Error loading plans') + '</div></td></tr>');
            }
        },
        error: function() {
            $('#plansTableBody').html('<tr><td colspan="10" class="empty-state"><i class="fa fa-network-wired text-danger"></i><div>Network error loading discount plans.</div></td></tr>');
        }
    });
}

function renderKPIs(stats) {
    var total = parseInt(stats.total_plans || 0);
    var active = parseInt(stats.active_plans || 0);
    var dlrGraft = parseInt(stats.dealer_grafted || 0);
    var dlrNong = parseInt(stats.dealer_nongrafted || 0);
    var frmGraft = parseInt(stats.farmer_grafted || 0);
    var frmNong = parseInt(stats.farmer_nongrafted || 0);

    $('#kpi-total-plans').text(total);
    $('#kpi-active-plans').text(active);
    $('#kpi-dealer-total').text(dlrGraft + dlrNong);
    $('#kpi-dealer-grafted').text(dlrGraft + ' Grafted');
    $('#kpi-dealer-nongrafted').text(dlrNong + ' Non-Graft');

    $('#kpi-farmer-total').text(frmGraft + frmNong);
    $('#kpi-farmer-grafted').text(frmGraft + ' Grafted');
    $('#kpi-farmer-nongrafted').text(frmNong + ' Non-Graft');

    $('#kpi-grafted-total').text((dlrGraft + frmGraft) + ' Grafted / ' + (dlrNong + frmNong) + ' Non-Graft');
}

function renderTable(plans) {
    if (!plans || plans.length === 0) {
        $('#plansTableBody').html('<tr><td colspan="10" class="empty-state"><i class="fa fa-folder-open"></i><div>No freight discount plans found matching the filters.</div></td></tr>');
        return;
    }

    var html = '';
    plans.forEach(function(p, index) {
        // Customer Badge
        var custBadge = '';
        if (p.customer_type === 'Dealer') {
            custBadge = '<span class="badge-customer badge-dealer"><i class="fa fa-store"></i> Dealer</span>';
        } else if (p.customer_type === 'Farmer') {
            custBadge = '<span class="badge-customer badge-farmer"><i class="fa fa-tractor"></i> Farmer</span>';
        } else {
            custBadge = '<span class="badge-customer badge-all-cust"><i class="fa fa-users"></i> All</span>';
        }

        // Crop Badge
        var cropBadge = '';
        if (p.plant_type === 'Grafted') {
            cropBadge = '<span class="badge-crop badge-grafted"><i class="fa fa-seedling"></i> Grafted</span>';
        } else if (p.plant_type === 'Nongrafted') {
            cropBadge = '<span class="badge-crop badge-nongrafted"><i class="fa fa-leaf"></i> Non-Graft</span>';
        } else {
            cropBadge = '<span class="badge-crop badge-both-crops"><i class="fa fa-check-double"></i> Both</span>';
        }

        // Discount Value
        var discText = '';
        var discVal = parseFloat(p.discount_value || 0);
        if (p.discount_type === 'Percentage') {
            discText = '<span class="font-weight-bold text-success" style="font-size:14px;">' + discVal + '% OFF</span>';
        } else if (p.discount_type === 'Fixed_Amount') {
            discText = '<span class="font-weight-bold text-primary" style="font-size:14px;">₹' + discVal.toFixed(2) + ' Flat</span>';
        } else if (p.discount_type === 'Per_Plant') {
            discText = '<span class="font-weight-bold text-info" style="font-size:14px;">₹' + discVal.toFixed(2) + ' / plant</span>';
        } else if (p.discount_type === 'Per_KM') {
            discText = '<span class="font-weight-bold text-warning" style="font-size:14px;">₹' + discVal.toFixed(2) + ' / KM</span>';
        }

        var maxCapText = '';
        if (parseFloat(p.max_discount_amount || 0) > 0) {
            maxCapText = '<div class="text-muted" style="font-size:11px;">Cap: ₹' + parseFloat(p.max_discount_amount).toFixed(2) + '</div>';
        }

        // Qualifications
        var minQty = parseInt(p.min_order_qty || 0);
        var minDist = parseFloat(p.min_distance_km || 0);
        var minFreight = parseFloat(p.min_freight_amount || 0);
        var qualHtml = '<div style="font-size:12px;">';
        qualHtml += minQty > 0 ? '<div>Min Qty: <b>' + minQty.toLocaleString() + ' pcs</b></div>' : '<div>Min Qty: None</div>';
        if (minDist > 0) qualHtml += '<div>Min Dist: <b>' + minDist + ' KM</b></div>';
        if (minFreight > 0) qualHtml += '<div>Freight: <b>₹' + minFreight.toFixed(0) + '</b></div>';
        qualHtml += '</div>';

        // Region
        var regionText = p.statename || 'All States';
        if (p.districtname && p.districtname !== 'All Districts' && p.districtname !== 'ALL') {
            regionText += ' (' + p.districtname + ')';
        }

        // Validity
        var validText = '<div style="font-size:12px; font-weight:600;">' + p.valid_from + ' to ' + p.valid_to + '</div>';
        if (p.is_expired) {
            validText += '<span class="badge badge-danger" style="font-size:10px;">Expired</span>';
        }

        // Status Badge & Switch
        var statusBadge = '';
        if (p.status === 'Active' && !p.is_expired) {
            statusBadge = '<span class="badge badge-success px-2 py-1" onclick="toggleStatus(' + p.id + ', \'Inactive\')" style="cursor:pointer; font-size:11px;" title="Click to Deactivate">Active</span>';
        } else if (p.status === 'Inactive') {
            statusBadge = '<span class="badge badge-secondary px-2 py-1" onclick="toggleStatus(' + p.id + ', \'Active\')" style="cursor:pointer; font-size:11px;" title="Click to Activate">Inactive</span>';
        } else {
            statusBadge = '<span class="badge badge-danger px-2 py-1" style="font-size:11px;">Expired</span>';
        }

        html += '<tr>' +
            '<td class="text-center text-muted font-weight-bold" style="font-size:11.5px;">' + (index + 1) + '</td>' +
            '<td>' +
                '<span class="plan-code-badge">' + p.plan_code + '</span>' +
                '<a href="javascript:void(0)" onclick="viewPlanDetails(' + p.id + ')" class="plan-title-link">' + htmlEscape(p.plan_name) + '</a>' +
            '</td>' +
            '<td>' + custBadge + '</td>' +
            '<td>' + cropBadge + '</td>' +
            '<td><div>' + discText + '</div>' + maxCapText + '</td>' +
            '<td>' + qualHtml + '</td>' +
            '<td><div style="font-size:12px; color:#475569;">' + htmlEscape(regionText) + '</div></td>' +
            '<td>' + validText + '</td>' +
            '<td class="text-center">' + statusBadge + '</td>' +
            '<td class="text-center">' +
                '<div class="d-flex justify-content-center gap-1">' +
                    '<button type="button" class="btn btn-sm btn-outline-info btn-action-icon" onclick="viewPlanDetails(' + p.id + ')" title="View Specifications"><i class="fa fa-eye"></i></button>' +
                    '<button type="button" class="btn btn-sm btn-outline-primary btn-action-icon" onclick="openEditPlanModal(' + p.id + ')" title="Edit Plan"><i class="fa fa-edit"></i></button>' +
                    '<button type="button" class="btn btn-sm btn-outline-danger btn-action-icon" onclick="deletePlan(' + p.id + ', \'' + htmlEscape(p.plan_name) + '\')" title="Delete Plan"><i class="fa fa-trash"></i></button>' +
                '</div>' +
            '</td>' +
        '</tr>';
    });

    $('#plansTableBody').html(html);
}

function filterBySegment(cust, plant) {
    $('#filter-customer').val(cust);
    $('#filter-plant').val(plant);
    fetchPlans();
}

function resetFilters() {
    $('#filter-search').val('');
    $('#filter-customer').val('All');
    $('#filter-plant').val('All');
    $('#filter-status').val('Active');
    fetchPlans();
}

function toggleStatus(id, newStatus) {
    $.ajax({
        url: 'modal/freight_discount_modal.php?action=toggle_status',
        type: 'POST',
        data: { id: id, status: newStatus },
        dataType: 'json',
        success: function(res) {
            if (res.status) {
                fetchPlans();
            } else {
                Swal.fire('Error', res.message || 'Could not update status', 'error');
            }
        }
    });
}

function autoGenerateModalPlanNameAndCode() {
    var cust = $('#m_customer_type').val() || 'Farmer';
    var plant = $('#m_plant_type').val() || 'Grafted';
    var custLabel = (cust === 'Dealer') ? 'Dealer' : (cust === 'Farmer' ? 'Farmer' : 'All Customers');
    var plantLabel = (plant === 'Grafted') ? 'Grafted Crops' : ((plant === 'Nongrafted') ? 'Non-Grafted Crops' : 'All Crops');
    
    var title = custLabel + ' - ' + plantLabel + ' Freight Discount Plan';
    var custCode = (cust === 'Dealer') ? 'DLR' : (cust === 'Farmer' ? 'FRM' : 'ALL');
    var plantCode = (plant === 'Grafted') ? 'GRAFT' : ((plant === 'Nongrafted') ? 'NONGR' : 'ALL');
    var planId = parseInt($('#m_plan_id').val() || 0);
    var code = (planId > 0)
        ? 'FDR-' + custCode + '-' + plantCode + '-' + String(planId).padStart(3, '0')
        : 'FDR-' + custCode + '-' + plantCode;
    
    $('#m_plan_name').val(title);
    $('#m_plan_code').val(code);
    if (planId > 0) {
        $('#planModalTitle').html('<i class="fa fa-edit text-warning mr-2"></i> Edit Plan: ' + htmlEscape(code));
    }
}

function openAddPlanModal() {
    $('#m_plan_id').val(0);
    $('#planModalTitle').html('<i class="fa fa-plus-circle text-success mr-2"></i> Add Freight Discount Plan');
    $('#modalPlanForm')[0].reset();
    $('#m_customer_type').val('Farmer');
    $('#m_plant_type').val('Grafted');
    autoGenerateModalPlanNameAndCode();
    $('#m_discount_type').val('Percentage');
    $('#m_discount_value').val('15.00');
    $('#m_discount_unit').text('%');
    $('#m_min_order_qty').val('1000');
    $('#m_max_order_qty').val('0');
    $('#m_min_distance_km').val('0');
    $('#m_min_freight_amount').val('1000.00');
    $('#m_max_discount_amount').val('0.00');
    $('#m_valid_from').val(new Date().toISOString().slice(0,10));
    var nextYear = new Date();
    nextYear.setFullYear(nextYear.getFullYear() + 1);
    $('#m_valid_to').val(nextYear.toISOString().slice(0,10));
    $('#m_stateid').val('ALL');
    $('#m_statename').val('All States');
    $('#m_districtid').html('<option value="ALL">All Districts</option>');
    $('#m_districtname').val('All Districts');
    $('#planModal').modal('show');
}

function openEditPlanModal(id) {
    $.ajax({
        url: 'modal/freight_discount_modal.php?action=get_plan',
        type: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(res) {
            if (res.status && res.data) {
                var p = res.data;
                $('#m_plan_id').val(p.id);
                $('#planModalTitle').html('<i class="fa fa-edit text-warning mr-2"></i> Edit Plan: ' + htmlEscape(p.plan_code));
                $('#m_plan_name').val(p.plan_name);
                $('#m_plan_code').val(p.plan_code);
                $('#m_customer_type').val(p.customer_type);
                $('#m_plant_type').val(p.plant_type);
                $('#m_discount_type').val(p.discount_type);
                $('#m_discount_value').val(parseFloat(p.discount_value || 0));
                $('#m_max_discount_amount').val(parseFloat(p.max_discount_amount || 0));
                $('#m_min_order_qty').val(parseInt(p.min_order_qty || 0));
                $('#m_max_order_qty').val(parseInt(p.max_order_qty || 0));
                $('#m_min_distance_km').val(parseFloat(p.min_distance_km || 0));
                $('#m_min_freight_amount').val(parseFloat(p.min_freight_amount || 0));
                $('#m_valid_from').val(p.valid_from);
                $('#m_valid_to').val(p.valid_to);
                $('#m_status').val(p.status);
                $('#m_priority').val(p.priority || 1);
                $('#m_description').val(p.description || '');

                handleModalDiscountTypeChange();

                if (p.stateid && p.stateid !== 'ALL') {
                    $('#m_stateid').val(p.stateid);
                    $('#m_statename').val(p.statename || '');
                    loadModalDistricts(p.stateid, p.districtid, p.districtname);
                } else {
                    $('#m_stateid').val('ALL');
                    $('#m_statename').val('All States');
                    $('#m_districtid').html('<option value="ALL">All Districts</option>');
                    $('#m_districtname').val('All Districts');
                }

                $('#planModal').modal('show');
            }
        }
    });
}

function handleModalDiscountTypeChange() {
    var type = $('#m_discount_type').val();
    if (type === 'Percentage') $('#m_discount_unit').text('%');
    else if (type === 'Fixed_Amount') $('#m_discount_unit').text('₹ Flat');
    else if (type === 'Per_Plant') $('#m_discount_unit').text('₹/Plant');
    else if (type === 'Per_KM') $('#m_discount_unit').text('₹/KM');
}

function handleModalStateChange() {
    var stateid = $('#m_stateid').val();
    var statename = $('#m_stateid option:selected').text();
    $('#m_statename').val(statename);
    loadModalDistricts(stateid, 'ALL', 'All Districts');
}

function handleModalDistrictChange() {
    var districtname = $('#m_districtid option:selected').text();
    $('#m_districtname').val(districtname);
}

function loadModalDistricts(stateid, selDistId, selDistName) {
    if (!stateid || stateid === 'ALL') {
        $('#m_districtid').html('<option value="ALL">All Districts</option>');
        $('#m_districtname').val('All Districts');
        return;
    }

    $.ajax({
        url: 'modal/freight_discount_modal.php?action=get_districts',
        type: 'GET',
        data: { stateid: stateid },
        dataType: 'json',
        success: function(res) {
            var opts = '<option value="ALL">All Districts</option>';
            if (res.status && res.data) {
                res.data.forEach(function(d) {
                    var isSel = (d.districtid == selDistId) ? 'selected' : '';
                    opts += '<option value="' + d.districtid + '" ' + isSel + '>' + d.districtname + '</option>';
                });
            }
            $('#m_districtid').html(opts);
            $('#m_districtname').val(selDistName || 'All Districts');
        }
    });
}

function submitModalPlan() {
    var planId = parseInt($('#m_plan_id').val() || 0);
    var action = (planId > 0) ? 'update_plan' : 'save_plan';

    $('#btn-modal-save').prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> Saving...');

    $.ajax({
        url: 'modal/freight_discount_modal.php?action=' + action,
        type: 'POST',
        data: $('#modalPlanForm').serialize(),
        dataType: 'json',
        success: function(res) {
            $('#btn-modal-save').prop('disabled', false).html('<i class="fa fa-save mr-1"></i> Save Plan');
            if (res.status) {
                $('#planModal').modal('hide');
                Swal.fire({
                    icon: 'success',
                    title: (planId > 0 ? 'Plan Updated' : 'Plan Created'),
                    text: res.message,
                    timer: 1500,
                    showConfirmButton: false
                });
                fetchPlans();
            } else {
                Swal.fire('Error', res.message || 'Could not save plan', 'error');
            }
        },
        error: function() {
            $('#btn-modal-save').prop('disabled', false).html('<i class="fa fa-save mr-1"></i> Save Plan');
            Swal.fire('Network Error', 'Could not connect to server.', 'error');
        }
    });
}

function viewPlanDetails(id) {
    $('#planDetailsModalBody').html('<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>');
    $('#btn-modal-edit-action').attr('onclick', '$("#planDetailsModal").modal("hide"); openEditPlanModal(' + id + ');');
    $('#planDetailsModal').modal('show');

    $.ajax({
        url: 'modal/freight_discount_modal.php?action=get_plan',
        type: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(res) {
            if (res.status && res.data) {
                var p = res.data;
                var html = '<div class="row g-3">' +
                    '<div class="col-md-6">' +
                        '<div class="p-3 bg-light rounded-3 mb-3">' +
                            '<div class="text-muted small text-uppercase font-weight-bold">Plan Identity</div>' +
                            '<div class="font-weight-bold fs-16 text-dark mt-1">' + htmlEscape(p.plan_name) + '</div>' +
                            '<div class="plan-code-badge mt-1">' + p.plan_code + '</div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="col-md-6">' +
                        '<div class="p-3 bg-light rounded-3 mb-3">' +
                            '<div class="text-muted small text-uppercase font-weight-bold">Target Customer &amp; Crop</div>' +
                            '<div class="mt-2">' +
                                '<span class="badge-customer ' + (p.customer_type === 'Dealer' ? 'badge-dealer' : 'badge-farmer') + ' mr-2">' + p.customer_type + '</span>' +
                                '<span class="badge-crop ' + (p.plant_type === 'Grafted' ? 'badge-grafted' : 'badge-nongrafted') + '">' + p.plant_type + '</span>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>' +

                '<div class="table-responsive my-2">' +
                    '<table class="table table-bordered table-sm mb-0">' +
                        '<thead class="bg-light"><tr><th width="40%">Rule Parameter</th><th>Configured Value</th></tr></thead>' +
                        '<tbody>' +
                            '<tr><td class="font-weight-bold text-muted">Discount Rule</td><td><span class="badge badge-primary px-2 py-1">' + p.discount_type + '</span> <b class="text-success fs-15 ml-2">' + (p.discount_type === 'Percentage' ? p.discount_value + '%' : '₹' + parseFloat(p.discount_value).toFixed(2)) + '</b></td></tr>' +
                            '<tr><td class="font-weight-bold text-muted">Maximum Discount Cap</td><td>' + (parseFloat(p.max_discount_amount || 0) > 0 ? '₹' + parseFloat(p.max_discount_amount).toFixed(2) : 'Unlimited (No Cap)') + '</td></tr>' +
                            '<tr><td class="font-weight-bold text-muted">Minimum Order Quantity</td><td>' + (parseInt(p.min_order_qty || 0) > 0 ? parseInt(p.min_order_qty).toLocaleString() + ' plants' : 'No Minimum') + '</td></tr>' +
                            '<tr><td class="font-weight-bold text-muted">Minimum Freight Charges</td><td>' + (parseFloat(p.min_freight_amount || 0) > 0 ? '₹' + parseFloat(p.min_freight_amount).toFixed(2) : 'No Minimum') + '</td></tr>' +
                            '<tr><td class="font-weight-bold text-muted">Geographic Region</td><td>' + htmlEscape(p.statename || 'All States') + ' / ' + htmlEscape(p.districtname || 'All Districts') + '</td></tr>' +
                            '<tr><td class="font-weight-bold text-muted">Effective Period</td><td>' + p.valid_from + ' to ' + p.valid_to + '</td></tr>' +
                            '<tr><td class="font-weight-bold text-muted">Priority Weight</td><td>' + p.priority + '</td></tr>' +
                            '<tr><td class="font-weight-bold text-muted">Description</td><td>' + htmlEscape(p.description || 'N/A') + '</td></tr>' +
                        '</tbody>' +
                    '</table>' +
                '</div>';

                $('#planDetailsModalBody').html(html);
            }
        }
    });
}

function deletePlan(id, planName) {
    Swal.fire({
        title: 'Delete Plan?',
        text: 'Are you sure you want to delete "' + planName + '"?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, Delete'
    }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                url: 'modal/freight_discount_modal.php?action=delete_plan',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(res) {
                    if (res.status) {
                        Swal.fire('Deleted', 'Plan removed successfully.', 'success');
                        fetchPlans();
                    } else {
                        Swal.fire('Error', res.message || 'Could not delete plan', 'error');
                    }
                }
            });
        }
    });
}

function openDiscountSimulatorModal() {
    $('#simResult').hide().empty();
    $('#simulatorModal').modal('show');
}

function runSimulation() {
    var custType = $('#sim-customer-type').val();
    var plantType = $('#sim-plant-type').val();
    var orderQty = $('#sim-order-qty').val();
    var freightAmt = $('#sim-freight-amount').val();
    var distanceKm = $('#sim-distance-km').val();

    $.ajax({
        url: 'modal/freight_discount_modal.php?action=calculate_discount',
        type: 'POST',
        data: {
            customer_type: custType,
            plant_type: plantType,
            order_qty: orderQty,
            freight_amount: freightAmt,
            distance_km: distanceKm
        },
        dataType: 'json',
        success: function(res) {
            if (res.status) {
                var d = res.data;
                var outHtml = '';
                if (d.has_discount && d.applied_plan) {
                    outHtml = '<div class="alert alert-success border-0 shadow-sm p-3">' +
                        '<div class="font-weight-bold text-success mb-1"><i class="fa fa-check-circle mr-1"></i> Applicable Discount Found!</div>' +
                        '<div class="fs-15 font-weight-bold text-dark">' + htmlEscape(d.applied_plan.plan_name) + ' (' + d.applied_plan.plan_code + ')</div>' +
                        '<hr class="my-2">' +
                        '<div class="d-flex justify-content-between"><span>Original Freight:</span> <b>₹' + parseFloat(d.freight_amount).toFixed(2) + '</b></div>' +
                        '<div class="d-flex justify-content-between text-success"><span>Freight Discount:</span> <b>- ₹' + parseFloat(d.discount_amount).toFixed(2) + '</b></div>' +
                        '<div class="d-flex justify-content-between fs-16 font-weight-bold text-primary mt-1"><span>Payable Freight:</span> <span>₹' + parseFloat(d.payable_freight).toFixed(2) + '</span></div>' +
                    '</div>';
                } else {
                    outHtml = '<div class="alert alert-warning border-0 shadow-sm p-3">' +
                        '<i class="fa fa-info-circle mr-1"></i> No matching active discount plan found for these order parameters.' +
                    '</div>';
                }
                $('#simResult').html(outHtml).show();
            }
        }
    });
}

function exportPlansExcel() {
    if (!rawPlansData || rawPlansData.length === 0) {
        Swal.fire('No Data', 'No plans available to export.', 'info');
        return;
    }

    var aoa = [
        ["Freight Discount Plans Master"],
        ["Export Date: " + new Date().toLocaleDateString(), "Total Records: " + rawPlansData.length],
        [],
        ["S.No", "Plan Code", "Plan Name", "Customer Segment", "Crop Category", "Discount Type", "Discount Value", "Max Cap (Rs)", "Min Qty (Pcs)", "Min Freight (Rs)", "State", "District", "Valid From", "Valid To", "Status"]
    ];

    rawPlansData.forEach(function(p, i) {
        aoa.push([
            i + 1,
            p.plan_code,
            p.plan_name,
            p.customer_type,
            p.plant_type,
            p.discount_type,
            parseFloat(p.discount_value || 0),
            parseFloat(p.max_discount_amount || 0),
            parseInt(p.min_order_qty || 0),
            parseFloat(p.min_freight_amount || 0),
            p.statename || 'All States',
            p.districtname || 'All Districts',
            p.valid_from,
            p.valid_to,
            p.status
        ]);
    });

    if (typeof XLSX !== 'undefined') {
        var wb = XLSX.utils.book_new();
        var ws = XLSX.utils.aoa_to_sheet(aoa);
        XLSX.utils.book_append_sheet(wb, ws, "Discount Plans");
        XLSX.writeFile(wb, 'Freight_Discount_Plans_' + new Date().toISOString().slice(0,10) + '.xlsx');
    }
}

function htmlEscape(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

<?php
if (!$isDz) {
    require_once(__DIR__ . '/../footer.php');
}
?>
