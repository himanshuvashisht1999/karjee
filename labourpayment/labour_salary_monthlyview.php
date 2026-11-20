<?php
if (isset($_GET['vw']) == 1) {
  $key = $_GET['k'];
  $rooturl = "";
} else {
  require('header.php');
}
$mdid = "";
if (isset($_GET['mdid'])) {
  $mdid = $_GET['mdid'];
}
$dddr = loginmodulesdetails_frommaster($authconn, $companyid, $subcompanyid, $projectid, "moduleid", $mdid);
$groupid = "";
if (count($dddr) > 0) {
  $groupid = $dddr[0]['groupid'];
}
?>
<style>
  body {
    background: linear-gradient(135deg, #f6f8fc, #e8f0fe);
    font-family: 'Poppins', sans-serif;
  }

  h4.title {
    text-align: center;
    font-weight: 600;
    color: #333;
    margin-bottom: 1rem;
  }

  /* Camera Section */
  #camera {
    width: 250px;
    max-height: 150px;
    border-radius: 15px;
    object-fit: cover;
    border: 3px solid #e0e0e0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }

  /* Button Styles */
  .btn {
    border-radius: 25px;
    font-weight: 500;
  }

  .btn-success {
    background: linear-gradient(90deg, #28a745, #34d058);
    border: none;
  }

  .btn-danger {
    background: linear-gradient(90deg, #dc3545, #ff5c75);
    border: none;
  }

  /* Summary Cards */
  .summary-card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    background: #fff;
    transition: transform 0.2s ease;
  }

  .summary-card:hover {
    transform: scale(1.03);
  }

  .summary-card .icon {
    font-size: 2rem;
    color: #0d6efd;
    margin-bottom: 5px;
  }

  .summary-card h4 {
    margin: 0;
    font-weight: 600;
  }

  /* Attendance Table styled as Modern Cards/Strips */
  #calculatedLaboursalDatatbl {
    border-collapse: separate !important;
    border-spacing: 0 8px !important;
    border: none !important;
    background: transparent !important;
  }

  #calculatedLaboursalDatatbl thead th {
    background-color: #f8fafc !important;
    color: #475569 !important;
    border: none !important;
    padding: 14px 16px !important;
    font-weight: 700 !important;
    font-size: 0.8rem !important;
    text-transform: uppercase !important;
    letter-spacing: 0.75px !important;
    text-align: center !important;
  }

  #calculatedLaboursalDatatbl tbody tr {
    background-color: #ffffff !important;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.02) !important;
    border: 1px solid rgba(226, 232, 240, 0.8) !important;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
  }

  #calculatedLaboursalDatatbl tbody tr:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05) !important;
    border-color: #cbd5e1 !important;
    background-color: #fafbfd !important;
  }

  #calculatedLaboursalDatatbl tbody td {
    padding: 14px 16px !important;
    border: none !important;
    vertical-align: middle !important;
    text-align: center !important;
    font-size: 0.875rem !important;
  }

  /* First and last td rounded corners */
  #calculatedLaboursalDatatbl tbody tr td:first-child,
  #calculatedLaboursalDatatbl thead tr th:first-child {
    border-top-left-radius: 12px !important;
    border-bottom-left-radius: 12px !important;
  }
  #calculatedLaboursalDatatbl tbody tr td:last-child,
  #calculatedLaboursalDatatbl thead tr th:last-child {
    border-top-right-radius: 12px !important;
    border-bottom-right-radius: 12px !important;
  }

  /* Custom table container wrapper background */
  .table-responsive {
    background: transparent !important;
    padding: 4px !important;
  }

  .card {
    border-radius: 15px;
    border: none;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
  }

  /* --- Modern Dashboard Aesthetics --- */
  .filter-section {
    background: #ffffff;
    border-radius: 12px;
    padding: 1.5rem;
    border: 1px solid #e2e8f0;
    margin-bottom: 1.5rem;
  }
  .filter-label {
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #475569;
    margin-bottom: 0.5rem;
    letter-spacing: 0.5px;
  }
  .filter-select {
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    height: 42px;
    font-size: 0.9rem;
    font-weight: 500;
    color: #1e293b;
    transition: all 0.2s;
  }
  .filter-select:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
  }

  /* KPI Summary Styling */
  .kpi-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    border: 1px solid #edf2f7;
    transition: all 0.3s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  }
  .kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
  }
  .kpi-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .bg-soft-primary { background-color: rgba(59, 130, 246, 0.1); }
  .bg-soft-success { background-color: rgba(16, 185, 129, 0.1); }
  .kpi-value {
    font-size: 1.4rem;
    font-weight: 700;
  }

  /* Segmented Control for Radio Options */
  .status-update-control {
    background: #f8fafc;
    border-radius: 12px;
    padding: 1.25rem;
    border: 1px solid #e2e8f0;
    height: 100%;
  }
  .segment-group {
    display: inline-flex;
    background: #e2e8f0;
    padding: 4px;
    border-radius: 8px;
    width: 100%;
  }
  .segment-btn {
    flex: 1;
    text-align: center;
    padding: 6px 12px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #475569;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
    user-select: none;
  }
  .segment-btn.active {
    background: #ffffff;
    color: #0f172a;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }

  /* Action Tabs Bar */
  .action-tabs-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.5rem;
    background: #ffffff;
    padding: 1rem;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
  }
  .tab-btn-group {
    display: inline-flex;
    background: #f1f5f9;
    padding: 4px;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
  }
  .viewdatabtn {
    border-radius: 6px !important;
    padding: 8px 16px !important;
    font-size: 0.85rem !important;
    font-weight: 600 !important;
    border: none !important;
    cursor: pointer;
    transition: all 0.2s;
  }
  .viewdatabtn.btn-primary {
    background-color: #f26f21 !important;
    border-color: #f26f21 !important;
    color: #ffffff !important;
    box-shadow: 0 2px 4px rgba(242, 111, 33, 0.2);
  }
  .viewdatabtn.btn-outline-primary {
    background-color: transparent !important;
    border-color: #f26f21 !important;
    color: #f26f21 !important;
    border: 1px solid #f26f21 !important;
  }
  .viewdatabtn.btn-outline-primary:hover {
    background-color: rgba(242, 111, 33, 0.08) !important;
    color: #f26f21 !important;
  }
  .export-btn-group {
    display: flex;
    gap: 0.5rem;
  }
  .export-btn {
    border-radius: 8px !important;
    font-weight: 600 !important;
    padding: 8px 16px !important;
    height: 38px;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0 !important;
  }
  .bg-soft-primary {
    background-color: rgba(13, 110, 253, 0.08) !important;
  }
  .bg-soft-info {
    background-color: rgba(13, 202, 240, 0.08) !important;
  }
  .bg-soft-success {
    background-color: rgba(25, 135, 84, 0.08) !important;
  }
  .bg-soft-warning {
    background-color: rgba(255, 193, 7, 0.08) !important;
  }
  .bg-soft-danger {
    background-color: rgba(220, 53, 69, 0.08) !important;
  }
</style>
<div class="container mt-2">
  <div class="row mb-2">
    <div class="col-12">
      
    </div>
  </div>

  <!-- Card 1: Calculate Attendance -->
  <div class="card" id="card1">
    <div class="card-header">
      <strong>Labour Disbursement Payments</strong><br />
      <span></span>
    </div>
    <div class="card-body">
      <div class="filter-section mt-2">
        <div class="row g-3 align-items-end">
          <div class="col-md-3">
            <label for="rpmonthlist" class="filter-label">Choose Wages Month</label>
            <select class="form-select filter-select" id="rpmonthlist"></select>
          </div>
          <div class="col-md-3">
            <label for="rpdaterangelist" class="filter-label">Wages Date Range</label>
            <select class="form-select filter-select" id="rpdaterangelist"></select>
          </div>
          <div class="col-md-3">
            <label for="wageslocationlist" class="filter-label">Wages Location</label>
            <select class="form-select filter-select" id="wageslocationlist">
              <option value="" selected disabled>Choose Location</option>
            </select>
          </div>
          <div class="col-md-3">
            <button class="btn btn-primary w-100 filter-select d-flex align-items-center justify-content-center gap-2" id="btnRefreshData" style="font-weight:600; background: linear-gradient(45deg, #0d6efd, #0b5ed7); border: none; color: white;">
              <i class="fa fa-sync-alt"></i> Refresh Data
            </button>
          </div>
        </div>
      </div>
      <div class="text-center" id="loadingdiv"></div>
      <div id="viewlabourpaymentdiv" style="display:none;">
        <!-- Title & Period Header -->
        <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2 mt-3">
          <div>
            <h5 class="fw-bold mb-0 text-dark">Labour Wages Payment Report</h5>
            <small class="text-muted">Period: <span class="salarypriodlbl fw-semibold text-primary"></span></small>
          </div>
        </div>

        <!-- Dashboard Widgets Grid -->
        <div class="row g-3 mb-4">
          <!-- KPIs Column -->
          <div class="col-lg-8">
            <div class="row g-3">
              <div class="col-md-4">
                <div class="kpi-card shadow-sm h-100">
                  <div class="kpi-icon bg-soft-primary text-primary">
                    <i class="fa fa-calculator fs-4"></i>
                  </div>
                  <div class="kpi-content">
                    <span class="text-muted small fw-bold text-uppercase d-block mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Total Amount</span>
                    <h4 class="mb-0 fw-bold text-dark">₹<span class="ttllabourwageslbl">0.00</span></h4>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="kpi-card shadow-sm h-100">
                  <div class="kpi-icon bg-soft-success text-success">
                    <i class="fa fa-check-circle fs-4"></i>
                  </div>
                  <div class="kpi-content">
                    <span class="text-muted small fw-bold text-uppercase d-block mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Total Paid</span>
                    <h4 class="mb-0 fw-bold text-success">₹<span class="ttlpaidlabourwageslbl">0.00</span></h4>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="kpi-card shadow-sm h-100">
                  <div class="kpi-icon" style="background-color: rgba(239, 68, 68, 0.1); color: #ef4444;">
                    <i class="fa fa-exclamation-circle fs-4"></i>
                  </div>
                  <div class="kpi-content">
                    <span class="text-muted small fw-bold text-uppercase d-block mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Total Un-Paid</span>
                    <h4 class="mb-0 fw-bold text-danger">₹<span class="ttlunpaidlabourwageslbl">0.00</span></h4>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Selection radio Column -->
          <div class="col-lg-4">
            <div class="status-update-control shadow-sm d-flex flex-column justify-content-between">
              <div>
                <label class="fw-bold text-dark mb-2" style="font-size: 13px;">Update Paid Status Method</label>
                <div class="segment-group">
                  <div class="segment-btn active" data-value="individual">Individual</div>
                  <div class="segment-btn" data-value="multiple">Multiple</div>
                </div>
                <!-- Hidden real radios to preserve compatibility -->
                <div class="d-none">
                  <input type="radio" name="selectionradio" id="radioIndividual" value="individual" checked />
                  <input type="radio" name="selectionradio" id="radioMultiple" value="multiple" />
                </div>
              </div>
              <div class="mt-2">
                <span id="noteformultiplespan" class="text-danger small" style="display: none; font-size:11px;">
                  Select checkboxes below to mark payments as paid in bulk.
                </span>
                <button class="btn btn-sm btn-primary w-100 mt-2 d-none" id="nexttoupdatetoall" style="border-radius:8px; font-weight:600;"><i class="fa fa-arrow-right"></i> Next to Proceed</button>
              </div>
            </div>
          </div>
        </div>

        <div class="action-tabs-bar" id="dataviewdiv" style="display:none;">
          <div class="tab-btn-group">
            <button class="btn btn-primary viewdatabtn" id="pendingpayment"><i class="fa fa-clock"></i> Pending Payment</button>
            <button class="btn btn-outline-primary viewdatabtn" id="paidpayment"><i class="fa fa-check-double"></i> Paid Payment</button>
          </div>
          <div class="export-btn-group">
            <button class="btn btn-primary export-btn" style="background-color:#f26f21; border-color:#f26f21; color:white;" id="btnExportExcelCustom"><i class="fa fa-file-excel"></i> Export Excel</button>
            <button class="btn btn-danger export-btn" style="background-color:#dc3545; border-color:#dc3545; color:white;" data-exporttype="unpaid" id="exportlbpaymentpdf"><i class="fa fa-file-pdf"></i> Export PDF</button>
            <div class="d-none">
              <table id="exporttable_hidden">
                <thead>
                  <tr>
                    <th>S.No</th>
                    <th>Labour ID</th>
                    <th>Labour Name</th>
                    <th>Bank Name</th>
                    <th>Account Number</th>
                    <th>IFSC Code</th>
                    <th>Bank Verification Status</th>
                    <th>DateFrom</th>
                    <th>DateTo</th>
                    <th>Net Payable</th>
                    <th>Paid</th>
                    <th>Balance</th>
                    <th>Update At</th>
                    <th>Update By</th>
                    <th>Update Remark</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-12 mt-2">
            <label>Search to Filter</label>
            <input type="search" class="form-control" border=1 placeholder="Search...." id="searchrptPaytbl"
              oninput="filterTable('#searchrptPaytbl', '#calculatedLaboursalDatatbl');">
          </div>
          <div class="col-md-12 mt-2">
            <div class="table-responsive">
              <table class="table table-mobile table-bordered" border="1" id="calculatedLaboursalDatatbl">
                <thead>
                  <tr>
                    <th data-label="Select All" style="width:50px;" class="multiple  noexport d-none">Select All<br /><input
                        type="checkbox" id="mnsltlabourslry"></th>
                    <th data-label="Action" style="width:50px;" class="updatestatustd individual noexport">Action</th>
                    <th data-label="Labour Info" style="width:180px;">Labour Info</th>
                    <th data-label="Duration" style="width:150px;">Duration of Payment</th>
                    <th data-label="Payment Summary" style="width:180px;">Payment Details</th>
                    <th data-label="Transfer Mode" class="paidtd d-none" style="width:170px;">Transfer Mode</th>
                    <th data-label="Updated By & At" class="paidtd d-none" style="width:140px;">Updated By & At</th>
                    <th data-label="Update Remark" class="paidtd d-none" style="width:200px;">Update Remark</th>
                    <th data-label="Paid Ref. File" class="downloadtd noexport d-none">Paid Ref. File</th>
                    <th data-label="Action" class="paidactiontd paidtd d-none noexport" style="width:90px;">Action</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="UpdatePaymentStatusModal" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4" style="overflow: hidden;">
            <div class="modal-header bg-primary text-white" style="background: linear-gradient(45deg, #0d6efd, #0a58ca);">
                <h5 class="modal-title fw-bold" id="UpdatePaymentStatusHeader"><i class="fa fa-hand-holding-usd me-2"></i>Confirm Payment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="row g-0">
                    <!-- Left: Camera Section -->
                    <div class="col-md-5 bg-dark position-relative d-flex align-items-center justify-content-center" style="min-height: 350px; background-color: #000;">
                        <video id="camera" autoplay muted playsinline style="width: 100%; height: 100%; object-fit: cover;"></video>
                        <canvas id="canvas" class="d-none"></canvas>
                        
                        <!-- Overlay Info -->
                        <div class="position-absolute bottom-0 start-0 w-100 p-3 text-white" style="background: linear-gradient(to top, rgba(0,0,0,0.9), transparent);">
                            <div class="mb-1">
                                <div class="d-flex align-items-start">
                                    <i class="fa fa-map-marker-alt text-warning mt-1 me-2"></i>
                                    <span id="cameraLocationText" style="font-size: 12px; line-height: 1.3; font-weight: 500;">
                                        Fetching location...
                                    </span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="fa fa-clock text-info me-2"></i>
                                <span id="cameraTimeText" style="font-size: 12px; font-weight: 500; letter-spacing: 0.5px;">--:--:--</span>
                            </div>
                        </div>
                        
                        <button id="cameraToggleBtn" class="btn btn-light rounded-circle shadow position-absolute top-0 end-0 m-3 d-none" style="width:40px;height:40px; z-index: 10;">
                            <i class="fa fa-sync text-primary"></i>
                        </button>
                    </div>

                    <!-- Right: Form Section -->
                    <div class="col-md-7 p-4 bg-white">
                        <!-- Employee Info Card -->
                        <div class="card border-0 shadow-sm mb-4 bg-light">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="rounded-circle bg-white text-primary d-flex align-items-center justify-content-center shadow-sm me-3" style="width:40px;height:40px;">
                                        <i class="fa fa-user fw-bold"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <small class="text-muted fw-bold" style="font-size:10px; letter-spacing:0.5px;">PAYING TO</small>
                                        <h6 class="mb-0 fw-bold text-dark text-truncate" id="employeName_pstatus">Loading...</h6>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between border-top pt-2 mt-2">
                                    <div>
                                        <small class="text-muted fw-bold" style="font-size:10px;">PERIOD</small>
                                        <div class="text-dark small fw-medium" id="AttDuration_pstatus">--</div>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted fw-bold" style="font-size:10px;">NET PAYABLE</small>
                                        <div class="text-success fw-bold h5 mb-0" id="NetSalary_pstatus">₹0</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Inputs -->
                        <div class="row g-3">
                            <input type="hidden" id="actualAmount">
                            
                            <div class="col-6">
                                <label class="form-label small fw-bold text-secondary">Paying Amount <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 fw-bold">₹</span>
                                    <input type="number" class="form-control fw-bold text-dark border-start-0 ps-1" id="paidAmount" placeholder="0.00">
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold text-secondary">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-sm" id="PaymentDate" value="<?php echo date('Y-m-d'); ?>">
                            </div>

                            <!-- Transfer Mode -->
                            <div class="col-12">
                                <label class="form-label small fw-bold text-secondary mb-1">Transfer Mode <span class="text-danger">*</span></label>
                                <div class="d-flex gap-4 p-2 rounded-2 border bg-light">
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="radio" name="payment_transfer_mode" id="transferModeCash" value="Cash" checked>
                                        <label class="form-check-label small fw-bold text-dark cursor-pointer" for="transferModeCash">
                                            <i class="fa fa-money-bill-wave text-success me-1"></i> Cash
                                        </label>
                                    </div>
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="radio" name="payment_transfer_mode" id="transferModeBank" value="Bank Transfer">
                                        <label class="form-check-label small fw-bold text-dark cursor-pointer" for="transferModeBank">
                                            <i class="fa fa-university text-primary me-1"></i> Bank Transfer
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Bank Verification Info (Only for Bank Transfer) -->
                            <div class="col-12 d-none" id="bank_verification_container"></div>

                            <!-- UTR No (Mandatory for Bank Transfer) -->
                            <div class="col-12 d-none" id="utr_no_container">
                                <label class="form-label small fw-bold text-secondary">UTR No / Transaction No <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="payment_utr_no" placeholder="Enter UTR / Transaction No">
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold text-secondary">Remarks / Note</label>
                                <textarea id="remark_pstatus" class="form-control form-control-sm" rows="3" placeholder="Enter remarks (optional)..." style="resize: none;"></textarea>
                            </div>
                        </div>

                        <!-- Confirm Button -->
                        <div class="d-grid mt-4">
                            <button class="btn btn-primary py-2 fw-bold shadow-sm" id="updateFinalPaymentStatusbtn" onclick="UpdateFinalPaymentStatus('Paid', $('#actualAmount').val(), $('#paidAmount').val(), $('#PaymentDate').val(), $('#remark_pstatus').val(), 'updateFinalPaymentStatusbtn');">
                                <i class="fa fa-paper-plane me-2"></i> SUBMIT & PAY
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="UpdateAllPaidModal" role="dialog">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="mb-2" id="UpdateAllPaidModalHeader">Update Labour Payment Status</h5>
        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
          <i class="ti ti-x"></i>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-12" id="refdocumentdiv">
            <label><b>Upload Reference File</b></label><br />
            <label><input type="file" class="form-control" name="referenceDocument" id="referenceDocument" />
          </div>

          <div class="col-md-6 mt mt-4">
            <label><b>Your total cash withdrawal amount from the bank.</b></label><br />
            <input type="text" class="form-control" name="totalCashwithdrawalamount" id="totalCashwithdrawalamount"
              readonly />
          </div>
          <div class="col-md-6 mt mt-4">
            <label><b>Your total cash balance amount.</b></label><br />
            <input type="text" class="form-control" name="totalbalanceCashamount" id="totalbalanceCashamount"
              readonly />
          </div>
        </div>
        <div class="row mt-4">
          <div class="col-md-12">
            <label><b>Remark</b> (optional)</label>
            <textarea id="allPaymentDateremark" name="allPaymentDateremark" class="form-control" rows="2"></textarea>
          </div>
        </div>

        <div class="row mt-4">
          <div class="col-md-12">
            <label><input type="checkbox" name="confirmupdatestatus" id="confirmupdatestatus" /> Are you sure your
              balance amount is correct?</label>
          </div>
        </div>

        <div class="row mt-4">
          <div class="col-md-12 text-center">
            <button class="btn btn-primary" id="multipleupdateFinalPaymentStatusbtn"
              onclick="AllUpdateFinalPaymentStatus('referenceDocument', 'allPaymentDateremark','confirmupdatestatus', 'calculatedLaboursalDatatbl', 'multipleupdateFinalPaymentStatusbtn');">UPDATE
              PAYMENT STATUS</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- Change Transfer Mode Modal -->
<div class="modal fade" id="ChangeTransferModeModal" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4" style="overflow: hidden;">
            <div class="modal-header bg-primary text-white" style="background: linear-gradient(45deg, #0d6efd, #0a58ca);">
                <h5 class="modal-title fw-bold" id="ChangeTransferModeHeader"><i class="fa fa-exchange-alt me-2"></i>Change Transfer Mode</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-white">
                <!-- Employee Info Card -->
                <div class="card border-0 shadow-sm mb-3 bg-light">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded-circle bg-white text-primary d-flex align-items-center justify-content-center shadow-sm me-3" style="width:40px;height:40px;">
                                <i class="fa fa-user fw-bold"></i>
                            </div>
                            <div class="flex-grow-1">
                                <small class="text-muted fw-bold" style="font-size:10px; letter-spacing:0.5px;">LABOUR DETAILS</small>
                                <h6 class="mb-0 fw-bold text-dark text-truncate" id="changeMode_employeName">--</h6>
                                <small class="text-muted fw-semibold" id="changeMode_employeeId">--</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center justify-content-between border-top pt-2 mt-2">
                            <div>
                                <small class="text-muted fw-bold" style="font-size:10px;">PERIOD</small>
                                <div class="text-dark small fw-medium" id="changeMode_duration">--</div>
                            </div>
                            <div class="text-end">
                                <small class="text-muted fw-bold" style="font-size:10px;">PAID AMOUNT</small>
                                <div class="text-success fw-bold h5 mb-0" id="changeMode_paidAmount">₹0.00</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hidden inputs -->
                <input type="hidden" id="changeMode_salaryId">
                <input type="hidden" id="changeMode_labourId">

                <!-- Transfer Mode Selection -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary mb-1">Select New Transfer Mode <span class="text-danger">*</span></label>
                    <div class="d-flex gap-4 p-2.5 rounded-2 border bg-light">
                        <div class="form-check mb-0">
                            <input class="form-check-input cursor-pointer" type="radio" name="change_transfer_mode_radio" id="changeModeRadioCash" value="Cash" checked>
                            <label class="form-check-label small fw-bold text-dark cursor-pointer" for="changeModeRadioCash">
                                <i class="fa fa-money-bill-wave text-success me-1"></i> Cash
                            </label>
                        </div>
                        <div class="form-check mb-0">
                            <input class="form-check-input cursor-pointer" type="radio" name="change_transfer_mode_radio" id="changeModeRadioBank" value="Bank Transfer">
                            <label class="form-check-label small fw-bold text-dark cursor-pointer" for="changeModeRadioBank">
                                <i class="fa fa-university text-primary me-1"></i> Bank Transfer
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Bank Verification Info Card (Visible for Bank Transfer) -->
                <div class="mb-3 d-none" id="changeMode_bank_container"></div>

                <!-- UTR No (Mandatory for Bank Transfer) -->
                <div class="mb-3 d-none" id="changeMode_utr_container">
                    <label class="form-label small fw-bold text-secondary">UTR No / Transaction No <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="changeMode_utr_no" placeholder="Enter UTR / Transaction No">
                </div>

                <!-- Remark -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">Reason / Remark</label>
                    <textarea id="changeMode_remark" class="form-control form-control-sm" rows="2" placeholder="Optional remark for mode change..." style="resize: none;"></textarea>
                </div>

                <!-- Action Button -->
                <div class="d-grid mt-4">
                    <button class="btn btn-primary py-2 fw-bold shadow-sm" id="btnSubmitChangeTransferMode">
                        <i class="fa fa-save me-2"></i> UPDATE TRANSFER MODE
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Hidden inputs -->
<input type="hidden" id="Source">
<input type="hidden" id="Latitude">
<input type="hidden" id="Longitude">
<input type="hidden" id="Accuracy">
<input type="hidden" id="Address">
<!-- Loader -->
<div id="loadingOverlay"
  style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:#fff;padding:20px;border-radius:8px;">
    <span id="loadingText">Please wait...</span>
  </div>
</div>
<?php
if (isset($_GET['vw']) == 1) {

} else {
  require('footer.php');
}
?>
<script>
  // Initialize by showing the first card
  let companyid = $("body").data("companyid");
  let subcompanyid = $("body").data("subcompanyid");
  let projectid = $("body").data("projectid");
  LabourSalaryMonthYearList("afterapproval", function (res) {
    // console.log(res);
    let $list = $("#rpmonthlist");
    $list.empty(); // clear previous options
    if (res.length > 0) {
      $list.append('<option value="" selected disabled>Choose Wages Month</option>');
      res.sort(function (a, b) {
        if (a.SalaryYear === b.SalaryYear) {
          return b.SalaryMonth - a.SalaryMonth;
        }
        return b.SalaryYear - a.SalaryYear;
      });
      for (let i = 0; i < res.length; i++) {
        $list.append(
          '<option value="' + res[i].SalaryYear + '-' + res[i].SalaryMonth + '">' +
          res[i].MonthName + ' - ' + res[i].SalaryYear +
          '</option>'
        );
      }
      if (res.length === 1) {
        $list.prop("selectedIndex", 1).trigger("change");
      }
    } else {
      $list.append('<option value="" disabled>No data available</option>');
    }
  });

  $(document).on("change", "#rpmonthlist", function () {
    LabourSalaryDateRangeList($("#rpmonthlist").val(), function (res) {
      // console.log(res);
      let $list = $("#rpdaterangelist");
      $list.empty(); // clear previous options
      if (res.length > 0) {
        $list.append('<option value="" selected disabled>Choose Wages Date Range</option>');
        // sort by DateFrom descending (latest first)
        res.sort(function (a, b) {
          return new Date(b.DateFrom) - new Date(a.DateFrom);
        });

        for (let i = 0; i < res.length; i++) {
          $list.append(
            '<option value="' + res[i].DateFrom + '|' + res[i].DateTo + '">' +
            res[i].DateFrom + ' - ' + res[i].DateTo +
            '</option>'
          );
        }
        // auto-select if only one option exists
        if (res.length === 1) {
          $list.prop("selectedIndex", 1).trigger("change");
        }
      } else {
        $list.append('<option value="" disabled>No data available for approval</option>');
      }
    });
  });

  $(document).on("change", "#rpdaterangelist", function () {
    var periodext = $("#rpdaterangelist").val();
    getLabourSalaryLocationList("withindates", $("#rpdaterangelist").val(), function (res) {
      // console.log(res);
      var lctlist = "<option value='' selected disabled>Choose Location</option>";
      $("#wageslocationlist").empty();
      if (res.length > 0) {
        for (var i = 0; i < res.length; i++) {
          let locName = res[i].LocationName.toLowerCase().trim();
          let locId = res[i].LocationId.toString().toLowerCase().trim();
          if (locName === "all" || locId === "all" || locName === "all location") {
            continue;
          }
          lctlist += "<option value='" + res[i].LocationId + "'>" + res[i].LocationName + "</option>";
        }
      }
      $("#wageslocationlist").append(lctlist);
      
      // Do NOT trigger change automatically, wait for user selection
      $("#viewlabourpaymentdiv").hide();
      $("#calculatedLaboursalDatatbl tbody").html('');
      $("#dataviewdiv").hide();
    });
  });

  $(document).on("change", "#wageslocationlist", function () {
    let locationid = $("#wageslocationlist").val();
    if (!locationid) {
      $("#viewlabourpaymentdiv").hide();
      $("#calculatedLaboursalDatatbl tbody").html('');
      $("#dataviewdiv").hide();
      return;
    }
    $("#loadingdiv").html('');
    var selectyearmonth = $("#rpdaterangelist").val();
    var yearmonthtext = $("#rpdaterangelist option:selected").text();
    let loadingMsg = $("<div id='loadingMsg' class='alert alert-info text-center p-3' style='margin-top:16px;'>Loading labour wages data...</div>");
    $(".projectnamelbl").html($("body").data("projectname"));
    $(".salarypriodlbl").html(yearmonthtext);
    $("#loadingdiv").append(loadingMsg);
    $("#calculatedLaboursalDatatbl tbody").html('');
    $("#viewlabourpaymentdiv").show();
    if (selectyearmonth) {
      CalculatedSalaryData_Labour('rpmonthlist', 'rpdaterangelist', locationid, function (res) {
        // console.log(res);
        var sslhtml = "";
        if (res.length > 0) {
          var trdnone = "";
          let unpaidcound = 0;
          let ttlamount = 0;
          let ttlpaidamount = 0;
          let ttlunpaidpaidamount = 0;
          let paidStatus = "";
          for (var i = 0; i < res.length; i++) {

            if (res[i].ReferenceNo) {
              const netSalary = parseFloat(res[i].NetSalary) || 0;
              const paidAmount = parseFloat(res[i].PaidAmount) || 0;
              const isPartial = (res[i].PaymentStatus == 'Paid' || res[i].PaymentStatus == 'paid') && (netSalary - paidAmount > 0.01) && (paidAmount > 0.01);
              const isFullyPaid = (res[i].PaymentStatus == 'Paid' || res[i].PaymentStatus == 'paid') && !isPartial;
              const isUnpaid = !isFullyPaid && !isPartial;
              const pendingAmount = netSalary - paidAmount;

              let trdnone = "";
              let bgStyle = "";
              let dataIsPartial = "false";

              ttlamount += netSalary;

              if (isFullyPaid) {
                trdnone = "d-none";
                ttlpaidamount += netSalary;
                
                let refFileHtml = "";
                if (res[i].PaymentReferenceFilepath && res[i].PaymentReferenceFilepath.trim() !== "") {
                  refFileHtml = `
                                  <span class="viewpaidreceipt text-success" style="cursor:pointer;"
                                      data-paidreceipt="${res[i].PaymentReferenceFilepath}" title="View Paid Receipt">
                                      <i class="fa fa-file fa-lg"></i>
                                  </span>`;
                } else {
                  refFileHtml = `
                                  <span class="text-muted" title="Paid (No Ref. File)">
                                      <i class="fa fa-check-circle fa-lg"></i>
                                  </span>`;
                }
                paidStatus = refFileHtml;

              } else if (isPartial) {
                trdnone = ""; // visible in default "Pending Payment" tab on load
                bgStyle = "background-color: #fef2f2 !important;";
                dataIsPartial = "true";
                unpaidcound++;

                ttlpaidamount += paidAmount;
                ttlunpaidpaidamount += pendingAmount;

                let refFileHtml = "";
                if (res[i].PaymentReferenceFilepath && res[i].PaymentReferenceFilepath.trim() !== "") {
                  refFileHtml = `
                                  <span class="viewpaidreceipt text-success" style="cursor:pointer;"
                                      data-paidreceipt="${res[i].PaymentReferenceFilepath}" title="View Paid Receipt">
                                      <i class="fa fa-file fa-lg"></i>
                                  </span>`;
                } else {
                  refFileHtml = `
                                  <span class="text-muted" title="Paid (No Ref. File)">
                                      <i class="fa fa-check-circle fa-lg"></i>
                                  </span>`;
                }
                paidStatus = `
                  <div>${refFileHtml}</div>
                  <div class="text-danger fw-bold mt-1" style="font-size: 0.75em;">Partially Paid</div>
                `;

              } else {
                trdnone = ""; // visible in default "Pending Payment" tab on load
                unpaidcound++;
                ttlunpaidpaidamount += netSalary;
                paidStatus = "Un-Paid";
              }

              let perDay = parseFloat(res[i].PayPerDay) || 0;
              let presentDays = res[i].CalculatedPresentDays || 0;
              let labourInfoHtml = `
                <div class="text-start">
                  <div class="text-dark" style="font-size: 14px; font-weight: 700;">${res[i].EmployeeID}</div>
                  <div class="text-secondary mb-1" style="font-size: 13px; font-weight: 500;">${res[i].EmployeeName}</div>
                  <div class="d-flex flex-wrap gap-1 align-items-center mt-1">
                    <span class="badge bg-soft-primary text-primary" style="font-size: 0.75em; padding: 3px 6px; font-weight: 600;">₹${perDay.toFixed(2)}/Day</span>
                    <span class="badge bg-soft-info text-info" style="font-size: 0.75em; padding: 3px 6px; font-weight: 600;">${presentDays} Days</span>
                  </div>
                </div>
              `;

              let durationHtml = `
                <div class="text-center small text-secondary" style="font-size: 13px;">
                  <div><span class="fw-semibold text-dark">From:</span> ${res[i].DateFrom}</div>
                  <div><span class="fw-semibold text-dark">To:</span> ${res[i].DateTo}</div>
                </div>
              `;
              let remainLabel = "Remain";
              let remainColorClass = "text-danger";
              let remainValue = pendingAmount;

              if (pendingAmount < -0.01) {
                remainLabel = "Extra";
                remainColorClass = "text-success";
                remainValue = Math.abs(pendingAmount);
              } else if (Math.abs(pendingAmount) <= 0.01) {
                remainLabel = "Balance";
                remainColorClass = "text-muted";
                remainValue = 0;
              }

              let paymentDetailsHtml = `
                <div class="text-start small" style="line-height: 1.5; font-size: 13px;">
                  <div><span class="text-muted fw-medium">Net Payable:</span> <span class="fw-bold text-dark">₹${netSalary.toFixed(2)}</span></div>
                  <div><span class="text-muted fw-medium">Paid:</span> <span class="fw-bold text-success">₹${paidAmount.toFixed(2)}</span></div>
                  <div><span class="text-muted fw-medium">${remainLabel}:</span> <span class="fw-bold ${remainColorClass}">₹${remainValue.toFixed(2)}</span></div>
                </div>
              `;

              let updatedBy = res[i].updated_by || "";
              let updatedAt = res[i].updated_at || "";
              let updatedByAndAtHtml = "";
              if (updatedBy) {
                updatedByAndAtHtml = `
                  <div class="text-center small" style="font-size: 13px;">
                    <div class="fw-bold text-dark">${updatedBy}</div>
                    <div class="text-muted" style="font-size: 0.85em;">${updatedAt}</div>
                  </div>
                `;
              } else {
                updatedByAndAtHtml = `<span class="text-muted">-</span>`;
              }

              let updateRemark = res[i].PaymentRemark || "";
              let updateRemarkHtml = "";
              if (updateRemark) {
                updateRemarkHtml = `<span class="text-secondary small d-inline-block text-truncate" style="max-width: 180px;" title="${updateRemark}">${updateRemark}</span>`;
              } else {
                updateRemarkHtml = `<span class="text-muted">-</span>`;
              }

              let transferMode = res[i].PaymentMethod || "Cash";
              let transactionNo = res[i].transactionno || "";
              let transferModeBadge = "";
              if (transferMode.toLowerCase() === "bank transfer") {
                  transferModeBadge = `
                    <span class="badge" style="background-color: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; font-weight: 600; font-size: 11.5px; padding: 4px 8px;">
                      <i class="fa fa-university me-1"></i> Bank Transfer
                    </span>
                    ${transactionNo ? `<div class="small text-muted mt-1 fw-semibold" style="font-size: 11px;">UTR: <span class="text-dark">${transactionNo}</span></div>` : ''}
                  `;
              } else {
                  transferModeBadge = `
                    <span class="badge" style="background-color: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; font-weight: 600; font-size: 11.5px; padding: 4px 8px;">
                      <i class="fa fa-money-bill-wave text-success me-1"></i> Cash
                    </span>
                  `;
              }

              let transferModeHtml = `
                <div class="d-flex align-items-center justify-content-center gap-1">
                  <div>${transferModeBadge}</div>
                  <button class="btn btn-sm btn-outline-secondary py-0 px-1 ms-1 btn-edit-transfermode" onclick="openChangeTransferModeModal(this)" title="Edit Transfer Mode" style="border-radius: 6px; font-size: 11px; height: 24px; line-height: 22px;">
                    <i class="fa fa-pen"></i>
                  </button>
                </div>
              `;

              let undoActionHtml = `
                <button class="btn btn-sm btn-outline-danger py-1 px-2 btn-undo-payment" onclick="undoLabourPayment(this)" title="Revert Payment to Unpaid" style="border-radius: 6px; font-size: 12px; font-weight: 600;">
                  <i class="fa fa-undo me-1"></i> Undo
                </button>
              `;

              sslhtml += `<tr class="${trdnone}" style="${bgStyle}" 
                        data-employeeid="${res[i].EmployeeID}"  
                        data-employeename="${res[i].EmployeeName}" 
                        data-salarymonth="${res[i].SalaryMonth}" 
                        data-salaryyear="${res[i].SalaryYear}" 
                        data-datefrom="${res[i].DateFrom}" 
                        data-dateto="${res[i].DateTo}" 
                        data-salaryid="${res[i].SalaryID}" 
                        data-netsalary="${res[i].NetSalary}" 
                        data-paidamount="${paidAmount}" 
                        data-pendingamount="${pendingAmount}" 
                        data-is-partial="${dataIsPartial}" 
                        data-paymentstatus="${res[i].PaymentStatus}" 
                        data-paymentdate="${res[i].PaymentDate}"
                        data-bankname="${res[i].bankname || res[i].BankName || ''}"
                        data-accountnumber="${res[i].accountnumber || res[i].AccountNumber || ''}"
                        data-ifsccode="${res[i].ifsccode || res[i].IFSCCode || ''}"
                        data-verifystatus="${res[i].verifystatus || res[i].VerifyStatus || ''}"
                        data-updatedat="${res[i].updated_at || ''}"
                        data-updatedby="${res[i].updated_by || ''}"
                        data-paymentremark="${res[i].PaymentRemark || ''}"
                        data-paymentmethod="${res[i].PaymentMethod || 'Cash'}"
                        data-transactionno="${res[i].transactionno || ''}">
                <td data-label="Select All" class="multiple  noexport d-none"><input type="checkbox" class="payperdaycheckbox" /></td>
                <td data-label="Action" class="updatestatustd individual noexport" style="cursor:pointer">
                  <button class="btn btn-sm btn-primary updatePaymentStatus" onclick="openModalFinalStatus(this)"><i class="fa fa-clock"></i> Pay</button>
                </td>
                <td data-label="Employee Name">${labourInfoHtml}</td>
                <td data-label="Duration">${durationHtml}</td>
                <td data-label="Net Payable">${paymentDetailsHtml}</td>
                <td data-label="Transfer Mode" class="paidtd d-none">${transferModeHtml}</td>
                <td data-label="Updated By & At" class="paidtd d-none">${updatedByAndAtHtml}</td>
                <td data-label="Update Remark" class="paidtd d-none">${updateRemarkHtml}</td>
                <td data-label="Pay Ref. File" class="d-none  noexport downloadtd">${paidStatus}</td>
                <td data-label="Action" class="paidactiontd paidtd d-none noexport">${undoActionHtml}</td>
              </tr>`;
            }
          }
          if (sslhtml) {
            $(".ttllabourwageslbl").html(ttlamount);
            $(".ttlpaidlabourwageslbl").html(ttlpaidamount);
            $(".ttlunpaidlabourwageslbl").html(ttlunpaidpaidamount);
            $("#calculatedLaboursalDatatbl tbody").html(sslhtml);
            $(".exportsheet").removeClass("d-none");
            $("#loadingMsg").remove();
            $("#dataviewdiv").show();
            $("#multipleselectiontr").hide();
            $("#nexttoupdatetoall").hide();
            if (unpaidcound > 0) {
              $("#nexttoupdatetoall").show();
              $("#multipleselectiontr").show();
            }
            // $("#pendingpayment").trigger("click");
          } else {
            $("#loadingMsg").html("No data available for the month of " + yearmonthtext);
          }
        } else {
          $("#loadingMsg").html("No data available for the month of " + yearmonthtext);
        }
      });
    }
  });

  $(document).on("click", ".viewpaidreceipt", function () {
    var routurl = $("body").data("routurl");
    let paidreceiptpath = $(this).data("paidreceipt") || "0";
    openPopup(routurl + "preview.php?f=" + paidreceiptpath, "Paid Receipt");
  });

  $(document).on("click", "#btnRefreshData", function () {
    let locationid = $("#wageslocationlist").val();
    if (locationid) {
      $("#wageslocationlist").trigger("change");
    } else {
      alertmessage("warning", "Please select a location first to refresh data.");
    }
  });

  $(document).on("click", "#btnExportExcelCustom", function () {
    // Clear hidden table body and head
    $("#exporttable_hidden tbody").empty();
    $("#exporttable_hidden thead").empty();

    let firmName = $("body").data("projectname") || "KRF - Project";
    let periodText = $(".salarypriodlbl").text().trim() || "All";
    let titleText = "Labour Wages Payment Report";

    let headerHtml = `
      <tr>
        <th colspan="15" style="font-weight: bold; text-align: left;">Firm Name: ${firmName}</th>
      </tr>
      <tr>
        <th colspan="15" style="font-weight: bold; text-align: left;">Period: ${periodText}</th>
      </tr>
      <tr>
        <th colspan="15" style="font-weight: bold; text-align: left;">Title: ${titleText}</th>
      </tr>
      <tr>
        <th>S.No</th>
        <th>Labour ID</th>
        <th>Labour Name</th>
        <th>Bank Name</th>
        <th>Account Number</th>
        <th>IFSC Code</th>
        <th>Bank Verification Status</th>
        <th>DateFrom</th>
        <th>DateTo</th>
        <th>Net Payable</th>
        <th>Paid</th>
        <th>Balance</th>
        <th>Transfer Mode</th>
        <th>UTR / Transaction No</th>
        <th>Update At</th>
        <th>Update By</th>
        <th>Update Remark</th>
      </tr>
    `;
    $("#exporttable_hidden thead").append(headerHtml);
    
    let sno = 1;
    let hasRows = false;
    
    // Select all visible rows in the main table
    $("#calculatedLaboursalDatatbl tbody tr:not(.d-none)").each(function () {
      let row = $(this);
      let labourId = row.data("employeeid") || "";
      let labourName = row.data("employeename") || "";
      let bankName = row.data("bankname") || "";
      let accountNumber = row.data("accountnumber") || "";
      let ifscCode = row.data("ifsccode") || "";
      let rawVerifyStatus = (row.data("verifystatus") || "").toString().trim().toLowerCase();
      let bankVerificationStatus = (rawVerifyStatus === "verified") ? "Verified" : "Un-Verify";
      let dateFrom = row.data("datefrom") || "";
      let dateTo = row.data("dateto") || "";
      let netPayable = parseFloat(row.data("netsalary")) || 0;
      let paid = parseFloat(row.data("paidamount")) || 0;
      let balance = parseFloat(row.data("pendingamount")) || 0;
      let transferMode = row.data("paymentmethod") || "";
      let utrNo = row.data("transactionno") || "";
      let updateAt = row.data("updatedat") || "";
      let updateBy = row.data("updatedby") || "";
      let remark = row.data("paymentremark") || "";

      let excelRow = `
        <tr>
          <td>${sno}</td>
          <td>${labourId}</td>
          <td>${labourName}</td>
          <td>${bankName}</td>
          <td style="mso-number-format:'\\@';">${accountNumber}</td>
          <td>${ifscCode}</td>
          <td>${bankVerificationStatus}</td>
          <td>${dateFrom}</td>
          <td>${dateTo}</td>
          <td>${netPayable.toFixed(2)}</td>
          <td>${paid.toFixed(2)}</td>
          <td>${balance.toFixed(2)}</td>
          <td>${transferMode}</td>
          <td style="mso-number-format:'\\@';">${utrNo}</td>
          <td>${updateAt}</td>
          <td>${updateBy}</td>
          <td>${remark}</td>
        </tr>
      `;
      $("#exporttable_hidden tbody").append(excelRow);
      sno++;
      hasRows = true;
    });

    if (!hasRows) {
      alertmessage("warning", "No data available to export.");
      return;
    }

    // Call the library function with the hidden table
    gridtoexcel_withfilter_chunk('exporttable_hidden', 'Labour Payment Report', 'landscape', function(res){});
  });

  $(document).on("click", "#nexttoupdatetoall", function () {
    $("#totalCashwithdrawalamount").val(0);
    $("#totalbalanceCashamount").val(0);

    let totalcashwithdraw = 0;
    let totalpaidamount = 0;
    let totalbalance = 0;

    let paytochecklength = $("input.payperdaycheckbox:checked").length;
    if (paytochecklength > 0) {
      $("#calculatedLaboursalDatatbl tbody tr").each(function () {
        let netsalary = Number($(this).data("netsalary")) || 0;
        let paidamount = Number($(this).data("paidamount")) || 0;
        let pendingamount = Number($(this).data("pendingamount")) || netsalary;
        let isPartial = $(this).data("is-partial") === true || $(this).data("is-partial") === "true";
        let status = ($(this).data("paymentstatus") || "").toLowerCase();

        let rowCheckbox = $(this).find("input.payperdaycheckbox");
        if (rowCheckbox.is(":checked")) {
          totalpaidamount += netsalary;
          totalcashwithdraw += pendingamount;
        } else {
          if (status === "paid" && !isPartial) {
            totalpaidamount += netsalary;
            totalcashwithdraw += netsalary;
          } else if (status === "paid" && isPartial) {
            totalpaidamount += paidamount;
            totalcashwithdraw += paidamount;
            totalbalance += pendingamount;
          } else {
            totalbalance += netsalary;
          }
        }
      });

      // Set values in fields
      $("#totalCashwithdrawalamount").val(totalcashwithdraw);
      $("#totalbalanceCashamount").val(totalbalance);

      // Always show modal
      $("#UpdateAllPaidModal").modal("show");

      // Optional: Show message if everything is already paid
      if (totalbalance === 0) {
        $("#UpdateAllPaidModal .modal-body").prepend(
          '<div class="alert alert-success mb-2">✅ All labour payments are already marked as Paid.</div>'
        );
      }
    } else {
      alertmessage("warning", "Please select at least one labour.");
      return false;
    }
  });


  $(document).on("click", "input[name='selectionradio']", function () {
    let selecteraioval = $(this).val();
    
    // Sync segmented control buttons visually
    $(".segment-btn").removeClass("active");
    $(`.segment-btn[data-value="${selecteraioval}"]`).addClass("active");

    $("input[type='checkbox']").prop("checked", false);
    if (selecteraioval == "individual") {
      $(".multiple").addClass("d-none");
      $(".individual").removeClass("d-none");
      $("#updatemultipleentry").addClass("d-none");
      $("#nexttoupdatetoall").addClass("d-none");
      $("#noteformultiplespan").hide();
    } else {
      $("#noteformultiplespan").show();
      $("#nexttoupdatetoall").removeClass("d-none");
      $(".multiple").removeClass("d-none");
      $(".individual").addClass("d-none");
      $("#updatemultipleentry").removeClass("d-none");
    }
  });
  $(document).ready(function () {
    handleMasterCheckboxAll("mnsltlabourslry", "payperdaycheckbox");

    // Segmented Control handler for Payment Status Method
    $(document).on("click", ".segment-btn", function() {
      $(".segment-btn").removeClass("active");
      $(this).addClass("active");
      let val = $(this).data("value");
      if (val === "individual") {
        $("#radioIndividual").prop("checked", true).click();
      } else {
        $("#radioMultiple").prop("checked", true).click();
      }
    });
  });

  $(document).on("click", "#updatemultiplepaymentstatusbtn", function () {
    var today = new Date();
    var dd = String(today.getDate()).padStart(2, '0');
    var mm = String(today.getMonth() + 1).padStart(2, '0'); // January is 0
    var yyyy = today.getFullYear();
    var formattedDate = yyyy + '-' + mm + '-' + dd; // format: YYYY-MM-DD

    var loginname = $("body").data("loginname");
    let updcount = 0;
    let totalToUpdate = 0;
    let completed = 0;
    $("#calculatedLaboursalDatatbl tbody tr").each(function () {
      if ($(this).find("input.payperdaycheckbox").is(":checked")) {
        if ($(this).data("employeeid") && $(this).data("salaryid")) {
          totalToUpdate++;
          var remark = $(this).data("employeename") +
            " Payment status update by " + loginname +
            " on date " + formattedDate;

          UpdateFinalPaymentStatusMultiple(
            $(this).data("employeeid"),
            $(this).data("salaryid"),
            "Paid",
            formattedDate,
            remark,
            function (res) {
              completed++;
              if (res.status === "success") {
                updcount++;
              }

              // ✅ When all AJAX calls are done
              if (completed === totalToUpdate) {
                if (updcount > 0) {
                  Swal.fire({
                    title: "Success!",
                    text: updcount + " Labour Wages Updated Successfully",
                    icon: "success",
                    showCancelButton: false,
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    timer: 1000
                  }).then(() => {
                    window.location.reload();
                  });
                }
              }
            }
          );
        }
      }
    });
  });


  $(document).on("click", ".viewdatabtn", function () {
    // reset both buttons first
    $("#paidpayment, #pendingpayment").removeClass("btn-primary").addClass("btn-outline-primary");

    $("input[name='selectionradio'][value='individual']").prop("checked", true).trigger("click");
    if ($(this).prop("id") == "paidpayment") {
      $("#exportlbpaymentpdf").data("exporttype", "paid");
      $(".updatestatustd").addClass("d-none");
      $(".downloadtd").removeClass("d-none");
      $(".paidtd").removeClass("d-none");
      $("#multipleselectiontr").addClass("d-none");
      // highlight clicked button
      $("#paidpayment")
        .removeClass("btn-outline-primary")
        .addClass("btn-primary");

      // show rows with paid status
      $("#calculatedLaboursalDatatbl tbody tr").each(function () {
        $(this).addClass("d-none");
        $(this).addClass("noexport");
        let status = $(this).data("paymentstatus");
        if (status && status == "Paid") {
          $(this).removeClass("d-none");
          $(this).removeClass("noexport");
        }
      });
    } else {
      $("#exportlbpaymentpdf").data("exporttype", "unpaid");
      $(".updatestatustd").removeClass("d-none");
      $(".downloadtd").addClass("d-none");
      $(".paidtd").addClass("d-none");
      $("#multipleselectiontr").removeClass("d-none");
      $("#pendingpayment")
        .removeClass("btn-outline-primary")
        .addClass("btn-primary");

      // show rows with pending status or partial status
      $("#calculatedLaboursalDatatbl tbody tr").each(function () {
        $(this).addClass("d-none");
        $(this).addClass("noexport");
        let status = $(this).data("paymentstatus");
        let isPartial = $(this).data("is-partial") === true || $(this).data("is-partial") === "true";
        if (status != "Paid" || isPartial) {
          $(this).removeClass("d-none");
          $(this).removeClass("noexport");
        }
      });
    }
  });

  function openModalFinalStatus(btn) {
    // Find table and row
    const row = $(btn).closest("tr");
    const table = row.closest("table");
    // Get table ID
    const tableId = table.attr("id");
    // Get row index (0-based)
    const tableRowIndex = row.index();
    const employeeid = row.data("employeeid");
    const employeename = row.data("employeename");
    const salarymonth = row.data("salarymonth");
    const salaryyear = row.data("salaryyear");
    const datefrom = row.data("datefrom");
    const dateto = row.data("dateto");
    const salaryid = row.data("salaryid");
    const netsalary = parseFloat(row.data("netsalary")) || 0;
    const paidamount = parseFloat(row.data("paidamount")) || 0;
    const pendingamount = parseFloat(row.data("pendingamount")) || netsalary;
    const isPartial = row.data("is-partial") === true || row.data("is-partial") === "true";

    // Fill modal fields
    $("#employeName_pstatus").html(employeename);
    $("#AttDuration_pstatus").html(`${datefrom} To ${dateto}`);
    
    if (isPartial) {
      $("#NetSalary_pstatus").html(`${pendingamount.toFixed(2)} <span class="text-muted" style="font-size:0.75em; font-weight:normal;">(Paid: ₹${paidamount.toFixed(2)})</span>`);
      $("#actualAmount").val(pendingamount);
      $("#paidAmount").val(pendingamount);
    } else {
      $("#NetSalary_pstatus").html(netsalary.toFixed(2));
      $("#actualAmount").val(netsalary);
      $("#paidAmount").val(netsalary);
    }
    // console.log(tableId, salaryid, employeeid);
    // Attach data to Update Button
    $("#updateFinalPaymentStatusbtn")
      .data("employeeid", employeeid)
      .data("salaryid", salaryid)
      .data("rowindex", tableRowIndex)
      .data("tableid", tableId);
    // Set modal title
    $("#UpdatePaymentStatusHeader").html("Update Payment Status of " + employeename);

    const bankname = row.data("bankname") || "";
    const accountnumber = row.data("accountnumber") || "";
    const ifsccode = row.data("ifsccode") || "";
    const rawVerifyStatus = (row.data("verifystatus") || "").toString().trim().toLowerCase();
    const isVerified = (rawVerifyStatus === "verified");

    // Prepare Bank Verification UI
    let bankCardHtml = "";
    if (isVerified) {
      bankCardHtml = `
        <div class="p-2.5 rounded-2 border" style="background-color: #f0fdf4; border-color: #bbf7d0 !important;">
          <div class="d-flex align-items-center justify-content-between mb-1 pb-1 border-bottom" style="border-color: #dcfce7 !important;">
            <div class="d-flex align-items-center gap-2">
              <i class="fa fa-university text-success"></i>
              <span class="fw-bold text-dark small">${bankname || 'Bank Account'}</span>
            </div>
            <span class="badge bg-success text-white fw-bold px-2 py-1" style="font-size: 11px;">
              <i class="fa fa-check-circle me-1"></i> Verified
            </span>
          </div>
          <div class="d-flex justify-content-between text-muted" style="font-size: 11.5px;">
            <div><span class="fw-medium">A/C:</span> <span class="text-dark fw-semibold">${accountnumber || '-'}</span></div>
            <div><span class="fw-medium">IFSC:</span> <span class="text-dark fw-semibold">${ifsccode || '-'}</span></div>
          </div>
        </div>
      `;
    } else {
      bankCardHtml = `
        <div class="p-2.5 rounded-2 border" style="background-color: #fef2f2; border-color: #fecaca !important;">
          <div class="d-flex align-items-center justify-content-between mb-1 pb-1 border-bottom" style="border-color: #fee2e2 !important;">
            <div class="d-flex align-items-center gap-2">
              <i class="fa fa-university text-danger"></i>
              <span class="fw-bold text-dark small">${bankname || 'Bank Details'}</span>
            </div>
            <span class="badge bg-danger text-white fw-bold px-2 py-1" style="font-size: 11px;">
              <i class="fa fa-times-circle me-1"></i> Un-Verify
            </span>
          </div>
          <div class="d-flex justify-content-between text-muted" style="font-size: 11.5px;">
            <div><span class="fw-medium">A/C:</span> <span class="text-dark fw-semibold">${accountnumber || 'N/A'}</span></div>
            <div><span class="fw-medium">IFSC:</span> <span class="text-dark fw-semibold">${ifsccode || 'N/A'}</span></div>
          </div>
        </div>
      `;
    }
    $("#bank_verification_container").html(bankCardHtml).addClass("d-none");

    // Reset Transfer Mode & UTR field
    $('input[name="payment_transfer_mode"][value="Cash"]').prop("checked", true);
    $("#utr_no_container").addClass("d-none");
    $("#payment_utr_no").val("");
    $("#remark_pstatus").val("");

    // Show modal
    $("#UpdatePaymentStatusModal").modal("show");
    detectmobile();
    openCamera();

    // Update Location & Time
    const addr = $("#Address").val();
    if(addr) {
        $("#cameraLocationText").text(addr);
    } else {
         $("#cameraLocationText").text("Fetching location...");
         getLocationMandatory().then(() => {
             $("#cameraLocationText").text($("#Address").val());
         }).catch(() =>{
             $("#cameraLocationText").text("Location not available");
         });
    }

    if(window.cameraClockInterval) clearInterval(window.cameraClockInterval);
    const updateTime = () => {
        const now = new Date();
        $("#cameraTimeText").text(now.toLocaleString());
    }
    updateTime();
    window.cameraClockInterval = setInterval(updateTime, 1000);
  }

  $("#exportlbpaymentpdf").on("click", function () {
    const completed = $("body").data("companyid");
    const subcompanyid = $("body").data("subcompanyid");
    const projectid = $("body").data("projectid");
    const datefromdateex = $("#rpdaterangelist").val();
    const exporttype = $(this).data("exporttype");
    var dateFrom = "";
    var dateTo = "";
    if (datefromdateex) {
      var datessp = datefromdateex.split("|");
      dateFrom = datessp[0];
      dateTo = datessp[1];
    }
    if (dateFrom && dateTo && exporttype == "paid") {
      window.open("labour_PaymentPaidSheetPDF.php?companyid=" + companyid + "&subcompanyid=" + subcompanyid + "&projectid=" + projectid + "&datefrom=" + dateFrom + "&dateto=" + dateTo + "&exporttype=" + exporttype);
    } else if (dateFrom && dateTo && exporttype == "unpaid") {
      window.open("labour_PaymentPendingSheetPDF.php?companyid=" + companyid + "&subcompanyid=" + subcompanyid + "&projectid=" + projectid + "&datefrom=" + dateFrom + "&dateto=" + dateTo + "&exporttype=" + exporttype);
    } else if (dateFrom && dateTo) {
      window.open("labour_PaymentSheetPDF.php?companyid=" + companyid + "&subcompanyid=" + subcompanyid + "&projectid=" + projectid + "&datefrom=" + dateFrom + "&dateto=" + dateTo + "&exporttype=" + exporttype);
    } else {
      alertmessage('warning', 'Invalid Date From and Date To');
    }
  });



  /* =============================
     CONFIG
  ============================= */
  const LOCATION_ACCURACY_LIMIT = 250000;
  const FORCE_BACK_CAMERA = true; // true = lock back camera

  let cameraStream = null;
  let currentFacingMode = "environment"; // default back
  let cameraCount = 0;

  /* =============================
     LOADER
  ============================= */
  function showLoading(show, text = "Please wait...") {
    $("#loadingText").text(text);
    $("#loadingOverlay").toggle(show);
  }

  /* =============================
     DEVICE DETECTION
  ============================= */
  function detectmobile() {
    const ua = navigator.userAgent.toLowerCase();
    $("#Source").val(/android|iphone|ipad|ipod/.test(ua) ? "Mobile" : "Desktop");
  }

  function isMobileDevice() {
    return /android|iphone|ipad|ipod/i.test(navigator.userAgent);
  }

  /* =============================
     ADDRESS (SERVER SIDE)
  ============================= */
  async function getAddressFromLatLng(lat, lng) {
    try {
      const res = await fetch(`reverse_geocode.php?lat=${lat}&lng=${lng}`);
      const data = await res.json();
      return data.status === "success" ? data.address : "";
    } catch {
      return "";
    }
  }

  /* =============================
     LOCATION (MANDATORY)
  ============================= */
  function getLocationMandatory() {
    return new Promise((resolve, reject) => {
      navigator.geolocation.getCurrentPosition(
        async pos => {
          const { latitude, longitude, accuracy } = pos.coords;

          if (accuracy > LOCATION_ACCURACY_LIMIT) {
            return reject(`Accuracy ${Math.round(accuracy)}m too low`);
          }

          $("#Latitude").val(latitude);
          $("#Longitude").val(longitude);
          $("#Accuracy").val(accuracy);

          const address = await getAddressFromLatLng(latitude, longitude);
          $("#Address").val(address);
          resolve(true);
        },
        () => reject("Location permission denied"),
        { enableHighAccuracy: true, timeout: 15000 }
      );
    });
  }

  /* =============================
     CAMERA OPEN (USER CLICK ONLY)
  ============================= */
  async function openCamera() {
    const video = document.getElementById("camera");

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      alertmessage("warning", "Camera not supported.");
      return;
    }

    try {
      stopCamera();

      let stream;

      // 🔥 Try requested facing mode
      try {
        stream = await navigator.mediaDevices.getUserMedia({
          video: { facingMode: currentFacingMode },
          audio: false
        });
      } catch (e) {
        console.warn("FacingMode failed, fallback used", e);

        // 🔁 Fallback to any camera
        stream = await navigator.mediaDevices.getUserMedia({
          video: true,
          audio: false
        });
      }

      cameraStream = stream;
      video.srcObject = cameraStream;
      video.style.display = "block";
      await video.play();

      // 🔍 Detect camera count AFTER permission
      await detectCameraCountAfterPermission();

    } catch (err) {
      console.error("Camera open failed:", err);
      alertmessage("warning", "Unable to open camera.");
      video.srcObject = null;
      video.style.display = "none";
    }
  }

  /* =============================
     DETECT CAMERA COUNT (POST PERMISSION)
  ============================= */
  async function detectCameraCountAfterPermission() {
    const devices = await navigator.mediaDevices.enumerateDevices();
    cameraCount = devices.filter(d => d.kind === "videoinput").length;

    toggleSwitchButton();
  }

  /* =============================
     STOP CAMERA
  ============================= */
  function stopCamera() {
    if (cameraStream) {
      cameraStream.getTracks().forEach(track => track.stop());
      cameraStream = null;
    }
  }

  /* =============================
     SWITCH CAMERA
  ============================= */
  async function switchCamera() {
    if (FORCE_BACK_CAMERA) return;
    if (cameraCount < 2) return;

    currentFacingMode =
      currentFacingMode === "environment" ? "user" : "environment";

    await openCamera();
  }

  /* =============================
     SHOW / HIDE SWITCH BUTTON
  ============================= */
  function toggleSwitchButton() {
    const show =
      isMobileDevice() &&
      cameraCount > 1 &&
      !FORCE_BACK_CAMERA;

    $("#switchCameraBtn").toggle(show);
  }

  /* =============================
     PHOTO + WATERMARK
  ============================= */
  async function capturePhotoWithWatermark() {
    const video = document.getElementById("camera");
    const canvas = document.getElementById("canvas");
    const ctx = canvas.getContext("2d");

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    ctx.drawImage(video, 0, 0);

    ctx.fillStyle = "rgba(0,0,0,0.6)";
    ctx.fillRect(0, canvas.height - 110, canvas.width, 110);

    ctx.fillStyle = "#fff";
    ctx.font = "18px Arial";

    ctx.fillText(`📍 ${$("#Latitude").val()}, ${$("#Longitude").val()}`, 10, canvas.height - 95);
    ctx.fillText(`🎯 Accuracy: ${Math.round($("#Accuracy").val())} m`, 10, canvas.height - 65);
    ctx.fillText(`🏠 ${$("#Address").val().substring(0, 80)}`, 10, canvas.height - 40);
    ctx.fillText(`⏱ ${new Date().toLocaleString()}`, 10, canvas.height - 15);

    return new Promise(res => canvas.toBlob(b => res(b), "image/jpeg", 0.9));
  }

  /* =============================
     INIT ON PAGE LOAD
  ============================= */
  $(document).ready(function () {
    detectmobile();
    getLocationMandatory();

    $(document).on("change", 'input[name="payment_transfer_mode"]', function () {
      if ($(this).val() === "Bank Transfer") {
        $("#bank_verification_container").removeClass("d-none");
        $("#utr_no_container").removeClass("d-none");
        $("#payment_utr_no").focus();
      } else {
        $("#bank_verification_container").addClass("d-none");
        $("#utr_no_container").addClass("d-none");
        $("#payment_utr_no").val("");
      }
    });
  });


  /* =============================
     MAIN SUBMIT FUNCTION
  ============================= */
  async function UpdateFinalPaymentStatus(PaymentStatus, actualAmount, paidAmount, PaymentDate, Remark, btnid) {
    let form_data = new FormData();
    let $btn = $("#" + btnid);

    try {
      /* ==========================
         GET ROW DATA
      ========================== */
      let LabourId = $btn.data("employeeid");
      let SalaryId = $btn.data("salaryid");
      let tableid = $btn.data("tableid");

      /* ==========================
         VALIDATIONS (FIRST)
      ========================== */
      if (!PaymentStatus) {
        alertmessage("warning", "Payment status is required.");
        return;
      }

      if (!LabourId || !SalaryId) {
        alertmessage("warning", "Invalid employee or salary reference.");
        return;
      }

      if (!paidAmount || parseFloat(paidAmount) <= 0) {
        alertmessage("warning", "Paid amount must be greater than zero.");
        return;
      }

      let transferMode = $('input[name="payment_transfer_mode"]:checked').val() || "Cash";
      let utrNo = ($("#payment_utr_no").val() || "").trim();

      if (transferMode === "Bank Transfer" && !utrNo) {
        alertmessage("warning", "UTR No is mandatory for Bank Transfer.");
        $("#payment_utr_no").focus();
        return;
      }

      /* ==========================
         LOCATION (MANDATORY RE-CHECK)
      ========================== */
      if (!$("#Latitude").val() || !$("#Longitude").val()) {
        showLoading(true, "Fetching location...");
        try {
          await getLocationMandatory();
        } catch (e) {
          console.warn("Location retry failed:", e);
          // Proceed anyway or block? Usually block if mandatory.
          // But existing code just logs.
        }
      }

      /* ==========================
         CAMERA CAPTURE
      ========================== */
      showLoading(true, "Capturing photo...");
      // Ensure camera is active
      const video = document.getElementById("camera");
      if (!video.srcObject || video.readyState < 2) {
        await openCamera();
        await new Promise(r => setTimeout(r, 1000)); // Wait for stabilization
      }

      let photo = null;
      try {
        photo = await capturePhotoWithWatermark();
      } catch (err) {
        console.warn("Photo capture failed:", err);
      } finally {
        stopCamera(); // Stop camera after capture
      }

      // Check if image is captured or not
      if (!photo || !(photo instanceof Blob)) {
        showLoading(false);
        const confirmSave = await Swal.fire({
          title: "Proof of Payment",
          text: "Image is not captured as proof of payment. Are you sure you want to save data without image capture?",
          icon: "warning",
          showCancelButton: true,
          confirmButtonText: "Yes, save without image",
          cancelButtonText: "No, cancel",
          confirmButtonColor: "#f97316",
          cancelButtonColor: "#64748b"
        });

        if (!confirmSave.isConfirmed) {
          return; // Cancel saving
        }
        showLoading(true, "Saving payment...");
      }

      /* ==========================
         FORM DATA
      ========================== */
      var companyid = $('body').data('companyid');
      var subcompanyid = $('body').data('subcompanyid');
      var projectid = $('body').data('projectid');
      var loginid = $('body').data('loginid');
      var loginname = $('body').data('loginname');

      form_data.append('companyid', companyid);
      form_data.append('subcompanyid', subcompanyid);
      form_data.append('projectid', projectid);
      form_data.append('loginid', loginid);
      form_data.append('loginname', loginname);

      form_data.append("PaymentStatus", PaymentStatus);
      form_data.append("actualAmount", actualAmount);
      form_data.append("paidAmount", paidAmount);
      form_data.append("LabourId", LabourId);
      form_data.append("SalaryId", SalaryId);
      form_data.append("PaymentDate", PaymentDate || "");
      form_data.append("Remark", Remark || "");
      form_data.append("transfer_mode", transferMode);
      form_data.append("utr_no", utrNo);

      form_data.append("latitude", $("#Latitude").val() || "");
      form_data.append("longitude", $("#Longitude").val() || "");
      form_data.append("accuracy", $("#Accuracy").val() || "0");
      form_data.append("source", $("#Source").val() || "Unknown");
      form_data.append("address", $("#Address").val() || "");

      if (photo instanceof Blob) {
        form_data.append("Photo", photo, "payment_proof.jpg");
      }

      /* ==========================
         AJAX
      ========================== */
      $.ajax({
        type: "POST",
        url: $("body").data("routurl") +
          "modal/hrm_laboursalarymodal.php?action=UpdateFinalPaymentStatus",
        data: form_data,
        contentType: false,
        processData: false,
        dataType: "json",

        success: function (res) {
          if (res?.status?.toLowerCase() === "success") {
            alertmessage("success", res.message);
            $(".modal").modal("hide");
            $("#wageslocationlist").trigger("change");
          } else if (res?.status?.toLowerCase() === "morethan") {
            alertmessage("warning", res.message);
          } else {
            alertmessage("error", res?.message || "Payment update failed.");
          }
        },

        error: function (xhr) {
          console.error("AJAX ERROR:", xhr.responseText);
          alertmessage("error", "Something went wrong while updating payment.");
        },

        complete: function () {
          showLoading(false);
        }
      });

    } catch (e) {
      stopCamera();
      showLoading(false);
      Swal.fire("Error", String(e), "warning");
    }
  }

  /* =============================
     CHANGE TRANSFER MODE FUNCTIONS
  ============================= */
  function openChangeTransferModeModal(btn) {
    const row = $(btn).closest("tr");
    const employeeid = row.data("employeeid");
    const employeename = row.data("employeename");
    const datefrom = row.data("datefrom");
    const dateto = row.data("dateto");
    const salaryid = row.data("salaryid");
    const paidamount = parseFloat(row.data("paidamount")) || parseFloat(row.data("netsalary")) || 0;
    const currentMode = row.data("paymentmethod") || "Cash";
    const currentUtr = row.data("transactionno") || "";

    $("#changeMode_salaryId").val(salaryid);
    $("#changeMode_labourId").val(employeeid);
    $("#changeMode_employeName").text(employeename);
    $("#changeMode_employeeId").text("ID: " + employeeid);
    $("#changeMode_duration").text(`${datefrom} To ${dateto}`);
    $("#changeMode_paidAmount").text("₹" + paidamount.toFixed(2));
    $("#changeMode_remark").val("");
    $("#changeMode_utr_no").val(currentUtr);

    const bankname = row.data("bankname") || "";
    const accountnumber = row.data("accountnumber") || "";
    const ifsccode = row.data("ifsccode") || "";
    const rawVerifyStatus = (row.data("verifystatus") || "").toString().trim().toLowerCase();
    const isVerified = (rawVerifyStatus === "verified");

    let bankCardHtml = "";
    if (isVerified) {
      bankCardHtml = `
        <div class="p-2.5 rounded-2 border" style="background-color: #f0fdf4; border-color: #bbf7d0 !important;">
          <div class="d-flex align-items-center justify-content-between mb-1 pb-1 border-bottom" style="border-color: #dcfce7 !important;">
            <div class="d-flex align-items-center gap-2">
              <i class="fa fa-university text-success"></i>
              <span class="fw-bold text-dark small">${bankname || 'Bank Account'}</span>
            </div>
            <span class="badge bg-success text-white fw-bold px-2 py-1" style="font-size: 11px;">
              <i class="fa fa-check-circle me-1"></i> Verified
            </span>
          </div>
          <div class="d-flex justify-content-between text-muted" style="font-size: 11.5px;">
            <div><span class="fw-medium">A/C:</span> <span class="text-dark fw-semibold">${accountnumber || '-'}</span></div>
            <div><span class="fw-medium">IFSC:</span> <span class="text-dark fw-semibold">${ifsccode || '-'}</span></div>
          </div>
        </div>
      `;
    } else {
      bankCardHtml = `
        <div class="p-2.5 rounded-2 border" style="background-color: #fef2f2; border-color: #fecaca !important;">
          <div class="d-flex align-items-center justify-content-between mb-1 pb-1 border-bottom" style="border-color: #fee2e2 !important;">
            <div class="d-flex align-items-center gap-2">
              <i class="fa fa-university text-danger"></i>
              <span class="fw-bold text-dark small">${bankname || 'Bank Details'}</span>
            </div>
            <span class="badge bg-danger text-white fw-bold px-2 py-1" style="font-size: 11px;">
              <i class="fa fa-times-circle me-1"></i> Un-Verify
            </span>
          </div>
          <div class="d-flex justify-content-between text-muted" style="font-size: 11.5px;">
            <div><span class="fw-medium">A/C:</span> <span class="text-dark fw-semibold">${accountnumber || 'N/A'}</span></div>
            <div><span class="fw-medium">IFSC:</span> <span class="text-dark fw-semibold">${ifsccode || 'N/A'}</span></div>
          </div>
        </div>
      `;
    }
    $("#changeMode_bank_container").html(bankCardHtml);

    if (currentMode.toLowerCase() === "bank transfer") {
      $('input[name="change_transfer_mode_radio"][value="Bank Transfer"]').prop("checked", true);
      $("#changeMode_bank_container").removeClass("d-none");
      $("#changeMode_utr_container").removeClass("d-none");
    } else {
      $('input[name="change_transfer_mode_radio"][value="Cash"]').prop("checked", true);
      $("#changeMode_bank_container").addClass("d-none");
      $("#changeMode_utr_container").addClass("d-none");
    }

    $("#ChangeTransferModeModal").modal("show");
  }

  $(document).on("change", 'input[name="change_transfer_mode_radio"]', function () {
    if ($(this).val() === "Bank Transfer") {
      $("#changeMode_bank_container").removeClass("d-none");
      $("#changeMode_utr_container").removeClass("d-none");
      $("#changeMode_utr_no").focus();
    } else {
      $("#changeMode_bank_container").addClass("d-none");
      $("#changeMode_utr_container").addClass("d-none");
    }
  });

  $(document).on("click", "#btnSubmitChangeTransferMode", function () {
    let salaryId = $("#changeMode_salaryId").val();
    let labourId = $("#changeMode_labourId").val();
    let transferMode = $('input[name="change_transfer_mode_radio"]:checked').val() || "Cash";
    let utrNo = ($("#changeMode_utr_no").val() || "").trim();
    let remark = ($("#changeMode_remark").val() || "").trim();

    if (!salaryId || !labourId) {
      alertmessage("warning", "Invalid salary or labour reference.");
      return;
    }

    if (transferMode === "Bank Transfer" && !utrNo) {
      alertmessage("warning", "UTR No is mandatory for Bank Transfer.");
      $("#changeMode_utr_no").focus();
      return;
    }

    let companyid = $('body').data('companyid');
    let subcompanyid = $('body').data('subcompanyid');
    let projectid = $('body').data('projectid');
    let loginname = $('body').data('loginname');

    let form_data = new FormData();
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("loginname", loginname);
    form_data.append("SalaryId", salaryId);
    form_data.append("LabourId", labourId);
    form_data.append("transfer_mode", transferMode);
    form_data.append("utr_no", utrNo);
    form_data.append("remark", remark);

    let $btn = $(this);
    $btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin me-2"></i> Saving...');

    $.ajax({
      type: "POST",
      url: $("body").data("routurl") + "modal/hrm_laboursalarymodal.php?action=ChangeLabourPaymentTransferMode",
      data: form_data,
      contentType: false,
      processData: false,
      dataType: "json",
      success: function (res) {
        if (res?.status?.toLowerCase() === "success") {
          Swal.fire({
            title: "Success!",
            text: res.message,
            icon: "success",
            timer: 1500,
            showConfirmButton: false
          });
          $("#ChangeTransferModeModal").modal("hide");
          $("#wageslocationlist").trigger("change");
        } else {
          alertmessage("error", res?.message || "Failed to update transfer mode.");
        }
      },
      error: function (xhr) {
        console.error("AJAX error:", xhr.responseText);
        alertmessage("error", "Error connecting to server.");
      },
      complete: function () {
        $btn.prop("disabled", false).html('<i class="fa fa-save me-2"></i> UPDATE TRANSFER MODE');
      }
    });
  });

  /* =============================
     UNDO PAYMENT STATUS FUNCTION
  ============================= */
  function undoLabourPayment(btn) {
    const row = $(btn).closest("tr");
    const employeeid = row.data("employeeid");
    const employeename = row.data("employeename");
    const salaryid = row.data("salaryid");
    const datefrom = row.data("datefrom");
    const dateto = row.data("dateto");
    const paidamount = parseFloat(row.data("paidamount")) || parseFloat(row.data("netsalary")) || 0;

    if (!salaryid || !employeeid) {
      alertmessage("warning", "Invalid labour salary record.");
      return;
    }

    Swal.fire({
      title: "Undo Payment Status?",
      html: `
        <div class="text-start p-2">
          <div class="alert alert-warning py-2 px-3 small d-flex align-items-start gap-2 mb-3">
            <i class="fa fa-exclamation-triangle text-warning fs-5 mt-0.5"></i>
            <div>
              <strong>Are you sure you want to revert this payment to Unpaid?</strong>
              <div class="text-muted" style="font-size: 11px;">This will clear the paid status and move the record back to Pending Payments.</div>
            </div>
          </div>
          <div class="p-2 border rounded bg-light mb-3" style="font-size: 12.5px;">
            <div><span class="text-muted">Labour:</span> <strong class="text-dark">${employeename} (${employeeid})</strong></div>
            <div><span class="text-muted">Period:</span> <span class="text-dark fw-semibold">${datefrom} To ${dateto}</span></div>
            <div><span class="text-muted">Paid Amount:</span> <strong class="text-danger">₹${paidamount.toFixed(2)}</strong></div>
          </div>
          <label class="form-label small fw-bold text-secondary mb-1">Reason for Undo (Optional):</label>
          <textarea id="swal_undo_reason" class="form-control form-control-sm" rows="2" placeholder="Enter reason for reverting payment..." style="resize:none;"></textarea>
        </div>
      `,
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: '<i class="fa fa-undo me-1"></i> Yes, Revert to Unpaid',
      cancelButtonText: 'Cancel',
      confirmButtonColor: "#dc3545",
      cancelButtonColor: "#64748b",
      focusCancel: true,
      preConfirm: () => {
        return $("#swal_undo_reason").val();
      }
    }).then((result) => {
      if (result.isConfirmed) {
        let reason = result.value || "";
        let companyid = $('body').data('companyid');
        let subcompanyid = $('body').data('subcompanyid');
        let projectid = $('body').data('projectid');
        let loginname = $('body').data('loginname');

        let form_data = new FormData();
        form_data.append("companyid", companyid);
        form_data.append("subcompanyid", subcompanyid);
        form_data.append("projectid", projectid);
        form_data.append("loginname", loginname);
        form_data.append("SalaryId", salaryid);
        form_data.append("LabourId", employeeid);
        form_data.append("remark", reason);

        Swal.fire({
          title: 'Reverting Payment...',
          text: 'Please wait',
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });

        $.ajax({
          type: "POST",
          url: $("body").data("routurl") + "modal/hrm_laboursalarymodal.php?action=UndoLabourPaymentStatus",
          data: form_data,
          contentType: false,
          processData: false,
          dataType: "json",
          success: function (res) {
            if (res?.status?.toLowerCase() === "success") {
              Swal.fire({
                title: "Reverted!",
                text: res.message,
                icon: "success",
                timer: 1500,
                showConfirmButton: false
              }).then(() => {
                $("#wageslocationlist").trigger("change");
              });
            } else {
              Swal.fire("Error", res?.message || "Failed to revert payment.", "error");
            }
          },
          error: function (xhr) {
            console.error("AJAX Error:", xhr.responseText);
            Swal.fire("Error", "Something went wrong while reverting payment.", "error");
          }
        });
      }
    });
  }

</script>