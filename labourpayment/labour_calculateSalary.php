<?php
if (isset($_GET['vw']) == 1) {
    $key = $_GET['k'] ?? '';
    $rooturl = "";
    $mdid = "";
    if (isset($_GET['mdid'])) {
        $mdid = $_GET['mdid'];
    }
    if (!empty($key)) {
        $key_decoded = base64url_decode($key);
        $key_parts = explode("|", $key_decoded);
        if (count($key_parts) >= 6) {
            $loginid = $key_parts[0];
            $loginroleid = $key_parts[1];
            $companyid = $key_parts[2];
            $subcompanyid = $key_parts[3];
            $projectid = $key_parts[4];
            $logintype = $key_parts[5];
        }
    }
    $dddr = loginmodulesdetails_frommaster($authconn, $companyid, $subcompanyid, $projectid, "moduleid", $mdid);
    $groupid = "";
    if (count($dddr) > 0) {
        $groupid = $dddr[0]['groupid'];
    }
} else {
    require('header.php');
}
?>
<!-- Google Fonts for premium typography -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/custom_labour.css">

<div class="main-container p-lg-4 p-3" style="font-family: 'Inter', sans-serif; background: #f8f9fa; min-height: 100vh;">
    
    <!-- Dashboard Header Section -->
    <div class="row align-items-center mb-4">
        <div class="col-md-7">
            <h2 class="fw-bold text-dark mb-1" style="font-family: 'Outfit', sans-serif; letter-spacing: -0.5px;">Labour Wages Processing</h2>
            <p class="text-muted small mb-0"><i class="ti ti-calculator me-1"></i> Review, Edit, and Approve workforce daily wages</p>
        </div>
        <div class="col-md-5 text-md-end mt-3 mt-md-0"></div>
    </div>

    <!-- Filters Selection Card -->
    <div class="card border-0 shadow-sm overflow-hidden mb-4" style="border-radius: 16px;">
        <div class="card-body p-4">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-bold text-muted small">Wages Month</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="ti ti-calendar-event text-primary"></i></span>
                        <select class="form-select bg-light border-0" id="slrycalyear"></select>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold text-muted small">Date Range</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="ti ti-calendar-time text-primary"></i></span>
                        <select class="form-select bg-light border-0" id="attdaterangelist"></select>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold text-muted small">Work Location</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="ti ti-map-pin text-primary"></i></span>
                        <select class="form-select bg-light border-0" id="wageslocationlist"></select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center" id="loadingdiv"></div>

    <div id="calviewdatadiv" style="display:none;">
        <!-- Stats Summary Section -->
        <div class="row g-3 mb-4">
            <div class="col-xl col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-3 d-flex align-items-center gap-3">
                        <div class="stat-icon bg-soft-primary text-primary p-3 rounded-4">
                            <i class="ti ti-users fs-24"></i>
                        </div>
                        <div>
                            <p class="text-muted small fw-medium mb-0">Total Labours</p>
                            <h4 class="fw-bold mb-0 ttllabourcount">0</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-3 d-flex align-items-center gap-3">
                        <div class="stat-icon bg-soft-success text-success p-3 rounded-4">
                            <i class="ti ti-calendar-check fs-24"></i>
                        </div>
                        <div>
                            <p class="text-muted small fw-medium mb-0">Worked Days</p>
                            <h4 class="fw-bold mb-0 ttlworkeddayscount">0</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-3 d-flex align-items-center gap-3">
                        <div class="stat-icon bg-soft-warning text-warning p-3 rounded-4">
                            <i class="ti ti-cash fs-24"></i>
                        </div>
                        <div>
                            <p class="text-muted small fw-medium mb-0">Total Wages</p>
                            <h4 class="fw-bold mb-0">₹ <span class="ttllabourwagesamountlbl">0</span></h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl col-md-6 col-sm-6">
                <div class="card border-0 shadow-sm rounded-4 h-100 position-relative overflow-hidden cursor-pointer" id="card_zerobasicfilter" style="cursor: pointer; transition: all 0.2s ease;" title="Click to filter labours where Basic Amount < ₹1">
                    <div class="card-body p-3 d-flex align-items-center gap-3">
                        <div class="stat-icon bg-soft-danger text-danger p-3 rounded-4">
                            <i class="ti ti-alert-triangle fs-24"></i>
                        </div>
                        <div>
                            <p class="text-muted small fw-medium mb-0" style="white-space: nowrap;">Payment Not Set</p>
                            <div class="d-flex align-items-baseline gap-1">
                                <h4 class="fw-bold mb-0 text-danger" id="labourzerobasiccount">0</h4>
                                <small class="text-muted" style="font-size: 11px;">(Basic &lt; ₹1)</small>
                            </div>
                        </div>
                    </div>
                    <div class="position-absolute top-0 end-0 p-2">
                        <span class="badge bg-soft-danger text-danger rounded-pill px-2 py-1" style="font-size: 10px;"><i class="ti ti-filter me-1"></i>Filter</span>
                    </div>
                </div>
            </div>
            <div class="col-xl col-md-6 col-sm-6">
                <div class="card border-0 shadow-sm rounded-4 h-100 position-relative overflow-hidden">
                    <div class="card-body p-3 d-flex align-items-center gap-3">
                        <div class="stat-icon bg-soft-secondary text-secondary p-3 rounded-4">
                            <i class="ti ti-user-x fs-24"></i>
                        </div>
                        <div>
                            <p class="text-muted small fw-medium mb-0">Zero Day Records</p>
                            <h4 class="fw-bold mb-0 text-muted" id="labourzerodayscount" style="cursor:pointer;">0</h4>
                        </div>
                    </div>
                    <div class="position-absolute top-0 end-0 p-2">
                        <i class="ti ti-info-circle text-muted fs-12"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Bar Section -->
        <div class="row align-items-center mb-4">
            <div class="col-lg-6">
                <div class="d-flex align-items-center gap-3">
                    <h5 class="fw-bold mb-0">Period: <span class="wagesperiod text-primary"></span></h5>
                    <div class="form-check form-switch ms-3 bg-white px-3 py-2 rounded-pill shadow-sm border">
                        <input class="form-check-input" type="checkbox" id="iseditwages" name="iseditwages">
                        <label class="form-check-label fw-bold text-muted small ms-1" for="iseditwages">Enable Editing</label>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mt-3 mt-lg-0 text-lg-end">
                <div class="d-inline-flex gap-2">
                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-sm btnReloadWageData" id="btnReloadWageData" title="Reload / Refresh current data">
                        <i class="ti ti-refresh me-1"></i> Reload Data
                    </button>
                    <button class="btn btn-outline-danger btn-sm rounded-pill px-3" id="exportlbpaymentpdf" style="display:none;">
                        <i class="ti ti-file-type-pdf me-1"></i> PDF
                    </button>
                    <button class="btn btn-outline-success btn-sm rounded-pill px-3" id="exportlbpaymentexcellb" 
                            onclick="gridtoexcel_withfilter_chunk('calculatesalry_tbl', 'Labour Wages', 'landscape',function(res){})">
                        <i class="ti ti-file-type-xls me-1"></i> Excel
                    </button>
                    <button class="btn btn-dark btn-sm rounded-pill px-3" id="addattofmissintlablour">
                        <i class="ti ti-plus me-1"></i> Manual Entry
                    </button>
                    <button class="btn btn-primary btn-sm rounded-pill px-4 fw-bold shadow-sm" id="savethecalculatedSalary" style="display:none;">
                        <i class="ti ti-device-floppy me-1"></i> <span id="saveupdatebtntxt">Save Payment</span>
                    </button>
                    <button class="btn btn-success btn-sm rounded-pill px-4 fw-bold shadow-sm d-none" id="GotoApprovallabourbagesbtn">
                        <i class="ti ti-circle-check me-1"></i> Proceed to Approval
                    </button>
                </div>
            </div>
        </div>

        <!-- Table Card Section -->
        <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 16px;" id="displaydataviewdiv">
            <div class="card-header bg-white py-3 border-bottom border-light d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="input-group search-box-premium" style="max-width: 450px;">
                    <span class="input-group-text bg-light border-0"><i class="ti ti-search text-muted"></i></span>
                    <input type="search" class="form-control bg-light border-0 ps-0" placeholder="Search by labour name, ID, wage..." id="searchcallabsalaryPaytbl">
                    <button class="btn btn-light border-0 text-muted px-2" type="button" id="btnClearTableSearch" style="display:none;" title="Clear search">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-sm btnReloadWageData" title="Reload / Refresh current data">
                        <i class="ti ti-refresh me-1"></i> Reload Data
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 60vh; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0" id="calculatesalry_tbl">
                        <thead class="bg-light sticky-top" style="z-index: 10;">
                            <tr>
                                <th class="ps-4 border-0 py-3 text-uppercase fs-11 fw-bold text-muted">S.No</th>
                                <th class="border-0 py-3 text-uppercase fs-11 fw-bold text-muted">Labour Name</th>
                                <th class="border-0 py-3 text-uppercase fs-11 fw-bold text-muted text-center">Wages/Day</th>
                                <th class="border-0 py-3 text-uppercase fs-11 fw-bold text-muted text-center">Days</th>
                                <th class="border-0 py-3 text-uppercase fs-11 fw-bold text-muted text-end">Basic Amt</th>
                                <th class="border-0 py-3 text-uppercase fs-11 fw-bold text-muted text-end">Incentive (G)</th>
                                <th class="border-0 py-3 text-uppercase fs-11 fw-bold text-muted text-end">Incentive (S)</th>
                                <th class="border-0 py-3 text-uppercase fs-11 fw-bold text-muted text-end">Overtime</th>
                                <th class="border-0 py-3 text-uppercase fs-11 fw-bold text-muted text-end">Other</th>
                                <th class="pe-4 border-0 py-3 text-uppercase fs-11 fw-bold text-muted text-end">Net Payable</th>
                            </tr>
                        </thead>
                        <tbody class="border-0"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modern Modals -->
<div class="modal fade" id="UpdatePresentDaysModal" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 p-4 pb-0">
                <h4 class="fw-bold text-dark mb-0">Edit Record Details</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert bg-soft-primary border-0 p-3 mb-4 rounded-3">
                    <p class="mb-1 fw-bold text-primary small"><i class="ti ti-user"></i> <span id="upPrDay_empname"></span></p>
                    <p class="mb-0 text-muted small"><i class="ti ti-calendar-time"></i> <span id="upPrDay_duration"></span></p>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold text-muted">Current Days</label>
                        <input type="text" id="oldPresentDays" class="form-control bg-light border-0" readonly />
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold text-muted">New Days</label>
                        <input type="text" id="newPresentDays" class="form-control bg-soft-success border-success-subtle" data-maxlength="0"
                               onkeypress="return isNumberKey(event)" placeholder="0" />
                        <small class="text-danger d-block mt-1" style="font-size: 11px;"><strong>Note :-</strong> for edit new days edit if required</small>
                    </div>
                    <div class="col-4">
                        <label class="form-label small fw-bold text-muted">Incentive (G)</label>
                        <input type="text" id="IncentiveGrafting" class="form-control bg-light border-0" onkeypress="return isNumberKey(event)" />
                    </div>
                    <div class="col-4">
                        <label class="form-label small fw-bold text-muted">Incentive (S)</label>
                        <input type="text" id="IncentiveSeasional" class="form-control bg-light border-0" onkeypress="return isNumberKey(event)" />
                    </div>
                    <div class="col-4">
                        <label class="form-label small fw-bold text-muted">Overtime</label>
                        <input type="text" id="OvertimeAmount" class="form-control bg-light border-0" onkeypress="return isNumberKey(event)" />
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold text-muted">Remark <sup class="text-danger">*</sup></label>
                        <textarea id="updprdays_remark" class="form-control bg-light border-0" rows="2" placeholder="Reason for change..."></textarea>
                    </div>
                </div>
                <button class="btn btn-primary w-100 py-3 mt-4 rounded-pill fw-bold shadow" id="EditupdatePresentDaysbtn"
                        onclick="UpdatePresentDaysToCalculateAttendance($('#newPresentDays').val(), $('#newPresentDays').data('maxlength'),$('#IncentiveGrafting').val(),$('#IncentiveSeasional').val(),$('#OvertimeAmount').val(), $('#updprdays_remark').val(),'EditupdatePresentDaysbtn');">
                        Update & Re-calculate
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add Missing Labour Attendance Modal -->
<div class="modal fade" id="addmissinglabourattmodal" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 p-4 pb-0">
                <h4 class="fw-bold text-dark mb-0">Manual Attendance Entry</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 align-items-end mb-4">
                    <div class="col-md-8">
                        <label class="form-label small fw-bold text-muted">Search Labour ID</label>
                        <input type="text" id="searchfrommasterlb" class="form-control bg-light border-0 py-2" placeholder="Enter Labour ID to search..." />
                    </div>
                    <div class="col-md-4">
                        <button type="button" id="laboursearchfrommasterbtn" class="btn btn-primary w-100 py-2 rounded-pill fw-bold shadow-sm">
                            <i class="ti ti-search me-1"></i> Search Labour
                        </button>
                    </div>
                </div>

                <div id="lbattlbdiv" style="display:none;">
                    <div class="table-responsive" style="border-radius: 12px; border: 1px solid #f1f1f1;">
                        <table class="table table-hover align-middle mb-0" id="upmissingattlbtbl">
                            <thead class="bg-light sticky-top">
                                <tr>
                                    <th class="ps-4 text-uppercase fs-11 fw-bold text-muted">Labour ID</th>
                                    <th class="text-uppercase fs-11 fw-bold text-muted">Labour Name</th>
                                    <th class="text-uppercase fs-11 fw-bold text-muted">Date From</th>
                                    <th class="text-uppercase fs-11 fw-bold text-muted">Date To</th>
                                    <th class="text-uppercase fs-11 fw-bold text-muted">Att. Days</th>
                                    <th class="pe-4 text-uppercase fs-11 fw-bold text-muted text-end">Action</th>
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

<!-- Update Labour Master Payment Modal -->
<div class="modal fade" id="UpdateLabourMasterPaymentModal" role="dialog" tabindex="-1" aria-labelledby="UpdateLabourMasterPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 p-4 pb-0">
                <div>
                    <h4 class="fw-bold text-dark mb-1" id="UpdateLabourMasterPaymentModalLabel"><i class="ti ti-cash text-primary me-2"></i>Update Master Payment</h4>
                    <p class="text-muted small mb-0">Update standard daily wage rate & payment method in Labour Master</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert bg-soft-primary border-0 p-3 mb-4 rounded-3">
                    <p class="mb-1 fw-bold text-primary small"><i class="ti ti-user me-1"></i> <span id="masterModalEmpName"></span></p>
                    <p class="mb-0 text-muted small"><i class="ti ti-id me-1"></i> Labour ID: <b class="text-dark" id="masterModalEmpID"></b></p>
                </div>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label small fw-bold text-muted">Per Day Wages / Rate (₹) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="ti ti-currency-rupee text-muted"></i></span>
                            <input type="number" step="0.01" min="1" id="masterModalPerDay" class="form-control bg-light border-0" placeholder="e.g. 350.00" onkeypress="return isNumberKey(event)" required />
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold text-muted">Payment Mode / Method <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="ti ti-credit-card text-muted"></i></span>
                            <select id="masterModalPaymentMethod" class="form-select bg-light border-0" required>
                                <option value="Cash">Cash</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="UPI">UPI</option>
                                <option value="Cheque">Cheque</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold text-muted">Comments / Remarks (Optional)</label>
                        <input type="text" id="masterModalComments" class="form-control bg-light border-0" placeholder="Reason or reference..." />
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch mt-1">
                            <input class="form-check-input" type="checkbox" id="masterModalSyncCurrent" checked>
                            <label class="form-check-label small text-muted" for="masterModalSyncCurrent">
                                Also apply new wage to current attendance row
                            </label>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-primary w-100 py-3 mt-4 rounded-pill fw-bold shadow" id="btnSubmitMasterWageUpdate">
                    <i class="ti ti-device-floppy me-1"></i> Update in Master
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-soft-primary { background-color: #eef2ff; }
    .bg-soft-success { background-color: #f0fdf4; }
    .bg-soft-warning { background-color: #fffbeb; }
    .bg-soft-danger { background-color: #fef2f2; }
    .fs-11 { font-size: 11px; }
    .stat-icon { transition: transform 0.3s ease; }
    @keyframes spin-anim {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    .ti-spin {
        animation: spin-anim 0.8s linear infinite;
        display: inline-block;
    }
    .search-box-premium {
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s ease;
        box-shadow: 0 0 0 1px rgba(0,0,0,0.05);
    }
    .search-box-premium:focus-within {
        box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.25);
    }
    #calculatesalry_tbl thead th {
        background: #fcfcfc;
        border-bottom: 1px solid #eee;
    }
    #calculatesalry_tbl tbody tr:hover {
        background-color: #f8faff;
        box-shadow: inset 4px 0 0 #0d6efd;
    }
</style>

<?php
if (isset($_GET['vw']) == 1) {
} else {
    require('footer.php');
}
?>
<!-- Include original logic (assumed to be in appropriate assets or inline if needed) -->
<script>
    // Initialize standard variables
    let companyid = $("body").data("companyid");
    let subcompanyid = $("body").data("subcompanyid");
    let projectid = $("body").data("projectid");
    var routurl = $("body").data("routurl");
    var key = $("body").data("key");
    var loginname = '<?php echo isset($loginname) ? $loginname : ""; ?>';
    var mdid = "<?php echo $mdid; ?>";
    let bodyRoleAttr = $("body").attr("data-loginroleid");
    if (bodyRoleAttr === undefined || bodyRoleAttr === null || bodyRoleAttr === "") {
        bodyRoleAttr = $("#hidden_loginroleid").val();
    }
    var loginroleid = (bodyRoleAttr !== undefined && bodyRoleAttr !== null && bodyRoleAttr !== "") 
        ? parseInt(bodyRoleAttr) 
        : parseInt("<?php echo (isset($loginroleid) && is_numeric($loginroleid)) ? $loginroleid : 99; ?>");
    $("body").attr("data-loginroleid", loginroleid);

    // ... (All original script logic remains below, identical to original file for functionality) ...
    // Note: I will keep the original script logic intact but ensure any UI interactions like modal shows use the new IDs.
</script>

<script>
    // [ORIGINAL SCRIPT LOGIC START]
    $("#MissingPerDayPaymentbtn").on("click", function () {
        $("#MissingPerDayPaymentmodal").modal("show");
    });

    $("#addattofmissintlablour").on("click", function () {
        $("#addmissinglabourattmodal").modal("show");
    });

    $("#laboursearchfrommasterbtn").on("click", function () {
        let selectedLabourId = $("#searchfrommasterlb").val();
        if (!selectedLabourId) {
            alertmessage('error', 'Labour id missing for search');
            return false;
        }
        $("#lbattlbdiv").show();
        LabourSearchfromMasterDataforaddatt(selectedLabourId, function (res) {
            let $tbody = $("#upmissingattlbtbl tbody");
            let attdate = $("#attdaterangelist").val();
            let locationid = $("#wageslocationlist").val();
            let attdatefrom="";
            let attdateto="";
            if(attdate){
                let dateexpt = attdate.split("|");
                attdatefrom =dateexpt[0]; 
                attdateto =dateexpt[1]; 
            }
            $tbody.empty();
            let rows = "";
            if (Array.isArray(res) && res.length > 0) {
                res.forEach(function (item) {
                    let labourid = item.EmployeeID || "";
                    let labourname = item.FullName || "";
                    rows +=`
                        <tr data-labourid="${labourid}">
                            <td><input type="text" class="form-control labourid" value="${labourid}" readonly /></td>
                            <td><input type="text" class="form-control labourname" value="${labourname}" readonly /></td>
                             <td><input type="date" class="form-control attdatefrom" value='${attdatefrom}' readonly /></td>
                            <td><input type="date" class="form-control attdateto" value='${attdateto}' readonly /></td>
                            <td><input type="number" class="form-control attdays" /></td>
                            <td>
                                <input type="hidden" class="form-control locationid" value="${locationid}" />
                                <button type="button" class="btn btn-sm btn-success updatebtn" onclick="updatemissinglabatt(this)">
                                    UPDATE
                                </button>
                            </td>
                        </tr>
                    `;
                });
            } else {
                rows +=`
                        <tr>
                            <td><input type="text" class="form-control labourid" value="${selectedLabourId}" /></td>
                            <td><input type="text" class="form-control labourname" value="" /></td>
                            <td><input type="date" class="form-control attdatefrom" value='${attdatefrom}' /></td>
                            <td><input type="date" class="form-control attdateto" value='${attdateto}' /></td>
                            <td><input type="number" class="form-control attdays" /></td>
                            <td>
                                <input type="hidden" class="form-control locationid" value="${locationid}" />
                                <button type="button" class="btn btn-sm btn-success updatebtn" onclick="updatemissinglabatt(this)">
                                    ADD NEW
                                </button>
                            </td>
                        </tr>
                    `;
            }
            $tbody.html(rows);
        });
    });

    $(document).on("click", "input[name='selectionradio']", function () {
        let selecteraioval = $(this).val();
        if (selecteraioval == "individual") {
            $(".multiple").addClass("d-none");
            $(".individual").removeClass("d-none");
            $("#updatemultipleentry").addClass("d-none");
        } else {
            $(".multiple").removeClass("d-none");
            $(".individual").addClass("d-none");
            $("#updatemultipleentry").removeClass("d-none");
        }
    });

    $(document).ready(function () {
        handleMasterCheckboxAll("mnsltlabourslry", "payperdaycheckbox");
        
        // Initial load of Month/Year list
        LabourAttendanceMonthYearList("all", function (res) {
            let $list = $("#slrycalyear");
            $list.empty();
            if (res.length > 0) {
                $list.append('<option value="" selected disabled>Choose Month</option>');
                res.sort((a,b) => (a.AttendanceYear === b.AttendanceYear) ? b.AttendanceMonth - a.AttendanceMonth : b.AttendanceYear - a.AttendanceYear);
                res.forEach(item => {
                    $list.append(`<option value="${item.AttendanceYear}-${item.AttendanceMonth}">${item.MonthName} - ${item.AttendanceYear}</option>`);
                });
                if (res.length === 1) $list.prop("selectedIndex", 1).trigger("change");
            } else {
                $list.append('<option value="" disabled>No data available</option>');
            }
        });
    });

    $(document).on("change", "#slrycalyear", function () {
        LabourAttendanceDateRangeList($("#slrycalyear").val(), function (res) {
            let $list = $("#attdaterangelist");
            $list.empty();
            if (res.length > 0) {
                $list.append('<option value="" selected disabled>Choose Range</option>');
                res.sort((a,b) => new Date(b.DateFrom) - new Date(a.DateFrom));
                res.forEach(item => {
                    $list.append(`<option value="${item.DateFrom}|${item.DateTo}">${item.DateFrom} - ${item.DateTo}</option>`);
                });
                if (res.length === 1) $list.prop("selectedIndex", 1).trigger("change");
            } else {
                $list.append('<option value="" disabled>No data available</option>');
            }
        });
    });

    $(document).on("change", "#attdaterangelist", function () {
        LabourAttendanceLocationList("withindates", $("#attdaterangelist").val(), function (res) {
            var lctlist = "";
            $("#wageslocationlist").empty();
            if (res.length > 0) {
                if (res.length > 1) lctlist += "<option value=''>Select Location</option>";
                res.forEach(item => {
                    lctlist += `<option value="${item.LocationId}">${item.LocationName}</option>`;
                });
            }
            if (lctlist) $("#wageslocationlist").append(lctlist);
            if (res.length == 1) $("#wageslocationlist").prop("selectedIndex", 0).trigger("change");
        });
    });

    function reloadLabourWagesData(callback) {
        let $reloadBtns = $(".btnReloadWageData");
        $reloadBtns.prop("disabled", true).find("i").addClass("ti-spin");

        let loadingMsg = $("<div id='loadingMsg' class='alert bg-soft-primary text-primary text-center p-3 mt-3'>Loading wages data...</div>");
        $("#loadingdiv").html(loadingMsg);
        var periodext = $("#attdaterangelist").val();
        var datefrom = "", dateto = "";
        if (periodext) {
            var p = periodext.split("|");
            datefrom = p[0]; dateto = p[1];
        }

        $("#calviewdatadiv").hide();
        let locationid = $("#wageslocationlist").val();
        let selectedLocText = $("#wageslocationlist option:selected").text();

        $(".wagesperiod").html(changeDateFormatYmd_dMY(datefrom) + " To " + changeDateFormatYmd_dMY(dateto));

        if (selectedLocText && selectedLocText !== 'Select Location') {
            $(".wageslocationbadge").text(selectedLocText).show();
        } else {
            $(".wageslocationbadge").hide();
        }
        
        EmplistforCalculateSalaryLabour_htmlcallback('slrycalyear', 'attdaterangelist', locationid, function (response) {
            $reloadBtns.prop("disabled", false).find("i").removeClass("ti-spin");
            $("#displaydataviewdiv").show();
            $("#calviewdatadiv").show();
            
            let hasRecords = response && response.data && (parseInt(response.ttllength) > 0);

            if (hasRecords) {
                if (response.calstatuscount == response.apprdstatuscount && response.calstatuscount > 0) {
                    $("#savethecalculatedSalary").hide();
                } else {
                    $("#savethecalculatedSalary").show();
                }

                if (response.status == "Calculated" || response.status == "Approved") {
                    $("#exportlbpaymentpdf").show();
                    $("#loadingMsg").html("<h5 class='mb-0'><i class='ti ti-circle-check'></i> Wages Processed & Saved</h5>").addClass("bg-soft-success text-success").removeClass("bg-soft-primary text-primary");
                    $("#GotoApprovallabourbagesbtn").removeClass("d-none");
                } else {
                    $("#exportlbpaymentpdf").hide();
                    $("#GotoApprovallabourbagesbtn").addClass("d-none");
                    $("#loadingMsg").html("<h5 class='mb-0'><i class='ti ti-info-circle'></i> Reviewing Raw Logs</h5>").addClass("bg-soft-warning text-warning").removeClass("bg-soft-primary text-primary");
                }

                resetZeroBasicFilter();
                $("#calculatesalry_tbl tbody").html(response.data);
                $(".ttllabourwagesamountlbl").text(response.totalwagesamount.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                $(".ttllabourcount").text(response.ttllength);
                $(".ttlworkeddayscount").text(response.totalworkeddays);
                $("#labourzerodayscount").text(response.zerodaylength).data("labourzerodaysdata", response.zerodayslist);

                let zeroBasicCount = 0;
                $("#calculatesalry_tbl tbody tr").each(function () {
                    let hasZero = $(this).attr("data-haszerobasic");
                    let bAmt = parseFloat($(this).attr("data-basiclabourpayment")) || 0;
                    let payRate = parseFloat($(this).find(".payperdayinput").val()) || 0;
                    if (hasZero == "1" || hasZero == 1 || bAmt < 1 || payRate <= 0) {
                        $(this).attr("data-haszerobasic", "1");
                        $(this).data("haszerobasic", 1);
                        zeroBasicCount++;
                        if ($(this).find(".employeename span.badge.bg-danger:contains('Rate Not Set')").length === 0) {
                            let empId = $(this).data("employeeid") || "";
                            let empName = $(this).data("employeename") || "";
                            let pMethod = $(this).data("paymentmethod") || "Cash";
                            let badgeHtml = (loginroleid <= 3)
                                ? ` <span class="badge bg-danger ms-1 btnUpdateMasterWage" style="cursor:pointer;" title="Click to edit/update Master Wage & Payment Mode" data-employeeid="${empId}" data-employeename="${empName}" data-payperday="${payRate}" data-paymentmethod="${pMethod}"><i class="ti ti-edit me-1"></i>Rate Not Set</span>`
                                : ` <span class="badge bg-danger ms-1">Rate Not Set</span>`;
                            $(this).find(".employeename").append(badgeHtml);
                        }
                    } else {
                        $(this).attr("data-haszerobasic", "0");
                        $(this).data("haszerobasic", 0);
                    }
                });

                let displayZeroCount = (typeof response.zerobasiccount !== 'undefined') ? parseInt(response.zerobasiccount) : zeroBasicCount;
                if (zeroBasicCount > displayZeroCount) {
                    displayZeroCount = zeroBasicCount;
                }
                $("#labourzerobasiccount").text(displayZeroCount);
                if (displayZeroCount > 0) {
                    $("#card_zerobasicfilter").addClass("border border-danger");
                } else {
                    $("#card_zerobasicfilter").removeClass("border border-danger");
                }

                // Apply any active search filter
                applyLabourTableFilter();
            } else {
                $("#savethecalculatedSalary").hide();
                $("#exportlbpaymentpdf").hide();
                $("#GotoApprovallabourbagesbtn").addClass("d-none");
                $("#loadingMsg").html("<h5 class='mb-0'><i class='ti ti-info-circle'></i> Reviewing Raw Logs</h5>").addClass("bg-soft-warning text-warning").removeClass("bg-soft-primary text-primary");

                resetZeroBasicFilter();
                $("#calculatesalry_tbl tbody").html("<tr><td colspan='12' class='text-center py-5 text-muted'>No attendance logs found for this period.</td></tr>");
                $(".ttllabourwagesamountlbl").text('0.00');
                $(".ttllabourcount").text('0');
                $(".ttlworkeddayscount").text('0');
                $("#labourzerodayscount").text('0');
                $("#labourzerobasiccount").text('0');
                $("#card_zerobasicfilter").removeClass("border border-danger");
            }

            if (typeof callback === "function") {
                callback(response);
            }
        });
    }

    $(document).on("change", "#wageslocationlist", function () {
        reloadLabourWagesData();
    });

    $(document).on("click", ".btnReloadWageData", function (e) {
        e.preventDefault();
        reloadLabourWagesData();
    });

    let isZeroBasicFiltered = false;

    function resetZeroBasicFilter() {
        isZeroBasicFiltered = false;
        $("#calculatesalry_tbl tbody tr").show();
        $("#card_zerobasicfilter").removeClass("border-2 bg-soft-danger shadow-sm");
        $("#activeZeroBasicFilterBadge").remove();
        applyLabourTableFilter();
    }

    $(document).on("click", "#card_zerobasicfilter", function () {
        let count = parseInt($("#labourzerobasiccount").text()) || 0;
        if (count === 0 && !isZeroBasicFiltered) {
            Swal.fire({
                title: "All Rates Configured",
                text: "All active labours for this period have their basic payment rate defined (Basic Amt >= ₹1).",
                icon: "info"
            });
            return;
        }

        isZeroBasicFiltered = !isZeroBasicFiltered;
        if (isZeroBasicFiltered) {
            $("#card_zerobasicfilter").addClass("border-2 bg-soft-danger shadow-sm");
            $("#activeZeroBasicFilterBadge").remove();
            let visibleCount = 0;
            $("#calculatesalry_tbl tbody tr").each(function () {
                let hasZeroBasic = $(this).attr("data-haszerobasic");
                if (hasZeroBasic == 1 || hasZeroBasic == "1") {
                    visibleCount++;
                }
            });
            $("#displaydataviewdiv .card-header").after(`
                <div id="activeZeroBasicFilterBadge" class="bg-soft-danger text-danger px-4 py-2 d-flex justify-content-between align-items-center border-bottom border-danger">
                    <span class="fw-bold small"><i class="ti ti-filter me-1"></i> Filtered View: Showing <b>${visibleCount}</b> Labour(s) with Payment Not Set (Basic Amt &lt; ₹1)</span>
                    <button class="btn btn-sm btn-danger rounded-pill px-3 py-1 fw-bold shadow-sm" id="clearZeroBasicFilterBtn">
                        <i class="ti ti-x me-1"></i> Show All Labours
                    </button>
                </div>
            `);
            applyLabourTableFilter();
        } else {
            resetZeroBasicFilter();
        }
    });

    $(document).on("click", "#clearZeroBasicFilterBtn", function (e) {
        e.stopPropagation();
        resetZeroBasicFilter();
    });

    function applyLabourTableFilter() {
        let rawVal = $("#searchcallabsalaryPaytbl").val() || "";
        let query = rawVal.toLowerCase().trim().replace(/\s+/g, ' ');
        let queryWords = query ? query.split(' ') : [];

        if (rawVal.trim().length > 0) {
            $("#btnClearTableSearch").show();
        } else {
            $("#btnClearTableSearch").hide();
        }

        $("#calculatesalry_tbl tbody tr").each(function () {
            let $tr = $(this);

            // Respect zero basic filter if active
            if (isZeroBasicFiltered) {
                let hasZero = $tr.attr("data-haszerobasic");
                if (hasZero != "1" && hasZero != 1) {
                    $tr.hide();
                    return;
                }
            }

            if (queryWords.length === 0) {
                $tr.show();
                return;
            }

            let empId = ($tr.data("employeeid") || $tr.attr("data-employeeid") || "").toString().toLowerCase();
            let empName = ($tr.data("employeename") || $tr.attr("data-employeename") || "").toString().toLowerCase().replace(/\s+/g, ' ');
            let pMethod = ($tr.data("paymentmethod") || $tr.attr("data-paymentmethod") || "").toString().toLowerCase();
            
            let tdText = $tr.find("td").map(function () {
                return $(this).text();
            }).get().join(" ").toLowerCase().replace(/\s+/g, ' ');

            let inputVals = $tr.find("input, select").map(function () {
                return $(this).val();
            }).get().join(" ").toLowerCase().replace(/\s+/g, ' ');

            let searchableContent = (empId + " " + empName + " " + pMethod + " " + tdText + " " + inputVals).replace(/\s+/g, ' ');

            let matches = queryWords.every(function (word) {
                return searchableContent.indexOf(word) > -1;
            });

            if (matches) {
                $tr.show();
            } else {
                $tr.hide();
            }
        });
    }

    $(document).on("input keyup search change", "#searchcallabsalaryPaytbl", function () {
        applyLabourTableFilter();
    });

    $(document).on("click", "#btnClearTableSearch", function () {
        $("#searchcallabsalaryPaytbl").val("").trigger("input");
    });

    $(document).on("input", ".payperdayinput", function () {
        let $tr = $(this).closest("tr");
        let payPerDay = parseFloat($(this).val()) || 0;
        let presentDays = parseFloat($tr.data("presentdays")) || 0;
        let basicAmt = payPerDay * presentDays;
        let incGrafting = parseFloat($tr.data("incentivegrafting")) || 0;
        let incSeasional = parseFloat($tr.data("incentiveseasional")) || 0;
        let overtime = parseFloat($tr.data("overtimepayment")) || 0;
        let other = parseFloat($tr.data("otherpayment")) || 0;
        let netPayable = basicAmt + incGrafting + incSeasional + overtime + other;

        $tr.find("td.basicpayment").text(basicAmt.toFixed(2));
        $tr.find("td.netpayable").text(netPayable.toFixed(2));
        $tr.attr("data-basiclabourpayment", basicAmt);
        $tr.attr("data-netpayable", netPayable);
        
        let hasZero = (basicAmt < 1 || payPerDay <= 0) ? 1 : 0;
        $tr.attr("data-haszerobasic", hasZero);
        $tr.data("haszerobasic", hasZero);

        if (hasZero === 0) {
            $tr.find(".employeename span.badge.bg-danger:contains('Rate Not Set')").remove();
        } else {
            if ($tr.find(".employeename span.badge.bg-danger:contains('Rate Not Set')").length === 0) {
                let empId = $tr.data("employeeid") || "";
                let empName = $tr.data("employeename") || "";
                let pMethod = $tr.data("paymentmethod") || "Cash";
                let badgeHtml = (loginroleid <= 3) 
                    ? ` <span class="badge bg-danger ms-1 btnUpdateMasterWage" style="cursor:pointer;" title="Click to edit/update Master Wage & Payment Mode" data-employeeid="${empId}" data-employeename="${empName}" data-payperday="${payPerDay}" data-paymentmethod="${pMethod}"><i class="ti ti-edit me-1"></i>Rate Not Set</span>`
                    : ` <span class="badge bg-danger ms-1">Rate Not Set</span>`;
                $tr.find(".employeename").append(badgeHtml);
            }
        }

        // Recalculate totals
        let totalWages = 0;
        let zeroBasicCount = 0;
        $("#calculatesalry_tbl tbody tr").each(function () {
            let bAmt = parseFloat($(this).attr("data-basiclabourpayment")) || 0;
            let nPay = parseFloat($(this).attr("data-netpayable")) || 0;
            let pRate = parseFloat($(this).find(".payperdayinput").val()) || 0;
            totalWages += nPay;
            if (bAmt < 1 || pRate <= 0) {
                zeroBasicCount++;
            }
        });
        $(".ttllabourwagesamountlbl").text(totalWages.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        $("#labourzerobasiccount").text(zeroBasicCount);
        if (zeroBasicCount > 0) {
            $("#card_zerobasicfilter").addClass("border border-danger");
        } else {
            $("#card_zerobasicfilter").removeClass("border border-danger");
        }
    });

    $(document).on("click", ".btnUpdateMasterWage", function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (loginroleid > 3) {
            Swal.fire("Permission Denied", "Only authorized users (Role <= 3) can edit/update master wages.", "error");
            return;
        }

        let $tr = $(this).closest("tr");
        let employeeId = $(this).data("employeeid") || $tr.data("employeeid");
        let employeeName = $(this).data("employeename") || $tr.data("employeename");
        let payPerDay = $(this).data("payperday");
        if (typeof payPerDay === "undefined" || payPerDay === "" || isNaN(parseFloat(payPerDay))) {
            payPerDay = $tr.find("input.payperdayinput").val() || "";
        }
        let paymentMethod = $(this).data("paymentmethod") || $tr.data("paymentmethod") || "Cash";

        $("#masterModalEmpID").text(employeeId);
        $("#masterModalEmpName").text(employeeName);
        $("#masterModalPerDay").val(parseFloat(payPerDay) > 0 ? payPerDay : "");
        $("#masterModalPaymentMethod").val(paymentMethod || "Cash");
        $("#masterModalComments").val("");
        $("#masterModalSyncCurrent").prop("checked", true);

        $("#btnSubmitMasterWageUpdate").data({
            employeeid: employeeId,
            employeename: employeeName
        });

        $("#UpdateLabourMasterPaymentModal").modal("show");
    });

    $(document).on("click", "#btnSubmitMasterWageUpdate", function () {
        if (loginroleid > 3) {
            Swal.fire("Permission Denied", "Only authorized users (Role <= 3) can edit/update master wages.", "error");
            return;
        }

        let employeeId = $(this).data("employeeid");
        let employeeName = $(this).data("employeename");
        let perDayPayment = parseFloat($("#masterModalPerDay").val()) || 0;
        let paymentMethod = $("#masterModalPaymentMethod").val() || "Cash";
        let comments = $("#masterModalComments").val() || "";
        let syncCurrent = $("#masterModalSyncCurrent").is(":checked");

        if (perDayPayment <= 0) {
            Swal.fire("Invalid Input", "Please enter a valid Per Day wage greater than ₹0.", "warning");
            return;
        }

        let periodext = $("#attdaterangelist").val() || "";
        let dateFrom = "", dateTo = "";
        if (periodext) {
            let p = periodext.split("|");
            dateFrom = p[0];
            dateTo = p[1];
        }

        let $btn = $(this);
        let origText = $btn.html();
        $btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

        let formData = new FormData();
        formData.append("companyid", companyid);
        formData.append("subcompanyid", subcompanyid);
        formData.append("projectid", projectid);
        formData.append("loginroleid", loginroleid);
        formData.append("loginname", loginname);
        formData.append("EmployeeID", employeeId);
        formData.append("PerDayPayment", perDayPayment);
        formData.append("PaymentMethod", paymentMethod);
        formData.append("Comments", comments);
        formData.append("dateFrom", dateFrom);
        formData.append("dateTo", dateTo);

        $.ajax({
            type: "POST",
            url: routurl + "modal/hrm_laboursalarymodal.php?action=UpdateLabourMasterWageAndMode",
            data: formData,
            contentType: false,
            processData: false,
            dataType: "JSON",
            success: function (res) {
                $btn.prop("disabled", false).html(origText);
                if (res.status === "success") {
                    $("#UpdateLabourMasterPaymentModal").modal("hide");
                    Swal.fire({
                        icon: "success",
                        title: "Master Updated",
                        text: `Labour ${employeeId}: Wages/Day updated to ₹${perDayPayment.toFixed(2)} (${paymentMethod}) in Master.`,
                        timer: 2000,
                        showConfirmButton: false
                    });

                    if (syncCurrent) {
                        let $row = $(`#calculatesalry_tbl tbody tr[data-employeeid="${employeeId}"]`);
                        if ($row.length > 0) {
                            $row.attr("data-paymentmethod", paymentMethod);
                            $row.data("paymentmethod", paymentMethod);
                            $row.find(".btnUpdateMasterWage").data("payperday", perDayPayment);
                            $row.find(".btnUpdateMasterWage").data("paymentmethod", paymentMethod);
                            
                            let $input = $row.find("input.payperdayinput");
                            $input.val(perDayPayment).trigger("input");

                            // If currently in filtered view for Payment Not Set, update filtered view count
                            if (isZeroBasicFiltered) {
                                $row.hide();
                                let remainingCount = 0;
                                $("#calculatesalry_tbl tbody tr").each(function () {
                                    if ($(this).attr("data-haszerobasic") == "1") {
                                        remainingCount++;
                                    }
                                });
                                $("#labourzerobasiccount").text(remainingCount);
                                $("#activeZeroBasicFilterBadge b").text(remainingCount);
                                if (remainingCount === 0) {
                                    resetZeroBasicFilter();
                                }
                            }
                        }
                    }
                } else {
                    Swal.fire("Error", res.message || "Failed to update master payment.", "error");
                }
            },
            error: function (xhr) {
                $btn.prop("disabled", false).html(origText);
                Swal.fire("Error", "Request failed: " + xhr.statusText, "error");
            }
        });
    });

    $(document).on("change", "#iseditwages", function () {
        if ($(this).is(":checked")) {
            $(".editPresentDays").removeClass("d-none");
            $(".payperdayinput").prop("disabled", false).prop("readonly", false);
            $("#exportlbpaymentpdf").hide();
            $("#savethecalculatedSalary").show();
            $("#saveupdatebtntxt").text("Update Wages");
        } else {
            $("#saveupdatebtntxt").text("Save Payment");
            $("#exportlbpaymentpdf").show();
            $("#savethecalculatedSalary").hide();
            $(".editPresentDays").addClass("d-none");
            $(".payperdayinput").prop("disabled", true).prop("readonly", true);
        }
    });

    $(document).on("click", ".editPresentDays", function () {
        let $tr = $(this).closest("tr");
        let locationid = $("#wageslocationlist").val();
        if (locationid == "all" || !locationid) {
            Swal.fire("Restriction", "Please select a specific location to edit individual records.", "info");
            return;
        }

        $("#EditupdatePresentDaysbtn").data({
            employeeid: $tr.data("employeeid"),
            datefrom: $tr.data("datefrom"),
            dateto: $tr.data("dateto"),
            locationid: $tr.data("locationid")
        });
        
        $("#upPrDay_empname").text($tr.data("employeename"));
        $("#upPrDay_duration").text($tr.data("datefrom") + " To " + $tr.data("dateto"));
        $("#oldPresentDays").val($tr.data("presentdays"));
        $("#IncentiveGrafting").val($tr.data("incentivegrafting"));
        $("#IncentiveSeasional").val($tr.data("incentiveseasional"));
        $("#OvertimeAmount").val($tr.data("overtimepayment"));
        $("#newPresentDays").data('maxlength', calculatedaysbetweendates($tr.data("datefrom"), $tr.data("dateto")));
        $("#updprdays_remark").val('');
        $("#newPresentDays").val($tr.data("presentdays"));
        $("#UpdatePresentDaysModal").modal("show");
    });
    $(document).on("click", "#savethecalculatedSalary", function () {
        let locationid = $("#wageslocationlist").val();
        let totalUnapproved = 0;
        let alreadyCalculatedCount = 0;
        let newLabourCount = 0;
        let alreadyApprovedCount = 0;

        $("#calculatesalry_tbl tbody tr").each(function () {
            let calstatus = $(this).data("calstatus") || "";
            let apprdstatus = $(this).data("apprdstatus") || "";
            let netpayable = parseFloat($(this).find("td.netpayable").text().trim()) || 0;

            if (apprdstatus === "Approved") {
                alreadyApprovedCount++;
            } else if (netpayable > 0) {
                totalUnapproved++;
                if (calstatus === "Calculated") {
                    alreadyCalculatedCount++;
                } else {
                    newLabourCount++;
                }
            }
        });

        if (totalUnapproved === 0) {
            if (alreadyApprovedCount > 0) {
                Swal.fire("Already Approved", "All labour payments for this period have already been approved.", "info");
            } else {
                Swal.fire("No Records", "No labour attendance records found to calculate/save.", "warning");
            }
            return;
        }

        const executeSave = (mode) => {
            let $btn = $("#savethecalculatedSalary");
            let oldText = $("#saveupdatebtntxt").text();
            $btn.prop("disabled", true);
            $("#saveupdatebtntxt").text("Saving...");

            InsertOrUpdateCalculatedSalaryLabour_Payroll('slrycalyear', 'attdaterangelist', 'calculatesalry_tbl', locationid, function (res) {
                $btn.prop("disabled", false);
                $("#saveupdatebtntxt").text(oldText);
                if (Array.isArray(res) && res.length > 0) {
                    Swal.fire({
                        title: "Saved Successfully",
                        text: (mode === 'only_new' ? "New labour wages" : "All labour wages") + " have been successfully committed.",
                        icon: "success"
                    });
                    $("#wageslocationlist").trigger("change");
                } else {
                    Swal.fire("Error", "Could not save calculations. Please check logs.", "error");
                }
            }, mode);
        };

        // Case 1: Both already calculated AND new labours exist
        if (alreadyCalculatedCount > 0 && newLabourCount > 0) {
            Swal.fire({
                title: "Labour Payment Calculation Status",
                html: `
                    <div class="text-start p-2" style="font-size: 0.95rem; color: #1e293b;">
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span><i class="ti ti-users text-primary me-2"></i> Total Labours to Process:</span>
                            <b class="text-dark">${totalUnapproved}</b>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span><i class="ti ti-circle-check text-success me-2"></i> Already Calculated & Saved:</span>
                            <b class="text-success">${alreadyCalculatedCount} Labours</b>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span><i class="ti ti-user-plus text-warning me-2"></i> New Labours for Update/Insert:</span>
                            <b class="text-warning">${newLabourCount} Labours</b>
                        </div>
                        <p class="mt-3 mb-1 text-muted" style="font-size: 0.88rem;">
                            Do you want to update all payments (new and previously calculated) or only insert/update new labours?
                        </p>
                    </div>
                `,
                icon: "question",
                showDenyButton: true,
                showCancelButton: true,
                confirmButtonText: '<i class="ti ti-refresh me-1"></i> Update All (New & Old)',
                denyButtonText: '<i class="ti ti-user-plus me-1"></i> Only New Labours',
                cancelButtonText: "Cancel",
                confirmButtonColor: "#0d6efd",
                denyButtonColor: "#198754",
                cancelButtonColor: "#6c757d"
            }).then((result) => {
                if (result.isConfirmed) {
                    executeSave('all');
                } else if (result.isDenied) {
                    executeSave('only_new');
                }
            });
        }
        // Case 2: Only already calculated records exist (0 new labours)
        else if (alreadyCalculatedCount > 0 && newLabourCount === 0) {
            Swal.fire({
                title: "Recalculate & Update All?",
                html: `
                    <div class="text-start p-2" style="font-size: 0.95rem; color: #1e293b;">
                        <p class="mb-2">All <b>${alreadyCalculatedCount}</b> labour payment records are already calculated and saved for this period.</p>
                        <p class="mb-0 text-muted small">Do you want to re-calculate and update all existing records with the current values?</p>
                    </div>
                `,
                icon: "question",
                showCancelButton: true,
                confirmButtonText: "Yes, Update All",
                cancelButtonText: "Cancel",
                confirmButtonColor: "#0d6efd"
            }).then((result) => {
                if (result.isConfirmed) {
                    executeSave('all');
                }
            });
        }
        // Case 3: All are new labours (0 previously calculated)
        else if (alreadyCalculatedCount === 0 && newLabourCount > 0) {
            Swal.fire({
                title: "Save New Labour Payments?",
                html: `
                    <div class="text-start p-2" style="font-size: 0.95rem; color: #1e293b;">
                        <p class="mb-2">Found <b>${newLabourCount}</b> new labour attendance records ready to calculate and save.</p>
                        <p class="mb-0 text-muted small">Do you want to commit these calculations for approval?</p>
                    </div>
                `,
                icon: "question",
                showCancelButton: true,
                confirmButtonText: "Yes, Save Payments",
                cancelButtonText: "Cancel",
                confirmButtonColor: "#0d6efd"
            }).then((result) => {
                if (result.isConfirmed) {
                    executeSave('all');
                }
            });
        }
    });

    $(document).on("click", "#GotoApprovallabourbagesbtn", function () {
        // Check if there are any uncalculated new labours
        let newLabourCount = 0;
        $("#calculatesalry_tbl tbody tr").each(function () {
            let calstatus = $(this).data("calstatus") || "";
            let apprdstatus = $(this).data("apprdstatus") || "";
            let netpayable = parseFloat($(this).find("td.netpayable").text().trim()) || 0;
            if (apprdstatus !== "Approved" && netpayable > 0 && calstatus !== "Calculated") {
                newLabourCount++;
            }
        });

        if (newLabourCount > 0) {
            Swal.fire({
                title: "Unsaved Labours Found",
                text: "There are " + newLabourCount + " new labour records that have not been saved yet. Please save payments before proceeding to approval.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Save Payment First",
                cancelButtonText: "Cancel"
            }).then((result) => {
                if (result.isConfirmed) {
                    $("#savethecalculatedSalary").trigger("click");
                }
            });
            return;
        }

        // If all calculated, navigate directly without extra confirmation
        window.location.href = "labour_approveSalary.php?k=" + key;
    });

    $("#exportlbpaymentpdf").on("click", function () {
        var datefromdateex = $("#attdaterangelist").val();
        if (datefromdateex) {
            var d = datefromdateex.split("|");
            window.open(`labour_PaymentSheetPDF.php?companyid=${companyid}&subcompanyid=${subcompanyid}&projectid=${projectid}&datefrom=${d[0]}&dateto=${d[1]}&loginname=${loginname}`);
        } else {
            Swal.fire("Missing Range", "Please select a date range first.", "warning");
        }
    });
</script>