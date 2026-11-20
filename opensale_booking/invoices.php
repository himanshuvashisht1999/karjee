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
  <title>Open Sale Invoices & Dispatches Register | Karjee Farm</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

  <!-- Google Fonts & Font Awesome -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Module CSS -->
  <link rel="stylesheet" href="assets/opensale_booking.css">
  <style>
    .kpi-invoices-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 1rem;
      margin-bottom: 1.25rem;
    }
    .kpi-inv-card {
      background: #fff;
      border: 1px solid var(--slate-200);
      border-radius: 12px;
      padding: 1rem 1.25rem;
      box-shadow: 0 1px 3px rgba(0,0,0,0.03);
      display: flex;
      align-items: center;
      gap: 1rem;
    }
    .kpi-inv-icon {
      width: 44px;
      height: 44px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.25rem;
      flex-shrink: 0;
    }
    .kpi-inv-title {
      font-size: 0.76rem;
      font-weight: 700;
      color: var(--slate-600);
      text-transform: uppercase;
      letter-spacing: 0.03em;
    }
    .kpi-inv-val {
      font-size: 1.35rem;
      font-weight: 800;
      color: var(--slate-900);
      line-height: 1.2;
    }
  </style>
</head>
<body>

<div class="osb-layout">

  <!-- Top Hero Header -->
  <div class="osb-hero">
    <div class="osb-hero-left">
      <h1>
        <i class="fa fa-file-invoice-dollar" style="color: #38bdf8;"></i>
        Open Sale Invoices & Sales Register
      </h1>
      <p>Billing history, tax invoices, customer balance tracking and payment receipts for direct open sale dispatches</p>
    </div>
    <div class="osb-hero-actions">
      <a href="index.php" class="btn-osb-nav" style="background:#10b981; border-color:#059669;">
        <i class="fa fa-cash-register"></i> + New POS Sale
      </a>
      <a href="opensale_register.php" class="btn-osb-nav">
        <i class="fa fa-boxes-packing"></i> Trays Register & Return
      </a>
      <a href="../krf-orderandsales/opensale_stock_review.php" class="btn-osb-nav">
        <i class="fa fa-boxes-stacked"></i> Stock Review
      </a>
      <a href="../krf-orderandsales/verifiedtraymovement/index.php" class="btn-osb-nav">
        <i class="fa fa-qrcode"></i> Tray Scanner
      </a>
    </div>
  </div>

  <!-- KPI Cards -->
  <div class="kpi-invoices-grid">
    <div class="kpi-inv-card">
      <div class="kpi-inv-icon" style="background:#e0e7ff; color:#4338ca;">
        <i class="fa fa-receipt"></i>
      </div>
      <div>
        <div class="kpi-inv-title">Total Orders</div>
        <div class="kpi-inv-val" id="kpiTotalOrders">0</div>
      </div>
    </div>
    <div class="kpi-inv-card">
      <div class="kpi-inv-icon" style="background:#dbeafe; color:#1d4ed8;">
        <i class="fa fa-file-invoice"></i>
      </div>
      <div>
        <div class="kpi-inv-title">Total Invoiced</div>
        <div class="kpi-inv-val" id="kpiTotalInvoiced">₹0.00</div>
      </div>
    </div>
    <div class="kpi-inv-card">
      <div class="kpi-inv-icon" style="background:#dcfce7; color:#15803d;">
        <i class="fa fa-wallet"></i>
      </div>
      <div>
        <div class="kpi-inv-title">Total Paid / Collected</div>
        <div class="kpi-inv-val" id="kpiTotalPaid" style="color:#15803d;">₹0.00</div>
      </div>
    </div>
    <div class="kpi-inv-card">
      <div class="kpi-inv-icon" style="background:#fee2e2; color:#b91c1c;">
        <i class="fa fa-hand-holding-dollar"></i>
      </div>
      <div>
        <div class="kpi-inv-title">Balance Outstanding</div>
        <div class="kpi-inv-val" id="kpiTotalBalance" style="color:#dc2626;">₹0.00</div>
      </div>
    </div>
  </div>

  <!-- Main Card -->
  <div class="osb-card">
    <div class="osb-card-head">
      <h2>
        <i class="fa fa-table-list" style="color:var(--primary);"></i>
        Invoices List
      </h2>
      <div style="display:flex; align-items:center; gap:0.6rem;">
        <div class="client-search-input-wrap" style="width: 320px;">
          <input type="text" id="invoiceSearchInput" class="client-input-field" 
                 placeholder="Search Invoice #, Client, Mobile, Order #..." />
        </div>
        <button type="button" id="btnRefreshInvoices" class="btn-osb-nav" style="background:#e2e8f0; color:#475569; border:none; padding:0.5rem 0.8rem;" title="Refresh list">
          <i class="fa fa-rotate"></i>
        </button>
      </div>
    </div>

    <div class="osb-card-body" style="padding:0;">
      <div style="overflow-x:auto;">
        <table class="invoices-data-table">
          <thead>
            <tr>
              <th style="width:40px;">#</th>
              <th>Invoice No</th>
              <th>Date</th>
              <th>Order No</th>
              <th>Client / Customer</th>
              <th>Mobile</th>
              <th style="text-align:right;">Invoice Amt</th>
              <th style="text-align:right;">Paid Amt</th>
              <th style="text-align:right;">Balance</th>
              <th style="text-align:center;">Status</th>
              <th style="text-align:center;">Actions</th>
            </tr>
          </thead>
          <tbody id="invoicesTableBody">
            <tr>
              <td colspan="11" style="text-align:center; padding:3rem 1rem; color:#64748b;">
                <i class="fa fa-spinner fa-spin fa-2x" style="color:var(--primary); margin-bottom:0.8rem; display:block;"></i>
                Loading invoices...
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<!-- MODAL: COLLECT BALANCE PAYMENT -->
<div id="modalCollectPayment" class="osb-modal-overlay">
  <div class="osb-modal">
    <div class="osb-modal-header">
      <h3><i class="fa fa-hand-holding-dollar" style="color:#10b981;"></i> Collect Balance Payment</h3>
      <button type="button" class="osb-modal-close" onclick="document.getElementById('modalCollectPayment').classList.remove('active')">&times;</button>
    </div>
    <form id="formCollectPayment">
      <input type="hidden" id="payInvNo" />
      <div class="osb-modal-body">
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:0.8rem 1rem; margin-bottom:1rem;">
          <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
            <span style="font-size:0.8rem; color:#64748b;">Invoice No:</span>
            <strong id="payInvNoDisp" style="font-family:'JetBrains Mono',monospace; color:var(--primary);">-</strong>
          </div>
          <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
            <span style="font-size:0.8rem; color:#64748b;">Client Name:</span>
            <strong id="payClientNameDisp">-</strong>
          </div>
          <div style="display:flex; justify-content:space-between;">
            <span style="font-size:0.8rem; color:#64748b;">Outstanding Balance:</span>
            <strong id="payCurBalanceDisp" style="color:#dc2626; font-size:1rem;">₹0.00</strong>
          </div>
        </div>

        <div style="margin-bottom:0.85rem;">
          <label class="form-label-osb">Payment Amount (₹) <span style="color:#ef4444;">*</span></label>
          <input type="number" id="payAmountInput" class="client-input-field" required min="1" step="0.01" style="font-weight:800; font-size:1rem;" />
        </div>

        <div style="margin-bottom:0.85rem;">
          <label class="form-label-osb">Payment Mode <span style="color:#ef4444;">*</span></label>
          <select id="payModeSelect" class="client-input-field">
            <option value="Cash">Cash</option>
            <option value="UPI">UPI / QR</option>
            <option value="Bank">NEFT / Bank Transfer</option>
            <option value="Cheque">Cheque</option>
          </select>
        </div>

        <div id="payUtrWrap" style="margin-bottom:0.85rem; display:none;">
          <label class="form-label-osb">UTR / Reference No.</label>
          <input type="text" id="payUtrInput" class="client-input-field" placeholder="Transaction ID or Cheque No." />
        </div>

        <div>
          <label class="form-label-osb">Payment Notes</label>
          <input type="text" id="payRemarksInput" class="client-input-field" placeholder="Optional remark / receiver note" />
        </div>
      </div>
      <div class="osb-modal-footer">
        <button type="button" class="btn-osb-nav" style="background:#e2e8f0; color:#475569; border:none;" onclick="document.getElementById('modalCollectPayment').classList.remove('active')">
          Cancel
        </button>
        <button type="submit" class="btn-osb-nav" style="background:#10b981; color:#fff; border:none;">
          <i class="fa fa-check"></i> Record Payment
        </button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL: EDIT INVOICE (SAME DAY ONLY) -->
<div id="modalEditInvoice" class="osb-modal-overlay">
  <div class="osb-modal" style="max-width: 760px; width: 92%;">
    <div class="osb-modal-header" style="background:#f0f9ff; border-bottom:1px solid #bae6fd;">
      <h3 style="color:#0369a1;"><i class="fa fa-pen-to-square"></i> Edit Open Sale Invoice</h3>
      <button type="button" class="osb-modal-close" onclick="closeEditModal()">&times;</button>
    </div>
    <form id="formEditInvoice">
      <input type="hidden" id="editInvNo" />
      <div class="osb-modal-body">
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:0.8rem 1rem; margin-bottom:1rem; display:flex; justify-content:space-between; flex-wrap:wrap; gap:8px;">
          <div>
            <div style="font-size:0.75rem; color:#64748b; text-transform:uppercase;">Invoice Number:</div>
            <strong id="editInvNoDisp" style="font-family:'JetBrains Mono',monospace; color:var(--primary); font-size:1rem;">-</strong>
          </div>
          <div>
            <div style="font-size:0.75rem; color:#64748b; text-transform:uppercase;">Customer / Client:</div>
            <strong id="editClientNameDisp" style="color:#1e293b;">-</strong>
          </div>
          <div>
            <div style="font-size:0.75rem; color:#64748b; text-transform:uppercase;">Invoice Date:</div>
            <strong id="editDateDisp" style="color:#059669;">-</strong>
          </div>
        </div>

        <div style="font-size:0.8rem; color:#0369a1; background:#e0f2fe; padding:6px 10px; border-radius:6px; margin-bottom:0.75rem;">
          <i class="fa fa-info-circle"></i> <strong>Automatic Tray Stock Reversal:</strong> If you reduce plant quantities, returned plants are instantly restored to their Open Sale trays for other sales.
        </div>

        <!-- Editable Items Table -->
        <table class="invoices-data-table" style="margin-bottom:1rem;">
          <thead>
            <tr>
              <th>Product / Variety</th>
              <th style="width:130px; text-align:center;">Quantity (Plants)</th>
              <th style="width:110px; text-align:right;">Rate (₹)</th>
              <th style="width:110px; text-align:right;">Total (₹)</th>
            </tr>
          </thead>
          <tbody id="editInvoiceItemsBody"></tbody>
          <tfoot>
            <tr style="background:#f1f5f9; font-weight:800;">
              <td colspan="3" style="text-align:right; padding:8px 10px;">Revised Invoice Total:</td>
              <td style="text-align:right; padding:8px 10px; color:var(--primary);" id="editRevisedTotalDisp">₹0.00</td>
            </tr>
          </tfoot>
        </table>

        <div>
          <label class="form-label-osb">Reason for Modification <span style="color:#ef4444;">*</span></label>
          <input type="text" id="editReasonInput" class="client-input-field" required placeholder="e.g. Client returned 50 plants / quantity correction" />
        </div>
      </div>
      <div class="osb-modal-footer">
        <button type="button" class="btn-osb-nav" style="background:#e2e8f0; color:#475569; border:none;" onclick="closeEditModal()">
          Cancel
        </button>
        <button type="submit" class="btn-osb-nav" style="background:#0284c7; color:#fff; border:none;">
          <i class="fa fa-floppy-disk"></i> Save Changes & Update Stock
        </button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL: CANCEL INVOICE (CREDIT NOTE OR CASH REFUND) -->
<div id="modalCancelInvoice" class="osb-modal-overlay">
  <div class="osb-modal" style="max-width: 600px; width: 90%;">
    <div class="osb-modal-header" style="background:#fef2f2; border-bottom:1px solid #fecaca;">
      <h3 style="color:#b91c1c;"><i class="fa fa-ban"></i> Cancel Invoice & Restore Stock</h3>
      <button type="button" class="osb-modal-close" onclick="closeCancelModal()">&times;</button>
    </div>
    <form id="formCancelInvoice">
      <input type="hidden" id="cancelInvNo" />
      <div class="osb-modal-body">
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:0.8rem 1rem; margin-bottom:1rem;">
          <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
            <span style="font-size:0.8rem; color:#64748b;">Invoice No:</span>
            <strong id="cancelInvNoDisp" style="font-family:'JetBrains Mono',monospace; color:var(--primary);">-</strong>
          </div>
          <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
            <span style="font-size:0.8rem; color:#64748b;">Client Name:</span>
            <strong id="cancelClientNameDisp">-</strong>
          </div>
          <div style="display:flex; justify-content:space-between;">
            <span style="font-size:0.8rem; color:#64748b;">Amount Paid by Customer:</span>
            <strong id="cancelPaidAmtDisp" style="color:#15803d; font-size:1.05rem;">₹0.00</strong>
          </div>
        </div>

        <div style="font-size:0.82rem; color:#15803d; background:#f0fdf4; border:1px solid #bbf7d0; padding:8px 10px; border-radius:6px; margin-bottom:1rem;">
          <i class="fa fa-rotate-left"></i> <strong>100% Stock Restoration:</strong> All trays deducted by this invoice will be restored back to Open Sale stock with status <code>OpenSale</code> for immediate resale.
        </div>

        <div style="margin-bottom:1rem;">
          <label class="form-label-osb" style="margin-bottom:8px; font-weight:700;">Select Refund / Settlement Option:</label>
          <div style="display:flex; flex-direction:column; gap:8px;">
            <label style="display:flex; align-items:center; gap:8px; padding:10px; border:1.5px solid #cbd5e1; border-radius:8px; cursor:pointer;" id="lblOptCreditNote">
              <input type="radio" name="refundOption" value="credit_note" checked onchange="onRefundOptionChange(this.value)" />
              <div>
                <strong>Issue Credit Note</strong> <span style="font-size:0.75rem; color:#059669; font-weight:700;">(Recommended)</span>
                <div style="font-size:0.78rem; color:#64748b;">Generates official Credit Note in <code>krf_creditnote</code> and credits client's ledger statement.</div>
              </div>
            </label>
            <label style="display:flex; align-items:center; gap:8px; padding:10px; border:1.5px solid #cbd5e1; border-radius:8px; cursor:pointer;" id="lblOptCashRefund">
              <input type="radio" name="refundOption" value="cash_refund" onchange="onRefundOptionChange(this.value)" />
              <div>
                <strong>Cash Refund</strong>
                <div style="font-size:0.78rem; color:#64748b;">Records a negative refund voucher in payment transactions for cash paid out from counter register.</div>
              </div>
            </label>
          </div>
        </div>

        <div>
          <label class="form-label-osb">Cancellation Reason <span style="color:#ef4444;">*</span></label>
          <input type="text" id="cancelReasonInput" class="client-input-field" required placeholder="e.g. Order cancelled by buyer / damaged lot returned" />
        </div>
      </div>
      <div class="osb-modal-footer">
        <button type="button" class="btn-osb-nav" style="background:#e2e8f0; color:#475569; border:none;" onclick="closeCancelModal()">
          Keep Invoice
        </button>
        <button type="submit" class="btn-osb-nav" style="background:#dc2626; color:#fff; border:none;">
          <i class="fa fa-ban"></i> Confirm Cancellation & Restore Trays
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function getApiEndpoint(query = '') {
  const loc = window.location.pathname;
  let dir = loc.substring(0, loc.lastIndexOf('/') + 1);
  if (!dir.includes('/opensale_booking/')) {
    const idx = loc.indexOf('/opensale_booking');
    if (idx !== -1) dir = loc.substring(0, idx) + '/opensale_booking/';
  }
  const base = dir + 'ajax/opensale_api.php';
  if (!query) return base;
  return base + (base.includes('?') ? '&' : '?') + (query.startsWith('?') ? query.substring(1) : query);
}

async function parseApiResponse(res) {
  const httpStatus = res.status;
  const text = await res.text();
  try {
    return JSON.parse(text);
  } catch (e) {
    let cleanText = text.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
    if (!cleanText) cleanText = `Empty response received (HTTP ${httpStatus})`;
    else if (cleanText.length > 300) cleanText = cleanText.substring(0, 300) + '...';
    throw new Error(`Server returned HTTP ${httpStatus} (Non-JSON): ${cleanText}`);
  }
}

let invoicesList = [];

function loadInvoices() {
  const tbody = document.getElementById('invoicesTableBody');
  const search = document.getElementById('invoiceSearchInput').value.trim();

  fetch(getApiEndpoint(`action=list_invoices&search=${encodeURIComponent(search)}`))
    .then(parseApiResponse)
    .then(data => {
      if (data.status === 'success') {
        invoicesList = data.invoices || [];
        renderInvoicesTable();
        updateKpis();
      } else {
        tbody.innerHTML = `<tr><td colspan="11" style="text-align:center; color:#dc2626; padding:2rem;">Failed: ${data.message}</td></tr>`;
      }
    })
    .catch(err => {
      tbody.innerHTML = `<tr><td colspan="11" style="text-align:center; color:#dc2626; padding:2rem;">Error: ${err.message}</td></tr>`;
    });
}

function updateKpis() {
  let totalOrders = invoicesList.length;
  let totalInvoiced = 0;
  let totalPaid = 0;
  let totalBalance = 0;

  invoicesList.forEach(inv => {
    totalInvoiced += parseFloat(inv.invoiceamount) || 0;
    totalPaid += parseFloat(inv.paidamount) || 0;
    totalBalance += parseFloat(inv.balanceamount) || 0;
  });

  document.getElementById('kpiTotalOrders').textContent = totalOrders.toLocaleString();
  document.getElementById('kpiTotalInvoiced').textContent = `₹${totalInvoiced.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
  document.getElementById('kpiTotalPaid').textContent = `₹${totalPaid.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
  document.getElementById('kpiTotalBalance').textContent = `₹${totalBalance.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
}

function renderInvoicesTable() {
  const tbody = document.getElementById('invoicesTableBody');
  if (invoicesList.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="11" style="text-align:center; padding:3rem 1rem; color:#94a3b8;">
          <i class="fa fa-file-invoice fa-3x" style="opacity:0.3; margin-bottom:0.8rem; display:block;"></i>
          No open sale invoices found.
        </td>
      </tr>
    `;
    return;
  }

  let html = '';
  invoicesList.forEach((inv, index) => {
    const invAmt = parseFloat(inv.invoiceamount) || 0;
    const paidAmt = parseFloat(inv.paidamount) || 0;
    const balAmt = parseFloat(inv.balanceamount) || 0;

    let statusBadge = '<span class="badge-status-pill badge-paid">PAID</span>';
    if (inv.status === 'cancelled') {
      statusBadge = '<span class="badge-status-pill" style="background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; font-weight:800;">CANCELLED</span>';
    } else if (balAmt > 0 && paidAmt > 0) {
      statusBadge = '<span class="badge-status-pill badge-partial">PARTIAL</span>';
    } else if (balAmt > 0 && paidAmt <= 0) {
      statusBadge = '<span class="badge-status-pill badge-unpaid">UNPAID</span>';
    }

    const isCancelled = (inv.status === 'cancelled');
    const isToday = !!inv.is_today;

    html += `
      <tr class="${isCancelled ? 'row-cancelled' : ''}" style="${isCancelled ? 'background:#fafafa; opacity:0.8;' : ''}">
        <td>${index + 1}</td>
        <td>
          <a href="../krf-orderandsales/modal/genrateInvoicePDF.php?invno=${encodeURIComponent(inv.invoiceno)}&companyid=${inv.companyid || 19}&subcompanyid=${inv.subcompanyid || 12}&projectid=${inv.projectid || 17}&mode=I" target="_blank" 
             style="font-family:'JetBrains Mono',monospace; font-weight:800; color:${isCancelled ? '#94a3b8' : 'var(--primary)'}; text-decoration:${isCancelled ? 'line-through' : 'none'};" title="Open Official Dispatch Invoice PDF">
            ${escapeHtml(inv.invoiceno)}
          </a>
        </td>
        <td>${escapeHtml(inv.invoicedate_fmt || '-')}</td>
        <td style="font-size:0.78rem; color:#64748b;">${escapeHtml(inv.psono || '-')}</td>
        <td style="font-weight:700; color:#1e293b;">${escapeHtml(inv.clientname || '-')}</td>
        <td style="font-size:0.8rem;">${escapeHtml(inv.mobileno || '-')}</td>
        <td style="text-align:right; font-weight:700;">₹${invAmt.toFixed(2)}</td>
        <td style="text-align:right; color:#15803d; font-weight:700;">₹${paidAmt.toFixed(2)}</td>
        <td style="text-align:right; color:${balAmt > 0 ? '#dc2626' : '#64748b'}; font-weight:700;">₹${balAmt.toFixed(2)}</td>
        <td style="text-align:center;">${statusBadge}</td>
        <td style="text-align:center;">
          <div style="display:inline-flex; gap:4px; align-items:center;">
            <a href="../krf-orderandsales/modal/genrateInvoicePDF.php?invno=${encodeURIComponent(inv.invoiceno)}&companyid=${inv.companyid || 19}&subcompanyid=${inv.subcompanyid || 12}&projectid=${inv.projectid || 17}&mode=I" target="_blank" 
               class="btn-action-sm btn-action-print" title="Print Official Dispatch Invoice PDF">
              <i class="fa fa-print"></i> Invoice
            </a>
            <a href="../krf-orderandsales/modal/gatepass_modal.php?action=view_pdf&psono=${encodeURIComponent(inv.psono)}" target="_blank" 
               class="btn-action-sm" style="background:#b45309; color:#fff;" title="Preview Official Dispatch Gate Pass">
              <i class="fa fa-id-badge"></i> Pass
            </a>
            ${!isCancelled && balAmt > 0 ? `
              <button type="button" class="btn-action-sm btn-action-pay" 
                      onclick="openCollectModal('${escapeHtml(inv.invoiceno)}', '${escapeHtml(inv.clientname)}', ${balAmt})" title="Collect Balance">
                <i class="fa fa-hand-holding-dollar"></i> Pay
              </button>
            ` : ''}

            <!-- Smart Edit / Cancel Controls -->
            ${!isCancelled ? (isToday ? `
              <button type="button" class="btn-action-sm" style="background:#0284c7; color:#fff; border:none; font-weight:700;"
                      onclick="openEditInvoiceModal('${escapeHtml(inv.invoiceno)}')" title="Edit Invoice (Same Day Only)">
                <i class="fa fa-pen-to-square"></i> Edit
              </button>
            ` : `
              <button type="button" class="btn-action-sm" style="background:#dc2626; color:#fff; border:none; font-weight:700;"
                      onclick="openCancelInvoiceModal('${escapeHtml(inv.invoiceno)}', '${escapeHtml(inv.clientname)}', ${paidAmt})" title="Cancel Invoice & Issue Credit Note / Refund">
                <i class="fa fa-ban"></i> Cancel
              </button>
            `) : ''}
          </div>
        </td>
      </tr>
    `;
  });

  tbody.innerHTML = html;
}

function openCollectModal(invoiceno, clientname, balance) {
  document.getElementById('payInvNo').value = invoiceno;
  document.getElementById('payInvNoDisp').textContent = invoiceno;
  document.getElementById('payClientNameDisp').textContent = clientname;
  document.getElementById('payCurBalanceDisp').textContent = `₹${balance.toFixed(2)}`;
  document.getElementById('payAmountInput').value = balance;
  document.getElementById('payAmountInput').max = balance;
  document.getElementById('modalCollectPayment').classList.add('active');
}

// Payment Mode toggle for UTR
document.getElementById('payModeSelect').addEventListener('change', function () {
  const isUtr = this.value !== 'Cash';
  document.getElementById('payUtrWrap').style.display = isUtr ? 'block' : 'none';
});

// Collect payment submit
document.getElementById('formCollectPayment').addEventListener('submit', function (e) {
  e.preventDefault();
  const invNo = document.getElementById('payInvNo').value;
  const amt = parseFloat(document.getElementById('payAmountInput').value) || 0;
  const mode = document.getElementById('payModeSelect').value;
  const utr = document.getElementById('payUtrInput').value.trim();
  const rem = document.getElementById('payRemarksInput').value.trim();

  if (amt <= 0) {
    alert('Please enter a valid amount.');
    return;
  }

  const btn = this.querySelector('button[type="submit"]');
  btn.disabled = true;
  btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';

  const params = new URLSearchParams({
    action: 'collect_payment',
    invoiceno: invNo,
    amount: amt,
    payment_mode: mode,
    utr_number: utr,
    remarks: rem
  });

  fetch(getApiEndpoint(), {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: params
  })
    .then(parseApiResponse)
    .then(data => {
      btn.disabled = false;
      btn.innerHTML = '<i class="fa fa-check"></i> Record Payment';
      if (data.status === 'success') {
        document.getElementById('modalCollectPayment').classList.remove('active');
        Swal.fire({
          icon: 'success',
          title: 'Payment Recorded!',
          text: data.message,
          timer: 2000,
          showConfirmButton: false
        });
        loadInvoices();
      } else {
        alert('Error: ' + data.message);
      }
    })
    .catch(err => {
      btn.disabled = false;
      btn.innerHTML = '<i class="fa fa-check"></i> Record Payment';
      alert('Network error: ' + err.message);
    });
});

// ==========================================
// EDIT INVOICE (SAME-DAY ONLY) LOGIC
// ==========================================
function closeEditModal() {
  document.getElementById('modalEditInvoice').classList.remove('active');
}

function openEditInvoiceModal(invoiceno) {
  fetch(getApiEndpoint(`action=get_invoice_details&invoiceno=${encodeURIComponent(invoiceno)}`))
    .then(parseApiResponse)
    .then(data => {
      const inv = data.invoice || (data.data ? data.data.invoice : null);
      if (data.status !== 'success' || !inv) {
        Swal.fire({
          icon: 'error',
          title: 'Unable to Load Invoice',
          text: data.message || 'Invoice record not found.'
        });
        return;
      }
      if (!inv.is_today) {
        Swal.fire({
          icon: 'warning',
          title: 'Edit Not Allowed',
          text: 'Invoices can only be modified on the same date they were created. For past dates, please cancel the invoice to generate a Credit Note or Cash Refund.'
        });
        return;
      }

      document.getElementById('editInvNo').value = inv.invoiceno;
      document.getElementById('editInvNoDisp').textContent = inv.invoiceno;
      document.getElementById('editClientNameDisp').textContent = inv.clientname || '-';
      document.getElementById('editDateDisp').textContent = inv.invoicedate || '-';
      document.getElementById('editReasonInput').value = '';

      let itemsHtml = '';
      (inv.items || []).forEach(it => {
        const lineQty = parseFloat(it.quantity) || 0;
        const lineRate = parseFloat(it.rate) || 0;
        const lineTotal = lineQty * lineRate;

        itemsHtml += `
          <tr data-item-id="${it.id}">
            <td>
              <strong style="color:#1e293b;">${escapeHtml(it.productname)}</strong>
              <div style="font-size:0.75rem; color:#64748b;">${escapeHtml(it.stockorder_no || '')}</div>
            </td>
            <td style="text-align:center;">
              <input type="number" class="client-input-field edit-item-qty" style="width:90px; text-align:center; padding:5px 6px; font-weight:700; margin:0 auto;"
                     data-id="${it.id}" data-orig-qty="${lineQty}" min="0" value="${lineQty}" oninput="recalcEditTotals()" />
              <div style="font-size:0.72rem; color:#64748b; margin-top:2px;">Orig: ${lineQty}</div>
            </td>
            <td style="text-align:right;">
              <input type="number" step="0.01" class="client-input-field edit-item-rate" style="width:90px; text-align:right; padding:5px 6px; font-weight:700; margin-left:auto;"
                     data-id="${it.id}" min="0" value="${lineRate}" oninput="recalcEditTotals()" />
            </td>
            <td style="text-align:right; font-weight:700; color:var(--primary); font-size:0.95rem;" class="edit-item-total" data-id="${it.id}">
              ₹${lineTotal.toFixed(2)}
            </td>
          </tr>
        `;
      });

      document.getElementById('editInvoiceItemsBody').innerHTML = itemsHtml;
      recalcEditTotals();
      document.getElementById('modalEditInvoice').classList.add('active');
    })
    .catch(err => {
      Swal.fire({
        icon: 'error',
        title: 'Network Error',
        text: err.message
      });
    });
}

function recalcEditTotals() {
  let grandTotal = 0;
  const rows = document.querySelectorAll('#editInvoiceItemsBody tr');
  rows.forEach(row => {
    const qtyInput = row.querySelector('.edit-item-qty');
    const rateInput = row.querySelector('.edit-item-rate');
    const totalCell = row.querySelector('.edit-item-total');

    const qty = parseFloat(qtyInput.value) || 0;
    const rate = parseFloat(rateInput.value) || 0;
    const lineTotal = qty * rate;
    grandTotal += lineTotal;

    if (totalCell) {
      totalCell.textContent = `₹${lineTotal.toFixed(2)}`;
    }
  });

  document.getElementById('editRevisedTotalDisp').textContent = `₹${grandTotal.toFixed(2)}`;
}

document.getElementById('formEditInvoice').addEventListener('submit', function (e) {
  e.preventDefault();
  const invNo = document.getElementById('editInvNo').value;
  const reason = document.getElementById('editReasonInput').value.trim();

  if (!reason) {
    Swal.fire({ icon: 'warning', title: 'Reason Required', text: 'Please enter a reason for modifying this invoice.' });
    return;
  }

  const items = [];
  const rows = document.querySelectorAll('#editInvoiceItemsBody tr');
  let hasValidQty = false;

  rows.forEach(row => {
    const qtyInput = row.querySelector('.edit-item-qty');
    const rateInput = row.querySelector('.edit-item-rate');
    const id = parseInt(qtyInput.getAttribute('data-id'), 10);
    const qty = parseInt(qtyInput.value, 10) || 0;
    const rate = parseFloat(rateInput.value) || 0;

    if (qty > 0) hasValidQty = true;
    items.push({ id: id, quantity: qty, rate: rate });
  });

  if (!hasValidQty) {
    Swal.fire({
      icon: 'warning',
      title: 'Invalid Quantities',
      text: 'Invoice must contain at least one product with quantity > 0. If you wish to void the whole sale, please use the Cancel Invoice option.'
    });
    return;
  }

  const btn = this.querySelector('button[type="submit"]');
  btn.disabled = true;
  btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Updating & Restoring Stock...';

  const formData = new URLSearchParams();
  formData.append('action', 'edit_opensale_invoice');
  formData.append('invoiceno', invNo);
  formData.append('items', JSON.stringify(items));
  formData.append('reason', reason);

  fetch(getApiEndpoint(), {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: formData
  })
    .then(parseApiResponse)
    .then(data => {
      btn.disabled = false;
      btn.innerHTML = '<i class="fa fa-floppy-disk"></i> Save Changes & Update Stock';
      if (data.status === 'success') {
        closeEditModal();
        Swal.fire({
          icon: 'success',
          title: 'Invoice Updated!',
          text: data.message,
          confirmButtonColor: '#0284c7'
        });
        loadInvoices();
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Update Failed',
          text: data.message
        });
      }
    })
    .catch(err => {
      btn.disabled = false;
      btn.innerHTML = '<i class="fa fa-floppy-disk"></i> Save Changes & Update Stock';
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Network error: ' + err.message
      });
    });
});

// ==========================================
// CANCEL INVOICE (CREDIT NOTE / CASH REFUND) LOGIC
// ==========================================
function closeCancelModal() {
  document.getElementById('modalCancelInvoice').classList.remove('active');
}

function openCancelInvoiceModal(invoiceno, clientname, paidamount) {
  document.getElementById('cancelInvNo').value = invoiceno;
  document.getElementById('cancelInvNoDisp').textContent = invoiceno;
  document.getElementById('cancelClientNameDisp').textContent = clientname || '-';
  document.getElementById('cancelPaidAmtDisp').textContent = `₹${(parseFloat(paidamount) || 0).toFixed(2)}`;
  document.getElementById('cancelReasonInput').value = '';

  const rdoCn = document.querySelector('input[name="refundOption"][value="credit_note"]');
  if (rdoCn) {
    rdoCn.checked = true;
    onRefundOptionChange('credit_note');
  }

  document.getElementById('modalCancelInvoice').classList.add('active');
}

function onRefundOptionChange(val) {
  const lblCn = document.getElementById('lblOptCreditNote');
  const lblCash = document.getElementById('lblOptCashRefund');
  if (val === 'credit_note') {
    lblCn.style.borderColor = '#0284c7';
    lblCn.style.background = '#f0f9ff';
    lblCash.style.borderColor = '#cbd5e1';
    lblCash.style.background = '#ffffff';
  } else {
    lblCash.style.borderColor = '#dc2626';
    lblCash.style.background = '#fef2f2';
    lblCn.style.borderColor = '#cbd5e1';
    lblCn.style.background = '#ffffff';
  }
}

document.getElementById('formCancelInvoice').addEventListener('submit', function (e) {
  e.preventDefault();
  const invNo = document.getElementById('cancelInvNo').value;
  const refundOpt = document.querySelector('input[name="refundOption"]:checked')?.value || 'credit_note';
  const reason = document.getElementById('cancelReasonInput').value.trim();

  if (!reason) {
    Swal.fire({ icon: 'warning', title: 'Reason Required', text: 'Please enter a cancellation reason.' });
    return;
  }

  const optText = (refundOpt === 'credit_note') ? 'Issue Official Credit Note to Client Ledger' : 'Record Counter Cash Refund';

  Swal.fire({
    title: 'Cancel Invoice & Restore Stock?',
    html: `You are about to cancel invoice <strong style="color:var(--primary);">${escapeHtml(invNo)}</strong>.<br><br>
           <ul style="text-align:left; font-size:0.88rem; color:#475569; padding-left:1.5rem; margin-bottom:0.5rem;">
             <li><strong>100% of plants</strong> will be restored to Open Sale stock for immediate resale.</li>
             <li>Settlement Method: <strong>${optText}</strong>.</li>
             <li>All related orders, allocations and gate pass records will be voided.</li>
           </ul>`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Yes, Cancel & Restore Trays'
  }).then((result) => {
    if (!result.isConfirmed) return;

    const btn = document.querySelector('#formCancelInvoice button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Cancelling & Restoring Stock...';

    const formData = new URLSearchParams();
    formData.append('action', 'cancel_opensale_invoice');
    formData.append('invoiceno', invNo);
    formData.append('refund_option', refundOpt);
    formData.append('cancel_reason', reason);

    fetch(getApiEndpoint(), {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: formData
    })
      .then(parseApiResponse)
      .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-ban"></i> Confirm Cancellation & Restore Trays';
        if (data.status === 'success') {
          closeCancelModal();
          Swal.fire({
            icon: 'success',
            title: 'Invoice Cancelled & Stock Restored!',
            text: data.message,
            confirmButtonColor: '#0284c7'
          });
          loadInvoices();
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Cancellation Failed',
            text: data.message
          });
        }
      })
      .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-ban"></i> Confirm Cancellation & Restore Trays';
        Swal.fire({
          icon: 'error',
          title: 'Network Error',
          text: err.message
        });
      });
  });
});

// Search input debounced
let searchTimer = null;
document.getElementById('invoiceSearchInput').addEventListener('input', function () {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(loadInvoices, 300);
});

document.getElementById('btnRefreshInvoices').addEventListener('click', loadInvoices);

function escapeHtml(text) {
  if (!text) return '';
  return String(text)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

document.addEventListener('DOMContentLoaded', loadInvoices);
</script>

</body>
</html>
