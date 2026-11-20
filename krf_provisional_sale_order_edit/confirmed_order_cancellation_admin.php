<?php
require '../header.php';require_once __DIR__.'/module_order/cancellation_helpers.php';
try{caAuthorize();}catch(Throwable $e){http_response_code(403);echo htmlspecialchars($e->getMessage());exit;}
$_SESSION['ca_csrf']=$_SESSION['ca_csrf']??bin2hex(random_bytes(32));
?>
<link rel="stylesheet" href="css/cancellation_admin.css?v=20261008-client-address-dates">
<div class="container-fluid ca-page" id="caPage" data-csrf="<?=htmlspecialchars($_SESSION['ca_csrf'])?>">
  <div class="ca-page-heading"><h4>Confirmed Order Cancellation</h4><span>Administration</span></div>
  <div class="ca-toolbar"><input id="caSearch" class="form-control" placeholder="Search client, PSO, SO or contact" aria-label="Search orders"><a id="caRefundLink" class="btn btn-outline-primary">Refunds &amp; Retained Advances</a></div>
  <div class="ca-table-wrap"><table class="table table-bordered" id="caOrders"><thead><tr><th>Client</th><th>Contact</th><th>PSO</th><th>SO</th><th>Created</th><th>Action</th></tr></thead><tbody></tbody></table></div>
  <div id="caReview" class="ca-dialog" role="dialog" aria-modal="true" aria-labelledby="caDialogTitle" style="display:none" hidden>
    <header class="ca-dialog-heading"><div><h5 id="caDialogTitle">Review Cancellation</h5><div id="caClient"></div><div id="caClientContact" class="ca-client-contact"></div></div><button type="button" id="caCloseTop" class="ca-close-icon" aria-label="Close cancellation review">&times;</button></header>
    <div class="ca-dialog-body">
      <section class="ca-section"><h6>Order Items</h6><div class="ca-table-wrap"><table class="table table-bordered" id="caItems"><thead><tr><th>Product</th><th>Stock</th><th>Client qty</th><th>Rate ₹</th><th>Amount ₹</th></tr></thead><tbody></tbody></table></div></section>
      <section class="ca-section"><h6>Sowing Details</h6><div class="ca-table-wrap"><table class="table table-bordered" id="caSowing"><thead><tr><th>Stock</th><th>Type</th><th>Planned</th><th>Actual sown</th><th>Remaining</th><th>Status</th></tr></thead><tbody></tbody></table></div></section>
      <section class="ca-section"><div class="ca-section-heading"><h6>Advance Payments</h6><span id="caPaymentNote"></span></div><div class="ca-table-wrap"><table class="table table-bordered" id="caPayments"><thead><tr><th>Payment</th><th>Date</th><th>UTR</th><th>Verification</th><th>Amount ₹</th></tr></thead><tbody></tbody></table></div></section>
      <fieldset id="caDecision" class="ca-decision"><legend>Choose Settlement Option</legend><div class="ca-choice-grid">
        <label class="ca-choice" for="caChoiceRefund"><input type="radio" name="ca-settlement-choice" id="caChoiceRefund" value="refund"><span><strong>Refund the Amount</strong><small>Record the verified amount as refund pending.</small></span></label>
        <label class="ca-choice" for="caChoiceAdvance"><input type="radio" name="ca-settlement-choice" id="caChoiceAdvance" value="advance"><span><strong>Mark as Advance</strong><small>Retain the verified amount for this client.</small></span></label>
      </div></fieldset>
      <div class="ca-reason"><label for="caReason">Cancellation Reason</label><textarea id="caReason" class="form-control" rows="2" placeholder="Enter the reason for cancelling this order"></textarea></div>
    </div>
    <footer class="ca-dialog-footer"><button type="button" id="caClose" class="btn btn-outline-secondary">Close</button><button type="button" id="caCancel" class="btn btn-danger">Cancel Order &amp; Record Settlement</button></footer>
  </div>
</div>
<?php require '../footer.php'; ?>
<script src="js/cancellation_admin.js?v=20261008-client-address-dates"></script>
