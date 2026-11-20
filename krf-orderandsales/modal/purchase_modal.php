<?php
error_reporting(E_ALL);
require('../../connection.php');
require('../../loginCell/functions/login_functions.php');
include("../functions/orders_functions.php");
include("../functions/product_functions.php");
include("../functions/seed_functions.php");

$action = $_GET['action'];

if ($action == "processInventoryAlerts") {
    $created_by = $_POST['loginname'] ?? 'system';
    $res = processInventoryAlertsAndPO($krfconn, $created_by);
    echo json_encode($res);
    exit;
}

if ($action == "getSystemGeneratedDemand") {
    $projectid = (int)($_POST['projectid'] ?? 0);
    
    if (function_exists('processInventoryAlertsAndPO')) {
        processInventoryAlertsAndPO($krfconn);
    }

    $returnarray = [];
    $qry = "SELECT a.id, a.stockorder_no, a.productname, a.seedid, a.seedname, a.vendor_id, a.required_qty as TotalDemandQty, a.packets_needed, COALESCE(NULLIF(a.unit_price, 0), sm.Price, ipm.purchase_price, 0) as unit_price, a.order_date, a.sowing_date, a.status 
            FROM inventory_purchase_demand_alerts a 
            LEFT JOIN seed_master sm ON a.seedid = sm.SeedID
            LEFT JOIN inventory_purchase_item_master ipm ON (a.seedid = ipm.item_id OR a.seedid = ipm.seed_id)
            WHERE (a.projectid = ? OR ? = 0) AND a.status = 'Pending' 
            ORDER BY a.sowing_date ASC, a.stockorder_no ASC";

    $stmt = $krfconn->prepare($qry);
    $stmt->bind_param("ii", $projectid, $projectid);
    $stmt->execute();
    $result = $stmt->get_result();
    $rowCount = 0;
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $returnarray[] = $row;
            $rowCount++;
        }
    }
    
    // Debug logging
    file_put_contents('../../scratch/po_debug.log', date('Y-m-d H:i:s') . " - Action: getSystemGeneratedDemand - ProjectID: $projectid - Rows Found: $rowCount\n", FILE_APPEND);
    
    echo json_encode($returnarray);
    exit;
}

if ($action == "getVendors") {
    $res = $krfconn->query("SELECT VendorID, VendorName FROM vendor_master ORDER BY VendorName ASC");
    $data = [];
    if ($res) {
        while($row = $res->fetch_assoc()) $data[] = $row;
    }
    echo json_encode($data);
    exit;
}

if ($action == "saveNewPurchaseOrder") {
    $vendor_id = $_POST['vendor_id'] ?? '';
    $po_date = $_POST['po_date'] ?? date('Y-m-d');
    $expected_delivery = $_POST['expected_delivery'] ?? NULL;
    $alert_id_str = $_POST['alert_id'] ?? '';
    $projectid = (int)($_POST['projectid'] ?? 1);
    
    $seed_ids = $_POST['seed_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $prices = $_POST['price'] ?? [];

    if (empty($vendor_id) || empty($seed_ids)) {
        echo json_encode(["status" => "error", "message" => "Missing required fields."]);
        exit;
    }

    $krfconn->begin_transaction();
    try {
        // 1. Generate PO Number
        $year = date('Y', strtotime($po_date));
        $res = $krfconn->query("SELECT MAX(po_id) as max_id FROM inventory_purchase_orders");
        $next_id = ($res->fetch_assoc()['max_id'] ?? 0) + 1;
        $po_number = "PO-" . $year . "-" . str_pad($next_id, 4, '0', STR_PAD_LEFT);

        // 2. Resolve Prices & Calculate Total Amount
        $total_amount = 0;
        foreach ($quantities as $i => $qty) {
            $qtyVal = (float)$qty;
            $submittedPrice = floatval($prices[$i] ?? 0);
            $seed_id = intval($seed_ids[$i] ?? 0);
            if ($submittedPrice <= 0 && $seed_id > 0) {
                // Fallback to fetch price from seed_master or inventory_purchase_item_master
                $pRes = $krfconn->query("SELECT Price FROM seed_master WHERE SeedID = {$seed_id} LIMIT 1");
                if ($pRes && $pRow = $pRes->fetch_assoc()) {
                    $submittedPrice = floatval($pRow['Price'] ?? 0);
                }
                if ($submittedPrice <= 0) {
                    $ipRes = $krfconn->query("SELECT purchase_price FROM inventory_purchase_item_master WHERE item_id = {$seed_id} OR seed_id = {$seed_id} LIMIT 1");
                    if ($ipRes && $ipRow = $ipRes->fetch_assoc()) {
                        $submittedPrice = floatval($ipRow['purchase_price'] ?? 0);
                    }
                }
                $prices[$i] = $submittedPrice;
            }
            $total_amount += $qtyVal * $submittedPrice;
        }

        $headerSql = "INSERT INTO inventory_purchase_orders (po_number, vendor_id, po_date, expected_date, total_amount, status, created_by) 
                      VALUES (?, ?, ?, ?, ?, 'Draft', 'System')";
        $hstmt = $krfconn->prepare($headerSql);
        $hstmt->bind_param("ssssd", $po_number, $vendor_id, $po_date, $expected_delivery, $total_amount);
        if (!$hstmt->execute()) throw new Exception("Header insert failed: " . $hstmt->error);
        $po_id = $krfconn->insert_id;
        $hstmt->close();

        // 3. Insert Items
        $itemSql = "INSERT INTO inventory_purchase_order_items (po_id, seed_id, quantity, unit_price, total_price) 
                    VALUES (?, ?, ?, ?, ?)";
        $istmt = $krfconn->prepare($itemSql);
        
        foreach ($seed_ids as $i => $seed_id) {
            $qty = (float)$quantities[$i];
            $price = (float)($prices[$i] ?? 0);
            $total = $qty * $price;
            
            $istmt->bind_param("iiddd", $po_id, $seed_id, $qty, $price, $total);
            if (!$istmt->execute()) throw new Exception("Item insert failed for seed $seed_id: " . $istmt->error);
        }
        $istmt->close();

        // 4. Update Demand Alert Status and Link PO (Handles Bulk)
        if (!empty($alert_id_str)) {
            $alertIds = explode(',', $alert_id_str);
            $updateSql = "UPDATE inventory_purchase_demand_alerts SET status = 'Ordered', po_id = ? WHERE id = ?";
            $ustmt = $krfconn->prepare($updateSql);
            foreach ($alertIds as $aid) {
                $aid = (int)$aid;
                if ($aid > 0) {
                    $ustmt->bind_param("ii", $po_id, $aid);
                    $ustmt->execute();
                }
            }
            $ustmt->close();
        }

        $krfconn->commit();
        echo json_encode(["status" => "success", "po_number" => $po_number]);
    } catch (Exception $e) {
        $krfconn->rollback();
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}
?>
