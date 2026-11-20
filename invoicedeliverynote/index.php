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

    .btn-premium-action {
        border-radius: 12px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 160px;
        position: relative;
        overflow: hidden;
    }

    .btn-premium-action:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 8px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
        filter: brightness(1.05);
    }

    .btn-premium-action:active:not(:disabled) {
        transform: translateY(0);
    }
    
    .btn-premium-action:disabled {
        opacity: 0.5;
        cursor: not-allowed;
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
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
        font-weight: 700;
    }

    /* Fixed Height for inputs to match Select2 exactly */
    .form-control-standard {
        height: 34px !important;
        padding-top: 1.0rem !important;
        padding-bottom: 1.0rem !important;
        /*padding: 1.0rem 0.7rem !important;*/
        display: flex !important;
        align-items: center !important;
    }
    
    .btn-add-inline {
        padding: 0 8px;
        height: 24px;
        line-height: 22px;
        font-size: 14px;
        margin-left: 10px;
        border-radius: 4px;
        vertical-align: middle;
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
                            <i class="fa fa-truck-loading me-2"></i>VEHICLE & DRIVER ALLOTMENT
                        </h3>
                        <p class="text-muted mb-0">Allot vehicle/driver details to generate Gate Pass, Delivery Note, and Final Invoice</p>
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
                            <div class="col-md-12">
                                <label for="clientid" class="form-label">Client Name <span class="text-danger">*</span></label>
                                <select class="form-select form-control" id="clientid" name="clientid" required>
                                    <option value="" selected disabled>Select Client</option>
                                </select>
                            </div>
                            <!-- Hidden info fields loaded on client change -->
                            <input type="hidden" id="ctcperson" />
                            <input type="hidden" id="ctcno" />
                            <input type="hidden" id="email_id" />
                            <input type="hidden" id="inv_add" />
                            <input type="hidden" id="hidden_projectid" value="<?php echo $projectid; ?>" />
                            <input type="hidden" id="hidden_companyid" value="<?php echo $companyid; ?>" />
                            <input type="hidden" id="hidden_subcompanyid" value="<?php echo $subcompanyid; ?>" />
                            <input type="hidden" id="hidden_companyname" value="<?php echo $companyname; ?>" />
                            <input type="hidden" id="hidden_loginname" value="<?php echo $loginname; ?>" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 2: Pending Orders Grid -->
    <div id="ordersContainer" class="d-none animate__animated animate__fadeIn">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-primary mb-0"><i class="fa fa-list me-2"></i>Pending Sale Orders</h5>
            <div class="d-flex align-items-center gap-2 flex-wrap py-2 px-3 bg-white rounded shadow-sm mb-4 border">
                <button type="button" class="btn btn-warning fw-bold border-0 shadow-sm d-flex align-items-center px-3 py-2 text-dark btn-premium-action" id="btn_vehicle_allotment" disabled>
                    <i class="fa fa-truck-loading me-2" style="font-size: 1.1rem;"></i>
                    <span>Vehicle Allocation</span>
                </button>
                
                <div class="vr mx-1 opacity-25" style="height: 28px;"></div>
                
                <button type="button" class="btn btn-primary fw-bold shadow-sm d-flex align-items-center  me-2 btn-premium-action" id="generate_gatepass" disabled>
                    <i class="fa fa-id-card me-1" style="font-size: 1.1rem;"></i>
                    <span>Generate Gate Pass</span>
                </button>
                
                <button type="button" class="btn btn-info fw-bold text-white shadow-sm d-flex align-items-center  me-2 btn-premium-action" id="generate_deliverynote" disabled>
                    <i class="fa fa-file-invoice me-1" style="font-size: 1.1rem;"></i>
                    <span>Generate Delivery Note</span>
                </button>
                
                <button type="button" class="btn btn-success fw-bold shadow-sm d-flex align-items-center  me-2 btn-premium-action" id="generatefinal_inv" disabled>
                    <i class="fa fa-file-signature me-1" style="font-size: 1.1rem;"></i>
                    <span>Generate Final Invoice</span>
                </button>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body p-0">  
                <div class="table-responsive"> 
                    <table class="table table-hover mb-0" id="tblpendingorders">
                        <thead> 
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Order No</th>
                                <th>Product Detail</th>
                                <th>Delivery Date</th>
                                <th class="text-center">Qty</th>
                                <th>Value</th>
                                <th class="text-center" style="width: 100px;">Disc %</th>
                                <th class="text-center" style="width: 120px;">Disc Amt</th>
                                <th class="text-end">Final Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Populated via loadPendingOrders() -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white border-top p-3 text-end d-flex justify-content-end gap-2">
                 <button type="button" class="btn btn-outline-secondary btn-action" id="btncreateinve">
                     <i class="fa fa-eye me-1"></i> Preview Draft
                 </button>
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
            <!-- Left Side: Actions/Controls -->
            <div class="col-lg-3">
                <div class="card h-100">
                    <div class="card-body p-4 text-center">
                        <h5 class="fw-bold mb-4 text-primary border-bottom pb-3">Actions</h5>
                        <p class="text-muted small">Your document has been generated in draft mode.</p>
                        
                        <button type="button" class="btn btn-warning border btn-action w-100 mb-3 backpreview"> 
                            <i class="fa fa-arrow-left me-2"></i> Back to Orders
                        </button>
                        
                        <button type="button" class="btn btn-info w-100 mb-3 text-white" id="regenerate">
                            <i class="fa fa-sync-alt me-2"></i> Refresh Preview
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Side: Live Preview -->
            <div class="col-lg-9">
                <div class="card h-100">
                    <div class="card-header bg-white p-4 border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0 text-primary">Live Document Preview</h5>
                        <div class="btn-group btn-group-sm rounded-pill overflow-hidden border shadow-sm">
                            <button type="button" class="btn btn-white active" id="showgatepass">Gate Pass</button>
                            <button type="button" class="btn btn-white" id="showdeliverynote">Delivery Note</button>
                            <button type="button" class="btn btn-white" id="showfreightinvoice">Freight Invoice</button>
                            <button type="button" class="btn btn-white" id="showinvoice">Final Invoice</button>
                        </div>
                    </div>
                    <div class="card-body p-4 bg-light">
                        <div id="displaypreview">
                            <div class="d-flex flex-column align-items-center justify-content-center h-100 py-5 text-muted opacity-50">
                                <i class="fa fa-file-pdf fa-4x mb-3"></i>
                                <p class="fw-bold">Preview will appear here</p>
                                <small>Generate a temporary invoice to see the draft</small>
                            </div>
                        </div>
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

    <!-- Vehicle & Driver Allotment Modal -->
    <div class="modal fade shadow-lg" id="vehicleAllotmentModal" aria-hidden="true" data-bs-backdrop="static" style="backdrop-filter: blur(4px);">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                <div class="modal-header bg-primary text-white border-0 p-4" style="border-radius: 16px 16px 0 0;">
                    <h5 class="modal-title fw-bold">
                        <i class="fa fa-truck me-2"></i>Vehicle Allocation Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <div class="row g-3">
                        <div class="col-md-12 mb-2">
                             <div class="alert alert-info py-2 small shadow-sm border-0 d-flex justify-content-between align-items-center" style="border-radius: 10px;">
                                 <div>
                                     <i class="fa fa-info-circle me-2"></i><strong>Instructions:</strong> Select a Delivery Date and Vehicle to check capacity.
                                 </div>
                                 <div class="bg-white px-3 py-1 rounded-pill border shadow-sm">
                                     <span class="text-muted small me-2 uppercase fw-bold">Total Selected:</span>
                                     <span id="modal_total_selected_qty" class="fw-bold text-primary fs-5">0</span>
                                 </div>
                             </div>
                        </div>

                        <div class="col-md-6 mt-1">
                            <label class="form-label fw-bold" for="alloc_delivery_date">Delivery Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="alloc_delivery_date" value="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div class="col-md-6 mt-1">
                            <label class="form-label fw-bold" for="alloc_vehicle_id">Select Vehicle <span class="text-danger">*</span></label>
                            <select class="form-select shadow-sm" id="alloc_vehicle_id">
                                <option value="">-- Select Vehicle --</option>
                            </select>
                        </div>

                        <div class="col-md-12 mt-3">
                            <div id="capacity_status_box" class="p-3 bg-white border rounded shadow-sm d-none">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small">Plants Capacity:</span>
                                    <span id="plants_cap_text" class="fw-bold text-primary">0 / 0</span>
                                </div>
                                <div class="progress mb-3" style="height: 10px; border-radius: 5px;">
                                    <div id="plants_cap_progress" class="progress-bar bg-primary" role="progressbar" style="width: 0%"></div>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted small">New Allocation Qty:</span>
                                    <span id="alloc_qty_text" class="fw-bold">0</span>
                                </div>
                                <div id="capacity_warning" class="text-danger small mt-2 d-none">
                                    <i class="fa fa-exclamation-triangle me-1"></i> Not enough capacity!
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mt-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-bold mb-0" for="alloc_driver_id">Driver Name <span class="text-danger">*</span></label>
                                <button class="btn btn-sm btn-outline-primary btn-add-inline" type="button" id="btn_add_driver_master" title="Add New Driver">
                                    <i class="fa fa-plus"></i> New
                                </button>
                            </div>
                            <select class="form-select shadow-sm" id="alloc_driver_id">
                                <option value="">-- Select Driver --</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mt-3">
                            <label class="form-label fw-bold" for="alloc_driver_contact">Contact Number</label>
                            <input type="text" class="form-control bg-white shadow-sm form-control-standard" id="alloc_driver_contact" readonly placeholder="Auto-populated">
                        </div>

                        <div class="col-md-12 mt-3 d-none" id="freight_details_container">
                            <div class="alert alert-warning border-0 shadow-sm p-3 mb-0" style="border-radius: 10px;">
                                <h6 class="fw-bold mb-2 text-dark" style="font-size: 0.9rem;">
                                    <i class="fa fa-info-circle me-1 text-warning"></i> Pre-decided Freight Details
                                </h6>
                                <div class="table-responsive bg-white rounded shadow-sm">
                                    <table class="table table-sm table-hover mb-0" style="font-size: 0.8rem;">
                                        <thead class="bg-light text-dark fw-bold">
                                            <tr>
                                                <th class="ps-2">Order No</th>
                                                <th class="text-end">Freight Amt</th>
                                                <th class="text-center">Disc (%)</th>
                                                <th class="text-end">Payable</th>
                                                <th class="text-center" style="width: 60px;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="freight_details_body" class="text-muted">
                                            <!-- Loaded dynamically -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 mt-3">
                            <label class="form-label fw-bold" for="editinv_freightcharges">Freight Charges</label>
                            <span class="badge bg-info float-end" style="cursor: pointer;" onclick="openFreightCalculator('<?php echo $projectid; ?>', '<?php echo $rooturl; ?>', handleFreightSelection)"> 
                                <i class="fa fa-calculator me-1"></i> Estimate
                            </span>
                            <span id="download_freight_pdf_btn" class="badge bg-success float-end me-2 d-none" style="cursor: pointer;">
                                <i class="fa fa-download me-1"></i> Download Invoice
                            </span>
                            <input type="text" class="form-control shadow-sm form-control-standard" id="editinv_freightcharges" placeholder="Enter amount">
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-end border-0 p-4 bg-light" style="border-radius: 0 0 16px 16px;">
                    <button type="button" class="btn btn-outline-secondary px-4 fw-bold" data-dismiss="modal" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success px-4 fw-bold" id="btn_save_vehicle_modal">
                        <i class="fa fa-save me-2"></i>Save Allotment 
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- PDF Preview Modal -->
    <div class="modal fade" id="pdfPreviewModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
                <div class="modal-header bg-dark text-white p-3 border-0" style="border-radius: 12px 12px 0 0;">
                    <h5 class="modal-title fw-bold" id="pdfPreviewTitle">Document Preview</h5>
                    <div class="ms-auto d-flex gap-2">
                        <a id="btnDownloadPDF" href="#" target="_blank" class="btn btn-sm btn-success px-3 rounded-pill fw-bold" download>
                            <i class="fa fa-download me-1"></i> Download
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-light rounded-circle" data-bs-dismiss="modal" data-dismiss="modal" style="width: 32px; height: 32px; padding: 0; line-height: 1;">&times;</button>
                    </div>
                </div>
                <div class="modal-body p-0 bg-secondary bg-opacity-10" style="min-height: 80vh;">
                    <div id="modalPreviewContent">
                        <!-- Preview loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
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
     
     // Calculate total quantity selected
    let totalSelectedQty = 0;
    $("#tblpendingorders tbody tr").each(function() {
        if ($(this).find(".chkorder").is(":checked")) {
            // Updated column index to 5 based on table structure (#, OrderNo, Product, Date, Qty)
            totalSelectedQty += Number($(this).find("td:nth-child(5)").text()) || 0; 
        }
    });
    $("#alloc_qty_text").text(totalSelectedQty).data("totalqty", totalSelectedQty);
    $("#modal_total_selected_qty").text(totalSelectedQty);
});
function handleFreightSelection(data) {
    // console.log("Freight Data Received:", data);
    console.log(data);
    // Target fields
    const $freightAmount = getprovisional_freightcharges(clientid, selectedOrders);
    $("#vehicleAllotmentModal").modal("show");
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

