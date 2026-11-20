<?php
require('header.php');

// Fetch employees list for filtering
$empRes = mysqli_query($hrmconn, "SELECT EmployeeID, FirstName, MiddleName, LastName FROM hrm_EmployeesMaster WHERE ProjectID = '$projectid' AND Status = 'Active' ORDER BY FirstName ASC");
$employees = [];
while ($row = mysqli_fetch_assoc($empRes)) {
    $employees[] = $row;
}
?>

<div class="container-fluid" id="weekoffapprovalcontainer">
    <!-- Breadcrumb Header -->
    <div class="d-flex align-items-center justify-content-between page-breadcrumb mb-4">
        <div class="my-auto mb-2">
            <h2 class="mb-1 fw-bold text-dark fs-22">Weekly Off Approval Panel</h2>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="<?php echo $rooturl; ?>index.php?k=<?php echo $key; ?>">
                            <i class="ti ti-smart-home"></i>
                        </a>
                    </li>
                    <li class="breadcrumb-item">Administration</li>
                    <li class="breadcrumb-item active text-primary" aria-current="page">Week-off Approval</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Main Card Container -->
    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-header bg-primary py-3 px-4 text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4 class="mb-0 text-white fw-bold fs-16 d-flex align-items-center gap-2">
                <i class="ti ti-calendar-check fs-20"></i> Week-off Request Manager
            </h4>
            <!-- State/Tab Selector -->
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-light fw-bold px-3 btn-tab active" id="btnTabPending" onclick="switchTab('Pending')">
                    <i class="ti ti-clock me-1"></i> Pending Applications
                </button>
                <button type="button" class="btn btn-sm btn-light fw-bold px-3 btn-tab" id="btnTabHistory" onclick="switchTab('History')">
                    <i class="ti ti-history me-1"></i> History Log
                </button>
            </div>
        </div>

        <div class="card-body p-3 p-md-4">
            <!-- Filter Section -->
            <div class="row g-3 mb-4 align-items-end">
                <div class="col-12 col-md-5">
                    <label class="filter-label fw-bold mb-1 text-muted" style="font-size:11px; text-transform:uppercase;">Select Employee</label>
                    <select class="form-select select2 border-2" id="employeeFilter" onchange="fetchApplications()" style="width: 100%;">
                        <option value="all">-- All Employees --</option>
                        <?php foreach ($employees as $emp): ?>
                            <?php 
                            $fullName = implode(' ', array_filter([
                                trim($emp['FirstName']),
                                trim($emp['MiddleName']),
                                trim($emp['LastName'])
                            ]));
                            ?>
                            <option value="<?php echo $emp['EmployeeID']; ?>">
                                <?php echo htmlspecialchars($fullName); ?> (ID: <?php echo $emp['EmployeeID']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-5">
                    <label class="filter-label fw-bold mb-1 text-muted" style="font-size:11px; text-transform:uppercase;">Search Name</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-2 border-end-0 text-muted">
                            <i class="ti ti-search"></i>
                        </span>
                        <input type="text" class="form-control border-2 border-start-0 ps-0" id="nameSearch" placeholder="Type keyword to filter..." oninput="filterTableSearch()">
                    </div>
                </div>
                <div class="col-12 col-md-2 text-md-end text-center">
                    <button class="btn btn-primary w-100 fw-bold py-2 border-0 shadow-sm" onclick="fetchApplications()" style="border-radius:10px;">
                        <i class="ti ti-refresh me-1"></i> Reload
                    </button>
                </div>
            </div>

            <!-- Table Block -->
            <div class="table-responsive border rounded-3 overflow-hidden">
                <table class="table table-hover align-middle mb-0" id="weekOffApprovalTbl">
                    <thead class="bg-light text-secondary">
                        <tr class="fw-bold" style="font-size:12px; border-bottom: 2px solid #e2e8f0;">
                            <th class="select-col text-center" style="width: 60px;">
                                <div class="form-check d-flex justify-content-center m-0">
                                    <input type="checkbox" class="form-check-input border-2 cursor-pointer" id="selectAllCheckboxes" onchange="toggleSelectAll(this)">
                                </div>
                            </th>
                            <th>Employee Name</th>
                            <th>Week-off Date</th>
                            <th>Backup Subordinate</th>
                            <th>Reason / Remarks</th>
                            <th>Date Applied</th>
                            <th class="status-header text-center d-none">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <div class="spinner-border text-primary spinner-border-sm mb-2"></div>
                                <p class="mb-0 small">Fetching applications...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Footer Action Panel (For Pending state) -->
            <div class="row mt-4 pt-2 border-top d-none" id="bulkActionsPanel">
                <input type="hidden" id="actionRemarks" value="">
                <div class="col-12 text-end d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-success btn-sm fw-bold py-2 px-3 shadow-sm d-flex align-items-center justify-content-center gap-1.5 border-0" id="btnApproveSelected" onclick="submitStatusUpdate('Approved')" style="border-radius:8px; font-size:12.5px; min-width: 140px;">
                        <i class="ti ti-circle-check fs-16"></i> Approve Selected
                    </button>
                    <button type="button" class="btn btn-danger btn-sm fw-bold py-2 px-3 shadow-sm d-flex align-items-center justify-content-center gap-1.5 border-0" id="btnRejectSelected" onclick="submitStatusUpdate('Rejected')" style="border-radius:8px; font-size:12.5px; min-width: 140px;">
                        <i class="ti ti-circle-x fs-16"></i> Reject Selected
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require('footer.php'); ?>

<style>
#weekoffapprovalcontainer {
    font-family: 'Inter', sans-serif;
}
.btn-tab {
    border: none !important;
    background-color: rgba(255, 255, 255, 0.15) !important;
    color: rgba(255, 255, 255, 0.8) !important;
    transition: all 0.2s;
    font-size: 12px;
    border-radius: 6px;
}
.btn-tab:hover {
    color: #fff !important;
    background-color: rgba(255, 255, 255, 0.25) !important;
}
.btn-tab.active {
    background-color: #ffffff !important;
    color: #0d6efd !important;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
}
.cursor-pointer {
    cursor: pointer;
}
#weekOffApprovalTbl th {
    padding: 12px 16px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
#weekOffApprovalTbl td {
    padding: 14px 16px;
    color: #4b5563;
    font-size: 13.5px;
}
.status-badge-admin {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: 600;
    font-size: 11px;
    padding: 4px 10px;
    border-radius: 9999px;
}
.status-Pending { background-color: #fef3c7; color: #d97706; }
.status-Approved { background-color: #dcfce7; color: #15803d; }
.status-Rejected { background-color: #fee2e2; color: #b91c1c; }
.status-Cancelled { background-color: #f1f5f9; color: #64748b; }

/* Select2 Custom Styling */
.select2-container--default .select2-selection--single {
    height: 40px !important;
    border-radius: 8px !important;
    border: 2px solid #dee2e6 !important;
    background-color: #ffffff !important;
    display: flex !important;
    align-items: center !important;
    transition: all 0.2s ease-in-out;
}
.select2-container--default .select2-selection--single:focus,
.select2-container--default.select2-container--open .select2-selection--single {
    border-color: #0d6efd !important;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15) !important;
    outline: none !important;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 38px !important;
    color: #212529 !important;
    font-size: 14px !important;
    padding-left: 12px !important;
    padding-right: 28px !important;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 38px !important;
    right: 8px !important;
}
.select2-dropdown {
    border-radius: 8px !important;
    border: 1px solid #dee2e6 !important;
    box-shadow: 0 10px 25px rgba(0,0,0,0.08) !important;
    overflow: hidden !important;
    z-index: 9999 !important;
}
.select2-search--dropdown {
    padding: 8px !important;
}
.select2-search--dropdown .select2-search__field {
    border-radius: 6px !important;
    border: 1px solid #dee2e6 !important;
    padding: 6px 10px !important;
    font-size: 13px !important;
    outline: none !important;
}
.select2-results__option {
    padding: 8px 12px !important;
    font-size: 13px !important;
}
.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: #0d6efd !important;
    color: #ffffff !important;
}

@media (max-width: 767px) {
    #weekOffApprovalTbl th, #weekOffApprovalTbl td {
        padding: 8px 10px !important;
        font-size: 11px !important;
    }
    #bulkActionsPanel {
        flex-direction: column;
    }
    #bulkActionsPanel .col-md-4 {
        margin-top: 10px;
    }
}
</style>

<script>
let currentTab = 'Pending';
let projectID = "<?php echo $projectid; ?>";

$(document).ready(function() {
    if ($.fn.select2) {
        $("#employeeFilter").select2({
            placeholder: "Search & Select Employee",
            allowClear: false,
            width: '100%'
        });
    }

    $(document).on("change", ".row-checkbox", function () {
        updateMasterCheckboxState();
    });

    fetchApplications();
});

function switchTab(tab) {
    currentTab = tab;
    $(".btn-tab").removeClass("active");
    if (tab === 'Pending') {
        $("#btnTabPending").addClass("active");
        $(".select-col").removeClass("d-none");
        $(".status-header").addClass("d-none");
        $("#bulkActionsPanel").removeClass("d-none");
    } else {
        $("#btnTabHistory").addClass("active");
        $(".select-col").addClass("d-none");
        $(".status-header").removeClass("d-none");
        $("#bulkActionsPanel").addClass("d-none");
    }
    
    // Clear checkalls
    $("#selectAllCheckboxes").prop("checked", false);
    fetchApplications();
}

function fetchApplications() {
    const tbody = $("#weekOffApprovalTbl tbody");
    tbody.html(`
        <tr>
            <td colspan="7" class="text-center py-5 text-muted">
                <div class="spinner-border text-primary spinner-border-sm mb-2"></div>
                <p class="mb-0 small">Loading records...</p>
            </td>
        </tr>
    `);

    const empFilter = $("#employeeFilter").val();

    $.ajax({
        type: "POST",
        url: "get_admin_weeklyoff_data.php",
        data: {
            Status: currentTab,
            EmployeeID: empFilter,
            ProjectID: projectID
        },
        dataType: "JSON",
        success: function(res) {
            if (res.status === 'success' && Array.isArray(res.data) && res.data.length > 0) {
                let html = "";
                res.data.forEach(row => {
                    const hideSelect = currentTab !== 'Pending' ? 'd-none' : '';
                    const showStatus = currentTab !== 'Pending' ? '' : 'd-none';

                    html += `
                        <tr class="app-row" data-id="${row.WeeklyOffApplyID}" data-name="${row.EmployeeName}">
                            <td class="select-col text-center ${hideSelect}">
                                <div class="form-check d-flex justify-content-center m-0">
                                    <input type="checkbox" class="form-check-input border-2 cursor-pointer row-checkbox" value="${row.WeeklyOffApplyID}">
                                </div>
                            </td>
                            <td>
                                <div><strong class="text-dark">${row.EmployeeName}</strong></div>
                                <div class="extra-small text-muted" style="font-size:10px;">ID: ${row.EmployeeID}</div>
                            </td>
                            <td><span class="text-primary fw-bold">${row.WeekOffDateFormatted}</span></td>
                            <td><span class="fw-semibold">${row.SubordinateName || 'N/A'}</span></td>
                            <td>
                                <div class="small text-muted">${row.Reason || 'N/A'}</div>
                                ${row.remarks ? `<div class="extra-small text-success mt-1" style="font-size:10.5px;"><strong>Note:</strong> ${row.remarks}</div>` : ''}
                            </td>
                            <td><span class="text-secondary">${row.AppliedDateFormatted}</span></td>
                            <td class="status-header text-center ${showStatus}">
                                <span class="status-badge-admin status-${row.Status}">
                                    ${row.Status}
                                </span>
                            </td>
                        </tr>
                    `;
                });
                tbody.html(html);
                
                if (currentTab === 'Pending') {
                    $("#bulkActionsPanel").removeClass("d-none");
                } else {
                    $("#bulkActionsPanel").addClass("d-none");
                }
            } else {
                tbody.html(`
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="ti ti-calendar-off fs-32 d-block mb-2"></i>
                            No week-off applications found in this list.
                        </td>
                    </tr>
                `);
                $("#bulkActionsPanel").addClass("d-none");
            }

            filterTableSearch();
        },
        error: function() {
            tbody.html(`
                <tr>
                    <td colspan="7" class="text-center py-4 text-danger">
                        <i class="ti ti-alert-triangle fs-24 d-block mb-1"></i>
                        Failed to fetch data from the server.
                    </td>
                </tr>
            `);
            $("#bulkActionsPanel").addClass("d-none");
        }
    });
}

function toggleSelectAll(master) {
    let isChecked = master.checked;
    $("#weekOffApprovalTbl tbody tr:visible").each(function () {
        if ($(this).find("td[colspan]").length === 0) {
            $(this).find(".row-checkbox:not(:disabled)").prop("checked", isChecked);
        }
    });
    updateMasterCheckboxState();
}

function filterTableSearch() {
    const searchVal = $("#nameSearch").val().toUpperCase().trim();
    $(".app-row").each(function() {
        const empName = ($(this).attr("data-name") || "").toUpperCase();
        const fullText = $(this).text().toUpperCase();
        if (searchVal === "" || empName.indexOf(searchVal) > -1 || fullText.indexOf(searchVal) > -1) {
            $(this).show().removeClass("d-none");
        } else {
            $(this).hide().addClass("d-none");
            // Uncheck hidden rows so they are never selected
            $(this).find(".row-checkbox").prop("checked", false);
        }
    });
    updateMasterCheckboxState();
}

function updateMasterCheckboxState() {
    let visibleRows = $("#weekOffApprovalTbl tbody tr:visible").filter(function () {
        return $(this).find("td[colspan]").length === 0;
    });

    let totalVisible = 0;
    let checkedVisible = 0;

    visibleRows.each(function () {
        let cb = $(this).find(".row-checkbox:not(:disabled)");
        if (cb.length > 0) {
            totalVisible++;
            if (cb.is(":checked")) {
                checkedVisible++;
            }
        }
    });

    let master = $("#selectAllCheckboxes");
    if (totalVisible === 0) {
        master.prop("checked", false);
        master.prop("indeterminate", false);
    } else {
        master.prop("checked", totalVisible === checkedVisible);
        master.prop("indeterminate", checkedVisible > 0 && checkedVisible < totalVisible);
    }
}

function submitStatusUpdate(actionType) {
    const selectedIds = [];
    $("#weekOffApprovalTbl tbody tr:visible").find(".row-checkbox:checked").each(function() {
        selectedIds.push(parseInt($(this).val()));
    });

    if (selectedIds.length === 0) {
        iziToast.warning({
            title: "Warning",
            message: "Please select at least one week-off application."
        });
        return;
    }

    const currentRemarks = $("#actionRemarks").val();

    Swal.fire({
        title: `Confirm Bulk ${actionType}`,
        html: `
            <p class="text-start mb-2" style="font-size: 14px;">You are about to <strong>${actionType.toLowerCase()}</strong> ${selectedIds.length} week-off application(s).</p>
            <div class="text-start mb-1">
                <label class="form-label fw-bold text-dark mb-1" style="font-size:12px;">Admin Note / Remarks ${actionType === 'Rejected' ? '<span class="text-danger">*</span>' : ''}:</label>
                <input id="swalRemarks" class="form-control border-2" value="${currentRemarks.replace(/"/g, '&quot;')}" placeholder="${actionType === 'Rejected' ? 'Enter rejection reason (mandatory)...' : 'Enter remarks or notes (optional)...'}">
            </div>
        `,
        icon: actionType === 'Approved' ? 'success' : 'warning',
        showCancelButton: true,
        confirmButtonColor: actionType === 'Approved' ? '#198754' : '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: `Yes, ${actionType}!`,
        cancelButtonText: 'Cancel',
        preConfirm: () => {
            const val = document.getElementById('swalRemarks').value.trim();
            if (actionType === 'Rejected' && !val) {
                Swal.showValidationMessage('Admin remarks/reason is mandatory for rejection.');
                return false;
            }
            return val;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const finalRemarks = result.value;
            // Sync finalRemarks back to page input for visual clarity
            $("#actionRemarks").val(finalRemarks);

            $.ajax({
                type: "POST",
                url: "update_weeklyoff_status.php",
                data: {
                    ActionType: actionType,
                    Ids: selectedIds,
                    Remarks: finalRemarks
                },
                dataType: "JSON",
                success: function(res) {
                    if (res.status === 'success') {
                        iziToast.success({
                            title: "Updated",
                            message: res.message
                        });
                        $("#actionRemarks").val('');
                        $("#selectAllCheckboxes").prop("checked", false);
                        fetchApplications();
                    } else {
                        iziToast.error({
                            title: "Error",
                            message: res.message
                        });
                    }
                },
                error: function() {
                    iziToast.error({
                        title: "Error",
                        message: "Failed to submit updates. Try again."
                    });
                }
            });
        }
    });
}
</script>
