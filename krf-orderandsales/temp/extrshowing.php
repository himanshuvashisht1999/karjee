<?php
/**
 * Extra Showing & Stock Order / Sowing Mismatch Auditor & Removal Tool
 * Location: krf-orderandsales/temp/extrshowing.php
 * 
 * Features:
 *  - Detects stock orders in `krf_production_stockorder` and `krf_polyhouse_sowingschedule`
 *    that are NOT in sowing table `krf_polyhouse_sowingplant_details`, or have mismatched order/qty.
 *  - Option to remove / mark tedit = '1' with custom remark in both tables.
 *  - Single & Bulk removal actions with instant AJAX updates.
 *  - Restore / Undo option for previously removed orders.
 *  - Filterable tabs, KPI cards, and DataTables export (Excel, CSV, Print).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 0); // Clean JSON responses

// Connection handling
if (file_exists(__DIR__ . '/../../connection.php')) {
    require_once __DIR__ . '/../../connection.php';
} elseif (file_exists('../../connection.php')) {
    require_once '../../connection.php';
} else {
    require_once 'connection.php';
}

$db = (isset($krfconn) && $krfconn instanceof mysqli) ? $krfconn : $conn;

$loginid   = $_SESSION['loginid'] ?? $_SESSION['userid'] ?? 'admin';
$loginname = $_SESSION['loginname'] ?? $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'Admin';

// Ensure helpful index on krf_polyhouse_sowingplant_details for performance (safely checked)
try {
    $checkIdx = @$db->query("SHOW INDEX FROM krf_polyhouse_sowingplant_details WHERE Key_name = 'idx_spd_stockorder'");
    if ($checkIdx && $checkIdx->num_rows === 0) {
        @$db->query("ALTER TABLE krf_polyhouse_sowingplant_details ADD INDEX idx_spd_stockorder (stockorder_no)");
    }
} catch (Throwable $e) {
    // Index already exists or insufficient DDL permissions - safely ignore
}

// =============================================================================
// AJAX / POST HANDLERS
// =============================================================================
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_POST['action'];

    // 1. Mark tedit = 1 (Single or Bulk)
    if ($action === 'mark_tedit' || $action === 'bulk_mark_tedit') {
        $stockorders = [];
        if ($action === 'mark_tedit') {
            $so = trim($_POST['stockorder_no'] ?? '');
            if ($so !== '') {
                $stockorders[] = $so;
            }
        } else {
            $rawList = $_POST['stockorders'] ?? [];
            if (is_string($rawList)) {
                $rawList = json_decode($rawList, true) ?: explode(',', $rawList);
            }
            if (is_array($rawList)) {
                foreach ($rawList as $s) {
                    $s = trim($s);
                    if ($s !== '') $stockorders[] = $s;
                }
            }
        }

        $stockorders = array_unique($stockorders);

        if (empty($stockorders)) {
            echo json_encode(['success' => false, 'message' => 'No valid stock order number(s) provided.']);
            exit;
        }

        $remark = trim($_POST['remark'] ?? 'Removed: Not in sowing table or mismatched');
        if ($remark === '') {
            $remark = 'Removed: Not in sowing table or mismatched';
        }
        $fullRemark = $remark . " [Marked by $loginname on " . date('Y-m-d H:i:s') . "]";

        $target = trim($_POST['target'] ?? 'both'); // 'both' or 'pss_only'

        $psoUpdated = 0;
        $pssUpdated = 0;
        $errors = [];

        // Prepare statements for krf_production_stockorder and krf_polyhouse_sowingschedule ONLY
        // (krf_polyhouse_sowingplant_details and krf_polyhouse_plant_details are NOT marked with tedit)
        $stmtPso = null;
        if ($target === 'both') {
            $stmtPso = $db->prepare("
                UPDATE krf_production_stockorder 
                SET tedit = '1', 
                    teditremark = ?, 
                    status = 'Removed',
                    updated_at = NOW()
                WHERE stockorder_no = ?
            ");
        }

        $stmtPss = $db->prepare("
            UPDATE krf_polyhouse_sowingschedule 
            SET tedit = '1', 
                teditremark = ?, 
                status = 'removed',
                updated_at = NOW(),
                updated_by = ?
            WHERE stockorder_no = ?
        ");

        $db->begin_transaction();
        try {
            foreach ($stockorders as $so) {
                // 1. Update Production Stock Order (only if target is 'both')
                if ($stmtPso) {
                    $stmtPso->bind_param("ss", $fullRemark, $so);
                    $stmtPso->execute();
                    $psoUpdated += $stmtPso->affected_rows;
                }
                // 2. Update Sowing Schedule
                if ($stmtPss) {
                    $stmtPss->bind_param("sss", $fullRemark, $loginname, $so);
                    $stmtPss->execute();
                    $pssUpdated += $stmtPss->affected_rows;
                }
            }
            $db->commit();
            if ($stmtPso) $stmtPso->close();
            if ($stmtPss) $stmtPss->close();

            $msg = ($target === 'pss_only')
                ? "Successfully marked " . count($stockorders) . " stock order(s) as tedit = 1 in krf_polyhouse_sowingschedule ONLY."
                : "Successfully marked " . count($stockorders) . " stock order(s) as tedit = 1 in BOTH stock order and schedule tables.";

            echo json_encode([
                'success' => true,
                'message' => $msg,
                'count' => count($stockorders),
                'target' => $target,
                'pso_affected' => $psoUpdated,
                'pss_affected' => $pssUpdated
            ]);
            exit;
        } catch (Exception $e) {
            $db->rollback();
            if ($stmtPso) $stmtPso->close();
            if ($stmtPss) $stmtPss->close();
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            exit;
        }
    }

    // 2. Restore / Undo tedit (Set tedit = NULL)
    if ($action === 'restore_tedit') {
        $so = trim($_POST['stockorder_no'] ?? '');
        if ($so === '') {
            echo json_encode(['success' => false, 'message' => 'Invalid stock order number.']);
            exit;
        }

        $restoreRemark = "Restored from tedit=1 by $loginname on " . date('Y-m-d H:i:s');
        $db->begin_transaction();
        try {
            $stmt1 = $db->prepare("
                UPDATE krf_production_stockorder 
                SET tedit = NULL, 
                    teditremark = CONCAT(COALESCE(teditremark,''), ' | ', ?), 
                    updated_at = NOW() 
                WHERE stockorder_no = ?
            ");
            $stmt1->bind_param("ss", $restoreRemark, $so);
            $stmt1->execute();
            $stmt1->close();

            $stmt2 = $db->prepare("
                UPDATE krf_polyhouse_sowingschedule 
                SET tedit = NULL, 
                    teditremark = CONCAT(COALESCE(teditremark,''), ' | ', ?), 
                    updated_at = NOW(), 
                    updated_by = ? 
                WHERE stockorder_no = ?
            ");
            $stmt2->bind_param("sss", $restoreRemark, $loginname, $so);
            $stmt2->execute();
            $stmt2->close();

            $db->commit();
            echo json_encode(['success' => true, 'message' => "Stock order $so has been restored successfully."]);
            exit;
        } catch (Exception $e) {
            $db->rollback();
            echo json_encode(['success' => false, 'message' => 'Failed to restore: ' . $e->getMessage()]);
            exit;
        }
    }

    // 2b. Sync PSO quantity from Sowing Schedule (Single or Bulk)
    if ($action === 'sync_pso_qty' || $action === 'bulk_sync_pso_qty') {
        $stockorders = [];
        if ($action === 'sync_pso_qty') {
            $so = trim($_POST['stockorder_no'] ?? '');
            if ($so !== '') $stockorders[] = $so;
        } else {
            $rawList = $_POST['stockorders'] ?? [];
            if (is_string($rawList)) {
                $rawList = json_decode($rawList, true) ?: explode(',', $rawList);
            }
            if (is_array($rawList)) {
                foreach ($rawList as $s) {
                    $s = trim($s);
                    if ($s !== '') $stockorders[] = $s;
                }
            }
        }

        $stockorders = array_unique($stockorders);
        if (empty($stockorders)) {
            echo json_encode(['success' => false, 'message' => 'No valid stock order number provided for sync.']);
            exit;
        }

        $updatedCount = 0;
        $insertedCount = 0;
        $syncDetails = [];

        $db->begin_transaction();
        try {
            foreach ($stockorders as $so) {
                // Fetch schedule rows for this stockorder
                $stmtPss = $db->prepare("
                    SELECT * FROM krf_polyhouse_sowingschedule 
                    WHERE stockorder_no = ? 
                    ORDER BY (LOWER(roottype) = 'scion') DESC, sowingschedulid ASC
                ");
                $stmtPss->bind_param("s", $so);
                $stmtPss->execute();
                $pssRes = $stmtPss->get_result();
                $pssRows = [];
                while ($r = $pssRes->fetch_assoc()) $pssRows[] = $r;
                $stmtPss->close();

                if (empty($pssRows)) {
                    continue; // No schedule found
                }

                // If grafted, prefer Scion orderqty
                $scionRow = null;
                $rootRow = null;
                $firstRow = null;
                foreach ($pssRows as $r) {
                    if (!$firstRow) $firstRow = $r;
                    if (strtolower(trim($r['roottype'])) === 'scion') $scionRow = $r;
                    if (strtolower(trim($r['roottype'])) === 'root') $rootRow = $r;
                }

                $isGrafted = ($scionRow !== null) || (stripos($firstRow['productname'], 'graft') !== false && stripos($firstRow['productname'], 'nongraft') === false);
                $chosenRow = ($isGrafted && $scionRow) ? $scionRow : ($rootRow ?: $firstRow);
                $syncQty = (int)$chosenRow['orderqty'];

                if ($syncQty <= 0) {
                    continue; // Nothing to sync
                }

                // Check if row exists in krf_production_stockorder
                $stmtChk = $db->prepare("SELECT id, quantity, status FROM krf_production_stockorder WHERE stockorder_no = ?");
                $stmtChk->bind_param("s", $so);
                $stmtChk->execute();
                $existingPso = $stmtChk->get_result()->fetch_assoc();
                $stmtChk->close();

                $remarkLog = "Synced qty ($syncQty) from schedule" . ($isGrafted ? " (Scion)" : "") . " by $loginname on " . date('Y-m-d H:i:s');

                if ($existingPso) {
                    // Update existing row
                    $stmtUpd = $db->prepare("
                        UPDATE krf_production_stockorder 
                        SET quantity = ?, 
                            sowingbalqty = CASE WHEN (sowingbalqty IS NULL OR sowingbalqty = 0) THEN ? ELSE sowingbalqty END,
                            status = CASE WHEN LOWER(status) IN ('remove', 'removed', 'cancelled', 'cancel') THEN 'Confirmed' ELSE status END,
                            tedit = NULL,
                            editedby = ?,
                            editedon = CURDATE(),
                            editedremark = CONCAT(IFNULL(editedremark, ''), ' | ', ?),
                            updated_at = NOW()
                        WHERE stockorder_no = ?
                    ");
                    $stmtUpd->bind_param("iisss", $syncQty, $syncQty, $loginname, $remarkLog, $so);
                    $stmtUpd->execute();
                    $stmtUpd->close();
                    $updatedCount++;
                } else {
                    // Insert new row using schedule context
                    $psono = $chosenRow['psono'] ?? '';
                    $projectid = (int)($chosenRow['projectid'] ?? 17);
                    $productid = $chosenRow['productid'] ?? '';
                    $productname = $chosenRow['productname'] ?? '';
                    $sowingDate = $chosenRow['sowing_date'] ?? null;
                    $roottype = $isGrafted ? 'Product' : ($chosenRow['roottype'] ?? null);

                    $companyid = 19;
                    $subcompanyid = 12;
                    $clientid = '';
                    $expDeliveryDate = null;

                    if (!empty($psono)) {
                        $qCp = $db->prepare("SELECT companyid, subcompanyid, clientid, expected_delivery_date FROM krf_provisional_sales_order WHERE psono = ? LIMIT 1");
                        if ($qCp) {
                            $qCp->bind_param("s", $psono);
                            $qCp->execute();
                            $cpRow = $qCp->get_result()->fetch_assoc();
                            if ($cpRow) {
                                if (!empty($cpRow['companyid'])) $companyid = (int)$cpRow['companyid'];
                                if (!empty($cpRow['subcompanyid'])) $subcompanyid = (int)$cpRow['subcompanyid'];
                                if (!empty($cpRow['clientid'])) $clientid = trim($cpRow['clientid']);
                                if (!empty($cpRow['expected_delivery_date'])) $expDeliveryDate = $cpRow['expected_delivery_date'];
                            }
                            $qCp->close();
                        }
                    }

                    $stmtIns = $db->prepare("
                        INSERT INTO krf_production_stockorder 
                        (psono, stockorder_no, projectid, companyid, subcompanyid, clientid, productid, product_name, quantity, sowingbalqty, expected_delivery_date, status, created_by, created_at, updated_at, sowingdate, sowingstatus, scionsowingdate, scionsowingstatus, roottype, access_qty_fromproduction, editedby, editedon, editedremark)
                        VALUES
                        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Confirmed', ?, NOW(), NOW(), ?, 'pending', ?, 'pending', ?, 0, ?, CURDATE(), ?)
                    ");
                    $stmtIns->bind_param("ssiiisssiisssssss", 
                        $psono, $so, $projectid, $companyid, $subcompanyid, $clientid, $productid, $productname, 
                        $syncQty, $syncQty, $expDeliveryDate, $loginname, $sowingDate, $sowingDate, $roottype, $loginname, $remarkLog
                    );
                    $stmtIns->execute();
                    $stmtIns->close();
                    $insertedCount++;
                }

                $syncDetails[] = [
                    'stockorder_no' => $so,
                    'synced_qty' => $syncQty,
                    'is_grafted' => $isGrafted
                ];
            }

            $db->commit();
            echo json_encode([
                'success' => true,
                'message' => "Successfully synced " . ($updatedCount + $insertedCount) . " production stock order(s) (Updated: $updatedCount, Added: $insertedCount).",
                'updated' => $updatedCount,
                'inserted' => $insertedCount,
                'details' => $syncDetails
            ]);
            exit;
        } catch (Exception $e) {
            $db->rollback();
            echo json_encode(['success' => false, 'message' => 'Sync failed: ' . $e->getMessage()]);
            exit;
        }
    }

    // 3. Fetch Details for modal
    if ($action === 'get_order_details') {
        $so = trim($_POST['stockorder_no'] ?? '');
        if ($so === '') {
            echo json_encode(['success' => false, 'message' => 'Stock order required.']);
            exit;
        }

        // Fetch PSO rows
        $psoRows = [];
        $qPso = $db->prepare("SELECT * FROM krf_production_stockorder WHERE stockorder_no = ?");
        $qPso->bind_param("s", $so);
        $qPso->execute();
        $resPso = $qPso->get_result();
        while ($r = $resPso->fetch_assoc()) $psoRows[] = $r;
        $qPso->close();

        // Fetch PSS rows
        $pssRows = [];
        $qPss = $db->prepare("SELECT * FROM krf_polyhouse_sowingschedule WHERE stockorder_no = ?");
        $qPss->bind_param("s", $so);
        $qPss->execute();
        $resPss = $qPss->get_result();
        while ($r = $resPss->fetch_assoc()) $pssRows[] = $r;
        $qPss->close();

        // Fetch SPD summary
        $spdSummary = null;
        $qSpd = $db->prepare("
            SELECT COUNT(*) as tray_count, 
                   SUM(COALESCE(CAST(traycavity AS SIGNED), 0)) as plant_count,
                   MAX(orderqty) as orderqty, 
                   MAX(totalsowingqty) as totalsowingqty,
                   GROUP_CONCAT(DISTINCT status) as statuses,
                   GROUP_CONCAT(DISTINCT workingstatus) as workingstatuses,
                   GROUP_CONCAT(DISTINCT roottype) as roottypes,
                   MIN(sowing_date) as min_sowing_date,
                   MAX(sowing_date) as max_sowing_date
            FROM krf_polyhouse_sowingplant_details 
            WHERE stockorder_no = ?
        ");
        $qSpd->bind_param("s", $so);
        $qSpd->execute();
        $resSpd = $qSpd->get_result();
        $spdSummary = $resSpd->fetch_assoc();
        $qSpd->close();

        // Fetch Polyhouse Plant details summary
        $phSummary = null;
        $qPh = $db->prepare("
            SELECT COUNT(*) as tray_count, 
                   SUM(COALESCE(CAST(qtypertray AS SIGNED), 126) - COALESCE(CAST(failurecount AS SIGNED), 0)) as plant_count,
                   GROUP_CONCAT(DISTINCT status) as statuses,
                   GROUP_CONCAT(DISTINCT workingstatus) as workingstatuses,
                   GROUP_CONCAT(DISTINCT growth_stage) as growth_stages,
                   MIN(planted_date) as min_planted_date,
                   MAX(planted_date) as max_planted_date
            FROM krf_polyhouse_plant_details 
            WHERE stockorder_no = ?
        ");
        $qPh->bind_param("s", $so);
        $qPh->execute();
        $resPh = $qPh->get_result();
        $phSummary = $resPh->fetch_assoc();
        $qPh->close();

        echo json_encode([
            'success' => true,
            'stockorder_no' => $so,
            'pso' => $psoRows,
            'pss' => $pssRows,
            'spd' => $spdSummary,
            'ph'  => $phSummary
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit;
}

// =============================================================================
// MAIN AUDIT QUERY & DATA PREPARATION
// =============================================================================

// Fetch view filter mode from GET
$viewMode = $_GET['view'] ?? 'active'; // 'active' (tedit!=1), 'removed' (tedit=1), 'all'

// 1. Grouped Sowing Plant Details (SPD)
$spdMap = [];
$qSpd = $db->query("
    SELECT stockorder_no, 
           COUNT(*) as tray_count, 
           SUM(COALESCE(CAST(traycavity AS SIGNED), 0)) as spd_plant_count,
           MAX(orderqty) as spd_orderqty, 
           MAX(totalsowingqty) as spd_totalsowingqty,
           GROUP_CONCAT(DISTINCT productid) as spd_productids,
           GROUP_CONCAT(DISTINCT productname) as spd_productnames,
           GROUP_CONCAT(DISTINCT roottype) as spd_roottypes,
           GROUP_CONCAT(DISTINCT status) as spd_statuses,
           GROUP_CONCAT(DISTINCT tedit) as spd_tedits
    FROM krf_polyhouse_sowingplant_details 
    WHERE stockorder_no IS NOT NULL AND stockorder_no != ''
    GROUP BY stockorder_no
");
if ($qSpd) {
    while ($r = $qSpd->fetch_assoc()) {
        $spdMap[$r['stockorder_no']] = $r;
    }
}

// 2. Grouped Polyhouse Plant Details (PH)
$phMap = [];
$qPh = $db->query("
    SELECT stockorder_no, 
           COUNT(*) as tray_count, 
           SUM(COALESCE(CAST(qtypertray AS SIGNED), 126) - COALESCE(CAST(failurecount AS SIGNED), 0)) as plant_count,
           GROUP_CONCAT(DISTINCT status) as ph_statuses
    FROM krf_polyhouse_plant_details 
    WHERE stockorder_no IS NOT NULL AND stockorder_no != ''
    GROUP BY stockorder_no
");
if ($qPh) {
    while ($r = $qPh->fetch_assoc()) {
        $phMap[$r['stockorder_no']] = $r;
    }
}

// 3. Grouped Sowing Schedule (PSS)
$pssMap = [];
$qPss = $db->query("
    SELECT stockorder_no, 
           COUNT(*) as pss_row_count,
           GROUP_CONCAT(sowingschedulid) as pss_ids,
           MAX(psono) as pss_psono,
           MAX(orderqty) as pss_orderqty,
           MAX(CASE WHEN LOWER(roottype) = 'scion' THEN orderqty ELSE NULL END) as scion_orderqty,
           MAX(CASE WHEN LOWER(roottype) = 'root' THEN orderqty ELSE NULL END) as root_orderqty,
           SUM(orderqty) as pss_sum_orderqty,
           MAX(sowingqty) as pss_sowingqty,
           SUM(sowingqty) as pss_sum_sowingqty,
           GROUP_CONCAT(DISTINCT productid) as pss_productids,
           GROUP_CONCAT(DISTINCT productname) as pss_productnames,
           GROUP_CONCAT(DISTINCT roottype) as pss_roottypes,
           GROUP_CONCAT(DISTINCT status) as pss_statuses,
           GROUP_CONCAT(DISTINCT tedit) as pss_tedits,
           GROUP_CONCAT(DISTINCT teditremark) as pss_teditremarks,
           MIN(sowing_date) as pss_sowing_date
    FROM krf_polyhouse_sowingschedule 
    WHERE stockorder_no IS NOT NULL AND stockorder_no != ''
    GROUP BY stockorder_no
");
if ($qPss) {
    while ($r = $qPss->fetch_assoc()) {
        $pssMap[$r['stockorder_no']] = $r;
    }
}

// 3. Grouped Production Stock Order (PSO)
$psoMap = [];
$qPso = $db->query("
    SELECT stockorder_no,
           COUNT(*) as pso_row_count,
           GROUP_CONCAT(id) as pso_ids,
           MAX(psono) as pso_psono,
           MAX(productid) as pso_productid,
           MAX(product_name) as pso_productname,
           MAX(quantity) as pso_quantity,
           SUM(quantity) as pso_sum_quantity,
           GROUP_CONCAT(DISTINCT roottype) as pso_roottypes,
           GROUP_CONCAT(DISTINCT status) as pso_statuses,
           GROUP_CONCAT(DISTINCT tedit) as pso_tedits,
           GROUP_CONCAT(DISTINCT teditremark) as pso_teditremarks,
           MAX(created_at) as pso_created_at
    FROM krf_production_stockorder
    WHERE stockorder_no IS NOT NULL AND stockorder_no != ''
    GROUP BY stockorder_no
");
if ($qPso) {
    while ($r = $qPso->fetch_assoc()) {
        $psoMap[$r['stockorder_no']] = $r;
    }
}

// 4. Combine all distinct stock orders from PSO and PSS
$allStockOrders = array_unique(array_merge(array_keys($psoMap), array_keys($pssMap)));

// Compute items and statistics
$records = [];
$stats = [
    'total_issues'        => 0,
    'not_in_sowing'       => 0,
    'missing_in_schedule' => 0,
    'missing_in_stockorder' => 0,
    'qty_mismatch'        => 0,
    'status_mismatch'     => 0,
    'pso_qty_zero'        => 0,
    'already_removed'     => 0,
];

foreach ($allStockOrders as $so) {
    $pso = $psoMap[$so] ?? null;
    $pss = $pssMap[$so] ?? null;
    $spd = $spdMap[$so] ?? null;

    // Resolve order metadata first
    $psono = $pso['pso_psono'] ?? ($pss['pss_psono'] ?? '—');
    $productName = $pso['pso_productname'] ?? ($pss['pss_productnames'] ?? ($spd['spd_productnames'] ?? '—'));
    $productId = $pso['pso_productid'] ?? ($pss['pss_productids'] ?? ($spd['spd_productids'] ?? '—'));
    $rootType = $pso['pso_roottypes'] ?? ($pss['pss_roottypes'] ?? ($spd['spd_roottypes'] ?? '—'));
    $teditRemark = trim(($pso['pso_teditremarks'] ?? '') . ' ' . ($pss['pss_teditremarks'] ?? ''));

    // Check if already removed / marked tedit=1
    $isPsoRemoved = ($pso && in_array('1', explode(',', (string)$pso['pso_tedits'])));
    $isPssRemoved = ($pss && in_array('1', explode(',', (string)$pss['pss_tedits'])));
    $isMarkedRemoved = ($isPsoRemoved || $isPssRemoved);

    if ($isMarkedRemoved) {
        $stats['already_removed']++;
    }

    // Determine issue flags
    $issues = [];
    $isIssue = false;

    // A) Not in sowing table (krf_polyhouse_sowingplant_details)
    $hasSpd = ($spd !== null && (int)$spd['tray_count'] > 0);
    if (!$hasSpd) {
        $issues[] = 'not_in_sowing';
        if (!$isMarkedRemoved) $stats['not_in_sowing']++;
        $isIssue = true;
    }

    // B) Missing in Sowing Schedule
    if ($pso && !$pss) {
        $issues[] = 'missing_in_schedule';
        if (!$isMarkedRemoved) $stats['missing_in_schedule']++;
        $isIssue = true;
    }

    // C) Missing in Stock Order
    if (!$pso && $pss) {
        $issues[] = 'missing_in_stockorder';
        if (!$isMarkedRemoved) $stats['missing_in_stockorder']++;
        $isIssue = true;
    }

    // D) Qty Mismatch
    $qtyMismatch = false;
    $psoQty = $pso ? (int)$pso['pso_quantity'] : null;
    $pssQty = $pss ? (int)$pss['pss_orderqty'] : null;
    $spdQty = $spd ? (int)$spd['spd_orderqty'] : null;

    if ($psoQty !== null && $pssQty !== null && $psoQty !== $pssQty) {
        $qtyMismatch = true;
    }
    if ($psoQty !== null && $spdQty !== null && $psoQty !== $spdQty) {
        $qtyMismatch = true;
    }
    if ($pssQty !== null && $spdQty !== null && $pssQty !== $spdQty) {
        $qtyMismatch = true;
    }

    if ($qtyMismatch) {
        $issues[] = 'qty_mismatch';
        if (!$isMarkedRemoved) $stats['qty_mismatch']++;
        $isIssue = true;
    }

    // E) Status Mismatch (e.g. PSO Cancelled / Removed but PSS pending/done, or vice versa)
    $psoStatus = $pso['pso_statuses'] ?? '';
    $pssStatus = $pss['pss_statuses'] ?? '';
    if ($pso && $pss) {
        $psoInactive = (stripos($psoStatus, 'cancel') !== false || stripos($psoStatus, 'remove') !== false);
        $pssInactive = (stripos($pssStatus, 'cancel') !== false || stripos($pssStatus, 'remove') !== false || stripos($pssStatus, 'delete') !== false);
        if ($psoInactive !== $pssInactive) {
            $issues[] = 'status_mismatch';
            if (!$isMarkedRemoved) $stats['status_mismatch']++;
            $isIssue = true;
        }
    }

    // F) PSO Qty is 0 (or missing) but Sowing Schedule has quantity -> Ready to Sync
    // Detect grafting: whether schedule has scion_orderqty OR product name explicitly contains 'graft' without 'nongraft' / 'non-graft'
    $cleanedProd = str_ireplace(['non-graft', 'nongraft', 'non graft'], '', (string)$productName);
    $isGrafted = (!empty($pss['scion_orderqty'])) || (stripos($cleanedProd, 'graft') !== false);
    $syncSchedQty = ($isGrafted && !empty($pss['scion_orderqty'])) ? (int)$pss['scion_orderqty'] : (int)($pss['pss_orderqty'] ?? 0);
    $canSyncPso = ($psoQty === null || $psoQty <= 0) && ($syncSchedQty > 0);

    if ($canSyncPso) {
        $issues[] = 'pso_qty_zero';
        if (!$isMarkedRemoved) {
            $stats['pso_qty_zero']++;
            $isIssue = true;
        }
    }

    if ($isIssue && !$isMarkedRemoved) {
        $stats['total_issues']++;
    }

    // Filter view according to viewMode
    if ($viewMode === 'active' && $isMarkedRemoved) {
        continue; // Only active issues
    } elseif ($viewMode === 'removed' && !$isMarkedRemoved) {
        continue; // Only removed / tedit=1 records
    }

    $ph = $phMap[$so] ?? null;
    $phTrayCount = $ph ? (int)$ph['tray_count'] : 0;
    $phPlantCount = $ph ? max(0, (int)$ph['plant_count']) : 0;

    $spdTrayCount = $spd ? (int)$spd['tray_count'] : 0;
    $spdPlantCount = ($spd && (int)$spd['spd_plant_count'] > 0) ? (int)$spd['spd_plant_count'] : ($spd ? (int)$spd['spd_totalsowingqty'] : 0);

    $records[] = [
        'stockorder_no'     => $so,
        'psono'             => $psono,
        'product_id'        => $productId,
        'product_name'      => $productName,
        'roottype'          => $rootType,
        'pso_qty'           => $psoQty,
        'pss_orderqty'      => $pssQty,
        'pss_sowingqty'     => $pss ? (int)$pss['pss_sowingqty'] : null,
        'spd_orderqty'      => $spdQty,
        'spd_tray_count'    => $spdTrayCount,
        'spd_plant_count'   => $spdPlantCount,
        'spd_totalsowingqty'=> $spd ? (int)$spd['spd_totalsowingqty'] : 0,
        'ph_tray_count'     => $phTrayCount,
        'ph_plant_count'    => $phPlantCount,
        'pso_status'        => $psoStatus,
        'pss_status'        => $pssStatus,
        'spd_status'        => $spd['spd_statuses'] ?? 'None',
        'ph_status'         => $ph['ph_statuses'] ?? 'None',
        'is_pso'            => (bool)$pso,
        'is_pss'            => (bool)$pss,
        'is_spd'            => $hasSpd,
        'is_ph'             => ($phTrayCount > 0),
        'is_removed'        => $isMarkedRemoved,
        'is_grafted'        => $isGrafted,
        'sync_sched_qty'    => $syncSchedQty,
        'can_sync_pso'      => $canSyncPso,
        'tedit_remark'      => $teditRemark,
        'issues'            => $issues,
        'created_at'        => $pso['pso_created_at'] ?? ($pss['pss_sowing_date'] ?? '—'),
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Production Stock Order & Sowing Mismatch Auditor (Extra Showing)</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables & Plugins -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        :root {
            --bs-body-font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
            --brand-primary: #0284c7;
            --brand-dark: #0f172a;
            --brand-surface: #f8fafc;
        }
        body {
            background-color: #f1f5f9;
            color: #334155;
            font-size: 0.885rem;
            min-height: 100vh;
        }
        .app-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-bottom: 1px solid #334155;
        }
        .stat-card {
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 12px;
            background: #ffffff;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
            position: relative;
        }
        .stat-card.card-filter {
            cursor: pointer;
            user-select: none;
        }
        .stat-card.card-filter:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.09);
            border-color: #94a3b8;
        }
        .stat-card.card-filter.active-filter-card {
            border: 2px solid #0284c7 !important;
            background: #f0f9ff !important;
            box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.18) !important;
            transform: translateY(-2px);
        }
        .stat-card.card-filter.active-filter-card .active-pill-badge {
            display: inline-block !important;
        }
        .active-pill-badge {
            display: none;
            position: absolute;
            top: 8px;
            right: 8px;
            font-size: 0.65rem;
            font-weight: 800;
            color: #0284c7;
            background: #e0f2fe;
            border: 1px solid #bae6fd;
            padding: 2px 7px;
            border-radius: 12px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        .table-card {
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }
        .table thead th {
            background-color: #f8fafc;
            color: #475569;
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0;
            padding: 10px 12px;
            vertical-align: middle;
            white-space: nowrap;
        }
        .table tbody td {
            padding: 10px 12px;
            vertical-align: middle;
            font-size: 0.84rem;
            border-bottom: 1px solid #f1f5f9;
        }
        .stockorder-code {
            font-family: var(--font-mono);
            font-weight: 700;
            color: #0369a1;
            background-color: #e0f2fe;
            padding: 2px 7px;
            border-radius: 5px;
            border: 1px solid #bae6fd;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .issue-badge {
            font-size: 0.72rem;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin: 2px 1px;
            white-space: nowrap;
        }
        .badge-not-sown {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
        }
        .badge-no-schedule {
            background-color: #ffedd5;
            color: #c2410c;
            border: 1px solid #fdba74;
        }
        .badge-no-pso {
            background-color: #f3e8ff;
            color: #7e22ce;
            border: 1px solid #d8b4fe;
        }
        .badge-qty-diff {
            background-color: #fef3c7;
            color: #b45309;
            border: 1px solid #fcd34d;
        }
        .badge-status-diff {
            background-color: #e0e7ff;
            color: #4338ca;
            border: 1px solid #c7d2fe;
        }
        .badge-pso-zero {
            background-color: #ecfdf5;
            color: #047857;
            border: 1px solid #6ee7b7;
        }
        .btn-xs {
            padding: 2px 7px;
            font-size: 0.72rem;
            line-height: 1.25;
            border-radius: 4px;
        }
        .badge-removed-tedit {
            background-color: #f1f5f9;
            color: #64748b;
            border: 1px solid #cbd5e1;
            text-decoration: line-through;
        }
        .qty-val {
            font-family: var(--font-mono);
            font-weight: 600;
        }
        .qty-mismatch-val {
            color: #dc2626;
            font-weight: 700;
            background-color: #fee2e2;
            padding: 1px 5px;
            border-radius: 4px;
        }
        .filter-btn-group .btn {
            font-weight: 600;
            font-size: 0.8rem;
            padding: 6px 14px;
        }
        .btn-action-remove {
            padding: 4px 10px;
            font-size: 0.78rem;
            font-weight: 600;
            border-radius: 6px;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <header class="app-header text-white py-3 shadow-sm mb-4">
        <div class="container-fluid px-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-primary bg-opacity-25 p-2 text-primary border border-primary border-opacity-25">
                        <i class="fa-solid fa-layer-group fa-xl"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h4 class="mb-0 fw-bold tracking-tight">Extra Showing & Stock Order Auditor</h4>
                            <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-25 px-2 py-1">Auditor & Cleaner</span>
                        </div>
                        <p class="text-white-50 small mb-0 mt-0.5">
                            Cross-audit <code>krf_production_stockorder</code>, <code>krf_polyhouse_sowingschedule</code> vs. <code>krf_polyhouse_sowingplant_details</code>
                        </p>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <a href="../showingplan_starting.php" class="btn btn-sm btn-outline-light">
                        <i class="fa fa-arrow-left me-1"></i> Showing Plan
                    </a>
                    <a href="../openstockproduct.php" class="btn btn-sm btn-outline-light">
                        <i class="fa fa-boxes-stacked me-1"></i> Open Stock
                    </a>
                    <button class="btn btn-sm btn-light fw-bold" onclick="window.location.reload();">
                        <i class="fa-solid fa-arrows-rotate me-1"></i> Refresh
                    </button>
                </div>
            </div>
        </div>
    </header>

    <div class="container-fluid px-4 pb-5">

        <!-- Quick Summary KPI Cards (Clickable Filters) -->
        <div class="row g-3 mb-4">
            <!-- Total Active Issues -->
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="stat-card card-filter p-3 h-100 <?= ($viewMode === 'active' ? 'active-filter-card' : '') ?>" 
                     data-issue="" title="Click to view all active issues">
                    <span class="active-pill-badge"><i class="fa-solid fa-check me-1"></i>Active</span>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-bold small text-uppercase">Total Issues</span>
                        <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                    </div>
                    <div class="h3 fw-bold mb-0 text-dark"><?= number_format($stats['total_issues']) ?></div>
                    <div class="text-muted small mt-1">Click to show all issues</div>
                </div>
            </div>

            <!-- Not in Sowing Table -->
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="stat-card card-filter p-3 h-100" 
                     data-issue="Not Sown" title="Click to filter by Not Sown (No SPD)">
                    <span class="active-pill-badge"><i class="fa-solid fa-filter me-1"></i>Filtered</span>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-bold small text-uppercase">Not Sown (No SPD)</span>
                        <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                            <i class="fa-solid fa-seedling"></i>
                        </div>
                    </div>
                    <div class="h3 fw-bold mb-0 text-danger"><?= number_format($stats['not_in_sowing']) ?></div>
                    <div class="text-muted small mt-1">Click to filter table</div>
                </div>
            </div>

            <!-- Missing in Schedule -->
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="stat-card card-filter p-3 h-100" 
                     data-issue="No Schedule" title="Click to filter by Missing in Schedule">
                    <span class="active-pill-badge"><i class="fa-solid fa-filter me-1"></i>Filtered</span>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-bold small text-uppercase">Missing in Schedule</span>
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                            <i class="fa-regular fa-calendar-xmark"></i>
                        </div>
                    </div>
                    <div class="h3 fw-bold mb-0 text-warning"><?= number_format($stats['missing_in_schedule']) ?></div>
                    <div class="text-muted small mt-1">Click to filter table</div>
                </div>
            </div>

            <!-- Qty Mismatched -->
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="stat-card card-filter p-3 h-100" 
                     data-issue="Qty Diff" title="Click to filter by Qty Mismatched">
                    <span class="active-pill-badge"><i class="fa-solid fa-filter me-1"></i>Filtered</span>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-bold small text-uppercase">Qty Mismatched</span>
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                            <i class="fa-solid fa-scale-unbalanced"></i>
                        </div>
                    </div>
                    <div class="h3 fw-bold mb-0 text-warning"><?= number_format($stats['qty_mismatch']) ?></div>
                    <div class="text-muted small mt-1">Click to filter table</div>
                </div>
            </div>

            <!-- Status Mismatch -->
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="stat-card card-filter p-3 h-100" 
                     data-issue="Status Diff" title="Click to filter by Status Mismatched">
                    <span class="active-pill-badge"><i class="fa-solid fa-filter me-1"></i>Filtered</span>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-bold small text-uppercase">Status Mismatched</span>
                        <div class="stat-icon bg-info bg-opacity-10 text-info">
                            <i class="fa-solid fa-shuffle"></i>
                        </div>
                    </div>
                    <div class="h3 fw-bold mb-0 text-info"><?= number_format($stats['status_mismatch']) ?></div>
                    <div class="text-muted small mt-1">Click to filter table</div>
                </div>
            </div>

            <!-- PSO Qty 0 (Sync Available) -->
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="stat-card card-filter p-3 h-100" 
                     data-issue="PSO Qty 0" title="Click to filter by PSO Qty is 0 (Ready to Sync)">
                    <span class="active-pill-badge"><i class="fa-solid fa-filter me-1"></i>Filtered</span>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-bold small text-uppercase">PSO Qty 0 (Sync)</span>
                        <div class="stat-icon bg-success bg-opacity-10 text-success">
                            <i class="fa-solid fa-arrows-rotate"></i>
                        </div>
                    </div>
                    <div class="h3 fw-bold mb-0 text-success"><?= number_format($stats['pso_qty_zero']) ?></div>
                    <div class="text-muted small mt-1">Click to filter table</div>
                </div>
            </div>

            <!-- Already Removed (tedit=1) -->
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="stat-card card-filter p-3 h-100 <?= ($viewMode === 'removed' ? 'active-filter-card' : '') ?>" 
                     data-issue="tedit" title="Click to switch to Removed (tedit=1) records">
                    <span class="active-pill-badge"><i class="fa-solid fa-check me-1"></i>Active</span>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-bold small text-uppercase">Removed (tedit=1)</span>
                        <div class="stat-icon bg-secondary bg-opacity-10 text-secondary">
                            <i class="fa-solid fa-trash-can"></i>
                        </div>
                    </div>
                    <div class="h3 fw-bold mb-0 text-secondary"><?= number_format($stats['already_removed']) ?></div>
                    <div class="text-muted small mt-1">Click to view removed</div>
                </div>
            </div>
        </div>

        <!-- Filter Controls & Bulk Action Bar -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <!-- View Mode Pills -->
                <div class="btn-group filter-btn-group shadow-sm" role="group">
                    <a href="?view=active" class="btn <?= $viewMode === 'active' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                        <i class="fa-solid fa-bolt me-1"></i> Active Issues (<?= number_format($stats['total_issues']) ?>)
                    </a>
                    <a href="?view=removed" class="btn <?= $viewMode === 'removed' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                        <i class="fa-solid fa-trash-can me-1"></i> Removed Records (<?= number_format($stats['already_removed']) ?>)
                    </a>
                    <a href="?view=all" class="btn <?= $viewMode === 'all' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                        <i class="fa-solid fa-list me-1"></i> View All (<?= number_format(count($allStockOrders)) ?>)
                    </a>
                </div>

                <!-- Custom Table Issue Type Filter Dropdown -->
                <div class="d-inline-block">
                    <select id="issueTypeFilter" class="form-select form-select-sm fw-semibold shadow-sm" style="min-width: 220px;">
                        <option value="">Filter by Issue Type (All)</option>
                        <option value="Not Sown">🔴 Not in Sowing Table (No SPD)</option>
                        <option value="No Schedule">🟠 Missing in Sowing Schedule</option>
                        <option value="Qty Diff">🟡 Quantity Mismatch</option>
                        <option value="Status Diff">🔵 Status Mismatch</option>
                        <option value="PSO Qty 0">⚡ PSO Qty is 0 (Ready to Sync)</option>
                    </select>
                </div>
            </div>

            <!-- Bulk Actions -->
            <div class="d-flex align-items-center gap-2">
                <span id="selectedCountBadge" class="badge bg-secondary py-2 px-3 fw-bold" style="display: none;">
                    0 selected
                </span>
                <button type="button" id="btnBulkSyncPso" class="btn btn-sm btn-success fw-bold shadow-sm" style="display: none;">
                    <i class="fa-solid fa-arrows-rotate me-1"></i> Sync Selected PSO Qty
                </button>
                <button type="button" id="btnBulkRemovePss" class="btn btn-sm btn-outline-warning text-dark fw-bold shadow-sm" style="display: none;">
                    <i class="fa-regular fa-calendar-xmark me-1"></i> Remove Schedule Only (PSS)
                </button>
                <button type="button" id="btnBulkRemoveBoth" class="btn btn-sm btn-danger fw-bold shadow-sm" style="display: none;">
                    <i class="fa-solid fa-trash-can me-1"></i> Remove Both (PSO & PSS)
                </button>
            </div>
        </div>

        <!-- Active Filter Indicator Banner -->
        <div id="activeFilterBanner" class="alert alert-info py-2 px-3 mb-3 d-flex align-items-center justify-content-between shadow-sm" style="display: none; border-radius: 10px; border-left: 4px solid #0284c7;">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-filter text-primary"></i>
                <span class="text-dark">Filtered by: <b id="activeFilterLabel" class="text-primary">-</b></span>
                <span class="badge bg-primary" id="filteredRowCountBadge">0 rows</span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 fw-semibold" id="btnClearCardFilter" title="Reset filter">
                <i class="fa-solid fa-times me-1"></i> Clear Filter
            </button>
        </div>

        <!-- Main Data Table Card -->
        <div class="table-card">
            <div class="p-3 border-bottom bg-light d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-table-list text-primary"></i>
                    <span class="fw-bold text-dark">Stock Order Audit Records</span>
                    <span class="badge bg-secondary rounded-pill"><?= number_format(count($records)) ?> rows</span>
                </div>
                <div class="text-muted small">
                    <i class="fa-solid fa-info-circle me-1"></i> Click <b>Remove (tedit=1)</b> to mark order as soft-deleted with an audit remark.
                </div>
            </div>

            <div class="p-3">
                <div class="table-responsive">
                    <table id="auditTable" class="table table-hover align-middle w-100">
                        <thead>
                            <tr>
                                <th style="width: 35px;" class="text-center no-sort">
                                    <input type="checkbox" class="form-check-input" id="selectAllCheckbox" title="Select All">
                                </th>
                                <th>Stock Order No</th>
                                <th>PSO No</th>
                                <th>Product Details</th>
                                <th class="text-center">Root</th>
                                <th class="text-end">PSO Qty</th>
                                <th class="text-end">Schedule Qty</th>
                                <th class="text-end">Sowing Plants (SPD)</th>
                                <th class="text-end">Polyhouse Plants (PH)</th>
                                <th>Status (PSO / PSS)</th>
                                <th>Audit Issues</th>
                                <th>tedit Status</th>
                                <th class="text-center no-sort" style="width: 250px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $row): 
                                $so = htmlspecialchars($row['stockorder_no']);
                                $isRemoved = $row['is_removed'];
                                $hasQtyDiff = in_array('qty_mismatch', $row['issues']);
                                $canSync = !empty($row['can_sync_pso']);
                            ?>
                            <tr data-stockorder="<?= $so ?>" class="<?= $isRemoved ? 'table-secondary opacity-75' : '' ?>">
                                <!-- Checkbox -->
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input row-select-checkbox" value="<?= $so ?>" data-cansync="<?= $canSync ? '1' : '0' ?>">
                                </td>

                                <!-- Stock Order No -->
                                <td>
                                    <span class="stockorder-code" title="Click to copy" onclick="navigator.clipboard.writeText('<?= $so ?>'); Swal.fire({toast:true, position:'top-end', icon:'success', title:'Copied <?= $so ?>', showConfirmButton:false, timer:1500});">
                                        <i class="fa-regular fa-copy small text-muted"></i>
                                        <?= $so ?>
                                    </span>
                                    <div class="text-muted" style="font-size: 0.72rem;"><?= htmlspecialchars($row['created_at']) ?></div>
                                </td>

                                <!-- PSO No -->
                                <td>
                                    <span class="fw-semibold text-dark"><?= htmlspecialchars($row['psono']) ?></span>
                                </td>

                                <!-- Product Details -->
                                <td>
                                    <div class="fw-semibold text-dark text-truncate" style="max-width: 260px;" title="<?= htmlspecialchars($row['product_name']) ?>">
                                        <?= htmlspecialchars($row['product_name']) ?>
                                    </div>
                                    <span class="text-muted small">PID: <?= htmlspecialchars($row['product_id']) ?></span>
                                </td>

                                <!-- Root Type -->
                                <td class="text-center">
                                    <?php if ($row['roottype']): ?>
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($row['roottype']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>

                                <!-- PSO Qty -->
                                <td class="text-end">
                                    <?php if ($row['pso_qty'] !== null): ?>
                                        <span class="qty-val <?= ($hasQtyDiff || $canSync) ? 'qty-mismatch-val' : 'text-dark' ?>">
                                            <?= number_format($row['pso_qty']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">Missing</span>
                                    <?php endif; ?>

                                    <?php if ($canSync): ?>
                                        <div class="mt-1">
                                            <button type="button" class="btn btn-xs btn-outline-success py-0 px-1 fw-bold btn-action-sync-pso" 
                                                    data-stockorder="<?= $so ?>"
                                                    data-syncqty="<?= $row['sync_sched_qty'] ?>"
                                                    data-isgrafted="<?= $row['is_grafted'] ? '1' : '0' ?>"
                                                    title="Sync PSO Qty as per Schedule (<?= number_format($row['sync_sched_qty']) ?><?= $row['is_grafted'] ? ' - Scion' : '' ?>)">
                                                <i class="fa-solid fa-arrows-rotate me-1"></i>Sync (<?= number_format($row['sync_sched_qty']) ?>)
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- PSS Schedule Qty -->
                                <td class="text-end">
                                    <?php if ($row['pss_orderqty'] !== null): ?>
                                        <div class="qty-val <?= $hasQtyDiff ? 'qty-mismatch-val' : 'text-dark' ?>">
                                            <?= number_format($row['pss_orderqty']) ?>
                                        </div>
                                        <div class="text-muted" style="font-size: 0.7rem;">Sow: <?= number_format($row['pss_sowingqty']) ?></div>
                                    <?php else: ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">Missing</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Sowing Plant Details (SPD) -->
                                <td class="text-end">
                                    <?php if ($row['spd_tray_count'] > 0): ?>
                                        <div class="fw-bold text-success">
                                            <i class="fa-solid fa-seedling me-1"></i><?= number_format($row['spd_tray_count']) ?> Trays
                                        </div>
                                        <div class="text-muted" style="font-size: 0.72rem;">
                                            <?= number_format($row['spd_plant_count']) ?> Plants
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">0 Trays (0 Plants)</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Polyhouse Plant Details (PH) -->
                                <td class="text-end">
                                    <?php if ($row['ph_tray_count'] > 0): ?>
                                        <div class="fw-bold text-primary">
                                            <i class="fa-solid fa-warehouse me-1"></i><?= number_format($row['ph_tray_count']) ?> Trays
                                        </div>
                                        <div class="text-muted" style="font-size: 0.72rem;">
                                            <?= number_format($row['ph_plant_count']) ?> Plants
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border">0 Trays (0 Plants)</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Statuses -->
                                <td>
                                    <div class="small">
                                        <span class="text-muted">PSO:</span> 
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($row['pso_status'] ?: 'None') ?></span>
                                    </div>
                                    <div class="small mt-1">
                                        <span class="text-muted">PSS:</span> 
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($row['pss_status'] ?: 'None') ?></span>
                                    </div>
                                </td>

                                <!-- Issue Badges -->
                                <td>
                                    <?php if ($canSync): ?>
                                        <span class="issue-badge badge-pso-zero" title="PSO quantity is 0 or missing, but schedule has <?= number_format($row['sync_sched_qty']) ?><?= $row['is_grafted'] ? ' (Scion)' : '' ?> - Ready to Sync">
                                            <i class="fa-solid fa-bolt text-warning"></i> PSO Qty 0
                                        </span>
                                    <?php endif; ?>
                                    <?php if (in_array('not_in_sowing', $row['issues'])): ?>
                                        <span class="issue-badge badge-not-sown" title="Record exists in PSO/PSS but not in sowing plant details table">
                                            <i class="fa-solid fa-circle-xmark"></i> Not Sown
                                        </span>
                                    <?php endif; ?>
                                    <?php if (in_array('missing_in_schedule', $row['issues'])): ?>
                                        <span class="issue-badge badge-no-schedule" title="Stock order in PSO has no corresponding row in krf_polyhouse_sowingschedule">
                                            <i class="fa-solid fa-calendar-xmark"></i> No Schedule
                                        </span>
                                    <?php endif; ?>
                                    <?php if (in_array('missing_in_stockorder', $row['issues'])): ?>
                                        <span class="issue-badge badge-no-pso" title="Schedule exists in PSS but not in krf_production_stockorder">
                                            <i class="fa-solid fa-file-circle-xmark"></i> No Stockorder
                                        </span>
                                    <?php endif; ?>
                                    <?php if (in_array('qty_mismatch', $row['issues'])): ?>
                                        <span class="issue-badge badge-qty-diff" title="Quantities differ between PSO, PSS or SPD">
                                            <i class="fa-solid fa-scale-unbalanced"></i> Qty Diff
                                        </span>
                                    <?php endif; ?>
                                    <?php if (in_array('status_mismatch', $row['issues'])): ?>
                                        <span class="issue-badge badge-status-diff" title="Status differs between PSO and PSS (e.g. Cancelled vs Active)">
                                            <i class="fa-solid fa-shuffle"></i> Status Diff
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- tedit Status -->
                                <td>
                                    <?php if ($isRemoved): ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25" title="<?= htmlspecialchars($row['tedit_remark']) ?>">
                                            <i class="fa-solid fa-ban me-1"></i> tedit = 1
                                        </span>
                                        <?php if ($row['tedit_remark']): ?>
                                            <div class="text-muted small text-truncate mt-1" style="max-width: 140px;" title="<?= htmlspecialchars($row['tedit_remark']) ?>">
                                                <?= htmlspecialchars($row['tedit_remark']) ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                            <i class="fa-solid fa-check me-1"></i> Active
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Actions -->
                                <td class="text-center">
                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                        <!-- View Details Button -->
                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-view-details" 
                                                data-stockorder="<?= $so ?>" title="View detailed cross-table breakdown">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>

                                        <?php if (!$isRemoved): ?>
                                            <?php if ($canSync): ?>
                                                <!-- Sync PSO Qty Button -->
                                                <button type="button" class="btn btn-sm btn-success fw-bold btn-action-sync-pso" 
                                                        data-stockorder="<?= $so ?>"
                                                        data-syncqty="<?= $row['sync_sched_qty'] ?>"
                                                        data-isgrafted="<?= $row['is_grafted'] ? '1' : '0' ?>"
                                                        title="Sync PSO Qty to <?= number_format($row['sync_sched_qty']) ?><?= $row['is_grafted'] ? ' (Scion Qty)' : '' ?> in krf_production_stockorder">
                                                    <i class="fa-solid fa-arrows-rotate me-1"></i> Sync
                                                </button>
                                            <?php endif; ?>

                                            <!-- Remove PSS Only (krf_polyhouse_sowingschedule ONLY) -->
                                            <button type="button" class="btn btn-sm btn-outline-warning text-dark fw-bold btn-action-remove-pss" 
                                                    data-stockorder="<?= $so ?>"
                                                    title="Mark tedit=1 ONLY in Sowing Schedule (krf_polyhouse_sowingschedule)">
                                                <i class="fa-regular fa-calendar-xmark me-1"></i> PSS Only
                                            </button>

                                            <!-- Remove Both (PSO + PSS) -->
                                            <button type="button" class="btn btn-sm btn-danger btn-action-remove" 
                                                    data-stockorder="<?= $so ?>"
                                                    data-psoqty="<?= (int)$row['pso_qty'] ?>"
                                                    data-pssqty="<?= (int)$row['pss_orderqty'] ?>"
                                                    title="Mark tedit=1 in Both Stock Order & Sowing Schedule">
                                                <i class="fa-solid fa-trash-can me-1"></i> Both
                                            </button>
                                        <?php else: ?>
                                            <!-- Restore Button -->
                                            <button type="button" class="btn btn-sm btn-outline-success btn-action-restore" 
                                                    data-stockorder="<?= $so ?>" title="Restore / Undo tedit=1">
                                                <i class="fa-solid fa-rotate-left me-1"></i> Restore
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Remove / Mark tedit = 1 -->
    <div class="modal fade" id="modalRemoveTedit" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow border-0">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="fa-solid fa-trash-can me-2"></i> Remove Stock Order (Mark tedit = 1)
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small">
                        This action will set <code>tedit = '1'</code>, record the remark, and update status in <b>krf_production_stockorder</b> and <b>krf_polyhouse_sowingschedule</b>.
                        <br><span class="text-primary fw-semibold"><i class="fa-solid fa-shield-halved me-1"></i>Note:</span> Plant detail tables (<code>krf_polyhouse_sowingplant_details</code> and <code>krf_polyhouse_plant_details</code>) are <b>not</b> marked with <code>tedit</code>.
                    </p>

                    <div class="p-3 bg-light rounded border mb-3">
                        <div class="row g-2 small">
                            <div class="col-6">
                                <span class="text-muted">Target Order No:</span>
                                <div class="fw-bold text-dark fs-6" id="modalTargetOrderNo">-</div>
                            </div>
                            <div class="col-6">
                                <span class="text-muted">Target Count:</span>
                                <div class="fw-bold text-dark fs-6" id="modalTargetCount">1 Order</div>
                            </div>
                        </div>
                    </div>

                    <!-- Target Scope Selection -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Target Table(s) <span class="text-danger">*</span></label>
                        <div class="border rounded p-3 bg-light">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="modalRemovalTarget" id="targetScopePssOnly" value="pss_only">
                                <label class="form-check-label fw-bold text-dark" for="targetScopePssOnly">
                                    <i class="fa-regular fa-calendar-xmark text-warning me-1"></i>
                                    Only in Sowing Schedule (<code>krf_polyhouse_sowingschedule</code> ONLY)
                                </label>
                                <div class="text-muted small ps-4">
                                    Sets <code>tedit = '1'</code>, <code>status = 'removed'</code> in <code>krf_polyhouse_sowingschedule</code> with user & timestamp. <code>krf_production_stockorder</code> remains untouched.
                                </div>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modalRemovalTarget" id="targetScopeBoth" value="both" checked>
                                <label class="form-check-label fw-bold text-dark" for="targetScopeBoth">
                                    <i class="fa-solid fa-trash-can text-danger me-1"></i>
                                    Both Tables (<code>krf_production_stockorder</code> & <code>krf_polyhouse_sowingschedule</code>)
                                </label>
                                <div class="text-muted small ps-4">
                                    Sets <code>tedit = '1'</code> in both the stock order and the sowing schedule.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="removeRemarkInput" class="form-label fw-bold">
                            Removal Remark <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="removeRemarkInput" rows="3" 
                                  placeholder="Enter explanation (e.g. Extra showing / not in sowing table / cancelled duplicate)...">Removed: Not in sowing table or mismatched</textarea>
                        <div class="form-text text-muted small">
                            This remark will be written to <code>teditremark</code> in both tables.
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger px-4 fw-bold" id="btnConfirmModalRemove">
                        <i class="fa-solid fa-check me-1"></i> Confirm & Mark tedit = 1
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: View Detailed Breakdown -->
    <div class="modal fade" id="modalOrderDetails" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content shadow border-0">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="fa-solid fa-magnifying-glass me-2"></i> Cross-Table Breakdown: <span id="detailOrderNo" class="text-warning"></span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4" id="modalDetailBody">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <div class="text-muted mt-2">Loading detailed data...</div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    $(document).ready(function() {
        // Initialize DataTable
        const table = $('#auditTable').DataTable({
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            order: [[1, 'desc']],
            dom: '<"d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3"Bf>rt<"d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3"lip>',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: '<i class="fa-solid fa-file-excel me-1 text-success"></i> Excel',
                    className: 'btn btn-sm btn-outline-secondary'
                },
                {
                    extend: 'csvHtml5',
                    text: '<i class="fa-solid fa-file-csv me-1 text-primary"></i> CSV',
                    className: 'btn btn-sm btn-outline-secondary'
                },
                {
                    extend: 'print',
                    text: '<i class="fa-solid fa-print me-1 text-dark"></i> Print',
                    className: 'btn btn-sm btn-outline-secondary'
                }
            ],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search order, product, status..."
            }
        });

        // Card Filter & Issue Filter Logic
        const issueLabelMap = {
            '': 'All Active Issues',
            'Not Sown': 'Not in Sowing Table (No SPD)',
            'No Schedule': 'Missing in Sowing Schedule',
            'Qty Diff': 'Quantity Mismatched',
            'Status Diff': 'Status Mismatched',
            'PSO Qty 0': 'PSO Qty 0 (Ready to Sync)'
        };

        function applyCardFilter(issueKey, cardElem) {
            if (issueKey === 'tedit') {
                window.location.href = '?view=removed';
                return;
            }

            $('.stat-card.card-filter').removeClass('active-filter-card');

            if (!issueKey) {
                // Reset filter to show all
                table.column(10).search('', true, false).draw();
                $('#issueTypeFilter').val('');
                $('[data-issue=""]').addClass('active-filter-card');
                $('#activeFilterBanner').slideUp(150);
            } else {
                table.column(10).search(issueKey, true, false).draw();
                $('#issueTypeFilter').val(issueKey);

                if (cardElem) {
                    $(cardElem).addClass('active-filter-card');
                } else {
                    $(`.stat-card.card-filter[data-issue="${issueKey}"]`).addClass('active-filter-card');
                }

                const label = issueLabelMap[issueKey] || issueKey;
                $('#activeFilterLabel').text(label);
                const info = table.page.info();
                $('#filteredRowCountBadge').text(info.recordsDisplay.toLocaleString() + ' matching rows');
                $('#activeFilterBanner').slideDown(150);
            }
        }

        // Click on KPI Card to filter
        $('.stat-card.card-filter').on('click', function() {
            const issueKey = $(this).data('issue');
            const isAlreadyActive = $(this).hasClass('active-filter-card');

            // Toggle filter off if already active and not the "Total" card
            if (isAlreadyActive && issueKey !== '') {
                applyCardFilter('', null);
            } else {
                applyCardFilter(issueKey, this);
            }
        });

        // Dropdown sync
        $('#issueTypeFilter').on('change', function() {
            const val = $(this).val();
            applyCardFilter(val, null);
        });

        // Clear filter button in banner
        $('#btnClearCardFilter').on('click', function() {
            applyCardFilter('', null);
        });

        // Update filtered count on any table search/draw
        table.on('draw', function() {
            const info = table.page.info();
            $('#filteredRowCountBadge').text(info.recordsDisplay.toLocaleString() + ' matching rows');
        });

        // Checkbox Selection Handling
        function updateSelectedCount() {
            const selected = $('.row-select-checkbox:checked').length;
            const canSyncSelected = $('.row-select-checkbox:checked[data-cansync="1"]').length;
            if (selected > 0) {
                $('#selectedCountBadge').text(selected + ' selected').show();
                $('#btnBulkRemovePss').show();
                $('#btnBulkRemoveBoth').show();
                if (canSyncSelected > 0) {
                    $('#btnBulkSyncPso').html('<i class="fa-solid fa-arrows-rotate me-1"></i> Sync PSO Qty (' + canSyncSelected + ')').show();
                } else {
                    $('#btnBulkSyncPso').hide();
                }
            } else {
                $('#selectedCountBadge').hide();
                $('#btnBulkRemovePss').hide();
                $('#btnBulkRemoveBoth').hide();
                $('#btnBulkSyncPso').hide();
            }
        }

        $('#selectAllCheckbox').on('change', function() {
            const isChecked = $(this).is(':checked');
            $('.row-select-checkbox').prop('checked', isChecked);
            updateSelectedCount();
        });

        $(document).on('change', '.row-select-checkbox', function() {
            updateSelectedCount();
            const total = $('.row-select-checkbox').length;
            const checked = $('.row-select-checkbox:checked').length;
            $('#selectAllCheckbox').prop('checked', total > 0 && total === checked);
        });

        // Context state for single or bulk remove
        let removalMode = 'single';
        let targetStockOrders = [];

        function setModalTargetStyle(scope) {
            if (scope === 'pss_only') {
                $('#targetScopePssOnly').prop('checked', true);
                $('#modalRemoveTedit .modal-header').removeClass('bg-danger').addClass('bg-warning text-dark');
                $('#modalRemoveTedit .modal-title').html('<i class="fa-regular fa-calendar-xmark me-2"></i> Remove Schedule Only (Mark tedit = 1 in PSS)');
                $('#btnConfirmModalRemove').removeClass('btn-danger').addClass('btn-warning text-dark').html('<i class="fa-solid fa-check me-1"></i> Confirm & Mark Schedule as tedit = 1');
            } else {
                $('#targetScopeBoth').prop('checked', true);
                $('#modalRemoveTedit .modal-header').removeClass('bg-warning text-dark').addClass('bg-danger text-white');
                $('#modalRemoveTedit .modal-title').html('<i class="fa-solid fa-trash-can me-2"></i> Remove Both (Stock Order & Schedule)');
                $('#btnConfirmModalRemove').removeClass('btn-warning text-dark').addClass('btn-danger').html('<i class="fa-solid fa-check me-1"></i> Confirm & Mark Both as tedit = 1');
            }
        }

        $('input[name="modalRemovalTarget"]').on('change', function() {
            setModalTargetStyle($(this).val());
        });

        // Single Remove PSS Only click
        $(document).on('click', '.btn-action-remove-pss', function() {
            removalMode = 'single';
            const so = $(this).data('stockorder');
            targetStockOrders = [so];
            $('#modalTargetOrderNo').text(so);
            $('#modalTargetCount').text('1 Order');
            setModalTargetStyle('pss_only');
            $('#removeRemarkInput').val('Removed from schedule: Not in sowing table or mismatched');
            $('#modalRemoveTedit').modal('show');
        });

        // Single Remove Both click
        $(document).on('click', '.btn-action-remove', function() {
            removalMode = 'single';
            const so = $(this).data('stockorder');
            targetStockOrders = [so];
            $('#modalTargetOrderNo').text(so);
            $('#modalTargetCount').text('1 Order');
            setModalTargetStyle('both');
            $('#removeRemarkInput').val('Removed: Not in sowing table or mismatched');
            $('#modalRemoveTedit').modal('show');
        });

        // Bulk Remove PSS Only click
        $('#btnBulkRemovePss').on('click', function() {
            removalMode = 'bulk';
            targetStockOrders = [];
            $('.row-select-checkbox:checked').each(function() {
                targetStockOrders.push($(this).val());
            });

            if (targetStockOrders.length === 0) {
                Swal.fire({icon: 'warning', title: 'No Selection', text: 'Please select at least one stock order.'});
                return;
            }

            $('#modalTargetOrderNo').text(targetStockOrders.slice(0, 3).join(', ') + (targetStockOrders.length > 3 ? '...' : ''));
            $('#modalTargetCount').text(targetStockOrders.length + ' Orders Selected');
            setModalTargetStyle('pss_only');
            $('#removeRemarkInput').val('Bulk removed from schedule: Not in sowing table or mismatched');
            $('#modalRemoveTedit').modal('show');
        });

        // Bulk Remove Both click
        $('#btnBulkRemoveBoth').on('click', function() {
            removalMode = 'bulk';
            targetStockOrders = [];
            $('.row-select-checkbox:checked').each(function() {
                targetStockOrders.push($(this).val());
            });

            if (targetStockOrders.length === 0) {
                Swal.fire({icon: 'warning', title: 'No Selection', text: 'Please select at least one stock order.'});
                return;
            }

            $('#modalTargetOrderNo').text(targetStockOrders.slice(0, 3).join(', ') + (targetStockOrders.length > 3 ? '...' : ''));
            $('#modalTargetCount').text(targetStockOrders.length + ' Orders Selected');
            setModalTargetStyle('both');
            $('#removeRemarkInput').val('Bulk removed: Not in sowing table or mismatched');
            $('#modalRemoveTedit').modal('show');
        });

        // Confirm Remove Button in Modal
        $('#btnConfirmModalRemove').on('click', function() {
            const remark = $('#removeRemarkInput').val().trim();
            if (!remark) {
                Swal.fire({icon: 'error', title: 'Remark Required', text: 'Please enter a removal remark.'});
                return;
            }

            const targetScope = $('input[name="modalRemovalTarget"]:checked').val() || 'both';
            const btn = $(this);
            btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Processing...');

            const postData = {
                action: (removalMode === 'bulk') ? 'bulk_mark_tedit' : 'mark_tedit',
                target: targetScope,
                remark: remark
            };

            if (removalMode === 'bulk') {
                postData.stockorders = targetStockOrders;
            } else {
                postData.stockorder_no = targetStockOrders[0];
            }

            $.ajax({
                url: 'extrshowing.php',
                type: 'POST',
                data: postData,
                dataType: 'json',
                success: function(resp) {
                    btn.prop('disabled', false).html('<i class="fa-solid fa-check me-1"></i> Confirm & Mark tedit = 1');
                    $('#modalRemoveTedit').modal('hide');

                    if (resp.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: resp.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({icon: 'error', title: 'Error', text: resp.message || 'Operation failed.'});
                    }
                },
                error: function(xhr, status, error) {
                    btn.prop('disabled', false).html('<i class="fa-solid fa-check me-1"></i> Confirm & Mark tedit = 1');
                    Swal.fire({icon: 'error', title: 'Server Error', text: error || 'Failed to process request.'});
                }
            });
        });

        // -------------------------------------------------------------
        // Single Sync PSO Quantity as per Schedule (Scion if Grafted)
        // -------------------------------------------------------------
        $(document).on('click', '.btn-action-sync-pso', function(e) {
            e.stopPropagation();
            const so = $(this).data('stockorder');
            const syncQty = parseInt($(this).data('syncqty'), 10) || 0;
            const isGrafted = $(this).data('isgrafted') == '1';

            let msg = `Sync PSO Quantity for <b>${so}</b> to <b>${syncQty.toLocaleString()}</b> as per Sowing Schedule?`;
            if (isGrafted) {
                msg += `<div class="alert alert-info py-2 px-3 mt-2 text-start small mb-0"><i class="fa-solid fa-code-branch me-1"></i><b>Grafted Product:</b> Syncing using <b>Scion Order Quantity</b> (ignoring Root qty).</div>`;
            }
            msg += `<div class="text-muted small mt-2">This will update or create the record in <code>krf_production_stockorder</code> with status <b>Confirmed</b> and clear tedit.</div>`;

            Swal.fire({
                title: 'Sync Production Stock Order?',
                html: msg,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#16a34a',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fa-solid fa-arrows-rotate me-1"></i> Yes, Sync Qty',
                cancelButtonText: 'Cancel'
            }).then((res) => {
                if (res.isConfirmed) {
                    Swal.fire({
                        title: 'Syncing Quantity...',
                        text: 'Please wait...',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });

                    $.ajax({
                        url: 'extrshowing.php',
                        type: 'POST',
                        data: { action: 'sync_pso_qty', stockorder_no: so },
                        dataType: 'json',
                        success: function(resp) {
                            if (resp.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Synced Successfully!',
                                    text: resp.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire({icon: 'error', title: 'Sync Failed', text: resp.message});
                            }
                        },
                        error: function(xhr, status, error) {
                            Swal.fire({icon: 'error', title: 'Server Error', text: error || 'Failed to sync quantity.'});
                        }
                    });
                }
            });
        });

        // -------------------------------------------------------------
        // Bulk Sync PSO Quantities as per Schedule
        // -------------------------------------------------------------
        $('#btnBulkSyncPso').on('click', function() {
            const syncStockOrders = [];
            $('.row-select-checkbox:checked[data-cansync="1"]').each(function() {
                syncStockOrders.push($(this).val());
            });

            if (syncStockOrders.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Eligible Orders',
                    text: 'None of the selected orders have PSO Qty 0 with available schedule quantity.'
                });
                return;
            }

            Swal.fire({
                title: 'Bulk Sync PSO Quantities?',
                html: `Sync PSO quantities for <b>${syncStockOrders.length}</b> selected orders as per Sowing Schedule?<br><br><span class="text-success small fw-semibold"><i class="fa-solid fa-code-branch me-1"></i>For grafted products, Scion quantity will be used automatically.</span><br><div class="text-muted small mt-2">Target Orders: ${syncStockOrders.slice(0, 5).join(', ')}${syncStockOrders.length > 5 ? '...' : ''}</div>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#16a34a',
                cancelButtonColor: '#6c757d',
                confirmButtonText: `<i class="fa-solid fa-arrows-rotate me-1"></i> Yes, Sync All (${syncStockOrders.length})`,
                cancelButtonText: 'Cancel'
            }).then((res) => {
                if (res.isConfirmed) {
                    Swal.fire({
                        title: 'Syncing in Bulk...',
                        text: 'Please wait...',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });

                    $.ajax({
                        url: 'extrshowing.php',
                        type: 'POST',
                        data: { action: 'bulk_sync_pso_qty', stockorders: syncStockOrders },
                        dataType: 'json',
                        success: function(resp) {
                            if (resp.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Bulk Sync Complete!',
                                    text: resp.message,
                                    timer: 2500,
                                    showConfirmButton: false
                                }).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire({icon: 'error', title: 'Sync Failed', text: resp.message});
                            }
                        },
                        error: function(xhr, status, error) {
                            Swal.fire({icon: 'error', title: 'Server Error', text: error || 'Failed to bulk sync.'});
                        }
                    });
                }
            });
        });

        // Restore / Undo Single Record
        $(document).on('click', '.btn-action-restore', function() {
            const so = $(this).data('stockorder');
            Swal.fire({
                title: 'Restore Stock Order?',
                text: `Are you sure you want to restore ${so}? This will set tedit = NULL in both tables.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#16a34a',
                confirmButtonText: 'Yes, Restore'
            }).then((res) => {
                if (res.isConfirmed) {
                    $.ajax({
                        url: 'extrshowing.php',
                        type: 'POST',
                        data: { action: 'restore_tedit', stockorder_no: so },
                        dataType: 'json',
                        success: function(resp) {
                            if (resp.success) {
                                Swal.fire({icon: 'success', title: 'Restored', text: resp.message, timer: 1500, showConfirmButton: false})
                                    .then(() => window.location.reload());
                            } else {
                                Swal.fire({icon: 'error', title: 'Error', text: resp.message});
                            }
                        },
                        error: function() {
                            Swal.fire({icon: 'error', title: 'Server Error', text: 'Could not restore record.'});
                        }
                    });
                }
            });
        });

        // View Detailed Breakdown Modal
        $(document).on('click', '.btn-view-details', function() {
            const so = $(this).data('stockorder');
            $('#detailOrderNo').text(so);
            $('#modalDetailBody').html(`
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="text-muted mt-2">Loading detailed data...</div>
                </div>
            `);
            $('#modalOrderDetails').modal('show');

            $.ajax({
                url: 'extrshowing.php',
                type: 'POST',
                data: { action: 'get_order_details', stockorder_no: so },
                dataType: 'json',
                success: function(resp) {
                    if (!resp.success) {
                        $('#modalDetailBody').html(`<div class="alert alert-danger">${resp.message}</div>`);
                        return;
                    }

                    let html = '';

                    // 1. Production Stock Order
                    html += `
                        <div class="card mb-3 border-0 bg-light">
                            <div class="card-header bg-primary text-white py-2 fw-bold d-flex justify-content-between align-items-center">
                                <span><i class="fa-solid fa-file-invoice me-2"></i>1. krf_production_stockorder (PSO)</span>
                                <span class="badge bg-light text-dark">${resp.pso.length} Row(s)</span>
                            </div>
                            <div class="card-body p-3">
                    `;
                    if (resp.pso.length > 0) {
                        html += `
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered bg-white mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>PSONO</th>
                                            <th>Product Name</th>
                                            <th>PID</th>
                                            <th>Quantity</th>
                                            <th>Status</th>
                                            <th>tedit</th>
                                            <th>teditremark</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;
                        resp.pso.forEach(r => {
                            html += `
                                <tr>
                                    <td>${r.id}</td>
                                    <td>${r.psono || '—'}</td>
                                    <td>${r.product_name || '—'}</td>
                                    <td>${r.productid || '—'}</td>
                                    <td class="fw-bold">${Number(r.quantity).toLocaleString()}</td>
                                    <td><span class="badge bg-secondary">${r.status || '—'}</span></td>
                                    <td>${r.tedit || '<span class="text-muted">NULL</span>'}</td>
                                    <td class="small text-muted">${r.teditremark || '—'}</td>
                                </tr>
                            `;
                        });
                        html += `</tbody></table></div>`;
                    } else {
                        html += `<div class="text-danger fw-semibold"><i class="fa fa-times-circle me-1"></i>No matching record in krf_production_stockorder.</div>`;
                    }
                    html += `</div></div>`;

                    // 2. Sowing Schedule
                    html += `
                        <div class="card mb-3 border-0 bg-light">
                            <div class="card-header bg-warning text-dark py-2 fw-bold d-flex justify-content-between align-items-center">
                                <span><i class="fa-regular fa-calendar-check me-2"></i>2. krf_polyhouse_sowingschedule (PSS)</span>
                                <span class="badge bg-dark text-white">${resp.pss.length} Row(s)</span>
                            </div>
                            <div class="card-body p-3">
                    `;
                    if (resp.pss.length > 0) {
                        html += `
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered bg-white mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Schedule ID</th>
                                            <th>Product Name</th>
                                            <th>Root</th>
                                            <th>Order Qty</th>
                                            <th>Sowing Qty</th>
                                            <th>Status</th>
                                            <th>Sowing Date</th>
                                            <th>tedit</th>
                                            <th>teditremark</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;
                        resp.pss.forEach(r => {
                            html += `
                                <tr>
                                    <td>${r.sowingschedulid}</td>
                                    <td>${r.productname || '—'}</td>
                                    <td><span class="badge bg-light text-dark border">${r.roottype || '—'}</span></td>
                                    <td class="fw-bold">${Number(r.orderqty).toLocaleString()}</td>
                                    <td class="fw-bold text-primary">${Number(r.sowingqty).toLocaleString()}</td>
                                    <td><span class="badge bg-secondary">${r.status || '—'}</span></td>
                                    <td>${r.sowing_date || '—'}</td>
                                    <td>${r.tedit || '<span class="text-muted">NULL</span>'}</td>
                                    <td class="small text-muted">${r.teditremark || '—'}</td>
                                </tr>
                            `;
                        });
                        html += `</tbody></table></div>`;
                    } else {
                        html += `<div class="text-danger fw-semibold"><i class="fa fa-times-circle me-1"></i>No matching record in krf_polyhouse_sowingschedule.</div>`;
                    }
                    html += `</div></div>`;

                    // 3. Sowing Plant Details
                    html += `
                        <div class="card border-0 bg-light mb-3">
                            <div class="card-header bg-success text-white py-2 fw-bold d-flex justify-content-between align-items-center">
                                <span><i class="fa-solid fa-seedling me-2"></i>3. krf_polyhouse_sowingplant_details (SPD)</span>
                                <span class="badge bg-light text-dark">${resp.spd ? Number(resp.spd.tray_count).toLocaleString() + ' Trays' : '0'}</span>
                            </div>
                            <div class="card-body p-3">
                    `;
                    if (resp.spd && Number(resp.spd.tray_count) > 0) {
                        html += `
                            <div class="row g-2 small">
                                <div class="col-md-3">
                                    <span class="text-muted">Total Trays Sown:</span>
                                    <div class="fw-bold fs-6 text-dark">${Number(resp.spd.tray_count).toLocaleString()} Trays</div>
                                </div>
                                <div class="col-md-3">
                                    <span class="text-muted">Total Sown Plants:</span>
                                    <div class="fw-bold fs-6 text-success">${Number(resp.spd.plant_count || resp.spd.totalsowingqty || 0).toLocaleString()} Plants</div>
                                </div>
                                <div class="col-md-3">
                                    <span class="text-muted">Order Qty in SPD:</span>
                                    <div class="fw-bold fs-6 text-primary">${Number(resp.spd.orderqty).toLocaleString()}</div>
                                </div>
                                <div class="col-md-3">
                                    <span class="text-muted">Sowing Date Range:</span>
                                    <div class="fw-semibold text-dark">${resp.spd.min_sowing_date || '—'} to ${resp.spd.max_sowing_date || '—'}</div>
                                </div>
                                <div class="col-md-6 mt-2">
                                    <span class="text-muted">Tray Statuses:</span>
                                    <div class="fw-semibold text-dark">${resp.spd.statuses || '—'}</div>
                                </div>
                                <div class="col-md-6 mt-2">
                                    <span class="text-muted">Tray Working Statuses:</span>
                                    <div class="fw-semibold text-dark">${resp.spd.workingstatuses || '—'}</div>
                                </div>
                            </div>
                        `;
                    } else {
                        html += `
                            <div class="alert alert-warning mb-0 d-flex align-items-center gap-2">
                                <i class="fa-solid fa-triangle-exclamation fa-lg"></i>
                                <div>
                                    <b>Not Found in Sowing Details!</b><br>
                                    No trays or plants have been entered into <code>krf_polyhouse_sowingplant_details</code> for stock order <b>${so}</b>.
                                </div>
                            </div>
                        `;
                    }
                    html += `</div></div>`;

                    // 4. Polyhouse Plant Details
                    html += `
                        <div class="card border-0 bg-light">
                            <div class="card-header bg-info text-white py-2 fw-bold d-flex justify-content-between align-items-center" style="background-color: #0284c7 !important;">
                                <span><i class="fa-solid fa-warehouse me-2"></i>4. krf_polyhouse_plant_details (Polyhouse Plants)</span>
                                <span class="badge bg-light text-dark">${resp.ph ? Number(resp.ph.tray_count).toLocaleString() + ' Trays' : '0'}</span>
                            </div>
                            <div class="card-body p-3">
                    `;
                    if (resp.ph && Number(resp.ph.tray_count) > 0) {
                        html += `
                            <div class="row g-2 small">
                                <div class="col-md-3">
                                    <span class="text-muted">Total Polyhouse Trays:</span>
                                    <div class="fw-bold fs-6 text-dark">${Number(resp.ph.tray_count).toLocaleString()} Trays</div>
                                </div>
                                <div class="col-md-3">
                                    <span class="text-muted">Total Net Plants:</span>
                                    <div class="fw-bold fs-6 text-primary">${Number(resp.ph.plant_count).toLocaleString()} Plants</div>
                                </div>
                                <div class="col-md-3">
                                    <span class="text-muted">Planted Date Range:</span>
                                    <div class="fw-semibold text-dark">${resp.ph.min_planted_date || '—'} to ${resp.ph.max_planted_date || '—'}</div>
                                </div>
                                <div class="col-md-3">
                                    <span class="text-muted">Growth Stages:</span>
                                    <div class="fw-semibold text-dark">${resp.ph.growth_stages || '—'}</div>
                                </div>
                                <div class="col-md-6 mt-2">
                                    <span class="text-muted">Statuses:</span>
                                    <div class="fw-semibold text-dark">${resp.ph.statuses || '—'}</div>
                                </div>
                                <div class="col-md-6 mt-2">
                                    <span class="text-muted">Working Statuses:</span>
                                    <div class="fw-semibold text-dark">${resp.ph.workingstatuses || '—'}</div>
                                </div>
                            </div>
                        `;
                    } else {
                        html += `
                            <div class="alert alert-secondary mb-0 d-flex align-items-center gap-2">
                                <i class="fa-solid fa-info-circle fa-lg"></i>
                                <div>
                                    No physical trays or plants currently recorded in <code>krf_polyhouse_plant_details</code> for stock order <b>${so}</b>.
                                </div>
                            </div>
                        `;
                    }
                    html += `</div></div>`;

                    $('#modalDetailBody').html(html);
                },
                error: function() {
                    $('#modalDetailBody').html(`<div class="alert alert-danger">Failed to load details.</div>`);
                }
            });
        });
    });
    </script>
</body>
</html>

