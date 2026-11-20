<?php
require '../header.php';require_once __DIR__.'/module_order/cancellation_helpers.php';
try{caAuthorize();}catch(Throwable $e){http_response_code(403);echo htmlspecialchars($e->getMessage());exit;}
$_SESSION['ca_csrf']=$_SESSION['ca_csrf']??bin2hex(random_bytes(32));
?>
<link rel="stylesheet" href="css/cancellation_admin.css?v=20261008-client-address-dates">
<div class="container-fluid ca-page" id="caPage" data-csrf="<?=htmlspecialchars($_SESSION['ca_csrf'])?>" data-mode="refunds">
  <div class="ca-page-heading"><h4>Refunds &amp; Retained Advances</h4><span>Administration</span></div>
  <div class="ca-toolbar"><input id="caSearch" class="form-control" placeholder="Search PSO, client or status" aria-label="Search refunds"><a id="caBackLink" class="btn btn-outline-secondary">Cancellation Orders</a></div>
  <div class="ca-table-wrap"><table id="caSettlements" class="table table-bordered"><thead><tr><th>PSO</th><th>SO</th><th>Client</th><th>Phone No.</th><th>Payment</th><th>Refund ₹</th><th>Retained advance ₹</th><th>Refund status</th><th>UTR / Date</th><th>Action</th></tr></thead><tbody></tbody></table></div>
  <div id="caRefundModal" class="ca-dialog" role="dialog" aria-modal="true" aria-labelledby="caRefundTitle" style="display:none" hidden>
    <header class="ca-dialog-heading"><div><h5 id="caRefundTitle">Record Client Refund</h5><div id="caRefundClient"></div><div id="caRefundContact" class="ca-client-contact"></div></div><button type="button" class="ca-close-icon ca-refund-close" aria-label="Close refund form">&times;</button></header>
    <form id="caRefundForm" novalidate><div class="ca-dialog-body">
      <div class="ca-refund-summary"><div><span>Cancelled order</span><strong id="caRefundOrder"></strong></div><div><span>Original payment</span><strong id="caRefundPayment"></strong></div><div><span>Refund payable</span><strong id="caRefundAmount"></strong></div></div>
      <input type="hidden" name="detail_id"><input type="hidden" name="amount">
      <div class="ca-refund-fields"><div><label for="caRefundUTR">UTR / Transaction Number</label><input id="caRefundUTR" name="utr" maxlength="45" required class="form-control" placeholder="Enter refund transaction reference"></div><div><label for="caRefundDate">Transaction Date</label><input id="caRefundDate" name="transaction_date" type="date" max="<?= (new DateTimeImmutable('now',new DateTimeZone('Asia/Kolkata')))->format('Y-m-d') ?>" required class="form-control"></div><div class="ca-proof-field"><label for="caRefundProof">Refund Transaction Proof</label><input id="caRefundProof" name="proof" type="file" accept="application/pdf,image/jpeg,image/png" required class="form-control"><small>PDF, JPG or PNG · Up to 5 MB</small></div></div>
      <p class="ca-note">Confirm only after this amount has actually been paid to the client shown above.</p>
    </div><footer class="ca-dialog-footer"><button type="button" class="btn btn-outline-secondary ca-refund-close">Close</button><button type="submit" class="btn btn-primary">Confirm Refund Recorded</button></footer></form>
  </div>
</div>
<?php require '../footer.php'; ?>
<script src="js/cancellation_admin.js?v=20261008-client-address-dates"></script>
