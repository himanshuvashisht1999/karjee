<?php
include("../connection.php");
$request_no=$_REQUEST['reqno'];
$purpose_type=strtolower($_REQUEST['purposetype']);
$actionfor=strtolower($_REQUEST['actionfor']);

if((strtolower($purpose_type)=='expenditure' || strtolower($purpose_type)=='payment') && $actionfor=='partial_adjustment'){
	$updatequery="update tbl_paymentrequest set purpose_type='Partial Adjustment' where request_no='".$request_no."'";
	$updrslt=$conn->query($updatequery);
	if($updrslt){
		$updatestatus="updated";
	}else{
		$updatestatus="not update";
	}
}else if((strtolower($purpose_type)=='expenditure' || strtolower($purpose_type)=='payment') && $actionfor=='imprest_adjustment'){
	$updatequery="update tbl_paymentrequest set purpose_type='Imprest Adjustment' where request_no='".$request_no."'";
	$updrslt=$conn->query($updatequery);
	if($updrslt){
		$updatestatus="updated";
	}else{
		$updatestatus="not update";
	}
} else if((strtolower($purpose_type)=='expenditure' || strtolower($purpose_type)=='payment') && $actionfor=='secritydeposit_adjustment'){
	$updatequery="update tbl_paymentrequest set purpose_type='Security Deposit Adjustment' where request_no='".$request_no."'";
	$updrslt=$conn->query($updatequery);
	if($updrslt){
		$updatestatus="updated";
	}else{
		$updatestatus="not update";
	}
} else{
	$updatequery="update tbl_paymentrequest set purpose_type='payment' where request_no='".$request_no."'";
	$updrslt=$conn->query($updatequery);
	if($updrslt){
		$updatestatus="updated";
	}else{
		$updatestatus="not update";
	}
}
?>