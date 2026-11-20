<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set("Asia/Calcutta");

// -------------------------------------------------------------------------
// Check whether page is loaded via framework menu (?k=...) or directly
// -------------------------------------------------------------------------
$hasKey = isset($_GET['k']) && !empty($_GET['k']);
if ($hasKey) {
    require('../header.php');
} else {
    require_once(__DIR__ . '/../connection.php');
    if (file_exists(__DIR__ . '/../loginCell/functions/login_functions.php')) {
        include_once(__DIR__ . '/../loginCell/functions/login_functions.php');
    }
}

$loginid      = $_SESSION['loginid'] ?? $_SESSION['userid'] ?? 'admin';
$loginname    = $_SESSION['loginname'] ?? $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'Admin';
$loginroleid  = (int)($_SESSION['loginroleid'] ?? 1);
$companyid    = (int)($_SESSION['companyid'] ?? 1);
$subcompanyid = (int)($_SESSION['subcompanyid'] ?? 1);
$projectid    = (int)($_SESSION['projectid'] ?? 17);
if ($projectid <= 0) $projectid = 17;

// -------------------------------------------------------------------------
// AJAX ENDPOINTS FOR OPEN STOCK DATA
// -------------------------------------------------------------------------
if (isset($_REQUEST['ajax_action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $ajaxAction = trim($_REQUEST['ajax_action']);

    // 1. Fetch List of Products that have Open Stock
    if ($ajaxAction === 'get_products_list') {
        $filterTrader = trim($_REQUEST['trader_type'] ?? 'all');

        $traderFilterSql = "";
        if ($filterTrader === 'trader') {
            $traderFilterSql = " AND (stk.clientid = 'C00001' OR COALESCE(cl.is_trader, 0) = 1) ";
        } elseif ($filterTrader === 'farmer') {
            $traderFilterSql = " AND stk.clientid != 'C00001' AND COALESCE(cl.is_trader, 0) != 1 ";
        }

        $prodSql = "
            SELECT 
                p.ProductID AS productid,
                p.ProductName AS productname,
                COUNT(DISTINCT stk.id) AS order_count,
                SUM(COALESCE(ph.total_ph_plants, stk.quantity, 0)) AS total_qty
            FROM krf_production_stockorder stk
            LEFT JOIN krf_client_master cl ON cl.projectid = stk.projectid AND cl.client_id = stk.clientid
            LEFT JOIN (
                SELECT 
                    stockorder_no,
                    SUM(GREATEST(0, COALESCE(CAST(qtypertray AS SIGNED), 0) - COALESCE(CAST(failurecount AS SIGNED), 0) - COALESCE(CAST(dispatchqty AS SIGNED), 0))) AS total_ph_plants
                FROM krf_polyhouse_plant_details
                WHERE (tedit IS NULL OR tedit = '')
                  AND LOWER(TRIM(COALESCE(status, ''))) NOT IN (
                      'clip stick removal', 'move for grafting', 'empty after grafting', 
                      'empty after shifting', 'remove after over age', 'tray dead', 
                      'removed', 'useforcorrection', 'internal dispatch', 'move for opensale'
                  )
                GROUP BY stockorder_no
                HAVING total_ph_plants > 0
            ) ph ON ph.stockorder_no = stk.stockorder_no
            INNER JOIN product_master p ON p.ProductID = stk.productid
            WHERE (stk.projectid = {$projectid} OR '{$projectid}' = '0' OR stk.projectid = '17')
              AND stk.status IN ('Confirmed', 'confirmed')
              AND LOWER(TRIM(COALESCE(stk.status, ''))) NOT IN ('cancelled', 'cancel', 'remove', 'removed')
              AND LOWER(TRIM(COALESCE(stk.workingstatus, ''))) NOT IN ('cancelled', 'cancel', 'remove', 'removed')
              {$traderFilterSql}
            GROUP BY p.ProductID, p.ProductName
            ORDER BY p.ProductName ASC
        ";
        $pRes = $krfconn->query($prodSql);
        $products = [];
        if ($pRes) {
            while ($pRow = $pRes->fetch_assoc()) {
                $products[] = [
                    'productid'   => (int)$pRow['productid'],
                    'productname' => $pRow['productname'],
                    'order_count' => (int)$pRow['order_count'],
                    'total_qty'   => (int)$pRow['total_qty']
                ];
            }
        }
        echo json_encode(['status' => true, 'data' => $products]);
        exit;
    }

    // 2. Fetch Open Stock Data (Filtered by Product, Location, Trader/Farmer, Date)
    if ($ajaxAction === 'get_openstock_data') {
        $filterProduct = trim($_REQUEST['product_id'] ?? 'all');
        $filterLocation = trim($_REQUEST['location_id'] ?? 'all');
        $filterTrader = trim($_REQUEST['trader_type'] ?? 'all');
        $returnarray = [];
        $emittedStockNos = [];

        $prodWhere = "";
        if ($filterProduct !== 'all' && $filterProduct !== '' && (int)$filterProduct > 0) {
            $pId = (int)$filterProduct;
            $prodWhere = " AND stk.productid = {$pId} ";
        }

        $traderWhereA = "";
        if ($filterTrader === 'trader') {
            $traderWhereA = " AND (stk.clientid = 'C00001' OR COALESCE(cl.is_trader, 0) = 1) ";
        } elseif ($filterTrader === 'farmer') {
            $traderWhereA = " AND stk.clientid != 'C00001' AND COALESCE(cl.is_trader, 0) != 1 ";
        }

        $statusWhereA = "";
        if ($filterProduct !== 'all' && (int)$filterProduct > 0) {
            // When filtering by a specific product, retrieve all production orders of that product (delivered or not)
            $statusWhereA = "";
        } else {
            $statusWhereA = " AND stk.status IN ('Confirmed', 'confirmed')
              AND LOWER(TRIM(COALESCE(stk.status, ''))) NOT IN ('cancelled', 'cancel', 'remove', 'removed')
              AND LOWER(TRIM(COALESCE(stk.workingstatus, ''))) NOT IN ('cancelled', 'cancel', 'remove', 'removed') ";
        }

        // A. Primary Query: Production Stock Orders (Active, Non-Dispatched, Non-Cancelled, or All for selected product)
        $qry = "
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
                stk.sowingstatus,
                stk.workingstatus,
                stk.status AS order_status,
                COALESCE(stk.quantity, 0) AS stkqty,
                COALESCE(stk.dispatchqty, 0) AS dispatchqty,
                COALESCE(stk.Qty_from_readysale, 0) AS ordreadytosaleqty,
                COALESCE(cl.is_trader, 0) AS is_trader,
                COALESCE(cl.client_name, '') AS client_name,
                COALESCE(c.confirmed_allocated, 0) AS confirmed_allocated,
                COALESCE(c.unconfirmed_allocated, 0) AS unconfirmed_allocated,
                COALESCE(soi.soi_dispatchedqty, 0) AS soi_dispatchedqty,
                COALESCE(soi.soi_status, '') AS soi_status,
                COALESCE(so.so_status, '') AS so_status,
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
                ) AS availableqty,
                CASE 
                    WHEN COALESCE(cl.is_trader, 0) = 1 OR stk.clientid = 'C00001' THEN 'internal_stock'
                    ELSE 'farmer_production_stock'
                END AS stock_type
            FROM krf_production_stockorder AS stk
            LEFT JOIN krf_client_master AS cl
                ON cl.projectid = stk.projectid
                AND cl.client_id = stk.clientid
            LEFT JOIN (
                SELECT
                    stockorderno,
                    SUM(
                        CASE
                            WHEN LOWER(TRIM(COALESCE(confirmstatus, ''))) = 'confirmed'
                            THEN COALESCE(consumed_qty, 0)
                            ELSE 0
                        END
                    ) AS confirmed_allocated,
                    SUM(
                        CASE
                            WHEN LOWER(TRIM(COALESCE(confirmstatus, ''))) NOT IN ('confirmed', 'cancel', 'cancelled')
                            THEN COALESCE(consumed_qty, 0)
                            ELSE 0
                        END
                    ) AS unconfirmed_allocated
                FROM krf_productionorder_consumed 
                WHERE LOWER(TRIM(COALESCE(status, ''))) IN ('pending', 'allocated', 'dispatched', 'fulldispatch', 'partialdispatch', 'readytoload', 'readytodispatch', 'loaded')
                  AND LOWER(TRIM(COALESCE(status, ''))) NOT IN ('cancelled', 'cancel')
                  AND (tedit IS NULL OR tedit = '')
                GROUP BY stockorderno
            ) AS c
                ON c.stockorderno = stk.stockorder_no
            LEFT JOIN (
                SELECT 
                    stockorder_no,
                    SUM(COALESCE(dispatchedqty, 0)) AS soi_dispatchedqty,
                    MAX(status) AS soi_status
                FROM krf_sales_order_items
                WHERE NULLIF(TRIM(stockorder_no), '') IS NOT NULL AND (tedit IS NULL OR tedit = '')
                GROUP BY stockorder_no
            ) AS soi ON soi.stockorder_no = stk.stockorder_no
            LEFT JOIN (
                SELECT 
                    psono, projectid,
                    MAX(status) AS so_status
                FROM krf_sales_order
                WHERE NULLIF(TRIM(psono), '') IS NOT NULL
                GROUP BY psono, projectid
            ) AS so ON NULLIF(TRIM(stk.psono), '') IS NOT NULL AND so.psono = stk.psono AND so.projectid = stk.projectid
            LEFT JOIN product_master AS p
                ON p.ProductID = stk.productid
            WHERE (stk.projectid = {$projectid} OR '{$projectid}' = '0' OR stk.projectid = '17')
              {$prodWhere}
              {$traderWhereA}
              {$statusWhereA}
            ORDER BY p.ProductName ASC, stk.expected_delivery_date ASC, stk.id ASC
        ";

        $result = $krfconn->query($qry);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $stkNo = trim((string)($row['stockorder_no'] ?? ''));
                if ($stkNo !== '') {
                    $emittedStockNos[$stkNo] = true;
                }
                $row['stksno'] = (int)$row['stksno'];
                $row['stkqty'] = (int)$row['stkqty'];
                $row['dispatchqty'] = (int)($row['dispatchqty'] ?? 0);
                $row['soi_dispatchedqty'] = (int)($row['soi_dispatchedqty'] ?? 0);
                $row['ordreadytosaleqty'] = (int)$row['ordreadytosaleqty'];
                $row['confirmed_allocated'] = (int)$row['confirmed_allocated'];
                $row['unconfirmed_allocated'] = (int)$row['unconfirmed_allocated'];
                $row['base_availableqty'] = (int)$row['base_availableqty'];
                $row['availableqty'] = (int)$row['availableqty'];
                $row['is_trader'] = (int)$row['is_trader'];
                $row['client_name'] = trim((string)($row['client_name'] ?? ''));
                if (empty($row['client_name']) && $row['clientid'] === 'C00001') {
                    $row['client_name'] = 'Karjee Farms';
                }
                $returnarray[] = $row;
            }
        }

        // B. Client Production Order Surplus Stock (Post-Dispatch for Non-Trader Clients - only if not "Only Trader")
        if ($filterTrader !== 'trader') {
            $surplusCandWhere = ($filterProduct !== 'all' && (int)$filterProduct > 0)
                ? " AND stk.productid = " . (int)$filterProduct
                : "";

            $surplusCandQry = "
                SELECT DISTINCT stk.stockorder_no
                FROM krf_production_stockorder stk
                LEFT JOIN krf_client_master cl 
                    ON cl.projectid = stk.projectid AND cl.client_id = stk.clientid
                LEFT JOIN krf_sales_order so 
                    ON so.psono = stk.psono AND so.projectid = stk.projectid
                LEFT JOIN krf_sales_order_items soi
                    ON soi.stockorder_no = stk.stockorder_no
                WHERE (stk.projectid = {$projectid} OR '{$projectid}' = '0' OR stk.projectid = '17')
                  {$surplusCandWhere}
                  AND stk.clientid != 'C00001'
                  AND COALESCE(cl.is_trader, 0) != 1
                  AND stk.quantity > 0
                  AND (
                      LOWER(TRIM(COALESCE(stk.workingstatus, ''))) = 'final dispatch'
                      OR (stk.dispatchqty > 0 AND stk.dispatchqty >= stk.quantity)
                      OR LOWER(TRIM(COALESCE(soi.status, ''))) IN ('fulldispatch', 'fulldispatched')
                      OR (soi.dispatchedqty > 0 AND soi.dispatchedqty >= soi.quantity)
                      OR LOWER(REPLACE(TRIM(COALESCE(so.status, '')), ' ', '')) IN ('fulldispatch', 'fulldispatched')
                  )
            ";
            $surplusCandRes = $krfconn->query($surplusCandQry);
            $surplusCandList = [];
            if ($surplusCandRes) {
                while ($cRow = $surplusCandRes->fetch_assoc()) {
                    $cStk = trim((string)$cRow['stockorder_no']);
                    if ($cStk !== '' && !isset($emittedStockNos[$cStk])) {
                        $surplusCandList[] = "'" . $krfconn->real_escape_string($cStk) . "'";
                    }
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
                        stk.sowingstatus,
                        stk.workingstatus,
                        COALESCE(cl.is_trader, 0) AS is_trader,
                        cl.client_name,
                        COALESCE(ph.physical_readytosale_qty, 0) AS total_readytosale_qty,
                        0 AS readytosale_qty,
                        COALESCE(ph.physical_readytosale_qty, 0) AS stkqty,
                        0 AS ordreadytosaleqty,
                        COALESCE(c.confirmed_allocated, 0) AS confirmed_allocated,
                        COALESCE(c.unconfirmed_allocated, 0) AS unconfirmed_allocated,
                        (COALESCE(ph.physical_readytosale_qty, 0) - COALESCE(c.confirmed_allocated, 0)) AS base_availableqty,
                        (COALESCE(ph.physical_readytosale_qty, 0) - COALESCE(c.confirmed_allocated, 0)) AS availableqty,
                        'client_surplus' AS stock_type,
                        'Surplus After Dispatched' AS reason
                    FROM krf_production_stockorder AS stk
                    LEFT JOIN krf_sales_order AS so
                        ON so.psono = stk.psono AND so.projectid = stk.projectid
                    INNER JOIN (
                        SELECT
                            stockorder_no,
                            psono,
                            SUM(GREATEST(0, COALESCE(NULLIF(CAST(qtypertray AS SIGNED), 0), 126) - COALESCE(failurecount, 0))) AS physical_readytosale_qty
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
                            SUM(CASE WHEN LOWER(TRIM(COALESCE(c.confirmstatus, ''))) NOT IN ('confirmed', 'cancel', 'cancelled') THEN COALESCE(consumed_qty, 0) ELSE 0 END) AS unconfirmed_allocated
                        FROM krf_productionorder_consumed c
                        JOIN krf_production_stockorder s ON s.stockorder_no = c.stockorderno
                        WHERE c.stockorderno IN ({$candIn})
                          AND (c.psono != s.psono OR s.psono IS NULL OR s.psono = '')
                          AND LOWER(TRIM(COALESCE(c.status, ''))) IN ('pending', 'allocated', 'dispatched', 'fulldispatch', 'partialdispatch', 'readytoload', 'readytodispatch', 'loaded')
                          AND LOWER(TRIM(COALESCE(c.status, ''))) NOT IN ('cancelled', 'cancel')
                          AND (c.tedit IS NULL OR c.tedit = '')
                        GROUP BY c.stockorderno
                    ) AS c
                        ON c.stockorderno = stk.stockorder_no
                    LEFT JOIN product_master AS p ON p.ProductID = stk.productid
                    LEFT JOIN krf_client_master AS cl ON cl.projectid = stk.projectid AND cl.client_id = stk.clientid
                    WHERE stk.stockorder_no IN ({$candIn})
                    HAVING availableqty > 0
                    ORDER BY p.ProductName ASC, stk.expected_delivery_date ASC
                ";
                $surplusResult = $krfconn->query($surplusQry);
                if ($surplusResult) {
                    while ($sRow = $surplusResult->fetch_assoc()) {
                        $sStk = trim((string)($sRow['stockorder_no'] ?? ''));
                        if ($sStk !== '' && isset($emittedStockNos[$sStk])) {
                            continue; // Prevent duplicate
                        }
                        if ($sStk !== '') {
                            $emittedStockNos[$sStk] = true;
                        }
                        $sRow['stksno'] = (int)$sRow['stksno'];
                        $sRow['stkqty'] = (int)$sRow['stkqty'];
                        $sRow['ordreadytosaleqty'] = 0;
                        $sRow['confirmed_allocated'] = (int)$sRow['confirmed_allocated'];
                        $sRow['unconfirmed_allocated'] = (int)$sRow['unconfirmed_allocated'];
                        $sRow['base_availableqty'] = (int)$sRow['base_availableqty'];
                        $sRow['availableqty'] = (int)$sRow['availableqty'];
                        $sRow['is_trader'] = (int)$sRow['is_trader'];
                        $returnarray[] = $sRow;
                    }
                }
            }

            // C. Cancelled Order Items from Provisional / Final Sales Order where Sowing Started (only if not "Only Trader")
            $canProdWhereP = ($filterProduct !== 'all' && (int)$filterProduct > 0)
                ? " AND product_id = " . (int)$filterProduct
                : "";
            $canProdWhereS = ($filterProduct !== 'all' && (int)$filterProduct > 0)
                ? " AND product_id = " . (int)$filterProduct
                : "";

            $cItemsQry = "
                SELECT DISTINCT c.stockorder_no, c.psono, c.product_id, c.product_name
                FROM (
                    SELECT stockorder_no, psono, product_id, product_name
                    FROM krf_sales_order_items
                    WHERE (LOWER(TRIM(status)) IN ('cancelled', 'cancel') OR quantity = 0)
                      AND stockorder_no != '' {$canProdWhereS}
                    UNION
                    SELECT stockorder_no, psono, product_id, product_name
                    FROM krf_provisional_sales_order_items
                    WHERE LOWER(TRIM(status)) IN ('cancelled', 'cancel')
                      AND stockorder_no != '' {$canProdWhereP}
                ) c
                INNER JOIN (
                    SELECT DISTINCT stockorder_no 
                    FROM krf_polyhouse_sowingplant_details 
                    WHERE stockorder_no != ''
                ) sp ON sp.stockorder_no = c.stockorder_no
            ";
            $cRes = $krfconn->query($cItemsQry);
            $candItems = [];
            $candStocks = [];
            if ($cRes) {
                while ($r = $cRes->fetch_assoc()) {
                    $stk = trim($r['stockorder_no']);
                    if (empty($stk) || isset($emittedStockNos[$stk])) {
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
                    SELECT stockorder_no, SUM(GREATEST(0, COALESCE(NULLIF(CAST(qtypertray AS SIGNED), 0), 126) - COALESCE(failurecount, 0))) as phys_qty
                    FROM krf_polyhouse_plant_details
                    WHERE stockorder_no IN ({$candIn})
                      AND LOWER(TRIM(COALESCE(status, ''))) NOT IN ('tray dead', 'dead', 'removed')
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
                    SELECT stockorder_no, SUM(CASE WHEN LOWER(TRIM(COALESCE(status, ''))) NOT IN ('dead', 'removed') THEN CAST(totalsowingqty AS UNSIGNED) ELSE 0 END) as sow_qty
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
                        SUM(CASE WHEN LOWER(TRIM(COALESCE(confirmstatus, ''))) NOT IN ('confirmed', 'cancel', 'cancelled') THEN COALESCE(consumed_qty, 0) ELSE 0 END) AS unconf_alloc
                    FROM krf_productionorder_consumed
                    WHERE stockorderno IN ({$candIn})
                      AND LOWER(TRIM(COALESCE(status, ''))) IN ('pending', 'allocated', 'dispatched', 'fulldispatch', 'partialdispatch', 'readytoload', 'readytodispatch', 'loaded')
                      AND LOWER(TRIM(COALESCE(status, ''))) NOT IN ('cancelled', 'cancel')
                      AND (tedit IS NULL OR tedit = '')
                    GROUP BY stockorderno
                ");
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
                    SELECT stk.id as stksno, stk.stockorder_no, stk.expected_delivery_date, stk.sowinglocationid, stk.clientid, cl.client_name, cl.is_trader, stk.sowingstatus, stk.workingstatus
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
                    if (isset($emittedStockNos[$stkNo])) {
                        continue;
                    }
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
                    $cid = trim((string)($meta['clientid'] ?? ''));
                    if ($cid === 'C00001' || isset($emittedStockNos[$stkNo])) {
                        continue; // Skip Karjee Farm internal stock or already present orders
                    }
                    $clientName = trim($meta['client_name'] ?? '');
                    if (empty($clientName) && !empty($meta['clientid'])) {
                        $clientName = $meta['clientid'];
                    }

                    $pId = (int)($item['product_id'] ?? 0);
                    $pso = trim($item['psono'] ?? '');

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
                        'stkqty' => $availPlants,
                        'ordreadytosaleqty' => 0,
                        'is_trader' => (int)($meta['is_trader'] ?? 0),
                        'confirmed_allocated' => $confAlloc,
                        'unconfirmed_allocated' => $unconfAlloc,
                        'base_availableqty' => $netAvail,
                        'total_readytosale_qty' => $availPlants,
                        'readytosale_qty' => 0,
                        'availableqty' => $netAvail,
                        'stock_type' => 'cancelled_order_stock',
                        'reason' => 'Surplus due to Client Order Cancelled',
                        'client_name' => $clientName,
                        'sowingstatus' => $meta['sowingstatus'] ?? '',
                        'workingstatus' => $meta['workingstatus'] ?? ''
                    ];
                    $emittedStockNos[$stkNo] = true;
                }
            }
        }

        // C. Fetch Locations & Polyhouse mappings
        $locMap = [];
        if (isset($hrmconn) && ($hrmconn instanceof mysqli)) {
            $locRes = $hrmconn->query("
                SELECT LocationId, LocationName
                FROM hrm_AttendanceLocationMaster
                WHERE (projectid = {$projectid} OR '{$projectid}' = '0' OR projectid = '17')
            ");
            if ($locRes) {
                while ($lr = $locRes->fetch_assoc()) {
                    $locMap[trim((string)$lr['LocationId'])] = trim((string)$lr['LocationName']);
                }
            }
        }

        $phMap = [];
        $phRes = $krfconn->query("
            SELECT polyhouseid, polyhousename, locationid
            FROM krf_polyhouse_master
            WHERE (projectid = {$projectid} OR '{$projectid}' = '0' OR projectid = '17')
        ");
        if ($phRes) {
            while ($pr = $phRes->fetch_assoc()) {
                $phMap[trim((string)$pr['polyhouseid'])] = [
                    'name' => trim((string)$pr['polyhousename']),
                    'locationid' => trim((string)$pr['locationid'])
                ];
            }
        }

        // GATHER STOCK ORDER NUMBERS
        $stockOrderNos = [];
        foreach ($returnarray as $rItem) {
            if (!empty($rItem['stockorder_no'])) {
                $stockOrderNos[] = "'" . $krfconn->real_escape_string($rItem['stockorder_no']) . "'";
            }
        }

        $plantStats = [];
        $orderLocMap = [];
        if (!empty($stockOrderNos)) {
            $soList = implode(',', array_unique($stockOrderNos));

            // Location Mapping per stock order
            $locSql = "
                SELECT DISTINCT stockorder_no, locationid
                FROM krf_polyhouse_plant_details
                WHERE (projectid = {$projectid} OR '{$projectid}' = '0' OR projectid = '17')
                  AND stockorder_no IN ({$soList})
                  AND (tedit IS NULL OR tedit = '')
                  AND LOWER(TRIM(status)) NOT IN ('removed', 'cancelled')
            ";
            $locRes = $krfconn->query($locSql);
            if ($locRes) {
                while ($lr = $locRes->fetch_assoc()) {
                    $so = trim((string)$lr['stockorder_no']);
                    $lId = trim((string)$lr['locationid']);
                    if (!isset($orderLocMap[$so])) $orderLocMap[$so] = [];
                    $orderLocMap[$so][] = $lId;
                }
            }

            // 1. Order-level summary: Total Ph. Plants (strictly from krf_polyhouse_plant_details where tedit is null or tedit = '')
            // and Rejected / Moved Plants (as per status list where tedit is null or tedit = '')
            $phSummaryMap = [];
            $sumPlSql = "
                SELECT
                    stockorder_no,
                    COUNT(*) AS total_trays,
                    SUM(GREATEST(0, COALESCE(CAST(qtypertray AS SIGNED), 126) - COALESCE(failurecount, 0))) AS total_ph_plants,
                    SUM(CASE WHEN LOWER(TRIM(COALESCE(status, ''))) IN (
                        'internal dispatch', 'clip stick removal', 'removed', 'useforcorrection', 'move for grafting', 
                        'empty after grafting', 'empty after shifting', 'remove after over age', 'tray dead', 'move for opensale'
                    ) THEN GREATEST(0, COALESCE(CAST(qtypertray AS SIGNED), 126) - COALESCE(failurecount, 0)) ELSE 0 END) AS rejected_moved_plants,
                    SUM(CASE WHEN LOWER(TRIM(COALESCE(status, ''))) IN (
                        'internal dispatch', 'clip stick removal', 'removed', 'useforcorrection', 'move for grafting', 
                        'empty after grafting', 'empty after shifting', 'remove after over age', 'tray dead', 'move for opensale'
                    ) THEN 1 ELSE 0 END) AS rejected_moved_trays,
                    SUM(COALESCE(CAST(dispatchqty AS SIGNED), 0)) AS total_disp_qty
                FROM krf_polyhouse_plant_details
                WHERE (projectid = {$projectid} OR '{$projectid}' = '0' OR projectid = '17')
                  AND stockorder_no IN ({$soList})
                  AND (tedit IS NULL OR tedit = '')
                GROUP BY stockorder_no
            ";
            $sumPlRes = $krfconn->query($sumPlSql);
            if ($sumPlRes) {
                while ($spRow = $sumPlRes->fetch_assoc()) {
                    $so = trim((string)$spRow['stockorder_no']);
                    $phSummaryMap[$so] = [
                        'total_ph_plants'       => (int)$spRow['total_ph_plants'],
                        'total_trays'           => (int)$spRow['total_trays'],
                        'rejected_moved_plants' => (int)$spRow['rejected_moved_plants'],
                        'rejected_moved_trays'  => (int)$spRow['rejected_moved_trays'],
                        'total_disp_qty'        => (int)$spRow['total_disp_qty']
                    ];
                }
            }

            // 2. Active Polyhouse Beds & Locations for LOCATION / POLYHOUSE column
            $plSql = "
                SELECT
                    stockorder_no,
                    locationid,
                    polyhouseid,
                    COUNT(*) AS tray_count,
                    SUM(GREATEST(0, COALESCE(CAST(qtypertray AS SIGNED), 126) - COALESCE(failurecount, 0))) AS plant_count
                FROM krf_polyhouse_plant_details
                WHERE (projectid = {$projectid} OR '{$projectid}' = '0' OR projectid = '17')
                  AND stockorder_no IN ({$soList})
                  AND (tedit IS NULL OR tedit = '')
                  AND LOWER(TRIM(status)) NOT IN ('removed', 'cancelled')
                GROUP BY stockorder_no, locationid, polyhouseid
            ";
            $plRes = $krfconn->query($plSql);
            if ($plRes) {
                while ($plr = $plRes->fetch_assoc()) {
                    $so = trim((string)$plr['stockorder_no']);
                    if (!isset($plantStats[$so])) $plantStats[$so] = [];
                    $plantStats[$so][] = [
                        'locationid'  => trim((string)$plr['locationid']),
                        'polyhouseid' => trim((string)$plr['polyhouseid']),
                        'tray_count'  => (int)$plr['tray_count'],
                        'plant_count' => (int)$plr['plant_count'],
                        'is_sowing'   => false
                    ];
                }
            }

            // 3. Sowing Tray Details: Sum of traycavity and tray count
            // A. From krf_polyhouse_sowingplant_details
            $sowSummaryMap = [];
            $sowSql = "
                SELECT
                    stockorder_no,
                    locationid,
                    polyhouseid,
                    COUNT(*) AS tray_count,
                    SUM(COALESCE(CAST(traycavity AS UNSIGNED), 126)) AS cavity_count
                FROM krf_polyhouse_sowingplant_details
                WHERE (projectid = {$projectid} OR '{$projectid}' = '0' OR projectid = '17')
                  AND stockorder_no IN ({$soList})
                  AND LOWER(TRIM(COALESCE(status, ''))) NOT IN ('dead', 'removed', 'cancelled')
                GROUP BY stockorder_no, locationid, polyhouseid
            ";
            $sowRes = $krfconn->query($sowSql);
            if ($sowRes) {
                while ($sr = $sowRes->fetch_assoc()) {
                    $so = trim((string)$sr['stockorder_no']);
                    if (!isset($sowSummaryMap[$so])) {
                        $sowSummaryMap[$so] = [
                            'tray_count'   => 0,
                            'cavity_count' => 0,
                            'locationid'   => trim((string)$sr['locationid']),
                            'polyhouseid'  => trim((string)$sr['polyhouseid']),
                            'source'       => 'sowingplant_details'
                        ];
                    }
                    $sowSummaryMap[$so]['tray_count'] += (int)$sr['tray_count'];
                    $sowSummaryMap[$so]['cavity_count'] += (int)$sr['cavity_count'];
                }
            }

            // B. From krf_polyhouse_germinated_boxes (for orders in germination boxes such as KFP4970)
            $boxSql = "
                SELECT
                    stockorderno AS stockorder_no,
                    sowinglocationid AS locationid,
                    polyhouseid,
                    SUM(traycountinbox) AS tray_count,
                    SUM(traycountinbox * 126) AS cavity_count
                FROM krf_polyhouse_germinated_boxes
                WHERE (projectid = {$projectid} OR '{$projectid}' = '0' OR projectid = '17')
                  AND stockorderno IN ({$soList})
                  AND LOWER(TRIM(COALESCE(status, ''))) NOT IN ('cancelled', 'removed')
                GROUP BY stockorderno, sowinglocationid, polyhouseid
            ";
            $boxRes = $krfconn->query($boxSql);
            if ($boxRes) {
                while ($br = $boxRes->fetch_assoc()) {
                    $so = trim((string)$br['stockorder_no']);
                    if (!isset($sowSummaryMap[$so]) || $sowSummaryMap[$so]['tray_count'] <= 0) {
                        $sowSummaryMap[$so] = [
                            'tray_count'   => (int)$br['tray_count'],
                            'cavity_count' => (int)$br['cavity_count'],
                            'locationid'   => trim((string)$br['locationid']),
                            'polyhouseid'  => trim((string)$br['polyhouseid']),
                            'source'       => 'germinated_boxes'
                        ];
                    }
                }
            }
        }

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
                    SUM(GREATEST(0, COALESCE(CAST(qtypertray AS SIGNED), 126) - COALESCE(CAST(failurecount AS SIGNED), 0))) AS rso_movement_qty
                FROM krf_polyhouse_plant_details
                WHERE (projectid = {$projectid} OR '{$projectid}' = '0' OR projectid = '17')
                  AND stockorder_no IN ({$rsoListStr})
                  AND LOWER(TRIM(status)) NOT IN ('polyhouse', 'readytosale')
                  AND LOWER(TRIM(COALESCE(status, ''))) NOT IN (
                      'internal dispatch', 'clip stick removal', 'removed', 'useforcorrection', 'move for grafting', 
                      'empty after grafting', 'empty after shifting', 'remove after over age', 'tray dead', 'move for opensale'
                  )
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

        // Map farmer orders whose client order was fulfilled / consumed / delivered with another production order
        $fulfilledOtherMap = [];
        $farmerPsonos = [];
        foreach ($returnarray as $r) {
            $cid = $r['clientid'] ?? '';
            $pso = trim($r['psono'] ?? '');
            $isTr = ((int)($r['is_trader'] ?? 0) === 1 || $cid === 'C00001');
            if (!$isTr && !empty($pso)) {
                $farmerPsonos[$pso] = [
                    'stockorder_no' => trim($r['stockorder_no'] ?? ''),
                    'product_id'    => (int)($r['productid'] ?? 0)
                ];
            }
        }

        if (!empty($farmerPsonos)) {
            $psoInList = "'" . implode("','", array_map(function($p) use ($krfconn) { return $krfconn->real_escape_string($p); }, array_keys($farmerPsonos))) . "'";
            $fRes = $krfconn->query("
                SELECT pc.psono, pc.stockorderno, pc.product_id, pc.consumed_qty, pc.confirmstatus, pc.status
                FROM krf_productionorder_consumed pc
                WHERE pc.psono IN ({$psoInList})
                  AND LOWER(TRIM(COALESCE(pc.status, ''))) IN ('pending', 'allocated', 'dispatched', 'fulldispatch', 'partialdispatch', 'readytoload', 'readytodispatch', 'loaded')
                  AND LOWER(TRIM(COALESCE(pc.status, ''))) NOT IN ('cancelled', 'cancel')
                ORDER BY CASE WHEN pc.stockorderno LIKE 'KFP%' THEN 1 ELSE 2 END, pc.id ASC
            ");
            if ($fRes) {
                while ($fr = $fRes->fetch_assoc()) {
                    $p = trim($fr['psono']);
                    $origStock = $farmerPsonos[$p]['stockorder_no'] ?? '';
                    $origProd  = $farmerPsonos[$p]['product_id'] ?? 0;
                    if ($origStock !== '' && $origStock !== trim($fr['stockorderno']) && (int)$fr['product_id'] === $origProd) {
                        $stkCandidate = trim($fr['stockorderno']);
                        if (!isset($fulfilledOtherMap[$origStock]) || strpos($stkCandidate, 'KFP') === 0) {
                            $fulfilledOtherMap[$origStock] = [
                                'fulfilled_by' => $stkCandidate,
                                'consumed_qty' => (float)$fr['consumed_qty'],
                                'status'       => trim($fr['status'])
                            ];
                        }
                    }
                }
            }
        }

        // Attach Location & Polyhouse details
        $filteredList = [];
        $totConfirmed = 0;
        $totUnconfirmed = 0;
        $totRsoMovement = 0;
        $totBalance = 0;
        $totAproxPhysicalStock = 0;
        $totOrderQty = 0;
        $totPhPlants = 0;
        $totRejMovedPlants = 0;

        foreach ($returnarray as &$rItem) {
            $so = trim((string)($rItem['stockorder_no'] ?? ''));
            $isRsoOrSo = (strncasecmp($so, 'RSO-', 4) === 0 || strncasecmp($so, 'SO-', 3) === 0) && strncasecmp($so, 'KFP', 3) !== 0;
            $rsoMvQty = $isRsoOrSo ? ($rsoMap[$so] ?? 0) : 0;
            $rItem['rso_movement'] = $rsoMvQty;

            // Total Plants across ALL statuses in polyhouse (where tedit is null or tedit = '')
            // Note: If plants are not in krf_polyhouse_plant_details, total_ph_plants is strictly 0!
            $sumInfo = $phSummaryMap[$so] ?? null;
            $rejMvQty = $sumInfo ? (int)$sumInfo['rejected_moved_plants'] : 0;
            $rejMvTrays = $sumInfo ? (int)$sumInfo['rejected_moved_trays'] : 0;
            $totPhPlantsVal = $sumInfo ? (int)$sumInfo['total_ph_plants'] : 0;
            $totPhTraysVal = $sumInfo ? (int)$sumInfo['total_trays'] : 0;
            $phDispQty = $sumInfo ? (int)($sumInfo['total_disp_qty'] ?? 0) : 0;

            // Sowing Tray Details (sum of traycavity and tray count from sowing / germination boxes)
            $sowInfo = $sowSummaryMap[$so] ?? null;
            $sowTraysVal = $sowInfo ? (int)$sowInfo['tray_count'] : 0;
            $sowCavityVal = $sowInfo ? (int)$sowInfo['cavity_count'] : 0;
            $rItem['sowing_trays'] = $sowTraysVal;
            $rItem['sowing_plants'] = $sowCavityVal;

            $orderQty = (int)($rItem['stkqty'] ?? 0);
            $confAlloc = (int)($rItem['confirmed_allocated'] ?? 0);
            $isTrader = ((int)($rItem['is_trader'] ?? 0) === 1 || ($rItem['clientid'] ?? '') === 'C00001');

            $isDeliveredWithOther = isset($fulfilledOtherMap[$so]);
            $rItem['is_delivered_with_other'] = $isDeliveredWithOther;
            $rItem['fulfilled_by_stockorder'] = $isDeliveredWithOther ? $fulfilledOtherMap[$so]['fulfilled_by'] : '';
            $rItem['is_free_to_allocate']     = $isDeliveredWithOther;

            if ($isDeliveredWithOther) {
                // Client order was fulfilled with other production order -> this production order is free to allocate to other!
                $confAlloc = 0;
                $rItem['confirmed_allocated'] = 0;
                $rItem['workingstatus'] = 'Delivered with other order';
            }

            if (!$isTrader && $orderQty == 0) {
                $orderQty = $confAlloc;
                $rItem['stkqty'] = $orderQty;
            }

            // Aprox Physical Stock formula: PH. PLANTS - (REJ. / MOVED + CONF. ALLOC + RSO-MOVEMENT)
            $aproxPhys = max(0, $totPhPlantsVal - ($rejMvQty + $confAlloc + $rsoMvQty));
            $rItem['aprox_physical_stock'] = $aproxPhys;

            // Balance calculation:
            if ($isTrader) {
                $bal = max(0, $orderQty - ($confAlloc + $rsoMvQty + $rejMvQty));
            } else {
                // For client order with surplus in polyhouse: available balance for open stock is the surplus plants!
                if ($totPhPlantsVal > 0) {
                    $bal = $aproxPhys;
                } else {
                    // For client order pending sowing or not yet placed in polyhouse beds, no surplus balance available for open sale
                    $bal = 0;
                }
            }
            $rItem['base_availableqty'] = $bal;
            $rItem['availableqty'] = $bal;

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
                $phName = $phMap[$phId]['name'] ?? ($phId ? "PH-{$phId}" : '');
                if (!empty($phName)) {
                    $prefix = !empty($st['is_sowing']) ? '<span class="badge bg-secondary-subtle text-secondary me-1" style="font-size:0.68rem;">Sowing</span>' : '';
                    $polyhouseDetails[] = "{$prefix}{$phName}: <strong>{$st['tray_count']} Trays</strong> (" . number_format($st['plant_count']) . " Plants)";
                }
                $totTrays += $st['tray_count'];
                $totPlants += $st['plant_count'];
            }

            if (empty($locNames)) {
                if (!empty($orderLocMap[$so])) {
                    foreach ($orderLocMap[$so] as $lId) {
                        $lName = $locMap[$lId] ?? $lId;
                        if ($lName && !in_array($lName, $locNames, true)) $locNames[] = $lName;
                    }
                } elseif (!empty($sowInfo['locationid'])) {
                    $lId = $sowInfo['locationid'];
                    $lName = $locMap[$lId] ?? $lId;
                    if ($lName && !in_array($lName, $locNames, true)) $locNames[] = $lName;
                } elseif (!empty($rItem['sowinglocationid'])) {
                    $fallbackLocId = trim((string)$rItem['sowinglocationid']);
                    $locNames[] = $locMap[$fallbackLocId] ?? $fallbackLocId;
                }
            }

            $sowStat = strtolower(trim((string)($rItem['sowingstatus'] ?? '')));
            $workStat = strtolower(trim((string)($rItem['workingstatus'] ?? '')));

            if (!empty($polyhouseDetails)) {
                $rItem['polyhouse_status_label'] = '';
                $rItem['polyhouse_details'] = implode('<br>', $polyhouseDetails);
            } elseif ($sowTraysVal > 0) {
                $rItem['polyhouse_status_label'] = 'In Sowing';
                $rItem['polyhouse_details'] = '<div class="small text-secondary mt-0.5" style="font-size:0.75rem;"><span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle me-1" style="font-size:0.68rem; font-weight:600;"><i class="fa fa-seedling me-1"></i>Sowing</span> <strong>' . number_format($sowTraysVal) . ' Trays</strong> (' . number_format($sowCavityVal) . ' Plants)</div>';
            } elseif ($sowStat === 'pending' || empty($workStat)) {
                $rItem['polyhouse_status_label'] = 'Pending Sowing';
                $rItem['polyhouse_details'] = '';
            } else {
                $rItem['polyhouse_status_label'] = '';
                $rItem['polyhouse_details'] = '';
            }

            $rItem['locationname'] = !empty($locNames) ? implode(', ', $locNames) : 'N/A';
            
            // Total plants strictly reflects plants physically present in polyhouse!
            $rItem['total_plants'] = $totPhPlantsVal;
            $rItem['total_trays'] = $totPhTraysVal;
            $rItem['rejected_moved_plants'] = $rejMvQty;
            $rItem['rejected_moved_trays'] = $rejMvTrays;

            // Delivery Status Determination
            $orderStat = strtolower(trim((string)($rItem['order_status'] ?? $rItem['status'] ?? '')));
            $workStatLower = strtolower(trim((string)($rItem['workingstatus'] ?? '')));
            $soiStatLower = strtolower(trim((string)($rItem['soi_status'] ?? '')));
            $soStatLower = strtolower(trim((string)($rItem['so_status'] ?? '')));
            $stkDisp = (int)($rItem['dispatchqty'] ?? 0);
            $soiDisp = (int)($rItem['soi_dispatchedqty'] ?? 0);
            $totDisp = max($stkDisp, $soiDisp, $phDispQty);

            $deliveryStatus = 'Not Delivered';
            $isDelivered = false;

            if (in_array($orderStat, ['cancelled', 'cancel', 'remove', 'removed']) || in_array($workStatLower, ['cancelled', 'cancel', 'remove', 'removed'])) {
                $deliveryStatus = 'Cancelled';
            } elseif ($workStatLower === 'final dispatch') {
                $deliveryStatus = 'Delivered';
                $isDelivered = true;
            } elseif ($isTrader) {
                if ($orderQty > 0 && $stkDisp >= $orderQty) {
                    $deliveryStatus = 'Delivered';
                    $isDelivered = true;
                } elseif ($totDisp > 0) {
                    $deliveryStatus = 'Partial Delivered';
                } elseif ($totPhPlantsVal > 0) {
                    $deliveryStatus = 'In Polyhouse';
                } elseif ($sowTraysVal > 0) {
                    $deliveryStatus = 'In Sowing';
                } else {
                    $deliveryStatus = 'Pending Sowing';
                }
            } else {
                // Farmer order
                if (($orderQty > 0 && $totDisp >= $orderQty) || in_array($soiStatLower, ['fulldispatch', 'fulldispatched']) || in_array($soStatLower, ['fulldispatch', 'fulldispatched'])) {
                    $deliveryStatus = 'Delivered';
                    $isDelivered = true;
                } elseif ($totDisp > 0) {
                    $deliveryStatus = 'Partial Delivered';
                } elseif ($totPhPlantsVal > 0) {
                    $deliveryStatus = 'In Polyhouse';
                } elseif ($sowTraysVal > 0) {
                    $deliveryStatus = 'In Sowing';
                } else {
                    $deliveryStatus = 'Pending Sowing';
                }
            }

            $rItem['delivery_status'] = $deliveryStatus;
            $rItem['is_delivered'] = $isDelivered;

            $isOpenStock = (!$isDelivered && $deliveryStatus !== 'Cancelled' && ($totPhPlantsVal > 0 || $sowTraysVal > 0 || ($isTrader && $bal > 0)));
            $rItem['is_open_stock'] = $isOpenStock;

            // Optional filter by location name if specified
            if ($filterLocation !== 'all' && $filterLocation !== '') {
                if (stripos($rItem['locationname'], $filterLocation) === false) {
                    continue;
                }
            }

            // In "All Products" view, skip completely inactive/empty orders
            if ($filterProduct === 'all') {
                if (!$isOpenStock && $totPhPlantsVal <= 0 && $sowTraysVal <= 0 && $bal <= 0) {
                    continue;
                }
            }

            // Only accumulate into overall Open Stock totals if order is active open stock!
            if ($isOpenStock) {
                $totConfirmed          += (int)$rItem['confirmed_allocated'];
                $totUnconfirmed        += (int)$rItem['unconfirmed_allocated'];
                $totRsoMovement        += (int)$rItem['rso_movement'];
                $totBalance            += (int)$rItem['availableqty'];
                $totAproxPhysicalStock += (int)$rItem['aprox_physical_stock'];
                $totOrderQty           += (int)$rItem['stkqty'];
                $totPhPlants           += (int)($rItem['total_plants'] ?? 0);
                $totRejMovedPlants     += (int)($rItem['rejected_moved_plants'] ?? 0);
            }

            $filteredList[] = $rItem;
        }
        unset($rItem);

        echo json_encode([
            'status'                 => true,
            'data'                   => $filteredList,
            'total_confirmed'            => $totConfirmed,
            'total_unconfirmed'          => $totUnconfirmed,
            'total_rso_movement'         => $totRsoMovement,
            'total_balance'              => $totBalance,
            'total_aprox_physical_stock' => $totAproxPhysicalStock,
            'total_order_qty'            => $totOrderQty,
            'total_ph_plants'            => $totPhPlants,
            'total_rej_moved_plants'     => $totRejMovedPlants,
            'total_count'                => count($filteredList)
        ]);
        exit;
    }

    // 3. Fetch RSO Movement Details (Status-Wise Breakdown)
    if ($ajaxAction === 'get_rso_movement_details') {
        $stockOrderNo = trim($_REQUEST['stockorder_no'] ?? '');
        $stockOrderNosParam = trim($_REQUEST['stockorder_nos'] ?? '');
        $productId = (int)($_REQUEST['product_id'] ?? 0);

        $soCandidates = [];
        if (!empty($stockOrderNo)) {
            $soCandidates[] = $stockOrderNo;
        } elseif (!empty($stockOrderNosParam)) {
            $parts = explode(',', $stockOrderNosParam);
            foreach ($parts as $p) {
                $p = trim($p);
                if (!empty($p)) $soCandidates[] = $p;
            }
        } elseif ($productId > 0) {
            // Fetch all stockorder_no for this product
            $pSoRes = $krfconn->query("
                SELECT stockorder_no 
                FROM krf_production_stockorder 
                WHERE productid = {$productId} 
                  AND (projectid = {$projectid} OR '{$projectid}' = '0' OR projectid = '17')
            ");
            if ($pSoRes) {
                while ($pRow = $pSoRes->fetch_assoc()) {
                    $s = trim((string)$pRow['stockorder_no']);
                    if (!empty($s) && (strncasecmp($s, 'RSO-', 4) === 0 || strncasecmp($s, 'SO-', 3) === 0) && strncasecmp($s, 'KFP', 3) !== 0) {
                        $soCandidates[] = $s;
                    }
                }
            }
        }

        $soCandidates = array_unique(array_filter($soCandidates));
        if (empty($soCandidates)) {
            echo json_encode([
                'status'         => true,
                'stockorder_no'  => $stockOrderNo ?: 'N/A',
                'data'           => [],
                'status_summary' => [],
                'total_plants'   => 0,
                'total_trays'    => 0
            ]);
            exit;
        }

        // Location Master Cache via $hrmconn
        $locMap = [];
        if (isset($hrmconn) && ($hrmconn instanceof mysqli)) {
            $locRes = $hrmconn->query("
                SELECT LocationId, LocationName
                FROM hrm_AttendanceLocationMaster
                WHERE (projectid = {$projectid} OR '{$projectid}' = '0' OR projectid = '17')
            ");
            if ($locRes) {
                while ($lr = $locRes->fetch_assoc()) {
                    $locMap[trim((string)$lr['LocationId'])] = trim((string)$lr['LocationName']);
                }
            }
        }

        // Polyhouse Master Cache
        $phMap = [];
        $phRes = $krfconn->query("
            SELECT polyhouseid, polyhousename, locationid
            FROM krf_polyhouse_master
            WHERE (projectid = {$projectid} OR '{$projectid}' = '0' OR projectid = '17')
        ");
        if ($phRes) {
            while ($pr = $phRes->fetch_assoc()) {
                $phMap[trim((string)$pr['polyhouseid'])] = [
                    'name' => trim((string)$pr['polyhousename']),
                    'locationid' => trim((string)$pr['locationid'])
                ];
            }
        }

        $escapedList = [];
        foreach ($soCandidates as $s) {
            $escapedList[] = "'" . $krfconn->real_escape_string($s) . "'";
        }
        $soListSql = implode(',', $escapedList);

        $sql = "
            SELECT
                p.stockorder_no,
                p.status,
                p.locationid,
                p.polyhouseid,
                p.floar_no,
                p.movementremark,
                p.updated_by,
                p.created_by,
                MAX(p.updated_at) AS last_updated,
                COUNT(*) AS tray_count,
                SUM(GREATEST(0, COALESCE(CAST(p.qtypertray AS SIGNED), 126) - COALESCE(CAST(p.failurecount AS SIGNED), 0))) AS plants_qty
            FROM krf_polyhouse_plant_details p
            WHERE (p.projectid = {$projectid} OR '{$projectid}' = '0' OR projectid = '17')
              AND p.stockorder_no IN ({$soListSql})
              AND LOWER(TRIM(p.status)) NOT IN ('polyhouse', 'readytosale')
              AND LOWER(TRIM(COALESCE(p.status, ''))) NOT IN (
                  'internal dispatch', 'clip stick removal', 'removed', 'useforcorrection', 'move for grafting', 
                  'empty after grafting', 'empty after shifting', 'remove after over age', 'tray dead', 'move for opensale'
              )
              AND (p.dispd_agst_psono IS NULL OR TRIM(p.dispd_agst_psono) = '')
              AND p.movementremark IS NOT NULL AND TRIM(p.movementremark) <> ''
              AND (p.tedit IS NULL OR p.tedit = '')
            GROUP BY p.stockorder_no, p.status, p.locationid, p.polyhouseid, p.floar_no, p.movementremark, p.updated_by, p.created_by
            HAVING plants_qty > 0
            ORDER BY plants_qty DESC
        ";

        $res = $krfconn->query($sql);
        $details = [];
        $statusSummary = [];
        $totPlants = 0;
        $totTrays = 0;

        $statusColors = [
            'move for grafting'     => ['bg' => '#6f42c1', 'text' => '#ffffff'],
            'hardening'             => ['bg' => '#fd7e14', 'text' => '#ffffff'],
            'empty after shifting'  => ['bg' => '#6c757d', 'text' => '#ffffff'],
            'final dispatch'        => ['bg' => '#0d6efd', 'text' => '#ffffff'],
            'grafted'               => ['bg' => '#20c997', 'text' => '#ffffff'],
            'clip stick removal'    => ['bg' => '#d63384', 'text' => '#ffffff'],
            'removed'               => ['bg' => '#dc3545', 'text' => '#ffffff'],
            'remove after over age' => ['bg' => '#b02a37', 'text' => '#ffffff'],
            'openstock'             => ['bg' => '#198754', 'text' => '#ffffff'],
            'loaded'                => ['bg' => '#0dcaf0', 'text' => '#000000'],
            'empty after grafting'  => ['bg' => '#495057', 'text' => '#ffffff'],
            'allocated'             => ['bg' => '#ffc107', 'text' => '#000000'],
            'internal dispatch'     => ['bg' => '#055160', 'text' => '#ffffff'],
            'reverify'              => ['bg' => '#ffca2c', 'text' => '#000000'],
        ];

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $st = trim((string)$row['status']);
                if (empty($st)) $st = 'Unspecified';
                $stKey = strtolower($st);

                $tCount = (int)$row['tray_count'];
                $pQty = (int)$row['plants_qty'];
                $totTrays += $tCount;
                $totPlants += $pQty;

                if (!isset($statusSummary[$st])) {
                    $colorInfo = $statusColors[$stKey] ?? ['bg' => '#6c757d', 'text' => '#ffffff'];
                    $statusSummary[$st] = [
                        'status'       => $st,
                        'tray_count'   => 0,
                        'plants_qty'   => 0,
                        'color'        => $colorInfo['bg'],
                        'text_color'   => $colorInfo['text']
                    ];
                }
                $statusSummary[$st]['tray_count'] += $tCount;
                $statusSummary[$st]['plants_qty'] += $pQty;

                $locId = trim((string)$row['locationid']);
                $phId = trim((string)$row['polyhouseid']);
                $locName = $locMap[$locId] ?? ($locId ?: 'N/A');
                $phName = $phMap[$phId]['name'] ?? ($phId ? "PH-{$phId}" : '-');
                $floor = trim((string)$row['floar_no']);

                $updatedAt = !empty($row['last_updated']) ? date('d-m-Y h:i A', strtotime($row['last_updated'])) : '-';
                $updatedBy = trim((string)($row['updated_by'] ?: $row['created_by'] ?: '-'));

                $details[] = [
                    'stockorder_no'  => $row['stockorder_no'],
                    'status'         => $st,
                    'status_color'   => $statusColors[$stKey]['bg'] ?? '#6c757d',
                    'status_text'    => $statusColors[$stKey]['text'] ?? '#ffffff',
                    'location_name'  => $locName,
                    'polyhouse_name' => $phName,
                    'floar_no'       => $floor,
                    'tray_count'     => $tCount,
                    'plants_qty'     => $pQty,
                    'movementremark' => $row['movementremark'] ?: '-',
                    'last_updated'   => $updatedAt,
                    'updated_by'     => $updatedBy
                ];
            }
        }

        // Sort status summary by plants_qty DESC
        usort($statusSummary, function($a, $b) {
            return $b['plants_qty'] <=> $a['plants_qty'];
        });

        echo json_encode([
            'status'         => true,
            'stockorder_no'  => $stockOrderNo ?: (count($soCandidates) === 1 ? $soCandidates[0] : 'Multiple Orders (' . count($soCandidates) . ')'),
            'total_plants'   => $totPlants,
            'total_trays'    => $totTrays,
            'status_summary' => array_values($statusSummary),
            'data'           => $details
        ]);
        exit;
    }

    // 4. Fetch Polyhouse Plants Details (Status-Wise Breakdown)
    if ($ajaxAction === 'get_ph_plants_details') {
        $stockOrderNo = trim($_REQUEST['stockorder_no'] ?? '');
        $stockOrderNosParam = trim($_REQUEST['stockorder_nos'] ?? '');
        $productId = (int)($_REQUEST['product_id'] ?? 0);

        $soCandidates = [];
        if (!empty($stockOrderNo)) {
            $soCandidates[] = $stockOrderNo;
        } elseif (!empty($stockOrderNosParam)) {
            $parts = explode(',', $stockOrderNosParam);
            foreach ($parts as $p) {
                $p = trim($p);
                if (!empty($p)) $soCandidates[] = $p;
            }
        } elseif ($productId > 0) {
            $pSoRes = $krfconn->query("
                SELECT stockorder_no 
                FROM krf_production_stockorder 
                WHERE productid = {$productId} 
                  AND (projectid = {$projectid} OR '{$projectid}' = '0' OR projectid = '17')
            ");
            if ($pSoRes) {
                while ($pRow = $pSoRes->fetch_assoc()) {
                    $s = trim((string)$pRow['stockorder_no']);
                    if (!empty($s)) $soCandidates[] = $s;
                }
            }
        }

        $soCandidates = array_unique(array_filter($soCandidates));
        if (empty($soCandidates)) {
            echo json_encode([
                'status'         => true,
                'stockorder_no'  => $stockOrderNo ?: 'N/A',
                'data'           => [],
                'status_summary' => [],
                'total_plants'   => 0,
                'total_trays'    => 0
            ]);
            exit;
        }

        // Location Master Cache via $hrmconn
        $locMap = [];
        if (isset($hrmconn) && ($hrmconn instanceof mysqli)) {
            $locRes = $hrmconn->query("
                SELECT LocationId, LocationName
                FROM hrm_AttendanceLocationMaster
                WHERE (projectid = {$projectid} OR '{$projectid}' = '0' OR projectid = '17')
            ");
            if ($locRes) {
                while ($lr = $locRes->fetch_assoc()) {
                    $locMap[trim((string)$lr['LocationId'])] = trim((string)$lr['LocationName']);
                }
            }
        }

        // Polyhouse Master Cache
        $phMap = [];
        $phRes = $krfconn->query("
            SELECT polyhouseid, polyhousename, locationid
            FROM krf_polyhouse_master
            WHERE (projectid = {$projectid} OR '{$projectid}' = '0' OR projectid = '17')
        ");
        if ($phRes) {
            while ($pr = $phRes->fetch_assoc()) {
                $phMap[trim((string)$pr['polyhouseid'])] = [
                    'name' => trim((string)$pr['polyhousename']),
                    'locationid' => trim((string)$pr['locationid'])
                ];
            }
        }

        $escapedList = [];
        foreach ($soCandidates as $s) {
            $escapedList[] = "'" . $krfconn->real_escape_string($s) . "'";
        }
        $soListSql = implode(',', $escapedList);

        $filterType = trim($_REQUEST['filter_type'] ?? 'all');
        $rejStatusList = "'internal dispatch', 'clip stick removal', 'removed', 'useforcorrection', 'move for grafting', 'empty after grafting', 'empty after shifting', 'remove after over age', 'tray dead', 'move for opensale'";

        $whereStatusFilter = "(p.tedit IS NULL OR p.tedit = '')";
        if ($filterType === 'rejected_moved') {
            $whereStatusFilter .= " AND LOWER(TRIM(p.status)) IN ({$rejStatusList})";
        } elseif ($filterType === 'physical_stock') {
            $whereStatusFilter .= " AND LOWER(TRIM(p.status)) NOT IN ({$rejStatusList}, 'final dispatch', 'dead', 'tray dead', 'cancelled')";
        }

        $sql = "
            SELECT
                p.stockorder_no,
                p.status,
                p.locationid,
                p.polyhouseid,
                p.bed_no,
                p.floar_no,
                p.rack_no,
                p.row_no,
                p.growth_stage,
                p.health_status,
                p.movementremark,
                p.remarks,
                p.updated_by,
                p.created_by,
                MAX(p.updated_at) AS last_updated,
                COUNT(*) AS tray_count,
                SUM(GREATEST(0, COALESCE(CAST(p.qtypertray AS SIGNED), 126) - COALESCE(CAST(p.failurecount AS SIGNED), 0))) AS plants_qty
            FROM krf_polyhouse_plant_details p
            WHERE (p.projectid = {$projectid} OR '{$projectid}' = '0' OR projectid = '17')
              AND p.stockorder_no IN ({$soListSql})
              AND {$whereStatusFilter}
            GROUP BY p.stockorder_no, p.status, p.locationid, p.polyhouseid, p.bed_no, p.floar_no, p.rack_no, p.row_no, p.growth_stage, p.health_status, p.movementremark, p.remarks, p.updated_by, p.created_by
            HAVING plants_qty > 0
            ORDER BY plants_qty DESC
        ";

        $res = $krfconn->query($sql);
        $details = [];
        $statusSummary = [];
        $totPlants = 0;
        $totTrays = 0;
        $foundOrders = [];

        $statusColors = [
            'readytosale'           => ['bg' => '#198754', 'text' => '#ffffff'],
            'polyhouse'             => ['bg' => '#0d6efd', 'text' => '#ffffff'],
            'move for grafting'     => ['bg' => '#6f42c1', 'text' => '#ffffff'],
            'hardening'             => ['bg' => '#fd7e14', 'text' => '#ffffff'],
            'final dispatch'        => ['bg' => '#055160', 'text' => '#ffffff'],
            'grafted'               => ['bg' => '#20c997', 'text' => '#ffffff'],
            'clip stick removal'    => ['bg' => '#d63384', 'text' => '#ffffff'],
            'openstock'             => ['bg' => '#157347', 'text' => '#ffffff'],
            'allocated'             => ['bg' => '#ffc107', 'text' => '#000000'],
            'loaded'                => ['bg' => '#0dcaf0', 'text' => '#000000'],
            'internal dispatch'     => ['bg' => '#6610f2', 'text' => '#ffffff'],
            'reverify'              => ['bg' => '#ffca2c', 'text' => '#000000'],
            'sowing'                => ['bg' => '#5c636a', 'text' => '#ffffff'],
            'readytoallocated'      => ['bg' => '#0aa2c0', 'text' => '#ffffff'],
            'readytodispatch'       => ['bg' => '#0b5ed7', 'text' => '#ffffff'],
            'healing'               => ['bg' => '#a370f7', 'text' => '#ffffff'],
            'move for opensale'     => ['bg' => '#198754', 'text' => '#ffffff'],
            'removed'               => ['bg' => '#dc3545', 'text' => '#ffffff'],
            'tray dead'             => ['bg' => '#6c757d', 'text' => '#ffffff'],
            'empty after shifting'  => ['bg' => '#eab308', 'text' => '#000000'],
            'empty after grafting'  => ['bg' => '#f97316', 'text' => '#ffffff'],
            'remove after over age' => ['bg' => '#991b1b', 'text' => '#ffffff'],
            'useforcorrection'      => ['bg' => '#0284c7', 'text' => '#ffffff'],
        ];

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $so = trim((string)$row['stockorder_no']);
                $foundOrders[$so] = true;
                $st = trim((string)$row['status']);
                if (empty($st)) $st = 'Polyhouse';
                $stKey = strtolower($st);

                $tCount = (int)$row['tray_count'];
                $pQty = (int)$row['plants_qty'];
                $totTrays += $tCount;
                $totPlants += $pQty;

                if (!isset($statusSummary[$st])) {
                    $colorInfo = $statusColors[$stKey] ?? ['bg' => '#6c757d', 'text' => '#ffffff'];
                    $statusSummary[$st] = [
                        'status'       => $st,
                        'tray_count'   => 0,
                        'plants_qty'   => 0,
                        'color'        => $colorInfo['bg'],
                        'text_color'   => $colorInfo['text']
                    ];
                }
                $statusSummary[$st]['tray_count'] += $tCount;
                $statusSummary[$st]['plants_qty'] += $pQty;

                $locId = trim((string)$row['locationid']);
                $phId = trim((string)$row['polyhouseid']);
                $locName = $locMap[$locId] ?? ($locId ?: 'N/A');
                $phName = $phMap[$phId]['name'] ?? ($phId ? "PH-{$phId}" : '-');
                $bed = trim((string)$row['bed_no']);
                $floor = trim((string)$row['floar_no']);
                $rack = trim((string)$row['rack_no']);
                $rowNum = trim((string)$row['row_no']);

                $updatedAt = !empty($row['last_updated']) ? date('d-m-Y h:i A', strtotime($row['last_updated'])) : '-';
                $updatedBy = trim((string)($row['updated_by'] ?: $row['created_by'] ?: '-'));
                $rem = trim((string)($row['movementremark'] ?: $row['remarks'] ?: '-'));

                $details[] = [
                    'stockorder_no'  => $so,
                    'status'         => $st,
                    'status_color'   => $statusColors[$stKey]['bg'] ?? '#6c757d',
                    'status_text'    => $statusColors[$stKey]['text'] ?? '#ffffff',
                    'location_name'  => $locName,
                    'polyhouse_name' => $phName,
                    'bed_no'         => $bed ?: '-',
                    'floar_no'       => $floor ?: '-',
                    'rack_no'        => $rack ?: '-',
                    'row_no'         => $rowNum ?: '-',
                    'growth_stage'   => trim((string)$row['growth_stage']) ?: '-',
                    'health_status'  => trim((string)$row['health_status']) ?: 'Healthy',
                    'tray_count'     => $tCount,
                    'plants_qty'     => $pQty,
                    'remark'         => $rem,
                    'last_updated'   => $updatedAt,
                    'updated_by'     => $updatedBy,
                    'source_type'    => 'polyhouse'
                ];
            }
        }

        // Fallback check for missing orders in sowingplant_details
        $missingCandidates = array_diff($soCandidates, array_keys($foundOrders));
        if (!empty($missingCandidates)) {
            $mEscaped = array_map(function($s) use ($krfconn) { return "'" . $krfconn->real_escape_string($s) . "'"; }, $missingCandidates);
            $mListSql = implode(',', $mEscaped);
            $sowQry = "
                SELECT
                    s.stockorder_no,
                    COALESCE(s.status, 'Sowing') AS status,
                    s.locationid,
                    s.polyhouseid,
                    s.bed_no,
                    s.floar_no,
                    s.health_status,
                    s.remarks,
                    s.updated_by,
                    s.created_by,
                    MAX(s.updated_at) AS last_updated,
                    COUNT(*) AS tray_count,
                    SUM(COALESCE(CAST(s.traycavity AS SIGNED), 126)) AS plants_qty
                FROM krf_polyhouse_sowingplant_details s
                WHERE (s.projectid = {$projectid} OR '{$projectid}' = '0' OR projectid = '17')
                  AND s.stockorder_no IN ({$mListSql})
                  AND LOWER(TRIM(COALESCE(s.status, ''))) NOT IN ('dead', 'removed', 'cancelled')
                GROUP BY s.stockorder_no, s.status, s.locationid, s.polyhouseid, s.bed_no, s.floar_no, s.health_status, s.remarks, s.updated_by, s.created_by
                HAVING plants_qty > 0
                ORDER BY plants_qty DESC
            ";
            $sowRes = $krfconn->query($sowQry);
            if ($sowRes) {
                while ($sRow = $sowRes->fetch_assoc()) {
                    $so = trim((string)$sRow['stockorder_no']);
                    $st = trim((string)$sRow['status']);
                    if (empty($st)) $st = 'Sowing';
                    $stKey = strtolower($st);

                    $tCount = (int)$sRow['tray_count'];
                    $pQty = (int)$sRow['plants_qty'];
                    $totTrays += $tCount;
                    $totPlants += $pQty;

                    if (!isset($statusSummary[$st])) {
                        $colorInfo = $statusColors[$stKey] ?? ['bg' => '#6c757d', 'text' => '#ffffff'];
                        $statusSummary[$st] = [
                            'status'       => $st,
                            'tray_count'   => 0,
                            'plants_qty'   => 0,
                            'color'        => $colorInfo['bg'],
                            'text_color'   => $colorInfo['text']
                        ];
                    }
                    $statusSummary[$st]['tray_count'] += $tCount;
                    $statusSummary[$st]['plants_qty'] += $pQty;

                    $locId = trim((string)$sRow['locationid']);
                    $phId = trim((string)$sRow['polyhouseid']);
                    $locName = $locMap[$locId] ?? ($locId ?: 'N/A');
                    $phName = $phMap[$phId]['name'] ?? ($phId ? "PH-{$phId}" : '-');
                    $bed = trim((string)$sRow['bed_no']);
                    $floor = trim((string)$sRow['floar_no']);

                    $updatedAt = !empty($sRow['last_updated']) ? date('d-m-Y h:i A', strtotime($sRow['last_updated'])) : '-';
                    $updatedBy = trim((string)($sRow['updated_by'] ?: $sRow['created_by'] ?: '-'));
                    $rem = trim((string)($sRow['remarks'] ?: 'Sowing in progress'));

                    $details[] = [
                        'stockorder_no'  => $so,
                        'status'         => $st,
                        'status_color'   => $statusColors[$stKey]['bg'] ?? '#6c757d',
                        'status_text'    => $statusColors[$stKey]['text'] ?? '#ffffff',
                        'location_name'  => $locName,
                        'polyhouse_name' => $phName,
                        'bed_no'         => $bed ?: '-',
                        'floar_no'       => $floor ?: '-',
                        'rack_no'        => '-',
                        'row_no'         => '-',
                        'growth_stage'   => 'Sowing / Germination',
                        'health_status'  => trim((string)$sRow['health_status']) ?: 'Healthy',
                        'tray_count'     => $tCount,
                        'plants_qty'     => $pQty,
                        'remark'         => $rem,
                        'last_updated'   => $updatedAt,
                        'updated_by'     => $updatedBy,
                        'source_type'    => 'sowing'
                    ];
                }
            }
        }

        // Sort status summary by plants_qty DESC
        usort($statusSummary, function($a, $b) {
            return $b['plants_qty'] <=> $a['plants_qty'];
        });

        echo json_encode([
            'status'         => true,
            'stockorder_no'  => $stockOrderNo ?: (count($soCandidates) === 1 ? $soCandidates[0] : 'Multiple Orders (' . count($soCandidates) . ')'),
            'total_plants'   => $totPlants,
            'total_trays'    => $totTrays,
            'status_summary' => array_values($statusSummary),
            'data'           => $details
        ]);
        exit;
    }

    // 5. Fetch Full Production Stock Order Details (For "Delivered with order no X" modal)
    if ($ajaxAction === 'get_stock_order_full_details') {
        $stockOrderNo = trim($_REQUEST['stockorder_no'] ?? '');
        $origStockOrderNo = trim($_REQUEST['orig_stockorder_no'] ?? '');
        if (empty($stockOrderNo)) {
            echo json_encode(['status' => false, 'message' => 'Stock Order No is required.']);
            exit;
        }

        $escSo = $krfconn->real_escape_string($stockOrderNo);

        // 1. Stock Order Details
        $stkRes = $krfconn->query("
            SELECT 
                stk.*,
                cl.client_name,
                COALESCE(cl.is_trader, 0) AS is_trader,
                p.ProductName,
                DATE_FORMAT(stk.expected_delivery_date, '%d-%m-%Y') AS display_delivery_date,
                DATE_FORMAT(stk.sowingdate, '%d-%m-%Y') AS display_sowing_date
            FROM krf_production_stockorder stk
            LEFT JOIN krf_client_master cl ON cl.projectid = stk.projectid AND cl.client_id = stk.clientid
            LEFT JOIN product_master p ON p.ProductID = stk.productid
            WHERE stk.stockorder_no = '{$escSo}'
            LIMIT 1
        ");
        $stk = $stkRes ? $stkRes->fetch_assoc() : null;

        if (!$stk) {
            echo json_encode(['status' => false, 'message' => 'Stock order not found.']);
            exit;
        }

        // Location Name
        $locName = 'Karjee';
        if (!empty($stk['sowinglocationid']) && isset($hrmconn) && ($hrmconn instanceof mysqli)) {
            $lRes = $hrmconn->query("SELECT LocationName FROM hrm_AttendanceLocationMaster WHERE LocationId = '" . $krfconn->real_escape_string($stk['sowinglocationid']) . "' LIMIT 1");
            if ($lRes && $lRow = $lRes->fetch_assoc()) {
                $locName = trim($lRow['LocationName']);
            }
        }

        // 2. Polyhouse Summary & Beds
        $phRes = $krfconn->query("
            SELECT 
                COUNT(*) as total_trays,
                SUM(GREATEST(0, COALESCE(CAST(qtypertray AS SIGNED), 126) - COALESCE(failurecount, 0))) as total_plants
            FROM krf_polyhouse_plant_details
            WHERE stockorder_no = '{$escSo}'
              AND (tedit IS NULL OR tedit = '')
        ");
        $phSum = $phRes ? $phRes->fetch_assoc() : [];
        $totPhPlants = (int)($phSum['total_plants'] ?? 0);
        $totPhTrays = (int)($phSum['total_trays'] ?? 0);

        // Beds Breakdown
        $bedRes = $krfconn->query("
            SELECT 
                polyhouseid,
                bedrackname,
                locationid,
                COUNT(*) as trays,
                SUM(GREATEST(0, COALESCE(CAST(qtypertray AS SIGNED), 126) - COALESCE(failurecount, 0))) as plants,
                status,
                health_status
            FROM krf_polyhouse_plant_details
            WHERE stockorder_no = '{$escSo}' AND (tedit IS NULL OR tedit = '')
            GROUP BY polyhouseid, bedrackname, locationid, status, health_status
            ORDER BY polyhouseid, bedrackname
        ");
        $beds = $bedRes ? $bedRes->fetch_all(MYSQLI_ASSOC) : [];

        // 3. Allocations / Consumed Details
        $allocRes = $krfconn->query("
            SELECT 
                c.id AS consumed_id,
                c.psono,
                c.sono,
                c.consumed_qty,
                c.confirmstatus,
                c.status AS consumed_status,
                c.dispatchedqty,
                DATE_FORMAT(c.dispatchedat, '%d-%m-%Y %H:%i') AS dispatched_at,
                c.remark AS consumed_remark,
                COALESCE(pso.clientname, cl.client_name, 'Karjee Farm') AS clientname,
                COALESCE(pso.mobileno, cl.phones, '-') AS mobileno,
                DATE_FORMAT(COALESCE(pso.created_at, c.created_at), '%d-%m-%Y') AS order_date,
                DATE_FORMAT(COALESCE(pso.expecteddelivery, c.created_at), '%d-%m-%Y') AS delivery_date
            FROM krf_productionorder_consumed c
            LEFT JOIN krf_provisional_sales_order pso ON pso.psono = c.psono
            LEFT JOIN krf_client_master cl ON cl.client_id = pso.clientid
            WHERE c.stockorderno = '{$escSo}'
              AND LOWER(TRIM(COALESCE(c.status, ''))) IN ('pending', 'allocated', 'dispatched', 'fulldispatch', 'partialdispatch', 'readytoload', 'readytodispatch', 'loaded')
              AND LOWER(TRIM(COALESCE(c.status, ''))) NOT IN ('cancelled', 'cancel')
              AND (c.tedit IS NULL OR c.tedit = '')
            ORDER BY c.id ASC
        ");
        $allocations = [];
        $totConfAlloc = 0;
        if ($allocRes) {
            while ($ar = $allocRes->fetch_assoc()) {
                $q = (float)($ar['consumed_qty'] ?? 0);
                if (strtolower(trim($ar['confirmstatus'] ?? '')) === 'confirmed') {
                    $totConfAlloc += $q;
                }
                $ar['consumed_qty'] = $q;
                $cSt = strtolower(trim((string)($ar['consumed_status'] ?? '')));
                $dispQ = (float)($ar['dispatchedqty'] ?? 0);
                if ($cSt === 'dispatched' || $cSt === 'fulldispatch' || ($dispQ > 0 && $dispQ >= $q)) {
                    $ar['dispatch_status'] = 'Dispatched';
                    $ar['dispatch_badge_class'] = 'bg-success text-white';
                } elseif ($cSt === 'partialdispatch' || ($dispQ > 0 && $dispQ < $q)) {
                    $ar['dispatch_status'] = 'Partial Dispatch';
                    $ar['dispatch_badge_class'] = 'bg-warning text-dark';
                } else {
                    $ar['dispatch_status'] = 'Allocated';
                    $ar['dispatch_badge_class'] = 'bg-primary-subtle text-primary border border-primary-subtle';
                }
                $allocations[] = $ar;
            }
        }

        $orderQty = (int)($stk['quantity'] ?? 0);
        $isTrader = ((int)($stk['is_trader'] ?? 0) === 1 || ($stk['clientid'] ?? '') === 'C00001');
        $bal = $isTrader ? max(0, $orderQty - $totConfAlloc) : max(0, $totPhPlants - $totConfAlloc);
        $aproxPhys = max(0, $totPhPlants - $totConfAlloc);

        // Fetch original order info if orig_stockorder_no is provided
        $origOrderInfo = null;
        if (!empty($origStockOrderNo)) {
            $origRes = $krfconn->query("
                SELECT stk.stockorder_no, stk.psono, stk.clientid, cl.client_name, stk.quantity, p.ProductName
                FROM krf_production_stockorder stk
                LEFT JOIN krf_client_master cl ON cl.projectid = stk.projectid AND cl.client_id = stk.clientid
                LEFT JOIN product_master p ON p.ProductID = stk.productid
                WHERE stk.stockorder_no = '" . $krfconn->real_escape_string($origStockOrderNo) . "'
                LIMIT 1
            ");
            if ($origRes && $oRow = $origRes->fetch_assoc()) {
                $origOrderInfo = $oRow;
            }
        }

        echo json_encode([
            'status'         => true,
            'stock_order'    => [
                'stockorder_no'          => $stk['stockorder_no'],
                'client_name'            => $stk['client_name'] ?: ($isTrader ? 'Karjee Farms (Trader)' : 'Farmer'),
                'is_trader'              => $isTrader,
                'category_label'         => $isTrader ? 'Trader Production Order' : 'Farmer Production Order',
                'product_name'           => $stk['ProductName'],
                'product_id'             => (int)$stk['productid'],
                'order_qty'              => $orderQty,
                'sowing_date'            => $stk['display_sowing_date'] ?: '-',
                'sowing_status'          => $stk['sowingstatus'] ?: '-',
                'expected_delivery_date' => $stk['display_delivery_date'] ?: '-',
                'working_status'         => $stk['workingstatus'] ?: 'Polyhouse',
                'order_status'           => $stk['status'] ?: 'Confirmed',
                'remarks'                => $stk['remarks'] ?: '',
                'location_name'          => $locName
            ],
            'polyhouse'      => [
                'total_plants' => $totPhPlants,
                'total_trays'  => $totPhTrays,
                'beds'         => $beds
            ],
            'allocations'    => $allocations,
            'metrics'        => [
                'order_qty'            => $orderQty,
                'ph_plants'            => $totPhPlants,
                'ph_trays'             => $totPhTrays,
                'conf_alloc'           => $totConfAlloc,
                'balance'              => $bal,
                'aprox_physical_stock' => $aproxPhys
            ],
            'orig_order'     => $origOrderInfo
        ]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Open Stock Product Wise | Karjee Farm</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Google Fonts & Font Awesome -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

  <!-- Bootstrap 5, Select2, SweetAlert2 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
  <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
    :root {
      --font-main: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
      --font-mono: 'JetBrains Mono', monospace;
      --primary-blue: #0d6efd;
      --dark-blue: #084298;
      --soft-bg: #f8fafc;
      --card-border: #e2e8f0;
    }

    body {
      font-family: var(--font-main);
      background-color: var(--soft-bg);
      color: #1e293b;
      margin: 0;
      padding: 0;
    }

    .openstock-page-container {
      max-width: 1480px;
      margin: 1.5rem auto 4rem auto;
      padding: 0 1.25rem;
    }

    /* Page Header Banner (Light Background, Dark Text, Responsive) */
    .openstock-header-card {
      background: #ffffff;
      color: #1e293b;
      border: 1px solid var(--card-border);
      border-radius: 14px;
      padding: 1.25rem 1.6rem;
      margin-bottom: 1.25rem;
      box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.05);
      transition: all 0.2s ease;
    }
    .openstock-header-card .header-icon-box {
      width: 46px;
      height: 46px;
      min-width: 46px;
      border-radius: 12px;
      background: #eff6ff;
      border: 1px solid #bfdbfe;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .openstock-header-card h4 {
      font-weight: 800;
      margin: 0 0 3px 0;
      font-size: 1.35rem;
      color: #0f172a;
      letter-spacing: -0.01em;
    }
    .openstock-header-card p {
      margin: 0;
      color: #64748b;
      font-size: 0.86rem;
    }

    .btn-header-csv {
      background-color: #f8fafc;
      color: #334155;
      border: 1px solid #cbd5e1;
      border-radius: 8px;
      transition: all 0.15s ease;
    }
    .btn-header-csv:hover {
      background-color: #f1f5f9;
      color: #0f172a;
      border-color: #94a3b8;
    }

    .btn-header-refresh {
      border-radius: 8px;
      box-shadow: 0 4px 12px -2px rgba(13, 110, 253, 0.4);
      transition: all 0.15s ease;
    }
    .btn-header-refresh:hover {
      transform: translateY(-1px);
      box-shadow: 0 6px 16px -2px rgba(13, 110, 253, 0.5);
    }

    /* Filter Controls Card */
    .filter-bar-card {
      background: #ffffff;
      border: 1px solid var(--card-border);
      border-radius: 12px;
      padding: 1.1rem 1.3rem;
      margin-bottom: 1.25rem;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
    }

    .form-label-custom {
      font-size: 0.82rem;
      font-weight: 700;
      color: #475569;
      margin-bottom: 5px;
      display: block;
    }

    /* Exact Existing Stock Container from Screenshot */
    .tablestockshow {
      background: #ffffff;
      border: 1px solid var(--card-border);
      border-radius: 14px;
      padding: 1.3rem;
      box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.06);
      margin-bottom: 2rem;
    }

    /* Table Scrollbar & Sticky Header & Sticky Footer */
    .tablestockshow .table-responsive {
      max-height: 520px; /* Displays max ~10-15 rows before internal scroll */
      overflow-y: auto;
      overflow-x: auto;
      border-radius: 8px;
      border: 1px solid var(--card-border);
      position: relative;
    }
    .tablestockshow .table-responsive::-webkit-scrollbar {
      width: 7px;
      height: 7px;
    }
    .tablestockshow .table-responsive::-webkit-scrollbar-track {
      background: #f1f5f9;
      border-radius: 6px;
    }
    .tablestockshow .table-responsive::-webkit-scrollbar-thumb {
      background: #0d6efd;
      border-radius: 6px;
    }

    #tblstockdatamaster {
      width: 100%;
      white-space: nowrap;
      border-collapse: separate;
      border-spacing: 0;
      font-size: 0.84rem;
    }
    #tblstockdatamaster thead th {
      position: sticky !important;
      top: 0 !important;
      z-index: 25;
      background-color: #e2f2fe !important;
      color: #052c65 !important;
      border-top: none !important;
      border-bottom: 2px solid #cbd5e1 !important;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
      font-size: 0.78rem;
      padding: 10px 12px;
      letter-spacing: 0.03em;
    }
    #tblstockdatamaster tbody td {
      padding: 9px 12px;
      vertical-align: middle;
      border-bottom: 1px solid #f1f5f9;
      color: #1e293b;
    }
    #tblstockdatamaster tbody tr:hover td {
      background-color: #f8fbff;
    }

    /* Sticky Footer Always Displayed on Scroll */
    #tblstockdatamaster tfoot th,
    #tblstockdatamaster tfoot td {
      position: sticky !important;
      bottom: 0 !important;
      z-index: 25;
      background-color: #f8fafc !important;
      color: #0f172a !important;
      border-top: 2px solid #cbd5e1 !important;
      border-bottom: 0 !important;
      box-shadow: 0 -3px 8px rgba(0, 0, 0, 0.07);
      padding: 10px 12px;
      font-size: 0.82rem;
      letter-spacing: 0.02em;
    }

    /* Responsive Media Queries */
    @media (max-width: 768px) {
      .openstock-page-container {
        padding: 0 0.75rem;
        margin: 1rem auto 3rem auto;
      }
      .openstock-header-card {
        padding: 1rem 1.15rem;
      }
      .openstock-header-card h4 {
        font-size: 1.18rem;
      }
      .openstock-header-card .header-actions {
        width: 100%;
      }
      .openstock-header-card .header-actions .btn {
        flex: 1 1 auto;
        text-align: center;
        justify-content: center;
      }
      .tablestockshow {
        padding: 0.85rem;
      }
      #tblstockdatamaster thead th,
      #tblstockdatamaster tfoot th,
      #tblstockdatamaster tbody td {
        padding: 8px 10px;
        font-size: 0.8rem;
      }
    }

    /* Badges Above Grid from Screenshot */
    .stock-badge-confirmed {
      background-color: #e7f1ff;
      color: #084298;
      border: 1px solid #b6d4fe !important;
      font-size: 0.82rem;
      font-weight: 600;
      border-radius: 6px;
    }
    .stock-badge-unconfirmed {
      background-color: #fff8e6;
      color: #78350f;
      border: 1px solid #fde68a !important;
      font-size: 0.82rem;
      font-weight: 600;
      border-radius: 6px;
    }
    .stock-badge-balance {
      background-color: #e8f5e9;
      color: #1b5e20;
      border: 1px solid #c8e6c9 !important;
      font-size: 0.82rem;
      font-weight: 600;
      border-radius: 6px;
    }
    .stock-badge-rso-movement {
      background-color: #f3e8ff;
      color: #581c87;
      border: 1px solid #d8b4fe !important;
      font-size: 0.82rem;
      font-weight: 600;
      border-radius: 6px;
    }

    .view-stock-alloc-btn {
      transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .view-stock-alloc-btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 3px 8px rgba(13, 110, 253, 0.35) !important;
    }

    .view-rso-movement-btn {
      cursor: pointer;
      transition: transform 0.15s ease, box-shadow 0.15s ease, filter 0.15s ease;
    }
    .view-rso-movement-btn:hover {
      transform: translateY(-1px) scale(1.05);
      filter: brightness(1.12);
      box-shadow: 0 3px 8px rgba(111, 66, 193, 0.45) !important;
    }

    .view-ph-plants-btn {
      cursor: pointer;
      transition: transform 0.15s ease, box-shadow 0.15s ease, filter 0.15s ease;
    }
    .view-ph-plants-btn:hover {
      transform: translateY(-1px) scale(1.05);
      filter: brightness(1.1);
      box-shadow: 0 3px 8px rgba(13, 110, 253, 0.45) !important;
    }

    .view-rej-moved-btn {
      cursor: pointer;
      transition: transform 0.15s ease, box-shadow 0.15s ease, filter 0.15s ease;
    }
    .view-rej-moved-btn:hover {
      transform: translateY(-1px) scale(1.05);
      filter: brightness(1.1);
      box-shadow: 0 3px 8px rgba(217, 119, 6, 0.45) !important;
    }

    .view-delivered-order-details-btn {
      cursor: pointer;
      transition: all 0.15s ease-in-out;
    }
    .view-delivered-order-details-btn:hover {
      transform: translateY(-1px);
      background-color: #198754 !important;
      color: #ffffff !important;
      box-shadow: 0 3px 8px rgba(25, 135, 84, 0.35) !important;
    }

    .ph-status-kpi-chip:hover {
      border-color: #0d6efd !important;
      background: #eff6ff !important;
    }
    .ph-status-kpi-chip.active {
      background: #0d6efd !important;
      color: #ffffff !important;
      border-color: #0d6efd !important;
      box-shadow: 0 2px 6px rgba(13, 110, 253, 0.35) !important;
    }

    .status-kpi-chip {
      cursor: pointer;
      border: 1px solid #e2e8f0;
      border-radius: 20px;
      padding: 0.3rem 0.75rem;
      font-size: 0.78rem;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all 0.15s ease;
      background: #ffffff;
      color: #334155;
    }
    .status-kpi-chip:hover {
      transform: translateY(-1px);
      box-shadow: 0 2px 6px rgba(0,0,0,0.08);
      border-color: #6f42c1;
      background: #faf5ff;
    }
    .status-kpi-chip.active {
      background: #6f42c1 !important;
      color: #ffffff !important;
      border-color: #6f42c1 !important;
      box-shadow: 0 2px 6px rgba(111, 66, 193, 0.35);
    }
    .status-kpi-chip.active .chip-count {
      background: rgba(255, 255, 255, 0.25);
      color: #ffffff;
    }
    .chip-count {
      background: #f1f5f9;
      color: #0f172a;
      padding: 1px 6px;
      border-radius: 12px;
      font-size: 0.72rem;
      font-family: var(--font-mono);
      font-weight: 700;
    }

    .edit-balance-btn {
      transition: transform 0.15s ease;
    }
    .edit-balance-btn:hover {
      transform: scale(1.15);
    }

    /* Product Summary Row & Expandable Container */
    #tblstockdatamaster tbody tr.product-summary-row {
      cursor: pointer;
      transition: background-color 0.15s ease;
      background-color: #ffffff;
      user-select: none;
    }
    #tblstockdatamaster tbody tr.product-summary-row:hover td {
      background-color: #f1f5f9 !important;
    }

    /* 1. EXPANDED ROW: Dark background color with white text */
    #tblstockdatamaster tbody tr.product-summary-row.is-expanded,
    #tblstockdatamaster tbody tr.product-summary-row.is-expanded td {
      background-color: #1e293b !important;
      color: #ffffff !important;
      border-color: #334155 !important;
      border-bottom: 2px solid #0284c7 !important;
    }
    #tblstockdatamaster tbody tr.product-summary-row.is-expanded:hover,
    #tblstockdatamaster tbody tr.product-summary-row.is-expanded:hover td {
      background-color: #0f172a !important;
      color: #ffffff !important;
    }
    #tblstockdatamaster tbody tr.product-summary-row.is-expanded .text-dark {
      color: #ffffff !important;
    }
    #tblstockdatamaster tbody tr.product-summary-row.is-expanded .text-muted {
      color: #94a3b8 !important;
    }
    #tblstockdatamaster tbody tr.product-summary-row.is-expanded .text-secondary {
      color: #cbd5e1 !important;
    }
    #tblstockdatamaster tbody tr.product-summary-row.is-expanded .text-success {
      color: #4ade80 !important; /* High contrast vibrant green */
    }
    #tblstockdatamaster tbody tr.product-summary-row.is-expanded td:nth-child(4) {
      color: #f8fafc !important; /* Location text */
    }
    #tblstockdatamaster tbody tr.product-summary-row.is-expanded td:nth-child(5) {
      color: #f1f5f9 !important; /* Ready Date Range text */
    }
    #tblstockdatamaster tbody tr.product-summary-row.is-expanded .badge.bg-light {
      background-color: rgba(255, 255, 255, 0.18) !important;
      color: #ffffff !important;
      border-color: rgba(255, 255, 255, 0.28) !important;
    }

    .btn-toggle-expand {
      width: 26px;
      height: 26px;
      padding: 0;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      border: 1px solid #cbd5e1;
      background: #ffffff;
      color: #0d6efd;
      font-size: 0.78rem;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .product-summary-row:hover .btn-toggle-expand {
      background: #eff6ff;
      border-color: #93c5fd;
    }
    #tblstockdatamaster tbody tr.product-summary-row.is-expanded .btn-toggle-expand {
      background: #0d6efd !important;
      color: #ffffff !important;
      border-color: #38bdf8 !important;
      transform: rotate(90deg);
      box-shadow: 0 2px 6px rgba(13, 110, 253, 0.45) !important;
    }

    /* 2. EXPANDED BODY: Light background with dark text */
    #tblstockdatamaster tbody tr.product-child-row,
    #tblstockdatamaster tbody tr.product-child-row td {
      background-color: #f8fafc !important;
      color: #0f172a !important;
      padding: 0 !important;
      border-top: none !important;
    }
    .product-nested-container {
      background: #f8fafc !important;
      border-left: 4px solid #0d6efd !important;
      border-bottom: 2px solid #cbd5e1 !important;
      box-shadow: inset 0 2px 5px rgba(15, 23, 42, 0.05);
      color: #0f172a !important;
    }
    .product-nested-container .text-dark {
      color: #0f172a !important;
    }
    .product-nested-container .text-muted {
      color: #64748b !important;
    }
    .product-nested-container .nested-order-table {
      background: #ffffff !important;
      border: 1px solid #cbd5e1 !important;
    }
    .product-nested-container .nested-order-table thead th {
      background-color: #f1f5f9 !important;
      color: #0f172a !important;
      font-size: 0.75rem;
      font-weight: 700;
      letter-spacing: 0.03em;
      border-color: #cbd5e1 !important;
      border-bottom: 2px solid #94a3b8 !important;
    }
    .product-nested-container .nested-order-table tbody tr {
      background-color: #ffffff !important;
      transition: background-color 0.15s ease;
    }
    .product-nested-container .nested-order-table tbody tr:hover td {
      background-color: #f8fbff !important;
    }
    .product-nested-container .nested-order-table tbody td {
      font-size: 0.84rem;
      color: #1e293b !important;
      border-color: #e2e8f0 !important;
      background-color: transparent !important;
    }
    .product-nested-container .nested-order-table tbody td .text-dark {
      color: #0f172a !important;
    }
    .product-nested-container .nested-order-table tbody td .text-secondary {
      color: #475569 !important;
    }

    /* Opening Balance Ledger Styling */
    .opening-balance-ledger-row {
      background-color: #fef3c7 !important;
      border-top: 2px solid #f59e0b !important;
      border-bottom: 2px solid #f59e0b !important;
      font-weight: 600;
    }
    .opening-balance-ledger-row td {
      background-color: #fef3c7 !important;
      color: #78350f !important;
    }
    .opening-detail-row {
      background-color: #fffdf5 !important;
    }
    .opening-detail-row td {
      background-color: #fffdf5 !important;
    }
    .btn-toggle-opening-batches {
      border: 1px solid #d97706;
      background: #ffffff;
      color: #92400e;
      font-size: 0.72rem;
      font-weight: 600;
      border-radius: 4px;
      padding: 1px 7px;
      transition: all 0.15s ease;
    }
    .btn-toggle-opening-batches:hover {
      background: #fde68a;
      color: #78350f;
    }
    .btn-date-preset {
      font-size: 0.75rem;
      font-weight: 600;
      border-radius: 6px;
      transition: all 0.15s ease;
    }
    .btn-date-preset.active {
      background-color: #0d6efd !important;
      color: #ffffff !important;
      border-color: #0d6efd !important;
    }
  </style>
</head>
<body data-projectid="<?= $projectid ?>" data-loginroleid="<?= $loginroleid ?>" data-loginname="<?= htmlspecialchars($loginname) ?>">

<div class="openstock-page-container">

  <!-- Header Banner (Light Background, Dark Text, Fully Responsive) -->
  <div class="openstock-header-card d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div class="d-flex align-items-center gap-3">
      <div class="header-icon-box shadow-xs">
        <i class="fa fa-boxes-stacked text-primary fs-4"></i>
      </div>
      <div>
        <h4>Open Stock Availability <span class="text-secondary fw-normal fs-6 d-none d-sm-inline">(Product Wise)</span></h4>
        <p>Real-time audit of available open production stock, client allocations & bed inventory balances.</p>
      </div>
    </div>
    <div class="header-actions d-flex align-items-center gap-2 flex-wrap">
      <button type="button" class="btn btn-sm btn-header-csv font-weight-bold px-3 py-2 d-inline-flex align-items-center" id="btnExportCSV">
        <i class="fa fa-file-csv me-1.5 text-success fs-6"></i> <span>Export CSV</span>
      </button>
      <button type="button" class="btn btn-sm btn-primary btn-header-refresh font-weight-bold px-3 py-2 d-inline-flex align-items-center" id="btnRefreshStock">
        <i class="fa fa-sync-alt me-1.5"></i> <span>Refresh Stock</span>
      </button>
    </div>
  </div>

  <!-- Filter & Selector Toolbar -->
  <div class="filter-bar-card">
    <div class="row g-3 align-items-end">
      <!-- 1. Product Selector Dropdown -->
      <div class="col-lg-4 col-md-6 col-12">
        <label class="form-label-custom">
          <i class="fa fa-seedling text-success me-1"></i> Select Product (Product Wise):
        </label>
        <select id="selectOpenStockProduct" class="form-select">
          <option value="all">Loading products...</option>
        </select>
      </div>

      <!-- 2. Order Category Filter (Trader vs Farmer/Client vs All) -->
      <div class="col-lg-3 col-md-6 col-12">
        <label class="form-label-custom">
          <i class="fa fa-user-tag text-info me-1"></i> Order Category:
        </label>
        <select id="selectTraderFilter" class="form-select">
          <option value="all">All (Trader &amp; Farmer)</option>
          <option value="trader">🏢 Only Trader (Trader = 1)</option>
          <option value="farmer">🌾 Only Client (Farmer)</option>
        </select>
      </div>

      <!-- 3. Location Filter -->
      <div class="col-lg-3 col-md-6 col-12">
        <label class="form-label-custom">
          <i class="fa fa-map-marker-alt text-danger me-1"></i> Location:
        </label>
        <select id="selectLocationFilter" class="form-select">
          <option value="all">-- All Locations --</option>
        </select>
      </div>

      <!-- 4. Stock Type Filter -->
      <div class="col-lg-2 col-md-6 col-12">
        <label class="form-label-custom">
          <i class="fa fa-layer-group text-primary me-1"></i> Stock Source:
        </label>
        <select id="selectStockTypeFilter" class="form-select">
          <option value="all">-- All Sources --</option>
          <option value="internal_stock">Karjee Farm Open Stock</option>
          <option value="client_surplus">Surplus After Dispatched</option>
          <option value="cancelled_order_stock">Surplus due to Client Order Cancelled</option>
        </select>
      </div>
    </div>

    <!-- 4. Expected Ready Date Range & Presets Toolbar -->
    <div class="row g-2 mt-2 pt-2 border-top align-items-end">
      <div class="col-lg-3 col-md-4 col-sm-6 col-12">
        <label class="form-label-custom mb-1">
          <i class="fa fa-calendar-alt text-primary me-1"></i> Expected Ready Date From:
        </label>
        <input type="date" id="filterReadyDateFrom" class="form-control form-control-sm shadow-xs" title="Expected Ready Date From (Batches before this will be counted as Opening Balance)">
      </div>
      <div class="col-lg-3 col-md-4 col-sm-6 col-12">
        <label class="form-label-custom mb-1">
          <i class="fa fa-calendar-check text-success me-1"></i> Expected Ready Date To:
        </label>
        <input type="date" id="filterReadyDateTo" class="form-control form-control-sm shadow-xs" title="Expected Ready Date To">
      </div>
      <div class="col-lg-6 col-md-4 col-12 d-flex align-items-center gap-1.5 flex-wrap">
        <span class="text-muted small fw-semibold me-1 d-none d-lg-inline">Presets:</span>
        <button type="button" class="btn btn-sm btn-outline-secondary btn-date-preset py-1 px-2" data-preset="today">Today</button>
        <button type="button" class="btn btn-sm btn-outline-secondary btn-date-preset py-1 px-2" data-preset="next7">Next 7 Days</button>
        <button type="button" class="btn btn-sm btn-outline-secondary btn-date-preset py-1 px-2" data-preset="next15">Next 15 Days</button>
        <button type="button" class="btn btn-sm btn-outline-secondary btn-date-preset py-1 px-2" data-preset="thismonth">This Month</button>
        <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2.5 ms-auto" id="btnClearDateFilter" title="Clear Date Filter and view all stock">
          <i class="fa fa-times me-1"></i>Clear Dates
        </button>
      </div>
    </div>
  </div>

  <!-- Existing Stock Table Card (Exact UI Matching Screenshot) -->
  <div class="tablestockshow">
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
      
      <!-- Section Title -->
      <h6 class="fw-bold mb-0 d-flex align-items-center border-start border-4 border-primary ps-2 text-uppercase" style="font-size: 0.95rem; letter-spacing: 0.02em;">
        <i class="fa fa-warehouse me-2 text-primary"></i> SELECT FROM EXISTING STOCK
      </h6>

      <!-- Summary Badges Above Grid (Exact Match to Screenshot) -->
      <div class="d-flex align-items-center gap-2 flex-wrap" id="stockSummaryBadgesContainer">
        <!-- Confirmed Allocation Badge -->
        <span class="badge border px-3 py-2 d-inline-flex align-items-center shadow-sm stock-badge-confirmed"
              title="Total Confirmed Allocation Quantity">
          <i class="fa fa-users me-1.5 text-primary" style="font-size: 0.85rem;"></i>
          <span style="color: #495057; font-weight: 600;" class="me-1">Total Confirm:</span>
          <strong id="badgeTotalConfirmed" style="color: #084298; font-size: 0.92rem; font-family: monospace;">0</strong>
        </span>

        <!-- Unconfirmed Allocation Badge -->
        <span class="badge border px-3 py-2 d-inline-flex align-items-center shadow-sm stock-badge-unconfirmed"
              title="Total Unconfirmed Allocation Quantity">
          <i class="fa fa-clock me-1.5 text-warning" style="font-size: 0.85rem;"></i>
          <span style="color: #495057; font-weight: 600;" class="me-1">Total Un-Confirm:</span>
          <strong id="badgeTotalUnconfirmed" style="color: #78350f; font-size: 0.92rem; font-family: monospace;">0</strong>
        </span>

        <!-- Total RSO Movement Badge -->
        <span class="badge border px-3 py-2 d-inline-flex align-items-center shadow-sm stock-badge-rso-movement"
              title="Total RSO Movement Quantity">
          <i class="fa fa-truck-ramp-box me-1.5" style="color: #7e22ce; font-size: 0.85rem;"></i>
          <span style="color: #495057; font-weight: 600;" class="me-1">Total RSO-Movement:</span>
          <strong id="badgeTotalRsoMovement" style="color: #581c87; font-size: 0.92rem; font-family: monospace;">0</strong>
        </span>

        <!-- Opening Balance Badge (Dynamically shown when Date Filter is applied) -->
        <span class="badge border px-3 py-2 d-inline-flex align-items-center shadow-sm" id="badgeOpeningBalanceContainer" style="display:none; background: #fffbeb; border-color: #fde68a !important;"
              title="Opening Balance: Stock ready prior to the selected Date From">
          <i class="fa fa-history me-1.5" style="color: #d97706; font-size: 0.85rem;"></i>
          <span style="color: #495057; font-weight: 600;" class="me-1">Opening Bal:</span>
          <strong id="badgeOpeningBalance" style="color: #b45309; font-size: 0.92rem; font-family: monospace;">0</strong>
        </span>

        <!-- Range Balance Badge (Dynamically shown when Date Filter is applied) -->
        <span class="badge border px-3 py-2 d-inline-flex align-items-center shadow-sm" id="badgeRangeBalanceContainer" style="display:none; background: #eff6ff; border-color: #bfdbfe !important;"
              title="Range Balance: Stock ready within the selected Date Range">
          <i class="fa fa-calendar-check me-1.5" style="color: #2563eb; font-size: 0.85rem;"></i>
          <span style="color: #495057; font-weight: 600;" class="me-1">Range Bal:</span>
          <strong id="badgeRangeBalance" style="color: #1d4ed8; font-size: 0.92rem; font-family: monospace;">0</strong>
        </span>

        <!-- Balance Available Quantity Badge -->
        <span class="badge border px-3 py-2 d-inline-flex align-items-center shadow-sm stock-badge-balance"
              title="Total Cumulative Available Balance Quantity (Opening + Range)">
          <i class="fa fa-layer-group me-1.5 text-success" style="font-size: 0.85rem;"></i>
          <span style="color: #495057; font-weight: 600;" class="me-1" id="lblTotalBalanceBadgeTitle">Total Balance Qty:</span>
          <strong id="badgeTotalBalance" style="color: #1b5e20; font-size: 0.92rem; font-family: monospace;">0</strong>
        </span>

        <!-- Aprox Physical Stock Badge -->
        <span class="badge border px-3 py-2 d-inline-flex align-items-center shadow-sm" style="background: #f0fdfa; border-color: #99f6e4 !important;"
              title="Total Aprox Physical Stock = PH. Plants - (REJ./MOVED + CONF. ALLOC + RSO-MOVEMENT)">
          <i class="fa fa-cubes-stacked me-1.5" style="color: #0f766e; font-size: 0.85rem;"></i>
          <span style="color: #495057; font-weight: 600;" class="me-1">Total Aprox Physical Stock:</span>
          <strong id="badgeTotalAproxPhysicalStock" style="color: #0f766e; font-size: 0.92rem; font-family: monospace;">0</strong>
        </span>
      </div>

      <!-- Quick Search Bar & Expand/Collapse Toggle -->
      <div class="d-flex align-items-center gap-2 flex-wrap" style="max-width: 440px; width: 100%;">
        <button type="button" class="btn btn-sm btn-outline-primary text-nowrap d-inline-flex align-items-center" id="btnToggleAllExpand" title="Expand or Collapse All Products">
          <i class="fa fa-angles-down me-1" id="btnToggleAllIcon"></i> <span id="btnToggleAllText">Expand All</span>
        </button>
        <div class="input-group input-group-sm flex-grow-1">
          <span class="input-group-text bg-white border-end-0"><i class="fa fa-search text-muted"></i></span>
          <input type="text" id="searchStockTableInput" class="form-control border-start-0" placeholder="Search product, order, location..." autocomplete="off">
          <button type="button" class="btn btn-outline-secondary border-start-0" id="btnClearSearch" style="display:none;" title="Clear Search">
            <i class="fa fa-times"></i>
          </button>
        </div>
      </div>

    </div>

    <!-- The Stock Table with Sticky Thead and Sticky Tfoot -->
    <div class="table-responsive shadow-sm">
      <table class="table table-hover table-bordered mb-0" id="tblstockdatamaster">
        <thead class="small text-uppercase fw-bold">
          <tr>
            <th class="text-center" style="width: 58px;">#</th>
            <th style="min-width: 200px; text-align: left;">PRODUCT NAME</th>
            <th class="text-center" style="min-width: 105px; white-space: nowrap;">ORDERS</th>
            <th style="min-width: 140px; text-align: left;">LOCATION</th>
            <th class="text-center" style="min-width: 135px; white-space: nowrap;">READY DATE RANGE</th>
            <th class="text-center" style="min-width: 95px; white-space: nowrap;">ORDER QTY</th>
            <th class="text-center text-primary" style="min-width: 95px; white-space: nowrap;">PH. PLANTS</th>
            <th class="text-center" style="min-width: 100px; white-space: nowrap; color: #d97706;">REJ. / MOVED</th>
            <th class="text-center" style="min-width: 95px; white-space: nowrap;">CONF. ALLOC</th>
            <th class="text-center" style="min-width: 105px; white-space: nowrap;">UNCONF. ALLOC</th>
            <th class="text-center" style="min-width: 110px; white-space: nowrap; color: #6f42c1;">RSO-MOVEMENT</th>
            <th class="text-center text-success" style="min-width: 110px; white-space: nowrap;">BALANCE</th>
            <th class="text-center" style="min-width: 120px; white-space: nowrap; color: #0f766e;">APROX PHYSICAL STOCK</th>
          </tr>
        </thead>
        <tbody class="align-middle">
          <tr>
            <td colspan="13" class="text-center py-4 text-primary">
              <div class="spinner-border spinner-border-sm me-2" role="status"></div>
              <span class="fw-semibold">Loading available production stock...</span>
            </td>
          </tr>
        </tbody>
        <tfoot class="fw-bold align-middle">
          <tr id="tblstockdatamaster-footer">
            <th class="text-center text-uppercase" style="width: 58px;">TOTAL</th>
            <th style="text-align: left;" id="footShowingCount">Showing: 0 Products</th>
            <th class="text-center font-monospace" id="footTotalOrdersCount" style="font-size: 0.85rem;">0 Orders</th>
            <th class="text-muted" style="font-weight: 500;">-</th>
            <th class="text-center text-muted" style="font-weight: 500;">-</th>
            <th class="text-center font-monospace" id="footTotalOrderQty" style="font-size: 0.9rem;">0</th>
            <th class="text-center font-monospace text-primary" id="footTotalPhPlants" style="font-size: 0.9rem;">0</th>
            <th class="text-center font-monospace" id="footTotalRejMovedPlants" style="color: #d97706; font-size: 0.9rem;">0</th>
            <th class="text-center font-monospace text-primary" id="footTotalConfirmed" style="font-size: 0.9rem;">0</th>
            <th class="text-center font-monospace" id="footTotalUnconfirmed" style="color: #b45309; font-size: 0.9rem;">0</th>
            <th class="text-center font-monospace" id="footTotalRsoMovement" style="color: #6f42c1; font-size: 0.9rem;">0</th>
            <th class="text-center font-monospace text-success" id="footTotalBalance" style="font-size: 0.95rem;">0</th>
            <th class="text-center font-monospace" id="footTotalAproxPhysicalStock" style="color: #0f766e; font-size: 0.95rem;">0</th>
          </tr>
        </tfoot>
      </table>
    </div>

    <!-- Bottom KPI Strip -->
    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top text-muted small flex-wrap gap-2">
      <div>
        Showing: <strong id="lblShowingCount" class="text-dark">0</strong> Products (<strong id="lblShowingOrdersCount" class="text-dark">0</strong> stock orders) &bull; <span id="lblProductTitle">-</span>
      </div>
      <div>
        Total Order Quantity: <strong id="lblTotalOrderQty" class="text-dark">0</strong> plants
      </div>
    </div>
  </div>

</div>

<!-- ========================================================================= -->
<!-- Stock Allocation Clients Modal (Confirmed vs Unconfirmed Allocations)     -->
<!-- ========================================================================= -->
<div class="modal fade" id="stockAllocClientsModal" tabindex="-1" aria-labelledby="stockAllocClientsModalLabel" aria-hidden="true" style="z-index: 1065;">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: 92vw;">
    <div class="modal-content shadow-lg border-0" style="border-radius: 12px; overflow: hidden;">
      
      <!-- Modal Header -->
      <div class="modal-header py-2.5 px-3 text-white" id="stockAllocModalHeader" style="background: linear-gradient(135deg, #0d6efd 0%, #052c65 100%);">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <div class="rounded-circle p-1.5 d-flex align-items-center justify-content-center bg-white shadow-xs" style="width: 32px; height: 32px;">
            <i class="fa fa-users text-primary" id="stockAllocModalIcon" style="font-size: 0.95rem;"></i>
          </div>
          <h6 class="modal-title mb-0 fw-bold text-white d-flex align-items-center gap-2 flex-wrap" id="stockAllocClientsModalLabel">
            <span id="allocModalTitlePrefix">Confirmed Allocations</span>:
            <span id="allocModalStockOrderNo" class="badge bg-white text-dark font-monospace px-2 py-1" style="font-size: 0.85rem;"></span>
            <span id="allocModalProductName" class="badge bg-light text-dark px-2 py-1" style="font-size: 0.8rem; font-weight: 500;"></span>
            <span id="allocModalTotalBadge" class="badge bg-warning text-dark px-2 py-1" style="font-size: 0.82rem; font-weight: 700;"></span>
          </h6>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close" onclick="$('#stockAllocClientsModal').modal('hide')"></button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body p-3 bg-light">
        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
          <div class="small text-muted" id="allocModalSubtitle">
            Showing all provisional orders and clients allocated against this production stock order.
          </div>
          <div style="max-width: 320px; width: 100%;">
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-white"><i class="fa fa-search text-muted"></i></span>
              <input type="text" id="searchAllocModalTable" class="form-control" placeholder="Filter orders or clients..." autocomplete="off">
            </div>
          </div>
        </div>

        <div class="table-responsive shadow-sm bg-white rounded border" style="max-height: 480px;">
          <table class="table table-hover table-bordered mb-0 align-middle" id="tblStockAllocClients">
            <thead class="small text-uppercase fw-bold table-light sticky-top" style="z-index: 5;">
              <tr>
                <th class="text-center" style="width: 45px;">#</th>
                <th style="min-width: 125px;">PSO No</th>
                <th style="min-width: 180px;">Client Name & Mobile</th>
                <th class="text-center" style="min-width: 100px;">Order Date</th>
                <th class="text-center" style="min-width: 110px;">Expected Delivery</th>
                <th class="text-center" style="min-width: 110px;">Alloc. Qty (Plants)</th>
                <th class="text-center" style="min-width: 105px;">Order Status</th>
                <th class="text-center" style="min-width: 135px;">Dispatch Status</th>
                <th style="min-width: 160px;">Remarks / Allocation Note</th>
                <th class="text-center col-alloc-actions" style="min-width: 190px;">Actions</th>
              </tr>
            </thead>
            <tbody id="tblStockAllocClientsBody">
              <!-- Populated dynamically via AJAX -->
            </tbody>
          </table>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer bg-white py-2 px-3 border-top d-flex justify-content-between align-items-center">
        <div class="small text-muted">
          Total Allocations: <span id="allocModalRowCount" class="fw-bold text-dark">0</span>
        </div>
        <button type="button" class="btn btn-secondary btn-sm px-4 fw-semibold" data-bs-dismiss="modal" data-dismiss="modal" onclick="$('#stockAllocClientsModal').modal('hide')">
          <i class="fa fa-times me-1"></i> Close
        </button>
      </div>

    </div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- RSO Movement Details Modal (Status-Wise Breakdown)                        -->
<!-- ========================================================================= -->
<div class="modal fade" id="rsoMovementModal" tabindex="-1" aria-labelledby="rsoMovementModalLabel" aria-hidden="true" style="z-index: 1065;">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: 92vw;">
    <div class="modal-content shadow-lg border-0" style="border-radius: 12px; overflow: hidden;">
      
      <!-- Modal Header -->
      <div class="modal-header py-2.5 px-3 text-white" id="rsoMovementModalHeader" style="background: linear-gradient(135deg, #6f42c1 0%, #3b1b68 100%);">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <div class="rounded-circle p-1.5 d-flex align-items-center justify-content-center bg-white shadow-xs" style="width: 32px; height: 32px;">
            <i class="fa fa-truck-ramp-box" style="color: #6f42c1; font-size: 0.95rem;"></i>
          </div>
          <h6 class="modal-title mb-0 fw-bold text-white d-flex align-items-center gap-2 flex-wrap" id="rsoMovementModalLabel">
            <span>RSO Movement Details (Status Wise)</span>:
            <span id="rsoModalStockOrderNo" class="badge bg-white text-dark font-monospace px-2 py-1" style="font-size: 0.85rem;"></span>
            <span id="rsoModalProductName" class="badge bg-light text-dark px-2 py-1" style="font-size: 0.8rem; font-weight: 500;"></span>
            <span id="rsoModalTotalBadge" class="badge bg-warning text-dark px-2 py-1" style="font-size: 0.82rem; font-weight: 700;"></span>
          </h6>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close" onclick="$('#rsoMovementModal').modal('hide')"></button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body p-3 bg-light">
        <!-- Top Status-wise summary badges / chips -->
        <div class="card border-0 shadow-xs mb-3 bg-white" style="border-radius: 10px;">
          <div class="card-body p-2.5">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
              <div class="small fw-bold text-muted text-uppercase" style="letter-spacing: 0.5px;">
                <i class="fa fa-layer-group text-primary me-1"></i>Status-Wise Summary:
              </div>
              <div class="small text-muted" id="rsoModalSummarySubtitle">
                Click any status chip below to quickly filter records
              </div>
            </div>
            <div class="d-flex flex-wrap gap-1.5 align-items-center" id="rsoStatusChipsContainer">
              <!-- Populated dynamically via JS -->
            </div>
          </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
          <div class="small text-muted" id="rsoModalSubtitle">
            Showing physical tray and plant movement details recorded against this stock order.
          </div>
          <div style="max-width: 320px; width: 100%;">
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-white"><i class="fa fa-search text-muted"></i></span>
              <input type="text" id="searchRsoModalTable" class="form-control" placeholder="Search status, polyhouse, remark..." autocomplete="off">
            </div>
          </div>
        </div>

        <div class="table-responsive shadow-sm bg-white rounded border" style="max-height: 480px;">
          <table class="table table-hover table-bordered mb-0 align-middle" id="tblRsoMovement">
            <thead class="small text-uppercase fw-bold table-light sticky-top" style="z-index: 5;">
              <tr>
                <th class="text-center" style="width: 45px;">#</th>
                <th style="min-width: 130px;">Prod. Order No</th>
                <th class="text-center" style="min-width: 140px;">Status</th>
                <th style="min-width: 180px;">Location / Polyhouse / Bed</th>
                <th class="text-center" style="min-width: 85px;">Trays</th>
                <th class="text-center" style="min-width: 100px; color: #6f42c1;">Plants Qty</th>
                <th style="min-width: 250px;">Movement Remark & Log</th>
                <th class="text-center" style="min-width: 140px;">Last Action By / Date</th>
              </tr>
            </thead>
            <tbody id="tblRsoMovementBody">
              <!-- Populated dynamically via AJAX -->
            </tbody>
          </table>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer bg-white py-2 px-3 border-top d-flex justify-content-between align-items-center">
        <div class="small text-muted">
          Showing: <span id="rsoModalRowCount" class="fw-bold text-dark">0</span> movement batch(es)
        </div>
        <button type="button" class="btn btn-secondary btn-sm px-4 fw-semibold" data-bs-dismiss="modal" data-dismiss="modal" onclick="$('#rsoMovementModal').modal('hide')">
          <i class="fa fa-times me-1"></i> Close
        </button>
      </div>

    </div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- Polyhouse Plants Details Modal (Status-Wise Breakdown)                    -->
<!-- ========================================================================= -->
<div class="modal fade" id="phPlantsDetailsModal" tabindex="-1" aria-labelledby="phPlantsDetailsModalLabel" aria-hidden="true" style="z-index: 1065;">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: 92vw;">
    <div class="modal-content shadow-lg border-0" style="border-radius: 12px; overflow: hidden;">
      
      <!-- Modal Header -->
      <div class="modal-header py-2.5 px-3 text-white" id="phPlantsModalHeader" style="background: linear-gradient(135deg, #0d6efd 0%, #084298 100%);">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <div class="rounded-circle p-1.5 d-flex align-items-center justify-content-center bg-white shadow-xs" style="width: 32px; height: 32px;">
            <i class="fa fa-warehouse text-primary" id="phModalHeaderIcon" style="font-size: 0.95rem;"></i>
          </div>
          <h6 class="modal-title mb-0 fw-bold text-white d-flex align-items-center gap-2 flex-wrap" id="phPlantsDetailsModalLabel">
            <span id="phModalTitlePrefix">Polyhouse Plants Details (Status Wise)</span>:
            <span id="phModalStockOrderNo" class="badge bg-white text-dark font-monospace px-2 py-1" style="font-size: 0.85rem;"></span>
            <span id="phModalProductName" class="badge bg-light text-dark px-2 py-1" style="font-size: 0.8rem; font-weight: 500;"></span>
            <span id="phModalTotalBadge" class="badge bg-warning text-dark px-2 py-1" style="font-size: 0.82rem; font-weight: 700;"></span>
          </h6>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close" onclick="$('#phPlantsDetailsModal').modal('hide')"></button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body p-3 bg-light">
        <!-- Top Status-wise summary badges / chips -->
        <div class="card border-0 shadow-xs mb-3 bg-white" style="border-radius: 10px;">
          <div class="card-body p-2.5">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
              <div class="small fw-bold text-muted text-uppercase" style="letter-spacing: 0.5px;">
                <i class="fa fa-layer-group text-primary me-1"></i>Status-Wise Summary:
              </div>
              <div class="small text-muted" id="phModalSummarySubtitle">
                Click any status chip below to quickly filter records
              </div>
            </div>
            <div class="d-flex flex-wrap gap-1.5 align-items-center" id="phStatusChipsContainer">
              <!-- Populated dynamically via JS -->
            </div>
          </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
          <div class="small text-muted" id="phModalSubtitle">
            Showing physical tray and plant distribution across polyhouses and beds.
          </div>
          <div style="max-width: 320px; width: 100%;">
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-white"><i class="fa fa-search text-muted"></i></span>
              <input type="text" id="searchPhModalTable" class="form-control" placeholder="Search status, polyhouse, bed no, remark..." autocomplete="off">
            </div>
          </div>
        </div>

        <div class="table-responsive shadow-sm bg-white rounded border" style="max-height: 480px;">
          <table class="table table-hover table-bordered mb-0 align-middle" id="tblPhPlantsDetails">
            <thead class="small text-uppercase fw-bold table-light sticky-top" style="z-index: 5;">
              <tr>
                <th class="text-center" style="width: 45px;">#</th>
                <th style="min-width: 130px;">Prod. Order No</th>
                <th class="text-center" style="min-width: 140px;">Status</th>
                <th style="min-width: 190px;">Location / Polyhouse / Bed</th>
                <th class="text-center" style="min-width: 85px;">Trays</th>
                <th class="text-center text-primary" style="min-width: 100px;">Plants Qty</th>
                <th class="text-center" style="min-width: 110px;">Health / Stage</th>
                <th style="min-width: 220px;">Remark & Movement Log</th>
                <th class="text-center" style="min-width: 140px;">Last Action By / Date</th>
              </tr>
            </thead>
            <tbody id="tblPhPlantsDetailsBody">
              <!-- Populated dynamically via AJAX -->
            </tbody>
          </table>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer bg-white py-2 px-3 border-top d-flex justify-content-between align-items-center">
        <div class="small text-muted">
          Showing: <span id="phModalRowCount" class="fw-bold text-dark">0</span> batch(es) &bull; Total: <span id="phModalFooterTotal" class="fw-bold text-primary">0</span> Plants
        </div>
        <button type="button" class="btn btn-secondary btn-sm px-4 fw-semibold" data-bs-dismiss="modal" data-dismiss="modal" onclick="$('#phPlantsDetailsModal').modal('hide')">
          <i class="fa fa-times me-1"></i> Close
        </button>
      </div>

    </div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- Production Stock Order Full Details Modal (For Cross-Order Fulfillment)    -->
<!-- ========================================================================= -->
<div class="modal fade" id="productionStockOrderDetailsModal" tabindex="-1" aria-labelledby="productionStockOrderDetailsModalLabel" aria-hidden="true" style="z-index: 1065;">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: 94vw;">
    <div class="modal-content shadow-lg border-0" style="border-radius: 12px; overflow: hidden;">
      
      <!-- Modal Header -->
      <div class="modal-header py-2.5 px-3 text-white" style="background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%);">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <div class="rounded-circle p-1.5 d-flex align-items-center justify-content-center bg-white shadow-xs" style="width: 32px; height: 32px;">
            <i class="fa fa-boxes-packing text-primary" style="font-size: 0.95rem;"></i>
          </div>
          <h6 class="modal-title mb-0 fw-bold text-white d-flex align-items-center gap-2 flex-wrap" id="productionStockOrderDetailsModalLabel">
            <span>Production Stock Order Details</span>:
            <span id="psoDetailStockOrderNo" class="badge bg-white text-dark font-monospace px-2 py-1" style="font-size: 0.85rem;"></span>
            <span id="psoDetailProductName" class="badge bg-light text-dark px-2 py-1" style="font-size: 0.8rem; font-weight: 500;"></span>
            <span id="psoDetailOrderTypeBadge" class="badge bg-info text-dark px-2 py-1" style="font-size: 0.78rem; font-weight: 600;"></span>
          </h6>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close" onclick="$('#productionStockOrderDetailsModal').modal('hide')"></button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body p-3 bg-light">
        <!-- Cross-Fulfillment Alert Notice -->
        <div id="psoDetailCrossAlert" class="alert alert-success border-success-subtle shadow-xs mb-3 py-2 px-3 d-flex align-items-center gap-2" style="border-radius: 8px;">
          <i class="fa fa-circle-check text-success fs-5"></i>
          <div>
            <div class="fw-bold text-success" id="psoDetailCrossAlertTitle">Cross-Order Fulfillment Information</div>
            <div class="small text-secondary" id="psoDetailCrossAlertText">Client order was fulfilled from this production stock order.</div>
          </div>
        </div>

        <!-- Metric KPI Cards -->
        <div class="row g-2 mb-3" id="psoDetailKpiCards">
          <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 shadow-xs h-100 bg-white" style="border-radius: 8px;">
              <div class="card-body p-2 text-center">
                <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.7rem;">Order Qty</div>
                <div class="fw-bold text-dark font-monospace fs-6" id="psoDetailKpiOrderQty">0</div>
                <div class="text-muted" style="font-size: 0.65rem;" id="psoDetailKpiClientName">-</div>
              </div>
            </div>
          </div>
          <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 shadow-xs h-100 bg-white" style="border-radius: 8px;">
              <div class="card-body p-2 text-center">
                <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.7rem;">PH. Plants</div>
                <div class="fw-bold text-primary font-monospace fs-6" id="psoDetailKpiPhPlants">0</div>
                <div class="text-muted" style="font-size: 0.65rem;" id="psoDetailKpiPhTrays">0 Trays</div>
              </div>
            </div>
          </div>
          <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 shadow-xs h-100 bg-white" style="border-radius: 8px;">
              <div class="card-body p-2 text-center">
                <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.7rem;">Conf. Alloc</div>
                <div class="fw-bold text-success font-monospace fs-6" id="psoDetailKpiConfAlloc">0</div>
                <div class="text-muted" style="font-size: 0.65rem;" id="psoDetailKpiAllocCount">0 Clients</div>
              </div>
            </div>
          </div>
          <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 shadow-xs h-100 bg-white" style="border-radius: 8px;">
              <div class="card-body p-2 text-center">
                <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.7rem;">Balance</div>
                <div class="fw-bold text-warning-emphasis font-monospace fs-6" id="psoDetailKpiBalance">0</div>
                <div class="text-muted" style="font-size: 0.65rem;">Available to Book</div>
              </div>
            </div>
          </div>
          <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 shadow-xs h-100 bg-white" style="border-radius: 8px;">
              <div class="card-body p-2 text-center">
                <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.7rem;">Physical Stock</div>
                <div class="fw-bold text-info-emphasis font-monospace fs-6" id="psoDetailKpiPhysStock">0</div>
                <div class="text-muted" style="font-size: 0.65rem;">In Polyhouse</div>
              </div>
            </div>
          </div>
          <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 shadow-xs h-100 bg-white" style="border-radius: 8px;">
              <div class="card-body p-2 text-center">
                <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.7rem;">Ready Date</div>
                <div class="fw-bold text-secondary font-monospace fs-6" id="psoDetailKpiReadyDate">-</div>
                <div class="text-muted" style="font-size: 0.65rem;" id="psoDetailKpiLocation">Karjee</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Nav Tabs for Allocations, Beds & Order Details -->
        <ul class="nav nav-pills mb-2 gap-1" id="psoDetailTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active py-1.5 px-3 btn-sm fw-semibold" id="tab-pso-allocations" data-bs-toggle="pill" data-bs-target="#pane-pso-allocations" type="button" role="tab">
              <i class="fa fa-users me-1 text-primary"></i>Client Allocations & Dispatches (<span id="psoAllocBadgeCount">0</span>)
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link py-1.5 px-3 btn-sm fw-semibold" id="tab-pso-beds" data-bs-toggle="pill" data-bs-target="#pane-pso-beds" type="button" role="tab">
              <i class="fa fa-warehouse me-1 text-info"></i>Polyhouse Beds Breakdown (<span id="psoBedsBadgeCount">0</span>)
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link py-1.5 px-3 btn-sm fw-semibold" id="tab-pso-info" data-bs-toggle="pill" data-bs-target="#pane-pso-info" type="button" role="tab">
              <i class="fa fa-circle-info me-1 text-secondary"></i>Order & Sowing Details
            </button>
          </li>
        </ul>

        <div class="tab-content">
          <!-- Allocations Pane -->
          <div class="tab-pane fade show active" id="pane-pso-allocations" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
              <div class="small text-muted">
                Showing all provisional orders and farmers fulfilled from <span class="fw-bold text-dark font-monospace" id="psoDetailAllocSoTitle"></span>:
              </div>
              <div style="max-width: 280px; width: 100%;">
                <div class="input-group input-group-sm">
                  <span class="input-group-text bg-white"><i class="fa fa-search text-muted"></i></span>
                  <input type="text" id="searchPsoAllocTable" class="form-control" placeholder="Search client, pso, status..." autocomplete="off">
                </div>
              </div>
            </div>

            <div class="table-responsive shadow-sm bg-white rounded border" style="max-height: 420px;">
              <table class="table table-hover table-bordered mb-0 align-middle" id="tblPsoDetailAllocations">
                <thead class="small text-uppercase fw-bold table-light sticky-top" style="z-index: 5;">
                  <tr>
                    <th class="text-center" style="width: 45px;">#</th>
                    <th style="min-width: 125px;">PSO No</th>
                    <th style="min-width: 180px;">Client Name & Mobile</th>
                    <th class="text-center" style="min-width: 100px;">Order Date</th>
                    <th class="text-center" style="min-width: 110px;">Expected Delivery</th>
                    <th class="text-center" style="min-width: 110px;">Alloc. Qty (Plants)</th>
                    <th class="text-center" style="min-width: 105px;">Order Status</th>
                    <th class="text-center" style="min-width: 135px;">Dispatch Status</th>
                    <th style="min-width: 200px;">Remarks / Allocation Note</th>
                  </tr>
                </thead>
                <tbody id="tblPsoDetailAllocationsBody">
                  <!-- Populated via AJAX -->
                </tbody>
              </table>
            </div>
          </div>

          <!-- Beds Pane -->
          <div class="tab-pane fade" id="pane-pso-beds" role="tabpanel">
            <div class="table-responsive shadow-sm bg-white rounded border" style="max-height: 420px;">
              <table class="table table-hover table-bordered mb-0 align-middle" id="tblPsoDetailBeds">
                <thead class="small text-uppercase fw-bold table-light sticky-top" style="z-index: 5;">
                  <tr>
                    <th class="text-center" style="width: 45px;">#</th>
                    <th>Polyhouse</th>
                    <th>Bed / Rack Name</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Health</th>
                    <th class="text-center">Trays</th>
                    <th class="text-center text-primary">Plants Qty</th>
                  </tr>
                </thead>
                <tbody id="tblPsoDetailBedsBody">
                  <!-- Populated via AJAX -->
                </tbody>
              </table>
            </div>
          </div>

          <!-- Order Info Pane -->
          <div class="tab-pane fade" id="pane-pso-info" role="tabpanel">
            <div class="card border-0 shadow-xs bg-white p-3" style="border-radius: 8px;">
              <div class="row g-3">
                <div class="col-md-6">
                  <table class="table table-sm table-borderless mb-0">
                    <tr><th class="text-muted" style="width: 160px;">Stock Order No:</th><td class="font-monospace fw-bold" id="infoPsoOrderNo">-</td></tr>
                    <tr><th class="text-muted">Client / Owner:</th><td class="fw-semibold" id="infoPsoClient">-</td></tr>
                    <tr><th class="text-muted">Category:</th><td id="infoPsoCategory">-</td></tr>
                    <tr><th class="text-muted">Product Name:</th><td class="fw-semibold text-primary" id="infoPsoProduct">-</td></tr>
                    <tr><th class="text-muted">Order Quantity:</th><td class="font-monospace fw-bold" id="infoPsoQty">-</td></tr>
                  </table>
                </div>
                <div class="col-md-6">
                  <table class="table table-sm table-borderless mb-0">
                    <tr><th class="text-muted" style="width: 160px;">Sowing Date:</th><td id="infoPsoSowDate">-</td></tr>
                    <tr><th class="text-muted">Sowing Status:</th><td id="infoPsoSowStatus">-</td></tr>
                    <tr><th class="text-muted">Delivery Date:</th><td id="infoPsoDelivDate">-</td></tr>
                    <tr><th class="text-muted">Working Status:</th><td id="infoPsoWorkingStatus">-</td></tr>
                    <tr><th class="text-muted">Remarks:</th><td class="small text-secondary" id="infoPsoRemarks">-</td></tr>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- Modal Footer -->
      <div class="modal-footer bg-white py-2 px-3 border-top d-flex justify-content-between align-items-center">
        <div class="small text-muted" id="psoDetailFooterSummary">
          Fulfilling Stock Order Details
        </div>
        <button type="button" class="btn btn-secondary btn-sm px-4 fw-semibold" data-bs-dismiss="modal" data-dismiss="modal" onclick="$('#productionStockOrderDetailsModal').modal('hide')">
          <i class="fa fa-times me-1"></i> Close
        </button>
      </div>

    </div>
  </div>
</div>

<?php 
if ($hasKey) {
    if (file_exists('../footer.php')) {
        require('../footer.php');
    }
}
?>

<!-- ========================================================================= -->
<!-- Custom JavaScript Logic for Open Stock Product Wise                        -->
<!-- ========================================================================= -->
<script>
$(document).ready(function() {
  const projectId = $('body').data('projectid') || 17;
  const loginRoleId = parseInt($('body').data('loginroleid') || 1);
  let allStockItems = [];
  let availableLocations = new Set();

  // -------------------------------------------------------------
  // 1. Initialize Product Selector Dropdown
  // -------------------------------------------------------------
  function loadProductsDropdown() {
    const selectedTrader = $('#selectTraderFilter').val() || 'all';
    $.getJSON('openstockproduct.php', { ajax_action: 'get_products_list', trader_type: selectedTrader }, function(resp) {
      if (resp && resp.status && resp.data) {
        let opts = `<option value="all">-- All Products with Open Stock (${resp.data.length} Products) --</option>`;
        resp.data.forEach(p => {
          opts += `<option value="${p.productid}">${p.productname} (${p.order_count} Orders - ${Number(p.total_qty).toLocaleString()} plants)</option>`;
        });
        $('#selectOpenStockProduct').html(opts);
        $('#selectOpenStockProduct').select2({
          theme: 'bootstrap-5',
          placeholder: 'Search and select product...'
        });
      }
    });
  }

  // -------------------------------------------------------------
  // 2. Fetch & Render Open Stock Table
  // -------------------------------------------------------------
  function fetchOpenStock() {
    const selectedProduct = $('#selectOpenStockProduct').val() || 'all';
    const selectedLocation = $('#selectLocationFilter').val() || 'all';
    const selectedTrader = $('#selectTraderFilter').val() || 'all';
    const $tbody = $('#tblstockdatamaster tbody');

    $tbody.html(`
      <tr>
        <td colspan="13" class="text-center py-4 text-primary">
          <div class="spinner-border spinner-border-sm me-2" role="status"></div>
          <span class="fw-semibold">Loading available production stock...</span>
        </td>
      </tr>
    `);

    $.ajax({
      url: 'openstockproduct.php',
      type: 'GET',
      data: {
        ajax_action: 'get_openstock_data',
        product_id: selectedProduct,
        location_id: selectedLocation,
        trader_type: selectedTrader
      },
      dataType: 'json',
      success: function(resp) {
        if (resp && resp.status && resp.data && resp.data.length > 0) {
          allStockItems = resp.data;
          availableLocations.clear();
          allStockItems.forEach(item => {
            if (item.locationname && item.locationname !== 'N/A') {
              availableLocations.add(item.locationname);
            }
          });
          populateLocationDropdown();
          renderOpenStockTable();
        } else {
          allStockItems = [];
          $tbody.html(`
            <tr>
              <td colspan="13" class="text-center py-4 text-muted fw-semibold">
                <i class="fa fa-info-circle me-1 text-primary"></i> No active open stock available for this selection.
              </td>
            </tr>
          `);
          resetTotalsToZero();
        }
      },
      error: function() {
        $tbody.html(`
          <tr>
            <td colspan="13" class="text-center py-4 text-danger fw-semibold">
              <i class="fa fa-exclamation-triangle me-1"></i> Failed to communicate with server. Please try again.
            </td>
          </tr>
        `);
        resetTotalsToZero();
      }
    });
  }

  // -------------------------------------------------------------
  // 2B. In-Memory Render Engine (Supports Date Filter & Opening Balance)
  // -------------------------------------------------------------
  function renderOpenStockTable() {
    const selectedProduct = $('#selectOpenStockProduct').val() || 'all';
    const selectedLocation = ($('#selectLocationFilter').val() || 'all').toLowerCase().trim();
    const selectedStockType = $('#selectStockTypeFilter').val() || 'all';
    const selectedTrader = $('#selectTraderFilter').val() || 'all';
    const filterFrom = ($('#filterReadyDateFrom').val() || '').trim(); // Format: YYYY-MM-DD
    const filterTo = ($('#filterReadyDateTo').val() || '').trim();     // Format: YYYY-MM-DD
    const isDateFilterActive = !!(filterFrom || filterTo);
    const $tbody = $('#tblstockdatamaster tbody');

    $tbody.empty();
    $('#searchStockTableInput').val('');
    $('#btnClearSearch').hide();
    $('#btnToggleAllIcon').attr('class', 'fa fa-angles-down me-1');
    $('#btnToggleAllText').text('Expand All');

    if (!allStockItems || allStockItems.length === 0) {
      $tbody.html(`
        <tr>
          <td colspan="13" class="text-center py-4 text-muted fw-semibold">
            <i class="fa fa-info-circle me-1 text-primary"></i> No active open stock available.
          </td>
        </tr>
      `);
      resetTotalsToZero();
      return;
    }

    // 1. Group stock items by productid
    const productGroups = {};
    const productOrder = [];

    allStockItems.forEach(item => {
      // Filter by location if specified
      if (selectedLocation !== 'all' && selectedLocation !== '') {
        const itemLoc = (item.locationname || '').toLowerCase();
        if (itemLoc.indexOf(selectedLocation) === -1) return;
      }

      // Filter by stock source/type if specified
      if (selectedStockType !== 'all' && selectedStockType !== '') {
        const itemType = item.stock_type || 'internal_stock';
        if (itemType !== selectedStockType) return;
      }

      // Filter by order category (Trader vs Farmer) if specified
      if (selectedTrader === 'trader') {
        const isTrader = (item.clientid === 'C00001' || String(item.is_trader) === '1');
        if (!isTrader) return;
      } else if (selectedTrader === 'farmer') {
        const isTrader = (item.clientid === 'C00001' || String(item.is_trader) === '1');
        if (isTrader) return;
      }

      const pid = parseInt(item.productid || 0);
      if (!productGroups[pid]) {
        productGroups[pid] = {
          productid: pid,
          productname: item.productname || 'N/A',
          all_orders: [],
          open_stock_orders: [],
          delivered_orders: [],
          sowing_pending_orders: [],
          opening_orders: [],
          in_range_orders: [],
          future_orders: [],
          locations: new Set(),
          in_range_dates: []
        };
        productOrder.push(pid);
      }

      productGroups[pid].all_orders.push(item);
      if (item.is_open_stock) {
        productGroups[pid].open_stock_orders.push(item);
      }
      const dStat = (item.delivery_status || '').toLowerCase();
      if (item.is_delivered || dStat === 'delivered' || dStat === 'partial delivered') {
        productGroups[pid].delivered_orders.push(item);
      }
      if (dStat === 'in sowing' || dStat === 'pending sowing') {
        productGroups[pid].sowing_pending_orders.push(item);
      }

      if (item.locationname && item.locationname !== 'N/A') {
        productGroups[pid].locations.add(item.locationname);
      }

      // Classify order by Expected Ready Date
      const d = item.expected_delivery_date ? item.expected_delivery_date.trim() : '';

      if (filterFrom && d && d < filterFrom) {
        // Stock ready PRIOR to filter start date = Opening Balance!
        productGroups[pid].opening_orders.push(item);
      } else if (filterTo && d && d > filterTo) {
        // Stock ready AFTER filter end date = Future
        productGroups[pid].future_orders.push(item);
      } else {
        // In-Range Stock ready within the selected window (or all active stock if no date filter)
        productGroups[pid].in_range_orders.push(item);
        if (d) {
          productGroups[pid].in_range_dates.push(d);
        }
      }
    });

    let renderedProductsCount = 0;
    let rowsHtml = '';

    productOrder.forEach(pid => {
      const group = productGroups[pid];

      // Helper: get net available physical balance (strictly as per PH. PLANTS / Aprox Physical Stock)
      const getItemPhysicalBal = (item) => {
        return Number(item.aprox_physical_stock || 0);
      };

      // Calculate Opening Balance totals (ready before filterFrom, only active open stock)
      let opBal = 0, opOrdQty = 0, opPh = 0, opRej = 0, opConf = 0, opUnconf = 0, opRso = 0, opAproxPhys = 0, opPhysicalBal = 0;
      group.opening_orders.forEach(item => {
        if (!item.is_open_stock) return;
        const finalStk = Number(item.stkqty || 0) + Number(item.readytosale_qty || 0);
        opOrdQty += finalStk;
        opPh += Number(item.total_plants || 0);
        opRej += Number(item.rejected_moved_plants || 0);
        opConf += Number(item.confirmed_allocated || 0);
        opUnconf += Number(item.unconfirmed_allocated || 0);
        opRso += Number(item.rso_movement || 0);
        opBal += Number(item.availableqty || 0);
        opAproxPhys += Number(item.aprox_physical_stock || 0);
        opPhysicalBal += getItemPhysicalBal(item);
      });

      // Calculate In-Range Period totals (ready within date range, only active open stock)
      let rngBal = 0, rngOrdQty = 0, rngPh = 0, rngRej = 0, rngConf = 0, rngUnconf = 0, rngRso = 0, rngAproxPhys = 0, rngPhysicalBal = 0;
      group.in_range_orders.forEach(item => {
        if (!item.is_open_stock) return;
        const finalStk = Number(item.stkqty || 0) + Number(item.readytosale_qty || 0);
        rngOrdQty += finalStk;
        rngPh += Number(item.total_plants || 0);
        rngRej += Number(item.rejected_moved_plants || 0);
        rngConf += Number(item.confirmed_allocated || 0);
        rngUnconf += Number(item.unconfirmed_allocated || 0);
        rngRso += Number(item.rso_movement || 0);
        rngBal += Number(item.availableqty || 0);
        rngAproxPhys += Number(item.aprox_physical_stock || 0);
        rngPhysicalBal += getItemPhysicalBal(item);
      });

      // Combined Cumulative Totals
      const totalBalance = opBal + rngBal;
      const totalPhysicalBal = opPhysicalBal + rngPhysicalBal;
      const totalOrdQty = opOrdQty + rngOrdQty;
      const totalPh = opPh + rngPh;
      const totalRej = opRej + rngRej;
      const totalConf = opConf + rngConf;
      const totalUnconf = opUnconf + rngUnconf;
      const totalRso = opRso + rngRso;
      const totalAproxPhys = opAproxPhys + rngAproxPhys;

      // Skip products with 0 total available balance and 0 in-range orders
      if (totalBalance <= 0 && group.in_range_orders.length === 0) {
        return;
      }

      renderedProductsCount++;
      const k = renderedProductsCount;

      // Format date range string
      let dateRangeStr = '-';
      if (group.in_range_dates && group.in_range_dates.length > 0) {
        const sortedDates = [...group.in_range_dates].sort();
        const minD = formatDateDisplay(sortedDates[0]);
        const maxD = formatDateDisplay(sortedDates[sortedDates.length - 1]);
        dateRangeStr = (minD === maxD) ? minD : `${minD} <span class="text-muted small">to</span> ${maxD}`;
        if (isDateFilterActive && group.opening_orders.length > 0) {
          dateRangeStr += `<div class="small text-muted" style="font-size:0.7rem;">(+ Op b/f ${formatDateDisplay(filterFrom)})</div>`;
        }
      } else if (isDateFilterActive && group.opening_orders.length > 0) {
        dateRangeStr = `<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-1.5 py-0.5" style="font-size:0.72rem;"><i class="fa fa-history me-1"></i>Op b/f ${formatDateDisplay(filterFrom)}</span>`;
      }

      // Format locations
      const locArr = Array.from(group.locations);
      const locStr = locArr.length > 0 ? locArr.join(', ') : 'N/A';

      // -----------------------------------------------------------
      // Generate Child Rows for Nested Table
      // -----------------------------------------------------------
      let childRowsHtml = '';

      // A. Opening Balance Ledger Row (if filterFrom is set and has opening orders)
      if (isDateFilterActive && filterFrom && group.opening_orders.length > 0) {
        childRowsHtml += `
          <tr class="opening-balance-ledger-row">
            <td class="text-center font-monospace small"><i class="fa fa-history text-warning-emphasis"></i></td>
            <td class="text-nowrap">
              <div class="fw-bold text-dark d-flex align-items-center gap-1.5 flex-wrap">
                <span>OPENING BALANCE</span>
                <span class="badge bg-warning text-dark border border-warning-subtle font-monospace" style="font-size:0.7rem;">&lt; ${formatDateDisplay(filterFrom)}</span>
              </div>
              <div class="mt-1">
                <button type="button" class="btn btn-toggle-opening-batches py-0 px-1.5" data-productid="${pid}">
                  <i class="fa fa-chevron-down me-1"></i><span class="btn-toggle-opening-text">View ${group.opening_orders.length} Past ${group.opening_orders.length === 1 ? 'Batch' : 'Batches'}</span>
                </button>
              </div>
            </td>
            <td class="text-muted small">Carried Forward Available Stock</td>
            <td class="text-center text-nowrap small text-muted">&lt; ${formatDateDisplay(filterFrom)}</td>
            <td class="text-center font-monospace">${Number(opOrdQty).toLocaleString()}</td>
            <td class="text-center font-monospace text-primary">${Number(opPh).toLocaleString()}</td>
            <td class="text-center font-monospace" style="color:#d97706;">${Number(opRej).toLocaleString()}</td>
            <td class="text-center font-monospace text-primary">${Number(opConf).toLocaleString()}</td>
            <td class="text-center font-monospace" style="color:#b45309;">${Number(opUnconf).toLocaleString()}</td>
            <td class="text-center font-monospace" style="color:#6f42c1;">${Number(opRso).toLocaleString()}</td>
            <td class="text-center font-monospace fw-bold text-success">${Number(opBal).toLocaleString()}</td>
            <td class="text-center font-monospace fw-bold text-primary" style="background:#e0f2fe;">${Number(opPhysicalBal).toLocaleString()}</td>
            <td class="text-center font-monospace" style="color:#0f766e;">${Number(opAproxPhys).toLocaleString()}</td>
          </tr>
        `;

        // Detailed rows for opening orders (hidden by default under the toggle button)
        let opRunningAccum = 0;
        group.opening_orders.forEach((item, oIdx) => {
          opRunningAccum += getItemPhysicalBal(item);
          childRowsHtml += renderSingleOrderRow(item, `Op-${oIdx + 1}`, true, pid, filterFrom, opRunningAccum);
        });
      }

      // B. In-Range Orders
      let runningCumulativeBal = (isDateFilterActive && filterFrom && group.opening_orders.length > 0) ? opPhysicalBal : 0;
      if (group.in_range_orders.length > 0) {
        // Sort in-range orders chronologically by expected_delivery_date
        group.in_range_orders.sort((a, b) => {
          const dA = a.expected_delivery_date || '';
          const dB = b.expected_delivery_date || '';
          return dA.localeCompare(dB);
        });

        group.in_range_orders.forEach((item, ordIdx) => {
          runningCumulativeBal += getItemPhysicalBal(item);
          childRowsHtml += renderSingleOrderRow(item, ordIdx + 1, false, pid, filterFrom, runningCumulativeBal);
        });
      } else if (isDateFilterActive && group.opening_orders.length > 0) {
        childRowsHtml += `
          <tr class="text-muted">
            <td colspan="13" class="text-center py-2.5 small" style="background:#f8fafc;">
              <i class="fa fa-info-circle me-1 text-primary"></i> No new batches maturing in this date range. Available stock is carried forward from Opening Balance.
            </td>
          </tr>
        `;
      }

      // Future Orders (if date filter active and orders exist after filterTo)
      if (group.future_orders && group.future_orders.length > 0) {
        group.future_orders.sort((a, b) => {
          const dA = a.expected_delivery_date || '';
          const dB = b.expected_delivery_date || '';
          return dA.localeCompare(dB);
        });
        group.future_orders.forEach((item, fIdx) => {
          childRowsHtml += renderSingleOrderRow(item, `Fut-${fIdx + 1}`, false, pid, filterFrom, '-');
        });
      }

      // C. Child Table Subtotal Footer
      if (isDateFilterActive && group.opening_orders.length > 0) {
        childRowsHtml += `
          <tr class="table-light border-top" style="font-weight: 700; background: #f1f5f9;">
            <td colspan="4" class="text-end text-muted small py-2">
              <span>Stock Verification Summary:</span>
              <span class="badge bg-warning text-dark border border-warning-subtle font-monospace ms-1.5" title="Opening Balance">Opening: ${Number(opBal).toLocaleString()}</span>
              <span class="text-muted mx-1">+</span>
              <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace" title="In-Range Additions">Range: ${Number(rngBal).toLocaleString()}</span>
              <span class="text-muted mx-1">=</span>
            </td>
            <td class="text-center font-monospace">${Number(totalOrdQty).toLocaleString()}</td>
            <td class="text-center font-monospace text-primary">${Number(totalPh).toLocaleString()}</td>
            <td class="text-center font-monospace" style="color:#d97706;">${Number(totalRej).toLocaleString()}</td>
            <td class="text-center font-monospace text-primary">${Number(totalConf).toLocaleString()}</td>
            <td class="text-center font-monospace" style="color:#b45309;">${Number(totalUnconf).toLocaleString()}</td>
            <td class="text-center font-monospace" style="color:#6f42c1;">${Number(totalRso).toLocaleString()}</td>
            <td class="text-center font-monospace fw-bold text-success">${Number(totalBalance).toLocaleString()}</td>
            <td class="text-center font-monospace fw-bold text-primary" style="background:#e0f2fe;">${Number(runningCumulativeBal).toLocaleString()}</td>
            <td class="text-center font-monospace" style="color:#0f766e;">${Number(totalAproxPhys).toLocaleString()}</td>
          </tr>
        `;
      }

      // -----------------------------------------------------------
      // Generate Master Product Summary Row
      // -----------------------------------------------------------
      const orderCountBadge = (isDateFilterActive && group.opening_orders.length > 0)
        ? `<span class="badge bg-light text-primary border font-monospace px-2 py-1 shadow-xs" title="${group.open_stock_orders.length} Active Open Stock Orders">${group.open_stock_orders.length} Open</span>
           <span class="badge bg-secondary-subtle text-secondary border font-monospace px-1.5 py-0.5 ms-1 shadow-xs" title="Total Production Orders for this Product: ${group.all_orders.length}"><i class="fa fa-list-ol me-1"></i>${group.all_orders.length} Total</span>
           <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-monospace px-1.5 py-0.5 ms-1 shadow-xs" title="${group.opening_orders.length} Opening Orders Ready before ${formatDateDisplay(filterFrom)}">+${group.opening_orders.length} Op</span>`
        : `<span class="badge bg-light text-primary border font-monospace px-2 py-1 shadow-xs" title="${group.open_stock_orders.length} Active Open Stock Orders"><i class="fa fa-cubes me-1"></i>${group.open_stock_orders.length} Open</span>
           <span class="badge bg-secondary-subtle text-secondary border font-monospace px-1.5 py-0.5 ms-1 shadow-xs" title="Total Production Orders for this Product: ${group.all_orders.length}"><i class="fa fa-list-ol me-1"></i>${group.all_orders.length} Total</span>`;

      const balanceCellHtml = (isDateFilterActive && opBal > 0)
        ? `<div class="fw-bold text-success font-monospace" style="font-size:0.95rem;">${Number(totalBalance).toLocaleString()}</div>
           <div class="small text-muted" style="font-size: 0.68rem; line-height: 1.2;">Op: <strong class="text-warning-emphasis">${Number(opBal).toLocaleString()}</strong> | In: <strong class="text-primary">${Number(rngBal).toLocaleString()}</strong></div>`
        : `<span class="fw-bold text-success font-monospace" style="font-size: 0.95rem;">${Number(totalBalance).toLocaleString()}</span>`;

      const allOrderNos = [...group.opening_orders, ...group.in_range_orders].map(o => o.stockorder_no).filter(Boolean).join(',');

      rowsHtml += `
        <tr class="product-summary-row"
            data-productid="${pid}"
            data-locations="${escapeHtml(locStr)}"
            data-ordercount="${group.in_range_orders.length + (isDateFilterActive ? 0 : group.opening_orders.length)}"
            data-orderqty="${totalOrdQty}"
            data-total_ph_plants="${totalPh}"
            data-total_rej_moved_plants="${totalRej}"
            data-confirmed_allocated="${totalConf}"
            data-unconfirmed_allocated="${totalUnconf}"
            data-rso_movement="${totalRso}"
            data-availableqty="${totalBalance}"
            data-opening_balance="${opBal}"
            data-range_balance="${rngBal}"
            data-total_aprox_physical_stock="${totalAproxPhys}"
            title="Click to expand/collapse orders for this product">
          
          <!-- 1. Expand Button & S.No -->
          <td class="text-center text-nowrap" style="vertical-align: middle;">
            <button type="button" class="btn-toggle-expand me-1" data-productid="${pid}">
              <i class="fa fa-chevron-right"></i>
            </button>
            <span class="text-muted font-monospace small">${k}</span>
          </td>

          <!-- 2. Product Name -->
          <td class="fw-bold text-dark" style="min-width: 200px; vertical-align: middle;">
            <div class="d-flex align-items-center flex-wrap gap-1">
              <span>${escapeHtml(group.productname)}</span>
            </div>
          </td>

          <!-- 3. Orders Count Badge -->
          <td class="text-center text-nowrap" style="vertical-align: middle;">
            ${orderCountBadge}
          </td>

          <!-- 4. Location -->
          <td class="text-nowrap small text-dark" style="vertical-align: middle;">
            <i class="fa fa-map-marker-alt text-danger me-1"></i>${escapeHtml(locStr)}
          </td>

          <!-- 5. Ready Date Range -->
          <td class="text-center text-nowrap small" style="vertical-align: middle;">
            ${dateRangeStr}
          </td>

          <!-- 6. Total Order Qty -->
          <td class="text-center fw-bold font-monospace" style="vertical-align: middle;">
            ${Number(totalOrdQty).toLocaleString()}
          </td>

          <!-- 7. Total Ph. Plants -->
          <td class="text-center" style="vertical-align: middle;">
            ${totalPh > 0 ? `
              <span class="badge text-white px-2 py-1 fw-semibold font-monospace view-ph-plants-btn"
                    style="background-color: #0d6efd; font-size: 0.82rem; cursor: pointer; box-shadow: 0 1px 3px rgba(13,110,253,0.25);"
                    data-productid="${pid}"
                    data-productname="${escapeHtml(group.productname)}"
                    data-stockordernos="${escapeHtml(allOrderNos)}"
                    data-qty="${totalPh}"
                    title="Click to view Status-Wise Polyhouse Plant Details (${Number(totalPh).toLocaleString()} Plants)">
                <i class="fa fa-warehouse me-1"></i>${Number(totalPh).toLocaleString()}
              </span>
            ` : `<span class="text-muted fw-semibold">0</span>`}
          </td>

          <!-- 8. Total Rej. / Moved Plants -->
          <td class="text-center" style="vertical-align: middle;">
            ${totalRej > 0 ? `
              <span class="badge text-white px-2 py-1 fw-semibold font-monospace view-rej-moved-btn"
                    style="background-color: #d97706; font-size: 0.82rem; cursor: pointer; box-shadow: 0 1px 3px rgba(217,119,6,0.25);"
                    data-productid="${pid}"
                    data-productname="${escapeHtml(group.productname)}"
                    data-stockordernos="${escapeHtml(allOrderNos)}"
                    data-qty="${totalRej}"
                    title="Click to view Rejected / Moved Plants (${Number(totalRej).toLocaleString()} Plants)">
                <i class="fa fa-triangle-exclamation me-1"></i>${Number(totalRej).toLocaleString()}
              </span>
            ` : `<span class="text-muted fw-semibold">0</span>`}
          </td>

          <!-- 9. Total Conf. Alloc -->
          <td class="text-center" style="vertical-align: middle;">
            ${totalConf > 0 ? `
              <span class="badge bg-primary text-white px-2 py-1 fw-semibold font-monospace" style="font-size:0.82rem;">
                ${Number(totalConf).toLocaleString()}
              </span>
            ` : `<span class="text-muted fw-semibold">0</span>`}
          </td>

          <!-- 10. Total Unconf. Alloc -->
          <td class="text-center" style="vertical-align: middle;">
            ${totalUnconf > 0 ? `
              <span class="badge bg-warning text-dark px-2 py-1 fw-semibold font-monospace" style="font-size:0.82rem; border:1px solid #d97706;">
                ${Number(totalUnconf).toLocaleString()}
              </span>
            ` : `<span class="text-muted fw-semibold">0</span>`}
          </td>

          <!-- 11. Total RSO-Movement -->
          <td class="text-center" style="vertical-align: middle;">
            ${totalRso > 0 ? `
              <span class="badge text-white px-2 py-1 fw-semibold font-monospace view-rso-movement-btn"
                    style="background-color:#6f42c1; font-size:0.82rem; cursor: pointer; box-shadow: 0 1px 3px rgba(111,66,193,0.25);"
                    data-productid="${pid}"
                    data-productname="${escapeHtml(group.productname)}"
                    data-stockordernos="${escapeHtml(allOrderNos)}"
                    data-qty="${totalRso}"
                    title="Click to view Product RSO Movement Details (${Number(totalRso).toLocaleString()} Plants)">
                <i class="fa fa-truck-ramp-box me-1"></i>${Number(totalRso).toLocaleString()}
              </span>
            ` : `<span class="text-muted fw-semibold">0</span>`}
          </td>

          <!-- 12. Total Balance -->
          <td class="text-center text-nowrap" style="vertical-align: middle;">
            ${balanceCellHtml}
          </td>

          <!-- 13. Total Aprox Physical Stock -->
          <td class="text-center" style="vertical-align: middle;">
            ${totalAproxPhys > 0 ? `
              <span class="badge text-white px-2 py-1 fw-semibold font-monospace view-physical-stock-btn"
                    style="background-color: #0f766e; font-size: 0.82rem; cursor: pointer; box-shadow: 0 1px 3px rgba(15,118,110,0.25);"
                    data-productid="${pid}"
                    data-productname="${escapeHtml(group.productname)}"
                    data-stockordernos="${escapeHtml(allOrderNos)}"
                    data-qty="${totalAproxPhys}"
                    title="Click to view Polyhouse & Bed Wise Physical Stock (${Number(totalAproxPhys).toLocaleString()} Plants)">
                <i class="fa fa-cubes-stacked me-1"></i>${Number(totalAproxPhys).toLocaleString()}
              </span>
            ` : `<span class="text-muted fw-semibold">0</span>`}
          </td>
        </tr>

        <!-- Expandable Child Row with Sub-Table -->
        <tr class="product-child-row" id="product-child-${pid}" data-productid="${pid}" style="display:none;">
          <td colspan="13" class="p-0 border-0">
            <div class="product-nested-container p-3 border-bottom">
              <div class="d-flex align-items-center justify-content-between mb-2.5 flex-wrap gap-2 pb-2 border-bottom">
                <div class="fw-bold small text-dark d-flex align-items-center flex-wrap gap-2">
                  <i class="fa fa-seedling text-success fs-6"></i>
                  <span>Stock Order Details: <strong class="text-primary">${escapeHtml(group.productname)}</strong></span>
                  <span class="badge bg-dark text-white font-monospace shadow-xs" title="Total Production Orders in System for this Product">
                    <i class="fa fa-list-check me-1"></i>${group.all_orders.length} Total Orders
                  </span>
                  <span class="badge bg-primary text-white font-monospace shadow-xs" title="Active Open Stock Orders">
                    <i class="fa fa-cubes me-1"></i>${group.open_stock_orders.length} Open Stock
                  </span>
                  ${group.delivered_orders.length > 0 ? `
                    <span class="badge bg-success text-white font-monospace shadow-xs" title="Delivered / Dispatched Orders">
                      <i class="fa fa-check-circle me-1"></i>${group.delivered_orders.length} Delivered
                    </span>
                  ` : ''}
                  ${group.sowing_pending_orders.length > 0 ? `
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-monospace shadow-xs" title="In Sowing or Pending Sowing">
                      <i class="fa fa-seedling me-1"></i>${group.sowing_pending_orders.length} Sowing / Pending
                    </span>
                  ` : ''}
                  ${(isDateFilterActive && group.opening_orders.length > 0) ? `
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-monospace shadow-xs">+${group.opening_orders.length} Opening Batches</span>
                  ` : ''}
                </div>
                
                <div class="d-flex align-items-center gap-2 flex-wrap">
                  <!-- Order Filter Tab Button Group -->
                  <div class="btn-group btn-group-sm order-tab-group" role="group" data-productid="${pid}">
                    <button type="button" class="btn btn-sm btn-primary active btn-order-filter-tab" data-filter="open_stock" data-productid="${pid}" title="Show Active Open Stock Orders only">
                      <i class="fa fa-cubes me-1"></i>Active Open Stock (${group.open_stock_orders.length})
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-order-filter-tab" data-filter="all" data-productid="${pid}" title="Show All Production Orders of this Product (Delivered, In Polyhouse, In Sowing, Pending, Cancelled)">
                      <i class="fa fa-layer-group me-1"></i>All Orders (${group.all_orders.length})
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success btn-order-filter-tab" data-filter="delivered" data-productid="${pid}" title="Show Delivered Orders">
                      <i class="fa fa-truck-ramp-box me-1"></i>Delivered (${group.delivered_orders.length})
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-warning text-dark btn-order-filter-tab" data-filter="sowing" data-productid="${pid}" title="Show Sowing & Pending Orders">
                      <i class="fa fa-seedling me-1"></i>Sowing / Pending (${group.sowing_pending_orders.length})
                    </button>
                  </div>
                  <div class="small text-muted ms-2">
                    Total Available Balance: <strong class="text-success font-monospace fs-6">${Number(totalBalance).toLocaleString()}</strong> Plants
                  </div>
                </div>
              </div>
              <div class="table-responsive shadow-xs bg-white rounded border">
                <table class="table table-sm table-hover table-bordered mb-0 align-middle nested-order-table">
                  <thead>
                    <tr>
                      <th class="text-center" style="width: 45px;">#</th>
                      <th style="min-width: 140px; text-align: left;">PROD. ORDER NO</th>
                      <th style="min-width: 180px; text-align: left;">LOCATION / POLYHOUSE</th>
                      <th class="text-center" style="min-width: 125px;">EXPECTED READY DATE</th>
                      <th class="text-center" style="min-width: 90px;">ORDER QTY</th>
                      <th class="text-center text-primary" style="min-width: 95px; white-space: nowrap;">PH. PLANTS</th>
                      <th class="text-center" style="min-width: 100px; color: #d97706; white-space: nowrap;">REJ. / MOVED</th>
                      <th class="text-center" style="min-width: 90px;">CONF. ALLOC</th>
                      <th class="text-center" style="min-width: 95px;">UNCONF. ALLOC</th>
                      <th class="text-center" style="min-width: 105px; color: #6f42c1;">RSO-MOVEMENT</th>
                      <th class="text-center text-success" style="min-width: 110px;">BALANCE</th>
                      <th class="text-center text-primary" style="min-width: 105px; white-space: nowrap; background: #e0f2fe;" title="Cumulative Running Balance: based on Net Available Physical Stock (Aprox Physical Stock) when plants exist in Polyhouse, or Order Qty Balance when pending sowing (PH Plants = 0)">RUNNING BAL</th>
                      <th class="text-center" style="min-width: 120px; white-space: nowrap; color: #0f766e;">APROX PHYSICAL STOCK</th>
                    </tr>
                  </thead>
                  <tbody>
                    ${childRowsHtml}
                    <tr class="no-tab-orders-row" style="display:none;">
                      <td colspan="13" class="text-center py-3 text-muted small">
                        <i class="fa fa-info-circle me-1 text-primary"></i> No orders found in this category for this product.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </td>
        </tr>
      `;
    });

    if (renderedProductsCount === 0) {
      $tbody.html(`
        <tr>
          <td colspan="13" class="text-center py-4 text-muted fw-semibold">
            <i class="fa fa-info-circle me-1 text-primary"></i> No active open stock available matching the current filters.
          </td>
        </tr>
      `);
      resetTotalsToZero();
      return;
    }

    $tbody.html(rowsHtml);
    updateSummaryBadges();

    const selProdText = $('#selectOpenStockProduct option:selected').text();
    $('#lblProductTitle').text(selectedProduct === 'all' ? 'All Products' : selProdText);

    // If single product selected, expand it automatically
    if (selectedProduct !== 'all' && renderedProductsCount === 1) {
      const singlePid = productOrder[0];
      $(`.product-summary-row[data-productid="${singlePid}"]`).addClass('is-expanded');
      $(`#product-child-${singlePid}`).show();
      $('#btnToggleAllIcon').attr('class', 'fa fa-angles-up me-1');
      $('#btnToggleAllText').text('Collapse All');
    }
  }

  // -------------------------------------------------------------
  // Helper: Render Single Child Order Row
  // -------------------------------------------------------------
  function renderSingleOrderRow(item, subIdx, isOpeningDetail, pid, filterFrom, runningCumulativeBal) {
    const finalStk = Number(item.stkqty || 0) + Number(item.readytosale_qty || 0);
    const isSurplus = (item.stock_type === 'client_surplus');
    const isCancelled = (item.stock_type === 'cancelled_order_stock');
    const isDeliveredWithOther = Boolean(item.is_delivered_with_other || item.stock_type === 'delivered_with_other');

    const dStat = (item.delivery_status || '').toLowerCase();
    const isOpenStock = Boolean(item.is_open_stock);
    let orderCategory = 'other';
    if (isOpenStock) {
      orderCategory = 'open_stock';
    } else if (dStat === 'delivered' || item.is_delivered) {
      orderCategory = 'delivered';
    } else if (dStat === 'in sowing' || dStat === 'pending sowing') {
      orderCategory = 'sowing';
    } else if (dStat === 'cancelled') {
      orderCategory = 'cancelled';
    }

    let deliveryBadge = '';
    if (dStat === 'delivered') {
      deliveryBadge = `
        <span class="badge bg-success text-white px-1.5 py-0.5" style="font-size:0.68rem; font-weight:600;" title="Order fully dispatched/delivered">
          <i class="fa fa-circle-check me-1"></i>Delivered
        </span>
      `;
    } else if (dStat === 'partial delivered') {
      deliveryBadge = `
        <span class="badge bg-info text-dark px-1.5 py-0.5" style="font-size:0.68rem; font-weight:600;" title="Partially dispatched">
          <i class="fa fa-truck-fast me-1"></i>Partial Delivered
        </span>
      `;
    } else if (dStat === 'in polyhouse') {
      deliveryBadge = `
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-1.5 py-0.5" style="font-size:0.68rem; font-weight:600;" title="Plants growing in polyhouse">
          <i class="fa fa-warehouse me-1"></i>In Polyhouse
        </span>
      `;
    } else if (dStat === 'in sowing') {
      deliveryBadge = `
        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-1.5 py-0.5" style="font-size:0.68rem; font-weight:600;" title="In Sowing / Germination - Not yet in polyhouse beds">
          <i class="fa fa-seedling me-1"></i>In Sowing
        </span>
      `;
    } else if (dStat === 'pending sowing') {
      deliveryBadge = `
        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-1.5 py-0.5" style="font-size:0.68rem; font-weight:600;" title="Pending sowing - 0 plants in polyhouse">
          <i class="fa fa-clock me-1"></i>Pending Sowing
        </span>
      `;
    } else if (dStat === 'cancelled') {
      deliveryBadge = `
        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-1.5 py-0.5" style="font-size:0.68rem; font-weight:600;" title="Order Cancelled / Removed">
          <i class="fa fa-ban me-1"></i>Cancelled
        </span>
      `;
    } else if (item.delivery_status) {
      deliveryBadge = `
        <span class="badge bg-light text-secondary border px-1.5 py-0.5" style="font-size:0.68rem; font-weight:500;">
          ${escapeHtml(item.delivery_status)}
        </span>
      `;
    }

    let polyhouseHtml = '<div class="small text-muted" style="font-size:0.75rem;">-</div>';
    if (item.polyhouse_details) {
      if (item.polyhouse_details.includes('<div') || item.polyhouse_details.includes('badge')) {
        polyhouseHtml = item.polyhouse_details;
      } else {
        polyhouseHtml = `
          <div class="small text-secondary mt-0.5" style="font-size:0.75rem; line-height: 1.35;">
            <i class="fa fa-warehouse text-primary me-1"></i>${item.polyhouse_details}
          </div>
        `;
      }
    } else if (Number(item.sowing_trays || 0) > 0 || Number(item.sowing_plants || 0) > 0) {
      polyhouseHtml = `
        <div class="small text-secondary mt-0.5" style="font-size:0.75rem;">
          <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle me-1" style="font-size:0.68rem; font-weight:600;">
            <i class="fa fa-seedling me-1"></i>Sowing
          </span>
          <strong>${Number(item.sowing_trays).toLocaleString()} Trays</strong> (${Number(item.sowing_plants).toLocaleString()} Plants)
        </div>
      `;
    } else if (item.polyhouse_status_label === 'Pending Sowing' || dStat === 'pending sowing') {
      polyhouseHtml = `
        <div class="mt-1">
          <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-1.5 py-0.5" style="font-size: 0.68rem; font-weight: 600;" title="Order has pending sowing status - Not yet planted in polyhouse">
            <i class="fa fa-clock me-1"></i>Pending Sowing
          </span>
        </div>
      `;
    } else if (Number(item.total_trays || 0) > 0) {
      polyhouseHtml = `
        <div class="small text-muted" style="font-size:0.75rem;">
          ${item.total_trays} Trays (${Number(item.total_plants || 0).toLocaleString()} Plants)
        </div>
      `;
    }

    const rowExtraClass = isOpeningDetail ? `opening-detail-row opening-detail-${pid}` : '';
    let rowExtraStyle = '';
    if (isOpeningDetail) {
      rowExtraStyle = 'display:none; background-color: #fffdf5;';
    } else if (!isOpenStock) {
      rowExtraStyle = 'display:none;';
    }

    return `
      <tr class="nested-order-row ${rowExtraClass}"
          style="${rowExtraStyle}"
          data-dbsno="${item.stksno}"
          data-stockorder="${escapeHtml(item.stockorder_no || '')}"
          data-productid="${pid}"
          data-productname="${escapeHtml(item.productname || '')}"
          data-is-open-stock="${isOpenStock ? '1' : '0'}"
          data-order-category="${orderCategory}"
          data-delivery-status="${escapeHtml(dStat)}"
          data-orderqty="${finalStk || 0}"
          data-total_plants="${item.total_plants || 0}"
          data-rejected_moved_plants="${item.rejected_moved_plants || 0}"
          data-confirmed_allocated="${item.confirmed_allocated}"
          data-unconfirmed_allocated="${item.unconfirmed_allocated}"
          data-rso_movement="${item.rso_movement || 0}"
          data-availableqty="${item.availableqty || 0}"
          data-stocktype="${item.stock_type || 'internal_stock'}"
          data-location="${escapeHtml(item.locationname || '')}">
        <td class="text-center text-muted font-monospace small">${subIdx}</td>
        <td class="text-nowrap font-monospace text-secondary">
          <div class="d-flex align-items-center gap-1.5 flex-wrap">
            <span class="fw-bold text-dark fs-6">${escapeHtml(item.stockorder_no || '')}</span>
            ${deliveryBadge}
          </div>
          ${isOpeningDetail ? `
            <div class="mt-0.5">
              <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-1 py-0.5" style="font-size: 0.65rem;">
                <i class="fa fa-history me-1"></i>Opening Batch (&lt; ${formatDateDisplay(filterFrom)})
              </span>
            </div>
          ` : ''}
          ${isSurplus ? `
            <div class="mt-1">
              <span class="badge bg-warning text-dark px-1.5 py-0.5" style="font-size: 0.68rem; font-weight: 600; border: 1px solid #d39e00;">
                <i class="fa fa-truck-fast me-1"></i>Surplus After Dispatched
              </span>
            </div>
            ${(item.client_name || item.clientid) ? `
              <div class="mt-1">
                <span class="badge bg-primary text-white px-2 py-0.5 text-truncate" style="font-size: 0.7rem; font-weight: 600; max-width: 175px; display: inline-block;" title="Client: ${escapeHtml(item.client_name || item.clientid)}">
                  <i class="fa fa-user me-1"></i>${escapeHtml(item.client_name || item.clientid)}
                </span>
              </div>
            ` : ''}
          ` : ''}
          ${isCancelled ? `
            <div class="mt-1">
              <span class="badge bg-danger text-white px-1.5 py-0.5" style="font-size: 0.68rem; font-weight: 600;">
                <i class="fa fa-ban me-1"></i>Surplus due to Client Order Cancelled
              </span>
            </div>
            <div class="mt-1">
              <span class="badge bg-secondary text-white px-1.5 py-0.5" style="font-size: 0.68rem; font-weight: 600;">
                <i class="fa fa-seedling me-1"></i>Sowing Started / Done
              </span>
            </div>
            ${(item.client_name || item.clientid) ? `
              <div class="mt-1">
                <span class="badge bg-info text-dark px-2 py-0.5 text-truncate" style="font-size: 0.7rem; font-weight: 600; max-width: 175px; display: inline-block;" title="Client: ${escapeHtml(item.client_name || item.clientid)}">
                  <i class="fa fa-user me-1"></i>${escapeHtml(item.client_name || item.clientid)}
                </span>
              </div>
            ` : ''}
          ` : ''}
          ${isDeliveredWithOther ? `
            <div class="mt-1 d-flex flex-column gap-1">
              <div>
                <span class="badge bg-success-subtle text-success border border-success-subtle px-1.5 py-0.5 view-delivered-order-details-btn" 
                      style="font-size: 0.68rem; font-weight: 600; cursor: pointer; text-decoration: underline;" 
                      data-stockorderno="${escapeHtml(item.fulfilled_by_stockorder || '')}"
                      data-orig-stockorder="${escapeHtml(item.stockorder_no || '')}"
                      data-productid="${item.productid || ''}"
                      data-productname="${escapeHtml(item.productname || '')}"
                      data-clientname="${escapeHtml(item.client_name || item.clientid || '')}"
                      data-orig-psono="${escapeHtml(item.psono || '')}"
                      title="Click to view details of production stock order ${escapeHtml(item.fulfilled_by_stockorder || '')}">
                  <i class="fa fa-truck-ramp-box me-1"></i>Delivered with order no ${escapeHtml(item.fulfilled_by_stockorder || '')} <i class="fa fa-external-link-alt ms-1" style="font-size: 0.6rem;"></i>
                </span>
              </div>
              ${(item.client_name || item.clientid) ? `
                <div>
                  <span class="badge border px-1.5 py-0.5 text-truncate" style="background-color: #fef3c7; color: #92400e; border-color: #fde68a; font-size: 0.68rem; font-weight: 600; max-width: 175px; display: inline-block;" title="Original Farmer/Client: ${escapeHtml(item.client_name || item.clientid)}">
                    <i class="fa fa-user me-1"></i>${escapeHtml(item.client_name || item.clientid)}
                  </span>
                </div>
              ` : ''}
            </div>
          ` : ''}
          ${(!isSurplus && !isCancelled && !isDeliveredWithOther) ? `
            <div class="mt-1 d-flex flex-wrap gap-1 align-items-center">
              ${(item.clientid === 'C00001' || String(item.is_trader) === '1') ? `
                <span class="badge border px-1.5 py-0.5" style="background-color: #e0e7ff; color: #4338ca; border-color: #c7d2fe; font-size: 0.68rem; font-weight: 600;" title="Trader Production Order">
                  <i class="fa fa-building me-1"></i>Trader
                </span>
              ` : `
                <span class="badge border px-1.5 py-0.5 text-truncate" style="background-color: #fef3c7; color: #92400e; border-color: #fde68a; font-size: 0.68rem; font-weight: 600; max-width: 175px; display: inline-block;" title="Farmer/Client Order: ${escapeHtml(item.client_name || item.clientid || '')}">
                  <i class="fa fa-user me-1"></i>${escapeHtml(item.client_name || item.clientid || 'Farmer')}
                </span>
              `}
              ${item.workingstatus ? `
                <span class="badge bg-light text-secondary border px-1.5 py-0.5" style="font-size: 0.65rem; font-weight: 500;" title="Working Status: ${escapeHtml(item.workingstatus)}">
                  ${escapeHtml(item.workingstatus)}
                </span>
              ` : (item.status ? `
                <span class="badge bg-light text-secondary border px-1.5 py-0.5" style="font-size: 0.65rem; font-weight: 500;" title="Order Status: ${escapeHtml(item.status)}">
                  ${escapeHtml(item.status)}
                </span>
              ` : '')}
            </div>
          ` : ''}
        </td>
        <td class="text-nowrap">
          <div class="fw-bold text-dark">
            <i class="fa fa-map-marker-alt text-danger me-1"></i>${escapeHtml(item.locationname || 'N/A')}
          </div>
          ${polyhouseHtml}
        </td>
        <td class="text-center text-nowrap">${escapeHtml(item.displaydate || '')}</td>
        <td class="text-center fw-semibold font-monospace">${Number(finalStk || 0).toLocaleString()}</td>
        <td class="text-center">
          ${Number(item.total_plants || 0) > 0 ? `
            <span class="badge text-white px-2 py-1 view-ph-plants-btn"
                  style="background-color: #0d6efd; font-size: 0.82rem; font-weight: 600; box-shadow: 0 1px 3px rgba(13,110,253,0.25); cursor: pointer;"
                  data-stockorderno="${escapeHtml(item.stockorder_no || '')}"
                  data-productid="${item.productid || ''}"
                  data-productname="${escapeHtml(item.productname || '')}"
                  data-qty="${item.total_plants}"
                  title="Click to view Status-Wise Polyhouse Plant Details (${Number(item.total_plants).toLocaleString()} Plants)">
              <i class="fa fa-warehouse me-1" style="font-size: 0.72rem;"></i>${Number(item.total_plants).toLocaleString()}
            </span>
          ` : `<span class="text-muted fw-semibold" title="${Number(item.sowing_plants || 0) > 0 ? 'In sowing stage - 0 plants in polyhouse' : '0 plants in polyhouse'}">0</span>`}
        </td>
        <td class="text-center">
          ${Number(item.rejected_moved_plants || 0) > 0 ? `
            <span class="badge text-white px-2 py-1 view-rej-moved-btn"
                  style="background-color: #d97706; font-size: 0.82rem; font-weight: 600; box-shadow: 0 1px 3px rgba(217,119,6,0.25); cursor: pointer;"
                  data-stockorderno="${escapeHtml(item.stockorder_no || '')}"
                  data-productid="${item.productid || ''}"
                  data-productname="${escapeHtml(item.productname || '')}"
                  data-qty="${item.rejected_moved_plants}"
                  title="Click to view Rejected / Moved Plants (${Number(item.rejected_moved_plants).toLocaleString()} Plants)">
              <i class="fa fa-triangle-exclamation me-1" style="font-size: 0.72rem;"></i>${Number(item.rejected_moved_plants).toLocaleString()}
            </span>
          ` : `<span class="text-muted fw-semibold">0</span>`}
        </td>
        <td class="text-center">
          ${Number(item.confirmed_allocated || 0) > 0 ? `
            <span class="badge bg-primary text-white px-2 py-1 view-stock-alloc-btn"
                  style="cursor: pointer; font-size: 0.82rem; font-weight: 600; box-shadow: 0 1px 3px rgba(13,110,253,0.25);"
                  data-stockorderno="${escapeHtml(item.stockorder_no || '')}"
                  data-productid="${item.productid || ''}"
                  data-productname="${escapeHtml(item.productname || '')}"
                  data-type="confirmed"
                  data-qty="${item.confirmed_allocated}"
                  title="Click to view Confirmed Allocations (${item.confirmed_allocated})">
              <i class="fa fa-users me-1" style="font-size: 0.72rem;"></i>${Number(item.confirmed_allocated).toLocaleString()}
            </span>
          ` : `<span class="text-muted fw-semibold">0</span>`}
        </td>
        <td class="text-center">
          ${Number(item.unconfirmed_allocated || 0) > 0 ? `
            <span class="badge bg-warning text-dark px-2 py-1 view-stock-alloc-btn"
                  style="cursor: pointer; font-size: 0.82rem; font-weight: 600; border: 1px solid #d97706; box-shadow: 0 1px 3px rgba(245,158,11,0.2);"
                  data-stockorderno="${escapeHtml(item.stockorder_no || '')}"
                  data-productid="${item.productid || ''}"
                  data-productname="${escapeHtml(item.productname || '')}"
                  data-type="unconfirmed"
                  data-qty="${item.unconfirmed_allocated}"
                  title="Click to view Unconfirmed Allocations (${item.unconfirmed_allocated})">
              <i class="fa fa-clock me-1" style="font-size: 0.72rem;"></i>${Number(item.unconfirmed_allocated).toLocaleString()}
            </span>
          ` : `<span class="text-muted fw-semibold">0</span>`}
        </td>
        <td class="text-center">
          ${Number(item.rso_movement || 0) > 0 ? `
            <span class="badge text-white px-2 py-1 view-rso-movement-btn"
                  style="background-color: #6f42c1; font-size: 0.82rem; font-weight: 600; box-shadow: 0 1px 3px rgba(111,66,193,0.25); cursor: pointer;"
                  data-stockorderno="${escapeHtml(item.stockorder_no || '')}"
                  data-productid="${item.productid || ''}"
                  data-productname="${escapeHtml(item.productname || '')}"
                  data-qty="${item.rso_movement}"
                  title="Click to view RSO Movement Details (${Number(item.rso_movement).toLocaleString()} Plants)">
              <i class="fa fa-truck-ramp-box me-1" style="font-size: 0.72rem;"></i>${Number(item.rso_movement).toLocaleString()}
            </span>
          ` : `<span class="text-muted fw-semibold">0</span>`}
        </td>
        <td class="text-center fw-bold text-success text-nowrap" data-availableqty="${item.availableqty || 0}">
          <span class="balance-display-val">${Number(item.availableqty || 0).toLocaleString()}</span>
          ${(loginRoleId <= 3 && isOpenStock && Number(item.total_plants || 0) > 0) ? `
            <button type="button" class="btn btn-sm btn-outline-success border-0 p-1 ms-1 edit-balance-btn"
                    style="line-height: 1; vertical-align: middle; border-radius: 4px;"
                    data-stockorderno="${escapeHtml(item.stockorder_no || '')}"
                    data-productid="${item.productid || ''}"
                    data-productname="${escapeHtml(item.productname || '')}"
                    data-projectid="${item.projectid || projectId}"
                    data-balance="${item.availableqty || 0}"
                    title="Edit Balance / Consume difference as Karjee Farm">
              <i class="fa fa-pencil-alt text-success" style="font-size: 0.8rem;"></i>
            </button>
          ` : ''}
        </td>
        <td class="text-center font-monospace fw-bold text-primary text-nowrap" style="background:#f0f9ff;">
          ${isOpenStock ? (typeof runningCumulativeBal === 'number' ? Number(runningCumulativeBal).toLocaleString() : runningCumulativeBal) : '<span class="text-muted small">-</span>'}
        </td>
        <td class="text-center text-nowrap" data-aprox-physical-stock="${item.aprox_physical_stock || 0}">
          ${Number(item.aprox_physical_stock || 0) > 0 ? `
            <span class="badge text-white px-2 py-1 view-physical-stock-btn"
                  style="background-color: #0f766e; font-size: 0.82rem; font-weight: 600; cursor: pointer; box-shadow: 0 1px 3px rgba(15,118,110,0.25);"
                  data-stockorderno="${escapeHtml(item.stockorder_no || '')}"
                  data-productid="${item.productid || ''}"
                  data-productname="${escapeHtml(item.productname || '')}"
                  data-qty="${item.aprox_physical_stock}"
                  title="Click to view Polyhouse & Bed Wise Physical Stock (${Number(item.aprox_physical_stock).toLocaleString()} Plants)">
              <i class="fa fa-cubes-stacked me-1" style="font-size: 0.72rem;"></i>${Number(item.aprox_physical_stock).toLocaleString()}
            </span>
          ` : `<span class="text-muted fw-semibold">0</span>`}
        </td>
      </tr>
    `;
  }

  // -------------------------------------------------------------
  // Helper: Reset Table Badges and Totals to 0
  // -------------------------------------------------------------
  function resetTotalsToZero() {
    $('#badgeTotalConfirmed').text('0');
    $('#badgeTotalUnconfirmed').text('0');
    $('#badgeTotalRsoMovement').text('0');
    $('#badgeTotalBalance').text('0');
    $('#badgeOpeningBalance').text('0');
    $('#badgeRangeBalance').text('0');
    $('#badgeOpeningBalanceContainer').hide();
    $('#badgeRangeBalanceContainer').hide();
    $('#lblTotalBalanceBadgeTitle').text('Total Balance Qty:');
    $('#badgeTotalAproxPhysicalStock').text('0');
    $('#lblShowingCount').text('0');
    $('#lblShowingOrdersCount').text('0');
    $('#lblTotalOrderQty').text('0');
    $('#footShowingCount').text('Showing: 0 Products');
    $('#footTotalOrdersCount').text('0 Orders');
    $('#footTotalOrderQty').text('0');
    $('#footTotalPhPlants').text('0');
    $('#footTotalRejMovedPlants').text('0');
    $('#footTotalConfirmed').text('0');
    $('#footTotalUnconfirmed').text('0');
    $('#footTotalRsoMovement').text('0');
    $('#footTotalBalance').text('0');
    $('#footTotalAproxPhysicalStock').text('0');
  }

  // Helper date formatter: converts 'yyyy-mm-dd' to 'dd-mm-yyyy'
  function formatDateDisplay(dStr) {
    if (!dStr) return '';
    const parts = dStr.split('-');
    if (parts.length === 3 && parts[0].length === 4) {
      return parts[2] + '-' + parts[1] + '-' + parts[0];
    }
    return dStr;
  }

  // Populate Location Dropdown from observed locations
  function populateLocationDropdown() {
    const curVal = $('#selectLocationFilter').val();
    let opts = '<option value="all">-- All Locations --</option>';
    Array.from(availableLocations).sort().forEach(loc => {
      opts += `<option value="${escapeHtml(loc)}">${escapeHtml(loc)}</option>`;
    });
    $('#selectLocationFilter').html(opts);
    if (curVal && availableLocations.has(curVal)) {
      $('#selectLocationFilter').val(curVal);
    }
  }

  // -------------------------------------------------------------
  // 3. Update Summary Badges & Sticky Table Footer Totals
  // -------------------------------------------------------------
  function updateSummaryBadges() {
    let totConf = 0;
    let totUnconf = 0;
    let totRsoMv = 0;
    let totBal = 0;
    let totOpeningBal = 0;
    let totRangeBal = 0;
    let totAproxPhys = 0;
    let totOrdQty = 0;
    let totPhPlants = 0;
    let totRejMoved = 0;
    let visibleProductsCount = 0;
    let visibleOrdersCount = 0;

    $('.product-summary-row').each(function() {
      const $prodRow = $(this);
      if ($prodRow.is(':hidden')) return;

      visibleProductsCount++;
      visibleOrdersCount += Number($prodRow.attr('data-ordercount') || 0);
      totConf += Number($prodRow.attr('data-confirmed_allocated') || 0);
      totUnconf += Number($prodRow.attr('data-unconfirmed_allocated') || 0);
      totRsoMv += Number($prodRow.attr('data-rso_movement') || 0);
      totBal += Number($prodRow.attr('data-availableqty') || 0);
      totOpeningBal += Number($prodRow.attr('data-opening_balance') || 0);
      totRangeBal += Number($prodRow.attr('data-range_balance') || 0);
      totAproxPhys += Number($prodRow.attr('data-total_aprox_physical_stock') || 0);
      totOrdQty += Number($prodRow.attr('data-orderqty') || 0);
      totPhPlants += Number($prodRow.attr('data-total_ph_plants') || 0);
      totRejMoved += Number($prodRow.attr('data-total_rej_moved_plants') || 0);
    });

    const filterFrom = ($('#filterReadyDateFrom').val() || '').trim();
    const filterTo = ($('#filterReadyDateTo').val() || '').trim();
    const isDateFilterActive = !!(filterFrom || filterTo);

    if (isDateFilterActive && filterFrom) {
      $('#badgeOpeningBalanceContainer').show();
      $('#badgeOpeningBalance').text(totOpeningBal.toLocaleString());
      $('#badgeRangeBalanceContainer').show();
      $('#badgeRangeBalance').text(totRangeBal.toLocaleString());
      $('#lblTotalBalanceBadgeTitle').text('Total Cumulative Bal:');
    } else {
      $('#badgeOpeningBalanceContainer').hide();
      $('#badgeRangeBalanceContainer').hide();
      $('#lblTotalBalanceBadgeTitle').text('Total Balance Qty:');
    }

    // Top Summary Badges
    $('#badgeTotalConfirmed').text(totConf.toLocaleString());
    $('#badgeTotalUnconfirmed').text(totUnconf.toLocaleString());
    $('#badgeTotalRsoMovement').text(totRsoMv.toLocaleString());
    $('#badgeTotalBalance').text(totBal.toLocaleString());
    $('#badgeTotalAproxPhysicalStock').text(totAproxPhys.toLocaleString());
    $('#lblShowingCount').text(visibleProductsCount.toLocaleString());
    $('#lblShowingOrdersCount').text(visibleOrdersCount.toLocaleString());
    $('#lblTotalOrderQty').text(totOrdQty.toLocaleString());

    // Sticky Table Footer Row (Always displayed on scroll)
    $('#footShowingCount').html(`<span class="badge bg-secondary text-white px-2 py-1">${visibleProductsCount.toLocaleString()} Products</span>`);
    $('#footTotalOrdersCount').html(`<span class="badge bg-light text-dark border font-monospace px-2 py-1">${visibleOrdersCount.toLocaleString()} Orders</span>`);
    $('#footTotalOrderQty').text(totOrdQty.toLocaleString());
    $('#footTotalPhPlants').text(totPhPlants.toLocaleString());
    $('#footTotalRejMovedPlants').text(totRejMoved.toLocaleString());
    $('#footTotalConfirmed').text(totConf.toLocaleString());
    $('#footTotalUnconfirmed').text(totUnconf.toLocaleString());
    $('#footTotalRsoMovement').text(totRsoMv.toLocaleString());
    $('#footTotalBalance').text(totBal.toLocaleString());
    $('#footTotalAproxPhysicalStock').text(totAproxPhys.toLocaleString());
  }

  // -------------------------------------------------------------
  // 4. Expand / Collapse Handlers
  // -------------------------------------------------------------
  // Click on product summary row or expand button
  $(document).on('click', '.product-summary-row', function(e) {
    if ($(e.target).closest('button, a, input, select, .view-stock-alloc-btn, .view-rso-movement-btn, .view-ph-plants-btn, .view-rej-moved-btn, .view-physical-stock-btn, .view-delivered-order-details-btn').length > 0 && !$(e.target).closest('.btn-toggle-expand').length) {
      return;
    }
    const pid = $(this).data('productid');
    const $childRow = $(`#product-child-${pid}`);
    const isExp = $(this).hasClass('is-expanded');

    if (isExp) {
      $(this).removeClass('is-expanded');
      $childRow.hide();
    } else {
      $(this).addClass('is-expanded');
      $childRow.show();
    }
  });

  // Expand All / Collapse All Toggle Button
  $('#btnToggleAllExpand').on('click', function() {
    const $childRows = $('.product-child-row');
    const anyHidden = $childRows.filter(':hidden').length > 0;

    if (anyHidden) {
      $('.product-summary-row:visible').addClass('is-expanded');
      $childRows.filter(function() {
        const pid = $(this).data('productid');
        return $(`.product-summary-row[data-productid="${pid}"]:visible`).length > 0;
      }).show();
      $('#btnToggleAllIcon').attr('class', 'fa fa-angles-up me-1');
      $('#btnToggleAllText').text('Collapse All');
    } else {
      $('.product-summary-row').removeClass('is-expanded');
      $childRows.hide();
      $('#btnToggleAllIcon').attr('class', 'fa fa-angles-down me-1');
      $('#btnToggleAllText').text('Expand All');
    }
  });

  // Toggle past opening batches inside child table
  $(document).on('click', '.btn-toggle-opening-batches', function(e) {
    e.stopPropagation();
    const pid = $(this).data('productid');
    const $detailRows = $(`.opening-detail-${pid}`);
    const isHidden = $detailRows.is(':hidden');

    $detailRows.toggle(isHidden);
    const $icon = $(this).find('i');
    const $text = $(this).find('.btn-toggle-opening-text');
    const count = $detailRows.length;

    if (isHidden) {
      $icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
      $text.text(`Hide ${count} Past ${count === 1 ? 'Batch' : 'Batches'}`);
    } else {
      $icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
      $text.text(`View ${count} Past ${count === 1 ? 'Batch' : 'Batches'}`);
    }
  });

  // -------------------------------------------------------------
  // Filter Child Order Rows by Selected Tab (Open Stock, All, Delivered, Sowing)
  // -------------------------------------------------------------
  function applyOrderTabFilter(pid) {
    const $childRow = $(`#product-child-${pid}`);
    if ($childRow.length === 0) return;

    const $activeBtn = $childRow.find('.btn-order-filter-tab.active');
    const filter = $activeBtn.length ? $activeBtn.data('filter') : 'open_stock';
    const $rows = $childRow.find('.nested-order-row');
    const $openingLedger = $childRow.find('.opening-balance-ledger-row');
    let visibleCount = 0;

    $rows.each(function() {
      const $row = $(this);
      const isOpen = ($row.data('is-open-stock') == '1');
      const cat = $row.data('order-category');
      const dStat = ($row.data('delivery-status') || '').toLowerCase();
      const isOp = $row.hasClass('opening-detail-row');

      if (isOp && filter !== 'all') {
        $row.hide();
        return;
      }

      let show = false;
      if (filter === 'all') {
        show = true;
      } else if (filter === 'open_stock') {
        show = isOpen;
      } else if (filter === 'delivered') {
        show = (cat === 'delivered' || dStat === 'delivered' || dStat === 'partial delivered');
      } else if (filter === 'sowing') {
        show = (cat === 'sowing' || dStat === 'in sowing' || dStat === 'pending sowing');
      } else if (filter === 'cancelled') {
        show = (cat === 'cancelled' || dStat === 'cancelled');
      }

      if (show) {
        $row.show();
        visibleCount++;
      } else {
        $row.hide();
      }
    });

    if (filter === 'open_stock' || filter === 'all') {
      $openingLedger.show();
    } else {
      $openingLedger.hide();
    }

    const $emptyMsg = $childRow.find('.no-tab-orders-row');
    if (visibleCount === 0) {
      $emptyMsg.show();
    } else {
      $emptyMsg.hide();
    }
  }

  // Click on order filter tab buttons
  $(document).on('click', '.btn-order-filter-tab', function(e) {
    e.stopPropagation();
    const pid = $(this).data('productid');
    const filter = $(this).data('filter');
    const $tabGroup = $(this).closest('.order-tab-group');

    $tabGroup.find('.btn-order-filter-tab').removeClass('active btn-primary btn-secondary btn-success btn-warning text-dark text-white')
      .addClass('btn-outline-secondary');

    $(this).removeClass('btn-outline-secondary').addClass('active');
    if (filter === 'open_stock') {
      $(this).addClass('btn-primary text-white');
    } else if (filter === 'all') {
      $(this).addClass('btn-secondary text-white');
    } else if (filter === 'delivered') {
      $(this).addClass('btn-success text-white');
    } else if (filter === 'sowing') {
      $(this).addClass('btn-warning text-dark');
    }

    applyOrderTabFilter(pid);
  });

  // -------------------------------------------------------------
  // 5. Live Table Search Filter (Searches Product Name & Orders)
  // -------------------------------------------------------------
  $('#searchStockTableInput').on('input', function() {
    const filter = $(this).val().toLowerCase().trim();
    $('#btnClearSearch').toggle(filter.length > 0);

    if (!filter) {
      $('.product-summary-row').show();
      $('.nested-order-row').removeClass('filter-hidden');
      $('.product-summary-row').each(function() {
        applyOrderTabFilter($(this).data('productid'));
      });
      $('.product-summary-row').removeClass('is-expanded');
      $('.product-child-row').hide();
      $('#btnToggleAllIcon').attr('class', 'fa fa-angles-down me-1');
      $('#btnToggleAllText').text('Expand All');
      updateSummaryBadges();
      return;
    }

    $('.product-summary-row').each(function() {
      const $prodRow = $(this);
      const pid = $prodRow.data('productid');
      const $childRow = $(`#product-child-${pid}`);
      const prodName = ($prodRow.find('td:eq(1)').text() || '').toLowerCase();
      const prodLoc = ($prodRow.attr('data-locations') || '').toLowerCase();

      const productMatched = (prodName.indexOf(filter) > -1 || prodLoc.indexOf(filter) > -1);
      let childMatchedCount = 0;

      $childRow.find('.nested-order-row').each(function() {
        const $ordRow = $(this);
        const ordText = $ordRow.text().toLowerCase();
        const ordMatched = (ordText.indexOf(filter) > -1);
        const shouldShow = (ordMatched || productMatched);
        $ordRow.toggleClass('filter-hidden', !shouldShow).toggle(shouldShow);
        if (shouldShow) {
          childMatchedCount++;
        }
      });

      if (productMatched || childMatchedCount > 0) {
        $prodRow.show();
        $prodRow.addClass('is-expanded');
        $childRow.show();
      } else {
        $prodRow.hide();
        $prodRow.removeClass('is-expanded');
        $childRow.hide();
      }
    });

    updateSummaryBadges();
  });

  $('#btnClearSearch').on('click', function() {
    $('#searchStockTableInput').val('').trigger('input').focus();
  });

  // -------------------------------------------------------------
  // 6. Event Listeners for Filters (Date, Presets, Locations, Source)
  // -------------------------------------------------------------
  $('#selectOpenStockProduct').on('change', fetchOpenStock);

  // Preset dates generator
  function getPresetDates(preset) {
    const now = new Date();
    const yyyy = now.getFullYear();
    const mm = String(now.getMonth() + 1).padStart(2, '0');
    const dd = String(now.getDate()).padStart(2, '0');
    const todayStr = `${yyyy}-${mm}-${dd}`;

    if (preset === 'today') {
      return { from: todayStr, to: todayStr };
    }
    if (preset === 'next7') {
      const end = new Date();
      end.setDate(end.getDate() + 7);
      const endStr = `${end.getFullYear()}-${String(end.getMonth() + 1).padStart(2, '0')}-${String(end.getDate()).padStart(2, '0')}`;
      return { from: todayStr, to: endStr };
    }
    if (preset === 'next15') {
      const end = new Date();
      end.setDate(end.getDate() + 15);
      const endStr = `${end.getFullYear()}-${String(end.getMonth() + 1).padStart(2, '0')}-${String(end.getDate()).padStart(2, '0')}`;
      return { from: todayStr, to: endStr };
    }
    if (preset === 'thismonth') {
      const firstDay = `${yyyy}-${mm}-01`;
      const lastDayObj = new Date(yyyy, now.getMonth() + 1, 0);
      const lastDayStr = `${yyyy}-${mm}-${String(lastDayObj.getDate()).padStart(2, '0')}`;
      return { from: firstDay, to: lastDayStr };
    }
    return { from: '', to: '' };
  }

  // Click on date preset buttons
  $(document).on('click', '.btn-date-preset', function() {
    const preset = $(this).data('preset');
    const dates = getPresetDates(preset);
    $('#filterReadyDateFrom').val(dates.from);
    $('#filterReadyDateTo').val(dates.to);
    $('.btn-date-preset').removeClass('active btn-primary text-white').addClass('btn-outline-secondary');
    $(this).removeClass('btn-outline-secondary').addClass('active btn-primary text-white');
    renderOpenStockTable();
  });

  // Clear date filter button
  $('#btnClearDateFilter').on('click', function() {
    $('#filterReadyDateFrom').val('');
    $('#filterReadyDateTo').val('');
    $('.btn-date-preset').removeClass('active btn-primary text-white').addClass('btn-outline-secondary');
    renderOpenStockTable();
  });

  // Change on date inputs
  $('#filterReadyDateFrom, #filterReadyDateTo').on('change', function() {
    $('.btn-date-preset').removeClass('active btn-primary text-white').addClass('btn-outline-secondary');
    renderOpenStockTable();
  });

  // Location filter change
  $('#selectLocationFilter').on('change', function() {
    renderOpenStockTable();
  });

  // Stock Type filter change
  $('#selectStockTypeFilter').on('change', function() {
    renderOpenStockTable();
  });

  // Trader / Farmer category filter change
  $('#selectTraderFilter').on('change', function() {
    loadProductsDropdown();
    fetchOpenStock();
  });

  $('#btnRefreshStock').on('click', function() {
    fetchOpenStock();
  });

  // -------------------------------------------------------------
  // 5. Click View Confirmed / Unconfirmed Allocations Modal
  // -------------------------------------------------------------
  let currentAllocStockOrder = '';
  let currentAllocProductId = 0;
  let currentAllocProductName = '';
  let currentAllocType = '';

  $(document).on('click', '.view-stock-alloc-btn', function() {
    const $btn = $(this);
    currentAllocStockOrder = $btn.data('stockorderno');
    currentAllocProductId = $btn.data('productid');
    currentAllocProductName = $btn.data('productname');
    currentAllocType = $btn.data('type');

    loadStockAllocationsModal(currentAllocStockOrder, currentAllocProductId, currentAllocProductName, currentAllocType);
  });

  function loadStockAllocationsModal(stockorder, productId, productName, type) {
    const isConf = (type === 'confirmed');
    $('#allocModalTitlePrefix').text(isConf ? 'Confirmed Allocations' : 'Unconfirmed Allocations');
    $('#stockAllocModalHeader').css('background', isConf 
      ? 'linear-gradient(135deg, #0d6efd 0%, #052c65 100%)' 
      : 'linear-gradient(135deg, #d97706 0%, #78350f 100%)'
    );
    $('#stockAllocModalIcon').attr('class', isConf ? 'fa fa-users text-primary' : 'fa fa-clock text-warning');
    $('#allocModalStockOrderNo').text(stockorder);
    $('#allocModalProductName').text(productName);
    $('#allocModalTotalBadge').text('Loading...');
    $('#searchAllocModalTable').val('');

    const canSeeActions = (loginRoleId <= 3);
    $('#tblStockAllocClients .col-alloc-actions').toggle(canSeeActions);

    const $tbody = $('#tblStockAllocClientsBody');
    $tbody.html(`
      <tr>
        <td colspan="${canSeeActions ? 10 : 9}" class="text-center py-4">
          <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
          <span class="text-muted fw-semibold">Loading allocations data...</span>
        </td>
      </tr>
    `);

    $('#stockAllocClientsModal').modal('show');

    // Call existing modal/stockverification.php?action=get_stock_allocations directly
    $.ajax({
      url: 'modal/stockverification.php?action=get_stock_allocations',
      type: 'POST',
      data: {
        stockorder_no: stockorder,
        product_id: productId,
        type: type
      },
      dataType: 'json',
      success: function(resp) {
        if (resp && resp.status && resp.data) {
          const list = resp.data;
          $('#allocModalTotalBadge').text(Number(resp.total_qty || 0).toLocaleString() + ' Plants');
          $('#allocModalRowCount').text(list.length);

          if (list.length === 0) {
            $tbody.html(`
              <tr>
                <td colspan="${canSeeActions ? 10 : 9}" class="text-center py-4 text-muted fw-semibold">
                  No active allocations found for this stock order.
                </td>
              </tr>
            `);
            return;
          }

          let allocRows = '';
          list.forEach((item, i) => {
            const isInternal = (item.is_internal === true);
            const isDispatched = (item.is_dispatched === true);

            allocRows += `
              <tr class="alloc-row" data-consumedid="${item.consumed_id}" data-psono="${escapeHtml(item.psono || '')}">
                <td class="text-center text-muted font-monospace">${i + 1}</td>
                <td>
                  <span class="badge ${isInternal ? 'bg-secondary' : 'bg-primary-subtle text-primary border border-primary-subtle'} font-monospace px-2 py-1" style="font-size: 0.8rem;">
                    ${escapeHtml(item.psono || 'N/A')}
                  </span>
                  ${item.sono ? `<div class="small text-muted mt-0.5" style="font-size: 0.72rem;">SO: ${escapeHtml(item.sono)}</div>` : ''}
                </td>
                <td>
                  <div class="fw-bold text-dark">${escapeHtml(item.clientname || 'Karjee Farm')}</div>
                  <div class="small text-muted font-monospace" style="font-size: 0.75rem;">
                    <i class="fa fa-phone me-1 text-secondary"></i>${escapeHtml(item.mobileno || '-')}
                  </div>
                </td>
                <td class="text-center text-nowrap">${escapeHtml(item.order_date || '-')}</td>
                <td class="text-center text-nowrap">${escapeHtml(item.expected_delivery_date || '-')}</td>
                <td class="text-center fw-bold text-primary font-monospace">${Number(item.consumed_qty || 0).toLocaleString()}</td>
                <td class="text-center">
                  <span class="badge bg-light text-secondary border px-2 py-1" style="font-size: 0.75rem;">
                    ${escapeHtml(item.pso_status || 'Pending')}
                  </span>
                </td>
                <td class="text-center">
                  <span class="badge ${item.dispatch_badge_class || 'bg-light text-secondary border'} px-2 py-1" style="font-size: 0.75rem;">
                    ${escapeHtml(item.dispatch_status || 'Not Dispatched')}
                  </span>
                </td>
                <td style="font-size: 0.78rem;" class="text-muted">
                  ${escapeHtml(item.consumed_remark || item.item_remark || '-')}
                </td>
                ${canSeeActions ? `
                  <td class="text-center text-nowrap col-alloc-actions">
                    ${isDispatched ? `
                      <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1" style="font-size: 0.75rem;">
                        <i class="fa fa-lock me-1"></i> Locked (Dispatched)
                      </span>
                    ` : `
                      <button type="button" class="btn btn-sm btn-outline-danger px-2 py-1 remove-alloc-btn"
                              style="font-size: 0.78rem; font-weight: 600; border-radius: 6px;"
                              data-consumedid="${item.consumed_id}"
                              data-psoiid="${item.psoi_id || 0}"
                              data-psono="${escapeHtml(item.psono || '')}"
                              data-productid="${item.product_id || productId}"
                              data-stockorderno="${escapeHtml(stockorder)}"
                              data-qty="${item.consumed_qty}"
                              data-clientname="${escapeHtml(item.clientname || '')}">
                        <i class="fa fa-times me-1"></i> Remove
                      </button>
                    `}
                  </td>
                ` : ''}
              </tr>
            `;
          });
          $tbody.html(allocRows);
        } else {
          $tbody.html(`
            <tr>
              <td colspan="${canSeeActions ? 10 : 9}" class="text-center py-4 text-danger fw-semibold">
                Failed to load allocations: ${resp.message || 'Error occurred.'}
              </td>
            </tr>
          `);
        }
      },
      error: function() {
        $tbody.html(`
          <tr>
            <td colspan="${canSeeActions ? 10 : 9}" class="text-center py-4 text-danger fw-semibold">
              Network error while fetching allocations.
            </td>
          </tr>
        `);
      }
    });
  }

  // Filter within allocations modal
  $('#searchAllocModalTable').on('input', function() {
    const q = $(this).val().toLowerCase().trim();
    $('#tblStockAllocClientsBody tr.alloc-row').each(function() {
      const text = $(this).text().toLowerCase();
      $(this).toggle(!q || text.indexOf(q) > -1);
    });
  });

  // -------------------------------------------------------------
  // 6. Remove Stock Allocation Handler (Role <= 3)
  // -------------------------------------------------------------
  $(document).on('click', '.remove-alloc-btn', function(e) {
    e.preventDefault();
    const $btn = $(this);
    const consumedId = $btn.data('consumedid');
    const psoiId = $btn.data('psoiid');
    const psono = $btn.data('psono');
    const productId = $btn.data('productid');
    const stockOrderNo = $btn.data('stockorderno');
    const qty = $btn.data('qty');
    const clientName = $btn.data('clientname');

    Swal.fire({
      title: 'Remove Stock Allocation?',
      html: `
        <div class="text-start">
          <p class="mb-2">Are you sure you want to cancel the allocation of <b>${Number(qty).toLocaleString()} plants</b> for <b>${escapeHtml(clientName)}</b> (${escapeHtml(psono)})?</p>
          <div class="mb-2">
            <label class="form-label fw-bold small text-secondary">Cancellation Reason <span class="text-danger">*</span>:</label>
            <textarea id="swalRemoveAllocRemark" class="form-control form-control-sm" rows="2" placeholder="Mandatory cancellation remark..."></textarea>
          </div>
        </div>
      `,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Yes, Remove Allocation',
      preConfirm: () => {
        const remark = $.trim($('#swalRemoveAllocRemark').val());
        if (!remark) {
          Swal.showValidationMessage('Cancellation remark is mandatory.');
          return false;
        }
        return remark;
      }
    }).then((result) => {
      if (result.isConfirmed) {
        Swal.fire({
          title: 'Removing allocation...',
          allowOutsideClick: false,
          didOpen: () => Swal.showLoading()
        });

        // Call modal/stockverification.php?action=remove_stock_allocation
        $.ajax({
          url: 'modal/stockverification.php?action=remove_stock_allocation',
          type: 'POST',
          data: {
            consumed_id: consumedId,
            psoi_id: psoiId,
            psono: psono,
            product_id: productId,
            stockorder_no: stockOrderNo,
            remark: result.value,
            projectid: projectId
          },
          dataType: 'json',
          success: function(resp) {
            if (resp && resp.status) {
              Swal.fire({
                icon: 'success',
                title: 'Allocation Removed',
                text: resp.message || 'Stock allocation removed successfully.',
                timer: 2000,
                showConfirmButton: false
              });
              loadStockAllocationsModal(currentAllocStockOrder, currentAllocProductId, currentAllocProductName, currentAllocType);
              fetchOpenStock();
            } else {
              Swal.fire('Error', resp.message || 'Failed to remove allocation.', 'error');
            }
          },
          error: function() {
            Swal.fire('Network Error', 'Failed to reach server.', 'error');
          }
        });
      }
    });
  });

  // -------------------------------------------------------------
  // 7. Edit Stock Balance Handler (Role <= 3)
  // -------------------------------------------------------------
  $(document).on('click', '.edit-balance-btn', function(e) {
    e.preventDefault();
    e.stopPropagation();

    if (loginRoleId > 3) {
      Swal.fire('Access Denied', 'Only Admin roles (Role <= 3) can edit stock balance.', 'error');
      return;
    }

    const $btn = $(this);
    const stockorder = $btn.data('stockorderno');
    const productId = $btn.data('productid');
    const productName = $btn.data('productname');
    const currentBalance = parseInt($btn.data('balance') || 0);

    const modalHtml = `
      <div class="text-start">
        <div class="p-2.5 mb-3 rounded bg-light border">
          <div class="d-flex justify-content-between mb-1">
            <span class="text-muted small">Stock Order:</span>
            <span class="fw-bold font-monospace">${escapeHtml(stockorder)}</span>
          </div>
          <div class="d-flex justify-content-between mb-1">
            <span class="text-muted small">Product:</span>
            <span class="fw-semibold text-truncate" style="max-width: 260px;">${escapeHtml(productName)}</span>
          </div>
          <div class="d-flex justify-content-between">
            <span class="text-muted small">Current Available Balance:</span>
            <span class="fw-bold text-success">${Number(currentBalance).toLocaleString()} Plants</span>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-bold small text-secondary">
            New Balance Quantity <span class="text-danger">*</span>
          </label>
          <input type="number" id="swal_new_balance" class="form-control form-control-lg text-center fw-bold text-primary" 
                 value="${currentBalance}" min="0" step="1" placeholder="Enter new balance">
          <div class="form-text small">Enter updated balance quantity (can be increased or decreased).</div>
        </div>

        <div id="swal_lessed_info_box" class="alert alert-secondary py-2 px-3 mb-3 small">
          <i class="fa fa-info-circle me-1"></i> Enter a new balance quantity to adjust stock.
        </div>

        <div class="mb-2">
          <label class="form-label fw-bold small text-secondary">
            Correction Remark / Reason <span class="text-danger">*</span>
          </label>
          <textarea id="swal_balance_remark" class="form-control" rows="2" placeholder="e.g. Damage plants / Karjee Farm consumption / physical count correction (Mandatory)..."></textarea>
        </div>
      </div>
    `;

    Swal.fire({
      title: '<i class="fa fa-balance-scale text-primary me-2"></i>Edit Stock Balance',
      html: modalHtml,
      showCancelButton: true,
      confirmButtonColor: '#198754',
      cancelButtonColor: '#6c757d',
      confirmButtonText: '<i class="fa fa-check-circle me-1"></i> Update Balance',
      cancelButtonText: 'Cancel',
      didOpen: () => {
        const $newBal = $('#swal_new_balance');
        const $infoBox = $('#swal_lessed_info_box');
        const confirmBtn = Swal.getConfirmButton();

        function updateDiff() {
          const val = $newBal.val();
          if (val === '' || isNaN(val)) {
            $infoBox.removeClass('alert-info alert-success alert-warning alert-danger').addClass('alert-secondary')
              .html('<i class="fa fa-info-circle me-1"></i> Please enter a valid balance quantity.');
            confirmBtn.disabled = true;
            return;
          }
          const n = parseInt(val);
          if (n < 0) {
            $infoBox.removeClass('alert-info alert-success alert-warning alert-secondary').addClass('alert-danger')
              .html('<i class="fa fa-exclamation-circle me-1"></i> New balance cannot be negative.');
            confirmBtn.disabled = true;
          } else if (n === currentBalance) {
            $infoBox.removeClass('alert-danger alert-warning alert-secondary').addClass('alert-info')
              .html('<i class="fa fa-info-circle me-1"></i> Balance quantity: <strong>' + currentBalance.toLocaleString() + '</strong> Plants. Enter a new balance to adjust, or add a remark.');
            confirmBtn.disabled = false;
          } else if (n < currentBalance) {
            const diffQty = currentBalance - n;
            $infoBox.removeClass('alert-danger alert-success alert-secondary alert-info').addClass('alert-warning')
              .html('<i class="fa fa-arrow-down text-danger me-1"></i> Balance will be <strong>reduced</strong> by <strong class="text-danger">' + diffQty.toLocaleString() + '</strong> plants (consumed as Karjee Farm).');
            confirmBtn.disabled = false;
          } else {
            const diffQty = n - currentBalance;
            $infoBox.removeClass('alert-danger alert-warning alert-secondary alert-info').addClass('alert-success')
              .html('<i class="fa fa-arrow-up text-success me-1"></i> Balance will be <strong>increased</strong> by <strong class="text-success">' + diffQty.toLocaleString() + '</strong> plants (New balance: <strong>' + n.toLocaleString() + '</strong>).');
            confirmBtn.disabled = false;
          }
        }

        $newBal.on('input keyup change paste', updateDiff);
        updateDiff();
      },
      preConfirm: () => {
        const newBal = parseInt($('#swal_new_balance').val());
        const remark = $.trim($('#swal_balance_remark').val());
        if (isNaN(newBal) || newBal < 0) {
          Swal.showValidationMessage('Please enter a valid balance quantity.');
          return false;
        }
        if (!remark) {
          Swal.showValidationMessage('Correction remark is mandatory.');
          return false;
        }
        return { new_balance: newBal, remark: remark };
      }
    }).then((res) => {
      if (res.isConfirmed && res.value) {
        Swal.fire({
          title: 'Updating balance...',
          allowOutsideClick: false,
          didOpen: () => Swal.showLoading()
        });

        // Call modal/stockverification.php?action=adjust_stock_balance directly
        $.ajax({
          url: 'modal/stockverification.php?action=adjust_stock_balance',
          type: 'POST',
          data: {
            stockorder_no: stockorder,
            product_id: productId,
            product_name: productName,
            projectid: projectId,
            current_balance: currentBalance,
            new_balance: res.value.new_balance,
            remark: res.value.remark
          },
          dataType: 'json',
          success: function(resp) {
            if (resp && resp.status) {
              Swal.fire({
                icon: 'success',
                title: 'Balance Updated',
                text: resp.message || 'Stock balance updated successfully.',
                timer: 2000,
                showConfirmButton: false
              });
              fetchOpenStock();
            } else {
              Swal.fire('Error', resp.message || 'Failed to update stock balance.', 'error');
            }
          },
          error: function() {
            Swal.fire('Network Error', 'Failed to reach server.', 'error');
          }
        });
      }
    });
  });

  // -------------------------------------------------------------
  // 7. Click View RSO Movement Details (Status-Wise) Modal
  // -------------------------------------------------------------
  $(document).on('click', '.view-rso-movement-btn', function(e) {
    e.stopPropagation(); // Prevent toggling the parent accordion row
    const $btn = $(this);
    const stockOrderNo = $btn.data('stockorderno') || '';
    const stockOrderNos = $btn.data('stockordernos') || '';
    const productId = $btn.data('productid') || 0;
    const productName = $btn.data('productname') || '';
    const totalQty = Number($btn.data('qty') || 0);

    loadRsoMovementModal(stockOrderNo, stockOrderNos, productId, productName, totalQty);
  });

  function loadRsoMovementModal(stockOrderNo, stockOrderNos, productId, productName, totalQty) {
    const $modal = $('#rsoMovementModal');
    const $tbody = $('#tblRsoMovementBody');
    const $chipsContainer = $('#rsoStatusChipsContainer');

    $('#rsoModalStockOrderNo').text(stockOrderNo || (stockOrderNos ? 'Multiple Orders' : 'Product Batch')).toggle(Boolean(stockOrderNo || stockOrderNos));
    $('#rsoModalProductName').text(productName || 'Stock Product');
    $('#rsoModalTotalBadge').text(Number(totalQty).toLocaleString() + ' Plants');
    $('#searchRsoModalTable').val('');

    $chipsContainer.html('<div class="spinner-border spinner-border-sm text-secondary" role="status"></div> <span class="small text-muted ms-1">Loading status summary...</span>');
    $tbody.html(`
      <tr>
        <td colspan="8" class="text-center py-4 text-muted fw-semibold">
          <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div> Loading RSO movement details...
        </td>
      </tr>
    `);

    $modal.modal('show');

    $.ajax({
      url: 'openstockproduct.php',
      type: 'POST',
      data: {
        ajax_action: 'get_rso_movement_details',
        stockorder_no: stockOrderNo,
        stockorder_nos: stockOrderNos,
        product_id: productId
      },
      dataType: 'json',
      success: function(resp) {
        if (resp && resp.status && resp.data) {
          const list = resp.data;
          const summary = resp.status_summary || [];

          $('#rsoModalTotalBadge').text(Number(resp.total_plants || totalQty).toLocaleString() + ' Plants (' + Number(resp.total_trays || 0).toLocaleString() + ' Trays)');
          $('#rsoModalRowCount').text(list.length);

          // Render status pills
          let chipsHtml = `
            <button type="button" class="status-kpi-chip active" data-filter-status="all">
              <span>All Statuses</span>
              <span class="chip-count">${Number(resp.total_plants || 0).toLocaleString()}</span>
            </button>
          `;

          summary.forEach(stItem => {
            chipsHtml += `
              <button type="button" class="status-kpi-chip" data-filter-status="${escapeHtml(stItem.status)}">
                <span class="d-inline-block rounded-circle me-1" style="width: 8px; height: 8px; background-color: ${stItem.color};"></span>
                <span>${escapeHtml(stItem.status)}</span>
                <span class="chip-count">${Number(stItem.plants_qty).toLocaleString()}</span>
              </button>
            `;
          });

          $chipsContainer.html(chipsHtml);

          if (list.length === 0) {
            $tbody.html(`
              <tr>
                <td colspan="8" class="text-center py-4 text-muted fw-semibold">
                  <i class="fa fa-info-circle me-1 text-primary"></i> No active RSO movement records found for this selection.
                </td>
              </tr>
            `);
            return;
          }

          let rowsHtml = '';
          list.forEach((item, i) => {
            const floorInfo = item.floar_no ? `<div class="small text-muted font-monospace"><i class="fa fa-layer-group me-1"></i>${escapeHtml(item.floar_no)}</div>` : '';
            rowsHtml += `
              <tr class="rso-detail-row" data-status="${escapeHtml(item.status || '')}">
                <td class="text-center text-muted font-monospace">${i + 1}</td>
                <td>
                  <span class="badge bg-light text-dark border font-monospace px-2 py-1">
                    ${escapeHtml(item.stockorder_no || 'N/A')}
                  </span>
                </td>
                <td class="text-center">
                  <span class="badge px-2.5 py-1.5 fw-semibold shadow-xs" style="background-color: ${item.status_color || '#6c757d'}; color: ${item.status_text || '#ffffff'}; font-size: 0.78rem;">
                    ${escapeHtml(item.status || 'Unspecified')}
                  </span>
                </td>
                <td>
                  <div class="fw-bold text-dark">
                    <i class="fa fa-map-marker-alt text-danger me-1"></i>${escapeHtml(item.location_name || 'N/A')}
                  </div>
                  <div class="small text-primary fw-semibold mt-0.5">
                    <i class="fa fa-warehouse me-1"></i>${escapeHtml(item.polyhouse_name || '-')}
                  </div>
                  ${floorInfo}
                </td>
                <td class="text-center fw-semibold font-monospace">${Number(item.tray_count || 0).toLocaleString()}</td>
                <td class="text-center fw-bold text-nowrap font-monospace" style="color: #6f42c1; font-size: 0.9rem;">
                  ${Number(item.plants_qty || 0).toLocaleString()}
                </td>
                <td style="font-size: 0.8rem;" class="text-secondary">
                  ${escapeHtml(item.movementremark || '-')}
                </td>
                <td class="text-center small text-nowrap">
                  <div class="fw-semibold text-dark">${escapeHtml(item.updated_by || '-')}</div>
                  <div class="text-muted" style="font-size: 0.72rem;">${escapeHtml(item.last_updated || '-')}</div>
                </td>
              </tr>
            `;
          });
          $tbody.html(rowsHtml);
        } else {
          $tbody.html(`
            <tr>
              <td colspan="8" class="text-center py-4 text-danger fw-semibold">
                Failed to load movement details: ${resp.message || 'Error occurred.'}
              </td>
            </tr>
          `);
        }
      },
      error: function() {
        $tbody.html(`
          <tr>
            <td colspan="8" class="text-center py-4 text-danger fw-semibold">
              Network error while fetching RSO movement details.
            </td>
          </tr>
        `);
      }
    });
  }

  // Filter inside RSO movement modal by status chip click
  $(document).on('click', '#rsoMovementModal .status-kpi-chip', function() {
    $('#rsoMovementModal .status-kpi-chip').removeClass('active');
    $(this).addClass('active');

    const selStatus = $(this).data('filter-status');
    const searchVal = $('#searchRsoModalTable').val().toLowerCase().trim();

    filterRsoModalRows(selStatus, searchVal);
  });

  // Filter inside RSO movement modal by search input
  $('#searchRsoModalTable').on('input', function() {
    const searchVal = $(this).val().toLowerCase().trim();
    const selStatus = $('#rsoMovementModal .status-kpi-chip.active').data('filter-status') || 'all';

    filterRsoModalRows(selStatus, searchVal);
  });

  function filterRsoModalRows(statusFilter, searchVal) {
    let visibleCount = 0;
    $('#tblRsoMovementBody tr.rso-detail-row').each(function() {
      const $tr = $(this);
      const rowStatus = ($tr.data('status') || '');
      const rowText = $tr.text().toLowerCase();

      const statusMatches = (statusFilter === 'all' || rowStatus === statusFilter);
      const searchMatches = (!searchVal || rowText.indexOf(searchVal) > -1);

      const isVisible = (statusMatches && searchMatches);
      $tr.toggle(isVisible);
      if (isVisible) visibleCount++;
    });
    $('#rsoModalRowCount').text(visibleCount);
  }

  // -------------------------------------------------------------
  // 8. Click View Polyhouse Plants Details (Status-Wise) Modal
  // -------------------------------------------------------------
  $(document).on('click', '.view-ph-plants-btn', function(e) {
    e.stopPropagation(); // Prevent toggling the parent accordion row
    const $btn = $(this);
    const stockOrderNo = $btn.data('stockorderno') || '';
    const stockOrderNos = $btn.data('stockordernos') || '';
    const productId = $btn.data('productid') || 0;
    const productName = $btn.data('productname') || '';
    const totalQty = Number($btn.data('qty') || 0);

    loadPhPlantsModal(stockOrderNo, stockOrderNos, productId, productName, totalQty, 'all');
  });

  // Click View Rejected / Moved Plants Details Modal
  $(document).on('click', '.view-rej-moved-btn', function(e) {
    e.stopPropagation(); // Prevent toggling the parent accordion row
    const $btn = $(this);
    const stockOrderNo = $btn.data('stockorderno') || '';
    const stockOrderNos = $btn.data('stockordernos') || '';
    const productId = $btn.data('productid') || 0;
    const productName = $btn.data('productname') || '';
    const totalQty = Number($btn.data('qty') || 0);

    loadPhPlantsModal(stockOrderNo, stockOrderNos, productId, productName, totalQty, 'rejected_moved');
  });

  // Click View Aprox Physical Stock Details (Polyhouse & Bed Wise) Modal
  $(document).on('click', '.view-physical-stock-btn', function(e) {
    e.stopPropagation(); // Prevent toggling the parent accordion row
    const $btn = $(this);
    const stockOrderNo = $btn.data('stockorderno') || '';
    const stockOrderNos = $btn.data('stockordernos') || '';
    const productId = $btn.data('productid') || 0;
    const productName = $btn.data('productname') || '';
    const totalQty = Number($btn.data('qty') || 0);

    loadPhPlantsModal(stockOrderNo, stockOrderNos, productId, productName, totalQty, 'physical_stock');
  });

  function loadPhPlantsModal(stockOrderNo, stockOrderNos, productId, productName, totalQty, filterType = 'all') {
    const $modal = $('#phPlantsDetailsModal');
    const $tbody = $('#tblPhPlantsDetailsBody');
    const $chipsContainer = $('#phStatusChipsContainer');

    const isRej = (filterType === 'rejected_moved');
    const isPhys = (filterType === 'physical_stock');
    if (isPhys) {
      $('#phPlantsModalHeader').css('background', 'linear-gradient(135deg, #0f766e 0%, #115e59 100%)');
      $('#phModalHeaderIcon').attr('class', 'fa fa-cubes-stacked text-success');
      $('#phModalTitlePrefix').text('Aprox Physical Stock (Polyhouse & Bed Wise)');
      $('#phModalSubtitle').text('Showing living plants physically standing in polyhouses and beds.');
    } else if (isRej) {
      $('#phPlantsModalHeader').css('background', 'linear-gradient(135deg, #d97706 0%, #b45309 100%)');
      $('#phModalHeaderIcon').attr('class', 'fa fa-triangle-exclamation text-warning');
      $('#phModalTitlePrefix').text('Rejected / Moved Plants Details (Status Wise)');
      $('#phModalSubtitle').text('Showing rejected or moved plants status-wise breakdown.');
    } else {
      $('#phPlantsModalHeader').css('background', 'linear-gradient(135deg, #0d6efd 0%, #084298 100%)');
      $('#phModalHeaderIcon').attr('class', 'fa fa-warehouse text-primary');
      $('#phModalTitlePrefix').text('Polyhouse Plants Details (Status Wise)');
      $('#phModalSubtitle').text('Showing physical tray and plant distribution across polyhouses and beds.');
    }

    $('#phModalStockOrderNo').text(stockOrderNo || (stockOrderNos ? 'Multiple Orders' : 'Product Batch')).toggle(Boolean(stockOrderNo || stockOrderNos));
    $('#phModalProductName').text(productName || 'Stock Product');
    $('#phModalTotalBadge').text(Number(totalQty).toLocaleString() + ' Plants');
    $('#searchPhModalTable').val('');

    $chipsContainer.html('<div class="spinner-border spinner-border-sm text-secondary" role="status"></div> <span class="small text-muted ms-1">Loading status summary...</span>');
    $tbody.html(`
      <tr>
        <td colspan="9" class="text-center py-4 text-muted fw-semibold">
          <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div> Loading ${isPhys ? 'approx physical stock' : (isRej ? 'rejected / moved' : 'polyhouse')} plant details...
        </td>
      </tr>
    `);

    $modal.modal('show');

    $.ajax({
      url: 'openstockproduct.php',
      type: 'POST',
      data: {
        ajax_action: 'get_ph_plants_details',
        stockorder_no: stockOrderNo,
        stockorder_nos: stockOrderNos,
        product_id: productId,
        filter_type: filterType
      },
      dataType: 'json',
      success: function(resp) {
        if (resp && resp.status && resp.data) {
          const list = resp.data;
          const summary = resp.status_summary || [];

          $('#phModalTotalBadge').text(Number(resp.total_plants || totalQty).toLocaleString() + ' Plants (' + Number(resp.total_trays || 0).toLocaleString() + ' Trays)');
          $('#phModalRowCount').text(list.length);
          $('#phModalFooterTotal').text(Number(resp.total_plants || 0).toLocaleString());

          // Render status chips
          let chipsHtml = `
            <button type="button" class="status-kpi-chip ph-status-kpi-chip active" data-filter-status="all">
              <span>All Statuses</span>
              <span class="chip-count">${Number(resp.total_plants || 0).toLocaleString()}</span>
            </button>
          `;

          summary.forEach(stItem => {
            chipsHtml += `
              <button type="button" class="status-kpi-chip ph-status-kpi-chip" data-filter-status="${escapeHtml(stItem.status)}" title="${Number(stItem.tray_count).toLocaleString()} Trays">
                <span class="d-inline-block rounded-circle me-1" style="width: 8px; height: 8px; background-color: ${stItem.color};"></span>
                <span>${escapeHtml(stItem.status)}</span>
                <span class="chip-count">${Number(stItem.plants_qty).toLocaleString()}</span>
              </button>
            `;
          });

          $chipsContainer.html(chipsHtml);

          if (list.length === 0) {
            $tbody.html(`
              <tr>
                <td colspan="9" class="text-center py-4 text-muted fw-semibold">
                  <i class="fa fa-info-circle me-1 text-primary"></i> No ${isRej ? 'rejected or moved' : 'active polyhouse'} plant records found for this selection.
                </td>
              </tr>
            `);
            return;
          }

          let rowsHtml = '';
          list.forEach((item, i) => {
            const locPhBed = `
              <div class="fw-bold text-dark">
                <i class="fa fa-map-marker-alt text-danger me-1"></i>${escapeHtml(item.location_name || 'N/A')}
              </div>
              <div class="small text-primary fw-semibold mt-0.5">
                <i class="fa fa-warehouse me-1"></i>${escapeHtml(item.polyhouse_name || '-')}
                ${item.bed_no && item.bed_no !== '-' ? `<span class="badge bg-light text-secondary border font-monospace ms-1">${escapeHtml(item.bed_no)}</span>` : ''}
              </div>
              ${item.floar_no && item.floar_no !== '-' ? `<div class="small text-muted font-monospace"><i class="fa fa-layer-group me-1"></i>Floor: ${escapeHtml(item.floar_no)}</div>` : ''}
            `;

            const stageInfo = `
              <div class="small fw-semibold text-dark">${escapeHtml(item.growth_stage || '-')}</div>
              <div class="small text-muted"><span class="badge ${item.health_status === 'Healthy' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning border border-warning-subtle'}" style="font-size:0.7rem;">${escapeHtml(item.health_status || 'Healthy')}</span></div>
            `;

            rowsHtml += `
              <tr class="ph-detail-row" data-status="${escapeHtml(item.status || '')}">
                <td class="text-center text-muted font-monospace">${i + 1}</td>
                <td>
                  <span class="badge bg-light text-dark border font-monospace px-2 py-1">
                    ${escapeHtml(item.stockorder_no || 'N/A')}
                  </span>
                </td>
                <td class="text-center">
                  <span class="badge px-2.5 py-1.5 fw-semibold shadow-xs" style="background-color: ${item.status_color || '#6c757d'}; color: ${item.status_text || '#ffffff'}; font-size: 0.78rem;">
                    ${escapeHtml(item.status || 'Unspecified')}
                  </span>
                </td>
                <td>${locPhBed}</td>
                <td class="text-center fw-semibold font-monospace">${Number(item.tray_count || 0).toLocaleString()}</td>
                <td class="text-center fw-bold text-nowrap font-monospace text-primary" style="font-size: 0.9rem;">
                  ${Number(item.plants_qty || 0).toLocaleString()}
                </td>
                <td class="text-center">${stageInfo}</td>
                <td style="font-size: 0.8rem;" class="text-secondary">
                  ${escapeHtml(item.remark || '-')}
                </td>
                <td class="text-center small text-nowrap">
                  <div class="fw-semibold text-dark">${escapeHtml(item.updated_by || '-')}</div>
                  <div class="text-muted" style="font-size: 0.72rem;">${escapeHtml(item.last_updated || '-')}</div>
                </td>
              </tr>
            `;
          });
          $tbody.html(rowsHtml);
        } else {
          $tbody.html(`
            <tr>
              <td colspan="9" class="text-center py-4 text-danger fw-semibold">
                Failed to load plant details: ${resp.message || 'Error occurred.'}
              </td>
            </tr>
          `);
        }
      },
      error: function() {
        $tbody.html(`
          <tr>
            <td colspan="9" class="text-center py-4 text-danger fw-semibold">
              Network error while fetching polyhouse plant details.
            </td>
          </tr>
        `);
      }
    });
  }

  // Filter inside Polyhouse modal by status chip click
  $(document).on('click', '#phPlantsDetailsModal .status-kpi-chip', function() {
    $('#phPlantsDetailsModal .status-kpi-chip').removeClass('active');
    $(this).addClass('active');

    const selStatus = $(this).data('filter-status');
    const searchVal = $('#searchPhModalTable').val().toLowerCase().trim();

    filterPhModalRows(selStatus, searchVal);
  });

  // Filter inside Polyhouse modal by search input
  $('#searchPhModalTable').on('input', function() {
    const searchVal = $(this).val().toLowerCase().trim();
    const selStatus = $('#phPlantsDetailsModal .status-kpi-chip.active').data('filter-status') || 'all';

    filterPhModalRows(selStatus, searchVal);
  });

  function filterPhModalRows(statusFilter, searchVal) {
    let visibleCount = 0;
    let visiblePlants = 0;
    $('#tblPhPlantsDetailsBody tr.ph-detail-row').each(function() {
      const $tr = $(this);
      const rowStatus = ($tr.data('status') || '');
      const rowText = $tr.text().toLowerCase();

      const statusMatches = (statusFilter === 'all' || rowStatus === statusFilter);
      const searchMatches = (!searchVal || rowText.indexOf(searchVal) > -1);

      const isVisible = (statusMatches && searchMatches);
      $tr.toggle(isVisible);
      if (isVisible) {
        visibleCount++;
        const pQty = parseInt($tr.find('td:eq(5)').text().replace(/,/g, '') || 0);
        visiblePlants += pQty;
      }
    });
    $('#phModalRowCount').text(visibleCount);
    $('#phModalFooterTotal').text(visiblePlants.toLocaleString());
  }

  // -------------------------------------------------------------
  // Click "Delivered with order no X" Badge to View Production Stock Order Details
  // -------------------------------------------------------------
  $(document).on('click', '.view-delivered-order-details-btn', function(e) {
    e.stopPropagation();
    const $btn = $(this);
    const stockOrderNo = $btn.data('stockorderno') || '';
    const origStockOrder = $btn.data('orig-stockorder') || '';
    const productId = $btn.data('productid') || 0;
    const productName = $btn.data('productname') || '';
    const clientName = $btn.data('clientname') || '';
    const origPsoNo = $btn.data('orig-psono') || '';

    if (!stockOrderNo) {
      Swal.fire('Notice', 'No fulfilling production stock order found for this entry.', 'info');
      return;
    }

    loadProductionStockOrderDetails(stockOrderNo, origStockOrder, productId, productName, clientName, origPsoNo);
  });

  function loadProductionStockOrderDetails(stockOrderNo, origStockOrder, productId, productName, clientName, origPsoNo) {
    const $modal = $('#productionStockOrderDetailsModal');
    $('#psoDetailStockOrderNo').text(stockOrderNo);
    $('#psoDetailProductName').text(productName || 'Stock Product');
    $('#psoDetailOrderTypeBadge').text('Loading...');
    $('#psoDetailAllocSoTitle').text(stockOrderNo);

    // Cross-order alert
    if (origStockOrder || clientName) {
      $('#psoDetailCrossAlert').show();
      $('#psoDetailCrossAlertText').html(
        `Original order was created for <strong>${escapeHtml(clientName || 'Client')}</strong> (${escapeHtml(origStockOrder)}), but plants were fulfilled & dispatched from Production Stock Order <strong class="font-monospace">${escapeHtml(stockOrderNo)}</strong>.`
      );
    } else {
      $('#psoDetailCrossAlert').hide();
    }

    // Reset KPI cards
    $('#psoDetailKpiOrderQty').html('<span class="spinner-border spinner-border-sm"></span>');
    $('#psoDetailKpiClientName').text('-');
    $('#psoDetailKpiPhPlants').text('-');
    $('#psoDetailKpiPhTrays').text('-');
    $('#psoDetailKpiConfAlloc').text('-');
    $('#psoDetailKpiAllocCount').text('-');
    $('#psoDetailKpiBalance').text('-');
    $('#psoDetailKpiPhysStock').text('-');
    $('#psoDetailKpiReadyDate').text('-');
    $('#psoDetailKpiLocation').text('-');

    // Reset tables
    $('#tblPsoDetailAllocationsBody').html(`
      <tr>
        <td colspan="9" class="text-center py-4 text-muted fw-semibold">
          <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div> Loading client allocations for ${escapeHtml(stockOrderNo)}...
        </td>
      </tr>
    `);
    $('#tblPsoDetailBedsBody').html(`
      <tr>
        <td colspan="7" class="text-center py-4 text-muted fw-semibold">
          <div class="spinner-border spinner-border-sm text-info me-2" role="status"></div> Loading polyhouse beds...
        </td>
      </tr>
    `);

    // Reset tabs to Allocations tab
    $('#psoDetailTabs button#tab-pso-allocations').tab('show');

    $modal.modal('show');

    $.ajax({
      url: 'openstockproduct.php',
      type: 'POST',
      data: {
        ajax_action: 'get_stock_order_full_details',
        stockorder_no: stockOrderNo,
        orig_stockorder_no: origStockOrder
      },
      dataType: 'json',
      success: function(resp) {
        if (resp && resp.status && resp.stock_order) {
          const so = resp.stock_order;
          const poly = resp.polyhouse || { beds: [], total_plants: 0, total_trays: 0 };
          const allocs = resp.allocations || [];
          const m = resp.metrics || {};
          const orig = resp.orig_order || null;

          // Badges
          $('#psoDetailOrderTypeBadge').text(so.category_label || (so.is_trader ? 'Trader Order' : 'Farmer Order'));
          $('#psoDetailProductName').text(so.product_name || productName);

          // KPI Cards
          $('#psoDetailKpiOrderQty').text(Number(m.order_qty || 0).toLocaleString());
          $('#psoDetailKpiClientName').text(so.client_name || '-');
          $('#psoDetailKpiPhPlants').text(Number(m.ph_plants || 0).toLocaleString());
          $('#psoDetailKpiPhTrays').text(Number(m.ph_trays || 0).toLocaleString() + ' Trays');
          $('#psoDetailKpiConfAlloc').text(Number(m.conf_alloc || 0).toLocaleString());
          $('#psoDetailKpiAllocCount').text(allocs.length + (allocs.length === 1 ? ' Allocation' : ' Allocations'));
          $('#psoDetailKpiBalance').text(Number(m.balance || 0).toLocaleString());
          $('#psoDetailKpiPhysStock').text(Number(m.aprox_physical_stock || 0).toLocaleString());
          $('#psoDetailKpiReadyDate').text(so.expected_delivery_date || '-');
          $('#psoDetailKpiLocation').text(so.location_name || 'Karjee');

          // Allocations count
          $('#psoAllocBadgeCount').text(allocs.length);
          $('#psoBedsBadgeCount').text((poly.beds || []).length);

          // Render Allocations Table
          if (allocs.length === 0) {
            $('#tblPsoDetailAllocationsBody').html(`
              <tr>
                <td colspan="9" class="text-center py-4 text-muted fw-semibold">
                  No client allocations found for this production stock order.
                </td>
              </tr>
            `);
          } else {
            let aRows = '';
            const matchName = (clientName || (orig && orig.client_name) || '').toLowerCase().trim();
            const matchPso = (origPsoNo || (orig && orig.psono) || '').toLowerCase().trim();

            allocs.forEach((a, idx) => {
              const aName = (a.clientname || '').toLowerCase().trim();
              const aPso = (a.psono || '').toLowerCase().trim();
              const isMatch = (matchPso && aPso === matchPso) || (matchName && aName.includes(matchName));

              aRows += `
                <tr class="pso-alloc-row ${isMatch ? 'table-success border-success-subtle' : ''}" style="${isMatch ? 'background-color: #ecfdf5 !important;' : ''}">
                  <td class="text-center font-monospace">${idx + 1}</td>
                  <td>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2 py-1" style="font-size: 0.8rem;">
                      ${escapeHtml(a.psono || 'N/A')}
                    </span>
                    ${a.sono ? `<div class="small text-muted mt-0.5" style="font-size: 0.72rem;">SO: ${escapeHtml(a.sono)}</div>` : ''}
                  </td>
                  <td>
                    <div class="d-flex align-items-center gap-1.5 flex-wrap">
                      <span class="fw-bold text-dark">${escapeHtml(a.clientname || 'Karjee Farm')}</span>
                      ${isMatch ? `<span class="badge bg-success text-white px-1.5 py-0.5" style="font-size: 0.65rem;"><i class="fa fa-check me-1"></i>Fulfilled Client</span>` : ''}
                    </div>
                    <div class="small text-muted font-monospace" style="font-size: 0.75rem;">
                      <i class="fa fa-phone me-1 text-secondary"></i>${escapeHtml(a.mobileno || '-')}
                    </div>
                  </td>
                  <td class="text-center text-nowrap">${escapeHtml(a.order_date || '-')}</td>
                  <td class="text-center text-nowrap">${escapeHtml(a.delivery_date || '-')}</td>
                  <td class="text-center fw-bold text-primary font-monospace">${Number(a.consumed_qty || 0).toLocaleString()}</td>
                  <td class="text-center">
                    <span class="badge bg-light text-secondary border px-2 py-1" style="font-size: 0.75rem;">
                      ${escapeHtml(a.confirmstatus || 'Confirmed')}
                    </span>
                  </td>
                  <td class="text-center">
                    <span class="badge ${a.dispatch_badge_class || 'bg-secondary text-white'} px-2 py-1" style="font-size: 0.75rem;">
                      <i class="fa fa-truck me-1"></i>${escapeHtml(a.dispatch_status || 'Allocated')}
                    </span>
                    ${a.dispatched_at ? `<div class="small text-muted" style="font-size:0.68rem;">${escapeHtml(a.dispatched_at)}</div>` : ''}
                  </td>
                  <td>
                    <div class="small text-muted">${escapeHtml(a.consumed_remark || a.remark || '-')}</div>
                  </td>
                </tr>
              `;
            });
            $('#tblPsoDetailAllocationsBody').html(aRows);
          }

          // Render Beds Breakdown Table
          const beds = poly.beds || [];
          if (beds.length === 0) {
            $('#tblPsoDetailBedsBody').html(`
              <tr>
                <td colspan="7" class="text-center py-4 text-muted fw-semibold">
                  No physical polyhouse bed records found.
                </td>
              </tr>
            `);
          } else {
            let bRows = '';
            beds.forEach((b, bIdx) => {
              bRows += `
                <tr>
                  <td class="text-center font-monospace">${bIdx + 1}</td>
                  <td>
                    <span class="badge bg-light text-dark border px-2 py-1">PH NO ${escapeHtml(b.polyhouseid || '-')}</span>
                  </td>
                  <td class="fw-semibold text-dark">${escapeHtml(b.bedrackname || '-')}</td>
                  <td class="text-center">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1" style="font-size: 0.75rem;">
                      ${escapeHtml(b.status || 'Polyhouse')}
                    </span>
                  </td>
                  <td class="text-center">
                    <span class="badge ${b.health_status === 'Healthy' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning'} px-2 py-1" style="font-size: 0.75rem;">
                      ${escapeHtml(b.health_status || 'Healthy')}
                    </span>
                  </td>
                  <td class="text-center fw-bold font-monospace">${Number(b.trays || 0).toLocaleString()}</td>
                  <td class="text-center fw-bold text-primary font-monospace">${Number(b.plants || 0).toLocaleString()}</td>
                </tr>
              `;
            });
            $('#tblPsoDetailBedsBody').html(bRows);
          }

          // Populate Order & Sowing Info
          $('#infoPsoOrderNo').text(so.stockorder_no || '-');
          $('#infoPsoClient').text(so.client_name || '-');
          $('#infoPsoCategory').html(`<span class="badge ${so.is_trader ? 'bg-primary text-white' : 'bg-warning text-dark'}">${escapeHtml(so.category_label || '-')}</span>`);
          $('#infoPsoProduct').text(so.product_name || '-');
          $('#infoPsoQty').text(Number(so.order_qty || 0).toLocaleString() + ' Plants');
          $('#infoPsoSowDate').text(so.sowing_date || '-');
          $('#infoPsoSowStatus').text(so.sowing_status || '-');
          $('#infoPsoDelivDate').text(so.expected_delivery_date || '-');
          $('#infoPsoWorkingStatus').text(so.working_status || '-');
          $('#infoPsoRemarks').text(so.remarks || 'No remarks recorded');

          $('#psoDetailFooterSummary').html(`
            Order: <strong class="font-monospace">${escapeHtml(so.stockorder_no)}</strong> &bull; Total PH Plants: <strong class="text-primary">${Number(m.ph_plants || 0).toLocaleString()}</strong> &bull; Conf. Alloc: <strong class="text-success">${Number(m.conf_alloc || 0).toLocaleString()}</strong>
          `);
        } else {
          $('#tblPsoDetailAllocationsBody').html(`
            <tr>
              <td colspan="9" class="text-center py-4 text-danger fw-semibold">
                <i class="fa fa-triangle-exclamation me-1"></i> Failed to load stock order details: ${escapeHtml(resp ? resp.message : 'Unknown error')}
              </td>
            </tr>
          `);
        }
      },
      error: function(xhr, status, err) {
        $('#tblPsoDetailAllocationsBody').html(`
          <tr>
            <td colspan="9" class="text-center py-4 text-danger fw-semibold">
              <i class="fa fa-triangle-exclamation me-1"></i> Server error while loading stock order details.
            </td>
          </tr>
        `);
      }
    });
  }

  // Live filter for PSO Allocations Table inside modal
  $(document).on('keyup', '#searchPsoAllocTable', function() {
    const q = $(this).val().toLowerCase().trim();
    $('#tblPsoDetailAllocationsBody tr.pso-alloc-row').each(function() {
      const txt = $(this).text().toLowerCase();
      $(this).toggle(!q || txt.indexOf(q) > -1);
    });
  });

  // -------------------------------------------------------------
  // 9. Export CSV Functionality (Supports Opening Balance & Date Ranges)
  // -------------------------------------------------------------
  $('#btnExportCSV').on('click', function() {
    let rows = [];
    rows.push(['S.No', 'Product Name', 'Prod Order No', 'Location / Polyhouse', 'Expected Ready Date', 'Order Qty', 'Ph Plants', 'Rej / Moved Plants', 'Conf Alloc', 'Unconf Alloc', 'RSO-Movement', 'Balance', 'Running Bal', 'Aprox Physical Stock']);

    let orderIdx = 0;
    const filterFrom = ($('#filterReadyDateFrom').val() || '').trim();

    $('#tblstockdatamaster tbody tr.product-summary-row').each(function() {
      const $prodRow = $(this);
      if ($prodRow.is(':hidden')) return;

      const pid = $prodRow.data('productid');
      const prodName = $prodRow.find('td:eq(1)').text().trim();
      const $childRow = $(`#product-child-${pid}`);

      // 1. Export Opening Balance row if present
      const $opRow = $childRow.find('tr.opening-balance-ledger-row');
      if ($opRow.length > 0) {
        orderIdx++;
        rows.push([
          orderIdx,
          `"${prodName.replace(/"/g, '""')}"`,
          `"OPENING BALANCE (< ${filterFrom})"`,
          `"Carried Forward"`,
          `"< ${filterFrom}"`,
          $opRow.find('td:eq(4)').text().trim().replace(/,/g, ''),
          $opRow.find('td:eq(5)').text().trim().replace(/,/g, ''),
          $opRow.find('td:eq(6)').text().trim().replace(/,/g, ''),
          $opRow.find('td:eq(7)').text().trim().replace(/,/g, ''),
          $opRow.find('td:eq(8)').text().trim().replace(/,/g, ''),
          $opRow.find('td:eq(9)').text().trim().replace(/,/g, ''),
          $opRow.find('td:eq(10)').text().trim().replace(/,/g, ''),
          $opRow.find('td:eq(11)').text().trim().replace(/,/g, ''),
          $opRow.find('td:eq(12)').text().trim().replace(/,/g, '')
        ]);
      }

      // 2. Export in-range order rows (exclude hidden opening detail rows to avoid duplicate counting)
      $childRow.find('tr.nested-order-row:not(.opening-detail-row)').each(function() {
        const $orderTr = $(this);
        if ($orderTr.is(':hidden')) return;

        orderIdx++;
        const orderNo = $orderTr.find('td:eq(1)').text().replace(/\s+/g, ' ').trim();
        const loc = $orderTr.find('td:eq(2)').text().replace(/\s+/g, ' ').trim();
        const readyDate = $orderTr.find('td:eq(3)').text().trim();
        const ordQty = $orderTr.find('td:eq(4)').text().trim().replace(/,/g, '');
        const phPlants = $orderTr.find('td:eq(5)').text().trim().replace(/,/g, '');
        const rejMoved = $orderTr.find('td:eq(6)').text().trim().replace(/,/g, '');
        const confAlloc = $orderTr.find('td:eq(7)').text().trim().replace(/,/g, '');
        const unconfAlloc = $orderTr.find('td:eq(8)').text().trim().replace(/,/g, '');
        const rsoMv = $orderTr.find('td:eq(9)').text().trim().replace(/,/g, '');
        const balance = $orderTr.find('td:eq(10) .balance-display-val').text().trim().replace(/,/g, '') || $orderTr.find('td:eq(10)').text().trim().replace(/,/g, '');
        const runningBal = $orderTr.find('td:eq(11)').text().trim().replace(/,/g, '');
        const aproxPhys = $orderTr.find('td:eq(12)').text().trim().replace(/,/g, '');

        rows.push([
          orderIdx,
          `"${prodName.replace(/"/g, '""')}"`,
          `"${orderNo.replace(/"/g, '""')}"`,
          `"${loc.replace(/"/g, '""')}"`,
          `"${readyDate}"`,
          ordQty,
          phPlants,
          rejMoved,
          confAlloc,
          unconfAlloc,
          rsoMv,
          balance,
          runningBal,
          aproxPhys
        ]);
      });
    });

    if (rows.length <= 1) {
      Swal.fire('No Data', 'No stock records currently displayed to export.', 'info');
      return;
    }

    // Append table total summary row to CSV
    const totOrd = $('#footTotalOrderQty').text().replace(/,/g, '');
    const totPh = $('#footTotalPhPlants').text().replace(/,/g, '');
    const totRej = $('#footTotalRejMovedPlants').text().replace(/,/g, '');
    const totConf = $('#footTotalConfirmed').text().replace(/,/g, '');
    const totUnconf = $('#footTotalUnconfirmed').text().replace(/,/g, '');
    const totRsoMv = $('#footTotalRsoMovement').text().replace(/,/g, '');
    const totBal = $('#footTotalBalance').text().replace(/,/g, '');
    const totAproxPhys = $('#footTotalAproxPhysicalStock').text().replace(/,/g, '');
    rows.push(['TOTAL', 'Total Across Orders', '', '', '', totOrd, totPh, totRej, totConf, totUnconf, totRsoMv, totBal, totBal, totAproxPhys]);

    const csvContent = "\uFEFF" + rows.map(e => e.join(',')).join('\r\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    const today = new Date().toISOString().slice(0, 10);
    link.setAttribute('href', url);
    link.setAttribute('download', `Open_Stock_Product_Wise_${today}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  });

  // Helper escape function
  function escapeHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  // Initial Load
  loadProductsDropdown();
  fetchOpenStock();
});
</script>
</body>
</html>
