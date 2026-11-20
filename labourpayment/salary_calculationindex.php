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
<div class="container" id="employee_SalaryCalculationDiv">
    <div class="row mb-2">
    <div class="col-12"></div>
  </div>
    <div class="row">
        <div class="col-md-12">
            <h4 class="text-center">Salary Calcuation</h4>
        </div>
    </div>
  <!-- Card 1: Calculate Attendance -->
  <div class="card mt-4" id="card1">
    <div class="card-header">
      <strong>Manual Attendance Mark</strong><br/>
      <span></span>
    </div>
    <div class="card-body" id="deductioncontainerid">
        <div class="row justify-content-center mt-2">
            <div class="col-md-4 text-center">
                <label for="salarymonth" class="form-label">Salary Month</label>
                <select class="form-select" id="salarymonth">
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
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <a class="btn btn-sm btn-outline-primary float-end" href="<?php echo $rooturl; ?>salary_uploadmanual.php?k=<?php echo $key; ?>"><i class="fas fa-cloud-upload-alt"></i> Upload Attendance Via Excel Sheet</a>
            </div>
            <div class="col-12">
                <div class="table-responsive mt-2">
                    <table class="table table-bordered" id="emplistforcalatt_tbl">
                        <thead>
                            <tr>
                              <th>S.No</th>
                              <!-- <th>Request Status</th> -->
                              <th>Employee Name</th>
                              <th>Employee Salary</th>
                              <th>Days (in Month)</th>
                              <th>No of Holidays</th>
                              <th>No of WeekOff</th>
                              <th>Working Days</th>
                              <th>Calculated Present Days</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12 mt-2 d-flex justify-content-center">
              <button class="btn btn-primary d-none" id="savethecalculatedattendance">
                Save Calculated Attendance
              </button>
            </div>
        </div>
    </div>
</div>

  <!-- Card 2: Calculate Salary -->
  <div class="card mt-4" id="card2" style="display: none;">
    <div class="card-header">
      <strong>Calculate Salary</strong><br/>
      <span></span>
    </div>
    <div class="card-body" id="calculatesalry">
      <div class="row">
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
                  <th>Allowance</th>
                  <th>EPF Deduction</th>
                  <th>ESIC Deduction</th>
                  <th>ITAX Deduction</th>
                  <th>Advance Deduction</th>
                  <th>Other Deduction</th>
                  <th>Net Payable</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>

        <div class="col-12 mt-2 text-center">
          <button class="btn btn-outline-primary" id="savethecalculatedSalary">
            Save Calculated Salary
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Navigation Buttons -->
  <div class="row mt-3">
        <div class="col-6">
            <button type="button" class="btn btn-primary d-none" id="prevButton"
                onclick="navigateCardWithContainerid('prev','prevButton','nextButton','employee_SalaryCalculationDiv')">
                <i class="fa fa-arrow-left me-1" aria-hidden="true"></i> Previous
            </button>
        </div>
        <div class="col-6 text-end">
            <button type="button" class="btn btn-primary" id="nextButton"
                onclick="navigateCardWithContainerid('next','prevButton','nextButton','employee_SalaryCalculationDiv')">
                Next <i class="fa fa-arrow-right ms-1" aria-hidden="true"></i>
            </button>
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
navigateCardWithContainerid('next','prevButton','nextButton','employee_SalaryCalculationDiv');
 
$(document).ready(function () {
    const currentYearMonth = getCurrentYearMonth();
    $("#salarymonth").val(currentYearMonth).trigger("change");
});

$(document).on("change", "#salarymonth", function () {
    // Optional: Show an alert or loader if needed
    // Calculate attendance
    EmplistforCalculateAttendance_htmlcallback('salarymonth', function (res) {
        // console.log(res);
        if (res && res.length > 0) {
            $("#savethecalculatedattendance").removeClass("d-none");
            $("#emplistforcalatt_tbl tbody").html(res);
            // $("#nextButton").trigger("click");
            // navigateCardWithContainerid('next','prevButton','nextButton','employee_SalaryCalculationDiv');
        } else {
            $("#emplistforcalatt_tbl tbody").html(
                "<tr><td colspan='8' class='text-center text-danger'>No employees available for salary calculation.</td></tr>"
            );
            $("#savethecalculatedattendance").addClass("d-none");
        }
    });

    // Calculate salary
    EmplistforCalculateSalary_htmlcallback('salarymonth', function (res) {
        if (res && res.length > 0) {
            $("#calculatesalry_tbl tbody").html(res);
        } else {
            $("#calculatesalry_tbl tbody").html(
                "<tr><td colspan='12' class='text-center text-danger'>No salary data available for selected month.</td></tr>"
            );
        }
    });
});

$(document).on("click","#savethecalculatedattendance", function(){
    InsertOrUpdateCalculatedAttendance_Payroll('salarymonth','emplistforcalatt_tbl', function(res){
        // console.log(res);
        if(res.length>0){
            for (var i = 0; i < res.length; i++) {
                if(res[i].status=="Success" || res[i].status=="success"){
                    alertmessage("success", res[i].message);
                    navigateCardWithContainerid('next','prevButton','nextButton','employee_SalaryCalculationDiv');
                }else{
                    alertmessage("warning", res[i].message);
                }
            }
            // Calculate salary
            EmplistforCalculateSalary_htmlcallback('salarymonth', function (res) {
                if (res && res.length > 0) {
                    $("#calculatesalry_tbl tbody").html(res);
                } else {
                    $("#calculatesalry_tbl tbody").html(
                        "<tr><td colspan='12' class='text-center text-danger'>No salary data available for selected month.</td></tr>"
                    );
                }
            });
        }else{
            alertmessage("warning", "Oops Somthing went wrong!");
        }
    });
})

$(document).on("click", "#savethecalculatedSalary", function(){
    InsertOrUpdateCalculatedSalary_Payroll('salarymonth','calculatesalry_tbl', function(res){
        // console.log(res);
        var rrstmsg = "";
        rrstmsg += "<div class='table-responsive'>";
        rrstmsg += "<table class='table table-bordered'>";
        rrstmsg += "<thead class='table-light'><tr><th>Employee Name</th><th>Message</th></tr></thead><tbody>";

        if (res.length > 0) {
            for (var i = 0; i < res.length; i++) {
                rrstmsg += "<tr><td>" + res[i].employeename + "</td><td>" + res[i].message + "</td></tr>";
            }
        } else {
            rrstmsg += "<tr><td colspan='2' class='text-center'>No data available</td></tr>";
        }

        rrstmsg += "</tbody></table>";
        rrstmsg += "</div>";

        Swal.fire({
          title: "Salary Calculation",
          html: rrstmsg,
          showDenyButton: false,
          showCancelButton: false,
          confirmButtonText: "Refresh",
          // denyButtonText: `Don't save`
        }).then((result) => {
          /* Read more about isConfirmed, isDenied below */
          if (result.isConfirmed) {
            // Swal.fire("Saved!", "", "success");
            location.reload();
          } else if (result.isDenied) {
            Swal.fire("Changes are not saved", "", "info");
          }
        });
    });
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

$(document).on("click", "#DownloadEPfChalantext", function(){
    var salaryyearmonghval = $("#salarymonth").val();
    if(salaryyearmonghval){
        var salaryyearmonghexp = salaryyearmonghval.split("-");
        var salaryYear = salaryyearmonghexp[0];
        var salaryMonth = salaryyearmonghexp[1];
    }
    if(salaryYear && salaryMonth){
        Download_EPFChalanTEXT(salaryYear,salaryMonth);
    }
});

$(document).on("click", "#DownloadEPfChalanexcel", function(){
    var salaryyearmonghval = $("#salarymonth").val();
    if(salaryyearmonghval){
        var salaryyearmonghexp = salaryyearmonghval.split("-");
        var salaryYear = salaryyearmonghexp[0];
        var salaryMonth = salaryyearmonghexp[1];
    }
    if(salaryYear && salaryMonth){
        Download_EPFChalanEXCEL(salaryYear,salaryMonth);
    }
});
</script>