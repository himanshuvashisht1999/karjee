<?php
// ⚡ Capture any accidental output from includes before TCPDF sends headers
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);

require(__DIR__ . '/../../connection.php');
require(__DIR__ . '/../../loginCell/functions/login_functions.php');
include(__DIR__ . '/../functions/client_functions.php');
include(__DIR__ . '/../functions/orders_functions.php');
include(__DIR__ . '/../functions/product_functions.php');
include(__DIR__ . '/../functions/pdf_generation_functions.php');

$companyid = intval($_GET['companyid'] ?? 0);
$subcompanyid = intval($_GET['subcompanyid'] ?? 0);
$projectid = intval($_GET['projectid'] ?? 0);
$sono = trim($_GET['sono'] ?? '');

if (empty($sono)) {
    ob_end_clean();
    http_response_code(400);
    die('Error: Sales Order Number (sono) is required.');
}

// Auto-resolve companyid, subcompanyid, projectid from krf_sales_order if not provided
if (empty($companyid) || empty($projectid)) {
    $stmtO = $krfconn->prepare("SELECT companyid, subcompanyid, projectid FROM krf_sales_order WHERE sono = ? LIMIT 1");
    if ($stmtO) {
        $stmtO->bind_param("s", $sono);
        $stmtO->execute();
        $resO = $stmtO->get_result();
        if ($rowO = $resO->fetch_assoc()) {
            $companyid = intval($rowO['companyid']);
            $subcompanyid = intval($rowO['subcompanyid']);
            $projectid = intval($rowO['projectid']);
        }
        $stmtO->close();
    }
}

$mode = $_GET['mode'] ?? 'I';

// Discard any buffered output (whitespace from includes etc.)
ob_end_clean();

// Now TCPDF can send headers freely
createSalesOrderPDF($conn, $authconn, $krfconn, $companyid, $subcompanyid, $projectid, $sono, $mode);
?>