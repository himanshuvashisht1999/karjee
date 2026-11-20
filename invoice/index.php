<?php 
  include('../header.php');
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
    /* Premium Look & Feel Alignment */
    :root {
        --invoice-primary: #667eea;
        --invoice-secondary: #764ba2;
        --invoice-bg: #f8fafc;
        --invoice-card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    .container-main {
        padding: 24px;
        background: var(--invoice-bg);
        min-height: calc(100vh - 100px);
    }
    
    .card {
        border-radius: 16px;
        border: none;
        box-shadow: var(--invoice-card-shadow);
        transition: all 0.3s ease;
        overflow: hidden;
    }

    .card-title {
        color: var(--invoice-primary);
        letter-spacing: -0.5px;
    }

    /* Table Responsiveness & Aesthetics */
    .table-responsive {
        background: #fff;
        border-radius: 12px;
        overflow-x: auto;
    }
    
    #tblproductdetail {
        width: 100% !important;
        min-width: 1100px;
        font-size: 0.9rem;
        border-collapse: separate;
        border-spacing: 0;
    }
    
    #tblproductdetail thead th {
        background: var(--theme-gradient, linear-gradient(135deg, #667eea 0%, #764ba2 100%)) !important;
        color: white !important;
        text-align: center;
        vertical-align: middle;
        white-space: nowrap;
        padding: 14px 10px;
        border: none;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
    }

    #tblproductdetail tbody td {
        vertical-align: middle;
        white-space: nowrap;
        padding: 12px 10px;
        border-bottom: 1px solid #f1f5f9;
        color: #475569;
    }

    #tblproductdetail tbody tr:hover {
        background-color: #f8fafc;
    }

    /* Editable Fields Visual Feedback */
    [contenteditable="true"] {
        background-color: #fff9ed !important; /* Soft yellow base */
        border: 1px dashed #fbbf24 !important;
        border-radius: 4px;
        cursor: text;
        transition: all 0.2s;
    }
    
    #tblproductdetail td[contenteditable="true"] {
        background-color: #ffffd0 !important; /* More vivid yellow for table cells */
    }
    
    [contenteditable="true"]:focus {
        outline: none;
        background-color: #fef3c7 !important;
        border-style: solid;
        box-shadow: 0 0 0 2px rgba(251, 191, 36, 0.2);
    }

    /* Stepper Styling */
    .step-indicator {
        background: white;
        border-left: 6px solid var(--invoice-primary);
        color: #1e293b;
        font-weight: 600;
        padding: 16px 24px;
        border-radius: 8px;
        margin-bottom: 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        display: flex;
        align-items: center;
    }

    .step-indicator i {
        font-size: 1.25rem;
        color: var(--invoice-primary);
    }

    /* Action Buttons */
    .btn-action {
        border-radius: 10px;
        padding: 8px 24px;
        font-weight: 600;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    .btn-action:active {
        transform: translateY(0);
    }

    #displaypreview {
        background: #f8fafc;
        border: 2px dashed #e2e8f0;
        border-radius: 12px;
        min-height: 600px;
        padding: 0;
        overflow: hidden;
    }
    
    .form-label {
        font-weight: 600;
        color: #64748b;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 6px;
    }

    .form-control, .form-select {
        border-radius: 10px !important;
        border: 1px solid #ced4da !important; /* Standard Bootstrap border color for better visibility */
        padding: 10px 14px;
        font-size: 0.95rem;
        transition: all 0.2s;
        background-color: #ffffff;
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--invoice-primary);
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
        outline: none;
    }

    /* Select2 Custom Styling to match Bootstrap */
    .select2-container--default .select2-selection--single {
        border: 1px solid #ced4da !important;
        border-radius: 10px !important;
        height: 45px !important;
        padding: 8px 12px !important;
        display: flex !important;
        align-items: center !important;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: var(--invoice-primary) !important;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15) !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 43px !important;
        right: 10px !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #475569 !important;
        line-height: normal !important;
        padding-left: 0 !important;
    }

    /* Specific Section Hiding */
    .mainheading.d-none, .productdetail.d-none, #previewinvoice.d-none {
        display: none !important;
    }

    /* Active Tab Highlighting */
    .btn-group .btn.active {
        background-color: var(--invoice-primary) !important;
        color: white !important;
        border-color: var(--invoice-primary) !important;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
        font-weight: 700;
    }
</style>
 
<!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/> -->
 

<div class="container-main">
    <!-- Header Section -->
    <div class="mainheading">
        <div class="card mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title fw-bold mb-1"> 
                            <i class="fa fa-file-invoice me-2"></i>INVOICE GENERATION
                        </h3>
                        <p class="text-muted mb-0">Create and manage your professional invoices with ease</p>
                    </div>
                    <div class="text-md-end mt-3 mt-md-0">
                        <span class="badge bg-light text-primary border px-3 py-2 rounded-pill">
                            <i class="fa fa-calendar-alt me-2"></i>Date: <?php echo date('d-m-Y'); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Selection Controls -->
        <div class="card mb-4">
            <div class="card-body p-4">
                <div class="row align-items-end g-4">
                    <div class="col-md-auto">
                        <div class="btn-group shadow-sm rounded-10 overflow-hidden">
                            <a class="btn btn-white border" href="../admin_dashboard.php?k=<?php echo htmlspecialchars($key); ?>&grpid=<?php echo htmlspecialchars($groupid); ?>&mdid=<?php echo htmlspecialchars($mdid); ?>"> 
                                <i class="fa fa-arrow-left me-1"></i> Back
                            </a>
                            <button type="button" class="btn btn-white border" id="btnRefresh" onclick="window.location.reload();">  
                                <i class="fa fa-sync-alt me-1"></i> Refresh 
                            </button>
                        </div>
                    </div>
                    <div class="col-md">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="clientid" class="form-label">Client Name <span class="text-danger">*</span></label>
                                <select class="form-select form-control" id="clientid" name="clientid" required>
                                    <option value="" selected disabled>Select Client</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="orderno" class="form-label">Sale Order No <span class="text-danger">*</span></label>
                                <select class="form-select form-control" id="orderno" name="orderno" required>
                                    <option value="" selected disabled>Select Order</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 2: Product Details -->
    <div class="productdetail" id="productdetail">
        <div id="stepHeader" class="d-none animate__animated animate__fadeIn">
            <div class="step-indicator">
                <i class="fa fa-shopping-basket me-3"></i>
                <div>
                    <span class="d-block text-primary small">Step 02</span>
                    <strong>Review Selected Products and Price Details</strong>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body p-0">  
                <div class="table-responsive"> 
                    <table class="table table-hover mb-0" id="tblproductdetail">
                        <thead> 
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Promise Date</th>
                                <th>Product Name</th>
                                <th>Order Qty</th>
                                <th>Ready Qty</th>
                                <th>Sell Qty</th>
                                <th>Rate</th> 
                                <th>Taxable Amt</th> 
                                <th>Disc %</th>
                                <th>Disc Amt</th>
                                <th class="text-end">Payable Amount</th>  
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="11" class="text-center py-5 text-muted">
                                    <i class="fa fa-info-circle me-2"></i>Please select a client and sale order to begin
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="table-light d-none">
                            <tr class="fw-bold">
                                <td colspan="5" class="text-end text-uppercase small">Grand Total</td>
                                <td class="ft-sellqty text-center"></td>
                                <td></td>
                                <td class="ft-taxable text-center"></td>
                                <td></td>
                                <td class="ft-discount text-center text-danger"></td>
                                <td class="ft-payable text-end text-primary"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white border-top p-4 text-end" id="invactionbtndiv">
                <button type="button" class="btn btn-primary btn-action px-5 py-2" id="btnnext">
                    Continue <i class="fa fa-chevron-right ms-2"></i>
                </button>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-action d-none hidebtn" id="btnback">
                        <i class="fa fa-chevron-left me-2"></i> Back
                    </button>
                    <button type="button" class="btn btn-success btn-action d-none hidebtn" id="btncreateinve">
                        <i class="fa fa-file-invoice me-1"></i> Preview Invoice
                    </button>
                </div>
            </div>
        </div>
    </div> 

    <!-- Step 3: Preview Section -->
    <div class="preview-box d-none mt-4 animate__animated animate__fadeIn" id="previewinvoice">
        <div class="step-indicator border-info">
            <i class="fa fa-clipboard-check me-3 text-info"></i>
            <div>
                <span class="d-block text-info small">Step 03</span>
                <strong>Review Invoice Details & Finalize</strong>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Side: Contact Details -->
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4 text-primary border-bottom pb-3">
                            <i class="fa fa-address-card me-2"></i>Shipping & Contact Info
                        </h5>
                        
                        <div class="row g-3">
                            <div class="col-12 mt-2">
                                <label class="form-label" for="ctcperson">Contact Person</label>
                                <input type="text" class="form-control" id="ctcperson" placeholder="Enter full name">
                            </div>
                            
                            <div class="col-md-6 mt-2">
                                <label class="form-label" for="ctcno">Phone Number</label>
                                <input type="text" class="form-control" id="ctcno" placeholder="e.g. +91 9876543210">
                            </div>
                            
                            <div class="col-md-6 mt-2">
                                <label class="form-label" for="email_id">Email Address</label>
                                <input type="email" class="form-control" id="email_id" placeholder="name@example.com">
                            </div>
                            
                            <div class="col-md-12 mt-2">
                                <label class="form-label" for="inv_add">Invoicing Address</label>
                                <textarea class="form-control" id="inv_add" rows="5" placeholder="Building, Street, City, State, ZIP..." style="resize:none;"></textarea>
                            </div>
                        </div>
                        <div class="row mt-4">
                            <div class="col-md-12 mt-2">
                                <label class="form-label" for="editnv_drivername">Driver Name</label>
                                <input type="text" class="form-control" id="editnv_drivername" placeholder="Enter full name">
                            </div>
                            
                            <div class="col-md-12 mt-2">
                                <label class="form-label" for="editnv_drivercontactno">Drivier Contact Number</label>
                                <input type="text" class="form-control" id="editnv_drivercontactno" placeholder="e.g. +91 9876543210">
                            </div>
                            <div class="col-md-12 mt-2">
                                <label class="form-label" for="editinv_freightcharges">Freight Charges</label>
                                <span class="badge badge-info float-right" style="cursor: pointer;" onclick="openFreightCalculator('<?php echo $projectid; ?>', '<?php echo $rooturl; ?>', handleFreightSelection)"> <i class="fa fa-calculator me-1"></i> Estimate Freight</span>
                                <input type="text" class="form-control" id="editinv_freightcharges" placeholder="Enter Freight Charges">
                            </div>
                            <div class="col-md-12 mt-2">
                                <label class="form-label" for="editinv_vehicleno">Vehicle No</label>
                                <input type="text" class="form-control" id="editinv_vehicleno" placeholder="Enter full vehicle number">
                            </div>
                            <div class="col-md-12 mt-2 d-none">
                                <label class="form-label" for="editinv_distancekm">Distance(KM)</label>
                                <input type="text" class="form-control" id="editinv_distancekm" placeholder="Enter the distance">
                            </div>
                            <div class="col-md-12 mt-2">
                                <label class="form-label" for="editinv_deliveraddress">Delivery Address</label>
                                <input type="text" class="form-control" id="editinv_deliveraddress" placeholder="Enter Delivery Address">
                            </div>                            
                        </div>
                        <div class="row mt-4">
                            <div class="col-md-4">
                                <button type="button" class="btn btn-warning border btn-action m-2 backpreview"> <i class="fa fa-arrow-left me-2"></i> Back to Modify</button>
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn btn-info m-2" id="regenerate"><i class="fa fa-sync-alt me-2"></i> Update Details</button>
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn btn-primary btn-action m-2" id="generatefinal_inv"> <i class="fa fa-check-double me-2"></i> Finalize Invoice</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Live Preview -->
            <div class="col-lg-7">
                <div class="card h-100">
                    <div class="card-header bg-white p-4 border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0 text-primary">Live Document Preview</h5>
                        <div class="btn-group btn-group-sm rounded-pill overflow-hidden border shadow-sm">
                            <button type="button" class="btn btn-white active" id="showinvoice">Invoice</button>
                            <button type="button" class="btn btn-white" id="showdeliverynote">Delivery Note</button>
                        </div>
                    </div>
                    <div class="card-body p-4 bg-light">
                       <!--  <div id="displaypreview">
                            <div class="d-flex flex-column align-items-center justify-content-center h-100 py-5 text-muted opacity-50">
                                <i class="fa fa-file-pdf fa-4x mb-3"></i>
                                <p class="fw-bold">Preview will appear here</p>
                                <small>Generate a temporary invoice to see the draft</small>
                            </div>
                        </div> -->
                        <iframe
                            id="displaypreview"
                            src=""
                            style="width:100%; height:650px; border:1px solid #ddd; border-radius:6px;"
                            frameborder="0">
                        </iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Decision Modal -->
    <div class="modal fade" id="msgModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"> 
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header bg-warning text-dark border-0 p-4">
                    <h5 class="modal-title fw-bold">
                        <i class="fa fa-exclamation-triangle me-2"></i>Action Required
                    </h5>                     
                </div>
                <div class="modal-body text-center p-5">
                    <p id="textMessage" class="fs-5 mb-0 text-dark fw-bold"></p>
                </div>
                <div class="modal-footer justify-content-center border-0 pb-5 gap-2">
                    <button type="button" class="btn btn-success px-4 rounded-pill" id="btnyes">
                        <span class="btn-text">Proceed Anyway</span>
                        <span class="spinner-border spinner-border-sm d-none"></span>
                    </button>
                    <button type="button" class="btn btn-info px-4 rounded-pill text-white" id="btnno">Seek Permission</button>
                    <button type="button" class="btn btn-outline-secondary px-4 rounded-pill" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- <div class="modal fade" id="updatecontactpersonmsgModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"> 
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header bg-warning text-dark border-0 p-4">
                    <h5 class="modal-title fw-bold">
                        <i class="fa fa-exclamation-triangle me-2"></i>UPdate Contact Person of Order No <b id="upstordernomd"></b>
                    </h5>                     
                </div>
                <div class="modal-body text-center p-5">
                    
                </div>
                <div class="modal-footer justify-content-center border-0 pb-5 gap-2">
                    <button type="button" class="btn btn-info px-4 rounded-pill text-white" id="regenerate"><i class="fa fa-sync-alt me-2"></i> Update Contact Person Details</button>
                    <button type="button" class="btn btn-outline-secondary px-4 rounded-pill" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div> -->
</div>

<?php include('../footer.php'); ?>
<script>
    window.serverCurrentDate = "<?= date('d-m-Y'); ?>";
    window.displayquestionno = 1;
</script>
<script type="text/javascript" src="js/control.js"></script>
<script type="text/javascript" src="js/function.js"></script>
<script src="<?php echo $prefix; ?>krf-orderandsales/js/vehicle_calculator.js?v=<?php echo time(); ?>"></script>
<script>    
$(document).ready(function () {
     getcustomer();     
     // Initialize Select2 if needed
     if($.fn.select2) {
         $('.form-select').select2({
             width: '100%'
         });
     }
});
function handleFreightSelection(data) {
    // console.log("Freight Data Received:", data);
    console.log(data);
    // Target fields
    const $freightAmount = $("#editinv_freightcharges");
    const $vehicleNo = $("#editinv_vehicleno");
    const $distanceKm = $("#editinv_distancekm");



    // 1. Populate the rate and trigger calculation logic
    $freightAmount.val(data.charge || 0).trigger('input');

    // 2. Store internal metadata as data attributes
    $freightAmount.data('vehiclename', data.vehiclename || '');
    $freightAmount.data('vehicleno', data.vehicleno || '');
    $freightAmount.data('distance', data.calc_km || '');

    // 3. Populate visible/hidden details fields
    if ($("#vehiclename_hidden").length) $("#vehiclename_hidden").val(data.vehiclename || '').trigger('change');
    if ($vehicleNo.length) $vehicleNo.val(data.vehicleno || '').trigger('change');
    if ($distanceKm.length) $distanceKm.val(data.calc_km || '').trigger('change');
  }
</script>

