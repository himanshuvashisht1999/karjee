<?php
// Handles Stock Transfer Request Logic

function createTransferRequest($conn, $data)
{
    if (empty($data['from_location']) || empty($data['to_location'])) {
        return ["status" => "error", "message" => "Both Main and Sub warehouse must be selected."];
    }

    $reqNum = "TR-" . date("Ymd") . "-" . rand(1000, 9999);
    $reqDate = date("Y-m-d");

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO inventory_transfer_requests 
            (request_number, from_location_id, to_location_id, request_date, remarks, status, requested_by) 
            VALUES (?, ?, ?, ?, ?, 'Pending', 'User')");
        $stmt->bind_param("siiss", $reqNum, $data['from_location'], $data['to_location'], $reqDate, $data['remarks']);
        $stmt->execute();
        $reqId = $stmt->insert_id;

        $stmtItem = $conn->prepare("INSERT INTO inventory_transfer_items (request_id, seed_id, quantity_requested) VALUES (?, ?, ?)");
        foreach ($data['items'] as $item) {
            $stmtItem->bind_param("iid", $reqId, $item['seed_id'], $item['qty']);
            $stmtItem->execute();
        }

        $conn->commit();
        return ["status" => "success", "message" => "Transfer Request Created successfully.", "req_id" => $reqId];
    } catch (Exception $e) {
        $conn->rollback();
        return ["status" => "error", "message" => $e->getMessage()];
    }
}

function approveTransferRequest($conn, $reqId, $approvedItems)
{
    // approvedItems = [ { item_id: X, approved_qty: Y }, ... ]
    $req = $conn->query("SELECT * FROM inventory_transfer_requests WHERE request_id = $reqId")->fetch_assoc();
    if (!$req || $req['status'] != 'Pending') {
        return ["status" => "error", "message" => "Invalid Request or already processed."];
    }

    $fromLoc = $req['from_location_id'];

    // Retrieve Source Location Name for clear error messages
    $fromLocName = "Location #$fromLoc";
    $hrmDb = (isset($GLOBALS['hrmconn']) && ($GLOBALS['hrmconn'] instanceof mysqli)) ? $GLOBALS['hrmconn'] : null;
    if ($hrmDb) {
        $locRes = @$hrmDb->query("SELECT LocationName FROM hrm_AttendanceLocationMaster WHERE sno = " . (int)$fromLoc);
        if ($locRes && ($locRow = $locRes->fetch_assoc())) {
            $fromLocName = $locRow['LocationName'];
        }
    }

    $conn->begin_transaction();
    try {
        foreach ($approvedItems as $item) {
            $itemId = $item['item_id'];
            $qty = (float)$item['approved_qty'];

            // Update Approved Qty
            $conn->query("UPDATE inventory_transfer_items SET quantity_approved = $qty WHERE item_id = $itemId");

            // Get Seed ID & Item info for clear error reporting
            $itemInfoRes = $conn->query("
                SELECT i.seed_id, 
                       COALESCE(s_item.item_name, s_seed.item_name, sm.SeedName, 'Item') as item_name,
                       COALESCE(s_item.sku, s_seed.sku, sm.sku, '') as sku
                FROM inventory_transfer_items i
                LEFT JOIN inventory_purchase_item_master s_item ON (i.seed_id = s_item.item_id)
                LEFT JOIN inventory_purchase_item_master s_seed ON (i.seed_id = s_seed.seed_id AND i.seed_id > 0 AND s_item.item_id IS NULL)
                LEFT JOIN seed_master sm ON (i.seed_id = sm.SeedID AND s_item.item_id IS NULL AND s_seed.item_id IS NULL)
                WHERE i.item_id = $itemId
                LIMIT 1
            ");
            $itemInfo = $itemInfoRes ? $itemInfoRes->fetch_assoc() : null;
            $seedId = $itemInfo ? $itemInfo['seed_id'] : 0;
            $itemName = $itemInfo ? $itemInfo['item_name'] : "Item #$itemId";
            if (!empty($itemInfo['sku'])) {
                $itemName .= " ({$itemInfo['sku']})";
            }

            if ($qty > 0) {
                // 1. Check Source Stock & Deduct
                $stockCheck = $conn->prepare("
                    SELECT COALESCE(isl.item_id, isl.seed_id) as actual_item_id, isl.seed_id as actual_seed_id, isl.quantity 
                    FROM inventory_stock_location isl
                    LEFT JOIN inventory_purchase_item_master s ON (isl.item_id = s.item_id OR isl.seed_id = s.seed_id OR isl.seed_id = s.item_id)
                    WHERE (isl.item_id = ? OR isl.seed_id = ? OR s.item_id = ? OR s.seed_id = ?) 
                      AND isl.location_id = ?
                    LIMIT 1 FOR UPDATE
                ");
                $stockCheck->bind_param("iiiii", $seedId, $seedId, $seedId, $seedId, $fromLoc);
                $stockCheck->execute();
                $stock = $stockCheck->get_result()->fetch_assoc();

                $availQty = ($stock && isset($stock['quantity'])) ? (float)$stock['quantity'] : 0;

                if (!$stock || $availQty < $qty) {
                    throw new Exception("Insufficient stock for \"$itemName\" in Source Warehouse ($fromLocName). Requested to dispatch: $qty, but only $availQty available in stock.");
                }

                $actualItemId = intval($stock['actual_item_id'] ?: ($stock['actual_seed_id'] ?: $seedId));
                $actualSeedId = intval($stock['actual_seed_id'] ?: $actualItemId);
                $deduct = $conn->prepare("UPDATE inventory_stock_location SET quantity = quantity - ? WHERE (item_id = ? OR seed_id = ?) AND location_id = ?");
                $deduct->bind_param("diii", $qty, $actualItemId, $actualSeedId, $fromLoc);
                $deduct->execute();

                // 2. Log OUT Transaction (Dispatched)
                $logOut = $conn->prepare("INSERT INTO inventory_transactions_log (item_id, seed_id, location_id, qty_change, trans_type, reference_type, reference_id, notes) VALUES (?, ?, ?, ?, 'TRANSFER_OUT', 'TR_REQ', ?, 'Dispatched to Sub Warehouse')");
                $negQty = -$qty;
                $logOut->bind_param("iiids", $actualItemId, $actualSeedId, $fromLoc, $negQty, $req['request_number']);
                $logOut->execute();
            }
        }

        // Update Status to 'Dispatched'
        $conn->query("UPDATE inventory_transfer_requests SET status = 'Dispatched' WHERE request_id = $reqId");

        $conn->commit();
        return ["status" => "success", "message" => "Transfer Dispatched successfully. Stock deducted from Source."];

    } catch (Exception $e) {
        $conn->rollback();
        return ["status" => "error", "message" => $e->getMessage()];
    }
}

function receiveTransferRequest($conn, $reqId)
{
    $req = $conn->query("SELECT * FROM inventory_transfer_requests WHERE request_id = $reqId")->fetch_assoc();
    if (!$req || $req['status'] != 'Dispatched') {
        return ["status" => "error", "message" => "Request must be Dispatched before receiving."];
    }

    $toLoc = $req['to_location_id'];

    $conn->begin_transaction();
    try {
        // Fetch items with approved quantities
        $itemsRes = $conn->query("SELECT * FROM inventory_transfer_items WHERE request_id = $reqId");
        while ($item = $itemsRes->fetch_assoc()) {
            $qty = $item['quantity_approved'];
            $seedId = $item['seed_id'];

            if ($qty > 0) {
                // Find actual item_id and seed_id from inventory mapping
                $stockCheck = $conn->prepare("
                    SELECT COALESCE(isl.item_id, isl.seed_id) as actual_item_id, isl.seed_id as actual_seed_id, s.item_id as master_item_id
                    FROM inventory_stock_location isl
                    LEFT JOIN inventory_purchase_item_master s ON (isl.item_id = s.item_id OR isl.seed_id = s.seed_id OR isl.seed_id = s.item_id)
                    WHERE (isl.item_id = ? OR isl.seed_id = ? OR s.item_id = ? OR s.seed_id = ?) 
                    LIMIT 1
                ");
                $stockCheck->bind_param("iiii", $seedId, $seedId, $seedId, $seedId);
                $stockCheck->execute();
                $stRes = $stockCheck->get_result()->fetch_assoc();
                
                $actualItemId = intval($stRes['actual_item_id'] ?? ($stRes['master_item_id'] ?? $seedId));
                $actualSeedId = intval($stRes['actual_seed_id'] ?? $actualItemId);

                // 1. Add to Destination Stock
                $add = $conn->prepare("INSERT INTO inventory_stock_location (item_id, seed_id, location_id, quantity) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity), item_id = VALUES(item_id)");
                $add->bind_param("iiid", $actualItemId, $actualSeedId, $toLoc, $qty);
                $add->execute();

                // 2. Log IN Transaction
                $logIn = $conn->prepare("INSERT INTO inventory_transactions_log (item_id, seed_id, location_id, qty_change, trans_type, reference_type, reference_id, notes) VALUES (?, ?, ?, ?, 'TRANSFER_IN', 'TR_REQ', ?, 'Received at Sub Warehouse')");
                $logIn->bind_param("iiids", $actualItemId, $actualSeedId, $toLoc, $qty, $req['request_number']);
                $logIn->execute();
            }
        }

        // Update Status to 'Completed'
        $conn->query("UPDATE inventory_transfer_requests SET status = 'Completed' WHERE request_id = $reqId");

        $conn->commit();
        return ["status" => "success", "message" => "Items Received and Inventory Updated."];

    } catch (Exception $e) {
        $conn->rollback();
        return ["status" => "error", "message" => "Receive Failed: " . $e->getMessage()];
    }
}

function getTransferRequests($conn = null, $status = '', $locationId = null)
{
    if (!$conn || !($conn instanceof mysqli)) {
        $conn = (isset($GLOBALS['krfconn']) && ($GLOBALS['krfconn'] instanceof mysqli)) ? $GLOBALS['krfconn'] : ($GLOBALS['conn'] ?? null);
    }
    if (!$conn) return [];

    $locationMap = [];
    $hrmDb = (isset($GLOBALS['hrmconn']) && ($GLOBALS['hrmconn'] instanceof mysqli)) ? $GLOBALS['hrmconn'] : null;
    if ($hrmDb) {
        $locRes = @$hrmDb->query("SELECT sno, LocationName FROM hrm_AttendanceLocationMaster");
        if ($locRes) {
            while ($l = $locRes->fetch_assoc()) {
                $locationMap[$l['sno']] = $l['LocationName'];
            }
        }
    }

    $sql = "SELECT r.*, 
            COUNT(ti.item_id) as total_items, 
            COALESCE(SUM(ti.quantity_requested), 0) as total_req_qty, 
            COALESCE(SUM(ti.quantity_approved), 0) as total_app_qty 
            FROM inventory_transfer_requests r 
            LEFT JOIN inventory_transfer_items ti ON r.request_id = ti.request_id 
            WHERE 1=1";
    if ($status) {
        $statusEsc = $conn->real_escape_string($status);
        $sql .= " AND r.status = '$statusEsc'";
    }
    if (!empty($locationId) && $locationId !== 'all' && (int)$locationId > 0) {
        $locId = (int)$locationId;
        $sql .= " AND (r.from_location_id = $locId OR r.to_location_id = $locId)";
    }
    $sql .= " GROUP BY r.request_id ORDER BY r.created_at DESC";

    $res = $conn->query($sql);
    $data = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $row['FromName'] = $locationMap[$row['from_location_id']] ?? ('Location #' . $row['from_location_id']);
            $row['ToName'] = $locationMap[$row['to_location_id']] ?? ('Location #' . $row['to_location_id']);
            $data[] = $row;
        }
    }
    return $data;
}

function getTransferRequestDetails($conn = null, $id = 0)
{
    if (!$conn || !($conn instanceof mysqli)) {
        $conn = (isset($GLOBALS['krfconn']) && ($GLOBALS['krfconn'] instanceof mysqli)) ? $GLOBALS['krfconn'] : ($GLOBALS['conn'] ?? null);
    }
    if (!$conn || empty($id)) return null;

    $locationMap = [];
    $hrmDb = (isset($GLOBALS['hrmconn']) && ($GLOBALS['hrmconn'] instanceof mysqli)) ? $GLOBALS['hrmconn'] : null;
    if ($hrmDb) {
        $locRes = @$hrmDb->query("SELECT sno, LocationName FROM hrm_AttendanceLocationMaster");
        if ($locRes) {
            while ($l = $locRes->fetch_assoc()) {
                $locationMap[$l['sno']] = $l['LocationName'];
            }
        }
    }

    $id = (int)$id;
    $reqRes = $conn->query("SELECT r.* FROM inventory_transfer_requests r WHERE r.request_id = $id");
    $req = $reqRes ? $reqRes->fetch_assoc() : null;

    if ($req) {
        $req['FromName'] = $locationMap[$req['from_location_id']] ?? ('Location #' . $req['from_location_id']);
        $req['ToName'] = $locationMap[$req['to_location_id']] ?? ('Location #' . $req['to_location_id']);

        $itemsRes = $conn->query("SELECT i.*, 
                                  COALESCE(s_item.item_name, s_seed.item_name, sm.SeedName, 'Item') as SeedName, 
                                  COALESCE(s_item.sku, s_seed.sku, sm.sku, '') as sku, 
                                  COALESCE(s_item.unit, s_seed.unit, 'Unit') as base_uom, 
                                  COALESCE(s_item.unit, s_seed.unit, 'Unit') as purchase_uom, 
                                  COALESCE(s_item.batch_required, s_seed.batch_required, 0) as batch_required,
                                  COALESCE(s_item.expiry_required, s_seed.expiry_required, 0) as expiry_required,
                                  COALESCE(s_item.serial_required, s_seed.serial_required, 0) as serial_required, 
                                  isl.quantity as SourceStock
                                  FROM inventory_transfer_items i 
                                  LEFT JOIN inventory_purchase_item_master s_item ON (i.seed_id = s_item.item_id)
                                  LEFT JOIN inventory_purchase_item_master s_seed ON (i.seed_id = s_seed.seed_id AND i.seed_id > 0 AND s_item.item_id IS NULL)
                                  LEFT JOIN seed_master sm ON (i.seed_id = sm.SeedID AND s_item.item_id IS NULL AND s_seed.item_id IS NULL)
                                  LEFT JOIN inventory_stock_location isl ON (isl.seed_id = i.seed_id AND isl.location_id = {$req['from_location_id']})
                                  WHERE i.request_id = $id");
        $req['items'] = [];
        if ($itemsRes) {
            while ($row = $itemsRes->fetch_assoc()) {
                $req['items'][] = $row;
            }
        }
    }
    return $req;
}
?>