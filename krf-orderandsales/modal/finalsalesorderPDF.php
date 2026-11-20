<?php
// ⚡ Capture any accidental output from includes before TCPDF sends headers
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);

require('../../connection.php');
require('../../loginCell/functions/login_functions.php');
include("../functions/client_functions.php");
include("../functions/orders_functions.php");
include("../functions/product_functions.php");
include("../functions/pdf_generation_functions.php");

$companyid = intval($_GET['companyid'] ?? 0);
$subcompanyid = intval($_GET['subcompanyid'] ?? 0);
$projectid = intval($_GET['projectid'] ?? 0);
$sono = trim($_GET['sono'] ?? '');
$psono = trim($_GET['psono'] ?? '');
$mode = $_GET['mode'] ?? 'I';

// If sono is empty, resolve by psono from krf_sales_order
if (empty($sono) && !empty($psono)) {
    $stmtS = $krfconn->prepare("SELECT sono, companyid, subcompanyid, projectid FROM krf_sales_order WHERE psono = ? ORDER BY id DESC LIMIT 1");
    if ($stmtS) {
        $stmtS->bind_param("s", $psono);
        $stmtS->execute();
        $resS = $stmtS->get_result();
        if ($rowS = $resS->fetch_assoc()) {
            $sono = trim($rowS['sono'] ?? '');
            if (empty($companyid)) $companyid = intval($rowS['companyid']);
            if (empty($subcompanyid)) $subcompanyid = intval($rowS['subcompanyid']);
            if (empty($projectid)) $projectid = intval($rowS['projectid']);
        }
        $stmtS->close();
    }
}

// Auto-resolve companyid, subcompanyid, projectid from krf_sales_order if not provided
if (!empty($sono) && (empty($companyid) || empty($projectid))) {
    $stmtO = $krfconn->prepare("SELECT companyid, subcompanyid, projectid, psono FROM krf_sales_order WHERE sono = ? LIMIT 1");
    if ($stmtO) {
        $stmtO->bind_param("s", $sono);
        $stmtO->execute();
        $resO = $stmtO->get_result();
        if ($rowO = $resO->fetch_assoc()) {
            $companyid = intval($rowO['companyid']);
            $subcompanyid = intval($rowO['subcompanyid']);
            $projectid = intval($rowO['projectid']);
            if (empty($psono)) $psono = trim($rowO['psono'] ?? '');
        }
        $stmtO->close();
    }
}

// Check status in krf_provisional_sales_order if psono is known
$psoStatus = '';
if (!empty($psono)) {
    $stmtP = $krfconn->prepare("SELECT companyid, subcompanyid, projectid, status FROM krf_provisional_sales_order WHERE psono = ? LIMIT 1");
    if ($stmtP) {
        $stmtP->bind_param("s", $psono);
        $stmtP->execute();
        $resP = $stmtP->get_result();
        if ($rowP = $resP->fetch_assoc()) {
            if (empty($companyid)) $companyid = intval($rowP['companyid']);
            if (empty($subcompanyid)) $subcompanyid = intval($rowP['subcompanyid']);
            if (empty($projectid)) $projectid = intval($rowP['projectid']);
            $psoStatus = trim($rowP['status'] ?? '');
        }
        $stmtP->close();
    }
}

// If sono is STILL empty or order is not confirmed, fall back to provisional order PDF:
if (empty($sono) && !empty($psono)) {
    ob_end_clean();
    createProvisionalOrderPDF($conn, $authconn, $krfconn, $companyid, $subcompanyid, $projectid, $psono, $mode);
    exit;
}

if (empty($sono) && empty($psono)) {
    ob_end_clean();
    http_response_code(400);
    die('Error: Sales Order Number (sono) or Provisional Order Number (psono) is required.');
}

// Discard any buffered output (whitespace from includes etc.)
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