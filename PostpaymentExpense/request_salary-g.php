<?php 
$ppcatid = isset($_GET['ppcatid'])?$_GET['ppcatid']:'';
if(!$ppcatid){
  die('Purpose category not defined');
}
$dz="";
if(isset($_GET['dz'])){
	$dz=$_GET['dz'];
}


$sltyp="";
if(isset($_GET['sltyp'])){
	$sltyp=$_GET['sltyp'];
}

$subaction="";
if(isset($_GET['subaction'])){
  $subaction = str_replace("-", "", strtolower($_GET['subaction']));
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
?>
<div class="row mt-4 mb-4 hideifalready <?php echo $sltyp!=""?"d-none":""; ?>" >
	<div class="col-md-12">
		<label>Do you upload salary excel sheet of employee to book entry?</label><br/>
		<label class="m-2"><input type="radio" name="isuploadsalarysheet" value="yes"> YES</label>
		<label class="m-2"><input type="radio" name="isuploadsalarysheet" value="no"> NO</label>
	</div>
</div>
<div id="uploadsheetcontainerdiv" style="display:none;">
	<!-- <div class="row mb-4">
		<div class="col-md-12 text-center">
			<h4>UPLOAD SALARY SHEET OF EMPLOYEE FOR RECONSTRUCTING BOOK</h4>
		</div>
	</div> -->
	<div class="row">
		<div class="col-md-6">
			<b>Note:-</b><ul style="color:red;">
				<li>Download the salary excel format sheet</li>
				<li>Check your salary sheet along with the salary Excel format sheet.</li>
				<li>Please upload the salary Excel sheet that which is formatted. </li></ul>
		</div>
		<div class="col-md-6">
			<div id="formatsheetdiv"></div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<input type="file" name="salarysheetatt" id="salarysheetatt" class="form-control mt-2" />
		</div>
	</div>
	<div class="row">
		<div class="col-md-4"></div>
		<div class="col-md-4 text-center">
			<button class="btn btn-success mt-2" id="uploadsalarysheetbtn" onclick="uploadsalarysheetforcreatebook('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','salarysheetatt','hidden_loginname','uploadsalarysheetbtn');">Upload salary excel sheet</button>
		</div>
		<div class="col-md-4"></div>
	</div>
	<div class="row mt-4">
		<div class="col-md-12 mt-4">
			<details id="pendingsalarydatadtl" open>
				<summary><h5>Salary data for creating a book</h5></summary>
				<div class="table-responsive">
					<table class="table table-bordered" id="pendingbookofsalarytbl">
						<thead>
							<tr>
								<th>S.No</th>
								<th>Employee Name</th>
								<th>Month Of Salary</th>
								<th>Designation</th>
								<th>Actual Salary</th>
								<th>Days</th>
								<th style='display:none;'>Monthly Salary</th>
								<th style='display:none;'>Bank Name</th>
								<th style='display:none;'>A/C No</th>
								<th style='display:none;'>IFSC Code</th>
								<th style='display:none;'>Advance Deduction</th>
								<th>Transfer Salary</th>
								<th>Provided Purpose</th>
								<th style='display:none;'>Remark</th>
								<th style="width:230px;">Purpose</th>
							</tr>
						</thead>
						<tbody></tbody>
						<tfoot>
							<tr>
								<td colspan="6"></td>
								<td colspan="2" class="text-center"><button class="btn btn-success" id="salarydatabtn" onclick="createbook_excelsheetdata('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','hidden_loginname','pendingbookofsalarytbl', 'salarydatabtn');">Create Salary Book</button></td>
							</tr>
						</tfoot>
					</table>
				</div>
			</details>
		</div>
	</div>
</div>
<div id="requestcontainerdiv" style="display:none;">
	<div class="row mt-4 mb-4">
		<!-- <div class="col-md-12 text-center">
			<h4>SALARY REQUEST OF EMPLOYEE</h4>
		</div> -->
	</div>
	<div class="row">
		<div class="col-md-12">
			<button class="btn btn-sm btn-deepblue m-2 all-closedetailtag pull-right">Close All</button>
			<button class="btn btn-sm btn-deepblue m-2 all-opendetailtag pull-right">Open All</button>
		</div>
	</div>
	<div class="row subprojectdiv" id="subprojectdiv">
		<div class="col-md-12">
			<label>Choose the subproject</label>
			<select class="form-control" id="salary_subproject"></select>
		</div>
	</div>
	<details open id="frmacdetails">
		<summary>Choose Bank</summary>
		<div class="row fromaccountdiv">
			<div class="col-md-12">
				<label>From Account<sup><i class="fa fa-star" style="font-size: 6px; color:red"></i></sup></label>
				<select id="salary_fromaccount" class="form-control"></select>
			</div>
		</div>
	</details>
	<details open id="transdetails"  style="display: <?php echo ($subaction=="postpayment"?"block":"none"); ?>;">
		<summary>Bank Transaction Details</summary>
		<div class="row  mdtdiv modeoftransferdiv">
			<div class="col-md-12">
				<label>Mode of Transfer</label>
				<select id="salary_modeofpayment" class="form-control"></select>
			</div>
		</div>
		<div class="row mdtdiv transactiondatediv">
			<div class="col-md-12">
				<label><b id="chqnolbl">Transaction/Cheque Date?</b></label>
				<input type="date" class="form-control" id="salary_transactiondate" max="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" />
			</div>
		</div>
		<div class="row mdtdiv transactionnodiv">
			<div class="col-md-12">
				<label><b id="chqnolbl">Transaction/Cheque number?</b></label>
				<input type="text" class="form-control" id="salary_transactionno" value="" />
			</div>
		</div>
		<div class="col-md-12">
  		<label>Upload payment receipt (prefered)</label>
  		<input type="file" id="salary_paymentreceiptfile" class="form-control" accept="image/* capture=camera" />
  	</div>
	</details>
	
	<details open id="paytopluspurposedetails">
		<summary>Payto & Purpose Detial</summary>
		<div class="row">
			<div class="col-md-12">
				<label>Payto Name <sup><i class="fa fa-star" style="font-size: 6px; color:red"></i></sup></label>
				<span class="float-right m-2 mr-4 ml-4" onclick='request_paytofrommaster_withpaytoid("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","salarypaytoid","","")' style="cursor: pointer; color: green;"> <i class=" fa fa-sync"></i> Refresh</span>
				<i class="fa fa-plus-circle" id="opennewemployeemodal_forsalary" style="cursor: pointer;"></i>
				<select id="salarypaytoid" class="form-control select2" style="width: 100%;"></select>
			</div>
		</div>
		<div class="row" id="slr_subcatdivid">
		  <div class="col-md-12">
		    <label>Purpose sub category</label>
		    <select class="form-control select2 purposesubcategoryslt" style="width: 100%;" id="salary_purposesubcategory" onchange="request_purposecodefrommaster('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','<?php echo $ppcatid; ?>',$(this).val(),'','salary_purposecode','slr_ppcodedivid');"></select>
		  </div>
		</div>
		<div class="row" id="slr_ppcodedivid1">
		  <div class="col-md-12">
		    <label>Purpose<sup><i class="fa fa-star" style="font-size: 6px; color:red"></i></sup></label>
				<span class="float-right m-2 refeshpaytolist" onclick="request_purposecodefrommaster('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','<?php echo $ppcatid; ?>',$('#salary_purposesubcategory').val(),'','salary_purposecode','slr_ppcodedivid');" style="cursor: pointer; color: green;"> <i class=" fa fa-sync"></i> Refresh</span>
		    <select class="form-control select2 purposecodeslt" style="width: 100%;" id="salary_purposecode"></select>
		  </div>
		</div>
	</details>
	<details open id="paymentdetails">
		<summary>Amount</summary>		
		<div class="row">
			<div class="col-md-12">
				<label>Salary Year</label>
				<select id="salary_year" class="form-control"></select>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<label>Salary Month</label>
				<select id="salary_month" class="form-control"></select>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<label>How Many Days Salary</label>
				<input type="text" id="salarydays" class="form-control autocalsalary" />
			</div>
		</div>
		<div class="row amounttopaydiv">
			<div class="col-md-12">
				<label>How Much Salary</label>
				<input type="text" id="salary_amount" onkeypress="return isNumberKey(event)" class="form-control autocalsalary" />
			</div>
		</div>
	</details>	
	<details open id="descriptionremark">
		<summary>Documents, Description & Other</summary>
		<div class="row paymentduedatediv d-none">
	    	<div class="col-md-12">
	    		<label>Payment Due Date</label>
	    		<input type="date" class="form-control" name="salary_paymentduedate" id="salary_paymentduedate" value="<?php echo date('Y-m-d'); ?>" />
	    	</div>
	  </div>
		<div class="row">
			<div class="col-md-12">
				<label>Remark</label>
				<textarea id="salary_remark" rows="4" style="resize:none; width: 100%;"></textarea>
			</div>
		</div>
		<div class="row">
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
		<div class="row" style="display: <?php echo ($dz==1?"block":"none"); ?>;">
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
			<div class="row" style="display: <?php echo ($dz?"none":"block"); ?>;">
			  	<div class="col-md-12">
				    <label></label>
				    <div class='form-control'><input id="importfile4" type='file' accept="image/*; capture=camera" /><button class="btn-sm pull-right btnshow" style="border-width: thin;" onclick="showPreview_document(document.getElementById('importfile4'), 'blob');">Show Preview</button></div>
			   	</div>
			</div>
		</div>
	</details>
	
	<div class="row mt-4">
		<div class="col-md-4"></div>
		<div class="col-md-4 text-center">
			<button id="savesalaryrequest" class="btn btn-success" onclick='savetodb_requestdataforsalary("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","salary_subproject","salary_fromaccount","salarypaytoid", "salary_amount", "salary_modeofpayment", "salary_transactionno", "salary_transactiondate", "salary_paymentreceiptfile", "purposecategory", "salary_purposesubcategory", "salary_purposecode","salary_year","salary_month","salarydays", "salary_remark","hidden_loginname","importfile4","audioPlayback","paymenttypeselection","savesalaryrequest","hidden_attpath_fromemail","hidden_attsno_fromemail","hidden_emailsno_fromemail","updatecategorized","hidden_instructionid","salary_paymentduedate");'>Save Payment Request</button>
		</div>
		<div class="col-md-4"></div>
	</div>
</div>
<script type="text/javascript">
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
// if(alrd_bankaccountid){
// 	$(".hideifalready").hide();
// 	$("#uploadsheetcontainerdiv").hide();
// 	$("#requestcontainerdiv").show();
// }

if(alrd_likelypayto){
	 $('#salarypaytoid').before('<br/><span style="color:red;">We believe you have payto : '+alrd_likelypayto+'</span>');
}

var now = new Date().getTime();
var microseconds = now;
var lastFourDigits = microseconds.toString().slice(-4);

// mdtdiv
if(alrd_bankdbsno){
	$(".modeoftransferdiv").hide();
	if(alrd_transactionno){
		$("#salary_transactionno").val(alrd_transactionno);
		$(".transactionnodiv").hide();
	}else{
		$("#salary_transactionno").val("AG-"+lastFourDigits+"-"+alrd_transactiondate.replace(/-/g,''));
		$(".transactionnodiv").hide();
	}
	if(alrd_transactiondate){
		$("#salary_transactiondate").val(alrd_transactiondate);
		$(".transactiondatediv").hide();
	}

	if(alrd_transactionamount){
		$("#salary_amount").val(alrd_transactionamount);
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
			$("#salary_subproject").append("<option value='' disabled selected>Choose sub-project name</option>");
		}else{
			$(".subprojectdiv").hide();
		}
		for (var i = 0; i < res.length; i++) {
			$("#salary_subproject").append("<option value='"+res[i].subprojectid+"'>"+res[i].subprojectname+"</option>");
		}
	}
});
getdata_forreconsturctuctsalarydata("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","pendingbookofsalarytbl");

var dwnforsheetbtn="";
var rooturl = $("#hidden_rooturl").val();
excelsheetfunctioninfo('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid','salarysheet',function(ress){
  if(ress.length>0){
    // console.log(ress);
    var fileurl="";
    dwnforsheetbtn ="";
    for(var j=0; j<ress.length; j++){
      fileurl=rooturl+'attachementfile/formatedexcelsheet/'+ress[j].function_name+'.xls';
      dwnforsheetbtn = '<a href="'+fileurl+'" class="text-info" target="_blank">Example salary sheet format</a>';
    }
  }else{
    dwnforsheetbtn='<span style="color:red;">Exaple salary sheet format not defined</span>';
  }
  $("#formatsheetdiv").append(dwnforsheetbtn);
});

$(document).on("click", "input[name='doyoudocumentradio']", function()
{
	if($(this).val()=='yes'){
		$("#dodocdiv").show();			
	}else{
		$("#dodocdiv").hide();			
	}
});

var isuplosslaction=	"<?php echo $sltyp; ?>";
if(isuplosslaction=="excelsalary"){
	$("#uploadsheetcontainerdiv").show();
	$("#requestcontainerdiv").hide();
}else{
	$("#uploadsheetcontainerdiv").hide();
	$("#requestcontainerdiv").show();
}

$(document).on("click", "input[name='isuploadsalarysheet']", function(){
	if($(this).val()=="yes"){
		$("#uploadsheetcontainerdiv").show();
		$("#requestcontainerdiv").hide();
	}else{
		$("#uploadsheetcontainerdiv").hide();
		$("#requestcontainerdiv").show();
	}
});
request_paytofrommaster_withpaytoid("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","salarypaytoid","",alrd_likelypayto);
// request_employeelistforsalary('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','all','salarypaytoid');

yearlistwithlastyear_dropdown("salary_year");
monthlistofyear_dropdown("salary_month");
request_purposesubcategoryfrommaster('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','onlysalary','salary','salary_purposesubcategory','slr_subcatdivid');

// request_purposecodefrommaster_markfromnote4('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','<?php echo $ppcatid; ?>','salary','','salary_purposecode');
getaccountdetailsofproject_frmaster("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","hidden_loginid","salary_fromaccount",alrd_bankaccountid);

modeoftransferfrommaster_todropdown("hidden_baseurl","Online","salary_modeofpayment");
$(document).on("click", "#checktogetcategorywise", function(){
		if($("#checktogetcategorywise").is(":checked")){
			$("#prps_subcategorydiv").show();
			$("#spe_purposecode").prop("selectedIndex", 0);
		}else{
			$("#prps_subcategorydiv").hide();
			request_purposecodefrommaster_markfromnote4('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','<?php echo $ppcatid; ?>','','','salary_purposecode');
		}
	});
$("#importfile4").on("change", function(){
	$("#salary_attachments").css("height", "350px");    
});
$(document).on("click", "#opennewpaytomodal_forexp", function(){	
	 $("#newpaytodivid").load("<?php echo $rooturl; ?>expenditure_expencessheet/newpayto_authorization.php", function(){
		$("#newpaytodetails_modal").modal("show");
		$("#newpaytodetails_modal").css("z-index","9999");
    });
});
$(document).on("click", "#opennewemployeemodal_forsalary", function(){
	 $("#newemployeedivid").load("<?php echo $rooturl; ?>expenditure_expencessheet/employee_registration.php", function(){
		$("#newemployeedetails_modal").modal("show");
    });
});
$(document).on("click", "#opennewpurposemodal_forexp", function(){
		 $("#newpurposedivid").load("<?php echo $rooturl; ?>expenditure_expencessheet/purpose_addnew.php", function(){
			$("#newpurposedetails_modal").modal("show");
			$("#newpurposedetails_modal").css("z-index","9999");
	    });
	});
document.getElementById('importfile4').addEventListener('change', function() {
	$("#salary_attachments").show();
  var file = this.files[0];
  if (file) {
    var reader = new FileReader();
    reader.onload = function(event) {
      var imageData = event.target.result;
      // Set iframe src to the data URL of the image
      document.getElementById('salary_attachments').src = imageData;
    };
    reader.readAsDataURL(file);
  }else{
  	// document.getElementById('salary_attachments').src ='';
  }
});
</script>