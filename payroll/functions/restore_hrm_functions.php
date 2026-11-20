function insertLeaveDetails($conn, $companyid, $leave_code, $leave_name, $leave_type, $max_days, $carry_forward,
$description) {
// Check if leave_code already exists for the company
$checkSql = "SELECT id FROM hrm_PayrollLeaveTypeMaster WHERE companyid = ? AND leave_code = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("is", $companyid, $leave_code);
$checkStmt->execute();
$checkStmt->store_result();

if ($checkStmt->num_rows > 0) {
$checkStmt->close();
return ['status' => 'Error', 'message' => 'Leave Code already exists.'];
}
$checkStmt->close();

$sql = "INSERT INTO hrm_PayrollLeaveTypeMaster (companyid, leave_code, leave_name, leave_type, max_days, carry_forward,
description, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
$stmt = $conn->prepare($sql);

if ($stmt) {
$stmt->bind_param("isssiss", $companyid, $leave_code, $leave_name, $leave_type, $max_days, $carry_forward,
$description);
if ($stmt->execute()) {
$stmt->close();
return ['status' => 'Success', 'message' => 'Leave Type added successfully.'];
} else {
$error = $stmt->error;
$stmt->close();
return ['status' => 'Error', 'message' => 'Failed to add Leave Type: ' . $error];
}
} else {
return ['status' => 'Error', 'message' => 'Database error: ' . $conn->error];
}
}

function updateLeaveDetails($conn, $leaveid, $leave_name, $leave_type, $max_days, $carry_forward, $description) {
$sql = "UPDATE hrm_PayrollLeaveTypeMaster SET leave_name = ?, leave_type = ?, max_days = ?, carry_forward = ?,
description = ?, updated_at = NOW() WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
$stmt->bind_param("ssissi", $leave_name, $leave_type, $max_days, $carry_forward, $description, $leaveid);
if ($stmt->execute()) {
$stmt->close();
return ['status' => 'Success', 'message' => 'Leave Type updated successfully.'];
} else {
$error = $stmt->error;
$stmt->close();
return ['status' => 'Error', 'message' => 'Failed to update Leave Type: ' . $error];
}
} else {
return ['status' => 'Error', 'message' => 'Database error: ' . $conn->error];
}
}

function deleteLeaveType($conn, $leaveid) {
// Ideally we should check if used elsewhere, but for now simple delete
$sql = "DELETE FROM hrm_PayrollLeaveTypeMaster WHERE id = ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
$stmt->bind_param("i", $leaveid);
if ($stmt->execute()) {
$stmt->close();
return ['status' => 'Success', 'message' => 'Leave Type deleted successfully.'];
} else {
$error = $stmt->error;
$stmt->close();
return ['status' => 'Error', 'message' => 'Failed to delete Leave Type: ' . $error];
}
} else {
return ['status' => 'Error', 'message' => 'Database error: ' . $conn->error];
}
}