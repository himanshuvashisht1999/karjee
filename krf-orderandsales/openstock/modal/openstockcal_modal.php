<?php
/**
 * openstockcal_modal.php
 * Self-contained backend API & calculation engine for Product-Wise Open Stock & Order Drilldown.
 * 
 * Strict Constraint Compliance:
 * - Only outside inclusion allowed is the database connection: require_once(__DIR__ . '/../../../connection.php');
 * - Zero reliance on external helper functions, classes, or other directory scripts.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(0);
ini_set('display_errors', '0');
date_default_timezone_set('Asia/Kolkata');

// 1. Database Connection (Only outside include permitted)
require_once(__DIR__ . '/../../../connection.php');

header('Content-Type: application/json; charset=utf-8');

// 2. Session Context & Parameters
$loginid      = $_SESSION['loginid'] ?? $_SESSION['userid'] ?? 'admin';
$loginname    = $_SESSION['loginname'] ?? $_SESSION['username'] ?? $_SESSION['name'] ?? 'Admin';
$companyid    = (int)($_SESSION['companyid'] ?? 1);
$subcompanyid = (int)($_SESSION['subcompanyid'] ?? 1);
$projectid    = (int)($_SESSION['projectid'] ?? 17);
if ($projectid <= 0) $projectid = 17;

// Self-contained JSON response helper
function sendJsonResponse($status, $data = [], $message = '') {
    echo json_encode([
        'status'  => $status,
        'message' => $message,
        'data'    => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
    exit;
}

// 3. Action Dispatcher
$action = trim($_REQUEST['action'] ?? '');
if (empty($action)) {
    sendJsonResponse(false, [], 'No action specified');
}

// ACTION 1: GET PRODUCT-WISE OPEN STOCK SUMMARY
if ($action === 'get_open_stock_summary') {
    $search = trim($_REQUEST['search'] ?? '');
    $categoryFilter = trim($_REQUEST['category'] ?? 'all');
    $statusFilter = trim($_REQUEST['stock_filter'] ?? 'all'); // 'all', 'available', 'exhausted', 'in_bed'

    // Build project condition for isolation
    $projWhereStock = " (stk.projectid = {$projectid} OR '{$projectid}' = '0' OR stk.projectid = 17) AND (stk.tedit IS NULL OR stk.tedit = '') ";
    $projWherePh    = " (ph.projectid = '{$projectid}' OR '{$projectid}' = '0' OR ph.projectid = '17') AND (ph.tedit IS NULL OR ph.tedit = '') ";
    $projWhereCons  = " (c.projectid = {$projectid} OR '{$projectid}' = '0' OR c.projectid = 17) AND (c.tedit IS NULL OR c.tedit = '') ";
    $projWhereAlloc = " (rsa.projectid = {$projectid} OR '{$projectid}' = '0' OR rsa.projectid = 17) AND (rsa.tedit IS NULL OR rsa.tedit = '') ";
    $projWhereInv   = " (sii.projectid = {$projectid} OR '{$projectid}' = '0' OR sii.projectid = 17) ";

    // Search, Category, Grafted, and Product ID filters
    $graftedFilter   = trim($_REQUEST['grafted_filter'] ?? 'all'); // 'all', 'grafted', 'nongrafted'
    $productIdFilter = (int)($_REQUEST['product_id'] ?? 0);

    $prodFilterClause = "";
    if ($productIdFilter > 0) {
        $prodFilterClause .= " AND p.ProductID = {$productIdFilter} ";
    }
    if ($graftedFilter === 'grafted') {
        $prodFilterClause .= " AND (LOWER(TRIM(COALESCE(p.IsGrafted, ''))) = 'yes' OR (p.ProductName LIKE '%Grafted%' AND p.ProductName NOT LIKE '%NonGrafted%' AND p.ProductName NOT LIKE '%Non-Grafted%')) ";
    } else if ($graftedFilter === 'nongrafted') {
        $prodFilterClause .= " AND (LOWER(TRIM(COALESCE(p.IsGrafted, ''))) != 'yes' AND (p.ProductName NOT LIKE '%Grafted%' OR p.ProductName LIKE '%NonGrafted%' OR p.ProductName LIKE '%Non-Grafted%')) ";
    }
    if ($categoryFilter !== 'all' && $categoryFilter !== '') {
        $catSafe = $krfconn->real_escape_string($categoryFilter);
        $prodFilterClause .= " AND (p.CategoryId = '{$catSafe}' OR p.ProductTypeID = '{$catSafe}') ";
    }
    if ($search !== '') {
        $searchSafe = $krfconn->real_escape_string($search);
        $prodFilterClause .= " AND (p.ProductName LIKE '%{$searchSafe}%' OR p.sku LIKE '%{$searchSafe}%' OR p.ProductID = '{$searchSafe}') ";
    }

    /**
     * Master Query Architecture:
     * We aggregate production orders, polyhouse physical plants, consumed orders,
     * ready stock allocations, and sales invoice dispatches grouped by productid.
     */
    $sql = "
        SELECT 
            p.ProductID AS product_id,
            p.ProductName AS product_name,
            COALESCE(p.sku, '') AS product_sku,
            COALESCE(p.CategoryId, '') AS category_id,
            COALESCE(p.ProductTypeID, '') AS product_type_id,
            CASE 
                WHEN LOWER(TRIM(COALESCE(p.IsGrafted, ''))) = 'yes' 
                  OR (p.ProductName LIKE '%Grafted%' AND p.ProductName NOT LIKE '%NonGrafted%' AND p.ProductName NOT LIKE '%Non-Grafted%') 
                THEN 1 
                ELSE 0 
            END AS is_grafted,
            
            -- 1. Total from krf_production_stockorder
            COALESCE(stk_agg.tot_production_qty, 0) AS total_production_qty,
            COALESCE(stk_agg.active_orders_count, 0) AS active_orders_count,
            
            -- 2. Total Bed-wise active plants (status IN ('Polyhouse', 'Readytosale'))
            COALESCE(ph_agg.tot_bed_plants, 0) AS total_bed_plants,
            COALESCE(ph_agg.active_trays_count, 0) AS active_trays_count,
            
            -- 3. Total Assigned / Consumed from krf_productionorder_consumed
            COALESCE(cons_agg.tot_assigned_qty, 0) AS total_assigned_qty,
            COALESCE(cons_agg.tot_consumed_dispatch, 0) AS total_consumed_dispatch,
            
            -- 4. Total Allocated to Confirm Sale from krf_readystock_allocation
            COALESCE(alloc_agg.tot_allocated_qty, 0) AS total_allocated_qty,
            
            -- 5. Total Dispatched from sales_invoice & sales_invoice_items
            COALESCE(inv_agg.tot_dispatched_qty, 0) AS total_dispatched_qty
            
        FROM product_master p
        
        -- Join 1: Production Stock Orders Aggregation
        LEFT JOIN (
            SELECT 
                stk.productid,
                SUM(COALESCE(stk.quantity, 0)) AS tot_production_qty,
                COUNT(DISTINCT stk.stockorder_no) AS active_orders_count
            FROM krf_production_stockorder stk
            WHERE {$projWhereStock}
              AND LOWER(TRIM(COALESCE(stk.status, ''))) NOT IN ('cancelled', 'cancel')
            GROUP BY stk.productid
        ) stk_agg ON stk_agg.productid = p.ProductID
        
        -- Join 2: Polyhouse Bed-wise Active Inventory
        LEFT JOIN (
            SELECT 
                ph.productid,
                SUM(GREATEST(0, COALESCE(ph.qtypertray, 0) - COALESCE(ph.failurecount, 0) - COALESCE(ph.dispatchqty, 0))) AS tot_bed_plants,
                COUNT(ph.polyhouseplantid) AS active_trays_count
            FROM krf_polyhouse_plant_details ph
            WHERE {$projWherePh}
              AND LOWER(TRIM(COALESCE(ph.status, ''))) IN ('polyhouse', 'readytosale')
            GROUP BY ph.productid
        ) ph_agg ON ph_agg.productid = p.ProductID
        
        -- Join 3: Production Order Consumed / Assigned
        LEFT JOIN (
            SELECT 
                c.product_id,
                SUM(COALESCE(c.consumed_qty, 0)) AS tot_assigned_qty,
                SUM(COALESCE(c.dispatchedqty, 0)) AS tot_consumed_dispatch
            FROM krf_productionorder_consumed c
            WHERE {$projWhereCons}
              AND LOWER(TRIM(COALESCE(c.status, ''))) NOT IN ('cancelled', 'cancel')
              AND LOWER(TRIM(COALESCE(c.confirmstatus, ''))) NOT IN ('cancelled', 'cancel')
              AND (c.tedit IS NULL OR c.tedit = '' OR c.tedit = '0')
            GROUP BY c.product_id
        ) cons_agg ON cons_agg.product_id = p.ProductID
        
        -- Join 4: Ready Stock Allocations
        LEFT JOIN (
            SELECT 
                rsa.productid,
                SUM(COALESCE(rsa.allocate_ordqty, 0)) AS tot_allocated_qty
            FROM krf_readystock_allocation rsa
            WHERE {$projWhereAlloc}
              AND LOWER(TRIM(COALESCE(rsa.status, ''))) NOT IN ('cancelled', 'cancel')
              AND (rsa.tedit IS NULL OR rsa.tedit = '' OR rsa.tedit = '0')
            GROUP BY rsa.productid
        ) alloc_agg ON alloc_agg.productid = p.ProductID
        
        -- Join 5: Sales Invoice Dispatched Items
        LEFT JOIN (
            SELECT 
                sii.productid,
                SUM(COALESCE(sii.total_dispatch_plants, sii.quantity, 0)) AS tot_dispatched_qty
            FROM sales_invoice_items sii
            WHERE {$projWhereInv}
              AND LOWER(TRIM(COALESCE(sii.status, ''))) = 'dispatched'
              AND (sii.tedit IS NULL OR sii.tedit = '' OR sii.tedit = '0')
            GROUP BY sii.productid
        ) inv_agg ON inv_agg.productid = p.ProductID
        
        WHERE (
            COALESCE(stk_agg.tot_production_qty, 0) > 0 
            OR COALESCE(ph_agg.tot_bed_plants, 0) > 0 
            OR COALESCE(cons_agg.tot_assigned_qty, 0) > 0
            OR COALESCE(alloc_agg.tot_allocated_qty, 0) > 0
            OR COALESCE(inv_agg.tot_dispatched_qty, 0) > 0
        )
        {$prodFilterClause}
        ORDER BY p.ProductName ASC
    ";

    $res = $krfconn->query($sql);
    if (!$res) {
        sendJsonResponse(false, [], 'Database Query Failed: ' . $krfconn->error);
    }

    $products = [];
    $kpiTotals = [
        'total_products'        => 0,
        'total_production_qty'  => 0,
        'total_bed_plants'      => 0,
        'total_assigned_qty'    => 0,
        'total_allocated_qty'   => 0,
        'total_dispatched_qty'  => 0,
        'total_physical_open'   => 0,
        'total_book_open'       => 0
    ];

    while ($r = $res->fetch_assoc()) {
        $pId              = (int)$r['product_id'];
        $prodQty          = (int)$r['total_production_qty'];
        $bedPlants        = (int)$r['total_bed_plants'];
        $assignedQty      = (int)$r['total_assigned_qty'];
        $allocatedQty     = (int)$r['total_allocated_qty'];
        $dispatchedQty    = (int)$r['total_dispatched_qty'];
        $activeOrdersCnt  = (int)$r['active_orders_count'];
        $activeTraysCnt   = (int)$r['active_trays_count'];

        // Calculations:
        // 1. Physical Open Stock: Living plants on nursery beds minus firm ready stock allocations
        $physicalOpen = max(0, $bedPlants - $allocatedQty);

        // 2. Net Production Book Open Stock: Total planned production minus assigned commitments and dispatches
        $bookOpen = max(0, $prodQty - $assignedQty - $allocatedQty);

        // Filter evaluation if applied
        if ($statusFilter === 'available' && $physicalOpen <= 0 && $bookOpen <= 0) {
            continue;
        }
        if ($statusFilter === 'exhausted' && ($physicalOpen > 0 || $bookOpen > 0)) {
            continue;
        }
        if ($statusFilter === 'in_bed' && $bedPlants <= 0) {
            continue;
        }

        // Tally KPI totals
        $kpiTotals['total_products']++;
        $kpiTotals['total_production_qty'] += $prodQty;
        $kpiTotals['total_bed_plants']     += $bedPlants;
        $kpiTotals['total_assigned_qty']   += $assignedQty;
        $kpiTotals['total_allocated_qty']  += $allocatedQty;
        $kpiTotals['total_dispatched_qty'] += $dispatchedQty;
        $kpiTotals['total_physical_open']  += $physicalOpen;
        $kpiTotals['total_book_open']      += $bookOpen;

        $products[] = [
            'product_id'           => $pId,
            'product_name'         => $r['product_name'] ?? 'Unknown Product',
            'product_sku'          => $r['product_sku'] ?? '',
            'category_id'          => $r['category_id'] ?? '',
            'is_grafted'           => (int)$r['is_grafted'],
            'total_production_qty' => $prodQty,
            'active_orders_count'  => $activeOrdersCnt,
            'total_bed_plants'     => $bedPlants,
            'active_trays_count'   => $activeTraysCnt,
            'total_assigned_qty'   => $assignedQty,
            'total_allocated_qty'  => $allocatedQty,
            'total_dispatched_qty' => $dispatchedQty,
            'physical_open_stock'  => $physicalOpen,
            'book_open_stock'      => $bookOpen
        ];
    }

    sendJsonResponse(true, [
        'products'  => $products,
        'kpi'       => $kpiTotals
    ]);
}

// ACTION 1.1: GET ACTIVE PRODUCTS FOR DROPDOWN FILTER
if ($action === 'get_products_dropdown') {
    $res = $krfconn->query("
        SELECT ProductID, ProductName, sku,
            CASE 
                WHEN LOWER(TRIM(COALESCE(IsGrafted, ''))) = 'yes' 
                  OR (ProductName LIKE '%Grafted%' AND ProductName NOT LIKE '%NonGrafted%' AND ProductName NOT LIKE '%Non-Grafted%') 
                THEN 1 
                ELSE 0 
            END AS is_grafted
        FROM product_master
        ORDER BY ProductName ASC
    ");

    $list = [];
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $list[] = [
                'id'         => (int)$r['ProductID'],
                'name'       => $r['ProductName'],
                'sku'        => $r['sku'],
                'is_grafted' => (int)$r['is_grafted']
            ];
        }
    }
    sendJsonResponse(true, $list);
}
if ($action === 'get_product_stockorders') {
    $productId = (int)($_REQUEST['product_id'] ?? 0);
    if ($productId <= 0) {
        sendJsonResponse(false, [], 'Valid Product ID is required');
    }

    // Fetch product details
    $pQuery = $krfconn->query("SELECT ProductID, ProductName, sku FROM product_master WHERE ProductID = {$productId} LIMIT 1");
    $productInfo = $pQuery ? $pQuery->fetch_assoc() : null;

    $projWhereStock = " (stk.projectid = {$projectid} OR '{$projectid}' = '0' OR stk.projectid = 17) AND (stk.tedit IS NULL OR stk.tedit = '') ";
    $projWherePh    = " (ph.projectid = '{$projectid}' OR '{$projectid}' = '0' OR ph.projectid = '17') AND (ph.tedit IS NULL OR ph.tedit = '') ";
    $projWhereCons  = " (c.projectid = {$projectid} OR '{$projectid}' = '0' OR c.projectid = 17) AND (c.tedit IS NULL OR c.tedit = '') ";
    $projWhereAlloc = " (rsa.projectid = {$projectid} OR '{$projectid}' = '0' OR rsa.projectid = 17) AND (rsa.tedit IS NULL OR rsa.tedit = '') ";
    $projWhereInv   = " (sii.projectid = {$projectid} OR '{$projectid}' = '0' OR sii.projectid = 17) ";

    /**
     * Fetch individual production batches under this product
     * with reconciled bed plants, assigned, allocated, and dispatched quantities.
     */
    $sql = "
        SELECT 
            stk.id,
            stk.stockorder_no,
            stk.psono,
            stk.productid,
            stk.product_name,
            stk.quantity AS order_qty,
            stk.sowinglocationid,
            DATE_FORMAT(stk.sowingdate, '%d-%m-%Y') AS sowing_date_disp,
            stk.sowingdate,
            DATE_FORMAT(stk.expected_delivery_date, '%d-%m-%Y') AS ready_date_disp,
            stk.expected_delivery_date,
            COALESCE(stk.status, '') AS status,
            COALESCE(stk.workingstatus, '') AS workingstatus,
            COALESCE(stk.clientid, '') AS clientid,
            COALESCE(cl.client_name, stk.clientid) AS client_name,

            -- 1. Bed-wise Living Plants
            COALESCE(ph.bed_plants, 0) AS bed_plants,
            COALESCE(ph.tray_count, 0) AS tray_count,

            -- 2. Consumed / Assigned Quantity
            COALESCE(cons.assigned_qty, 0) AS assigned_qty,

            -- 3. Ready Stock Allocated Quantity
            COALESCE(alloc.allocated_qty, 0) AS allocated_qty,

            -- 4. Sales Invoice Dispatched Quantity
            COALESCE(inv.dispatched_qty, 0) AS dispatched_qty

        FROM krf_production_stockorder stk
        LEFT JOIN krf_client_master cl 
            ON CONVERT(cl.client_id USING utf8mb4) = CONVERT(stk.clientid USING utf8mb4) 
            AND (cl.projectid = stk.projectid OR cl.projectid = 17)

        -- Bed Plants Subquery for this order
        LEFT JOIN (
            SELECT 
                CONVERT(ph.stockorder_no USING utf8mb4) COLLATE utf8mb4_unicode_ci AS stockorder_no,
                SUM(GREATEST(0, COALESCE(ph.qtypertray, 0) - COALESCE(ph.failurecount, 0) - COALESCE(ph.dispatchqty, 0))) AS bed_plants,
                COUNT(ph.polyhouseplantid) AS tray_count
            FROM krf_polyhouse_plant_details ph
            WHERE {$projWherePh}
              AND LOWER(TRIM(COALESCE(ph.status, ''))) IN ('polyhouse', 'readytosale')
            GROUP BY CONVERT(ph.stockorder_no USING utf8mb4)
        ) ph ON ph.stockorder_no = stk.stockorder_no

        -- Consumed / Assigned Subquery for this order
        LEFT JOIN (
            SELECT 
                CONVERT(c.stockorderno USING utf8mb4) COLLATE utf8mb4_unicode_ci AS stockorderno,
                SUM(COALESCE(c.consumed_qty, 0)) AS assigned_qty
            FROM krf_productionorder_consumed c
            WHERE {$projWhereCons}
              AND LOWER(TRIM(COALESCE(c.status, ''))) NOT IN ('cancelled', 'cancel')
              AND LOWER(TRIM(COALESCE(c.confirmstatus, ''))) NOT IN ('cancelled', 'cancel')
            GROUP BY CONVERT(c.stockorderno USING utf8mb4)
        ) cons ON cons.stockorderno = stk.stockorder_no

        -- Ready Stock Allocations Subquery for this order
        LEFT JOIN (
            SELECT 
                CONVERT(COALESCE(NULLIF(rsa.allocate_stockorder_no, ''), rsa.stockorder_no) USING utf8mb4) COLLATE utf8mb4_unicode_ci AS alloc_stk,
                SUM(COALESCE(rsa.allocate_ordqty, 0)) AS allocated_qty
            FROM krf_readystock_allocation rsa
            WHERE {$projWhereAlloc}
              AND LOWER(TRIM(COALESCE(rsa.status, ''))) NOT IN ('cancelled', 'cancel')
              AND (rsa.tedit IS NULL OR rsa.tedit = '')
            GROUP BY alloc_stk
        ) alloc ON alloc.alloc_stk = stk.stockorder_no

        -- Sales Invoice Dispatched Subquery for this order
        LEFT JOIN (
            SELECT 
                CONVERT(sii.stockorder_no USING utf8mb4) COLLATE utf8mb4_unicode_ci AS stockorder_no,
                SUM(COALESCE(sii.total_dispatch_plants, sii.quantity, 0)) AS dispatched_qty
            FROM sales_invoice_items sii
            WHERE {$projWhereInv}
              AND LOWER(TRIM(COALESCE(sii.status, ''))) = 'dispatched'
              AND (sii.tedit IS NULL OR sii.tedit = '' OR sii.tedit = '0')
            GROUP BY CONVERT(sii.stockorder_no USING utf8mb4)
        ) inv ON inv.stockorder_no = stk.stockorder_no

        WHERE stk.productid = {$productId}
          AND {$projWhereStock}
          AND LOWER(TRIM(COALESCE(stk.status, ''))) NOT IN ('cancelled', 'cancel')
        ORDER BY stk.expected_delivery_date ASC, stk.id DESC
    ";

    $res = $krfconn->query($sql);
    if (!$res) {
        sendJsonResponse(false, [], 'Database Query Failed: ' . $krfconn->error);
    }

    $orders = [];
    while ($r = $res->fetch_assoc()) {
        $ordQty       = (int)$r['order_qty'];
        $bedQty       = (int)$r['bed_plants'];
        $assignQty    = (int)$r['assigned_qty'];
        $allocQty     = (int)$r['allocated_qty'];
        $dispQty      = (int)$r['dispatched_qty'];

        // Batch-level Open Stock calculations
        $physicalOpen = max(0, $bedQty - $allocQty);
        $bookOpen     = max(0, $ordQty - $assignQty - $allocQty);

        $orders[] = [
            'id'                   => (int)$r['id'],
            'stockorder_no'        => $r['stockorder_no'],
            'psono'                => $r['psono'] ?? '',
            'client_name'          => $r['client_name'] ?? '',
            'clientid'             => $r['clientid'] ?? '',
            'sowinglocationid'     => $r['sowinglocationid'] ?? '',
            'sowing_date'          => $r['sowing_date_disp'] ?? '',
            'expected_delivery_date' => $r['ready_date_disp'] ?? '',
            'status'               => $r['status'] ?? '',
            'workingstatus'        => $r['workingstatus'] ?? '',
            'order_qty'            => $ordQty,
            'bed_plants'           => $bedQty,
            'tray_count'           => (int)$r['tray_count'],
            'assigned_qty'         => $assignQty,
            'allocated_qty'        => $allocQty,
            'dispatched_qty'       => $dispQty,
            'physical_open_stock'  => $physicalOpen,
            'book_open_stock'      => $bookOpen
        ];
    }

    sendJsonResponse(true, [
        'product' => $productInfo,
        'orders'  => $orders
    ]);
}

// ACTION 3: GET ORDER DEEP-DRILLDOWN DETAILS (BED TRAYS, ASSIGNED, ALLOCATIONS, DISPATCHES)
if ($action === 'get_stockorder_deep_details') {
    $stockOrderNo = trim($_REQUEST['stockorder_no'] ?? '');
    if (empty($stockOrderNo)) {
        sendJsonResponse(false, [], 'Stock Order No is required');
    }

    $safeStk = $krfconn->real_escape_string($stockOrderNo);

    // 1. Fetch Production Stock Order Header
    $hdrSql = "
        SELECT 
            stk.*,
            DATE_FORMAT(stk.sowingdate, '%d-%m-%Y') AS sowing_date_disp,
            DATE_FORMAT(stk.expected_delivery_date, '%d-%m-%Y') AS ready_date_disp,
            COALESCE(cl.client_name, stk.clientid) AS client_name,
            p.ProductName,
            p.sku
        FROM krf_production_stockorder stk
        LEFT JOIN krf_client_master cl 
            ON CONVERT(cl.client_id USING utf8mb4) = CONVERT(stk.clientid USING utf8mb4) 
            AND (cl.projectid = stk.projectid OR cl.projectid = 17)
        LEFT JOIN product_master p 
            ON p.ProductID = stk.productid
        WHERE stk.stockorder_no = '{$safeStk}'
          AND (stk.tedit IS NULL OR stk.tedit = '')
        LIMIT 1
    ";
    $hdrRes = $krfconn->query($hdrSql);
    $orderHeader = $hdrRes ? $hdrRes->fetch_assoc() : null;

    if (!$orderHeader) {
        sendJsonResponse(false, [], "Order batch '{$stockOrderNo}' not found");
    }

    // 2. Fetch Bed-Wise Physical Trays from krf_polyhouse_plant_details
    // User requirement: Active tray is only status 'polyhouse' or 'readytosale'
    $traySql = "
        SELECT 
            polyhouseplantid,
            tray_id,
            tray_code,
            locationid,
            polyhouseid,
            bed_no,
            bedrackname,
            shelf_level,
            growth_stage,
            health_status,
            status,
            COALESCE(qtypertray, 0) AS qtypertray,
            COALESCE(failurecount, 0) AS failurecount,
            COALESCE(dispatchqty, 0) AS dispatchqty,
            GREATEST(0, COALESCE(qtypertray, 0) - COALESCE(failurecount, 0) - COALESCE(dispatchqty, 0)) AS net_plants,
            DATE_FORMAT(planted_date, '%d-%m-%Y') AS planted_date_disp,
            DATE_FORMAT(sowing_date, '%d-%m-%Y') AS sowing_date_disp,
            DATE_FORMAT(last_inspection_date, '%d-%m-%Y') AS last_inspection_disp,
            remarks
        FROM krf_polyhouse_plant_details
        WHERE CONVERT(stockorder_no USING utf8mb4) = '{$safeStk}'
          AND (tedit IS NULL OR tedit = '')
        ORDER BY 
            CASE 
                WHEN LOWER(TRIM(COALESCE(status, ''))) = 'readytosale' THEN 1
                WHEN LOWER(TRIM(COALESCE(status, ''))) = 'polyhouse' THEN 2
                ELSE 3
            END,
            polyhouseid ASC, bed_no ASC, tray_code ASC
    ";
    $trayRes = $krfconn->query($traySql);
    $trays = [];
    $traySummary = [
        'total_trays'       => 0,
        'active_trays'      => 0,
        'active_net_plants' => 0,
        'readytosale_plants'=> 0,
        'polyhouse_plants'  => 0,
        'other_plants'      => 0
    ];

    if ($trayRes) {
        while ($t = $trayRes->fetch_assoc()) {
            $st = strtolower(trim($t['status'] ?? ''));
            $net = (int)$t['net_plants'];
            $traySummary['total_trays']++;

            if ($st === 'polyhouse' || $st === 'readytosale') {
                $traySummary['active_trays']++;
                $traySummary['active_net_plants'] += $net;
                if ($st === 'readytosale') {
                    $traySummary['readytosale_plants'] += $net;
                } else {
                    $traySummary['polyhouse_plants'] += $net;
                }
            } else {
                $traySummary['other_plants'] += $net;
            }

            $trays[] = [
                'polyhouseplantid'     => (int)$t['polyhouseplantid'],
                'tray_code'            => $t['tray_code'] ?? 'N/A',
                'polyhouseid'          => $t['polyhouseid'] ?? 'Main',
                'bed_no'               => $t['bed_no'] ?? '-',
                'bedrackname'          => $t['bedrackname'] ?? '-',
                'shelf_level'          => $t['shelf_level'] ?? '-',
                'growth_stage'         => $t['growth_stage'] ?? '-',
                'health_status'        => $t['health_status'] ?? 'Healthy',
                'status'               => $t['status'] ?? '',
                'qtypertray'           => (int)$t['qtypertray'],
                'failurecount'         => (int)$t['failurecount'],
                'dispatchqty'          => (int)$t['dispatchqty'],
                'net_plants'           => $net,
                'planted_date'         => $t['planted_date_disp'] ?? '',
                'last_inspection_date' => $t['last_inspection_disp'] ?? ''
            ];
        }
    }

    // 3. Fetch Consumed / Assigned Orders from krf_productionorder_consumed
    $consSql = "
        SELECT 
            c.id,
            c.psono,
            c.sono,
            c.stockorderno,
            c.product_id,
            c.product_name,
            c.consumed_qty,
            c.wastage_qty,
            c.dispatchedqty,
            c.confirmstatus,
            c.status,
            c.remark,
            DATE_FORMAT(c.created_at, '%d-%m-%Y %H:%i') AS created_at_disp,
            COALESCE(cl.client_name, so.client_name, 'Client Order') AS client_name
        FROM krf_productionorder_consumed c
        LEFT JOIN krf_sales_order so 
            ON CONVERT(so.psono USING utf8mb4) = CONVERT(c.psono USING utf8mb4) 
            AND (so.projectid = c.projectid OR so.projectid = 17)
        LEFT JOIN krf_client_master cl 
            ON CONVERT(cl.client_id USING utf8mb4) = CONVERT(so.clientid USING utf8mb4) 
            AND (cl.projectid = c.projectid OR cl.projectid = 17)
        WHERE CONVERT(c.stockorderno USING utf8mb4) = '{$safeStk}'
          AND (c.tedit IS NULL OR c.tedit = '')
        ORDER BY c.id DESC
    ";
    $consRes = $krfconn->query($consSql);
    $consumedOrders = [];
    $totConsumed = 0;
    if ($consRes) {
        while ($c = $consRes->fetch_assoc()) {
            $cQty = (int)$c['consumed_qty'];
            $totConsumed += $cQty;
            $consumedOrders[] = [
                'id'            => (int)$c['id'],
                'psono'         => $c['psono'] ?? '',
                'sono'          => $c['sono'] ?? '',
                'client_name'   => $c['client_name'] ?? 'N/A',
                'consumed_qty'  => $cQty,
                'dispatchedqty' => (int)$c['dispatchedqty'],
                'confirmstatus' => $c['confirmstatus'] ?? 'Pending',
                'status'        => $c['status'] ?? 'pending',
                'remark'        => $c['remark'] ?? '',
                'created_at'    => $c['created_at_disp'] ?? ''
            ];
        }
    }

    // 4. Fetch Ready Stock Allocations from krf_readystock_allocation
    $allocSql = "
        SELECT 
            rsa.id,
            rsa.stockorder_no,
            rsa.allocate_stockorder_no,
            rsa.clientid,
            rsa.productid,
            rsa.productname,
            rsa.allocate_ordqty,
            rsa.healthyplant,
            rsa.unhealthyplant,
            rsa.dispatchqty,
            rsa.status,
            DATE_FORMAT(rsa.created_at, '%d-%m-%Y %H:%i') AS created_at_disp,
            COALESCE(cl.client_name, rsa.clientid) AS client_name
        FROM krf_readystock_allocation rsa
        LEFT JOIN krf_client_master cl 
            ON CONVERT(cl.client_id USING utf8mb4) = CONVERT(rsa.clientid USING utf8mb4) 
            AND (cl.projectid = rsa.projectid OR cl.projectid = 17)
        WHERE (rsa.allocate_stockorder_no = '{$safeStk}' OR rsa.stockorder_no = '{$safeStk}')
          AND (rsa.tedit IS NULL OR rsa.tedit = '')
          AND LOWER(TRIM(COALESCE(rsa.status, ''))) NOT IN ('cancelled', 'cancel')
        ORDER BY rsa.id DESC
    ";
    $allocRes = $krfconn->query($allocSql);
    $allocations = [];
    $totAllocated = 0;
    if ($allocRes) {
        while ($a = $allocRes->fetch_assoc()) {
            $aQty = (int)$a['allocate_ordqty'];
            $totAllocated += $aQty;
            $allocations[] = [
                'id'            => (int)$a['id'],
                'clientid'      => $a['clientid'] ?? '',
                'client_name'   => $a['client_name'] ?? 'N/A',
                'allocate_ordqty'=> $aQty,
                'healthyplant'  => (int)$a['healthyplant'],
                'dispatchqty'   => (int)$a['dispatchqty'],
                'status'        => $a['status'] ?? 'allocated',
                'created_at'    => $a['created_at_disp'] ?? ''
            ];
        }
    }

    // 5. Fetch Dispatched Sales Invoices from sales_invoice & sales_invoice_items
    $invSql = "
        SELECT 
            sii.id AS item_id,
            sii.invoiceno,
            sii.psono,
            sii.sono,
            sii.clientid,
            COALESCE(si.clientname, sii.clientid) AS clientname,
            DATE_FORMAT(si.invoicedate, '%d-%m-%Y') AS invoicedate_disp,
            COALESCE(sii.total_dispatch_plants, sii.quantity, 0) AS dispatch_plants,
            sii.rate,
            sii.amount,
            si.vehicleno,
            sii.status
        FROM sales_invoice_items sii
        LEFT JOIN sales_invoice si 
            ON CONVERT(si.invoiceno USING utf8mb4) = CONVERT(sii.invoiceno USING utf8mb4) 
            AND (si.projectid = sii.projectid OR si.projectid = 17)
        WHERE sii.stockorder_no = '{$safeStk}'
          AND LOWER(TRIM(COALESCE(sii.status, ''))) = 'dispatched'
          AND (sii.tedit IS NULL OR sii.tedit = '' OR sii.tedit = '0')
          AND (si.tedit IS NULL OR si.tedit = '' OR si.tedit = '0')
        ORDER BY sii.id DESC
    ";
    $invRes = $krfconn->query($invSql);
    $invoices = [];
    $totDispatched = 0;
    if ($invRes) {
        while ($inv = $invRes->fetch_assoc()) {
            $dPlants = (int)$inv['dispatch_plants'];
            $totDispatched += $dPlants;
            $invoices[] = [
                'item_id'         => (int)$inv['item_id'],
                'invoiceno'       => $inv['invoiceno'] ?? 'N/A',
                'invoicedate'     => $inv['invoicedate_disp'] ?? '',
                'clientname'      => $inv['clientname'] ?? 'N/A',
                'psono'           => $inv['psono'] ?? '',
                'dispatch_plants' => $dPlants,
                'rate'            => (float)$inv['rate'],
                'amount'          => (float)$inv['amount'],
                'vehicleno'       => $inv['vehicleno'] ?? '-',
                'status'          => $inv['status'] ?? 'dispatched'
            ];
        }
    }

    sendJsonResponse(true, [
        'header'           => $orderHeader,
        'tray_summary'     => $traySummary,
        'trays'            => $trays,
        'consumed_orders'  => $consumedOrders,
        'allocations'      => $allocations,
        'invoices'         => $invoices,
        'totals'           => [
            'order_qty'      => (int)$orderHeader['quantity'],
            'bed_active_qty' => $traySummary['active_net_plants'],
            'consumed_qty'   => $totConsumed,
            'allocated_qty'  => $totAllocated,
            'dispatched_qty' => $totDispatched
        ]
    ]);
}

sendJsonResponse(false, [], 'Invalid action requested');
