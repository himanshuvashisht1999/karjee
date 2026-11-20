<?php
session_start();
ini_set('display_errors', 0);
include("connection.php");

header('Content-Type: application/json');

$ProjectID = $_SESSION['projectid'] ?? $_POST['ProjectID'] ?? '';
$Status = $_POST['Status'] ?? 'Pending';
$EmployeeID = $_POST['EmployeeID'] ?? 'all';

if (empty($ProjectID)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing project scope.']);
    exit;
}

$sql = "SELECT 
            a.WeeklyOffApplyID,
            a.EmployeeID,
            UPPER(TRIM(CONCAT(
                COALESCE(emp.FirstName,''), ' ', 
                COALESCE(emp.MiddleName,''), ' ', 
                COALESCE(emp.LastName,'')
            ))) AS EmployeeName,
            a.WeekOffDate,
            DATE_FORMAT(a.WeekOffDate, '%d-%b-%Y (%W)') AS WeekOffDateFormatted,
            a.SubordinateID,
            UPPER(TRIM(CONCAT(
                COALESCE(sub.FirstName,''), ' ', 
                COALESCE(sub.MiddleName,''), ' ', 
                COALESCE(sub.LastName,'')
            ))) AS SubordinateName,
            a.Reason,
            a.Status,
            DATE_FORMAT(a.created_at, '%d-%b-%Y') AS AppliedDateFormatted,
            a.approved_by,
            DATE_FORMAT(a.approved_at, '%d-%b-%Y') AS ApprovedDateFormatted,
            a.remarks
        FROM hrm_employeeweekoffapply a
        LEFT JOIN hrm_EmployeesMaster emp ON a.EmployeeID = emp.EmployeeID
        LEFT JOIN hrm_EmployeesMaster sub ON a.SubordinateID = sub.EmployeeID
        WHERE a.ProjectID = ?";

$params = [$ProjectID];
$types = "s";

if ($Status === 'Pending') {
    $sql .= " AND a.Status = 'Pending'";
} else {
    $sql .= " AND a.Status != 'Pending'";
}

if ($EmployeeID !== 'all') {
    $sql .= " AND a.EmployeeID = ?";
    $params[] = $EmployeeID;
    $types .= "i";
}

$sql .= " ORDER BY a.WeekOffDate DESC";

$stmt = $hrmconn->prepare($sql);
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Query preparation failed: ' . $hrmconn->error]);
    exit;
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$data = [];
while ($row = $res->fetch_assoc()) {
    $data[] = $row;
}

$stmt->close();
echo json_encode(['status' => 'success', 'data' => $data]);
?>
