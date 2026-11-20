<?php
// Function enhancements for multi-location inventory

function getLocations($conn = null)
{
    $hrmDb = (isset($GLOBALS['hrmconn']) && ($GLOBALS['hrmconn'] instanceof mysqli)) 
             ? $GLOBALS['hrmconn'] 
             : ((isset($conn) && ($conn instanceof mysqli)) ? $conn : ($GLOBALS['conn'] ?? null));
    
    $locs = [];
    if ($hrmDb) {
        $res = @$hrmDb->query("SELECT sno as location_id, LocationName as location_name FROM hrm_AttendanceLocationMaster ORDER BY LocationName");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $locs[] = $row;
            }
        }
    }

    if (empty($locs)) {
        $krfDb = (isset($GLOBALS['krfconn']) && ($GLOBALS['krfconn'] instanceof mysqli)) ? $GLOBALS['krfconn'] : ($GLOBALS['conn'] ?? null);
        if ($krfDb) {
            $res = @$krfDb->query("SELECT location_id as location_id, location_name as location_name FROM inventory_locations WHERE is_active='Yes' ORDER BY location_name");
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $locs[] = $row;
                }
            }
        }
    }
    return $locs;
}

// Override or Enhanced: Receive PO now needs Location
function receivePurchaseOrderAdvanced($conn, $poId, $receivedItems, $locationId)
{
    if (empty($locationId))
        return ["status" => "error", "message" => "Destination Location is required."];

    $conn->begin_transaction();
    try {
        $allItemsReceived = true;

        foreach ($receivedItems as $itemId => $qty) {
            if ($qty > 0) {
                // 1. Update PO Item Received Qty
                $stmt = $conn->prepare("UPDATE inventory_purchase_order_items SET received_qty = received_qty + ? WHERE po_item_id = ?");
                $stmt->bind_param("di", $qty, $itemId);
                $stmt->execute();

                // 2. Get Item ID & Seed ID
                $check = $conn->query("SELECT poi.po_item_id, poi.seed_id, poi.quantity, poi.received_qty, COALESCE(ipm.item_id, poi.seed_id) as item_id 
                                       FROM inventory_purchase_order_items poi 
                                       LEFT JOIN inventory_purchase_item_master ipm ON (poi.seed_id = ipm.item_id OR poi.seed_id = ipm.seed_id) 
                                       WHERE poi.po_item_id = $itemId");
                $itemData = $check ? $check->fetch_assoc() : [];
                $finalItemId = intval($itemData['item_id'] ?? $itemData['seed_id'] ?? 0);
                $finalSeedId = intval($itemData['seed_id'] ?? 0);

                if (($itemData['received_qty'] ?? 0) < ($itemData['quantity'] ?? 0)) {
                    $allItemsReceived = false;
                }

                // 3. Update Inventory (Location Specific, keyed primarily by item_id)
                $upsert = "INSERT INTO inventory_stock_location (item_id, seed_id, location_id, quantity) 
                           VALUES (?, ?, ?, ?) 
                           ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity), item_id = VALUES(item_id)";
                $stmtInv = $conn->prepare($upsert);
                $stmtInv->bind_param("iiid", $finalItemId, $finalItemId, $locationId, $qty);
                $stmtInv->execute();

                // 4. Log Transaction (keyed by item_id)
                $stmtLog = $conn->prepare("INSERT INTO inventory_transactions_log 
                    (item_id, seed_id, location_id, qty_change, trans_type, reference_type, reference_id, notes) 
                    VALUES (?, ?, ?, ?, 'IN', 'PO', ?, 'Received from Vendor')");
                $stmtLog->bind_param("iiids", $finalItemId, $finalItemId, $locationId, $qty, $poId);
                $stmtLog->execute();
            }
        }

        // 5. Update PO Status
        // Check if ANY items are still pending fully
        $checkAll = $conn->query("SELECT COUNT(*) as pending FROM inventory_purchase_order_items WHERE po_id = $poId AND received_qty < quantity");
        $pendingCount = $checkAll->fetch_assoc()['pending'];

        $newStatus = ($pendingCount == 0) ? 'Received' : 'Partial';
        $conn->query("UPDATE inventory_purchase_orders SET status = '$newStatus' WHERE po_id = $poId");

        $conn->commit();
        return ["status" => "success", "message" => "Items received into location successfully. PO Status: $newStatus"];

    } catch (Exception $e) {
        $conn->rollback();
        return ["status" => "error", "message" => $e->getMessage()];
    }
}

// Get Stock specific to location or aggregated
function getInventoryWithLocation($conn = null, $locationId = null, $itemId = null, $dateFrom = null, $dateTo = null)
{
    if (!$conn || !($conn instanceof mysqli)) {
        $conn = (isset($GLOBALS['krfconn']) && ($GLOBALS['krfconn'] instanceof mysqli)) ? $GLOBALS['krfconn'] : ($GLOBALS['conn'] ?? null);
    }
    if (!$conn) {
        return [];
    }

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

    $where = " WHERE 1=1";
    if ($locationId !== null && $locationId !== '' && $locationId !== 'all') {
        $where .= " AND isl.location_id = " . intval($locationId);
    }
    if ($itemId !== null && $itemId !== '' && $itemId !== 'all') {
        $idInt = intval($itemId);
        $allMatchIds = [$idInt];
        $idRes = $conn->query("SELECT item_id, seed_id FROM inventory_purchase_item_master WHERE item_id = $idInt OR seed_id = '$idInt'");
        if ($idRes) {
            while ($idRow = $idRes->fetch_assoc()) {
                if (!empty($idRow['item_id'])) $allMatchIds[] = intval($idRow['item_id']);
                if (!empty($idRow['seed_id'])) $allMatchIds[] = intval($idRow['seed_id']);
            }
        }
        $allMatchIds = array_unique(array_filter($allMatchIds));
        $idListStr = implode(',', $allMatchIds);
        $where .= " AND (isl.seed_id IN ($idListStr) OR s_item.item_id IN ($idListStr) OR s_item.seed_id IN ($idListStr) OR s_seed.item_id IN ($idListStr) OR s_seed.seed_id IN ($idListStr) OR sm.SeedID IN ($idListStr))";
    }

    $dateFilter = "";
    if ($dateFrom && $dateTo) {
        $dFrom = $conn->real_escape_string($dateFrom);
        $dTo = $conn->real_escape_string($dateTo);
        $dateFilter = " AND date(t.created_at) BETWEEN '$dFrom' AND '$dTo'";
    }

    $sql = "SELECT 
                COALESCE(s_item.item_name, s_seed.item_name, sm.SeedName, CONCAT('Item #', COALESCE(isl.item_id, isl.seed_id))) as SeedName, 
                COALESCE(s_item.sku, s_seed.sku, sm.sku, '') as sku, 
                COALESCE(s_item.category_id, s_seed.category_id, 'General') as category_name,
                COALESCE(s_item.base_uom, s_item.unit, s_seed.unit, 'Unit') as base_uom,
                isl.quantity as stock_quantity,
                isl.last_updated,
                isl.location_id,
                COALESCE(isl.item_id, isl.seed_id) as item_id,
                COALESCE(isl.item_id, isl.seed_id) as seed_id,
                COALESCE(trans.total_purchase, 0) as total_purchase,
                COALESCE(trans.total_used, 0) as total_used
            FROM inventory_stock_location isl
            LEFT JOIN inventory_purchase_item_master s_item ON (COALESCE(isl.item_id, isl.seed_id) = s_item.item_id)
            LEFT JOIN inventory_purchase_item_master s_seed ON (isl.seed_id = s_seed.seed_id AND isl.seed_id > 0 AND s_item.item_id IS NULL)
            LEFT JOIN seed_master sm ON (isl.seed_id = sm.SeedID AND s_item.item_id IS NULL AND s_seed.item_id IS NULL)
            LEFT JOIN (
                SELECT 
                    COALESCE(item_id, seed_id) as item_id, 
                    location_id,
                    SUM(CASE WHEN qty_change > 0 OR trans_type IN ('IN', 'TRANSFER_IN', 'ADJUST_IN', 'purchase', 'RECEIPT', 'PO') THEN qty_change ELSE 0 END) as total_purchase,
                    SUM(CASE WHEN qty_change < 0 OR trans_type IN ('OUT', 'TRANSFER_OUT', 'ADJUST_OUT', 'consumption', 'sale', 'usage', 'DISPATCH') THEN ABS(qty_change) ELSE 0 END) as total_used
                FROM inventory_transactions_log t
                WHERE 1=1 $dateFilter
                GROUP BY COALESCE(item_id, seed_id), location_id
            ) trans ON (COALESCE(isl.item_id, isl.seed_id) = trans.item_id) AND isl.location_id = trans.location_id
            $where
            ORDER BY SeedName ASC";

    $res = $conn->query($sql);
    $data = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $row['location_name'] = $locationMap[$row['location_id']] ?? ('Location #' . $row['location_id']);
            $stockQty = (float)$row['stock_quantity'];
            $totPurch = (float)$row['total_purchase'];
            $totUsed = (float)$row['total_used'];
            
            // If no transactions logged yet but physical stock exists, treat opening stock as total purchase
            if ($totPurch == 0 && $totUsed == 0 && $stockQty > 0) {
                $totPurch = $stockQty;
            }

            $row['total_purchase'] = $totPurch;
            $row['total_used'] = $totUsed;
            $row['balance_quantity'] = $stockQty;
            $row['quantity'] = $stockQty;
            $row['current_stock'] = $stockQty;
            $row['quantity_formatted'] = number_format($stockQty, 2);
            $data[] = $row;
        }
    }
    return $data;
}

// Get Stock Aggregated across all locations item-wise
function getInventoryAggregatedByItem($conn = null, $itemId = null, $dateFrom = null, $dateTo = null)
{
    $detailed = getInventoryWithLocation($conn, null, $itemId, $dateFrom, $dateTo);
    $aggregated = [];

    foreach ($detailed as $row) {
        $key = $row['seed_id'];
        if (!isset($aggregated[$key])) {
            $aggregated[$key] = [
                'seed_id' => $row['seed_id'],
                'SeedName' => $row['SeedName'],
                'sku' => $row['sku'],
                'category_name' => $row['category_name'] ?? 'General',
                'base_uom' => $row['base_uom'] ?? 'Unit',
                'unit' => $row['base_uom'] ?? 'Unit',
                'total_purchase' => 0,
                'total_used' => 0,
                'stock_quantity' => 0,
                'current_stock' => 0,
                'balance_quantity' => 0,
                'location_count' => 0,
                'locations' => [],
                'locations_breakdown' => [],
                'last_updated' => $row['last_updated']
            ];
        }

        $aggregated[$key]['total_purchase'] += (float)$row['total_purchase'];
        $aggregated[$key]['total_used'] += (float)$row['total_used'];
        $aggregated[$key]['stock_quantity'] += (float)$row['stock_quantity'];
        $aggregated[$key]['current_stock'] = $aggregated[$key]['stock_quantity'];
        $aggregated[$key]['balance_quantity'] += (float)$row['balance_quantity'];
        $aggregated[$key]['location_count']++;
        
        $locItem = [
            'location_id' => $row['location_id'],
            'location_name' => $row['location_name'],
            'stock_quantity' => (float)$row['stock_quantity'],
            'quantity' => (float)$row['stock_quantity'],
            'current_stock' => (float)$row['stock_quantity'],
            'quantity_formatted' => number_format((float)$row['stock_quantity'], 2),
            'total_purchase' => (float)$row['total_purchase'],
            'total_used' => (float)$row['total_used'],
            'balance_quantity' => (float)$row['balance_quantity'],
            'last_updated' => $row['last_updated']
        ];
        
        $aggregated[$key]['locations'][] = $locItem;
        $aggregated[$key]['locations_breakdown'][] = $locItem;

        if (!empty($row['last_updated']) && $row['last_updated'] > $aggregated[$key]['last_updated']) {
            $aggregated[$key]['last_updated'] = $row['last_updated'];
        }
    }

    return array_values($aggregated);
}

// Get Location Breakdown and transactions for a specific item
function getItemLocationBreakdown($conn = null, $seedId = 0, $dateFrom = null, $dateTo = null)
{
    if (!$conn || !($conn instanceof mysqli)) {
        $conn = (isset($GLOBALS['krfconn']) && ($GLOBALS['krfconn'] instanceof mysqli)) ? $GLOBALS['krfconn'] : ($GLOBALS['conn'] ?? null);
    }
    if (!$conn || !$seedId) {
        return ['status' => 'error', 'message' => 'Invalid item parameters.'];
    }

    $sInt = intval($seedId);
    $allMatchIds = [$sInt];
    $itemName = 'Item #' . $seedId;
    $sku = '';

    $idRes = $conn->query("SELECT item_id, seed_id, item_name, sku FROM inventory_purchase_item_master WHERE item_id = $sInt OR seed_id = '$sInt' LIMIT 1");
    if ($idRes && $idRow = $idRes->fetch_assoc()) {
        if (!empty($idRow['item_id'])) $allMatchIds[] = intval($idRow['item_id']);
        if (!empty($idRow['seed_id'])) $allMatchIds[] = intval($idRow['seed_id']);
        $itemName = $idRow['item_name'];
        $sku = $idRow['sku'];
    }
    $allMatchIds = array_unique(array_filter($allMatchIds));
    $idListStr = implode(',', $allMatchIds);

    $locationRows = getInventoryWithLocation($conn, null, $seedId, $dateFrom, $dateTo);

    $sumPurchase = 0;
    $sumUsed = 0;
    $sumStock = 0;
    $sumBalance = 0;

    foreach ($locationRows as &$loc) {
        if (!empty($loc['SeedName']) && $loc['SeedName'] !== 'Item') {
            $itemName = $loc['SeedName'];
        }
        if (!empty($loc['sku'])) {
            $sku = $loc['sku'];
        }
        $sumPurchase += (float)$loc['total_purchase'];
        $sumUsed += (float)$loc['total_used'];
        $sumStock += (float)$loc['stock_quantity'];
        $sumBalance += (float)$loc['balance_quantity'];

        $loc['quantity'] = (float)$loc['stock_quantity'];
        $loc['quantity_formatted'] = number_format((float)$loc['stock_quantity'], 2);
    }
    unset($loc);

    if ($sumPurchase == 0 && $sumUsed == 0 && $sumStock > 0) {
        $sumPurchase = $sumStock;
    }
    if ($sumBalance == 0 && $sumStock > 0) {
        $sumBalance = $sumStock;
    }

    $summary = [
        'total_purchase' => $sumPurchase,
        'total_purchased' => number_format($sumPurchase, 2),
        'total_used' => number_format($sumUsed, 2),
        'stock_quantity' => $sumStock,
        'current_stock' => number_format($sumStock, 2),
        'balance_quantity' => $sumBalance,
        'balance_qty' => number_format($sumBalance, 2),
        'location_count' => count($locationRows)
    ];

    // Fetch transaction history
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

    $dateFilter = "";
    if ($dateFrom && $dateTo) {
        $dFrom = $conn->real_escape_string($dateFrom);
        $dTo = $conn->real_escape_string($dateTo);
        $dateFilter = " AND date(created_at) BETWEEN '$dFrom' AND '$dTo'";
    }

    $txSql = "SELECT log_id, location_id, qty_change, trans_type, reference_type, reference_id, notes, created_at
              FROM inventory_transactions_log
              WHERE seed_id IN ($idListStr) $dateFilter
              ORDER BY created_at DESC, log_id DESC
              LIMIT 50";
    $txRes = $conn->query($txSql);
    $transactions = [];
    if ($txRes) {
        while ($t = $txRes->fetch_assoc()) {
            $t['location_name'] = $locationMap[$t['location_id']] ?? ('Location #' . $t['location_id']);
            $transactions[] = $t;
        }
    }

    return [
        'status' => 'success',
        'item' => [
            'seed_id' => $seedId,
            'item_name' => $itemName,
            'sku' => $sku
        ],
        'summary' => $summary,
        'locations' => $locationRows,
        'transactions' => $transactions
    ];
}

// Finalize Purchase Receipt with Invoice Details
function finalizePurchaseReceipt($conn, $data)
{
    $poId = $data['po_id'] ?? 0;
    $locationId = $data['location_id'];
    $invoiceNo = $data['invoice_no'];
    $invoiceDate = $data['invoice_date'];
    $receivedDate = $data['received_date'];
    $items = $data['items']; // array of objects { po_item_id, seed_id, unit_id, qty, price, gst_percent, cgst_percent, sgst_percent, igst_percent, taxable_amount, gst_amount, total_price }

    if (empty($locationId))
        return ["status" => "error", "message" => "Location is required"];

    $conn->begin_transaction();
    try {
        // 1. Create Receipt Header
        $totalAmount = 0;
        $taxableAmount = 0;
        $gstAmount = 0;

        foreach ($items as $it) {
            $qty = floatval($it['qty']);
            $price = floatval($it['price']);
            $taxable = isset($it['taxable_amount']) ? floatval($it['taxable_amount']) : ($qty * $price);
            $gstP = isset($it['gst_percent']) ? floatval($it['gst_percent']) : 0;
            $gstAmt = isset($it['gst_amount']) ? floatval($it['gst_amount']) : ($taxable * ($gstP / 100));
            $tot = isset($it['total_price']) ? floatval($it['total_price']) : ($taxable + $gstAmt);

            $taxableAmount += $taxable;
            $gstAmount += $gstAmt;
            $totalAmount += $tot;
        }

        $stmtR = $conn->prepare("INSERT INTO inventory_purchase_receipts (po_id, invoice_no, invoice_date, received_date, location_id, taxable_amount, gst_amount, total_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmtR->bind_param("isssiddd", $poId, $invoiceNo, $invoiceDate, $receivedDate, $locationId, $taxableAmount, $gstAmount, $totalAmount);
        $stmtR->execute();
        $receiptId = $stmtR->insert_id;
        $stmtR->close();

        foreach ($items as $item) {
            if ($item['qty'] <= 0)
                continue;

            $rawItemId = intval($item['item_id'] ?? 0);
            $rawSeedId = intval($item['seed_id'] ?? 0);
            $qty = floatval($item['qty']);
            $price = floatval($item['price']);
            $unitId = intval($item['unit_id'] ?? 0);
            $taxable = isset($item['taxable_amount']) ? floatval($item['taxable_amount']) : ($qty * $price);
            $gstPercent = isset($item['gst_percent']) ? floatval($item['gst_percent']) : 0;
            $cgstPercent = isset($item['cgst_percent']) ? floatval($item['cgst_percent']) : 0;
            $sgstPercent = isset($item['sgst_percent']) ? floatval($item['sgst_percent']) : 0;
            $igstPercent = isset($item['igst_percent']) ? floatval($item['igst_percent']) : 0;
            $gstAmt = isset($item['gst_amount']) ? floatval($item['gst_amount']) : ($taxable * ($gstPercent / 100));
            $lineTotal = isset($item['total_price']) ? floatval($item['total_price']) : ($taxable + $gstAmt);

            // Resolve proper item_id and seed_id from inventory_purchase_item_master
            $finalItemId = $rawItemId;
            $finalSeedId = $rawSeedId;

            $lookupId = $finalItemId > 0 ? $finalItemId : $finalSeedId;
            if ($lookupId > 0) {
                $imCheck = $conn->query("SELECT item_id, seed_id FROM inventory_purchase_item_master WHERE item_id = $lookupId OR seed_id = '$lookupId' ORDER BY (item_id = $lookupId) DESC LIMIT 1");
                if ($imCheck && $imRow = $imCheck->fetch_assoc()) {
                    $finalItemId = intval($imRow['item_id']);
                    if (!empty($imRow['seed_id']) && intval($imRow['seed_id']) > 0) {
                        $finalSeedId = intval($imRow['seed_id']);
                    }
                }
            }

            if ($finalSeedId <= 0 && $rawSeedId > 0 && $rawSeedId != $finalItemId) {
                $finalSeedId = $rawSeedId;
            }

            // 2. Save Receipt Item with BOTH item_id and seed_id
            $stmtRI = $conn->prepare("INSERT INTO inventory_purchase_receipt_items (receipt_id, item_id, seed_id, unit_id, quantity, unit_price, taxable_amount, gst_percent, cgst_percent, sgst_percent, igst_percent, gst_amount, total_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtRI->bind_param("iiiiddddddddd", $receiptId, $finalItemId, $finalSeedId, $unitId, $qty, $price, $taxable, $gstPercent, $cgstPercent, $sgstPercent, $igstPercent, $gstAmt, $lineTotal);
            $stmtRI->execute();
            $stmtRI->close();

            // 3. Update PO Item if exists
            if (!empty($item['po_item_id'])) {
                $pId = intval($item['po_item_id']);
                $stmtUI = $conn->prepare("UPDATE inventory_purchase_order_items SET received_qty = received_qty + ? WHERE po_item_id = ?");
                $stmtUI->bind_param("di", $qty, $pId);
                $stmtUI->execute();
                $stmtUI->close();
            }

            // 4. Update Inventory Stock Location (keyed by item_id)
            $stockKeyId = $finalItemId > 0 ? $finalItemId : ($finalSeedId > 0 ? $finalSeedId : $rawSeedId);
            $upsert = "INSERT INTO inventory_stock_location (item_id, seed_id, location_id, quantity) 
                       VALUES (?, ?, ?, ?) 
                       ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity), item_id = VALUES(item_id)";
            $stmtInv = $conn->prepare($upsert);
            $stmtInv->bind_param("iiid", $stockKeyId, $stockKeyId, $locationId, $qty);
            $stmtInv->execute();
            $stmtInv->close();

            // 5. Log Transaction (keyed by item_id)
            $notes = "Invoice #$invoiceNo received at " . $receivedDate;
            $stmtLog = $conn->prepare("INSERT INTO inventory_transactions_log 
                (item_id, seed_id, location_id, qty_change, trans_type, reference_type, reference_id, notes) 
                VALUES (?, ?, ?, ?, 'IN', 'RECEIPT', ?, ?)");
            $stmtLog->bind_param("iiidss", $stockKeyId, $stockKeyId, $locationId, $qty, $receiptId, $notes);
            $stmtLog->execute();
            $stmtLog->close();
        }

        // 6. Update PO Status
        if ($poId > 0) {
            $checkAll = $conn->query("SELECT COUNT(*) as pending FROM inventory_purchase_order_items WHERE po_id = $poId AND received_qty < quantity");
            $pendingCount = $checkAll ? $checkAll->fetch_assoc()['pending'] : 0;
            $newStatus = ($pendingCount == 0) ? 'Received' : 'Partial';
            $conn->query("UPDATE inventory_purchase_orders SET status = '$newStatus' WHERE po_id = $poId");
        }

        $conn->commit();
        return ["status" => "success", "message" => "Invoice #$invoiceNo processed and inventory updated successfully."];

    } catch (Exception $e) {
        $conn->rollback();
        return ["status" => "error", "message" => $e->getMessage()];
    }
}
?>