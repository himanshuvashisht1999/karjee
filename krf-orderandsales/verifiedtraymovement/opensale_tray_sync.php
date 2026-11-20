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

if (isset($_GET['dz'])) {
    require('../../connection.php');
} else {
    require('../../header.php');
}
$loginroleid = isset($loginroleid) ? (int)$loginroleid : (int)($_SESSION['loginroleid'] ?? 4);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Previous Open Sale Tray Location Sync | Karjee Farm</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

  <!-- Fonts & Icons -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  
  <!-- jQuery & SweetAlert2 -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Custom Module CSS -->
  <link rel="stylesheet" href="css/verifiedtraymovement.css?v=<?= time() ?>">
  <style>
    .sync-page-wrap {
      max-width: 1400px;
      margin: 20px auto;
      padding: 0 16px 50px;
      font-family: 'Plus Jakarta Sans', sans-serif;
    }
    .sync-hero {
      background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
      color: #fff;
      padding: 24px 28px;
      border-radius: 16px;
      margin-bottom: 20px;
      box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.2);
    }
    .sync-hero h1 {
      font-size: 1.5rem;
      font-weight: 800;
      margin: 0 0 6px 0;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .sync-hero p {
      font-size: 0.9rem;
      color: #94a3b8;
      margin: 0;
    }
    .stat-cards-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 14px;
      margin-bottom: 20px;
    }
    .stat-card {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 16px 18px;
      display: flex;
      align-items: center;
      gap: 14px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.03);
      transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .stat-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 12px rgba(0,0,0,0.06);
    }
    .stat-icon {
      width: 44px;
      height: 44px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.25rem;
    }
    .stat-info h3 {
      font-size: 1.45rem;
      font-weight: 800;
      margin: 0;
      color: #0f172a;
      line-height: 1.2;
    }
    .stat-info span {
      font-size: 0.78rem;
      color: #64748b;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.03em;
    }
    .action-panel-card {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 14px;
      padding: 20px;
      margin-bottom: 20px;
      box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
    }
    .filter-panel-card {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 14px;
      padding: 16px 20px;
      margin-bottom: 20px;
    }
    .table-container {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 14px;
      overflow: hidden;
      box-shadow: 0 4px 6px -1px rgba(0,0,0,0.04);
    }
    .table-custom-sync {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      font-size: 0.85rem;
    }
    .table-custom-sync thead th {
      background: #f1f5f9;
      color: #475569;
      font-weight: 700;
      text-transform: uppercase;
      font-size: 0.75rem;
      letter-spacing: 0.04em;
      padding: 12px 14px;
      border-bottom: 1px solid #e2e8f0;
      white-space: nowrap;
    }
    .table-custom-sync tbody td {
      padding: 12px 14px;
      border-bottom: 1px solid #f1f5f9;
      vertical-align: middle;
      color: #1e293b;
    }
    .table-custom-sync tbody tr:hover {
      background-color: #f8fafc;
    }
    .barcode-pill {
      font-family: 'JetBrains Mono', monospace;
      font-weight: 700;
      color: #1e293b;
      background: #f1f5f9;
      padding: 3px 8px;
      border-radius: 6px;
      border: 1px solid #cbd5e1;
      font-size: 0.82rem;
      display: inline-block;
    }
    .badge-pending {
      background: #fef3c7;
      color: #92400e;
      font-weight: 700;
      font-size: 0.72rem;
      padding: 4px 8px;
      border-radius: 6px;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      border: 1px solid #fde68a;
    }
    .badge-loc-recorded {
      background: #dbeafe;
      color: #1e40af;
      font-weight: 700;
      font-size: 0.75rem;
      padding: 4px 8px;
      border-radius: 6px;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      border: 1px solid #bfdbfe;
    }
    .badge-ph-bed {
      background: #f1f5f9;
      color: #334155;
      font-weight: 600;
      font-size: 0.75rem;
      padding: 3px 7px;
      border-radius: 6px;
      border: 1px solid #e2e8f0;
    }
    .btn-sync-action {
      background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
      color: #fff;
      font-weight: 700;
      border: none;
      border-radius: 10px;
      padding: 10px 20px;
      font-size: 0.9rem;
      box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.15s ease;
    }
    .btn-sync-action:hover {
      background: linear-gradient(135deg, #4338ca 0%, #3730a3 100%);
      box-shadow: 0 6px 16px rgba(79, 70, 229, 0.4);
      transform: translateY(-1px);
      color: #fff;
    }
    .btn-sync-action:disabled {
      background: #94a3b8;
      box-shadow: none;
      cursor: not-allowed;
      transform: none;
    }
  </style>
</head>
<body>

<div class="sync-page-wrap">
  
  <!-- Hero Header -->
  <div class="sync-hero">
    <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 14px;">
      <div>
        <h1><i class="fa fa-sync-alt text-warning"></i> Open Sale Tray Location Sync & Shifting</h1>
        <p>Assign and insert new rows in <code>krf_polyhouse_plant_details</code> for previously moved Open Sale trays.</p>
      </div>
      <div>
        <a href="index.php" class="btn btn-sm btn-outline-light" style="font-weight: 700; border-radius: 8px; padding: 8px 14px;">
          <i class="fa fa-arrow-left mr-1"></i> Back to Verified Tray Movement
        </a>
      </div>
    </div>
  </div>

  <!-- KPI Stat Cards -->
  <div class="stat-cards-grid">
    <div class="stat-card">
      <div class="stat-icon" style="background:#fef3c7; color:#d97706;">
        <i class="fa fa-exclamation-triangle"></i>
      </div>
      <div class="stat-info">
        <h3 id="statPendingCount">...</h3>
        <span>Pending Trays</span>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-icon" style="background:#e0e7ff; color:#4f46e5;">
        <i class="fa fa-clipboard-list"></i>
      </div>
      <div class="stat-info">
        <h3 id="statOrdersCount">...</h3>
        <span>Orders Affected</span>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-icon" style="background:#dcfce7; color:#16a34a;">
        <i class="fa fa-seedling"></i>
      </div>
      <div class="stat-info">
        <h3 id="statProductsCount">...</h3>
        <span>Distinct Products</span>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-icon" style="background:#f1f5f9; color:#475569;">
        <i class="fa fa-store"></i>
      </div>
      <div class="stat-info">
        <h3 id="statCentersCount">3</h3>
        <span>Open Sale Centers</span>
      </div>
    </div>
  </div>

  <!-- Filter Panel -->
  <div class="filter-panel-card">
    <div class="row align-items-center" style="gap: 12px 0;">
      <div class="col-md-4 col-12">
        <label class="form-label-custom font-weight-bold" style="font-size:0.8rem; color:#475569;">
          <i class="fa fa-filter text-primary mr-1"></i> Filter by Order / Stock Order:
        </label>
        <select id="filterOrder" class="form-select-custom modern-select w-100" onchange="applyFilters()">
          <option value="">-- All Orders --</option>
        </select>
      </div>

      <div class="col-md-4 col-12">
        <label class="form-label-custom font-weight-bold" style="font-size:0.8rem; color:#475569;">
          <i class="fa fa-seedling text-success mr-1"></i> Filter by Product:
        </label>
        <select id="filterProduct" class="form-select-custom modern-select w-100" onchange="applyFilters()">
          <option value="">-- All Products --</option>
        </select>
      </div>

      <div class="col-md-4 col-12">
        <label class="form-label-custom font-weight-bold" style="font-size:0.8rem; color:#475569;">
          <i class="fa fa-search text-secondary mr-1"></i> Search (Barcode / Order / Plant):
        </label>
        <div class="d-flex" style="gap: 8px;">
          <input type="text" id="filterSearch" class="form-control" placeholder="Search..." oninput="applyFilters()" style="font-size:0.85rem; border-radius:8px;">
          <button type="button" class="btn btn-outline-secondary" onclick="resetFilters()" style="border-radius:8px; white-space:nowrap; font-size:0.82rem;">
            <i class="fa fa-undo"></i> Reset
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Bulk Action Panel -->
  <div class="action-panel-card">
    <div class="d-flex align-items-center justify-content-between flex-wrap mb-3" style="gap: 10px;">
      <div>
        <h4 style="font-size: 1.05rem; font-weight: 800; color: #0f172a; margin: 0;">
          <i class="fa fa-dolly-flatbed text-primary mr-1"></i> Insert Open Sale Location Row
        </h4>
        <small class="text-muted">Select destination Open Sale Location and execute insertion for checked trays.</small>
      </div>
      <div>
        <span class="badge" style="background:#e0e7ff; color:#3730a3; font-weight:700; font-size:0.8rem; padding:6px 12px; border-radius:8px;">
          <span id="badgeSelectedCount">0</span> Tray(s) Selected
        </span>
      </div>
    </div>

    <div class="row align-items-end" style="gap: 12px 0;">
      <div class="col-md-5 col-12">
        <label class="form-label-custom font-weight-bold" style="font-size:0.82rem; color:#1e293b;">
          <i class="fa fa-shopping-cart text-success mr-1"></i> Which Open Sale Location? <span class="text-danger">*</span>
        </label>
        <select id="selectOpenSaleTarget" class="form-select-custom modern-select w-100">
          <option value="">-- Select Destination Open Sale Location --</option>
        </select>
      </div>

      <div class="col-md-4 col-12">
        <label class="form-label-custom font-weight-bold" style="font-size:0.82rem; color:#1e293b;">
          <i class="fa fa-comment-alt text-secondary mr-1"></i> Movement Narration / Remark (Optional):
        </label>
        <input type="text" id="inputMovementNarration" class="form-control" placeholder="Optional notes for sync entry..." style="font-size:0.85rem; border-radius:8px;">
      </div>

      <div class="col-md-3 col-12 text-md-right text-left">
        <button type="button" class="btn-sync-action w-100 justify-content-center" id="btnExecuteSync" onclick="executeBulkSync()">
          <i class="fa fa-plus-circle"></i> <span>Insert Row for (<span id="lblBtnCount">0</span>) Trays</span>
        </button>
      </div>
    </div>
  </div>

  <!-- Trays Table -->
  <div class="table-container">
    <div class="d-flex justify-content-between align-items-center p-3 border-bottom" style="background:#fafafa;">
      <div>
        <span class="font-weight-bold text-dark" style="font-size:0.9rem;">
          <i class="fa fa-list text-primary mr-1"></i> Pending Trays List
        </span>
        <span class="text-muted ml-2" style="font-size:0.8rem;" id="lblShowingCount">Loading...</span>
      </div>
      <div>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadPendingTrays()" style="font-size:0.78rem; border-radius:6px;">
          <i class="fa fa-sync-alt mr-1"></i> Refresh List
        </button>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table-custom-sync">
        <thead>
          <tr>
            <th style="width: 40px; text-align: center;">
              <input type="checkbox" id="chkSelectAll" onchange="toggleSelectAll(this)" style="cursor:pointer; width:16px; height:16px;">
            </th>
            <th style="width: 50px;">#</th>
            <th>Tray Barcode</th>
            <th>Order / Stock Order</th>
            <th>Product Name & Variety</th>
            <th>Original Location</th>
            <th style="text-align: center;">Tray Qty</th>
            <th>Recorded Location in Stock Table</th>
            <th style="text-align: center;">Status</th>
            <th style="text-align: center; width: 120px;">Action</th>
          </tr>
        </thead>
        <tbody id="tbodyPendingTrays">
          <tr>
            <td colspan="10" class="text-center py-5 text-muted">
              <i class="fa fa-spinner fa-spin fa-2x mb-2 text-primary"></i><br>Loading pending open sale trays...
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

</div>

<script>
let allPendingTrays = [];
let filteredTrays = [];
let openSaleCentersList = [];

$(document).ready(function () {
  loadPendingTrays();
});

function loadPendingTrays() {
  $('#tbodyPendingTrays').html(`
    <tr>
      <td colspan="10" class="text-center py-5 text-muted">
        <i class="fa fa-spinner fa-spin fa-2x mb-2 text-primary"></i><br>Loading pending open sale trays...
      </td>
    </tr>
  `);

  $.getJSON('modal/verifiedtraymovement_modal.php?action=get_pending_opensale_trays', function (res) {
    if (!res || res.status !== 'success' || !res.data) {
      $('#tbodyPendingTrays').html(`
        <tr>
          <td colspan="10" class="text-center py-4 text-danger font-weight-bold">
            <i class="fa fa-exclamation-circle mr-1"></i> Error loading pending trays.
          </td>
        </tr>
      `);
      return;
    }

    const d = res.data;
    allPendingTrays = d.trays || [];
    openSaleCentersList = d.opensale_locations || [];

    // Populate Stats
    $('#statPendingCount').text(d.total_count || 0);
    $('#statOrdersCount').text(d.orders ? d.orders.length : 0);
    $('#statProductsCount').text(d.products ? d.products.length : 0);
    $('#statCentersCount').text(openSaleCentersList.length);

    // Populate Open Sale Locations Dropdown
    let locOpts = '<option value="">-- Select Destination Open Sale Location --</option>';
    openSaleCentersList.forEach(loc => {
      locOpts += `<option value="${escapeHtml(loc.polyhouseid)}" data-locid="${escapeHtml(loc.locationid)}">${escapeHtml(loc.polyhousename)} (PH-${escapeHtml(loc.polyhouseid)})</option>`;
    });
    $('#selectOpenSaleTarget').html(locOpts);

    // Populate Orders Dropdown
    let curOrder = $('#filterOrder').val();
    let ordOpts = '<option value="">-- All Orders --</option>';
    (d.orders || []).forEach(o => {
      ordOpts += `<option value="${escapeHtml(o)}">${escapeHtml(o)}</option>`;
    });
    $('#filterOrder').html(ordOpts);
    if (curOrder) $('#filterOrder').val(curOrder);

    // Populate Products Dropdown
    let curProd = $('#filterProduct').val();
    let prodOpts = '<option value="">-- All Products --</option>';
    (d.products || []).forEach(p => {
      prodOpts += `<option value="${escapeHtml(p.productid)}">${escapeHtml(p.productname)}</option>`;
    });
    $('#filterProduct').html(prodOpts);
    if (curProd) $('#filterProduct').val(curProd);

    applyFilters();
  }).fail(function () {
    $('#tbodyPendingTrays').html(`
      <tr>
        <td colspan="10" class="text-center py-4 text-danger font-weight-bold">
          <i class="fa fa-exclamation-circle mr-1"></i> Failed to communicate with server.
        </td>
      </tr>
    `);
  });
}

function applyFilters() {
  const selOrder = $('#filterOrder').val().trim().toLowerCase();
  const selProd  = $('#filterProduct').val().trim();
  const search   = $('#filterSearch').val().trim().toLowerCase();

  filteredTrays = allPendingTrays.filter(t => {
    if (selOrder) {
      const o1 = (t.stockorder_no || '').toLowerCase();
      const o2 = (t.psono || '').toLowerCase();
      if (o1 !== selOrder && o2 !== selOrder) return false;
    }
    if (selProd) {
      if (String(t.productid || '') !== selProd) return false;
    }
    if (search) {
      const b = (t.barcode || '').toLowerCase();
      const so = (t.stockorder_no || '').toLowerCase();
      const p = (t.productname || '').toLowerCase();
      if (!b.includes(search) && !so.includes(search) && !p.includes(search)) return false;
    }
    return true;
  });

  renderTable();
}

function resetFilters() {
  $('#filterOrder').val('');
  $('#filterProduct').val('');
  $('#filterSearch').val('');
  applyFilters();
}

function renderTable() {
  $('#lblShowingCount').text(`Showing ${filteredTrays.length} of ${allPendingTrays.length} tray(s)`);
  $('#chkSelectAll').prop('checked', false);

  if (!filteredTrays.length) {
    if (!allPendingTrays.length) {
      $('#tbodyPendingTrays').html(`
        <tr>
          <td colspan="10" class="text-center py-5">
            <div style="font-size:2.5rem; color:#10b981; margin-bottom:10px;"><i class="fa fa-check-circle"></i></div>
            <h5 class="font-weight-bold text-dark">All Trays Synced!</h5>
            <p class="text-muted" style="font-size:0.85rem;">There are no previous open sale trays missing location rows.</p>
          </td>
        </tr>
      `);
    } else {
      $('#tbodyPendingTrays').html(`
        <tr>
          <td colspan="10" class="text-center py-5 text-muted">
            <i class="fa fa-filter fa-2x mb-2 text-secondary"></i><br>No trays match the selected filter criteria.
          </td>
        </tr>
      `);
    }
    updateSelectedCount();
    return;
  }

  let html = '';
  filteredTrays.forEach((t, idx) => {
    const sNo = idx + 1;
    const bCode = escapeHtml(t.barcode);
    const soNo = escapeHtml(t.stockorder_no || t.psono || '-');
    const prodName = escapeHtml(t.productname || 'Plant');
    const variety = t.variety ? `<div class="text-muted" style="font-size:0.75rem;">${escapeHtml(t.variety)}</div>` : '';
    const origLoc = `<span class="badge-ph-bed"><i class="fa fa-warehouse text-secondary mr-1"></i>${escapeHtml(t.polyhousename || ('PH-' + t.polyhouseid))}</span> <span class="badge-ph-bed ml-1">${escapeHtml(t.bed_no || '-')}</span>`;
    const qty = t.actual_plant_count !== undefined ? t.actual_plant_count : (t.qtypertray || '-');
    const recLoc = t.open_sale_location ? `<span class="badge-loc-recorded"><i class="fa fa-store mr-1"></i>${escapeHtml(t.open_sale_location)}</span>` : '<span class="text-muted">-</span>';

    html += `
      <tr id="rowTray_${escapeHtml(t.polyhouseplantid)}">
        <td style="text-align: center;">
          <input type="checkbox" class="chk-tray" value="${escapeHtml(t.polyhouseplantid)}" data-barcode="${bCode}" data-osloc="${escapeHtml(t.open_sale_location || '')}" onchange="updateSelectedCount()" style="cursor:pointer; width:16px; height:16px;">
        </td>
        <td><b>${sNo}</b></td>
        <td>
          <span class="barcode-pill">${bCode}</span>
        </td>
        <td>
          <span class="font-weight-bold text-dark">${soNo}</span>
        </td>
        <td>
          <div class="font-weight-bold text-dark">${prodName}</div>
          ${variety}
        </td>
        <td>${origLoc}</td>
        <td style="text-align: center;">
          <span class="font-weight-bold text-primary">${qty}</span>
        </td>
        <td>${recLoc}</td>
        <td style="text-align: center;">
          <span class="badge-pending"><i class="fa fa-clock mr-1"></i>Pending Entry</span>
        </td>
        <td style="text-align: center;">
          <button type="button" class="btn btn-sm btn-outline-primary" onclick="syncSingleTray('${escapeHtml(t.polyhouseplantid)}', '${bCode}', '${escapeHtml(t.open_sale_location || '')}')" style="font-size:0.75rem; padding:4px 10px; border-radius:6px; font-weight:700;">
            <i class="fa fa-arrow-right mr-1"></i> Sync
          </button>
        </td>
      </tr>
    `;
  });

  $('#tbodyPendingTrays').html(html);
  updateSelectedCount();
}

function toggleSelectAll(masterChk) {
  const isChecked = $(masterChk).is(':checked');
  $('.chk-tray').prop('checked', isChecked);
  updateSelectedCount();
}

function updateSelectedCount() {
  const checked = $('.chk-tray:checked');
  const count = checked.length;
  $('#badgeSelectedCount').text(count);
  $('#lblBtnCount').text(count);
  $('#btnExecuteSync').prop('disabled', count === 0);

  // Smart helper: if user selected trays that share the same recorded location, suggest it in the dropdown if not chosen
  if (count > 0 && !$('#selectOpenSaleTarget').val()) {
    let locCandidates = [];
    checked.each(function () {
      const l = $(this).data('osloc');
      if (l && !locCandidates.includes(l)) locCandidates.push(l);
    });
    if (locCandidates.length === 1) {
      const targetName = locCandidates[0];
      $("#selectOpenSaleTarget option").each(function () {
        if ($(this).text().includes(targetName)) {
          $('#selectOpenSaleTarget').val($(this).val());
          return false;
        }
      });
    }
  }
}

function syncSingleTray(plantId, barcode, recordedLocName) {
  // If dropdown already has a target, use it; otherwise, try to match recordedLocName
  let targetPhId = $('#selectOpenSaleTarget').val();
  if (!targetPhId && recordedLocName) {
    $("#selectOpenSaleTarget option").each(function () {
      if ($(this).text().includes(recordedLocName)) {
        targetPhId = $(this).val();
        $('#selectOpenSaleTarget').val(targetPhId);
        return false;
      }
    });
  }

  if (!targetPhId) {
    Swal.fire({
      icon: 'warning',
      title: 'Select Open Sale Location',
      text: 'Please select "Which Open Sale Location?" from the dropdown above before syncing.',
      confirmButtonColor: '#4f46e5'
    });
    $('#selectOpenSaleTarget').focus();
    return;
  }

  const targetName = $('#selectOpenSaleTarget option:selected').text();
  Swal.fire({
    title: 'Confirm Location Entry',
    html: `Insert Open Sale row in <b>krf_polyhouse_plant_details</b> for Tray <code>${barcode}</code> at <b>${targetName}</b>?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: '<i class="fa fa-check mr-1"></i> Yes, Insert Row',
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#4f46e5'
  }).then(result => {
    if (result.isConfirmed) {
      sendSyncRequest([plantId], [barcode], targetPhId, $('#inputMovementNarration').val());
    }
  });
}

function executeBulkSync() {
  const checked = $('.chk-tray:checked');
  if (!checked.length) {
    Swal.fire({
      icon: 'warning',
      title: 'No Trays Selected',
      text: 'Please check at least 1 tray from the list to sync.',
      confirmButtonColor: '#4f46e5'
    });
    return;
  }

  const targetPhId = $('#selectOpenSaleTarget').val();
  if (!targetPhId) {
    Swal.fire({
      icon: 'warning',
      title: 'Select Destination Location',
      text: 'Please select "Which Open Sale Location?" from the dropdown.',
      confirmButtonColor: '#4f46e5'
    });
    $('#selectOpenSaleTarget').focus();
    return;
  }

  const targetName = $('#selectOpenSaleTarget option:selected').text();
  const plantIds = [];
  const barcodes = [];
  checked.each(function () {
    plantIds.push($(this).val());
    barcodes.push($(this).data('barcode'));
  });

  Swal.fire({
    title: 'Confirm Bulk Entry',
    html: `Insert Open Sale row in <b>krf_polyhouse_plant_details</b> for <b>${plantIds.length}</b> selected tray(s) into <b>${targetName}</b>?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: `<i class="fa fa-check mr-1"></i> Yes, Insert for ${plantIds.length} Trays`,
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#4f46e5'
  }).then(result => {
    if (result.isConfirmed) {
      sendSyncRequest(plantIds, barcodes, targetPhId, $('#inputMovementNarration').val());
    }
  });
}

function sendSyncRequest(plantIds, barcodes, targetPhId, narration) {
  Swal.fire({
    title: 'Processing Location Insertion...',
    html: 'Inserting new records into <code>krf_polyhouse_plant_details</code> and synchronizing stock...',
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    }
  });

  $.ajax({
    url: 'modal/verifiedtraymovement_modal.php?action=sync_previous_opensale_trays',
    type: 'POST',
    dataType: 'json',
    data: {
      target_opensale_polyhouseid: targetPhId,
      custom_narration: narration,
      selected_plant_ids: JSON.stringify(plantIds),
      selected_barcodes: JSON.stringify(barcodes)
    },
    success: function (res) {
      if (res && res.status === 'success') {
        Swal.fire({
          icon: 'success',
          title: 'Location Entry Successful!',
          text: res.message || `Successfully synced ${plantIds.length} tray(s).`,
          confirmButtonColor: '#10b981'
        }).then(() => {
          loadPendingTrays();
        });
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Sync Failed',
          text: (res && res.message) ? res.message : 'An error occurred during insertion.',
          confirmButtonColor: '#ef4444'
        });
      }
    },
    error: function (xhr) {
      Swal.fire({
        icon: 'error',
        title: 'Communication Error',
        text: 'Failed to reach server: ' + (xhr.statusText || 'Unknown error'),
        confirmButtonColor: '#ef4444'
      });
    }
  });
}

function escapeHtml(str) {
  if (str === null || str === undefined) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
</script>

<?php
if (!isset($_GET['dz'])) {
    require('../../footer.php'); 
}
?>
</body>
</html>

