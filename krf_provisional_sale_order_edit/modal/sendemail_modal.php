<?php
error_reporting(E_ALL);
ini_set('display_errors',0);
require_once __DIR__.'/../../connection.php';
require_once __DIR__.'/../../loginCell/functions/login_functions.php';
require_once __DIR__.'/../functions/orders_functions.php';
require_once __DIR__.'/../functions/product_functions.php';
$action=$_GET['action']??'';
require_once __DIR__.'/../functions/client_functions.php';
require_once __DIR__.'/../functions/emailsend_functions.php';
require_once __DIR__.'/../functions/pdf_generation_functions.php';
if ($action == "SendEmailProvisionalOrderDetails") {
  $companyid = $_POST['companyid'] ?? 0;
  $subcompanyid = $_POST['subcompanyid'] ?? 0;
  $projectid = $_POST['projectid'] ?? 0;
  $psono = $_POST['psono'] ?? '';
  $clientid = $_POST['clientid'] ?? 0;

  // sendemail_WhileProvistionalOrderCreate handles PDF generation internally
  $emislstatus = sendemail_WhileProvistionalOrderCreate($authconn, $conn, $krfconn, $companyid, $subcompanyid, $projectid, $clientid, $psono);
  echo json_encode(['status' => ($emislstatus === 'sent' ? 'success' : 'error'), 'message' => $emislstatus]);


}


