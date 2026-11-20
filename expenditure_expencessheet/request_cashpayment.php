<?php 
$ppcatid = isset($_GET['ppcatid'])?$_GET['ppcatid']:'';
if(!$ppcatid){
  die('Purpose category not defined');
}
$dz="";
if(isset($_GET['dz'])){
	$dz=$_GET['dz'];
}
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
if(isset($_GET['subaction'])){
	$subaction = $_GET['subaction'];
}
$subprojectid="";
if(isset($_GET['subprojectid'])){
	$subprojectid=$_GET['subprojectid'];
}
?>
<!-- <div class="row mt-4 mb-4">
	<div class="col-md-12 text-center">
		<h4>INVESTMENT REQUEST</h4>
	</div>
</div> -->
<div class="row mt-2">
  <div class="col-md-12">
    <!-- <label>Loan category</label> -->
    <!-- <input type="radio" name="loantype"> -->
  </div>
</div>
<div class="row mt-2 subprojectdiv" id="subprojectdiv">
	<div class="col-md-12">
		<label><?php echo $subprojectid!=""?"Sub-project name":"Choose the Sub-project"; ?></label>
		<select class="form-control" id="cash_subproject"></select>
	</div>
</div>
<details open style="display: <?php echo ($subaction=="postpayment"?"block":"none"); ?>;">
	<summary>Bank & Transaction Details</summary>
	<div class="row mt-2 fromaccountdiv">
		<div class="col-md-12">
			<label>From Account</label>
			<select id="cash_fromaccount" class="form-control"></select>
		</div>
	</div>
	<div class="row mt-2  mdtdiv modeoftransferdiv">
		<div class="col-md-12">
			<label>Mode of Transfer</label>
			<select id="cash_modeofpayment" class="form-control"></select>
		</div>
	</div>
	<div class="row mt-2 mdtdiv transactionnodiv">
		<div class="col-md-12">
			<label><b id="chqnolbl">Transaction/Cheque number?</b></label>
			<input type="text" class="form-control" id="cash_transactionno" value="" />
		</div>
	</div>
	<div class="row mt-2 mdtdiv transactiondatediv">
		<div class="col-md-12">
			<label><b id="chqnolbl">Transaction/Cheque Date?</b></label>
			<input type="date" class="form-control" id="cash_transactiondate" value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" />
		</div>
	</div>
	<div class="row mt-2 mdtdiv transactiondatediv">
		<div class="col-md-12 d-none">
			<label>Upload payment receipt (prefered)</label>
			<input type="file" id="paymentreceiptfile" class="form-control" accept="image/* capture=camera" />
		</div>
	</div>
</details>

<div class="bg-dark-gray-red-p3 p-4 mt-4">
	<div class="row mt-2 amounttopaydiv">
		<div class="col-md-12">
			<label>How mutch cash amount ?</label>
			<input type="text" id="cash_amount" onkeypress="return isFloatNumber(event, this)" oninput="copyText('cash_amount','cash_taxableamount');" class="form-control" />
		</div>
	</div>
	<div class="row mt-2">
		<div class="col-md-12">
			<label>Cash Payto Name</label>
			<i class="fa fa-plus-circle" style="cursor:pointer; color:green" onclick="openModalForAddCashAccount('hidden_rooturl');"></i>
			<span class="float-right m-2" onclick="request_cashpaymentpaytolist('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','cash_paytoname');" style="cursor: pointer; color: green;"> <i class="fa fa-sync"></i> Refresh</span>

			<select id="cash_paytoname" class="form-control"></select>
		</div>
	</div>
	<div class="row mt-2" id="cash_ppurposesubdiv">
	  <div class="col-md-12" style="display: none;">
	    <label>Purpose sub category</label>
	    <select class="form-control select2 purposesubcategoryslt" style="width: 100%;" id="cash_purposesubcategory" onchange="request_purposecodefrommaster('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','<?php echo $ppcatid; ?>',$(this).val(),'','cash_purposecode','cash_ppurposediv');"></select>
	  </div>
	</div>
	<div class="row mt-2" id="cash_ppurposediv">
	  <div class="col-md-12">
	    <label>Purpose Name</label>
	    <select class="form-control select2 purposecodeslt" style="width: 100%;" id="cash_purposecode"></select>
	  </div>
	</div>
	<div class="row mt-2 paymentduedatediv" style="display: <?php echo ($subaction=="postpayment"?"none":"block"); ?>;">
		<div class="col-md-12">
			<label>Payment Due Date</label>
			<input type="date" class="form-control" name="nvest_paymentduedate" id="nvest_paymentduedate" value="<?php echo date('Y-m-d'); ?>" />
		</div>
	</div>
</div>

<div class="bg-dark-gray-red-p5 p-4 mt-4">
	<div class="row mt-2">
		<div class="col-md-12">
			<label>Voice Remark of Request</label>
		</div>
		<div class="col-md-12">
				<button class="btn-success btn-sm recording-btn mr-2" id="startRecord">&#9899;</button>
		    <button class="btn-danger btn-sm recording-btn" id="stopRecord" disabled>&#11035;</button>
	  </div>
	  <div class="col-md-12 mt-2">
	  	<div id="recordingTime" style="display: none;"><span id="timeElapsed">0</span> s</div>
	    <audio controlsList="nodownload" id="audioPlayback" class="form-control audioPlayback" controls></audio>
  	</div>
  </div>
	<div class="row mt-2">
		<div class="col-md-12">
			<label>Remark</label>
			<textarea id="cash_remark" rows="4" style="resize:none; width: 100%;"></textarea>
		</div>
	</div>
	<div class="row mt-2" style="display: <?php echo ($dz==1?"block":"none"); ?>;">
	  <div class="col-md-12">
		  <label>Do you update categorized attachement?</label>
				<label><input type='radio' name="updatecategorized" value="yes" checked /> YES</label>
				<label><input type='radio' name="updatecategorized" value="no" /> NO</label>
	  </div>
	</div>
	<div class="row mt-2" style="display: <?php echo ($dz==1?"none":"block"); ?>;">
	  	<div class="col-md-12">
		    <label>Do you have any document?</label>
		    <!-- <input id="importfile4" type='file' accept="image/*; capture=camera"/> -->
		    <div class='form-control'><input id="importfile4" type='file' accept="image/*; capture=camera" /><button class="btn-sm pull-right btnshow" style="border-width: thin;" onclick="showPreview_document(document.getElementById('importfile4'), 'blob');">Show Preview</button></div>
	   	</div>
	</div>
	<div class="row mt-2" id="invoicetypediv" style="display:none;">
		<div class="col-md-12">
			<label>Invoice/Voucher Type</label>
			<select id="cash_invoicetype" class="form-control"></select>
		</div>
	</div>
	<div class="row mt-2 dohavegstinvoicediv" id="dohavegstinvoicediv" style="display:none;">
		<div class="col-md-12">
			<label>Do you have purchase invoce?</label><br/>
			<label><input type="radio" name="dohavegstinvoice" class="dohavegstinvoice" value="yes" /> YES</label>
			<label><input type="radio" name="dohavegstinvoice" class="dohavegstinvoice" value="no" /> NO</label>
		</div>
	</div>
	<div class="row mt-2 invoicenodiv" id="invoicenodiv" style="display:none;">
		<div class="col-md-6">
		<label class="taxableamount_lbl">Taxable Amount</label>
		<input type="float" class="form-control taxableamount" onkeypress="return isFloatNumber(event, this)" id="cash_taxableamount" />
	</div>
	<div class="col-md-6">
		<label class="gstamount_lbl">GST Amount</label>
		<input type="float" class="form-control invoicegstamount" onkeypress="return isFloatNumber(event, this)" id="nvest_invoicegstamount" />
	</div>
		<div class="col-md-6">
			<label class="invno_lbl">Invoice/Voucher Number</label>
			<input type="text" class="form-control invoiceno" id="cash_invoiceno" />
		</div>
		<div class="col-md-6">
			<label class="invdate_lbl">Invoice/Voucher Date</label>
			<input type="date" class="form-control invoicedate" id="cash_invoicedate" max="<?php echo date('Y-m-d'); ?>" />
		</div>
	</div>
</div>
<div class="row mt-4">
	<div class="col-md-4"></div>
	<div class="col-md-4 text-center">
		<button id="cash_savetodbbnt" data-isforproduction="" data-categoryorrequest="" class="btn btn-success" onclick='savetodb_requestdataforall("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","cash_subproject","cash_fromaccount","cash_paytoname","cash_amount","cash_invoicetype","cash_invoiceno","cash_invoicedate","cash_modeofpayment","cash_transactionno","cash_transactiondate","cash_purposesubcategory","cash_purposecode","cash_remark","hidden_loginname","paymentreceiptfile","importfile4","audioPlayback","paymenttypeselection","cash_savetodbbnt","hidden_attpath_fromemail","hidden_attsno_fromemail","hidden_emailsno_fromemail","updatecategorized","hidden_instructionid","","nvest_paymentduedate","cash_taxableamount","nvest_invoicegstamount","");'>Save Payment Request</button>
	</div>
	<div class="col-md-4"></div>
</div>
<script type="text/javascript">
var subprjid = "<?php echo $subprojectid; ?>";
var alrd_transactionno = $("#htmlcontainerdiv").data("unrtransactionno");
var alrd_transactiondate = $("#htmlcontainerdiv").data("unrtransactiondate");
var alrd_transactionamount = $("#htmlcontainerdiv").data("unrtransactionamount");
var alrd_likelypayto = $("#htmlcontainerdiv").data("unrlikelypayto");
var alrd_bankdbsno = $("#htmlcontainerdiv").data("unrbankdbsno");
var alrd_bankaccountid = $("#htmlcontainerdiv").data("unrbankaccountid");
var alrd_bankaccountno = $("#htmlcontainerdiv").data("unrbankaccountno");
var alrd_bankaccounttype = $("#htmlcontainerdiv").data("unrbankaccounttype");
var alrd_bankdescription = $("#htmlcontainerdiv").data("unrbankdescription");
var alrd_bankname = $("#htmlcontainerdiv").data("unrbankname");
if(alrd_likelypayto){
	 $('#cash_paytoname').before('<br/><span style="color:red;">We believe you have payto : '+alrd_likelypayto+'</span>');
}
var now = new Date().getTime();
var microseconds = now;
var lastFourDigits = microseconds.toString().slice(-4);

// mdtdiv
if(alrd_bankdbsno){
	$(".modeoftransferdiv").hide();
	if(alrd_transactionno){
		$("#cash_transactionno").val(alrd_transactionno);
		$(".transactionnodiv").hide();
	}else{
		$("#cash_transactionno").val("AG-"+lastFourDigits+"-"+alrd_transactiondate.replace(/-/g,''));
		$(".transactionnodiv").hide();
	}
	if(alrd_transactiondate){
		$("#cash_transactiondate").val(alrd_transactiondate);
		$(".transactiondatediv").hide();
	}

	// $("#nvest_amount").val(alrd_transactionamount);
	if(alrd_transactionamount){
		$("#cash_amount").val(alrd_transactionamount);
		$(".amounttopaydiv").hide();
	}

	if(alrd_bankaccountid!=""){
		$(".fromaccountdiv").hide();
	}else{
		$(".fromaccountdiv").show();
	}
}
subprojectdetails_callback("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid",function(res){
	if(res.length>0){
		if(res.length>1){
			$(".subprojectdiv").show();
			$("#cash_subproject").append("<option value='' disabled selected>Choose sub-project name</option>");
		}else{
			$(".subprojectdiv").hide();
		}
		for (var i = 0; i < res.length; i++) {
			$("#cash_subproject").append("<option value='"+res[i].subprojectid+"'>"+res[i].subprojectname+"</option>");
		}
		if(subprjid && res.length>1){
			$("#cash_subproject").val(subprjid).trigger("change");
		}
	}
});
request_purposesubcategoryfrommaster('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','<?php echo $ppcatid; ?>','','cash_purposesubcategory','cash_ppurposesubdiv');
getaccountdetailsofproject_frmaster("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","hidden_loginid","cash_fromaccount",alrd_bankaccountid);

request_cashpaymentpaytolist('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','cash_paytoname');

modeoftransferfrommaster_todropdown("hidden_baseurl","Online","cash_modeofpayment");
request_invoicetype("hidden_baseurl","","cash_invoicetype");
</script>