<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Set error reporting to 0 before any includes to prevent HTML output on warnings
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../connection.php';
require_once __DIR__ . '/../../loginCell/functions/login_functions.php';
include_once __DIR__ . '/../functions/vehicle_functions.php';

$action = $_GET['action'] ?? '';

// Clear any previous output buffers to ensure valid JSON
if (ob_get_length())
    ob_clean();
header('Content-Type: application/json');

if ($action == "saveVehicle") {
    echo json_encode(saveVehicleMaster($krfconn, $_POST));
} elseif ($action == "deleteVehicle") {
    $vehicleid = $_POST['vehicleid'] ?? 0;
    $projectid = $_POST['projectid'] ?? 0;
    echo json_encode(deleteVehicle($krfconn, $vehicleid, $projectid));
} elseif ($action == "getVehicleData") {
    $projectid = $_POST['projectid'] ?? 0;
    echo json_encode(getVehicleData($krfconn, $projectid));
} elseif ($action == "saveFreightChargeMatrix") {
    $projectid = $_POST['projectid'] ?? 0;
    $matrixData = json_decode($_POST['matrix_data'], true);

    if (!$matrixData) {
        echo json_encode(["status" => "error", "message" => "Invalid matrix data"]);
        exit;
    }

    $krfconn->begin_transaction();
    try {
        foreach ($matrixData as $row) {
            $calcType = $row['calculationtype'] ?? 'fixed';
            foreach ($row['charges'] as $vid => $charge) {
                saveFreightCharge($krfconn, [
                    'projectid' => $projectid,
                    'dist_min' => $row['dist_min'],
                    'dist_max' => $row['dist_max'],
                    'vehicleid' => $vid,
                    'charge' => $charge,
                    'calculationtype' => $calcType
                ]);
            }
        }
        $krfconn->commit();
        echo json_encode(["status" => "success", "message" => "Freight charges updated successfully."]);
    } catch (Exception $e) {
        $krfconn->rollback();
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
} elseif ($action == "calculateFreight") {
    $projectid = $_POST['projectid'] ?? 0;
    $km = $_POST['km'] ?? 0;
    $totalQty = $_POST['total_qty'] ?? $_POST['qty'] ?? 0;
    echo json_encode(getFreightSuggestion($krfconn, $projectid, $km, $totalQty));
}
?>