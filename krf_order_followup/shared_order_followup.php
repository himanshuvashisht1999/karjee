<?php
require '../header.php';
require_once dirname(__DIR__).'/krf_enquiry_followup/helpers.php';
$_SESSION['order_followup_csrf']=$_SESSION['order_followup_csrf']??bin2hex(random_bytes(24));
?>
<div class="container-fluid py-3" id="sharedOrderFollowup" data-csrf="<?=htmlspecialchars($_SESSION['order_followup_csrf'],ENT_QUOTES)?>">
<h4>Order Follow-up — Converted Enquiries</h4><p>Uses the existing enquiry follow-up history table. Each converted enquiry remains linked to its PSO.</p>
<div class="row g-2 mb-3"><div class="col-md-5"><input class="form-control" id="ofSearch" placeholder="Search client, enquiry, PSO"></div><div class="col-auto"><button class="btn btn-outline-primary" id="ofRefresh">Refresh</button></div></div>
<div class="table-responsive"><table class="table table-bordered table-sm"><thead><tr><th>Client</th><th>Enquiry</th><th>Product</th><th>PSO</th><th>Next Follow-up</th><th>Last Reason</th><th>Action</th></tr></thead><tbody id="ofRows"></tbody></table></div>
<div class="modal fade" id="ofModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Order Follow-up</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div id="ofHistory" class="mb-3"></div><label>Conversation / Remarks</label><textarea id="ofRemarks" class="form-control mb-2" rows="3"></textarea><label>Next follow-up</label><input type="datetime-local" id="ofNext" class="form-control mb-2"><label>Outcome</label><select id="ofReason" class="form-control"><option value="Call Me Later">Call Me Later</option><option value="Completed">Completed</option></select></div><div class="modal-footer"><button class="btn btn-primary" id="ofSave">Save</button></div></div></div></div>
</div>
<?php require '../footer.php'; ?>
<script src="../krf_order_followup/shared_order_followup.js?v=1"></script>
