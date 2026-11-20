<?php
date_default_timezone_set("Asia/Calcutta");
error_reporting(E_ALL);
require(__DIR__ . '/../../connection.php');
require(__DIR__ . '/../../loginCell/functions/login_functions.php');
include(__DIR__ . '/../functions/orders_functions.php');
include(__DIR__ . '/../functions/product_functions.php');
include(__DIR__ . '/../functions/seed_functions.php');
include(__DIR__ . '/../functions/tray_functions.php');
include(__DIR__ . '/../functions/client_functions.php');
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === "chkstock") {

    $projectid    = (int)($_POST['projectid'] ?? 0);
    $productid    = (int)($_POST['productid'] ?? 0);
    $requiredqty  = (int)($_POST['requiredqty'] ?? 0);
    $deliverydate = $_POST['deliverydate'] ?? '';
    $current_psono = $krfconn->real_escape_string(trim($_POST['current_psono'] ?? $_POST['psono'] ?? ''));
    $exclude_consumed_sql = !empty($current_psono) ? " AND (psono != '{$current_psono}' OR psono IS NULL) " : "";
    $exclude_c_consumed_sql = !empty($current_psono) ? " AND (c.psono != '{$current_psono}' OR c.psono IS NULL) " : "";

    $returnarray = [];

    // Check if product is grafted
    $isGrafted = false;
    $chkGraftQry = $krfconn->query("SELECT IsGrafted, ProductName FROM product_master WHERE ProductID = {$productid} LIMIT 1");
    if ($chkGraftQry && $gRow = $chkGraftQry->fetch_assoc()) {
        $gVal = strtolower(trim((string)($gRow['IsGrafted'] ?? '')));
        $pName = strtolower(trim((string)($gRow['ProductName'] ?? '')));
        if ($gVal === 'grafted' || $gVal === 'yes' || $gVal === '1' || strpos($pName, 'grafted') === 0) {
            $isGrafted = true;
        }
    }
    // If grafted product, rootstock orders (prefixed with 'RSO-') should NOT be shown for stock allocation against provisional orders
    // $graft_stock_exclude_sql = $isGrafted ? " AND LOWER(stk.stockorder_no) NOT LIKE 'rso-%' AND LOWER(stk.psono) NOT LIKE 'rso-%' " : "";
    // $graft_c_exclude_sql     = $isGrafted ? " AND LOWER(stockorder_no) NOT LIKE 'rso-%' " : "";
    $graft_stock_exclude_sql = "";
    $graft_c_exclude_sql     = "";

    // ==========================================
    // SIMPLE STOCK AVAILABILITY QUERY
    // Removed krf_readytosale_stocks
    // ==========================================
    $qry = "
        SELECT
            stk.id AS stksno,
            stk.projectid,
            stk.clientid,
            stk.productid,
            stk.sowinglocationid,

            p.ProductName AS productname,

            stk.stockorder_no,

            DATE_FORMAT(
                stk.expected_delivery_date,
                '%d-%m-%Y'
            ) AS displaydate,

            stk.expected_delivery_date,

            COALESCE(stk.quantity, 0) AS stkqty,

            COALESCE(
                ( SELECT SUM(pc.consumed_qty)
                  FROM krf_productionorder_consumed pc
                  WHERE pc.stockorderno = stk.stockorder_no
                    AND LOWER(TRIM(pc.confirmstatus)) <> 'confirmed'
                    AND pc.status IN ('pending','readytoallocate','allocated','readytoload','loaded','readytodispatch','dispatched','PartialDispatch','FullDispatch')
                    AND (pc.tedit IS NULL OR pc.tedit = '')
                    {$exclude_consumed_sql}
                ), 0) AS ordreadytosaleqty,

            COALESCE(cl.is_trader, 0) AS is_trader,

            COALESCE(
                c.confirmed_allocated, 0
            ) AS confirmed_allocated,

            COALESCE(
                c.unconfirmed_allocated, 0
            ) AS unconfirmed_allocated,

            (
                COALESCE(stk.quantity, 0)
                + COALESCE(stk.Qty_from_readysale, 0)
                - COALESCE(c.confirmed_allocated, 0)
            ) AS base_availableqty,

            0 AS total_readytosale_qty,

            0 AS readytosale_qty,

            (
                COALESCE(stk.quantity, 0)
                + COALESCE(stk.Qty_from_readysale, 0)
                - COALESCE(c.confirmed_allocated, 0)
            ) AS availableqty

        FROM krf_production_stockorder AS stk

        LEFT JOIN krf_client_master AS cl
            ON cl.projectid = stk.projectid
            AND cl.client_id = stk.clientid

        LEFT JOIN (
            SELECT
                stockorderno,
                SUM(
                    CASE
                        WHEN LOWER(
                            TRIM(
                                COALESCE(confirmstatus, '')
                            )
                        ) = 'confirmed'
                        THEN COALESCE(consumed_qty, 0)
                        ELSE 0
                    END
                ) AS confirmed_allocated,

                SUM(
                    CASE
                        WHEN LOWER(
                            TRIM(
                                COALESCE(confirmstatus, '')
                            )
                        ) <> 'confirmed'
                        THEN COALESCE(consumed_qty, 0)
                        ELSE 0
                    END
                ) AS unconfirmed_allocated

            FROM krf_productionorder_consumed WHERE status in('pending','readytoallocate','allocated','readytoload','loaded','readytodispatch','dispatched','PartialDispatch','FullDispatch') AND (tedit IS NULL OR tedit = '') {$exclude_consumed_sql}
            GROUP BY stockorderno

        ) AS c
            ON c.stockorderno = stk.stockorder_no

        LEFT JOIN product_master AS p
            ON p.ProductID = stk.productid

        WHERE stk.projectid = {$projectid}
          AND stk.productid = {$productid}
          AND stk.clientid = 'C00001'
          AND stk.status IN ('Confirmed', 'confirmed') AND (stk.tedit is null or stk.tedit='')
          {$graft_stock_exclude_sql}

        HAVING availableqty > 0

        ORDER BY
            expected_delivery_date ASC,
            ABS(availableqty - {$requiredqty}) ASC
    ";

    $result = $krfconn->query($qry);

    if (!$result) {
        echo json_encode([
            'status'  => false,
            'message' => $krfconn->error,
            'data'    => []
        ]);
        exit;
    }

    while ($row = $result->fetch_assoc()) {

        // Cast numeric fields
        $row['stkqty'] = (int)$row['stkqty'];
        $row['ordreadytosaleqty'] = (int)$row['ordreadytosaleqty'];
        $row['confirmed_allocated'] = (int)$row['confirmed_allocated'];
        $row['unconfirmed_allocated'] = (int)$row['unconfirmed_allocated'];
        $row['base_availableqty'] = (int)$row['base_availableqty'];
        $row['total_readytosale_qty'] = 0;
        $row['readytosale_qty'] = 0;
        $row['availableqty'] = (int)$row['availableqty'];
        $row['is_trader'] = (int)$row['is_trader'];



        $row['stock_type'] = 'internal_stock';
        // if ($isGrafted) {
        //     $curSo = strtolower(trim((string)($row['stockorder_no'] ?? '')));
        //     $curPso = strtolower(trim((string)($row['psono'] ?? '')));
        //     if (strpos($curSo, 'rso-') === 0 || strpos($curPso, 'rso-') === 0) {
        //         continue;
        //     }
        // }
        $returnarray[] = $row;
    }

    // ==========================================
    // TRACK EXISTING STOCK ORDERS TO ENFORCE CLEAN SINGLE-ROW RENDERING
    // ==========================================
    $existingStockNos = [];
    foreach ($returnarray as $existingItem) {
        $eStk = trim((string)($existingItem['stockorder_no'] ?? ''));
        if ($eStk !== '') {
            $existingStockNos[$eStk] = true;
        }
    }

    // ==========================================
    // CLIENT PRODUCTION ORDER SURPLUS STOCK (OPTIMIZED)
    // If a client order (clientid != 'C00001' and is_trader != 1) is fully dispatched,
    // any remaining plants sitting in krf_polyhouse_plant_details with status = 'Readytosale' are available as surplus open stock.
    // ==========================================
    $surplusCandQry = "
        SELECT stk.stockorder_no
        FROM krf_production_stockorder stk
        LEFT JOIN krf_client_master cl 
            ON cl.projectid = stk.projectid AND cl.client_id = stk.clientid
        LEFT JOIN krf_sales_order so 
            ON so.psono = stk.psono AND so.projectid = stk.projectid
        WHERE stk.projectid = {$projectid}
          AND stk.productid = {$productid}
          AND stk.clientid != 'C00001'
          AND COALESCE(cl.is_trader, 0) != 1
          AND stk.quantity > 0
          {$graft_stock_exclude_sql}
          AND (LOWER(TRIM(COALESCE(stk.workingstatus, ''))) = 'final dispatch' OR (stk.dispatchqty > 0 AND stk.dispatchqty >= stk.quantity) OR LOWER(REPLACE(TRIM(COALESCE(so.status, '')), ' ', '')) IN ('fulldispatch', 'fulldispatched'))
    ";
    $surplusCandRes = $krfconn->query($surplusCandQry);
    $surplusCandList = [];
    if ($surplusCandRes) {
        while ($cRow = $surplusCandRes->fetch_assoc()) {
            $surplusCandList[] = "'" . $krfconn->real_escape_string($cRow['stockorder_no']) . "'";
        }
    }

    if (!empty($surplusCandList)) {
        $candIn = implode(',', array_unique($surplusCandList));
        $surplusQry = "
            SELECT
                stk.id AS stksno,
                stk.projectid,
                stk.clientid,
                stk.productid,
                stk.sowinglocationid,
                p.ProductName AS productname,
                stk.stockorder_no,
                stk.psono,
                DATE_FORMAT(stk.expected_delivery_date, '%d-%m-%Y') AS displaydate,
                stk.expected_delivery_date,
                COALESCE(cl.is_trader, 0) AS is_trader,
                cl.client_name,

                COALESCE(ph.physical_readytosale_qty, 0) AS total_readytosale_qty,
                0 AS readytosale_qty,
                COALESCE(ph.physical_readytosale_qty, 0) AS stkqty,
                0 AS ordreadytosaleqty,

                COALESCE(c.confirmed_allocated, 0) AS confirmed_allocated,
                COALESCE(c.unconfirmed_allocated, 0) AS unconfirmed_allocated,

                (
                    COALESCE(ph.physical_readytosale_qty, 0) - COALESCE(c.confirmed_allocated, 0)
                ) AS base_availableqty,

                (
                    COALESCE(ph.physical_readytosale_qty, 0) - COALESCE(c.confirmed_allocated, 0)
                ) AS availableqty,

                'client_surplus' AS stock_type

            FROM krf_production_stockorder AS stk

            LEFT JOIN krf_sales_order AS so
                ON so.psono = stk.psono
               AND so.projectid = stk.projectid

            INNER JOIN (
                SELECT
                    stockorder_no,
                    psono,
                    SUM(GREATEST(0, COALESCE(qtypertray, 0) - COALESCE(failurecount, 0) - COALESCE(dispatchqty, 0))) AS physical_readytosale_qty
                FROM krf_polyhouse_plant_details
                WHERE stockorder_no IN ({$candIn})
                  AND LOWER(REPLACE(TRIM(COALESCE(status, '')), ' ', '')) = 'readytosale'
                GROUP BY stockorder_no, psono
                HAVING physical_readytosale_qty > 0
            ) AS ph
                ON ph.stockorder_no = stk.stockorder_no

            LEFT JOIN (
                SELECT
                    c.stockorderno,
                    SUM(CASE WHEN LOWER(TRIM(COALESCE(c.confirmstatus, ''))) = 'confirmed' THEN COALESCE(c.consumed_qty, 0) ELSE 0 END) AS confirmed_allocated,
                    SUM(CASE WHEN LOWER(TRIM(COALESCE(c.confirmstatus, ''))) <> 'confirmed' THEN COALESCE(c.consumed_qty, 0) ELSE 0 END) AS unconfirmed_allocated
                FROM krf_productionorder_consumed c
                JOIN krf_production_stockorder s ON s.stockorder_no = c.stockorderno
                WHERE c.stockorderno IN ({$candIn})
                  AND c.psono != s.psono
                  AND c.status IN ('pending', 'allocated', 'dispatched') 
                  AND (c.tedit IS NULL OR c.tedit = '')
                  {$exclude_c_consumed_sql}
                GROUP BY c.stockorderno
            ) AS c
                ON c.stockorderno = stk.stockorder_no

            LEFT JOIN product_master AS p
                ON p.ProductID = stk.productid

            LEFT JOIN krf_client_master AS cl
                ON cl.projectid = stk.projectid
               AND cl.client_id = stk.clientid

            WHERE stk.stockorder_no IN ({$candIn})
            HAVING availableqty > 0
            ORDER BY stk.expected_delivery_date ASC
        ";

        $surplusResult = $krfconn->query($surplusQry);
        if ($surplusResult) {
            while ($sRow = $surplusResult->fetch_assoc()) {
                $sStk = trim((string)($sRow['stockorder_no'] ?? ''));
                if (empty($sStk) || isset($existingStockNos[$sStk])) continue;
                if (isset($excludedStocks) && isset($excludedStocks[$sStk])) continue;
                // if ($isGrafted) {
                //     $curSo = strtolower(trim((string)($sRow['stockorder_no'] ?? '')));
                //     $curPso = strtolower(trim((string)($sRow['psono'] ?? '')));
                //     if (strpos($curSo, 'rso-') === 0 || strpos($curPso, 'rso-') === 0) {
                //         continue;
                //     }
                // }

                 // $ssq = "SELECT stockorder_no, psono, product_id, product_name FROM krf_provisional_sales_order_items WHERE product_id = ? AND LOWER(TRIM(status)) IN ('cancelled', 'cancel') AND stockorder_no = ?"";
                // $ssqResult = $krfconn->query($ssq);


                $sRow['stkqty'] = (int)$sRow['stkqty'];
                $sRow['ordreadytosaleqty'] = 0;
                $sRow['confirmed_allocated'] = (int)$sRow['confirmed_allocated'];
                $sRow['unconfirmed_allocated'] = (int)$sRow['unconfirmed_allocated'];
                $sRow['base_availableqty'] = (int)$sRow['base_availableqty'];
                $sRow['total_readytosale_qty'] = (int)$sRow['total_readytosale_qty'];
                $sRow['readytosale_qty'] = (int)$sRow['readytosale_qty'];
                $sRow['availableqty'] = (int)$sRow['availableqty'];
                $sRow['is_trader'] = (int)$sRow['is_trader'];
                $sRow['reason'] = 'Surplus After Dispatched';
                $returnarray[] = $sRow;
                $existingStockNos[$sStk] = true;
            }
        }
    }

    // ==========================================
    // CANCELLED ORDER ITEMS WHERE SOWING STARTED
    // If an order item was cancelled from provisional or sales order but sowing has started,
    // active plants in polyhouse or sowing are available as open stock. (Optimized Batch Fetch)
    // Only dedicated client orders (clientid != 'C00001') are checked, and orders already in Open Stock are skipped.
    // ==========================================
    $cItemsQry = "
        SELECT DISTINCT c.stockorder_no, c.psono, c.product_id, c.product_name
        FROM (
            SELECT stockorder_no, psono, product_id, product_name
            FROM krf_sales_order_items
            WHERE product_id = {$productid}
              AND (LOWER(TRIM(status)) IN ('cancelled', 'cancel') OR quantity = 0)  and (tedit is null or tedit='') AND stockorder_no != ''
              {$graft_c_exclude_sql}
            UNION
            SELECT stockorder_no, psono, product_id, product_name
            FROM krf_provisional_sales_order_items
            WHERE product_id = {$productid}
              AND LOWER(TRIM(status)) IN ('cancelled', 'cancel') and (tedit is null or tedit='') AND stockorder_no != ''
              {$graft_c_exclude_sql}
        ) c
        INNER JOIN (
            SELECT DISTINCT stockorder_no 
            FROM krf_polyhouse_sowingplant_details 
            WHERE stockorder_no != ''
        ) sp ON sp.stockorder_no = c.stockorder_no
        INNER JOIN krf_production_stockorder stk 
            ON stk.stockorder_no = c.stockorder_no 
           AND stk.clientid != 'C00001'
    ";
    $cRes = $krfconn->query($cItemsQry);
    $candItems = [];
    $candStocks = [];
    if ($cRes) {
        while ($r = $cRes->fetch_assoc()) {
            $stk = trim($r['stockorder_no']);
            if (empty($stk) || isset($existingStockNos[$stk])) {
                continue; // Skip if already included in Open Stock
            }
            $candItems[$stk] = $r;
            $candStocks[] = "'" . $krfconn->real_escape_string($stk) . "'";
        }
    }

    if (!empty($candStocks)) {
        $candIn = implode(',', array_unique($candStocks));

        // Batch 1: Physical plants in polyhouse
        $pltMap = [];
        $pltRes = $krfconn->query("
            SELECT stockorder_no, SUM(GREATEST(0, COALESCE(qtypertray, 0) - COALESCE(failurecount, 0) - COALESCE(dispatchqty, 0))) as phys_qty
            FROM krf_polyhouse_plant_details
            WHERE  (tedit is null or tedit='') and stockorder_no IN ({$candIn})
              AND LOWER(TRIM(COALESCE(status, ''))) NOT IN ('Internal Dispatch','Clip Stick Removal','Removed','UseForCorrection','Move for Grafting','Empty After Grafting','Empty After Shifting','Tray Dead','Move for Opensale')
            GROUP BY stockorder_no
        ");
        if ($pltRes) {
            while ($pr = $pltRes->fetch_assoc()) {
                $pltMap[trim($pr['stockorder_no'])] = (int)$pr['phys_qty'];
            }
        }

        // Batch 2: Sowing plants fallback if not yet moved to polyhouse
        $sowMap = [];
        $sowRes = $krfconn->query("
            SELECT stockorder_no, SUM(CASE WHEN LOWER(TRIM(COALESCE(status, ''))) NOT IN ('Internal Dispatch','Clip Stick Removal','Removed','UseForCorrection','Move for Grafting','Empty After Grafting','Empty After Shifting','Tray Dead','Move for Opensale') THEN COALESCE(traycavity, 126) ELSE 0 END) as sow_qty
            FROM krf_polyhouse_sowingplant_details
            WHERE stockorder_no IN ({$candIn})
            GROUP BY stockorder_no
        ");
        if ($sowRes) {
            while ($sr = $sowRes->fetch_assoc()) {
                $sowMap[trim($sr['stockorder_no'])] = (int)$sr['sow_qty'];
            }
        }

        // Batch 3: Allocations in krf_productionorder_consumed
        $allocMap = [];
        $allocRes = $krfconn->query("
            SELECT stockorderno,
                SUM(CASE WHEN LOWER(TRIM(COALESCE(confirmstatus, ''))) = 'confirmed' THEN COALESCE(consumed_qty, 0) ELSE 0 END) AS conf_alloc,
                SUM(CASE WHEN LOWER(TRIM(COALESCE(confirmstatus, ''))) <> 'confirmed' THEN COALESCE(consumed_qty, 0) ELSE 0 END) AS unconf_alloc
            FROM krf_productionorder_consumed
            WHERE stockorderno IN ({$candIn})
              AND (tedit IS NULL OR tedit = '')
              AND  {$exclude_consumed_sql}
            GROUP BY stockorderno            
        ");
        // status IN ('pending', 'allocated', 'dispatched')
        if ($allocRes) {
            while ($ar = $allocRes->fetch_assoc()) {
                $allocMap[trim($ar['stockorderno'])] = [
                    'conf' => (int)$ar['conf_alloc'],
                    'unconf' => (int)$ar['unconf_alloc']
                ];
            }
        }

        // Batch 4: Metadata from krf_production_stockorder & client master
        $metaMap = [];
        $metaRes = $krfconn->query("
            SELECT stk.id as stksno, stk.stockorder_no, stk.expected_delivery_date, stk.sowinglocationid, stk.clientid, stk.quantity as prod_order_qty, cl.client_name, cl.is_trader
            FROM krf_production_stockorder stk
            LEFT JOIN krf_client_master cl ON cl.projectid = stk.projectid AND cl.client_id = stk.clientid
            WHERE stk.stockorder_no IN ({$candIn})
        ");
        if ($metaRes) {
            while ($mr = $metaRes->fetch_assoc()) {
                $metaMap[trim($mr['stockorder_no'])] = $mr;
            }
        }

        foreach ($candItems as $stkNo => $item) {
            if (isset($existingStockNos[$stkNo])) continue;

            $availPlants = $pltMap[$stkNo] ?? 0;
            if ($availPlants <= 0) {
                $availPlants = $sowMap[$stkNo] ?? 0;
            }
            if ($availPlants <= 0) continue;

            $confAlloc = $allocMap[$stkNo]['conf'] ?? 0;
            $unconfAlloc = $allocMap[$stkNo]['unconf'] ?? 0;
            $netAvail = $availPlants - $confAlloc;
            if ($netAvail <= 0) continue;

            $meta = $metaMap[$stkNo] ?? [];
            $clientName = trim($meta['client_name'] ?? '');
            if (empty($clientName) && !empty($meta['clientid'])) {
                $clientName = $meta['clientid'];
            }

            $pId = (int)($item['product_id'] ?? 0);
            $pso = trim($item['psono'] ?? '');

            if (isset($excludedStocks) && isset($excludedStocks[(string)$stkNo])) continue;
            if ($isGrafted) {
                $curSo = strtolower(trim((string)$stkNo));
                $curPso = strtolower(trim((string)$pso));
                if (strpos($curSo, 'rso-') === 0 || strpos($curPso, 'rso-') === 0) {
                    continue;
                }
            }

            // Production order details retrieved from krf_production_stockorder
            $prodOrderQty = (int)($meta['prod_order_qty'] ?? 0);
            $orderStkQty = ($prodOrderQty > 0) ? $prodOrderQty : $availPlants;

            $existingStockNos[$stkNo] = true;


            $returnarray[] = [
                'stksno' => (int)($meta['stksno'] ?? 0),
                'projectid' => $projectid,
                'clientid' => $meta['clientid'] ?? '',
                'productid' => $pId,
                'sowinglocationid' => $meta['sowinglocationid'] ?? '',
                'productname' => $item['product_name'] ?? '',
                'stockorder_no' => $stkNo,
                'psono' => $pso,
                'displaydate' => !empty($meta['expected_delivery_date']) ? date('d-m-Y', strtotime($meta['expected_delivery_date'])) : '',
                'expected_delivery_date' => $meta['expected_delivery_date'] ?? '',
                'stkqty' => $orderStkQty,
                'ordreadytosaleqty' => 0,
                'is_trader' => (int)($meta['is_trader'] ?? 0),
                'confirmed_allocated' => $confAlloc,
                'unconfirmed_allocated' => $unconfAlloc,
                'base_availableqty' => $netAvail,
                'total_readytosale_qty' => $availPlants,
                'readytosale_qty' => 0,
                'availableqty' => $netAvail,
                'stock_type' => 'cancelled_order_stock',
                'reason' => 'Final Sale Order Item Removed',
                'client_name' => $clientName
            ];
        }
    }

    // Sort unified return array by expected delivery date, then closest to required qty
    if (!empty($returnarray)) {
        usort($returnarray, function ($a, $b) use ($requiredqty) {
            $dateA = $a['expected_delivery_date'] ?? '';
            $dateB = $b['expected_delivery_date'] ?? '';
            $cmp = strcmp($dateA, $dateB);
            if ($cmp !== 0) return $cmp;
            $diffA = abs(((int)($a['availableqty'] ?? 0)) - $requiredqty);
            $diffB = abs(((int)($b['availableqty'] ?? 0)) - $requiredqty);
            return $diffA <=> $diffB;
        });
    }

    // ==========================================
    // FETCH LOCATION NAMES
    // ==========================================
    $locMap = [];

    if (isset($hrmconn) && ($hrmconn instanceof mysqli)) {

        $locRes = $hrmconn->query("
            SELECT LocationId, LocationName
            FROM hrm_AttendanceLocationMaster
            WHERE projectid = {$projectid}
        ");

        if ($locRes) {
            while ($lr = $locRes->fetch_assoc()) {
                $locMap[trim((string)$lr['LocationId'])] =
                    trim((string)$lr['LocationName']);
            }
        }
    }

    // ==========================================
    // FETCH POLYHOUSE MASTER
    // ==========================================
    $phMap = [];

    $phRes = $krfconn->query("
        SELECT polyhouseid, polyhousename, locationid
        FROM krf_polyhouse_master
        WHERE projectid = {$projectid}
    ");

    if ($phRes) {
        while ($pr = $phRes->fetch_assoc()) {

            $phMap[trim((string)$pr['polyhouseid'])] = [
                'name' => trim((string)$pr['polyhousename']),
                'locationid' => trim((string)$pr['locationid'])
            ];
        }
    }

    // ==========================================
    // GATHER STOCK ORDER NUMBERS
    // ==========================================
    $stockOrderNos = [];

    foreach ($returnarray as $rItem) {
        if (!empty($rItem['stockorder_no'])) {
            $stockOrderNos[] = "'" .
                $krfconn->real_escape_string(
                    $rItem['stockorder_no']
                ) . "'";
        }
    }

    $plantStats = [];
    $orderLocMap = [];

    if (!empty($stockOrderNos)) {

        $soList = implode(',', array_unique($stockOrderNos));

        // ==========================================
        // LOCATION MAPPING PER STOCK ORDER
        // ==========================================
        $locSql = "
            SELECT DISTINCT stockorder_no, locationid
            FROM krf_polyhouse_plant_details
            WHERE projectid = {$projectid}
              AND stockorder_no IN ({$soList})
              AND status IN ('Readytosale', 'Polyhouse')
        ";

        $locRes = $krfconn->query($locSql);

        if ($locRes) {
            while ($lr = $locRes->fetch_assoc()) {

                $so = trim((string)$lr['stockorder_no']);
                $lId = trim((string)$lr['locationid']);

                if (!isset($orderLocMap[$so])) {
                    $orderLocMap[$so] = [];
                }

                $orderLocMap[$so][] = $lId;
            }
        }

        // ==========================================
        // PLANT AND TRAY COUNT
        // ==========================================
        $plSql = "
            SELECT
                stockorder_no,
                locationid,
                polyhouseid,

                COUNT(*) AS tray_count,

                SUM(
                    GREATEST(
                        0,
                        COALESCE(qtypertray, 0)
                        - COALESCE(failurecount, 0)
                    )
                ) AS plant_count

            FROM krf_polyhouse_plant_details

            WHERE projectid = {$projectid}
              AND stockorder_no IN ({$soList})

              AND (
                    LOWER(TRIM(COALESCE(health_status, '')))
                    = 'healthy'
                    OR health_status IS NULL
                    OR TRIM(health_status) = ''
              )

              AND status IN ('Readytosale', 'Polyhouse')  and (tedit is null or tedit='')

            GROUP BY
                stockorder_no,
                locationid,
                polyhouseid
        ";

        $plRes = $krfconn->query($plSql);

        if ($plRes) {
            while ($plr = $plRes->fetch_assoc()) {

                $so = trim((string)$plr['stockorder_no']);

                if (!isset($plantStats[$so])) {
                    $plantStats[$so] = [];
                }

                $plantStats[$so][] = [
                    'locationid' => trim((string)$plr['locationid']),
                    'polyhouseid' => trim((string)$plr['polyhouseid']),
                    'tray_count' => (int)$plr['tray_count'],
                    'plant_count' => (int)$plr['plant_count']
                ];
            }
        }
    }

    // ==========================================
    // ATTACH LOCATION AND POLYHOUSE DETAILS
    // ==========================================
    foreach ($returnarray as &$rItem) {

        $so = trim((string)($rItem['stockorder_no'] ?? ''));

        $stats = $plantStats[$so] ?? [];

        $locNames = [];
        $polyhouseDetails = [];

        $totTrays = 0;
        $totPlants = 0;

        foreach ($stats as $st) {

            $locId = $st['locationid'];
            $phId = $st['polyhouseid'];

            $locName = $locMap[$locId] ?? $locId;

            if ($locName && !in_array($locName, $locNames, true)) {
                $locNames[] = $locName;
            }

            $phName = $phMap[$phId]['name']
                ?? ($phId ? "PH-{$phId}" : 'Polyhouse');

            $polyhouseDetails[] =
                "{$phName}: <strong>{$st['tray_count']} Trays</strong> (" .
                number_format($st['plant_count']) . " Plants)";

            $totTrays += $st['tray_count'];
            $totPlants += $st['plant_count'];
        }

        // Fallback location
        if (empty($locNames)) {

            if (!empty($orderLocMap[$so])) {

                foreach ($orderLocMap[$so] as $lId) {

                    $lName = $locMap[$lId] ?? $lId;

                    if ($lName && !in_array($lName, $locNames, true)) {
                        $locNames[] = $lName;
                    }
                }

            } elseif (!empty($rItem['sowinglocationid'])) {

                $fallbackLocId = trim(
                    (string)$rItem['sowinglocationid']
                );

                $locNames[] = $locMap[$fallbackLocId]
                    ?? $fallbackLocId;
            }
        }

        $rItem['locationname'] = !empty($locNames)
            ? implode(', ', $locNames)
            : 'N/A';

        $rItem['polyhouse_details'] = !empty($polyhouseDetails)
            ? implode('<br>', $polyhouseDetails)
            : '';

        $rItem['total_trays'] = $totTrays;
        $rItem['total_plants'] = $totPlants;
    }

    unset($rItem);

    // ==========================================
    // RSO-MOVEMENT CALCULATION & BALANCE UPDATE
    // If production stockorder starts with 'RSO-' or 'SO-' BUT NOT 'KFP'
    // Consumed count = plants in krf_polyhouse_plant_details where
    // lcase(status) not in('polyhouse','readytosale') and (dispd_agst_psono is null or dispd_agst_psono='') and movementremark<>''
    // Balance formula: ORDER QTY - (CONF. ALLOC + RSO Movement) = balance
    // ==========================================
    $rsoCandidateNos = [];
    foreach ($returnarray as $rItem) {
        $soNo = trim((string)($rItem['stockorder_no'] ?? ''));
        if ((strncasecmp($soNo, 'RSO-', 4) === 0 || strncasecmp($soNo, 'SO-', 3) === 0) && strncasecmp($soNo, 'KFP', 3) !== 0) {
            $rsoCandidateNos[] = "'" . $krfconn->real_escape_string($soNo) . "'";
        }
    }

    $rsoMap = [];
    if (!empty($rsoCandidateNos)) {
        $rsoListStr = implode(',', array_unique($rsoCandidateNos));
        $rsoQry = "
            SELECT
                stockorder_no,
                SUM(GREATEST(0, COALESCE(CAST(qtypertray AS SIGNED), 0) - COALESCE(CAST(failurecount AS SIGNED), 0) - COALESCE(CAST(dispatchqty AS SIGNED), 0))) AS rso_movement_qty
            FROM krf_polyhouse_plant_details
            WHERE (projectid = {$projectid} OR '{$projectid}' = '0' OR projectid = '17')
              AND stockorder_no IN ({$rsoListStr})
              AND LOWER(TRIM(status)) NOT IN ('polyhouse', 'readytosale')
              AND (dispd_agst_psono IS NULL OR TRIM(dispd_agst_psono) = '')
              AND movementremark IS NOT NULL AND TRIM(movementremark) <> ''
            GROUP BY stockorder_no
        ";
        $rsoRes = $krfconn->query($rsoQry);
        if ($rsoRes) {
            while ($rsoR = $rsoRes->fetch_assoc()) {
                $rsoMap[trim($rsoR['stockorder_no'])] = (int)$rsoR['rso_movement_qty'];
            }
        }
    }

    $finalReturnArray = [];
    $seenFinalStockNos = [];
    foreach ($returnarray as &$rItem) {
        $soNo = trim((string)($rItem['stockorder_no'] ?? ''));
        if (empty($soNo) || isset($seenFinalStockNos[$soNo])) {
            continue; // Clean single-row rendering per production order guaranteed
        }
        $seenFinalStockNos[$soNo] = true;

        $isRsoOrSo = (strncasecmp($soNo, 'RSO-', 4) === 0 || strncasecmp($soNo, 'SO-', 3) === 0) && strncasecmp($soNo, 'KFP', 3) !== 0;
        $rsoMvQty = $isRsoOrSo ? ($rsoMap[$soNo] ?? 0) : 0;
        $rItem['rso_movement'] = $rsoMvQty;

        // ORDER QTY - (CONF. ALLOC + RSO Movement) = balance
        $orderQty = (int)($rItem['stkqty'] ?? 0);
        $confAlloc = (int)($rItem['confirmed_allocated'] ?? 0);
        $bal = $orderQty - ($confAlloc + $rsoMvQty);
        $rItem['base_availableqty'] = $bal;
        $rItem['availableqty'] = $bal;

        if ($bal > 0) {
            $finalReturnArray[] = $rItem;
        }
    }
    unset($rItem);
    $returnarray = $finalReturnArray;

    echo json_encode($returnarray);
    exit;
}
else if ($action == "fecthgrowingdate") {
    $productid = (int)($_POST['productid'] ?? 0);
    $projectid = (int)($_POST['projectid'] ?? 0);
    $deliverydate = trim($_POST['deliverydate'] ?? '');
    $bufferdays = 7;

    $returnarray = [];

    /* =====================================
       1️⃣ GET MINIMUM EXPECTED DELIVERY DATE (including standard buffer days)
    ===================================== */ 
    $rrs = getExpectedDeliveryDatesofProduct($krfconn, $projectid, $productid, $bufferdays); 
    if (!$rrs || empty($rrs['deliverydate'])) {
        echo json_encode([]);
        exit;
    }
    $minDeliveryDate = $rrs['deliverydate']; 

    $isEmergency = (
        (isset($_POST['is_emergency']) && in_array(strtolower((string)$_POST['is_emergency']), ['yes', '1', 'true'], true)) ||
        (isset($_POST['is_emergency_sowing']) && in_array(strtolower((string)$_POST['is_emergency_sowing']), ['yes', '1', 'true'], true))
    );

    // Target delivery date: user-selected or default minimum
    if (!empty($deliverydate)) {
        // If not emergency, do not allow delivery date earlier than minimum (which includes buffer)
        $targetDeliveryDate = (!$isEmergency && $deliverydate < $minDeliveryDate) ? $minDeliveryDate : $deliverydate;
    } else {
        $targetDeliveryDate = $minDeliveryDate;
    }

    /* =====================================
       2️⃣ GET SOWING DATE FOR TARGET DELIVERY DATE
    ===================================== */
    $swgdate = getSowingDatesWithSessionCorrection($krfconn, $targetDeliveryDate, $projectid, $productid);
    if (!$swgdate || !isset($swgdate['RootSeedId'])) {
        echo json_encode([]);
        exit;
    }

    $rootsowingdate = $swgdate['RootSeedId']['sowingdate'];
    $scionsowingdate = $swgdate['ScionSeedId']['sowingdate'] ?? $rootsowingdate;
    $today = date('Y-m-d');
    $isBackDate = ($rootsowingdate < $today || $scionsowingdate < $today);

    /* =====================================
       3️⃣ CALCULATE FULL CYCLE DAYS
    ===================================== */
    $fullcycledays = $rrs['growingdays'] + ($rrs['bufferdays'] ?? $bufferdays) + ($rrs['sessiondays'] ?? 0) + ($rrs['aftergraftingdays'] ?? 0);

    /* =====================================
       4️⃣ FINAL RESPONSE
    ===================================== */
    $returnarray[] = [
        'expecteddeliverydate' => $targetDeliveryDate,
        'mindeliverydate' => $minDeliveryDate,
        'sowingdate' => $rootsowingdate,
        'rootsowingdate' => $rootsowingdate,
        'scionsowingdate' => $scionsowingdate,
        'today' => $today,
        'is_backdate' => $isBackDate,
        'bufferdays' => ($rrs['bufferdays'] ?? $bufferdays),
        'fullcycledays' => $fullcycledays,
        'growingdays' => $rrs['growingdays'],
        'aftergraftingdays' => ($rrs['aftergraftingdays'] ?? 0),
        'sessiondays' => ($rrs['sessiondays'] ?? 0)
    ];
    echo json_encode($returnarray);
    exit;
}
else if ($action === "get_stock_allocations") {
    header('Content-Type: application/json');
    $stockorder_no = trim($_POST['stockorder_no'] ?? $_GET['stockorder_no'] ?? '');
    $type = strtolower(trim($_POST['type'] ?? $_GET['type'] ?? 'all'));
    $product_id = intval($_POST['product_id'] ?? $_GET['product_id'] ?? 0);

    if (empty($stockorder_no)) {
        echo json_encode(['status' => false, 'message' => 'Stock Order No is required.', 'data' => []]);
        exit;
    }

    $escStock = $krfconn->real_escape_string($stockorder_no);
    $typeCond = "";
    if ($type === 'confirmed') {
        $typeCond = " AND LOWER(TRIM(COALESCE(c.confirmstatus, ''))) = 'confirmed' ";
    } else if ($type === 'unconfirmed') {
        $typeCond = " AND LOWER(TRIM(COALESCE(c.confirmstatus, ''))) NOT IN ('confirmed', 'cancel', 'cancelled') ";
    }

    $prodCond = ($product_id > 0) ? " AND c.product_id = $product_id " : "";

    $sql = "SELECT 
        c.id AS consumed_id,
        c.psono,
        c.stockorderno,
        c.product_id,
        c.product_name,
        c.consumed_qty,
        c.confirmstatus,
        c.status AS consumed_status,
        c.dispatchedqty,
        DATE_FORMAT(c.dispatchedat, '%d-%m-%Y %H:%i') AS dispatched_at,
        c.remark AS consumed_remark,
        c.created_at AS allocated_at,
        c.created_by AS allocated_by,
        COALESCE(pso.clientid, 'C00001') AS clientid,
        COALESCE(pso.clientname, cl.client_name, 'Karjee Farm') AS clientname,
        COALESCE(pso.mobileno, cl.phones, '-') AS mobileno,
        COALESCE(pso.status, IF(c.psono = 'KF-INTERNAL', 'Internal Consumption', 'Allocated')) AS pso_status,
        DATE_FORMAT(COALESCE(pso.created_at, c.created_at), '%d-%m-%Y') AS order_date,
        DATE_FORMAT(COALESCE(psoi.expecteddeliverydate, pso.expecteddelivery, c.created_at), '%d-%m-%Y') AS expected_delivery_date,
        psoi.id AS psoi_id,
        psoi.quantity AS order_qty,
        psoi.rate AS item_rate,
        psoi.amount AS item_amount,
        psoi.status AS item_status,
        psoi.remark AS item_remark,
        so.status AS so_status,
        so.sono,
        inv.invoicenos,
        inv.inv_status,
        inv.inv_dispatched_plants
    FROM krf_productionorder_consumed c
    LEFT JOIN krf_provisional_sales_order pso 
        ON pso.psono = c.psono
    LEFT JOIN krf_client_master cl 
        ON cl.client_id = pso.clientid
    LEFT JOIN (
        SELECT 
            psono, 
            product_id,
            MIN(id) AS id,
            SUM(quantity) AS quantity,
            MIN(expecteddeliverydate) AS expecteddeliverydate,
            MAX(rate) AS rate,
            SUM(amount) AS amount,
            MAX(status) AS status,
            GROUP_CONCAT(DISTINCT NULLIF(TRIM(remark), '') SEPARATOR '; ') AS remark
        FROM krf_provisional_sales_order_items
        WHERE (status IS NULL OR status = '' OR status != 'Cancelled')
        GROUP BY psono, product_id
    ) psoi 
        ON psoi.psono = c.psono 
       AND psoi.product_id = c.product_id 
    LEFT JOIN (
        SELECT 
            psono,
            MAX(status) AS status,
            GROUP_CONCAT(DISTINCT sono SEPARATOR ', ') AS sono
        FROM krf_sales_order
        GROUP BY psono
    ) so 
        ON so.psono = c.psono
    LEFT JOIN (
        SELECT 
            sii.psono,
            sii.productid,
            GROUP_CONCAT(DISTINCT sii.invoiceno ORDER BY sii.id SEPARATOR ', ') AS invoicenos,
            CASE 
                WHEN SUM(CASE WHEN LOWER(TRIM(COALESCE(sii.status, ''))) = 'dispatched' OR LOWER(TRIM(COALESCE(si.status, ''))) = 'dispatched' THEN 1 ELSE 0 END) > 0 THEN 'dispatched'
                WHEN SUM(CASE WHEN LOWER(TRIM(COALESCE(sii.status, ''))) = 'readytodispatch' OR LOWER(TRIM(COALESCE(si.status, ''))) = 'readytodispatch' THEN 1 ELSE 0 END) > 0 THEN 'readytodispatch'
                WHEN SUM(CASE WHEN LOWER(TRIM(COALESCE(sii.status, ''))) = 'readytoload' OR LOWER(TRIM(COALESCE(si.status, ''))) = 'readytoload' THEN 1 ELSE 0 END) > 0 THEN 'readytoload'
                ELSE MAX(sii.status)
            END AS inv_status,
            SUM(COALESCE(sii.total_dispatch_plants, sii.quantity, 0)) AS inv_dispatched_plants
        FROM sales_invoice_items sii
        LEFT JOIN sales_invoice si ON si.id = sii.invoice_id
        WHERE (sii.status IS NULL OR LOWER(TRIM(sii.status)) != 'cancelled')
          AND (si.status IS NULL OR LOWER(TRIM(si.status)) != 'cancelled')
        GROUP BY sii.psono, sii.productid
    ) inv ON inv.psono = c.psono AND inv.productid = c.product_id
    WHERE c.stockorderno = '$escStock'
      AND LOWER(TRIM(COALESCE(c.status, ''))) IN ('pending', 'allocated', 'dispatched', 'fulldispatch', 'partialdispatch', 'readytoload', 'readytodispatch', 'loaded')
      AND LOWER(TRIM(COALESCE(c.status, ''))) NOT IN ('cancelled', 'cancel')
      AND (c.tedit IS NULL OR c.tedit = '')
      $typeCond
      $prodCond
    GROUP BY c.id
    ORDER BY c.id ASC";

    $res = $krfconn->query($sql);
    if (!$res) {
        echo json_encode(['status' => false, 'message' => $krfconn->error, 'data' => []]);
        exit;
    }

    $allocations = [];
    $totalQty = 0;
    while ($row = $res->fetch_assoc()) {
        $qty = (float)$row['consumed_qty'];
        $totalQty += $qty;
        $row['consumed_qty'] = $qty;
        $row['is_internal'] = ($row['psono'] === 'KF-INTERNAL');

        // Calculate standardized dispatch status
        $cSt = strtolower(trim((string)($row['consumed_status'] ?? '')));
        $soSt = strtolower(trim((string)($row['so_status'] ?? '')));
        $invSt = strtolower(trim((string)($row['inv_status'] ?? '')));
        $dispQty = (float)($row['dispatchedqty'] ?? 0);

        if ($row['is_internal']) {
            $row['dispatch_status'] = 'Internal Farm';
            $row['dispatch_badge_class'] = 'bg-info text-dark';
            $row['is_dispatched'] = false;
        } else if ($cSt === 'dispatched' || $cSt === 'fulldispatch' || $soSt === 'fulldispatch' || $soSt === 'delivered' || $soSt === 'completed' || $invSt === 'dispatched' || ($dispQty > 0 && $dispQty >= $qty)) {
            $row['dispatch_status'] = 'Dispatched';
            $row['dispatch_badge_class'] = 'bg-success text-white';
            $row['is_dispatched'] = true;
        } else if ($cSt === 'partialdispatch' || $soSt === 'partialdispatch' || ($dispQty > 0 && $dispQty < $qty)) {
            $row['dispatch_status'] = 'Partial Dispatch';
            $row['dispatch_badge_class'] = 'bg-warning text-dark';
            $row['is_dispatched'] = false;
        } else if ($cSt === 'readytodispatch' || $invSt === 'readytodispatch') {
            $row['dispatch_status'] = 'Ready to Dispatch';
            $row['dispatch_badge_class'] = 'bg-primary text-white';
            $row['is_dispatched'] = false;
        } else if ($cSt === 'loaded') {
            $row['dispatch_status'] = 'Loaded';
            $row['dispatch_badge_class'] = 'bg-info text-dark';
            $row['is_dispatched'] = false;
        } else if ($cSt === 'readytoload' || $invSt === 'readytoload') {
            $row['dispatch_status'] = 'Ready to Load';
            $row['dispatch_badge_class'] = 'bg-secondary text-white';
            $row['is_dispatched'] = false;
        } else if ($cSt === 'allocated') {
            $row['dispatch_status'] = 'Allocated';
            $row['dispatch_badge_class'] = 'bg-primary-subtle text-primary border border-primary-subtle';
            $row['is_dispatched'] = false;
        } else {
            $row['dispatch_status'] = 'Not Dispatched';
            $row['dispatch_badge_class'] = 'bg-light text-secondary border';
            $row['is_dispatched'] = false;
        }

        $allocations[] = $row;
    }

    echo json_encode([
        'status' => true,
        'stockorder_no' => $stockorder_no,
        'type' => $type,
        'total_qty' => $totalQty,
        'data' => $allocations
    ]);
    exit;
}
else if ($action === "adjust_stock_balance") {
    header('Content-Type: application/json');
    if (session_status() === PHP_SESSION_NONE) { session_start(); }

    $user_role_id = (isset($_SESSION['loginroleid']) && $_SESSION['loginroleid'] !== '' && is_numeric($_SESSION['loginroleid']))
        ? intval($_SESSION['loginroleid'])
        : null;

    if ($user_role_id === null && !empty($_SESSION['loginid'])) {
        $uLoginId = $_SESSION['loginid'];
        $uStmt = $authconn->prepare("SELECT loginroleid FROM login_user_master WHERE loginid = ? LIMIT 1");
        if ($uStmt) {
            $uStmt->bind_param("s", $uLoginId);
            $uStmt->execute();
            $uRes = $uStmt->get_result();
            if ($uRow = $uRes->fetch_assoc()) {
                $user_role_id = intval($uRow['loginroleid']);
                $_SESSION['loginroleid'] = $user_role_id;
            }
            $uStmt->close();
        }
    }

    if ($user_role_id === null && isset($_POST['loginroleid']) && $_POST['loginroleid'] !== '' && is_numeric($_POST['loginroleid'])) {
        $user_role_id = intval($_POST['loginroleid']);
    }

    if ($user_role_id === null) {
        $user_role_id = 99;
    }

    if ($user_role_id > 3) {
        echo json_encode(['status' => false, 'message' => 'Access Denied: Only Admin roles (Role <= 3) can adjust stock balance.']);
        exit;
    }

    $stockorder_no = trim($_POST['stockorder_no'] ?? '');
    $product_id = intval($_POST['product_id'] ?? 0);
    $product_name = trim($_POST['product_name'] ?? '');
    $projectid = intval($_POST['projectid'] ?? 1);
    $current_balance = intval($_POST['current_balance'] ?? 0);
    $new_balance = intval($_POST['new_balance'] ?? 0);
    $remark = trim($_POST['remark'] ?? '');

    if (empty($stockorder_no)) {
        echo json_encode(['status' => false, 'message' => 'Stock Order No is required.']);
        exit;
    }
    if (empty($remark)) {
        echo json_encode(['status' => false, 'message' => 'Correction remark is mandatory.']);
        exit;
    }
    if ($new_balance < 0) {
        echo json_encode(['status' => false, 'message' => 'New balance cannot be negative.']);
        exit;
    }

    // Lookup product name if not provided
    if (empty($product_name) && $product_id > 0) {
        $pRes = $krfconn->query("SELECT ProductName FROM product_master WHERE ProductID = $product_id LIMIT 1");
        if ($pRes && $pRow = $pRes->fetch_assoc()) {
            $product_name = $pRow['ProductName'];
        }
    }

    $loginname = $_SESSION['loginname'] ?? $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'Admin';
    $esc_so = $krfconn->real_escape_string($stockorder_no);
    $esc_remark = $krfconn->real_escape_string($remark);
    $esc_login = $krfconn->real_escape_string($loginname);

    if ($new_balance === $current_balance) {
        // Balance verified without change - record remark and audit
        $krfconn->query("UPDATE krf_production_stockorder 
            SET editedqty = {$new_balance},
                editedremark = CONCAT(COALESCE(editedremark, ''), ' | Verified count {$new_balance}: {$esc_remark}'),
                editedby = '{$esc_login}',
                editedon = NOW()
            WHERE stockorder_no = '{$esc_so}' AND projectid = {$projectid}");

        echo json_encode([
            'status' => true,
            'message' => "Stock balance verified and recorded as " . number_format($new_balance) . " plants.",
            'diff_qty' => 0,
            'new_balance' => $new_balance
        ]);
        exit;
    }

    if ($new_balance < $current_balance) {
        // CASE A: REDUCING BALANCE
        $lessed_qty = $current_balance - $new_balance;
        $fullRemark = "Consumed as Karjee Farm (Balance Correction): " . $remark;

        $stmt = $krfconn->prepare("INSERT INTO krf_productionorder_consumed 
            (psono, stockorderno, product_id, product_name, consumed_qty, projectid, created_by, created_at, updated_at, confirmstatus, remark, status)
            VALUES ('KF-INTERNAL', ?, ?, ?, ?, ?, ?, NOW(), NOW(), 'Confirmed', ?, 'allocated')");

        if (!$stmt) {
            echo json_encode(['status' => false, 'message' => 'Prepare failed: ' . $krfconn->error]);
            exit;
        }

        $stmt->bind_param("sisisss", $stockorder_no, $product_id, $product_name, $lessed_qty, $projectid, $loginname, $fullRemark);
        if ($stmt->execute()) {
            $insertId = $stmt->insert_id;
            $stmt->close();

            // Traceability in krf_production_stockorder
            $krfconn->query("UPDATE krf_production_stockorder 
                SET editedqty = {$new_balance},
                    editedremark = CONCAT(COALESCE(editedremark, ''), ' | Reduced by {$lessed_qty} to {$new_balance}: {$esc_remark}'),
                    editedby = '{$esc_login}',
                    editedon = NOW()
                WHERE stockorder_no = '{$esc_so}' AND projectid = {$projectid}");

            echo json_encode([
                'status' => true,
                'message' => "Stock balance successfully updated from " . number_format($current_balance) . " to " . number_format($new_balance) . ". Difference of " . number_format($lessed_qty) . " plants consumed as Karjee Farm.",
                'inserted_id' => $insertId,
                'diff_qty' => -$lessed_qty,
                'new_balance' => $new_balance
            ]);
        } else {
            $err = $stmt->error;
            $stmt->close();
            echo json_encode(['status' => false, 'message' => 'Insert error: ' . $err]);
        }
        exit;
    } else {
        // CASE B: INCREASING BALANCE ($new_balance > $current_balance)
        $diff = $new_balance - $current_balance;
        $remaining_diff = $diff;

        // 1. If existing KF-INTERNAL consumption records exist for this stock order, release/reduce them first
        $kfRes = $krfconn->query("SELECT id, consumed_qty FROM krf_productionorder_consumed 
            WHERE stockorderno = '{$esc_so}' AND psono = 'KF-INTERNAL' AND status IN ('pending', 'allocated', 'dispatched')
            ORDER BY id DESC");

        if ($kfRes && $kfRes->num_rows > 0) {
            while ($kfRow = $kfRes->fetch_assoc()) {
                if ($remaining_diff <= 0) break;
                $kfId = intval($kfRow['id']);
                $cQty = intval($kfRow['consumed_qty']);

                if ($cQty <= $remaining_diff) {
                    // $krfconn->query("DELETE FROM krf_productionorder_consumed WHERE id = {$kfId}");
                    $newConsumedQty = $cQty - $remaining_diff;
                    $krfconn->query("UPDATE krf_productionorder_consumed SET consumed_qty = {$newConsumedQty}, updated_at = NOW() WHERE id = {$kfId}");
                    $remaining_diff -= $cQty;
                } else {
                    $newConsumedQty = $cQty - $remaining_diff;
                    $krfconn->query("UPDATE krf_productionorder_consumed SET consumed_qty = {$newConsumedQty}, updated_at = NOW() WHERE id = {$kfId}");
                    $remaining_diff = 0;
                }
            }
        }

        // 2. Remaining increase is added directly to quantity and sowingbalqty in krf_production_stockorder
        if ($remaining_diff > 0) {
            $krfconn->query("UPDATE krf_production_stockorder 
                SET quantity = quantity + {$remaining_diff},
                    sowingbalqty = GREATEST(0, sowingbalqty + {$remaining_diff}),
                    editedqty = {$new_balance},
                    editedremark = CONCAT(COALESCE(editedremark, ''), ' | Increased by {$diff} to {$new_balance}: {$esc_remark}'),
                    editedby = '{$esc_login}',
                    editedon = NOW()
                WHERE stockorder_no = '{$esc_so}' AND projectid = {$projectid}");
        } else {
            $krfconn->query("UPDATE krf_production_stockorder 
                SET editedqty = {$new_balance},
                    editedremark = CONCAT(COALESCE(editedremark, ''), ' | Increased by {$diff} to {$new_balance} (reversed KF-INTERNAL): {$esc_remark}'),
                    editedby = '{$esc_login}',
                    editedon = NOW()
                WHERE stockorder_no = '{$esc_so}' AND projectid = {$projectid}");
        }

        echo json_encode([
            'status' => true,
            'message' => "Stock balance successfully updated from " . number_format($current_balance) . " to " . number_format($new_balance) . " (+ " . number_format($diff) . " plants).",
            'diff_qty' => $diff,
            'new_balance' => $new_balance
        ]);
        exit;
    }
}
else if ($action === "check_removal_impact") {
    header('Content-Type: application/json');
    if (session_status() === PHP_SESSION_NONE) { session_start(); }

    $consumed_id = intval($_POST['consumed_id'] ?? 0);
    $psoi_id = intval($_POST['psoi_id'] ?? 0);
    $psono = trim($_POST['psono'] ?? '');
    $product_id = intval($_POST['product_id'] ?? 0);
    $stockorder_no = trim($_POST['stockorder_no'] ?? '');

    if ($psono === 'KF-INTERNAL' || ($consumed_id > 0 && empty($psono))) {
        echo json_encode([
            'status' => true,
            'is_internal' => true,
            'is_full_removal' => false,
            'total_paid' => 0
        ]);
        exit;
    }

    $escPsono = $krfconn->real_escape_string($psono);

    // 1. Check Dispatch Status
    if ($consumed_id > 0) {
        $chkDisp = $krfconn->query("SELECT status, dispatchedqty FROM krf_productionorder_consumed WHERE id = $consumed_id LIMIT 1");
        if ($chkDisp && $dRow = $chkDisp->fetch_assoc()) {
            $st = strtolower(trim((string)$dRow['status']));
            if (in_array($st, ['dispatched', 'fulldispatch']) || (float)($dRow['dispatchedqty'] ?? 0) > 0) {
                echo json_encode([
                    'status' => false,
                    'is_dispatched' => true,
                    'message' => "Cannot remove: This order allocation has already been dispatched."
                ]);
                exit;
            }
        }
    }

    // 2. Check Sowing Status
    if ($psoi_id > 0) {
        $itemQ = $krfconn->query("SELECT id, product_name, stockorder_no, psono FROM krf_provisional_sales_order_items WHERE id = $psoi_id LIMIT 1");
        if ($itemQ && $itemInfo = $itemQ->fetch_assoc()) {
            $chkStk = trim($itemInfo['stockorder_no'] ?? '');
            $orderPsono = trim($itemInfo['psono'] ?? $psono);
            if (!empty($chkStk)) {
                $stkQ = $krfconn->query("SELECT psono, quantity, sowingstatus, workingstatus FROM krf_production_stockorder WHERE stockorder_no = '{$krfconn->real_escape_string($chkStk)}' LIMIT 1");
                if ($stkQ && $sRow = $stkQ->fetch_assoc()) {
                    $isSelf = (!empty($sRow['psono']) && strcasecmp(trim($sRow['psono']), $orderPsono) === 0);
                    $swStatus = strtolower(trim($sRow['sowingstatus'] ?? ''));
                    $wStatus = strtolower(trim($sRow['workingstatus'] ?? ''));
                    if ($isSelf && (in_array($swStatus, ['start', 'sowed', 'done']) || in_array($wStatus, ['polyhouse', 'final dispatch']))) {
                        echo json_encode([
                            'status' => false,
                            'is_locked' => true,
                            'message' => "Cannot remove product '{$itemInfo['product_name']}': Sowing has already started ({$sRow['sowingstatus']}). Sown items cannot be removed."
                        ]);
                        exit;
                    }
                }
            }
        }
    }

    // 3. Count active items in krf_provisional_sales_order_items
    $targetItemId = $psoi_id;
    if ($targetItemId <= 0 && !empty($psono) && $product_id > 0) {
        $tQ = $krfconn->query("SELECT id FROM krf_provisional_sales_order_items WHERE psono = '$escPsono' AND product_id = $product_id AND (status IS NULL OR status = '' OR status != 'Cancelled') LIMIT 1");
        if ($tQ && $tRow = $tQ->fetch_assoc()) {
            $targetItemId = (int)$tRow['id'];
        }
    }

    $activeQ = $krfconn->query("SELECT id, product_name, quantity, rate, amount FROM krf_provisional_sales_order_items WHERE psono = '$escPsono' AND (status IS NULL OR status = '' OR status != 'Cancelled')");
    $totalActiveItems = 0;
    $remainingActiveItems = 0;
    $targetItemName = '';
    $targetItemQty = 0;
    $targetItemAmount = 0;

    if ($activeQ) {
        while ($aRow = $activeQ->fetch_assoc()) {
            $totalActiveItems++;
            if ($targetItemId > 0 && (int)$aRow['id'] === $targetItemId) {
                $targetItemName = $aRow['product_name'];
                $targetItemQty = (float)$aRow['quantity'];
                $targetItemAmount = (float)$aRow['amount'];
            } else {
                $remainingActiveItems++;
            }
        }
    }

    $isFullRemoval = ($remainingActiveItems === 0);

    // 4. Fetch linked SO No
    $sono = '';
    $soQ = $krfconn->query("SELECT sono FROM krf_sales_order WHERE psono = '$escPsono' LIMIT 1");
    if ($soQ && $soRow = $soQ->fetch_assoc()) {
        $sono = trim($soRow['sono'] ?? '');
    }

    // 5. Calculate Payment Received
    $escSono = $krfconn->real_escape_string($sono);
    $payQ = $krfconn->query("SELECT IFNULL(SUM(advanceamount), 0) AS total_paid FROM advance_payment_transactions WHERE (invoiceno = '$escPsono' OR (invoiceno != '' AND invoiceno = '$escSono')) AND (tedit IS NULL OR tedit = '')");
    $totalPaid = ($payQ && $pRow = $payQ->fetch_assoc()) ? (float)$pRow['total_paid'] : 0.00;

    // 6. Fetch Order Header details
    $hdrQ = $krfconn->query("SELECT clientid, clientname, grandtotal, created_at FROM krf_provisional_sales_order WHERE psono = '$escPsono' LIMIT 1");
    $hdr = ($hdrQ && $hRow = $hdrQ->fetch_assoc()) ? $hRow : [];

    echo json_encode([
        'status' => true,
        'is_internal' => false,
        'psono' => $psono,
        'sono' => $sono,
        'clientid' => $hdr['clientid'] ?? '',
        'clientname' => $hdr['clientname'] ?? 'Client',
        'order_date' => !empty($hdr['created_at']) ? date('d-m-Y', strtotime($hdr['created_at'])) : '',
        'grandtotal' => (float)($hdr['grandtotal'] ?? 0),
        'total_paid' => $totalPaid,
        'total_active_items' => $totalActiveItems,
        'remaining_active_items' => $remainingActiveItems,
        'is_full_removal' => $isFullRemoval,
        'target_item_name' => $targetItemName,
        'target_item_qty' => $targetItemQty,
        'target_item_amount' => $targetItemAmount
    ]);
    exit;
}
else if ($action === "remove_stock_allocation") {
    header('Content-Type: application/json');
    if (session_status() === PHP_SESSION_NONE) { session_start(); }

    $user_role_id = (isset($_SESSION['loginroleid']) && $_SESSION['loginroleid'] !== '' && is_numeric($_SESSION['loginroleid']))
        ? intval($_SESSION['loginroleid'])
        : null;

    if ($user_role_id === null && !empty($_SESSION['loginid'])) {
        $uLoginId = $_SESSION['loginid'];
        $uStmt = $authconn->prepare("SELECT loginroleid FROM login_user_master WHERE loginid = ? LIMIT 1");
        if ($uStmt) {
            $uStmt->bind_param("s", $uLoginId);
            $uStmt->execute();
            $uRes = $uStmt->get_result();
            if ($uRow = $uRes->fetch_assoc()) {
                $user_role_id = intval($uRow['loginroleid']);
                $_SESSION['loginroleid'] = $user_role_id;
            }
            $uStmt->close();
        }
    }

    if ($user_role_id === null && isset($_POST['loginroleid']) && $_POST['loginroleid'] !== '' && is_numeric($_POST['loginroleid'])) {
        $user_role_id = intval($_POST['loginroleid']);
    }

    if ($user_role_id === null) {
        $user_role_id = 99;
    }

    if ($user_role_id > 3) {
        echo json_encode(['status' => false, 'message' => 'Access Denied: Only Admin roles (Role <= 3) can remove allocations.']);
        exit;
    }

    $consumed_id = intval($_POST['consumed_id'] ?? 0);
    $psoi_id = intval($_POST['psoi_id'] ?? 0);
    $psono = trim($_POST['psono'] ?? '');
    $product_id = intval($_POST['product_id'] ?? 0);
    $stockorder_no = trim($_POST['stockorder_no'] ?? '');
    $remark = trim($_POST['remark'] ?? '');
    $financial_action = trim($_POST['financial_action'] ?? 'credit_note');
    $projectid = intval($_POST['projectid'] ?? 1);

    if (empty($remark)) {
        echo json_encode(['status' => false, 'message' => 'Cancellation remark is mandatory.']);
        exit;
    }

    $loginname = $_SESSION['loginname'] ?? $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'Admin';
    $userForUpdate = substr((string)$loginname, 0, 45);
    $now = date('Y-m-d H:i:s');
    $curdate = date('Y-m-d');
    $escRemark = $krfconn->real_escape_string($remark);

    // Case 1: Internal Karjee Farm consumption
    if ($psono === 'KF-INTERNAL' || ($consumed_id > 0 && empty($psono))) {
        $cancelRemark = "Cancelled by " . $userForUpdate . " on " . $now . " - Reason: " . $remark;
        $escCancel = $krfconn->real_escape_string($cancelRemark);
        $res = $krfconn->query("UPDATE krf_productionorder_consumed 
            SET confirmstatus = 'cancelled', 
                status = 'Cancelled', 
                remark = IF(remark IS NULL OR remark = '', '$escCancel', CONCAT(remark, ' | ', '$escCancel')), 
                updated_at = NOW() 
            WHERE id = $consumed_id");

        if ($res) {
            echo json_encode(['status' => true, 'is_full_removal' => false, 'message' => 'Internal Karjee Farm consumption cancelled successfully. Balance restored.']);
        } else {
            echo json_encode(['status' => false, 'message' => 'Failed to cancel internal consumption: ' . $krfconn->error]);
        }
        exit;
    }

    // Case 2: Regular provisional sales order item
    try {
        // DISPATCH VALIDATION: If allocation is already dispatched, block removal
        if ($consumed_id > 0) {
            $chkDisp = $krfconn->query("SELECT status, dispatchedqty FROM krf_productionorder_consumed WHERE id = $consumed_id LIMIT 1");
            if ($chkDisp && $dRow = $chkDisp->fetch_assoc()) {
                $st = strtolower(trim((string)$dRow['status']));
                if (in_array($st, ['dispatched', 'fulldispatch']) || (float)($dRow['dispatchedqty'] ?? 0) > 0) {
                    echo json_encode(['status' => false, 'message' => "Cannot remove product: This order allocation has already been dispatched."]);
                    exit;
                }
            }
        }

        // SOWING VALIDATION: If psoi_id exists, check if sowing started for own stock
        if ($psoi_id > 0) {
            $itemQ = $krfconn->query("SELECT id, product_name, stockorder_no, psono FROM krf_provisional_sales_order_items WHERE id = $psoi_id LIMIT 1");
            if ($itemQ && $itemInfo = $itemQ->fetch_assoc()) {
                $chkStk = trim($itemInfo['stockorder_no'] ?? '');
                $orderPsono = trim($itemInfo['psono'] ?? $psono);
                if (!empty($chkStk)) {
                    $stkQ = $krfconn->query("SELECT psono, quantity, sowingstatus, workingstatus FROM krf_production_stockorder WHERE stockorder_no = '{$krfconn->real_escape_string($chkStk)}' LIMIT 1");
                    if ($stkQ && $sRow = $stkQ->fetch_assoc()) {
                        $isSelf = (!empty($sRow['psono']) && strcasecmp(trim($sRow['psono']), $orderPsono) === 0);
                        $swStatus = strtolower(trim($sRow['sowingstatus'] ?? ''));
                        $wStatus = strtolower(trim($sRow['workingstatus'] ?? ''));
                        if ($isSelf && (in_array($swStatus, ['start', 'sowed', 'done']) || in_array($wStatus, ['polyhouse', 'final dispatch']))) {
                            echo json_encode([
                                'status' => false,
                                'message' => "Cannot remove product '{$itemInfo['product_name']}': Sowing has already started ({$sRow['sowingstatus']}). Sown items cannot be removed."
                            ]);
                            exit;
                        }
                    }
                }
            }
        }

        $teditText = substr("Cancelled by " . $userForUpdate . " on " . $now . " - Reason: " . $remark, 0, 255);
        $escTedit = $krfconn->real_escape_string($teditText);
        $escPsono = $krfconn->real_escape_string($psono);
        $escStock = $krfconn->real_escape_string($stockorder_no);

        // 1. Update krf_provisional_sales_order_items if psoi_id available, or by psono + product_id
        if ($psoi_id > 0) {
            $krfconn->query("UPDATE krf_provisional_sales_order_items 
                SET status = 'Cancelled', 
                    tedit = '$escTedit', 
                    remark = IF(remark IS NULL OR remark = '', '$escRemark', CONCAT(remark, ' | Cancelled: ', '$escRemark')), 
                    updatedat = '$curdate', 
                    updatedby = '$userForUpdate' 
                WHERE id = $psoi_id");
        } else if (!empty($psono) && $product_id > 0) {
            $krfconn->query("UPDATE krf_provisional_sales_order_items 
                SET status = 'Cancelled', 
                    tedit = '$escTedit', 
                    remark = IF(remark IS NULL OR remark = '', '$escRemark', CONCAT(remark, ' | Cancelled: ', '$escRemark')), 
                    updatedat = '$curdate', 
                    updatedby = '$userForUpdate' 
                WHERE psono = '$escPsono' AND product_id = '$product_id'");
        }

        // 2. Update krf_productionorder_consumed
        $consumedWhere = ($consumed_id > 0) ? "id = $consumed_id" : "psono = '$escPsono' AND product_id = '$product_id' AND stockorderno = '$escStock'";
        $krfconn->query("UPDATE krf_productionorder_consumed 
            SET confirmstatus = 'cancelled', 
                status = 'Cancelled', 
                remark = IF(remark IS NULL OR remark = '', '$escRemark', CONCAT(remark, ' | Cancelled: ', '$escRemark')), 
                updated_at = NOW() 
            WHERE $consumedWhere");

        // 3. Update krf_sales_order_items
        if (!empty($psono) && $product_id > 0) {
            $krfconn->query("UPDATE krf_sales_order_items 
                SET status = 'Cancelled', 
                    remark = IF(remark IS NULL OR remark = '', '$escRemark', CONCAT(remark, ' | Cancelled: ', '$escRemark')), 
                    ammendedby = '$userForUpdate', 
                    updateamended = NOW() 
                WHERE psono = '$escPsono' AND product_id = '$product_id'");
        }

        // 4. Update krf_polyhouse_sowingschedule
        if (!empty($psono) && $product_id > 0) {
            $krfconn->query("UPDATE krf_polyhouse_sowingschedule 
                SET status = 'removed', 
                    remarks = IF(remarks IS NULL OR remarks = '', '$escRemark', CONCAT(remarks, ' | Cancelled: ', '$escRemark')), 
                    updated_by = '$userForUpdate', 
                    updated_at = NOW() 
                WHERE psono = '$escPsono' AND productid = '$product_id'");
        }

        // 5. Check if all items in krf_provisional_sales_order_items are now cancelled
        $isFullOrderCancellation = false;
        $financialMessage = '';

        if (!empty($psono)) {
            $chk = $krfconn->query("SELECT COUNT(*) AS active_cnt FROM krf_provisional_sales_order_items WHERE psono = '$escPsono' AND (status IS NULL OR status = '' OR status != 'Cancelled')");
            $activeCnt = ($chk && $r = $chk->fetch_assoc()) ? (int)$r['active_cnt'] : 0;
            if ($activeCnt === 0) {
                $isFullOrderCancellation = true;

                // Check payment received against psono / sono
                $sono = '';
                $soQ = $krfconn->query("SELECT sono FROM krf_sales_order WHERE psono = '$escPsono' LIMIT 1");
                if ($soQ && $soRow = $soQ->fetch_assoc()) {
                    $sono = trim($soRow['sono'] ?? '');
                }
                $escSono = $krfconn->real_escape_string($sono);

                $amount_paid = 0.00;
                $payQ = $krfconn->query("SELECT IFNULL(SUM(advanceamount), 0) AS total_paid FROM advance_payment_transactions WHERE (invoiceno = '$escPsono' OR (invoiceno != '' AND invoiceno = '$escSono')) AND (tedit IS NULL OR tedit = '')");
                if ($payQ && $payRow = $payQ->fetch_assoc()) {
                    $amount_paid = floatval($payRow['total_paid'] ?? 0);
                }

                if ($amount_paid > 0) {
                    $psoHdrRes = $krfconn->query("SELECT clientid, clientname, companyid, subcompanyid, projectid FROM krf_provisional_sales_order WHERE psono = '$escPsono' LIMIT 1");
                    $psoHdr = $psoHdrRes ? $psoHdrRes->fetch_assoc() : [];
                    $clId = $psoHdr['clientid'] ?? '';
                    $clName = $psoHdr['clientname'] ?? '';
                    $comp = (int)($psoHdr['companyid'] ?? 0);
                    $subcomp = (int)($psoHdr['subcompanyid'] ?? 0);
                    $proj = (int)($psoHdr['projectid'] ?? $projectid);

                    if ($financial_action === 'refund') {
                        // Return Cash / Refund
                        $refRemarks = "Return Cash against full cancellation of Provisional Order #" . $psono . " - Reason: " . $remark;
                        $negAmt = -$amount_paid;
                        $stmtRef = $krfconn->prepare("INSERT INTO advance_payment_transactions (
                            companyid, subcompanyid, projectid, clientid, clientname, advanceamount, paymentdate, purposeofpayment, invoiceno, sourceofpayment, receivedby, remarks, transaction_type, allocated_datetime, verification
                        ) VALUES (?, ?, ?, ?, ?, ?, CURDATE(), 'refund', ?, 'Cash', ?, ?, 'Refund', NOW(), 'verified')");
                        if ($stmtRef) {
                            $stmtRef->bind_param("iiissdsss", $comp, $subcomp, $proj, $clId, $clName, $negAmt, $psono, $userForUpdate, $refRemarks);
                            $stmtRef->execute();
                            $stmtRef->close();
                        }

                        $stmtStmt = $krfconn->prepare("INSERT INTO krf_tbl_client_statement (
                            project_id, client_id, txn_date, txn_type, txn_no, description, debit_amount, credit_amount, running_balance, created_at
                        ) VALUES (?, ?, CURDATE(), 'WRITE_OFF', ?, ?, ?, 0.00, 0.00, NOW())");
                        if ($stmtStmt) {
                            $stmtStmt->bind_param("isssd", $proj, $clId, $psono, $refRemarks, $amount_paid);
                            $stmtStmt->execute();
                            $stmtStmt->close();
                        }
                        $financialMessage = " Return Cash / Refund of ₹" . number_format($amount_paid, 2) . " has been recorded for the customer.";
                    } else {
                        // Issue Credit Note
                        $cnRes = $krfconn->query("SELECT MAX(creditnote_id) AS max_id FROM krf_creditnote");
                        $nextId = 1;
                        if ($cnRes && $cnRow = $cnRes->fetch_assoc()) {
                            $nextId = intval($cnRow['max_id'] ?? 0) + 1;
                        }
                        $creditnote_no = "CN-" . date('Y') . "-" . str_pad((string)$nextId, 5, '0', STR_PAD_LEFT);
                        $narration = "Credit Note of Rs. " . number_format($amount_paid, 2) . " (Full Cancellation) for Client: " . ($clName ?: $clId) . " against cancelled Provisional Order " . $psono . (!empty($sono) ? ", SO: " . $sono : "") . ". Advance payment credited to client ledger. Reason: " . $remark . ". Processed by " . $loginname . " on " . date('d-m-Y H:i:s') . ".";

                        $stmtCn = $krfconn->prepare("INSERT INTO krf_creditnote (
                            creditnote_no, creditnote_date, projectid, companyid, subcompanyid,
                            clientid, clientname, psono, sono, stockorder_no, amount_paid, credit_amount, narration, reason, status, created_by, created_at, updated_at
                        ) VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?, NOW(), NOW())");
                        if ($stmtCn) {
                            $stmtCn->bind_param("siiisssssddsss", $creditnote_no, $proj, $comp, $subcomp, $clId, $clName, $psono, $sono, $stockorder_no, $amount_paid, $amount_paid, $narration, $remark, $loginname);
                            $stmtCn->execute();
                            $stmtCn->close();
                        }

                        $stmtStmt = $krfconn->prepare("INSERT INTO krf_tbl_client_statement (
                            project_id, client_id, txn_date, txn_type, txn_no, description, debit_amount, credit_amount, running_balance, created_at
                        ) VALUES (?, ?, CURDATE(), 'CREDIT_NOTE', ?, ?, 0.00, ?, 0.00, NOW())");
                        if ($stmtStmt) {
                            $stmtStmt->bind_param("isssd", $proj, $clId, $creditnote_no, $narration, $amount_paid);
                            $stmtStmt->execute();
                            $stmtStmt->close();
                        }
                        $financialMessage = " Credit Note (" . $creditnote_no . ") of ₹" . number_format($amount_paid, 2) . " has been issued and credited to client ledger.";
                    }
                }

                $autoRemark = "All items cancelled: " . $remark . (!empty($financialMessage) ? " [" . trim($financialMessage) . "]" : "");
                $escAuto = $krfconn->real_escape_string($autoRemark);
                $krfconn->query("UPDATE krf_provisional_sales_order 
                    SET status = 'Cancelled', 
                        remarks = IF(remarks IS NULL OR remarks = '', '$escAuto', CONCAT(remarks, ' | ', '$escAuto')), 
                        updated_by = '$userForUpdate', 
                        updated_at = NOW() 
                    WHERE psono = '$escPsono'");

                $krfconn->query("UPDATE krf_sales_order 
                    SET status = 'Cancelled', 
                        remarks = IF(remarks IS NULL OR remarks = '', '$escAuto', CONCAT(remarks, ' | ', '$escAuto')), 
                        updated_by = '$userForUpdate', 
                        updated_at = NOW() 
                    WHERE psono = '$escPsono'");
            }
        }

        if ($isFullOrderCancellation) {
            echo json_encode([
                'status' => true,
                'is_full_removal' => true,
                'message' => "Provisional order $psono has been fully cancelled." . $financialMessage
            ]);
        } else {
            echo json_encode([
                'status' => true,
                'is_full_removal' => false,
                'message' => "Product allocation removed from order $psono successfully."
            ]);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

?>