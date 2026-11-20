<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
require('../../connection.php');
require('../../loginCell/functions/login_functions.php');
include("../functions/client_functions.php");
include("../functions/product_functions.php");
include("../functions/orders_functions.php");
include("../functions/emailsend_functions.php");
include("../functions/pdf_generation_functions.php");

/**
 * Generates a Provisional Order PDF and returns the absolute file path.
 * Saves to: <project_root>/attachementfile/<cmpid>/<subcmpid>/<projid>/provisionalorder_bills/<psono>.pdf
 */
if (!function_exists('generateProvisionalOrderPDFAndReturnPath')) {
  function generateProvisionalOrderPDFAndReturnPath($authconn, $conn, $krfconn, $companyid, $subcompanyid, $projectid, $clientid, $psono)
  {
    // Project root = two levels up from /krf-orderandsales/modal/
    $projectRoot = dirname(__DIR__, 2); // = /krf_amanaccounting
    $tempDir = $projectRoot . '/attachementfile/' . $companyid . '/' . $subcompanyid . '/' . $projectid . '/provisionalorder_bills/';

    if (!file_exists($tempDir)) {
      mkdir($tempDir, 0777, true);
    }
    $pdfPath = $tempDir . $psono . '.pdf';

    $pdfResult = createProvisionalOrderPDF($conn, $authconn, $krfconn, $companyid, $subcompanyid, $projectid, $psono, 'F', $pdfPath);

    if ($pdfResult && file_exists($pdfPath)) {
      return $pdfPath;
    }

    error_log("[PDF ERROR] generateProvisionalOrderPDFAndReturnPath failed for psono=$psono | path=$pdfPath | result=" . var_export($pdfResult, true));
    return false;
  }
}

$action = $_GET['action'] ?? '';

if ($action == "SendEmailProvisionalOrderDetails") {
  $companyid = $_POST['companyid'] ?? 0;
  $subcompanyid = $_POST['subcompanyid'] ?? 0;
  $projectid = $_POST['projectid'] ?? 0;
  $psono = $_POST['psono'] ?? '';
  $clientid = $_POST['clientid'] ?? 0;

  // sendemail_WhileProvistionalOrderCreate handles PDF generation internally
  $emislstatus = sendemail_WhileProvistionalOrderCreate($authconn, $conn, $krfconn, $companyid, $subcompanyid, $projectid, $clientid, $psono);
  echo json_encode(['status' => ($emislstatus === 'sent' ? 'success' : 'error'), 'message' => $emislstatus]);


} else if ($action == "SendEmailSalesOrderConfirm") {
  $companyid = $_POST['companyid'] ?? 0;
  $subcompanyid = $_POST['subcompanyid'] ?? 0;
  $projectid = $_POST['projectid'] ?? 0;
  $psono = $_POST['psono'] ?? 0;
  $sono = $_POST['sono'] ?? 0;
  $clientid = $_POST['clientid'] ?? 0;
  $receiptpath = $_POST['receiptpath'] ?? '';
  $emislstatus = sendemail_WhileSalesOrderConfirm($authconn, $conn, $krfconn, $companyid, $subcompanyid, $projectid, $clientid, $psono, $sono, $receiptpath);
  echo json_encode($emislstatus);
}
?>