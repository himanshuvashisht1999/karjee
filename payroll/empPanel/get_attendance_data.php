<?php
error_reporting(0);
header('Content-Type: application/json');


date_default_timezone_set('Asia/Kolkata');
include("../connection.php");
$EmployeeID = $_POST['EmployeeID'] ?? '';

if (empty($EmployeeID)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing EmployeeID']);
    exit;
}

$month = date('m');
$year = date('Y');

// ✅ Get EmployeeID and ProjectID
$stmtEmp = $hrmconn->prepare("SELECT EmployeeID, projectid FROM hrm_EmployeesMaster WHERE EmployeeID = ?");
if (!$stmtEmp) {
    echo json_encode(['status' => 'error', 'message' => 'Prepare failed (stmtEmp): ' . $hrmconn->error]);
    exit;
}

$stmtEmp->bind_param("s", $EmployeeID);
$stmtEmp->execute();
$resEmp = $stmtEmp->get_result();
$rowEmp = $resEmp->fetch_assoc();
$stmtEmp->close();

if (!$rowEmp) {
    echo json_encode(['status' => 'error', 'message' => 'Employee not found for EmployeeID: ' . $EmployeeID]);
    exit;
}

$EmployeeID = $rowEmp['EmployeeID'];
$ProjectID = $rowEmp['projectid'];

// ✅ Fetch attendance data
$sql = "SELECT 
          DATE_FORMAT(AttendanceDate, '%d-%m-%Y') AS date,
          CheckInTime AS `in`,
          CheckOutTime AS `out`,
          TIME_FORMAT(
                SEC_TO_TIME(TIME_TO_SEC(IFNULL(WorkingHours,'00:00:00'))),
                '%H:%i:%s'
            ) AS wrkh
        FROM hrm_EmployeeAttendance
        WHERE EmployeeID = ? 
          AND ProjectID = ?
          AND MONTH(AttendanceDate) = ?
          AND YEAR(AttendanceDate) = ?
          AND (tedit IS NULL OR tedit = '')";

$stmt = $hrmconn->prepare($sql);
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Prepare failed (attendance): ' . $hrmconn->error]);
    exit;
}

$stmt->bind_param("ssii", $EmployeeID, $ProjectID, $month, $year);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
$stmt->close();

// ✅ Return JSON
echo json_encode(['status' => 'success', 'data' => $data]);
?>