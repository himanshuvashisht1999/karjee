<?php
error_reporting(E_ALL);
require('../../connection.php');
require('../../loginCell/functions/login_functions.php');
include("../functions/tray_functions.php");
$action = $_GET['action'];
if ($action == "GetTrayCategoryData") {
    $trayCatdata = getTrayCategoryData($krfconn);
    echo json_encode($trayCatdata);
} else if ($action == "GetTrayData") {
    $shortby = $_POST['shortby'] ?? '';
    $shortbyvalue = $_POST['shortbyvalue'] ?? '';
    $trayData = getTrayData($krfconn, $shortby, $shortbyvalue);
    echo json_encode($trayData);
} else if ($action == "saveupdateTrayDetails") {
    $TrayName = $_POST['TrayName'] ?? '';
    $TrayCategoryId = $_POST['TrayCategoryId'] ?? '';
    $CavityCount = $_POST['CavityCount'] ?? '';
    $CavitySize = $_POST['CavitySize'] ?? '';
    $material = $_POST['material'] ?? '';
    $description = $_POST['description'] ?? '';
    $tray_id = $_POST['tray_id'] ?? 0;

    if ($tray_id) {
        $TrayCode = $_POST['TrayCode'];
    } else {
        $TrayCode = 'TRAY-' . date('Ymd') . rand(1000, 9999);
    }

    $response = saveTrayMaster($krfconn, [
        "tray_id" => $tray_id,
        "tray_code" => $TrayCode,
        "tray_name" => $TrayName,
        "category_id" => $TrayCategoryId,
        "cavity_count" => $CavityCount,
        "cavity_size" => $CavitySize,
        "material" => $material,
        "description" => $description,
        "status" => "Active"
    ]);
    echo json_encode($response);
} else if ($action == "updateTrayStatus") {
    $tray_id = $_POST['tray_id'] ?? 0;
    $status = $_POST['status'] ?? 'Active';
    $response = updateTrayStatus($krfconn, $tray_id, $status);
    echo json_encode($response);
} else if ($action == "deleteTrayPermanent") {
    $tray_id = $_POST['tray_id'] ?? 0;
    $response = deleteTrayPermanent($krfconn, $tray_id);
    echo json_encode($response);
}
?>