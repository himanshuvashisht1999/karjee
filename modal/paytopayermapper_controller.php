<?php
date_default_timezone_set('Asia/Kolkata');
require_once "../connection.php";
require_once "../functions/function_master.php";
require_once "../functions/function_savetodb.php";
require('../loginCell/functions/login_functions.php');
require_once "../FuzzyMatcher.php";
$action = $_GET["action"];
if($action==="withdrawn"){
	$companyid=$_POST["companyid"];
	$subcompanyid=$_POST["subcompanyid"];
	$projectid=$_POST["projectid"];
	$data = fetchTableData("reconcile_reconstructed_tbl", ["distinct paid"], "companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and ifnull(paidid,'')='' and transactiontype='".$action."'");
	echo json_encode($data);
}
else if($action==="deposit"){
	$companyid=$_POST["companyid"];
	$subcompanyid=$_POST["subcompanyid"];
	$projectid=$_POST["projectid"];
	$data = fetchTableData("reconcile_reconstructed_tbl", ["distinct paid"], "companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and ifnull(paidid,'')='' and transactiontype='".$action."'");
	echo json_encode($data);
}
else if($action==="savetomaster"){
	$transactiontype=$_POST["transactiontype"];
	$companyid=$_POST["companyid"];
	$subcompanyid=$_POST["subcompanyid"];
	$projectid=$_POST["projectid"];
    $loginname=$_POST["loginname"];
    $existingpayname = $_POST["existingpayname"];
	$payname=$_POST["payname"];
    $resp = null;
    $retarray = [];
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn->begin_transaction();
    try{
    	if($transactiontype==="withdrawn"){
    		$resp = savetodbnewpayto_details($conn,$payname,"","","","","","",$companyid,$subcompanyid,$projectid,"","",$loginname);
    	}
    	else if($transactiontype==="deposit"){
    		$resp = savetodbnewpayerto_details($conn,$payname,"","","","","","",$companyid,$subcompanyid,$projectid,"","",$loginname);
    	}
        if($resp[0]["status"]==="Success"){
            $paidid = $resp[0]["paytoid"];
            $upqry = "UPDATE reconcile_reconstructed_tbl set paid = '".$payname."', paidid='".$paidid."' where paid='".$existingpayname."' and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'";
            $conn->query($upqry);
            $conn->commit();
            $retarray[]=array('status'=>'Success', 'message'=>'');
        }
        else{
            $conn->rollback();
            $retarray[]=array('status'=>'Error', 'message'=>$resp[0]["status"]);
        }
    }
    catch (mysqli_sql_exception $e) {
        $conn->rollback();
        $retarray[]=array('status'=>'Error', 'message'=>$e->getMessage());
    }
    echo json_encode($retarray);
}
else if($action==="applyfuzzymapper"){
    $transactiontype = $_POST["transactiontype"];
    $companyid = $_POST["companyid"];
    $subcompanyid = $_POST["subcompanyid"];
    $projectid = $_POST["projectid"];
    $unverifiedpaynames = json_decode($_POST["unverifiedpaynames"],true);

    $sql=null;
    if($transactiontype==="withdrawn"){
        $sql = "SELECT paytoid as id, paytoname as name FROM tbl_payto_master";
    }
    else if($transactiontype==="deposit"){
        $sql = "SELECT payerid as id, payername as name FROM tbl_payer_master";
    }
    $result = $conn->query($sql);
    $paymaster = [];
    $map = [];
    while ($row = $result->fetch_assoc()) {
        $map[$row['name']] = $row['id'];
        $paymaster[] = $row['name'];
    }
    foreach($unverifiedpaynames as $unverifiedpayname){
        $matcher = new FuzzyMatcher();
        $best = $matcher->findBestMatch($unverifiedpayname, $paymaster);
        if ($best['score'] >= 60) {
            $paidid = $map[$best['match']];
            $upqry = "UPDATE reconcile_reconstructed_tbl set paid = '".$best['match']."', paidid='".$paidid."' where paid='".$unverifiedpayname."' and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'";
            $conn->query($upqry);
        }
    }
    echo "done"; 
}

function fetchTableData($tableName, $fields = ['*'], $condition = "", $orderby = "") {
	global $conn;
    $tableName = $conn->real_escape_string($tableName);
    if ($fields === ['*']) {
        $fieldsString = '*';
    } else {
         $fieldsString = implode(", ",  $fields);
    }

    $query = "SELECT $fieldsString FROM $tableName";

    if (!empty($condition)) {
        $query .= " WHERE $condition";
    }
    
    if (!empty($orderby)) {
        $query .= " order by $orderby";
    }
    $result = $conn->query($query);
    
    if (!$result) {
        return ["error" => $conn->error];
    }
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $result->free();
    return $data;
}

function fetchCustomData($query) {
    global $conn;
    $result = $conn->query($query);
    
    if (!$result) {
        return ["error" => $conn->error];
    }
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $result->free();
    //print_r($data);
    return $data;
}