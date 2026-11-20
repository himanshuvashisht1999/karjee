<?php
/**
 * Standalone Sales Order, Ready Stock Allocation, Invoice & Polyhouse Tray Auditor & Correction Tool
 * Location: krf-orderandsales/temp/rechek_pro_aloc_disp_ply.php
 * Only requires connection.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Establish database connection
if (file_exists(__DIR__ . '/../../connection.php')) {
    require_once __DIR__ . '/../../connection.php';
} elseif (file_exists('../../connection.php')) {
    require_once '../../connection.php';
} else {
    require_once 'connection.php';
}

$db = (isset($krfconn) && $krfconn instanceof mysqli) ? $krfconn : $conn;
if ($db instanceof mysqli) {
    @$db->set_charset("utf8mb4");
}

@session_start();
$currentUser = $_SESSION['loginname'] ?? $_SESSION['username'] ?? $_SESSION['loginid'] ?? 'Admin Correction Auditor';

// -------------------------------------------------------------------------
// HELPER FUNCTIONS
// -------------------------------------------------------------------------

/**
 * Resolve parent batch for RSO/RTS stock order numbers
 */
if (!function_exists('resolveRsoParentBatch')) {
    function resolveRsoParentBatch($db, $stockOrderNo) {
        $stockOrderNo = trim($stockOrderNo);
        if (empty($stockOrderNo)) return null;

        if (strncasecmp($stockOrderNo, 'RSO-', 4) !== 0 && 
            strncasecmp($stockOrderNo, 'RSO', 3) !== 0 && 
            strncasecmp($stockOrderNo, 'RTS-', 4) !== 0) {
            return null;
        }

        $escStk = $db->real_escape_string($stockOrderNo);
        $psoQry = $db->query("SELECT po_number, remarks, productid FROM krf_production_stockorder WHERE stockorder_no = '{$escStk}' LIMIT 1");
        if (!$psoQry || $psoQry->num_rows === 0) return null;
        $psoRow = $psoQry->fetch_assoc();
        $poNumber = trim($psoRow['po_number'] ?? '');
        $remarks = trim($psoRow['remarks'] ?? '');

        $candidates = [];
        if (!empty($poNumber)) {
            $candidates[] = $poNumber;
            if (strncasecmp($poNumber, 'KFP', 3) !== 0) {
                $candidates[] = 'KFP' . $poNumber;
                $candidates[] = 'KFP-' . $poNumber;
            } else {
                $cleanPo = preg_replace('/^KFP[-=]?/i', '', $poNumber);
                if (!empty($cleanPo)) {
                    $candidates[] = 'KFP' . $cleanPo;
                    $candidates[] = $cleanPo;
                }
            }
        }
        $suffix = preg_replace('/^(RSO|RTS)[-]?/i', '', $stockOrderNo);
        if (!empty($suffix)) {
            $candidates[] = $suffix;
            if (strncasecmp($suffix, 'KFP', 3) !== 0) $candidates[] = 'KFP' . $suffix;
        }
        if (preg_match('/(?:Bill\s*No|Batch\s*No|Parent\s*Batch)\s*[:\-–]?\s*([A-Za-z0-9_\-]+)/i', $remarks, $m)) {
            $b = trim($m[1]);
            if (!empty($b)) {
                $candidates[] = $b;
                if (strncasecmp($b, 'KFP', 3) !== 0) $candidates[] = 'KFP' . $b;
            }
        }
        $candidates = array_values(array_unique(array_filter($candidates)));

        foreach ($candidates as $cand) {
            $escCand = $db->real_escape_string($cand);
            $chkPpd = $db->query("
                SELECT COUNT(*) as total_trays 
                FROM krf_polyhouse_plant_details ppd
                WHERE ppd.stockorder_no = '{$escCand}'
                  AND (ppd.tedit IS NULL OR ppd.tedit = '')
            ");
            if ($chkPpd && ($ppdRow = $chkPpd->fetch_assoc()) && intval($ppdRow['total_trays'] ?? 0) > 0) {
                return ['parent_batch' => $cand, 'total_trays' => intval($ppdRow['total_trays'])];
            }
        }

        return null;
    }
}

/**
 * Correct allocatedbed JSON array so plants match target allocation quantity
 */
function correctBedJsonToQty($rawJson, $targetPlants) {
    $targetPlants = intval($targetPlants);
    $beds = json_decode((string)($rawJson ?? ''), true);
    if (!is_array($beds) || empty($beds)) {
        return json_encode([[
            "polyhouse_id" => "",
            "polyhouse_name" => "Main Polyhouse",
            "bed_name" => "Bed-1",
            "tray_count" => (int)ceil($targetPlants / 126),
            "plants" => (int)$targetPlants,
            "total_plants" => (int)$targetPlants,
            "extra_plants" => 0
        ]]);
    }

    $remaining = $targetPlants;
    $numBeds = count($beds);

    foreach ($beds as $idx => &$bed) {
        $cavity = !empty($bed['cavity_count']) ? intval($bed['cavity_count']) : 126;
        if ($cavity <= 0) {
            $oldTrays = intval($bed['tray_count'] ?? 0);
            $oldPlants = intval($bed['plants'] ?? 0);
            $cavity = ($oldTrays > 0 && $oldPlants > 0) ? (int)round($oldPlants / $oldTrays) : 126;
        }
        if ($cavity <= 0) $cavity = 126;

        if ($idx === $numBeds - 1) {
            $bedPlants = max(0, $remaining);
        } else {
            $oldBedPlants = intval($bed['plants'] ?? 0);
            $bedPlants = min($remaining, $oldBedPlants > 0 ? $oldBedPlants : $remaining);
        }
        $remaining -= $bedPlants;

        $trayCount = ($bedPlants > 0) ? (int)ceil($bedPlants / $cavity) : 0;
        $bed['plants'] = $bedPlants;
        $bed['tray_count'] = $trayCount;
        if (isset($bed['total_plants'])) {
            $bed['total_plants'] = $bedPlants;
        }
        if (isset($bed['extra_plants'])) {
            $bed['extra_plants'] = 0;
        }
    }
    unset($bed);

    return json_encode($beds);
}

/**
 * Mark/update polyhouse plant trays for an order/batch
 */
function markPolyhousePlantTrays($db, $psono, $productId, $clientId, $stockOrderNo, $targetDispatchQty, $loginUser, $dispatchAt = null) {
    $psono = trim($psono);
    $productId = trim($productId);
    $clientId = trim($clientId);
    $stockOrderNo = trim($stockOrderNo);
    $targetDispatchQty = intval($targetDispatchQty);
    $loginUser = trim($loginUser ?: 'Admin Correction Auditor');
    $dispatchAt = $dispatchAt ?: date('Y-m-d H:i:s');

    if (empty($psono) || empty($stockOrderNo) || $targetDispatchQty <= 0) {
        return ['success' => false, 'message' => 'Missing required parameters for tray marking.'];
    }

    // Determine batch candidates: current batch and its parent if RSO/RTS
    $batches = [$stockOrderNo];
    if (strncasecmp($stockOrderNo, 'RSO-', 4) === 0 || strncasecmp($stockOrderNo, 'RTS-', 4) === 0 || strncasecmp($stockOrderNo, 'RSO', 3) === 0) {
        $pInfo = resolveRsoParentBatch($db, $stockOrderNo);
        if ($pInfo && !empty($pInfo['parent_batch'])) {
            $batches[] = $pInfo['parent_batch'];
        }
    }
    $batches = array_values(array_unique(array_filter($batches)));
    $escBatches = "'" . implode("','", array_map([$db, 'real_escape_string'], $batches)) . "'";
    $escPso = $db->real_escape_string($psono);

    $updatedTraysCount = 0;
    $newlyMarkedCount = 0;

    // 1. Update any trays that are ALREADY tagged with this dispd_agst_psono
    $chkQ = $db->query("
        SELECT polyhouseplantid, qtypertray, status, dispatchqty, dispatchby, dispatchat, dispd_agst_clientid, dispd_agst_stockorderno
        FROM krf_polyhouse_plant_details
        WHERE dispd_agst_psono = '{$escPso}'
          AND (stockorder_no IN ({$escBatches}) OR dispd_agst_stockorderno IN ({$escBatches}) OR productid = '{$productId}')
    ");
    $alreadyTaggedIds = [];
    $alreadyTaggedPlants = 0;
    if ($chkQ) {
        while ($t = $chkQ->fetch_assoc()) {
            $pid = intval($t['polyhouseplantid']);
            $alreadyTaggedIds[] = $pid;
            $tCap = intval($t['dispatchqty'] ?: ($t['qtypertray'] ?: 126));
            $alreadyTaggedPlants += $tCap;

            // Check if any required field is missing on this tray
            $needsUpdate = (
                strtolower(trim($t['status'] ?? '')) !== 'final dispatch' ||
                empty($t['dispatchqty']) ||
                empty($t['dispatchby']) ||
                empty($t['dispatchat']) ||
                empty($t['dispd_agst_clientid']) ||
                empty($t['dispd_agst_stockorderno'])
            );

            if ($needsUpdate) {
                $updSql = "
                    UPDATE krf_polyhouse_plant_details
                    SET status = 'Final Dispatch',
                        workingstatus = 'Final Dispatch',
                        dispatchqty = CASE WHEN CAST(IFNULL(dispatchqty, 0) AS SIGNED) > 0 THEN dispatchqty ELSE IFNULL(qtypertray, 126) END,
                        dispatchby = CASE WHEN dispatchby IS NOT NULL AND dispatchby != '' THEN dispatchby ELSE '{$db->real_escape_string($loginUser)}' END,
                        dispatchat = CASE WHEN dispatchat IS NOT NULL THEN dispatchat ELSE '{$dispatchAt}' END,
                        dispd_agst_clientid = CASE WHEN dispd_agst_clientid IS NOT NULL AND dispd_agst_clientid != '' THEN dispd_agst_clientid ELSE '{$db->real_escape_string($clientId)}' END,
                        dispd_agst_stockorderno = CASE WHEN dispd_agst_stockorderno IS NOT NULL AND dispd_agst_stockorderno != '' THEN dispd_agst_stockorderno ELSE stockorder_no END,
                        updated_at = NOW(),
                        updated_by = '{$db->real_escape_string($loginUser)}'
                    WHERE polyhouseplantid = {$pid}
                ";
                $db->query($updSql);
                $updatedTraysCount++;
            }
        }
    }

    // 2. If already tagged plants are less than targetDispatchQty, select and mark additional available active trays
    $reqTotalTrays = (int)ceil($targetDispatchQty / 126);
    $needMoreTrays = max(0, $reqTotalTrays - count($alreadyTaggedIds));

    if ($needMoreTrays > 0) {
        $findSql = "
            SELECT polyhouseplantid, qtypertray
            FROM krf_polyhouse_plant_details
            WHERE stockorder_no IN ({$escBatches})
              AND (dispd_agst_psono IS NULL OR dispd_agst_psono = '')
              AND LOWER(TRIM(status)) IN ('readytosale', 'openstock', 'polyhouse', 'allocated', 'readytodispatch', 'readytoallocated', 'loading', 'loaded', 'final dispatch')
            ORDER BY 
                CASE WHEN LOWER(TRIM(status)) = 'final dispatch' THEN 1 ELSE 2 END,
                polyhouseplantid ASC
            LIMIT {$needMoreTrays}
        ";
        $findRes = $db->query($findSql);
        $newTargetIds = [];
        if ($findRes) {
            while ($r = $findRes->fetch_assoc()) {
                $newTargetIds[] = intval($r['polyhouseplantid']);
            }
        }

        if (!empty($newTargetIds)) {
            $inList = implode(',', $newTargetIds);
            $markSql = "
                UPDATE krf_polyhouse_plant_details
                SET status = 'Final Dispatch',
                    workingstatus = 'Final Dispatch',
                    dispatchqty = IFNULL(qtypertray, 126),
                    dispatchby = '{$db->real_escape_string($loginUser)}',
                    dispatchat = '{$dispatchAt}',
                    dispd_agst_clientid = '{$db->real_escape_string($clientId)}',
                    dispd_agst_stockorderno = stockorder_no,
                    dispd_agst_psono = '{$escPso}',
                    updated_at = NOW(),
                    updated_by = '{$db->real_escape_string($loginUser)}'
                WHERE polyhouseplantid IN ({$inList})
            ";
            $db->query($markSql);
            $newlyMarkedCount = $db->affected_rows;
        }
    }

    return [
        'success' => true,
        'alreadyTaggedCount' => count($alreadyTaggedIds),
        'updatedFieldsCount' => $updatedTraysCount,
        'newlyMarkedCount' => $newlyMarkedCount,
        'totalNowMarked' => count($alreadyTaggedIds) + $newlyMarkedCount,
        'batches' => $batches
    ];
}

/**
 * Revert any extra marked polyhouse trays back to ReadyToSale with dispatch fields set to NULL
 */
function revertExtraPolyhouseTrays($db, $psono, $productId, $stockOrderNo = '', $currentUser = 'Admin Correction Auditor') {
    $psono = trim((string)$psono);
    $productId = trim((string)$productId);
    $stockOrderNo = trim((string)$stockOrderNo);

    if (empty($psono) || empty($productId)) {
        return ['success' => false, 'message' => 'Missing psono or product_id.', 'revertedCount' => 0];
    }

    // 1. Calculate how many plants/trays are legitimately dispatched from sales_invoice_items
    $invQ = $db->query("
        SELECT COALESCE(SUM(quantity), 0) AS total_inv_disp
        FROM sales_invoice_items
        WHERE psono = '{$db->real_escape_string($psono)}' 
          AND productid = '{$db->real_escape_string($productId)}'
          AND LOWER(IFNULL(status, '')) = 'dispatched'
          AND IFNULL(tedit, '') = ''
    ");
    $totalInvDisp = intval($invQ ? $invQ->fetch_assoc()['total_inv_disp'] ?? 0 : 0);
    $reqTrays = ($totalInvDisp > 0) ? (int)ceil($totalInvDisp / 126) : 0;

    // 2. Query all trays currently marked with dispd_agst_psono = psono
    $stockCond = !empty($stockOrderNo) ? "AND (stockorder_no = '{$db->real_escape_string($stockOrderNo)}' OR dispd_agst_stockorderno = '{$db->real_escape_string($stockOrderNo)}')" : "";
    $chkTrays = $db->query("
        SELECT polyhouseplantid
        FROM krf_polyhouse_plant_details
        WHERE dispd_agst_psono = '{$db->real_escape_string($psono)}'
          AND (productid = '{$db->real_escape_string($productId)}' {$stockCond})
          AND IFNULL(tedit, '') = ''
        ORDER BY polyhouseplantid DESC
    ");
    $markedTrayIds = [];
    if ($chkTrays) {
        while ($r = $chkTrays->fetch_assoc()) {
            $markedTrayIds[] = intval($r['polyhouseplantid']);
        }
    }

    $totalMarked = count($markedTrayIds);
    $extraCount = max(0, $totalMarked - $reqTrays);

    $revertedCount = 0;
    if ($extraCount > 0) {
        $extraIds = array_slice($markedTrayIds, 0, $extraCount);
        $inList = implode(',', $extraIds);
        $db->query("
            UPDATE krf_polyhouse_plant_details
            SET status = 'ReadyToSale',
                workingstatus = 'ReadyToSale',
                dispatchqty = NULL,
                dispatchby = NULL,
                dispatchat = NULL,
                dispd_agst_clientid = NULL,
                dispd_agst_stockorderno = NULL,
                dispd_agst_psono = NULL,
                updated_at = NOW(),
                updated_by = '{$db->real_escape_string($currentUser)}'
            WHERE polyhouseplantid IN ({$inList})
              AND IFNULL(tedit, '') = ''
        ");
        $revertedCount = $db->affected_rows;
    }

    return [
        'success' => true,
        'totalMarked' => $totalMarked,
        'requiredTrays' => $reqTrays,
        'revertedCount' => $revertedCount
    ];
}

// -------------------------------------------------------------------------
// AJAX / POST REQUEST HANDLERS
// -------------------------------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_POST['action'];

    // Action: Mark Duplicate Allocation with tedit='1' & Revert Any Extra Marked Polyhouse Trays
    if ($action === 'mark_allocation_tedit') {
        $rsaId = intval($_POST['rsa_id'] ?? 0);
        $psono = trim((string)($_POST['psono'] ?? ''));
        $productId = trim((string)($_POST['product_id'] ?? ''));

        if ($rsaId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid Allocation Record ID.']);
            exit;
        }

        $db->begin_transaction();
        try {
            // 1. Fetch the allocation record
            $q = $db->query("SELECT * FROM krf_readystock_allocation WHERE id = {$rsaId} LIMIT 1");
            if (!$q || $q->num_rows === 0) {
                throw new Exception("Allocation record #{$rsaId} not found.");
            }
            $rsa = $q->fetch_assoc();
            if (empty($psono)) $psono = trim((string)($rsa['psono'] ?? ''));
            if (empty($productId)) $productId = trim((string)($rsa['productid'] ?? ''));
            $stockOrderNo = trim((string)($rsa['stockorder_no'] ?? ''));

            // 2. Mark this allocation row with tedit='1', status='cancel', and teditremark
            $updRsa = $db->prepare("
                UPDATE krf_readystock_allocation 
                SET tedit = '1', 
                    status = 'cancel',
                    teditremark = CONCAT(COALESCE(teditremark, ''), ' [Duplicate allocation marked via Auditor]'),
                    updated_at = NOW(),
                    updated_by = ?
                WHERE id = ?
            ");
            $updRsa->bind_param("si", $currentUser, $rsaId);
            $updRsa->execute();
            $updRsa->close();

            // 3. Recalculate remaining active allocations for this psono and product_id
            $qActive = $db->query("
                SELECT COALESCE(SUM(allocate_ordqty), 0) AS total_active_alloc
                FROM krf_readystock_allocation
                WHERE psono = '{$db->real_escape_string($psono)}' 
                  AND productid = '{$db->real_escape_string($productId)}'
                  AND IFNULL(tedit, '') = '' 
                  AND LOWER(IFNULL(status, '')) != 'cancel'
            ");
            $totalActiveAlloc = intval($qActive ? $qActive->fetch_assoc()['total_active_alloc'] ?? 0 : 0);

            // 4. Update krf_sales_order_items allocate_ordqty to remaining active allocations
            if (!empty($psono) && !empty($productId)) {
                $updSoi = $db->prepare("
                    UPDATE krf_sales_order_items
                    SET allocate_ordqty = ?,
                        reservedqty = ?,
                        balancereservedqty = GREATEST(0, CAST(quantity AS SIGNED) - ?),
                        healthyplant = ?,
                        status = CASE WHEN ? >= quantity THEN 'Allocated' WHEN ? > 0 THEN 'Partial Allocated' ELSE 'Confirmed' END,
                        ammendedby = ?,
                        updateamended = NOW()
                    WHERE psono = ? AND (product_id = ? OR CAST(product_id AS CHAR) = ?)
                      AND IFNULL(tedit, '') = ''
                ");
                $updSoi->bind_param("iiiiisssss", $totalActiveAlloc, $totalActiveAlloc, $totalActiveAlloc, $totalActiveAlloc, $totalActiveAlloc, $totalActiveAlloc, $currentUser, $psono, $productId, $productId);
                $updSoi->execute();
                $updSoi->close();
            }

            // 5. Check and revert any extra marked trays in krf_polyhouse_plant_details
            $revertRes = revertExtraPolyhouseTrays($db, $psono, $productId, $stockOrderNo, $currentUser);

            $db->commit();
            $msg = "Duplicate allocation #{$rsaId} marked with tedit='1'. Remaining active allocation: " . number_format($totalActiveAlloc) . " plants.";
            if ($revertRes['revertedCount'] > 0) {
                $msg .= " Reverted {$revertRes['revertedCount']} extra marked polyhouse trays to ReadyToSale (dispatch fields reset to NULL).";
            }

            echo json_encode([
                'success' => true,
                'message' => $msg,
                'totalActiveAlloc' => $totalActiveAlloc,
                'revertedTraysCount' => $revertRes['revertedCount']
            ]);
            exit;
        } catch (Exception $e) {
            $db->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    // Action: Revert Extra Trays in krf_polyhouse_plant_details
    if ($action === 'revert_extra_trays') {
        $psono = trim((string)($_POST['psono'] ?? ''));
        $productId = trim((string)($_POST['product_id'] ?? ''));
        $stockOrderNo = trim((string)($_POST['stockorder_no'] ?? ''));

        if (empty($psono) || empty($productId)) {
            echo json_encode(['success' => false, 'message' => 'Missing psono or product_id.']);
            exit;
        }

        $db->begin_transaction();
        try {
            $revertRes = revertExtraPolyhouseTrays($db, $psono, $productId, $stockOrderNo, $currentUser);
            $db->commit();
            echo json_encode([
                'success' => true,
                'message' => "Reverted {$revertRes['revertedCount']} extra polyhouse tray(s) back to ReadyToSale with dispatch fields reset to NULL. Required trays: {$revertRes['requiredTrays']}, Total currently marked: " . ($revertRes['totalMarked'] - $revertRes['revertedCount']),
                'details' => $revertRes
            ]);
            exit;
        } catch (Exception $e) {
            $db->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    // Action 1: Fix Bed Plants Mismatch in krf_readystock_allocation
    if ($action === 'fix_bed_plants_mismatch') {
        $rsaId = intval($_POST['rsa_id'] ?? 0);
        $fixMode = $_POST['fix_mode'] ?? 'bed_to_alloc'; // 'bed_to_alloc' or 'alloc_to_bed'
        $customQty = isset($_POST['custom_qty']) ? intval($_POST['custom_qty']) : null;

        if ($rsaId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid Allocation Record ID.']);
            exit;
        }

        $db->begin_transaction();
        try {
            $q = $db->query("SELECT * FROM krf_readystock_allocation WHERE id = {$rsaId} LIMIT 1");
            if (!$q || $q->num_rows === 0) {
                throw new Exception("Allocation record #{$rsaId} not found.");
            }
            $rsa = $q->fetch_assoc();
            $psono = trim($rsa['psono'] ?? '');
            $productId = trim($rsa['productid'] ?? '');

            if ($fixMode === 'bed_to_alloc') {
                // Adjust allocatedbed JSON so plants match allocate_ordqty
                $targetPlants = ($customQty !== null && $customQty > 0) ? $customQty : intval($rsa['allocate_ordqty']);
                $newBedJson = correctBedJsonToQty($rsa['allocatedbed'], $targetPlants);

                $stmt = $db->prepare("UPDATE krf_readystock_allocation SET allocatedbed = ?, updated_at = NOW(), updated_by = ? WHERE id = ?");
                $stmt->bind_param("ssi", $newBedJson, $currentUser, $rsaId);
                $stmt->execute();
                $stmt->close();

                $msg = "Successfully corrected allocatedbed JSON for allocation #{$rsaId} to match {$targetPlants} plants.";
            } elseif ($fixMode === 'alloc_to_bed') {
                // Adjust allocate_ordqty to match the plants in allocatedbed JSON
                $beds = json_decode((string)($rsa['allocatedbed'] ?? ''), true);
                $sumPlants = 0;
                if (is_array($beds)) {
                    foreach ($beds as $b) {
                        $p = isset($b['plants']) ? intval($b['plants']) : (isset($b['total_plants']) ? intval($b['total_plants']) : 0);
                        $sumPlants += $p;
                    }
                }
                if ($sumPlants <= 0) {
                    throw new Exception("Cannot read valid plant count from allocatedbed JSON.");
                }

                $stmt = $db->prepare("UPDATE krf_readystock_allocation SET allocate_ordqty = ?, healthyplant = ?, updated_at = NOW(), updated_by = ? WHERE id = ?");
                $stmt->bind_param("iisi", $sumPlants, $sumPlants, $currentUser, $rsaId);
                $stmt->execute();
                $stmt->close();

                // Also update krf_sales_order_items allocate_ordqty if linked
                if (!empty($psono) && !empty($productId)) {
                    $updSoi = $db->prepare("
                        UPDATE krf_sales_order_items 
                        SET allocate_ordqty = ?, 
                            reservedqty = ?, 
                            balancereservedqty = GREATEST(0, CAST(quantity AS SIGNED) - ?),
                            healthyplant = ?,
                            ammendedby = ?, 
                            updateamended = NOW() 
                        WHERE psono = ? AND (product_id = ? OR CAST(product_id AS CHAR) = ?)
                    ");
                    $updSoi->bind_param("iiiissss", $sumPlants, $sumPlants, $sumPlants, $sumPlants, $currentUser, $psono, $productId, $productId);
                    $updSoi->execute();
                    $updSoi->close();
                }

                $msg = "Successfully updated allocate_ordqty to {$sumPlants} plants to match bed plants.";
            } else {
                throw new Exception("Unknown fix mode: {$fixMode}");
            }

            $db->commit();
            echo json_encode(['success' => true, 'message' => $msg]);
            exit;
        } catch (Exception $e) {
            $db->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    // Action 2: Sync Sales Order & Allocation Dispatch with Dispatched Invoice
    if ($action === 'fix_dispatch_sync') {
        $psono = trim($_POST['psono'] ?? '');
        $productId = trim($_POST['product_id'] ?? '');

        if (empty($psono) || empty($productId)) {
            echo json_encode(['success' => false, 'message' => 'Missing psono or product_id.']);
            exit;
        }

        $db->begin_transaction();
        try {
            // 1. Calculate truth from sales_invoice_items
            $invStmt = $db->prepare("
                SELECT SUM(CASE WHEN LOWER(TRIM(status)) = 'dispatched' THEN quantity ELSE 0 END) AS total_dispatched_qty,
                       SUM(quantity) AS total_inv_qty,
                       GROUP_CONCAT(DISTINCT invoiceno SEPARATOR ', ') AS invoices
                FROM sales_invoice_items
                WHERE psono = ? AND productid = ? AND productid != 'BOX_PACKING' AND LOWER(IFNULL(status, '')) != 'cancelled'
            ");
            $invStmt->bind_param("ss", $psono, $productId);
            $invStmt->execute();
            $invData = $invStmt->get_result()->fetch_assoc();
            $invStmt->close();

            $totDisp = intval($invData['total_dispatched_qty'] ?? 0);
            $totInv = intval($invData['total_inv_qty'] ?? 0);

            if ($totDisp <= 0 && $totInv <= 0) {
                throw new Exception("No active commercial invoices found for order {$psono}, product {$productId}.");
            }

            // 2. Update krf_sales_order_items
            $effDispQty = ($totDisp > 0) ? $totDisp : $totInv;
            $soiStatus = ($effDispQty > 0) ? 'Dispatched' : 'Allocated';

            $updSoi = $db->prepare("
                UPDATE krf_sales_order_items
                SET dispatchedqty = ?,
                    invoiceqty = ?,
                    status = CASE 
                        WHEN ? >= quantity THEN 'Dispatched' 
                        WHEN ? > 0 THEN 'Partial Dispatched' 
                        ELSE status 
                    END,
                    ammendedby = ?,
                    updateamended = NOW()
                WHERE psono = ? AND (product_id = ? OR CAST(product_id AS CHAR) = ?)
                  AND IFNULL(tedit, '') = ''
            ");
            $updSoi->bind_param("iiiissss", $effDispQty, $totInv, $effDispQty, $effDispQty, $currentUser, $psono, $productId, $productId);
            $updSoi->execute();
            $soiRows = $updSoi->affected_rows;
            $updSoi->close();

            // 3. Update krf_readystock_allocation
            $updRsa = $db->prepare("
                UPDATE krf_readystock_allocation
                SET dispatchqty = CASE WHEN allocate_ordqty > 0 THEN LEAST(allocate_ordqty, ?) ELSE ? END,
                    status = 'dispatched',
                    updated_by = ?,
                    updated_at = NOW()
                WHERE psono = ? AND (productid = ? OR CAST(productid AS CHAR) = ?)
                  AND IFNULL(tedit, '') = ''
            ");
            $updRsa->bind_param("iissss", $effDispQty, $effDispQty, $currentUser, $psono, $productId, $productId);
            $updRsa->execute();
            $rsaRows = $updRsa->affected_rows;
            $updRsa->close();

            $db->commit();
            echo json_encode([
                'success' => true,
                'message' => "Successfully synced dispatch quantities ({$effDispQty} plants) across sales order & allocation table!"
            ]);
            exit;
        } catch (Exception $e) {
            $db->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    // Action 3: Mark/Update Polyhouse Plant Trays
    if ($action === 'fix_polyhouse_trays') {
        $psono = trim($_POST['psono'] ?? '');
        $productId = trim($_POST['product_id'] ?? '');
        $stockOrderNo = trim($_POST['stockorder_no'] ?? '');
        $clientId = trim($_POST['client_id'] ?? '');
        $targetQty = intval($_POST['target_qty'] ?? 0);

        if (empty($psono) || empty($productId)) {
            echo json_encode(['success' => false, 'message' => 'Missing psono or product_id.']);
            exit;
        }

        // If targetQty not passed, get it from sales_invoice_items or krf_sales_order_items
        if ($targetQty <= 0) {
            $invQ = $db->query("
                SELECT SUM(quantity) AS qty 
                FROM sales_invoice_items 
                WHERE psono = '{$db->real_escape_string($psono)}' 
                  AND productid = '{$db->real_escape_string($productId)}'
                  AND LOWER(IFNULL(status, '')) = 'dispatched'
            ");
            if ($invQ && $row = $invQ->fetch_assoc()) {
                $targetQty = intval($row['qty'] ?? 0);
            }
        }
        if ($targetQty <= 0) {
            $soiQ = $db->query("
                SELECT dispatchedqty, quantity, clientid, stockorder_no 
                FROM krf_sales_order_items 
                WHERE psono = '{$db->real_escape_string($psono)}' 
                  AND product_id = '{$db->real_escape_string($productId)}' 
                LIMIT 1
            ");
            if ($soiQ && $row = $soiQ->fetch_assoc()) {
                $targetQty = intval($row['dispatchedqty'] ?: $row['quantity']);
                if (empty($clientId) && !empty($row['clientid'])) $clientId = trim($row['clientid']);
                if (empty($stockOrderNo) && !empty($row['stockorder_no'])) $stockOrderNo = trim($row['stockorder_no']);
            }
        }

        // If stockOrderNo still empty, look up in krf_readystock_allocation
        if (empty($stockOrderNo)) {
            $rsaQ = $db->query("
                SELECT stockorder_no, allocate_stockorder_no, clientid 
                FROM krf_readystock_allocation 
                WHERE psono = '{$db->real_escape_string($psono)}' 
                  AND productid = '{$db->real_escape_string($productId)}' 
                ORDER BY id DESC LIMIT 1
            ");
            if ($rsaQ && $row = $rsaQ->fetch_assoc()) {
                $stockOrderNo = trim($row['allocate_stockorder_no'] ?: $row['stockorder_no']);
                if (empty($clientId) && !empty($row['clientid'])) $clientId = trim($row['clientid']);
            }
        }

        $db->begin_transaction();
        try {
            $res = markPolyhousePlantTrays($db, $psono, $productId, $clientId, $stockOrderNo, $targetQty, $currentUser);
            if (!$res['success']) {
                throw new Exception($res['message']);
            }
            $db->commit();
            echo json_encode([
                'success' => true,
                'message' => "Polyhouse trays updated: {$res['updatedFieldsCount']} field-fixed, {$res['newlyMarkedCount']} newly marked as Final Dispatch (Total {$res['totalNowMarked']} trays).",
                'details' => $res
            ]);
            exit;
        } catch (Exception $e) {
            $db->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    // Action 4: Auto-Fix All Issues for an Item in One Click
    if ($action === 'fix_all_in_item') {
        $soiId = intval($_POST['soi_id'] ?? 0);
        $psono = trim($_POST['psono'] ?? '');
        $productId = trim($_POST['product_id'] ?? '');

        if ($soiId <= 0 && (empty($psono) || empty($productId))) {
            echo json_encode(['success' => false, 'message' => 'Missing item identifier.']);
            exit;
        }

        $db->begin_transaction();
        try {
            $log = [];

            // 1. Fetch Sales Order Item
            $soi = null;
            if ($soiId > 0) {
                $q = $db->query("SELECT * FROM krf_sales_order_items WHERE id = {$soiId} LIMIT 1");
                if ($q && $q->num_rows > 0) $soi = $q->fetch_assoc();
            }
            if (!$soi && !empty($psono) && !empty($productId)) {
                $q = $db->query("SELECT * FROM krf_sales_order_items WHERE psono = '{$db->real_escape_string($psono)}' AND product_id = '{$db->real_escape_string($productId)}' LIMIT 1");
                if ($q && $q->num_rows > 0) $soi = $q->fetch_assoc();
            }

            if ($soi) {
                $soiId = intval($soi['id']);
                $psono = trim($soi['psono']);
                $productId = trim($soi['product_id']);
                $clientId = trim($soi['clientid']);
                $orderQty = intval($soi['quantity']);
            } else {
                $clientId = '';
                $orderQty = 0;
            }

            // 2. Fetch RSA rows for this psono and product
            $rsaList = [];
            $rsaQ = $db->query("SELECT * FROM krf_readystock_allocation WHERE psono = '{$db->real_escape_string($psono)}' AND productid = '{$db->real_escape_string($productId)}' AND IFNULL(tedit, '') = '' AND LOWER(IFNULL(status, '')) != 'cancel'");
            if ($rsaQ) {
                while ($r = $rsaQ->fetch_assoc()) $rsaList[] = $r;
            }

            // Step A: Fix Bed Plants Mismatch in RSA
            $totalRsaAlloc = 0;
            $stockOrderNoForTrays = '';
            foreach ($rsaList as $rsa) {
                $rsaId = intval($rsa['id']);
                $allocQty = intval($rsa['allocate_ordqty']);
                $totalRsaAlloc += $allocQty;
                if (empty($stockOrderNoForTrays)) {
                    $stockOrderNoForTrays = trim($rsa['allocate_stockorder_no'] ?: $rsa['stockorder_no']);
                }

                $beds = json_decode((string)($rsa['allocatedbed'] ?? ''), true);
                if (is_array($beds)) {
                    $sumPlants = 0;
                    foreach ($beds as $b) {
                        $p = isset($b['plants']) ? intval($b['plants']) : (isset($b['total_plants']) ? intval($b['total_plants']) : 0);
                        $sumPlants += $p;
                    }
                    if ($sumPlants !== $allocQty) {
                        $newBedJson = correctBedJsonToQty($rsa['allocatedbed'], $allocQty);
                        $stmt = $db->prepare("UPDATE krf_readystock_allocation SET allocatedbed = ?, updated_at = NOW(), updated_by = ? WHERE id = ?");
                        $stmt->bind_param("ssi", $newBedJson, $currentUser, $rsaId);
                        $stmt->execute();
                        $stmt->close();
                        $log[] = "Corrected allocatedbed JSON for batch {$rsa['stockorder_no']} to {$allocQty} plants.";
                    }
                }
            }

            // Step B: Sync Sales Order Item allocate_ordqty
            if ($soi && count($rsaList) > 0 && intval($soi['allocate_ordqty']) !== $totalRsaAlloc) {
                $updSoiAlloc = $db->prepare("
                    UPDATE krf_sales_order_items 
                    SET allocate_ordqty = ?, 
                        reservedqty = ?, 
                        balancereservedqty = GREATEST(0, CAST(quantity AS SIGNED) - ?),
                        healthyplant = ?,
                        ammendedby = ?, 
                        updateamended = NOW() 
                    WHERE id = ?
                ");
                $updSoiAlloc->bind_param("iiiisi", $totalRsaAlloc, $totalRsaAlloc, $totalRsaAlloc, $totalRsaAlloc, $currentUser, $soiId);
                $updSoiAlloc->execute();
                $updSoiAlloc->close();
                $log[] = "Synced sales order allocate_ordqty to {$totalRsaAlloc} plants.";
            }

            // Step C: Check Dispatched Invoice
            $invQ = $db->query("
                SELECT SUM(CASE WHEN LOWER(TRIM(status)) = 'dispatched' THEN quantity ELSE 0 END) AS total_dispatched_qty,
                       SUM(quantity) AS total_inv_qty,
                       GROUP_CONCAT(DISTINCT invoiceno SEPARATOR ', ') AS invoices,
                       GROUP_CONCAT(DISTINCT stockorder_no SEPARATOR ', ') AS stockorders
                FROM sales_invoice_items
                WHERE psono = '{$db->real_escape_string($psono)}' 
                  AND productid = '{$db->real_escape_string($productId)}'
                  AND productid != 'BOX_PACKING' 
                  AND LOWER(IFNULL(status, '')) != 'cancelled'
            ");
            $invData = $invQ ? $invQ->fetch_assoc() : null;
            $invDispQty = intval($invData['total_dispatched_qty'] ?? 0);
            $invTotalQty = intval($invData['total_inv_qty'] ?? 0);

            if ($invDispQty > 0 || $invTotalQty > 0) {
                $effDisp = ($invDispQty > 0) ? $invDispQty : $invTotalQty;

                // Sync SOI dispatchedqty & status
                if ($soi) {
                    $updSoiDisp = $db->prepare("
                        UPDATE krf_sales_order_items
                        SET dispatchedqty = ?,
                            invoiceqty = ?,
                            status = CASE 
                                WHEN ? >= quantity THEN 'Dispatched' 
                                WHEN ? > 0 THEN 'Partial Dispatched' 
                                ELSE status 
                            END,
                            ammendedby = ?,
                            updateamended = NOW()
                        WHERE id = ?
                    ");
                    $updSoiDisp->bind_param("iiiisi", $effDisp, $invTotalQty, $effDisp, $effDisp, $currentUser, $soiId);
                    $updSoiDisp->execute();
                    $updSoiDisp->close();
                    $log[] = "Updated sales order status to Dispatched with {$effDisp} dispatched plants.";
                }

                // Sync RSA dispatchqty & status
                if (count($rsaList) > 0) {
                    $updRsaDisp = $db->prepare("
                        UPDATE krf_readystock_allocation
                        SET dispatchqty = CASE WHEN allocate_ordqty > 0 THEN LEAST(allocate_ordqty, ?) ELSE ? END,
                            status = 'dispatched',
                            updated_by = ?,
                            updated_at = NOW()
                        WHERE psono = ? AND productid = ? AND IFNULL(tedit, '') = ''
                    ");
                    $updRsaDisp->bind_param("iisss", $effDisp, $effDisp, $currentUser, $psono, $productId);
                    $updRsaDisp->execute();
                    $updRsaDisp->close();
                    $log[] = "Updated allocation table status to dispatched with {$effDisp} plants.";
                }

                // Step D: Mark Polyhouse Plant Trays
                if (empty($stockOrderNoForTrays) && !empty($invData['stockorders'])) {
                    $firstStock = explode(',', $invData['stockorders'])[0];
                    $stockOrderNoForTrays = trim($firstStock);
                }
                if (empty($stockOrderNoForTrays) && $soi && !empty($soi['stockorder_no'])) {
                    $stockOrderNoForTrays = trim($soi['stockorder_no']);
                }

                if (!empty($stockOrderNoForTrays)) {
                    $trayRes = markPolyhousePlantTrays($db, $psono, $productId, $clientId, $stockOrderNoForTrays, $effDisp, $currentUser);
                    if ($trayRes['success']) {
                        $log[] = "Polyhouse trays synced: {$trayRes['updatedFieldsCount']} field-fixed, {$trayRes['newlyMarkedCount']} newly marked (Total {$trayRes['totalNowMarked']} trays).";
                    }
                }
            }

            // Step E: Check and revert any extra marked polyhouse trays
            $revertRes = revertExtraPolyhouseTrays($db, $psono, $productId, $stockOrderNoForTrays, $currentUser);
            if ($revertRes['revertedCount'] > 0) {
                $log[] = "Reverted {$revertRes['revertedCount']} extra marked polyhouse tray(s) back to ReadyToSale (dispatch fields reset to NULL).";
            }

            $db->commit();
            echo json_encode([
                'success' => true,
                'message' => "Order {$psono} synced successfully!",
                'log' => $log
            ]);
            exit;
        } catch (Exception $e) {
            $db->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    // Action 5: Batch Fix Multiple Items
    if ($action === 'batch_fix_items') {
        $items = isset($_POST['items']) ? json_decode($_POST['items'], true) : [];
        if (!is_array($items) || empty($items)) {
            echo json_encode(['success' => false, 'message' => 'No items selected for batch fix.']);
            exit;
        }

        $successCount = 0;
        $failCount = 0;
        $errors = [];

        foreach ($items as $item) {
            $soiId = intval($item['soi_id'] ?? 0);
            $psono = trim($item['psono'] ?? '');
            $productId = trim($item['product_id'] ?? '');

            $db->begin_transaction();
            try {
                // Fetch SOI
                $soi = null;
                if ($soiId > 0) {
                    $q = $db->query("SELECT * FROM krf_sales_order_items WHERE id = {$soiId} LIMIT 1");
                    if ($q && $q->num_rows > 0) $soi = $q->fetch_assoc();
                }
                if (!$soi && !empty($psono) && !empty($productId)) {
                    $q = $db->query("SELECT * FROM krf_sales_order_items WHERE psono = '{$db->real_escape_string($psono)}' AND product_id = '{$db->real_escape_string($productId)}' LIMIT 1");
                    if ($q && $q->num_rows > 0) $soi = $q->fetch_assoc();
                }

                $clientId = $soi ? trim($soi['clientid']) : '';

                // Fetch RSA rows
                $rsaList = [];
                $rsaQ = $db->query("SELECT * FROM krf_readystock_allocation WHERE psono = '{$db->real_escape_string($psono)}' AND productid = '{$db->real_escape_string($productId)}' AND IFNULL(tedit, '') = '' AND LOWER(IFNULL(status, '')) != 'cancel'");
                if ($rsaQ) {
                    while ($r = $rsaQ->fetch_assoc()) $rsaList[] = $r;
                }

                // Fix bed plants
                $totalRsaAlloc = 0;
                $stockOrderNoForTrays = '';
                foreach ($rsaList as $rsa) {
                    $rsaId = intval($rsa['id']);
                    $allocQty = intval($rsa['allocate_ordqty']);
                    $totalRsaAlloc += $allocQty;
                    if (empty($stockOrderNoForTrays)) {
                        $stockOrderNoForTrays = trim($rsa['allocate_stockorder_no'] ?: $rsa['stockorder_no']);
                    }

                    $beds = json_decode((string)($rsa['allocatedbed'] ?? ''), true);
                    if (is_array($beds)) {
                        $sumPlants = 0;
                        foreach ($beds as $b) {
                            $p = isset($b['plants']) ? intval($b['plants']) : (isset($b['total_plants']) ? intval($b['total_plants']) : 0);
                            $sumPlants += $p;
                        }
                        if ($sumPlants !== $allocQty) {
                            $newBedJson = correctBedJsonToQty($rsa['allocatedbed'], $allocQty);
                            $stmt = $db->prepare("UPDATE krf_readystock_allocation SET allocatedbed = ?, updated_at = NOW(), updated_by = ? WHERE id = ?");
                            $stmt->bind_param("ssi", $newBedJson, $currentUser, $rsaId);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                }

                // Sync SOI allocate_ordqty
                if ($soi && count($rsaList) > 0 && intval($soi['allocate_ordqty']) !== $totalRsaAlloc) {
                    $updSoiAlloc = $db->prepare("
                        UPDATE krf_sales_order_items 
                        SET allocate_ordqty = ?, 
                            reservedqty = ?, 
                            balancereservedqty = GREATEST(0, CAST(quantity AS SIGNED) - ?),
                            healthyplant = ?,
                            ammendedby = ?, 
                            updateamended = NOW() 
                        WHERE id = ?
                    ");
                    $updSoiAlloc->bind_param("iiiisi", $totalRsaAlloc, $totalRsaAlloc, $totalRsaAlloc, $totalRsaAlloc, $currentUser, $soiId);
                    $updSoiAlloc->execute();
                    $updSoiAlloc->close();
                }

                // Check Dispatched Invoice
                $invQ = $db->query("
                    SELECT SUM(CASE WHEN LOWER(TRIM(status)) = 'dispatched' THEN quantity ELSE 0 END) AS total_dispatched_qty,
                           SUM(quantity) AS total_inv_qty,
                           GROUP_CONCAT(DISTINCT stockorder_no SEPARATOR ', ') AS stockorders
                    FROM sales_invoice_items
                    WHERE psono = '{$db->real_escape_string($psono)}' 
                      AND productid = '{$db->real_escape_string($productId)}'
                      AND productid != 'BOX_PACKING' 
                      AND LOWER(IFNULL(status, '')) != 'cancelled'
                ");
                $invData = $invQ ? $invQ->fetch_assoc() : null;
                $invDispQty = intval($invData['total_dispatched_qty'] ?? 0);
                $invTotalQty = intval($invData['total_inv_qty'] ?? 0);

                if ($invDispQty > 0 || $invTotalQty > 0) {
                    $effDisp = ($invDispQty > 0) ? $invDispQty : $invTotalQty;

                    if ($soi) {
                        $updSoiDisp = $db->prepare("
                            UPDATE krf_sales_order_items
                            SET dispatchedqty = ?,
                                invoiceqty = ?,
                                status = CASE 
                                    WHEN ? >= quantity THEN 'Dispatched' 
                                    WHEN ? > 0 THEN 'Partial Dispatched' 
                                    ELSE status 
                                END,
                                ammendedby = ?,
                                updateamended = NOW()
                            WHERE id = ?
                        ");
                        $updSoiDisp->bind_param("iiiisi", $effDisp, $invTotalQty, $effDisp, $effDisp, $currentUser, $soiId);
                        $updSoiDisp->execute();
                        $updSoiDisp->close();
                    }

                    if (count($rsaList) > 0) {
                        $updRsaDisp = $db->prepare("
                            UPDATE krf_readystock_allocation
                            SET dispatchqty = CASE WHEN allocate_ordqty > 0 THEN LEAST(allocate_ordqty, ?) ELSE ? END,
                                status = 'dispatched',
                                updated_by = ?,
                                updated_at = NOW()
                            WHERE psono = ? AND productid = ? AND IFNULL(tedit, '') = ''
                        ");
                        $updRsaDisp->bind_param("iisss", $effDisp, $effDisp, $currentUser, $psono, $productId);
                        $updRsaDisp->execute();
                        $updRsaDisp->close();
                    }

                    if (empty($stockOrderNoForTrays) && !empty($invData['stockorders'])) {
                        $firstStock = explode(',', $invData['stockorders'])[0];
                        $stockOrderNoForTrays = trim($firstStock);
                    }
                    if (empty($stockOrderNoForTrays) && $soi && !empty($soi['stockorder_no'])) {
                        $stockOrderNoForTrays = trim($soi['stockorder_no']);
                    }

                    if (!empty($stockOrderNoForTrays)) {
                        markPolyhousePlantTrays($db, $psono, $productId, $clientId, $stockOrderNoForTrays, $effDisp, $currentUser);
                    }
                }

                // Check and revert any extra marked polyhouse trays
                revertExtraPolyhouseTrays($db, $psono, $productId, $stockOrderNoForTrays, $currentUser);

                $db->commit();
                $successCount++;
            } catch (Exception $e) {
                $db->rollback();
                $failCount++;
                $errors[] = "Order {$psono}: " . $e->getMessage();
            }
        }

        echo json_encode([
            'success' => true,
            'message' => "Batch fix finished: {$successCount} successful, {$failCount} failed.",
            'successCount' => $successCount,
            'failCount' => $failCount,
            'errors' => $errors
        ]);
        exit;
    }

    // Action 6: Get Item Details for Inspection Modal
    if ($action === 'get_item_details') {
        $soiId = intval($_POST['soi_id'] ?? 0);
        $psono = trim($_POST['psono'] ?? '');
        $productId = trim($_POST['product_id'] ?? '');

        // Fetch SOI
        $soi = null;
        if ($soiId > 0) {
            $q = $db->query("
                SELECT soi.*, so.clientname, so.statename, so.districtname, so.mobileno, so.delivery_address 
                FROM krf_sales_order_items soi
                LEFT JOIN krf_sales_order so ON (so.psono = soi.psono OR so.sono = soi.sono)
                WHERE soi.id = {$soiId} LIMIT 1
            ");
            if ($q && $q->num_rows > 0) $soi = $q->fetch_assoc();
        }
        if (!$soi && !empty($psono) && !empty($productId)) {
            $q = $db->query("
                SELECT soi.*, so.clientname, so.statename, so.districtname, so.mobileno, so.delivery_address 
                FROM krf_sales_order_items soi
                LEFT JOIN krf_sales_order so ON (so.psono = soi.psono OR so.sono = soi.sono)
                WHERE soi.psono = '{$db->real_escape_string($psono)}' AND soi.product_id = '{$db->real_escape_string($productId)}' LIMIT 1
            ");
            if ($q && $q->num_rows > 0) $soi = $q->fetch_assoc();
        }

        if ($soi) {
            $psono = trim($soi['psono']);
            $productId = trim($soi['product_id']);
        }

        // Fetch RSA rows
        $rsaList = [];
        $rsaQ = $db->query("
            SELECT * FROM krf_readystock_allocation 
            WHERE psono = '{$db->real_escape_string($psono)}' AND productid = '{$db->real_escape_string($productId)}'
              AND IFNULL(tedit, '') = '' AND LOWER(IFNULL(status, '')) != 'cancel'
            ORDER BY id DESC
        ");
        if ($rsaQ) {
            while ($r = $rsaQ->fetch_assoc()) {
                $r['parsed_beds'] = json_decode((string)($r['allocatedbed'] ?? ''), true);
                $rsaList[] = $r;
            }
        }

        // Fetch Sales Invoice Items
        $invoices = [];
        $invQ = $db->query("
            SELECT sii.*, si.invoicedate, si.created_at AS invoice_created_at
            FROM sales_invoice_items sii
            LEFT JOIN sales_invoice si ON (si.id = sii.invoice_id OR si.invoiceno = sii.invoiceno)
            WHERE sii.psono = '{$db->real_escape_string($psono)}' AND sii.productid = '{$db->real_escape_string($productId)}'
              AND sii.productid != 'BOX_PACKING' AND LOWER(IFNULL(sii.status, '')) != 'cancelled'
            ORDER BY sii.id DESC
        ");
        if ($invQ) {
            while ($r = $invQ->fetch_assoc()) $invoices[] = $r;
        }

        // Fetch marked / related trays in krf_polyhouse_plant_details
        $trays = [];
        $trayQ = $db->query("
            SELECT ppd.polyhouseplantid, ppd.barcode, ppd.stockorder_no, ppd.productid, ppd.productname,
                   ppd.polyhouseid, ppd.bed_no, ppd.qtypertray, ppd.status, ppd.workingstatus,
                   ppd.dispatchqty, ppd.dispatchby, ppd.dispatchat, ppd.dispd_agst_clientid,
                   ppd.dispd_agst_stockorderno, ppd.dispd_agst_psono
            FROM krf_polyhouse_plant_details ppd
            WHERE ppd.dispd_agst_psono = '{$db->real_escape_string($psono)}'
               OR (ppd.stockorder_no IN (SELECT stockorder_no FROM krf_readystock_allocation WHERE psono = '{$db->real_escape_string($psono)}' AND productid = '{$db->real_escape_string($productId)}') AND ppd.productid = '{$db->real_escape_string($productId)}')
            ORDER BY CASE WHEN ppd.dispd_agst_psono = '{$db->real_escape_string($psono)}' THEN 1 ELSE 2 END, ppd.polyhouseplantid ASC
            LIMIT 100
        ");
        if ($trayQ) {
            while ($r = $trayQ->fetch_assoc()) $trays[] = $r;
        }

        echo json_encode([
            'success' => true,
            'soi' => $soi,
            'rsaList' => $rsaList,
            'invoices' => $invoices,
            'trays' => $trays
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit;
}

// -------------------------------------------------------------------------
// QUERY AUDIT DATA (ONLY INCORRECT / MISMATCHED ENTRIES DISPLAYED)
// -------------------------------------------------------------------------

$startAuditTime = microtime(true);

// 1. Index all active RSA rows
$rsaMap = []; // psono_productid => array of rsa rows
$rsaAllCount = 0;
$rsaRes = $db->query("
    SELECT id, projectid, polyhouse, psono, stockorder_no, allocate_stockorder_no, clientid, productid, productname, allocatedbed, allocate_ordqty, dispatchqty, status, created_at, updated_at
    FROM krf_readystock_allocation
    WHERE IFNULL(tedit, '') = '' AND LOWER(IFNULL(status, '')) != 'cancel'
    ORDER BY id DESC
");
if ($rsaRes) {
    while ($r = $rsaRes->fetch_assoc()) {
        $rsaAllCount++;
        $key = trim((string)($r['psono'] ?? '')) . '_' . trim((string)($r['productid'] ?? ''));
        if (!isset($rsaMap[$key])) {
            $rsaMap[$key] = [];
        }
        $rsaMap[$key][] = $r;
    }
}

// 2. Index all active sales_invoice_items
$siiMap = []; // psono_productid => summary
$siiRes = $db->query("
    SELECT psono, productid, 
           SUM(quantity) AS total_inv_qty,
           SUM(CASE WHEN LOWER(TRIM(status)) = 'dispatched' THEN quantity ELSE 0 END) AS total_dispatched_qty,
           GROUP_CONCAT(DISTINCT invoiceno SEPARATOR ', ') AS invoices,
           GROUP_CONCAT(DISTINCT status SEPARATOR ', ') AS statuses,
           GROUP_CONCAT(DISTINCT stockorder_no SEPARATOR ', ') AS stockorders
    FROM sales_invoice_items
    WHERE IFNULL(tedit, '') = '' AND LOWER(IFNULL(status, '')) != 'cancelled'
      AND psono IS NOT NULL AND psono != '' AND productid != 'BOX_PACKING'
    GROUP BY psono, productid
");
if ($siiRes) {
    while ($r = $siiRes->fetch_assoc()) {
        $key = trim((string)($r['psono'] ?? '')) . '_' . trim((string)($r['productid'] ?? ''));
        $siiMap[$key] = $r;
    }
}

// 3. Index all krf_polyhouse_plant_details dispatched trays
$ppdMap = []; // dispd_agst_psono_productid => summary
$ppdRes = $db->query("
    SELECT dispd_agst_psono, productid,
           COUNT(*) AS total_trays,
           SUM(CASE WHEN CAST(IFNULL(dispatchqty, 0) AS SIGNED) > 0 THEN CAST(dispatchqty AS SIGNED) ELSE CAST(IFNULL(qtypertray, 126) AS SIGNED) END) AS total_dispatched_plants,
           SUM(CASE WHEN LOWER(TRIM(status)) = 'final dispatch' THEN 1 ELSE 0 END) AS final_dispatch_trays,
           SUM(CASE WHEN dispatchqty IS NULL OR dispatchqty = '' OR dispatchqty = '0' THEN 1 ELSE 0 END) AS missing_dispatchqty,
           SUM(CASE WHEN dispatchby IS NULL OR dispatchby = '' THEN 1 ELSE 0 END) AS missing_dispatchby,
           SUM(CASE WHEN dispatchat IS NULL OR dispatchat = '' THEN 1 ELSE 0 END) AS missing_dispatchat,
           SUM(CASE WHEN dispd_agst_clientid IS NULL OR dispd_agst_clientid = '' THEN 1 ELSE 0 END) AS missing_clientid,
           SUM(CASE WHEN dispd_agst_stockorderno IS NULL OR dispd_agst_stockorderno = '' THEN 1 ELSE 0 END) AS missing_stockorderno,
           GROUP_CONCAT(DISTINCT status SEPARATOR ', ') AS statuses,
           GROUP_CONCAT(DISTINCT stockorder_no SEPARATOR ', ') AS stockorders
    FROM krf_polyhouse_plant_details
    WHERE dispd_agst_psono IS NOT NULL AND dispd_agst_psono != ''
    GROUP BY dispd_agst_psono, productid
");
if ($ppdRes) {
    while ($r = $ppdRes->fetch_assoc()) {
        $key = trim((string)($r['dispd_agst_psono'] ?? '')) . '_' . trim((string)($r['productid'] ?? ''));
        $ppdMap[$key] = $r;
    }
}

// 4. Fetch sales order items & audit for mismatches
$soiRes = $db->query("
    SELECT soi.id AS soi_id, soi.sono, soi.psono, soi.stockorder_no, soi.clientid, soi.product_id, soi.product_name,
           soi.quantity, soi.allocate_ordqty, soi.dispatchedqty, soi.invoiceqty, soi.status, soi.actionstatus,
           so.clientname, so.statename, so.districtname
    FROM krf_sales_order_items soi
    LEFT JOIN krf_sales_order so ON (so.psono = soi.psono OR so.sono = soi.sono)
    WHERE IFNULL(soi.tedit, '') = '' AND LOWER(IFNULL(soi.status, '')) NOT IN ('cancelled', 'cancel')
    ORDER BY soi.id DESC
");

$mismatchedItems = [];
$stats = [
    'total_inspected' => 0,
    'total_mismatched' => 0,
    'bed_plants_mismatches' => 0,
    'so_alloc_mismatches' => 0,
    'invoice_disp_mismatches' => 0,
    'polyhouse_trays_incomplete' => 0,
    'duplicate_allocations' => 0,
    'extra_marked_trays' => 0
];

if ($soiRes) {
    while ($soi = $soiRes->fetch_assoc()) {
        $stats['total_inspected']++;
        $psono = trim((string)($soi['psono'] ?? ''));
        $productId = trim((string)($soi['product_id'] ?? ''));
        $key = $psono . '_' . $productId;

        $rsaList = $rsaMap[$key] ?? [];
        $sii = $siiMap[$key] ?? null;
        $ppd = $ppdMap[$key] ?? null;

        $issues = [];

        // Check 1: RSA bed plants mismatch vs allocate_ordqty
        $bedMismatchList = [];
        $totalRsaAlloc = 0;
        $totalRsaDisp = 0;
        foreach ($rsaList as $rsa) {
            $allocQty = intval($rsa['allocate_ordqty']);
            $totalRsaAlloc += $allocQty;
            $totalRsaDisp += intval($rsa['dispatchqty']);

            $bedsJson = trim($rsa['allocatedbed'] ?? '');
            $beds = json_decode($bedsJson, true);
            if (is_array($beds)) {
                $sumPlants = 0;
                $sumTrays = 0;
                foreach ($beds as $b) {
                    $p = isset($b['plants']) ? intval($b['plants']) : (isset($b['total_plants']) ? intval($b['total_plants']) : 0);
                    $sumPlants += $p;
                    $sumTrays += intval($b['tray_count'] ?? 0);
                }
                if ($sumPlants !== $allocQty) {
                    $bedMismatchList[] = [
                        'rsa_id' => $rsa['id'],
                        'stockorder_no' => $rsa['stockorder_no'],
                        'allocate_ordqty' => $allocQty,
                        'bed_plants' => $sumPlants,
                        'bed_trays' => $sumTrays,
                        'allocatedbed' => $bedsJson
                    ];
                }
            }
        }
        if (!empty($bedMismatchList)) {
            $issues['bed_mismatch'] = $bedMismatchList;
            $stats['bed_plants_mismatches']++;
        }

        // Check 2: SOI allocate_ordqty != sum of RSA allocate_ordqty
        $soiAlloc = intval($soi['allocate_ordqty']);
        if (count($rsaList) > 0 && $soiAlloc !== $totalRsaAlloc) {
            $issues['soi_alloc_mismatch'] = [
                'soi_alloc' => $soiAlloc,
                'rsa_total_alloc' => $totalRsaAlloc
            ];
            $stats['so_alloc_mismatches']++;
        }

        // Check 3: Dispatched Invoice vs Sales Order & RSA
        $invDispQty = $sii ? intval($sii['total_dispatched_qty']) : 0;
        $soiDisp = intval($soi['dispatchedqty']);

        if ($invDispQty > 0) {
            if ($soiDisp !== $invDispQty || strtolower(trim($soi['status'])) !== 'dispatched') {
                $issues['soi_disp_mismatch'] = [
                    'soi_disp' => $soiDisp,
                    'soi_status' => $soi['status'],
                    'inv_disp' => $invDispQty
                ];
                $stats['invoice_disp_mismatches']++;
            }
            if (count($rsaList) > 0 && $totalRsaDisp !== $invDispQty) {
                $issues['rsa_disp_mismatch'] = [
                    'rsa_disp' => $totalRsaDisp,
                    'inv_disp' => $invDispQty
                ];
                if (!isset($issues['soi_disp_mismatch'])) {
                    $stats['invoice_disp_mismatches']++;
                }
            }

            // Check 4: Polyhouse trays dispatch marking
            $ppdTrays = $ppd ? intval($ppd['total_trays']) : 0;
            $ppdPlants = $ppd ? intval($ppd['total_dispatched_plants']) : 0;
            $expectedTrays = (int)ceil($invDispQty / 126);

            if ($ppdTrays === 0) {
                $issues['ppd_missing_marking'] = [
                    'expected_trays' => $expectedTrays,
                    'inv_disp' => $invDispQty,
                    'marked_trays' => 0
                ];
                $stats['polyhouse_trays_incomplete']++;
            } else {
                $missingAny = (
                    intval($ppd['missing_dispatchqty']) > 0 ||
                    intval($ppd['missing_dispatchby']) > 0 ||
                    intval($ppd['missing_dispatchat']) > 0 ||
                    intval($ppd['missing_clientid']) > 0 ||
                    intval($ppd['missing_stockorderno']) > 0 ||
                    intval($ppd['final_dispatch_trays']) < $ppdTrays
                );
                if ($missingAny || $ppdPlants < $invDispQty) {
                    $issues['ppd_incomplete_fields'] = [
                        'marked_trays' => $ppdTrays,
                        'marked_plants' => $ppdPlants,
                        'inv_disp' => $invDispQty,
                        'missing_dispatchqty' => intval($ppd['missing_dispatchqty']),
                        'missing_dispatchby' => intval($ppd['missing_dispatchby']),
                        'missing_dispatchat' => intval($ppd['missing_dispatchat']),
                        'missing_clientid' => intval($ppd['missing_clientid']),
                        'missing_stockorderno' => intval($ppd['missing_stockorderno']),
                        'final_dispatch_trays' => intval($ppd['final_dispatch_trays'])
                    ];
                    $stats['polyhouse_trays_incomplete']++;
                }
            }
        }

        // Check 5: Duplicate Allocations in RSA (Multiple active allocation rows for same order item)
        if (count($rsaList) > 1) {
            $issues['duplicate_allocation'] = [
                'count' => count($rsaList),
                'total_alloc' => $totalRsaAlloc,
                'order_qty' => intval($soi['quantity'] ?? 0)
            ];
            $stats['duplicate_allocations']++;
        }

        // Check 6: Extra Marked Polyhouse Trays (Trays marked dispatched beyond invoice need or when no invoice exists)
        $ppdTrays = $ppd ? intval($ppd['total_trays']) : 0;
        $ppdPlants = $ppd ? intval($ppd['total_dispatched_plants']) : 0;
        $expectedTrays = ($invDispQty > 0) ? (int)ceil($invDispQty / 126) : 0;

        if ($ppdTrays > 0) {
            if ($invDispQty == 0) {
                // Trays marked against this psono but 0 invoiced dispatched
                $issues['extra_marked_trays'] = [
                    'marked_trays' => $ppdTrays,
                    'marked_plants' => $ppdPlants,
                    'inv_disp' => 0,
                    'expected_trays' => 0,
                    'extra_trays' => $ppdTrays
                ];
                $stats['extra_marked_trays']++;
            } elseif ($ppdTrays > $expectedTrays && $ppdPlants > $invDispQty) {
                // More trays tagged than invoiced quantity
                $issues['extra_marked_trays'] = [
                    'marked_trays' => $ppdTrays,
                    'marked_plants' => $ppdPlants,
                    'inv_disp' => $invDispQty,
                    'expected_trays' => $expectedTrays,
                    'extra_trays' => ($ppdTrays - $expectedTrays)
                ];
                $stats['extra_marked_trays']++;
            }
        }

        // STRICT FILTER: Display entry ONLY if incorrect details exist!
        if (!empty($issues)) {
            $stats['total_mismatched']++;
            $mismatchedItems[] = [
                'soi' => $soi,
                'rsaList' => $rsaList,
                'sii' => $sii,
                'ppd' => $ppd,
                'issues' => $issues
            ];
        }
    }
}

// 5. Also check standalone/orphan RSA rows with bed plants mismatch where no SOI was found
$orphanRsaMismatches = [];
foreach ($rsaMap as $key => $rList) {
    // Check if this psono_productid was already processed in SOI
    list($pPso, $pProd) = explode('_', $key . '_');
    $pPso = trim($pPso);
    $pProd = trim($pProd);

    // If psono is empty or not in SOI, check for bed mismatch
    foreach ($rList as $rsa) {
        $allocQty = intval($rsa['allocate_ordqty']);
        $beds = json_decode((string)($rsa['allocatedbed'] ?? ''), true);
        if (is_array($beds)) {
            $sumPlants = 0;
            $sumTrays = 0;
            foreach ($beds as $b) {
                $p = isset($b['plants']) ? intval($b['plants']) : (isset($b['total_plants']) ? intval($b['total_plants']) : 0);
                $sumPlants += $p;
                $sumTrays += intval($b['tray_count'] ?? 0);
            }
            if ($sumPlants !== $allocQty) {
                // Check if already in mismatchedItems
                $alreadyListed = false;
                foreach ($mismatchedItems as $mItem) {
                    if (trim($mItem['soi']['psono'] ?? '') === $pPso && trim($mItem['soi']['product_id'] ?? '') === $pProd) {
                        $alreadyListed = true;
                        break;
                    }
                }
                if (!$alreadyListed) {
                    $orphanRsaMismatches[] = [
                        'rsa' => $rsa,
                        'bed_plants' => $sumPlants,
                        'bed_trays' => $sumTrays
                    ];
                    $stats['bed_plants_mismatches']++;
                    $stats['total_mismatched']++;
                }
            }
        }
    }
}

$auditDuration = round(microtime(true) - $startAuditTime, 3);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Order, Allocation, Invoice & Polyhouse Tray Auditor & Correction Tool</title>
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --primary-light: #eff6ff;
            --success: #10b981;
            --success-light: #ecfdf5;
            --warning: #f59e0b;
            --warning-light: #fffbeb;
            --danger: #ef4444;
            --danger-light: #fef2f2;
            --purple: #8b5cf6;
            --purple-light: #f5f3ff;
            --cyan: #06b6d4;
            --cyan-light: #ecfeff;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 14px;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font-sans);
            background-color: #f8fafc;
            color: var(--gray-800);
            line-height: 1.5;
            padding-bottom: 80px;
        }

        /* Top Header */
        .top-navbar {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #ffffff;
            padding: 16px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-md);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .brand-container {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-icon {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: #ffffff;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3);
        }

        .brand-title {
            font-size: 19px;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .brand-subtitle {
            font-size: 12px;
            color: var(--gray-400);
            font-weight: 400;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-tag {
            background: rgba(255, 255, 255, 0.1);
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 13px;
            color: var(--gray-200);
            display: flex;
            align-items: center;
            gap: 8px;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .container {
            max-width: 1720px;
            margin: 24px auto;
            padding: 0 24px;
        }

        /* KPI Stat Cards */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 18px;
            margin-bottom: 24px;
        }

        .kpi-card {
            background: #ffffff;
            border-radius: var(--radius-lg);
            padding: 20px 22px;
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .kpi-card.active {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px var(--primary-light), var(--shadow-md);
        }

        .kpi-icon {
            width: 52px;
            height: 52px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }

        .kpi-danger .kpi-icon { background: var(--danger-light); color: var(--danger); }
        .kpi-warning .kpi-icon { background: var(--warning-light); color: var(--warning); }
        .kpi-cyan .kpi-icon { background: var(--cyan-light); color: var(--cyan); }
        .kpi-purple .kpi-icon { background: var(--purple-light); color: var(--purple); }
        .kpi-primary .kpi-icon { background: var(--primary-light); color: var(--primary); }

        .kpi-info {
            flex-grow: 1;
        }

        .kpi-value {
            font-size: 28px;
            font-weight: 800;
            line-height: 1.1;
            color: var(--gray-900);
        }

        .kpi-label {
            font-size: 13px;
            font-weight: 500;
            color: var(--gray-500);
            margin-top: 4px;
        }

        /* Controls / Filter Bar */
        .controls-card {
            background: #ffffff;
            border-radius: var(--radius-lg);
            padding: 18px 24px;
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
            margin-bottom: 24px;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }

        .filter-tabs {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 16px;
            border-radius: 9999px;
            border: 1px solid var(--gray-200);
            background: var(--gray-50);
            color: var(--gray-600);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-btn:hover {
            background: var(--gray-100);
            color: var(--gray-900);
        }

        .filter-btn.active {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.3);
        }

        .filter-badge {
            background: rgba(0, 0, 0, 0.08);
            padding: 2px 7px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 700;
        }

        .filter-btn.active .filter-badge {
            background: rgba(255, 255, 255, 0.25);
            color: #ffffff;
        }

        .search-box-wrapper {
            position: relative;
            min-width: 320px;
        }

        .search-box-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            font-size: 15px;
        }

        .search-input {
            width: 100%;
            padding: 9px 16px 9px 40px;
            border-radius: 9999px;
            border: 1px solid var(--gray-300);
            background: #ffffff;
            font-size: 13px;
            color: var(--gray-800);
            transition: all 0.2s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-light);
        }

        /* Batch Actions Toolbar */
        .batch-bar {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: #ffffff;
            padding: 12px 24px;
            border-radius: var(--radius-md);
            margin-bottom: 20px;
            display: none;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--shadow-md);
            animation: slideDown 0.25s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .batch-count {
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .batch-buttons {
            display: flex;
            gap: 10px;
        }

        /* Main Data Table */
        .table-card {
            background: #ffffff;
            border-radius: var(--radius-lg);
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            position: relative;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            overflow-y: auto;
            max-height: calc(100vh - 150px);
            position: relative;
            scrollbar-width: thin;
            scrollbar-color: var(--gray-400) var(--gray-100);
        }

        .table-responsive::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .table-responsive::-webkit-scrollbar-track {
            background: var(--gray-100);
            border-radius: 4px;
        }

        .table-responsive::-webkit-scrollbar-thumb {
            background: var(--gray-400);
            border-radius: 4px;
        }

        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: var(--gray-500);
        }

        table.audit-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 13px;
            text-align: left;
        }

        table.audit-table thead {
            position: sticky;
            top: 0;
            z-index: 25;
        }

        table.audit-table th {
            position: sticky;
            top: 0;
            z-index: 25;
            background: #f1f5f9 !important;
            padding: 14px 16px;
            font-weight: 700;
            color: var(--gray-700);
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.05em;
            white-space: nowrap;
            border-bottom: 2px solid var(--gray-300);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.07);
        }

        @media print {
            .table-responsive {
                max-height: none !important;
                overflow: visible !important;
            }
            table.audit-table thead, table.audit-table th {
                position: static !important;
            }
        }

        table.audit-table td {
            padding: 16px;
            border-bottom: 1px solid var(--gray-200);
            vertical-align: top;
        }

        table.audit-table tbody tr:hover {
            background: var(--gray-50);
        }

        table.audit-table tbody tr.row-resolved {
            background: #f0fdf4 !important;
            opacity: 0.7;
            transition: all 0.3s ease;
        }

        /* Typography & Badges in Rows */
        .psono-badge {
            font-family: var(--font-mono);
            font-weight: 700;
            font-size: 13px;
            color: var(--primary);
            background: var(--primary-light);
            padding: 3px 8px;
            border-radius: var(--radius-sm);
            display: inline-block;
            margin-bottom: 4px;
        }

        .sono-badge {
            font-family: var(--font-mono);
            font-size: 12px;
            color: var(--gray-600);
            background: var(--gray-100);
            padding: 2px 6px;
            border-radius: var(--radius-sm);
            display: inline-block;
        }

        .client-info {
            font-weight: 600;
            color: var(--gray-800);
            margin-top: 4px;
            font-size: 13px;
        }

        .product-title {
            font-weight: 700;
            color: var(--gray-900);
            line-height: 1.3;
        }

        .product-meta {
            font-size: 12px;
            color: var(--gray-500);
            margin-top: 2px;
        }

        .issue-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            margin: 2px 2px 4px 0;
            line-height: 1.2;
        }

        .issue-tag-danger { background: var(--danger-light); color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.2); }
        .issue-tag-warning { background: var(--warning-light); color: var(--warning); border: 1px solid rgba(245, 158, 11, 0.2); }
        .issue-tag-cyan { background: var(--cyan-light); color: #0891b2; border: 1px solid rgba(6, 182, 212, 0.2); }
        .issue-tag-purple { background: var(--purple-light); color: var(--purple); border: 1px solid rgba(139, 92, 246, 0.2); }

        .stat-pill {
            display: inline-block;
            padding: 3px 8px;
            border-radius: var(--radius-sm);
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .stat-pill-gray { background: var(--gray-100); color: var(--gray-700); }
        .stat-pill-green { background: var(--success-light); color: var(--success); }
        .stat-pill-blue { background: var(--primary-light); color: var(--primary); }
        .stat-pill-red { background: var(--danger-light); color: var(--danger); }
        .stat-pill-orange { background: var(--warning-light); color: #b45309; }

        .bed-box {
            background: #ffffff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-sm);
            padding: 8px 10px;
            margin-top: 6px;
            font-size: 12px;
        }

        .bed-mismatch-alert {
            background: #fff1f2;
            border-left: 3px solid var(--danger);
            padding: 6px 10px;
            margin-top: 6px;
            border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
            font-size: 12px;
            color: #9f1239;
            font-weight: 600;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: var(--radius-sm);
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.2s ease;
            text-decoration: none;
            white-space: nowrap;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 11px;
        }

        .btn-primary { background: var(--primary); color: #ffffff; }
        .btn-primary:hover { background: var(--primary-hover); }

        .btn-success { background: var(--success); color: #ffffff; }
        .btn-success:hover { background: #059669; }

        .btn-warning { background: var(--warning); color: #ffffff; }
        .btn-warning:hover { background: #d97706; }

        .btn-danger { background: var(--danger); color: #ffffff; }
        .btn-danger:hover { background: #dc2626; }

        .btn-outline {
            background: #ffffff;
            border-color: var(--gray-300);
            color: var(--gray-700);
        }
        .btn-outline:hover {
            background: var(--gray-100);
            color: var(--gray-900);
        }

        .action-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 130px;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 20px;
        }

        .modal-content {
            background: #ffffff;
            border-radius: var(--radius-lg);
            max-width: 950px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--gray-200);
            animation: modalPop 0.25s ease;
        }

        @keyframes modalPop {
            from { opacity: 0; transform: scale(0.96); }
            to { opacity: 1; transform: scale(1); }
        }

        .modal-header {
            padding: 18px 24px;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--gray-50);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .modal-title {
            font-size: 17px;
            font-weight: 700;
            color: var(--gray-900);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 20px;
            color: var(--gray-400);
            cursor: pointer;
            padding: 4px 8px;
            border-radius: var(--radius-sm);
        }
        .modal-close:hover {
            background: var(--gray-200);
            color: var(--gray-800);
        }

        .modal-body {
            padding: 24px;
        }

        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid var(--gray-200);
            background: var(--gray-50);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            position: sticky;
            bottom: 0;
            z-index: 10;
        }

        .table-preview-card {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            padding: 16px;
            margin-bottom: 18px;
        }

        .table-preview-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .code-block {
            background: #1e293b;
            color: #e2e8f0;
            padding: 10px 14px;
            border-radius: var(--radius-sm);
            font-family: var(--font-mono);
            font-size: 12px;
            overflow-x: auto;
            white-space: pre-wrap;
            word-break: break-all;
        }

        /* Empty State */
        .empty-state {
            padding: 60px 24px;
            text-align: center;
        }

        .empty-icon {
            width: 72px;
            height: 72px;
            background: var(--success-light);
            color: var(--success);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin: 0 auto 18px;
        }

        .empty-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--gray-900);
        }

        .empty-desc {
            font-size: 14px;
            color: var(--gray-500);
            max-width: 480px;
            margin: 8px auto 0;
        }
    </style>
</head>
<body>

<!-- Top Navigation -->
<header class="top-navbar">
    <div class="brand-container">
        <div class="brand-icon">
            <i class="fa-solid fa-seedling"></i>
        </div>
        <div>
            <div class="brand-title">Order, Allocation, Invoice & Polyhouse Tray Sync Auditor</div>
            <div class="brand-subtitle">Automated Mismatch Detection & One-Click Correction Engine</div>
        </div>
    </div>
    <div class="header-actions">
        <div class="user-tag">
            <i class="fa-solid fa-user-shield"></i>
            <span><?= htmlspecialchars($currentUser) ?></span>
        </div>
        <button onclick="location.reload();" class="btn btn-primary btn-sm" title="Refresh Audit">
            <i class="fa-solid fa-arrows-rotate"></i> Re-Scan All Tables
        </button>
    </div>
</header>

<div class="container">

    <!-- KPI Metric Cards -->
    <div class="kpi-grid">
        <div class="kpi-card kpi-danger <?= $stats['duplicate_allocations'] > 0 ? 'active' : '' ?>" onclick="filterByIssue('duplicate_allocation')" title="Click to filter orders with duplicate active allocations">
            <div class="kpi-icon" style="background: #fef2f2; color: #dc2626;">
                <i class="fa-solid fa-clone"></i>
            </div>
            <div class="kpi-info">
                <div class="kpi-value" id="kpi-dup-count" style="color: #dc2626;"><?= number_format($stats['duplicate_allocations']) ?></div>
                <div class="kpi-label">Duplicate Allocations (Mark tedit)</div>
            </div>
        </div>

        <div class="kpi-card kpi-warning <?= $stats['extra_marked_trays'] > 0 ? 'active' : '' ?>" onclick="filterByIssue('extra_marked_trays')" title="Click to filter orders with extra trays marked in polyhouse table">
            <div class="kpi-icon" style="background: #fffbeb; color: #d97706;">
                <i class="fa-solid fa-rotate-left"></i>
            </div>
            <div class="kpi-info">
                <div class="kpi-value" id="kpi-extra-trays-count" style="color: #d97706;"><?= number_format($stats['extra_marked_trays']) ?></div>
                <div class="kpi-label">Extra Trays Marked (Revert to RTS)</div>
            </div>
        </div>

        <div class="kpi-card kpi-danger" onclick="filterByIssue('bed_mismatch')">
            <div class="kpi-icon">
                <i class="fa-solid fa-cubes-stacked"></i>
            </div>
            <div class="kpi-info">
                <div class="kpi-value" id="kpi-bed-count"><?= number_format($stats['bed_plants_mismatches']) ?></div>
                <div class="kpi-label">Bed Plants vs Alloc Qty Mismatches</div>
            </div>
        </div>

        <div class="kpi-card kpi-warning" onclick="filterByIssue('soi_alloc_mismatch')">
            <div class="kpi-icon">
                <i class="fa-solid fa-scale-unbalanced"></i>
            </div>
            <div class="kpi-info">
                <div class="kpi-value"><?= number_format($stats['so_alloc_mismatches']) ?></div>
                <div class="kpi-label">Sales Order Allocation Mismatches</div>
            </div>
        </div>

        <div class="kpi-card kpi-cyan" onclick="filterByIssue('soi_disp_mismatch')">
            <div class="kpi-icon">
                <i class="fa-solid fa-file-invoice-dollar"></i>
            </div>
            <div class="kpi-info">
                <div class="kpi-value"><?= number_format($stats['invoice_disp_mismatches']) ?></div>
                <div class="kpi-label">Invoice Dispatched Sync Needed</div>
            </div>
        </div>

        <div class="kpi-card kpi-purple" onclick="filterByIssue('ppd_trays')">
            <div class="kpi-icon">
                <i class="fa-solid fa-warehouse"></i>
            </div>
            <div class="kpi-info">
                <div class="kpi-value"><?= number_format($stats['polyhouse_trays_incomplete']) ?></div>
                <div class="kpi-label">Polyhouse Trays Incomplete/Missing</div>
            </div>
        </div>

        <div class="kpi-card kpi-primary" onclick="filterByIssue('all')">
            <div class="kpi-icon">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div class="kpi-info">
                <div class="kpi-value" id="kpi-total-count"><?= number_format($stats['total_mismatched']) ?></div>
                <div class="kpi-label">Total Incorrect Items Displayed</div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Controls -->
    <div class="controls-card">
        <div class="filter-tabs">
            <button class="filter-btn active" data-filter="all" onclick="setFilter('all', this)">
                <i class="fa-solid fa-list-check"></i> All Incorrect Items
                <span class="filter-badge"><?= count($mismatchedItems) + count($orphanRsaMismatches) ?></span>
            </button>
            <button class="filter-btn" data-filter="duplicate_allocation" onclick="setFilter('duplicate_allocation', this)">
                <i class="fa-solid fa-clone"></i> Duplicate Allocations
                <span class="filter-badge" style="background: rgba(220, 38, 38, 0.2); color: #dc2626;"><?= $stats['duplicate_allocations'] ?></span>
            </button>
            <button class="filter-btn" data-filter="extra_marked_trays" onclick="setFilter('extra_marked_trays', this)">
                <i class="fa-solid fa-rotate-left"></i> Extra Marked Trays
                <span class="filter-badge" style="background: rgba(217, 119, 6, 0.2); color: #d97706;"><?= $stats['extra_marked_trays'] ?></span>
            </button>
            <button class="filter-btn" data-filter="bed_mismatch" onclick="setFilter('bed_mismatch', this)">
                <i class="fa-solid fa-layer-group"></i> Bed Plants Mismatch
                <span class="filter-badge"><?= $stats['bed_plants_mismatches'] ?></span>
            </button>
            <button class="filter-btn" data-filter="soi_alloc_mismatch" onclick="setFilter('soi_alloc_mismatch', this)">
                <i class="fa-solid fa-cart-shopping"></i> SO Alloc Mismatch
                <span class="filter-badge"><?= $stats['so_alloc_mismatches'] ?></span>
            </button>
            <button class="filter-btn" data-filter="invoice_disp" onclick="setFilter('invoice_disp', this)">
                <i class="fa-solid fa-receipt"></i> Invoice vs Dispatch
                <span class="filter-badge"><?= $stats['invoice_disp_mismatches'] ?></span>
            </button>
            <button class="filter-btn" data-filter="ppd_trays" onclick="setFilter('ppd_trays', this)">
                <i class="fa-solid fa-boxes-stacked"></i> Polyhouse Tray Marking
                <span class="filter-badge"><?= $stats['polyhouse_trays_incomplete'] ?></span>
            </button>
        </div>
                <span class="filter-badge"><?= $stats['polyhouse_trays_incomplete'] ?></span>
            </button>
        </div>

        <div class="search-box-wrapper">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="tableSearch" class="search-input" placeholder="Search by PSONO, SONO, Client, Product, Batch..." onkeyup="filterTableSearch()">
        </div>
    </div>

    <!-- Batch Actions Floating Bar -->
    <div id="batchBar" class="batch-bar">
        <div class="batch-count">
            <i class="fa-solid fa-check-double"></i>
            <span id="selectedCountText">0 items selected</span>
        </div>
        <div class="batch-buttons">
            <button class="btn btn-outline btn-sm" onclick="clearBatchSelection()" style="color: #ffffff; border-color: rgba(255,255,255,0.3);">
                Deselect All
            </button>
            <button class="btn btn-success btn-sm" onclick="executeBatchFix()">
                <i class="fa-solid fa-bolt"></i> Auto-Fix Selected Items
            </button>
        </div>
    </div>

    <!-- Main Audit Data Table -->
    <div class="table-card">
        <div class="table-responsive">
            <table class="audit-table" id="auditTable">
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;">
                            <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)" style="cursor: pointer; transform: scale(1.2);">
                        </th>
                        <th>#</th>
                        <th>Final Sale Order (`krf_sales_order_items`)</th>
                        <th>Ready Stock Allocation (`krf_readystock_allocation`)</th>
                        <th>Commercial Invoice (`sales_invoice_items`)</th>
                        <th>Polyhouse Trays (`krf_polyhouse_plant_details`)</th>
                        <th>Mismatch Status</th>
                        <th style="text-align: right;">Correction Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($mismatchedItems) && empty($orphanRsaMismatches)): ?>
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="fa-solid fa-circle-check"></i></div>
                                    <div class="empty-title">All Details Are 100% Correct & In Sync!</div>
                                    <div class="empty-desc">
                                        No mismatched bed quantities, unsynced invoice dispatches, or unmarked polyhouse trays were found. Everything matches perfectly across all four tables.
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $rowIdx = 0;
                        foreach ($mismatchedItems as $item): 
                            $rowIdx++;
                            $soi = $item['soi'];
                            $rsaList = $item['rsaList'];
                            $sii = $item['sii'];
                            $ppd = $item['ppd'];
                            $issues = $item['issues'];

                            $soiId = intval($soi['soi_id'] ?? $soi['id'] ?? 0);
                            $psono = trim((string)($soi['psono'] ?? ''));
                            $productId = trim((string)($soi['product_id'] ?? ''));
                            $rowClasses = [];

                            if (isset($issues['duplicate_allocation'])) $rowClasses[] = 'filter-duplicate_allocation';
                            if (isset($issues['extra_marked_trays'])) $rowClasses[] = 'filter-extra_marked_trays';
                            if (isset($issues['bed_mismatch'])) $rowClasses[] = 'filter-bed_mismatch';
                            if (isset($issues['soi_alloc_mismatch'])) $rowClasses[] = 'filter-soi_alloc_mismatch';
                            if (isset($issues['soi_disp_mismatch']) || isset($issues['rsa_disp_mismatch'])) $rowClasses[] = 'filter-invoice_disp';
                            if (isset($issues['ppd_missing_marking']) || isset($issues['ppd_incomplete_fields'])) $rowClasses[] = 'filter-ppd_trays';
                            $rowFilterClass = implode(' ', $rowClasses);
                        ?>
                        <tr class="audit-row <?= $rowFilterClass ?>" id="row-<?= $soiId ?>" data-soi-id="<?= $soiId ?>" data-psono="<?= htmlspecialchars($psono) ?>" data-product-id="<?= htmlspecialchars($productId) ?>">
                            <td style="text-align: center; vertical-align: middle;">
                                <input type="checkbox" class="row-checkbox" onchange="updateBatchBar()" data-soi-id="<?= $soiId ?>" data-psono="<?= htmlspecialchars($psono) ?>" data-product-id="<?= htmlspecialchars($productId) ?>" style="cursor: pointer; transform: scale(1.15);">
                            </td>
                            <td style="font-weight: 700; color: var(--gray-400);"><?= $rowIdx ?></td>
                            
                            <!-- 1. Sale Order Info -->
                            <td>
                                <div class="psono-badge"><?= htmlspecialchars($psono) ?></div>
                                <?php if (!empty($soi['sono'])): ?>
                                    <div class="sono-badge"><?= htmlspecialchars((string)$soi['sono']) ?></div>
                                <?php endif; ?>
                                <div class="client-info">
                                    <i class="fa-regular fa-user" style="color: var(--gray-400);"></i> 
                                    <?= htmlspecialchars((string)($soi['clientname'] ?: $soi['clientid'])) ?>
                                </div>
                                <div class="product-title" style="margin-top: 6px;">
                                    <?= htmlspecialchars((string)($soi['product_name'] ?? '')) ?>
                                </div>
                                <div class="product-meta">PID: <?= htmlspecialchars($productId) ?></div>
                                
                                <div style="margin-top: 8px; display: flex; flex-wrap: wrap; gap: 4px;">
                                    <span class="stat-pill stat-pill-gray" title="Order Quantity">Ord: <?= number_format(floatval($soi['quantity'] ?? 0)) ?></span>
                                    <span class="stat-pill stat-pill-blue" title="Allocated Quantity">Alloc: <?= number_format(floatval($soi['allocate_ordqty'] ?? 0)) ?></span>
                                    <span class="stat-pill <?= intval($soi['dispatchedqty'] ?? 0) > 0 ? 'stat-pill-green' : 'stat-pill-gray' ?>" title="Dispatched Quantity">Disp: <?= number_format(floatval($soi['dispatchedqty'] ?? 0)) ?></span>
                                    <span class="stat-pill stat-pill-orange" title="Sales Order Status"><?= htmlspecialchars((string)($soi['status'] ?: 'Pending')) ?></span>
                                </div>
                            </td>

                            <!-- 2. Ready Stock Allocation Info -->
                            <td>
                                <?php if (empty($rsaList)): ?>
                                    <span class="stat-pill stat-pill-gray"><i class="fa-solid fa-circle-xmark"></i> No Allocation Found</span>
                                <?php else: ?>
                                    <?php foreach ($rsaList as $rsa): 
                                        $rsaAlloc = intval($rsa['allocate_ordqty']);
                                        $beds = json_decode((string)($rsa['allocatedbed'] ?? ''), true);
                                        $bedPlants = 0;
                                        $bedNames = [];
                                        if (is_array($beds)) {
                                            foreach ($beds as $b) {
                                                $p = isset($b['plants']) ? intval($b['plants']) : (isset($b['total_plants']) ? intval($b['total_plants']) : 0);
                                                $bedPlants += $p;
                                                $bedNames[] = ($b['polyhouse_name'] ?? 'PH') . ' | ' . ($b['bed_name'] ?? 'Bed') . ' (' . ($b['tray_count'] ?? 0) . ' trays, ' . number_format($p) . ' plts)';
                                            }
                                        }
                                        $isBedMismatched = ($bedPlants !== $rsaAlloc);
                                    ?>
                                        <div class="bed-box">
                                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                                <span style="font-weight: 700; color: var(--gray-800); font-family: var(--font-mono);">
                                                    <i class="fa-solid fa-barcode"></i> <?= htmlspecialchars($rsa['stockorder_no']) ?>
                                                </span>
                                                <span class="stat-pill stat-pill-blue" title="Batch Allocated Qty">
                                                    Alloc: <?= number_format($rsaAlloc) ?>
                                                </span>
                                            </div>

                                            <div style="font-size: 11px; color: var(--gray-600); margin-top: 4px;">
                                                <?= implode('<br>', array_map('htmlspecialchars', $bedNames)) ?: 'No Bed Info' ?>
                                            </div>

                                            <?php if ($isBedMismatched): ?>
                                                <div class="bed-mismatch-alert">
                                                    <i class="fa-solid fa-triangle-exclamation"></i> 
                                                    Bed plants (<?= number_format($bedPlants) ?>) &ne; Alloc ordqty (<?= number_format($rsaAlloc) ?>)
                                                </div>
                                                <button class="btn btn-outline btn-sm" onclick="quickFixBed(<?= $rsa['id'] ?>, <?= $rsaAlloc ?>)" style="margin-top: 4px; font-size: 10px; padding: 2px 6px;">
                                                    <i class="fa-solid fa-wrench"></i> Fix Bed JSON &rarr; <?= number_format($rsaAlloc) ?> plts
                                                </button>
                                            <?php endif; ?>

                                            <div style="margin-top: 4px; font-size: 11px; display: flex; gap: 8px;">
                                                <span>Disp: <b><?= number_format($rsa['dispatchqty']) ?></b></span>
                                                <span>Status: <b style="text-transform: capitalize;"><?= htmlspecialchars($rsa['status'] ?: 'allocated') ?></b></span>
                                            </div>

                                            <?php if (count($rsaList) > 1): ?>
                                                <button class="btn btn-outline btn-sm" onclick="markAllocationTedit(<?= $rsa['id'] ?>, '<?= htmlspecialchars($psono) ?>', '<?= htmlspecialchars($productId) ?>', '<?= htmlspecialchars($rsa['stockorder_no']) ?>', <?= $rsaAlloc ?>)" style="margin-top: 6px; color: #dc2626; border-color: #fca5a5; font-size: 11px; padding: 3px 6px; width: 100%;" title="Mark this duplicate allocation with tedit='1' and cancel status">
                                                    <i class="fa-solid fa-ban"></i> Mark Duplicate (tedit)
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>

                            <!-- 3. Sales Invoice Info -->
                            <td>
                                <?php if (!$sii || empty($sii['total_inv_qty'])): ?>
                                    <span class="stat-pill stat-pill-gray">No Invoices</span>
                                <?php else: ?>
                                    <div style="font-weight: 700; color: var(--gray-800);">
                                        <i class="fa-solid fa-receipt" style="color: var(--cyan);"></i> 
                                        <?= htmlspecialchars($sii['invoices']) ?>
                                    </div>
                                    <div style="margin-top: 4px; display: flex; flex-wrap: wrap; gap: 4px;">
                                        <span class="stat-pill stat-pill-gray" title="Total Invoiced Qty">Total Inv: <?= number_format($sii['total_inv_qty']) ?></span>
                                        <span class="stat-pill stat-pill-green" title="Dispatched Invoice Qty">Dispatched: <?= number_format($sii['total_dispatched_qty']) ?></span>
                                        <span class="stat-pill stat-pill-blue" title="Invoice Status"><?= htmlspecialchars($sii['statuses']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <!-- 4. Polyhouse Trays Info -->
                            <td>
                                <?php if (!$ppd || empty($ppd['total_trays'])): ?>
                                    <div class="stat-pill stat-pill-red">
                                        <i class="fa-solid fa-xmark"></i> 0 Trays Tagged
                                    </div>
                                    <?php if ($sii && intval($sii['total_dispatched_qty']) > 0): ?>
                                        <div style="font-size: 11px; color: var(--danger); font-weight: 600; margin-top: 4px;">
                                            Needs <?= (int)ceil($sii['total_dispatched_qty'] / 126) ?> trays for <?= number_format($sii['total_dispatched_qty']) ?> plts!
                                        </div>
                                    <?php endif; ?>
                                <?php else: 
                                    $hasMissingFields = (
                                        intval($ppd['missing_dispatchqty']) > 0 ||
                                        intval($ppd['missing_dispatchby']) > 0 ||
                                        intval($ppd['missing_dispatchat']) > 0 ||
                                        intval($ppd['missing_clientid']) > 0 ||
                                        intval($ppd['missing_stockorderno']) > 0 ||
                                        intval($ppd['final_dispatch_trays']) < intval($ppd['total_trays'])
                                    );
                                ?>
                                    <div style="font-weight: 700; color: var(--gray-800);">
                                        <i class="fa-solid fa-boxes-stacked" style="color: var(--purple);"></i> 
                                        <?= number_format($ppd['total_trays']) ?> Trays (<?= number_format($ppd['total_dispatched_plants']) ?> plants)
                                    </div>
                                    <div style="margin-top: 4px; display: flex; flex-wrap: wrap; gap: 4px;">
                                        <span class="stat-pill <?= intval($ppd['final_dispatch_trays']) === intval($ppd['total_trays']) ? 'stat-pill-green' : 'stat-pill-orange' ?>">
                                            Final Disp: <?= $ppd['final_dispatch_trays'] ?> / <?= $ppd['total_trays'] ?>
                                        </span>
                                    </div>
                                    <?php if ($hasMissingFields): ?>
                                        <div style="font-size: 11px; color: var(--warning); font-weight: 600; margin-top: 4px;">
                                            <i class="fa-solid fa-triangle-exclamation"></i> Incomplete dispatch fields detected!
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if (isset($issues['extra_marked_trays'])): ?>
                                    <div class="issue-tag issue-tag-danger" style="margin-top: 6px; width: 100%;">
                                        <i class="fa-solid fa-triangle-exclamation"></i> <?= $issues['extra_marked_trays']['extra_trays'] ?> Extra Trays Marked
                                    </div>
                                    <button class="btn btn-warning btn-sm" onclick="revertExtraTraysAction('<?= htmlspecialchars($psono) ?>', '<?= htmlspecialchars($productId) ?>', '<?= htmlspecialchars($rsaList[0]['stockorder_no'] ?? '') ?>')" style="margin-top: 3px; font-size: 10px; padding: 3px 6px; width: 100%;" title="Revert extra marked trays back to ReadyToSale">
                                        <i class="fa-solid fa-rotate-left"></i> Revert Extra Trays
                                    </button>
                                <?php endif; ?>
                            </td>

                            <!-- 5. Detected Mismatches Badges -->
                            <td>
                                <?php if (isset($issues['duplicate_allocation'])): ?>
                                    <div class="issue-tag issue-tag-danger">
                                        <i class="fa-solid fa-clone"></i> Duplicate Allocation (<?= $issues['duplicate_allocation']['count'] ?> Active Rows)
                                    </div>
                                <?php endif; ?>
                                <?php if (isset($issues['extra_marked_trays'])): ?>
                                    <div class="issue-tag issue-tag-warning">
                                        <i class="fa-solid fa-boxes-stacked"></i> Extra Polyhouse Trays (<?= $issues['extra_marked_trays']['extra_trays'] ?> Trays)
                                    </div>
                                <?php endif; ?>
                                <?php if (isset($issues['bed_mismatch'])): ?>
                                    <div class="issue-tag issue-tag-danger">
                                        <i class="fa-solid fa-layer-group"></i> Bed Plants Mismatch
                                    </div>
                                <?php endif; ?>
                                <?php if (isset($issues['soi_alloc_mismatch'])): ?>
                                    <div class="issue-tag issue-tag-warning">
                                        <i class="fa-solid fa-scale-unbalanced"></i> SO Alloc &ne; Table
                                    </div>
                                <?php endif; ?>
                                <?php if (isset($issues['soi_disp_mismatch'])): ?>
                                    <div class="issue-tag issue-tag-cyan">
                                        <i class="fa-solid fa-file-invoice"></i> Invoiced Dispatched, SO Not Updated
                                    </div>
                                <?php endif; ?>
                                <?php if (isset($issues['rsa_disp_mismatch'])): ?>
                                    <div class="issue-tag issue-tag-purple">
                                        <i class="fa-solid fa-boxes-packing"></i> Invoiced Dispatched, RSA Not Updated
                                    </div>
                                <?php endif; ?>
                                <?php if (isset($issues['ppd_missing_marking'])): ?>
                                    <div class="issue-tag issue-tag-danger">
                                        <i class="fa-solid fa-box-open"></i> Trays Missing Dispatch Marking
                                    </div>
                                <?php endif; ?>
                                <?php if (isset($issues['ppd_incomplete_fields'])): ?>
                                    <div class="issue-tag issue-tag-warning">
                                        <i class="fa-solid fa-pen-to-square"></i> Tray Fields Incomplete
                                    </div>
                                <?php endif; ?>
                            </td>

                            <!-- 6. Actions -->
                            <td style="text-align: right;">
                                <div class="action-group">
                                    <button class="btn btn-success btn-sm" onclick="autoFixSingleItem(<?= $soiId ?>, '<?= htmlspecialchars($psono) ?>', '<?= htmlspecialchars($productId) ?>')" title="One-Click Auto Fix All Issues">
                                        <i class="fa-solid fa-bolt"></i> Auto-Fix All
                                    </button>
                                    <button class="btn btn-outline btn-sm" onclick="openDetailsModal(<?= $soiId ?>, '<?= htmlspecialchars($psono) ?>', '<?= htmlspecialchars($productId) ?>')" title="Inspect Full Details & Manual Adjustments">
                                        <i class="fa-solid fa-sliders"></i> Inspect / Edit
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>

                        <!-- Orphan RSA Rows with Bed Plants Mismatch -->
                        <?php foreach ($orphanRsaMismatches as $oIdx => $oItem): 
                            $rsa = $oItem['rsa'];
                            $rsaId = intval($rsa['id']);
                            $rsaAlloc = intval($rsa['allocate_ordqty']);
                            $bPlants = $oItem['bed_plants'];
                        ?>
                        <tr class="audit-row filter-bed_mismatch" id="rsa-row-<?= $rsaId ?>" data-rsa-id="<?= $rsaId ?>">
                            <td style="text-align: center; vertical-align: middle;">
                                <span class="stat-pill stat-pill-gray">Orphan</span>
                            </td>
                            <td style="font-weight: 700; color: var(--gray-400);">RSA-<?= $rsaId ?></td>
                            <td>
                                <div class="psono-badge"><?= htmlspecialchars($rsa['psono'] ?: 'No PSONO') ?></div>
                                <div class="client-info">Client: <?= htmlspecialchars($rsa['clientid']) ?></div>
                                <div class="product-title" style="margin-top: 4px;"><?= htmlspecialchars($rsa['productname']) ?></div>
                                <div class="product-meta">PID: <?= htmlspecialchars($rsa['productid']) ?></div>
                            </td>
                            <td>
                                <div class="bed-box">
                                    <div style="font-weight: 700; font-family: var(--font-mono);">
                                        <i class="fa-solid fa-barcode"></i> <?= htmlspecialchars($rsa['stockorder_no']) ?>
                                    </div>
                                    <div class="bed-mismatch-alert">
                                        Bed plants (<?= number_format($bPlants) ?>) &ne; Alloc ordqty (<?= number_format($rsaAlloc) ?>)
                                    </div>
                                </div>
                            </td>
                            <td><span class="stat-pill stat-pill-gray">N/A</span></td>
                            <td><span class="stat-pill stat-pill-gray">N/A</span></td>
                            <td>
                                <div class="issue-tag issue-tag-danger">
                                    <i class="fa-solid fa-layer-group"></i> Bed Plants Mismatch
                                </div>
                            </td>
                            <td style="text-align: right;">
                                <button class="btn btn-primary btn-sm" onclick="quickFixBed(<?= $rsaId ?>, <?= $rsaAlloc ?>)">
                                    <i class="fa-solid fa-wrench"></i> Fix Bed JSON &rarr; <?= number_format($rsaAlloc) ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Inspection & Custom Correction Modal -->
<div id="detailsModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-title">
                <i class="fa-solid fa-magnifying-glass-chart" style="color: var(--primary);"></i>
                <span id="modalHeaderTitle">Detailed Inspection & Correction</span>
            </div>
            <button class="modal-close" onclick="closeDetailsModal()">&times;</button>
        </div>
        <div class="modal-body" id="modalBodyContent">
            <!-- Dynamically populated via AJAX -->
            <div style="text-align: center; padding: 40px;">
                <i class="fa-solid fa-circle-notch fa-spin fa-2x" style="color: var(--primary);"></i>
                <p style="margin-top: 10px; color: var(--gray-500);">Loading table records...</p>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeDetailsModal()">Close</button>
            <button class="btn btn-success" id="modalAutoFixBtn" onclick="modalPerformAutoFix()">
                <i class="fa-solid fa-bolt"></i> Apply All Corrections Now
            </button>
        </div>
    </div>
</div>

<script>
let currentActiveFilter = 'all';
let currentModalItem = null;

// Filter table by mismatch category
function setFilter(filterType, element) {
    currentActiveFilter = filterType;
    document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
    if (element) element.classList.add('active');

    const rows = document.querySelectorAll('#auditTable tbody tr.audit-row');
    rows.forEach(row => {
        if (filterType === 'all') {
            row.style.display = '';
        } else if (row.classList.contains('filter-' + filterType)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });

    filterTableSearch();
}

function filterByIssue(issueType) {
    const btn = document.querySelector(`.filter-btn[data-filter="${issueType}"]`);
    if (btn) btn.click();
}

// Client-side instant text search
function filterTableSearch() {
    const query = document.getElementById('tableSearch').value.toLowerCase().trim();
    const rows = document.querySelectorAll('#auditTable tbody tr.audit-row');

    rows.forEach(row => {
        // Must match active category filter first
        const matchesCategory = (currentActiveFilter === 'all' || row.classList.contains('filter-' + currentActiveFilter));
        if (!matchesCategory) {
            row.style.display = 'none';
            return;
        }

        if (query === '') {
            row.style.display = '';
            return;
        }

        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(query) ? '' : 'none';
    });
}

// Checkbox selection & Batch Actions Bar
function toggleSelectAll(masterCheckbox) {
    const isChecked = masterCheckbox.checked;
    document.querySelectorAll('.row-checkbox').forEach(cb => {
        const row = cb.closest('tr');
        if (row && row.style.display !== 'none') {
            cb.checked = isChecked;
        }
    });
    updateBatchBar();
}

function updateBatchBar() {
    const selectedBoxes = document.querySelectorAll('.row-checkbox:checked');
    const batchBar = document.getElementById('batchBar');
    const countText = document.getElementById('selectedCountText');

    if (selectedBoxes.length > 0) {
        batchBar.style.display = 'flex';
        countText.innerText = selectedBoxes.length + ' item' + (selectedBoxes.length > 1 ? 's' : '') + ' selected';
    } else {
        batchBar.style.display = 'none';
        const master = document.getElementById('selectAllCheckbox');
        if (master) master.checked = false;
    }
}

function clearBatchSelection() {
    document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false);
    const master = document.getElementById('selectAllCheckbox');
    if (master) master.checked = false;
    updateBatchBar();
}

// One-Click Auto-Fix Single Item
function autoFixSingleItem(soiId, psono, productId) {
    Swal.fire({
        title: 'Auto-Fix Item?',
        html: `This will automatically:<br>
            &bull; Correct <b>allocatedbed</b> plants to match order allocation qty<br>
            &bull; Sync sales order and allocation table with commercial invoice dispatches<br>
            &bull; Mark/update production trays in <b>krf_polyhouse_plant_details</b> as Final Dispatch with all 7 required fields.<br><br>
            <b>Order:</b> ${psono} | <b>PID:</b> ${productId}`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa-solid fa-bolt"></i> Yes, Fix All Now!'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Synchronizing...',
                html: 'Applying database corrections across all 4 tables...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            const formData = new FormData();
            formData.append('action', 'fix_all_in_item');
            formData.append('soi_id', soiId);
            formData.append('psono', psono);
            formData.append('product_id', productId);

            fetch('rechek_pro_aloc_disp_ply.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Fixed Successfully!',
                        html: `<div style="text-align: left; font-size: 13px;">${data.log.map(l => '&bull; ' + l).join('<br>')}</div>`,
                        icon: 'success'
                    }).then(() => {
                        // Mark row as resolved
                        const row = document.getElementById('row-' + soiId);
                        if (row) {
                            row.classList.add('row-resolved');
                            row.style.background = '#ecfdf5';
                            setTimeout(() => {
                                row.style.transition = 'all 0.5s ease';
                                row.style.opacity = '0';
                                setTimeout(() => row.remove(), 500);
                            }, 1200);
                        }
                    });
                } else {
                    Swal.fire('Error', data.message || 'Correction failed.', 'error');
                }
            })
            .catch(err => {
                Swal.fire('Error', 'Network or server error: ' + err.message, 'error');
            });
        }
    });
}

// Quick Fix Bed Plants Mismatch Only
function quickFixBed(rsaId, allocQty) {
    Swal.fire({
        title: 'Fix Allocated Bed Plants?',
        text: `Update allocatedbed JSON so total plants and tray counts match ${allocQty} plants?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        confirmButtonText: 'Yes, Correct Bed JSON'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'fix_bed_plants_mismatch');
            formData.append('rsa_id', rsaId);
            formData.append('fix_mode', 'bed_to_alloc');

            fetch('rechek_pro_aloc_disp_ply.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Corrected!', data.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(err => Swal.fire('Error', err.message, 'error'));
        }
    });
}

// Execute Batch Fix on Selected Items
function executeBatchFix() {
    const selectedBoxes = document.querySelectorAll('.row-checkbox:checked');
    if (selectedBoxes.length === 0) return;

    const items = [];
    selectedBoxes.forEach(cb => {
        items.push({
            soi_id: cb.getAttribute('data-soi-id'),
            psono: cb.getAttribute('data-psono'),
            product_id: cb.getAttribute('data-product-id')
        });
    });

    Swal.fire({
        title: `Auto-Fix ${items.length} Selected Items?`,
        html: `This will sequentially apply all corrections to all 4 tables for all <b>${items.length}</b> selected items.<br>Are you sure you want to proceed?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#64748b',
        confirmButtonText: `<i class="fa-solid fa-bolt"></i> Proceed Batch Fix (${items.length})`
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Executing Batch Fix...',
                html: 'Updating tables in transaction batches...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            const formData = new FormData();
            formData.append('action', 'batch_fix_items');
            formData.append('items', JSON.stringify(items));

            fetch('rechek_pro_aloc_disp_ply.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Batch Fix Complete!',
                        text: data.message,
                        icon: 'success'
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Batch Error', data.message, 'error');
                }
            })
            .catch(err => Swal.fire('Error', err.message, 'error'));
        }
    });
}

// Inspection & Custom Correction Modal
function openDetailsModal(soiId, psono, productId) {
    currentModalItem = { soiId, psono, productId };
    document.getElementById('detailsModal').style.display = 'flex';
    document.getElementById('modalHeaderTitle').innerText = `Inspection: ${psono} (Product ID: ${productId})`;
    document.getElementById('modalBodyContent').innerHTML = `
        <div style="text-align: center; padding: 40px;">
            <i class="fa-solid fa-circle-notch fa-spin fa-2x" style="color: var(--primary);"></i>
            <p style="margin-top: 10px; color: var(--gray-500);">Fetching live records from all 4 tables...</p>
        </div>
    `;

    const formData = new FormData();
    formData.append('action', 'get_item_details');
    formData.append('soi_id', soiId);
    formData.append('psono', psono);
    formData.append('product_id', productId);

    fetch('rechek_pro_aloc_disp_ply.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            document.getElementById('modalBodyContent').innerHTML = `<div class="empty-state"><p style="color: var(--danger);">${data.message}</p></div>`;
            return;
        }

        renderModalDetails(data);
    })
    .catch(err => {
        document.getElementById('modalBodyContent').innerHTML = `<div class="empty-state"><p style="color: var(--danger);">${err.message}</p></div>`;
    });
}

function renderModalDetails(data) {
    const soi = data.soi;
    const rsaList = data.rsaList || [];
    const invoices = data.invoices || [];
    const trays = data.trays || [];

    let html = `
        <!-- Section 1: Sales Order Item -->
        <div class="table-preview-card">
            <div class="table-preview-title">
                <span><i class="fa-solid fa-file-invoice" style="color: var(--primary);"></i> 1. Final Sale Order Item (\`krf_sales_order_items\`)</span>
                <span class="stat-pill stat-pill-blue">ID #${soi ? soi.id : 'N/A'}</span>
            </div>
            ${soi ? `
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; font-size: 13px;">
                <div><b>PSONO:</b> <span class="psono-badge">${soi.psono}</span></div>
                <div><b>SONO:</b> <span class="sono-badge">${soi.sono || 'N/A'}</span></div>
                <div><b>Client:</b> ${soi.clientname || soi.clientid}</div>
                <div><b>Product:</b> ${soi.product_name} (${soi.product_id})</div>
                <div><b>Order Qty:</b> <span style="font-weight: 700;">${Number(soi.quantity).toLocaleString()}</span></div>
                <div><b>Allocate OrdQty:</b> <span style="font-weight: 700; color: var(--primary);">${Number(soi.allocate_ordqty).toLocaleString()}</span></div>
                <div><b>Dispatched Qty:</b> <span style="font-weight: 700; color: var(--success);">${Number(soi.dispatchedqty).toLocaleString()}</span></div>
                <div><b>Status:</b> <span class="stat-pill stat-pill-orange">${soi.status}</span></div>
            </div>
            ` : '<p style="color: var(--gray-500);">No sales order item found.</p>'}
        </div>

        <!-- Section 2: Ready Stock Allocation -->
        <div class="table-preview-card">
            <div class="table-preview-title">
                <span><i class="fa-solid fa-cubes-stacked" style="color: var(--warning);"></i> 2. Ready Stock Allocation (\`krf_readystock_allocation\`)</span>
                <span class="stat-pill stat-pill-gray">${rsaList.length} Allocation(s)</span>
            </div>
            ${rsaList.length > 0 ? rsaList.map(rsa => {
                const beds = rsa.parsed_beds || [];
                let bedSum = 0;
                beds.forEach(b => bedSum += Number(b.plants || b.total_plants || 0));
                const isMismatch = (bedSum !== Number(rsa.allocate_ordqty));

                return `
                <div style="background: #ffffff; border: 1px solid var(--gray-200); border-radius: 8px; padding: 12px; margin-bottom: 10px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <b>Batch: <span style="font-family: var(--font-mono);">${rsa.stockorder_no}</span></b>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span>Status: <b style="text-transform: capitalize;">${rsa.status}</b> | Disp: <b>${rsa.dispatchqty}</b></span>
                            ${rsaList.length > 1 ? `
                                <button class="btn btn-outline btn-sm" onclick="markAllocationTedit(${rsa.id}, '${soi ? soi.psono : ''}', '${soi ? soi.product_id : ''}', '${rsa.stockorder_no}', ${rsa.allocate_ordqty})" style="color: #dc2626; border-color: #fca5a5; padding: 2px 8px; font-size: 11px;" title="Mark this duplicate allocation as tedit='1'">
                                    <i class="fa-solid fa-ban"></i> Mark Duplicate (tedit)
                                </button>
                            ` : ''}
                        </div>
                    </div>
                    <div style="display: flex; gap: 16px; font-size: 13px;">
                        <div>Table \`allocate_ordqty\`: <b>${Number(rsa.allocate_ordqty).toLocaleString()}</b></div>
                        <div>Sum of plants in \`allocatedbed\` JSON: <b style="${isMismatch ? 'color: var(--danger);' : ''}">${Number(bedSum).toLocaleString()}</b></div>
                    </div>
                    ${isMismatch ? `
                        <div class="bed-mismatch-alert" style="margin: 8px 0;">
                            <i class="fa-solid fa-triangle-exclamation"></i> Mismatch detected: allocatedbed JSON has ${bedSum} plants but allocate_ordqty is ${rsa.allocate_ordqty}!
                        </div>
                        <div style="margin-top: 6px; display: flex; gap: 8px;">
                            <button class="btn btn-outline btn-sm" onclick="quickFixBed(${rsa.id}, ${rsa.allocate_ordqty})">
                                <i class="fa-solid fa-wrench"></i> Fix Bed JSON &rarr; ${rsa.allocate_ordqty} plants
                            </button>
                            <button class="btn btn-outline btn-sm" onclick="fixAllocToBed(${rsa.id})">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i> Set allocate_ordqty &rarr; ${bedSum} plants
                            </button>
                        </div>
                    ` : '<div style="color: var(--success); font-size: 12px; margin-top: 4px;"><i class="fa-solid fa-check"></i> Bed plants match allocate_ordqty perfectly.</div>'}
                    <div style="margin-top: 8px;">
                        <span style="font-size: 11px; color: var(--gray-500); font-weight: 600;">Raw allocatedbed JSON:</span>
                        <div class="code-block">${JSON.stringify(beds, null, 2)}</div>
                    </div>
                </div>
                `;
            }).join('') : '<p style="color: var(--gray-500);">No allocation records found in krf_readystock_allocation.</p>'}
        </div>

        <!-- Section 3: Commercial Invoices -->
        <div class="table-preview-card">
            <div class="table-preview-title">
                <span><i class="fa-solid fa-receipt" style="color: var(--cyan);"></i> 3. Dispatched Invoices (\`sales_invoice_items\`)</span>
                <span class="stat-pill stat-pill-cyan">${invoices.length} Item(s)</span>
            </div>
            ${invoices.length > 0 ? `
            <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--gray-300); text-align: left;">
                        <th style="padding: 6px;">Invoice No</th>
                        <th style="padding: 6px;">Batch No</th>
                        <th style="padding: 6px;">Quantity</th>
                        <th style="padding: 6px;">Dispatched Plants</th>
                        <th style="padding: 6px;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    ${invoices.map(inv => `
                    <tr style="border-bottom: 1px solid var(--gray-200);">
                        <td style="padding: 6px; font-weight: 600;">${inv.invoiceno}</td>
                        <td style="padding: 6px; font-family: var(--font-mono);">${inv.stockorder_no || 'N/A'}</td>
                        <td style="padding: 6px; font-weight: 700;">${Number(inv.quantity).toLocaleString()}</td>
                        <td style="padding: 6px; color: var(--success); font-weight: 700;">${Number(inv.total_dispatch_plants).toLocaleString()}</td>
                        <td style="padding: 6px;"><span class="stat-pill ${inv.status === 'dispatched' ? 'stat-pill-green' : 'stat-pill-blue'}">${inv.status}</span></td>
                    </tr>
                    `).join('')}
                </tbody>
            </table>
            ` : '<p style="color: var(--gray-500);">No commercial invoice items found.</p>'}
        </div>

        <!-- Section 4: Polyhouse Plant Trays -->
        <div class="table-preview-card">
            <div class="table-preview-title">
                <span><i class="fa-solid fa-boxes-stacked" style="color: var(--purple);"></i> 4. Polyhouse Plant Details Trays (\`krf_polyhouse_plant_details\`)</span>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span class="stat-pill stat-pill-purple">${trays.length} Tray(s) Sample</span>
                    <button class="btn btn-warning btn-sm" onclick="revertExtraTraysAction('${soi ? soi.psono : ''}', '${soi ? soi.product_id : ''}', '${rsaList.length > 0 ? (rsaList[0].allocate_stockorder_no || rsaList[0].stockorder_no) : ''}')" style="font-size: 11px; padding: 3px 8px;" title="Revert any trays marked beyond invoiced needs back to ReadyToSale">
                        <i class="fa-solid fa-rotate-left"></i> Revert Extra Trays to RTS
                    </button>
                </div>
            </div>
            ${trays.length > 0 ? `
            <div style="max-height: 220px; overflow-y: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--gray-300); text-align: left; position: sticky; top: 0; background: var(--gray-100);">
                            <th style="padding: 4px;">Tray ID</th>
                            <th style="padding: 4px;">Location</th>
                            <th style="padding: 4px;">Status</th>
                            <th style="padding: 4px;">Dispatch Qty</th>
                            <th style="padding: 4px;">Dispatch By</th>
                            <th style="padding: 4px;">Dispatch At</th>
                            <th style="padding: 4px;">Client ID</th>
                            <th style="padding: 4px;">PSONO</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${trays.map(t => {
                            const isMarked = (t.dispd_agst_psono === (soi ? soi.psono : ''));
                            return `
                            <tr style="border-bottom: 1px solid var(--gray-200); ${isMarked ? 'background: #fdf4ff;' : ''}">
                                <td style="padding: 4px; font-family: var(--font-mono);">${t.polyhouseplantid}</td>
                                <td style="padding: 4px;">PH-${t.polyhouseid} / ${t.bed_no}</td>
                                <td style="padding: 4px; font-weight: 600; color: ${t.status === 'Final Dispatch' ? 'var(--success)' : 'var(--gray-700)'};">${t.status}</td>
                                <td style="padding: 4px; font-weight: 700;">${t.dispatchqty || '<span style="color: var(--danger);">NULL</span>'}</td>
                                <td style="padding: 4px;">${t.dispatchby || '<span style="color: var(--danger);">NULL</span>'}</td>
                                <td style="padding: 4px;">${t.dispatchat || '<span style="color: var(--danger);">NULL</span>'}</td>
                                <td style="padding: 4px;">${t.dispd_agst_clientid || '<span style="color: var(--danger);">NULL</span>'}</td>
                                <td style="padding: 4px; font-weight: 700; color: var(--purple);">${t.dispd_agst_psono || '<span style="color: var(--danger);">NULL</span>'}</td>
                            </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>
            </div>
            ` : '<p style="color: var(--gray-500);">No trays currently marked or matched for this order.</p>'}
        </div>
    `;

    document.getElementById('modalBodyContent').innerHTML = html;
}

function fixAllocToBed(rsaId) {
    Swal.fire({
        title: 'Update Allocation to Match Bed Plants?',
        text: 'This will update allocate_ordqty in both the allocation table and the sales order item to match the sum of bed plants.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        confirmButtonText: 'Yes, Set to Bed Plants'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'fix_bed_plants_mismatch');
            formData.append('rsa_id', rsaId);
            formData.append('fix_mode', 'alloc_to_bed');

            fetch('rechek_pro_aloc_disp_ply.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Updated!', data.message, 'success').then(() => {
                        if (currentModalItem) openDetailsModal(currentModalItem.soiId, currentModalItem.psono, currentModalItem.productId);
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(err => Swal.fire('Error', err.message, 'error'));
        }
    });
}

function modalPerformAutoFix() {
    if (!currentModalItem) return;
    autoFixSingleItem(currentModalItem.soiId, currentModalItem.psono, currentModalItem.productId);
    closeDetailsModal();
}

function closeDetailsModal() {
    document.getElementById('detailsModal').style.display = 'none';
    currentModalItem = null;
}

// Mark Duplicate Allocation with tedit='1' and status='cancel'
function markAllocationTedit(rsaId, psono, productId, stockOrderNo, allocQty) {
    Swal.fire({
        title: 'Mark Duplicate Allocation?',
        html: `Are you sure you want to mark Allocation <b>#${rsaId}</b> (${Number(allocQty).toLocaleString()} plants, Batch: <b>${stockOrderNo}</b>) as duplicate with <b>tedit = '1'</b>?<br><br>
            <div style="text-align: left; font-size: 12px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 10px; color: #991b1b;">
                &bull; Sets allocation record <b>status = 'cancel'</b> and <b>tedit = '1'</b>.<br>
                &bull; Deducts allocation from sales order item <b>allocate_ordqty</b>.<br>
                &bull; Automatically checks <b>krf_polyhouse_plant_details</b>: if extra trays were marked dispatched, reverts them back to <b>ReadyToSale</b> and clears all 6 dispatch columns to <b>NULL</b>.<br>
                &bull; <i>Note: Once marked with tedit, it will not be audited or shown for re-modifying.</i>
            </div>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa-solid fa-ban"></i> Yes, Mark tedit & Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Marking Duplicate...',
                html: 'Updating allocation, sales order, and polyhouse trays...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            const formData = new FormData();
            formData.append('action', 'mark_allocation_tedit');
            formData.append('rsa_id', rsaId);
            formData.append('psono', psono);
            formData.append('product_id', productId);

            fetch('rechek_pro_aloc_disp_ply.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Duplicate Marked!',
                        html: `<p>${data.message}</p>`,
                        icon: 'success'
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message || 'Action failed.', 'error');
                }
            })
            .catch(err => Swal.fire('Error', 'Network or server error: ' + err.message, 'error'));
        }
    });
}

// Revert Extra Polyhouse Trays back to ReadyToSale with NULL dispatch fields
function revertExtraTraysAction(psono, productId, stockOrderNo) {
    if (!psono || !productId) {
        Swal.fire('Error', 'Missing Order PSONO or Product ID.', 'error');
        return;
    }

    Swal.fire({
        title: 'Revert Extra Marked Trays?',
        html: `Scan and revert all polyhouse trays marked dispatched for <b>${psono}</b> that exceed commercial invoice requirements?<br><br>
            All excess trays will be reverted to:
            <ul style="text-align: left; font-size: 12px; margin-top: 6px; color: #475569;">
                <li><b>status & workingstatus:</b> 'ReadyToSale'</li>
                <li><b>dispatchqty, dispatchby, dispatchat:</b> NULL</li>
                <li><b>dispd_agst_clientid, dispd_agst_stockorderno, dispd_agst_psono:</b> NULL</li>
            </ul>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#f59e0b',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa-solid fa-rotate-left"></i> Yes, Revert to ReadyToSale'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Reverting Extra Trays...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            const formData = new FormData();
            formData.append('action', 'revert_extra_trays');
            formData.append('psono', psono);
            formData.append('product_id', productId);
            formData.append('stockorder_no', stockOrderNo || '');

            fetch('rechek_pro_aloc_disp_ply.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Success!', data.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message || 'Revert failed.', 'error');
                }
            })
            .catch(err => Swal.fire('Error', 'Network or server error: ' + err.message, 'error'));
        }
    });
}

// Close modal when clicking outside content
window.onclick = function(event) {
    const modal = document.getElementById('detailsModal');
    if (event.target === modal) {
        closeDetailsModal();
    }
}
</script>

</body>
</html>
