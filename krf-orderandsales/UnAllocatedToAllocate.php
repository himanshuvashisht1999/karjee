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

$unalocatedata = getAdvancePaymentTransactions($krfconn, $companyid, $subcompanyid, $projectid, "unallocatedpayments", "");
$totalUnallocCount = count($unalocatedata);
$totalUnallocAmount = 0;
if ($totalUnallocCount > 0) {
  foreach ($unalocatedata as $row) {
    $totalUnallocAmount += floatval($row['advanceamount'] ?? 0);
  }
}

// Build user map from authconn login_user_master for resolving user names
$userMap = [];
if (isset($authconn) && $authconn) {
    $uRes = @$authconn->query("SELECT sno, loginid, loginname, mobileno FROM login_user_master");
    if ($uRes) {
        while ($uRow = $uRes->fetch_assoc()) {
            $name = trim($uRow['loginname'] ?? '');
            if (empty($name)) continue;

            $sno = trim((string)$uRow['sno']);
            $loginid = trim((string)$uRow['loginid']);
            $mobile = trim((string)$uRow['mobileno']);

            if ($sno !== '') $userMap[$sno] = $name;
            if ($loginid !== '') {
                $userMap[$loginid] = $name;
                $userMap[strtolower($loginid)] = $name;
            }
            if ($mobile !== '') {
                $userMap[$mobile] = $name;
            }

            $cleanMobile = preg_replace('/\D/', '', $mobile);
            if (strlen($cleanMobile) >= 10) {
                $userMap[substr($cleanMobile, -10)] = $name;
            }

            $cleanLogin = preg_replace('/\D/', '', $loginid);
            if (strlen($cleanLogin) >= 10) {
                $userMap[substr($cleanLogin, -10)] = $name;
            }
        }
    }
}
?>

<style>
  :root {
    --primary-gradient: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    --card-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    --card-shadow-hover: 0 8px 25px rgba(0, 0, 0, 0.1);
  }

  .page-header-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: var(--card-shadow);
  }

  .stat-card {
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    transition: all 0.25s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
  }
  .stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--card-shadow-hover);
  }

  .unalloc-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-top: 3.5px solid #00d2ff;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
    transition: all 0.2s ease-in-out;
    cursor: pointer;
    position: relative;
  }
  .unalloc-card:hover {
    transform: translateY(-2px);
    border-color: #38bdf8;
    border-top-color: #0284c7;
    box-shadow: var(--card-shadow-hover);
  }
  .unalloc-card.active-selected {
    background: #f0f9ff !important;
    border: 1.5px solid #0284c7 !important;
    border-top: 4px solid #0284c7 !important;
    box-shadow: 0 8px 24px rgba(2, 132, 199, 0.15) !important;
  }

  .pso-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-top: 3.5px solid #10b981;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
    transition: all 0.2s ease-in-out;
    cursor: pointer;
    position: relative;
  }
  .pso-card:hover {
    transform: translateY(-2px);
    border-color: #34d399;
    border-top-color: #059669;
    box-shadow: var(--card-shadow-hover);
  }
  .pso-card.pso-selected {
    background: #ecfdf5 !important;
    border: 1.5px solid #059669 !important;
    border-top: 4px solid #059669 !important;
    box-shadow: 0 8px 24px rgba(5, 150, 105, 0.15) !important;
  }

  /* Pure isolated custom radio styling (immune to global/bootstrap size conflicts) */
  .radio-custom-ui {
    position: relative !important;
    width: 16px !important;
    height: 16px !important;
    min-width: 16px !important;
    max-width: 16px !important;
    min-height: 16px !important;
    max-height: 16px !important;
    flex-shrink: 0 !important;
    margin-top: 3px !important;
    margin-right: 4px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    box-sizing: border-box !important;
  }

  .radio-custom-ui input.native-radio {
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
    width: 16px !important;
    height: 16px !important;
    min-width: 16px !important;
    max-width: 16px !important;
    min-height: 16px !important;
    max-height: 16px !important;
    opacity: 0 !important;
    margin: 0 !important;
    padding: 0 !important;
    cursor: pointer !important;
    z-index: 5 !important;
    pointer-events: auto !important;
    appearance: none !important;
    -webkit-appearance: none !important;
  }

  .radio-custom-ui .radio-indicator {
    width: 15px !important;
    height: 15px !important;
    min-width: 15px !important;
    max-width: 15px !important;
    min-height: 15px !important;
    max-height: 15px !important;
    border-radius: 50% !important;
    border: 1.5px solid #94a3b8 !important;
    background-color: #ffffff !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    box-sizing: border-box !important;
    transition: all 0.15s ease-in-out !important;
    pointer-events: none !important;
  }

  .radio-custom-ui input.native-radio:checked + .radio-indicator {
    border-color: #0284c7 !important;
    background-color: #ffffff !important;
  }

  .radio-custom-ui input.native-radio:checked + .radio-indicator::after {
    content: "" !important;
    display: block !important;
    width: 7px !important;
    height: 7px !important;
    border-radius: 50% !important;
    background-color: #0284c7 !important;
  }

  .radio-custom-ui input.native-radio:checked + .radio-indicator-green {
    border-color: #059669 !important;
    background-color: #ffffff !important;
  }

  .radio-custom-ui input.native-radio:checked + .radio-indicator-green::after {
    background-color: #059669 !important;
  }

  .mode-badge {
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    padding: 3px 8px;
    border-radius: 6px;
    letter-spacing: 0.4px;
  }

  .mode-badge-cash { background-color: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
  .mode-badge-upi { background-color: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
  .mode-badge-bank { background-color: #f5f3ff; color: #5b21b6; border: 1px solid #ddd6fe; }
  .mode-badge-cheque { background-color: #fffbeb; color: #92400e; border: 1px solid #fde68a; }

  .search-input-group {
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid #cbd5e1;
    transition: all 0.2s;
  }
  .search-input-group:focus-within {
    border-color: #0284c7;
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
  }

  .scroll-container {
    max-height: 680px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 #f8fafc;
  }
  .scroll-container::-webkit-scrollbar {
    width: 6px;
  }
  .scroll-container::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 6px;
  }

  .sticky-panel-header {
    position: sticky;
    top: 0;
    background: #ffffff;
    z-index: 10;
    padding-bottom: 10px;
  }

  .info-tag-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 6px 10px;
    font-size: 0.8rem;
  }
</style>

<div class="container-fluid px-2 px-md-4 py-3">
  <!-- Page Header -->
  <div class="page-header-card p-3 p-md-4 mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
      <div>
        <h4 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
          <span class="p-2 rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
            <i class="fa fa-hand-holding-dollar fs-5"></i>
          </span>
          Allocation of Advance Amount
        </h4>
        <p class="text-secondary small mb-0">Select an unallocated advance payment and map it against provisional orders or sales invoices.</p>
      </div>
      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-outline-primary btn-sm px-3 shadow-sm d-flex align-items-center gap-2" onclick="window.location.reload();">
          <i class="fa fa-sync"></i> <span>Refresh</span>
        </button>
      </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="row g-3 mt-2">
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="stat-card p-3 border-start border-4 border-info h-100">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <span class="text-muted small text-uppercase fw-semibold d-block">Advance Amount Count</span>
              <h4 class="fw-bold text-dark mb-0 mt-1" id="statAdvanceCount"><?php echo $totalUnallocCount; ?> <span class="fs-6 fw-normal text-muted">Entries</span></h4>
              <span class="text-info small fw-semibold">Total: Rs. <?php echo number_format($totalUnallocAmount, 2); ?></span>
            </div>
            <div class="rounded-circle bg-info-subtle p-3 text-info">
              <i class="fa fa-wallet fs-4"></i>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-lg-4">
        <div class="stat-card p-3 border-start border-4 border-primary h-100">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <span class="text-muted small text-uppercase fw-semibold d-block">Selected Customer</span>
              <h5 class="fw-bold text-primary mb-0 mt-1 text-truncate" id="statSelectedClient" style="max-width: 220px;">None Selected</h5>
              <span class="text-muted small" id="statSelectedAmt">Click any advance payment below</span>
            </div>
            <div class="rounded-circle bg-primary-subtle p-3 text-primary">
              <i class="fa fa-user-check fs-4"></i>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-lg-4">
        <div class="stat-card p-3 border-start border-4 border-success h-100">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <span class="text-muted small text-uppercase fw-semibold d-block">Against Invoice Count</span>
              <h4 class="fw-bold text-success mb-0 mt-1" id="statInvoiceCount">0 <span class="fs-6 fw-normal text-muted">Matched</span></h4>
              <span class="text-muted small" id="statInvoiceSubtext">Select a payment to search invoices</span>
            </div>
            <div class="rounded-circle bg-success-subtle p-3 text-success">
              <i class="fa fa-file-invoice-dollar fs-4"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Main Content Grid -->
  <div class="row g-4">
    <!-- Left Column: Un-Allocated Amount List -->
    <div class="col-12 col-lg-6" id="unallocCol">
      <div class="card shadow-sm border-0 rounded-4 h-100">
        <div class="card-body p-3 p-md-4">
          <div class="sticky-panel-header border-bottom pb-3 mb-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
              <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i class="fa fa-circle-dot text-info"></i> Un-Allocated Amount
                <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-2.5 py-1 small" id="unallocBadgeCount">
                  <?php echo $totalUnallocCount; ?>
                </span>
              </h5>
              <div id="undoDivContainer"></div>
            </div>

            <!-- Search Filter for Unallocated Payments -->
            <div class="search-input-group d-flex align-items-center bg-light px-2 py-1">
              <i class="fa fa-search text-muted me-2 ms-1"></i>
              <input type="text" id="searchUnallocInput" class="form-control form-control-sm border-0 bg-transparent shadow-none" placeholder="Search customer, UTR, remarks, amount, received by..." autocomplete="off">
              <button class="btn btn-sm btn-link text-muted p-0 me-1 d-none" id="clearSearchUnalloc" title="Clear search">
                <i class="fa fa-times-circle"></i>
              </button>
            </div>
          </div>

          <!-- Unallocated Cards Container -->
          <div class="scroll-container d-flex flex-column gap-3 pe-1" id="unallocListContainer">
            <?php 
            if ($totalUnallocCount > 0) {
              foreach ($unalocatedata as $index => $unalkey) {
                $amount = floatval($unalkey['advanceamount'] ?? 0);
                $formattedAmt = number_format($amount, 2);
                $date   = !empty($unalkey['paymentdate']) ? date('d-M-Y', strtotime($unalkey['paymentdate'])) : '-';
                $mode   = strtolower($unalkey['sourceofpayment'] ?? 'cash');
                $utr    = trim($unalkey['utrnumber'] ?? '');
                $remarks = trim($unalkey['remarks'] ?? '');

                // Payment Type (Advance Amount vs Against Invoice)
                $invoiceno = trim($unalkey['invoiceno'] ?? '');
                $purpose   = trim($unalkey['purposeofpayment'] ?? '');
                if (!empty($invoiceno)) {
                  $paymentTypeBadge = '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle"><i class="fa fa-file-invoice me-1"></i>Against Invoice (' . htmlspecialchars($invoiceno) . ')</span>';
                  $paymentTypeTxt = "Against Invoice ({$invoiceno})";
                } else if (stripos($purpose, 'invoice') !== false) {
                  $paymentTypeBadge = '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle"><i class="fa fa-file-invoice me-1"></i>Against Invoice</span>';
                  $paymentTypeTxt = "Against Invoice";
                } else {
                  $paymentTypeBadge = '<span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="fa fa-coins me-1"></i>Advance Amount</span>';
                  $paymentTypeTxt = "Advance Amount";
                }

                // Received By & Received At details
                $rawReceivedBy = !empty($unalkey['receivedby']) ? $unalkey['receivedby'] : (!empty($unalkey['casgreceivedby']) ? $unalkey['casgreceivedby'] : (!empty($unalkey['created_by']) ? $unalkey['created_by'] : (!empty($unalkey['allocatedby']) ? $unalkey['allocatedby'] : '')));
                $receivedBy = $userMap[$rawReceivedBy] ?? ($userMap[strtolower($rawReceivedBy)] ?? '');
                if (empty($receivedBy)) {
                  $cleanRcv = preg_replace('/\D/', '', $rawReceivedBy);
                  if (strlen($cleanRcv) >= 10 && isset($userMap[substr($cleanRcv, -10)])) {
                    $receivedBy = $userMap[substr($cleanRcv, -10)];
                  } else {
                    $receivedBy = !empty($rawReceivedBy) ? $rawReceivedBy : 'Admin';
                  }
                }
                $rawReceivedAt = !empty($unalkey['receivedlocation']) ? $unalkey['receivedlocation'] : (!empty($unalkey['receivedtoaccountid']) ? $unalkey['receivedtoaccountid'] : '');
                $receivedAt = !empty($rawReceivedAt) ? $rawReceivedAt : '-';

                $modeClass = 'mode-badge-cash';
                $modeText = 'Cash';
                if ($mode === 'upi') {
                  $modeClass = 'mode-badge-upi';
                  $modeText = 'UPI';
                } else if ($mode === 'banktransfer' || $mode === 'bank' || $mode === 'neft' || $mode === 'rtgs') {
                  $modeClass = 'mode-badge-bank';
                  $modeText = 'Bank Transfer';
                } else if ($mode === 'cheque') {
                  $modeClass = 'mode-badge-cheque';
                  $modeText = 'Cheque';
                }

                if ($mode === "cash") {
                  $amtlbl = "Rs. {$formattedAmt} received via cash on {$date}";
                } else if ($mode === "cheque") {
                  $amtlbl = "Rs. {$formattedAmt} received via cheque" . ($utr ? " ({$utr})" : "") . " on {$date}";
                } else if ($mode === "banktransfer" || $mode === 'bank' || $mode === 'neft' || $mode === 'rtgs') {
                  $amtlbl = "Rs. {$formattedAmt} received via bank transfer" . ($utr ? ", UTR: {$utr}" : "") . " on {$date}";
                } else {
                  $amtlbl = "Rs. {$formattedAmt} received via UPI" . ($utr ? ", UTR: {$utr}" : "") . " on {$date}";
                }

                $searchPayload = strtolower($unalkey['clientname'] . ' ' . $amtlbl . ' ' . $remarks . ' ' . $utr . ' ' . $amount . ' ' . $paymentTypeTxt . ' ' . $receivedBy . ' ' . $receivedAt);
            ?>
            <div class="unalloc-card p-3 row-clickable" 
                 data-unaldbsno="<?php echo htmlspecialchars($unalkey['sno']); ?>"
                 data-username="<?php echo htmlspecialchars($unalkey['clientname']); ?>"
                 data-amount="<?php echo htmlspecialchars($formattedAmt); ?>"
                 data-mode="<?php echo htmlspecialchars($modeText); ?>"
                 data-search="<?php echo htmlspecialchars($searchPayload); ?>">
              <div class="d-flex align-items-start gap-3">
                <div class="radio-custom-ui">
                  <input type="radio" 
                         class="unallocradio native-radio" 
                         name="unallocatedamountradio" 
                         data-unaldbsno="<?php echo htmlspecialchars($unalkey['sno']); ?>" 
                         data-username="<?php echo htmlspecialchars($unalkey['clientname']); ?>" 
                         data-amount="<?php echo htmlspecialchars($formattedAmt); ?>"
                         id="unallocatedamountradio_<?php echo $index; ?>" />
                  <span class="radio-indicator"></span>
                </div>
                <div class="flex-grow-1" style="min-width: 0;">
                  <!-- Top Row: Name & Badges -->
                  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-1">
                    <h6 class="text-dark fw-bold mb-0 text-break">
                      Name: <span class="text-primary-emphasis"><?php echo htmlspecialchars($unalkey['clientname']); ?></span>
                    </h6>
                    <div class="d-flex align-items-center gap-1.5 flex-wrap">
                      <?php echo $paymentTypeBadge; ?>
                      <span class="mode-badge <?php echo $modeClass; ?>"><?php echo $modeText; ?></span>
                    </div>
                  </div>

                  <!-- Amount & Payment Line -->
                  <div class="text-secondary small fw-medium mb-2">
                    <i class="fa fa-indian-rupee-sign text-success me-1"></i><?php echo htmlspecialchars($amtlbl); ?>
                  </div>

                  <!-- Detail Grid: Received By & Received At -->
                  <div class="row g-2 mb-2">
                    <div class="col-sm-6">
                      <div class="info-tag-box text-truncate">
                        <span class="text-muted d-block small"><i class="fa fa-user-tag text-primary me-1"></i>Received By</span>
                        <strong class="text-dark"><?php echo $receivedBy !== '' ? htmlspecialchars($receivedBy) : '<span class="text-muted fw-normal">N/A</span>'; ?></strong>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="info-tag-box text-truncate">
                        <span class="text-muted d-block small"><i class="fa fa-building-columns text-secondary me-1"></i>Received At / Account</span>
                        <strong class="text-dark"><?php echo $receivedAt !== '' ? htmlspecialchars($receivedAt) : '<span class="text-muted fw-normal">N/A</span>'; ?></strong>
                      </div>
                    </div>
                  </div>

                  <!-- Description / Remarks -->
                  <div class="text-muted small text-break">
                    <strong>Description:</strong> <?php echo $remarks !== '' ? htmlspecialchars($remarks) : '<span class="fst-italic text-muted">No description</span>'; ?>
                  </div>
                </div>
              </div>
            </div>
            <?php
              }
            } else {
            ?>
            <div class="text-center py-5">
              <div class="p-3 rounded-circle bg-light d-inline-flex mb-3">
                <i class="fa fa-folder-open fs-2 text-muted"></i>
              </div>
              <h6 class="fw-bold text-dark">No Unallocated Payments Found</h6>
              <p class="text-muted small mb-0">All advance amounts have already been allocated.</p>
            </div>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Column: Provisional Orders / Invoices List -->
    <div class="col-12 col-lg-6" id="prvordContainer" style="display:none;">
      <div class="card shadow-sm border-0 rounded-4 h-100">
        <div class="card-body p-3 p-md-4">
          <div class="sticky-panel-header border-bottom pb-3 mb-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
              <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i class="fa fa-file-invoice text-success"></i> Provisional Orders / Invoices
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 small" id="psoBadgeCount">
                  0
                </span>
              </h5>
              <div class="small text-muted" id="psoClientSubtitle"></div>
            </div>

            <!-- Search Filter for Matched Orders -->
            <div class="search-input-group d-flex align-items-center bg-light px-2 py-1">
              <i class="fa fa-search text-muted me-2 ms-1"></i>
              <input type="text" id="searchPsoInput" class="form-control form-control-sm border-0 bg-transparent shadow-none" placeholder="Filter orders by PSO No, crop name, created by, remarks, amount..." autocomplete="off">
              <button class="btn btn-sm btn-link text-muted p-0 me-1 d-none" id="clearSearchPso" title="Clear filter">
                <i class="fa fa-times-circle"></i>
              </button>
            </div>
          </div>

          <!-- Provisional Orders Container -->
          <div class="scroll-container d-flex flex-column gap-3 pe-1" id="subprvordContainer"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include('../footer.php'); ?>
<script type="text/javascript" src="<?php echo $prefix;?>krf-orderandsales/js/pjs_function.js"></script>
<script>
// Format date helper
function formatOrderDisplayDate(dateStr) {
    if (!dateStr) return '-';
    let d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    let day = String(d.getDate()).padStart(2, '0');
    let monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    let month = monthNames[d.getMonth()];
    let year = d.getFullYear();
    return `${day}-${month}-${year}`;
}

// Search/Filter for Unallocated Payments
$(document).on('input', '#searchUnallocInput', function() {
    const filter = $(this).val().toLowerCase().trim();
    $('#clearSearchUnalloc').toggleClass('d-none', !filter);
    let visibleCount = 0;

    $('.unalloc-card').each(function() {
        if ($(this).hasClass('selected-hidden')) return;
        const text = $(this).data('search') || $(this).text().toLowerCase();
        const matches = !filter || text.indexOf(filter) > -1;
        $(this).toggle(matches);
        if (matches) visibleCount++;
    });

    $('#unallocBadgeCount').text(visibleCount);
});

$(document).on('click', '#clearSearchUnalloc', function() {
    $('#searchUnallocInput').val('').trigger('input');
});

// Search/Filter for Provisional Orders
$(document).on('input', '#searchPsoInput', function() {
    const filter = $(this).val().toLowerCase().trim();
    $('#clearSearchPso').toggleClass('d-none', !filter);
    let visibleCount = 0;

    $('.pso-card').each(function() {
        const text = $(this).data('search') || $(this).text().toLowerCase();
        const matches = !filter || text.indexOf(filter) > -1;
        $(this).toggle(matches);
        if (matches) visibleCount++;
    });

    $('#psoBadgeCount').text(visibleCount);
});

$(document).on('click', '#clearSearchPso', function() {
    $('#searchPsoInput').val('').trigger('input');
});

// 1) Click on unallocated payment row -> check radio and trigger change
$(document).on('click', '.row-clickable', function(e) {
    if (e.target.tagName !== 'INPUT') {
        const $radio = $(this).find('input[type="radio"]');
        if ($radio.length) {
            $radio.prop('checked', true).trigger('change');
        }
    }
});

$(document).on('change', 'input[type="radio"][name="unallocatedamountradio"]', function() {
    const $radio = $(this);
    const unaldbsno = $radio.data('unaldbsno');
    const likelyClientName = $radio.data('username');
    const amountStr = $radio.data('amount');
    const $parentDiv = $radio.closest('.unalloc-card');

    if (!likelyClientName || $parentDiv.length === 0) {
        console.warn("No parent .unalloc-card found for radio", $radio);
        return;
    }

    // Update KPI Top Header
    $('#statSelectedClient').text(likelyClientName).attr('title', likelyClientName);
    $('#statSelectedAmt').text('Advance: Rs. ' + amountStr);

    // Visual selection state
    $('.unalloc-card').removeClass('active-selected').addClass('d-none selected-hidden');
    $parentDiv.removeClass('d-none selected-hidden').addClass('active-selected');

    // Add back/undo button in header
    $('#undoDivContainer').html(`
        <button type="button" id="undoFilter" class="btn btn-outline-secondary btn-sm px-2.5 py-1 shadow-sm d-flex align-items-center gap-1.5">
            <i class="fa fa-arrow-left"></i> <span>Show All</span>
        </button>
    `);

    // Loading State for Right Container
    $('#psoClientSubtitle').html(`for <span class="fw-semibold text-dark">${likelyClientName}</span>`);
    $('#subprvordContainer').html(`
        <div class="text-center py-5">
            <div class="spinner-border text-primary mb-3" role="status" style="width: 2.5rem; height: 2.5rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <h6 class="fw-semibold text-dark">Searching Orders & Invoices...</h6>
            <p class="text-muted small mb-0">Finding provisional records for <strong>${likelyClientName}</strong></p>
        </div>
    `);
    $('#prvordContainer').fadeIn(200);

    // Call AJAX and render provisional orders
    GetProvisionaOrderLikebyUserName(likelyClientName, function(res) {
        if (res && res.length > 0) {
            let prvordlist = '';
            const againstInvoiceCount = res.length;

            // Update KPI count
            $('#statInvoiceCount').html(`${againstInvoiceCount} <span class="fs-6 fw-normal text-muted">Orders</span>`);
            $('#statInvoiceSubtext').text(`Found for ${likelyClientName}`);
            $('#psoBadgeCount').text(againstInvoiceCount);

            for (let i = 0; i < res.length; i++) {
                const item = res[i];
                const isConfirmed = item.status === 'Confirmed';
                const statusBadge = isConfirmed
                    ? '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 small">Confirmed</span>'
                    : (item.status ? '<span class="badge bg-secondary-subtle text-secondary border rounded-pill px-2.5 py-1 small">' + item.status + '</span>' : '');

                const orderAmt = Number(item.total_amount || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 });
                const paidAmt = Number(item.paid_amount || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 });
                const pendingBalanceVal = (item.pending_balance !== undefined) ? Number(item.pending_balance) : (Number(item.total_amount || 0) - Number(item.paid_amount || 0));
                const pendingBalanceFormatted = pendingBalanceVal.toLocaleString('en-IN', { minimumFractionDigits: 2 });
                const rawRemarks = (item.remarks || item.requirementtxt || '').trim();
                const hasRemarks = rawRemarks !== '' && rawRemarks !== '-';
                const remarksHtml = hasRemarks ? `
                    <div class="text-muted small text-break mt-2.5 mb-2 p-2 rounded bg-light border">
                        <strong class="text-dark"><i class="fa fa-comment-dots text-secondary me-1"></i>Requirement / Remarks:</strong> ${rawRemarks}
                    </div>` : '';
                const orderDate = formatOrderDisplayDate(item.created_at || item.orderdate || '');
                const createdBy = item.created_by_name || item.created_by || item.createdby || 'Admin';

                // Extract crop names from product_name or items list
                let cropNames = item.product_name || '';
                if (!cropNames && item.items && item.items.length > 0) {
                    cropNames = item.items.map(it => it.product_name).filter(Boolean).join(', ');
                }
                if (!cropNames) cropNames = 'N/A';

                // Format payment transactions against this provisional order
                let paymentsHtml = '';
                if (item.payments && item.payments.length > 0) {
                    let pList = '';
                    for (let p = 0; p < item.payments.length; p++) {
                        let ptx = item.payments[p];
                        let pAmt = Number(ptx.advanceamount || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 });
                        let pDate = formatOrderDisplayDate(ptx.paymentdate);
                        let pMode = ptx.sourceofpayment || 'Payment';
                        let pUtr = ptx.utrnumber ? ` (UTR: ${ptx.utrnumber})` : '';
                        let pRemarks = ptx.remarks ? ` - ${ptx.remarks}` : '';

                        pList += `
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 py-2 px-3 rounded-2 bg-white border shadow-2xs">
                                <div>
                                    <span class="fw-bold text-success font-monospace me-1.5">Rs. ${pAmt}</span>
                                    <span class="badge bg-light text-dark border small text-uppercase px-2 py-0.5">${pMode}</span>
                                    <span class="small text-muted ms-1">${pUtr}${pRemarks}</span>
                                </div>
                                <div class="small text-muted font-monospace">
                                    <i class="fa fa-calendar-day text-secondary me-1"></i>${pDate}
                                </div>
                            </div>`;
                    }
                    paymentsHtml = `
                        <div class="mt-2.5 p-3 rounded-3 bg-light border shadow-2xs">
                            <div class="d-flex align-items-center justify-content-between mb-2.5 pb-2 border-bottom">
                                <span class="small fw-bold text-dark d-flex align-items-center gap-1.5">
                                    <i class="fa fa-money-bill-transfer text-primary"></i> Payment Transactions Against Order
                                </span>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill small px-2 py-0.5">${item.payments.length} Paid</span>
                            </div>
                            <div class="d-flex flex-column gap-1.5">${pList}</div>
                        </div>`;
                } else {
                    paymentsHtml = `
                        <div class="mt-2.5 p-2.5 px-3 rounded-3 bg-light border text-muted small d-flex align-items-center gap-2">
                            <i class="fa fa-info-circle text-info"></i> No previous payment transactions allocated against this order.
                        </div>`;
                }

                const searchData = (item.clientname + ' ' + item.psono + ' ' + orderAmt + ' ' + cropNames + ' ' + createdBy + ' ' + orderDate + ' ' + rawRemarks + ' ' + (item.status || '')).toLowerCase();

                prvordlist += `
                    <div class="pso-card p-3 row-psoclickable"
                         data-search="${searchData}"
                         data-unalocdbsno="${unaldbsno}"
                         data-psodbsno="${item.id}">
                        <div class="d-flex align-items-start gap-3">
                            <div class="radio-custom-ui">
                                <input type="radio"
                                       class="native-radio"
                                       name="provisionalorderradio" 
                                       data-unalocdbsno="${unaldbsno}"
                                       data-psodbsno="${item.id}"
                                       data-username="${item.clientname}"
                                       id="provisionalorderradio_${i}" />
                                <span class="radio-indicator radio-indicator-green"></span>
                            </div>
                            <div class="flex-grow-1" style="min-width: 0;">
                                <!-- Top Row: Name & Status -->
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                    <h6 class="text-dark fw-bold mb-0 text-break">
                                        Name: <span class="text-dark">${item.clientname}</span>
                                    </h6>
                                    <div>${statusBadge}</div>
                                </div>

                                <!-- Grid Details: Order No, Order Date, Created By, Crop Name, Total Amount -->
                                <div class="row g-2 small mb-2">
                                    <div class="col-sm-6">
                                        <div class="info-tag-box">
                                            <span class="text-muted d-block small"><i class="fa fa-receipt text-primary me-1"></i>Provisional Order No</span>
                                            <span class="fw-bold font-monospace text-primary">${item.psono || '-'}</span>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="info-tag-box">
                                            <span class="text-muted d-block small"><i class="fa fa-calendar-day text-secondary me-1"></i>Order Date</span>
                                            <strong class="text-dark">${orderDate}</strong>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="info-tag-box">
                                            <span class="text-muted d-block small"><i class="fa fa-user-pen text-info me-1"></i>Created By</span>
                                            <strong class="text-dark">${createdBy}</strong>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="info-tag-box">
                                            <span class="text-muted d-block small"><i class="fa fa-coins text-success me-1"></i>Total Amount (PSO)</span>
                                            <strong class="text-dark fs-6">Rs. ${orderAmt}</strong>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="info-tag-box">
                                            <span class="text-muted d-block small"><i class="fa fa-circle-check text-success me-1"></i>Paid Amount</span>
                                            <strong class="text-success fs-6">Rs. ${paidAmt}</strong>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="info-tag-box">
                                            <span class="text-muted d-block small"><i class="fa fa-scale-balanced text-warning me-1"></i>Pending Balance</span>
                                            <strong class="${pendingBalanceVal > 0 ? 'text-danger' : 'text-success'} fs-6">Rs. ${pendingBalanceFormatted}</strong>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="info-tag-box">
                                            <span class="text-muted d-block small"><i class="fa fa-seedling text-success me-1"></i>Crop / Product Name</span>
                                            <strong class="text-dark">${cropNames}</strong>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Transactions against this Order -->
                                ${paymentsHtml}

                                <!-- Requirement Remarks (Only if not empty) -->
                                ${remarksHtml}

                                <!-- Confirm Allocation Button container -->
                                <div class="action-btn-container text-end mt-2 pt-2 border-top" style="display:none;">
                                    <button type="button" 
                                            class="btn btn-sm btn-success px-3 py-1.5 shadow-sm fw-semibold btn-confirm-allocate" 
                                            data-psodbsno="${item.id}" 
                                            data-unalocdbsno="${unaldbsno}"
                                            data-psoname="${item.psono || ''}"
                                            data-clientname="${item.clientname}">
                                        <i class="fa fa-check-circle me-1"></i> Confirmed To Allocate
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>`;
            }

            $('#subprvordContainer').html(prvordlist);
        } else {
            $('#statInvoiceCount').html(`0 <span class="fs-6 fw-normal text-muted">Orders</span>`);
            $('#statInvoiceSubtext').text(`No orders found`);
            $('#psoBadgeCount').text(0);
            $('#subprvordContainer').html(`
                <div class="text-center py-5">
                    <div class="p-3 rounded-circle bg-warning-subtle text-warning d-inline-flex mb-3">
                        <i class="fa fa-triangle-exclamation fs-2"></i>
                    </div>
                    <h6 class="fw-bold text-dark">No Orders or Invoices Found</h6>
                    <p class="text-muted small mb-0 px-3">There are currently no provisional orders or sales invoices matching <strong>${likelyClientName}</strong>.</p>
                </div>
            `);
        }
    });
});

// Undo/Back button handler
$(document).on('click', '#undoFilter', function() {
    $('.unalloc-card').removeClass('d-none selected-hidden active-selected').show();
    $('input[type="radio"][name="unallocatedamountradio"]').prop('checked', false);
    $('#undoDivContainer').empty();
    $('#searchUnallocInput').val('').trigger('input');
    
    $('#prvordContainer').fadeOut(150, function() {
        $('#subprvordContainer').empty();
    });

    $('#statSelectedClient').text('None Selected').removeAttr('title');
    $('#statSelectedAmt').text('Click any advance payment below');
    $('#statInvoiceCount').html(`0 <span class="fs-6 fw-normal text-muted">Matched</span>`);
    $('#statInvoiceSubtext').text('Select a payment to search invoices');
    $('#psoBadgeCount').text(0);
});

// Right side: Click on order row -> check radio and reveal confirm button
$(document).on('click', '.row-psoclickable', function(e) {
    if ($(e.target).closest('.btn-confirm-allocate').length) {
        return;
    }
    if (e.target.tagName !== 'INPUT') {
        const $radio = $(this).find('input[type="radio"]');
        if ($radio.length) {
            $radio.prop('checked', true).trigger('change');
        }
    }
});

// Radio change handler for provisional orders
$(document).on('change', 'input[type="radio"][name="provisionalorderradio"]', function() {
    const $parentDiv = $(this).closest('.pso-card');
    $('.pso-card').removeClass('pso-selected');
    $('.pso-card').find('.action-btn-container').slideUp(150);

    $parentDiv.addClass('pso-selected');
    $parentDiv.find('.action-btn-container').slideDown(200);
});

// Confirm Allocation Button Click
$(document).on('click', '.btn-confirm-allocate', function(e) {
    e.stopPropagation();
    const psodbsno = $(this).data('psodbsno');
    const unalocdbsno = $(this).data('unalocdbsno');
    const psoname = $(this).data('psoname') || 'Selected Order';
    const clientname = $(this).data('clientname') || '';

    Swal.fire({
        title: 'Confirm Allocation?',
        html: `Are you sure you want to allocate this advance payment to <strong>${psoname}</strong> (${clientname})?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#059669',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fa fa-check"></i> Yes, Allocate Now',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            UnallocatedAmounttoAllocat(unalocdbsno, psodbsno);
        }
    });
});
</script>