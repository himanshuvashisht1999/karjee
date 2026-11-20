<?php
if(isset($_GET['vw'])==1){

}else{
  require('../header.php');
}
?>
<div class="container mt-2 mb-4" id="leavemastercontainer">
    <div class="card">
        <div class="card-body">
            <div class="form-row mt-2">
                <div class="col-md-12">
                    <label>Leave Name</label>
                    <small class="text-muted">(Full name (e.g., "Casual Leave").)</small>
                    <input type="text" class="form-control" name="leave_name" id="leave_name" required />
                </div>
            </div>
            
            <div class="form-row mt-2">
                <div class="col-md-6">
                    <label>Leave Code</label>
                    <small class="text-muted">(A unique identifier for each leave type (e.g., "CL" for Casual Leave).)</small>
                    <input type="text" class="form-control ucaseinput" name="leave_code" id="leave_code" required />
                </div>
                <div class="col-md-6">
                    <label>Max Days</label>
                    <small class="text-muted">(Maximum number of days allowed per year.)</small>
                    <input type="number" class="form-control" name="max_days" id="max_days" min="0" required />
                </div>
            </div>
            <div class="form-row mt-2">
                <div class="col-md-6">
                    <label>Leave Type</label>
                    <small class="text-muted">(Whether the leave is Paid or Unpaid.)</small>
                    <select class="form-control" name="leave_type" id="leave_type" required>
                        <option value="Paid">Paid</option>
                        <option value="UnPaid">Un Paid</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label>Carry Forward</label>
                    <small class="text-muted">(Whether unused leave can be carried forward to the next year.)</small>
                    <select class="form-control" name="carry_forward" id="carry_forward" required>
                        <option value="Yes">Yes</option>
                        <option value="No">No</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="col-md-12">
                    <label>Description</label>
                    <textarea class="form-control" name="description" id="description"></textarea>
                    <input type="hidden" name="leaveid" id="leaveid" value="" />
                </div>
            </div>
            <div class="form-row mt-4">
                <div class="col-md-12">
                    <button id="addupdateLeaveData" data-leaveid='' class="btn btn-info pull-right" onclick="AddOrupdateContaineDivData('leavemastercontainer','addupdateLeaveData','addupdateleavedata')">Add Leave</button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
if(isset($_GET['vw'])==1){

}else{
  require('../footer.php'); 
}
?>