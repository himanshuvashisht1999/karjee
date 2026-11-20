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
            $EmployeeID,
            $companyid,
            $subcompanyid,
            $projected);

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
            // Try converting to integer
            $EmployeeID = (int)$employeeData['EmployeeID'];

            // Validate: after conversion, the value must still match a numeric pattern
            if (is_numeric($employeeData['EmployeeID'])) {
                // OK — valid numeric EmployeeID
            } else {
                // Not numeric — stop and skip saving
                $response = [
                    'status' => 'error',
                    'message' => 'Invalid EmployeeID: must be numeric. '.$employeeData['EmployeeID']
                ];
                echo json_encode($response);
                exit;
            }
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

        $stmt->bind_param("sssissssssssssssssssssssssss", $employeeData['companyid'], $employeeData['subcompanyid'], $employeeData['projectid'], $EmployeeID, addslashes($employeeData['TitleName']), addslashes($employeeData['FirstName']), addslashes($employeeData['MiddleName']), addslashes($employeeData['LastName']), addslashes($employeeData['FatherName']), addslashes($employeeData['MotherName']), addslashes($employeeData['MaritalStatus']), addslashes($employeeData['SpouseName']), addslashes($employeeData['Gender']), addslashes($employeeData['DateOfBirth']), addslashes($employeeData['Email']), addslashes($employeeData['MobileNo']), addslashes($employeeData['Nationality']), addslashes($employeeData['PassportNumber']), addslashes($employeeData['AddressLine1']), addslashes($employeeData['AddressLine2']), addslashes($employeeData['AddressLine3']), addslashes($employeeData['AddressLine4']), addslashes($employeeData['PostalCode']), addslashes($employeeData['CountryName']), addslashes($employeeData['PanNumber']), addslashes($employeeData['AadharNumber']), addslashes($employeeData['DateOfJoining']), addslashes($employeeData['addedby']));

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


function saveOrUpdateLabourEmployee($hrmconn, $employeeData) {
    // Check if the employee already exists (based on Email or Phone)
    $checkSql = "SELECT EmployeeID FROM hrm_LabourMaster WHERE (EmployeeID = ?) AND projectid = ?)";
    $stmt = $hrmconn->prepare($checkSql);
    if (!$stmt) {
        return [
            "success" => false,
            "message" => "Error preparing statement: " . $hrmconn->error
        ];
    }
    $stmt->bind_param("ss", $employeeData['EmployeeID'], $employeeData['projectid']);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->fetch_assoc();
    $stmt->close();
    if ($exists) {
        // Update existing employee
        $updateSql = "UPDATE hrm_LabourMaster SET TitleName = ?, FirstName = ?, MiddleName = ?, LastName = ?, FatherName = ?, MotherName = ?, MaritalStatus = ?, SpouseName = ?, Gender = ?, DateOfBirth = ?, Email = ?, MobileNo = ?, Nationality = ?, PassportNumber = ?, AddressLine1 = ?, AddressLine2 = ?, AddressLine3 = ?, AddressLine4 = ?, PostalCode = ?, CountryName = ?, PanNumber = ?, AadharNumber = ?, DateOfJoining = ?, addedby = ?, updated_at = CURRENT_TIMESTAMP
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
            $EmployeeID,
            $companyid,
            $subcompanyid,
            $projected);

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
        // Insert new employee
        $insertSql = "INSERT INTO hrm_LabourMaster (companyid, subcompanyid, projectid, TitleName, FirstName, MiddleName, LastName, FatherName, MotherName, MaritalStatus, SpouseName, Gender, DateOfBirth, Email, MobileNo, Nationality, PassportNumber, AddressLine1, AddressLine2, AddressLine3, AddressLine4, PostalCode, CountryName, PanNumber, AadharNumber, DateOfJoining, addedby) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $hrmconn->prepare($insertSql);
        if (!$stmt) {
            return [
                "success" => false,
                "message" => "Error preparing insert statement: " . $hrmconn->error
            ];
        }
        $stmt->bind_param("sssssssssssssssssssssssssss", $employeeData['companyid'], $employeeData['subcompanyid'], $employeeData['projectid'], addslashes($employeeData['TitleName']), addslashes($employeeData['FirstName']), addslashes($employeeData['MiddleName']), addslashes($employeeData['LastName']), addslashes($employeeData['FatherName']), addslashes($employeeData['MotherName']), addslashes($employeeData['MaritalStatus']), addslashes($employeeData['SpouseName']), addslashes($employeeData['Gender']), addslashes($employeeData['DateOfBirth']), addslashes($employeeData['Email']), addslashes($employeeData['MobileNo']), addslashes($employeeData['Nationality']), addslashes($employeeData['PassportNumber']), addslashes($employeeData['AddressLine1']), addslashes($employeeData['AddressLine2']), addslashes($employeeData['AddressLine3']), addslashes($employeeData['AddressLine4']), addslashes($employeeData['PostalCode']), addslashes($employeeData['CountryName']), addslashes($employeeData['PanNumber']), addslashes($employeeData['AadharNumber']), addslashes($employeeData['DateOfJoining']), addslashes($employeeData['addedby']));

        if ($stmt->execute()) {
            $employeeID = $hrmconn->insert_id;
            $stmt->close();
            return [
                "success" => true,
                "message" => "Employee registered successfully.",
                "employeeID" => $employeeID
            ];
        } else {
            return [
                "success" => false,
                "message" => "Error inserting data: " . $stmt->error
            ];
        }
    }
}

function insertOrUpdateEmployeeCalculatedSalary($hrmconn, $calSalarydata) {
    $projectId = trim($calSalarydata['ProjectID'] ?? '');
    $employeeId = trim($calSalarydata['EmployeeID'] ?? '');
    $salaryYear = trim($calSalarydata['SalaryYear'] ?? '');
    $salaryMonth = trim($calSalarydata['SalaryMonth'] ?? '');

    $salaryYearInt = intval($salaryYear);
    $salaryMonthInt = intval($salaryMonth);
    $salaryYearStr = (string)$salaryYearInt;
    $salaryMonthPad = str_pad((string)$salaryMonthInt, 2, "0", STR_PAD_LEFT);
    $salaryMonthRaw = (string)$salaryMonthInt;

    $gross = floatval($calSalarydata['GrossSalary'] ?? 0);
    $basic = floatval($calSalarydata['BasicSalary'] ?? 0);
    $hra = floatval($calSalarydata['HRA'] ?? 0);
    $conv = floatval($calSalarydata['Conveyance'] ?? 0);
    $allow = floatval($calSalarydata['Allowances'] ?? 0);
    $ot = floatval($calSalarydata['OvertimePay'] ?? 0);
    $arrear = floatval($calSalarydata['ArrearAmount'] ?? 0);
    $adv = floatval($calSalarydata['AdvanceDeduction'] ?? 0);
    $ded = floatval($calSalarydata['Deductions'] ?? 0);

    $rawNetSalary = floatval(($basic + $hra + $conv + $allow + $ot + $arrear) - ($ded + $adv));
    if (isset($calSalarydata['NetSalary'])) {
        $netSalaryVal = round(floatval($calSalarydata['NetSalary']));
        $roundOffVal = isset($calSalarydata['RoundOff']) 
            ? floatval($calSalarydata['RoundOff']) 
            : round($netSalaryVal - floatval($calSalarydata['NetSalary']), 2);
    } else {
        $netSalaryVal = round($rawNetSalary);
        $roundOffVal = round($netSalaryVal - $rawNetSalary, 2);
    }

    $companyId = $calSalarydata['CompanyID'] ?? '';
    $subCompanyId = $calSalarydata['SubCompanyID'] ?? '';
    $pDate = $calSalarydata['PaymentDate'] ?? '';
    $curr = $calSalarydata['CurrencyCode'] ?? 'INR';
    $pMethod = $calSalarydata['PaymentMethod'] ?? 'Bank Transfer';
    $comments = $calSalarydata['Comments'] ?? ('Salary for ' . $salaryYearStr . '-' . $salaryMonthPad);

    // Check if salary record exists for the given project, employee, year, and month
    $checkQuery = "SELECT SalaryID FROM hrm_EmployeeCalculatedSalary 
                   WHERE ProjectID = ? AND EmployeeID = ? 
                     AND (SalaryYear = ? OR SalaryYear = ?) 
                     AND (SalaryMonth = ? OR SalaryMonth = ?) 
                   ORDER BY SalaryID ASC";
    $stmt = $hrmconn->prepare($checkQuery);
    $salaryIds = [];
    if ($stmt) {
        $stmt->bind_param("ssssss", $projectId, $employeeId, $salaryYearStr, $salaryYear, $salaryMonthPad, $salaryMonthRaw);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $salaryIds[] = intval($row['SalaryID']);
        }
        $stmt->close();
    }

    if (count($salaryIds) > 0) {
        $SalaryID = $salaryIds[0];

        // Automatically delete any duplicate records for the same project, employee, year, and month
        if (count($salaryIds) > 1) {
            $duplicateIds = array_slice($salaryIds, 1);
            $dupIdList = implode(',', array_map('intval', $duplicateIds));
            @$hrmconn->query("DELETE FROM hrm_EmployeeCalculatedSalary WHERE SalaryID IN ($dupIdList)");
            @$hrmconn->query("DELETE FROM hrm_EmployeeDeductionTransaction WHERE DeductedFromSalaryId IN ($dupIdList)");
            @$hrmconn->query("DELETE FROM hrm_EmployeeEPFTransaction WHERE DeductedFromSalaryId IN ($dupIdList)");
            @$hrmconn->query("DELETE FROM hrm_EmployeeESICTransactions WHERE DeductedFromSalaryId IN ($dupIdList)");
        }

        // Update the primary salary record
        $updateQuery = "UPDATE hrm_EmployeeCalculatedSalary 
                        SET GrossSalary = ?, BasicSalary = ?, HRA = ?, Conveyance = ?, Allowances = ?, 
                            OvertimePay = ?, ArrearAmount = ?, AdvanceDeduction = ?, Deductions = ?, NetSalary = ?, RoundOff = ?,
                            PaymentDate = ?, CurrencyCode = ?, PaymentMethod = ?, 
                            Comments = ?, created_at = NOW()
                        WHERE SalaryID = ?";
        
        $updateStmt = $hrmconn->prepare($updateQuery);
        if ($updateStmt) {
            $updateStmt->bind_param("dddddddddddssssi", 
                $gross, $basic, $hra, $conv, 
                $allow, $ot, $arrear, $adv, 
                $ded, $netSalaryVal, $roundOffVal, $pDate, $curr, 
                $pMethod, $comments, $SalaryID
            );
            if ($updateStmt->execute()) {
                $updateStmt->close();
                @$hrmconn->query("UPDATE hrm_EmployeeCalculatedSalary SET NetSalary = {$netSalaryVal}, RoundOff = {$roundOffVal} WHERE SalaryID = '{$SalaryID}'");
                return json_encode([
                    "status" => "success",
                    "message" => "Salary updated successfully.",
                    "SalaryID" => $SalaryID
                ]);
            }
            $updateStmt->close();
        }

        // Fallback direct update query
        @$hrmconn->query("UPDATE hrm_EmployeeCalculatedSalary SET 
            GrossSalary = '{$gross}', BasicSalary = '{$basic}', HRA = '{$hra}', Conveyance = '{$conv}', 
            Allowances = '{$allow}', OvertimePay = '{$ot}', ArrearAmount = '{$arrear}', AdvanceDeduction = '{$adv}', 
            Deductions = '{$ded}', NetSalary = '{$netSalaryVal}', RoundOff = '{$roundOffVal}', Comments = '" . mysqli_real_escape_string($hrmconn, $comments) . "', created_at = NOW() 
            WHERE SalaryID = '{$SalaryID}'");

        return json_encode([
            "status" => "success",
            "message" => "Salary updated successfully.",
            "SalaryID" => $SalaryID
        ]);
    } else {
        // Insert new salary record
        $insertQuery = "INSERT INTO hrm_EmployeeCalculatedSalary 
                        (EmployeeID, CompanyID, SubCompanyID, ProjectID, SalaryYear, SalaryMonth, 
                        GrossSalary, BasicSalary, HRA, Conveyance, Allowances, OvertimePay, ArrearAmount, AdvanceDeduction, 
                        Deductions, NetSalary, RoundOff, PaymentDate, CurrencyCode, PaymentMethod, Comments, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $insertStmt = $hrmconn->prepare($insertQuery);
        if ($insertStmt) {
            $insertStmt->bind_param("ssssssdddddddddddssss", 
                $employeeId, $companyId, $subCompanyId, 
                $projectId, $salaryYearStr, $salaryMonthPad, 
                $gross, $basic, $hra, $conv, 
                $allow, $ot, $arrear, $adv, 
                $ded, $netSalaryVal, $roundOffVal, $pDate, $curr, 
                $pMethod, $comments
            );
            if ($insertStmt->execute()) {
                $newId = $insertStmt->insert_id;
                $insertStmt->close();
                return json_encode([
                    "status" => "success",
                    "message" => "Salary inserted successfully.",
                    "SalaryID" => $newId
                ]);
            }
            $insertStmt->close();
        }

        // Fallback direct insert query in case prepared statement encounters strict typing
        $directInsert = "INSERT INTO hrm_EmployeeCalculatedSalary 
            (EmployeeID, CompanyID, SubCompanyID, ProjectID, SalaryYear, SalaryMonth, 
            GrossSalary, BasicSalary, HRA, Conveyance, Allowances, OvertimePay, ArrearAmount, AdvanceDeduction, 
            Deductions, NetSalary, RoundOff, PaymentDate, CurrencyCode, PaymentMethod, Comments, created_at) 
            VALUES (
                '" . mysqli_real_escape_string($hrmconn, $employeeId) . "',
                '" . mysqli_real_escape_string($hrmconn, $companyId) . "',
                '" . mysqli_real_escape_string($hrmconn, $subCompanyId) . "',
                '" . mysqli_real_escape_string($hrmconn, $projectId) . "',
                '" . mysqli_real_escape_string($hrmconn, $salaryYearStr) . "',
                '" . mysqli_real_escape_string($hrmconn, $salaryMonthPad) . "',
                '{$gross}', '{$basic}', '{$hra}', '{$conv}', '{$allow}', '{$ot}', '{$arrear}', '{$adv}',
                '{$ded}', '{$netSalaryVal}', '{$roundOffVal}', '" . mysqli_real_escape_string($hrmconn, $pDate) . "',
                '" . mysqli_real_escape_string($hrmconn, $curr) . "', '" . mysqli_real_escape_string($hrmconn, $pMethod) . "',
                '" . mysqli_real_escape_string($hrmconn, $comments) . "', NOW()
            )";

        if (@$hrmconn->query($directInsert)) {
            $newId = $hrmconn->insert_id;
            return json_encode([
                "status" => "success",
                "message" => "Salary inserted successfully.",
                "SalaryID" => $newId
            ]);
        }

        return json_encode(["status" => "error", "message" => "Error inserting salary for employee " . $employeeId]);
    }
}

function insertOrUpdateEmployeeDeductionTransaction($hrmconn, $Dedtdata) {
    if (!is_array($Dedtdata) || empty($Dedtdata)) {
        return ["status" => "Success", "message" => "No deductions to process."];
    }
    $returnmsg = ["status" => "Success", "message" => "Deductions processed successfully."];
    foreach ($Dedtdata as $data) {
        $checkSql = "SELECT id FROM hrm_EmployeeDeductionsTransactions WHERE companyid = ? AND projectid = ? AND EmployeeId = ? AND DeductionId = ? AND SalaryYear = ? AND SalaryMonth = ?";    
        $checkStmt = $hrmconn->prepare($checkSql);
        if (!$checkStmt) continue;
        $checkStmt->bind_param("ssssss", $data['companyid'], $data['projectid'], $data['EmployeeId'], $data['DeductionId'], $data['SalaryYear'], $data['SalaryMonth']);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $recordId = $row['id'];
            // Update existing record using the `id` as the key
            $updateSql = "UPDATE hrm_EmployeeDeductionsTransactions SET DeductionName = ?, DeductionType = ?, DeductionBase = ?, DeductionValue = ?, DeductionAmount = ?, DeductedFromSalaryId = ?, processed_at = NOW() WHERE companyid = ? AND subcompanyid = ? AND projectid = ? AND EmployeeId = ? AND id = ?";
            $updateStmt = $hrmconn->prepare($updateSql);
            if ($updateStmt) {
                $updateStmt->bind_param("sssssssddii",
                    $data['DeductionName'], 
                    $data['DeductionType'], 
                    $data['DeductionBase'], 
                    $data['DeductionValue'], 
                    $data['DeductionAmount'], 
                    $data['DeductedFromSalaryId'], 
                    $data['companyid'],
                    $data['subcompanyid'],
                    $data['projectid'],
                    $data['EmployeeId'], 
                    $recordId);
                if ($updateStmt->execute()) {
                    $returnmsg = array("status"=>"Success", "message"=>"✅ Deduction transaction updated successfully!", "insertedid"=>$recordId);
                } else {
                    $returnmsg = array("status"=>"Error", "message"=>"❌ Error updating record: " . $updateStmt->error, "insertedid"=>"");
                }
                $updateStmt->close();
            }
        } else {
            // Insert new record
            $insertSql = "INSERT INTO hrm_EmployeeDeductionsTransactions 
                         (companyid, subcompanyid, projectid, EmployeeId, DeductionId, DeductionName, DeductionType, DeductionBase, 
                          DeductionValue, DeductionAmount, SalaryYear, SalaryMonth, DeductedFromSalaryId, processed_at, created_at) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $insertStmt = $hrmconn->prepare($insertSql);
            if ($insertStmt) {
                $insertStmt->bind_param("ssssssssddsss", 
                    $data['companyid'], 
                    $data['subcompanyid'], 
                    $data['projectid'], 
                    $data['EmployeeId'], 
                    $data['DeductionId'], 
                    $data['DeductionName'], 
                    $data['DeductionType'], 
                    $data['DeductionBase'], 
                    $data['DeductionValue'], 
                    $data['DeductionAmount'], 
                    $data['SalaryYear'], 
                    $data['SalaryMonth'], 
                    $data['DeductedFromSalaryId']);

                if ($insertStmt->execute()) {
                    $returnmsg = array("status"=>"Success", "message"=>"✅ Deduction transaction inserted successfully!", "insertedid"=>$insertStmt->insert_id);
                } else {
                    $returnmsg = array("status"=>"Error", "message"=>"❌ Error inserting record: " . $insertStmt->error, "insertedid"=>"");
                }
                $insertStmt->close();
            }
        }
        $checkStmt->close();
    }
    return $returnmsg;
}

function insertOrUpdateEmployeeEPF($hrmconn, $data) {
    // Prepare SQL to check if the record exists
    $checkSql = "SELECT COUNT(*) as count FROM hrm_EmployeeEPFTransactions 
                 WHERE companyid = ? AND projectid = ? AND EmployeeId = ? 
                 AND DeductionId = ? AND SalaryYear = ? 
                 AND SalaryMonth = ?";
    
    $stmt = $hrmconn->prepare($checkSql);
    if (!$stmt) return false;
    $stmt->bind_param("iiiiii", 
        $data['companyid'], $data['projectid'], $data['EmployeeId'], 
        $data['DeductionId'], $data['SalaryYear'], $data['SalaryMonth']
    );
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($result['count'] > 0) {
        // If record exists, update it
        $updateSql = "UPDATE hrm_EmployeeEPFTransactions 
                      SET DeductionName = ?, BasicSalary = ?, EmployeePF = ?, EPS = ?, EmployerPF = ?, 
                          EDLI = ?, PFAdmin = ?, EDLIAdmin = ?, DeductedFromSalaryId = ?, processed_at = NOW()
                      WHERE companyid = ? AND projectid = ? AND EmployeeId = ? 
                      AND DeductionId = ? AND SalaryYear = ? 
                      AND SalaryMonth = ?";
        
        $stmt = $hrmconn->prepare($updateSql);
        if (!$stmt) return false;
        $stmt->bind_param("sdddddddiiiiiii", 
            $data['DeductionName'], $data['BasicSalary'], $data['EmployeePF'], $data['EPS'], 
            $data['EmployerPF'], $data['EDLI'], $data['PFAdmin'], $data['EDLIAdmin'], 
            $data['DeductedFromSalaryId'], 
            $data['companyid'], $data['projectid'], $data['EmployeeId'], 
            $data['DeductionId'], $data['SalaryYear'], $data['SalaryMonth']
        );
    } else {
        // If record does not exist, insert a new record
        $insertSql = "INSERT INTO hrm_EmployeeEPFTransactions 
                      (companyid, subcompanyid, projectid, EmployeeId, DeductionId, DeductionName, 
                       BasicSalary, EmployeePF, EPS, EmployerPF, EDLI, PFAdmin, EDLIAdmin, 
                       SalaryYear, SalaryMonth, DeductedFromSalaryId, processed_at, created_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $hrmconn->prepare($insertSql);
        if (!$stmt) return false;
        $stmt->bind_param("iiiiisdddddddiis", 
            $data['companyid'], $data['subcompanyid'], $data['projectid'], 
            $data['EmployeeId'], $data['DeductionId'], $data['DeductionName'], 
            $data['BasicSalary'], $data['EmployeePF'], $data['EPS'], $data['EmployerPF'], 
            $data['EDLI'], $data['PFAdmin'], $data['EDLIAdmin'], 
            $data['SalaryYear'], $data['SalaryMonth'], 
            $data['DeductedFromSalaryId']
        );
    }
    // Execute and return success status
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

function insertOrUpdateEmployeeESIC($hrmconn, $data) {
    // Prepare SQL to check if the record exists
    $checkSql = "SELECT id FROM hrm_EmployeeESICTransactions 
                 WHERE companyid = ? AND subcompanyid = ? AND projectid = ? 
                 AND EmployeeId = ? AND DeductionId = ? 
                 AND SalaryYear = ? 
                 AND SalaryMonth = ? LIMIT 1";
    
    $stmt = $hrmconn->prepare($checkSql);
    if (!$stmt) return false;
    $stmt->bind_param("iiiiiii", 
        $data['companyid'], $data['subcompanyid'], $data['projectid'], 
        $data['EmployeeId'], $data['DeductionId'], 
        $data['SalaryYear'], $data['SalaryMonth']
    );
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // If record exists, update it
        $updateSql = "UPDATE hrm_EmployeeESICTransactions 
                      SET DeductionName = ?, GrossSalary = ?, EmployeeESIC = ?, EmployerESIC = ?, 
                          DeductedFromSalaryId = ?, processed_at = NOW()
                      WHERE companyid = ? AND subcompanyid = ? AND projectid = ? AND EmployeeId = ? 
                      AND DeductionId = ? AND SalaryYear = ? 
                      AND SalaryMonth = ?";
        
        $stmt = $hrmconn->prepare($updateSql);
        if (!$stmt) return false;
        $stmt->bind_param("sddddiiiiiii", 
            $data['DeductionName'], $data['GrossSalary'], $data['EmployeeESIC'], $data['EmployerESIC'], 
            $data['DeductedFromSalaryId'], 
            $data['companyid'], $data['subcompanyid'], $data['projectid'], $data['EmployeeId'], 
            $data['DeductionId'], $data['SalaryYear'], $data['SalaryMonth']
        );
    } else {
        // If record does not exist, insert a new record
        $insertSql = "INSERT INTO hrm_EmployeeESICTransactions 
                      (companyid, subcompanyid, projectid, EmployeeId, DeductionId, DeductionName, 
                       GrossSalary, EmployeeESIC, EmployerESIC, 
                       SalaryYear, SalaryMonth, DeductedFromSalaryId, processed_at, created_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $hrmconn->prepare($insertSql);
        if (!$stmt) return false;
        $stmt->bind_param("iiiiisdddiii", 
            $data['companyid'], $data['subcompanyid'], $data['projectid'], 
            $data['EmployeeId'], $data['DeductionId'], $data['DeductionName'], 
            $data['GrossSalary'], $data['EmployeeESIC'], $data['EmployerESIC'], 
            $data['SalaryYear'], $data['SalaryMonth'], 
            $data['DeductedFromSalaryId']
        );
    }    
    // Execute and return success status
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

function insertOrUpdateEmployeeDocument($hrmconn, $companyid, $subcompanyid, $projectid, $employeeID, $documentType, $documentPath) {
    $checkSql = "SELECT * FROM hrm_EmployeeDocuments WHERE companyid = ? AND projectid =? AND EmployeeID = ? AND DocumentType = ?";
    if ($checkStmt = $hrmconn->prepare($checkSql)) {
        $checkStmt->bind_param("ssis", $companyid,$projectid,$employeeID, $documentType);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        if ($result->num_rows > 0) {
            // Update the existing record's DocumentPath
            $updateSql = "UPDATE hrm_EmployeeDocuments SET tedit = ? WHERE companyid =? AND projectid =? and EmployeeID = ? AND DocumentType = ?";
            $tedit="remove old and add new on ".date('Y-m-d h:i:s');
            if ($updateStmt = $hrmconn->prepare($updateSql)) {
                $updateStmt->bind_param("sssss", $tedit, $companyid, $projectid, $employeeID, $documentType);
                if ($updateStmt->execute()) {
                    $updateStmt->close();
                } else {
                    $updateStmt->close();
                    return "Error updating existing document: " . $updateStmt->error;
                }
            } else {
                return "Error in preparing update statement: " . $hrmconn->error;
            }

            // Insert a new entry for the updated file
            $insertSql = "INSERT INTO hrm_EmployeeDocuments (companyid,subcompanyid,projectid,EmployeeID, DocumentType, DocumentPath) VALUES (?, ?, ?, ?, ?, ?)";

            if ($insertStmt = $hrmconn->prepare($insertSql)) {
                $insertStmt->bind_param("ssssss", $companyid, $subcompanyid, $projectid, $employeeID, addslashes($documentType), addslashes($documentPath));
                if ($insertStmt->execute()) {
                    $insertStmt->close();
                    return "Existing document updated and new entry added successfully.";
                } else {
                    $insertStmt->close();
                    return "Error inserting new document entry: " . $insertStmt->error;
                }
            } else {
                return "Error in preparing insert statement: " . $hrmconn->error;
            }
        } else {
            // No existing record, insert a new entry
            $insertSql = "INSERT INTO hrm_EmployeeDocuments (companyid,subcompanyid,projectid,EmployeeID, DocumentType, DocumentPath) VALUES (?, ?, ?, ?, ?, ?)";
            if ($insertStmt = $hrmconn->prepare($insertSql)) {
                $insertStmt->bind_param("ssssss", $companyid, $subcompanyid, $projectid, $employeeID, addslashes($documentType), addslashes($documentPath));
                if ($insertStmt->execute()) {
                    $insertStmt->close();
                    return "New document inserted successfully.";
                } else {
                    $insertStmt->close();
                    return "Error inserting document: " . $insertStmt->error;
                }
            } else {
                return "Error in preparing insert statement: " . $hrmconn->error;
            }
        }

        $checkStmt->close();
    } else {
        return "Error in preparing check statement: " . $hrmconn->error;
    }
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
    $checkStmt->bind_param("isssii", $employeeid, $companyid, $subcompanyid, $projectid, $attendanceyear, $attendancemonth);
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

        $insertStmt->bind_param("isssiiidddddddddsi",
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
    if (isset($data['EmployeeID'])) {
        $data = [$data];
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

        if (!empty($empddct) && is_array($empddct)) {
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

        // Fetch DateOfJoining if affectedfrom is empty
        $affectedFrom = $row['affectedfrom'] ?? $row['DateOfJoining'] ?? null;
        if (empty($affectedFrom) || $affectedFrom == '0000-00-00') {
            $dojRes = $hrmconn->query("SELECT DateOfJoining FROM hrm_EmployeesMaster WHERE EmployeeID = '$employeeID' LIMIT 1");
            if ($dojRes && $dojRow = $dojRes->fetch_assoc()) {
                $affectedFrom = $dojRow['DateOfJoining'];
            }
        }
        if (empty($affectedFrom) || $affectedFrom == '0000-00-00') {
            $affectedFrom = date('Y-m-d');
        }

        // Check if an active record exists (where validtilldate is NULL, empty, or '0000-00-00')
        $chkActive = $hrmconn->query(
            "SELECT SalaryID FROM hrm_EmployeeSalary 
             WHERE EmployeeID = '$employeeID' 
               AND (validtilldate IS NULL OR validtilldate = '' OR validtilldate = '0000-00-00') 
             ORDER BY SalaryID DESC LIMIT 1"
        );

        $netSalaryVal = floatval($basicSalary + $hra + $conveyanceAllowance + $specialAllowance);
        if ($netSalaryVal <= 0) {
            $netSalaryVal = floatval($grossSalary);
        }

        if ($chkActive && $chkRow = $chkActive->fetch_assoc()) {
            $activeSalaryID = intval($chkRow['SalaryID']);
            $sql = "UPDATE hrm_EmployeeSalary SET 
                        companyid = ?,
                        subcompanyid = ?,
                        projectid = ?,
                        CTCY = ?,
                        CTC = ?,
                        GrossSalary = ?,
                        BasicSalary = ?,
                        HRA = ?,
                        ConveyanceAllowance = ?,
                        SpecialAllowance = ?,
                        NetSalary = ?,
                        CurrencyCode = ?,
                        affectedfrom = IF(affectedfrom IS NULL OR affectedfrom = '' OR affectedfrom = '0000-00-00', ?, affectedfrom)
                    WHERE SalaryID = ?";
            $stmt = $hrmconn->prepare($sql);
            if ($stmt) {
                // Binding: 3 ints, 8 floats, 2 strings, 1 int = iiiddddddddssi
                $stmt->bind_param(
                    "iiiddddddddssi",
                    $companyid,
                    $subcompanyid,
                    $projectid,
                    $ctcy,
                    $ctc,
                    $grossSalary,
                    $basicSalary,
                    $hra,
                    $conveyanceAllowance,
                    $specialAllowance,
                    $netSalaryVal,
                    $currencyCode,
                    $affectedFrom,
                    $activeSalaryID
                );

                if (!$stmt->execute()) {
                    $errors[] = "Update failed for SalaryID $activeSalaryID: " . $stmt->error;
                    $success = false;
                }
                $stmt->close();
            } else {
                $errors[] = "Update statement prep failed: " . $hrmconn->error;
                $success = false;
            }
        } else {
            // Insert new salary record if no active record exists
            $sql = "INSERT INTO hrm_EmployeeSalary 
                (companyid, subcompanyid, projectid, EmployeeID, CTCY, CTC, GrossSalary, BasicSalary, HRA, ConveyanceAllowance, SpecialAllowance, NetSalary, CurrencyCode, affectedfrom)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $hrmconn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param(
                    "iiiiddddddddss",
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
                    $netSalaryVal,
                    $currencyCode,
                    $affectedFrom
                );

                if (!$stmt->execute()) {
                    $errors[] = "Insert failed for EmployeeID $employeeID: " . $stmt->error;
                    $success = false;
                }
                $stmt->close();
            } else {
                $errors[] = "Insert statement prep failed: " . $hrmconn->error;
                $success = false;
            }
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
    if (isset($data['ComponentId']) || isset($data['EmployeeId'])) {
        $data = [$data];
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
            "iiiiisssdss", // 5 ints, 3 strings, 1 float, 2 strings
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
    if (isset($data['DeductionId'])) {
        $data = [$data];
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

function insertOrUpdateBankDetails($hrmconn, $companyid, $subcompanyid, $projectid, $employeeID, $bankName, $accountNumber, $ifscCode, $swiftCode, $iban, $sortCode, $currencyCode, $cancelChequePath) {
    // Check if a record already exists with the given EmployeeID and AccountNumber
    $checkSql = "SELECT * FROM hrm_EmployeeBankDetails WHERE companyid =? AND projectid =? AND EmployeeID = ? AND AccountNumber = ?";
    if ($checkStmt = $hrmconn->prepare($checkSql)) {
        $checkStmt->bind_param("ssis", $companyid,$projectid,$employeeID, $accountNumber);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        if ($result->num_rows > 0) {
            // Update the existing record
            $updateSql = "UPDATE hrm_EmployeeBankDetails SET tedit = 'update new' WHERE companyid =? AND projectid =? AND EmployeeID = ? and AccountNumber = ?";

            if ($updateStmt = $hrmconn->prepare($updateSql)) {
                $updateStmt->bind_param("ss", $companyid,$projectid,$employeeID, $accountNumber);
                if ($updateStmt->execute()) {
                    $updateStmt->close();
                } else {
                    $updateStmt->close();
                }
            }

            // Insert a new entry for the updated bank details
            $insertSql = "INSERT INTO hrm_EmployeeBankDetails (companyid,subcompanyid,projectid,EmployeeID, BankName, AccountNumber, IFSCCode, SWIFTCode, IBAN, SortCode, CurrencyCode, CancelChequePath) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            if ($insertStmt = $hrmconn->prepare($insertSql)) {
                $insertStmt->bind_param("sssissssssss", $companyid,$subcompanyid,$projectid,$employeeID, addslashes($bankName), addslashes($accountNumber), addslashes($ifscCode), addslashes($swiftCode), addslashes($iban), addslashes($sortCode), addslashes($currencyCode), addslashes($cancelChequePath));
                if ($insertStmt->execute()) {
                    return "Existing bank details updated and new entry added successfully.";
                    $insertStmt->close();
                } else {
                    return "Error inserting new bank details: " . $insertStmt->error;
                    $insertStmt->close();
                }
            } else {
                return "Error in preparing insert statement: " . $hrmconn->error;
            }
        } else {
            // No existing record, insert a new entry
            $insertSql = "INSERT INTO hrm_EmployeeBankDetails (companyid,subcompanyid,projectid,EmployeeID, BankName, AccountNumber, IFSCCode, SWIFTCode, IBAN, SortCode, CurrencyCode, CancelChequePath) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            if ($insertStmt = $hrmconn->prepare($insertSql)) {
                $insertStmt->bind_param("sssissssssss", $companyid,$subcompanyid,$projectid,$employeeID, addslashes($bankName), addslashes($accountNumber), addslashes($ifscCode), addslashes($swiftCode), addslashes($iban), addslashes($sortCode), addslashes($currencyCode), addslashes($cancelChequePath));
                if ($insertStmt->execute()) {
                    return "New bank details inserted successfully.";
                    $insertStmt->close();
                } else {
                    return "Error inserting bank details: " . $insertStmt->error;
                    $insertStmt->close();
                }
            } else {
                return "Error in preparing insert statement: " . $hrmconn->error;
            }
        }

        $checkStmt->close();
    } else {
        return "Error in preparing check statement: " . $hrmconn->error;
    }
}

function insertOrUpdateEmployeeContactPersonDetails($hrmconn,$companyid,$subcompanyid,$projectid,$employeeId,$contactName,$relationship,$phoneNumber,$emailAddress,$postalCode,$address,$note,$isPrimaryContact) {
    // Check if the record already exists
    $checkQuery = "SELECT COUNT(*) as record_count FROM hrm_EmployeeContactPersonDetails WHERE companyid =? AND subcompanyid =? AND projectid =? AND EmployeeId = ? AND ContactName = ?";
    $stmt = $hrmconn->prepare($checkQuery);
    $stmt->bind_param("iiiis", $companyid,$subcompanyid,$projectid,$employeeId,$contactName);
    $stmt->execute();
    $result = $stmt->get_result();
    $recordExists = $result->fetch_assoc()['record_count'] > 0;
    $stmt->close();
    if ($recordExists) {
        $updateQuery = "UPDATE hrm_EmployeeContactPersonDetails SET Relationship = ?, PhoneNumber = ?, EmailAddress = ?, PostalCode = ?, Address = ?, Note = ?, IsPrimaryContact = ?, UpdatedAt = CURRENT_TIMESTAMP WHERE EmployeeId = ? AND companyid = ? AND subcompanyid = ? AND projectid = ?";
        $stmt = $hrmconn->prepare($updateQuery);
        $stmt->bind_param("ssssssssiii", $relationship, $phoneNumber, $emailAddress, $postalCode, addslashes($address), addslashes($note), $isPrimaryContact, $employeeId, addslashes($companyid), addslashes($subcompanyid), addslashes($projectid));
        $stmt->execute();
        // $stmt->close();
        return $stmt->affected_rows > 0 ? "Record updated successfully." : "No changes made.";
    } else {
        // Insert a new record
        $insertQuery = "INSERT INTO hrm_EmployeeContactPersonDetails (companyid,subcompanyid,projectid,EmployeeId, ContactName, Relationship, PhoneNumber, EmailAddress, PostalCode, Address, Note, IsPrimaryContact, CreatedAt, UpdatedAt) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
        $stmt = $hrmconn->prepare($insertQuery);
        $stmt->bind_param("sssissssssis", $companyid,$subcompanyid,$projectid,$employeeId, addslashes($contactName), $relationship, $phoneNumber, $emailAddress, $postalCode, addslashes($address), addslashes($note), $isPrimaryContact);
        $stmt->execute();
        // $stmt->close();

        return $stmt->affected_rows > 0 ? "Record inserted successfully." : "Failed to insert record.";
    }
}

function insertOrUpdateEmployeeLoginDetails($hrmconn, $authconn, $companyid, $subcompanyid, $projectid, $EmployeeID, $loginid, $loginname, $loginpassword, $phoneNumber, $emailAddress) {
    /* ------------------------------------------------------
       1. CHECK USER EXISTS
    ------------------------------------------------------ */
    $checkUser = "SELECT loginid FROM login_user_master 
                  WHERE loginid = ? AND loginroleid = '99' LIMIT 1";

    $stmt = $authconn->prepare($checkUser);
    if (!$stmt) {
        return ["status" => "error", "msg" => $authconn->error];
    }

    $stmt->bind_param("s", $loginid);
    $stmt->execute();
    $userExists = $stmt->get_result()->num_rows > 0;
    $stmt->close();


    /* ------------------------------------------------------
       2. CHECK AUTH EXISTS
    ------------------------------------------------------ */
    $checkAuth = "SELECT sno FROM login_authorized_projects
                  WHERE companyid = ? AND subcompanyid = ? 
                  AND projectid = ? AND loginid = ? LIMIT 1";

    $stmtA = $authconn->prepare($checkAuth);
    if (!$stmtA) {
        return ["status" => "error", "msg" => $authconn->error];
    }

    $stmtA->bind_param("iiis", $companyid, $subcompanyid, $projectid, $loginid);
    $stmtA->execute();
    $authExists = $stmtA->get_result()->num_rows > 0;
    $stmtA->close();


    /* ------------------------------------------------------
       3. INSERT/UPDATE login_user_master
    ------------------------------------------------------ */
    if ($userExists) {

        $updateSql = "UPDATE login_user_master 
                      SET loginname = ?, loginpassword = ?, mobileno = ?, emailid = ?
                      WHERE loginid = ? AND loginroleid='99'";

        $stmt2 = $authconn->prepare($updateSql);
        if (!$stmt2) {
            return ["status" => "error", "msg" => $authconn->error];
        }

        $stmt2->bind_param("sssss", $loginname, $loginpassword, $phoneNumber, $emailAddress, $loginid);
        $stmt2->execute();
        $stmt2->close();

        $finalMessage = "Employee login updated successfully.";

    } else {

        $insertSql = "INSERT INTO login_user_master 
                      (loginname, loginid, loginpassword, loginroleid, loginrolename, mobileno, emailid, isActive)
                      VALUES (?, ?, ?, '99', 'Employee', ?, ?, 'yes')";

        $stmt3 = $authconn->prepare($insertSql);
        if (!$stmt3) {
            return ["status" => "error", "msg" => $authconn->error];
        }


        $stmt3->bind_param("sssss", $loginname, $loginid, $loginpassword, $phoneNumber, $emailAddress);
        $stmt3->execute();
        $stmt3->close();

        $hrmconn->query("UPDATE hrm_EmployeesMaster set loginid='".$loginid."' where EmployeeID='".$EmployeeID."'");

        $finalMessage = "Employee login created successfully.";
    }


    /* ------------------------------------------------------
       4. INSERT/UPDATE AUTHORIZATION TABLE
    ------------------------------------------------------ */
    if ($authExists) {

        $updateAuth = "UPDATE login_authorized_projects 
                       SET status='active' 
                       WHERE companyid=? AND subcompanyid=? AND projectid=? AND loginid=?";

        $stmt4 = $authconn->prepare($updateAuth);
        if (!$stmt4) {
            return ["status" => "error", "msg" => $authconn->error];
        }

        $stmt4->bind_param("iiis", $companyid, $subcompanyid, $projectid, $loginid);
        $stmt4->execute();
        $stmt4->close();

    } else {

        $insertAuth = "INSERT INTO login_authorized_projects 
                       (companyid, subcompanyid, projectid, loginid, status)
                       VALUES (?, ?, ?, ?, 'active')";

        $stmt5 = $authconn->prepare($insertAuth);
        if (!$stmt5) {
            return ["status" => "error", "msg" => $authconn->error];
        }

        $stmt5->bind_param("iiis", $companyid, $subcompanyid, $projectid, $loginid);
        $stmt5->execute();
        $stmt5->close();
    }


    /* ------------------------------------------------------
       5. FINAL RETURN
    ------------------------------------------------------ */
    return [
        "status" => "success",
        "msg" => $finalMessage
    ];
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
                "iisdisssii",
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
                "iisdissss",
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

function insertEmployeeAdvanceSummary($hrmconn,$AdvID,$projectid,$EmployeeID,$TransactionDate,$DebitAmount,$CreditAmount,$BalanceAmount,$ReferenceNo,$Description,$TransactionType = 'Debit',$PaymentType = 'AdjustSalary',$TransactionNo = null,$MonthYearSalary = null,$UploadFlag = null,$SalaryID = null)
{
   
        // ✅ Normalize input values
        $DebitAmount   = floatval($DebitAmount ?: 0.00);
        $CreditAmount  = floatval($CreditAmount ?: 0.00);
        $BalanceAmount = floatval($BalanceAmount ?: 0.00);
        $TransactionDate = $TransactionDate ?: date('Y-m-d');
        if (empty($MonthYearSalary)) $MonthYearSalary = date('Y-m');

        // ✅ Check if record already exists
        $checkSQL = "
            SELECT id FROM hrm_EmployeeAdvanceSummary 
            WHERE projectid = ? AND EmployeeID = ? AND MonthYearSalary = ?
        ";
        $checkStmt = $hrmconn->prepare($checkSQL);
        if (!$checkStmt) {
            throw new Exception("Check prepare failed: " . $hrmconn->error);
        }

        $checkStmt->bind_param("sss", $projectid, $EmployeeID, $MonthYearSalary);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            // 🔁 Record exists → update it
            $checkStmt->bind_result($existingId);
            $checkStmt->fetch();
            $checkStmt->close();

            $updateSQL = "
                UPDATE hrm_EmployeeAdvanceSummary
                SET 
                    projectid = ?, 
                    EmployeeID = ?, 
                    TransactionDate = ?, 
                    DebitAmount = ?, 
                    CreditAmount = ?, 
                    BalanceAmount = ?, 
                    Description = ?, 
                    TransactionType = ?, 
                    PaymentType = ?, 
                    MonthYearSalary = ?, 
                    UploadFlag = ?, 
                    SalaryID = ?
                WHERE id = ?
            ";
            $updateStmt = $hrmconn->prepare($updateSQL);
            if (!$updateStmt) {
                throw new Exception("Update prepare failed: " . $hrmconn->error);
            }

            $updateStmt->bind_param(
                "sissdddssssii",
                $projectid,
                $EmployeeID,
                $TransactionDate,
                $DebitAmount,
                $CreditAmount,
                $BalanceAmount,
                $Description,
                $TransactionType,
                $PaymentType,
                $MonthYearSalary,
                $UploadFlag,
                $SalaryID,
                $existingId
            );

            if (!$updateStmt->execute()) {
                throw new Exception("Update failed: " . $updateStmt->error);
            }

            $updateStmt->close();
            return [
                "status" => "updated",
                "message" => "Existing record updated successfully",
                "record_id" => $existingId
            ];
        }

        $checkStmt->close();

        // ➕ Insert new record
        $insertSQL = "
            INSERT INTO hrm_EmployeeAdvanceSummary 
            (AdvID, projectid, EmployeeID, TransactionDate, DebitAmount, CreditAmount, BalanceAmount, ReferenceNo, Description, TransactionType, PaymentType, TransactionNo, MonthYearSalary, UploadFlag, SalaryID)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        $insertStmt = $hrmconn->prepare($insertSQL);
        if (!$insertStmt) {
            throw new Exception("Insert prepare failed: " . $hrmconn->error);
        }

        $insertStmt->bind_param(
            "issssddssssssss",
            $AdvID,
            $projectid,
            $EmployeeID,
            $TransactionDate,
            $DebitAmount,
            $CreditAmount,
            $BalanceAmount,
            $ReferenceNo,
            $Description,
            $TransactionType,
            $PaymentType,
            $TransactionNo,
            $MonthYearSalary,
            $UploadFlag,
            $SalaryID
        );

        if (!$insertStmt->execute()) {
            throw new Exception("Insert failed: " . $insertStmt->error);
        }

        $insertId = $insertStmt->insert_id;
        $insertStmt->close();

        return [
            "status" => "inserted",
            "message" => "Record inserted successfully",
            "insert_id" => $insertId
        ];

    
}

function saveEmployeeWeekOff($hrmconn, $ProjectID, $EmployeeID, $WeekOffDayNo = [], $WeekOffdayName = [], $Remarks = '', $Status = 'Active', $created_by = '')
{
    if (empty($EmployeeID) || empty($ProjectID)) {
        return ['status' => 'error', 'message' => 'EmployeeID and ProjectID are required'];
    }

    if (!is_array($WeekOffDayNo) || !is_array($WeekOffdayName) || count($WeekOffDayNo) !== count($WeekOffdayName)) {
        return ['status' => 'error', 'message' => 'Invalid week off data'];
    }

    $updatedCount = 0;
    $insertedCount = 0;

    foreach ($WeekOffDayNo as $index => $dayNo) {
        $dayName = $WeekOffdayName[$index] ?? '';

        // Check if record already exists
        $sqlCheck = "SELECT WeekOffID FROM hrm_EmployeeWeekOff WHERE EmployeeID = ? AND ProjectID = ? AND WeekOffDayNo = ?";
        $stmtCheck = $hrmconn->prepare($sqlCheck);
        if (!$stmtCheck) {
            return ['status' => 'error', 'message' => 'Prepare failed (check): ' . $hrmconn->error];
        }
        $stmtCheck->bind_param("isi", $EmployeeID, $ProjectID, $dayNo);
        $stmtCheck->execute();
        $result = $stmtCheck->get_result();
        $exists = $result->num_rows > 0;
        $stmtCheck->close();

        if ($exists) {
            // Update existing record
            $sqlUpdate = "UPDATE hrm_EmployeeWeekOff 
                          SET WeekOffdayName = ?, Remarks = ?, Status = ?, created_by = ?
                          WHERE EmployeeID = ? AND ProjectID = ? AND WeekOffDayNo = ?";
            $stmtUpdate = $hrmconn->prepare($sqlUpdate);
            if (!$stmtUpdate) {
                return ['status' => 'error', 'message' => 'Prepare failed (update): ' . $hrmconn->error];
            }
            $stmtUpdate->bind_param("ssssisi", $dayName, $Remarks, $Status, $created_by, $EmployeeID, $ProjectID, $dayNo);
            if ($stmtUpdate->execute()) {
                $updatedCount++;
            } else {
                $stmtUpdate->close();
                return ['status' => 'error', 'message' => 'Update failed: ' . $stmtUpdate->error];
            }
            $stmtUpdate->close();
        } else {
            // Insert new record
            $sqlInsert = "INSERT INTO hrm_EmployeeWeekOff (EmployeeID, ProjectID, WeekOffDayNo, WeekOffdayName, Remarks, Status, created_by)
                          VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmtInsert = $hrmconn->prepare($sqlInsert);
            if (!$stmtInsert) {
                return ['status' => 'error', 'message' => 'Prepare failed (insert): ' . $hrmconn->error];
            }
            $stmtInsert->bind_param("isissss", $EmployeeID, $ProjectID, $dayNo, $dayName, $Remarks, $Status, $created_by);
            if ($stmtInsert->execute()) {
                $insertedCount++;
            } else {
                $stmtInsert->close();
                return ['status' => 'error', 'message' => 'Insert failed: ' . $stmtInsert->error];
            }
            $stmtInsert->close();
        }
    }

    return [
        'status' => 'success',
        'message' => "Employee week off data saved successfully. Updated: {$updatedCount}, Inserted: {$insertedCount}."
    ];
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
    $stmt->bind_param("isss", $calSalarydata['EmployeeID'], $calSalarydata['dateFrom'], $calSalarydata['dateTo'], $LocationId);
    $stmt->execute();
    $stmt->store_result();    

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($SalaryID);
        $stmt->fetch();

        $updateQuery = "UPDATE hrm_LabourCalculatedSalary 
        SET SalaryYear = ?, SalaryMonth = ?, DateFrom = ?, DateTo = ?, 
            PayPerDay = ?, incentivegrafting = ?, incentiveseasional = ?, overtimepayment = ?, otherpayment = ?, basiclabourpayment = ?, NetSalary = ?, Comments = ?, LocationId = ?, created_at = NOW()
        WHERE EmployeeID = ? AND DateFrom = ? AND DateTo = ?";
        
        $updateStmt = $hrmconn->prepare($updateQuery);
        $updateStmt->bind_param(
            "iissdddddddsssss",  // 15 chars
            $calSalarydata['SalaryYear'],   // i
            $calSalarydata['SalaryMonth'],  // i
            $calSalarydata['dateFrom'],     // s
            $calSalarydata['dateTo'],       // s
            $calSalarydata['PayPerDay'],    // d
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
         DateFrom, DateTo, PayPerDay, incentivegrafting, incentiveseasional, overtimepayment, otherpayment, basiclabourpayment, NetSalary, Comments, LocationId, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        
        $insertStmt = $hrmconn->prepare($insertQuery);
        $insertStmt->bind_param(
            "ssssiissdddddddss",  // 16 chars
            $calSalarydata['EmployeeID'],   // i
            $calSalarydata['CompanyID'],    // s
            $calSalarydata['SubCompanyID'], // s
            $calSalarydata['ProjectID'],    // s
            $calSalarydata['SalaryYear'],   // i
            $calSalarydata['SalaryMonth'],  // i
            $calSalarydata['dateFrom'],     // s
            $calSalarydata['dateTo'],       // s
            $calSalarydata['PayPerDay'],
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
            "ssiss",
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
            "ssisss",
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


function saveAttendanceLocationExemption($conn,$ProjectID,$EmployeeID,$LocationId,$AttendanceDate,$Comments,$loginname) {

    $ProjectID     = trim($ProjectID);
    $EmployeeID    = trim($EmployeeID);
    $LocationId    = trim($LocationId);
    $AttendanceDate = trim($AttendanceDate);
    $Comments      = trim($Comments);

    /* ==============================
       CHECK EXISTING
    ============================== */
    $check_sql = "
        SELECT sno
        FROM hrm_EmployeeAttLocationExemption
        WHERE EmployeeID = ?
          AND AttendanceDate = ?
          AND ProjectID = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($check_sql);
    if (!$stmt) {
        return ["status" => "error", "msg" => $conn->error];
    }

    $stmt->bind_param("sss", $EmployeeID, $AttendanceDate, $ProjectID);
    $stmt->execute();
    $result = $stmt->get_result();

    /* ==============================
       UPDATE
    ============================== */
    if ($result->num_rows > 0) {

        $row = $result->fetch_assoc();
        $sno = (int)$row['sno'];

        $update_sql = "
            UPDATE hrm_EmployeeAttLocationExemption
            SET 
                LocationId = ?,
                Comments = ?,
                created_by = ?,
                created_at = NOW()
            WHERE sno = ?
        ";

        $stmt2 = $conn->prepare($update_sql);
        if (!$stmt2) {
            return ["status" => "error", "msg" => $conn->error];
        }

        $stmt2->bind_param("sssi", $LocationId, $Comments, $loginname, $sno);
        $stmt2->execute();

        // 🔥 CRITICAL CHECK
        if ($stmt2->affected_rows > 0) {
            return [
                "status" => "success",
                "msg" => "Exemption updated successfully"
            ];
        }

        return [
            "status" => "warning",
            "msg" => "No changes detected (data already same)"
        ];
    }

    /* ==============================
       INSERT
    ============================== */
    $insert_sql = "
        INSERT INTO hrm_EmployeeAttLocationExemption
        (
            ProjectID,
            EmployeeID,
            LocationId,
            AttendanceDate,
            Comments,
            created_by,
            created_at
        )
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ";

    $stmt3 = $conn->prepare($insert_sql);
    if (!$stmt3) {
        return ["status" => "error", "msg" => $conn->error];
    }

    $stmt3->bind_param(
        "ssssss",
        $ProjectID,
        $EmployeeID,
        $LocationId,
        $AttendanceDate,
        $Comments,
        $loginname
    );

    $stmt3->execute();

    if ($stmt3->affected_rows > 0) {
        return [
            "status" => "success",
            "msg" => "Exemption added successfully"
        ];
    }

    return [
        "status" => "error",
        "msg" => "Insert failed"
    ];
}

?>