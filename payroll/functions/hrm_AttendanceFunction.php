<?php
function insertEmployeeAttendance($conn, $data, $photoBlob = null)
{
    $EmployeeID = $data['EmployeeID'] ?? '';
    $Source = $data['Source'] ?? '';
    $Latitude = $data['Latitude'] ?? null;
    $Longitude = $data['Longitude'] ?? null;
    $locationid = $data['locationid'] ?? '';
    $Address = $data['Address'] ?? '';
    $created_by = $EmployeeID;
    $AttendanceDate = date('Y-m-d');
    $now = date('Y-m-d H:i:s');

    if ($Latitude === '' || $Latitude === null || $Longitude === '' || $Longitude === null) {
        return ['status' => 'error', 'message' => 'Location coordinates are required to mark attendance. Please enable GPS.'];
    }

    // ✅ Get EmployeeID and ProjectID
    $stmtEmp = $conn->prepare("SELECT EmployeeID, companyid, subcompanyid, projectid FROM hrm_EmployeesMaster WHERE EmployeeID = ?");
    if (!$stmtEmp) {
        return ['status' => 'error', 'message' => 'Prepare failed (stmtEmp): ' . $conn->error];
    }
    $stmtEmp->bind_param("s", $EmployeeID);
    $stmtEmp->execute();
    $resEmp = $stmtEmp->get_result();
    $rowEmp = $resEmp->fetch_assoc();
    $stmtEmp->close();

    if (!$rowEmp) {
        return ['status' => 'error', 'message' => 'Employee not found for EmployeeID: ' . $EmployeeID];
    }

    $EmployeeID = $rowEmp['EmployeeID'];
    $CompanyId = $rowEmp['companyid'];
    $SubCompanyId = $rowEmp['subcompanyid'];
    $ProjectID = $rowEmp['projectid'];

    // ✅ Handle photo upload
    $photoPath = null;
    $uphotoPath = null;
    if ($photoBlob && isset($photoBlob['tmp_name']) && is_uploaded_file($photoBlob['tmp_name'])) {
        $uploadDir = __DIR__ . "/../attachementfile/" . $CompanyId . "/" . $SubCompanyId . "/" . $ProjectID . "/attendance_photos/" . $EmployeeID . "/" . date('Ymd') . "/";

        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0777, true);
        $photoName = "emp_" . $EmployeeID . "_" . time() . ".jpg";
        $photoPath = $uploadDir . $photoName;
        move_uploaded_file($photoBlob['tmp_name'], $photoPath);

        $uphotoPath = "/../attachementfile/" . $CompanyId . "/" . $SubCompanyId . "/" . $ProjectID . "/attendance_photos/" . $EmployeeID . "/" . date('Ymd') . "/" . $photoName;
    }

    // ✅ Check if today's attendance exists
    $check = $conn->prepare("SELECT AttendanceID, CheckInTime, CheckOutTime FROM hrm_EmployeeAttendance WHERE EmployeeID = ? AND AttendanceDate = ? AND (tedit IS NULL OR tedit = '') order by AttendanceID desc limit 1");
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
        $status = 'MarkOut Missing';
        $stmt = $conn->prepare("
            INSERT INTO hrm_EmployeeAttendance 
            (ProjectID, EmployeeID, AttendanceDate, CheckInTime, SourceIn, CheckInLat, CheckInLng, CheckInAddress, created_by, CheckInPhoto,Status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Prepare failed (insert mark in): ' . $conn->error];
        }

        $stmt->bind_param(
            "sisssddssss",
            $ProjectID,
            $EmployeeID,
            $AttendanceDate,
            $now,
            $Source,
            $Latitude,
            $Longitude,
            $Address,
            $created_by,
            $uphotoPath,
            $status
        );
        if (!$stmt->execute()) {
            return ['status' => 'error', 'message' => 'Execute failed (insert mark in): ' . $stmt->error];
        }
        $stmt->close();
        return ['status' => 'success', 'message' => 'Marked In successfully.'];
    }

    // ✅ Case 2: Record exists but CheckOut is empty → Mark Out
    elseif (empty($existing['CheckOutTime'])) {
        $status = 'Present';
        $WorkingHours = '';
        $duration_minutes = 0;
        if (!empty($existing['CheckInTime']) && !empty($now)) {
            try {
                $in = new DateTime($existing['CheckInTime']);
                $out = new DateTime($now);
                $duration_minutes = ($out->getTimestamp() - $in->getTimestamp()) / 60;
                $interval = $in->diff($out);
                $WorkingHours = $interval->format('%H:%I');
            } catch (Exception $e) {
                $WorkingHours = '';
            }
        }

        if ($duration_minutes < 30) {
            $teditVal = "bymistake " . date('Y-m-d H:i:s');
            $stmt = $conn->prepare("
                UPDATE hrm_EmployeeAttendance 
                SET tedit = ?, Status = 'Mistake'
                WHERE EmployeeID = ? AND AttendanceDate = ? AND AttendanceID = ?
            ");
            if (!$stmt) {
                return ['status' => 'error', 'message' => 'Prepare failed (cancel check in): ' . $conn->error];
            }
            $stmt->bind_param("sisi", $teditVal, $EmployeeID, $AttendanceDate, $existing['AttendanceID']);
            if (!$stmt->execute()) {
                return ['status' => 'error', 'message' => 'Execute failed (cancel check in): ' . $stmt->error];
            }
            $stmt->close();
            return ['status' => 'success', 'message' => 'Punch cancelled as duration was less than 30 minutes.'];
        }

        // UPDATE hrmplus.hrm_EmployeeAttendance SET WorkingHours = TIMEDIFF(CheckOutTime, CheckInTime) WHERE CheckInTime IS NOT NULL AND CheckOutTime IS NOT NULL AND CheckOutTime > CheckInTime;

        $stmt = $conn->prepare("
            UPDATE hrm_EmployeeAttendance 
            SET CheckOutTime = ?, SourceOut = ?, CheckOutLat = ?, CheckOutLng = ?, CheckOutAddress = ?, CheckOutPhoto = ?, Status =?, WorkingHours = ?
            WHERE EmployeeID = ? AND AttendanceDate = ? AND AttendanceID = ?
        ");
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Prepare failed (update mark out): ' . $conn->error];
        }

        $stmt->bind_param(
            "ssddssssisi",
            $now,
            $Source,
            $Latitude,
            $Longitude,
            $Address,
            $uphotoPath,
            $status,
            $WorkingHours,
            $EmployeeID,
            $AttendanceDate,
            $existing['AttendanceID']
        );
        if (!$stmt->execute()) {
            return ['status' => 'error', 'message' => 'Execute failed (update mark out): ' . $stmt->error];
        }
        $stmt->close();
        return ['status' => 'success', 'message' => 'Marked Out successfully.'];
    } else {
    // ✅ Case 3: Both In & Out exist → Allow multiple check-ins (new row)
        $status = 'MarkOut Missing';
        $stmt = $conn->prepare("
            INSERT INTO hrm_EmployeeAttendance 
            (ProjectID, EmployeeID, AttendanceDate, CheckInTime, SourceIn, CheckInLat, CheckInLng, CheckInAddress, created_by, CheckInPhoto, Status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Prepare failed (insert new session): ' . $conn->error];
        }

        $stmt->bind_param(
            "sisssddssss",
            $ProjectID,
            $EmployeeID,
            $AttendanceDate,
            $now,
            $Source,
            $Latitude,
            $Longitude,
            $Address,
            $created_by,
            $uphotoPath,
            $status
        );
        if (!$stmt->execute()) {
            return ['status' => 'error', 'message' => 'Execute failed (insert new session): ' . $stmt->error];
        }
        $stmt->close();
        return ['status' => 'success', 'message' => 'New session Marked In successfully.'];
    }
}
?>