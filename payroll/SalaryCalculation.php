<?php
if (isset($_GET['vw']) == 1) {
    $key = $_GET['k'];
    $rooturl = "";
} else {
    require('header.php');
}
?>
<style>
/* Light Soft Base Button Colors (Inactive State) */
.btn-step-attendance {
    background-color: #e8f5e9 !important;
    border: 1.5px solid #a5d6a7 !important;
    color: #1b5e20 !important;
}
.btn-step-attendance:hover {
    background-color: #c8e6c9 !important;
}

.btn-step-view {
    background-color: #e3f2fd !important;
    border: 1.5px solid #90caf9 !important;
    color: #0d47a1 !important;
}
.btn-step-view:hover {
    background-color: #bbdefb !important;
}

.btn-step-salary {
    background-color: #fff3e0 !important;
    border: 1.5px solid #ffcc80 !important;
    color: #e65100 !important;
}
.btn-step-salary:hover {
    background-color: #ffe0b2 !important;
}

.btn-step-reports {
    background-color: #f1f5f9 !important;
    border: 1.5px solid #cbd5e1 !important;
    color: #0f172a !important;
}
.btn-step-reports:hover {
    background-color: #e2e8f0 !important;
}

/* Base Button Structure */
.hrm-workflow-btn {
    position: relative;
    padding: 0.6rem 1.25rem;
    font-size: 0.95rem;
    font-weight: 600;
    border-radius: 8px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    cursor: pointer;
}

/* Active Dark Solid Color & Animation */
.hrm-workflow-btn.step-active {
    transform: translateY(-2px) scale(1.03);
    z-index: 5;
}

.btn-step-attendance.step-active {
    background-color: #00c853 !important;
    border-color: #00c853 !important;
    color: #ffffff !important;
    animation: pulseGlowGreen 1.8s infinite ease-in-out;
}

.btn-step-view.step-active {
    background-color: #1e88e5 !important;
    border-color: #1e88e5 !important;
    color: #ffffff !important;
    animation: pulseGlowBlue 1.8s infinite ease-in-out;
}

.btn-step-salary.step-active {
    background-color: #f4511e !important;
    border-color: #f4511e !important;
    color: #ffffff !important;
    animation: pulseGlowOrange 1.8s infinite ease-in-out;
}

.btn-step-reports.step-active {
    background-color: #111827 !important;
    border-color: #111827 !important;
    color: #ffffff !important;
    animation: pulseGlowDark 1.8s infinite ease-in-out;
}

@keyframes pulseGlowGreen {
    0% { box-shadow: 0 0 0 0 rgba(0, 200, 83, 0.6), 0 4px 12px rgba(0, 0, 0, 0.15); }
    50% { box-shadow: 0 0 0 10px rgba(0, 200, 83, 0), 0 8px 18px rgba(0, 0, 0, 0.25); }
    100% { box-shadow: 0 0 0 0 rgba(0, 200, 83, 0), 0 4px 12px rgba(0, 0, 0, 0.15); }
}

@keyframes pulseGlowBlue {
    0% { box-shadow: 0 0 0 0 rgba(30, 136, 229, 0.6), 0 4px 12px rgba(0, 0, 0, 0.15); }
    50% { box-shadow: 0 0 0 10px rgba(30, 136, 229, 0), 0 8px 18px rgba(0, 0, 0, 0.25); }
    100% { box-shadow: 0 0 0 0 rgba(30, 136, 229, 0), 0 4px 12px rgba(0, 0, 0, 0.15); }
}

@keyframes pulseGlowOrange {
    0% { box-shadow: 0 0 0 0 rgba(244, 81, 30, 0.6), 0 4px 12px rgba(0, 0, 0, 0.15); }
    50% { box-shadow: 0 0 0 10px rgba(244, 81, 30, 0), 0 8px 18px rgba(0, 0, 0, 0.25); }
    100% { box-shadow: 0 0 0 0 rgba(244, 81, 30, 0), 0 4px 12px rgba(0, 0, 0, 0.15); }
}

@keyframes pulseGlowDark {
    0% { box-shadow: 0 0 0 0 rgba(17, 24, 39, 0.7), 0 4px 12px rgba(0, 0, 0, 0.15); }
    50% { box-shadow: 0 0 0 10px rgba(17, 24, 39, 0), 0 8px 18px rgba(0, 0, 0, 0.25); }
    100% { box-shadow: 0 0 0 0 rgba(17, 24, 39, 0), 0 4px 12px rgba(0, 0, 0, 0.15); }
}

/* Active Step Badge Indicator */
.step-active-badge {
    font-size: 11px;
    padding: 2px 7px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.95);
    color: #111827;
    font-weight: 700;
    margin-left: 6px;
    display: inline-flex;
    align-items: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.step-active-dot {
    width: 7px;
    height: 7px;
    background-color: #00c853;
    border-radius: 50%;
    margin-right: 4px;
    display: inline-block;
    animation: activeDotPulse 1s infinite alternate;
}

@keyframes activeDotPulse {
    0% { opacity: 0.4; transform: scale(0.8); }
    100% { opacity: 1; transform: scale(1.2); }
}

/* Custom Red Scrollbar with Increased Width */
.hrm-table-wrapper::-webkit-scrollbar {
    width: 14px !important;
    height: 14px !important;
}

.hrm-table-wrapper::-webkit-scrollbar-track {
    background: #f1f5f9 !important;
    border-radius: 6px;
}

.hrm-table-wrapper::-webkit-scrollbar-thumb {
    background-color: #dc2626 !important; /* Red scrollbar thumb */
    border-radius: 6px;
    border: 2px solid #f1f5f9;
}

.hrm-table-wrapper::-webkit-scrollbar-thumb:hover {
    background-color: #991b1b !important;
}

/* Sticky Table Wrapper & Headers - Compact Fit to Page */
.hrm-table-wrapper {
    max-height: 72vh !important;
    overflow-y: auto !important;
    overflow-x: auto !important;
    position: relative !important;
    background: #ffffff;
    border-radius: 10px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
}

.hrm-table {
    border-collapse: separate !important;
    border-spacing: 0 !important;
    width: 100% !important;
    margin-bottom: 0 !important;
    table-layout: auto !important;
    border-left: 1px solid #cbd5e1 !important;
    border-top: 1px solid #cbd5e1 !important;
}

.hrm-table thead {
    position: sticky !important;
    top: 0 !important;
    z-index: 25 !important;
}

.hrm-table thead th {
    position: sticky !important;
    top: 0 !important;
    z-index: 25 !important;
    background-color: #f1f5f9 !important;
    color: #334155 !important;
    font-weight: 700 !important;
    font-size: 0.72rem !important;
    text-transform: uppercase;
    letter-spacing: 0.2px;
    padding: 0.35rem 0.2rem !important;
    vertical-align: middle !important;
    text-align: center !important;
    white-space: normal !important; /* Allow header text to wrap */
    word-wrap: break-word !important;
    line-height: 1.15 !important;
    border-right: 1px solid #cbd5e1 !important;
    border-bottom: 2px solid #94a3b8 !important;
    box-shadow: inset 0 -2px 0 #cbd5e1, 0 2px 5px rgba(0, 0, 0, 0.05) !important;
}

.hrm-table tbody td,
.hrm-table tfoot td {
    padding: 0.3rem 0.25rem !important;
    font-size: 0.8rem !important;
    vertical-align: middle !important;
    text-align: center !important;
    white-space: nowrap;
    border-right: 1px solid #cbd5e1 !important;
    border-bottom: 1px solid #cbd5e1 !important;
}

.hrm-table tbody tr:hover {
    background-color: #f8fafc !important;
}

.hrm-table th.th-employee,
.hrm-table thead th.th-employee,
.hrm-table td.employeename {
    text-align: left !important;
    white-space: normal !important;
    word-break: break-word;
    min-width: 140px;
    max-width: 250px;
    line-height: 1.3;
}

.hrm-table td.employeeid {
    font-weight: 600;
}

/* Advance Deduction Editable Column Styling */
.hrm-table th.th-advdeduction,
.hrm-table thead th.th-advdeduction {
    background-color: #fef3c7 !important;
    color: #92400e !important;
    border-bottom: 2px solid #f59e0b !important;
    box-shadow: inset 0 -2px 0 #f59e0b, 0 2px 5px rgba(0, 0, 0, 0.05) !important;
}

.hrm-table td.advdeduction {
    background-color: #fffbe6 !important;
    color: #92400e !important;
    font-weight: 700 !important;
    border: 1.5px dashed #f59e0b !important;
    border-radius: 4px;
    cursor: text;
    transition: all 0.2s ease-in-out;
}

.hrm-table td.advdeduction:hover {
    background-color: #fef08a !important;
    border-style: solid !important;
    border-color: #d97706 !important;
    box-shadow: inset 0 0 4px rgba(217, 119, 6, 0.25);
}

.hrm-table td.advdeduction:focus {
    background-color: #ffffff !important;
    border: 2px solid #f97316 !important;
    outline: none !important;
    box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.25) !important;
}

.hrm-table tfoot td#tblTotalAdv,
.hrm-table tfoot td.td-advdeduction-foot {
    background-color: #fef3c7 !important;
    color: #92400e !important;
    font-weight: 700 !important;
}

.hrm-table thead th.bg-success {
    background-color: #198754 !important;
    color: #ffffff !important;
}

.hrm-table thead th.bg-primary {
    background-color: #0d6efd !important;
    color: #ffffff !important;
}

.hrm-table thead th.bg-info {
    background-color: #0dcaf0 !important;
    color: #ffffff !important;
}

.hrm-table thead th.bg-dark {
    background-color: #212529 !important;
    color: #ffffff !important;
}

/* Attendance KPI Filter Cards Styling */
.att-filter-card {
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 10px;
    border-width: 1.5px !important;
    border-style: solid;
    position: relative;
    user-select: none;
}

.att-filter-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 14px rgba(0, 0, 0, 0.08) !important;
}

.att-filter-card.active {
    border-width: 2px !important;
    animation: attFilterPulse 2s infinite ease-in-out;
    transform: translateY(-2px);
}

@keyframes attFilterPulse {
    0% {
        box-shadow: 0 0 0 0 rgba(var(--active-rgb, 59, 130, 246), 0.75), 0 4px 10px rgba(0, 0, 0, 0.08);
        border-color: rgba(var(--active-rgb, 59, 130, 246), 1);
    }
    50% {
        box-shadow: 0 0 0 7px rgba(var(--active-rgb, 59, 130, 246), 0), 0 6px 14px rgba(0, 0, 0, 0.12);
        border-color: rgba(var(--active-rgb, 59, 130, 246), 1);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(var(--active-rgb, 59, 130, 246), 0), 0 4px 10px rgba(0, 0, 0, 0.08);
        border-color: rgba(var(--active-rgb, 59, 130, 246), 1);
    }
}
</style>

<div class="container-fluid mt-4">
    <!-- Main Calculation Input Card -->
    <div class="card hrm-card" id="calsalarcard">
        <div class="card-header">
            <h4><i class="ti ti-calculator me-2"></i>Employee Salary Calculation</h4>
        </div>
        <div class="card-body p-4">
            <div class="row align-items-end">
                <div class="col-md-6 mb-3">
                    <label for="AttendanceYearMonth" class="hrm-label">Choose Salary Month</label>
                    <select class="form-select hrm-select" id="AttendanceYearMonth"
                        onchange="checkPendingApprovalAndFetchAttendance('AttendanceYearMonth','attpreviewtbl');">
                    </select>
                </div>
                <div class="col-md-6 mb-3 d-none">
                    <!-- Reserved for future use -->
                </div>
            </div>

            <!-- Approval Status Warning / Ready Banner -->
            <div id="approvalStatusBanner" class="mt-2 d-none"></div>

            <!-- Action Buttons - Compact & Responsive -->
            <div class="row g-2 mt-3 d-none" id="actionbtngroup">
                <div class="col-auto">
                    <button class="btn btn-step-attendance hrm-workflow-btn" id="saveCalculatedAttendancebtn"
                        onclick="saveCalculatedAttendance('AttendanceYearMonth','attpreviewtbl');">
                        <i class="ti ti-device-floppy me-1"></i> Save Attendance
                    </button>
                </div>
                <div class="col-auto">
                    <button class="btn btn-step-view hrm-workflow-btn" id="showCalculatedSalarybtn"
                        onclick="showCalculatedSalary('AttendanceYearMonth','Salarycalculateddatatbl');">
                        <i class="ti ti-eye me-1"></i> View Salary
                    </button>
                </div>
                <div class="col-auto">
                    <button class="btn btn-step-salary hrm-workflow-btn" id="saveCalculatedSalarybtn"
                        onclick="saveCalculatedSalary('AttendanceYearMonth','Salarycalculateddatatbl');">
                        <i class="ti ti-cash me-1"></i> Save Salary
                    </button>
                </div>
                <div class="col-auto">
                    <button class="btn btn-step-reports hrm-workflow-btn" id="getSalaryReportsbtn"
                        onclick="getSalaryReports('AttendanceYearMonth')">
                        <i class="ti ti-file-report me-1"></i> Get Reports
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Calculated Attendance Card -->
    <div class="card hrm-card d-none" id="calattcard">
        <div class="card-header">
            <h4 class="mb-0"><i class="ti ti-calendar-check me-2"></i>Calculated Attendance <small class="ms-2"
                    id="calattlable"></small></h4>
            <button class="btn btn-light btn-sm shadow-sm text-primary fw-bold"
                onclick="exportSalaryToExcel('attpreviewtbl');">
                <i class="ti ti-file-spreadsheet me-1"></i> Export Excel
            </button>
        </div>
        <div class="card-body p-0">
            <!-- Attendance KPI Filter Cards -->
            <div id="attFilterCardsContainer" class="p-3 bg-light border-bottom">
                <div class="row g-2">
                    <!-- 1. All Attendance Employees -->
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card att-filter-card active shadow-sm h-100" data-filter="all" onclick="filterAttendanceGridByCard('all', this)" style="background-color: #eff6ff; border-color: #3b82f6; --active-rgb: 59, 130, 246;">
                            <div class="card-body p-2 text-center">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="small fw-semibold text-primary" style="font-size: 0.75rem;">All Attendance</span>
                                    <i class="ti ti-users text-primary fs-5"></i>
                                </div>
                                <h4 class="mb-0 fw-bold text-primary" id="cardCountAll">0</h4>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Att. Calculated -->
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card att-filter-card shadow-sm h-100" data-filter="calculated" onclick="filterAttendanceGridByCard('calculated', this)" style="background-color: #f0fdf4; border-color: #86efac; --active-rgb: 34, 197, 94;">
                            <div class="card-body p-2 text-center">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="small fw-semibold text-success" style="font-size: 0.75rem;">Att. Calculated</span>
                                    <i class="ti ti-circle-check text-success fs-5"></i>
                                </div>
                                <h4 class="mb-0 fw-bold text-success" id="cardCountCalculated">0</h4>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Att. Not Calculated -->
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card att-filter-card shadow-sm h-100" data-filter="not_calculated" onclick="filterAttendanceGridByCard('not_calculated', this)" style="background-color: #fffbeb; border-color: #fde047; --active-rgb: 234, 179, 8;">
                            <div class="card-body p-2 text-center">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="small fw-semibold text-warning" style="font-size: 0.75rem;">Not Calculated</span>
                                    <i class="ti ti-clock-pause text-warning fs-5"></i>
                                </div>
                                <h4 class="mb-0 fw-bold text-warning" id="cardCountNotCalculated">0</h4>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Late Deduction -->
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card att-filter-card shadow-sm h-100" data-filter="late_ded" onclick="filterAttendanceGridByCard('late_ded', this)" style="background-color: #fef2f2; border-color: #fca5a5; --active-rgb: 239, 68, 68;">
                            <div class="card-body p-2 text-center">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="small fw-semibold text-danger" style="font-size: 0.75rem;">Late Deduction</span>
                                    <i class="ti ti-clock-exclamation text-danger fs-5"></i>
                                </div>
                                <h4 class="mb-0 fw-bold text-danger" id="cardCountLateDed">0</h4>
                            </div>
                        </div>
                    </div>

                    <!-- 5. Extra Days -->
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card att-filter-card shadow-sm h-100" data-filter="extra_days" onclick="filterAttendanceGridByCard('extra_days', this)" style="background-color: #faf5ff; border-color: #d8b4fe; --active-rgb: 168, 85, 247;">
                            <div class="card-body p-2 text-center">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="small fw-semibold" style="font-size: 0.75rem; color: #7e22ce;">Extra Days (>0)</span>
                                    <i class="ti ti-calendar-plus fs-5" style="color: #7e22ce;"></i>
                                </div>
                                <h4 class="mb-0 fw-bold" style="color: #7e22ce;" id="cardCountExtraDays">0</h4>
                            </div>
                        </div>
                    </div>

                    <!-- 6. Leave > 0 -->
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card att-filter-card shadow-sm h-100" data-filter="leaves" onclick="filterAttendanceGridByCard('leaves', this)" style="background-color: #f0fdfa; border-color: #5eead4; --active-rgb: 20, 184, 166;">
                            <div class="card-body p-2 text-center">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="small fw-semibold" style="font-size: 0.75rem; color: #0f766e;">Leave > 0</span>
                                    <i class="ti ti-calendar-off fs-5" style="color: #0f766e;"></i>
                                </div>
                                <h4 class="mb-0 fw-bold" style="color: #0f766e;" id="cardCountLeaves">0</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Bar for Attendance Table -->
            <div class="p-3 bg-light border-bottom">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i
                                    class="ti ti-search text-muted"></i></span>
                            <input type="search" class="form-control border-start-0" placeholder="Search employees in calculated attendance..."
                                id="searchempsalcaltbl" oninput="applyAttendanceCombinedFilter();">
                        </div>
                    </div>
                </div>
            </div>
            <div class="hrm-table-wrapper">
                <table class="table hrm-table table-hover mb-0" id="attpreviewtbl">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 35px;">S.No</th>
                            <th style="min-width: 140px; text-align: left !important;" class="text-start th-employee">Employee Name</th>
                            <th>Gross Salary</th>
                            <th>Month Days</th>
                            <th>Present Days</th>
                            <th>Leave(Emergency)</th>
                            <th>Holidays</th>
                            <th>Week Off</th>
                            <th>Absent</th>
                            <th>Sandwich</th>
                            <th>LOP</th>
                            <th>Extra Days</th>
                            <th class="text-center">
                                <div class="d-flex align-items-center justify-content-center gap-1">
                                    <input type="checkbox" id="toggle-all-late-ded" checked class="form-check-input border-secondary" style="cursor:pointer; width: 15px; height: 15px;" title="Toggle Late Deduction for All Employees" onclick="toggleAllLateDeductions(this)">
                                    <span>Late Ded.</span>
                                </div>
                            </th>
                            <th>Net Payable</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot></tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Salary Calculation Table -->
    <div class="card hrm-card d-none" id="calsalarycard">
        <div class="card-header bg-info">
            <h4 class="mb-0 text-white"><i class="ti ti-report-money me-2"></i>Salary Calculations Audit</h4>
        </div>
        <div class="card-body p-0">
            <!-- Search Bar for Salary Audit -->
            <div class="p-3 bg-light border-bottom">
                <div class="row align-items-center justify-content-between g-2">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="ti ti-search text-muted"></i></span>
                            <input type="search" class="form-control border-start-0" placeholder="Search employees in salary audit..." id="searchcalsalarytbl" oninput="filterTable('#searchcalsalarytbl', '#Salarycalculateddatatbl'); if(typeof calculateSalaryAuditSummary==='function') calculateSalaryAuditSummary();">
                        </div>
                    </div>
                    <div class="col-md-8 text-end">
                        <button type="button" class="btn btn-outline-success btn-sm px-3 py-2 fw-semibold d-inline-flex align-items-center shadow-sm" id="btnExportSalaryCalcExcel" onclick="exportSalaryCalculationAuditExcel()">
                            <i class="ti ti-file-spreadsheet fs-5 me-2 text-success"></i> Export as Excel
                        </button>
                    </div>
                </div>
            </div>

            <!-- Audit KPI Summary Cards -->
            <div id="salaryAuditSummaryDiv" class="p-3 bg-light border-bottom">
                <div class="row g-3">
                    <!-- Attendance & Days Summary -->
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm h-100 bg-white">
                            <div class="card-body p-3">
                                <h6 class="text-uppercase text-muted fw-bold mb-2" style="font-size: 0.75rem; letter-spacing: 0.5px;">Attendance Summary</h6>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-dark small fw-semibold">Total Payable Days:</span>
                                    <span class="badge bg-primary text-white font-monospace" id="sumTotalDays">0</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-dark small fw-semibold">Zero (0) Days Count:</span>
                                    <span class="badge bg-danger text-white font-monospace" id="sumZeroDaysEmpCount">0</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Earnings Summary -->
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100 bg-white">
                            <div class="card-body p-3">
                                <h6 class="text-uppercase text-success fw-bold mb-2" style="font-size: 0.75rem; letter-spacing: 0.5px;"><i class="ti ti-currency-rupee me-1"></i>Earnings Summary</h6>
                                <div class="row g-1 small">
                                    <div class="col-6 d-flex justify-content-between">
                                        <span class="text-muted">Total Basic:</span>
                                        <span class="fw-bold" id="sumTotalBasic">₹0.00</span>
                                    </div>
                                    <div class="col-6 d-flex justify-content-between">
                                        <span class="text-muted">Total HRA:</span>
                                        <span class="fw-bold" id="sumTotalHRA">₹0.00</span>
                                    </div>
                                    <div class="col-6 d-flex justify-content-between">
                                        <span class="text-muted">Total Conv.:</span>
                                        <span class="fw-bold" id="sumTotalConv">₹0.00</span>
                                    </div>
                                    <div class="col-6 d-flex justify-content-between">
                                        <span class="text-muted">Total Allow.:</span>
                                        <span class="fw-bold" id="sumTotalAllow">₹0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Deductions & Net Summary -->
                    <div class="col-md-5">
                        <div class="card border-0 shadow-sm h-100 bg-white">
                            <div class="card-body p-3">
                                <h6 class="text-uppercase text-danger fw-bold mb-2" style="font-size: 0.75rem; letter-spacing: 0.5px;"><i class="ti ti-minus me-1"></i>Deductions & Net Summary</h6>
                                <div class="row g-1 small">
                                    <div class="col-4 d-flex justify-content-between">
                                        <span class="text-muted">EPF:</span>
                                        <span class="fw-bold" id="sumTotalEPF">₹0.00</span>
                                    </div>
                                    <div class="col-4 d-flex justify-content-between">
                                        <span class="text-muted">ESIC:</span>
                                        <span class="fw-bold" id="sumTotalESIC">₹0.00</span>
                                    </div>
                                    <div class="col-4 d-flex justify-content-between">
                                        <span class="text-muted">ITax:</span>
                                        <span class="fw-bold" id="sumTotalITax">₹0.00</span>
                                    </div>
                                    <div class="col-4 d-flex justify-content-between">
                                        <span class="text-muted">Advance:</span>
                                        <span class="fw-bold" id="sumTotalAdv">₹0.00</span>
                                    </div>
                                    <div class="col-4 d-flex justify-content-between">
                                        <span class="text-muted">Other:</span>
                                        <span class="fw-bold" id="sumTotalOther">₹0.00</span>
                                    </div>
                                    <div class="col-4 d-flex justify-content-between">
                                        <span class="text-dark fw-bold">Net Payable:</span>
                                        <span class="badge bg-success text-white" id="sumTotalNetPayable">₹0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="hrm-table-wrapper">
                <table class="table hrm-table table-hover mb-0" id="Salarycalculateddatatbl">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 35px;">S.No</th>
                            <th style="min-width: 140px; text-align: left !important;" class="text-start th-employee">Employee Name</th>
                            <th>Gross Salary</th>
                            <th>Days</th>
                            <th>Basic</th>
                            <th>HRA</th>
                            <th>Conv.</th>
                            <th>Allow.</th>
                            <th>EPF</th>
                            <th>ESIC</th>
                            <th>ITax</th>
                            <th class="th-advdeduction text-center" title="Editable Advance Deduction"><i class="ti ti-pencil me-1"></i>Adv. Deduct ✏️</th>
                            <th>Other</th>
                            <th class="bg-success text-white">Net Payable</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot></tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Reports Card -->
    <div class="card hrm-card d-none" id="reportsalarycard">
        <div class="card-header bg-dark">
            <h4><i class="ti ti-download me-2"></i>Download Reports</h4>
        </div>
        <div class="card-body p-0">
            <!-- Search Bar and Download Buttons Wrapper -->
            <div class="p-3 bg-light border-bottom">
                <div class="row align-items-center justify-content-between g-2">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="ti ti-search text-muted"></i></span>
                            <input type="search" class="form-control border-start-0" placeholder="Search employees in reports..." id="searchreporttbl" oninput="filterTable('#searchreporttbl', '#empPayslipsalry_tbl'); if(typeof calculateReportSummary==='function') calculateReportSummary();">
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="d-flex flex-wrap align-items-center justify-content-end gap-2" id="salaryReportButtonsWrapper">
                            <div class="exportsheet d-none">
                                <button class="btn btn-outline-success btn-sm px-3 py-2 fw-semibold d-inline-flex align-items-center shadow-sm"
                                    id="DownloadSalarySheet">
                                    <i class="ti ti-file-spreadsheet fs-5 me-2 text-success"></i> Salary Sheet (Excel)
                                </button>
                            </div>
                            <div class="exportsheet d-none">
                                <button class="btn btn-outline-success btn-sm px-3 py-2 fw-semibold d-inline-flex align-items-center shadow-sm"
                                    id="DownloadBankSalarySheet">
                                    <i class="ti ti-building-bank fs-5 me-2 text-success"></i> Salary Sheet for Bank
                                </button>
                            </div>
                            <div class="exportsheet d-none">
                                <button class="btn btn-outline-primary btn-sm px-3 py-2 fw-semibold d-inline-flex align-items-center shadow-sm"
                                    id="DownloadEPfChalantext">
                                    <i class="ti ti-file-text fs-5 me-2 text-primary"></i> EPF Chalan (TXT)
                                </button>
                            </div>
                            <div class="exportsheet d-none">
                                <button class="btn btn-outline-info btn-sm px-3 py-2 fw-semibold d-inline-flex align-items-center shadow-sm"
                                    id="DownloadEPfChalanexcel">
                                    <i class="ti ti-file-spreadsheet fs-5 me-2 text-info"></i> EPF Chalan (XLS)
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report KPI Summary Cards -->
            <div id="reportAuditSummaryDiv" class="p-3 bg-light border-bottom">
                <div class="row g-3">
                    <!-- Attendance & Days Summary -->
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm h-100 bg-white">
                            <div class="card-body p-3">
                                <h6 class="text-uppercase text-muted fw-bold mb-2" style="font-size: 0.75rem; letter-spacing: 0.5px;">Attendance Summary</h6>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-dark small fw-semibold">Total Payable Days:</span>
                                    <span class="badge bg-primary text-white font-monospace" id="rpt_sumTotalDays">0</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-dark small fw-semibold">Zero (0) Days Count:</span>
                                    <span class="badge bg-danger text-white font-monospace" id="rpt_sumZeroDaysEmpCount">0</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Earnings Summary -->
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100 bg-white">
                            <div class="card-body p-3">
                                <h6 class="text-uppercase text-success fw-bold mb-2" style="font-size: 0.75rem; letter-spacing: 0.5px;"><i class="ti ti-currency-rupee me-1"></i>Earnings Summary</h6>
                                <div class="row g-1 small">
                                    <div class="col-6 d-flex justify-content-between">
                                        <span class="text-muted">Total Basic:</span>
                                        <span class="fw-bold" id="rpt_sumTotalBasic">₹0.00</span>
                                    </div>
                                    <div class="col-6 d-flex justify-content-between">
                                        <span class="text-muted">Total HRA:</span>
                                        <span class="fw-bold" id="rpt_sumTotalHRA">₹0.00</span>
                                    </div>
                                    <div class="col-6 d-flex justify-content-between">
                                        <span class="text-muted">Total Conv.:</span>
                                        <span class="fw-bold" id="rpt_sumTotalConv">₹0.00</span>
                                    </div>
                                    <div class="col-6 d-flex justify-content-between">
                                        <span class="text-muted">Total Allow.:</span>
                                        <span class="fw-bold" id="rpt_sumTotalAllow">₹0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Deductions & Net Summary -->
                    <div class="col-md-5">
                        <div class="card border-0 shadow-sm h-100 bg-white">
                            <div class="card-body p-3">
                                <h6 class="text-uppercase text-danger fw-bold mb-2" style="font-size: 0.75rem; letter-spacing: 0.5px;"><i class="ti ti-minus me-1"></i>Deductions & Net Summary</h6>
                                <div class="row g-1 small">
                                    <div class="col-4 d-flex justify-content-between">
                                        <span class="text-muted">EPF:</span>
                                        <span class="fw-bold" id="rpt_sumTotalEPF">₹0.00</span>
                                    </div>
                                    <div class="col-4 d-flex justify-content-between">
                                        <span class="text-muted">ESIC:</span>
                                        <span class="fw-bold" id="rpt_sumTotalESIC">₹0.00</span>
                                    </div>
                                    <div class="col-4 d-flex justify-content-between">
                                        <span class="text-muted">ITax:</span>
                                        <span class="fw-bold" id="rpt_sumTotalITax">₹0.00</span>
                                    </div>
                                    <div class="col-4 d-flex justify-content-between">
                                        <span class="text-muted">Other:</span>
                                        <span class="fw-bold" id="rpt_sumTotalOther">₹0.00</span>
                                    </div>
                                    <div class="col-8 d-flex justify-content-between align-items-center">
                                        <span class="text-dark fw-bold">Net Total:</span>
                                        <span class="badge bg-success text-white" id="rpt_sumTotalNetPayable">₹0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="hrm-table-wrapper">
                <table class="table hrm-table table-hover mb-0" id="empPayslipsalry_tbl">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 35px;">S.No</th>
                            <th style="min-width: 140px; text-align: left !important;" class="text-start th-employee">Employee Name</th>
                            <th>Days</th>
                            <th>Basic</th>
                            <th>HRA</th>
                            <th>Conv.</th>
                            <th>Allow.</th>
                            <th>EPF</th>
                            <th>ESIC</th>
                            <th>ITax</th>
                            <th>Other</th>
                            <th class="bg-primary text-white">Net</th>
                            <th class="noexport">Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot></tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- Attendance Detail Modal -->
<div class="modal fade" id="attendanceDetailModal" tabindex="-1" aria-labelledby="attendanceDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title d-flex align-items-center" id="attendanceDetailModalLabel">
                    <i class="ti ti-calendar-event me-2 fs-4"></i>
                    <span>Attendance Details - </span>
                    <strong class="ms-1" id="modalEmpName">-</strong>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" style="background-color: #f8f9fa;">
                <!-- Summary Card Row -->
                <div class="row g-2 mb-4 text-center">
                    <div class="col-4 col-sm-2">
                        <div class="card border-0 shadow-sm h-100 bg-white">
                            <div class="card-body p-2">
                                <small class="text-muted d-block text-truncate">Present</small>
                                <h4 class="text-success mb-0 fw-bold" id="sumPresent">0</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-4 col-sm-2">
                        <div class="card border-0 shadow-sm h-100 bg-white">
                            <div class="card-body p-2">
                                <small class="text-muted d-block text-truncate">Half Day</small>
                                <h4 class="text-info mb-0 fw-bold" id="sumHalf">0</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-4 col-sm-2">
                        <div class="card border-0 shadow-sm h-100 bg-white">
                            <div class="card-body p-2">
                                <small class="text-muted d-block text-truncate">Absent</small>
                                <h4 class="text-danger mb-0 fw-bold" id="sumAbsent">0</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-4 col-sm-2">
                        <div class="card border-0 shadow-sm h-100 bg-white">
                            <div class="card-body p-2">
                                <small class="text-muted d-block text-truncate">Leave</small>
                                <h4 class="text-warning mb-0 fw-bold" id="sumLeave">0</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-4 col-sm-2">
                        <div class="card border-0 shadow-sm h-100 bg-white">
                            <div class="card-body p-2">
                                <small class="text-muted d-block text-truncate">Week Off</small>
                                <h4 class="text-secondary mb-0 fw-bold" id="sumWeekoff">0</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-4 col-sm-2">
                        <div class="card border-0 shadow-sm h-100 bg-white">
                            <div class="card-body p-2">
                                <small class="text-muted d-block text-truncate">Holiday</small>
                                <h4 class="text-primary mb-0 fw-bold" id="sumHoliday">0</h4>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Calculations Card -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 text-dark fw-bold"><i class="ti ti-calculator me-2"></i>Payable & Extra Days Calculation</h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3">
                            <div class="col-md-6 border-md-end">
                                <h6 class="text-primary fw-bold mb-2">Net Payable Days Calculation</h6>
                                <p class="mb-1 text-secondary fs-12">
                                    Formula: <code>min(Total Days, Present + (Half * 0.5) + Leave + Holiday + WeekOff)</code>
                                </p>
                                <div class="bg-light p-2 rounded fw-semibold text-dark fs-13" id="calcNetPayableText">
                                    -
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-primary fw-bold mb-2">Extra Days Worked Calculation</h6>
                                <p class="mb-1 text-secondary fs-12">
                                    Formula: <code>max(0, [Present + (Half * 0.5)] - [Total Days - (WeekOff + Holiday)])</code>
                                </p>
                                <div class="bg-light p-2 rounded fw-semibold text-dark fs-13" id="calcExtraDaysText">
                                    -
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Daily Breakdown Table -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 text-dark fw-bold"><i class="ti ti-list-check me-2"></i>Daily Breakdown for <span id="modalMonthYear" class="text-primary">-</span></h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-hover align-middle mb-0" id="modalAttTbl">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th class="ps-3" style="width: 150px;">Date</th>
                                        <th style="width: 100px;">Day</th>
                                        <th class="text-center" style="width: 120px;">Status</th>
                                        <th class="text-center" style="width: 120px;">In Time</th>
                                        <th class="text-center" style="width: 120px;">Out Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Populated dynamically via JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Late Days Details Modal -->
<div class="modal fade" id="lateDaysDetailModal" tabindex="-1" aria-labelledby="lateDaysDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-danger text-white py-3">
                <h5 class="modal-title d-flex align-items-center" id="lateDaysDetailModalLabel">
                    <i class="ti ti-clock-exclamation me-2 fs-4"></i>
                    <span>Late Attendance Details — </span>
                    <strong class="ms-1" id="lateModalEmpName">-</strong>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" style="background-color: #f8f9fa;">
                <!-- Late Summary Banner -->
                <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center justify-content-between mb-3" id="lateModalSummaryBanner">
                    <div>
                        <i class="ti ti-alert-triangle me-2 fs-5"></i>
                        <span id="lateModalSummaryText">Loading summary...</span>
                    </div>
                    <span class="badge bg-danger fs-6" id="lateModalDeductedBadge">0 Day Deducted</span>
                </div>

                <!-- Late Dates Table -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 text-dark fw-bold"><i class="ti ti-list-details me-2"></i>Late Check-In Breakdown</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-hover align-middle mb-0" id="lateModalTbl">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th class="ps-3" style="width: 50px;">S.No</th>
                                        <th style="width: 140px;">Date</th>
                                        <th style="width: 100px;">Day</th>
                                        <th class="text-center" style="width: 130px;">Shift In Time</th>
                                        <th class="text-center" style="width: 130px;">Actual Check-In</th>
                                        <th class="text-center" style="width: 130px;">Late By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Populated dynamically -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script type="text/javascript">
    // Initialize by showing the first card
    let companyid = $("body").data("companyid");
    let subcompanyid = $("body").data("subcompanyid");
    let projectid = $("body").data("projectid");
    EmployeeAttendanceYearMonth_callback("", "", function (res) {
        var atymlist = "";
        $("#AttendanceYearMonth").empty();
        if (res.length > 0) {
            if (res.length > 1) {
                atymlist += "<option value='' selected disabled>Choose Attendance Month</option>";
            }
            for (var i = 0; i < res.length; i++) {
                atymlist += "<option  data-salaryyear='" + res[i].AttendanceYear + "' value='" + res[i].MonthYear + "'>" + res[i].MonthYeardisp + "</option>";
            }
        }
        if (atymlist) {
            $("#AttendanceYearMonth").append(atymlist);
        }
    });
    AttendanceLocationMaster_callback("all", "all", function (res) {
        var lctlist = "";
        $("#attlocation").empty();
        if (res.length > 0) {
            if (res.length > 1) {
                lctlist += "<option value='' selected disabled>Choose Location</option>";
            }
            for (var i = 0; i < res.length; i++) {
                lctlist += "<option value='" + res[i].LocationId + "'>" + res[i].LocationName + "</option>";
            }
        }
        if (lctlist) {
            $("#attlocation").append(lctlist);
        }
    });
    $("#refreshLocation").on("click", function () {
        AttendanceLocationMaster_callback("all", "all", function (res) {
            var lctlist = "";
            $("#attlocation").empty();
            if (res.length > 0) {
                if (res.length > 1) {
                    lctlist += "<option value='' selected disabled>Choose Location</option>";
                }
                for (var i = 0; i < res.length; i++) {
                    lctlist += "<option value='" + res[i].LocationId + "'>" + res[i].LocationName + "</option>";
                }
            }
            if (lctlist) {
                $("#attlocation").append(lctlist);
            }
        });
    });

    $("#openLocationModal").on("click", function () {
        $("#locationAddModal").modal("show");
    });

    // $(document).ready(function () {
    //     const currentYearMonth = getCurrentYearMonth();
    //     $("#AttendanceYearMonth").val(currentYearMonth).trigger("change");
    // });


    $(document).on("click", "#DownloadSalarySheet", function () {
        var salaryyearmonghval = $("#AttendanceYearMonth").val();
        if (salaryyearmonghval) {
            var salaryyearmonghexp = salaryyearmonghval.split("-");
            var salaryYear = salaryyearmonghexp[0];
            var salaryMonth = salaryyearmonghexp[1];
        }
        if (salaryYear && salaryMonth) {
            Download_SalaryReportExcel(salaryYear, salaryMonth);
        }
    });

    $(document).on("click", "#DownloadBankSalarySheet", function () {
        var salaryyearmonghval = $("#AttendanceYearMonth").val();
        if (salaryyearmonghval) {
            var salaryyearmonghexp = salaryyearmonghval.split("-");
            var salaryYear = salaryyearmonghexp[0];
            var salaryMonth = salaryyearmonghexp[1];
        }
        if (salaryYear && salaryMonth) {
            Download_BankSalaryReportExcel(salaryYear, salaryMonth);
        }
    });

    $(document).on("click", "#DownloadEPfChalantext", function () {
        var salaryyearmonghval = $("#AttendanceYearMonth").val();
        if (salaryyearmonghval) {
            var salaryyearmonghexp = salaryyearmonghval.split("-");
            var salaryYear = salaryyearmonghexp[0];
            var salaryMonth = salaryyearmonghexp[1];
        }
        if (salaryYear && salaryMonth) {
            Download_EPFChalanTEXT(salaryYear, salaryMonth);
        }

    });

    $(document).on("click", "#DownloadEPfChalanexcel", function () {
        var salaryyearmonghval = $("#AttendanceYearMonth").val();
        if (salaryyearmonghval) {
            var salaryyearmonghexp = salaryyearmonghval.split("-");
            var salaryYear = salaryyearmonghexp[0];
            var salaryMonth = salaryyearmonghexp[1];
        }
        if (salaryYear && salaryMonth) {
            Download_EPFChalanEXCEL(salaryYear, salaryMonth);
        }
    });

    // Export Salary Calculation Audit Table to Excel
    // Export Salary Calculation Audit Table to Excel (Exact 13-column screen grid match)
    window.exportSalaryCalculationAuditExcel = function () {
        var $table = $("#Salarycalculateddatatbl");
        var $visibleRows = $table.find("tbody tr:visible");
        var $rows = $visibleRows.length > 0 ? $visibleRows : $table.find("tbody tr");

        // Filter out empty placeholder or no-data rows
        var $validRows = $rows.filter(function () {
            return $(this).find("td").length >= 14 && $(this).find("td[colspan]").length === 0;
        });

        if ($validRows.length === 0) {
            Swal.fire({
                icon: "warning",
                title: "No Data Available",
                text: "Please calculate or view salary data first before exporting."
            });
            return;
        }

        var ym = $("#AttendanceYearMonth").val() || "";
        var filename = "Salary_Calculations_Audit_" + (ym ? ym : new Date().toISOString().slice(0, 7)) + ".xls";

        // Read KPI summary values from screen
        var sumTotalDays = ($("#sumTotalDays").text() || "0").trim();
        var sumZeroDaysCount = ($("#sumZeroDaysEmpCount").text() || "0").trim();
        var sumTotalBasic = ($("#sumTotalBasic").text() || "₹0.00").replace(/[₹]/g, "").trim();
        var sumTotalHRA = ($("#sumTotalHRA").text() || "₹0.00").replace(/[₹]/g, "").trim();
        var sumTotalConv = ($("#sumTotalConv").text() || "₹0.00").replace(/[₹]/g, "").trim();
        var sumTotalAllow = ($("#sumTotalAllow").text() || "₹0.00").replace(/[₹]/g, "").trim();
        var sumTotalEPF = ($("#sumTotalEPF").text() || "₹0.00").replace(/[₹]/g, "").trim();
        var sumTotalESIC = ($("#sumTotalESIC").text() || "₹0.00").replace(/[₹]/g, "").trim();
        var sumTotalITax = ($("#sumTotalITax").text() || "₹0.00").replace(/[₹]/g, "").trim();
        var sumTotalAdv = ($("#sumTotalAdv").text() || "₹0.00").replace(/[₹]/g, "").trim();
        var sumTotalOther = ($("#sumTotalOther").text() || "₹0.00").replace(/[₹]/g, "").trim();
        var sumTotalNetPayable = ($("#sumTotalNetPayable").text() || "₹0.00").replace(/[₹]/g, "").trim();

        // Footer total numbers
        var footTotalGross = ($("#tblTotalGross").text() || "0.00").replace(/[₹,]/g, "").trim();
        var footTotalDays = ($("#tblTotalDays").text() || sumTotalDays).trim();
        var footTotalBasic = ($("#tblTotalBasic").text() || sumTotalBasic).replace(/[₹,]/g, "").trim();
        var footTotalHRA = ($("#tblTotalHRA").text() || sumTotalHRA).replace(/[₹,]/g, "").trim();
        var footTotalConv = ($("#tblTotalConv").text() || sumTotalConv).replace(/[₹,]/g, "").trim();
        var footTotalAllow = ($("#tblTotalAllow").text() || sumTotalAllow).replace(/[₹,]/g, "").trim();
        var footTotalEPF = ($("#tblTotalEPF").text() || sumTotalEPF).replace(/[₹,]/g, "").trim();
        var footTotalESIC = ($("#tblTotalESIC").text() || sumTotalESIC).replace(/[₹,]/g, "").trim();
        var footTotalITax = ($("#tblTotalITax").text() || sumTotalITax).replace(/[₹,]/g, "").trim();
        var footTotalAdv = ($("#tblTotalAdv").text() || sumTotalAdv).replace(/[₹,]/g, "").trim();
        var footTotalOther = ($("#tblTotalOther").text() || sumTotalOther).replace(/[₹,]/g, "").trim();
        var footTotalNet = ($("#tblTotalNetPayable").text() || sumTotalNetPayable).replace(/[₹,]/g, "").trim();

        // Build Excel Content matching screen grid
        var excelContent = `
            <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
            <head>
                <meta charset="utf-8">
                <!--[if gte mso 9]>
                <xml>
                    <x:ExcelWorkbook>
                        <x:ExcelWorksheets>
                            <x:ExcelWorksheet>
                                <x:Name>Salary Audit Grid</x:Name>
                                <x:WorksheetOptions>
                                    <x:DisplayGridlines/>
                                </x:WorksheetOptions>
                            </x:ExcelWorksheet>
                        </x:ExcelWorksheets>
                    </x:ExcelWorkbook>
                </xml>
                <![endif]-->
                <style>
                    body { font-family: Calibri, Arial, sans-serif; font-size: 11pt; }
                    table { border-collapse: collapse; width: 100%; margin-bottom: 12px; }
                    th { border: 1px solid #94a3b8; font-family: Calibri, Arial, sans-serif; font-size: 10pt; font-weight: bold; vertical-align: middle; padding: 6px; }
                    td { border: 1px solid #cbd5e1; font-family: Calibri, Arial, sans-serif; font-size: 10pt; vertical-align: middle; padding: 5px; }
                    .num { mso-number-format: "#,##0.00"; text-align: right; }
                    .int { mso-number-format: "0"; text-align: center; }
                    .days { mso-number-format: "0.0"; text-align: center; }
                    .text { mso-number-format: "\\@"; text-align: left; }
                    .text-center { text-align: center; }
                    .text-right { text-align: right; }
                    .text-left { text-align: left; }
                </style>
            </head>
            <body>
                <!-- Summary KPI Card Header -->
                <table border="1">
                    <tr>
                        <th colspan="14" style="background-color: #0284c7; color: #ffffff; font-size: 13pt; height: 36px; text-align: center;">
                            Salary Calculations Audit Summary (${ym || 'All Records'})
                        </th>
                    </tr>
                    <tr style="background-color: #f8fafc;">
                        <th colspan="3" style="background-color: #e0f2fe; color: #0369a1; text-align: center;">ATTENDANCE SUMMARY</th>
                        <th colspan="4" style="background-color: #dcfce7; color: #15803d; text-align: center;">EARNINGS SUMMARY</th>
                        <th colspan="7" style="background-color: #fee2e2; color: #b91c1c; text-align: center;">DEDUCTIONS & NET SUMMARY</th>
                    </tr>
                    <tr>
                        <td style="font-weight: bold; background-color: #f0f9ff;">Total Payable Days:</td>
                        <td colspan="2" class="days" style="font-weight: bold; background-color: #f0f9ff; color: #0284c7;">${sumTotalDays}</td>
                        <td style="font-weight: bold; background-color: #f0fdf4;">Total Basic:</td>
                        <td class="num" style="font-weight: bold; background-color: #f0fdf4;">${sumTotalBasic}</td>
                        <td style="font-weight: bold; background-color: #f0fdf4;">Total HRA:</td>
                        <td class="num" style="font-weight: bold; background-color: #f0fdf4;">${sumTotalHRA}</td>
                        <td style="font-weight: bold; background-color: #fef2f2;">EPF:</td>
                        <td class="num" style="font-weight: bold; background-color: #fef2f2;">${sumTotalEPF}</td>
                        <td style="font-weight: bold; background-color: #fef2f2;">ESIC:</td>
                        <td class="num" style="font-weight: bold; background-color: #fef2f2;">${sumTotalESIC}</td>
                        <td style="font-weight: bold; background-color: #fef2f2;">iTax:</td>
                        <td colspan="2" class="num" style="font-weight: bold; background-color: #fef2f2;">${sumTotalITax}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold; background-color: #f0f9ff;">Zero (0) Days Count:</td>
                        <td colspan="2" class="int" style="font-weight: bold; background-color: #f0f9ff; color: #dc2626;">${sumZeroDaysCount}</td>
                        <td style="font-weight: bold; background-color: #f0fdf4;">Total Conv.:</td>
                        <td class="num" style="font-weight: bold; background-color: #f0fdf4;">${sumTotalConv}</td>
                        <td style="font-weight: bold; background-color: #f0fdf4;">Total Allow.:</td>
                        <td class="num" style="font-weight: bold; background-color: #f0fdf4;">${sumTotalAllow}</td>
                        <td style="font-weight: bold; background-color: #fef2f2;">Advance:</td>
                        <td class="num" style="font-weight: bold; background-color: #fef2f2;">${sumTotalAdv}</td>
                        <td style="font-weight: bold; background-color: #fef2f2;">Other:</td>
                        <td class="num" style="font-weight: bold; background-color: #fef2f2;">${sumTotalOther}</td>
                        <td style="font-weight: bold; background-color: #15803d; color: #ffffff;">Net Payable:</td>
                        <td colspan="2" class="num" style="font-weight: bold; background-color: #15803d; color: #ffffff;">${sumTotalNetPayable}</td>
                    </tr>
                </table>

                <br/>

                <!-- Exact 14-Column Screen Grid Table -->
                <table border="1">
                    <thead>
                        <tr style="background-color: #0f766e; color: #ffffff; height: 32px;">
                            <th style="width: 45px; text-align: center; background-color: #0f766e; color: #ffffff;">S.NO</th>
                            <th style="min-width: 230px; text-align: left; background-color: #0f766e; color: #ffffff;">EMPLOYEE</th>
                            <th style="width: 90px; text-align: right; background-color: #0f766e; color: #ffffff;">GROSS SALARY</th>
                            <th style="width: 60px; text-align: center; background-color: #0f766e; color: #ffffff;">DAYS</th>
                            <th style="width: 90px; text-align: right; background-color: #0f766e; color: #ffffff;">BASIC</th>
                            <th style="width: 80px; text-align: right; background-color: #0f766e; color: #ffffff;">HRA</th>
                            <th style="width: 80px; text-align: right; background-color: #0f766e; color: #ffffff;">CONV.</th>
                            <th style="width: 80px; text-align: right; background-color: #0f766e; color: #ffffff;">ALLOW.</th>
                            <th style="width: 75px; text-align: right; background-color: #0f766e; color: #ffffff;">EPF</th>
                            <th style="width: 75px; text-align: right; background-color: #0f766e; color: #ffffff;">ESIC</th>
                            <th style="width: 75px; text-align: right; background-color: #0f766e; color: #ffffff;">ITAX</th>
                            <th style="width: 95px; text-align: right; background-color: #fef08a; color: #854d0e;">ADV. DEDUCT</th>
                            <th style="width: 75px; text-align: right; background-color: #0f766e; color: #ffffff;">OTHER</th>
                            <th style="width: 105px; text-align: right; background-color: #15803d; color: #ffffff;">NET PAYABLE</th>
                        </tr>
                    </thead>
                    <tbody>`;

        var rowCounter = 0;
        $validRows.each(function () {
            var $tr = $(this);
            var $tds = $tr.find("td");
            if ($tds.length < 14) return;

            rowCounter++;

            // Employee text matching screen display
            var empId = $tr.attr("data-employeeid") || $tr.data("employeeid") || "";
            var empName = $tr.attr("data-employeename") || $tr.data("employeename") || "";
            var empStatus = $tr.attr("data-calculatedstatus") || $tr.data("calculatedstatus") || "";

            var empDisplayText = "";
            if (empId && empName) {
                empDisplayText = empId + " - " + empName + (empStatus === "calculated" ? " [calculated]" : "");
            } else {
                var empCell = $tds.eq(1).clone();
                var hasCalculated = empCell.find(".badge-success, .badge:contains('calculated')").length > 0 || empCell.text().indexOf("calculated") !== -1;
                empCell.find("span.badge, sup, button, .badge").remove();
                var cleanEmp = empCell.text().replace(/\s+/g, " ").trim();
                empDisplayText = cleanEmp + (hasCalculated ? " [calculated]" : "");
            }

            var gross = parseFloat($tds.eq(2).text().replace(/[₹,]/g, "").trim()) || 0;
            var days = parseFloat($tds.eq(3).text().replace(/[₹,]/g, "").trim()) || 0;
            var basic = parseFloat($tds.eq(4).text().replace(/[₹,]/g, "").trim()) || 0;
            var hra = parseFloat($tds.eq(5).text().replace(/[₹,]/g, "").trim()) || 0;
            var conv = parseFloat($tds.eq(6).text().replace(/[₹,]/g, "").trim()) || 0;
            var allow = parseFloat($tds.eq(7).text().replace(/[₹,]/g, "").trim()) || 0;
            var epf = parseFloat($tds.eq(8).text().replace(/[₹,]/g, "").trim()) || 0;
            var esic = parseFloat($tds.eq(9).text().replace(/[₹,]/g, "").trim()) || 0;
            var itax = parseFloat($tds.eq(10).text().replace(/[₹,]/g, "").trim()) || 0;

            var advInput = $tds.eq(11).find("input");
            var advVal = advInput.length ? advInput.val() : $tds.eq(11).text();
            var adv = parseFloat((advVal || "0").replace(/[₹,]/g, "").trim()) || 0;

            var other = parseFloat($tds.eq(12).text().replace(/[₹,]/g, "").trim()) || 0;
            var net = parseFloat($tds.eq(13).text().replace(/[₹,]/g, "").trim()) || 0;

            excelContent += `
                <tr>
                    <td class="int">${rowCounter}</td>
                    <td class="text">${empDisplayText}</td>
                    <td class="num">${gross}</td>
                    <td class="days">${days}</td>
                    <td class="num">${basic}</td>
                    <td class="num">${hra}</td>
                    <td class="num">${conv}</td>
                    <td class="num">${allow}</td>
                    <td class="num">${epf}</td>
                    <td class="num">${esic}</td>
                    <td class="num">${itax}</td>
                    <td class="num" style="background-color: #fefce8;">${adv}</td>
                    <td class="num">${other}</td>
                    <td class="num" style="font-weight: bold; background-color: #dcfce7; color: #166534;">${net}</td>
                </tr>`;
        });

        // Exact 14-Column Footer Total Row
        excelContent += `
                    </tbody>
                    <tfoot>
                        <tr style="background-color: #f1f5f9; font-weight: bold; border-top: 2px solid #64748b; height: 30px;">
                            <td colspan="2" style="text-align: right; font-weight: bold; padding: 6px;">TOTAL (${rowCounter} Emp):</td>
                            <td class="num" style="font-weight: bold;">${footTotalGross}</td>
                            <td class="days" style="font-weight: bold;">${footTotalDays}</td>
                            <td class="num" style="font-weight: bold;">${footTotalBasic}</td>
                            <td class="num" style="font-weight: bold;">${footTotalHRA}</td>
                            <td class="num" style="font-weight: bold;">${footTotalConv}</td>
                            <td class="num" style="font-weight: bold;">${footTotalAllow}</td>
                            <td class="num" style="font-weight: bold;">${footTotalEPF}</td>
                            <td class="num" style="font-weight: bold;">${footTotalESIC}</td>
                            <td class="num" style="font-weight: bold;">${footTotalITax}</td>
                            <td class="num" style="font-weight: bold; background-color: #fef9c3;">${footTotalAdv}</td>
                            <td class="num" style="font-weight: bold;">${footTotalOther}</td>
                            <td class="num" style="font-weight: bold; background-color: #15803d; color: #ffffff;">${footTotalNet}</td>
                        </tr>
                    </tfoot>
                </table>
            </body>
            </html>`;

        var blob = new Blob(["\uFEFF" + excelContent], { type: "application/vnd.ms-excel;charset=utf-8" });
        var downloadUrl = URL.createObjectURL(blob);
        var a = document.createElement("a");
        a.href = downloadUrl;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(downloadUrl);

        Swal.fire({
            icon: "success",
            title: "Export Completed",
            text: "Salary calculation audit sheet downloaded successfully.",
            timer: 1500,
            showConfirmButton: false
        });
    };

    function checkPendingApprovalAndFetchAttendance(dropId, tblId) {
        dropId = dropId || 'AttendanceYearMonth';
        tblId = tblId || 'attpreviewtbl';
        let yearmonth = $("#" + dropId).val();
        if (!yearmonth) {
            $("#approvalStatusBanner").addClass("d-none").html("");
            $("#actionbtngroup").addClass("d-none");
            $("#calattcard").addClass("d-none");
            $("#calsalarycard").addClass("d-none");
            $("#reportsalarycard").addClass("d-none");
            return;
        }

        let baseurl = $("body").data("routurl") || "";
        let appKey = $("body").data("key") || (new URLSearchParams(window.location.search).get("k") || "");
        let keyParam = appKey ? ("?k=" + encodeURIComponent(appKey)) : "";
        let companyid = $("body").data("companyid");
        let subcompanyid = $("body").data("subcompanyid");
        let projectid = $("body").data("projectid");
        let monthDispText = $("#" + dropId + " option:selected").text() || yearmonth;

        let attUrl = baseurl + "employeeAttendanceApproval.php" + keyParam;
        let leaveUrl = baseurl + "employeeLeaveApproval.php" + keyParam;

        $.ajax({
            type: "POST",
            url: baseurl + "modal/hrm_salarymodal.php?action=checkPendingAttendanceAndLeave",
            data: {
                companyid: companyid,
                subcompanyid: subcompanyid,
                projectid: projectid,
                yearmonth: yearmonth
            },
            dataType: "JSON",
            success: function (res) {
                let banner = $("#approvalStatusBanner");
                banner.removeClass("d-none");

                if (res && res.isReady) {
                    // Success: Ready to calculate salary
                    banner.html(`
                        <div class="alert alert-success border-success shadow-sm rounded-3 p-3 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="ti ti-circle-check text-success fs-3 me-3"></i>
                                <div>
                                    <h5 class="alert-heading mb-0 text-success fw-bold">Ready to Calculate Salary</h5>
                                    <small class="text-muted">All attendance and leave requests for <strong>${monthDispText}</strong> have been approved.</small>
                                </div>
                            </div>
                        </div>
                    `);

                    // Proceed with fetching attendance calculation data
                    fetcheEmployeeCalculatedAttendance(dropId, tblId);
                    setActiveWorkflowStep(1);
                } else {
                    // Warning: Pending approvals exist -> Hide layouts and show warning
                    let pendingAtt = res ? (res.pendingAttendance || 0) : 0;
                    let pendingLeave = res ? (res.pendingLeaves || 0) : 0;

                    let listHtml = "";
                    if (pendingAtt > 0) {
                        listHtml += `<li><strong>${pendingAtt}</strong> Attendance approval request(s) pending</li>`;
                    }
                    if (pendingLeave > 0) {
                        listHtml += `<li><strong>${pendingLeave}</strong> Leave approval request(s) pending</li>`;
                    }

                    let buttonsHtml = "";
                    if (pendingAtt > 0) {
                        buttonsHtml += `<a href="${attUrl}" class="btn btn-sm btn-warning text-dark fw-bold shadow-sm me-2"><i class="ti ti-calendar-check me-1"></i> Review Attendance (${pendingAtt})</a>`;
                    }
                    if (pendingLeave > 0) {
                        buttonsHtml += `<a href="${leaveUrl}" class="btn btn-sm btn-warning text-dark fw-bold shadow-sm"><i class="ti ti-calendar-off me-1"></i> Review Leaves (${pendingLeave})</a>`;
                    }

                    banner.html(`
                        <div class="alert alert-warning border-warning shadow-sm rounded-3 p-3 mb-3">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div>
                                    <h5 class="alert-heading mb-1 text-dark fw-bold">
                                        <i class="ti ti-alert-triangle text-warning me-2 fs-4"></i>
                                        Pending Approvals Found — Cannot Calculate Salary
                                    </h5>
                                    <p class="mb-1 text-muted fs-13">
                                        There are pending requests for <strong>${monthDispText}</strong> that must be approved or rejected before salary can be calculated:
                                    </p>
                                    <ul class="mb-0 text-dark fs-13 ps-3">
                                        ${listHtml}
                                    </ul>
                                </div>
                                <div class="d-flex align-items-center">
                                    ${buttonsHtml}
                                </div>
                            </div>
                        </div>
                    `);

                    // Hide all calculation & report layouts
                    $("#actionbtngroup").addClass("d-none");
                    $("#calattcard").addClass("d-none");
                    $("#calsalarycard").addClass("d-none");
                    $("#reportsalarycard").addClass("d-none");
                }
            },
            error: function () {
                // Fallback to standard behavior if check fails
                fetcheEmployeeCalculatedAttendance(dropId, tblId);
                setActiveWorkflowStep(1);
            }
        });
    }

    // Step indicator manager
    function setActiveWorkflowStep(stepNum) {
        const steps = [
            { id: "saveCalculatedAttendancebtn", label: '<i class="ti ti-device-floppy me-1"></i> Save Attendance', step: 1, title: '<i class="ti ti-device-floppy me-2"></i>Save Attendance & Review Working Days' },
            { id: "showCalculatedSalarybtn", label: '<i class="ti ti-eye me-1"></i> View Salary', step: 2, title: '<i class="ti ti-eye me-2"></i>View Salary Calculations Audit' },
            { id: "saveCalculatedSalarybtn", label: '<i class="ti ti-cash me-1"></i> Save Salary', step: 3, title: '<i class="ti ti-cash me-2"></i>Save Processed Salary' },
            { id: "getSalaryReportsbtn", label: '<i class="ti ti-file-report me-1"></i> Get Reports', step: 4, title: '<i class="ti ti-file-report me-2"></i>Download Reports & Employee Payslips' }
        ];

        steps.forEach(s => {
            const btn = $("#" + s.id);
            btn.removeClass("step-active");
            btn.find(".step-active-badge").remove();
            btn.find(".step-completed-icon").remove();

            if (s.step < stepNum) {
                btn.html(s.label + ' <i class="ti ti-check step-completed-icon ms-1"></i>');
            } else if (s.step === stepNum) {
                btn.addClass("step-active");
                btn.html(s.label + ' <span class="step-active-badge"><span class="step-active-dot"></span>Active</span>');
                // Change card header as per active button action
                $("#calsalarcard .card-header h4").html(s.title);
            } else {
                btn.html(s.label);
            }
        });
    }

    $(document).on("click", "#saveCalculatedAttendancebtn", function () {
        setActiveWorkflowStep(2);
    });

    $(document).on("click", "#showCalculatedSalarybtn", function () {
        setActiveWorkflowStep(3);
    });

    // Attendance KPI Card Filter Handlers
    window.currentAttCardFilter = 'all';

    window.filterAttendanceGridByCard = function (filterType, cardElement) {
        window.currentAttCardFilter = filterType || 'all';
        $(".att-filter-card").removeClass("active");
        if (cardElement) {
            $(cardElement).addClass("active");
        } else {
            $(`.att-filter-card[data-filter="${window.currentAttCardFilter}"]`).addClass("active");
        }
        applyAttendanceCombinedFilter();
    };

    window.applyAttendanceCombinedFilter = function () {
        let filterType = window.currentAttCardFilter || 'all';
        let searchText = ($("#searchempsalcaltbl").val() || "").toLowerCase().trim();

        $("#attpreviewtbl tbody tr").each(function () {
            let $tr = $(this);
            let tds = $tr.find("td");
            if (tds.length < 13 || $tr.find("td[colspan]").length > 0) return;

            let isCalc = $tr.attr("data-is-calculated") === "1";
            let hasLate = $tr.attr("data-late-ded") === "1";
            let hasExtra = $tr.attr("data-extra-days") === "1";
            let hasLeaves = $tr.attr("data-leaves") === "1";

            let matchesCard = true;
            if (filterType === 'calculated') {
                matchesCard = isCalc;
            } else if (filterType === 'not_calculated') {
                matchesCard = !isCalc;
            } else if (filterType === 'late_ded') {
                matchesCard = hasLate;
            } else if (filterType === 'extra_days') {
                matchesCard = hasExtra;
            } else if (filterType === 'leaves') {
                matchesCard = hasLeaves;
            }

            let matchesSearch = true;
            if (searchText.length > 0) {
                matchesSearch = $tr.text().toLowerCase().indexOf(searchText) !== -1;
            }

            if (matchesCard && matchesSearch) {
                $tr.show();
            } else {
                $tr.hide();
            }
        });

        if (typeof calculateAttendanceSummary === 'function') {
            calculateAttendanceSummary();
        }
    };
</script>