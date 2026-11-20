<?php
/**
 * Dispatch Variety Change Approvals Console
 * Path: /readytoload/pending_approvals.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Kolkata');

// Session & Authentication Verification
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
    require_once(__DIR__ . '/../connection.php');
} else {
    require_once(__DIR__ . '/../header.php');
}

$loginroleid = isset($loginroleid) ? (int)$loginroleid : (int)($_SESSION['loginroleid'] ?? 4);
$loginname   = $_SESSION['loginname'] ?? $_SESSION['username'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Variety Change Approvals | Karjee Farm Logistics</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Modern Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@600;700&display=swap" rel="stylesheet">

  <!-- FontAwesome & Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
    :root {
      --primary: #4f46e5;
      --primary-hover: #4338ca;
      --success: #10b981;
      --warning: #f59e0b;
      --danger: #ef4444;
      --dark: #0f172a;
      --slate-50: #f8fafc;
      --slate-100: #f1f5f9;
      --slate-200: #e2e8f0;
      --slate-300: #cbd5e1;
      --slate-600: #475569;
      --slate-700: #334155;
      --slate-800: #1e293b;
      --radius-sm: 6px;
      --radius-md: 10px;
      --radius-lg: 14px;
      --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
      --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.07), 0 2px 4px -2px rgba(0,0,0,0.05);
      --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.08), 0 4px 6px -4px rgba(0,0,0,0.04);
    }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background-color: #f8fafc;
      color: #1e293b;
      margin: 0;
      padding: 0;
    }

    .app-container {
      max-width: 1520px;
      margin: 0 auto;
      padding: 24px 20px 48px;
    }

    /* Hero Header */
    .app-hero {
      background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
      color: #fff;
      padding: 24px 28px;
      border-radius: var(--radius-lg);
      margin-bottom: 24px;
      box-shadow: var(--shadow-md);
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 16px;
    }

    .app-hero h1 {
      margin: 0 0 6px 0;
      font-size: 1.55rem;
      font-weight: 800;
      letter-spacing: -0.02em;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .app-hero p {
      margin: 0;
      font-size: 0.88rem;
      color: #94a3b8;
    }

    .app-hero-actions {
      display: flex;
      gap: 10px;
      align-items: center;
    }

    .btn-hero {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      padding: 8px 16px;
      border-radius: var(--radius-sm);
      font-size: 0.86rem;
      font-weight: 700;
      text-decoration: none;
      transition: all 0.15s ease-in-out;
      border: 1px solid transparent;
      cursor: pointer;
    }

    .btn-hero-light {
      background: #ffffff;
      color: #1e293b;
      box-shadow: var(--shadow-sm);
    }
    .btn-hero-light:hover {
      background: #f1f5f9;
      color: #0f172a;
    }

    .btn-hero-back {
      background: rgba(255, 255, 255, 0.12);
      color: #f8fafc;
      border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .btn-hero-back:hover {
      background: rgba(255, 255, 255, 0.22);
      color: #ffffff;
    }

    /* KPI Cards */
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
      gap: 16px;
      margin-bottom: 24px;
    }

    .kpi-card {
      background: #ffffff;
      border-radius: var(--radius-md);
      padding: 18px 20px;
      display: flex;
      align-items: center;
      gap: 16px;
      box-shadow: var(--shadow-sm);
      border: 1px solid var(--slate-200);
      transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .kpi-card:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-md);
    }

    .kpi-icon {
      width: 48px;
      height: 48px;
      border-radius: var(--radius-sm);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.3rem;
      flex-shrink: 0;
    }
    .kpi-icon-blue    { background: #e0e7ff; color: #4338ca; }
    .kpi-icon-amber   { background: #fef3c7; color: #b45309; }
    .kpi-icon-emerald { background: #dcfce7; color: #15803d; }
    .kpi-icon-rose    { background: #ffe4e6; color: #be123c; }

    .kpi-info {
      display: flex;
      flex-direction: column;
    }
    .kpi-label {
      font-size: 0.8rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      color: #64748b;
      margin-bottom: 2px;
    }
    .kpi-val {
      font-size: 1.6rem;
      font-weight: 800;
      color: #0f172a;
      line-height: 1.2;
    }

    /* Filter Panel */
    .filter-panel {
      background: #ffffff;
      border-radius: var(--radius-md);
      padding: 16px 20px;
      box-shadow: var(--shadow-sm);
      border: 1px solid var(--slate-200);
      margin-bottom: 20px;
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      align-items: center;
      gap: 14px;
    }

    .status-tabs {
      display: flex;
      gap: 6px;
      background: #f1f5f9;
      padding: 4px;
      border-radius: var(--radius-sm);
      border: 1px solid var(--slate-200);
    }

    .status-tab {
      padding: 6px 14px;
      font-size: 0.82rem;
      font-weight: 700;
      border-radius: 4px;
      border: none;
      background: transparent;
      color: #64748b;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 6px;
      transition: all 0.15s ease;
    }
    .status-tab:hover {
      color: #1e293b;
    }
    .status-tab.active {
      background: #ffffff;
      color: #4f46e5;
      box-shadow: var(--shadow-sm);
    }

    .tab-badge {
      display: inline-block;
      padding: 1px 6px;
      font-size: 0.72rem;
      border-radius: 10px;
      background: #e2e8f0;
      color: #475569;
    }
    .status-tab.active .tab-badge {
      background: #e0e7ff;
      color: #4338ca;
    }

    .filter-controls {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }

    .search-input-wrap {
      position: relative;
      min-width: 260px;
    }
    .search-input-wrap i {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: #94a3b8;
      font-size: 0.85rem;
    }
    .search-input {
      width: 100%;
      padding: 8px 12px 8px 34px;
      font-size: 0.86rem;
      border: 1px solid var(--slate-300);
      border-radius: var(--radius-sm);
      outline: none;
      transition: border-color 0.15s ease;
      box-sizing: border-box;
    }
    .search-input:focus {
      border-color: #6366f1;
      box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
    }

    /* Table Card */
    .table-card {
      background: #ffffff;
      border-radius: var(--radius-md);
      box-shadow: var(--shadow-sm);
      border: 1px solid var(--slate-200);
      overflow: hidden;
    }

    .table-card-head {
      padding: 16px 20px;
      border-bottom: 1px solid var(--slate-200);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .table-card-head h2 {
      margin: 0;
      font-size: 1.05rem;
      font-weight: 700;
      color: #1e293b;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .table-responsive {
      overflow-x: auto;
      width: 100%;
    }

    .app-table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
      font-size: 0.86rem;
    }

    .app-table th {
      background: #f8fafc;
      color: #475569;
      font-weight: 700;
      padding: 12px 14px;
      border-bottom: 1px solid var(--slate-200);
      white-space: nowrap;
      text-transform: uppercase;
      font-size: 0.75rem;
      letter-spacing: 0.03em;
    }

    .app-table td {
      padding: 12px 14px;
      border-bottom: 1px solid #f1f5f9;
      color: #334155;
      vertical-align: middle;
    }

    .app-table tbody tr:hover {
      background-color: #f8fafc;
    }

    /* Badges & Pills */
    .badge-req {
      font-family: 'JetBrains Mono', monospace;
      font-weight: 700;
      font-size: 0.8rem;
      padding: 3px 7px;
      border-radius: 4px;
      background: #eef2ff;
      color: #4338ca;
      border: 1px solid #c7d2fe;
      display: inline-block;
    }

    .badge-barcode {
      font-family: 'JetBrains Mono', monospace;
      font-weight: 700;
      font-size: 0.82rem;
      padding: 3px 8px;
      border-radius: 4px;
      background: #f1f5f9;
      color: #0f172a;
      border: 1px solid var(--slate-300);
      display: inline-block;
    }

    .badge-status {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 3px 9px;
      border-radius: 12px;
      font-size: 0.78rem;
      font-weight: 700;
      text-transform: capitalize;
    }
    .badge-status-pending  { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
    .badge-status-approved { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .badge-status-rejected { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

    .badge-loaded {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 2px 7px;
      border-radius: 4px;
      font-size: 0.72rem;
      font-weight: 700;
      background: #ecfdf5;
      color: #047857;
      border: 1px solid #a7f3d0;
      margin-left: 4px;
    }

    .crop-pill {
      display: inline-block;
      padding: 3px 8px;
      border-radius: 4px;
      font-size: 0.82rem;
      font-weight: 600;
    }
    .crop-pill-target { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
    .crop-pill-scanned { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }

    /* Action Buttons */
    .btn-action {
      padding: 6px 12px;
      border-radius: var(--radius-sm);
      font-size: 0.8rem;
      font-weight: 700;
      cursor: pointer;
      border: none;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      transition: all 0.12s ease;
    }
    .btn-action-approve {
      background: #10b981;
      color: #ffffff;
    }
    .btn-action-approve:hover {
      background: #059669;
    }
    .btn-action-reject {
      background: #ef4444;
      color: #ffffff;
    }
    .btn-action-reject:hover {
      background: #dc2626;
    }

    .btn-action-info {
      background: #f1f5f9;
      color: #475569;
      border: 1px solid var(--slate-300);
    }
    .btn-action-info:hover {
      background: #e2e8f0;
      color: #1e293b;
    }

    /* Empty state */
    .empty-state {
      text-align: center;
      padding: 48px 20px;
    }
    .empty-state i {
      font-size: 3rem;
      color: #cbd5e1;
      margin-bottom: 12px;
    }
    .empty-state h3 {
      font-size: 1.1rem;
      font-weight: 700;
      color: #475569;
      margin: 0 0 6px 0;
    }
    .empty-state p {
      font-size: 0.88rem;
      color: #94a3b8;
      margin: 0;
    }

    /* Pulse animation */
    @keyframes pulseDot {
      0% { transform: scale(0.95); opacity: 0.8; }
      50% { transform: scale(1.2); opacity: 1; }
      100% { transform: scale(0.95); opacity: 0.8; }
    }
    .pulse-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: #f59e0b;
      display: inline-block;
      animation: pulseDot 1.5s infinite;
    }
  </style>
</head>
<body>

<div class="app-container">

  <!-- Hero Header -->
  <div class="app-hero">
    <div>
      <h1><i class="fa fa-clipboard-check text-warning"></i> Variety Change Approvals</h1>
      <p>Review, authorize, or reject dispatch requests when crop varieties differ from sales orders.</p>
    </div>
    <div class="app-hero-actions">
      <a href="readytoload.php" class="btn-hero btn-hero-back" title="Return to Scanner Dashboard">
        <i class="fa fa-arrow-left"></i>
        <span>Ready to Load</span>
      </a>
      <button type="button" class="btn-hero btn-hero-light" onclick="loadApprovalsList();" title="Refresh Table">
        <i class="fa fa-sync-alt"></i>
        <span>Refresh</span>
      </button>
    </div>
  </div>

  <!-- KPI Cards -->
  <div class="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-icon kpi-icon-blue">
        <i class="fa fa-layer-group"></i>
      </div>
      <div class="kpi-info">
        <span class="kpi-label">Total Requests</span>
        <span class="kpi-val" id="kpiTotal">0</span>
      </div>
    </div>

    <div class="kpi-card">
      <div class="kpi-icon kpi-icon-amber">
        <i class="fa fa-clock"></i>
      </div>
      <div class="kpi-info">
        <span class="kpi-label" style="display:flex; align-items:center; gap:6px;">
          Pending Review <span class="pulse-dot" id="dotPendingPulse" style="display:none;"></span>
        </span>
        <span class="kpi-val" style="color: #b45309;" id="kpiPending">0</span>
      </div>
    </div>

    <div class="kpi-card">
      <div class="kpi-icon kpi-icon-emerald">
        <i class="fa fa-check-circle"></i>
      </div>
      <div class="kpi-info">
        <span class="kpi-label">Approved</span>
        <span class="kpi-val" style="color: #15803d;" id="kpiApproved">0</span>
      </div>
    </div>

    <div class="kpi-card">
      <div class="kpi-icon kpi-icon-rose">
        <i class="fa fa-times-circle"></i>
      </div>
      <div class="kpi-info">
        <span class="kpi-label">Rejected</span>
        <span class="kpi-val" style="color: #be123c;" id="kpiRejected">0</span>
      </div>
    </div>
  </div>

  <!-- Filter Panel -->
  <div class="filter-panel">
    <div class="status-tabs">
      <button type="button" class="status-tab active" onclick="setStatusFilter('all', this);">
        <span>All</span>
        <span class="tab-badge" id="tabBadgeAll">0</span>
      </button>
      <button type="button" class="status-tab" onclick="setStatusFilter('pending', this);">
        <i class="fa fa-clock text-amber-500"></i>
        <span>Pending</span>
        <span class="tab-badge" id="tabBadgePending">0</span>
      </button>
      <button type="button" class="status-tab" onclick="setStatusFilter('approved', this);">
        <i class="fa fa-check text-emerald-500"></i>
        <span>Approved</span>
        <span class="tab-badge" id="tabBadgeApproved">0</span>
      </button>
      <button type="button" class="status-tab" onclick="setStatusFilter('rejected', this);">
        <i class="fa fa-times text-rose-500"></i>
        <span>Rejected</span>
        <span class="tab-badge" id="tabBadgeRejected">0</span>
      </button>
    </div>

    <div class="filter-controls">
      <div class="search-input-wrap">
        <i class="fa fa-search"></i>
        <input type="text" id="inputTableSearch" class="search-input" placeholder="Search order, batch, variety...">
      </div>
    </div>
  </div>

  <!-- Data Table Card -->
  <div class="table-card">
    <div class="table-card-head">
      <h2><i class="fa fa-list text-primary"></i> Requests Log</h2>
      <span class="text-muted" style="font-size:0.84rem;" id="lblRequestCount">Showing 0 requests</span>
    </div>

    <div class="table-responsive">
      <table class="app-table">
        <thead>
          <tr>
            <th style="width: 50px;">#</th>
            <th>Request Info</th>
            <th>Scanned Tray & Origin Order</th>
            <th>Target Order & Client</th>
            <th>Variety Comparison (Expected vs Scanned)</th>
            <th>Reason & Requested By</th>
            <th>Status</th>
            <th style="text-align: right; width: 150px;">Action</th>
          </tr>
        </thead>
        <tbody id="tblApprovalsBody">
          <!-- Populated dynamically via AJAX -->
        </tbody>
      </table>
    </div>

    <!-- Empty State -->
    <div id="boxEmptyState" class="empty-state" style="display:none;">
      <i class="fa fa-clipboard-check"></i>
      <h3>No Approval Requests Found</h3>
      <p>There are no variety change requests matching the current filter criteria.</p>
    </div>
  </div>

</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
(function ($) {
  'use strict';

  let currentStatusFilter = 'all';
  let requestsData = [];
  let searchDebounceTimer = null;

  function esc(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  window.setStatusFilter = function (status, el) {
    currentStatusFilter = status;
    $('.status-tab').removeClass('active');
    $(el).addClass('active');
    loadApprovalsList();
  };

  window.loadApprovalsList = function () {
    const searchVal = $.trim($('#inputTableSearch').val() || '');

    $.ajax({
      url: 'model/readytoload_model.php',
      type: 'GET',
      dataType: 'json',
      data: {
        action: 'get_pending_approvals',
        status: currentStatusFilter,
        search: searchVal
      },
      beforeSend: function () {
        $('#tblApprovalsBody').html(`
          <tr>
            <td colspan="8" style="text-align:center; padding:32px 14px; color:#64748b;">
              <i class="fa fa-spinner fa-spin fa-2x mb-2 text-primary" style="opacity:0.8;"></i>
              <div>Loading approval requests...</div>
            </td>
          </tr>
        `);
      },
      success: function (res) {
        if (res.status !== 'success') {
          $('#tblApprovalsBody').html(`
            <tr>
              <td colspan="8" style="text-align:center; padding:32px 14px; color:#ef4444;">
                <i class="fa fa-exclamation-triangle fa-2x mb-2"></i>
                <div>Failed to load requests: ${esc(res.message || 'Server error')}</div>
              </td>
            </tr>
          `);
          return;
        }

        requestsData = res.data || [];
        const kpis = res.kpis || { total: 0, pending: 0, approved: 0, rejected: 0 };

        // Update KPIs
        $('#kpiTotal').text(kpis.total);
        $('#kpiPending').text(kpis.pending);
        $('#kpiApproved').text(kpis.approved);
        $('#kpiRejected').text(kpis.rejected);

        $('#tabBadgeAll').text(kpis.total);
        $('#tabBadgePending').text(kpis.pending);
        $('#tabBadgeApproved').text(kpis.approved);
        $('#tabBadgeRejected').text(kpis.rejected);

        if (kpis.pending > 0) {
          $('#dotPendingPulse').show();
        } else {
          $('#dotPendingPulse').hide();
        }

        renderTableRows(requestsData);
      },
      error: function (xhr, status, err) {
        $('#tblApprovalsBody').html(`
          <tr>
            <td colspan="8" style="text-align:center; padding:32px 14px; color:#ef4444;">
              <i class="fa fa-wifi fa-2x mb-2"></i>
              <div>Communication error: ${esc(err)}</div>
            </td>
          </tr>
        `);
      }
    });
  };

  function renderTableRows(items) {
    const $tbody = $('#tblApprovalsBody');
    const $empty = $('#boxEmptyState');
    const count = items.length;

    $('#lblRequestCount').text(`Showing ${count} request${count === 1 ? '' : 's'}`);

    if (count === 0) {
      $tbody.html('');
      $empty.show();
      return;
    }

    $empty.hide();
    let html = '';

    items.forEach((r, idx) => {
      const isPending = (r.status === 'pending');
      const isApproved = (r.status === 'approved');
      const isRejected = (r.status === 'rejected');
      const isLoaded = (r.is_loaded == 1 || r.is_loaded === '1');

      let statusBadge = '';
      if (isPending) {
        statusBadge = `<span class="badge-status badge-status-pending"><i class="fa fa-clock"></i> Pending</span>`;
      } else if (isApproved) {
        statusBadge = `<span class="badge-status badge-status-approved"><i class="fa fa-check-circle"></i> Approved</span>`;
      } else if (isRejected) {
        statusBadge = `<span class="badge-status badge-status-rejected"><i class="fa fa-times-circle"></i> Rejected</span>`;
      }

      let loadedBadge = '';
      if (isLoaded) {
        loadedBadge = `<span class="badge-loaded" title="Tray Loaded on ${esc(r.formatted_loaded_at)}"><i class="fa fa-truck"></i> Loaded</span>`;
      }

      let actionsHtml = '';
      if (isPending) {
        actionsHtml = `
          <div style="display:flex; justify-content:flex-end; gap:6px;">
            <button type="button" class="btn-action btn-action-approve" onclick="openDecisionModal(${r.id}, 'approved', '${esc(r.request_no)}', '${esc(r.barcode)}');" title="Authorize Variety Change">
              <i class="fa fa-check"></i> Approve
            </button>
            <button type="button" class="btn-action btn-action-reject" onclick="openDecisionModal(${r.id}, 'rejected', '${esc(r.request_no)}', '${esc(r.barcode)}');" title="Reject Request">
              <i class="fa fa-times"></i> Reject
            </button>
          </div>
        `;
      } else {
        actionsHtml = `
          <div style="display:flex; justify-content:flex-end;">
            <button type="button" class="btn-action btn-action-info" onclick="viewRequestDetails(${r.id});" title="View Decision Audit">
              <i class="fa fa-eye"></i> Details
            </button>
          </div>
        `;
      }

      html += `
        <tr>
          <td class="text-muted font-weight-bold">${idx + 1}</td>
          <td>
            <span class="badge-req">${esc(r.request_no)}</span>
            <div style="font-size:0.78rem; color:#64748b; margin-top:4px;">
              <i class="fa fa-calendar-alt mr-1"></i> ${esc(r.formatted_requested_at)}
            </div>
          </td>
          <td>
            <div style="font-size:0.82rem; color:#1e293b; font-weight:700;">
              <i class="fa fa-map-marker-alt mr-1 text-primary"></i> ${esc(r.tray_polyhouse_name || 'PH')} &bull; Bed ${esc(r.tray_bed_no || '-')}
            </div>
            <div style="font-size:0.77rem; color:#334155; margin-top:2px;">
              Batch: <b>${esc(r.scanned_stockorderno || '-')}</b> (${esc(r.qtypertray || 126)} Plants)
            </div>
            ${r.scanned_psono ? `
            <div style="font-size:0.77rem; color:#4338ca; font-weight:700; margin-top:3px;">
              <i class="fa fa-cube mr-1"></i>From: ${esc(r.scanned_psono)}${r.scanned_sono ? ` (${esc(r.scanned_sono)})` : ''}
            </div>` : ''}
            <div style="font-size:0.74rem; color:#b45309; font-weight:700; margin-top:4px; background:#fffbeb; padding:2px 7px; border-radius:4px; display:inline-block; border:1px solid #fef3c7;">
              <i class="fa fa-calendar-alt mr-1"></i>Dispatch: <b>${esc(r.formatted_scanned_deliverydate || '-')}</b>
            </div>
          </td>
          <td>
            <div style="font-weight:700; color:#1e293b; font-size:0.9rem;">${esc(r.target_psono)}</div>
            ${r.target_sono ? `<div style="font-size:0.78rem; color:#64748b;">SO: ${esc(r.target_sono)}</div>` : ''}
            <div style="font-size:0.8rem; color:#475569; margin-top:2px;">
              <i class="fa fa-user mr-1 text-muted"></i> ${esc(r.target_clientname || r.target_clientid || 'Customer')}
            </div>
            <div style="font-size:0.74rem; color:#0369a1; font-weight:700; margin-top:4px; background:#f0f9ff; padding:2px 7px; border-radius:4px; display:inline-block; border:1px solid #e0f2fe;">
              <i class="fa fa-calendar-alt mr-1"></i>Dispatch: <b>${esc(r.formatted_target_deliverydate || '-')}</b>
            </div>
          </td>
          <td>
            <div style="margin-bottom: 4px;">
              <span style="font-size:0.75rem; color:#64748b; font-weight:700; display:block;">Target Expected:</span>
              <span class="crop-pill crop-pill-target">${esc(r.target_productname)} ${r.target_variety ? `(${esc(r.target_variety)})` : ''}</span>
            </div>
            <div>
              <span style="font-size:0.75rem; color:#64748b; font-weight:700; display:block;">Scanned Crop:</span>
              <span class="crop-pill crop-pill-scanned">${esc(r.scanned_productname)} ${r.scanned_variety ? `(${esc(r.scanned_variety)})` : ''}</span>
            </div>
          </td>
          <td>
            <div style="font-size:0.84rem; color:#334155; margin-bottom:4px; max-width:280px; line-height:1.4;">
              <em>"${esc(r.reason || 'No reason provided')}"</em>
            </div>
            <div style="font-size:0.76rem; color:#64748b;">
              Requested by: <b>${esc(r.requested_by)}</b>
            </div>
          </td>
          <td>
            <div>${statusBadge} ${loadedBadge}</div>
            ${r.approved_by ? `
              <div style="font-size:0.75rem; color:#64748b; margin-top:4px;">
                by ${esc(r.approved_by)}<br>
                <span style="color:#94a3b8;">${esc(r.formatted_approved_at || '')}</span>
              </div>
            ` : ''}
          </td>
          <td style="text-align: right;">
            ${actionsHtml}
          </td>
        </tr>
      `;
    });

    $tbody.html(html);
  }

  // Decision Modal: Approve or Reject
  window.openDecisionModal = function (requestId, decision, requestNo, barcode) {
    const isApprove = (decision === 'approved');
    const title = isApprove ? 'Approve Variety Change' : 'Reject Variety Change';
    const confirmColor = isApprove ? '#10b981' : '#ef4444';
    const confirmIcon = isApprove ? 'fa-check' : 'fa-times';
    const actionVerb = isApprove ? 'Approve Request' : 'Reject Request';
    const r = requestsData.find(item => Number(item.id) === Number(requestId)) || {};

    Swal.fire({
      title: title,
      width: '540px',
      html: `
        <div style="text-align:left; font-size:0.9rem; color:#334155;">
          <!-- Request Header Pill -->
          <div style="display:flex; justify-content:space-between; align-items:center; background:#f1f5f9; border-radius:6px; padding:6px 10px; margin-bottom:12px; font-size:0.82rem;">
            <span><i class="fa fa-tag mr-1 text-primary"></i>Request: <b>${esc(requestNo)}</b></span>
            <span><i class="fa fa-map-marker-alt mr-1 text-muted"></i>${esc(r.tray_polyhouse_name || 'PH')} &bull; Bed ${esc(r.tray_bed_no || '-')}</span>
          </div>

          <!-- Comparison Card with Dispatch Dates -->
          <div style="background:#ffffff; border:1.5px solid #e2e8f0; border-radius:8px; padding:10px 12px; margin-bottom:12px; font-size:0.82rem; line-height:1.5;">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:8px; padding-bottom:8px; border-bottom:1px dashed #cbd5e1;">
              <div>
                <span style="font-size:0.72rem; font-weight:800; color:#0369a1; text-transform:uppercase; display:block;">Target Order</span>
                <b style="color:#0f172a; font-size:0.88rem;">${esc(r.target_psono || '-')}</b>
                <div style="font-size:0.77rem; color:#475569;">${esc(r.target_clientname || 'Customer')}</div>
                <div style="font-size:0.76rem; color:#0369a1; font-weight:700; margin-top:3px; background:#f0f9ff; padding:2px 6px; border-radius:4px; display:inline-block; border:1px solid #e0f2fe;">
                  <i class="fa fa-calendar-alt mr-1"></i>Dispatch: ${esc(r.formatted_target_deliverydate || '-')}
                </div>
              </div>
              <div>
                <span style="font-size:0.72rem; font-weight:800; color:#b45309; text-transform:uppercase; display:block;">Scanned Origin Order</span>
                <b style="color:#0f172a; font-size:0.88rem;">${esc(r.scanned_psono || 'Batch ' + (r.scanned_stockorderno || '-'))}</b>
                <div style="font-size:0.77rem; color:#475569;">Batch: <b>${esc(r.scanned_stockorderno || '-')}</b> (${esc(r.qtypertray || 126)} Pl)</div>
                <div style="font-size:0.76rem; color:#b45309; font-weight:700; margin-top:3px; background:#fffbeb; padding:2px 6px; border-radius:4px; display:inline-block; border:1px solid #fef3c7;">
                  <i class="fa fa-calendar-alt mr-1"></i>Dispatch: ${esc(r.formatted_scanned_deliverydate || '-')}
                </div>
              </div>
            </div>
            <div style="margin-bottom:5px;">
              <span class="text-muted" style="font-size:0.74rem; font-weight:700; display:block;">Target Expected:</span>
              <span class="crop-pill crop-pill-target" style="font-size:0.8rem;">${esc(r.target_productname)} ${r.target_variety ? `(${esc(r.target_variety)})` : ''}</span>
            </div>
            <div>
              <span class="text-muted" style="font-size:0.74rem; font-weight:700; display:block;">Scanned Variety:</span>
              <span class="crop-pill crop-pill-scanned" style="font-size:0.8rem;">${esc(r.scanned_productname)} ${r.scanned_variety ? `(${esc(r.scanned_variety)})` : ''}</span>
            </div>
          </div>

          <div class="form-group mb-2">
            <label style="font-weight:600; font-size:0.85rem; display:block; margin-bottom:4px;">
              Manager Remarks / Notes ${isApprove ? '(Optional)' : '<span class="text-danger">*</span>'}:
            </label>
            <textarea id="swalRemarks" class="form-control" rows="2" placeholder="${isApprove ? 'e.g. Approved as per customer confirmation...' : 'e.g. Variety substitution not authorized for this client...'}" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px; font-size:0.86rem; box-sizing:border-box; resize:vertical;"></textarea>
          </div>
          ${isApprove ? `
            <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:6px; padding:8px 12px; font-size:0.82rem; color:#166534;">
              <i class="fa fa-info-circle mr-1"></i> Once approved, the operator will be allowed to scan and load trays from this batch immediately on <b>Ready to Load</b>.
            </div>
          ` : `
            <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:6px; padding:8px 12px; font-size:0.82rem; color:#991b1b;">
              <i class="fa fa-exclamation-triangle mr-1"></i> Once rejected, trays from this batch will remain blocked from being loaded for this order.
            </div>
          `}
        </div>
      `,
      icon: isApprove ? 'question' : 'warning',
      showCancelButton: true,
      confirmButtonText: `<i class="fa ${confirmIcon} mr-1"></i> ${actionVerb}`,
      cancelButtonText: 'Cancel',
      confirmButtonColor: confirmColor,
      cancelButtonColor: '#64748b',
      preConfirm: () => {
        const remarks = $.trim($('#swalRemarks').val() || '');
        if (!isApprove && !remarks) {
          Swal.showValidationMessage('Please provide rejection remarks.');
          return false;
        }
        return remarks;
      }
    }).then((result) => {
      if (!result.isConfirmed) return;
      const remarks = result.value;

      Swal.fire({
        title: 'Saving Decision...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
      });

      $.ajax({
        url: 'model/readytoload_model.php',
        type: 'POST',
        dataType: 'json',
        data: {
          action: 'action_variety_approval',
          request_id: requestId,
          decision: decision,
          remarks: remarks
        },
        success: function (res) {
          if (res.status === 'success') {
            Swal.fire({
              icon: 'success',
              title: isApprove ? 'Request Approved!' : 'Request Rejected',
              text: res.message || 'Decision updated successfully.',
              confirmButtonColor: '#4f46e5'
            });
            loadApprovalsList();
          } else {
            Swal.fire('Error', res.message || 'Could not update decision.', 'error');
          }
        },
        error: function (xhr, status, err) {
          Swal.fire('Error', 'Communication error: ' + err, 'error');
        }
      });
    });
  };

  // View Details Modal
  window.viewRequestDetails = function (requestId) {
    const r = requestsData.find(item => Number(item.id) === Number(requestId));
    if (!r) return;

    Swal.fire({
      title: `Audit Log: ${esc(r.request_no)}`,
      width: '540px',
      html: `
        <div style="text-align:left; font-size:0.88rem; color:#334155; line-height:1.6;">
          <div style="background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:8px; padding:10px 12px; margin-bottom:12px;">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:8px; padding-bottom:8px; border-bottom:1px dashed #cbd5e1;">
              <div>
                <span style="font-size:0.72rem; font-weight:800; color:#0369a1; text-transform:uppercase; display:block;">Target Order</span>
                <b style="color:#0f172a; font-size:0.88rem;">${esc(r.target_psono)}</b> ${r.target_sono ? `<small class="text-muted">(SO: ${esc(r.target_sono)})</small>` : ''}
                <div style="font-size:0.77rem; color:#475569;">${esc(r.target_clientname || r.target_clientid || 'Customer')}</div>
                <div style="font-size:0.76rem; color:#0369a1; font-weight:700; margin-top:3px; background:#f0f9ff; padding:2px 6px; border-radius:4px; display:inline-block; border:1px solid #e0f2fe;">
                  <i class="fa fa-calendar-alt mr-1"></i>Dispatch: ${esc(r.formatted_target_deliverydate || '-')}
                </div>
              </div>
              <div>
                <span style="font-size:0.72rem; font-weight:800; color:#b45309; text-transform:uppercase; display:block;">Scanned Origin Order</span>
                <b style="color:#0f172a; font-size:0.88rem;">${esc(r.scanned_psono || '-')}</b> ${r.scanned_sono ? `<small class="text-muted">(SO: ${esc(r.scanned_sono)})</small>` : ''}
                <div style="font-size:0.77rem; color:#475569;">Batch: <b>${esc(r.scanned_stockorderno || '-')}</b></div>
                <div style="font-size:0.76rem; color:#b45309; font-weight:700; margin-top:3px; background:#fffbeb; padding:2px 6px; border-radius:4px; display:inline-block; border:1px solid #fef3c7;">
                  <i class="fa fa-calendar-alt mr-1"></i>Dispatch: ${esc(r.formatted_scanned_deliverydate || '-')}
                </div>
              </div>
            </div>
            <div>
              <b>Location:</b> ${esc(r.tray_polyhouse_name || 'PH')} / Bed ${esc(r.tray_bed_no || '-')} &bull; <b>Qty:</b> ${esc(r.qtypertray || 126)} Plants
            </div>
          </div>
          <div style="margin-bottom:10px;">
            <b>Target Expected:</b> <span class="badge bg-primary text-white" style="background:#2563eb; color:#fff; padding:2px 6px; border-radius:4px;">${esc(r.target_productname)} ${r.target_variety ? `(${esc(r.target_variety)})` : ''}</span><br>
            <b>Scanned Crop:</b> <span class="badge bg-danger text-white" style="background:#dc2626; color:#fff; padding:2px 6px; border-radius:4px; margin-top:4px; display:inline-block;">${esc(r.scanned_productname)} ${r.scanned_variety ? `(${esc(r.scanned_variety)})` : ''}</span>
          </div>
          <div style="margin-bottom:10px;">
            <b>Request Reason:</b> <em>"${esc(r.reason || 'None')}"</em><br>
            <small class="text-muted">Submitted by ${esc(r.requested_by)} on ${esc(r.formatted_requested_at)}</small>
          </div>
          <hr style="border:none; border-top:1px solid #e2e8f0; margin:10px 0;">
          <div>
            <b>Decision:</b> <span class="badge-status badge-status-${esc(r.status)}">${esc(r.status)}</span><br>
            <b>Decided By:</b> ${esc(r.approved_by || '-')}<br>
            <b>Decision Date:</b> ${esc(r.formatted_approved_at || '-')}<br>
            <b>Remarks:</b> <em>"${esc(r.approval_remarks || 'None')}"</em>
          </div>
          ${r.is_loaded == 1 ? `
            <div style="margin-top:10px; background:#ecfdf5; border:1px solid #a7f3d0; border-radius:6px; padding:8px 12px; color:#065f46;">
              <i class="fa fa-truck mr-1"></i> <b>Loaded on Truck:</b> ${esc(r.formatted_loaded_at)}
            </div>
          ` : ''}
        </div>
      `,
      confirmButtonColor: '#4f46e5',
      confirmButtonText: 'Close'
    });
  };

  // Init
  $(document).ready(function () {
    loadApprovalsList();

    $('#inputTableSearch').on('input', function () {
      clearTimeout(searchDebounceTimer);
      searchDebounceTimer = setTimeout(function () {
        loadApprovalsList();
      }, 250);
    });

    // Auto-refresh every 30 seconds
    setInterval(function () {
      loadApprovalsList();
    }, 30000);
  });

})(jQuery);
</script>

<?php
if (!isset($_GET['dz'])) {
    require_once(__DIR__ . '/../footer.php');
}
?>
</body>
</html>
