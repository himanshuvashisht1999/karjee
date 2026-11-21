<?php
error_reporting(E_ALL);
date_default_timezone_set('Asia/Kolkata');
require_once('../../connection.php');
require('../../loginCell/functions/login_functions.php');
include("../../whatsappnotification/whatsappsmsfunctions.php");
include("../functions/orders_functions.php");
include("../functions/product_functions.php");
include("../functions/seed_functions.php");
include("../functions/labour_functions.php");
function checkStockOrderSowingStatus($krfconn, $stockorder_no, $projectid) {
    // 1. Fetch product details and stockorder details
    $stmt_stk = $krfconn->prepare("
        SELECT s.stockorder_no, s.projectid, s.productid, s.product_name, s.sowingdate, s.expected_delivery_date,
               s.sowingstatus, s.scionsowingstatus,
               pm.IsGrafted, pm.SeedId, pm.ScionSeedId
        FROM krf_production_stockorder s
        LEFT JOIN product_master pm ON s.productid = pm.ProductID
        WHERE s.stockorder_no = ? AND s.projectid = ?
        LIMIT 1
    ");
    if (!$stmt_stk) {
        return [
            'exists' => false,
            'is_grafted' => false,
            'sowing_started' => false,
            'root_sowed_cnt' => 0,
            'scion_sowed_cnt' => 0,
            'total_sowed_cnt' => 0,
            'reason' => 'Database error preparing stock order check'
        ];
    }
    $stmt_stk->bind_param("si", $stockorder_no, $projectid);
    $stmt_stk->execute();
    $stk = $stmt_stk->get_result()->fetch_assoc();
    $stmt_stk->close();

    if (!$stk) {
        return [
            'exists' => false,
            'is_grafted' => false,
            'sowing_started' => false,
            'root_sowed_cnt' => 0,
            'scion_sowed_cnt' => 0,
            'total_sowed_cnt' => 0,
            'reason' => 'Stock order not found'
        ];
    }

    $is_grafted = (
        (!empty($stk['IsGrafted']) && in_array(strtolower(trim($stk['IsGrafted'])), ['yes', 'grafted'])) ||
        (!empty($stk['ScionSeedId']) && trim($stk['ScionSeedId']) != '0' && trim($stk['ScionSeedId']) != trim($stk['SeedId'])) ||
        (stripos($stk['product_name'], 'graft') !== false && stripos($stk['product_name'], 'non') === false)
    );

    // Check Root sowing
    $stmt_root = $krfconn->prepare("
        SELECT COUNT(*) AS root_cnt 
        FROM krf_polyhouse_sowingplant_details 
        WHERE stockorder_no = ? AND projectid = ? 
          AND roottype = 'Root' 
          AND sowingdate_internal IS NOT NULL AND sowingdate_internal > '1970-01-01'
          AND (tedit IS NULL OR tedit = '')
    ");
    $stmt_root->bind_param("si", $stockorder_no, $projectid);
    $stmt_root->execute();
    $root_cnt = (int)($stmt_root->get_result()->fetch_assoc()['root_cnt'] ?? 0);
    $stmt_root->close();

    // Check Scion sowing
    $stmt_scion = $krfconn->prepare("
        SELECT COUNT(*) AS scion_cnt 
        FROM krf_polyhouse_sowingplant_details 
        WHERE stockorder_no = ? AND projectid = ? 
          AND roottype = 'Scion' 
          AND sowingdate_internal IS NOT NULL AND sowingdate_internal > '1970-01-01'
          AND (tedit IS NULL OR tedit = '')
    ");
    $stmt_scion->bind_param("si", $stockorder_no, $projectid);
    $stmt_scion->execute();
    $scion_cnt = (int)($stmt_scion->get_result()->fetch_assoc()['scion_cnt'] ?? 0);
    $stmt_scion->close();

    // Also check generic / non-grafted sowing count
    $stmt_all = $krfconn->prepare("
        SELECT COUNT(*) AS all_cnt 
        FROM krf_polyhouse_sowingplant_details 
        WHERE stockorder_no = ? AND projectid = ? 
          AND sowingdate_internal IS NOT NULL AND sowingdate_internal > '1970-01-01'
          AND (tedit IS NULL OR tedit = '')
    ");
    $stmt_all->bind_param("si", $stockorder_no, $projectid);
    $stmt_all->execute();
    $all_cnt = (int)($stmt_all->get_result()->fetch_assoc()['all_cnt'] ?? 0);
    $stmt_all->close();

    $root_status_done = (strtolower(trim($stk['sowingstatus'] ?? '')) === 'done');
    $scion_status_done = (strtolower(trim($stk['scionsowingstatus'] ?? '')) === 'done');

    $root_started = ($root_cnt > 0 || $root_status_done);
    $scion_started = ($scion_cnt > 0 || $scion_status_done);

    $sowing_started = false;
    $reason = '';

    if ($is_grafted) {
        if ($root_started && $scion_started) {
            $sowing_started = true;
            $reason = "Sowing has already started for BOTH Root ($root_cnt trays) and Scion ($scion_cnt trays)";
        } else if ($root_started) {
            $sowing_started = true;
            $reason = "Root sowing has already started" . ($root_cnt > 0 ? " ($root_cnt trays sowed)" : " (Root status: done)");
        } else if ($scion_started) {
            $sowing_started = true;
            $reason = "Scion sowing has already started" . ($scion_cnt > 0 ? " ($scion_cnt trays sowed)" : " (Scion status: done)");
        }
    } else {
        if ($all_cnt > 0 || $root_status_done) {
            $sowing_started = true;
            $reason = "Sowing has already started for this order" . ($all_cnt > 0 ? " ($all_cnt trays sowed)" : " (status: done)");
        }
    }

    return [
        'exists' => true,
        'stockorder_no' => $stockorder_no,
        'productid' => $stk['productid'],
        'product_name' => $stk['product_name'],
        'sowingdate' => $stk['sowingdate'],
        'expected_delivery_date' => $stk['expected_delivery_date'],
        'is_grafted' => $is_grafted,
        'root_started' => $root_started,
        'scion_started' => $scion_started,
        'root_sowed_cnt' => $root_cnt,
        'scion_sowed_cnt' => $scion_cnt,
        'total_sowed_cnt' => $all_cnt,
        'sowing_started' => $sowing_started,
        'reason' => $reason
    ];
}
$action = $_GET['action'];
if ($action == "ProductListFrommaster") {
    $projectid = $_POST['projectid'] ?? 0;
    $pcd = getProductData($krfconn, $projectid, "all", "all");
    echo json_encode($pcd);
} else if ($action == "GetClientLastPurchaseHistory") {
    $clientid = trim($_POST['clientid'] ?? '');
    $mobileno = trim($_POST['mobileno'] ?? '');
    $productid = trim($_POST['productid'] ?? '');
    $projectid = trim($_POST['projectid'] ?? '');

    if (empty($productid) || (empty($clientid) && empty($mobileno))) {
        echo json_encode(["status" => "empty", "data" => null]);
        exit;
    }

    $query = "
        SELECT 
            soi.rate,
            soi.quantity,
            soi.sono AS order_no,
            so.sono AS sono,
            so.psono AS psono,
            DATE_FORMAT(so.created_at, '%d-%b-%Y') AS order_date,
            'Final Sale Order' AS order_type,
            so.created_at AS order_datetime,
            soi.product_name
        FROM krf_sales_order_items soi
        JOIN krf_sales_order so ON soi.sono = so.sono
        WHERE (soi.clientid = ? OR (so.mobileno = ? AND ? != ''))
          AND soi.product_id = ?
          AND soi.rate > 0
        ORDER BY so.created_at DESC
        LIMIT 3
    ";

    $stmt = $krfconn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("ssss", $clientid, $mobileno, $mobileno, $productid);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
        if (!empty($rows)) {
            echo json_encode(["status" => "success", "data" => $rows]);
        } else {
            echo json_encode(["status" => "not_found", "data" => []]);
        }
        $stmt->close();
    } else {
        echo json_encode(["status" => "error", "message" => $krfconn->error]);
    }
} else if ($action == "getClientProductDiscount" || $action == "getClientDiscountDetails" || $action == "getClientDiscount") {
    $clientid = trim($_POST['clientid'] ?? $_POST['client_id'] ?? $_GET['clientid'] ?? $_GET['client_id'] ?? '');
    $mobileno = trim($_POST['mobileno'] ?? $_POST['phone'] ?? $_POST['phones'] ?? $_GET['mobileno'] ?? '');
    $productid = trim($_POST['productid'] ?? $_POST['product_id'] ?? $_GET['productid'] ?? $_GET['product_id'] ?? '');
    $quantity = floatval($_POST['quantity'] ?? $_POST['qty'] ?? $_GET['quantity'] ?? 0);
    $price = floatval($_POST['price'] ?? $_POST['rate'] ?? $_GET['price'] ?? 0);

    if (empty($clientid) && empty($mobileno)) {
        echo json_encode([
            "status" => "error",
            "message" => "Client ID or Mobile Number is required.",
            "has_discount" => false
        ]);
        exit;
    }

    // Fetch client's discountpercent from krf_client_master
    $clientStmt = null;
    if (!empty($clientid)) {
        $clientStmt = $krfconn->prepare("SELECT client_id, client_name, phones, discountpercent FROM krf_client_master WHERE client_id = ? LIMIT 1");
        $clientStmt->bind_param("s", $clientid);
    } else {
        $clientStmt = $krfconn->prepare("SELECT client_id, client_name, phones, discountpercent FROM krf_client_master WHERE phones = ? LIMIT 1");
        $clientStmt->bind_param("s", $mobileno);
    }

    $clientData = null;
    if ($clientStmt) {
        $clientStmt->execute();
        $clientData = $clientStmt->get_result()->fetch_assoc();
        $clientStmt->close();
    }

    if (!$clientData) {
        echo json_encode([
            "status" => "error",
            "message" => "Client not found in database.",
            "has_discount" => false
        ]);
        exit;
    }

    $rawDiscount = trim($clientData['discountpercent'] ?? '');
    $discountArray = null;

    if (!empty($rawDiscount)) {
        if (is_string($rawDiscount) && substr($rawDiscount, 0, 1) === '{') {
            $discountArray = json_decode($rawDiscount, true);
        } else if (is_numeric($rawDiscount) && floatval($rawDiscount) > 0) {
            // Legacy flat percentage discount fallback
            $discountArray = [
                "nongrafting" => [
                    "enabled" => 1,
                    "applicable" => "everyorder",
                    "type" => "percent",
                    "percent" => floatval($rawDiscount),
                    "amount" => 0,
                    "slabs" => []
                ],
                "grafting" => [
                    "enabled" => 1,
                    "applicable" => "everyorder",
                    "type" => "percent",
                    "percent" => floatval($rawDiscount),
                    "amount" => 0,
                    "slabs" => []
                ]
            ];
        }
    }

    if (!$discountArray || !is_array($discountArray)) {
        echo json_encode([
            "status" => "success",
            "has_discount" => false,
            "message" => "No discount policy configured for this client.",
            "client_id" => $clientData['client_id'],
            "client_name" => $clientData['client_name'],
            "discount_percent" => 0,
            "discount_amount" => 0,
            "discount_rate" => 0,
            "discount_type" => "none"
        ]);
        exit;
    }

    // Determine whether product is Grafted or Non-Grafted
    $isGrafted = false;
    $productInfo = null;

    if (!empty($productid)) {
        $prodStmt = $krfconn->prepare("SELECT ProductID, ProductName, sku, IsGrafted, PriceOfProduct FROM product_master WHERE ProductID = ? LIMIT 1");
        if ($prodStmt) {
            $prodStmt->bind_param("s", $productid);
            $prodStmt->execute();
            $productInfo = $prodStmt->get_result()->fetch_assoc();
            $prodStmt->close();
        }

        if ($productInfo) {
            $isGraftedField = strtolower(trim($productInfo['IsGrafted'] ?? ''));
            $prodName = strtolower(trim($productInfo['ProductName'] ?? ''));
            $sku = strtolower(trim($productInfo['sku'] ?? ''));

            if (strpos($isGraftedField, 'nongraft') !== false || strpos($prodName, 'nongraft') !== false || strpos($sku, 'nongraft') !== false) {
                $isGrafted = false;
            } else if (strpos($isGraftedField, 'graft') !== false || strpos($prodName, 'graft') !== false || strpos($sku, 'graft') !== false) {
                $isGrafted = true;
            } else {
                $isGrafted = false;
            }
        }
    } else {
        // Check if explicit is_grafted or plant_type parameter is passed
        $plantTypeParam = strtolower(trim($_POST['plant_type'] ?? $_GET['plant_type'] ?? ''));
        $isGraftedParam = $_POST['is_grafted'] ?? $_GET['is_grafted'] ?? null;

        if ($plantTypeParam === 'grafting' || $plantTypeParam === 'grafted' || $isGraftedParam === '1' || $isGraftedParam === 'true' || $isGraftedParam === true) {
            $isGrafted = true;
        } else {
            $isGrafted = false;
        }
    }

    $plantCategoryKey = $isGrafted ? 'grafting' : 'nongrafting';
    $policy = $discountArray[$plantCategoryKey] ?? null;

    // Evaluate policy
    $hasDiscount = false;
    $discType = 'none';
    $discPercent = 0.0;
    $discAmount = 0.0;
    $discRate = 0.0;
    $calculatedUnitDiscount = 0.0;
    $matchedSlab = null;

    if ($policy && !empty($policy['enabled']) && ($policy['enabled'] == 1 || $policy['enabled'] === true || $policy['enabled'] === '1')) {
        $applicable = trim($policy['applicable'] ?? 'none');

        if ($applicable === 'everyorder') {
            $hasDiscount = true;
            $discType = $policy['type'] ?? 'rate';
            $discPercent = floatval($policy['percent'] ?? 0);
            $discAmount = floatval($policy['amount'] ?? 0);

            if ($discType === 'percent') {
                $calculatedUnitDiscount = ($price > 0 && $discPercent > 0) ? ($price * ($discPercent / 100)) : 0;
            } else if ($discType === 'rate') {
                $discRate = $discAmount;
                $calculatedUnitDiscount = $discAmount;
            } else if ($discType === 'fixed') {
                $calculatedUnitDiscount = $discAmount;
            }
        } else if ($applicable === 'asperplane') {
            $slabs = $policy['slabs'] ?? [];
            if (is_array($slabs) && count($slabs) > 0) {
                if ($quantity > 0) {
                    foreach ($slabs as $slab) {
                        $min = floatval($slab['min_qty'] ?? 0);
                        $max = floatval($slab['max_qty'] ?? 0);
                        if ($max <= 0) $max = PHP_INT_MAX;

                        if ($quantity >= $min && $quantity <= $max) {
                            $hasDiscount = true;
                            $matchedSlab = $slab;
                            $discType = $slab['type'] ?? 'rate';
                            $discPercent = floatval($slab['percent'] ?? 0);
                            $discAmount = floatval($slab['amount'] ?? 0);

                            if ($discType === 'percent') {
                                $calculatedUnitDiscount = ($price > 0 && $discPercent > 0) ? ($price * ($discPercent / 100)) : 0;
                            } else if ($discType === 'rate') {
                                $discRate = $discAmount;
                                $calculatedUnitDiscount = $discAmount;
                            } else if ($discType === 'fixed') {
                                $calculatedUnitDiscount = $discAmount;
                            }
                            break;
                        }
                    }
                } else {
                    // No quantity specified yet, but client has slabs available
                    $hasDiscount = true;
                    $discType = $policy['type'] ?? 'rate';
                }
            }
        }
    }

    echo json_encode([
        "status" => "success",
        "has_discount" => $hasDiscount,
        "client_id" => $clientData['client_id'],
        "client_name" => $clientData['client_name'],
        "product_id" => $productid,
        "product_name" => $productInfo['ProductName'] ?? '',
        "is_grafted" => $isGrafted,
        "plant_type" => $plantCategoryKey,
        "policy_enabled" => ($policy && !empty($policy['enabled'])) ? 1 : 0,
        "applicable_rule" => $policy['applicable'] ?? 'none',
        "discount_type" => $discType,
        "discount_percent" => $discPercent,
        "discount_amount" => $discAmount,
        "discount_rate" => ($discType === 'rate' ? $discAmount : $discRate),
        "calculated_unit_discount" => round($calculatedUnitDiscount, 2),
        "matched_slab" => $matchedSlab,
        "active_policy" => $policy,
        "all_policies" => $discountArray
    ]);
    exit;
} else if ($action == "GetFullProductListFrommaster") {
    $projectid = $_POST['projectid'] ?? 0;
    $shortby = $_POST['shortby'] ?? 'all';
    $shortbyvalue = $_POST['shortbyvalue'] ?? 'all';
    $pcd = getProductData($krfconn, $projectid, $shortby, $shortbyvalue);
    // print_r($pcd);
    $fulldetails = [];
    if (count($pcd) > 0) {
        foreach ($pcd as $pcdkey) {
            // categoryData
            $pCatd = $procatelist = getPlantCategoryData($krfconn, $projectid, "CategoryID", $pcdkey['CategoryId']);

            // Crop Details
            $sbcatData = getSubCategoryData($krfconn, $projectid, "SubCategoryID", $pcdkey['ProductTypeID']);

            // RootSeed Detail
            $shortBy = ['SeedID' => $pcdkey['SeedId']];
            $rootseeddt = getSeedData($krfconn, $shortBy, 'SeedID', "ASC");

            // ScionSeed Detail
            $shortBy = ['SeedID' => $pcdkey['ScionSeedId']];
            $scionseeddt = getSeedData($krfconn, $shortBy, 'SeedID', "ASC");
            // print_r($scionseeddt);
            // die();
            // ScionSeed Detail
            $fullsycledays = "";
            $productGrowthid = "";
            $prdG = getProductGrowthStages($krfconn, $projectid, "gs.id", (int) $pcdkey['ProductGrowthId']);
            $prdGrths = $prdG['data'];
            // print_r($prdGrths);
            if ($pcdkey['ProductGrowthId'] != "" && count($prdGrths) > 0) {
                $fullsycledays = $prdGrths[0]['fullcycledays'];
                $productGrowthid = $prdGrths[0]['id'];
            }
            // growingdays
            $fulldetails[] = ['Categoryid' => $pcdkey['CategoryId'], 'CategoryName' => $pCatd[0]['CategoryName'], "CategorySKU" => $pCatd[0]['sku'], "Categoryhsncode" => $pCatd[0]['HSNCode'], 'CropId' => $pcdkey['ProductTypeID'], 'CropName' => $sbcatData[0]['SubCategoryName'], 'Cropsku' => $sbcatData[0]['sku'], 'ProductId' => $pcdkey['ProductID'], 'ProductName' => $pcdkey['ProductName'], 'ProductSKU' => $pcdkey['sku'], 'IsGrafted' => $pcdkey['IsGrafted'], 'RootSeedId' => $pcdkey['SeedId'], 'PriceOfProduct' => $pcdkey['PriceOfProduct'], 'RootSeedgrowingdays' => $rootseeddt[0]['growingdays'], 'RootSeedName' => $rootseeddt[0]['SeedName'], 'RootSeedType' => $rootseeddt[0]['TypeOfSeed'], 'RootSeedVariety' => $rootseeddt[0]['VarietyofSeed'], 'RootSeedBrandName' => $rootseeddt[0]['BrandName'], 'RootSeedCompanyName' => $rootseeddt[0]['CompanyName'], 'RootSeedVendorId' => $rootseeddt[0]['VendorID'], 'RootSeedWeightPerPackate' => $rootseeddt[0]['WeightPerPacket'], 'RootSeedCountPerPackate' => $rootseeddt[0]['CountPerPackate'], 'ScionSeedId' => $pcdkey['SeedId'], 'ScionSeedgrowingdays' => $scionseeddt[0]['growingdays'], 'ScionSeedName' => $scionseeddt[0]['SeedName'], 'ScionSeedType' => $scionseeddt[0]['TypeOfSeed'], 'ScionSeedVariety' => $scionseeddt[0]['VarietyofSeed'], 'ScionSeedBrandName' => $rootseeddt[0]['BrandName'], 'ScionSeedCompanyName' => $scionseeddt[0]['CompanyName'], 'ScionSeedVendorId' => $scionseeddt[0]['VendorID'], 'ScionSeedWeightPerPackate' => $scionseeddt[0]['WeightPerPacket'], 'ScionSeedCountPerPackate' => $scionseeddt[0]['CountPerPackate'], 'productGrowthid' => $productGrowthid, 'fullsycledays' => $fullsycledays, 'productGrowthData' => $prdGrths];
        }
    }
    echo json_encode($fulldetails);
} else if ($action == "ProductCategoryFrommaster") {
    $projectid = $_POST['projectid'] ?? 0;
    $procatelist = getPlantCategoryData($krfconn, $projectid, "all");
    echo json_encode($procatelist);
} else if ($action == "GetSowingDateForProduct") {
    $projectid = $_POST['projectid'] ?? 0;
    $productid = $_POST['productid'] ?? 0;
    $deliverydate = $_POST['deliverydate'] ?? '';

    if (empty($productid) || empty($deliverydate)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing productid or deliverydate']);
        exit;
    }

    $calcRes = calculateSowingAndInternalDispatchDates($krfconn, $deliverydate, $projectid, $productid);
    echo json_encode($calcRes);
    exit;
} else if ($action == "GetProductionStockOrderStageStatus") {
    $projectid = $_POST['projectid'] ?? 0;
    $stockorder_no = $_POST['stockorder_no'] ?? '';
    $productid = $_POST['productid'] ?? '';

    // Track Root & Scion Sowing
    $root_sowing_trays = 0; $root_sowing_plants = 0; $root_avg_cavity = 0;
    $scion_sowing_trays = 0; $scion_sowing_plants = 0; $scion_avg_cavity = 0;

    $s_sql = "SELECT 
                LOWER(IFNULL(roottype, 'root')) as rtype,
                COUNT(sowingplantid) as trays, 
                IFNULL(SUM(traycavity),0) as plants, 
                IFNULL(AVG(traycavity),0) as avg_cavity
              FROM krf_polyhouse_sowingplant_details 
              WHERE stockorder_no = ? AND projectid = ? AND productid = ?
              GROUP BY LOWER(IFNULL(roottype, 'root'))";
    $stmt = $krfconn->prepare($s_sql);
    if ($stmt) {
        $stmt->bind_param("sis", $stockorder_no, $projectid, $productid);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            if (trim($row['rtype']) === 'scion') {
                $scion_sowing_trays = (int)$row['trays'];
                $scion_sowing_plants = (int)$row['plants'];
                $scion_avg_cavity = (float)$row['avg_cavity'];
            } else {
                $root_sowing_trays += (int)$row['trays'];
                $root_sowing_plants += (int)$row['plants'];
                $root_avg_cavity = (float)$row['avg_cavity'];
            }
        }
        $stmt->close();
    }

    // Track Root & Scion Germination
    $root_germ_trays = 0; $root_germ_plants = 0;
    $scion_germ_trays = 0; $scion_germ_plants = 0;

    $g_sql = "SELECT 
                LOWER(IFNULL(roottype, 'root')) as rtype,
                IFNULL(SUM(traycountinbox),0) as trays 
              FROM krf_polyhouse_germinated_boxes 
              WHERE stockorderno = ? AND projectid = ? AND productid = ?
              GROUP BY LOWER(IFNULL(roottype, 'root'))";
    $stmt = $krfconn->prepare($g_sql);
    if ($stmt) {
        $stmt->bind_param("sis", $stockorder_no, $projectid, $productid);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $trays = (int)$row['trays'];
            if (trim($row['rtype']) === 'scion') {
                $scion_germ_trays = $trays;
                $avg_c = $scion_avg_cavity > 0 ? $scion_avg_cavity : ($root_avg_cavity > 0 ? $root_avg_cavity : 1);
                $scion_germ_plants = (int)round($trays * $avg_c);
            } else {
                $root_germ_trays += $trays;
                $avg_c = $root_avg_cavity > 0 ? $root_avg_cavity : 1;
                $root_germ_plants += (int)round($trays * $avg_c);
            }
        }
        $stmt->close();
    }

    // Track Root & Scion Polyhouse
    $root_poly_trays = 0; $root_poly_plants = 0;
    $scion_poly_trays = 0; $scion_poly_plants = 0;

    $p_sql = "SELECT 
                LOWER(IFNULL(roottype, 'root')) as rtype,
                COUNT(*) as trays, 
                IFNULL(SUM(qtypertray),0) as plants 
              FROM krf_polyhouse_plant_details 
              WHERE stockorder_no = ? AND projectid = ? AND productid = ?
              GROUP BY LOWER(IFNULL(roottype, 'root'))";
    $stmt = $krfconn->prepare($p_sql);
    if ($stmt) {
        $stmt->bind_param("sis", $stockorder_no, $projectid, $productid);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            if (trim($row['rtype']) === 'scion') {
                $scion_poly_trays = (int)$row['trays'];
                $scion_poly_plants = (int)$row['plants'];
            } else {
                $root_poly_trays += (int)$row['trays'];
                $root_poly_plants += (int)$row['plants'];
            }
        }
        $stmt->close();
    }

    $has_scion = ($scion_sowing_trays > 0 || $scion_germ_trays > 0 || $scion_poly_trays > 0);

    if ($has_scion) {
        $sowing_trays  = ($root_sowing_trays > 0) ? $root_sowing_trays : $scion_sowing_trays;
        $sowing_plants = ($root_sowing_plants > 0) ? $root_sowing_plants : $scion_sowing_plants;

        $germ_trays  = ($root_germ_trays > 0) ? $root_germ_trays : $scion_germ_trays;
        $germ_plants = ($root_germ_plants > 0) ? $root_germ_plants : $scion_germ_plants;

        $poly_trays  = ($root_poly_trays > 0) ? $root_poly_trays : $scion_poly_trays;
        $poly_plants = ($root_poly_plants > 0) ? $root_poly_plants : $scion_poly_plants;
    } else {
        $sowing_trays  = $root_sowing_trays;
        $sowing_plants = $root_sowing_plants;

        $germ_trays  = $root_germ_trays;
        $germ_plants = $root_germ_plants;

        $poly_trays  = $root_poly_trays;
        $poly_plants = $root_poly_plants;
    }

    // Fetch distinct sowinglotno_internal details from krf_polyhouse_plant_details (or krf_polyhouse_sowingplant_details)
    $sowing_lots = [];
    $lot_sql = "SELECT DISTINCT sowinglotno_internal, LOWER(IFNULL(roottype, 'root')) as roottype, COUNT(*) as trays, IFNULL(SUM(qtypertray),0) as plants, MIN(sowingdate_internal) as sowingdate_internal, MAX(dispatchdate_internal) as dispatchdate_internal
                FROM krf_polyhouse_plant_details
                WHERE stockorder_no = ? AND projectid = ? AND (productid = ? OR productname = ?) AND sowinglotno_internal IS NOT NULL AND sowinglotno_internal != ''
                GROUP BY sowinglotno_internal, roottype";
    $stmt_lot = $krfconn->prepare($lot_sql);
    if ($stmt_lot) {
        $stmt_lot->bind_param("siss", $stockorder_no, $projectid, $productid, $productid);
        $stmt_lot->execute();
        $res_lot = $stmt_lot->get_result();
        while ($lrow = $res_lot->fetch_assoc()) {
            $sowing_lots[] = [
                'sowinglotno_internal'  => $lrow['sowinglotno_internal'],
                'roottype'              => $lrow['roottype'],
                'trays'                 => (int)$lrow['trays'],
                'plants'                => (int)$lrow['plants'],
                'sowingdate_internal'   => $lrow['sowingdate_internal'] ?? '',
                'dispatchdate_internal' => $lrow['dispatchdate_internal'] ?? ''
            ];
        }
        $stmt_lot->close();
    }

    if (empty($sowing_lots)) {
        $lot_sql2 = "SELECT DISTINCT sowinglotno_internal, LOWER(IFNULL(roottype, 'root')) as roottype, COUNT(sowingplantid) as trays, IFNULL(SUM(traycavity),0) as plants, MIN(sowingdate_internal) as sowingdate_internal, MAX(dispatchdate_internal) as dispatchdate_internal
                     FROM krf_polyhouse_sowingplant_details
                     WHERE stockorder_no = ? AND projectid = ? AND productid = ? AND sowinglotno_internal IS NOT NULL AND sowinglotno_internal != ''
                     GROUP BY sowinglotno_internal, roottype";
        $stmt_lot2 = $krfconn->prepare($lot_sql2);
        if ($stmt_lot2) {
            $stmt_lot2->bind_param("sis", $stockorder_no, $projectid, $productid);
            $stmt_lot2->execute();
            $res_lot2 = $stmt_lot2->get_result();
            while ($lrow2 = $res_lot2->fetch_assoc()) {
                $sowing_lots[] = [
                    'sowinglotno_internal'  => $lrow2['sowinglotno_internal'],
                    'roottype'              => $lrow2['roottype'],
                    'trays'                 => (int)$lrow2['trays'],
                    'plants'                => (int)$lrow2['plants'],
                    'sowingdate_internal'   => $lrow2['sowingdate_internal'] ?? '',
                    'dispatchdate_internal' => $lrow2['dispatchdate_internal'] ?? ''
                ];
            }
            $stmt_lot2->close();
        }
    }

    // Fallback tray cavity from product master if sowing not done yet /*p.tray_id = t.tray_id */ commit by  after discuss with chatur
    // if ($root_avg_cavity <= 0) {
    //     $p_sql = "SELECT IFNULL(t.cavity_count, 104) as cavity_count 
    //               FROM product_master p 
    //               LEFT JOIN krf_tray_master t ON /*p.tray_id = t.tray_id */
    //               p.trayCategoryId =t.category_id
    //               WHERE p.ProductID = ? AND p.projectid = ? and p.trayCategoryId is not null and  p.trayCategoryId <>'' and t.category_id is not null and t.category_id <>'' ";
    //     $stmt_p = $krfconn->prepare($p_sql);
    //     if ($stmt_p) {
    //         $stmt_p->bind_param("si", $productid, $projectid);
    //         $stmt_p->execute();
    //         $res_p = $stmt_p->get_result();
    //         if ($prow = $res_p->fetch_assoc()) {
    //             $root_avg_cavity = (float)$prow['cavity_count'];
    //         }
    //         $stmt_p->close();
    //     }
    // }
    if ($root_avg_cavity <= 0) $root_avg_cavity = 104;

    echo json_encode([
        'status' => 'success',
        'data' => [
            'sowing_trays'  => $sowing_trays,
            'sowing_plants' => $sowing_plants,
            'germ_trays'    => $germ_trays,
            'germ_plants'   => $germ_plants,
            'poly_trays'    => $poly_trays,
            'poly_plants'   => $poly_plants,

            'root_sowing_trays'  => $root_sowing_trays,
            'root_sowing_plants' => $root_sowing_plants,
            'root_germ_trays'    => $root_germ_trays,
            'root_germ_plants'   => $root_germ_plants,
            'root_poly_trays'    => $root_poly_trays,
            'root_poly_plants'   => $root_poly_plants,

            'scion_sowing_trays'  => $scion_sowing_trays,
            'scion_sowing_plants' => $scion_sowing_plants,
            'scion_germ_trays'    => $scion_germ_trays,
            'scion_germ_plants'   => $scion_germ_plants,
            'scion_poly_trays'    => $scion_poly_trays,
            'scion_poly_plants'   => $scion_poly_plants,

            'has_scion' => $has_scion,
            'sowing_lots' => $sowing_lots,
            'root_dispatch_date' => $root_dispatch_date,
            'root_tray_cavity'   => (int)round($root_avg_cavity)
        ]
    ]);
    exit;
} else if ($action == "AutoCancelExpiredDraftOrders") {
    require_once(__DIR__ . '/../functions/orders_functions.php');
    $psono = trim($_POST['psono'] ?? '');
    $res = autoCancelExpiredDraftOrders($krfconn, !empty($psono) ? $psono : null);
    echo json_encode([
        'status' => 'success',
        'cancelled_count' => $res['cancelled_count'],
        'cancelled_psos' => $res['psono_list']
    ]);
    exit;
} else if ($action == "DeleteProvisionalOrder" || $action == "CancelWholeProvisionalOrder") {
    $projectid = intval($_POST['projectid'] ?? 0);
    $psono = trim($_POST['psono'] ?? '');
    $remark = trim($_POST['remark'] ?? ($_POST['remarks'] ?? ''));
    $financial_action = trim($_POST['financial_action'] ?? 'credit_note');
    
    if (empty($psono)) {
        echo json_encode(['status' => 'error', 'message' => 'Order number is missing.']);
        exit;
    }

    if (empty($remark)) {
        echo json_encode(['status' => 'error', 'message' => 'Cancellation remark is required. Cannot cancel order without a remark.']);
        exit;
    }
    
    $loginid = $_SESSION['loginid'] ?? $_SESSION['userid'] ?? 0;
    $loginname = $_SESSION['loginname'] ?? $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'Admin';
    $userForUpdate = substr(!empty($loginid) ? (string)$loginid : (string)$loginname, 0, 45);
    
    try {
        // DISPATCH VALIDATION: Prevent whole order cancel if any item has already been dispatched
        $chkDisp = $krfconn->query("
            SELECT soi.product_name, soi.dispatchedqty, soi.status 
            FROM krf_sales_order_items soi 
            WHERE soi.psono = '{$krfconn->real_escape_string($psono)}' 
              AND (LOWER(soi.status) IN ('dispatched', 'fulldispatch', 'partialdispatch') OR soi.dispatchedqty > 0)
            LIMIT 1
        ");
        if ($chkDisp && $dRow = $chkDisp->fetch_assoc()) {
            echo json_encode([
                'status' => 'error',
                'message' => "Cannot cancel whole order: Product '{$dRow['product_name']}' has already been dispatched. Dispatched orders cannot be cancelled via trash. For returned plants, please process through Sales Return."
            ]);
            exit;
        }

        $chkDispCons = $krfconn->query("
            SELECT poc.product_id, poc.dispatchedqty, poc.status 
            FROM krf_productionorder_consumed poc 
            WHERE poc.psono = '{$krfconn->real_escape_string($psono)}' 
              AND (LOWER(poc.status) IN ('dispatched', 'fulldispatch', 'partialdispatch') OR poc.dispatchedqty > 0)
              AND IFNULL(poc.tedit, '') = ''
            LIMIT 1
        ");
        if ($chkDispCons && $dRowCons = $chkDispCons->fetch_assoc()) {
            echo json_encode([
                'status' => 'error',
                'message' => "Cannot cancel whole order: Plants for this order have already been dispatched. For returned plants, please process through Sales Return."
            ]);
            exit;
        }

        // SOWING VALIDATION: Check if ANY active item created its own production stock order AND sowing has started
        $chkActive = $krfconn->query("SELECT id, product_id, product_name, stockorder_no, quantity FROM krf_provisional_sales_order_items WHERE psono = '{$krfconn->real_escape_string($psono)}' AND (status IS NULL OR status = '' OR status != 'Cancelled')");
        if ($chkActive) {
            while ($actItem = $chkActive->fetch_assoc()) {
                $actStk = trim($actItem['stockorder_no'] ?? '');
                if (!empty($actStk)) {
                    $isSelf = false;
                    $sowingStarted = false;
                    $sowingStatusText = '';

                    $stkQ = $krfconn->query("SELECT psono, quantity, sowingstatus, workingstatus, sowingdate FROM krf_production_stockorder WHERE stockorder_no = '{$krfconn->real_escape_string($actStk)}' LIMIT 1");
                    if ($stkQ && $sRow = $stkQ->fetch_assoc()) {
                        $isSelf = (!empty($sRow['psono']) && strcasecmp(trim($sRow['psono']), $psono) === 0);
                        $swStatus = strtolower(trim($sRow['sowingstatus'] ?? ''));
                        $wStatus = strtolower(trim($sRow['workingstatus'] ?? ''));
                        if (in_array($swStatus, ['start', 'sowed', 'done']) || in_array($wStatus, ['polyhouse', 'final dispatch'])) {
                            $sowingStarted = true;
                            $sowingStatusText = $sRow['sowingstatus'] ?: $sRow['workingstatus'];
                        }
                    }
                    if (!$sowingStarted) {
                        $schQ = $krfconn->query("SELECT status FROM krf_polyhouse_sowingschedule WHERE stockorder_no = '{$krfconn->real_escape_string($actStk)}' AND LOWER(TRIM(status)) NOT IN ('delete','removed','notrequired')");
                        if ($schQ) {
                            while ($schRow = $schQ->fetch_assoc()) {
                                if (strtolower(trim($schRow['status'] ?? '')) !== 'pending') {
                                    $sowingStarted = true;
                                    $sowingStatusText = $schRow['status'];
                                    break;
                                }
                            }
                        }
                    }
                    if (!$sowingStarted) {
                        $trQ = $krfconn->query("SELECT COUNT(*) as sowed_cnt FROM krf_polyhouse_sowingplant_details WHERE stockorder_no = '{$krfconn->real_escape_string($actStk)}' AND sowingdate_internal IS NOT NULL AND sowingdate_internal > '1970-01-01'");
                        if ($trQ && $trRow = $trQ->fetch_assoc()) {
                            if ((int)$trRow['sowed_cnt'] > 0) {
                                $sowingStarted = true;
                                $sowingStatusText = 'Trays Sowed (' . $trRow['sowed_cnt'] . ')';
                            }
                        }
                    }

                    if ($isSelf && $sowingStarted) {
                        echo json_encode([
                            'status' => 'error',
                            'message' => "Cannot cancel whole order: Product '{$actItem['product_name']}' created its own production stock order ({$actStk}) and sowing has already started ({$sowingStatusText}). Sown products cannot be removed. You can only edit its quantity in consultation with the sowing department (Order Qty <= Sown Qty)."
                        ]);
                        exit;
                    }
                }
            }
        }

        $now = date('Y-m-d H:i:s');
        $curdate = date('Y-m-d');
        $teditText = substr("Cancelled by " . $userForUpdate . " on " . $now . " - Reason: " . $remark, 0, 255);

        // 1. Update krf_provisional_sales_order: remarks, status='Cancelled', updated_by, updated_at
        $stmt = $krfconn->prepare("UPDATE krf_provisional_sales_order SET status = 'Cancelled', remarks = IF(remarks IS NULL OR remarks = '', ?, CONCAT(remarks, '\n', ?)), updated_by = ?, updated_at = NOW() WHERE psono = ? AND (projectid = ? OR ? = 0)");
        $stmt->bind_param("ssssii", $remark, $remark, $userForUpdate, $psono, $projectid, $projectid);
        $res = $stmt->execute();
        $stmt->close();

        // 2. Update krf_provisional_sales_order_items: status='Cancelled', remark, updatedat, updatedby (tedit not updated)
        $stmtItems = $krfconn->prepare("UPDATE krf_provisional_sales_order_items SET status = 'Cancelled', remark = IF(remark IS NULL OR remark = '', ?, CONCAT(remark, ' | Cancelled: ', ?)), updatedat = ?, updatedby = ? WHERE psono = ? AND (projectid = ? OR ? = 0)");
        $stmtItems->bind_param("sssssii", $remark, $remark, $curdate, $userForUpdate, $psono, $projectid, $projectid);
        $stmtItems->execute();
        $stmtItems->close();

        // 3. Update krf_productionorder_consumed to cancel reservations
        $stmtCons = $krfconn->prepare("UPDATE krf_productionorder_consumed SET confirmstatus = 'cancelled', status = 'Cancelled', remark = IF(remark IS NULL OR remark = '', ?, CONCAT(remark, ' | Cancelled: ', ?)), updated_at = NOW() WHERE psono = ? AND (projectid = ? OR ? = 0)");
        $stmtCons->bind_param("sssii", $remark, $remark, $psono, $projectid, $projectid);
        $stmtCons->execute();
        $stmtCons->close();

        // Trader check: if client is trader (is_trader = 1), DO NOT cancel production_stockorder or polyhouse_sowingschedule
        $isTraderClient = false;
        $chkTrader = $krfconn->query("
            SELECT cm.is_trader 
            FROM krf_provisional_sales_order pso 
            LEFT JOIN krf_client_master cm ON cm.client_id = pso.clientid 
            WHERE pso.psono = '{$krfconn->real_escape_string($psono)}' 
            LIMIT 1
        ");
        if ($chkTrader && $tRow = $chkTrader->fetch_assoc()) {
            $isTraderClient = (!empty($tRow['is_trader']) && intval($tRow['is_trader']) === 1);
        }

        // 4. Update krf_production_stockorder as per psono (skipped if client is Trader)
        if (!$isTraderClient) {
            $stmtStock = $krfconn->prepare("UPDATE krf_production_stockorder SET status = 'Cancelled', workingstatus = 'Cancelled', editedremark = IF(editedremark IS NULL OR editedremark = '', ?, CONCAT(editedremark, ' | Cancelled: ', ?)), editedby = ?, editedon = ?, updated_at = NOW() WHERE psono = ? AND (projectid = ? OR ? = 0)");
            $stmtStock->bind_param("sssssii", $remark, $remark, $userForUpdate, $curdate, $psono, $projectid, $projectid);
            $stmtStock->execute();
            $stmtStock->close();
        }

        // 5. Update krf_sales_order: if full order cancel
        $stmtSales = $krfconn->prepare("UPDATE krf_sales_order SET status = 'Cancelled', remarks = IF(remarks IS NULL OR remarks = '', ?, CONCAT(remarks, '\n', ?)), updated_by = ?, updated_at = NOW() WHERE psono = ? AND (projectid = ? OR ? = 0)");
        $stmtSales->bind_param("ssssii", $remark, $remark, $userForUpdate, $psono, $projectid, $projectid);
        $stmtSales->execute();
        $stmtSales->close();

        // 6. Update krf_sales_order_items: as per psono
        $stmtSalesItems = $krfconn->prepare("UPDATE krf_sales_order_items SET status = 'Cancelled', remark = IF(remark IS NULL OR remark = '', ?, CONCAT(remark, ' | Cancelled: ', ?)), ammendedby = ?, updateamended = NOW() WHERE psono = ? AND (projectid = ? OR ? = 0)");
        $stmtSalesItems->bind_param("ssssii", $remark, $remark, $userForUpdate, $psono, $projectid, $projectid);
        $stmtSalesItems->execute();
        $stmtSalesItems->close();

        // 7. Update krf_polyhouse_sowingschedule & plants: as per psono (skipped if client is Trader)
        if (!$isTraderClient) {
            $stmtSow = $krfconn->prepare("UPDATE krf_polyhouse_sowingschedule SET status = 'Cancelled', remarks = IF(remarks IS NULL OR remarks = '', ?, CONCAT(remarks, ' | Cancelled: ', ?)), updated_by = ?, updated_at = NOW() WHERE psono = ? AND (projectid = ? OR ? = 0)");
            $stmtSow->bind_param("ssssii", $remark, $remark, $userForUpdate, $psono, $projectid, $projectid);
            $stmtSow->execute();
            $stmtSow->close();

            // 7b. Update krf_polyhouse_sowingplant_details: as per psono -> status = 'Removed'
            $stmtPlant = $krfconn->prepare("UPDATE krf_polyhouse_sowingplant_details SET status = 'Removed' WHERE psono = ? AND (projectid = ? OR ? = 0)");
            if ($stmtPlant) {
                $stmtPlant->bind_param("sii", $psono, $projectid, $projectid);
                $stmtPlant->execute();
                $stmtPlant->close();
            }
        }

        // 8. FINANCIAL SETTLEMENT: Check if advance payment was received against this psono / sono
        $sono = '';
        $soQ = $krfconn->query("SELECT sono FROM krf_sales_order WHERE psono = '{$krfconn->real_escape_string($psono)}' LIMIT 1");
        if ($soQ && $soRow = $soQ->fetch_assoc()) {
            $sono = trim($soRow['sono'] ?? '');
        }

        $amount_paid = 0.00;
        $payQ = $krfconn->query("SELECT IFNULL(SUM(advanceamount), 0) AS total_paid FROM advance_payment_transactions WHERE (invoiceno = '{$krfconn->real_escape_string($psono)}' OR (invoiceno != '' AND invoiceno = '{$krfconn->real_escape_string($sono)}')) AND (tedit IS NULL OR tedit = '')");
        if ($payQ && $payRow = $payQ->fetch_assoc()) {
            $amount_paid = floatval($payRow['total_paid'] ?? 0);
        }

        $financialMessage = '';
        if ($amount_paid > 0) {
            $psoHdrRes = $krfconn->query("SELECT clientid, clientname, companyid, subcompanyid, projectid FROM krf_provisional_sales_order WHERE psono = '{$krfconn->real_escape_string($psono)}' LIMIT 1");
            $psoHdr = $psoHdrRes ? $psoHdrRes->fetch_assoc() : [];
            $clId = $psoHdr['clientid'] ?? '';
            $clName = $psoHdr['clientname'] ?? '';
            $comp = (int)($psoHdr['companyid'] ?? 0);
            $subcomp = (int)($psoHdr['subcompanyid'] ?? 0);
            $proj = (int)($psoHdr['projectid'] ?? $projectid);

            if ($financial_action === 'refund') {
                $refRemarks = "Refund against cancellation of Provisional Order #" . $psono . " - Reason: " . $remark;
                $negAmt = -$amount_paid;
                $stmtRef = $krfconn->prepare("INSERT INTO advance_payment_transactions (
                    companyid, subcompanyid, projectid, clientid, clientname, advanceamount, paymentdate, purposeofpayment, invoiceno, sourceofpayment, receivedby, remarks, transaction_type, allocated_datetime, verification
                ) VALUES (?, ?, ?, ?, ?, ?, CURDATE(), 'refund', ?, 'Refund', ?, ?, 'Refund', NOW(), 'verified')");
                if ($stmtRef) {
                    $stmtRef->bind_param("iiissdssss", $comp, $subcomp, $proj, $clId, $clName, $negAmt, $psono, $userForUpdate, $refRemarks);
                    $stmtRef->execute();
                    $stmtRef->close();
                }

                $stmtStmt = $krfconn->prepare("INSERT INTO krf_tbl_client_statement (
                    project_id, client_id, txn_date, txn_type, txn_no, description, debit_amount, credit_amount, running_balance, created_at
                ) VALUES (?, ?, CURDATE(), 'WRITE_OFF', ?, ?, ?, 0.00, 0.00, NOW())");
                if ($stmtStmt) {
                    $stmtStmt->bind_param("isssd", $proj, $clId, $psono, $refRemarks, $amount_paid);
                    $stmtStmt->execute();
                    $stmtStmt->close();
                }
                $financialMessage = " Refund of ₹" . number_format($amount_paid, 2) . " has been recorded for the customer.";
            } else {
                // Generate Credit Note in krf_creditnote
                $cnRes = $krfconn->query("SELECT MAX(creditnote_id) AS max_id FROM krf_creditnote");
                $nextId = 1;
                if ($cnRes && $cnRow = $cnRes->fetch_assoc()) {
                    $nextId = intval($cnRow['max_id'] ?? 0) + 1;
                }
                $creditnote_no = "CN-" . date('Y') . "-" . str_pad((string)$nextId, 5, '0', STR_PAD_LEFT);
                $narration = "Credit Note of Rs. " . number_format($amount_paid, 2) . " (Full Cancellation) for Client: " . ($clName ?: $clId) . " against cancelled Provisional Order " . $psono . (!empty($sono) ? ", SO: " . $sono : "") . ". Advance payment credited to client ledger. Reason: " . $remark . ". Processed by " . $loginname . " on " . date('d-m-Y H:i:s') . ".";

                $stmtCn = $krfconn->prepare("INSERT INTO krf_creditnote (
                    creditnote_no, creditnote_date, projectid, companyid, subcompanyid,
                    clientid, clientname, psono, sono, amount_paid, credit_amount, narration, reason, status, created_by, created_at, updated_at
                ) VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?, NOW(), NOW())");
                if ($stmtCn) {
                    $stmtCn->bind_param("siiissssddsss", $creditnote_no, $proj, $comp, $subcomp, $clId, $clName, $psono, $sono, $amount_paid, $amount_paid, $narration, $remark, $loginname);
                    $stmtCn->execute();
                    $stmtCn->close();
                }

                $stmtStmt = $krfconn->prepare("INSERT INTO krf_tbl_client_statement (
                    project_id, client_id, txn_date, txn_type, txn_no, description, debit_amount, credit_amount, running_balance, created_at
                ) VALUES (?, ?, CURDATE(), 'CREDIT_NOTE', ?, ?, 0.00, ?, 0.00, NOW())");
                if ($stmtStmt) {
                    $stmtStmt->bind_param("isssd", $proj, $clId, $creditnote_no, $narration, $amount_paid);
                    $stmtStmt->execute();
                    $stmtStmt->close();
                }
                $financialMessage = " Credit Note (" . $creditnote_no . ") of ₹" . number_format($amount_paid, 2) . " has been issued and credited to client ledger.";
            }
        }
        
        if ($res) {
            echo json_encode([
                'status' => 'success', 
                'message' => 'Provisional order (' . $psono . ') has been cancelled successfully.' . $financialMessage
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to cancel the order.']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
} else if ($action == "CancelProvisionalOrderItem" || $action == "CancelProvisionalOrderItems") {
    $projectid = intval($_POST['projectid'] ?? 0);
    $psono = trim($_POST['psono'] ?? '');
    $financial_action = trim($_POST['financial_action'] ?? 'adjust_order');
    
    // Parse item_ids (array, comma-separated string, or single item_id)
    $itemIds = [];
    if (isset($_POST['item_ids'])) {
        if (is_array($_POST['item_ids'])) {
            $itemIds = array_map('intval', $_POST['item_ids']);
        } else {
            $parts = explode(',', (string)$_POST['item_ids']);
            $itemIds = array_map('intval', $parts);
        }
    } else if (isset($_POST['item_id'])) {
        $itemIds = [intval($_POST['item_id'])];
    }
    $itemIds = array_filter(array_unique($itemIds), function($v) { return $v > 0; });
    
    $remark = trim($_POST['remark'] ?? ($_POST['remarks'] ?? ''));
    
    if (empty($itemIds)) {
        echo json_encode(['status' => 'error', 'message' => 'No item(s) selected for cancellation.']);
        exit;
    }

    if (empty($remark)) {
        echo json_encode(['status' => 'error', 'message' => 'Cancellation remark is required. Cannot cancel item(s) without a remark.']);
        exit;
    }
    
    $loginid = $_SESSION['loginid'] ?? $_SESSION['userid'] ?? 0;
    $loginname = $_SESSION['loginname'] ?? $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'Admin';
    $userForUpdate = substr(!empty($loginid) ? (string)$loginid : (string)$loginname, 0, 45);
    
    try {
        // DISPATCH & SOWING VALIDATION: Check each selected item
        foreach ($itemIds as $chkId) {
            $itemInfoQ = $krfconn->query("SELECT id, product_id, product_name, stockorder_no, psono FROM krf_provisional_sales_order_items WHERE id = " . intval($chkId) . " LIMIT 1");
            if ($itemInfoQ && $itemInfo = $itemInfoQ->fetch_assoc()) {
                $chkStk = trim($itemInfo['stockorder_no'] ?? '');
                $chkPid = trim($itemInfo['product_id'] ?? '');
                $orderPsono = trim($itemInfo['psono'] ?? $psono);

                // 1. Check if item has already been dispatched
                $isItemDisp = false;
                if (!empty($chkPid) || !empty($chkStk)) {
                    $chkItemDisp = $krfconn->query("
                        SELECT status, dispatchedqty FROM krf_sales_order_items 
                        WHERE psono = '{$krfconn->real_escape_string($orderPsono)}' 
                          AND (product_id = '{$krfconn->real_escape_string($chkPid)}' OR stockorder_no = '{$krfconn->real_escape_string($chkStk)}') 
                          AND (LOWER(status) IN ('dispatched', 'fulldispatch', 'partialdispatch') OR dispatchedqty > 0) 
                        LIMIT 1
                    ");
                    if ($chkItemDisp && $chkItemDisp->num_rows > 0) {
                        $isItemDisp = true;
                    }
                    if (!$isItemDisp) {
                        $chkItemDisp2 = $krfconn->query("
                            SELECT status, dispatchedqty FROM krf_productionorder_consumed 
                            WHERE psono = '{$krfconn->real_escape_string($orderPsono)}' 
                              AND (product_id = '{$krfconn->real_escape_string($chkPid)}' OR stockorderno = '{$krfconn->real_escape_string($chkStk)}') 
                              AND (LOWER(status) IN ('dispatched', 'fulldispatch', 'partialdispatch') OR dispatchedqty > 0) 
                              AND IFNULL(tedit, '') = '' 
                            LIMIT 1
                        ");
                        if ($chkItemDisp2 && $chkItemDisp2->num_rows > 0) {
                            $isItemDisp = true;
                        }
                    }
                }
                if ($isItemDisp) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => "Cannot cancel product '{$itemInfo['product_name']}': It has already been dispatched. Dispatched items cannot be cancelled. For returned plants, please process through Sales Return."
                    ]);
                    exit;
                }

                if (!empty($chkStk)) {
                    $isSelf = false;
                    $sowingStarted = false;
                    $sowingStatusText = '';

                    $stkQ = $krfconn->query("SELECT psono, quantity, sowingstatus, workingstatus, sowingdate FROM krf_production_stockorder WHERE stockorder_no = '{$krfconn->real_escape_string($chkStk)}' LIMIT 1");
                    if ($stkQ && $sRow = $stkQ->fetch_assoc()) {
                        $isSelf = (!empty($sRow['psono']) && strcasecmp(trim($sRow['psono']), $orderPsono) === 0);
                        $swStatus = strtolower(trim($sRow['sowingstatus'] ?? ''));
                        $wStatus = strtolower(trim($sRow['workingstatus'] ?? ''));
                        if (in_array($swStatus, ['start', 'sowed', 'done']) || in_array($wStatus, ['polyhouse', 'final dispatch'])) {
                            $sowingStarted = true;
                            $sowingStatusText = $sRow['sowingstatus'] ?: $sRow['workingstatus'];
                        }
                    }
                    if (!$sowingStarted) {
                        $schQ = $krfconn->query("SELECT status FROM krf_polyhouse_sowingschedule WHERE stockorder_no = '{$krfconn->real_escape_string($chkStk)}' AND LOWER(TRIM(status)) NOT IN ('delete','removed','notrequired')");
                        if ($schQ) {
                            while ($schRow = $schQ->fetch_assoc()) {
                                if (strtolower(trim($schRow['status'] ?? '')) !== 'pending') {
                                    $sowingStarted = true;
                                    $sowingStatusText = $schRow['status'];
                                    break;
                                }
                            }
                        }
                    }
                    if (!$sowingStarted) {
                        $trQ = $krfconn->query("SELECT COUNT(*) as sowed_cnt FROM krf_polyhouse_sowingplant_details WHERE stockorder_no = '{$krfconn->real_escape_string($chkStk)}' AND sowingdate_internal IS NOT NULL AND sowingdate_internal > '1970-01-01'");
                        if ($trQ && $trRow = $trQ->fetch_assoc()) {
                            if ((int)$trRow['sowed_cnt'] > 0) {
                                $sowingStarted = true;
                                $sowingStatusText = 'Trays Sowed (' . $trRow['sowed_cnt'] . ')';
                            }
                        }
                    }

                    if ($isSelf && $sowingStarted) {
                        echo json_encode([
                            'status' => 'error',
                            'message' => "Cannot cancel product '{$itemInfo['product_name']}': It created its own production stock order ({$chkStk}) and sowing has already started ({$sowingStatusText}). Sown items cannot be removed. You can only edit quantity in consultation with the sowing department (Order Qty <= Sown Qty)."
                        ]);
                        exit;
                    }
                }
            }
        }

        $now = date('Y-m-d H:i:s');
        $curdate = date('Y-m-d');
        $teditText = substr("Cancelled by " . $userForUpdate . " on " . $now . " - Reason: " . $remark, 0, 255);

        // Fetch product_id and stockorder_no for the selected items
        $inClause = implode(',', $itemIds);
        $itemsRes = $krfconn->query("SELECT id, psono, product_id, stockorder_no, projectid FROM krf_provisional_sales_order_items WHERE id IN ($inClause)");
        $productIds = [];
        $stockOrderNos = [];
        if ($itemsRes) {
            while ($row = $itemsRes->fetch_assoc()) {
                if (!empty($row['product_id'])) {
                    $productIds[] = trim($row['product_id']);
                }
                if (!empty($row['stockorder_no'])) {
                    $stockOrderNos[] = trim($row['stockorder_no']);
                }
                if (empty($psono) && !empty($row['psono'])) {
                    $psono = trim($row['psono']);
                }
                if (empty($projectid) && !empty($row['projectid'])) {
                    $projectid = intval($row['projectid']);
                }
            }
        }
        $productIds = array_unique(array_filter($productIds));
        $stockOrderNos = array_unique(array_filter($stockOrderNos));

        // 1. Update krf_provisional_sales_order_items: status='Cancelled', remark, updatedat, updatedby (tedit not updated)
        $stmtItem = $krfconn->prepare("UPDATE krf_provisional_sales_order_items SET status = 'Cancelled', remark = IF(remark IS NULL OR remark = '', ?, CONCAT(remark, ' | Cancelled: ', ?)), updatedat = ?, updatedby = ? WHERE id IN ($inClause)");
        $stmtItem->bind_param("ssss", $remark, $remark, $curdate, $userForUpdate);
        $res = $stmtItem->execute();
        $stmtItem->close();

        // If productIds and psono are available, update linked tables for these products
        if (!empty($psono) && !empty($productIds)) {
            $escPsono = $krfconn->real_escape_string($psono);
            $escRemark = $krfconn->real_escape_string($remark);
            $escUser = $krfconn->real_escape_string($userForUpdate);
            $escProdIds = implode("','", array_map([$krfconn, 'real_escape_string'], $productIds));

            $stockCondConsumed = "";
            $stockCondStock = "";
            $stockCondSales = "";
            if (!empty($stockOrderNos)) {
                $escStockNos = implode("','", array_map([$krfconn, 'real_escape_string'], $stockOrderNos));
                $stockCondConsumed = " OR stockorderno IN ('$escStockNos')";
                $stockCondStock = " OR stockorder_no IN ('$escStockNos')";
                $stockCondSales = " OR stockorder_no IN ('$escStockNos')";
            }

            // Check if client is a trader (is_trader = 1)
            $isTraderClient = false;
            $chkTrader = $krfconn->query("
                SELECT cm.is_trader 
                FROM krf_provisional_sales_order pso 
                LEFT JOIN krf_client_master cm ON cm.client_id = pso.clientid 
                WHERE pso.psono = '$escPsono' 
                LIMIT 1
            ");
            if ($chkTrader && $tRow = $chkTrader->fetch_assoc()) {
                $isTraderClient = (!empty($tRow['is_trader']) && intval($tRow['is_trader']) === 1);
            }

            // 2. Update krf_productionorder_consumed as per provisional order (psono) and productid
            $krfconn->query("UPDATE krf_productionorder_consumed 
                SET confirmstatus = 'cancelled', 
                    status = 'Cancelled', 
                    remark = IF(remark IS NULL OR remark = '', '$escRemark', CONCAT(remark, ' | Cancelled: ', '$escRemark')), 
                    updated_at = NOW() 
                WHERE psono = '$escPsono' AND (product_id IN ('$escProdIds')$stockCondConsumed)");

            // 3. Update krf_production_stockorder as per psono and productid (skipped if client is Trader)
            if (!$isTraderClient) {
                $krfconn->query("UPDATE krf_production_stockorder 
                    SET status = 'Cancelled', 
                        workingstatus = 'Cancelled', 
                        editedremark = IF(editedremark IS NULL OR editedremark = '', '$escRemark', CONCAT(editedremark, ' | Cancelled: ', '$escRemark')), 
                        editedby = '$escUser', 
                        editedon = '$curdate', 
                        updated_at = NOW() 
                    WHERE psono = '$escPsono' AND (productid IN ('$escProdIds')$stockCondStock)");
            }

            // 4. Update krf_sales_order_items as per psono and productid
            $krfconn->query("UPDATE krf_sales_order_items 
                SET status = 'Cancelled', 
                    remark = IF(remark IS NULL OR remark = '', '$escRemark', CONCAT(remark, ' | Cancelled: ', '$escRemark')), 
                    ammendedby = '$escUser', 
                    updateamended = NOW() 
                WHERE psono = '$escPsono' AND (product_id IN ('$escProdIds')$stockCondSales)");

            // 5. Update krf_polyhouse_sowingschedule & plants as per psono and productid (skipped if client is Trader)
            if (!$isTraderClient) {
                $krfconn->query("UPDATE krf_polyhouse_sowingschedule 
                    SET status = 'Cancelled', 
                        remarks = IF(remarks IS NULL OR remarks = '', '$escRemark', CONCAT(remarks, ' | Cancelled: ', '$escRemark')), 
                        updated_by = '$escUser', 
                        updated_at = NOW() 
                    WHERE psono = '$escPsono' AND (productid IN ('$escProdIds')$stockCondStock)");

                // 5b. Update krf_polyhouse_sowingplant_details -> status = 'Removed'
                $krfconn->query("UPDATE krf_polyhouse_sowingplant_details 
                    SET status = 'Removed' 
                    WHERE psono = '$escPsono' AND (productid IN ('$escProdIds')$stockCondStock)");
            }
        }

        // Check if all items in krf_provisional_sales_order_items are now cancelled
        $allCancelled = false;
        $financialMessage = '';
        if (!empty($psono)) {
            $chk = $krfconn->query("SELECT COUNT(*) AS active_cnt, COALESCE(SUM(amount), 0) AS new_total, COALESCE(SUM(cgst_amount + sgst_amount + igst_amount), 0) AS new_tax, COALESCE(SUM(taxableamount), 0) AS new_taxable FROM krf_provisional_sales_order_items WHERE psono = '{$krfconn->real_escape_string($psono)}' AND (status IS NULL OR status = '' OR status != 'Cancelled')");
            $actRow = ($chk && $row = $chk->fetch_assoc()) ? $row : [];
            $activeCnt = (int)($actRow['active_cnt'] ?? 0);
            if ($activeCnt === 0) {
                $allCancelled = true;
                $autoRemark = "Cancelled: All items were cancelled. Reason: " . $remark;
                $escAutoRemark = $krfconn->real_escape_string($autoRemark);
                $escPsono = $krfconn->real_escape_string($psono);
                $escUser = $krfconn->real_escape_string($userForUpdate);

                // Full Order Cancel cascade:
                // 1. krf_provisional_sales_order
                $krfconn->query("UPDATE krf_provisional_sales_order 
                    SET status = 'Cancelled', 
                        remarks = IF(remarks IS NULL OR remarks = '', '$escAutoRemark', CONCAT(remarks, '\n', '$escAutoRemark')), 
                        updated_by = '$escUser', 
                        updated_at = NOW() 
                    WHERE psono = '$escPsono'");

                // 2. krf_sales_order: if full order cancel
                $krfconn->query("UPDATE krf_sales_order 
                    SET status = 'Cancelled', 
                        remarks = IF(remarks IS NULL OR remarks = '', '$escAutoRemark', CONCAT(remarks, '\n', '$escAutoRemark')), 
                        updated_by = '$escUser', 
                        updated_at = NOW() 
                    WHERE psono = '$escPsono'");

                // 3. Cascade to any remaining rows in consumed, stockorder, sales_order_items, sowingschedule for this psono
                $krfconn->query("UPDATE krf_productionorder_consumed 
                    SET confirmstatus = 'cancelled', 
                        status = 'Cancelled', 
                        remark = IF(remark IS NULL OR remark = '', '$escAutoRemark', CONCAT(remark, ' | ', '$escAutoRemark')), 
                        updated_at = NOW() 
                    WHERE psono = '$escPsono'");

                if (!$isTraderClient) {
                    $krfconn->query("UPDATE krf_production_stockorder 
                        SET status = 'Cancelled', 
                            workingstatus = 'Cancelled', 
                            editedremark = IF(editedremark IS NULL OR editedremark = '', '$escAutoRemark', CONCAT(editedremark, ' | ', '$escAutoRemark')), 
                            editedby = '$escUser', 
                            editedon = '$curdate', 
                            updated_at = NOW() 
                        WHERE psono = '$escPsono'");
                }

                $krfconn->query("UPDATE krf_sales_order_items 
                    SET status = 'Cancelled', 
                        remark = IF(remark IS NULL OR remark = '', '$escAutoRemark', CONCAT(remark, ' | ', '$escAutoRemark')), 
                        ammendedby = '$escUser', 
                        updateamended = NOW() 
                    WHERE psono = '$escPsono'");

                if (!$isTraderClient) {
                    $krfconn->query("UPDATE krf_polyhouse_sowingschedule 
                        SET status = 'Cancelled', 
                            remarks = IF(remarks IS NULL OR remarks = '', '$escAutoRemark', CONCAT(remarks, ' | ', '$escAutoRemark')), 
                            updated_by = '$escUser', 
                            updated_at = NOW() 
                        WHERE psono = '$escPsono'");

                    $krfconn->query("UPDATE krf_polyhouse_sowingplant_details 
                        SET status = 'Removed' 
                        WHERE psono = '$escPsono'");
                }

                // Financial settlement if full order is cancelled and advance was paid
                $sono = '';
                $soQ = $krfconn->query("SELECT sono FROM krf_sales_order WHERE psono = '$escPsono' LIMIT 1");
                if ($soQ && $soRow = $soQ->fetch_assoc()) {
                    $sono = trim($soRow['sono'] ?? '');
                }

                $amount_paid = 0.00;
                $payQ = $krfconn->query("SELECT IFNULL(SUM(advanceamount), 0) AS total_paid FROM advance_payment_transactions WHERE (invoiceno = '$escPsono' OR (invoiceno != '' AND invoiceno = '{$krfconn->real_escape_string($sono)}')) AND (tedit IS NULL OR tedit = '')");
                if ($payQ && $payRow = $payQ->fetch_assoc()) {
                    $amount_paid = floatval($payRow['total_paid'] ?? 0);
                }

                if ($amount_paid > 0 && $financial_action !== 'none') {
                    $psoHdrRes = $krfconn->query("SELECT clientid, clientname, companyid, subcompanyid, projectid FROM krf_provisional_sales_order WHERE psono = '$escPsono' LIMIT 1");
                    $psoHdr = $psoHdrRes ? $psoHdrRes->fetch_assoc() : [];
                    $clId = $psoHdr['clientid'] ?? '';
                    $clName = $psoHdr['clientname'] ?? '';
                    $comp = (int)($psoHdr['companyid'] ?? 0);
                    $subcomp = (int)($psoHdr['subcompanyid'] ?? 0);
                    $proj = (int)($psoHdr['projectid'] ?? $projectid);

                    if ($financial_action === 'refund') {
                        $refRemarks = "Refund against cancellation of Provisional Order #" . $psono . " - Reason: " . $remark;
                        $negAmt = -$amount_paid;
                        $stmtRef = $krfconn->prepare("INSERT INTO advance_payment_transactions (
                            companyid, subcompanyid, projectid, clientid, clientname, advanceamount, paymentdate, purposeofpayment, invoiceno, sourceofpayment, receivedby, remarks, transaction_type, allocated_datetime, verification
                        ) VALUES (?, ?, ?, ?, ?, ?, CURDATE(), 'refund', ?, 'Refund', ?, ?, 'Refund', NOW(), 'verified')");
                        if ($stmtRef) {
                            $stmtRef->bind_param("iiissdssss", $comp, $subcomp, $proj, $clId, $clName, $negAmt, $psono, $userForUpdate, $refRemarks);
                            $stmtRef->execute();
                            $stmtRef->close();
                        }

                        $stmtStmt = $krfconn->prepare("INSERT INTO krf_tbl_client_statement (
                            project_id, client_id, txn_date, txn_type, txn_no, description, debit_amount, credit_amount, running_balance, created_at
                        ) VALUES (?, ?, CURDATE(), 'WRITE_OFF', ?, ?, ?, 0.00, 0.00, NOW())");
                        if ($stmtStmt) {
                            $stmtStmt->bind_param("isssd", $proj, $clId, $psono, $refRemarks, $amount_paid);
                            $stmtStmt->execute();
                            $stmtStmt->close();
                        }
                        $financialMessage = " Refund of ₹" . number_format($amount_paid, 2) . " has been recorded.";
                    } else {
                        // Generate Credit Note
                        $cnRes = $krfconn->query("SELECT MAX(creditnote_id) AS max_id FROM krf_creditnote");
                        $nextId = 1;
                        if ($cnRes && $cnRow = $cnRes->fetch_assoc()) {
                            $nextId = intval($cnRow['max_id'] ?? 0) + 1;
                        }
                        $creditnote_no = "CN-" . date('Y') . "-" . str_pad((string)$nextId, 5, '0', STR_PAD_LEFT);
                        $narration = "Credit Note of Rs. " . number_format($amount_paid, 2) . " (All Items Cancelled) for Client: " . ($clName ?: $clId) . " against cancelled Provisional Order " . $psono . (!empty($sono) ? ", SO: " . $sono : "") . ". Advance payment credited to client ledger. Reason: " . $remark . ". Processed by " . $loginname . " on " . date('d-m-Y H:i:s') . ".";

                        $stmtCn = $krfconn->prepare("INSERT INTO krf_creditnote (
                            creditnote_no, creditnote_date, projectid, companyid, subcompanyid,
                            clientid, clientname, psono, sono, amount_paid, credit_amount, narration, reason, status, created_by, created_at, updated_at
                        ) VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?, NOW(), NOW())");
                        if ($stmtCn) {
                            $stmtCn->bind_param("siiissssddsss", $creditnote_no, $proj, $comp, $subcomp, $clId, $clName, $psono, $sono, $amount_paid, $amount_paid, $narration, $remark, $loginname);
                            $stmtCn->execute();
                            $stmtCn->close();
                        }

                        $stmtStmt = $krfconn->prepare("INSERT INTO krf_tbl_client_statement (
                            project_id, client_id, txn_date, txn_type, txn_no, description, debit_amount, credit_amount, running_balance, created_at
                        ) VALUES (?, ?, CURDATE(), 'CREDIT_NOTE', ?, ?, 0.00, ?, 0.00, NOW())");
                        if ($stmtStmt) {
                            $stmtStmt->bind_param("isssd", $proj, $clId, $creditnote_no, $narration, $amount_paid);
                            $stmtStmt->execute();
                            $stmtStmt->close();
                        }
                        $financialMessage = " Credit Note (" . $creditnote_no . ") of ₹" . number_format($amount_paid, 2) . " has been issued and credited to client ledger.";
                    }
                }
            } else {
                // PARTIAL ITEM CANCELLATION: Recalculate remaining active order amounts!
                $newTotal = (float)($actRow['new_total'] ?? 0);
                $newTax = (float)($actRow['new_tax'] ?? 0);
                $newTaxable = (float)($actRow['new_taxable'] ?? 0);

                // Fetch delivery/freight charges
                $locQ = $krfconn->query("SELECT SUM(CASE WHEN pickuptype = 'boxpacking' THEN (CASE WHEN freightamt > 0 THEN freightamt WHEN deliverycharges > 0 THEN deliverycharges ELSE (noofbox * chargesperbox) END) ELSE (CASE WHEN freightamt > 0 THEN freightamt ELSE deliverycharges END) END) AS loc_charges FROM krf_deliveryaddress_location WHERE psono = '{$krfconn->real_escape_string($psono)}' AND (tedit IS NULL OR tedit = '')");
                $locCharges = ($locQ && $lRow = $locQ->fetch_assoc()) ? (float)($lRow['loc_charges'] ?? 0) : 0;
                $finalOrderAmount = $newTotal + $locCharges;

                // Fetch total advance paid
                $sono = '';
                $soQ = $krfconn->query("SELECT sono FROM krf_sales_order WHERE psono = '{$krfconn->real_escape_string($psono)}' LIMIT 1");
                if ($soQ && $soRow = $soQ->fetch_assoc()) {
                    $sono = trim($soRow['sono'] ?? '');
                }

                $amount_paid = 0.00;
                $payQ = $krfconn->query("SELECT IFNULL(SUM(advanceamount), 0) AS total_paid FROM advance_payment_transactions WHERE (invoiceno = '{$krfconn->real_escape_string($psono)}' OR (invoiceno != '' AND invoiceno = '{$krfconn->real_escape_string($sono)}')) AND (tedit IS NULL OR tedit = '')");
                if ($payQ && $payRow = $payQ->fetch_assoc()) {
                    $amount_paid = floatval($payRow['total_paid'] ?? 0);
                }

                $newBalance = max(0, $finalOrderAmount - $amount_paid);
                $escUser = $krfconn->real_escape_string($userForUpdate);
                $escPsono = $krfconn->real_escape_string($psono);

                $partialRemark = "[Partial Item Cancellation: " . count($itemIds) . " item(s) cancelled by " . $loginname . " on " . date('d-m-Y H:i:s') . " - Reason: " . $remark . "]";
                $escPartialRemark = $krfconn->real_escape_string($partialRemark);

                // Update krf_provisional_sales_order remarks & updated_by
                $krfconn->query("UPDATE krf_provisional_sales_order 
                    SET updated_by = '$escUser', 
                        updated_at = NOW(), 
                        remarks = CONCAT(IFNULL(remarks, ''), '\n', '$escPartialRemark') 
                    WHERE psono = '$escPsono'");

                // Update krf_sales_order totals & remarks if exists
                $krfconn->query("UPDATE krf_sales_order 
                    SET total_amount = $newTaxable, 
                        tax_amount = $newTax, 
                        grand_total = $finalOrderAmount, 
                        updated_by = '$escUser', 
                        updated_at = NOW(), 
                        remarks = CONCAT(IFNULL(remarks, ''), '\n', '$escPartialRemark') 
                    WHERE psono = '$escPsono'");

                // If advance payment exceeded remaining active order amount, settle the excess
                if ($amount_paid > $finalOrderAmount && $financial_action !== 'none' && $financial_action !== 'adjust_order') {
                    $excessAmount = $amount_paid - $finalOrderAmount;
                    $psoHdrRes = $krfconn->query("SELECT clientid, clientname, companyid, subcompanyid, projectid FROM krf_provisional_sales_order WHERE psono = '$escPsono' LIMIT 1");
                    $psoHdr = $psoHdrRes ? $psoHdrRes->fetch_assoc() : [];
                    $clId = $psoHdr['clientid'] ?? '';
                    $clName = $psoHdr['clientname'] ?? '';
                    $comp = (int)($psoHdr['companyid'] ?? 0);
                    $subcomp = (int)($psoHdr['subcompanyid'] ?? 0);
                    $proj = (int)($psoHdr['projectid'] ?? $projectid);

                    if ($financial_action === 'refund') {
                        $refRemarks = "Refund of excess advance (Partial cancellation of order #" . $psono . ") - Reason: " . $remark;
                        $negAmt = -$excessAmount;
                        $stmtRef = $krfconn->prepare("INSERT INTO advance_payment_transactions (
                            companyid, subcompanyid, projectid, clientid, clientname, advanceamount, paymentdate, purposeofpayment, invoiceno, sourceofpayment, receivedby, remarks, transaction_type, allocated_datetime, verification
                        ) VALUES (?, ?, ?, ?, ?, ?, CURDATE(), 'refund', ?, 'Refund', ?, ?, 'Refund', NOW(), 'verified')");
                        if ($stmtRef) {
                            $stmtRef->bind_param("iiissdssss", $comp, $subcomp, $proj, $clId, $clName, $negAmt, $psono, $userForUpdate, $refRemarks);
                            $stmtRef->execute();
                            $stmtRef->close();
                        }
                        $stmtStmt = $krfconn->prepare("INSERT INTO krf_tbl_client_statement (
                            project_id, client_id, txn_date, txn_type, txn_no, description, debit_amount, credit_amount, running_balance, created_at
                        ) VALUES (?, ?, CURDATE(), 'WRITE_OFF', ?, ?, ?, 0.00, 0.00, NOW())");
                        if ($stmtStmt) {
                            $stmtStmt->bind_param("isssd", $proj, $clId, $psono, $refRemarks, $excessAmount);
                            $stmtStmt->execute();
                            $stmtStmt->close();
                        }
                        $financialMessage = " Refund of excess advance (₹" . number_format($excessAmount, 2) . ") has been recorded.";
                    } else {
                        $cnRes = $krfconn->query("SELECT MAX(creditnote_id) AS max_id FROM krf_creditnote");
                        $nextId = 1;
                        if ($cnRes && $cnRow = $cnRes->fetch_assoc()) {
                            $nextId = intval($cnRow['max_id'] ?? 0) + 1;
                        }
                        $creditnote_no = "CN-" . date('Y') . "-" . str_pad((string)$nextId, 5, '0', STR_PAD_LEFT);
                        $narration = "Credit Note of Rs. " . number_format($excessAmount, 2) . " (Partial Item Cancellation) for Client: " . ($clName ?: $clId) . " against Provisional Order " . $psono . ". Excess advance credited to client ledger. Reason: " . $remark . ". Processed by " . $loginname . " on " . date('d-m-Y H:i:s') . ".";
                        $stmtCn = $krfconn->prepare("INSERT INTO krf_creditnote (
                            creditnote_no, creditnote_date, projectid, companyid, subcompanyid,
                            clientid, clientname, psono, sono, amount_paid, credit_amount, narration, reason, status, created_by, created_at, updated_at
                        ) VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?, NOW(), NOW())");
                        if ($stmtCn) {
                            $stmtCn->bind_param("siiissssddsss", $creditnote_no, $proj, $comp, $subcomp, $clId, $clName, $psono, $sono, $excessAmount, $excessAmount, $narration, $remark, $loginname);
                            $stmtCn->execute();
                            $stmtCn->close();
                        }
                        $stmtStmt = $krfconn->prepare("INSERT INTO krf_tbl_client_statement (
                            project_id, client_id, txn_date, txn_type, txn_no, description, debit_amount, credit_amount, running_balance, created_at
                        ) VALUES (?, ?, CURDATE(), 'CREDIT_NOTE', ?, ?, 0.00, ?, 0.00, NOW())");
                        if ($stmtStmt) {
                            $stmtStmt->bind_param("isssd", $proj, $clId, $creditnote_no, $narration, $excessAmount);
                            $stmtStmt->execute();
                            $stmtStmt->close();
                        }
                        $financialMessage = " Credit Note (" . $creditnote_no . ") of excess advance (₹" . number_format($excessAmount, 2) . ") has been issued.";
                    }
                } else if ($financial_action === 'adjust_order' && $amount_paid > 0) {
                    $financialMessage = " Advance payment remains adjusted against remaining order items.";
                }
            }
        }
        
        if ($res) {
            $msg = count($itemIds) > 1 
                ? count($itemIds) . ' items cancelled successfully.' 
                : 'Item entry cancelled successfully.';
            if (!empty($financialMessage)) {
                $msg .= $financialMessage;
            }
            echo json_encode([
                'status' => 'success', 
                'message' => $msg,
                'cancelled_count' => count($itemIds),
                'all_cancelled' => $allCancelled
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to cancel the selected item(s).']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
} else if ($action == "UndoProvisionalOrder" || $action == "UndoRejectOrder") {
    $loginroleid = (isset($_SESSION['loginroleid']) && $_SESSION['loginroleid'] !== '') ? intval($_SESSION['loginroleid']) : (isset($_POST['loginroleid']) ? intval($_POST['loginroleid']) : 0);
    if ($loginroleid > 3) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized: Only users with role <= 3 can undo rejected orders.']);
        exit;
    }

    $projectid = intval($_POST['projectid'] ?? 0);
    $psono = trim($_POST['psono'] ?? '');
    $remark = trim($_POST['remark'] ?? ($_POST['remarks'] ?? ''));

    if (empty($psono)) {
        echo json_encode(['status' => 'error', 'message' => 'Provisional order number (PSONO) is missing.']);
        exit;
    }

    if (empty($remark)) {
        echo json_encode(['status' => 'error', 'message' => 'Undo reason / remark is required. Cannot restore order without a reason.']);
        exit;
    }

    // Parse item_ids (array, comma-separated string, or single item_id)
    $itemIds = [];
    if (isset($_POST['item_ids'])) {
        if (is_array($_POST['item_ids'])) {
            $itemIds = array_map('intval', $_POST['item_ids']);
        } else {
            $parts = explode(',', (string)$_POST['item_ids']);
            $itemIds = array_map('intval', $parts);
        }
    } else if (isset($_POST['item_id'])) {
        $itemIds = [intval($_POST['item_id'])];
    }
    $itemIds = array_filter(array_unique($itemIds), function($v) { return $v > 0; });

    // Fallback: if no specific itemIds provided, select all cancelled items for this psono
    if (empty($itemIds)) {
        $allChkQ = $krfconn->query("SELECT id FROM krf_provisional_sales_order_items WHERE psono = '{$krfconn->real_escape_string($psono)}' AND LOWER(TRIM(COALESCE(status, ''))) = 'cancelled'");
        if ($allChkQ) {
            while ($cRow = $allChkQ->fetch_assoc()) {
                $itemIds[] = intval($cRow['id']);
            }
        }
    }

    if (empty($itemIds)) {
        echo json_encode(['status' => 'error', 'message' => 'No cancelled item(s) found to undo.']);
        exit;
    }

    $loginid = $_SESSION['loginid'] ?? $_SESSION['userid'] ?? 0;
    $loginname = $_SESSION['loginname'] ?? $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'Admin';
    $userForUpdate = substr(!empty($loginname) ? (string)$loginname : (string)$loginid, 0, 45);

    $now = date('Y-m-d H:i:s');
    $curdate = date('Y-m-d');

    try {
        $krfconn->begin_transaction();

        // Fetch product_id and stockorder_no for the selected items
        $inClause = implode(',', $itemIds);
        $itemsRes = $krfconn->query("SELECT id, psono, product_id, stockorder_no, projectid FROM krf_provisional_sales_order_items WHERE id IN ($inClause)");
        $productIds = [];
        $stockOrderNos = [];
        if ($itemsRes) {
            while ($row = $itemsRes->fetch_assoc()) {
                if (!empty($row['product_id'])) {
                    $productIds[] = trim($row['product_id']);
                }
                if (!empty($row['stockorder_no'])) {
                    $stockOrderNos[] = trim($row['stockorder_no']);
                }
                if (empty($psono) && !empty($row['psono'])) {
                    $psono = trim($row['psono']);
                }
                if (empty($projectid) && !empty($row['projectid'])) {
                    $projectid = intval($row['projectid']);
                }
            }
        }
        $productIds = array_unique(array_filter($productIds));
        $stockOrderNos = array_unique(array_filter($stockOrderNos));

        // Determine target status: check advance payment or sales order existence
        $payQ = $krfconn->query("SELECT IFNULL(SUM(advanceamount), 0) AS total_paid FROM advance_payment_transactions WHERE invoiceno = '{$krfconn->real_escape_string($psono)}' AND (tedit IS NULL OR tedit = '') AND advanceamount > 0");
        $advancePaid = ($payQ && $pRow = $payQ->fetch_assoc()) ? floatval($pRow['total_paid']) : 0.0;

        $soQ = $krfconn->query("SELECT sono, id, status FROM krf_sales_order WHERE psono = '{$krfconn->real_escape_string($psono)}' LIMIT 1");
        $hasSo = ($soQ && $soQ->num_rows > 0);

        $targetStatus = ($advancePaid > 0 || $hasSo) ? 'Confirmed' : 'Draft';
        $itemTargetStatus = ($targetStatus === 'Confirmed') ? 'Confirmed' : NULL;
        $teditLog = "Rejection undone by " . $userForUpdate . " on " . $now . " - Reason: " . $remark;

        // 1. Update krf_provisional_sales_order_items
        $stmtItems = $krfconn->prepare("UPDATE krf_provisional_sales_order_items 
            SET status = ?, 
                remark = IF(remark IS NULL OR remark = '', ?, CONCAT(remark, ' | Rejection Undone: ', ?)), 
                tedit = ?, 
                updatedat = ?, 
                updatedby = ? 
            WHERE id IN ($inClause)");
        $stmtItems->bind_param("ssssss", $itemTargetStatus, $remark, $remark, $teditLog, $curdate, $userForUpdate);
        $stmtItems->execute();
        $stmtItems->close();

        // 2. Update krf_provisional_sales_order
        $undoLog = "[Rejection Undone by " . $userForUpdate . " on " . $now . " - Reason: " . $remark . "]";
        $stmtPso = $krfconn->prepare("UPDATE krf_provisional_sales_order 
            SET status = ?, 
                remarks = IF(remarks IS NULL OR remarks = '', ?, CONCAT(remarks, '\n', ?)), 
                updated_by = ?, 
                updated_at = NOW() 
            WHERE psono = ? AND (projectid = ? OR ? = 0)");
        $stmtPso->bind_param("sssssii", $targetStatus, $undoLog, $undoLog, $userForUpdate, $psono, $projectid, $projectid);
        $stmtPso->execute();
        $stmtPso->close();

        // If productIds and stockOrderNos are available, restore linked tables
        $escPsono = $krfconn->real_escape_string($psono);
        $escRemark = $krfconn->real_escape_string($remark);
        $escUser = $krfconn->real_escape_string($userForUpdate);

        $escProdIds = !empty($productIds) ? implode("','", array_map([$krfconn, 'real_escape_string'], $productIds)) : "";
        $escStockNos = !empty($stockOrderNos) ? implode("','", array_map([$krfconn, 'real_escape_string'], $stockOrderNos)) : "";

        // 3. Update krf_productionorder_consumed
        $consumedCondArr = [];
        if (!empty($escProdIds)) $consumedCondArr[] = "product_id IN ('$escProdIds')";
        if (!empty($escStockNos)) $consumedCondArr[] = "stockorderno IN ('$escStockNos')";
        if (!empty($consumedCondArr)) {
            $consumedWhere = implode(" OR ", $consumedCondArr);
            $cStatus = ($targetStatus === 'Confirmed') ? 'Confirmed' : 'pending';
            $krfconn->query("UPDATE krf_productionorder_consumed 
                SET confirmstatus = '$cStatus', 
                    status = 'pending', 
                    remark = IF(remark IS NULL OR remark = '', '$escRemark', CONCAT(remark, ' | Restored: ', '$escRemark')), 
                    updated_at = NOW() 
                WHERE psono = '$escPsono' AND ($consumedWhere)");
        }

        // 4. Update krf_production_stockorder (productid, stockorder_no)
        $stockCondArr = [];
        if (!empty($escProdIds)) $stockCondArr[] = "productid IN ('$escProdIds')";
        if (!empty($escStockNos)) $stockCondArr[] = "stockorder_no IN ('$escStockNos')";
        if (!empty($stockCondArr)) {
            $stockWhere = implode(" OR ", $stockCondArr);
            $stkStatus = ($targetStatus === 'Confirmed') ? 'Confirmed' : 'unconfirmed';
            $krfconn->query("UPDATE krf_production_stockorder 
                SET status = '$stkStatus', 
                    workingstatus = NULL, 
                    editedremark = IF(editedremark IS NULL OR editedremark = '', '$escRemark', CONCAT(editedremark, ' | Restored: ', '$escRemark')), 
                    editedby = '$escUser', 
                    editedon = '$curdate', 
                    updated_at = NOW() 
                WHERE psono = '$escPsono' AND ($stockWhere)");
        }

        // 5. Update krf_polyhouse_sowingschedule (productid, stockorder_no)
        $schCondArr = [];
        if (!empty($escProdIds)) $schCondArr[] = "productid IN ('$escProdIds')";
        if (!empty($escStockNos)) $schCondArr[] = "stockorder_no IN ('$escStockNos')";
        if (!empty($schCondArr)) {
            $schWhere = implode(" OR ", $schCondArr);
            $krfconn->query("UPDATE krf_polyhouse_sowingschedule 
                SET status = 'pending', 
                    remarks = IF(remarks IS NULL OR remarks = '', '$escRemark', CONCAT(remarks, ' | Restored: ', '$escRemark')), 
                    updated_by = '$escUser', 
                    updated_at = NOW() 
                WHERE psono = '$escPsono' AND ($schWhere) AND LOWER(TRIM(status)) IN ('cancelled', 'removed', 'delete')");

            // 6. Update krf_polyhouse_sowingplant_details (productid, stockorder_no)
            $krfconn->query("UPDATE krf_polyhouse_sowingplant_details 
                SET status = 'Active', 
                    updated_at = NOW(), 
                    updated_by = '$escUser' 
                WHERE psono = '$escPsono' AND ($schWhere) AND status = 'Removed'");
        }

        // 7 & 8. Update krf_sales_order & krf_sales_order_items (product_id, stockorder_no) if Sales Order exists
        if ($hasSo) {
            $soStatus = ($targetStatus === 'Confirmed') ? 'Confirmed' : 'Pending';
            $krfconn->query("UPDATE krf_sales_order 
                SET status = '$soStatus', 
                    remarks = IF(remarks IS NULL OR remarks = '', '$escRemark', CONCAT(remarks, '\n[Restored: ', '$escRemark', ']')), 
                    updated_by = '$escUser', 
                    updated_at = NOW() 
                WHERE psono = '$escPsono'");

            $soiCondArr = [];
            if (!empty($escProdIds)) $soiCondArr[] = "product_id IN ('$escProdIds')";
            if (!empty($escStockNos)) $soiCondArr[] = "stockorder_no IN ('$escStockNos')";
            if (!empty($soiCondArr)) {
                $soiWhere = implode(" OR ", $soiCondArr);
                $krfconn->query("UPDATE krf_sales_order_items 
                    SET status = '$soStatus', 
                        remark = IF(remark IS NULL OR remark = '', '$escRemark', CONCAT(remark, ' | Restored: ', '$escRemark')), 
                        ammendedby = '$escUser', 
                        updateamended = NOW() 
                    WHERE psono = '$escPsono' AND ($soiWhere)");
            }
        }

        // 9 & 10. Financial Reversals: Credit Notes & Refunds
        $cnCancelRemark = "Reversed upon Undo Rejection of Provisional Order " . $psono . " - Reason: " . $remark;
        $escCnRemark = $krfconn->real_escape_string($cnCancelRemark);

        $activeCns = [];
        $cnFindQ = $krfconn->query("SELECT creditnote_no FROM krf_creditnote WHERE psono = '$escPsono' AND status = 'Active'");
        if ($cnFindQ) {
            while ($cnF = $cnFindQ->fetch_assoc()) {
                $activeCns[] = $cnF['creditnote_no'];
            }
        }

        $krfconn->query("UPDATE krf_creditnote 
            SET status = 'Cancelled', 
                reason = IF(reason IS NULL OR reason = '', '$escCnRemark', CONCAT(reason, ' | ', '$escCnRemark')), 
                tedit = 'Cancelled on Undo Reject Order by $escUser on $now', 
                updated_at = NOW() 
            WHERE psono = '$escPsono' AND status = 'Active'");

        if (!empty($activeCns)) {
            $escCns = implode("','", array_map([$krfconn, 'real_escape_string'], $activeCns));
            $krfconn->query("UPDATE krf_tbl_client_statement 
                SET tedit = 'Reversed on Undo Reject Order by $escUser on $now' 
                WHERE (txn_no IN ('$escCns') OR (description LIKE '%$escPsono%' AND txn_type = 'CREDIT_NOTE')) AND (tedit IS NULL OR tedit = '')");
        }

        // Reverse any refund in advance_payment_transactions & statement
        $krfconn->query("UPDATE advance_payment_transactions 
            SET tedit = 'Reversed on Undo Reject Order by $escUser on $now', 
                remarks = CONCAT(IFNULL(remarks, ''), ' | Reversed on Undo Reject') 
            WHERE invoiceno = '$escPsono' AND purposeofpayment = 'refund' AND (tedit IS NULL OR tedit = '')");

        $krfconn->query("UPDATE krf_tbl_client_statement 
            SET tedit = 'Reversed on Undo Reject Order by $escUser on $now' 
            WHERE txn_no = '$escPsono' AND txn_type = 'WRITE_OFF' AND (tedit IS NULL OR tedit = '')");

        // 11. Audit Trail: Insert into krf_provisional_order_followup
        $psoClQ = $krfconn->query("SELECT clientid, projectid FROM krf_provisional_sales_order WHERE psono = '$escPsono' LIMIT 1");
        $clRow = ($psoClQ && $r = $psoClQ->fetch_assoc()) ? $r : [];
        $orderClientId = $clRow['clientid'] ?? '';
        $orderProjId = !empty($clRow['projectid']) ? intval($clRow['projectid']) : $projectid;

        $fuStmt = $krfconn->prepare("INSERT INTO krf_provisional_order_followup (projectid, psono, clientid, reason, conversation, created_by, created_by_name, created_at) VALUES (?, ?, ?, 'Undo Rejection', ?, ?, ?, NOW())");
        if ($fuStmt) {
            $fuStmt->bind_param("isssss", $orderProjId, $psono, $orderClientId, $remark, $userForUpdate, $loginname);
            $fuStmt->execute();
            $fuStmt->close();
        }

        $krfconn->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'Provisional order (' . $psono . ') rejection has been undone and status restored successfully.',
            'restored_items_count' => count($itemIds)
        ]);
    } catch (Exception $e) {
        $krfconn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
} else if ($action == "CheckProductNameExist") {
    $projectid = trim($_POST['projectid']);
    $ProductName = trim($_POST['productname']);
    $sql = $krfconn->query("SELECT * FROM product_master WHERE lcase(Status) = 'active' AND ProjectId = '" . $projectid . "' AND ProductName ='" . $ProductName . "'");
    $rrst = [];
    if ($sql && $sql->num_rows > 0) {
        $rrst = array('status' => 'duplicate', 'message' => 'Name' . $ProductName . ' is already exist as Product Name');
    } else {
        $rrst = array('status' => 'notexist', 'message' => 'Product Name' . $ProductName . ' is not exist in Product Master');
    }
    echo json_encode($rrst);
} else if ($action == "saveupdateProductdetails") {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Collect form data
        $ProductID = $_POST['ProductID'] ?? null; // Hidden field for update
        $ProductName = trim($_POST['productname']);
        $CategoryId = $_POST['ProductCategory'] ?? '';
        $SubCategoryId = $_POST['ProductSubCategory'] ?? '';
        $IsGrafted = $_POST['isgrafted'] ?? ''; // YES/NO
        if ($IsGrafted == "Grafted") {
            $SeedId = $_POST['rootseedname'] ?? '';
            $ScionSeedId = $_POST['scionseedname'] ?? '';
        } else {
            $SeedId = $_POST['rootseedname'] ?? '';
            $ScionSeedId = $SeedId;
        }
        $SuggestedProductSKU = $_POST['productsuggestedsku'];
        $ProjectId = $_POST['projectid'] ?? '';
        $CreatedBy = $_POST['loginid'] ?? '';
        $PriceOfProduct = $_POST['suggestedprice'] ?? 0;

        // Basic validation
        if (empty($ProductName) || empty($CategoryId) || empty($IsGrafted) || empty($SeedId)) {
            echo json_encode(['status' => 'error', 'message' => 'Required fields are missing.']);
            exit;
        }

        try {
            // Check for duplicate product name within same project and category
            $dupCheckSql = "SELECT ProductID FROM product_master WHERE ProductName = ? AND ProjectId = ? AND CategoryId = ? AND SeedId =? AND ScionSeedId =?";
            $dupStmt = $krfconn->prepare($dupCheckSql);
            $dupStmt->bind_param("sssss", $ProductName, $ProjectId, $CategoryId, $SeedId, $ScionSeedId);
            $dupStmt->execute();
            $dupStmt->store_result();
            if ($dupStmt->num_rows > 0 && !$ProductID) {
                // Insert mode and duplicate exists
                echo json_encode(['status' => 'error', 'message' => 'Duplicate product name found for this project and category.']);
                exit;
            } elseif ($dupStmt->num_rows > 0 && $ProductID) {
                // Update mode: Ensure the duplicate is not the same row
                $dupStmt->bind_result($existingProductID);
                $dupStmt->fetch();
                if ($existingProductID != $ProductID) {
                    echo json_encode(['status' => 'error', 'message' => 'Duplicate product name found for this project and category.']);
                    exit;
                }
            }
            $dupStmt->close();

            // Start transaction
            $krfconn->begin_transaction();

            if ($ProductID) {
                // UPDATE existing product
                $sql = "UPDATE product_master SET 
                          ProductName = ?, 
                          CategoryId = ?, 
                          ProductTypeID = ?,
                          SeedId = ?,
                          ScionSeedId = ?, 
                          IsGrafted = ?, 
                          ProjectId = ?, 
                          PriceOfProduct = ?,
                          UpdateAt = NOW()
                      WHERE ProductID = ?";
                $stmt = $krfconn->prepare($sql);
                if (!$stmt)
                    throw new Exception("Prepare failed: " . $krfconn->error);

                $stmt->bind_param(
                    "ssssssssi",
                    $ProductName,
                    $CategoryId,
                    $SubCategoryId,
                    $SeedId,
                    $ScionSeedId,
                    $IsGrafted,
                    $ProjectId,
                    $PriceOfProduct,
                    $ProductID
                );

            } else {
                // INSERT new product
                $prosku = "";
                if ($SuggestedProductSKU) {
                    $prosku = $SuggestedProductSKU;
                } else {
                    $prdsku = generateProductSKU($krfconn, $ProjectId, $SeedId, $IsGrafted);
                    if ($prdsku['status'] === "success") {
                        $prosku = $prdsku['sku'];
                    } else {
                        throw new Exception('Something went wrong or Product of seed is missing, Try again.');
                    }
                }

                $sql = "INSERT INTO product_master 
                          (sku, ProductName, CategoryId, ProductTypeID, SeedId, ScionSeedId, IsGrafted, ProjectId, Status, CreatedAt, CreatedBy, PriceOfProduct) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Active', NOW(), ?, ?)";
                $stmt = $krfconn->prepare($sql);
                if (!$stmt)
                    throw new Exception("Prepare failed: " . $krfconn->error);

                $stmt->bind_param(
                    "ssssssssss",
                    $prosku,
                    $ProductName,
                    $CategoryId,
                    $SubCategoryId,
                    $SeedId,
                    $ScionSeedId,
                    $IsGrafted,
                    $ProjectId,
                    $CreatedBy,
                    $PriceOfProduct
                );
            }

            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            $krfconn->commit();
            $stmt->close();

            echo json_encode([
                'status' => 'success',
                'message' => $ProductID ? 'Product updated successfully.' : 'Product inserted successfully.'
            ]);

        } catch (Exception $e) {
            $krfconn->rollback();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }

    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    }
} else if ($action == "RemoveProductMaster") {
    $response = [];
    try {

        if (empty($_POST['productid'])) {
            echo json_encode([
                "status" => "error",
                "message" => "Product ID is missing."
            ]);
            exit;
        }

        $productid = (int) $_POST['productid'];
        $projectid = $_POST['projectid'] ?? '';
        $loginname = $_POST['loginname'] ?? '';
        $updatedAt = date("Y-m-d H:i:s");

        // ✅ Step 1: Check Product Exists
        $check_sql = "SELECT Status 
                    FROM product_master 
                    WHERE ProductID = ? 
                    AND ProjectId = ?";

        $stmt = $krfconn->prepare($check_sql);

        if (!$stmt) {
            throw new Exception($krfconn->error);
        }

        $stmt->bind_param("is", $productid, $projectid);

        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }

        $stmt->store_result();

        if ($stmt->num_rows == 0) {
            echo json_encode([
                "status" => "error",
                "message" => "Product not found."
            ]);
            $stmt->close();
            exit;
        }

        $stmt->bind_result($status);
        $stmt->fetch();

        if ($status !== 'Active') {
            echo json_encode([
                "status" => "error",
                "message" => "Product already inactive."
            ]);
            $stmt->close();
            exit;
        }

        $stmt->close();

        // ✅ Step 2: Soft Delete (Update Status)
        $update_sql = "UPDATE product_master 
                     SET Status = 'Inactive',
                         UpdateAt = ?
                     WHERE ProductID = ?
                     AND ProjectId = ?";

        $stmt = $krfconn->prepare($update_sql);

        if (!$stmt) {
            throw new Exception($krfconn->error);
        }

        $stmt->bind_param(
            "sis",
            $updatedAt,
            $productid,
            $projectid
        );

        if ($stmt->execute()) {

            echo json_encode([
                "status" => "success",
                "message" => "Product marked as Inactive successfully."
            ]);

        } else {

            throw new Exception($stmt->error);
        }

        $stmt->close();

    } catch (Exception $e) {

        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage()   // Keep for debugging
        ]);
    }
} else if ($action == "saveupdategrowthStageForm") {
    $id = $_POST['id'] ?? "";
    $productid = $_POST['productid'] ?? null;
    $sessionalCorrectionDays = $_POST['SessionalCorrectionDays'] ?? '{}';
    $decoded = json_decode($sessionalCorrectionDays, true);
    // print_r($decoded);
    if (!$productid) {
        echo json_encode(["status" => "error", "message" => "Product ID missing"]);
        return;
    }
    // Map POST fields to array
    $data = [
        "productid" => $_POST["productid"],
        "projectid" => $_POST["projectid"],
        "fullcycledays" => $_POST["fullcycledays"],
        "growingdays" => 1,
        "SeedGerminationdays" => $_POST["SeedGerminationdays"],
        "ScianSeedGerminationdays" => $_POST["ScianSeedGerminationdays"],
        "EarlySeedlingdays" => $_POST["EarlySeedlingdays"],
        "TrueLeafSeedlingdays" => $_POST["TrueLeafSeedlingdays"],
        "GraftingOperationdays" => $_POST["GraftingOperationdays"],
        "HealingChamberdays" => $_POST["HealingChamberdays"],
        "Hardeningdays" => $_POST["Hardeningdays"],
        "ClipRemovaldays" => $_POST["ClipRemovaldays"],
        "SecondaryHardeningdays" => $_POST["SecondaryHardeningdays"],
        "SessionalCorrectionDays" => $sessionalCorrectionDays,
        "SessionalName" => $_POST["SessionCorrectionName"] ?? "",
        "loginid" => $_POST["loginid"]
    ];
    if ($id == "" || $id == "0") {
        insertGrowthStage($krfconn, $data);
    } else {
        updateGrowthStage($krfconn, $data, $id);
    }
} else if ($action == "GetSuggestedProductSKU") {
    $SeedId = $_POST['SeedID'];
    $projectid = $_POST['projectid'];
    $IsGraftedstatus = $_POST['IsGraftedstatus'];

    $prdsku = generateProductSKU($krfconn, $projectid, $SeedId, $IsGraftedstatus);
    if ($prdsku['status'] == "success") {
        $prosku = $prdsku['sku'];
    }
    echo json_encode($prosku);
} else if ($action == "GetExistingProductPrices") {
    $subcategoryId = $_POST['ProductTypeID'] ?? $_POST['SubCategoryID'] ?? '';
    $isGrafted = $_POST['IsGrafted'] ?? $_POST['isgrafted'] ?? '';
    $categoryId = $_POST['CategoryID'] ?? $_POST['CategoryId'] ?? '';
    $projectId = $_POST['projectid'] ?? '';

    $whereClauses = ["PriceOfProduct > 0"];
    $params = [];
    $types = "";

    if (!empty($subcategoryId)) {
        $whereClauses[] = "ProductTypeID = ?";
        $params[] = $subcategoryId;
        $types .= "s";
    }
    if (!empty($isGrafted)) {
        $whereClauses[] = "IsGrafted = ?";
        $params[] = $isGrafted;
        $types .= "s";
    }
    if (!empty($categoryId)) {
        $whereClauses[] = "CategoryId = ?";
        $params[] = $categoryId;
        $types .= "s";
    }

    $whereSql = implode(" AND ", $whereClauses);
    $sql = "SELECT PriceOfProduct, COUNT(*) as usage_count 
            FROM product_master 
            WHERE $whereSql 
            GROUP BY PriceOfProduct 
            ORDER BY usage_count DESC, PriceOfProduct ASC 
            LIMIT 10";

    $stmt = $krfconn->prepare($sql);
    if ($stmt) {
        if (!empty($types)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $prices = [];
        while ($row = $result->fetch_assoc()) {
            $prices[] = [
                'price' => number_format((float)$row['PriceOfProduct'], 2, '.', ''),
                'usage_count' => (int)$row['usage_count']
            ];
        }
        $stmt->close();
        echo json_encode($prices);
    } else {
        echo json_encode([]);
    }
    exit;
} else if ($action == "ProductSubCategoryFrommaster") {
    $projectid = $_POST['projectid'] ?? 0;
    $shortby = $_POST['shortby'] ?? '';
    $Shortbyvalue = $_POST['Shortbyvalue'] ?? $_POST['shortbyvalue'] ?? '';
    if (empty($shortby) || $shortby == "undefined") {
        $shortby = "all";
    }
    if ($shortby == "SeedCategory") {
        $shortby = "CategoryID";
    }
    $subcatlist = getSubCategoryData($krfconn, $projectid, $shortby, $Shortbyvalue);
    if (empty($subcatlist) && !empty($Shortbyvalue) && $shortby == "CategoryID") {
        $sql = "SELECT * FROM plant_subcategory_master WHERE Status = 'Active' AND CategoryID = ?";
        $stmt = $krfconn->prepare($sql);
        $stmt->bind_param("s", $Shortbyvalue);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $subcatlist[] = $row;
        }
        $stmt->close();
    }
    echo json_encode($subcatlist);
} else if ($action == "saveupdateSubCategorydetails") {
    $ProjectId = $_POST['projectid'] ?? 0;
    $CategoryID = $_POST['nsbcCategoryID'] ?? 0;
    $SubCategoryName = $_POST['SubCategoryName'] ?? 0;
    $Description = $_POST['DescriptionSubCategory'] ?? 0;
    $CreatedBy = $_POST['loginid'] ?? 0;
    $SubCategoryID = $_POST['SubCategoryID'] ?? '';
    $Status = "active";
    $rrst = saveSubCategory($krfconn, $SubCategoryName, $Description, $CategoryID, $ProjectId, $Status, $CreatedBy, $SubCategoryID);
    echo json_encode($rrst);
} else if ($action == "SaveUpdateProductStockOrder") {
    // This action is now for NEW orders only
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        try {
            $krfconn->begin_transaction();

            $companyid = $_POST['companyid'];
            $subcompanyid = $_POST['subcompanyid'];
            $projectid = $_POST['projectid'];
            $clientid = $_POST['productionforclient'];
            $loginid = $_POST['loginid'];
            $created_by = $loginid;

            $productlist = $_POST['productname'] ?? [];
            $quantities = $_POST['quantity'] ?? [];
            $expecteddelivery = $_POST['expecteddelivery'] ?? [];
            $sowingdates = $_POST['sowingdate'] ?? [];
            $removebuffer = $_POST['removebuffer_val'] ?? $_POST['removebuffer'] ?? [];
            $sentry = 0;
            $allCropDetails = [];
            $allOrderNos = [];

            for ($i = 0; $i < count($productlist); $i++) {
                // Generate a fresh unique number for EACH item in the order
                $production_stockorder_no = getNewProductionStockOrderNo($krfconn, $companyid, $projectid);
                $allOrderNos[] = $production_stockorder_no;

                $prdin = getProductData($krfconn, $projectid, "ProductID", trim($productlist[$i]));
                if (empty($prdin)) {
                    throw new Exception("Product not found for ID: " . $productlist[$i]);
                }
                $productid = trim($productlist[$i]);
                $productName = $prdin[0]['ProductName'];

                $isBufferRemoved = (isset($removebuffer[$i]) && ($removebuffer[$i] == '1' || $removebuffer[$i] === 1 || $removebuffer[$i] === 'true' || $removebuffer[$i] === 'on'));
                $bufferRemark = $isBufferRemoved ? "Buffer: Removed (-7 Days)" : "Buffer: Added (+7 Days)";

                $data = [
                    "stockorder_no" => $production_stockorder_no,
                    "projectid" => $projectid,
                    "companyid" => $companyid,
                    "subcompanyid" => $subcompanyid,
                    "clientid" => $clientid,
                    "productid" => $productid,
                    "product_name" => $productName,
                    "quantity" => (float) $quantities[$i],
                    "expected_delivery_date" => $expecteddelivery[$i],
                    "sowingdate" => $sowingdates[$i] ?? '',
                    "scionsowingdate" => $sowingdates[$i] ?? '',
                    "remarks" => $bufferRemark,
                    "status" => "Confirmed",
                    "created_by" => $loginid
                ];

                $svdata = saveProductionStockOrder($krfconn, $data, false); // false = don't commit inside
                if ($svdata['status'] !== "success") {
                    throw new Exception($svdata['message']); // force rollback
                }

                $allCropDetails[] = [
                    "cropname"     => $productName,
                    "qty"          => (float) $quantities[$i],
                    "unit"         => "Plants",
                    "deliverydate" => $expecteddelivery[$i]
                ];
                
                $sentry++;
            }

            if ($sentry > 0) {
                $krfconn->commit();
                
                // Send single WhatsApp for all products in the order
                $wa_error = "";
                $orderno_display = implode(", ", $allOrderNos);
                try {
                    sendProductionStockOrderWhatsApp($krfconn, "Production Unit", $orderno_display, date('Y-m-d'), $allCropDetails, "", "", "hi");
                } catch (Exception $e_wa) {
                    $wa_error = $e_wa->getMessage();
                    error_log("WhatsApp Error: " . $wa_error);
                }
                
                $response_msg = "Production order(s) $orderno_display saved successfully with $sentry items.";
                if ($wa_error) {
                    $response_msg .= " (Notification Error: $wa_error)";
                }

                echo json_encode(['status' => 'success', 'message' => $response_msg, 'items' => ['stockorder_no' => $orderno_display]]);
            } else {
                throw new Exception("No entries saved for the new order.");
            }
        } catch (Exception $e) {
            $krfconn->rollback();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
} 

if (!function_exists('getNonGraftedProductForSeed')) {
    function getNonGraftedProductForSeed($krfconn, $projectid, $seedid, $isScion = false, $rootProductId = null, $mainProductName = '') {
        $prefix = $isScion ? "NonGrafted-Scion, " : "NonGrafted-Rootstock, ";
        
        if (!empty($seedid) && $seedid != '0') {
            // 1. Search in product_master for a Non-Grafted product linked to this seed (strictly excluding Grafted products)
            $excludeSql = ($rootProductId && (int)$rootProductId > 0) ? " AND ProductID != " . (int)$rootProductId : "";
            $seedCol = $isScion ? "ScionSeedId" : "SeedId";
            $sql = "SELECT ProductID, ProductName FROM product_master 
                    WHERE ProjectId = ? AND ($seedCol = ? OR SeedId = ? OR ScionSeedId = ?) 
                      AND (IsGrafted = 'Non-Grafted' OR IsGrafted = 'NonGrafted' OR IsGrafted = '0' OR IsGrafted = 0 OR IsGrafted IS NULL)
                      AND LCASE(ProductName) NOT LIKE '%grafted%'
                      " . $excludeSql . "
                    ORDER BY ProductID ASC 
                    LIMIT 1";
            $stmt = $krfconn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("isss", $projectid, $seedid, $seedid, $seedid);
                $stmt->execute();
                $res = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if (!empty($res['ProductID']) && !empty($res['ProductName'])) {
                    $cleanName = preg_replace('/^Grafted[-\s,.]*/i', '', $res['ProductName']);
                    if (stripos($cleanName, 'NonGrafted') === false) {
                        $cleanName = $prefix . $cleanName;
                    }
                    return [
                        'ProductID' => $res['ProductID'],
                        'ProductName' => $cleanName
                    ];
                }
            }
            
            // 2. Fallback: Lookup in seed_master if no dedicated non-grafted product_master row found
            $sql_sm = "SELECT SeedID as ProductID, SeedName as ProductName, VarietyofSeed, BrandName FROM seed_master WHERE ProjectId = ? AND (SeedID = ? OR sku = ?) LIMIT 1";
            $stmt_sm = $krfconn->prepare($sql_sm);
            if ($stmt_sm) {
                $stmt_sm->bind_param("iss", $projectid, $seedid, $seedid);
                $stmt_sm->execute();
                $sm_res = $stmt_sm->get_result()->fetch_assoc();
                $stmt_sm->close();
                if ($sm_res && !empty($sm_res['ProductName'])) {
                    $sName = $sm_res['ProductName'];
                    if (!empty($sm_res['VarietyofSeed']) && stripos($sName, $sm_res['VarietyofSeed']) === false) {
                        $sName .= ", " . $sm_res['VarietyofSeed'];
                    }
                    if (!empty($sm_res['BrandName']) && stripos($sName, $sm_res['BrandName']) === false) {
                        $sName .= " (" . $sm_res['BrandName'] . ")";
                    }
                    $sName = preg_replace('/^Grafted[-\s,.]*/i', '', $sName);
                    return [
                        'ProductID' => $sm_res['ProductID'],
                        'ProductName' => $prefix . $sName
                    ];
                }
            }
        }
        
        // 3. Ultimate Fallback: Clean main product name and apply NonGrafted prefix
        $displayName = !empty($mainProductName) ? preg_replace('/^Grafted[-\s,.]*/i', '', $mainProductName) : (!empty($seedid) ? $seedid : 'Seed');
        return [
            'ProductID' => !empty($seedid) ? $seedid : ($isScion ? 'SCION' : 'ROOT'),
            'ProductName' => $prefix . $displayName
        ];
    }
}

if (!function_exists('insertOrUpdateOpenStockOrder')) {
    function insertOrUpdateOpenStockOrder($krfconn, $orderData) {
        if (empty($orderData['stockorder_no'])) return false;

        $sowingbalqty = isset($orderData['sowingbalqty']) ? (float)$orderData['sowingbalqty'] : (float)$orderData['quantity'];
        $scionsowingstatus = $orderData['scionsowingstatus'] ?? 'pending';

        // Resolve Trader Client ID from krf_client_master where projectid=? and is_trader=1
        $target_client_id = trim($orderData['clientid'] ?? '');
        if (empty($target_client_id) || $target_client_id == '0') {
            $sql_trader = "SELECT client_id FROM krf_client_master WHERE (projectid = ? OR ProjectId = ?) AND (is_trader = 1 OR is_trader = '1' OR LCASE(IFNULL(is_trader,'')) IN ('yes','trader','true')) LIMIT 1";
            $stmt_tr = $krfconn->prepare($sql_trader);
            if ($stmt_tr) {
                $pid = (int)($orderData['projectid'] ?? 0);
                $stmt_tr->bind_param("ii", $pid, $pid);
                $stmt_tr->execute();
                $res_tr = $stmt_tr->get_result()->fetch_assoc();
                $stmt_tr->close();
                if (!empty($res_tr['client_id'])) {
                    $target_client_id = $res_tr['client_id'];
                }
            }
        }
        if (empty($target_client_id) || $target_client_id == '0') {
            $sql_trader2 = "SELECT client_id FROM krf_client_master WHERE (is_trader = 1 OR is_trader = '1' OR LCASE(IFNULL(is_trader,'')) IN ('yes','trader','true')) LIMIT 1";
            $stmt_tr2 = $krfconn->prepare($sql_trader2);
            if ($stmt_tr2) {
                $stmt_tr2->execute();
                $res_tr2 = $stmt_tr2->get_result()->fetch_assoc();
                $stmt_tr2->close();
                if (!empty($res_tr2['client_id'])) {
                    $target_client_id = $res_tr2['client_id'];
                }
            }
        }
        if (empty($target_client_id) || $target_client_id == '0') {
            $sql_trader3 = "SELECT client_id FROM krf_client_master WHERE IFNULL(client_id,'') != '' AND client_id != '0' LIMIT 1";
            $stmt_tr3 = $krfconn->prepare($sql_trader3);
            if ($stmt_tr3) {
                $stmt_tr3->execute();
                $res_tr3 = $stmt_tr3->get_result()->fetch_assoc();
                $stmt_tr3->close();
                if (!empty($res_tr3['client_id'])) {
                    $target_client_id = $res_tr3['client_id'];
                }
            }
        }

        $check_sql = "SELECT id FROM krf_production_stockorder WHERE stockorder_no = ? AND projectid = ? LIMIT 1";
        $stmt_c = $krfconn->prepare($check_sql);
        if ($stmt_c) {
            $stmt_c->bind_param("si", $orderData['stockorder_no'], $orderData['projectid']);
            $stmt_c->execute();
            $res_c = $stmt_c->get_result()->fetch_assoc();
            $stmt_c->close();

            if ($res_c && !empty($res_c['id'])) {
                $upd_sql = "UPDATE krf_production_stockorder SET 
                            clientid = ?, productid = ?, product_name = ?, quantity = ?, sowingbalqty = ?, 
                            sowinglocationid = ?, roottrayid = ?, sciontrayid = ?, scionsowingdate = ?, 
                            scionsowingstatus = ?, expected_delivery_date = ?, sowingdate = ?, updated_at = NOW() 
                            WHERE id = ?";
                $stmt_u = $krfconn->prepare($upd_sql);
                if ($stmt_u) {
                    $stmt_u->bind_param("sssddsssssssi", 
                        $target_client_id,
                        $orderData['productid'], 
                        $orderData['product_name'], 
                        $orderData['quantity'], 
                        $sowingbalqty, 
                        $orderData['sowinglocationid'], 
                        $orderData['roottrayid'], 
                        $orderData['sciontrayid'], 
                        $orderData['scionsowingdate'], 
                        $scionsowingstatus, 
                        $orderData['expected_delivery_date'], 
                        $orderData['sowingdate'], 
                        $res_c['id']
                    );
                    $stmt_u->execute();
                    $stmt_u->close();
                }
                return true;
            }
        }

        $ins_sql = "INSERT INTO krf_production_stockorder 
                    (stockorder_no, projectid, companyid, subcompanyid, clientid, 
                     productid, product_name, quantity, sowingbalqty,
                     sowinglocationid, roottrayid, sciontrayid, scionsowingdate, scionsowingstatus,
                     expected_delivery_date, sowingdate, remarks, status, created_by, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt_i = $krfconn->prepare($ins_sql);
        if ($stmt_i) {
            $stmt_i->bind_param("siiiissddssssssssss",
                $orderData['stockorder_no'],
                $orderData['projectid'],
                $orderData['companyid'],
                $orderData['subcompanyid'],
                $target_client_id,
                $orderData['productid'],
                $orderData['product_name'],
                $orderData['quantity'],
                $sowingbalqty,
                $orderData['sowinglocationid'],
                $orderData['roottrayid'],
                $orderData['sciontrayid'],
                $orderData['scionsowingdate'],
                $scionsowingstatus,
                $orderData['expected_delivery_date'],
                $orderData['sowingdate'],
                $orderData['remarks'],
                $orderData['status'],
                $orderData['created_by']
            );
            $stmt_i->execute();
            $stmt_i->close();
            return true;
        }
        return false;
    }
}
// Dedicated standalone PHP Action Handler for Condition 2 (Edited qty with same PO & rest of qty create new PO)
if (!function_exists('handleCondition2StockOrderEdit')) {
function handleCondition2StockOrderEdit($krfconn, $projectid, $companyid, $subcompanyid, $loginid, $old, $post_qty, $post_edd, $post_sd, $edit_reason, $new_prod_id, $target_client_id, $new_sowing_date, $new_scion_sowing_date, $root_sowed, $scion_sowed, $new_expected_delivery = null) {
    $production_stockorder_no = $old['stockorder_no'];
    $productid = $old['productid'];
    $old_qty = (float)$old['quantity'];
    $rest_qty = max(0, $old_qty - $post_qty);
    $remark_str = !empty($edit_reason) ? " [Edit Reason: " . $edit_reason . "]" : "";

    $root_sowing_started = ($root_sowed > 0);
    $scion_sowing_started = ($scion_sowed > 0);
    $sowing_started = ($root_sowing_started || $scion_sowing_started);

    // STEP 01: Sowing NOT started of Root Seed ($root_sowing_started == false)
    if (!$root_sowing_started) {
        // 1. Update existing production stock order quantity & metadata
        $upd_sql = "UPDATE krf_production_stockorder SET quantity = ?, expected_delivery_date = ?, sowingdate = ?, remarks = CONCAT(IFNULL(remarks,''), ?), editedqty = ?, editedremark = ?, editedby = ?, editedon = NOW(), updated_at = NOW() WHERE id = ?";
        $stmt_upd = $krfconn->prepare($upd_sql);
        if ($stmt_upd) {
            $stmt_upd->bind_param("dsssdssi", $post_qty, $post_edd, $post_sd, $remark_str, $post_qty, $edit_reason, $loginid, $old['id']);
            $stmt_upd->execute();
            $stmt_upd->close();
        }

        // 1b. Update or Insert sowing schedule in krf_polyhouse_sowingschedule for Parent Stock Order
        $chk_sch = $krfconn->prepare("SELECT sowingschedulid FROM krf_polyhouse_sowingschedule WHERE stockorder_no = ? AND projectid = ? LIMIT 1");
        if ($chk_sch) {
            $chk_sch->bind_param("si", $production_stockorder_no, $projectid);
            $chk_sch->execute();
            $res_sch = $chk_sch->get_result()->fetch_assoc();
            $chk_sch->close();
            if ($res_sch) {
                $new_sowing_qty = (int)ceil($post_qty * 1.30);
                $upd_sch_sql = "UPDATE krf_polyhouse_sowingschedule SET orderqty = ?, sowingqty = ?, sowing_date = ?, updated_at = NOW() WHERE stockorder_no = ? AND projectid = ?";
                $stmt_upd_sch = $krfconn->prepare($upd_sch_sql);
                if ($stmt_upd_sch) {
                    $stmt_upd_sch->bind_param("iisss", $post_qty, $new_sowing_qty, $post_sd, $production_stockorder_no, $projectid);
                    $stmt_upd_sch->execute();
                    $stmt_upd_sch->close();
                }
            } else {
                createSowingScheduleForNewStockOrder($krfconn, $projectid, $production_stockorder_no, $old['productid'], $old['product_name'], $post_qty, $post_sd, $old['scionsowingdate'] ?? null, $loginid);
            }
        }

        // 2. If remaining balance exists ($rest_qty > 0), create new production stock order for selected dropdown product
        if ($rest_qty > 0) {
            $target_prod_id = !empty($new_prod_id) ? trim($new_prod_id) : trim($productid);

            // Resolve Product Name for selected product from product_master
            $target_prod_name = '';
            if (!empty($target_prod_id)) {
                $stmt_pn = $krfconn->prepare("SELECT ProductName FROM product_master WHERE (ProductID = ? OR sku = ?) AND (ProjectId = ? OR ProjectId = 0 OR ProjectId IS NULL) ORDER BY ProjectId DESC LIMIT 1");
                if ($stmt_pn) {
                    $stmt_pn->bind_param("ssi", $target_prod_id, $target_prod_id, $projectid);
                    $stmt_pn->execute();
                    $res_pn = $stmt_pn->get_result()->fetch_assoc();
                    $stmt_pn->close();
                    if ($res_pn) {
                        $target_prod_name = $res_pn['ProductName'];
                    }
                }
            }
            if (empty($target_prod_name)) {
                $target_prod_name = $old['product_name'] ?? 'Production Product';
            }

            // Generate new unique production stock order number
            $new_stockorder_no = getNewProductionStockOrderNo($krfconn, $companyid, $projectid);

            // Effective expected delivery date for the new order
            $eff_new_edd = !empty($new_expected_delivery) ? $new_expected_delivery : $post_edd;

            // Recalculate sowing & internal dispatch dates using backend formula ONLY IF NOT PROVIDED FROM DISPLAY FORM
            if (empty($new_sowing_date) || empty($new_scion_sowing_date)) {
                if (function_exists('calculateSowingAndInternalDispatchDates')) {
                    $c_dates = calculateSowingAndInternalDispatchDates($krfconn, $eff_new_edd, $projectid, $target_prod_id);
                    if (!empty($c_dates) && isset($c_dates['status']) && $c_dates['status'] === 'success') {
                        if (empty($new_sowing_date) && !empty($c_dates['RootSeed']['sowingdate'])) {
                            $new_sowing_date = $c_dates['RootSeed']['sowingdate'];
                        }
                        if (empty($new_scion_sowing_date) && !empty($c_dates['ScionSeed']['sowingdate'])) {
                            $new_scion_sowing_date = $c_dates['ScionSeed']['sowingdate'];
                        }
                    }
                }
            }

            // Insert new production stock order
            $old_psono = $old['psono'] ?? '';
            $ins_new_sql = "INSERT INTO krf_production_stockorder (psono, stockorder_no, projectid, companyid, subcompanyid, clientid, productid, product_name, quantity, expected_delivery_date, sowingdate, scionsowingdate, remarks, editedqty, editedremark, editedby, editedon, status, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'Confirmed', ?, NOW(), NOW())";
            $stmt_ins_new = $krfconn->prepare($ins_new_sql);
            if ($stmt_ins_new) {
                $new_remarks = "Created from balance split of " . $production_stockorder_no . $remark_str;
                $stmt_ins_new->bind_param("ssiiisssdssssdsss", $old_psono, $new_stockorder_no, $projectid, $companyid, $subcompanyid, $target_client_id, $target_prod_id, $target_prod_name, $rest_qty, $eff_new_edd, $new_sowing_date, $new_scion_sowing_date, $new_remarks, $rest_qty, $edit_reason, $loginid, $loginid);
                $stmt_ins_new->execute();
                $stmt_ins_new->close();
            }

            // Create sowing schedule in krf_polyhouse_sowingschedule for the new stock order
            createSowingScheduleForNewStockOrder($krfconn, $projectid, $new_stockorder_no, $target_prod_id, $target_prod_name, $rest_qty, $new_sowing_date, $new_scion_sowing_date, $loginid);
        }

        return [
            "status" => "success",
            "message" => "Production order qty update successfully",
            "items" => [
                "stockorder_no" => $production_stockorder_no,
                "productid" => $productid,
                "quantity" => $post_qty,
                "rest_qty" => $rest_qty
            ]
        ];
    }

    // =========================================================================
    // STEP 02: Root Sowing is Started / Done ($root_sowing_started == true)
    // =========================================================================
    if ($root_sowing_started) {
        $consumed_qty = (float)getTotalConsumedQtyForPSO($krfconn, $projectid, $production_stockorder_no, $productid);

        // 1. Max Limit Validation: Edited qty cannot be > sowed root qty
        if ($post_qty > $root_sowed) {
            return [
                "status" => "error",
                "message" => "Sowing of Root seed is started/done in polyhouse. Edited quantity (" . number_format($post_qty) . ") cannot be greater than sowed root quantity (" . number_format($root_sowed) . " plants)."
            ];
        }

        // 2. Min Limit Validation: Edited qty cannot be < consumed qty
        if ($consumed_qty > 0 && $post_qty < $consumed_qty) {
            return [
                "status" => "error",
                "message" => "Edited quantity (" . number_format($post_qty) . ") cannot be less than consumed quantity (" . number_format($consumed_qty) . " plants)."
            ];
        }

        // 3. Update original production stock order quantity
        $upd_sql = "UPDATE krf_production_stockorder SET quantity = ?, expected_delivery_date = ?, sowingdate = ?, remarks = CONCAT(IFNULL(remarks,''), ?), editedqty = ?, editedremark = ?, editedby = ?, editedon = NOW(), updated_at = NOW() WHERE id = ?";
        $stmt_upd = $krfconn->prepare($upd_sql);
        if ($stmt_upd) {
            $stmt_upd->bind_param("dsssdssi", $post_qty, $post_edd, $post_sd, $remark_str, $post_qty, $edit_reason, $loginid, $old['id']);
            $stmt_upd->execute();
            $stmt_upd->close();
        }

        // 4. If remaining balance exists ($rest_qty > 0), split excess sowed trays & create new production order
        if ($rest_qty > 0) {
            $target_prod_id = !empty($new_prod_id) ? trim($new_prod_id) : trim($productid);

            // Resolve Product Name for selected product
            $target_prod_name = '';
            if (!empty($target_prod_id)) {
                $stmt_pn = $krfconn->prepare("SELECT ProductName FROM product_master WHERE (ProductID = ? OR sku = ?) AND (ProjectId = ? OR ProjectId = 0 OR ProjectId IS NULL) ORDER BY ProjectId DESC LIMIT 1");
                if ($stmt_pn) {
                    $stmt_pn->bind_param("ssi", $target_prod_id, $target_prod_id, $projectid);
                    $stmt_pn->execute();
                    $res_pn = $stmt_pn->get_result()->fetch_assoc();
                    $stmt_pn->close();
                    if ($res_pn) {
                        $target_prod_name = $res_pn['ProductName'];
                    }
                }
            }
            if (empty($target_prod_name)) {
                $target_prod_name = $old['product_name'] ?? 'Production Product';
            }

            // Retrieve dispatchdate_internal from krf_polyhouse_sowingplant_details for Root sowed trays
            $dispatchdate_internal = '';
            $root_sowingdate = '';
            $stmt_disp = $krfconn->prepare("SELECT MAX(dispatchdate_internal) as dispatchdate_internal, MIN(sowingdate_internal) as sowingdate_internal, MIN(sowing_date) as sowing_date FROM krf_polyhouse_sowingplant_details WHERE stockorder_no = ? AND LOWER(IFNULL(roottype,'root')) = 'root'");
            if ($stmt_disp) {
                $stmt_disp->bind_param("s", $production_stockorder_no);
                $stmt_disp->execute();
                $res_disp = $stmt_disp->get_result()->fetch_assoc();
                $stmt_disp->close();
                if ($res_disp) {
                    $dispatchdate_internal = !empty($res_disp['dispatchdate_internal']) ? $res_disp['dispatchdate_internal'] : '';
                    $root_sowingdate = !empty($res_disp['sowingdate_internal']) ? $res_disp['sowingdate_internal'] : ($res_disp['sowing_date'] ?? '');
                }
            }

            // If dispatchdate_internal is missing, calculate from root sowing date + root growing days
            if (empty($dispatchdate_internal) && !empty($root_sowingdate)) {
                $root_growing_days = 25; // default
                $orig_seed_id = $old['SeedId'] ?? '';
                if (!empty($orig_seed_id)) {
                    $stmt_rgd = $krfconn->prepare("SELECT growingdays FROM seed_master WHERE SeedID = ? AND (ProjectId = ? OR ProjectId = 0 OR ProjectId IS NULL) ORDER BY ProjectId DESC LIMIT 1");
                    if ($stmt_rgd) {
                        $stmt_rgd->bind_param("si", $orig_seed_id, $projectid);
                        $stmt_rgd->execute();
                        $res_rgd = $stmt_rgd->get_result()->fetch_assoc();
                        $stmt_rgd->close();
                        if ($res_rgd && !empty($res_rgd['growingdays'])) {
                            $root_growing_days = (int)$res_rgd['growingdays'];
                        }
                    }
                }
                $dispatchdate_internal = date('Y-m-d', strtotime($root_sowingdate . " + {$root_growing_days} days"));
            }

            // Calculate Scion Sowing Date for $target_prod_id by subtracting Scion growing days from dispatchdate_internal
            $scion_growing_days = 25; // default
            $target_scion_seed_id = '';
            $stmt_pm_scion = $krfconn->prepare("SELECT ScionSeedId, SeedId FROM product_master WHERE (ProductID = ? OR sku = ?) AND (ProjectId = ? OR ProjectId = 0 OR ProjectId IS NULL) ORDER BY ProjectId DESC LIMIT 1");
            if ($stmt_pm_scion) {
                $stmt_pm_scion->bind_param("ssi", $target_prod_id, $target_prod_id, $projectid);
                $stmt_pm_scion->execute();
                $res_pms = $stmt_pm_scion->get_result()->fetch_assoc();
                $stmt_pm_scion->close();
                if ($res_pms) {
                    $target_scion_seed_id = !empty($res_pms['ScionSeedId']) ? $res_pms['ScionSeedId'] : $res_pms['SeedId'];
                }
            }
            if (!empty($target_scion_seed_id)) {
                $stmt_sgd = $krfconn->prepare("SELECT growingdays FROM seed_master WHERE SeedID = ? AND ProjectId = ? ORDER BY ProjectId DESC LIMIT 1");
                if ($stmt_sgd) {
                    $stmt_sgd->bind_param("ss", $target_scion_seed_id, $projectid);
                    $stmt_sgd->execute();
                    $res_sgd = $stmt_sgd->get_result()->fetch_assoc();
                    $stmt_sgd->close();
                    if ($res_sgd && !empty($res_sgd['growingdays'])) {
                        $scion_growing_days = (int)$res_sgd['growingdays'];
                    }
                }
            }

            $calc_base_date = !empty($dispatchdate_internal) ? $dispatchdate_internal : (!empty($post_edd) ? $post_edd : date('Y-m-d'));
            $calculated_scion_sowing_date = date('Y-m-d', strtotime($calc_base_date . " - {$scion_growing_days} days"));

            // Calculate tray cavity and trays needed for parent order
            $tray_cavity_count = 209;
            $stmt_tc = $krfconn->prepare("SELECT IFNULL(AVG(traycavity), 209) as cavity FROM krf_polyhouse_sowingplant_details WHERE stockorder_no = ? AND projectid = ?");
            if ($stmt_tc) {
                $stmt_tc->bind_param("si", $production_stockorder_no, $projectid);
                $stmt_tc->execute();
                $res_tc = $stmt_tc->get_result()->fetch_assoc();
                $stmt_tc->close();
                if ($res_tc && !empty($res_tc['cavity'])) {
                    $tray_cavity_count = (int)$res_tc['cavity'];
                }
            }
            $sowing_seeds_needed = ceil($post_qty * 1.30);
            $trays_needed_for_parent = ceil($sowing_seeds_needed / $tray_cavity_count);

            // Identify excess Root sowed trays
            $move_root_sowing_ids = [];
            $stmt_rt = $krfconn->prepare("SELECT sowingplantid FROM krf_polyhouse_sowingplant_details WHERE stockorder_no = ? AND projectid = ? AND LOWER(IFNULL(roottype,'root')) = 'root' ORDER BY sowingplantid ASC");
            if ($stmt_rt) {
                $stmt_rt->bind_param("si", $production_stockorder_no, $projectid);
                $stmt_rt->execute();
                $res_rt = $stmt_rt->get_result();
                $accum_trays = 0;
                while ($rrow = $res_rt->fetch_assoc()) {
                    $accum_trays++;
                    if ($accum_trays > $trays_needed_for_parent) {
                        $move_root_sowing_ids[] = (int)$rrow['sowingplantid'];
                    }
                }
                $stmt_rt->close();
            }

            // Identify excess Scion sowed trays
            $move_scion_sowing_ids = [];
            $stmt_st = $krfconn->prepare("SELECT sowingplantid, seedid FROM krf_polyhouse_sowingplant_details WHERE stockorder_no = ? AND projectid = ? AND LOWER(IFNULL(roottype,'root')) = 'scion' ORDER BY sowingplantid ASC");
            if ($stmt_st) {
                $stmt_st->bind_param("si", $production_stockorder_no, $projectid);
                $stmt_st->execute();
                $res_st = $stmt_st->get_result();
                $accum_scion_trays = 0;
                while ($srow = $res_st->fetch_assoc()) {
                    $accum_scion_trays++;
                    if ($accum_scion_trays > $trays_needed_for_parent) {
                        $move_scion_sowing_ids[] = (int)$srow['sowingplantid'];
                    }
                }
                $stmt_st->close();
            }

            // A. Create New Production Stock Order for $target_prod_id (for excess Root balance)
            $new_stockorder_no = getNewProductionStockOrderNo($krfconn, $companyid, $projectid);
            $ins_new_sql = "INSERT INTO krf_production_stockorder (stockorder_no, projectid, companyid, subcompanyid, clientid, productid, product_name, quantity, expected_delivery_date, sowingdate, scionsowingdate, remarks, editedqty, editedremark, editedby, editedon, status, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'Confirmed', ?, NOW(), NOW())";
            $stmt_ins_new = $krfconn->prepare($ins_new_sql);
            if ($stmt_ins_new) {
                $new_remarks = "Created from balance split of " . $production_stockorder_no . $remark_str;
                $stmt_ins_new->bind_param("siiisssdssssdsss", $new_stockorder_no, $projectid, $companyid, $subcompanyid, $target_client_id, $target_prod_id, $target_prod_name, $rest_qty, $post_edd, $calculated_scion_sowing_date, $calculated_scion_sowing_date, $new_remarks, $rest_qty, $edit_reason, $loginid, $loginid);
                $stmt_ins_new->execute();
                $stmt_ins_new->close();
            }

            // Move excess Root sowed trays to $new_stockorder_no
            if (!empty($move_root_sowing_ids)) {
                $in_clause_root = implode(",", array_map('intval', $move_root_sowing_ids));

                $upd_sow_r = "UPDATE krf_polyhouse_sowingplant_details SET stockorder_no = ?, psono = ?, productid = ?, productname = ?, updated_at = NOW() WHERE sowingplantid IN ($in_clause_root)";
                $stmt_u_sow_r = $krfconn->prepare($upd_sow_r);
                if ($stmt_u_sow_r) {
                    $stmt_u_sow_r->bind_param("ssss", $new_stockorder_no, $new_stockorder_no, $target_prod_id, $target_prod_name);
                    $stmt_u_sow_r->execute();
                    $stmt_u_sow_r->close();
                }

                $upd_plant_r = "UPDATE krf_polyhouse_plant_details SET stockorder_no = ?, psono = ?, productid = ?, productname = ?, updated_at = NOW() WHERE sowingplantid IN ($in_clause_root) OR (stockorder_no = ? AND LOWER(IFNULL(roottype,'root')) = 'root' AND sowingplantid IN ($in_clause_root))";
                $stmt_u_plant_r = $krfconn->prepare($upd_plant_r);
                if ($stmt_u_plant_r) {
                    $stmt_u_plant_r->bind_param("sssss", $new_stockorder_no, $new_stockorder_no, $target_prod_id, $target_prod_name, $production_stockorder_no);
                    $stmt_u_plant_r->execute();
                    $stmt_u_plant_r->close();
                }
            }

            // Create schedule for $new_stockorder_no
            createSowingScheduleForNewStockOrder($krfconn, $projectid, $new_stockorder_no, $target_prod_id, $target_prod_name, $rest_qty, $calculated_scion_sowing_date, $calculated_scion_sowing_date, $loginid);

            // B. Move excess Scion sowed trays to OPEN STOCK
            if (!empty($move_scion_sowing_ids)) {
                // 1. Get original product's ScionSeedId from product_master
                $scion_seed_id = '';
                $stmt_orig_p = $krfconn->prepare("SELECT ScionSeedId, SeedId FROM product_master WHERE (ProductID = ? OR sku = ?) AND ProjectId = ? LIMIT 1");
                if ($stmt_orig_p) {
                    $stmt_orig_p->bind_param("ssi", $productid, $productid, $projectid);
                    $stmt_orig_p->execute();
                    $res_op = $stmt_orig_p->get_result()->fetch_assoc();
                    $stmt_orig_p->close();
                    if ($res_op) {
                        $scion_seed_id = !empty($res_op['ScionSeedId']) ? $res_op['ScionSeedId'] : $res_op['SeedId'];
                    }
                }
                if (empty($scion_seed_id)) {
                    $scion_seed_id = $old['ScionSeedId'] ?? $old['SeedId'] ?? '';
                }

                // 2. Resolve Open Stock Product where SeedId = ScionSeedId AND ScionSeedId = ScionSeedId
                $open_stock_prod_id = $target_prod_id;
                $open_stock_prod_name = $target_prod_name;

                if (!empty($scion_seed_id)) {
                    $stmt_open_p = $krfconn->prepare("SELECT ProductID, ProductName FROM product_master WHERE SeedId = ? AND ScionSeedId = ? AND ProjectId = ? LIMIT 1");
                    if ($stmt_open_p) {
                        $stmt_open_p->bind_param("ssi", $scion_seed_id, $scion_seed_id, $projectid);
                        $stmt_open_p->execute();
                        $res_open_p = $stmt_open_p->get_result()->fetch_assoc();
                        $stmt_open_p->close();
                        if ($res_open_p) {
                            $open_stock_prod_id = $res_open_p['ProductID'];
                            $open_stock_prod_name = $res_open_p['ProductName'];
                        }
                    }
                }

                $SDdatetime = date('YmdHis');
                $scion_open_stockorder_no = $production_stockorder_no . "_" . $SDdatetime;

                // Create Open Stock Production Order for Scion
                $ins_open_s = "INSERT INTO krf_production_stockorder (stockorder_no, projectid, companyid, subcompanyid, clientid, productid, product_name, quantity, expected_delivery_date, sowingdate, scionsowingdate, remarks, editedqty, editedremark, editedby, editedon, status, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'Confirmed', ?, NOW(), NOW())";
                $stmt_ins_open_s = $krfconn->prepare($ins_open_s);
                if ($stmt_ins_open_s) {
                    $open_remarks = "Open Stock Scion split from " . $production_stockorder_no . $remark_str;
                    $stmt_ins_open_s->bind_param("siiisssdssssdsss", $scion_open_stockorder_no, $projectid, $companyid, $subcompanyid, $target_client_id, $open_stock_prod_id, $open_stock_prod_name, $rest_qty, $post_edd, $calculated_scion_sowing_date, $calculated_scion_sowing_date, $open_remarks, $rest_qty, $edit_reason, $loginid, $loginid);
                    $stmt_ins_open_s->execute();
                    $stmt_ins_open_s->close();
                }

                $in_clause_scion = implode(",", array_map('intval', $move_scion_sowing_ids));

                $upd_sow_s = "UPDATE krf_polyhouse_sowingplant_details SET stockorder_no = ?, psono = ?, productid = ?, productname = ?, seedid = ?, updated_at = NOW() WHERE sowingplantid IN ($in_clause_scion)";
                $stmt_u_sow_s = $krfconn->prepare($upd_sow_s);
                if ($stmt_u_sow_s) {
                    $stmt_u_sow_s->bind_param("sssss", $scion_open_stockorder_no, $scion_open_stockorder_no, $open_stock_prod_id, $open_stock_prod_name, $scion_seed_id);
                    $stmt_u_sow_s->execute();
                    $stmt_u_sow_s->close();
                }

                $upd_plant_s = "UPDATE krf_polyhouse_plant_details SET stockorder_no = ?, psono = ?, productid = ?, productname = ?, updated_at = NOW() WHERE sowingplantid IN ($in_clause_scion) OR (stockorder_no = ? AND LOWER(IFNULL(roottype,'root')) = 'scion' AND sowingplantid IN ($in_clause_scion))";
                $stmt_u_plant_s = $krfconn->prepare($upd_plant_s);
                if ($stmt_u_plant_s) {
                    $stmt_u_plant_s->bind_param("sssss", $scion_open_stockorder_no, $scion_open_stockorder_no, $open_stock_prod_id, $open_stock_prod_name, $production_stockorder_no);
                    $stmt_u_plant_s->execute();
                    $stmt_u_plant_s->close();
                }

                createSowingScheduleForNewStockOrder($krfconn, $projectid, $scion_open_stockorder_no, $open_stock_prod_id, $open_stock_prod_name, $rest_qty, $calculated_scion_sowing_date, $calculated_scion_sowing_date, $loginid);
            }
        }

        return [
            "status" => "success",
            "message" => "Production order qty update successfully",
            "items" => [
                "stockorder_no" => $production_stockorder_no,
                "productid" => $productid,
                "quantity" => $post_qty,
                "rest_qty" => $rest_qty
            ]
        ];
    }

    return null;
}
}

if (!function_exists('getScalarOrArrayPostVal')) {
    function getScalarOrArrayPostVal($key, $i = 0, $default = '') {
        if (!isset($_POST[$key])) return $default;
        $v = $_POST[$key];
        if (is_array($v)) {
            return isset($v[$i]) && $v[$i] !== '' ? trim((string)$v[$i]) : $default;
        }
        return trim((string)$v) !== '' ? trim((string)$v) : $default;
    }
}

if ($action == "UpdateEditedProductStockOrder") {
    // Action for updating existing orders
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        try {
            $krfconn->begin_transaction();

            $production_stockorder_no = $_POST['production_stockorder_no'] ?? '';
            $projectid = $_POST['projectid'] ?? 0;
            $companyid = $_POST['companyid'] ?? 0;
            $subcompanyid = $_POST['subcompanyid'] ?? 0;
            $clientid = $_POST['clientid'] ?? 0;
            $loginid = $_POST['loginid'] ?? 0;

            $raw_productlist = $_POST['productname'] ?? $_POST['productid'] ?? $_POST['product_id'] ?? [];
            if (!is_array($raw_productlist)) {
                $raw_productlist = !empty($raw_productlist) ? [$raw_productlist] : [];
            }
            $productlist = [];
            foreach ($raw_productlist as $p_item) {
                if ($p_item !== null && $p_item !== '') {
                    $productlist[] = trim($p_item);
                }
            }

            if (empty($productlist) && !empty($production_stockorder_no)) {
                $stmt_pl = $krfconn->prepare("SELECT productid FROM krf_production_stockorder WHERE stockorder_no = ?");
                if ($stmt_pl) {
                    $stmt_pl->bind_param("s", $production_stockorder_no);
                    $stmt_pl->execute();
                    $res_pl = $stmt_pl->get_result();
                    while ($r_pl = $res_pl->fetch_assoc()) {
                        $productlist[] = $r_pl['productid'];
                    }
                    $stmt_pl->close();
                }
            }

            $quantities = $_POST['quantity'] ?? [];
            $expecteddelivery = $_POST['expecteddelivery'] ?? [];
            $sowingdates = $_POST['sowingdate'] ?? [];

            if (empty($production_stockorder_no)) {
                throw new Exception("Production stock order number is missing for update.");
            }

            $sentry = 0;
            $last_data = [];

            // Loop through the products from POST and update/insert
            // print_r($productlist);
            for ($i = 0; $i < count($productlist); $i++) {
                $productid = trim($productlist[$i]);
                $post_qty = (float)getScalarOrArrayPostVal('quantity', $i, 0);
                $post_edd = getScalarOrArrayPostVal('expecteddelivery', $i, null);
                $post_sd = getScalarOrArrayPostVal('sowingdate', $i, null);

                $edit_reason = getScalarOrArrayPostVal('edit_reason', $i, '');
                $edit_choice = getScalarOrArrayPostVal('edit_choice_' . $i, 0, getScalarOrArrayPostVal('edit_choice', $i, 'only_number'));
                $new_prod_id = getScalarOrArrayPostVal('new_order_product_id', $i, '');


                // echo "rd: ".$raw_post_new_root_sd = getScalarOrArrayPostVal('new_order_root_sowingdate', $i, '');
                // echo "sd: ".$raw_post_new_scion_sd = getScalarOrArrayPostVal('new_order_scion_sowingdate', $i, '');
                // echo "exd: ".$raw_post_new_edd = getScalarOrArrayPostVal('new_order_expected_delivery', $i, '');

                // die();

                // Fetch original production stock order record by stockorder_no
                $stmt_old = $krfconn->prepare("SELECT * FROM krf_production_stockorder WHERE stockorder_no = ? LIMIT 1");
                $stmt_old->bind_param("s", $production_stockorder_no);
                $stmt_old->execute();
                $old = $stmt_old->get_result()->fetch_assoc();
                $stmt_old->close();

                $traycavity = 0;
                $sowing_plants = 0;
                $root_trayid_found = null;
                $scion_trayid_found = null;
                $sowinglocation_found = '';

                $check_sowing_sql = "SELECT IFNULL(AVG(traycavity),0) as cavity, 
                                            IFNULL(SUM(CASE WHEN LOWER(IFNULL(roottype,'root')) = 'root' THEN traycavity ELSE 0 END),0) as root_sowed_plants,
                                            IFNULL(SUM(CASE WHEN LOWER(IFNULL(roottype,'root')) = 'scion' THEN traycavity ELSE 0 END),0) as scion_sowed_plants,
                                            MAX(CASE WHEN LOWER(IFNULL(roottype,'root')) = 'root' THEN trayid ELSE NULL END) as root_trayid_found,
                                            MAX(CASE WHEN LOWER(IFNULL(roottype,'root')) = 'scion' THEN trayid ELSE NULL END) as scion_trayid_found,
                                            MAX(CASE WHEN sowinglocationid IS NOT NULL AND sowinglocationid != '' THEN sowinglocationid ELSE locationid END) as sowinglocation_found
                                     FROM krf_polyhouse_sowingplant_details WHERE stockorder_no = ? AND projectid = ?";
                $stmt_s = $krfconn->prepare($check_sowing_sql);
                if ($stmt_s) {
                    $stmt_s->bind_param("si", $production_stockorder_no, $projectid);
                    $stmt_s->execute();
                    $res_s = $stmt_s->get_result()->fetch_assoc();
                    $traycavity = (int)($res_s['cavity'] ?? 0);
                    $root_sowed = (int)($res_s['root_sowed_plants'] ?? 0);
                    $scion_sowed = (int)($res_s['scion_sowed_plants'] ?? 0);
                    $root_trayid_found = $res_s['root_trayid_found'] ?? null;
                    $scion_trayid_found = $res_s['scion_trayid_found'] ?? null;
                    $sowinglocation_found = $res_s['sowinglocation_found'] ?? '';
                    $sowing_plants = ($root_sowed > 0) ? $root_sowed : $scion_sowed;
                    $stmt_s->close();
                }

                // Check if Root / Scion sowing is started or done in krf_polyhouse_sowingplant_details
                $root_sowing_started = ($root_sowed > 0);
                $scion_sowing_started = ($scion_sowed > 0);
                $sowing_started = ($root_sowing_started || $scion_sowing_started);
                $both_sowing_completed = ($root_sowing_started && $scion_sowing_started);

                if ($old) {
                    $old_qty = (float)$old['quantity'];
                    $consumed_qty = (float)getTotalConsumedQtyForPSO($krfconn, $projectid, $production_stockorder_no, $productid);

                    // 1. Fully Consumed Check: Order cannot be edited under any condition if fully consumed
                    if ($old_qty > 0 && $consumed_qty >= $old_qty) {
                        echo json_encode([
                            "status" => "error",
                            "message" => "Production stock order is fully consumed (" . number_format($consumed_qty) . ") and cannot be edited under any condition."
                        ]);
                        return;
                    }

                    // 2. Minimum Allowed Quantity Rule: Cannot be less than consumed quantity
                    $min_allowed = max(1.0, (float)$consumed_qty);
                    if ($post_qty < $min_allowed) {
                        echo json_encode([
                            "status" => "error",
                            "message" => "Edited quantity (" . number_format($post_qty) . ") cannot be less than consumed quantity (" . number_format($consumed_qty) . " plants)."
                        ]);
                        return;
                    }
                    // CONDITION 2 SEPARATE PHP ACTION HANDLER DISPATCH
                    if ($edit_choice === 'create_new_order' || $edit_choice === 'full_split') {
                        $target_client_id = !empty($_POST['edit_client_id']) ? trim($_POST['edit_client_id']) : (!empty($_POST['clientid']) ? trim($_POST['clientid']) : (!empty($old['clientid']) ? trim($old['clientid']) : $clientid));
                        
                        $raw_post_new_root_sd = getScalarOrArrayPostVal('new_order_root_sowingdate', $i, '');
                        $raw_post_new_scion_sd = getScalarOrArrayPostVal('new_order_scion_sowingdate', $i, '');
                        $raw_post_new_edd = getScalarOrArrayPostVal('new_order_expected_delivery', $i, '');
                        $new_prod_id = getScalarOrArrayPostVal('new_order_product_id', $i, $new_prod_id);

                        if (empty($new_prod_id)) {
                            echo json_encode([
                                "status" => "error",
                                "message" => "Warning: Product Name for the new production order is missing. Please select a product before saving."
                            ]);
                            return;
                        }

                        if (empty($raw_post_new_edd) && empty($post_edd)) {
                            echo json_encode([
                                "status" => "error",
                                "message" => "Warning: Expected Ready Delivery Date for the new production order is missing. Please select a delivery date."
                            ]);
                            return;
                        }

                        $eff_new_edd = $raw_post_new_edd;
                        $new_sowing_date = !empty($raw_post_new_root_sd) ? $raw_post_new_root_sd : '';
                        $new_scion_sowing_date = !empty($raw_post_new_scion_sd) ? $raw_post_new_scion_sd : '';


                        if ((empty($new_sowing_date) || empty($new_scion_sowing_date)) && !empty($new_prod_id) && !empty($eff_new_edd)) {
                            if (function_exists('calculateSowingAndInternalDispatchDates')) {
                                $c_dates = calculateSowingAndInternalDispatchDates($krfconn, $eff_new_edd, $projectid, $new_prod_id);
                                if (!empty($c_dates) && isset($c_dates['status']) && $c_dates['status'] === 'success') {
                                    if (empty($new_sowing_date) && !empty($c_dates['RootSeed']['sowingdate'])) {
                                        $new_sowing_date = $c_dates['RootSeed']['sowingdate'];
                                    }
                                    if (empty($new_scion_sowing_date) && !empty($c_dates['ScionSeed']['sowingdate'])) {
                                        $new_scion_sowing_date = $c_dates['ScionSeed']['sowingdate'];
                                    }
                                }
                            }
                        }

                        if (empty($new_sowing_date)) {
                            echo json_encode([
                                "status" => "error",
                                "message" => "Warning: Root Seed Sowing Date for the new production order is missing."
                            ]);
                            return;
                        }

                        if (empty($eff_new_edd)) {
                            echo json_encode([
                                "status" => "error",
                                "message" => "Warning: New Expected delivery date is missing."
                            ]);
                            return;
                        }

                        $res_c2 = handleCondition2StockOrderEdit($krfconn, $projectid, $companyid, $subcompanyid, $loginid, $old, $post_qty, $post_edd, $post_sd, $edit_reason, $new_prod_id, $target_client_id, $new_sowing_date, $new_scion_sowing_date, $root_sowed, $scion_sowed, $eff_new_edd);
                        if ($res_c2 !== null) {
                            $krfconn->commit();
                            echo json_encode($res_c2);
                            return;
                        }
                    }

                    // 3. Maximum Allowed Quantity Rule for Option 1:
                    // If root seed sowing is started or done in krf_polyhouse_sowingplant_details (roottype='Root'), edited qty must be <= sowed qty
                    if ($root_sowing_started) {
                        $max_sowed = ($root_sowed > 0) ? $root_sowed : $old_qty;
                        if ($post_qty > $max_sowed) {
                            echo json_encode([
                                "status" => "error",
                                "message" => "Sowing of Root seed is started/done in polyhouse. Edited quantity (" . number_format($post_qty) . ") cannot be greater than sowed root quantity (" . number_format($max_sowed) . " plants)."
                            ]);
                            return;
                        }
                    }

                    $remark_str = !empty($edit_reason) ? " [Edit Reason: " . $edit_reason . "]" : "";
                    $rest_qty = max(0, $old_qty - $post_qty);

                    // 1. Update current production stock order quantity & metadata
                    $upd_sql = "UPDATE krf_production_stockorder SET quantity = ?, expected_delivery_date = ?, sowingdate = ?, remarks = CONCAT(IFNULL(remarks,''), ?), editedqty = ?, editedremark = ?, editedby = ?, editedon = NOW(), updated_at = NOW() WHERE id = ?";
                    $stmt_upd = $krfconn->prepare($upd_sql);
                    if ($stmt_upd) {
                        $stmt_upd->bind_param("dsssdssi", $post_qty, $post_edd, $post_sd, $remark_str, $post_qty, $edit_reason, $loginid, $old['id']);
                        $stmt_upd->execute();
                        $stmt_upd->close();
                    }

                    $move_root_sowing_ids = [];
                    $move_scion_sowing_ids = [];

                    // 2. If remaining quantity exists ($rest_qty > 0), split sowed Root and Scion trays
                    if ($rest_qty > 0) {
                        $target_prod_id = !empty($new_prod_id) ? trim($new_prod_id) : trim($productid);
                        $target_client_id = !empty($_POST['edit_client_id']) ? trim($_POST['edit_client_id']) : (!empty($_POST['clientid']) ? trim($_POST['clientid']) : (!empty($old['clientid']) ? trim($old['clientid']) : $clientid));

                        $orig_seed_id = !empty($old['SeedId']) ? trim($old['SeedId']) : '';
                        $orig_scion_seed_id = !empty($old['ScionSeedId']) ? trim($old['ScionSeedId']) : $orig_seed_id;

                        // Effective location & tray IDs
                        $eff_sowinglocationid = !empty($old['sowinglocationid']) ? $old['sowinglocationid'] : (!empty($sowinglocation_found) ? $sowinglocation_found : '');
                        $eff_roottrayid = !empty($old['roottrayid']) ? $old['roottrayid'] : ($root_trayid_found ?? null);
                        $eff_sciontrayid = !empty($old['sciontrayid']) ? $old['sciontrayid'] : ($scion_trayid_found ?? null);

                        // Resolve ROOT Product ID & Product Name (where SeedId matches root seed ID)
                        $root_prod_id = $productid;
                        $root_prod_name = $old['product_name'] ?? 'Production Product';
                        if (!empty($orig_seed_id)) {
                            $stmt_rprd = $krfconn->prepare("SELECT ProductID, ProductName FROM product_master WHERE (SeedId = ? OR sku LIKE ?) AND (ScionSeedId = ? OR IsGrafted != 'Grafted') ORDER BY (ProductID = ?) DESC, ProductID ASC LIMIT 1");
                            if ($stmt_rprd) {
                                $like_root = "%-" . $orig_seed_id . "-%";
                                $stmt_rprd->bind_param("ssss", $orig_seed_id, $like_root, $orig_seed_id, $orig_seed_id);
                                $stmt_rprd->execute();
                                $res_rprd = $stmt_rprd->get_result()->fetch_assoc();
                                $stmt_rprd->close();
                                if ($res_rprd) {
                                    $root_prod_id = $res_rprd['ProductID'];
                                    $root_prod_name = $res_rprd['ProductName'];
                                }
                            }
                        }

                        // Resolve SCION Product ID & Product Name (where SeedId matches scion seed ID)
                        $scion_prod_id = $productid;
                        $scion_prod_name = $old['product_name'] ?? 'Production Product';
                        if (!empty($orig_scion_seed_id)) {
                            $stmt_sprd = $krfconn->prepare("SELECT ProductID, ProductName FROM product_master WHERE (SeedId = ? OR sku LIKE ?) AND (ScionSeedId = ? OR IsGrafted != 'Grafted') ORDER BY (ProductID = ?) DESC, ProductID ASC LIMIT 1");
                            if ($stmt_sprd) {
                                $like_scion = "%-" . $orig_scion_seed_id . "-%";
                                $stmt_sprd->bind_param("ssss", $orig_scion_seed_id, $like_scion, $orig_scion_seed_id, $orig_scion_seed_id);
                                $stmt_sprd->execute();
                                $res_sprd = $stmt_sprd->get_result()->fetch_assoc();
                                $stmt_sprd->close();
                                if ($res_sprd) {
                                    $scion_prod_id = $res_sprd['ProductID'];
                                    $scion_prod_name = $res_sprd['ProductName'];
                                }
                            }
                        }

                        $post_new_sowing_date = getScalarOrArrayPostVal('new_order_root_sowingdate', 0, '');
                        $post_new_scion_sowing_date = getScalarOrArrayPostVal('new_order_scion_sowingdate', 0, '');
                        $post_new_edd = getScalarOrArrayPostVal('new_order_expected_delivery', 0, '');

                        $eff_new_edd = !empty($post_new_edd) ? $post_new_edd : (!empty($new_expected_delivery) ? $new_expected_delivery : $post_edd);
                        $new_sowing_date = !empty($post_new_sowing_date) ? $post_new_sowing_date : (!empty($new_sowing_date) ? $new_sowing_date : '');
                        $new_scion_sowing_date = !empty($post_new_scion_sowing_date) ? $post_new_scion_sowing_date : (!empty($new_scion_sowing_date) ? $new_scion_sowing_date : '');

                        if ($root_sowed > 0) {
                            $orig_root_sd = !empty($post_sd) ? $post_sd : (!empty($old['sowingdate']) ? $old['sowingdate'] : '');
                            if (!empty($orig_root_sd)) {
                                $new_sowing_date = $orig_root_sd;
                            }
                        }

                        if ((empty($new_sowing_date) || empty($new_scion_sowing_date)) && !empty($target_prod_id) && !empty($eff_new_edd)) {
                            if (function_exists('calculateSowingAndInternalDispatchDates')) {
                                $c_dates = calculateSowingAndInternalDispatchDates($krfconn, $eff_new_edd, $projectid, $target_prod_id);
                                if (!empty($c_dates) && isset($c_dates['status']) && $c_dates['status'] === 'success') {
                                    if (empty($new_sowing_date) && !empty($c_dates['RootSeed']['sowingdate'])) {
                                        $new_sowing_date = $c_dates['RootSeed']['sowingdate'];
                                    }
                                    if (empty($new_scion_sowing_date) && !empty($c_dates['ScionSeed']['sowingdate'])) {
                                        $new_scion_sowing_date = $c_dates['ScionSeed']['sowingdate'];
                                    }
                                }
                            }
                        }

                        $tray_cavity_count = ($traycavity > 0) ? $traycavity : 209;
                        $sowing_seeds_needed = ceil($post_qty * 1.30);
                        $trays_needed_for_parent = ceil($sowing_seeds_needed / $tray_cavity_count);

                        // A. ROOT TRAYS REMAINING SPLIT (roottype = 'Root')
                        if ($root_sowed > 0) {
                            $stmt_rt = $krfconn->prepare("SELECT sowingplantid FROM krf_polyhouse_sowingplant_details WHERE stockorder_no = ? AND projectid = ? AND LOWER(IFNULL(roottype,'root')) = 'root' ORDER BY sowingplantid ASC");
                            $accum_trays = 0;
                            if ($stmt_rt) {
                                $stmt_rt->bind_param("si", $production_stockorder_no, $projectid);
                                $stmt_rt->execute();
                                $res_rt = $stmt_rt->get_result();
                                while ($rrow = $res_rt->fetch_assoc()) {
                                    $accum_trays++;
                                    if ($accum_trays > $trays_needed_for_parent) {
                                        $move_root_sowing_ids[] = (int)$rrow['sowingplantid'];
                                    }
                                }
                                $stmt_rt->close();
                            }

                            if (!empty($move_root_sowing_ids)) {
                                $RDdatetime = date('YmdHis');
                                $root_stockorder_no = $production_stockorder_no . "_" . $RDdatetime;

                                // Duplicate prevention check
                                $chk_r = $krfconn->prepare("SELECT COUNT(*) as cnt FROM krf_production_stockorder WHERE stockorder_no = ?");
                                if ($chk_r) {
                                    $chk_r->bind_param("s", $root_stockorder_no);
                                    $chk_r->execute();
                                    $rc = $chk_r->get_result()->fetch_assoc();
                                    $chk_r->close();
                                    if (!empty($rc['cnt']) && $rc['cnt'] > 0) {
                                        $root_stockorder_no .= "_" . rand(10, 99);
                                    }
                                }

                                // Create Root Production Order with resolved ProductID, ProductName, sowinglocationid, and roottrayid
                                $ins_root_sql = "INSERT INTO krf_production_stockorder (stockorder_no, projectid, companyid, subcompanyid, clientid, productid, product_name, quantity, expected_delivery_date, sowingdate, scionsowingdate, remarks, editedqty, editedremark, editedby, editedon, status, created_by, sowinglocationid, roottrayid, sciontrayid, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'Confirmed', ?, ?, ?, NULL, NOW(), NOW())";
                                $stmt_ins_r = $krfconn->prepare($ins_root_sql);
                                if ($stmt_ins_r) {
                                    $root_remarks = "Created from remaining Root split of " . $production_stockorder_no . $remark_str;
                                    $stmt_ins_r->bind_param("siiisssdssssdsssss", $root_stockorder_no, $projectid, $companyid, $subcompanyid, $target_client_id, $root_prod_id, $root_prod_name, $rest_qty, $eff_new_edd, $new_sowing_date, $new_scion_sowing_date, $root_remarks, $rest_qty, $edit_reason, $loginid, $loginid, $eff_sowinglocationid, $eff_roottrayid);
                                    $stmt_ins_r->execute();
                                    $stmt_ins_r->close();
                                }

                                $in_clause_root = implode(",", array_map('intval', $move_root_sowing_ids));

                                // Update krf_polyhouse_sowingplant_details for Root
                                $upd_sow_root = "UPDATE krf_polyhouse_sowingplant_details SET stockorder_no = ?, psono = ?, productid = ?, productname = ?, seedid = ?, locationid = ?, sowinglocationid = ?, trayid = ?, updated_at = NOW() WHERE sowingplantid IN ($in_clause_root)";
                                $stmt_u_sow_r = $krfconn->prepare($upd_sow_root);
                                if ($stmt_u_sow_r) {
                                    $stmt_u_sow_r->bind_param("ssssssss", $root_stockorder_no, $root_stockorder_no, $root_prod_id, $root_prod_name, $orig_seed_id, $eff_sowinglocationid, $eff_sowinglocationid, $eff_roottrayid);
                                    $stmt_u_sow_r->execute();
                                    $stmt_u_sow_r->close();
                                }

                                // Update krf_polyhouse_plant_details for Root
                                $upd_plant_root = "UPDATE krf_polyhouse_plant_details SET stockorder_no = ?, psono = ?, productid = ?, productname = ?, locationid = ?, tray_id = ?, updated_at = NOW() WHERE sowingplantid IN ($in_clause_root) OR (stockorder_no = ? AND LOWER(IFNULL(roottype,'root')) = 'root' AND sowingplantid IN ($in_clause_root))";
                                $stmt_u_plant_r = $krfconn->prepare($upd_plant_root);
                                if ($stmt_u_plant_r) {
                                    $stmt_u_plant_r->bind_param("sssssss", $root_stockorder_no, $root_stockorder_no, $root_prod_id, $root_prod_name, $eff_sowinglocationid, $eff_roottrayid, $production_stockorder_no);
                                    $stmt_u_plant_r->execute();
                                    $stmt_u_plant_r->close();
                                }

                                // Create schedule entry in krf_polyhouse_sowingschedule for Root
                                createSowingScheduleForNewStockOrder($krfconn, $projectid, $root_stockorder_no, $root_prod_id, $root_prod_name, $rest_qty, $new_sowing_date, $new_scion_sowing_date, $loginid, $eff_sowinglocationid, $eff_roottrayid, 'Root');
                            }
                        }

                        // B. SCION TRAYS REMAINING SPLIT (roottype = 'Scion')
                        if ($scion_sowed > 0 || $root_sowed > 0) {
                            $stmt_st = $krfconn->prepare("SELECT sowingplantid FROM krf_polyhouse_sowingplant_details WHERE stockorder_no = ? AND projectid = ? AND LOWER(IFNULL(roottype,'root')) = 'scion' ORDER BY sowingplantid ASC");
                            $accum_scion_trays = 0;
                            if ($stmt_st) {
                                $stmt_st->bind_param("si", $production_stockorder_no, $projectid);
                                $stmt_st->execute();
                                $res_st = $stmt_st->get_result();
                                while ($srow = $res_st->fetch_assoc()) {
                                    $accum_scion_trays++;
                                    if ($accum_scion_trays > $trays_needed_for_parent) {
                                        $move_scion_sowing_ids[] = (int)$srow['sowingplantid'];
                                    }
                                }
                                $stmt_st->close();
                            }

                            if (!empty($move_scion_sowing_ids)) {
                                $SDdatetime = date('YmdHis', time() + 1);
                                $scion_stockorder_no = $production_stockorder_no . "_" . $SDdatetime;

                                // Duplicate prevention check
                                $chk_s = $krfconn->prepare("SELECT COUNT(*) as cnt FROM krf_production_stockorder WHERE stockorder_no = ?");
                                if ($chk_s) {
                                    $chk_s->bind_param("s", $scion_stockorder_no);
                                    $chk_s->execute();
                                    $sc = $chk_s->get_result()->fetch_assoc();
                                    $chk_s->close();
                                    if (!empty($sc['cnt']) && $sc['cnt'] > 0) {
                                        $scion_stockorder_no .= "_" . rand(10, 99);
                                    }
                                }

                                // Create Scion Production Order with resolved ProductID, ProductName, sowinglocationid, and sciontrayid
                                $ins_scion_sql = "INSERT INTO krf_production_stockorder (stockorder_no, projectid, companyid, subcompanyid, clientid, productid, product_name, quantity, expected_delivery_date, sowingdate, scionsowingdate, remarks, editedqty, editedremark, editedby, editedon, status, created_by, sowinglocationid, roottrayid, sciontrayid, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'Confirmed', ?, ?, NULL, ?, NOW(), NOW())";
                                $stmt_ins_s = $krfconn->prepare($ins_scion_sql);
                                if ($stmt_ins_s) {
                                    $scion_remarks = "Created from remaining Scion split of " . $production_stockorder_no . $remark_str;
                                    $stmt_ins_s->bind_param("siiisssdssssdsssss", $scion_stockorder_no, $projectid, $companyid, $subcompanyid, $target_client_id, $scion_prod_id, $scion_prod_name, $rest_qty, $eff_new_edd, $new_sowing_date, $new_scion_sowing_date, $scion_remarks, $rest_qty, $edit_reason, $loginid, $loginid, $eff_sowinglocationid, $eff_sciontrayid);
                                    $stmt_ins_s->execute();
                                    $stmt_ins_s->close();
                                }

                                $in_clause_scion = implode(",", array_map('intval', $move_scion_sowing_ids));

                                // Update krf_polyhouse_sowingplant_details for Scion
                                $upd_sow_scion = "UPDATE krf_polyhouse_sowingplant_details SET stockorder_no = ?, psono = ?, productid = ?, productname = ?, seedid = ?, locationid = ?, sowinglocationid = ?, trayid = ?, updated_at = NOW() WHERE sowingplantid IN ($in_clause_scion)";
                                $stmt_u_sow_s = $krfconn->prepare($upd_sow_scion);
                                if ($stmt_u_sow_s) {
                                    $stmt_u_sow_s->bind_param("ssssssss", $scion_stockorder_no, $scion_stockorder_no, $scion_prod_id, $scion_prod_name, $orig_scion_seed_id, $eff_sowinglocationid, $eff_sowinglocationid, $eff_sciontrayid);
                                    $stmt_u_sow_s->execute();
                                    $stmt_u_sow_s->close();
                                }

                                // Update krf_polyhouse_plant_details for Scion
                                $upd_plant_scion = "UPDATE krf_polyhouse_plant_details SET stockorder_no = ?, psono = ?, productid = ?, productname = ?, locationid = ?, tray_id = ?, updated_at = NOW() WHERE sowingplantid IN ($in_clause_scion) OR (stockorder_no = ? AND LOWER(IFNULL(roottype,'root')) = 'scion' AND sowingplantid IN ($in_clause_scion))";
                                $stmt_u_plant_s = $krfconn->prepare($upd_plant_scion);
                                if ($stmt_u_plant_s) {
                                    $stmt_u_plant_s->bind_param("sssssss", $scion_stockorder_no, $scion_stockorder_no, $scion_prod_id, $scion_prod_name, $eff_sowinglocationid, $eff_sciontrayid, $production_stockorder_no);
                                    $stmt_u_plant_s->execute();
                                    $stmt_u_plant_s->close();
                                }

                                // Create schedule entry in krf_polyhouse_sowingschedule for Scion
                                createSowingScheduleForNewStockOrder($krfconn, $projectid, $scion_stockorder_no, $scion_prod_id, $scion_prod_name, $rest_qty, $new_sowing_date, $new_scion_sowing_date, $loginid, $eff_sowinglocationid, $eff_sciontrayid, 'Scion');
                            }
                        }

                        // C. Fallback for Option 2 if NO sowed trays were moved (e.g. sowing not started yet)
                        if (($edit_choice === 'create_new_order' || $edit_choice === 'full_split') && empty($move_root_sowing_ids) && empty($move_scion_sowing_ids)) {
                            $new_stockorder_no = getNewProductionStockOrderNo($krfconn, $companyid, $projectid);
                            $ins_new_sql = "INSERT INTO krf_production_stockorder (stockorder_no, projectid, companyid, subcompanyid, clientid, productid, product_name, quantity, expected_delivery_date, sowingdate, scionsowingdate, remarks, editedqty, editedremark, editedby, editedon, status, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'Confirmed', ?, NOW(), NOW())";
                            $stmt_ins_new = $krfconn->prepare($ins_new_sql);
                            if ($stmt_ins_new) {
                                $new_remarks = "Created from balance split of " . $production_stockorder_no . $remark_str;
                                $stmt_ins_new->bind_param("siiisssdssssdsss", $new_stockorder_no, $projectid, $companyid, $subcompanyid, $target_client_id, $target_prod_id, $target_prod_name, $rest_qty, $eff_new_edd, $new_sowing_date, $new_scion_sowing_date, $new_remarks, $rest_qty, $edit_reason, $loginid, $loginid);
                                $stmt_ins_new->execute();
                                $stmt_ins_new->close();
                                createSowingScheduleForNewStockOrder($krfconn, $projectid, $new_stockorder_no, $target_prod_id, $target_prod_name, $rest_qty, $new_sowing_date, $new_scion_sowing_date, $loginid);
                            }
                        }
                    }

                    $last_data = [
                        "stockorder_no" => $production_stockorder_no,
                        "productid" => $productid,
                        "quantity" => $post_qty,
                        "rest_qty" => $rest_qty
                    ];
                    $sentry++;
                } else {
                    // New product added during edit
                    $prdin = getProductData($krfconn, $projectid, "ProductID", $productid);
                    if (!empty($prdin)) {
                        $productName = $prdin[0]['ProductName'];
                        $ins_data = [
                            "stockorder_no" => $production_stockorder_no,
                            "projectid" => $projectid,
                            "companyid" => $companyid,
                            "subcompanyid" => $subcompanyid,
                            "clientid" => $clientid,
                            "productid" => $productid,
                            "product_name" => $productName,
                            "quantity" => $post_qty,
                            "expected_delivery_date" => $post_edd,
                            "sowingdate" => $post_sd,
                            "remarks" => "",
                            "status" => "Confirmed",
                            "created_by" => $loginid
                        ];
                        $sv = saveProductionStockOrder($krfconn, $ins_data, false);
                        // print_r($sv);
                        if ($sv['status'] === "success") {
                            $last_data = $ins_data;
                            $sentry++;
                        }
                    }
                }
            }
            if ($sentry > 0) {
                $krfconn->commit();
                echo json_encode(['status' => 'success', 'message' => 'Production order qty update successfully', 'items' => $last_data]);
            } else {
                echo json_encode(['status' => 'success', 'message' => 'No modifiable items were updated.', 'items' => []]);
            }
        } catch (Throwable $e) {
            $krfconn->rollback();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
} else if ($action == "changeProductionStockOrderProduct") {
    $stockorder_no = trim($_POST['stockorder_no'] ?? '');
    $new_product_id = trim($_POST['new_product_id'] ?? '');
    $new_sowing_date = trim($_POST['new_sowing_date'] ?? '');
    $new_delivery_date = trim($_POST['new_delivery_date'] ?? '');
    $remark = trim($_POST['remark'] ?? '');
    $projectid = isset($_POST['projectid']) ? (int) $_POST['projectid'] : 0;

    $loginroleid = (isset($_SESSION['loginroleid']) && $_SESSION['loginroleid'] !== '') ? intval($_SESSION['loginroleid']) : (isset($_POST['loginroleid']) ? intval($_POST['loginroleid']) : 0);
    $loginname = $_SESSION['loginname'] ?? $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'Admin';
    $update_tedit_sch = (isset($_POST['update_tedit_sch']) && (int)$_POST['update_tedit_sch'] === 1 && $loginroleid < 2);

    if (empty($stockorder_no) || empty($new_product_id) || empty($new_sowing_date) || $projectid <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required product change parameters.']);
        exit;
    }

    $todayDate = date('Y-m-d');
    if ($new_sowing_date < $todayDate) {
        echo json_encode(['status' => 'error', 'message' => 'New sowing date cannot be in the past (before today).']);
        exit;
    }

    if ($loginroleid >= 2) {
        echo json_encode(['status' => 'error', 'message' => 'Permission denied. Only authorized users can edit production stock orders.']);
        exit;
    }

    if (empty($remark)) {
        echo json_encode(['status' => 'error', 'message' => 'Please provide a reason/remark for changing the product.']);
        exit;
    }

    // 1. Verify that sowing has not started
    $stmt_chk = $krfconn->prepare("SELECT COUNT(*) as sowed_cnt FROM krf_polyhouse_sowingplant_details WHERE stockorder_no = ? AND projectid = ? AND sowingdate_internal IS NOT NULL AND sowingdate_internal <> '' AND sowingdate_internal <> '0000-00-00'");
    $stmt_chk->bind_param("si", $stockorder_no, $projectid);
    $stmt_chk->execute();
    $chk_res = $stmt_chk->get_result()->fetch_assoc();
    $stmt_chk->close();

    if (!empty($chk_res['sowed_cnt']) && $chk_res['sowed_cnt'] > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Cannot change product: Sowing has already started for this order (' . $chk_res['sowed_cnt'] . ' trays sowed).']);
        exit;
    }

    // 2. Fetch current stock order details
    $stmt_stk = $krfconn->prepare("SELECT id, psono, stockorder_no, projectid, clientid, productid, product_name, quantity, expected_delivery_date, sowingdate, remarks FROM krf_production_stockorder WHERE stockorder_no = ? AND projectid = ? LIMIT 1");
    $stmt_stk->bind_param("si", $stockorder_no, $projectid);
    $stmt_stk->execute();
    $cur_stock = $stmt_stk->get_result()->fetch_assoc();
    $stmt_stk->close();

    if (!$cur_stock) {
        echo json_encode(['status' => 'error', 'message' => 'Production stock order not found.']);
        exit;
    }

    $old_product_id = $cur_stock['productid'];
    $old_product_name = $cur_stock['product_name'];
    $old_sowing_date = $cur_stock['sowingdate'];
    $old_delivery_date = $cur_stock['expected_delivery_date'];
    $clientid = $cur_stock['clientid'];
    $psono = $cur_stock['psono'];

    // 3. Fetch new product details from product_master
    $stmt_newp = $krfconn->prepare("SELECT ProductID, ProductName, SeedId, ScionSeedId, IsGrafted, ProductGrowthId FROM product_master WHERE ProductID = ? LIMIT 1");
    $stmt_newp->bind_param("i", $new_product_id);
    $stmt_newp->execute();
    $new_prd = $stmt_newp->get_result()->fetch_assoc();
    $stmt_newp->close();

    if (!$new_prd) {
        echo json_encode(['status' => 'error', 'message' => 'New product not found in product master.']);
        exit;
    }

    $new_product_name = $new_prd['ProductName'];
    $new_seed_id = $new_prd['SeedId'] ?? '';
    $new_scion_seed_id = $new_prd['ScionSeedId'] ?? '';    // Fetch all linked client IDs from production stock order, sales order, and provisional sales order
    $clientIds = [];
    if (!empty($clientid)) {
        $clientIds[] = $clientid;
    }

    if (!empty($stockorder_no) || !empty($psono)) {
        $st_cl_lookup = $krfconn->prepare("
            SELECT DISTINCT clientid FROM krf_sales_order WHERE (psono = ? AND ? != '') AND IFNULL(clientid,'') != ''
            UNION
            SELECT DISTINCT clientid FROM krf_provisional_sales_order WHERE (psono = ? AND ? != '') AND IFNULL(clientid,'') != ''
            UNION
            SELECT DISTINCT clientid FROM krf_sales_order_items WHERE (stockorder_no = ? OR (psono = ? AND ? != '')) AND IFNULL(clientid,'') != ''
            UNION
            SELECT DISTINCT clientid FROM krf_provisional_sales_order_items WHERE (stockorder_no = ? OR (psono = ? AND ? != '')) AND IFNULL(clientid,'') != ''
        ");
        if ($st_cl_lookup) {
            $st_cl_lookup->bind_param("ssssssssss", $psono, $psono, $psono, $psono, $stockorder_no, $psono, $psono, $stockorder_no, $psono, $psono);
            $st_cl_lookup->execute();
            $cl_res = $st_cl_lookup->get_result();
            while ($cr = $cl_res->fetch_assoc()) {
                if (!empty($cr['clientid']) && !in_array($cr['clientid'], $clientIds)) {
                    $clientIds[] = $cr['clientid'];
                }
            }
            $st_cl_lookup->close();
        }
    }

    // Resolve client recipient details from krf_client_master
    $clientRecipients = [];
    $clientName = '';

    foreach ($clientIds as $cid) {
        $stmt_cl = $krfconn->prepare("SELECT client_id, client_name, phones, contact_forpo FROM krf_client_master WHERE client_id = ? LIMIT 1");
        if ($stmt_cl) {
            $stmt_cl->bind_param("s", $cid);
            $stmt_cl->execute();
            $cl_row = $stmt_cl->get_result()->fetch_assoc();
            $stmt_cl->close();
            if ($cl_row) {
                if (empty($clientName)) {
                    $clientName = $cl_row['client_name'] ?? '';
                }
                $cName = $cl_row['client_name'] ?? 'Customer';

                $rawPhoneList = [];
                if (!empty($cl_row['phones'])) $rawPhoneList[] = $cl_row['phones'];
                foreach ($rawPhoneList as $rawP) {
                    $splitNumbers = preg_split('/[\/,\s|]+/', $rawP);
                    foreach ($splitNumbers as $num) {
                        $cleaned = preg_replace('/[^0-9]/', '', $num);
                        if (strlen($cleaned) == 12 && substr($cleaned, 0, 2) == '91') {
                            $cleaned = substr($cleaned, 2);
                        }
                        if (strlen($cleaned) == 10 && !in_array($cleaned, array_column($clientRecipients, 'mobile'))) {
                            $clientRecipients[] = [
                                'clientid' => $cid,
                                'clientname' => $cName,
                                'mobile' => $cleaned
                            ];
                        }
                    }
                }
            }
        }
    }

    // Fallback lookup from krf_sales_order / krf_provisional_sales_order if client master didn't return a phone
    // if (empty($clientRecipients) && !empty($psono)) {
    //     $stmt_so_cl = $krfconn->prepare("
    //         SELECT clientid, clientname, mobileno FROM krf_sales_order WHERE psono = ? AND IFNULL(mobileno,'') != ''
    //         UNION
    //         SELECT clientid, clientname, mobileno FROM krf_provisional_sales_order WHERE psono = ? AND IFNULL(mobileno,'') != ''
    //         LIMIT 1
    //     ");
    //     if ($stmt_so_cl) {
    //         $stmt_so_cl->bind_param("ss", $psono, $psono);
    //         $stmt_so_cl->execute();
    //         $so_cl = $stmt_so_cl->get_result()->fetch_assoc();
    //         $stmt_so_cl->close();
    //         if ($so_cl && !empty($so_cl['mobileno'])) {
    //             if (empty($clientName)) $clientName = $so_cl['clientname'] ?? '';
    //             $cleaned = preg_replace('/[^0-9]/', '', $so_cl['mobileno']);
    //             if (strlen($cleaned) == 12 && substr($cleaned, 0, 2) == '91') $cleaned = substr($cleaned, 2);
    //             if (strlen($cleaned) == 10) {
    //                 $clientRecipients[] = [
    //                     'clientid' => $so_cl['clientid'] ?? '',
    //                     'clientname' => $so_cl['clientname'] ?? 'Customer',
    //                     'mobile' => $cleaned
    //                 ];
    //             }
    //         }
    //     }
    // }

    // 4. Formulate Remarks with complete audit log
    $dateFormatted = date('d-m-Y H:i:s');
    $auditLog = "[Product Changed by " . $loginname . " on " . $dateFormatted . "]\n"
              . "• Old Product: " . ($old_product_name ?: 'N/A') . " (Sowing: " . ($old_sowing_date ?: 'N/A') . ", Delivery: " . ($old_delivery_date ?: 'N/A') . ")\n"
              . "• New Product: " . $new_product_name . " (Sowing: " . $new_sowing_date . ", Delivery: " . ($new_delivery_date ?: 'N/A') . ")\n"
              . "• Reason: " . $remark;

    $existingRemarks = trim($cur_stock['remarks'] ?? '');
    $combinedRemarks = !empty($existingRemarks) ? $existingRemarks . "\n\n" . $auditLog : $auditLog;

    $krfconn->begin_transaction();
    try {
        // A. Update krf_production_stockorder
        $upd_pso = $krfconn->prepare("
            UPDATE krf_production_stockorder 
            SET productid = ?, 
                product_name = ?, 
                sowingdate = ?, 
                expected_delivery_date = ?, 
                remarks = ?, 
                updated_at = NOW() 
            WHERE stockorder_no = ? AND projectid = ?
        ");
        if (!$upd_pso) {
            throw new Exception("Error preparing stock order update: " . $krfconn->error);
        }
        $upd_pso->bind_param("ssssssi", $new_product_id, $new_product_name, $new_sowing_date, $new_delivery_date, $combinedRemarks, $stockorder_no, $projectid);
        $upd_pso->execute();
        $upd_pso->close();

        // B. Update krf_polyhouse_sowingschedule
        $upd_sch = $krfconn->prepare("
            UPDATE krf_polyhouse_sowingschedule 
            SET productid = ?, 
                productname = ?, 
                seedid = ?, 
                sowing_date = ?, 
                status = 'edited', 
                remarks = CONCAT(IFNULL(remarks, ''), '\n', ?), 
                updated_at = NOW(), 
                updated_by = ? 
            WHERE stockorder_no = ? AND projectid = ?
        ");
        if (!$upd_sch) {
            throw new Exception("Error preparing sowing schedule update: " . $krfconn->error);
        }
        $upd_sch->bind_param("ssssssss", $new_product_id, $new_product_name, $new_seed_id, $new_sowing_date, $auditLog, $loginname, $stockorder_no, $projectid);
        $upd_sch->execute();
        $upd_sch->close();

        // C. Update unsowed plant details
        $upd_sow = $krfconn->prepare("
            UPDATE krf_polyhouse_sowingplant_details 
            SET productid = ?, 
                productname = ?, 
                seedid = ?,
                sowing_date = ? 
            WHERE stockorder_no = ? AND projectid = ?
        ");
        if (!$upd_sow) {
            throw new Exception("Error preparing sowing plant details update: " . $krfconn->error);
        }
        $upd_sow->bind_param("sssssi", $new_product_id, $new_product_name, $new_seed_id, $new_sowing_date, $stockorder_no, $projectid);
        $upd_sow->execute();
        $upd_sow->close();

        // D. Update krf_productionorder_consumed
        $upd_con = $krfconn->prepare("
            UPDATE krf_productionorder_consumed 
            SET product_id = ?, 
                product_name = ?, 
                updated_at = NOW() 
            WHERE stockorderno = ? AND projectid = ?
        ");
        if ($upd_con) {
            $upd_con->bind_param("issi", $new_product_id, $new_product_name, $stockorder_no, $projectid);
            $upd_con->execute();
            $upd_con->close();
        }

        // E. Update linked provisional sales order items if applicable
        $upd_pso_items = $krfconn->prepare("UPDATE krf_provisional_sales_order_items SET product_id = ?, product_name = ?, expecteddeliverydate = ? WHERE (stockorder_no = ? OR (psono = ? AND psono <> '')) AND projectid = ?");
        if ($upd_pso_items) {
            $upd_pso_items->bind_param("sssssi", $new_product_id, $new_product_name, $new_delivery_date, $stockorder_no, $psono, $projectid);
            $upd_pso_items->execute();
            $upd_pso_items->close();
        }

        // F. Update linked confirmed sales order items if applicable
        $upd_so_items = $krfconn->prepare("UPDATE krf_sales_order_items SET product_id = ?, product_name = ?, deliverydate = ? WHERE (stockorder_no = ? OR (psono = ? AND psono <> '')) AND projectid = ?");
        if ($upd_so_items) {
            $upd_so_items->bind_param("sssssi", $new_product_id, $new_product_name, $new_delivery_date, $stockorder_no, $psono, $projectid);
            $upd_so_items->execute();
            $upd_so_items->close();
        }

        $krfconn->commit();

        // 5. Dispatch WhatsApp Notifications
        try {
            $smsUserRes = $krfconn->query("SELECT sourceid, whatsappno, apikey, accesstoken, status FROM whatsappsms_usermaster WHERE sourceid='1' AND status='active' LIMIT 1");
            if ($smsUserRes && $smsUserRes->num_rows > 0) {
                $smsUser = $smsUserRes->fetch_assoc();
                $sourceid = $smsUser['sourceid'];
                $fromWhatsapp = $smsUser['whatsappno'];
                $apikey = $smsUser['apikey'];
                $accesstoken = $smsUser['accesstoken'];

                $formattedSowingDate = !empty($new_sowing_date) ? date('d-M-Y', strtotime($new_sowing_date)) : 'N/A';
                $formattedDeliveryDate = !empty($new_delivery_date) ? date('d-M-Y', strtotime($new_delivery_date)) : 'N/A';
                $orderRef = $stockorder_no . (!empty($psono) ? " / " . $psono : "");

                // A. Dedicated Client-Facing Notification Message
                $waClientMsgTemplate = "🌱 *Karjee Farm – फसल / वैरायटी अपडेट (Product Update)*\n\n"
                                     . "प्रिय *{CLIENT_NAME}* जी,\n\n"
                                     . "आपके ऑर्डर के संबंध में सूचित किया जाता है कि आपकी फसल / वैरायटी को सफलतापूर्वक अपडेट कर दिया गया है:\n\n"
                                     . "📋 *ऑर्डर संदर्भ (Order No):* " . $orderRef . "\n"
                                     . "❌ *पूर्व वैरायटी (Previous):* " . ($old_product_name ?: 'N/A') . "\n"
                                     . "✅ *नई वैरायटी (Updated Variety):* " . $new_product_name . "\n"
                                     . "🌱 *बुवाई तिथि (Sowing Date):* " . $formattedSowingDate . "\n"
                                     . "🚚 *अनुमानित डिलीवरी तिथि (Expected Delivery):* " . $formattedDeliveryDate . "\n\n"
                                     . "📝 *विवरण:* " . $remark . "\n\n"
                                     . "यदि आपके पास कोई प्रश्न है या अन्य जानकारी चाहिए, तो कृपया संपर्क करें।\n\n"
                                     . "Karjee Farm चुनने के लिए धन्यवाद! 🌾\n"
                                     . "📞 संपर्क: +91 6261832409, +91 7879615628";

                // Dispatch to all client numbers found in client master / order records
                foreach ($clientRecipients as $cRecip) {
                    if (!empty($cRecip['mobile'])) {
                        $personalizedMsg = str_replace('{CLIENT_NAME}', $cRecip['clientname'] ?: 'किसान मित्र', $waClientMsgTemplate);
                        readyto_sentwhatsapp_message(
                            $krfconn,
                            $sourceid,
                            $fromWhatsapp,
                            $cRecip['mobile'],
                            $accesstoken,
                            $apikey,
                            "Product Update - Client Notice",
                            $personalizedMsg,
                            $cRecip['clientid'] ?? '',
                            'text',
                            '',
                            ''
                        );
                    }
                }

                // B. Internal Staff Alert Message (Admin & Sowing Departments)
                $waAdminMsg = "🌱 *Production Order Update - Product / Variety Changed*\n\n"
                            . "📋 *Order No:* " . $stockorder_no . "\n"
                            . (!empty($clientName) ? "👤 *Client:* " . $clientName . "\n" : "")
                            . (!empty($psono) ? "📄 *PSONO:* " . $psono . "\n" : "")
                            . "❌ *Previous Product:* " . ($old_product_name ?: 'N/A') . "\n"
                            . "✅ *New Product:* " . $new_product_name . "\n"
                            . "📅 *New Sowing Date:* " . $formattedSowingDate . "\n"
                            . "🚚 *New Delivery Date:* " . $formattedDeliveryDate . "\n"
                            . "📝 *Reason:* " . $remark . "\n"
                            . "👤 *Changed By:* " . $loginname . "\n"
                            . "⏰ *Timestamp:* " . date('d-M-Y H:i');

                // Notification to Admin Department (Department ID 3)
                $resAdmin = $krfconn->query("SELECT mobile_no FROM whatsapp_departmentperson WHERE department_id = 3 AND status = 'Active' AND receive_whatsapp = 'Yes'");
                if ($resAdmin) {
                    while ($p = $resAdmin->fetch_assoc()) {
                        if (!empty($p['mobile_no'])) {
                            readyto_sentwhatsapp_message($krfconn, $sourceid, $fromWhatsapp, $p['mobile_no'], $accesstoken, $apikey, "Product Change - Admin Alert", $waAdminMsg, '', 'text', '', '');
                        }
                    }
                }

                // Notification to Sowing Department (Department ID 6)
                $resSow = $krfconn->query("SELECT mobile_no FROM whatsapp_departmentperson WHERE department_id = 6 AND status = 'Active' AND receive_whatsapp = 'Yes'");
                if ($resSow) {
                    while ($p = $resSow->fetch_assoc()) {
                        if (!empty($p['mobile_no'])) {
                            readyto_sentwhatsapp_message($krfconn, $sourceid, $fromWhatsapp, $p['mobile_no'], $accesstoken, $apikey, "Product Change - Sowing Alert", $waAdminMsg, '', 'text', '', '');
                        }
                    }
                }
            }
        } catch (Exception $eNotify) {
            error_log("WhatsApp notification queue error on product change: " . $eNotify->getMessage());
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Product updated to ' . $new_product_name . ' and client & department notifications have been dispatched.'
        ]);
        exit;

    } catch (Exception $e) {
        $krfconn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Failed to update product: ' . $e->getMessage()]);
        exit;
    }
} else if ($action == "getPsoProductsForRemoval") {
    $psono = trim($_POST['psono'] ?? '');
    $projectid = isset($_POST['projectid']) ? (int) $_POST['projectid'] : 0;
    $clicked_stockorder_no = trim($_POST['stockorder_no'] ?? '');

    if (empty($psono) || $projectid <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid parameters. Provisional Order Number and Project ID are required.']);
        exit;
    }

    // 1. Fetch from krf_provisional_sales_order_items
    $stmt_psoi = $krfconn->prepare("
        SELECT 
            psoi.id as psoi_id,
            psoi.psono,
            psoi.product_id,
            psoi.product_name,
            psoi.stockorder_no,
            psoi.quantity,
            psoi.rate,
            psoi.amount,
            psoi.status as psoi_status
        FROM krf_provisional_sales_order_items psoi
        WHERE psoi.psono = ? AND (psoi.projectid = ? OR ? = 0) AND psoi.status NOT IN ('Cancelled', 'remove')
        ORDER BY psoi.id ASC
    ");
    $stmt_psoi->bind_param("sii", $psono, $projectid, $projectid);
    $stmt_psoi->execute();
    $psoi_rows = $stmt_psoi->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_psoi->close();

    // 2. Fetch from krf_production_stockorder
    $stmt_stk = $krfconn->prepare("
        SELECT 
            stk.id as stk_id,
            stk.stockorder_no,
            stk.psono,
            stk.productid,
            stk.product_name,
            stk.quantity,
            stk.status as stk_status,
            stk.sowingdate,
            stk.expected_delivery_date
        FROM krf_production_stockorder stk
        WHERE stk.psono = ? AND (stk.projectid = ? OR ? = 0) AND stk.status NOT IN ('remove', 'Cancelled', 'delete')
        ORDER BY stk.id ASC
    ");
    $stmt_stk->bind_param("sii", $psono, $projectid, $projectid);
    $stmt_stk->execute();
    $stk_rows = $stmt_stk->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_stk->close();

    // 3. Find client details
    $clientid = '';
    $clientname = '';
    $stmt_cp = $krfconn->prepare("SELECT clientname, clientid FROM krf_provisional_sales_order WHERE psono = ? AND projectid = ? LIMIT 1");
    if ($stmt_cp) {
        $stmt_cp->bind_param("si", $psono, $projectid);
        $stmt_cp->execute();
        $cp_row = $stmt_cp->get_result()->fetch_assoc();
        if ($cp_row) {
            $clientname = trim($cp_row['clientname'] ?? '');
            $clientid = trim($cp_row['clientid'] ?? '');
        }
        $stmt_cp->close();
    }
    if (empty($clientname) && !empty($clientid)) {
        $stmt_c = $krfconn->prepare("SELECT client_name FROM krf_client_master WHERE client_id = ? LIMIT 1");
        if ($stmt_c) {
            $stmt_c->bind_param("s", $clientid);
            $stmt_c->execute();
            $c_row = $stmt_c->get_result()->fetch_assoc();
            if ($c_row && !empty($c_row['client_name'])) $clientname = trim($c_row['client_name']);
            $stmt_c->close();
        }
    }

    // 4. Find linked SO
    $sono = '';
    $stmt_so = $krfconn->prepare("SELECT sono FROM krf_sales_order WHERE psono = ? AND projectid = ? LIMIT 1");
    if ($stmt_so) {
        $stmt_so->bind_param("si", $psono, $projectid);
        $stmt_so->execute();
        $so_row = $stmt_so->get_result()->fetch_assoc();
        if ($so_row && !empty($so_row['sono'])) $sono = trim($so_row['sono']);
        $stmt_so->close();
    }

    // 5. Find advance / paid amount
    $total_paid = 0.00;
    $stmt_pay = $krfconn->prepare("SELECT IFNULL(SUM(advanceamount), 0) AS total_paid FROM advance_payment_transactions WHERE (invoiceno = ? OR invoiceno = ?) AND (tedit IS NULL OR tedit = '')");
    if ($stmt_pay) {
        $stmt_pay->bind_param("ss", $psono, $sono);
        $stmt_pay->execute();
        $pay_row = $stmt_pay->get_result()->fetch_assoc();
        if ($pay_row) {
            $total_paid = floatval($pay_row['total_paid'] ?? 0);
        }
        $stmt_pay->close();
    }

    // 6. Combine products
    $products = [];
    $matched_stk_nos = [];
    $total_order_amount = 0.00;

    foreach ($psoi_rows as $psoi) {
        $stkNo = trim($psoi['stockorder_no'] ?? '');
        $matchedStk = null;
        if (!empty($stkNo)) {
            foreach ($stk_rows as $stk) {
                if (trim($stk['stockorder_no']) === $stkNo) {
                    $matchedStk = $stk;
                    $matched_stk_nos[] = $stkNo;
                    break;
                }
            }
        }
        if (!$matchedStk) {
            foreach ($stk_rows as $stk) {
                if (!in_array(trim($stk['stockorder_no']), $matched_stk_nos) && trim($stk['productid']) === trim($psoi['product_id'])) {
                    $matchedStk = $stk;
                    $matched_stk_nos[] = trim($stk['stockorder_no']);
                    break;
                }
            }
        }

        $stockOrderNo = $matchedStk ? trim($matchedStk['stockorder_no']) : $stkNo;
        $orderQty = (int)$psoi['quantity'];
        $amount = floatval($psoi['amount'] ?? 0);
        $rate = floatval($psoi['rate'] ?? 0);
        $total_order_amount += $amount;

        // Check sowing status in krf_polyhouse_sowingplant_details safely
        $sowedTrays = 0;
        if (!empty($stockOrderNo)) {
            $stmt_sow = $krfconn->prepare("SELECT COUNT(*) as cnt FROM krf_polyhouse_sowingplant_details WHERE stockorder_no = ? AND sowingdate_internal IS NOT NULL AND sowingdate_internal > '1970-01-01'");
            if ($stmt_sow) {
                $stmt_sow->bind_param("s", $stockOrderNo);
                $stmt_sow->execute();
                $sowedTrays = (int)($stmt_sow->get_result()->fetch_assoc()['cnt'] ?? 0);
                $stmt_sow->close();
            }
        }

        $canRemove = ($sowedTrays === 0);
        $products[] = [
            'psoi_id' => (int)$psoi['psoi_id'],
            'stk_id' => $matchedStk ? (int)$matchedStk['stk_id'] : null,
            'stockorder_no' => $stockOrderNo,
            'product_id' => $psoi['product_id'],
            'product_name' => $psoi['product_name'],
            'quantity' => $orderQty,
            'rate' => $rate,
            'amount' => $amount,
            'sowed_trays' => $sowedTrays,
            'can_remove' => $canRemove,
            'cannot_remove_reason' => $canRemove ? '' : "Sowing already started ($sowedTrays trays sowed)",
            'is_clicked' => (!empty($clicked_stockorder_no) && $stockOrderNo === $clicked_stockorder_no)
        ];
    }

    foreach ($stk_rows as $stk) {
        $stkNo = trim($stk['stockorder_no']);
        if (!in_array($stkNo, $matched_stk_nos)) {
            $sowedTrays = 0;
            $stmt_sow = $krfconn->prepare("SELECT COUNT(*) as cnt FROM krf_polyhouse_sowingplant_details WHERE stockorder_no = ? AND sowingdate_internal IS NOT NULL AND sowingdate_internal > '1970-01-01'");
            if ($stmt_sow) {
                $stmt_sow->bind_param("s", $stkNo);
                $stmt_sow->execute();
                $sowedTrays = (int)($stmt_sow->get_result()->fetch_assoc()['cnt'] ?? 0);
                $stmt_sow->close();
            }

            $canRemove = ($sowedTrays === 0);
            $qty = (int)$stk['quantity'];
            $products[] = [
                'psoi_id' => null,
                'stk_id' => (int)$stk['stk_id'],
                'stockorder_no' => $stkNo,
                'product_id' => $stk['productid'],
                'product_name' => $stk['product_name'],
                'quantity' => $qty,
                'rate' => 0.00,
                'amount' => 0.00,
                'sowed_trays' => $sowedTrays,
                'can_remove' => $canRemove,
                'cannot_remove_reason' => $canRemove ? '' : "Sowing already started ($sowedTrays trays sowed)",
                'is_clicked' => (!empty($clicked_stockorder_no) && $stkNo === $clicked_stockorder_no)
            ];
        }
    }

    echo json_encode([
        'status' => 'success',
        'data' => [
            'psono' => $psono,
            'sono' => $sono,
            'clientid' => $clientid,
            'clientname' => $clientname,
            'total_order_amount' => $total_order_amount,
            'total_paid' => $total_paid,
            'products' => $products
        ]
    ]);
    exit;
} else if ($action == "removeProductionStockOrderWithRemark") {
    $stockorder_no = trim($_POST['stockorder_no'] ?? '');
    $psono = trim($_POST['psono'] ?? '');
    $projectid = isset($_POST['projectid']) ? (int) $_POST['projectid'] : 0;
    $remark = trim($_POST['remark'] ?? '');
    $is_forced = isset($_POST['is_forced']) ? (int) $_POST['is_forced'] : 0;

    $loginroleid = (isset($_SESSION['loginroleid']) && $_SESSION['loginroleid'] !== '') ? intval($_SESSION['loginroleid']) : (isset($_POST['loginroleid']) ? intval($_POST['loginroleid']) : 0);
    $loginname = $_SESSION['loginname'] ?? $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'Admin';
    $update_tedit_sch = (isset($_POST['update_tedit_sch']) && (int)$_POST['update_tedit_sch'] === 1 && $loginroleid < 2);
    $loginid = $_SESSION['loginid'] ?? $_SESSION['userid'] ?? 0;
    $userForUpdate = !empty($loginid) ? $loginid : $loginname;

    // Parse stockorder_nos (array or JSON or comma-separated or single)
    $stockorder_nos = [];
    if (!empty($_POST['stockorder_nos'])) {
        if (is_array($_POST['stockorder_nos'])) {
            $stockorder_nos = array_values(array_filter(array_map('trim', $_POST['stockorder_nos'])));
        } else {
            $decoded = json_decode($_POST['stockorder_nos'], true);
            if (is_array($decoded)) {
                $stockorder_nos = array_values(array_filter(array_map('trim', $decoded)));
            } else {
                $stockorder_nos = array_values(array_filter(explode(',', trim($_POST['stockorder_nos']))));
            }
        }
    }
    if (empty($stockorder_nos) && !empty($stockorder_no)) {
        $stockorder_nos = [$stockorder_no];
    }

    // Parse psoi_ids (array or JSON or comma-separated)
    $psoi_ids = [];
    if (!empty($_POST['psoi_ids'])) {
        if (is_array($_POST['psoi_ids'])) {
            $psoi_ids = array_values(array_filter(array_map('intval', $_POST['psoi_ids'])));
        } else {
            $decoded = json_decode($_POST['psoi_ids'], true);
            if (is_array($decoded)) {
                $psoi_ids = array_values(array_filter(array_map('intval', $decoded)));
            } else {
                $psoi_ids = array_values(array_filter(array_map('intval', explode(',', trim($_POST['psoi_ids'])))));
            }
        }
    }

    if ((empty($stockorder_nos) && empty($psoi_ids)) || $projectid <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'No products selected for removal or invalid project ID.']);
        exit;
    }

    if ($loginroleid >= 2) {
        echo json_encode(['status' => 'error', 'message' => 'Permission denied. Only authorized users can remove production stock orders.']);
        exit;
    }

    if (empty($remark)) {
        echo json_encode(['status' => 'error', 'message' => 'Removal remark is required. Please state why you are removing this product.']);
        exit;
    }

    // 1. Check if sowing has started for ANY of the selected stock orders safely (Root & Scion for grafted)
    foreach ($stockorder_nos as $chkStk) {
        if (empty($chkStk)) continue;
        $swCheck = checkStockOrderSowingStatus($krfconn, $chkStk, $projectid);
        if ($swCheck['sowing_started']) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Cannot remove order ' . $chkStk . ': ' . $swCheck['reason'] . '. Sown products cannot be deleted/removed.'
            ]);
            exit;
        }
    }

    // 2. Fetch context (psono, clientid, companyid, subcompanyid) from krf_production_stockorder or krf_provisional_sales_order
    $clientid = '';
    $clientname = '';
    $companyid = 0;
    $subcompanyid = 0;

    if (!empty($stockorder_nos)) {
        $firstStk = $stockorder_nos[0];
        $stmt_stk = $krfconn->prepare("SELECT id, psono, stockorder_no, projectid, companyid, subcompanyid, clientid, productid, product_name, quantity, remarks, status FROM krf_production_stockorder WHERE stockorder_no = ? AND projectid = ? LIMIT 1");
        if ($stmt_stk) {
            $stmt_stk->bind_param("si", $firstStk, $projectid);
            $stmt_stk->execute();
            $stk_res = $stmt_stk->get_result()->fetch_assoc();
            if ($stk_res) {
                if (empty($psono)) $psono = trim($stk_res['psono'] ?? '');
                $clientid = trim($stk_res['clientid'] ?? '');
                $companyid = (int)($stk_res['companyid'] ?? 0);
                $subcompanyid = (int)($stk_res['subcompanyid'] ?? 0);
            }
            $stmt_stk->close();
        }
    }

    if (!empty($psono)) {
        $stmt_cp = $krfconn->prepare("SELECT clientname, clientid, companyid, subcompanyid FROM krf_provisional_sales_order WHERE psono = ? AND projectid = ? LIMIT 1");
        if ($stmt_cp) {
            $stmt_cp->bind_param("si", $psono, $projectid);
            $stmt_cp->execute();
            $cp_row = $stmt_cp->get_result()->fetch_assoc();
            if ($cp_row) {
                if (empty($clientname)) $clientname = trim($cp_row['clientname'] ?? '');
                if (empty($clientid)) $clientid = trim($cp_row['clientid'] ?? '');
                if ($companyid <= 0) $companyid = (int)($cp_row['companyid'] ?? 0);
                if ($subcompanyid <= 0) $subcompanyid = (int)($cp_row['subcompanyid'] ?? 0);
            }
            $stmt_cp->close();
        }
    }

    if (empty($clientname) && !empty($clientid)) {
        $stmt_c = $krfconn->prepare("SELECT client_name FROM krf_client_master WHERE client_id = ? LIMIT 1");
        if ($stmt_c) {
            $stmt_c->bind_param("s", $clientid);
            $stmt_c->execute();
            $c_row = $stmt_c->get_result()->fetch_assoc();
            if ($c_row && !empty($c_row['client_name'])) $clientname = trim($c_row['client_name']);
            $stmt_c->close();
        }
    }

    // 3. Find linked Final Sales Order (sono)
    $sono = '';
    if (!empty($psono)) {
        $stmt_so2 = $krfconn->prepare("SELECT sono FROM krf_sales_order WHERE psono = ? AND projectid = ? LIMIT 1");
        if ($stmt_so2) {
            $stmt_so2->bind_param("si", $psono, $projectid);
            $stmt_so2->execute();
            $so2_row = $stmt_so2->get_result()->fetch_assoc();
            if ($so2_row) $sono = trim($so2_row['sono'] ?? '');
            $stmt_so2->close();
        }
    }

    // 4. Calculate total order value, cancelled items value, and total paid advance
    $total_order_amount = 0.00;
    $total_items_count = 0;
    $cancelled_items_amount = 0.00;
    $cancelled_items_qty = 0;
    $cancelled_descriptions = [];
    $cancelled_product_names = [];
    $cancelled_product_ids = [];

    if (!empty($psono)) {
        $stmt_all_psoi = $krfconn->prepare("SELECT id, product_id, product_name, stockorder_no, quantity, rate, amount FROM krf_provisional_sales_order_items WHERE psono = ? AND projectid = ? AND status NOT IN ('Cancelled', 'remove')");
        if ($stmt_all_psoi) {
            $stmt_all_psoi->bind_param("si", $psono, $projectid);
            $stmt_all_psoi->execute();
            $all_psoi = $stmt_all_psoi->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt_all_psoi->close();

            $total_items_count = count($all_psoi);
            foreach ($all_psoi as $it) {
                $itAmt = floatval($it['amount'] ?? 0);
                $total_order_amount += $itAmt;

                $isThisCancelled = in_array((int)$it['id'], $psoi_ids) || (!empty($it['stockorder_no']) && in_array(trim($it['stockorder_no']), $stockorder_nos));
                if ($isThisCancelled) {
                    $cancelled_items_amount += $itAmt;
                    $cancelled_items_qty += (int)$it['quantity'];
                    $cancelled_descriptions[] = $it['product_name'] . " (Qty: " . $it['quantity'] . ", Amt: ₹" . number_format($itAmt, 2) . ")";
                    $cancelled_product_names[] = $it['product_name'];
                    $cancelled_product_ids[] = $it['product_id'];
                    if (!in_array((int)$it['id'], $psoi_ids)) $psoi_ids[] = (int)$it['id'];
                    if (!empty($it['stockorder_no']) && !in_array(trim($it['stockorder_no']), $stockorder_nos)) $stockorder_nos[] = trim($it['stockorder_no']);
                }
            }
        }
    }

    // If any selected stockorder_nos were not in psoi, include their details
    foreach ($stockorder_nos as $stkNo) {
        $stmt_extra = $krfconn->prepare("SELECT productid, product_name, quantity FROM krf_production_stockorder WHERE stockorder_no = ? AND projectid = ? LIMIT 1");
        if ($stmt_extra) {
            $stmt_extra->bind_param("si", $stkNo, $projectid);
            $stmt_extra->execute();
            $extra_row = $stmt_extra->get_result()->fetch_assoc();
            if ($extra_row) {
                if (!in_array($extra_row['product_name'], $cancelled_product_names)) {
                    $cancelled_product_names[] = $extra_row['product_name'];
                    $cancelled_product_ids[] = $extra_row['productid'];
                    $cancelled_items_qty += (int)$extra_row['quantity'];
                    $cancelled_descriptions[] = $extra_row['product_name'] . " (StockOrder: " . $stkNo . ", Qty: " . $extra_row['quantity'] . ")";
                }
            }
            $stmt_extra->close();
        }
    }

    // Advance payment check
    $amount_paid = 0.00;
    if (!empty($psono) || !empty($sono)) {
        $stmt_pay = $krfconn->prepare("SELECT IFNULL(SUM(advanceamount), 0) AS total_paid FROM advance_payment_transactions WHERE (invoiceno = ? OR invoiceno = ?) AND (tedit IS NULL OR tedit = '')");
        if ($stmt_pay) {
            $stmt_pay->bind_param("ss", $psono, $sono);
            $stmt_pay->execute();
            $pay_row = $stmt_pay->get_result()->fetch_assoc();
            if ($pay_row) {
                $amount_paid = floatval($pay_row['total_paid'] ?? 0);
            }
            $stmt_pay->close();
        }
    }

    $cancelledCount = count($cancelled_descriptions);
    $remainingActiveCount = $total_items_count > 0 ? ($total_items_count - $cancelledCount) : 0;
    $isFullOrderCancellation = (!empty($psono) && $total_items_count > 0 && $remainingActiveCount <= 0);

    // Calculate Credit Note amount
    $credit_amount = 0.00;
    if ($amount_paid > 0) {
        if ($isFullOrderCancellation) {
            $credit_amount = $amount_paid;
        } else if ($total_order_amount > 0 && $cancelled_items_amount > 0) {
            $rest_order_amount = max(0, $total_order_amount - $cancelled_items_amount);
            if ($rest_order_amount >= $amount_paid) {
                // If rest of order amount is greater than or equal to received amount,
                // no need to create credit note or refund as remaining order covers the advance
                $credit_amount = 0.00;
            } else {
                $credit_amount = round($amount_paid - $rest_order_amount, 2);
            }
        }
    }

    $dateFormatted = date('d-m-Y H:i:s');
    $removedLog = "[Removed by " . $loginname . " on " . $dateFormatted . "]: " . $remark;

    $krfconn->begin_transaction();
    try {
        $creditnote_no = '';

        // 5. Generate and insert Credit Note if credit_amount > 0
        if ($credit_amount > 0) {
            $cn_res = $krfconn->query("SELECT MAX(creditnote_id) AS max_id FROM krf_creditnote");
            $next_cn_id = 1;
            if ($cn_res && $row = $cn_res->fetch_assoc()) {
                $next_cn_id = intval($row['max_id'] ?? 0) + 1;
            }
            $creditnote_no = "CN-" . date('Y') . "-" . str_pad((string)$next_cn_id, 5, '0', STR_PAD_LEFT);

            $cancelTypeStr = $isFullOrderCancellation ? "Full Order Cancellation" : "Partial Order Cancellation";
            $narration = "Credit Note of Rs. " . number_format($credit_amount, 2) . " ($cancelTypeStr) for Client: " . ($clientname ?: $clientid) . " against removal of: " . implode("; ", $cancelled_descriptions) . ". Linked PSO: " . ($psono ?: 'N/A') . (!empty($sono) ? ", SO: " . $sono : "") . ". Total Order: Rs. " . number_format($total_order_amount, 2) . ", Paid: Rs. " . number_format($amount_paid, 2) . ". Reason: " . $remark . ". Processed by " . $loginname . " on " . $dateFormatted . ".";

            $stmt_cn = $krfconn->prepare("INSERT INTO krf_creditnote (
                creditnote_no, creditnote_date, projectid, companyid, subcompanyid,
                clientid, clientname, psono, sono, stockorder_no, productid, product_name,
                order_quantity, amount_paid, credit_amount, narration, reason, status, created_by, created_at, updated_at
            ) VALUES (
                ?, CURDATE(), ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, 'Active', ?, NOW(), NOW()
            )");

            if (!$stmt_cn) throw new Exception("Prepare credit note insert failed: " . $krfconn->error);

            $stkStr = implode(', ', $stockorder_nos);
            $pidStr = implode(', ', array_unique($cancelled_product_ids));
            $pnameStr = implode(', ', array_unique($cancelled_product_names));

            $stmt_cn->bind_param(
                "siiisssssssiddsss",
                $creditnote_no,
                $projectid,
                $companyid,
                $subcompanyid,
                $clientid,
                $clientname,
                $psono,
                $sono,
                $stkStr,
                $pidStr,
                $pnameStr,
                $cancelled_items_qty,
                $amount_paid,
                $credit_amount,
                $narration,
                $remark,
                $loginname
            );

            if (!$stmt_cn->execute()) {
                throw new Exception("Execute credit note insert failed: " . $stmt_cn->error);
            }
            $stmt_cn->close();

            // Insert into krf_tbl_client_statement
            $stmt_stmt = $krfconn->prepare("INSERT INTO krf_tbl_client_statement (
                project_id, client_id, txn_date, txn_type, txn_no, description, debit_amount, credit_amount, running_balance, created_at
            ) VALUES (
                ?, ?, CURDATE(), 'CREDIT_NOTE', ?, ?, 0.00, ?, 0.00, NOW()
            )");
            if ($stmt_stmt) {
                $stmt_stmt->bind_param("isssd", $projectid, $clientid, $creditnote_no, $narration, $credit_amount);
                $stmt_stmt->execute();
                $stmt_stmt->close();
            }
        }

        // 6. Update selected stock orders
        foreach ($stockorder_nos as $stkNo) {
            if (empty($stkNo)) continue;

            // krf_production_stockorder -> status = 'Cancelled' (tedit not updated)
            $upd_stk = $krfconn->prepare("UPDATE krf_production_stockorder SET status = 'Cancelled', remarks = CONCAT(IFNULL(remarks, ''), '\n', ?) WHERE stockorder_no = ? AND projectid = ?");
            if ($upd_stk) {
                $upd_stk->bind_param("ssi", $removedLog, $stkNo, $projectid);
                $upd_stk->execute();
                $upd_stk->close();
            }

            // krf_polyhouse_sowingschedule -> status = 'Cancelled' (tedit updated ONLY if loginroleid < 2 and update_tedit_sch is 1)
            if ($update_tedit_sch) {
                $upd_sch = $krfconn->prepare("UPDATE krf_polyhouse_sowingschedule SET status = 'Cancelled', tedit = 'delete', teditremark = ?, remarks = CONCAT(IFNULL(remarks, ''), '\n', ?) WHERE stockorder_no = ? AND projectid = ?");
                if ($upd_sch) {
                    $upd_sch->bind_param("sssi", $remark, $removedLog, $stkNo, $projectid);
                    $upd_sch->execute();
                    $upd_sch->close();
                }
            } else {
                $upd_sch = $krfconn->prepare("UPDATE krf_polyhouse_sowingschedule SET status = 'Cancelled', remarks = CONCAT(IFNULL(remarks, ''), '\n', ?) WHERE stockorder_no = ? AND projectid = ?");
                if ($upd_sch) {
                    $upd_sch->bind_param("ssi", $removedLog, $stkNo, $projectid);
                    $upd_sch->execute();
                    $upd_sch->close();
                }
            }

            // krf_polyhouse_sowingplant_details -> status = 'Removed' (tedit not updated)
            $upd_sow = $krfconn->prepare("UPDATE krf_polyhouse_sowingplant_details SET status = 'Removed' WHERE stockorder_no = ? AND projectid = ?");
            if ($upd_sow) {
                $upd_sow->bind_param("si", $stkNo, $projectid);
                $upd_sow->execute();
                $upd_sow->close();
            }

            // krf_productionorder_consumed -> confirmstatus = 'cancelled', status = 'Cancelled' (tedit not updated)
            $upd_con = $krfconn->prepare("UPDATE krf_productionorder_consumed SET confirmstatus = 'cancelled', status = 'Cancelled' WHERE stockorderno = ? AND projectid = ?");
            if ($upd_con) {
                $upd_con->bind_param("si", $stkNo, $projectid);
                $upd_con->execute();
                $upd_con->close();
            }

            // krf_sales_order_items -> status = 'Cancelled' (tedit not updated)
            $upd_so_it = $krfconn->prepare("UPDATE krf_sales_order_items SET status = 'Cancelled' WHERE stockorder_no = ? AND projectid = ?");
            if ($upd_so_it) {
                $upd_so_it->bind_param("si", $stkNo, $projectid);
                $upd_so_it->execute();
                $upd_so_it->close();
            }

            // krf_provisional_sales_order_items -> status = 'Cancelled' (tedit not updated)
            $upd_pso_it = $krfconn->prepare("UPDATE krf_provisional_sales_order_items SET status = 'Cancelled' WHERE stockorder_no = ? AND projectid = ?");
            if ($upd_pso_it) {
                $upd_pso_it->bind_param("si", $stkNo, $projectid);
                $upd_pso_it->execute();
                $upd_pso_it->close();
            }
        }

        // Also update by psoi_ids
        foreach ($psoi_ids as $pid) {
            if ($pid <= 0) continue;
            $upd_psoi_id = $krfconn->prepare("UPDATE krf_provisional_sales_order_items SET status = 'Cancelled' WHERE id = ? AND projectid = ?");
            if ($upd_psoi_id) {
                $upd_psoi_id->bind_param("ii", $pid, $projectid);
                $upd_psoi_id->execute();
                $upd_psoi_id->close();
            }
        }

        // 7. Handle Full vs Partial Cancellation on main headers
        if (!empty($psono)) {
            if ($isFullOrderCancellation) {
                // Cancel main PSO
                $cancelPsoMsg = "[Cancelled on Stock Order Removal: " . implode(', ', $stockorder_nos) . " by " . $loginname . " on " . $dateFormatted . " - Reason: " . $remark . "]";
                $upd_pso_main = $krfconn->prepare("UPDATE krf_provisional_sales_order SET status = 'Cancelled', updated_by = ?, updated_at = NOW(), remarks = CONCAT(IFNULL(remarks, ''), '\n', ?) WHERE psono = ? AND projectid = ?");
                if ($upd_pso_main) {
                    $upd_pso_main->bind_param("sssi", $userForUpdate, $cancelPsoMsg, $psono, $projectid);
                    $upd_pso_main->execute();
                    $upd_pso_main->close();
                }

                // Cancel main SO
                if (!empty($sono)) {
                    $cancelSoMsg = "[Cancelled on Stock Order Removal: " . implode(', ', $stockorder_nos) . " by " . $loginname . " on " . $dateFormatted . " - Reason: " . $remark . "]";
                    $upd_so_main = $krfconn->prepare("UPDATE krf_sales_order SET status = 'Cancelled', updated_by = ?, updated_at = NOW(), remarks = CONCAT(IFNULL(remarks, ''), '\n', ?) WHERE sono = ? AND projectid = ?");
                    if ($upd_so_main) {
                        $upd_so_main->bind_param("ssssi", $userForUpdate, $cancelSoMsg, $sono, $projectid);
                        $upd_so_main->execute();
                        $upd_so_main->close();
                    }
                }
            } else {
                // Partial cancellation: Log in PSO remarks, keep status active
                $partialPsoMsg = "[Partial Product Removal: " . implode(', ', $cancelled_descriptions) . " removed by " . $loginname . " on " . $dateFormatted . " - Reason: " . $remark . "]";
                $upd_pso_part = $krfconn->prepare("UPDATE krf_provisional_sales_order SET updated_by = ?, updated_at = NOW(), remarks = CONCAT(IFNULL(remarks, ''), '\n', ?) WHERE psono = ? AND projectid = ?");
                if ($upd_pso_part) {
                    $upd_pso_part->bind_param("sssi", $userForUpdate, $partialPsoMsg, $psono, $projectid);
                    $upd_pso_part->execute();
                    $upd_pso_part->close();
                }

                // In SO, also update remarks and reduce totals
                if (!empty($sono)) {
                    $partialSoMsg = "[Partial Product Removal: " . implode(', ', $cancelled_descriptions) . " removed by " . $loginname . " on " . $dateFormatted . " - Reason: " . $remark . "]";
                    $upd_so_part = $krfconn->prepare("UPDATE krf_sales_order SET total_amount = GREATEST(0, total_amount - ?), grand_total = GREATEST(0, grand_total - ?), updated_by = ?, updated_at = NOW(), remarks = CONCAT(IFNULL(remarks, ''), '\n', ?) WHERE sono = ? AND projectid = ?");
                    if ($upd_so_part) {
                        $upd_so_part->bind_param("ddsssi", $cancelled_items_amount, $cancelled_items_amount, $userForUpdate, $partialSoMsg, $sono, $projectid);
                        $upd_so_part->execute();
                        $upd_so_part->close();
                    }
                }
            }
        }

        $krfconn->commit();

        $actionType = $isFullOrderCancellation ? "Full provisional order ($psono) cancelled" : "Selected products removed";
        $successMsg = $actionType . " successfully. " . count($stockorder_nos) . " production order(s) removed.";
        if (!empty($creditnote_no) && $credit_amount > 0) {
            $successMsg .= " Credit Note " . $creditnote_no . " of ₹" . number_format($credit_amount, 2) . " has been issued to Client: " . ($clientname ?: $clientid) . ".";
        }

        echo json_encode([
            'status' => 'success',
            'message' => $successMsg,
            'creditnote_no' => $creditnote_no,
            'credit_amount' => $credit_amount,
            'is_full_cancellation' => $isFullOrderCancellation
        ]);
        exit;
    } catch (Exception $e) {
        $krfconn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Failed to remove production stock order: ' . $e->getMessage()]);
        exit;
    }
} else if ($action == "getOrderRemovalPreview") {
    $stockorder_no = trim($_POST['stockorder_no'] ?? '');
    $projectid = isset($_POST['projectid']) ? (int) $_POST['projectid'] : 0;

    if (empty($stockorder_no) || $projectid <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid parameters.']);
        exit;
    }

    $stmt_stk = $krfconn->prepare("SELECT id, psono, stockorder_no, projectid, clientid, productid, product_name, quantity, remarks, status, sowingdate, expected_delivery_date FROM krf_production_stockorder WHERE stockorder_no = ? AND projectid = ? LIMIT 1");
    $stmt_stk->bind_param("si", $stockorder_no, $projectid);
    $stmt_stk->execute();
    $stk_res = $stmt_stk->get_result()->fetch_assoc();
    $stmt_stk->close();

    if (!$stk_res) {
        echo json_encode(['status' => 'error', 'message' => 'Production stock order not found.']);
        exit;
    }

    $psono = trim($stk_res['psono'] ?? '');
    $clientid = trim($stk_res['clientid'] ?? '');
    $productid = trim($stk_res['productid'] ?? '');
    $product_name = trim($stk_res['product_name'] ?? '');
    $quantity = (int)($stk_res['quantity'] ?? 0);
    $sowingdate = $stk_res['sowingdate'] ?? '';
    $expected_delivery_date = $stk_res['expected_delivery_date'] ?? '';

    // Find linked Final Sales Order (sono)
    $sono = '';
    $stmt_sono = $krfconn->prepare("SELECT sono, psono FROM krf_productionorder_consumed WHERE (stockorderno = ? OR psono = ?) AND projectid = ? AND NULLIF(TRIM(sono), '') IS NOT NULL LIMIT 1");
    if ($stmt_sono) {
        $stmt_sono->bind_param("ssi", $stockorder_no, $psono, $projectid);
        $stmt_sono->execute();
        $so_row = $stmt_sono->get_result()->fetch_assoc();
        if ($so_row) {
            $sono = trim($so_row['sono'] ?? '');
            if (empty($psono)) $psono = trim($so_row['psono'] ?? '');
        }
        $stmt_sono->close();
    }
    if (empty($sono) && !empty($psono)) {
        $stmt_so2 = $krfconn->prepare("SELECT sono FROM krf_sales_order WHERE psono = ? AND projectid = ? LIMIT 1");
        if ($stmt_so2) {
            $stmt_so2->bind_param("si", $psono, $projectid);
            $stmt_so2->execute();
            $so2_row = $stmt_so2->get_result()->fetch_assoc();
            if ($so2_row) $sono = trim($so2_row['sono'] ?? '');
            $stmt_so2->close();
        }
    }

    // Find Client Name
    $clientname = '';
    if (!empty($clientid)) {
        $stmt_c = $krfconn->prepare("SELECT client_name FROM krf_client_master WHERE client_id = ? LIMIT 1");
        if ($stmt_c) {
            $stmt_c->bind_param("s", $clientid);
            $stmt_c->execute();
            $c_row = $stmt_c->get_result()->fetch_assoc();
            if ($c_row && !empty($c_row['client_name'])) $clientname = trim($c_row['client_name']);
            $stmt_c->close();
        }
    }
    if (empty($clientname) && !empty($psono)) {
        $stmt_cp = $krfconn->prepare("SELECT clientname, clientid FROM krf_provisional_sales_order WHERE psono = ? AND projectid = ? LIMIT 1");
        if ($stmt_cp) {
            $stmt_cp->bind_param("si", $psono, $projectid);
            $stmt_cp->execute();
            $cp_row = $stmt_cp->get_result()->fetch_assoc();
            if ($cp_row) {
                if (!empty($cp_row['clientname'])) $clientname = trim($cp_row['clientname']);
                if (empty($clientid) && !empty($cp_row['clientid'])) $clientid = trim($cp_row['clientid']);
            }
            $stmt_cp->close();
        }
    }

    // Find advance / payment received
    $amount_paid = 0.00;
    if (!empty($psono) || !empty($sono)) {
        $stmt_pay = $krfconn->prepare("SELECT IFNULL(SUM(advanceamount), 0) AS total_paid FROM advance_payment_transactions WHERE (invoiceno = ? OR invoiceno = ?) AND (tedit IS NULL OR tedit = '')");
        if ($stmt_pay) {
            $stmt_pay->bind_param("ss", $psono, $sono);
            $stmt_pay->execute();
            $pay_row = $stmt_pay->get_result()->fetch_assoc();
            if ($pay_row) {
                $amount_paid = floatval($pay_row['total_paid'] ?? 0);
            }
            $stmt_pay->close();
        }
    }

    // Find seed / variety details from seed_master
    $seed_variety = '';
    $stmt_var = $krfconn->prepare("
        SELECT pm.IsGrafted,
               sm.VarietyofSeed AS root_variety, sm.SeedName AS root_seedname,
               sm_scion.VarietyofSeed AS scion_variety, sm_scion.SeedName AS scion_seedname
        FROM product_master pm
        LEFT JOIN seed_master sm ON pm.SeedId = sm.SeedID
        LEFT JOIN seed_master sm_scion ON pm.ScionSeedId = sm_scion.SeedID
        WHERE pm.ProductID = ?
        LIMIT 1
    ");
    if ($stmt_var) {
        $stmt_var->bind_param("s", $productid);
        $stmt_var->execute();
        $var_row = $stmt_var->get_result()->fetch_assoc();
        if ($var_row) {
            $isG = (!empty($var_row['IsGrafted']) && in_array(strtolower(trim($var_row['IsGrafted'])), ['yes', 'grafted']));
            if ($isG && !empty($var_row['scion_variety'])) {
                $seed_variety = "Root: " . ($var_row['root_variety'] ?: $var_row['root_seedname'] ?: 'N/A') . " / Scion: " . ($var_row['scion_variety'] ?: $var_row['scion_seedname'] ?: 'N/A');
            } else {
                $seed_variety = $var_row['root_variety'] ?: $var_row['root_seedname'] ?: '';
            }
        }
        $stmt_var->close();
    }

    $sowStatus = checkStockOrderSowingStatus($krfconn, $stockorder_no, $projectid);
    $is_client_order = (!empty($clientname) && strtolower(trim($clientname)) !== 'karjee farms') || (!empty($psono) && stripos($psono, 'kf-ps') !== false);

    echo json_encode([
        'status' => 'success',
        'data' => [
            'stockorder_no' => $stockorder_no,
            'psono' => $psono,
            'sono' => $sono,
            'clientid' => $clientid,
            'clientname' => $clientname,
            'is_client_order' => $is_client_order ? 1 : 0,
            'productid' => $productid,
            'product_name' => $product_name,
            'seed_variety' => $seed_variety,
            'is_grafted' => $sowStatus['is_grafted'] ? 1 : 0,
            'quantity' => $quantity,
            'sowingdate' => $sowingdate,
            'expected_delivery_date' => $expected_delivery_date,
            'amount_paid' => $amount_paid,
            'sowing_started' => $sowStatus['sowing_started'] ? 1 : 0,
            'root_sowed_cnt' => $sowStatus['root_sowed_cnt'],
            'scion_sowed_cnt' => $sowStatus['scion_sowed_cnt'],
            'total_sowed_cnt' => $sowStatus['total_sowed_cnt'],
            'cannot_remove_reason' => $sowStatus['reason']
        ]
    ]);
    exit;

} else if ($action == "deleteProductionStockOrder") {
    $stockorder_no = $_POST['stockorder_no'] ?? '';
    $projectid = isset($_POST['projectid']) ? (int) $_POST['projectid'] : 0;
    
    if (empty($stockorder_no) || $projectid <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid stock order parameters']);
        exit;
    }
    
    // 1. Check if sowing has started (Root & Scion for grafted)
    $swCheck = checkStockOrderSowingStatus($krfconn, $stockorder_no, $projectid);
    if ($swCheck['sowing_started']) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Cannot delete order ' . $stockorder_no . ': ' . $swCheck['reason'] . '. Sown products cannot be deleted/removed.'
        ]);
        exit;
    }
    
    // 2. Check if there are allocated provisional orders consumed
    $stmt_alloc = $krfconn->prepare("SELECT COUNT(*) as alloc_cnt, COALESCE(SUM(consumed_qty), 0) as alloc_qty FROM krf_productionorder_consumed WHERE stockorderno = ? AND projectid = ?");
    $stmt_alloc->bind_param("si", $stockorder_no, $projectid);
    $stmt_alloc->execute();
    $alloc_res = $stmt_alloc->get_result()->fetch_assoc();
    $stmt_alloc->close();
    
    if (!empty($alloc_res['alloc_cnt']) && $alloc_res['alloc_cnt'] > 0 && $alloc_res['alloc_qty'] > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Cannot delete: This production order has provisional order allocations linked to it.']);
        exit;
    }
    
    // 3. Mark unsowed plant details as Removed (tedit not updated)
    $upd_sow = $krfconn->prepare("UPDATE krf_polyhouse_sowingplant_details SET status = 'Removed' WHERE stockorder_no = ? AND projectid = ?");
    $upd_sow->bind_param("si", $stockorder_no, $projectid);
    $upd_sow->execute();
    $upd_sow->close();
    
    // 4. Mark sowing schedule as Cancelled (tedit not updated)
    $upd_sch = $krfconn->prepare("UPDATE krf_polyhouse_sowingschedule SET status = 'Cancelled' WHERE stockorder_no = ? AND projectid = ?");
    $upd_sch->bind_param("si", $stockorder_no, $projectid);
    $upd_sch->execute();
    $upd_sch->close();

    // 5. Mark consumed records as cancelled (tedit not updated)
    $upd_con = $krfconn->prepare("UPDATE krf_productionorder_consumed SET confirmstatus = 'cancelled', status = 'Cancelled' WHERE stockorderno = ? AND projectid = ?");
    $upd_con->bind_param("si", $stockorder_no, $projectid);
    $upd_con->execute();
    $upd_con->close();
    
    // 6. Mark krf_production_stockorder as Cancelled (tedit not updated)
    $upd_pso = $krfconn->prepare("UPDATE krf_production_stockorder SET status = 'Cancelled' WHERE stockorder_no = ? AND projectid = ?");
    $upd_pso->bind_param("si", $stockorder_no, $projectid);
    $upd_pso->execute();
    $upd_pso->close();

    // 7. Mark sales items as Cancelled (tedit not updated)
    $upd_so_it = $krfconn->prepare("UPDATE krf_sales_order_items SET status = 'Cancelled' WHERE stockorder_no = ? AND projectid = ?");
    $upd_so_it->bind_param("si", $stockorder_no, $projectid);
    $upd_so_it->execute();
    $upd_so_it->close();

    // 8. Mark provisional items as Cancelled (tedit not updated)
    $upd_pso_it = $krfconn->prepare("UPDATE krf_provisional_sales_order_items SET status = 'Cancelled' WHERE stockorder_no = ? AND projectid = ?");
    $upd_pso_it->bind_param("si", $stockorder_no, $projectid);
    $upd_pso_it->execute();
    $upd_pso_it->close();
    
    echo json_encode(['status' => 'success', 'message' => 'Production stock order ' . $stockorder_no . ' cancelled successfully.']);
    exit;
} else if ($action == "GetProductionStockOrderData") {
    $projectid = $_POST['projectid'] ?? 0;
    $yearValue = $_POST['yearValue'] ?? '';
    $monthValue = $_POST['monthValue'] ?? '';
    $prddData = [];
    if (!empty($yearValue)) {
        if ($monthValue === "all" || empty($monthValue)) {
            // Entire year
            $datefrom = sprintf("%04d-01-01", $yearValue);
            $dateto = sprintf("%04d-12-31", $yearValue);
        } else if (!empty($monthValue)) {
            // First day of selected month
            $datefrom = sprintf("%04d-%02d-01", $yearValue, $monthValue);
            // Last day of selected month
            $dateto = date("Y-m-t", strtotime($datefrom));
        } else {
            echo json_encode(["error" => "Invalid month"]);
            exit;
        }
        // Fetch production stock orders
        $prdData = getFullProductionOrderDetailsWithSowingDetails($krfconn, $projectid, $datefrom, $dateto);
        // $prddData
        echo json_encode($prdData);
        exit;
    } else {
        echo json_encode(["error" => "Invalid year"]);
        exit;
    }
} else if ($action == "getsowingstockOrderdatawithdates") {
    $projectid = isset($_POST['projectid']) ? (int) $_POST['projectid'] : 0;
    $dateFrom = $_POST['datefrom'] ?? '';
    $dateTo = $_POST['dateto'] ?? '';
    $sowingdata = [];
    if ($projectid > 0 && !empty($dateFrom) && !empty($dateTo)) {
        $ddshortbyvalue = $dateFrom . "|" . $dateTo;
        $ppdstordata = getPendingSowingOfPSODataRootyTypewise($krfconn, $projectid, $dateFrom, $dateTo);
        // print_r($ppdstordata);
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
                //product details
                $prd = getProductData($krfconn, $projectid, "ProductID", $swdkey['productid']);
                // echo "--".$projectid.'--'.$swdkey['productid'];
                // print_r($prd);
                $productCategoryid = $prd[0]['CategoryId'] ?? '';
                $productRootSeedid = $prd[0]['SeedId'] ?? '';
                $productScionSeedid = $prd[0]['ScionSeedId'] ?? '';
                if($roottype!="" && strtolower($roottype)=="root"){
                    $rootseedinfo = SeedDetailsForProduct($krfconn, $projectid, $productRootSeedid);
                    if (count($rootseedinfo) > 0) {
                        $seedid = $productRootSeedid;
                        $seedname = $rootseedinfo['SeedName'];
                    }
                }else{
                    $scionseedinfo = SeedDetailsForProduct($krfconn, $projectid, $productScionSeedid);
                    if (count($scionseedinfo) > 0) {
                        $seedid = $productScionSeedid;
                        $seedname = $scionseedinfo['SeedName'];
                    }
                }



                // Tray count rows
                $sowingcount = getSowingRowCount($krfconn, $projectid, $stockOrderNo, $roottype);

                // Tray / lot details
                $lotdetails = getTrayGroupedData($krfconn, $projectid, $stockOrderNo, $roottype);

                // germinationbox details
                $germinatinboxdetails = getGerminationBoxQRCodeDetails($krfconn, $projectid, $roottype, "stockorderno", $stockOrderNo);

                // get sowing location
                $swlocaiton = getProductionLocationMaster($hrmconn, $swdkey['companyid'], $swdkey['subcompanyid'], $swdkey['projectid'], "LocationId", $swdkey['sowinglocationid']);
                $sowinglocationname = "";
                if (count($swlocaiton) > 0) {
                    $sowinglocationname = $swlocaiton[0]['LocationName'];
                }

                // Defaults
                $totalSowingQty = 0;
                $sowingdoneqty = 0;
                $tray_count = 0;
                $traycavity = 0;
                
                $failurebufferpercent = 0;

                if (is_array($lotdetails) && count($lotdetails) > 0) {
                    $traycavity = (int) ($lotdetails[0]['traycavity'] ?? 0);
                    // $seedid = $lotdetails[0]['seedid'] ?? null;
                    // $seedname = $lotdetails[0]['seedname'] ?? null;
                    $failurebufferpercent = (float) ($lotdetails[0]['failurebufferpercent'] ?? 0);
                    $totalSowingQty = (float) ($lotdetails[0]['totalsowingqty'] ?? 0);

                    foreach ($lotdetails as $ltdkey) {
                        $currentTrayCount = (int) ($ltdkey['tray_count'] ?? 0);
                        $tray_count = $currentTrayCount;
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

} else if ($action == "GetProductionStockOrderDataWithDate") {
    $projectid = isset($_POST['projectid']) ? (int) $_POST['projectid'] : 0;
    $dateFrom = $_POST['datefrom'] ?? '';
    $dateTo = $_POST['dateto'] ?? '';
    $sowingdata = [];
    if ($projectid > 0 && !empty($dateFrom) && !empty($dateTo)) {
        $ddshortbyvalue = $dateFrom . "|" . $dateTo;
        $ppdstordata = getPendingSowingOfPSODataRootyTypewise($krfconn, $projectid, $dateFrom, $dateTo);
        
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



                // Tray count rows
                $sowingcount = getSowingRowCount($krfconn, $projectid, $stockOrderNo, $roottype);

                // Tray / lot details
                $lotdetails = getTrayGroupedData($krfconn, $projectid, $stockOrderNo, $roottype);

                // germinationbox details
                $germinatinboxdetails = getGerminationBoxQRCodeDetails($krfconn, $projectid, $roottype, "stockorderno", $stockOrderNo);

                // get sowing location
                $swlocaiton = getProductionLocationMaster($hrmconn, $swdkey['companyid'], $swdkey['subcompanyid'], $swdkey['projectid'], "LocationId", $swdkey['sowinglocationid']);
                $sowinglocationname = "";
                if (count($swlocaiton) > 0) {
                    $sowinglocationname = $swlocaiton[0]['LocationName'];
                }

                // Defaults
                $totalSowingQty = 0;
                $sowingdoneqty = 0;
                $tray_count = 0;
                $traycavity = 0;
                
                $failurebufferpercent = 0;

                if (is_array($lotdetails) && count($lotdetails) > 0) {
                    $traycavity = (int) ($lotdetails[0]['traycavity'] ?? 0);
                    // $seedid = $lotdetails[0]['seedid'] ?? null;
                    // $seedname = $lotdetails[0]['seedname'] ?? null;
                    $failurebufferpercent = (float) ($lotdetails[0]['failurebufferpercent'] ?? 0);
                    $totalSowingQty = (float) ($lotdetails[0]['totalsowingqty'] ?? 0);

                    foreach ($lotdetails as $ltdkey) {
                        $currentTrayCount = (int) ($ltdkey['tray_count'] ?? 0);
                        $tray_count = $currentTrayCount;
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

} else if ($action == "GetOrderGerminatedBoxesDetails") {
    $projectid = !empty($_REQUEST['projectid']) ? (int)$_REQUEST['projectid'] : (!empty($_SESSION['projectid']) ? (int)$_SESSION['projectid'] : 0);
    $stockorderno = trim($_REQUEST['stockorderno'] ?? '');
    $roottype = trim($_REQUEST['roottype'] ?? '');
    $resData = getOrderGerminatedBoxesDetails($krfconn, $authconn, $projectid, $stockorderno, $roottype);
    if (ob_get_length()) {
        ob_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($resData, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
    exit;
} else if ($action == "GetPendingAllocationGBoxesDataWithDate") {
    $projectid = !empty($_REQUEST['projectid']) ? (int)$_REQUEST['projectid'] : (!empty($_SESSION['projectid']) ? (int)$_SESSION['projectid'] : 0);
    $dateFrom = $_REQUEST['datefrom'] ?? 'all';
    $dateTo = $_REQUEST['dateto'] ?? 'all';
    $pndallbxdta = [];
    if ($projectid > 0) {
        $pndallbxdta = getPendingAllocationBoxesGroupedByDateDesc($krfconn, $projectid, $dateFrom, $dateTo);
    }
    if (ob_get_length()) {
        ob_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($pndallbxdta, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
    exit;
} else if ($action == "GetPendingTrayAllocationofGBoxes") {
    $projectid = isset($_POST['projectid']) ? (int) $_POST['projectid'] : 0;
    $trpndallbxdta = [];
    if ($projectid > 0) {
        $trpndallbxdta = GetPendingTrayAllocationofGBoxes($krfconn, $projectid);
    }
    // Final JSON output
    echo json_encode($trpndallbxdta, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
    exit;
} else if ($action == "GetActveSowingdates") {
    $projectid = $_POST['projectid'] ?? 0;
    $unqswdattes = getUniqueSowingDates($krfconn, $projectid);
    echo json_encode($unqswdattes);
} else if ($action == "GetProductionStockOrderYearMonthlist") {
    $projectid = $_POST['projectid'] ?? 0;
    $prdYearMonth = getProductionStockOrderYearMonthList($krfconn, $projectid);
    echo json_encode($prdYearMonth);
} else if ($action == "GetGrowthstages") {
    $projectid = $_POST['projectid'] ?? 0;
    $grwothd = getProductGrowthStages($krfconn, $projectid, '', '');
    echo json_encode($grwothd);
} else if ($action == "GetPendingAllocationGerminationdates") {
    $projectid = $_POST['projectid'] ?? 0;
     $unqaldattes = GetPendingAllocationGerminationdates($krfconn, $projectid);
    echo json_encode($unqaldattes);
} else if ($action == "getproductionorderProductdata") {
    $companyid = $_POST['companyid'] ?? 0;
    $subcompanyid = $_POST['subcompanyid'] ?? 0;
    $projectid = $_POST['projectid'] ?? 0;
    $productid = $_POST['productid'] ?? 0;
    $productstockorderno = $_POST['productstockorderno'] ?? 0;
    $ppdd = getFullProductionStockOrdersDetails($krfconn, $projectid, 'stockorder_no', $productstockorderno);
    echo json_encode($ppdd);
} else if ($action == "saveUpdatedDeliveryDate") {
    $companyid = $_POST['companyid'] ?? 0;
    $subcompanyid = $_POST['subcompanyid'] ?? 0;
    $projectid = $_POST['projectid'] ?? 0;
    $productid = $_POST['productid'] ?? 0;
    $productstockorderno = $_POST['productstockorderno'] ?? 0;
    $todaysowing = date('Y-m-d');
    // deliverydate = $_POST['DeliveryDate'];
    // incomplete the code due to sowing date not confirm to change
} else if ($action == "UpdateSowingLocation") {
    $stockorder_no = $_POST['stockorder_no'] ?? '';
    $sowinglocationid = $_POST['sowinglocationid'] ?? '';
    $projectid = $_POST['projectid'] ?? 0;
    $updated_by = $_POST['loginid'] ?? '';
    $roottype = $_POST['roottype'] ?? '';
    $sowingschedulid = $_POST['sowingschedulid'] ?? '';

    if (!$stockorder_no || !$projectid || !$sowinglocationid) {
        echo json_encode(["status" => "error", "message" => "Missing required parameters."]);
        exit;
    }

    $result = updateProductionOrderSowingLocation($krfconn, $stockorder_no, $sowinglocationid, $projectid, $updated_by, $roottype, $sowingschedulid);
    echo json_encode($result);
} else if ($action == "GetProductionLocationList") {
    $projectid = $_POST['projectid'] ?? 0;
    $companyid = $_POST['companyid'] ?? 0;
    $subcompanyid = $_POST['subcompanyid'] ?? 0;
    // Using existing function from labour_functions.php
    $locations = getProductionLocationMaster($hrmconn, $companyid, $subcompanyid, $projectid, "all", "all");
    echo json_encode($locations);
} else if ($action == "GetAllocatedOrderDetails") {
    $stockorder_no = $_POST['stockorder_no'] ?? '';
    $projectid = $_POST['projectid'] ?? 0;

    $sql = "SELECT c.*, pso.created_at as order_date, cm.client_name 
            FROM krf_productionorder_consumed c
            LEFT JOIN krf_provisional_sales_order pso ON c.psono = pso.psono AND c.projectid = pso.projectid
            LEFT JOIN krf_client_master cm ON pso.clientid = cm.client_id AND pso.projectid = cm.ProjectId
            WHERE c.stockorderno = ? AND c.projectid = ?
            GROUP BY c.id";
    $stmt = $krfconn->prepare($sql);
    $stmt->bind_param("si", $stockorder_no, $projectid);
    $stmt->execute();
    $result = $stmt->get_result();
    $details = [];
    while ($row = $result->fetch_assoc()) {
        $details[] = $row;
    }
    echo json_encode($details);
    exit;
} else if ($action == "GetComingStockOrdersForSameRoot") {
    $projectid = $_POST['projectid'] ?? 0;
    $stockorder_no = $_POST['stockorder_no'] ?? '';
    $productid = $_POST['productid'] ?? '';

    // Get SeedId of current product
    $prd = getProductData($krfconn, $projectid, "ProductID", $productid);
    $seedid = $prd[0]['SeedId'] ?? '';

    $orders = [];
    if (!empty($seedid)) {
        // Find coming stock orders sharing the same Root Seed ID
        $sql = "SELECT DISTINCT pso.id, pso.stockorder_no, pso.productid, pso.product_name, pso.quantity, pso.expected_delivery_date
                FROM krf_production_stockorder pso
                JOIN product_master pm ON pso.productid = pm.ProductID
                WHERE pso.projectid = ?
                  AND pso.stockorder_no != ?
                  AND pm.SeedId = ?
                  AND (pso.status IS NULL OR LOWER(pso.status) != 'cancelled')
                ORDER BY pso.expected_delivery_date ASC";
        $stmt = $krfconn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("iss", $projectid, $stockorder_no, $seedid);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $orders[] = [
                    'id' => $row['id'],
                    'stockorder_no' => $row['stockorder_no'],
                    'productid' => $row['productid'],
                    'product_name' => $row['product_name'],
                    'quantity' => (float)$row['quantity'],
                    'expected_delivery_date' => $row['expected_delivery_date']
                ];
            }
            $stmt->close();
        }
    }
    echo json_encode(["status" => "success", "data" => $orders]);
    exit;
}
?>