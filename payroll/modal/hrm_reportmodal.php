<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require('../../connection.php');
require('../functions/admin_functionmaster.php');
require('../functions/comanphp_functions.php');
require('../functions/hrm_FunctionMaster.php');
require('../functions/hrm_insertupdatesalary_functions.php');
require('../pdfroutines/tcpdf/tcpdf.php');
$action=$_GET['action'];

if ($action == 'getAttendancePAReport') {
    $projectid = $_POST['projectid'] ?? '';
    $datefrom = $_POST['datefrom'] ?? '';
    $dateto = $_POST['dateto'] ?? '';
    
    if (empty($projectid) || empty($datefrom) || empty($dateto)) {
        echo json_encode(['status' => false, 'message' => 'Missing parameters.']);
        exit;
    }

    $start = new DateTime($datefrom);
    $end = new DateTime($dateto);
    $end->modify('+1 day');
    $interval = new DateInterval('P1D');
    $daterange = new DatePeriod($start, $interval, $end);

    $dateColumns = [];
    foreach ($daterange as $date) {
        $dateColumns[] = $date->format('Y-m-d');
    }

    // 1. Fetch Employees
    $empSql = "SELECT e.EmployeeID, CONCAT(e.FirstName, ' ', e.LastName) as EmployeeName, 
               IFNULL(dept.DepartmentName, '-') as Dept, IFNULL(desg.DesignationName, '-') as Desg
               FROM hrm_EmployeesMaster e 
               LEFT JOIN hrm_DepartmentMaster dept ON e.DepartmentID = dept.DepartmentID
               LEFT JOIN hrm_DesignationsMaster desg ON e.DesignationID = desg.DesignationID
               WHERE e.ProjectID = ? AND e.Status = 'Active'";
    $stmt = $hrmconn->prepare($empSql);
    $stmt->bind_param("s", $projectid);
    $stmt->execute();
    $empRes = $stmt->get_result();
    $employees = [];
    while ($row = $empRes->fetch_assoc()) {
        $employees[] = $row;
    }
    $stmt->close();

    // 2. Fetch Holidays
    $holidays = [];
    $holSql = "SELECT DATE(HolidayDate) as HolidayDate FROM hrm_PayrollHolidaysMaster WHERE (tedit is null or tedit ='') AND projectid = ?";
    $hStmt = $hrmconn->prepare($holSql);
    $hStmt->bind_param("s", $projectid);
    $hStmt->execute();
    $hRes = $hStmt->get_result();
    while ($hRow = $hRes->fetch_assoc()) {
        $holidays[$hRow['HolidayDate']] = true;
    }
    $hStmt->close();

    // 3. Fetch WeekOffs
    $weekoffs = [];
    $woSql = "SELECT EmployeeID, WeekOffDayNo FROM hrm_EmployeeWeekOff WHERE ProjectID=? AND Status='Active'";
    $wStmt = $hrmconn->prepare($woSql);
    $wStmt->bind_param("s", $projectid);
    $wStmt->execute();
    $wRes = $wStmt->get_result();
    while ($wRow = $wRes->fetch_assoc()) {
        $weekoffs[$wRow['EmployeeID']][] = (int)$wRow['WeekOffDayNo'];
    }
    $wStmt->close();

    // 4. Fetch Approved Leaves
    $leaves = [];
    $lSql = "SELECT EmployeeID, LeaveStartDate, LeaveEndDate FROM hrm_EmployeeLeaves WHERE ProjectID=? AND Status='Approved' AND LeaveStartDate <= ? AND LeaveEndDate >= ?";
    $lStmt = $hrmconn->prepare($lSql);
    $lStmt->bind_param("sss", $projectid, $dateto, $datefrom);
    $lStmt->execute();
    $lRes = $lStmt->get_result();
    while ($lRow = $lRes->fetch_assoc()) {
        $p = new DatePeriod(
            new DateTime($lRow['LeaveStartDate']),
            new DateInterval('P1D'),
            (new DateTime($lRow['LeaveEndDate']))->modify('+1 day')
        );
        foreach ($p as $d) {
            $leaves[$lRow['EmployeeID']][$d->format('Y-m-d')] = true;
        }
    }
    $lStmt->close();

    // 5. Fetch Attendance
    $attSql = "SELECT EmployeeID, AttendanceDate, Status, CheckInTime, CheckOutTime, WorkingHours, LeaveType, ApprovalStatus FROM hrm_EmployeeAttendance WHERE ProjectID = ? AND AttendanceDate >= ? AND AttendanceDate <= ?";
    $stmt2 = $hrmconn->prepare($attSql);
    $stmt2->bind_param("sss", $projectid, $datefrom, $dateto);
    $stmt2->execute();
    $attRes = $stmt2->get_result();
    $attendanceMap = [];
    while ($row = $attRes->fetch_assoc()) {
        $attendanceMap[$row['EmployeeID']][$row['AttendanceDate']][] = $row;
    }
    $stmt2->close();

    $reportData = [];
    foreach ($employees as $emp) {
        $empId = $emp['EmployeeID'];
        $empData = [
            'EmployeeCode' => $empId,
            'EmployeeName' => $emp['EmployeeName'],
            'Dept' => $emp['Dept'],
            'Desg' => $emp['Desg'],
            'attendance' => [],
            'totals' => ['Total' => 0, 'P' => 0, 'A' => 0, 'HalfDay' => 0, 'Leave' => 0, 'Permission' => 0, 'WO' => 0, 'H' => 0, 'MIS' => 0]
        ];
        
        foreach ($dateColumns as $dateStr) {
            $dt = new DateTime($dateStr);
            $dbDayNo = ($dt->format('w') == 0) ? 7 : (int) $dt->format('w');
            
            $isHoliday = isset($holidays[$dateStr]);
            $isWeekOff = isset($weekoffs[$empId]) && in_array($dbDayNo, $weekoffs[$empId]);
            $isLeave = isset($leaves[$empId][$dateStr]);
            
            $allAttRecords = $attendanceMap[$empId][$dateStr] ?? [];
            $attRecords = [];
            foreach ($allAttRecords as $rec) {
                if ($rec['ApprovalStatus'] !== 'Rejected') {
                    $attRecords[] = $rec;
                }
            }
            $isPresent = count($attRecords) > 0;
            
            $hasLeave = false;
            foreach ($attRecords as $rec) {
                if (!empty($rec['LeaveType']) && strtolower($rec['ApprovalStatus']) == 'approved') {
                    $hasLeave = true;
                }
            }

            if ($isPresent && $hasLeave) {
                $isLeave = true;
            }

            // Get dynamic shift hours for this employee
            $shiftData = getShiftHours($hrmconn, $empId);
            $FULL_DAY_HOURS = convertTimeToDecimal($shiftData['full']);
            $HALF_DAY_HOURS = convertTimeToDecimal($shiftData['half']);

            $status = 'A';
            
            if ($isLeave) {
                $status = 'L';
                $empData['totals']['Leave']++;
            } elseif ($isPresent) {
                $dayTotalHours = 0;
                $dayHasMis = false;
                $dayHasHalf = false;
                $hasMissingOut = false;
                
                foreach ($attRecords as $attRecord) {
                    if (empty($attRecord['CheckOutTime'])) {
                        $hasMissingOut = true;
                    }
                    if ($attRecord['WorkingHours']) {
                        $dayTotalHours += convertTimeToDecimal($attRecord['WorkingHours']);
                    }
                    $dbStatus = strtolower($attRecord['Status'] ?? '');
                    if (strpos($dbStatus, 'mis') !== false || strpos($dbStatus, 'missing') !== false) {
                        $dayHasMis = true;
                    } elseif (strpos($dbStatus, 'half') !== false) {
                        $dayHasHalf = true;
                    }
                }
                
                if ($hasMissingOut || $dayHasMis) {
                    $status = 'MIS';
                    $empData['totals']['MIS']++;
                    $empData['totals']['A']++; // MIS counts as Absent in summary
                } elseif ($dayTotalHours >= $FULL_DAY_HOURS) {
                    $status = 'P';
                    $empData['totals']['P']++;
                    if ($isHoliday) {
                        $empData['totals']['H']++;
                    }
                    if ($isWeekOff) {
                        $empData['totals']['WO']++;
                    }
                } elseif ($dayTotalHours >= $HALF_DAY_HOURS || $dayHasHalf) {
                    $status = 'Half';
                    $empData['totals']['HalfDay']++;
                    if ($isHoliday) {
                        $empData['totals']['H']++;
                    }
                    if ($isWeekOff) {
                        $empData['totals']['WO']++;
                    }
                } else {
                    // Worked less than Half day threshold
                    if ($isHoliday) {
                        $status = 'H';
                        $empData['totals']['H']++;
                    } elseif ($isWeekOff) {
                        $status = 'W/O';
                        $empData['totals']['WO']++;
                    } else {
                        $status = 'A';
                        $empData['totals']['A']++;
                    }
                }
            } else {
                if ($isHoliday) {
                    $status = 'H';
                    $empData['totals']['H']++;
                } elseif ($isWeekOff) {
                    $status = 'W/O';
                    $empData['totals']['WO']++;
                } else {
                    $status = 'A';
                    $empData['totals']['A']++;
                }
            }
            
            $empData['attendance'][$dateStr] = $status;
            $empData['totals']['Total']++;
        }
        $reportData[] = $empData;
    }

    echo json_encode(['status' => true, 'dates' => $dateColumns, 'data' => $reportData]);
    exit;
}

if ($action == 'getAttendanceTimeReport') {
    $projectid = $_POST['projectid'] ?? '';
    $datefrom = $_POST['datefrom'] ?? '';
    $dateto = $_POST['dateto'] ?? '';
    
    if (empty($projectid) || empty($datefrom) || empty($dateto)) {
        echo json_encode(['status' => false, 'message' => 'Missing parameters.']);
        exit;
    }

    $start = new DateTime($datefrom);
    $end = new DateTime($dateto);
    $end->modify('+1 day');
    $interval = new DateInterval('P1D');
    $daterange = new DatePeriod($start, $interval, $end);

    $dateColumns = [];
    foreach ($daterange as $date) {
        $dateColumns[] = $date->format('Y-m-d');
    }

    // 1. Fetch Employees
    $empSql = "SELECT e.EmployeeID, CONCAT(e.FirstName, ' ', e.LastName) as EmployeeName, 
               IFNULL(dept.DepartmentName, '-') as Dept, IFNULL(desg.DesignationName, '-') as Desg
               FROM hrm_EmployeesMaster e 
               LEFT JOIN hrm_DepartmentMaster dept ON e.DepartmentID = dept.DepartmentID
               LEFT JOIN hrm_DesignationsMaster desg ON e.DesignationID = desg.DesignationID
               WHERE e.ProjectID = ? AND e.Status = 'Active'";
    $stmt = $hrmconn->prepare($empSql);
    $stmt->bind_param("s", $projectid);
    $stmt->execute();
    $empRes = $stmt->get_result();
    $employees = [];
    while ($row = $empRes->fetch_assoc()) {
        $employees[] = $row;
    }
    $stmt->close();

    // 2. Fetch Holidays
    $holidays = [];
    $holSql = "SELECT DATE(HolidayDate) as HolidayDate FROM hrm_PayrollHolidaysMaster WHERE (tedit is null or tedit ='') AND projectid = ?";
    $hStmt = $hrmconn->prepare($holSql);
    $hStmt->bind_param("s", $projectid);
    $hStmt->execute();
    $hRes = $hStmt->get_result();
    while ($hRow = $hRes->fetch_assoc()) {
        $holidays[$hRow['HolidayDate']] = true;
    }
    $hStmt->close();

    // 3. Fetch WeekOffs
    $weekoffs = [];
    $woSql = "SELECT EmployeeID, WeekOffDayNo FROM hrm_EmployeeWeekOff WHERE ProjectID=? AND Status='Active'";
    $wStmt = $hrmconn->prepare($woSql);
    $wStmt->bind_param("s", $projectid);
    $wStmt->execute();
    $wRes = $wStmt->get_result();
    while ($wRow = $wRes->fetch_assoc()) {
        $weekoffs[$wRow['EmployeeID']][] = (int)$wRow['WeekOffDayNo'];
    }
    $wStmt->close();

    // 4. Fetch Approved Leaves
    $leaves = [];
    $lSql = "SELECT EmployeeID, LeaveStartDate, LeaveEndDate FROM hrm_EmployeeLeaves WHERE ProjectID=? AND Status='Approved' AND LeaveStartDate <= ? AND LeaveEndDate >= ?";
    $lStmt = $hrmconn->prepare($lSql);
    $lStmt->bind_param("sss", $projectid, $dateto, $datefrom);
    $lStmt->execute();
    $lRes = $lStmt->get_result();
    while ($lRow = $lRes->fetch_assoc()) {
        $p = new DatePeriod(
            new DateTime($lRow['LeaveStartDate']),
            new DateInterval('P1D'),
            (new DateTime($lRow['LeaveEndDate']))->modify('+1 day')
        );
        foreach ($p as $d) {
            $leaves[$lRow['EmployeeID']][$d->format('Y-m-d')] = true;
        }
    }
    $lStmt->close();

    // 5. Fetch Attendance
    $attSql = "SELECT EmployeeID, AttendanceDate, Status, CheckInTime, CheckOutTime, WorkingHours, LeaveType, ApprovalStatus FROM hrm_EmployeeAttendance WHERE ProjectID = ? AND AttendanceDate >= ? AND AttendanceDate <= ?";
    $stmt2 = $hrmconn->prepare($attSql);
    $stmt2->bind_param("sss", $projectid, $datefrom, $dateto);
    $stmt2->execute();
    $attRes = $stmt2->get_result();
    $attendanceMap = [];
    while ($row = $attRes->fetch_assoc()) {
        $attendanceMap[$row['EmployeeID']][$row['AttendanceDate']][] = $row;
    }
    $stmt2->close();

    $reportData = [];
    foreach ($employees as $emp) {
        $empId = $emp['EmployeeID'];
        $empData = [
            'EmployeeCode' => $empId,
            'EmployeeName' => $emp['EmployeeName'],
            'Dept' => $emp['Dept'],
            'Desg' => $emp['Desg'],
            'attendance' => [],
            'TotalHrsDecimal' => 0,
            'totals' => ['Total' => 0, 'P' => 0, 'A' => 0, 'HalfDay' => 0, 'Leave' => 0, 'Permission' => 0, 'WO' => 0, 'H' => 0, 'MIS' => 0]
        ];
        
        foreach ($dateColumns as $dateStr) {
            $dt = new DateTime($dateStr);
            $dbDayNo = ($dt->format('w') == 0) ? 7 : (int) $dt->format('w');
            
            $isHoliday = isset($holidays[$dateStr]);
            $isWeekOff = isset($weekoffs[$empId]) && in_array($dbDayNo, $weekoffs[$empId]);
            $isLeave = isset($leaves[$empId][$dateStr]);
            
            $allAttRecords = $attendanceMap[$empId][$dateStr] ?? [];
            $attRecords = [];
            foreach ($allAttRecords as $rec) {
                if ($rec['ApprovalStatus'] !== 'Rejected') {
                    $attRecords[] = $rec;
                }
            }
            $isPresent = count($attRecords) > 0;
            
            $hasLeave = false;
            foreach($attRecords as $rec) {
                if (!empty($rec['LeaveType']) && strtolower($rec['ApprovalStatus']) == 'approved') {
                    $hasLeave = true;
                }
            }

            if ($isPresent && $hasLeave) {
                $isLeave = true;
            }

            // Get dynamic shift hours for this employee
            $shiftData = getShiftHours($hrmconn, $empId);
            $FULL_DAY_HOURS = convertTimeToDecimal($shiftData['full']);
            $HALF_DAY_HOURS = convertTimeToDecimal($shiftData['half']);

            $cellText = 'A';
            
            if ($isLeave) {
                $cellText = 'L';
                $empData['totals']['Leave']++;
            } elseif ($isPresent) {
                $timeStrs = [];
                $dayTotalHours = 0;
                $dayHasMis = false;
                $dayHasHalf = false;
                $hasMissingOut = false;
                
                foreach($attRecords as $attRecord) {
                    $in = $attRecord['CheckInTime'] ? date('H:i', strtotime($attRecord['CheckInTime'])) : '';
                    $out = $attRecord['CheckOutTime'] ? date('H:i', strtotime($attRecord['CheckOutTime'])) : '';
                    
                    if ($in && $out) {
                        $inDT = new DateTime($attRecord['CheckInTime']);
                        $outDT = new DateTime($attRecord['CheckOutTime']);
                        $diff = $inDT->diff($outDT);
                        $totalHours = ($diff->days * 24) + $diff->h;
                        $hrsStr = $totalHours . 'h ' . $diff->i . 'm';
                        $timeStrs[] = $in . " - " . $out . " <br/><small class='text-muted'>(" . $hrsStr . ")</small>";
                    } elseif ($in) {
                        $timeStrs[] = $in . " - MIS";
                        $hasMissingOut = true;
                    } else {
                        $timeStrs[] = "MIS - MIS";
                        $hasMissingOut = true;
                    }
                    
                    if ($attRecord['WorkingHours']) {
                        $decimalHrs = convertTimeToDecimal($attRecord['WorkingHours']);
                        $empData['TotalHrsDecimal'] += $decimalHrs;
                        $dayTotalHours += $decimalHrs;
                    }

                    $dbStatus = strtolower($attRecord['Status'] ?? '');
                    if (strpos($dbStatus, 'mis') !== false || strpos($dbStatus, 'missing') !== false) {
                        $dayHasMis = true;
                    } elseif (strpos($dbStatus, 'half') !== false) {
                        $dayHasHalf = true;
                    }
                }
                $cellText = implode('<br>', $timeStrs);

                if ($hasMissingOut || $dayHasMis) {
                    $empData['totals']['MIS']++;
                    $empData['totals']['A']++; // MIS counts as Absent in summary
                } elseif ($dayTotalHours >= $FULL_DAY_HOURS) {
                    $empData['totals']['P']++;
                    if ($isHoliday) {
                        $empData['totals']['H']++;
                    }
                    if ($isWeekOff) {
                        $empData['totals']['WO']++;
                    }
                } elseif ($dayTotalHours >= $HALF_DAY_HOURS || $dayHasHalf) {
                    $empData['totals']['HalfDay']++;
                    if ($isHoliday) {
                        $empData['totals']['H']++;
                    }
                    if ($isWeekOff) {
                        $empData['totals']['WO']++;
                    }
                } else {
                    // Worked less than Half day threshold
                    if ($isHoliday) {
                        $cellText = 'H';
                        $empData['totals']['H']++;
                    } elseif ($isWeekOff) {
                        $cellText = 'W/O';
                        $empData['totals']['WO']++;
                    } else {
                        $cellText = 'A';
                        $empData['totals']['A']++;
                    }
                }
            } else {
                if ($isHoliday) {
                    $cellText = 'H';
                    $empData['totals']['H']++;
                } elseif ($isWeekOff) {
                    $cellText = 'W/O';
                    $empData['totals']['WO']++;
                } else {
                    $cellText = 'A';
                    $empData['totals']['A']++;
                }
            }
            
            $empData['totals']['Total']++;
            $empData['attendance'][$dateStr] = $cellText;
        }
        
        // Convert decimal hours to HH:MM format
        $totalMinutes = floor($empData['TotalHrsDecimal'] * 60);
        $hh = str_pad(floor($totalMinutes / 60), 2, "0", STR_PAD_LEFT);
        $mm = str_pad($totalMinutes % 60, 2, "0", STR_PAD_LEFT);
        $empData['TotalHrsFormatted'] = $hh . ":" . $mm;
        
        $reportData[] = $empData;
    }

    echo json_encode(['status' => true, 'dates' => $dateColumns, 'data' => $reportData]);
    exit;
}
?>