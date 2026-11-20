<?php
session_start();
ini_set('display_errors', 0);
include("../connection.php");

header('Content-Type: application/json');

$EmployeeID = $_POST['EmployeeID'] ?? '';
$ProjectID = $_POST['ProjectID'] ?? '';

if (empty($EmployeeID) || empty($ProjectID)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameter EmployeeID or ProjectID.']);
    exit;
}

$sql = "SELECT 
            a.WeeklyOffApplyID, 
            a.WeekOffDate,
            DATE_FORMAT(a.WeekOffDate, '%d-%b-%Y (%W)') AS WeekOffDateFormatted,
            a.SubordinateID,
            UPPER(TRIM(CONCAT(
                COALESCE(e.FirstName,''), ' ', 
                COALESCE(e.MiddleName,''), ' ', 
                COALESCE(e.LastName,'')
            ))) AS SubordinateName,
            a.Reason,
            a.Status,
            DATE_FORMAT(a.created_at, '%d-%b-%Y') AS AppliedDateFormatted
        FROM hrm_employeeweekoffapply a
        LEFT JOIN hrm_EmployeesMaster e ON a.SubordinateID = e.EmployeeID
        WHERE a.EmployeeID = ? AND a.ProjectID = ?
        ORDER BY a.WeekOffDate DESC";

$stmt = $hrmconn->prepare($sql);
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Query preparation failed: ' . $hrmconn->error]);
    exit;
}

$stmt->bind_param("is", $EmployeeID, $ProjectID);
$stmt->execute();
$res = $stmt->get_result();

$data = [];
while ($row = $res->fetch_assoc()) {
    $data[] = $row;
}

$stmt->close();
echo json_encode(['status' => 'success', 'data' => $data]);
?>
