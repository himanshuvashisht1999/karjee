<?php
ini_set('display_errors', 1);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
date_default_timezone_set('Asia/Kolkata');
include("../connection.php");
$LoginID     = $_POST['LoginID'];
$Source      = $_POST['Source'];
$Latitude    = $_POST['Latitude'];
$Longitude   = $_POST['Longitude'];
$ActionType  = "";
$ProjectID  = $_POST['ProjectID'];

// ✅ Prepare Data Array
$Address = "";
$data = [
    'LoginID'     => $LoginID,
    'ActionType'  => $ActionType,
    'Source'      => $Source,
    'Latitude'    => $Latitude,
    'Longitude'   => $Longitude,
    'Address'     => $Address,
    'ProjectID'  => $ProjectID
];

// ✅ Call function
$response = insertEmployeeAttendance($hrmconn, $data, $_FILES['Photo']);
echo json_encode($response);

function insertEmployeeAttendance($conn, $data, $photoBlob = null) {
    $LoginID    = $data['LoginID'] ?? '';
    $Source     = $data['Source'] ?? '';
    $Latitude   = $data['Latitude'] ?? null;
    $Longitude  = $data['Longitude'] ?? null;
    $Address    = $data['Address'] ?? '';
    $created_by = $LoginID;
    $AttendanceDate = date('Y-m-d');
    $now = date('Y-m-d H:i:s');

    // ✅ Get EmployeeID and ProjectID
    $stmtEmp = $conn->prepare("SELECT EmployeeID, companyid, subcompanyid, projectid FROM hrm_EmployeesMaster WHERE LoginID = ?");
    if (!$stmtEmp) {
        return ['status' => 'error', 'message' => 'Prepare failed (stmtEmp): ' . $conn->error];
    }
    $stmtEmp->bind_param("s", $LoginID);
    $stmtEmp->execute();
    $resEmp = $stmtEmp->get_result();
    $rowEmp = $resEmp->fetch_assoc();
    $stmtEmp->close();

    if (!$rowEmp) {
        return ['status' => 'error', 'message' => 'Employee not found for LoginID: ' . $LoginID];
    }

    $EmployeeID = $rowEmp['EmployeeID'];
    $CompanyId  = $rowEmp['companyid'];
    $SubCompanyId  = $rowEmp['subcompanyid'];
    $ProjectID  = $rowEmp['projectid'];

    // ✅ Handle photo upload
    $photoPath = null;
    $uphotoPath = null;
    if ($photoBlob && isset($photoBlob['tmp_name']) && is_uploaded_file($photoBlob['tmp_name'])) {
        $uploadDir = __DIR__ . "/../attachementfile/" . $CompanyId . "/" . $SubCompanyId . "/" . $ProjectID . "/attendance_photos/" . date('Ymd') . "/";
        
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $photoName = "emp_" . $EmployeeID . "_" . time() . ".jpg";
        $photoPath = $uploadDir . $photoName;
        move_uploaded_file($photoBlob['tmp_name'], $photoPath);

        $uphotoPath = "attachementfile/" . $CompanyId . "/" . $SubCompanyId . "/" . $ProjectID . "/attendance_photos/" . date('Ymd') . "/".$photoName;
    }

    // ✅ Check if today's attendance exists
    $check = $conn->prepare("SELECT AttendanceID, CheckInTime, CheckOutTime FROM hrm_EmployeeAttendance WHERE EmployeeID = ? AND AttendanceDate = ? order by AttendanceID desc limit 1");
    if (!$check) {
        return ['status' => 'error', 'message' => 'Prepare failed (check): ' . $conn->error];
    }
    $check->bind_param("is", $EmployeeID, $AttendanceDate);
    $check->execute();
    $res = $check->get_result();
    $existing = $res->fetch_assoc();
    $check->close();
    // ✅ Case 1: No record → Insert new (Mark In)
    if (!$existing) {
        $stmt = $conn->prepare("
            INSERT INTO hrm_EmployeeAttendance 
            (ProjectID, EmployeeID, AttendanceDate, CheckInTime, SourceIn, CheckInLat, CheckInLng, CheckInAddress, created_by, CheckInPhoto)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Prepare failed (insert mark in): ' . $conn->error];
        }

        $stmt->bind_param("sisssddsss",
            $ProjectID, $EmployeeID, $AttendanceDate, $now, $Source, $Latitude, $Longitude, $Address, $created_by, $uphotoPath
        );
        $stmt->execute();
        $stmt->close();
        return ['status' => 'success', 'message' => 'Marked In successfully.'];
    }

    // ✅ Case 2: Record exists but CheckOut is empty → Mark Out
    elseif (empty($existing['CheckOutTime'])) {
        $stmt = $conn->prepare("
            UPDATE hrm_EmployeeAttendance 
            SET CheckOutTime = ?, SourceOut = ?, CheckOutLat = ?, CheckOutLng = ?, CheckOutAddress = ?, CheckOutPhoto = ?
            WHERE EmployeeID = ? AND AttendanceDate = ? AND AttendanceID = ?
        ");
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Prepare failed (update mark out): ' . $conn->error];
        }

        $stmt->bind_param("ssddssisi",
            $now, $Source, $Latitude, $Longitude, $Address, $uphotoPath, $EmployeeID, $AttendanceDate,$existing['AttendanceID']
        );
        $stmt->execute();
        $stmt->close();
        return ['status' => 'success', 'message' => 'Marked Out successfully.'];
    }

    // ✅ Case 3: Both In & Out exist → Allow multiple check-ins (new row)
    else {
        $stmt = $conn->prepare("
            INSERT INTO hrm_EmployeeAttendance 
            (ProjectID, EmployeeID, AttendanceDate, CheckInTime, SourceIn, CheckInLat, CheckInLng, CheckInAddress, created_by, CheckInPhoto)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Prepare failed (insert new session): ' . $conn->error];
        }

        $stmt->bind_param("sisssddsss",
            $ProjectID, $EmployeeID, $AttendanceDate, $now, $Source, $Latitude, $Longitude, $Address, $created_by, $uphotoPath
        );
        $stmt->execute();
        $stmt->close();
        return ['status' => 'success', 'message' => 'New session Marked In successfully.'];
    }
}
?>