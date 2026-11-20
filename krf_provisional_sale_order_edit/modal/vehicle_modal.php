<?php
if(session_status()===PHP_SESSION_NONE)session_start();
error_reporting(0);
ini_set('display_errors',0);
require_once __DIR__.'/../../connection.php';
require_once __DIR__.'/../../loginCell/functions/login_functions.php';
require_once __DIR__.'/../functions/vehicle_functions.php';
if(ob_get_length())ob_clean();
header('Content-Type: application/json');
$action=$_GET['action']??'';
if ($action == "calculateFreight") {
    $projectid = $_POST['projectid'] ?? 0;
    $km = $_POST['km'] ?? 0;
    $totalQty = $_POST['total_qty'] ?? $_POST['qty'] ?? 0;
    echo json_encode(getFreightSuggestion($krfconn, $projectid, $km, $totalQty));
}


