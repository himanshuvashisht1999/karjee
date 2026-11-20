<?php
error_reporting(E_ALL);
require_once(__DIR__ . '/../../connection.php');
if (isset($krfconn) && $krfconn) {
    @$krfconn->set_charset("utf8mb4");
}
require_once(__DIR__ . '/../../loginCell/functions/login_functions.php');
@include_once(__DIR__ . "/../../whatsappnotification/whatsappsmsfunctions.php");
@include_once(__DIR__ . "/../../functions/orders_functions.php");
@include_once(__DIR__ . "/../../functions/product_functions.php");
@include_once(__DIR__ . "/../../functions/seed_functions.php");
date_default_timezone_set("Asia/Calcutta");

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action == "GetActivePostDispatchDates") {
    $projectid = intval($_POST['projectid'] ?? 0);
    
    // Fetch unique delivery/dispatch dates from final dispatched orders
    $sql = "
        SELECT DISTINCT soi.deliverydate AS dispatch_date
        FROM krf_sales_order_items soi
        JOIN krf_sales_order so ON soi.sono = so.sono
        WHERE (soi.projectid = ? OR so.projectid = ?)
          AND soi.deliverydate IS NOT NULL 
          AND soi.deliverydate != '0000-00-00'
        UNION
        SELECT DISTINCT pso.expected_delivery_date AS dispatch_date
        FROM krf_production_stockorder pso
        WHERE (pso.projectid = ? OR ? = 0)
          AND pso.expected_delivery_date IS NOT NULL 
          AND pso.expected_delivery_date != '0000-00-00'
        ORDER BY dispatch_date ASC
    ";
    
    $stmt = $krfconn->prepare($sql);
    $stmt->bind_param("iiii", $projectid, $projectid, $projectid, $projectid);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $dates = [];
    while ($row = $res->fetch_assoc()) {
        if (!empty($row['dispatch_date'])) {
            $dates[] = ["dispatch_date" => $row['dispatch_date']];
        }
    }
    echo json_encode($dates);
    exit;

} else if ($action == "ConfirmEstimateOpenStock") {
    $consumedId       = intval($_POST['consumed_id'] ?? 0);
    $estimateOpenStock = intval($_POST['estimate_open_stock'] ?? 0);
    $projectid        = intval($_POST['projectid'] ?? 0);

    if ($consumedId <= 0) {
        echo json_encode(["status" => "error", "message" => "Invalid record ID."]);
        exit;
    }

    // Verify the record belongs to this project
    $checkSql = "SELECT id, allocate_healthyplant, dispatchedqty FROM krf_productionorder_consumed WHERE id = ?";
    $checkStmt = $krfconn->prepare($checkSql);
    $checkStmt->bind_param("i", $consumedId);
    $checkStmt->execute();
    $checkRes = $checkStmt->get_result();
    $pocRow = $checkRes->fetch_assoc();
    $checkStmt->close();

    if (!$pocRow) {
        echo json_encode(["status" => "error", "message" => "Record not found."]);
        exit;
    }

    // If client supplied a valid estimate_open_stock, use it, else calculate from allocate - dispatch
    if (isset($_POST['estimate_open_stock']) && is_numeric($_POST['estimate_open_stock'])) {
        $safeEstimate = max(0, intval($_POST['estimate_open_stock']));
    } else {
        $safeEstimate = max(0, intval($pocRow['allocate_healthyplant']) - intval($pocRow['dispatchedqty']));
    }

    $updateSql = "UPDATE krf_productionorder_consumed SET estimate_open_stock = ?, updated_at = NOW() WHERE id = ?";
    $updateStmt = $krfconn->prepare($updateSql);
    $updateStmt->bind_param("ii", $safeEstimate, $consumedId);

    if ($updateStmt->execute()) {
        echo json_encode([
            "status"             => "success",
            "message"            => "Est. Open Stock updated and saved.",
            "consumed_id"        => $consumedId,
            "estimate_open_stock" => $safeEstimate
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "DB update failed: " . $krfconn->error]);
    }
    $updateStmt->close();
    exit;

} else if ($action == "GetPostDispatchSurplusStockData") {
    $projectid = intval($_POST['projectid'] ?? 0);
    $dateFrom = trim($_POST['datefrom'] ?? '');
    $dateTo = trim($_POST['dateto'] ?? '');

    $dateFilter = "AND soi.deliverydate BETWEEN ? AND ?";

    // 1. Fetch Sales Orders, Items, Consumed, and Stock Order details
    $sql = "
        SELECT 
            soi.id AS soi_id,
            soi.sono,
            soi.psono,
            soi.product_id,
            soi.product_name,
            soi.quantity AS order_qty,
            soi.deliverydate AS delivery_date,
            IFNULL(soi.reservedqty, 0) AS reserved_qty,
            IFNULL(soi.balancereservedqty, IFNULL(soi.quantity, 0) - IFNULL(soi.reservedqty, 0)) AS balance_reserved_qty,
            IFNULL(soi.status, 'Pending') AS item_status,
            soi.stockorder_no AS soi_stockorderno,
            IFNULL(soi.dispatchedqty, 0) AS soi_dispatchedqty,
            
            so.clientid,
            so.clientname,
            so.mobileno,
            so.statename,
            so.districtname,
            so.delivery_address,
            IFNULL(so.status, 'Pending') AS sales_order_status,
            so.created_at AS sales_order_created_at,
            so.created_by AS so_created_by,
            
            poc.id AS consumed_id,
            poc.stockorderno AS consumed_stockorderno,
            IFNULL(poc.consumed_qty, 0) AS consumed_qty,
            poc.dispatchedqty AS poc_dispatchedqty,
            IFNULL(poc.confirmstatus, 'Pending') AS consumed_confirmstatus,
            poc.status AS consumed_status,
            poc.allocatedat AS allocated_at,
            poc.allocatedby AS allocated_by_id,
            poc.dispatchedat AS dispatched_at,
            poc.dispatchedby AS dispatched_by_id,
            poc.remark AS consumed_remark,
            IFNULL(poc.allocate_healthyplant, 0) AS allocated_qty,
            IFNULL(poc.allocate_unhealthyplant, 0) AS allocate_unhealthyplant,
            IFNULL(poc.estimate_open_stock, 0) AS poc_estimate_open_stock,
            
            pso.id AS pso_id,
            COALESCE(poc.stockorderno, soi.stockorder_no, pso.stockorder_no, 'Not Allocated') AS effective_stockorderno,
            pso.quantity AS pso_total_qty,
            pso.expected_delivery_date AS pso_expected_delivery_date,
            pso.sowingdate AS pso_sowingdate,
            pso.status AS pso_status,
            pso.workingstatus AS pso_workingstatus,
            pso.dispatchqty AS pso_dispatchqty

        FROM krf_sales_order_items soi
        JOIN krf_sales_order so ON soi.sono = so.sono
        LEFT JOIN krf_productionorder_consumed poc ON (
            ((soi.psono = poc.psono AND soi.psono != '') OR (soi.sono = poc.sono AND soi.sono != ''))
            AND (soi.product_id = poc.product_id OR soi.product_id = CAST(poc.product_id AS CHAR))
        )
        LEFT JOIN krf_production_stockorder pso ON (COALESCE(poc.stockorderno, soi.stockorder_no) = pso.stockorder_no)
        WHERE (soi.projectid = ? OR so.projectid = ?)
          AND soi.deliverydate IS NOT NULL 
          AND soi.deliverydate != '0000-00-00'
          {$dateFilter}
        ORDER BY soi.deliverydate ASC, so.id DESC
    ";

    $stmt = $krfconn->prepare($sql);
    $stmt->bind_param("iiss", $projectid, $projectid, $dateFrom, $dateTo);
    $stmt->execute();
    $res = $stmt->get_result();

    $rawRows = [];
    $stockOrderNos = [];

    while ($row = $res->fetch_assoc()) {
        $effStk = trim($row['effective_stockorderno'] ?? '');
        if (!empty($effStk) && $effStk !== 'Not Allocated') {
            $stockOrderNos[] = $effStk;
        }
        $rawRows[] = $row;
    }

    // 2. Batch Polyhouse Plants Summary for all active stock orders
    $plantMap = [];
    $stockOrderNos = array_values(array_unique($stockOrderNos));
    if (!empty($stockOrderNos)) {
        $inPlaceholders = implode(',', array_fill(0, count($stockOrderNos), '?'));
        $plantSql = "
            SELECT 
                ppd.stockorder_no,
                COUNT(ppd.polyhouseplantid) AS polyhouse_tray_count,
                SUM(GREATEST(0, CAST(IFNULL(ppd.qtypertray, 0) AS SIGNED))) AS total_sowing_plants_in_trays,
                SUM(CASE 
                    WHEN LOWER(IFNULL(ppd.health_status, '')) IN ('healthy','unhealthy') 
                         AND LOWER(IFNULL(ppd.status, '')) IN ('openstock', 'polyhouse', 'readytosale', 'hardening') 
                    THEN GREATEST(0, CAST(IFNULL(ppd.qtypertray, 0) AS SIGNED) - CAST(IFNULL(ppd.failurecount, 0) AS SIGNED)) 
                    ELSE 0 
                END) AS polyhouse_plant_qty,
                SUM(CASE 
                    WHEN LOWER(IFNULL(ppd.health_status, '')) IN ('healthy','unhealthy') 
                         AND LOWER(IFNULL(ppd.status, '')) IN ('readytosale', 'openstock') 
                    THEN GREATEST(0, CAST(IFNULL(ppd.qtypertray, 0) AS SIGNED) - CAST(IFNULL(ppd.failurecount, 0) AS SIGNED)) 
                    ELSE 0 
                END) AS ready_plant_qty,
                COUNT(CASE 
                    WHEN LOWER(IFNULL(ppd.status, '')) IN ('readytosale', 'openstock') OR LOWER(IFNULL(ppd.workingstatus, '')) IN ('readytosale', 'openstock')
                    THEN 1 
                    ELSE NULL 
                END) AS ready_trays_count,
                SUM(CASE 
                    WHEN (LOWER(IFNULL(ppd.status, '')) = 'final dispatch' OR LOWER(IFNULL(ppd.workingstatus, '')) = 'final dispatch') 
                    THEN GREATEST(0, CAST(IFNULL(ppd.qtypertray, 0) AS SIGNED) - CAST(IFNULL(ppd.failurecount, 0) AS SIGNED)) 
                    ELSE 0 
                END) AS dispatched_plant_qty,
                COUNT(CASE 
                    WHEN (LOWER(IFNULL(ppd.status, '')) = 'final dispatch' OR LOWER(IFNULL(ppd.workingstatus, '')) = 'final dispatch')
                    THEN 1 
                    ELSE NULL 
                END) AS dispatched_trays_count,
                SUM(CASE 
                    WHEN (LOWER(IFNULL(ppd.status, '')) = 'empty after shifting' OR LOWER(IFNULL(ppd.workingstatus, '')) = 'empty after shifting')
                    THEN GREATEST(0, CAST(IFNULL(ppd.qtypertray, 0) AS SIGNED) - CAST(IFNULL(ppd.failurecount, 0) AS SIGNED)) 
                    ELSE 0 
                END) AS empty_shifting_plant_qty,
                COUNT(CASE 
                    WHEN (LOWER(IFNULL(ppd.status, '')) = 'empty after shifting' OR LOWER(IFNULL(ppd.workingstatus, '')) = 'empty after shifting')
                    THEN 1 
                    ELSE NULL 
                END) AS empty_shifting_trays_count,
                COUNT(CASE 
                    WHEN LOWER(IFNULL(ppd.status, '')) IN ('openstock', 'polyhouse', 'readytosale', 'hardening') 
                    THEN 1 
                    ELSE NULL 
                END) AS remaining_trays_count,
                GROUP_CONCAT(DISTINCT ph.polyhousename SEPARATOR ', ') AS polyhouse_names,
                GROUP_CONCAT(DISTINCT NULLIF(TRIM(ppd.bedrackname), '') SEPARATOR ', ') AS bed_numbers
            FROM krf_polyhouse_plant_details ppd
            LEFT JOIN krf_polyhouse_master ph ON ppd.polyhouseid = ph.polyhouseid
            WHERE ppd.stockorder_no IN ($inPlaceholders)
            GROUP BY ppd.stockorder_no
        ";

        $types = str_repeat('s', count($stockOrderNos));
        $pStmt = $krfconn->prepare($plantSql);
        if ($pStmt) {
            $pStmt->bind_param($types, ...$stockOrderNos);
            $pStmt->execute();
            $pRes = $pStmt->get_result();
            while ($pRow = $pRes->fetch_assoc()) {
                $plantMap[$pRow['stockorder_no']] = $pRow;
            }
        }

        // 2.2 Sowing Details Summary from krf_polyhouse_sowingplant_details
        $sowSql = "
            SELECT 
                stockorder_no,
                psono,
                MAX(CAST(orderqty AS UNSIGNED)) AS sp_orderqty,
                MAX(CAST(totalsowingqty AS UNSIGNED)) AS sp_totalsowingqty,
                MIN(COALESCE(sowingdate_internal, sowing_date)) AS sp_sowingdate
            FROM krf_polyhouse_sowingplant_details
            WHERE stockorder_no IN ($inPlaceholders)
            GROUP BY stockorder_no
        ";
        $sStmt = $krfconn->prepare($sowSql);
        if ($sStmt) {
            $sStmt->bind_param($types, ...$stockOrderNos);
            $sStmt->execute();
            $sRes = $sStmt->get_result();
            while ($sRow = $sRes->fetch_assoc()) {
                $sowingMap[$sRow['stockorder_no']] = $sRow;
                if (!empty($sRow['psono'])) {
                    $sowingMap[$sRow['psono']] = $sRow;
                }
            }
            $sStmt->close();
        }
    }

    // 2.3 Query Dispatched Plants from sales_invoice_items strictly as per productid
    $allSoiIds = [];
    $allPsonos = [];
    $allSonos = [];
    foreach ($rawRows as $r) {
        if (!empty($r['soi_id'])) $allSoiIds[] = intval($r['soi_id']);
        if (!empty($r['psono'])) $allPsonos[] = trim($r['psono']);
        if (!empty($r['sono'])) $allSonos[] = trim($r['sono']);
    }
    $allSoiIds = array_values(array_unique(array_filter($allSoiIds)));
    $allPsonos = array_values(array_unique(array_filter($allPsonos)));
    $allSonos = array_values(array_unique(array_filter($allSonos)));

    $siiDispatchedBySoiId = [];
    $siiDispatchedByPsonoProduct = [];
    $siiDispatchedBySonoProduct = [];

    $siiWhere = [];
    $siiParams = [];
    $siiTypes = "";

    if (!empty($allSoiIds)) {
        $inSoi = implode(',', array_fill(0, count($allSoiIds), '?'));
        $siiWhere[] = "sii.original_soi_id IN ($inSoi)";
        $siiTypes .= str_repeat('i', count($allSoiIds));
        foreach ($allSoiIds as $id) $siiParams[] = $id;
    }
    if (!empty($allPsonos)) {
        $inPso = implode(',', array_fill(0, count($allPsonos), '?'));
        $siiWhere[] = "sii.psono IN ($inPso)";
        $siiTypes .= str_repeat('s', count($allPsonos));
        foreach ($allPsonos as $p) $siiParams[] = $p;
    }
    if (!empty($allSonos)) {
        $inSo = implode(',', array_fill(0, count($allSonos), '?'));
        $siiWhere[] = "sii.sono IN ($inSo)";
        $siiTypes .= str_repeat('s', count($allSonos));
        foreach ($allSonos as $s) $siiParams[] = $s;
    }

    if (!empty($siiWhere)) {
        $siiSql = "
            SELECT 
                sii.original_soi_id,
                sii.psono,
                sii.sono,
                sii.productid,
                SUM(CASE WHEN IFNULL(sii.total_dispatch_plants, 0) > 0 THEN sii.total_dispatch_plants ELSE sii.quantity END) AS total_dispatched_plants,
                SUM(sii.quantity) AS total_invoice_qty
            FROM sales_invoice_items sii
            JOIN sales_invoice si ON (si.id = sii.invoice_id OR si.invoiceno = sii.invoiceno)
            WHERE (" . implode(' OR ', $siiWhere) . ")
              AND sii.productid != 'BOX_PACKING'
              AND sii.is_box_packing = 0
              AND LOWER(IFNULL(sii.status, '')) != 'cancelled'
              AND LOWER(IFNULL(si.status, '')) != 'cancelled'
            GROUP BY sii.original_soi_id, sii.psono, sii.sono, sii.productid
        ";
        $siiStmt = $krfconn->prepare($siiSql);
        if ($siiStmt) {
            $siiStmt->bind_param($siiTypes, ...$siiParams);
            $siiStmt->execute();
            $siiRes = $siiStmt->get_result();
            while ($siiRow = $siiRes->fetch_assoc()) {
                $qty = intval($siiRow['total_dispatched_plants'] ?? $siiRow['total_invoice_qty'] ?? 0);
                $origId = intval($siiRow['original_soi_id'] ?? 0);
                $pso = trim($siiRow['psono'] ?? '');
                $so = trim($siiRow['sono'] ?? '');
                $prod = trim(strval($siiRow['productid'] ?? ''));

                if ($origId > 0) {
                    $siiDispatchedBySoiId[$origId] = ($siiDispatchedBySoiId[$origId] ?? 0) + $qty;
                }
                if (!empty($pso) && !empty($prod)) {
                    $k = $pso . '___' . $prod;
                    $siiDispatchedByPsonoProduct[$k] = ($siiDispatchedByPsonoProduct[$k] ?? 0) + $qty;
                }
                if (!empty($so) && !empty($prod)) {
                    $kSo = $so . '___' . $prod;
                    $siiDispatchedBySonoProduct[$kSo] = ($siiDispatchedBySonoProduct[$kSo] ?? 0) + $qty;
                }
            }
            $siiStmt->close();
        }
    }

    // 3. Process records & filter ONLY final dispatched orders with surplus plants remaining in polyhouse
    $surplusList = [];
    $totalSowingAgg = 0;
    $totalDispatchedAgg = 0;
    $totalSurplusAgg = 0;

    foreach ($rawRows as $row) {
        $soiId = intval($row['soi_id'] ?? 0);
        $prodId = trim(strval($row['product_id'] ?? ''));
        $stk = trim($row['effective_stockorderno'] ?? '');
        $psono = trim($row['psono'] ?? '');
        $sono = trim($row['sono'] ?? '');

        // Resolve Dispatched Plants STRICTLY as per productid from sales_invoice_items
        $siiDisp = 0;
        if ($soiId > 0 && isset($siiDispatchedBySoiId[$soiId])) {
            $siiDisp = $siiDispatchedBySoiId[$soiId];
        } elseif (!empty($psono) && !empty($prodId) && isset($siiDispatchedByPsonoProduct[$psono . '___' . $prodId])) {
            $siiDisp = $siiDispatchedByPsonoProduct[$psono . '___' . $prodId];
        } elseif (!empty($sono) && !empty($prodId) && isset($siiDispatchedBySonoProduct[$sono . '___' . $prodId])) {
            $siiDisp = $siiDispatchedBySonoProduct[$sono . '___' . $prodId];
        }

        $consumedStatus = strtolower(trim($row['consumed_status'] ?? ''));
        $itemStatus = strtolower(trim($row['item_status'] ?? ''));
        $soStatus = strtolower(trim($row['sales_order_status'] ?? ''));

        // Check if row has dispatch activity
        $isDispatchedRow = (
            $siiDisp > 0 ||
            $consumedStatus === 'dispatch' || $consumedStatus === 'dispatched' || $consumedStatus === 'fulldispatch' || $consumedStatus === 'partialdispatch' ||
            $itemStatus === 'dispatched' || $itemStatus === 'final dispatch' || $itemStatus === 'completed' || $itemStatus === 'delivered' ||
            $soStatus === 'fulldispatch' || $soStatus === 'partialdispatch' ||
            !empty($row['dispatched_at']) ||
            intval($row['poc_dispatchedqty'] ?? 0) > 0 ||
            intval($row['soi_dispatchedqty'] ?? 0) > 0
        );

        if (!$isDispatchedRow) {
            continue;
        }

        $pInfo = $plantMap[$stk] ?? null;
        $sInfo = $sowingMap[$stk] ?? ($sowingMap[$psono] ?? null);

        $polyhouse_tray_count = intval($pInfo['polyhouse_tray_count'] ?? 0);
        $polyhouse_plant_qty = intval($pInfo['polyhouse_plant_qty'] ?? 0);
        $ready_plant_qty = intval($pInfo['ready_plant_qty'] ?? 0);
        $dispatched_plant_qty = intval($pInfo['dispatched_plant_qty'] ?? 0);
        $remaining_trays_count = intval($pInfo['remaining_trays_count'] ?? 0);
        $total_sowing_trays_plants = intval($pInfo['total_sowing_plants_in_trays'] ?? 0);

        $spOrderQty = intval($sInfo['sp_orderqty'] ?? 0);
        $spTotalSowingQty = intval($sInfo['sp_totalsowingqty'] ?? 0);
        $spSowingDate = !empty($sInfo['sp_sowingdate']) ? $sInfo['sp_sowingdate'] : '';

        // Order Qty & Total Sowing Qty strictly from krf_polyhouse_sowingplant_details
        $orderQty = ($spOrderQty > 0) ? $spOrderQty : intval($row['order_qty'] ?? 0);
        $psoTotalQty = intval($row['pso_total_qty'] ?? 0);
        $allocatedQty = intval($row['allocated_qty'] ?? 0);

        // Skip rows with no allocation — nothing was ever allocated to this order
        if ($allocatedQty <= 0) {
            continue;
        }

        // poc_dispatchedqty is fetched raw (no IFNULL) so null means no poc record, 0 means explicit 0 in DB
        $pocRaw = $row['poc_dispatchedqty'];
        $pocHasRecord = ($pocRaw !== null); // poc join matched a row
        $pocDispatchedQty = $pocHasRecord ? intval($pocRaw) : null;
        $consumedQty = intval($row['consumed_qty'] ?? 0);

        // Sowing Qty: from krf_polyhouse_sowingplant_details.totalsowingqty
        $sowingQty = ($spTotalSowingQty > 0) ? $spTotalSowingQty : (($psoTotalQty > 0) ? $psoTotalQty : (($total_sowing_trays_plants > 0) ? $total_sowing_trays_plants : $orderQty));
        
        $readyQty = ($ready_plant_qty > 0) ? $ready_plant_qty : (($polyhouse_plant_qty > 0) ? $polyhouse_plant_qty : $orderQty);

        // Dispatched quantity: Priority sequence strictly per product_id:
        // 1. sales_invoice_items (siiDisp)
        // 2. krf_productionorder_consumed.dispatchedqty (pocDispatchedQty)
        // 3. krf_sales_order_items.dispatchedqty (soi_dispatchedqty)
        // 4. If status is dispatched but unrecorded, default to allocatedQty
        if ($siiDisp > 0) {
            $dispatchedQty = $siiDisp;
            // Synchronize krf_productionorder_consumed.dispatchedqty strictly as per productid
            if ($pocHasRecord && intval($row['poc_dispatchedqty'] ?? 0) !== $siiDisp && !empty($row['consumed_id'])) {
                $updPocDisp = $krfconn->prepare("UPDATE krf_productionorder_consumed SET dispatchedqty = ? WHERE id = ?");
                if ($updPocDisp) {
                    $updPocDisp->bind_param("ii", $siiDisp, $row['consumed_id']);
                    $updPocDisp->execute();
                    $updPocDisp->close();
                }
            }
        } elseif ($pocHasRecord && intval($pocDispatchedQty ?? 0) > 0) {
            $dispatchedQty = intval($pocDispatchedQty);
        } elseif (intval($row['soi_dispatchedqty'] ?? 0) > 0) {
            $dispatchedQty = intval($row['soi_dispatchedqty']);
        } elseif (
            $consumedStatus === 'dispatch' || $consumedStatus === 'dispatched' || $consumedStatus === 'fulldispatch' ||
            $itemStatus === 'dispatched' || $itemStatus === 'final dispatch' || $itemStatus === 'completed' || $itemStatus === 'delivered'
        ) {
            $dispatchedQty = ($allocatedQty > 0) ? $allocatedQty : $orderQty;
        } else {
            $dispatchedQty = 0;
        }

        // Surplus / Rest plants currently available in polyhouse
        $surplusQty = ($polyhouse_plant_qty > 0) ? $polyhouse_plant_qty : max(0, $readyQty - $dispatchedQty);

        // Estimate Open Stock = allocated - dispatched (minimum 0)
        $estimateOpenStock = max(0, $allocatedQty - $dispatchedQty);

        // Check if order is Final Dispatched
        // poc record existing (even with dispatchedqty=0) counts as final dispatched
        $isFinalDispatched = (
            $pocHasRecord ||
            strtolower(trim($row['item_status'] ?? '')) === 'dispatched' || 
            strtolower(trim($row['item_status'] ?? '')) === 'delivered' ||
            strtolower(trim($row['item_status'] ?? '')) === 'completed' ||
            strtolower(trim($row['item_status'] ?? '')) === 'final dispatch' ||
            strtolower(trim($row['consumed_status'] ?? '')) === 'dispatched' ||
            strtolower(trim($row['pso_status'] ?? '')) === 'dispatched' ||
            strtolower(trim($row['pso_workingstatus'] ?? '')) === 'final dispatch' ||
            !empty($row['dispatched_at']) ||
            $dispatched_plant_qty > 0 ||
            $psoDispatchQty > 0
        );

        // BUSINESS RULE:
        // Show ONLY final dispatch marked orders where estimate_open_stock > 1.
        // estimate_open_stock = allocated_qty - dispatched_qty
        // If allocated = 800 and dispatched = 800 -> estimate_open_stock = 0 -> DO NOT SHOW.
        // If allocated = 1000, dispatched = 700 -> estimate_open_stock = 300 -> SHOW.
        if (!$isFinalDispatched) {
            continue;
        }

        if ($estimateOpenStock <= 1 && $remaining_trays_count <= 0) {
            continue;
        }

        $row['sp_orderqty'] = $spOrderQty;
        $row['order_qty'] = $orderQty;
        $row['sowing_qty'] = $sowingQty;
        $row['polyhouse_received_qty'] = ($total_sowing_trays_plants > 0) ? $total_sowing_trays_plants : (($sowingQty > 0) ? $sowingQty : $orderQty);
        $row['polyhouse_tray_count'] = $polyhouse_tray_count;
        $row['ready_to_sell_qty'] = $readyQty;
        $row['ready_trays_count'] = intval($pInfo['ready_trays_count'] ?? 0);
        $row['empty_shifting_plant_qty'] = intval($pInfo['empty_shifting_plant_qty'] ?? 0);
        $row['empty_shifting_trays_count'] = intval($pInfo['empty_shifting_trays_count'] ?? 0);
        $row['dispatched_trays_count'] = intval($pInfo['dispatched_trays_count'] ?? 0);
        $row['allocated_qty'] = $allocatedQty;
        $row['dispatched_qty'] = $dispatchedQty;
        $row['surplus_plants_qty'] = $surplusQty;
        $row['estimate_open_stock'] = $estimateOpenStock;
        $row['remaining_trays_count'] = $remaining_trays_count;
        $row['polyhouse_names'] = !empty($pInfo['polyhouse_names']) ? $pInfo['polyhouse_names'] : 'Polyhouse Area';
        $row['bed_numbers'] = !empty($pInfo['bed_numbers']) ? $pInfo['bed_numbers'] : '-';
        $row['formatted_delivery_date'] = !empty($row['delivery_date']) ? date('d-M-Y', strtotime($row['delivery_date'])) : '-';
        $row['formatted_sowing_date'] = !empty($spSowingDate) ? date('d-M-Y', strtotime($spSowingDate)) : (!empty($row['pso_sowingdate']) ? date('d-M-Y', strtotime($row['pso_sowingdate'])) : '-');
        $row['formatted_booking_date'] = !empty($row['sales_order_created_at']) ? date('d-M-Y', strtotime($row['sales_order_created_at'])) : '-';

        // Update estimate_open_stock in DB for this poc record
        if ($pocHasRecord && !empty($row['consumed_id'])) {
            $updateEOS = $krfconn->prepare("UPDATE krf_productionorder_consumed SET estimate_open_stock = ? WHERE id = ?");
            if ($updateEOS) {
                $updateEOS->bind_param('ii', $estimateOpenStock, $row['consumed_id']);
                $updateEOS->execute();
                $updateEOS->close();
            }
        }

        $totalSowingAgg += $sowingQty;
        $totalDispatchedAgg += $dispatchedQty;
        $totalSurplusAgg += $surplusQty;

        $surplusList[] = $row;
    }

    echo json_encode([
        "status" => "success",
        "data" => $surplusList,
        "total_surplus_orders" => count($surplusList),
        "total_sowing_plants" => $totalSowingAgg,
        "total_dispatched_plants" => $totalDispatchedAgg,
        "total_surplus_plants" => $totalSurplusAgg
    ]);
    exit;

} else if ($action == "GetOrderGrowthAndTrayDetails") {
    $projectid = intval($_POST['projectid'] ?? 0);
    $soiId = intval($_POST['soi_id'] ?? 0);
    $stockOrderNo = trim($_POST['stockorder_no'] ?? '');

    // 1. Get Sales Order Item Details
    $orderItem = null;
    if ($soiId > 0) {
        $soiSql = "
            SELECT 
                soi.id AS soi_id,
                soi.sono,
                soi.psono,
                soi.product_id,
                soi.product_name,
                soi.quantity AS order_qty,
                soi.deliverydate AS delivery_date,
                IFNULL(soi.reservedqty, 0) AS reserved_qty,
                IFNULL(soi.balancereservedqty, IFNULL(soi.quantity, 0) - IFNULL(soi.reservedqty, 0)) AS balance_reserved_qty,
                IFNULL(soi.status, 'Pending') AS item_status,
                soi.stockorder_no AS soi_stockorderno,
                
                so.clientid,
                so.clientname,
                so.mobileno,
                so.statename,
                so.districtname,
                so.delivery_address,
                so.created_at AS sales_order_created_at
            FROM krf_sales_order_items soi
            JOIN krf_sales_order so ON soi.sono = so.sono
            WHERE soi.id = ?
            LIMIT 1
        ";
        $stmt = $krfconn->prepare($soiSql);
        if ($stmt) {
            $stmt->bind_param("i", $soiId);
            $stmt->execute();
            $orderItem = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
    }

    if (!$orderItem && !empty($stockOrderNo)) {
        $soiByStkSql = "
            SELECT 
                soi.id AS soi_id,
                soi.sono,
                soi.psono,
                soi.product_id,
                soi.product_name,
                soi.quantity AS order_qty,
                soi.deliverydate AS delivery_date,
                IFNULL(soi.reservedqty, 0) AS reserved_qty,
                IFNULL(soi.balancereservedqty, IFNULL(soi.quantity, 0) - IFNULL(soi.reservedqty, 0)) AS balance_reserved_qty,
                IFNULL(soi.status, 'Pending') AS item_status,
                soi.stockorder_no AS soi_stockorderno,
                
                so.clientid,
                so.clientname,
                so.mobileno,
                so.statename,
                so.districtname,
                so.delivery_address,
                so.created_at AS sales_order_created_at
            FROM krf_sales_order_items soi
            JOIN krf_sales_order so ON soi.sono = so.sono
            WHERE soi.stockorder_no = ?
            ORDER BY soi.id DESC
            LIMIT 1
        ";
        $stStmt = $krfconn->prepare($soiByStkSql);
        if ($stStmt) {
            $stStmt->bind_param("s", $stockOrderNo);
            $stStmt->execute();
            $orderItem = $stStmt->get_result()->fetch_assoc();
            $stStmt->close();
        }
    }

    if (!$orderItem) {
        $orderItem = [
            'soi_id' => $soiId,
            'sono' => '-',
            'psono' => '-',
            'product_name' => '-',
            'order_qty' => 0,
            'delivery_date' => '',
            'clientname' => 'Client',
            'mobileno' => '-',
            'statename' => '-',
            'districtname' => '-',
            'delivery_address' => '-'
        ];
    }

    $rawProdName = $orderItem['product_name'] ?? '';
    $orderItem['clean_product_name'] = preg_replace('/^(NonGrafted|Non-Grafted)\s*[-:]?\s*/i', '', $rawProdName);
    $orderItem['is_grafted'] = (stripos($rawProdName, 'grafted') !== false && stripos($rawProdName, 'nongrafted') === false && stripos($rawProdName, 'non-grafted') === false) ? 1 : 0;
    $orderItem['formatted_delivery_date'] = !empty($orderItem['delivery_date']) ? date('d-M-Y', strtotime($orderItem['delivery_date'])) : '-';

    // 2. Resolve Stock Order No
    if (empty($stockOrderNo) || $stockOrderNo === 'Not Allocated') {
        $stockOrderNo = $orderItem['soi_stockorderno'] ?? '';
    }

    // 3. Get Stock Order Details & Allocation Info
    $stockOrderInfo = null;
    $allocationInfo = null;
    if (!empty($stockOrderNo) && $stockOrderNo !== 'Not Allocated') {
        $psoSql = "
            SELECT 
                pso.*,
                IFNULL(cm.is_trader, 0) AS is_trader,
                cm.client_name AS stock_client_name,
                IFNULL((
                    SELECT SUM(consumed_qty) 
                    FROM krf_productionorder_consumed 
                    WHERE stockorderno = pso.stockorder_no 
                      AND LOWER(IFNULL(confirmstatus, '')) NOT IN('cancelled','removed','remove','delete')
                ), 0) AS total_allocated_qty
            FROM krf_production_stockorder pso
            LEFT JOIN krf_client_master cm ON pso.clientid = cm.client_id
            WHERE pso.stockorder_no = ? AND (pso.projectid = ? OR ? = 0)
            LIMIT 1
        ";
        $pStmt = $krfconn->prepare($psoSql);
        $pStmt->bind_param("sii", $stockOrderNo, $projectid, $projectid);
        $pStmt->execute();
        $stockOrderInfo = $pStmt->get_result()->fetch_assoc();

        if ($stockOrderInfo) {
            $stockOrderInfo['is_trader'] = intval($stockOrderInfo['is_trader'] ?? 0);
            $stockOrderInfo['order_type_label'] = ($stockOrderInfo['is_trader'] === 1) ? 'Trader Production Order' : 'Self Production Order';
            $stockOrderInfo['formatted_sowing_date'] = !empty($stockOrderInfo['sowingdate']) ? date('d-M-Y', strtotime($stockOrderInfo['sowingdate'])) : '-';
            $stockOrderInfo['formatted_expected_delivery'] = !empty($stockOrderInfo['expected_delivery_date']) ? date('d-M-Y', strtotime($stockOrderInfo['expected_delivery_date'])) : '-';
            $rawStockProd = $stockOrderInfo['product_name'] ?? '';
            $stockOrderInfo['clean_product_name'] = preg_replace('/^(NonGrafted|Non-Grafted)\s*[-:]?\s*/i', '', $rawStockProd);
        }

        // Consumption / Allocation Record for current order item
        $pocSql = "
            SELECT poc.*
            FROM krf_productionorder_consumed poc
            WHERE (poc.stockorderno = ? AND poc.psono = ?) OR (poc.psono = ? AND poc.product_id = ?)
            ORDER BY poc.id DESC
            LIMIT 1
        ";
        $pocStmt = $krfconn->prepare($pocSql);
        $pocStmt->bind_param("ssss", $stockOrderNo, $orderItem['psono'], $orderItem['psono'], $orderItem['product_id']);
        $pocStmt->execute();
        $allocationInfo = $pocStmt->get_result()->fetch_assoc();
        if ($allocationInfo) {
            $allocationInfo['formatted_allocated_at'] = !empty($allocationInfo['allocatedat']) ? date('d-M-Y H:i', strtotime($allocationInfo['allocatedat'])) : (!empty($allocationInfo['created_at']) ? date('d-M-Y H:i', strtotime($allocationInfo['created_at'])) : '-');
            $allocationInfo['formatted_dispatched_at'] = !empty($allocationInfo['dispatchedat']) ? date('d-M-Y H:i', strtotime($allocationInfo['dispatchedat'])) : '-';
        }

        $isFullyDispatched = (
            strtolower(trim($orderItem['item_status'] ?? '')) === 'dispatched' || 
            strtolower(trim($orderItem['item_status'] ?? '')) === 'final dispatch' ||
            strtolower(trim($allocationInfo['status'] ?? '')) === 'dispatched' ||
            !empty($allocationInfo['dispatchedat'])
        );
        $orderItem['is_fully_dispatched'] = $isFullyDispatched;
        $orderItem['dispatched_date'] = !empty($allocationInfo['dispatchedat']) ? date('d-M-Y', strtotime($allocationInfo['dispatchedat'])) : (!empty($orderItem['delivery_date']) ? date('d-M-Y', strtotime($orderItem['delivery_date'])) : '-');
        $orderItem['dispatched_by'] = !empty($allocationInfo['dispatchedby']) ? $allocationInfo['dispatchedby'] : '';
    }

    // 4. Fetch Tray Breakdown from Polyhouse Plant Details
    $trays = [];
    $polyhouseSummary = [];
    $bedSummary = [];
    $totalPlantsInTrays = 0;
    $totalHealthyPlants = 0;
    $totalUnhealthyPlants = 0;
    $totalFailurePlants = 0;
    $totalDispatchedPlants = 0;
    $totalSurplusPlants = 0;

    if (!empty($stockOrderNo) && $stockOrderNo !== 'Not Allocated') {
        $traySql = "
            SELECT 
                ppd.polyhouseplantid,
                ppd.stockorder_no,
                ppd.tray_id,
                ppd.tray_code,
                ppd.qtypertray,
                ppd.health_status,
                ppd.status,
                ppd.workingstatus,
                ppd.shiftingstatus,
                ppd.shiftingby,
                ppd.shiftingat,
                ppd.failurecount,
                ppd.remarks,
                ppd.bedrackname,
                ppd.created_at,
                ph.polyhousename
            FROM krf_polyhouse_plant_details ppd
            LEFT JOIN krf_polyhouse_master ph ON ppd.polyhouseid = ph.polyhouseid
            WHERE ppd.stockorder_no = ?
            ORDER BY ppd.polyhouseplantid ASC
        ";
        $tStmt = $krfconn->prepare($traySql);
        if ($tStmt) {
            $tStmt->bind_param("s", $stockOrderNo);
            $tStmt->execute();
            $tRes = $tStmt->get_result();

            $phMap = [];
            $bedMap = [];
            $statusGroups = [];
            $healthGroups = [];
            $shiftingGroups = [
                'shifted' => ['status_key' => 'shifted', 'label' => 'Shifted', 'tray_count' => 0, 'plant_qty' => 0],
                'nonshifted' => ['status_key' => 'nonshifted', 'label' => 'Non-Shifted', 'tray_count' => 0, 'plant_qty' => 0]
            ];
            $totalReadyPlants = 0;
            $totalDispatchedPlants = 0;
            $totalEmptyShiftingPlants = 0;
            $totalOtherPlants = 0;
            $totalShiftedPlants = 0;
            $totalNonShiftedPlants = 0;

            $readyTraysCount = 0;
            $dispatchedTraysCount = 0;
            $emptyShiftingTraysCount = 0;
            $otherTraysCount = 0;
            $shiftedTraysCount = 0;
            $nonshiftedTraysCount = 0;
            $healthyTraysCount = 0;
            $unhealthyTraysCount = 0;

            while ($t = $tRes->fetch_assoc()) {
                $qty = intval($t['qtypertray'] ?? 0);
                $fail = intval($t['failurecount'] ?? 0);
                $netQty = max(0, $qty - $fail);
                $health = strtolower(trim($t['health_status'] ?? 'healthy'));
                $st = trim($t['status'] ?? '');
                $wst = trim($t['workingstatus'] ?? '');
                $phName = !empty($t['polyhousename']) ? $t['polyhousename'] : 'Main Polyhouse';
                $bedName = !empty($t['bedrackname']) ? $t['bedrackname'] : '-';

                $totalPlantsInTrays += $qty;
                $totalFailurePlants += $fail;

                $stLower = strtolower($st);
                $wstLower = strtolower($wst);

                if ($stLower === 'final dispatch' || $wstLower === 'final dispatch') {
                    $groupKey = 'final_dispatch';
                    $groupName = 'Final Dispatch';
                    $displayStatus = 'Final Dispatch';
                    $badgeClass = 'bg-dark text-white';
                    $totalDispatchedPlants += $netQty;
                    $dispatchedTraysCount++;
                } elseif ($stLower === 'empty after shifting' || $wstLower === 'empty after shifting') {
                    $groupKey = 'empty_after_shifting';
                    $groupName = 'Empty After Shifting';
                    $displayStatus = 'Empty After Shifting';
                    $badgeClass = 'bg-warning text-dark';
                    $totalEmptyShiftingPlants += $netQty;
                    $emptyShiftingTraysCount++;
                } elseif ($stLower === 'readytosale' || $stLower === 'ready to sale' || $wstLower === 'readytosale' || $wstLower === 'ready to sale' || $stLower === 'openstock' || $stLower === 'open stock') {
                    $groupKey = 'ready_to_sale';
                    $groupName = 'Ready to Sale';
                    $displayStatus = 'Ready to Sale';
                    $badgeClass = 'bg-success text-white';
                    $totalReadyPlants += $netQty;
                    $readyTraysCount++;
                } else {
                    $groupKey = preg_replace('/[^a-z0-9_]/', '_', $stLower ?: ($wstLower ?: 'other'));
                    $groupName = $st ?: ($wst ?: 'Polyhouse');
                    $displayStatus = $groupName;
                    $badgeClass = 'bg-primary text-white';
                    $totalOtherPlants += $netQty;
                    $otherTraysCount++;
                }

                if (!isset($statusGroups[$groupKey])) {
                    $statusGroups[$groupKey] = [
                        'group_key' => $groupKey,
                        'status_name' => $groupName,
                        'badge_class' => $badgeClass,
                        'tray_count' => 0,
                        'gross_qty' => 0,
                        'fail_qty' => 0,
                        'net_qty' => 0
                    ];
                }
                $statusGroups[$groupKey]['tray_count']++;
                $statusGroups[$groupKey]['gross_qty'] += $qty;
                $statusGroups[$groupKey]['fail_qty'] += $fail;
                $statusGroups[$groupKey]['net_qty'] += $netQty;

                // Health Status Breakdown
                $rawHealth = !empty($t['health_status']) ? trim($t['health_status']) : 'Healthy';
                $healthKey = strtolower($rawHealth);
                if (!isset($healthGroups[$healthKey])) {
                    $healthBadgeClass = ($healthKey === 'healthy') ? 'bg-success text-white' : (($healthKey === 'unhealthy' || $healthKey === 'dead' || $healthKey === 'diseased') ? 'bg-danger text-white' : 'bg-warning text-dark');
                    $healthGroups[$healthKey] = [
                        'health_key' => $healthKey,
                        'health_label' => ucfirst($rawHealth),
                        'badge_class' => $healthBadgeClass,
                        'tray_count' => 0,
                        'plant_qty' => 0
                    ];
                }
                $healthGroups[$healthKey]['tray_count']++;
                $healthGroups[$healthKey]['plant_qty'] += $netQty;

                if ($healthKey === 'healthy') {
                    $healthyTraysCount++;
                } else {
                    $unhealthyTraysCount++;
                }

                // Shifting Status Breakdown
                $rawShifting = strtolower(trim($t['shiftingstatus'] ?? 'nonshifted'));
                if ($rawShifting === 'shifted') {
                    $shiftingGroups['shifted']['tray_count']++;
                    $shiftingGroups['shifted']['plant_qty'] += $netQty;
                    $totalShiftedPlants += $netQty;
                    $shiftedTraysCount++;
                } else {
                    $shiftingGroups['nonshifted']['tray_count']++;
                    $shiftingGroups['nonshifted']['plant_qty'] += $netQty;
                    $totalNonShiftedPlants += $netQty;
                    $nonshiftedTraysCount++;
                }

                $isDispatchedTray = ($stLower === 'final dispatch' || $wstLower === 'final dispatch');
                if ($health === 'unhealthy') {
                    $totalUnhealthyPlants += $netQty;
                } else {
                    $totalHealthyPlants += $netQty;
                    if (!$isDispatchedTray && $stLower !== 'empty after shifting') {
                        $totalSurplusPlants += $netQty;
                    }
                }

                if (!isset($phMap[$phName])) {
                    $phMap[$phName] = [
                        'polyhousename' => $phName, 
                        'tray_count' => 0, 
                        'total_plants' => 0,
                        'ready_plants' => 0, 
                        'ready_trays_count' => 0,
                        'healthy_plants' => 0, 
                        'healthy_trays_count' => 0,
                        'shifted_plants' => 0, 
                        'shifted_trays_count' => 0,
                        'nonshifted_plants' => 0,
                        'nonshifted_trays_count' => 0
                    ];
                }
                $phMap[$phName]['tray_count']++;
                $phMap[$phName]['total_plants'] += $netQty;
                if (!$isDispatchedTray && $health !== 'unhealthy' && $stLower !== 'empty after shifting') {
                    $phMap[$phName]['ready_plants'] += $netQty;
                    $phMap[$phName]['ready_trays_count']++;
                }
                if ($healthKey === 'healthy') {
                    $phMap[$phName]['healthy_plants'] += $netQty;
                    $phMap[$phName]['healthy_trays_count']++;
                }
                if ($rawShifting === 'shifted') {
                    $phMap[$phName]['shifted_plants'] += $netQty;
                    $phMap[$phName]['shifted_trays_count']++;
                } else {
                    $phMap[$phName]['nonshifted_plants'] += $netQty;
                    $phMap[$phName]['nonshifted_trays_count']++;
                }

                $t['group_key'] = $groupKey;
                $t['display_status'] = $displayStatus;
                $t['badge_class'] = $badgeClass;
                $t['net_quantity'] = $netQty;
                $t['polyhousename'] = $phName;
                $trays[] = $t;
            }
            $tStmt->close();
        }

        // Sowing Plant Details Breakdown
        $sowingEntries = [];
        $sowSql = "
            SELECT 
                sowingplantid,
                stockorder_no,
                psono,
                totalsowingqty,
                traycavity,
                roottype,
                lotno,
                sowing_date,
                sowingdate_internal,
                health_status,
                status,
                workingstatus,
                remarks,
                created_at
            FROM krf_polyhouse_sowingplant_details
            WHERE stockorder_no = ? OR (psono = ? AND psono != '')
            ORDER BY sowingplantid ASC
        ";
        $sStmt = $krfconn->prepare($sowSql);
        if ($sStmt) {
            $sStmt->bind_param("ss", $stockOrderNo, $orderItem['psono']);
            $sStmt->execute();
            $sRes = $sStmt->get_result();
            while ($sRow = $sRes->fetch_assoc()) {
                $sRow['formatted_date'] = !empty($sRow['sowingdate_internal']) ? date('d-M-Y', strtotime($sRow['sowingdate_internal'])) : (!empty($sRow['sowing_date']) ? date('d-M-Y', strtotime($sRow['sowing_date'])) : '-');
                $sowingEntries[] = $sRow;
            }
            $sStmt->close();
        }

        // All Allocation Records for this stock order
        $allAllocations = [];
        $allocListSql = "
            SELECT 
                poc.id,
                poc.stockorderno,
                poc.psono,
                poc.product_id,
                poc.consumed_qty,
                poc.allocate_healthyplant,
                poc.allocate_unhealthyplant,
                poc.dispatchedqty,
                poc.estimate_open_stock,
                poc.confirmstatus,
                poc.status,
                poc.allocatedat,
                poc.dispatchedat,
                poc.allocatedby,
                poc.dispatchedby,
                poc.remark,
                so.sono,
                so.clientname,
                so.mobileno,
                so.statename,
                so.districtname
            FROM krf_productionorder_consumed poc
            LEFT JOIN krf_sales_order so ON poc.sono = so.sono
            WHERE (poc.stockorderno = ? AND poc.stockorderno != '') OR (poc.psono = ? AND poc.psono != '')
            ORDER BY poc.id DESC
        ";
        $alStmt = $krfconn->prepare($allocListSql);
        if ($alStmt) {
            $alStmt->bind_param("ss", $stockOrderNo, $orderItem['psono']);
            $alStmt->execute();
            $alRes = $alStmt->get_result();
            while ($alRow = $alRes->fetch_assoc()) {
                $alRow['formatted_allocated_at'] = !empty($alRow['allocatedat']) ? date('d-M-Y H:i', strtotime($alRow['allocatedat'])) : '-';
                $alRow['formatted_dispatched_at'] = !empty($alRow['dispatchedat']) ? date('d-M-Y H:i', strtotime($alRow['dispatchedat'])) : '-';
                $allAllocations[] = $alRow;
            }
            $alStmt->close();
        }

        $polyhouseSummary = array_values($phMap ?? []);
    }

    echo json_encode([
        "status" => "success",
        "order_item" => $orderItem,
        "stock_order_info" => $stockOrderInfo,
        "allocation_info" => $allocationInfo,
        "all_allocations" => $allAllocations ?? [],
        "sowing_entries" => $sowingEntries ?? [],
        "trays" => $trays,
        "polyhouse_summary" => $polyhouseSummary,
        "summary" => [
            "total_trays" => count($trays),
            "total_plants_in_trays" => $totalPlantsInTrays,
            "total_received_plants" => $totalPlantsInTrays,
            "total_ready_plants" => $totalReadyPlants,
            "ready_trays_count" => $readyTraysCount,
            "total_dispatched_plants" => $totalDispatchedPlants,
            "dispatched_trays_count" => $dispatchedTraysCount,
            "total_empty_shifting_plants" => $totalEmptyShiftingPlants,
            "empty_shifting_trays_count" => $emptyShiftingTraysCount,
            "total_other_plants" => $totalOtherPlants,
            "other_trays_count" => $otherTraysCount,
            "total_healthy_plants" => $totalHealthyPlants,
            "healthy_trays_count" => $healthyTraysCount,
            "total_unhealthy_plants" => $totalUnhealthyPlants,
            "unhealthy_trays_count" => $unhealthyTraysCount,
            "total_failure_plants" => $totalFailurePlants,
            "total_surplus_plants" => $totalSurplusPlants,
            "total_shifted_plants" => $totalShiftedPlants,
            "shifted_trays_count" => $shiftedTraysCount,
            "total_nonshifted_plants" => $totalNonShiftedPlants,
            "nonshifted_trays_count" => $nonshiftedTraysCount,
            "status_groups" => array_values($statusGroups),
            "health_groups" => array_values($healthGroups),
            "shifting_groups" => array_values($shiftingGroups)
        ]
    ]);
    exit;

} else {
    echo json_encode(["status" => "error", "message" => "Invalid API action."]);
    exit;
}
?>
