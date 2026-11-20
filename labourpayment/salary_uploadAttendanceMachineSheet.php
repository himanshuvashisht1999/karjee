<?php
if(isset($_GET['vw'])==1){
    $key=$_GET['k'];
    $rooturl="";
}else{
    require('header.php');
}
$mdid ="";
if(isset($_GET['mdid'])){
  $mdid = $_GET['mdid'];
}
$dddr = loginmodulesdetails_frommaster($authconn,$companyid,$subcompanyid,$projectid,"moduleid",$mdid);
$groupid ="";
if(count($dddr)>0){
  $groupid = $dddr[0]['groupid'];
}
?>
<div class="container mt-2" id="employee_manualSalaryCalculationDiv">
    <div class="row mb-2">
        <div class="col-12"></div>
    </div>
  <!-- Card 1: Calculate Attendance -->
  <div class="card" id="card1">
    <div class="card-header">
       <a class="btn btn-sm btn-outline-primary float-end" href="<?php echo $rooturl; ?>salary_calculationindex.php?k=<?php echo $key; ?>"><i class="fas fa-calendar-check"></i> Calculate Attendance</a>
      <strong>Upload Attendance Excel Sheet</strong><br/>
      <span></span>
    </div>

    <div class="card-body">
        <div class="row justify-content-center mt-2">
            <div class="col-md-6">
                <i class="fa fa-plus-circle float-end" id="openLocationModal" style="cursor: pointer; font-size: 20px; color: blue;"></i>
                <i class="fa fa-sync text-danger float-end" id="refreshLocation" style="cursor: pointer; font-size: 20px; color: blue; margin-left: 8px; margin-right: 8px;"></i>
                <label for="attlocation" class="form-label">Choose Attendance Location</label>
                <select class="form-select" id="attlocation" name="attlocation"></select>
            </div>
            <div class="col-md-6">
              <label for="uploadsalarymonth" class="form-label">Choose the Salary Month</label>
              <select class="form-select" id="uploadsalarymonth">
                <?php
                    $months = [
                        "January", "February", "March", "April", "May", "June",
                        "July", "August", "September", "October", "November", "December"
                    ];
                    $year = date('Y');
                    foreach ($months as $i => $m) {
                        $monthNumber = str_pad($i + 1, 2, '0', STR_PAD_LEFT); // Pad month with leading zero
                        echo "<option data-salaryyear=\"$year\" value=\"$year-$monthNumber\">$m-$year</option>";
                    }
                ?>
            </select>
            </div>
            <div class="col-md-6">
                <label for="EmpAttendanceFrom" class="form-label">Attendance date from </label>
                <input type="date" name="EmpAttendanceFrom" id="EmpAttendanceFrom" min="<?php echo date('Y-m-d', strtotime('-60 days')); ?>" class="form-control" />
            </div>
            <div class="col-md-6">
                <label for="EmpAttendanceTo" class="form-label">Attendance date to </label>
                <input type="date" name="EmpAttendanceTo" id="EmpAttendanceTo" class="form-control" />
            </div>
        </div>
        <div class="row justify-content-center mt-2">
            
        </div>
        <div class="row mt-4" id="uploadsalarysheetdiv" style="display:none;">
            <div class="col-6">
                <label>Upload Employee Attendance Excel Sheet</label><br/>
                <input type="file" name="employeeattendancefile" id="employeeattendancefile" accept=".xls,.xlsx" required><br/>
                <span>Attendance excel sheet downloaded by attendance machine</span>
            </div>
            <div class="col-6">
                <button type="button"  class="btn btn-primary float-end mt-4" id="employeeattsheetdatabtn" onclick="UploadEmployeeAttenanceMachineExcelSheet('attlocation','uploadsalarymonth','EmpAttendanceFrom','EmpAttendanceTo', 'employeeattendancefile', 'employeeattsheetdatabtn')"><i class="fas fa-upload"></i> Upload </button>
            </div>
        </div>
        <div class="row mt-3 d-none" id="attendanceCalculationdiv">
            <div class="col-12 d-none" id="downloadsalarysheetdiv">
                <div class="table-responsive">
                    <table class="table table-bordered" id="downloadsalarysheettbl">
                      <thead>
                        <tr>
                          <th>Project Name</th>
                          <th id="projectnameth"></th>
                          <th></th>
                          <th></th>
                        </tr>
                        <tr>
                          <th>Salary Month</th>
                          <th id="salarymonthth"></th>
                          <th></th>
                          <th></th>
                        </tr>
                        <tr>
                          <th>Employee ID</th>
                          <th>Employee Name</th>
                          <th>Employee Gross Salary</th>
                          <th>Net Payable Days</th>
                        </tr>
                      </thead>
                      <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
  </div>

  <!-- Card 2: Calculate Salary -->
  <div class="card mt-4" id="card2" style="display: none;">
    <div class="card-header">
      <strong>Calculate Attendance</strong><br/>
      <span></span>
    </div>
    <div class="card-body" id="calculatesalry">
      <div class="row">
        <div class="col-12 mt-2 text-center">
          <button class="btn btn-outline-primary" id="savethecalculatedSalary">
            Save Calculated Salary
          </button>
        </div>
        <div class="col-12">
          <div class="table-responsive">
            <table class="table table-bordered" id="calculatesalry_tbl">
              <thead>
                <tr>
                  <th>S.No</th>
                  <th>Employee Name</th>
                  <th>Calculated Days</th>
                  <th>Basic Salary</th>
                  <th>HRA</th>
                  <th>Conveyance</th>
                  <th>Allowance</th>
                  <th>EPF Deduction</th>
                  <th>ESIC Deduction</th>
                  <th>ITAX Deduction</th>
                  <th>Advance Against Salary</th>
                  <th>Other Deduction</th>
                  <th>Net Payable</th>
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
<div class="modal fade" id="locationAddModal">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center">
                    <h4 class="modal-title me-2" id="">Attendance Location</h4><span></span>
                </div>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <div class="modal-body pb-0 ">

                <div class="row">
                    <div class="col-md-12">
                        <label>Location Name</label>
                        <input type="text" class="form-control" name="locationName" id="locationName" required />
                    </div>
                    <div class="col-md-12">
                        <label>Location Short Name</label>
                        <input type="text" class="form-control" name="locationShortName" id="locationShortName" required />
                    </div>
                    <div class="col-md-12">
                        <label>Location Address</label>
                        <input type="text" class="form-control" name="locationAddress" id="locationAddress" />
                    </div>
                    <div class="col-md-12">
                        <label>Remark</label>
                        <textarea type="text" class="form-control" name="remark" id="remark" rows="2"></textarea>
                    </div>
                </div>
                <div class="row mt-4 mb-4">
                    <div class="col-md-12 text-center">
                        <!-- data-locationid="" → if empty = add new, if set = update -->
                        <button class="btn btn-outline-primary" data-locationid="" id="savetolocationbtn" onclick="saveLocationJs('locationName', 'locationAddress','remark', 'savetolocationbtn');">Save Location</button>
                    </div>
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
<script>
// Initialize by showing the first card
let companyid =  $("body").data("companyid");
let subcompanyid =  $("body").data("subcompanyid");
let projectid =  $("body").data("projectid");
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
    }
});

$("#refreshLocation").on("click", function() {
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
        }
    });
});

$("#openLocationModal").on("click", function() {
    $("#locationAddModal").modal("show");
});

$(document).ready(function () {
    const currentYearMonth = getCurrentYearMonth();
    $("#uploadsalarymonth").val(currentYearMonth).trigger("change");
});

// 
$(document).on("change", "#uploadsalarymonth", function () {
    let yearMonthVal = $(this).val()?.trim();
    let yearMonthValExp = yearMonthVal.split("-") || [];
    let attYearVal = yearMonthValExp[0] || "";
    let attMonthVal = yearMonthValExp[1] || "";
    autofillDatesFromMonthYear(attYearVal, attMonthVal, 'EmpAttendanceFrom', 'EmpAttendanceTo');

    fetchEmployeeDatawithOtherifno_callback('allactive', 'allactive', function (res) {
        // Get the salary month from a dedicated input (fix this if needed)
        var salaryMonth = $("#uploadsalarymonth option:selected").text(); // ensure this input exists
        if (!salaryMonth) {
            alertmessage("warning","Please select a salary month.");
            return;
        }
        $("#downloadsalarysheettbl tbody").empty();

        // Update project name and salary month display
        $("#projectnameth").html($("body").data("projectname") || "N/A");
        $("#salarymonthth").html(salaryMonth);

        var dwemptr = "";
        if (res && res.length > 0) {
            for (var i = 0; i < res.length; i++) {
                const emp = res[i];
                const fullName = [emp.FirstName, emp.MiddleName, emp.LastName].filter(Boolean).join(" ");
                const grossSalary = emp.GrossSalary || 0;

                dwemptr += "<tr>" +
                    "<td>" + emp.EmployeeID + "</td>" +
                    "<td>" + fullName + "</td>" +
                    "<td>" + grossSalary + "</td>" +
                    "<td></td>" + // Amount Payable (editable or calculated later)
                    "</tr>";
            }

            $("#downloadsalarysheettbl tbody").append(dwemptr);
        } else {
            $("#downloadsalarysheettbl tbody").html(
                "<tr><td colspan='4' class='text-center text-danger'>No employees available for salary calculation.</td></tr>"
            );
        }
    });

    CalculatedSalaryData('uploadsalarymonth',function(res){
        // console.log("calculated"+res.length);
        let companyid =  $("body").data("companyid");
        let subcompanyid =  $("body").data("subcompanyid");
        let projectid =  $("body").data("projectid");
        var routurl = $("body").data("routurl");
         var key = $("body").data("key");
         var mdid = $("body").data("mdid");
        if(res.length>0){
            EmplistforCalculateSalary_htmlcallback('uploadsalarymonth', function (response) {
                if (response && response.length > 0) {
                    // Reset first
                    $("#savethecalculatedSalary").hide();
                    $("#uploadsalarysheetdiv").hide();
                    $("#card2").show();

                    // Insert table rows
                    $("#calculatesalry_tbl tbody").html(response);

                    // Count uncalculated rows
                    let calcc = 0;
                    $("#calculatesalry_tbl tbody tr").each(function () {
                        let status = $(this).data("calculatedstatus");
                        if (!status) {   // catches "", null, undefined
                            calcc++;
                        }
                    });
                    // Show save button if pending calculations exist
                    if (calcc > 0) {
                        $("#savethecalculatedSalary").show();
                    }else{
                        Swal.fire({
                          title: "Salary has been calculated",
                          text: "Do you got to Salary Approval",
                          icon: "success",
                          showCancelButton: true,
                          confirmButtonColor: "#3085d6",
                          cancelButtonColor: "#09C1D1",
                          confirmButtonText: "Yes, Go to Approval",
                          cancelButtonText: "No, I want stay here",
                        }).then((result) => {
                          if (result.isConfirmed) {
                            window.location.href = routurl + "approveSalary.php?k=" + key + "&mdid=" + mdid;
                          } else {
                            // window.location.reload();
                          }
                        });
                    }
                } else {
                    $("#card2").hide();
                    $("#savethecalculatedSalary").hide();
                    $("#uploadsalarysheetdiv").show();

                    $("#calculatesalry_tbl tbody").html(
                        "<tr><td colspan='12' class='text-center text-danger'>No salary data available for selected month.</td></tr>"
                    );
                }
            });
        }else{
            $("#savethecalculatedSalary").show();
            $("#uploadsalarysheetdiv").show();
            // Calculate salary
            EmplistforCalculateSalary_htmlcallback('uploadsalarymonth', function (response) {
                // console.log(res);
                if (response && response.length > 0) {
                    $("#card2").show();
                    $("#calculatesalry_tbl tbody").html(response);
                } else {
                     $("#card2").hide();
                    $("#calculatesalry_tbl tbody").html(
                        "<tr><td colspan='12' class='text-center text-danger'>No salary data available for selected month.</td></tr>"
                    );
                }
            });
        }
    })
});

$(document).on("click", "#savethecalculatedSalary", function(){
    let companyid =  $("body").data("companyid");
    let subcompanyid =  $("body").data("subcompanyid");
    let projectid =  $("body").data("projectid");
    var routurl = $("body").data("routurl");
    var key = $("body").data("key");
    var mdid = $("body").data("mdid");
    let trlenghtsltbl = $("#calculatesalry_tbl tbody tr").length;
    var yearmonth = $("#uploadsalarymonth").val();
    InsertOrUpdateCalculatedSalary_Payroll('uploadsalarymonth','calculatesalry_tbl', function(res){
        let sccsscount=0;
        if (res.length > 0) {
            for (var i = 0; i < res.length; i++) {
                if(res[i].status=="success" || res[i].status=="Success"){
                    sccsscount++;
                }
            }
        }
        if(sccsscount==trlenghtsltbl){
            rrstmsg = trlenghtsltbl+" Employee Salary Saved Successfully";
        }else{
            let restemp = sccsscount - trlenghtsltbl;
            rrstmsg = trlenghtsltbl+" Employee Salary Saved Successfully and "+restemp+" Employee salary did not saved";
        }
        Swal.fire({
          title: "Salary Saved, Do You Approved Now",
          text: rrstmsg,
          icon: "success",
          showCancelButton: true,
          confirmButtonColor: "#3085d6",
          cancelButtonColor: "#09C1D1",
          confirmButtonText: "Yes, Go to Approval",
          cancelButtonText: "No, I want stay here",
        }).then((result) => {
          if (result.isConfirmed) {
            window.location.href = routurl + "approveSalary.php?k=" + key + "&yearmonth="+yearmonth+"&mdid=" + mdid;
          } else {
            window.location.reload();
          }
        });
    })
});

$(document).on("input", "td.advdeduction", function() {
    let newadvdeduction = parseFloat($(this).text()) || 0;
    let netpayableamt = parseFloat($(this).closest("tr").data("netpayableamount")) || 0;
    if(netpayableamt>=newadvdeduction){
        let finalamt = netpayableamt - newadvdeduction;
        $(this).closest("tr").find("td.netpayable").text(finalamt.toFixed(2));
    }else{
        Swal.fire("Deduction amount cannot exceed the net payable amount.");
        $(this).text(0)
        let finalamt = netpayableamt - 0;
        $(this).closest("tr").find("td.netpayable").text(finalamt.toFixed(2));
    }
});

$(document).on("click", "#DownloadSalarySheet", function(){
    var salaryyearmonghval = $("#salarymonth").val();
    if(salaryyearmonghval){
        var salaryyearmonghexp = salaryyearmonghval.split("-");
        var salaryYear = salaryyearmonghexp[0];
        var salaryMonth = salaryyearmonghexp[1];
    }
    if(salaryYear && salaryMonth){
        Download_EmployeeSalarySheet(salaryYear,salaryMonth);
    }

});
</script>