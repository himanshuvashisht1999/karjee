<?php
if(isset($_GET['vw'])==1){
    $key=$_GET['k'];
    $rooturl="";
	$mdid ="";
	if(isset($_GET['mdid'])){
		$mdid = $_GET['mdid'];
	}
	$dddr = loginmodulesdetails_frommaster($authconn,$companyid,$subcompanyid,$projectid,"moduleid",$mdid);
	$groupid ="";
	if(count($dddr)>0){
		$groupid = $dddr[0]['groupid'];
	}
}else{
    require('header.php');
}
?>
<!-- Google Fonts for premium typography -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/custom_labour.css">

<div class="main-container p-lg-4 p-3" style="font-family: 'Inter', sans-serif; background: #f8f9fa; min-height: 100vh;">
    
    <!-- Header Section -->
    <div class="row align-items-center mb-4">
        <div class="col-md-7">
            <h2 class="fw-bold text-dark mb-1" style="font-family: 'Outfit', sans-serif; letter-spacing: -0.5px;">Attendance Upload</h2>
            <p class="text-muted small mb-0"><i class="ti ti-upload me-1"></i> Import workforce attendance from machine logs / Excel</p>
        </div>
        <div class="col-md-5 text-md-end mt-3 mt-md-0"></div>
    </div>

    <div class="row justify-content-center">
        <div class="col-xl-10">
            <!-- Main Upload Card -->
            <div class="card border-0 shadow-sm overflow-hidden mb-4" style="border-radius: 16px;">
                <div class="card-header bg-white py-4 border-bottom border-light text-center">
                    <div class="stat-icon bg-soft-primary text-primary p-3 rounded-circle d-inline-flex mb-3">
                        <i class="ti ti-file-spreadsheet fs-30"></i>
                    </div>
                    <h3 class="fw-bold text-dark mb-1">Attendance Machine Import</h3>
                    <p class="text-muted small mb-0">Select location and date range to process the attendance file</p>
                </div>
                <div class="card-body p-lg-5 p-4">
                    <div class="row g-4">
                        <!-- Location Selection -->
                        <div class="col-md-4">
                            <div class="form-group position-relative">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label fw-semibold text-dark small mb-0">Attendance Location</label>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-link p-0 text-primary" id="openLocationModal" title="Add New Location">
                                            <i class="ti ti-plus"></i>
                                        </button>
                                        <button class="btn btn-link p-0 text-danger" id="refreshLocation" title="Refresh List">
                                            <i class="ti ti-refresh"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0"><i class="ti ti-map-pin text-muted"></i></span>
                                    <select class="form-select bg-light border-0" id="attlocation" name="attlocation">
                                        <option value="" disabled selected>Loading locations...</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Date From -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label fw-semibold text-dark small mb-2">From Date</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0"><i class="ti ti-calendar text-muted"></i></span>
                                    <input type="date" name="attdatefrom" id="attdatefrom" 
                                           min="<?php echo date('Y-m-d', strtotime('-60 days')); ?>" 
                                           class="form-control bg-light border-0" />
                                </div>
                            </div>
                        </div>

                        <!-- Date To -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label fw-semibold text-dark small mb-2">To Date</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0"><i class="ti ti-calendar text-muted"></i></span>
                                    <input type="date" name="attdateto" id="attdateto" 
                                           class="form-control bg-light border-0" />
                                </div>
                            </div>
                        </div>

                        <!-- File Upload Section -->
                        <div class="col-12 mt-5">
                            <div class="upload-zone p-5 text-center border-dashed rounded-4 bg-light position-relative">
                                <input type="file" name="laboursalaryfile" id="laboursalaryfile" accept=".xls,.xlsx" 
                                       class="position-absolute w-100 h-100 opacity-0 top-0 start-0" style="cursor: pointer;">
                                <div class="upload-content">
                                    <div class="icon-box bg-white shadow-sm rounded-circle p-3 d-inline-flex mb-3">
                                        <i class="ti ti-cloud-upload fs-40 text-primary"></i>
                                    </div>
                                    <h5 class="fw-bold text-dark">Click or Drag Excel File Here</h5>
                                    <p class="text-muted small mb-0" id="file-name-display">Supported formats: .xls, .xlsx (Machine log format)</p>
                                </div>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <div class="col-12 text-center mt-4">
                            <button type="button" class="btn btn-primary btn-lg shadow px-5 py-3 rounded-pill fw-bold d-inline-flex align-items-center gap-2" 
                                    id="labourattsheetdata" 
                                    onclick="UploadLabourAttenanceSheet('attlocation', 'attdatefrom', 'attdateto', 'laboursalaryfile', 'labourattsheetdata')">
                                <i class="ti ti-upload fs-20"></i> Start Processing Sheet
                            </button>
                            <div class="mt-3">
                                <button class="btn btn-soft-danger d-none rounded-pill px-4" id="MissingPerDayPaymentbtn">
                                    <i class="ti ti-alert-triangle me-1"></i> Update Missing Wages
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Helpful Information -->
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3"><i class="ti ti-info-circle text-primary me-2"></i> Important Note</h6>
                            <ul class="text-muted small mb-0 ps-3">
                                <li class="mb-2">Ensure the Excel sheet contains column headers matching the attendance machine export format.</li>
                                <li class="mb-2">Selected date range should match the records present in the file.</li>
                                <li>Duplicate entries for the same location and date will trigger a warning.</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3"><i class="ti ti-help text-success me-2"></i> Support</h6>
                            <p class="text-muted small mb-0">Facing issues with the file format? Download the sample template or contact the HR IT helpdesk for assistance.</p>
                            <a href="#" class="btn btn-link text-success p-0 mt-2 small fw-bold">Download Template <i class="ti ti-download"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modern Modals -->
<div class="modal fade" id="locationAddModal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 p-4 pb-0">
                <h4 class="fw-bold text-dark mb-0">Add New Location</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="form-group mb-3">
                    <label class="form-label small fw-bold text-muted">Location Name</label>
                    <input type="text" class="form-control bg-light border-0 py-2" name="locationName" id="locationName" placeholder="e.g. Warehouse 1" required />
                </div>
                <div class="form-group mb-3">
                    <label class="form-label small fw-bold text-muted">Location Short Name</label>
                    <input type="text" class="form-control bg-light border-0 py-2" name="locationShortName" id="locationShortName" placeholder="e.g. WH1" required />
                </div>
                <div class="form-group mb-3">
                    <label class="form-label small fw-bold text-muted">Location Address</label>
                    <input type="text" class="form-control bg-light border-0 py-2" name="locationAddress" id="locationAddress" placeholder="Full address details..." />
                </div>
                <div class="form-group mb-4">
                    <label class="form-label small fw-bold text-muted">Remark</label>
                    <textarea class="form-control bg-light border-0" name="remark" id="remark" rows="2" placeholder="Any additional notes..."></textarea>
                </div>
                <button class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow-sm" data-locationid="" id="savetolocationbtn" 
                        onclick="saveLocationJs('locationName', 'locationAddress','remark', 'savetolocationbtn');">
                        Create Location
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Missing Payment Modal -->
<div class="modal fade" id="MissingPerDayPaymentmodal" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-bottom border-light p-4">
                <div>
                    <h4 class="fw-bold text-dark mb-1">Missing Daily Wages</h4>
                    <p class="text-muted small mb-0">These labours are active but missing their per-day payment rates.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row align-items-end mb-4 g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted">Update Mode</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="selectionradio" id="mode-ind" value="individual" checked>
                            <label class="btn btn-outline-primary" for="mode-ind">Individual</label>
                            
                            <input type="radio" class="btn-check" name="selectionradio" id="mode-multi" value="multiple">
                            <label class="btn btn-outline-primary" for="mode-multi">Bulk Update</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div id="bulk-input-container" class="d-none">
                            <label class="form-label small fw-bold text-muted">Bulk Wage Rate</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0">₹</span>
                                <input type="text" class="form-control bg-light border-0" id="payperdayinput" placeholder="Apply to all selected" />
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="input-group search-box-premium">
                            <span class="input-group-text bg-light border-0"><i class="ti ti-search text-muted"></i></span>
                            <input type="search" class="form-control bg-light border-0 ps-0" placeholder="Filter labours..." id="searchMissingPaytbl" 
                                   oninput="filterTable('#searchMissingPaytbl', '#MissingPerDayPaymenttbl');">
                        </div>
                    </div>
                </div>

                <div class="table-responsive" style="max-height: 50vh; border-radius: 12px; border: 1px solid #f1f1f1;">
                    <table class="table table-hover align-middle mb-0" id="MissingPerDayPaymenttbl">
                        <thead class="bg-light sticky-top">
                            <tr>
                                <th class="multiple d-none ps-4"><input type="checkbox" class="form-check-input" id="mnsltlabourslry"></th>
                                <th class="ps-4">Labour Details</th>
                                <th>Daily Wage Rate (₹)</th>
                                <th class="individual pe-4 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0 p-4">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-primary rounded-pill px-5 fw-bold d-none shadow" id="updatemultipleentry">
                    Update Selected Labours
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-soft-primary { background-color: #eef2ff; }
    .border-dashed { border: 2px dashed #dee2e6; }
    .upload-zone { transition: all 0.3s ease; }
    .upload-zone:hover { border-color: #0d6efd; background-color: #f0f7ff !important; }
    .btn-soft-danger { background-color: #fef2f2; color: #dc3545; }
    .btn-soft-danger:hover { background-color: #fee2e2; }
    
    .search-box-premium {
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s ease;
        box-shadow: 0 0 0 1px rgba(0,0,0,0.05);
    }
</style>

<?php
if(isset($_GET['vw'])==1){
}else{
  require('footer.php'); 
}
?>

<script>
// Handle file name display
$("#laboursalaryfile").on("change", function() {
    var fileName = $(this).val().split('\\').pop();
    if(fileName) {
        $("#file-name-display").html('<strong class="text-primary"><i class="ti ti-file-check"></i> ' + fileName + '</strong>');
        $(".upload-zone").addClass("bg-soft-primary").removeClass("bg-light");
    }
});

// UI Overrides for the existing logic
$(document).on("click", "input[name='selectionradio']", function(){
    let selecteraioval = $(this).val();
    if(selecteraioval=="individual"){
        $(".multiple").addClass("d-none");
        $(".individual").removeClass("d-none");
        $("#updatemultipleentry").addClass("d-none");
        $("#bulk-input-container").addClass("d-none");
    } else {
        $(".multiple").removeClass("d-none");
        $(".individual").addClass("d-none");
        $("#updatemultipleentry").removeClass("d-none");
        $("#bulk-input-container").removeClass("d-none");
    }
});

// Initialize existing logic variables
let companyid =  $("body").data("companyid");
let subcompanyid =  $("body").data("subcompanyid");
let projectid =  $("body").data("projectid");
var routurl = $("body").data("routurl");
var mdid = "<?php echo $mdid; ?>";

$(document).ready(function () {
    LabourAttendanceLocationList("all","all",function(res){
        var lctlist="";
        $("#attlocation").empty();
        if(res.length>0){
            if(res.length>1){
                lctlist +="<option value='' selected disabled>Choose Location</option>";
            }
            for (var i = 0; i < res.length; i++) {
                lctlist +="<option value='"+res[i].LocationId+"'>"+res[i].LocationName+"</option>";
            }
        }
        if(lctlist){
            $("#attlocation").append(lctlist);
        } else {
            $("#attlocation").append("<option value='' disabled>No locations found</option>");
        }
    });

    $("#refreshLocation").on("click", function() {
        $(this).addClass("fa-spin");
        LabourAttendanceLocationList("all","all",(res) => {
            $(this).removeClass("fa-spin");
            var lctlist="";
            $("#attlocation").empty();
            if(res.length>0){
                if(res.length>1) lctlist +="<option value='' selected disabled>Choose Location</option>";
                for (var i = 0; i < res.length; i++) {
                    lctlist +="<option value='"+res[i].LocationId+"'>"+res[i].LocationName+"</option>";
                }
            }
            $("#attlocation").append(lctlist || "<option value='' disabled>No locations found</option>");
        });
    });

    $("#openLocationModal").on("click", function(e) {
        e.preventDefault();
        $("#locationAddModal").modal("show");
    });

    $(document).on("change", "#attdateto", function () {
        var locationId = $("#attlocation").val();
        var locationName = $("#attlocation option:selected").text();
        var dateFrom = $("#attdatefrom").val();
        var dateTo = $("#attdateto").val();
        var routurl = $("body").data("routurl");
        var key = $("body").data("key");
        
        if(!locationId || !dateFrom || !dateTo) return;

        CheckUploadedAttendanceWithLocation('attlocation', 'attdatefrom', 'attdateto', function(res){
            if(res.status=="success"){
                Swal.fire({
                  title: "Attendance Overlap Detected",
                  html: "Attendance already updated between <b>" + dateFrom + "</b> and <b>" + dateTo + "</b> for Location <b>" + locationName + "</b>",
                  icon: "warning",
                  showCancelButton: true,
                  showDenyButton: true,
                  confirmButtonColor: "#3085d6",
                  denyButtonColor: "#e74c3c",
                  cancelButtonColor: "#09C1D1",
                  confirmButtonText: "Yes, Upload Another Sheet",
                  denyButtonText: "Remove Existing Records",
                  cancelButtonText: "Go to Approval Page",
                }).then((result) => {
                  if (result.isConfirmed) {
                    location.reload();
                  } else if (result.isDenied) {
                     RemovedAlreadyUploadedLabourAttendance(locationId, locationName, dateFrom, dateTo);
                  } else if (result.dismiss === Swal.DismissReason.cancel) {
                    window.location.href = routurl + "labour_calculateSalary.php?k=" + key + "&mdid=" + mdid;
                  }
                });              
            }
        });
    });

    // Load missing payments
    LabourMasterData("allactivebutperdaypaymentmissing", "all",function(res){
        $("#MissingPerDayPaymenttbl tbody").empty();
        var tblrow = "";
        for (var i = 0; i < res.length; i++) {
            var employeeid = res[i].EmployeeID;
            var employeename = res[i].FullName;
            tblrow += '<tr data-employeeid="' + employeeid + '"><td class="multiple d-none ps-4"><input type="checkbox" class="form-check-input payperdaycheckbox" /></td>' +
                '<td class="ps-4 fw-medium text-dark">' + employeeid + ' - ' + employeename + '</td>' +
                '<td><div class="input-group input-group-sm" style="width: 150px;"><span class="input-group-text bg-light border-0">₹</span><input type="text" class="form-control bg-light border-0 labourPerDayPayment" /></div></td>' +
                '<td class="individual text-end pe-4"><button type="button" class="btn btn-sm btn-soft-success rounded-pill px-3 updatePerdayPaymentbtn" onclick="UpdateMissingLabourWage(this)">Update</button></td>' +
                '</tr>';
        }

        if (tblrow) {
            $("#MissingPerDayPaymentbtn").removeClass("d-none");
            $("#MissingPerDayPaymentbtn").html('<i class="ti ti-alert-triangle me-1"></i> ' + res.length + " Labours missing wages");
            $("#MissingPerDayPaymenttbl tbody").html(tblrow);
        }
    });
});

$("#MissingPerDayPaymentbtn").on("click", function(){
    $("#MissingPerDayPaymentmodal").modal("show");
});

$(document).ready(function () {
     handleMasterCheckboxAll("mnsltlabourslry", "payperdaycheckbox");
});

$(document).on("input", "#payperdayinput", function(){
    let payPerDayInput = $("#payperdayinput").val() ?? 0;
    $(".labourPerDayPayment").each(function(){
        $(this).val(payPerDayInput).trigger("input");
    });
});

function UpdateMissingLabourWage(btn) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var loginname = $("body").data("loginname");

    let row = $(btn).closest("tr");
    let employeeId = row.data("employeeid");
    let perDayPay = row.find("input.labourPerDayPayment").val();

    if (!perDayPay || parseFloat(perDayPay) <= 0) {
        alertmessage('warning', 'Pay Per Day Payment Missing or invalid');
        return false;
    } else if (!employeeId) {
        alertmessage('warning', 'Something wrong, Labour id is missing.');
        return false;
    }
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("loginname", loginname);
    form_data.append("EmployeeId", employeeId);
    form_data.append("PayPerDayAmount", perDayPay);

    $.ajax({
        type: "POST",
        url: baseurl + "modal/hrm_laboursalarymodal.php?action=UpdatePayPerDayPayment",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "JSON",
        success: function (res) {
            if (res.status.toLowerCase() === "success") {
                alertmessage(res.status, res.message);
                row.fadeOut(300, function() {
                    row.remove();
                    let remaining = $("#MissingPerDayPaymenttbl tbody tr").length;
                    if (remaining > 0) {
                        $("#MissingPerDayPaymentbtn").html('<i class="ti ti-alert-triangle me-1"></i> ' + remaining + " Labours missing wages");
                    } else {
                        $("#MissingPerDayPaymentbtn").addClass("d-none");
                        $("#MissingPerDayPaymentmodal").modal("hide");
                    }
                });
            } else {
                alertmessage(res.status, res.message);
            }
        },
        error: function () {
            alertmessage("error", "Something went wrong while updating payment.");
        }
    });
}

$(document).on("click", "#updatemultipleentry", function () {
    let checkedRows = $(".payperdaycheckbox:checked");
    if (checkedRows.length === 0) {
        alertmessage("warning", "No labours selected!");
        return false;
    }

    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var loginname = $("body").data("loginname");

    let excuteentry = 0;
    let completed = 0;

    checkedRows.each(function () {
        let row = $(this).closest("tr");
        let payperdayAmount = $.trim(row.find("input.labourPerDayPayment").val());
        let employeeId = row.data("employeeid");

        if (employeeId && parseFloat(payperdayAmount) > 0) {
            excuteentry++;
            var form_data = new FormData();
            form_data.append("companyid", companyid);
            form_data.append("subcompanyid", subcompanyid);
            form_data.append("projectid", projectid);
            form_data.append("loginname", loginname);
            form_data.append("EmployeeId", employeeId);
            form_data.append("PayPerDayAmount", payperdayAmount);

            $.ajax({
                type: "POST",
                url: baseurl + "modal/hrm_laboursalarymodal.php?action=UpdatePayPerDayPayment",
                data: form_data,
                contentType: false,
                processData: false,
                cache: false,
                dataType: "JSON",
                success: function (res) {
                    completed++;
                    if (res.status.toLowerCase() === "success") {
                        row.fadeOut(300, function() {
                            row.remove();
                            let remaining = $("#MissingPerDayPaymenttbl tbody tr").length;
                            if (remaining > 0) {
                                $("#MissingPerDayPaymentbtn").html('<i class="ti ti-alert-triangle me-1"></i> ' + remaining + " Labours missing wages");
                            } else {
                                $("#MissingPerDayPaymentbtn").addClass("d-none");
                                $("#MissingPerDayPaymentmodal").modal("hide");
                            }
                        });
                    }
                    if (completed === excuteentry) {
                        alertmessage("success", "Selected labours' payment rates updated successfully.");
                    }
                },
                error: function () {
                    completed++;
                    if (completed === excuteentry) {
                        alertmessage("success", "Selected labours' payment rates updated successfully.");
                    }
                }
            });
        }
    });

    if (excuteentry === 0) {
        alertmessage("warning", "Please enter a valid wage rate for the selected labours.");
    }
});
</script>