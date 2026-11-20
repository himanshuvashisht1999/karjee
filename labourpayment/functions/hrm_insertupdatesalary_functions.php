<?php
function insertOrUpdateLabourCalculatedAttendance($hrmconn, $loginname, $companyid, $subcompanyid, $projectid, $employeeid, $DateFrom, $DateTo, $LocationId, $calculatepresentdays, $attFileid='') {
    $ttlHolidays       = 0;
    $empWeekOffcount   = 0;
    $absentdays        = 0;
    $TotalApproveLeave = 0; 
    $HalfDays          = 0;
    $LateArrivals      = 0;
    $extraDays         = 0;

    $yrmntex  = explode("-", $DateFrom);
    $attYear  = (isset($yrmntex[0]) && is_numeric($yrmntex[0])) ? intval($yrmntex[0]) : intval(date('Y'));
    $attMonth = (isset($yrmntex[1]) && is_numeric($yrmntex[1])) ? intval($yrmntex[1]) : intval(date('m'));
    $attFileid = (!empty($attFileid) && is_numeric($attFileid)) ? intval($attFileid) : 0;

    $Comments = 'Attendance date from '.$DateFrom.' To '.$DateTo.' upload excel data on date ' . date('Y-m-d') . ' by ' . $loginname;
    
    $returnmsg = array();

    // Check if record already exists
    $checkStmt = $hrmconn->prepare("SELECT AttendanceID 
        FROM hrm_labourcalculateattendance 
        WHERE EmployeeID = ? AND CompanyID = ? AND SubCompanyID = ? 
          AND ProjectID = ? AND DateFrom = ? AND DateTo = ? AND LocationId = ?");

    if (!$checkStmt) {
        $msg = array("status" => "Error", "message" => "❌ Check prepare error: " . $hrmconn->error, "employeeid" => $employeeid);
        $msg[0] = $msg;
        return $msg;
    }

    $checkStmt->bind_param("sssssss", $employeeid, $companyid, $subcompanyid, $projectid, $DateFrom, $DateTo, $LocationId);
    if (!$checkStmt->execute()) {
        $err = $checkStmt->error;
        $checkStmt->close();
        $msg = array("status" => "Error", "message" => "❌ Check query error: " . $err, "employeeid" => $employeeid);
        $msg[0] = $msg;
        return $msg;
    }
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        // Update existing record
        $updateStmt = $hrmconn->prepare("UPDATE hrm_labourcalculateattendance 
            SET DateFrom = ?, DateTo = ?, WeekOffDays = ?, HoliDays = ?, PresentDays = ?, 
                oldPresentDays = ?, AbsentDays = ?, TotalApproveLeave = ?, HalfDays = ?, LateArrivals = ?, 
                ExtraWorkingDays = ?, Comments = ?, attFileid = ?
            WHERE EmployeeID = ? AND CompanyID = ? AND SubCompanyID = ? 
              AND ProjectID = ? AND DateFrom = ? AND DateTo = ? AND LocationId = ?");

        if (!$updateStmt) {
            $err = $hrmconn->error;
            $checkStmt->close();
            $msg = array("status" => "Error", "message" => "❌ Update prepare error: " . $err, "employeeid" => $employeeid);
            $msg[0] = $msg;
            return $msg;
        }

        $updateStmt->bind_param("ssssssssssssssssssss",
            $DateFrom, $DateTo, $empWeekOffcount, $ttlHolidays,
            $calculatepresentdays, $calculatepresentdays, $absentdays, $TotalApproveLeave, $HalfDays,
            $LateArrivals, $extraDays, $Comments, $attFileid,
            $employeeid, $companyid, $subcompanyid, $projectid,
            $DateFrom, $DateTo, $LocationId
        );

        if ($updateStmt->execute()) {
            $returnmsg = array("status" => "Success", "message" => "✅ Attendance updated successfully!", "employeeid" => $employeeid);
        } else {
            $returnmsg = array("status" => "Error", "message" => "❌ Error updating attendance: " . $updateStmt->error, "employeeid" => $employeeid);
        }

        $updateStmt->close();
    } else {
        // Insert new record
        $insertStmt = $hrmconn->prepare("INSERT INTO hrm_labourcalculateattendance 
            (EmployeeID, CompanyID, SubCompanyID, ProjectID, DateFrom, DateTo, 
             WeekOffDays, HoliDays, PresentDays, oldPresentDays, AbsentDays, TotalApproveLeave, 
             HalfDays, LateArrivals, ExtraWorkingDays, Comments, LocationId, AttendanceYear, AttendanceMonth, attFileid) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        if (!$insertStmt) {
            $err = $hrmconn->error;
            $checkStmt->close();
            $msg = array("status" => "Error", "message" => "❌ Insert prepare error: " . $err, "employeeid" => $employeeid);
            $msg[0] = $msg;
            return $msg;
        }

        $insertStmt->bind_param("sssssssssssssssssiii",
            $employeeid, $companyid, $subcompanyid, $projectid,
            $DateFrom, $DateTo, $empWeekOffcount, $ttlHolidays,
            $calculatepresentdays, $calculatepresentdays, $absentdays, $TotalApproveLeave, $HalfDays,
            $LateArrivals, $extraDays, $Comments, $LocationId, $attYear, $attMonth, $attFileid
        );

        if ($insertStmt->execute()) {
            $returnmsg = array("status" => "Success", "message" => "✅ Attendance inserted successfully!", "employeeid" => $employeeid);
        } else {
            $returnmsg = array("status" => "Error", "message" => "❌ Error inserting attendance: " . $insertStmt->error, "employeeid" => $employeeid);
        }
        $insertStmt->close();
    }

    $checkStmt->close();
    $returnmsg[0] = $returnmsg;
    return $returnmsg;
}

function saveOrUpdateEmployee($hrmconn, $employeeData) {
    // Check if the employee already exists (based on Email or Phone)
    $checkSql = "SELECT EmployeeID FROM hrm_EmployeesMaster WHERE (EmployeeID = ?) OR (DateOfBirth =? AND MobileNo = ? AND FirstName = ? AND projectid = ?)";
    $stmt = $hrmconn->prepare($checkSql);
    if (!$stmt) {
        return [
            "success" => false,
            "message" => "Error preparing statement: " . $hrmconn->error
        ];
    }
    $stmt->bind_param("sdsss", $employeeData['EmployeeID'], $employeeData['DateOfBirth'], $employeeData['MobileNo'], $employeeData['FirstName'], $employeeData['projectid']);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->fetch_assoc();
    $stmt->close();
    if ($exists) {
        // Update existing employee
        $updateSql = "UPDATE hrm_EmployeesMaster SET TitleName = ?, FirstName = ?, MiddleName = ?, LastName = ?, FatherName = ?, MotherName = ?, MaritalStatus = ?, SpouseName = ?, Gender = ?, DateOfBirth = ?, Email = ?, MobileNo = ?, Nationality = ?, PassportNumber = ?, AddressLine1 = ?, AddressLine2 = ?, AddressLine3 = ?, AddressLine4 = ?, PostalCode = ?, CountryName = ?, PanNumber = ?, AadharNumber = ?, DateOfJoining = ?, addedby = ?, updated_at = CURRENT_TIMESTAMP
            WHERE EmployeeID = ? AND companyid = ? AND subcompanyid = ? AND projectid = ?";
        $stmt = $hrmconn->prepare($updateSql);
        if (!$stmt) {
            return [
                "success" => false,
                "message" => "Error preparing update statement: " . $hrmconn->error
            ];
        }
        $TitleName = isset($employeeData['TitleName']) ? addslashes($employeeData['TitleName']) : null;
        $FirstName = isset($employeeData['FirstName']) ? addslashes($employeeData['FirstName']) : null;
        $MiddleName = isset($employeeData['MiddleName']) ? addslashes($employeeData['MiddleName']) : null;
        $LastName = isset($employeeData['LastName']) ? addslashes($employeeData['LastName']) : null;
        $FatherName = isset($employeeData['FatherName']) ? addslashes($employeeData['FatherName']) : null;
        $MotherName = isset($employeeData['MotherName']) ? addslashes($employeeData['MotherName']) : null;
        $MaritalStatus = isset($employeeData['MaritalStatus']) ? addslashes($employeeData['MaritalStatus']) : null;
        $SpouseName = isset($employeeData['SpouseName']) ? addslashes($employeeData['SpouseName']) : null;
        $Gender = isset($employeeData['Gender']) ? addslashes($employeeData['Gender']) : null;
        $DateOfBirth = isset($employeeData['DateOfBirth']) ? addslashes($employeeData['DateOfBirth']) : null;
        $Email = isset($employeeData['Email']) ? addslashes($employeeData['Email']) : null;
        $MobileNo = isset($employeeData['MobileNo']) ? addslashes($employeeData['MobileNo']) : null;
        $Nationality = isset($employeeData['Nationality']) ? addslashes($employeeData['Nationality']) : null;
        $PassportNumber = isset($employeeData['PassportNumber']) ? addslashes($employeeData['PassportNumber']) : null;
        $AddressLine1 = isset($employeeData['AddressLine1']) ? addslashes($employeeData['AddressLine1']) : null;
        $AddressLine2 = isset($employeeData['AddressLine2']) ? addslashes($employeeData['AddressLine2']) : null;
        $AddressLine3 = isset($employeeData['AddressLine3']) ? addslashes($employeeData['AddressLine3']) : null;
        $AddressLine4 = isset($employeeData['AddressLine4']) ? addslashes($employeeData['AddressLine4']) : null;
        $PostalCode = isset($employeeData['PostalCode']) ? addslashes($employeeData['PostalCode']) : null;
        $CountryName = isset($employeeData['CountryName']) ? addslashes($employeeData['CountryName']) : null;
        $PanNumber = isset($employeeData['PanNumber']) ? addslashes($employeeData['PanNumber']) : null;
        $AadharNumber = isset($employeeData['AadharNumber']) ? addslashes($employeeData['AadharNumber']) : null;
        $DateOfJoining = isset($employeeData['DateOfJoining']) ? addslashes($employeeData['DateOfJoining']) : null;

        $addedby = isset($employeeData['addedby']) ? addslashes($employeeData['addedby']) : null;
        $EmployeeID = isset($exists['EmployeeID']) ? addslashes($exists['EmployeeID']) : null;
        $companyid = isset($employeeData['companyid']) ? $employeeData['companyid'] : null;
        $subcompanyid = isset($employeeData['subcompanyid']) ? $employeeData['subcompanyid'] : null;
        $projected = isset($employeeData['projected']) ? $employeeData['projected'] : null;
        if($FirstName=="" || $FirstName==null || $FirstName=="undefined"){
            return [
                "success" => false,
                "message" => "Error Atlease name is requred of employee: " . $stmt->error
            ];
            die();
        }
        $stmt->bind_param(
            "ssssssssssssssssssssssssssss",
            $TitleName,
            $FirstName,
            $MiddleName,
            $LastName,
            $FatherName,
            $MotherName,
            $MaritalStatus,
            $SpouseName,
            $Gender,
            $DateOfBirth,
            $Email,
            $MobileNo,
            $Nationality,
            $PassportNumber,
            $graftingskill,
            $AddressLine1,
            $AddressLine2,
            $AddressLine3,
            $AddressLine4,
            $PostalCode,
            $CountryName,
            $PanNumber,
            $AadharNumber,
            $DateOfJoining,
            $addedby,
            $locationid,
            $EmployeeID,
            $companyid,
            $subcompanyid,
            $projectid);

        if ($stmt->execute()) {
            $stmt->close();
            return [
                "success" => true,
                "message" => "Employee details updated successfully.",
                "employeeID" => $exists['EmployeeID']
            ];
        } else {
            return [
                "success" => false,
                "message" => "Error updating data: " . $stmt->error
            ];
        }
    } else {

        if (isset($employeeData['EmployeeID']) && $employeeData['EmployeeID'] !== '') {
            $EmployeeID = (string)$employeeData['EmployeeID'];
        } else {
            // No EmployeeID given — get next EmployeeID
            $EmployeeID = generateUniqueEmployeeID($hrmconn);
        }
        // echo "==: ".$EmployeeID;

        // Insert new employee
       $insertSql = "INSERT INTO hrm_EmployeesMaster (companyid, subcompanyid, projectid, EmployeeID, TitleName, FirstName, MiddleName, LastName, FatherName, MotherName, MaritalStatus, SpouseName, Gender, DateOfBirth, Email, MobileNo, Nationality, PassportNumber, AddressLine1, AddressLine2, AddressLine3, AddressLine4, PostalCode, CountryName, PanNumber, AadharNumber, DateOfJoining, addedby) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $hrmconn->prepare($insertSql);
        if (!$stmt) {
            return [
                "success" => false,
                "message" => "Error preparing insert statement: " . $hrmconn->error
            ];
        }

        $stmt->bind_param("ssssssssssssssssssssssssssss", $employeeData['companyid'], $employeeData['subcompanyid'], $employeeData['projectid'], $EmployeeID, addslashes($employeeData['TitleName']), addslashes($employeeData['FirstName']), addslashes($employeeData['MiddleName']), addslashes($employeeData['LastName']), addslashes($employeeData['FatherName']), addslashes($employeeData['MotherName']), addslashes($employeeData['MaritalStatus']), addslashes($employeeData['SpouseName']), addslashes($employeeData['Gender']), addslashes($employeeData['DateOfBirth']), addslashes($employeeData['Email']), addslashes($employeeData['MobileNo']), addslashes($employeeData['Nationality']), addslashes($employeeData['PassportNumber']), addslashes($employeeData['AddressLine1']), addslashes($employeeData['AddressLine2']), addslashes($employeeData['AddressLine3']), addslashes($employeeData['AddressLine4']), addslashes($employeeData['PostalCode']), addslashes($employeeData['CountryName']), addslashes($employeeData['PanNumber']), addslashes($employeeData['AadharNumber']), addslashes($employeeData['DateOfJoining']), addslashes($employeeData['addedby']));

        if ($stmt->execute()) {
            $stmt->close();
            return [
                "success" => true,
                "message" => "Employee registered successfully.",
                "employeeID" => $EmployeeID
            ];
        } else {
            return [
                "success" => false,
                "message" => "Error inserting data: " . $stmt->error
            ];
        }
    }
}

function generateUniqueEmployeeID($hrmconn) {
    $uniqueID = null;
    $isUnique = false;

    while (!$isUnique) {
        // Generate random 4-digit number
        $uniqueID = rand(1000, 9999);

        // Check if this ID already exists in hrm_EmployeesMaster table
        $stmt = $hrmconn->prepare("SELECT COUNT(*) AS cnt FROM hrm_EmployeesMaster WHERE EmployeeID = ?");
        $stmt->bind_param("s", $uniqueID);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result['cnt'] == 0) {
            $isUnique = true; // Unique ID found
        }

        $stmt->close();
    }

    return $uniqueID;
}

function generateUniqueLabourID($hrmconn, $locationid, $seriesid = null)
{
    // 1. Get location shortname
    $prefix = '';
    $stmt = $hrmconn->prepare("SELECT shortname FROM hrm_AttendanceLocationMaster WHERE LocationId = ?");
    if ($stmt) {
        $stmt->bind_param("s", $locationid);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        if ($res && !empty($res['shortname'])) {
            $prefix = trim($res['shortname']);
        }
        $stmt->close();
    }
    
    if (empty($prefix)) {
        $prefix = strtoupper(substr($locationid, 0, 8)); // Fallback
    }

    // 2. Get max seriesid + 1 for this location
    $next_series = 1;
    $stmt = $hrmconn->prepare("SELECT IFNULL(MAX(seriesid), 0) + 1 AS next_series FROM hrm_LabourMaster WHERE locationid = ?");
    if ($stmt) {
        $stmt->bind_param("s", $locationid);
        $stmt->execute();
        $resSeries = $stmt->get_result()->fetch_assoc();
        $next_series = $resSeries['next_series'] ?? 1;
        $stmt->close();
    }

    if ($seriesid !== null) {
        $seriesid = $next_series;
    }

    $newID = $prefix . '-' . $next_series;

    // Final check for duplicates (safety)
    $checkSql = "SELECT EmployeeID FROM hrm_LabourMaster WHERE EmployeeID = ?";
    $checkStmt = $hrmconn->prepare($checkSql);
    if ($checkStmt) {
        $checkStmt->bind_param("s", $newID);
        $checkStmt->execute();
        $checkRes = $checkStmt->get_result();
        if ($checkRes->num_rows > 0) {
            $newID = $prefix . '-' . ($next_series + 1);
            if ($seriesid !== null) {
                $seriesid = $next_series + 1;
            }
        }
        $checkStmt->close();
    }

    return $newID;
}


function saveOrUpdateLabourEmployee($hrmconn, $employeeData)
{
    // Check if employee exists
    if (!empty($employeeData['EmployeeID'])) {
        $checkSql = "SELECT EmployeeID FROM hrm_LabourMaster WHERE EmployeeID = ? AND projectid = ?";
        $stmt = $hrmconn->prepare($checkSql);
        $stmt->bind_param("ss", $employeeData['EmployeeID'], $employeeData['projectid']);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->fetch_assoc();
        $stmt->close();
    } else {
        // Before insert check also duplicate according the given details (Mobile No & First Name)
        $checkSql = "SELECT EmployeeID FROM hrm_LabourMaster WHERE MobileNo = ? AND FirstName = ? AND projectid = ?";
        $stmt = $hrmconn->prepare($checkSql);
        $stmt->bind_param("sss", $employeeData['MobileNo'], $employeeData['FirstName'], $employeeData['projectid']);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->fetch_assoc();
        $stmt->close();
        
        if ($exists) {
            return [
                "success" => false,
                "message" => "Duplicate Error: A labour with this Mobile Number and Name already exists."
            ];
        }
    }
    if ($exists) {
        // Update existing employee
        $updateSql = "UPDATE hrm_LabourMaster SET seriesid = ?, TitleName = ?, FirstName = ?, MiddleName = ?, LastName = ?, FatherName = ?, MotherName = ?, MaritalStatus = ?, SpouseName = ?, Gender = ?, DateOfBirth = ?, Email = ?, MobileNo = ?, Nationality = ?, PassportNumber = ?, graftingskill = ?, AddressLine1 = ?, AddressLine2 = ?, AddressLine3 = ?, AddressLine4 = ?, PostalCode = ?, CountryName = ?, PanNumber = ?, AadharNumber = ?, DateOfJoining = ?, addedby = ?, locationid = ?, stateid = ?, districtid = ?, tahseel = ?, city = ?, address = ?, updated_at = CURRENT_TIMESTAMP
            WHERE EmployeeID = ? AND companyid = ? AND subcompanyid = ? AND projectid = ?";
        $stmt = $hrmconn->prepare($updateSql);
        if (!$stmt) {
            return [
                "success" => false,
                "message" => "Error preparing update statement: " . $hrmconn->error
            ];
        }
        $seriesid = isset($employeeData['seriesid']) ? (int)$employeeData['seriesid'] : 0;
        $TitleName = '';
        if (!empty($employeeData['TitleName'])) {
            // $TitleName = addslashes($employeeData['TitleName']);
        } else {
            // Query the database to retrieve existing TitleName
            $existQ = "SELECT TitleName, Gender, MaritalStatus FROM hrm_LabourMaster WHERE EmployeeID = ? LIMIT 1";
            $existStmt = $hrmconn->prepare($existQ);
            if ($existStmt) {
                $existStmt->bind_param("s", $EmployeeID);
                $existStmt->execute();
                $existRes = $existStmt->get_result()->fetch_assoc();
                $existStmt->close();
            }
        }
        $FirstName = isset($employeeData['FirstName']) ? addslashes($employeeData['FirstName']) : null;
        $MiddleName = isset($employeeData['MiddleName']) ? addslashes($employeeData['MiddleName']) : null;
        $LastName = isset($employeeData['LastName']) ? addslashes($employeeData['LastName']) : null;
        $FatherName = isset($employeeData['FatherName']) ? addslashes($employeeData['FatherName']) : null;
        $MotherName = isset($employeeData['MotherName']) ? addslashes($employeeData['MotherName']) : null;
        $MaritalStatus = isset($employeeData['MaritalStatus']) ? addslashes($employeeData['MaritalStatus']) : null;
        $SpouseName = isset($employeeData['SpouseName']) ? addslashes($employeeData['SpouseName']) : null;
        $Gender = isset($employeeData['Gender']) ? addslashes($employeeData['Gender']) : null;
        $DateOfBirth = isset($employeeData['DateOfBirth']) ? addslashes($employeeData['DateOfBirth']) : null;
        $Email = isset($employeeData['Email']) ? addslashes($employeeData['Email']) : null;
        $MobileNo = isset($employeeData['MobileNo']) ? addslashes($employeeData['MobileNo']) : null;
        $Nationality = isset($employeeData['Nationality']) ? addslashes($employeeData['Nationality']) : null;
        $PassportNumber = isset($employeeData['PassportNumber']) ? addslashes($employeeData['PassportNumber']) : null;
        $graftingskill = isset($employeeData['graftingskill']) ? addslashes($employeeData['graftingskill']) : null;
        $AddressLine1 = isset($employeeData['AddressLine1']) ? addslashes($employeeData['AddressLine1']) : null;
        $AddressLine2 = isset($employeeData['AddressLine2']) ? addslashes($employeeData['AddressLine2']) : null;
        $AddressLine3 = isset($employeeData['AddressLine3']) ? addslashes($employeeData['AddressLine3']) : null;
        $AddressLine4 = isset($employeeData['AddressLine4']) ? addslashes($employeeData['AddressLine4']) : null;
        $PostalCode = isset($employeeData['PostalCode']) ? addslashes($employeeData['PostalCode']) : null;
        $CountryName = isset($employeeData['CountryName']) ? addslashes($employeeData['CountryName']) : null;
        $PanNumber = isset($employeeData['PanNumber']) ? addslashes($employeeData['PanNumber']) : null;
        $AadharNumber = isset($employeeData['AadharNumber']) ? addslashes($employeeData['AadharNumber']) : null;
        $DateOfJoining = isset($employeeData['DateOfJoining']) ? addslashes($employeeData['DateOfJoining']) : null;

        $addedby = isset($employeeData['addedby']) ? addslashes($employeeData['addedby']) : null;
        $EmployeeID = isset($exists['EmployeeID']) ? addslashes($exists['EmployeeID']) : null;
        $companyid = isset($employeeData['companyid']) ? $employeeData['companyid'] : null;
        $subcompanyid = isset($employeeData['subcompanyid']) ? $employeeData['subcompanyid'] : null;
        $projectid = isset($employeeData['projectid']) ? $employeeData['projectid'] : null;
        $locationid = isset($employeeData['locationid']) ? addslashes($employeeData['locationid']) : null;

        $stateid = !empty($employeeData['stateid']) ? $employeeData['stateid'] : null;
        $districtid = !empty($employeeData['districtid']) ? $employeeData['districtid'] : null;
        $tahseel = !empty($employeeData['tahseel']) ? $employeeData['tahseel'] : null;
        $city = !empty($employeeData['city']) ? $employeeData['city'] : null;
        $address = !empty($employeeData['address']) ? $employeeData['address'] : ($employeeData['AddressLine1'] ?? null);

        if($FirstName=="" || $FirstName==null || $FirstName=="undefined"){
            return [
                "success" => false,
                "message" => "Error Atlease name is requred of employee: " . $stmt->error
            ];
            die();
        }
        $stmt->bind_param(
            "isssssssssssssssssssssssssssssssssss",
            $seriesid,
            $TitleName,
            $FirstName,
            $MiddleName,
            $LastName,
            $FatherName,
            $MotherName,
            $MaritalStatus,
            $SpouseName,
            $Gender,
            $DateOfBirth,
            $Email,
            $MobileNo,
            $Nationality,
            $PassportNumber,
            $graftingskill,
            $AddressLine1,
            $AddressLine2,
            $AddressLine3,
            $AddressLine4,
            $PostalCode,
            $CountryName,
            $PanNumber,
            $AadharNumber,
            $DateOfJoining,
            $addedby,
            $locationid,
            $stateid,
            $districtid,
            $tahseel,
            $city,
            $address,
            $EmployeeID,
            $companyid,
            $subcompanyid,
            $projectid);

        if ($stmt->execute()) {
            $stmt->close();
            return [
                "success" => true,
                "message" => "Employee details updated successfully.",
                "employeeID" => $exists['EmployeeID']
            ];
        } else {
            return [
                "success" => false,
                "message" => "Error updating data: " . $stmt->error
            ];
        }
    } else {
        // Suggested ID / series ID duplicate check
        $seriesid_input = isset($employeeData['seriesid']) ? trim($employeeData['seriesid']) : '';
        $seriesid = 0;
        $newEmployeeID = "";

        // Get location prefix
        $prefix = '';
        $stmt = $hrmconn->prepare("SELECT shortname FROM hrm_AttendanceLocationMaster WHERE LocationId = ?");
        if ($stmt) {
            $stmt->bind_param("s", $employeeData['locationid']);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            if ($res && !empty($res['shortname'])) {
                $prefix = trim($res['shortname']);
            }
            $stmt->close();
        }
        if (empty($prefix)) {
            $prefix = strtoupper(substr($employeeData['locationid'], 0, 8)); // Fallback
        }

        if ($seriesid_input !== "") {
            $suggestedEmployeeID = $prefix . '-' . $seriesid_input;

            // Check if suggested ID already exists
            $checkStmt = $hrmconn->prepare("SELECT EmployeeID, FirstName, MiddleName, LastName FROM hrm_LabourMaster WHERE EmployeeID = ?");
            if ($checkStmt) {
                $checkStmt->bind_param("s", $suggestedEmployeeID);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                $idExists = $checkResult->fetch_assoc();
                $checkStmt->close();

                if ($idExists) {
                    $fullName = trim(($idExists['FirstName'] ?? '') . ' ' . ($idExists['MiddleName'] ?? '') . ' ' . ($idExists['LastName'] ?? ''));
                    $fullName = preg_replace('/\s+/', ' ', $fullName);
                    return [
                        "success" => false,
                        "message" => "Suggested ID is already exist with name: " . $fullName
                    ];
                }
            }
            $newEmployeeID = $suggestedEmployeeID;
            $seriesid = (int)$seriesid_input;
        } else {
            // Auto generate new EmployeeID and seriesid using Master function
            $newEmployeeID = generateUniqueLabourID($hrmconn, $employeeData['locationid'], $seriesid);
        }

        $TitleName = '';
        if (!empty($employeeData['TitleName'])) {
            // $TitleName = addslashes($employeeData['TitleName']);
        } else {
            $genderVal = $employeeData['Gender'] ?? 'Male';
            $maritalVal = $employeeData['MaritalStatus'] ?? 'Single';
            // if (strcasecmp($genderVal, 'Female') === 0) {
            //     $TitleName = (strcasecmp($maritalVal, 'Married') === 0) ? 'Mrs.' : 'Miss';
            // } else {
            // }
            $TitleName = '';
        }

        $stateid = !empty($employeeData['stateid']) ? $employeeData['stateid'] : null;
        $districtid = !empty($employeeData['districtid']) ? $employeeData['districtid'] : null;
        $tahseel = !empty($employeeData['tahseel']) ? $employeeData['tahseel'] : null;
        $city = !empty($employeeData['city']) ? $employeeData['city'] : null;
        $address = !empty($employeeData['address']) ? $employeeData['address'] : ($employeeData['AddressLine1'] ?? null);

        // Insert new employee
        $insertSql = "INSERT INTO hrm_LabourMaster (seriesid, EmployeeID, companyid, subcompanyid, projectid, TitleName, FirstName, MiddleName, LastName, FatherName, MotherName, MaritalStatus, SpouseName, Gender, DateOfBirth, Email, MobileNo, Nationality, PassportNumber, graftingskill, AddressLine1, AddressLine2, AddressLine3, AddressLine4, PostalCode, CountryName, PanNumber, AadharNumber, DateOfJoining, addedby, locationid, stateid, districtid, tahseel, city, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $hrmconn->prepare($insertSql);
        if (!$stmt) {
            return [
                "success" => false,
                "message" => "Error preparing insert statement: " . $hrmconn->error
            ];
        }
        $stmt->bind_param("isssssssssssssssssssssssssssssssssss", $seriesid, $newEmployeeID, $employeeData['companyid'], $employeeData['subcompanyid'], $employeeData['projectid'], $TitleName, $employeeData['FirstName'], $employeeData['MiddleName'], $employeeData['LastName'], $employeeData['FatherName'], $employeeData['MotherName'], $employeeData['MaritalStatus'], $employeeData['SpouseName'], $employeeData['Gender'], $employeeData['DateOfBirth'], $employeeData['Email'], $employeeData['MobileNo'], $employeeData['Nationality'], $employeeData['PassportNumber'], $employeeData['graftingskill'], $employeeData['AddressLine1'], $employeeData['AddressLine2'], $employeeData['AddressLine3'], $employeeData['AddressLine4'], $employeeData['PostalCode'], $employeeData['CountryName'], $employeeData['PanNumber'], $employeeData['AadharNumber'], $employeeData['DateOfJoining'], $employeeData['addedby'], $employeeData['locationid'], $stateid, $districtid, $tahseel, $city, $address);

        if ($stmt->execute()) {
            $stmt->close();
            return [
                "success" => true,
                "message" => "Employee registered successfully.",
                "employeeID" => $newEmployeeID
            ];
        } else {
            return [
                "success" => false,
                "message" => "Error inserting data: " . $stmt->error
            ];
        }
    }
}


function insertOrUpdateLabourDocument($hrmconn, $companyid, $subcompanyid, $projectid, $employeeID, $documentType, $documentPath) {
    $checkSql = "SELECT EmployeeID FROM hrm_labourdocuments WHERE companyid = ? AND projectid =? AND EmployeeID = ? AND DocumentType = ?";
    if ($checkStmt = $hrmconn->prepare($checkSql)) {
        $checkStmt->bind_param("ssss", $companyid, $projectid, $employeeID, $documentType);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        if ($result->num_rows > 0) {
            // Update existing record
            $updateSql = "UPDATE hrm_labourdocuments SET DocumentPath = ?, tedit = ? WHERE companyid = ? AND projectid = ? AND EmployeeID = ? AND DocumentType = ?";
            $tedit = "updated on ".date('Y-m-d h:i:s');
            if ($updateStmt = $hrmconn->prepare($updateSql)) {
                $updateStmt->bind_param("ssssss", $documentPath, $tedit, $companyid, $projectid, $employeeID, $documentType);
                $updateStmt->execute();
                $updateStmt->close();
            }
        } else {
            // Insert new record
            $insertSql = "INSERT INTO hrm_labourdocuments (companyid, subcompanyid, projectid, EmployeeID, DocumentType, DocumentPath) VALUES (?, ?, ?, ?, ?, ?)";
            if ($insertStmt = $hrmconn->prepare($insertSql)) {
                $insertStmt->bind_param("ssssss", $companyid, $subcompanyid, $projectid, $employeeID, $documentType, $documentPath);
                $insertStmt->execute();
                $insertStmt->close();
            }
        }
        $checkStmt->close();
    }
    return true;
}

function insertLeaveDetails($hrmconn, $companyid, $leave_code, $leave_name, $leave_type, $max_days, $carry_forward, $description) {
    $sql = "INSERT INTO hrm_PayrollLeaveTypeMaster (companyid,leave_code, leave_name, leave_type, max_days, carry_forward, description) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $hrmconn->prepare($sql);
    $stmt->bind_param("ssssiis", $companyid,addslashes($leave_code), addslashes($leave_name), addslashes($leave_type), $max_days, $carry_forward, addslashes($description));
    $rrst=[];
    if ($stmt->execute()) {
        $rrst = ['status'=>'Success', 'message'=>"New Leave details added successfully!"];
    } else {
        $rrst = ['status'=>'Error', 'message'=>$stmt->error];
    }
    return $rrst;
}

function updateLeaveDetails($hrmconn, $id, $leave_name, $leave_type, $max_days, $carry_forward, $description) {
    $sql = "UPDATE hrm_PayrollLeaveTypeMaster SET leave_name = ?, leave_type = ?, max_days = ?, carry_forward = ?, description = ? WHERE id = ?";
    
    $stmt = $hrmconn->prepare($sql);
    $stmt->bind_param("ssissi", addslashes($leave_name), addslashes($leave_type), $max_days, $carry_forward, addslashes($description), $id);
    $rrst=[];
    if ($stmt->execute()) {
        $rrst = ['status'=>'Success', 'message'=>"Leave details updated successfully!"];
    } else {
        $rrst = ['status'=>'Error', 'message'=>$stmt->error];
    }
    return $rrst;
}
function insertOrUpdateEmployeeCalculatedAttendance($hrmconn, $loginname, $companyid, $subcompanyid, $projectid, $employeeid, $attendanceyear, $attendancemonth, $daysinmonth, $workingdaysinmonth, $calculatepresentdays,$attFileid='') {
    $ttlHolidays = 0;
    $empWeekOffcount = 0;
    $absentdays = 0;
    $TotalApproveLeave = 0; 
    $HalfDays = 0;
    $LateArrivals = 0;
    $extraDays = 0;

    $Comments = 'Manual Attendance calculated on behalf or upload excel data on date ' . date('Y-m-d') . ' by ' . $loginname;
    $ttldays = floatval($workingdaysinmonth + $ttlHolidays + $empWeekOffcount);

    if ($workingdaysinmonth > $calculatepresentdays) {
        $absentdays = $workingdaysinmonth - $calculatepresentdays;
    } else if ($calculatepresentdays > $workingdaysinmonth) {
        $extraDays = $calculatepresentdays - $workingdaysinmonth;
    }

    // Check if record already exists
    $checkStmt = $hrmconn->prepare("SELECT AttendanceID FROM hrm_EmployeeCalculateAttendance WHERE EmployeeID = ? AND CompanyID = ? AND SubCompanyID = ? AND ProjectID = ? AND AttendanceYear = ? AND AttendanceMonth = ?");
    $checkStmt->bind_param("ssssii", $employeeid, $companyid, $subcompanyid, $projectid, $attendanceyear, $attendancemonth);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        // Update existing record
        $updateStmt = $hrmconn->prepare("UPDATE hrm_EmployeeCalculateAttendance 
            SET TotalDaysInMonth = ?, WorkingDaysInMonth = ?, WeekOffDays = ?, HoliDays = ?, PresentDays = ?, AbsentDays = ?, TotalApproveLeave = ?, HalfDays = ?, LateArrivals = ?, ExtraWorkingDays = ?, Comments = ?
            WHERE EmployeeID = ? AND CompanyID = ? AND SubCompanyID = ? AND ProjectID = ? AND AttendanceYear = ? AND AttendanceMonth = ?");

        $updateStmt->bind_param("sssssssssssssssss",
            $daysinmonth, $workingdaysinmonth, $empWeekOffcount, $ttlHolidays,
            $calculatepresentdays, $absentdays, $TotalApproveLeave, $HalfDays,
            $LateArrivals, $extraDays, $Comments,
            $employeeid, $companyid, $subcompanyid, $projectid, $attendanceyear, $attendancemonth
        );

        if ($updateStmt->execute()) {
            $returnmsg[] = array("status" => "Success", "message" => "✅ Attendance updated successfully!", "employeeid" => $employeeid);
        } else {
            $returnmsg[] = array("status" => "Error", "message" => "❌ Error updating attendance: " . $updateStmt->error, "employeeid" => $employeeid);
        }

        $updateStmt->close();
    } else {
        // Insert new record
        $insertStmt = $hrmconn->prepare("INSERT INTO hrm_EmployeeCalculateAttendance 
            (EmployeeID, CompanyID, SubCompanyID, ProjectID, AttendanceYear, AttendanceMonth, TotalDaysInMonth, WorkingDaysInMonth, WeekOffDays, HoliDays, PresentDays, AbsentDays, TotalApproveLeave, HalfDays, LateArrivals, ExtraWorkingDays, Comments, attFileid) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $insertStmt->bind_param("ssssiiidddddddddsi",
            $employeeid, $companyid, $subcompanyid, $projectid,
            $attendanceyear, $attendancemonth,
            $daysinmonth, $workingdaysinmonth, $empWeekOffcount, $ttlHolidays,
            $calculatepresentdays, $absentdays, $TotalApproveLeave, $HalfDays,
            $LateArrivals, $extraDays, $Comments, $attFileid
        );

        if ($insertStmt->execute()) {
            $returnmsg[] = array("status" => "Success", "message" => "✅ Attendance inserted successfully!", "employeeid" => $employeeid);
        } else {
            $returnmsg[] = array("status" => "Error", "message" => "❌ Error inserting attendance: " . $insertStmt->error, "employeeid" => $employeeid);
        }

        $insertStmt->close();
    }

    $checkStmt->close();

    return $returnmsg;
}

function insertOrUpdateEmployeeSalary($hrmconn, $data) {
    if (empty($data)) {
        return ["status" => "error", "message" => "No data provided"];
    }
    $success = true;
    $errors = [];
    foreach ($data as $row) {
        // Validate required fields
        $requiredFields = ['companyid', 'subcompanyid', 'projectid', 'EmployeeID', 'CTC', 'BasicSalary', 'HRA', 'ConveyanceAllowance', 'SpecialAllowance', 'CurrencyCode'];
        foreach ($requiredFields as $field) {
            if (!isset($row[$field]) || $row[$field] === '') {
                $errors[] = "Missing required field '$field' for EmployeeID " . ($row['EmployeeID'] ?? 'unknown');
                $success = false;
                continue 2;
            }
        }

        // Extract input
        $companyid          = intval($row['companyid']);
        $subcompanyid       = intval($row['subcompanyid']);
        $projectid          = intval($row['projectid']);
        $employeeID         = intval($row['EmployeeID']);
        $currencyCode       = trim($row['CurrencyCode']);

        // Get deductions (EPF, ESIC)
        $empddct = getEmployeeDeductionsComponents($hrmconn, $companyid, $subcompanyid, $projectid, $employeeID);
        $epfApplicable = $iTaxApplicable = $esicApplicable = false;

        if (!empty($empddct)) {
            foreach ($empddct as $empddkey) {
                if ($empddkey['DeductionId'] == 1) $epfApplicable = true;   // EPF
                if ($empddkey['DeductionId'] == 3) $esicApplicable = true;  // ESIC
                if ($empddkey['DeductionId'] == 4) $iTaxApplicable = true;  // ESIC
            }
        }

        // Convert annual to monthly
        $ctcy                 = $row['CTC'];
        $ctc                 = round(floatval($row['CTC']) / 12, 2);
        $grossSalary         = round(floatval($row['GrossSalary']) / 12, 2);
        $basicSalary         = round(floatval($row['BasicSalary']) / 12, 2);
        $hra                 = round(floatval($row['HRA']) / 12, 2);
        $conveyanceAllowance = round(floatval($row['ConveyanceAllowance']) / 12, 2);
        $specialAllowance    = round(floatval($row['SpecialAllowance']) / 12, 2);

        // Insert/Update SQL
        $sql = "INSERT INTO hrm_EmployeeSalary 
            (companyid, subcompanyid, projectid, EmployeeID, CTCY, CTC, GrossSalary, BasicSalary, HRA, ConveyanceAllowance, SpecialAllowance, CurrencyCode)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                CTCY = VALUES(CTCY),
                CTC = VALUES(CTC),
                GrossSalary = VALUES(GrossSalary),
                BasicSalary = VALUES(BasicSalary),
                HRA = VALUES(HRA),
                ConveyanceAllowance = VALUES(ConveyanceAllowance),
                SpecialAllowance = VALUES(SpecialAllowance),
                CurrencyCode = VALUES(CurrencyCode)";

        $stmt = $hrmconn->prepare($sql);
        if ($stmt) {
            // Correct binding: 4 integers, 6 floats, 1 string = iiiidddddds
            $stmt->bind_param(
                "iiiiddddddds",
                $companyid,
                $subcompanyid,
                $projectid,
                $employeeID,
                $ctcy,
                $ctc,
                $grossSalary,
                $basicSalary,
                $hra,
                $conveyanceAllowance,
                $specialAllowance,
                $currencyCode
            );

            if (!$stmt->execute()) {
                $errors[] = "Insert/Update failed for EmployeeID $employeeID: " . $stmt->error;
                $success = false;
            }

            $stmt->close();
        } else {
            $errors[] = "Statement preparation failed for EmployeeID $employeeID: " . $hrmconn->error;
            $success = false;
        }
    }

    if ($success) {
        return ["status" => "success", "message" => "Salary data inserted/updated successfully!"];
    } else {
        return ["status" => "error", "message" => "Some salary insertions/updates failed.", "errors" => $errors];
    }
}


function insertOrUpdateEmployeeEarnings($hrmconn, $data) {
    if (empty($data)) {
        return ["status" => "error", "message" => "No data provided"];
    }

    $sql = "INSERT INTO hrm_EmployeeEarningComponents 
        (companyid, subcompanyid, projectid, EmployeeId, ComponentId, ComponentName, Frequency, 
        EmployeeEarningType, EmployeeEarningValue, EmployeeEarningBase, description)
    VALUES 
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE 
        ComponentName = VALUES(ComponentName),
        Frequency = VALUES(Frequency),
        EmployeeEarningType = VALUES(EmployeeEarningType),
        EmployeeEarningValue = VALUES(EmployeeEarningValue),
        EmployeeEarningBase = VALUES(EmployeeEarningBase),
        description = VALUES(description),
        updated_at = CURRENT_TIMESTAMP";

    $stmt = $hrmconn->prepare($sql);
    if (!$stmt) {
        return ["status" => "error", "message" => "Prepare failed: " . $hrmconn->error];
    }

    foreach ($data as $row) {
        // Make sure all fields exist and cast appropriately
        $companyid             = intval($row['companyid']);
        $subcompanyid          = intval($row['subcompanyid']);
        $projectid             = intval($row['projectid']);
        $EmployeeId            = intval($row['EmployeeId']);
        $ComponentId           = intval($row['ComponentId']);
        $ComponentName         = $row['ComponentName'] ?? '';
        $Frequency             = $row['Frequency'] ?? '';
        $EmployeeEarningType   = $row['EmployeeEarningType'] ?? '';
        $EmployeeEarningValue  = floatval($row['EmployeeEarningValue']);
        $EmployeeEarningBase   = $row['EmployeeEarningBase'] ?? '';
        $description           = $row['description'] ?? '';

        $stmt->bind_param(
            "iiiissssdss", // correct types: 4 ints, 2 strings, 1 string, 1 float, 1 string, 1 string
            $companyid, $subcompanyid, $projectid, $EmployeeId, 
            $ComponentId, $ComponentName, $Frequency, 
            $EmployeeEarningType, $EmployeeEarningValue, $EmployeeEarningBase, $description
        );

        if (!$stmt->execute()) {
            return ["status" => "error", "message" => "Execute failed for EmployeeId $EmployeeId: " . $stmt->error];
        }
    }

    $stmt->close();
    return ["status" => "success", "message" => "All records processed successfully"];
}


function insertOrUpdateEmployeeDeductions($hrmconn, $companyid, $subcompanyid, $projectid, $employeeid, $data) {
    if (empty($data)) {
        return ["status" => "error", "message" => "No data provided"];
    }

    // Update tedit timestamp for tracking
    $teditDate = date('Y-m-d');
    $hrmconn->query(" UPDATE hrm_EmployeeDeductionsComponents SET tedit = '$teditDate' WHERE companyid = '$companyid' 
          AND subcompanyid = '$subcompanyid' AND projectid = '$projectid' AND EmployeeId = '$employeeid'");

    foreach ($data as $row) {

        $tedit = ($row['activestatus'] == "Active") ? "" : "Deactive";
        // Check if deduction entry already exists
        $chkq = $hrmconn->query("SELECT id FROM hrm_EmployeeDeductionsComponents WHERE companyid = '$companyid' AND subcompanyid = '$subcompanyid' AND projectid = '$projectid' AND EmployeeId = '$employeeid' AND DeductionId = '" . $hrmconn->real_escape_string($row['DeductionId']) . "'");

        if ($chkq && $chkq->num_rows > 0) {
            // Update existing entry
            $chkrow = $chkq->fetch_assoc();
            $updateSQL = "
                UPDATE hrm_EmployeeDeductionsComponents 
                SET DeductionName = ?, Frequency = ?, EmployeeDeductionType = ?, EmployeeDeductionValue = ?, 
                    EmployeeDeductionBase = ?, EmployerDeductionType = ?, EmployerDeductionValue = ?, 
                    EmployerDeductionBase = ?, IsIncludedInCTC = ?, IsIncludedInFBP = ?, IsProRataBasis = ?, 
                    description = ?, tedit = ?
                WHERE id = ?
            ";
            $stmt = $hrmconn->prepare($updateSQL);
            $stmt->bind_param(
                "ssssssssiiissi",
                $row['DeductionName'],
                $row['Frequency'],
                $row['EmployeeDeductionType'],
                $row['EmployeeDeductionValue'],
                $row['EmployeeDeductionBase'],
                $row['EmployerDeductionType'],
                $row['EmployerDeductionValue'],
                $row['EmployerDeductionBase'],
                $row['IsIncludedInCTC'],
                $row['IsIncludedInFBP'],
                $row['IsProRataBasis'],
                $row['description'],
                $tedit,
                $chkrow['id']
            );
        } else {
            // Insert new entry
            $tedit = ($row['activestatus'] == "Active") ? "" : "Deactive";
            $insertSQL = "
                INSERT INTO hrm_EmployeeDeductionsComponents (
                    companyid, subcompanyid, projectid, EmployeeId, DeductionId, DeductionName, Frequency,
                    EmployeeDeductionType, EmployeeDeductionValue, EmployeeDeductionBase, EmployerDeductionType,
                    EmployerDeductionValue, EmployerDeductionBase, IsIncludedInCTC, IsIncludedInFBP,
                    IsProRataBasis, description, tedit
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            $stmt = $hrmconn->prepare($insertSQL);
            $stmt->bind_param(
                "iiiiisssdssdsiiiss",
                $companyid,
                $subcompanyid,
                $projectid,
                $employeeid,
                $row['DeductionId'],
                $row['DeductionName'],
                $row['Frequency'],
                $row['EmployeeDeductionType'],
                $row['EmployeeDeductionValue'],
                $row['EmployeeDeductionBase'],
                $row['EmployerDeductionType'],
                $row['EmployerDeductionValue'],
                $row['EmployerDeductionBase'],
                $row['IsIncludedInCTC'],
                $row['IsIncludedInFBP'],
                $row['IsProRataBasis'],
                $row['description'],
                $tedit
            );
        }

        // Execute and check for errors
        if (!$stmt->execute()) {
            return ["status" => "error", "message" => "Error: " . $stmt->error];
        }
    }

    return ["status" => "success", "message" => "All records processed successfully"];
}

function insertOrUpdateBankDetails($hrmconn, $companyid, $subcompanyid, $projectid, $employeeID, $bankName, $accountNumber, $ifscCode, $swiftCode, $iban, $sortCode, $currencyCode, $cancelChequePath, $verifyStatus = 'Pending', $verifyBy = '', $verifyAt = null, $verifyRemark = '') {
    $now = date('Y-m-d H:i:s');
    $cleanBankName = trim($bankName);
    $cleanAccountNumber = trim($accountNumber);
    $cleanIfscCode = trim($ifscCode);
    $cleanSwiftCode = trim($swiftCode);
    $cleanIban = trim($iban);
    $cleanSortCode = trim($sortCode);
    $cleanCurrencyCode = trim($currencyCode) ?: 'INR';
    $cleanCancelChequePath = trim($cancelChequePath);
    $cleanVerifyStatus = trim($verifyStatus) ?: 'Pending';
    $cleanVerifyRemark = trim($verifyRemark);
    $cleanVerifyBy = trim($verifyBy);
    $cleanVerifyAt = $verifyAt ? trim($verifyAt) : ($cleanVerifyStatus === 'Verified' ? $now : null);

    // 1. Manage legacy hrm_EmployeeBankDetails table (only for valid positive integer EmployeeID)
    $numEmpId = intval(preg_replace('/[^0-9]/', '', $employeeID));
    if ($numEmpId > 0) {
        $updateSqlOld = "UPDATE hrm_EmployeeBankDetails SET tedit = 'update new' WHERE companyid = ? AND projectid = ? AND EmployeeID = ? AND EmployeeID > 0 AND (tedit IS NULL OR tedit = '')";
        if ($updateStmtOld = $hrmconn->prepare($updateSqlOld)) {
            $updateStmtOld->bind_param("ssi", $companyid, $projectid, $numEmpId);
            $updateStmtOld->execute();
            $updateStmtOld->close();
        }

        $insertSql = "INSERT INTO hrm_EmployeeBankDetails (companyid, subcompanyid, projectid, EmployeeID, BankName, AccountNumber, IFSCCode, SWIFTCode, IBAN, SortCode, CurrencyCode, CancelChequePath) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        if ($insertStmt = $hrmconn->prepare($insertSql)) {
            $insertStmt->bind_param("sssissssssss", $companyid, $subcompanyid, $projectid, $numEmpId, $cleanBankName, $cleanAccountNumber, $cleanIfscCode, $cleanSwiftCode, $cleanIban, $cleanSortCode, $cleanCurrencyCode, $cleanCancelChequePath);
            $insertStmt->execute();
            $insertStmt->close();
        }
    }

    // 2. Manage new hrm_labourbankdetails table
    $updateSqlNew = "UPDATE hrm_labourbankdetails SET tedit = 'update new' WHERE companyid = ? AND projectid = ? AND labourid = ? AND (tedit IS NULL OR tedit = '')";
    if ($updateStmtNew = $hrmconn->prepare($updateSqlNew)) {
        $updateStmtNew->bind_param("sss", $companyid, $projectid, $employeeID);
        $updateStmtNew->execute();
        $updateStmtNew->close();
    }

    // Insert new active record
    $insertSqlNew = "INSERT INTO hrm_labourbankdetails (companyid, subcompanyid, projectid, labourid, bankname, accountnumber, ifsccode, currencycode, cancelchequepath, verifystatus, verifyremark, verifyby, verifyat) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    if ($insertStmtNew = $hrmconn->prepare($insertSqlNew)) {
        $insertStmtNew->bind_param("sssssssssssss", $companyid, $subcompanyid, $projectid, $employeeID, $cleanBankName, $cleanAccountNumber, $cleanIfscCode, $cleanCurrencyCode, $cleanCancelChequePath, $cleanVerifyStatus, $cleanVerifyRemark, $cleanVerifyBy, $cleanVerifyAt);
        $insertStmtNew->execute();
        $insertStmtNew->close();
    }

    return "Bank details updated and inserted successfully across tables.";
}

function insertOrUpdateLabourContactPersonDetails($hrmconn,$companyid,$subcompanyid,$projectid,$employeeId,$contactName,$relationship,$phoneNumber,$emailAddress,$postalCode,$address,$note,$isPrimaryContact) {
    // Check if the record already exists
    $checkQuery = "SELECT COUNT(*) as record_count FROM hrm_LabourContactPersonDetails WHERE companyid =? AND subcompanyid =? AND projectid =? AND EmployeeId = ? AND ContactName = ?";
    $stmt = $hrmconn->prepare($checkQuery);
    $stmt->bind_param("sssss", $companyid,$subcompanyid,$projectid,$employeeId,$contactName);
    $stmt->execute();
    $result = $stmt->get_result();
    $recordExists = $result->fetch_assoc()['record_count'] > 0;
    $stmt->close();
    if ($recordExists) {
        $updateQuery = "UPDATE hrm_LabourContactPersonDetails SET Relationship = ?, PhoneNumber = ?, EmailAddress = ?, PostalCode = ?, Address = ?, Note = ?, IsPrimaryContact = ?, UpdatedAt = CURRENT_TIMESTAMP WHERE EmployeeId = ? AND companyid = ? AND subcompanyid = ? AND projectid = ?";
        $stmt = $hrmconn->prepare($updateQuery);
        $stmt->bind_param("sssssssssss", $relationship, $phoneNumber, $emailAddress, $postalCode, addslashes($address), addslashes($note), $isPrimaryContact, $employeeId, addslashes($companyid), addslashes($subcompanyid), addslashes($projectid));
        $stmt->execute();
        // $stmt->close();
        return $stmt->affected_rows > 0 ? "Record updated successfully." : "No changes made.";
    } else {
        // Insert a new record
        $insertQuery = "INSERT INTO hrm_LabourContactPersonDetails (companyid,subcompanyid,projectid,EmployeeId, ContactName, Relationship, PhoneNumber, EmailAddress, PostalCode, Address, Note, IsPrimaryContact, CreatedAt, UpdatedAt) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
        $stmt = $hrmconn->prepare($insertQuery);
        $stmt->bind_param("ssssssssssis", $companyid,$subcompanyid,$projectid,$employeeId, addslashes($contactName), $relationship, $phoneNumber, $emailAddress, $postalCode, addslashes($address), addslashes($note), $isPrimaryContact);
        $stmt->execute();
        // $stmt->close();

        return $stmt->affected_rows > 0 ? "Record inserted successfully." : "Failed to insert record.";
    }
}

function addOrUpdateDepartment($hrmconn, $departmentId, $departmentName, $companyId, $loginname) {
    if (!empty($departmentId)) {
        $updateSql = "UPDATE hrm_DepartmentMaster SET DepartmentName = ?, addedby = ? WHERE companyid = ? and DepartmentID = ?";
        $stmt = $hrmconn->prepare($updateSql);
        $stmt->bind_param("sssi", $departmentName, $loginname, $companyId, $departmentId);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $stmt->close();
            return "Updated";
        }
        $stmt->close();
    }

    $insertSql = "INSERT INTO hrm_DepartmentMaster (DepartmentName, companyid, addedby) VALUES (?, ?, ?)";
    $stmt = $hrmconn->prepare($insertSql);
    $stmt->bind_param("sss", $departmentName, $companyId, $loginname);
    $stmt->execute();
    $stmt->close();
    return "Inserted";
}

function addOrUpdateDesignation($hrmconn, $designationId, $designationName, $companyId, $loginname) {
    // Trim and normalize input
    $designationName = trim($designationName);

    // ✅ If updating an existing designation
    if (!empty($designationId)) {
        // Check for duplicate name (excluding current ID)
        $checkSql = "SELECT DesignationID FROM hrm_DesignationsMaster WHERE companyid = ? AND DesignationName = ? AND DesignationID <> ?";
        $stmt = $hrmconn->prepare($checkSql);
        $stmt->bind_param("ssi", $companyId, $designationName, $designationId);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->close();
            return "Duplicate"; // ❌ Same name exists
        }
        $stmt->close();

        // Update query
        $updateSql = "UPDATE hrm_DesignationsMaster 
                      SET DesignationName = ?, addedby = ? 
                      WHERE companyid = ? AND DesignationID = ?";
        $stmt = $hrmconn->prepare($updateSql);
        $stmt->bind_param("sssi", $designationName, $loginname, $companyId, $designationId);
        $stmt->execute();

        $result = $stmt->affected_rows > 0 ? "Updated" : "NoChange";
        $stmt->close();
        return $result;
    }

    // ✅ If inserting new designation — check for duplicates first
    $checkSql = "SELECT DesignationID FROM hrm_DesignationsMaster WHERE companyid = ? AND DesignationName = ?";
    $stmt = $hrmconn->prepare($checkSql);
    $stmt->bind_param("ss", $companyId, $designationName);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        return "Duplicate"; // ❌ Duplicate found, skip insert
    }
    $stmt->close();

    // Insert new record
    $insertSql = "INSERT INTO hrm_DesignationsMaster (DesignationName, companyid, addedby) VALUES (?, ?, ?)";
    $stmt = $hrmconn->prepare($insertSql);
    $stmt->bind_param("sss", $designationName, $companyId, $loginname);
    $stmt->execute();
    $stmt->close();

    return "Inserted";
}


function saveEmployeeAdvance($conn, $data) {
    // Default status
    $status = ["status" => "Error", "message" => "", "advance_id" => null];

    try {
        // Start transaction
        $conn->begin_transaction();

        // Validate required fields
        if (!isset($data['EmployeeID'], $data['AdvanceDate'], $data['AdvanceAmount'])) {
            throw new Exception("Missing required fields: EmployeeID, AdvanceDate, AdvanceAmount.");
        }

        // Prepare values
        $ProjectID         = $data['ProjectID'] ?? null;
        $AdvanceAmount     = $data['AdvanceAmount'];
        $DeductionPerMonth = $data['DeductionPerMonth'] ?? 0;
        $Purpose           = $data['Purpose'] ?? null;
        $Remarks           = $data['Remarks'] ?? null;
        $ApprovedBy        = $data['ApprovedBy'] ?? null;
        $Status            = $data['Status'] ?? 'Pending';

        if (!empty($data['AdvanceID'])) {
            // UPDATE
            $query = "UPDATE hrm_EmployeeAdvance 
                      SET ProjectID = ?, 
                          EmployeeID = ?, 
                          AdvanceDate = ?, 
                          AdvanceAmount = ?,
                          DeductionPerMonth = ?, 
                          Purpose = ?, 
                          Remarks = ?, 
                          ApprovedBy = ?, 
                          Status = ?, 
                          UpdatedAt = CURRENT_TIMESTAMP
                      WHERE AdvanceID = ?";

            $stmt = $conn->prepare($query);
            if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);

            $stmt->bind_param(
                "sssdisssii",
                $ProjectID,
                $data['EmployeeID'],
                $data['AdvanceDate'],
                $AdvanceAmount,
                $DeductionPerMonth,
                $Purpose,
                $Remarks,
                $ApprovedBy,
                $Status,
                $data['AdvanceID']
            );

            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            $stmt->close();

            $status['status'] = "Success";
            $status['message'] = "Advance updated successfully.";
            $status['advance_id'] = $data['AdvanceID'];

        } else {
            // INSERT
            $query = "INSERT INTO hrm_EmployeeAdvance 
                        (ProjectID, EmployeeID, AdvanceDate, AdvanceAmount, DeductionPerMonth, Purpose, Remarks, ApprovedBy, Status)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($query);
            if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);

            $stmt->bind_param(
                "sssdissss",
                $ProjectID,
                $data['EmployeeID'],
                $data['AdvanceDate'],
                $AdvanceAmount,
                $DeductionPerMonth,
                $Purpose,
                $Remarks,
                $ApprovedBy,
                $Status
            );

            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            $insertId = $stmt->insert_id;
            if ($insertId) {
                $status['status'] = "Success";
                $status['message'] = "Advance inserted successfully.";
                $status['advanceid'] = $insertId;
            }

            $stmt->close();
        }

        // If everything is fine -> commit
        $conn->commit();

    } catch (Exception $e) {
        // Rollback if error
        $conn->rollback();
        $status['message'] = "Transaction failed: " . $e->getMessage();
    }

    return $status;
}

function CreateRequestforEmployeeAdvance($conn,$hrmconn,$companyid,$subcompanyid,$projectid,$fromaccountid,$paytoid,$modeoftransfer,$requestamount,$description,$purposecode,$loginname,$filepath,$requestdatatype,$paymentduedate){
	date_default_timezone_set('asia/kolkata');
	//purpose details
	$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary FROM purpose_code_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and lcase(purposecode)='".$purposecode."'");
	$purpose_category = "";
	$purpose_categoryid ="";
	$purpose_subcategory ="";
	$purpose_subcategoryid ="";
	$purpose ="";
	$purpose_code ="";
	if($sltp->num_rows>0){
		while ($akey=$sltp->fetch_assoc()) {
			$purpose_category = $akey['category'];
			$purpose_categoryid = $akey['categoryid'];
			$purpose_subcategory = $akey['sub_category'];
			$purpose_subcategoryid = $akey['sub_categoryid'];
			$purpose = $akey['purpose'];
			$purpose_code = $akey['purposecode'];
			$default_beneficiary = $akey['default_beneficiary'];
		}
	}
	
	//payto details
	$payname="";
	$beneficiaryname="";
	$payto_bankname="";
	$payto_accountno="";
	$payto_bankifsccode="";
	$is_intermediate_transfer="";
	$payto_accountid="";

	$allqry = $conn->query("select paytoid, paytoname, accountno, bankname, ifsccode FROM tbl_payto_master where cmp_id='".$companyid."' and subcmp_id = '".$subcompanyid."' and (tedit is null or tedit='') and paytoid = '".$paytoid."' and (paytoname is not null and paytoname<>'')");
	if($allqry->num_rows>0){
		while ($pptkey =$allqry->fetch_assoc()) {
			$paytoname = $pptkey['paytoname'];
			$beneficiaryname=$pptkey['paytoname'];
			$payto_bankname=$pptkey['bankname'];
			$payto_accountno=$pptkey['accountno'];
			$payto_bankifsccode=$pptkey['ifsccode'];
		}
	}
	
	
	//payfrom account details
	$from_bankid = "";
	$from_accountid = "";
	$from_bankname = "";
	$from_accountname = "";
	$from_accountno = "";
	$from_ifsccode = "";
	if($fromaccountid){
		$accinq=$conn->query("select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername FROM tbl_master_account WHERE (tedit is null or tedit='') and  cmp_id='".$companyid."' and subcmp_id in('".str_replace(",", "','", $subcompanyid)."') and projectid in('".str_replace(",", "','", $projectid)."') and accountid in('".str_replace(",", "','", $fromaccountid)."') and accountname not like '%_FD'");
		if($accinq->num_rows>0){
			while ($accrow=$accinq->fetch_assoc()) {
				$from_bankid=$accrow['bankid'];
				$from_accountid=$accrow['accountid'];
				$from_bankname=$accrow['bankname'];
				$from_accountname=$accrow['accountname'];
				$from_accountno=$accrow['accountno'];
				$from_ifsccode=$accrow['ifsccode'];
			}
		}
	}

	// $prj=projectdetails($conn,$companyid,$subcompanyid,"projectid",$projectid);
	$pquery=$conn->query("select countryid, projectname, project_email, documenttype FROM tbl_project_master where companyid='".str_replace(",", "','", $companyid)."' and subcompanyid='".str_replace(",", "','", $subcompanyid)."' and projectid in ('".str_replace(",", "','", $projectid)."') and (tedit is null or tedit='')");
	$documenttype="";
	$countryid="";
	if($pquery->num_rows>0){
		while ($ppkey = $pquery->fetch_assoc()) {
			$documenttype = $ppkey['documenttype'];
			$countryid = $ppkey['countryid'];
		}
	}
	$beneficierytype="Individual";
	$maxsno=GetMaxTempSno_hrmmd($conn);
	$newrequestno = getnewrequestno_hrmmd($conn,$companyid,$subcompanyid,$projectid);
	$resultarr=array();
	if($purpose_code){
		if($beneficiaryname){
			$insertdata = "INSERT INTO tbl_paymentrequest (temp_requestno, request_no, request_date,paymentduedate, requested_by, paytoid, payto, payment_mode, beneficiery_type, beneficiery_name, request_amount, purpose_type, from_accountid, from_bankname, from_accountno, payto_bankname, payto_accountno, payto_bankifsccode, payto_accountid, software_version, status_cheque, pending_description, countryid, companyid, subcompanyid, projectid,subprojectid, requestdatatype,is_intermediate_transfer) VALUES ('".$maxsno."', '".$newrequestno."', '".date('Y-m-d')."','".$paymentduedate."','".addslashes($loginname)."', '".$paytoid."', '".addslashes($paytoname)."', '".$modeoftransfer."', '".$beneficierytype."', '".addslashes($beneficiaryname)."', '".addslashes($requestamount)."', '".$purpose_category."', '".$from_accountid."', '".$from_bankname."', '".$from_accountno."', '".$payto_bankname."', '".$payto_accountno."', '".$payto_bankifsccode."', '".$payto_accountid."', 'Web', 'Pending', 'Request not seen by Admin', '".$countryid."', '".$companyid."', '".$subcompanyid."', '".$projectid."', '".$subprojectid."', '".$requestdatatype."', '".$is_intermediate_transfer."')";
			if($conn->query($insertdata))
			{
				if($filepath!=""){
					$document_status = "completed";
					$temp = explode(".", $filepath);
					$extension=end($temp);
					$newfilename = round(microtime(true)) .'_'.date('His'). '.' . end($temp);
					$dir="../../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/requestdocument/".$newrequestno."/";
					if ( !is_dir( $dir ) ) {
					    mkdir($dir, 0755);       
					}
					$distpath =$dir.$newfilename;
					if(rename($filepath, $distpath)){
						removeEmptyDirectory($filepath);
					}
    				$newfilepath = str_replace("../", "", $distpath);
				}else{
					$document_status = "";
					$extension = "";
					$filepath = "";
				}
				$brkquery = "INSERT INTO tbl_paymentrequest_breakdown (countryid,companyid,subcompanyid,projectid, request_no, beneficiery_name, ammount, purpose_type,purpose_category,purpose_categoryid,purpose_subcategory,purpose_subcategoryid, purpose, purpose_code, description, document_status,documenttype,extension,filepath,paytoid,taxableamount) VALUES ('".$countryid."', '".$companyid."', '".$subcompanyid."', '".$projectid."', '".$newrequestno."', '".addslashes($beneficiaryname)."', '".addslashes($requestamount)."', '".$purpose_category."', '".$purpose_category."', '".$purpose_categoryid."', '".$purpose_subcategory."', '".$purpose_subcategoryid."', '".$purpose."', '".$purpose_code."', '".addslashes($description)."', '".$document_status."', '".$documenttype."', '".$extension."', '".$newfilepath."', '".$paytoid."','".addslashes($requestamount)."')";						
				if($conn->query($brkquery))
				{
					
					$resultarr=array('status'=>'Success', 'message'=>'Your advance against salary save & also request ('.$newrequestno.') hase been created', 'requestno'=>$newrequestno);
					$request_msg=$newrequestno;
				}else{
					$resultarr=array('status'=>'Error', 'message'=>'Request number incomplete');
					$request_msg="Technical issue";
				}
			}else{
				$resultarr=array('status'=>'Error', 'message'=>'Technical issue');
			}
			
		}else{
			$resultarr=array('status'=>'Error', 'message'=>'beneficiaryname details wrong/missing');
		}		
	}else{
		$resultarr=array('status'=>'Error', 'message'=>'purpose details wrong/missing');
	}		
	return $resultarr;
}



function getnewrequestno_hrmmd($conn,$companyid,$subcompanyid,$projectid){
	$tempmaxno = GetMaxTempSno_hrmmd($conn);
	$requestno = "REQ-".$companyid.'-'.$subcompanyid.'-'.$projectid.'-'.$tempmaxno;
	return $requestno;
}
function GetMaxTempSno_hrmmd($conn){
	$selectsno="select max(temp_requestno) as texp_sno from tbl_paymentrequest";
	$result_sno = $conn->query($selectsno);
	if ($result_sno->num_rows > 0)
	{
		$rowsno = $result_sno->fetch_array();
		$maxtempsno = $rowsno['texp_sno']+1;
	}
	return $maxtempsno;
}


function insertOrUpdateLabourCalculatedSalary($hrmconn, $LocationId, $calSalarydata) {
    // Check if salary record exists for the given employee, year, and month
    $checkQuery = "SELECT SalaryID 
                   FROM hrm_LabourCalculatedSalary 
                   WHERE EmployeeID = ? AND DateFrom = ? AND DateTo = ? AND LocationId = ?";
    $stmt = $hrmconn->prepare($checkQuery);
    $stmt->bind_param("ssss", $calSalarydata['EmployeeID'], $calSalarydata['dateFrom'], $calSalarydata['dateTo'], $LocationId);
    $stmt->execute();
    $stmt->store_result();    

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($SalaryID);
        $stmt->fetch();

        $updateQuery = "UPDATE hrm_LabourCalculatedSalary 
        SET SalaryYear = ?, SalaryMonth = ?, DateFrom = ?, DateTo = ?, 
            PayPerDay = ?, PresentDays = ?, incentivegrafting = ?, incentiveseasional = ?, overtimepayment = ?, otherpayment = ?, basiclabourpayment = ?, NetSalary = ?, Comments = ?, LocationId = ?, created_at = NOW()
        WHERE EmployeeID = ? AND DateFrom = ? AND DateTo = ?";
        
        $updateStmt = $hrmconn->prepare($updateQuery);
        $updateStmt->bind_param(
            "iissddddddddsssss",  // 15 chars
            $calSalarydata['SalaryYear'],   // i
            $calSalarydata['SalaryMonth'],  // i
            $calSalarydata['dateFrom'],     // s
            $calSalarydata['dateTo'],       // s
            $calSalarydata['PayPerDay'],    // d
            $calSalarydata['PresentDays'],    // d
            $calSalarydata['incentivegrafting'],     //d
            $calSalarydata['incentiveseasional'],    //d
            $calSalarydata['overtimepayment'],   //d
            $calSalarydata['otherpayment'],  //d
            $calSalarydata['basiclabourpayment'],    //d
            $calSalarydata['NetPaySalary'], // d
            $calSalarydata['Comments'],     // s
            $calSalarydata['LocationId'],
            $calSalarydata['EmployeeID'],   // i
            $calSalarydata['dateFrom'],     // s
            $calSalarydata['dateTo']        // s
        );

        if ($updateStmt->execute()) {
            return json_encode([
                "status" => "success",
                "message" => "Salary updated successfully.",
                "SalaryID" => $SalaryID
            ]);
        } else {
            return json_encode([
                "status" => "error",
                "message" => "Error updating salary: " . $updateStmt->error
            ]);
        }
    } else {
        // Insert new salary record
        $insertQuery = "INSERT INTO hrm_LabourCalculatedSalary 
        (EmployeeID, CompanyID, SubCompanyID, ProjectID, SalaryYear, SalaryMonth, 
         DateFrom, DateTo, PayPerDay, PresentDays, incentivegrafting, incentiveseasional, overtimepayment, otherpayment, basiclabourpayment, NetSalary, Comments, LocationId, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        
        $insertStmt = $hrmconn->prepare($insertQuery);
        $insertStmt->bind_param(
            "ssssiissddddddddss",  // 16 chars
            $calSalarydata['EmployeeID'],   // i
            $calSalarydata['CompanyID'],    // s
            $calSalarydata['SubCompanyID'], // s
            $calSalarydata['ProjectID'],    // s
            $calSalarydata['SalaryYear'],   // i
            $calSalarydata['SalaryMonth'],  // i
            $calSalarydata['dateFrom'],     // s
            $calSalarydata['dateTo'],       // s
            $calSalarydata['PayPerDay'],
            $calSalarydata['PresentDays'],
            $calSalarydata['incentivegrafting'],     //d
            $calSalarydata['incentiveseasional'],    //d
            $calSalarydata['overtimepayment'],   //d
            $calSalarydata['otherpayment'],  //d
            $calSalarydata['basiclabourpayment'],    //d
            $calSalarydata['NetPaySalary'], // d
            $calSalarydata['Comments'],      // s
            $calSalarydata['LocationId']
        );

        if ($insertStmt->execute()) {
            updateSalaryIdInAttendance($hrmconn, $calSalarydata['ProjectID'], $calSalarydata['EmployeeID'], $calSalarydata['dateFrom'], $calSalarydata['dateTo'], $calSalarydata['LocationId'], $insertStmt->insert_id);
            return json_encode([
                "status" => "success",
                "message" => "Salary inserted successfully.",
                "SalaryID" => $insertStmt->insert_id
            ]);
        } else {
            return json_encode([
                "status" => "error",
                "message" => "Error inserting salary: " . $insertStmt->error
            ]);
        }
    }
}

function insertLabourPaymentAccount($conn, $data) {
    // Define which columns you want to check for duplicates
    $checkFields = [
        'idate',
        'companyid',
        'subcompanyid',
        'projectid',
        'request_no',
        'reference_no',
        'paytoid',
        'cash_amount'
    ];

    // Build WHERE condition dynamically
    $where = [];
    $types = "";
    $values = [];
    foreach ($checkFields as $field) {
        $where[] = "$field = ?";
        $types .= "s";
        $values[] = $data[$field] ?? null;
    }
    $whereSql = implode(" AND ", $where);

    // 1. Check duplicate
    $sqlCheck = "SELECT COUNT(*) as cnt FROM labourpayment_account WHERE $whereSql";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bind_param($types, ...$values);
    $stmtCheck->execute();
    $result = $stmtCheck->get_result()->fetch_assoc();
    $stmtCheck->close();

    if ($result['cnt'] > 0) {
        return ["status" => "error", "message" => "Duplicate entry found"];
    }

    // 2. Insert if not duplicate
    $columns = array_keys($data);
    $placeholders = implode(",", array_fill(0, count($columns), "?"));
    $colNames = implode(",", $columns);

    $sqlInsert = "INSERT INTO labourpayment_account ($colNames) VALUES ($placeholders)";
    $stmtInsert = $conn->prepare($sqlInsert);

    $insertTypes = str_repeat("s", count($data)); // all as string (safe for varchar/date)
    $stmtInsert->bind_param($insertTypes, ...array_values($data));

    if ($stmtInsert->execute()) {
        $stmtInsert->close();
        return ["status" => "success", "message" => "Inserted successfully"];
    } else {
        $stmtInsert->close();
        return ["status" => "error", "message" => "Insert failed: " . $conn->error];
    }
}

function updateSalaryIdInAttendance($hrmconn, $ProjectID, $EmployeeID, $DateFrom, $DateTo, $LocationId, $SalaryID) {

    // If Location is ALL – do not filter by LocationId
    if ($LocationId == "all") {

        $updateStmt = $hrmconn->prepare("
            UPDATE hrm_labourcalculateattendance 
            SET SalaryID = ? 
            WHERE ProjectID = ? AND EmployeeID = ? AND DateFrom = ? AND DateTo = ?
        ");

        // Only EmployeeID is int, others are string
        $updateStmt->bind_param(
            "sssss",
            $SalaryID,   // string
            $ProjectID,  // string
            $EmployeeID, // int
            $DateFrom,   // string
            $DateTo      // string
        );

    } else {

        $updateStmt = $hrmconn->prepare("
            UPDATE hrm_labourcalculateattendance 
            SET SalaryID = ? 
            WHERE ProjectID = ? AND EmployeeID = ? AND DateFrom = ? AND DateTo = ? AND LocationId = ?
        ");

        // Only EmployeeID is int, others are string
        $updateStmt->bind_param(
            "ssssss",
            $SalaryID,   // string
            $ProjectID,  // string
            $EmployeeID, // int
            $DateFrom,   // string
            $DateTo,     // string
            $LocationId  // string
        );
    }

    // Execute and Build Response
    if ($updateStmt->execute()) {
        $returnmsg[] = array(
            "status" => "Success",
            "message" => "✅ Salary ID updated successfully!",
            "employeeid" => $EmployeeID
        );
    } else {
        $returnmsg[] = array(
            "status" => "Error",
            "message" => "❌ Error updating attendance: " . $updateStmt->error,
            "employeeid" => $EmployeeID
        );
    }

    $updateStmt->close();
    return $returnmsg;
}

function saveLabourOtherPayment($conn,$projectid,$EmployeeID,$datefrom,$dateto,$incentivegrafting = 0.00,$incentiveseasional = 0.00,$overtimepayment = 0.00,$otherpayment = 0.00,$Comments = null)
{
    $checkSql = "SELECT sno FROM hrm_LabourOtherPayment WHERE projectid = ? AND EmployeeID = ? AND datefrom = ? AND dateto = ? AND status IS NULL LIMIT 1";
    $checkStmt = mysqli_prepare($conn, $checkSql);
    mysqli_stmt_bind_param($checkStmt, "ssss", $projectid, $EmployeeID, $datefrom, $dateto);
    mysqli_stmt_execute($checkStmt);
    mysqli_stmt_store_result($checkStmt);
    if (mysqli_stmt_num_rows($checkStmt) > 0) {
        mysqli_stmt_bind_result($checkStmt, $sno);
        mysqli_stmt_fetch($checkStmt);
        mysqli_stmt_close($checkStmt);
        $updateSql = "UPDATE hrm_LabourOtherPayment SET incentivegrafting = ?, incentiveseasional = ?, overtimepayment = ?, otherpayment = ?, Comments = ? WHERE sno = ?";
        $updateStmt = mysqli_prepare($conn, $updateSql);
        mysqli_stmt_bind_param(
            $updateStmt,
            "ddddsi",
            $incentivegrafting,
            $incentiveseasional,
            $overtimepayment,
            $otherpayment,
            $Comments,
            $sno
        );
        if (mysqli_stmt_execute($updateStmt)) {
            mysqli_stmt_close($updateStmt);
            return [
                'status' => true,
                'action' => 'updated',
                'sno' => $sno
            ];
        } else {
            $error = mysqli_stmt_error($updateStmt);
            mysqli_stmt_close($updateStmt);
            return [
                'status' => false,
                'message' => $error
            ];
        }
    }

    // 🟢 No record → INSERT
    mysqli_stmt_close($checkStmt);
    $insertSql = "INSERT INTO hrm_LabourOtherPayment (projectid, EmployeeID, datefrom, dateto, incentivegrafting, incentiveseasional, overtimepayment, otherpayment, Comments) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $insertStmt = mysqli_prepare($conn, $insertSql);
    mysqli_stmt_bind_param(
        $insertStmt,
        "ssssdddds",
        $projectid,
        $EmployeeID,
        $datefrom,
        $dateto,
        $incentivegrafting,
        $incentiveseasional,
        $overtimepayment,
        $otherpayment,
        $Comments
    );

    if (mysqli_stmt_execute($insertStmt)) {
        $insertId = mysqli_insert_id($conn);
        mysqli_stmt_close($insertStmt);
        return [
            'status' => true,
            'action' => 'inserted',
            'sno' => $insertId
        ];
    } else {
        $error = mysqli_stmt_error($insertStmt);
        mysqli_stmt_close($insertStmt);
        return [
            'status' => false,
            'message' => $error
        ];
    }
}

?>