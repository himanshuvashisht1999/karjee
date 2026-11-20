<?php
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
?>
<div class="row">
	<div class="col-md-12">
		<div class="form-group">
			<label>What is the Payto name?</label>
			<input type="text" class="form-control" id="new_paytoname" value="" onblur='checkexist_paytonamefrommaster("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","new_paytoname","alreadyexistpaytodiv");' />
		</div>
	</div>
</div>
<div class="row">
	<div class="col-md-12">
		<div id="alreadyexistpaytodiv"></div>
	</div>
</div>
<div class="row" id="payto_bankdetailsdiv" style="display: none;">
	<div class="col-md-12">
		<div class="form-group">
			<label>What is bank name of payto</label>
			<input type="text" class="form-control" id="new_paytobankname" value="" autocomplete="off" placeholder="Enter payto bank name" />
		</div>
	</div>

	<div class="col-md-12">
		<div class="form-group">
			<label>What is the account number of payto</label>
			<input type="text" class="form-control" id="new_paytobankaccountno"  oninput="validateIndianBankAccountNumber('new_paytobankaccountno');" value="" autocomplete="off" placeholder="Enter payto account number" />
		</div>
	</div>
	<div class="col-md-12">
		<div class="form-group ">
			<label>Bank IFSC code  of payto</label>
			<input type="text" class="form-control" id="new_paytobankifsccode"  oninput="validateIFSCCode('new_paytobankifsccode');" value="" autocomplete="off" placeholder="Enter payto IFSC code" />
		</div>
	</div>
</div>
<div class="row">
	<div class="col-md-12">
		<div class="form-group ">
			<label> <input type="checkbox" id="wanttofill_paytobank"/> Do you want to have payto bank details?</label>
		</div>
	</div>
</div>
<div class="row">
	<div class="col-md-12">
		<div id="projectnamediv"></div>
	</div>
</div>
<div class="row">
	<div class="col-md-12 text-center">
		<button class='btn btn-success' id="newpaytosavebtn" onclick='savetodb_newpatytodetails("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","new_paytoname","new_paytobankname","new_paytobankaccountno","new_paytobankifsccode","newpaytosavebtn","hidden_loginname");'>Save Payto Details</button>

	</div>
</div>
<script type="text/javascript">
$("#wanttofill_paytobank").on("click", function(){
	if($(this).is(":checked")){
		$('#payto_bankdetailsdiv').show();
	}else{
		$('#payto_bankdetailsdiv').hide();
	}
});
</script>