<?php
error_reporting(E_ALL);
ini_set('display_errors',0);
require_once __DIR__.'/../../connection.php';
require_once __DIR__.'/../../loginCell/functions/login_functions.php';
require_once __DIR__.'/../functions/orders_functions.php';
require_once __DIR__.'/../functions/product_functions.php';
$action=$_GET['action']??'';
if ($action == "ProductListFrommaster") {
    $projectid = $_POST['projectid'] ?? 0;
    $pcd = getProductData($krfconn, $projectid, "all", "all");
    echo json_encode($pcd);
}

if ($action == "GetClientLastPurchaseHistory") {
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
}

if ($action == "getClientProductDiscount" || $action == "getClientDiscountDetails" || $action == "getClientDiscount") {
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
}


