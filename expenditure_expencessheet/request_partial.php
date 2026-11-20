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
<!-- <div class="row mb-4">
	<div class="col-md-12 text-center">
		<h4>PARTIAL PAYMENT REQUEST</h4>
	</div>
</div> -->
<div class="row mt-2 subprojectdiv" id="subprojectdiv">
	<div class="col-md-12">
		<label><?php echo $subprojectid!=""?"Sub-project name":"Choose the Sub-project"; ?></label>
		<select class="form-control" id="partial_subproject"></select>
	</div>
</div>
<details open style="display: <?php echo ($subaction=="postpayment"?"block":"none"); ?>;">
	<summary>Bank & Transaction Details</summary>
	<div class="row mt-2 fromaccountdiv" id="prt_facprtdivid">
		<div class="col-md-12">
			<label>From Account</label>
			<select id="partial_fromaccount" class="form-control"></select>
		</div>
	</div>
	<div class="row mt-2 mdtdiv modeoftransferdiv">
		<div class="col-md-12">
			<label>Mode of Transfer</label>
			<select id="partial_modeofpayment" class="form-control"></select>
		</div>
	</div>
	<div class="row mt-2 mdtdiv transactionnodiv">
		<div class="col-md-12">
			<label><b id="chqnolbl">Transaction/Cheque number?</b></label>
			<input type="text" class="form-control" id="partial_transactionno" value="" />
		</div>
	</div>
	<div class="row mt-2 mdtdiv transactiondatediv">
		<div class="col-md-12">
			<label><b id="chqnolbl">Transaction/Cheque Date?</b></label>
			<input type="date" class="form-control" id="partial_transactiondate" max="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" />
		</div>
	</div>
	<div class="row mt-2 mdtdiv transactiondatediv">
		<div class="col-md-12 d-none">
			<label>Upload payment receipt (prefered)</label>
			<input type="file" id="paymentreceiptfile" class="form-control" accept="image/* capture=camera" />
		</div>
	</div>
</details>
<div class="bg-dark-gray-red-p3 p-2 mt-4">
	<div class="row mt-2 amounttopaydiv">
		<div class="col-md-12">
			<label>Partial Amount</label>
			<input type="text" id="partial_amount" onkeypress="return isFloatNumber(event, this)" oninput="copyText('partial_amount','partial_taxableamount');" class="form-control invoiceamount" />
		</div>
	</div>
	<div class="row mt-2">
		<div class="col-md-12">
			<div id="partial_paytonamelist"></div>
		</div>
	</div>
	<div class="row mt-2" id="prt_psubprtdivid">
	  <div class="col-md-12" style="display: none;">
	    <label>Purpose sub category</label>
	    <select class="form-control select2 purposesubcategoryslt" style="width: 100%;" id="partial_purposesubcategory" onchange="request_purposecodefrommaster('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','<?php echo $ppcatid; ?>',$(this).val(),'','partial_purposecode','prt_parentdivid');"></select>
	  </div>
	</div>
	<div class="row mt-2" id="prt_parentdivid">
	  <div class="col-md-12">
	    <label>Purpose Name</label>
	    <select class="form-control select2 purposecodeslt" style="width: 100%;" id="partial_purposecode"></select>
	  </div>
	</div>
	<div class="row mt-2 paymentduedatediv" style="display: <?php echo ($subaction=="postpayment"?"none":"block"); ?>;">
	  	<div class="col-md-12">
	  		<label>Payment Due Date</label>
	  		<input type="date" class="form-control" name="partial_paymentduedate"  min="<?php echo date('Y-m-d'); ?>" id="partial_paymentduedate" value="" />
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
			<textarea id="partial_remark" rows="4" style="resize:none; width: 100%;"></textarea>
		</div>
	</div>
	<div class="row mt-2" style="display: <?php echo ($dz==1?"block":"none"); ?>;">
	  <div class="col-md-12">
		  <label>Do you update categorized attachement?</label>
				<label><input type='radio' name="updatecategorized" value="yes" checked /> YES</label>
				<label><input type='radio' name="updatecategorized" value="no" /> NO</label>
	  </div>
	</div>
	<div class="row mt-4" style="display: <?php echo ($dz==1?"none":"block"); ?>;">
		<div class="col-md-12">
	    <label>Do you have any document?</label>
	    <label><input type="radio" name="doyoudocumentradio" value="yes" /> YES</label>
	    <label><input type="radio" name="doyoudocumentradio" value="no" /> NO</label>
		</div>
	</div>
	<div id="dodocdiv" style="display: none;">
		<div class="row mt-2" style="display: <?php echo ($dz?"none":"block"); ?>;">
		  	<div class="col-md-12">
			    <label>Do you have any document?</label>
			    <!-- <input id="importfile4" type='file' accept="image/*; capture=camera"/> -->
			    <div class='form-control'><input id="importfile4" type='file' accept="image/*; capture=camera" /><button class="btn-sm pull-right btnshow" style="border-width: thin;" onclick="showPreview_document(document.getElementById('importfile4'), 'blob');">Show Preview</button></div>
		   	</div>
		</div>
		<div class="row mt-2" id="invoicetypediv" style="display:none;">
			<div class="col-md-12">
				<label>Invoice/Voucher Type</label>
				<select id="partial_invoicetype" class="form-control"></select>
			</div>
		</div>
		<div class="row mt-2 dohavegstinvoicediv" id="dohavegstinvoicediv" style="display:none;">
				<div class="col-md-12">
					<label>Do you have purchase invoce?</label><br/>
					<label><input type="radio" name="dohavegstinvoice" class="dohavegstinvoice" value="yes" /> YES</label>
					<label><input type="radio" name="dohavegstinvoice" class="dohavegstinvoice" value="no" /> NO</label>
				</div>
		</div>
		<div class="row mt-2 invoicenodiv" id="invoicenodiv">
			<div class="col-md-6">
				<label class="invno_lbl">Invoice/Voucher Number</label>
				<input type="text" class="form-control invoiceno" id="partial_invoiceno" />
			</div>
			<div class="col-md-6">
				<label class="invdate_lbl">Invoice/Voucher Date</label>
				<input type="date" class="form-control invoicedate" id="partial_invoicedate" max="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" />
			</div>
		</div>
		<div class="col-md-12" style="display:none;">
			<label class="taxableamount_lbl">Taxable Amount</label>
			<input type="float" class="form-control taxableamount" onkeypress="return isFloatNumber(event, this)" id="partial_taxableamount" />
		</div>
		<div class="col-md-12" style="display:none;">
			<label class="gstamount_lbl">GST Amount</label>
			<input type="float" class="form-control invoicegstamount" onkeypress="return isFloatNumber(event, this)" id="partial_invoicegstamount" />
		</div>
	</div>
</div>

<div class="row mt-4">
	<div class="col-md-4"></div>
	<div class="col-md-4 text-center">
		<button id="partial_savetodbbnt" data-isforproduction="" data-categoryorrequest="" class="btn btn-info" onclick='savetodb_requestdataforall("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","partial_subproject","partial_fromaccount","paytoname", "partial_amount","partial_invoicetype","partial_invoiceno","partial_invoicedate", "partial_modeofpayment", "partial_transactionno", "partial_transactiondate", "partial_purposesubcategory", "partial_purposecode", "partial_remark","hidden_loginname","paymentreceiptfile","importfile4","audioPlayback","paymenttypeselection","partial_savetodbbnt", "hidden_attpath_fromemail","hidden_attsno_fromemail","hidden_emailsno_fromemail","updatecategorized","hidden_instructionid","","partial_paymentduedate","partial_taxableamount","partial_invoicegstamount","");'>Save Partial Request</button>
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
	 $('#paytoname').before('<br/><span style="color:red;">We believe you have payto : '+alrd_likelypayto+'</span>');
}
$(document).on("click", "input[name='doyoudocumentradio']", function()
{
	if($(this).val()=='yes'){
		$("#dodocdiv").show();			
	}else{
		$("#dodocdiv").hide();			
	}
});

var now = new Date().getTime();
var microseconds = now;
var lastFourDigits = microseconds.toString().slice(-4);

// mdtdiv
if(alrd_bankdbsno){
	$(".modeoftransferdiv").hide();
	if(alrd_transactionno){
		$("#partial_transactionno").val(alrd_transactionno);
		$(".transactionnodiv").hide();
	}else{
		$("#partial_transactionno").val("AG-"+lastFourDigits+"-"+alrd_transactiondate.replace(/-/g,''));
		$(".transactionnodiv").hide();
	}
	if(alrd_transactiondate){
		$("#partial_transactiondate").val(alrd_transactiondate);
		$(".transactiondatediv").hide();
	}

	// $("#spe_amount").val(alrd_transactionamount);
	if(alrd_transactionamount){
		$("#partial_amount").val(alrd_transactionamount);
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
				$("#partial_subproject").append("<option value='' disabled selected>Choose sub-project name</option>");
			}else{
				$(".subprojectdiv").hide();
			}
			for (var i = 0; i < res.length; i++) {
				$("#partial_subproject").append("<option value='"+res[i].subprojectid+"'>"+res[i].subprojectname+"</option>");
			}
			if(subprjid && res.length>1){
				$("#partial_subproject").val(subprjid).trigger("change");
			}
		}
	});
request_purposesubcategoryfrommaster('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','<?php echo $ppcatid; ?>','','partial_purposesubcategory','prt_psubprtdivid');
getaccountdetailsofproject_frmaster("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","hidden_loginid","partial_fromaccount",alrd_bankaccountid,'prt_facprtdivid');


request_paytolist_withhtml("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","partial_paytonamelist");
modeoftransferfrommaster_todropdown("hidden_baseurl","Online","partial_modeofpayment");
request_invoicetype("hidden_baseurl","","partial_invoicetype");

$(document).on("change", "#partial_invoicetype", function(){
	$(".invoicetypetxt").html($(this).val());
	if($(this).val()=="Invoice"){
		$(".invno_lbl").html("Invoice/Voucher No");
		$(".invdate_lbl").html("Invoice/Voucher Date");
		// $("#gstinvoicedetailsmodal").modal("show");
		$("#invoicenodiv").show();
	}else if($(this).val()=="Cash Memo"){
		$(".invno_lbl").html("Cash Memo No");
		$(".invdate_lbl").html("Cash Memo Date");
		// $("#gstinvoicedetailsmodal").modal("show");
		$("#invoicenodiv").show();
	}else if($(this).val()=="Purchase Order (PO)"){
		$("#invoicenodiv").show();
		$(".invno_lbl").html("Purchase Order No");
		$(".invdate_lbl").html("Purchase Order Date");
	}else if($(this).val()=="Delivery Note/Chalan"){
		$("#invoicenodiv").show();
		$(".invno_lbl").html("Delivery Note/Chalan No");
		$(".invdate_lbl").html("Delivery Note/Chalan Date");
	}else if($(this).val()=="Packing List"){
		$("#invoicenodiv").show();
		$(".invno_lbl").html("Packing List No");
		$(".invdate_lbl").html("Packing List Date");
	}else{
		$("#invoicenodiv").hide();
		$(".invno_lbl").html("Invoice/Voucher No");
		$(".invdate_lbl").html("Invoice/Voucher Date");
		// $("#gstinvoicedetailsmodal").modal("hide");
	}
});
	
$(document).on("click", "#opennewpaytomodal_forexp", function(){
	$("#newpaytodivid").load("<?php echo $rooturl; ?>expenditure_expencessheet/newpayto_authorization.php", function(){
		$("#newpaytodetails_modal").modal("show");
		$("#newpaytodetails_modal").css("z-index","9999");
  });
});
</script>