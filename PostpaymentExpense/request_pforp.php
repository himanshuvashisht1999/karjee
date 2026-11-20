<?php 
$ppcatid = isset($_GET['ppcatid'])?$_GET['ppcatid']:'';
if(!$ppcatid){
  die('Purpose category not defined');
}
$dz="";
if(isset($_GET['dz'])){
	$dz=$_GET['dz'];
}

$ramount="";
if(isset($_GET['ramount'])){
	$ramount=$_GET['ramount'];
}
$rpaytoname="";
// if(isset($_GET['rpaytoname'])){
// 	$rpaytoname=$_GET['rpaytoname'];
// }
$rinstid="";
if(isset($_GET['rinstid'])){
	$rinstid=$_GET['rinstid'];
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
	<div class="row mt-2 subprojectdiv" id="subprojectdiv">
		<div class="col-md-12">
			<label><?php echo $subprojectid!=""?"Sub-project name":"Choose the Sub-project"; ?></label>
			<select class="form-control" id="pfp_subproject"></select>
		</div>
	</div>

	<div class="row mt-2 isforproductiondiv" id="isforproductiondiv" style="display:none;">
		<div class="col-md-12">
			<label>Is it purchase for production request?</label><br/>
			<label><input type="radio" name="isforproductionradio" value="yes" /> Yes</label>
			<label><input type="radio" name="isforproductionradio" value="no" /> No</label>			
		</div>
	</div>
	<details open style="display: <?php echo ($subaction=="postpayment"?"block":"none"); ?>;">
		<summary>Bank & Transaction Details</summary>
		<div class="row fromaccountdiv mdtdiv" id="pfp_faccparentdivid" style="display: <?php echo ($subaction=="postpayment"?"block":"none"); ?>;">
			<div class="col-md-12">
				<label>From Account<sup><i class="fa fa-star" style="font-size: 6px; color:red"></i></sup></label>
				<select id="pfp_fromaccount" class="form-control"></select>
			</div>
		</div>
		<div class="row modeoftransferdiv">
			<div class="col-md-12">
				<label>Mode of Transfer</label>
				<select id="pfp_modeofpayment" class="form-control"></select>
			</div>
		</div>
		<div class="row transactionnodiv mdtdiv">
			<div class="col-md-12">
				<label><b id="chqnolbl">Transaction/Cheque number?</b></label>
				<input type="text" class="form-control" id="pfp_transactionno" value="" />
			</div>
		</div>
		<div class="row transactiondatediv mdtdiv">
			<div class="col-md-12">
				<label><b id="chqnolbl">Transaction/Cheque Date?</b></label>
				<input type="date" class="form-control" id="pfp_transactiondate" max="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" />
			</div>
		</div>
		<div class="row transactiondatediv mdtdiv">
			<div class="col-md-12 d-none">
				<label>Upload payment receipt (prefered)</label>
				<input type="file" id="paymentreceiptfile" class="form-control" accept="image/* capture=camera" />
			</div>
		</div>
	</details>
	<div class="bg-dark-gray-red-p3 p-2 mt-4">
		<div class="row amounttopaydiv">
			<div class="col-md-12">
				<label><?php echo $subaction=="prepayment"?"Request":"Transfered"; ?> Amount <sup><i class="fa fa-star" style="font-size: 6px; color:red"></i></sup></label>
				<?php
					if($ramount>0){
						echo "<i class='text-danger'>You can edit the amount if the difference between the requested amount and the transfer amount is greater than zero.</i>";
					}
				?>
				<input type="text" id="pfp_amount" onkeypress="return isFloatNumber(event, this)" oninput="copyText('pfp_amount','pfp_taxableamount');" class="form-control invoiceamount" value="<?php echo $ramount;?>" />
			</div>
		</div>

		<div class="row mt-2 paytodiv" id="pfppayto_pdivid">
			<div class="col-md-12">
				<label>Who is the Payto? <sup><i class="fa fa-star" style="font-size: 6px; color:red"></i></sup></label>
				<i class="fa fa-plus-circle m-2" id="opennewpaytomodal_forexp" style="cursor: pointer; height: 22px;"></i>
				<span class="fafloat-right m-2 refeshpaytolist" onclick='request_paytofrommaster_withpaytoid("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","pfp_paytoname","","");' style="cursor: pointer; color: green;"> <i class=" fa fa-sync"></i> Refresh</span>
				<select id="pfp_paytoname" class="form-control select2 select2manualxx" style="width:100%;"></select>
			</div>
		</div>
		<div class="row mt-2" id="prps_subcategorydiv" style="display:block;">
		  <div class="col-md-12">
		    <label>Purpose sub category</label>
		    <select class="select2 form-control purposesubcategoryslt" style="width: 100%;" id="pfp_purposesubcategory" onchange="request_purposecodefrommaster('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','<?php echo $ppcatid; ?>',$(this).val(),'','pfp_purposecode','pfpprp_parentdivid');"></select>
		  </div>
		</div>
		<div class="row mt-2 purposenamediv" id="pfpprp_parentdivid">
		  <div class="col-md-12">
		    <label>Purpose name</label>
				<span class="float-right m-2 refeshpaytolist" onclick="request_purposecodefrommaster('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','<?php echo $ppcatid; ?>',$('#pfp_purposesubcategory').val(),'','pfp_purposecode','pfpprp_parentdivid');" style="cursor: pointer; color: green;"> <i class=" fa fa-sync"></i> Refresh</span>
		    <select id="pfp_purposecode" class="form-control select2 select2manualxxx purposecodeslt" style="width:100%;"></select>
		  </div>
		</div>
		<div class="row mt-2 beneficiarydiv">
	    <div class="col-md-12">
				<label><input type="checkbox" id="ispaytoasbeneficiary" onclick="ispaytoasbeneficiary('pfp_paytoname','pfp_beneficiaryname','ispaytoasbeneficiary')" /> Is payto name same as beneficiary?</label>
			</div>
		</div>
		<div class="row mt-2 beneficiarydiv">
	    <div class="col-md-12">
	      <label>Who is beneficiary?</label>
	      <p class="fa fa-plus-circle m-2" id="opennewbeneficiarymodal" style="cursor: pointer; height: 22px;"></i>
	      <span class="float-right m-2 refeshpaytolist" onclick='request_beneficiaryfrommaster("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","pfp_beneficiaryname","");' style="cursor: pointer; color: green;"> <i class=" fa fa-sync"></i> Refresh</span>
	      <script type="text/javascript">
	      request_beneficiaryfrommaster("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","pfp_beneficiaryname","");
	      </script>
	      <select class="form-control select2 pfp_beneficiaryname" style="width: 100%;" id="pfp_beneficiaryname">
	      </select>				            
	    </div>
	  </div>
		
		<div class="row mt-2 paymentduedatediv" style="display: <?php echo ($subaction=="postpayment"?"none":"block"); ?>;">
	    	<div class="col-md-12">
	    		<label>Payment Due Date</label>
	    		<input type="date" class="form-control" name="pfp_paymentduedate" id="pfp_paymentduedate" value="" />
	    	</div>
	  </div>
	</div>

	<div id="otherpurposehtml"></div>

	<div class="bg-dark-gray-red-p5 p-2 mt-4">
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
				<textarea id="pfp_remark" rows="4" style="resize:none; width: 100%;"></textarea>
			</div>
		</div>		
		<div class="row mt-2" style="display: <?php echo ($dz==1?"block":"none"); ?>;">
		  <div class="col-md-12">
			  <label>Do you update categorized attachement?</label>
					<label><input type='radio' name="updatecategorized" value="yes" checked /> YES</label>
					<label><input type='radio' name="updatecategorized" value="no" /> NO</label>
		  </div>
		</div>
		<div class="row mt-2" id="isponodiv" style="display:none;">
			<div class="col-md-12">
				<label>Enter the PO number(If available)</label><br/>
				<input type="text" class="form-control" name="ponumber" id="ponumber" />
			</div>
		</div>
		<div class="row mt-2" style="display: <?php echo ($dz==1?"none":"block"); ?>;">
	  	<div class="col-md-12">
		    <label>Do you have any document?</label>
		    <label><input type="radio" name="doyoudocumentradio" value="yes" /> YES</label>
		    <label><input type="radio" name="doyoudocumentradio" value="no" /> NO</label>
	  	</div>
	  </div>
	  <div id="dodocdiv" style="display: none;">
	  	<div class="row mt-2" style="display: <?php echo ($dz==1?"none":"block"); ?>;">
		  	<div class="col-md-12" style="">
			    <label>Choose the document?</label>
			    <!-- <input id="importfile4" type='file' accept="image/*; capture=camera"/> -->
			    <div class='form-control'><input id="importfile4" type='file' accept="image/*; capture=camera" /><button class="btn-sm pull-right btnshow" style="border-width: thin;" onclick="showPreview_document(document.getElementById('importfile4'), 'blob');">Show Preview</button></div>
		   	</div>
			</div>
			
			<div class="row mt-2 " style="display: <?php echo ($dz!=2?"block":"none"); ?>;">
				<div class="col-md-12">
					<label>Invoice/Voucher Type</label>
					<select id="pfp_invoicetype" class="form-control invoicetype"></select>
				</div>
			</div>
			<div class="row mt-2 dohavegstinvoicediv" id="dohavegstinvoicediv" style="display:none;">
				<div class="col-md-12">
					<label>Do you have taxable/purchase invoce?</label><br/>
					<label><input type="radio" name="dohavegstinvoice" class="dohavegstinvoice" value="yes" /> YES</label>
					<label><input type="radio" name="dohavegstinvoice" class="dohavegstinvoice" value="no" /> NO</label>
				</div>
			</div>
			<div class="row mt-2 invoicenodiv" id="invoicenodiv"  style="display: <?php echo ($dz==2?"none":"block"); ?>;">
				<div class="col-md-12">
					<label class="taxableamount_lbl">Taxable Amount</label>
					<input type="float" class="form-control taxableamount" onkeypress="return isFloatNumber(event, this)" id="pfp_taxableamount" />
				</div>
				<div class="col-md-12">
					<label class="gstamount_lbl">GST Amount</label>
					<input type="float" class="form-control invoicegstamount" onkeypress="return isFloatNumber(event, this)" id="pfp_invoicegstamount" />
				</div>

				<div class="col-md-12">
					<label class="invno_lbl">Invoice/Voucher Number</label>
					<input type="text" class="form-control invoiceno" id="pfp_invoiceno" />
				</div>

				<div class="col-md-12">
					<label class="invdate_lbl">Invoice/Voucher Date</label>
					<input type="date" class="form-control invoicedate" id="pfp_invoicedate" max="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" />
				</div>
			</div>
		</div>
	</div>

	<div class="row mt-4">
		<div class="col-md-12 text-center">
			<button id="pfp_savetodbbnt" data-isforproduction="yes" data-categoryofrequest="" class="btn btn-success" onclick='savetodb_requestdataforall("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","pfp_subproject","pfp_fromaccount","pfp_paytoname", "pfp_amount","pfp_invoicetype","pfp_invoiceno","pfp_invoicedate", "pfp_modeofpayment", "pfp_transactionno", "pfp_transactiondate", "pfp_purposesubcategory", "pfp_purposecode", "pfp_remark","hidden_loginname","paymentreceiptfile","importfile4","audioPlayback","paymenttypeselection","pfp_savetodbbnt", "hidden_attpath_fromemail","hidden_attsno_fromemail","hidden_emailsno_fromemail","updatecategorized","hidden_instructionid","pfp_beneficiaryname","pfp_paymentduedate","pfp_taxableamount","pfp_invoicegstamount","ponumber");' value="<?php echo ($subaction=="postpayment"?"Save Book Entry":"Save Payment Request"); ?>"><?php echo ($subaction=="postpayment"?"Save Book Entry":"Save Payment Request"); ?></button>
		</div>
	</div>
	<script type="text/javascript">
		var subprjid = "<?php echo $subprojectid; ?>";
		var alrd_transactionno = $("#htmlcontainerdiv").data("unrtransactionno");
		var alrd_transactiondate = $("#htmlcontainerdiv").data("unrtransactiondate");
		var alrd_transactionamount = $("#htmlcontainerdiv").data("unrtransactionamount");
		var alrd_likelypayto = $("#htmlcontainerdiv").data("unrlikelypayto");
		var alrd_bankdbsno = $("#htmlcontainerdiv").data("unrbankdbsno");
		var alrd_bankaccountid = $("#htmlcontainerdiv").data("unrbankaccountid"); //done
		var alrd_bankaccountno = $("#htmlcontainerdiv").data("unrbankaccountno");
		var alrd_bankaccounttype = $("#htmlcontainerdiv").data("unrbankaccounttype");
		var alrd_bankdescription = $("#htmlcontainerdiv").data("unrbankdescription");
		var alrd_bankname = $("#htmlcontainerdiv").data("unrbankname");

		$(document).on("change", "#pfp_invoicetype", function(){
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

		if(alrd_likelypayto){
			 $('#pfp_paytoname').before('<br/><span style="color:red;">We believe you have payto : '+alrd_likelypayto+'</span>');
		}
		var now = new Date().getTime();
		var microseconds = now;
		var lastFourDigits = microseconds.toString().slice(-4);
		
		// mdtdiv
		if(alrd_bankdbsno){
			$(".modeoftransferdiv").hide();
			if(alrd_transactionno){
				$("#pfp_transactionno").val(alrd_transactionno);
				$(".transactionnodiv").hide();
			}else{
				$("#pfp_transactionno").val("AG-"+lastFourDigits+"-"+alrd_transactiondate.replace(/-/g,''));
				$(".transactionnodiv").hide();
			}
			if(alrd_transactiondate){
				$("#pfp_transactiondate").val(alrd_transactiondate);
				$(".transactiondatediv").hide();
			}

			if(alrd_transactionamount){
				$("#pfp_amount").val(alrd_transactionamount);
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
					$("#pfp_subproject").append("<option value='' disabled selected>Choose sub-project name</option>");
				}else{
					$(".subprojectdiv").hide();
				}
				for (var i = 0; i < res.length; i++) {
					$("#pfp_subproject").append("<option value='"+res[i].subprojectid+"'>"+res[i].subprojectname+"</option>");
				}
				if(subprjid && res.length>1){
					$("#pfp_subproject").val(subprjid).trigger("change");
				}
			}
		});
		request_paytofrommaster_withpaytoid("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","pfp_paytoname","",alrd_likelypayto);
		request_purposesubcategoryfrommaster('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','<?php echo $ppcatid; ?>','','pfp_purposesubcategory','prps_subcategorydiv');
		// request_purposecodefrommaster_markfromnote4('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','<?php echo $ppcatid; ?>','','','pfp_purposecode');

		getaccountdetailsofproject_frmaster("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","hidden_loginid","pfp_fromaccount",alrd_bankaccountid,'pfp_faccparentdivid');
		modeoftransferfrommaster_todropdown("hidden_baseurl","Online","pfp_modeofpayment");	
		request_invoicetype("hidden_baseurl","","pfp_invoicetype");

		

		$(document).on("click", "input[name='doyoudocumentradio']", function()
		{
			if($(this).val()=='yes'){
				$("#dodocdiv").show();			
			}else{
				$("#dodocdiv").hide();			
			}
		});
		
		$(document).on("click", "#checktogetcategorywise", function(){
			if($("#checktogetcategorywise").is(":checked")){
				$("#prps_subcategorydiv").show();
				$("#pfp_purposecode").prop("selectedIndex", 0);
			}else{
				$("#prps_subcategorydiv").hide();
				request_purposecodefrommaster_markfromnote4('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','<?php echo $ppcatid; ?>','','','pfp_purposecode');
			}
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

		
		/*$(document).on("click", "#opennewpurposemodal_forexp", function(){	
			 $("#newpurposedivid").load("<?php echo $rooturl; ?>expenditure_expencessheet/purpose_addnew.php", function(){
				$("#newpurposedetails_modal").modal("show");
				$("#newpurposedetails_modal").css("z-index","9999");
		    });
		});*/
		$('.select2').select2();
		$('.select2-selection').css('border-radius','0px');
		$('.select2-container').children().css('border-radius','0px');
		$('.select2-selection').css('height','42px');
	</script>