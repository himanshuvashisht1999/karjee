<?php
session_start();
ini_set('display_errors', 0);
date_default_timezone_set('Asia/Kolkata');
include("../connection.php");

header('Content-Type: application/json');

$Action = $_POST['Action'] ?? 'Apply';

if ($Action === 'Apply') {
    $EmployeeID = $_POST['EmployeeID'] ?? '';
    $ProjectID = $_POST['ProjectID'] ?? '';
    $WeekOffDate = $_POST['WeekOffDate'] ?? '';
    $SubordinateID = $_POST['SubordinateID'] ?? '';
    $Reason = $_POST['Reason'] ?? '';

    if (empty($EmployeeID) || empty($ProjectID) || empty($WeekOffDate) || empty($SubordinateID)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }

    // Check if already applied for this date (Only block if status is Pending or Approved)
    $checkSql = "SELECT WeeklyOffApplyID FROM hrm_employeeweekoffapply WHERE EmployeeID = ? AND WeekOffDate = ? AND Status IN ('Pending', 'Approved')";
    $stmt = $hrmconn->prepare($checkSql);
    $stmt->bind_param("is", $EmployeeID, $WeekOffDate);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'You already have a Pending or Approved week-off application on this date.']);
        $stmt->close();
        exit;
    }
    $stmt->close();

    // Insert into database
    $insertSql = "INSERT INTO hrm_employeeweekoffapply (EmployeeID, ProjectID, WeekOffDate, SubordinateID, Reason, Status) VALUES (?, ?, ?, ?, ?, 'Pending')";
    $stmt = $hrmconn->prepare($insertSql);
    $stmt->bind_param("issis", $EmployeeID, $ProjectID, $WeekOffDate, $SubordinateID, $Reason);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Week-off application submitted successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save application: ' . $hrmconn->error]);
    }
    $stmt->close();
} elseif ($Action === 'Cancel') {
    $WeeklyOffApplyID = $_POST['WeeklyOffApplyID'] ?? '';
    if (empty($WeeklyOffApplyID)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing application ID.']);
        exit;
    }

    $cancelSql = "UPDATE hrm_employeeweekoffapply SET Status = 'Cancelled' WHERE WeeklyOffApplyID = ?";
    $stmt = $hrmconn->prepare($cancelSql);
    $stmt->bind_param("i", $WeeklyOffApplyID);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Week-off application cancelled.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to cancel: ' . $hrmconn->error]);
    }
    $stmt->close();
}
?>
