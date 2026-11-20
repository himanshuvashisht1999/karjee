<?php
/**
 * Open Sale Stock Review Modal / Controller
 * Handles all AJAX endpoints for Open Sale Stock Review page.
 * Completely independent of other controllers to ensure zero regressions.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Kolkata');

// Clean any accidental output buffer
if (ob_get_length()) ob_clean();

require_once __DIR__ . '/../../connection.php';
require_once __DIR__ . '/../functions/opensale_stock_functions.php';

function outJson($data) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

$action = trim($_REQUEST['action'] ?? '');
$projectid = isset($_SESSION['projectid']) ? (string)$_SESSION['projectid'] : '17';

// -------------------------------------------------------------
// ACTION: GET FILTER OPTIONS (LOCATIONS & PRODUCTS)
// -------------------------------------------------------------
if ($action === 'get_filters') {
    $opts = getOpenSaleFilterOptions($krfconn, $projectid);
    outJson(['status' => 'success', 'data' => $opts]);
}

// -------------------------------------------------------------
// ACTION: GET KPIS
// -------------------------------------------------------------
if ($action === 'get_kpis') {
    $filters = [
        'location'  => trim($_REQUEST['location'] ?? ''),
        'productid' => trim($_REQUEST['productid'] ?? ''),
        'date_from' => trim($_REQUEST['date_from'] ?? ''),
        'date_to'   => trim($_REQUEST['date_to'] ?? '')
    ];
    $kpis = getOpenSaleKPIs($krfconn, $projectid, $filters);
    outJson(['status' => 'success', 'data' => $kpis]);
}

// -------------------------------------------------------------
// ACTION: GET GROUPED STOCK SUMMARY (BY PRODUCT & VARIETY)
// -------------------------------------------------------------
if ($action === 'get_grouped_stock') {
    $filters = [
        'status'    => trim($_REQUEST['status'] ?? 'OpenSale'),
        'location'  => trim($_REQUEST['location'] ?? ''),
        'productid' => trim($_REQUEST['productid'] ?? ''),
        'search'    => trim($_REQUEST['search'] ?? ''),
        'date_from' => trim($_REQUEST['date_from'] ?? ''),
        'date_to'   => trim($_REQUEST['date_to'] ?? '')
    ];
    $data = getOpenSaleGroupedSummary($krfconn, $projectid, $filters);
    outJson([
        'status' => 'success',
        'count'  => count($data),
        'data'   => $data
    ]);
}

// -------------------------------------------------------------
// ACTION: GET DETAILED TRAYS LIST (PAGINATED)
// -------------------------------------------------------------
if ($action === 'get_trays_list') {
    $limit  = isset($_REQUEST['limit']) ? (int)$_REQUEST['limit'] : 1000;
    $offset = isset($_REQUEST['offset']) ? (int)$_REQUEST['offset'] : 0;
    $filters = [
        'status'    => trim($_REQUEST['status'] ?? 'OpenSale'),
        'location'  => trim($_REQUEST['location'] ?? ''),
        'productid' => trim($_REQUEST['productid'] ?? ''),
        'search'    => trim($_REQUEST['search'] ?? ''),
        'date_from' => trim($_REQUEST['date_from'] ?? ''),
        'date_to'   => trim($_REQUEST['date_to'] ?? '')
    ];
    $result = getOpenSaleTraysList($krfconn, $projectid, $filters, $limit, $offset);
    outJson([
        'status' => 'success',
        'total'  => $result['total'],
        'items'  => $result['items']
    ]);
}

// -------------------------------------------------------------
// ACTION: EXPORT CSV REPORT
// -------------------------------------------------------------
if ($action === 'export_csv') {
    $view = trim($_REQUEST['view'] ?? 'trays');
    $filters = [
        'status'    => trim($_REQUEST['status'] ?? 'All'),
        'location'  => trim($_REQUEST['location'] ?? ''),
        'productid' => trim($_REQUEST['productid'] ?? ''),
        'search'    => trim($_REQUEST['search'] ?? ''),
        'date_from' => trim($_REQUEST['date_from'] ?? ''),
        'date_to'   => trim($_REQUEST['date_to'] ?? '')
    ];

    $filename = "opensale_stock_" . date('Y-m-d_His') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    $out = fopen('php://output', 'w');

    // Add UTF-8 BOM
    fputs($out, "\xEF\xBB\xBF");

    if ($view === 'grouped') {
        // Product Summary CSV
        fputcsv($out, ['#', 'Product ID', 'Product Name', 'Variety', 'Tray Cavity', 'Rate (₹)', 'Available Trays', 'Available Plants', 'Karjee Plants', 'Sargawa Plants', 'Mansoon Plants', 'Sold Plants', 'Locations Available']);
        $rows = getOpenSaleGroupedSummary($krfconn, $projectid, $filters);
        $sno = 1;
        foreach ($rows as $r) {
            fputcsv($out, [
                $sno++,
                $r['productid'],
                $r['productname'],
                $r['variety'],
                $r['tray_code'] ?: $r['qtypertray'],
                number_format($r['standard_rate'], 2),
                $r['total_trays'],
                $r['total_plants'],
                $r['plants_karjee'],
                $r['plants_sargawa'],
                $r['plants_mansoon'],
                $r['total_sold_qty'],
                $r['locations_list']
            ]);
        }
    } else {
        // Detailed Trays CSV
        fputcsv($out, ['#', 'Barcode', 'Product Name', 'Variety', 'Current Plants', 'Tray Capacity', 'Sold Plants', 'Open Sale Location', 'From Nursery', 'From Polyhouse', 'From Bed', 'Stock Order No', 'PSO No', 'Status', 'Invoice No', 'Moved Date', 'Moved By', 'Remarks']);
        $result = getOpenSaleTraysList($krfconn, $projectid, $filters, 10000, 0);
        $sno = 1;
        foreach ($result['items'] as $it) {
            fputcsv($out, [
                $sno++,
                $it['barcode'],
                $it['productname'],
                $it['variety'],
                $it['actual_plant_count'],
                $it['qtypertray'],
                $it['sold_qty'],
                $it['open_sale_location'] ?: '-',
                $it['from_locationname'] ?: '-',
                $it['from_polyhousename'] ?: '-',
                $it['from_bed_no'] ?: '-',
                $it['stockorder_no'] ?: '-',
                $it['psono'] ?: '-',
                $it['status'],
                $it['invoiceno'] ?: '-',
                $it['created_at_fmt'],
                $it['created_by'] ?: '-',
                $it['remarks'] ?: '-'
            ]);
        }
    }
    fclose($out);
    exit;
}

outJson(['status' => 'error', 'message' => 'Invalid action specified.']);
