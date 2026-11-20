<?php
if(isset($_GET['vw'])==1){

}else{
  require('header.php');
}
$empid ="";
$employeename = "";
$DateOfJoining = "";
$EmploymentTypeid = "";
$DesignationID = "";
$DepartmentID = "";
$MarkInTime = "";
$MarkOutTime = "";
$FatherName = $MobileNo = $BankName = $AccountNumber = $IFSCCode = $EpfRegId = $EsicRegId = "";
$NetSalaryVal = ""; $SalaryBreakup = ""; $hasSalaryRow = false;
if(isset($_GET['empid'])){
    $empid = $_GET['empid'];
    $empinfo=fetchEmployeeData($hrmconn, $companyid, $subcompanyid, $projectid, "EmployeeID", $empid);
    $employeename = str_replace("  ", " ", $empinfo[0]['FirstName'].' '.$empinfo[0]['MiddleName'].' '.$empinfo[0]['LastName']);
    $DateOfJoining = $empinfo[0]['DateOfJoining'];
    $EmploymentTypeid = $empinfo[0]['EmploymentTypeid'];
    $DesignationID = $empinfo[0]['DesignationID'];
    $DepartmentID = $empinfo[0]['DepartmentID'];
    $MarkInTime = $empinfo[0]['InTime'];
    $MarkOutTime = $empinfo[0]['OutTime'];
    $WorkHourseId = $empinfo[0]['WorkHourseId'];
    $attendanceType = $empinfo[0]['attendanceType'];
    $WorkLocation = $empinfo[0]['WorkLocation'];
    $FatherName = $empinfo[0]['FatherName'] ?? '';
    $MobileNo = $empinfo[0]['MobileNo'] ?? '';

    // Bank details (latest row)
    $empBank = getEmployeeBankDetails($hrmconn, $companyid, $subcompanyid, $projectid, $empid);
    if (!empty($empBank) && !isset($empBank['error'])) {
        $lastBank = end($empBank);
        $BankName = $lastBank['BankName'] ?? '';
        $AccountNumber = $lastBank['AccountNumber'] ?? '';
        $IFSCCode = $lastBank['IFSCCode'] ?? '';
    }

    // EPF UAN (DeductionId 1) / ESIC IP (DeductionId 3)
    $EpfRegId = getEmployeeDeductionRegIdAny($hrmconn, $companyid, $projectid, $empid, 1);
    $EsicRegId = getEmployeeDeductionRegIdAny($hrmconn, $companyid, $projectid, $empid, 3);

    // Current monthly salary (same row/figure the Employee List shows as Net Salary)
    $salStmt = $hrmconn->prepare("SELECT BasicSalary, HRA, ConveyanceAllowance, SpecialAllowance, NetSalary, GrossSalary FROM hrm_EmployeeSalary
        WHERE EmployeeID = ? AND projectid = ?
        ORDER BY (validtilldate IS NULL OR validtilldate = '' OR validtilldate = '0000-00-00') DESC, SalaryID DESC LIMIT 1");
    if ($salStmt) {
        $salStmt->bind_param("is", $empid, $projectid);
        $salStmt->execute();
        $salRow = $salStmt->get_result()->fetch_assoc();
        $salStmt->close();
        if ($salRow) {
            $hasSalaryRow = true;
            $sumParts = (float)$salRow['BasicSalary'] + (float)$salRow['HRA'] + (float)$salRow['ConveyanceAllowance'] + (float)$salRow['SpecialAllowance'];
            $NetSalaryVal = (float)$salRow['NetSalary'] > 0 ? (float)$salRow['NetSalary'] : ((float)$salRow['GrossSalary'] > 0 ? (float)$salRow['GrossSalary'] : $sumParts);
            $SalaryBreakup = 'Basic ₹' . number_format((float)$salRow['BasicSalary'], 0)
                . ' · HRA ₹' . number_format((float)$salRow['HRA'], 0)
                . ' · Conveyance ₹' . number_format((float)$salRow['ConveyanceAllowance'], 0)
                . ' · Special ₹' . number_format((float)$salRow['SpecialAllowance'], 0);
        }
    }
}
$h = function ($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); };

?>
<div class="container mt-2 mb-4" id="employeeworkcontainer">
    <h4 class="text-center fw-bold" id="headingsalcal">
        <?php echo !empty($employeename) ? "Employee Work Details of " . $employeename : "Employee Work Details"; ?>
    </h4>

    <!-- Card 1: Employee Work Details -->
    <div class="card shadow-sm border-0 mt-3" id="card1">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="fw-bold text-dark mb-0" style="font-size: 16px !important; line-height: 1.5 !important;">
                Employee Work Details
                <i class="fa fa-edit d-none ms-2" id="editempwork" style="cursor:pointer; color:blue; font-size: 18px;"></i>
            </h5>
        </div>

        <div class="card-body p-4" id="empworkdiv">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="StartDate" class="form-label fw-semibold">
                        Start Date (Joining Date)
                        <span class="text-danger">*</span>
                    </label>
                    <input type="date" class="form-control" id="StartDate" name="StartDate" value="<?php echo $DateOfJoining; ?>" />
                </div>
                <div class="col-md-6">
                    <label for="employmentType" class="form-label fw-semibold">
                        Employment Type
                        <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="employmentType" name="employmentType"></select>
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <label for="department" class="form-label fw-semibold d-flex justify-content-between align-items-center">
                        <span>Department <span class="text-danger">*</span></span>
                        <span>
                            <i class="fa fa-plus-circle text-primary me-2 cursor-pointer" onclick="opennewDepartment()" title="Add Department" style="cursor:pointer;"></i>
                            <i class="fa fa-sync text-secondary cursor-pointer" onclick="departmentlist_dropdown('','department');" title="Refresh Department" style="cursor:pointer;"></i>
                        </span>
                    </label>
                    <select class="form-select select2" id="department" name="department"></select>
                </div>
                <div class="col-md-6">
                    <label for="designation" class="form-label fw-semibold d-flex justify-content-between align-items-center">
                        <span>Designation <span class="text-danger">*</span></span>
                        <span>
                            <i class="fa fa-plus-circle text-primary me-2 cursor-pointer" onclick="opennewDesignation()" title="Add Designation" style="cursor:pointer;"></i>
                            <i class="fa fa-sync text-secondary cursor-pointer" onclick="designationlist_dropdwon('','designation');" title="Refresh Designation" style="cursor:pointer;"></i>
                        </span>
                    </label>
                    <select class="form-select select2" id="designation" name="designation"></select>
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <label for="workinghourSelect" class="form-label fw-semibold">Employee Working Timing</label>
                    <select id="workinghourSelect" class="form-select"></select>
                </div>
                <div class="col-md-6">
                    <label for="WorkingLocation" class="form-label fw-semibold">Employee Working Location</label>
                    <select id="WorkingLocation" class="form-select"></select>
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <label for="wrkFatherName" class="form-label fw-semibold"><i class="ti ti-user text-primary me-1"></i>Father's Name</label>
                    <input type="text" class="form-control" id="wrkFatherName" placeholder="Enter Father's Name" value="<?php echo $h($FatherName); ?>">
                </div>
                <div class="col-md-6">
                    <label for="wrkMobileNo" class="form-label fw-semibold"><i class="ti ti-phone text-primary me-1"></i>Mobile Number</label>
                    <input type="text" class="form-control" id="wrkMobileNo" maxlength="15" inputmode="numeric" placeholder="Enter Mobile Number" value="<?php echo $h($MobileNo); ?>">
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <label for="wrkEpfRegId" class="form-label fw-semibold"><i class="ti ti-building-bank text-success me-1"></i>EPF UAN / Registration No. (Deduction ID 1)</label>
                    <input type="text" class="form-control" id="wrkEpfRegId" maxlength="95" placeholder="Enter EPF UAN No." value="<?php echo $h($EpfRegId); ?>">
                    <small class="text-muted">Updates `regid` in `hrm_EmployeeDeductionsComponents` for DeductionID 1</small>
                </div>
                <div class="col-md-6">
                    <label for="wrkEsicRegId" class="form-label fw-semibold"><i class="ti ti-shield-heart text-info me-1"></i>ESIC IP / Registration No. (Deduction ID 3)</label>
                    <input type="text" class="form-control" id="wrkEsicRegId" maxlength="95" placeholder="Enter ESIC IP Registration No." value="<?php echo $h($EsicRegId); ?>">
                    <small class="text-muted">Updates `regid` in `hrm_EmployeeDeductionsComponents` for DeductionID 3</small>
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-md-4">
                    <label for="wrkBankName" class="form-label fw-semibold"><i class="ti ti-building-bank text-primary me-1"></i>Bank Name</label>
                    <input type="text" class="form-control" id="wrkBankName" placeholder="Enter Bank Name" value="<?php echo $h($BankName); ?>">
                </div>
                <div class="col-md-4">
                    <label for="wrkAccountNumber" class="form-label fw-semibold"><i class="ti ti-credit-card text-primary me-1"></i>Account Number</label>
                    <input type="text" class="form-control" id="wrkAccountNumber" inputmode="numeric" placeholder="Enter Account Number" value="<?php echo $h($AccountNumber); ?>">
                </div>
                <div class="col-md-4">
                    <label for="wrkIFSCCode" class="form-label fw-semibold"><i class="ti ti-key text-primary me-1"></i>IFSC Code</label>
                    <input type="text" class="form-control text-uppercase" id="wrkIFSCCode" maxlength="11" placeholder="Enter IFSC Code" value="<?php echo $h($IFSCCode); ?>">
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <label for="wrkNetSalary" class="form-label fw-semibold"><i class="ti ti-currency-rupee text-success me-1"></i>Net Salary (Monthly)</label>
                    <?php if ($hasSalaryRow) { ?>
                        <input type="number" min="1" step="0.01" class="form-control" id="wrkNetSalary"
                               data-original="<?php echo $h($NetSalaryVal); ?>" value="<?php echo $h($NetSalaryVal); ?>" placeholder="Enter monthly salary">
                        <small class="text-muted d-block">Now: <?php echo $h($SalaryBreakup); ?>. A new amount is split the same way (Special Allowance takes the balance).</small>
                    <?php } else { ?>
                        <input type="text" class="form-control" value="Not set up yet" disabled>
                        <small class="text-muted d-block">Set the salary once on the next (Salary) step; after that it can be changed here or from the Employee List.</small>
                    <?php } ?>
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-md-12">
                    <label class="form-label fw-semibold">Employee Week Off</label>
                    <div id="weekoffdays" class="d-flex flex-wrap gap-3 p-3 bg-light rounded border">
                        <?php 
                        $weekdays = [
                            ['dayno' => 1, 'dayname' => 'Monday'],
                            ['dayno' => 2, 'dayname' => 'Tuesday'],
                            ['dayno' => 3, 'dayname' => 'Wednesday'],
                            ['dayno' => 4, 'dayname' => 'Thursday'],
                            ['dayno' => 5, 'dayname' => 'Friday'],
                            ['dayno' => 6, 'dayname' => 'Saturday'],
                            ['dayno' => 7, 'dayname' => 'Sunday']
                        ];

                        foreach ($weekdays as $wkkey) {
                            echo "<div class='form-check form-check-inline me-0'>
                                    <input type='checkbox' value='{$wkkey['dayno']}' id='wk_{$wkkey['dayno']}' class='form-check-input weekoffday cursor-pointer'>
                                    <label for='wk_{$wkkey['dayno']}' class='form-check-label cursor-pointer fw-semibold text-secondary me-3'>{$wkkey['dayname']}</label>
                                  </div>";
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- Navigation / Action Buttons -->
            <div class="row mt-4 pt-2">
                <div class="col-6">
                    <button type="button" class="btn btn-primary px-4 fw-bold" onclick="goToEmployeesList();">
                        <i class="fa fa-arrow-left me-1"></i> Back
                    </button>
                </div>
                <div class="col-6 text-end">
                    <button id="employeeworkdetailupdbtn"
                            data-wrkemployeeid="<?php echo htmlspecialchars($empid, ENT_QUOTES, 'UTF-8'); ?>"
                            class="btn btn-primary px-4 py-2 fw-bold shadow-sm <?php echo empty($empid) ? 'd-none' : ''; ?>">
                        UPDATE WORK DETAILS & NEXT <i class="fa fa-arrow-right ms-1"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
if(isset($_GET['vw'])==1){

}else{
  require('footer.php'); 
}
?>
<script type="text/javascript">
function goToEmployeesList() {
    var urlParams = new URLSearchParams(window.location.search);
    var k = urlParams.get('k') || '';
    var vw = urlParams.get('vw') || '';
    
    var targetUrl = 'employees.php';
    var params = [];
    if (k) params.push('k=' + encodeURIComponent(k));
    if (vw) params.push('vw=' + encodeURIComponent(vw));
    
    if (params.length > 0) {
        targetUrl += '?' + params.join('&');
    }
    
    window.location.href = targetUrl;
}

var alEmploymentTypesId = "<?php echo $EmploymentTypeid; ?>";
var alDesignationID = "<?php echo $DesignationID; ?>";
var alDepartmentID = "<?php echo $DepartmentID; ?>";
var WorkHourseId = "<?php echo $WorkHourseId; ?>";
let EmployeeID = "<?php echo $empid; ?>";
let WorkLocation = "<?php echo $WorkLocation; ?>";

employmenttypelist_callback("","",function(res){
    $("#employmentType").empty();
    if (res && res.length > 0) {
        var emptypelist = "";
        if (res.length > 1) {
            emptypelist += "<option value='' selected disabled>Choose Employee Type</option>";
        }
        for (var i = 0; i < res.length; i++) {
            emptypelist += "<option value='" + res[i].EmploymentTypesId + "'>" + res[i].EmploymentTypesName + "</option>";
        }
        if (emptypelist) {
            $("#employmentType").append(emptypelist);
        }
        if(alEmploymentTypesId){
            $("#employmentType").val(alEmploymentTypesId).trigger("change");
        }
    } else {
        $("#employmentType").append("No data found");
    }
});
departmentlist_dropdown(alDepartmentID,'department');
designationlist_dropdwon(alDesignationID,'designation');
WorkingHoursDataCallback(function(res){
    $("#workinghourSelect").empty();
    if(res.length>0){
        var whlist='';
        if (res.length > 1) {
            whlist += "<option value='' selected disabled>Select</option>";
        }
        for (var i = 0; i < res.length; i++) {
            whlist += "<option value='" + res[i].id + "'>" + res[i].working_hour_name +"("+res[i].start_time+" - "+res[i].end_time +")</option>";
        }
        if (whlist) {
            $("#workinghourSelect").append(whlist);
        }
        if(WorkHourseId){
            $("#workinghourSelect").val(WorkHourseId).trigger("change");
        }
    }
});

// WorkingLocation
AttendanceLocationMaster_callback("all", "all", function (res) {
    let lcoptionhtml = "";
    const $workingLocation = $("#WorkingLocation");
    // Clear dropdown
    $workingLocation.empty();
    if (Array.isArray(res) && res.length > 0) {
        // Default select option
        if (res.length > 1) {
            lcoptionhtml += `<option value="" disabled selected>Select</option>`;
        }
        res.forEach(row => {
            lcoptionhtml += `
                <option value="${row.LocationId}">
                    ${row.LocationName}
                </option>
            `;
        });
        lcoptionhtml += `
            <option value="outoffice">Outside Office</option>
        `;
    } else {
        lcoptionhtml = `
            <option value="" disabled>No locations found</option>
        `;
    }
    // Append options FIRST
    $workingLocation.append(lcoptionhtml);
    // ✅ Set selected value AFTER append
    if (typeof WorkLocation !== "undefined" && WorkLocation) {
        $workingLocation.val(WorkLocation).trigger("change");
    }
});

fetchEmployeeWeekOffData_callback(EmployeeID, function (res) {
    console.log(res);
    // 🔹 First uncheck all days
    $("#weekoffdays .weekoffday").prop("checked", false);
    if (res.status === "success" && Array.isArray(res.data)) {
        res.data.forEach(function (row) {
            let dayNo = row.WeekOffDayNo;
            // ✅ Check matching checkbox
            $("#weekoffdays .weekoffday[value='" + dayNo + "']")
                .prop("checked", true);

        });
    }
});

</script>