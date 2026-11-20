<?php 
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
require('../../connection.php');
require('../functions/comanphp_functions.php');
require('../functions/hrm_insertupdatesalary_functions.php');
require('../functions/hrm_FunctionMaster.php');
include('../SimpleXLSX.php');
include('../SimpleXLS.php');
$action = $_GET['action'];
if($action=="uploadsalarydetails_excelfile"){
	$companyid    = $_POST['companyid'] ?? '';
	$subcompanyid = $_POST['subcompanyid'] ?? '';
	$projectid    = $_POST['projectid'] ?? '';
	$loginid      = $_POST['loginid'] ?? '';
	$loginname      = $_POST['loginname'] ?? '';
	$expected_salary_month = $_POST['slarymonthyear'] ?? ''; // Expected in YYYY-MM format
	$returninfo = [];
	// Extract year and month from expected input
	$salaryyear = '';
	$salarymonth = '';
	if ($expected_salary_month && preg_match("/^\d{4}-\d{2}$/", $expected_salary_month)) {
	    list($salaryyear, $salarymonth) = explode('-', $expected_salary_month);
	} else {
	    $returninfo[] = [
	        'status' => 'Error',
	        'message' => 'Invalid salary month format. Expected YYYY-MM.'
	    ];
	    echo json_encode($returninfo);
	    exit;
	}

	// Check and parse uploaded file
	if (isset($_FILES['salaryfile']) && !empty($_FILES['salaryfile']['tmp_name'])) {
	    $file_ext = strtolower(pathinfo($_FILES['salaryfile']['name'], PATHINFO_EXTENSION));
	    $xlsx = ($file_ext == "xlsx") ? SimpleXLSX::parse($_FILES['salaryfile']['tmp_name']) : 
	            (($file_ext == "xls") ? SimpleXLS::parse($_FILES['salaryfile']['tmp_name']) : null);

	    if (!$xlsx) {
	        $errorMsg = ($file_ext == "xlsx") ? SimpleXLSX::parseError() : SimpleXLS::parseError();
	        $returninfo[] = [
	            'status' => 'Error',
	            'message' => 'Excel parsing error: ' . $errorMsg
	        ];
	        echo json_encode($returninfo);
	        exit;
	    }

	    $exceldata = $xlsx->rows();

	    // Extract sheet's salary month from row 2, column A
	    $salaryMonthFromSheetRaw = trim($exceldata[1][1] ?? '');

	    // Try converting to YYYY-MM
	    $salaryMonthFromSheetFormatted = '';
	    if (!empty($salaryMonthFromSheetRaw)) {
	        $timestamp = strtotime($salaryMonthFromSheetRaw);
	        if ($timestamp) {
	            $salaryMonthFromSheetFormatted = date('Y-m', $timestamp);
	        }
	    }

	    if (!$salaryMonthFromSheetFormatted) {
	        $returninfo[] = [
	            'status' => 'Error',
	            'message' => 'Unable to detect salary month from Excel sheet.'
	        ];
	        echo json_encode($returninfo);
	        exit;
	    }

	    // Compare formatted salary month from Excel with expected input
	    // echo $salaryMonthFromSheetFormatted."==".$expected_salary_month;
	    if ($salaryMonthFromSheetFormatted !== $expected_salary_month) {
	        $returninfo[] = [
	            'status' => 'Error',
	            'message' => "Mismatch in salary month. Sheet has '{$salaryMonthFromSheetFormatted}', but you provided '{$expected_salary_month}'."
	        ];
	        echo json_encode($returninfo);
	        exit;
	    }

	    // Detect header row
	    $header = HRMdetectHeaderRowFromArray($exceldata);
	    if ($header < 0) {
	        $returninfo[] = [
	            'status' => 'Error',
	            'message' => 'This sheet contains no valid header. Please correct the sheet and try again.'
	        ];
	        echo json_encode($returninfo);
	        exit;
	    }

	    // Read data rows
	    $newexceldata = [];
	    $newemp=0;
	    for ($row = $header + 1; $row < count($exceldata); $row++) {
	        $employeeID   = floatval($exceldata[$row][0] ?? '');
	        $employeename   = trim(preg_replace('/\s+/', ' ', $exceldata[$row][1] ?? ''));
	        $payablegross   = floatval($exceldata[$row][2] ?? 0);
	        $WorkedDays  = floatval($exceldata[$row][3] ?? 0);
	        $daysinmonth = getDaysInMonthForSalary($salaryyear, $salarymonth);

	        $empcheck = fetchEmployeeData($hrmconn, $companyid, $subcompanyid, $projectid, 'EmployeeID', $employeeID);
	        if(count($empcheck)>0){
	        	$employeeID = $empcheck[0]['EmployeeID'];
	        	$PaymentDate = $emkkey['PaymentDate'];
                $CurrencyCode = $emkkey['CurrencyCode'];
                $PaymentMethod = $emkkey['PaymentMethod'];
	        }else{
	        	$newemp++;
	        }

	        if ($employeename != '' && $employeeID!='') {
	            $newexceldata[] = [
	                "EmployeeId"    => $employeeID,
	                "payablegross"    => $payablegross,
	                "payablesalary"   => $payablesalary,
	                "daysinmonth"   => $daysinmonth,
	                "WorkedDays"   => $WorkedDays,
	                "salaryyear"   => $salaryyear,
	                "salarymonth"   => $salarymonth,
	                "salaryyearmonth"     => $expected_salary_month
	            ];
	        }
	    }

	    if (count($newexceldata) > 0 && $newemp==0) {
	    	foreach ($newexceldata as $newexkey) {
	    		$saveatt = insertOrUpdateEmployeeCalculatedAttendance($hrmconn, $loginname, $companyid, $subcompanyid, $projectid, $newexkey['EmployeeId'], $newexkey['salaryyear'], $newexkey['salarymonth'], $newexkey['daysinmonth'], $newexkey['daysinmonth'], $newexkey['WorkedDays']);
	    		if($saveatt[0]['status']=="Success" || $saveatt[0]['status']=="success"){
	    			// print_r($newexceldata);
	    		}
	    	}
	        $returninfo[] = [
	            'status' => 'Success',
	            'message' => " Manual uploaded salary successfully. ==".$newemp,
	            'data' => $newexceldata
	        ];
	    }else if(count($newexceldata) > 0 && $newemp>0){
	    	$returninfo[] = [
	            'status' => 'Unknownemployee',
	            'message' => 'Some employee details did not match with our employee master. If they are new employees, please register them in the payroll first or remove them from the Excel sheet before uploading.'
	        ];
	    } else {
	        $returninfo[] = [
	            'status' => 'Error',
	            'message' => 'No valid employee salary data found.'
	        ];
	    }
	} else {
	    $returninfo[] = [
	        'status' => 'Error',
	        'message' => 'No file uploaded.'
	    ];
	}

	header('Content-Type: application/json');
	echo json_encode($returninfo);
}else if ($action == "UploadEmployeeAttenanceMachineExcelSheet") {
    $companyid    = $_POST['companyid'] ?? '';
    $subcompanyid = $_POST['subcompanyid'] ?? '';
    $projectid    = $_POST['projectid'] ?? '';
    $loginid      = $_POST['loginid'] ?? '';
    $loginname    = $_POST['loginname'] ?? '';

    $locationId   = $_POST['locationId'] ?? '';
    $locationName = $_POST['locationName'] ?? '';
    $dateFrom     = $_POST['dateFrom'] ?? '';
    $dateTo       = $_POST['dateTo'] ?? '';

    $attyearmonthex = explode("-", $dateFrom);
    $attyear = $attyearmonthex[0] ?? '';
    $attmonth = $attyearmonthex[1] ?? '';

    $expected_salary_month = $_POST['slarymonthyear'] ?? ''; // Expected in YYYY-MM format
    $returninfo = [];

    // Basic validation
    if (empty($companyid) || empty($projectid) || empty($locationId) || empty($dateFrom) || empty($dateTo)) {
        header('Content-Type: application/json');
        echo json_encode([['status' => 'Error', 'message' => 'Missing required parameters.']]);
        exit;
    }

    // print_r($_FILES['empAttendancefile']);
    if (isset($_FILES['empAttendancefile']) && !empty($_FILES['empAttendancefile']['tmp_name'])) {

        // Check duplicate upload
        $checkSql = "SELECT COUNT(*) as cnt FROM hrm_AttendanceFiles 
                     WHERE projectid=? AND locationId=? AND dateFrom=? AND dateTo=?";
        $stmtCheck = $hrmconn->prepare($checkSql);
        if (!$stmtCheck) {
            header('Content-Type: application/json');
            echo json_encode([['status' => 'Error', 'message' => 'DB prepare error: ' . $hrmconn->error]]);
            exit;
        }
        $stmtCheck->bind_param("ssss", $projectid, $locationId, $dateFrom, $dateTo);
        $stmtCheck->execute();
        $resultCheck = $stmtCheck->get_result();
        $rowCheck = $resultCheck ? $resultCheck->fetch_assoc() : null;
        if ($rowCheck && intval($rowCheck['cnt']) > 0) {
            header('Content-Type: application/json');
            echo json_encode([['status' => 'Error', 'message' => "Attendance for this project, location, and date range ($dateFrom to $dateTo) has already been uploaded."]]);
            exit;
        }

        $file_ext = strtolower(pathinfo($_FILES['empAttendancefile']['name'], PATHINFO_EXTENSION));
        $file_tmp = $_FILES['empAttendancefile']['tmp_name'];
        $exceldata = [];
        $errorMsg = '';

        // Save uploaded file
        $uploadDir = "../attachementfile/UploadedAttendanceSheet/" . $companyid . "/" . $subcompanyid . "/" . $projectid . "/" . str_replace(" ", "", $locationName) . "/" . $dateFrom . "_" . $dateTo . "/";
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                header('Content-Type: application/json');
                echo json_encode([['status' => 'Error', 'message' => 'Failed to create upload directory.']]);
                exit;
            }
        }
        $storedFileName = "attendance_" . $companyid . "_" . $projectid . "_" . date("Ymd_His") . "." . $file_ext;
        $storedFilePath = $uploadDir . $storedFileName;
        // echo $storedFilePath;
        if (!move_uploaded_file($file_tmp, $storedFilePath)) {
            header('Content-Type: application/json');
            echo json_encode([['status' => 'Error', 'message' => 'Failed to save uploaded file.']]);
            exit;
        }

        // Parse file
        if ($file_ext === "xlsx") {
            $xlsx = SimpleXLSX::parse($storedFilePath);
            if (!$xlsx) {
                $errorMsg = SimpleXLSX::parseError();
            } else {
                $exceldata = $xlsx->rows();
            }
        } elseif ($file_ext === "xls") {
            $xls = SimpleXLS::parse($storedFilePath);
            if (!$xls) {
                $errorMsg = SimpleXLS::parseError();
            } else {
                $exceldata = $xls->rows();
            }
            // print_r($exceldata);
        } elseif ($file_ext === "csv") {
            if (($handle = fopen($storedFilePath, "r")) !== false) {
                while (($row = fgetcsv($handle, 1000, ",")) !== false) {
                    $exceldata[] = $row;
                }
                fclose($handle);
            } else {
                $errorMsg = "Failed to open CSV file.";
            }
        } else {
            // Unsupported file type
            unlink($storedFilePath);
            header('Content-Type: application/json');
            echo json_encode([['status' => 'Error', 'message' => 'Unsupported file type: ' . $file_ext]]);
            exit;
        }

        // print_r($exceldata);

        if (!empty($errorMsg)) {
            unlink($storedFilePath);
            header('Content-Type: application/json');
            echo json_encode([['status' => 'Error', 'message' => 'Excel parsing error: ' . $errorMsg]]);
            exit;
        }

        // Extract period dates (row 0 col 0 expected like "dd/mm/YYYY To dd/mm/YYYY")
        $fromDateExcel = $toDateExcel = "";
        // echo $exceldata[0][0];
        if (!empty($exceldata[0][0])) {
            $cellValue = trim($exceldata[0][0]);
            if (preg_match('/(\d{2}\/\d{2}\/\d{4})\s*To\s*(\d{2}\/\d{2}\/\d{4})/i', $cellValue, $matches)) {
                $d1 = DateTime::createFromFormat("d/m/Y", $matches[1]);
                $d2 = DateTime::createFromFormat("d/m/Y", $matches[2]);
                if ($d1 && $d2) {
                    $fromDateExcel = $d1->format("Y-m-d");
                    $toDateExcel = $d2->format("Y-m-d");
                }
            }
        }
        if (!empty($fromDateExcel) && $dateFrom != $fromDateExcel) {
            unlink($storedFilePath);
            header('Content-Type: application/json');
            echo json_encode([['status' => 'Error', 'message' => "Date From mismatch: Excel ($fromDateExcel) vs Selected ($dateFrom)"]]);
            exit;
        } elseif (!empty($toDateExcel) && $dateTo != $toDateExcel) {
            unlink($storedFilePath);
            header('Content-Type: application/json');
            echo json_encode([['status' => 'Error', 'message' => "Date To mismatch: Excel ($toDateExcel) vs Selected ($dateTo)"]]);
            exit;
        }
        // If excel didn't contain dates, we could choose to allow or error. I keep current behavior (skip check if not found).

        // Extract location (example at excel row 5 col 0)
        $ExcelLocation = "";
        if (!empty($exceldata[5][0])) {
            $cellValue = trim($exceldata[5][0]);
            if (preg_match('/Location\s*:\s*(.+)$/i', $cellValue, $matches)) {
                $ExcelLocation = trim($matches[1]);
            }
        }
        
        // Extract location (example at excel row 5 col 0)
        $ExcelLocation = "";
        if (!empty($exceldata[5][0])) {
            $cellValue = trim($exceldata[5][0]);
            if (preg_match('/Location\s*:\s*(.+)$/i', $cellValue, $matches)) {
                $ExcelLocation = trim($matches[1]);
            }
        }

        if (!empty($ExcelLocation) && strtolower(trim($ExcelLocation)) != strtolower(trim($locationName))) {
            unlink($storedFilePath);
            header('Content-Type: application/json');
            echo json_encode([['status' => 'Error', 'message' => "Location mismatch: Excel ($ExcelLocation) vs Selected ($locationName)"]]);
            exit;
        }
        // get the index of total day
        // print_r($exceldata);
        if($exceldata){
	        $WorkedDaysIndex = findTotalDaysCell($exceldata, "Worked Days");
	        $LeaveDaysIndex = findTotalDaysCell($exceldata, "Leave Days");
	        $AbsentDaysIndex = findTotalDaysCell($exceldata, "Absent Days");
	        $TotalDaysIndex = findTotalDaysCell($exceldata, "Total Days");
	    }
        // Begin transaction
        $hrmconn->begin_transaction();
        try {
            // Save file meta to DB
            $insertFile = "INSERT INTO hrm_AttendanceFiles (companyid, subcompanyid, projectid, locationId, dateFrom, dateTo, stored_filename, file_path, uploaded_by)
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
			$stmt = $hrmconn->prepare($insertFile);
			if (!$stmt) {
			    throw new Exception("DB prepare failed: " . $hrmconn->error);
			}
			$storedFilePath = str_replace("../", "", $storedFilePath);

			$stmt->bind_param("sssssssss", $companyid, $subcompanyid, $projectid, $locationId, $dateFrom, $dateTo, $storedFileName, $storedFilePath, $loginname);

			if (!$stmt->execute()) {
			    throw new Exception("Failed to record uploaded file in DB: " . $stmt->error);
			}

			// ✅ Get last insert ID
			// $lastInsertId = $hrmconn->insert_id;
			$insertedFileId = $stmt->insert_id ?? ''; // or $insertedId = $hrmconn->insert_id;

            // Process rows
            $header = 9; // original code used this
            $newexceldata = [];
            $newEmployeeexceldata = [];
            $newemp = 0; // counter for new/unmatched employees

            for ($row = $header + 1; $row < count($exceldata); $row++) {
                // Ensure first column exists and numeric
                if (isset($exceldata[$row][0]) && $exceldata[$row][0] !== "" && is_numeric($exceldata[$row][0]) && $exceldata[$row][0] > 0) {
                    $EmployeeID   = $exceldata[$row][0] ?? '';
                    $EmployeeName = !empty($exceldata[$row][2]) ? trim(strtoupper($exceldata[$row][2])) : '';
                    $WorkedDays = $exceldata[$row][$WorkedDaysIndex['cellIndex']] ?? '0';
                    $TotalDays  = $exceldata[$row][$TotalDaysIndex['cellIndex']] ?? '0';

                    if (empty($EmployeeID) && empty($EmployeeName)) continue;

                    if (!is_numeric($TotalDays) || floatval($TotalDays) < 1) continue;

                    // get days in month helper (assumed to exist)
                    $daysinmonth = getDaysInMonthForSalary($attyear, $attmonth);

                    // fetch employee from DB using the ID or other identification
                    // Note: use the same variable ($EmployeeID)
                    $empcheck = fetchEmployeeData($hrmconn, $companyid, $subcompanyid, $projectid, 'EmployeeID', $EmployeeID);
                    if (is_array($empcheck) && count($empcheck) > 0 && $EmployeeID!="") {
                        // matched employee — ensure we use DB employee id consistently
                        $dbEmployeeId = $empcheck[0]['EmployeeID'];
                        $newexceldata[] = [
                            "EmployeeId"    => $dbEmployeeId,
                            "payablegross"  => 0,
                            "payablesalary" => 0,
                            "daysinmonth"   => $daysinmonth,
                            "WorkedDays"    => $WorkedDays,
                            "salaryyear"    => $attyear,
                            "salarymonth"   => $attmonth,
                            "salaryyearmonth" => $expected_salary_month
                        ];
                    } else {
                        // employee not found in master
                        $newemp++;
                        // Optionally collect missing employees details for reporting
                        $newEmployeeexceldata[] = [
                            "EmployeeId"    => $EmployeeID,
                            "EmployeeName"  => $EmployeeName,
                            "WorkedDays"    => $WorkedDays,
                            "salaryyear"    => $attyear,
                            "salarymonth"   => $attmonth
                        ];
                    }
                }
            }

            // If we have matched employees and none unmatched -> save attendance
            // echo count($newexceldata)."===".$newemp;
            if (count($newexceldata) > 0 && $newemp == 0) {
                foreach ($newexceldata as $newexkey) {
                    // Use a function to insert/update calculated attendance.
                    // Adjust parameters if your function signature differs.
                    // I preserved the original order but you should ensure it matches your function definition.
                    $saveatt = insertOrUpdateEmployeeCalculatedAttendance(
                        $hrmconn,
                        $loginname,
                        $companyid,
                        $subcompanyid,
                        $projectid,
                        $newexkey['EmployeeId'],
                        $newexkey['salaryyear'],
                        $newexkey['salarymonth'],
                        $newexkey['daysinmonth'],
                        $newexkey['daysinmonth'], // original code had this duplicated; adjust if needed
                        $newexkey['WorkedDays'],
                        $insertedFileId
                    );
                    // print_r($saveatt);
                    // Optionally check $saveatt result structure here
                }
                $returninfo[] = [
                    'status' => 'Success',
                    'message' => "Attendance sheet uploaded and salary data processed successfully.----",
                    'data' => $newexceldata,
                    'newemployeedata' => $newEmployeeexceldata
                ];
            } else if (count($newexceldata) > 0 && $newemp > 0) {
                $returninfo[] = [
                    'status' => 'Unknownemployee',
                    'message' => 'Some employee details did not match with our employee master. If they are new employees, please register them in the payroll first or remove them from the Excel sheet before uploading.',
                    'unmatched_count' => $newemp, 'newemployeedata'=>$newEmployeeexceldata
                ];
            } else {
                $returninfo[] = [
                    'status' => 'Error',
                    'message' => 'No valid employee salary data found in the uploaded sheet.'
                ];
            }

            $hrmconn->commit();

            // Save final success record message
            // $returninfo[] = [
            //     'status' => 'Success',
            //     'message' => 'Attendance uploaded & saved successfully for location ' . $locationName,
            //     'file' => $storedFileName
            // ];
        } catch (Exception $e) {
            $hrmconn->rollback();
            if (file_exists($storedFilePath)) {
                unlink($storedFilePath); // delete file if rollback
            }
            $returninfo[] = ['status' => 'Error', 'message' => $e->getMessage()];
        }
    } else {
        $returninfo[] = ['status' => 'Error', 'message' => 'No file uploaded.'];
    }
    header('Content-Type: application/json');
    echo json_encode($returninfo);
    exit;
}else if ($action == "UploadEmployeeAttenanceMachineExcelSheet__") {
   $companyid    = $_POST['companyid'] ?? '';
$subcompanyid = $_POST['subcompanyid'] ?? '';
$projectid    = $_POST['projectid'] ?? '';
$loginid      = $_POST['loginid'] ?? '';
$loginname    = $_POST['loginname'] ?? '';

$locationId   = $_POST['locationId'] ?? '';
$locationName = $_POST['locationName'] ?? '';
$dateFrom     = $_POST['dateFrom'] ?? '';
$dateTo       = $_POST['dateTo'] ?? '';

$attyearmonthex = explode("-", $dateFrom);
$attyear  = $attyearmonthex[0] ?? '';
$attmonth = $attyearmonthex[1] ?? '';

$expected_salary_month = $_POST['slarymonthyear'] ?? ''; // Expected in YYYY-MM format
$returninfo = [];

// ✅ Basic validation
if (empty($companyid) || empty($projectid) || empty($locationId) || empty($dateFrom) || empty($dateTo)) {
    header('Content-Type: application/json');
    echo json_encode([['status' => 'Error', 'message' => 'Missing required parameters.']]);
    exit;
}

if (isset($_FILES['empAttendancefile']) && is_uploaded_file($_FILES['empAttendancefile']['tmp_name'])) {

	

    // ✅ Check duplicate upload
    $checkSql = "SELECT COUNT(*) as cnt FROM hrm_AttendanceFiles 
                 WHERE projectid=? AND locationId=? AND dateFrom=? AND dateTo=?";
    $stmtCheck = $hrmconn->prepare($checkSql);
    if (!$stmtCheck) {
        echo json_encode([['status' => 'Error', 'message' => 'DB prepare error: ' . $hrmconn->error]]);
        exit;
    }
    $stmtCheck->bind_param("ssss", $projectid, $locationId, $dateFrom, $dateTo);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();
    $rowCheck = $resultCheck ? $resultCheck->fetch_assoc() : null;

    if (!empty($rowCheck['cnt']) && intval($rowCheck['cnt']) > 0) {
        echo json_encode([['status' => 'Error', 'message' => "Attendance for this project, location, and date range ($dateFrom to $dateTo) has already been uploaded."]]);
        exit;
    }

    // ✅ File extension and path setup
    $file_ext = strtolower(pathinfo($_FILES['empAttendancefile']['name'], PATHINFO_EXTENSION));
    $file_tmp = $_FILES['empAttendancefile']['tmp_name'];
    $exceldata = [];
    $errorMsg = '';

    // ✅ Corrected upload path (missing slash before ../)
    $uploadDir = __DIR__ . "/../attachementfile/UploadedAttendanceSheet/" . 
                 $companyid . "/" . $subcompanyid . "/" . $projectid . "/" . 
                 str_replace(" ", "_", $locationName) . "/" . $dateFrom . "_" . $dateTo . "/";

    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            echo json_encode([['status' => 'Error', 'message' => 'Failed to create upload directory.']]);
            exit;
        }
    }

    $storedFileName = "attendance_" . $companyid . "_" . $projectid . "_" . date("Ymd_His") . "." . $file_ext;
    $storedFilePath = $uploadDir . $storedFileName;

    if (!move_uploaded_file($file_tmp, $storedFilePath)) {
        echo json_encode([['status' => 'Error', 'message' => 'Failed to save uploaded file.']]);
        exit;
    }

    // ✅ Parse uploaded file
    if ($file_ext === "xlsx") {
        $xlsx = SimpleXLSX::parse($storedFilePath);
        if (!$xlsx) {
            $errorMsg = SimpleXLSX::parseError();
        } else {
            $exceldata = $xlsx->rows();
        }
    } elseif ($file_ext === "xls") {
        $xls = SimpleXLS::parse($storedFilePath);
        if (!$xls) {
            $errorMsg = SimpleXLS::parseError();
        } else {
            $exceldata = $xls->rows();
        }
    } elseif ($file_ext === "csv") {
        if (($handle = fopen($storedFilePath, "r")) !== false) {
            while (($row = fgetcsv($handle, 1000, ",")) !== false) {
                $exceldata[] = $row;
            }
            fclose($handle);
        } else {
            $errorMsg = "Failed to open CSV file.";
        }
    } else {
        unlink($storedFilePath);
        echo json_encode([['status' => 'Error', 'message' => 'Unsupported file type: ' . $file_ext]]);
        exit;
    }
    
    // print_r($exceldata);
    if (!empty($errorMsg)) {
        unlink($storedFilePath);
        echo json_encode([['status' => 'Error', 'message' => 'Excel parsing error: ' . $errorMsg]]);
        exit;
    }

    // ✅ Extract date range from Excel
    $fromDateExcel = $toDateExcel = "";
    if (!empty($exceldata[0][0])) {
        $cellValue = trim($exceldata[0][0]);
        if (preg_match('/(\d{2}\/\d{2}\/\d{4})\s*To\s*(\d{2}\/\d{2}\/\d{4})/i', $cellValue, $matches)) {
            $d1 = DateTime::createFromFormat("d/m/Y", $matches[1]);
            $d2 = DateTime::createFromFormat("d/m/Y", $matches[2]);
            if ($d1 && $d2) {
                $fromDateExcel = $d1->format("Y-m-d");
                $toDateExcel = $d2->format("Y-m-d");
            }
        }
    }

    if (!empty($fromDateExcel) && $dateFrom != $fromDateExcel) {
        unlink($storedFilePath);
        echo json_encode([['status' => 'Error', 'message' => "Date From mismatch: Excel ($fromDateExcel) vs Selected ($dateFrom)"]]);
        exit;
    } elseif (!empty($toDateExcel) && $dateTo != $toDateExcel) {
        unlink($storedFilePath);
        echo json_encode([['status' => 'Error', 'message' => "Date To mismatch: Excel ($toDateExcel) vs Selected ($dateTo)"]]);
        exit;
    }

    // ✅ Extract Location from Excel
    $ExcelLocation = "";
    if (!empty($exceldata[5][0])) {
        $cellValue = trim($exceldata[5][0]);
        if (preg_match('/Location\s*:\s*(.+)$/i', $cellValue, $matches)) {
            $ExcelLocation = trim($matches[1]);
        }
    }

    if (!empty($ExcelLocation) && strtolower(trim($ExcelLocation)) != strtolower(trim($locationName))) {
        unlink($storedFilePath);
        echo json_encode([['status' => 'Error', 'message' => "Location mismatch: Excel ($ExcelLocation) vs Selected ($locationName)"]]);
        exit;
    }

    // ✅ Identify index of required headers
    if (!empty($exceldata)) {
        $WorkedDaysIndex = findTotalDaysCell($exceldata, "Worked Days");
        $LeaveDaysIndex  = findTotalDaysCell($exceldata, "Leave Days");
        $AbsentDaysIndex = findTotalDaysCell($exceldata, "Absent Days");
        $TotalDaysIndex  = findTotalDaysCell($exceldata, "Total Days");
    }

    // ✅ Begin transaction
    $hrmconn->begin_transaction();
    try {
        // ✅ Insert uploaded file info
        $insertFile = "INSERT INTO hrm_AttendanceFiles 
            (companyid, subcompanyid, projectid, locationId, dateFrom, dateTo, stored_filename, file_path, uploaded_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $hrmconn->prepare($insertFile);
        if (!$stmt) {
            throw new Exception("DB prepare failed: " . $hrmconn->error);
        }

        $dbFilePath = str_replace(__DIR__ . "/../", "", $storedFilePath);
        $stmt->bind_param("sssssssss", $companyid, $subcompanyid, $projectid, $locationId, $dateFrom, $dateTo, $storedFileName, $dbFilePath, $loginname);
        $stmt->execute();
        $insertedFileId = $stmt->insert_id;

        // ✅ Process each row
        $headerRow = 9;
        $newexceldata = [];
        $newEmployeeexceldata = [];
        $newemp = 0;

        for ($row = $headerRow + 1; $row < count($exceldata); $row++) {
            if (!empty($exceldata[$row][0]) && is_numeric($exceldata[$row][0])) {
                $EmployeeID   = trim($exceldata[$row][0]);
                $EmployeeName = !empty($exceldata[$row][2]) ? trim(strtoupper($exceldata[$row][2])) : '';
                $WorkedDays   = $exceldata[$row][$WorkedDaysIndex['cellIndex']] ?? '0';
                $TotalDays    = $exceldata[$row][$TotalDaysIndex['cellIndex']] ?? '0';

                if (empty($EmployeeID) || floatval($TotalDays) < 1) continue;

                $daysinmonth = getDaysInMonthForSalary($attyear, $attmonth);
                $empcheck = fetchEmployeeData($hrmconn, $companyid, $subcompanyid, $projectid, 'EmployeeID', $EmployeeID);

                if (!empty($empcheck)) {
                    $dbEmployeeId = $empcheck[0]['EmployeeID'];
                    $newexceldata[] = [
                        "EmployeeId" => $dbEmployeeId,
                        "WorkedDays" => $WorkedDays,
                        "daysinmonth" => $daysinmonth,
                        "salaryyear" => $attyear,
                        "salarymonth" => $attmonth,
                        "salaryyearmonth" => $expected_salary_month
                    ];
                } else {
                    $newemp++;
                    $newEmployeeexceldata[] = [
                        "EmployeeId"   => $EmployeeID,
                        "EmployeeName" => $EmployeeName,
                        "WorkedDays"   => $WorkedDays,
                        "salaryyear"   => $attyear,
                        "salarymonth"  => $attmonth
                    ];
                }
            }
        }

        if (count($newexceldata) > 0 && $newemp == 0) {
            foreach ($newexceldata as $newexkey) {
                insertOrUpdateEmployeeCalculatedAttendance(
                    $hrmconn,
                    $loginname,
                    $companyid,
                    $subcompanyid,
                    $projectid,
                    $newexkey['EmployeeId'],
                    $newexkey['salaryyear'],
                    $newexkey['salarymonth'],
                    $newexkey['daysinmonth'],
                    $newexkey['WorkedDays'],
                    $insertedFileId
                );
            }

            $returninfo[] = [
                'status' => 'Success',
                'message' => "Attendance sheet uploaded and salary data processed successfully.",
                'data' => $newexceldata
            ];
        } elseif ($newemp > 0) {
            $returninfo[] = [
                'status' => 'Unknownemployee',
                'message' => 'Some employee records not found in employee master. Register them first.',
                'unmatched_count' => $newemp,
                'newemployeedata' => $newEmployeeexceldata
            ];
        } else {
            $returninfo[] = ['status' => 'Error', 'message' => 'No valid employee data found.'];
        }

        $hrmconn->commit();

    } catch (Exception $e) {
        $hrmconn->rollback();
        if (file_exists($storedFilePath)) unlink($storedFilePath);
        $returninfo[] = ['status' => 'Error', 'message' => $e->getMessage()];
    }
} else {
    $returninfo[] = ['status' => 'Error', 'message' => 'No file uploaded.'];
}

header('Content-Type: application/json');
echo json_encode($returninfo);
}else if($action=="uploadbulkemployeedetails_excelfile"){
	$companyid     = $_POST['companyid']    ?? '';
	$subcompanyid  = $_POST['subcompanyid'] ?? '';
	$projectid     = $_POST['projectid']    ?? '';
	$loginid       = $_POST['loginid']      ?? '';
	$loginname     = $_POST['loginname']    ?? '';
	$returninfo    = [];

	// Check and parse uploaded file
	if (isset($_FILES['bulkemployeefile']) && !empty($_FILES['bulkemployeefile']['tmp_name'])) {
	    $file_ext = strtolower(pathinfo($_FILES['bulkemployeefile']['name'], PATHINFO_EXTENSION));
	    $xlsx = ($file_ext === "xlsx") ? SimpleXLSX::parse($_FILES['bulkemployeefile']['tmp_name']) :
	           (($file_ext === "xls") ? SimpleXLS::parse($_FILES['bulkemployeefile']['tmp_name']) : null);

	    if (!$xlsx) {
	        $errorMsg = ($file_ext === "xlsx") ? SimpleXLSX::parseError() : SimpleXLS::parseError();
	        echo json_encode([
	            [
	                'status' => 'Error',
	                'message' => 'Excel parsing error: ' . $errorMsg
	            ]
	        ]);
	        exit;
	    }

	    $exceldata = $xlsx->rows();
	    $header = HRMdetectHeaderRowFromArray($exceldata);

	    if ($header < 0) {
	        echo json_encode([
	            [
	                'status' => 'Error',
	                'message' => 'This sheet contains no valid header. Please correct the sheet and try again.'
	            ]
	        ]);
	        exit;
	    }

	    $valid = 0;
	    $requiredfieldmsg = [];

	    for ($row = $header + 1; $row < count($exceldata); $row++) {
	    	$ExistEmployeeID     = ucwords($exceldata[$row][0] ?? '');
	        $titleName     = ucwords($exceldata[$row][1] ?? '');
	        $employeeName  = trim(preg_replace('/\s+/', ' ', $exceldata[$row][2] ?? ''));
	        $parts = splitFullName($employeeName);
	        $FirstName   = $parts['first_name'];
	        $MiddleName  = $parts['middle_name'];
	        $LastName    = $parts['last_name'];

	        $dateOfBirth = parseExcelDate($exceldata[$row][3]);
			if (!empty($dateOfBirth)) {
			    $parsedDate = strtotime($dateOfBirth);
			    if ($parsedDate && $parsedDate > 0) {
			        $dateOfBirth = date('Y-m-d', $parsedDate);
			    } else {
			        $dateOfBirth = ''; // Invalid date will be treated as empty
			    }
			}
	        $gender        = $exceldata[$row][4] ?? '';
	        $dateOfJoining = parseExcelDate($exceldata[$row][5]);
			if (!empty($dateOfJoining)) {
			    $parsedDate = strtotime($dateOfJoining);
			    if ($parsedDate && $parsedDate > 0) {
			        $dateOfJoining = date('Y-m-d', $parsedDate);
			    } else {
			        $dateOfJoining = ''; // Invalid date will be treated as empty
			    }
			}

	        $grossSalary        = $exceldata[$row][6] ?? '';
	        $panNumber     = !empty($exceldata[$row][7]) ? trim(strtoupper($exceldata[$row][7])) : '';
	        $aadharNumber  = !empty($exceldata[$row][8]) ? trim(strtoupper($exceldata[$row][8])) : '';
	        $bankName      = trim(strtoupper($exceldata[$row][9] ?? ''));
	        $accountNumber = trim($exceldata[$row][10] ?? '');
	        $ifscCode      = trim(strtoupper($exceldata[$row][11] ?? ''));
	        $mobileNo      = trim($exceldata[$row][12] ?? '');
	        $emailAddress      = trim($exceldata[$row][13] ?? '');
	        $addressLine1      = trim($exceldata[$row][14] ?? '');
	        $addressLine2      = trim($exceldata[$row][15] ?? '');
	        $postalCode      = trim($exceldata[$row][16] ?? '');

	        // Skip completely empty rows
	        if (empty($employeeName) && empty($bankName) && empty($dateOfBirth) && empty($gender) && empty($dateOfJoining) && empty($grossSalary) && empty($accountNumber) && empty($ifscCode) && empty($mobileNo)) {
	            continue;
	        }

	        $requiredFields = [
	        	'Employee Name'  => $employeeName,
	            'Bank Name'      => $bankName,
	            'Date of Birth'  => $dateOfBirth,
	            'Gender'  => $gender,
	            'Date of Joining'  => $dateOfJoining,
	            'Gross Salary'  => $grossSalary,
	            'Account Number' => $accountNumber,
	            'IFSC Code'      => $ifscCode
	        ];

	        $missingFields = [];
	        foreach ($requiredFields as $fieldName => $value) {
	            if (empty(trim($value))) {
	                $missingFields[] = $fieldName;
	            }
	        }

	        if (!empty($missingFields)) {
	            $valid++;
	            $missingList = implode(', ', $missingFields);
	            $requiredfieldmsg[] = [
	                "status" => "Warning",
	                "message" => "Row " . ($row + 1) . " - Employee \"$employeeName\": Missing required field(s): $missingList"
	            ];
	            continue;
	        }
	    }

	    if ($valid > 0) {
	        header('Content-Type: application/json');
	        echo json_encode($requiredfieldmsg);
	        exit;
	    }

	    // Begin processing valid rows
	    $ExistEmployeeID     ="";
	    for ($row = $header + 1; $row < count($exceldata); $row++) {
	    	$ExistEmployeeID     = ucwords($exceldata[$row][0] ?? '');
	        $titleName     = ucwords($exceldata[$row][1] ?? '');
	        $employeeName  = trim(preg_replace('/\s+/', ' ', $exceldata[$row][2] ?? ''));
	        $parts = splitFullName($employeeName);
	        $FirstName   = $parts['first_name'];
	        $MiddleName  = $parts['middle_name'];
	        $LastName    = $parts['last_name'];

	        $dateOfBirth = parseExcelDate($exceldata[$row][3]);
			if (!empty($dateOfBirth)) {
			    $parsedDate = strtotime($dateOfBirth);
			    if ($parsedDate && $parsedDate > 0) {
			        $dateOfBirth = date('Y-m-d', $parsedDate);
			    } else {
			        $dateOfBirth = ''; // Invalid date will be treated as empty
			    }
			}
	        $gender        = $exceldata[$row][4] ?? '';
	        $dateOfJoining = parseExcelDate($exceldata[$row][5]);
			if (!empty($dateOfJoining)) {
			    $parsedDate = strtotime($dateOfJoining);
			    if ($parsedDate && $parsedDate > 0) {
			        $dateOfJoining = date('Y-m-d', $parsedDate);
			    } else {
			        $dateOfJoining = ''; // Invalid date will be treated as empty
			    }
			}

	        $grossSalary        = $exceldata[$row][6] ?? '';
	        $panNumber     = !empty($exceldata[$row][7]) ? trim(strtoupper($exceldata[$row][7])) : '';
	        $aadharNumber  = !empty($exceldata[$row][8]) ? trim(strtoupper($exceldata[$row][8])) : '';
	        $bankName      = trim(strtoupper($exceldata[$row][9] ?? ''));
	        $accountNumber = trim($exceldata[$row][10] ?? '');
	        $ifscCode      = trim(strtoupper($exceldata[$row][11] ?? ''));
	        $mobileNo      = trim($exceldata[$row][12] ?? '');
	        $emailAddress      = trim($exceldata[$row][13] ?? '');
	        $addressLine1      = trim($exceldata[$row][14] ?? '');
	        $addressLine2      = trim($exceldata[$row][16] ?? '');
	        $postalCode      = trim($exceldata[$row][17] ?? '');

	        // Skip completely empty rows
	        if (empty($employeeName) && empty($bankName) && empty($dateOfBirth) && empty($gender) && empty($dateOfJoining) && empty($grossSalary) && empty($accountNumber) && empty($ifscCode) && empty($mobileNo)) {
	            continue;
	        }

	        
	        $sql = "SELECT 1 FROM hrm_EmployeesMaster WHERE (tedit IS NULL OR tedit = '') AND companyid = ? AND subcompanyid = ? AND projectid = ? AND TRIM(FirstName) = ? AND DateOfBirth = ? AND MobileNo = ? LIMIT 1";

	        $chkstmt = $hrmconn->prepare($sql);
	        $chkstmt->bind_param('ssssss', $companyid, $subcompanyid, $projectid, $FirstName, $dateOfBirth, $mobileNo);
	        $chkstmt->execute();
	        $chkstmt->store_result();

	        if ($chkstmt->num_rows === 0) {
	            $CurrencyCode = 'INR';
	            $employeeData = [
	                "companyid" => $companyid, "subcompanyid" => $subcompanyid, "projectid" => $projectid, 'EmployeeID'=>$ExistEmployeeID,
	                "TitleName" => $titleName, "FirstName" => $FirstName, "MiddleName" => $MiddleName,
	                "LastName" => $LastName, "FatherName" => "", "MotherName" => "", "MaritalStatus" => "",
	                "SpouseName" => "", "Gender" => $gender, "DateOfBirth" => $dateOfBirth, "Email" => $emailAddress,
	                "MobileNo" => $mobileNo, "PanNumber" => $panNumber, "AadharNumber" => $aadharNumber,
	                "Nationality" => "", "PassportNumber" => "", "AddressLine1" => $addressLine1,
	                "AddressLine2" => $addressLine2, "AddressLine3" => "", "AddressLine4" => "",
	                "PostalCode" => $postalCode, "CountryName" => "", "addedby" => $loginname, "DateOfJoining"=>$dateOfJoining
	            ];



	            $empsaverst = saveOrUpdateEmployee($hrmconn, $employeeData);
	            // print_r($empsaverst);

	            if ($empsaverst) {
	            	$percntforbasic = getPayrollEarningsMasterList($hrmconn, $companyid, "ComponentId", 1);
			        $basicpercent = ($percntforbasic[0]['EmployeeEarningValue']/100);

			        $percntfrohra = getPayrollEarningsMasterList($hrmconn, $companyid, "ComponentId", 2);
			        $hrapercent = ($percntfrohra[0]['EmployeeEarningValue']/100);
			        
			        $conveyanceamount = getPayrollEarningsMasterList($hrmconn, $companyid, "ComponentId", 4);
			        $conveyancefixed = $conveyanceamount[0]['EmployeeEarningValue'];

			        $SalaryBrekup = GetMonthlySalaryBreakupFromGross($grossSalary, $basicpercent, $hrapercent, $conveyancefixed, false, false);
			        
			        $Slrydddata[] = [
			            "companyid" => $companyid,
			            "subcompanyid" => $subcompanyid,
			            "projectid" => $projectid,
			            "EmployeeID" => $empsaverst['employeeID'],
			            "CTC" => floatval($SalaryBrekup['monthly_ctc']*12)??0,
			            "GrossSalary" => floatval($SalaryBrekup['monthly_gross']*12)??0,
			            "BasicSalary" => floatval($SalaryBrekup['basic_salary']*12)??0,
			            "HRA" => floatval($SalaryBrekup['hra']*12)??0,
			            "ConveyanceAllowance" => floatval($SalaryBrekup['conveyance']*12),
			            "SpecialAllowance" => floatval($SalaryBrekup['special_allowance']*12)??0,
			            "Deductions" => 0,
			            "PaymentDate" => '',
			            "CurrencyCode" => $CurrencyCode??'INR',
			            "PaymentMethod" => '',
			            "Comments" => ''
			        ];
			        // Debugging line — can remove after testing
			        // print_r($Slrydddata); die();
			        $Errsst = insertOrUpdateEmployeeSalary($hrmconn, $Slrydddata);
			        $errncm = getPayrollEarningsMasterList($hrmconn, $companyid, "defaultforemployee", "1");
		            foreach ($errncm as $ermmkey) {
		                // Match by ComponentId
		                $Erdddata[] = [
		                    "companyid" => $companyid,
		                    "subcompanyid" => $subcompanyid,
		                    "projectid" => $projectid,
		                    "EmployeeId" => $empsaverst['employeeID'],
		                    "ComponentId" => $ermmkey['ComponentId'],
		                    "ComponentName" => $ermmkey['ComponentName'],
		                    "Frequency" => $ermmkey['Frequency'],
		                    "EmployeeEarningType" => $ermmkey['EmployeeEarningType'],
		                    "EmployeeEarningValue" => $ermmkey['EmployeeEarningValue'],
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
			        $Errsst = insertOrUpdateEmployeeEarnings($hrmconn, $Erdddata);

	                insertOrUpdateBankDetails($hrmconn, $companyid, $subcompanyid, $projectid, $empsaverst['employeeID'], $bankName, $accountNumber, $ifscCode, "", "", "", $CurrencyCode, "");

	                $returninfo[] = [
	                    'status'  => 'Success',
	                    'message' => "Employee $employeename registration saved successfully. Employee ID: {$empsaverst['employeeID']}",
	                    'EmployeeId' => $empsaverst['employeeID']
	                ];
	            }else{
				    $returninfo[] = [
				        'status'  => 'Error',
				        'message' => 'Employee '.$FirstName.' did not save, due to technical issue.'
				    ];      	
	            }
	        } else {
	            $returninfo[] = [
	                'status'  => 'Duplicate',
	                'message' => "Registration for employee $employeename could not be completed due to duplicate details.",
	                'EmployeeId' => ''
	            ];
	        }

	        $chkstmt->close();
	    }
	} else {
	    $returninfo[] = [
	        'status'  => 'Error',
	        'message' => 'No file uploaded.'
	    ];
	}
	header('Content-Type: application/json');
	echo json_encode($returninfo);
}else if($action=="UploadLabourAttenance"){
	$companyid    = $_POST['companyid'] ?? '';
	$subcompanyid = $_POST['subcompanyid'] ?? '';
	$projectid    = $_POST['projectid'] ?? '';
	$loginid      = $_POST['loginid'] ?? '';
	$loginname    = $_POST['loginname'] ?? '';
	$locationId   = $_POST['locationId'] ?? '';
	$locationName = $_POST['locationName'] ?? '';
	$dateFrom     = $_POST['dateFrom'] ?? '';
	$dateTo	      = $_POST['dateTo'] ?? '';

	$attyearmonthex = explode("-", $dateFrom);
	$attyear = $attyearmonthex[0];
	$attmonth = $attyearmonthex[1];
	$returninfo = [];

	if (isset($_FILES['lbAttendancefile']) && !empty($_FILES['lbAttendancefile']['tmp_name'])) {

		// ✅ Check if attendance already uploaded for this project, location, and date range
	    $checkSql = "SELECT COUNT(*) as cnt FROM hrm_AttendanceFiles 
	                 WHERE projectid=? AND locationId=? AND dateFrom=? AND dateTo=?";
	    $stmtCheck = $hrmconn->prepare($checkSql);
	    $stmtCheck->bind_param("ssss", $projectid, $locationId, $dateFrom, $dateTo);
	    $stmtCheck->execute();
	    $resultCheck = $stmtCheck->get_result()->fetch_assoc();
	    if ($resultCheck['cnt'] > 0) {
	        echo json_encode([['status' => 'Error', 'message' => "Attendance for this project, location, and date range ($dateFrom to $dateTo) has already been uploaded."]]);
	        exit;
	    }

	    $file_ext = strtolower(pathinfo($_FILES['lbAttendancefile']['name'], PATHINFO_EXTENSION));
	    $file_tmp = $_FILES['lbAttendancefile']['tmp_name'];
	    $exceldata = [];
	    $errorMsg = '';

	    // ✅ Save uploaded file first
	    $uploadDir = "../attachementfile/UploadedAttendanceSheet/".$companyid."/".$subcompanyid."/".$projectid."/".str_replace(" ", "", $locationName)."/".$dateFrom . "_" . $dateTo . "/";

	    if (!is_dir($uploadDir)) {
	        mkdir($uploadDir, 0777, true);
	    }
	    $storedFileName = "attendance_" . $companyid . "_" . $projectid . "_" . date("Ymd_His") . "." . $file_ext;
	    $storedFilePath = $uploadDir . $storedFileName;

	    if (!move_uploaded_file($file_tmp, $storedFilePath)) {
	        echo json_encode([
	            ['status' => 'Error', 'message' => 'Failed to save uploaded file.']
	        ]);
	        exit;
	    }

	    // ✅ Parse file
	    if ($file_ext === "xlsx") {
	        $xlsx = SimpleXLSX::parse($storedFilePath);
	        if (!$xlsx) {
	            $errorMsg = SimpleXLSX::parseError();
	        } else {
	            $exceldata = $xlsx->rows();
	        }
	    } elseif ($file_ext === "xls") {
	        $xls = SimpleXLS::parse($storedFilePath);
	        if (!$xls) {
	            $errorMsg = SimpleXLS::parseError();
	        } else {
	            $exceldata = $xls->rows();
	        }
	    } elseif ($file_ext === "csv") {
	        if (($handle = fopen($storedFilePath, "r")) !== false) {
	            while (($row = fgetcsv($handle, 1000, ",")) !== false) {
	                $exceldata[] = $row;
	            }
	            fclose($handle);
	        } else {
	            $errorMsg = "Failed to open CSV file.";
	        }
	    } else {
	        echo json_encode([
	            ['status' => 'Error', 'message' => 'Unsupported file type: ' . $file_ext]
	        ]);
	        unlink($storedFilePath); // delete file if unsupported
	        exit;
	    }

	    if (!empty($errorMsg)) {
	        echo json_encode([
	            ['status' => 'Error', 'message' => 'Excel parsing error: ' . $errorMsg]
	        ]);
	        unlink($storedFilePath); // cleanup
	        exit;
	    }

	    // ✅ Extract period dates
	    $fromDateExcel = $toDateExcel = "";
	    if (!empty($exceldata[0][0])) {
	        $cellValue = trim($exceldata[0][0]);
	        if (preg_match('/(\d{2}\/\d{2}\/\d{4})\s*To\s*(\d{2}\/\d{2}\/\d{4})/i', $cellValue, $matches)) {
	            $fromDateExcel = date("Y-m-d", DateTime::createFromFormat("d/m/Y", $matches[1])->getTimestamp());
	            $toDateExcel   = date("Y-m-d", DateTime::createFromFormat("d/m/Y", $matches[2])->getTimestamp());
	        }
	    }
	    if ($dateFrom != $fromDateExcel) {
	        echo json_encode([['status' => 'Error', 'message' => "Date From mismatch: Excel ($fromDateExcel) vs Selected ($dateFrom)"]]);
	        unlink($storedFilePath);
	        exit;
	    } elseif ($dateTo != $toDateExcel) {
	        echo json_encode([['status' => 'Error', 'message' => "Date To mismatch: Excel ($toDateExcel) vs Selected ($dateTo)"]]);
	        unlink($storedFilePath);
	        exit;
	    }

	    // ✅ Extract location
	    $ExcelLocation = "";
	    if (!empty($exceldata[5][0])) {
	        $cellValue = trim($exceldata[5][0]);
	        if (preg_match('/Location\s*:\s*(.+)$/i', $cellValue, $matches)) {
	            $ExcelLocation = trim($matches[1]);
	        }
	    }
	    if (strtolower(trim($ExcelLocation)) != strtolower(trim($locationName))) {
	        echo json_encode([['status' => 'Error', 'message' => "Location mismatch: Excel ($ExcelLocation) vs Selected ($locationName)"]]);
	        unlink($storedFilePath);
	        exit;
	    }

	    // ✅ Begin transaction
	    $hrmconn->begin_transaction();
	    try {

	    	$insertFile = "INSERT INTO hrm_AttendanceFiles (companyid, subcompanyid, projectid, locationId, dateFrom, dateTo, stored_filename, file_path, uploaded_by)
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
			$stmt = $hrmconn->prepare($insertFile);
			if (!$stmt) {
			    throw new Exception("DB prepare failed: " . $hrmconn->error);
			}
			$storedFilePath = str_replace("../", "", $storedFilePath);

			$stmt->bind_param("sssssssss", $companyid, $subcompanyid, $projectid, $locationId, $dateFrom, $dateTo, $storedFileName, $storedFilePath, $loginname);



	        // Save file reference in DB
	        // $insertFile = "INSERT INTO hrm_AttendanceFiles (companyid, subcompanyid, projectid, locationId, dateFrom, dateTo, file_path, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
	        // $stmt = $hrmconn->prepare($insertFile);
	        // $stmt->bind_param("ssssssss", $companyid, $subcompanyid, $projectid, $locationId, $dateFrom, $dateTo, $storedFileName, $loginname);
	        if (!$stmt->execute()) {
	            throw new Exception("Failed to record uploaded file in DB: " . $stmt->error);
	        }
	        // ✅ Get the auto-increment ID of the newly inserted record
			$insertedFileId = $stmt->insert_id ?? ''; // or $insertedId = $hrmconn->insert_id;
	        // get the index of total day
	        $WorkedDaysIndex = findTotalDaysCell($exceldata, "Worked Days");
	        $LeaveDaysIndex = findTotalDaysCell($exceldata, "Leave Days");
	        $AbsentDaysIndex = findTotalDaysCell($exceldata, "Absent Days");
	        $TotalDaysIndex = findTotalDaysCell($exceldata, "Total Days");
	        
	        $header = 9;
	        for ($row = $header + 1; $row < count($exceldata); $row++) {
	        	if (isset($exceldata[$row][0]) && is_numeric($exceldata[$row][0]) && $exceldata[$row][0] > 0){
		            $LabourId   = $exceldata[$row][0] ?? '';
		            $LabourName = !empty($exceldata[$row][2]) ? trim(strtoupper($exceldata[$row][2])) : '';
		            
		            $WorkedDays = $exceldata[$row][$WorkedDaysIndex['cellIndex']] ?? '';
		            $TotalDays  = $exceldata[$row][$TotalDaysIndex['cellIndex']] ?? '';
		            
		            if (empty($LabourId) && empty($LabourName)) continue;
		            
		            if (!is_numeric($TotalDays) || floatval($TotalDays) < 1) continue;

		            $chkLbexist = fetchLabourData($hrmconn, $companyid, $subcompanyid, $projectid, "EmployeeID", $LabourId);

		            if (count($chkLbexist) == 0) {
		                $parts = splitFullName($LabourName);
		                $FirstName = $parts['first_name'];
		                $MiddleName = $parts['middle_name'];
		                $LastName = $parts['last_name'];

		                $insertSql = "INSERT INTO hrm_LabourMaster (companyid, subcompanyid, projectid, EmployeeID, FirstName, MiddleName, LastName, addedby) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
		                $stmt = $hrmconn->prepare($insertSql);
		                $stmt->bind_param("ssssssss", $companyid, $subcompanyid, $projectid, $LabourId, $FirstName, $MiddleName, $LastName, $loginname);
		                if (!$stmt->execute()) {
		                    throw new Exception("Error inserting Labour: " . $stmt->error);
		                }
		            }

		            // Save attendance
		            $saveatt = insertOrUpdateLabourCalculatedAttendance(
		                $hrmconn, $loginname, $companyid, $subcompanyid, $projectid, 
		                $LabourId, $dateFrom, $dateTo, $locationId, $TotalDays, $insertedFileId
		            );
		            if (!$saveatt) {
		                throw new Exception("Error inserting attendance for LabourID: $LabourId");
		            }
		        }
	        }

	        $hrmconn->commit();
	        $returninfo[] = ['status' => 'Success', 'message' => 'Attendance uploaded & saved successfully for location '.$locationName, 'file' => $storedFileName];

	    } catch (Exception $e) {
	        $hrmconn->rollback();
	        unlink($storedFilePath); // delete file if rollback
	        $returninfo[] = ['status' => 'Error', 'message' => $e->getMessage()];
	    }

	} else {
	    $returninfo[] = ['status' => 'Error', 'message' => 'No file uploaded.'];
	}

	header('Content-Type: application/json');
	echo json_encode($returninfo);
}

function parseExcelDate($rawDate) {
    if (empty($rawDate)) return '';
    // Check if it's a numeric Excel serial date
    if (is_numeric($rawDate)) {
        // Excel base date is 1900-01-01 (day 1)
        $UNIX_DATE = ($rawDate - 25569) * 86400;
        $date = gmdate("Y-m-d", $UNIX_DATE);
    } else {
        // Try parsing string format
        $timestamp = strtotime($rawDate);
        $date = ($timestamp && $timestamp > 0) ? date('Y-m-d', $timestamp) : '';
    }
    return $date;
}



function HRMdetectHeaderRowFromArray($data) {
    foreach ($data as $index => $row) {
        $allFilled = true;
        $i=0;
        foreach ($row as $cell) {

             if (empty($cell) && !is_string($cell)) {
                // echo $i."string \n";
                $allFilled = false;
                break;
            }
            else if (empty($cell) && $cell !== '0') { 
                // echo $i."field\n";
                $allFilled = false;
                break;
            }
            $i++;
        }
        if ($allFilled) {
            // echo "final\n";
            return $index; 
        }
    }
    return -1;
}
?>