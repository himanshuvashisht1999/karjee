<?php
// ⚡ Start output buffering FIRST to prevent any whitespace or header conflicts before TCPDF
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require('../../connection.php');
if (isset($krfconn) && $krfconn) {
    @$krfconn->set_charset("utf8mb4");
}
require('../../loginCell/functions/login_functions.php');
include("../functions/client_functions.php");
include("../functions/orders_functions.php");
include("../functions/product_functions.php");
require_once('../functions/pdf_generation_functions.php');

$token = trim($_GET['token'] ?? '');
$params = [];

if (!empty($token) && isset($_SESSION['dinv_preview_data'][$token]) && is_array($_SESSION['dinv_preview_data'][$token])) {
    $params = $_SESSION['dinv_preview_data'][$token];
}

// Fallback to GET parameters if not found in session
if (empty($params)) {
    $params = $_GET;
}

$mode = $_GET['mode'] ?? ($params['mode'] ?? 'I');

// Discard buffered output
ob_end_clean();

createDispatchInvoicePDF($conn, $authconn, $krfconn, $params, $mode);
?>
