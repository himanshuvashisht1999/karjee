<?php
include('header.php');
?>
<!-- Google Fonts & Bootstrap Icons -->
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    .premium-page {
        font-family: 'Outfit', sans-serif;
        background-color: #f8fafc;
        padding: 1.5rem;
    }
    .page-header-card {
        background: #111a2e !important;
        border-radius: 16px;
        padding: 1.25rem 1.5rem;
        color: white;
        border: none;
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.15);
    }
    .page-header-card h4 {
        font-weight: 700;
        margin: 0;
        letter-spacing: -0.5px;
        color: #ffffff !important;
    }
    .page-header-card p {
        color: #94a3b8 !important;
    }
    .page-header-card .btn-pdf {
        background-color: transparent !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        color: #94a3b8 !important;
        transition: all 0.2s;
    }
    .page-header-card .btn-pdf:hover {
        background-color: rgba(255, 255, 255, 0.05) !important;
        color: #ffffff !important;
        border-color: rgba(255, 255, 255, 0.6) !important;
    }
    .page-header-card .btn-excel {
        background-color: #f97316 !important;
        border: none !important;
        color: #ffffff !important;
        transition: all 0.2s;
    }
    .page-header-card .btn-excel:hover {
        background-color: #ea580c !important;
        color: #ffffff !important;
    }
    .stat-card {
        background: white;
        border-radius: 16px;
        border: 2px solid #e2e8f0;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
        padding: 1.25rem;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
    }
    .stat-card.active-filter {
        border-color: #2563eb !important;
        background-color: #f8fafc;
        box-shadow: 0 10px 25px rgba(37, 99, 235, 0.08);
    }
    .main-card {
        background: white;
        border-radius: 20px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
        overflow: hidden;
    }
    .search-wrapper {
        position: relative;
    }
    .search-wrapper i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 1.1rem;
    }
    .search-input {
        padding-left: 42px !important;
        height: 48px;
        border-radius: 12px;
        border: 1px solid #cbd5e1;
        font-size: 0.95rem;
        transition: all 0.2s ease;
    }
    .search-input:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    }
    .custom-table {
        margin-bottom: 0;
    }
    .custom-table th {
        background-color: #f8fafc;
        color: #475569;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        padding: 1rem 1.25rem;
        border-bottom: 2px solid #e2e8f0;
    }
    .custom-table td {
        padding: 1rem 1.25rem;
        vertical-align: middle;
        font-size: 0.9rem;
        color: #334155;
        border-bottom: 1px solid #f1f5f9;
    }
    .custom-table tr:last-child td {
        border-bottom: none;
    }
    /* Zebra Striping Design */
    .custom-table-striped tbody tr:nth-of-type(odd) {
        background-color: #f8fafc;
    }
    .custom-table-striped tbody tr:hover {
        background-color: #f1f5f9;
    }
    .btn-action {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        border: none;
    }
    .btn-reset-pwd {
        background-color: #eff6ff;
        color: #2563eb;
    }
    .btn-reset-pwd:hover {
        background-color: #2563eb;
        color: white;
    }
    .status-switch {
        cursor: pointer;
    }
    .badge-active {
        background-color: #dcfce7;
        color: #15803d;
        padding: 0.35rem 0.75rem;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.75rem;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .badge-inactive {
        background-color: #fee2e2;
        color: #b91c1c;
        padding: 0.35rem 0.75rem;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.75rem;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .info-label {
        font-size: 0.75rem;
        color: #64748b;
        font-weight: 500;
    }
    .info-value {
        font-weight: 600;
        color: #1e293b;
    }
    /* Modal premium styles */
    .premium-modal .modal-content {
        border-radius: 20px;
        border: none;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }
    .premium-modal .modal-header {
        border-bottom: 1px solid #f1f5f9;
        padding: 1.5rem;
    }
    .premium-modal .modal-footer {
        border-top: 1px solid #f1f5f9;
        padding: 1.25rem 1.5rem;
    }
</style>

<div class="premium-page container-fluid">
    <!-- Header Card -->
    <div class="page-header-card d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <h4>🔑 Employee Login & Security Accounts</h4>
            <p class="text-slate-400 mb-0 mt-1" style="font-size: 0.85rem; opacity: 0.85;">Manage login IDs, reset passwords, and activate/deactivate user accounts.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-pdf d-inline-flex align-items-center gap-2" style="border-radius: 12px; font-weight: 500;" onclick="exportEmployeePDF()">
                <i class="bi bi-file-earmark-pdf-fill"></i> Export PDF
            </button>
            <button class="btn btn-excel d-inline-flex align-items-center gap-2" style="border-radius: 12px; font-weight: 500;" onclick="exportEmployeeExcel()">
                <i class="bi bi-file-earmark-excel-fill"></i> Export Excel
            </button>
        </div>
    </div>

    <!-- Stats Widgets Row -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card d-flex align-items-center gap-3" id="btnFilterActive" onclick="setFilter('active')">
                <div class="bg-blue-50 text-blue-600 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: #eff6ff; color: #2563eb;">
                    <i class="bi bi-person-check-fill fs-5"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1" style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.5px;">Total Active Employee</h6>
                    <h4 class="mb-0 fw-bold text-primary" id="statActive">0</h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card d-flex align-items-center gap-3" id="btnFilterGenerated" onclick="setFilter('generated')">
                <div class="bg-green-50 text-green-600 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: #f0fdf4; color: #16a34a;">
                    <i class="bi bi-shield-lock-fill fs-5"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1" style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.5px;">Password Genrated</h6>
                    <h4 class="mb-0 fw-bold text-success" id="statPwdGenerated">0</h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card d-flex align-items-center gap-3" id="btnFilterMissing" onclick="setFilter('missing')">
                <div class="bg-amber-50 text-amber-600 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: #fffbeb; color: #d97706;">
                    <i class="bi bi-key-fill fs-5"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1" style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.5px;">Missing Password</h6>
                    <h4 class="mb-0 fw-bold" style="color: #d97706;" id="statPwdMissing">0</h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card d-flex align-items-center gap-3" id="btnFilterInactive" onclick="setFilter('inactive')">
                <div class="bg-red-50 text-red-600 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: #fef2f2; color: #dc2626;">
                    <i class="bi bi-person-x-fill fs-5"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1" style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.5px;">Inactive Employee</h6>
                    <h4 class="mb-0 fw-bold text-danger" id="statInactive">0</h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Card Container -->
    <div class="main-card card">
        <div class="card-body p-0">
            <!-- Search & Control Header -->
            <div class="p-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-3" style="background: #fafafb;">
                <div class="search-wrapper flex-grow-1" style="max-width: 450px;">
                    <i class="bi bi-search"></i>
                    <input type="search" class="form-control search-input" placeholder="Search by name, login ID, email, phone..." id="searchempmastertbl" oninput="filterTable('#searchempmastertbl', '#emplistLoginIdPasstbl');">
                </div>
            </div>

            <!-- Table Block -->
            <div class="table-responsive">
                <table class="table custom-table custom-table-striped" id="emplistLoginIdPasstbl">
                    <thead>
                        <tr>
                            <th>Employee Name</th>
                            <th>Account Details</th>
                            <th>Contact Info</th>
                            <th class="text-center">Status</th>
                            <th class="text-center no-export" style="width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-secondary">
                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                Fetching employee login list...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Premium Reset Password Modal -->
<div class="modal fade premium-modal" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="resetPasswordModalLabel">🔑 Reset Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary">Employee Name</label>
                    <input type="text" id="resetEmpName" class="form-control" readonly style="background-color: #f8fafc; font-weight: 500;">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary">Login ID</label>
                    <input type="text" id="resetEmpLoginId" class="form-control" readonly style="background-color: #f8fafc; font-weight: 500;">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary">New Password</label>
                    <div class="input-group">
                        <input type="text" id="resetNewPassword" class="form-control" placeholder="Enter new password">
                        <button class="btn btn-outline-secondary" type="button" onclick="generateRandomPassword()">
                            <i class="bi bi-shuffle me-1"></i> Auto
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius: 10px;">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitPasswordReset()" style="border-radius: 10px;">Save Password</button>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>

<script type="text/javascript">
var routurl = $("body").data("routurl");
var key = $("body").data("key");
var globalEmployeeData = [];
var currentFilterState = 'all'; // 'all', 'active', 'generated', 'missing', 'inactive'

function fetchAndRenderData() {
    fetchEmployeeLoginIdPassword_callback('employeelogindetails', '99', function(res) {
        globalEmployeeData = res;
        updateStatsAndRender();
    });
}

function updateStatsAndRender() {
    var activeCount = 0;
    var inactiveCount = 0;
    var pwdGeneratedCount = 0;
    var pwdMissingCount = 0;
    
    for (var i = 0; i < globalEmployeeData.length; i++) {
        var item = globalEmployeeData[i];
        var isActive = (item.isactive && item.isactive.toLowerCase() === 'yes');
        var pwd = (item.loginpassword || '').trim();
        var hasPwd = (pwd !== '' && pwd !== '-' && pwd !== 'N/A' && pwd.toLowerCase() !== 'null');

        if (isActive) {
            activeCount++;
        } else {
            inactiveCount++;
        }

        if (hasPwd) {
            pwdGeneratedCount++;
        } else {
            pwdMissingCount++;
        }
    }
    
    $("#statActive").text(activeCount);
    $("#statPwdGenerated").text(pwdGeneratedCount);
    $("#statPwdMissing").text(pwdMissingCount);
    $("#statInactive").text(inactiveCount);
    
    renderTable();
}

function renderTable() {
    var filteredData = globalEmployeeData.filter(function(item) {
        var isActive = (item.isactive && item.isactive.toLowerCase() === 'yes');
        var pwd = (item.loginpassword || '').trim();
        var hasPwd = (pwd !== '' && pwd !== '-' && pwd !== 'N/A' && pwd.toLowerCase() !== 'null');

        if (currentFilterState === 'active') return isActive;
        if (currentFilterState === 'inactive') return !isActive;
        if (currentFilterState === 'generated') return hasPwd;
        if (currentFilterState === 'missing') return !hasPwd;
        return true;
    });

    if (filteredData.length > 0) {
        var emplist = ``;
        for (var i = 0; i < filteredData.length; i++) {
            var item = filteredData[i];
            var isActive = (item.isactive && item.isactive.toLowerCase() === 'yes');

            var statusBadge = isActive 
                ? `<span class="badge-active status-switch" onclick="toggleStatus('${item.loginid}', 'no')"><i class="bi bi-check-circle-fill"></i> Active</span>`
                : `<span class="badge-inactive status-switch" onclick="toggleStatus('${item.loginid}', 'yes')"><i class="bi bi-x-circle-fill"></i> Inactive</span>`;

            var escapeName = (item.loginname || '').replace(/'/g, "\\'");
            var pwdDisplay = (item.loginpassword && item.loginpassword.trim() !== '' && item.loginpassword !== '-' && item.loginpassword !== 'N/A')
                ? item.loginpassword
                : '<span class="text-danger fw-normal fst-italic">Missing</span>';

            emplist += `<tr>
                <td class="fw-semibold text-slate-800">${item.loginname}</td>
                <td>
                    <div><span class="info-label">ID:</span> <span class="info-value font-monospace">${item.loginid}</span></div>
                    <div class="mt-1"><span class="info-label">PWD:</span> <span class="info-value font-monospace">${pwdDisplay}</span></div>
                </td>
                <td>
                    <div><span class="info-label">Phone:</span> <span class="info-value">${item.mobileno || 'N/A'}</span></div>
                    <div class="mt-1"><span class="info-label">Email:</span> <span class="info-value">${item.emailid || 'N/A'}</span></div>
                </td>
                <td class="text-center">${statusBadge}</td>
                <td class="text-center no-export">
                    <button class="btn-action btn-reset-pwd" title="Reset Password" onclick="openResetPasswordModal('${escapeName}', '${item.loginid}')">
                        <i class="bi bi-key-fill"></i>
                    </button>
                </td>
            </tr>`;
        }
        $("#emplistLoginIdPasstbl tbody").html(emplist);
    } else {
        var filterLabelMap = {
            'all': 'accounts',
            'active': 'active employees',
            'generated': 'accounts with generated passwords',
            'missing': 'accounts with missing passwords',
            'inactive': 'inactive employees'
        };
        var emptyMsg = 'No ' + (filterLabelMap[currentFilterState] || 'accounts') + ' available';
        $("#emplistLoginIdPasstbl tbody").html(`<tr><td colspan="5" class="text-center py-4 text-muted">${emptyMsg}</td></tr>`);
    }
    
    // Clear search filter input when filter state changes to prevent mismatches
    $("#searchempmastertbl").val('');
}

function setFilter(filterType) {
    if (currentFilterState === filterType) {
        currentFilterState = 'all';
    } else {
        currentFilterState = filterType;
    }
    
    // Manage active visual state of cards
    $(".stat-card").removeClass("active-filter");
    if (currentFilterState === 'active') {
        $("#btnFilterActive").addClass("active-filter");
    } else if (currentFilterState === 'generated') {
        $("#btnFilterGenerated").addClass("active-filter");
    } else if (currentFilterState === 'missing') {
        $("#btnFilterMissing").addClass("active-filter");
    } else if (currentFilterState === 'inactive') {
        $("#btnFilterInactive").addClass("active-filter");
    }
    
    renderTable();
}

// Initial load
$(document).ready(function() {
    fetchAndRenderData();
});

// Toggle User Status
function toggleStatus(loginid, targetStatus) {
    var title = targetStatus === 'yes' ? 'Activate Account?' : 'Deactivate Account?';
    var text = targetStatus === 'yes' ? 'This user will be allowed to log in.' : 'This user will be blocked from logging in.';
    var confirmColor = targetStatus === 'yes' ? '#16a34a' : '#d33';

    Swal.fire({
        title: title,
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: confirmColor,
        cancelButtonColor: '#64748b',
        confirmButtonText: targetStatus === 'yes' ? 'Yes, Activate' : 'Yes, Deactivate',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                type: "POST",
                url: routurl + "modal/hrm_modal.php?action=toggleEmployeeActiveStatus",
                data: { loginid: loginid, status: targetStatus },
                dataType: 'JSON',
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Status Updated',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        fetchAndRenderData();
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Failed to update status. Please try again.', 'error');
                }
            });
        }
    });
}

// Reset Password Open Modal
function openResetPasswordModal(name, loginid) {
    $("#resetEmpName").val(name);
    $("#resetEmpLoginId").val(loginid);
    $("#resetNewPassword").val('');
    $("#resetPasswordModal").modal('show');
}

// Generate Random Password
function generateRandomPassword() {
    var chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#$";
    var pass = "";
    for (var i = 0; i < 8; i++) {
        var rnum = Math.floor(Math.random() * chars.length);
        pass += chars.substring(rnum, rnum + 1);
    }
    $("#resetNewPassword").val(pass);
}

// Submit Password Reset
function submitPasswordReset() {
    var loginid = $("#resetEmpLoginId").val();
    var newPassword = $("#resetNewPassword").val();

    if (!newPassword || newPassword.trim() === '') {
        Swal.fire('Warning', 'Password field cannot be empty.', 'warning');
        return;
    }

    $.ajax({
        type: "POST",
        url: routurl + "modal/hrm_modal.php?action=resetEmployeePassword",
        data: { loginid: loginid, new_password: newPassword },
        dataType: 'JSON',
        success: function(response) {
            if (response.status === 'success') {
                $("#resetPasswordModal").modal('hide');
                Swal.fire({
                    icon: 'success',
                    title: 'Password Reset',
                    text: response.message,
                    timer: 2000,
                    showConfirmButton: false
                });
                fetchAndRenderData();
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function() {
            Swal.fire('Error', 'Failed to reset password. Please try again.', 'error');
        }
    });
}

// Filter table function
function filterTable(inputId, tableId) {
    var value = $(inputId).val().toLowerCase();
    $(tableId + " tbody tr").filter(function() {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
    });
}

// Helper to extract export dataset matching required headers
function getExportData() {
    var filteredData = globalEmployeeData.filter(function(item) {
        var isActive = (item.isactive && item.isactive.toLowerCase() === 'yes');
        var pwd = (item.loginpassword || '').trim();
        var hasPwd = (pwd !== '' && pwd !== '-' && pwd !== 'N/A' && pwd.toLowerCase() !== 'null');

        if (currentFilterState === 'active') return isActive;
        if (currentFilterState === 'inactive') return !isActive;
        if (currentFilterState === 'generated') return hasPwd;
        if (currentFilterState === 'missing') return !hasPwd;
        return true;
    });

    var searchVal = ($("#searchempmastertbl").val() || '').toLowerCase().trim();
    if (searchVal !== '') {
        filteredData = filteredData.filter(function(item) {
            var text = [
                item.employeeid || '',
                item.loginname || '',
                item.loginid || '',
                item.mobileno || ''
            ].join(' ').toLowerCase();
            return text.indexOf(searchVal) > -1;
        });
    }

    var exportRows = [];
    for (var i = 0; i < filteredData.length; i++) {
        var item = filteredData[i];
        var empId = item.employeeid || item.loginid || '';
        var empName = item.loginname || '';
        var loginId = item.loginid || '';
        var loginPwd = (item.loginpassword && item.loginpassword.trim() !== '' && item.loginpassword !== '-' && item.loginpassword !== 'N/A') ? item.loginpassword : '';
        var mobileNo = (item.mobileno && item.mobileno !== 'N/A') ? item.mobileno : '';

        exportRows.push([
            i + 1,
            empId,
            empName,
            loginId,
            loginPwd,
            mobileNo
        ]);
    }
    return exportRows;
}

// Export PDF
function exportEmployeePDF(titleText = "Login Id and Password of Employee") {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF("l", "mm", "a4");
    
    doc.setFontSize(14);
    doc.text(titleText, 14, 15);

    var headers = [["S.No", "Employee Id", "Employee Name", "Login ID", "Login Password", "Contact Mobile No"]];
    var dataRows = getExportData();

    doc.autoTable({
        head: headers,
        body: dataRows,
        startY: 22,
        theme: "grid",
        styles: {
            fontSize: 9,
            cellPadding: 3
        },
        headStyles: {
            fillColor: [30, 41, 59],
            textColor: [255, 255, 255],
            fontStyle: 'bold'
        },
        columnStyles: {
            0: { cellWidth: 15, halign: 'center' },
            1: { cellWidth: 35 },
            2: { cellWidth: 60 },
            3: { cellWidth: 45 },
            4: { cellWidth: 45 },
            5: { cellWidth: 45 }
        }
    });

    doc.save("loginidpasswordemployee.pdf");
}

// Export Excel
function exportEmployeeExcel(titleText = "Login Id and Password of Employee") {
    var headers = ["S.No", "Employee Id", "Employee Name", "Login ID", "Login Password", "Contact Mobile No"];
    var dataRows = getExportData();

    var worksheetData = [
        [titleText],
        [],
        headers
    ].concat(dataRows);

    var ws = XLSX.utils.aoa_to_sheet(worksheetData);

    ws['!cols'] = [
        { wch: 8 },  // S.No
        { wch: 18 }, // Employee Id
        { wch: 30 }, // Employee Name
        { wch: 22 }, // Login ID
        { wch: 22 }, // Login Password
        { wch: 22 }  // Contact Mobile No
    ];

    var wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Employees");
    XLSX.writeFile(wb, "loginidpasswordemployee.xlsx");
}
</script>
