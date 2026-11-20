<?php
if(isset($_GET['vw'])==1){
    $key=$_GET['k'];
    $rooturl="";
}else{
  require('header.php');
}
?>
<style>
    :root {
        --approval-accent: #0284c7;
        --approval-bg: rgba(255, 255, 255, 0.7);
        --approval-blur: 10px;
    }

    #employeesalarycontainer {
        max-width: 1300px;
        margin-bottom: 30px !important;
    }

    .approval-header-card {
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
        border: none;
        border-radius: 15px;
        box-shadow: 0 10px 25px rgba(2, 132, 199, 0.2);
        margin-bottom: 20px !important;
        position: relative;
        overflow: hidden;
    }

    .approval-header-card::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -10%;
        width: 300px;
        height: 300px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        pointer-events: none;
    }

    .approval-header-card h4 {
        font-weight: 700;
        letter-spacing: 0.5px;
        margin: 0;
        padding: 15px;
        color: #fff;
    }

    .glass-card {
        background: var(--approval-bg);
        backdrop-filter: blur(var(--approval-blur));
        -webkit-backdrop-filter: blur(var(--approval-blur));
        border: 1px solid rgba(255, 255, 255, 0.35);
        border-radius: 15px;
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
    }

    .master-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        margin-bottom: 20px;
    }

    .approval-table-container {
        border-radius: 12px;
        overflow: visible;
        border: none;
        background: transparent;
    }

    .table-custom {
        border-collapse: separate !important;
        border-spacing: 0 12px !important;
        background: transparent !important;
        margin-bottom: 0;
    }

    .table-custom thead th {
        background: transparent;
        color: #64748b;
        font-weight: 700;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 8px 15px;
        border: none;
    }

    .table-custom tbody tr {
        background: #fff;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.04);
        border-radius: 10px;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .table-custom tbody tr:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(2, 132, 199, 0.08);
    }

    .table-custom tbody td {
        padding: 16px 15px;
        vertical-align: middle;
        color: #334155;
        border: none !important;
        font-size: 0.9rem;
    }

    .table-custom tbody tr td:first-child {
        border-top-left-radius: 10px;
        border-bottom-left-radius: 10px;
        border-left: 4px solid var(--approval-accent) !important;
    }

    .table-custom tbody tr td:last-child {
        border-top-right-radius: 10px;
        border-bottom-right-radius: 10px;
    }

    .table-custom .form-check-input {
        width: 1.25rem;
        height: 1.25rem;
        cursor: pointer;
        -webkit-appearance: checkbox !important;
        -moz-appearance: checkbox !important;
        appearance: checkbox !important;
    }

    @media (max-width: 991.98px) {
        .table-custom thead {
            display: none !important;
        }
        
        .table-custom, 
        .table-custom tbody, 
        .table-custom tr, 
        .table-custom td {
            display: block !important;
            width: 100% !important;
        }

        .table-custom {
            border-spacing: 0 !important;
        }

        .table-custom tbody tr {
            background: #fff !important;
            border-radius: 12px !important;
            margin-bottom: 20px !important;
            padding: 15px !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06) !important;
            border-left: 5px solid var(--approval-accent) !important;
            border-top: 1px solid rgba(0, 0, 0, 0.03) !important;
            border-right: 1px solid rgba(0, 0, 0, 0.03) !important;
            border-bottom: 1px solid rgba(0, 0, 0, 0.03) !important;
            transition: none !important;
            transform: none !important;
        }

        .table-custom tbody tr:hover {
            transform: none !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06) !important;
        }

        .table-custom tbody td {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            padding: 10px 0 !important;
            border: none !important;
            border-bottom: 1px dashed #f1f5f9 !important;
            font-size: 0.88rem !important;
            text-align: right !important;
            border-radius: 0 !important;
        }

        .table-custom tbody td:first-child {
            border-left: none !important;
            padding-top: 5px !important;
        }

        .table-custom tbody td:last-child {
            border-bottom: none !important;
            padding-bottom: 5px !important;
        }

        .table-custom tbody td::before {
            content: attr(data-label);
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            font-size: 0.72rem;
            letter-spacing: 0.5px;
            float: left;
            text-align: left;
            margin-right: 15px;
        }

        .table-custom tbody td > * {
            margin: 0 !important;
            text-align: right;
        }

        .table-custom tbody td[data-label="Select"] {
            justify-content: space-between !important;
        }
    }

    @media (max-width: 768px) {
        .master-controls {
            flex-direction: column;
            align-items: flex-start;
        }
    }

    .floating-approval-actions {
        position: fixed;
        bottom: 25px;
        right: 25px;
        z-index: 1050;
        display: flex;
        align-items: center;
        gap: 12px;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        padding: 10px 18px;
        border-radius: 50px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.18);
        border: 1px solid rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease-in-out;
    }

    @media (max-width: 576px) {
        .floating-approval-actions {
            bottom: 15px;
            right: 15px;
            left: 15px;
            justify-content: center;
            padding: 8px 12px;
            border-radius: 16px;
        }
    }

    /* Select2 Custom Styling */
    .select2-container--default .select2-selection--single {
        height: 38px !important;
        border-radius: 20px !important;
        border: 1px solid #e2e8f0 !important;
        background-color: #ffffff !important;
        display: flex !important;
        align-items: center !important;
        transition: all 0.2s ease-in-out;
    }
    .select2-container--default .select2-selection--single:focus,
    .select2-container--default.select2-container--open .select2-selection--single {
        border-color: #0284c7 !important;
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15) !important;
        outline: none !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px !important;
        color: #1f2937 !important;
        font-size: 13.5px !important;
        padding-left: 14px !important;
        padding-right: 28px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
        right: 10px !important;
    }
    .select2-dropdown {
        border-radius: 10px !important;
        border: 1px solid #e2e8f0 !important;
        box-shadow: 0 10px 25px rgba(0,0,0,0.08) !important;
        overflow: hidden !important;
        z-index: 9999 !important;
    }
    .select2-search--dropdown {
        padding: 8px !important;
    }
    .select2-search--dropdown .select2-search__field {
        border-radius: 8px !important;
        border: 1px solid #e2e8f0 !important;
        padding: 6px 12px !important;
        font-size: 13px !important;
        outline: none !important;
    }
    .select2-results__option {
        padding: 8px 14px !important;
        font-size: 13px !important;
    }
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #0284c7 !important;
        color: #ffffff !important;
    }
</style>
<div class="container mt-2" id="employeesalarycontainer">

    
    <div class="card approval-header-card shadow-lg">
        <div class="text-center">
            <h4><i class="ti ti-checkbox me-2"></i>Pending Attendance Approval</h4>
        </div>
    </div>

    <div class="card glass-card">
        <div class="card-body p-4">
            <div class="master-controls">
                <div class="d-flex align-items-center gap-3">
                    <h5 class="mb-0 fw-bold text-dark"><i class="ti ti-archive me-2 text-primary"></i>Pending Requests</h5>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap" style="flex: 1; max-width: 650px; justify-content: flex-end;">
                    <div style="min-width: 240px; flex: 1;">
                        <select class="form-select select2" id="EmployeeListdrp" style="width: 100%;">
                            <option value="all">All Employees</option>
                        </select>
                    </div>
                    <div style="min-width: 220px; flex: 1;">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 rounded-start-pill"><i class="ti ti-search text-muted"></i></span>
                            <input type="search" class="form-control border-start-0 rounded-end-pill form-control-sm" placeholder="Search records..." id="searchrptlvap" oninput="filterAttendanceApprovalTable();">
                        </div>
                    </div>
                </div>
            </div>

            <div class="approval-table-container">
                <div class="table-responsive">
                    <table class="table table-custom" id="attapprovaltbl">
                        <thead>
                            <tr>
                                <th style="width:70px; text-align: center;">
                                    <label class="form-check-label small d-block mb-1">Select All</label>
                                    <input type="checkbox" class="form-check-input msselectall" id="sltallattapprl">
                                </th>
                                <th>Employee Name</th>
                                <th>Attendance / Apply Date</th>
                                <th>Mark-In / Mark-Out</th>
                                <th>Remark</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Floating Action Buttons (Shown only when selected rows > 0 at bottom right) -->
<div class="floating-approval-actions d-none" id="approvalFloatingActions">
    <button class="btn btn-success rounded-pill px-4 shadow-sm" id="approvedattbtn" onclick="UpdateAppliedAttendanceApproval('Present', 'attapprovaltbl', 'approvedattbtn')">
        <i class="ti ti-check me-1"></i> Approve Selected
    </button>
    <button class="btn btn-danger rounded-pill px-4 shadow-sm" id="rejectattbtn" onclick="UpdateAppliedAttendanceApprovalReject('Rejected', 'attapprovaltbl', 'rejectattbtn')">
        <i class="ti ti-x me-1"></i> Reject Selected
    </button>
</div>

<!-- Modal for viewing attached file -->
<div class="modal fade" id="previewFileModal" tabindex="-1" aria-labelledby="previewFileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title text-white" id="previewFileModalLabel"><i class="ti ti-file me-2"></i>Attendance Proof Attachment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-3" id="previewFileModalBody" style="min-height: 200px; background-color: #f8fafc;">
                <!-- Content will be injected via JS -->
            </div>
            <div class="modal-footer bg-light">
                <a href="#" id="downloadFileBtn" class="btn btn-primary rounded-pill px-3" download target="_blank"><i class="ti ti-download me-1"></i>Download</a>
                <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Close</button>
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
let routurl = $("body").data("routurl") || "";

function toggleApprovalActionButtons() {
    let checkedCount = $("#attapprovaltbl tbody tr:visible").find('.sltallattapprlindivisula:checked').length;
    if (checkedCount > 0) {
        $('#approvalFloatingActions').removeClass('d-none');
    } else {
        $('#approvalFloatingActions').addClass('d-none');
    }
}

// Unified filter for Search and Employee dropdown
function filterAttendanceApprovalTable() {
    let searchVal = ($("#searchrptlvap").val() || "").toLowerCase().trim();
    let selectedEmp = $("#EmployeeListdrp").val() || "all";

    $("#attapprovaltbl tbody tr").each(function () {
        let $row = $(this);
        if ($row.find("td[colspan]").length > 0) return;

        let empId = $row.data("employeeid") ? String($row.data("employeeid")) : "";
        let rowText = $row.text().toLowerCase();

        let matchEmp = (selectedEmp === "all" || selectedEmp === "" || empId == selectedEmp);
        let matchSearch = (searchVal === "" || rowText.indexOf(searchVal) > -1);

        if (matchEmp && matchSearch) {
            $row.show().removeClass("d-none");
        } else {
            $row.hide().addClass("d-none");
            // Uncheck hidden rows so they are never submitted
            $row.find(".sltallattapprlindivisula").prop("checked", false);
        }
    });

    updateMasterCheckboxState();
    toggleApprovalActionButtons();
}

// Master Checkbox Select All / Deselect All (Applies ONLY to VISIBLE rows on screen)
$(document).on("change", "#sltallattapprl", function () {
    let isChecked = $(this).prop("checked");
    $("#attapprovaltbl tbody tr:visible").each(function () {
        if ($(this).find("td[colspan]").length === 0) {
            $(this).find(".sltallattapprlindivisula:not(:disabled)").prop("checked", isChecked);
        }
    });
    updateMasterCheckboxState();
    toggleApprovalActionButtons();
});

// Individual row checkbox change
$(document).on("change", ".sltallattapprlindivisula", function () {
    updateMasterCheckboxState();
    toggleApprovalActionButtons();
});

function updateMasterCheckboxState() {
    let visibleRows = $("#attapprovaltbl tbody tr:visible").filter(function () {
        return $(this).find("td[colspan]").length === 0;
    });

    let totalVisible = 0;
    let checkedVisible = 0;

    visibleRows.each(function () {
        let cb = $(this).find(".sltallattapprlindivisula:not(:disabled)");
        if (cb.length > 0) {
            totalVisible++;
            if (cb.is(":checked")) {
                checkedVisible++;
            }
        }
    });

    let master = $("#sltallattapprl");
    if (totalVisible === 0) {
        master.prop("checked", false);
        master.prop("indeterminate", false);
    } else {
        master.prop("checked", totalVisible === checkedVisible);
        master.prop("indeterminate", checkedVisible > 0 && checkedVisible < totalVisible);
    }
}

$(document).ready(function () {
    if ($.fn.select2) {
        $("#EmployeeListdrp").select2({
            placeholder: "Search & Select Employee",
            allowClear: false,
            width: '100%'
        });
    }

    $(document).on("change", "#EmployeeListdrp", function () {
        filterAttendanceApprovalTable();
    });

    // Event listener for viewing attached proof file
    $(document).on('click', '.view-proof-btn', function() {
        let url = $(this).data('url');
        let ext = url.split('.').pop().toLowerCase();
        let content = '';
        
        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
            content = `<img src="${url}" class="img-fluid rounded border shadow-sm" style="max-height: 70vh;" alt="Attachment Proof">`;
        } else if (ext === 'pdf') {
            content = `<iframe src="${url}" style="width: 100%; height: 60vh; border: none;" class="rounded border"></iframe>`;
        } else {
            content = `<div class="p-5 text-center"><i class="ti ti-file-text text-muted mb-3 d-block" style="font-size: 4rem;"></i><p class="text-muted mb-0">Preview not available for this file type. Please use the download button to view.</p></div>`;
        }
        
        $('#previewFileModalBody').html(content);
        $('#downloadFileBtn').attr('href', url);
        $('#previewFileModal').modal('show');
    });
});

FullAttendanceDetailsOfEmployee('ApprovalStatus', 'Pending', function(lt) {
    let ltye = "";
    let empDrop = $("#EmployeeListdrp");
    let currentSel = empDrop.val() || "all";
    let optionsHtml = `<option value="all">All Employees</option>`;
    let employeeSet = new Set();

    if (Array.isArray(lt) && lt.length > 0) {
        for (let i = 0; i < lt.length; i++) {
            if (lt[i].EmployeeID && !employeeSet.has(lt[i].EmployeeID)) {
                employeeSet.add(lt[i].EmployeeID);
                optionsHtml += `<option value="${lt[i].EmployeeID}">${lt[i].FullName} (${lt[i].EmployeeID})</option>`;
            }

            let checkIn  = lt[i].CheckInTime  || "";
            let checkOut = lt[i].CheckOutTime || "";
            // Calculate duration
            let duration = getTimeDuration(checkIn, checkOut);
            
            // Format attachment / remarks column
            let remarkHtml = `<span class="text-muted font-monospace">${lt[i].Remarks || '-'}</span>`;
            if (lt[i].misattendancefile) {
                let fileUrl = lt[i].misattendancefile;
                if (fileUrl.startsWith('/../')) {
                    fileUrl = fileUrl.substring(4);
                } else if (fileUrl.startsWith('../')) {
                    fileUrl = fileUrl.substring(3);
                } else if (fileUrl.startsWith('/')) {
                    fileUrl = fileUrl.substring(1);
                }
                let fullFileUrl = routurl + fileUrl;
                remarkHtml += `<br><button type="button" class="btn btn-xs btn-outline-primary mt-1 py-0 px-2 rounded-pill view-proof-btn" data-url="${fullFileUrl}" style="font-size: 0.75rem;"><i class="ti ti-eye me-1"></i>View Attachment</button>`;
            }

            let attDate = changeDateFormatYmd_dMY(lt[i].AttendanceDate);
            let applyDateVal = (lt[i].editapplydate && lt[i].editapplydate !== '0000-00-00 00:00:00') ? lt[i].editapplydate : lt[i].created_at;
            let applyDate = formatDateTime_DMY_Hi(applyDateVal,'d-M-Y h:i A');
            let attApplyHtml = `
                <div class="text-start d-inline-block">
                    <span class="badge bg-light text-dark border">${attDate}</span>
                    <div class="mt-1"><span class="badge border" style="background-color: rgba(2, 132, 199, 0.08); color: #0284c7; border-color: rgba(2, 132, 199, 0.2); font-size: 0.75rem;"><i class="ti ti-calendar-event me-1"></i>Applied: ${applyDate}</span></div>
                </div>`;

            // Mark-In / Mark-Out combined
            let markInStr = checkIn ? formatDateTime_DMY_Hi(checkIn,'d-M-Y h:i A') : '<span class="text-danger">Missing</span>';
            let markOutStr = checkOut ? formatDateTime_DMY_Hi(checkOut,'d-M-Y h:i A') : '<span class="text-danger">Missing</span>';
            let markInOutHtml = `
                <div class="small text-start d-inline-block">
                    <div><span class="badge me-1" style="background-color: rgba(25, 135, 84, 0.1); color: #198754; font-size: 0.7rem; padding: 2px 6px;">IN</span> ${markInStr}</div>
                    <div class="mt-1"><span class="badge me-1" style="background-color: rgba(220, 53, 69, 0.1); color: #dc3545; font-size: 0.7rem; padding: 2px 6px;">OUT</span> ${markOutStr}</div>
                    <div class="mt-2"><span class="badge bg-soft-info text-info border-info"><i class="ti ti-clock me-1"></i>Duration: ${duration}</span></div>
                </div>`;

            ltye += `
                <tr 
                    data-attendanceid="${lt[i].AttendanceID}"
                    data-employeeid="${lt[i].EmployeeID}" 
                    data-EmployeeName="${lt[i].FullName}" 
                    data-AttendanceDate="${lt[i].AttendanceDate}"
                >
                    <td class="text-center" data-label="Select" style="width:70px;">
                        <input type="checkbox" class="form-check-input sltallattapprlindivisula" />
                    </td>
                    <td class="fw-bold text-dark" data-label="Employee Name">${lt[i].FullName} (${lt[i].EmployeeID})</td>
                    <td data-label="Attendance / Apply Date">${attApplyHtml}</td>
                    <td data-label="Mark-In / Mark-Out">${markInOutHtml}</td>
                    <td data-label="Remark">${remarkHtml}</td>
                </tr>`;
        }

    } else {
        ltye = `<tr><td colspan="5" style="text-align:center;" class="text-muted py-4"><i class="ti ti-info-circle me-1"></i>No pending attendance requests found</td></tr>`;
    }

    $("#attapprovaltbl tbody").html(ltye);

    // Populate employee dropdown
    empDrop.html(optionsHtml);
    if (currentSel && empDrop.find(`option[value="${currentSel}"]`).length > 0) {
        empDrop.val(currentSel);
    } else {
        empDrop.val("all");
    }

    if ($.fn.select2) {
        if (!empDrop.hasClass("select2-hidden-accessible")) {
            empDrop.select2({
                placeholder: "Search & Select Employee",
                allowClear: false,
                width: '100%'
            });
        } else {
            empDrop.trigger('change.select2');
        }
    }

    filterAttendanceApprovalTable();
});

</script>