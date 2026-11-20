<?php
require('../../connection.php');
require('../../functions/function_master.php');
$action=$_GET['action'];
if($action=="imprestpayto"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$loginid = $_POST['loginid'];
	$logininfo=getlogindetailsfrommaster($conn,$companyid,"loginid",$loginid);
	foreach ($logininfo as $lgkey) {
		$logintype = $lgkey['logintype'];
		$loginname = $lgkey['loginname'];
	}
	
	if(strtolower($logintype)=="admin"){
		$ishortby='allpayto';
		$ishortbyvalue="";
	}else{
		$ishortby='payto';
		$ishortbyvalue=$loginname;		
	}
	$paytolist = getimprestpayto($conn,$companyid,$subcompanyid,$projectid,$ishortby,$ishortbyvalue);
	echo json_encode($paytolist);
}else if($action=="payfromaccountlist"){
	$countryid = $_POST['countryid'];
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$loginid = $_POST['loginid'];
	$accountsids=getauthorizedaccountids($conn,$companyid,$subcompanyid,$projectid,$loginid);
	$acclist = getaccountinformation($conn,$countryid,$companyid,$subcompanyid,$projectid,"accountid",$accountsids);
	echo json_encode($acclist);	
}else if($action == "getpaytobankinfo"){
	$paytoname = $_POST['paytoname'];
	echo getpaytobankdetails($conn,$paytoname);
}
/*else if($action == "egetpaytobankinfo"){
	// $paytonameinfo = explode("|", $_POST['paytoname']);
	$paytoname = $_POST['paytoname'];
	echo getpaytobankdetails($conn,$paytoname);
}else if($action=="getauthorized_accountofproject"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$loginid = $_POST['loginid'];
	$subcompanyinfo=getsubcompanydetails($conn,$companyid,$subcompanyid,"subcompanyid",$subcompanyid);
	$countryid="";
	foreach ($subcompanyinfo as $subckey) {
		$countryid=$subckey['countryid'];
	}
	$authaccountids=getauthorizedaccountids($conn,$companyid,$subcompanyid,$projectid,$loginid);
	$tbllist=getaccountinformation($conn,$countryid,$companyid,$subcompanyid,$projectid,"accountid",$authaccountids);
	echo json_encode($tbllist);
	
}
else if($action=="getpurposelist"){
	$pcategorycode = $_POST['categorycode'];
	$pdshortby='purposecategory';
	$pdshortbyvalue=$pcategorycode;
	$countryid = $_POST['countryid'];
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	echo json_encode(getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,$pdshortby,$pdshortbyvalue));
}*/

//Payto Bank Details
function getpaytobankdetails($conn,$paytoname){
	$bnf_bnkdetails = "select paytoname, bankname, accountno, ifsccode from tbl_payto_master where paytoname='".$paytoname."'";
	$paytobnkdls_result=$conn->query($bnf_bnkdetails);
	if($paytobnkdls_result->num_rows>0)
	{
		$row_bk = $paytobnkdls_result->fetch_assoc();
		echo '<div class="w3-row w3-row-padding">
			<div class="w3-third">
				<label>Payto Accountno <i class="w3-text-red">*</i></label>
				<input type="text" class="w3-input w3-border-blue bnf_bankdetails_input" name="bnkaccountno_eltransfer" id="payto_bnkaccountno" value="'.trim($row_bk['accountno']).'" autocomplete="off" readonly/>
				<label class="w3-text-red"><i id="error_accountno_label"></i></label>
			</div>
			<div class="w3-third">
				<label>Bank Name <i class="w3-text-red">*</i></label>
				<input type="text" class="w3-input w3-border-blue bnf_bankdetails_input" name="bnkname_eltransfer" id="payto_bnkname" value="'.trim($row_bk['bankname']).'" autocomplete="off" readonly/>
				<label class="w3-text-red"><i id="error_bankname_labe"></i></label>
			</div>
			<div class="w3-third">
				<label>Bank IFSC Code <i class="w3-text-red">*</i></label>
				<input type="text" class="w3-input w3-border-blue bnf_bankdetails_input" name="bnkifsc_eltransfer" id="payto_bankifsc" value="'.trim($row_bk['ifsccode']).'" autocomplete="off" readonly/>
				<label class="w3-text-red"><i id="error_ifsc_label"></i></label>
			</div>
		</div>
		<div class="w3-row w3-row-padding" style="display:block;">
			<div class="w3-half">
				<input type="checkbox" class="w3-check" id="upd_bnk_chk"> <label class="w3-text-red">If you want to update then click to conform.</label>
			</div>
			<div class="w3-half">
				<input type="button" class="w3-btn w3-animate-zoom w3-deep-orange w3-right update_bankdatails" name="update_bankdatails" id="update_bankdatails" value="UPDATE PAYTO BANK DETAILS" style="display: none;" />
			</div>
		</div>
		<div class="w3-row w3-center">
			<label id="update_bankdatails_msg"></label>
		</div>';
	}else{
			echo '<p class="w3-centre w3-">Bank Details not Available</p>';
	}
}
?>