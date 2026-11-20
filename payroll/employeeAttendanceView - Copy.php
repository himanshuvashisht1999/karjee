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
<div class="container mt-2" id="employeesalarycontainer">
    <div class="row mb-2">
        <div class="col-12">
          
        </div>
    </div>
     <div class="card holiday-card">
        <div class="card-header">
          <h4>Employee Attendance</h4>
        </div>
        <div class="card-body">
            <div class="row mt-2">
                <div class="col-md-12 mt-2">
                    <div class="table-responsive">
                        <table class="table table-bordered table-light" id="attpreviewtbl">
                            <thead>
                                <thead>
                                    <tr class="noexport">
                                        <th colspan="4">
                                            <label>Search to Filter</label>
                                            <input type="search" class="form-control" border=1 placeholder="Search...." id="searchrptlvap" oninput="filterTable('#searchrptlvap', '#attpreviewtbl');">
                                        </th>
                                        <th colspan="3">
                                            <label>Attendance Date</label>
                                            <input type="date" name="attendanceDate" id="attendanceDate" class="form-control" />
                                        </th>
                                    </tr>
                                <tr>
                                    <th style="width:50px; text-align: center;">S.No</th>
                                    <th>Employee Name</th>
                                    <th>Attendance Date</th>
                                    <th>MarkIn</th>
                                    <th>MarkOut</th>
                                    <th>Duration</th>
                                    <th>Location</th>
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
<?php
if(isset($_GET['vw'])==1){

}else{
  require('footer.php'); 
}
?>
<script type="text/javascript">
// Initialize by showing the first card
let companyid = $("body").data("companyid");
let subcompanyid =  $("body").data("subcompanyid");
let projectid =  $("body").data("projectid");

$(document).ready(function () {
  // Utility: format local date as YYYY-MM-DD (avoids UTC shift)
  function getLocalTodayYMD() {
    const d = new Date();
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, "0");
    const dd = String(d.getDate()).padStart(2, "0");
    return `${yyyy}-${mm}-${dd}`;
  }

  // Change handler (attach first)
  $(document).on("change", "#attendanceDate", function () {
    const selectedDate = $("#attendanceDate").val();
    console.log("attendanceDate changed ->", selectedDate);
    if (!selectedDate) {
      console.warn("No date selected");
      return;
    }

    // Support both callback-style and Promise-style implementations
    try {
      const maybePromise = FullAttendanceDetailsOfEmployee(
        "AttendanceDate",
        selectedDate,
        function (lt) {
          // callback-style response
          renderAttendancePreview(lt);
        }
      );

      // If the function returns a Promise, handle it as well
      if (maybePromise && typeof maybePromise.then === "function") {
        maybePromise
          .then((res) => {
            // some implementations resolve with { status, data } or directly an array
            const lt = Array.isArray(res) ? res : res.data ?? res;
            renderAttendancePreview(lt);
          })
          .catch((err) => {
            console.error("Error (promise) from FullAttendanceDetailsOfEmployee:", err);
            renderAttendancePreview([]);
          });
      }
    } catch (ex) {
      console.error("Error calling FullAttendanceDetailsOfEmployee:", ex);
      renderAttendancePreview([]);
    }
  });

  // Renderer extracted for readability
  function renderAttendancePreview(lt) {
    let html = "";
    if (Array.isArray(lt) && lt.length > 0) {
      let sno = 1;
      lt.forEach((row) => {
        const checkIn = row.CheckInTime ?? "";
        const checkOut = row.CheckOutTime ?? "";
        const duration = typeof getTimeDuration === "function" ? getTimeDuration(checkIn, checkOut) : "";
        html += `
          <tr 
            data-attendanceid="${row.AttendanceID ?? ''}"
            data-employeeid="${row.EmployeeID ?? ''}" 
            data-employeename="${(row.FullName ?? '').replace(/"/g, '&quot;')}" 
            data-attendancedate="${row.AttendanceDate ?? ''}"
          >
            <td>${sno++}</td>
            <td>${row.FullName ?? '-'} (${row.EmployeeID ?? '-'})</td>
            <td>${typeof changeDateFormatYmd_dMY === "function" ? changeDateFormatYmd_dMY(row.AttendanceDate) : (row.AttendanceDate ?? '-') }</td>
            <td>${checkIn}</td>
            <td>${checkOut}</td>
            <td>${duration}</td>
            <td></td>
          </tr>`;
      });
    } else {
      html = `
        <tr>
          <td colspan="7" class="text-center">
            No pending attendance requests found
          </td>
        </tr>`;
    }
    $("#attpreviewtbl tbody").html(html);
  }
  const today = getLocalTodayYMD();
  $("#attendanceDate").val(today).trigger("change");
});
</script>