<?php
require('header.php');

?>
<link rel="stylesheet" href="assets/css/custom_labour.css">
<!-- Google Fonts for premium typography -->
<style>
  /* Premium Styling Overrides */
  .bg-soft-primary {
    background-color: #eef2ff;
  }

  .bg-soft-success {
    background-color: #f0fdf4;
  }

  .bg-soft-warning {
    background-color: #fffbeb;
  }

  .bg-soft-danger {
    background-color: #fef2f2;
  }

  /* Light Gender Badges with Dark Text */
  .badge-gender-m {
    background-color: #e0f2fe !important;
    color: #111827 !important;
    border: 1px solid #bae6fd !important;
    font-size: 9px;
    padding: 1px 5px;
    font-weight: 600;
    border-radius: 4px;
  }
  .badge-gender-w {
    background-color: #fce7f3 !important;
    color: #111827 !important;
    border: 1px solid #fbcfe8 !important;
    font-size: 9px;
    padding: 1px 5px;
    font-weight: 600;
    border-radius: 4px;
  }

  /* Active filter highlight styling */
  .filter-active-highlight {
    background-color: #e0f2fe !important;
    border: 1.5px solid #0284c7 !important;
    color: #0369a1 !important;
    font-weight: 600 !important;
    box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.15) !important;
  }
  .search-box-premium.filter-active-highlight {
    background-color: #e0f2fe !important;
    border: 1.5px solid #0284c7 !important;
    box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.15) !important;
  }
  .search-box-premium.filter-active-highlight input,
  .search-box-premium.filter-active-highlight .input-group-text {
    background-color: #e0f2fe !important;
    color: #0369a1 !important;
    font-weight: 600 !important;
  }
  .btn-clear-filters {
    transition: all 0.2s ease;
    white-space: nowrap;
  }
  .btn-clear-filters:hover {
    background-color: #ef4444 !important;
    color: #ffffff !important;
    border-color: #ef4444 !important;
  }

  /* Interactive Tab Cards styling */
  .stat-card {
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 2px solid transparent !important;
    position: relative;
    overflow: hidden;
  }

  .stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.08) !important;
  }

  /* All Labours Active State */
  .stat-card.active-all {
    border-color: #0d6efd !important;
    background-color: #eef2ff !important;
    box-shadow: 0 8px 25px rgba(13, 110, 253, 0.15) !important;
    animation: glow-all 2s infinite ease-in-out;
  }
  @keyframes glow-all {
    0%, 100% { box-shadow: 0 8px 20px rgba(13, 110, 253, 0.1); }
    50% { box-shadow: 0 8px 30px rgba(13, 110, 253, 0.25); }
  }

  /* Active Labours Active State */
  .stat-card.active-active {
    border-color: #198754 !important;
    background-color: #f0fdf4 !important;
    box-shadow: 0 8px 25px rgba(25, 135, 84, 0.15) !important;
    animation: glow-active 2s infinite ease-in-out;
  }
  @keyframes glow-active {
    0%, 100% { box-shadow: 0 8px 20px rgba(25, 135, 84, 0.1); }
    50% { box-shadow: 0 8px 30px rgba(25, 135, 84, 0.25); }
  }

  /* In-Active Labours Active State */
  .stat-card.active-inactive {
    border-color: #dc3545 !important;
    background-color: #fef2f2 !important;
    box-shadow: 0 8px 25px rgba(220, 53, 69, 0.15) !important;
    animation: glow-inactive 2s infinite ease-in-out;
  }
  @keyframes glow-inactive {
    0%, 100% { box-shadow: 0 8px 20px rgba(220, 53, 69, 0.1); }
    50% { box-shadow: 0 8px 30px rgba(220, 53, 69, 0.25); }
  }


  /* Incomplete Details Active State */
  .stat-card.active-incomplete {
    border-color: #e44d26 !important;
    background-color: #fff5f0 !important;
    box-shadow: 0 8px 25px rgba(228, 77, 38, 0.15) !important;
    animation: glow-incomplete 2s infinite ease-in-out;
  }
  @keyframes glow-incomplete {
    0%, 100% { box-shadow: 0 8px 20px rgba(228,77,38, 0.1); }
    50% { box-shadow: 0 8px 30px rgba(228,77,38, 0.28); }
  }

  .bg-soft-orange { background-color: #fff0eb; }

  .missing-tag {
    display: inline-block;
    font-size: 10px;
    padding: 2px 7px;
    border-radius: 20px;
    background: #fee2e2;
    color: #b91c1c;
    font-weight: 600;
    margin: 2px;
    white-space: nowrap;
  }
  .incomplete-row:hover { background: #fff5f0 !important; }

  .fs-12 {
    font-size: 12px;
  }

  .stat-icon {
    transition: transform 0.3s ease;
  }

  .card:hover .stat-icon {
    transform: scale(1.1);
  }

  .search-box-premium {
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.05);
  }

  .search-box-premium:focus-within {
    box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.25);
  }

  #labourMastertbl thead th {
    background: #fcfcfc;
    cursor: pointer;
    transition: background 0.2s ease;
  }

  #labourMastertbl thead th:hover {
    background: #f4f4f4;
  }

  #labourMastertbl tbody tr {
    transition: all 0.2s ease;
    border-bottom: 1px solid #f8f9fa;
  }

  #labourMastertbl tbody tr:hover {
    background-color: #f8faff;
    box-shadow: inset 4px 0 0 #0d6efd;
  }

  .btn-white {
    background: #fff;
    color: #444;
  }

  .btn-white:hover {
    background: #f8f9fa;
  }

  .dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #dee2e6;
  }

  .dot.active {
    background: #0d6efd;
    width: 20px;
    border-radius: 10px;
  }

  /* Wizard Styling */
  .step-indicator {
    position: relative;
  }
  .step {
    text-align: center;
    flex: 1;
    z-index: 2;
  }
  .step-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #e9ecef;
    color: #6c757d;
    font-weight: 700;
    font-size: 14px;
    margin-bottom: 4px;
    border: 2px solid #fff;
    transition: all 0.3s ease;
  }
  .step.active .step-num {
    background: #0d6efd;
    color: #fff;
    box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.15);
  }
  .step.completed .step-num {
    background: #198754;
    color: #fff;
  }
  .step-label {
    display: block;
    font-size: 11px;
    font-weight: 600;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  .step.active .step-label {
    color: #0d6efd;
  }

  /* Custom Radio Option Styling */
  .custom-option {
    position: relative;
    padding-left: 0;
    margin-bottom: 0;
  }
  .custom-option .form-check-input {
    display: none;
  }
  .custom-option .form-check-label {
    display: flex;
    align-items: center;
    padding: 10px 20px 10px 15px;
    background: #fff;
    border: 2px solid transparent;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
  }
  .custom-option .form-check-label::before {
    content: '';
    display: inline-block;
    width: 18px;
    height: 18px;
    border: 2px solid #dee2e6;
    border-radius: 50%;
    margin-right: 10px;
    transition: all 0.3s ease;
  }
  .custom-option .form-check-input:checked + .form-check-label {
    border-color: #0d6efd;
    background: #f0f7ff;
    color: #0d6efd;
  }
  .custom-option .form-check-input:checked + .form-check-label::before {
    border-color: #0d6efd;
    background: #0d6efd;
    box-shadow: inset 0 0 0 3px #fff;
  }
  .custom-option .form-check-label:hover {
    background: #f8f9fa;
    transform: translateY(-1px);
  }
</style>
<link
  href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap"
  rel="stylesheet">

<div class="main-container p-lg-4 p-3"
  style="font-family: 'Inter', sans-serif; background: #f8f9fa; min-height: 100vh;">

  <!-- Dashboard Header Section -->
  <div class="row align-items-center mb-4">
    <div class="col-md-6">
      <h2 class="fw-bold text-dark mb-1" style="font-family: 'Outfit', sans-serif; letter-spacing: -0.5px;">Labour
        Master Management</h2>
      <p class="text-muted small mb-0"><i class="ti ti-building-community me-1"></i> <?php echo $projectname; ?> /
        Workforce Directory</p>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0 d-flex justify-content-md-end gap-2">
      <button id="quicklabourregistration" class="btn btn-primary shadow-sm d-inline-flex align-items-center gap-2 px-4 py-2" data-bs-toggle="modal"
        data-bs-target="#QuickLabourRegistrationModal">
        <i class="ti ti-user-plus fs-18"></i> <span>Quick Registration</span>
      </button>
      <button id="newlabourregistration" class="btn btn-dark shadow-sm d-inline-flex align-items-center gap-2 px-4 py-2" data-bs-toggle="modal"
        data-bs-target="#LabourRegistrationModal">
        <i class="ti ti-user-plus fs-18"></i> <span>Register New Labour</span>
      </button></div>
  </div>

  <!-- Summary Stats Section -->
  <div class="row g-2 mb-4">
    <div class="col-lg-3 col-md-6 col-sm-6">
      <div class="card border-0 shadow-sm overflow-hidden h-100 stat-card" id="card-all-labours" style="border-radius: 14px;">
        <div class="card-body p-2">
          <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
              <div class="stat-icon bg-soft-primary text-primary p-2 rounded-3" style="min-width:38px;text-align:center;">
                <i class="ti ti-users fs-20"></i>
              </div>
              <div>
                <p class="text-muted mb-0" style="font-size:11px;font-weight:600;">All Labours</p>
                <h4 class="fw-bold mb-0" style="font-size:20px;" id="stat-all-labours">0</h4>
              </div>
            </div>
            <div class="d-flex flex-column gap-1 align-items-end pe-2">
              <span class="badge badge-gender-m">M: <span id="stat-all-man">0</span></span>
              <span class="badge badge-gender-w">W: <span id="stat-all-woman">0</span></span>
            </div>
          </div>
        </div>
        <div class="bg-primary opacity-25 position-absolute end-0 bottom-0"
          style="width:44px;height:44px;border-radius:44px 0 0 0;"></div>
      </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-6">
      <div class="card border-0 shadow-sm overflow-hidden h-100 stat-card active-active" id="card-active-labours" style="border-radius: 14px;">
        <div class="card-body p-2">
          <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
              <div class="stat-icon bg-soft-success text-success p-2 rounded-3" style="min-width:38px;text-align:center;">
                <i class="ti ti-user-check fs-20"></i>
              </div>
              <div>
                <p class="text-muted mb-0" style="font-size:11px;font-weight:600;">Active</p>
                <h4 class="fw-bold mb-0" style="font-size:20px;" id="stat-active-labours">0</h4>
              </div>
            </div>
            <div class="d-flex flex-column gap-1 align-items-end pe-2">
              <span class="badge badge-gender-m">M: <span id="stat-active-man">0</span></span>
              <span class="badge badge-gender-w">W: <span id="stat-active-woman">0</span></span>
            </div>
          </div>
        </div>
        <div class="bg-success opacity-25 position-absolute end-0 bottom-0"
          style="width:44px;height:44px;border-radius:44px 0 0 0;"></div>
      </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-6">
      <div class="card border-0 shadow-sm overflow-hidden h-100 stat-card" id="card-inactive-labours" style="border-radius: 14px;">
        <div class="card-body p-2">
          <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
              <div class="stat-icon bg-soft-danger text-danger p-2 rounded-3" style="min-width:38px;text-align:center;">
                <i class="ti ti-user-minus fs-20"></i>
              </div>
              <div>
                <p class="text-muted mb-0" style="font-size:11px;font-weight:600;">In-Active</p>
                <h4 class="fw-bold mb-0" style="font-size:20px;" id="stat-inactive-labours">0</h4>
              </div>
            </div>
            <div class="d-flex flex-column gap-1 align-items-end pe-2">
              <span class="badge badge-gender-m">M: <span id="stat-inactive-man">0</span></span>
              <span class="badge badge-gender-w">W: <span id="stat-inactive-woman">0</span></span>
            </div>
          </div>
        </div>
        <div class="bg-danger opacity-25 position-absolute end-0 bottom-0"
          style="width:44px;height:44px;border-radius:44px 0 0 0;"></div>
      </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-6">
      <div class="card border-0 shadow-sm overflow-hidden h-100 stat-card" id="card-incomplete" style="border-radius: 14px; cursor:pointer;">
        <div class="card-body p-2">
          <div class="d-flex align-items-center gap-2">
            <div class="stat-icon bg-soft-orange p-2 rounded-3" style="min-width:38px;text-align:center;color:#e44d26;">
              <i class="ti ti-alert-triangle fs-20"></i>
            </div>
            <div class="flex-grow-1">
              <p class="text-muted mb-0" style="font-size:11px;font-weight:600;">Incomplete Details</p>
              <div class="d-flex align-items-end gap-2">
                <h4 class="fw-bold mb-0" style="font-size:20px;color:#e44d26;" id="stat-incomplete"><span class="spinner-border spinner-border-sm"></span></h4>
                <small class="text-muted mb-1" style="font-size:10px;">with missing info</small>
              </div>
            </div>
            <div class="ms-auto">
              <span class="badge" style="background:#fff0eb;color:#e44d26;border:1px solid #fcdfd7;font-size:10px;">View</span>
            </div>
          </div>
        </div>
        <div class="position-absolute end-0 bottom-0" style="width:44px;height:44px;border-radius:44px 0 0 0;background:#e44d26;opacity:0.18;"></div>
      </div>
    </div>
  </div>

  <!-- Table Card Section -->
  <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 16px;">
    <div class="card-header bg-white py-3 border-bottom border-light">
      <!-- Title & Export Row -->
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <h5 class="fw-bold mb-0 text-dark">Workforce Directory</h5>
          <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-11 px-2.5 py-1">
            Total: <strong id="filter-total-count">0</strong>
          </span>
          <span class="badge badge-gender-m">
            M: <strong id="filter-man-count">0</strong>
          </span>
          <span class="badge badge-gender-w">
            W: <strong id="filter-woman-count">0</strong>
          </span>
        </div>
        <div>
          <button type="button" class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1 shadow-sm px-3 py-1.5 fw-semibold" id="btnExportLabourExcel" onclick="exportLabourMasterToExcel()">
            <i class="ti ti-file-spreadsheet fs-16"></i> Export Excel
          </button>
        </div>
      </div>

      <!-- Filters Row in its own line -->
      <div class="row g-2 align-items-center">
        <div class="col-lg-3 col-md-4 col-sm-6">
          <select class="form-select bg-light border-0" id="filter-loc" onchange="handleFilterChange('loc')">
            <option value="all">All Locations</option>
          </select>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
          <select class="form-select bg-light border-0" id="filter-wage" onchange="handleFilterChange('wage')">
            <option value="all">All Wages</option>
          </select>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
          <select class="form-select bg-light border-0" id="filter-gender" onchange="handleFilterChange('gender')">
            <option value="all">All Genders</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
            <option value="Other">Other</option>
          </select>
        </div>
        <div class="col-lg-4 col-md-8 col-sm-8">
          <div class="input-group search-box-premium" id="search-box-wrap">
            <span class="input-group-text bg-light border-0"><i class="ti ti-search text-muted"></i></span>
            <input type="search" class="form-control bg-light border-0 ps-0"
              placeholder="Search name, ID or mobile..." id="searchmasterPaytbl"
              oninput="handleFilterChange('search')">
          </div>
        </div>
        <div class="col-lg-1 col-md-4 col-sm-4">
          <button type="button" class="btn btn-outline-danger btn-clear-filters w-100 d-inline-flex align-items-center justify-content-center gap-1 shadow-sm py-2" id="btnClearFilters" onclick="clearAllFilters()" title="Reset all filters">
            <i class="ti ti-filter-off"></i> <span class="d-none d-sm-inline">Clear</span>
          </button>
        </div>
      </div>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive" style="max-height: 65vh; overflow-y: auto;">
        <table class="table table-hover align-middle mb-0" id="labourMastertbl">
          <thead class="bg-light sticky-top" style="z-index: 10;">
            <tr>
              <th class="ps-4 border-0 py-3 text-uppercase fs-12 fw-bold text-muted">S.No</th>
              <th class="border-0 py-3 text-uppercase fs-12 fw-bold text-muted" onclick="sortTable(2, this)">Labour</th>
              <th class="border-0 py-3 text-uppercase fs-12 fw-bold text-muted" onclick="sortTable(3, this)">Mobile No</th>
              <th class="border-0 py-3 text-uppercase fs-12 fw-bold text-muted" onclick="sortTable(4, this)">Address</th>
              <th class="border-0 py-3 text-uppercase fs-12 fw-bold text-muted" onclick="sortTable(5, this)">Daily Wage</th>
              <th class="border-0 py-3 text-uppercase fs-12 fw-bold text-muted" onclick="sortTable(6, this)">Location</th>
              <th class="border-0 py-3 text-uppercase fs-12 fw-bold text-muted text-center" onclick="sortTable(7, this)">Grafting</th>
              <th class="pe-4 border-0 py-3 text-uppercase fs-12 fw-bold text-muted text-end">Action</th>
            </tr>
          </thead>
          <tbody class="border-0">
            <!-- Data will be injected here via JS -->
            <tr>
              <td colspan="8" class="text-center py-5">
                <div class="spinner-border text-primary spinner-border-sm me-2"></div>
                <span class="text-muted">Initializing workforce data...</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <div class="card-footer bg-white py-3 border-top border-light d-flex justify-content-between align-items-center">
      <p class="text-muted small mb-0">Showing <span id="current-showing-count">0</span> records</p>
      <div class="pagination-dots d-flex gap-1">
        <span class="dot active"></span>
        <span class="dot"></span>
        <span class="dot"></span>
      </div>
    </div>
  </div>
</div>

<!-- Incomplete Details Modal -->
<div class="modal fade" id="IncompleteDetailsModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content border-0 shadow-lg" style="border-radius:18px;">
      <div class="modal-header border-0 p-4 pb-2" style="background:linear-gradient(135deg,#fff5f0 0%,#fff 100%);">
        <div class="d-flex align-items-center gap-3">
          <div class="p-2 rounded-3" style="background:#fff0eb;color:#e44d26;">
            <i class="ti ti-alert-triangle fs-22"></i>
          </div>
          <div>
            <h5 class="fw-bold text-dark mb-0">Incomplete Labour Details</h5>
            <p class="text-muted small mb-0">Labours with missing or incorrect required information</p>
          </div>
        </div>
        <div class="ms-auto d-flex align-items-center gap-2">
          <button type="button" class="btn btn-sm btn-success rounded-pill px-3 shadow-sm" id="btnExportIncompleteExcel" onclick="exportIncompleteToExcel()">
            <i class="ti ti-file-spreadsheet me-1"></i> Export Excel
          </button>
          <span class="badge rounded-pill px-3 py-2" style="background:#fee2e2;color:#b91c1c;font-size:12px;">
            <i class="ti ti-users me-1"></i><span id="incomplete-modal-count">0</span> Labours
          </span>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
      </div>
      <div class="modal-body p-0">
        <!-- Search & Filter Bar -->
        <div class="px-4 py-3 border-bottom bg-light d-flex gap-3 align-items-center flex-wrap">
          <div class="input-group" style="max-width:280px;">
            <span class="input-group-text bg-white border-end-0"><i class="ti ti-search text-muted"></i></span>
            <input type="text" class="form-control border-start-0 bg-white" id="incomplete-search" placeholder="Search by name or ID..." oninput="filterIncompleteTable()">
          </div>
          <div style="flex:1;"></div>
          <small class="text-muted">Click <i class="ti ti-edit text-primary"></i> to edit and complete the labour details</small>
        </div>
        <!-- Legend / Filter Bar -->
        <div class="px-4 py-2 border-bottom d-flex flex-wrap gap-2 align-items-center" style="background:#fffaf9;" id="incomplete-filter-container">
          <small class="text-muted fw-bold me-1"><i class="ti ti-filter text-primary me-1"></i>Filter by Missing Fields:</small>
          <div class="form-check form-check-inline m-0 me-2">
            <input class="form-check-input filter-incomplete-check" type="checkbox" id="chk_all_fields" checked onchange="toggleAllIncompleteFields(this)" style="cursor: pointer;">
            <label class="form-check-label fw-bold text-dark small cursor-pointer" for="chk_all_fields" style="cursor: pointer;">Select All</label>
          </div>
          <div id="incomplete-field-checkboxes" class="d-flex flex-wrap gap-2 align-items-center flex-grow-1">
            <span class="text-muted small">Loading field filters...</span>
          </div>
          <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm ms-auto" id="btnApplyIncompleteFilter" onclick="filterIncompleteTable()">
            <i class="ti ti-filter me-1"></i> Apply Filter
          </button>
        </div>
        <!-- Table -->
        <div class="table-responsive" style="max-height:55vh;overflow-y:auto;">
          <table class="table table-hover align-middle mb-0" id="incompleteDetailsTbl">
            <thead class="sticky-top" style="z-index:5;background:#fcfcfc;">
              <tr>
                <th class="ps-4 py-3 border-0 text-uppercase fs-12 fw-bold text-muted" style="width:50px;">S.No</th>
                <th class="py-3 border-0 text-uppercase fs-12 fw-bold text-muted">Labour</th>
                <th class="py-3 border-0 text-uppercase fs-12 fw-bold text-muted">Location</th>
                <th class="py-3 border-0 text-uppercase fs-12 fw-bold text-muted">Missing / Wrong Fields</th>
                <th class="py-3 pe-4 border-0 text-uppercase fs-12 fw-bold text-muted text-end" style="width:80px;">Action</th>
              </tr>
            </thead>
            <tbody id="incompleteDetailsTblBody">
              <tr><td colspan="5" class="text-center py-5">
                <div class="spinner-border text-danger spinner-border-sm me-2"></div>
                <span class="text-muted">Loading incomplete records...</span>
              </td></tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer border-0 px-4 py-3">
        <small class="text-muted me-auto"><i class="ti ti-info-circle me-1"></i>Edit each labour to fill in the missing information.</small>
        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Labour Registration Modal -->
<div class="modal fade" id="LabourRegistrationModal">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
      <div class="modal-header border-0 p-4 pb-0">
        <div>
          <h4 class="fw-bold text-dark mb-0">Labour Registration</h4>
          <p class="text-muted small mb-0">Onboard new workforce with detailed credentials</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="LabourRegistrationForm" enctype="multipart/form-data">
        <input type="hidden" name="action" value="LabourRegistration">
        <input type="hidden" name="employeeID" id="reg_employeeID" value="">
        
        <!-- Step Progress Indicator -->
        <div class="modal-steps-container px-4 py-3 bg-light border-bottom">
          <div class="d-flex justify-content-between step-indicator">
            <div class="step active" data-step="1">
              <span class="step-num">1</span>
              <span class="step-label">Basic</span>
            </div>
            <div class="step" data-step="2">
              <span class="step-num">2</span>
              <span class="step-label">Bank</span>
            </div>
            <div class="step" data-step="3">
              <span class="step-num">3</span>
              <span class="step-label">Contact</span>
            </div>
            <div class="step" data-step="4">
              <span class="step-num">4</span>
              <span class="step-label">Address</span>
            </div>
            <div class="step" data-step="5">
              <span class="step-num">5</span>
              <span class="step-label">Skills</span>
            </div>
            <div class="step" data-step="6">
              <span class="step-num">6</span>
              <span class="step-label">Refs</span>
            </div>
          </div>
          <div class="progress mt-2" style="height: 4px;">
            <div class="progress-bar" id="stepProgressBar" role="progressbar" style="width: 16.6%;"></div>
          </div>
        </div>

        <div class="modal-body p-4">
          <div class="container-fluid p-0">
            <div class="row g-4">
              <!-- Step 1: Basic Info -->
              <div class="form-step active" id="step-1">
                <div class="row g-4">
                  <!-- Left Column: Photo & Personal -->
                  <div class="col-md-4" style="min-width: 200px;">
                    <div class="text-center p-4 bg-light rounded-4 shadow-sm h-100">
                      <label class="form-label d-block small fw-bold text-muted mb-3 text-uppercase tracking-wider">Labour
                        Photo</label>
                      <div class="position-relative d-inline-block mb-3">
                        <div class="rounded-circle border border-2 border-white shadow-sm overflow-hidden"
                          style="width: 130px; height: 130px; background: #eee; margin: 0 auto;">
                          <img id="labourPhotoPreview"
                            src="<?= generateSelfAvatarPHP('Labour', '', '#f8f9fa', '#adb5bd', 256) ?>"
                            class="w-100 h-100 object-fit-cover">
                        </div>
                        <label for="labourPhoto"
                          class="btn btn-sm btn-primary rounded-circle position-absolute bottom-0 end-0 p-0 shadow-sm d-flex align-items-center justify-content-center"
                          style="width: 32px; height: 32px;">
                          <i class="ti ti-camera fs-14"></i>
                          <input type="file" id="labourPhoto" name="labourPhoto" class="d-none" accept="image/*"
                            onchange="previewImage(this, 'labourPhotoPreview')">
                        </label>
                      </div>
                      <p class="text-muted fs-11 mb-0">JPG, PNG up to 2MB</p>
                    </div>
                  </div>

                  <!-- Right Column: Basic Info -->
                  <div class="col-md-8">
                    <div class="card border-0 shadow-sm h-100">
                      <div class="card-body p-4">
                        <h6 class="fs-13 fw-bold text-dark mb-4 d-flex align-items-center">
                          <span class="bg-primary-subtle text-primary rounded-2 p-1 me-2"><i
                              class="ti ti-user fs-16"></i></span>
                          Basic Information
                        </h6>
                        <div class="row g-3">
                          <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Working Location <span
                                class="text-danger">*</span></label>
                            <select name="workingLocation" id="reg_workingLocation" class="form-select bg-light border-0"
                              required>
                              <option value="">Select Site Location</option>
                            </select>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Suggested Labour ID <span
                                class="text-danger">*</span></label>
                             <input type="text" inputmode="numeric" pattern="[0-9]*" name="seriesid" id="reg_seriesid" class="form-control bg-light border-0" required
                               placeholder="Suggested Labour ID">
                          </div>
                          <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">First Name <span
                                class="text-danger">*</span></label>
                            <input type="text" name="firstName" class="form-control bg-light border-0" required
                              placeholder="Enter first name">
                          </div>
                          <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Middle Name</label>
                            <input type="text" name="middleName" class="form-control bg-light border-0"
                              placeholder="Optional">
                          </div>
                          <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Last Name</label>
                            <input type="text" name="lastName" class="form-control bg-light border-0"
                              placeholder="Enter last name">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Gender <span
                                class="text-danger">*</span></label>
                            <select name="gender" id="reg_gender" class="form-select bg-light border-0" required>
                              <option value="">Select Gender</option>
                              <option value="Male">Male</option>
                              <option value="Female">Female</option>
                              <option value="Other">Other</option>
                            </select>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Marital Status <span
                                class="text-danger">*</span></label>
                            <select name="maritalStatus" id="reg_maritalStatus" class="form-select bg-light border-0" required>
                              <option value="">Select Status</option>
                              <option value="Single">Single</option>
                              <option value="Married">Married</option>
                              <option value="Divorced">Divorced</option>
                              <option value="Widowed">Widowed</option>
                            </select>
                          </div>
                          <div class="col-md-6" id="reg_fatherNameWrapper">
                            <label class="form-label small fw-bold text-muted">Father Name</label>
                            <input type="text" name="fatherName" id="reg_fatherName" class="form-control bg-light border-0"
                              placeholder="Enter father name">
                          </div>
                          <div class="col-md-6" id="reg_motherNameWrapper">
                            <label class="form-label small fw-bold text-muted">Mother Name</label>
                            <input type="text" name="motherName" id="reg_motherName" class="form-control bg-light border-0"
                              placeholder="Enter mother name">
                          </div>
                          <div class="col-md-6" id="reg_spouseNameWrapper" style="display:none;">
                            <label class="form-label small fw-bold text-muted" id="reg_spouseNameLabel">Spouse Name</label>
                            <input type="text" name="spouseName" id="reg_spouseName" class="form-control bg-light border-0"
                              placeholder="Enter spouse name">
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Step 2: Bank Section -->
              <div class="form-step d-none" id="step-2">
                <div class="card border-0 shadow-sm">
                  <div class="card-header bg-transparent border-0 p-4 pb-0">
                    <h6 class="fs-13 fw-bold text-dark mb-0 d-flex align-items-center">
                      <span class="bg-success-subtle text-success rounded-2 p-1 me-2"><i class="ti ti-building-bank fs-16"></i></span>
                      Bank Account Details
                    </h6>
                  </div>
                  <div class="card-body p-4">
                    <div class="row g-4">
                      <div class="col-md-6 position-relative bank-autocomplete-wrapper">
                        <label class="form-label small fw-bold text-muted">Bank Name <span class="text-danger">*</span></label>
                        <input type="text" name="bankName" id="reg_bankName" class="form-control bg-light border-0 shadow-none py-2 text-uppercase" required placeholder="e.g. STATE BANK OF INDIA" autocomplete="off">
                        <div id="reg_bank_suggestions" class="bank-suggestions-dropdown shadow-lg rounded-3 border"></div>
                        <div class="validation-feedback small mt-1" id="reg_bankNameFeedback"></div>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Account Number <span class="text-danger">*</span></label>
                        <input type="text" name="accountNumber" id="reg_accountNumber" class="form-control bg-light border-0 shadow-none py-2" required placeholder="Enter bank account number" maxlength="18">
                        <div class="validation-feedback small mt-1" id="reg_accountNumberFeedback"></div>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">IFSC Code <span class="text-danger">*</span></label>
                        <input type="text" name="ifscCode" id="reg_ifscCode" class="form-control bg-light border-0 shadow-none py-2 text-uppercase" required placeholder="e.g. SBIN0001234" maxlength="11">
                        <div class="validation-feedback small mt-1" id="reg_ifscCodeFeedback"></div>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Upload Bank Document (Passbook/Cancel Cheque)</label>
                        <input type="file" name="bankDoc" id="reg_bankDoc" class="form-control bg-light border-0 shadow-none py-2">
                        <div id="bankDocPreviewContainer" class="d-none mt-2">
                          <a href="#" id="bankDocLink" target="_blank" class="btn btn-sm btn-light border text-primary d-inline-flex align-items-center gap-1">
                            <i class="ti ti-file-text"></i> View Existing Document
                          </a>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Step 3: Contact & Verification -->
              <div class="form-step d-none" id="step-3">
                <div class="card border-0 shadow-sm">
                  <div class="card-body p-4">
                    <h6 class="fs-13 fw-bold text-dark mb-4 d-flex align-items-center">
                      <span class="bg-success-subtle text-success rounded-2 p-1 me-2"><i
                          class="ti ti-phone fs-16"></i></span>
                      Contact & Verification
                    </h6>
                    <div class="row g-3">
                      <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted">Mobile Number <span
                            class="text-danger">*</span></label>
                        <input type="text" name="mobileNo" id="reg_mobileNo" class="form-control bg-light border-0" required
                          placeholder="9876543210" maxlength="10">
                        <div class="validation-feedback small mt-1" id="reg_mobileNoFeedback"></div>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted">Emergency Contact <span
                            class="text-danger">*</span></label>
                        <input type="text" name="emergencyContact" id="reg_emergencyContact" class="form-control bg-light border-0" required
                          placeholder="Emergency phone" maxlength="10">
                        <div class="validation-feedback small mt-1" id="reg_emergencyContactFeedback"></div>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted">ID Proof Type <span
                            class="text-danger">*</span></label>
                        <select name="idProofType" id="reg_idProofType" class="form-select bg-light border-0" required>
                          <option value="">Select ID Type</option>
                          <option value="Aadhar Card">Aadhar Card</option>
                          <option value="PAN Card">PAN Card</option>
                          <option value="Voter ID">Voter ID</option>
                          <option value="Driving License">Driving License</option>
                          <option value="Passport">Passport</option>
                          <option value="Other">Other</option>
                        </select>
                      </div>
                      <div class="col-md-8 d-none" id="reg_idProofNoWrapper">
                        <label class="form-label small fw-bold text-muted" id="reg_idProofNoLabel">ID Proof Number <span class="text-danger">*</span></label>
                        <input type="text" name="idProofNo" id="reg_idProofNo" class="form-control bg-light border-0"
                          placeholder="" maxlength="20" autocomplete="off">
                        <div class="invalid-feedback" id="reg_idProofNoFeedback"></div>
                      </div>
                      <div class="col-md-12">
                        <label class="form-label small fw-bold text-muted">Upload ID Proof <span
                            class="text-muted fs-11">(PDF/JPG)</span></label>
                        <input type="file" name="idProofDoc" class="form-control bg-light border-0">
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Step 4: Address Details -->
              <div class="form-step d-none" id="step-4">
                <div class="card border-0 shadow-sm">
                  <div class="card-body p-4">
                    <h6 class="fs-13 fw-bold text-dark mb-4 d-flex align-items-center">
                      <span class="bg-warning-subtle text-warning rounded-2 p-1 me-2"><i
                          class="ti ti-map-pin fs-16"></i></span>
                      Address Details
                    </h6>
                    <div class="row g-3">
                      <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">State Name <span class="text-danger">*</span></label>
                        <select name="stateid" id="reg_stateid" class="form-select bg-light border-0" required>
                          <option value="">Select State</option>
                        </select>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">District Name <span class="text-danger">*</span></label>
                        <select name="districtid" id="reg_districtid" class="form-select bg-light border-0" required disabled>
                          <option value="">Select State First</option>
                        </select>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Tahseel / Block Name</label>
                        <input type="text" name="tahseel" id="reg_tahseel" class="form-control bg-light border-0"
                          placeholder="Enter tahseel/block name">
                      </div>
                      <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">City / Village Name <span class="text-danger">*</span></label>
                        <input type="text" name="city" id="reg_city" class="form-control bg-light border-0" required
                          placeholder="Enter city/village name">
                      </div>
                      <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Current Address <span
                            class="text-danger">*</span></label>
                        <textarea name="current_address" id="reg_current_address" class="form-control bg-light border-0" rows="3" required
                          placeholder="Enter current address..."></textarea>
                      </div>
                      <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                          <label class="form-label small fw-bold text-muted mb-0">Permanent Address</label>
                          <div class="form-check form-check-inline mb-0">
                            <input class="form-check-input" type="checkbox" id="reg_same_as_current">
                            <label class="form-check-label small text-muted" for="reg_same_as_current">Same as Current</label>
                          </div>
                        </div>
                        <textarea name="permanent_address" id="reg_permanent_address" class="form-control bg-light border-0" rows="3"
                          placeholder="Enter permanent address..."></textarea>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Step 5: Skills & Location -->
              <div class="form-step d-none" id="step-5">
                <div class="card border-0 shadow-sm bg-primary-subtle border-start border-4 border-primary">
                  <div class="card-body p-4">
                    <div class="row align-items-center g-3">
                      <div class="col-md-12">
                        <label class="form-label small fw-bold text-primary d-block">Is Grafted Skill? <span
                            class="text-danger">*</span></label>
                        <div class="d-flex gap-4">
                          <div class="form-check custom-option">
                            <input class="form-check-input" type="radio" name="grafting_skill" id="skill_yes"
                              value="yes" required>
                            <label class="form-check-label fw-bold" for="skill_yes">Yes, Skilled</label>
                          </div>
                          <div class="form-check custom-option">
                            <input class="form-check-input" type="radio" name="grafting_skill" id="skill_no" value="no"
                              checked>
                            <label class="form-check-label fw-bold" for="skill_no">No</label>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Step 6: Reference Section -->
              <div class="form-step d-none" id="step-6">
                <div class="card border-0 shadow-sm">
                  <div
                    class="card-header bg-transparent border-0 p-4 pb-0 d-flex justify-content-between align-items-center">
                    <h6 class="fs-13 fw-bold text-dark mb-0 d-flex align-items-center">
                      <span class="bg-danger-subtle text-danger rounded-2 p-1 me-2"><i
                          class="ti ti-users fs-16"></i></span>
                      Reference Person Details
                    </h6>
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3"
                      onclick="addReferenceRow()">
                      <i class="ti ti-plus me-1"></i> Add More
                    </button>
                  </div>
                  <div class="card-body p-4">
                    <div id="referenceContainer" class="row g-4">
                      <!-- Mandatory First Reference -->
                      <div class="col-md-6 reference-item">
                        <div class="p-3 bg-light rounded-4 border border-light-subtle position-relative">
                          <h7 class="fs-12 fw-bold text-muted mb-3 d-block text-uppercase">Reference Person #1</h7>
                          <div class="row g-3">
                            <div class="col-md-6">
                              <label class="form-label small fw-bold text-muted">Full Name <span
                                  class="text-danger">*</span></label>
                              <input type="text" name="ref_name[]" class="form-control bg-white border-0 shadow-sm ref-input-name" required
                                placeholder="Person name">
                            </div>
                            <div class="col-md-6">
                              <label class="form-label small fw-bold text-muted">Relation <span
                                  class="text-danger">*</span></label>
                              <select name="ref_relation[]" class="form-select bg-white border-0 shadow-sm ref-input-relation" required>
                                <option value="">Select Relation</option>
                                <option value="Father">Father</option>
                                <option value="Mother">Mother</option>
                                <option value="Brother">Brother</option>
                                <option value="Sister">Sister</option>
                                <option value="Spouse">Spouse</option>
                                <option value="Husband">Husband</option>
                                <option value="Wife">Wife</option>
                                <option value="Son">Son</option>
                                <option value="Daughter">Daughter</option>
                                <option value="Uncle">Uncle</option>
                                <option value="Aunt">Aunt</option>
                                <option value="Cousin">Cousin</option>
                                <option value="Friend">Friend</option>
                                <option value="Relative">Relative</option>
                                <option value="Neighbor">Neighbor</option>
                                <option value="Guardian">Guardian</option>
                                <option value="Other">Other</option>
                              </select>
                            </div>
                            <div class="col-md-6">
                              <label class="form-label small fw-bold text-muted">Mobile No. <span
                                  class="text-danger">*</span></label>
                              <input type="text" name="ref_mobile[]" class="form-control bg-white border-0 shadow-sm ref-input-mobile" required
                                placeholder="9876543210" maxlength="10">
                              <div class="ref-mobile-feedback small mt-1"></div>
                            </div>
                            <div class="col-md-6">
                              <label class="form-label small fw-bold text-muted">Document Type</label>
                              <select name="ref_doc_type[]" class="form-select bg-white border-0 shadow-sm">
                                <option value="">Select Doc Type</option>
                                <option value="Aadhar Card">Aadhar Card</option>
                                <option value="PAN Card">PAN Card</option>
                                <option value="Voter ID">Voter ID</option>
                                <option value="Other">Other</option>
                              </select>
                            </div>
                            <div class="col-md-12">
                              <label class="form-label small fw-bold text-muted">Upload Document</label>
                              <input type="file" name="ref_doc_file[]" class="form-control bg-white border-0 shadow-sm">
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 px-4 pb-4">
          <button type="button" class="btn btn-light rounded-pill px-4 d-none" id="prevStepBtn" onclick="changeStep(-1)">
            <i class="ti ti-arrow-left me-1"></i> Previous
          </button>
          <div class="ms-auto d-flex gap-2">
            <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Discard</button>
            <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm" id="nextStepBtn" onclick="changeStep(1)">
              Next <i class="ti ti-arrow-right ms-1"></i>
            </button>
            <button type="submit" class="btn btn-success rounded-pill px-4 shadow-sm d-none" id="submitRegistration">
              <i class="ti ti-check me-2"></i> Finish & Register
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
if (isset($_GET['vw']) == 1) {
} else {
  require('footer.php');
}
?>

<!-- Labour Details Modal -->
<div class="modal fade" id="LabourDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-0 px-4 pt-4">
        <h5 class="modal-title fw-bold text-dark d-flex align-items-center">
          <span class="bg-primary-subtle text-primary rounded-2 p-1 me-2"><i class="ti ti-id fs-20"></i></span>
          Labour Profile Details
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4" id="labourDetailsContent">
        <div class="text-center py-5">
           <div class="spinner-border text-primary" role="status"></div>
           <p class="mt-2 text-muted">Loading profile...</p>
        </div>
      </div>
      <div class="modal-footer border-0 px-4 pb-4">
        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary rounded-pill px-4" id="downloadIDManualBtn">
          <i class="ti ti-download me-1"></i> Download ID Card
        </button>
      </div>
    </div>
  </div>
</div>

<script>
    const RELATION_OPTIONS = [
      "Father", "Mother", "Brother", "Sister", "Spouse", "Husband", "Wife",
      "Son", "Daughter", "Uncle", "Aunt", "Cousin", "Friend", "Relative",
      "Neighbor", "Guardian", "Other"
    ];

    function buildRelationOptions(selectedVal) {
        selectedVal = (selectedVal || "").trim();
        let options = '<option value="">Select Relation</option>';
        let found = false;
        RELATION_OPTIONS.forEach(function(rel) {
            let isSel = (selectedVal && selectedVal.toLowerCase() === rel.toLowerCase());
            if (isSel) found = true;
            options += '<option value="' + rel + '"' + (isSel ? ' selected' : '') + '>' + rel + '</option>';
        });
        if (selectedVal && !found) {
            options += '<option value="' + selectedVal + '" selected>' + selectedVal + '</option>';
        }
        return options;
    }

    const INDIAN_BANKS = [
      "STATE BANK OF INDIA", "PUNJAB NATIONAL BANK", "BANK OF BARODA", "CANARA BANK",
      "UNION BANK OF INDIA", "BANK OF INDIA", "INDIAN BANK", "CENTRAL BANK OF INDIA",
      "INDIAN OVERSEAS BANK", "UCO BANK", "BANK OF MAHARASHTRA", "PUNJAB & SIND BANK",
      "HDFC BANK", "ICICI BANK", "AXIS BANK", "KOTAK MAHINDRA BANK", "INDUSIND BANK",
      "YES BANK", "FEDERAL BANK", "IDBI BANK", "BANDHAN BANK", "IDFC FIRST BANK",
      "RBL BANK", "SOUTH INDIAN BANK", "CITY UNION BANK", "KARUR VYSYA BANK",
      "TAMILNAD MERCANTILE BANK", "JAMMU & KASHMIR BANK", "KARNATAKA BANK",
      "AU SMALL FINANCE BANK", "EQUITAS SMALL FINANCE BANK", "UTKARSH SMALL FINANCE BANK",
      "UJJIVAN SMALL FINANCE BANK", "JANA SMALL FINANCE BANK", "FINCARE SMALL FINANCE BANK",
      "PAYTM PAYMENTS BANK", "AIRTEL PAYMENTS BANK", "INDIA POST PAYMENTS BANK",
      "FINO PAYMENTS BANK", "NSDL PAYMENTS BANK", "BARODA RAJASTHAN KSHETRIYA GRAMIN BANK",
      "RAJASTHAN MARUDHARA GRAMIN BANK", "ARYAVART BANK", "PRATHAMA UP GRAMIN BANK",
      "MADHYA PRADESH GRAMIN BANK", "MADHYANCHAL GRAMIN BANK", "SARVA HARYANA GRAMIN BANK",
      "PUNJAB GRAMIN BANK", "HIMACHAL PRADESH GRAMIN BANK", "UTTARAKHAND GRAMIN BANK",
      "DAKSHIN BIHAR GRAMIN BANK", "UTTAR BIHAR GRAMIN BANK", "PASCHIM BANGA GRAMIN BANK",
      "BANGIYA GRAMIN VIKASH BANK", "ODISHA GRAMYA BANK", "UTKAL GRAMEEN BANK",
      "ASSAM GRAMIN VIKASH BANK", "ANDHRA PRADESH GRAMEENA VIKAS BANK",
      "ANDHRA PRAGATHI GRAMEENA BANK", "TELANGANA GRAMEENA BANK", "KARNATAKA GRAMIN BANK",
      "KARNATAKA VIKAS GRAMEENA BANK", "KERALA GRAMIN BANK", "TAMIL NADU GRAMA BANK",
      "MAHARASHTRA GRAMIN BANK", "VIDHARBHA KONKAN GRAMIN BANK", "SAURASHTRA GRAMIN BANK",
      "BARODA GUJARAT GRAMIN BANK", "CHHATTISGARH RAJYA GRAMIN BANK", "JHARKHAND RAJYA GRAMIN BANK"
    ];

    let selectedBankIndex = -1;

    function renderBankSuggestions(query) {
      const $list = $("#reg_bank_suggestions");
      query = (query || "").trim().toUpperCase();
      selectedBankIndex = -1;

      if (!query) {
        $list.hide().html('');
        return;
      }

      const matches = INDIAN_BANKS.filter(function(b) {
        return b.indexOf(query) !== -1;
      });

      if (matches.length === 0) {
        $list.hide().html('');
        return;
      }

      let html = "";
      matches.forEach(function(bank, idx) {
        const regex = new RegExp("(" + query.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&') + ")", "gi");
        const highlighted = bank.replace(regex, '<strong style="color:#0d6efd;">$1</strong>');
        html += '<div class="bank-autocomplete-item" data-index="' + idx + '" data-value="' + bank + '">' +
          '<i class="ti ti-building-bank"></i> ' +
          '<span>' + highlighted + '</span>' +
          '</div>';
      });

      $list.html(html).show();
    }

    function selectBankSuggestion(bankName) {
      if (!bankName) return;
      const upperName = bankName.toUpperCase();
      $("#reg_bankName").val(upperName);
      $("#reg_bank_suggestions").hide().html('');
      validateBankName($("#reg_bankName"), $("#reg_bankNameFeedback"), true);
    }

    function validateBankName($input, $feedback, isRequired) {
      if (!$input || !$input.length) return true;
      let raw = ($input.val() || "").toString().toUpperCase();
      $input.val(raw);
      let val = raw.trim();

      if (!val) {
        if (isRequired) {
          $input.removeClass("is-valid").addClass("is-invalid")
            .css({"border": "1.5px solid #ef4444", "background-color": "#fef2f2", "box-shadow": "0 0 0 3px rgba(239, 68, 68, 0.15)"});
          if ($feedback && $feedback.length) {
            $feedback.removeClass("is-valid").addClass("is-invalid")
              .html('<span style="color: #dc2626 !important; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i class="ti ti-alert-circle"></i> Bank name is required</span>');
          }
          return false;
        } else {
          $input.removeClass("is-valid is-invalid").css({"border": "", "background-color": "", "box-shadow": ""});
          if ($feedback && $feedback.length) $feedback.removeClass("is-valid is-invalid").html('');
          return true;
        }
      }

      if (val.length < 3) {
        $input.removeClass("is-valid").addClass("is-invalid")
          .css({"border": "1.5px solid #ef4444", "background-color": "#fef2f2", "box-shadow": "0 0 0 3px rgba(239, 68, 68, 0.15)"});
        if ($feedback && $feedback.length) {
          $feedback.removeClass("is-valid").addClass("is-invalid")
            .html('<span style="color: #dc2626 !important; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i class="ti ti-alert-circle"></i> Enter a valid bank name</span>');
        }
        return false;
      }

      $input.removeClass("is-invalid").addClass("is-valid")
        .css({"border": "1.5px solid #10b981", "background-color": "#f0fdf4", "box-shadow": "0 0 0 3px rgba(16, 185, 129, 0.15)"});
      if ($feedback && $feedback.length) {
        $feedback.removeClass("is-invalid").addClass("is-valid")
          .html('<span style="color: #16a34a !important; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i class="ti ti-circle-check"></i> Valid Bank Name</span>');
      }
      return true;
    }

    function validateAccountNumber($input, $feedback, isRequired) {
      if (!$input || !$input.length) return true;
      let raw = ($input.val() || "").toString();
      let digits = raw.replace(/\D/g, '').slice(0, 18);
      if (raw !== digits) {
        $input.val(digits);
      }

      if (!digits) {
        if (isRequired) {
          $input.removeClass("is-valid").addClass("is-invalid")
            .css({"border": "1.5px solid #ef4444", "background-color": "#fef2f2", "box-shadow": "0 0 0 3px rgba(239, 68, 68, 0.15)"});
          if ($feedback && $feedback.length) {
            $feedback.removeClass("is-valid").addClass("is-invalid")
              .html('<span style="color: #dc2626 !important; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i class="ti ti-alert-circle"></i> Account number is required</span>');
          }
          return false;
        } else {
          $input.removeClass("is-valid is-invalid").css({"border": "", "background-color": "", "box-shadow": ""});
          if ($feedback && $feedback.length) $feedback.removeClass("is-valid is-invalid").html('');
          return true;
        }
      }

      // RBI rule: Indian bank account numbers are 9 to 18 numeric digits
      if (digits.length < 9) {
        $input.removeClass("is-valid").addClass("is-invalid")
          .css({"border": "1.5px solid #ef4444", "background-color": "#fef2f2", "box-shadow": "0 0 0 3px rgba(239, 68, 68, 0.15)"});
        if ($feedback && $feedback.length) {
          $feedback.removeClass("is-valid").addClass("is-invalid")
            .html('<span style="color: #dc2626 !important; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i class="ti ti-alert-circle"></i> Account number must be 9 to 18 digits (' + digits.length + '/9-18)</span>');
        }
        return false;
      }

      $input.removeClass("is-invalid").addClass("is-valid")
        .css({"border": "1.5px solid #10b981", "background-color": "#f0fdf4", "box-shadow": "0 0 0 3px rgba(16, 185, 129, 0.15)"});
      if ($feedback && $feedback.length) {
        $feedback.removeClass("is-invalid").addClass("is-valid")
          .html('<span style="color: #16a34a !important; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i class="ti ti-circle-check"></i> Valid Account Number</span>');
      }
      return true;
    }

    function validateIFSCCode($input, $feedback, isRequired) {
      if (!$input || !$input.length) return true;
      let raw = ($input.val() || "").toString().toUpperCase();
      let clean = raw.replace(/[^A-Z0-9]/g, '').slice(0, 11);
      $input.val(clean);

      if (!clean) {
        if (isRequired) {
          $input.removeClass("is-valid").addClass("is-invalid")
            .css({"border": "1.5px solid #ef4444", "background-color": "#fef2f2", "box-shadow": "0 0 0 3px rgba(239, 68, 68, 0.15)"});
          if ($feedback && $feedback.length) {
            $feedback.removeClass("is-valid").addClass("is-invalid")
              .html('<span style="color: #dc2626 !important; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i class="ti ti-alert-circle"></i> IFSC code is required</span>');
          }
          return false;
        } else {
          $input.removeClass("is-valid is-invalid").css({"border": "", "background-color": "", "box-shadow": ""});
          if ($feedback && $feedback.length) $feedback.removeClass("is-valid is-invalid").html('');
          return true;
        }
      }

      // RBI rule: Exactly 11 chars (4 letters + 0 + 6 alphanumeric)
      if (clean.length < 11) {
        $input.removeClass("is-valid").addClass("is-invalid")
          .css({"border": "1.5px solid #ef4444", "background-color": "#fef2f2", "box-shadow": "0 0 0 3px rgba(239, 68, 68, 0.15)"});
        if ($feedback && $feedback.length) {
          $feedback.removeClass("is-valid").addClass("is-invalid")
            .html('<span style="color: #dc2626 !important; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i class="ti ti-alert-circle"></i> IFSC code must be 11 characters (' + clean.length + '/11)</span>');
        }
        return false;
      }

      if (!/^[A-Z]{4}/.test(clean)) {
        $input.removeClass("is-valid").addClass("is-invalid")
          .css({"border": "1.5px solid #ef4444", "background-color": "#fef2f2", "box-shadow": "0 0 0 3px rgba(239, 68, 68, 0.15)"});
        if ($feedback && $feedback.length) {
          $feedback.removeClass("is-valid").addClass("is-invalid")
            .html('<span style="color: #dc2626 !important; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i class="ti ti-alert-circle"></i> First 4 characters must be letters (e.g. SBIN)</span>');
        }
        return false;
      }

      if (clean.charAt(4) !== '0') {
        $input.removeClass("is-valid").addClass("is-invalid")
          .css({"border": "1.5px solid #ef4444", "background-color": "#fef2f2", "box-shadow": "0 0 0 3px rgba(239, 68, 68, 0.15)"});
        if ($feedback && $feedback.length) {
          $feedback.removeClass("is-valid").addClass("is-invalid")
            .html('<span style="color: #dc2626 !important; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i class="ti ti-alert-circle"></i> 5th character must be 0 (Zero) as per RBI rule</span>');
        }
        return false;
      }

      if (!/^[A-Z]{4}0[A-Z0-9]{6}$/.test(clean)) {
        $input.removeClass("is-valid").addClass("is-invalid")
          .css({"border": "1.5px solid #ef4444", "background-color": "#fef2f2", "box-shadow": "0 0 0 3px rgba(239, 68, 68, 0.15)"});
        if ($feedback && $feedback.length) {
          $feedback.removeClass("is-valid").addClass("is-invalid")
            .html('<span style="color: #dc2626 !important; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i class="ti ti-alert-circle"></i> Invalid IFSC format (e.g. SBIN0001234)</span>');
        }
        return false;
      }

      $input.removeClass("is-invalid").addClass("is-valid")
        .css({"border": "1.5px solid #10b981", "background-color": "#f0fdf4", "box-shadow": "0 0 0 3px rgba(16, 185, 129, 0.15)"});
      if ($feedback && $feedback.length) {
        $feedback.removeClass("is-invalid").addClass("is-valid")
          .html('<span style="color: #16a34a !important; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i class="ti ti-circle-check"></i> Valid IFSC Code</span>');
      }
      return true;
    }

    function validateMobileInput($input, $feedback, isRequired) {
      if (!$input || !$input.length) return true;
      let raw = ($input.val() || "").toString();
      let digits = raw.replace(/\D/g, '').slice(0, 10);
      if (raw !== digits) {
        $input.val(digits);
      }

      if (!digits) {
        if (isRequired) {
          $input.removeClass("is-valid").addClass("is-invalid")
            .css({"border": "1.5px solid #ef4444", "background-color": "#fef2f2", "box-shadow": "0 0 0 3px rgba(239, 68, 68, 0.15)"});
          if ($feedback && $feedback.length) {
            $feedback.removeClass("is-valid").addClass("is-invalid")
              .html('<span style="color: #dc2626 !important; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i class="ti ti-alert-circle"></i> Mobile number is required</span>');
          }
          return false;
        } else {
          $input.removeClass("is-valid is-invalid").css({"border": "", "background-color": "", "box-shadow": ""});
          if ($feedback && $feedback.length) {
            $feedback.removeClass("is-valid is-invalid").html('');
          }
          return true;
        }
      }

      // Validate starting digit (6, 7, 8, 9)
      if (!/^[6-9]/.test(digits)) {
        $input.removeClass("is-valid").addClass("is-invalid")
          .css({"border": "1.5px solid #ef4444", "background-color": "#fef2f2", "box-shadow": "0 0 0 3px rgba(239, 68, 68, 0.15)"});
        if ($feedback && $feedback.length) {
          $feedback.removeClass("is-valid").addClass("is-invalid")
            .html('<span style="color: #dc2626 !important; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i class="ti ti-alert-circle"></i> Must start with 6, 7, 8, or 9</span>');
        }
        return false;
      }

      // Check length < 10
      if (digits.length < 10) {
        $input.removeClass("is-valid").addClass("is-invalid")
          .css({"border": "1.5px solid #ef4444", "background-color": "#fef2f2", "box-shadow": "0 0 0 3px rgba(239, 68, 68, 0.15)"});
        if ($feedback && $feedback.length) {
          $feedback.removeClass("is-valid").addClass("is-invalid")
            .html('<span style="color: #dc2626 !important; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i class="ti ti-alert-circle"></i> Enter 10-digit number (' + digits.length + '/10)</span>');
        }
        return false;
      }

      // Valid 10 digit number
      $input.removeClass("is-invalid").addClass("is-valid")
        .css({"border": "1.5px solid #10b981", "background-color": "#f0fdf4", "box-shadow": "0 0 0 3px rgba(16, 185, 129, 0.15)"});
      if ($feedback && $feedback.length) {
        $feedback.removeClass("is-invalid").addClass("is-valid")
          .html('<span style="color: #16a34a !important; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i class="ti ti-circle-check"></i> Valid mobile number</span>');
      }
      return true;
    }

    function validateReferenceRowMobile($row) {
      if (!$row || !$row.length) return true;
      let name = ($row.find(".ref-input-name").val() || "").trim();
      let relation = ($row.find(".ref-input-relation").val() || "").trim();
      let $mobileInput = $row.find(".ref-input-mobile");
      let $feedback = $row.find(".ref-mobile-feedback");
      let mobileVal = ($mobileInput.val() || "").trim();

      // If any field in this row has a value or mobile has digits, validate mobile
      let isRequired = !!(name || relation || mobileVal);
      return validateMobileInput($mobileInput, $feedback, isRequired);
    }

    function previewImage(input, previewId) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#' + previewId).attr('src', e.target.result);
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function addReferenceRow() {
        const container = document.getElementById('referenceContainer');
        const count = container.getElementsByClassName('reference-item').length + 1;
        const rowId = 'ref_' + Date.now();
        const relOptions = buildRelationOptions('');
        const html = `
            <div id="${rowId}" class="col-md-6 reference-item animate__animated animate__fadeIn">
                <div class="p-3 bg-light rounded-4 border border-light-subtle position-relative">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h7 class="fs-12 fw-bold text-muted mb-0 text-uppercase">Reference Person #${count}</h7>
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-circle p-1" onclick="removeReferenceRow('${rowId}')" style="width:24px; height:24px; line-height:1;">
                            <i class="ti ti-x fs-10"></i>
                        </button>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Full Name</label>
                            <input type="text" name="ref_name[]" class="form-control bg-white border-0 shadow-sm ref-input-name" placeholder="Person name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Relation</label>
                            <select name="ref_relation[]" class="form-select bg-white border-0 shadow-sm ref-input-relation">
                                ${relOptions}
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Mobile No.</label>
                            <input type="text" name="ref_mobile[]" class="form-control bg-white border-0 shadow-sm ref-input-mobile" placeholder="9876543210" maxlength="10">
                            <div class="ref-mobile-feedback small mt-1"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Document Type</label>
                            <select name="ref_doc_type[]" class="form-select bg-white border-0 shadow-sm">
                                <option value="">Select Doc Type</option>
                                <option value="Aadhar Card">Aadhar Card</option>
                                <option value="PAN Card">PAN Card</option>
                                <option value="Voter ID">Voter ID</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted">Upload Document</label>
                            <input type="file" name="ref_doc_file[]" class="form-control bg-white border-0 shadow-sm">
                        </div>
                    </div>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
    }

    function removeReferenceRow(id) {
        const row = document.getElementById(id);
        if (row) {
            row.classList.add('animate__fadeOut');
            setTimeout(() => row.remove(), 500);
        }
    }

    // Wizard Logic
    let currentStep = 1;
    const totalSteps = 6;

    function changeStep(n) {
        if (n === 1 && !validateStep(currentStep)) return;

        // Hide current step
        $(`#step-${currentStep}`).addClass('d-none');
        $(`.step[data-step="${currentStep}"]`).removeClass('active').addClass(n === 1 ? 'completed' : '');

        currentStep += n;

        // Show new step
        $(`#step-${currentStep}`).removeClass('d-none');
        $(`.step[data-step="${currentStep}"]`).addClass('active').removeClass('completed');

        // Update Progress Bar
        const progress = (currentStep / totalSteps) * 100;
        $('#stepProgressBar').css('width', progress + '%');

        // Toggle Buttons
        $('#prevStepBtn').toggleClass('d-none', currentStep === 1);
        $('#nextStepBtn').toggleClass('d-none', currentStep === totalSteps);
        $('#submitRegistration').toggleClass('d-none', currentStep !== totalSteps);
    }

    function validateStep(step) {
        if (step === 1) {
            var loc = $('#reg_workingLocation').val();
            if (!loc) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Location Selection Missing',
                    text: 'Please select a Working Location first to get a suggested Labour ID.',
                    confirmButtonColor: '#0d6efd'
                });
                $('#reg_workingLocation').addClass('is-invalid');
                return false;
            }
        }
        let isValid = true;

        if (step === 2) {
            let isBankValid = validateBankName($('#reg_bankName'), $('#reg_bankNameFeedback'), true);
            let isAccValid = validateAccountNumber($('#reg_accountNumber'), $('#reg_accountNumberFeedback'), true);
            let isIfscValid = validateIFSCCode($('#reg_ifscCode'), $('#reg_ifscCodeFeedback'), true);
            
            if (!isBankValid || !isAccValid || !isIfscValid) {
                isValid = false;
            }
        }

        // Step 3: Validate mobileNo, emergencyContact and idProofNo
        if (step === 3) {
            let isMobileValid = validateMobileInput($('#reg_mobileNo'), $('#reg_mobileNoFeedback'), true);
            let isEmergencyValid = validateMobileInput($('#reg_emergencyContact'), $('#reg_emergencyContactFeedback'), true);
            if (!isMobileValid || !isEmergencyValid) {
                isValid = false;
            }

            var proofType  = $('#reg_idProofType').val();
            var proofNoVal = $('#reg_idProofNo').val().trim().toUpperCase();
            var $proofInput = $('#reg_idProofNo');
            var $proofFb    = $('#reg_idProofNoFeedback');

            if (proofType) {
                if (!proofNoVal) {
                    $proofInput.addClass('is-invalid');
                    $proofFb.text('ID proof number is required.');
                    isValid = false;
                } else if (proofType === 'Aadhar Card' && !/^\d{12}$/.test(proofNoVal)) {
                    $proofInput.addClass('is-invalid');
                    $proofFb.text('Aadhar number must be exactly 12 digits.');
                    isValid = false;
                } else if (proofType === 'PAN Card' && !/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/.test(proofNoVal)) {
                    $proofInput.addClass('is-invalid');
                    $proofFb.text('Invalid PAN format. Expected: ABCDE1234F.');
                    isValid = false;
                } else {
                    $proofInput.val(proofNoVal).removeClass('is-invalid').addClass('is-valid');
                    $proofFb.text('');
                }
            }
        }

        // Step 6: Reference Section validation
        if (step === 6) {
            let refErrors = [];
            $('#referenceContainer .reference-item').each(function(idx) {
                let $item = $(this);
                let personNum = idx + 1;
                let $nameInput = $item.find('.ref-input-name');
                let $relSelect = $item.find('.ref-input-relation');
                let $mobInput  = $item.find('.ref-input-mobile');
                let $feedback  = $item.find('.ref-mobile-feedback');

                let name = ($nameInput.val() || '').trim();
                let relation = ($relSelect.val() || '').trim();
                let mobile = ($mobInput.val() || '').trim();

                let isRowRequired = (idx === 0) || !!(name || relation || mobile);

                if (isRowRequired) {
                    let missing = [];
                    if (!name) {
                        missing.push("Name");
                        $nameInput.addClass("is-invalid").css({"border": "1.5px solid #ef4444", "background-color": "#fef2f2"});
                    } else {
                        $nameInput.removeClass("is-invalid").css({"border": "", "background-color": ""});
                    }

                    if (!relation) {
                        missing.push("Relation");
                        $relSelect.addClass("is-invalid").css({"border": "1.5px solid #ef4444", "background-color": "#fef2f2"});
                    } else {
                        $relSelect.removeClass("is-invalid").css({"border": "", "background-color": ""});
                    }

                    let isMobValid = validateMobileInput($mobInput, $feedback, true);
                    if (!isMobValid) {
                        if (!mobile) {
                            missing.push("Mobile");
                        } else {
                            refErrors.push("Reference Person " + personNum + ": Invalid 10-digit mobile number");
                        }
                    }

                    if (missing.length > 0) {
                        refErrors.push("Reference Person " + personNum + " requires " + missing.join(", "));
                    }
                } else {
                    $nameInput.removeClass("is-invalid").css({"border": "", "background-color": ""});
                    $relSelect.removeClass("is-invalid").css({"border": "", "background-color": ""});
                    $mobInput.removeClass("is-invalid is-valid").css({"border": "", "background-color": ""});
                    $feedback.removeClass("is-invalid is-valid").html('');
                }
            });

            if (refErrors.length > 0) {
                isValid = false;
                Swal.fire({
                    icon: 'warning',
                    title: 'Reference Details Required',
                    text: refErrors[0],
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3500
                });
            }
        }

        $(`#step-${step} [required]`).each(function() {
            let $el = $(this);
            let val = $el.val();
            
            if (!val || ($el.is(':radio') && !$(`input[name="${$el.attr('name')}"]:checked`).val())) {
                if ($el.is(':file')) {
                    $el.closest('.bg-light').addClass('border-danger').removeClass('border-light-subtle');
                } else {
                    $el.addClass('is-invalid');
                }
                isValid = false;
            } else {
                if ($el.is(':file')) {
                    $el.closest('.bg-light').removeClass('border-danger').addClass('border-light-subtle');
                } else {
                    $el.removeClass('is-invalid');
                }
            }
        });

        if (!isValid && step !== 6) {
            Swal.fire({
                icon: 'warning',
                title: 'Required Fields',
                text: 'Please enter valid details in all mandatory fields to proceed.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        }
        return isValid;
    }

    // ID Proof Type → Show/Hide Number field with validation
    function handleIdProofTypeChange(sel) {
        var val = $(sel).val();
        var $wrapper = $('#reg_idProofNoWrapper');
        var $input   = $('#reg_idProofNo');
        var $label   = $('#reg_idProofNoLabel');
        var $fb      = $('#reg_idProofNoFeedback');

        if (!val) {
            $wrapper.addClass('d-none');
            $input.val('').removeAttr('required').attr('maxlength', '20');
            return;
        }

        $wrapper.removeClass('d-none');
        $input.attr('required', 'required');

        if (val === 'Aadhar Card') {
            $label.html('Aadhar Number <span class="text-danger">*</span>');
            $input.attr('maxlength', '12').attr('placeholder', '12-digit Aadhar number');
            $input.attr('data-proof-type', 'aadhar');
        } else if (val === 'PAN Card') {
            $label.html('PAN Number <span class="text-danger">*</span>');
            $input.attr('maxlength', '10').attr('placeholder', 'e.g. ABCDE1234F');
            $input.attr('data-proof-type', 'pan');
        } else {
            $label.html('ID Proof Number <span class="text-danger">*</span>');
            $input.attr('maxlength', '50').attr('placeholder', 'Enter ID proof number');
            $input.attr('data-proof-type', 'other');
        }
        $input.removeClass('is-invalid is-valid');
        $fb.text('');
    }

    // Validate idProofNo on input/blur
    $(document).on('input blur', '#reg_idProofNo', function () {
        var $input  = $(this);
        var type    = $input.attr('data-proof-type');
        var val     = $input.val().trim().toUpperCase();
        var $fb     = $('#reg_idProofNoFeedback');

        if (!val) {
            $input.removeClass('is-valid').addClass('is-invalid');
            $fb.text('ID proof number is required.');
            return;
        }

        if (type === 'aadhar') {
            if (/^\d{12}$/.test(val)) {
                $input.removeClass('is-invalid').addClass('is-valid'); $fb.text('');
            } else {
                $input.removeClass('is-valid').addClass('is-invalid');
                $fb.text('Aadhar number must be exactly 12 digits (numbers only).');
            }
        } else if (type === 'pan') {
            if (/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/.test(val)) {
                $input.val(val); // Uppercase PAN
                $input.removeClass('is-invalid').addClass('is-valid'); $fb.text('');
            } else {
                $input.removeClass('is-valid').addClass('is-invalid');
                $fb.text('Invalid PAN format. Expected: ABCDE1234F (5 letters, 4 digits, 1 letter).');
            }
        } else {
            $input.removeClass('is-invalid').addClass('is-valid'); $fb.text('');
        }
    });

    // Bind change on idProofType select (register form uses id reg_idProofType)
    $(document).on('change', '#reg_idProofType', function() {
        handleIdProofTypeChange(this);
    });

    // Reset idProofNo wrapper on modal open/reset
    function resetIdProofNoField() {
        $('#reg_idProofNoWrapper').addClass('d-none');
        $('#reg_idProofNo').val('').removeAttr('required').removeClass('is-invalid is-valid');
        $('#reg_idProofNoFeedback').text('');
    }

    // Reset wizard when modal opens
    $('#LabourRegistrationModal').on('show.bs.modal', function (e) {
        currentStep = 1;
        $('.form-step').addClass('d-none');
        $('#step-1').removeClass('d-none');
        $('.step').removeClass('active completed');
        $('.step[data-step="1"]').addClass('active');
        $('#stepProgressBar').css('width', '16.6%');
        $('#prevStepBtn').addClass('d-none');
        $('#nextStepBtn').removeClass('d-none');
        $('#submitRegistration').addClass('d-none');
        $('.is-invalid, .is-valid').removeClass('is-invalid is-valid');
        $('.validation-feedback, .ref-mobile-feedback').html('');

        // Check if opened via Register button (Add mode)
        if (e && e.relatedTarget) {
            $('#LabourRegistrationModal h4').text("Labour Registration");
            $('#LabourRegistrationModal .modal-header p').text("Onboard new workforce with detailed credentials");
            $('#submitRegistration').html('<i class="ti ti-check me-2"></i> Finish & Register');
            $('#reg_employeeID').val('');
            $('#LabourRegistrationForm')[0].reset();
            $('#reg_fatherNameWrapper').show();
            $('#reg_motherNameWrapper').show();
            $('#reg_spouseNameWrapper').hide();
            $('#bankDocPreviewContainer').addClass('d-none');
            $('#labourPhotoPreview').attr('src', generateSelfAvatarJS('Labour', '', '#f8f9fa', '#adb5bd', 256));
            resetIdProofNoField();
            loadRegistrationStates('');
            $('#reg_districtid').html('<option value="">Select State First</option>').prop('disabled', true);
            $('#reg_same_as_current').prop('checked', false);
            // Reset reference container to original state
            const initRelOptions = buildRelationOptions('');
            $("#referenceContainer").html(`
                <!-- Mandatory First Reference -->
                <div class="col-md-6 reference-item">
                  <div class="p-3 bg-light rounded-4 border border-light-subtle position-relative">
                    <h7 class="fs-12 fw-bold text-muted mb-3 d-block text-uppercase">Reference Person #1</h7>
                    <div class="row g-3">
                      <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="ref_name[]" class="form-control bg-white border-0 shadow-sm ref-input-name" required placeholder="Person name">
                      </div>
                      <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Relation <span class="text-danger">*</span></label>
                        <select name="ref_relation[]" class="form-select bg-white border-0 shadow-sm ref-input-relation" required>
                          ${initRelOptions}
                        </select>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Mobile No. <span class="text-danger">*</span></label>
                        <input type="text" name="ref_mobile[]" class="form-control bg-white border-0 shadow-sm ref-input-mobile" required placeholder="9876543210" maxlength="10">
                        <div class="ref-mobile-feedback small mt-1"></div>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Document Type</label>
                        <select name="ref_doc_type[]" class="form-select bg-white border-0 shadow-sm">
                          <option value="">Select Doc Type</option>
                          <option value="Aadhar Card">Aadhar Card</option>
                          <option value="PAN Card">PAN Card</option>
                          <option value="Voter ID">Voter ID</option>
                          <option value="Other">Other</option>
                        </select>
                      </div>
                      <div class="col-md-12">
                        <label class="form-label small fw-bold text-muted">Upload Document</label>
                        <input type="file" name="ref_doc_file[]" class="form-control bg-white border-0 shadow-sm">
                      </div>
                    </div>
                  </div>
                </div>
            `);
        } else {
            $('#labourPhoto').prop('required', false);
        }
    });

    $(document).on("click", "#newlabourregistration", function () {
        $('#LabourRegistrationModal h4').text("Labour Registration");
        $('#LabourRegistrationModal .modal-header p').text("Onboard new workforce with detailed credentials");
        $('#submitRegistration').html('<i class="ti ti-check me-2"></i> Finish & Register');
        $('#reg_employeeID').val('');
        $('#LabourRegistrationForm')[0].reset();
        $('#labourPhotoPreview').attr('src', generateSelfAvatarJS('Labour', '', '#f8f9fa', '#adb5bd', 256));
        $('#labourPhoto').prop('required', false);
        $('.is-invalid, .is-valid').removeClass('is-invalid is-valid');
        $('.validation-feedback, .ref-mobile-feedback').html('');
        const initRelOptions = buildRelationOptions('');
        // Reset reference container to original state
        $("#referenceContainer").html(`
            <!-- Mandatory First Reference -->
            <div class="col-md-6 reference-item animate__animated animate__fadeIn">
              <div class="p-3 bg-light rounded-4 border border-light-subtle position-relative">
                <h7 class="fs-12 fw-bold text-muted mb-3 d-block text-uppercase">Reference Person #1</h7>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="ref_name[]" class="form-control bg-white border-0 shadow-sm ref-input-name" required placeholder="Person name">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted">Relation <span class="text-danger">*</span></label>
                    <select name="ref_relation[]" class="form-select bg-white border-0 shadow-sm ref-input-relation" required>
                      ${initRelOptions}
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted">Mobile No. <span class="text-danger">*</span></label>
                    <input type="text" name="ref_mobile[]" class="form-control bg-white border-0 shadow-sm ref-input-mobile" required placeholder="9876543210" maxlength="10">
                    <div class="ref-mobile-feedback small mt-1"></div>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted">Document Type</label>
                    <select name="ref_doc_type[]" class="form-select bg-white border-0 shadow-sm">
                      <option value="">Select Doc Type</option>
                      <option value="Aadhar Card">Aadhar Card</option>
                      <option value="PAN Card">PAN Card</option>
                      <option value="Voter ID">Voter ID</option>
                      <option value="Other">Other</option>
                    </select>
                  </div>
                  <div class="col-md-12">
                    <label class="form-label small fw-bold text-muted">Upload Document</label>
                    <input type="file" name="ref_doc_file[]" class="form-control bg-white border-0 shadow-sm">
                  </div>
                </div>
              </div>
            </div>
        `);
    });
</script>

<script src="assets/js/custom_labour.js?v=<?php echo time(); ?>"></script>
<script type="text/javascript">
  var routurl = $("body").data("routurl");
  var key = $("body").data("key");
  var loginname = '<?php echo isset($loginname) ? $loginname : ""; ?>';

  $(document).ready(function () {
    loadLabours();
    getlabourworkinglocationdropdown('reg_workingLocation','all', 'all');

    // Bank Name Autocomplete & Suggestions Keyboard Navigation
    $(document).on('input', '#reg_bankName', function() {
        var query = $(this).val();
        renderBankSuggestions(query);
        validateBankName($(this), $('#reg_bankNameFeedback'), true);
    });

    $(document).on('keydown', '#reg_bankName', function(e) {
        var $list = $("#reg_bank_suggestions");
        var $items = $list.find(".bank-autocomplete-item");

        if (!$list.is(":visible") || $items.length === 0) return;

        if (e.key === "ArrowDown") {
            e.preventDefault();
            selectedBankIndex = (selectedBankIndex + 1) % $items.length;
            $items.removeClass("active");
            $items.eq(selectedBankIndex).addClass("active");
            $items.eq(selectedBankIndex)[0].scrollIntoView({ block: "nearest" });
        } else if (e.key === "ArrowUp") {
            e.preventDefault();
            selectedBankIndex = (selectedBankIndex - 1 + $items.length) % $items.length;
            $items.removeClass("active");
            $items.eq(selectedBankIndex).addClass("active");
            $items.eq(selectedBankIndex)[0].scrollIntoView({ block: "nearest" });
        } else if (e.key === "Enter") {
            if (selectedBankIndex >= 0 && selectedBankIndex < $items.length) {
                e.preventDefault();
                var val = $items.eq(selectedBankIndex).data("value");
                selectBankSuggestion(val);
            }
        } else if (e.key === "Escape") {
            $list.hide();
        }
    });

    $(document).on("click", ".bank-autocomplete-item", function() {
        var val = $(this).data("value");
        selectBankSuggestion(val);
    });

    $(document).on("click", function(e) {
        if (!$(e.target).closest(".bank-autocomplete-wrapper").length) {
            $("#reg_bank_suggestions").hide();
        }
    });

    $(document).on('blur', '#reg_bankName', function() {
        validateBankName($(this), $('#reg_bankNameFeedback'), true);
    });

    // RBI Validation: Account Number (9-18 digits, digits only)
    $(document).on('input blur keyup change', '#reg_accountNumber', function() {
        validateAccountNumber($(this), $('#reg_accountNumberFeedback'), true);
    });

    // RBI Validation: IFSC Code (11 alphanumeric, 5th is 0, uppercase)
    $(document).on('input blur keyup change', '#reg_ifscCode', function() {
        validateIFSCCode($(this), $('#reg_ifscCodeFeedback'), true);
    });

    // Mobile Number Live Validation
    $(document).on('input blur keyup change', '#reg_mobileNo', function() {
        validateMobileInput($(this), $('#reg_mobileNoFeedback'), true);
    });

    // Emergency Contact Live Validation
    $(document).on('input blur keyup change', '#reg_emergencyContact', function() {
        validateMobileInput($(this), $('#reg_emergencyContactFeedback'), true);
    });

    // Reference Persons Live Validation
    $(document).on('input blur keyup change', '.ref-input-mobile', function() {
        var $row = $(this).closest('.reference-item');
        validateReferenceRowMobile($row);
    });

    $(document).on('input blur keyup change', '.ref-input-name', function() {
        var val = ($(this).val() || "").trim();
        if (val) {
            $(this).removeClass("is-invalid").css({"border": "", "background-color": ""});
        }
        var $row = $(this).closest('.reference-item');
        validateReferenceRowMobile($row);
    });

    $(document).on('change', '.ref-input-relation', function() {
        var val = ($(this).val() || "").trim();
        if (val) {
            $(this).removeClass("is-invalid").css({"border": "", "background-color": ""});
        }
        var $row = $(this).closest('.reference-item');
        validateReferenceRowMobile($row);
    });

    // Fetch Suggested Labour ID based on location selection
    $(document).on('change', '#reg_workingLocation', function() {
        var locId = $(this).val();
        if (!locId) {
            $('#reg_seriesid').val('');
            $('#reg_seriesid').data('fixed-prefix', '');
            return;
        }
        $.post(routurl + 'modal/hrm_laboursalarymodal.php?action=GetSuggestedLabourID', { locationid: locId }, function(res) {
            var data = (typeof res === 'object') ? res : JSON.parse(res);
            if (data.status === 'Success') {
                $('#reg_seriesid').val(data.suggested_id);
                let valStr = String(data.suggested_id);
                let fixedPrefix = valStr.substring(0, 2);
                $('#reg_seriesid').data('fixed-prefix', fixedPrefix);
            }
        });
    });

    // Spouse / Father / Mother Name: show/hide based on marital status + gender
    function updateSpouseFieldVisibility() {
        var marital = $('#reg_maritalStatus').val();
        var gender  = $('#reg_gender').val();

        if (gender === 'Female' && marital === 'Married') {
            $('#reg_fatherNameWrapper').hide();
            $('#reg_motherNameWrapper').hide();
            $('#reg_spouseNameWrapper').show();
            $('#reg_spouseNameLabel').text('Husband Name');
            $('#reg_spouseName').attr('placeholder', 'Enter husband name');
        } else if (marital === 'Married') {
            $('#reg_fatherNameWrapper').show();
            $('#reg_motherNameWrapper').show();
            $('#reg_spouseNameWrapper').show();
            if (gender === 'Male') {
                $('#reg_spouseNameLabel').text('Wife Name');
                $('#reg_spouseName').attr('placeholder', 'Enter wife name');
            } else {
                $('#reg_spouseNameLabel').text('Spouse Name');
                $('#reg_spouseName').attr('placeholder', 'Enter spouse name');
            }
        } else {
            $('#reg_fatherNameWrapper').show();
            $('#reg_motherNameWrapper').show();
            $('#reg_spouseNameWrapper').hide();
            $('#reg_spouseName').val('');
        }
    }
    $(document).on('change', '#reg_maritalStatus, #reg_gender', function() {
        updateSpouseFieldVisibility();
    });

    // Address Builder: States, Districts & Auto-fill Textarea
    var regStatesCache = null;
    var regDistrictsCache = {};

    window.loadRegistrationStates = function(selectedStateId, callback) {
        if (regStatesCache) {
            $("#reg_stateid").html(regStatesCache);
            if (selectedStateId) $("#reg_stateid").val(selectedStateId);
            if (typeof callback === 'function') callback();
            return;
        }
        $.post(routurl + "modal/hrm_laboursalarymodal.php?action=GetMasterStates", function(res) {
            try {
                var data = (typeof res === "string") ? JSON.parse(res) : res;
                var html = '<option value="">Select State</option>';
                if (data && data.status === "Success" && Array.isArray(data.data)) {
                    data.data.forEach(function(st) {
                        html += '<option value="' + st.stateid + '" data-name="' + (st.statename || '') + '">' + st.statename + '</option>';
                    });
                }
                regStatesCache = html;
                $("#reg_stateid").html(html);
                if (selectedStateId) $("#reg_stateid").val(selectedStateId);
                if (typeof callback === 'function') callback();
            } catch(e) {
                console.error("Error loading states:", e);
            }
        });
    };

    window.loadRegistrationDistricts = function(stateid, selectedDistrictId, callback) {
        if (!stateid) {
            $("#reg_districtid").html('<option value="">Select State First</option>').prop("disabled", true);
            if (typeof callback === 'function') callback();
            return;
        }
        if (regDistrictsCache[stateid]) {
            $("#reg_districtid").html(regDistrictsCache[stateid]).prop("disabled", false);
            if (selectedDistrictId) $("#reg_districtid").val(selectedDistrictId);
            if (typeof callback === 'function') callback();
            return;
        }
        $("#reg_districtid").html('<option value="">Loading districts...</option>').prop("disabled", true);
        $.post(routurl + "modal/hrm_laboursalarymodal.php?action=GetMasterDistricts", { stateid: stateid }, function(res) {
            try {
                var data = (typeof res === "string") ? JSON.parse(res) : res;
                var html = '<option value="">Select District</option>';
                if (data && data.status === "Success" && Array.isArray(data.data)) {
                    data.data.forEach(function(dt) {
                        html += '<option value="' + dt.districtid + '" data-name="' + (dt.districtname || '') + '">' + dt.districtname + '</option>';
                    });
                }
                regDistrictsCache[stateid] = html;
                $("#reg_districtid").html(html).prop("disabled", false);
                if (selectedDistrictId) $("#reg_districtid").val(selectedDistrictId);
                if (typeof callback === 'function') callback();
            } catch(e) {
                console.error("Error loading districts:", e);
                $("#reg_districtid").html('<option value="">Error loading districts</option>');
            }
        });
    };

    window.updateRegistrationAddressFromBuilder = function() {
        var stateName = $("#reg_stateid option:selected").data("name") || ($("#reg_stateid option:selected").val() ? $("#reg_stateid option:selected").text().trim() : "");
        var districtName = $("#reg_districtid option:selected").data("name") || ($("#reg_districtid option:selected").val() ? $("#reg_districtid option:selected").text().trim() : "");
        var tahseel = ($("#reg_tahseel").val() || "").trim();
        var city = ($("#reg_city").val() || "").trim();

        var parts = [];
        if (city) parts.push(city);
        if (tahseel) parts.push(tahseel);
        if (districtName && districtName !== "Select District" && districtName !== "Select State First" && districtName !== "Loading districts...") parts.push(districtName);
        if (stateName && stateName !== "Select State") parts.push(stateName);

        var composed = parts.join(", ");
        if (composed) {
            $("#reg_current_address").val(composed);
            if ($("#reg_same_as_current").is(":checked")) {
                $("#reg_permanent_address").val(composed);
            }
        }
    };

    $(document).on("change", "#reg_stateid", function() {
        var stateid = $(this).val();
        loadRegistrationDistricts(stateid, '', function() {
            updateRegistrationAddressFromBuilder();
        });
    });

    $(document).on("change", "#reg_districtid", function() {
        updateRegistrationAddressFromBuilder();
    });

    $(document).on("input", "#reg_tahseel, #reg_city", function() {
        updateRegistrationAddressFromBuilder();
    });

    $(document).on("change", "#reg_same_as_current", function() {
        if ($(this).is(":checked")) {
            $("#reg_permanent_address").val($("#reg_current_address").val());
        }
    });

    $(document).on("input", "#reg_current_address", function() {
        if ($("#reg_same_as_current").is(":checked")) {
            $("#reg_permanent_address").val($(this).val());
        }
    });
  });
</script>