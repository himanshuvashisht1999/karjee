<?php
error_reporting(E_ALL);
require_once __DIR__ . '/../../connection.php';
require_once __DIR__ . '/../../loginCell/functions/login_functions.php';
include_once __DIR__ . '/../functions/orders_functions.php';

$companyid    = $_REQUEST['companyid'] ?? 0;
$subcompanyid = $_REQUEST['subcompanyid'] ?? 0;
$projectid    = $_REQUEST['projectid'] ?? 0;
$psono        = trim($_REQUEST['psono'] ?? '');
$targetItemId = intval($_REQUEST['target_item_id'] ?? 0);

$order = getProvisionalSalesOrders($krfconn, $companyid, $subcompanyid, $projectid, "psono", $psono)[0] ?? null;
if (!$order && !empty($psono)) {
    // Fallback: fetch directly from krf_provisional_sales_order by psono
    $fallbackStmt = $krfconn->prepare("SELECT * FROM krf_provisional_sales_order WHERE psono = ? LIMIT 1");
    if ($fallbackStmt) {
        $fallbackStmt->bind_param("s", $psono);
        $fallbackStmt->execute();
        $fRes = $fallbackStmt->get_result();
        if ($fRes && $fRow = $fRes->fetch_assoc()) {
            $order = $fRow;
            $companyid = $order['companyid'] ?? $companyid;
            $subcompanyid = $order['subcompanyid'] ?? $subcompanyid;
            $projectid = $order['projectid'] ?? $projectid;
        }
        $fallbackStmt->close();
    }
}

if (!$order):
?>
  <div class="alert alert-danger m-3">
    <i class="fa fa-exclamation-triangle me-2"></i> Provisional order (<?php echo htmlspecialchars($psono); ?>) not found.
  </div>
<?php
  exit;
endif;

$lginfo = loginuserinfo($authconn, "", "loginid", $order['created_by']);
$created_by = !empty($lginfo[0]['loginname']) ? $lginfo[0]['loginname'] : ($order['created_by'] ?? 'N/A');
$paymentrcv = getAdvancePaymentTransactions($krfconn, $companyid, $subcompanyid, $projectid, "invoiceno", $psono);

// Fetch ALL items belonging to this provisional order directly (do NOT filter out cancelled items)
$items = [];
$itemStmt = $krfconn->prepare("SELECT * FROM krf_provisional_sales_order_items WHERE psono = ? ORDER BY id ASC");
if ($itemStmt) {
    $itemStmt->bind_param("s", $psono);
    $itemStmt->execute();
    $res = $itemStmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $items[] = $row;
    }
    $itemStmt->close();
}

$orderStatus = trim($order['status'] ?? '');
$isOrderCancelled = in_array(strtolower($orderStatus), ['cancelled', 'cancel', 'remove', 'removed']);

// Check active vs cancelled items
$cancelledItemsCount = 0;
$activeItemsCount = 0;
$totalCancelledAmount = 0;
$grandTotalAmount = 0;
foreach ($items as $it) {
    $itSt = strtolower(trim($it['status'] ?? ''));
    $itCancelled = in_array($itSt, ['cancelled', 'cancel', 'remove', 'removed']) || $isOrderCancelled;
    if ($itCancelled) {
        $cancelledItemsCount++;
        $totalCancelledAmount += (float)($it['amount'] ?? 0);
    } else {
        $activeItemsCount++;
    }
    $grandTotalAmount += (float)($it['amount'] ?? 0);
}

// Check for existing active credit note for this order
$existingCreditNotes = [];
$cnStmt = $krfconn->prepare("SELECT creditnote_id, creditnote_no, amount_paid, credit_amount, status, reason FROM krf_creditnote WHERE psono = ? AND status = 'Active'");
if ($cnStmt) {
    $cnStmt->bind_param("s", $psono);
    $cnStmt->execute();
    $cnRes = $cnStmt->get_result();
    while ($cnRow = $cnRes->fetch_assoc()) {
        $existingCreditNotes[] = $cnRow;
    }
    $cnStmt->close();
}
?>

<style>
  /* 90% Screen Width & Responsive Layout */
  #undoOrderModal .modal-undo-90 {
    width: 90vw !important;
    max-width: 90vw !important;
    min-width: 90vw !important;
    margin: 2vh auto !important;
    height: 94vh !important;
    max-height: 94vh !important;
  }
  #undoOrderModal .modal-content {
    height: 94vh !important;
    max-height: 94vh !important;
    display: flex !important;
    flex-direction: column !important;
    border-radius: 14px !important;
  }
  #undoOrderModal .modal-body {
    flex: 1 1 auto !important;
    overflow-y: auto !important;
    max-height: none !important;
  }

  /* Mobile Top Action Bar */
  .undo-mobile-top-action {
    display: none;
  }

  @media (max-width: 991px) {
    #undoOrderModal .modal-undo-90 {
      width: 94vw !important;
      max-width: 94vw !important;
      min-width: 94vw !important;
    }
  }

  @media (max-width: 768px) {
    #undoOrderModal .modal-undo-90 {
      width: 96vw !important;
      max-width: 96vw !important;
      min-width: 96vw !important;
      margin: 1vh auto !important;
      height: 98vh !important;
      max-height: 98vh !important;
    }
    #undoOrderModal .modal-content {
      height: 98vh !important;
      max-height: 98vh !important;
      border-radius: 10px !important;
    }
    .undo-mobile-top-action {
      display: block !important;
      margin-bottom: 16px !important;
      background: #fffbeb !important;
      border: 1.5px solid #fde047 !important;
      border-radius: 10px !important;
      padding: 10px !important;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06) !important;
    }
  }

  .undo-order-table input[type="checkbox"],
  #undoOrderModal input[type="checkbox"],
  .undo-item-chk,
  #checkAllUndoItems {
    transform: none !important;
    -webkit-transform: none !important;
    -ms-transform: none !important;
    margin: 0 auto !important;
    width: 17px !important;
    height: 17px !important;
    min-width: 17px !important;
    max-width: 17px !important;
    min-height: 17px !important;
    max-height: 17px !important;
    display: inline-block !important;
    cursor: pointer !important;
    position: static !important;
    box-shadow: none !important;
    accent-color: #d97706 !important;
    vertical-align: middle !important;
  }
  .undo-order-table th.col-checkbox,
  .undo-order-table td.col-checkbox {
    width: 44px !important;
    min-width: 44px !important;
    max-width: 48px !important;
    text-align: center !important;
    vertical-align: middle !important;
    padding: 6px 2px !important;
  }
</style>

<div class="card border-0 mb-1">
  <div class="card-body p-2 p-md-3">

    <!-- Mobile-Only Top Action Bar (View top of screen on mobile with margin from bottom) -->
    <?php if ($cancelledItemsCount > 0): ?>
    <div class="undo-mobile-top-action">
      <div class="d-flex align-items-center justify-content-between gap-2">
        <button type="button" 
                class="btn btn-warning fw-bold py-2 px-3 flex-grow-1 shadow-sm btnUndoOrderSubmitAction" 
                data-psono="<?php echo htmlspecialchars($psono, ENT_QUOTES); ?>"
                style="font-size: 0.95rem; border-radius: 8px;">
          <i class="fa fa-undo me-1"></i> <span class="undoBtnLabel">Undo Rejection & Restore (<span class="undoBtnCountText"><?php echo $cancelledItemsCount; ?></span>)</span>
        </button>
        <button type="button" class="btn btn-outline-secondary px-3 py-2" onclick="$('#undoOrderModal').modal('hide')" style="border-radius: 8px;" title="Close">
          <i class="fa fa-times"></i>
        </button>
      </div>
    </div>
    <?php endif; ?>

    <!-- Top Header -->
    <div class="text-center mb-3">
      <h4 class="fw-bold mb-1" style="color: #b45309; font-size: 1.35rem; letter-spacing: 0.2px;">
        <i class="fa fa-undo-alt me-2"></i> Undo Rejected Provisional Order
      </h4>
      <p class="text-muted small mb-0">Select cancelled items below to restore their active status across all modules.</p>
    </div>

    <!-- Order & Customer Summary -->
    <div class="row g-2 mb-3 p-3 rounded" style="background-color: #fefce8; border: 1px solid #fef08a; font-size: 0.92rem; line-height: 1.7; color: #1e293b;">
      <div class="col-md-6 ps-2">
        <div><strong>Customer:</strong> <span><?php echo htmlspecialchars($order['clientname'] ?? 'N/A'); ?></span></div>
        <div><strong>Mobile:</strong> <span><?php echo htmlspecialchars(!empty($order['mobileno']) ? $order['mobileno'] : 'N/A'); ?></span></div>
        <div><strong>Email:</strong> <span><?php echo htmlspecialchars(!empty($order['emailid']) ? $order['emailid'] : 'N/A'); ?></span></div>
        <div>
          <strong>Current Status:</strong> 
          <?php if ($isOrderCancelled): ?>
            <span class="badge bg-danger text-white"><i class="fa fa-ban me-1"></i>Cancelled</span>
          <?php else: ?>
            <span class="badge bg-warning text-dark"><i class="fa fa-exclamation-circle me-1"></i>Partially Cancelled</span>
          <?php endif; ?>
        </div>
        <?php if (!empty($order['remarks'])): ?>
          <div class="small text-danger mt-1"><strong>Cancellation Remarks:</strong> <?php echo nl2br(htmlspecialchars($order['remarks'])); ?></div>
        <?php endif; ?>
      </div>
      
      <div class="col-md-6 pe-2">
        <div><strong>Order No:</strong> <span class="fw-bold text-primary"><?php echo htmlspecialchars($order['psono']); ?></span></div>
        <div><strong>Order Date:</strong> <span><?php echo !empty($order['created_at']) ? date('d-M-Y', strtotime($order['created_at'])) : 'N/A'; ?></span></div>
        <div><strong>Delivery Address:</strong> <span><?php echo htmlspecialchars(!empty($order['delivery_address']) ? $order['delivery_address'] : 'N/A'); ?></span></div>
        <div><strong>Created By:</strong> <span><?php echo htmlspecialchars($created_by); ?></span></div>
      </div>
    </div>

    <!-- Items Table -->
    <div class="table-responsive rounded mb-3 border">
      <table class="table table-bordered table-hover align-middle mb-0 undo-order-table" style="font-size: 0.88rem;">
        <thead style="background-color: #fef3c7; color: #92400e;">
          <tr class="text-uppercase" style="font-weight: 700; font-size: 0.8rem; letter-spacing: 0.4px;">
            <th class="text-center align-middle col-checkbox" style="background-color: #fef3c7; color: #92400e;">
              <div style="display: flex; justify-content: center; align-items: center; width: 100%; min-height: 20px; margin: 0; padding: 0;">
                <input type="checkbox" 
                       id="checkAllUndoItems" 
                       class="shadow-none" 
                       title="Check / Uncheck All Cancelled Items"
                       <?php echo ($cancelledItemsCount > 0) ? 'checked' : 'disabled'; ?>>
              </div>
            </th>
            <th style="background-color: #fef3c7; color: #92400e;">Product</th>
            <th class="text-center" style="width: 80px; background-color: #fef3c7; color: #92400e;">Qty</th>
            <th class="text-end" style="width: 100px; background-color: #fef3c7; color: #92400e;">Rate</th>
            <th class="text-end" style="width: 100px; background-color: #fef3c7; color: #92400e;">Discount</th>
            <th class="text-end" style="width: 120px; background-color: #fef3c7; color: #92400e;">Amount</th>
            <th class="text-center" style="width: 120px; background-color: #fef3c7; color: #92400e;">Exp. Delivery</th>
            <th class="text-center" style="width: 110px; background-color: #fef3c7; color: #92400e;">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php
          if (empty($items)):
          ?>
            <tr>
              <td colspan="8" class="text-center py-4 text-muted">
                <i class="fa fa-info-circle me-1"></i> No items found for this provisional order.
              </td>
            </tr>
          <?php
          else:
            $selectableCount = 0;
            foreach ($items as $item):
              $itemStatus = trim($item['status'] ?? '');
              $itemStatusLower = strtolower($itemStatus);
              $isCancelled = in_array($itemStatusLower, ['cancelled', 'cancel', 'remove', 'removed']) || $isOrderCancelled;
              $cleanName = preg_replace('/^(NonGrafted|Non-Grafted)\s*[-:]?\s*/i', '', $item['product_name'] ?? '');
              
              $isPreChecked = false;
              if ($isCancelled) {
                  $selectableCount++;
                  if ($targetItemId > 0) {
                      $isPreChecked = ((int)$item['id'] === $targetItemId);
                  } else {
                      $isPreChecked = true;
                  }
              }
            ?>
            <tr class="<?php echo $isCancelled ? 'table-warning bg-opacity-25' : 'table-light'; ?>">
              <td class="text-center align-middle col-checkbox">
                <?php if ($isCancelled): ?>
                  <div style="display: flex; justify-content: center; align-items: center; width: 100%; min-height: 20px;">
                    <input type="checkbox" 
                           class="undo-item-chk shadow-none" 
                           value="<?php echo $item['id']; ?>" 
                           data-product="<?php echo htmlspecialchars(!empty($cleanName) ? $cleanName : ($item['product_name'] ?? ''), ENT_QUOTES); ?>"
                           data-amount="<?php echo (float)($item['amount'] ?? 0); ?>"
                           <?php echo $isPreChecked ? 'checked' : ''; ?>>
                  </div>
                <?php else: ?>
                  <span class="badge bg-success-subtle text-success p-1" title="Item is already active"><i class="fa fa-check"></i></span>
                <?php endif; ?>
              </td>
              <td>
                <div class="fw-bold <?php echo $isCancelled ? 'text-dark' : 'text-success'; ?>">
                  <?php echo htmlspecialchars(!empty($cleanName) ? $cleanName : ($item['product_name'] ?? '')); ?>
                </div>
                <?php if ($isCancelled): ?>
                  <div class="small text-danger mt-1">
                    <i class="fa fa-ban me-1"></i>Cancelled
                    <?php if (!empty($item['remark'])): ?>
                      &bull; <span class="fst-italic">Reason: <?php echo htmlspecialchars($item['remark']); ?></span>
                    <?php elseif (!empty($item['tedit'])): ?>
                      &bull; <span class="fst-italic"><?php echo htmlspecialchars($item['tedit']); ?></span>
                    <?php endif; ?>
                  </div>
                <?php else: ?>
                  <div class="small text-muted mt-0.5"><i class="fa fa-check-circle text-success me-1"></i>Active Item</div>
                <?php endif; ?>
              </td>
              <td class="text-center fw-semibold">
                <?php echo number_format($item['quantity'] ?? 0, 0); ?>
              </td>
              <td class="text-end">
                ₹<?php echo number_format($item['rate'] ?? 0, 2); ?>
              </td>
              <td class="text-end text-danger">
                ₹<?php echo number_format($item['discount_amount'] ?? 0, 2); ?>
              </td>
              <td class="text-end fw-bold text-dark">
                ₹<?php echo number_format($item['amount'] ?? 0, 2); ?>
              </td>
              <td class="text-center small">
                <?php echo (!empty($item['expecteddeliverydate']) && $item['expecteddeliverydate'] !== '0000-00-00') ? date('d-M-Y', strtotime($item['expecteddeliverydate'])) : 'N/A'; ?>
              </td>
              <td class="text-center">
                <?php if ($isCancelled): ?>
                  <span class="badge bg-danger text-white"><i class="fa fa-ban me-1"></i>Cancelled</span>
                <?php else: ?>
                  <span class="badge bg-success text-white"><i class="fa fa-check me-1"></i>Active</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
        <?php if (!empty($items)): ?>
        <tfoot style="background-color: #f8fafc; font-weight: 700;">
          <tr>
            <td colspan="5" class="text-end text-uppercase" style="color: #334155;">Total Order Value</td>
            <td class="text-end text-primary fw-bold" style="font-size: 0.95rem;">
              ₹<?php echo number_format($grandTotalAmount, 2); ?>
            </td>
            <td colspan="2"></td>
          </tr>
        </tfoot>
        <?php endif; ?>
      </table>
    </div>

    <!-- Payment & Settlement Information -->
    <?php 
    $ttlrcv = 0; 
    foreach ($paymentrcv as $p) {
        $ttlrcv += (float)($p['advanceamount'] ?? 0);
    }
    if ($ttlrcv > 0 || !empty($existingCreditNotes)): 
    ?>
    <div class="card border-info shadow-xs mb-3" style="background-color: #f0f9ff; border-left: 4px solid #0284c7 !important;">
      <div class="card-body p-3 small">
        <h6 class="fw-bold text-primary mb-1 d-flex align-items-center">
          <i class="fa fa-receipt me-2"></i> Financial & Payment Details
        </h6>
        <?php if ($ttlrcv > 0): ?>
          <div class="text-dark mb-1">
            Received Advance: <strong class="text-success">₹<?php echo number_format($ttlrcv, 2); ?></strong>
            <?php foreach ($paymentrcv as $p): ?>
              (<?php echo strtoupper($p['sourceofpayment'] ?? 'Payment'); ?>: ₹<?php echo number_format($p['advanceamount'] ?? 0, 2); ?>)
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($existingCreditNotes)): ?>
          <div class="alert alert-warning py-1.5 px-2 mb-0 mt-2 border-warning text-dark small">
            <i class="fa fa-exclamation-triangle text-warning me-1"></i>
            <strong>Active Credit Note Detected:</strong> 
            <?php foreach ($existingCreditNotes as $cn): ?>
              <span><?php echo htmlspecialchars($cn['creditnote_no']); ?> (₹<?php echo number_format($cn['credit_amount'], 2); ?>)</span>
            <?php endforeach; ?>
            <div class="mt-1">Undoing this rejection will automatically <strong>cancel / reverse this Credit Note</strong> in customer ledger and reinstate the advance balance to this order.</div>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Mandatory Undo Reason Box -->
    <div class="card border-warning shadow-xs mb-3" style="background-color: #fffdf5;">
      <div class="card-body p-3">
        <label for="undoRemarkInput" class="form-label fw-bold text-dark mb-1 d-flex align-items-center">
          <i class="fa fa-comment-dots text-warning me-2 fs-16"></i> Undo Reason / Remark <span class="text-danger ms-1">* (Mandatory)</span>
        </label>
        <textarea id="undoRemarkInput" 
                  class="form-control" 
                  rows="2" 
                  placeholder="Enter detailed reason for undoing rejection (e.g. Product change resolved, Customer reconfirmed order, etc.)..." 
                  style="border: 1.5px solid #fde047; font-size: 0.9rem;"></textarea>
        <div id="undoRemarkError" class="text-danger small fw-bold mt-1" style="display: none;">
          <i class="fa fa-exclamation-triangle me-1"></i> Undo reason is mandatory! Please enter a reason before restoring.
        </div>
      </div>
    </div>

    <!-- Modal Footer Actions -->
    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
      <button type="button" class="btn btn-secondary px-3" onclick="$('#undoOrderModal').modal('hide')">
        <i class="fa fa-times me-1"></i> Close
      </button>

      <div class="d-flex align-items-center gap-2">
        <?php if ($cancelledItemsCount > 0): ?>
          <button type="button" 
                  class="btn btn-warning fw-bold px-3 shadow-xs btnUndoOrderSubmitAction" 
                  id="btnUndoOrderSubmit" 
                  data-psono="<?php echo htmlspecialchars($psono, ENT_QUOTES); ?>">
            <i class="fa fa-undo me-1" id="undoBtnIcon"></i>
            <span class="undoBtnLabel">Undo Rejection & Restore (<span class="undoBtnCountText"><?php echo $cancelledItemsCount; ?></span>)</span>
          </button>
        <?php else: ?>
          <span class="badge bg-success p-2 fs-14">
            <i class="fa fa-check-circle me-1"></i> All Items In This Order Are Already Active
          </span>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>
