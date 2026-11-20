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
$items = getProvisionalSalesOrderItems($krfconn, $psono);
$orderStatus = trim($order['status'] ?? '');
$isOrderCancelled = ($orderStatus === 'Cancelled');

$activeItemsCount = 0;
$ttlamount = 0;
foreach ($items as $it) {
    if (strtolower(trim($it['status'] ?? '')) !== 'cancelled') {
        $activeItemsCount++;
        $ttlamount += (float)($it['amount'] ?? 0);
    }
}
?>

<div class="card border-0 mb-1">
  <div class="card-body p-2 p-md-3">

    <!-- Top Review Header (no PDF button) -->
    <div class="text-center mb-3">
      <h4 class="fw-normal mb-0" style="color: #334155; font-size: 1.35rem; letter-spacing: 0.2px;">
        Provisional Order Review
      </h4>
    </div>

    <!-- Order & Customer Info Grid matching Screenshot 2 -->
    <div class="row g-2 mb-3" style="font-size: 0.92rem; line-height: 1.7; color: #1e293b;">
      <div class="col-md-6 ps-2">
        <div><strong>Customer:</strong> <span><?php echo htmlspecialchars($order['clientname'] ?? 'N/A'); ?></span></div>
        <div><strong>Email:</strong> <span><?php echo htmlspecialchars(!empty($order['emailid']) ? $order['emailid'] : 'N/A'); ?></span></div>
        <div><strong>Mobile:</strong> <span><?php echo htmlspecialchars(!empty($order['mobileno']) ? $order['mobileno'] : 'N/A'); ?></span></div>
        <div>
          <strong>Status:</strong> 
          <?php if ($isOrderCancelled): ?>
            <span class="badge bg-danger text-white"><i class="fa fa-ban me-1"></i>Cancelled</span>
          <?php elseif ($orderStatus === 'Confirmed'): ?>
            <span class="badge bg-success text-white"><i class="fa fa-check me-1"></i>Confirmed</span>
          <?php else: ?>
            <span><?php echo htmlspecialchars($orderStatus ?: 'Draft'); ?></span>
          <?php endif; ?>
        </div>
        <?php if (!empty($order['remarks'])): ?>
          <div class="small text-danger mt-1"><strong>Existing Remarks:</strong> <?php echo nl2br(htmlspecialchars($order['remarks'])); ?></div>
        <?php endif; ?>
      </div>
      
      <div class="col-md-6 pe-2">
        <div><strong>Order No:</strong> <span><?php echo htmlspecialchars($order['psono']); ?></span></div>
        <div><strong>Order Date:</strong> <span><?php echo !empty($order['created_at']) ? date('d-M-Y', strtotime($order['created_at'])) : 'N/A'; ?></span></div>
        <div><strong>Delivery Address:</strong> <span><?php echo htmlspecialchars(!empty($order['delivery_address']) ? $order['delivery_address'] : 'N/A'); ?></span></div>
        <div><strong>Created By:</strong> <span><?php echo htmlspecialchars($created_by); ?></span></div>
      </div>
    </div>

<style>
  /* Reset global header.php checkbox scale(2) and margin to keep checkboxes properly sized and centered */
  .cancel-order-table input[type="checkbox"],
  #cancelOrderModal input[type="checkbox"],
  .cancel-item-chk,
  #checkAllCancelItems {
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
    accent-color: #2563eb !important;
    vertical-align: middle !important;
  }

  .cancel-order-table th.col-checkbox,
  .cancel-order-table td.col-checkbox {
    width: 44px !important;
    min-width: 44px !important;
    max-width: 48px !important;
    text-align: center !important;
    vertical-align: middle !important;
    padding: 6px 2px !important;
  }

  /* Reset global header.php radio scale(2) and spacing for settlement radio buttons */
  #cancelOrderModal input[type="radio"],
  .settlement-radio-input {
    transform: none !important;
    -webkit-transform: none !important;
    -ms-transform: none !important;
    margin: 0 !important;
    width: 18px !important;
    height: 18px !important;
    min-width: 18px !important;
    max-width: 18px !important;
    min-height: 18px !important;
    max-height: 18px !important;
    display: inline-block !important;
    cursor: pointer !important;
    position: static !important;
    box-shadow: none !important;
    vertical-align: middle !important;
    flex-shrink: 0 !important;
  }

  .settlement-radio-card {
    display: inline-flex !important;
    align-items: center !important;
    gap: 8px !important;
    padding: 8px 16px !important;
    margin-right: 15px !important;
    margin-bottom: 6px !important;
    background: #ffffff !important;
    border: 1.5px solid #cbd5e1 !important;
    border-radius: 8px !important;
    cursor: pointer !important;
    transition: all 0.2s ease !important;
    user-select: none !important;
  }

  .settlement-radio-card:hover {
    border-color: #0ea5e9 !important;
    background: #f8fafc !important;
  }

  .settlement-radio-card.active-cn {
    border-color: #2563eb !important;
    background: #eff6ff !important;
  }

  .settlement-radio-card.active-refund {
    border-color: #ef4444 !important;
    background: #fef2f2 !important;
  }

  .settlement-radio-card.active-adjust {
    border-color: #059669 !important;
    background: #ecfdf5 !important;
  }

  .settlement-radio-label {
    margin: 0 0 0 8px !important;
    cursor: pointer !important;
    font-size: 0.88rem !important;
    font-weight: 600 !important;
    line-height: 1.3 !important;
  }
</style>

    <!-- Items Table with Checkbox as FIRST column and no serial number -->
    <div class="table-responsive rounded mb-3 border">
      <table class="table table-bordered table-hover align-middle mb-0 cancel-order-table" style="font-size: 0.88rem;">
        <thead style="background-color: #dbeafe; color: #1e3a8a;">
          <tr class="text-uppercase" style="font-weight: 700; font-size: 0.8rem; letter-spacing: 0.4px;">
            <th class="text-center align-middle col-checkbox" style="width: 44px; min-width: 44px; max-width: 48px; text-align: center; vertical-align: middle; padding: 6px 2px; background-color: #dbeafe; color: #1e3a8a;">
              <div class="cancel-chk-wrapper" style="display: flex; justify-content: center; align-items: center; width: 100%; min-height: 20px; margin: 0; padding: 0;">
                <input type="checkbox" 
                       id="checkAllCancelItems" 
                       class="shadow-none" 
                       title="Check / Uncheck All"
                       style="transform: none !important; -webkit-transform: none !important; -ms-transform: none !important; width: 17px !important; height: 17px !important; min-width: 17px !important; max-width: 17px !important; margin: 0 auto !important; cursor: pointer; accent-color: #2563eb; display: block;">
              </div>
            </th>
            <th style="background-color: #dbeafe; color: #1e3a8a;">Product</th>
            <th class="text-center" style="width: 80px; background-color: #dbeafe; color: #1e3a8a;">Qty</th>
            <th class="text-end" style="width: 100px; background-color: #dbeafe; color: #1e3a8a;">Rate</th>
            <th class="text-end" style="width: 100px; background-color: #dbeafe; color: #1e3a8a;">Discount</th>
            <th class="text-end" style="width: 120px; background-color: #dbeafe; color: #1e3a8a;">Amount</th>
            <th class="text-center" style="width: 120px; background-color: #dbeafe; color: #1e3a8a;">Exp. Delivery</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $sowingLockedCount = 0;
          foreach ($items as $item):
            $itemStatus = trim($item['status'] ?? '');
            $isCancelled = (strtolower($itemStatus) === 'cancelled');
            $cleanName = preg_replace('/^(NonGrafted|Non-Grafted)\s*[-:]?\s*/i', '', $item['product_name'] ?? '');
            
            // Dispatch Check & Sowing Started Check
            $isItemDispatched = false;
            $dispLabel = '';
            $itemProdId = trim((string)($item['product_id'] ?? ''));
            $stkNo = trim($item['stockorder_no'] ?? '');

            // 1. Check if item is already dispatched in krf_sales_order_items
            if (!empty($itemProdId) || !empty($stkNo)) {
                $dispStmt = $krfconn->prepare("SELECT status, dispatchedqty FROM krf_sales_order_items WHERE psono = ? AND (product_id = ? OR stockorder_no = ?) AND (LOWER(status) IN ('dispatched', 'fulldispatch', 'partialdispatch') OR dispatchedqty > 0) LIMIT 1");
                if ($dispStmt) {
                    $dispStmt->bind_param("sss", $psono, $itemProdId, $stkNo);
                    $dispStmt->execute();
                    $dispRes = $dispStmt->get_result();
                    if ($dispRes && $dispRes->num_rows > 0) {
                        $isItemDispatched = true;
                        $dispRow = $dispRes->fetch_assoc();
                        $dispLabel = $dispRow['status'] ?: 'Dispatched';
                    }
                    $dispStmt->close();
                }
                // 2. Check in krf_productionorder_consumed
                if (!$isItemDispatched) {
                    $dispStmt2 = $krfconn->prepare("SELECT status, dispatchedqty FROM krf_productionorder_consumed WHERE psono = ? AND (product_id = ? OR stockorderno = ?) AND (LOWER(status) IN ('dispatched', 'fulldispatch', 'partialdispatch') OR dispatchedqty > 0) AND IFNULL(tedit, '') = '' LIMIT 1");
                    if ($dispStmt2) {
                        $dispStmt2->bind_param("sss", $psono, $itemProdId, $stkNo);
                        $dispStmt2->execute();
                        $dispRes2 = $dispStmt2->get_result();
                        if ($dispRes2 && $dispRes2->num_rows > 0) {
                            $isItemDispatched = true;
                            $dispRow2 = $dispRes2->fetch_assoc();
                            $dispLabel = $dispRow2['status'] ?: 'Dispatched';
                        }
                        $dispStmt2->close();
                    }
                }
            }

            // Sowing Started & Own Stock Order Check
            $isSelfStock = false;
            $sowingStarted = false;
            $sowingQty = 0;
            $sowingStatusText = '';

            if (!$isItemDispatched && !empty($stkNo)) {
                // 1. Check krf_production_stockorder
                $stkStmt = $krfconn->prepare("SELECT psono, quantity, sowingstatus, workingstatus, sowingdate, scionsowingdate FROM krf_production_stockorder WHERE stockorder_no = ? AND (projectid = ? OR ? = 0) LIMIT 1");
                if ($stkStmt) {
                    $stkStmt->bind_param("sii", $stkNo, $projectid, $projectid);
                    $stkStmt->execute();
                    $stkData = $stkStmt->get_result()->fetch_assoc();
                    $stkStmt->close();
                    if ($stkData) {
                        $isSelfStock = (!empty($stkData['psono']) && strcasecmp(trim($stkData['psono']), $psono) === 0);
                        $sowingQty = (int)($stkData['quantity'] ?? 0);
                        $swStatus = strtolower(trim($stkData['sowingstatus'] ?? ''));
                        $wStatus = strtolower(trim($stkData['workingstatus'] ?? ''));
                        if (in_array($swStatus, ['start', 'sowed', 'done']) || in_array($wStatus, ['polyhouse', 'final dispatch'])) {
                            $sowingStarted = true;
                            $sowingStatusText = $stkData['sowingstatus'] ?: $stkData['workingstatus'];
                        }
                    }
                }

                // 2. Check krf_polyhouse_sowingschedule
                $schStmt = $krfconn->prepare("SELECT status, sowingqty FROM krf_polyhouse_sowingschedule WHERE stockorder_no = ? AND (projectid = ? OR ? = 0) AND LOWER(TRIM(status)) NOT IN ('delete','removed','notrequired')");
                if ($schStmt) {
                    $schStmt->bind_param("sii", $stkNo, $projectid, $projectid);
                    $schStmt->execute();
                    $schRes = $schStmt->get_result();
                    while ($schRow = $schRes->fetch_assoc()) {
                        $schSt = strtolower(trim($schRow['status'] ?? ''));
                        if ($schSt !== 'pending') {
                            $sowingStarted = true;
                            $sowingStatusText = $schRow['status'];
                        }
                        if (!empty($schRow['sowingqty']) && (int)$schRow['sowingqty'] > 0) {
                            $sowingQty = (int)$schRow['sowingqty'];
                        }
                    }
                    $schStmt->close();
                }

                // 3. Check tray level details in krf_polyhouse_sowingplant_details
                if (!$sowingStarted) {
                    $trStmt = $krfconn->prepare("SELECT COUNT(*) as sowed_cnt FROM krf_polyhouse_sowingplant_details WHERE stockorder_no = ? AND (projectid = ? OR ? = 0) AND sowingdate_internal IS NOT NULL AND sowingdate_internal > '1970-01-01'");
                    if ($trStmt) {
                        $trStmt->bind_param("sii", $stkNo, $projectid, $projectid);
                        $trStmt->execute();
                        $trRow = $trStmt->get_result()->fetch_assoc();
                        $trStmt->close();
                        if (!empty($trRow['sowed_cnt']) && (int)$trRow['sowed_cnt'] > 0) {
                            $sowingStarted = true;
                            $sowingStatusText = 'Trays Sowed (' . $trRow['sowed_cnt'] . ')';
                        }
                    }
                }
            }

            $isLockedDueToSowing = (!$isCancelled && !$isItemDispatched && $isSelfStock && $sowingStarted);
            if ($isLockedDueToSowing) {
                $sowingLockedCount++;
            }
          ?>
          <tr class="<?php echo $isCancelled ? 'table-secondary opacity-75' : ($isItemDispatched ? 'table-info bg-opacity-25' : ($isLockedDueToSowing ? 'table-warning bg-opacity-25' : '')); ?>">
            <td class="text-center align-middle col-checkbox" style="width: 44px; min-width: 44px; max-width: 48px; text-align: center; vertical-align: middle; padding: 6px 2px;">
              <?php if ($isCancelled): ?>
                <span class="badge bg-secondary p-1" title="Already Cancelled"><i class="fa fa-ban"></i></span>
              <?php elseif ($isItemDispatched): ?>
                <span class="badge bg-primary text-white p-1 shadow-xs" title="Already Dispatched - Cannot cancel. Use Sales Return process."><i class="fa fa-truck"></i></span>
              <?php elseif ($isLockedDueToSowing): ?>
                <span class="badge bg-warning text-dark p-1 shadow-xs" title="Sowing started. Cannot remove this item."><i class="fa fa-lock"></i></span>
              <?php else: ?>
                <?php
                  $isItemPreChecked = false;
                  if ($targetItemId > 0) {
                      $isItemPreChecked = ((int)$item['id'] === $targetItemId);
                  }
                ?>
                <div class="cancel-chk-wrapper" style="display: flex; justify-content: center; align-items: center; width: 100%; min-height: 20px; margin: 0; padding: 0;">
                  <input type="checkbox" 
                         class="cancel-item-chk shadow-none" 
                         value="<?php echo $item['id']; ?>" 
                         data-product="<?php echo htmlspecialchars(!empty($cleanName) ? $cleanName : ($item['product_name'] ?? ''), ENT_QUOTES); ?>"
                         data-amount="<?php echo $item['amount'] ?? 0; ?>"
                         <?php echo $isItemPreChecked ? 'checked' : ''; ?>
                         style="transform: none !important; -webkit-transform: none !important; -ms-transform: none !important; width: 17px !important; height: 17px !important; min-width: 17px !important; max-width: 17px !important; margin: 0 auto !important; cursor: pointer; accent-color: #2563eb; display: block;">
                </div>
              <?php endif; ?>
            </td>
            <td>
              <div class="fw-bold <?php echo $isCancelled ? 'text-decoration-line-through text-muted' : 'text-dark'; ?>">
                <?php echo htmlspecialchars(!empty($cleanName) ? $cleanName : ($item['product_name'] ?? '')); ?>
              </div>
              <?php if ($isCancelled): ?>
                <div class="badge bg-danger text-white mt-1" style="font-size: 0.68rem;">
                  <i class="fa fa-ban me-1"></i>Cancelled
                </div>
                <?php if (!empty($item['remark'])): ?>
                  <div class="small text-danger fst-italic mt-0.5" style="font-size: 0.72rem;">
                    Reason: <?php echo htmlspecialchars($item['remark']); ?>
                  </div>
                <?php endif; ?>
              <?php elseif ($isItemDispatched): ?>
                <div class="mt-1 d-flex flex-wrap align-items-center gap-1">
                  <span class="badge bg-primary text-white border border-primary shadow-xs" style="font-size: 0.68rem;">
                    <i class="fa fa-truck me-1"></i><?php echo htmlspecialchars($dispLabel ?: 'Dispatched'); ?>
                  </span>
                </div>
                <div class="small text-primary fw-bold mt-1" style="font-size: 0.72rem; line-height: 1.3;">
                  <i class="fa fa-lock me-1"></i>Cannot cancel: Plant has already been dispatched. For returns, please use Sales Return process.
                </div>
              <?php elseif ($isLockedDueToSowing): ?>
                <div class="mt-1 d-flex flex-wrap align-items-center gap-1">
                  <span class="badge bg-warning text-dark border border-warning" style="font-size: 0.68rem;">
                    <i class="fa fa-seedling me-1 text-success"></i>Sowing Started: <?php echo htmlspecialchars($sowingStatusText ?: 'In Progress'); ?>
                  </span>
                  <?php if ($sowingQty > 0): ?>
                    <span class="badge bg-light text-dark border" style="font-size: 0.68rem;">
                      Sown Qty: <?php echo number_format($sowingQty); ?>
                    </span>
                  <?php endif; ?>
                </div>
                <div class="small text-danger fw-bold mt-1" style="font-size: 0.72rem; line-height: 1.3;">
                  <i class="fa fa-lock me-1"></i>Cannot remove: Sowing has started. Only quantity edit is allowed in consultation with the sowing department (Order Qty &le; Sown Qty).
                </div>
              <?php endif; ?>
            </td>
            <td class="text-center <?php echo $isCancelled ? 'text-decoration-line-through text-muted' : ''; ?>">
              <?php echo number_format($item['quantity'] ?? 0, 0); ?>
            </td>
            <td class="text-end <?php echo $isCancelled ? 'text-decoration-line-through text-muted' : ''; ?>">
              ₹<?php echo number_format($item['rate'] ?? 0, 2); ?>
            </td>
            <td class="text-end text-danger <?php echo $isCancelled ? 'text-decoration-line-through' : ''; ?>">
              ₹<?php echo number_format($item['discount_amount'] ?? 0, 2); ?>
            </td>
            <td class="text-end fw-bold <?php echo $isCancelled ? 'text-decoration-line-through text-muted' : 'text-primary'; ?>">
              ₹<?php echo number_format($item['amount'] ?? 0, 2); ?>
            </td>
            <td class="text-center small">
              <?php echo (!empty($item['expecteddeliverydate']) && $item['expecteddeliverydate'] !== '0000-00-00') ? date('d-M-Y', strtotime($item['expecteddeliverydate'])) : 'N/A'; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot class="bg-light font-weight-bold" style="background-color: #f8fafc;">
          <tr>
            <td colspan="5" class="text-end text-uppercase" style="font-weight: 700; color: #334155;">Grand Total</td>
            <td class="text-end text-primary fw-bold" style="font-size: 1rem; color: #2563eb;">
              ₹<?php echo number_format($ttlamount, 2); ?>
            </td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>

    <!-- Payment Received info if any -->
    <?php 
    $ttlrcv = 0; 
    foreach ($paymentrcv as $p) {
        $ttlrcv += (float)($p['advanceamount'] ?? 0);
    }
    if ($ttlrcv > 0): 
    ?>
    <div class="bg-light p-2.5 rounded border mb-3 small">
      <h6 class="fw-bold mb-1 text-dark"><i class="fa fa-money-bill-wave text-success me-1"></i> Payment Received Against Order:</h6>
      <ul class="mb-1 ps-3">
        <?php foreach ($paymentrcv as $p): ?>
          <li>
            <strong><?php echo strtoupper($p['sourceofpayment'] ?? 'Payment'); ?>:</strong> 
            ₹<?php echo number_format($p['advanceamount'] ?? 0, 2); ?>
          </li>
        <?php endforeach; ?>
      </ul>
      <div><strong>Total Received:</strong> ₹<?php echo number_format($ttlrcv, 2); ?> | <strong>Active Balance:</strong> ₹<?php echo number_format(max(0, $ttlamount - $ttlrcv), 2); ?></div>
    </div>

    <!-- Meta info for cancel modal JS (active items count, sowing locked count) -->
    <div id="cancelOrderMeta" 
         data-active-items="<?php echo (int)$activeItemsCount; ?>" 
         data-locked-items="<?php echo (int)$sowingLockedCount; ?>" 
         data-total-items="<?php echo ($activeItemsCount - $sowingLockedCount); ?>"
         style="display: none;"></div>

    <!-- Financial Settlement Options for Confirmed / Advance-paid orders -->
    <div id="advanceSettlementSection" 
         class="card border-primary shadow-xs mb-3" 
         data-total-received="<?php echo (float)$ttlrcv; ?>"
         data-total-order-amount="<?php echo (float)$ttlamount; ?>"
         data-active-items="<?php echo (int)$activeItemsCount; ?>"
         data-locked-items="<?php echo (int)$sowingLockedCount; ?>"
         style="background-color: #f0fdf4; border-left: 4px solid #10b981 !important;">
      <div class="card-body p-3">
        <h6 class="fw-bold text-success mb-2 d-flex align-items-center">
          <i class="fa fa-hand-holding-usd me-2 fs-16"></i> Advance / Received Amount Settlement
        </h6>
        <div id="settlementNotice" class="mb-2 text-dark small">
          An amount of <strong>₹<?php echo number_format($ttlrcv, 2); ?></strong> has been received against this order. If the whole order is cancelled, choose how this received amount should be settled:
        </div>
        <div id="settlementRadioOptions" class="d-flex flex-wrap align-items-center mt-2" style="gap: 10px;">
          <label class="settlement-radio-card active-adjust mb-0" id="cardSettleAdjust" for="settleAdjust">
            <input class="settlement-radio-input" type="radio" name="financial_settlement" id="settleAdjust" value="adjust_order" checked style="accent-color: #059669;">
            <span class="settlement-radio-label text-success">
              <i class="fa fa-sync-alt me-1"></i> Adjust with Existing Order (Keep on remaining products)
            </span>
          </label>
          <label class="settlement-radio-card mb-0" id="cardSettleCreditNote" for="settleCreditNote">
            <input class="settlement-radio-input" type="radio" name="financial_settlement" id="settleCreditNote" value="credit_note" style="accent-color: #2563eb;">
            <span class="settlement-radio-label text-primary">
              <i class="fa fa-file-invoice me-1"></i> Issue Credit Note (Credited to Client Ledger Statement)
            </span>
          </label>
          <label class="settlement-radio-card mb-0" id="cardSettleRefund" for="settleRefund">
            <input class="settlement-radio-input" type="radio" name="financial_settlement" id="settleRefund" value="refund" style="accent-color: #ef4444;">
            <span class="settlement-radio-label text-danger">
              <i class="fa fa-undo-alt me-1"></i> Return Cash / Refund Amount to Customer
            </span>
          </label>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Mandatory Cancellation Reason / Remark Box -->
    <div class="card border-danger shadow-xs mb-3" style="background-color: #fff8f8;">
      <div class="card-body p-3">
        <label for="cancellationRemarkInput" class="form-label fw-bold text-danger mb-1 d-flex align-items-center">
          <i class="fa fa-comment-dots me-2 fs-16"></i> Cancellation Reason / Remark <span class="text-danger ms-1">* (Mandatory)</span>
        </label>
        <textarea id="cancellationRemarkInput" 
                  class="form-control" 
                  rows="2" 
                  placeholder="Enter detailed reason for cancellation (cannot cancel item or order without this)..." 
                  style="border: 1.5px solid #fca5a5; font-size: 0.9rem;"></textarea>
        <div id="cancellationRemarkError" class="text-danger small fw-bold mt-1" style="display: none;">
          <i class="fa fa-exclamation-triangle me-1"></i> Remark is mandatory! You cannot cancel without providing a reason.
        </div>
      </div>
    </div>

    <!-- Modal Footer Actions -->
    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
      <button type="button" class="btn btn-secondary px-3" onclick="$('#cancelOrderModal').modal('hide')">
        <i class="fa fa-times me-1"></i> Close
      </button>

      <div class="d-flex align-items-center gap-2">
        <?php if ($sowingLockedCount > 0 && $sowingLockedCount === $activeItemsCount): ?>
          <div class="alert alert-warning py-1.5 px-3 mb-0 small fw-bold text-dark border-warning shadow-xs">
            <i class="fa fa-exclamation-triangle text-warning me-1"></i> Sowing has started for all items. Cannot remove order. Please use Edit Order to adjust quantity.
          </div>
        <?php elseif (!$isOrderCancelled && $activeItemsCount > 0): ?>
          <button type="button" 
                  class="btn btn-danger fw-bold px-3 shadow-xs" 
                  id="btnCancelOrderSubmit" 
                  data-psono="<?php echo htmlspecialchars($psono, ENT_QUOTES); ?>"
                  data-total-items="<?php echo ($activeItemsCount - $sowingLockedCount); ?>"
                  data-active-items="<?php echo (int)$activeItemsCount; ?>"
                  data-locked-items="<?php echo (int)$sowingLockedCount; ?>"
                  style="display: none;">
            <i class="fa fa-trash-alt me-1" id="cancelBtnIcon"></i>
            <span id="cancelBtnText">Cancel Selected Items</span>
          </button>
        <?php else: ?>
          <span class="badge bg-danger p-2 fs-14">
            <i class="fa fa-ban me-1"></i> Provisional Order is Cancelled
          </span>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>
