<?php
if(isset($_GET['vw'])==1){
    $key=$_GET['k'];
    $rooturl="";
}else{
  require('header.php');
}
?>
<style>
    .holiday-card {
      width: 100%;
      margin: 10px auto;
      border-radius: 15px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    .table thead th {
      background-color: #0d6efd;
      color: #fff;
      /*text-align: center;*/
    }
</style>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<div class="container mt-2">
    <div class="row mb-2">
        <div class="col-12">
          
        </div>
    </div>
     <div class="card holiday-card">
        <div class="card-header">
          <h4>Working Hours Master</h4>
        </div>
        <div class="card-body">
            <div class="row mt-2">
                <div class="col-md-12">
                    <button class="btn btn-primary btn-sm m-2 float-end" id="applytoworkinghousebtn">+ Apply Office Working Hourse </button>                    
                    <button class="btn btn-outline-primary btn-sm m-2 float-end" id="addworkinghoursebtn">+ Add Office Working Hourse </button>                    
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <h4>Working Hours List</h4>
                        <table class="table table-bordered" id="workingHoursTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Working Hourse Title</th>
                                    <th>In Time</th>
                                    <th>Out Time</th>
                                    <th>Total Hours</th>
                                    <th>Action</th>
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

<!-- Modal -->
<div class="modal fade" id="WorkingHourseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">            
            <div class="modal-header">
                <h5 class="modal-title">Office Working Hours</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="wh_id"> <!-- for update -->
                <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Working Hours</label>
                    <div class="col-sm-9">
                        <input type="text" id="workinghour_name" class="form-control">
                    </div>
                </div>
                <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Start Time</label>
                    <div class="col-sm-9">
                        <input type="time" id="start_time" class="form-control" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">End Time</label>
                    <div class="col-sm-9">
                        <input type="time" id="end_time" class="form-control" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-12 text-center">
                        <button type="button" id="savewhbtn" onclick="saveWorkingHours()" class="btn btn-primary m-2">Save Working Hours</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="ApplytoEmployeeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Apply Working Hours</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="col-md-12 mt-2">
                    <label>Select Working Hours</label>
                    <select id="workinghourTiming" class="form-select"></select>
                </div>
                <div class="col-md-12 mt-2">
                    <label>Working Hourse Apply To</label><br/>
                    <label><input type="radio" name="applytoworkinghouse" class="applytoworkinghouse" value="allemployee" checked /> All Employee</label>
                    <label><input type="radio" name="applytoworkinghouse" class="applytoworkinghouse"  value="oneemployee" /> A Particular Employee</label>
                </div>
                <div class="col-md-12 mt-2" id="applytoempdiv" style="display:none;">
                    <label>Employee Name</label>
                    <select id="AppyToEmployeesId" class="form-select"></select>
                </div>
                <div class="col-md-12 mt-4 text-center">
                    <button class="btn btn-primary" id="applyBtn" onclick="saveApplyWorkingHoursToEmployee()">Apply Working Hourse</button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<?php
if(isset($_GET['vw'])==1){

}else{
  require('footer.php'); 
}
?>
<script type="text/javascript">
$(document).on("click", "#applytoworkinghousebtn", function(){
   $("#ApplytoEmployeeModal").modal("show");
   fetchEmployeeData_callback("allactive","all",function(res){
        $("#AppyToEmployeesId").empty();
        if(res.length>0){
            var emplist='';
            if (res.length > 1) {
                emplist += "<option value='' selected disabled>Select</option>";
            }
            for (var i = 0; i < res.length; i++) {
                emplist += "<option value='" + res[i].EmployeeID + "'>" + res[i].FullName + "</option>";
            }
            if (emplist) {
                $("#AppyToEmployeesId").append(emplist);
            }
        }
   });
    WorkingHoursDataCallback(function(res){
        $("#workinghourTiming").empty();
        if(res.length>0){
            var whlist='';
            if (res.length > 1) {
                whlist += "<option value='' selected disabled>Select</option>";
            }
            for (var i = 0; i < res.length; i++) {
                whlist += "<option value='" + res[i].id + "'>" + res[i].working_hour_name +"("+res[i].start_time+" - "+res[i].end_time +")</option>";
            }
            if (whlist) {
                $("#workinghourTiming").append(whlist);
            }
        }
   });
});
$(document).on("click", "#addworkinghoursebtn", function(){
     $("#wh_id").val('');
    $("#workinghour_name").val('');
    $("#start_time").val('');
    $("#end_time").val('');
   $("#WorkingHourseModal").modal("show");
});

getWorkingHoursData();
$(document).on("click", ".editpwh", function(){
    $("#wh_id").val('');
    $("#workinghour_name").val('');
    $("#start_time").val('');
    $("#end_time").val('');
    let pwhid = $(this).closest("tr").data("dbid");
    let whname = $(this).closest("tr").data("whname");    
    let starttime = $(this).closest("tr").data("starttime");
    let endtime = $(this).closest("tr").data("endtime");
    $("#WorkingHourseModal").modal("show");
    $("#wh_id").val(pwhid);
    $("#workinghour_name").val(whname);
    $("#start_time").val(starttime);
    $("#end_time").val(endtime);
});

$(document).on("click", "input[name='applytoworkinghouse']", function(){
    if($(this).val()=="oneemployee"){
        $("#applytoempdiv").show();
    }else{
        $("#applytoempdiv").hide();
    }
});
</script>
