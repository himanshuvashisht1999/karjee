<?php
session_start();
ini_set('display_errors', 0);
date_default_timezone_set('Asia/Kolkata');
include("connection.php");

header('Content-Type: application/json');

$ActionType = $_POST['ActionType'] ?? ''; // 'Approved' or 'Rejected'
$Ids = $_POST['Ids'] ?? []; // Array of WeeklyOffApplyID
$Remarks = $_POST['Remarks'] ?? '';
$ApprovedBy = $_SESSION['loginid'] ?? 'Admin';

if (empty($ActionType) || empty($Ids) || !is_array($Ids)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters. Action and IDs are required.']);
    exit;
}

if ($ActionType === 'Rejected' && empty(trim($Remarks))) {
    echo json_encode(['status' => 'error', 'message' => 'Admin remarks/reason is mandatory for rejection.']);
    exit;
}

// Sanitize IDs to make sure they are integers
$sanitizedIds = array_map('intval', $Ids);
$idsPlaceholder = implode(',', array_fill(0, count($sanitizedIds), '?'));

$sql = "UPDATE hrm_employeeweekoffapply 
        SET Status = ?, approved_at = NOW(), approved_by = ?, remarks = ?
        WHERE WeeklyOffApplyID IN ($idsPlaceholder)";

$stmt = $hrmconn->prepare($sql);
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Query preparation failed: ' . $hrmconn->error]);
    exit;
}

// Bind types
$types = 'sss' . str_repeat('i', count($sanitizedIds));
$bindParams = array_merge([$ActionType, $ApprovedBy, $Remarks], $sanitizedIds);

$stmt->bind_param($types, ...$bindParams);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Week-off application(s) ' . strtolower($ActionType) . ' successfully.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Update failed: ' . $stmt->error]);
}
$stmt->close();
?>
