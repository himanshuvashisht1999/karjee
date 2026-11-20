<?php
if (isset($_GET['vw']) == 1) {
    $key = $_GET['k'];
    $rooturl = "";
} else {
    require('header.php');
}
?>

<style>
    :root {
        --leave-accent: #6366f1;
        --leave-bg: rgba(255, 255, 255, 0.7);
        --leave-blur: 10px;
    }

    #employeesalarycontainer {
        max-width: 1300px;
        margin-bottom: 30px !important;
    }

    .leave-header-card {
        background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
        border: none;
        border-radius: 15px;
        box-shadow: 0 10px 25px rgba(99, 102, 241, 0.2);
        margin-bottom: 20px !important;
        position: relative;
        overflow: hidden;
    }

    .leave-header-card::before {
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

    .leave-header-card h4 {
        font-weight: 700;
        letter-spacing: 0.5px;
        margin: 0;
        padding: 15px;
        color: #fff;
    }

    .glass-card {
        background: var(--leave-bg);
        backdrop-filter: blur(var(--leave-blur));
        -webkit-backdrop-filter: blur(var(--leave-blur));
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

    .leave-table-container {
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #f1f5f9;
        background: #fff;
    }

    .table-custom {
        margin-bottom: 0;
    }

    .table-custom thead th {
        background: #f8fafc;
        color: #64748b;
        font-weight: 700;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 15px;
        border: none;
    }

    .table-custom tbody td {
        padding: 12px 15px;
        vertical-align: middle;
        color: #334155;
        border-top: 1px solid #f1f5f9;
        font-size: 0.9rem;
    }

    .badge-custom {
        padding: 6px 12px;
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .badge-paid {
        background: rgba(16, 185, 129, 0.1);
        color: #10b981;
        border: 1px solid rgba(16, 185, 129, 0.2);
    }

    .badge-unpaid {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.2);
    }

    .badge-restricted {
        background: rgba(245, 158, 11, 0.1);
        color: #f59e0b;
        border: 1px solid rgba(245, 158, 11, 0.2);
    }

    .badge-status-active {
        background: rgba(16, 185, 129, 0.15);
        color: #10b981;
        border: 1px solid rgba(16, 185, 129, 0.25);
        font-size: 0.7rem;
        padding: 3px 8px;
        border-radius: 50px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .badge-status-inactive {
        background: rgba(100, 116, 139, 0.15);
        color: #64748b;
        border: 1px solid rgba(100, 116, 139, 0.25);
        font-size: 0.7rem;
        padding: 3px 8px;
        border-radius: 50px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .info-strip.strip-deactive {
        background: #f8fafc;
        border-color: #e2e8f0;
        opacity: 0.75;
    }

    .info-strip.strip-deactive::before {
        background: #94a3b8;
    }

    .info-strip.strip-deactive .info-strip-icon {
        background: #f1f5f9;
        color: #64748b;
        border-color: #cbd5e1;
    }

    .info-strip-icon.bg-soft-primary {
        background: #eff6ff;
        color: #3b82f6;
        border: 1px solid #dbeafe;
        font-weight: 800;
    }

    .info-strip {
        position: relative;
        overflow: hidden;
        padding: 1.25rem;
        border-radius: 16px;
        background: #ffffff;
        border: 1px solid #f1f5f9;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .info-strip:hover {
        transform: translateY(-2px);
        border-color: #3b82f6;
        box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.1), 0 4px 6px -4px rgba(59, 130, 246, 0.1);
    }

    .info-strip::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        height: 100%;
        width: 5px;
        background: #3b82f6;
        opacity: 0;
        transition: opacity 0.3s;
    }

    .info-strip:hover::before {
        opacity: 1;
    }

    @media (max-width: 768px) {
        .master-controls {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>

<div class="container mt-2" id="employeesalarycontainer">
    <div class="row mb-3">
        <div class="col-12 text-end">
            <a class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-sm"
                href="../admin_dashboard.php?k=<?php echo $key; ?>&grpid=<?php echo $groupid; ?>&mdid=<?php echo $mdid; ?>">
                <i class="ti ti-arrow-left me-1"></i> BACK TO HOME
            </a>
        </div>
    </div>

    <div class="card leave-header-card shadow-lg">
        <div class="text-center">
            <h4><i class="ti ti-calendar-off me-2"></i>Leave Type Management</h4>
        </div>
    </div>

    <div class="card glass-card">
        <div class="card-body p-4">
            <div class="master-controls">
                <div>
                    <h5 class="mb-0 fw-bold text-dark"><i class="ti ti-archive me-2 text-primary"></i>Configured Leave
                        Types</h5>
                </div>
                <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal"
                    data-bs-target="#addLeaveModal" id="addNewTypeBtn">
                    <i class="ti ti-circle-plus me-1"></i> Add New Type
                </button>
            </div>

            <div id="leaveTypeStripsWrapper" class="info-strip-container">
                <!-- Data populated dynamically -->
                <div class="text-center py-4">
                    <div class="spinner-border text-primary spinner-border-sm"></div>
                    <p class="mt-2 mb-0 text-muted small">Loading leave types...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modern Styled Modal -->
    <div class="modal fade" id="addLeaveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-2xl" style="border-radius: 20px; overflow: hidden;">
                <div class="modal-header text-white" style="background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); border-bottom: none; padding: 20px 24px;">
                    <h5 class="modal-title fw-bold text-white d-flex align-items-center" id="leaveModalTitle">
                        <i class="ti ti-circle-plus me-2 fs-22"></i><span>Create Leave Type</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="background: none; border: none; color: #fff; font-size: 1.5rem; line-height: 1; cursor: pointer; transition: opacity 0.2s; opacity: 0.8;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.8'">&times;</button>
                </div>
                <div class="modal-body p-4 bg-white">
                    <form id="addLeaveForm">
                        <input type="hidden" id="edit_leave_id" value="0">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="small fw-bold text-dark mb-1">Leave Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control rounded-3 border-light-subtle px-3 py-2" id="new_leave_name"
                                    placeholder="e.g. Sick Leave" required>
                            </div>
                            <div class="col-md-6">
                                <label class="small fw-bold text-dark mb-1">Short Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control rounded-3 border-light-subtle px-3 py-2" id="new_leave_code"
                                    placeholder="e.g. SL" required>
                            </div>
                            <div class="col-md-6">
                                <label class="small fw-bold text-dark mb-1">Category</label>
                                <select class="form-select rounded-3 border-light-subtle px-3 py-2" id="new_leave_type">
                                    <option value="Paid">Paid</option>
                                    <option value="Unpaid">Unpaid</option>
                                    <option value="Restricted">Restricted</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="small fw-bold text-dark mb-1">Annual Cap (Days)</label>
                                <input type="number" class="form-control rounded-3 border-light-subtle px-3 py-2" id="new_max_days" value="0">
                            </div>
                            <div class="col-md-12">
                                <label class="small fw-bold text-dark mb-1">Carry Forward Policy</label>
                                <select class="form-select rounded-3 border-light-subtle px-3 py-2" id="new_carry_forward">
                                    <option value="Yes">Allow Carry Forward</option>
                                    <option value="No">No Carry Forward</option>
                                </select>
                            </div>
                            <div class="col-md-12" id="statusGroup" style="display: none;">
                                <label class="small fw-bold text-dark mb-1">Status</label>
                                <select class="form-select rounded-3 border-light-subtle px-3 py-2" id="new_status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Deactive</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="small fw-bold text-dark mb-1">Description (Optional)</label>
                                <textarea class="form-control rounded-3 border-light-subtle px-3 py-2" id="new_description" rows="3" placeholder="Brief explanation of the policy..."></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-light border-top-0 px-4 py-3" style="border-radius: 0 0 20px 20px;">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm" id="saveNewLeaveBtn" style="background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); border: none;">Create Type</button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
if (isset($_GET['vw']) == 1) {

} else {
    require('footer.php');
}
?>
<script type="text/javascript">
    // Initialize by showing the first card
    let companyid = $("body").data("companyid");
    let subcompanyid = $("body").data("subcompanyid");
    let projectid = $("body").data("projectid");

    loadLeaveTypes();

    $(document).on("click", "#addNewTypeBtn", function () {
        // Reset inputs in modal
        $("#edit_leave_id").val("0");
        $("#addLeaveForm")[0].reset();
        $("#statusGroup").hide();

        // Update modal title and save button text
        $("#leaveModalTitle span").text("Create Leave Type");
        $("#leaveModalTitle i").attr("class", "ti ti-circle-plus me-2 fs-22");
        $("#saveNewLeaveBtn").text("Create Type");
    });

    $(document).on("click", ".editleave", function () {
        let $strip = $(this).closest(".info-strip");
        let leaveid = $strip.data("leaveid");
        let leave_name = $strip.data("leavename");
        let leave_code = $strip.data("leavecode");
        let leave_type = $strip.data("leavetype");
        let max_days = $strip.data("maxdays");
        let carry_forward = $strip.data("carryforward");
        let description = $strip.data("description");
        let status = $strip.data("status") || "active";

        // Set inputs in modal
        $("#edit_leave_id").val(leaveid);
        $("#new_leave_name").val(leave_name);
        $("#new_leave_code").val(leave_code);
        $("#new_leave_type").val(leave_type);
        $("#new_max_days").val(max_days);
        $("#new_carry_forward").val(carry_forward);
        $("#new_description").val(description);
        $("#new_status").val(status);
        $("#statusGroup").show();

        // Update modal title and save button text
        $("#leaveModalTitle span").text("Edit Leave Type");
        $("#leaveModalTitle i").attr("class", "ti ti-edit me-2 fs-22");
        $("#saveNewLeaveBtn").text("Save Changes");

        // Open modal
        $("#addLeaveModal").modal("show");
    });

    $(document).on("click", "#saveNewLeaveBtn", function () {
        let leaveid = $("#edit_leave_id").val();
        let data = {
            leaveid: leaveid,
            leave_name: $("#new_leave_name").val().trim(),
            leave_code: $("#new_leave_code").val().trim(),
            leave_type: $("#new_leave_type").val(),
            max_days: $("#new_max_days").val(),
            carry_forward: $("#new_carry_forward").val(),
            description: $("#new_description").val(),
            status: $("#new_status").val(),
            action: (leaveid === "0" ? "add" : "edit")
        };

        if (!data.leave_name || !data.leave_code) {
            Swal.fire("Validation", "Leave Name and Code are required.", "warning");
            return;
        }

        Swal.fire({
            title: leaveid === "0" ? "Saving..." : "Updating...",
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        saveLeaveType(data, function (res) {
            Swal.close();
            if (res.status === "Success" || res.status === "success") {
                Swal.fire("Success", res.message, "success");
                $("#addLeaveModal").modal("hide");
                $("#addLeaveForm")[0].reset();
                loadLeaveTypes();
            } else {
                Swal.fire("Error", res.message, "error");
            }
        });
    });

    $(document).on("click", ".removeleave", function () {
        let $strip = $(this).closest(".info-strip");
        let leaveid = $strip.data("leaveid");
        Swal.fire({
            title: "Are you sure?",
            text: "This leave type will be removed.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            confirmButtonText: "Yes, Remove"
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: "Removing...",
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });
                
                saveLeaveType({
                    leaveid: leaveid,
                    action: "delete"
                }, function (response) {
                    Swal.close();
                    if (response.status === "success" || response.status === "Success") {
                        Swal.fire("Removed", "Leave type deleted.", "success");
                        loadLeaveTypes();
                    } else {
                        Swal.fire("Error", response.message || "Delete failed.", "error");
                    }
                });
            }
        });
    });

    $(document).on("click", ".togglestatus", function () {
        let $strip = $(this).closest(".info-strip");
        let leaveid = $strip.data("leaveid");
        let leave_name = $strip.data("leavename");
        let newstatus = $(this).data("newstatus");
        let statusText = (newstatus === "active" ? "activated" : "deactivated");
        let confirmBtnColor = (newstatus === "active" ? "#10b981" : "#f59e0b");

        Swal.fire({
            title: "Are you sure?",
            text: `"${leave_name}" will be ${statusText}.`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: confirmBtnColor,
            confirmButtonText: `Yes, ${newstatus === "active" ? "Activate" : "Deactivate"}`
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: "Updating Status...",
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                let data = {
                    leaveid: leaveid,
                    leave_name: $strip.data("leavename"),
                    leave_code: $strip.data("leavecode"),
                    leave_type: $strip.data("leavetype"),
                    max_days: $strip.data("maxdays"),
                    carry_forward: $strip.data("carryforward"),
                    description: $strip.data("description"),
                    status: newstatus,
                    action: "edit"
                };

                saveLeaveType(data, function (response) {
                    Swal.close();
                    if (response.status === "success" || response.status === "Success") {
                        Swal.fire("Updated!", `"${leave_name}" has been ${statusText}.`, "success");
                        loadLeaveTypes();
                    } else {
                        Swal.fire("Error", response.message || "Failed to update status.", "error");
                    }
                });
            }
        });
    });

    function saveLeaveType(data, callback) {
        let form_data = new FormData();
        let baseurl = $("body").data("routurl") || "";
        let companyid = $("body").data("companyid") || "";
        
        form_data.append("companyid", companyid);
        form_data.append("actiontodo", data.action || data.actiontodo || "add");
        form_data.append("leave_id", data.leaveid || data.leave_id || 0);
        form_data.append("leave_code", data.leave_code || "");
        form_data.append("leave_name", data.leave_name || "");
        form_data.append("leave_type", data.leave_type || "");
        form_data.append("max_days", data.max_days || 0);
        form_data.append("carry_forward", data.carry_forward || "");
        form_data.append("description", data.description || "");
        form_data.append("status", data.status || "active");
        $.ajax({
            type: "POST",
            url: baseurl + "modal/hrm_modal.php?action=saveUpdateLeaveType",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'JSON',
            success: function (res) {
                callback(res);
            },
            error: function () {
                callback({ status: "error", message: "Server communication failed." });
            }
        });
    }

    function loadLeaveTypes() {
        LeaveTypeMaster_callback('all', '', function (res) {
            let ltye = "";
            if (res && res.length > 0) {
                for (let i = 0; i < res.length; i++) {
                    let badgeClass = "badge-paid";
                    let leaveTypeLower = (res[i].leave_type || '').toLowerCase();
                    if (leaveTypeLower === "unpaid") {
                        badgeClass = "badge-unpaid";
                    } else if (leaveTypeLower === "restricted") {
                        badgeClass = "badge-restricted";
                    }

                    let isDeactive = false;
                    if ((res[i].status || '').toLowerCase() === "inactive") {
                        isDeactive = true;
                    }

                    let statusBtn = "";
                    if (isDeactive) {
                        statusBtn = `
                            <button class="btn btn-sm btn-outline-success rounded-pill px-3 togglestatus" data-newstatus="active">
                                <i class="fa fa-check me-1"></i> Activate
                            </button>
                        `;
                    } else {
                        statusBtn = `
                            <button class="btn btn-sm btn-outline-warning rounded-pill px-3 togglestatus" data-newstatus="inactive">
                                <i class="fa fa-ban me-1"></i> Deactivate
                            </button>
                        `;
                    }

                    ltye += `
                    <div class="info-strip position-relative mb-3 ${isDeactive ? 'strip-deactive' : ''}" data-leaveid="${res[i].id}" data-leavename="${res[i].leave_name}" data-leavecode="${res[i].leave_code}" data-leavetype="${res[i].leave_type}" data-maxdays="${res[i].max_days}" data-carryforward="${res[i].carry_forward}" data-description="${res[i].description || ''}" data-status="${res[i].status || 'active'}">
                        <div class="row align-items-center g-3">
                            <!-- Code Icon -->
                            <div class="col-auto">
                                <div class="info-strip-icon bg-soft-primary text-primary font-weight-bold" style="font-size: 1rem; width: 50px; height: 50px;">${res[i].leave_code}</div>
                            </div>
                            
                            <!-- Main Details -->
                            <div class="col">
                                <div class="row g-2">
                                    <div class="col-md-3 col-12">
                                        <div class="info-strip-label">Leave Name</div>
                                        <div class="info-strip-value">${res[i].leave_name}</div>
                                    </div>
                                    <div class="col-md-2 col-6">
                                        <div class="info-strip-label">Category</div>
                                        <div class="info-strip-value">
                                            <span class="badge-custom ${badgeClass}">${res[i].leave_type}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-2 col-6">
                                        <div class="info-strip-label">Annual Cap</div>
                                        <div class="info-strip-value">${res[i].max_days} Days</div>
                                    </div>
                                    <div class="col-md-2 col-6">
                                        <div class="info-strip-label">Carry Forward</div>
                                        <div class="info-strip-value">${res[i].carry_forward}</div>
                                    </div>
                                    <div class="col-md-3 col-12">
                                        <div class="info-strip-label">Description</div>
                                        <div class="info-strip-value text-muted" style="font-size: 0.85rem; font-weight: 500;">${res[i].description || '—'}</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Action buttons -->
                            <div class="col-auto d-flex gap-2">
                                <button class="btn btn-sm btn-outline-primary rounded-pill px-3 editleave">
                                    <i class="fa fa-edit me-1"></i> Edit
                                </button>
                                ${statusBtn}
                                <button class="btn btn-sm btn-outline-danger rounded-pill px-3 removeleave">
                                    <i class="fa fa-trash me-1"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                }
            } else {
                ltye = `<div class="alert alert-light text-center border-dashed py-5"><i class="ti ti-info-circle fs-30 text-muted d-block mb-2"></i>No Leave Types Configured Yet.</div>`;
            }
            $("#leaveTypeStripsWrapper").html(ltye);
        });
    }
</script>