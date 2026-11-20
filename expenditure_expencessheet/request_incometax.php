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
		<h4>VENDOR INCOME-TAX REQUEST</h4>
	</div>
</div> -->
<div class="row mt-2 subprojectdiv" id="subprojectdiv">
	<div class="col-md-12">
		<label><?php echo $subprojectid!=""?"Sub-project name":"Choose the Sub-project"; ?></label>
		<select class="form-control" id="itax_subproject"></select>
	</div>
</div>

<div class="row">
	<div class="col-md-4 mt-2">
		<label>Income-Tax Payto name <sup><i class="fa fa-star" style="font-size: 6px; color:red"></i></sup></label>
			<i class="fa fa-plus-circle" id="opennewpaytomodal_forexp" style="cursor: pointer; height: 28px;"></i>
			<span class="float-right m-2 paytoaddrefresh refeshpaytolist" onclick='request_paytofrommaster_incometax("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","itax_payto");' style="cursor: pointer; color: green;"> <i class=" fa fa-sync"></i> Refresh</span>
		<select class="form-control" style="width: 100%;" id="itax_payto"></select>
	</div>
	<div class="col-md-4 mt-2"></div>
	<div class="col-md-4 mt-2">
		<label>Income-Tax Beneficiary Name</label>
		<select class="form-control" style="width: 100%;" id="itax_beneficiaryname"></select>
	</div>
	<div class="col-md-4 mt-2"></div>
</div>

<div class="row mt-2">
	<div class="col-md-12">
		<div id="itaxdataview"></div>
	</div>
</div>
<details open style="display: <?php echo ($subaction=="postpayment"?"block":"none"); ?>;">
	<summary>Bank & Transaction Details</summary>
	<div class="row mt-2  mdtdiv fromaccountdiv" id="itax_frmaccprtdivid">
		<div class="col-md-12">
			<label>Pay From Which Account<sup><i class="fa fa-star" style="font-size: 6px; color:red"></i></sup></label>
			<select id="itax_fromaccount" class="form-control"></select>
		</div>
	</div>
	<div class="row mt-2 mdtdiv transactionnodiv">
		<div class="col-md-12">
			<label><b id="chqnolbl">Transaction/Cheque number?</b></label>
			<input type="text" class="form-control" id="itax_transactionno" value="" />
		</div>
	</div>
	<div class="row mt-2 mdtdiv transactiondatediv">
		<div class="col-md-12">
			<label><b id="chqnolbl">Transaction/Cheque Date?</b></label>
			<input type="date" class="form-control" id="itax_transactiondate" value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" />
		</div>
	</div>
	<div class="row mt-2 mdtdiv transactiondatediv">
		<div class="col-md-12 d-none">
			<label>Upload payment receipt (prefered)</label>
			<input type="file" id="paymentreceiptfile" class="form-control" accept="image/* capture=camera" />
		</div>
	</div>
</details>
<div class="row mt-2" id="itax_psubprtdivid" style="display:none;">
  <div class="col-md-12">
    <p class="lbl-text lbl-full">Purpose sub category</p>
    <select class="form-control select2 purposesubcategoryslt" style="width: 100%;" id="itax_purposesubcategory" onchange="request_purposecodefrommaster('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','<?php echo $ppcatid; ?>',$(this).val(),'','itax_purposecode','itax_pprtdivid');"></select>
  </div>
</div>
<div class="row" id="itax_pprtdivid">
	<div class="col-md-12">
    <p class="lbl-text lbl-full">Purpose</p>
    <select class="form-control select2 purposecodeslt" style="width: 100%;" id="itax_purposecode"></select>
  </div>
</div>
<div class="row">
	<div class="col-md-6 mt-2">
		<label>Mode of Transfer</label>
		<select class="form-control"  style="width: 100%;" id="itax_modeofpayment"></select>
	</div>
	<div class="col-md-6 mt-2">
		<label>Payment Due Date</label>
		<input type="date" class="form-control" id="itax_paymentduedate" max="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" />
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
			<textarea id="itax_remark" rows="4" style="resize:none; width: 100%;"></textarea>
		</div>
	</div>
	<div class="row mt-2" style="display: <?php echo ($dz?"none":"block"); ?>;">
	  	<div class="col-md-12">
		    <label>Do you have any document?</label>
		    <div class='form-control'><input id="importfile4" type='file' accept="image/*; capture=camera" /><button class="btn-sm pull-right btnshow" style="border-width: thin;" onclick="showPreview_document(document.getElementById('importfile4'), 'blob');">Show Preview</button></div>
	   	</div>
	</div>
	<div class="row mt-2" style="display:none;">
		<div class="col-md-12">
			<label>Invoice/Voucher Type</label>
			<select id="itax_invoicetype" class="form-control"></select>
		</div>
	</div>
	<div class="row mt-2" style="display:none;">
		<div class="col-md-12">
			<label>Do you have purchase invoce?</label><br/>
			<label><input type="radio" name="dohavegstinvoice" class="dohavegstinvoice" value="yes" /> YES</label>
			<label><input type="radio" name="dohavegstinvoice" class="dohavegstinvoice" value="no" /> NO</label>
		</div>
	</div>
	<div class="row mt-2" style="display:none;">
		<div class="col-md-6">
			<label class="taxableamount_lbl">Taxable Amount</label>
			<input type="float" class="form-control taxableamount" onkeypress="return isFloatNumber(event, this)" id="itax_taxableamount" />
		</div>
		<div class="col-md-6">
			<label class="gstamount_lbl">GST Amount</label>
			<input type="float" class="form-control invoicegstamount" onkeypress="return isFloatNumber(event, this)" id="itax_invoicegstamount" />
		</div>
		<div class="col-md-6">
			<label class="invno_lbl">Invoice/Voucher Number</label>
			<input type="text" class="form-control invoiceno" id="itax_invoiceno" />
		</div>
		<div class="col-md-6">
			<label class="invdate_lbl">Invoice/Voucher Date</label>
			<input type="date" class="form-control invoicedate" id="itax_invoicedate" max="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" />
		</div>
	</div>
</div>
<div class="row">
  	<div class="col-md-12 mt-4 text-center">
  		<!-- <button class="btn btn-success" id="incometaxrequestbtn">Create Income-Tax Request</button> -->
  		<button id="itax_savetodbbnt" data-isforproduction="" data-categoryofrequest="" class="btn btn-success" onclick='savetodb_requestdatafor_itax("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","itax_subproject","itax_fromaccount","itax_payto","itax_amount","itax_invoicetype","itax_invoiceno","itax_invoicedate","itax_modeofpayment","itax_transactionno","itax_transactiondate","itax_purposesubcategory","itax_purposecode","itax_remark","hidden_loginname","paymentreceiptfile","importfile4","audioPlayback","paymenttypeselection","itax_savetodbbnt","hidden_attpath_fromemail","hidden_attsno_fromemail","hidden_emailsno_fromemail","updatecategorized","hidden_instructionid","","itax_paymentduedate","itax_taxableamount","itax_invoicegstamount","");'>Create Income-Tax Request</button>
  	</div>
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
var now = new Date().getTime();
var microseconds = now;
var lastFourDigits = microseconds.toString().slice(-4);

// mdtdiv
if(alrd_bankdbsno){
	$(".modeoftransferdiv").hide();
	if(alrd_transactionno){
		$("#itax_transactionno").val(alrd_transactionno);
		$(".transactionnodiv").hide();
	}else{
		$("#itax_transactionno").val("AG-"+lastFourDigits+"-"+alrd_transactiondate.replace(/-/g,''));
		$(".transactionnodiv").hide();
	}
	if(alrd_transactiondate){
		$("#itax_transactiondate").val(alrd_transactiondate);
		$(".transactiondatediv").hide();
	}

	// $("#itax_amount").val(alrd_transactionamount);
	if(alrd_transactionamount){
		$("#itax_amount").val(alrd_transactionamount);
		$(".amounttopaydiv").hide();
	}

	if(alrd_bankaccountid!=""){
		$(".fromaccountdiv").hide();
	}else{
		$(".fromaccountdiv").show();
	}
}
subprojectdetails_callback("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid",function(res)
{
	// console.log(res);
		if(res.length>0){
			if(res.length>1){
				$(".subprojectdiv").show();
				$("#itax_subproject").append("<option value='' disabled selected>Choose sub-project name</option>");
			}else{
				$(".subprojectdiv").hide();
			}
			for (var i = 0; i < res.length; i++) {
				$("#itax_subproject").append("<option value='"+res[i].subprojectid+"'>"+res[i].subprojectname+"</option>");
			}
			if(subprjid && res.length>1){
				$("#itax_subproject").val(subprjid).trigger("change");
			}
		}
});

// itax_payto
request_paytofrommaster_incometax("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","itax_payto");

getaccountdetailsofproject_frmaster("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","hidden_loginid","itax_fromaccount",alrd_bankaccountid,'itax_frmaccprtdivid');

modeoftransferfrommaster_todropdown("hidden_baseurl","Online","itax_modeofpayment");
request_purposesubcategoryfrommaster('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','<?php echo $ppcatid; ?>','','itax_purposesubcategory','itax_psubprtdivid');

incometaxdeducted_paytolist('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid','all','','itax_beneficiaryname');

getincometaxdeducted_data('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid','itax_beneficiaryname',function(res){
		$("#itaxdataview").html('');
		if(res){
			$("#itaxdataview").html(res);
			$("#prpdetailsdiv").show();
		}else{
			$("#itaxdataview").html('<h4 class="text-danger text-center">Income-Tax Data Not Available</h4>');
			$("#prpdetailsdiv").hide();
		}
});

$(document).on("change", "#itax_beneficiaryname", function(){
	getincometaxdeducted_data('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid','itax_beneficiaryname',function(res){
		$("#itaxdataview").html('');
		if(res){
			$("#itaxdataview").html(res);
			$("#prpdetailsdiv").show();
		}else{
			$("#itaxdataview").html('<h4 class="text-danger text-center">Income-Tax Data Not Available</h4>');
			$("#prpdetailsdiv").hide();
		}
	});
});

$(document).on("click", "#opennewpaytomodal_forexp", function(){	
		 $("#newpaytodivid").load("<?php echo $rooturl; ?>expenditure_expencessheet/newpayto_authorization.php", function(){
			$("#newpaytodetails_modal").modal("show");
			$("#newpaytodetails_modal").css("z-index","9999");
	    });
	});

	$('#newpaytodetails_modal').on('hidden.bs.modal', function () {
    $('body').addClass('modal-open');
	});

</script>