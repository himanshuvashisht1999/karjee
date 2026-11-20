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
            <button class="btn btn-success btn-sm float-end"  onclick="openAddExemptionModal()">📍 Set Attendance Location Exemption</button>
            <h4>Attendance Location Exemption</h4>
        </div>
        <div class="card-body">
            <div class="row mt-2">
                <div class="col-md-3 mt-2">
                    <label>Filter</label>
                    <select class="form-control select2" id="FilterEmployeeId"></select>
                </div>
                <div class="col-md-3 mt-2"></div>
                <div class="col-md-3 mt-2"></div>
                <div class="col-md-3 mt-2">
                    <label>Year</label>
                    <select class="form-control" id="FilterExemptYear"></select>
                </div>
            </div>
            <div class="row mt-2">    
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Employee Name</th>
                                    <th>Exempted Location</th>
                                    <th>Exempt Date</th>
                                    <th>Comments</th>
                                    <th>Created By</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody id="exemptedLocationTableBody">
                                <tr>
                                    <td colspan="8" class="text-center">No data</td>
                                </tr>
                            </tbody>
                        </table>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal" id="attlocationExmptModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exptModalTitle">Add Attendance Location Exemption</h5>
                <!-- Title changes dynamically to Update if editing -->
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="exptEmployeeid" class="form-label">Employee Name</label>
                        <select class="select2 form-control" id="exptEmployeeid"></select>
                    </div>

                    <div class="col-md-6">
                        <label for="exptLocation" class="form-label">Allowed Location for Attendance</label>
                        <select class="select2 form-control" id="exptLocation"></select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="exptDateFrom" class="form-label">Allowed Date From</label>
                        <input type="date" class="form-control" id="exptDateFrom">
                    </div>

                    <div class="col-md-6">
                        <label for="exptDateTo" class="form-label">Allowed Date To</label>
                        <input type="date" class="form-control" id="exptDateTo">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label>Remark</label>
                        <textarea class="form-control" rows="2" id="exptLocationRemark"></textarea>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>

                <!-- Save / Update button -->
                <button type="button" id="exptSaveBtn" class="btn btn-primary" onclick="saveEmployeeAttendanceLocationExemption($('#exptEmployeeid').val(), $('#exptLocation').val(), $('#exptDateFrom').val(), $('#exptDateTo').val(), $('#exptLocationRemark').val(), 'exptSaveBtn');">
                    Save Exemption
                </button>
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
loadEmployeeExemptedLocations('<?php echo date('Y'); ?>', "all", "all");

AttendanceLocationMaster_callback("all", "all", function(res) {
    let lcoptionhtml = "";
    $("#exptLocation").empty();
    if (Array.isArray(res) && res.length > 0) {
        res.forEach((row, index) => {
            lcoptionhtml += `<option value="${row.LocationId}">${row.LocationName}</option>`;
        });
        lcoptionhtml += `<option value="outoffice">Out Side Office</option>`;

    } else {
        lcoptionhtml = `
            <option>No locations found</option>`;
    }
    $("#exptLocation").append(lcoptionhtml);
});
fetchEmployeeDataForLocaton_callback("all", "all", function(res) {
    let empoptionhtml = "";
    $("#exptEmployeeid").empty();
    if (Array.isArray(res) && res.length > 0) {
        if(res.length > 1){
            empoptionhtml += `<option value="" selected disabled>Select</option>`;
        }
        res.forEach((row, index) => {
            empoptionhtml += `<option value="${row.EmployeeID}">${row.EmployeeName}</option>`;
        });

    } else {
        empoptionhtml = `
            <option>Data not available</option>`;
    }
    $("#exptEmployeeid").append(empoptionhtml);
    $("#FilterEmployeeId").append(empoptionhtml);
});

$('#attlocationExmptModal').on('shown.bs.modal', function () {
    $('#exptEmployeeid').select2({
        width: '100%',
        dropdownParent: $('#attlocationExmptModal')
    });

    $('#exptLocation').select2({
        width: '100%',
        dropdownParent: $('#attlocationExmptModal')
    });
});
function openAddExemptionModal() {

    
    $("#exptModalTitle").text("Add Attendance Location Exemption");
    $("#exptSaveBtn").text("Save Exemption").data("mode", "add");

    $("#exptEmployeeid").val("").trigger("change");
    $("#exptLocation").val("").trigger("change");
    $("#exptDateFrom").val("");
    $("#exptDateTo").val("");
    $("#attlocationExmptModal").modal("show");
}
const today = new Date().toISOString().split("T")[0];
// 🔒 Min date = today
$("#exptDateFrom").attr("min", today);
// When FROM date changes
$("#exptDateFrom").on("change", function () {
        const fromDate = $(this).val();
        if (fromDate) {
            // Set TO date min = FROM date
            $("#exptDateTo")
                .attr("min", fromDate)
                .prop("disabled", false);

            // Clear TO date if invalid
            if ($("#exptDateTo").val() && $("#exptDateTo").val() < fromDate) {
                $("#exptDateTo").val("");
            }
        } else {
            $("#exptDateTo").val("").prop("disabled", true);
        }
    });

</script>
