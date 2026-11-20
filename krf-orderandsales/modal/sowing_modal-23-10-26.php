<?php
// error_reporting(E_ALL);
error_reporting(1);
require('../../connection.php');
require_once __DIR__.'/sowing_save_validation.php';
require('../../loginCell/functions/login_functions.php');
include("../functions/orders_functions.php");
include("../functions/product_functions.php");
include("../functions/seed_functions.php");
include("../functions/tray_functions.php");
include("../functions/client_functions.php");
$action = $_GET['action'];
if ($action == "sowingProductLIst") {
    $projectid = (int)($_POST['projectid'] ?? 0);
    if ($projectid <= 0) {
        echo json_encode([]);
        exit;
    }
    $returnarray = [];
      $qry = "
        SELECT 
            client.client_name,
            IFNULL(g.fullcycledays, 0) AS growthperiod,
            p.productname, 
            s.SeedName AS seedname,
            p.sku AS productsku,
            stk.sowingbalqty AS balqty,  
            stk.productid, 
            stk.psono,   
            stk.stockorder_no,  
            stk.expected_delivery_date,
            stk.sowingdate AS sowing_start_date,
            'Root' AS roottype,
            WEEK(stk.sowingdate) AS sowing_week,
            DATE_SUB(stk.sowingdate, INTERVAL WEEKDAY(stk.sowingdate) DAY) AS week_start,
            DATE_ADD(DATE_SUB(stk.sowingdate, INTERVAL WEEKDAY(stk.sowingdate) DAY), INTERVAL 6 DAY) AS week_end
        FROM krf_production_stockorder stk
        JOIN krf_client_master client ON client.projectid = stk.projectid AND client.client_id = stk.clientid 
        JOIN product_master p ON p.projectid = stk.projectid AND p.productid = stk.productid
        LEFT JOIN seed_master s ON s.SeedID = p.SeedId
        LEFT JOIN krf_growthstages g ON g.projectid = stk.projectid AND g.productid = stk.productid 
        WHERE stk.projectid = ?
          AND stk.sowingbalqty > 0 
          AND LCASE(stk.status) = 'confirmed'
          AND stk.sowingdate IS NOT NULL

        UNION ALL

        SELECT 
            client.client_name,
            IFNULL(g.fullcycledays, 0) AS growthperiod,
            p.productname, 
            s2.SeedName AS seedname,
            p.sku AS productsku,
            stk.sowingbalqty AS balqty,  
            stk.productid, 
            stk.psono,   
            stk.stockorder_no,  
            stk.expected_delivery_date,
            stk.scionsowingdate AS sowing_start_date,
            'Scion' AS roottype,
            WEEK(stk.scionsowingdate) AS sowing_week,
            DATE_SUB(stk.scionsowingdate, INTERVAL WEEKDAY(stk.scionsowingdate) DAY) AS week_start,
            DATE_ADD(DATE_SUB(stk.scionsowingdate, INTERVAL WEEKDAY(stk.scionsowingdate) DAY), INTERVAL 6 DAY) AS week_end
        FROM krf_production_stockorder stk
        JOIN krf_client_master client ON client.projectid = stk.projectid AND client.client_id = stk.clientid 
        JOIN product_master p ON p.projectid = stk.projectid AND p.productid = stk.productid
        LEFT JOIN seed_master s2 ON s2.SeedID = p.ScionSeedId
        LEFT JOIN krf_growthstages g ON g.projectid = stk.projectid AND g.productid = stk.productid 
        WHERE stk.projectid = ?
          AND stk.sowingbalqty > 0 
          AND LCASE(stk.status) = 'confirmed'
          AND stk.scionsowingdate IS NOT NULL
          AND LCASE(p.IsGrafted) = 'yes'

        ORDER BY sowing_start_date ASC, seedname ASC";

    $stmt = $krfconn->prepare($qry);
    $stmt->bind_param("ii", $projectid, $projectid);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $returnarray[] = $row;
        }
    }
    echo json_encode($returnarray);
    exit;
} else if ($action == "SaveAndUpdateSowingDataForQRCodeGenrate") {

    $data = [
        "companyid" => $_POST['companyid'] ?? '',
        "subcompanyid" => $_POST['subcompanyid'] ?? '',
        "projectid" => $_POST['projectid'] ?? '',
        "stockorder_no" => $_POST['pstockorderno'] ?? '',
        "productid" => $_POST['productid'] ?? '',
        "trayid" => $_POST['trayid'] ?? '',
        "seedid" => $_POST['seedid'] ?? '',
        "lotno" => $_POST['seedlotno'] ?? '',
        "orderqty" => $_POST['orderqty'] ?? '',
        "failurebufferpercent" => $_POST['failurebufferpecent'] ?? '',
        "totalsowingqty" => $_POST['sowingquantity'] ?? '',
        "traycount" => $_POST['traycount'] ?? '',
        "sowingplantid" => $_POST['sowingplantid'] ?? null,
        "roottype" => $_POST['roottype'] ?? '',
        "loginid" => $_POST['loginid'] ?? '',
    ];
    $rrtmsg = saveSowingPlant($krfconn, $data);
    echo json_encode($rrtmsg);
} else if ($action == "UpdateConfirmationSowingData") {
    $data = [
        "companyid" => $_POST['companyid'] ?? '',
        "subcompanyid" => $_POST['subcompanyid'] ?? '',
        "projectid" => $_POST['projectid'] ?? '',
        "stockorderno" => $_POST['stockorderno'] ?? '',
        "lotno" => $_POST['lotno'] ?? '',
        "total_trays" => $_POST['total_trays'] ?? '',
        "confirmed_trays" => $_POST['confirmed_trays'] ?? '',
        "damaged_trays" => $_POST['damaged_trays'] ?? '',
        "loginid" => $_POST['loginid'] ?? '',
    ];
    $rrtmsg = UpdateConfirmationSowingData($krfconn, $data);
    echo json_encode($rrtmsg);
} else if ($action == "RemoveQRCodeofsowingTray") {
    $companyid = $_POST['companyid'] ?? '';
    $subcompanyid = $_POST['subcompanyid'] ?? '';
    $projectid = $_POST['projectid'] ?? '';
    $loginid = $_POST['loginid'] ?? '';
    $stockorder_no = $_POST['stockorderno'] ?? '';
    $qrcode = $_POST['qrcode'] ?? '';
    $updated_at = date('Y-m-d H:i:s');
    $sql = "UPDATE krf_polyhouse_sowingplant_details SET 
            status = 'removed', updated_at = ?, updated_by = ? WHERE projectid = ? AND stockorder_no = ? AND barcode = ?";
    $stmt = $krfconn->prepare($sql);
    if (!$stmt) {
        echo json_encode([
            "status" => "error",
            "message" => $krfconn->error
        ]);
        exit;
    }
    $stmt->bind_param(
        "sssss",
        $updated_at,
        $loginid,
        $projectid,
        $stockorder_no,
        $qrcode
    );
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        echo json_encode([
            "status" => "success",
            "message" => "QR code removed successfully"
        ]);
    } else {
        echo json_encode([
            "status" => "warning",
            "message" => "QR code not found or already removed"
        ]);
    }
} else if ($action == "GetProductionStockOrderDataWithDate") {
    // die();
    $projectid = isset($_POST['projectid']) ? (int) $_POST['projectid'] : 0;
    $dateFrom = $_POST['datefrom'] ?? '';
    $dateTo = $_POST['dateto'] ?? '';
    $sowingdata = [];
    if ($projectid > 0 && !empty($dateFrom) && !empty($dateTo)) {
        $ddshortbyvalue = $dateFrom . "|" . $dateTo;
        $ppdstordata = getSowingScheduleDataFromTable($krfconn, $authconn, $projectid, $dateFrom, $dateTo);
        if (!is_array($ppdstordata) || empty($ppdstordata)) {
            $ppdstordata = getPendingSowingOfPSODataRootyTypewise($krfconn, $projectid, $dateFrom, $dateTo);
        }
        
        // Caches to avoid N+1 queries
        $productCache = [];
        $seedCache = [];
        $locationCache = [];


        if (is_array($ppdstordata) && count($ppdstordata) > 0) {
            foreach ($ppdstordata as $swdkey) {


                $expecteddeliverydate = $swdkey['expected_delivery_date'] ?? '';
                $psono = $swdkey['psono'] ?? '';
                $stockOrderNo = $swdkey['stockorder_no'] ?? '';
                $stockOrderQty = (int) ($swdkey['quantity'] ?? 0);
                $sowingdate = $swdkey['sowing_date'] ?? '';
                $roottype = $swdkey['sowing_type'] ?? '';
                $roottrayid = $swdkey['roottrayid'] ?? '';
                $sciontrayid = $swdkey['sciontrayid'] ?? '';
                $clientname = $swdkey['clientname'] ?? '';
                $createdbyname= $swdkey['createdbyname'] ?? '';
                if (empty($stockOrderNo)) {
                    continue;
                }
                $seedid = null;
                $seedname = null;
                
                // Fetch product with cache
                $pid = $swdkey['productid'];
                if (!isset($productCache[$pid])) {
                    $productCache[$pid] = getProductData($krfconn, $projectid, "ProductID", $pid);
                }
                $prd = $productCache[$pid];

                $productCategoryid = $prd[0]['CategoryId'] ?? '';
                $productRootSeedid = $prd[0]['SeedId'] ?? '';
                $productScionSeedid = $prd[0]['ScionSeedId'] ?? '';
                $productIsGrafted = strtolower($prd[0]['IsGrafted'] ?? '');
                $isGrafted = ($productIsGrafted === 'yes' || $productIsGrafted === 'grafted' || (!empty($productScionSeedid) && !empty($productRootSeedid) && $productRootSeedid != $productScionSeedid)) ? 'yes' : 'no';

                $rootseedname = null;
                $scionseedname = null;

                
                if (!empty($productRootSeedid)) {
                    if (!isset($seedCache[$productRootSeedid])) {
                        $seedCache[$productRootSeedid] = SeedDetailsForProduct($krfconn, $projectid, $productRootSeedid);
                    }
                    $rootseedinfo = $seedCache[$productRootSeedid];
                    if (is_array($rootseedinfo) && count($rootseedinfo) > 0) {
                        $rootseedname = $rootseedinfo['SeedName'] ?? null;
                    }
                }

                if (!empty($productScionSeedid)) {
                    if (!isset($seedCache[$productScionSeedid])) {
                        $seedCache[$productScionSeedid] = SeedDetailsForProduct($krfconn, $projectid, $productScionSeedid);
                    }
                    $scionseedinfo = $seedCache[$productScionSeedid];
                    if (is_array($scionseedinfo) && count($scionseedinfo) > 0) {
                        $scionseedname = $scionseedinfo['SeedName'] ?? null;
                    }
                }

                if ($roottype != "" && strtolower($roottype) == "root") {
                    $seedid = $productRootSeedid;
                    $seedname = $rootseedname;
                } else {
                    $seedid = $productScionSeedid;
                    $seedname = $scionseedname;
                }

                // Tray count rows (across all batches)
                $sowingcount = getSowingRowCount($krfconn, $projectid, $stockOrderNo, $roottype);


                /*$pendingforgerminationbox = pendingsowingtraysforgerminationbox($krfconn, $projectid, $stockOrderNo, $roottype);*/
                $pendingforgerminationbox = pendingsowingtraysforgerminationbox_ajay($krfconn, $projectid, $stockOrderNo, $roottype);

              


                // Tray / lot details (across all batches)
                $lotdetails = getTrayGroupedData($krfconn, $projectid, $stockOrderNo, $roottype);



                // germinationbox details
                $germinatinboxdetails = getGerminationBoxQRCodeDetails($krfconn, $projectid, $roottype, "stockorderno", $stockOrderNo);

                $loc_id = !empty($swdkey['sowinglocationid']) ? $swdkey['sowinglocationid'] : (!empty($swdkey['locationid']) ? $swdkey['locationid'] : '');
                $swlocaiton = productionunitlocationdetails($hrmconn, $swdkey['companyid'], $swdkey['subcompanyid'], $swdkey['projectid'], "LocationId", $loc_id);
                $sowinglocationname = "";
                if (is_array($swlocaiton) && count($swlocaiton) > 0) {
                    $sowinglocationname = $swlocaiton[0]['LocationName'];
                }

                // Defaults
                $totalSowingQty = 0;
                $sowingdoneqty = 0;
                $tray_count = 0;
                $traycavity = 0;
                
                $failurebufferpercent = 0;
                $sowingdate_internal = "";
                if (is_array($lotdetails) && count($lotdetails) > 0) {
                    $traycavity = (int) ($lotdetails[0]['traycavity'] ?? 0);
                    $failurebufferpercent = (float) ($lotdetails[0]['failurebufferpercent'] ?? 0);
                    $totalSowingQty = (float) ($lotdetails[0]['totalsowingqty'] ?? 0);

                    foreach ($lotdetails as $ltdkey) {
                        $sowingdate_internal = $ltdkey['sowingdate_internal'] ?? '';
                        $currentTrayCount = (int) ($ltdkey['tray_count'] ?? 0);
                        $tray_count += $currentTrayCount;
                        $sowingdoneqty += ($traycavity * $currentTrayCount);
                    }
                }

                $laststatus = "";

                // Pending sowing = Order Qty - Done Qty
                $pendingSowingQty = max(0, $totalSowingQty - $sowingdoneqty);

                $sowingdata[] = [
                    'stockorder_no' => $stockOrderNo,
                    'expecteddeliverydate' => $expecteddeliverydate,
                    'psono' => $psono,
                    'orderqty' => $stockOrderQty,
                    'productid' => $swdkey['productid'] ?? null,
                    'product_name' => $swdkey['product_name'] ?? '',
                    'roottype' => $roottype,
                    'isgrafted' => $isGrafted,
                    'productCategoryid' => $productCategoryid,
                    'sowinglocationid' => $swdkey['sowinglocationid'],
                    'sowinglocationname' => $sowinglocationname,
                    'productRootSeedid' => $productRootSeedid,
                    'productScionSeedid' => $productScionSeedid,
                    'rootseedname' => $rootseedname,
                    'scionseedname' => $scionseedname,
                    'sowingdate' => $swdkey['sowing_date'],
                    'seedid' => $seedid,
                    'seedname' => $seedname,
                    'pendingsowingqty' => $pendingSowingQty,
                    'failurebufferpercent' => $failurebufferpercent,
                    'totalsowingqty' => $totalSowingQty,
                    'sowingtraycount' => $sowingcount,
                    'sowingdoneqty' => $sowingdoneqty,
                    'sowingdate_internal' => $sowingdate_internal,
                    'pendingforgerminationbox' => $pendingforgerminationbox,                    
                    'roottrayid' => $roottrayid,
                    'sciontrayid' => $sciontrayid,
                    'traycavity' => $traycavity,
                    'tray_count' => $tray_count,
                    'lotdetails' => $lotdetails,
                    'laststatus' => $laststatus,
                    'germinatinboxdetails' => $germinatinboxdetails,
                    'clientname' => $clientname,
                    'createdbyname' => $createdbyname,

                ];
              
            }
        }
    }

    // Final JSON output
    echo json_encode($sowingdata, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
    exit;

} else if ($action == "sowingschedulewithdates") {
    $projectid = isset($_POST['projectid']) ? (int) $_POST['projectid'] : 0;
    $dateFrom = $_POST['datefrom'] ?? '';
    $dateTo = $_POST['dateto'] ?? '';
    $sowingdata = [];
    if ($projectid > 0 && !empty($dateFrom) && !empty($dateTo)) {
        $ddshortbyvalue = $dateFrom . "|" . $dateTo;
        $ppdstordata = getSowingScheduleDataFromTable($krfconn, $projectid, $dateFrom, $dateTo);
        if (!is_array($ppdstordata) || empty($ppdstordata)) {
            $ppdstordata = getPendingSowingOfPSODataRootyTypewise($krfconn, $projectid, $dateFrom, $dateTo);
        }
        
        // Caches to avoid N+1 queries
        $productCache = [];
        $seedCache = [];
        $locationCache = [];

        if (is_array($ppdstordata) && count($ppdstordata) > 0) {
            foreach ($ppdstordata as $swdkey) {

                $expecteddeliverydate = $swdkey['expected_delivery_date'] ?? '';
                $stockOrderNo = $swdkey['stockorder_no'] ?? '';
                $stockOrderQty = (int) ($swdkey['quantity'] ?? 0);
                $sowingdate = $swdkey['sowing_date'] ?? '';
                $roottype = $swdkey['sowing_type'] ?? '';
                $roottrayid = $swdkey['roottrayid'] ?? '';
                $sciontrayid = $swdkey['sciontrayid'] ?? '';
                if (empty($stockOrderNo)) {
                    continue;
                }
                $seedid = null;
                $seedname = null;
                
                // Fetch product with cache
                $pid = $swdkey['productid'];
                if (!isset($productCache[$pid])) {
                    $productCache[$pid] = getProductData($krfconn, $projectid, "ProductID", $pid);
                }
                $prd = $productCache[$pid];

                $productCategoryid = $prd[0]['CategoryId'] ?? '';
                $productRootSeedid = $prd[0]['SeedId'] ?? '';
                $productScionSeedid = $prd[0]['ScionSeedId'] ?? '';
                $productIsGrafted = strtolower($prd[0]['IsGrafted'] ?? '');
                $isGrafted = ($productIsGrafted === 'yes' || $productIsGrafted === 'grafted' || (!empty($productScionSeedid) && !empty($productRootSeedid) && $productRootSeedid != $productScionSeedid)) ? 'yes' : 'no';

                if($roottype!="" && strtolower($roottype)=="root"){
                    if (!isset($seedCache[$productRootSeedid])) {
                        $seedCache[$productRootSeedid] = SeedDetailsForProduct($krfconn, $projectid, $productRootSeedid);
                    }
                    $rootseedinfo = $seedCache[$productRootSeedid];
                    if (is_array($rootseedinfo) && count($rootseedinfo) > 0) {
                        $seedid = $productRootSeedid;
                        $seedname = $rootseedinfo['SeedName'];
                    }
                }else{
                    if (!isset($seedCache[$productScionSeedid])) {
                        $seedCache[$productScionSeedid] = SeedDetailsForProduct($krfconn, $projectid, $productScionSeedid);
                    }
                    $scionseedinfo = $seedCache[$productScionSeedid];
                    if (is_array($scionseedinfo) && count($scionseedinfo) > 0) {
                        $seedid = $productScionSeedid;
                        $seedname = $scionseedinfo['SeedName'];
                    }
                }

                // Tray count rows (across all batches)
                $sowingcount = getSowingRowCount($krfconn, $projectid, $stockOrderNo, $roottype);
                $pendingforgerminationbox = pendingsowingtraysforgerminationbox($krfconn, $projectid, $stockOrderNo, $roottype);

                // Tray / lot details (across all batches)
                $lotdetails = getTrayGroupedData($krfconn, $projectid, $stockOrderNo, $roottype);

                // germinationbox details
                $germinatinboxdetails = getGerminationBoxQRCodeDetails($krfconn, $projectid, $roottype, "stockorderno", $stockOrderNo);

                $loc_id = !empty($swdkey['sowinglocationid']) ? $swdkey['sowinglocationid'] : (!empty($swdkey['locationid']) ? $swdkey['locationid'] : '');
                $swlocaiton = productionunitlocationdetails($hrmconn, $swdkey['companyid'], $swdkey['subcompanyid'], $swdkey['projectid'], "LocationId", $loc_id);
                $sowinglocationname = "";
                if (is_array($swlocaiton) && count($swlocaiton) > 0) {
                    $sowinglocationname = $swlocaiton[0]['LocationName'];
                }

                // Defaults
                $totalSowingQty = 0;
                $sowingdoneqty = 0;
                $tray_count = 0;
                $traycavity = 0;
                
                $failurebufferpercent = 0;
                $sowingdate_internal = "";
                if (is_array($lotdetails) && count($lotdetails) > 0) {
                    $traycavity = (int) ($lotdetails[0]['traycavity'] ?? 0);
                    $failurebufferpercent = (float) ($lotdetails[0]['failurebufferpercent'] ?? 0);
                    $totalSowingQty = (float) ($lotdetails[0]['totalsowingqty'] ?? 0);

                    foreach ($lotdetails as $ltdkey) {
                        $sowingdate_internal = $ltdkey['sowingdate_internal'] ?? '';
                        $currentTrayCount = (int) ($ltdkey['tray_count'] ?? 0);
                        $tray_count += $currentTrayCount;
                        $sowingdoneqty += ($traycavity * $currentTrayCount);
                    }
                }

                $laststatus = "";

                // Pending sowing = Order Qty - Done Qty
                $pendingSowingQty = max(0, $totalSowingQty - $sowingdoneqty);

                $sowingdata[] = [
                    'stockorder_no' => $stockOrderNo,
                    'expecteddeliverydate' => $expecteddeliverydate,
                    'orderqty' => $stockOrderQty,
                    'productid' => $swdkey['productid'] ?? null,
                    'product_name' => $swdkey['product_name'] ?? '',
                    'roottype' => $roottype,
                    'isgrafted' => $isGrafted,
                    'productCategoryid' => $productCategoryid,
                    'sowinglocationid' => $swdkey['sowinglocationid'],
                    'sowinglocationname' => $sowinglocationname,
                    'productRootSeedid' => $productRootSeedid,
                    'productScionSeedid' => $productScionSeedid,
                    'rootseedname' => $rootseedname,
                    'scionseedname' => $scionseedname,
                    'sowingdate' => $swdkey['sowing_date'],
                    'seedid' => $seedid,
                    'seedname' => $seedname,
                    'pendingsowingqty' => $pendingSowingQty,
                    'failurebufferpercent' => $failurebufferpercent,
                    'totalsowingqty' => $totalSowingQty,
                    'sowingtraycount' => $sowingcount,
                    'sowingdoneqty' => $sowingdoneqty,
                    'sowingdate_internal' => $sowingdate_internal,
                    'pendingforgerminationbox' => $pendingforgerminationbox,                    
                    'roottrayid' => $roottrayid,
                    'sciontrayid' => $sciontrayid,
                    'traycavity' => $traycavity,
                    'tray_count' => $tray_count,
                    'lotdetails' => $lotdetails,
                    'laststatus' => $laststatus,
                    'germinatinboxdetails' => $germinatinboxdetails
                ];
            }
        }
    }

    // Final JSON output
    echo json_encode($sowingdata, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
    exit;

} else if ($action == "getsowinglotdetailswithstockno") {
    $projectid = (int) ($_POST['projectid'] ?? 0);
    $stockOrderNo = $_POST['stockOrderNo'] ?? '';
    $roottype = $_POST['roottype'] ?? '';
    $response = [];
    if ($projectid > 0 && $stockOrderNo !== '') {
        $orders = getFullProductionStockOrdersDetails($krfconn, $projectid, 'stockorder_no', $stockOrderNo);
        if (!empty($orders)) {
            foreach ($orders as $order) {
                $orderQty = (int) ($order['quantity'] ?? 0);
                $sowingtraycount = getSowingRowCount($krfconn, $projectid, $stockOrderNo, $roottype);
                $lotdetails = getTrayGroupedData($krfconn, $projectid, $stockOrderNo, $roottype);
                // print_r($lotdetails);
                if (!is_array($lotdetails))
                    continue;
                foreach ($lotdetails as $lot) {
                    $traycavity = (int) ($lot['traycavity'] ?? 0);
                    $traycount = (int) ($lot['tray_count'] ?? 0);
                    $response[] = [
                        'productid' => $order['productid'] ?? null,
                        'product_name' => $order['product_name'] ?? '',
                        'stockorder_no' => $stockOrderNo,
                        'sowingbatchno' => $lot['sowingbatchno'] ?? '',
                        'expected_delivery_date' => $order['expected_delivery_date'] ?? '',
                        'psono' => $order['psono'] ?? '',
                        // LOT LEVEL DATA (IMPORTANT)
                        'lotno' => $lot['lotno'] ?? '',
                        'tray_count' => $traycount,
                        'traycavity' => $traycavity,
                        'sowinglotno_internal' => $lot['sowinglotno_internal'] ?? '',
                        'sowingdateinternal' => $lot['sowingdate_internal'] ?? '',
                        'sowing_date' => $lot['sowing_date'] ?? '',
                        'roottype' => $roottype ?? '',
                        'tray_details' => $lot['tray_details'] ?? [],
                        // CALCULATED
                        'total_seed_qty' => $traycount * $traycavity,
                        'sowingtraycount' => $sowingtraycount,
                        'dispatchdateinternal' => $lot['dispatchdate_internal'] ?? '',
                        // META
                        'seedid' => $lot['seedid'] ?? null,
                        'seedname' => $lot['seedname'] ?? null,
                        'failurebufferpercent' => (float) ($lot['failurebufferpercent'] ?? 0)
                    ];
                }
            }
        }
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
    exit;
} else if($action=="sowingtrysofgerminationbox"){
    $projectid = $_POST['projectid'] ?? null;
    $stockorderno = $_POST['stockorderno'] ?? null;
    $roottype = $_POST['roottype'] ?? null;
    $gbodqrcode = getGerminatedBoxesQRCodeListByBatch($krfconn, $projectid, $stockorderno, $roottype);
    echo json_encode($gbodqrcode);
} else if($action=="pendingsowingtrysofgerminationbox"){
    $projectid = $_POST['projectid'] ?? null;
    $stockorderno = $_POST['stockorderno'] ?? null;
    $roottype = $_POST['roottype'] ?? null;
    $ppds = pendingsowingtraysforgerminationbox($krfconn, $projectid, $stockorderno, $roottype);
    echo json_encode($ppds);
} else if ($action == "SaveAndUpdateGerminationDataForQRCodeGenrate") {
    $companyid = $_POST['companyid'] ?? '';
    $subcompanyid = $_POST['subcompanyid'] ?? '';
    $projectid = $_POST['projectid'] ?? '';
    $loginid = $_POST['loginid'] ?? '';
    $stockorder_no = $_POST['pstockorderno'] ?? '';
    $ProductId = $_POST['ProductId'] ?? '';
    $ProductName = $_POST['ProductName'] ?? '';
    $traycountinabox = (int) ($_POST['traycountinabox'] ?? 0);
    $countboxqrcodetoprint = (int) ($_POST['countboxqrcodetoprint'] ?? 0);
    $ttltraycountforbox = (int)$traycountinabox * (int)$countboxqrcodetoprint;
    $dateofpacking = $_POST['dateofpacking'] ?? '';
    $dateofopening = $_POST['dateofopening'] ?? '';
    $roottype = $_POST['roottype'] ?? '';


    $response = [
        "status" => "error",
        "message" => "Data not updated",
        "qrcode" => []
    ];

    if (
        empty($projectid) ||
        empty($stockorder_no) ||
        $traycountinabox <= 0 ||
        empty($dateofpacking) ||
        empty($dateofopening)
    ) {
        $response['message'] = "Missing or invalid input data.";
        echo json_encode($response);
        exit;
    }

    /* ðŸ”¢ Fetch sowinglocationid from stockorder */
    $sowinglocationid = "";
    $stockOrderSql = "SELECT sowinglocationid FROM krf_production_stockorder WHERE stockorder_no = ? AND projectid = ?";
    $stockOrderStmt = $krfconn->prepare($stockOrderSql);
    $stockOrderStmt->bind_param("si", $stockorder_no, $projectid);
    $stockOrderStmt->execute();
    $stockOrderResult = $stockOrderStmt->get_result();
    if ($stockOrderRow = $stockOrderResult->fetch_assoc()) {
        $sowinglocationid = $stockOrderRow['sowinglocationid'];
    }

    /* ðŸ”¢ Get total sowing tray count (pending germination box packing only) */
    $ssq = "SELECT sowingplantid, roottype, sowing_date, sowingdate_internal, sowinglotno_internal 
            FROM krf_polyhouse_sowingplant_details 
            WHERE projectid=? 
            AND stockorder_no=? 
            AND roottype=? 
            AND status='Sowing'
            AND workingstatus='SowingInProcess'
            ORDER BY sowingdate_internal ASC, sowingplantid ASC";

    $stmt = $krfconn->prepare($ssq);
    $stmt->bind_param("iss", $projectid, $stockorder_no, $roottype);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        $response['status'] = 'error';
        $response['message'] = "No pending sowing trays found for germination packing.";
        echo json_encode($response);
        exit;
    } else if ($ttltraycountforbox > $result->num_rows) {
        $response['status'] = 'error';
        $response['message'] = "Requested tray quantity exceeds available pending sowing trays.";
        echo json_encode($response);
        exit;
    }
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $sowinglotno_internal = $data[0]['sowinglotno_internal'];
    try {
        $krfconn->begin_transaction();
        $germinated_at = date('Y-m-d H:i:s');
        $todaydate = date('Y-m-d');
        $boxbatchno = generateUniqueBoxBatchNo($krfconn);
        $qrList = [];
        /* ðŸ”¹ INSERT INTO germinated box table */
        $insertBoxSql = "INSERT INTO krf_polyhouse_germinated_boxes (projectid, roottype, stockorderno, productid,productname, qrcode, traycountinbox, boxpackingdate, boxopeningdate, polyhouseid, status, created_by, received_by, sowinglocationid,boxbatchno, boxbatchdate, sowinglotno_internal) VALUES (?,?,?,?,?,?,?,?,?,?,'PendingAllocation',?,?,?,?,?,?)";
        $insertBoxStmt = $krfconn->prepare($insertBoxSql);

        /* ðŸ”¹ PREPARE update statement for individual trays */
        $updateSowingSql = "
            UPDATE krf_polyhouse_sowingplant_details
            SET
                workingstatus = 'GerminationInProcess',
                germination_qrcode = ?,
                packingdate_germbox = ?,
                openingdate_germbox = ?,
                germinated_by = ?,
                germinated_at = ?,
                sowinglocationid = ?
            WHERE sowingplantid = ?
        ";
        $updateSowingStmt = $krfconn->prepare($updateSowingSql);
        if (!$updateSowingStmt) {
            throw new Exception("Failed to prepare sowing details update statement: " . $krfconn->error);
        }

        $receivedby = "";
        for ($i = 0; $i < $countboxqrcodetoprint; $i++) {
            // $germinationcode = $stockorder_no . "_" . generate4DigitFromUUID();
            $germinationcode = generateUniqueQRCode_DB($krfconn, 'krf_polyhouse_germinated_boxes', 'qrcode', 'B');

            // Prevent duplicate QR (extra safety)
            if (in_array($germinationcode, $qrList)) {
                continue;
            }
            $polyhouseid = 0; // not allocated yet
            $insertBoxStmt->bind_param(
                "isssssississssss",
                $projectid,
                $roottype,
                $stockorder_no,
                $ProductId,
                $ProductName,
                $germinationcode,
                $traycountinabox,
                $dateofpacking,
                $dateofopening,
                $polyhouseid,
                $loginid,
                $receivedby,
                $sowinglocationid,
                $boxbatchno,
                $todaydate,
                $sowinglotno_internal
            );

            if (!$insertBoxStmt->execute()) {
                throw new Exception("Failed to insert germinated box");
            }
            $qrList[] = $germinationcode;

            // Update individual trays belonging to this box
            $start_idx = $i * $traycountinabox;
            for ($j = 0; $j < $traycountinabox; $j++) {
                $tray_idx = $start_idx + $j;
                if (isset($data[$tray_idx])) {
                    $sowingplantid = $data[$tray_idx]['sowingplantid'];
                    $updateSowingStmt->bind_param(
                        "ssssssi",
                        $germinationcode,
                        $dateofpacking,
                        $dateofopening,
                        $loginid,
                        $germinated_at,
                        $sowinglocationid,
                        $sowingplantid
                    );
                    if (!$updateSowingStmt->execute()) {
                        throw new Exception("Failed to update sowing tray ID: " . $sowingplantid);
                    }
                }
            }
        }
            // Record update transaction in krf_traymovement_history
            if (function_exists('updateTrayMovementHistory')) {
                updateTrayMovementHistory($krfconn, [
                    'sowingplantid'         => $sowingplantid,
                    'companyid'             => $companyid,
                    'subcompanyid'          => $subcompanyid,
                    'projectid'             => $projectid,
                    'trayid'                => $trayid,
                    'tray_cavity'           => $traycavity,
                    'seedid'                => $seedid,
                    'roottype'              => $roottype,
                    'to_location_id'        => $sowinglocationid,
                    'updated_by'            => $loginid
                ]);
            }
            $krfconn->commit();
        $response = [
            "status" => "success",
            "message" => "Germinated boxes created successfully",
            "qrcode" => $qrList
        ];
    } catch (Exception $e) {
        $krfconn->rollback();
        $response['message'] = $e->getMessage();
    }
    echo json_encode($response);
    exit;
} else if ($action == "saveUpdateGerminatedBoxDetailsToPolyhouse") {
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;
    $loginid = $_POST['loginid'] ?? null;
    $stockorderno = $_POST['stockorderno'] ?? null;
    $gboxtableData = $_POST['gboxtableData'] ?? '';
    $tableData = json_decode($gboxtableData, true);
    if (!is_array($tableData) || empty($tableData)) {
        echo json_encode([
            "status" => "error",
            "message" => "No allocation data received"
        ]);
        exit;
    }
    try {
        $krfconn->begin_transaction();
        foreach ($tableData as $row) {
            $polyhouseid = (int) ($row['polyhouseid'] ?? 0);
            $noofbox = (int) ($row['noofbox'] ?? 0);
            if ($polyhouseid <= 0 || $noofbox <= 0) {
                continue;
            }

            $selectStmt = $krfconn->prepare("SELECT boxid FROM krf_polyhouse_germinated_boxes WHERE stockorderno = ? AND status = 'PendingAllocation' ORDER BY boxid ASC LIMIT {$noofbox}");
            $selectStmt->bind_param("s", $stockorderno);
            $selectStmt->execute();
            $result = $selectStmt->get_result();
            if ($result->num_rows === 0) {
                throw new Exception("No pending boxes available for allocation");
            }
            $updateStmt = $krfconn->prepare("UPDATE krf_polyhouse_germinated_boxes SET polyhouseid = ?, status = 'Allocated' WHERE boxid = ?");
            while ($box = $result->fetch_assoc()) {
                $boxid = (int) $box['boxid'];
                $updateStmt->bind_param("ii", $polyhouseid, $boxid);
                if (!$updateStmt->execute()) {
                    throw new Exception("Failed to allocate box ID: {$boxid}");
                }
            }
        }
        $krfconn->commit();
        echo json_encode([
            "status" => "success",
            "message" => "Polyhouse allocation saved successfully"
        ]);
        exit;
    } catch (Exception $e) {
        $krfconn->rollback();
        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage()
        ]);
        exit;
    }
}

function saveSowingPlant($krfconn, $data)
{
    $krfconn->begin_transaction();
    try {

        /* ==============================
           1. Inputs
        ============================== */
        $companyid = $data['companyid'] ?? '';
        $subcompanyid = $data['subcompanyid'] ?? '';
        $projectid = $data['projectid'] ?? '';
        $stockorder_no = $data['stockorder_no'] ?? '';
        $productid = $data['productid'] ?? '';
        $seedid = $data['seedid'] ?? '';
        $lotno = $data['lotno'] ?? '';
        $orderqty = $data['orderqty'] ?? 0;
        $failurebufferpercent = $data['failurebufferpercent'] ?? 0;
        $totalsowingqty = $data['totalsowingqty'] ?? 0;
        $traycount = $data['traycount'] ?? 0;
        $sowingplantid = $data['sowingplantid'] ?? null;
        $trayid = $data['trayid'] ?? '';
          $roottype = $data['roottype'] ?? null;

       /* commit by ajay on 02-09-2028
       if (strtolower($roottype) == "root") {
            $krfconn->query("update krf_production_stockorder set roottrayid='" . $trayid . "' where stockorder_no='" . $stockorder_no . "'");
        } else {
            $krfconn->query("update krf_production_stockorder set sciontrayid='" . $trayid . "' where stockorder_no='" . $stockorder_no . "'");
        }*/
        $loginid = $data['loginid'] ?? '';
        $today = date("Y-m-d");

        /* ==============================
           2. Validation
        ============================== */
        if (!$projectid)
            throw new Exception("Project ID required");
        if (!$stockorder_no)
            throw new Exception("Stock Order No required");
        if (!$productid)
            throw new Exception("Product ID required");
        if (!$trayid)
            throw new Exception("Tray ID required");

        /* ==============================
           3. Product
        ============================== */
        $prd = getProductData($krfconn, $projectid, "ProductID", $productid);
        if (!$prd)
            throw new Exception("Invalid Product ID");
        $productname = $prd[0]['ProductName'];

        /* ==============================
           4. Tray
        ============================== */
        $tray = getTrayData($krfconn, "tray_id", $trayid);
        if (!$tray)
            throw new Exception("Invalid Tray ID");
        $traycavity = (int) $tray[0]['cavity_count'];
        //add by ajay 08-10-2026
        // $currentPlan=validateSowingSaveAgainstCurrentPlan($krfconn,$data,$traycavity);


        /* ==============================
           5. Stock Order
        ============================== */
        $ps = getFullProductionStockOrdersDetails($krfconn, $projectid, 'stockorder_no', $stockorder_no);
        if (!$ps)
            throw new Exception("Invalid Stock Order No");

        $psono = $ps[0]['psono'];
        $clientid = $ps[0]['clientid'] ?? "";
        $sowinglocationid = $ps[0]['sowinglocationid'] ?? "";
        if ($roottype === "Scion" || $roottype === "scion") {
            $sowingdate = $ps[0]['scionsowingdate'] ?? "";
            // $seedid = 
        } else {
            $sowingdate = $ps[0]['sowingdate'] ?? "";
        }

        // Seed Growing Date
        $seeddata = SeedDetailsForProduct($krfconn, $projectid, $seedid);
        $growingdate = $seeddata['growingdays'] ?? 0;
        $dispatchdate_internal = getModifiedDate($today, $growingdate, 'add');
        
        
        $cl = getClientDetailsInfo($krfconn, $companyid, $subcompanyid, $projectid, "clientid", $clientid);
        $clientname = $cl[0]['client_name'] ?? "";


        /* ==============================
           6. Row count (barcode serial)
        ============================== */
        $currentRows = getSowingRowCount($krfconn, $projectid, $stockorder_no, $roottype);

        $sowingbatchno = generateSowingBatchNo($krfconn, $projectid, $stockorder_no, $productid) ?? '';

        /* ==============================
           7. Defaults
        ============================== */
        $defaults = [
            "sowingbatchno" => "",
            "sowinglocationid" => $sowinglocationid,
            "labourid" => 0,
            "locationid" => $sowinglocationid,
            "polyhouseid" => 0,
            "tray_position" => "",
            "bed_no" => "",
            "row_no" => 0,
            "column_no" => 0,
            "rack_no" => "",
            "floar_no" => "",
            "growth_stage" => "",
            "health_status" => "Healthy",
            "last_inspection_date" => null,
            "last_inspection_remark" => "",
            "inspected_by" => "",
            "status" => "Sowing",
            "workingstatus" => "SowingInProcess",
            "remarks" => ""
        ];

        /* ==============================
           8. UPDATE (single row)
        ============================== */
        if ($sowingplantid) {
            $sql = "UPDATE krf_polyhouse_sowingplant_details SET
                sowing_date=?, sowingbatchno=?, orderqty=?, trayid=?, traycavity=?,
                failurebufferpercent=?, totalsowingqty=?,
                updated_by=?, seedid=?, roottype=?, sowinglocationid=?
                WHERE sowingplantid=?";

            $stmt = $krfconn->prepare($sql);
            $stmt->bind_param(
                "ssssssssssss",
                $sowingdate,
                $sowingbatchno,
                $orderqty,
                $trayid,
                $traycavity,
                $failurebufferpercent,
                $totalsowingqty,
                $loginid,
                $seedid,
                $roottype,
                $sowinglocationid,
                $sowingplantid
            );

            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }

            $krfconn->commit();

            return [
                "status" => "success",
                "message" => "Updated Successfully"
            ];
        }

        /* ==============================
           9. INSERT (loop)
        ============================== */


        $sql = "INSERT INTO krf_polyhouse_sowingplant_details (
            sowing_date, sowingbatchno, projectid, psono, stockorder_no, orderqty,
            productid, productname, lotno, barcode,
            trayid, traycavity,
            labourid, locationid, polyhouseid, tray_position, bed_no,
            row_no, column_no, rack_no, floar_no, growth_stage,
            health_status, last_inspection_date, last_inspection_remark,
            inspected_by, status, workingstatus, remarks,
            created_by, failurebufferpercent, totalsowingqty, seedid, roottype, sowinglocationid, sowingdate_internal, dispatchdate_internal,sowinglotno_internal
        ) VALUES (
            ?,?, ?,?,?,?, ?,?,?,?,
            ?,?,
            ?,?,?,?,?,
            ?,?,?,?,?,
            ?,?,?,?,
            ?,?,?,?,
            ?,?,?,?,?,?,?,?
        )";

        $stmt = $krfconn->prepare($sql);
        $results = [];
        // Generate 3 distinct uppercase letters (A-Z) with database uniqueness check
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        do {
            $alphaPrefix = substr(str_shuffle($alphabet), 0, 3);
            $checkStmt = $krfconn->prepare("SELECT COUNT(*) FROM krf_polyhouse_sowingplant_details WHERE barcode LIKE ?");
            $searchLike = $alphaPrefix . '%';
            $checkStmt->bind_param("s", $searchLike);
            $checkStmt->execute();
            $checkStmt->bind_result($existsCount);
            $checkStmt->fetch();
            $checkStmt->close();
        } while ($existsCount > 0);

        $sowinglotno_internal = $productid.$roottype.date('Ymd');
        
        $i = 1;
        while ($i <= $traycount) {
            $barcode = $alphaPrefix . str_pad($i, 3, '0', STR_PAD_LEFT);
            $sowingdate_internal = date('Y-m-d');
            $stmt->bind_param(
                "ssssssssssssssssssssssssssssssdsssssss",
                $sowingdate,
                $sowingbatchno,
                $projectid,
                $psono,
                $stockorder_no,
                $orderqty,
                $productid,
                $productname,
                $lotno,
                $barcode,
                $trayid,
                $traycavity,
                $defaults['labourid'],
                $defaults['locationid'],
                $defaults['polyhouseid'],
                $defaults['tray_position'],
                $defaults['bed_no'],
                $defaults['row_no'],
                $defaults['column_no'],
                $defaults['rack_no'],
                $defaults['floar_no'],
                $defaults['growth_stage'],
                $defaults['health_status'],
                $defaults['last_inspection_date'],
                $defaults['last_inspection_remark'],
                $defaults['inspected_by'],
                $defaults['status'],
                $defaults['workingstatus'],
                $defaults['remarks'],
                $loginid,
                $failurebufferpercent,
                $totalsowingqty,
                $seedid,
                $roottype,
                $sowinglocationid,
                $sowingdate_internal,
                $dispatchdate_internal,
                $sowinglotno_internal
            );

            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }

            $inserted_id = $stmt->insert_id;
            $results[] = [
                "id" => $inserted_id,
                "barcode" => $barcode
            ];

            // Log initial tray transaction into krf_traymovement_history
            /*if (function_exists('insertTrayMovementHistory')) {
                insertTrayMovementHistory($krfconn, [
                    'transaction_no'        => 'TRX-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6)),
                    'companyid'             => $companyid,
                    'subcompanyid'          => $subcompanyid,
                    'projectid'             => $projectid,
                    'psono'                 => $psono,
                    'stockorder_no'         => $stockorder_no,
                    'clientid'              => $clientid,
                    'clientname'            => $clientname,
                    'productid'             => $productid,
                    'productname'           => $productname,
                    'activity_type'         => 'SOWING_INITIATED',
                    'sowingplantid'         => $inserted_id,
                    'barcode'               => $barcode,
                    'sowingbatchno'         => $sowingbatchno,
                    'sowinglotno_internal'  => $sowinglotno_internal,
                    'seedid'                => $seedid,
                    'seedname'              => $seeddata['seedname'] ?? '',
                    'seed_lotno'            => $lotno,
                    'trayid'                => $trayid,
                    'tray_cavity'           => $traycavity,
                    'roottype'              => $roottype,
                    'to_location_id'        => $sowinglocationid,
                    'to_polyhouse_id'       => $defaults['polyhouseid'],
                    'to_bed_no'             => $defaults['bed_no'],
                    'to_row_no'             => $defaults['row_no'],
                    'to_column_no'          => $defaults['column_no'],
                    'to_rack_no'            => $defaults['rack_no'],
                    'to_floor_no'           => $defaults['floar_no'],
                    'previous_status'       => 'New',
                    'current_status'        => $defaults['status'],
                    'growth_stage'          => $defaults['growth_stage'],
                    'health_status'         => $defaults['health_status'],
                    'reason_for_movement'   => 'Initial Sowing Tray Registered',
                    'movement_date'         => $sowingdate_internal,
                    'movement_time'         => date('H:i:s'),
                    'created_by'            => $loginid
                ]);
            }*/
            $i++;
        }


        //ajay
        $sql = "  SELECT   COALESCE(SUM(traycavity), 0) AS totalqty, (?-COALESCE(SUM(traycavity), 0) ) AS balqty  FROM krf_polyhouse_sowingplant_details WHERE status IN ('Sowing', 'Germination') AND roottype = ?   AND projectid = ?  AND stockorder_no = ?   AND productid = ?   ";
            $stmt = $krfconn->prepare($sql);
            $stmt->bind_param( "isiss",
                $totalsowingqty, 
                $roottype,
                $projectid,
                $stockorder_no,
                $productid
            );
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $totalqty = (int)($row['totalqty'] ?? 0);
            $balqty   = (int)($row['balqty'] ?? 0);

            $stmt->close();
            $status = "pending";
            $status_sch = "pending";
            if ($totalqty > 0 && $balqty <= 0) {
                $status = "done";
                $status_sch = "done";
            } elseif ($totalqty > 0) {
                $status = "start";
                $status_sch = "inprogress";
            }
        if (strtolower($roottype) == "root") { 
            $krfconn->query("update krf_production_stockorder set roottrayid='".$trayid."' , sowingstatus = '".$status."' where   stockorder_no='".$stockorder_no."' and productid = '".$productid."' ");

            $krfconn->query("update krf_polyhouse_sowingschedule set   status = '".$status_sch."' where roottype = '".$roottype."' and stockorder_no='".$stockorder_no."' and productid = '".$productid."' ");
            
        } else { 

            $krfconn->query("update krf_production_stockorder set  sowingstatus = '".$status."' where   stockorder_no='".$stockorder_no."' and productid = '".$productid."' ");
            $krfconn->query("update krf_polyhouse_sowingschedule set   status = '".$status_sch."' where roottype = '".$roottype."' and  stockorder_no='".$stockorder_no."' and productid = '".$productid."' ");
        }
        //ajay

        $krfconn->commit();
        return [
            "status" => "success",
            "message" => "Inserted Successfully",
            "rows" => $results,
            "stockorderNo" => $stockorder_no,
            "clientName" => $clientname,
            "lotno" => $lotno,
            "productname" => $productname,
            "sowingdate" => $sowingdate,
            "sowingbatchno" => $sowingbatchno,
            "sowinglotno_internal" => $sowinglotno_internal
        ];
    } catch (Exception $e) {
        $krfconn->rollback();
        return [
            "status" => "error",
            "message" => $e->getMessage()
        ];
    }
}

function UpdateConfirmationSowingData($krfconn, $data)
{
    $krfconn->begin_transaction();
    try {

        $projectid = $data['projectid'] ?? '';
        $stockorderno = $data['stockorderno'] ?? '';
        $lotno = $data['lotno'] ?? '';
        $damaged_trays = (int) ($data['damaged_trays'] ?? 0);
        $loginid = $data['loginid'] ?? '';

        if ($damaged_trays <= 0) {
            throw new Exception("Invalid damaged tray count");
        }

        /**
         * STEP 1ï¸âƒ£ : Get tray IDs to mark as damaged
         * Ordered DESC (latest first)
         */
        $selectSql = " SELECT sowingplantid, productid,roottype  FROM krf_polyhouse_sowingplant_details WHERE projectid = ? AND stockorder_no = ? AND lotno = ? AND status <> 'UnConfirmSowing' ORDER BY sowingplantid DESC LIMIT ? ";
        $selectStmt = $krfconn->prepare($selectSql);
        if (!$selectStmt) {
            throw new Exception($krfconn->error);
        }
        $selectStmt->bind_param("sssi", $projectid, $stockorderno, $lotno, $damaged_trays);
        $selectStmt->execute();
        $result = $selectStmt->get_result();
        if ($result->num_rows === 0) {
            throw new Exception("No trays found to mark as damaged");
        }
        $ids = [];
        $productid=null;
        $roottype =null;
        while ($row = $result->fetch_assoc()) {
            $ids[] = $row['sowingplantid'];
            $productid=$row['productid'];
            $roottype=$row['roottype'];
        }
        $selectStmt->close();
        $idList = implode(",", array_map('intval', $ids));
        $updateSql = "UPDATE krf_polyhouse_sowingplant_details SET status = 'UnConfirmSowing', updated_by = ?, updated_at = NOW() WHERE sowingplantid IN ($idList)";
        $updateStmt = $krfconn->prepare($updateSql);
        if (!$updateStmt) {
            throw new Exception($krfconn->error);
        }
        $updateStmt->bind_param("s", $loginid);
        if (!$updateStmt->execute()) {
            throw new Exception($updateStmt->error);
        }
        $updateStmt->close();



        //ajay 
        $sql = "  SELECT   COALESCE(SUM(traycavity), 0) AS totalqty, (totalsowingqty-COALESCE(SUM(traycavity), 0) ) AS balqty  FROM krf_polyhouse_sowingplant_details WHERE status IN ('Sowing', 'Germination') AND roottype = ?   AND projectid = ?  AND stockorder_no = ?   AND productid = ?   ";
            $stmt = $krfconn->prepare($sql);
            $stmt->bind_param( "siss",
                $roottype,
                $projectid,
                $stockorderno,
                $productid
            );
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $totalqty = (int)($row['totalqty'] ?? 0);
            $balqty   = (int)($row['balqty'] ?? 0);

            $stmt->close();
            $status = "pending";
            $status_sch = "pending";
            if ($totalqty > 0 && $balqty <= 0) {
                $status = "done";
                $status_sch = "done";
            } elseif ($totalqty > 0) {
                $status = "start";
                $status_sch = "inprogress";
            }
        if (strtolower($roottype) == "root") { 
            $krfconn->query("update krf_production_stockorder set   sowingstatus = '".$status."' where   stockorder_no='".$stockorderno."' and productid = '".$productid."' ");

            $krfconn->query("update krf_polyhouse_sowingschedule set   status = '".$status_sch."' where roottype = '".$roottype."' and stockorder_no='".$stockorderno."' and productid = '".$productid."' ");
            
        } else { 

            $krfconn->query("update krf_production_stockorder set  sowingstatus = '".$status."' where   stockorder_no='".$stockorderno."' and productid = '".$productid."' ");
            $krfconn->query("update krf_polyhouse_sowingschedule set   status = '".$status_sch."' where roottype = '".$roottype."' and  stockorder_no='".$stockorderno."' and productid = '".$productid."' ");
        }
        //ajay
        $krfconn->commit();
        return [
            "status" => "success",
            "message" => count($ids) . " trays marked as damaged successfully"
        ];

    } catch (Exception $e) {
        $krfconn->rollback();
        return [
            "status" => "error",
            "message" => $e->getMessage()
        ];
    }
}


function generateSowingBatchNo($conn, $projectid, $stockorder_no, $productid)
{
    // Search the database for the maximum existing 6-digit numeric batch number
    $sql = "SELECT MAX(CAST(sowingbatchno AS UNSIGNED)) AS max_batch 
            FROM krf_polyhouse_sowingplant_details 
            WHERE sowingbatchno REGEXP '^[0-9]{6}$'";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $max_batch = (int) $row['max_batch'];
            if ($max_batch >= 100000 && $max_batch < 999999) {
                $next_batch = $max_batch + 1;
                $stmt->close();
                return (string)$next_batch;
            }
        }
        $stmt->close();
    }
    
    // If no 6-digit batch number exists yet, start at 100001
    return "100001";
}




?>
