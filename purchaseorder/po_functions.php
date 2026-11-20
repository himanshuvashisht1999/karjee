<?php

function getPurchaseOrders($conn, $filters = [])
{
    if (!$conn || !($conn instanceof mysqli)) {
        return [];
    }

    $supplierMap = [];
    $accountingDb = (isset($GLOBALS['conn']) && ($GLOBALS['conn'] instanceof mysqli)) ? $GLOBALS['conn'] : null;
    if ($accountingDb) {
        $sRes = $accountingDb->query("SELECT paytoid, paytoname FROM supplier_authorizedperson");
        if ($sRes) {
            while ($sRow = $sRes->fetch_assoc()) {
                if (!empty($sRow['paytoid']) && !empty($sRow['paytoname'])) {
                    $supplierMap[$sRow['paytoid']] = $sRow['paytoname'];
                }
            }
        }
    }

    $krfDb = (isset($GLOBALS['krfconn']) && ($GLOBALS['krfconn'] instanceof mysqli)) ? $GLOBALS['krfconn'] : $conn;
    if ($krfDb && ($krfDb instanceof mysqli)) {
        $vRes = $krfDb->query("SELECT VendorID, VendorName FROM vendor_master");
        if ($vRes) {
            while ($vRow = $vRes->fetch_assoc()) {
                if (!empty($vRow['VendorID']) && !empty($vRow['VendorName'])) {
                    $supplierMap[$vRow['VendorID']] = $vRow['VendorName'];
                }
            }
        }
    }

    $sql = "SELECT po.*,
                   COALESCE(
                       NULLIF(po.total_amount, 0),
                       (SELECT SUM(CASE 
                           WHEN total_price > 0 THEN total_price 
                           WHEN (quantity * unit_price) > 0 THEN (quantity * unit_price)
                           ELSE quantity * COALESCE(sm.Price, ipm.purchase_price, 0)
                        END)
                        FROM inventory_purchase_order_items poi
                        LEFT JOIN seed_master sm ON poi.seed_id = sm.SeedID
                        LEFT JOIN inventory_purchase_item_master ipm ON (poi.seed_id = ipm.item_id OR poi.seed_id = ipm.seed_id)
                        WHERE poi.po_id = po.po_id
                       ),
                       0
                   ) AS calculated_total_amount,
                   r.receipt_id,
                   r.invoice_no,
                   r.invoice_date,
                   r.received_date,
                   r.received_by,
                   r.receipt_created_at,
                   r.total_receipt_amount,
                   r.receipt_count
            FROM inventory_purchase_orders po 
            LEFT JOIN (
                SELECT 
                    po_id,
                    MAX(receipt_id) as receipt_id,
                    GROUP_CONCAT(DISTINCT invoice_no ORDER BY receipt_id DESC SEPARATOR ', ') as invoice_no,
                    MAX(invoice_date) as invoice_date,
                    MAX(received_date) as received_date,
                    SUBSTRING_INDEX(GROUP_CONCAT(COALESCE(NULLIF(received_by, ''), 'Admin') ORDER BY receipt_id DESC SEPARATOR '||'), '||', 1) as received_by,
                    MAX(created_at) as receipt_created_at,
                    SUM(total_amount) as total_receipt_amount,
                    COUNT(receipt_id) as receipt_count
                FROM inventory_purchase_receipts
                GROUP BY po_id
            ) r ON po.po_id = r.po_id
            WHERE 1=1";

    if (!empty($filters['status'])) {
        $sql .= " AND po.status = '" . $conn->real_escape_string($filters['status']) . "'";
    }

    if (!empty($filters['search'])) {
        $term = $conn->real_escape_string($filters['search']);
        $matchingVendorIds = [];
        foreach ($supplierMap as $vId => $vName) {
            if (stripos($vName, $term) !== false) {
                $matchingVendorIds[] = "'" . $conn->real_escape_string($vId) . "'";
            }
        }
        $vendorClause = !empty($matchingVendorIds) ? " OR po.vendor_id IN (" . implode(',', $matchingVendorIds) . ")" : "";
        $sql .= " AND (po.po_number LIKE '%$term%' OR r.invoice_no LIKE '%$term%' $vendorClause)";
    }

    $sql .= " ORDER BY po.created_at DESC";

    $result = $conn->query($sql);
    $data = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $vId = $row['vendor_id'];
            $row['VendorName'] = $supplierMap[$vId] ?? 'N/A';
            $data[] = $row;
        }
    }
    return $data;
}

function getPurchaseOrderById($conn, $id)
{
    if (empty($id) || !$conn || !($conn instanceof mysqli))
        return null;

    $po = [];

    // Fetch Main PO
    $stmt = $conn->prepare("SELECT po.* FROM inventory_purchase_orders po WHERE po.po_id = ?");
    if (!$stmt) return null;
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $po = $res ? $res->fetch_assoc() : null;

    if ($po) {
        // Fetch Vendor Info from supplier_authorizedperson or vendor_master
        $accountingDb = (isset($GLOBALS['conn']) && ($GLOBALS['conn'] instanceof mysqli)) ? $GLOBALS['conn'] : null;
        $po['VendorName'] = 'N/A';
        $po['Address'] = '';
        $po['City'] = '';
        $po['State'] = '';
        $po['Phone'] = '';
        if ($accountingDb && !empty($po['vendor_id'])) {
            $vId = $accountingDb->real_escape_string($po['vendor_id']);
            $vRes = $accountingDb->query("SELECT paytoname, mobileno FROM supplier_authorizedperson WHERE paytoid = '$vId' LIMIT 1");
            if ($vRes && $vRow = $vRes->fetch_assoc()) {
                $po['VendorName'] = $vRow['paytoname'];
                $po['Address'] = '';
                $po['Phone'] = $vRow['mobileno'];
            }
        }
        if (($po['VendorName'] === 'N/A' || empty($po['VendorName'])) && !empty($po['vendor_id'])) {
            $krfDb = (isset($GLOBALS['krfconn']) && ($GLOBALS['krfconn'] instanceof mysqli)) ? $GLOBALS['krfconn'] : $conn;
            if ($krfDb && ($krfDb instanceof mysqli)) {
                $vId = $krfDb->real_escape_string($po['vendor_id']);
                $vRes = $krfDb->query("SELECT VendorName FROM vendor_master WHERE VendorID = '$vId' LIMIT 1");
                if ($vRes && $vRow = $vRes->fetch_assoc()) {
                    $po['VendorName'] = $vRow['VendorName'];
                }
            }
        }

        // Fetch Items
        $stmtItems = $conn->prepare("SELECT poi.*, 
                                    COALESCE(i_item.item_id, i_seed.item_id, poi.seed_id) AS item_id,
                                    COALESCE(i_item.item_id, i_seed.item_id, s.SeedID, poi.seed_id) AS matched_item_id,
                                    COALESCE(i_item.seed_id, i_seed.seed_id, s.SeedID, 0) AS master_seed_id,
                                    COALESCE(i_item.item_name, i_seed.item_name, s.SeedName, 'Item') AS item_name,
                                    COALESCE(i_item.sku, i_seed.sku, s.sku, '') AS sku,
                                    COALESCE(i_item.unit, i_seed.unit, 'PKT') AS base_uom,
                                    COALESCE(u.unit, i_item.unit, i_seed.unit, 'BAG') AS purchase_uom,
                                    COALESCE(u.unit, i_item.unit, i_seed.unit, '') AS unit,
                                    COALESCE(u.unit_size, i_item.unit_size, i_seed.unit_size, '') AS unit_size,
                                    COALESCE(i_item.cgst, i_seed.cgst, 0) AS m_cgst,
                                    COALESCE(i_item.sgst, i_seed.sgst, 0) AS m_sgst,
                                    COALESCE(i_item.igst, i_seed.igst, 0) AS m_igst,
                                    COALESCE(i_item.tax_percent, i_seed.tax_percent, 0) AS m_tax_percent,
                                    COALESCE(i_item.batch_required, i_seed.batch_required, 0) AS batch_required,
                                    COALESCE(i_item.expiry_required, i_seed.expiry_required, 0) AS expiry_required,
                                    COALESCE(i_item.serial_required, i_seed.serial_required, 0) AS serial_required
                                    FROM inventory_purchase_order_items poi 
                                    LEFT JOIN inventory_purchase_item_master i_item ON (poi.seed_id = i_item.item_id)
                                    LEFT JOIN inventory_purchase_item_master i_seed ON (poi.seed_id = i_seed.seed_id AND poi.seed_id > 0 AND i_item.item_id IS NULL)
                                    LEFT JOIN seed_master s ON (poi.seed_id = s.SeedID AND i_item.item_id IS NULL AND i_seed.item_id IS NULL)
                                    LEFT JOIN inventory_purchase_item_units u ON poi.unit_id = u.item_unit_id
                                    WHERE poi.po_id = ?");
        if ($stmtItems) {
            $stmtItems->bind_param("i", $id);
            $stmtItems->execute();
            $resItems = $stmtItems->get_result();

            $po['items'] = [];
            if ($resItems) {
                while ($row = $resItems->fetch_assoc()) {
                    $row['purchase_uom'] = $row['unit'] ?? $row['base_uom'];
                    if (empty($row['uom_mode'])) {
                        $row['uom_mode'] = !empty($row['unit_id']) ? 'PURCHASE' : 'BASE';
                    }
                    // Fallback for GST if not already set in PO item record
                    if (empty($row['gst_percent']) || (float)$row['gst_percent'] == 0) {
                        $mCgst = (float)($row['m_cgst'] ?? 0);
                        $mSgst = (float)($row['m_sgst'] ?? 0);
                        $mIgst = (float)($row['m_igst'] ?? 0);
                        $mTax = (float)($row['m_tax_percent'] ?? 0);
                        if ($mIgst > 0 || $mCgst > 0 || $mSgst > 0 || $mTax > 0) {
                            $row['cgst_percent'] = $mCgst;
                            $row['sgst_percent'] = $mSgst;
                            $row['igst_percent'] = $mIgst;
                            $row['gst_percent'] = ($mIgst > 0) ? $mIgst : (($mCgst + $mSgst > 0) ? ($mCgst + $mSgst) : $mTax);
                        }
                    }
                    if (empty($row['taxable_amount']) || (float)$row['taxable_amount'] == 0) {
                        $row['taxable_amount'] = (float)$row['quantity'] * (float)$row['unit_price'];
                    }
                    if (empty($row['gst_amount']) || (float)$row['gst_amount'] == 0) {
                        $row['gst_amount'] = (float)$row['taxable_amount'] * ((float)($row['gst_percent'] ?? 0) / 100);
                    }
                    if (empty($row['total_price']) || (float)$row['total_price'] == 0) {
                        $row['total_price'] = (float)$row['taxable_amount'] + (float)$row['gst_amount'];
                    }
                    $po['items'][] = $row;
                }
            }
        } else {
            $po['items'] = [];
        }

        // Fetch Purchase Receipts / Invoice Records
        $locationMap = [];
        global $hrmconn;
        if (isset($hrmconn) && $hrmconn) {
            $lRes = $hrmconn->query("SELECT sno, LocationName FROM hrm_AttendanceLocationMaster");
            if ($lRes) {
                while ($lRow = $lRes->fetch_assoc()) {
                    $locationMap[$lRow['sno']] = $lRow['LocationName'];
                }
            }
        }

        $recRes = $conn->query("SELECT r.* FROM inventory_purchase_receipts r WHERE r.po_id = $id ORDER BY r.receipt_id DESC");
        $po['receipts'] = [];
        if ($recRes) {
            while ($rRow = $recRes->fetch_assoc()) {
                $rRow['location_name'] = $locationMap[$rRow['location_id']] ?? ('Main Location #' . $rRow['location_id']);
                $po['receipts'][] = $rRow;
            }
        }
    }

    return $po;
}

function savePurchaseOrder($conn, $data)
{
    $vendorId = $data['vendor_id'];
    $poDate = $data['po_date'];
    $expDate = $data['expected_date'];
    $items = $data['items']; // Array of {seed_id, qty, price, unit_id, gst_percent, ...}
    $totalAmount = 0;
    $taxableAmount = 0;
    $gstAmount = 0;

    // Calculate totals
    foreach ($items as $item) {
        $qty = (float)($item['qty'] ?? 0);
        $price = (float)($item['price'] ?? 0);
        $taxable = isset($item['taxable_amount']) ? (float)$item['taxable_amount'] : ($qty * $price);
        $gstPercent = isset($item['gst_percent']) ? (float)$item['gst_percent'] : 0;
        $gstAmt = isset($item['gst_amount']) ? (float)$item['gst_amount'] : ($taxable * ($gstPercent / 100));
        $lineTotal = isset($item['total_price']) ? (float)$item['total_price'] : ($taxable + $gstAmt);

        $taxableAmount += $taxable;
        $gstAmount += $gstAmt;
        $totalAmount += $lineTotal;
    }

    // Generate PO Number (e.g., PO-YYYYMMDD-XXXX)
    $year = date('Y', strtotime($poDate));
    $res = $conn->query("SELECT MAX(po_id) as max_id FROM inventory_purchase_orders");
    $next_id = ($res && $row = $res->fetch_assoc()) ? ((int)($row['max_id'] ?? 0) + 1) : 1;
    $poNumber = "PO-" . $year . "-" . str_pad($next_id, 4, '0', STR_PAD_LEFT);

    $conn->begin_transaction();

    try {
        // Insert Header
        $stmt = $conn->prepare("INSERT INTO inventory_purchase_orders (po_number, vendor_id, po_date, expected_date, taxable_amount, gst_amount, total_amount, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Draft')");
        $stmt->bind_param("ssssddd", $poNumber, $vendorId, $poDate, $expDate, $taxableAmount, $gstAmount, $totalAmount);

        if (!$stmt->execute()) {
            throw new Exception("PO Header Insert Failed: " . $stmt->error);
        }
        $poId = $stmt->insert_id;
        $stmt->close();

        // Insert Items
        $stmtItem = $conn->prepare("INSERT INTO inventory_purchase_order_items 
            (po_id, seed_id, unit_id, uom_mode, conversion_factor, quantity, unit_price, taxable_amount, gst_percent, cgst_percent, sgst_percent, igst_percent, gst_amount, total_price, received_qty) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");

        foreach ($items as $item) {
            $seedId = intval($item['seed_id']);
            $unitId = !empty($item['unit_id']) ? intval($item['unit_id']) : null;
            $uomMode = !empty($item['uom_mode']) ? $item['uom_mode'] : 'BASE';
            $convFactor = !empty($item['conversion_factor']) ? (float)$item['conversion_factor'] : 1.0;
            $qty = (float)$item['qty'];
            $price = (float)$item['price'];
            $taxable = isset($item['taxable_amount']) ? (float)$item['taxable_amount'] : ($qty * $price);
            $gstPercent = isset($item['gst_percent']) ? (float)$item['gst_percent'] : 0;
            $cgstPercent = isset($item['cgst_percent']) ? (float)$item['cgst_percent'] : 0;
            $sgstPercent = isset($item['sgst_percent']) ? (float)$item['sgst_percent'] : 0;
            $igstPercent = isset($item['igst_percent']) ? (float)$item['igst_percent'] : 0;
            $gstAmt = isset($item['gst_amount']) ? (float)$item['gst_amount'] : ($taxable * ($gstPercent / 100));
            $lineTotal = isset($item['total_price']) ? (float)$item['total_price'] : ($taxable + $gstAmt);

            $stmtItem->bind_param(
                "iiisdddddddddd", 
                $poId, $seedId, $unitId, $uomMode, $convFactor, $qty, $price, 
                $taxable, $gstPercent, $cgstPercent, $sgstPercent, $igstPercent, $gstAmt, $lineTotal
            );
            if (!$stmtItem->execute()) {
                throw new Exception("PO Item Insert Failed: " . $stmtItem->error);
            }
        }
        $stmtItem->close();

        $conn->commit();
        return ["status" => "success", "message" => "Purchase Order ($poNumber) created successfully", "po_id" => $poId];

    } catch (Exception $e) {
        $conn->rollback();
        return ["status" => "error", "message" => $e->getMessage()];
    }
}

function receivePurchaseOrder($conn, $poId, $receivedItems)
{
    // receivedItems: { item_id: qty_received, ... }

    $conn->begin_transaction();

    try {
        foreach ($receivedItems as $itemId => $qty) {
            if ($qty > 0) {
                // Update PO Item
                $stmt = $conn->prepare("UPDATE inventory_purchase_order_items SET received_qty = received_qty + ? WHERE po_item_id = ?");
                $stmt->bind_param("di", $qty, $itemId);
                $stmt->execute();

                // Get Seed ID
                $check = $conn->query("SELECT seed_id FROM inventory_purchase_order_items WHERE po_item_id = $itemId");
                $seedId = $check->fetch_assoc()['seed_id'];

                // Check Inventory
                $invCheck = $conn->query("SELECT stock_id FROM inventory_stock WHERE seed_id = $seedId");
                if ($invCheck->num_rows > 0) {
                    $conn->query("UPDATE inventory_stock SET current_quantity = current_quantity + $qty WHERE seed_id = $seedId");
                } else {
                    $conn->query("INSERT INTO inventory_stock (seed_id, current_quantity) VALUES ($seedId, $qty)");
                }

                // Log Transaction
                $stmtLog = $conn->prepare("INSERT INTO inventory_transactions (seed_id, qty_change, trans_type, reference_type, reference_id) VALUES (?, ?, 'IN', 'PO', ?)");
                $stmtLog->bind_param("idsi", $seedId, $qty, $poId);
                $stmtLog->execute();
            }
        }

        // Update PO Status to Received or Partial
        $conn->query("UPDATE inventory_purchase_orders SET status = 'Received' WHERE po_id = $poId");

        $conn->commit();
        return ["status" => "success", "message" => "Items received and inventory updated."];

    } catch (Exception $e) {
        $conn->rollback();
        return ["status" => "error", "message" => $e->getMessage()];
    }
}

function getInventory($conn)
{
    $sql = "SELECT i.*, s.SeedName, s.sku, s.BrandName 
            FROM inventory_stock i 
            JOIN seed_master s ON i.seed_id = s.SeedID 
            ORDER BY s.SeedName";
    $res = $conn->query($sql);
    $data = [];
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
    return $data;
}

?>