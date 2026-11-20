<?php
// ⚡ Start output buffering FIRST — prevents "headers already sent" / TCPDF conflict
// caused by any whitespace or output from connection.php / login_functions.php
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);

require('../../connection.php');
require('../../loginCell/functions/login_functions.php');
include("../functions/client_functions.php");
include("../functions/orders_functions.php");
include("../functions/product_functions.php");
require_once('../functions/pdf_generation_functions.php');

$companyid = intval($_GET['companyid'] ?? 0);
$subcompanyid = intval($_GET['subcompanyid'] ?? 0);
$projectid = intval($_GET['projectid'] ?? 0);
$psono = trim($_GET['psono'] ?? '');
$sono = trim($_GET['sono'] ?? '');
$mode = $_GET['mode'] ?? 'I';

if (empty($psono) && !empty($sono)) {
    // If only sono was provided, look up psono
    $stmtS = $krfconn->prepare("SELECT psono, companyid, subcompanyid, projectid FROM krf_sales_order WHERE sono = ? LIMIT 1");
    if ($stmtS) {
        $stmtS->bind_param("s", $sono);
        $stmtS->execute();
        $resS = $stmtS->get_result();
        if ($rowS = $resS->fetch_assoc()) {
            $psono = trim($rowS['psono'] ?? '');
            if (empty($companyid)) $companyid = intval($rowS['companyid']);
            if (empty($subcompanyid)) $subcompanyid = intval($rowS['subcompanyid']);
            if (empty($projectid)) $projectid = intval($rowS['projectid']);
        }
        $stmtS->close();
    }
}

if (empty($psono) && empty($sono)) {
    ob_end_clean();
    http_response_code(400);
    die('Error: Order Number (psono or sono) is required.');
}

// Fetch provisional order status and company IDs if not provided
$psoStatus = '';
if (!empty($psono)) {
    $stmtO = $krfconn->prepare("SELECT companyid, subcompanyid, projectid, status FROM krf_provisional_sales_order WHERE psono = ? LIMIT 1");
    if ($stmtO) {
        $stmtO->bind_param("s", $psono);
        $stmtO->execute();
        $resO = $stmtO->get_result();
        if ($rowO = $resO->fetch_assoc()) {
            if (empty($companyid)) $companyid = intval($rowO['companyid']);
            if (empty($subcompanyid)) $subcompanyid = intval($rowO['subcompanyid']);
            if (empty($projectid)) $projectid = intval($rowO['projectid']);
            $psoStatus = trim($rowO['status'] ?? '');
        }
        $stmtO->close();
    }
}

// Check if there is an existing confirmed sales order in krf_sales_order
if (empty($sono) && !empty($psono)) {
    $stmtSo = $krfconn->prepare("SELECT sono, companyid, subcompanyid, projectid FROM krf_sales_order WHERE psono = ? ORDER BY id DESC LIMIT 1");
    if ($stmtSo) {
        $stmtSo->bind_param("s", $psono);
        $stmtSo->execute();
        $resSo = $stmtSo->get_result();
        if ($rowSo = $resSo->fetch_assoc()) {
            $sono = trim($rowSo['sono'] ?? '');
            if (empty($companyid)) $companyid = intval($rowSo['companyid']);
            if (empty($subcompanyid)) $subcompanyid = intval($rowSo['subcompanyid']);
            if (empty($projectid)) $projectid = intval($rowSo['projectid']);
        }
        $stmtSo->close();
    }
}

// Discard any output buffered so far (whitespace from includes etc.)
ob_end_clean();

// RULE:
// After confirm order -> show ONLY final sales order PDF view, NOT provisional order.
// While order NOT confirm -> show provisional order PDF, NOT final sale order PDF.
$isConfirmed = ($psoStatus === 'Confirmed' || !empty($sono));

if ($isConfirmed && !empty($sono)) {
    // Show Final Sales Order PDF
    createSalesOrderPDF($conn, $authconn, $krfconn, $companyid, $subcompanyid, $projectid, $sono, $mode);
} else {
    // Show Provisional Order PDF
    createProvisionalOrderPDF($conn, $authconn, $krfconn, $companyid, $subcompanyid, $projectid, $psono, $mode);
}
?>