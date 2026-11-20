<?php
ini_set('display_errors', 1);
header('Content-Type: application/json'); // tell browser to expect JSON
error_reporting(0); // hide warnings/notices that break JSON

date_default_timezone_set('Asia/Kolkata');
include("../connection.php");
$loginID = $_POST['LoginID'] ?? '';

if (empty($loginID)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing LoginID']);
    exit;
}

$month = date('m');
$year = date('Y');

// ✅ Get EmployeeID and ProjectID
$stmtEmp = $hrmconn->prepare("SELECT EmployeeID, projectid FROM hrm_EmployeesMaster WHERE LoginID = ?");
if (!$stmtEmp) {
    echo json_encode(['status' => 'error', 'message' => 'Prepare failed (stmtEmp): ' . $hrmconn->error]);
    exit;
}

$stmtEmp->bind_param("s", $loginID);
$stmtEmp->execute();
$resEmp = $stmtEmp->get_result();
$rowEmp = $resEmp->fetch_assoc();
$stmtEmp->close();

if (!$rowEmp) {
    echo json_encode(['status' => 'error', 'message' => 'Employee not found for LoginID: ' . $loginID]);
    exit;
}

$EmployeeID = $rowEmp['EmployeeID'];
$ProjectID  = $rowEmp['projectid'];

// ✅ Fetch attendance data
$sql = "SELECT 
          DATE_FORMAT(AttendanceDate, '%d-%m-%Y') AS date,
          CheckInTime AS `in`,
          CheckOutTime AS `out`
        FROM hrm_EmployeeAttendance
        WHERE EmployeeID = ? 
          AND ProjectID = ?
          AND MONTH(AttendanceDate) = ?
          AND YEAR(AttendanceDate) = ?";

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