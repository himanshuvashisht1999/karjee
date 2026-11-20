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
if($action=="UploadLabourAttenanceExcelsheet"){
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
	    $uploadDir = "../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/labourAttendanceSheet/".str_replace(" ", "", $locationName)."/".$dateFrom . "_" . $dateTo . "/";

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
	            $exceldata = [];
	            foreach ($xlsx->sheetNames() as $sheetIndex => $sheetName) {
	                $sheetData = $xlsx->rows($sheetIndex);
	                if (!empty($sheetData)) {
	                    $exceldata = $sheetData;
	                    break;
	                }
	            }
	        }
	    } elseif ($file_ext === "xls") {
	        $xls = SimpleXLS::parse($storedFilePath);
	        if (!$xls) {
	            $errorMsg = SimpleXLS::parseError();
	        } else {
	            $exceldata = [];
	            $sheetCount = isset($xls->boundsheets) ? count($xls->boundsheets) : 1;
	            for ($sheetIndex = 0; $sheetIndex < $sheetCount; $sheetIndex++) {
	                $sheetData = $xls->rows($sheetIndex);
	                if (!empty($sheetData)) {
	                    $exceldata = $sheetData;
	                    break;
	                }
	            }
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

	    if (empty($exceldata) || !is_array($exceldata)) {
	        echo json_encode([
	            ['status' => 'Error', 'message' => 'Excel file could not be parsed properly. It may be empty, corrupted, or an unsupported HTML format disguised as .xls.']
	        ]);
	        unlink($storedFilePath);
	        exit;
	    }

	    // ✅ Extract period dates
	    $fromDateExcel = $toDateExcel = "";
	    for ($i = 0; $i <= 10; $i++) {
	        if (!empty($exceldata[$i][0])) {
	            $cellValue = trim($exceldata[$i][0]);
	            // Match formats like dd/mm/yyyy, d/m/yyyy, dd-mm-yyyy, yyyy-mm-dd, 01-May-2026, 1 May 2026
	            if (preg_match('/((?:\d{1,4}[\/\-.\s]+(?:[a-zA-Z]{3,}|\d{1,2})[\/\-.\s]+\d{1,4}))\s*(?:To|-|~)\s*((?:\d{1,4}[\/\-.\s]+(?:[a-zA-Z]{3,}|\d{1,2})[\/\-.\s]+\d{1,4}))/i', $cellValue, $matches)) {
	                $cleanFrom = str_replace(['/', '.'], '-', $matches[1]);
	                $cleanTo = str_replace(['/', '.'], '-', $matches[2]);
	                
	                $fromTime = strtotime($cleanFrom);
	                $toTime = strtotime($cleanTo);
	                
	                if ($fromTime && $toTime) {
	                    $fromDateExcel = date("Y-m-d", $fromTime);
	                    $toDateExcel   = date("Y-m-d", $toTime);
	                    break;
	                }
	            }
	        }
	    }
	    if ($fromDateExcel != "" && $dateFrom != $fromDateExcel) {
	        echo json_encode([['status' => 'Error', 'message' => "Date From mismatch: Excel ($fromDateExcel) vs Selected ($dateFrom)"]]);
	        unlink($storedFilePath);
	        exit;
	    } elseif ($toDateExcel != "" && $dateTo != $toDateExcel) {
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
	    }else if (!empty($exceldata[6][0])) {
	        $cellValue = trim($exceldata[6][0]);
	        if (preg_match('/Location\s*:\s*(.+)$/i', $cellValue, $matches)) {
	            $ExcelLocation = trim($matches[1]);
	        }
	    }
	    if ($ExcelLocation != "" && strtolower(trim($ExcelLocation)) != strtolower(trim($locationName))) {
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
	        
	        $insertedCount = 0;
	        $header = 9;
	        for ($row = $header + 1; $row < count($exceldata); $row++) {
	        	if (isset($exceldata[$row][0]) && !empty(trim((string)$exceldata[$row][0]))){
		            $LabourId   = trim((string)$exceldata[$row][0]);
		            $LabourName = !empty($exceldata[$row][2]) ? trim(strtoupper($exceldata[$row][2])) : '';
		            
		            $WorkedDays = $exceldata[$row][$WorkedDaysIndex['cellIndex']] ?? '';
		            $TotalDays  = $exceldata[$row][$TotalDaysIndex['cellIndex']] ?? '';
		            
		            if (empty($LabourId) && empty($LabourName)) continue;
		            
		            if (!is_numeric($TotalDays) || floatval($TotalDays) <= 0) continue;

		            $chkLbexist = fetchLabourData($hrmconn, $companyid, $subcompanyid, $projectid, "EmployeeID", $LabourId);

		            if (count($chkLbexist) == 0) {
		                $parts = splitFullName($LabourName);
		                $FirstName = $parts['first_name'];
		                $MiddleName = $parts['middle_name'];
		                $LastName = $parts['last_name'];
		                $seriesid = preg_replace('/\D/', '', $LabourId);
		                $insertSql = "INSERT INTO hrm_LabourMaster (companyid, subcompanyid, projectid, EmployeeID, seriesid, FirstName, MiddleName, LastName, addedby) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
		                $stmt = $hrmconn->prepare($insertSql);
		                $stmt->bind_param("sssssssss", $companyid, $subcompanyid, $projectid, $LabourId, $seriesid, $FirstName, $MiddleName, $LastName, $loginname);
		                if (!$stmt->execute()) {
		                    throw new Exception("Error inserting Labour: " . $stmt->error);
		                }
		            }

		            // Save attendance
		            $saveatt = insertOrUpdateLabourCalculatedAttendance(
		                $hrmconn, $loginname, $companyid, $subcompanyid, $projectid, 
		                $LabourId, $dateFrom, $dateTo, $locationId, $TotalDays, $insertedFileId
		            );

		            $attStatus = $saveatt['status'] ?? ($saveatt[0]['status'] ?? '');
		            $attMsg    = $saveatt['message'] ?? ($saveatt[0]['message'] ?? '');

		            if (empty($saveatt) || $attStatus === 'Error') {
		                $errDetail = !empty($attMsg) ? $attMsg : "Failed to save attendance";
		                throw new Exception("Error saving attendance for Labour ID $LabourId: " . $errDetail);
		            }

		            $insertedCount++;
		        }
	        }

	        if ($insertedCount === 0) {
	            throw new Exception("No valid attendance entries could be processed or inserted from the uploaded sheet.");
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

function findTotalDaysCell($data, $searchString) {
    if (!is_array($data) && !($data instanceof Countable)) {
        return ['rowIndex' => -1, 'cellIndex' => -1];
    }
    $searchString = strtolower(trim($searchString));
    $limit = min(20, count($data)); // search first 20 rows
    for ($rowIndex = 0; $rowIndex < $limit; $rowIndex++) {
        if (!is_array($data[$rowIndex])) continue;
        foreach ($data[$rowIndex] as $colIndex => $cellValue) {
            if (strtolower(trim((string)$cellValue)) === $searchString) {
                return ['rowIndex' => $rowIndex, 'cellIndex' => $colIndex];
            }
        }
    }
    return ['rowIndex' => -1, 'cellIndex' => -1];
}
?>