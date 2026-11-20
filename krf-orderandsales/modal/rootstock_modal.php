<?php
error_reporting(E_ALL);
require('../../connection.php');
require('../../loginCell/functions/login_functions.php');
include("../functions/product_functions.php");
$action = $_GET['action'];
if ($action == "GetSuggestedRootStockSKU") {
  $RootStockName = $_POST['RootStockName'];
  $projectid = $_POST['projectid'];
  $rootstocksku = generateRootStockSKU($krfconn, $projectid, $RootStockName);
  echo json_encode($rootstocksku);
} else if ($action == "GetRootStockMasterData") {
  $projectid = $_POST['projectid'];
  $shortby = $_POST['shortby'] ?? '';
  $shortbyvalue = $_POST['shortbyvalue'] ?? '';
  $rootstockdata = getRootstockMasterData($krfconn, $projectid, $shortby, $shortbyvalue);
  echo json_encode($rootstockdata);
} else if ($action == "saveupdateRootstockdetails") {
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $RootStockId = $_POST['RootStockId'] ?? null; // Hidden field for update
    $RootStockName = trim($_POST['RootStockName'] ?? '');
    $SuggestedRootStocksku = trim($_POST['RootStocksku'] ?? '');
    $ProjectId = $_POST['projectid'] ?? '';
    $loginid = $_POST['loginid'] ?? '';

    // Basic validation
    if (empty($RootStockName) || empty($SuggestedRootStocksku) || empty($ProjectId)) {
      echo json_encode(['status' => 'error', 'message' => 'Required fields are missing.']);
      exit;
    }

    // Prepare new data array
    $newData = [
      "projectid" => $ProjectId,
      "rootstock_sku" => $SuggestedRootStocksku,
      "rootstock_name" => $RootStockName,
      "scientific_name" => "",
      "rootstock_type" => "",
      "growth_vigor" => "",
      "disease_resistance" => "Medium",
      "climate_suitability" => "",
      "soil_preference" => "",
      "water_requirement" => "Medium",
      "propagation_method" => "Seed",
      "availability_status" => "Available",
      "supplier_name" => "",
      "supplier_contact" => "",
      "compatible_scions" => "",
      "planting_distance" => "",
      "expected_yield_influence" => "",
      "lifespan" => "",
      "remarks" => "",
      "created_by" => $loginid
    ];
    $rrstmsg = [];
    try {
      // Start transaction
      $krfconn->begin_transaction();

      if ($RootStockId) {
        $rrstmsg = updateRootstockMasterData($krfconn, $RootStockId, $newData);
      } else {
        $rrstmsg = insertRootstockMasterData($krfconn, $newData);
      }
      // Commit transaction
      $krfconn->commit();
      echo json_encode($rrstmsg);
    } catch (Exception $e) {
      // Rollback on error
      $krfconn->rollback();
      echo json_encode(['status' => 'error', 'message' => 'Transaction failed: ' . $e->getMessage()]);
    }
  } else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
  }
} // End of saveupdateRootstockdetails
// SaveUpdateRootStockOrder and GetRootStockOrderData have been removed as part of the ROOT STOCK ORDERS decomposition.
?>