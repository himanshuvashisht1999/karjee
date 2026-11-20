<?php
require('../../connection.php');
require('../../functions/function_master.php');

$action= $_GET['action'];
if($action=="getpurposelist"){
	$countryid = $_POST['countryid'];
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];

	$pcategorycode = $_POST['categorycode'];
	$pdshortby='purposecategory';
	$pdshortbyvalue=$pcategorycode;
	echo json_encode(getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,$pdshortby,$pdshortbyvalue));
}else if($action=="getallpurposelist"){
	$countryid = $_POST['countryid'];
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];

	$pdshortby='all';
	$pdshortbyvalue="all";
	echo json_encode(getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,$pdshortby,$pdshortbyvalue));	

}else if($action=="getrefundableproject"){
	$countryid = $_POST['countryid'];
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$shortby='all';
	$shortbyvalue="all";
	echo json_encode(projectdetails($conn,$countryid,$companyid,$subcompanyid,$shortby,$shortbyvalue));
}else if($action == "savepaytobnfinfo"){
	$newname = $_POST['newpaytoname'];
	if($_POST['newaccountno']==""){
		$newaccountno = "";
		$newifsccode = "";
		$newbankname = "";

	}else{
		$newaccountno = $_POST['newaccountno'];
		$newifsccode = $_POST['newifsccode'];
		$newbankname = $_POST['newbankname'];
	}
	echo json_encode(addnewpaytobnfname($conn,$newname,$newaccountno,$newbankname,$newifsccode));
}else if($action == "getdocumentforview"){
	$senderemail = $_POST['senderemail'];
	echo json_encode(getdocumentforview($conn,$senderemail));
}else if($action == "getnextdocument"){
	$senderemail = $_POST['senderemail'];
	$sno = $_POST['docsno'];
	echo json_encode(getnextdocument($conn,$senderemail,$sno));
}else if($action == "addnewitemforstock"){
	$newitemname = $_POST['newitemname'];
	$purposecode = $_POST['purposecode'];
	echo json_encode(addnewitemforstock($conn,$newitemname,$purposecode));

}else if($action == "getbeneficiaryinfo"){
	echo json_encode(getbeneficiaryinfo($conn));

}else if($action == "getpayto_bankdetails"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$paytoname = $_POST['paytoname'];
	echo json_encode(getpaytoinfo($conn,$companyid,$subcompanyid,"paytoname",$paytoname));
}