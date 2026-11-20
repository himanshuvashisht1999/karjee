<?php
/**
 * LABOURDASH MODAL - Backend API Handler
 * Handles all AJAX calls from the Labour Dashboard
 * DB: hrmplus → hrm_LabourCalculatedSalary + hrm_labourcalculateattendance + hrm_LabourMaster
 *
 * Actions:
 *   - GetLabourAttendanceSummary   → monthly dots + day counts
 *   - GetLabourMonthAttendance     → records for a selected month
 *   - GetLabourPaymentHistory      → payment records for date range
 */

if (session_status() === PHP_SESSION_NONE) session_start();
if (!$_SESSION['sessionid']) {
    echo json_encode(["status" => "Error", "message" => "Session expired"]);
    exit;
}

include("../../connection.php");
include("../../labourpayment/functions/hrm_FunctionMaster.php");
include("../../labourpayment/functions/admin_functionmaster.php");
include("../../labourpayment/functions/comanphp_functions.php");
include("../../labourpayment/functions/hrm_loginFunctions.php");

header("Content-Type: application/json");

$action       = isset($_GET['action'])       ? trim($_GET['action'])        : (isset($_POST['action']) ? trim($_POST['action']) : '');
$companyid    = isset($_POST['companyid'])   ? trim($_POST['companyid'])    : '';
$subcompanyid = isset($_POST['subcompanyid'])? trim($_POST['subcompanyid']) : '';
$projectid    = isset($_POST['projectid'])   ? trim($_POST['projectid'])    : '';
$employeeID   = isset($_POST['employeeID'])  ? trim($_POST['employeeID'])   : '';

// Use hrmconn for labour tables (hrmplus DB)
$db = $hrmconn;

/* ======================================================
   ACTION: GetLabourAttendanceSummary
   Returns monthly summary for the whole year
   (used for "dot" indicators on month strip)
   ====================================================== */
if ($action === "GetLabourAttendanceSummary") {
    $year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');
    $empEsc  = $db->real_escape_string($employeeID);
    $projEsc = $db->real_escape_string($projectid);

    // 1. Fetch all raw attendance records for the year to count actual present days
    $qAtt = "SELECT 
                attendancedate,
                checkintime_mn, checkouttime_mn, workinghours_mn,
                checkintime_dp, checkouttime_dp, workinghours_dp,
                status
             FROM hrm_labourattendance
             WHERE labourid = ? AND projectid = ? AND YEAR(attendancedate) = ?";
    $stmtAtt = $db->prepare($qAtt);
    $stmtAtt->bind_param("ssi", $employeeID, $projectid, $year);
    $stmtAtt->execute();
    $resAtt = $stmtAtt->get_result();
    
    $presentCounts = array_fill(1, 12, 0); // Months 1 to 12
    
    while ($rowAtt = $resAtt->fetch_assoc()) {
        $mo = intval(date('n', strtotime($rowAtt['attendancedate'])));
        
        $ciMN = $rowAtt['checkintime_mn'];
        $coMN = $rowAtt['checkouttime_mn'];
        $whMN = $rowAtt['workinghours_mn'];
        
        $ciDP = $rowAtt['checkintime_dp'];
        $coDP = $rowAtt['checkouttime_dp'];
        $whDP = $rowAtt['workinghours_dp'];
        
        $hasCI = (!empty($ciMN) && $ciMN !== '0000-00-00 00:00:00') || (!empty($ciDP) && $ciDP !== '0000-00-00 00:00:00');
        $hasCO = (!empty($coMN) && $coMN !== '0000-00-00 00:00:00') || (!empty($coDP) && $coDP !== '0000-00-00 00:00:00');
        
        if ($hasCI && $hasCO) {
            $maxHrs = 0.0;
            if (!empty($coMN) && !empty($whMN)) {
                $p = explode(':', $whMN);
                $h = isset($p[0]) ? intval($p[0]) : 0;
                $m = isset($p[1]) ? intval($p[1]) : 0;
                $maxHrs = max($maxHrs, $h + ($m / 60.0));
            }
            if (!empty($coDP) && !empty($whDP)) {
                $p = explode(':', $whDP);
                $h = isset($p[0]) ? intval($p[0]) : 0;
                $m = isset($p[1]) ? intval($p[1]) : 0;
                $maxHrs = max($maxHrs, $h + ($m / 60.0));
            }
            if ($maxHrs >= 2.0) {
                $presentCounts[$mo]++;
            }
        }
    }
    $stmtAtt->close();

    // 2. Initialize summary for all 12 months
    $summary = [];
    for ($m = 1; $m <= 12; $m++) {
        $key = $year . '-' . $m;
        $summary[$key] = [
            'days'      => (float)$presentCounts[$m],
            'basic'     => 0.0,
            'incentive' => 0.0,
            'overtime'  => 0.0,
            'net'       => 0.0,
        ];
    }

    // 3. Merge calculated salary numbers if they exist
    $qSalary = "SELECT
            YEAR(s.DateFrom)  AS yr,
            MONTH(s.DateFrom) AS mo,
            SUM(COALESCE(
                (SELECT SUM(a.CalculatedPresentDays)
                 FROM hrm_labourcalculateattendance a
                 WHERE a.ProjectID  = s.ProjectID
                   AND a.EmployeeID = s.EmployeeID
                   AND a.DateFrom   = s.DateFrom
                   AND a.DateTo     = s.Dateto), 0
            )) AS TotalDays,
            SUM(IFNULL(s.basiclabourpayment, 0)) AS TotalBasic,
            SUM(IFNULL(s.incentivegrafting, 0) + IFNULL(s.incentiveseasional, 0)) AS TotalIncentive,
            SUM(IFNULL(s.overtimepayment, 0))   AS TotalOvertime,
            SUM(IFNULL(s.NetSalary, 0))          AS TotalNet
          FROM hrm_LabourCalculatedSalary s
          WHERE s.EmployeeID = '{$empEsc}'
            AND s.ProjectID  = '{$projEsc}'
            AND YEAR(s.DateFrom) = {$year}
          GROUP BY YEAR(s.DateFrom), MONTH(s.DateFrom)";
          
    $resultSalary = $db->query($qSalary);
    if ($resultSalary && $resultSalary->num_rows > 0) {
        while ($row = $resultSalary->fetch_assoc()) {
            $mo = intval($row['mo']);
            $key = $row['yr'] . '-' . $mo;
            if (isset($summary[$key])) {
                $summary[$key]['basic']     = (float)$row['TotalBasic'];
                $summary[$key]['incentive'] = (float)$row['TotalIncentive'];
                $summary[$key]['overtime']  = (float)$row['TotalOvertime'];
                $summary[$key]['net']       = (float)$row['TotalNet'];
                
                // If official calculated days exist, prioritize it
                if ((float)$row['TotalDays'] > 0) {
                    $summary[$key]['days'] = (float)$row['TotalDays'];
                }
            }
        }
    }

    echo json_encode(["status" => "Success", "summary" => $summary]);
    exit;
}

/* ======================================================
   ACTION: GetLabourMonthAttendance
   Returns detailed records for a specific month
   ====================================================== */
if ($action === "GetLabourMonthAttendance") {
    $year    = isset($_POST['year'])  ? intval($_POST['year'])  : date('Y');
    $month   = isset($_POST['month']) ? intval($_POST['month']) : date('n');
    $empEsc  = $db->real_escape_string($employeeID);
    $projEsc = $db->real_escape_string($projectid);

    $q = "SELECT
            attendanceid,
            attendancedate,
            checkintime_mn, checkouttime_mn, workinghours_mn,
            checkintime_dp, checkouttime_dp, workinghours_dp,
            status
          FROM hrm_labourattendance
          WHERE labourid           = '{$empEsc}'
            AND projectid          = '{$projEsc}'
            AND YEAR(attendancedate)  = {$year}
            AND MONTH(attendancedate) = {$month}
          ORDER BY attendancedate ASC";

    $result  = $db->query($q);
    $records = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $records[] = [
                'attendanceid'    => $row['attendanceid'],
                'attendancedate'  => $row['attendancedate'],
                'checkintime_mn'  => $row['checkintime_mn'],
                'checkouttime_mn' => $row['checkouttime_mn'],
                'workinghours_mn' => $row['workinghours_mn'],
                'checkintime_dp'  => $row['checkintime_dp'],
                'checkouttime_dp' => $row['checkouttime_dp'],
                'workinghours_dp' => $row['workinghours_dp'],
                'status'          => $row['status']
            ];
        }
        echo json_encode(["status" => "Success", "records" => $records]);
    } else {
        echo json_encode(["status" => "NoData", "message" => "No records found.", "records" => []]);
    }
    exit;
}

/* ======================================================
   ACTION: GetLabourPaymentHistory
   Returns payment records for a date range
   ====================================================== */
if ($action === "GetLabourPaymentHistory") {
    $datefrom = isset($_POST['datefrom']) ? trim($_POST['datefrom']) : '';
    $dateto   = isset($_POST['dateto'])   ? trim($_POST['dateto'])   : '';

    if (!$datefrom || !$dateto) {
        echo json_encode(["status" => "Error", "message" => "Date range required."]);
        exit;
    }

    $empEsc   = $db->real_escape_string($employeeID);
    $projEsc  = $db->real_escape_string($projectid);
    $fromEsc  = $db->real_escape_string($datefrom);
    $toEsc    = $db->real_escape_string($dateto);

    $q = "SELECT
            s.SalaryID,
            s.EmployeeID,
            s.DateFrom,
            s.Dateto                             AS DateTo,
            s.PayPerDay                          AS PerDayPayment,
            IFNULL(s.basiclabourpayment, 0)      AS BasicAmount,
            IFNULL(s.incentivegrafting, 0)        AS IncentiveGrafting,
            IFNULL(s.incentiveseasional, 0)       AS IncentiveSeasional,
            IFNULL(s.overtimepayment, 0)          AS OvertimeAmount,
            IFNULL(s.otherpayment, 0)             AS OtherAmount,
            IFNULL(s.NetSalary, 0)                AS NetPayable,
            IFNULL(s.PaymentStatus, 'pending')    AS PaymentStatus,
            s.PaymentDate,
            s.requestno                           AS ReferenceNo,
            COALESCE(
               (SELECT SUM(a.CalculatedPresentDays)
                FROM hrm_labourcalculateattendance a
                WHERE a.ProjectID  = s.ProjectID
                  AND a.EmployeeID = s.EmployeeID
                  AND a.DateFrom   = s.DateFrom
                  AND a.DateTo     = s.Dateto), 0
            ) AS PresentDays
          FROM hrm_LabourCalculatedSalary s
          WHERE s.EmployeeID  = '{$empEsc}'
            AND s.ProjectID   = '{$projEsc}'
            AND s.DateFrom   >= '{$fromEsc}'
            AND s.Dateto     <= '{$toEsc}'
          ORDER BY s.DateFrom DESC";

    $result  = $db->query($q);
    $records = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $records[] = [
                'SalaryID'          => $row['SalaryID'],
                'EmployeeID'        => $row['EmployeeID'],
                'DateFrom'          => $row['DateFrom'],
                'DateTo'            => $row['DateTo'],
                'PresentDays'       => $row['PresentDays'],
                'PerDayPayment'     => $row['PerDayPayment'],
                'BasicAmount'       => $row['BasicAmount'],
                'IncentiveGrafting' => $row['IncentiveGrafting'],
                'IncentiveSeasional'=> $row['IncentiveSeasional'],
                'OvertimeAmount'    => $row['OvertimeAmount'],
                'OtherAmount'       => $row['OtherAmount'],
                'NetPayable'        => $row['NetPayable'],
                'PaymentStatus'     => $row['PaymentStatus'],
                'PaymentDate'       => $row['PaymentDate'],
                'ReferenceNo'       => $row['ReferenceNo'],
            ];
        }
        echo json_encode(["status" => "Success", "records" => $records, "count" => count($records)]);
    } else {
        echo json_encode(["status" => "NoData", "message" => "No payment records found.", "records" => []]);
    }
    exit;
}

/* ======================================================
   ACTION: MarkManualAttendance
   Allows admin/supervisor to manually insert/update attendance for a date
   ====================================================== */
if ($action === "MarkManualAttendance") {
    $empEsc       = $db->real_escape_string($employeeID);
    $projEsc      = $db->real_escape_string($projectid);
    $attDate      = isset($_POST['attendancedate']) ? trim($_POST['attendancedate']) : '';
    $checkin      = isset($_POST['checkin']) ? trim($_POST['checkin']) : '09:00';
    $checkout     = isset($_POST['checkout']) ? trim($_POST['checkout']) : '18:00';
    $loginnameEsc = $db->real_escape_string(isset($_SESSION['loginname']) ? $_SESSION['loginname'] : (isset($_POST['loginname']) ? $_POST['loginname'] : 'Admin'));

    if (empty($attDate)) {
        echo json_encode(["status" => "Error", "message" => "Attendance date is required."]);
        exit;
    }

    $checkinDT  = $attDate . ' ' . $checkin . ':00';
    $checkoutDT = $attDate . ' ' . $checkout . ':00';

    $timeIn  = strtotime($checkinDT);
    $timeOut = strtotime($checkoutDT);
    if ($timeOut <= $timeIn) {
        echo json_encode(["status" => "Error", "message" => "Check-out time must be greater than Check-in time."]);
        exit;
    }

    $diff  = $timeOut - $timeIn;
    $hours = floor($diff / 3600);
    $mins  = floor(($diff % 3600) / 60);
    $secs  = $diff % 60;
    $wh    = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

    // Fetch registered location ID from Labour Master
    $registeredLocationId = '';
    $stmtLoc = $db->prepare("SELECT locationid FROM hrm_LabourMaster WHERE EmployeeID = ? LIMIT 1");
    $stmtLoc->bind_param("s", $employeeID);
    $stmtLoc->execute();
    $locRow = $stmtLoc->get_result()->fetch_assoc();
    $stmtLoc->close();
    if ($locRow && !empty($locRow['locationid'])) {
        $registeredLocationId = $locRow['locationid'];
    }

    $locationName   = isset($_POST['locationName']) ? trim($_POST['locationName']) : 'Manual';
    $locationId     = isset($_POST['locationId']) ? trim($_POST['locationId']) : '';
    $locationManual = ($locationName === 'Manual' || empty($locationName)) ? 'Manual' : ($locationName . ' - Manual');
    $scannerManual  = 'Manual';

    $labourlocationid = !empty($registeredLocationId) ? $registeredLocationId : $locationId;
    $mnLocationId     = !empty($locationId) ? $locationId : $labourlocationid;

    // Check if record already exists where (tedit IS NULL OR tedit = '')
    $stmtFetch = $db->prepare(
        "SELECT attendanceid, checkintime_mn, checkouttime_mn, checkintime_dp, checkouttime_dp, checkinlocation_mn, checkinlocation_dp, labourlocationid
         FROM hrm_labourattendance
         WHERE labourid = ? AND attendancedate = ? AND (tedit IS NULL OR tedit = '')
         LIMIT 1"
    );
    $stmtFetch->bind_param("ss", $employeeID, $attDate);
    $stmtFetch->execute();
    $row = $stmtFetch->get_result()->fetch_assoc();
    $stmtFetch->close();

    if ($row) {
        $attendanceid = intval($row['attendanceid']);

        $stmtUpdate = $db->prepare(
            "UPDATE hrm_labourattendance SET
                checkintime_mn = ?,
                checkouttime_mn = ?,
                workinghours_mn = ?,
                checkintime_dp = ?,
                checkouttime_dp = ?,
                workinghours_dp = ?,
                checkinlocation_mn = IF(? != '', ?, checkinlocation_mn),
                checkoutlocation_mn = IF(? != '', ?, checkoutlocation_mn),
                checkinby_mn = IF(checkinby_mn IS NULL OR checkinby_mn = '', ?, checkinby_mn),
                checkoutby_mn = ?,
                checkinby_dp = IF(checkinby_dp IS NULL OR checkinby_dp = '', ?, checkinby_dp),
                checkoutby_dp = ?,
                status = 'Present',
                updated_at = NOW(),
                updated_by = ?
             WHERE attendanceid = ?"
        );

        if (!$stmtUpdate) {
            echo json_encode(["status" => "Error", "message" => "Prepare error: " . $db->error]);
            exit;
        }

        $stmtUpdate->bind_param(
            "sssssssssssssssi",
            $checkinDT,
            $checkoutDT,
            $wh,
            $checkinDT,
            $checkoutDT,
            $wh,
            $mnLocationId,
            $mnLocationId,
            $mnLocationId,
            $mnLocationId,
            $loginnameEsc,
            $loginnameEsc,
            $loginnameEsc,
            $loginnameEsc,
            $loginnameEsc,
            $attendanceid
        );

        $ok = $stmtUpdate->execute();
        $stmtUpdate->close();

        if ($ok) {
            echo json_encode(["status" => "Success", "message" => "Attendance updated successfully!"]);
        } else {
            echo json_encode(["status" => "Error", "message" => "Database error: " . $db->error]);
        }
        exit;
    }

    // INSERT new record
    $stmtInsert = $db->prepare(
        "INSERT INTO hrm_labourattendance (
            projectid, labourid, labourlocationid, attendancedate,
            checkintime_mn, checkouttime_mn, workinghours_mn,
            checkintime_dp, checkouttime_dp, workinghours_dp,
            checkinlocation_mn, checkoutlocation_mn,
            checkinlocation_dp, checkoutlocation_dp,
            checkinby_mn, checkoutby_mn,
            checkinscannerid_mn, checkoutscannerid_mn,
            checkinby_dp, checkoutby_dp,
            checkinscannerid_dp, checkoutscannerid_dp,
            status, updated_at, updated_by
         ) VALUES (
            ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?,
            ?, ?,
            ?, ?,
            ?, ?,
            ?, ?,
            ?, ?,
            ?, ?,
            'Present', NOW(), ?
         )"
    );
    $stmtInsert->bind_param(
        "sssssssssssssssssssssss",
        $projectid, $employeeID, $labourlocationid, $attDate,
        $checkinDT, $checkoutDT, $wh,
        $checkinDT, $checkoutDT, $wh,
        $mnLocationId, $mnLocationId,
        $locationManual, $locationManual,
        $loginnameEsc, $loginnameEsc,
        $scannerManual, $scannerManual,
        $loginnameEsc, $loginnameEsc,
        $scannerManual, $scannerManual,
        $loginnameEsc
    );
    $ok = $stmtInsert->execute();
    $stmtInsert->close();

    if ($ok) {
        echo json_encode(["status" => "Success", "message" => "Attendance marked successfully!"]);
    } else {
        echo json_encode(["status" => "Error", "message" => "Database error: " . $db->error]);
    }
    exit;
} else if ($action === "RemoveAttendance") {
    $attendanceid = isset($_POST['attendanceid']) ? intval($_POST['attendanceid']) : 0;
    $remark = isset($_POST['remark']) ? trim($_POST['remark']) : '';
    $loginnameEsc = $db->real_escape_string(isset($_SESSION['loginname']) ? $_SESSION['loginname'] : (isset($_POST['loginname']) ? $_POST['loginname'] : 'Admin'));

    if ($attendanceid <= 0) {
        echo json_encode(["status" => "Error", "message" => "Attendance ID is required."]);
        exit;
    }
    if (empty($remark)) {
        echo json_encode(["status" => "Error", "message" => "Remark is required to remove attendance."]);
        exit;
    }

    // Get the details before deleting to write a nice audit log entry
    $stmtFetch = $db->prepare("SELECT labourid, attendancedate FROM hrm_labourattendance WHERE attendanceid = ? LIMIT 1");
    $stmtFetch->bind_param("i", $attendanceid);
    $stmtFetch->execute();
    $attRow = $stmtFetch->get_result()->fetch_assoc();
    $stmtFetch->close();

    if (!$attRow) {
        echo json_encode(["status" => "Error", "message" => "Attendance record not found."]);
        exit;
    }

    // Perform actual update instead of deletion
    date_default_timezone_set('Asia/Kolkata');
    $localDT = date('Y-m-d H:i:s');

    $stmtDelete = $db->prepare(
        "UPDATE hrm_labourattendance SET
            checkintime_mn = NULL, checkouttime_mn = NULL, workinghours_mn = NULL,
            checkintime_dp = NULL, checkouttime_dp = NULL, workinghours_dp = NULL,
            status = 'Absent',
            tedit = ?,
            tedit_remark = ?,
            tedit_by = ?,
            updated_at = NOW(),
            updated_by = ?
         WHERE attendanceid = ?"
    );
    $stmtDelete->bind_param("ssssi", $localDT, $remark, $loginnameEsc, $loginnameEsc, $attendanceid);
    $ok = $stmtDelete->execute();
    $stmtDelete->close();

    if ($ok) {
        // Log the removal with user details and reason to log file
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        $logMessage = "[" . date('Y-m-d H:i:s') . "] User: " . $loginnameEsc . " soft-deleted attendance (ID: " . $attendanceid . ") for Labour: " . $attRow['labourid'] . " on Date: " . $attRow['attendancedate'] . ". Reason: " . $remark . "\n";
        file_put_contents($logDir . '/attendance_deletions.log', $logMessage, FILE_APPEND);

        echo json_encode(["status" => "Success", "message" => "Attendance removed successfully!"]);
    } else {
        echo json_encode(["status" => "Error", "message" => "Database error: " . $db->error]);
    }
    exit;
}

/* ======================================================
   FALLBACK
   ====================================================== */
echo json_encode(["status" => "Error", "message" => "Unknown action: " . htmlspecialchars($action)]);
exit;
?>
