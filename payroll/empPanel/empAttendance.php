<?php
include('header.php');
?>
<style>
/* Modern Attendance Strip Styles */
.attendance-strips-wrapper {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 16px !important;
    background-color: #f8fafc;
    border-radius: 12px;
}

.attendance-strip {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px 20px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    transition: all 0.2s ease-in-out;
    position: relative;
    border-left: 5px solid #cbd5e1; /* Default grey border */
}

/* Border colors based on status */
.attendance-strip.status-present {
    border-left-color: #10b981; /* Green */
}
.attendance-strip.status-mis {
    border-left-color: #f59e0b; /* Orange/Yellow */
}
.attendance-strip.status-absent {
    border-left-color: #ef4444; /* Red */
}
.attendance-strip.status-leave {
    border-left-color: #06b6d4; /* Teal/Blue */
}
.attendance-strip.status-weekoff {
    border-left-color: #6366f1; /* Indigo */
}
.attendance-strip.status-holiday {
    border-left-color: #475569; /* Dark slate */
}

.attendance-strip:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.05);
    border-color: #cbd5e1;
}

.attendance-strip.status-present:hover {
    border-color: #a7f3d0;
}
.attendance-strip.status-mis:hover {
    border-color: #fde68a;
}
.attendance-strip.status-absent:hover {
    border-color: #fca5a5;
}

.strip-label {
    font-size: 0.75rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.strip-value {
    font-size: 0.82rem;
    color: #1e293b;
}

/* Custom styles for Edit & Remove action buttons inside strip - badge sized */
.attendance-strip .EditAttendance,
.attendance-strip .RemoveAttendance {
    font-size: 0.75rem !important;
    padding: 3px 8px !important;
    font-weight: 600 !important;
    border-radius: 4px !important;
    line-height: 1.3 !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 3px !important;
    box-shadow: none !important;
    height: auto !important;
}

.attendance-strip .EditAttendance {
    background-color: #ffffff !important;
    border: 1px solid #fdba74 !important;
    color: #f97316 !important;
}

.attendance-strip .EditAttendance:hover {
    background-color: #fff7ed !important;
    border-color: #f97316 !important;
    color: #ea580c !important;
}

.attendance-strip .RemoveAttendance {
    background-color: #ffffff !important;
    border: 1px solid #fca5a5 !important;
    color: #ef4444 !important;
}

.attendance-strip .RemoveAttendance:hover {
    background-color: #fef2f2 !important;
    border-color: #ef4444 !important;
    color: #dc2626 !important;
}

/* Responsiveness for mobile screens */
@media (max-width: 767px) {
    .attendance-strips-wrapper {
        padding: 8px !important;
        gap: 10px;
    }
    .attendance-strip {
        padding: 14px 16px;
    }
    .attendance-strip .row > [class*="col-"] {
        margin-bottom: 8px;
    }
    .attendance-strip .row > [class*="col-"]:last-child {
        margin-bottom: 0;
    }
    .attendance-strip .text-end {
        text-align: left !important;
    }
    .attendance-strip .justify-content-end {
        justify-content: flex-start !important;
    }
}
</style>

<div class="content">
    <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
        <div class="my-auto mb-2">
            <h2 class="mb-1 d-flex align-items-center flex-wrap gap-2">
                <span class="text-dark fw-bold"><?php echo htmlspecialchars(!empty($EmployeeName) ? $EmployeeName : ($loginname ?? 'My Attendance')); ?></span>
                <span class="badge bg-soft-primary text-primary border border-primary px-3 py-1.5 fs-13 fw-semibold shadow-sm d-inline-flex align-items-center ms-md-2" id="liveClockBadge" style="border-radius: 20px;">
                    <i class="ti ti-clock me-1.5 fs-15"></i><span id="liveClockTime">--:--:-- --</span>
                </span>
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php?k=<?php echo $key; ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active text-primary" aria-current="page">Attendance Log</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex align-items-end flex-wrap gap-2 mb-2">
            <div class="dropdown me-md-1" style="flex: 1; min-width: 160px;">
                <label class="hrm-label mb-1 d-block fw-bold text-dark fs-12 uppercase">Select Month</label>
                <select class="form-select hrm-select w-100" id="attendanceyearmonth" style="height: 42px; border-radius: 8px;"></select>
            </div>
            <?php if (!$isResigned) { ?>
            <div>
                <button class="btn btn-primary shadow-sm hrm-action-btn d-inline-flex align-items-center justify-content-center" id="markMissingAttendancebtn" style="height: 42px; border-radius: 8px; padding: 0 16px;">
                    <i class="ti ti-circle-plus me-1 fs-16"></i> Add Missing
                </button>
            </div>
            <?php } ?>
        </div>
    </div>

    <!-- Days Info Row -->
    <div class="row mb-2">
        <div class="col-12">
            <span class="badge border px-3 py-2 w-sm-100 shadow-sm" id="btnFilterAll" style="cursor: pointer; transition: all 0.2s; background-color: #0f172a; border-color: #334155; color: #ffffff; font-size: 13px;">
                <i class="ti ti-calendar-event me-1 text-warning"></i> Days in Month: <span id="daysinMonth" style="color: #f97316; font-weight: 700;">0</span>
            </span>
        </div>
    </div>

    <!-- Attendance Summary Grid - Same Size Cards -->
    <div class="row g-2 mb-3">
        <!-- Present -->
        <div class="col-lg-2 col-md-4 col-4">
            <div class="card hrm-card bg-soft-success border-success text-center h-100" id="btnFilterPresent" style="cursor: pointer; transition: all 0.2s;">
                <div class="card-body p-2 d-flex flex-column justify-content-center">
                    <p class="text-success fw-bold mb-1 fs-10 uppercase">PRESENT</p>
                    <div id="presentDays" class="lh-1">0</div>
                </div>
            </div>
        </div>

        <!-- MIS -->
        <div class="col-lg-2 col-md-4 col-4">
            <div class="card hrm-card bg-soft-warning border-warning text-center h-100" id="btnFilterMIS" style="cursor: pointer; transition: all 0.2s;">
                <div class="card-body p-2 d-flex flex-column justify-content-center">
                    <p class="text-warning fw-bold mb-1 fs-10">MIS</p>
                    <h4 id="misDays" class="mb-0 fs-18">0</h4>
                </div>
            </div>
        </div>

        <!-- Absent -->
        <div class="col-lg-2 col-md-4 col-4">
            <div class="card hrm-card bg-soft-danger border-danger text-center h-100" id="btnFilterAbsent" style="cursor: pointer; transition: all 0.2s;">
                <div class="card-body p-2 d-flex flex-column justify-content-center">
                    <p class="text-danger fw-bold mb-1 fs-10">ABSENT</p>
                    <h4 id="absentDays" class="mb-0 fs-18">0</h4>
                </div>
            </div>
        </div>

        <!-- On Leave -->
        <div class="col-lg-2 col-md-4 col-4">
            <div class="card hrm-card bg-soft-info border-info text-center h-100" id="btnFilterLeave" style="cursor: pointer; transition: all 0.2s;">
                <div class="card-body p-2 d-flex flex-column justify-content-center">
                    <p class="text-info fw-bold mb-1 fs-10">LEAVE</p>
                    <h4 id="onleavesDays" class="mb-0 fs-18">0</h4>
                </div>
            </div>
        </div>

        <!-- Week Off -->
        <div class="col-lg-2 col-md-4 col-4">
            <div class="card hrm-card bg-soft-secondary border-secondary text-center h-100" id="btnFilterWeekOff" style="cursor: pointer; transition: all 0.2s;">
                <div class="card-body p-2 d-flex flex-column justify-content-center">
                    <p class="text-secondary fw-bold mb-1 fs-10">WEEK OFF</p>
                    <h4 id="weekoffDays" class="mb-0 fs-18">0</h4>
                </div>
            </div>
        </div>

        <!-- Holidays -->
        <div class="col-lg-2 col-md-4 col-4">
            <div class="card hrm-card bg-soft-dark border-dark text-center h-100" id="btnFilterHoliday" style="cursor: pointer; transition: all 0.2s;">
                <div class="card-body p-2 d-flex flex-column justify-content-center">
                    <p class="text-dark fw-bold mb-1 fs-10">HOLIDAY</p>
                    <h4 id="holyDays" class="mb-0 fs-18">0</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card hrm-card mt-3">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="mb-0"><i class="ti ti-history me-2"></i>Attendance Log Details</h4>
            <div class="d-flex align-items-center gap-2">
                <div class="dropdown">
                    <button
                        class="btn btn-light btn-sm shadow-sm dropdown-toggle d-inline-flex align-items-center fw-bold text-primary"
                        data-bs-toggle="dropdown">
                        <i class="ti ti-file-export me-1"></i> Export
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 p-2">
                        <li><button class="dropdown-item rounded-1" onclick="exportEmployeePDF()"><i
                                    class="ti ti-file-type-pdf me-2 text-danger"></i>Export as PDF</button></li>
                        <li><button class="dropdown-item rounded-1" onclick="exportEmployeeExcel()"><i
                                    class="ti ti-file-type-xls me-2 text-success"></i>Export as Excel</button></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="row px-3 pt-3">
                <div class="col-12 d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-soft-success border text-dark px-3 py-2">
                            <i class="ti ti-calendar-event me-1"></i> Full Day Required Hours: <span id="fulldayhours" class="fw-bold text-primary">0</span>
                        </span>
                        <span class="badge bg-soft-success border text-dark px-3 py-2">
                            <i class="ti ti-calendar-event me-1"></i> Half Day Required Hours: <span id="halfdayhours" class="fw-bold text-primary">0</span>
                        </span>
                    </div>
                    <?php if ($loginroleid > 3): ?>
                    <div class="alert alert-warning mb-0 py-1.5 px-3 border-warning d-inline-flex align-items-center" style="border-radius: 8px; background-color: #fffbe6; color: #d97706; border: 1px solid #fde68a;">
                        <i class="ti ti-alert-triangle me-2 fs-16" style="color: #d97706;"></i>
                        <span class="fw-bold fs-12">Note: Only Last 3 days attendance can edit</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div id="empAttendanceStripsWrapper" class="attendance-strips-wrapper">
                <!-- Strips populated via AJAX -->
            </div>
        </div>
    </div>
</div>
<!-- Edit Attendance -->
<div class="modal fade" id="edit_attendance" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">

            <!-- HEADER -->
            <div class="modal-header header-border align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <h5 class="modal-title">
                        <i class="ti ti-edit-circle me-1"></i>
                        Edit Attendance
                    </h5>
                    <span class="badge bg-light text-dark">
                        <i class="ti ti-calendar me-1"></i>
                        Date: <span id="editattdatespan"></span>
                    </span>
                </div>

                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>

            <div class="add-info-fieldset">
                <div class="modal-body">

                    <!-- WARNING NOTE -->
                    <div class="alert alert-warning d-flex align-items-start">
                        <i class="ti ti-alert-triangle me-2 mt-1"></i>
                        <div>
                            <strong>Important Note</strong><br>
                            Attendance time must be maintained in
                            <strong>24-hour format (HH:MM)</strong>.
                            Any modification will be sent for
                            <strong>manager approval</strong>.
                            <br>
                            <small class="text-muted">
                                Example: 09:30 – 18:00
                            </small>
                        </div>
                    </div>

                    <!-- ATTENDANCE TABLE -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive">

                                <table class="table table-bordered align-middle" id="editatttbl">
                                    <thead class="table-light">
                                        <tr>
                                            <th>
                                                <i class="ti ti-login me-1"></i>
                                                Mark In Time
                                            </th>
                                            <th>
                                                <i class="ti ti-logout me-1"></i>
                                                Mark Out Time
                                            </th>
                                            <th>
                                                <i class="ti ti-clock-hour-4 me-1"></i>
                                                Duration
                                            </th>
                                            <th class="text-center" style="width: 90px;">
                                                <i class="ti ti-settings me-1"></i>
                                                Action
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>

                                <!-- TOTAL HOURS -->
                                <div id="totalWorkingHours" class="fw-bold text-end mt-2 text-primary">
                                    <i class="ti ti-clock-hour-4 me-1"></i>
                                    Total Working Hours: 00:00
                                </div>

                            </div>
                        </div>

                        <!-- REMARKS -->
                        <div class="col-md-12 mt-3">
                            <label class="form-label">
                                <i class="ti ti-message me-1"></i>
                                Remarks / Reason for Edit
                            </label>
                            <textarea rows="3" class="form-control" id="editattremark"
                                placeholder="Enter reason for attendance modification"></textarea>
                        </div>

                        <!-- PROOF FILE -->
                        <div class="col-md-12 mt-3">
                            <label class="form-label">
                                <i class="ti ti-file me-1"></i>
                                Attendance Proof File (PDF or Image)
                            </label>
                            <input type="file" class="form-control" id="editattprooffile" accept=".pdf,image/*" />
                            <small class="text-muted d-block mt-1" id="currentProofFileText"></small>
                        </div>
                    </div>

                </div>

                <!-- FOOTER -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">
                        <i class="ti ti-x me-1"></i>
                        Cancel
                    </button>

                    <button class="btn btn-primary" type="button" id="updateEditAttendancebtn" onclick="UpdateEmployeeEditedAttendance(
                                'editatttbl',
                                'editattremark',
                                'updateEditAttendancebtn'
                            )">
                        <i class="<?= ($loginroleid <= 3) ? 'ti ti-check me-1' : 'ti ti-send me-1' ?>"></i>
                        <?= ($loginroleid <= 3) ? 'Update & Approve' : 'Send for Approval' ?>
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>
<!-- /Edit Attendance -->

<!-- ADD MISSING ATTENDANCE MODAL -->
<div class="modal fade" id="add_attendance" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">

            <!-- HEADER -->
            <div class="modal-header header-border justify-content-between">
                <h5 class="modal-title">
                    <i class="ti ti-calendar-time me-1"></i>
                    Add Missing Attendance
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <!-- BODY -->
            <div class="modal-body">

                <!-- INFO MESSAGE -->
                <div class="alert d-flex align-items-start" style="background-color: #fff9e6; border: 1.5px solid #ffeeba; border-radius: 6px; padding: 12px 16px;">
                    <i class="ti ti-alert-triangle me-2 mt-1" style="color: #d97706; font-size: 20px; flex-shrink: 0;"></i>
                    <div>
                        <strong style="color: #d97706; font-size: 14px;">Important Attendance Note</strong><br>
                        <span style="color: #d97706; font-size: 13px; font-weight: 500;">Enter attendance time strictly in <span style="font-weight: 700;">24-hour format (HH:MM)</span>. AM/PM or incorrect entries will not be accepted.</span>
                        <br>
                        <span style="color: #8a7350; font-size: 12px; display: inline-block; margin-top: 4px;">Example: 09:30, 14:45, 18:00</span>
                    </div>
                </div>

                <!-- INPUT ROW -->
                <div class="row">

                    <!-- DATE -->
                    <div class="col-md-4 mb-2">
                        <label class="form-label">
                            <i class="ti ti-calendar me-1"></i> Attendance Date
                        </label>
                        <input type="date" class="form-control" id="attendanceDate"
                            <?= ($loginroleid <= 3) ? '' : 'min="' . date('Y-m-d', strtotime('-3 days')) . '"' ?> max="<?= date('Y-m-d') ?>">
                    </div>

                    <!-- MARK IN -->
                    <div class="col-md-4 mb-2">
                        <label class="form-label">
                            <i class="ti ti-login me-1"></i> Mark In Time
                        </label>
                        <input type="time" class="form-control" id="markininput">
                        <small class="text-danger d-none" id="markinError">
                            <i class="ti ti-alert-circle me-1"></i>
                            Mark In time is required
                        </small>
                    </div>

                    <!-- MARK OUT -->
                    <div class="col-md-4 mb-2">
                        <label class="form-label">
                            <i class="ti ti-logout me-1"></i> Mark Out Time
                        </label>
                        <input type="time" class="form-control" id="markoutinput">
                        <small class="text-danger d-none" id="markoutError">
                            <i class="ti ti-alert-circle me-1"></i>
                            Mark Out time must be after Mark In
                        </small>
                    </div>
                </div>

                <!-- TOTAL HOURS -->
                <div class="row mt-2">
                    <div class="col-md-4">
                        <label class="form-label">
                            <i class="ti ti-clock-hour-4 me-1"></i>
                            Total Working Hours
                        </label>
                        <input type="text" class="form-control" id="totalHours" readonly placeholder="00:00">
                    </div>
                </div>

                <!-- REMARKS -->
                <div class="row mt-2">
                    <div class="col-md-12">
                        <label class="form-label">
                            <i class="ti ti-message me-1"></i> Remarks / Reason
                        </label>
                        <textarea class="form-control" rows="3" id="addattremark"
                            placeholder="Enter reason for missing attendance"></textarea>
                    </div>
                </div>

                <!-- PROOF FILE -->
                <div class="row mt-2">
                    <div class="col-md-12">
                        <label class="form-label">
                            <i class="ti ti-file me-1"></i> Attendance Proof File (PDF or Image)
                        </label>
                        <input type="file" class="form-control" id="addattprooffile" accept=".pdf,image/*" />
                    </div>
                </div>

            </div>

            <!-- FOOTER -->
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i> Cancel
                </button>

                <button class="btn btn-primary" id="updateMissingAttendancebtn" disabled onclick="UpdateEmployeeMissingAttendance(
                            'attendanceDate',
                            'markininput',
                            'markoutinput',
                            'addattremark',
                            'updateMissingAttendancebtn'
                        )">
                    <i class="<?= ($loginroleid <= 3) ? 'ti ti-check me-1' : 'ti ti-send me-1' ?>"></i>
                    <?= ($loginroleid <= 3) ? 'Save & Approve' : 'Send for Approval' ?>
                </button>
            </div>

        </div>
    </div>
</div>
<?php include('footer.php'); ?>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>
<script>
    const markIn = document.getElementById("markininput");
    const markOut = document.getElementById("markoutinput");
    const totalHours = document.getElementById("totalHours");
    const submitBtn = document.getElementById("updateMissingAttendancebtn");

    const markInErr = document.getElementById("markinError");
    const markOutErr = document.getElementById("markoutError");

    function timeToMinutes(time) {
        const [h, m] = time.split(":").map(Number);
        return (h * 60) + m;
    }

    function minutesToHHMM(mins) {
        const h = String(Math.floor(mins / 60)).padStart(2, "0");
        const m = String(mins % 60).padStart(2, "0");
        return `${h}:${m}`;
    }

    function validateAttendance() {
        let valid = true;

        markInErr.classList.add("d-none");
        markOutErr.classList.add("d-none");
        totalHours.value = "";

        if (!markIn.value) {
            markInErr.classList.remove("d-none");
            valid = false;
        }

        if (!markOut.value) {
            markOutErr.textContent = "Mark Out time is required";
            markOutErr.classList.remove("d-none");
            valid = false;
        }

        if (markIn.value && markOut.value) {
            const inMin = timeToMinutes(markIn.value);
            const outMin = timeToMinutes(markOut.value);

            if (outMin <= inMin) {
                markOutErr.textContent = "Mark Out must be after Mark In";
                markOutErr.classList.remove("d-none");
                valid = false;
            } else {
                const diffMins = outMin - inMin;
                if (diffMins >= 720) {
                    markOutErr.textContent = "Duration must be less than 12 hours";
                    markOutErr.classList.remove("d-none");
                    valid = false;
                } else {
                    totalHours.value = minutesToHHMM(diffMins);
                }
            }
        }

        submitBtn.disabled = !valid;
    }

    markIn.addEventListener("change", validateAttendance);
    markOut.addEventListener("change", validateAttendance);
</script>

<script type="text/javascript">
    var routurl = $("body").data("routurl");
    var key = $("body").data("key");
    let empid = $("body").data("empid");

    function updateLiveClock() {
        const now = new Date();
        let hours = now.getHours();
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12; // convert 0 to 12
        const strHours = String(hours).padStart(2, '0');
        const timeStr = `${strHours}:${minutes}:${seconds} ${ampm}`;
        const clockEl = document.getElementById("liveClockTime");
        if (clockEl) {
            clockEl.textContent = timeStr;
        }
    }
    updateLiveClock();
    setInterval(updateLiveClock, 1000);

    function getDaysDiffFromToday(dateStr) {
        if (!dateStr) return 999;
        const parts = dateStr.split('-');
        if (parts.length !== 3) return 999;
        
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        const attDate = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
        attDate.setHours(0, 0, 0, 0);
        
        const diffTime = today.getTime() - attDate.getTime();
        return Math.floor(diffTime / (1000 * 60 * 60 * 24));
    }

    function exportEmployeePDF(titleText = "Employee Attendance Report") {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF("l", "mm", "a4");
        doc.setFontSize(14);
        doc.text(titleText, 14, 15);
        doc.autoTable({
            html: "#empAttendanceviewtbl",
            startY: 22,
            theme: "grid",
            styles: {
                fontSize: 9
            },
            headStyles: {
                fillColor: [41, 128, 185]
            },
            didParseCell: function (data) {

                // Remove no-export columns
                if (
                    data.cell.raw &&
                    data.cell.raw.classList &&
                    data.cell.raw.classList.contains("no-export")
                ) {
                    data.cell.text = "";
                    data.cell.styles.cellPadding = 0;
                    data.cell.styles.lineWidth = 0;
                }
            }
        });

        doc.save("Employee_Attendance.pdf");
    }

    function exportEmployeeExcel(titleText = "Employee Attendance Report") {
        const table = document.getElementById("empAttendanceviewtbl");
        // Clone table
        const clone = table.cloneNode(true);
        // Remove no-export columns
        clone.querySelectorAll(".no-export").forEach(el => el.remove());
        // Convert to worksheet
        const ws = XLSX.utils.table_to_sheet(clone);
        // Add title
        XLSX.utils.sheet_add_aoa(ws, [[titleText]], { origin: "A1" });
        XLSX.utils.sheet_add_aoa(ws, [[]], { origin: "A2" });

        // Shift table down
        XLSX.utils.sheet_add_dom(ws, clone, { origin: "A3" });

        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Employees");

        XLSX.writeFile(wb, "Employee_Attendance.xlsx");
    }

    EmployeeAttendanceYearMonth_callback('EmployeeID', empid, function (attym) {
        if (!Array.isArray(attym)) {
            attym = [];
        }

        // Get current year-month in "YYYY-MM" format
        const now = new Date();
        const currentYear = now.getFullYear();
        const currentMonth = now.getMonth() + 1; // 1-indexed
        const currentMonthStr = String(currentMonth).padStart(2, '0');
        const currentValue = `${currentYear}-${currentMonthStr}`;

        // Check if the current month is already in the list
        const hasCurrent = attym.some(item => item.MonthYear === currentValue || item.MonthYear === `${currentYear}-${currentMonth}`);

        if (!hasCurrent) {
            const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
            const currentDisp = `${monthNames[now.getMonth()]}-${currentYear}`;
            // Prepend the current month so it is at the top/most recent
            attym.unshift({
                AttendanceYear: currentYear,
                AttendanceMonth: currentMonthStr,
                MonthYear: currentValue,
                MonthYeardisp: currentDisp
            });
        }

        let attymoption = "";

        // Build options
        for (let i = 0; i < attym.length; i++) {
            attymoption += `
            <option value="${attym[i].MonthYear}"
                    data-attyear="${attym[i].AttendanceYear}"
                    data-attmonth="${attym[i].AttendanceMonth}">
                ${attym[i].MonthYeardisp}
            </option>`;
        }

        const select = $("#attendanceyearmonth");
        select.empty().append(attymoption);

        // Select the current month option
        const currentOption = select.find(`option[value='${currentValue}']`);
        if (currentOption.length > 0) {
            currentOption.prop("selected", true);
        } else {
            // Otherwise select the first option
            select.find("option:first").prop("selected", true);
        }

        // Trigger the change event to fetch data
        select.trigger("change");
    });
    $(document).on("change", "#attendanceyearmonth", function () {
        let yearmonth = $("#attendanceyearmonth").val();
        let parts = yearmonth.split("-");
        let attyear = parts[0];
        let attmonth = parts[1];
        // console.log(yearmonth);
        let EmployeeId = $("body").data("empid");
        getEmployeeMonthlyAttendanceSummary(EmployeeId, attyear, attmonth, function (attres) {
            // console.log(attres);
            if (attres.days_till_today && attres.days_till_today < attres.days_inmonth) {
                $("#daysinMonth").html(`${attres.days_inmonth} <span class="ms-1 text-warning fw-normal fs-12">(Till Today: ${attres.days_till_today})</span>`);
            } else {
                $("#daysinMonth").html(attres.days_inmonth || 0);
            }
            $("#misDays").html(attres.mis || 0);
            // Combined Present (Full + Half) in same box
            $("#presentDays").html(`
                <div class="d-flex flex-column align-items-center">
                    <span class="fs-18 fw-bold text-success">${attres.present || 0}</span>
                    <small class="text-muted">(F: ${attres.full_days || 0} | H: ${attres.half_days || 0})</small>
                </div>
            `);
            $("#absentDays").html(attres.absent || 0);
            $("#onleavesDays").html(attres.leave || 0);
            $("#weekoffDays").html(attres.weekoff || 0);
            $("#holyDays").html(attres.holiday || 0);
            $("#fulldayhours").html(attres.full_days_hours || 0);
            $("#halfdayhours").html(attres.half_days_hours || 0);

            // Store in window variables for filtering
            window.currentAttendanceDetails = attres.details || [];
            window.currentEmployeeID = attres.EmployeeID || EmployeeId;
            
            // Render the table with all records initially
            renderAttendanceTable(window.currentAttendanceDetails, 'All');
        });
    });

    function renderAttendanceTable(details, filterType = 'All') {
        // Reset card borders and scales
        $(".hrm-card").css({ "transform": "none", "box-shadow": "none", "border-width": "1px" });

        // Apply visual highlights to the active filter element
        if (filterType === 'All') {
            $("#btnFilterAll").css({ "background-color": "#0f172a", "color": "#ffffff", "border-color": "#334155" });
        } else {
            $("#btnFilterAll").css({ "background-color": "#f8fafc", "color": "#334155", "border-color": "#cbd5e1" });
            let cardId = "";
            if (filterType === 'Present') cardId = "#btnFilterPresent";
            else if (filterType === 'MIS') cardId = "#btnFilterMIS";
            else if (filterType === 'Absent') cardId = "#btnFilterAbsent";
            else if (filterType === 'Leave') cardId = "#btnFilterLeave";
            else if (filterType === 'WeekOff') cardId = "#btnFilterWeekOff";
            else if (filterType === 'Holiday') cardId = "#btnFilterHoliday";
            
            if (cardId) {
                $(cardId).css({
                    "transform": "scale(1.03)",
                    "box-shadow": "0 4px 12px rgba(0,0,0,0.08)",
                    "border-width": "2px",
                    "transition": "all 0.2s ease"
                });
            }
        }

        // Today's date in YYYY-MM-DD format
        const todayStr = new Date().toISOString().split('T')[0];
        let filteredDetails = details.filter(row => {
            if (row.status === 'Upcoming' || (row.date > todayStr && !row.attendance && row.leave_status !== 'Approved' && row.leave_status !== 'Pending')) {
                return false;
            }
            if (filterType === 'All') {
                return true;
            }
            const statusText = row.status || "";
            const statusofdayText = row.statusofday || "";
            if (filterType === 'Present') {
                return statusText === 'Present';
            } else if (filterType === 'MIS') {
                return statusText === 'MIS' || statusText.includes('MIS') || row.hasMissingOut;
            } else if (filterType === 'Absent') {
                return (statusText === 'Absent' || statusofdayText === 'Absent' || statusofdayText === 'Absent By Sandwich') && statusofdayText !== 'Half';
            } else if (filterType === 'Leave') {
                return statusText === 'Leave' || (row.leave_status && row.leave_status !== 'Rejected');
            } else if (filterType === 'WeekOff') {
                return statusText === 'WeekOff' || statusofdayText.includes('WeekOff');
            } else if (filterType === 'Holiday') {
                return statusText === 'Holiday' || statusofdayText.includes('Holiday');
            }
            return true;
        });

        let atttbldata = "";
        let attStripsData = "";
        if (Array.isArray(filteredDetails)) {
            filteredDetails.forEach((row) => {
                let statusIcon = "";
                let actionBtn = "";
                let inTime = "-";
                let outTime = "-";
                let workingHours = "-";
                const statusText = row.status || "";
                const statusofdayText = row.statusofday || "";
                inTime = row.FirstCheckInTime!=""
                        ? formatDateTime_DMY_Hi(row.FirstCheckInTime)
                        : "-";
                outTime = row.LastCheckOutTime!=""
                        ? formatDateTime_DMY_Hi(row.LastCheckOutTime)
                        : "-";
                workingHours = row.TotalWorkingHours ?? "00:00";
                /* =========================
                   1. PRIMARY DAY STATUS BADGE
                ========================= */
                let dayStatusBadge = "";
                if (statusText === "Leave") {
                    dayStatusBadge = `
                        <span class="badge bg-soft-info text-info border border-info px-2 py-1">
                            <i class="ti ti-calendar-event me-1"></i> Leave (${row.leave_status || 'Approved'})
                        </span>
                    `;
                } else if (statusText.includes("MIS") || row.hasMissingOut) {
                    dayStatusBadge = `
                        <span class="badge bg-soft-warning text-warning border border-warning px-2 py-1">
                            <i class="ti ti-alert-circle me-1"></i> MIS
                        </span>
                    `;
                } else if (statusText.includes("Present")) {
                    let badgeClass = statusofdayText === 'Full' ? 'bg-soft-success text-success border-success' : 'bg-soft-info text-info border-info';
                    dayStatusBadge = `
                        <span class="badge ${badgeClass} border px-2 py-1">
                            <i class="ti ti-circle-check me-1"></i> ${statusofdayText || 'Present'}
                        </span>
                    `;
                } else if (statusText === "Absent") {
                    dayStatusBadge = `
                        <span class="badge bg-soft-danger text-danger border border-danger px-2 py-1">
                            <i class="ti ti-user-x me-1"></i> ${statusofdayText || 'Absent'}
                        </span>
                    `;
                } else if (statusText === "Holiday") {
                    dayStatusBadge = `
                        <span class="badge bg-soft-dark text-dark border border-dark px-2 py-1">
                            <i class="ti ti-calendar-star me-1"></i> ${statusofdayText || 'Holiday'}
                        </span>
                    `;
                } else if (statusText === "WeekOff") {
                    dayStatusBadge = `
                        <span class="badge bg-soft-primary text-primary border border-primary px-2 py-1">
                            <i class="ti ti-calendar-week me-1"></i> ${statusofdayText || 'WeekOff'}
                        </span>
                    `;
                } else if (statusText === "Upcoming") {
                    dayStatusBadge = `
                        <span class="badge bg-soft-secondary text-secondary border border-secondary px-2 py-1">
                            <i class="ti ti-clock me-1"></i> Upcoming
                        </span>
                    `;
                } else {
                    dayStatusBadge = `
                        <span class="badge bg-soft-secondary text-secondary border border-secondary px-2 py-1">
                            ${statusofdayText || statusText}
                        </span>
                    `;
                }

                /* =========================
                   2. LEAVE BADGE (IF ALSO ON LEAVE BUT MARKED PRESENT/ABSENT)
                ========================= */
                let leaveBadge = "";
                if (statusText !== "Leave" && row.leave_status) {
                    if (row.leave_status === "Approved") {
                        leaveBadge = `
                            <span class="badge bg-soft-info text-info border border-info px-2 py-1" title="${row.leave_type || 'Leave'} Approved">
                                <i class="ti ti-calendar-event me-1"></i> Leave (Approved)
                            </span>
                        `;
                    } else if (row.leave_status === "Pending") {
                        leaveBadge = `
                            <span class="badge bg-soft-warning text-warning border border-warning px-2 py-1" title="${row.leave_type || 'Leave'} Pending">
                                <i class="ti ti-clock me-1"></i> Leave (Pending)
                            </span>
                        `;
                    } else if (row.leave_status === "Rejected") {
                        leaveBadge = `
                            <span class="badge bg-soft-danger text-danger border border-danger px-2 py-1" title="${row.leave_type || 'Leave'} Rejected">
                                <i class="ti ti-calendar-off me-1"></i> Leave (Rejected)
                            </span>
                        `;
                    }
                }

                /* =========================
                   3. APPROVAL STATUS BADGE (IF ATTENDANCE MARKED)
                ========================= */
                let approvalBadge = "";
                const appStat = (row.ApprovalStatus || "").toString().trim().toLowerCase();
                let isMisRecord = statusText.includes("MIS") || row.hasMissingOut || statusText === "MIS";
                if (row.FirstCheckInTime || (row.punches && row.punches.length > 0)) {
                    if (appStat === "pending") {
                        approvalBadge = `
                            <span class="badge bg-soft-warning text-warning border border-warning px-2 py-1">
                                <i class="ti ti-clock me-1"></i> Pending Approval
                            </span>
                        `;
                    } else if (appStat === "approved" && !isMisRecord) {
                        approvalBadge = `
                            <span class="badge bg-soft-success text-success border border-success px-2 py-1">
                                <i class="ti ti-circle-check me-1"></i> Approved
                            </span>
                        `;
                    } else if (appStat === "rejected") {
                        approvalBadge = `
                            <span class="badge bg-soft-danger text-danger border border-danger px-2 py-1">
                                <i class="ti ti-circle-x me-1"></i> Rejected
                            </span>
                        `;
                    }
                }

                /* =========================
                   4. EDIT & REMOVE ACTION BUTTONS
                ========================= */
                let editBtn = "";
                let removeBtn = "";
                let loginroleid = parseInt($("body").data("loginroleid")) || 0;
                let hasAttendance = row.FirstCheckInTime || (row.punches && row.punches.length > 0);

                let diffDays = getDaysDiffFromToday(row.date);
                let isWithin3Days = (diffDays >= 0 && diffDays <= 3);

                let pendingEditDaysBadge = "";
                if (loginroleid > 3) {
                    let dayText = "";
                    if (isWithin3Days) {
                        let remainingDays = 3 - diffDays;
                        dayText = remainingDays === 1 ? '1 Day Pending for Edit' : `${remainingDays} Days Pending for Edit`;
                    } else {
                        dayText = '0 Days Pending for Edit';
                    }
                    pendingEditDaysBadge = `
                        <span class="badge bg-soft-danger text-danger border border-danger px-2 py-1" style="color: #ef4444 !important; border-color: #fca5a5 !important; background-color: #fef2f2 !important;" title="Days pending to edit attendance">
                            <i class="ti ti-clock-hour-4 me-1"></i> ${dayText}
                        </span>
                    `;
                }

                if (hasAttendance) {
                    let canEdit = (loginroleid <= 3) || isWithin3Days;
                    let canRemove = (loginroleid <= 3) || (isWithin3Days && appStat === 'pending');

                    if (canEdit) {
                        editBtn = `
                            <button class="btn btn-outline-warning btn-sm EditAttendance" title="Edit Attendance">
                                <i class="ti ti-edit me-1"></i> Edit
                            </button>
                        `;
                    }
                    if (canRemove) {
                        removeBtn = `
                            <button class="btn btn-outline-danger btn-sm RemoveAttendance" data-attdate="${row.date}" title="Remove Attendance Request">
                                <i class="ti ti-trash me-1"></i> Remove
                            </button>
                        `;
                    }
                }

                /* =========================
                   MODERN STRIP ROW (Visible)
                ========================= */
                let stripStatusClass = 'status-default';
                let lowerStatus = statusText.toLowerCase();
                if (lowerStatus.includes('present')) {
                    stripStatusClass = 'status-present';
                } else if (lowerStatus.includes('mis')) {
                    stripStatusClass = 'status-mis';
                } else if (lowerStatus.includes('absent')) {
                    stripStatusClass = 'status-absent';
                } else if (lowerStatus.includes('holiday')) {
                    stripStatusClass = 'status-holiday';
                } else if (lowerStatus.includes('weekoff')) {
                    stripStatusClass = 'status-weekoff';
                } else if (lowerStatus.includes('leave')) {
                    stripStatusClass = 'status-leave';
                }

                /* =========================
                   MULTIPLE PUNCHES RENDERING
                ========================= */
                let inTimeHtml = "";
                let outTimeHtml = "";
                let durationHtml = "";

                if (Array.isArray(row.punches) && row.punches.length > 0) {
                    row.punches.forEach((p, idx) => {
                        let rawOut = p.LastCheckOutTime || p.CheckOutTime || "";
                        let isMissing = !rawOut || rawOut === "0000-00-00 00:00:00" || rawOut.includes("00:00:00");

                        let pIn = p.FirstCheckInTime || p.CheckInTime ? formatDateTime_DMY_Hi(p.FirstCheckInTime || p.CheckInTime) : "-";
                        let pOut = !isMissing ? formatDateTime_DMY_Hi(rawOut) : "-";
                        let pDur = p.TotalWorkingHours || p.WorkingHours || "00:00";
                        if (pDur.length > 5 && pDur.endsWith(":00")) {
                            pDur = pDur.substring(0, 5);
                        }
                        
                        let borderStyle = idx > 0 ? 'margin-top: 6px; padding-top: 6px; border-top: 1px dashed #e2e8f0;' : '';
                        if (isMissing) {
                            inTimeHtml += `<div class="strip-value fw-semibold text-warning" style="${borderStyle}">${pIn}</div>`;
                            outTimeHtml += `<div class="strip-value fw-semibold text-warning" style="${borderStyle}"><i class="ti ti-alert-circle me-1"></i> Missing Markout</div>`;
                            durationHtml += `<div style="${borderStyle}"><span class="badge bg-soft-warning text-warning border border-warning px-2 py-1 fw-bold">MIS</span></div>`;
                        } else {
                            inTimeHtml += `<div class="strip-value fw-semibold text-secondary" style="${borderStyle}">${pIn}</div>`;
                            outTimeHtml += `<div class="strip-value fw-semibold text-secondary" style="${borderStyle}">${pOut}</div>`;
                            durationHtml += `<div style="${borderStyle}"><span class="badge bg-light text-dark border px-2 py-1 fw-bold">${pDur}</span></div>`;
                        }
                    });
                } else {
                    inTimeHtml = `<div class="strip-value fw-semibold text-secondary">${inTime}</div>`;
                    outTimeHtml = `<div class="strip-value fw-semibold text-secondary">${outTime}</div>`;
                    durationHtml = `<div><span class="badge bg-light text-dark border px-2 py-1 fw-bold">${workingHours}</span></div>`;
                }

                let stripHtml = `
                    <div class="attendance-strip ${stripStatusClass}" data-attdate="${row.date}" data-empid="${window.currentEmployeeID}">
                        <div class="row align-items-center">
                            <!-- Date Section -->
                            <div class="col-md-2 col-12 mb-2 mb-md-0">
                                <div class="strip-label d-md-none text-muted mb-1">Date</div>
                                <div class="strip-value fw-bold text-dark fs-15">${changeDateFormatYmd_dMY(row.date)}</div>
                            </div>
                            
                            <!-- Mark-In Section -->
                            <div class="col-md-2 col-6 mb-2 mb-md-0">
                                <div class="strip-label text-muted mb-1">Mark-In</div>
                                ${inTimeHtml}
                            </div>
                            
                            <!-- Mark-Out Section -->
                            <div class="col-md-2 col-6 mb-2 mb-md-0">
                                <div class="strip-label text-muted mb-1">Mark-Out</div>
                                ${outTimeHtml}
                            </div>
                            
                            <!-- Work Duration -->
                            <div class="col-md-2 col-6">
                                <div class="strip-label text-muted mb-1">Duration</div>
                                ${durationHtml}
                            </div>
                            
                            <!-- Status & Actions -->
                            <div class="col-md-4 col-12 text-end">
                                <div class="d-flex align-items-center justify-content-end gap-2 flex-wrap">
                                    ${leaveBadge ? `<div>${leaveBadge}</div>` : ''}
                                    ${dayStatusBadge ? `<div>${dayStatusBadge}</div>` : ''}
                                    ${approvalBadge ? `<div>${approvalBadge}</div>` : ''}
                                    ${pendingEditDaysBadge ? `<div>${pendingEditDaysBadge}</div>` : ''}
                                    ${editBtn ? `<div>${editBtn}</div>` : ''}
                                    ${removeBtn ? `<div>${removeBtn}</div>` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                attStripsData += stripHtml;
            });
        }

        if (attStripsData === "") {
            attStripsData = `<div class="alert alert-light text-center border-dashed py-4 m-3"><i class="ti ti-info-circle fs-30 text-muted d-block mb-2"></i>No attendance records found for this filter.</div>`;
        }

        $("#empAttendanceStripsWrapper").html(attStripsData);
    }

    // Attach click events for filtering
    $(document).on("click", "#btnFilterAll", function () {
        renderAttendanceTable(window.currentAttendanceDetails, 'All');
    });
    $(document).on("click", "#btnFilterPresent", function () {
        renderAttendanceTable(window.currentAttendanceDetails, 'Present');
    });
    $(document).on("click", "#btnFilterMIS", function () {
        renderAttendanceTable(window.currentAttendanceDetails, 'MIS');
    });
    $(document).on("click", "#btnFilterAbsent", function () {
        renderAttendanceTable(window.currentAttendanceDetails, 'Absent');
    });
    $(document).on("click", "#btnFilterLeave", function () {
        renderAttendanceTable(window.currentAttendanceDetails, 'Leave');
    });
    $(document).on("click", "#btnFilterWeekOff", function () {
        renderAttendanceTable(window.currentAttendanceDetails, 'WeekOff');
    });
    $(document).on("click", "#btnFilterHoliday", function () {
        renderAttendanceTable(window.currentAttendanceDetails, 'Holiday');
    });

    $(document).on("click", ".EditAttendance", function () {
        $("#edit_attendance").modal("show");

        const $parent = $(this).closest("tr, .attendance-strip");
        const empId = $parent.data("empid");
        const attDate = $parent.data("attdate");
        $("#editattdatespan").html(attDate);
        EmployeeAttendanceDetails(empId, 'AttendanceDate', attDate, function (atv) {
            // console.log(atv);
            const $tbody = $("#editatttbl tbody");
            $tbody.empty();
            $("#editattprooffile").val(""); // Reset file input
            $("#currentProofFileText").html(""); // Reset current file link
            if (Array.isArray(atv) && atv.length > 0) {
                let edatttr = "";
                let loginroleid = parseInt($("body").data("loginroleid")) || 0;
                for (let i = 0; i < atv.length; i++) {
                    let checkinVal = atv[i].CheckInTime || '';
                    let checkoutVal = atv[i].CheckOutTime || '';
                    if (!checkoutVal && checkinVal) {
                        const datePart = checkinVal.includes(' ') ? checkinVal.split(' ')[0] : checkinVal.split('T')[0];
                        checkoutVal = datePart + 'T00:00:00';
                    }
                    let appStat = (atv[i].ApprovalStatus || "").toString().trim().toLowerCase();
                    let diffDays = getDaysDiffFromToday(attDate);
                    let isWithin3Days = (diffDays >= 0 && diffDays <= 3);
                    let canEdit = (loginroleid <= 3) || isWithin3Days;
                    let canRemove = (loginroleid <= 3) || (isWithin3Days && appStat === 'pending');

                    let disabledAttr = canEdit ? "" : "disabled readonly";
                    let removeBtnHtml = "";
                    if (canRemove) {
                        removeBtnHtml = `<button type="button" class="btn btn-outline-danger btn-sm removePunchRow" data-attid="${atv[i].AttendanceID}" title="Remove Entry"><i class="ti ti-trash"></i></button>`;
                    } else {
                        removeBtnHtml = `<span class="badge bg-light text-muted" title="Approved entry cannot be removed"><i class="ti ti-lock"></i> Locked</span>`;
                    }

                    edatttr += `<tr data-attid='${atv[i].AttendanceID}' data-empid='${atv[i].EmployeeID}'>
                        <td><input type="datetime-local" step="1" class="form-control flatpickr-input checkininput" ${disabledAttr} placeholder="YYYY-MM-DD HH:MM" <?= ($loginroleid <= 3) ? '' : 'min="' . date('Y-m-d\T00:00', strtotime('-3 days')) . '"' ?> max="<?= date('Y-m-d\T23:59') ?>" value="${checkinVal}" /></td>
                        <td><input type="datetime-local" step="1" class="form-control flatpickr-input checkoutinput" ${disabledAttr} <?= ($loginroleid <= 3) ? '' : 'min="' . date('Y-m-d\T00:00', strtotime('-3 days')) . '"' ?> max="<?= date('Y-m-d\T23:59') ?>" placeholder="YYYY-MM-DD HH:MM" value="${checkoutVal}" /></td>
                        <td><span class='badge badge-success workinghours'>${atv[i].WorkingHours || ''}</span></td>
                        <td class="text-center">${removeBtnHtml}</td>
                    </tr>`;
                    $("#editattremark").val(atv[i].Remarks);
                    if (atv[i].misattendancefile) {
                        const fileLink = `<a href="${routurl}${atv[i].misattendancefile.replace(/^\/..\//, '')}" target="_blank" class="text-primary fw-bold"><i class="ti ti-external-link me-1"></i>View Current Proof File</a>`;
                        $("#currentProofFileText").html(fileLink);
                    }
                }
                $tbody.html(edatttr);
                // updateTotalWorkingHours(); // show initial total
            }
        });
    });

    // ===== Remove Punch Row in Edit Attendance Modal =====
    $(document).on("click", ".removePunchRow", function () {
        const $row = $(this).closest("tr");
        const $tbody = $row.closest("tbody");
        const attid = $row.data("attid");
        const empid = $row.data("empid");
        const loginroleid = parseInt($("body").data("loginroleid")) || '';
        Swal.fire({
            title: "Remove Entry?",
            text: "Are you sure you want to remove this punch entry?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Yes, Remove"
        }).then((result) => {
            if (result.isConfirmed) {
                updateTotalWorkingHours();
                if (result.isConfirmed) {
                    let form_data = new FormData();
                    form_data.append("companyid", $("body").data("companyid"));
                    form_data.append("subcompanyid", $("body").data("subcompanyid"));
                    form_data.append("projectid", $("body").data("projectid"));
                    form_data.append("loginid", $("body").data("loginid"));
                    form_data.append("loginroleid", loginroleid);
                    form_data.append("empid", empid);
                    form_data.append("attid", attid);

                    $.ajax({
                        type: "POST",
                        url: routurl + "modal/hrm_modal.php?action=DeleteEmployeePunchAttendance",
                        data: form_data,
                        contentType: false,
                        processData: false,
                        dataType: "json",
                        success: function (res) {
                            if (res.status === "success") {
                                Swal.fire({
                                    icon: "success",
                                    title: "Removed",
                                    text: res.message || "Attendance request removed successfully.",
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                                setTimeout(() => location.reload(), 1200);
                            } else {
                                Swal.fire("Error", res.message || "Failed to remove attendance request.", "error");
                            }
                        },
                        error: function () {
                            Swal.fire("Server Error", "Unable to process request. Please try again.", "error");
                        }
                    });
                }
                
            }
        });
    });

    // ===== Remove Attendance Request from Main Strip =====
    $(document).on("click", ".RemoveAttendance", function () {
        const $strip = $(this).closest(".attendance-strip");
        const attDate = $(this).data("attdate") || $strip.data("attdate");
        const empId = window.currentEmployeeID || $strip.data("empid");
        const loginroleid = parseInt($("body").data("loginroleid")) || 0;

        Swal.fire({
            title: "Remove Attendance Request?",
            text: `Are you sure you want to remove attendance request for ${changeDateFormatYmd_dMY(attDate)}?`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Yes, Remove Request",
            cancelButtonText: "Cancel"
        }).then((result) => {
            if (result.isConfirmed) {
                let form_data = new FormData();
                form_data.append("companyid", $("body").data("companyid"));
                form_data.append("subcompanyid", $("body").data("subcompanyid"));
                form_data.append("projectid", $("body").data("projectid"));
                form_data.append("loginid", $("body").data("loginid"));
                form_data.append("loginroleid", loginroleid);
                form_data.append("EmployeeID", empId);
                form_data.append("AttendanceDate", attDate);

                $.ajax({
                    type: "POST",
                    url: routurl + "modal/hrm_modal.php?action=DeleteEmployeeAttendance",
                    data: form_data,
                    contentType: false,
                    processData: false,
                    dataType: "json",
                    success: function (res) {
                        if (res.status === "success") {
                            Swal.fire({
                                icon: "success",
                                title: "Removed",
                                text: res.message || "Attendance request removed successfully.",
                                timer: 1500,
                                showConfirmButton: false
                            });
                            setTimeout(() => location.reload(), 1200);
                        } else {
                            Swal.fire("Error", res.message || "Failed to remove attendance request.", "error");
                        }
                    },
                    error: function () {
                        Swal.fire("Server Error", "Unable to process request. Please try again.", "error");
                    }
                });
            }
        });
    });
    // add_attendance
    $(document).on("click", "#markMissingAttendancebtn", function () {
        $("#add_attendance").modal("show");
    });
    $(document).on("click", ".markininput", function () {
        // Get today's date in YYYY-MM-DD format
        const today = new Date().toISOString().split('T')[0];
        const attDate = today;
        const empId = $("body").data("empid");

        EmployeeAttendanceDetails(empId, 'AttendanceDate', attDate, function (atv) {
            const $tbody = $("#editatttbl tbody");
            $tbody.empty();

            if (Array.isArray(atv) && atv.length > 0) {
                alertmessage("warning", "Attendance already marked. You cannot add new attendance.");
                return;
            }
        });
    });
    // ===== Function to calculate single row working hours =====
    function calculateRowHours($row) {
        const checkin = $row.find(".checkininput").val();
        const checkout = $row.find(".checkoutinput").val();
        const $whours = $row.find(".workinghours");

        if (checkin && checkout) {
            const start = new Date(checkin);
            const end = new Date(checkout);

            if (end > start) {
                const diffMs = end - start;
                const diffHrs = Math.floor(diffMs / (1000 * 60 * 60));
                const diffMins = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
                const formatted = `${diffHrs.toString().padStart(2, "0")}:${diffMins
                    .toString()
                    .padStart(2, "0")}`;
                $whours.text(formatted);
                return diffHrs * 60 + diffMins; // total minutes
            } else {
                $whours.text("");
                // alert("Checkout time must be after Check-in time.");
                Swal.fire({
                    icon: "warning",
                    title: "Invalid Checkout Time",
                    text: "Checkout time must be after Check-in time.",
                    confirmButtonText: "OK"
                });
            }
        } else {
            $whours.text("");
        }
        return 0;
    }

    // ===== Function to update total working hours =====
    function updateTotalWorkingHours() {
        let totalMinutes = 0;
        let hasInvalidHours = false;

        $("#editatttbl tbody tr").each(function () {
            const rowMinutes = calculateRowHours($(this));

            // ❌ Less than 2 hours (120 minutes)
            if (rowMinutes > 0 && rowMinutes < 120) {
                hasInvalidHours = true;

                Swal.fire({
                    icon: "warning",
                    title: "Invalid Working Hours",
                    text: "Working hours less than 2 hours will not be considered in attendance.",
                    confirmButtonText: "OK"
                });
                $("#updateEditAttendancebtn").prop("disabled", true);
                return false; // ⛔ stop loop immediately
            }

            totalMinutes += rowMinutes;
        });

        // ⛔ Stop updating total if invalid found
        if (hasInvalidHours) {
            $("#totalWorkingHours").text("Total Working Hours: 00:00");
            return;
        }

        const totalHrs = Math.floor(totalMinutes / 60);
        const totalMins = totalMinutes % 60;

        const formattedTotal = `${totalHrs.toString().padStart(2, "0")}:${totalMins
            .toString()
            .padStart(2, "0")}`;
        $("#updateEditAttendancebtn").prop("disabled", false);
        $("#totalWorkingHours").text(`Total Working Hours: ${formattedTotal}`);
    }


    // ===== Auto update working hours & total on change =====
    $(document).on("change", ".checkininput, .checkoutinput", function () {
        const $row = $(this).closest("tr");
        calculateRowHours($row);
        updateTotalWorkingHours();
    });

    // ===== Autofill checkout date based on checkin date =====
    $(document).on("change input", ".checkininput", function () {
        const checkinVal = $(this).val();
        if (checkinVal && checkinVal.includes('T')) {
            const checkinDate = checkinVal.split('T')[0];
            const $row = $(this).closest("tr");
            const $checkout = $row.find(".checkoutinput");
            const checkoutVal = $checkout.val() || "";
            
            let newCheckoutVal = "";
            if (checkoutVal && checkoutVal.includes('T')) {
                const checkoutTime = checkoutVal.split('T')[1];
                newCheckoutVal = checkinDate + 'T' + checkoutTime;
            } else {
                newCheckoutVal = checkinDate + 'T18:00:00';
            }
            
            if ($checkout.val() !== newCheckoutVal) {
                $checkout.val(newCheckoutVal);
                $checkout.trigger("change");
            }
        }
    });

    // ===== Click to open native time picker =====
    $(document).on("click", "#markininput, #markoutinput, .checkininput, .checkoutinput", function() {
        try {
            this.showPicker();
        } catch(e) {
            console.log("showPicker failed: ", e);
        }
    });

    // ===== Track previous values to detect AM/PM clicks/toggles =====
    let prevValues = {};

    $(document).on("focus", "#markininput, #markoutinput, .checkininput, .checkoutinput", function() {
        const id = this.id || $(this).closest("tr").data("attid") + "_" + ($(this).hasClass("checkininput") ? "in" : "out");
        prevValues[id] = $(this).val();
    });

    $(document).on("input change", "#markininput, #markoutinput, .checkininput, .checkoutinput", function() {
        const id = this.id || $(this).closest("tr").data("attid") + "_" + ($(this).hasClass("checkininput") ? "in" : "out");
        const oldVal = prevValues[id] || "";
        const newVal = $(this).val() || "";
        prevValues[id] = newVal;

        if (oldVal && newVal) {
            let oldDatePart = "", newDatePart = "";
            let oldTimePart = oldVal, newTimePart = newVal;
            
            if (oldVal.includes('T')) {
                const oldSplit = oldVal.split('T');
                const newSplit = newVal.split('T');
                oldDatePart = oldSplit[0];
                newDatePart = newSplit[0];
                oldTimePart = oldSplit[1] || "";
                newTimePart = newSplit[1] || "";
            }
            
            if (oldDatePart === newDatePart) {
                const oldTimeSplit = oldTimePart.split(':');
                const newTimeSplit = newTimePart.split(':');
                
                if (oldTimeSplit.length >= 2 && newTimeSplit.length >= 2) {
                    const oldHour = parseInt(oldTimeSplit[0], 10);
                    const newHour = parseInt(newTimeSplit[0], 10);
                    const oldMin = oldTimeSplit[1];
                    const newMin = newTimeSplit[1];
                    const oldSec = oldTimeSplit[2] || "";
                    const newSec = newTimeSplit[2] || "";
                    
                    if (oldMin === newMin && oldSec === newSec && Math.abs(oldHour - newHour) === 12) {
                        this.blur();
                    }
                }
            }
        }
    });

    $(document).on("change", "#markininput, #markoutinput, .checkininput, .checkoutinput", function() {
        this.blur();
    });

    // Close when typing a/p/A/P (AM/PM selection)
    $(document).on("keyup", "#markininput, #markoutinput, .checkininput, .checkoutinput", function(e) {
        if (e.key === 'a' || e.key === 'A' || e.key === 'p' || e.key === 'P') {
            this.blur();
        }
    });

</script>