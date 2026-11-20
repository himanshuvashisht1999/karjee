<?php
if(isset($_GET['vw'])==1){

}else{
  require('header.php');
}
$empid ="";
if(isset($_GET['empid'])){
    $empid = $_GET['empid'];
}

?>
<style>
    .profile-pic {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
    }
    .nav-tabs {
        flex-direction: column;
        border-right: 2px solid #dee2e6;
    }
    .nav-tabs .nav-link {
        text-align: left;
    }
</style>
<div class="container mt-4">
    <div class="card">
        <div class="card-header " style="background-color: #004953;">
            <h4>Employee Profile</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <!-- Left Side (Profile & Tabs) -->
                <div class="col-md-3">
                    <div class="text-center mb-3">
                        <img src="../assets/img/user.jpg" alt="Profile Picture" id="EmpProfilePic" class="profile-pic">
                        <h5 class="mt-3" id="pr_empname"></h5>
                        <p id="pr_empdesignation"></p>
                    </div>
                    <ul class="nav nav-tabs flex-column border-left" id="profileTabs">
                        <li class="nav-item border-bottom">
                            <a class="nav-link active" data-toggle="tab" href="#basicdetails"><i class="fa fa-user"></i> Employee Details</a>
                        </li>
                        <li class="nav-item border-bottom">
                            <a class="nav-link" data-toggle="tab" href="#salary"><i class="fa fa-cubes"></i> Salary Details</a>
                        </li>
                        <li class="nav-item border-bottom d-none">
                            <a class="nav-link" data-toggle="tab" href="#attendance">Attendance Details</a>
                        </li>
                        <li class="nav-item border-bottom d-none">
                            <a class="nav-link" data-toggle="tab" href="#leave">Leave Details</a>
                        </li>
                        <li class="nav-item border-bottom">
                            <a class="nav-link" data-toggle="tab" href="#holidays"><i class="fa fa-calendar"></i> Holidays Details</a>
                        </li>
                    </ul>
                </div>

                <!-- Right Side (Content) -->
                <div class="col-md-9">
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="basicdetails">
                            <div class="row">
                                <div class="col-sm">
                                    <div class="card mt-2">
                                        <div class="card-header">Basic Information</div>
                                        <div class="card-body">
                                            <div id="basicinformationdiv"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm mt-2">
                                    <div class="card">
                                        <div class="card-header">Personal Information</div>
                                        <div class="card-body">
                                            <div id="personalinformationdiv"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm mt-2">
                                    <div class="card">
                                        <div class="card-header">Bank Information</div>
                                        <div class="card-body">
                                            <div id="bankinformationdiv"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm mt-2">
                                    <div class="card">
                                        <div class="card-header">Salary Information</div>
                                        <div class="card-body">
                                            <div id="salaryinformationdiv"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="salary">
                            <div class="row">
                                <div class="col-sm-12">
                                    <h5><i class="fa fa-cubes"></i> Salary Details</h5>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-9"></div>
                                <div class="col-sm-3">
                                    <label>Salary Year</label>
                                    <select class="form-control" id="salaryyearlist"></select>
                                </div>
                                <div class="col-sm-12 mt-2">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="empsalarytbl">
                                            <thead>
                                                <tr>
                                                    <th>S.No</th>
                                                    <th>Salary Month</th>
                                                    <th>Gross</th>
                                                    <th>Basic</th>
                                                    <th>HRA</th>
                                                    <th>Allowance</th>
                                                    <th>Deductions</th>
                                                    <th>Net Paid</th>
                                                    <th>Pay Slip</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="attendance">
                            <h5>Attendance Details</h5>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Month</th>
                                        <th>Present</th>
                                        <th>Absent</th>
                                        <th>Late</th>
                                        <th>Early Leave</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>January</td>
                                        <td>22</td>
                                        <td>2</td>
                                        <td>1</td>
                                        <td>0</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="tab-pane fade" id="leave">
                            <h5>Leave Details</h5>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Days</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Casual Leave</td>
                                        <td>3</td>
                                        <td>Approved</td>
                                    </tr>
                                    <tr>
                                        <td>Sick Leave</td>
                                        <td>2</td>
                                        <td>Pending</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="tab-pane fade" id="holidays">
                            <div class="row">
                                <div class="col-sm-12">
                                    <h5><i class="fa fa-calendar"></i> Holi Days Details</h5>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="holidaysvietbl">
                                            <thead>
                                                <tr>
                                                    <th>S.No</th>
                                                    <th>Holi Days Date</th>
                                                    <th>Holi Days Name</th>
                                                    <th>Holi Days Type</th>
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
            </div> <!-- End Row -->
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
var EmpId = "<?php echo $empid; ?>";
var currectyear = "<?php echo date('Y'); ?>";

fetchEmployeeDatawithOtherifno_callback('EmployeeID',EmpId,function(res){
    var basichtml="";
    var personalhtml="";
    if(res.length>0){
        for (var i = 0; i < res.length; i++) {
            $("#pr_empname").html(res[i].TitleName+" "+res[i].FirstName+" "+res[i].MiddleName+" "+res[i].LastName);
            $("#pr_empdesignation").html(res[i].DesignationName);
            basichtml += "<p><i class='fa fa-user' data-toggle='tooltip' title='Employee Name' style='cursor:pointer;'></i> "+res[i].TitleName+" "+res[i].FirstName+" "+res[i].MiddleName+" "+res[i].LastName+"</p>";
            
            basichtml += "<p><i class='fa fa-envelope' data-toggle='tooltip' title='Email Address' style='cursor:pointer;'></i> "+(res[i].Email!=""?res[i].Email:"N/A")+"</p>";

            basichtml += "<p><i class='fa fa-mobile' data-toggle='tooltip' title='Mobile No' style='cursor:pointer;'></i> "+ (res[i].MobileNo!=""?res[i].MobileNo:"N/A")+"</p>";
            
            basichtml += "<p><i class='fa fa-user' data-toggle='tooltip' title='Gender' style='cursor:pointer;'></i> "+res[i].Gender+"</p>";

            basichtml += "<p><i class='fa fa-calendar' data-toggle='tooltip' title='Gender' style='cursor:pointer;'></i> " + 
             (res[i].DateOfJoining !== "" ? changeDateFormatYmd_dMY(res[i].DateOfJoining) : "N/A") + 
             "</p>";
            
            basichtml += "<p><i class='fa fa-tag' data-toggle='tooltip' title='Department' style='cursor:pointer;'></i> "+res[i].DepartmentName+"</p>";
            
            basichtml += "<p><i class='fa fa-map-marker' data-toggle='tooltip' title='Work Location' style='cursor:pointer;'></i> "+(res[i].WorkLocation!=""?res[i].WorkLocation:'N/A')+"</p>";
            personalhtml += "<p><i class='fa fa-calendar' data-toggle='tooltip' title='Date of Birth' style='cursor:pointer;'></i> <b>Date of Birth -</b> "+(res[i].DateOfBirth!=""?changeDateFormatYmd_dMY(res[i].DateOfBirth):'N/A')+"</p>";
            personalhtml += "<p><i class='fa fa-user' data-toggle='tooltip' title='Father Name' style='cursor:pointer;'></i> <b>Father Name -</b> "+res[i].FatherName+"</p>";
            personalhtml += "<p><i class='fa fa-id-card' data-toggle='tooltip' title='Pan Number' style='cursor:pointer;'></i> <b>Pan Number -</b> "+res[i].PanNumber+"</p>";
            personalhtml += "<p><i class='fa fa-id-card' data-toggle='tooltip' title='Aadhar Number' style='cursor:pointer;'></i> <b>Aadhar Number -</b> "+res[i].AadharNumber+"</p>";
            personalhtml += "<p><i class='fa fa-address-card' data-toggle='tooltip' title='Address' style='cursor:pointer;'></i> <b>Address -</b> "+res[i].AddressLine1+"</p>";

        }
        $("#basicinformationdiv").html(basichtml);
        $("#personalinformationdiv").html(personalhtml);        
    }

});

fetchEmployeeBankDetails_callback(EmpId,function(res){
    var empBankhtml="";
    if(res.length>0){
        for (var i = 0; i < res.length; i++) {
            if(res[i].BankName){
                empBankhtml += "<p><i class='fa fa-university' data-toggle='tooltip' title='Bank Name' style='cursor:pointer;'></i> <b>Bank Name -</b> "+res[i].BankName+"</p>";
            }
            if(res[i].AccountNumber){
                empBankhtml += "<p><i class='fa fa-balance-scale' data-toggle='tooltip' title='Account Number' style='cursor:pointer;'></i> <b>Account Number -</b> "+res[i].AccountNumber+"</p>";
            }
            if(res[i].IFSCCode){
                empBankhtml += "<p><i class='fa fa-key' data-toggle='tooltip' title='IFSC Code' style='cursor:pointer;'></i> <b>IFSC Code -</b> "+res[i].IFSCCode+"</p>";
            }
            if(res[i].SWIFTCode){
                empBankhtml += "<p><i class='fa fa-codepen' data-toggle='tooltip' title='SWIFT Code' style='cursor:pointer;'></i> <b>SWIFT Code -</b> "+res[i].SWIFTCode+"</p>";
            }
            if(res[i].IBAN){
            empBankhtml += "<p><i class='fa fa-codepen' data-toggle='tooltip' title='IBAN' style='cursor:pointer;'></i> <b>IBAN -</b> "+res[i].IBAN+"</p>";
            }
            if(res[i].SortCode){
                empBankhtml += "<p><i class='fa fa-random' data-toggle='tooltip' title='Sort Code' style='cursor:pointer;'></i> <b>Sort Code -</b> "+res[i].SortCode+"</p>";
            }
        }
        $("#bankinformationdiv").html(empBankhtml);
    }
});

fetchEmployeeSalaryFromMaster_callback(EmpId,function(res){
    var empSalaryhtml="";
    let loginroleid = parseInt($("body").data("loginroleid")) || 0;
    if(res && res.length>0){
        for (var i = 0; i < res.length; i++) {
            let s = res[i];
            let netSalaryVal = (s.NetSalary && parseFloat(s.NetSalary) > 0) ? s.NetSalary : (s.GrossSalary || "0.00");

            if (loginroleid <= 2) {
                if(s.CTC){
                    empSalaryhtml += "<p><i class='fa fa-object-group' data-toggle='tooltip' title='CTC' style='cursor:pointer;'></i> <b>CTC -</b> "+s.CTC+"</p>";
                }
                if(s.GrossSalary){
                    empSalaryhtml += "<p><i class='fa fa-object-group' data-toggle='tooltip' title='Gross Salary' style='cursor:pointer;'></i> <b>Gross Salary -</b> "+s.GrossSalary+"</p>";
                }
                if(s.BasicSalary){
                    empSalaryhtml += "<p><i class='fa fa-id-badge' data-toggle='tooltip' title='Basic Salary' style='cursor:pointer;'></i> <b>Basic Salary -</b> "+s.BasicSalary+"</p>";
                }
                if(s.HRA){
                    empSalaryhtml += "<p><i class='fa fa-id-badge' data-toggle='tooltip' title='HRA' style='cursor:pointer;'></i> <b>HRA -</b> "+s.HRA+"</p>";
                }
                if(s.Allowances){
                    empSalaryhtml += "<p><i class='fa fa-id-badge' data-toggle='tooltip' title='Allowances' style='cursor:pointer;'></i> <b>Allowances -</b> "+s.Allowances+"</p>";
                }
                empSalaryhtml += "<p><i class='fa fa-credit-card' data-toggle='tooltip' title='Net Salary' style='cursor:pointer;'></i> <b>Net Salary -</b> "+netSalaryVal+"</p>";
            } else {
                empSalaryhtml += "<p><i class='fa fa-credit-card' data-toggle='tooltip' title='Net Salary' style='cursor:pointer;'></i> <b>Net Salary -</b> "+netSalaryVal+"</p>";
            }
        }
        $("#salaryinformationdiv").html(empSalaryhtml);
    }
});

fetchHolidayFromMaster_callback(currectyear, function(res) {    
    var hldlist = "";
    if (res && res.length > 0) {

        for (var i = 0; i < res.length; i++) {
            hldlist += `<tr>
                <td>${i + 1}</td>
                <td>${changeDateFormatYmd_dMY(res[i].HolidayDate)}</td>
                <td>${res[i].HolidayName}</td>
                <td>${res[i].HolidayType}</td>
            </tr>`;
        }
        if (hldlist) {
            $("#holidaysvietbl tbody").append(hldlist);
        }
    }
});

SalaryYear_callback(function(res){
    var syear="";
    if(res.length>0){
        if(res.length>1){
            syear +="<option value='' selected disabled>Select Year</option>";
        }
        for (var i = 0; i < res.length; i++) {
            syear +="<option value='"+res[i]+"'>"+res[i]+"</option>";
        }
        $("#salaryyearlist").append(syear);
    }
});

EmployeeCalculatedSalary_callback(currectyear,'employeeid',EmpId,function(res){
    // console.log(res);
    var sslhtml="";
    if(res.length>0){
        var j=1;
        for (var i = 0; i < res.length; i++) {
            var salaryYear = res[i].SalaryYear; // Example: 2025
            var salaryMonth = res[i].SalaryMonth; // Example: 1 (January)
            // Array of month abbreviations
            var monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

            // Convert month number to abbreviation
            var formattedDate = monthNames[salaryMonth - 1] + '-' + salaryYear;
            sslhtml += `<tr data-slid='${res[i].SalaryID}'><td>${j}</td><td>${formattedDate}</td><td>${res[i].GrossSalary}</td><td>${res[i].BasicSalary}</td><td>${res[i].HRA}</td><td>${res[i].Allowances}</td><td>${res[i].Deductions}</td><td>${res[i].NetSalary}</td><td class="downloadPaySlip" style="cursor:pointer" onclick="EmployeeSalarySlipCreat(${res[i].SalaryID});"><i class="fa fa-download" style="color:red; font-size:20px;"></i></td></tr>`;
            j++;
        }
        $("#empsalarytbl tbody").html(sslhtml);
    }
});
</script>