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
      text-align: center;
    }
  </style>
<div class="container mt-2" id="employeesalarycontainer">
    <div class="row mb-2">
        <div class="col-12">
          
        </div>
    </div>
    <div class="card leave-card">
        <div class="card-header bg-primary text-white text-center">
          <h4>Apply Leave</h4>
        </div>
        <div class="card-body">
          <div id="leaveForm">
            <div class="row">
              <div class="col-md-6 mb-3">
                  <label class="form-label">Employee</label>
                  <select class="form-select select2" name="EmployeeList" id="EmployeeList" required></select>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">Leave Type</label>
                  <select class="form-select select2" id="leaveType" required></select>
                </div>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">From Date</label>
                <input type="date" class="form-control" id="fromDate" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">To Date</label>
                <input type="date" class="form-control" id="toDate" required>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label">Total Days</label>
              <input type="number" class="form-control" id="totalDays" readonly>
            </div>

            <div class="mb-3">
              <label class="form-label">Reason</label>
              <textarea class="form-control" id="reason" rows="3" placeholder="Enter reason" required></textarea>
            </div>

            <div class="mb-3">
              <label class="form-label">Attachment (optional)</label>
              <input type="file" class="form-control" id="attachment" accept=".jpg,.png,.pdf">
            </div>

            <div class="text-center">
              <button type="button" id="savedappliyedleavebtn" onclick="SaveOrUpdateAppliedLeave('EmployeeList', 'leaveType', 'fromDate', 'toDate', 'reason', 'attachment', 'savedappliyedleavebtn');" class="btn btn-success">Submit</button>
              <button type="reset" class="btn btn-secondary ms-2" onclick="clearFormElementsInDiv('leaveForm')">Reset</button>
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
// Initialize by showing the first card
let companyid = $("body").data("companyid");
let subcompanyid = $("body").data("subcompanyid");
let projectid = $("body").data("projectid");
fetchEmployeeData_callback('allactive', '', (res) => {
    const $dropdown = $("#EmployeeList");
    const EmployeeId = ""; // You can set a default or preselected ID if needed
    $dropdown.empty(); // Clear existing options
    if (Array.isArray(res) && res.length > 0) {
        if (res.length > 1) {
            $dropdown.append(`<option value="">Select</option>`);
        }
        res.forEach(emp => {
            const { EmployeeID, FullName } = emp;
            $dropdown.append(`<option value="${EmployeeID}">${FullName}</option>`);
        });
        // ✅ If only one employee, select automatically
        if (res.length === 1) {
            $dropdown.val(res[0].EmployeeID).trigger("change");
        } else {
            // ✅ Otherwise select saved EmployeeId if available
            if (EmployeeId) {
                $dropdown.val(EmployeeId).trigger("change");
            } else {
                $dropdown.trigger("change");
            }
        }
    } else {
        // ✅ Fallback if no data received
        $dropdown.append(`<option value="">No data available</option>`);
        $dropdown.trigger("change");
    }
});

LeaveTypeMaster_callback('all','',function(res){
    var ltye="";
    let activeIndex = 1;
    if(res.length>0){
        for (var i = 0; i < res.length; i++) {
            if ((res[i].status || '').toLowerCase() === 'inactive') continue;
            ltye += `<tr><td>${activeIndex++}</td><td>${res[i].leave_name}</td><td>${res[i].leave_code}</td><td>${res[i].leave_type}</td><td>${res[i].max_days}</td><td>${res[i].carry_forward}</td><td>${res[i].description}</td><td></td></tr>`;
        }
    }
    const $dropdown = $("#leaveType");
    const leave_code = ""; // You can set a default or preselected ID if needed
    $dropdown.empty(); // Clear existing options
    if (Array.isArray(res) && res.length > 0) {
        const activeLeaves = res.filter(lytype => (lytype.status || '').toLowerCase() !== 'inactive');
        if (activeLeaves.length > 0) {
            if (activeLeaves.length > 1) {
                $dropdown.append(`<option value="">Select</option>`);
            }
            activeLeaves.forEach(lytype => {
                const { leave_code, leave_name, leave_type } = lytype;
                $dropdown.append(`<option value="${leave_code}" data-type="${leave_type}">${leave_name}</option>`);
            });
            // ✅ If only one employee, select automatically
            if (activeLeaves.length === 1) {
                $dropdown.val(activeLeaves[0].leave_code).trigger("change");
            } else {
                // ✅ Otherwise select saved EmployeeId if available
                if (leave_code) {
                    $dropdown.val(leave_code).trigger("change");
                } else {
                    $dropdown.trigger("change");
                }
            }
        } else {
            $dropdown.append(`<option value="">No active leave types available</option>`);
            $dropdown.trigger("change");
        }
    } else {
        // ✅ Fallback if no data received
        $dropdown.append(`<option value="">No data available</option>`);
        $dropdown.trigger("change");
    }
});

linkDateFields('fromDate', 'toDate');

$(document).on("change", "#fromDate, #toDate", function(){
    if($("#fromDate").val() && $("#toDate").val()){
        $("#totalDays").val(CountDaysBitweenDates($("#fromDate").val(), $("#toDate").val()));
    }
});
</script>