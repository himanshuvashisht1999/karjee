<?php
/**
 * openstocklivemodal.php
 * Backend API & Live Calculation Engine for Warehouse Stock vs Confirmed / Partial Dispatch Customer Orders.
 * 
 * Rules & Requirements:
 * - Summary as per Product ID.
 * - Table Headers: Total Warehouse Plants, Customer Demand, Sufficiency Analysis, Action.
 * - Location is a FILTER, NOT table headers.
 * - Demand considers 'Confirmed' and 'Partial Dispatch' orders to be dispatched:
 *   Net to Dispatch = GREATEST(0, quantity - dispatchedqty)
 * - Year list retrieved dynamically from plant table (krf_polyhouse_plant_details).
 * - Date range filter: default Full Year of selected year, plus Custom Range option.
 * - Clicking any product opens full order details modal with comprehensive client and dispatch data.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(0);
ini_set('display_errors', '0');
date_default_timezone_set('Asia/Kolkata');

// Database Connection
require_once(__DIR__ . '/../../../connection.php');

header('Content-Type: application/json; charset=utf-8');

// Session Parameters
$loginid      = $_SESSION['loginid'] ?? $_SESSION['userid'] ?? 'admin';
$loginname    = $_SESSION['loginname'] ?? $_SESSION['username'] ?? $_SESSION['name'] ?? 'Admin';
$companyid    = (int)($_SESSION['companyid'] ?? 1);
$subcompanyid = (int)($_SESSION['subcompanyid'] ?? 1);
$projectid    = (int)($_SESSION['projectid'] ?? 17);
if ($projectid <= 0) $projectid = 17;

// Constant Location Definitions as specified by User
define('LOC_1_ID', 'LOC6905e0fd5ab05');
define('LOC_2_ID', 'LOC696b280faf104');
define('LOC_1_LABEL', 'Location 1 (9 Polyhouses)');
define('LOC_2_LABEL', 'Location 2 (2 Polyhouses)');

// Helper for standard JSON responses
function sendJsonResponse($status, $data = [], $message = '', $meta = []) {
    echo json_encode([
        'status'    => $status,
        'message'   => $message,
        'data'      => $data,
        'meta'      => $meta,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
    exit;
}

// Action Dispatcher
$action = trim($_REQUEST['action'] ?? '');
if (empty($action)) {
    sendJsonResponse(false, [], 'No action specified');
}

// ACTION 0: GET YEARS LIST FROM PLANT TABLE
if ($action === 'get_years_list') {
    $sql = "
        SELECT DISTINCT yr FROM (
            SELECT YEAR(sowing_date) as yr FROM krf_polyhouse_plant_details 
            WHERE sowing_date IS NOT NULL AND sowing_date != '0000-00-00'
            UNION
            SELECT YEAR(planted_date) as yr FROM krf_polyhouse_plant_details 
            WHERE planted_date IS NOT NULL AND planted_date != '0000-00-00'
            UNION
            SELECT YEAR(created_at) as yr FROM krf_polyhouse_plant_details 
            WHERE created_at IS NOT NULL
        ) t 
        WHERE yr IS NOT NULL AND yr >= 2020
        ORDER BY yr DESC
    ";

    $res = $krfconn->query($sql);
    $years = [];
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $years[] = (int)$r['yr'];
        }
    }
    if (empty($years)) {
        $years = [ (int)date('Y') ];
    }

    sendJsonResponse(true, $years, 'Years retrieved successfully');
}

// ACTION 1: GET LIVE STOCK SUMMARY (PER PRODUCT ID)
if ($action === 'get_live_stock_summary') {
    $search         = trim($_REQUEST['search'] ?? '');
    $demandType     = trim($_REQUEST['demand_type'] ?? 'confirmed_partial'); // 'confirmed_partial', 'confirmed_only', 'pending', 'all'
    $statusFilter   = trim($_REQUEST['status_filter'] ?? 'all');             // 'all', 'sufficient', 'shortage', 'ready_sufficient', 'growing_needed', 'surplus_only', 'zero_stock'
    $graftedFilter  = trim($_REQUEST['grafted_filter'] ?? 'all');            // 'all', 'grafted', 'nongrafted'
    $locationFilter = trim($_REQUEST['location_filter'] ?? 'all');           // 'all', LOC_1_ID, LOC_2_ID
    $yearFilter     = trim($_REQUEST['year'] ?? '');
    $dateFrom       = trim($_REQUEST['date_from'] ?? '');
    $dateTo         = trim($_REQUEST['date_to'] ?? '');

    $loc1Safe = $krfconn->real_escape_string(LOC_1_ID);
    $loc2Safe = $krfconn->real_escape_string(LOC_2_ID);

    // 1. Polyhouse Location Filter
    $polyLocWhere = " ph.locationid IN ('{$loc1Safe}', '{$loc2Safe}') ";
    if ($locationFilter === LOC_1_ID) {
        $polyLocWhere = " ph.locationid = '{$loc1Safe}' ";
    } else if ($locationFilter === LOC_2_ID) {
        $polyLocWhere = " ph.locationid = '{$loc2Safe}' ";
    }

    // 2. Polyhouse Date / Year Filter (Applied to sowing_date / planted_date)
    $polyDateClause = "";
    if (!empty($dateFrom) && !empty($dateTo)) {
        $fromSafe = $krfconn->real_escape_string($dateFrom);
        $toSafe   = $krfconn->real_escape_string($dateTo);
        $polyDateClause .= " AND (
            (ph.sowing_date IS NOT NULL AND ph.sowing_date != '0000-00-00' AND ph.sowing_date BETWEEN '{$fromSafe}' AND '{$toSafe}')
            OR (ph.planted_date IS NOT NULL AND ph.planted_date != '0000-00-00' AND ph.planted_date BETWEEN '{$fromSafe}' AND '{$toSafe}')
            OR (DATE(ph.created_at) BETWEEN '{$fromSafe}' AND '{$toSafe}')
            OR (ph.sowing_date IS NULL AND ph.planted_date IS NULL)
        ) ";
    } else if (!empty($yearFilter) && $yearFilter !== 'all') {
        $yrInt = (int)$yearFilter;
        $polyDateClause .= " AND (
            YEAR(ph.sowing_date) = {$yrInt}
            OR YEAR(ph.planted_date) = {$yrInt}
            OR YEAR(ph.created_at) = {$yrInt}
            OR (ph.sowing_date IS NULL AND ph.planted_date IS NULL)
        ) ";
    }

    // 3. Sales Order Demand Status Condition
    // Default: 'confirmed_partial' -> Confirmed and PartialDispatch orders to be dispatched
    $soDemandClause = " (LOWER(TRIM(COALESCE(soi.status, ''))) IN ('confirmed', 'partialdispatch') OR LOWER(TRIM(COALESCE(so.status, ''))) IN ('confirmed', 'partialdispatch')) ";
    if ($demandType === 'confirmed_only') {
        $soDemandClause = " (LOWER(TRIM(COALESCE(soi.status, ''))) = 'confirmed' OR LOWER(TRIM(COALESCE(so.status, ''))) = 'confirmed') ";
    } else if ($demandType === 'pending') {
        $soDemandClause = " (LOWER(TRIM(COALESCE(soi.status, ''))) NOT IN ('cancelled', 'cancel', 'dispatched', 'fulldispatch')) ";
    } else if ($demandType === 'all') {
        $soDemandClause = " (LOWER(TRIM(COALESCE(soi.status, ''))) NOT IN ('cancelled', 'cancel')) ";
    }

    // 4. Sales Order Date / Year Filter (Applied to deliverydate / confirmdeliverydate / created_at)
    $soDateClause = "";
    if (!empty($dateFrom) && !empty($dateTo)) {
        $fromSafe = $krfconn->real_escape_string($dateFrom);
        $toSafe   = $krfconn->real_escape_string($dateTo);
        $soDateClause .= " AND (
            (soi.deliverydate IS NOT NULL AND soi.deliverydate != '0000-00-00' AND soi.deliverydate BETWEEN '{$fromSafe}' AND '{$toSafe}')
            OR (soi.confirmdeliverydate IS NOT NULL AND soi.confirmdeliverydate != '0000-00-00' AND soi.confirmdeliverydate BETWEEN '{$fromSafe}' AND '{$toSafe}')
            OR (soi.deliverydate IS NULL AND DATE(so.created_at) BETWEEN '{$fromSafe}' AND '{$toSafe}')
        ) ";
    } else if (!empty($yearFilter) && $yearFilter !== 'all') {
        $yrInt = (int)$yearFilter;
        $soDateClause .= " AND (
            YEAR(soi.deliverydate) = {$yrInt}
            OR YEAR(soi.confirmdeliverydate) = {$yrInt}
            OR (soi.deliverydate IS NULL AND YEAR(so.created_at) = {$yrInt})
        ) ";
    }

    // 5. Product Filter Clause (Search and Grafted)
    $prodFilterClause = "";
    if ($search !== '') {
        $searchSafe = $krfconn->real_escape_string($search);
        $prodFilterClause .= " AND (p_ids.product_id LIKE '%{$searchSafe}%' OR p_ids.product_name LIKE '%{$searchSafe}%' OR pm.ProductName LIKE '%{$searchSafe}%' OR pm.sku LIKE '%{$searchSafe}%') ";
    }
    if ($graftedFilter === 'grafted') {
        $prodFilterClause .= " AND (LOWER(TRIM(COALESCE(pm.IsGrafted, ''))) = 'yes' OR (pm.ProductName LIKE '%Grafted%' AND pm.ProductName NOT LIKE '%NonGrafted%' AND pm.ProductName NOT LIKE '%Non-Grafted%')) ";
    } else if ($graftedFilter === 'nongrafted') {
        $prodFilterClause .= " AND (LOWER(TRIM(COALESCE(pm.IsGrafted, ''))) != 'yes' AND (pm.ProductName NOT LIKE '%Grafted%' OR pm.ProductName LIKE '%NonGrafted%' OR pm.ProductName LIKE '%Non-Grafted%')) ";
    }

    $sql = "
        SELECT 
            p_ids.product_id,
            COALESCE(NULLIF(pm.ProductName, ''), p_ids.product_name) AS product_name,
            COALESCE(pm.sku, '') AS product_sku,
            COALESCE(pm.CategoryId, '') AS category_id,
            CASE 
                WHEN LOWER(TRIM(COALESCE(pm.IsGrafted, ''))) = 'yes' 
                  OR (pm.ProductName LIKE '%Grafted%' AND pm.ProductName NOT LIKE '%NonGrafted%' AND pm.ProductName NOT LIKE '%Non-Grafted%') 
                THEN 1 
                ELSE 0 
            END AS is_grafted,

            -- Warehouse Polyhouse Stock (Ready to Sale, Growing, Total)
            COALESCE(ph.ready_stock_qty, 0) AS ready_stock_qty,
            COALESCE(ph.growing_stock_qty, 0) AS growing_stock_qty,
            (COALESCE(ph.ready_stock_qty, 0) + COALESCE(ph.growing_stock_qty, 0)) AS total_warehouse_stock,
            COALESCE(ph.trays_count, 0) AS trays_count,

            -- Location Breakdowns for Tooltips/Context
            COALESCE(ph.loc1_total, 0) AS loc1_total_qty,
            COALESCE(ph.loc2_total, 0) AS loc2_total_qty,

            -- Customer Demand Orders ('Confirmed, Partial Dispatch' to be dispatched)
            COALESCE(so.net_to_dispatch_qty, 0) AS net_to_dispatch_qty,
            COALESCE(so.dispatched_qty, 0) AS dispatched_qty,
            COALESCE(so.total_order_qty, 0) AS total_order_qty,
            COALESCE(so.active_orders_count, 0) AS active_orders_count

        FROM (
            -- Union of active products from Polyhouse details and Sales Order Items
            SELECT DISTINCT productid AS product_id, MAX(productname) AS product_name
            FROM krf_polyhouse_plant_details ph
            WHERE ph.status IN ('Polyhouse', 'Readytosale')
              AND {$polyLocWhere}
              {$polyDateClause}
              AND (ph.projectid = '{$projectid}' OR '{$projectid}' = '0' OR ph.projectid = '17')
            GROUP BY productid
            
            UNION
            
            SELECT DISTINCT soi.product_id, MAX(soi.product_name) AS product_name
            FROM krf_sales_order_items soi
            LEFT JOIN krf_sales_order so ON so.sono = soi.sono
            WHERE {$soDemandClause}
              {$soDateClause}
              AND soi.product_id IS NOT NULL AND soi.product_id != ''
              AND (soi.projectid = {$projectid} OR '{$projectid}' = '0' OR soi.projectid = 17)
            GROUP BY soi.product_id
        ) p_ids

        LEFT JOIN product_master pm ON pm.ProductID = p_ids.product_id

        -- Polyhouse Stock Aggregation
        LEFT JOIN (
            SELECT 
                ph.productid,
                SUM(CASE WHEN ph.status = 'Readytosale' 
                         THEN GREATEST(0, COALESCE(ph.qtypertray, 0) - COALESCE(ph.failurecount, 0) - COALESCE(ph.dispatchqty, 0)) ELSE 0 END) AS ready_stock_qty,
                SUM(CASE WHEN ph.status = 'Polyhouse' 
                         THEN GREATEST(0, COALESCE(ph.qtypertray, 0) - COALESCE(ph.failurecount, 0) - COALESCE(ph.dispatchqty, 0)) ELSE 0 END) AS growing_stock_qty,
                COUNT(ph.polyhouseplantid) AS trays_count,

                -- Breakdown for tooltips/indicators
                SUM(CASE WHEN ph.locationid = '{$loc1Safe}' 
                         THEN GREATEST(0, COALESCE(ph.qtypertray, 0) - COALESCE(ph.failurecount, 0) - COALESCE(ph.dispatchqty, 0)) ELSE 0 END) AS loc1_total,
                SUM(CASE WHEN ph.locationid = '{$loc2Safe}' 
                         THEN GREATEST(0, COALESCE(ph.qtypertray, 0) - COALESCE(ph.failurecount, 0) - COALESCE(ph.dispatchqty, 0)) ELSE 0 END) AS loc2_total

            FROM krf_polyhouse_plant_details ph
            WHERE ph.status IN ('Polyhouse', 'Readytosale')
              AND {$polyLocWhere}
              {$polyDateClause}
              AND (ph.projectid = '{$projectid}' OR '{$projectid}' = '0' OR ph.projectid = '17')
            GROUP BY ph.productid
        ) ph ON ph.productid = p_ids.product_id

        -- Customer Demand Aggregation
        LEFT JOIN (
            SELECT 
                soi.product_id,
                SUM(GREATEST(0, COALESCE(soi.quantity, 0) - COALESCE(soi.dispatchedqty, 0))) AS net_to_dispatch_qty,
                SUM(COALESCE(soi.dispatchedqty, 0)) AS dispatched_qty,
                SUM(COALESCE(soi.quantity, 0)) AS total_order_qty,
                COUNT(DISTINCT soi.sono) AS active_orders_count
            FROM krf_sales_order_items soi
            LEFT JOIN krf_sales_order so ON so.sono = soi.sono
            WHERE {$soDemandClause}
              {$soDateClause}
              AND (soi.projectid = {$projectid} OR '{$projectid}' = '0' OR soi.projectid = 17)
            GROUP BY soi.product_id
        ) so ON so.product_id = p_ids.product_id

        WHERE 1=1 {$prodFilterClause}
        ORDER BY total_warehouse_stock DESC, net_to_dispatch_qty DESC
    ";

    $res = $krfconn->query($sql);
    if (!$res) {
        sendJsonResponse(false, [], 'Query execution failed: ' . $krfconn->error);
    }

    $items = [];
    
    // KPI Counters
    $kpi = [
        'total_warehouse_stock'  => 0,
        'total_ready_stock'      => 0,
        'total_growing_stock'    => 0,
        'total_order_demand'     => 0,
        'total_dispatched_qty'   => 0,
        'total_net_balance'      => 0,
        'count_sufficient_ready' => 0,
        'count_sufficient_grow'  => 0,
        'count_shortage'         => 0,
        'count_surplus_only'     => 0,
        'count_zero_stock'       => 0,
        'total_products'         => 0,
        'total_trays'            => 0,
        'location_filter_label'  => ($locationFilter === LOC_1_ID) ? LOC_1_LABEL : (($locationFilter === LOC_2_ID) ? LOC_2_LABEL : 'All Locations')
    ];

    while ($r = $res->fetch_assoc()) {
        $availStock   = (int)$r['total_warehouse_stock'];
        $readyStock   = (int)$r['ready_stock_qty'];
        $growingStock = (int)$r['growing_stock_qty'];
        $orderDemand  = (int)$r['net_to_dispatch_qty']; // Net to dispatch (orders to be dispatched)

        // Sufficiency & Balance Math
        $netBalance   = $availStock - $orderDemand;
        $readyBalance = $readyStock - $orderDemand;

        // Classify Status
        $statusCode = '';
        $statusText = '';
        $statusClass = '';

        if ($availStock == 0 && $orderDemand > 0) {
            $statusCode  = 'zero_stock';
            $statusText  = 'Zero Stock (Demand Waiting)';
            $statusClass = 'badge-danger';
        } else if ($orderDemand == 0 && $availStock > 0) {
            $statusCode  = 'surplus_only';
            $statusText  = 'Surplus (No Orders)';
            $statusClass = 'badge-info';
        } else if ($readyStock >= $orderDemand && $orderDemand > 0) {
            $statusCode  = 'sufficient_ready';
            $statusText  = 'Ready Stock Sufficient';
            $statusClass = 'badge-success';
        } else if ($availStock >= $orderDemand && $orderDemand > 0) {
            $statusCode  = 'sufficient_grow';
            $statusText  = 'Sufficient with Growing';
            $statusClass = 'badge-primary';
        } else if ($availStock < $orderDemand) {
            $statusCode  = 'shortage';
            $statusText  = 'Stock Shortage / Deficit';
            $statusClass = 'badge-danger';
        } else {
            $statusCode  = 'balanced';
            $statusText  = 'Stock Balanced';
            $statusClass = 'badge-secondary';
        }

        // Apply Status Filter if specified
        if ($statusFilter !== 'all') {
            if ($statusFilter === 'sufficient' && !in_array($statusCode, ['sufficient_ready', 'sufficient_grow', 'surplus_only'])) {
                continue;
            } else if ($statusFilter === 'shortage' && $statusCode !== 'shortage' && $statusCode !== 'zero_stock') {
                continue;
            } else if ($statusFilter === 'ready_sufficient' && $statusCode !== 'sufficient_ready') {
                continue;
            } else if ($statusFilter === 'growing_needed' && $statusCode !== 'sufficient_grow') {
                continue;
            } else if ($statusFilter === 'surplus_only' && $statusCode !== 'surplus_only') {
                continue;
            } else if ($statusFilter === 'zero_stock' && $statusCode !== 'zero_stock') {
                continue;
            }
        }

        // Accumulate KPIs
        $kpi['total_warehouse_stock'] += $availStock;
        $kpi['total_ready_stock']     += $readyStock;
        $kpi['total_growing_stock']   += $growingStock;
        $kpi['total_order_demand']    += $orderDemand;
        $kpi['total_dispatched_qty']  += (int)$r['dispatched_qty'];
        $kpi['total_net_balance']     += $netBalance;
        $kpi['total_trays']           += (int)$r['trays_count'];
        $kpi['total_products']++;

        if ($statusCode === 'sufficient_ready') $kpi['count_sufficient_ready']++;
        if ($statusCode === 'sufficient_grow')  $kpi['count_sufficient_grow']++;
        if ($statusCode === 'shortage')         $kpi['count_shortage']++;
        if ($statusCode === 'surplus_only')     $kpi['count_surplus_only']++;
        if ($statusCode === 'zero_stock')       $kpi['count_zero_stock']++;

        $r['net_balance']    = $netBalance;
        $r['ready_balance']  = $readyBalance;
        $r['status_code']    = $statusCode;
        $r['status_text']    = $statusText;
        $r['status_class']   = $statusClass;
        $r['shortage_qty']   = ($netBalance < 0) ? abs($netBalance) : 0;
        $r['surplus_qty']    = ($netBalance > 0) ? $netBalance : 0;

        $items[] = $r;
    }

    sendJsonResponse(true, $items, 'Live stock summary retrieved successfully', $kpi);
}

// ACTION 2: GET FULL DETAILS ORDER LIST FOR CLICKED PRODUCT
if ($action === 'get_plant_orders_drilldown') {
    $productId  = trim($_REQUEST['product_id'] ?? '');
    $demandType = trim($_REQUEST['demand_type'] ?? 'confirmed_partial');
    $dateFrom   = trim($_REQUEST['date_from'] ?? '');
    $dateTo     = trim($_REQUEST['date_to'] ?? '');
    $yearFilter = trim($_REQUEST['year'] ?? '');

    if (empty($productId)) {
        sendJsonResponse(false, [], 'Product ID required');
    }

    $prodSafe = $krfconn->real_escape_string($productId);
    $where = " soi.product_id = '{$prodSafe}' 
               AND (soi.projectid = {$projectid} OR '{$projectid}' = '0' OR soi.projectid = 17) ";

    // Demand condition
    if ($demandType === 'confirmed_partial') {
        $where .= " AND (LOWER(TRIM(COALESCE(soi.status, ''))) IN ('confirmed', 'partialdispatch') OR LOWER(TRIM(COALESCE(so.status, ''))) IN ('confirmed', 'partialdispatch')) ";
    } else if ($demandType === 'confirmed_only') {
        $where .= " AND (LOWER(TRIM(COALESCE(soi.status, ''))) = 'confirmed' OR LOWER(TRIM(COALESCE(so.status, ''))) = 'confirmed') ";
    } else if ($demandType === 'pending') {
        $where .= " AND (LOWER(TRIM(COALESCE(soi.status, ''))) NOT IN ('cancelled', 'cancel', 'dispatched', 'fulldispatch')) ";
    } else {
        $where .= " AND (LOWER(TRIM(COALESCE(soi.status, ''))) NOT IN ('cancelled', 'cancel')) ";
    }

    // Date range filter
    if (!empty($dateFrom) && !empty($dateTo)) {
        $fromSafe = $krfconn->real_escape_string($dateFrom);
        $toSafe   = $krfconn->real_escape_string($dateTo);
        $where .= " AND (
            (soi.deliverydate IS NOT NULL AND soi.deliverydate != '0000-00-00' AND soi.deliverydate BETWEEN '{$fromSafe}' AND '{$toSafe}')
            OR (soi.confirmdeliverydate IS NOT NULL AND soi.confirmdeliverydate != '0000-00-00' AND soi.confirmdeliverydate BETWEEN '{$fromSafe}' AND '{$toSafe}')
            OR (soi.deliverydate IS NULL AND DATE(so.created_at) BETWEEN '{$fromSafe}' AND '{$toSafe}')
        ) ";
    } else if (!empty($yearFilter) && $yearFilter !== 'all') {
        $yrInt = (int)$yearFilter;
        $where .= " AND (
            YEAR(soi.deliverydate) = {$yrInt}
            OR YEAR(soi.confirmdeliverydate) = {$yrInt}
            OR (soi.deliverydate IS NULL AND YEAR(so.created_at) = {$yrInt})
        ) ";
    }

    $sql = "
        SELECT 
            soi.id,
            soi.serialno,
            soi.sono,
            soi.psono,
            soi.product_id,
            soi.product_name,
            soi.product_sku,
            soi.quantity,
            COALESCE(soi.dispatchedqty, 0) AS dispatchedqty,
            GREATEST(0, COALESCE(soi.quantity, 0) - COALESCE(soi.dispatchedqty, 0)) AS balance_to_dispatch,
            COALESCE(soi.reservedqty, 0) AS reservedqty,
            COALESCE(soi.rate, 0) AS rate,
            COALESCE(soi.taxableamount, 0) AS taxableamount,
            COALESCE(soi.amount, 0) AS amount,
            soi.deliverydate,
            soi.confirmdeliverydate,
            soi.confirmdeliverydateremark,
            COALESCE(NULLIF(soi.status, ''), 'Pending') AS item_status,
            COALESCE(NULLIF(so.status, ''), 'Pending') AS order_status,
            so.clientid,
            COALESCE(so.clientname, 'Unknown Client') AS clientname,
            so.mobileno,
            so.emailid,
            so.address,
            so.delivery_address,
            so.districtname,
            so.statename,
            so.payment_terms,
            so.invoicetype,
            so.dinvoiceno,
            so.invoicedate,
            so.created_at AS order_date,
            so.created_by,
            soi.remark
        FROM krf_sales_order_items soi
        LEFT JOIN krf_sales_order so ON so.sono = soi.sono
        WHERE {$where}
        ORDER BY soi.deliverydate ASC, soi.id ASC
    ";

    $res = $krfconn->query($sql);
    if (!$res) {
        sendJsonResponse(false, [], 'Query failed: ' . $krfconn->error);
    }

    $orders = [];
    $totalQty = 0;
    $totalDispatched = 0;
    $totalPending = 0;
    $totalAmount = 0;

    while ($r = $res->fetch_assoc()) {
        $qty = (int)$r['quantity'];
        $disp = (int)$r['dispatchedqty'];
        $bal = (int)$r['balance_to_dispatch'];
        $amt = (float)$r['amount'];

        $totalQty += $qty;
        $totalDispatched += $disp;
        $totalPending += $bal;
        $totalAmount += $amt;

        $orders[] = $r;
    }

    $meta = [
        'order_count'      => count($orders),
        'total_quantity'   => $totalQty,
        'total_dispatched' => $totalDispatched,
        'total_pending'    => $totalPending,
        'total_amount'     => $totalAmount
    ];

    sendJsonResponse(true, $orders, 'Orders retrieved successfully', $meta);
}

// ACTION 3: GET PLANT POLYHOUSE TRAY-LEVEL DRILLDOWN
if ($action === 'get_plant_polyhouse_drilldown') {
    $productId  = trim($_REQUEST['product_id'] ?? '');
    $locationId = trim($_REQUEST['location_id'] ?? '');
    $stockType  = trim($_REQUEST['stock_type'] ?? 'all');

    if (empty($productId)) {
        sendJsonResponse(false, [], 'Product ID required');
    }

    $prodSafe = $krfconn->real_escape_string($productId);
    $where = " ph.productid = '{$prodSafe}' 
               AND ph.status IN ('Polyhouse', 'Readytosale')
               AND ph.locationid IN ('" . LOC_1_ID . "', '" . LOC_2_ID . "')
               AND (ph.projectid = '{$projectid}' OR '{$projectid}' = '0' OR ph.projectid = '17') ";

    if (!empty($locationId) && $locationId !== 'all') {
        $locSafe = $krfconn->real_escape_string($locationId);
        $where .= " AND ph.locationid = '{$locSafe}' ";
    }

    if ($stockType === 'ready') {
        $where .= " AND ph.status = 'Readytosale' ";
    } else if ($stockType === 'growing') {
        $where .= " AND ph.status = 'Polyhouse' ";
    }

    $sql = "
        SELECT 
            ph.polyhouseplantid,
            ph.productid,
            ph.productname,
            ph.variety,
            ph.locationid,
            CASE 
                WHEN ph.locationid = '" . LOC_1_ID . "' THEN '" . LOC_1_LABEL . "'
                WHEN ph.locationid = '" . LOC_2_ID . "' THEN '" . LOC_2_LABEL . "'
                ELSE ph.locationid 
            END AS location_name,
            ph.polyhouseid,
            COALESCE(pm.polyhousename, CONCAT('PH ', ph.polyhouseid)) AS polyhousename,
            ph.bed_no,
            ph.rack_no,
            ph.row_no,
            ph.floar_no,
            ph.shelf_level,
            ph.tray_code,
            ph.barcode,
            ph.lotno,
            ph.batchno,
            ph.sowing_date,
            ph.planted_date,
            ph.growth_stage,
            ph.health_status,
            ph.status,
            COALESCE(ph.qtypertray, 0) AS qtypertray,
            COALESCE(ph.failurecount, 0) AS failurecount,
            COALESCE(ph.dispatchqty, 0) AS dispatchqty,
            GREATEST(0, COALESCE(ph.qtypertray, 0) - COALESCE(ph.failurecount, 0) - COALESCE(ph.dispatchqty, 0)) AS available_plants,
            ph.remarks
        FROM krf_polyhouse_plant_details ph
        LEFT JOIN krf_polyhouse_master pm ON pm.polyhouseid = ph.polyhouseid AND pm.locationid = ph.locationid
        WHERE {$where}
        ORDER BY ph.locationid ASC, CAST(ph.polyhouseid AS UNSIGNED) ASC, ph.polyhouseplantid ASC
    ";

    $res = $krfconn->query($sql);
    if (!$res) {
        sendJsonResponse(false, [], 'Query failed: ' . $krfconn->error);
    }

    $trays = [];
    $totalPlants = 0;
    $readyPlants = 0;
    $growingPlants = 0;

    while ($r = $res->fetch_assoc()) {
        $avail = (int)$r['available_plants'];
        $totalPlants += $avail;
        if ($r['status'] === 'Readytosale') {
            $readyPlants += $avail;
        } else {
            $growingPlants += $avail;
        }
        $trays[] = $r;
    }

    $summary = [
        'tray_count'     => count($trays),
        'total_plants'   => $totalPlants,
        'ready_plants'   => $readyPlants,
        'growing_plants' => $growingPlants
    ];

    sendJsonResponse(true, $trays, 'Trays retrieved successfully', $summary);
}

// Invalid Action Fallback
sendJsonResponse(false, [], "Invalid action '{$action}'");
