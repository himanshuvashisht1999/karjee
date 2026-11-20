<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Kolkata');

// -------------------------------------------------------------
// Check Session & Auth
// -------------------------------------------------------------
if (!isset($_SESSION['sessionid']) || $_SESSION['sessionid'] !== session_id()) {
    $protocol = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';
    $rooturl = $protocol . '://' . $_SERVER['HTTP_HOST'];
    $prefix = ($_SERVER['HTTP_HOST'] == "localhost") ? $rooturl . "/krf_amanaccounting/" : $rooturl . "/";
    $requested_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    echo "<script>
        try {
            if (" . json_encode($requested_url) . " && !" . json_encode($requested_url) . ".includes('loginCell') && !" . json_encode($requested_url) . ".includes('logout')) {
                localStorage.setItem('last_active_module_url', " . json_encode($requested_url) . ");
            }
        } catch(e){}
        top.location.href = '" . $prefix . "loginCell/index.php';
    </script>";
    exit;
}

if (isset($_GET['k'])) {
    require('../header.php');
} else {
    require('../connection.php');
}

$loginroleid = isset($loginroleid) ? (int)$loginroleid : (int)($_SESSION['loginroleid'] ?? 4);
$loginid     = $_SESSION['loginid'] ?? 'Admin';
$projectid   = (int)($_SESSION['projectid'] ?? 17);
if ($projectid <= 0) $projectid = 17;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Open Sale Trays Register & Return Hub | Karjee Farm</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

  <!-- Google Fonts & Font Awesome -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Module Stylesheets -->
  <link rel="stylesheet" href="assets/opensale_booking.css?v=<?= time() ?>">
  <link rel="stylesheet" href="assets/opensale_register.css?v=<?= time() ?>">
</head>
<body>

<div class="osr-container">

  <!-- Top Hero Header -->
  <div class="osr-hero">
    <div class="osr-hero-left">
      <h1>
        <i class="fa fa-boxes-packing" style="color: #38bdf8;"></i>
        Open Sale Trays Register & Return
      </h1>
      <p>
        Monitor all plant trays shifted from polyhouses to Open Sale (Sargawa, Karjee, Mansoon). 
        Easily track retention age and return unsold trays back to polyhouses within 2–3 days.
      </p>
    </div>
    <div class="osr-hero-actions">
      <a href="index.php" class="btn-osr-nav" style="background:#10b981; border-color:#059669; color:#fff;">
        <i class="fa fa-cash-register"></i> + New POS Sale
      </a>
      <a href="invoices.php" class="btn-osr-nav">
        <i class="fa fa-file-invoice-dollar"></i> Invoices Register
      </a>
      <a href="../krf-orderandsales/verifiedtraymovement/index.php" class="btn-osr-nav">
        <i class="fa fa-qrcode"></i> Scan & Move Trays
      </a>
      <a href="../krf-orderandsales/opensale_stock_review.php" class="btn-osr-nav">
        <i class="fa fa-boxes-stacked"></i> Stock Review
      </a>
    </div>
  </div>

  <!-- KPI Metric Cards Grid -->
  <div class="osr-kpi-grid">
    <!-- Active Open Sale Trays -->
    <div class="osr-kpi-card">
      <div class="osr-kpi-icon" style="background:#e0e7ff; color:#4338ca;">
        <i class="fa fa-boxes-stacked"></i>
      </div>
      <div class="osr-kpi-details">
        <div class="osr-kpi-title">Active In Open Sale</div>
        <div class="osr-kpi-val" id="kpiActiveTrays">--</div>
        <div class="osr-kpi-sub" id="kpiActivePlants">-- Plants</div>
      </div>
    </div>

    <!-- Retained > 2 Days (Return Window) -->
    <div class="osr-kpi-card" style="border-left: 4px solid #f59e0b;">
      <div class="osr-kpi-icon" style="background:#fef3c7; color:#d97706;">
        <i class="fa fa-clock-rotate-left"></i>
      </div>
      <div class="osr-kpi-details">
        <div class="osr-kpi-title">In Open Sale &gt; 2 Days</div>
        <div class="osr-kpi-val" id="kpiDueForReturn" style="color:#d97706;">--</div>
        <div class="osr-kpi-sub">Eligible / recommended to return</div>
      </div>
    </div>

    <!-- Retained > 5 Days (High Alert) -->
    <div class="osr-kpi-card" style="border-left: 4px solid #ef4444;">
      <div class="osr-kpi-icon" style="background:#fee2e2; color:#dc2626;">
        <i class="fa fa-triangle-exclamation"></i>
      </div>
      <div class="osr-kpi-details">
        <div class="osr-kpi-title">Aged &gt; 5 Days</div>
        <div class="osr-kpi-val" id="kpiAgedTrays" style="color:#dc2626;">--</div>
        <div class="osr-kpi-sub">Needs immediate check/return</div>
      </div>
    </div>

    <!-- Returned Back to Polyhouse -->
    <div class="osr-kpi-card" style="border-left: 4px solid #10b981;">
      <div class="osr-kpi-icon" style="background:#d1fae5; color:#059669;">
        <i class="fa fa-rotate-left"></i>
      </div>
      <div class="osr-kpi-details">
        <div class="osr-kpi-title">Returned To Polyhouse</div>
        <div class="osr-kpi-val" id="kpiReturnedTrays" style="color:#059669;">--</div>
        <div class="osr-kpi-sub">Restocked to PH beds</div>
      </div>
    </div>
  </div>

  <!-- Center Filter Strip -->
  <div class="osr-center-strip" id="centerFilterStrip">
    <button type="button" class="osr-center-chip active" data-center="ALL">
      <i class="fa fa-layer-group"></i> All Open Sale Centers
    </button>
    <button type="button" class="osr-center-chip" data-center="Sargawa">
      <i class="fa fa-location-dot"></i> Sargawa Center
    </button>
    <button type="button" class="osr-center-chip" data-center="Karjee">
      <i class="fa fa-location-dot"></i> Karjee Center
    </button>
    <button type="button" class="osr-center-chip" data-center="Mansoon">
      <i class="fa fa-location-dot"></i> Mansoon Agro Center
    </button>
  </div>

  <!-- Filter & Control Toolbar -->
  <div class="osr-filter-card">
    <div class="osr-filter-grid">
      <!-- Search Input -->
      <div class="osr-filter-group" style="min-width: 250px; flex: 2;">
        <label for="searchInput"><i class="fa fa-magnifying-glass"></i> Search Barcode / Crop / Variety</label>
        <input type="text" id="searchInput" class="osr-input" placeholder="Type tray barcode, crop name, variety, remark..." autocomplete="off">
      </div>

      <!-- Status Filter -->
      <div class="osr-filter-group">
        <label for="statusFilter"><i class="fa fa-tag"></i> Stock Status</label>
        <select id="statusFilter" class="osr-select">
          <option value="OpenSale" selected>Active Open Sale (Available)</option>
          <option value="Returned">Returned to Polyhouse</option>
          <option value="Sold">Sold / Dispatched</option>
          <option value="ALL">All Records</option>
        </select>
      </div>

      <!-- Retention Age Filter -->
      <div class="osr-filter-group">
        <label for="ageFilter"><i class="fa fa-hourglass-half"></i> Retention Age</label>
        <select id="ageFilter" class="osr-select">
          <option value="ALL" selected>All Days</option>
          <option value="0-2">0 – 2 Days (Fresh Moved)</option>
          <option value="3-5">3 – 5 Days (Recommended Return)</option>
          <option value=">5">&gt; 5 Days (Aged Stock)</option>
        </select>
      </div>

      <!-- Action Buttons -->
      <div class="osr-filter-actions">
        <button type="button" id="btnApplyFilter" class="btn-osr-primary">
          <i class="fa fa-filter"></i> Apply
        </button>
        <button type="button" id="btnResetFilter" class="btn-osr-outline">
          <i class="fa fa-rotate-right"></i> Reset
        </button>
        <button type="button" id="btnExportCSV" class="btn-osr-outline" title="Export current list to CSV">
          <i class="fa fa-file-excel" style="color:#10b981;"></i> Export CSV
        </button>
      </div>
    </div>
  </div>

  <!-- Register Data Table Card -->
  <div class="osr-table-card">
    <div class="osr-table-head">
      <div class="osr-table-title">
        <i class="fa fa-table-list" style="color:var(--primary);"></i>
        <span>Tray Inventory Movement Details</span>
        <span class="osr-table-count" id="tableCountBadge">Loading...</span>
      </div>
      <div>
        <button type="button" id="btnRefreshTable" class="btn-osr-outline" style="padding: 6px 12px; font-size: 0.8rem;">
          <i class="fa fa-sync-alt"></i> Refresh
        </button>
      </div>
    </div>

    <!-- Table Responsive Wrapper -->
    <div class="osr-table-responsive">
      <table class="osr-table" id="traysTable">
        <thead>
          <tr>
            <th style="width: 40px; text-align: center;">
              <input type="checkbox" id="selectAllCheckbox" title="Select / Deselect all visible trays">
            </th>
            <th>Tray Barcode</th>
            <th>Center Location</th>
            <th>Crop & Variety</th>
            <th style="text-align: right;">Plants</th>
            <th>Origin (PH & Bed)</th>
            <th>Moved At</th>
            <th style="text-align: center;">Age In Open Sale</th>
            <th style="text-align: center;">Status</th>
            <th style="text-align: center;">Actions</th>
          </tr>
        </thead>
        <tbody id="traysTableBody">
          <tr>
            <td colspan="10" style="text-align: center; padding: 2.5rem; color: #64748b;">
              <i class="fa fa-circle-notch fa-spin fa-2x" style="color: var(--primary); margin-bottom: 0.75rem; display:block;"></i>
              Loading open sale tray register...
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination Footer -->
    <div class="osr-pagination" id="tablePagination">
      <div class="osr-page-info" id="paginationInfo">Showing 0 to 0 of 0 entries</div>
      <div class="osr-page-buttons" id="paginationControls">
        <!-- Rendered via JS -->
      </div>
    </div>
  </div>

</div>

<!-- Floating Bulk Action Bar (Appears when >= 1 trays are selected) -->
<div class="osr-floating-bar" id="floatingBar">
  <div class="osr-floating-info">
    <i class="fa fa-check-circle" style="color:#10b981; font-size:1.1rem;"></i>
    <span id="selectedCountText">0 trays selected</span>
  </div>
  <div class="osr-floating-actions">
    <button type="button" id="btnBulkReturn" class="btn-bulk-return">
      <i class="fa fa-rotate-left"></i> Return Selected Trays to Polyhouse
    </button>
    <button type="button" id="btnDeselectAll" class="btn-bulk-cancel">
      Deselect All
    </button>
  </div>
</div>

<!-- Modal: Return Trays to Polyhouse -->
<div class="osr-modal-backdrop" id="returnModal">
  <div class="osr-modal-dialog">
    <div class="osr-modal-header">
      <h3>
        <i class="fa fa-rotate-left" style="color: #10b981;"></i>
        Return Trays to Polyhouse
      </h3>
      <button type="button" class="osr-modal-close" id="btnCloseModal">&times;</button>
    </div>

    <form id="formReturnTrays">
      <div class="osr-modal-body">
        
        <!-- Summary Box of Trays Being Returned -->
        <div class="osr-return-summary">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 6px;">
            <strong style="color:#0f172a; font-size:0.88rem;">
              <i class="fa fa-cubes-stacked text-primary"></i> Trays Selected for Return:
            </strong>
            <span class="badge" id="modalTrayCountBadge" style="background:#dbeafe; color:#1e40af; font-weight:700; padding:2px 8px; border-radius:12px; font-size:0.75rem;">0 Trays</span>
          </div>
          <div id="modalSelectedTraysList" class="osr-trays-chips">
            <!-- Tray chips rendered via JS -->
          </div>
        </div>

        <!-- Target Polyhouse Selector -->
        <div class="osr-form-group">
          <label for="returnPolyhouseSelect" class="osr-form-label">
            Target Polyhouse <span style="color:#ef4444;">*</span>
          </label>
          <select id="returnPolyhouseSelect" class="osr-select" required>
            <option value="">-- Choose Target Polyhouse --</option>
            <!-- Loaded via API -->
          </select>
          <small class="osr-help-text">Select which production polyhouse will receive these trays.</small>
        </div>

        <!-- Target Bed Selector / Input -->
        <div class="osr-form-group">
          <label for="returnBedSelect" class="osr-form-label">
            Target Bed No <span style="color:#ef4444;">*</span>
          </label>
          <div style="display:flex; gap: 8px;">
            <select id="returnBedSelect" class="osr-select" style="flex: 1;" required>
              <option value="">-- Select Bed --</option>
            </select>
            <input type="text" id="returnBedCustom" class="osr-input" placeholder="Or type Bed No (e.g. 05)" style="flex: 1; display:none;">
            <button type="button" id="btnToggleCustomBed" class="btn-osr-outline" style="font-size:0.75rem; white-space:nowrap; padding: 0 10px;" title="Switch between predefined bed list and custom text entry">
              Custom Bed
            </button>
          </div>
          <small class="osr-help-text">Where the tray will be placed in the polyhouse.</small>
        </div>

        <!-- Return Narration / Reason -->
        <div class="osr-form-group">
          <label for="returnReason" class="osr-form-label">
            Return Reason / Remark <span style="color:#ef4444;">*</span>
          </label>
          <textarea id="returnReason" class="osr-textarea" rows="2" placeholder="e.g. Returned after 2 days unsold, plant healthy, shifted back to bed." required></textarea>
        </div>

        <div class="osr-alert-info">
          <i class="fa fa-info-circle"></i>
          <div>
            <strong>What happens upon return:</strong>
            <ul style="margin: 4px 0 0 16px; padding: 0;">
              <li>Open sale stock record will be marked as <code>Returned</code>.</li>
              <li>Plant details & Ready-to-Sale stock will be restored to the selected Polyhouse & Bed.</li>
              <li>Movement will be audited in tray movement history under your username.</li>
            </ul>
          </div>
        </div>

      </div>

      <div class="osr-modal-footer">
        <button type="button" class="btn-osr-outline" id="btnCancelModal">Cancel</button>
        <button type="submit" class="btn-osr-primary" id="btnSubmitReturn" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
          <i class="fa fa-check-circle"></i> Confirm Return to Polyhouse
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Module JavaScript -->
<script src="assets/opensale_register.js?v=<?= time() ?>"></script>
</body>
</html>