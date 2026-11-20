<?php
/**
 * Standalone Provisional Sales Order Cancellation & Status Auditor
 * Location: krf-orderandsales/temp/checkordercancel.php
 * Only requires connection.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require('../../connection.php');

$db = (isset($krfconn) && $krfconn instanceof mysqli) ? $krfconn : $conn;

// -------------------------------------------------------------------------
// HELPER FUNCTIONS FOR STATUS CLASSIFICATION
// -------------------------------------------------------------------------
function isPsoiCancelled($status) {
    $s = strtolower(trim((string)$status));
    return ($s === 'cancelled' || $s === 'cancel');
}

function isSoiCancelled($status) {
    $s = strtolower(trim((string)$status));
    return ($s === 'cancelled' || $s === 'cancel');
}

function isPocCancelled($status, $confirmstatus) {
    $s = strtolower(trim((string)$status));
    $c = strtolower(trim((string)$confirmstatus));
    return ($s === 'cancelled' || $s === 'cancel' || $c === 'cancelled');
}

function isRsaCancelled($status) {
    $s = strtolower(trim((string)$status));
    return ($s === 'cancel' || $s === 'cancelled');
}

// -------------------------------------------------------------------------
// CORE UPDATE LOGIC
// -------------------------------------------------------------------------
function executeStatusUpdate($db, $psono, $productId, $targetStatus, $targetTable = 'all') {
    $psono = trim($psono);
    $productId = trim($productId);
    $targetStatus = ucfirst(strtolower(trim($targetStatus))); // 'Cancel' or 'Active'

    if (empty($psono) || empty($productId)) {
        return ['success' => false, 'message' => 'Missing psono or product_id'];
    }

    $updated = [
        'psoi' => 0,
        'soi' => 0,
        'poc' => 0,
        'rsa' => 0,
        'parent_pso' => 0,
        'parent_so' => 0
    ];

    $curDate = date('Y-m-d');
    $now = date('Y-m-d H:i:s');
    $userTag = 'Admin Cancel Auditor';

    // 1. Update krf_provisional_sales_order_items
    if ($targetTable === 'all' || $targetTable === 'psoi') {
        if ($targetStatus === 'Cancel') {
            $stmt = $db->prepare("UPDATE krf_provisional_sales_order_items 
                SET status = 'Cancelled', 
                    updatedat = ?, 
                    updatedby = ?, 
                    remark = CONCAT(COALESCE(remark, ''), ' [Cancelled via Auditor]') 
                WHERE psono = ? AND CAST(product_id AS CHAR) = ?");
            $stmt->bind_param("ssss", $curDate, $userTag, $psono, $productId);
            $stmt->execute();
            $updated['psoi'] = $stmt->affected_rows;
            $stmt->close();
        } else {
            $stmt = $db->prepare("UPDATE krf_provisional_sales_order_items 
                SET status = 'Confirmed', 
                    updatedat = ?, 
                    updatedby = ?, 
                    remark = CONCAT(COALESCE(remark, ''), ' [Activated via Auditor]') 
                WHERE psono = ? AND CAST(product_id AS CHAR) = ?");
            $stmt->bind_param("ssss", $curDate, $userTag, $psono, $productId);
            $stmt->execute();
            $updated['psoi'] = $stmt->affected_rows;
            $stmt->close();
        }
    }

    // 2. Update krf_sales_order_items
    if ($targetTable === 'all' || $targetTable === 'soi') {
        if ($targetStatus === 'Cancel') {
            $stmt = $db->prepare("UPDATE krf_sales_order_items 
                SET status = 'Cancelled', 
                    updateamended = ?, 
                    ammendedby = ?, 
                    remark = CONCAT(COALESCE(remark, ''), ' [Cancelled via Auditor]') 
                WHERE psono = ? AND CAST(product_id AS CHAR) = ?");
            $stmt->bind_param("ssss", $now, $userTag, $psono, $productId);
            $stmt->execute();
            $updated['soi'] = $stmt->affected_rows;
            $stmt->close();
        } else {
            $stmt = $db->prepare("UPDATE krf_sales_order_items 
                SET status = 'Confirmed', 
                    updateamended = ?, 
                    ammendedby = ?, 
                    remark = CONCAT(COALESCE(remark, ''), ' [Activated via Auditor]') 
                WHERE psono = ? AND CAST(product_id AS CHAR) = ?");
            $stmt->bind_param("ssss", $now, $userTag, $psono, $productId);
            $stmt->execute();
            $updated['soi'] = $stmt->affected_rows;
            $stmt->close();
        }
    }

    // 3. Update krf_productionorder_consumed
    if ($targetTable === 'all' || $targetTable === 'poc') {
        if ($targetStatus === 'Cancel') {
            $stmt = $db->prepare("UPDATE krf_productionorder_consumed 
                SET status = 'Cancelled', 
                    confirmstatus = 'cancelled', 
                    updated_at = ?, 
                    remark = CONCAT(COALESCE(remark, ''), ' [Cancelled via Auditor]') 
                WHERE psono = ? AND (CAST(product_id AS CHAR) = ? OR (? = 'BOX_PACKING' AND (product_id = 0 OR stockorderno = 'BOX_PACKING')))");
            $stmt->bind_param("ssss", $now, $psono, $productId, $productId);
            $stmt->execute();
            $updated['poc'] = $stmt->affected_rows;
            $stmt->close();
        } else {
            $stmt = $db->prepare("UPDATE krf_productionorder_consumed 
                SET status = 'allocated', 
                    confirmstatus = 'Confirmed', 
                    updated_at = ?, 
                    remark = CONCAT(COALESCE(remark, ''), ' [Activated via Auditor]') 
                WHERE psono = ? AND (CAST(product_id AS CHAR) = ? OR (? = 'BOX_PACKING' AND (product_id = 0 OR stockorderno = 'BOX_PACKING')))");
            $stmt->bind_param("ssss", $now, $psono, $productId, $productId);
            $stmt->execute();
            $updated['poc'] = $stmt->affected_rows;
            $stmt->close();
        }
    }

    // 4. Update krf_readystock_allocation
    if ($targetTable === 'all' || $targetTable === 'rsa') {
        // Collect linked stock orders for this item
        $stockOrders = [];
        $soRes = $db->query("SELECT DISTINCT stockorder_no FROM krf_provisional_sales_order_items 
                             WHERE psono = '" . $db->real_escape_string($psono) . "' 
                             AND CAST(product_id AS CHAR) = '" . $db->real_escape_string($productId) . "' 
                             AND stockorder_no IS NOT NULL AND stockorder_no != ''");
        if ($soRes) {
            while ($r = $soRes->fetch_assoc()) {
                $stockOrders[] = "'" . $db->real_escape_string($r['stockorder_no']) . "'";
            }
        }
        $soClause = !empty($stockOrders) ? " OR stockorder_no IN (" . implode(',', $stockOrders) . ") OR allocate_stockorder_no IN (" . implode(',', $stockOrders) . ")" : "";

        if ($targetStatus === 'Cancel') {
            $rsaSql = "UPDATE krf_readystock_allocation 
                       SET status = 'cancel', 
                           updated_at = '" . $now . "', 
                           updated_by = '" . $db->real_escape_string($userTag) . "' 
                       WHERE (psono = '" . $db->real_escape_string($psono) . "' $soClause) 
                       AND CAST(productid AS CHAR) = '" . $db->real_escape_string($productId) . "'";
            if ($db->query($rsaSql)) {
                $updated['rsa'] = $db->affected_rows;
            }
        } else {
            $rsaSql = "UPDATE krf_readystock_allocation 
                       SET status = 'allocated', 
                           updated_at = '" . $now . "', 
                           updated_by = '" . $db->real_escape_string($userTag) . "' 
                       WHERE (psono = '" . $db->real_escape_string($psono) . "' $soClause) 
                       AND CAST(productid AS CHAR) = '" . $db->real_escape_string($productId) . "'";
            if ($db->query($rsaSql)) {
                $updated['rsa'] = $db->affected_rows;
            }
        }
    }

    // ---------------------------------------------------------------------
    // TRADER CHECK FOR SOWING & PRODUCTION STOCK ORDERS
    // Check clientid in krf_production_stockorder (or provisional order).
    // If client_master 'is_trader' = 1, DO NOT update krf_production_stockorder
    // and krf_polyhouse_sowingschedule!
    // If other than 1 (is_trader != 1), then update them!
    // ---------------------------------------------------------------------
    $isTrader = false;
    $clientidForStock = '';

    $chkClSo = $db->query("SELECT clientid FROM krf_production_stockorder 
                          WHERE psono = '" . $db->real_escape_string($psono) . "' 
                          AND clientid IS NOT NULL AND clientid != '' LIMIT 1");
    if ($chkClSo && $r = $chkClSo->fetch_assoc()) {
        $clientidForStock = trim((string)$r['clientid']);
    }

    if (empty($clientidForStock)) {
        $chkClPso = $db->query("SELECT clientid FROM krf_provisional_sales_order 
                                WHERE psono = '" . $db->real_escape_string($psono) . "' LIMIT 1");
        if ($chkClPso && $r = $chkClPso->fetch_assoc()) {
            $clientidForStock = trim((string)($r['clientid'] ?? ''));
        }
    }

    if (!empty($clientidForStock)) {
        $chkTrader = $db->query("SELECT is_trader FROM krf_client_master 
                                 WHERE client_id = '" . $db->real_escape_string($clientidForStock) . "' LIMIT 1");
        if ($chkTrader && $r = $chkTrader->fetch_assoc()) {
            $isTrader = (intval($r['is_trader']) === 1);
        }
    }

    if (!$isTrader) {
        // 5. Update krf_polyhouse_sowingschedule (if exists productid with psono)
        if ($targetTable === 'all' || $targetTable === 'sow') {
            if ($targetStatus === 'Cancel') {
                $stockOrders = [];
                $soRes = $db->query("SELECT DISTINCT stockorder_no FROM krf_provisional_sales_order_items 
                                     WHERE psono = '" . $db->real_escape_string($psono) . "' 
                                     AND CAST(product_id AS CHAR) = '" . $db->real_escape_string($productId) . "' 
                                     AND stockorder_no IS NOT NULL AND stockorder_no != ''");
                if ($soRes) {
                    while ($r = $soRes->fetch_assoc()) {
                        $stockOrders[] = "'" . $db->real_escape_string($r['stockorder_no']) . "'";
                    }
                }
                $soClause = !empty($stockOrders) ? " OR stockorder_no IN (" . implode(',', $stockOrders) . ")" : "";

                $db->query("UPDATE krf_polyhouse_sowingschedule 
                            SET status = 'removed', 
                                remarks = CONCAT(COALESCE(remarks, ''), ' [Cancelled via Auditor]'), 
                                updated_by = '" . $db->real_escape_string($userTag) . "', 
                                updated_at = '" . $now . "' 
                            WHERE (psono = '" . $db->real_escape_string($psono) . "' $soClause) 
                            AND (CAST(productid AS CHAR) = '" . $db->real_escape_string($productId) . "' OR productid IS NULL OR productid = '' OR productid = '0')");
                $updated['sow'] = $db->affected_rows;
            }
        }

        // 6. Update krf_production_stockorder (if exists productid with psono)
        if ($targetTable === 'all' || $targetTable === 'stockorder') {
            if ($targetStatus === 'Cancel') {
                $db->query("UPDATE krf_production_stockorder 
                            SET status = 'Cancelled', 
                                workingstatus = 'Cancelled', 
                                editedremark = CONCAT(COALESCE(editedremark, ''), ' [Cancelled via Auditor]'), 
                                editedby = '" . $db->real_escape_string($userTag) . "', 
                                editedon = '" . $curDate . "', 
                                updated_at = '" . $now . "' 
                            WHERE psono = '" . $db->real_escape_string($psono) . "' 
                            AND (CAST(productid AS CHAR) = '" . $db->real_escape_string($productId) . "' OR productid IS NULL OR productid = '' OR productid = '0') 
                            AND status != 'Cancelled'");
                $updated['stockorder'] = $db->affected_rows;
            }
        }
    } else {
        $updated['skipped_trader_production'] = true;
    }

    // 7. Parent Order Sync
    if ($targetStatus === 'Cancel') {
        $checkActive = $db->query("SELECT COUNT(*) as active_cnt FROM krf_provisional_sales_order_items 
                                   WHERE psono = '" . $db->real_escape_string($psono) . "' 
                                   AND (status IS NULL OR LOWER(status) NOT IN ('cancelled', 'cancel'))");
        $actRow = $checkActive ? $checkActive->fetch_assoc() : null;
        if ($actRow && intval($actRow['active_cnt']) === 0) {
            $db->query("UPDATE krf_provisional_sales_order SET status = 'Cancelled', updated_at = NOW() 
                        WHERE psono = '" . $db->real_escape_string($psono) . "' AND status != 'Cancelled'");
            $updated['parent_pso'] = $db->affected_rows;

            $db->query("UPDATE krf_sales_order SET status = 'Cancelled', updated_at = NOW() 
                        WHERE psono = '" . $db->real_escape_string($psono) . "' AND status != 'Cancelled'");
            $updated['parent_so'] = $db->affected_rows;
        }
    } else {
        $db->query("UPDATE krf_provisional_sales_order SET status = 'Confirmed', updated_at = NOW() 
                    WHERE psono = '" . $db->real_escape_string($psono) . "' AND status = 'Cancelled'");
        $updated['parent_pso'] = $db->affected_rows;

        $db->query("UPDATE krf_sales_order SET status = 'Confirmed', updated_at = NOW() 
                    WHERE psono = '" . $db->real_escape_string($psono) . "' AND status = 'Cancelled'");
        $updated['parent_so'] = $db->affected_rows;
    }

    return [
        'success' => true,
        'message' => "Order {$psono} Product #{$productId} status updated to {$targetStatus}!",
        'updated' => $updated
    ];
}

// -------------------------------------------------------------------------
// INSERT MISSING SALES ORDER ITEM AS PER PROVISIONAL ITEMS
// -------------------------------------------------------------------------
function getOrCreateSalesOrder($db, $psono, $targetStatus = 'Confirmed') {
    // 1. Check if sales order already exists
    $st = $db->prepare("SELECT sono, id, status FROM krf_sales_order WHERE psono = ? LIMIT 1");
    $st->bind_param("s", $psono);
    $st->execute();
    $res = $st->get_result();
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $st->close();
        return $row['sono'];
    }
    $st->close();

    // 2. Check if sono is already recorded in krf_productionorder_consumed or krf_sales_order_items
    $chkPoc = $db->query("SELECT sono FROM krf_productionorder_consumed WHERE psono = '" . $db->real_escape_string($psono) . "' AND sono IS NOT NULL AND sono != '' LIMIT 1");
    if ($chkPoc && $r = $chkPoc->fetch_assoc()) {
        $sono = $r['sono'];
    } else {
        // Generate a unique sono (KF-CS-XXXX)
        do {
            $sono = 'KF-CS-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $chk = $db->query("SELECT id FROM krf_sales_order WHERE sono = '$sono'");
        } while ($chk && $chk->num_rows > 0);
    }

    // 3. Fetch provisional sales order details for header
    $pStmt = $db->prepare("SELECT * FROM krf_provisional_sales_order WHERE psono = ? LIMIT 1");
    $pStmt->bind_param("s", $psono);
    $pStmt->execute();
    $pRes = $pStmt->get_result();
    $pso = ($pRes && $pRes->num_rows > 0) ? $pRes->fetch_assoc() : [];
    $pStmt->close();

    $headerStatus = (strtolower((string)$targetStatus) === 'cancelled' || strtolower((string)$targetStatus) === 'cancel') ? 'Cancelled' : 'Confirmed';

    $companyid = intval($pso['companyid'] ?? 0);
    $subcompanyid = intval($pso['subcompanyid'] ?? 0);
    $projectid = intval($pso['projectid'] ?? 0);
    $clientid = (string)($pso['clientid'] ?? '');
    $clientname = (string)($pso['clientname'] ?? '');
    $emailid = (string)($pso['emailid'] ?? '');
    $address = (string)($pso['address'] ?? '');
    $delivery_address = (string)($pso['delivery_address'] ?? '');
    $districtname = (string)($pso['districtname'] ?? '');
    $statename = (string)($pso['statename'] ?? '');
    $mobileno = (string)($pso['mobileno'] ?? '');
    $payment_terms = (string)($pso['payment_terms'] ?? '');
    $remarks = (string)($pso['remarks'] ?? 'Generated from Provisional Order Sync');
    $createdBy = (string)($pso['created_by'] ?? 'Admin Audit');

    $ins = $db->prepare("
        INSERT INTO krf_sales_order (
            sono, psono, companyid, subcompanyid, projectid, clientid, clientname,
            emailid, address, delivery_address, districtname, statename, mobileno,
            payment_terms, remarks, total_amount, grand_total, status, created_by, created_at, updated_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, 0.00, 0.00, ?, ?, NOW(), NOW()
        )
    ");
    if ($ins) {
        $ins->bind_param(
            "ssiiissssssssssss",
            $sono,
            $psono,
            $companyid,
            $subcompanyid,
            $projectid,
            $clientid,
            $clientname,
            $emailid,
            $address,
            $delivery_address,
            $districtname,
            $statename,
            $mobileno,
            $payment_terms,
            $remarks,
            $headerStatus,
            $createdBy
        );
        $ins->execute();
        $ins->close();
    }

    return $sono;
}

function insertMissingSalesOrderItem($db, $psoi_id, $psono = '', $productId = '', $statusOverride = '') {
    // 1. Fetch provisional item
    if (!empty($psoi_id)) {
        $stmt = $db->prepare("SELECT * FROM krf_provisional_sales_order_items WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $psoi_id);
    } else {
        $stmt = $db->prepare("SELECT * FROM krf_provisional_sales_order_items WHERE psono = ? AND CAST(product_id AS CHAR) = ? LIMIT 1");
        $stmt->bind_param("ss", $psono, $productId);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res || $res->num_rows === 0) {
        $stmt->close();
        return ['success' => false, 'message' => "Provisional item not found."];
    }
    $psoi = $res->fetch_assoc();
    $stmt->close();

    $psono = trim((string)$psoi['psono']);
    $productId = trim((string)$psoi['product_id']);

    // Check if item already exists in krf_sales_order_items
    $chk = $db->prepare("SELECT id FROM krf_sales_order_items WHERE psono = ? AND CAST(product_id AS CHAR) = ? LIMIT 1");
    $chk->bind_param("ss", $psono, $productId);
    $chk->execute();
    $chkRes = $chk->get_result();
    if ($chkRes && $chkRes->num_rows > 0) {
        $chk->close();
        return ['success' => false, 'message' => "Sales item already exists for {$psono} Product #{$productId}."];
    }
    $chk->close();

    // Determine status as per Provisional Items
    if (!empty($statusOverride)) {
        $targetStatus = $statusOverride;
    } else {
        $targetStatus = isPsoiCancelled($psoi['status']) ? 'Cancelled' : (!empty($psoi['status']) && $psoi['status'] != 'Draft' ? $psoi['status'] : 'Confirmed');
    }

    // Ensure Sales Order Header exists
    $sono = getOrCreateSalesOrder($db, $psono, $targetStatus);

    // Prepare fields
    $cols = [
        'sono' => $sono,
        'psono' => $psono,
        'projectid' => intval($psoi['projectid'] ?? 0),
        'clientid' => (string)($psoi['clientid'] ?? ''),
        'product_sku' => (string)($psoi['product_sku'] ?? ''),
        'product_id' => $productId,
        'product_name' => (string)($psoi['product_name'] ?? ''),
        'quantity' => intval($psoi['quantity'] ?? 0),
        'rate' => floatval($psoi['rate'] ?? 0),
        'taxableamount' => floatval($psoi['taxableamount'] ?? 0),
        'cgst_percent' => floatval($psoi['cgst_percent'] ?? 0),
        'cgst_amount' => floatval($psoi['cgst_amount'] ?? 0),
        'sgst_percent' => floatval($psoi['sgst_percent'] ?? 0),
        'sgst_amount' => floatval($psoi['sgst_amount'] ?? 0),
        'igst_percent' => floatval($psoi['igst_percent'] ?? 0),
        'igst_amount' => floatval($psoi['igst_amount'] ?? 0),
        'discount_percent' => floatval($psoi['discount_percent'] ?? 0),
        'discount_amount' => floatval($psoi['discount_amount'] ?? 0),
        'freight_charges' => floatval($psoi['freight_charges'] ?? 0),
        'amount' => floatval($psoi['amount'] ?? 0),
        'roundoffamount' => floatval($psoi['roundoffamount'] ?? 0),
        'deliverydate' => !empty($psoi['expecteddeliverydate']) ? $psoi['expecteddeliverydate'] : date('Y-m-d'),
        'created_by' => (string)($psoi['created_by'] ?? 'Admin Audit'),
        'status' => $targetStatus,
        'stockorder_no' => (string)($psoi['stockorder_no'] ?? ''),
        'shiftingqty' => intval(ceil(intval($psoi['quantity'] ?? 0) / 125)),
        'remark' => !empty($psoi['remark']) ? $psoi['remark'] : 'Inserted via Cancel Auditor as per Provisional Items'
    ];

    $typeStr = "ssissssidddddddddddddssssis";
    $colNames = implode(", ", array_keys($cols));
    $placeholders = implode(", ", array_fill(0, count($cols), "?"));

    $sql = "INSERT INTO krf_sales_order_items ($colNames, tax_amount, isshifted, updateamended) VALUES ($placeholders, 0.00, 'yes', NOW())";
    $ins = $db->prepare($sql);
    if (!$ins) {
        return ['success' => false, 'message' => "Prepare failed: " . $db->error];
    }

    $ins->bind_param($typeStr, ...array_values($cols));
    if ($ins->execute()) {
        $newId = $ins->insert_id;
        $ins->close();

        // Recalculate sales order header totals
        $totRes = $db->query("SELECT IFNULL(SUM(amount), 0) as tot FROM krf_sales_order_items WHERE sono = '" . $db->real_escape_string($sono) . "'");
        $totRow = $totRes ? $totRes->fetch_assoc() : null;
        $totalAmount = floatval($totRow['tot'] ?? 0);
        $db->query("UPDATE krf_sales_order SET total_amount = $totalAmount, grand_total = $totalAmount, updated_at = NOW() WHERE sono = '" . $db->real_escape_string($sono) . "'");

        return [
            'success' => true,
            'message' => "Inserted into Sales Items for {$psono} Product #{$productId} with status '{$targetStatus}' (SO: {$sono})!",
            'new_soi_id' => $newId,
            'sono' => $sono,
            'status' => $targetStatus
        ];
    } else {
        $err = $ins->error;
        $ins->close();
        return ['success' => false, 'message' => "Insert failed: " . $err];
    }
}

// -------------------------------------------------------------------------
// INSERT MISSING PRODUCTION ORDER CONSUMED (POC) AS PER PROVISIONAL ITEMS
// -------------------------------------------------------------------------
function insertMissingProductionOrderConsumed($db, $psoi_id, $psono = '', $productId = '', $statusOverride = '') {
    // 1. Fetch provisional item
    if (!empty($psoi_id)) {
        $stmt = $db->prepare("SELECT * FROM krf_provisional_sales_order_items WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $psoi_id);
    } else {
        $stmt = $db->prepare("SELECT * FROM krf_provisional_sales_order_items WHERE psono = ? AND CAST(product_id AS CHAR) = ? LIMIT 1");
        $stmt->bind_param("ss", $psono, $productId);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res || $res->num_rows === 0) {
        $stmt->close();
        return ['success' => false, 'message' => "Provisional item not found."];
    }
    $psoi = $res->fetch_assoc();
    $stmt->close();

    $psono = trim((string)$psoi['psono']);
    $productId = trim((string)$psoi['product_id']);
    $isBoxPacking = ($productId === 'BOX_PACKING' || stripos($psoi['product_name'] ?? '', 'Box Packing') !== false);

    // Check if item already exists in krf_productionorder_consumed
    if ($isBoxPacking) {
        $chk = $db->prepare("SELECT id FROM krf_productionorder_consumed WHERE psono = ? AND (CAST(product_id AS CHAR) = ? OR product_id = 0 OR stockorderno = 'BOX_PACKING') LIMIT 1");
        $chk->bind_param("ss", $psono, $productId);
    } else {
        $chk = $db->prepare("SELECT id FROM krf_productionorder_consumed WHERE psono = ? AND CAST(product_id AS CHAR) = ? LIMIT 1");
        $chk->bind_param("ss", $psono, $productId);
    }
    $chk->execute();
    $chkRes = $chk->get_result();
    if ($chkRes && $chkRes->num_rows > 0) {
        $chk->close();
        return ['success' => false, 'message' => "Consumed stock entry already exists for {$psono} Product #{$productId}."];
    }
    $chk->close();

    // Determine status as per Provisional Items
    $isCancel = false;
    if (!empty($statusOverride)) {
        $isCancel = (strtolower((string)$statusOverride) === 'cancelled' || strtolower((string)$statusOverride) === 'cancel');
    } else {
        $isCancel = isPsoiCancelled($psoi['status']);
    }

    $confirmstatus = $isCancel ? 'cancelled' : 'Confirmed';
    $status = $isCancel ? 'Cancelled' : 'pending';

    // Find sono if available from krf_sales_order or krf_sales_order_items
    $sono = '';
    $chkSo = $db->query("SELECT sono FROM krf_sales_order WHERE psono = '" . $db->real_escape_string($psono) . "' LIMIT 1");
    if ($chkSo && $r = $chkSo->fetch_assoc()) {
        $sono = $r['sono'];
    } else {
        $chkSoi = $db->query("SELECT sono FROM krf_sales_order_items WHERE psono = '" . $db->real_escape_string($psono) . "' AND sono IS NOT NULL AND sono != '' LIMIT 1");
        if ($chkSoi && $r = $chkSoi->fetch_assoc()) {
            $sono = $r['sono'];
        }
    }

    $stockorderno = (string)($psoi['stockorder_no'] ?? '');
    if ($isBoxPacking && empty($stockorderno)) {
        $stockorderno = 'BOX_PACKING';
    }
    $intProductId = intval($psoi['product_id'] ?? 0);
    $product_name = (string)($psoi['product_name'] ?? '');
    $consumed_qty = floatval($psoi['quantity'] ?? 0);
    $original_orderqty = intval($psoi['quantity'] ?? 0);
    $projectid = intval($psoi['projectid'] ?? 0);
    $created_by = (string)($psoi['created_by'] ?? 'Admin Audit');
    $remark = 'Inserted via Cancel Auditor as per Provisional Items';

    $ins = $db->prepare("
        INSERT INTO krf_productionorder_consumed (
            psono, sono, stockorderno, original_stockorder_no, product_id, product_name,
            consumed_qty, original_orderqty, wastage_qty, projectid, confirmstatus, status,
            remark, created_by, created_at, updated_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?,
            ?, ?, 0.000, ?, ?, ?,
            ?, ?, NOW(), NOW()
        )
    ");

    if (!$ins) {
        return ['success' => false, 'message' => "Prepare failed: " . $db->error];
    }

    $typeStr = "ssssisdiissss";
    $ins->bind_param(
        $typeStr,
        $psono,
        $sono,
        $stockorderno,
        $stockorderno,
        $intProductId,
        $product_name,
        $consumed_qty,
        $original_orderqty,
        $projectid,
        $confirmstatus,
        $status,
        $remark,
        $created_by
    );

    if ($ins->execute()) {
        $newId = $ins->insert_id;
        $ins->close();

        return [
            'success' => true,
            'message' => "Inserted into Consumed Stock (POC) for {$psono} Product #{$productId} with status '{$status}' / confirm: '{$confirmstatus}'!",
            'new_poc_id' => $newId,
            'status' => $status,
            'confirmstatus' => $confirmstatus
        ];
    } else {
        $err = $ins->error;
        $ins->close();
        return ['success' => false, 'message' => "Insert failed: " . $err];
    }
}

// -------------------------------------------------------------------------
// AJAX / POST REQUEST HANDLERS
// -------------------------------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_POST['action'];

    // 1. Single Item Update
    if ($action === 'update_single') {
        $psono = $_POST['psono'] ?? '';
        $productId = $_POST['product_id'] ?? '';
        $targetStatus = $_POST['target_status'] ?? 'Cancel'; // 'Cancel' or 'Active'
        $targetTable = $_POST['target_table'] ?? 'all';     // 'all', 'psoi', 'soi', 'poc', 'rsa'

        $res = executeStatusUpdate($db, $psono, $productId, $targetStatus, $targetTable);
        echo json_encode($res);
        exit;
    }

    // 2. Bulk Selection Update
    if ($action === 'update_bulk') {
        $itemsJson = $_POST['items'] ?? '[]';
        $targetStatus = $_POST['target_status'] ?? 'Cancel';
        $items = json_decode($itemsJson, true);

        if (!is_array($items) || empty($items)) {
            echo json_encode(['success' => false, 'message' => 'No items selected for bulk update.']);
            exit;
        }

        $totalSuccess = 0;
        $errors = [];
        foreach ($items as $it) {
            $p = $it['psono'] ?? '';
            $pid = $it['product_id'] ?? '';
            $tbl = $it['target_table'] ?? 'all';
            $r = executeStatusUpdate($db, $p, $pid, $targetStatus, $tbl);
            if (!empty($r['success'])) {
                $totalSuccess++;
            } else {
                $errors[] = "$p ($pid): " . ($r['message'] ?? 'Error');
            }
        }

        echo json_encode([
            'success' => true,
            'updated_count' => $totalSuccess,
            'errors' => $errors,
            'message' => "Successfully updated $totalSuccess item(s) to {$targetStatus}!"
        ]);
        exit;
    }

    // 3. Sync All Mismatches
    if ($action === 'sync_all_mismatches') {
        $targetStatus = $_POST['target_status'] ?? 'Cancel'; // 'Cancel' or 'Active'
        $mismatchItemsJson = $_POST['mismatch_items'] ?? '[]';
        $items = json_decode($mismatchItemsJson, true);

        if (!is_array($items) || empty($items)) {
            echo json_encode(['success' => false, 'message' => 'No mismatched items provided for sync.']);
            exit;
        }

        $successCount = 0;
        foreach ($items as $it) {
            $p = $it['psono'] ?? '';
            $pid = $it['product_id'] ?? '';
            $r = executeStatusUpdate($db, $p, $pid, $targetStatus, 'all');
            if (!empty($r['success'])) {
                $successCount++;
            }
        }

        echo json_encode([
            'success' => true,
            'updated_count' => $successCount,
            'message' => "Successfully synchronized $successCount mismatched item(s) to {$targetStatus}!"
        ]);
        exit;
    }

    // 4. Insert Missing Sales Order Item
    if ($action === 'insert_missing_soi') {
        $psoi_id = intval($_POST['psoi_id'] ?? 0);
        $psono = $_POST['psono'] ?? '';
        $productId = $_POST['product_id'] ?? '';
        $targetStatus = $_POST['target_status'] ?? ''; // if empty, auto-detects from PSOI

        $res = insertMissingSalesOrderItem($db, $psoi_id, $psono, $productId, $targetStatus);
        echo json_encode($res);
        exit;
    }

    // 5. Bulk Insert Missing Sales Order Items
    if ($action === 'bulk_insert_missing_soi') {
        $itemsJson = $_POST['items'] ?? '[]';
        $items = json_decode($itemsJson, true);

        if (!is_array($items) || empty($items)) {
            echo json_encode(['success' => false, 'message' => 'No items provided for bulk insert.']);
            exit;
        }

        $totalSuccess = 0;
        $errors = [];
        foreach ($items as $it) {
            $psoi_id = intval($it['psoi_id'] ?? 0);
            $psono = $it['psono'] ?? '';
            $productId = $it['product_id'] ?? '';
            $statusOverride = $it['target_status'] ?? '';

            $r = insertMissingSalesOrderItem($db, $psoi_id, $psono, $productId, $statusOverride);
            if (!empty($r['success'])) {
                $totalSuccess++;
            } else {
                $errors[] = "$psono (#$productId): " . ($r['message'] ?? 'Error');
            }
        }

        echo json_encode([
            'success' => true,
            'total_success' => $totalSuccess,
            'errors' => $errors,
            'message' => "Successfully inserted $totalSuccess missing item(s) into Sales Items!"
        ]);
        exit;
    }

    // 6. Insert Missing Production Order Consumed (POC)
    if ($action === 'insert_missing_poc') {
        $psoi_id = intval($_POST['psoi_id'] ?? 0);
        $psono = $_POST['psono'] ?? '';
        $productId = $_POST['product_id'] ?? '';
        $targetStatus = $_POST['target_status'] ?? '';

        $res = insertMissingProductionOrderConsumed($db, $psoi_id, $psono, $productId, $targetStatus);
        echo json_encode($res);
        exit;
    }

    // 7. Bulk Insert Missing Production Order Consumed (POC)
    if ($action === 'bulk_insert_missing_poc') {
        $itemsJson = $_POST['items'] ?? '[]';
        $items = json_decode($itemsJson, true);

        if (!is_array($items) || empty($items)) {
            echo json_encode(['success' => false, 'message' => 'No items provided for bulk insert.']);
            exit;
        }

        $totalSuccess = 0;
        $errors = [];
        foreach ($items as $it) {
            $psoi_id = intval($it['psoi_id'] ?? 0);
            $psono = $it['psono'] ?? '';
            $productId = $it['product_id'] ?? '';
            $statusOverride = $it['target_status'] ?? '';

            $r = insertMissingProductionOrderConsumed($db, $psoi_id, $psono, $productId, $statusOverride);
            if (!empty($r['success'])) {
                $totalSuccess++;
            } else {
                $errors[] = "$psono (#$productId): " . ($r['message'] ?? 'Error');
            }
        }

        echo json_encode([
            'success' => true,
            'total_success' => $totalSuccess,
            'errors' => $errors,
            'message' => "Successfully inserted $totalSuccess missing item(s) into Consumed Stock (POC)!"
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action request.']);
    exit;
}

// -------------------------------------------------------------------------
// DATA AUDITING & RETRIEVAL ENGINE
// -------------------------------------------------------------------------
$timeStart = microtime(true);

// 1. Fetch parent orders metadata (Client Name, Order Date, Status)
$parentOrders = [];
$res = $db->query("SELECT psono, clientid, clientname, created_at, status FROM krf_provisional_sales_order WHERE psono IS NOT NULL AND psono != ''");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $parentOrders[trim($r['psono'])] = $r;
    }
}

// Fetch Trader clients from krf_client_master where is_trader = 1
$traderClients = [];
$trRes = $db->query("SELECT client_id FROM krf_client_master WHERE is_trader = 1");
if ($trRes) {
    while ($r = $trRes->fetch_assoc()) {
        if (!empty($r['client_id'])) {
            $traderClients[trim((string)$r['client_id'])] = true;
        }
    }
}

// Fetch clientid from krf_production_stockorder if present
$stockorderClients = [];
$soClRes = $db->query("SELECT DISTINCT psono, clientid FROM krf_production_stockorder WHERE psono IS NOT NULL AND psono != '' AND clientid IS NOT NULL AND clientid != ''");
if ($soClRes) {
    while ($r = $soClRes->fetch_assoc()) {
        $stockorderClients[trim((string)$r['psono'])] = trim((string)$r['clientid']);
    }
}

// 2. Fetch all PSO Items (Source of Truth)
$psoiList = [];
$psoiKeys = [];
$res = $db->query("SELECT id, psono, stockorder_no, product_id, product_name, quantity, status, tedit, remark, updatedat, updatedby 
                   FROM krf_provisional_sales_order_items 
                   WHERE psono IS NOT NULL AND psono != '' 
                   ORDER BY id DESC");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $psoiList[] = $r;
        $k = trim($r['psono']) . '___' . trim((string)$r['product_id']);
        $psoiKeys[$k] = true;
    }
}

// 3. Fetch all SOI Items
$soiByPsoProd = [];
$res = $db->query("SELECT id, sono, psono, stockorder_no, product_id, product_name, quantity, status, actionstatus, invoiceno, dispatchedqty, invoiceqty, remark 
                   FROM krf_sales_order_items 
                   WHERE psono IS NOT NULL AND psono != ''");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $k = trim($r['psono']) . '___' . trim((string)$r['product_id']);
        $soiByPsoProd[$k][] = $r;
    }
}

// 4. Fetch all POC Items
$pocByPsoProd = [];
$res = $db->query("SELECT id, sono, psono, stockorderno, original_stockorder_no, allocated_stockorderno, product_id, product_name, consumed_qty, dispatchedqty, status, confirmstatus, remark 
                   FROM krf_productionorder_consumed 
                   WHERE psono IS NOT NULL AND psono != ''");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $psonoTrim = trim($r['psono']);
        $pidTrim = trim((string)$r['product_id']);
        $k = $psonoTrim . '___' . $pidTrim;
        $pocByPsoProd[$k][] = $r;

        // Smart alias for Box Packing / non-plant packing charges
        if ($pidTrim === '0' || $r['stockorderno'] === 'BOX_PACKING' || stripos($r['product_name'] ?? '', 'Box Packing') !== false) {
            $pocByPsoProd[$psonoTrim . '___BOX_PACKING'][] = $r;
        }
    }
}

// 5. Fetch all RSA Items
$rsaByPsoProd = [];
$rsaByStockOrder = [];
$res = $db->query("SELECT id, psono, stockorder_no, allocate_stockorder_no, productid, productname, allocate_ordqty, dispatchqty, status, polyhouse, allocatedbed 
                   FROM krf_readystock_allocation");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        if (!empty($r['psono'])) {
            $k = trim($r['psono']) . '___' . trim((string)$r['productid']);
            $rsaByPsoProd[$k][] = $r;
        }
        if (!empty($r['stockorder_no'])) {
            $rsaByStockOrder[trim($r['stockorder_no'])][] = $r;
        }
        if (!empty($r['allocate_stockorder_no'])) {
            $rsaByStockOrder[trim($r['allocate_stockorder_no'])][] = $r;
        }
    }
}

// -------------------------------------------------------------------------
// COMPILE AUDIT DATA & DETECT MISMATCHES
// -------------------------------------------------------------------------
$auditItems = [];
$totalItems = 0;
$totalMismatches = 0;
$cntPsoiCancelChildActive = 0;
$cntPsoiActiveChildCancel = 0;
$cntAllCancel = 0;
$cntAllActive = 0;
$cntMissingSoi = 0;
$cntMissingPoc = 0;
$cntParentCancelTotal = 0;
$cntParentCancelChildActive = 0;

$mismatchListForGlobalSync = [];
$parentCancelMismatchList = [];

foreach ($psoiList as $psoi) {
    $totalItems++;
    $psono = trim($psoi['psono']);
    $pid = trim((string)$psoi['product_id']);
    $key = $psono . '___' . $pid;

    // Parent Order Status Check (Column "Order & Client")
    $parentStatus = $parentOrders[$psono]['status'] ?? 'Draft';
    $isParentCancel = (strtolower(trim((string)$parentStatus)) === 'cancelled' || strtolower(trim((string)$parentStatus)) === 'cancel');

    // Trader Check
    $orderClientId = $stockorderClients[$psono] ?? ($parentOrders[$psono]['clientid'] ?? '');
    $isTraderOrder = isset($traderClients[$orderClientId]);

    // PSOI Status
    $psoiCancel = isPsoiCancelled($psoi['status']);
    $psoiState = $psoiCancel ? 'Cancel' : 'Active';

    // SOI Status
    $soiList = $soiByPsoProd[$key] ?? [];
    $isMissingSoi = empty($soiList);
    if ($isMissingSoi) {
        $cntMissingSoi++;
    }
    $soiState = 'N/A';
    if (!empty($soiList)) {
        $hasCancel = false;
        $hasActive = false;
        foreach ($soiList as $s) {
            if (isSoiCancelled($s['status'])) {
                $hasCancel = true;
            } else {
                $hasActive = true;
            }
        }
        $soiState = ($hasCancel && $hasActive) ? 'Mixed' : ($hasCancel ? 'Cancel' : 'Active');
    }

    // POC Status
    $pocList = $pocByPsoProd[$key] ?? [];
    if (empty($pocList)) {
        if ($pid === 'BOX_PACKING' || stripos($psoi['product_name'] ?? '', 'Box Packing') !== false) {
            $pocList = $pocByPsoProd[$psono . '___BOX_PACKING'] ?? ($pocByPsoProd[$psono . '___0'] ?? []);
        }
    }
    $isMissingPoc = empty($pocList);
    if ($isMissingPoc) {
        $cntMissingPoc++;
    }
    $pocState = 'N/A';
    if (!empty($pocList)) {
        $hasCancel = false;
        $hasActive = false;
        foreach ($pocList as $c) {
            if (isPocCancelled($c['status'], $c['confirmstatus'])) {
                $hasCancel = true;
            } else {
                $hasActive = true;
            }
        }
        $pocState = ($hasCancel && $hasActive) ? 'Mixed' : ($hasCancel ? 'Cancel' : 'Active');
    }

    // RSA Status
    $rsaList = $rsaByPsoProd[$key] ?? [];
    if (!empty($psoi['stockorder_no']) && isset($rsaByStockOrder[trim($psoi['stockorder_no'])])) {
        foreach ($rsaByStockOrder[trim($psoi['stockorder_no'])] as $rsaItem) {
            if (!in_array($rsaItem['id'], array_column($rsaList, 'id')) && (empty($rsaItem['productid']) || strval($rsaItem['productid']) === strval($pid))) {
                $rsaList[] = $rsaItem;
            }
        }
    }
    $rsaState = 'N/A';
    if (!empty($rsaList)) {
        $hasCancel = false;
        $hasActive = false;
        foreach ($rsaList as $r) {
            if (isRsaCancelled($r['status'])) {
                $hasCancel = true;
            } else {
                $hasActive = true;
            }
        }
        $rsaState = ($hasCancel && $hasActive) ? 'Mixed' : ($hasCancel ? 'Cancel' : 'Active');
    }

    // Check if all present tables are cancelled
    $areChildrenAllCancelled = ($psoiState === 'Cancel' 
        && ($soiState === 'Cancel' || $soiState === 'N/A') 
        && ($pocState === 'Cancel' || $pocState === 'N/A') 
        && ($rsaState === 'Cancel' || $rsaState === 'N/A'));

    // Parent Order Cancelled, but items/children still active
    $isParentCancelChildActive = ($isParentCancel && !$areChildrenAllCancelled);

    if ($isParentCancel) {
        $cntParentCancelTotal++;
    }
    if ($isParentCancelChildActive) {
        $cntParentCancelChildActive++;
        $parentCancelMismatchList[] = [
            'psono' => $psono,
            'product_id' => $pid
        ];
    }

    // Collect all present states
    $presentStates = ['psoi' => $psoiState];
    if ($soiState !== 'N/A') $presentStates['soi'] = $soiState;
    if ($pocState !== 'N/A') $presentStates['poc'] = $pocState;
    if ($rsaState !== 'N/A') $presentStates['rsa'] = $rsaState;

    $distinctStates = array_unique(array_values($presentStates));
    $isItemMismatch = (count($distinctStates) > 1 || in_array('Mixed', $distinctStates));
    $isMismatch = ($isItemMismatch || $isParentCancelChildActive);

    $mismatchType = 'consistent';
    if ($isMismatch) {
        $totalMismatches++;
        $mismatchListForGlobalSync[] = [
            'psono' => $psono,
            'product_id' => $pid
        ];

        if ($isParentCancelChildActive) {
            $mismatchType = 'parent_cancel_child_active';
        } else if ($psoiState === 'Cancel') {
            $cntPsoiCancelChildActive++;
            $mismatchType = 'psoi_cancel_child_active';
        } else {
            $cntPsoiActiveChildCancel++;
            $mismatchType = 'psoi_active_child_cancel';
        }
    } else {
        if ($psoiState === 'Cancel') {
            $cntAllCancel++;
            $mismatchType = 'all_cancel';
        } else {
            $cntAllActive++;
            $mismatchType = 'all_active';
        }
    }

    $auditItems[] = [
        'psono' => $psono,
        'product_id' => $pid,
        'product_name' => $psoi['product_name'] ?? 'N/A',
        'stockorder_no' => $psoi['stockorder_no'] ?? '',
        'quantity' => $psoi['quantity'] ?? 0,
        'psoi_id' => $psoi['id'],
        'psoi_status_raw' => $psoi['status'] ?? '',
        'psoi_state' => $psoiState,
        'psoi_remark' => $psoi['remark'] ?? '',
        'psoi_tedit' => $psoi['tedit'] ?? '',
        'psoi_updatedat' => $psoi['updatedat'] ?? '',

        'soi_list' => $soiList,
        'soi_state' => $soiState,

        'poc_list' => $pocList,
        'poc_state' => $pocState,

        'rsa_list' => $rsaList,
        'rsa_state' => $rsaState,

        'is_mismatch' => $isMismatch,
        'is_item_mismatch' => $isItemMismatch,
        'is_missing_soi' => $isMissingSoi,
        'is_missing_poc' => $isMissingPoc,
        'is_parent_cancel' => $isParentCancel,
        'is_parent_cancel_child_active' => $isParentCancelChildActive,
        'mismatch_type' => $mismatchType,
        'clientid' => $orderClientId,
        'is_trader' => $isTraderOrder,
        'clientname' => $parentOrders[$psono]['clientname'] ?? 'N/A',
        'created_at' => $parentOrders[$psono]['created_at'] ?? '',
        'parent_status' => $parentStatus
    ];
}

$elapsedTime = round(microtime(true) - $timeStart, 3);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Provisional Order Cancellation Status Auditor & Sync</title>
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --bg-color: #0b1120;
            --panel-bg: #151f32;
            --panel-border: #24344d;
            --table-header: #0f172a;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --primary: #3b82f6;
            --primary-hover: #2563eb;
            --success: #10b981;
            --success-bg: rgba(16, 185, 129, 0.15);
            --danger: #ef4444;
            --danger-bg: rgba(239, 68, 68, 0.15);
            --warning: #f59e0b;
            --warning-bg: rgba(245, 158, 11, 0.15);
            --info: #06b6d4;
            --info-bg: rgba(6, 182, 212, 0.15);
            --card-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4), 0 8px 10px -6px rgba(0, 0, 0, 0.3);
            --transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            min-height: 100vh;
            padding: 24px 24px 60px;
        }

        .container {
            max-width: 1750px;
            margin: 0 auto;
        }

        /* Top Header */
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            padding-bottom: 18px;
            border-bottom: 1px solid var(--panel-border);
            flex-wrap: wrap;
            gap: 16px;
        }

        .header-title-box {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .header-icon {
            width: 52px;
            height: 52px;
            background: linear-gradient(135deg, #ef4444, #f59e0b);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #ffffff;
            box-shadow: 0 4px 18px rgba(239, 68, 68, 0.35);
        }

        .header-title {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: #ffffff;
        }

        .header-subtitle {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 600;
            padding: 9px 16px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            line-height: 1.2;
        }

        .btn-primary {
            background: var(--primary);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        .btn-danger {
            background: #dc2626;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }
        .btn-danger:hover {
            background: #b91c1c;
            transform: translateY(-1px);
        }

        .btn-success {
            background: #059669;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
        }
        .btn-success:hover {
            background: #047857;
            transform: translateY(-1px);
        }

        .btn-outline {
            background: transparent;
            color: var(--text-main);
            border: 1px solid var(--panel-border);
        }
        .btn-outline:hover {
            background: var(--panel-bg);
            border-color: #475569;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 11.5px;
            border-radius: 6px;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--panel-bg);
            border: 1px solid var(--panel-border);
            border-radius: 12px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            cursor: pointer;
        }
        .stat-card:hover {
            border-color: #475569;
            transform: translateY(-2px);
        }

        .stat-info h4 {
            font-size: 11.5px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .stat-info .stat-value {
            font-size: 26px;
            font-weight: 800;
            color: #ffffff;
        }

        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .stat-icon.red { background: var(--danger-bg); color: var(--danger); }
        .stat-icon.amber { background: var(--warning-bg); color: var(--warning); }
        .stat-icon.blue { background: rgba(59, 130, 246, 0.15); color: var(--primary); }
        .stat-icon.green { background: var(--success-bg); color: var(--success); }
        .stat-icon.gray { background: rgba(148, 163, 184, 0.15); color: var(--text-muted); }

        /* Action & Filter Bar */
        .action-bar {
            background: var(--panel-bg);
            border: 1px solid var(--panel-border);
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }

        .search-box {
            display: flex;
            align-items: center;
            background: #0b1120;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 8px 14px;
            gap: 10px;
            width: 320px;
            max-width: 100%;
        }

        .search-box i {
            color: var(--text-muted);
            font-size: 14px;
        }

        .search-box input {
            background: transparent;
            border: none;
            outline: none;
            color: #ffffff;
            font-size: 13px;
            width: 100%;
        }

        .filter-tags {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .filter-btn {
            background: #0b1120;
            border: 1px solid #334155;
            color: var(--text-muted);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .filter-btn:hover, .filter-btn.active {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
        }

        .bulk-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Table Styles */
        .table-card {
            background: var(--panel-bg);
            border: 1px solid var(--panel-border);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 13px;
        }

        thead {
            background: var(--table-header);
            border-bottom: 2px solid var(--panel-border);
        }

        th {
            padding: 14px 16px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.05em;
            white-space: nowrap;
        }

        tbody tr {
            border-bottom: 1px solid rgba(51, 65, 85, 0.6);
            transition: var(--transition);
        }

        tbody tr:hover {
            background: rgba(30, 41, 59, 0.7);
        }

        tbody tr.row-mismatch {
            background: rgba(239, 68, 68, 0.04);
        }
        tbody tr.row-mismatch:hover {
            background: rgba(239, 68, 68, 0.09);
        }

        td {
            padding: 13px 16px;
            vertical-align: middle;
        }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 9px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .badge-cancel {
            background: var(--danger-bg);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.35);
        }

        .badge-active {
            background: var(--success-bg);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.35);
        }

        .badge-mixed {
            background: var(--warning-bg);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.35);
        }

        .badge-na {
            background: rgba(148, 163, 184, 0.1);
            color: #94a3b8;
            border: 1px solid rgba(148, 163, 184, 0.2);
        }

        .badge-psono {
            background: rgba(59, 130, 246, 0.12);
            color: #60a5fa;
            border: 1px solid rgba(59, 130, 246, 0.3);
            font-family: monospace;
            font-size: 12px;
            padding: 3px 8px;
        }

        .cell-subtext {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 3px;
            line-height: 1.3;
        }

        .product-title {
            font-weight: 600;
            color: #ffffff;
            font-size: 13px;
        }

        /* Difference Alert Tag */
        .diff-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 600;
        }
        .diff-tag.danger {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        .diff-tag.success {
            background: rgba(16, 185, 129, 0.12);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.25);
        }
        .diff-tag.muted {
            background: rgba(148, 163, 184, 0.1);
            color: #94a3b8;
            border: 1px solid rgba(148, 163, 184, 0.2);
        }

        /* Action Buttons Grid */
        .actions-cell {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: nowrap;
        }

        /* Checkbox styling */
        input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
            accent-color: var(--primary);
        }

        /* Footer & Pagination */
        .table-footer {
            padding: 14px 20px;
            background: var(--table-header);
            border-top: 1px solid var(--panel-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            font-size: 12.5px;
            color: var(--text-muted);
        }

        .pagination {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .page-btn {
            background: #0b1120;
            border: 1px solid #334155;
            color: var(--text-main);
            padding: 5px 10px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            transition: var(--transition);
        }

        .page-btn:hover:not(:disabled) {
            background: var(--primary);
            border-color: var(--primary);
        }

        .page-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: var(--text-muted);
        }
        .empty-state i {
            font-size: 40px;
            margin-bottom: 12px;
            color: #475569;
        }

        /* Dropdown menu for table-specific updates */
        .dropdown {
            position: relative;
            display: inline-block;
        }
        .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 6px 0;
            min-width: 180px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
            z-index: 100;
        }
        .dropdown:hover .dropdown-menu {
            display: block;
        }
        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            font-size: 12px;
            color: var(--text-main);
            cursor: pointer;
            text-decoration: none;
            transition: background 0.15s ease;
        }
        .dropdown-item:hover {
            background: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
        }
        .dropdown-divider {
            height: 1px;
            background: #334155;
            margin: 4px 0;
        }
    </style>
</head>
<body>

<div class="container">

    <!-- Top Header -->
    <div class="header">
        <div class="header-title-box">
            <div class="header-icon">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div>
                <h1 class="header-title">Provisional Order Cancellation Auditor & Sync</h1>
                <div class="header-subtitle">
                    Cross-checking <code>krf_provisional_sales_order_items</code>, <code>krf_sales_order_items</code>, <code>krf_productionorder_consumed</code>, and <code>krf_readystock_allocation</code>
                </div>
            </div>
        </div>
        <div class="header-actions">
            <button class="btn btn-danger btn-sm" onclick="syncParentCancelledMismatches()" <?php echo empty($parentCancelMismatchList) ? 'disabled' : ''; ?> style="background: #dc2626; border-color: #b91c1c; font-weight: 700; box-shadow: 0 4px 14px rgba(220, 38, 38, 0.4);">
                <i class="fa-solid fa-triangle-exclamation"></i> Cancel Everywhere for Cancelled Orders (<?php echo $cntParentCancelChildActive; ?>)
            </button>
            <button class="btn btn-outline btn-sm" onclick="syncAllMismatches('Cancel')" <?php echo empty($mismatchListForGlobalSync) ? 'disabled' : ''; ?> style="color: #f87171; border-color: rgba(239, 68, 68, 0.4);">
                <i class="fa-solid fa-ban"></i> Sync All Mismatches to Cancel (<?php echo $totalMismatches; ?>)
            </button>
            <button class="btn btn-success btn-sm" onclick="syncAllMismatches('Active')" <?php echo empty($mismatchListForGlobalSync) ? 'disabled' : ''; ?>>
                <i class="fa-solid fa-check-double"></i> Sync All Mismatches to Active
            </button>
            <button class="btn btn-outline btn-sm" onclick="location.reload()">
                <i class="fa-solid fa-rotate"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card" onclick="setFilter('parent_cancel_child_active')" style="border-left: 4px solid #ef4444; background: rgba(239, 68, 68, 0.08);">
            <div class="stat-info">
                <h4>Order Cancelled / Items Active</h4>
                <div class="stat-value" style="color: #f87171;"><?php echo number_format($cntParentCancelChildActive); ?></div>
            </div>
            <div class="stat-icon red">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
        </div>

        <div class="stat-card" onclick="setFilter('parent_cancel_all')">
            <div class="stat-info">
                <h4>All Cancelled Orders (Items)</h4>
                <div class="stat-value" style="color: #cbd5e1;"><?php echo number_format($cntParentCancelTotal); ?></div>
            </div>
            <div class="stat-icon gray">
                <i class="fa-solid fa-ban"></i>
            </div>
        </div>

        <div class="stat-card" onclick="setFilter('mismatch')">
            <div class="stat-info">
                <h4>Total Mismatches</h4>
                <div class="stat-value" style="color: #f87171;"><?php echo number_format($totalMismatches); ?></div>
            </div>
            <div class="stat-icon red">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
        </div>

        <div class="stat-card" onclick="setFilter('psoi_cancel_child_active')">
            <div class="stat-info">
                <h4>PSOI Cancelled / Others Active</h4>
                <div class="stat-value" style="color: #fb923c;"><?php echo number_format($cntPsoiCancelChildActive); ?></div>
            </div>
            <div class="stat-icon amber">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
        </div>

        <div class="stat-card" onclick="setFilter('psoi_active_child_cancel')">
            <div class="stat-info">
                <h4>PSOI Active / Others Cancelled</h4>
                <div class="stat-value" style="color: #38bdf8;"><?php echo number_format($cntPsoiActiveChildCancel); ?></div>
            </div>
            <div class="stat-icon blue">
                <i class="fa-solid fa-layer-group"></i>
            </div>
        </div>

        <div class="stat-card" onclick="setFilter('all_cancel')">
            <div class="stat-info">
                <h4>Fully Cancelled</h4>
                <div class="stat-value" style="color: #94a3b8;"><?php echo number_format($cntAllCancel); ?></div>
            </div>
            <div class="stat-icon gray">
                <i class="fa-solid fa-ban"></i>
            </div>
        </div>

        <div class="stat-card" onclick="setFilter('all_active')">
            <div class="stat-info">
                <h4>Fully Active</h4>
                <div class="stat-value" style="color: #34d399;"><?php echo number_format($cntAllActive); ?></div>
            </div>
            <div class="stat-icon green">
                <i class="fa-solid fa-check"></i>
            </div>
        </div>

        <div class="stat-card" onclick="setFilter('missing_soi')">
            <div class="stat-info">
                <h4>Missing in Sales Items</h4>
                <div class="stat-value" style="color: #60a5fa;"><?php echo number_format($cntMissingSoi); ?></div>
            </div>
            <div class="stat-icon blue">
                <i class="fa-solid fa-file-circle-plus"></i>
            </div>
        </div>

        <div class="stat-card" onclick="setFilter('missing_poc')">
            <div class="stat-info">
                <h4>Missing in Consumed (POC)</h4>
                <div class="stat-value" style="color: #c084fc;"><?php echo number_format($cntMissingPoc); ?></div>
            </div>
            <div class="stat-icon" style="background: rgba(192, 132, 252, 0.15); color: #c084fc;">
                <i class="fa-solid fa-cubes-stacked"></i>
            </div>
        </div>

        <div class="stat-card" onclick="setFilter('all')">
            <div class="stat-info">
                <h4>Total Items Audited</h4>
                <div class="stat-value"><?php echo number_format($totalItems); ?></div>
            </div>
            <div class="stat-icon blue">
                <i class="fa-solid fa-database"></i>
            </div>
        </div>
    </div>

    <!-- Action & Filter Bar -->
    <div class="action-bar">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="searchInput" placeholder="Search order no, product, client, sku..." onkeyup="handleSearch()">
        </div>

        <div class="filter-tags">
            <button class="filter-btn" id="filter-parent_cancel_child_active" onclick="setFilter('parent_cancel_child_active')" style="border-color: #ef4444; color: #fca5a5;">
                <i class="fa-solid fa-triangle-exclamation text-danger me-1"></i> Order Cancelled / Items Active (<?php echo $cntParentCancelChildActive; ?>)
            </button>
            <button class="filter-btn" id="filter-parent_cancel_all" onclick="setFilter('parent_cancel_all')">
                <i class="fa-solid fa-ban me-1"></i> All Cancelled Orders (<?php echo $cntParentCancelTotal; ?>)
            </button>
            <button class="filter-btn active" id="filter-mismatch" onclick="setFilter('mismatch')">
                <i class="fa-solid fa-triangle-exclamation me-1"></i> Mismatches Only (<?php echo $totalMismatches; ?>)
            </button>
            <button class="filter-btn" id="filter-missing_soi" onclick="setFilter('missing_soi')">
                <i class="fa-solid fa-plus-circle me-1"></i> Missing in SOI (<?php echo $cntMissingSoi; ?>)
            </button>
            <button class="filter-btn" id="filter-missing_poc" onclick="setFilter('missing_poc')">
                <i class="fa-solid fa-plus-circle me-1"></i> Missing in POC (<?php echo $cntMissingPoc; ?>)
            </button>
            <button class="filter-btn" id="filter-psoi_cancel_child_active" onclick="setFilter('psoi_cancel_child_active')">
                PSOI Cancelled / Others Active (<?php echo $cntPsoiCancelChildActive; ?>)
            </button>
            <button class="filter-btn" id="filter-psoi_active_child_cancel" onclick="setFilter('psoi_active_child_cancel')">
                PSOI Active / Others Cancelled (<?php echo $cntPsoiActiveChildCancel; ?>)
            </button>
            <button class="filter-btn" id="filter-all_cancel" onclick="setFilter('all_cancel')">
                Fully Cancelled (<?php echo $cntAllCancel; ?>)
            </button>
            <button class="filter-btn" id="filter-all_active" onclick="setFilter('all_active')">
                Fully Active (<?php echo $cntAllActive; ?>)
            </button>
            <button class="filter-btn" id="filter-all" onclick="setFilter('all')">
                Show All (<?php echo $totalItems; ?>)
            </button>
        </div>

        <div class="bulk-actions">
            <span id="selectedCount" style="font-size: 12px; color: var(--text-muted); display: none;">0 selected</span>
            <button class="btn btn-primary btn-sm" id="btnBulkInsertSoi" onclick="bulkInsertMissingSoi()" style="display: none;">
                <i class="fa-solid fa-plus-circle"></i> Insert Missing in SOI
            </button>
            <button class="btn btn-sm" id="btnBulkInsertPoc" onclick="bulkInsertMissingPoc()" style="background: rgba(192, 132, 252, 0.2); border: 1px solid rgba(192, 132, 252, 0.5); color: #e9d5ff; display: none;">
                <i class="fa-solid fa-plus-circle"></i> Insert Missing in POC
            </button>
            <button class="btn btn-danger btn-sm" id="btnBulkCancel" onclick="bulkUpdate('Cancel')" style="display: none;">
                <i class="fa-solid fa-ban"></i> Mark Cancel
            </button>
            <button class="btn btn-success btn-sm" id="btnBulkActive" onclick="bulkUpdate('Active')" style="display: none;">
                <i class="fa-solid fa-check"></i> Mark Active
            </button>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="table-card">
        <div class="table-responsive">
            <table id="auditTable">
                <thead>
                    <tr>
                        <th style="width: 38px;">
                            <input type="checkbox" id="checkAll" onchange="toggleSelectAll(this)">
                        </th>
                        <th>Order & Client</th>
                        <th>Product & Details</th>
                        <th>1. Provisional Items<br><span style="font-size: 9.5px; opacity: 0.7;">krf_provisional_sales_order_items</span></th>
                        <th>2. Sales Items<br><span style="font-size: 9.5px; opacity: 0.7;">krf_sales_order_items</span></th>
                        <th>3. Consumed Stock<br><span style="font-size: 9.5px; opacity: 0.7;">krf_productionorder_consumed</span></th>
                        <th>4. Ready Allocation<br><span style="font-size: 9.5px; opacity: 0.7;">krf_readystock_allocation</span></th>
                        <th>Status Audit</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <!-- Rows dynamically generated by JavaScript for speed & pagination -->
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <div id="paginationInfo">Showing 0 to 0 of 0 items</div>
            <div class="pagination">
                <button class="page-btn" id="btnPrevPage" onclick="prevPage()"><i class="fa-solid fa-chevron-left"></i> Prev</button>
                <span id="pageIndicator" style="padding: 0 8px; font-weight: 600;">Page 1</span>
                <button class="page-btn" id="btnNextPage" onclick="nextPage()">Next <i class="fa-solid fa-chevron-right"></i></button>
                <select id="pageSizeSelect" class="page-btn" onchange="changePageSize(this.value)" style="margin-left: 8px;">
                    <option value="50">50 / page</option>
                    <option value="100">100 / page</option>
                    <option value="250">250 / page</option>
                    <option value="500">500 / page</option>
                    <option value="all">All items</option>
                </select>
            </div>
        </div>
    </div>

</div>

<!-- Pass PHP Data into JavaScript for instantaneous client-side operations -->
<script>
const rawData = <?php echo json_encode($auditItems, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
const allMismatchesData = <?php echo json_encode($mismatchListForGlobalSync); ?>;
const parentCancelMismatchesData = <?php echo json_encode($parentCancelMismatchList); ?>;

let currentFilter = 'mismatch'; // Default to showing differences!
let currentSearch = '';
let currentPage = 1;
let pageSize = 50;
let filteredItems = [];
const selectedKeys = new Set();

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    applyFilterAndSearch();
});

function applyFilterAndSearch() {
    filteredItems = rawData.filter(item => {
        // Filter condition
        if (currentFilter === 'mismatch' && !item.is_mismatch) return false;
        if (currentFilter === 'parent_cancel_child_active' && !item.is_parent_cancel_child_active) return false;
        if (currentFilter === 'parent_cancel_all' && !item.is_parent_cancel) return false;
        if (currentFilter === 'missing_soi' && !item.is_missing_soi) return false;
        if (currentFilter === 'missing_poc' && !item.is_missing_poc) return false;
        if (currentFilter === 'psoi_cancel_child_active' && item.mismatch_type !== 'psoi_cancel_child_active') return false;
        if (currentFilter === 'psoi_active_child_cancel' && item.mismatch_type !== 'psoi_active_child_cancel') return false;
        if (currentFilter === 'all_cancel' && item.mismatch_type !== 'all_cancel') return false;
        if (currentFilter === 'all_active' && item.mismatch_type !== 'all_active') return false;

        // Search condition
        if (currentSearch.trim() !== '') {
            const q = currentSearch.toLowerCase();
            const matchOrder = item.psono.toLowerCase().includes(q);
            const matchProd = item.product_name.toLowerCase().includes(q);
            const matchPid = String(item.product_id).includes(q);
            const matchClient = (item.clientname || '').toLowerCase().includes(q);
            const matchStock = (item.stockorder_no || '').toLowerCase().includes(q);
            if (!matchOrder && !matchProd && !matchPid && !matchClient && !matchStock) {
                return false;
            }
        }

        return true;
    });

    currentPage = 1;
    renderTable();
}

function renderTable() {
    const tbody = document.getElementById('tableBody');
    tbody.innerHTML = '';

    const total = filteredItems.length;
    let startIdx = 0;
    let endIdx = total;

    if (pageSize !== 'all') {
        const size = parseInt(pageSize, 10);
        startIdx = (currentPage - 1) * size;
        endIdx = Math.min(startIdx + size, total);
    }

    if (total === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9">
                    <div class="empty-state">
                        <i class="fa-solid fa-circle-check"></i>
                        <h3>No Records Found</h3>
                        <p>No orders matched your current filter criteria.</p>
                    </div>
                </td>
            </tr>
        `;
        document.getElementById('paginationInfo').innerText = 'Showing 0 items';
        document.getElementById('btnPrevPage').disabled = true;
        document.getElementById('btnNextPage').disabled = true;
        document.getElementById('pageIndicator').innerText = 'Page 0 of 0';
        return;
    }

    const pageItems = filteredItems.slice(startIdx, endIdx);

    let html = '';
    for (const item of pageItems) {
        const rowKey = `${item.psono}___${item.product_id}`;
        const isChecked = selectedKeys.has(rowKey) ? 'checked' : '';
        const mismatchClass = item.is_mismatch ? 'row-mismatch' : '';

        // Badges for PSOI
        const psoiBadge = item.psoi_state === 'Cancel' 
            ? `<span class="badge badge-cancel"><i class="fa-solid fa-ban"></i> Cancelled</span>`
            : `<span class="badge badge-active"><i class="fa-solid fa-check"></i> Active (${escapeHtml(item.psoi_status_raw || 'Confirmed')})</span>`;

        // Badges for SOI
        let soiBadge = `<span class="badge badge-na"><i class="fa-solid fa-minus"></i> No SOI</span>`;
        let soiDetails = '';
        if (item.soi_list && item.soi_list.length > 0) {
            const s = item.soi_list[0];
            const stateClass = item.soi_state === 'Cancel' ? 'badge-cancel' : (item.soi_state === 'Mixed' ? 'badge-mixed' : 'badge-active');
            const icon = item.soi_state === 'Cancel' ? 'fa-ban' : 'fa-check';
            soiBadge = `<span class="badge ${stateClass}"><i class="fa-solid ${icon}"></i> ${escapeHtml(item.soi_state)} (${escapeHtml(s.status || 'Active')})</span>`;
            soiDetails = `<div class="cell-subtext">SO: ${escapeHtml(s.sono || 'N/A')} | Disp: ${escapeHtml(String(s.dispatchedqty || 0))}</div>`;
        } else {
            const targetStatusText = item.psoi_state === 'Cancel' ? 'Cancelled' : 'Confirmed';
            const isCancel = (item.psoi_state === 'Cancel');
            const btnBg = isCancel ? 'rgba(239, 68, 68, 0.15)' : 'rgba(16, 185, 129, 0.15)';
            const btnBorder = isCancel ? 'rgba(239, 68, 68, 0.4)' : 'rgba(16, 185, 129, 0.4)';
            const btnColor = isCancel ? '#fca5a5' : '#86efac';
            const btnIcon = isCancel ? 'fa-ban' : 'fa-check';

            soiBadge = `
                <div style="display: flex; flex-direction: column; gap: 6px; align-items: flex-start;">
                    <span class="badge badge-na"><i class="fa-solid fa-minus"></i> No SOI</span>
                    <button class="btn btn-sm" style="background: ${btnBg}; border: 1px solid ${btnBorder}; color: ${btnColor}; padding: 3px 8px; font-size: 11px; border-radius: 6px; cursor: pointer; white-space: nowrap;"
                            title="Insert entry into krf_sales_order_items with status as per Provisional Items (${targetStatusText})"
                            onclick="insertMissingSoi('${escapeHtml(item.psono)}', '${escapeHtml(item.product_id)}', '${escapeHtml(targetStatusText)}', ${item.psoi_id})">
                        <i class="fa-solid ${btnIcon} me-1"></i> Insert SOI (${escapeHtml(targetStatusText)})
                    </button>
                </div>
            `;
        }

        // Badges for POC
        let pocBadge = `<span class="badge badge-na"><i class="fa-solid fa-minus"></i> No POC</span>`;
        let pocDetails = '';
        if (item.poc_list && item.poc_list.length > 0) {
            const p = item.poc_list[0];
            const stateClass = item.poc_state === 'Cancel' ? 'badge-cancel' : (item.poc_state === 'Mixed' ? 'badge-mixed' : 'badge-active');
            const icon = item.poc_state === 'Cancel' ? 'fa-ban' : 'fa-check';
            pocBadge = `<span class="badge ${stateClass}"><i class="fa-solid ${icon}"></i> ${escapeHtml(item.poc_state)}</span>`;
            pocDetails = `<div class="cell-subtext">Status: ${escapeHtml(p.status || '')} | Confirm: ${escapeHtml(p.confirmstatus || '')}</div>`;
        } else {
            const targetStatusText = item.psoi_state === 'Cancel' ? 'Cancelled' : 'Confirmed';
            const isCancel = (item.psoi_state === 'Cancel');
            const btnBg = isCancel ? 'rgba(239, 68, 68, 0.15)' : 'rgba(16, 185, 129, 0.15)';
            const btnBorder = isCancel ? 'rgba(239, 68, 68, 0.4)' : 'rgba(16, 185, 129, 0.4)';
            const btnColor = isCancel ? '#fca5a5' : '#86efac';
            const btnIcon = isCancel ? 'fa-ban' : 'fa-check';

            pocBadge = `
                <div style="display: flex; flex-direction: column; gap: 6px; align-items: flex-start;">
                    <span class="badge badge-na"><i class="fa-solid fa-minus"></i> No POC</span>
                    <button class="btn btn-sm" style="background: ${btnBg}; border: 1px solid ${btnBorder}; color: ${btnColor}; padding: 3px 8px; font-size: 11px; border-radius: 6px; cursor: pointer; white-space: nowrap;"
                            title="Insert entry into krf_productionorder_consumed with status as per Provisional Items (${targetStatusText})"
                            onclick="insertMissingPoc('${escapeHtml(item.psono)}', '${escapeHtml(item.product_id)}', '${escapeHtml(targetStatusText)}', ${item.psoi_id})">
                        <i class="fa-solid ${btnIcon} me-1"></i> Insert POC (${escapeHtml(targetStatusText)})
                    </button>
                </div>
            `;
        }

        // Badges for RSA
        let rsaBadge = `<span class="badge badge-na"><i class="fa-solid fa-minus"></i> No RSA</span>`;
        let rsaDetails = '';
        if (item.rsa_list && item.rsa_list.length > 0) {
            const r = item.rsa_list[0];
            const stateClass = item.rsa_state === 'Cancel' ? 'badge-cancel' : 'badge-active';
            const icon = item.rsa_state === 'Cancel' ? 'fa-ban' : 'fa-check';
            rsaBadge = `<span class="badge ${stateClass}"><i class="fa-solid ${icon}"></i> ${escapeHtml(item.rsa_state)} (${escapeHtml(r.status || '')})</span>`;
            rsaDetails = `<div class="cell-subtext">Bed: ${escapeHtml(r.allocatedbed || 'N/A')} | Qty: ${escapeHtml(String(r.allocate_ordqty || 0))}</div>`;
        }

        // Audit Summary Indicator
        let diffIndicator = '';
        if (item.is_parent_cancel_child_active) {
            diffIndicator = `<span class="diff-tag danger" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5;"><i class="fa-solid fa-triangle-exclamation"></i> Order Cancelled, Items Active!</span>`;
        } else if (item.is_mismatch) {
            let explanation = '';
            if (item.mismatch_type === 'psoi_cancel_child_active') {
                explanation = 'PSOI is Cancelled, but children are Active';
            } else if (item.mismatch_type === 'psoi_active_child_cancel') {
                explanation = 'Child tables Cancelled, but PSOI is Active';
            } else {
                explanation = 'Status mismatch across tables';
            }
            diffIndicator = `<span class="diff-tag danger"><i class="fa-solid fa-triangle-exclamation"></i> ${explanation}</span>`;
        } else {
            if (item.psoi_state === 'Cancel') {
                diffIndicator = `<span class="diff-tag muted"><i class="fa-solid fa-ban"></i> All Cancelled</span>`;
            } else {
                diffIndicator = `<span class="diff-tag success"><i class="fa-solid fa-check"></i> All Active</span>`;
            }
        }

        html += `
            <tr class="${mismatchClass}" id="row-${escapeHtml(item.psono)}-${escapeHtml(item.product_id)}">
                <td>
                    <input type="checkbox" class="row-chk" data-key="${escapeHtml(rowKey)}" ${isChecked} onchange="toggleRowSelect(this, '${escapeHtml(rowKey)}')">
                </td>
                <td>
                    <div class="badge badge-psono">${escapeHtml(item.psono)}</div>
                    <div style="font-weight: 600; font-size: 12.5px; margin-top: 4px; color: #ffffff;">
                        ${escapeHtml(item.clientname)}
                        ${item.is_trader ? `<span class="badge" style="background: rgba(168, 85, 247, 0.2); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.4); font-size: 10px; padding: 2px 6px; margin-left: 6px;" title="Trader Client (is_trader = 1): Production & Sowing orders are preserved"><i class="fa-solid fa-store me-1"></i>Trader</span>` : ''}
                    </div>
                    <div class="cell-subtext">Order Status: <span style="color: #60a5fa;">${escapeHtml(item.parent_status)}</span> | ${escapeHtml(item.created_at ? item.created_at.substring(0, 10) : 'N/A')}</div>
                </td>
                <td>
                    <div class="product-title">${escapeHtml(item.product_name)}</div>
                    <div class="cell-subtext">Product ID: <strong>#${escapeHtml(item.product_id)}</strong> | Qty: <strong>${escapeHtml(String(item.quantity))}</strong></div>
                    ${item.stockorder_no ? `<div class="cell-subtext">Stock Order: <code>${escapeHtml(item.stockorder_no)}</code></div>` : ''}
                </td>
                <td>
                    ${psoiBadge}
                    ${item.psoi_remark ? `<div class="cell-subtext" title="${escapeHtml(item.psoi_remark)}"><i class="fa-solid fa-comment-dots"></i> ${escapeHtml(item.psoi_remark.substring(0, 35))}...</div>` : ''}
                </td>
                <td>
                    ${soiBadge}
                    ${soiDetails}
                </td>
                <td>
                    ${pocBadge}
                    ${pocDetails}
                </td>
                <td>
                    ${rsaBadge}
                    ${rsaDetails}
                </td>
                <td>
                    ${diffIndicator}
                </td>
                <td style="text-align: right;">
                    <div class="actions-cell" style="justify-content: flex-end;">
                        <button class="btn btn-danger btn-sm" title="Mark Cancel in All Tables" onclick="updateItem('${escapeHtml(item.psono)}', '${escapeHtml(item.product_id)}', 'Cancel', 'all')">
                            <i class="fa-solid fa-ban"></i> Cancel
                        </button>
                        <button class="btn btn-success btn-sm" title="Mark Active in All Tables" onclick="updateItem('${escapeHtml(item.psono)}', '${escapeHtml(item.product_id)}', 'Active', 'all')">
                            <i class="fa-solid fa-check"></i> Active
                        </button>
                        <div class="dropdown">
                            <button class="btn btn-outline btn-sm" title="Table-specific options">
                                <i class="fa-solid fa-ellipsis-vertical"></i>
                            </button>
                            <div class="dropdown-menu">
                                ${(!item.soi_list || item.soi_list.length === 0) ? `
                                    <a class="dropdown-item" style="color: #60a5fa; font-weight: 600;" onclick="insertMissingSoi('${escapeHtml(item.psono)}', '${escapeHtml(item.product_id)}', '${escapeHtml(item.psoi_state === 'Cancel' ? 'Cancelled' : 'Confirmed')}', ${item.psoi_id})">
                                        <i class="fa-solid fa-plus-circle text-primary"></i> Insert into SOI (${escapeHtml(item.psoi_state === 'Cancel' ? 'Cancelled' : 'Confirmed')})
                                    </a>
                                    <div class="dropdown-divider"></div>
                                ` : ''}
                                ${(!item.poc_list || item.poc_list.length === 0) ? `
                                    <a class="dropdown-item" style="color: #c084fc; font-weight: 600;" onclick="insertMissingPoc('${escapeHtml(item.psono)}', '${escapeHtml(item.product_id)}', '${escapeHtml(item.psoi_state === 'Cancel' ? 'Cancelled' : 'Confirmed')}', ${item.psoi_id})">
                                        <i class="fa-solid fa-plus-circle" style="color: #c084fc;"></i> Insert into POC (${escapeHtml(item.psoi_state === 'Cancel' ? 'Cancelled' : 'Confirmed')})
                                    </a>
                                    <div class="dropdown-divider"></div>
                                ` : ''}
                                <a class="dropdown-item" onclick="updateItem('${escapeHtml(item.psono)}', '${escapeHtml(item.product_id)}', 'Cancel', 'psoi')"><i class="fa-solid fa-ban text-danger"></i> Cancel PSOI Only</a>
                                <a class="dropdown-item" onclick="updateItem('${escapeHtml(item.psono)}', '${escapeHtml(item.product_id)}', 'Active', 'psoi')"><i class="fa-solid fa-check text-success"></i> Activate PSOI Only</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" onclick="updateItem('${escapeHtml(item.psono)}', '${escapeHtml(item.product_id)}', 'Cancel', 'soi')"><i class="fa-solid fa-ban text-danger"></i> Cancel SOI Only</a>
                                <a class="dropdown-item" onclick="updateItem('${escapeHtml(item.psono)}', '${escapeHtml(item.product_id)}', 'Active', 'soi')"><i class="fa-solid fa-check text-success"></i> Activate SOI Only</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" onclick="updateItem('${escapeHtml(item.psono)}', '${escapeHtml(item.product_id)}', 'Cancel', 'poc')"><i class="fa-solid fa-ban text-danger"></i> Cancel POC Only</a>
                                <a class="dropdown-item" onclick="updateItem('${escapeHtml(item.psono)}', '${escapeHtml(item.product_id)}', 'Active', 'poc')"><i class="fa-solid fa-check text-success"></i> Activate POC Only</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" onclick="updateItem('${escapeHtml(item.psono)}', '${escapeHtml(item.product_id)}', 'Cancel', 'rsa')"><i class="fa-solid fa-ban text-danger"></i> Cancel RSA Only</a>
                                <a class="dropdown-item" onclick="updateItem('${escapeHtml(item.psono)}', '${escapeHtml(item.product_id)}', 'Active', 'rsa')"><i class="fa-solid fa-check text-success"></i> Activate RSA Only</a>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        `;
    }

    tbody.innerHTML = html;

    // Update pagination controls
    const totalPages = pageSize === 'all' ? 1 : Math.ceil(total / parseInt(pageSize, 10));
    document.getElementById('paginationInfo').innerText = `Showing ${startIdx + 1} to ${endIdx} of ${total} items`;
    document.getElementById('pageIndicator').innerText = `Page ${currentPage} of ${totalPages || 1}`;
    document.getElementById('btnPrevPage').disabled = (currentPage <= 1);
    document.getElementById('btnNextPage').disabled = (currentPage >= totalPages);

    updateBulkActionBar();
}

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function setFilter(filterType) {
    currentFilter = filterType;
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    const btn = document.getElementById(`filter-${filterType}`);
    if (btn) btn.classList.add('active');
    applyFilterAndSearch();
}

function handleSearch() {
    currentSearch = document.getElementById('searchInput').value;
    applyFilterAndSearch();
}

function prevPage() {
    if (currentPage > 1) {
        currentPage--;
        renderTable();
        window.scrollTo({ top: 320, behavior: 'smooth' });
    }
}

function nextPage() {
    const totalPages = pageSize === 'all' ? 1 : Math.ceil(filteredItems.length / parseInt(pageSize, 10));
    if (currentPage < totalPages) {
        currentPage++;
        renderTable();
        window.scrollTo({ top: 320, behavior: 'smooth' });
    }
}

function changePageSize(val) {
    pageSize = val;
    currentPage = 1;
    renderTable();
}

// -------------------------------------------------------------------------
// SELECTION HANDLING
// -------------------------------------------------------------------------
function toggleSelectAll(chk) {
    const isChecked = chk.checked;
    const size = pageSize === 'all' ? filteredItems.length : parseInt(pageSize, 10);
    const startIdx = (currentPage - 1) * (pageSize === 'all' ? 0 : size);
    const pageItems = filteredItems.slice(startIdx, startIdx + size);

    pageItems.forEach(item => {
        const key = `${item.psono}___${item.product_id}`;
        if (isChecked) {
            selectedKeys.add(key);
        } else {
            selectedKeys.delete(key);
        }
    });

    document.querySelectorAll('.row-chk').forEach(c => c.checked = isChecked);
    updateBulkActionBar();
}

function toggleRowSelect(chk, key) {
    if (chk.checked) {
        selectedKeys.add(key);
    } else {
        selectedKeys.delete(key);
    }
    updateBulkActionBar();
}

function updateBulkActionBar() {
    const count = selectedKeys.size;
    const countSpan = document.getElementById('selectedCount');
    const btnCancel = document.getElementById('btnBulkCancel');
    const btnActive = document.getElementById('btnBulkActive');
    const btnBulkInsertSoi = document.getElementById('btnBulkInsertSoi');
    const btnBulkInsertPoc = document.getElementById('btnBulkInsertPoc');

    const hasMissingSoiInSelection = Array.from(selectedKeys).some(k => {
        const item = rawData.find(d => `${d.psono}___${d.product_id}` === k);
        return item && item.is_missing_soi;
    });

    const hasMissingPocInSelection = Array.from(selectedKeys).some(k => {
        const item = rawData.find(d => `${d.psono}___${d.product_id}` === k);
        return item && item.is_missing_poc;
    });

    if (count > 0) {
        countSpan.style.display = 'inline';
        countSpan.innerText = `${count} item(s) selected`;
        btnCancel.style.display = 'inline-flex';
        btnActive.style.display = 'inline-flex';
        if (btnBulkInsertSoi) {
            btnBulkInsertSoi.style.display = hasMissingSoiInSelection ? 'inline-flex' : 'none';
        }
        if (btnBulkInsertPoc) {
            btnBulkInsertPoc.style.display = hasMissingPocInSelection ? 'inline-flex' : 'none';
        }
    } else {
        countSpan.style.display = 'none';
        btnCancel.style.display = 'none';
        btnActive.style.display = 'none';
        if (btnBulkInsertSoi) {
            btnBulkInsertSoi.style.display = 'none';
        }
        if (btnBulkInsertPoc) {
            btnBulkInsertPoc.style.display = 'none';
        }
    }
}

// -------------------------------------------------------------------------
// INSERT MISSING SALES ITEM CALLS
// -------------------------------------------------------------------------
async function insertMissingSoi(psono, productId, targetStatus, psoiId) {
    const isCancel = (targetStatus === 'Cancelled' || targetStatus === 'Cancel');
    const actionColor = isCancel ? '#ef4444' : '#10b981';
    const statusLabel = isCancel ? 'Cancelled' : 'Confirmed';
    const statusBadge = isCancel 
        ? `<span style="color: #f87171; font-weight: bold; background: rgba(239, 68, 68, 0.15); padding: 2px 8px; border-radius: 4px;"><i class="fa-solid fa-ban me-1"></i>Cancelled</span>` 
        : `<span style="color: #34d399; font-weight: bold; background: rgba(16, 185, 129, 0.15); padding: 2px 8px; border-radius: 4px;"><i class="fa-solid fa-check me-1"></i>Confirmed</span>`;

    const confirm = await Swal.fire({
        title: `Insert into Sales Order Items?`,
        html: `Order: <b>${escapeHtml(psono)}</b><br>Product ID: <b>#${escapeHtml(productId)}</b><br><br>Status will be set to: ${statusBadge} <br><div style="font-size: 11.5px; color: #94a3b8; margin-top: 6px;">(Auto-matched to Provisional Items status)</div>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: actionColor,
        cancelButtonColor: '#334155',
        confirmButtonText: `Yes, Insert as ${statusLabel}`
    });

    if (!confirm.isConfirmed) return;

    Swal.fire({
        title: 'Inserting Sales Item...',
        text: 'Please wait while sales order header and item are created',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        const formData = new FormData();
        formData.append('action', 'insert_missing_soi');
        formData.append('psono', psono);
        formData.append('product_id', productId);
        formData.append('psoi_id', psoiId || 0);
        formData.append('target_status', statusLabel);

        const response = await fetch(window.location.pathname, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        if (result.success) {
            await Swal.fire({
                icon: 'success',
                title: 'Inserted Successfully!',
                text: result.message,
                timer: 1800,
                showConfirmButton: false
            });
            location.reload();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Insert Failed',
                text: result.message || 'Unknown error occurred.'
            });
        }
    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'Request Failed',
            text: err.message
        });
    }
}

// -------------------------------------------------------------------------
// INSERT MISSING CONSUMED STOCK (POC) CALLS
// -------------------------------------------------------------------------
async function insertMissingPoc(psono, productId, targetStatus, psoiId) {
    const isCancel = (targetStatus === 'Cancelled' || targetStatus === 'Cancel');
    const actionColor = isCancel ? '#ef4444' : '#10b981';
    const statusLabel = isCancel ? 'Cancelled' : 'Confirmed';
    const statusBadge = isCancel 
        ? `<span style="color: #f87171; font-weight: bold; background: rgba(239, 68, 68, 0.15); padding: 2px 8px; border-radius: 4px;"><i class="fa-solid fa-ban me-1"></i>Cancelled (confirm: cancelled)</span>` 
        : `<span style="color: #34d399; font-weight: bold; background: rgba(16, 185, 129, 0.15); padding: 2px 8px; border-radius: 4px;"><i class="fa-solid fa-check me-1"></i>Pending (confirm: Confirmed)</span>`;

    const confirm = await Swal.fire({
        title: `Insert into Consumed Stock (POC)?`,
        html: `Order: <b>${escapeHtml(psono)}</b><br>Product ID: <b>#${escapeHtml(productId)}</b><br><br>Status will be set to: ${statusBadge} <br><div style="font-size: 11.5px; color: #94a3b8; margin-top: 6px;">(Auto-matched to Provisional Items status)</div>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: actionColor,
        cancelButtonColor: '#334155',
        confirmButtonText: `Yes, Insert as ${statusLabel}`
    });

    if (!confirm.isConfirmed) return;

    Swal.fire({
        title: 'Inserting Consumed Stock...',
        text: 'Please wait while krf_productionorder_consumed record is created',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        const formData = new FormData();
        formData.append('action', 'insert_missing_poc');
        formData.append('psono', psono);
        formData.append('product_id', productId);
        formData.append('psoi_id', psoiId || 0);
        formData.append('target_status', statusLabel);

        const response = await fetch(window.location.pathname, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        if (result.success) {
            await Swal.fire({
                icon: 'success',
                title: 'Inserted Successfully!',
                text: result.message,
                timer: 1800,
                showConfirmButton: false
            });
            location.reload();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Insert Failed',
                text: result.message || 'Unknown error occurred.'
            });
        }
    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'Request Failed',
            text: err.message
        });
    }
}

async function bulkInsertMissingPoc() {
    const missingKeys = [];
    selectedKeys.forEach(k => {
        const item = rawData.find(d => `${d.psono}___${d.product_id}` === k);
        if (item && item.is_missing_poc) {
            missingKeys.push(item);
        }
    });

    if (missingKeys.length === 0) {
        Swal.fire({
            icon: 'info',
            title: 'No Missing POC Items',
            text: 'None of the selected rows are missing in Consumed Stock (POC).'
        });
        return;
    }

    const count = missingKeys.length;
    const confirm = await Swal.fire({
        title: `Insert ${count} Missing Consumed Stock Item(s)?`,
        html: `Each item will be inserted into <code>krf_productionorder_consumed</code> with status matching its corresponding Provisional Item.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#a78bfa',
        cancelButtonColor: '#334155',
        confirmButtonText: `Yes, Insert ${count} Item(s)`
    });

    if (!confirm.isConfirmed) return;

    Swal.fire({
        title: 'Inserting Items...',
        text: `Processing ${count} missing POC item(s)...`,
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        const payload = missingKeys.map(it => ({
            psono: it.psono,
            product_id: it.product_id,
            psoi_id: it.psoi_id,
            target_status: it.psoi_state === 'Cancel' ? 'Cancelled' : 'Confirmed'
        }));

        const formData = new FormData();
        formData.append('action', 'bulk_insert_missing_poc');
        formData.append('items', JSON.stringify(payload));

        const response = await fetch(window.location.pathname, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        if (result.success) {
            await Swal.fire({
                icon: 'success',
                title: 'Bulk Insert Complete',
                text: result.message
            });
            location.reload();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Bulk Insert Error',
                text: result.message || 'Error occurred during insertion.'
            });
        }
    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'Request Failed',
            text: err.message
        });
    }
}

async function bulkInsertMissingSoi() {
    const missingKeys = [];
    selectedKeys.forEach(k => {
        const item = rawData.find(d => `${d.psono}___${d.product_id}` === k);
        if (item && item.is_missing_soi) {
            missingKeys.push(item);
        }
    });

    if (missingKeys.length === 0) {
        Swal.fire({
            icon: 'info',
            title: 'No Missing Sales Items',
            text: 'None of the selected rows are missing in Sales Items.'
        });
        return;
    }

    const count = missingKeys.length;
    const confirm = await Swal.fire({
        title: `Insert ${count} Missing Sales Item(s)?`,
        html: `Each item will be inserted into <code>krf_sales_order_items</code> with status matching its corresponding Provisional Item.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3b82f6',
        cancelButtonColor: '#334155',
        confirmButtonText: `Yes, Insert ${count} Item(s)`
    });

    if (!confirm.isConfirmed) return;

    Swal.fire({
        title: 'Inserting Items...',
        text: `Processing ${count} missing item(s)...`,
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        const payload = missingKeys.map(it => ({
            psono: it.psono,
            product_id: it.product_id,
            psoi_id: it.psoi_id,
            target_status: it.psoi_state === 'Cancel' ? 'Cancelled' : 'Confirmed'
        }));

        const formData = new FormData();
        formData.append('action', 'bulk_insert_missing_soi');
        formData.append('items', JSON.stringify(payload));

        const response = await fetch(window.location.pathname, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        if (result.success) {
            await Swal.fire({
                icon: 'success',
                title: 'Bulk Insert Complete',
                text: result.message
            });
            location.reload();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Bulk Insert Error',
                text: result.message || 'Error occurred during insertion.'
            });
        }
    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'Request Failed',
            text: err.message
        });
    }
}

// -------------------------------------------------------------------------
// INSERT MISSING CONSUMED STOCK (POC) CALLS
// -------------------------------------------------------------------------
async function insertMissingPoc(psono, productId, targetStatus, psoiId) {
    const isCancel = (targetStatus === 'Cancelled' || targetStatus === 'Cancel');
    const actionColor = isCancel ? '#ef4444' : '#10b981';
    const statusLabel = isCancel ? 'Cancelled' : 'Confirmed';
    const statusBadge = isCancel 
        ? `<span style="color: #f87171; font-weight: bold; background: rgba(239, 68, 68, 0.15); padding: 2px 8px; border-radius: 4px;"><i class="fa-solid fa-ban me-1"></i>Cancelled (confirm: cancelled)</span>` 
        : `<span style="color: #34d399; font-weight: bold; background: rgba(16, 185, 129, 0.15); padding: 2px 8px; border-radius: 4px;"><i class="fa-solid fa-check me-1"></i>Pending (confirm: Confirmed)</span>`;

    const confirm = await Swal.fire({
        title: `Insert into Consumed Stock (POC)?`,
        html: `Order: <b>${escapeHtml(psono)}</b><br>Product ID: <b>#${escapeHtml(productId)}</b><br><br>Status will be set to: ${statusBadge} <br><div style="font-size: 11.5px; color: #94a3b8; margin-top: 6px;">(Auto-matched to Provisional Items status)</div>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: actionColor,
        cancelButtonColor: '#334155',
        confirmButtonText: `Yes, Insert as ${statusLabel}`
    });

    if (!confirm.isConfirmed) return;

    Swal.fire({
        title: 'Inserting Consumed Stock...',
        text: 'Please wait while krf_productionorder_consumed record is created',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        const formData = new FormData();
        formData.append('action', 'insert_missing_poc');
        formData.append('psono', psono);
        formData.append('product_id', productId);
        formData.append('psoi_id', psoiId || 0);
        formData.append('target_status', statusLabel);

        const response = await fetch(window.location.pathname, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        if (result.success) {
            await Swal.fire({
                icon: 'success',
                title: 'Inserted Successfully!',
                text: result.message,
                timer: 1800,
                showConfirmButton: false
            });
            location.reload();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Insert Failed',
                text: result.message || 'Unknown error occurred.'
            });
        }
    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'Request Failed',
            text: err.message
        });
    }
}

// -------------------------------------------------------------------------
// STATUS UPDATE CALLS
// -------------------------------------------------------------------------
async function updateItem(psono, productId, targetStatus, targetTable = 'all') {
    const tableLabel = targetTable === 'all' ? 'All Tables' : targetTable.toUpperCase();
    const actionColor = targetStatus === 'Cancel' ? '#ef4444' : '#10b981';

    const confirm = await Swal.fire({
        title: `Set status to ${targetStatus}?`,
        html: `Order: <b>${escapeHtml(psono)}</b><br>Product ID: <b>#${escapeHtml(productId)}</b><br>Target: <b>${tableLabel}</b>`,
        icon: targetStatus === 'Cancel' ? 'warning' : 'question',
        showCancelButton: true,
        confirmButtonColor: actionColor,
        cancelButtonColor: '#334155',
        confirmButtonText: `Yes, Set to ${targetStatus}`
    });

    if (!confirm.isConfirmed) return;

    Swal.fire({
        title: 'Updating Status...',
        text: 'Please wait while database tables are updated',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        const formData = new FormData();
        formData.append('action', 'update_single');
        formData.append('psono', psono);
        formData.append('product_id', productId);
        formData.append('target_status', targetStatus);
        formData.append('target_table', targetTable);

        const response = await fetch(window.location.pathname, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        if (result.success) {
            await Swal.fire({
                icon: 'success',
                title: 'Updated!',
                text: result.message,
                timer: 1500,
                showConfirmButton: false
            });
            location.reload();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Update Failed',
                text: result.message || 'Unknown error occurred.'
            });
        }
    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'Request Failed',
            text: err.message
        });
    }
}

async function bulkUpdate(targetStatus) {
    const count = selectedKeys.size;
    if (count === 0) return;

    const actionColor = targetStatus === 'Cancel' ? '#ef4444' : '#10b981';
    const confirm = await Swal.fire({
        title: `Bulk Update ${count} Item(s)?`,
        html: `Are you sure you want to mark all <b>${count}</b> selected item(s) as <b>${targetStatus}</b> across all tables?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: actionColor,
        cancelButtonColor: '#334155',
        confirmButtonText: `Yes, Mark All as ${targetStatus}`
    });

    if (!confirm.isConfirmed) return;

    Swal.fire({
        title: 'Updating Items...',
        text: `Applying ${targetStatus} to ${count} item(s)`,
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    const items = [];
    selectedKeys.forEach(k => {
        const parts = k.split('___');
        items.push({ psono: parts[0], product_id: parts[1], target_table: 'all' });
    });

    try {
        const formData = new FormData();
        formData.append('action', 'update_bulk');
        formData.append('target_status', targetStatus);
        formData.append('items', JSON.stringify(items));

        const response = await fetch(window.location.pathname, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        if (result.success) {
            await Swal.fire({
                icon: 'success',
                title: 'Bulk Update Completed',
                text: result.message
            });
            location.reload();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Bulk Update Error',
                text: result.message || 'Failed to update items.'
            });
        }
    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'Request Error',
            text: err.message
        });
    }
}

async function syncAllMismatches(targetStatus) {
    const count = allMismatchesData.length;
    if (count === 0) {
        Swal.fire({
            icon: 'info',
            title: 'No Mismatches',
            text: 'There are currently no detected mismatches to synchronize.'
        });
        return;
    }

    const actionColor = targetStatus === 'Cancel' ? '#ef4444' : '#10b981';
    const confirm = await Swal.fire({
        title: `Synchronize ALL ${count} Mismatches?`,
        html: `This will update all <b>${count}</b> mismatched items across <code>krf_provisional_sales_order_items</code>, <code>krf_sales_order_items</code>, <code>krf_productionorder_consumed</code>, and <code>krf_readystock_allocation</code> to <b>${targetStatus}</b>.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: actionColor,
        cancelButtonColor: '#334155',
        confirmButtonText: `Yes, Synchronize to ${targetStatus}`
    });

    if (!confirm.isConfirmed) return;

    Swal.fire({
        title: 'Synchronizing Database...',
        text: `Processing ${count} mismatched items...`,
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        const formData = new FormData();
        formData.append('action', 'sync_all_mismatches');
        formData.append('target_status', targetStatus);
        formData.append('mismatch_items', JSON.stringify(allMismatchesData));

        const response = await fetch(window.location.pathname, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        if (result.success) {
            await Swal.fire({
                icon: 'success',
                title: 'Synchronization Complete',
                text: result.message
            });
            location.reload();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Sync Error',
                text: result.message || 'Error occurred during synchronization.'
            });
        }
    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'Request Failed',
            text: err.message
        });
    }
}

async function syncParentCancelledMismatches() {
    const count = parentCancelMismatchesData.length;
    if (count === 0) {
        Swal.fire({
            icon: 'info',
            title: 'No Mismatches Found',
            text: 'All items under cancelled orders are already marked cancelled everywhere!'
        });
        return;
    }

    const confirm = await Swal.fire({
        title: `Cancel All ${count} Item(s) Everywhere?`,
        html: `These items belong to Orders whose status in "Order & Client" is <b>Cancelled</b>, but items/children are still Active.<br><br>This will mark them <b>Cancelled</b> in:<br>
        1. <code>krf_provisional_sales_order_items</code><br>
        2. <code>krf_sales_order_items</code><br>
        3. <code>krf_productionorder_consumed</code><br>
        4. <code>krf_readystock_allocation</code><br>
        5. <code>krf_polyhouse_sowingschedule</code> <i>(skipped if Client is Trader)</i><br>
        6. <code>krf_production_stockorder</code> <i>(skipped if Client is Trader)</i><br><br>
        <span style="font-size: 11.5px; color: #a78bfa;"><i class="fa-solid fa-circle-info me-1"></i>Trader clients (is_trader = 1) retain their production and sowing schedules!</span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#334155',
        confirmButtonText: `Yes, Mark All ${count} Cancelled Everywhere`
    });

    if (!confirm.isConfirmed) return;

    Swal.fire({
        title: 'Cancelling Items Everywhere...',
        text: `Processing ${count} item(s)...`,
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        const formData = new FormData();
        formData.append('action', 'sync_all_mismatches');
        formData.append('target_status', 'Cancel');
        formData.append('mismatch_items', JSON.stringify(parentCancelMismatchesData));

        const response = await fetch(window.location.pathname, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        if (result.success) {
            await Swal.fire({
                icon: 'success',
                title: 'Cancelled Everywhere!',
                text: result.message
            });
            location.reload();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Sync Error',
                text: result.message || 'Error occurred.'
            });
        }
    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'Request Failed',
            text: err.message
        });
    }
}
</script>

</body>
</html>
