<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../../connection.php');
require_once(__DIR__ . '/../functions/hrm_FunctionMaster.php');
require_once(__DIR__ . '/../functions/hrm_insertupdatesalary_functions.php');

$action = $_GET['action'] ?? '';

if ($action == "QuickLabourRegistration") {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    @ini_set("display_errors", 0);
    ob_start();

    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;
    $addedby = $_POST['addedby'] ?? null;

    $workingLocation = $_POST['workingLocation'] ?? null;
    $seriesid = $_POST['seriesid'] ?? null;
    $labourName = trim($_POST['labourName'] ?? '');
    $contactNo = $_POST['contactNo'] ?? null;
    $grafting_skill = $_POST['grafting_skill'] ?? 'no';

    $out = null;
    if (empty($workingLocation) || empty($labourName) || empty($contactNo)) {
        ob_end_clean();
        echo json_encode(["status" => "Error", "message" => "Mandatory fields are missing."]);
        exit;
    }

    // Split name into first, middle, last
    $parts = explode(' ', $labourName);
    $firstName = $parts[0];
    $middleName = '';
    $lastName = '';
    if (count($parts) > 1) {
        $lastName = array_pop($parts);
        if (count($parts) > 1) {
            array_shift($parts);
            $middleName = implode(' ', $parts);
        }
    }

    $employeeData = [
        "companyid" => $companyid,
        "subcompanyid" => $subcompanyid,
        "projectid" => $projectid,
        "seriesid" => $seriesid,
        "TitleName" => "Mr.",
        "FirstName" => $firstName,
        "MiddleName" => $middleName,
        "LastName" => $lastName,
        "MobileNo" => $contactNo,
        "MaritalStatus" => "Single",
        "Gender" => "Male",
        "AddressLine1" => "",
        "AddressLine2" => "",
        "graftingskill" => $grafting_skill,
        "locationid" => $workingLocation,
        "addedby" => $addedby,
        "EmployeeID" => null
    ];

    try {
        $hrmconn->begin_transaction();
        
        $res = saveOrUpdateLabourEmployee($hrmconn, $employeeData);
        
        if ($res['success']) {
            $hrmconn->commit();
            $out = [
                "status" => "Success", 
                "message" => "Labour registered successfully with ID: " . $res['employeeID'],
                "employeeID" => $res['employeeID']
            ];
        } else {
            $hrmconn->rollback();
            $out = ["status" => "Error", "message" => $res['message']];
        }
    } catch (Exception $e) {
        $hrmconn->rollback();
        $out = ["status" => "Error", "message" => $e->getMessage()];
    }
    ob_end_clean();
    echo json_encode($out);
    exit;
}
?>
