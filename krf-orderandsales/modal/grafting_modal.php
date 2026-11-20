<?php
error_reporting(E_ALL);
require('../../connection.php');
require('../../loginCell/functions/login_functions.php');
include("../functions/labour_functions.php");
include("../functions/product_functions.php");
include("../functions/tray_functions.php");
date_default_timezone_set('Asia/Kolkata');
$action = $_GET['action'];
 if ($action == "GetLabourDailyGraftingSummary") {

    header('Content-Type: application/json; charset=utf-8');

    $projectid    = $_POST['projectid'] ?? '';
    $locationid   = trim($_POST['locationid'] ?? '');
    $labourid     = trim($_POST['labourid'] ?? '');
    $graftingdate = $_POST['graftingdate'] ?? '';


    if (
        $projectid === '' ||
        $locationid === '' ||
        $labourid === '' ||
        $graftingdate === ''
    ) {

        echo json_encode([
            "status"  => "error",
            "message" => "Required information is missing."
        ]);

        exit;
    }


    // ============================================================
    // ALL PRODUCTS / ORDERS ASSIGNED TO THIS LABOUR TODAY
    // ============================================================

    $sql = "
        SELECT
            productid,
            MAX(productname) AS productname,
            stockorder_no,

            MAX(COALESCE(todaytarget, 0))
                AS todaytarget,

            SUM(COALESCE(noofgraftedtry, 0))
                AS assigned_qty,

            SUM(COALESCE(noroottrayassigned, 0))
                AS root_assigned,

            SUM(COALESCE(nosciontrayassigned, 0))
                AS scion_assigned,

            MAX(allocationtime)
                AS allocationtime

        FROM krf_graftingdetails_daily

        WHERE projectid = ?
          AND locationid = ?
          AND labourid = ?
          AND graftingdate = ?

        GROUP BY
            productid,
            stockorder_no

        ORDER BY
            MAX(allocationtime),
            productname,
            stockorder_no
    ";


    $stmt = $krfconn->prepare($sql);

    if (!$stmt) {

        echo json_encode([
            "status"  => "error",
            "message" => $krfconn->error
        ]);

        exit;
    }


    $stmt->bind_param(
        "ssss",
        $projectid,
        $locationid,
        $labourid,
        $graftingdate
    );


    $stmt->execute();

    $result = $stmt->get_result();


    $details       = [];
    $dayTarget     = 0;
    $totalAssigned = 0;
    $totalRoot     = 0;
    $totalScion    = 0;


    while ($row = $result->fetch_assoc()) {

        $dayTarget = max(
            $dayTarget,
            (int)$row['todaytarget']
        );

        $assigned =
            (int)$row['assigned_qty'];

        $rootAssigned =
            (int)$row['root_assigned'];

        $scionAssigned =
            (int)$row['scion_assigned'];


        $totalAssigned += $assigned;
        $totalRoot     += $rootAssigned;
        $totalScion    += $scionAssigned;


        $details[] = [

            "productid" =>
                $row['productid'] ?? '',

            "productname" =>
                $row['productname'] ?? '',

            "stockorder_no" =>
                $row['stockorder_no'] ?? '',

            "assigned_qty" =>
                $assigned,

            "root_assigned" =>
                $rootAssigned,

            "scion_assigned" =>
                $scionAssigned,

            "allocationtime" =>
                $row['allocationtime'] ?? ''
        ];
    }


    $stmt->close();


    $balance = max(
        0,
        $dayTarget - $totalAssigned
    );


    echo json_encode([

        "status" => "success",

        "summary" => [

            "daytarget" =>
                $dayTarget,

            "totalassigned" =>
                $totalAssigned,

            "balance" =>
                $balance,

            "totalroot" =>
                $totalRoot,

            "totalscion" =>
                $totalScion
        ],

        "details" =>
            $details

    ], JSON_UNESCAPED_UNICODE);


    exit;
}
else if($action=="GetlaboursWithLocationProductForGrafting"){
  $companyid = $_POST['companyid'] ?? 0;
  $subcompanyid = $_POST['subcompanyid'] ?? 0;
  $projectid = $_POST['projectid'] ?? 0;
  $locationid = $_POST['locationid'] ?? 0;
  $productid = $_POST['productid'] ?? 0;
  $graftingdate = $_POST['graftingdate'] ?? date('Y-m-d');

  $lblist=fetchLabourDataForGrafting($hrmconn, $companyid, $subcompanyid, $projectid, $locationid);
  // print_r($lblist);
  $grfdata = [];
  if(count($lblist)>0){
    $gftdata = getGraftingTargetForDate($krfconn, $projectid, $productid, $locationid, $graftingdate);
    foreach ($lblist as $lbkey) {
      $checkgrt = checkGraftingExistOnDate($krfconn, $projectid, $locationid, $productid, $lbkey['EmployeeID'], $graftingdate);
      if($checkgrt){
        $allocationstatus="yes";
        $graftraytetarget=$checkgrt['target_traycount'];
        $graftedcount=$checkgrt['grafted_traycount'];
        $plantpertray=$checkgrt['plantpertray'];
        $targetplants = $gftdata['target_plants'];
        $graftdifference=$checkgrt['graftdifference'];
        $allocationtime=$checkgrt['allocationtime'];
        $trayid = $checkgrt['trayid'];
      }else{
        $allocationstatus="no";
        $graftraytetarget = $gftdata['target_traycount'];
        $plantpertray = $gftdata['plantpertray'];
        $targetplants = $gftdata['target_plants'];
        $trayid = $gftdata['trayid'];
        $graftedcount=0;
        $graftdifference=0;
        $allocationtime="";
      }
      $grfdata[] = array("LabourId"=>$lbkey['EmployeeID'], "LabourName"=>$lbkey['FullName'], "Gender"=>$lbkey['Gender'], "Gender"=>$lbkey['Gender'], "locationid"=>$lbkey['locationid'], "DesignationID"=>$lbkey['DesignationID'], "DepartmentID"=>$lbkey['DepartmentID'], 'graftingskill'=>$lbkey['graftingskill'], 'PerDayPayment'=>$lbkey['PerDayPayment'], 'graftraytetarget'=>$graftraytetarget, 'graftedcount'=>$graftedcount, 'plantpertray'=>$plantpertray, 'targetplants'=>$targetplants, 'graftdifference'=>$graftdifference, 'allocationstatus'=>$allocationstatus, 'allocationtime'=>$allocationtime, 'trayid'=>$trayid);
    }
  }
  echo json_encode($grfdata);
} 
   else if ($action == "GetlaboursWithLocationProductForGrafting_Ajay") {

    header('Content-Type: application/json; charset=utf-8');

    $companyid    = $_POST['companyid'] ?? 0;
    $subcompanyid = $_POST['subcompanyid'] ?? 0;
    $projectid    = $_POST['projectid'] ?? 0;
    $locationid   = trim($_POST['locationid'] ?? '');
    $productid    = $_POST['productid'] ?? 0;
    $graftingdate = $_POST['graftingdate'] ?? date('Y-m-d');


    // ============================================================
    // 1. GET PRESENT GRAFTING LABOURS FROM HRM
    // ============================================================

    $lblist = fetchLabourDataForGrafting_ajay(
        $hrmconn,
        $companyid,
        $subcompanyid,
        $projectid,
        $locationid,
        $graftingdate
    );


    // ============================================================
    // 2. MANAGEMENT ROUGH TARGET FOR SELECTED PRODUCT
    // ============================================================

    $gftdata = getGraftingTargetForDate_ajay(
        $krfconn,
        $projectid, 
        $locationid,
        $graftingdate
    );


    $managementTarget = 0;
    $plantpertray     = 0;
    $targetplants     = 0;
    $trayid           = '';

    if (is_array($gftdata)) {

        $managementTarget =
            (int)($gftdata['target_traycount'] ?? 0);

        $plantpertray =
            (int)($gftdata['plantpertray'] ?? 0);

        $targetplants =
            (int)($gftdata['target_plants'] ?? 0);

        $trayid =
            $gftdata['trayid'] ?? '';
    }


    // ============================================================
    // 3. GET DAILY LABOUR SUMMARY
    //
    // IMPORTANT:
    // NO productid / psono / stockorder filter here.
    //
    // We need total allocation of labour for WHOLE DAY.
    // ============================================================

    $dailySql = "  SELECT
            labourid,

            MAX(COALESCE(todaytarget, 0))
                AS todaytarget,

            SUM(COALESCE(noofgraftedtry, 0))
                AS dailyassigned,

            SUM(COALESCE(noroottrayassigned, 0))
                AS dailyrootassigned,

            SUM(COALESCE(nosciontrayassigned, 0))
                AS dailyscionassigned

        FROM krf_graftingdetails_daily

        WHERE projectid = ?
          AND locationid = ?
          AND graftingdate = ?

        GROUP BY labourid
    ";


    $dailyStmt = $krfconn->prepare($dailySql);

    if (!$dailyStmt) {

        echo json_encode([
            "status"  => "error",
            "message" => "Daily labour allocation SQL Error: " .
                         $krfconn->error
        ]);

        exit;
    }


    $dailyStmt->bind_param(
        "iss",
        $projectid,
        $locationid,
        $graftingdate
    );

    $dailyStmt->execute();

    $dailyResult = $dailyStmt->get_result();


    // ============================================================
    // 4. CREATE LABOUR-WISE LOOKUP
    // ============================================================

    $dailySummary = [];


    while ($row = $dailyResult->fetch_assoc()) {

        $labourId = (string)$row['labourid'];

        $dailySummary[$labourId] = [

            "todaytarget" =>
                (int)$row['todaytarget'],

            "dailyassigned" =>
                (int)$row['dailyassigned'],

            "dailyrootassigned" =>
                (int)$row['dailyrootassigned'],

            "dailyscionassigned" =>
                (int)$row['dailyscionassigned']
        ];
    }


    $dailyStmt->close();


    // ============================================================
    // 5. PREPARE RESPONSE
    // ============================================================

    $grfdata = [];


    foreach ($lblist as $lbkey) {

        $labourid =
            (string)$lbkey['EmployeeID'];


        // --------------------------------------------------------
        // DOES THIS LABOUR ALREADY HAVE DAILY HISTORY?
        // --------------------------------------------------------

        $daily =
            $dailySummary[$labourid] ?? null;


        if ($daily !== null) {

            // ---------------------------------------------
            // LABOUR ALREADY HAS A DAY TARGET
            // ---------------------------------------------

            $todaytarget =
                (int)$daily['todaytarget'];

            $dailyassigned =
                (int)$daily['dailyassigned'];

            $dailyrootassigned =
                (int)$daily['dailyrootassigned'];

            $dailyscionassigned =
                (int)$daily['dailyscionassigned'];


            if ($todaytarget > 0) {

                $targetsource = "saved";

            } else if ($managementTarget > 0) {

                // History exists but no Day Target was set.
                $todaytarget = $managementTarget;
                $targetsource = "suggested";

            } else {

                $todaytarget = 0;
                $targetsource = "none";
            }

        } else {

            // ---------------------------------------------
            // NO PREVIOUS ALLOCATION TODAY
            // ---------------------------------------------

            $dailyassigned      = 0;
            $dailyrootassigned  = 0;
            $dailyscionassigned = 0;


            if ($managementTarget > 0) {

                $todaytarget =
                    $managementTarget;

                $targetsource =
                    "suggested";

            } else {

                $todaytarget = 0;

                $targetsource =
                    "none";
            }
        }


        // --------------------------------------------------------
        // DAILY BALANCE
        // --------------------------------------------------------

        $dailybalance = max(
            0,
            $todaytarget - $dailyassigned
        );


        // ========================================================
        // RESPONSE
        // ========================================================

        $grfdata[] = [

            // HRM
            "LabourId" =>
                $labourid,

            "LabourName" =>
                $lbkey['FullName'] ?? '',

            "Gender" =>
                $lbkey['Gender'] ?? '',

            "locationid" =>
                $lbkey['locationid'] ?? '',

            "DesignationID" =>
                $lbkey['DesignationID'] ?? '',

            "DepartmentID" =>
                $lbkey['DepartmentID'] ?? '',

            "graftingskill" =>
                $lbkey['graftingskill'] ?? '',


            // ================================================
            // LABOUR DAILY INFORMATION
            // ================================================

            "todaystarget" =>
                $todaytarget,

            "targetsource" =>
                $targetsource,

            "dailyassigned" =>
                $dailyassigned,

            "dailybalance" =>
                $dailybalance,

            "dailyrootassigned" =>
                $dailyrootassigned,

            "dailyscionassigned" =>
                $dailyscionassigned,


            // ================================================
            // MANAGEMENT ROUGH TARGET
            // ================================================

            "managementtarget" =>
                $managementTarget,

            "plantpertray" =>
                $plantpertray,

            "targetplants" =>
                $targetplants,

            "trayid" =>
                $trayid
        ];
    }


    echo json_encode(
        $grfdata,
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

else if($action=="PendingForSubmissionGrafting"){
  $companyid = $_POST['companyid'] ?? 0;
  $subcompanyid = $_POST['subcompanyid'] ?? 0;
  $projectid = $_POST['projectid'] ?? 0;
  $locationid = $_POST['locationid'] ?? 0;
  $productid = $_POST['productid'] ?? 0;
  $graftingdate = $_POST['graftingdate'] ?? date('Y-m-d');
  $pdgf = fetchGraftingDetailsDaily($krfconn, $projectid, $graftingdate, $graftingdate, "pendingforsubmissionwithlocationid", $locationid);
  // print_r($lblist);
  $grfdata = [];
  if(count($pdgf)>0){
    $labourName ="";
    foreach ($pdgf as $lbkey) {
      
      // labour details
      $lblist=fetchLabourDataForProduction($hrmconn, $companyid, $subcompanyid, $projectid, "LabourID", $lbkey['labourid']);
      $labourName = $lblist[0]['FullName'];
      $Gender = $lblist[0]['Gender'];

      // product details
      $prdd = getProductData($krfconn, $projectid, "ProductID", $lbkey['productid']);
      $ProductName = $prdd[0]['ProductName'];

      // tray details
      $traydata = getTrayData($krfconn, 'tray_id',$lbkey['trayid']);
      $trayname = $traydata[0]['tray_name'];
      $pplval = $lbkey['labourid']."|".$lbkey['graftingdate'];
      $polihousedata = getPolyhousePlantDetails($krfconn, $projectid, 'pendingGraftinglabouridwithdate', $pplval);

      $grfdata[] = array("GraftingId"=>$lbkey['id'], "LabourId"=>$lbkey['labourid'], "LabourName"=>$labourName, "Gender"=>$Gender, "locationid"=>$lbkey['locationid'], 'allocationdate'=>$lbkey['graftingdate'], 'allocationtime'=>$lbkey['allocationtime'], 'targettraycount'=>$lbkey['target_traycount'], 'plantpertray'=>$lbkey['plantpertray'], 'target_plants'=>$lbkey['target_plants'], 'graftedtraycount'=>$lbkey['grafted_traycount']>0?$lbkey['grafted_traycount']:'0', 'ProductID'=>$lbkey['productid'], 'ProductName'=>$ProductName, 'trayid'=>$lbkey['trayid'], 'trayname'=>$trayname, 'polyhouseplantdata'=>$polihousedata);
    }
  }
  echo json_encode($grfdata);
}else if($action=="ProductionLocationMastercallback"){
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $sortBy = $_POST['shortby'];
    $sortByValue = $_POST['shortbyvalue'];
    $locationmaster=getProductionLocationMaster($hrmconn,$companyid,$subcompanyid,$projectid,$sortBy,$sortByValue);
    echo json_encode($locationmaster);
}else if($action=="SaveUpdatedailyGraftingTarget"){
  echo saveGraftingTargetRange($krfconn,$hrmconn,$_POST['companyid'],$_POST['subcompanyid'], $_POST['projectid'], $_POST['productlist'], $_POST['product_sku'], $_POST['locationlist'], $_POST['start_date'], $_POST['end_date'], $_POST['trayid'], $_POST['plantcountpertray'], $_POST['target_traycount'], '', $_POST['loginid']);
}else if($action=="GetProductListForGrafting"){
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $sortBy = $_POST['shortby'];
    $sortByValue = $_POST['shortbyvalue'];
    $pndgrfd=getGraftedOrderDetailsWithProgress($krfconn,$projectid,$sortBy,$sortByValue);
    echo json_encode($pndgrfd);
}else if($action=="UpdateDailyGraftingSubmissionForm"){
  $GraftingData = json_decode($_POST['labourgraftedData'], true);
  echo saveMultipleDailyGraftingSubmissionDetails($krfconn,$hrmconn,$_POST['projectid'],$_POST['locationid'],$_POST['graftingdate'],$_POST['loginid'],$GraftingData);
}else if($action=="UpdateSubmissionGraftingDeails"){
  $companyid    = $_POST['companyid'];
  $subcompanyid = $_POST['subcompanyid'];
  $projectid    = $_POST['projectid'];
  $loginid      = $_POST['loginid'];  
  $graftingid   = $_POST['graftingid'];
  $productid    = $_POST['productid'];
  $labourid     = $_POST['labourid'];
  $graftedcount = $_POST['graftedcount'];
  $polyhouseid  = $_POST['polyhouseid'];
  $activeprocess = $_POST['processstatus'];

  $batchno      = '';
  // 🟢 Fetch Grafting details
  $gfdata = fetchGraftingDetailsDaily($krfconn, $projectid, '', '', 'id', $graftingid);
  $trayid = $gfdata[0]['trayid'] ?? null;
  $alreadygraftedcount = $gfdata[0]['grafted_traycount'] ?? 0;
  $psono = $gfdata[0]['psono'] ?? 0;
  $stockorder_no = $gfdata[0]['stockorder_no'] ?? 0;
  $locationid = $gfdata[0]['locationid'] ?? 0;
  $newgraftedCount = $alreadygraftedcount + $graftedcount; 

  // 🟢 Fetch Product details
  $prddata = getProductData($conn, $projectid, "ProductID", $productid);
  $productName = $prddata[0]['ProductName'] ?? '';
  $source_type = "Graft";

  try {
      // Validate inputs
      if (empty($graftedcount) || !is_numeric($graftedcount) || $graftedcount <= 0) {
          echo json_encode(["status" => "error", "message" => "Invalid grafted count. Please enter a valid number of grafted plants."]);
          exit;
      }

      // Start transaction
      $krfconn->begin_transaction();

      // 🟢 Step 1: Update the grafting record
      $updateSql = " UPDATE krf_graftingdetails_daily SET grafted_traycount = ?, polyhouseid = ?, psono = ?, stockorder_no = ?, locationid = ?, status = ?, submitted_by = ?, submitted_at = ?
          WHERE projectid = ? AND id = ?
      ";

      $stmtUpdate = $krfconn->prepare($updateSql);
      $submitted_at = date("Y-m-d H:i:s");
      $stmtUpdate->bind_param("iisssssssi", $newgraftedCount, $polyhouseid, $psono,$stockorder_no, $locationid, $loginid, $activeprocess, $submitted_at, $projectid, $graftingid);

      if (!$stmtUpdate->execute()) {
          throw new Exception("Failed to update grafting details.");
      }
      $stmtUpdate->close();

      // 🟢 Step 2: Insert into polyhouse plant details
      $insertSql = "
          INSERT INTO krf_polyhouse_plant_details
          (graftingid, labourid, polyhouseid, psono, stockorder_no, locationid, projectid, batchno, productid, productname, source_type, tray_id, created_by, created_at)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
      ";

      $stmtInsert = $krfconn->prepare($insertSql);

      for ($i = 0; $i < $graftedcount; $i++) {
          $stmtInsert->bind_param(
              "iiissssssssis",
              $graftingid,
              $labourid,
              $polyhouseid,
              $psono,
              $stockorder_no,
              $locationid,
              $projectid,
              $batchno,
              $productid,
              $productName,
              $source_type,
              $trayid,
              $loginid
          );
          $stmtInsert->execute();
      }

      $stmtInsert->close();
      // Commit transaction
      $krfconn->commit();

      echo json_encode([
          "status" => "success",
          "message" => "Grafting details updated and $graftedcount grafted plants inserted successfully."
      ]);

  } catch (Exception $e) {
      // Rollback if any error occurs
      $krfconn->rollback();
      echo json_encode([
          "status" => "error",
          "message" => $e->getMessage()
      ]);
  }
}else if($action=="SaveUpdatedailyGraftingAllocationForm"){
  $companyid = $_POST['companyid'];
  $subcompanyid = $_POST['subcompanyid'];
  $projectid = $_POST['projectid'];
  $loginid = $_POST['loginid'];
  $graftingdate = $_POST['graftingdate'];
  $locationid = $_POST['locationid'];
  $productid = $_POST['productid'];
  $allocationgraftedData = json_decode($_POST['allocationgraftedData'], true);
  echo saveMultipleDailyGraftingAllocationDetails($krfconn, $hrmconn, $projectid, $graftingdate, $locationid, $productid, $loginid, $allocationgraftedData);
}else if($action=="GraftingDatesList"){
  $companyid = $_POST['companyid'];
  $subcompanyid = $_POST['subcompanyid'];
  $projectid = $_POST['projectid'];
  $locationid = $_POST['locationid'];
  echo json_encode(GraftingDatesList($krfconn, $projectid, $locationid));
}else if($action=="GetGraftedByList"){
  $companyid    = $_POST['companyid'] ?? '';
  $subcompanyid = $_POST['subcompanyid'] ?? '';
  $projectid    = $_POST['projectid'] ?? '';
  $locationid   = $_POST['locationid'] ?? '';
  $grafteddate  = $_POST['grafteddate'] ?? '';
  $graftedbylist = [];
  $gflbdata = GraftedByForListwithlocationandDate($krfconn, $projectid, $locationid, $grafteddate);
  if (!empty($gflbdata)) {
      foreach ($gflbdata as $gftkey) {
          $lbdata = fetchLabourDataForProduction($hrmconn,$companyid,$subcompanyid,$projectid,"LabourID",$gftkey['labourid']);
          if (!empty($lbdata) && isset($lbdata[0]['FullName'])) {
              $graftedbylist[] = [
                  'labourid'   => $gftkey['labourid'],
                  'labourname' => $lbdata[0]['FullName']
              ];
          }
      }
  }
  echo json_encode($graftedbylist);
}else if($action=="GraftedByForBarcode"){
  $companyid    = $_POST['companyid'] ?? '';
  $subcompanyid = $_POST['subcompanyid'] ?? '';
  $projectid    = $_POST['projectid'] ?? '';
  $locationid   = $_POST['locationid'] ?? '';
  $grafteddate  = $_POST['grafteddate'] ?? '';
  $graftedbylist = [];
  $gflbdata = GraftedByForBarcode($krfconn, $projectid, $locationid, $grafteddate);
  if (!empty($gflbdata)) {
      foreach ($gflbdata as $gftkey) {
          $lbdata = fetchLabourDataForProduction($hrmconn,$companyid,$subcompanyid,$projectid,"LabourID",$gftkey['labourid']);
          if (!empty($lbdata) && isset($lbdata[0]['FullName'])) {
              $graftedbylist[] = [
                  'labourid'   => $gftkey['labourid'],
                  'labourname' => $lbdata[0]['FullName']
              ];
          }
      }
  }
  echo json_encode($graftedbylist);
}else if($action=="GraftingDatesForMissingBarcode"){
  $companyid = $_POST['companyid'];
  $subcompanyid = $_POST['subcompanyid'];
  $projectid = $_POST['projectid'];
  $locationid = $_POST['locationid'];
  echo json_encode(getGraftingDatesForMissingBarcode($krfconn, $projectid, $locationid));
}else if($action=="GraftingTargetMonthYearList"){
  $companyid = $_POST['companyid'];
  $subcompanyid = $_POST['subcompanyid'];
  $projectid = $_POST['projectid'];
  $targetyear = $_POST['targetyear'];
  $trgmy = getGraftingMonthYearList($krfconn, $projectid, $targetyear);
  echo json_encode($trgmy);
}else if($action=="GraftingDetailsWithMonthYear"){
  $companyid = $_POST['companyid'];
  $subcompanyid = $_POST['subcompanyid'];
  $projectid = $_POST['projectid'];
  $yearmonth = $_POST['yearmonth'];
  $ftgrf = fetchGraftingTargetDaily($krfconn, $projectid, "month_year", $yearmonth);
  $trgd=[];
  if(count($ftgrf)>0){
    foreach ($ftgrf as $ftgkey) {
      // product name
      //$pddata = getProductData($krfconn, $projectid, "ProductID", $ftgkey['productid']);
      $productiName = '';//$pddata[0]['ProductName'];

      // production location
      $lgd = getProductionLocationMaster($hrmconn, $companyid, $subcompanyid, $projectid, "LocationId", $ftgkey['locationid']);
      $locationName = $lgd[0]['LocationName'];
      $trgd[]=array('id'=>$ftgkey['id'], 'targetdate'=>$ftgkey['target_date'], 'targetdatedsp'=>date('d-M-Y', strtotime($ftgkey['target_date'])), 'product_name'=>$productiName, 'productid'=>$ftgkey['productid'], 'product_sku'=>$ftgkey['product_sku'], 'location_name'=>$locationName, 'locationid'=>$ftgkey['locationid'], 'trayid'=>$ftgkey['trayid'], 'target_traycount'=>$ftgkey['target_traycount'], 'target_plants'=>$ftgkey['target_plants'], 'plantpertray'=>$ftgkey['plantpertray']);
    }
  }
  echo json_encode($trgd);
}else if($action=="PendingBarcodeForGraftedPlants"){
  $companyid = $_POST['companyid'] ?? 0;
  $subcompanyid = $_POST['subcompanyid'] ?? 0;
  $projectid = $_POST['projectid'] ?? 0;
  $locationid = $_POST['locationid'] ?? '';
  $grafteddate = $_POST['grafteddate'] ?? date('Y-m-d');
  $labourid = $_POST['labourid'] ?? '';
  $pdgf = GraftedBarcodeDetailsGoupByBatchno($krfconn, $projectid, $locationid, $grafteddate, $labourid);
  // print_r($lblist);
  $grfdata = [];
  if(count($pdgf)>0){
    $labourName ="";
    foreach ($pdgf as $lbkey) {
      
      // labour details
      $lblist=fetchLabourDataForProduction($hrmconn, $companyid, $subcompanyid, $projectid, "LabourID", $lbkey['labourid']);
      $labourName = $lblist[0]['FullName'];
      $Gender = $lblist[0]['Gender'];

      // product details
      $prdd = getProductData($krfconn, $projectid, "ProductID", $lbkey['productid']);
      $ProductName = $prdd[0]['ProductName'];

      // tray details
      $traydata = getTrayData($krfconn, 'tray_id',$lbkey['tray_id']);
      $trayname = $traydata[0]['tray_name'];

      // polyhouse details
      $polyhousename ="";
      if($lbkey['polyhouseid']){
        $plhdata = getPolyhouseData($krfconn, $projectid, 'polyhouseid', $lbkey['polyhouseid']);
        $polyhousename = $plhdata[0]['polyhousename'];
      }
      // g.id,g.productid,g.locationid,g.graftingdate,g.allocationtime,g.submitted_at,g.target_traycount,g.plantpertray,p.labourid,p.polyhouseid,p.batchno,p.barcode,p.tray_id
      // polyhouseid
      // grafted_traycount
      $submissiondatetime="";
      if($lbkey['submitted_at']){
        $submissiondatetime = date('d-M-Y H:i:s', strtotime($lbkey['submitted_at']));
      }
      $grfdata[] = array("GraftingId"=>$lbkey['id'], "graftingdate"=>$lbkey['graftingdate'], "barcode"=>$lbkey['barcode'], "LabourId"=>$lbkey['labourid'], "LabourName"=>$labourName, "Gender"=>$Gender, "locationid"=>$lbkey['locationid'], 'allocationdate'=>$lbkey['graftingdate'], 'allocationtime'=>$lbkey['allocationtime'],'submissiondatetime'=>$submissiondatetime, 'targettraycount'=>$lbkey['target_traycount'], 'plantpertray'=>$lbkey['plantpertray'], 'graftedtraycount'=>$lbkey['grafted_traycount']>0?$lbkey['grafted_traycount']:'0', 'ProductID'=>$lbkey['productid'], 'ProductName'=>$ProductName, 'trayid'=>$lbkey['tray_id'], 'trayname'=>$trayname, 'polyhouseid'=>$lbkey['polyhouseid'], 'polyhousename'=>$polyhousename);
    }
  }
  echo json_encode($grfdata);
}else if($action=="GraftedPlantsByLabourForDatewithLocation"){
  $companyid = $_POST['companyid'] ?? 0;
  $subcompanyid = $_POST['subcompanyid'] ?? 0;
  $projectid = $_POST['projectid'] ?? 0;
  $locationid = $_POST['locationid'] ?? '';
  $grafteddate = $_POST['grafteddate'] ?? date('Y-m-d');
  $labourid = $_POST['labourid'] ?? '';
  $pdgf = GraftedPlantsByLabourForDatewithLocation($krfconn, $projectid, $locationid, $grafteddate, $labourid);
  echo json_encode($pdgf);
}else if($action == "UpdateTrayDeailsForPolyhousewithBarcode"){
  $response = ['status' => 'error', 'message' => 'Unexpected error occurred'];

  try {
      // Validate required POST fields
      $required = ['companyid', 'subcompanyid', 'projectid', 'loginid', 'productid', 'labourid', 'graftingid', 'polyhouseid', 'tabledata'];
      foreach ($required as $key) {
          if (!isset($_POST[$key])) {
              throw new Exception("Missing required field: $key");
          }
      }

      // Extract POST data
      $companyid      = trim($_POST['companyid']);
      $subcompanyid   = trim($_POST['subcompanyid']);
      $projectid      = trim($_POST['projectid']);
      $loginid        = trim($_POST['loginid']);
      $productid      = trim($_POST['productid']);
      $labourid       = trim($_POST['labourid']);
      $graftingid     = trim($_POST['graftingid']);
      $polyhouseid    = intval($_POST['polyhouseid']);
      $rowsdata       = json_decode($_POST['tabledata'], true);

      if (empty($rowsdata) || !is_array($rowsdata)) {
          throw new Exception("No valid barcode data found.");
      }

      // ✅ Begin transaction
      $krfconn->begin_transaction();
      $updateCount = 0;

      // Prepare the UPDATE query once (better performance)
      $update = $krfconn->prepare("
          UPDATE krf_polyhouse_plant_details 
          SET bed_no = ?, rack_no = ?, floar_no = ?, tray_position = ?, barcode = ?, 
              updated_at = NOW(), updated_by = ?, polyhouseid = ?
          WHERE projectid = ? AND polyhouseplantid = ?
      ");

      if (!$update) {
          throw new Exception("Prepare failed: " . $krfconn->error);
      }

      // Loop through rows and update
      foreach ($rowsdata as $row) {
          $polyhouseplantid = isset($row['polyhouseplantid']) ? intval($row['polyhouseplantid']) : 0;
          $barcodeno        = isset($row['barcode']) ? trim($row['barcode']) : '';
          $bed_no           = isset($row['bednumber']) ? trim($row['bednumber']) : '';
          $rack_no          = isset($row['racknumber']) ? trim($row['racknumber']) : '';
          $floar_no         = isset($row['floarnumber']) ? trim($row['floarnumber']) : '';
          $tray_position         = isset($row['trayposition']) ? trim($row['trayposition']) : '';

          // Skip if barcode is empty
          if ($barcodeno === '') continue;

          // Bind parameters for this iteration
          $update->bind_param(
              "ssssssisi",
              $bed_no, $rack_no, $floar_no, $tray_position,
              $barcodeno, $loginid, $polyhouseid,
              $projectid, $polyhouseplantid
          );

          if (!$update->execute()) {
              throw new Exception("Failed to update barcode: $barcodeno → " . $update->error);
          }

          if ($update->affected_rows > 0) {
              $updateCount++;
          }
      }

      // ✅ Commit transaction if all updates succeed
      $krfconn->commit();

      $msg = ($updateCount > 0)
          ? "$updateCount barcode(s) updated successfully."
          : "No records were updated.";

      $response = ['status' => 'success', 'message' => $msg];

  } catch (Exception $e) {
      // ❌ Rollback transaction on any error
      if ($krfconn->in_transaction) {
          $krfconn->rollback();
      }
      $response = ['status' => 'error', 'message' => $e->getMessage()];
  }

  echo json_encode($response);
  exit;

}
?>