<?php
if(isset($_GET['dz'])){
  require('../connection.php');
  require('../functions/function_master.php');
  require('../loginCell/functions/login_functions.php');
  $key=$_GET['k'];
	 $key_decoded = base64url_decode($key);
	 //print_r($key_decoded);
	$key_parts = explode("|",$key_decoded);
	 //print_r($key_parts);
	$checkkey = base64url_decode($key_parts[count($key_parts)-1]);

	$actkey = "";
	for($i=0;$i<count($key_parts)-1 ;$i++){
		$actkey = $actkey?$actkey."|".$key_parts[$i]:$key_parts[$i];
	}
	// echo "<br>".$actkey."<br>";
	// echo $checkkey;
	if($actkey!=$checkkey){
		echo"<div class=\"col-md-12 text-center black\"><h3>OOPS!!Something went wrong. Please Try after sometime.</h3></div>";
		die;
	}
	// print_r($key_parts);
	$loginname ="";
	$loginid = $key_parts[0];
	$loginroleid = $key_parts[1];
	$companyid = $key_parts[2];
	$subcompanyid = $key_parts[3];
	$projectid = $key_parts[4];
	$logintype = $key_parts[5];
	$newkey = "x|x|".$key_parts[2]."|".$key_parts[3]."|".$key_parts[4]."|x";
	$encodednewkey = base64url_encode($newkey);
	$keycompany=base64url_encode($newkey."|".$encodednewkey);

	$frmmail_emailsno=$_GET['emailid'];
	$frmmail_attsno=$_GET['attid'];
	$frmmail_attpath=$_GET['attpath'];
	$protocol=$_SERVER['PROTOCOL'] = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';
	$url = basename( $_SERVER["PHP_SELF"]);
	$rooturl = $protocol.'://'.$_SERVER['HTTP_HOST'];
	$baseurl= $rooturl.str_replace($url, "", $_SERVER["PHP_SELF"]);
	if($_SERVER['HTTP_HOST']=="localhost"){
		$prefix=$rooturl."/krf_amanaccounting/";
	}else{
		$prefix=$rooturl."/";
	}
	$rooturl =$prefix;
}else{
	  require('../header.php');
	  $frmmail_emailsno="";
	  $frmmail_attsno="";
	  $frmmail_attpath="";
}

$insid = $_GET['insid'];
$insamount = $_GET['insamount'];
// $companyid;
// $subcompanyid;
// $projectid;
$instinfo=voiceinstructiondetails($authconn,$conn,$companyid,$subcompanyid,$projectid,"instructionid",$insid);
$requestno ="";
$payto ="";
$paytoid ="";
$requestamount ="";
$purpose ="";
$purposecode ="";
$requestedby ="";
$requestdate ="";
$requesttype ="";
$from_bankname ="";
$from_accountid ="";
$from_accountno ="";
$transactiondate ="";
$transactionno ="";
if(count($instinfo)>0){
	foreach ($instinfo as $instinfokey) {
		$requestno = $instinfokey['requestno'];
		$payto = $instinfokey['payto'];
		$paytoid = $instinfokey['paytoid'];
		$requestamount = $instinfokey['requestamount'];
		$purpose = $instinfokey['purpose'];
		$purposecode = $instinfokey['purposecode'];
		$requestedby = $instinfokey['requestedby'];
		$requestdate = $instinfokey['requestdate'];
		$requesttype = $instinfokey['requesttype'];
		$from_bankname = $instinfokey['from_bankname'];
		$from_accountid = $instinfokey['from_accountid'];
		$from_accountno = $instinfokey['from_accountno'];
		$transactiondate = $instinfokey['transactiondate'];
		$transactionno = $instinfokey['transactionno'];
	}
}
?>
	<div class="row mt-2 subprojectdiv" id="subprojectdiv">
		<div class="col-md-12">
			<label>Choose the Sub-project</label>
			<select class="form-control" id="invdes_subproject"></select>
		</div>
	</div>

	<div class="row mt-2">
		<div class="col-md-12">
			<label>Amount<sup><i class="fa fa-star" style="font-size: 6px; color:red"></i></sup></label>
			<input type="text" id="invdes_amount" onkeypress="return isNumberKey(event)" class="form-control" value="<?= $requestamount; ?>" />
		</div>
	</div>
	<div class="row mt-2 <?= $requesttype=="Post-Payment"?"": "d-none" ?>">
		<div class="col-md-12">
			<label>From Account<sup><i class="fa fa-star" style="font-size: 6px; color:red"></i></sup></label>
			<select id="invdes_fromaccount" style="width:100%;" class="form-control select2"></select>
			<i class="text-primary"><?= $from_accountid!==""?"":"Note: provided account/bank name :".$from_bankname; ?></i>
		</div>
	</div>
	<div class="row mt-2 <?= $requesttype=="Post-Payment"?"": "d-none" ?>">
		<div class="col-md-12">
			<label>Mode of Transfer</label>
			<select id="invdes_modeofpayment" style="width:100%;" class="form-control select2"></select>
		</div>
	</div>
	<div class="row mt-2 <?= $requesttype=="Post-Payment"?"": "d-none" ?>">
		<div class="col-md-12">
			<label><b id="chqnolbl">Transaction/Cheque number?</b></label>
			<input type="text" class="form-control" id="invdes_transactionno" value="<?php echo $transactionno; ?>" />
		</div>
	</div>

	<div class="row mt-2 <?= $requesttype=="Post-Payment"?"": "d-none" ?>">
		<div class="col-md-12">
			<label><b id="chqnolbl">Transaction/Cheque Date?</b></label>
			<input type="date" class="form-control" id="invdes_transactiondate" value="<?php echo $transactiondate; ?>" />
		</div>
	</div>
	<div class="row mt-2">
		<div class="col-md-12">
			<label>Payto Name <sup><i class="fa fa-star" style="font-size: 6px; color:red"></i></sup></label>
			<i class="fa fa-plus-circle" id="opennewpaytomodal_forexp" style="cursor: pointer; height: 28px;"></i>
			<span class="float-right m-2 refeshpaytolist" onclick='request_paytofrommaster_withpaytoid("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","invdes_paytoname");' style="cursor: pointer; color: green;"><i class="fa fa-sync"></i> Refresh</span>
			<select id="invdes_paytoname" class="form-control select2" style="width:100%;"></select>
			<i class="text-primary"><?= $paytoid!==""?"":"Note: Provided payto name :".$payto; ?></i>
		</div>
	</div>
	
	<div class="row mt-2">
	  <div class="col-md-12">
	    <label>Purpose name</label>
		<select class="select2 form-control" style="width: 100%;" id="invdes_purposecode"></select>
		<i class="text-primary"><?= $purposecode!==""?"":"Note: provided purpose name :".$purpose; ?></i>
	  </div>
	</div>
	<div class="row mt-2">
		<div class="col-md-12">
			<label>Remark</label>
			<textarea id="invdes_remark" rows="4" style="resize:none; width: 100%; border: 1px solid black;"></textarea>
		</div>
	</div>
	<div class="row mt-4">
		<div class="col-md-12 text-center">
			<button id="savetobookfromvoiceinstructionbtn" data-voiceinstid='<?= $insid ?>' data-requesttype='<?= $requesttype; ?>' class="btn btn-success" onclick='DigitizeVoiceInstructionintoBookEntry("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","invdes_subproject","invdes_fromaccount","invdes_paytoname", "invdes_amount", "invdes_modeofpayment", "invdes_transactionno", "invdes_transactiondate", "invdes_purposecode","invdes_remark","savetobookfromvoiceinstructionbtn");' value="Digitize Voice Instruction into Book Entry">Digitize Voice Instruction into Book Entry</button>
		</div>
	</div>
<?php
if(isset($_GET['dz'])){
}else{
	include('../footer.php');
}
?>
<script type="text/javascript">
	var alreadyexistpurpose="<?php echo $purposecode; ?>";
	var subprjid="";
	subprojectdetails_callback("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid",function(res){
		if(res.length>0){
			if(res.length>1){
				$(".subprojectdiv").show();
				$("#invdes_subproject").append("<option value='' disabled selected>Choose sub-project name</option>");
			}else{
				$(".subprojectdiv").hide();
			}
			for (var i = 0; i < res.length; i++) {
				$("#invdes_subproject").append("<option value='"+res[i].subprojectid+"'>"+res[i].subprojectname+"</option>");
			}
			if(subprjid && res.length>1){
				$("#invdes_subproject").val(subprjid).trigger("change");
			}
		}
	});

	request_paytofrommaster_withpaytoid("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","invdes_paytoname","<?php echo $paytoid; ?>");

	getallpurposewithprojectid_withstatus('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid',function(res){
		// console.log(res);
		let ppdrp = "";
		if (res.length > 0) {
			if (res.length > 1) {
				ppdrp += "<option value='' selected disabled>Chose purpose</option>";
			}
		    for (let i = 0; i < res.length; i++) {
		        ppdrp += "<option value='" + res[i].purposecode + "'>" + res[i].purpose + "</option>";
		    }
		    if (ppdrp) {
		        $("#invdes_purposecode").append(ppdrp);
		        $("#invdes_purposecode").val(alreadyexistpurpose).trigger("change");
		    }
		}
	});

	getaccountdetailsofproject_frmaster("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","hidden_loginid","invdes_fromaccount");
	modeoftransferfrommaster_todropdown("hidden_baseurl","","invdes_modeofpayment");
	// request_invoicetype("hidden_baseurl","","invdes_invoicetype");
	
	$(document).on("click", "#opennewpaytomodal_forexp", function(){	
		 $("#newpaytodivid").load("<?php echo $rooturl; ?>expenditure_expencessheet/newpayto_authorization.php", function(){
			$("#newpaytodetails_modal").modal("show");
			$("#newpaytodetails_modal").css("z-index","9999");
	    });
	});

	$(document).on("click", "input[name='doyoudocumentradio']", function()
		{
			if($(this).val()=='yes'){
				$("#dodocdiv").show();			
			}else{
				$("#dodocdiv").hide();
			}
		});
	$('.select2').select2();
	$('.select2-selection').css('border-radius','0px');
	$('.select2-container').children().css('border-radius','0px');
	$('.select2-selection').css('height','42px');
</script>