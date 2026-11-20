<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once(__DIR__ . '/../../connection.php');
if (isset($krfconn) && $krfconn) {
    @$krfconn->set_charset("utf8mb4");
}
require_once(__DIR__ . '/../../loginCell/functions/login_functions.php');
@include_once(__DIR__ . "/../../whatsappnotification/whatsappsmsfunctions.php");
date_default_timezone_set("Asia/Calcutta");

$action = $_POST['action'] ?? $_GET['action'] ?? '';

header('Content-Type: application/json; charset=utf-8');

if (!function_exists('cleanVal')) {
    function cleanVal($val) {
        return $val !== null ? trim(strval($val)) : '';
    }
}

// -------------------------------------------------------------
// 1. AUTOCOMPLETE / SEARCH CLIENTS
// -------------------------------------------------------------
if ($action == "SearchClients") {
    $term = trim($_POST['searchTerm'] ?? $_POST['q'] ?? $_GET['q'] ?? '');
    $projectid = intval($_POST['projectid'] ?? 0);
    
    $clients = [];
    $seenKeys = [];

    if (strlen($term) < 1) {
        // Return recent clients from PSO and Client Master
        $sql = "
            SELECT DISTINCT 
                pso.clientid AS clientid,
                pso.clientname AS clientname,
                pso.mobileno AS mobileno,
                pso.districtname AS districtname,
                pso.statename AS statename
            FROM krf_provisional_sales_order pso
            WHERE (pso.projectid = ? OR ? = 0)
              AND (pso.mobileno IS NOT NULL AND pso.mobileno != '')
            ORDER BY pso.id DESC
            LIMIT 20
        ";
        if ($stmt = $krfconn->prepare($sql)) {
            $stmt->bind_param("ii", $projectid, $projectid);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $mob = trim($row['mobileno'] ?? '');
                $cid = trim($row['clientid'] ?? '');
                $key = $mob ?: $cid;
                if (empty($key) || isset($seenKeys[$key])) continue;
                $seenKeys[$key] = true;

                $clients[] = [
                    'id' => $mob ?: $cid,
                    'clientid' => $cid,
                    'clientname' => $row['clientname'] ?: 'Client',
                    'mobileno' => $mob,
                    'location' => implode(', ', array_filter([$row['districtname'] ?? '', $row['statename'] ?? ''])),
                    'text' => ($row['clientname'] ?: 'Client') . ' (' . ($mob ?: 'No Mobile') . ')' . ($row['districtname'] ? ' - ' . $row['districtname'] : '')
                ];
            }
        }
    } else {
        $searchLike = "%{$term}%";
        
        // Search in Client Master
        $cmSql = "
            SELECT 
                client_id AS clientid,
                client_name AS clientname,
                phones AS mobileno,
                districtname,
                statename
            FROM krf_client_master
            WHERE phones LIKE ? OR client_name LIKE ? OR client_id LIKE ?
            LIMIT 25
        ";
        if ($stmt = $krfconn->prepare($cmSql)) {
            $stmt->bind_param("sss", $searchLike, $searchLike, $searchLike);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $mob = trim($row['mobileno'] ?? '');
                $cid = trim($row['clientid'] ?? '');
                $key = $mob ?: $cid;
                if (empty($key) || isset($seenKeys[$key])) continue;
                $seenKeys[$key] = true;

                $clients[] = [
                    'id' => $mob ?: $cid,
                    'clientid' => $cid,
                    'clientname' => $row['clientname'] ?: 'Client',
                    'mobileno' => $mob,
                    'location' => implode(', ', array_filter([$row['districtname'] ?? '', $row['statename'] ?? ''])),
                    'text' => ($row['clientname'] ?: 'Client') . ' (' . ($mob ?: 'No Mobile') . ')' . ($row['districtname'] ? ' - ' . $row['districtname'] : '')
                ];
            }
        }

        // Search in Provisional Sales Orders
        $psoSql = "
            SELECT DISTINCT
                clientid,
                clientname,
                mobileno,
                districtname,
                statename
            FROM krf_provisional_sales_order
            WHERE mobileno LIKE ? OR clientname LIKE ? OR clientid LIKE ? OR psono LIKE ?
            LIMIT 25
        ";
        if ($stmt = $krfconn->prepare($psoSql)) {
            $stmt->bind_param("ssss", $searchLike, $searchLike, $searchLike, $searchLike);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $mob = trim($row['mobileno'] ?? '');
                $cid = trim($row['clientid'] ?? '');
                $key = $mob ?: $cid;
                if (empty($key) || isset($seenKeys[$key])) continue;
                $seenKeys[$key] = true;

                $clients[] = [
                    'id' => $mob ?: $cid,
                    'clientid' => $cid,
                    'clientname' => $row['clientname'] ?: 'Client',
                    'mobileno' => $mob,
                    'location' => implode(', ', array_filter([$row['districtname'] ?? '', $row['statename'] ?? ''])),
                    'text' => ($row['clientname'] ?: 'Client') . ' (' . ($mob ?: 'No Mobile') . ')' . ($row['districtname'] ? ' - ' . $row['districtname'] : '')
                ];
            }
        }

        // Search in Advance Payments
        $aptSql = "
            SELECT DISTINCT
                clientid,
                clientname,
                invoiceno
            FROM advance_payment_transactions
            WHERE clientname LIKE ? OR clientid LIKE ? OR invoiceno LIKE ?
            LIMIT 25
        ";
        if ($stmt = $krfconn->prepare($aptSql)) {
            $stmt->bind_param("sss", $searchLike, $searchLike, $searchLike);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $cid = trim($row['clientid'] ?? '');
                $cname = trim($row['clientname'] ?? '');
                $key = $cid ?: $cname;
                if (empty($key) || isset($seenKeys[$key])) continue;
                $seenKeys[$key] = true;

                $clients[] = [
                    'id' => $cid ?: $cname,
                    'clientid' => $cid,
                    'clientname' => $cname ?: 'Client',
                    'mobileno' => '',
                    'location' => '',
                    'text' => ($cname ?: 'Client') . ' (ID: ' . ($cid ?: '-') . ')'
                ];
            }
        }
    }
    
    echo json_encode(['status' => 'success', 'results' => $clients]);
    exit;
}

// -------------------------------------------------------------
// 1b. GET AVAILABLE OPEN STOCK PRODUCTION BATCHES & PRODUCTS
// -------------------------------------------------------------
if ($action == "GetOpenStockBatches") {
    $projectid = intval($_POST['projectid'] ?? 0);
    
    // Fetch available open stock production orders with actual consumed & available balance
    $sql = "
        SELECT 
            p.id,
            p.stockorder_no,
            p.productid AS product_id,
            p.product_name,
            p.quantity AS total_qty,
            IFNULL(c.total_consumed, 0) AS consumed_qty,
            (p.quantity - IFNULL(c.total_consumed, 0)) AS available_qty,
            p.expected_delivery_date AS delivery_date,
            DATE_FORMAT(p.expected_delivery_date, '%d-%b-%Y') AS formatted_delivery_date,
            p.confirmdeliverydate,
            p.status,
            IFNULL(p.sowingstatus, 'Confirmed') AS sowingstatus,
            IFNULL(pm.PriceOfProduct, 0) AS default_rate,
            IFNULL(pm.sku, '') AS product_sku
        FROM krf_production_stockorder p
        LEFT JOIN (
            SELECT stockorderno, product_id, IFNULL(SUM(consumed_qty), 0) AS total_consumed
            FROM krf_productionorder_consumed
            WHERE status != 'Cancelled'
            GROUP BY stockorderno, product_id
        ) c ON c.stockorderno = p.stockorder_no AND c.product_id = p.productid
        LEFT JOIN product_master pm ON pm.ProductID = p.productid
        WHERE (p.psono IS NULL OR p.psono = '' OR p.clientid = 'C00001' OR p.clientid = '' OR p.clientid IS NULL)
          AND p.status IN ('Confirmed', 'confirmed', 'Active', 'active')
          AND p.quantity > 0
        HAVING available_qty > 0
        ORDER BY p.product_name ASC, p.expected_delivery_date ASC
    ";
    $res = $krfconn->query($sql);
    $batches = [];
    $productMap = [];

    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $pid = (string)$r['product_id'];
            $b = [
                'id' => (int)$r['id'],
                'stockorder_no' => $r['stockorder_no'],
                'product_id' => $pid,
                'product_name' => $r['product_name'],
                'total_qty' => (int)$r['total_qty'],
                'consumed_qty' => (float)$r['consumed_qty'],
                'available_qty' => (float)$r['available_qty'],
                'delivery_date' => $r['delivery_date'] ?: '',
                'formatted_delivery_date' => $r['formatted_delivery_date'] ?: ($r['delivery_date'] ? date('d-M-Y', strtotime($r['delivery_date'])) : '-'),
                'confirmdeliverydate' => $r['confirmdeliverydate'] ?: '',
                'status' => $r['status'],
                'sowingstatus' => $r['sowingstatus'] ?: 'Confirmed',
                'default_rate' => (float)$r['default_rate'],
                'product_sku' => $r['product_sku']
            ];
            $batches[] = $b;

            if (!isset($productMap[$pid])) {
                $productMap[$pid] = [
                    'product_id' => $pid,
                    'product_name' => $r['product_name'],
                    'default_rate' => (float)$r['default_rate'],
                    'product_sku' => $r['product_sku'],
                    'batch_count' => 0,
                    'total_open_qty' => 0,
                    'total_consumed_qty' => 0,
                    'total_available_qty' => 0,
                    'earliest_delivery_date' => $r['delivery_date'] ?: '',
                    'formatted_earliest_delivery_date' => $b['formatted_delivery_date']
                ];
            }
            $productMap[$pid]['batch_count']++;
            $productMap[$pid]['total_open_qty'] += $b['total_qty'];
            $productMap[$pid]['total_consumed_qty'] += $b['consumed_qty'];
            $productMap[$pid]['total_available_qty'] += $b['available_qty'];
        }
    }

    // Also fetch list of all active products for fallback / custom selection
    $pSql = "SELECT ProductID, ProductName, PriceOfProduct, sku FROM product_master WHERE Status = 'Active' OR Status = '1' ORDER BY ProductName ASC";
    $pRes = $krfconn->query($pSql);
    $allProducts = [];
    if ($pRes) {
        while ($pr = $pRes->fetch_assoc()) {
            $pid = (string)$pr['ProductID'];
            if (isset($productMap[$pid])) {
                $allProducts[] = $productMap[$pid];
            } else {
                $allProducts[] = [
                    'product_id' => $pid,
                    'product_name' => $pr['ProductName'],
                    'default_rate' => (float)($pr['PriceOfProduct'] ?? 0),
                    'product_sku' => $pr['sku'] ?? '',
                    'batch_count' => 0,
                    'total_open_qty' => 0,
                    'total_consumed_qty' => 0,
                    'total_available_qty' => 0,
                    'earliest_delivery_date' => '',
                    'formatted_earliest_delivery_date' => '-'
                ];
            }
        }
    }

    echo json_encode([
        'status' => 'success',
        'batches' => $batches,
        'products' => $allProducts
    ]);
    exit;
}

// -------------------------------------------------------------
// 1c. EDIT / UPDATE PROVISIONAL ORDER ITEM QUANTITY & REPLACEMENT
// -------------------------------------------------------------
if ($action == "UpdateItemOrderQty") {
    try {
        $itemId = intval($_POST['item_id'] ?? 0);
        $psono = trim($_POST['psono'] ?? '');
        $productId = trim($_POST['product_id'] ?? '');
        $newQuantity = intval($_POST['new_quantity'] ?? 0);
        $actionMode = trim($_POST['action_mode'] ?? 'only_edit'); // 'only_edit' | 'add_replacement_product'
        $remark = trim($_POST['remark'] ?? '');
        $loginname = trim($_POST['loginname'] ?? $_SESSION['username'] ?? $_SESSION['loginname'] ?? 'Admin');

        // Replacement product inputs
        $newProductId = trim($_POST['new_product_id'] ?? '');
        $newProductName = trim($_POST['new_product_name'] ?? '');
        $newStockOrderNo = trim($_POST['new_stockorder_no'] ?? '');
        $newProductRate = floatval($_POST['new_product_rate'] ?? 0);
        $newProductQty = intval($_POST['new_product_qty'] ?? 0);
        $newDeliveryDate = trim($_POST['new_delivery_date'] ?? '');

        // 1. Remark is strictly required for any edit against order & product
        if (empty($remark)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Amended reason / remark is required for any edit against order and product.'
            ]);
            exit;
        }

        if ($itemId <= 0 || $newQuantity <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid item ID or quantity. Quantity must be greater than 0.']);
            exit;
        }

        // 2. Fetch current item details from krf_provisional_sales_order_items
        $stmt = $krfconn->prepare("
            SELECT id, projectid, clientid, psono, stockorder_no, product_sku, product_id, product_name, rate, quantity, taxableamount, amount, expecteddeliverydate 
            FROM krf_provisional_sales_order_items 
            WHERE id = ? LIMIT 1
        ");
        if (!$stmt) {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $krfconn->error]);
            exit;
        }
        $stmt->bind_param("i", $itemId);
        $stmt->execute();
        $itemRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$itemRow) {
            echo json_encode(['status' => 'error', 'message' => 'Provisional order item not found.']);
            exit;
        }

        $psono = $itemRow['psono'];
        $productId = $itemRow['product_id'];
        $rate = floatval($itemRow['rate'] ?? 0);
        $oldQuantity = intval($itemRow['quantity'] ?? 0);
        $projectid = intval($itemRow['projectid'] ?? 0);
        $clientid = trim($itemRow['clientid'] ?? '');
        $productSku = trim($itemRow['product_sku'] ?? '');
        $expectedDeliveryDate = $itemRow['expecteddeliverydate'];

        if ($newQuantity == $oldQuantity) {
            echo json_encode(['status' => 'error', 'message' => "The new quantity is identical to current quantity ({$oldQuantity})."]);
            exit;
        }

        // Find sono if available
        $sono = '';
        $soQ = $krfconn->query("SELECT sono FROM krf_sales_order WHERE psono = '" . $krfconn->real_escape_string($psono) . "' LIMIT 1");
        if ($soQ && $soR = $soQ->fetch_assoc()) {
            $sono = $soR['sono'];
        }
        if (empty($sono)) {
            $soiQ = $krfconn->query("SELECT sono FROM krf_sales_order_items WHERE psono = '" . $krfconn->real_escape_string($psono) . "' LIMIT 1");
            if ($soiQ && $soiR = $soiQ->fetch_assoc()) {
                $sono = $soiR['sono'];
            }
        }

        // 3. Handle Quantity Reduction (newQuantity < oldQuantity)
        if ($newQuantity < $oldQuantity) {
            $restQty = $oldQuantity - $newQuantity;

            // Scenario A: "Only edit the quantity" (no replacement product selected)
            if ($actionMode === 'only_edit') {
                // Check if a dedicated production order was generated for this client against this psono and productid
                $checkDedicated = $krfconn->prepare("
                    SELECT id, stockorder_no, clientid, quantity, status, sowingstatus, workingstatus 
                    FROM krf_production_stockorder 
                    WHERE psono = ? AND productid = ? AND clientid != '' AND clientid != 'C00001'
                    LIMIT 1
                ");
                $checkDedicated->bind_param("ss", $psono, $productId);
                $checkDedicated->execute();
                $stkDedicated = $checkDedicated->get_result()->fetch_assoc();
                $checkDedicated->close();

                // If dedicated production order was generated for client:
                // Show warning, inform and request to dispatch all order qty!
                if ($stkDedicated) {
                    echo json_encode([
                        'status' => 'warning_dedicated_production',
                        'stockorder_no' => $stkDedicated['stockorder_no'],
                        'quantity' => intval($stkDedicated['quantity']),
                        'current_quantity' => $oldQuantity,
                        'new_quantity' => $newQuantity,
                        'rest_quantity' => $restQty,
                        'message' => "A dedicated Production Order (" . $stkDedicated['stockorder_no'] . ") has already been generated specifically for this client against order " . $psono . " (" . number_format($stkDedicated['quantity']) . " plants). You cannot simply reduce the quantity. Please dispatch all order quantity (" . number_format($oldQuantity) . " plants), or choose to add another replacement product for the remaining " . number_format($restQty) . " plants."
                    ]);
                    exit;
                }

                // If NOT dedicated (allocated from openstock production order):
                // Allowed to update the quantity directly
            }

            // Scenario B: "Add another product for remaining quantity"
            if ($actionMode === 'add_replacement_product' || $actionMode === 'add_product') {
                if (empty($newProductId) || empty($newProductName)) {
                    echo json_encode(['status' => 'error', 'message' => 'Please select a replacement product for the remaining quantity.']);
                    exit;
                }
                if ($newProductQty <= 0) {
                    $newProductQty = $restQty;
                }
                if (empty($newStockOrderNo)) {
                    echo json_encode(['status' => 'error', 'message' => 'Please select a production stock order to allocate the replacement product from.']);
                    exit;
                }
                if ($newProductRate <= 0) {
                    // Try fetch rate from product_master
                    $pmQ = $krfconn->query("SELECT PriceOfProduct FROM product_master WHERE ProductID = '" . $krfconn->real_escape_string($newProductId) . "' LIMIT 1");
                    if ($pmQ && $pmR = $pmQ->fetch_assoc()) {
                        $newProductRate = floatval($pmR['PriceOfProduct'] ?? 0);
                    }
                }
            }
        }

        // 4. Update Product A (the original item being edited)
        $newTaxableA = round($newQuantity * $rate, 2);
        $newAmountA = round($newQuantity * $rate, 2);
        $updateRemarkA = ($actionMode === 'add_replacement_product' || $actionMode === 'add_product')
            ? "Qty amended from {$oldQuantity} to {$newQuantity} (Rest {$restQty} allocated to {$newProductName}): " . $remark
            : "Qty amended from {$oldQuantity} to {$newQuantity}: " . $remark;

        // 4a. Update krf_provisional_sales_order_items for Product A
        $upPsoi = $krfconn->prepare("
            UPDATE krf_provisional_sales_order_items 
            SET quantity = ?, 
                taxableamount = ?, 
                amount = ?, 
                updatedat = CURDATE(), 
                updatedby = ?, 
                remark = IF(remark IS NULL OR remark = '', ?, CONCAT(remark, ' | ', ?))
            WHERE id = ?
        ");
        if ($upPsoi) {
            $upPsoi->bind_param("iddsssi", $newQuantity, $newTaxableA, $newAmountA, $loginname, $updateRemarkA, $updateRemarkA, $itemId);
            $upPsoi->execute();
            $upPsoi->close();
        }

        // 4b. Update krf_sales_order_items for Product A
        $upSoi = $krfconn->prepare("
            UPDATE krf_sales_order_items 
            SET quantity = ?, 
                taxableamount = ?, 
                amount = ?, 
                balancereservedqty = ?, 
                updateamended = NOW(), 
                ammendedby = ?, 
                remark = IF(remark IS NULL OR remark = '', ?, CONCAT(remark, ' | ', ?))
            WHERE psono = ? AND product_id = ?
        ");
        if ($upSoi) {
            $upSoi->bind_param("iddiissss", $newQuantity, $newTaxableA, $newAmountA, $newQuantity, $loginname, $updateRemarkA, $updateRemarkA, $psono, $productId);
            $upSoi->execute();
            $upSoi->close();
        }

        // 4c. Update krf_productionorder_consumed for Product A
        $upCons = $krfconn->prepare("
            UPDATE krf_productionorder_consumed 
            SET original_orderqty = ?, 
                consumed_qty = ?,
                updated_at = NOW(),
                remark = IF(remark IS NULL OR remark = '', ?, CONCAT(remark, ' | ', ?))
            WHERE psono = ? AND product_id = ?
        ");
        if ($upCons) {
            $upCons->bind_param("iissss", $newQuantity, $newQuantity, $updateRemarkA, $updateRemarkA, $psono, $productId);
            $upCons->execute();
            $upCons->close();
        }

        // 4d. Update krf_production_stockorder if a dedicated batch exists for Product A
        $upStk = $krfconn->prepare("
            UPDATE krf_production_stockorder 
            SET quantity = ?, 
                editedqty = ?, 
                editedremark = ?, 
                editedby = ?, 
                editedon = CURDATE(), 
                updated_at = NOW() 
            WHERE psono = ? AND productid = ?
        ");
        if ($upStk) {
            $upStk->bind_param("iissss", $newQuantity, $newQuantity, $updateRemarkA, $loginname, $psono, $productId);
            $upStk->execute();
            $upStk->close();
        }

        // 5. If "Add Replacement Product" is selected: Insert/Update Product B
        if (($actionMode === 'add_replacement_product' || $actionMode === 'add_product') && !empty($newProductId) && $newProductQty > 0) {
            $newTaxableB = round($newProductQty * $newProductRate, 2);
            $newAmountB = round($newProductQty * $newProductRate, 2);
            $delivDateB = !empty($newDeliveryDate) ? $newDeliveryDate : ($expectedDeliveryDate ?: date('Y-m-d'));
            $updateRemarkB = "Added {$newProductQty} plants allocated from {$newStockOrderNo} (Replaced from {$itemRow['product_name']}): " . $remark;

            // Fetch SKU for new product
            $newSku = '';
            $skuQ = $krfconn->query("SELECT sku FROM product_master WHERE ProductID = '" . $krfconn->real_escape_string($newProductId) . "' LIMIT 1");
            if ($skuQ && $skuR = $skuQ->fetch_assoc()) {
                $newSku = $skuR['sku'] ?? '';
            }

            // 5a. Update or Insert in krf_sales_order_items
            $chkSoi = $krfconn->prepare("SELECT id FROM krf_sales_order_items WHERE psono = ? AND product_id = ? LIMIT 1");
            $chkSoi->bind_param("ss", $psono, $newProductId);
            $chkSoi->execute();
            $existSoi = $chkSoi->get_result()->fetch_assoc();
            $chkSoi->close();

            if ($existSoi) {
                $upSoiB = $krfconn->prepare("
                    UPDATE krf_sales_order_items 
                    SET quantity = quantity + ?, 
                        balancereservedqty = balancereservedqty + ?, 
                        taxableamount = (quantity + ?) * rate, 
                        amount = (quantity + ?) * rate, 
                        updateamended = NOW(), 
                        ammendedby = ?, 
                        remark = IF(remark IS NULL OR remark = '', ?, CONCAT(remark, ' | ', ?))
                    WHERE id = ?
                ");
                if ($upSoiB) {
                    $upSoiB->bind_param("iiiisssi", $newProductQty, $newProductQty, $newProductQty, $newProductQty, $loginname, $updateRemarkB, $updateRemarkB, $existSoi['id']);
                    $upSoiB->execute();
                    $upSoiB->close();
                }
            } else {
                $inSoiB = $krfconn->prepare("
                    INSERT INTO krf_sales_order_items (
                        sono, psono, stockorder_no, projectid, clientid, product_sku,
                        product_id, product_name, quantity, rate, taxableamount, amount,
                        balancereservedqty, deliverydate, created_by, status, remark
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, 'Pending', ?
                    )
                ");
                if ($inSoiB) {
                    $inSoiB->bind_param(
                        "ssssssssiddiisss",
                        $sono, $psono, $newStockOrderNo, $projectid, $clientid, $newSku,
                        $newProductId, $newProductName, $newProductQty, $newProductRate, $newTaxableB, $newAmountB,
                        $newProductQty, $delivDateB, $loginname, $updateRemarkB
                    );
                    $inSoiB->execute();
                    $inSoiB->close();
                }
            }

            // 5b. Update or Insert in krf_productionorder_consumed
            $chkCons = $krfconn->prepare("SELECT id FROM krf_productionorder_consumed WHERE psono = ? AND product_id = ? AND stockorderno = ? LIMIT 1");
            $chkCons->bind_param("sss", $psono, $newProductId, $newStockOrderNo);
            $chkCons->execute();
            $existCons = $chkCons->get_result()->fetch_assoc();
            $chkCons->close();

            if ($existCons) {
                $upConsB = $krfconn->prepare("
                    UPDATE krf_productionorder_consumed 
                    SET consumed_qty = consumed_qty + ?, 
                        original_orderqty = original_orderqty + ?, 
                        updated_at = NOW(), 
                        remark = IF(remark IS NULL OR remark = '', ?, CONCAT(remark, ' | ', ?))
                    WHERE id = ?
                ");
                if ($upConsB) {
                    $upConsB->bind_param("iissi", $newProductQty, $newProductQty, $updateRemarkB, $updateRemarkB, $existCons['id']);
                    $upConsB->execute();
                    $upConsB->close();
                }
            } else {
                $inConsB = $krfconn->prepare("
                    INSERT INTO krf_productionorder_consumed (
                        psono, sono, stockorderno, product_id, product_name,
                        consumed_qty, wastage_qty, projectid, confirmstatus,
                        original_orderqty, status, created_by, created_at, updated_at, remark
                    ) VALUES (
                        ?, ?, ?, ?, ?,
                        ?, 0, ?, 'Confirmed',
                        ?, 'pending', ?, NOW(), NOW(), ?
                    )
                ");
                if ($inConsB) {
                    $inConsB->bind_param(
                        "sssssiiiss",
                        $psono, $sono, $newStockOrderNo, $newProductId, $newProductName,
                        $newProductQty, $projectid, $newProductQty, $loginname, $updateRemarkB
                    );
                    $inConsB->execute();
                    $inConsB->close();
                }
            }

            // 5c. Update or Insert in krf_provisional_sales_order_items
            $chkPsoi = $krfconn->prepare("SELECT id FROM krf_provisional_sales_order_items WHERE psono = ? AND product_id = ? LIMIT 1");
            $chkPsoi->bind_param("ss", $psono, $newProductId);
            $chkPsoi->execute();
            $existPsoi = $chkPsoi->get_result()->fetch_assoc();
            $chkPsoi->close();

            if ($existPsoi) {
                $upPsoiB = $krfconn->prepare("
                    UPDATE krf_provisional_sales_order_items 
                    SET quantity = quantity + ?, 
                        taxableamount = (quantity + ?) * rate, 
                        amount = (quantity + ?) * rate, 
                        updatedat = CURDATE(), 
                        updatedby = ?, 
                        remark = IF(remark IS NULL OR remark = '', ?, CONCAT(remark, ' | ', ?))
                    WHERE id = ?
                ");
                if ($upPsoiB) {
                    $upPsoiB->bind_param("iiisssi", $newProductQty, $newProductQty, $newProductQty, $loginname, $updateRemarkB, $updateRemarkB, $existPsoi['id']);
                    $upPsoiB->execute();
                    $upPsoiB->close();
                }
            } else {
                $inPsoiB = $krfconn->prepare("
                    INSERT INTO krf_provisional_sales_order_items (
                        projectid, clientid, psono, stockorder_no, product_sku,
                        product_id, product_name, quantity, rate, taxableamount, amount,
                        expecteddeliverydate, created_by, status, remark, updatedat, updatedby
                    ) VALUES (
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, 'Confirmed', ?, CURDATE(), ?
                    )
                ");
                if ($inPsoiB) {
                    $inPsoiB->bind_param(
                        "issssssiddsssss",
                        $projectid, $clientid, $psono, $newStockOrderNo, $newSku,
                        $newProductId, $newProductName, $newProductQty, $newProductRate, $newTaxableB, $newAmountB,
                        $delivDateB, $loginname, $updateRemarkB, $loginname
                    );
                    $inPsoiB->execute();
                    $inPsoiB->close();
                }
            }
        }

        // 6. Recalculate total_amount & grand_total in krf_sales_order
        $upSo = $krfconn->prepare("
            UPDATE krf_sales_order 
            SET total_amount = (SELECT IFNULL(SUM(amount), 0) FROM krf_sales_order_items WHERE psono = ?),
                grand_total = (SELECT IFNULL(SUM(amount), 0) FROM krf_sales_order_items WHERE psono = ?),
                updated_at = NOW(),
                updated_by = ?
            WHERE psono = ?
        ");
        if ($upSo) {
            $upSo->bind_param("ssss", $psono, $psono, $loginname, $psono);
            $upSo->execute();
            $upSo->close();
        }

        $successMsg = ($actionMode === 'add_replacement_product' || $actionMode === 'add_product')
            ? "Order quantity for '{$itemRow['product_name']}' updated to {$newQuantity}, and {$newProductQty} plants of '{$newProductName}' allocated from {$newStockOrderNo}."
            : "Order quantity for '{$itemRow['product_name']}' updated from {$oldQuantity} to {$newQuantity} (New Total: ₹" . number_format($newAmountA, 2) . ").";

        echo json_encode([
            'status' => 'success',
            'message' => $successMsg,
            'item' => [
                'id' => $itemId,
                'psono' => $psono,
                'product_id' => $productId,
                'old_quantity' => $oldQuantity,
                'new_quantity' => $newQuantity,
                'rate' => $rate,
                'new_amount' => $newAmountA
            ]
        ]);
        exit;
    } catch (Throwable $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Server Error while updating quantity: ' . $e->getMessage()
        ]);
        exit;
    }
}

// -------------------------------------------------------------
// 2. GET CLIENT FULL 360° PAYMENT & ORDER LIFECYCLE HISTORY
// -------------------------------------------------------------
if ($action == "GetClientFullHistory") {
    try {
        $searchKey = trim($_POST['searchKey'] ?? $_POST['clientNumber'] ?? $_GET['searchKey'] ?? '');
        $projectid = intval($_POST['projectid'] ?? 0);
        
        if (empty($searchKey)) {
            echo json_encode(['status' => 'error', 'message' => 'Please enter a client mobile number, name, or order ID to search.']);
            exit;
        }
    
    // Step 1: Identify Client Profile
    $clientProfile = null;
    $searchLike = "%{$searchKey}%";
    
    // Check in krf_client_master first
    $clientSql = "
        SELECT 
            client_id, client_name, phones, client_email, 
            statename, districtname, invoicing_address, vat, date_time
        FROM krf_client_master
        WHERE phones = ? OR client_id = ? OR phones LIKE ? OR client_name LIKE ?
        LIMIT 1
    ";
    if ($cStmt = $krfconn->prepare($clientSql)) {
        $cStmt->bind_param("ssss", $searchKey, $searchKey, $searchLike, $searchLike);
        $cStmt->execute();
        $cRes = $cStmt->get_result();
        if ($cRow = $cRes->fetch_assoc()) {
            $clientProfile = [
                'clientid' => $cRow['client_id'],
                'clientname' => $cRow['client_name'],
                'mobileno' => $cRow['phones'],
                'alternateno' => '',
                'email' => $cRow['client_email'],
                'statename' => $cRow['statename'],
                'districtname' => $cRow['districtname'],
                'address' => $cRow['invoicing_address'],
                'gstno' => $cRow['vat'],
                'created_at' => $cRow['date_time']
            ];
        }
    }
    
    // If not found in client master, check in krf_provisional_sales_order
    if (!$clientProfile) {
        $psoSearchSql = "
            SELECT 
                clientid, clientname, mobileno, statename, districtname, delivery_address, address, emailid, created_at
            FROM krf_provisional_sales_order
            WHERE mobileno = ? OR clientid = ? OR psono = ? OR mobileno LIKE ? OR clientname LIKE ?
            ORDER BY id DESC LIMIT 1
        ";
        if ($pStmt = $krfconn->prepare($psoSearchSql)) {
            $pStmt->bind_param("sssss", $searchKey, $searchKey, $searchKey, $searchLike, $searchLike);
            $pStmt->execute();
            $pRes = $pStmt->get_result();
            if ($pRow = $pRes->fetch_assoc()) {
                $clientProfile = [
                    'clientid' => $pRow['clientid'],
                    'clientname' => $pRow['clientname'],
                    'mobileno' => $pRow['mobileno'],
                    'alternateno' => '',
                    'email' => $pRow['emailid'],
                    'statename' => $pRow['statename'],
                    'districtname' => $pRow['districtname'],
                    'address' => $pRow['delivery_address'] ?: $pRow['address'],
                    'gstno' => '',
                    'created_at' => $pRow['created_at']
                ];
            }
        }
    }
    
    // If still not found, check in advance_payment_transactions
    if (!$clientProfile) {
        $aptSearchSql = "
            SELECT clientid, clientname, paymentdate
            FROM advance_payment_transactions
            WHERE invoiceno = ? OR clientid = ? OR clientname LIKE ?
            ORDER BY sno DESC LIMIT 1
        ";
        if ($aStmt = $krfconn->prepare($aptSearchSql)) {
            $aStmt->bind_param("sss", $searchKey, $searchKey, $searchLike);
            $aStmt->execute();
            $aRes = $aStmt->get_result();
            if ($aRow = $aRes->fetch_assoc()) {
                $clientProfile = [
                    'clientid' => $aRow['clientid'],
                    'clientname' => $aRow['clientname'],
                    'mobileno' => '',
                    'alternateno' => '',
                    'email' => '',
                    'statename' => '',
                    'districtname' => '',
                    'address' => '',
                    'gstno' => '',
                    'created_at' => $aRow['paymentdate']
                ];
            }
        }
    }
    
    if (!$clientProfile) {
        echo json_encode([
            'status' => 'not_found',
            'message' => "No client or order records found matching '{$searchKey}'."
        ]);
        exit;
    }
    
    $clientId = cleanVal($clientProfile['clientid'] ?? '');
    $clientMobile = cleanVal($clientProfile['mobileno'] ?? '');
    $clientName = cleanVal($clientProfile['clientname'] ?? '');
    
    // -------------------------------------------------------------
    // STEP 2: Fetch All Provisional Sales Orders (PSO) for this Client
    // -------------------------------------------------------------
    $psoList = [];
    $allPsonoNumbers = [];
    
    $psoQuery = "
        SELECT 
            pso.id,
            pso.psono,
            pso.clientid,
            pso.clientname,
            pso.mobileno,
            pso.address,
            pso.delivery_address,
            pso.statename,
            pso.districtname,
            pso.pickupfrom,
            pso.created_at,
            pso.created_by,
            pso.expecteddelivery,
            pso.status AS pso_status,
            pso.bookingno,
            pso.bookingfilepath,
            pso.remarks AS pso_remarks,
            DATE_FORMAT(pso.created_at, '%d-%b-%Y') AS formatted_booking_date,
            DATE_FORMAT(pso.expecteddelivery, '%d-%b-%Y') AS formatted_delivery_date
        FROM krf_provisional_sales_order pso
        WHERE (
            (pso.clientid = ? AND ? != '') 
            OR (pso.mobileno = ? AND ? != '') 
            OR (pso.clientname = ? AND ? != '')
            OR (pso.psono = ?)
        )
        ORDER BY pso.id DESC
    ";
    if ($pStmt = $krfconn->prepare($psoQuery)) {
        $pStmt->bind_param("sssssss", $clientId, $clientId, $clientMobile, $clientMobile, $clientName, $clientName, $searchKey);
        $pStmt->execute();
        $psoRes = $pStmt->get_result();
        
        while ($pRow = $psoRes->fetch_assoc()) {
            $psono = trim($pRow['psono'] ?? '');
            if ($psono) {
                $psoList[$psono] = $pRow;
                $allPsonoNumbers[] = $psono;
            }
        }
    }
    
    // Fetch confirmed sales orders from krf_sales_order
    $confirmedSalesOrders = [];
    $soQuery = "
        SELECT 
            so.id,
            so.sono,
            so.psono,
            so.clientid,
            so.clientname,
            so.mobileno,
            so.delivery_address,
            so.statename,
            so.districtname,
            so.created_at,
            so.total_amount,
            so.grand_total,
            so.status AS so_status,
            DATE_FORMAT(so.created_at, '%d-%b-%Y') AS formatted_so_date
        FROM krf_sales_order so
        WHERE (
            (so.clientid = ? AND ? != '') 
            OR (so.mobileno = ? AND ? != '') 
            OR (so.clientname = ? AND ? != '')
            OR (so.sono = ?)
            OR (so.psono = ?)
        )
        ORDER BY so.id DESC
    ";
    if ($sStmt = $krfconn->prepare($soQuery)) {
        $sStmt->bind_param("ssssssss", $clientId, $clientId, $clientMobile, $clientMobile, $clientName, $clientName, $searchKey, $searchKey);
        $sStmt->execute();
        $soRes = $sStmt->get_result();
        
        while ($soRow = $soRes->fetch_assoc()) {
            $psonoRef = trim($soRow['psono'] ?? '');
            $sono = trim($soRow['sono'] ?? '');
            if ($psonoRef && !in_array($psonoRef, $allPsonoNumbers)) {
                $allPsonoNumbers[] = $psonoRef;
            }
            $confirmedSalesOrders[$psonoRef ?: $sono] = $soRow;
        }
    }
    
    // -------------------------------------------------------------
    // STEP 3: Fetch Order Items for All PSONOs & Their Dispatches
    // -------------------------------------------------------------
    $psoItemsMap = [];
    $dispatchesByPsonoProduct = [];
    $totalDispatchedByPsono = [];

    if (!empty($allPsonoNumbers)) {
        $inClause = implode("','", array_map([$krfconn, 'real_escape_string'], $allPsonoNumbers));

        // 3a. Fetch dispatches from sales_invoice_items & sales_invoice
        $dispatchSql = "
            SELECT 
                sii.id,
                sii.invoice_id,
                sii.projectid,
                sii.clientid,
                sii.psono,
                sii.sono,
                sii.invoiceno,
                sii.stockorder_no,
                sii.productid,
                sii.product_sku,
                sii.productname,
                sii.quantity,
                sii.shiftingqty,
                sii.total_dispatch_plants,
                sii.rate,
                sii.amount,
                sii.deliverydate,
                COALESCE(sii.deliverydate, si.invoicedate) AS dispatch_date,
                DATE_FORMAT(COALESCE(sii.deliverydate, si.invoicedate), '%d-%b-%Y') AS formatted_dispatch_date,
                si.invoicedate,
                si.vehicleno,
                si.drivername,
                si.drivermobileno,
                si.status AS invoice_status,
                sii.status AS item_status
            FROM sales_invoice_items sii
            LEFT JOIN sales_invoice si ON si.invoiceno = sii.invoiceno
            WHERE sii.psono IN ('{$inClause}')
              AND (sii.status IS NULL OR sii.status = '' OR sii.status != 'Cancelled')
              AND (si.status IS NULL OR si.status = '' OR si.status != 'Cancelled')
            ORDER BY COALESCE(sii.deliverydate, si.invoicedate) ASC, sii.id ASC
        ";
        $dRes = $krfconn->query($dispatchSql);
        if ($dRes) {
            while ($dRow = $dRes->fetch_assoc()) {
                $pKey = trim($dRow['psono'] ?? '');
                $prodId = trim($dRow['productid'] ?? '');

                if (!isset($dispatchesByPsonoProduct[$pKey])) {
                    $dispatchesByPsonoProduct[$pKey] = [];
                }
                if (!isset($dispatchesByPsonoProduct[$pKey][$prodId])) {
                    $dispatchesByPsonoProduct[$pKey][$prodId] = [];
                }
                $dispatchesByPsonoProduct[$pKey][$prodId][] = [
                    'id' => $dRow['id'],
                    'invoiceno' => $dRow['invoiceno'],
                    'dispatch_date' => $dRow['dispatch_date'],
                    'formatted_dispatch_date' => $dRow['formatted_dispatch_date'] ?: ($dRow['dispatch_date'] ? date('d-M-Y', strtotime($dRow['dispatch_date'])) : '-'),
                    'quantity' => (int)($dRow['quantity'] ?? 0),
                    'shiftingqty' => (int)($dRow['shiftingqty'] ?? 0),
                    'total_dispatch_plants' => (int)($dRow['total_dispatch_plants'] ?: $dRow['quantity'] ?? 0),
                    'vehicleno' => $dRow['vehicleno'] ?? '',
                    'drivername' => $dRow['drivername'] ?? ''
                ];

                if (!isset($totalDispatchedByPsono[$pKey])) {
                    $totalDispatchedByPsono[$pKey] = 0;
                }
                $totalDispatchedByPsono[$pKey] += (int)($dRow['quantity'] ?? 0);
            }
        }

        // 3b. Fetch provisional order items
        $itemSql = "
            SELECT 
                psoi.id,
                psoi.psono,
                psoi.product_id,
                psoi.product_name,
                psoi.quantity,
                psoi.rate,
                psoi.amount,
                psoi.stockorder_no,
                psoi.status AS item_status,
                psoi.expecteddeliverydate,
                DATE_FORMAT(psoi.expecteddeliverydate, '%d-%b-%Y') AS formatted_delivery_date
            FROM krf_provisional_sales_order_items psoi
            WHERE psoi.psono IN ('{$inClause}')
            ORDER BY psoi.id ASC
        ";
        $iRes = $krfconn->query($itemSql);
        if ($iRes) {
            while ($iRow = $iRes->fetch_assoc()) {
                $psoKey = trim($iRow['psono'] ?? '');
                $prodId = trim($iRow['product_id'] ?? '');

                $prodDispatches = $dispatchesByPsonoProduct[$psoKey][$prodId] ?? [];
                $dispatchedQty = 0;
                $totalDispatchedPlants = 0;
                foreach ($prodDispatches as $d) {
                    $dispatchedQty += (int)($d['quantity'] ?? 0);
                    $totalDispatchedPlants += (int)($d['total_dispatch_plants'] ?: $d['quantity'] ?? 0);
                }
                $iRow['dispatched_qty'] = $dispatchedQty;
                $iRow['total_dispatch_plants'] = $totalDispatchedPlants;
                $iRow['dispatches'] = $prodDispatches;

                if (!isset($psoItemsMap[$psoKey])) $psoItemsMap[$psoKey] = [];
                $psoItemsMap[$psoKey][] = $iRow;
            }
        }
    }
    
    // -------------------------------------------------------------
    // STEP 4: Fetch Advance Payments from `advance_payment_transactions`
    // -------------------------------------------------------------
    // Cache user names map from authconn (maps loginid and mobileno to loginname)
    $userMap = [];
    if (isset($authconn) && $authconn) {
        $uRes = @$authconn->query("SELECT loginid, loginname, mobileno FROM login_user_master");
        if ($uRes) {
            while ($uRow = $uRes->fetch_assoc()) {
                $lid = trim($uRow['loginid'] ?? '');
                $lname = trim($uRow['loginname'] ?? '');
                $mob = trim($uRow['mobileno'] ?? '');
                if ($lid !== '' && $lname !== '') {
                    $userMap[$lid] = $lname;
                    $userMap[strtolower($lid)] = $lname;
                }
                if ($mob !== '' && $lname !== '') {
                    $userMap[$mob] = $lname;
                }
            }
        }
    }

    $paymentsByOrder = [];
    $allClientPayments = [];
    $totalAdvancePaidLifetime = 0;
    
    $whereApt = "
        (
            (apt.clientid = '" . $krfconn->real_escape_string($clientId) . "' AND '" . $krfconn->real_escape_string($clientId) . "' != '') 
            OR (apt.clientname = '" . $krfconn->real_escape_string($clientName) . "' AND '" . $krfconn->real_escape_string($clientName) . "' != '')
    ";
    if (!empty($allPsonoNumbers)) {
        $inPso = implode("','", array_map([$krfconn, 'real_escape_string'], $allPsonoNumbers));
        $whereApt .= " OR apt.invoiceno IN ('{$inPso}') ";
    }
    $whereApt .= ")";
    
    $aptSql = "
        SELECT 
            apt.sno,
            apt.companyid,
            apt.projectid,
            apt.clientid,
            apt.clientname,
            apt.advanceamount,
            apt.utrnumber,
            apt.paymentdate,
            apt.purposeofpayment,
            apt.invoiceno,
            apt.sourceofpayment,
            apt.paymentref_no,
            apt.receivedby,
            apt.receivedlocation,
            apt.receiptpath,
            apt.remarks,
            apt.allocated_datetime,
            apt.allocatedby,
            apt.ledgerstatus,
            COALESCE(apt.verification, 'unverified') AS verification,
            apt.verificationby,
            apt.verificationat,
            DATE_FORMAT(apt.paymentdate, '%d-%b-%Y') AS formatted_payment_date,
            DATE_FORMAT(apt.allocated_datetime, '%d-%b-%Y %H:%i') AS formatted_created_at
        FROM advance_payment_transactions apt
        WHERE {$whereApt}
        ORDER BY apt.paymentdate DESC, apt.sno DESC
    ";
    $aptRes = $krfconn->query($aptSql);
    if ($aptRes) {
        while ($pRow = $aptRes->fetch_assoc()) {
            $amt = (float)($pRow['advanceamount'] ?? 0);
            $invNo = trim($pRow['invoiceno'] ?? '');
            
            // Resolve recorded/account user name (in which account/ID payment was recorded)
            $rawAllocated = trim($pRow['allocatedby'] ?? '');
            $rawReceived = trim($pRow['receivedby'] ?? '');
            $rawVerification = trim($pRow['verificationby'] ?? '');
            
            // Priority 1: The account user whose login ID recorded this transaction
            $recordedAccountUser = $userMap[$rawAllocated] ?? $userMap[strtolower($rawAllocated)] ?? '';
            
            // Priority 2: Check if receivedby resolves in user master
            $receivedAccountUser = $userMap[$rawReceived] ?? $userMap[strtolower($rawReceived)] ?? '';
            
            // Priority 3: Other resolution or direct values
            $resolvedVerification = $userMap[$rawVerification] ?? $userMap[strtolower($rawVerification)] ?? $rawVerification;

            $finalReceivedName = $recordedAccountUser ?: ($receivedAccountUser ?: ($rawAllocated ?: ($rawReceived ?: 'Admin')));
            $pRow['received_by_name'] = $finalReceivedName;
            $pRow['allocated_by_name'] = $recordedAccountUser ?: ($rawAllocated ?: 'Admin');
            $pRow['verification_by_name'] = $resolvedVerification ?: '';

            $allClientPayments[] = $pRow;
            $totalAdvancePaidLifetime += $amt;
            
            if ($invNo) {
                if (!isset($paymentsByOrder[$invNo])) $paymentsByOrder[$invNo] = [];
                $paymentsByOrder[$invNo][] = $pRow;
            } else {
                if (!isset($paymentsByOrder['__UNALLOCATED__'])) $paymentsByOrder['__UNALLOCATED__'] = [];
                $paymentsByOrder['__UNALLOCATED__'][] = $pRow;
            }
        }
    }
    
    // -------------------------------------------------------------
    // STEP 5: Fetch Sowing Records from `krf_polyhouse_sowingplant_details`
    // -------------------------------------------------------------
    $sowingByOrder = [];
    if (!empty($allPsonoNumbers)) {
        $inPso = implode("','", array_map([$krfconn, 'real_escape_string'], $allPsonoNumbers));
        $sowingSql = "
            SELECT 
                spd.psono,
                spd.stockorder_no,
                MAX(spd.sowingbatchno) AS sowingbatchno,
                MAX(spd.sowing_date) AS sowing_date,
                MAX(spd.orderqty) AS orderqty,
                MAX(spd.totalsowingqty) AS totalsowingqty,
                COUNT(spd.sowingplantid) AS total_trays,
                MAX(spd.traycavity) AS traycavity,
                MAX(spd.productid) AS productid,
                MAX(spd.productname) AS productname,
                MAX(spd.roottype) AS roottype,
                MAX(spd.lotno) AS lotno,
                MAX(spd.health_status) AS health_status,
                MAX(spd.workingstatus) AS workingstatus,
                MAX(spd.status) AS status,
                MAX(spd.remarks) AS remarks,
                DATE_FORMAT(MAX(spd.sowing_date), '%d-%b-%Y') AS formatted_sowing_date
            FROM krf_polyhouse_sowingplant_details spd
            WHERE spd.psono IN ('{$inPso}')
            GROUP BY spd.psono, spd.stockorder_no
            ORDER BY spd.psono DESC
        ";
        $sowRes = $krfconn->query($sowingSql);
        if ($sowRes) {
            while ($sRow = $sowRes->fetch_assoc()) {
                $pKey = trim($sRow['psono'] ?? '');
                if (!isset($sowingByOrder[$pKey])) $sowingByOrder[$pKey] = [];
                $sowingByOrder[$pKey][] = $sRow;
            }
        }
    }
    
    // -------------------------------------------------------------
    // STEP 6: Fetch Polyhouse Allocations & Dispatch from `krf_productionorder_consumed`
    // -------------------------------------------------------------
    $consumedByOrder = [];
    $allStockOrders = [];
    
    if (!empty($allPsonoNumbers)) {
        $inPso = implode("','", array_map([$krfconn, 'real_escape_string'], $allPsonoNumbers));
        $consumedSql = "
            SELECT 
                c.id AS consumed_id,
                c.psono,
                c.sono,
                c.stockorderno,
                c.allocate_healthyplant,
                c.allocate_unhealthyplant,
                c.dispatchedqty,
                GREATEST(0, (COALESCE(c.allocate_healthyplant, 0) - COALESCE(c.dispatchedqty, 0))) AS estimate_open_stock,
                c.confirmstatus,
                c.status AS consumed_status,
                c.allocatedat,
                c.allocatedby,
                c.remark AS consumed_remark,
                DATE_FORMAT(c.allocatedat, '%d-%b-%Y %H:%i') AS formatted_allocated_at
            FROM krf_productionorder_consumed c
            WHERE c.psono IN ('{$inPso}') OR c.sono IN ('{$inPso}')
            ORDER BY c.id DESC
        ";
        $cRes = $krfconn->query($consumedSql);
        if ($cRes) {
            while ($cRow = $cRes->fetch_assoc()) {
                $pKey = trim($cRow['psono'] ?? $cRow['sono'] ?? '');
                $stk = trim($cRow['stockorderno'] ?? '');
                if ($stk && !in_array($stk, $allStockOrders)) $allStockOrders[] = $stk;
                
                if ($pKey) {
                    if (!isset($consumedByOrder[$pKey])) $consumedByOrder[$pKey] = [];
                    $consumedByOrder[$pKey][] = $cRow;
                }
            }
        }
    }
    
    // -------------------------------------------------------------
    // STEP 7: Fetch Polyhouse & Bed Locations from `krf_polyhouse_plant_details`
    // -------------------------------------------------------------
    $polyhouseByStock = [];
    if (!empty($allStockOrders)) {
        $inStk = implode("','", array_map([$krfconn, 'real_escape_string'], $allStockOrders));
        $polySql = "
            SELECT 
                pd.stockorder_no,
                pd.polyhouseid,
                pm.polyhousename,
                GROUP_CONCAT(DISTINCT pd.bed_no ORDER BY pd.bed_no SEPARATOR ', ') AS beds,
                COUNT(pd.polyhouseplantid) AS tray_count,
                SUM(pd.qtypertray) AS total_plants
            FROM krf_polyhouse_plant_details pd
            LEFT JOIN krf_polyhouse_master pm ON pm.polyhouseid = pd.polyhouseid
            WHERE pd.stockorder_no IN ('{$inStk}')
            GROUP BY pd.stockorder_no, pd.polyhouseid, pm.polyhousename
        ";
        $pRes = $krfconn->query($polySql);
        if ($pRes) {
            while ($pRow = $pRes->fetch_assoc()) {
                $stkKey = trim($pRow['stockorder_no'] ?? '');
                if (!isset($polyhouseByStock[$stkKey])) $polyhouseByStock[$stkKey] = [];
                $polyhouseByStock[$stkKey][] = $pRow;
            }
        }
    }
    
    // -------------------------------------------------------------
    // STEP 8: Construct Comprehensive Order Cards Array & Summary KPIs
    // -------------------------------------------------------------
    $ordersCards = [];
    $totalOrdersCount = 0;
    $totalOrderValueLifetime = 0;
    $totalPlantsOrderedLifetime = 0;
    $totalPlantsSownLifetime = 0;
    $totalPlantsAllocatedLifetime = 0;
    $totalPlantsDispatchedLifetime = 0;
    
    $allOrderKeys = array_unique(array_merge($allPsonoNumbers, array_keys($confirmedSalesOrders)));
    if (empty($allOrderKeys) && !empty($allClientPayments)) {
        $allOrderKeys[] = '__PAYMENTS_ONLY__';
    }
    
    foreach ($allOrderKeys as $orderKey) {
        $psoData = $psoList[$orderKey] ?? null;
        $soData = $confirmedSalesOrders[$orderKey] ?? null;
        $items = $psoItemsMap[$orderKey] ?? [];
        $payments = $paymentsByOrder[$orderKey] ?? [];
        $sowings = $sowingByOrder[$orderKey] ?? [];
        $allocations = $consumedByOrder[$orderKey] ?? [];
        
        $psono = $orderKey !== '__PAYMENTS_ONLY__' ? $orderKey : 'General Client Payments';
        $sono = $soData['sono'] ?? '';
        
        // Calculate Order Totals
        $orderTotalAmount = 0;
        $orderTotalQty = 0;
        if (!empty($items)) {
            foreach ($items as $it) {
                $orderTotalAmount += (float)($it['amount'] ?? 0);
                $orderTotalQty += (int)($it['quantity'] ?? 0);
            }
        } elseif ($soData) {
            $orderTotalAmount = (float)($soData['grand_total'] ?: $soData['total_amount'] ?: 0);
        }
        
        // Calculate Payments against this order
        $orderTotalAdvancePaid = 0;
        foreach ($payments as $pay) {
            $orderTotalAdvancePaid += (float)($pay['advanceamount'] ?? 0);
        }
        
        // Calculate Sowing against this order
        $orderTotalSownQty = 0;
        $stockOrderNumbers = [];
        foreach ($sowings as $sow) {
            $orderTotalSownQty += (int)($sow['totalsowingqty'] ?? 0);
            $stk = trim($sow['stockorder_no'] ?? '');
            if ($stk && !in_array($stk, $stockOrderNumbers)) $stockOrderNumbers[] = $stk;
        }
        
        // Calculate Allocations & Dispatches
        $orderAllocatedPlants = 0;
        $orderDispatchedPlants = 0;
        $orderEstOpenStock = 0;
        foreach ($allocations as $alc) {
            $orderAllocatedPlants += (int)($alc['allocate_healthyplant'] ?? 0);
            $orderDispatchedPlants += (int)($alc['dispatchedqty'] ?? 0);
            $orderEstOpenStock += (int)($alc['estimate_open_stock'] ?? 0);
            $stk = trim($alc['stockorderno'] ?? '');
            if ($stk && !in_array($stk, $stockOrderNumbers)) $stockOrderNumbers[] = $stk;
        }

        // If dispatches recorded in sales_invoice_items, use sales_invoice_items dispatched total
        $invoiceDispatchedQty = $totalDispatchedByPsono[$orderKey] ?? 0;
        if ($invoiceDispatchedQty > 0 || isset($dispatchesByPsonoProduct[$orderKey])) {
            $orderDispatchedPlants = $invoiceDispatchedQty;
        }
        
        // Polyhouse locations mapped for this order
        $polyhouseDetails = [];
        foreach ($stockOrderNumbers as $stk) {
            if (isset($polyhouseByStock[$stk])) {
                foreach ($polyhouseByStock[$stk] as $ploc) {
                    $polyhouseDetails[] = $ploc;
                }
            }
        }
        
        $balanceDue = max(0, $orderTotalAmount - $orderTotalAdvancePaid);
        $paidPercentage = ($orderTotalAmount > 0) ? min(100, round(($orderTotalAdvancePaid / $orderTotalAmount) * 100, 1)) : ($orderTotalAdvancePaid > 0 ? 100 : 0);
        
        if ($orderKey !== '__PAYMENTS_ONLY__') {
            $totalOrdersCount++;
            $totalOrderValueLifetime += $orderTotalAmount;
            $totalPlantsOrderedLifetime += $orderTotalQty;
            $totalPlantsSownLifetime += $orderTotalSownQty;
            $totalPlantsAllocatedLifetime += $orderAllocatedPlants;
            $totalPlantsDispatchedLifetime += $orderDispatchedPlants;
        }
        
        $ordersCards[] = [
            'psono' => $psono,
            'sono' => $sono,
            'booking_date' => $psoData['formatted_booking_date'] ?? $soData['formatted_so_date'] ?? '-',
            'delivery_date' => $psoData['formatted_delivery_date'] ?? '-',
            'status' => $psoData['pso_status'] ?? $soData['so_status'] ?? 'Active',
            'delivery_address' => $psoData['delivery_address'] ?? $psoData['address'] ?? $soData['delivery_address'] ?? $clientProfile['address'] ?? '-',
            'districtname' => $psoData['districtname'] ?? $soData['districtname'] ?? $clientProfile['districtname'] ?? '',
            'statename' => $psoData['statename'] ?? $soData['statename'] ?? $clientProfile['statename'] ?? '',
            'total_amount' => $orderTotalAmount,
            'advance_paid' => $orderTotalAdvancePaid,
            'balance_due' => $balanceDue,
            'paid_percentage' => $paidPercentage,
            'ordered_qty' => $orderTotalQty,
            'sown_qty' => $orderTotalSownQty,
            'allocated_qty' => $orderAllocatedPlants,
            'dispatched_qty' => $orderDispatchedPlants,
            'est_open_stock' => $orderEstOpenStock,
            'items' => $items,
            'payments' => $payments,
            'sowings' => $sowings,
            'allocations' => $allocations,
            'polyhouses' => $polyhouseDetails,
            'stock_order_numbers' => $stockOrderNumbers
        ];
    }
    
    // Add any unallocated payments box if present
    $unallocatedPayments = $paymentsByOrder['__UNALLOCATED__'] ?? [];
    
    $lifetimeBalanceOutstanding = max(0, $totalOrderValueLifetime - $totalAdvancePaidLifetime);
    
    echo json_encode([
        'status' => 'success',
        'client' => $clientProfile,
        'summary_kpis' => [
            'total_orders' => $totalOrdersCount,
            'total_order_value' => $totalOrderValueLifetime,
            'total_advance_paid' => $totalAdvancePaidLifetime,
            'total_balance_due' => $lifetimeBalanceOutstanding,
            'total_plants_ordered' => $totalPlantsOrderedLifetime,
            'total_plants_sown' => $totalPlantsSownLifetime,
            'total_plants_allocated' => $totalPlantsAllocatedLifetime,
            'total_plants_dispatched' => $totalPlantsDispatchedLifetime
        ],
        'orders_cards' => $ordersCards,
        'unallocated_payments' => $unallocatedPayments,
        'all_payments' => $allClientPayments
    ]);
    exit;
} catch (Throwable $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Server Error: ' . $e->getMessage()
    ]);
    exit;
}
}

echo json_encode(['status' => 'error', 'message' => "Invalid action requested: '{$action}'."]);
exit;
