<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// AJAX Handler - MUST run before requiring header.php to avoid sending HTML content
if (isset($_POST['action'])) {
    include_once("../connection.php");
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action == 'get_manual_attendance_data') {
        $employeeId = intval($_POST['employee_id']);
        $monthYear = $_POST['month_year']; // e.g. "2026-06"
        $parts = explode('-', $monthYear);
        $year = intval($parts[0]);
        $month = intval($parts[1]);
        $projectid = $_SESSION['projectid'] ?? $_POST['projectid'] ?? '';

        // Fetch employee shift details
        $shiftIn = '';
        $shiftOut = '';
        $empQuery = $hrmconn->prepare("SELECT InTime, OutTime FROM hrm_EmployeesMaster WHERE EmployeeID = ?");
        $empQuery->bind_param("i", $employeeId);
        $empQuery->execute();
        $empRes = $empQuery->get_result()->fetch_assoc();
        if ($empRes) {
            $shiftIn = !empty($empRes['InTime']) ? date('H:i', strtotime($empRes['InTime'])) : '';
            $shiftOut = !empty($empRes['OutTime']) ? date('H:i', strtotime($empRes['OutTime'])) : '';
        }
        $empQuery->close();

        // Fetch employee active week-offs
        $weekOffs = [];
        $woQuery = $hrmconn->prepare("SELECT WeekOffDayNo FROM hrm_EmployeeWeekOff WHERE EmployeeID = ? AND ProjectID = ? AND Status = 'Active'");
        $woQuery->bind_param("ii", $employeeId, $projectid);
        $woQuery->execute();
        $woRes = $woQuery->get_result();
        while ($woRow = $woRes->fetch_assoc()) {
            $weekOffs[] = intval($woRow['WeekOffDayNo']);
        }
        $woQuery->close();

        // Fetch holidays for the project
        $holidays = [];
        $hQuery = $hrmconn->prepare("SELECT DATE(HolidayDate) as hdate, HolidayName FROM hrm_PayrollHolidaysMaster WHERE (tedit IS NULL OR tedit = '') AND projectid = ? AND MONTH(HolidayDate) = ? AND YEAR(HolidayDate) = ?");
        $hQuery->bind_param("iii", $projectid, $month, $year);
        $hQuery->execute();
        $hRes = $hQuery->get_result();
        while ($hRow = $hRes->fetch_assoc()) {
            $holidays[$hRow['hdate']] = $hRow['HolidayName'];
        }
        $hQuery->close();

        // Fetch existing attendance records
        $attendance = [];
        $attQuery = $hrmconn->prepare("SELECT AttendanceID, AttendanceDate, CheckInTime, CheckOutTime, WorkingHours, Status, ApprovalStatus FROM hrm_EmployeeAttendance WHERE EmployeeID = ? AND ProjectID = ? AND MONTH(AttendanceDate) = ? AND YEAR(AttendanceDate) = ? AND (tedit IS NULL OR tedit = '')");
        $attQuery->bind_param("iiii", $employeeId, $projectid, $month, $year);
        $attQuery->execute();
        $attRes = $attQuery->get_result();
        while ($attRow = $attRes->fetch_assoc()) {
            $attendance[$attRow['AttendanceDate']] = [
                'AttendanceID' => $attRow['AttendanceID'],
                'CheckInTime' => !empty($attRow['CheckInTime']) ? date('H:i', strtotime($attRow['CheckInTime'])) : '',
                'CheckOutTime' => !empty($attRow['CheckOutTime']) ? date('H:i', strtotime($attRow['CheckOutTime'])) : '',
                'WorkingHours' => $attRow['WorkingHours'],
                'Status' => $attRow['Status'],
                'ApprovalStatus' => $attRow['ApprovalStatus']
            ];
        }
        $attQuery->close();

        echo json_encode([
            'status' => 'success',
            'shift' => ['in' => $shiftIn, 'out' => $shiftOut],
            'weekoffs' => $weekOffs,
            'holidays' => $holidays,
            'attendance' => $attendance
        ]);
        exit;
    }

    if ($action == 'save_manual_attendance') {
        $employeeId = intval($_POST['employee_id']);
        $projectid = intval($_POST['projectid']);
        $loginid = $_POST['loginid'] ?? '';
        $attendanceData = json_decode($_POST['attendance_data'], true);

        if (empty($attendanceData)) {
            echo json_encode(['status' => 'error', 'message' => 'No attendance data received']);
            exit;
        }

        $inserted = 0;
        $updated = 0;
        $errors = 0;

        foreach ($attendanceData as $row) {
            $attDate = $row['date']; // YYYY-MM-DD
            $checkIn = $row['check_in']; // HH:MM
            $checkOut = $row['check_out']; // HH:MM

            // Calculate Working Hours
            $workingHours = '';
            if (!empty($checkIn) && !empty($checkOut)) {
                try {
                    $in = new DateTime($attDate . ' ' . $checkIn . ':00');
                    $out = new DateTime($attDate . ' ' . $checkOut . ':00');
                    if ($out > $in) {
                        $interval = $in->diff($out);
                        $workingHours = $interval->format('%H:%I');
                    } else {
                        $workingHours = '00:00';
                    }
                } catch (Exception $e) {
                    $workingHours = '';
                }
            }

            $checkInTime = !empty($checkIn) ? ($attDate . ' ' . $checkIn . ':00') : null;
            $checkOutTime = !empty($checkOut) ? ($attDate . ' ' . $checkOut . ':00') : null;

            // Check if record exists
            $checkStmt = $hrmconn->prepare("SELECT AttendanceID FROM hrm_EmployeeAttendance WHERE EmployeeID = ? AND ProjectID = ? AND AttendanceDate = ? AND (tedit IS NULL OR tedit = '') LIMIT 1");
            $checkStmt->bind_param("iis", $employeeId, $projectid, $attDate);
            $checkStmt->execute();
            $checkRes = $checkStmt->get_result()->fetch_assoc();
            $checkStmt->close();

            if ($checkRes) {
                // Update
                $attId = $checkRes['AttendanceID'];
                $updateStmt = $hrmconn->prepare("UPDATE hrm_EmployeeAttendance SET CheckInTime = ?, CheckOutTime = ?, WorkingHours = ?, Status = 'Present', ApprovalStatus = 'Approved', Remarks = 'Manual Entry' WHERE AttendanceID = ?");
                $updateStmt->bind_param("sssi", $checkInTime, $checkOutTime, $workingHours, $attId);
                if ($updateStmt->execute()) {
                    $updated++;
                } else {
                    $errors++;
                }
                $updateStmt->close();
            } else {
                // Insert
                $insertStmt = $hrmconn->prepare("INSERT INTO hrm_EmployeeAttendance (ProjectID, EmployeeID, AttendanceDate, CheckInTime, CheckOutTime, WorkingHours, Status, ApprovalStatus, Remarks, created_by) VALUES (?, ?, ?, ?, ?, ?, 'Present', 'Approved', 'Manual Entry', ?)");
                $insertStmt->bind_param("iisssss", $projectid, $employeeId, $attDate, $checkInTime, $checkOutTime, $workingHours, $loginid);
                if ($insertStmt->execute()) {
                    $inserted++;
                } else {
                    $errors++;
                }
                $insertStmt->close();
            }
        }

        echo json_encode([
            'status' => 'success',
            'message' => "Attendance saved successfully. Updated: {$updated}, Inserted: {$inserted}" . ($errors > 0 ? ", Errors: {$errors}" : "")
        ]);
        exit;
    }
}

// Render regular page view
require('header.php');

$currentYear = date('Y');
$currentMonth = date('m');
$currentMonthName = date('F Y');
$currentMonthVal = "$currentYear-$currentMonth";

// Calculate previous month
$prevMonthTime = strtotime('first day of last month');
$prevYear = date('Y', $prevMonthTime);
$prevMonth = date('m', $prevMonthTime);
$prevMonthName = date('F Y', $prevMonthTime);
$prevMonthVal = "$prevYear-$prevMonth";

// Check if previous month salary is calculated
$salaryCalculated = false;
$checkSal = $hrmconn->prepare("SELECT COUNT(*) as count FROM hrm_EmployeeCalculatedSalary WHERE ProjectID = ? AND SalaryYear = ? AND SalaryMonth = ?");
$checkSal->bind_param("sii", $projectid, $prevYear, $prevMonth);
$checkSal->execute();
$salRes = $checkSal->get_result()->fetch_assoc();
if ($salRes && $salRes['count'] > 0) {
    $salaryCalculated = true;
}
$checkSal->close();
?>

<style>
    .table-light-info {
        background-color: rgba(54, 209, 220, 0.08) !important;
    }
    .table-light-warning {
        background-color: rgba(247, 151, 30, 0.08) !important;
    }
    .bg-soft-info {
        background-color: rgba(54, 209, 220, 0.15) !important;
    }
    .text-info {
        color: #00b0ff !important;
    }
    .border-info {
        border-color: rgba(54, 209, 220, 0.4) !important;
    }
    .bg-soft-warning {
        background-color: rgba(247, 151, 30, 0.15) !important;
    }
    .text-warning {
        color: #f7971e !important;
    }
    .border-warning {
        border-color: rgba(247, 151, 30, 0.4) !important;
    }
    .bg-soft-success {
        background-color: rgba(40, 167, 69, 0.15) !important;
    }
    .text-success {
        color: #28a745 !important;
    }
    .border-success {
        border-color: rgba(40, 167, 69, 0.4) !important;
    }
    .fs-11 {
        font-size: 0.7rem;
    }
</style>

<div class="content">
    <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-4">
        <div class="my-auto mb-2">
            <h2 class="mb-1 fw-bold">Manual Attendance Marker</h2>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php?k=<?php echo $key; ?>"><i class="ti ti-smart-home"></i></a></li>
                    <li class="breadcrumb-item active text-primary" aria-current="page">Manual Attendance</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex my-xl-auto right-content align-items-center flex-wrap gap-2">
            <!-- <div>
                <a class="btn btn-outline-primary shadow-sm" href="index.php?k=<?php echo $key; ?>">
                    <i class="ti ti-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div> -->
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4 bg-white rounded-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label fw-bold text-dark"><i class="ti ti-user me-1 text-primary"></i> Choose Employee</label>
                    <select class="select2 form-control" id="manualEmpSelect" style="width: 100%;">
                        <option value="">Select Employee</option>
                        <?php
                        $employees = fetchEmployeeData($hrmconn, $companyid, $subcompanyid, $projectid, "allactive");
                        foreach ($employees as $emp) {
                            echo '<option value="' . $emp['EmployeeID'] . '">' . htmlspecialchars($emp['EmployeeID'] . ' - ' . $emp['FullName']) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-bold text-dark"><i class="ti ti-calendar me-1 text-primary"></i> Choose Attendance Month</label>
                    <select class="form-select hrm-select" id="manualMonthSelect">
                        <?php
                        // Current month
                        echo '<option value="' . $currentMonthVal . '" data-year="' . $currentYear . '" data-month="' . $currentMonth . '">' . $currentMonthName . ' (Current Month)</option>';
                        
                        // Previous month if salary not calculated
                        if (!$salaryCalculated) {
                            echo '<option value="' . $prevMonthVal . '" data-year="' . $prevYear . '" data-month="' . $prevMonth . '">' . $prevMonthName . ' (Salary Pending)</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100 fw-bold" id="loadCalendarBtn">
                        <i class="ti ti-search me-1"></i> Load Days
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendar Card -->
    <div class="card border-0 shadow-sm d-none" id="calendarCard">
        <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
            <h4 class="mb-0 fw-bold text-dark"><i class="ti ti-calendar-event me-2 text-primary"></i>Monthly Calendar View</h4>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="manualAttendanceTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;" class="text-center"><div class="form-check">
                                <input class="form-check-input" type="checkbox" id="selectAllDays">
                                <label class="form-check-label fw-bold text-dark" for="selectAllDays">All</label>
                            </div></th>
                            <th style="width: 150px;">Date</th>
                            <th style="width: 120px;">Day</th>
                            <th style="width: 180px;">Check-In Time</th>
                            <th style="width: 180px;">Check-Out Time</th>
                            <th>Status & Highlighting</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Calendar rows will be injected dynamically via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-0 py-3 text-end">
            <button class="btn btn-success px-5 fw-bold" id="saveManualAttendanceBtn">
                <i class="ti ti-device-floppy me-1"></i> Save Attendance
            </button>
        </div>
    </div>
</div>

<?php
require('footer.php');
?>

<script>
    function showLoading(show) {
        if (show) {
            $("#global-loader").fadeIn("fast");
        } else {
            $("#global-loader").fadeOut("fast");
        }
    }

    $(document).ready(function() {
        // Initialize Select2 searchable dropdown for Choose Employee
        $('#manualEmpSelect').select2({
            placeholder: "Select Employee",
            allowClear: true,
            width: '100%'
        });

        // Handle calendar loading
        $('#loadCalendarBtn').on('click', function() {
            const employeeId = $('#manualEmpSelect').val();
            const monthYear = $('#manualMonthSelect').val();
            const selectedOption = $('#manualMonthSelect option:selected');
            const year = selectedOption.data('year');
            const month = selectedOption.data('month');
            const projectid = $("body").data("projectid");

            if (!employeeId) {
                iziToast.warning({ title: 'Validation Error', message: 'Please select an employee.' });
                return;
            }

            showLoading(true);

            $.ajax({
                url: 'manualAttendance.php',
                type: 'POST',
                data: {
                    action: 'get_manual_attendance_data',
                    employee_id: employeeId,
                    month_year: monthYear,
                    projectid: projectid
                },
                dataType: 'json',
                success: function(response) {
                    showLoading(false);
                    if (response.status === 'success') {
                        generateCalendar(year, month, response);
                    } else {
                        iziToast.error({ title: 'Error', message: response.message || 'Failed to load details.' });
                    }
                },
                error: function() {
                    showLoading(false);
                    iziToast.error({ title: 'Error', message: 'An error occurred while connecting to the server.' });
                }
            });
        });

        // Toggle inputs on row selection
        $(document).on('change', '.row-select', function() {
            const row = $(this).closest('tr');
            const checked = $(this).is(':checked');
            row.find('input[type="time"]').prop('disabled', !checked);
        });

        // Select All handler
        $('#selectAllDays').on('change', function() {
            const checked = $(this).is(':checked');
            $('.row-select').prop('checked', checked).trigger('change');
        });

        // Save manual attendance
        $('#saveManualAttendanceBtn').on('click', function() {
            const employeeId = $('#manualEmpSelect').val();
            if (!employeeId) {
                iziToast.warning({ title: 'Validation Error', message: 'Please select an employee.' });
                return;
            }

            const attendanceData = [];
            let valid = true;

            $('.row-select:checked').each(function() {
                const row = $(this).closest('tr');
                const date = row.data('date');
                const checkIn = row.find('.check-in-input').val();
                const checkOut = row.find('.check-out-input').val();

                if (!checkIn || !checkOut) {
                    iziToast.warning({ title: 'Validation Error', message: `Please enter both Check-In and Check-Out times for ${date}.` });
                    valid = false;
                    return false; // break loop
                }

                // Check out must be after check in
                if (checkOut <= checkIn) {
                    iziToast.warning({ title: 'Validation Error', message: `Check-Out must be after Check-In for ${date}.` });
                    valid = false;
                    return false;
                }

                attendanceData.push({
                    date: date,
                    check_in: checkIn,
                    check_out: checkOut
                });
            });

            if (!valid) return;

            if (attendanceData.length === 0) {
                iziToast.warning({ title: 'No Selection', message: 'Please select at least one day to mark attendance.' });
                return;
            }

            const projectid = $("body").data("projectid");
            const loginid = $("body").data("loginid");

            showLoading(true);

            $.ajax({
                url: 'manualAttendance.php',
                type: 'POST',
                data: {
                    action: 'save_manual_attendance',
                    employee_id: employeeId,
                    projectid: projectid,
                    loginid: loginid,
                    attendance_data: JSON.stringify(attendanceData)
                },
                dataType: 'json',
                success: function(response) {
                    showLoading(false);
                    if (response.status === 'success') {
                        iziToast.success({
                            title: 'Success',
                            message: response.message,
                            onClosing: function() {
                                $('#loadCalendarBtn').trigger('click');
                            }
                        });
                    } else {
                        iziToast.error({ title: 'Error', message: response.message || 'Failed to save attendance.' });
                    }
                },
                error: function() {
                    showLoading(false);
                    iziToast.error({ title: 'Error', message: 'An error occurred while connecting to the server.' });
                }
            });
        });
    });

    function generateCalendar(year, month, data) {
        const tbody = $('#manualAttendanceTable tbody');
        tbody.empty();
        $('#selectAllDays').prop('checked', false);

        // Get last day of the selected month
        const lastDay = new Date(year, month, 0).getDate();

        // Check if selected month is the current month
        const today = new Date();
        const isCurrentMonth = (today.getFullYear() === parseInt(year) && (today.getMonth() + 1) === parseInt(month));
        const maxDay = isCurrentMonth ? today.getDate() : lastDay;

        const weekOffs = data.weekoffs || [];
        const holidays = data.holidays || {};
        const attendance = data.attendance || {};
        const shift = data.shift || { in: '09:30', out: '18:00' };

        for (let day = 1; day <= maxDay; day++) {
            const dateStr = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const dateObj = new Date(year, month - 1, day);

            // Day Name
            const dayNames = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
            const dayName = dayNames[dateObj.getDay()];

            // Day Number (1: Monday, 7: Sunday)
            let dayNo = dateObj.getDay();
            if (dayNo === 0) dayNo = 7;

            const isWeekOff = weekOffs.includes(dayNo);
            const holidayName = holidays[dateStr];
            const hasAttendance = attendance[dateStr];

            // Default values
            let checkInVal = shift.in || '';
            let checkOutVal = shift.out || '';
            let statusHtml = '';
            let rowClass = '';

            if (isWeekOff) {
                rowClass = 'table-light-info';
                statusHtml += `<span class="badge bg-soft-info text-info border border-info me-2 fs-11">Week-off</span>`;
            }

            if (holidayName) {
                rowClass = 'table-light-warning';
                statusHtml += `<span class="badge bg-soft-warning text-warning border border-warning me-2 fs-11">Holiday: ${holidayName}</span>`;
            }

            if (hasAttendance) {
                checkInVal = hasAttendance.CheckInTime || '';
                checkOutVal = hasAttendance.CheckOutTime || '';
                const statusClass = hasAttendance.ApprovalStatus === 'Approved' ? 'bg-soft-success text-success border-success' : 'bg-soft-danger text-danger border-danger';
                statusHtml += `<span class="badge ${statusClass} border fs-11">Present (${hasAttendance.ApprovalStatus})</span>`;
            } else if (!isWeekOff && !holidayName) {
                statusHtml += `<span class="text-muted small">No attendance marked</span>`;
            }

            // Date format: DD-MMM-YYYY
            const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
            const displayDate = `${String(day).padStart(2, '0')}-${monthNames[dateObj.getMonth()]}-${year}`;

            const rowHtml = `
                <tr class="${rowClass}" data-date="${dateStr}">
                    <td class="text-center">
                        <input class="form-check-input row-select" type="checkbox">
                    </td>
                    <td class="fw-bold">${displayDate}</td>
                    <td>${dayName}</td>
                    <td>
                        <input type="time" class="form-control check-in-input" value="${checkInVal}" disabled>
                    </td>
                    <td>
                        <input type="time" class="form-control check-out-input" value="${checkOutVal}" disabled>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">${statusHtml}</div>
                    </td>
                </tr>
            `;
            tbody.append(rowHtml);
        }

        $('#calendarCard').removeClass('d-none');
    }
</script>
