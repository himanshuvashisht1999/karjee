<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(1);
require('../../connection.php');
require('../functions/hrm_FunctionMaster.php');
require('../functions/hrm_insertupdatesalary_functions.php');
require('../functions/comanphp_functions.php');
require('../functions/admin_functionmaster.php');
require('../functions/hrm_loginFunctions.php');
$action = $_GET['action'];
if ($action == "EmployeeRegistration") {
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;
    $addedby = $_POST['addedby'] ?? null;
    $EmployeeID = $_POST['EmployeeID'] ?? null;

    // Get POST data
    $TitleName = $_POST['TitleName'] ?? null;
    $FirstName = $_POST['FirstName'] ?? null;
    $MiddleName = $_POST['MiddleName'] ?? null;
    $LastName = $_POST['LastName'] ?? null;
    $DateOfBirth = $_POST['DateOfBirth'] ?? null;
    $GenderName = $_POST['GenderName'] ?? null;
    $MaritalStatus = $_POST['MaritalStatus'] ?? null;
    $SpouseName = $_POST['SpouseName'] ?? null;
    $FatherName = $_POST['FatherName'] ?? null;
    $MotherName = $_POST['MotherName'] ?? null;
    $Nationality = $_POST['Nationality'] ?? null;
    $PassportNumber = $_POST['PassportNumber'] ?? null;
    $PanNumber = $_POST['PanNumber'] ?? null;
    $AadharNumber = $_POST['AadharNumber'] ?? null;

    $EmailAddress = $_POST['EmailAddress'] ?? null;
    $MobileNo = $_POST['MobileNumber'] ?? null;
    $AddressLine1 = $_POST['AddressLine1'] ?? null;
    $AddressLine2 = $_POST['AddressLine2'] ?? null;
    $AddressLine3 = $_POST['AddressLine3'] ?? null;
    $AddressLine4 = $_POST['AddressLine4'] ?? null;
    $PostalCode = $_POST['PostalCode'] ?? null;
    $CountryName = $_POST['CountryName'] ?? null;

    // Employee Bank Details 
    $BankName = $_POST['BankName'] ?? null;
    $AccountNumber = $_POST['AccountNumber'] ?? null;
    $IFSCCode = $_POST['IFSCCode'] ?? null;
    $SwiftCode = $_POST['SwiftCode'] ?? null;
    $IbanNumber = $_POST['IbanNumber'] ?? null;
    $SortCode = $_POST['SortCode'] ?? null;
    $CurrencyCode = $_POST['CurrencyCode'] ?? null;

    // contact person details
    $ContactPersone1 = $_POST['ContactPersone1'];
    $cp1Relationship = $_POST['cp1Relationship'];
    $cp1MobileNumber = $_POST['cp1MobileNumber'];
    $cp1EmailAddress = $_POST['cp1EmailAddress'];
    $cp1Address = $_POST['cp1Address'];
    $cp1PostalCode = $_POST['cp1PostalCode'];
    $cp1Note = $_POST['cp1Note'];

    $ContactPersone2 = $_POST['ContactPersone2'];
    $cp2Relationship = $_POST['cp2Relationship'];
    $cp2MobileNumber = $_POST['cp2MobileNumber'];
    $cp2EmailAddress = $_POST['cp2EmailAddress'];
    $cp2Address = $_POST['cp2Address'];
    $cp2PostalCode = $_POST['cp2PostalCode'];
    $cp2Note = $_POST['cp2Note'];
    $isPrimaryRadio = $_POST['isPrimaryRadio'];
    $isPrimaryRadio1 = "";
    $isPrimaryRadio2 = "";
    if (isset($_POST['isPrimaryRadio']) == "cp1") {
        $isPrimaryRadio1 = "yes";
    } else if (isset($_POST['isPrimaryRadio']) == "cp2") {
        $isPrimaryRadio2 = "yes";
    }

    $distpath = "../attachementfile/" . $companyid . "/" . $subcompanyid . "/" . $projectid . "/";

    // Employee data (replace with actual data)
    $employeeData = ["companyid" => $companyid, "subcompanyid" => $subcompanyid, "projectid" => $projectid, "TitleName" => $TitleName, "FirstName" => $FirstName, "MiddleName" => $MiddleName, "LastName" => $LastName, "FatherName" => $FatherName, "MotherName" => $MotherName, "MaritalStatus" => $MaritalStatus, "SpouseName" => $SpouseName, "Gender" => $GenderName, "DateOfBirth" => $DateOfBirth, "Email" => $EmailAddress, "MobileNo" => $MobileNo, "PanNumber" => $PanNumber, "AadharNumber" => $AadharNumber, "Nationality" => $Nationality, "PassportNumber" => $PassportNumber, "AddressLine1" => $AddressLine1, "AddressLine2" => $AddressLine2, "AddressLine3" => $AddressLine3, "AddressLine4" => $AddressLine4, "PostalCode" => $PostalCode, "CountryName" => $CountryName, "addedby" => $addedby, "EmployeeID" => $EmployeeID, "dateOfJoining" => ""];
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $hrmconn->begin_transaction();
    try {
        $empsaverst = saveOrUpdateEmployee($hrmconn, $employeeData);
        // print_r($savedataresutl);
        // if($empsaverst['success']==1){            
        $employeeID = $empsaverst['employeeID'];
        $employeeName = preg_replace('/\s+/', ' ', trim("$FirstName $MiddleName $LastName"));
        if ($employeeID) {
            // Uploaded Document Path of EmployeeId
            $PanDocument = uploadFile('PanDocument', $distpath . 'HRM/pan/');
            $AadharDocument = uploadFile('AadharDocument', $distpath . 'HRM/aadhar/');
            $ProfilePhoto = uploadFile('ProfilePhoto', $distpath . 'HRM/photos/');
            $CVFile = uploadFile('Cv', $distpath . 'HRM/cv/');
            $CanceledCheque = uploadFile('CanceledCheque', $distpath . 'HRM/cheque/');

            // You can now use the EmployeeID for further operations, such as inserting documents
            if ($PanDocument) {
                insertOrUpdateEmployeeDocument($hrmconn, $companyid, $subcompanyid, $projectid, $employeeID, 'PanDocument', $PanDocument);
            }
            if ($AadharDocument) {
                insertOrUpdateEmployeeDocument($hrmconn, $companyid, $subcompanyid, $projectid, $employeeID, 'AadharDocument', $AadharDocument);
            }
            if ($ProfilePhoto) {
                insertOrUpdateEmployeeDocument($hrmconn, $companyid, $subcompanyid, $projectid, $employeeID, 'ProfilePhoto', $ProfilePhoto);
            }
            if ($CVFile) {
                insertOrUpdateEmployeeDocument($hrmconn, $companyid, $subcompanyid, $projectid, $employeeID, 'CVFile', $CVFile);
            }
            if ($CanceledCheque) {
                insertOrUpdateEmployeeDocument($hrmconn, $companyid, $subcompanyid, $projectid, $employeeID, 'CanceledCheque', $CanceledCheque);
            }
            // update bank details of EmployeeID
            insertOrUpdateBankDetails($hrmconn, $companyid, $subcompanyid, $projectid, $employeeID, $BankName, $AccountNumber, $IFSCCode, $SwiftCode, $IbanNumber, $SortCode, $CurrencyCode, $CanceledCheque);
            // update contact person details
            insertOrUpdateEmployeeContactPersonDetails($hrmconn, $companyid, $subcompanyid, $projectid, $employeeID, $ContactPersone1, $cp1Relationship, $cp1MobileNumber, $cp1EmailAddress, $cp1PostalCode, $cp1Address, $cp1Note, $isPrimaryRadio1);
            insertOrUpdateEmployeeContactPersonDetails($hrmconn, $companyid, $subcompanyid, $projectid, $employeeID, $ContactPersone2, $cp2Relationship, $cp2MobileNumber, $cp2EmailAddress, $cp2PostalCode, $cp2Address, $cp2Note, $isPrimaryRadio2);

            $loginpassword = preg_replace('/\s+/', ' ', trim("$FirstName")) . '@' . $employeeID;
            insertOrUpdateEmployeeLoginDetails($hrmconn, $authconn, $companyid, $subcompanyid, $projectid, $employeeID, $employeeID, $employeeName, $loginpassword, $MobileNo, $EmailAddress);
        } else {
            $hrmconn->rollback();
            throw new Exception("Error Emplyee Id not found: ");
        }

        // }
        $hrmconn->commit();
        $rst = array('status' => 'Success', 'message' => $empsaverst['message'], 'empid' => $employeeID);
    } catch (mysqli_sql_exception $e) {
        $hrmconn->rollback();
        // $rst=array('status'=>'Error', 'message'=>$e->getMessage());
        // Capture the error message, line number, and file
        $errorMessage = $e->getMessage();
        $errorLine = $e->getLine(); // Gets the line number
        $errorFile = $e->getFile(); // Gets the file name

        // Prepare the error response
        $rst = array(
            'status' => 'Error',
            'message' => "Error on line $errorLine in file $errorFile: $errorMessage"
        );
    }
    echo json_encode($rst);
} else if ($action == "AddUpdateEmployeeWorkDetails") {
    $companyid = $_POST['companyid'] ?? '';
    $subcompanyid = $_POST['subcompanyid'] ?? '';
    $projectid = $_POST['projectid'] ?? '';
    $addedby = $_POST['addedby'] ?? '';
    $loginid = $_POST['loginid'] ?? '';

    $EmployeeID = $_POST['EmployeeID'] ?? '';
    $DateOfJoining = $_POST['StartDate'] ?? '';
    $EmploymentTypeid = $_POST['employmentType'] ?? '';
    $DepartmentID = $_POST['department'] ?? '';
    $DesignationID = $_POST['designation'] ?? '';
    $workinghourid = $_POST['workinghour'] ?? '';
    $WorkLocation = $_POST['WorkingLocation'] ?? '';
    if ($WorkLocation == "outoffice") {
        $attendanceType = $WorkLocation;
    } else {
        $attendanceType = "office";
    }
    $gwhdata = getEmployeeWorkingHoursFromMaster($hrmconn, $projectid, $workinghourid, false);
    if (empty($gwhdata)) {
        echo json_encode(["status" => "error", "message" => "Working Hours not found"]);
        exit;
    }
    $inTime = $gwhdata[0]['start_time'];
    $outTime = $gwhdata[0]['end_time'];

    $weekOffArray = $_POST['WeekOff'] ?? [];
    if (!is_array($weekOffArray)) {
        $weekOffArray = explode(",", $weekOffArray);
        $ddd = saveUpdateEmployeeWeekOffWithArray($hrmconn, $EmployeeID, $projectid, $weekOffArray, $remark, $addedby);
    }
    
    // Response array
    $response = ['status' => 'error', 'message' => 'Invalid request.'];
    if (!empty($EmployeeID)) {
        // Set UTF-8 encoding
        $hrmconn->query("SET NAMES 'UTF8'");
        // Prepare the SQL update query
        $updquery = "UPDATE hrm_EmployeesMaster 
                     SET DateOfJoining = ?, EmploymentTypeid = ?, DesignationID = ?, DepartmentID = ?, InTime = ?, OutTime = ?, WorkHourseId = ?, attendanceType = ?, WorkLocation = ?
                     WHERE companyid = ? AND projectid = ? AND EmployeeID = ?";

        if ($updateStmt = $hrmconn->prepare($updquery)) {
            // Bind parameters
            $updateStmt->bind_param("ssssssssssss", $DateOfJoining, $EmploymentTypeid, $DesignationID, $DepartmentID, $inTime, $outTime, $workinghourid, $attendanceType, $WorkLocation, $companyid, $projectid, $EmployeeID);

            // Execute the statement and check for success
            if ($updateStmt->execute()) {
                if (!empty($DateOfJoining) && $DateOfJoining != '0000-00-00') {
                    $hrmconn->query("UPDATE hrm_EmployeeSalary SET affectedfrom = '$DateOfJoining' WHERE EmployeeID = '$EmployeeID' AND (affectedfrom IS NULL OR affectedfrom = '' OR affectedfrom = '0000-00-00')");
                }
                $response = ['status' => 'success', 'message' => 'Employee details updated successfully.'];
            } else {
                $response = ['status' => 'error', 'message' => 'Error updating employee details.'];
            }

            // Close statement
            $updateStmt->close();
        } else {
            $response = ['status' => 'error', 'message' => 'Failed to prepare the update query.'];
        }
    } else {
        $response = ['status' => 'error', 'message' => 'EmployeeID is required.'];
    }
    // Return JSON response
    echo json_encode($response);
} else if ($action == "SaveOrUpdateEarningComponenttoPayroll") {
    $companyid = $_POST['companyid'] ?? '';
    $loginname = $_POST['loginname'] ?? '';
    $componentid = $_POST['componentid'] ?? ''; // This is id (if updating)
    $description = $_POST['description'] ?? '';
    $earningpaytype = $_POST['earningpaytype'] ?? ''; // Percentage / Fixed
    $calculationtype = $_POST['calculationtype'] ?? ''; // Basic / Gross / CTC
    $calculationval = $_POST['calculationval'] ?? '0';

    $returnmsg = [];

    $el = getPayrollEarningMasterGlobalList($hrmconn, "id", $componentid);
    if (count($el) > 0) {
        $componentname = $el[0]['ComponentName'] ?? '';
        $sortname = $el[0]['sortname'] ?? '';
        $frequency = $el[0]['frequency'] ?? 'Monthly';
        $defaultforemployee = $el[0]['defaultforemployee'] ?? 0;
    }
    $sss = getPayrollEarningsMasterList($hrmconn, $companyid, "ComponentId", $componentid);
    if (!empty($componentid) && count($sss) > 0) {
        // UPDATE record
        $updQry = "UPDATE hrm_PayrollEarningsMaster 
                   SET ComponentName = ?, sortname = ?, defaultforemployee = ?, Frequency = ?, 
                       EmployeeEarningType = ?, EmployeeEarningValue = ?, EmployeeEarningBase = ?, description = ?
                   WHERE ComponentId = ?";

        $updstmt = $hrmconn->prepare($updQry);
        $updstmt->bind_param(
            "ssissdssi",
            $componentname,
            $sortname,
            $defaultforemployee,
            $frequency,
            $earningpaytype,
            $calculationval,
            $calculationtype,
            $description,
            $componentid
        );

        if ($updstmt->execute()) {
            $result = ['status' => 'Success', 'message' => "Record updated successfully."];
        } else {
            $result = ['status' => 'Error', 'message' => "Error: " . $updstmt->error];
        }
        $updstmt->close();
    } else {
        // INSERT new record
        $insertQry = "INSERT INTO hrm_PayrollEarningsMaster (
                        companyid, ComponentName, ComponentId, sortname, defaultforemployee, Frequency,
                        EmployeeEarningType, EmployeeEarningValue, EmployeeEarningBase, description
                      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $insertstmt = $hrmconn->prepare($insertQry);
        $insertstmt->bind_param(
            "isssissdss",
            $companyid,
            $componentname,
            $componentid,
            $sortname,
            $defaultforemployee,
            $frequency,
            $earningpaytype,
            $calculationval,
            $calculationtype,
            $description
        );

        if ($insertstmt->execute()) {
            $result = ['status' => 'Success', 'message' => "New record inserted successfully."];
        } else {
            $result = ['status' => 'Error', 'message' => "Error: " . $insertstmt->error];
        }
        $insertstmt->close();
    }

    echo json_encode($result);

} else if ($action == "SaveOrUpdateDeductiontoPayroll") {
    $companyid = $_POST['companyid'] ?? '';
    $loginname = $_POST['loginname'] ?? '';
    $DeductionId = $_POST['deductionid'] ?? '';
    $DeductionName = $_POST['deductionname'] ?? '';
    $SortName = $_POST['deductionsortname'] ?? '';
    $description = $_POST['description'] ?? '';
    $Frequency = $_POST['deductionfrequency'] ?? '';
    $EmployeeDeductionType = $_POST['deductiontype'] ?? '';
    $EmployeeDeductionBase = $_POST['empdeductionbase'] ?? '';
    $EmployeeDeductionValue = $_POST['employeededctionvalue'] ?? '0';

    $EmployerDeductionType = $EmployeeDeductionType;
    $EmployerDeductionBase = $_POST['emplrdeductionbase'] ?? '';
    $EmployerDeductionValue = $_POST['employerdedctionvalue'] ?? '0';

    $IsIncludedInCTC = $_POST['iscontributeinctc'] ?? '0';
    $IsIncludedInFBP = $_POST['iscontributeinfbp'] ?? '0';
    $IsProRataBasis = $_POST['iscontributeinproratabasis'] ?? '0';
    $defaultforemployee = '0';
    $response = [];
    $checkId = getPayrollDeductionsMasterList($hrmconn, $companyid, "DeductionId", $DeductionId);
    if ($DeductionId == $checkId[0]['DeductionId']) {
        $sql = "UPDATE hrm_PayrollDeductionsMaster 
                SET 
                    DeductionName = '" . addslashes($DeductionName) . "',
                    sortname = '" . addslashes($SortName) . "',
                    Frequency = '" . addslashes($Frequency) . "',
                    EmployeeDeductionType = '" . addslashes($EmployeeDeductionType) . "',
                    EmployeeDeductionValue = '" . floatval($EmployeeDeductionValue) . "',
                    EmployeeDeductionBase = '" . addslashes($EmployeeDeductionBase) . "',
                    EmployerDeductionType = '" . addslashes($EmployerDeductionType) . "',
                    EmployerDeductionValue = '" . floatval($EmployerDeductionValue) . "',
                    EmployerDeductionBase = '" . addslashes($EmployerDeductionBase) . "',
                    IsIncludedInCTC = '" . intval($IsIncludedInCTC) . "',
                    IsIncludedInFBP = '" . intval($IsIncludedInFBP) . "',
                    IsProRataBasis = '" . intval($IsProRataBasis) . "',
                    defaultforemployee = '" . intval($defaultforemployee) . "',
                    description = '" . addslashes($description) . "',
                    updated_at = CURRENT_TIMESTAMP
                WHERE companyid = '" . intval($companyid) . "' AND DeductionId = '" . intval($DeductionId) . "'";

        $stmt = $hrmconn->query($sql);

        if ($stmt) {
            $response = ["status" => "Success", "message" => "Deduction rule updated successfully!"];
        } else {
            $response = ["status" => "error", "message" => "Failed to update deduction rule: " . $hrmconn->error];
        }
    } else {
        $sql = "INSERT INTO hrm_PayrollDeductionsMaster (
                    companyid, DeductionId, DeductionName, sortname, Frequency, 
                    EmployeeDeductionType, EmployeeDeductionValue, EmployeeDeductionBase, 
                    EmployerDeductionType, EmployerDeductionValue, EmployerDeductionBase, 
                    IsIncludedInCTC, IsIncludedInFBP, IsProRataBasis, defaultforemployee, description
                ) VALUES (
                    '" . intval($companyid) . "', 
                    '" . intval($DeductionId) . "', 
                    '" . addslashes($DeductionName) . "', 
                    '" . addslashes($SortName) . "', 
                    '" . addslashes($Frequency) . "', 
                    '" . addslashes($EmployeeDeductionType) . "', 
                    '" . floatval($EmployeeDeductionValue) . "', 
                    '" . addslashes($EmployeeDeductionBase) . "', 
                    '" . addslashes($EmployerDeductionType) . "', 
                    '" . floatval($EmployerDeductionValue) . "', 
                    '" . addslashes($EmployerDeductionBase) . "', 
                    '" . intval($IsIncludedInCTC) . "', 
                    '" . intval($IsIncludedInFBP) . "', 
                    '" . intval($IsProRataBasis) . "', 
                    '" . intval($defaultforemployee) . "', 
                    '" . addslashes($description) . "'
                )";

        $stmt = $hrmconn->query($sql);

        if ($stmt) {
            $response = ["status" => "Success", "message" => "Deduction rule added successfully!"];
        } else {
            $response = ["status" => "error", "message" => "Failed to insert deduction rule: " . $hrmconn->error];
        }
    }

    echo json_encode($response);
} else if ($action == "fetchEmployeeDatawithOtherifno_callback") {
    // Ensure all expected POST parameters exist before using them
    $companyid = isset($_POST['companyid']) ? $_POST['companyid'] : null;
    $subcompanyid = isset($_POST['subcompanyid']) ? $_POST['subcompanyid'] : null;
    $projectid = isset($_POST['projectid']) ? $_POST['projectid'] : null;
    $sortby = isset($_POST['sortby']) ? $_POST['sortby'] : null;
    $sortbyvalue = isset($_POST['sortbyvalue']) ? $_POST['sortbyvalue'] : null;
    // Fetch employee data
    $emplist = fetchEmployeeData($hrmconn, $companyid, $subcompanyid, $projectid, $sortby, $sortbyvalue);
    // print_r($emplist);
    $emplistdata = [];
    if (!empty($emplist)) {
        foreach ($emplist as $eekey) {

            // Working Location
            $WorkLocationName = "";
            if ($eekey['WorkLocation']) {
                $empwl = getAttendanceLocationMaster($hrmconn, $companyid, $subcompanyid, $projectid, "LocationId", $eekey['WorkLocation']);
                $WorkLocationName = $empwl[0]['LocationName'];
            }
            // Fetch Department Details
            $ddp = fetchDepartmentMaster($hrmconn, $companyid, "id", $eekey['DepartmentID']);
            $deparmentname = !empty($ddp) ? $ddp[0]['DepartmentName'] : 'N/A';
            // print_r($ddp);
            // die();

            // Fetch Designation Details
            $desp = fetchDesignationsMaster($hrmconn, $companyid, "id", $eekey['DesignationID']);
            $designationame = !empty($desp) ? $desp[0]['DesignationName'] : 'N/A';

            // EmployeeEarningComponents
            $empEarcom = getEmployeeEarningComponents($hrmconn, $eekey['companyid'], $eekey['subcompanyid'], $eekey['projectid'], $eekey['EmployeeID']);

            $empEarcomponentstatus = 0;
            if (count($empEarcom) > 0) {
                $empEarcomponentstatus = 1;
            }

            // Employee Salary
            $empSalary = fetchEmployeeSalaryData($hrmconn, $eekey['companyid'], $eekey['subcompanyid'], $eekey['projectid'], 'EmployeeID', $eekey['EmployeeID']);
            $empCTC = 0;
            $empGross = 0;
            $empBasic = 0;
            $empHRA = 0;
            $empConveyance = 0;
            $empSpecialAllowance = 0;
            if (count($empSalary) > 0) {
                $empCTC = $empSalary[0]['CTC'];
                $empGross = $empSalary[0]['GrossSalary'];
                $empBasic = $empSalary[0]['BasicSalary'];
                $empHRA = $empSalary[0]['HRA'];
                $empConveyance = $empSalary[0]['ConveyanceAllowance'];
                $empSpecialAllowance = $empSalary[0]['SpecialAllowance'];
            }

            $basiccompletestatus = "Active Employee";
            // echo $deparmentname." :: ".$designationame." :: ".$empEarcomponentstatus;
            // die();
            if ($deparmentname == "" || $deparmentname == "N/A" || $designationame == "N/A" || $empEarcomponentstatus == 0) {
                $basiccompletestatus = "<button class='btn btn-sm btn-waring gotocompleteinfo text-primary' onclick='gotoCompletebasicdetails(\"" . $eekey['EmployeeID'] . "\")'>Incomplete Employee Profile</button>";
            } else {
                $basiccompletestatus = "<button class='btn btn-sm btn-success gotocompleteinfo text-white' onclick='gotoCompletebasicdetails(\"" . $eekey['EmployeeID'] . "\")'>Active Employee</button>";
            }
            // employee documents
            $empDocs = getEmployeeDocumentDetails($hrmconn, $eekey['companyid'], $eekey['subcompanyid'], $eekey['projectid'], $eekey['EmployeeID']);
            $empPanpath = "";
            $empAadharpath = "";
            $empCvpath = "";
            $empBankDocpath = "";
            $empPhotopath = "";
            // print_r($empDocs);
            if (count($empDocs) > 0) {
                foreach ($empDocs as $empDkey) {
                    $dType = trim($empDkey['DocumentType'] ?? '');
                    $dPath = trim($empDkey['DocumentPath'] ?? '');
                    if ($dType === "PanDocument" && empty($empPanpath)) {
                        $empPanpath = $dPath;
                    } elseif ($dType === "AadharDocument" && empty($empAadharpath)) {
                        $empAadharpath = $dPath;
                    } elseif ($dType === "CVFile" && empty($empCvpath)) {
                        $empCvpath = $dPath;
                    } elseif ($dType === "CanceledCheque" && empty($empBankDocpath)) {
                        $empBankDocpath = $dPath;
                    } elseif (in_array(strtolower($dType), ["profilephoto", "profile photo", "profile_photo", "photo"]) && empty($empPhotopath)) {
                        $empPhotopath = $dPath;
                    }
                }
            }
            // Employee Data Formatting
            $emplistdata[] = [
                'EmployeeID' => $eekey['EmployeeID'] ?? '',
                'empPanpath' => $empPanpath ?? '',
                'empAadharpath' => $empAadharpath ?? '',
                'empCvpath' => $empCvpath ?? '',
                'empBankDocpath' => $empBankDocpath ?? '',
                'empPhotopath' => $empPhotopath ?? '',
                'companyid' => $eekey['companyid'] ?? '',
                'subcompanyid' => $eekey['subcompanyid'] ?? '',
                'projectid' => $eekey['projectid'] ?? '',
                'TitleName' => $eekey['TitleName'] ?? '',
                'FirstName' => $eekey['FirstName'] ?? '',
                'MiddleName' => $eekey['MiddleName'] ?? '',
                'LastName' => $eekey['LastName'] ?? '',
                'FullName' => $eekey['FullName'] ?? '',
                'CTC' => $empCTC ?? '0',
                'GrossSalary' => $empGross ?? '0',
                'BasicSalary' => $empBasic ?? '0',
                'HRA' => $empHRA ?? '0',
                'ConveyanceAllowance' => $empConveyance ?? '0',
                'SpecialAllowance' => $empSpecialAllowance ?? '0',
                'FatherName' => $eekey['FatherName'] ?? '',
                'MotherName' => $eekey['MotherName'] ?? '',
                'MaritalStatus' => $eekey['MaritalStatus'] ?? '',
                'SpouseName' => $eekey['SpouseName'] ?? '',
                'Gender' => $eekey['Gender'] ?? '',
                'DateOfBirth' => $eekey['DateOfBirth'] ?? '',
                'DateOfJoining' => $eekey['DateOfJoining'] ?? '',
                'DateOfResignation' => $eekey['DateOfResignation'] ?? '',
                'DesignationID' => $eekey['DesignationID'] ?? '',
                'DesignationName' => $designationame ?? '',
                'DepartmentID' => $eekey['DepartmentID'] ?? '',
                'DepartmentName' => $deparmentname ?? '',
                'Email' => $eekey['Email'] ?? '',
                'MobileNo' => $eekey['MobileNo'] ?? '',
                'PanNumber' => $eekey['PanNumber'] ?? '',
                'AadharNumber' => $eekey['AadharNumber'] ?? '',
                'PassportNumber' => $eekey['PassportNumber'] ?? '',
                'VisaStatus' => $eekey['VisaStatus'] ?? '',
                'Nationality' => $eekey['Nationality'] ?? '',
                'WorkLocation' => $WorkLocationName ?? '',
                'WorkLocationID' => $eekey['WorkLocation'] ?? '',
                'InTime' => $eekey['InTime'] ?? '',
                'OutTime' => $eekey['OutTime'] ?? '',
                'WorkLocationName' => $WorkLocationName ?? '',
                'CountryCode' => $eekey['CountryCode'] ?? '',
                'AddressLine1' => $eekey['AddressLine1'] ?? '',
                'AddressLine2' => $eekey['AddressLine2'] ?? '',
                'AddressLine3' => $eekey['AddressLine3'] ?? '',
                'AddressLine4' => $eekey['AddressLine4'] ?? '',
                'PostalCode' => $eekey['PostalCode'] ?? '',
                'CountryName' => $eekey['CountryName'] ?? '',
                'Status' => $eekey['Status'] ?? '',
                'basiccompletestatus' => $basiccompletestatus ?? 0
            ];
        }
    }
    // Send JSON response
    header('Content-Type: application/json');
    echo json_encode($emplistdata);
} else if ($action == "EditEmployeeProfildata") {
    $companyid = isset($_POST['companyid']) ? $_POST['companyid'] : null;
    $subcompanyid = isset($_POST['subcompanyid']) ? $_POST['subcompanyid'] : null;
    $projectid = isset($_POST['projectid']) ? $_POST['projectid'] : null;
    $EmployeeID = isset($_POST['employeeid']) ? $_POST['employeeid'] : null;
    $empdata = fetchEmployeeData($hrmconn, $companyid, $subcompanyid, $projectid, "EmployeeID", $EmployeeID);
    $emplistdata = [];
    if (!empty($empdata)) {
        foreach ($empdata as $eekey) {
            // Working Location
            $WorkLocationName = "";
            if ($eekey['WorkLocation']) {
                $empwl = getAttendanceLocationMaster($hrmconn, $companyid, $subcompanyid, $projectid, "LocationId", $eekey['WorkLocation']);
                $WorkLocationName = $empwl[0]['LocationName'];
            }
            // Fetch Department Details
            $ddp = fetchDepartmentMaster($hrmconn, $companyid, "id", $eekey['DepartmentID']);
            $deparmentname = !empty($ddp) ? $ddp[0]['DepartmentName'] : 'N/A';
            // print_r($ddp);
            // die();

            // Fetch Designation Details
            $desp = fetchDesignationsMaster($hrmconn, $companyid, "id", $eekey['DesignationID']);
            $designationame = !empty($desp) ? $desp[0]['DesignationName'] : 'N/A';

            // EmployeeEarningComponents
            $empEarcom = getEmployeeEarningComponents($hrmconn, $eekey['companyid'], $eekey['subcompanyid'], $eekey['projectid'], $eekey['EmployeeID']);

            $empEarcomponentstatus = 0;
            if (count($empEarcom) > 0) {
                $empEarcomponentstatus = 1;
            }

            // Employee Salary
            $empSalary = fetchEmployeeSalaryData($hrmconn, $eekey['companyid'], $eekey['subcompanyid'], $eekey['projectid'], 'EmployeeID', $eekey['EmployeeID']);
            $empCTC = 0;
            $empGross = 0;
            $empBasic = 0;
            $empHRA = 0;
            $empConveyance = 0;
            $empSpecialAllowance = 0;
            if (count($empSalary) > 0) {
                $empCTC = $empSalary[0]['CTC'];
                $empGross = $empSalary[0]['GrossSalary'];
                $empBasic = $empSalary[0]['BasicSalary'];
                $empHRA = $empSalary[0]['HRA'];
                $empConveyance = $empSalary[0]['ConveyanceAllowance'];
                $empSpecialAllowance = $empSalary[0]['SpecialAllowance'];
            }

            $basiccompletestatus = "Active Employee";
            // echo $deparmentname." :: ".$designationame." :: ".$empEarcomponentstatus;
            // die();
            if ($deparmentname == "" || $deparmentname == "N/A" || $designationame == "N/A" || $empEarcomponentstatus == 0) {
                $basiccompletestatus = "<button class='btn btn-sm btn-waring gotocompleteinfo text-primary' onclick='gotoCompletebasicdetails(\"" . $eekey['EmployeeID'] . "\")'>Incomplete Employee Profile</button>";
            } else {
                $basiccompletestatus = "<button class='btn btn-sm btn-success gotocompleteinfo text-white' onclick='gotoCompletebasicdetails(\"" . $eekey['EmployeeID'] . "\")'>Active Employee</button>";
            }
            // employee documents
            $empDocs = getEmployeeDocumentDetails($hrmconn, $eekey['companyid'], $eekey['subcompanyid'], $eekey['projectid'], $eekey['EmployeeID']);
            $empPanpath = "";
            $empAadharpath = "";
            $empCvpath = "";
            $empBankDocpath = "";
            $empPhotopath = "";
            // print_r($empDocs);
            if (count($empDocs) > 0) {
                foreach ($empDocs as $empDkey) {
                    $dType = trim($empDkey['DocumentType'] ?? '');
                    $dPath = trim($empDkey['DocumentPath'] ?? '');
                    if ($dType === "PanDocument" && empty($empPanpath)) {
                        $empPanpath = $dPath;
                    } elseif ($dType === "AadharDocument" && empty($empAadharpath)) {
                        $empAadharpath = $dPath;
                    } elseif ($dType === "CVFile" && empty($empCvpath)) {
                        $empCvpath = $dPath;
                    } elseif ($dType === "CanceledCheque" && empty($empBankDocpath)) {
                        $empBankDocpath = $dPath;
                    } elseif (in_array(strtolower($dType), ["profilephoto", "profile photo", "profile_photo", "photo"]) && empty($empPhotopath)) {
                        $empPhotopath = $dPath;
                    }
                }
            }
            // Fetch Contact Person Details
            $contactPersons = getEmployeeContactPersonDetails($hrmconn, $eekey['companyid'], $eekey['subcompanyid'], $eekey['projectid'], $eekey['EmployeeID']);
            $cp1 = isset($contactPersons[0]) ? $contactPersons[0] : [];
            $cp2 = isset($contactPersons[1]) ? $contactPersons[1] : [];

            // Employee Data Formatting
            $emplistdata[] = [
                'EmployeeID' => $eekey['EmployeeID'] ?? '',
                'empPanpath' => $empPanpath ?? '',
                'empAadharpath' => $empAadharpath ?? '',
                'empCvpath' => $empCvpath ?? '',
                'empBankDocpath' => $empBankDocpath ?? '',
                'empPhotopath' => $empPhotopath ?? '',
                'companyid' => $eekey['companyid'] ?? '',
                'subcompanyid' => $eekey['subcompanyid'] ?? '',
                'projectid' => $eekey['projectid'] ?? '',
                'TitleName' => $eekey['TitleName'] ?? '',
                'FirstName' => $eekey['FirstName'] ?? '',
                'MiddleName' => $eekey['MiddleName'] ?? '',
                'LastName' => $eekey['LastName'] ?? '',
                'FullName' => $eekey['FullName'] ?? '',
                'CTC' => $empCTC ?? '0',
                'GrossSalary' => $empGross ?? '0',
                'BasicSalary' => $empBasic ?? '0',
                'HRA' => $empHRA ?? '0',
                'ConveyanceAllowance' => $empConveyance ?? '0',
                'SpecialAllowance' => $empSpecialAllowance ?? '0',
                'FatherName' => $eekey['FatherName'] ?? '',
                'MotherName' => $eekey['MotherName'] ?? '',
                'MaritalStatus' => $eekey['MaritalStatus'] ?? '',
                'SpouseName' => $eekey['SpouseName'] ?? '',
                'Gender' => $eekey['Gender'] ?? '',
                'DateOfBirth' => $eekey['DateOfBirth'] ?? '',
                'DateOfJoining' => $eekey['DateOfJoining'] ?? '',
                'DateOfResignation' => $eekey['DateOfResignation'] ?? '',
                'DesignationID' => $eekey['DesignationID'] ?? '',
                'DesignationName' => $designationame ?? '',
                'DepartmentID' => $eekey['DepartmentID'] ?? '',
                'DepartmentName' => $deparmentname ?? '',
                'Email' => $eekey['Email'] ?? '',
                'MobileNo' => $eekey['MobileNo'] ?? '',
                'PanNumber' => $eekey['PanNumber'] ?? '',
                'AadharNumber' => $eekey['AadharNumber'] ?? '',
                'PassportNumber' => $eekey['PassportNumber'] ?? '',
                'VisaStatus' => $eekey['VisaStatus'] ?? '',
                'Nationality' => $eekey['Nationality'] ?? '',
                'WorkLocation' => $eekey['WorkLocation'] ?? '',
                'InTime' => $eekey['InTime'] ?? '',
                'OutTime' => $eekey['OutTime'] ?? '',
                'WorkLocationName' => $WorkLocationName ?? '',
                'CountryCode' => $eekey['CountryCode'] ?? '',
                'AddressLine1' => $eekey['AddressLine1'] ?? '',
                'AddressLine2' => $eekey['AddressLine2'] ?? '',
                'AddressLine3' => $eekey['AddressLine3'] ?? '',
                'AddressLine4' => $eekey['AddressLine4'] ?? '',
                'PostalCode' => $eekey['PostalCode'] ?? '',
                'CountryName' => $eekey['CountryName'] ?? '',
                'ContactPerson1_Name' => $cp1['ContactName'] ?? '',
                'ContactPerson1_Relation' => $cp1['Relationship'] ?? '',
                'ContactPerson1_Mobile' => $cp1['PhoneNumber'] ?? '',
                'ContactPerson1_Email' => $cp1['EmailAddress'] ?? '',
                'ContactPerson1_Address' => $cp1['Address'] ?? '',
                'ContactPerson1_PostalCode' => $cp1['PostalCode'] ?? '',
                'ContactPerson1_Note' => $cp1['Note'] ?? '',
                'ContactPerson2_Name' => $cp2['ContactName'] ?? '',
                'ContactPerson2_Relation' => $cp2['Relationship'] ?? '',
                'ContactPerson2_Mobile' => $cp2['PhoneNumber'] ?? '',
                'ContactPerson2_Email' => $cp2['EmailAddress'] ?? '',
                'ContactPerson2_Address' => $cp2['Address'] ?? '',
                'ContactPerson2_PostalCode' => $cp2['PostalCode'] ?? '',
                'ContactPerson2_Note' => $cp2['Note'] ?? '',
                'Status' => $eekey['Status'] ?? '',
                'basiccompletestatus' => $basiccompletestatus ?? 0
            ];
        }
    }
    // Send JSON response
    header('Content-Type: application/json');
    echo json_encode($emplistdata);
} else if ($action == "UpdateEmployeeProfileDetails") {
    $response = ["status" => "error", "message" => "Unknown error"];
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;

    $titlename = trim($_POST['titlename'] ?? "");
    $firstname = trim(strtoupper($_POST['firsname']) ?? "");
    $middlename = trim(strtoupper($_POST['middlename']) ?? "");
    $lastname = trim(strtoupper($_POST['lastname']) ?? "");
    $dob = $_POST['dateofbirth'] ?? null;
    $gender = $_POST['gender'] ?? null;
    $email = trim(strtolower($_POST['email']) ?? "");
    $mobile = trim($_POST['phoneno'] ?? "");
    $doj = $_POST['joiningdate'] ?? null;
    $employeeid = $_POST['EmployeeId'] ?? null;
    if (empty($employeeid) || empty($projectid)) {
        $response["message"] = "Employee ID or Project ID missing.";
        echo json_encode($response);
        exit;
    }
    if ($firstname === "") {
        $response["message"] = "First name is required.";
        echo json_encode($response);
        exit;
    }
    $sql = "UPDATE hrm_EmployeesMaster
        SET 
            TitleName     = ?,
            FirstName     = ?,
            MiddleName    = ?,
            LastName      = ?,
            Gender        = ?,
            DateOfBirth   = ?,
            DateOfJoining = ?,
            MobileNo      = ?,
            Email         = ?
        WHERE ProjectID = ?
          AND EmployeeID = ?
    ";
    $stmt = $hrmconn->prepare($sql);
    if (!$stmt) {
        $response["message"] = "Prepare failed: " . $hrmconn->error;
        echo json_encode($response);
        exit;
    }
    $stmt->bind_param(
        "sssssssssii",
        $titlename,
        $firstname,
        $middlename,
        $lastname,
        $gender,
        $dob,
        $doj,
        $mobile,
        $email,
        $projectid,
        $employeeid
    );
    if ($stmt->execute()) {
        $response["status"] = "success";
        $response["message"] = "Employee profile updated successfully.";
    } else {
        $response["message"] = "Update failed: " . $stmt->error;
    }
    $stmt->close();
    echo json_encode($response);
    exit;
} else if ($action == "getEmployeeSummarywithDetails") {
    $companyid = isset($_POST['companyid']) ? $_POST['companyid'] : null;
    $subcompanyid = isset($_POST['subcompanyid']) ? $_POST['subcompanyid'] : null;
    $projectid = isset($_POST['projectid']) ? $_POST['projectid'] : null;
    $empsum = getEmployeeSummaryWithDetails($hrmconn, $companyid, $subcompanyid, $projectid);
    echo json_encode($empsum);
} else if ($action == "removEmployeeFromMaster") {
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;
    $employeeid = $_POST['rmemid'] ?? null;
    $remark = $_POST['rmremark'] ?? null;
    $loginname = $_POST['loginname'] ?? null;
    $dor = $_POST['dor'] ?? date('Y-m-d');
    $status = "Resigned";
    $updatedate = date('Y-m-d H:i:s');
    $empD = fetchEmployeeData($hrmconn, $companyid, $subcompanyid, $projectid, "EmployeeID", $employeeid);
    $emploginid = $empD[0]['loginid'];

    $hrmconn->begin_transaction();

    try {
        $sqlEmp = " UPDATE hrm_EmployeesMaster 
            SET DateOfResignation = ?, remark = ?, Status = ?, tedit = ?, updated_at = ?
            WHERE projectid = ? AND EmployeeID = ?";
        $stmtEmp = $hrmconn->prepare($sqlEmp);
        $stmtEmp->bind_param("ssssssi", $dor, $remark, $status, $loginname, $updatedate, $projectid, $employeeid);
        if (!$stmtEmp->execute()) {
            throw new Exception("Employee update failed");
        }
        // 2️⃣ Disable Login Access
        $sqlLogin = "
            UPDATE login_user_master 
            SET 
                isActive = 'no',
                tedit = ?
            WHERE loginid = ?
        ";

        $stmtLogin = $authconn->prepare($sqlLogin);
        $stmtLogin->bind_param("ss", $status, $emploginid);

        if (!$stmtLogin->execute()) {
            throw new Exception("Login disable failed");
        }

        // ✅ Commit both updates
        $authconn->commit();
        $hrmconn->commit();

        echo json_encode([
            "status" => "success",
            "message" => "Employee resigned and login access disabled"
        ]);

    } catch (Exception $e) {

        // ❌ Rollback on error
        $hrmconn->rollback();

        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage()
        ]);
    }
} else if ($action == "fetchEmployeeData_callback") {
    // Ensure all expected POST parameters exist before using them
    $companyid = isset($_POST['companyid']) ? $_POST['companyid'] : null;
    $subcompanyid = isset($_POST['subcompanyid']) ? $_POST['subcompanyid'] : null;
    $projectid = isset($_POST['projectid']) ? $_POST['projectid'] : null;
    $sortby = isset($_POST['sortby']) ? $_POST['sortby'] : null;
    $sortbyvalue = isset($_POST['sortbyvalue']) ? $_POST['sortbyvalue'] : null;
    $emplist = fetchEmployeeData($hrmconn, $companyid, $subcompanyid, $projectid, $sortby, $sortbyvalue);
    echo json_encode($emplist);
} else if ($action == "fetchEmployeeDataForLocaton") {
    // Ensure all expected POST parameters exist before using them
    $companyid = isset($_POST['companyid']) ? $_POST['companyid'] : null;
    $subcompanyid = isset($_POST['subcompanyid']) ? $_POST['subcompanyid'] : null;
    $projectid = isset($_POST['projectid']) ? $_POST['projectid'] : null;
    $sortby = isset($_POST['sortby']) ? $_POST['sortby'] : null;
    $sortbyvalue = isset($_POST['sortbyvalue']) ? $_POST['sortbyvalue'] : null;
    $emplist = fetchEmployeeData($hrmconn, $companyid, $subcompanyid, $projectid, $sortby, $sortbyvalue);
    $emplocdata = array();
    if (count($emplist) > 0) {
        foreach ($emplist as $eekkey) {
            $locname = getAttendanceLocationMaster($hrmconn, $companyid, $subcompanyid, $projectid, "LocationId", $eekkey['WorkLocation']);
            $locationName = $locname[0]['LocationName'] ?? '';
            $emplocdata[] = array('sno' => $eekkey['sno'], 'EmployeeName' => $eekkey['FullName'], 'EmployeeID' => $eekkey['EmployeeID'], 'LocationId' => $eekkey['WorkLocation'], 'LocationName' => $locationName);
        }
    }
    echo json_encode($emplocdata);
} else if ($action == "uploadDirectProfilePhoto") {
    $companyid = $_POST['companyid'] ?? '';
    $subcompanyid = $_POST['subcompanyid'] ?? '';
    $projectid = $_POST['projectid'] ?? '';
    $EmployeeID = $_POST['EmployeeID'] ?? '';

    // Document & Profile Photo Handling
    $distpath = "../attachementfile/" . $companyid . "/" . $subcompanyid . "/" . $projectid . "/";
    
    $ProfilePhoto = null;
    $ProfilePhotoRaw = uploadFile('ProfilePhoto', $distpath . 'HRM/photos/');
    if ($ProfilePhotoRaw && strpos($ProfilePhotoRaw, '.') !== false) {
        $ProfilePhoto = $ProfilePhotoRaw;
        insertOrUpdateEmployeeDocument($hrmconn, $companyid, $subcompanyid, $projectid, $EmployeeID, 'ProfilePhoto', $ProfilePhoto);
    }  
    
    $response = ['status' => 'Error', 'message' => 'No photo file provided or file upload failed'];

    if ($EmployeeID && $ProfilePhoto) {
        $hrmconn->begin_transaction();
        try {
            // Update ONLY ProfilePhoto in hrm_EmployeesMaster
            $sql = "UPDATE hrm_EmployeesMaster SET ProfilePhoto=? WHERE EmployeeID=? AND projectid=?";
            $stmt = $hrmconn->prepare($sql);
            if (!$stmt)
                throw new Exception("Prepare failed: " . $hrmconn->error);
            $stmt->bind_param("ssi", $ProfilePhoto, $EmployeeID, $projectid);
            if (!$stmt->execute())
                throw new Exception("Update photo failed: " . $stmt->error);
            $stmt->close();

            $hrmconn->commit();
            $response = [
                'status' => 'Success', 
                'message' => 'Profile photo updated successfully!',
                'ProfilePhoto' => $ProfilePhoto
            ];
        } catch (Exception $e) {
            $hrmconn->rollback();
            $response = ['status' => 'Error', 'message' => $e->getMessage()];
        }
    }
    echo json_encode($response);
} else if ($action == "updatedetailsByemployee") {
    $companyid = $_POST['companyid'] ?? '';
    $subcompanyid = $_POST['subcompanyid'] ?? '';
    $projectid = $_POST['projectid'] ?? '';
    $EmployeeID = $_POST['EmployeeID'] ?? '';

    // Personal Details
    $TitleName = $_POST['TitleName'] ?? '';
    $FirstName = $_POST['FirstName'] ?? '';
    $MiddleName = $_POST['MiddleName'] ?? '';
    $LastName = $_POST['LastName'] ?? '';
    $FatherName = $_POST['FatherName'] ?? '';
    $MotherName = $_POST['MotherName'] ?? '';
    $MaritalStatus = $_POST['MaritalStatus'] ?? '';
    $SpouseName = $_POST['SpouseName'] ?? '';
    if (strtolower(trim($MaritalStatus)) !== 'married') {
        $SpouseName = '';
    }
    $DateOfBirth = $_POST['DateOfBirth'] ?? '';
    $GenderName = $_POST['GenderName'] ?? '';
    $MobileNo = $_POST['MobileNumber'] ?? '';
    $Email = $_POST['EmailAddress'] ?? '';
    $PanNumber = $_POST['PanNumber'] ?? '';
    $AadharNumber = $_POST['AadharNumber'] ?? '';
    $PassportNumber = $_POST['PassportNumber'] ?? '';
    $AddressLine1 = $_POST['AddressLine1'] ?? '';
    $AddressLine2 = $_POST['AddressLine2'] ?? '';
    $AddressLine3 = $_POST['AddressLine3'] ?? '';
    $AddressLine4 = $_POST['AddressLine4'] ?? '';
    $PostalCode = $_POST['PostalCode'] ?? '';
    $Nationality = $_POST['Nationality'] ?? '';
    $CountryName = $_POST['CountryName'] ?? '';

    // Document & Profile Photo Handling
    $distpath = "../attachementfile/" . $companyid . "/" . $subcompanyid . "/" . $projectid . "/";
    
    $ProfilePhoto = null;
    $ProfilePhotoRaw = uploadFile('ProfilePhoto', $distpath . 'HRM/photos/');
    if ($ProfilePhotoRaw && strpos($ProfilePhotoRaw, '.') !== false) {
        $ProfilePhoto = $ProfilePhotoRaw;
        insertOrUpdateEmployeeDocument($hrmconn, $companyid, $subcompanyid, $projectid, $EmployeeID, 'ProfilePhoto', $ProfilePhoto);
    }

    $PanDocRaw = uploadFile('PanDocument', $distpath . 'HRM/documents/');
    if ($PanDocRaw && strpos($PanDocRaw, '.') !== false) {
        insertOrUpdateEmployeeDocument($hrmconn, $companyid, $subcompanyid, $projectid, $EmployeeID, 'PanDocument', $PanDocRaw);
    }

    $AadharDocRaw = uploadFile('AadharDocument', $distpath . 'HRM/documents/');
    if ($AadharDocRaw && strpos($AadharDocRaw, '.') !== false) {
        insertOrUpdateEmployeeDocument($hrmconn, $companyid, $subcompanyid, $projectid, $EmployeeID, 'AadharDocument', $AadharDocRaw);
    }

    $CvDocRaw = uploadFile('CvDocument', $distpath . 'HRM/documents/');
    if ($CvDocRaw && strpos($CvDocRaw, '.') !== false) {
        insertOrUpdateEmployeeDocument($hrmconn, $companyid, $subcompanyid, $projectid, $EmployeeID, 'CVFile', $CvDocRaw);
    }

    $ChequeDocRaw = uploadFile('CanceledCheque', $distpath . 'HRM/documents/');
    if ($ChequeDocRaw && strpos($ChequeDocRaw, '.') !== false) {
        insertOrUpdateEmployeeDocument($hrmconn, $companyid, $subcompanyid, $projectid, $EmployeeID, 'CanceledCheque', $ChequeDocRaw);
    }

    // Bank Details
    $BankName = $_POST['BankName'] ?? '';
    $AccountNumber = $_POST['AccountNumber'] ?? '';
    $IFSCCode = $_POST['IFSCCode'] ?? '';

    $response = ['status' => 'Error', 'message' => 'Something went wrong'];

    if ($EmployeeID) {
        $hrmconn->begin_transaction();
        try {
            // 1. Full Employee Master Update
            if ($ProfilePhoto) {
                // Update with photo
                $sql = "UPDATE hrm_EmployeesMaster SET TitleName=?, FirstName=?, MiddleName=?, LastName=?, FatherName=?, MotherName=?, MaritalStatus=?, SpouseName=?, DateOfBirth=?, Gender=?, MobileNo=?, Email=?, PanNumber=?, AadharNumber=?, PassportNumber=?, AddressLine1=?, AddressLine2=?, AddressLine3=?, AddressLine4=?, PostalCode=?, Nationality=?, CountryName=?, ProfilePhoto=? WHERE EmployeeID=? AND projectid=?";
                $stmt = $hrmconn->prepare($sql);
                if (!$stmt)
                    throw new Exception("Prepare failed: " . $hrmconn->error);
                $stmt->bind_param("ssssssssssssssssssssssssi", $TitleName, $FirstName, $MiddleName, $LastName, $FatherName, $MotherName, $MaritalStatus, $SpouseName, $DateOfBirth, $GenderName, $MobileNo, $Email, $PanNumber, $AadharNumber, $PassportNumber, $AddressLine1, $AddressLine2, $AddressLine3, $AddressLine4, $PostalCode, $Nationality, $CountryName, $ProfilePhoto, $EmployeeID, $projectid);
            } else {
                // Update without photo (keep existing photo)
                $sql = "UPDATE hrm_EmployeesMaster SET TitleName=?, FirstName=?, MiddleName=?, LastName=?, FatherName=?, MotherName=?, MaritalStatus=?, SpouseName=?, DateOfBirth=?, Gender=?, MobileNo=?, Email=?, PanNumber=?, AadharNumber=?, PassportNumber=?, AddressLine1=?, AddressLine2=?, AddressLine3=?, AddressLine4=?, PostalCode=?, Nationality=?, CountryName=? WHERE EmployeeID=? AND projectid=?";
                $stmt = $hrmconn->prepare($sql);
                if (!$stmt)
                    throw new Exception("Prepare failed: " . $hrmconn->error);
                $stmt->bind_param("sssssssssssssssssssssssi", $TitleName, $FirstName, $MiddleName, $LastName, $FatherName, $MotherName, $MaritalStatus, $SpouseName, $DateOfBirth, $GenderName, $MobileNo, $Email, $PanNumber, $AadharNumber, $PassportNumber, $AddressLine1, $AddressLine2, $AddressLine3, $AddressLine4, $PostalCode, $Nationality, $CountryName, $EmployeeID, $projectid);
            }

            if (!$stmt->execute())
                throw new Exception("Update failed: " . $stmt->error);
            $stmt->close();

                // 2. Update Bank Details (only if BankName parameter was submitted)
                if (isset($_POST['BankName'])) {
                    $chkInfo = getEmployeeBankDetails($hrmconn, $companyid, $subcompanyid, $projectid, $EmployeeID);
                    if (count($chkInfo) > 0) {
                        // Update
                        $sqlBank = "UPDATE hrm_EmployeeBankDetails SET BankName=?, AccountNumber=?, IFSCCode=? WHERE EmployeeID=? AND projectid=?";
                        $stmtB = $hrmconn->prepare($sqlBank);
                        if (!$stmtB)
                            throw new Exception("Prepare Bank Update failed: " . $hrmconn->error);

                        $stmtB->bind_param("sssii", $BankName, $AccountNumber, $IFSCCode, $EmployeeID, $projectid);
                        if (!$stmtB->execute())
                            throw new Exception("Bank Update failed: " . $stmtB->error);
                        $stmtB->close();
                    } else {
                        // Insert
                        $sqlBank = "INSERT INTO hrm_EmployeeBankDetails (companyid, subcompanyid, projectid, EmployeeID, BankName, AccountNumber, IFSCCode) VALUES (?,?,?,?,?,?,?)";
                        $stmtB = $hrmconn->prepare($sqlBank);
                        if (!$stmtB)
                            throw new Exception("Prepare Bank Insert failed: " . $hrmconn->error);

                        $stmtB->bind_param("iiissss", $companyid, $subcompanyid, $projectid, $EmployeeID, $BankName, $AccountNumber, $IFSCCode);
                        if (!$stmtB->execute())
                            throw new Exception("Bank Insert failed: " . $stmtB->error);
                        $stmtB->close();
                    }
                }

                // 3. Update / Insert Contact Person Details (only if ContactPerson fields were submitted)
                if (isset($_POST['ContactPerson1_Name']) || isset($_POST['ContactPerson2_Name'])) {
                    $cp1_Name = $_POST['ContactPerson1_Name'] ?? '';
                    $cp1_Relation = $_POST['ContactPerson1_Relation'] ?? '';
                    $cp1_Mobile = $_POST['ContactPerson1_Mobile'] ?? '';
                    $cp1_Email = $_POST['ContactPerson1_Email'] ?? '';
                    $cp1_Address = $_POST['ContactPerson1_Address'] ?? '';
                    $cp1_PostalCode = $_POST['ContactPerson1_PostalCode'] ?? '';
                    $cp1_Note = $_POST['ContactPerson1_Note'] ?? '';

                    $cp2_Name = $_POST['ContactPerson2_Name'] ?? '';
                    $cp2_Relation = $_POST['ContactPerson2_Relation'] ?? '';
                    $cp2_Mobile = $_POST['ContactPerson2_Mobile'] ?? '';
                    $cp2_Email = $_POST['ContactPerson2_Email'] ?? '';
                    $cp2_Address = $_POST['ContactPerson2_Address'] ?? '';
                    $cp2_PostalCode = $_POST['ContactPerson2_PostalCode'] ?? '';
                    $cp2_Note = $_POST['ContactPerson2_Note'] ?? '';

                    if (!empty($cp1_Name)) {
                        insertOrUpdateEmployeeContactPersonDetails($hrmconn, $companyid, $subcompanyid, $projectid, $EmployeeID, $cp1_Name, $cp1_Relation, $cp1_Mobile, $cp1_Email, $cp1_PostalCode, $cp1_Address, $cp1_Note, 1);
                    }
                    if (!empty($cp2_Name)) {
                        insertOrUpdateEmployeeContactPersonDetails($hrmconn, $companyid, $subcompanyid, $projectid, $EmployeeID, $cp2_Name, $cp2_Relation, $cp2_Mobile, $cp2_Email, $cp2_PostalCode, $cp2_Address, $cp2_Note, 0);
                    }
                }

            $hrmconn->commit();
            $response = ['status' => 'Success', 'message' => 'Details updated successfully!'];
        } catch (Exception $e) {
            $hrmconn->rollback();
            $response = ['status' => 'Error', 'message' => $e->getMessage()];
        }
    }
    echo json_encode($response);
} else if ($action == "hrm_EmployeeDeductionComponent") {
    $companyid = isset($_POST['companyid']) ? $_POST['companyid'] : null;
    $subcompanyid = isset($_POST['subcompanyid']) ? $_POST['subcompanyid'] : null;
    $projectid = isset($_POST['projectid']) ? $_POST['projectid'] : null;
    $employeeid = isset($_POST['employeeid']) ? $_POST['employeeid'] : null;
    $eeempddt = getEmployeeDeductionsComponents($hrmconn, $companyid, $subcompanyid, $projectid, $employeeid);
    echo json_encode($eeempddt);
} else if ($action == "hrm_EmployeeEarningWithSalaryDetails") {
    $companyid = isset($_POST['companyid']) ? $_POST['companyid'] : null;
    $subcompanyid = isset($_POST['subcompanyid']) ? $_POST['subcompanyid'] : null;
    $projectid = isset($_POST['projectid']) ? $_POST['projectid'] : null;
    $employeeid = isset($_POST['employeeid']) ? $_POST['employeeid'] : null;
    if ($employeeid) {
        $epdees = getEmployeeEarningSalaryDetails($hrmconn, $companyid, $subcompanyid, $projectid, $employeeid);
        // print_r($epdees);
        if (count($epdees) == 0) {
            $epdees = getPayrollEarningsMasterList($hrmconn, $companyid, "defaultforemployee");
            // print_r($epdees)
        }
    } else {
        $epdees = getPayrollEarningsMasterList($hrmconn, $companyid, "defaultforemployee", "");
    }
    echo json_encode($epdees);
} else if ($action == "countrylist_callback") {
    $sortby = $_POST['sortby'];
    $sortbyvalue = $_POST['sortbyvalue'];
    $cctry = fetchCountryMaster($hrmconn, $sortby, $sortbyvalue);
    echo json_encode($cctry);
} else if ($action == "genderlist_callback") {
    $gndlist = fetchGenderMaster($hrmconn);
    echo json_encode($gndlist);
} else if ($action == "departmentlist_dropdown") {
    $shortby = $_POST['shortby'];
    $shortbyvalue = $_POST['shortbyvalue'];
    $companyid = $_POST['companyid'];
    $departlist = fetchDepartmentMaster($hrmconn, $companyid, $shortby, $shortbyvalue);
    echo json_encode($departlist);
} else if ($action == "designationlist_callback") {
    $companyid = $_POST['companyid'];
    $shortby = $_POST['shortby'];
    $shortbyvalue = $_POST['shortbyvalue'];
    $designationlist = fetchDesignationsMaster($hrmconn, $companyid, $shortby, $shortbyvalue);
    echo json_encode($designationlist);
} else if ($action == "courrencylist_callback") {
    $shortby = $_POST['shortby'];
    $shortbyvalue = $_POST['shortbyvalue'];
    $currencylist = fetchCurrencyMaster($hrmconn, $shortby, $shortbyvalue);
    echo json_encode($currencylist);
} else if ($action == "relationshiplist_callback") {
    $shortby = $_POST['shortby'];
    $shortbyvalue = $_POST['shortbyvalue'];
    $rsplist = fetchRelationships($hrmconn, $shortby, $shortbyvalue);
    echo json_encode($rsplist);
} else if ($action == "jobIndustrieslist_callback") {
    $shortby = $_POST['shortby'];
    $shortbyvalue = $_POST['shortbyvalue'];
    $indslist = getIndustries($hrmconn, $shortby, $shortbyvalue);
    echo json_encode($indslist);
} else if ($action == "jobtitlelist_callback") {
    $shortby = $_POST['shortby'];
    $shortbyvalue = $_POST['shortbyvalue'];
    $joblist = getJobTitles($hrmconn, $shortby, $shortbyvalue);
    echo json_encode($joblist);
} else if ($action == "employmenttypelist_callback") {
    $shortby = $_POST['shortby'];
    $shortbyvalue = $_POST['shortbyvalue'];
    $joblist = getEmploymentTypes($hrmconn, $shortby, $shortbyvalue);
    echo json_encode($joblist);
} else if ($action == "SaveOrUpdateDepartment") {
    $companyid = $_POST['companyid'];
    $departmentid = $_POST['departmentid'];
    $departmentname = $_POST['departmentname'];
    $loginname = $_POST['loginname'];
    $rrs = addOrUpdateDepartment($hrmconn, $departmentid, $departmentname, $companyid, $loginname);
    $rstmsg = [];
    if (strtolower($rrs) == "updated") {
        $rstmsg = array('status' => 'Success', 'message' => 'Department name updated successfully.');
    } else if (strtolower($rrs) == "inserted") {
        $rstmsg = array('status' => 'Success', 'message' => 'New Department name save successfully.');
    } else {
        $rstmsg = array('status' => 'Error', 'message' => 'Oops! Something wrong');
    }
    echo json_encode($rstmsg);
} else if ($action == "SaveOrUpdateDesignation") {
    $companyid = $_POST['companyid'];
    $designationname = $_POST['designationname'];
    $designationid = $_POST['designationid'];
    $loginname = $_POST['loginname'];
    $rrs = addOrUpdateDesignation($hrmconn, $designationid, $designationname, $companyid, $loginname);
    $rstmsg = [];
    if (strtolower($rrs) == "updated" || strtolower($rrs) == "nochange") {
        $rstmsg = array('status' => 'Success', 'message' => 'Designation name updated successfully.');
    } else if (strtolower($rrs) == "inserted") {
        $rstmsg = array('status' => 'Success', 'message' => 'New Designation name save successfully.');
    } else if (strtolower($rrs) == "duplicate") {
        $rstmsg = array('status' => 'Error', 'message' => 'Duplicate found of Designation name, Please Enter the other name ');
    } else {
        $rstmsg = array('status' => 'Error', 'message' => 'Oops! Something wrong');
    }
    echo json_encode($rstmsg);
} else if ($action == "hrm_deductioncomponentGlobal") {
    $shortby = $_POST['shortby'];
    $shortbyvalue = $_POST['shortbyvalue'];
    $ddst = getPayrollDeductionsMasterGlobalList($hrmconn, $shortby, $shortbyvalue);
    echo json_encode($ddst);
} else if ($action == "hrm_deductioncomponent") {
    $companyid = $_POST['companyid'];
    $shortby = $_POST['shortby'];
    $shortbyvalue = $_POST['shortbyvalue'];
    $ddst = getPayrollDeductionsMasterList($hrmconn, $companyid, $shortby, $shortbyvalue);
    echo json_encode($ddst);
} else if ($action == "hrm_earningcomponentGlobal") {
    $shortby = $_POST['shortby'];
    $shortbyvalue = $_POST['shortbyvalue'];
    $ddst = getPayrollEarningMasterGlobalList($hrmconn, $shortby, $shortbyvalue);
    echo json_encode($ddst);
} else if ($action == "hrm_earningcomponent") {
    $companyid = $_POST['companyid'];
    $shortby = $_POST['shortby'];
    $shortbyvalue = $_POST['shortbyvalue'];
    $ernst = getPayrollEarningsMasterList($hrmconn, $companyid, $shortby, $shortbyvalue);
    echo json_encode($ernst);
} else if ($action == "hrm_earningcomponentnew") {
    $companyid = $_POST['companyid'];
    $shortby = $_POST['shortby'];
    $shortbyvalue = $_POST['shortbyvalue'];
    $ernst = getPayrollEarningsMasterList($hrmconn, $companyid, $shortby, $shortbyvalue);
    // print_r($ernst);
    echo json_encode($ernst);
} else if ($action == "AddUpdate_EmployeeDeduction") {
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $employeeid = $_POST['employeeid'];
    // $deductionid = $_POST['deductionid'];
    $deductionid = json_decode($_POST['deductionid'], true);
    // print_r($deductionid);
    // die();
    if (count($deductionid) > 0) {
        $dddata = [];
        for ($i = 0; $i < count($deductionid); $i++) {
            $ccmdd = getPayrollDeductionsMasterList($hrmconn, $companyid, "DeductionId", $deductionid[$i]['id']);
            foreach ($ccmdd as $mmckey) {
                $dddata[] = [
                    "companyid" => $companyid,
                    "subcompanyid" => $subcompanyid,
                    "projectid" => $projectid,
                    "EmployeeId" => $employeeid,
                    "DeductionId" => $mmckey['DeductionId'],
                    "DeductionName" => $mmckey['DeductionName'],
                    "Frequency" => $mmckey['Frequency'],
                    "EmployeeDeductionType" => $mmckey['EmployeeDeductionType'],
                    "EmployeeDeductionValue" => $mmckey['EmployeeDeductionValue'],
                    "EmployeeDeductionBase" => $mmckey['EmployeeDeductionBase'],
                    "EmployerDeductionType" => $mmckey['EmployerDeductionType'],
                    "EmployerDeductionValue" => $mmckey['EmployerDeductionValue'],
                    "EmployerDeductionBase" => $mmckey['EmployerDeductionBase'],
                    "IsIncludedInCTC" => $mmckey['IsIncludedInCTC'],
                    "IsIncludedInFBP" => $mmckey['IsIncludedInFBP'],
                    "IsProRataBasis" => $mmckey['IsProRataBasis'],
                    "description" => $mmckey['description'],
                    "activestatus" => $deductionid[$i]['status']
                ];
            }
        }
        $rrsst = insertOrUpdateEmployeeDeductions($hrmconn, $companyid, $subcompanyid, $projectid, $employeeid, $dddata);
        echo json_encode($rrsst);
    } else {
        echo json_encode(array('status' => 'Error', 'message' => 'Something wrong, Try again'));
    }
} else if ($action == "AddUpdate_EmployeeEarnings") {
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $CurrencyCode = $_POST['currencyCode'];
    $ctcsalary = $_POST['ctcsalary'];
    $employeeid = $_POST['employeeid'];
    // die();
    $Slrydddata = [];
    $basicsalary = 0;
    $grosssalary = 0;
    $hra = 0;
    $conveyanceallowance = 0;
    $specialallowance = 0;
    $componentData = isset($_POST['componentData']) ? json_decode($_POST['componentData'], true) : [];
    // print_r($componentData);
    // die();
    if (is_array($componentData) && count($componentData) > 0) {
        $Erdddata = [];

        // Initialize variables
        $basicsalary = 0;
        $hra = 0;
        $conveyanceallowance = 0;
        $specialallowance = 0;

        foreach ($componentData as $ccmpkey) {
            $errncm = getPayrollEarningsMasterList($hrmconn, $companyid, "componentid", $ccmpkey['componentId']);
            // print_r($errncm);
            foreach ($errncm as $ermmkey) {
                // Match by ComponentId
                switch ($ermmkey['ComponentId']) {
                    case 1:
                        $basicsalary = $ccmpkey['componentAmount'];
                        break;
                    case 2:
                        $hra = $ccmpkey['componentAmount'];
                        break;
                    case 4:
                        $conveyanceallowance = $ccmpkey['componentAmount'];
                        break;
                    case 6:
                        $specialallowance = $ccmpkey['componentAmount'];
                        break;
                }

                $monthlyVal = isset($ccmpkey['monthlyAmount']) ? floatval($ccmpkey['monthlyAmount']) : round(floatval($ccmpkey['componentAmount']) / 12, 2);
                $percentVal = floatval($ccmpkey['percentValue']);

                if (strtolower($ermmkey['EmployeeEarningType']) === 'percentage' && $percentVal > 0) {
                    $earningVal = $percentVal;
                } else {
                    $earningVal = ($percentVal > 0) ? $percentVal : $monthlyVal;
                }

                $Erdddata[] = [
                    "companyid" => $companyid,
                    "subcompanyid" => $subcompanyid,
                    "projectid" => $projectid,
                    "EmployeeId" => $employeeid,
                    "ComponentId" => $ermmkey['ComponentId'],
                    "ComponentName" => $ermmkey['ComponentName'],
                    "Frequency" => $ermmkey['Frequency'],
                    "EmployeeEarningType" => $ermmkey['EmployeeEarningType'],
                    "EmployeeEarningValue" => $earningVal,
                    "EmployeeEarningBase" => $ermmkey['EmployeeEarningBase'],
                    "EmployerEarningType" => $ermmkey['EmployerEarningType'],
                    "EmployerEarningValue" => $ermmkey['EmployerEarningValue'],
                    "EmployerEarningBase" => $ermmkey['EmployerEarningBase'],
                    "IsIncludedInCTC" => $ermmkey['IsIncludedInCTC'],
                    "IsIncludedInFBP" => $ermmkey['IsIncludedInFBP'],
                    "IsProRataBasis" => $ermmkey['IsProRataBasis'],
                    "description" => $ermmkey['description']
                ];
            }
        }
        // print_r($Erdddata);
        // die();
        // echo "==".$basicsalary;
        $dojRes = $hrmconn->query("SELECT DateOfJoining FROM hrm_EmployeesMaster WHERE EmployeeID = '$employeeid' LIMIT 1");
        $empDOJ = ($dojRes && $dRow = $dojRes->fetch_assoc() && !empty($dRow['DateOfJoining']) && $dRow['DateOfJoining'] != '0000-00-00') ? $dRow['DateOfJoining'] : date('Y-m-d');

        $Slrydddata[] = [
            "companyid" => $companyid,
            "subcompanyid" => $subcompanyid,
            "projectid" => $projectid,
            "EmployeeID" => $employeeid,
            "affectedfrom" => $empDOJ,
            "CTC" => floatval($ctcsalary) ?? 0,
            "GrossSalary" => floatval($basicsalary + $hra + $conveyanceallowance + $specialallowance) ?? 0,
            "BasicSalary" => floatval($basicsalary) ?? 0,
            "HRA" => floatval($hra) ?? 0,
            "ConveyanceAllowance" => floatval($conveyanceallowance),
            "SpecialAllowance" => floatval($specialallowance) ?? 0,
            "Deductions" => 0,
            "PaymentDate" => '',
            "CurrencyCode" => $CurrencyCode ?? 'INR',
            "PaymentMethod" => '',
            "Comments" => ''
        ];
        // Debugging line — can remove after testing
        // print_r($Slrydddata); die();
        $Errsst = insertOrUpdateEmployeeSalary($hrmconn, $Slrydddata);
        $Errsst = insertOrUpdateEmployeeEarnings($hrmconn, $Erdddata);
        echo json_encode($Errsst);
    } else {
        echo json_encode(['status' => 'Error', 'message' => 'Something wrong, Try again']);
    }
} else if ($action == "fetchHolidayYearsFromMaster") {
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $hly = getHolidaysYearsFromMaster($hrmconn, $projectid);
    echo json_encode($hly);
} else if ($action == "fetchHolidayFromMaster_callback") {
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $holidayyear = $_POST['holidayyear'];
    $hhhl = getHolidaysFromMaster($hrmconn, $holidayyear, $companyid, $subcompanyid, $projectid);
    echo json_encode($hhhl);
} else if ($action == "fetchEmployeeBankDetails_callback") {
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $EmployeeId = $_POST['EmployeeId'];
    $empBank = getEmployeeBankDetails($hrmconn, $companyid, $subcompanyid, $projectid, $EmployeeId);
    echo json_encode($empBank);
} else if ($action == "EmployeeCalculatedSalary_callback") {
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $salaryyear = $_POST['salaryyear'];
    $salarymonth = $_POST['salarymonth'];
    $shortby = $_POST['shortby'];
    $shortbyvalue = $_POST['shortbyvalue'];
    $empcldSl = getEmployeeCalculatedSalary($hrmconn, $companyid, $subcompanyid, $projectid, $salaryyear, $salarymonth, $shortby, $shortbyvalue);
    echo json_encode($empcldSl);
} else if ($action == "fetchEmployeeSalarywithYear") {
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $salaryyear = $_POST['salaryyear'];
    $shortby = $_POST['shortby'];
    $shortbyvalue = $_POST['shortbyvalue'];

    $empcldSl = getEmployeeSalaryWithYear($hrmconn, $projectid, $salaryyear, $shortby, $shortbyvalue);
    // print_r($empcldSl);
    $payslipdata = [];
    if (count($empcldSl) > 0) {
        foreach ($empcldSl as $slckey) {
            $salaryyear = $slckey['SalaryYear'];
            $salarymonth = $slckey['SalaryMonth'];
            // employee data
            $empdata = fetchEmployeeData($hrmconn, $companyid, $subcompanyid, $projectid, "EmployeeID", $slckey['EmployeeID']);
            $EmployeeName = "";
            $DateOfBirth = "";
            $DateOfJoining = "";
            $DesignationID = "";
            $Email = "";
            $MobileNo = "";
            $PanNumber = "";
            $DOJDMY = "";
            $DesignationName = "";
            $DepartmentName = "";
            if (count($empdata) > 0) {
                $EmployeeName = $empdata[0]['FullName'];
                $DateOfBirth = $empdata[0]['DateOfBirth'];
                if ($empdata[0]['DateOfJoining']) {
                    $DOJDMY = date('d-M-Y', strtotime($empdata[0]['DateOfJoining']));
                }
                $DateOfJoining = $empdata[0]['DateOfJoining'];
                if ($empdata[0]['DesignationID']) {
                    $DsN = fetchDesignationsMaster($hrmconn, $companyid, 'DesignationID', $eDkey[0]['DesignationID']);
                    $DesignationName = $DsN[0]['DesignationName'];
                }
                $DesignationID = $empdata[0]['DesignationID'];
                if ($empdata[0]['DepartmentID']) {
                    $DpI = fetchDepartmentMaster($hrmconn, $companyid, 'DepartmentID', $empdata[0]['DepartmentID']);
                    $DepartmentName = $DpI[0]['DepartmentName'];
                }

                $Email = $empdata[0]['Email'];
                $MobileNo = $empdata[0]['MobileNo'];
                $PanNumber = $empdata[0]['PanNumber'];
            }

            // Employee Attendance Data
            $attdata = fetchEmployeeCalculatedAttendance($hrmconn, $companyid, $subcompanyid, $projectid, $slckey['SalaryYear'], $slckey['SalaryMonth'], 'EmployeeID', $slckey['EmployeeID']);
            $TotalDaysInMonth = "";
            $WorkingDaysInMonth = "";
            $PresentDays = "";
            $AbsentDays = "";
            $CalculatedPresentDays = "";
            if (count($attdata) > 0) {
                $TotalDaysInMonth = $attdata[0]['TotalDaysInMonth'];
                $WorkingDaysInMonth = $attdata[0]['WorkingDaysInMonth'];
                $PresentDays = $attdata[0]['PresentDays'];
                $AbsentDays = $attdata[0]['AbsentDays'];
                $CalculatedPresentDays = $attdata[0]['CalculatedPresentDays'];
            }

            // Employee Deductions
            $ddctdata = getEmployeeSalaryDeductions($hrmconn, $companyid, $subcompanyid, $projectid, $slckey['EmployeeID'], $slckey['SalaryYear'], $slckey['SalaryMonth']);
            $EPFDeduction = 0;
            $ESICDeduction = 0;
            $IncomeTaxDeduction = 0;
            $otherDeduction = 0;
            if (count($ddctdata) > 0) {
                foreach ($ddctdata as $ddctkey) {
                    if ($ddctkey['DeductionId'] == 1) {
                        $EPFDeduction = $ddctkey['DeductionAmount'];
                    } else if ($ddctkey['DeductionId'] == 3) {
                        $ESICDeduction = $ddctkey['DeductionAmount'];
                    } else if ($ddctkey['DeductionId'] == 4) {
                        $IncomeTaxDeduction = $ddctkey['DeductionAmount'];
                    } else {
                        $otherDeduction += $ddctkey['DeductionAmount'];
                    }
                }
            }
            $SalaryMonthYear = date('M-Y', strtotime($slckey['SalaryYear'] . '-' . $slckey['SalaryMonth'] . '-01'));
            // echo $EPFDeduction.
            $payslipdata[] = array('EmployeeName' => $EmployeeName, 'DateOfBirth' => $DateOfBirth, 'DateOfJoining' => $DateOfJoining, 'DesignationName' => $DesignationName, 'DepartmentName' => $DepartmentName, 'Email' => $Email, 'MobileNo' => $MobileNo, 'TotalDaysInMonth' => $TotalDaysInMonth, 'PresentDays' => $PresentDays, 'AbsentDays' => $AbsentDays, 'CalculatedPresentDays' => $CalculatedPresentDays, 'EPFDeduction' => $EPFDeduction, 'ESICDeduction' => $ESICDeduction, 'IncomeTaxDeduction' => $IncomeTaxDeduction, 'otherDeduction' => $otherDeduction, 'GrossSalary' => $slckey['GrossSalary'] ?? '0.00', 'BasicSalary' => $slckey['BasicSalary'] ?? '0.00', 'HRA' => $slckey['HRA'] ?? '0.00', 'Conveyance' => $slckey['Conveyance'] ?? '0.00', 'Allowances' => $slckey['Allowances'] ?? '0.00', 'NetSalary' => $slckey['NetSalary'], 'SalaryID' => $slckey['SalaryID'], 'SalaryYear' => $slckey['SalaryYear'], 'SalaryMonth' => $slckey['SalaryMonth'], 'SalaryMonthYear' => $SalaryMonthYear);

        }
    }
    echo json_encode($payslipdata);
} else if ($action == "SalaryYear_callback") {
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $empcldSl = getDistinctSalaryYears($hrmconn, $companyid, $subcompanyid, $projectid);
    echo json_encode($empcldSl);
} else if ($action == "EmployeeAttendanceYearMonth") {
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $shortby = $_POST['shortby'] ?? '';
    $shortbyvalue = $_POST['shortbyvalue'] ?? '';
    $attyearmonth = EmployeeAttendanceYearMonth($hrmconn, $projectid, $shortby, $shortbyvalue);
    echo json_encode($attyearmonth);
} else if ($action == "FullAttendanceDetailsOfEmployee") {
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $shortby = $_POST['shortby'] ?? '';
    $shortbyvalue = $_POST['shortbyvalue'] ?? '';
    $attsmry = FullAttendanceDetailsOfEmployee($hrmconn, $projectid, $shortby, $shortbyvalue);
    echo json_encode($attsmry);
} else if ($action == "EmployeeAttendanceSummaryDetails") {
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $EmployeeId = $_POST['EmployeeId'];
    $shortby = $_POST['shortby'] ?? '';
    $shortbyvalue = $_POST['shortbyvalue'] ?? '';
    $attsmry = getEmployeeAttendanceSummaryDetails($hrmconn, $projectid, $EmployeeId, $shortby, $shortbyvalue);
    echo json_encode($attsmry);
} else if ($action == "getEmployeeMonthlyAttendanceSummary") {
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $EmployeeId = $_POST['EmployeeId'];
    $attyear = $_POST['attyear'] ?? '';
    $attmonth = $_POST['attmonth'] ?? '';
    $attsmry = getEmployeeMonthlyAttendanceSummary($hrmconn, $projectid, $EmployeeId, $attyear, $attmonth);
    echo json_encode($attsmry);
} else if ($action == "EmployeeAttendanceDetails") {
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $EmployeeId = $_POST['EmployeeId'];
    $shortby = $_POST['shortby'] ?? '';
    $shortbyvalue = $_POST['shortbyvalue'] ?? '';
    $attsmry = EmployeeAttendanceDetails($hrmconn, $projectid, $EmployeeId, $shortby, $shortbyvalue);
    echo json_encode($attsmry);
} else if ($action == "UpdateEmployeeEditedAttendance") {
    $companyid = $_POST['companyid'] ?? '';
    $subcompanyid = $_POST['subcompanyid'] ?? '';
    $projectid = $_POST['projectid'] ?? '';
    $loginid = $_POST['loginid'] ?? '';
    $loginroleid = $_POST['loginroleid'] ?? '';

    $editremark = $_POST['editremark'] ?? '';
    $EditedAttDataList = $_POST['EditedAttDataList'] ?? '[]';
    $EditedAttDataList = json_decode($EditedAttDataList, true);
    // print_r($EditedAttDataList);
    if (empty($EditedAttDataList)) {
        echo json_encode(["status" => "error", "message" => "No attendance data received."]);
        exit;
    }

    if (empty(trim($editremark))) {
        echo json_encode(["status" => "error", "message" => "Remarks / reason for edit is required."]);
        exit;
    }

    $uphotoPath = null;
    $EmployeeID = !empty($EditedAttDataList) ? ($EditedAttDataList[0]['EmployeeID'] ?? '') : '';
    if (!empty($EmployeeID) && isset($_FILES['ProofFile']) && is_uploaded_file($_FILES['ProofFile']['tmp_name'])) {
        $uploadDir = __DIR__ . "/../attachementfile/" . $companyid . "/" . $subcompanyid . "/" . $projectid . "/attendance_proofs/" . $EmployeeID . "/" . date('Ymd') . "/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $photoName = "proof_" . $EmployeeID . "_" . time() . "_" . basename($_FILES['ProofFile']['name']);
        $photoPath = $uploadDir . $photoName;
        if (move_uploaded_file($_FILES['ProofFile']['tmp_name'], $photoPath)) {
            $uphotoPath = "/../attachementfile/" . $companyid . "/" . $subcompanyid . "/" . $projectid . "/attendance_proofs/" . $EmployeeID . "/" . date('Ymd') . "/" . $photoName;
        }
    }

    if (!empty($EditedAttDataList)) {
        $firstRow = $EditedAttDataList[0];
        $testMarkInTime = !empty($firstRow['MarkInTime']) ? date('Y-m-d H:i:s', strtotime($firstRow['MarkInTime'])) : null;
        if ($testMarkInTime) {
            $year = intval(date('Y', strtotime($testMarkInTime)));
            $month = intval(date('m', strtotime($testMarkInTime)));
            $checkSalStmt = $hrmconn->prepare("SELECT COUNT(*) as count FROM hrm_EmployeeCalculatedSalary WHERE ProjectID = ? AND EmployeeID = ? AND SalaryYear = ? AND SalaryMonth = ?");
            if ($checkSalStmt) {
                $checkSalStmt->bind_param("ssii", $projectid, $EmployeeID, $year, $month);
                $checkSalStmt->execute();
                $resSal = $checkSalStmt->get_result()->fetch_assoc();
                $checkSalStmt->close();
                if ($resSal && $resSal['count'] > 0) {
                    echo json_encode(["status" => "error", "message" => "Attendance action did not perform due to salary has been calculated"]);
                    exit;
                }
            }
        }
    }

    foreach ($EditedAttDataList as $row) {
        $attendanceID = $row['attendanceID'] ?? '';
        $EmployeeID = $row['EmployeeID'] ?? '';

        if (!empty($row['isDeleted'])) {
            continue;
        }

        $MarkInTime = !empty($row['MarkInTime']) ? date('Y-m-d H:i:s', strtotime($row['MarkInTime'])) : null;
        $MarkOutTime = !empty($row['MarkOutTime']) ? date('Y-m-d H:i:s', strtotime($row['MarkOutTime'])) : null;

        if (empty($attendanceID) || empty($EmployeeID) || empty($MarkInTime) || empty($MarkOutTime)) {
            echo json_encode(["status" => "error", "message" => "Mark In and Mark Out times are required for all records."]);
            exit;
        }

        // Check for duplicate entry with the same CheckInTime and CheckOutTime for the same EmployeeID and ProjectID
        $checkStmt = $hrmconn->prepare("SELECT AttendanceID FROM hrm_EmployeeAttendance WHERE ProjectID = ? AND EmployeeID = ? AND CheckInTime = ? AND CheckOutTime = ? AND AttendanceID != ?");
        if ($checkStmt) {
            $checkStmt->bind_param("iissi", $projectid, $EmployeeID, $MarkInTime, $MarkOutTime, $attendanceID);
            $checkStmt->execute();
            $checkStmt->store_result();
            if ($checkStmt->num_rows > 0) {
                echo json_encode([
                    "status" => "exists",
                    "message" => "An attendance record with the same check-in and check-out time already exists for this employee."
                ]);
                $checkStmt->close();
                exit;
            }
            $checkStmt->close();
        }
    }

    $loginid = $_POST['loginid'] ?? '';
    $loginroleid = isset($_POST['loginroleid']) && $_POST['loginroleid'] !== '' ? intval($_POST['loginroleid']) : null;
    if ($loginroleid === null && !empty($_SESSION['loginroleid'])) {
        $loginroleid = intval($_SESSION['loginroleid']);
    }
    if ($loginroleid === null && !empty($loginid)) {
        $logininfo = hrm_loginuserinfo($authconn, "", "loginid", $loginid);
        if (!empty($logininfo) && isset($logininfo[0]['loginroleid'])) {
            $loginroleid = intval($logininfo[0]['loginroleid']);
        }
    }
    if ($loginroleid === null) {
        $loginroleid = 4;
    }

    $loginname = $_POST['loginname'] ?? '';
    if (empty($loginname) && !empty($loginid)) {
        $logininfo = hrm_loginuserinfo($authconn, "", "loginid", $loginid);
        $loginname = $logininfo[0]['loginname'] ?? $loginid;
    }
    if (empty($loginname)) {
        $loginname = $_SESSION['loginname'] ?? 'User';
    }

    $updatedCount = 0;
    $errorCount = 0;
    $applydatetime = date('Y-m-d H:i:s');
    // ✅ Prepare SQL based on role: loginroleid <= 2 auto approves, > 2 sets pending
    if ($loginroleid <= 2) {
        $updateStmt = $hrmconn->prepare("UPDATE hrm_EmployeeAttendance SET CheckInTime = ?, CheckOutTime = ?, WorkingHours = ?, Status = 'Present', ApprovalStatus = 'Approved', ApprovedBy = ?, ApprovedDateTime = NOW(), Remarks=?, editapplydate=?, misattendancefile=COALESCE(?, misattendancefile) WHERE AttendanceID = ? AND EmployeeID = ? AND ProjectID = ?");
    } else {
        $updateStmt = $hrmconn->prepare("UPDATE hrm_EmployeeAttendance SET CheckInTime = ?, CheckOutTime = ?, WorkingHours = ?, Status = 'Edited', ApprovalStatus = 'Pending', Remarks=?, editapplydate=?, misattendancefile=COALESCE(?, misattendancefile) WHERE AttendanceID = ? AND EmployeeID = ? AND ProjectID = ?");
    }
    $delStmt = $hrmconn->prepare("UPDATE hrm_EmployeeAttendance SET Status = 'Removed', ApprovalStatus = 'Remove', tedit = ? WHERE AttendanceID = ? AND ProjectID = ?");

    if (!$updateStmt) {
        echo json_encode(["status" => "error", "message" => "Failed to prepare SQL statement."]);
        exit;
    }
    foreach ($EditedAttDataList as $row) {
        $attendanceID = $row['attendanceID'] ?? '';
        $EmployeeID = $row['EmployeeID'] ?? '';

        if (!empty($row['isDeleted']) && !empty($attendanceID)) {
            if ($delStmt) {
                $teditMsg = "Removed by " . $loginname . " on " . date('Y-m-d H:i:s');
                $delStmt->bind_param("sss", $teditMsg, $attendanceID, $projectid);
                if ($delStmt->execute()) {
                    $updatedCount++;
                } else {
                    error_log("delStmt execute failed: " . $delStmt->error);
                    $errorCount++;
                }
            }
            continue;
        }

        $MarkInTime = !empty($row['MarkInTime']) ? date('Y-m-d H:i:s', strtotime($row['MarkInTime'])) : null;
        $MarkOutTime = !empty($row['MarkOutTime']) ? date('Y-m-d H:i:s', strtotime($row['MarkOutTime'])) : null;
        if (empty($attendanceID) || empty($EmployeeID)) {
            $errorCount++;
            continue;
        }
        $WorkingHours = '';
        if (!empty($MarkInTime) && !empty($MarkOutTime)) {
            try {
                $in = new DateTime($MarkInTime);
                $out = new DateTime($MarkOutTime);
                $interval = $in->diff($out);
                $WorkingHours = $interval->format('%H:%I');
            } catch (Exception $e) {
                $WorkingHours = '';
            }
        }
        if ($updateStmt) {
            if ($loginroleid <= 2) {
                $updateStmt->bind_param("ssssssssss", $MarkInTime, $MarkOutTime, $WorkingHours, $loginname, $editremark, $applydatetime, $uphotoPath, $attendanceID, $EmployeeID, $projectid);
            } else {
                $updateStmt->bind_param("sssssssss", $MarkInTime, $MarkOutTime, $WorkingHours, $editremark, $applydatetime, $uphotoPath, $attendanceID, $EmployeeID, $projectid);
            }

            if ($updateStmt->execute()) {
                $updatedCount++;
            } else {
                error_log("updateStmt execute failed: " . $updateStmt->error);
                $errorCount++;
            }
        }
    }
    if ($updateStmt) $updateStmt->close();
    if ($delStmt) $delStmt->close();
    
    if ($updatedCount > 0) {
        echo json_encode([
            "status" => "success",
            "message" => "Successfully processed {$updatedCount} record(s)." .
                ($errorCount > 0 ? " {$errorCount} record(s) failed." : "")
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "No attendance records were updated."
        ]);
    }
} else if ($action == "DeleteEmployeeAttendance") {
    $companyid = $_POST['companyid'] ?? '';
    $subcompanyid = $_POST['subcompanyid'] ?? '';
    $projectid = $_POST['projectid'] ?? '';
    $loginid = $_POST['loginid'] ?? '';
    $loginname = $_POST['loginname'] ?? '';
    $loginroleid = intval($_POST['loginroleid'] ?? 4);
    $EmployeeID = $_POST['EmployeeID'] ?? '';
    $attDate = $_POST['AttendanceDate'] ?? '';

    if (empty($EmployeeID) || empty($attDate)) {
        echo json_encode(["status" => "error", "message" => "Employee ID and Attendance Date are required."]);
        exit;
    }

    if (empty($loginname) && !empty($loginid)) {
        $logininfo = hrm_loginuserinfo($authconn, "", "loginid", $loginid);
        $loginname = $logininfo[0]['loginname'] ?? $loginid;
    }
    if (empty($loginname)) {
        $loginname = $_SESSION['loginname'] ?? 'User';
    }

    // Role check: Employee (role > 3) can only remove if attendance is Pending
    if ($loginroleid > 3) {
        $chk = $hrmconn->prepare("SELECT ApprovalStatus FROM hrm_EmployeeAttendance WHERE EmployeeID = ? AND AttendanceDate = ? AND ProjectID = ?");
        if ($chk) {
            $chk->bind_param("sss", $EmployeeID, $attDate, $projectid);
            $chk->execute();
            $res = $chk->get_result();
            while ($r = $res->fetch_assoc()) {
                if (strtolower($r['ApprovalStatus'] ?? '') === 'approved') {
                    echo json_encode(["status" => "error", "message" => "Approved attendance cannot be removed by employees."]);
                    $chk->close();
                    exit;
                }
            }
            $chk->close();
        }
    }

    $teditMsg = "Removed by " . $loginname . " on " . date('Y-m-d H:i:s');
    $del = $hrmconn->prepare("UPDATE hrm_EmployeeAttendance SET Status = 'Removed', ApprovalStatus = 'Remove', tedit = ? WHERE EmployeeID = ? AND AttendanceDate = ? AND ProjectID = ?");
    if ($del) {
        $del->bind_param("ssss", $teditMsg, $EmployeeID, $attDate, $projectid);
        if ($del->execute()) {
            echo json_encode(["status" => "success", "message" => "Attendance request removed successfully."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to remove attendance: " . $del->error]);
        }
        $del->close();
    } else {
        echo json_encode(["status" => "error", "message" => "Database error: " . $hrmconn->error]);
    }
} else if ($action == "DeleteEmployeePunchAttendance") {
    $companyid = $_POST['companyid'] ?? '';
    $subcompanyid = $_POST['subcompanyid'] ?? '';
    $projectid = $_POST['projectid'] ?? '';
    $loginid = $_POST['loginid'] ?? '';
    $loginname = $_POST['loginname'] ?? '';
    $loginroleid = intval($_POST['loginroleid'] ?? 4);
    $EmployeeID = $_POST['empid'] ?? $_POST['EmployeeID'] ?? '';
    $attID = $_POST['attid'] ?? $_POST['AttendanceID'] ?? '';

    if (empty($EmployeeID) || empty($attID)) {
        echo json_encode(["status" => "error", "message" => "Employee ID and Attendance ID are required."]);
        exit;
    }

    if (empty($loginname) && !empty($loginid)) {
        $logininfo = hrm_loginuserinfo($authconn, "", "loginid", $loginid);
        $loginname = $logininfo[0]['loginname'] ?? $loginid;
    }
    if (empty($loginname)) {
        $loginname = $_SESSION['loginname'] ?? 'User';
    }

    // Role check: Employee (role > 3) can only remove if attendance is Pending
    if ($loginroleid > 3) {
        $chk = $hrmconn->prepare("SELECT ApprovalStatus FROM hrm_EmployeeAttendance WHERE EmployeeID = ? AND AttendanceID = ? AND ProjectID = ?");
        if ($chk) {
            $chk->bind_param("sss", $EmployeeID, $attID, $projectid);
            $chk->execute();
            $res = $chk->get_result();
            while ($r = $res->fetch_assoc()) {
                if (strtolower($r['ApprovalStatus'] ?? '') === 'approved') {
                    echo json_encode(["status" => "error", "message" => "Approved attendance cannot be removed by employees."]);
                    $chk->close();
                    exit;
                }
            }
            $chk->close();
        }
    }

    $teditMsg = "Removed by " . $loginname . " on " . date('Y-m-d H:i:s');
    $del = $hrmconn->prepare("UPDATE hrm_EmployeeAttendance SET Status = 'Removed', ApprovalStatus = 'Remove', tedit = ? WHERE EmployeeID = ? AND AttendanceID = ? AND ProjectID = ?");
    if ($del) {
        $del->bind_param("ssss", $teditMsg, $EmployeeID, $attID, $projectid);
        if ($del->execute()) {
            echo json_encode(["status" => "success", "message" => "Attendance request removed successfully."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to remove attendance: " . $del->error]);
        }
        $del->close();
    } else {
        echo json_encode(["status" => "error", "message" => "Database error: " . $hrmconn->error]);
    }
} else if ($action == "UpdateEmployeeMissingAttendance") {
    $companyid = $_POST['companyid'] ?? '';
    $subcompanyid = $_POST['subcompanyid'] ?? '';
    $projectid = $_POST['projectid'] ?? '';
    $loginid = $_POST['loginid'] ?? '';
    $EmployeeId = $_POST['EmployeeId'] ?? '';
    $AttendanceDate = $_POST['AttendanceDate'] ?? '';
    $MarkInDatetime = $_POST['MarkInDatetime'] ?? '';
    $MarkOutDatetime = $_POST['MarkOutDatetime'] ?? '';
    $Addattremark = $_POST['Addattremark'] ?? '';

    // ✅ Sanitize undefined/null values
    foreach (['AttendanceDate', 'MarkInDatetime', 'MarkOutDatetime'] as $key) {
        if (isset($$key) && (strtolower($$key) === 'undefined' || strtolower($$key) === 'null')) {
            $$key = '';
        }
    }

    // ✅ Ensure required fields
    if (empty($companyid) || empty($projectid) || empty($EmployeeId) || empty($MarkInDatetime)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
        exit;
    }

    if (empty(trim($Addattremark))) {
        echo json_encode(['status' => 'error', 'message' => 'Remarks / reason for missing attendance is required.']);
        exit;
    }

    // ✅ Extract AttendanceDate if missing
    if (empty($AttendanceDate)) {
        $AttendanceDate = date('Y-m-d', strtotime($MarkInDatetime));
    }

    // Check if salary is calculated for this month
    if (!empty($AttendanceDate)) {
        $year = intval(date('Y', strtotime($AttendanceDate)));
        $month = intval(date('m', strtotime($AttendanceDate)));
        $checkSalStmt = $hrmconn->prepare("SELECT COUNT(*) as count FROM hrm_EmployeeCalculatedSalary WHERE ProjectID = ? AND EmployeeID = ? AND SalaryYear = ? AND SalaryMonth = ?");
        if ($checkSalStmt) {
            $checkSalStmt->bind_param("ssii", $projectid, $EmployeeId, $year, $month);
            $checkSalStmt->execute();
            $resSal = $checkSalStmt->get_result()->fetch_assoc();
            $checkSalStmt->close();
            if ($resSal && $resSal['count'] > 0) {
                echo json_encode(["status" => "error", "message" => "Attendance action did not perform due to salary has been calculated"]);
                exit;
            }
        }
    }

    // ✅ If CheckInTime or CheckOutTime has no date part, prefix AttendanceDate
    if (!empty($MarkInDatetime) && !preg_match('/\d{4}-\d{2}-\d{2}/', $MarkInDatetime)) {
        $MarkInDatetime = $AttendanceDate . ' ' . $MarkInDatetime;
    }
    if (!empty($MarkOutDatetime) && !preg_match('/\d{4}-\d{2}-\d{2}/', $MarkOutDatetime)) {
        $MarkOutDatetime = $AttendanceDate . ' ' . $MarkOutDatetime;
    }

    // ✅ Check if attendance already exists for this date
    $checkSql = "SELECT AttendanceID FROM hrm_EmployeeAttendance 
                 WHERE Status not in('Rejected') AND ProjectID = ? 
                   AND EmployeeID = ? 
                   AND AttendanceDate = ?";

    $checkStmt = $hrmconn->prepare($checkSql);
    $checkStmt->bind_param("sis", $projectid, $EmployeeId, $AttendanceDate);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        echo json_encode([
            'status' => 'exists',
            'message' => 'Attendance already exists for this date. You cannot add a new attendance entry. If you need to make changes, please edit the existing attendance.'
        ]);
        $checkStmt->close();
        $hrmconn->close();
        exit;
    }
    $checkStmt->close();

    $uphotoPath = null;
    if (isset($_FILES['ProofFile']) && is_uploaded_file($_FILES['ProofFile']['tmp_name'])) {
        $uploadDir = __DIR__ . "/../attachementfile/" . $companyid . "/" . $subcompanyid . "/" . $projectid . "/attendance_proofs/" . $EmployeeId . "/" . date('Ymd') . "/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $photoName = "proof_" . $EmployeeId . "_" . time() . "_" . basename($_FILES['ProofFile']['name']);
        $photoPath = $uploadDir . $photoName;
        if (move_uploaded_file($_FILES['ProofFile']['tmp_name'], $photoPath)) {
            $uphotoPath = "/../attachementfile/" . $companyid . "/" . $subcompanyid . "/" . $projectid . "/attendance_proofs/" . $EmployeeId . "/" . date('Ymd') . "/" . $photoName;
        }
    }

    // ✅ Calculate working hours
    // UPDATE hrmplus.hrm_EmployeeAttendance SET WorkingHours = TIMEDIFF(CheckOutTime, CheckInTime) WHERE CheckInTime IS NOT NULL AND CheckOutTime IS NOT NULL AND CheckOutTime > CheckInTime;
    $WorkingHours = '';
    if (!empty($MarkInDatetime) && !empty($MarkOutDatetime)) {
        try {
            $in = new DateTime($MarkInDatetime);
            $out = new DateTime($MarkOutDatetime);
            $interval = $in->diff($out);
            $WorkingHours = $interval->format('%H:%I');
        } catch (Exception $e) {
            $WorkingHours = '';
        }
    }

    $loginroleid = isset($_POST['loginroleid']) && $_POST['loginroleid'] !== '' ? intval($_POST['loginroleid']) : null;
    if ($loginroleid === null && !empty($_SESSION['loginroleid'])) {
        $loginroleid = intval($_SESSION['loginroleid']);
    }
    if ($loginroleid === null && !empty($loginid)) {
        $logininfo = hrm_loginuserinfo($authconn, "", "loginid", $loginid);
        if (!empty($logininfo) && isset($logininfo[0]['loginroleid'])) {
            $loginroleid = intval($logininfo[0]['loginroleid']);
        }
    }
    if ($loginroleid === null) {
        $loginroleid = 4;
    }

    $loginname = $_POST['loginname'] ?? '';
    if (empty($loginname) && !empty($loginid)) {
        $logininfo = hrm_loginuserinfo($authconn, "", "loginid", $loginid);
        $loginname = $logininfo[0]['loginname'] ?? $loginid;
    }
    if (empty($loginname)) {
        $loginname = $_SESSION['loginname'] ?? 'User';
    }

    // ✅ Insert new attendance record based on role
    if ($loginroleid <= 2) {
        $statusVal = 'Present';
        $approvalVal = 'Approved';
        $insertSql = "INSERT INTO hrm_EmployeeAttendance 
        (ProjectID, EmployeeID, AttendanceDate, CheckInTime, CheckOutTime, WorkingHours, Status, ApprovalStatus, ApprovedBy, ApprovedDateTime, Remarks, created_by, misattendancefile) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)";
        $insertStmt = $hrmconn->prepare($insertSql);
        if (!$insertStmt) {
            echo json_encode(['status' => 'error', 'message' => 'Database prepare error: ' . $hrmconn->error]);
            exit;
        }
        $insertStmt->bind_param(
            "sissssssssss",
            $projectid,
            $EmployeeId,
            $AttendanceDate,
            $MarkInDatetime,
            $MarkOutDatetime,
            $WorkingHours,
            $statusVal,
            $approvalVal,
            $loginname,
            $Addattremark,
            $loginid,
            $uphotoPath
        );
    } else {
        $statusVal = 'Add Missing Attendance';
        $approvalVal = 'Pending';
        $insertSql = "INSERT INTO hrm_EmployeeAttendance 
        (ProjectID, EmployeeID, AttendanceDate, CheckInTime, CheckOutTime, WorkingHours, Status, ApprovalStatus, Remarks, created_by, misattendancefile) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insertStmt = $hrmconn->prepare($insertSql);
        if (!$insertStmt) {
            echo json_encode(['status' => 'error', 'message' => 'Database prepare error: ' . $hrmconn->error]);
            exit;
        }
        $insertStmt->bind_param(
            "sisssssssss",
            $projectid,
            $EmployeeId,
            $AttendanceDate,
            $MarkInDatetime,
            $MarkOutDatetime,
            $WorkingHours,
            $statusVal,
            $approvalVal,
            $Addattremark,
            $loginid,
            $uphotoPath
        );
    }

    if ($insertStmt->execute()) {
        $successMsg = ($loginroleid <= 2) ? 'Attendance marked and approved successfully.' : 'Attendance marked successfully and pending for approval.';
        echo json_encode(['status' => 'success', 'message' => $successMsg]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error inserting attendance: ' . $insertStmt->error]);
    }

    $insertStmt->close();
    $hrmconn->close();

} else if ($action == "holidayType_callback") {
    $hltype = getHolidayTypes($hrmconn);
    echo json_encode($hltype);
} else if ($action == "addupdate_holidaymaster") {
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $holidayid = $_POST['holidayid'];
    $holidayname = $_POST['holidayname'];
    $holidaydate = $_POST['holidaydate'];
    $holidaytype = $_POST['holidaytype'];
    $loginname = $_POST['loginname'];
    $actionfordo = $_POST['actionfordo'];
    $rsmsg = [];
    if ($actionfordo == "edit" && $holidayid != "") {
        $qryStm = $hrmconn->prepare("UPDATE hrm_PayrollHolidaysMaster SET HolidayName = ?,HolidayDate = ?,HolidayType = ? WHERE companyid = ? AND projectid = ? and id = ?");
        $qryStm->bind_param('ssssss', $holidayname, $holidaydate, $holidaytype, $companyid, $projectid, $holidayid);
    } else if ($actionfordo == "remove" && $holidayid != "") {
        $qryStm = $hrmconn->prepare("UPDATE hrm_PayrollHolidaysMaster SET tedit = ? WHERE companyid = ? AND projectid = ? and id = ?");
        $remove = "remove on date " . date('Y-m-d') . ' By ' . $loginname;
        $qryStm->bind_param('ssss', $remove, $companyid, $projectid, $holidayid);
    } else if (($actionfordo == "add" || $actionfordo == "new") && $holidayid == "") {
        $qryStm = $hrmconn->prepare("INSERT INTO hrm_PayrollHolidaysMaster(HolidayName,HolidayDate,HolidayType,companyid,subcompanyid,projectid) VALUES(?,?,?,?,?,?)");
        $qryStm->bind_param('ssssss', $holidayname, $holidaydate, $holidaytype, $companyid, $subcompanyid, $projectid);
    }
    if ($qryStm->execute()) {
        if ($actionfordo == "add") {
            $rsmsg = ['status' => 'Success', 'message' => 'New Holiday add in master successfully'];
        } else if ($actionfordo == "edit") {
            $rsmsg = ['status' => 'Success', 'message' => 'Holiday details update in master successfully'];
        } else if ($actionfordo == "remove") {
            $rsmsg = ['status' => 'Success', 'message' => 'Holiday details remove from master successfully'];
        }
    } else {
        $rsmsg = ['status' => 'Error', 'message' => $hrmconn->error];
    }
    echo json_encode($rsmsg);
} else if ($action == "LeaveTypeMaster_callback") {
    $companyid = $_POST['companyid'] ?? '';
    // Check if it's a delete request
    $postAction = $_POST['action'] ?? '';
    if ($postAction === 'delete') {
        $leaveid = $_POST['leave_id'] ?? '';
        if ($leaveid) {
            // Call delete function (ensure deleteLeaveType is defined in hrm_FunctionMaster.php)
            // FunctionMaster update was appended, so deleteLeaveType should be available
            $delResult = deleteLeaveType($hrmconn, $leaveid);
            echo json_encode($delResult);
        } else {
            echo json_encode(['status' => 'Error', 'message' => 'Leave ID missing for deletion.']);
        }
    } else {
        // Fetch Logic
        $sortBy = $_POST['sortby'] ?? '';
        $sortByValue = $_POST['sortbyvalue'] ?? '';
        $ltype = getLeaveTypeMasterList($hrmconn, $companyid, $sortBy, $sortByValue);
        echo json_encode($ltype);
    }
} else if ($action == "EmployeeLeaveCountDetails") {
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $EmployeeId = $_POST['EmployeeId'];
    $LeaveYear = $_POST['LeaveYear'] ?? date('Y');
    $ltype = getLeaveTypeMasterList($hrmconn, $companyid, '', '');
    $emplytd = [];
    if (!empty($ltype)) {
        foreach ($ltype as $ltykey) {
            $lytcount = getApprovedLeavesCount($hrmconn, $projectid, $EmployeeId, $LeaveYear, $ltykey['leave_code'], '', '');

            // Safe extraction
            $used = 0;
            if (!empty($lytcount) && isset($lytcount[0]['ApprovedDays'])) {
                $used = (int) $lytcount[0]['ApprovedDays'];
            }
            $allowed = (int) ($ltykey['max_days'] ?? 0);
            if ($allowed <= 0 && in_array(strtoupper($ltykey['leave_code']), ['SL', 'CL', 'EL', 'PL'])) {
                $allowed = 12;
            }
            $balance = max(0, $allowed - $used);
            $emplytd[] = [
                'leavetype' => $ltykey['leave_code'],
                'leavename' => $ltykey['leave_name'],
                'leaveallowed' => $allowed,
                'leaveused' => $used,
                'balanceleave' => $balance,
                'ltyc' => $lytcount   // keep raw data
            ];
        }
    }
    $emplyd = getApprovedLeavesFullDetails($hrmconn, $projectid, $EmployeeId, $LeaveYear);
    if (!empty($emplyd)) {
        foreach ($emplyd as $key => $empl) {
            // Get leave type name
            $ltype = getLeaveTypeMasterList($hrmconn, $companyid, 'leave_code', $empl['LeaveType']);
            $leaveName = isset($ltype[0]['leave_name']) ? $ltype[0]['leave_name'] : '';
            // Add new key to the same array
            $emplyd[$key]['leaveName'] = $leaveName;
        }
    }
    $emplyfulldata = ['fullleavedetails' => $emplyd, 'leavesummarydata' => $emplytd];
    echo json_encode($emplyfulldata);
} else if ($action == "addupdateleavedata") {
    $companyid = $_POST['companyid'];
    $leave_name = $_POST['leave_name'];
    $leave_code = $_POST['leave_code'];
    $max_days = $_POST['max_days'];
    $leave_type = $_POST['leave_type'];
    $carry_forward = $_POST['carry_forward'];
    $description = $_POST['description'];
    $leaveid = $_POST['leaveid'];
    if ($leaveid) {
        $rstmsg = updateLeaveDetails($hrmconn, $leaveid, $leave_name, $leave_type, $max_days, $carry_forward, $description);
    } else {
        $rstmsg = insertLeaveDetails($hrmconn, $companyid, $leave_code, $leave_name, $leave_type, $max_days, $carry_forward, $description);
    }
    echo json_encode($rstmsg);
} else if ($action == "AttendanceLocationMaster_callback") {
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $sortBy = $_POST['shortby'];
    $sortByValue = $_POST['shortbyvalue'];
    $locationmaster = getAttendanceLocationMaster($hrmconn, $companyid, $subcompanyid, $projectid, $sortBy, $sortByValue);
    echo json_encode($locationmaster);
} else if ($action == "savelocationlatlng") {
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $sno = $_POST['sno'];
    $lat = $_POST['lat'];
    $lng = $_POST['lng'];
    $sql = "UPDATE hrm_AttendanceLocationMaster SET OfficeLat = ?, OfficeLng = ? WHERE sno = ?";
    $stmt = $hrmconn->prepare($sql);
    $stmt->bind_param("ssi", $lat, $lng, $sno);
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Coordinates updated"]);
    } else {
        echo json_encode(["status" => "error", "message" => $stmt->error]);
    }
} else if ($action == "addupdatelocation") {
    // Get POST data
    $companyId = $_POST['companyid'] ?? '';
    $subCompanyId = $_POST['subcompanyid'] ?? '';
    $projectId = $_POST['projectid'] ?? '';
    $locationId = $_POST['locationId'] ?? ''; // empty for insert
    $locationName = $_POST['locationName'] ?? '';
    $locationAddress = $_POST['locationAddress'] ?? '';
    $comments = $_POST['comments'] ?? '';

    // Validate required fields
    if ($companyId == "" || $subCompanyId == "" || $projectId == "" || $locationName == "") {
        echo json_encode(["status" => "error", "message" => "Required fields missing"]);
        exit;
    }

    try {
        $shortname = $_POST['shortname'] ?? '';
        if ($locationId == "") {
            // INSERT
            $locationId = uniqid("LOC"); // generate unique LocationId
            $stmt = $hrmconn->prepare("INSERT INTO hrm_AttendanceLocationMaster 
                (LocationId, CompanyID, SubCompanyID, ProjectID, LocationName, LocationAddress, Comments, shortname) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

            if (!$stmt)
                throw new Exception("Prepare failed: " . $hrmconn->error);

            $stmt->bind_param("ssssssss", $locationId, $companyId, $subCompanyId, $projectId, $locationName, $locationAddress, $comments, $shortname);

            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Location added successfully", "locationId" => $locationId]);
            } else {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            $stmt->close();

        } else {
            // UPDATE
            $stmt = $hrmconn->prepare("UPDATE hrm_AttendanceLocationMaster 
                SET LocationName = ?, LocationAddress = ?, Comments = ?, shortname = ? 
                WHERE LocationId = ?");

            if (!$stmt)
                throw new Exception("Prepare failed: " . $hrmconn->error);

            $stmt->bind_param("sssss", $locationName, $locationAddress, $comments, $shortname, $locationId);

            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Location updated successfully", "locationId" => $locationId]);
            } else {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    $hrmconn->close();
} else if ($action == "checkSalaryCalculated") {
    $projectid = $_POST['projectid'] ?? '';
    $attendanceDate = $_POST['attendanceDate'] ?? '';
    if (empty($projectid) || empty($attendanceDate)) {
        echo json_encode(["status" => "error", "message" => "Project ID and Attendance Date are required."]);
        exit;
    }
    $parts = explode('-', $attendanceDate);
    if (count($parts) >= 2) {
        $year = intval($parts[0]);
        $month = intval($parts[1]);
        $stmt = $hrmconn->prepare("SELECT COUNT(*) as count FROM hrm_EmployeeCalculatedSalary WHERE ProjectID = ? AND SalaryYear = ? AND SalaryMonth = ?");
        if ($stmt) {
            $stmt->bind_param("sii", $projectid, $year, $month);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($res && $res['count'] > 0) {
                echo json_encode(["status" => "success", "calculated" => true]);
                exit;
            }
        }
    }
    echo json_encode(["status" => "success", "calculated" => false]);
    exit;
} else if ($action == "saveEmployeeAttendanceLocationExemption") {
    $CompanyID = $_POST['companyid'] ?? '';
    $SubCompanyID = $_POST['subcompanyid'] ?? '';
    $ProjectID = $_POST['projectid'] ?? '';
    $loginname = $_POST['loginname'] ?? '';
    $EmployeeID = $_POST['EmployeeID'] ?? '';
    $LocationId = $_POST['LocationId'] ?? '';
    $AttDateFrom = $_POST['AttDateFrom'] ?? '';
    $AttDateTo = $_POST['AttDateTo'] ?? '';
    $Comments = $_POST['Comments'] ?? '';

    // Basic validation
    if ($EmployeeID == "" || $LocationId == "" || $AttDateFrom == "" || $AttDateTo == "") {
        echo json_encode([
            "status" => "error",
            "message" => "Employee, Location, From-Date & To-Date are required."
        ]);
        exit;
    }

    try {

        // START TRANSACTION
        $hrmconn->begin_transaction();
        // Convert inputs to valid date objects
        $start = new DateTime($AttDateFrom);
        $end = new DateTime($AttDateTo);
        // include last date
        $end->modify("+1 day");
        // Loop through all dates
        foreach (new DatePeriod($start, new DateInterval('P1D'), $end) as $dateObj) {
            $AttendanceDate = $dateObj->format("Y-m-d");
            // Call your save/update function
            $ddf = saveAttendanceLocationExemption($hrmconn, $ProjectID, $EmployeeID, $LocationId, $AttendanceDate, $Comments, $loginname);
        }
        // COMMIT ALL CHANGES
        $hrmconn->commit();
        echo json_encode([
            "status" => "success",
            "message" => "Attendance location exemption saved successfully."
        ]);
    } catch (Exception $e) {
        // ROLLBACK IF ANY ERROR OCCURS
        $hrmconn->rollback();
        echo json_encode([
            "status" => "error",
            "message" => "Failed to save. Error: " . $e->getMessage()
        ]);
    }
    exit;
} else if ($action == "getEmployeeExemptedLocations") {
    $ProjectID = $_POST['ProjectID'] ?? '';
    $ExemptYear = $_POST['ExemptYear'] ?? '';
    $sortby = $_POST['sortby'] ?? '';
    $sortbyvalue = $_POST['sortbyvalue'] ?? '';
    $gempExD = getEmployeeExemptedLocationsDetailsData($hrmconn, $ProjectID, $ExemptYear, $sortby, $sortbyvalue);
    echo json_encode($gempExD);
} else if ($action == "SaveOrUpdateAppliedLeave") {
    // Assumes $hrmconn and $authconn are existing mysqli connections and session/auth already done
    $CompanyID = $_POST['companyid'] ?? '';
    $SubCompanyID = $_POST['subcompanyid'] ?? '';
    $ProjectID = $_POST['projectid'] ?? '';
    $EmployeeID = $_POST['EmployeeID'] ?? '';
    $LeaveType = $_POST['LeaveType'] ?? '';
    $LeaveStart = $_POST['DateFrom'] ?? '';
    $LeaveEnd = $_POST['DateTo'] ?? '';
    $Remarks = $_POST['Remarks'] ?? '';
    $CreatedBy = $_POST['loginid'] ?? '';
    $loginid = $_POST['loginid'] ?? '';

    // login info for approval and cancelled_by name
    $logininfo = hrm_loginuserinfo($authconn, "", "loginid", $loginid);
    $loginName = $logininfo[0]['loginname'] ?? $loginid;

    // default approval
    $appStatus = "Pending";
    $approved_at = "";
    $approved_by = "";
    if (!empty($logininfo) && isset($logininfo[0]['loginroleid']) && $logininfo[0]['loginroleid'] <= 3) {
        $appStatus = "Approved";
        $approved_at = date('Y-m-d');
        $approved_by = $logininfo[0]['loginname'];
    }

    // Basic validation
    if (
        empty($CompanyID) || empty($SubCompanyID) || empty($ProjectID) || empty($EmployeeID) ||
        empty($LeaveType) || empty($LeaveStart) || empty($LeaveEnd) || empty(trim($Remarks))
    ) {
        echo json_encode(["status" => "Error", "message" => "All required fields must be filled, including the Reason for Leave."]);
        exit;
    }

    if (strtotime($LeaveStart) > strtotime($LeaveEnd)) {
        echo json_encode(["status" => "Error", "message" => "From Date must be earlier than or equal to To Date."]);
        exit;
    }

    // 1) Check employee status (Active)
    $empSql = "SELECT EmployeeID, `Status` FROM hrm_EmployeesMaster WHERE EmployeeID = ? LIMIT 1";
    $empStmt = $hrmconn->prepare($empSql);
    if (!$empStmt) {
        echo json_encode(["status" => "Error", "message" => "Prepare failed: " . $hrmconn->error]);
        exit;
    }
    $empStmt->bind_param("i", $EmployeeID);
    $empStmt->execute();
    $empRes = $empStmt->get_result()->fetch_assoc();
    $empStmt->close();

    if (!$empRes) {
        echo json_encode(["status" => "Error", "message" => "Employee not found."]);
        exit;
    }

    if (strtolower($empRes['Status']) !== 'active') {
        echo json_encode(["status" => "Error", "message" => "Employee is not active/resigned. Leave cannot be applied."]);
        exit;
    }

    // 1.5) Check leave type status (must be Active and not deleted)
    $ltSql = "SELECT id, status FROM hrm_PayrollLeaveTypeMaster WHERE leave_code = ? AND companyid = ? AND (tedit is null or tedit = '') LIMIT 1";
    $ltStmt = $hrmconn->prepare($ltSql);
    if ($ltStmt) {
        $ltStmt->bind_param("ss", $LeaveType, $CompanyID);
        $ltStmt->execute();
        $ltRes = $ltStmt->get_result()->fetch_assoc();
        $ltStmt->close();

        if (!$ltRes) {
            echo json_encode(["status" => "Error", "message" => "Leave Type not found."]);
            exit;
        }

        if (strtolower($ltRes['status'] ?? 'active') === 'inactive') {
            echo json_encode(["status" => "Error", "message" => "This leave type is deactivated. Leave cannot be applied."]);
            exit;
        }
    }

    // 1.8) Check if attendance already exists for any date in the requested date range
    $attCheckSql = "SELECT AttendanceDate, Status FROM hrm_EmployeeAttendance 
                    WHERE EmployeeID = ? AND AttendanceDate >= ? AND AttendanceDate <= ? 
                    AND (Status NOT IN ('Rejected') OR Status IS NULL) 
                    AND (tedit IS NULL OR tedit = '') 
                    ORDER BY AttendanceDate ASC LIMIT 1";
    $attCheckStmt = $hrmconn->prepare($attCheckSql);
    if ($attCheckStmt) {
        $attCheckStmt->bind_param("iss", $EmployeeID, $LeaveStart, $LeaveEnd);
        $attCheckStmt->execute();
        $attCheckRes = $attCheckStmt->get_result()->fetch_assoc();
        $attCheckStmt->close();

        if ($attCheckRes) {
            $existDate = date('d-M-Y', strtotime($attCheckRes['AttendanceDate']));
            $attStatus = !empty($attCheckRes['Status']) ? $attCheckRes['Status'] : 'Present';
            echo json_encode([
                "status" => "Error",
                "message" => "Leave cannot be applied. Attendance record already exists for date {$existDate} (Status: {$attStatus})."
            ]);
            exit;
        }
    }

    // 2) Handle file upload (single attachment for entire request)
    $attFilepath = null;
    if (!empty($_FILES['Attachment']['name'])) {
        $baseDir = "../attachementfile/{$CompanyID}/{$SubCompanyID}/{$ProjectID}/LeaveAttachment/" .
            preg_replace('/\s+/', '', $EmployeeID) . "/{$LeaveStart}_{$LeaveEnd}/";

        if (!is_dir($baseDir))
            mkdir($baseDir, 0777, true);

        $fileName = time() . "_" . preg_replace("/[^A-Za-z0-9.\-_]/", "_", $_FILES["Attachment"]["name"]);
        $targetFile = $baseDir . $fileName;

        if (move_uploaded_file($_FILES["Attachment"]["tmp_name"], $targetFile)) {
            $attFilepath = str_replace("../", "", $targetFile);
        } else {
            echo json_encode(["status" => "Error", "message" => "File upload failed."]);
            exit;
        }
    }

    // Prepare counters and details
    $insertCount = 0;
    $cancelInsertCount = 0; // previous leave cancelled then inserted
    $attendanceSkipped = 0;
    $weekoffSkipped = 0;
    $holidaySkipped = 0;
    $errors = [];
    $responseDetails = [];

    // Normalize date range and iterate per day
    try {
        $start = new DateTime($LeaveStart);
        $end = new DateTime($LeaveEnd);
    } catch (Exception $ex) {
        echo json_encode(["status" => "Error", "message" => "Invalid date format."]);
        exit;
    }
    $end->modify('+1 day'); // include end date

    while ($start < $end) {

        $leaveDate = $start->format('Y-m-d');
        $weekdayNo = (int) $start->format('N'); // 1 (Mon) - 7 (Sun)

        // --- 1. Check WeekOff for employee (hrm_EmployeeWeekOff) ---
        $woSql = "SELECT WeekOffDayNo, WeekOffdayName, Status FROM hrm_EmployeeWeekOff WHERE EmployeeID = ? AND ProjectID = ? AND Status = 'Active' LIMIT 1";
        $woStmt = $hrmconn->prepare($woSql);
        if (!$woStmt) {
            $errors[] = "Prepare failed (weekoff): " . $hrmconn->error;
            $start->modify('+1 day');
            continue;
        }
        $woStmt->bind_param("is", $EmployeeID, $ProjectID);
        $woStmt->execute();
        $woRes = $woStmt->get_result()->fetch_assoc();
        $woStmt->close();

        // If weekoff configured and matches current day, skip
        if ($woRes && intval($woRes['WeekOffDayNo']) === $weekdayNo) {
            $weekoffSkipped++;
            $responseDetails[] = "$leaveDate skipped (WeekOff: {$woRes['WeekOffdayName']})";
            $start->modify('+1 day');
            continue;
        }

        // --- 2. Check Holiday (hrm_PayrollHolidaysMaster) ---
        $holidaySql = "SELECT id FROM hrm_PayrollHolidaysMaster WHERE (tedit is null or tedit = '') AND companyid = ? AND subcompanyid = ? AND projectid = ? AND HolidayDate = ? LIMIT 1";
        $hstmt = $hrmconn->prepare($holidaySql);
        if (!$hstmt) {
            $errors[] = "Prepare failed (holiday): " . $hrmconn->error;
            $start->modify('+1 day');
            continue;
        }
        $hstmt->bind_param("iiis", $CompanyID, $SubCompanyID, $ProjectID, $leaveDate);
        $hstmt->execute();
        $hres = $hstmt->get_result()->fetch_assoc();
        $hstmt->close();

        if ($hres) {
            $holidaySkipped++;
            $responseDetails[] = "$leaveDate skipped (Holiday)";
            $start->modify('+1 day');
            continue;
        }

        // --- 3. Check Attendance exists (hrm_EmployeeAttendance) ---
        // If attendance exists and Status is Present or Absent, skip (do not allow leave)
        $attSql = "SELECT AttendanceID, Status FROM hrm_EmployeeAttendance WHERE Status NOT IN('Rejected') AND EmployeeID = ? AND AttendanceDate = ? LIMIT 1";
        $attStmt = $hrmconn->prepare($attSql);
        if (!$attStmt) {
            $errors[] = "Prepare failed (attendance): " . $hrmconn->error;
            $start->modify('+1 day');
            continue;
        }
        $attStmt->bind_param("is", $EmployeeID, $leaveDate);
        $attStmt->execute();
        $attRes = $attStmt->get_result()->fetch_assoc();
        $attStmt->close();

        if ($attRes) {
            $statusCheck = strtolower($attRes['Status']);
            if (in_array($statusCheck, ['present', 'absent'])) {
                $attendanceSkipped++;
                $responseDetails[] = "$leaveDate skipped (Attendance exists: {$attRes['Status']})";
                $start->modify('+1 day');
                continue;
            }
        }

        // --- 4. Check existing leave for the date ---
        $checkSql = "SELECT LeaveID, Status FROM hrm_EmployeeLeaves WHERE EmployeeID = ? AND LeaveStartDate = ? AND LeaveEndDate = ? AND ProjectID = ? LIMIT 1";
        $checkStmt = $hrmconn->prepare($checkSql);
        if (!$checkStmt) {
            $errors[] = "Prepare failed (check leave): " . $hrmconn->error;
            $start->modify('+1 day');
            continue;
        }
        $checkStmt->bind_param("issi", $EmployeeID, $leaveDate, $leaveDate, $ProjectID);
        $checkStmt->execute();
        $existing = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();

        if ($existing) {
            // Auto-cancel previous leave record, then insert new one
            $cancelSql = "UPDATE hrm_EmployeeLeaves SET Status = 'Cancelled' WHERE LeaveID = ?";
            $cancelStmt = $hrmconn->prepare($cancelSql);
            if ($cancelStmt) {
                $cancelStmt->bind_param("i", $existing['LeaveID']);
                $cancelStmt->execute();
                $cancelStmt->close();
            } else {
                $errors[] = "Failed to cancel existing leave ID {$existing['LeaveID']}: " . $hrmconn->error;
            }

            // Now insert the new leave record for the date
            $insSql = "INSERT INTO hrm_EmployeeLeaves 
                (EmployeeID, CompanyID, SubCompanyID, ProjectID, LeaveType, LeaveStartDate, LeaveEndDate, attFilepath, Remarks, Status, created_by, approved_at, approved_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $insStmt = $hrmconn->prepare($insSql);
            if (!$insStmt) {
                $errors[] = "Prepare failed (insert after cancel): " . $hrmconn->error;
                $start->modify('+1 day');
                continue;
            }
            $insStmt->bind_param(
                "issssssssssss",
                $EmployeeID,
                $CompanyID,
                $SubCompanyID,
                $ProjectID,
                $LeaveType,
                $leaveDate,
                $leaveDate,
                $attFilepath,
                $Remarks,
                $appStatus,
                $CreatedBy,
                $approved_at,
                $approved_by
            );
            if ($insStmt->execute()) {
                $cancelInsertCount++;
                $responseDetails[] = "$leaveDate previous leave cancelled, new leave applied.";
            } else {
                $errors[] = "Insert failed for $leaveDate after cancelling previous leave: " . $insStmt->error;
            }
            $insStmt->close();

        } else {
            // No existing leave -> Insert
            $insSql = "INSERT INTO hrm_EmployeeLeaves 
                (EmployeeID, CompanyID, SubCompanyID, ProjectID, LeaveType, LeaveStartDate, LeaveEndDate, attFilepath, Remarks, Status, created_by, approved_at, approved_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $insStmt = $hrmconn->prepare($insSql);
            if (!$insStmt) {
                $errors[] = "Prepare failed (insert): " . $hrmconn->error;
                $start->modify('+1 day');
                continue;
            }
            $insStmt->bind_param(
                "issssssssssss",
                $EmployeeID,
                $CompanyID,
                $SubCompanyID,
                $ProjectID,
                $LeaveType,
                $leaveDate,
                $leaveDate,
                $attFilepath,
                $Remarks,
                $appStatus,
                $CreatedBy,
                $approved_at,
                $approved_by
            );
            if ($insStmt->execute()) {
                $insertCount++;
                $responseDetails[] = "$leaveDate leave applied.";
            } else {
                $errors[] = "Insert failed for $leaveDate: " . $insStmt->error;
            }
            $insStmt->close();
        }
        // next day
        $start->modify('+1 day');
    }
    // Final result
    $result = [
        "status" => empty($errors) ? "Success" : "Partial",
        "message" => "Leave process completed.",
        "inserted" => $insertCount,
        "cancelled_and_inserted" => $cancelInsertCount,
        "attendance_skipped" => $attendanceSkipped,
        "weekoff_skipped" => $weekoffSkipped,
        "holiday_skipped" => $holidaySkipped,
        "details" => $responseDetails
    ];

    if (!empty($errors))
        $result['errors'] = $errors;

    echo json_encode($result);
    exit;

} else if ($action == "SaveOrUpdateAppliedLeave___") {
    header("Content-Type: application/json");
    // 🔹 Step 1: Collect and sanitize inputs
    $CompanyID = $_POST['companyid'] ?? '';
    $SubCompanyID = $_POST['subcompanyid'] ?? '';
    $ProjectID = $_POST['projectid'] ?? '';
    $EmployeeID = $_POST['EmployeeID'] ?? '';
    $LeaveType = $_POST['LeaveType'] ?? '';
    $LeaveStart = $_POST['DateFrom'] ?? '';
    $LeaveEnd = $_POST['DateTo'] ?? '';
    $Remarks = $_POST['Remarks'] ?? '';
    $CreatedBy = $_POST['loginid'] ?? '';
    $loginid = $_POST['loginid'] ?? '';

    $logininfo = hrm_loginuserinfo($authconn, "", "loginid", $loginid);
    $appSatus = "Pending";
    $approved_at = "";
    $approved_by = "";
    if ($logininfo[0]['loginroleid'] <= 3) {
        $appSatus = "Approved";
        $approved_at = date('Y-m-d');
        $approved_by = $logininfo[0]['loginname'];

    }
    // 🔹 Step 2: Validate required fields
    if (empty($CompanyID) || empty($SubCompanyID) || empty($ProjectID) || empty($EmployeeID) || empty($LeaveType) || empty($LeaveStart) || empty($LeaveEnd)) {
        echo json_encode(["status" => "Error", "message" => "All required fields must be filled."]);
        exit;
    }

    // 🔹 Step 3: Handle file upload
    $attFilepath = null;
    if (!empty($_FILES['Attachment']['name'])) {
        $baseDir = "../attachementfile/{$CompanyID}/{$SubCompanyID}/{$ProjectID}/LeaveAttachment/" . str_replace(" ", "", $EmployeeID) . "/" . $LeaveStart . "_" . $LeaveEnd . "/";

        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0777, true);
        }

        $fileName = time() . "_" . preg_replace("/[^A-Za-z0-9.\-_]/", "_", $_FILES["Attachment"]["name"]);
        $targetFile = $baseDir . $fileName;

        if (move_uploaded_file($_FILES["Attachment"]["tmp_name"], $targetFile)) {
            $attFilepath = str_replace("../", "", $targetFile);
        } else {
            echo json_encode(["status" => "Error", "message" => "File upload failed."]);
            exit;
        }
    }

    // 🔹 Step 4: Check for existing leave (same employee and date range)
    $checkSql = "SELECT LeaveID FROM hrm_EmployeeLeaves 
                 WHERE EmployeeID = ? AND LeaveStartDate = ? AND LeaveEndDate = ? AND ProjectID = ?";
    $checkStmt = $hrmconn->prepare($checkSql);
    if (!$checkStmt) {
        echo json_encode(["status" => "Error", "message" => "Prepare failed: " . $hrmconn->error]);
        exit;
    }
    $checkStmt->bind_param("issi", $EmployeeID, $LeaveStart, $LeaveEnd, $ProjectID);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $existing = $checkResult->fetch_assoc();
    $checkStmt->close();

    // 🔹 Step 5: Update or Insert
    if ($existing) {
        // ---- UPDATE ----
        $sql = "UPDATE hrm_EmployeeLeaves 
                SET LeaveType = ?, Remarks = ?, attFilepath = ?, Status = ?, 
                    approved_at = ?, approved_by = ? 
                WHERE LeaveID = ?";
        $stmt = $hrmconn->prepare($sql);
        if (!$stmt) {
            echo json_encode(["status" => "Error", "message" => "Prepare failed: " . $hrmconn->error]);
            exit;
        }
        $stmt->bind_param("ssssssi", $LeaveType, $Remarks, $attFilepath, $appSatus, $approved_at, $approved_by, $existing['LeaveID']);

        if ($stmt->execute()) {
            echo json_encode(["status" => "Success", "message" => "Leave updated successfully."]);
        } else {
            echo json_encode(["status" => "Error", "message" => "Failed to update leave: " . $stmt->error]);
        }
        $stmt->close();

    } else {
        // ---- INSERT ----
        $sql = "INSERT INTO hrm_EmployeeLeaves 
                (EmployeeID, CompanyID, SubCompanyID, ProjectID, LeaveType, LeaveStartDate, LeaveEndDate, attFilepath, Remarks, Status, created_by, approved_at, approved_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $hrmconn->prepare($sql);
        if (!$stmt) {
            echo json_encode(["status" => "Error", "message" => "Prepare failed: " . $hrmconn->error]);
            exit;
        }

        $stmt->bind_param(
            "issssssssssss",
            $EmployeeID,
            $CompanyID,
            $SubCompanyID,
            $ProjectID,
            $LeaveType,
            $LeaveStart,
            $LeaveEnd,
            $attFilepath,
            $Remarks,
            $appSatus,
            $CreatedBy,
            $approved_at,
            $approved_by
        );

        if ($stmt->execute()) {
            echo json_encode(["status" => "Success", "message" => "Leave applied successfully."]);
        } else {
            echo json_encode(["status" => "Error", "message" => "Failed to apply leave: " . $stmt->error]);
        }
        $stmt->close();
    }
} else if ($action == "GetEmployeeLeavesData") {
    $companyid = $_POST['companyid'] ?? '';
    $subcompanyid = $_POST['subcompanyid'] ?? '';
    $projectid = $_POST['projectid'] ?? '';
    $datefrom = $_POST['datefrom'] ?? '';
    $dateto = $_POST['dateto'] ?? '';
    $sortby = $_POST['sortby'] ?? '';
    $sortbyvalue = $_POST['sortbyvalue'] ?? '';

    $isPendingQuery = ($sortby === "Status" && (empty($sortbyvalue) || strtolower($sortbyvalue) === 'pending'));

    $sql = "SELECT LeaveID, EmployeeID, CompanyID, SubCompanyID, ProjectID, LeaveType, LeaveStartDate, LeaveEndDate, TotalLeaveDays, attFilepath, Remarks, Status, created_at, created_by, approved_at, approved_by
            FROM hrm_EmployeeLeaves 
            WHERE ProjectID = ? 
              AND (Status IS NULL OR Status NOT IN ('Removed', 'Cancelled'))";

    $params = [$projectid];
    $types = "s";

    if (!empty($datefrom) && !empty($dateto)) {
        $sql .= " AND LeaveStartDate >= ? AND LeaveStartDate <= ?";
        $params[] = $datefrom;
        $params[] = $dateto;
        $types .= "ss";
    }

    if ($isPendingQuery) {
        // Pending for approval: Status is null or empty '' or 'Pending'
        $sql .= " AND (Status IS NULL OR Status = '' OR LOWER(Status) = 'pending')";
    } else if (!empty($sortby) && !empty($sortbyvalue) && $sortby === "Status" && strtolower($sortbyvalue) !== 'all') {
        $sql .= " AND Status = ?";
        $params[] = $sortbyvalue;
        $types .= "s";
    }

    $sql .= " ORDER BY created_at DESC";

    $lvdata = [];
    $stmt = $hrmconn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $lvdata[] = $row;
        }
        $stmt->close();
    }

    $empleavedata = [];
    if (count($lvdata) > 0) {
        foreach ($lvdata as $lvdkey) {
            $empinfo = fetchEmployeeData($hrmconn, $companyid, $subcompanyid, $projectid, "EmployeeID", $lvdkey['EmployeeID']);

            $EmployeeName = $empinfo[0]['FullName'] ?? '';
            $lvtypinfo = getLeaveTypeMasterList($hrmconn, $companyid, "leave_code", $lvdkey['LeaveType']);
            $leaveName = $lvtypinfo[0]['leave_name'] ?? '';

            // Normalize status: null, empty string, or 'unknown' is treated as 'Pending'
            $rawStatus = trim($lvdkey['Status'] ?? '');
            $rawLower = strtolower($rawStatus);
            if (empty($rawStatus) || $rawLower === 'null' || $rawLower === 'pending' || $rawLower === 'unknown') {
                $displayStatus = 'Pending';
            } else if ($rawLower === 'approved') {
                $displayStatus = 'Approved';
            } else if ($rawLower === 'rejected') {
                $displayStatus = 'Rejected';
            } else {
                $displayStatus = 'Pending';
            }

            $empleavedata[] = array(
                'LeaveID' => $lvdkey['LeaveID'], 
                'EmployeeID' => $lvdkey['EmployeeID'], 
                'EmployeeName' => strtoupper($EmployeeName), 
                'LeaveType' => $lvdkey['LeaveType'], 
                'Status' => $displayStatus, 
                'leaveName' => $leaveName, 
                'LeaveEndDate' => $lvdkey['LeaveEndDate'], 
                'LeaveStartDate' => $lvdkey['LeaveStartDate'], 
                'TotalLeaveDays' => $lvdkey['TotalLeaveDays'], 
                'attFilepath' => $lvdkey['attFilepath'], 
                'Remarks' => $lvdkey['Remarks'],
                'created_at' => $lvdkey['created_at']
            );
        }

    }
    echo json_encode($empleavedata);
} else if ($action == "GetLeaveCounts") {
    $projectid = $_POST['projectid'] ?? '';
    $datefrom = $_POST['datefrom'] ?? '';
    $dateto = $_POST['dateto'] ?? '';

    $counts = ['pending' => 0, 'history' => 0];
    if (!empty($projectid) && !empty($datefrom) && !empty($dateto)) {
        $stmt = $hrmconn->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN (Status IS NULL OR Status = '' OR LOWER(Status) = 'pending') THEN 1 ELSE 0 END) as pending_cnt
            FROM hrm_EmployeeLeaves
            WHERE ProjectID = ? 
              AND (Status IS NULL OR Status NOT IN ('Removed', 'Cancelled'))
              AND LeaveStartDate >= ? 
              AND LeaveStartDate <= ?
        ");
        if ($stmt) {
            $stmt->bind_param("sss", $projectid, $datefrom, $dateto);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $counts['pending'] = (int)($row['pending_cnt'] ?? 0);
                $counts['history'] = (int)($row['total'] ?? 0);
            }
            $stmt->close();
        }
    }
    echo json_encode($counts);
    exit;
} else if ($action == "GetLeaveYearsList") {
    $projectid = $_POST['projectid'] ?? '';
    $years = [];
    if (!empty($projectid)) {
        $stmt = $hrmconn->prepare("SELECT DISTINCT YEAR(created_at) AS y FROM hrm_EmployeeLeaves WHERE ProjectID = ? AND created_at IS NOT NULL AND created_at != '0000-00-00 00:00:00' ORDER BY y DESC");
        if ($stmt) {
            $stmt->bind_param("s", $projectid);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                if (!empty($row['y'])) {
                    $years[] = (int)$row['y'];
                }
            }
            $stmt->close();
        }
    }
    $currentYear = (int)date('Y');
    if (!in_array($currentYear, $years)) {
        $years[] = $currentYear;
        rsort($years);
    }
    echo json_encode(array_values($years));
    exit;
} else if ($action == "DeleteLeaveRequest") {
    $LeaveID = $_POST['LeaveID'] ?? '';
    $remark = $_POST['remark'] ?? '';
    $loginroleid = intval($_POST['loginroleid'] ?? 4);
    $Status = $_POST['Status'] ?? '';
    $loginid = $_POST['loginid'] ?? '';

    // login info for performer name
    $logininfo = hrm_loginuserinfo($authconn, "", "loginid", $loginid);
    $loginName = $logininfo[0]['loginname'] ?? $loginid;

    // Standard user can delete only if Status is 'Pending'
    // Admin (role < 3) can delete any leave request
    if (empty($LeaveID)) {
        echo json_encode(["status" => "Error", "message" => "Leave ID is required."]);
        exit;
    }

    $statusLower = strtolower($Status);

    if ($loginroleid >= 3 && $statusLower !== 'pending') {
        echo json_encode(["status" => "Error", "message" => "You can only delete leave requests before approval."]);
        exit;
    }

    if ($loginroleid < 3 && $statusLower !== 'pending' && empty(trim($remark))) {
        echo json_encode(["status" => "Error", "message" => "Admin must provide a remark to delete approved/rejected leave requests."]);
        exit;
    }

    // Execute status update (soft delete)
    $sql = "UPDATE hrm_EmployeeLeaves SET Status = 'Removed', approval_remark = ?, approved_by = ?, approved_at = NOW() WHERE LeaveID = ?";
    $stmt = $hrmconn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ssi", $remark, $loginName, $LeaveID);
        if ($stmt->execute()) {
            echo json_encode(["status" => "Success", "message" => "Leave request deleted successfully."]);
        } else {
            echo json_encode(["status" => "Error", "message" => "Failed to update leave request: " . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(["status" => "Error", "message" => "Failed to prepare query: " . $hrmconn->error]);
    }
} else if ($action == "UpdateAppliedLeaveStatus") {
    $companyid = $_POST['companyid'] ?? '';
    $subcompanyid = $_POST['subcompanyid'] ?? '';
    $projectid = $_POST['projectid'] ?? '';
    $loginid = $_POST['loginid'] ?? '';
    $actionType = $_POST['actionType'] ?? '';
    $loginname = $_POST['loginname'] ?? '';
    $approval_remark = $_POST['approval_remark'] ?? '';
    $empLeaveData = json_decode($_POST['empLeaveData'] ?? '[]', true);
    $response = ['status' => 'error', 'message' => 'No data received'];
    if (!empty($empLeaveData)) {
        $updated = 0;

        foreach ($empLeaveData as $leave) {
            $EmployeeID = (int) ($leave['EmployeeID'] ?? 0);
            $LeaveID = (int) ($leave['LeaveID'] ?? 0);

            if ($EmployeeID && $LeaveID) {
                $sql = "
                    UPDATE hrm_EmployeeLeaves 
                    SET Status = ?, approved_by =?, approval_remark = ?, approved_at = NOW() 
                    WHERE CompanyID = ? AND SubCompanyID = ? AND ProjectID = ? 
                    AND EmployeeID = ? AND LeaveID = ?
                ";

                $stmt = $hrmconn->prepare($sql);

                if (!$stmt) {
                    // Log the SQL error for debugging
                    // echo $hrmconn->error;
                    error_log("Prepare failed: " . $hrmconn->error . " | SQL: " . $sql);
                    continue; // Skip this iteration if prepare failed
                }

                $stmt->bind_param(
                    "ssssssii",
                    $actionType,
                    $loginname,
                    $approval_remark,
                    $companyid,
                    $subcompanyid,
                    $projectid,
                    $EmployeeID,
                    $LeaveID
                );

                if ($stmt->execute()) {
                    $updated++;
                } else {
                    error_log("Execute failed: " . $stmt->error);
                }

                $stmt->close();
            }
        }
        if ($updated > 0) {
            $response = [
                'status' => 'success',
                'message' => "$updated leave request(s) updated successfully!"
            ];
        } else {
            $response = [
                'status' => 'error',
                'message' => 'No records were updated. Please check your selection.'
            ];
        }
    }

    header('Content-Type: application/json');
    echo json_encode($response);
} else if ($action == "ChangeAppliedLeaveType") {
    $companyid = $_POST['companyid'] ?? '';
    $subcompanyid = $_POST['subcompanyid'] ?? '';
    $projectid = $_POST['projectid'] ?? '';
    $loginid = $_POST['loginid'] ?? '';
    $loginname = $_POST['loginname'] ?? '';
    $newLeaveType = trim($_POST['newLeaveType'] ?? '');
    $change_remark = trim($_POST['change_remark'] ?? '');
    $empLeaveData = json_decode($_POST['empLeaveData'] ?? '[]', true);
    $response = ['status' => 'error', 'message' => 'No data received'];

    if (!empty($empLeaveData) && !empty($newLeaveType)) {
        $updated = 0;
        foreach ($empLeaveData as $leave) {
            $EmployeeID = (int) ($leave['EmployeeID'] ?? 0);
            $LeaveID = (int) ($leave['LeaveID'] ?? 0);

            if ($EmployeeID && $LeaveID) {
                if (!empty($change_remark)) {
                    $sql = "UPDATE hrm_EmployeeLeaves 
                            SET LeaveType = ?, Remarks = CONCAT(IFNULL(Remarks, ''), ' [Leave type changed to ', ?, ' by ', ?, ': ', ?, ']') 
                            WHERE EmployeeID = ? AND LeaveID = ?";
                    $stmt = $hrmconn->prepare($sql);
                    if ($stmt) {
                        $stmt->bind_param("ssssii", $newLeaveType, $newLeaveType, $loginname, $change_remark, $EmployeeID, $LeaveID);
                        if ($stmt->execute()) {
                            $updated++;
                        } else {
                            error_log("ChangeAppliedLeaveType Execute failed: " . $stmt->error);
                        }
                        $stmt->close();
                    }
                } else {
                    $sql = "UPDATE hrm_EmployeeLeaves 
                            SET LeaveType = ? 
                            WHERE EmployeeID = ? AND LeaveID = ?";
                    $stmt = $hrmconn->prepare($sql);
                    if ($stmt) {
                        $stmt->bind_param("sii", $newLeaveType, $EmployeeID, $LeaveID);
                        if ($stmt->execute()) {
                            $updated++;
                        } else {
                            error_log("ChangeAppliedLeaveType Execute failed: " . $stmt->error);
                        }
                        $stmt->close();
                    }
                }
            }
        }
        if ($updated > 0) {
            $response = [
                'status' => 'success',
                'message' => "$updated leave application(s) changed to $newLeaveType successfully!"
            ];
        } else {
            $response = [
                'status' => 'error',
                'message' => 'No records were updated. Please check your selection.'
            ];
        }
    } else {
        $response = [
            'status' => 'error',
            'message' => 'Please select a valid leave type and applications to update.'
        ];
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
} else if ($action == "UpdateAppliedAttendanceApproval") {
    $companyid = $_POST['companyid'] ?? '';
    $subcompanyid = $_POST['subcompanyid'] ?? '';
    $projectid = $_POST['projectid'] ?? '';
    $loginid = $_POST['loginid'] ?? '';
    $actionType = $_POST['actionType'] ?? '';
    $loginname = $_POST['loginname'] ?? '';
    $approval_remark = $_POST['approval_remark'] ?? '';
    $empAttendanceData = json_decode($_POST['empAttendanceData'] ?? '[]', true);
    $response = ['status' => 'error', 'message' => 'No data received'];
    if (!empty($empAttendanceData)) {
        $updated = 0;

        foreach ($empAttendanceData as $attdata) {
            $EmployeeID = (int) ($attdata['EmployeeID'] ?? 0);
            $AttendanceID = (int) ($attdata['AttendanceID'] ?? 0);

            if ($EmployeeID && $AttendanceID) {
                if($actionType == "Present"){
                    $ApprovalStatus = "Approved";
                }else if($actionType == "Rejected"){
                    $ApprovalStatus = "Rejected";
                }else{
                    continue;
                }
                $sql = "
                    UPDATE hrm_EmployeeAttendance 
                    SET Status = ?, ApprovalStatus = ?, ApprovedBy =?, ApprovalRemarks = ?, ApprovedDateTime = NOW() 
                    WHERE ProjectID = ? AND EmployeeID = ? AND AttendanceID = ?
                ";

                $stmt = $hrmconn->prepare($sql);

                if (!$stmt) {
                    // Log the SQL error for debugging
                    // echo $hrmconn->error;
                    error_log("Prepare failed: " . $hrmconn->error . " | SQL: " . $sql);
                    continue; // Skip this iteration if prepare failed
                }

                $stmt->bind_param(
                    "sssssii",
                    $actionType,
                    $ApprovalStatus,
                    $loginname,
                    $approval_remark,
                    $projectid,
                    $EmployeeID,
                    $AttendanceID
                );

                if ($stmt->execute()) {
                    $updated++;
                } else {
                    error_log("Execute failed: " . $stmt->error);
                }

                $stmt->close();
            }
        }
        if ($updated > 0) {
            $response = [
                'status' => 'success',
                'message' => "$updated attendance request(s) updated successfully!"
            ];
        } else {
            $response = [
                'status' => 'error',
                'message' => 'No records were updated. Please check your selection.'
            ];
        }
    }
    header('Content-Type: application/json');
    echo json_encode($response);
} else if ($action == "save_working_hours") {
    $companyid = $_POST['companyid'] ?? '';
    $subcompanyid = $_POST['subcompanyid'] ?? '';
    $ProjectID = $_POST['projectid'] ?? '';
    $loginid = $_POST['loginid'] ?? '';
    $id = $_POST['id'] ?? '';
    $workinghourname = $_POST['workinghour'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    // Calculate total hours (supports overnight e.g., 22:00 to 06:00)
    $start = new DateTime($start_time);
    $end = new DateTime($end_time);
    if ($end < $start) {
        $end->modify('+1 day');
    }
    $interval = $start->diff($end);
    $total_hours = $interval->h + ($interval->i / 60);
    if ($id == "") {
        $sql = "INSERT INTO hrm_PayrollWorkingHourse_master 
                (ProjectID, working_hour_name, start_time, end_time, total_hours, create_by) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $hrmconn->prepare($sql);
        if (!$stmt) {
            die(json_encode([
                "status" => "error",
                "message" => "Prepare failed: " . $hrmconn->error,
                "sql" => $sql
            ]));
        }

        $stmt->bind_param(
            "isssdi",
            $ProjectID,
            $workinghourname,
            $start_time,
            $end_time,
            $total_hours,
            $loginid
        );
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Working Hours Added"]);
        } else {
            echo json_encode(["status" => "error", "message" => $stmt->error]);
        }

    } else {
        $sql = "UPDATE hrm_PayrollWorkingHourse_master 
                SET working_hour_name=?, start_time=?, end_time=?, total_hours=? 
                WHERE id=?";
        $stmt = $hrmconn->prepare($sql);
        if (!$stmt) {
            die(json_encode([
                "status" => "error",
                "message" => "Prepare failed: " . $hrmconn->error,
                "sql" => $sql
            ]));
        }
        $stmt->bind_param(
            "sssdi",
            $workinghourname,
            $start_time,
            $end_time,
            $total_hours,
            $id
        );
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Working Hours Updated"]);
        } else {
            echo json_encode(["status" => "error", "message" => $stmt->error]);
        }
    }
} else if ($action == "getWorkingHoursCallback") {
    $companyid = $_POST['companyid'] ?? '';
    $subcompanyid = $_POST['subcompanyid'] ?? '';
    $projectid = $_POST['projectid'] ?? '';
    $gwhdata = getEmployeeWorkingHoursFromMaster($hrmconn, $projectid, '', '');
    echo json_encode($gwhdata);
} else if ($action == "saveApplyWorkingHoursToEmployee") {
    $companyid = $_POST['companyid'] ?? '';
    $subcompanyid = $_POST['subcompanyid'] ?? '';
    $projectid = $_POST['projectid'] ?? '';
    $loginid = $_POST['loginid'] ?? '';
    $workinghourTiming = $_POST['workinghourTiming'] ?? '';
    $applytoworkinghouse = $_POST['applytoworkinghouse'] ?? '';
    $AppyToEmployeesId = $_POST['AppyToEmployeesId'] ?? '';
    // Fetch working hour data (returns array, not JSON)
    $gwhdata = getEmployeeWorkingHoursFromMaster($hrmconn, $projectid, $workinghourTiming, false);
    if (empty($gwhdata)) {
        echo json_encode(["status" => "error", "message" => "Working Hours not found"]);
        exit;
    }
    $inTime = $gwhdata[0]['start_time'];
    $outTime = $gwhdata[0]['end_time'];
    // Fetch employees
    $empData = "";
    if ($applytoworkinghouse == "allemployee") {
        $empData = fetchEmployeeData($hrmconn, $companyid, $subcompanyid, $projectid, "allactive", "all");
    } else {
        $empData = fetchEmployeeData($hrmconn, $companyid, $subcompanyid, $projectid, "EmployeeID", $AppyToEmployeesId);
    }
    if (count($empData) > 0) {
        $stmt = $hrmconn->prepare("UPDATE hrm_EmployeesMaster SET InTime = ?, OutTime = ?  WHERE EmployeeID = ?");
        foreach ($empData as $em) {
            $stmt->bind_param("sss", $inTime, $outTime, $em['EmployeeID']);
            $stmt->execute();
        }
        echo json_encode(["status" => "success", "message" => "Working Hours applied successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "No employees found"]);
    }
} else if ($action == "fetchEmployeeWeekOffData_callback") {
    $companyid = $_POST['companyid'] ?? '';
    $subcompanyid = $_POST['subcompanyid'] ?? '';
    $projectid = $_POST['projectid'] ?? '';
    $EmployeeID = $_POST['EmployeeID'] ?? '';
    $empWeekof = fetchEmployeeWeekOffData($hrmconn, $projectid, $EmployeeID);
    echo json_encode($empWeekof);
} else if ($action == "fetchEmployeeLoginIdPassword") {
    $companyid = $_POST['companyid'] ?? '';
    $subcompanyid = $_POST['subcompanyid'] ?? '';
    $projectid = $_POST['projectid'] ?? '';

    // 1. Query $authconn separately for user login credentials
    $userMap = array();
    $resAuth = $authconn->query("SELECT loginid, loginpassword, isActive, mobileno, emailid FROM login_user_master WHERE (tedit IS NULL OR tedit = '')");
    if ($resAuth && $resAuth->num_rows > 0) {
        while ($u = $resAuth->fetch_assoc()) {
            $uId = (string)$u['loginid'];
            $userMap[$uId] = $u;
            $cleanId = str_replace('EMP-', '', strtoupper($uId));
            if (!isset($userMap[$cleanId])) {
                $userMap[$cleanId] = $u;
            }
        }
    }

    // 2. Query $hrmconn separately for hrm_EmployeesMaster
    if (!empty($companyid) && !empty($subcompanyid) && !empty($projectid)) {
        $sql = "
            SELECT 
                e.EmployeeID,
                e.loginid as emp_loginid,
                CONCAT_WS(' ',
                    IFNULL(e.TitleName,''),
                    IFNULL(e.FirstName,''),
                    IFNULL(e.MiddleName,''),
                    IFNULL(e.LastName,'')
                ) AS FullName,
                e.MobileNo,
                e.Email,
                e.Status as EmpStatus,
                e.DateOfResignation
            FROM hrm_EmployeesMaster e
            WHERE e.companyid = ? AND e.subcompanyid = ? AND e.projectid = ? 
              AND (e.tedit IS NULL OR e.tedit = '')
            ORDER BY e.EmployeeID ASC
        ";
        $stmt = $hrmconn->prepare($sql);
        $stmt->bind_param("iii", $companyid, $subcompanyid, $projectid);
    } else if (!empty($projectid)) {
        $sql = "
            SELECT 
                e.EmployeeID,
                e.loginid as emp_loginid,
                CONCAT_WS(' ',
                    IFNULL(e.TitleName,''),
                    IFNULL(e.FirstName,''),
                    IFNULL(e.MiddleName,''),
                    IFNULL(e.LastName,'')
                ) AS FullName,
                e.MobileNo,
                e.Email,
                e.Status as EmpStatus,
                e.DateOfResignation
            FROM hrm_EmployeesMaster e
            WHERE e.projectid = ? 
              AND (e.tedit IS NULL OR e.tedit = '')
            ORDER BY e.EmployeeID ASC
        ";
        $stmt = $hrmconn->prepare($sql);
        $stmt->bind_param("i", $projectid);
    } else {
        $shortby = $_POST['shortby'] ?? 'all';
        $shortbyvalue = $_POST['shortbyvalue'] ?? 'all';
        $emplogind = hrm_loginuserinfowithproject($authconn, $projectid, '99', $shortby, $shortbyvalue);
        echo json_encode($emplogind);
        exit;
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $ullist = array();
    while ($row = $result->fetch_assoc()) {
        $empName = trim($row['FullName']);
        $empId = (string)$row['EmployeeID'];
        $empLoginId = !empty($row['emp_loginid']) ? (string)$row['emp_loginid'] : $empId;

        // Lookup login credentials from $authconn $userMap
        $userInfo = null;
        if (isset($userMap[$empLoginId])) {
            $userInfo = $userMap[$empLoginId];
        } else if (isset($userMap[$empId])) {
            $userInfo = $userMap[$empId];
        } else if (isset($userMap['EMP-' . $empId])) {
            $userInfo = $userMap['EMP-' . $empId];
        }

        $pwd = ($userInfo && !empty($userInfo['loginpassword'])) ? $userInfo['loginpassword'] : '';
        $userIsActive = ($userInfo && !empty($userInfo['isActive'])) ? strtolower(trim($userInfo['isActive'])) : '';

        $empStatus = strtolower(trim($row['EmpStatus'] ?? ''));

        $isActiveStr = 'yes';
        if ($empStatus === 'resigned' || !empty($row['DateOfResignation']) || $userIsActive === 'no') {
            $isActiveStr = 'no';
        }

        $ullist[] = array(
            'loginname' => $empName,
            'loginid' => $empLoginId,
            'loginpassword' => (string)$pwd,
            'loginroleid' => '99',
            'loginrolename' => 'Employee',
            'mobileno' => !empty($row['MobileNo']) ? $row['MobileNo'] : ($userInfo['mobileno'] ?? 'N/A'),
            'emailid' => !empty($row['Email']) ? $row['Email'] : ($userInfo['emailid'] ?? 'N/A'),
            'isactive' => $isActiveStr,
            'employeeid' => $row['EmployeeID']
        );
    }
    $stmt->close();
    echo json_encode($ullist);
    exit;
} else if ($action == "resetEmployeePassword") {
    $loginid = $_POST['loginid'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    if (empty($loginid) || empty($new_password)) {
        echo json_encode(["status" => "error", "message" => "Login ID and Password are required"]);
        exit;
    }

    $chk = $authconn->prepare("SELECT loginid FROM login_user_master WHERE loginid = ? LIMIT 1");
    $chk->bind_param("s", $loginid);
    $chk->execute();
    $hasUser = $chk->get_result()->fetch_assoc();
    $chk->close();

    if ($hasUser) {
        $stmt = $authconn->prepare("UPDATE login_user_master SET loginpassword = ? WHERE loginid = ?");
        $stmt->bind_param("ss", $new_password, $loginid);
        $stmt->execute();
        $stmt->close();
    } else {
        $empStmt = $hrmconn->prepare("SELECT EmployeeID, FirstName, LastName, MobileNo, Email, companyid, subcompanyid, projectid FROM hrm_EmployeesMaster WHERE EmployeeID = ? OR loginid = ? LIMIT 1");
        $empStmt->bind_param("ss", $loginid, $loginid);
        $empStmt->execute();
        $empData = $empStmt->get_result()->fetch_assoc();
        $empStmt->close();

        $loginname = $empData ? trim(($empData['FirstName'] ?? '') . ' ' . ($empData['LastName'] ?? '')) : 'Employee ' . $loginid;
        $mobile = $empData['MobileNo'] ?? '';
        $email = $empData['Email'] ?? '';

        $ins = $authconn->prepare("INSERT INTO login_user_master (loginname, loginid, loginpassword, loginroleid, loginrolename, mobileno, emailid, isActive) VALUES (?, ?, ?, '99', 'Employee', ?, ?, 'yes')");
        $ins->bind_param("sssss", $loginname, $loginid, $new_password, $mobile, $email);
        $ins->execute();
        $ins->close();

        if ($empData) {
            $updEmp = $hrmconn->prepare("UPDATE hrm_EmployeesMaster SET loginid = ? WHERE EmployeeID = ?");
            $updEmp->bind_param("ss", $loginid, $empData['EmployeeID']);
            $updEmp->execute();
            $updEmp->close();

            $cId = $empData['companyid'];
            $scId = $empData['subcompanyid'];
            $pId = $empData['projectid'];

            $chkAuth = $authconn->prepare("SELECT sno FROM login_authorized_projects WHERE loginid = ? AND projectid = ? LIMIT 1");
            $chkAuth->bind_param("ss", $loginid, $pId);
            $chkAuth->execute();
            $hasAuth = $chkAuth->get_result()->fetch_assoc();
            $chkAuth->close();

            if (!$hasAuth) {
                $insAuth = $authconn->prepare("INSERT INTO login_authorized_projects (companyid, subcompanyid, projectid, loginid, status) VALUES (?, ?, ?, ?, 'active')");
                $insAuth->bind_param("iiss", $cId, $scId, $pId, $loginid);
                $insAuth->execute();
                $insAuth->close();
            }
        }
    }

    echo json_encode(["status" => "success", "message" => "Password updated successfully"]);
    exit;
} else if ($action == "toggleEmployeeActiveStatus") {
    $loginid = $_POST['loginid'] ?? '';
    $status = $_POST['status'] ?? ''; // 'yes' or 'no'
    if (empty($loginid) || empty($status)) {
        echo json_encode(["status" => "error", "message" => "Login ID and Status are required"]);
        exit;
    }

    $stmt = $authconn->prepare("UPDATE login_user_master SET isActive = ? WHERE loginid = ?");
    $stmt->bind_param("ss", $status, $loginid);
    $stmt->execute();
    $stmt->close();

    $empStatus = ($status === 'yes') ? 'Active' : 'Resigned';
    $stmtEmp = $hrmconn->prepare("UPDATE hrm_EmployeesMaster SET Status = ? WHERE EmployeeID = ? OR loginid = ?");
    $stmtEmp->bind_param("sss", $empStatus, $loginid, $loginid);
    $stmtEmp->execute();
    $stmtEmp->close();

    $msg = ($status == 'yes') ? "Account activated successfully" : "Account inactivated successfully";
    echo json_encode(["status" => "success", "message" => $msg]);
    exit;
} else if ($action == "saveUpdateLeaveType") {
    $response = ["status" => "error", "message" => "Invalid request"];
    $actiontodo = $_POST['actiontodo'] ?? '';
    $companyid = $_POST['companyid'] ?? '';
    $leave_id = $_POST['leave_id'] ?? 0;
    $leave_code = trim($_POST['leave_code'] ?? '');
    $leave_name = trim($_POST['leave_name'] ?? '');
    $leave_type = $_POST['leave_type'] ?? '';
    $max_days = $_POST['max_days'] ?? 0;
    $carry_forward = $_POST['carry_forward'] ?? '';
    $description = $_POST['description'] ?? '';
    $status = $_POST['status'] ?? 'active';

    if ($actiontodo !== "delete") {
        if (!$companyid || !$leave_code || !$leave_name) {
            echo json_encode(["status" => "error", "message" => "Required fields missing"]);
            exit;
        }
        // 🔎 Check duplicate leave_code
        $checkSql = "SELECT id FROM hrm_PayrollLeaveTypeMaster 
                     WHERE leave_code = ? AND companyid = ? 
                     AND id != ?";
        $stmt = $hrmconn->prepare($checkSql);
        $stmt->bind_param("ssi", $leave_code, $companyid, $leave_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            echo json_encode(["status" => "error", "message" => "Leave Code already exists"]);
            exit;
        }
    } else {
        if (!$companyid || !$leave_id) {
            echo json_encode(["status" => "error", "message" => "Required fields missing"]);
            exit;
        }
    }

    if ($actiontodo === "add") {

        $sql = "INSERT INTO hrm_PayrollLeaveTypeMaster
                (companyid, leave_code, leave_name, leave_type, max_days, carry_forward, description, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $hrmconn->prepare($sql);
        $stmt->bind_param(
            "ssssisss",
            $companyid,
            $leave_code,
            $leave_name,
            $leave_type,
            $max_days,
            $carry_forward,
            $description,
            $status
        );

        if ($stmt->execute()) {
            $response = ["status" => "success", "message" => "Leave Type added successfully"];
        }

    } elseif ($actiontodo === "edit") {

        $sql = "UPDATE hrm_PayrollLeaveTypeMaster 
                SET leave_code=?, leave_name=?, leave_type=?, 
                    max_days=?, carry_forward=?, description=?, status=?
                WHERE id=? AND companyid=?";

        $stmt = $hrmconn->prepare($sql);
        $stmt->bind_param(
            "sssisssis",
            $leave_code,
            $leave_name,
            $leave_type,
            $max_days,
            $carry_forward,
            $description,
            $status,
            $leave_id,
            $companyid
        );

        if ($stmt->execute()) {
            $response = ["status" => "success", "message" => "Leave Type updated successfully"];
        }

    } elseif ($actiontodo === "delete") {

        $deleted_at = date('Y-m-d H:i:s');
        $tedit = "Deleted on " . $deleted_at;
        $sql = "UPDATE hrm_PayrollLeaveTypeMaster 
                SET tedit=? 
                WHERE id=? AND companyid=?";
        $stmt = $hrmconn->prepare($sql);
        $stmt->bind_param("sis", $tedit, $leave_id, $companyid);

        if ($stmt->execute()) {
            $response = ["status" => "success", "message" => "Leave Type deleted"];
        }
    }
    echo json_encode($response);
} else if ($action == "SearchEmployeeForIncrement") {
    $search = isset($_POST['search']) ? trim($_POST['search']) : '';
    $companyid = isset($_POST['companyid']) ? $_POST['companyid'] : null;
    $subcompanyid = isset($_POST['subcompanyid']) ? $_POST['subcompanyid'] : null;
    
    $employees = [];
    if (!empty($search)) {
        $searchParam = "%" . $search . "%";
        $sql = "SELECT EmployeeID, TitleName, FirstName, MiddleName, LastName, FatherName, DateOfJoining, MobileNo
                FROM hrm_EmployeesMaster 
                WHERE (Status = 'Active' OR Status IS NULL OR Status = '')
                  AND (
                      CONCAT_WS(' ', TitleName, FirstName, MiddleName, LastName) LIKE ?
                      OR FatherName LIKE ?
                      OR MobileNo LIKE ?
                      OR DateOfJoining LIKE ?
                      OR EmployeeID LIKE ?
                  )
                LIMIT 20";
        $stmt = $hrmconn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("sssss", $searchParam, $searchParam, $searchParam, $searchParam, $searchParam);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $fullName = trim($row['TitleName'] . ' ' . $row['FirstName'] . ' ' . $row['MiddleName'] . ' ' . $row['LastName']);
                $doj = (!empty($row['DateOfJoining']) && $row['DateOfJoining'] != '0000-00-00') ? date('d-m-Y', strtotime($row['DateOfJoining'])) : 'N/A';
                $employees[] = [
                    'EmployeeID' => $row['EmployeeID'],
                    'FullName' => $fullName,
                    'FatherName' => !empty($row['FatherName']) ? $row['FatherName'] : 'N/A',
                    'DateOfJoining' => $doj,
                    'RawDOJ' => $row['DateOfJoining'],
                    'ContactNo' => !empty($row['MobileNo']) ? $row['MobileNo'] : 'N/A'
                ];
            }
        }
    }
    echo json_encode($employees);
} else if ($action == "SaveEmployeeSalaryIncrement") {
    $employeeid = isset($_POST['employeeid']) ? $_POST['employeeid'] : null;
    $companyid = isset($_POST['companyid']) ? $_POST['companyid'] : null;
    $subcompanyid = isset($_POST['subcompanyid']) ? $_POST['subcompanyid'] : null;
    $projectid = isset($_POST['projectid']) ? $_POST['projectid'] : null;
    $addedby = isset($_POST['addedby']) ? $_POST['addedby'] : null;
    
    $previousGross = floatval($_POST['previousGross'] ?? 0);
    $previousCTC = floatval($_POST['previousCTC'] ?? 0);
    $incrementType = $_POST['incrementType'] ?? 'Percentage';
    $incrementValue = floatval($_POST['incrementValue'] ?? 0);
    $incrementAmount = floatval($_POST['incrementAmount'] ?? 0);
    $newGross = floatval($_POST['newGross'] ?? 0);
    $newCTC = floatval($_POST['newCTC'] ?? 0);
    
    $previousBasic = floatval($_POST['previousBasic'] ?? 0);
    $newBasic = floatval($_POST['newBasic'] ?? 0);
    $previousHRA = floatval($_POST['previousHRA'] ?? 0);
    $newHRA = floatval($_POST['newHRA'] ?? 0);
    $previousConveyance = floatval($_POST['previousConveyance'] ?? 0);
    $newConveyance = floatval($_POST['newConveyance'] ?? 0);
    $previousSpecial = floatval($_POST['previousSpecial'] ?? 0);
    $newSpecial = floatval($_POST['newSpecial'] ?? 0);
    $affectedFromDate = $_POST['affectedFromDate'] ?? date('Y-m-d');
    $remarks = $_POST['remarks'] ?? '';
    
    if (empty($employeeid)) {
        echo json_encode(["status" => "error", "message" => "Please select an employee."]);
        exit;
    }
    if ($incrementAmount <= 0) {
        echo json_encode(["status" => "error", "message" => "Increment amount must be greater than 0."]);
        exit;
    }
    
    try {
        $newCTC_Year = $newGross * 12;
        
        // 1. Check duplicate entry for same employee & affectedfrom date
        $checkDupSql = "SELECT SalaryID FROM hrm_EmployeeSalary WHERE EmployeeID = ? AND affectedfrom = ? LIMIT 1";
        $stmtDup = $hrmconn->prepare($checkDupSql);
        if ($stmtDup) {
            $stmtDup->bind_param("ss", $employeeid, $affectedFromDate);
            $stmtDup->execute();
            $resDup = $stmtDup->get_result();
            if ($resDup && $resDup->num_rows > 0) {
                echo json_encode([
                    "status" => "error", 
                    "message" => "Duplicate entry! Salary increment record for effective date (" . date('d-m-Y', strtotime($affectedFromDate)) . ") already exists for this employee."
                ]);
                exit;
            }
            $stmtDup->close();
        }

        // 2. Update validtilldate of the last salary row (if exists) with the new entry's validfromdate ($affectedFromDate)
        $getLastSql = "SELECT SalaryID FROM hrm_EmployeeSalary WHERE EmployeeID = ? AND (validtilldate IS NULL OR validtilldate = '' OR validtilldate = '0000-00-00') ORDER BY SalaryID DESC LIMIT 1";
        $stmtLast = $hrmconn->prepare($getLastSql);
        if ($stmtLast) {
            $stmtLast->bind_param("s", $employeeid);
            $stmtLast->execute();
            $resLast = $stmtLast->get_result();
            if ($resLast && $resLast->num_rows > 0) {
                $lastRow = $resLast->fetch_assoc();
                $lastSalaryID = $lastRow['SalaryID'];

                $updateLastSql = "UPDATE hrm_EmployeeSalary SET validtilldate = ? WHERE SalaryID = ?";
                $stmtUpdateLast = $hrmconn->prepare($updateLastSql);
                if ($stmtUpdateLast) {
                    $stmtUpdateLast->bind_param("si", $affectedFromDate, $lastSalaryID);
                    $stmtUpdateLast->execute();
                    $stmtUpdateLast->close();
                }
            }
            $stmtLast->close();
        }
        
        // 3. Insert NEW row into hrm_EmployeeSalary table
        $sqlSal = "INSERT INTO hrm_EmployeeSalary 
                    (companyid, subcompanyid, projectid, EmployeeID, GrossSalary, BasicSalary, HRA, ConveyanceAllowance, SpecialAllowance, CTC, CTCY, incrementtype, incrementvalue, affectedfrom, validtilldate, update_by, Comments, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, NOW())";
        $stmtSal = $hrmconn->prepare($sqlSal);
        $stmtSal->bind_param("ssssdddddddsdsss",
            $companyid, $subcompanyid, $projectid, $employeeid,
            $newGross, $newBasic, $newHRA, $newConveyance, $newSpecial, $newGross, $newCTC_Year,
            $incrementType, $incrementValue, $affectedFromDate, $addedby, $remarks
        );
        $stmtSal->execute();
        $stmtSal->close();

        // 4. Update hrm_EmployeeEarningComponents table for component level breakdown
        $checkComp = $hrmconn->query("SELECT id FROM hrm_EmployeeEarningComponents WHERE EmployeeId = '$employeeid' LIMIT 1");
        if ($checkComp && $checkComp->num_rows > 0) {
            $hrmconn->query("UPDATE hrm_EmployeeEarningComponents SET EmployeeEarningValue = '$newBasic' WHERE EmployeeId = '$employeeid' AND ComponentId = 1");
            $hrmconn->query("UPDATE hrm_EmployeeEarningComponents SET EmployeeEarningValue = '$newHRA' WHERE EmployeeId = '$employeeid' AND ComponentId = 2");
            $hrmconn->query("UPDATE hrm_EmployeeEarningComponents SET EmployeeEarningValue = '$newConveyance' WHERE EmployeeId = '$employeeid' AND ComponentId = 4");
            $hrmconn->query("UPDATE hrm_EmployeeEarningComponents SET EmployeeEarningValue = '$newSpecial' WHERE EmployeeId = '$employeeid' AND ComponentId = 6");
        }
        
        echo json_encode(["status" => "success", "message" => "Employee salary increment saved successfully!"]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
    }
} else if ($action == "GetEmployeeIncrementHistory") {
    $employeeid = isset($_POST['employeeid']) ? $_POST['employeeid'] : null;
    $history = [];
    if (!empty($employeeid)) {
        $sql = "SELECT * FROM hrm_EmployeeSalary WHERE EmployeeID = ? ORDER BY SalaryID ASC";
        $stmt = $hrmconn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("s", $employeeid);
            $stmt->execute();
            $result = $stmt->get_result();
            $allRows = [];
            while ($row = $result->fetch_assoc()) {
                $allRows[] = $row;
            }
            $stmt->close();

            for ($i = 0; $i < count($allRows); $i++) {
                $row = $allRows[$i];
                $prevGross = ($i > 0) ? floatval($allRows[$i - 1]['GrossSalary'] ?? 0) : floatval($row['GrossSalary'] ?? 0);
                
                $row['AffectedFromDateFormatted'] = (!empty($row['affectedfrom']) && $row['affectedfrom'] != '0000-00-00') ? date('d-m-Y', strtotime($row['affectedfrom'])) : 'N/A';
                $row['ValidTillDateFormatted'] = (!empty($row['validtilldate']) && $row['validtilldate'] != '0000-00-00') ? date('d-m-Y', strtotime($row['validtilldate'])) : 'Present';
                $row['CreatedDateFormatted'] = (!empty($row['updated_at'])) ? date('d-m-Y H:i', strtotime($row['updated_at'])) : 'N/A';
                $row['PreviousGross'] = $prevGross;
                $incVal = floatval($row['incrementvalue'] ?? 0);
                $incType = $row['incrementtype'] ?? 'Percentage';
                
                if ($i == 0 && $incVal == 0) {
                    $row['IncrementType'] = 'Initial Salary';
                } else {
                    $row['IncrementType'] = $incType;
                }
                
                $row['IncrementValue'] = $incVal;
                $row['IncrementAmount'] = ($i > 0) ? (floatval($row['GrossSalary'] ?? 0) - $prevGross) : 0;
                if ($row['IncrementAmount'] < 0) $row['IncrementAmount'] = 0;
                $row['NewGross'] = floatval($row['GrossSalary'] ?? 0);
                $row['NewCTC'] = floatval($row['CTCY'] ?? 0);
                $row['Remarks'] = !empty($row['Comments']) ? $row['Comments'] : ($i == 0 ? 'Base Salary' : '');

                $history[] = $row;
            }
            
            // Sort newest first for table display
            usort($history, function($a, $b) {
                return $b['SalaryID'] <=> $a['SalaryID'];
            });
        }
    }
    echo json_encode($history);
}
?>