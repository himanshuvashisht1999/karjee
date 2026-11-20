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

$hasHeader = false;
if (isset($_GET['k'])) {
    require('../header.php');
    $hasHeader = true;
} else {
    require('../connection.php');
}

$loginroleid  = isset($loginroleid) ? (int)$loginroleid : (int)($_SESSION['loginroleid'] ?? 4);
$loginid      = $_SESSION['loginid'] ?? 'Admin';
$companyid    = isset($_SESSION['companyid']) && (int)$_SESSION['companyid'] > 0 ? (int)$_SESSION['companyid'] : 19;
$subcompanyid = isset($_SESSION['subcompanyid']) && (int)$_SESSION['subcompanyid'] > 0 ? (int)$_SESSION['subcompanyid'] : 12;
$projectid    = isset($_SESSION['projectid']) && (int)$_SESSION['projectid'] > 0 ? (int)$_SESSION['projectid'] : 17;

$openSaleCenters = [];
if (isset($krfconn) && $krfconn) {
    $resOsc = $krfconn->query("SELECT polyhouseid, polyhousename, locationid 
                               FROM krf_polyhouse_master 
                               WHERE (projectid = '{$projectid}' OR '{$projectid}' = '0' OR projectid = '17') 
                                 AND (type = 'OpenSale' OR structure_type = 'OpenSale' OR polyhousename LIKE '%Open%Sale%') 
                                 AND (status = 'Active' OR status IS NULL) 
                               ORDER BY polyhouseid ASC");
    if ($resOsc) {
        while ($r = $resOsc->fetch_assoc()) {
            $openSaleCenters[] = $r;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Open Sale Order Booking & POS | Karjee Farm</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

  <script>
    window.APP_CONFIG = {
      companyid: <?= $companyid ?>,
      subcompanyid: <?= $subcompanyid ?>,
      projectid: <?= $projectid ?>,
      loginid: <?= json_encode($loginid) ?>,
      baseUrl: <?= json_encode(rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/') ?>,
      apiUrl: <?= json_encode(rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/ajax/opensale_api.php') ?>
    };
  </script>

  <!-- Google Fonts & Font Awesome -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Module CSS -->
  <link rel="stylesheet" href="assets/opensale_booking.css?v=<?= time() ?>">
</head>
<body>

<div class="osb-layout">

  <!-- Top Hero Header -->
  <div class="osb-hero">
    <div class="osb-hero-left">
      <h1>
        <i class="fa fa-cash-register" style="color: #38bdf8;"></i>
        Open Sale POS & Order Booking
      </h1>
      <p>Instant client order booking, billing & stock deduction from Sargawa, Karjee & Mansoon open sale inventory</p>
    </div>
    <div class="osb-hero-actions">
      <a href="opensale_register.php" class="btn-osb-nav">
        <i class="fa fa-boxes-packing"></i> Trays Register & Return
      </a>
      <a href="../krf-orderandsales/opensale_stock_review.php" class="btn-osb-nav">
        <i class="fa fa-boxes-stacked"></i> Stock Review
      </a>
      <a href="invoices.php" class="btn-osb-nav">
        <i class="fa fa-file-invoice-dollar"></i> Invoices Register
      </a>
    </div>
  </div>



  <!-- Main POS Terminal Grid -->
  <div class="osb-main-grid">

    <!-- 1ST OPTION: SELECT CLIENT (SEARCH BY MOBILE NO OR NAME) -->
    <div class="osb-card" id="cardClientSection">
      <div class="osb-card-head">
        <h2>
          <i class="fa fa-user-check" style="color:var(--primary);"></i>
          1st Option: Select Client (Using Mobile No. or Name)
        </h2>
        <button type="button" id="btnRefreshClient" class="btn-refresh-client" title="Refresh Client List">
          <i class="fa fa-rotate"></i> Refresh
        </button>
      </div>
      <div class="osb-card-body">
        <div class="client-select-wrap" style="margin-bottom:0;">
          <div class="client-picker-box">
            <div id="clientSearchWrap" class="client-search-input-wrap">
              <input type="text" id="clientSearchInput" class="client-input-field" 
                     placeholder="Search client by mobile number or name (type min. 3 digits/chars)..." autocomplete="off" />
              <button type="button" id="btnOpenNewClient" class="btn-add-client-quick" title="Create New Client">
                <i class="fa fa-user-plus"></i> + New Client
              </button>
            </div>

            <!-- Autocomplete Suggestion Dropdown -->
            <div id="clientSuggestResults" class="client-autocomplete-results"></div>

            <!-- Selected Client Card Badge -->
            <div id="clientSelectedBadge" class="client-selected-badge" style="display: none;">
              <div>
                <strong id="selectedClientName">-</strong>
                <small id="selectedClientSub">-</small>
              </div>
              <button type="button" id="btnRemoveClient" class="btn-remove-client" title="Change client">
                <i class="fa fa-xmark"></i> Change Client
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 2ND OPTION: SELECT OPEN SALE CENTER / SCAN TRAY QR CODE -->
    <div class="osb-card" id="cardStockSection">
      <div class="osb-card-head">
        <h2>
          <i class="fa fa-boxes-stacked" style="color:var(--primary);"></i>
          2nd Option: Select Open Sale Center / Scan Tray QR Code
        </h2>
        <div style="font-size:0.82rem; color:#64748b;">
          Available: 
          <strong id="kpiAvailTrays" style="color:#0f172a;">0</strong> Trays 
          (<strong id="kpiAvailPlants" style="color:var(--success-dark);">0</strong> Plants)
        </div>
      </div>

      <div class="osb-card-body">

        <!-- Toolbar: Open Sale Center Selector + Search Product / Scan Tray Barcode -->
        <div class="product-selection-toolbar" style="display:flex; gap:1rem; margin-bottom:0.6rem; flex-wrap:wrap; align-items:flex-end;">
          
          <!-- 1. Select Open Sale Center Dropdown -->
          <div style="flex:1; max-width:380px; min-width:260px;">
            <label class="form-label-osb" style="display:flex; align-items:center; gap:5px; margin-bottom:5px;">
              <i class="fa fa-store" style="color:var(--primary);"></i> Select Open Sale Center:
            </label>
            <select id="openSaleCenterDropdown" class="product-select-dropdown" style="width:100%; padding:0.65rem 0.85rem; border:1px solid var(--slate-300); border-radius:8px; font-size:0.88rem; font-weight:600; color:var(--slate-800); background:#fff; outline:none; cursor:pointer;">
              <option value="">-- Select Open Sale Center --</option>
              <?php foreach ($openSaleCenters as $osc): ?>
                <option value="<?= htmlspecialchars($osc['polyhousename']) ?>" data-polyhouseid="<?= htmlspecialchars($osc['polyhouseid']) ?>" data-locationid="<?= htmlspecialchars($osc['locationid']) ?>">
                  <?= htmlspecialchars($osc['polyhousename']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- 2. Search Product Name or Scan Tray QR / Barcode -->
          <div style="flex:2; min-width:320px;">
            <label class="form-label-osb" style="display:flex; align-items:center; gap:5px; margin-bottom:5px;">
              <i class="fa fa-search" style="color:var(--primary);"></i> Search Product / Scan Tray Barcode:
            </label>
            <div class="scan-bar-container" style="margin-bottom:0;">
              <div class="scan-bar-dock" style="padding:0.45rem 0.85rem;">
                <i class="fa fa-search" style="color:var(--primary);"></i>
                <input type="text" id="scanBarcodeInput" class="scan-input-lg" 
                       placeholder="Type plant name or scan tray barcode (e.g. Tomato, Brinjal, RS...)..." autofocus autocomplete="off" />
                <button type="button" id="btnClearScan" class="btn-osb-nav" style="background:#e2e8f0; color:#475569; border:none; padding:0.4rem 0.65rem;" title="Clear search">
                  <i class="fa fa-xmark"></i>
                </button>
              </div>
              <!-- Suggested Box Below Search Input (ONLY SHOWN WHILE SEARCHING / FOCUSING) -->
              <div id="scanSuggestResults" class="scan-suggest-box" style="display: none;"></div>
            </div>
          </div>

        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:0.35rem; padding:0 4px;">
          <div style="font-size:0.76rem; color:#64748b;">
            <i class="fa fa-seedling" style="color:var(--primary);"></i> Search plant name to see available sum of qty grouped by product &bull; Click &quot;+ Add&quot; to add all plants to cart &bull; Or scan tray barcode directly
          </div>
        </div>

        <!-- Hidden catalog container for script compatibility -->
        <div id="stockCatalogGrid" style="display: none;"></div>

      </div>
    </div>

    <!-- 3. SALES ORDER CART & BILLING DOCK -->
    <div class="osb-card" id="cardBillingSection">
      <div class="osb-card-head">
        <h2>
          <i class="fa fa-receipt" style="color:var(--primary);"></i>
          3. Sales Order & Billing Dock
        </h2>
        <button type="button" id="btnClearCart" class="btn-remove-client" style="padding:4px 8px;">
          <i class="fa fa-trash"></i> Clear Cart
        </button>
      </div>

      <div class="osb-card-body">

        <!-- Order Items Cart Table -->
        <div style="margin-bottom:0.85rem;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
            <span class="form-label-osb" style="margin:0;">Cart Items</span>
            <span style="font-size:0.75rem; font-weight:700; color:var(--primary);">
              Total Plants: <span id="cartTotalPlantsCount">0</span>
            </span>
          </div>

          <div style="max-height: 240px; overflow-y: auto; border: 1px solid var(--slate-200); border-radius: 8px;">
            <table class="cart-items-table">
              <thead>
                <tr>
                  <th style="width:34%;">Item / Variety</th>
                  <th style="width:16%;">Qty</th>
                  <th style="width:14%; text-align:center;">FOC (Plants)</th>
                  <th style="width:14%;">Rate (₹)</th>
                  <th style="width:10%;">Disc%</th>
                  <th style="width:12%; text-align:right;">Total</th>
                  <th style="width:5%;"></th>
                </tr>
              </thead>
              <tbody id="cartTableBody">
                <tr id="cartEmptyRow">
                  <td colspan="7" style="text-align: center; padding: 2.5rem 1rem; color: #94a3b8;">
                    <i class="fa fa-cart-shopping fa-2x" style="opacity: 0.3; margin-bottom: 0.5rem; display:block;"></i>
                    Cart is empty. Scan a barcode or select plants from catalog to begin.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- 3.5 Box Packing Charges Section (Billable Line Item) -->
        <div class="box-packing-card" id="boxPackingCard">
          <div class="box-packing-head">
            <div class="box-packing-head-left">
              <label class="dinv-toggle-switch" for="chkEnableBoxPacking" title="Toggle Box Packing Charges in Bill">
                <input type="checkbox" id="chkEnableBoxPacking">
                <span class="dinv-toggle-track">
                  <span class="dinv-toggle-thumb"></span>
                </span>
              </label>
              <label class="box-packing-title" for="chkEnableBoxPacking">
                <i class="fa fa-box-open" style="color:var(--primary);"></i> Add Box Packing Charges in Bill
              </label>
              <span class="box-rate-badge" id="boxRateBadge">₹ 100.00 / Box</span>
            </div>
            <div>
              <button type="button" class="btn-box-auto-calc" id="btnAutoCalcBoxes" title="Auto calculate boxes from Total Dispatch Plants (500 Plants/Box)">
                <i class="fa fa-calculator"></i> Auto Calculate Boxes
              </button>
            </div>
          </div>

          <div class="box-packing-body" id="boxPackingBody" style="display:none;">
            <div class="box-packing-grid">
              <!-- Total Dispatch Plants -->
              <div class="box-packing-col">
                <label class="box-col-label">Total Dispatch Plants</label>
                <div class="box-input-group">
                  <input type="text" id="cartBoxDispatchPlantsDisplay" class="box-field-readonly text-center" value="0 Plants" readonly>
                </div>
                <div class="box-col-hint" id="cartBoxCapacityHint">Capacity: 500 plants / box</div>
              </div>

              <!-- No. of Boxes -->
              <div class="box-packing-col">
                <label class="box-col-label text-dark">No. of Boxes <span style="color:#ef4444;">*</span></label>
                <div class="box-input-group">
                  <span class="box-input-prefix"><i class="fa fa-boxes-stacked" style="color:#64748b;"></i></span>
                  <input type="number" id="cartBoxCount" class="box-field-input text-center text-primary" min="0" step="1" value="0" placeholder="0">
                  <span class="box-input-suffix">Boxes</span>
                </div>
                <div class="box-col-hint">Enter or auto-calculate boxes</div>
              </div>

              <!-- Packing Rate / Box -->
              <div class="box-packing-col">
                <label class="box-col-label">Packing Rate / Box</label>
                <div class="box-input-group">
                  <span class="box-input-prefix">₹</span>
                  <input type="number" id="cartBoxRate" class="box-field-input text-center" min="0" step="any" value="100.00">
                  <span class="box-input-suffix">/ Box</span>
                </div>
                <div class="box-col-hint" id="cartBoxFixedRateHint">Fixed charge: ₹100 per box</div>
              </div>

              <!-- Total Box Packing Charges -->
              <div class="box-packing-col">
                <label class="box-col-label text-success">Total Box Packing Charges</label>
                <div class="box-input-group box-total-group">
                  <span class="box-input-prefix box-prefix-success">₹</span>
                  <input type="text" id="cartBoxTotalCharge" class="box-field-total text-end" value="0.00" readonly>
                </div>
                <div class="box-col-hint text-success" id="cartBoxFormulaText">0 boxes × ₹ 100.00 = ₹ 0.00</div>
              </div>
            </div>

            <!-- Info alert banner -->
            <div class="box-packing-alert">
              <i class="fa fa-info-circle" style="color:var(--primary); font-size:14px; margin-top:2px;"></i>
              <div>
                When enabled, box packing charge is added as a billable item into the dispatched invoice (<code style="color:#b91c1c;">krf_sales_order_items</code>) and automatically included in taxable value and grand total.
              </div>
            </div>
          </div>
        </div>

        <!-- Billing Summary Calculation -->
        <div class="bill-summary-box">
          <div class="bill-summary-row">
            <span>Taxable Subtotal:</span>
            <strong id="billSubtotal">₹0.00</strong>
          </div>
          <div class="bill-summary-row">
            <span>Total Item Discount:</span>
            <span id="billTotalDiscount" style="color:#059669;">-₹0.00</span>
          </div>
          <div class="bill-summary-row">
            <span>Tax / GST:</span>
            <span id="billTaxAmount">₹0.00</span>
          </div>
          <div class="bill-summary-row">
            <span>Round Off:</span>
            <span id="billRoundOff">₹0.00</span>
          </div>
          <div class="bill-summary-row" id="billBoxPackingRow" style="display:none;">
            <span>Box Packing Charges (<span id="billBoxCountLabel">0</span> Boxes):</span>
            <span id="billBoxPackingAmount" style="color:var(--primary); font-weight:700;">+₹0.00</span>
          </div>
          <div class="bill-summary-row" id="billSpecialDiscountRow" style="display:none;">
            <span>Special Discount:</span>
            <span id="billSpecialDiscount" style="color:#ea580c; font-weight:700;">-₹0.00</span>
          </div>
          <div class="bill-total-row">
            <span>Grand Total:</span>
            <span id="billGrandTotal" style="color:var(--primary);">₹0.00</span>
          </div>
          <div class="bill-summary-row" id="billPendingOrdersRow" style="display:none; margin-top:6px;">
            <span>Previous Pending Orders:</span>
            <span id="billPendingOrdersTotal" style="color:#dc2626; font-weight:700;">+₹0.00</span>
          </div>
          <div class="bill-total-row" id="billTotalPayableRow" style="border-top:1.5px solid #2563eb; color:#1e3a8a; margin-top:8px; padding-top:8px; font-size:1.18rem;">
            <span>Total Payable Amount:</span>
            <span id="billTotalPayableAmount" style="color:#2563eb; font-weight:800;">₹0.00</span>
          </div>
        </div>

        <!-- Client Pending Orders For Payment Section (Displayed Above PAYMENT COLLECTION) -->
        <div id="pendingOrdersSection" class="pending-orders-section" style="display:none;">
          <div class="pending-orders-header">
            <div class="pending-orders-title">
              <i class="fa fa-clock-rotate-left" style="color:var(--primary);"></i>
              <span>Pending Orders for Payment</span>
              <span id="pendingOrdersCountBadge" class="badge-pending-count">0 Pending</span>
            </div>
            <div class="pending-orders-hint">
              <i class="fa fa-info-circle"></i> Check order(s) below to add their balance into payable amount and record payment in this transaction
            </div>
          </div>

          <div class="pending-orders-table-wrap">
            <table class="pending-orders-table">
              <thead>
                <tr>
                  <th style="width: 38px; text-align: center;">
                    <input type="checkbox" id="chkSelectAllPendingOrders" title="Select / Deselect All Pending Orders" />
                  </th>
                  <th>Order No (PSO / SO)</th>
                  <th>Product Name</th>
                  <th>Expected Delivery</th>
                  <th style="text-align: right;">Qty</th>
                  <th style="text-align: right;">Total Order (₹)</th>
                  <th style="text-align: right;">Paid (₹)</th>
                  <th style="text-align: right;">Pending Due (₹)</th>
                  <th style="text-align: right; width: 130px;">Pay Amount (₹)</th>
                </tr>
              </thead>
              <tbody id="pendingOrdersTableBody">
                <!-- Rendered dynamically on client selection -->
              </tbody>
              <tfoot id="pendingOrdersTableFoot" style="display:none;">
                <tr style="background:#f1f5f9; border-top:1.5px solid #cbd5e1;">
                  <td colspan="7" style="text-align: right; font-weight: 700; color:#334155; padding:8px 10px;">
                    Selected Pending Orders Total Payment:
                  </td>
                  <td colspan="2" style="text-align: right; font-weight: 800; color:var(--primary); font-size:0.95rem; padding:8px 10px;" id="totalSelectedPendingPayDisplay">
                    ₹0.00
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <!-- Payment Collection Panel -->
        <div class="payment-panel">
          <div class="payment-panel-head">
            <span class="payment-panel-title">
              <i class="fa fa-wallet" style="color:var(--primary);"></i> PAYMENT COLLECTION
            </span>
          </div>

          <div class="payment-panel-body">
            <!-- Left: Payment Modes & UTR -->
            <div class="payment-modes-col">
              <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px; flex-wrap:wrap; gap:6px;">
                <label class="pay-col-label" style="margin:0;">Select Payment Method</label>
                <label class="multi-mode-toggle-label" title="Enable to receive amount across multiple payment modes simultaneously">
                  <input type="checkbox" id="chkMultiPaymentMode" onchange="OpenSalePOS.toggleMultiPaymentMode(this.checked)" />
                  <span><i class="fa fa-layer-group"></i> Multiple Payment Modes</span>
                </label>
              </div>
              <div class="payment-modes-grid" id="paymentModesGrid">
                <button type="button" class="pay-mode-btn active" data-mode="Cash">
                  <i class="fa fa-money-bill-wave"></i> Cash
                </button>
                <button type="button" class="pay-mode-btn" data-mode="UPI">
                  <i class="fa fa-qrcode"></i> UPI / QR
                </button>
                <button type="button" class="pay-mode-btn" data-mode="Bank">
                  <i class="fa fa-building-columns"></i> NEFT / Bank
                </button>
                <button type="button" class="pay-mode-btn" data-mode="Cheque">
                  <i class="fa fa-money-check"></i> Cheque
                </button>
              </div>

              <!-- Multi-Payment Mode Breakdown Box (Shown when Multiple Payment Modes is checked) -->
              <div id="multiPaymentBreakdown" class="multi-pay-breakdown" style="display:none; margin-top:0.65rem;">
                <div style="font-size:0.75rem; font-weight:700; color:#334155; margin-bottom:8px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:6px;">
                  <span><i class="fa fa-layer-group" style="color:var(--primary);"></i> Active Payment Mode Splits</span>
                  <div style="display:flex; gap:5px; flex-wrap:wrap;">
                    <button type="button" class="btn-add-split-mode" onclick="OpenSalePOS.addSplitRow('UPI')" style="background:#eef2ff; color:#4338ca; border:1px solid #c7d2fe; padding:3px 8px; border-radius:6px; font-size:0.73rem; font-weight:700; cursor:pointer;" title="Add another UPI payment">
                      <i class="fa fa-plus"></i> UPI
                    </button>
                    <button type="button" class="btn-add-split-mode" onclick="OpenSalePOS.addSplitRow('Bank')" style="background:#f0f9ff; color:#0369a1; border:1px solid #bae6fd; padding:3px 8px; border-radius:6px; font-size:0.73rem; font-weight:700; cursor:pointer;" title="Add another NEFT / Bank transfer">
                      <i class="fa fa-plus"></i> Bank/NEFT
                    </button>
                    <button type="button" class="btn-add-split-mode" onclick="OpenSalePOS.addSplitRow('Cheque')" style="background:#fffbeb; color:#b45309; border:1px solid #fde68a; padding:3px 8px; border-radius:6px; font-size:0.73rem; font-weight:700; cursor:pointer;" title="Add another Cheque">
                      <i class="fa fa-plus"></i> Cheque
                    </button>
                    <button type="button" class="btn-add-split-mode" onclick="OpenSalePOS.addSplitRow('Cash')" style="background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; padding:3px 8px; border-radius:6px; font-size:0.73rem; font-weight:700; cursor:pointer;" title="Add Cash payment">
                      <i class="fa fa-plus"></i> Cash
                    </button>
                  </div>
                </div>
                <div id="multiPayRowsContainer" class="multi-pay-rows-wrap"></div>
              </div>

              <!-- UTR / Reference (Shown when UPI / Bank / Cheque in single mode) -->
              <div id="utrFieldWrap" style="display:none; margin-top:0.65rem;">
                <label class="pay-col-label">UTR / Reference / Cheque No.</label>
                <input type="text" id="cartUtrNo" class="client-input-field" placeholder="Enter transaction reference / UTR / Cheque No." style="background:#fff; width:100%;" oninput="OpenSalePOS.onSingleUtrInput(this.value)" />
                <div id="singleUtrStatus" class="utr-status-badge" style="display:none;"></div>
              </div>

              <!-- Single Mode Payment Document Upload (Shown when single payment mode is active) -->
              <div id="singleDocAttachWrap" style="margin-top:0.65rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                  <label class="pay-col-label" style="margin:0;">
                    <i class="fa fa-paperclip" style="color:var(--primary);"></i> Payment Document / Proof
                  </label>
                  <span style="font-size:0.7rem; color:#64748b;">(Image/PDF)</span>
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                  <label class="btn-attach-doc" id="lblSingleDocAttach" style="cursor:pointer;">
                    <input type="file" id="fileSingleDoc" accept="image/*,.pdf" style="display:none;" onchange="OpenSalePOS.onSingleDocFileSelected(this)" />
                    <i class="fa fa-arrow-up-from-bracket"></i> <span>Attach Receipt / Proof</span>
                  </label>
                  <div id="singleDocPreviewBadge" class="attached-doc-badge" style="display:none;">
                    <i class="fa fa-file-lines" style="color:var(--primary);"></i>
                    <span id="singleDocName" class="doc-name" title="">-</span>
                    <button type="button" class="btn-doc-preview-pill" onclick="OpenSalePOS.previewSingleDoc()" title="Preview Document">
                      <i class="fa fa-eye"></i> Preview
                    </button>
                    <button type="button" class="btn-doc-clear-pill" onclick="OpenSalePOS.clearSingleDoc()" title="Remove Document">&times;</button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Right: Special Discount, Amount Paid & Balance Due -->
            <div class="payment-figures-col">
              <!-- Payable Amount Display Card -->
              <div class="pay-figure-card" style="background:#eff6ff; border-color:#bfdbfe;">
                <div class="pay-figure-head">
                  <label class="pay-col-label" style="margin:0; color:#1e40af; font-weight:800;">Payable Amount (₹)</label>
                </div>
                <div id="payFigureTotalPayable" class="pay-balance-display" style="color:#1d4ed8; background:transparent;">
                  ₹0.00
                </div>
              </div>

              <!-- Special Discount -->
              <div class="pay-figure-card">
                <div class="pay-figure-head">
                  <label class="pay-col-label" style="margin:0;">Special Discount (₹)</label>
                </div>
                <div class="pay-input-wrap">
                  <span class="pay-currency-prefix" style="color:#ea580c;">₹</span>
                  <input type="number" id="cartSpecialDiscount" class="pay-amount-input" value="0" min="0" step="any" placeholder="0" style="color:#c2410c;" />
                </div>
              </div>

              <!-- Amount Paid -->
              <div class="pay-figure-card">
                <div class="pay-figure-head">
                  <label class="pay-col-label" style="margin:0;">Amount Paid (₹)</label>
                  <button type="button" id="btnFullPaid" class="btn-full-paid-link" title="Set paid amount equal to payable amount">
                    <i class="fa fa-check-double"></i> Full Paid
                  </button>
                </div>
                <div class="pay-input-wrap">
                  <span class="pay-currency-prefix">₹</span>
                  <input type="number" id="cartPaidAmount" class="pay-amount-input" value="0" min="0" step="any" />
                </div>
              </div>

              <!-- Balance Due -->
              <div class="pay-figure-card">
                <div class="pay-figure-head">
                  <label class="pay-col-label" style="margin:0;">Balance Due (₹)</label>
                </div>
                <div id="billBalanceAmount" class="pay-balance-display">
                  ₹0.00
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Order / Sale Remarks Section -->
        <div class="order-remarks-section" style="margin-top:0.85rem; background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:0.85rem 1rem;">
          <label class="form-label-osb" for="cartRemarks" style="display:flex; align-items:center; gap:6px; margin-bottom:6px; font-weight:700; color:#334155; font-size:0.85rem;">
            <i class="fa fa-comment-dots" style="color:var(--primary);"></i> Sale / Order Remarks (Optional):
          </label>
          <textarea id="cartRemarks" class="client-input-field" rows="2" 
                    placeholder="Enter any special remarks, instructions, notes or delivery terms for this sale..." 
                    style="width:100%; border:1px solid var(--slate-300); border-radius:8px; padding:0.55rem 0.8rem; font-size:0.85rem; font-family:inherit; resize:vertical; outline:none; background:#f8fafc; color:#1e293b;"></textarea>
        </div>

        <!-- Checkout & Page Actions (Centered, Not Full Width) -->
        <div class="checkout-actions-row">
          <button type="button" id="btnBookOrder" class="btn-book-order" disabled>
            <i class="fa fa-check-circle fa-lg"></i> Complete Sale & Generate Invoice
          </button>
          <button type="button" id="btnFullRefreshPage" class="btn-full-refresh" title="Reload / Refresh entire page" onclick="window.location.reload();">
            <i class="fa fa-arrows-rotate"></i> Refresh Page
          </button>
        </div>

      </div>
    </div>

  </div>

</div>

<!-- MODAL: ADD NEW CLIENT -->
<div id="modalNewClient" class="osb-modal-overlay">
  <div class="osb-modal">
    <div class="osb-modal-header">
      <h3><i class="fa fa-user-plus" style="color:var(--primary);"></i> Add New Client</h3>
      <button type="button" id="btnCloseNewClient" class="osb-modal-close">&times;</button>
    </div>
    <form id="formNewClient">
      <div class="osb-modal-body">
        <div class="client-modal-grid">
          <!-- Client Name (Full Width) -->
          <div class="modal-field-span2">
            <label class="form-label-osb">Client / Farmer Name <span style="color:#ef4444;">*</span></label>
            <input type="text" id="newClientName" class="client-input-field" required placeholder="e.g. Ramesh Patil" />
          </div>

          <!-- Mobile & Email -->
          <div>
            <label class="form-label-osb">Mobile Number <span style="color:#ef4444;">*</span></label>
            <input type="tel" id="newClientPhone" class="client-input-field" required placeholder="10-digit mobile" maxlength="15" />
          </div>
          <div>
            <label class="form-label-osb">Email Address</label>
            <input type="email" id="newClientEmail" class="client-input-field" placeholder="optional" />
          </div>

          <!-- Invoicing Address (Full Width) -->
          <div class="modal-field-span2">
            <label class="form-label-osb">Invoicing Address / Village</label>
            <input type="text" id="newClientAddress" class="client-input-field" placeholder="Village, Taluka / Area" />
          </div>

          <!-- State & District -->
          <div>
            <label class="form-label-osb">State</label>
            <select id="newClientState" class="client-input-field">
              <option value="">-- Select State --</option>
            </select>
          </div>
          <div>
            <label class="form-label-osb">District</label>
            <select id="newClientDistrict" class="client-input-field">
              <option value="">-- Select District --</option>
            </select>
          </div>

          <!-- Tehsil / Taluka & City / Town -->
          <div>
            <label class="form-label-osb">Tehsil / Taluka</label>
            <input type="text" id="newClientTahseel" class="client-input-field" placeholder="e.g. Haveli, Ambikapur" />
          </div>
          <div>
            <label class="form-label-osb">City / Village</label>
            <input type="text" id="newClientCity" class="client-input-field" placeholder="e.g. Pune, Raipur" />
          </div>
        </div>
      </div>
      <div class="osb-modal-footer">
        <button type="button" class="btn-osb-nav" style="background:#e2e8f0; color:#475569; border:none;" onclick="document.getElementById('modalNewClient').classList.remove('active')">
          Cancel
        </button>
        <button type="submit" class="btn-osb-nav" style="background:var(--primary); color:#fff; border:none;">
          <i class="fa fa-check"></i> Save & Select Client
        </button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL: ORDER PDF PREVIEW (PROVISIONAL / FINAL SALE ORDER) -->
<div id="modalOrderPdfPreview" class="osb-modal-overlay">
  <div class="osb-modal" style="max-width:960px; width:95%; height:92vh; display:flex; flex-direction:column; padding:0; overflow:hidden;">
    <div class="osb-modal-header" style="flex-shrink:0; padding:12px 20px; background:#f8fafc; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
      <h3 id="orderPdfModalTitle" style="margin:0; font-size:1.02rem; font-weight:800; color:#1e293b; display:flex; align-items:center; gap:8px;">
        <i class="fa fa-file-pdf" style="color:var(--primary);"></i>
        <span>Order PDF Preview</span>
      </h3>
      <div style="display:flex; align-items:center; gap:10px;">
        <a id="btnOrderPdfOpenNewTab" href="#" target="_blank" class="btn-osb-nav" style="padding:5px 12px; font-size:0.78rem; text-decoration:none; background:#e0e7ff; color:#4338ca; border:1px solid #c7d2fe;">
          <i class="fa fa-arrow-up-right-from-square"></i> Open in New Tab
        </a>
        <button type="button" class="osb-modal-close" onclick="OpenSalePOS.closeOrderPdfModal()" title="Close">&times;</button>
      </div>
    </div>
    <div class="osb-modal-body" style="flex:1; padding:0; background:#334155; position:relative; overflow:hidden;">
      <div id="orderPdfLoader" style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center; color:#fff; font-size:1rem; gap:10px; background:rgba(15,23,42,0.7); z-index:10;">
        <i class="fa fa-spinner fa-spin fa-2x" style="color:#38bdf8;"></i> Loading PDF Preview...
      </div>
      <iframe id="orderPdfIframe" src="" style="width:100%; height:100%; border:none;" onload="const l=document.getElementById('orderPdfLoader'); if(l) l.style.display='none';"></iframe>
    </div>
  </div>
</div>

<!-- MODAL: PAYMENT ATTACHED DOCUMENT PREVIEW -->
<div id="modalDocPreview" class="osb-modal-overlay">
  <div class="osb-modal" style="max-width:860px; width:95%; max-height:90vh; display:flex; flex-direction:column; padding:0; overflow:hidden;">
    <div class="osb-modal-header" style="flex-shrink:0; padding:10px 18px; background:#f8fafc; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
      <h3 id="docPreviewModalTitle" style="margin:0; font-size:0.98rem; font-weight:800; color:#1e293b; display:flex; align-items:center; gap:8px;">
        <i class="fa fa-file-image" style="color:var(--primary);"></i>
        <span id="docPreviewTitleText">Payment Document Preview</span>
      </h3>
      <div style="display:flex; align-items:center; gap:10px;">
        <a id="btnDocDownload" href="#" download class="btn-osb-nav" style="padding:4px 10px; font-size:0.76rem; text-decoration:none; background:#f1f5f9; color:#334155; border:1px solid #cbd5e1;">
          <i class="fa fa-download"></i> Download
        </a>
        <a id="btnDocOpenTab" href="#" target="_blank" class="btn-osb-nav" style="padding:4px 10px; font-size:0.76rem; text-decoration:none; background:#e0e7ff; color:#4338ca; border:1px solid #c7d2fe;">
          <i class="fa fa-arrow-up-right-from-square"></i> Open in New Tab
        </a>
        <button type="button" class="osb-modal-close" onclick="OpenSalePOS.closeDocPreviewModal()" title="Close">&times;</button>
      </div>
    </div>
    <div class="osb-modal-body" id="docPreviewModalBody" style="flex:1; padding:16px; background:#0f172a; display:flex; align-items:center; justify-content:center; overflow:auto; min-height:380px;">
      <!-- Content (Image or iframe) rendered dynamically -->
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="assets/opensale_booking.js?v=<?= time() ?>"></script>

</body>
</html>
