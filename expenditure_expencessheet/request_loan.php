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
<!-- <div class="row">
	<div class="col-md-12 text-center">
		<h4>LOAN REQUEST</h4>
	</div>
</div> -->
<div class="row mt-2 subprojectdiv" id="subprojectdiv">
	<div class="col-md-12">
		<label><?php echo $subprojectid!=""?"Sub-project name":"Choose the Sub-project"; ?></label>
		<select class="form-control" id="loan_subproject"></select>
	</div>
</div>
<div class="row mt-2 amounttopaydiv">
	<div class="col-md-12">
		<label>Loan Amount</label>
		<input type="text" id="loan_amount" onkeypress="return isFloatNumber(event, this)" oninput="copyText('loan_amount','loan_taxableamount');" class="form-control invoiceamount" />
	</div>
</div>
<div class="row mt-2" id="lon_psubcatprtdivid">
  <div class="col-md-12" style="display: block;">
    <label>Purpose sub category</label>
    <select class="form-control select2 purposesubcategoryslt" style="width: 100%;" id="loan_purposesubcategory"></select>
  </div>
</div>
<div class="row mt-2" id="lon_pprpprtdivid">
  <div class="col-md-12">
    <label>Purpose Name</label>
    <select class="form-control purposecodeslt" style="width: 100%;" id="loan_purposecode"></select>
  </div>
</div>
<div class="row mt-2">
	<div class="col-md-12">
		<label>Loan Payto Name</label>
		<i class="fa fa-plus-circle openloanauthorizedmodal"  style="font-size: 22px; margin:4px; color:#1A5319; cursor:pointer;"></i>
		<span class="float-right m-2 refreshnewpayto" onclick="request_loanpayto('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid',$('#loan_purposesubcategory').val(),'loan_paytoname');" style="cursor: pointer; color: green;"> <i class="fa fa-sync"></i> Refresh</span>
    <select class="form-control" style="width: 100%;" id="loan_paytoname"></select>
	</div>
</div>

<div class="row mt-2" id="invoicetypediv" style="display:none;">
	<div class="col-md-12">
		<label>Invoice/Voucher Type</label>
		<select id="loan_invoicetype" class="form-control"></select>
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
		<label class="invno_lbl">Invoice/Voucher Number</label>
		<input type="text" class="form-control invoiceno" id="loan_invoiceno" />
	</div>
	<div class="col-md-6">
		<label class="invdate_lbl">Invoice/Voucher Date</label>
		<input type="date" class="form-control invoicedate" id="loan_invoicedate" max="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" />
	</div>
</div>
<div class="row mt-2 fromaccountdiv" id="lon_frmaccprtdivid">
	<div class="col-md-12">
		<label>From Account</label>
		<select id="loan_fromaccount" class="form-control"></select>
	</div>
</div>
<div class="row mt-2  mdtdiv modeoftransferdiv">
	<div class="col-md-12">
		<label>Mode of Transfer</label>
		<select id="loan_modeofpayment" class="form-control"></select>
	</div>
</div>
<div class="row mt-2 paymentduedatediv" style="display: <?php echo ($subaction=="postpayment"?"none":"block"); ?>;">
  	<div class="col-md-12">
  		<label>Payment Due Date</label>
  		<input type="date" class="form-control" name="loan_paymentduedate" id="loan_paymentduedate" value="<?php echo date('Y-m-d'); ?>" />
  	</div>
</div>
<div class="row mt-2 mdtdiv transactionnodiv">
	<div class="col-md-12">
		<label><b id="chqnolbl">Transaction/Cheque number?</b></label>
		<input type="text" class="form-control" id="loan_transactionno" value="" />
	</div>
</div>
<div class="row mt-2 mdtdiv transactiondatediv">
	<div class="col-md-12">
		<label><b id="chqnolbl">Transaction/Cheque Date?</b></label>
		<input type="date" class="form-control" id="loan_transactiondate" max="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" />
	</div>
</div>
<div class="row mdtdiv transactiondatediv">
	<div class="col-md-12 d-none">
		<label>Upload payment receipt (prefered)</label>
		<input type="file" id="paymentreceiptfile" class="form-control" accept="image/* capture=camera" />
	</div>
</div>
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
		<textarea id="loan_remark" rows="4" style="resize:none; width: 100%;"></textarea>
	</div>
</div>

<div class="row mt-2" style="display: <?php echo ($dz==1?"block":"none"); ?>;">
  <div class="col-md-12">
	  <label>Do you update categorized attachement?</label>
			<label><input type='radio' name="updatecategorized" value="yes" checked /> YES</label>
			<label><input type='radio' name="updatecategorized" value="no" /> NO</label>
  </div>
</div>
<div class="row mt-2" style="display: <?php echo ($dz?"none":"block"); ?>;">
  	<div class="col-md-12">
	    <label>Do you have any document?</label>
	    <!-- <input id="importfile4" type='file' accept="image/*; capture=camera"/> -->
	    <div class='form-control'><input id="importfile4" type='file' accept="image/*; capture=camera" /><button class="btn-sm pull-right btnshow" style="border-width: thin;" onclick="showPreview_document(document.getElementById('importfile4'), 'blob');">Show Preview</button></div>
   	</div>
</div>
<div class="col-md-12" style="display:none;">
	<label class="taxableamount_lbl">Taxable Amount</label>
	<input type="float" class="form-control taxableamount" onkeypress="return isFloatNumber(event, this)" id="loan_taxableamount" />
</div>
<div class="col-md-12" style="display:none;">
	<label class="gstamount_lbl">GST Amount</label>
	<input type="float" class="form-control invoicegstamount" onkeypress="return isFloatNumber(event, this)" id="loan_invoicegstamount" />
</div>
<!-- <div class="row mt-2">
   	<div class="col-md-12">
      <a target="_blank" href="bnf_inoice_attachment"><image src="" id="loan_attachments" name="loan_attachments" style="width:100%;" /></a>
      <input type="hidden" id="loan_attachmentsval" name="loan_attachmentsval" />
      <input type="hidden" id="loan_docsno" name="loan_docsno" />
    </div>
    <div class="col-md-12">
	    <p style="color:red;">Note:- If you have choosen wrong attachement then click the choose file again.</p>
	</div>
</div> -->
<div class="row mt-4">
	<div class="col-md-4"></div>
	<div class="col-md-4 text-center">
		<button id="loan_savetodbbnt" data-isforproduction="" data-categoryorrequest="" class="btn btn-success" onclick='savetodb_requestdataforall("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","loan_subproject","loan_fromaccount","loan_paytoname","loan_amount","loan_invoicetype","loan_invoiceno","loan_invoicedate","loan_modeofpayment","loan_transactionno","loan_transactiondate","loan_purposesubcategory","loan_purposecode","loan_remark","hidden_loginname","paymentreceiptfile","importfile4","audioPlayback","paymenttypeselection","loan_savetodbbnt","hidden_attpath_fromemail","hidden_attsno_fromemail","hidden_emailsno_fromemail","updatecategorized","hidden_instructionid","","loan_paymentduedate","loan_taxableamount","loan_invoicegstamount","");'>Save Loan Request</button>
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
	 $('#loan_paytoname').before('<br/><span style="color:red;">We believe you have payto : '+alrd_likelypayto+'</span>');
}

$(document).on("change", "#loan_purposesubcategory", function(){
	request_purposecodefrommaster('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','<?php echo $ppcatid; ?>',$("#loan_purposesubcategory").val(),'','loan_purposecode','lon_pprpprtdivid');
	request_loanpayto('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid',$("#loan_purposesubcategory").val(),'loan_paytoname');
});

var now = new Date().getTime();
var microseconds = now;
var lastFourDigits = microseconds.toString().slice(-4);

// mdtdiv
if(alrd_bankdbsno){
	$(".modeoftransferdiv").hide();
	if(alrd_transactionno){
		$("#loan_transactionno").val(alrd_transactionno);
		$(".transactionnodiv").hide();
	}else{
		$("#loan_transactionno").val("AG-"+lastFourDigits+"-"+alrd_transactiondate.replace(/-/g,''));
		$(".transactionnodiv").hide();
	}
	if(alrd_transactiondate){
		$("#loan_transactiondate").val(alrd_transactiondate);
		$(".transactiondatediv").hide();
	}

	if(alrd_transactionamount){
		$("#loan_amount").val(alrd_transactionamount);
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
				$("#loan_subproject").append("<option value='' disabled selected>Choose sub-project name</option>");
			}else{
				$(".subprojectdiv").hide();
			}
			for (var i = 0; i < res.length; i++) {
				$("#loan_subproject").append("<option value='"+res[i].subprojectid+"'>"+res[i].subprojectname+"</option>");
			}
			if(subprjid && res.length>1){
				$("#loan_subproject").val(subprjid).trigger("change");
			}
		}
	});
request_purposesubcategoryfrommaster('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','<?php echo $ppcatid; ?>','','loan_purposesubcategory','lon_psubcatprtdivid');

// request_purposecodefrommaster_markfromnote4('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','<?php echo $ppcatid; ?>','','','loan_purposecode');
getaccountdetailsofproject_frmaster("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","hidden_loginid","loan_fromaccount",alrd_bankaccountid,'lon_frmaccprtdivid');
modeoftransferfrommaster_todropdown("hidden_baseurl","Online","loan_modeofpayment");
$(document).on("change", "#loan_paytoname", function(){
	var value = $(this).val();
	var select = document.getElementById("loan_fromaccount");
  for (var i = 0; i < select.options.length; i++) {
    if (select.options[i].value === $(this).val()) {
      select.options[i].style.display = "none";
      break;
    }else{
    	select.options[i].style.display = "block";
    }
  }
});

request_invoicetype("hidden_baseurl","","loan_invoicetype");
$(document).on("change", "#loan_invoicetype", function(){
	$(".invoicetypetxt").html($(this).val());
	if($(this).val()=="Invoice"){
		$("#gstinvoicedetailsmodal").modal("show");
		$("#invoicenodiv").show();
	}else if($(this).val()=="Cash Memo"){
		$("#gstinvoicedetailsmodal").modal("show");
		$("#invoicenodiv").show();
	}else{
		$("#invoicenodiv").hide();
		$("#gstinvoicedetailsmodal").modal("hide");
	}
});
$(document).on("click", "#opennewpaytomodal_forexp", function(){
	 $("#newpaytodivid").load("<?php echo $rooturl; ?>expenditure_expencessheet/newpayto_authorization.php", function(){
		$("#newpaytodetails_modal").modal("show");
    });
});
// document.getElementById('importfile4').addEventListener('change', function() {
//   var file = this.files[0];
//   if (file) {
//     var reader = new FileReader();
//     reader.onload = function(event) {
//       var imageData = event.target.result;
//       // Set iframe src to the data URL of the image
//       document.getElementById('loan_attachments').src = imageData;
//     };
//     reader.readAsDataURL(file);
//   }
// });
</script>