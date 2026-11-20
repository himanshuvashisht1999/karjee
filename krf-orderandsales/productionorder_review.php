<?php
include('../header.php');
include('functions/orders_functions.php');

$mdid = "";
if (isset($_GET['mdid'])) {
  $mdid = $_GET['mdid'];
}
$dddr = loginmodulesdetails_frommaster($authconn, $companyid, $subcompanyid, $projectid, "moduleid", $mdid);
$groupid = "";
if (count($dddr) > 0) {
  $groupid = $dddr[0]['groupid'];
}
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">

<style>
  :root {
    --prod-primary: #0f766e;
    --prod-primary-hover: #0d9488;
    --prod-primary-light: #f0fdfa;
    --prod-accent: #f59e0b;
    --prod-dark: #0f172a;
    --prod-slate: #475569;
    --prod-border: #e2e8f0;
    --prod-bg: #f8fafc;
    --prod-card-shadow: 0 4px 18px rgba(15, 23, 42, 0.05);
  }

  .production-dashboard {
    padding: 1.25rem 1.5rem 3rem;
    background: var(--prod-bg);
    min-height: 100vh;
  }

  /* 1. Header Card - Light Background with Dark Text */
  .main-title-card {
    background: #ffffff;
    color: var(--prod-dark);
    padding: 1.25rem 1.6rem;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(15, 23, 42, 0.05);
    margin-bottom: 1.15rem;
    border: 1px solid #e2e8f0;
    position: relative;
  }

  .header-brand h4 {
    letter-spacing: -0.3px;
    font-size: 1.35rem;
    color: #0f172a;
    font-weight: 800;
  }

  .header-brand h4 i {
    color: var(--prod-primary);
  }

  .header-brand p {
    color: #64748b;
    font-size: 0.85rem;
  }

  .stat-chips-container {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: flex-end;
  }

  .stat-chip {
    border-radius: 12px;
    padding: 6px 14px;
    font-size: 0.825rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
    transition: all 0.25s ease;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
  }

  .stat-chip:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(15, 23, 42, 0.08);
  }

  .stat-chip-orders {
    background: #fffbeb;
    border: 1px solid #fde68a;
    color: #92400e;
  }
  .stat-chip-orders .chip-value { color: #78350f; font-weight: 800; font-size: 0.95rem; }

  .stat-chip-total {
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    color: #0369a1;
  }
  .stat-chip-total .chip-value { color: #075985; font-weight: 800; font-size: 0.95rem; }

  .stat-chip-alloc {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    color: #166534;
  }
  .stat-chip-alloc .chip-value { color: #14532d; font-weight: 800; font-size: 0.95rem; }

  .stat-chip-bal {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #991b1b;
  }
  .stat-chip-bal .chip-value { color: #7f1d1d; font-weight: 800; font-size: 0.95rem; }

  .stat-chip .chip-label {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
    opacity: 0.9;
  }

  .header-actions-bar {
    border-top: 1px solid #f1f5f9;
  }

  .btn-export-dropdown {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: #334155;
    font-weight: 600;
    border-radius: 20px;
    padding: 6px 16px;
    height: 38px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    transition: all 0.2s ease;
  }
  .btn-export-dropdown:hover {
    background: #f8fafc;
    border-color: #94a3b8;
    color: #0f172a;
  }

  .btn-new-order {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: #ffffff;
    font-weight: 700;
    border-radius: 20px;
    padding: 6px 18px;
    height: 38px;
    border: none;
    box-shadow: 0 3px 10px rgba(245, 158, 11, 0.28);
    transition: all 0.2s ease;
  }
  .btn-new-order:hover {
    background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
    color: #ffffff;
    transform: translateY(-1px);
    box-shadow: 0 5px 14px rgba(245, 158, 11, 0.38);
  }

  .btn-reload-page {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: #475569;
    border-radius: 20px;
    padding: 6px 14px;
    height: 38px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    transition: all 0.2s ease;
  }
  .btn-reload-page:hover {
    background: #f8fafc;
    color: var(--prod-primary);
    border-color: var(--prod-primary);
  }

  /* 2. Filter Panel - 1 Line with Expand Option */
  .filter-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 0.95rem 1.25rem;
    box-shadow: var(--prod-card-shadow);
    border: 1px solid var(--prod-border);
    margin-bottom: 1.15rem;
  }

  /* 1-Line Flex / Grid with Crop Subcategory */
  .filter-row-primary {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr)) auto;
    gap: 0.75rem;
    align-items: flex-end;
  }

  .filter-row-advanced {
    transition: all 0.3s ease;
  }

  .filter-grid-secondary {
    display: grid;
    grid-template-columns: 1.2fr 1.2fr 1.1fr 1.5fr;
    gap: 0.75rem;
    align-items: flex-end;
  }

  .filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    min-width: 0;
  }

  .filter-label {
    font-size: 0.71rem;
    font-weight: 700;
    color: var(--prod-slate);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
    gap: 5px;
    margin-bottom: 0;
  }

  .filter-label i {
    font-size: 0.76rem;
    color: var(--prod-primary);
  }

  .filter-control, 
  .form-select-custom {
    width: 100%;
    height: 38px;
    padding: 0.4rem 0.75rem;
    border-radius: 9px;
    border: 1px solid #cbd5e1;
    font-size: 0.85rem;
    font-weight: 500;
    color: #1e293b;
    background-color: #ffffff;
    transition: all 0.2s ease;
  }

  .filter-control:focus,
  .form-select-custom:focus {
    border-color: var(--prod-primary);
    outline: none;
    box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.15);
  }

  .btn-toggle-more {
    height: 38px;
    border-radius: 9px;
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    color: #334155;
    font-weight: 600;
    font-size: 0.825rem;
    padding: 0 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
    transition: all 0.2s ease;
  }

  .btn-toggle-more:hover, 
  .btn-toggle-more.active {
    background: #e6fffa;
    border-color: #0d9488;
    color: #0f766e;
  }

  .btn-reset-filters {
    height: 38px;
    border-radius: 9px;
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    color: #475569;
    font-weight: 600;
    font-size: 0.825rem;
    padding: 0 0.85rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    white-space: nowrap;
    transition: all 0.2s ease;
  }

  .btn-reset-filters:hover {
    background: #fee2e2;
    border-color: #fca5a5;
    color: #ef4444;
  }

  /* Select2 Customization */
  .select2-container--default .select2-selection--multiple {
    border: 1px solid #cbd5e1 !important;
    border-radius: 9px !important;
    min-height: 38px !important;
    padding: 2px 6px !important;
    background: #ffffff !important;
  }

  .select2-container--default.select2-container--focus .select2-selection--multiple {
    border-color: var(--prod-primary) !important;
    box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.15) !important;
  }

  .select2-container--default .select2-selection--multiple .select2-selection__choice {
    background-color: var(--prod-primary-light) !important;
    border: 1px solid #ccfbf1 !important;
    color: var(--prod-primary) !important;
    border-radius: 6px !important;
    font-size: 0.78rem !important;
    font-weight: 600 !important;
    padding: 2px 7px !important;
  }

  .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
    color: var(--prod-primary) !important;
    margin-right: 5px !important;
  }

  .search-wrapper-custom {
    position: relative;
    width: 100%;
  }

  .search-wrapper-custom .search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 0.85rem;
    pointer-events: none;
  }

  .search-wrapper-custom input {
    padding-left: 36px !important;
    padding-right: 32px !important;
    height: 38px !important;
    border-radius: 9px !important;
  }

  .search-wrapper-custom .clear-icon {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    cursor: pointer;
    display: none;
    font-size: 0.85rem;
  }

  .search-wrapper-custom .clear-icon:hover {
    color: #334155;
  }

  /* 3. Strips Container & Sowing Date Column */
  .order-strips-wrapper {
    max-height: 74vh;
    overflow-y: auto;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    box-shadow: var(--prod-card-shadow);
  }

  .order-strips-container {
    display: flex;
    flex-direction: column;
    padding: 0.6rem;
    gap: 0.5rem;
  }

  /* Desktop Sticky Header with SOWING DATE Column */
  .strips-header {
    position: sticky !important;
    top: 0;
    z-index: 20;
    display: grid;
    grid-template-columns: 38px 1.25fr 1.8fr 1.15fr 1.25fr 1.55fr 98px;
    padding: 0.85rem 1.15rem;
    color: #0c4a6e !important;
    font-size: 0.74rem;
    text-transform: uppercase;
    font-weight: 800;
    letter-spacing: 0.6px;
    background: #f0f9ff !important;
    border-bottom: 2px solid #bae6fd;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
  }

  /* Order Strip Row (Desktop Default) */
  .order-strip {
    background: #ffffff;
    border-radius: 10px;
    padding: 0.75rem 1.15rem;
    display: grid;
    grid-template-columns: 38px 1.25fr 1.8fr 1.15fr 1.25fr 1.55fr 98px;
    align-items: center;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    border: 1px solid #e2e8f0;
    transition: all 0.2s ease-in-out;
    position: relative;
  }

  .order-strip:hover {
    background-color: #f8fafc !important;
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.08);
    transform: translateY(-1px);
    border-color: #99f6e4;
    z-index: 2;
  }

  .strip-item {
    min-width: 0;
    padding-right: 0.65rem;
  }

  .order-no-pill {
    background: #f0fdf4;
    color: #0f766e;
    padding: 0.25rem 0.65rem;
    border-radius: 7px;
    font-weight: 700;
    font-size: 0.8rem;
    border: 1px solid #ccfbf1;
    font-family: 'Courier New', Courier, monospace;
    display: inline-block;
    letter-spacing: 0.3px;
  }

  /* Category & Product Badges */
  .badge-category {
    font-size: 0.7rem;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    letter-spacing: 0.2px;
  }

  .badge-farmer {
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
  }

  .badge-trader {
    background: #f5f3ff;
    color: #6d28d9;
    border: 1px solid #ddd6fe;
  }

  .badge-stock {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #cbd5e1;
  }

  .badge-ptype {
    font-size: 0.68rem;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
  }

  .badge-grafted {
    background: #f0fdfa;
    color: #0f766e;
    border: 1px solid #99f6e4;
  }

  .badge-nongrafted {
    background: #f8fafc;
    color: #64748b;
    border: 1px solid #e2e8f0;
  }

  .badge-crop-tag {
    font-size: 0.68rem;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 6px;
    background: #fefce8;
    color: #a16207;
    border: 1px solid #fef08a;
    display: inline-flex;
    align-items: center;
    gap: 3px;
  }

  .product-title-text {
    font-size: 0.88rem;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.35;
  }

  .client-name-text {
    font-size: 0.8rem;
    color: #475569;
    font-weight: 500;
  }

  .remark-callout {
    background: #fffbeb;
    color: #92400e;
    border: 1px solid #fde68a;
    border-radius: 6px;
    font-size: 0.75rem;
    padding: 3px 8px;
    display: inline-block;
    max-width: 100%;
    margin-top: 4px;
    word-break: break-word;
  }

  /* Quantity Badges & Bar */
  .qty-badges-wrapper {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  .qty-badges-row {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
  }

  .qty-chip {
    padding: 3px 7px;
    border-radius: 6px;
    font-size: 0.77rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    white-space: nowrap;
  }

  .qty-chip-order {
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    color: #334155;
  }

  .qty-chip-alloc {
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #065f46;
  }

  .qty-chip-bal {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #991b1b;
  }

  .qty-progress-track {
    height: 4px;
    width: 100%;
    background: #e2e8f0;
    border-radius: 3px;
    overflow: hidden;
    margin-top: 2px;
  }

  .qty-progress-bar {
    height: 100%;
    border-radius: 3px;
    transition: width 0.3s ease;
  }

  /* Action Buttons */
  .action-buttons {
    display: flex;
    gap: 0.35rem;
    justify-content: flex-end;
  }

  .btn-circle {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    transition: all 0.2s ease;
    font-size: 0.85rem;
    cursor: pointer;
  }

  .btn-circle:hover {
    transform: translateY(-2px);
  }

  .btn-view-details { background: #f1f5f9; color: #475569; }
  .btn-view-details:hover { background: #334155; color: #ffffff; }
  
  .btn-edit-order { background: #e0f2fe; color: #0284c7; }
  .btn-edit-order:hover { background: #0284c7; color: #ffffff; }

  .btn-delete-order { background: #fee2e2; color: #ef4444; border: 1px solid #fca5a5; }
  .btn-delete-order:hover { background: #ef4444; color: #ffffff; border-color: #dc2626; transform: scale(1.08); }

  .export-dropdown-menu {
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    padding: 8px 0;
  }

  .export-dropdown-menu .dropdown-item {
    font-size: 0.85rem;
    padding: 8px 16px;
    font-weight: 500;
    color: #334155;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .export-dropdown-menu .dropdown-item:hover {
    background-color: #f0fdf4;
    color: #0d9488;
  }

  /* ========================================================
     MOBILE & RESPONSIVE BREAKPOINTS (Tablets & Phones)
     ======================================================== */
  @media (max-width: 1299.98px) {
    .filter-row-primary {
      grid-template-columns: repeat(3, 1fr) auto;
    }
    .filter-grid-secondary {
      grid-template-columns: 1fr 1fr;
    }
  }

  @media (max-width: 991.98px) {
    .production-dashboard {
      padding: 0.75rem 0.75rem 2.5rem;
    }

    .main-title-card {
      padding: 1.15rem;
      border-radius: 14px;
    }

    .header-top-bar {
      flex-direction: column;
      align-items: stretch !important;
      gap: 1rem;
    }

    .stat-chips-container {
      justify-content: flex-start;
      gap: 6px;
    }

    .stat-chip {
      padding: 5px 10px;
      font-size: 0.75rem;
      flex: 1 1 calc(50% - 6px);
      justify-content: space-between;
    }

    .header-actions-bar {
      flex-direction: row;
      flex-wrap: wrap;
      justify-content: space-between !important;
      gap: 0.5rem;
    }

    .filter-row-primary {
      grid-template-columns: repeat(2, 1fr);
      gap: 0.65rem;
    }

    .filter-row-primary .filter-group-actions {
      grid-column: span 2;
    }

    .filter-grid-secondary {
      grid-template-columns: 1fr;
      gap: 0.65rem;
    }

    /* Hide table header on mobile */
    .strips-header {
      display: none !important;
    }

    /* Transform Strip to Modern Mobile Card */
    .order-strip {
      display: flex;
      flex-direction: column;
      padding: 1rem;
      border-radius: 12px;
      gap: 0.75rem;
      border: 1px solid #e2e8f0;
      box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
    }

    .mobile-card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      width: 100%;
      border-bottom: 1px dashed #e2e8f0;
      padding-bottom: 0.6rem;
    }

    .mobile-badges-row {
      display: flex;
      gap: 6px;
      align-items: center;
      flex-wrap: wrap;
    }

    .mobile-card-body {
      width: 100%;
    }

    .mobile-dates-row {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 0.5rem;
      background: #f8fafc;
      padding: 0.6rem 0.75rem;
      border-radius: 8px;
      margin-top: 0.5rem;
      border: 1px solid #f1f5f9;
    }

    .mobile-date-item {
      font-size: 0.78rem;
    }

    .mobile-date-label {
      font-size: 0.67rem;
      text-transform: uppercase;
      font-weight: 700;
      color: #64748b;
      margin-bottom: 2px;
    }

    .mobile-qtys-row {
      width: 100%;
      margin-top: 0.35rem;
    }

    .mobile-card-actions {
      display: flex;
      justify-content: space-between;
      align-items: center;
      width: 100%;
      border-top: 1px solid #f1f5f9;
      padding-top: 0.6rem;
      margin-top: 0.2rem;
    }

    .action-buttons {
      width: 100%;
      justify-content: flex-end;
      gap: 0.5rem;
    }

    .btn-circle {
      width: 36px;
      height: 36px;
      border-radius: 10px;
      font-size: 0.95rem;
    }
  }

  @media (max-width: 576px) {
    .filter-row-primary {
      grid-template-columns: 1fr;
    }
    .filter-row-primary .filter-group-actions {
      grid-column: span 1;
    }
    .header-actions-bar {
      flex-direction: column;
      align-items: stretch !important;
    }
    .header-actions-bar .btn,
    .header-actions-bar .dropdown {
      width: 100%;
    }
    .header-actions-bar .dropdown button {
      width: 100%;
      justify-content: center;
    }
    .mobile-dates-row {
      grid-template-columns: 1fr;
      gap: 0.4rem;
    }
  }

  /* Allocation Modal & Responsive Table */
  .alloc-table-responsive {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
  }

  .alloc-table-responsive::-webkit-scrollbar {
    height: 6px;
  }

  .alloc-table-responsive::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
  }

  .alloc-table-responsive::-webkit-scrollbar-thumb {
    background: #0d9488;
    border-radius: 4px;
  }

  .alloc-table {
    width: 100%;
    margin-bottom: 0;
  }

  .alloc-table thead th {
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #475569;
    background-color: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    padding: 0.75rem 0.85rem;
    white-space: nowrap;
  }

  .alloc-table tbody td {
    padding: 0.75rem 0.85rem;
    font-size: 0.85rem;
    border-bottom: 1px solid #f1f5f9;
  }

  @media (max-width: 576px) {
    #AllocationModal .modal-dialog {
      margin: 0.5rem;
      max-width: calc(100% - 1rem);
    }
    #AllocationModal .modal-body {
      padding: 0.65rem !important;
    }
    #AllocationModal .modal-header {
      padding: 0.75rem 1rem !important;
    }
    #AllocationModal .modal-title {
      font-size: 0.95rem;
    }
    .alloc-table thead th {
      font-size: 0.72rem;
      padding: 0.55rem 0.65rem;
    }
    .alloc-table tbody td {
      font-size: 0.8rem;
      padding: 0.55rem 0.65rem;
    }
  }
</style>

<div class="production-dashboard">
  
  <!-- Header Card (Light Background & Dark Text) -->
  <div class="main-title-card">
    <div class="d-flex justify-content-between align-items-center header-top-bar w-100 mb-2">
      <div class="header-brand">
        <h4 class="mb-1 d-flex align-items-center gap-2">
          <i class="fa fa-industry"></i> Production Stock Orders
        </h4>
        <p class="mb-0">Manage plant production, sowing schedules, and stock allocations.</p>
      </div>

      <!-- Quick Summary Chips -->
      <div class="stat-chips-container">
        <div class="stat-chip stat-chip-orders" title="Total Production Orders">
          <span class="chip-label"><i class="fa fa-clipboard-list text-warning me-1"></i>Orders:</span>
          <span class="chip-value" id="showingOrderCount">0</span>
        </div>
        <div class="stat-chip stat-chip-total" title="Total Ordered Quantity">
          <span class="chip-label"><i class="fa fa-cubes text-info me-1"></i>Total Qty:</span>
          <span class="chip-value" id="showingTotalQty">0</span>
        </div>
        <div class="stat-chip stat-chip-alloc" title="Total Allocated Quantity">
          <span class="chip-label"><i class="fa fa-check-circle text-success me-1"></i>Allocated:</span>
          <span class="chip-value" id="showingAllocQty">0</span>
        </div>
        <div class="stat-chip stat-chip-bal" title="Total Balance / Open Quantity">
          <span class="chip-label"><i class="fa fa-hourglass-half text-danger me-1"></i>Balance:</span>
          <span class="chip-value" id="showingBalQty">0</span>
        </div>
      </div>
    </div>

    <!-- Action Bar -->
    <div class="d-flex justify-content-between align-items-center header-actions-bar w-100 pt-2">
      <div class="text-muted small d-none d-md-block">
        <i class="fa fa-filter text-teal me-1" style="color: #0f766e;"></i> Real-time multi-dimensional production tracking
      </div>
      <div class="d-flex align-items-center gap-2">
        
        <!-- Export Dropdown -->
        <div class="dropdown">
          <button class="btn btn-sm btn-export-dropdown dropdown-toggle d-flex align-items-center gap-1" type="button" id="exportDropdownBtn" data-bs-toggle="dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fa fa-file-export text-success"></i> Export <span class="caret"></span>
          </button>
          <div class="dropdown-menu dropdown-menu-end export-dropdown-menu shadow-lg" aria-labelledby="exportDropdownBtn">
            <a class="dropdown-item" href="javascript:void(0)" onclick="exportProductionOrders('excel')">
              <i class="fa fa-file-excel text-success"></i> Export to Excel (.csv)
            </a>
            <a class="dropdown-item" href="javascript:void(0)" onclick="exportProductionOrders('csv')">
              <i class="fa fa-file-csv text-primary"></i> Export to CSV
            </a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="javascript:void(0)" onclick="window.print()">
              <i class="fa fa-print text-secondary"></i> Print View
            </a>
          </div>
        </div>

        <?php if ($loginroleid < 2): ?>
          <button class="btn btn-sm btn-new-order d-flex align-items-center gap-1 newproductionorder">
            <i class="fa fa-plus-circle"></i> New Production Order
          </button>
        <?php endif; ?>

        <button class="btn btn-sm btn-reload-page d-flex align-items-center gap-1" onclick="window.location.reload();" title="Reload Page Data">
          <i class="fa fa-sync-alt"></i>
        </button>

      </div>
    </div>
  </div>

  <!-- Re-designed Filter Card (1 Line Default + Expand Option) -->
  <div class="filter-card">
    
    <!-- Line 1: Primary Filters (Always Visible on 1 Line) -->
    <div class="filter-row-primary">
      
      <!-- 1. Year Filter -->
      <div class="filter-group">
        <label class="filter-label" for="psoYearlist"><i class="fa fa-calendar-alt"></i> Year</label>
        <select class="form-select-custom filter-control" name="psoYearlist" id="psoYearlist" title="Filter by Order or Delivery Year"></select>
      </div>

      <!-- 2. Order Month Filter -->
      <div class="filter-group">
        <label class="filter-label" for="psoMonthlist"><i class="fa fa-calendar"></i> Order Month</label>
        <select class="form-select-custom filter-control" name="psoMonthlist" id="psoMonthlist">
          <option value="all">All Months</option>
        </select>
      </div>

      <!-- 3. Dispatch Month Filter -->
      <div class="filter-group">
        <label class="filter-label" for="dispatchMonthFilter"><i class="fa fa-truck-loading"></i> Dispatch Month</label>
        <select class="form-select-custom filter-control" id="dispatchMonthFilter">
          <option value="all">All Months</option>
        </select>
      </div>

      <!-- 4. Client Category Filter (Farmer vs Trader) -->
      <div class="filter-group">
        <label class="filter-label" for="categoryFilter"><i class="fa fa-users"></i> Category</label>
        <select class="form-select-custom filter-control" id="categoryFilter">
          <option value="all">All Categories</option>
          <option value="farmer">🌾 Farmer Orders</option>
          <option value="trader">🏢 Trader Orders</option>
        </select>
      </div>

      <!-- 5. Product Type Filter (Grafted vs Non-Grafted) -->
      <div class="filter-group">
        <label class="filter-label" for="productTypeFilter"><i class="fa fa-seedling"></i> Product Type</label>
        <select class="form-select-custom filter-control" id="productTypeFilter">
          <option value="all">All Product Types</option>
          <option value="grafted">🌱 Grafted</option>
          <option value="nongrafted">🌿 Non-Grafted</option>
        </select>
      </div>

      <!-- 6. Crop Subcategory Filter (After Product Type) -->
      <div class="filter-group">
        <label class="filter-label" for="cropSubcategoryFilter"><i class="fa fa-spa"></i> Crop Subcategory</label>
        <select class="form-select-custom filter-control" id="cropSubcategoryFilter">
          <option value="all">All Crops</option>
        </select>
      </div>

      <!-- 7. Expand (+ / -) & Reset Actions -->
      <div class="filter-group filter-group-actions">
        <label class="filter-label d-none d-xl-block">&nbsp;</label>
        <div class="d-flex align-items-center gap-2">
          
          <!-- Expand/Collapse Button with + Sign -->
          <button type="button" class="btn-toggle-more" id="toggleMoreFiltersBtn" title="Expand more filters">
            <i class="fa fa-plus text-teal" id="toggleFilterIcon" style="color: #0f766e;"></i>
            <span id="toggleFilterText">More</span>
            <span class="badge bg-teal text-white ms-1" id="activeFilterBadge" style="display:none; font-size: 11px; padding: 2px 6px; border-radius: 10px; background-color: #0f766e !important;">0</span>
          </button>

          <!-- Reset Filter Button -->
          <button type="button" class="btn-reset-filters" id="resetFiltersBtn" title="Reset all filters to default">
            <i class="fa fa-undo"></i>
          </button>

        </div>
      </div>

    </div>

    <!-- Line 2: Expandable Advanced Filters (Client, Product, Delivery Date, Quick Search) -->
    <div class="filter-row-advanced" id="moreFiltersSection" style="display: none;">
      <div class="filter-grid-secondary pt-3 mt-2 border-top">
        
        <!-- Client Multi-Select -->
        <div class="filter-group">
          <label class="filter-label"><i class="fa fa-user"></i> Client</label>
          <select id="clientFilter" class="filter-control select2" multiple="multiple" data-placeholder="All Clients"></select>
        </div>

        <!-- Product Multi-Select -->
        <div class="filter-group">
          <label class="filter-label"><i class="fa fa-leaf"></i> Product</label>
          <select id="productFilter" class="filter-control select2" multiple="multiple" data-placeholder="All Products"></select>
        </div>

        <!-- Delivery Date Multi-Select -->
        <div class="filter-group">
          <label class="filter-label"><i class="fa fa-clock"></i> Delivery Date</label>
          <select id="deliveryDateFilter" class="filter-control select2" multiple="multiple" data-placeholder="All Dates"></select>
        </div>

        <!-- Quick Search Input -->
        <div class="filter-group">
          <label class="filter-label"><i class="fa fa-search"></i> Quick Search</label>
          <div class="search-wrapper-custom">
            <i class="fa fa-search search-icon"></i>
            <input type="text" id="productionOrderSearch" class="filter-control" placeholder="Search order no, client, product, remarks...">
            <i class="fa fa-times clear-icon" id="clearSearchBtn" title="Clear Search"></i>
          </div>
        </div>

      </div>
    </div>

  </div>

  <!-- Order Strips Scroll Wrapper -->
  <div class="order-strips-wrapper">
    
    <!-- Desktop Sticky Header with Sowing Date Column -->
    <div class="strips-header">
      <div>#</div>
      <div>Order No / Date</div>
      <div>Product / Client</div>
      <div>Sowing Date</div>
      <div>Promised Delivery Date</div>
      <div>Quantities (Order/Alloc/Open)</div>
      <div class="text-end">Actions</div>
    </div>

    <!-- Order Strips & Mobile Cards Container -->
    <div class="order-strips-container" id="productionOrderList">
      <!-- Dynamic Content -->
      <div class="text-center py-5">
         <i class="fa fa-spinner fa-spin fa-2x text-teal" style="color: #0d9488;"></i>
         <p class="mt-2 text-muted fw-semibold">Loading production orders...</p>
      </div>
    </div>

  </div>

</div>

<!-- View Allocation Modal -->
<div class="modal fade" id="AllocationModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
    <div class="modal-content shadow-lg border-0" style="border-radius: 14px; overflow: hidden;">
      <div class="modal-header text-white" style="background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%);">
        <h5 class="modal-title fw-bold"><i class="fa fa-list-ul me-2"></i>Allocation Details - <span id="allocOrderNo"></span></h5>
        <button type="button" class="btn-close btn-close-white close text-white" onclick="$('#AllocationModal').modal('hide')" data-bs-dismiss="modal" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body p-3 p-md-4" id="AllocationContent">
        <!-- Dynamic content -->
      </div>
    </div>
  </div>
</div>

<!-- Edit / New Order Modal -->
<div class="modal fade" id="ProdOrderModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document" style="max-width:98%;">
    <div class="modal-content shadow-lg border-0" style="border-radius: 16px; overflow: hidden;">
      <div class="modal-header text-white" style="background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%);">
        <h5 class="modal-title fw-bold"><i class="fa fa-edit me-2"></i>Production Order</h5>
        <button type="button" class="btn-close btn-close-white close text-white" onclick="$('#ProdOrderModal').modal('hide')" data-bs-dismiss="modal" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body p-4" id="ProdOrderContent">
      </div>
    </div>
  </div>
</div>

<?php include('../footer.php'); ?>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script type="text/javascript" src="<?php echo $prefix; ?>krf-orderandsales/js/pjs_function.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  // Date Formatting Helper (dd-MM-yy)
  function formatDate_dd_MM_yy(dateStr) {
    if (!dateStr || dateStr === '0000-00-00' || dateStr === '0000-00-00 00:00:00') return '-';
    let raw = (dateStr + '').split(' ')[0];
    let parts = raw.split('-');
    if (parts.length === 3) {
      let y = parts[0], m = parts[1], d = parts[2];
      const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
      let mIdx = parseInt(m, 10) - 1;
      let mName = (mIdx >= 0 && mIdx < 12) ? monthNames[mIdx] : m;
      let yy = y.length === 4 ? y.substring(2) : y;
      return `${d}-${mName}-${yy}`;
    }
    return dateStr;
  }

  function changeDateFormatYmd_dMY(dateStr) {
    return formatDate_dd_MM_yy(dateStr);
  }

  $(document).ready(function () {
    let key = $("body").data("key");
    let routurl = $("body").data("routurl");
    let projectid = $("body").data("projectid");
    let loginRoleId = $("body").data("loginroleid");

    const monthNamesList = ["", "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

    // Expand / Collapse Advanced Filters (+ / -)
    $("#toggleMoreFiltersBtn").on("click", function() {
      let $section = $("#moreFiltersSection");
      let $icon = $("#toggleFilterIcon");
      let $text = $("#toggleFilterText");
      
      $section.slideToggle(200, function() {
        if ($section.is(":visible")) {
          $icon.removeClass("fa-plus").addClass("fa-minus");
          $text.text("Less");
          $("#toggleMoreFiltersBtn").addClass("active");
        } else {
          $icon.removeClass("fa-minus").addClass("fa-plus");
          $text.text("More");
          $("#toggleMoreFiltersBtn").removeClass("active");
        }
      });
    });

    // 1. Fetch Year & Month List with Unique Years (Order Date OR Delivery Date)
    GetProductionStockOrderYearMonthlist(function (ymres) {
      $("#psoYearlist").empty();
      let ylist = `<option value='' disabled selected>Select Year</option>`;
      if (ymres && ymres.length > 0) {
        ymres.forEach(item => {
          let monthJSON = encodeURIComponent(JSON.stringify(item.monthlist || []));
          let dispatchJSON = encodeURIComponent(JSON.stringify(item.dispatchmonthlist || []));
          ylist += `<option value='${item.year}' data-monthlist='${monthJSON}' data-dispatchmonthlist='${dispatchJSON}'>${item.year}</option>`;
        });
        $("#psoYearlist").append(ylist);
        
        // Auto-select current year or first available year
        const currentYear = new Date().getFullYear();
        if ($("#psoYearlist option[value='" + currentYear + "']").length > 0) {
          $("#psoYearlist").val(currentYear).trigger("change");
        } else {
          $("#psoYearlist").val(ymres[0].year).trigger("change");
        }
      } else {
        ylist += `<option value=''>No data available</option>`;
        $("#psoYearlist").append(ylist);
        $("#productionOrderList").html(`<div class="text-center py-5 bg-white border rounded-3"><p class="text-muted mb-0">No data available</p></div>`);
      }
    });

    // 2. Year Change -> Populate Order Month and Dispatch Month options, then load data
    $("#psoYearlist").on("change", function () {
      let selectedOption = $(this).find(":selected");
      let selectedYear = $(this).val();

      // Populate Order Month options
      $("#psoMonthlist").empty();
      let monthData = selectedOption.data("monthlist");
      let mlist = `<option value="all">All Months</option>`;
      if (monthData) {
        let months = JSON.parse(decodeURIComponent(monthData));
        if (months && months.length > 0) {
          months.forEach(m => {
            mlist += `<option value='${m.numeric}'>${m.alpha || monthNamesList[m.numeric] + '-' + selectedYear}</option>`;
          });
        }
      }
      $("#psoMonthlist").append(mlist).val("all");

      // Populate Dispatch Month options
      $("#dispatchMonthFilter").empty();
      let dispatchData = selectedOption.data("dispatchmonthlist");
      let dlist = `<option value="all">All Months</option>`;
      if (dispatchData) {
        let dMonths = JSON.parse(decodeURIComponent(dispatchData));
        if (dMonths && dMonths.length > 0) {
          dMonths.forEach(dm => {
            dlist += `<option value='${dm.numeric}'>${dm.alpha || monthNamesList[dm.numeric] + '-' + selectedYear}</option>`;
          });
        }
      }
      $("#dispatchMonthFilter").append(dlist).val("all");

      // Load full year production data
      loadProductionData();
    });

    // Primary Filters change listeners
    $(document).on("change", "#psoMonthlist", applyFilters);
    $(document).on("change", "#dispatchMonthFilter", applyFilters);
    $(document).on("change", "#categoryFilter", applyFilters);
    $(document).on("change", "#productTypeFilter", applyFilters);
    $(document).on("change", "#cropSubcategoryFilter", applyFilters);

    // 3. Load Production Data for Selected Year
    function loadProductionData() {
      let year = $("#psoYearlist").val();
      if (!year) {
        $("#productionOrderList").html(`<div class="text-center py-5 bg-white border rounded-3"><p class="text-muted mb-0">Please select a year</p></div>`);
        return;
      }

      $("#productionOrderList").html(`
        <div class="text-center py-5">
          <i class="fa fa-spinner fa-spin fa-2x text-teal" style="color: #0d9488;"></i>
          <p class="mt-2 text-muted fw-semibold">Loading production orders for ${year}...</p>
        </div>`);
      
      GetProductionStockOrderData(year, "all", function (res) {
        let html = "";
        let uniqueDates = new Set();
        let uniqueClients = new Set();
        let uniqueProducts = new Set();
        let uniqueCrops = new Set();

        if (res && res.length > 0) {
          res.forEach((item, index) => {
            if (item.expected_delivery_date && item.expected_delivery_date !== '0000-00-00') {
              uniqueDates.add(item.expected_delivery_date);
            }
            if (item.client_name) uniqueClients.add(item.client_name.trim());
            if (item.product_name) uniqueProducts.add(item.product_name.trim());

            // Crop Subcategory
            let cropVal = (item.crop_subcategory || '').trim();
            if (!cropVal && item.product_name) {
              // fallback extract
              let cleanProd = item.product_name.replace(/^(grafted|nongrafted)-/i, '');
              let parts = cleanProd.split(',');
              cropVal = parts[0].trim();
            }
            if (cropVal) uniqueCrops.add(cropVal);

            let allocated = parseFloat(item.total_allocated_qty || 0);
            let orderQty = parseFloat(item.quantity || 0);
            let balance = orderQty - allocated;
            if (balance < 0) balance = 0;

            let allocPercent = orderQty > 0 ? Math.min(100, Math.round((allocated / orderQty) * 100)) : 0;
            let barColor = allocPercent >= 100 ? '#10b981' : (allocPercent > 0 ? '#0d9488' : '#cbd5e1');

            // Category Determination (is_trader == 1 in client master)
            let isTrader = (item.is_trader == 1 || item.is_trader === '1');
            let hasClient = item.clientid && item.client_name;
            let categoryType = isTrader ? 'trader' : 'farmer';
            let categoryBadge = isTrader 
              ? `<span class="badge-category badge-trader" title="Client Master: Trader"><i class="fa fa-briefcase"></i> Trader</span>` 
              : (hasClient ? `<span class="badge-category badge-farmer" title="Client Master: Farmer"><i class="fa fa-user-tag"></i> Farmer</span>` : `<span class="badge-category badge-stock"><i class="fa fa-boxes"></i> Open Stock</span>`);

            // Product Type Determination (Grafted vs Non-Grafted)
            let rawPType = (item.producttype || '').toLowerCase();
            let prodNameLower = (item.product_name || '').toLowerCase();
            let isGrafted = rawPType === 'grafted' || (rawPType === '' && prodNameLower.indexOf('nongrafted') === -1 && prodNameLower.indexOf('grafted') > -1);
            let ptypeKey = isGrafted ? 'grafted' : 'nongrafted';
            let ptypeBadge = isGrafted
              ? `<span class="badge-ptype badge-grafted"><i class="fa fa-seedling"></i> Grafted</span>`
              : `<span class="badge-ptype badge-nongrafted"><i class="fa fa-leaf"></i> Non-Grafted</span>`;

            // Month extraction for filtering
            let orderDateRaw = item.created_at ? item.created_at.split(' ')[0] : '';
            let orderMonthNum = orderDateRaw ? parseInt(orderDateRaw.split('-')[1], 10) : 0;

            let delivDateRaw = item.expected_delivery_date && item.expected_delivery_date !== '0000-00-00' ? item.expected_delivery_date.split(' ')[0] : '';
            let delivMonthNum = delivDateRaw ? parseInt(delivDateRaw.split('-')[1], 10) : 0;

            let sowDateRaw = item.sowingdate && item.sowingdate !== '0000-00-00' ? item.sowingdate.split(' ')[0] : '';
            let scionDateRaw = item.scionsowingdate && item.scionsowingdate !== '0000-00-00' ? item.scionsowingdate.split(' ')[0] : '';

            let editBtn = "";
            let deleteBtn = "";
            if (parseInt(loginRoleId) < 2) {
              editBtn = `
                <button class="btn-circle btn-edit-order edit-productionorder" data-id="${item.stockorder_no}" title="Edit Order">
                  <i class="fa fa-edit"></i>
                </button>`;

              // Remove option: available if not allocated (backend validates sowing status)
              if (allocated === 0) {
                deleteBtn = `
                  <button class="btn-circle btn-delete-order delete-productionorder" data-id="${item.stockorder_no}" title="Remove Production Order">
                    <i class="fa fa-trash-alt"></i>
                  </button>`;
              }
            }

            let remarkHtml = "";
            let rVal = (item.remarks || item.editedremark || "").trim();
            if (rVal) {
              remarkHtml = `<div class="remark-callout"><i class="fa fa-comment-alt me-1 text-warning"></i><strong>Remark:</strong> ${rVal}</div>`;
            }

            let displayOrderDate = changeDateFormatYmd_dMY(orderDateRaw);
            let displayDelivDate = changeDateFormatYmd_dMY(delivDateRaw);
            let displaySowDate = changeDateFormatYmd_dMY(sowDateRaw);
            let displayScionDate = changeDateFormatYmd_dMY(scionDateRaw);
            let clientDisplayName = item.client_name ? item.client_name.trim() : 'Open Stock Order';

            // Sowing Date HTML for Desktop Column
            let sowingColHtml = "";
            if (!sowDateRaw && !scionDateRaw) {
              sowingColHtml = `<span class="text-muted small">-</span>`;
            } else if (isGrafted && scionDateRaw && scionDateRaw !== sowDateRaw) {
              sowingColHtml = `
                <div class="d-flex flex-column gap-1" style="font-size: 0.8rem;">
                  <div title="Root Sowing Date"><span class="badge bg-light text-dark border me-1" style="font-size: 10px; padding: 1px 4px;">Root</span><span class="fw-semibold text-dark">${displaySowDate}</span></div>
                  <div title="Scion Sowing Date"><span class="badge bg-light text-teal border me-1" style="font-size: 10px; padding: 1px 4px; color: #0f766e;">Scion</span><span class="fw-semibold text-dark">${displayScionDate}</span></div>
                </div>`;
            } else {
              sowingColHtml = `
                <div class="fw-semibold text-dark d-flex align-items-center gap-1" style="font-size: 0.85rem;">
                  <i class="fa fa-seedling text-success"></i> ${displaySowDate || displayScionDate}
                </div>
                <div class="text-muted" style="font-size: 0.7rem;">Sowing Date</div>`;
            }

            html += `
            <div class="order-strip" 
                 data-search-term="${(item.stockorder_no + ' ' + clientDisplayName + ' ' + (item.product_name || '') + ' ' + cropVal + ' ' + rVal).toLowerCase()}" 
                 data-deliverydate="${delivDateRaw}"
                 data-ordermonth="${orderMonthNum}"
                 data-dispatchmonth="${delivMonthNum}"
                 data-category="${categoryType}"
                 data-ptype="${ptypeKey}"
                 data-crop="${cropVal.toLowerCase()}"
                 data-client="${clientDisplayName}"
                 data-product="${(item.product_name || '').trim()}"
                 data-orderqty="${orderQty}"
                 data-allocqty="${allocated}"
                 data-balqty="${balance}"
                 data-orderno="${item.stockorder_no}"
                 data-orderdate="${displayOrderDate}"
                 data-sowingdate="${displaySowDate}"
                 data-scionsowingdate="${displayScionDate}"
                 data-delivdate="${displayDelivDate}">
              
              <!-- Mobile Card Header (Visible on < 992px) -->
              <div class="mobile-card-header d-lg-none">
                <div class="d-flex align-items-center gap-2">
                  <span class="text-muted small fw-bold font-monospace">#${index + 1}</span>
                  <span class="order-no-pill">${item.stockorder_no}</span>
                </div>
                <div class="mobile-badges-row">
                  ${categoryBadge}
                  ${ptypeBadge}
                  ${cropVal ? `<span class="badge-crop-tag"><i class="fa fa-spa"></i> ${cropVal}</span>` : ''}
                </div>
              </div>

              <!-- Desktop Column 1: Index (Hidden on mobile) -->
              <div class="strip-item d-none d-lg-block">
                <div class="text-muted small font-monospace fw-semibold">${index + 1}</div>
              </div>

              <!-- Desktop Column 2: Order No & Date -->
              <div class="strip-item d-none d-lg-block">
                <div class="d-flex align-items-center gap-1 mb-1">
                  <span class="order-no-pill">${item.stockorder_no}</span>
                  ${categoryBadge}
                </div>
                <div class="text-muted small d-flex align-items-center gap-1">
                  <i class="fa fa-calendar-alt text-secondary"></i> ${displayOrderDate}
                  <span class="ms-1">${ptypeBadge}</span>
                </div>
              </div>

              <!-- Column 3 / Mobile Body: Product & Client -->
              <div class="strip-item mobile-card-body">
                <div class="product-title-text d-flex align-items-center gap-2 flex-wrap">
                  <span>${item.product_name}</span>
                  ${cropVal ? `<span class="badge-crop-tag d-none d-lg-inline-flex"><i class="fa fa-spa"></i> ${cropVal}</span>` : ''}
                </div>
                <div class="client-name-text mt-1 d-flex align-items-center gap-1">
                  <i class="fa fa-user text-muted"></i> ${clientDisplayName}
                </div>
                ${remarkHtml}
                
                <!-- Mobile Dates Row (< 992px) -->
                <div class="mobile-dates-row d-lg-none">
                  <div class="mobile-date-item">
                    <div class="mobile-date-label"><i class="fa fa-calendar-plus text-muted"></i> Order Date</div>
                    <div class="fw-semibold text-dark">${displayOrderDate}</div>
                  </div>
                  <div class="mobile-date-item">
                    <div class="mobile-date-label"><i class="fa fa-seedling text-success"></i> Sowing Date</div>
                    <div class="fw-semibold text-dark">${displaySowDate}${isGrafted && scionDateRaw && scionDateRaw !== sowDateRaw ? ' / ' + displayScionDate : ''}</div>
                  </div>
                  <div class="mobile-date-item">
                    <div class="mobile-date-label"><i class="fa fa-truck-loading text-teal" style="color: #0f766e;"></i> Dispatch Date</div>
                    <div class="fw-bold text-teal" style="color: #0f766e;">${displayDelivDate}</div>
                  </div>
                </div>
              </div>

              <!-- Desktop Column 4: SOWING DATE (New Column after Product / Client) -->
              <div class="strip-item d-none d-lg-block">
                ${sowingColHtml}
              </div>

              <!-- Desktop Column 5: Promised Delivery / Dispatch Date -->
              <div class="strip-item d-none d-lg-block">
                <div class="fw-bold text-dark d-flex align-items-center gap-1" style="font-size: 0.88rem;">
                  <i class="fa fa-clock text-warning"></i> ${displayDelivDate}
                </div>
                <div class="text-muted" style="font-size: 0.7rem;">Dispatch Date</div>
              </div>

              <!-- Column 6 / Mobile Qtys: Quantities (Order/Alloc/Open) -->
              <div class="strip-item mobile-qtys-row">
                <div class="qty-badges-wrapper">
                  <div class="qty-badges-row">
                     <div class="qty-chip qty-chip-order" title="Ordered Quantity"><small class="text-muted me-1">Ord:</small><strong>${orderQty.toLocaleString()}</strong></div>
                     <div class="qty-chip qty-chip-alloc" title="Allocated Quantity"><small class="me-1">Alloc:</small><strong>${allocated.toLocaleString()}</strong></div>
                     <div class="qty-chip qty-chip-bal" title="Balance Quantity"><small class="me-1">Bal:</small><strong>${balance.toLocaleString()}</strong></div>
                  </div>
                  <div class="qty-progress-track" title="Allocation: ${allocPercent}%">
                    <div class="qty-progress-bar" style="width: ${allocPercent}%; background-color: ${barColor};"></div>
                  </div>
                </div>
              </div>

              <!-- Column 7 / Mobile Actions: Action Buttons -->
              <div class="action-buttons mobile-card-actions">
                <div class="small text-muted d-lg-none fw-semibold">
                  <i class="fa fa-tools me-1"></i> Actions
                </div>
                <div class="d-flex align-items-center gap-1">
                  <button class="btn-circle btn-view-details view-allocations" data-id="${item.stockorder_no}" title="View Allocations">
                    <i class="fa fa-list-ul"></i>
                  </button>
                  ${editBtn}
                  ${deleteBtn}
                </div>
              </div>

            </div>`;
          });
        } else {
          html = `<div class="text-center py-5 bg-white border rounded-3"><p class="text-muted mb-0 fw-semibold">No production orders found for this year</p></div>`;
        }
        
        $("#productionOrderList").html(html);

        // Populate Crop Subcategory Filter
        let currentCrop = $("#cropSubcategoryFilter").val();
        $("#cropSubcategoryFilter").empty().append('<option value="all">All Crops</option>');
        Array.from(uniqueCrops).sort((a,b) => a.localeCompare(b, undefined, {sensitivity: 'base'})).forEach(crop => {
          $("#cropSubcategoryFilter").append(`<option value="${crop.toLowerCase()}">${crop}</option>`);
        });
        if (currentCrop && $("#cropSubcategoryFilter option[value='" + currentCrop + "']").length > 0) {
          $("#cropSubcategoryFilter").val(currentCrop);
        } else {
          $("#cropSubcategoryFilter").val("all");
        }

        // Populate Multi-Select Filters
        populateMultiSelect("#clientFilter", Array.from(uniqueClients).sort());
        populateMultiSelect("#productFilter", Array.from(uniqueProducts).sort());
        populateMultiSelect("#deliveryDateFilter", Array.from(uniqueDates).sort(), true);

        // Initialize Select2 with Checkboxes
        $('.select2').select2({
            width: '100%',
            closeOnSelect: false,
            templateResult: function(data) {
                if (!data.id) return data.text;
                return $('<span>' + data.text + '</span>');
            }
        }).off('select2:select select2:unselect').on('select2:select select2:unselect', applyFilters);

        applyFilters();
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

    // Comprehensive Live Filter Engine
    function applyFilters() {
      let searchText = $("#productionOrderSearch").val().toLowerCase().trim();
      let orderMonth = $("#psoMonthlist").val();
      let dispatchMonth = $("#dispatchMonthFilter").val();
      let category = $("#categoryFilter").val();
      let productType = $("#productTypeFilter").val();
      let cropSubcat = ($("#cropSubcategoryFilter").val() || "").toLowerCase();
      let deliveryDates = $("#deliveryDateFilter").val() || [];
      let clients = $("#clientFilter").val() || [];
      let products = $("#productFilter").val() || [];

      // Update Active Badge on More Filters Button
      let advancedFilterCount = 0;
      if (deliveryDates.length > 0) advancedFilterCount++;
      if (clients.length > 0) advancedFilterCount++;
      if (products.length > 0) advancedFilterCount++;
      if (searchText.length > 0) advancedFilterCount++;

      if (advancedFilterCount > 0) {
        $("#activeFilterBadge").text(advancedFilterCount).show();
      } else {
        $("#activeFilterBadge").hide();
      }

      let visibleCount = 0;
      let totalVisibleQty = 0;
      let totalAllocQty = 0;
      let totalBalQty = 0;

      $(".order-strip").each(function() {
        let searchable = $(this).data("search-term") || "";
        let rowDate = $(this).data("deliverydate") || "";
        let rowClient = $(this).data("client") || "";
        let rowProduct = $(this).data("product") || "";
        let rowCrop = $(this).data("crop") || "";
        let rowOrderMonth = parseInt($(this).data("ordermonth"), 10) || 0;
        let rowDispatchMonth = parseInt($(this).data("dispatchmonth"), 10) || 0;
        let rowCategory = $(this).data("category") || "";
        let rowPType = $(this).data("ptype") || "";

        let matchesSearch = searchText === "" || searchable.indexOf(searchText) > -1;
        let matchesOrderMonth = (orderMonth === "all" || !orderMonth) || rowOrderMonth === parseInt(orderMonth, 10);
        let matchesDispatchMonth = (dispatchMonth === "all" || !dispatchMonth) || rowDispatchMonth === parseInt(dispatchMonth, 10);
        let matchesCategory = (category === "all" || !category) || rowCategory === category;
        let matchesPType = (productType === "all" || !productType) || rowPType === productType;
        let matchesCrop = (cropSubcat === "all" || !cropSubcat) || rowCrop === cropSubcat;
        let matchesDate = deliveryDates.length === 0 || deliveryDates.includes(rowDate);
        let matchesClient = clients.length === 0 || clients.includes(rowClient);
        let matchesProduct = products.length === 0 || products.includes(rowProduct);

        let isMatch = matchesSearch && matchesOrderMonth && matchesDispatchMonth && matchesCategory && matchesPType && matchesCrop && matchesDate && matchesClient && matchesProduct;
        $(this).toggle(isMatch);
        if (isMatch) {
          visibleCount++;
          totalVisibleQty += parseFloat($(this).data("orderqty")) || 0;
          totalAllocQty += parseFloat($(this).data("allocqty")) || 0;
          totalBalQty += parseFloat($(this).data("balqty")) || 0;
        }
      });

      $("#showingOrderCount").text(visibleCount.toLocaleString());
      $("#showingTotalQty").text(totalVisibleQty.toLocaleString());
      $("#showingAllocQty").text(totalAllocQty.toLocaleString());
      $("#showingBalQty").text(totalBalQty.toLocaleString());

      $("#noDataFilterMsg").remove();
      if (visibleCount === 0 && $(".order-strip").length > 0) {
        $("#productionOrderList").append(`<div id="noDataFilterMsg" class="text-center py-5 bg-white border rounded-3"><i class="fa fa-filter text-muted fa-2x mb-2"></i><p class="text-muted mb-0 fw-semibold">No matching production orders found for current filters</p></div>`);
      }
    }

    $("#productionOrderSearch").on("keyup search input", function() {
      let val = $(this).val().trim();
      if (val.length > 0) {
        $("#clearSearchBtn").show();
      } else {
        $("#clearSearchBtn").hide();
      }
      applyFilters();
    });

    $("#clearSearchBtn").on("click", function() {
      $("#productionOrderSearch").val("").trigger("keyup").focus();
    });

    $("#deliveryDateFilter").on("change", applyFilters);

    // Reset All Filters
    $("#resetFiltersBtn").on("click", function() {
      $("#psoMonthlist").val("all");
      $("#dispatchMonthFilter").val("all");
      $("#categoryFilter").val("all");
      $("#productTypeFilter").val("all");
      $("#cropSubcategoryFilter").val("all");
      $("#clientFilter").val(null).trigger("change.select2");
      $("#productFilter").val(null).trigger("change.select2");
      $("#deliveryDateFilter").val(null).trigger("change.select2");
      $("#productionOrderSearch").val("");
      $("#clearSearchBtn").hide();
      applyFilters();
    });

    // ==========================================
    // ACTION 1: Delete Production Order (Sowing Not Started)
    // ==========================================
    $(document).on("click", ".delete-productionorder", function () {
      let stockorderNo = $(this).data("id");
      
      Swal.fire({
        title: "Remove Production Order?",
        text: `Are you sure you want to remove production order "${stockorderNo}"? This action will permanently delete the stock order because sowing has not started.`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#ef4444",
        cancelButtonColor: "#64748b",
        confirmButtonText: "Yes, remove it!"
      }).then((result) => {
        if (result.isConfirmed) {
          $.ajax({
            url: routurl + "krf-orderandsales/modal/product_modal.php?action=deleteProductionStockOrder",
            type: "POST",
            dataType: "json",
            data: { stockorder_no: stockorderNo, projectid: projectid },
            success: function (res) {
              if (res && res.status === "success") {
                Swal.fire({
                  icon: "success",
                  title: "Removed!",
                  text: res.message,
                  timer: 1500,
                  showConfirmButton: false
                });
                loadProductionData();
              } else {
                Swal.fire("Cannot Delete", res.message || "Failed to remove production order", "error");
              }
            },
            error: function () {
              Swal.fire("Error", "Server error while removing production order", "error");
            }
          });
        }
      });
    });

    // ==========================================
    // ACTION 2: Edit Order
    // ==========================================
    $(document).on("click", ".edit-productionorder", function () {
      let stockorderNo = $(this).data("id");
      $("#ProdOrderModal .modal-title").html('<i class="fa fa-edit me-2"></i>Edit Production Order');
      $("#ProdOrderContent").load(routurl + "krf-orderandsales/editProductionOrder.php?k=" + key + "&dz=11&stockorder_no=" + stockorderNo);
      $("#ProdOrderModal").modal("show");
    });

    // ==========================================
    // ACTION 3: View Allocations
    // ==========================================
    $(document).on("click", ".view-allocations", function () {
      let stockorderNo = $(this).data("id");
      $("#allocOrderNo").text(stockorderNo);
      $("#AllocationContent").html(`<div class="text-center py-4"><i class="fa fa-spinner fa-spin fa-2x text-teal" style="color: #0d9488;"></i></div>`);
      $("#AllocationModal").modal("show");

      $.ajax({
        url: "modal/product_modal.php?action=GetAllocatedOrderDetails",
        type: "POST",
        data: { stockorder_no: stockorderNo, projectid: projectid },
        dataType: "JSON",
        success: function (res) {
          let table = `<div class="table-responsive alloc-table-responsive shadow-sm">
            <table class="table table-sm table-hover align-middle mb-0 alloc-table">
            <thead class="bg-light">
              <tr>
                <th class="text-nowrap">Order No</th>
                <th class="text-nowrap">Order Date</th>
                <th class="text-nowrap">Client Name</th>
                <th class="text-end text-nowrap">Allocated Qty</th>
                <th class="text-end text-nowrap">Wastage</th>
              </tr>
            </thead>
            <tbody>`;
          
          if (res && res.length > 0) {
            res.forEach(row => {
              table += `<tr>
                <td class="font-weight-bold text-teal fw-bold text-nowrap" style="color: #0d9488;">${row.psono}</td>
                <td class="text-nowrap">${changeDateFormatYmd_dMY(row.order_date ? row.order_date.split(' ')[0] : '')}</td>
                <td>${row.client_name || 'N/A'}</td>
                <td class="text-end font-weight-bold text-success text-nowrap">${Number(row.consumed_qty).toLocaleString()}</td>
                <td class="text-end text-danger text-nowrap">${Number(row.wastage_qty).toLocaleString()}</td>
              </tr>`;
            });
          } else {
            table += `<tr><td colspan="5" class="text-center py-4 text-muted">No allocations found for this stock order</td></tr>`;
          }
          table += `</tbody></table></div>`;
          $("#AllocationContent").html(table);
        }
      });
    });

    // ==========================================
    // ACTION 4: New Production Order
    // ==========================================
    $(document).on("click", ".newproductionorder", function () {
      $("#ProdOrderModal .modal-title").html('<i class="fa fa-plus-circle me-2"></i>New Production Order');
      $("#ProdOrderContent").load(routurl + "krf-orderandsales/newProductionOrder.php?k=" + key + "&dz=11");
      $("#ProdOrderModal").modal("show");
    });

  });

  // Export Production Orders Data to Excel / CSV
  function exportProductionOrders(type) {
    let rows = [];
    let headers = ["S.No", "Order No", "Order Date", "Category", "Product Type", "Crop", "Product Name", "Client Name", "Sowing Date", "Promised Delivery Date", "Order Qty", "Allocated Qty", "Balance Qty"];
    rows.push(headers.map(h => `"${h}"`).join(","));

    let $visibleStrips = $(".order-strip:visible");
    if ($visibleStrips.length === 0) {
      Swal.fire({
        icon: "warning",
        title: "No Data Available",
        text: "There are no production order records matching your search/filter criteria."
      });
      return;
    }

    $visibleStrips.each(function (index) {
      let row = [];
      row.push(index + 1);
      row.push('"' + ($(this).data("orderno") || '').toString().replace(/"/g, '""') + '"');
      row.push('"' + ($(this).data("orderdate") || '').toString().replace(/"/g, '""') + '"');
      row.push('"' + ($(this).data("category") === 'trader' ? 'Trader' : 'Farmer').replace(/"/g, '""') + '"');
      row.push('"' + ($(this).data("ptype") === 'grafted' ? 'Grafted' : 'Non-Grafted').replace(/"/g, '""') + '"');
      row.push('"' + ($(this).data("crop") || '').toString().replace(/"/g, '""') + '"');
      row.push('"' + ($(this).data("product") || '').toString().replace(/"/g, '""') + '"');
      row.push('"' + ($(this).data("client") || '').toString().replace(/"/g, '""') + '"');
      row.push('"' + ($(this).data("sowingdate") || '').toString().replace(/"/g, '""') + '"');
      row.push('"' + ($(this).data("delivdate") || '').toString().replace(/"/g, '""') + '"');
      row.push($(this).data("orderqty") || 0);
      row.push($(this).data("allocqty") || 0);
      row.push($(this).data("balqty") || 0);
      rows.push(row.join(","));
    });

    let csvContent = "\uFEFF" + rows.join("\r\n");
    let blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
    let url = URL.createObjectURL(blob);
    let link = document.createElement("a");
    let todayStr = new Date().toISOString().slice(0, 10);
    link.setAttribute("href", url);
    link.setAttribute("download", `Production_Orders_Report_${todayStr}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    Swal.fire({
      icon: "success",
      title: "Export Downloaded",
      text: `Exported ${$visibleStrips.length} production order record(s) successfully!`,
      timer: 2000,
      showConfirmButton: false
    });
  }
</script>
