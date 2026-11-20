<?php
require('../header.php');
?>
  <div class="main-container">
    <div class="row mt-4">
      <div class="col-md-12  text-center">
        <h3 class="uppercase bold">EMPLOYEE MASTER</h3>
      </div>
    </div>
    <div class="row mt-4 d-none">
      <div class="col-md-12 mt-2">
        <a href="uploadattendancedays_manual.php?k=<?php echo $key; ?>" class="btn btn-info d-block" id="uploadattendancedays">UPLOAD EMPLOYEE ATTENDANCE DAYS</a>
      </div>
    </div>
    <div class="row">
      <div class="col-md-12 mt-2 text-right">
        <a href="<?php echo $rooturl; ?>/payroll/employeeResigtration.php?k=<?php echo $key; ?>" class="btn btn-info" id="addemployeebtn"><i class="fa fa-user"></i> Add Employee</a>
      </div>
      <div class="col-md-12 mt-2">
        <div class="table-responsive">
          <table class="table table-bordered table-light table-striped" id="emplistPayrolltbl">
            <thead>
              <th>EMPLOYEE NAME</th>
              <th>EMPLOYEE EMAIL</th>
              <th>CONTACT NO</th>
              <th>DEPARTMENT</th>
              <th>DESIGNATION</th>
              <th>WORK LOCATION</th>
              <th>STATUS</th>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
<?php
require('../footer.php'); 
?>
<script type="text/javascript">
var routurl = $("body").data("routurl");
var key = $("body").data("key");
fetchEmployeeDatawithOtherifno_callback('allactive','allactive',function(res){
  // console.log(res);
  if(res.length>0){
    var emplist=``;
    for (var i = 0; i < res.length; i++) {
      emplist += `<tr>
        <td><a href="${routurl}payroll/employeeProfile.php?k=${key}&empid=${res[i].EmployeeID}">${res[i].FirstName} ${res[i].LastName}</a></td>
        <td>${res[i].Email}</td>
        <td>${res[i].MobileNo}</td>
        <td>${res[i].DepartmentName}</td>
        <td>${res[i].DesignationName}</td>
        <td>${res[i].WorkLocation}</td>
        <td>${res[i].basiccompletestatus}</td>
    </tr>`;

    }
    $("#emplistPayrolltbl tbody").html(emplist);
  }else{
     $("#emplistPayrolltbl tbody").html('<tr><td colspan="6" class="text-center">No data available</td></tr>');
  }
});

$(document).on("click","#addemployeebtn", function(){

})
</script>