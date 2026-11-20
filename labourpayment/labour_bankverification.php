<?php
require('header.php');

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

<!-- Google Fonts & Custom Styling -->
<link rel="stylesheet" href="assets/css/custom_labour.css">
<style>
  :root {
    --primary-gradient: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    --save-btn-gradient: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
    --success-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
    --warning-gradient: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    --danger-gradient: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    --card-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05), 0 2px 6px -1px rgba(0, 0, 0, 0.03);
    --card-shadow-hover: 0 10px 24px -4px rgba(0, 0, 0, 0.08), 0 4px 10px -2px rgba(0, 0, 0, 0.04);
  }

  body {
    background-color: #f8fafc;
    color: #1e293b;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  }

  /* Stat & KPI Cards */
  .kpi-stat-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: var(--card-shadow);
    padding: 18px 20px;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    position: relative;
    overflow: hidden;
  }

  .kpi-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--card-shadow-hover);
    border-color: #cbd5e1;
  }

  .kpi-stat-card.active-kpi {
    border: 2px solid #2563eb !important;
    background: #f8faff;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12) !important;
  }

  .kpi-stat-card.active-kpi::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--primary-gradient);
  }

  .kpi-stat-card .icon-wrapper {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
  }

  .kpi-primary .icon-wrapper { background: #eff6ff; color: #2563eb; }
  .kpi-warning .icon-wrapper { background: #fffbeb; color: #d97706; }
  .kpi-success .icon-wrapper { background: #ecfdf5; color: #059669; }
  .kpi-danger .icon-wrapper { background: #fef2f2; color: #dc2626; }

  /* Pulse dot for pending verification */
  .pulse-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: #f59e0b;
    box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7);
    animation: pulse 1.6s infinite cubic-bezier(0.66, 0, 0, 1);
    margin-right: 6px;
  }

  @keyframes pulse {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(245, 158, 11, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
  }

  /* --- STRIP DESIGN ARCHITECTURE --- */
  .strips-header-wrapper {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    margin-bottom: 8px;
    padding: 12px 18px;
  }

  .strips-header {
    display: grid;
    grid-template-columns: 45px 2.2fr 2.5fr 110px 2fr 110px;
    align-items: center;
    color: #475569;
    font-size: 11.5px;
    text-transform: uppercase;
    font-weight: 700;
    letter-spacing: 0.6px;
  }

  .bank-strips-container {
    display: flex;
    flex-direction: column;
    gap: 8px;
    width: 100%;
  }

  .bank-strip-item {
    background: #ffffff;
    border-radius: 14px;
    padding: 14px 18px;
    display: grid;
    grid-template-columns: 45px 2.2fr 2.5fr 110px 2fr 110px;
    align-items: center;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  }

  .bank-strip-item:hover {
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
    border-color: #cbd5e1;
    transform: translateY(-2px);
    background-color: #fafcff;
  }

  .bank-strip-item.item-selected {
    border-color: #2563eb !important;
    background-color: #f8faff !important;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15) !important;
  }

  /* Custom Badges */
  .badge-status {
    padding: 5px 11px;
    border-radius: 20px;
    font-size: 11.5px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    letter-spacing: 0.2px;
    width: fit-content;
  }

  .badge-pending {
    background-color: #fffbeb;
    color: #b45309;
    border: 1px solid #fde68a;
  }

  .badge-verified {
    background-color: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
  }

  .badge-rejected {
    background-color: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
  }

  .badge-id {
    background: #f1f5f9;
    color: #0f172a;
    font-weight: 700;
    padding: 3px 7px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    font-size: 11.5px;
    letter-spacing: 0.5px;
  }

  .avatar-labour {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    object-fit: cover;
    border: 2px solid #e2e8f0;
    background: #e2e8f0;
    flex-shrink: 0;
  }

  .avatar-initial {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #6366f1, #4f46e5);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 16px;
    text-transform: uppercase;
    flex-shrink: 0;
  }

  /* Document Thumbnail Preview */
  .doc-thumb-wrapper {
    position: relative;
    width: 60px;
    height: 40px;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #cbd5e1;
    cursor: pointer;
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
  }

  .doc-thumb-wrapper:hover {
    transform: scale(1.06);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
    border-color: #2563eb;
  }

  .doc-thumb-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .doc-thumb-overlay {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    opacity: 0;
    transition: opacity 0.2s ease;
    font-size: 14px;
  }

  .doc-thumb-wrapper:hover .doc-thumb-overlay {
    opacity: 1;
  }

  .doc-missing-badge {
    background: #f1f5f9;
    color: #94a3b8;
    font-size: 11px;
    padding: 4px 8px;
    border-radius: 6px;
    border: 1px dashed #cbd5e1;
    display: inline-flex;
    align-items: center;
    gap: 4px;
  }

  /* Bulk action bar */
  .bulk-bar {
    position: sticky;
    top: 70px;
    z-index: 900;
    background: #1e293b;
    color: #ffffff;
    border-radius: 12px;
    padding: 12px 20px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.25);
    margin-bottom: 16px;
    display: none;
    align-items: center;
    justify-content: space-between;
    animation: slideDown 0.3s ease-out;
  }

  @keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
  }

  /* Copy button micro-interaction */
  .btn-copy-code {
    border: none;
    background: transparent;
    color: #94a3b8;
    cursor: pointer;
    padding: 2px 5px;
    border-radius: 4px;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
  }

  .btn-copy-code:hover {
    color: #2563eb;
    background: #eff6ff;
  }

  /* Status tab pill navigation */
  .status-pill-nav {
    display: inline-flex;
    background: #f1f5f9;
    padding: 4px;
    border-radius: 12px;
    gap: 4px;
    flex-wrap: wrap;
  }

  .status-pill-btn {
    border: none;
    background: transparent;
    padding: 7px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }

  .status-pill-btn.active {
    background: #ffffff;
    color: #0f172a;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
  }

  .status-pill-btn .pill-count {
    font-size: 11px;
    padding: 2px 7px;
    border-radius: 10px;
    background: #e2e8f0;
    color: #475569;
  }

  .status-pill-btn.active .pill-count {
    background: #0f172a;
    color: #ffffff;
  }

  /* Action Buttons */
  .btn-action-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid transparent;
    transition: all 0.15s ease;
    cursor: pointer;
    font-size: 15px;
  }

  .btn-action-verify {
    background: #ecfdf5;
    color: #059669;
    border-color: #a7f3d0;
  }
  .btn-action-verify:hover {
    background: #059669;
    color: #ffffff;
  }

  .btn-action-reject {
    background: #fef2f2;
    color: #dc2626;
    border-color: #fecaca;
  }
  .btn-action-reject:hover {
    background: #dc2626;
    color: #ffffff;
  }

  .btn-action-edit {
    background: #eff6ff;
    color: #2563eb;
    border-color: #bfdbfe;
  }
  .btn-action-edit:hover {
    background: #2563eb;
    color: #ffffff;
  }

  /* Modal Styling: Light body, slightly darker subtle tint for header */
  .modal-content-custom {
    border-radius: 16px !important;
    overflow: hidden;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 20px 45px -10px rgba(0, 0, 0, 0.15) !important;
  }

  .modal-header-custom {
    background-color: #f1f5f9 !important;
    border-bottom: 1px solid #e2e8f0 !important;
    padding: 16px 22px !important;
  }

  .modal-body-custom {
    background-color: #ffffff !important;
    padding: 22px !important;
  }

  .modal-footer-custom {
    background-color: #f8fafc !important;
    border-top: 1px solid #e2e8f0 !important;
    padding: 14px 22px !important;
  }

  /* Stacked Modal Z-Index Management */
  #docPreviewModal {
    z-index: 1070 !important;
  }
  .modal-backdrop-preview {
    z-index: 1065 !important;
  }

  /* Image & PDF Viewer Modal */
  #docPreviewModal .modal-body {
    background: #f8fafc !important;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 420px;
    padding: 20px;
  }

  #docPreviewModal img {
    max-height: 75vh;
    max-width: 100%;
    object-fit: contain;
    border-radius: 10px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.12);
    border: 1px solid #e2e8f0;
  }

  #docPreviewModal iframe {
    width: 100%;
    height: 75vh;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    background: #ffffff;
  }

  /* Responsive Strip to Full Details Card on Mobile/Tablet */
  @media (max-width: 991px) {
    .strips-header-wrapper {
      display: none !important;
    }

    .bank-strip-item {
      display: flex;
      flex-direction: column;
      align-items: stretch;
      gap: 12px;
      padding: 16px;
      position: relative;
    }

    .mobile-strip-top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid #f1f5f9;
      padding-bottom: 10px;
      gap: 8px;
    }

    .mobile-strip-body {
      display: grid;
      grid-template-columns: 1fr;
      gap: 12px;
    }

    .mobile-strip-footer {
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-top: 1px solid #f1f5f9;
      padding-top: 10px;
      gap: 10px;
      flex-wrap: wrap;
    }
  }
</style>

<div class="content container-fluid py-4">

    <!-- Page Header & Breadcrumb -->
    <div class="d-md-flex align-items-center justify-content-between mb-4">
      <div>
        <h3 class="page-title fw-bold text-dark mb-1">Labour Bank Verification</h3>
        <ul class="breadcrumb bg-transparent p-0 mb-0" style="font-size: 13px;">
          <li class="breadcrumb-item"><a href="index.php?k=<?php echo $key; ?>" class="text-muted"><i class="ti ti-home me-1"></i>Home</a></li>
          <li class="breadcrumb-item"><a href="labourmaster.php?k=<?php echo $key; ?>" class="text-muted">Labour Master</a></li>
          <li class="breadcrumb-item active text-primary fw-semibold">Bank Verification</li>
        </ul>
      </div>
      <div class="d-flex align-items-center gap-2 mt-3 mt-md-0 flex-wrap">
        <button type="button" class="btn btn-outline-secondary d-flex align-items-center gap-1 rounded-pill px-3" id="btnRefreshList">
          <i class="ti ti-refresh fs-16"></i> <span>Refresh</span>
        </button>
        <button type="button" class="btn btn-outline-success d-flex align-items-center gap-1 rounded-pill px-3" id="btnExportExcel">
          <i class="ti ti-file-spreadsheet fs-16"></i> <span>Export Excel</span>
        </button>
        <button type="button" class="btn btn-outline-primary d-flex align-items-center gap-1 rounded-pill px-3" id="btnExportCSV">
          <i class="ti ti-file-type-csv fs-16"></i> <span>Export CSV</span>
        </button>
      </div>
    </div>

    <!-- Top KPI Summary Cards -->
    <div class="row g-3 mb-4">
      <!-- Total Bank Details Card -->
      <div class="col-xl-3 col-sm-6">
        <div class="kpi-stat-card kpi-primary active-kpi" data-status="all" id="kpi-all">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <span class="text-muted small fw-bold text-uppercase d-block mb-1" style="letter-spacing: 0.5px;">All Bank Records</span>
              <h2 class="mb-0 fw-bold text-dark" id="kpi_total_count">0</h2>
              <small class="text-muted mt-1 d-block"><span id="kpi_doc_count" class="text-primary fw-semibold">0</span> with cheque attached</small>
            </div>
            <div class="icon-wrapper">
              <i class="ti ti-building-bank"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Pending Verification Card (Highlighted) -->
      <div class="col-xl-3 col-sm-6">
        <div class="kpi-stat-card kpi-warning" data-status="pending" id="kpi-pending">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <span class="text-muted small fw-bold text-uppercase d-flex align-items-center mb-1" style="letter-spacing: 0.5px;">
                <span class="pulse-dot"></span> Pending Verification
              </span>
              <h2 class="mb-0 fw-bold text-warning" id="kpi_pending_count">0</h2>
              <small class="text-warning fw-semibold mt-1 d-block">Requires audit & approval</small>
            </div>
            <div class="icon-wrapper">
              <i class="ti ti-clock-check"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Verified Bank Details Card -->
      <div class="col-xl-3 col-sm-6">
        <div class="kpi-stat-card kpi-success" data-status="verified" id="kpi-verified">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <span class="text-muted small fw-bold text-uppercase d-block mb-1" style="letter-spacing: 0.5px;">Verified Details</span>
              <h2 class="mb-0 fw-bold text-success" id="kpi_verified_count">0</h2>
              <small class="text-success fw-semibold mt-1 d-block">Approved for disbursement</small>
            </div>
            <div class="icon-wrapper">
              <i class="ti ti-discount-check"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Rejected / Needs Fix Card -->
      <div class="col-xl-3 col-sm-6">
        <div class="kpi-stat-card kpi-danger" data-status="rejected" id="kpi-rejected">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <span class="text-muted small fw-bold text-uppercase d-block mb-1" style="letter-spacing: 0.5px;">Rejected / Inactive</span>
              <h2 class="mb-0 fw-bold text-danger" id="kpi_rejected_count">0</h2>
              <small class="text-danger fw-semibold mt-1 d-block">Flagged for re-upload</small>
            </div>
            <div class="icon-wrapper">
              <i class="ti ti-alert-triangle"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bulk Action Sticky Bar -->
    <div class="bulk-bar" id="bulkActionBar">
      <div class="d-flex align-items-center gap-3">
        <span class="badge bg-primary fs-14 px-3 py-2 rounded-pill"><span id="selectedRowsCount">0</span> Selected</span>
        <span class="fw-semibold">Batch operations on selected bank accounts</span>
      </div>
      <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-success btn-sm rounded-pill px-3 d-flex align-items-center gap-1" id="btnBulkVerify">
          <i class="ti ti-checks fs-16"></i> <span>Bulk Verify</span>
        </button>
        <button type="button" class="btn btn-danger btn-sm rounded-pill px-3 d-flex align-items-center gap-1" id="btnBulkReject">
          <i class="ti ti-x fs-16"></i> <span>Bulk Reject</span>
        </button>
        <button type="button" class="btn btn-outline-light btn-sm rounded-pill px-3" id="btnDeselectAll">
          <span>Cancel</span>
        </button>
      </div>
    </div>

    <!-- Filters & Tab Controls Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-3">
      <div class="card-body p-3 p-md-4">
        <div class="row g-3 align-items-center">
          
          <!-- Status Pill Navigation Tabs -->
          <div class="col-lg-6">
            <div class="status-pill-nav">
              <button type="button" class="status-pill-btn active" data-status="all">
                All Details <span class="pill-count" id="tab_count_all">0</span>
              </button>
              <button type="button" class="status-pill-btn" data-status="pending">
                <span class="pulse-dot"></span> Pending <span class="pill-count" id="tab_count_pending">0</span>
              </button>
              <button type="button" class="status-pill-btn" data-status="verified">
                Verified <span class="pill-count" id="tab_count_verified">0</span>
              </button>
              <button type="button" class="status-pill-btn" data-status="rejected">
                Rejected <span class="pill-count" id="tab_count_rejected">0</span>
              </button>
            </div>
          </div>

          <!-- Secondary Filters & Search -->
          <div class="col-lg-6">
            <div class="row g-2 justify-content-lg-end">
              <!-- Location Dropdown -->
              <div class="col-sm-5 col-md-4">
                <select class="form-select form-select-sm rounded-3 bg-light border-0" id="filterLocation">
                  <option value="all">All Locations</option>
                </select>
              </div>

              <!-- Document Attachment Filter -->
              <div class="col-sm-4 col-md-4">
                <select class="form-select form-select-sm rounded-3 bg-light border-0" id="filterDoc">
                  <option value="all">All Cheque Docs</option>
                  <option value="with_doc">With Document</option>
                  <option value="no_doc">Missing Document</option>
                </select>
              </div>

              <!-- Live Search Input -->
              <div class="col-sm-12 col-md-4">
                <div class="input-group input-group-sm">
                  <span class="input-group-text bg-light border-0 text-muted"><i class="ti ti-search"></i></span>
                  <input type="text" class="form-control bg-light border-0" id="searchKeyword" placeholder="Search ID, Name, Bank, IFSC...">
                  <button class="btn btn-light border-0" type="button" id="btnClearSearch" title="Clear Search" style="display:none;">
                    <i class="ti ti-x"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- Desktop Strip Header Row -->
    <div class="strips-header-wrapper" id="stripsHeader">
      <div class="strips-header">
        <div class="text-center">
          <input type="checkbox" class="form-check-input" id="checkSelectAll" title="Select All">
        </div>
        <div>Labour Details</div>
        <div>Bank Account Details</div>
        <div class="text-center">Cheque Doc</div>
        <div>Verify Status & Audit</div>
        <div class="text-center">Actions</div>
      </div>
    </div>

    <!-- Strip Items Container -->
    <div class="bank-strips-container" id="bankStripsContainer">
      <div class="text-center py-5 text-muted bg-white rounded-4 border">
        <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
        Loading labour bank verification records...
      </div>
    </div>

</div>

<!-- ================= MODALS ================= -->

<!-- 1. Document / Cheque Preview Modal -->
<div class="modal fade" id="docPreviewModal" tabindex="-1" aria-labelledby="docPreviewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content modal-content-custom">
      <div class="modal-header modal-header-custom d-flex align-items-center justify-content-between">
        <div>
          <h5 class="modal-title fw-bold text-dark mb-0" id="docPreviewModalLabel">Bank Document Preview</h5>
          <small class="text-muted" id="docPreviewSubtitle">Cancel Cheque / Passbook Attachment</small>
        </div>
        <div class="d-flex align-items-center gap-2">
          <a href="#" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3" id="btnDownloadDoc">
            <i class="ti ti-download me-1"></i> Open Original
          </a>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
      </div>
      <div class="modal-body p-4 bg-light d-flex align-items-center justify-content-center" id="docPreviewContainer" style="min-height: 400px; background-color: #f8fafc !important;">
        <!-- Content dynamically injected (Image or PDF) -->
      </div>
    </div>
  </div>
</div>

<!-- 2. Edit Bank Details & Verification Modal -->
<div class="modal fade" id="editBankModal" tabindex="-1" aria-labelledby="editBankModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content modal-content-custom">
      <div class="modal-header modal-header-custom d-flex align-items-center justify-content-between">
        <div>
          <h5 class="modal-title fw-bold text-dark mb-0" id="editBankModalLabel">Edit Bank & Verification Details</h5>
          <small class="text-muted" id="edit_labour_info_header">Labour ID: KRF-000 | Name: John Doe</small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="editBankForm" enctype="multipart/form-data">
        <input type="hidden" name="bank_id" id="edit_bank_id">
        <input type="hidden" name="labourid" id="edit_labourid">
        <input type="hidden" name="currencycode" value="INR">
        <div class="modal-body modal-body-custom">
          <div class="row g-3">
            
            <!-- Bank Name -->
            <div class="col-md-6">
              <label class="form-label small fw-bold text-muted">Bank Name <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text bg-light border-0"><i class="ti ti-building-bank"></i></span>
                <input type="text" class="form-control bg-light border-0" name="bankname" id="edit_bankname" required placeholder="e.g. State Bank of India, HDFC Bank">
              </div>
            </div>

            <!-- Account Number -->
            <div class="col-md-6">
              <label class="form-label small fw-bold text-muted">Account Number <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text bg-light border-0"><i class="ti ti-credit-card"></i></span>
                <input type="text" class="form-control bg-light border-0" name="accountnumber" id="edit_accountnumber" required placeholder="Enter bank account number">
              </div>
            </div>

            <!-- IFSC Code -->
            <div class="col-md-6">
              <label class="form-label small fw-bold text-muted">IFSC Code <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text bg-light border-0"><i class="ti ti-hash"></i></span>
                <input type="text" class="form-control bg-light border-0 text-uppercase" name="ifsccode" id="edit_ifsccode" required placeholder="e.g. SBIN0001234" maxlength="15">
              </div>
            </div>

            <!-- Verification Status Selection -->
            <div class="col-md-6">
              <label class="form-label small fw-bold text-muted">Verification Status <span class="text-danger">*</span></label>
              <select class="form-select bg-light border-0 fw-semibold" name="verifystatus" id="edit_verifystatus" required>
                <option value="Pending">🟡 Pending Verification</option>
                <option value="Verified">🟢 Verified & Approved</option>
                <option value="Rejected">🔴 Rejected / Needs Correction</option>
              </select>
            </div>

            <!-- Reason of Un-Verify Section (Visible when Pending is selected) -->
            <div class="col-md-12" id="edit_unverify_reason_container" style="display: none;">
              <div class="p-2.5 rounded-3 border" style="background-color: #fffdf5; border-color: #fde68a !important;">
                <label class="form-label small fw-bold text-dark mb-2 d-flex align-items-center gap-1">
                  <i class="ti ti-alert-triangle text-warning fs-15"></i> Reason of Un-Verify Bank Details:
                </label>
                <div class="d-flex flex-column gap-2 ms-1">
                  <div class="form-check form-check-inline m-0">
                    <input class="form-check-input" type="checkbox" id="edit_chk_mismatch_acc" value="Mismatch Account No">
                    <label class="form-check-label small fw-semibold text-dark cursor-pointer" for="edit_chk_mismatch_acc">
                      1. Mismatch Account No
                    </label>
                  </div>
                  <div class="form-check form-check-inline m-0">
                    <input class="form-check-input" type="checkbox" id="edit_chk_mismatch_ifsc" value="Mismatch IFSC Code">
                    <label class="form-check-label small fw-semibold text-dark cursor-pointer" for="edit_chk_mismatch_ifsc">
                      2. Mismatch IFSC Code
                    </label>
                  </div>
                  <div class="form-check form-check-inline m-0">
                    <input class="form-check-input" type="checkbox" id="edit_chk_mismatch_name" value="Mismatch Account holder Name">
                    <label class="form-check-label small fw-semibold text-dark cursor-pointer" for="edit_chk_mismatch_name">
                      3. Mismatch Account holder Name
                    </label>
                  </div>
                </div>

                <!-- Display Name Input (Visible & Required when Checkbox 3 is checked) -->
                <div class="mt-2.5 pt-2 border-top" id="edit_display_name_wrap" style="display: none; border-color: #fef08a !important;">
                  <label class="form-label small fw-bold text-dark mb-1" for="edit_display_name">
                    Display Name as per verified <span class="text-danger">*</span>
                  </label>
                  <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-0 text-muted"><i class="ti ti-id"></i></span>
                    <input type="text" class="form-control bg-white border-0 fw-semibold text-uppercase" id="edit_display_name" placeholder="Enter display name as per verified">
                  </div>
                </div>
              </div>
            </div>

            <!-- Verification Source Section (Visible when Verified & Approved is selected) -->
            <div class="col-md-12" id="edit_verify_source_container" style="display: none;">
              <div class="p-2.5 rounded-3 border" style="background-color: #f0fdf4; border-color: #bbf7d0 !important;">
                <label class="form-label small fw-bold text-success mb-2 d-flex align-items-center gap-1">
                  <i class="ti ti-shield-check fs-15 text-success"></i> What is the verification source? <span class="text-danger">*</span>
                </label>
                <div class="d-flex flex-row flex-wrap gap-4 ms-1">
                  <div class="form-check form-check-inline m-0">
                    <input class="form-check-input" type="radio" name="edit_verify_source" id="edit_source_online" value="Online/Bank">
                    <label class="form-check-label small fw-semibold text-dark cursor-pointer" for="edit_source_online">
                      1. Online/Bank
                    </label>
                  </div>
                  <div class="form-check form-check-inline m-0">
                    <input class="form-check-input" type="radio" name="edit_verify_source" id="edit_source_physical" value="Physical Document">
                    <label class="form-check-label small fw-semibold text-dark cursor-pointer" for="edit_source_physical">
                      2. Physical Document
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <!-- Cancel Cheque / Passbook File Upload & Preview -->
            <div class="col-md-12">
              <label class="form-label small fw-bold text-muted">Attach Cheque / Passbook Document (Optional / Replace)</label>
              <input type="file" class="form-control bg-light border-0" name="bankDoc" id="edit_bankDoc" accept="image/jpeg,image/png,image/webp,application/pdf">
              <small class="text-muted d-block mt-1">Accepted formats: JPG, PNG, WEBP, PDF (Max 5MB)</small>
              
              <!-- Document Preview / Status Container -->
              <div id="edit_current_doc_preview" class="mt-2"></div>
            </div>

          </div>
        </div>
        <div class="modal-footer modal-footer-custom d-flex justify-content-end gap-2">
          <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary rounded-pill px-4 d-flex align-items-center gap-2" id="btnSaveEditBank" style="background: var(--save-btn-gradient); border: none;">
            <i class="ti ti-device-floppy fs-16"></i> <span>Save & Update Status</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 3. Rejection Reason Modal -->
<div class="modal fade" id="rejectReasonModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content modal-content-custom">
      <div class="modal-header modal-header-custom d-flex align-items-center justify-content-between">
        <h5 class="modal-title fw-bold text-danger d-flex align-items-center gap-2 mb-0">
          <i class="ti ti-alert-circle fs-22"></i> Reject Bank Verification
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body modal-body-custom">
        <input type="hidden" id="reject_target_ids">
        <p class="text-muted mb-3" id="reject_confirm_msg">Are you sure you want to reject the selected bank verification?</p>
        <div>
          <label class="form-label small fw-bold text-muted">Rejection Reason / Note (Optional)</label>
          <textarea class="form-control bg-light border-0" id="reject_reason_text" rows="3" placeholder="e.g. Account number mismatch with cheque, IFSC invalid, blurred image..."></textarea>
        </div>
      </div>
      <div class="modal-footer modal-footer-custom d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger rounded-pill px-4" id="btnConfirmReject">
          <i class="ti ti-x me-1"></i> Confirm Rejection
        </button>
      </div>
    </div>
  </div>
</div>

<?php require('footer.php'); ?>

<script>
$(document).ready(function() {
  const routurl = $('#hidden_routurl').val() || '';
  const currentKey = $('body').data('key') || '';
  const currentLoginName = $('body').data('loginname') || 'Admin';
  const currentCompanyId = $('body').data('companyid') || '';
  const currentSubCompanyId = $('body').data('subcompanyid') || '';
  const currentProjectId = $('body').data('projectid') || '';

  let bankDataList = [];
  let currentActiveStatus = 'all'; // 'all', 'pending', 'verified', 'rejected'
  let selectedBankIds = new Set();

  // Initialize
  loadLocationDropdown();
  fetchBankVerificationData();

  // Load locations for filter dropdown
  function loadLocationDropdown() {
    $.getJSON(routurl + 'modal/hrm_laboursalarymodal.php?action=getallLocationList', function(res) {
      if (res && res.status === 'success' && Array.isArray(res.data)) {
        let opts = '<option value="all">All Locations</option>';
        res.data.forEach(function(loc) {
          opts += `<option value="${loc.LocationId}">${loc.LocationName}</option>`;
        });
        $('#filterLocation').html(opts);
      }
    });
  }

  // Fetch Bank Records and KPI summary
  function fetchBankVerificationData() {
    $('#bankStripsContainer').html(`
      <div class="text-center py-5 text-muted bg-white rounded-4 border">
        <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
        Loading labour bank verification records...
      </div>
    `);

    const locId = $('#filterLocation').val();
    const docStatus = $('#filterDoc').val();
    const keyword = $('#searchKeyword').val();

    $.ajax({
      url: routurl + 'modal/hrm_laboursalarymodal.php?action=GetLabourBankVerificationData',
      type: 'POST',
      data: {
        companyid: currentCompanyId,
        subcompanyid: currentSubCompanyId,
        projectid: currentProjectId,
        status: currentActiveStatus,
        locationid: locId,
        docstatus: docStatus,
        search: keyword
      },
      dataType: 'json',
      success: function(res) {
        if (res && res.status === 'success') {
          bankDataList = res.data || [];
          updateKPIStats(res.summary);
          renderBankStrips(bankDataList);
        } else {
          showErrorToast(res.message || 'Failed to fetch bank verification data.');
          $('#bankStripsContainer').html(`<div class="text-center py-4 text-danger bg-white rounded-4 border">Error loading data.</div>`);
        }
      },
      error: function(xhr, status, err) {
        console.error('Fetch error:', err);
        showErrorToast('Server connection error. Please try again.');
        $('#bankStripsContainer').html(`<div class="text-center py-4 text-danger bg-white rounded-4 border">Connection error while fetching records.</div>`);
      }
    });
  }

  // Update Top KPI Cards and Pill Counts
  function updateKPIStats(summary) {
    if (!summary) return;
    const total = summary.total || 0;
    const pending = summary.pending || 0;
    const verified = summary.verified || 0;
    const rejected = summary.rejected || 0;
    const withDoc = summary.with_doc || 0;

    $('#kpi_total_count').text(total);
    $('#kpi_doc_count').text(withDoc);
    $('#kpi_pending_count').text(pending);
    $('#kpi_verified_count').text(verified);
    $('#kpi_rejected_count').text(rejected);

    $('#tab_count_all').text(total);
    $('#tab_count_pending').text(pending);
    $('#tab_count_verified').text(verified);
    $('#tab_count_rejected').text(rejected);
  }

  // Render Data as Modern Responsive Strips
  function renderBankStrips(data) {
    selectedBankIds.clear();
    updateBulkActionBar();
    $('#checkSelectAll').prop('checked', false);

    if (!data || data.length === 0) {
      $('#bankStripsContainer').html(`
        <div class="text-center py-5 bg-white rounded-4 border">
          <div class="text-muted">
            <i class="ti ti-inbox fs-36 d-block mb-2 text-secondary"></i>
            <h6 class="fw-bold mb-1">No bank records found</h6>
            <p class="small text-muted mb-0">Try adjusting your filters or search keywords.</p>
          </div>
        </div>
      `);
      return;
    }

    let stripsHtml = '';
    data.forEach(function(item, idx) {
      const bankId = item.bank_id;
      const labourId = item.labourid || 'N/A';
      const fullName = item.fullname || 'Unknown';
      const mobile = item.MobileNo || '-';
      const location = item.location_display || 'N/A';
      const bankName = item.bankname || '-';
      const accountNo = item.accountnumber || '-';
      const ifsc = item.ifsccode || '-';
      const docPath = item.cancelchequepath ? item.cancelchequepath.trim() : '';
      const status = item.normalized_status || 'Pending';
      const verifyBy = item.verifyby || '';
      const verifyAt = item.verifyat ? moment(item.verifyat).format('DD MMM YYYY, hh:mm A') : '';
      const profilePic = item.profilepath ? (item.profilepath.startsWith('http') ? item.profilepath : '../' + item.profilepath) : '';
      const initial = fullName.charAt(0).toUpperCase();

      // Combined 1-Column: Verification Status & Audit Details
      let statusBadge = '';
      const remarkTxt = (item.verifyremark || '').trim();
      if (status.toLowerCase() === 'verified') {
        statusBadge = `<span class="badge-status badge-verified"><i class="ti ti-circle-check fs-14"></i> Verified${remarkTxt ? ' (' + remarkTxt + ')' : ''}</span>`;
      } else if (status.toLowerCase() === 'rejected') {
        statusBadge = `<span class="badge-status badge-rejected"><i class="ti ti-circle-x fs-14"></i> Rejected${remarkTxt ? ' (' + remarkTxt + ')' : ''}</span>`;
      } else {
        statusBadge = `<span class="badge-status badge-pending"><span class="pulse-dot"></span> Pending${remarkTxt ? ' (' + remarkTxt + ')' : ''}</span>`;
      }

      let auditTextHtml = '';
      if (verifyBy || verifyAt) {
        auditTextHtml = `
          <div class="mt-1" style="line-height: 1.25;">
            <div class="small text-dark fw-semibold" style="font-size: 11.5px;">
              <i class="ti ti-user-check text-primary me-1"></i>${verifyBy || 'Admin'}
            </div>
            <div class="text-muted" style="font-size: 10.5px;">
              ${verifyAt}
            </div>
          </div>
        `;
      } else {
        auditTextHtml = `<div class="text-muted mt-1" style="font-size: 11px;">Not yet audited</div>`;
      }

      // Document Thumbnail / Badge Logic
      let docHtml = '';
      if (docPath) {
        const fullDocUrl = docPath.startsWith('http') ? docPath : '../' + docPath;
        const isPdf = docPath.toLowerCase().endsWith('.pdf');
        
        if (isPdf) {
          docHtml = `
            <div class="doc-thumb-wrapper mx-auto text-danger" onclick="window.previewBankDoc('${fullDocUrl}', '${labourId} - ${fullName}', true)" title="Click to view PDF">
              <i class="ti ti-file-type-pdf fs-22"></i>
              <div class="doc-thumb-overlay"><i class="ti ti-eye"></i></div>
            </div>
          `;
        } else {
          docHtml = `
            <div class="doc-thumb-wrapper mx-auto" onclick="window.previewBankDoc('${fullDocUrl}', '${labourId} - ${fullName}', false)" title="Click to view Image">
              <img src="${fullDocUrl}" alt="Cheque" onerror="this.src='assets/img/icon.png'">
              <div class="doc-thumb-overlay"><i class="ti ti-zoom-in"></i></div>
            </div>
          `;
        }
      } else {
        docHtml = `<span class="doc-missing-badge"><i class="ti ti-file-x"></i> No Doc</span>`;
      }

      // Avatar
      let avatarHtml = '';
      if (profilePic) {
        avatarHtml = `<img src="${profilePic}" class="avatar-labour me-2.5" alt="Photo" onerror="this.onerror=null; this.src='assets/img/profiles/profile.jpg';">`;
      } else {
        avatarHtml = `<div class="avatar-initial me-2.5">${initial}</div>`;
      }

      // Actions HTML
      const actionsHtml = `
        <div class="d-flex align-items-center justify-content-center gap-1">
          ${status.toLowerCase() !== 'verified' ? `
            <button type="button" class="btn-action-icon btn-action-verify" title="Verify Bank Details" onclick="window.quickVerifyBank(${bankId}, '${labourId}')">
              <i class="ti ti-check"></i>
            </button>
          ` : ''}
          ${status.toLowerCase() !== 'rejected' ? `
            <button type="button" class="btn-action-icon btn-action-reject" title="Reject Bank Details" onclick="window.openRejectModal(${bankId}, '${labourId}')">
              <i class="ti ti-x"></i>
            </button>
          ` : ''}
          <button type="button" class="btn-action-icon btn-action-edit" title="Edit Bank Details" onclick="window.openEditBankModal(${bankId})">
            <i class="ti ti-edit"></i>
          </button>
        </div>
      `;

      stripsHtml += `
        <div class="bank-strip-item" data-bank-id="${bankId}" data-labour-id="${labourId}" id="strip_row_${bankId}">
          
          <!-- Column 1: Checkbox -->
          <div class="text-center d-flex align-items-center justify-content-center">
            <input type="checkbox" class="form-check-input row-select-checkbox m-0" value="${bankId}">
          </div>

          <!-- Column 2: Labour Details -->
          <div class="overflow-hidden">
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <span class="badge-id">${labourId}</span>
              <span class="fw-bold text-dark text-truncate" style="font-size: 13.5px;" title="${fullName}">${fullName}</span>
            </div>
            <div class="d-flex align-items-center gap-2 text-muted flex-wrap" style="font-size: 11px;">
              <span><i class="ti ti-phone me-1"></i>${mobile}</span>
              <span>•</span>
              <span><i class="ti ti-map-pin me-1"></i>${location}</span>
            </div>
          </div>

          <!-- Column 3: Combined Bank Details (1 Unified Column) -->
          <div class="pe-2">
            <div class="d-flex align-items-center gap-1.5 mb-1">
              <span class="text-primary fw-bold" style="font-size: 13px;">
                <i class="ti ti-building-bank me-1"></i>${bankName}
              </span>
            </div>
            <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
              <div class="d-flex align-items-center gap-1">
                <span class="small text-muted fw-semibold" style="font-size: 11px;">A/C:</span>
                <span class="font-monospace fw-bold text-dark" style="font-size: 12.5px; letter-spacing: 0.3px;">${accountNo}</span>
                <button type="button" class="btn-copy-code" onclick="window.copyToClipboard('${accountNo}', 'Account Number')" title="Copy Account Number">
                  <i class="ti ti-copy fs-13"></i>
                </button>
              </div>
              <div class="d-flex align-items-center gap-1">
                <span class="small text-muted fw-semibold" style="font-size: 11px;">IFSC:</span>
                <span class="badge bg-light text-dark font-monospace border px-1.5 py-0.5" style="font-size: 11px;">${ifsc}</span>
                <button type="button" class="btn-copy-code" onclick="window.copyToClipboard('${ifsc}', 'IFSC Code')" title="Copy IFSC">
                  <i class="ti ti-copy fs-13"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- Column 4: Cheque / Passbook Doc -->
          <div class="text-center">
            ${docHtml}
          </div>

          <!-- Column 5: Combined Verify Status & Audit (1 Unified Column) -->
          <div>
            ${statusBadge}
            ${auditTextHtml}
          </div>

          <!-- Column 6: Actions -->
          <div class="text-center">
            ${actionsHtml}
          </div>

        </div>
      `;
    });

    $('#bankStripsContainer').html(stripsHtml);
  }

  // Handle Tab Pill Clicks
  $('.status-pill-btn').on('click', function() {
    $('.status-pill-btn').removeClass('active');
    $(this).addClass('active');

    currentActiveStatus = $(this).data('status');
    // Sync Top KPI active class
    $('.kpi-stat-card').removeClass('active-kpi');
    $(`#kpi-${currentActiveStatus}`).addClass('active-kpi');

    fetchBankVerificationData();
  });

  // Handle KPI Stat Card Clicks
  $('.kpi-stat-card').on('click', function() {
    $('.kpi-stat-card').removeClass('active-kpi');
    $(this).addClass('active-kpi');

    currentActiveStatus = $(this).data('status');
    // Sync Status Pill Tab
    $('.status-pill-btn').removeClass('active');
    $(`.status-pill-btn[data-status="${currentActiveStatus}"]`).addClass('active');

    fetchBankVerificationData();
  });

  // Filter change handlers
  $('#filterLocation, #filterDoc').on('change', function() {
    fetchBankVerificationData();
  });

  // Live Search with debounce
  let searchTimer = null;
  $('#searchKeyword').on('input', function() {
    const val = $(this).val().trim();
    $('#btnClearSearch').toggle(val.length > 0);
    clearTimeout(searchTimer);
    searchTimer = setTimeout(function() {
      fetchBankVerificationData();
    }, 350);
  });

  $('#btnClearSearch').on('click', function() {
    $('#searchKeyword').val('');
    $(this).hide();
    fetchBankVerificationData();
  });

  $('#btnRefreshList').on('click', function() {
    fetchBankVerificationData();
  });

  // Checkbox Selection & Bulk Operations
  $('#checkSelectAll').on('change', function() {
    const isChecked = $(this).is(':checked');
    $('.row-select-checkbox').prop('checked', isChecked);
    $('.bank-strip-item').toggleClass('item-selected', isChecked);
    selectedBankIds.clear();
    if (isChecked) {
      $('.row-select-checkbox').each(function() {
        selectedBankIds.add(parseInt($(this).val()));
      });
    }
    updateBulkActionBar();
  });

  $(document).on('change', '.row-select-checkbox', function() {
    const val = parseInt($(this).val());
    const isChecked = $(this).is(':checked');
    $(this).closest('.bank-strip-item').toggleClass('item-selected', isChecked);

    if (isChecked) {
      selectedBankIds.add(val);
    } else {
      selectedBankIds.delete(val);
      $('#checkSelectAll').prop('checked', false);
    }
    updateBulkActionBar();
  });

  function updateBulkActionBar() {
    const count = selectedBankIds.size;
    $('#selectedRowsCount').text(count);
    if (count > 0) {
      $('#bulkActionBar').css('display', 'flex');
    } else {
      $('#bulkActionBar').hide();
    }
  }

  $('#btnDeselectAll').on('click', function() {
    selectedBankIds.clear();
    $('.row-select-checkbox, #checkSelectAll').prop('checked', false);
    $('.bank-strip-item').removeClass('item-selected');
    updateBulkActionBar();
  });

  // Bulk Verify Action
  $('#btnBulkVerify').on('click', function() {
    if (selectedBankIds.size === 0) return;

    Swal.fire({
      title: 'Bulk Verify Bank Details?',
      html: `
        <div class="text-muted small mb-3">Mark <strong>${selectedBankIds.size}</strong> bank accounts as Verified &amp; Approved.</div>
        <div class="text-start p-3 rounded-3" style="background:#f0fdf4; border:1px solid #bbf7d0;">
          <label class="form-label small fw-bold text-success mb-2 d-block">
            <i class="ti ti-shield-check me-1"></i> What is the verification source? <span class="text-danger">*</span>
          </label>
          <div class="d-flex flex-column gap-2">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="swal_bulk_verify_source" id="swal_bulk_online" value="Online/Bank" checked>
              <label class="form-check-label small fw-semibold cursor-pointer text-dark" for="swal_bulk_online">
                1. Online/Bank
              </label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="swal_bulk_verify_source" id="swal_bulk_physical" value="Physical Document">
              <label class="form-check-label small fw-semibold cursor-pointer text-dark" for="swal_bulk_physical">
                2. Physical Document
              </label>
            </div>
          </div>
        </div>
      `,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#10b981',
      cancelButtonColor: '#64748b',
      confirmButtonText: '<i class="ti ti-checks me-1"></i> Yes, Verify All',
      cancelButtonText: 'Cancel',
      preConfirm: () => {
        const source = $('input[name="swal_bulk_verify_source"]:checked').val();
        if (!source) {
          Swal.showValidationMessage('Please select a verification source');
          return false;
        }
        return source;
      }
    }).then((result) => {
      if (result.isConfirmed && result.value) {
        performVerificationUpdate(Array.from(selectedBankIds), 'Verified', result.value);
      }
    });
  });

  // Bulk Reject Action
  $('#btnBulkReject').on('click', function() {
    if (selectedBankIds.size === 0) return;
    $('#reject_target_ids').val(Array.from(selectedBankIds).join(','));
    $('#reject_confirm_msg').text(`Are you sure you want to reject ${selectedBankIds.size} selected bank verifications?`);
    $('#reject_reason_text').val('');
    $('#rejectReasonModal').modal('show');
  });

  // Global Quick Verify function
  window.quickVerifyBank = function(bankId, labourId) {
    Swal.fire({
      title: 'Verify Bank Details?',
      html: `
        <div class="text-muted small mb-3">Mark bank details for Labour ID <strong>${labourId}</strong> as Verified &amp; Approved.</div>
        <div class="text-start p-3 rounded-3" style="background:#f0fdf4; border:1px solid #bbf7d0;">
          <label class="form-label small fw-bold text-success mb-2 d-block">
            <i class="ti ti-shield-check me-1"></i> What is the verification source? <span class="text-danger">*</span>
          </label>
          <div class="d-flex flex-column gap-2">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="swal_quick_verify_source" id="swal_quick_online" value="Online/Bank" checked>
              <label class="form-check-label small fw-semibold cursor-pointer text-dark" for="swal_quick_online">
                1. Online/Bank
              </label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="swal_quick_verify_source" id="swal_quick_physical" value="Physical Document">
              <label class="form-check-label small fw-semibold cursor-pointer text-dark" for="swal_quick_physical">
                2. Physical Document
              </label>
            </div>
          </div>
        </div>
      `,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#10b981',
      cancelButtonColor: '#64748b',
      confirmButtonText: '<i class="ti ti-check me-1"></i> Yes, Verify',
      cancelButtonText: 'Cancel',
      preConfirm: () => {
        const source = $('input[name="swal_quick_verify_source"]:checked').val();
        if (!source) {
          Swal.showValidationMessage('Please select a verification source');
          return false;
        }
        return source;
      }
    }).then((result) => {
      if (result.isConfirmed && result.value) {
        performVerificationUpdate([bankId], 'Verified', result.value);
      }
    });
  };

  // Global Open Reject Modal function
  window.openRejectModal = function(bankId, labourId) {
    $('#reject_target_ids').val(bankId);
    $('#reject_confirm_msg').text(`Are you sure you want to reject bank verification for Labour ID ${labourId}?`);
    $('#reject_reason_text').val('');
    $('#rejectReasonModal').modal('show');
  };

  // Confirm Reject Button Handler
  $('#btnConfirmReject').on('click', function() {
    const rawIds = $('#reject_target_ids').val();
    const reason = $('#reject_reason_text').val().trim();
    if (!rawIds) return;

    const ids = rawIds.split(',').map(s => parseInt(s.trim()));
    $('#rejectReasonModal').modal('hide');
    performVerificationUpdate(ids, 'Rejected', reason);
  });

  // API Call to Update Verification Status
  function performVerificationUpdate(ids, status, reason) {
    Swal.fire({
      title: 'Updating...',
      text: 'Please wait while verification status is being updated.',
      allowOutsideClick: false,
      didOpen: () => { Swal.showLoading(); }
    });

    $.ajax({
      url: routurl + 'modal/hrm_laboursalarymodal.php?action=UpdateLabourBankVerifyStatus',
      type: 'POST',
      data: {
        ids: ids,
        verifystatus: status,
        verifyby: currentLoginName,
        reason: reason
      },
      dataType: 'json',
      success: function(res) {
        Swal.close();
        if (res && res.status === 'success') {
          showSuccessToast(res.message || 'Status updated successfully.');
          fetchBankVerificationData();
        } else {
          showErrorToast(res.message || 'Failed to update status.');
        }
      },
      error: function(xhr, st, err) {
        Swal.close();
        showErrorToast('Server error while updating verification status.');
      }
    });
  }

  // Toggle un-verify reason container / verification source on status change
  $('#edit_verifystatus').on('change', function() {
    const val = $(this).val();
    if (val === 'Pending') {
      $('#edit_unverify_reason_container').slideDown(200);
      $('#edit_verify_source_container').slideUp(200);
    } else if (val === 'Verified') {
      $('#edit_unverify_reason_container').slideUp(200);
      $('#edit_verify_source_container').slideDown(200);
    } else {
      $('#edit_unverify_reason_container').slideUp(200);
      $('#edit_verify_source_container').slideUp(200);
    }
  });

  // Toggle display name when Checkbox 3 is toggled
  $('#edit_chk_mismatch_name').on('change', function() {
    if ($(this).is(':checked')) {
      $('#edit_display_name_wrap').slideDown(200);
      $('#edit_display_name').focus();
    } else {
      $('#edit_display_name_wrap').slideUp(200);
    }
  });

  // Global Open Edit Modal
  window.openEditBankModal = function(bankId) {
    const item = bankDataList.find(b => parseInt(b.bank_id) === parseInt(bankId));
    if (!item) return;

    $('#edit_bank_id').val(item.bank_id);
    $('#edit_labourid').val(item.labourid);
    $('#edit_bankname').val(item.bankname || '');
    $('#edit_accountnumber').val(item.accountnumber || '');
    $('#edit_ifsccode').val(item.ifsccode || '');
    const curStatus = item.normalized_status || 'Pending';
    $('#edit_verifystatus').val(curStatus);
    $('#edit_bankDoc').val('');

    // Reset un-verify reason fields
    $('#edit_chk_mismatch_acc').prop('checked', false);
    $('#edit_chk_mismatch_ifsc').prop('checked', false);
    $('#edit_chk_mismatch_name').prop('checked', false);
    $('#edit_display_name').val('');
    $('#edit_display_name_wrap').hide();

    // Reset verify source fields
    $('input[name="edit_verify_source"]').prop('checked', false);

    const curRemark = String(item.verifyremark || '');
    if (curRemark) {
      if (curRemark.indexOf('Online/Bank') !== -1) {
        $('#edit_source_online').prop('checked', true);
      } else if (curRemark.indexOf('Physical Document') !== -1) {
        $('#edit_source_physical').prop('checked', true);
      }
      if (curRemark.indexOf('Mismatch Account No') !== -1) {
        $('#edit_chk_mismatch_acc').prop('checked', true);
      }
      if (curRemark.indexOf('Mismatch IFSC Code') !== -1) {
        $('#edit_chk_mismatch_ifsc').prop('checked', true);
      }
      if (curRemark.indexOf('Mismatch Account holder Name') !== -1) {
        $('#edit_chk_mismatch_name').prop('checked', true);
        $('#edit_display_name_wrap').show();
        const matchName = curRemark.match(/Mismatch Account holder Name\s*\(([^)]+)\)/i);
        if (matchName && matchName[1]) {
          $('#edit_display_name').val(matchName[1].trim());
        }
      }
    }

    if (curStatus.toLowerCase() === 'pending') {
      $('#edit_unverify_reason_container').show();
      $('#edit_verify_source_container').hide();
    } else if (curStatus.toLowerCase() === 'verified') {
      $('#edit_unverify_reason_container').hide();
      $('#edit_verify_source_container').show();
      if (!$('input[name="edit_verify_source"]:checked').val()) {
        $('#edit_source_online').prop('checked', true);
      }
    } else {
      $('#edit_unverify_reason_container').hide();
      $('#edit_verify_source_container').hide();
    }

    $('#edit_labour_info_header').text(`Labour ID: ${item.labourid} | Name: ${item.fullname} | Location: ${item.location_display}`);
    $('#edit_avatar_initial').text(item.fullname.charAt(0).toUpperCase());

    // Check if document file exists
    if (item.cancelchequepath && item.cancelchequepath.trim() !== '') {
      const docUrl = item.cancelchequepath.startsWith('http') ? item.cancelchequepath : '../' + item.cancelchequepath;
      const isPdf = item.cancelchequepath.toLowerCase().endsWith('.pdf');
      const filename = item.cancelchequepath.split('/').pop();

      $('#edit_current_doc_preview').html(`
        <div class="d-flex align-items-center justify-content-between p-2.5 px-3 mt-2 rounded-3 border" style="background-color: #f8fafc; border-color: #cbd5e1 !important;">
          <div class="d-flex align-items-center gap-2.5 overflow-hidden">
            <span class="p-2 rounded-2 ${isPdf ? 'bg-danger-subtle text-danger' : 'bg-primary-subtle text-primary'}">
              <i class="${isPdf ? 'ti ti-file-type-pdf' : 'ti ti-file-check'} fs-18"></i>
            </span>
            <div class="text-truncate">
              <span class="small fw-bold text-dark d-block text-truncate" style="max-width: 260px;">${filename}</span>
              <small class="text-success fw-semibold" style="font-size: 11px;"><i class="ti ti-check me-1"></i>Document attached</small>
            </div>
          </div>
          <div class="d-flex align-items-center gap-2 ms-2">
            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 d-flex align-items-center gap-1 shadow-sm" onclick="window.previewBankDoc('${docUrl}', '${item.labourid} - ${item.fullname}', ${isPdf})">
              <i class="ti ti-eye fs-14"></i> <span>Preview Document</span>
            </button>
          </div>
        </div>
      `);
    } else {
      $('#edit_current_doc_preview').html(`
        <div class="small text-muted mt-2 d-flex align-items-center gap-1" style="font-size: 12.5px;">
          <i class="ti ti-info-circle fs-15 text-muted"></i> No document currently attached.
        </div>
      `);
    }

    $('#editBankModal').modal('show');
  };

  // Submit Edit Form Handler
  $('#editBankForm').on('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    const vStat = $('#edit_verifystatus').val();
    let verifyRemark = '';
    if (vStat === 'Verified') {
      const vSource = $('input[name="edit_verify_source"]:checked').val();
      if (!vSource) {
        showErrorToast('Please select a Verification Source (Online/Bank or Physical Document).');
        $('#edit_source_online').focus();
        return;
      }
      verifyRemark = vSource;
    } else if (vStat === 'Pending') {
      const isMismatchAcc = $('#edit_chk_mismatch_acc').is(':checked');
      const isMismatchIfsc = $('#edit_chk_mismatch_ifsc').is(':checked');
      const isMismatchName = $('#edit_chk_mismatch_name').is(':checked');

      if (!isMismatchAcc && !isMismatchIfsc && !isMismatchName) {
        showErrorToast('Please select at least 1 reason for Un-Verified Bank Details.');
        $('#edit_chk_mismatch_acc').focus();
        return;
      }

      const remarks = [];
      if (isMismatchAcc) {
        remarks.push('Mismatch Account No');
      }
      if (isMismatchIfsc) {
        remarks.push('Mismatch IFSC Code');
      }
      if (isMismatchName) {
        const dName = $('#edit_display_name').val().trim();
        if (!dName) {
          showErrorToast('Please enter Display Name as per verified when Mismatch Account holder Name is selected.');
          $('#edit_display_name').focus();
          return;
        }
        remarks.push(`Mismatch Account holder Name (${dName})`);
      }
      verifyRemark = remarks.join(', ');
    }

    formData.append('verifyremark', verifyRemark);
    formData.append('verifyby', currentLoginName);
    formData.append('companyid', currentCompanyId);
    formData.append('subcompanyid', currentSubCompanyId);
    formData.append('projectid', currentProjectId);

    $('#btnSaveEditBank').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

    $.ajax({
      url: routurl + 'modal/hrm_laboursalarymodal.php?action=SaveLabourBankDetailsFromVerification',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      dataType: 'json',
      success: function(res) {
        $('#btnSaveEditBank').prop('disabled', false).html('<i class="ti ti-device-floppy fs-16"></i> <span>Save & Update Status</span>');
        if (res && res.status === 'success') {
          $('#editBankModal').modal('hide');
          showSuccessToast(res.message || 'Bank details updated successfully.');
          fetchBankVerificationData();
        } else {
          showErrorToast(res.message || 'Failed to update bank details.');
        }
      },
      error: function() {
        $('#btnSaveEditBank').prop('disabled', false).html('<i class="ti ti-device-floppy fs-16"></i> <span>Save & Update Status</span>');
        showErrorToast('Server error while saving bank details.');
      }
    });
  });

  // Global Document Preview Function (Opens cleanly on top of any open modal)
  window.previewBankDoc = function(url, title, isPdf) {
    $('#docPreviewSubtitle').text(title);
    $('#btnDownloadDoc').attr('href', url);

    if (isPdf) {
      $('#docPreviewContainer').html(`<iframe src="${url}#toolbar=0" title="PDF Preview"></iframe>`);
    } else {
      $('#docPreviewContainer').html(`<img src="${url}" alt="Cancel Cheque" class="img-fluid">`);
    }

    $('#docPreviewModal').modal('show');
  };

  // Stacked Modal Management: Ensure preview modal is always above the edit modal and backdrop
  $(document).on('show.bs.modal', '#docPreviewModal', function () {
    const isEditOpen = $('#editBankModal').hasClass('show');
    if (isEditOpen) {
      $('#docPreviewModal').css('z-index', 1070);
      setTimeout(function() {
        $('.modal-backdrop').not('.modal-backdrop-preview').last().addClass('modal-backdrop-preview').css('z-index', 1065);
      }, 0);
    }
  });

  $(document).on('hidden.bs.modal', '#docPreviewModal', function () {
    $('.modal-backdrop-preview').remove();
    if ($('#editBankModal').hasClass('show')) {
      $('body').addClass('modal-open');
    }
  });

  // Global Copy to Clipboard Helper
  window.copyToClipboard = function(text, label) {
    if (!text || text === '-') return;
    navigator.clipboard.writeText(text).then(function() {
      iziToast.success({
        title: 'Copied!',
        message: `${label} copied to clipboard`,
        position: 'topRight',
        timeout: 2000
      });
    }).catch(function() {
      showErrorToast('Failed to copy to clipboard.');
    });
  };

  // Export to Excel handler (All details in separate individual columns)
  $('#btnExportExcel').on('click', function() {
    if (!bankDataList || bankDataList.length === 0) {
      showErrorToast('No data available to export.');
      return;
    }

    try {
      const workbook = new ExcelJS.Workbook();
      const worksheet = workbook.addWorksheet('Labour Bank Details');

      // Setup Headers with separate dedicated columns for each attribute
      worksheet.columns = [
        { header: 'S.No', key: 'sno', width: 8 },
        { header: 'Labour ID', key: 'labourid', width: 16 },
        { header: 'Labour Name', key: 'name', width: 28 },
        { header: 'Mobile Number', key: 'mobile', width: 18 },
        { header: 'Work Location', key: 'location', width: 22 },
        { header: 'Bank Name', key: 'bankname', width: 30 },
        { header: 'Account Number', key: 'accountno', width: 26 },
        { header: 'IFSC Code', key: 'ifsc', width: 18 },
        { header: 'Currency', key: 'currency', width: 12 },
        { header: 'Cheque Attached', key: 'hasdoc', width: 18 },
        { header: 'Verification Status', key: 'status', width: 22 },
        { header: 'Verified By', key: 'verifyby', width: 20 },
        { header: 'Verified Date & Time', key: 'verifyat', width: 24 }
      ];

      // Format Header Row
      const headerRow = worksheet.getRow(1);
      headerRow.font = { bold: true, color: { argb: 'FFFFFFFF' } };
      headerRow.fill = {
        type: 'pattern',
        pattern: 'solid',
        fgColor: { argb: 'FF1E293B' }
      };
      headerRow.alignment = { vertical: 'middle', horizontal: 'center' };

      // Add Data Rows
      bankDataList.forEach((item, idx) => {
        const row = worksheet.addRow({
          sno: idx + 1,
          labourid: item.labourid || '',
          name: item.fullname || '',
          mobile: item.MobileNo || '',
          location: item.location_display || '',
          bankname: item.bankname || '',
          accountno: item.accountnumber ? String(item.accountnumber) : '',
          ifsc: item.ifsccode || '',
          currency: item.currencycode || 'INR',
          hasdoc: item.cancelchequepath ? 'Yes' : 'No',
          status: item.normalized_status || 'Pending',
          verifyby: item.verifyby || '',
          verifyat: item.verifyat ? moment(item.verifyat).format('DD-MM-YYYY HH:mm:ss') : ''
        });

        // Ensure Account Number is treated as text so Excel won't format as scientific notation
        row.getCell('accountno').numFmt = '@';
      });

      // Download file
      workbook.xlsx.writeBuffer().then(function(buffer) {
        const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `Labour_Bank_Verification_${moment().format('YYYYMMDD_HHmmss')}.xlsx`;
        link.click();
      });

      showSuccessToast('Excel file exported successfully with separate columns.');
    } catch (e) {
      console.error('Excel export error:', e);
      showErrorToast('Failed to export Excel.');
    }
  });

  // Export to CSV handler (All details in separate individual columns)
  $('#btnExportCSV').on('click', function() {
    if (!bankDataList || bankDataList.length === 0) {
      showErrorToast('No data available to export.');
      return;
    }

    try {
      const headers = [
        'S.No',
        'Labour ID',
        'Labour Name',
        'Mobile Number',
        'Work Location',
        'Bank Name',
        'Account Number',
        'IFSC Code',
        'Currency',
        'Cheque Attached',
        'Verification Status',
        'Verified By',
        'Verified Date & Time'
      ];

      const csvRows = [];
      csvRows.push(headers.map(h => `"${h.replace(/"/g, '""')}"`).join(','));

      bankDataList.forEach((item, idx) => {
        const row = [
          idx + 1,
          item.labourid || '',
          item.fullname || '',
          item.MobileNo || '',
          item.location_display || '',
          item.bankname || '',
          `\t${item.accountnumber || ''}`, // Tab prefix prevents Excel from scientific notation in CSV
          item.ifsccode || '',
          item.currencycode || 'INR',
          item.cancelchequepath ? 'Yes' : 'No',
          item.normalized_status || 'Pending',
          item.verifyby || '',
          item.verifyat ? moment(item.verifyat).format('DD-MM-YYYY HH:mm:ss') : ''
        ];
        csvRows.push(row.map(val => `"${String(val).replace(/"/g, '""')}"`).join(','));
      });

      const csvContent = "data:text/csv;charset=utf-8,\uFEFF" + csvRows.join('\r\n');
      const encodedUri = encodeURI(csvContent);
      const link = document.createElement('a');
      link.setAttribute('href', encodedUri);
      link.setAttribute('download', `Labour_Bank_Verification_${moment().format('YYYYMMDD_HHmmss')}.csv`);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);

      showSuccessToast('CSV file exported successfully with separate columns.');
    } catch (e) {
      console.error('CSV export error:', e);
      showErrorToast('Failed to export CSV.');
    }
  });

  // Notification Helpers
  function showSuccessToast(msg) {
    iziToast.success({
      title: 'Success',
      message: msg,
      position: 'topRight',
      timeout: 3000
    });
  }

  function showErrorToast(msg) {
    iziToast.error({
      title: 'Error',
      message: msg,
      position: 'topRight',
      timeout: 3500
    });
  }

});
</script>
