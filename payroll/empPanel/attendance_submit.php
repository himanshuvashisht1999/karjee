<?php
ini_set('display_errors', 1);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
date_default_timezone_set('Asia/Kolkata');
include("../connection.php");
include("../functions/hrm_AttendanceFunction.php");
$EmployeeID = $_POST['EmployeeID'];
$Source = $_POST['Source'];
$Latitude = $_POST['Latitude'];
$Longitude = $_POST['Longitude'];
$ActionType = $_POST['ActionType'] ?? '';
$ProjectID = $_POST['ProjectID'];
$locationid = $_POST['locationid'] ?? '';

$exStmt = $hrmconn->prepare("
    SELECT ProjectID, LocationId, LocationName  FROM hrm_AttendanceLocationMaster WHERE ProjectID = ? AND LocationId = ? LIMIT 1");
$exStmt->bind_param("ss", $ProjectID, $locationid); // corrected
$exStmt->execute();
$exRes = $exStmt->get_result();

$locationname = '';
if ($exRes && $exRes->num_rows > 0) {
    $mrow = $exRes->fetch_assoc();
    $locationname = !empty($mrow['LocationName']) ? strtoupper($mrow['LocationName']) : '';
}

$exStmt->close();


// ✅ Prepare Data Array
$data = [
    'EmployeeID' => $EmployeeID,
    'ActionType' => $ActionType,
    'Source' => $Source,
    'Latitude' => $Latitude,
    'Longitude' => $Longitude,
    'locationid' => $locationid,
    'Address' => $locationname,
    'ProjectID' => $ProjectID
];
// ✅ Call function
$response = insertEmployeeAttendance($hrmconn, $data, $_FILES['Photo']);
echo json_encode($response);
?>