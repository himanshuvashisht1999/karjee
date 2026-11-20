<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
require('../../connection.php');
require('../../loginCell/functions/login_functions.php');
include("../functions/product_functions.php");
include("../functions/labour_functions.php");
include("../functions/orders_functions.php");
include("../functions/tray_functions.php");
include("../../whatsappnotification/whatsappsmsfunctions.php");

$action = $_GET['action'];
if ($action == "GetTrayDataWithQRCode") {
  $companyid = $_POST['companyid'];
  $subcompanyid = $_POST['subcompanyid'];
  $projectid = $_POST['projectid'];
  // echo "qrcode: ".$qr_code = $_POST['qr_code'];
  $barcode = $_POST['qr_code'] ?? '';
  // Example response from DB lookup
  $responsedata = getPolyhousePlantDetails($krfconn, $projectid, "barcode", $barcode);
  $response = [];
  if (!empty($responsedata) && is_array($responsedata)) {
    foreach ($responsedata as $reskey) {

      // ✅ Product details
      $prd = getProductData($krfconn, $projectid, "ProductID", $reskey['productid']);
      $productName = isset($prd[0]['ProductName']) ? $prd[0]['ProductName'] : '';

      // ✅ Tray details
      $trd = getTrayData($krfconn, "tray_id", $reskey['tray_id']);
      $trayname = isset($trd[0]['tray_name']) ? $trd[0]['tray_name'] : '';
      $traycavitycount = isset($trd[0]['cavity_count']) ? $trd[0]['cavity_count'] : '';

      // ✅ Polyhouse details
      $poldata = getPolyhouseData($krfconn, $projectid, "polyhouseid", $reskey['polyhouseid']);
      $polyhousename = isset($poldata[0]['polyhousename']) ? $poldata[0]['polyhousename'] : '';

      // ✅ Growth stage fallback
      if ($reskey['growth_stage'] == "Healthy") {
        $growthstage = '<i class="fas fa-leaf text-success"></i> <span class="badge badge-success">' . $reskey['growth_stage'] . '</span>';
      } else if ($reskey['growth_stage'] == "Weak") {
        $growthstage = '<i class="fas fa-exclamation-circle text-warning"></i> <span class="badge badge-warning">' . $reskey['growth_stage'] . '</span>';
      } else if ($reskey['growth_stage'] == "Diseased") {
        $growthstage = '<i class="fas fa-virus text-danger"></i> <span class="badge badge-danger">' . $reskey['growth_stage'] . '</span>';
      } else if ($reskey['growth_stage'] == "Dead") {
        $growthstage = '<i class="fas fa-skull-crossbones text-dark"></i> <span class="badge badge-dark">' . $reskey['growth_stage'] . '</span>';
      } else if ($reskey['growth_stage'] == "Infected") {
        $growthstage = '<i class="fas fa-bug text-danger"></i> <span class="badge badge-danger">' . $reskey['growth_stage'] . '</span>';
      } else if ($reskey['growth_stage'] == "Under Observation") {
        $growthstage = '<i class="fas fa-eye text-info"></i> <span class="badge badge-info">' . $reskey['growth_stage'] . '</span>';
      } else if ($reskey['growth_stage'] == "Use For Correction") {
        $growthstage = '<i class="fas fa-eye text-warning"></i> <span class="badge badge-warning">' . $reskey['growth_stage'] . '</span>';
      } else if ($reskey['growth_stage'] == "ReadyToSale") {
        $growthstage = '<span class="alert alert-success"><i class="fas fa-check-circle text-success"></i> This tray is now finalized and ready for sale.</span>';
      } else {
        $growthstage = '<i class="fa fa-clock-o"></i> <span class="badge badge-warning">Inspection Pending</span>';
      }
      $qchistorybtn = '';
      $qctrackingdata = getPolyhousePlantQCStatusData($krfconn, $projectid, 'polyhouseplantid', $reskey['polyhouseplantid']);
      if (count($qctrackingdata) > 0) {
        $qchistorybtn = '<span data-polyhouseplantid=' . $reskey['polyhouseplantid'] . ' data-polyhouseplantid=' . $reskey['polyhouseplantid'] . ' class="badge badge-success openuqchistory" style="cursor:pointer;">View QC History</span>';
      }

      $response[] = [
        'stockorder_no' => $reskey['stockorder_no'],
        'polyhouseplantid' => $reskey['polyhouseplantid'],
        'productid' => $reskey['productid'],
        'productname' => $productName,
        'trayid' => $reskey['tray_id'],
        'trayname' => $trayname,
        'traycavitycount' => $traycavitycount,
        'polyhouseid' => $reskey['polyhouseid'],
        'polyhousename' => $polyhousename,
        'graftingid' => $reskey['graftingid'],
        'graftingdate' => $reskey['created_at'],
        'growth_stage' => $reskey['growth_stage'],
        'graftingdatedsp' => !empty($reskey['created_at']) ? date('d-M-Y', strtotime($reskey['created_at'])) : '',
        'growthstage' => $growthstage,
        'last_inspection_date' => !empty($reskey['last_inspection_date']) ? date('d-M-Y', strtotime($reskey['last_inspection_date'])) : '',
        'last_inspection_remark' => $reskey['last_inspection_remark'] != "" ? $reskey['last_inspection_remark'] : 'N/A',
        'qchistorybtn' => $qchistorybtn,
        'qctrackingdata' => $qctrackingdata
      ];
    }
    echo json_encode([
      'status' => 'success',
      'count' => count($response),
      'data' => $response
    ]);

  } else {
    echo json_encode([
      'status' => 'error',
      'message' => 'No plant data found for the given QR code.'. $barcode
    ]);
  }
} else if ($action == "UpdateGraftingQCStatus__") {
  $response = ['status' => 'error', 'message' => 'Unexpected error occurred'];
  try {
    $companyid = $_POST['companyid'] ?? '';
    $subcompanyid = $_POST['subcompanyid'] ?? '';
    $projectid = $_POST['projectid'] ?? '';
    $loginid = $_POST['loginid'] ?? '';
    $graftingqcstatus = $_POST['graftingqcstatus'] ?? '';
    $qcfinalstatus = $_POST['qcfinalstatus'] ?? '';
    $graftingqcremark = $_POST['graftingqcremark'] ?? '';

    $stockorder_no = $_POST['stockorder_no'] ?? '';
    $polyhouseplantid = $_POST['polyhouseplantid'] ?? '';

    $correctionpolyhouseplantid = $_POST['correctionpolyhouseplantid'] ?? '';
    $replacementcount = $_POST['replacementcount'] ?? 0;
    $updateto = $_POST['updateto'];

    if ($updateto == "all" && $stockorder_no != "") {
      $realtraydata = getPolyhousePlantDetails($krfconn, $projectid, 'allhealthyplantswithstockorderno', $stockorder_no);
    } else {
      $realtraydata = getPolyhousePlantDetails($krfconn, $projectid, 'polyhouseplantid', $polyhouseplantid);
    }
    if (count($realtraydata) > 0) {
      foreach ($realtraydata as $rltrkey) {
        $trayid = $rltrkey['tray_id'];
        $barcode = $rltrkey['barcode'];
        $polyhouseid = $rltrkey['polyhouseid'];
        $psono = $rltrkey['psono'] ?? '';
        $stockorder_no = $rltrkey['stockorder_no'] ?? '';
        $productid = $rltrkey['productid'] ?? '';
        $productname = $rltrkey['productname'] ?? '';
        $locationid = $rltrkey['locationid'] ?? '';
        $productionClientid = "";
        if ($stockorder_no) {
          $ppdtsd = getFullProductionStockOrdersDetails($krfconn, $projectid, 'stockorder_no', $stockorder_no);
          $prdClientid = $ppdtsd[0]['clientid'];
          $expected_delivery_date = $ppdtsd[0]['expected_delivery_date'];
        }
        $locationname = "";
        if ($locationid) {
          $lgdt = getProductionLocationMaster($hrmconn, $companyid, $subcompanyid, $projectid, "LocationId", $locationid);
          $locationname = $lgdt[0]['LocationName'];
        }

        $trayData = getTrayData($krfconn, 'tray_id', $trayid);
        $traycavitycount = $trayData[0]['cavity_count'] ?? '';

        if ($qcfinalstatus == "ReadyToSale" && $graftingqcstatus == "Healthy") {
          $graftingqcstatus = $qcfinalstatus;

          $rtsdata = [
            "psono" => $psono,
            "stockorder_no" => $stockorder_no,
            "productionclientid" => $prdClientid,
            "productid" => $productid,
            "productname" => $productname,
            "trayid" => $trayid,
            "qtypertray" => $traycavitycount,
            "locationid" => $locationid,
            "locationname" => $locationname,
            "polyhouseplantid" => $polyhouseplantid,
            "saleorderno" => "",
            "deliverydate" => $expected_delivery_date,
            "remarks" => $graftingqcremark,
            "status" => "ReadyToSale",
            "created_by" => $loginid
          ];
        }

        $qctrackingdata = [];
        if ($correctionpolyhouseplantid && $replacementcount > 0) {
          $reptraydata = getPolyhousePlantDetails($krfconn, $projectid, 'polyhouseplantid', $correctionpolyhouseplantid);
          $reptrayid = $reptraydata[0]['tray_id'];
          $repbarcode = $reptraydata[0]['barcode'];

          $qctrackingdata = ['projectid' => $projectid, 'polyhouseid' => $polyhouseid, 'polyhouseplantid' => $polyhouseplantid, 'trayid' => $trayid, 'barcode' => $barcode, 'qc_status' => $graftingqcstatus, 'replaced_any_plant' => 'Yes', 'replacefrom_polyhouseplantid' => $correctionpolyhouseplantid, 'replacefrom_trayid' => $reptrayid, 'replacefrom_tray_barcode' => $repbarcode, 'replaced_plantcount' => $replacementcount, 'qcremarks' => $graftingqcremark, 'updated_by' => $loginid, 'statustrackingid' => ''];
        } else {
          $qctrackingdata = ['projectid' => $projectid, 'polyhouseid' => $polyhouseid, 'polyhouseplantid' => $polyhouseplantid, 'trayid' => $trayid, 'barcode' => $barcode, 'qc_status' => $graftingqcstatus, 'replaced_any_plant' => 'No', 'replacefrom_polyhouseplantid' => '', 'replacefrom_trayid' => '', 'replacefrom_tray_barcode' => '', 'replaced_plantcount' => '0', 'qcremarks' => $graftingqcremark, 'updated_by' => $loginid, 'statustrackingid' => ''];
        }

        if (empty($polyhouseplantid) || empty($graftingqcstatus)) {
          throw new Exception("Missing required data.");
        }
        $sql = "UPDATE krf_polyhouse_plant_details 
                  SET growth_stage = ?, 
                      last_inspection_date = NOW(), 
                      last_inspection_remark = ?, 
                      inspected_by = ? 
                  WHERE polyhouseplantid = ?";

        $stmt = $krfconn->prepare($sql);
        if (!$stmt) {
          throw new Exception("Prepare failed: " . $krfconn->error);
        }
        $stmt->bind_param("ssss", $graftingqcstatus, $graftingqcremark, $loginid, $polyhouseplantid);

        if ($stmt->execute()) {
          if ($stmt->affected_rows > 0) {
            // print_r($qctrackingdata);
            savePolyhousePlantQCStatusTracking($krfconn, $qctrackingdata);
            if ($qcfinalstatus == "ReadyToSale") {
              $sssq = saveReadyToSaleStock($krfconn, $projectid, $rtsdata);
            }
            $response = ['status' => 'success', 'message' => 'QC status updated successfully.'];
          } else {
            $response = ['status' => 'warning', 'message' => 'No record updated (invalid ID or no changes).'];
          }
        } else {
          throw new Exception("Execution failed: " . $stmt->error);
        }
      }
    }
    $stmt->close();
  } catch (Exception $e) {
    $response = ['status' => 'error', 'message' => $e->getMessage()];
  }
  echo json_encode($response);
} else if ($action == "UpdateGraftingQCStatus") {
  $response = [
    'status' => 'error',
    'message' => 'Unexpected error occurred.'
  ];
  try {

    /* ===============================
       1️⃣ INPUTS
    =============================== */
    $companyid = $_POST['companyid'] ?? '';
    $subcompanyid = $_POST['subcompanyid'] ?? '';
    $projectid = $_POST['projectid'] ?? '';
    $loginid = $_POST['loginid'] ?? '';

    $graftingqcstatus = $_POST['graftingqcstatus'] ?? '';
    $qcfinalstatus = $_POST['qcfinalstatus'] ?? '';
    $graftingqcremark = $_POST['graftingqcremark'] ?? '';

    $stockorder_no = $_POST['stockorder_no'] ?? '';
    $polyhouseplantid = $_POST['polyhouseplantid'] ?? '';


    $correctionpolyhouseplantid = $_POST['correctionpolyhouseplantid'] ?? '';
    $replacementcount = (int) ($_POST['replacementcount'] ?? 0);
    $updateto = $_POST['updateto'] ?? '';

    if (empty($graftingqcstatus)) {
      throw new Exception("QC status is required.");
    }

    /* ===============================
       2️⃣ FETCH TARGET TRAYS
    =============================== */
    if ($updateto === "all" && $stockorder_no !== "") {
      $realtraydata = getPolyhousePlantDetails($krfconn, $projectid, 'allhealthyplantswithstockorderno', $stockorder_no);
    } else {
      if (empty($polyhouseplantid)) {
        throw new Exception("Tray record not found.");
      }
      $realtraydata = getPolyhousePlantDetails($krfconn, $projectid, 'polyhouseplantid', $polyhouseplantid);
    }

    if (empty($realtraydata)) {
      throw new Exception("No tray records found for update.");
    }
    // echo "hello: ".count($realtraydata);

    /* ===============================
       3️⃣ PREPARE UPDATE QUERY
    =============================== */
    $sql = "
          UPDATE krf_polyhouse_plant_details 
          SET 
              growth_stage = ?,
              status = ?, 
              last_inspection_date = NOW(), 
              last_inspection_remark = ?, 
              inspected_by = ? 
          WHERE polyhouseplantid = ?
      ";

    $stmt = $krfconn->prepare($sql);
    if (!$stmt) {
      throw new Exception("Database prepare failed.");
    }

    $updatedCount = 0;

    /* ===============================
       4️⃣ PROCESS EACH TRAY
    =============================== */
    foreach ($realtraydata as $row) {
      $plantId = $row['polyhouseplantid'];
      $trayid = $row['tray_id'];
      $barcode = $row['barcode'];
      $polyhouseid = $row['polyhouseid'];
      $psono = $row['psono'] ?? '';
      $stockNo = $row['stockorder_no'] ?? '';
      $productid = $row['productid'] ?? '';
      $productname = $row['productname'] ?? '';
      $locationid = $row['locationid'] ?? '';

      /* Location */
      $locationname = '';
      if ($locationid) {
        $lgdt = getProductionLocationMaster($hrmconn, $companyid, $subcompanyid, $projectid, "LocationId", $locationid);
        $locationname = $lgdt[0]['LocationName'] ?? '';
      }

      /* Tray cavity count */
      $trayData = getTrayData($krfconn, 'tray_id', $trayid);
      $traycavitycount = $trayData[0]['cavity_count'] ?? 0;

      /* Ready To Sale logic */
      $finalQCStatus = $graftingqcstatus;
      $rtsdata = [];
      $status = "Polyhouse";
      if ($qcfinalstatus === "ReadyToSale" && $graftingqcstatus === "Healthy") {

        $finalQCStatus = "ReadyToSale";
        $status = "Readytosale";

        $ppdtsd = getFullProductionStockOrdersDetails($krfconn, $projectid, 'stockorder_no', $stockNo);
        $prdClientid = $ppdtsd[0]['clientid'] ?? '';
        $expected_delivery_date = $ppdtsd[0]['expected_delivery_date'] ?? '';

        $rtsdata = [
          "psono" => $psono,
          "stockorder_no" => $stockNo,
          "productionclientid" => $prdClientid,
          "productid" => $productid,
          "productname" => $productname,
          "trayid" => $trayid,
          "qtypertray" => $traycavitycount,
          "locationid" => $locationid,
          "locationname" => $locationname,
          "polyhouseplantid" => $plantId,
          "saleorderno" => "",
          "deliverydate" => $expected_delivery_date,
          "remarks" => $graftingqcremark,
          "status" => "ReadyToSale",
          "created_by" => $loginid
        ];
      }

      /* QC Tracking */
      if ($correctionpolyhouseplantid && $replacementcount > 0) {

        $reptraydata = getPolyhousePlantDetails(
          $krfconn,
          $projectid,
          'polyhouseplantid',
          $correctionpolyhouseplantid
        );

        $qctrackingdata = [
          'projectid' => $projectid,
          'polyhouseid' => $polyhouseid,
          'polyhouseplantid' => $plantId,
          'trayid' => $trayid,
          'barcode' => $barcode,
          'qc_status' => $finalQCStatus,
          'replaced_any_plant' => 'Yes',
          'replacefrom_polyhouseplantid' => $correctionpolyhouseplantid,
          'replacefrom_trayid' => $reptraydata[0]['tray_id'] ?? '',
          'replacefrom_tray_barcode' => $reptraydata[0]['barcode'] ?? '',
          'replaced_plantcount' => $replacementcount,
          'qcremarks' => $graftingqcremark,
          'updated_by' => $loginid,
          'statustrackingid' => ''
        ];

      } else {

        $qctrackingdata = [
          'projectid' => $projectid,
          'polyhouseid' => $polyhouseid,
          'polyhouseplantid' => $plantId,
          'trayid' => $trayid,
          'barcode' => $barcode,
          'qc_status' => $finalQCStatus,
          'replaced_any_plant' => 'No',
          'replacefrom_polyhouseplantid' => '',
          'replacefrom_trayid' => '',
          'replacefrom_tray_barcode' => '',
          'replaced_plantcount' => 0,
          'qcremarks' => $graftingqcremark,
          'updated_by' => $loginid,
          'statustrackingid' => ''
        ];
      }

      /* Update main table */
      $stmt->bind_param("sssss", $finalQCStatus, $status, $graftingqcremark, $loginid, $plantId);
      if ($stmt->execute() && $stmt->affected_rows > 0) {
        savePolyhousePlantQCStatusTracking($krfconn, $qctrackingdata);
        if (!empty($rtsdata)) {
          saveReadyToSaleStock($krfconn, $projectid, $rtsdata);
        }
        $updatedCount++;
      }
    }

    $stmt->close();

    $response = [
      'status' => $updatedCount > 0 ? 'success' : 'warning',
      'message' => $updatedCount > 0
        ? "QC status updated successfully for {$updatedCount} tray(s)."
        : "No records were updated."
    ];

  } catch (Exception $e) {

    $response = [
      'status' => 'error',
      'message' => $e->getMessage()
    ];
  }

  echo json_encode($response);
  exit;

} else if ($action == "GetGraftedQCDoneToday") {
  $companyid = $_POST['companyid'];
  $subcompanyid = $_POST['subcompanyid'];
  $projectid = $_POST['projectid'];
  $loginid = $_POST['loginid'];
  $qcdate = date('Y-m-d');
  $responsedata = getPolyhousePlantDetails($krfconn, $projectid, "last_inspection_date", $qcdate);
  $response = [];
  if (!empty($responsedata) && is_array($responsedata)) {
    foreach ($responsedata as $reskey) {

      // ✅ Product details
      $prd = getProductData($krfconn, $projectid, "ProductID", $reskey['productid']);
      $productName = isset($prd[0]['ProductName']) ? $prd[0]['ProductName'] : '';

      // ✅ Tray details
      $trd = getTrayData($krfconn, "tray_id", $reskey['tray_id']);
      $trayname = isset($trd[0]['tray_name']) ? $trd[0]['tray_name'] : '';
      $traycavitycount = isset($trd[0]['cavity_count']) ? $trd[0]['cavity_count'] : '';

      // ✅ Polyhouse details
      $poldata = getPolyhouseData($krfconn, $projectid, "polyhouseid", $reskey['polyhouseid']);
      $polyhousename = isset($poldata[0]['polyhousename']) ? $poldata[0]['polyhousename'] : '';

      // ✅ Growth stage fallback
      if ($reskey['growth_stage'] == "Healthy") {
        $growthstage = '<i class="fas fa-leaf text-success"></i> ' . $reskey['growth_stage'] . '';
      } else if ($reskey['growth_stage'] == "Weak") {
        $growthstage = '<i class="fas fa-exclamation-circle text-warning"></i> ' . $reskey['growth_stage'] . '';
      } else if ($reskey['growth_stage'] == "Diseased") {
        $growthstage = '<i class="fas fa-virus text-danger"></i> ' . $reskey['growth_stage'] . '';
      } else if ($reskey['growth_stage'] == "Dead") {
        $growthstage = '<i class="fas fa-skull-crossbones text-dark"></i> ' . $reskey['growth_stage'] . '';
      } else if ($reskey['growth_stage'] == "Infested") {
        $growthstage = '<i class="fas fa-bug text-danger"></i> ' . $reskey['growth_stage'] . '';
      } else if ($reskey['growth_stage'] == "Under Observation") {
        $growthstage = '<i class="fas fa-eye text-info"></i> ' . $reskey['growth_stage'] . '';
      } else if ($reskey['growth_stage'] == "Use For Correction") {
        $growthstage = '<i class="fas fa-eye text-warning"></i> ' . $reskey['growth_stage'] . '';
      } else if ($reskey['growth_stage'] == "ReadyToSale") {
        $growthstage = '<i class="fas fa-check-circle text-success"></i> ' . $reskey['growth_stage'] . '';
      } else {
        $growthstage = '<i class="fa fa-clock-o"></i> Inspection Pending';
      }



      $response[] = [
        'polyhouseplantid' => $reskey['polyhouseplantid'],
        'productid' => $reskey['productid'],
        'productname' => $productName,
        'trayid' => $reskey['tray_id'],
        'trayname' => $trayname,
        'traycavitycount' => $traycavitycount,
        'polyhouseid' => $reskey['polyhouseid'],
        'polyhousename' => $polyhousename,
        'graftingid' => $reskey['graftingid'],
        'graftingdate' => $reskey['created_at'],
        'growth_stage' => $reskey['growth_stage'],
        'graftingdatedsp' => !empty($reskey['created_at']) ? date('d-M-Y', strtotime($reskey['created_at'])) : '',
        'growthstage' => $growthstage,
        'last_inspection_date' => !empty($reskey['last_inspection_date']) ? date('d-M-Y', strtotime($reskey['last_inspection_date'])) : '',
        'last_inspection_remark' => $reskey['last_inspection_remark'] != "" ? $reskey['last_inspection_remark'] : 'N/A'
      ];
    }
    echo json_encode($response);
  }
} else if ($action == "GetGraftedQCHistory") {
  $companyid = $_POST['companyid'];
  $subcompanyid = $_POST['subcompanyid'];
  $projectid = $_POST['projectid'];
  $polyhouseplantid = $_POST['polyhouseplantid'];
  // ✅ QC Main details
  $ppd = getPolyhousePlantDetails($krfconn, $projectid, "polyhouseplantid", $polyhouseplantid);
  $trayid = $ppd[0]['tray_id'];
  $productid = $ppd[0]['productid'];
  $polyhouseid = $ppd[0]['polyhouseid'];
  $graftingid = $ppd[0]['graftingid'];
  $graftingdate = !empty($ppd[0]['created_at']) ? date('d-M-Y', strtotime($ppd[0]['created_at'])) : '';
  // ✅ Product details
  $prd = getProductData($krfconn, $projectid, "ProductID", $ppd[0]['productid']);
  $productName = isset($prd[0]['ProductName']) ? $prd[0]['ProductName'] : '';

  // ✅ Tray details
  $trd = getTrayData($krfconn, "tray_id", $ppd[0]['tray_id']);
  $trayname = isset($trd[0]['tray_name']) ? $trd[0]['tray_name'] : '';
  $traycavitycount = isset($trd[0]['cavity_count']) ? $trd[0]['cavity_count'] : '';

  // ✅ Polyhouse details
  $poldata = getPolyhouseData($krfconn, $projectid, "polyhouseid", $ppd[0]['polyhouseid']);
  $polyhousename = isset($poldata[0]['polyhousename']) ? $poldata[0]['polyhousename'] : '';


  $responsedata = getPolyhousePlantQCStatusData($krfconn, $projectid, "polyhouseplantid", $polyhouseplantid);
  $response = [];
  $finalqchistryres = [];
  if (!empty($responsedata) && is_array($responsedata)) {
    foreach ($responsedata as $reskey) {
      $lginfo = loginuserinfo($authconn, '', 'loginid', $reskey['updated_by']);
      $updatedby = $lginfo[0]['loginname'];

      // ✅ Growth stage fallback
      if ($reskey['qc_status'] == "Healthy") {
        $growthstage = '<i class="fas fa-leaf text-success"></i> ' . $reskey['qc_status'] . '';
      } else if ($reskey['qc_status'] == "Weak") {
        $growthstage = '<i class="fas fa-exclamation-circle text-warning"></i> ' . $reskey['qc_status'] . '';
      } else if ($reskey['qc_status'] == "Diseased") {
        $growthstage = '<i class="fas fa-virus text-danger"></i> ' . $reskey['qc_status'] . '';
      } else if ($reskey['qc_status'] == "Dead") {
        $growthstage = '<i class="fas fa-skull-crossbones text-dark"></i> ' . $reskey['qc_status'] . '';
      } else if ($reskey['qc_status'] == "Infested") {
        $growthstage = '<i class="fas fa-bug text-danger"></i> ' . $reskey['qc_status'] . '';
      } else if ($reskey['qc_status'] == "Under Observation") {
        $growthstage = '<i class="fas fa-eye text-info"></i> ' . $reskey['qc_status'] . '';
      } else if ($reskey['qc_status'] == "Use For Correction") {
        $growthstage = '<i class="fas fa-eye text-warning"></i> ' . $reskey['qc_status'] . '';
      } else if ($reskey['qc_status'] == "ReadyToSale") {
        $growthstage = '<i class="fas fa-check-circle text-success"></i> ' . $reskey['qc_status'] . '';
      } else {
        $growthstage = '<i class="fa fa-clock-o"></i> Inspection Pending';
      }

      $response[] = [
        'qcdoneby' => $updatedby,
        'replaceanyplant' => $reskey['replaced_any_plant'],
        'replaceanyplantcount' => $reskey['replaced_plantcount'],
        'replacefromtrayqcrode' => $reskey['replacefrom_tray_barcode'],
        'qcstatus' => $growthstage,
        'qcdate' => !empty($reskey['updated_on']) ? date('d-M-Y', strtotime($reskey['updated_on'])) : '',
        'qcremark' => $reskey['remarks'] != "" ? $reskey['remarks'] : 'N/A'
      ];
    }
    $finalqchistryres = ['polyhouseplantid' => $polyhouseplantid, 'trayid' => $trayid, 'trayname' => $trayname, 'polyhouseid' => $polyhouseid, 'polyhousename' => $polyhousename, 'productid' => $productid, 'productname' => $productName, 'graftingid' => $graftingid, 'graftingdate' => $graftingdate, 'qcdata' => $response];
    echo json_encode($finalqchistryres);
  }
} else if ($action == "GetQCCorrectionTray") {
  $companyid = $_POST['companyid'];
  $subcompanyid = $_POST['subcompanyid'];
  $projectid = $_POST['projectid'];
  $responsedata = getPolyhousePlantDetails($krfconn, $projectid, "growth_stage", 'Use For Correction');
  $response = [];
  if (!empty($responsedata) && is_array($responsedata)) {
    foreach ($responsedata as $reskey) {

      // ✅ Product details
      $prd = getProductData($krfconn, $projectid, "ProductID", $reskey['productid']);
      $productName = isset($prd[0]['ProductName']) ? $prd[0]['ProductName'] : '';

      // ✅ Tray details
      $trd = getTrayData($krfconn, "tray_id", $reskey['tray_id']);
      $trayname = isset($trd[0]['tray_name']) ? $trd[0]['tray_name'] : '';
      $traycavitycount = isset($trd[0]['cavity_count']) ? $trd[0]['cavity_count'] : '';

      // ✅ Polyhouse details
      $poldata = getPolyhouseData($krfconn, $projectid, "polyhouseid", $reskey['polyhouseid']);
      $polyhousename = isset($poldata[0]['polyhousename']) ? $poldata[0]['polyhousename'] : '';

      // ✅ Growth stage fallback
      if ($reskey['growth_stage'] == "Healthy") {
        $growthstage = '<i class="fas fa-leaf text-success"></i> ' . $reskey['growth_stage'] . '';
      } else if ($reskey['growth_stage'] == "Weak") {
        $growthstage = '<i class="fas fa-exclamation-circle text-warning"></i> ' . $reskey['growth_stage'] . '';
      } else if ($reskey['growth_stage'] == "Diseased") {
        $growthstage = '<i class="fas fa-virus text-danger"></i> ' . $reskey['growth_stage'] . '';
      } else if ($reskey['growth_stage'] == "Dead") {
        $growthstage = '<i class="fas fa-skull-crossbones text-dark"></i> ' . $reskey['growth_stage'] . '';
      } else if ($reskey['growth_stage'] == "Infested") {
        $growthstage = '<i class="fas fa-bug text-danger"></i> ' . $reskey['growth_stage'] . '';
      } else if ($reskey['growth_stage'] == "Under Observation") {
        $growthstage = '<i class="fas fa-eye text-info"></i> ' . $reskey['growth_stage'] . '';
      } else if ($reskey['growth_stage'] == "Use For Correction") {
        $growthstage = '<i class="fas fa-eye text-warning"></i> ' . $reskey['growth_stage'] . '';
      } else if ($reskey['growth_stage'] == "ReadyToSale") {
        $growthstage = '<i class="fas fa-check-circle text-success"></i> ' . $reskey['growth_stage'] . '';
      } else {
        $growthstage = '<i class="fa fa-clock-o"></i> Inspection Pending';
      }

      $response[] = [
        'polyhouseplantid' => $reskey['polyhouseplantid'],
        'productid' => $reskey['productid'],
        'productname' => $productName,
        'trayid' => $reskey['tray_id'],
        'trayname' => $trayname,
        'traycavitycount' => $traycavitycount,
        'polyhouseid' => $reskey['polyhouseid'],
        'polyhousename' => $polyhousename,
        'graftingid' => $reskey['graftingid'],
        'graftingdate' => $reskey['created_at'],
        'growth_stage' => $reskey['growth_stage'],
        'graftingdatedsp' => !empty($reskey['created_at']) ? date('d-M-Y', strtotime($reskey['created_at'])) : '',
        'growthstage' => $growthstage,
        'last_inspection_date' => !empty($reskey['last_inspection_date']) ? date('d-M-Y', strtotime($reskey['last_inspection_date'])) : '',
        'last_inspection_remark' => $reskey['last_inspection_remark'] != "" ? $reskey['last_inspection_remark'] : 'N/A'
      ];
    }
    echo json_encode($response);
  }
} else if ($action == "fetchGerminationTrayData") {
  $companyid = $_POST['companyid'] ?? 1;
  $subcompanyid = $_POST['subcompanyid'] ?? 1;
  $projectid = $_POST['projectid'] ?? 17;
  if ($projectid <= 0) $projectid = 17;
  
  $barcode = trim($_POST['qr_code'] ?? '');

  // JSON payload extraction
  $decodedJson = @json_decode($barcode, true);
  if (is_array($decodedJson)) {
      $barcode = $decodedJson['barcode'] ?? $decodedJson['bedqrcode'] ?? $decodedJson['stockorder_no'] ?? $decodedJson['psono'] ?? $decodedJson['code'] ?? $decodedJson['qr_text'] ?? $barcode;
  }

  // URL extraction
  if (strpos($barcode, 'http') === 0 || strpos($barcode, '://') !== false) {
      $parsedUrl = parse_url($barcode);
      if (!empty($parsedUrl['query'])) {
          parse_str($parsedUrl['query'], $urlParams);
          $barcode = $urlParams['barcode'] ?? $urlParams['stockorder_no'] ?? $urlParams['code'] ?? $urlParams['qr_code'] ?? $barcode;
      }
  }

  // Strip control and non-printable characters
  $barcode = preg_replace('/[\x00-\x1F\x7F\xC2\xA0]/u', '', $barcode);
  $barcode = trim($barcode);

  $responsedata = getPolyhousePlantDetails($krfconn, $projectid, "barcode", $barcode);
  if (empty($responsedata)) {
      $responsedata = getPolyhousePlantDetails($krfconn, null, "barcode", $barcode);
  }
  if (empty($responsedata) && is_numeric($barcode)) {
      $responsedata = getPolyhousePlantDetails($krfconn, null, "polyhouseplantid", (int)$barcode);
  }

  $response = [];
  if (!empty($responsedata) && is_array($responsedata)) {
    foreach ($responsedata as $reskey) {
      if ($reskey['health_status'] == "Germination Failure") {
        echo json_encode(['status' => 'error', 'message' => 'This tray is already marked as Germination Failure. Please choose another tray for germination failure observation.']);
        exit;
      }else if ($reskey['bed_verify'] == "" || $reskey['bed_verify'] == null) {
        echo json_encode(['status' => 'error', 'message' => 'Physical verification is pending, So first do the Physical verification']);
        exit;
      }
      // bed_verify
      $stockorder_no = $reskey['stockorder_no'];
      $polyhouseid = $reskey['polyhouseid'];
      $bedrackname = $reskey['bedrackname'];
      $bed_no = $reskey['bed_no'] ?? '';
      $sowinglotno_internal = $reskey['sowinglotno_internal'];
      $sowingdate_internal = $reskey['sowingdate_internal'] ?? '';
      $productid = $reskey['productid'];
      $roottype = $reskey['roottype'];

      // ✅ Order Info from Production Stock Order
      $prodOrder = getFullProductionStockOrdersDetails($krfconn, $projectid, 'stockorder_no', $stockorder_no)[0] ?? null;
      $planQty = (float) ($prodOrder['quantity'] ?? 0);

      // ✅ Statistics for Order (Filtered by sowingdate_internal)
      $stmtOrdStats = $krfconn->prepare("
          SELECT COUNT(*) as tray_cnt,
                 SUM(CASE WHEN p.health_status = 'Germination Failure' THEN 1 ELSE 0 END) as total_fail_trays,
                 SUM(CASE WHEN p.health_status = 'Germination Failure' THEN IFNULL(p.failurecount, 0) ELSE 0 END) as total_fail_count,
                 SUM(CASE WHEN p.health_status = 'Germination Failure' THEN CAST(IFNULL(t.cavity_count, p.qtypertray) AS UNSIGNED) ELSE 0 END) as total_fail_trays_qty,
                 MAX(p.planted_date) as planted_date,
                 MAX(p.sowing_date) as sowing_date,
                 MAX(p.sowingdate_internal) as sowingdate_internal,
                 MAX(p.roottype) as roottype,
                 MAX(p.productid) as productid
          FROM krf_polyhouse_plant_details p
          LEFT JOIN krf_tray_master t ON p.tray_id = t.tray_id
          WHERE p.bed_verify<>'' and p.stockorder_no = ? AND p.projectid = ? AND p.sowinglotno_internal = ? AND p.roottype = ? AND p.sowingdate_internal = ?
      ");
      $stmtOrdStats->bind_param("sisss", $stockorder_no, $projectid, $sowinglotno_internal, $roottype, $sowingdate_internal);
      $stmtOrdStats->execute();
      $ordStatsObj = $stmtOrdStats->get_result()->fetch_assoc();
      $ordTotal = (int) ($ordStatsObj['tray_cnt'] ?? 0);
      $totalOrderFailTrays = (int) ($ordStatsObj['total_fail_trays'] ?? 0);
      $totalOrderFailCount = (int) ($ordStatsObj['total_fail_count'] ?? 0);
      $totalOrderFailTraysQty = (int) ($ordStatsObj['total_fail_trays_qty'] ?? 0);

      $planted_date = $ordStatsObj['planted_date'] ?? '';
      $sowing_date = $ordStatsObj['sowing_date'] ?? '';
      $sowingdate_internal = !empty($sowingdate_internal) ? $sowingdate_internal : ($ordStatsObj['sowingdate_internal'] ?? '');
      $productid = $ordStatsObj['productid'] ?? '';
      $seeddetails = getSeedDetailspassingpassing($krfconn, $projectid, $productid, $roottype);
      $seedname = $seeddetails['SeedName'] ?? '';

      $stmtOrdStats->close();

      // ✅ Total Sowing Qty (Sum of cavities in all trays of this order & sowingdate_internal)
      $stmtSown = $krfconn->prepare("
          SELECT SUM(CAST(IFNULL(t.cavity_count, p.qtypertray) AS UNSIGNED)) as total_sown
          FROM krf_polyhouse_plant_details p
          LEFT JOIN krf_tray_master t ON p.tray_id = t.tray_id
          WHERE p.bed_verify<>'' and p.stockorder_no = ? AND p.projectid = ? and p.sowinglotno_internal = ? AND p.roottype = ? AND p.sowingdate_internal = ?
      ");
      $stmtSown->bind_param("sisss", $stockorder_no, $projectid, $sowinglotno_internal, $roottype, $sowingdate_internal);
      $stmtSown->execute();
      $totalSownQty = (int) ($stmtSown->get_result()->fetch_assoc()['total_sown'] ?? 0);
      $stmtSown->close();

      // Germination Failure %: sum of failed plants of germination trays / sum of qtypertray of germination failure trays * 100
      $orderFailPercent = ($totalOrderFailTraysQty > 0) ? round(($totalOrderFailCount / $totalOrderFailTraysQty) * 100, 2) : 0;
      $orderTrayFailPercent = ($ordTotal > 0) ? round(($totalOrderFailTrays / $ordTotal) * 100, 2) : 0;

      // ✅ Statistics for Bed (Filtered by LOT & sowingdate_internal)
      $bedStatsList = [];
      $stmtAllBeds = $krfconn->prepare("
          SELECT p.polyhouseid, 
                 COALESCE(NULLIF(TRIM(p.bed_no),''), TRIM(p.bedrackname)) as bed_no,
                 COUNT(*) as bed_total, 
                 SUM(CASE WHEN NULLIF(TRIM(p.bed_verify),'') IS NOT NULL AND p.bed_verify != '0000-00-00 00:00:00' THEN 1 ELSE 0 END) as verified_trays,
                 SUM(CASE WHEN NULLIF(TRIM(p.bed_verify),'') IS NULL OR p.bed_verify = '0000-00-00 00:00:00' THEN 1 ELSE 0 END) as unverified_trays,
                 SUM(CASE WHEN p.health_status = 'Germination Failure' THEN 1 ELSE 0 END) as bed_fail,
                 SUM(CAST(IFNULL(t.cavity_count, p.qtypertray) AS UNSIGNED)) as bed_total_plants,
                 SUM(CASE WHEN p.health_status = 'Germination Failure' THEN IFNULL(p.failurecount, 0) ELSE 0 END) as bed_fail_plants,
                 SUM(CASE WHEN p.health_status = 'Germination Failure' THEN CAST(IFNULL(t.cavity_count, p.qtypertray) AS UNSIGNED) ELSE 0 END) as bed_fail_trays_qty
          FROM krf_polyhouse_plant_details p
          LEFT JOIN krf_tray_master t ON p.tray_id = t.tray_id
          WHERE p.projectid = ? AND p.productid = ? AND p.stockorder_no = ? AND p.sowinglotno_internal = ? AND p.roottype = ? AND p.sowingdate_internal = ?
            AND p.status != 'Removed'
          GROUP BY p.polyhouseid, COALESCE(NULLIF(TRIM(p.bed_no),''), TRIM(p.bedrackname))
          ORDER BY p.polyhouseid, CAST(REPLACE(REPLACE(COALESCE(NULLIF(TRIM(p.bed_no),''), TRIM(p.bedrackname)),'Bed-',''),'Bed ','') AS UNSIGNED)
      ");
      $stmtAllBeds->bind_param("isssss", $projectid, $productid, $stockorder_no, $sowinglotno_internal, $roottype, $sowingdate_internal);
      $stmtAllBeds->execute();
      $resAllBeds = $stmtAllBeds->get_result();
      while ($brow = $resAllBeds->fetch_assoc()) {
          $bpoldata = getPolyhouseData($krfconn, $projectid, "polyhouseid", $brow['polyhouseid']);
          $bpolyhousename = isset($bpoldata[0]['polyhousename']) ? $bpoldata[0]['polyhousename'] : '';
          
          $bedTotal = (int)$brow['bed_total'];
          $verifiedTrays = (int)$brow['verified_trays'];
          $unverifiedTrays = (int)$brow['unverified_trays'];
          $bedFail = (int)$brow['bed_fail'];
          $bedFailPercent = ($bedTotal > 0) ? round(($bedFail / $bedTotal) * 100, 2) : 0;
          $tenPercent = round($bedTotal * 0.1, 2);

          $bedTotalPlants = (int)($brow['bed_total_plants'] ?? 0);
          $bedFailPlants = (int)($brow['bed_fail_plants'] ?? 0);
          $bedFailTraysQty = (int)($brow['bed_fail_trays_qty'] ?? 0);
          // Bed Germination failure %: sum of failed plants / sum of qtypertray of fail trays * 100
          $bedPlantFailPercent = ($bedFailTraysQty > 0) ? round(($bedFailPlants / $bedFailTraysQty) * 100, 2) : 0;

          $bedStatsList[] = [
              'polyhouseid' => $brow['polyhouseid'],
              'polyhousename' => $bpolyhousename,
              'bedrackname' => $brow['bed_no'],
              'bed_no' => $brow['bed_no'],
              'bed_total_trays' => $bedTotal,
              'verified_trays' => $verifiedTrays,
              'unverified_trays' => $unverifiedTrays,
              'bed_fail_trays' => $bedFail,
              'bed_fail_percent' => $bedFailPercent,
              'bed_ten_percent' => $tenPercent,
              'bed_total_plants' => $bedTotalPlants,
              'bed_fail_plants' => $bedFailPlants,
              'bed_fail_trays_qty' => $bedFailTraysQty,
              'bed_plant_fail_percent' => $bedPlantFailPercent
          ];
      }
      $stmtAllBeds->close();

      // ✅ Product details
      $prd = getProductData($krfconn, $projectid, "ProductID", $reskey['productid']);
      $productName = isset($prd[0]['ProductName']) ? $prd[0]['ProductName'] : '';

      // ✅ Tray details
      $trd = getTrayData($krfconn, "tray_id", $reskey['tray_id']);
      $trayname = isset($trd[0]['tray_name']) ? $trd[0]['tray_name'] : '';
      $traycavitycount = isset($trd[0]['cavity_count']) ? $trd[0]['cavity_count'] : '';

      // ✅ Polyhouse details
      $poldata = getPolyhouseData($krfconn, $projectid, "polyhouseid", $reskey['polyhouseid']);
      $polyhousename = isset($poldata[0]['polyhousename']) ? $poldata[0]['polyhousename'] : '';

      if ($reskey['health_status'] == "Healthy") {
        $healthstatus = '<i class="fas fa-leaf text-success"></i> ' . $reskey['health_status'];
      } else if ($reskey['health_status'] == "Germination Failure") {
        $healthstatus = '<i class="fas fa-leaf text-danger"></i> ' . $reskey['health_status'] . ' <span class="badge badge-pill badge-danger" style="width:20px; color:white;">' . $reskey['failurecount'] . '</span>';
      } else {
        $healthstatus = '<i class="fa fa-clock-o"></i> ' . $reskey['health_status'];
      }

      $qchistorybtn = '';
      $qctrackingdata = getPolyhousePlantQCStatusData($krfconn, $projectid, 'polyhouseplantid', $reskey['polyhouseplantid'],$sowinglotno_internal);
      if (count($qctrackingdata) > 0) {
        $qchistorybtn = '<span data-polyhouseplantid=' . $reskey['polyhouseplantid'] . ' class="badge badge-success openuqchistory" style="cursor:pointer;">View Monitoring History</span>';
      }

      $response[] = [
        'polyhouseplantid' => $reskey['polyhouseplantid'],
        'productid' => $reskey['productid'],
        'productname' => $productName,
        'seedname' => $seedname,
        'trayid' => $reskey['tray_id'],
        'trayname' => $trayname,
        'traycavitycount' => $traycavitycount,
        'polyhouseid' => $reskey['polyhouseid'],
        'polyhousename' => $polyhousename,
        'lotno' => $reskey['lotno'],
        'status' => $reskey['status'],
        'health_status' => $healthstatus,
        'tray_position' => $reskey['tray_position'],
        'bedrackname' => $reskey['bedrackname'],
        'bed_no' => $bed_no,
        // Statistics
        'orderno' => $stockorder_no,
        'order_total_trays' => $ordTotal,
        'order_fail_trays' => $totalOrderFailTrays,
        'order_fail_trays_qty' => $totalOrderFailTraysQty,
        'order_tray_fail_percent' => $orderTrayFailPercent,
        'order_plan_qty' => $planQty,
        'order_total_sown' => $totalSownQty,
        'order_total_fail' => $totalOrderFailCount,
        'order_fail_percent' => $orderFailPercent,
        'planted_date' => ($planted_date ?? 'N/A'),
        'sowing_date' => ($sowing_date ?? 'N/A'),
        'sowingdate_internal' => ($sowingdate_internal ?? 'N/A'),
        'sowingdate_internaldsp' => !empty($sowingdate_internal) ? date('d-M-Y', strtotime($sowingdate_internal)) : 'N/A',
        'delivery_date' => ($prodOrder['expected_delivery_date'] ?? 'N/A'),
        'bed_stats_list' => $bedStatsList,


        'graftingid' => $reskey['graftingid'],
        'graftingdate' => $reskey['created_at'],
        'growth_stage' => $reskey['growth_stage'],
        'graftingdatedsp' => !empty($reskey['created_at']) ? date('d-M-Y', strtotime($reskey['created_at'])) : '',
        'last_inspection_date' => !empty($reskey['last_inspection_date']) ? date('d-M-Y', strtotime($reskey['last_inspection_date'])) : '',
        'last_inspection_remark' => $reskey['last_inspection_remark'] != "" ? $reskey['last_inspection_remark'] : 'N/A',
        'qchistorybtn' => $qchistorybtn
      ];
    }
    echo json_encode(['status' => 'success', 'count' => count($response), 'data' => $response]);
  } else {
    $stmtSowing = $krfconn->prepare("SELECT workingstatus, status FROM krf_polyhouse_sowingplant_details WHERE barcode = ? OR germination_qrcode = ? LIMIT 1");
    if ($stmtSowing) {
      $stmtSowing->bind_param("ss", $barcode, $barcode);
      $stmtSowing->execute();
      $sowingResult = $stmtSowing->get_result();
      if ($sowingRow = $sowingResult->fetch_assoc()) {
        $workingstatus = $sowingRow['workingstatus'] ?? '';
        $status = $sowingRow['status'] ?? '';
        $displayStatus = !empty($workingstatus) ? $workingstatus : (!empty($status) ? $status : 'Unknown');
        echo json_encode([
          'status' => 'error',
          'message' => "Tray details not found in monitoring. Sowing status of this tray is currently: " . $displayStatus . "."
        ]);
        $stmtSowing->close();
        exit;
      }
      $stmtSowing->close();
    }
    echo json_encode(['status' => 'error', 'message' => 'No plant data found for the given QR code.']);
  }
} else if ($action == "UpdateGerminationFailure") {
  $response = [
    'status' => 'error',
    'message' => 'Unexpected error occurred.'
  ];

  try {

    // Required inputs
    $companyid = $_POST['companyid'] ?? '';
    $subcompanyid = $_POST['subcompanyid'] ?? '';
    $projectid = $_POST['projectid'] ?? '';
    $loginid = $_POST['loginid'] ?? '';
    $polyhouseplantid = $_POST['polyhouseplantid'] ?? '';
    $failurecount = $_POST['unhealthyplants'] ?? '';
    $graftingqcremark = $_POST['graftingqcremark'] ?? '';

    // 🔐 Validation
    if (empty($polyhouseplantid)) {
      throw new Exception("Tray record not found. Please refresh and try again.");
    }

    if ($failurecount === '' || !is_numeric($failurecount)) {
      throw new Exception("Please enter the germination failure plant count.");
    }

    if ($failurecount < 0) {
      throw new Exception("Germination failure count cannot be negative.");
    }

    // Fetch tray details
    $realtraydata = getPolyhousePlantDetails(
      $krfconn,
      $projectid,
      'polyhouseplantid',
      $polyhouseplantid
    );

    if (empty($realtraydata)) {
      throw new Exception("Invalid tray details. Record not found.");
    }

    $trayid = $realtraydata[0]['tray_id'];
    $healthstatus = "Germination Failure";

    // Update query (FIXED)
    $fupdateat = date('Y-m-d H:i:s');
    $sql = "
          UPDATE krf_polyhouse_plant_details 
          SET 
              health_status = ?,
              growth_stage = ?, 
              failurecount  = ?, 
              remarks       = ?, 
              failmarked_by    = ?, 
              failmarked_at    = ?
          WHERE polyhouseplantid = ?
      "; 

    $stmt = $krfconn->prepare($sql);
    if (!$stmt) {
      throw new Exception("Database prepare failed.");
    }

    $stmt->bind_param(
      "ssissss",
      $healthstatus,
      $healthstatus,
      $failurecount,
      $graftingqcremark,
      $loginid,
      $fupdateat,
      $polyhouseplantid
    );

    if (!$stmt->execute()) {
      throw new Exception("Failed to update germination failure status.");
    }

    if ($stmt->affected_rows > 0) {
      // Extract required data
      $stockorder_no = $realtraydata[0]['stockorder_no'];
      $productid = $realtraydata[0]['productid'];
      $roottype = '';
      $seedid = '';

      // Fetch roottype from krf_polyhouse_plant_details
      $stmtRoot = $krfconn->prepare("SELECT roottype FROM krf_polyhouse_plant_details WHERE polyhouseplantid = ?");
      if ($stmtRoot) {
          $stmtRoot->bind_param("s", $polyhouseplantid);
          $stmtRoot->execute();
          $resRoot = $stmtRoot->get_result();
          if ($rrow = $resRoot->fetch_assoc()) {
              $roottype = $rrow['roottype'] ?? '';
          }
          $stmtRoot->close();
      }

      // Get Summary (applicable only when at least 10% tray germination process is complete)
      $summary = getGerminationFailureSummary($krfconn, $stockorder_no, $roottype);
      // print_r($summary);
      // Check failure rate
      if (!empty($summary) && !empty($summary[0]['is_applicable']) && ($summary[0]['failure_percent'] > 10 || $summary[0]['tray_failure_percent'] > 10)) {

          // Fetch seedid as per failed tray stockorder_no and roottype
          if ($roottype) {
              $stmtSeed = $krfconn->prepare("SELECT seedid FROM krf_polyhouse_sowingplant_details WHERE stockorder_no = ? AND roottype = ? LIMIT 1");
              $stmtSeed->bind_param("ss", $stockorder_no, $roottype);
              $stmtSeed->execute();
              $resSeed = $stmtSeed->get_result();
              if ($srow = $resSeed->fetch_assoc()) {
                  $seedid = $srow['seedid'];
              }
              $stmtSeed->close();
          }

          $stockOrderDetails = getFullProductionStockOrdersDetails($krfconn, $projectid, 'stockorder_no', $stockorder_no);
          if (!empty($stockOrderDetails)) {
              $clientid = $stockOrderDetails[0]['clientid'];
              $quantity = $summary[0]['resowing_qty'];
              //$ssst = createResowingProductionOrder($krfconn, $companyid, $subcompanyid, $projectid, $productid, $quantity, $clientid, $loginid, 7, $roottype, $seedid, $stockorder_no);
              if($ssst['status']=="success"){
                $summaryRow = $summary;
                //sendWhatsappAlert_ResowingOrder($krfconn,$summaryRow,$ssst['stockorder_no'],$centreid = 1,$lng = "en");
              }
          }
      }

      $response = [
        'status' => 'success',
        'message' => 'Germination failure details updated successfully.'
      ];
    } else {
      $response = [
        'status' => 'warning',
        'message' => 'No changes detected. Record already up to date.'
      ];
    }

    $stmt->close();

  } catch (Exception $e) {

    $response = [
      'status' => 'error',
      'message' => $e->getMessage()
    ];
  }

  echo json_encode($response);
  exit;
}
?>