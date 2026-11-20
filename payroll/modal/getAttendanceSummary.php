<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require('../../connection.php');
require('../functions/hrm_FunctionMaster.php');
require('../functions/hrm_insertupdatesalary_functions.php');
require('../functions/comanphp_functions.php');
require('../functions/admin_functionmaster.php');
require('../functions/hrm_loginFunctions.php');

$response = [
    "status" => false,
    "data" => []
];

$projectid = $_POST['projectid'] ?? '';
$daterange = $_POST['daterange'] ?? '';

if (empty($projectid)) {
    echo json_encode(["status" => false, "message" => "Invalid Project"]);
    exit;
}

/* =========================
   DATE RANGE
========================= */
// $dateFrom = date('Y-m-01');
// $dateTo   = date('Y-m-t');
$dateFrom=date('Y-m-d');
$dateTo=date('Y-m-d');
if ($daterange!="") {
    if (strpos($daterange, " - ") !== false) {
        [$from, $to] = explode(" - ", $daterange);
        $dateFrom = date('Y-m-d', strtotime($from));
        $dateTo   = date('Y-m-d', strtotime($to));
    } else {
        $singleDate = date('Y-m-d', strtotime($daterange));
        $dateFrom = $singleDate;
        $dateTo   = $singleDate;
    }
}

// echo $dateFrom.'---'.$dateTo;

/* =========================
   FETCH ATTENDANCE DATA
========================= */
$attendanceSql = "
SELECT 
    a.EmployeeID,
    CONCAT(e.FirstName,' ',e.LastName) AS EmployeeName,
    a.AttendanceDate,
    TIME(a.CheckInTime) AS markin,
    TIME(a.CheckOutTime) AS markout,
    TIMESTAMPDIFF(MINUTE, a.CheckInTime, a.CheckOutTime) AS workingminutes,
    a.CheckInAddress,
    a.SourceIn,
    a.SourceOut,
    a.CheckOutAddress,
    e.InTime,
    e.OutTime
FROM hrm_EmployeeAttendance a
JOIN hrm_EmployeesMaster e ON e.EmployeeID = a.EmployeeID
WHERE a.ProjectID = ?
AND (a.tedit IS NULL OR a.tedit = '')
AND a.AttendanceDate BETWEEN ? AND ?
 order by a.CheckInTime";

$stmt = $hrmconn->prepare($attendanceSql);
$stmt->bind_param("sss", $projectid, $dateFrom, $dateTo);
$stmt->execute();
$res = $stmt->get_result();

$today = date('Y-m-d');
$markindata = [];
$present = [];
$late = [];
$early = [];
$mis = [];
$markedIds = [];
while ($row = $res->fetch_assoc()) {

    $workingDuration = ($row['workingminutes'] !== null)
        ? round($row['workingminutes'] / 60, 2)
        : "";

    $locationIn = ($row['CheckInAddress'] ?: '') . ($row['SourceIn'] ? ' (' . $row['SourceIn'] . ')' : '');
    $locationOut = ($row['CheckOutAddress'] ?: '') . ($row['SourceOut'] ? ' (' . $row['SourceOut'] . ')' : '');
    
    $fullLocation = "";
    if ($locationIn) $fullLocation .= "In: " . $locationIn;
    if ($locationOut) {
        if ($fullLocation) $fullLocation .= " | ";
        $fullLocation .= "Out: " . $locationOut;
    }

    $markInVal  = (!empty($row['markin']) && $row['markin'] !== '00:00:00') ? $row['markin'] : '';
    $markOutVal = (!empty($row['markout']) && $row['markout'] !== '00:00:00') ? $row['markout'] : '';

    $hasMarkIn  = !empty($markInVal);
    $hasMarkOut = !empty($markOutVal);

    $baseData = [
        "employeeid"     => $row['EmployeeID'],
        "empoyeename"    => $row['EmployeeName'],
        "attendancedate" => date('d-M-Y', strtotime($row['AttendanceDate'])),
        "markin"         => $hasMarkIn ? $markInVal : "-",
        "markout"        => $hasMarkOut ? $markOutVal : ($hasMarkIn ? "MIS" : "-"),
        "workingduration"=> $hasMarkOut ? $workingDuration : "-",
        "locationIn"     => $locationIn,
        "locationOut"    => $locationOut,
        "location"       => $fullLocation ?: '-'
    ];

    $markedIds[$row['EmployeeID']] = true;

    // ✅ Present (Mark-In and Mark-Out both exist)
    if ($hasMarkIn && $hasMarkOut) {
        $baseData['status'] = "Present";
        $present[$row['EmployeeID']] = $baseData;
    }

    // ⚠️ MIS Attendance (Mark-In exists but Mark-Out is missing/empty/00:00:00)
    if ($hasMarkIn && !$hasMarkOut) {
        $misDataCopy = $baseData;
        $misDataCopy['status'] = "MIS";
        $mis[$row['EmployeeID']] = $misDataCopy;
    }

    // ✅ Mark-In list (All employees who marked in)
    if ($hasMarkIn) {
        $markInDataCopy = $baseData;
        $markInDataCopy['status'] = $hasMarkOut ? "Present" : "MIS";
        $markindata[$row['EmployeeID']] = $markInDataCopy;
    }

    // ⏰ Late Login
    if ($hasMarkIn && !empty($row['InTime']) && $markInVal > $row['InTime']) {
        $lateDataCopy = $baseData;
        $lateDataCopy['status'] = "Late";
        $late[$row['EmployeeID']] = $lateDataCopy;
    }

    // 🚪 Early Logout
    if ($hasMarkOut && !empty($row['OutTime']) && $markOutVal < $row['OutTime']) {
        $earlyDataCopy = $baseData;
        $earlyDataCopy['status'] = "Early Out";
        $early[$row['EmployeeID']] = $earlyDataCopy;
    }
}
$stmt->close();

/* =========================
   PERMISSION LEAVE DATA
========================= */
$permissionSql = "
SELECT 
    l.EmployeeID,
    CONCAT(e.FirstName,' ',e.LastName) AS EmployeeName,
    l.LeaveStartDate,
    l.LeaveEndDate
FROM hrm_EmployeeLeaves l
JOIN hrm_EmployeesMaster e ON e.EmployeeID = l.EmployeeID
WHERE l.ProjectID = ?
AND l.LeaveType <>''
AND l.Status = 'Approved'
AND l.LeaveStartDate >= ?
AND l.LeaveStartDate <= ?
";

$stmt = $hrmconn->prepare($permissionSql);
$stmt->bind_param("sss", $projectid, $dateFrom, $dateTo);
$stmt->execute();
$res = $stmt->get_result();

$permission = [];
$onLeaveIds = [];

while ($row = $res->fetch_assoc()) {
    $permission[$row['EmployeeID']] = [
        "employeeid" => $row['EmployeeID'],
        "empoyeename" => $row['EmployeeName'],
        "attendancedate" => $row['LeaveStartDate'],
        "markin" => "",
        "markout" => "",
        "workingduration" => "",
        "location" => "",
        "status" => "Permission"
    ];
    $onLeaveIds[$row['EmployeeID']] = true;
}
$stmt->close();

/* =========================
   ALL ACTIVE EMPLOYEES
========================= */
$allEmpSql = "
SELECT EmployeeID,
       CONCAT(FirstName,' ',LastName) AS EmployeeName
FROM hrm_EmployeesMaster
WHERE projectid = ?
AND Status = 'Active'
AND isAttendanceRequired = 'yes'
";

$stmt = $hrmconn->prepare($allEmpSql);
$stmt->bind_param("s", $projectid);
$stmt->execute();
$res = $stmt->get_result();

$allEmployees = [];
while ($row = $res->fetch_assoc()) {
    $allEmployees[$row['EmployeeID']] = $row['EmployeeName'];
}
$stmt->close();

/* =========================
   ABSENT & UNINFORMED & WEEKOFF
========================= */
// Fetch WeekOffs
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

$absentData = [];
$permissionDataFiltered = [];
$uninformedDataFiltered = [];

foreach ($allEmployees as $empId => $empName) {

    if (!isset($markedIds[$empId])) {
        // Determine why they are absent
        $status = "Uninformed";
        if (isset($onLeaveIds[$empId])) {
            $status = "Permission";
        } else {
            // Check if any date in range is a weekoff
            $isWeekOff = false;
            if (isset($weekoffs[$empId])) {
                $start = new DateTime($dateFrom);
                $end = new DateTime($dateTo);
                $end->modify('+1 day');
                $interval = new DateInterval('P1D');
                $daterangePeriod = new DatePeriod($start, $interval, $end);
                foreach ($daterangePeriod as $date) {
                    $dateStr = $date->format('Y-m-d');
                    $dt = new DateTime($dateStr);
                    $dbDayNo = ($dt->format('w') == 0) ? 7 : (int) $dt->format('w');
                    if (in_array($dbDayNo, $weekoffs[$empId])) {
                        $isWeekOff = true;
                        break;
                    }
                }
            }
            if ($isWeekOff) {
                $status = "Weekoff";
            }
        }

        $baseAbsent = [
            "employeeid" => $empId,
            "empoyeename" => $empName,
            "attendancedate" => ($dateFrom == $dateTo) ? date('d-M-Y', strtotime($dateFrom)) : date('d-M-Y', strtotime($dateFrom)) . " to " . date('d-M-Y', strtotime($dateTo)),
            "markin" => "",
            "markout" => "",
            "workingduration" => "",
            "location" => "",
            "status" => $status
        ];

        $absentData[$empId] = $baseAbsent;

        if ($status === 'Permission') {
            $permissionDataFiltered[] = $baseAbsent;
        } elseif ($status === 'Uninformed') {
            $uninformedDataFiltered[] = $baseAbsent;
        }
    }
}

/* =========================
   FINAL RESPONSE
========================= */
$response['status'] = true;
$response['data'] = [
    
    "presentemployeecount" => count($present),
    "presentemployeedata"  => array_values($present),

    "markinemployeecount" => count($markindata),
    "markinemployeedata"  => array_values($markindata),
    "totalactivecount"    => count($allEmployees),

    "lateemployeecount" => count($late),
    "lateemployeedata"  => array_values($late),

    "earlylogout" => count($early),
    "earlylogoutdata" => array_values($early),

    "permission" => count($permissionDataFiltered),
    "permissiondata" => $permissionDataFiltered,

    "absent" => count($absentData),
    "absentdata" => array_values($absentData),

    "uninformed" => count($uninformedDataFiltered),
    "uninformeddata" => $uninformedDataFiltered,

    "misemployeecount" => count($mis),
    "misemployeedata"  => array_values($mis),

    "employees" => $allEmployees
];

echo json_encode($response);

