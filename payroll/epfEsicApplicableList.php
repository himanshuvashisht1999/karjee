<?php
include('header.php');
?>
<div class="content container-fluid">
  <!-- Page Header -->
  <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-4">
    <div>
      <h4 class="mb-1 text-dark fw-bold"><i class="ti ti-shield-check me-2 text-primary"></i>EPF & ESIC Applicable Employees</h4>
      <p class="text-muted mb-0 fs-13">View and filter employees covered under Employee Provident Fund (EPF - Deduction ID 1) and Employee State Insurance (ESIC - Deduction ID 3).</p>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-success shadow-sm rounded-2 px-3 py-2" id="btnExportExcel">
        <i class="ti ti-file-spreadsheet me-1"></i> Export to Excel
      </button>
    </div>
  </div>

  <!-- KPI Summary Cards -->
  <div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
      <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
        <div class="card-body p-3 d-flex align-items-center justify-content-between">
          <div>
            <span class="text-muted fs-12 fw-semibold text-uppercase d-block mb-1">Total Employees</span>
            <h3 class="mb-0 fw-bold text-dark" id="kpiTotalEmp">0</h3>
          </div>
          <div class="avatar avatar-md bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
            <i class="ti ti-users fs-3"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
        <div class="card-body p-3 d-flex align-items-center justify-content-between">
          <div>
            <span class="text-muted fs-12 fw-semibold text-uppercase d-block mb-1">EPF Applicable</span>
            <h3 class="mb-0 fw-bold text-success" id="kpiEpfEmp">0</h3>
          </div>
          <div class="avatar avatar-md bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
            <i class="ti ti-building-bank fs-3"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
        <div class="card-body p-3 d-flex align-items-center justify-content-between">
          <div>
            <span class="text-muted fs-12 fw-semibold text-uppercase d-block mb-1">ESIC Applicable</span>
            <h3 class="mb-0 fw-bold text-info" id="kpiEsicEmp">0</h3>
          </div>
          <div class="avatar avatar-md bg-info-subtle text-info rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
            <i class="ti ti-heart-handshake fs-3"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
        <div class="card-body p-3 d-flex align-items-center justify-content-between">
          <div>
            <span class="text-muted fs-12 fw-semibold text-uppercase d-block mb-1">Both EPF & ESIC</span>
            <h3 class="mb-0 fw-bold text-warning" id="kpiBothEmp">0</h3>
          </div>
          <div class="avatar avatar-md bg-warning-subtle text-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
            <i class="ti ti-checkup-list fs-3"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Filter & Search Toolbar -->
  <div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
      <div class="row g-3 align-items-center">
        <div class="col-md-4">
          <label class="form-label fs-12 fw-bold text-dark text-uppercase mb-1"><i class="ti ti-filter me-1 text-primary"></i>Deduction Filter</label>
          <select class="form-select shadow-none" id="filterType">
            <option value="applicable_any" selected>Applicable Only (Either EPF or ESIC)</option>
            <option value="epf_only">EPF Applicable Only</option>
            <option value="esic_only">ESIC Applicable Only</option>
            <option value="both">Both EPF & ESIC Applicable</option>
            <option value="all">All Employees (Including Non-Applicable)</option>
            <option value="not_applicable">Not Applicable (Neither)</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label fs-12 fw-bold text-dark text-uppercase mb-1"><i class="ti ti-user-check me-1 text-primary"></i>Status</label>
          <select class="form-select shadow-none" id="filterStatus">
            <option value="all">All Statuses</option>
            <option value="active" selected>Active Only</option>
            <option value="resigned">Resigned</option>
          </select>
        </div>
        <div class="col-md-5">
          <label class="form-label fs-12 fw-bold text-dark text-uppercase mb-1"><i class="ti ti-search me-1 text-primary"></i>Search Employee</label>
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0"><i class="ti ti-search text-muted"></i></span>
            <input type="text" class="form-control border-start-0 shadow-none" id="searchInput" placeholder="Search by ID, Name, Designation, UAN/Reg ID...">
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Main Table Card Grid -->
  <div class="card shadow-sm border-0 rounded-3">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="epfEsicTbl">
          <thead class="bg-light text-dark fw-semibold" style="border-bottom: 2px solid #cbd5e1;">
            <tr>
              <th style="width: 60px;">S.No</th>
              <th style="width: 120px;">Employee ID</th>
              <th>Employee Name</th>
              <th>Designation</th>
              <th>Date of Joining</th>
              <th class="text-center">EPF (Deduction ID 1)</th>
              <th class="text-center">ESIC (Deduction ID 3)</th>
              <th class="text-center" style="width: 100px;">Status</th>
              <th class="text-center noexport" style="width: 130px;">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td colspan="9" class="text-center text-muted py-4">Loading records...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Edit Registration Numbers Modal -->
<div class="modal fade" id="editRegNoModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-3">
      <form id="editRegNoForm" method="post">
        <div class="modal-header bg-primary text-white py-3">
          <div class="d-flex align-items-center">
            <i class="ti ti-id me-2 fs-4"></i>
            <h5 class="modal-title mb-0 text-white fw-bold">Update EPF & ESIC Registration Numbers</h5>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <input type="hidden" name="EmployeeID" id="regModalEmpId" value="">
          
          <div class="mb-3">
            <label class="form-label fs-12 fw-bold text-dark text-uppercase mb-1">Employee</label>
            <input type="text" class="form-control bg-light text-dark fw-bold" id="regModalEmpName" readonly>
          </div>

          <div class="mb-3">
            <label class="form-label fs-12 fw-bold text-dark text-uppercase mb-1">
              <i class="ti ti-building-bank me-1 text-success"></i>EPF UAN / Registration No. (Deduction ID 1)
            </label>
            <input type="text" class="form-control" name="epf_regid" id="regModalEpfRegId" placeholder="Enter EPF UAN No.">
            <small class="text-muted fs-11">Updates `regid` column in `hrm_EmployeeDeductionsComponents` for DeductionID 1</small>
          </div>

          <div class="mb-3">
            <label class="form-label fs-12 fw-bold text-dark text-uppercase mb-1">
              <i class="ti ti-heart-handshake me-1 text-info"></i>ESIC IP / Registration No. (Deduction ID 3)
            </label>
            <input type="text" class="form-control" name="esic_regid" id="regModalEsicRegId" placeholder="Enter ESIC IP Registration No.">
            <small class="text-muted fs-11">Updates `regid` column in `hrm_EmployeeDeductionsComponents` for DeductionID 3</small>
          </div>
        </div>
        <div class="modal-footer bg-light py-3 border-top">
          <button type="button" class="btn btn-outline-secondary px-4 me-2" data-bs-dismiss="modal">
            <i class="ti ti-x me-1"></i> Cancel
          </button>
          <button type="submit" class="btn btn-primary px-4 shadow-sm" id="btnSaveRegNo">
            <i class="ti ti-device-floppy me-1"></i> Save Numbers
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include('footer.php'); ?>

<script type="text/javascript">
var routurl = $("body").data("routurl") || "../";
var key = $("body").data("key");
var rawEmployeeData = [];

$(document).ready(function() {
    loadEpfEsicData();

    // Event listeners for filters
    $("#filterType, #filterStatus").on("change", function() {
        renderTableData();
    });

    $("#searchInput").on("keyup input", function() {
        renderTableData();
    });

    // Export to Excel Button
    $("#btnExportExcel").on("click", function() {
        exportTableToExcel('epfEsicTbl', 'EPF_ESIC_Applicable_Employees');
    });
});

function loadEpfEsicData() {
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");

    $.ajax({
        url: routurl + "modal/hrm_salarymodal.php?action=getEpfEsicApplicableEmployeeList",
        type: "POST",
        data: {
            companyid: companyid,
            subcompanyid: subcompanyid,
            projectid: projectid,
            filterType: 'all'
        },
        dataType: "json",
        success: function(res) {
            rawEmployeeData = res || [];
            updateKPIs(rawEmployeeData);
            renderTableData();
        },
        error: function() {
            $("#epfEsicTbl tbody").html('<tr><td colspan="9" class="text-center text-danger py-4">Failed to load data. Please try again.</td></tr>');
        }
    });
}

function updateKPIs(data) {
    let total = data.length;
    let epfCount = 0;
    let esicCount = 0;
    let bothCount = 0;

    data.forEach(item => {
        let hasEpf = !!item.hasEpf;
        let hasEsic = !!item.hasEsic;
        if (hasEpf) epfCount++;
        if (hasEsic) esicCount++;
        if (hasEpf && hasEsic) bothCount++;
    });

    $("#kpiTotalEmp").text(total);
    $("#kpiEpfEmp").text(epfCount);
    $("#kpiEsicEmp").text(esicCount);
    $("#kpiBothEmp").text(bothCount);
}

function renderTableData() {
    let filterType = $("#filterType").val();
    let filterStatus = $("#filterStatus").val();
    let searchVal = $("#searchInput").val().toLowerCase().trim();

    let filtered = rawEmployeeData.filter(emp => {
        let hasEpf = !!emp.hasEpf;
        let hasEsic = !!emp.hasEsic;

        // Deduction Filter
        if (filterType === 'epf_only' && !hasEpf) return false;
        if (filterType === 'esic_only' && !hasEsic) return false;
        if (filterType === 'both' && (!hasEpf || !hasEsic)) return false;
        if (filterType === 'applicable_any' && (!hasEpf && !hasEsic)) return false;
        if (filterType === 'not_applicable' && (hasEpf || hasEsic)) return false;

        // Status Filter
        let empStatus = (emp.Status || '').toLowerCase();
        if (filterStatus === 'active' && empStatus !== 'active') return false;
        if (filterStatus === 'resigned' && empStatus !== 'resigned') return false;

        // Search Input Filter
        if (searchVal) {
            let searchableStr = (
                (emp.EmployeeID || '') + ' ' +
                (emp.FullName || '') + ' ' +
                (emp.DesignationName || '') + ' ' +
                (emp.epf_regid || '') + ' ' +
                (emp.esic_regid || '')
            ).toLowerCase();

            if (!searchableStr.includes(searchVal)) return false;
        }

        return true;
    });

    if (filtered.length === 0) {
        $("#epfEsicTbl tbody").html('<tr><td colspan="9" class="text-center text-muted py-4">No records match the selected filters.</td></tr>');
        return;
    }

    let rowsHtml = '';
    filtered.forEach((emp, index) => {
        let empDashUrl = `${routurl}empPanel/index.php?k=${key}&empid=${emp.EmployeeID}&accessby=adminu`;
        let empIdLink = `<a href="${empDashUrl}" target="_blank" class="fw-bold text-primary text-decoration-none" title="Click to open Employee Dashboard">${emp.EmployeeID}</a>`;
        
        let dojFormatted = emp.DateOfJoining ? new Date(emp.DateOfJoining).toLocaleDateString("en-IN") : '-';

        // EPF Badge
        let epfBadge = '<span class="badge bg-secondary-subtle text-secondary border px-2 py-1"><i class="ti ti-x me-1"></i>No</span>';
        if (emp.hasEpf) {
            let epfReg = emp.epf_regid ? `<br/><small class="text-dark fw-bold fs-11">UAN: ${emp.epf_regid}</small>` : '<br/><small class="text-warning fs-11"><i class="ti ti-alert-triangle me-1"></i>No UAN</small>';
            epfBadge = `<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="ti ti-check me-1"></i>Yes</span>${epfReg}`;
        }

        // ESIC Badge
        let esicBadge = '<span class="badge bg-secondary-subtle text-secondary border px-2 py-1"><i class="ti ti-x me-1"></i>No</span>';
        if (emp.hasEsic) {
            let esicReg = emp.esic_regid ? `<br/><small class="text-dark fw-bold fs-11">IP: ${emp.esic_regid}</small>` : '<br/><small class="text-warning fs-11"><i class="ti ti-alert-triangle me-1"></i>No IP No.</small>';
            esicBadge = `<span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1"><i class="ti ti-check me-1"></i>Yes</span>${esicReg}`;
        }

        // Status Badge
        let statusBadge = emp.Status === 'Active' 
            ? '<span class="badge bg-success">Active</span>' 
            : emp.Status === 'Resigned'
                ? '<span class="badge bg-danger">Resigned</span>'
                : `<span class="badge bg-secondary">${emp.Status || 'Active'}</span>`;

        let safeFullName = (emp.FullName || '').replace(/'/g, "\\'");
        let safeEpfReg = (emp.epf_regid || '').replace(/'/g, "\\'");
        let safeEsicReg = (emp.esic_regid || '').replace(/'/g, "\\'");

        let actionBtn = `<button type="button" class="btn btn-sm btn-outline-primary px-2 py-1 shadow-sm" onclick="openEditRegModal('${emp.EmployeeID}', '${safeFullName}', '${safeEpfReg}', '${safeEsicReg}')"><i class="ti ti-edit me-1"></i> Edit Reg</button>`;

        rowsHtml += `
            <tr>
                <td>${index + 1}</td>
                <td>${empIdLink}</td>
                <td class="fw-semibold text-dark">${emp.FullName}</td>
                <td class="text-muted">${emp.DesignationName || '-'}</td>
                <td>${dojFormatted}</td>
                <td class="text-center">${epfBadge}</td>
                <td class="text-center">${esicBadge}</td>
                <td class="text-center">${statusBadge}</td>
                <td class="text-center noexport">${actionBtn}</td>
            </tr>
        `;
    });

    $("#epfEsicTbl tbody").html(rowsHtml);
}

// Open Registration Numbers Edit Modal
function openEditRegModal(empId, empName, epfReg, esicReg) {
    $("#regModalEmpId").val(empId);
    $("#regModalEmpName").val(empId + " - " + empName);
    $("#regModalEpfRegId").val(epfReg);
    $("#regModalEsicRegId").val(esicReg);
    $("#editRegNoModal").modal("show");
}

// Submit Form Handler for Reg Numbers
$("#editRegNoForm").submit(function(e) {
    e.preventDefault();
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");

    let postData = $(this).serialize() + `&companyid=${companyid}&subcompanyid=${subcompanyid}&projectid=${projectid}`;
    let $btn = $("#btnSaveRegNo");
    $btn.prop("disabled", true).html('<i class="ti ti-spinner fa-spin me-1"></i> Saving...');

    $.ajax({
        url: routurl + "modal/hrm_salarymodal.php?action=updateEpfEsicRegNumbers",
        type: "POST",
        data: postData,
        dataType: "json",
        success: function(res) {
            $btn.prop("disabled", false).html('<i class="ti ti-device-floppy me-1"></i> Save Numbers');
            if (res && (res.status === 'success' || res.status === 'Success')) {
                if (typeof alertmessage === 'function') {
                    alertmessage("success", res.message || "Registration numbers updated!");
                } else if (typeof Swal !== 'undefined') {
                    Swal.fire("Success", res.message || "Registration numbers updated!", "success");
                } else {
                    alert(res.message || "Registration numbers updated!");
                }
                $("#editRegNoModal").modal("hide");
                loadEpfEsicData(); // Auto refresh table grid
            } else {
                let msg = (res && res.message) ? res.message : 'Error updating registration numbers.';
                if (typeof alertmessage === 'function') {
                    alertmessage("error", msg);
                } else {
                    alert(msg);
                }
            }
        },
        error: function() {
            $btn.prop("disabled", false).html('<i class="ti ti-device-floppy me-1"></i> Save Numbers');
            if (typeof alertmessage === 'function') {
                alertmessage("error", 'Error while saving registration numbers.');
            } else {
                alert('Error while saving registration numbers.');
            }
        }
    });
});

// Function to export table to Excel (CSV format)
function exportTableToExcel(tableID, filename) {
    let table = document.getElementById(tableID);
    if (!table) return;

    let csv = [];
    let rows = table.querySelectorAll("tr");

    for (let i = 0; i < rows.length; i++) {
        let row = [], cols = rows[i].querySelectorAll("td, th");
        for (let j = 0; j < cols.length; j++) {
            if (cols[j].classList.contains('noexport')) continue;
            let text = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, " ").trim();
            text = text.replace(/"/g, '""');
            row.push('"' + text + '"');
        }
        csv.push(row.join(","));
    }

    let csvFile = new Blob([csv.join("\n")], { type: "text/csv" });
    let downloadLink = document.createElement("a");
    downloadLink.download = (filename || 'export') + '_' + new Date().toISOString().slice(0,10) + '.csv';
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}
</script>
