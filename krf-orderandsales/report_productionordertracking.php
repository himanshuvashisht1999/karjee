<?php
include('../header.php');
?>

<style>
  :root {
    --primary-blue: #007bff;
    --text-main: #333333;
    --text-muted: #888888;
    --bg-light: #f4f6f9;
    --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    --border-color: #e2e8f0;
    --transition: all 0.2s ease-in-out;
  }

  .tracking-dashboard {
    padding: 1.5rem;
    background: #f8f9fa;
    min-height: 100vh;
    font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  }

  /* Title Card */
  .main-title-card {
    background: white;
    padding: 1.25rem 2rem;
    border-radius: 12px;
    box-shadow: var(--card-shadow);
    margin-bottom: 1.5rem;
    border-left: 6px solid #4f46e5;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .title-content {
    display: flex;
    align-items: center;
    gap: 1.25rem;
  }

  .title-icon-wrapper {
    background: #edf2f7;
    color: #4f46e5;
    width: 45px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    font-size: 1.3rem;
  }

  .title-text h4 {
    margin: 0;
    font-weight: 700;
    color: #1a202c;
    font-size: 1.5rem;
    letter-spacing: -0.5px;
  }

  .title-text p {
    margin: 2px 0 0;
    color: #718096;
    font-size: 0.95rem;
  }

  /* Filter Strip Card */
  .filter-strip-card {
    background: white;
    padding: 1.5rem;
    border-radius: 16px;
    box-shadow: var(--card-shadow);
    margin-bottom: 1.5rem;
    border: 1px solid #edf2f7;
  }

  .filters-row {
    display: flex;
    flex-wrap: wrap;
    gap: 1.25rem;
    margin-bottom: 1.25rem;
  }

  .filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    flex: 1;
    min-width: 180px;
  }

  .filter-label {
    font-size: 0.75rem;
    font-weight: 700;
    color: #4a5568;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .filter-control {
    width: 100%;
    padding: 0.55rem 0.75rem;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    font-size: 0.9rem;
    color: #2d3748;
    transition: var(--transition);
    background-color: #f8fafc;
  }

  .filter-control:focus {
    border-color: #4f46e5;
    background-color: white;
    outline: none;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
  }

  /* Select2 Styling Overrides */
  .select2-container--default .select2-selection--multiple {
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    min-height: 38px;
    padding-bottom: 3px;
  }
  .select2-container--default.select2-container--focus .select2-selection--multiple {
    border-color: #4f46e5;
    background-color: white;
  }
  .select2-container--default .select2-selection--multiple .select2-selection__choice {
    background-color: #e2e8f0;
    border: 1px solid #cbd5e0;
    border-radius: 5px;
    padding: 2px 8px;
    font-size: 0.8rem;
    margin-top: 5px;
  }

  /* Search Row */
  .search-row {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
  }

  .search-wrapper {
    position: relative;
    width: 100%;
  }

  .search-wrapper i {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #a0aec0;
    font-size: 1.1rem;
  }

  .search-wrapper input {
    width: 100%;
    padding: 0.65rem 1rem 0.65rem 2.5rem;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    font-size: 0.95rem;
    color: #2d3748;
    background-color: #f8fafc;
    transition: var(--transition);
  }

  .search-wrapper input:focus {
    border-color: #4f46e5;
    background-color: white;
    outline: none;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
  }

  /* Grid Layout */
  .strips-header {
    position: sticky !important;
    top: 0;
    z-index: 100;
    display: grid;
    grid-template-columns: 40px 1.4fr 1.8fr 1.6fr 1.1fr 2.3fr 90px;
    padding: 0.75rem 1.25rem;
    color: #718096;
    font-size: 0.75rem;
    text-transform: uppercase;
    font-weight: 700;
    letter-spacing: 0.5px;
    background: #e9ecef !important;
    border-radius: 8px;
    margin-bottom: 0.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
  }

  .order-strips-container {
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
  }

  .order-strip {
    background: white;
    border-radius: 10px;
    padding: 0.85rem 1.25rem;
    display: grid;
    grid-template-columns: 40px 1.4fr 1.8fr 1.6fr 1.1fr 2.3fr 90px;
    align-items: center;
    box-shadow: 0 1px 2px rgba(0,0,0,0.02);
    border: 1px solid #edf2f7;
    transition: var(--transition);
  }

  .order-strip:hover {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
    transform: translateY(-2px);
    border-color: #cbd5e0;
  }

  .strip-item {
    min-width: 0;
    padding-right: 0.75rem;
  }

  .strip-content {
    font-size: 0.9rem;
    color: #2d3748;
  }

  /* Pills & Badges */
  .order-no-pill {
    background: #ebf8ff;
    color: #2b6cb0;
    padding: 0.25rem 0.6rem;
    border-radius: 6px;
    font-weight: 700;
    font-size: 0.8rem;
    border: 1px solid #bee3f8;
    display: inline-block;
  }

  .pso-no-pill {
    background: #f5f3ff;
    color: #6b21a8;
    padding: 0.22rem 0.55rem;
    border-radius: 6px;
    font-weight: 700;
    font-size: 0.78rem;
    border: 1px solid #ddd6fe;
    display: inline-block;
    letter-spacing: 0.2px;
  }

  .qty-badge {
    background: #f9fafb;
    border: 1px solid #e2e8f0;
    color: #1f2937;
    padding: 0.35rem 0.75rem;
    border-radius: 6px;
    font-weight: 700;
    font-size: 0.85rem;
    display: inline-block;
  }

  .allocated-badge {
    background: #f0fff4;
    border: 1px solid #c6f6d5;
    color: #2f855a;
    padding: 0.35rem 0.75rem;
    border-radius: 6px;
    font-weight: 700;
    font-size: 0.85rem;
    display: inline-block;
  }

  .balance-badge {
    background: #fff5f5;
    border: 1px solid #fed7d7;
    color: #c53030;
    padding: 0.35rem 0.75rem;
    border-radius: 6px;
    font-weight: 700;
    font-size: 0.85rem;
    display: inline-block;
  }

  /* Actions */
  .action-buttons {
    display: flex;
    gap: 0.4rem;
    justify-content: flex-end;
  }

  .btn-circle {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    transition: var(--transition);
    font-size: 0.9rem;
    cursor: pointer;
  }

  .btn-view-details {
    background: #edf2f7;
    color: #4a5568;
  }
  .btn-view-details:hover {
    background: #4a5568;
    color: white;
  }

  .btn-edit-order {
    background: #ebf8ff;
    color: #3182ce;
  }
  .btn-edit-order:hover {
    background: #3182ce;
    color: white;
  }

  /* Modal Strip Card Details */
  .modal-lifecycle-container {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .modal-strips-header {
    display: grid;
    grid-template-columns: 40px 1.8fr 1.6fr 1.2fr 2.0fr 1.2fr 2.0fr 1.4fr;
    padding: 0.75rem 1.25rem;
    color: #718096;
    font-size: 0.75rem;
    text-transform: uppercase;
    font-weight: 700;
    letter-spacing: 0.5px;
    background: #e9ecef !important;
    border-radius: 8px;
    margin-bottom: 0.5rem;
  }

  .modal-order-strip {
    background: white;
    border-radius: 10px;
    padding: 0.85rem 1.25rem;
    display: grid;
    grid-template-columns: 40px 1.8fr 1.6fr 1.2fr 2.0fr 1.2fr 2.0fr 1.4fr;
    align-items: center;
    box-shadow: 0 1px 2px rgba(0,0,0,0.02);
    border: 1px solid #edf2f7;
    transition: var(--transition);
  }

  .modal-order-strip:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    border-color: #cbd5e0;
    transform: translateY(-1px);
  }

  .modal-strip-content {
    font-size: 0.85rem;
    color: #2d3748;
    word-break: break-word;
    padding-right: 0.5rem;
  }

  .badge-status {
    padding: 0.2rem 0.5rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 700;
  }

  .badge-status.success { background: #d1fae5; color: #065f46; }
  .badge-status.warning { background: #fef3c7; color: #92400e; }
  .badge-status.info { background: #dbeafe; color: #1e40af; }

  /* Full screen modal styling overrides */
  .modal-fullscreen-custom {
    max-width: 100% !important;
    width: 100% !important;
    height: 100% !important;
    margin: 0 !important;
  }
  .modal-fullscreen-custom .modal-content {
    min-height: 100vh !important;
    height: 100% !important;
    border-radius: 0 !important;
  }
  .modal-fullscreen-custom .modal-header {
    border-radius: 0 !important;
  }

  /* Sowing Child Grid Styles */
  .sowing-child-grid-container {
    background: #f8fafc;
    border-radius: 12px;
    padding: 1rem 1.25rem;
    margin: 0.5rem 1.25rem 1rem 1.25rem;
    box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.03);
    border: 1px solid #e2e8f0;
  }

  /* Bed Details Card & Strips */
  .bed-details-card {
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    border-left: 5px solid #20c997; /* Emerald/turquoise left accent border */
    overflow: hidden;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.02);
  }

  .bed-details-header {
    display: grid;
    grid-template-columns: 1.8fr 1fr 1fr 1.8fr 1.8fr 1.1fr 1fr 1.4fr;
    background: linear-gradient(90deg, #20c997 0%, #15b38a 100%);
    padding: 0.95rem 1.25rem;
    color: #ffffff;
    font-weight: 700;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    border-bottom: 1px solid rgba(0,0,0,0.08);
  }

  .bed-details-header-item {
    padding-right: 0.5rem;
  }

  .bed-details-row {
    display: grid;
    grid-template-columns: 1.8fr 1fr 1fr 1.8fr 1.8fr 1.1fr 1fr 1.4fr;
    background: #ffffff;
    padding: 0.95rem 1.25rem;
    align-items: center;
    border-bottom: 1px solid #f1f5f9;
    transition: background-color 0.2s ease-in-out;
  }

  .bed-details-row:last-child {
    border-bottom: none;
  }

  .bed-details-row:hover {
    background-color: #f8fafc;
  }

  .bed-detail-item {
    font-size: 0.88rem;
    color: #2d3748;
    padding-right: 0.5rem;
    min-width: 0;
  }

  .bed-detail-item .label-bold {
    font-weight: 700;
    color: #1a202c;
  }

  /* Badges in Bed Details */
  .badge-dark-pill {
    background: #495057 !important;
    color: #ffffff !important;
    padding: 0.35rem 0.8rem;
    border-radius: 20px;
    font-weight: 700;
    font-size: 0.8rem;
    display: inline-block;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
  }

  .badge-yellow-pill {
    background: #ffc107 !important;
    color: #1a202c !important;
    padding: 0.35rem 0.8rem;
    border-radius: 20px;
    font-weight: 700;
    font-size: 0.8rem;
    display: inline-block;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
  }

  .badge-green-pill {
    background: #198754 !important;
    color: #ffffff !important;
    padding: 0.35rem 0.8rem;
    border-radius: 20px;
    font-weight: 700;
    font-size: 0.8rem;
    display: inline-block;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
  }

  .badge-red-pill {
    background: #dc3545 !important;
    color: #ffffff !important;
    padding: 0.35rem 0.8rem;
    border-radius: 20px;
    font-weight: 700;
    font-size: 0.8rem;
    display: inline-block;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
  }

  .badge-status-dispatch {
    padding: 0.35rem 0.8rem;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
  }

  .badge-status-dispatch.dispatched {
    background: #198754 !important;
    color: #ffffff !important;
  }

  .badge-status-dispatch.not-dispatched {
    background: #dc3545 !important;
    color: #ffffff !important;
  }

  .badge-status-dispatch.pending {
    background: #ffc107 !important;
    color: #1a202c !important;
  }

  /* Scale down layouts for Tablet viewports to keep everything aligned */
  @media (min-width: 768px) and (max-width: 991px) {
    .bed-details-header {
      font-size: 0.72rem;
      padding: 0.8rem 1rem;
    }
    .bed-details-row {
      font-size: 0.82rem;
      padding: 0.8rem 1rem;
    }
    .badge-dark-pill, .badge-yellow-pill, .badge-green-pill, .badge-red-pill, .badge-status-dispatch {
      font-size: 0.75rem;
      padding: 0.28rem 0.7rem;
    }
  }

  /* Responsive styling for Mobile */
  @media (max-width: 767px) {
    .bed-details-card {
      border: none;
      box-shadow: none;
      background: transparent;
      border-radius: 0;
      overflow: visible;
    }

    .bed-details-header {
      display: none;
    }

    .bed-details-row {
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
      padding: 1.2rem;
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-left: 5px solid #20c997; /* Retain accent line on mobile card list */
      border-radius: 12px;
      margin-bottom: 1rem;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    .bed-details-row:last-child {
      margin-bottom: 0;
    }

    .bed-detail-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      width: 100%;
      border-bottom: 1px solid #f1f5f9;
      padding-bottom: 0.6rem;
      padding-right: 0;
    }

    .bed-detail-item:last-child {
      border-bottom: none;
      padding-bottom: 0;
    }

    .bed-detail-item::before {
      content: attr(data-label);
      font-weight: 700;
      color: #4a5568;
      font-size: 0.72rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-right: auto;
      text-align: left;
    }
    
    .bed-detail-item .value-content {
      text-align: right;
      display: inline-block;
    }

    .bed-detail-item .value-content .text-end {
      text-align: right !important;
    }
  }

  /* Sowing vs Polyhouse Trays Status Banners */
  .sowing-status-banner {
    padding: 0.85rem 1.25rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    font-weight: 600;
    font-size: 0.88rem;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
  }

  .sowing-status-banner.missing {
    background-color: #ffeef0 !important;
    color: #b91c1c !important;
    border: 1px solid #fca5a5 !important;
    border-left: 5px solid #ef4444 !important;
  }

  .sowing-status-banner.extra {
    background-color: #eff6ff !important;
    color: #1e40af !important;
    border: 1px solid #bfdbfe !important;
    border-left: 5px solid #3b82f6 !important;
  }

  .sowing-status-banner.matched {
    background-color: #f0fdf4 !important;
    color: #166534 !important;
    border: 1px solid #bbf7d0 !important;
    border-left: 5px solid #22c55e !important;
  }

  /* Batch Summary KPI Cards */
  .batch-summary-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 12px;
    margin-bottom: 1rem;
  }

  .batch-summary-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 0.8rem 1rem;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }

  .batch-summary-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
  }

  .summary-icon-box {
    width: 42px;
    height: 42px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    flex-shrink: 0;
  }

  .summary-icon-box.total { background: #f1f5f9; color: #334155; }
  .summary-icon-box.verified { background: #dcfce7; color: #16a34a; }
  .summary-icon-box.unverified { background: #fef3c7; color: #d97706; }
  .summary-icon-box.failure { background: #fee2e2; color: #dc2626; }
  .summary-icon-box.success { background: #ccfbf1; color: #0d9488; }

  .summary-info {
    min-width: 0;
    flex: 1;
  }

  .summary-label {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #64748b;
    letter-spacing: 0.5px;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .summary-value {
    font-size: 1.15rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.2;
  }

  .summary-sub {
    font-size: 0.72rem;
    font-weight: 600;
    color: #94a3b8;
    margin-top: 2px;
  }

  @media (max-width: 991px) {
    .batch-summary-grid {
      grid-template-columns: repeat(2, 1fr);
    }
  }

  @media (max-width: 576px) {
    .batch-summary-grid {
      grid-template-columns: 1fr;
    }
  }

  .modal-order-strip.clickable-row {
    cursor: pointer;
  }
</style>

<div class="tracking-dashboard">
  <!-- Title Card -->
  <div class="main-title-card">
    <div class="title-content">
      <div class="title-icon-wrapper shadow-sm">
        <i class="fa fa-chart-line"></i>
      </div>
      <div class="title-text">
        <h4>Production Order Lifecycle Tracking</h4>
        <p>Monitor progress from Sowing, Germination Room, to Polyhouse bed allocation</p>
      </div>
    </div>
  </div>

  <!-- Filter Strip Card -->
  <div class="filter-strip-card">
    <div class="filters-row">
      <div class="filter-group">
        <label class="filter-label">Order Year</label>
        <select class="filter-control" name="psoYearlist" id="psoYearlist"></select>
      </div>
      <div class="filter-group">
        <label class="filter-label">Order Month</label>
        <select class="filter-control" name="psoMonthlist" id="psoMonthlist"></select>
      </div>
      <div class="filter-group">
        <label class="filter-label">Client</label>
        <select id="clientFilter" class="filter-control select2" multiple="multiple" data-placeholder="All Clients"></select>
      </div>
      <div class="filter-group">
        <label class="filter-label">Product</label>
        <select id="productFilter" class="filter-control select2" multiple="multiple" data-placeholder="All Products"></select>
      </div>
      <div class="filter-group">
        <label class="filter-label">Delivery Date</label>
        <select id="deliveryDateFilter" class="filter-control select2" multiple="multiple" data-placeholder="All Dates"></select>
      </div>
    </div>
    <div class="search-row">
      <label class="filter-label">Search</label>
      <div class="search-wrapper">
        <i class="fa fa-search"></i>
        <input type="text" id="productionOrderSearch" placeholder="Quick search by Order No, PSO No, Client, Product...">
      </div>
    </div>
  </div>

  <!-- Strips List Grid -->
  <div class="strips-header">
    <div>#</div>
    <div>Order No / PSO / Date</div>
    <div>Product / Client Name</div>
    <div>Prod. Date / Exp. Delivery</div>
    <div>Product Type</div>
    <div>Seed Name</div>
    <div class="text-end">Track</div>
  </div>

  <div class="order-strips-container" id="productionOrderList">
    <div class="text-center py-5 bg-white border rounded-3">
      <i class="fa fa-spinner fa-spin fa-2x text-muted"></i>
      <p class="mt-2 text-muted">Loading production orders...</p>
    </div>
  </div>
</div>

<!-- Detailed Strip Tracking Modal (Full Screen) -->
<div class="modal fade" id="LifecycleTrackingModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-custom" role="document">
    <div class="modal-content shadow-2xl border-0">
      <div class="modal-header bg-dark text-white border-0">
        <h5 class="modal-title font-weight-bold">
          <i class="fa fa-route me-2"></i> Sowing Tracking Details: <span id="lblStockOrderTitle" class="text-info font-weight-bold"></span>
        </h5>
        <button type="button" class="close text-white" onclick="$('#LifecycleTrackingModal').modal('hide')" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body p-4" id="lifecycleDetailsBody">
        <!-- Dynamic Lifecycle stages populated in strip forms -->
      </div>
    </div>
  </div>
</div>

<?php include('../footer.php'); ?>
<script type="text/javascript" src="<?php echo $prefix; ?>krf-orderandsales/js/pjs_function.js"></script>

<script>
  $(document).ready(function () {
    const projectid = $("body").data("projectid");
    const loginRoleId = $("body").data("loginroleid");
    const key = $("body").data("key");
    const routurl = $("body").data("routurl");

    // Fetch and populate Order Year and Order Month
    GetProductionStockOrderYearMonthlist(function (ymres) {
      $("#psoYearlist").empty();
      let ylist = `<option value='' disabled selected>Select Year</option>`;
      if (ymres.length > 0) {
        ymres.forEach(item => {
          let monthJSON = encodeURIComponent(JSON.stringify(item.monthlist));
          ylist += `<option value='${item.year}' data-monthlist='${monthJSON}'>${item.year}</option>`;
        });
      } else {
        ylist += `<option value=''>No data</option>`;
      }
      $("#psoYearlist").append(ylist);

      // Select current year or first year in the list as default
      const currentYear = new Date().getFullYear();
      if ($("#psoYearlist option[value='" + currentYear + "']").length > 0) {
        $("#psoYearlist").val(currentYear).trigger("change");
      } else if (ymres.length > 0) {
        $("#psoYearlist").val(ymres[0].year).trigger("change");
      }
    });

    $("#psoYearlist").on("change", function () {
      $("#psoMonthlist").empty();
      let monthData = $(this).find(":selected").data("monthlist");
      if (!monthData) return;
      let months = JSON.parse(decodeURIComponent(monthData));
      let mlist = `<option value="" disabled>Select Month</option><option value="all">All Months</option>`;
      months.forEach(m => mlist += `<option value='${m.numeric}'>${m.alpha}</option>`);
      $("#psoMonthlist").append(mlist).val("all").trigger("change");
    });

    $(document).on("change", "#psoMonthlist", function () {
      loadTrackingReport();
    });

    function loadTrackingReport() {
      let year = $("#psoYearlist").val();
      let month = $("#psoMonthlist").val();
      if (!year || !month) return;

      let datefrom, dateto;
      if (month === "all") {
        datefrom = `${year}-01-01`;
        dateto = `${year}-12-31`;
      } else {
        let monthFormatted = String(month).padStart(2, '0');
        datefrom = `${year}-${monthFormatted}-01`;
        let lastDay = new Date(year, month, 0).getDate();
        dateto = `${year}-${monthFormatted}-${lastDay}`;
      }

      $("#productionOrderList").html(`
        <div class="text-center py-5">
          <i class="fa fa-spinner fa-spin fa-2x text-muted"></i>
          <p class="mt-2 text-muted">Loading tracking report data...</p>
        </div>
      `);

      $.ajax({
        url: 'modal/reporting.php?action=GetProductionOrderTrackingList',
        type: 'POST',
        data: { datefrom, dateto, projectid },
        dataType: 'JSON',
        success: function (res) {
          // console.log(res);
          let html = "";
          let uniqueDates = new Set();
          let uniqueClients = new Set();
          let uniqueProducts = new Set();

          if (Array.isArray(res) && res.length > 0) {
            res.forEach((item, index) => {
              if (item.expected_delivery_date) uniqueDates.add(item.expected_delivery_date);
              if (item.client_name) uniqueClients.add(item.client_name.trim());
              if (item.product_name) uniqueProducts.add(item.product_name.trim());

              let orderQty = parseFloat(item.quantity || 0);
              let sowingQty = parseFloat(item.totalsowingqty || 0);
              let sowingQtydone = parseFloat(item.sowing_qty || 0);
              let sowingTrays = parseInt(item.sowing_trays || 0);
              let pendingSowingQty = parseInt(sowingQty - sowingQtydone) || 0;
              
              let badgeClass = "";
              let badgeText = "";
              let absVal = Math.abs(pendingSowingQty);

              if (sowingQty>0 &&pendingSowingQty < 0) {
                  badgeClass = "badge-status success";
                  badgeText = `Extra Plants Sowing: ${absVal}`;
              } else if (sowingQty>0 && pendingSowingQty > 0) {
                  badgeClass = "badge-status warning";
                  badgeText = `Pending for Sowing: ${absVal}`;
              } else if (sowingQty>0 && pendingSowingQty == 0) {
                  badgeClass = "badge-status warning";
                  badgeText = `Pending for Sowing: ${sowingQty}`;
              } else if (sowingQty<=0) {
                  badgeClass = "badge-status danger";
                  badgeText = `Sowing not Started: ${orderQty}`;
              } else if (sowingQtydone>0 && pendingSowingQty == 0) {
                  badgeClass = "badge-status info";
                  badgeText = "Done Sowing";
              }

              const graftStatus = item.graftstatus || 'Non-Grafted';
              const isGrafted = graftStatus.toLowerCase() === 'grafted';
              const scionSeed = item.scion_seed_name || 'N/A';
              const rootSeed = item.root_seed_name || 'N/A';
              const sowingDateFormatted = item.sowingdate ? changeDateFormatYmd_dMY(item.sowingdate) : 'N/A';

              let seedBadgeHtml = '';
              if (isGrafted) {
                seedBadgeHtml = `
                  <div class="mb-1"><span class="badge bg-light text-dark border py-1 px-2" style="font-size:0.8rem; font-weight:600;"><span class="text-muted">Scion:</span> ${scionSeed}</span></div>
                  <div><span class="badge bg-light text-dark border py-1 px-2" style="font-size:0.8rem; font-weight:600;"><span class="text-muted">Root:</span> ${rootSeed}</span></div>
                `;
              } else {
                seedBadgeHtml = `
                  <div><span class="badge bg-light text-dark border py-1 px-2" style="font-size:0.8rem; font-weight:600;"><span class="text-muted">Scion:</span> ${scionSeed}</span></div>
                `;
              }

              const psoVal = (item.psono || '').trim();
              const psoBadgeHtml = psoVal ? `<span class="pso-no-pill" title="Provisional Sales Order No: ${psoVal}"><i class="fa fa-file-invoice mr-1" style="font-size:0.72rem; opacity:0.75;"></i>${psoVal}</span>` : '';

              html += `
                <div class="order-strip" 
                     data-search-term="${(item.stockorder_no + ' ' + psoVal + ' ' + (item.client_name || '') + ' ' + (item.product_name || '')).toLowerCase()}" 
                     data-psono="${psoVal}"
                     data-deliverydate="${item.expected_delivery_date}"
                     data-client="${(item.client_name || '').trim()}"
                     data-product="${(item.product_name || '').trim()}"
                     data-orderdate="${changeDateFormatYmd_dMY(item.created_at)}"
                     data-sowingdate="${sowingDateFormatted}"
                     data-graftstatus="${graftStatus}"
                     data-scion-seed="${scionSeed}"
                     data-root-seed="${rootSeed}"
                     data-productname="${(item.product_name || '').trim()}"
                     data-clientname="${item.client_name || 'Stock Order'}"
                     data-expdeliverydate="${changeDateFormatYmd_dMY(item.expected_delivery_date)}"
                     data-orderqty="${orderQty}"
                     data-sowingqty="${sowingQty}"
                     data-badgeclass="${badgeClass}"
                     data-badgetext="${badgeText}">
                  <div class="strip-item">
                    <div class="strip-content text-muted font-weight-bold">${index + 1}</div>
                  </div>
                  <div class="strip-item">
                    <div class="strip-content mb-1 d-flex flex-wrap align-items-center" style="gap: 5px;">
                      <span class="order-no-pill">${item.stockorder_no}</span>
                      ${psoBadgeHtml}
                    </div>
                    <div class="strip-content text-muted small">${changeDateFormatYmd_dMY(item.created_at)}</div>
                  </div>
                  <div class="strip-item">
                    <div class="strip-content font-weight-bold">
                       <a href="javascript:void(0)" class="text-primary font-weight-bold">${item.product_name}</a>
                    </div>
                    <div class="strip-content text-muted small">${item.client_name || 'Stock Order'}</div>
                  </div>
                  <div class="strip-item">
                    <div class="strip-content font-weight-bold text-dark"><i class="fa fa-seedling mr-1 text-success"></i>${sowingDateFormatted}</div>
                    <div class="strip-content text-muted small mt-1"><i class="fa fa-truck mr-1 text-secondary"></i>${changeDateFormatYmd_dMY(item.expected_delivery_date)}</div>
                  </div>
                  <div class="strip-item">
                    <span class="badge ${isGrafted ? 'bg-success text-white' : 'bg-secondary text-white'}" style="font-size: 0.8rem; border-radius: 6px; padding: 0.35rem 0.6rem;">
                      ${graftStatus}
                    </span>
                  </div>
                  <div class="strip-item">
                    ${seedBadgeHtml}
                  </div>
                  <div class="action-buttons">
                    <button class="btn-circle btn-view-details view-lifecycle-details" data-id="${item.stockorder_no}" title="Track Order Progress">
                      <i class="fa fa-route"></i>
                    </button>
                  </div>
                </div>
              `;
            });
          } else {
            html = `
              <div class="text-center py-5 bg-white border rounded-3 shadow-sm">
                <p class="text-muted mb-0">No production stock orders found within the selected period.</p>
              </div>
            `;
          }
          $("#productionOrderList").html(html);

          // Update Filters
          populateMultiSelect("#clientFilter", Array.from(uniqueClients).sort());
          populateMultiSelect("#productFilter", Array.from(uniqueProducts).sort());
          populateMultiSelect("#deliveryDateFilter", Array.from(uniqueDates).sort(), true);

          // Initialize Select2 with Checkboxes
          $('.select2').select2({
              width: '100%',
              closeOnSelect: false,
              templateResult: function(data) {
                  if (!data.id) return data.text;
                  var $result = $('<span>' + data.text + '</span>');
                  return $result;
              }
          }).on('select2:select select2:unselect', applyFilters);
        },
        error: function () {
          $("#productionOrderList").html(`
            <div class="text-center py-5 bg-white border rounded-3 shadow-sm">
              <p class="text-danger mb-0">Error getting tracking data. Please verify your connection.</p>
            </div>
          `);
        }
      });
    }

    function populateMultiSelect(selector, items, isDate = false) {
      let $select = $(selector);
      let currentVal = $select.val();
      $select.empty();
      items.forEach(item => {
        let text = isDate ? changeDateFormatYmd_dMY(item) : item;
        $select.append(`<option value="${item}">${text}</option>`);
      });
      $select.val(currentVal).trigger('change.select2');
    }

    function applyFilters() {
      let searchText = $("#productionOrderSearch").val().toLowerCase();
      let deliveryDates = $("#deliveryDateFilter").val() || [];
      let clients = $("#clientFilter").val() || [];
      let products = $("#productFilter").val() || [];

      $(".order-strip").each(function() {
        let searchable = $(this).data("search-term");
        let rowDate = $(this).data("deliverydate");
        let rowClient = $(this).data("client");
        let rowProduct = $(this).data("product");

        let matchesSearch = searchable.indexOf(searchText) > -1;
        let matchesDate = deliveryDates.length === 0 || deliveryDates.includes(rowDate);
        let matchesClient = clients.length === 0 || clients.includes(rowClient);
        let matchesProduct = products.length === 0 || products.includes(rowProduct);

        $(this).toggle(matchesSearch && matchesDate && matchesClient && matchesProduct);
      });
    }

    $("#productionOrderSearch").on("keyup", applyFilters);
    $("#deliveryDateFilter").on("change", applyFilters);
    $("#clientFilter").on("change", applyFilters);
    $("#productFilter").on("change", applyFilters);

    // Open detailed lifecycle modal on click
    $(document).on("click", ".view-lifecycle-details", function () {
      const stockorder_no = $(this).data("id");
      const $strip = $(this).closest(".order-strip");
      const psoNo = $strip.data("psono") || '';
      const orderDate = $strip.data("orderdate");
      const sowingDate = $strip.data("sowingdate") || 'N/A';
      const graftStatus = $strip.data("graftstatus") || 'Non-Grafted';
      const scionSeed = $strip.data("scion-seed") || 'N/A';
      const rootSeed = $strip.data("root-seed") || 'N/A';
      const productName = $strip.data("productname") || 'N/A';
      const clientName = $strip.data("clientname") || 'N/A';
      const expDeliveryDate = $strip.data("expdeliverydate") || 'N/A';
      const orderQty = $strip.data("orderqty") || '0';
      const sowingQty = $strip.data("sowingqty") || '0';
      const badgeClass = $strip.data("badgeclass") || '';
      const badgeText = $strip.data("badgetext") || '';
      const pendingBadgeHtml = `<span class="${badgeClass}">${badgeText}</span>`;

      const isGrafted = graftStatus.toLowerCase() === 'grafted';
      let seedInfoHtml = '';
      if (isGrafted) {
        seedInfoHtml = `<div><span class="text-muted">Scion:</span> <span class="text-dark font-weight-bold">${scionSeed}</span></div>
                        <div class="mt-1"><span class="text-muted">Root:</span> <span class="text-dark font-weight-bold">${rootSeed}</span></div>`;
      } else {
        seedInfoHtml = `<div><span class="text-muted">Scion:</span> <span class="text-dark font-weight-bold">${scionSeed}</span></div>`;
      }
      const now = new Date();
      const realdatetime =
        now.getDate().toString().padStart(2, '0') + '-' +
        now.toLocaleString('en-US', { month: 'short' }) + '-' +
        now.getFullYear() + ' ' +
        now.toLocaleTimeString('en-US', {
          hour: '2-digit',
          minute: '2-digit',
          second: '2-digit',
          hour12: true
        });

      const cardHtml = `
        <div class="card mb-4 border-0 shadow-sm" style="border-radius: 12px; background: #f8fafc; border: 1px solid #e2e8f0 !important;">
          <div class="card-body p-3">
            <div class="row">
              <div class="col-md-4" style="border-right: 1px solid #e2e8f0;"><span class="badge bg-primary text-white px-2 py-1" style="font-size:0.85rem; border-radius:6px;">Date & Time : ${realdatetime}<span></div>
            </div>
            <div class="row mt-4">
              <div class="col-md-2" style="border-right: 1px solid #e2e8f0;">
                <div class="small text-muted font-weight-bold text-uppercase mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Order No/Date/Qty</div>
                <div class="font-weight-bold text-dark mb-1 d-flex flex-wrap align-items-center" style="gap:4px;">
                  <span class="badge bg-primary text-white px-2 py-1" style="font-size:0.85rem; border-radius:6px;">${stockorder_no}</span>
                  ${psoNo ? `<span class="badge bg-purple text-white px-2 py-1" style="font-size:0.8rem; border-radius:6px; background:#6b21a8 !important;">${psoNo}</span>` : ''}
                </div>
                <div class="small text-muted font-weight-bold"><i class="fa fa-calendar-alt mr-1"></i>${orderDate}</div>
                <div class="font-weight-bold text-dark mb-1"><span class="badge bg-warning text-white px-2 py-1" style="font-size:0.85rem; border-radius:6px;">${orderQty}</span></div>        
              </div>
              <div class="col-md-3" style="border-right: 1px solid #e2e8f0;">
                <div class="small text-muted font-weight-bold text-uppercase mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Product /Client Name</div>
                <div class="font-weight-bold text-primary mb-1" style="font-size:0.95rem; word-break: break-word;">${productName}</div>
                <div class="small text-muted font-weight-bold" style="word-break: break-word;"><i class="fa fa-user mr-1"></i>${clientName}</div>
              </div>
              <div class="col-md-2" style="border-right: 1px solid #e2e8f0;">
                <div class="small text-muted font-weight-bold text-uppercase mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Prod. Date/Exp. Deliver Date</div>
                <div class="font-weight-bold text-dark mb-1" style="font-size:0.85rem;"><i class="fa fa-seedling mr-1 text-success"></i>${sowingDate}</div>
                <div class="small text-muted font-weight-bold"><i class="fa fa-truck mr-1 text-secondary"></i>${expDeliveryDate}</div>
              </div>
              <div class="col-md-2" style="border-right: 1px solid #e2e8f0;">
                <div class="small text-muted font-weight-bold text-uppercase mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Product Type</div>
                <div class="font-weight-bold text-dark mb-1" style="font-size:0.95rem; margin-top:6px;">
                  <span class="badge ${isGrafted ? 'bg-success text-white' : 'bg-secondary text-white'}" style="font-size: 0.8rem; border-radius: 6px; padding: 0.35rem 0.6rem;">
                    ${graftStatus}
                  </span>
                </div>
              </div>
              <div class="col-md-3" style="border-right: 1px solid #e2e8f0;">
                <div class="small text-muted font-weight-bold text-uppercase mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Seed Name</div>
                <div style="font-size: 0.85rem; line-height: 1.4;">
                  ${seedInfoHtml}
                </div>
              </div>
            </div>
          </div>
        </div>
      `;

      $("#lblStockOrderTitle").text(stockorder_no);
      $("#lifecycleDetailsBody").html(`<div class="text-center py-5"><i class="fa fa-spinner fa-spin fa-2x text-muted"></i><p class="mt-2 text-muted">Loading tracking details...</p></div>`);
      $("#LifecycleTrackingModal").modal("show");

      $.ajax({
        url: 'modal/reporting.php?action=GetProductionOrderTrackingDetails',
        type: 'POST',
        data: { stockorder_no, projectid },
        dataType: 'JSON',
        success: function (res) {
          if (res.status === 'success' && Array.isArray(res.sowings) && res.sowings.length > 0) {
            let html = cardHtml + `
              <div class="modal-strips-header">
                <div>#</div>
                <div>Sowing Batch No/ Date</div>
                <div>Sowing Location</div>
                <div>Type</div>
                <div>Sowing Qty (Trays/Plants)</div>
                <div>Germ Box Count</div>
                <div>Germ Box Dates (Packing/Opening)</div>
                <div>Seed Lot No</div>
              </div>
              <div class="modal-lifecycle-container">
            `;

            let lastBatch = null;
            let bgToggle = false;

            res.sowings.forEach((row, idx) => {
              const currentBatch = row.sowinglotno_internal || '';
              if (lastBatch !== currentBatch) {
                bgToggle = !bgToggle;
                lastBatch = currentBatch;
              }
              
              const rowBgStyle = bgToggle 
                ? 'background: #ffffff !important;' 
                : 'background: #f8fafc !important;';

              const batchNo = row.sowinglotno_internal ? `<span class="font-weight-bold text-primary">${row.sowinglotno_internal}</span>` : '<span class="text-muted">N/A</span>';
              const sowingDate = row.sowingdate_internal ? `<span class="font-weight-bold text-dark">${changeDateFormatYmd_dMY(row.sowingdate_internal)}</span>` : '<span class="text-muted">N/A</span>';
              const batchWithDate = `<div>${batchNo}</div><div class="small text-muted mt-1 font-weight-bold">${sowingDate}</div>`;

              const locationBadge = row.sowing_location_name && row.sowing_location_name !== 'N/A' 
                ? `<span class="badge bg-success text-white" style="font-size: 0.8rem; padding: 0.35rem 0.6rem;">${row.sowing_location_name}</span>` 
                : '<span class="text-muted">N/A</span>';

              const typeText = row.roottype || 'Scion';
              const typeBadge = `<span class="badge ${typeText === 'Root' ? 'bg-info text-white' : 'bg-secondary text-white'}" style="font-size: 0.8rem; padding: 0.35rem 0.6rem;">${typeText}</span>`;

              const qtyDisplay = `<span class="badge bg-dark text-white py-1 px-2" style="font-size: 0.85rem; font-weight: 600;">${row.tray_qty} Trays / ${row.product_qty} Plants</span>`;
              const germBoxCount = `<span class="badge bg-warning text-dark py-1 px-2" style="font-weight: 700; font-size: 0.85rem;">${row.germ_box_count} Boxes</span>`;
              const seedLot = `<span class="badge bg-light text-danger border py-1 px-2" style="font-weight: 700; font-size: 0.85rem;">${row.seed_lot_no || 'N/A'}</span>`;

              const packingDate = row.boxpackingdate ? changeDateFormatYmd_dMY(row.boxpackingdate) : 'N/A';
              const openingDate = row.boxopeningdate ? changeDateFormatYmd_dMY(row.boxopeningdate) : 'N/A';
              const boxDatesDisplay = `<div><span class="text-muted">Pack:</span> <span class="font-weight-bold text-dark">${packingDate}</span></div>
                                       <div class="mt-1"><span class="text-muted">Open:</span> <span class="font-weight-bold text-dark">${openingDate}</span></div>`;

              html += `
                <div class="modal-order-strip clickable-row" style="${rowBgStyle}" 
                     data-stockorderno="${row.stockorder_no || stockorder_no || ''}"
                     data-sowinglotno="${row.sowinglotno_internal || ''}" 
                     data-roottype="${typeText}"
                     data-sowingtrays="${row.tray_qty || 0}">
                  <div class="modal-strip-content text-muted font-weight-bold">${idx + 1}</div>
                  <div class="modal-strip-content">${batchWithDate}</div>
                  <div class="modal-strip-content">${locationBadge}</div>
                  <div class="modal-strip-content">${typeBadge}</div>
                  <div class="modal-strip-content">${qtyDisplay}</div>
                  <div class="modal-strip-content">${germBoxCount}</div>
                  <div class="modal-strip-content">${boxDatesDisplay}</div>
                  <div class="modal-strip-content">${seedLot}</div>
                </div>
              `;
            });

            html += `</div>`;
            $("#lifecycleDetailsBody").html(html);
          } else {
            $("#lifecycleDetailsBody").html(cardHtml + `
              <div class="text-center py-5 bg-white border rounded-3 shadow-sm">
                <p class="text-muted mb-0">No sowing records found for this production order.</p>
              </div>
            `);
          }
        },
        error: function () {
          $("#lifecycleDetailsBody").html(cardHtml + `<div class="alert alert-danger">Failed to load detailed tracking progress.</div>`);
        }
      });
    });

    // Toggle child grid for detailed sowing batch
    $(document).on("click", ".modal-order-strip.clickable-row", function () {
      const $row = $(this);
      const stockorderno = $row.attr("data-stockorderno") || $("#lblStockOrderTitle").text() || '';
      const sowinglotno = $row.attr("data-sowinglotno");
      const roottype = $row.attr("data-roottype");

      console.log("modal-order-strip clicked:", { stockorderno, sowinglotno, roottype, projectid });

      if (sowinglotno === undefined || sowinglotno === null) {
        console.warn("sowinglotno attribute is missing, skipping click action");
        return;
      }

      const $next = $row.next();
      if ($next.hasClass("sowing-child-grid-container")) {
        $next.slideToggle(200);
        return;
      }

      const $container = $('<div class="sowing-child-grid-container" style="display:none;"><div class="text-center py-2"><i class="fa fa-spinner fa-spin text-muted"></i> Loading details...</div></div>');
      $row.after($container);
      $container.slideDown(200);

      $.ajax({
        url: 'modal/reporting.php?action=GetSowingBatchBedDetails',
        type: 'POST',
        data: { stockorder_no: stockorderno, sowinglotno_internal: sowinglotno, roottype: roottype, projectid: projectid },
        dataType: 'json',
        success: function (res) {
          console.log("GetSowingBatchBedDetails response:", res);
          const sowingtrays = parseInt($row.attr("data-sowingtrays")) || 0;
          let detailsArray = (res.status === 'success' && Array.isArray(res.details)) ? res.details : [];
          
          let totalPolyhouseTrays = 0;
          let totalVerifiedTrays = 0;
          let totalUnverifiedTrays = 0;
          let totalFailureTrays = 0;
          let totalPolyhousePlants = 0;
          let totalFailedPlants = 0;

          detailsArray.forEach(item => {
            const trays = parseInt(item.qty_of_trays || 0);
            const verified = parseInt(item.verified_trays || 0);
            const unverified = parseInt(item.unverified_trays || 0);
            const failure = parseInt(item.failure_trays || 0);
            const plants = parseFloat(item.total_plants || 0);
            const failedPlants = parseFloat(item.failed_plants || 0);

            totalPolyhouseTrays += trays;
            totalVerifiedTrays += verified;
            totalUnverifiedTrays += unverified;
            totalFailureTrays += failure;
            totalPolyhousePlants += plants;
            totalFailedPlants += failedPlants;
          });

          const totalSuccessPlants = Math.max(0, totalPolyhousePlants - totalFailedPlants);
          const verifiedPct = totalPolyhouseTrays > 0 ? ((totalVerifiedTrays / totalPolyhouseTrays) * 100).toFixed(1) : '0.0';
          const unverifiedPct = totalPolyhouseTrays > 0 ? ((totalUnverifiedTrays / totalPolyhouseTrays) * 100).toFixed(1) : '0.0';

          // Rule: sum of plants of germination failed / sum plants of germinated trays * 100
          const overallFailurePct = totalPolyhousePlants > 0 ? ((totalFailedPlants / totalPolyhousePlants) * 100).toFixed(2) : '0.00';
          const overallSuccessPct = totalPolyhousePlants > 0 ? (100 - parseFloat(overallFailurePct)).toFixed(2) : '100.00';

          const diff = totalPolyhouseTrays - sowingtrays;
          let statusBannerHtml = '';
          if (diff < 0) {
            const missingTrays = Math.abs(diff);
            statusBannerHtml = `
              <div class="sowing-status-banner missing">
                <i class="fa fa-exclamation-circle"></i>
                <span>Status: Sowing trays count and Polyhouse count do not match. <strong>Missing ${missingTrays} Trays</strong> in Polyhouse (Sowing: ${sowingtrays} | Polyhouse: ${totalPolyhouseTrays})</span>
              </div>
            `;
          } else if (diff > 0) {
            statusBannerHtml = `
              <div class="sowing-status-banner extra">
                <i class="fa fa-info-circle"></i>
                <span>Status: Sowing trays count and Polyhouse count do not match. <strong>Extra ${diff} Trays</strong> in Polyhouse (Sowing: ${sowingtrays} | Polyhouse: ${totalPolyhouseTrays})</span>
              </div>
            `;
          } else {
            statusBannerHtml = `
              <div class="sowing-status-banner matched">
                <i class="fa fa-check-circle"></i>
                <span>Status: Sowing trays count and Polyhouse count match. <strong>Perfect Match</strong> (Qty: ${sowingtrays} Trays)</span>
              </div>
            `;
          }

          let summaryBarHtml = `
            <div class="batch-summary-grid">
              <div class="batch-summary-card">
                <div class="summary-icon-box total"><i class="fa fa-layer-group"></i></div>
                <div class="summary-info">
                  <div class="summary-label">Total Trays</div>
                  <div class="summary-value">${totalPolyhouseTrays} <span style="font-size: 0.8rem; font-weight:600; color:#64748b;">Trays</span></div>
                  <div class="summary-sub">${detailsArray.length} Bed(s) (${totalPolyhousePlants.toLocaleString()} Plants)</div>
                </div>
              </div>
              <div class="batch-summary-card">
                <div class="summary-icon-box verified"><i class="fa fa-check-circle"></i></div>
                <div class="summary-info">
                  <div class="summary-label">Verified Trays</div>
                  <div class="summary-value text-success">${totalVerifiedTrays} <span style="font-size: 0.8rem; font-weight:600; color:#16a34a;">Trays</span></div>
                  <div class="summary-sub">${verifiedPct}% Verified</div>
                </div>
              </div>
              <div class="batch-summary-card">
                <div class="summary-icon-box unverified"><i class="fa fa-hourglass-half"></i></div>
                <div class="summary-info">
                  <div class="summary-label">Unverified Trays</div>
                  <div class="summary-value text-warning">${totalUnverifiedTrays} <span style="font-size: 0.8rem; font-weight:600; color:#d97706;">Trays</span></div>
                  <div class="summary-sub">${unverifiedPct}% Pending</div>
                </div>
              </div>
              <div class="batch-summary-card">
                <div class="summary-icon-box failure"><i class="fa fa-exclamation-triangle"></i></div>
                <div class="summary-info">
                  <div class="summary-label">Germination Failure %</div>
                  <div class="summary-value text-danger">${overallFailurePct}%</div>
                  <div class="summary-sub">${totalFailedPlants.toLocaleString()} Failed Plants (${totalFailureTrays} Trays)</div>
                </div>
              </div>
              <div class="batch-summary-card">
                <div class="summary-icon-box success"><i class="fa fa-seedling"></i></div>
                <div class="summary-info">
                  <div class="summary-label">Germination Success %</div>
                  <div class="summary-value" style="color:#0d9488;">${overallSuccessPct}%</div>
                  <div class="summary-sub">${totalSuccessPlants.toLocaleString()} Healthy Plants</div>
                </div>
              </div>
            </div>
          `;

          if (detailsArray.length > 0) {
            let tableHtml = statusBannerHtml + summaryBarHtml + `
              <div class="bed-details-card">
                <div class="bed-details-header">
                  <div class="bed-details-header-item">Polyhouse / Location</div>
                  <div class="bed-details-header-item">Bed No</div>
                  <div class="bed-details-header-item">Total Trays</div>
                  <div class="bed-details-header-item">Verified / Unverified</div>
                  <div class="bed-details-header-item">Verified By & Date</div>
                  <div class="bed-details-header-item">Germ. Failure %</div>
                  <div class="bed-details-header-item">Disease (Yes/No)</div>
                  <div class="bed-details-header-item">Dispatch Date (Status)</div>
                </div>
            `;

            detailsArray.forEach(item => {
              const locPHName = `${item.location_name || 'N/A'} / ${item.polyhousename || 'N/A'}`;
              const bedNo = item.bed_no ? `<span class="badge bg-primary text-white font-weight-bold" style="font-size: 0.82rem; padding: 0.3rem 0.65rem; border-radius: 6px;"><i class="fa fa-layer-group me-1"></i>${item.bed_no}</span>` : '<span class="badge bg-secondary text-white" style="font-size: 0.8rem;">N/A</span>';
              const qtyTrays = `${item.qty_of_trays || 0} Trays`;
              const totalBedPlants = parseInt(item.total_plants || 0);
              const failedBedPlants = parseInt(item.failed_plants || 0);
              const failureBedTrays = parseInt(item.failure_trays || 0);
              const failurePct = `${item.failure_percentage || 0}%`;

              let failurePlantDisplay = '';
              if (failedBedPlants > 0) {
                failurePlantDisplay = `
                  <div><span class="badge-yellow-pill">${failurePct}</span></div>
                  <div class="small mt-1 font-weight-bold" style="font-size: 0.74rem; color: #dc2626;" title="${failedBedPlants.toLocaleString()} Failed Plants out of ${totalBedPlants.toLocaleString()} Total Plants">
                    ${failedBedPlants.toLocaleString()} / ${totalBedPlants.toLocaleString()} Plants
                  </div>
                `;
              } else {
                failurePlantDisplay = `
                  <div><span class="badge-yellow-pill">${failurePct}</span></div>
                  <div class="small text-muted mt-1" style="font-size: 0.74rem;">
                    0 / ${totalBedPlants.toLocaleString()} Plants
                  </div>
                `;
              }

              const verifiedCount = parseInt(item.verified_trays || 0);
              const unverifiedCount = parseInt(item.unverified_trays || 0);
              
              let verificationStatusHtml = '';
              if (verifiedCount > 0 && unverifiedCount > 0) {
                verificationStatusHtml = `
                  <div class="d-flex flex-wrap gap-1 align-items-center">
                    <span class="badge bg-success text-white py-1 px-2" style="font-size: 0.75rem; font-weight: 700;" title="Verified Trays"><i class="fa fa-check-circle me-1"></i>${verifiedCount} Verified</span>
                    <span class="badge bg-warning text-dark py-1 px-2" style="font-size: 0.75rem; font-weight: 700;" title="Unverified Trays"><i class="fa fa-hourglass-half me-1"></i>${unverifiedCount} Unverified</span>
                  </div>
                `;
              } else if (verifiedCount > 0 && unverifiedCount === 0) {
                verificationStatusHtml = `
                  <span class="badge bg-success text-white py-1 px-2" style="font-size: 0.78rem; font-weight: 700;" title="All Trays Verified"><i class="fa fa-check-double me-1"></i>All ${verifiedCount} Verified</span>
                `;
              } else {
                verificationStatusHtml = `
                  <span class="badge bg-secondary text-white py-1 px-2" style="font-size: 0.78rem; font-weight: 600;" title="Unverified Trays"><i class="fa fa-hourglass-half me-1"></i>${unverifiedCount} Unverified</span>
                `;
              }

              let verifierDisplay = '';
              if (verifiedCount > 0) {
                const vName = item.verified_by_name || item.verified_by || 'Verified';
                const vDate = item.verified_date ? changeDateFormatYmd_dMY(item.verified_date.split(' ')[0]) + ' ' + (item.verified_date.split(' ')[1] || '').substring(0, 5) : '';
                verifierDisplay = `
                  <div><span class="font-weight-bold text-dark" style="font-size: 0.82rem;"><i class="fa fa-user-check text-success me-1"></i>${vName}</span></div>
                  ${vDate ? `<div class="small text-muted mt-1" style="font-size: 0.75rem;"><i class="fa fa-calendar-check me-1 text-secondary"></i>${vDate}</div>` : ''}
                `;
              } else {
                verifierDisplay = `<span class="text-muted small"><i class="fa fa-minus-circle me-1"></i>Pending</span>`;
              }
              
              let diseaseBadge = '';
              if (item.disease_detected === 'Yes') {
                diseaseBadge = '<span class="badge-red-pill">Yes</span>';
              } else {
                diseaseBadge = '<span class="badge-green-pill">No</span>';
              }

              const dDate = item.dispatch_date ? changeDateFormatYmd_dMY(item.dispatch_date) : 'N/A';
              
              let statusBadge = '';
              if (item.dispatch_status === 'Dispatched') {
                statusBadge = '<span class="badge-status-dispatch dispatched"><i class="fa fa-check"></i>Dispatched</span>';
              } else if (item.dispatch_status === 'Not Dispatched') {
                statusBadge = '<span class="badge-status-dispatch not-dispatched"><i class="fa fa-exclamation-triangle"></i>Not Dispatched</span>';
              } else {
                statusBadge = '<span class="badge-status-dispatch pending"><i class="fa fa-clock"></i>Pending</span>';
              }

              tableHtml += `
                <div class="bed-details-row">
                  <div class="bed-detail-item" data-label="Polyhouse / Location">
                    <span class="value-content label-bold">${locPHName}</span>
                  </div>
                  <div class="bed-detail-item" data-label="Bed No">
                    <span class="value-content">${bedNo}</span>
                  </div>
                  <div class="bed-detail-item" data-label="Total Trays">
                    <span class="value-content"><span class="badge-dark-pill">${qtyTrays}</span></span>
                  </div>
                  <div class="bed-detail-item" data-label="Verified / Unverified">
                    <span class="value-content">${verificationStatusHtml}</span>
                  </div>
                  <div class="bed-detail-item" data-label="Verified By & Date">
                    <div class="value-content">${verifierDisplay}</div>
                  </div>
                  <div class="bed-detail-item" data-label="Germ. Failure %">
                    <div class="value-content">${failurePlantDisplay}</div>
                  </div>
                  <div class="bed-detail-item" data-label="Disease (Yes/No)">
                    <span class="value-content">${diseaseBadge}</span>
                  </div>
                  <div class="bed-detail-item" data-label="Dispatch Date (Status)">
                    <div class="value-content">
                      <div class="font-weight-bold text-dark">${dDate}</div>
                      <div class="mt-1">${statusBadge}</div>
                    </div>
                  </div>
                </div>
              `;
            });

            tableHtml += `
              </div>
            `;
            $container.html(tableHtml);
          } else {
            const noDetailsMsg = res.message || 'No polyhouse bed details found for this batch.';
            $container.html(statusBannerHtml + `<div class="text-center py-3 text-muted border rounded-3 bg-white shadow-sm mt-2">${noDetailsMsg}</div>`);
          }
        },
        error: function (xhr, status, error) {
          console.error("GetSowingBatchBedDetails AJAX error:", status, error, xhr.responseText);
          $container.html('<div class="text-center py-2 text-danger">Failed to load details.</div>');
        }
      });
    });
  });
</script>
