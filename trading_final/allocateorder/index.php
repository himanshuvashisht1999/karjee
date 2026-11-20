<?php 
  include('../../header.php');
  date_default_timezone_set("Asia/Calcutta");
  $mdid ="";
  if(isset($_GET['mdid'])){
    $mdid = $_GET['mdid'];
  }
  $dddr = loginmodulesdetails_frommaster($authconn,$companyid,$subcompanyid,$projectid,"moduleid",$mdid);
  $groupid ="";
  if(is_array($dddr) && count($dddr)>0){
    $groupid = $dddr[0]['groupid'];
  }
?>
<style>
/* --- Global Styles & Fixes --- */
body {
    background-color: #f4f7fa;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

#history_modal .modal-body {
    max-height: 80vh;
    overflow-y: auto;
    background: #f8faff;
    padding: 20px;
}
 
/* Custom Details/Summary (Accordions) */
details > summary {
    cursor: pointer;
    list-style: none;
    outline: none;
}

details > summary::-webkit-details-marker {
    display: none;
}

/* Modal Styling */
#history_modal .modal-content {
    border: none;
    border-radius: 20px;
    box-shadow: 0 15px 50px rgba(0,0,0,0.1);
    overflow: hidden;
}

#history_modal .modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 1.5rem 2rem;
    border-bottom: none;
}

#history_modal .modal-title {
    font-size: 1.4rem;
    font-weight: 700;
    letter-spacing: -0.02em;
}

#history_modal .btn-close-custom {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

#history_modal .btn-close-custom:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
}

/* Info Bar (Floating Card) */
.info-bar {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    margin: -30px 20px 25px;
    border-radius: 15px;
    padding: 20px 25px;
    box-shadow: 0 8px 32px rgba(31, 38, 135, 0.07);
    border: 1px solid rgba(255, 255, 255, 0.18);
    position: relative;
    z-index: 10;
}

.info-label {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #718096;
    margin-bottom: 5px;
    display: block;
}

/* Modern Card for Sections */
.modern-section {
    background: white;
    border-radius: 12px;
    margin-bottom: 16px;
    border: 1px solid #edf2f7;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.modern-section:hover {
    box-shadow: 0 4px 20px rgba(0,0,0,0.03);
}

.modern-section summary {
    padding: 16px 20px;
    font-weight: 600;
    color: #2d3748;
    display: flex;
    align-items: center;
    gap: 12px;
}

.modern-section summary i {
    color: #667eea;
    font-size: 1.1rem;
    width: 20px;
    text-align: center;
}

.modern-section summary::after {
    content: '\f078';
    font-family: 'Font Awesome 5 Free';
    font-weight: 900;
    margin-left: auto;
    font-size: 0.8rem;
    color: #a0aec0;
    transition: transform 0.3s;
}

.modern-section[open] summary::after {
    transform: rotate(180deg);
}

.modern-section .content-inner {
    padding: 0 20px 20px;
    border-top: 1px solid #f7fafc;
    padding-top: 15px;
}

/* Table Refinements */
.table-modern {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.table-modern thead th {
    background: #f8fafc;
    color: #4a5568;
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 12px 15px;
    border-bottom: 2px solid #edf2f7;
}

.table-modern tbody td {
    padding: 14px 15px;
    color: #2d3748;
    border-bottom: 1px solid #edf2f7;
    font-size: 0.85rem;
}

.table-modern tbody tr:last-child td {
    border-bottom: none;
}

.table-modern tbody tr:hover td {
    background-color: #fdfdff;
}

/* Input Styles */
.form-control-modern {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 8px 12px;
    transition: all 0.2s;
    font-size: 0.9rem;
}

.form-control-modern:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    outline: none;
}

/* Custom Table for index page */
.table-responsive {
    background: white;
    padding: 15px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

#tblorder th {
    background: linear-gradient(135deg, ##e0f0fe 0%, #1e3799 100%) !important;
    color: black !important;
    padding: 15px;
    font-size: 0.8rem;
    font-weight: 600;
}

.badge-stage {
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 700;
}

/* Dynamic Question Card Styles */
.showquestion1, .showquestion2, .deliverysummary > div, .freightquestion > div {
    background: #ffffff;
    border: 1px solid #edf2f7 !important;
    border-radius: 16px;
    padding: 25px !important;
    margin-bottom: 20px !important;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
    transition: transform 0.2s, box-shadow 0.2s;
}

.showquestion1:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px rgba(0, 0, 0, 0.04);
}

.showquestion1.activerow {
    border-left: 5px solid #667eea !important;
}

.product-summary {
    background: #f8fafc;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    border: 1px solid #f1f5f9;
}

.product-summary div {
    margin-bottom: 10px;
    color: #4a5568;
    font-size: 0.95rem;
    line-height: 1.5;
}

.product-summary b {
    color: #1a202c;
}

.responsesummary {
    padding: 15px 0;
    border-top: 1px dashed #e2e8f0;
}

.responsesummary b {
    font-size: 1.1rem;
    color: #2d3748;
    display: block;
    margin-bottom: 20px;
}

.responsesummary label {
    padding: 12px 15px;
    border: 1px solid #edf2f7;
    border-radius: 10px;
    width: 100%;
    cursor: pointer;
    transition: all 0.2s;
    background: #fdfdff;
}

.responsesummary label:hover {
    background: #f7fafc;
    border-color: #cbd5e0;
}

.responsesummary input[type="radio"] {
    margin-right: 12px;
    accent-color: #667eea;
}

.responsesummary .form-control {
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    margin-top: 5px;
}

/* Button overrides for dynamic content */
.showquestion1 .btnSaveAll, .btnnext, .btnupdateaddress {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    padding: 12px 35px;
    border-radius: 50px;
    font-weight: 600;
    color: white;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    transition: all 0.3s;
}

.showquestion1 .btnSaveAll:hover, .btnnext:hover, .btnupdateaddress:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    color: white;
}

.showquestion1 .btnExit {
    border-radius: 50px;
    padding: 12px 35px;
    font-weight: 600;
}

/* Animations */
@keyframes fadeInSlide {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.showquestion1 {
    animation: fadeInSlide 0.4s ease-out;
}
</style>

<div class="container-fluid px-4 my-4">
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <h3 class="card-title text-center text-primary mb-0 fw-bold"> 
                <i class="fa fa-clipboard-list me-2"></i>Order Allocation Ready Stock Availability
                <small class="text-muted d-block mt-1">As On <?php echo date('d-m-Y'); ?></small>
            </h3>
        </div>
    </div>

    <!-- Controls Row Above Table (50/50 Split) -->
    <div class="header-actions-row mb-3 px-1">
        <div class="row align-items-end">
            <!-- Search & Refresh Column (50%) -->
            <div class="col-6"> 
                <div class="d-flex flex-column align-items-start">
                    <label class="fw-bold mb-1 text-dark" style="font-size: 0.85rem;">Search Order</label>
                    <div class="d-flex align-items-center gap-2 w-100">
                        <div class="input-group shadow-sm flex-grow-1">
                            <span class="input-group-text bg-white text-muted border-end-0">
                                <i class="fa fa-search"></i>
                            </span>
                            <input type="text" id='inputord' oninput="filterTable('#inputord', '#tblorder');" 
                                   class="form-control border-start-0" placeholder="Searching..." 
                                   style="height: 38px;">
                        </div>
                        <button type="button" class="btn btn-outline-info shadow-sm" id="btnRefresh" onclick="window.location.reload();" style="height: 38px;">  
                            <i class="fa fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Date Selection Column (50%) -->
            <div class="col-6">
                <div class="d-flex flex-column align-items-end">
                    <div style="width: 100%; max-width: 300px;">
                        <label for="mydate" class="fw-bold mb-1 text-dark" style="font-size: 0.85rem;">Promise Delivery Date</label>
                        <input type="date" id="mydate" name="mydate" class="form-control shadow-sm"  
                               style="height: 38px;" value="<?php echo date('Y-m-d', strtotime('+10 days')); ?>">
                    </div>
                </div>
            </div> 
        </div>
    </div>

    <div class="table-responsive" style="height:65vh;overflow-y:auto;"> 
        <table class="table table-hover table-bordered shadow-sm" id="tblorder">
            <thead class="sticky-top thead-dark">
                <tr> 
                    <th class="sticky-top text-center">S.No</th> 
                    <th class="sticky-top text-center">#</th>
                    <th class="sticky-top text-start">Client Name</th>
                    <th class="sticky-top text-start">Product Name</th>
                    <th class="sticky-top text-center">Promised Delivery Date</th>
                    <th class="sticky-top text-center">Order Qty</th>
                    <th class="sticky-top text-center">Reserved From Stock Order #</th> 
                    <th class="sticky-top text-center">Stock Order Qty</th> 
                    <th class="sticky-top text-center">Stock Order Stages</th>
                    <th class="sticky-top text-center">Stage Qty</th>
                    <th class="sticky-top text-center">Available Dt</th> 
                    <th class="sticky-top text-center">Prov Ord No</th> 
                </tr>
            </thead>
            <tbody></tbody>
            <tfoot></tfoot>
        </table>
    </div>
</div>   
         
        
    <!-- HISTORY & COMMUNICATION MODAL --> 
    <div class="modal fade" id="history_modal" tabindex="-1" role="dialog" aria-labelledby="lblclientname" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: 95%;">
            <div class="modal-content">
                
                <!-- Modal Header -->
                <div class="modal-header text-white">
                    <div class="d-flex align-items-center">
                        <div class="bg-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 45px; height: 45px;">
                            <i class="fa fa-history text-primary fs-4"></i>
                        </div>
                        <div>
                            <h5 class="modal-title mb-0" id="lblclientname">Active Order History</h5>
                            <small class="opacity-75">View communication logs and order status</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close-custom btnExit ms-auto" aria-label="Close">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                
                <!-- Modal Body -->
                <div class="modal-body p-0">

                    <!-- Info Bar / Control Row -->
                    <div class="info-bar">
                        <div class="row g-4 mt-4 align-items-end d-none">
                            <div class="col-md-5">
                                <div id="client_quick_info" class="d-flex flex-column">
                                    <!-- Populated dynamically if needed -->
                                     <span class="text-muted small">Loading client information...</span>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="contactno" class="info-label">
                                            <i class="fa fa-phone-alt me-1"></i> Live Session Contact No
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-end-0"><i class="fa fa-phone text-muted small"></i></span>
                                            <input type="text" class="form-control form-control-modern border-start-0 ps-0" id="contactno" autocomplete="off" placeholder="Contact No" oninput="this.value=this.value.replace(/[^0-9+\-()]/g,'');" maxlength="10">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="communicationdate" class="info-label">
                                            <i class="fa fa-calendar-alt me-1"></i> Interaction Date
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-end-0"><i class="fa fa-calendar-day text-muted small"></i></span>
                                            <input type="date" class="form-control form-control-modern border-start-0 ps-0" id="communicationdate" value="<?php echo date('Y-m-d'); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="container-fluid px-4 pb-4">
                        <div class="row">
                            <!-- Left: Order and Payment History -->
                            <div class="col-lg-12">
                                <details class="modern-section alwaysopen" id="history_details">
                                    <summary id="lblactivehistory">
                                        <i class="fa fa-shopping-cart"></i> Active Order History
                                    </summary>
                                    <div class="content-inner">
                                        <div class="table-responsive p-0 shadow-none border-0">
                                            <table class="table-modern" id="tblhistory">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 60px;">S.No</th> 
                                                        <th>Product Name</th>
                                                        <th class="text-center">Promised Dt</th>
                                                        <th class="text-center">Order Qty</th>
                                                        <th class="text-center">Stage</th>
                                                        <th class="text-center">Avail Qty</th>
                                                        <th class="text-center">Avail Dt</th>
                                                        <th class="d-none">#</th>
                                                    </tr>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </details> 

                                <details class="modern-section alwaysopen d-none" id="paymenthistory">
                                    <summary>
                                        <i class="fa fa-file-invoice-dollar"></i> Client Statement
                                    </summary>
                                    <div class="content-inner">
                                        <div class="table-responsive p-0 shadow-none border-0">
                                            <table class="table-modern" id="tblpaymenthistory">
                                                <thead>
                                                    <tr>
                                                        <th>Date</th>
                                                        <th class="d-none">Type</th>
                                                        <th>Txn No</th>
                                                        <th>Description</th>
                                                        <th class="text-end">Debit</th>
                                                        <th class="text-end">Credit</th>
                                                        <th class="text-end">Balance</th>
                                                    </tr>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </details> 
                            </div>
    
                            <!-- Right: Communication Details -->
                            <div class="col-lg-12 mt-3"> 
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-primary bg-opacity-10 p-2 rounded-circle me-2">
                                        <i class="fa fa-comments text-primary"></i>
                                    </div>
                                    <h6 class="mb-0 fw-bold" style="color: #2d3748;">Communication Details</h6>
                                </div>

                                <div class="row d-none mb-3">
                                    <div class="col-md-12">
                                        <label for="recipientname" class="info-label">Recipient Name</label>
                                        <input type="text" class="form-control form-control-modern" id="recipientname" autocomplete="off" placeholder="Enter recipient name">
                                    </div>
                                </div>                             

                                <details class="modern-section alwaysopen" id="phonestage1" open>
                                    <summary><i class="fa fa-check-circle"></i> Product Confirmation</summary>
                                    <div class="content-inner">
                                        <div class="initialquestion"> </div>
                                    </div>
                                </details>

                                <details class="modern-section alwaysopen" id="phonestage2" open>
                                    <summary><i class="fa fa-truck"></i> Delivery Summary</summary>
                                    <div class="content-inner">
                                        <div class="deliverysummary"> </div>
                                    </div>
                                </details>

                                <details class="modern-section alwaysopen" id="phonestage3" open>
                                    <summary><i class="fa fa-map-marker-alt"></i> Delivery Address Confirmation</summary>
                                    <div class="content-inner">
                                        <div class="freightquestion"> </div>
                                    </div>
                                </details>

                                <details class="modern-section alwaysopen" id="phonestage4" open>
                                    <summary><i class="fa fa-paper-plane"></i> Finalize & Email</summary>
                                    <div class="content-inner">
                                        <div class="card bg-light border-0 p-4 rounded-4">
                                            <div class="row g-4">
                                                <div class="col-md-6">
                                                    <label for="txttoemail" class="info-label">Recipient Email</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-white border-end-0"><i class="fa fa-envelope text-muted"></i></span>
                                                        <input type="email" class="form-control form-control-modern border-start-0 ps-0" id="txttoemail" placeholder="customer@example.com">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="txtccemail" class="info-label">CC Email (Comma separated)</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-white border-end-0"><i class="fa fa-copy text-muted"></i></span>
                                                        <input type="email" class="form-control form-control-modern border-start-0 ps-0" id="txtccemail" name="txtccemail" placeholder="manager@example.com">
                                                    </div>
                                                </div>
                                                <div class="col-md-12">
                                                    <label for="txtemailbody" class="info-label">Email Body Content</label>
                                                    <textarea class="form-control form-control-modern" id="txtemailbody" name="txtemailbody" rows="6" placeholder="Dear Customer, Please find the details below..."></textarea>
                                                </div>
                                                
                                                <div class="col-md-12 text-center mt-4">
                                                    <div class="d-flex gap-3 justify-content-center">
                                                        <button type="button" class="btn btn-primary px-5 py-2 rounded-pill shadow-sm" id="btnsentemail">
                                                            <i class="fa fa-paper-plane me-2"></i> Send Communication Email
                                                        </button>
                                                        <button type="button" class="btn btn-outline-secondary px-5 py-2 rounded-pill shadow-sm" id="btcloseconservation">
                                                            <i class="fa fa-times-circle me-2"></i> Close Conversation
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </details>    
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

 
    <div class="modal" id="msgModal" tabindex="-1" role="dialog" aria-labelledby="partialQtyPermissionTitle" data-backdrop="static" data-keyboard="false"> 
        <div class="modal-dialog modal-lg modal-dialog-scrollable" style="max-width: 50%;">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="msgTitle">
                        Permission Required
                    </h5>                     
                </div>
                <div class="modal-body">
                    <p id="textMessage" class="mb-0"></p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-success mx-2" id="btnyes">
                        <span class="btn-text">Proceed Anyway</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                    <button type="button" class="btn btn-info mx-2" id="btnno">
                        Seek Permission
                    </button>
                    <button type="button" class="btn btn-secondary mx-2" data-dismiss="modal">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('../../footer.php'); ?>
<script>
    window.serverCurrentDate = "<?= date('d-m-Y'); ?>";
    window.displayquestionno = 1;
</script>

<script type="text/javascript" src="js/control.js"></script>

<script type="text/javascript" src="js/function.js"></script>
<script>    
$(document).ready(function () {
    orderdetails("tblorder");
});
 
 
</script>
