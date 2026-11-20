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
    <div class="col-12">
      
    </div>
  </div>

  <!-- Card 1: Calculate Attendance -->
    <div class="card" id="card1">
        <div class="card-header">
          <strong>Employee Salary</strong><br/>
          <span></span>
        </div>
        <div class="card-body">
            <div class="row justify-content-center mt-2">
                <div class="col-md-4 text-center">
                  <label for="uploadsalarymonth" class="form-label">Choose the Salary Month</label>
                  <select class="form-select" id="uploadsalarymonth">
                    
                </select>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-3 mt-2 d-flex exportsheet justify-content-between d-none">
                  <button class="btn btn-primary btn-sm float-start" id="DownloadSalarySheet"><i class="fa fa-download"></i> Employee Salary Sheet</button>
                </div>
                <div class="col-3 mt-2 d-flex exportsheet justify-content-between d-none">
                  <button class="btn btn-primary btn-sm float-start" id="DownloadEPfChalantext"><i class="fa fa-download"></i> EPF Chalan AS TEXT</button>
                </div>
                <div class="col-3 mt-2 d-flex exportsheet justify-content-between d-none">
                  <button class="btn btn-primary btn-sm float-start" id="DownloadEPfChalanexcel"><i class="fa fa-download"></i> EPF Chalan AS EXCEL</button>
                </div>
                <div class="col-3">
                    <!-- <button class="btn btn-sm btn-warning float-end d-none" data-calsalarydata="" id="readytobookdatabtn"></button> -->
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-12 mt-2">
                  <div class="table-responsive">
                    <table class="table table-bordered" id="payslipsalry_tbl">
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
                          <th>Other Deduction</th>
                          <th>Net Payable</th>
                          <th class="noexport">Pay Slip</th>
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
<!-- view ready to create a book of employee salary -->
<div class="modal fade" id="calculatesalryReadytoBookmodal">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center">
                    <h4 class="modal-title me-2" id="">Calculated Salary</h4><span></span>
                </div>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <div class="modal-body pb-0 ">  
                <div class="row">
                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="table" id="calculatesalryReadytoBooktbl">
                                <thead>
                                    <tr>
                                        <th>Select</th>
                                        <th>Employee Name</th>
                                        <th>Net Salary</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="2"></td>
                                        <td class="float-end"><button class="btn btn-primary" id="crtbookfromslrydata" onclick="CreateBookFromCalculatedSalaryData('calculatesalryReadytoBooktbl','crtbookfromslrydata');">Create Book</button></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- <div id="tempContainer"></div> -->
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
EmployeeAttendanceYearMonth_callback("","",function(res){
    var atymlist="";
    $("#uploadsalarymonth").empty();
    if(res.length>0){
        if(res.length>1){
            atymlist +="<option value='' selected disabled>Choose Attendance Month</option>";
        }
        for (var i = 0; i < res.length; i++) {
            atymlist +="<option  data-salaryyear='"+res[i].AttendanceYear+"' value='"+res[i].MonthYear+"'>"+res[i].MonthYeardisp+"</option>";
        }
    }
    if(atymlist){
        $("#uploadsalarymonth").append(atymlist);
    }
});
// $(document).ready(function () {
//     const currentYearMonth = getCurrentYearMonth();
//     $("#uploadsalarymonth").val(currentYearMonth).trigger("change");
// });

// 
$(document).on("change", "#uploadsalarymonth", function () {
    var selectyearmonth = $("#uploadsalarymonth").val();
    var yearmonthtext = $("#uploadsalarymonth option:selected").text();
    if(selectyearmonth){
        var yearmonthexp = selectyearmonth.split("-");
        var yearval = yearmonthexp[0];
        var monthval = yearmonthexp[1];
        $("#payslipsalry_tbl tbody").empty();
        EmployeeSalaryforpayslip(yearval,monthval,'all','all',function(res){
            var sslhtml="";
            var epfesiexist=0;
            if(res.length>0){
                var j=1;
                for (var i = 0; i < res.length; i++) {
                    var salaryYear = res[i].SalaryYear; // Example: 2025
                    var salaryMonth = res[i].SalaryMonth; // Example: 1 (January)
                    // Array of month abbreviations
                    var monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

                    // Convert month number to abbreviation
                    var formattedDate = monthNames[salaryMonth - 1] + '-' + salaryYear;
                    if(res[i].EPFDeduction>0 || res[i].ESICDeduction>0){
                        epfesiexist++;
                    }
                    sslhtml += `<tr data-slid='${res[i].SalaryID}'><td>${j}</td><td>${res[i].EmployeeName}</td><td>${res[i].GrossSalary}</td><td>${res[i].BasicSalary}</td><td>${res[i].HRA}</td><td>${res[i].Conveyance}</td><td>${res[i].Allowances}</td><td>${res[i].EPFDeduction}</td><td>${res[i].ESICDeduction}</td><td>${res[i].IncomeTaxDeduction}</td><td>${res[i].otherDeduction}</td><td>${res[i].NetSalary}</td><td class="downloadPaySlip noexport" style="cursor:pointer" onclick="EmployeeSalarySlipCreat(${res[i].SalaryID});"><i class="fa fa-download" style="color:red; font-size:20px;"></i></td></tr>`;
                    j++;
                }
                if(sslhtml){
                    $("#payslipsalry_tbl tbody").html(sslhtml);
                    $(".exportsheet").removeClass("d-none");
                }else{
                    $("#payslipsalry_tbl tbody").html("<tr><td colspan='12'>No data available for the month of "+yearmonthtext+"</td></tr>");
                }
                if(parseInt(epfesiexist)>0){
                    $("#DownloadEPfChalantext").removeClass("d-none");
                    $("#DownloadEPfChalanexcel").removeClass("d-none");
                }else{
                    $("#DownloadEPfChalantext").addClass("d-none");
                    $("#DownloadEPfChalanexcel").addClass("d-none");
                }
            }else{
                $("#payslipsalry_tbl tbody").html("<tr><td colspan='12' class='text-center'>No data available for the month of "+yearmonthtext+"</td></tr>");
            }
        });

        PendingBookFromCalculatedSalaryData('uploadsalarymonth',function(res){
            // console.log(res);
            // $("#readytobookdatabtn").removeClass("d-none");
            if(res.length>0){
                $("#readytobookdatabtn").removeClass("d-none");
                $("#readytobookdatabtn").html("Ready To Create Book of "+res.length+" Employe");
                $("#readytobookdatabtn").data("calsalarydata", res);
            }
        });
    }    
});

$(document).on("click", "#readytobookdatabtn", function(){
    var salarydata = $("#readytobookdatabtn").data("calsalarydata");
    $("#calculatesalryReadytoBookmodal").modal("show");
    // console.log(salarydata);
    // alert(salarydata.length);
    var rdbooktr = "";
    for (var i = 0; i < salarydata.length; i++) {
        rdbooktr += `
            <tr data-employeeid="${salarydata[i].EmployeeID}" 
                data-salarymonth="${salarydata[i].SalaryMonth}" 
                data-salaryyear="${salarydata[i].SalaryYear}" 
                data-netsalary="${salarydata[i].NetSalary}">
                <td><input type="checkbox" class="slrdbookcheckbox" /></td>
                <td>${salarydata[i].EmployeeName}</td>
                <td>${salarydata[i].NetSalary}</td>
            </tr>`;
    }
    if (rdbooktr) {
        $("#calculatesalryReadytoBooktbl tbody").append(rdbooktr);
    }
});

$(document).on("click", "#DownloadSalarySheet", function(){
    var salaryyearmonghval = $("#uploadsalarymonth").val();
    if(salaryyearmonghval){
        var salaryyearmonghexp = salaryyearmonghval.split("-");
        var salaryYear = salaryyearmonghexp[0];
        var salaryMonth = salaryyearmonghexp[1];
    }
    if(salaryYear && salaryMonth){
        Download_SalaryReportExcel(salaryYear,salaryMonth);
    }

});

$(document).on("click", "#DownloadEPfChalantext", function(){
    var salaryyearmonghval = $("#uploadsalarymonth").val();
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
    var salaryyearmonghval = $("#uploadsalarymonth").val();
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