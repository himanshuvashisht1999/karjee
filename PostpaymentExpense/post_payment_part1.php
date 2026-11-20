<?php
if(isset($_GET['dz'])){
  require('../connection.php');
  require('../functions/function_master.php');
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
$subaction="";
if(isset($_GET['subaction'])){
  $subaction = str_replace("-", "", strtolower($_GET['subaction']));
}
$countryid="";
if(isset($_GET['cntryid'])){
  $countryid = $_GET['cntryid'];
}
?>

<div class="container p-2 mt-2" id="postpaymentpart1div" style="background-color: rgb(3, 26, 64, 0.5);">
	<!-- <div class="row mt-4 mb-4">
		<div class="col-md-12  text-center">
			<h3><b class="uppercase text-warning">Already Paid VOICE INSTRUCTION</b></h3>
			<h5 class="text-warning">One Payment - One Instruction</h5>
		</div>
	</div> -->
	<div class="row mt-2">
  	<div class="col-md-12" style="display:none;">
  		<p class=" ">Payment Type</p>
  		<label class=" "><input type="radio" name="paymenttype_voice" value="Pre-Payment" /> Is instruction for request of payment (Pre Payment) </label><br/>
  		<label class=" "><input type="radio" name="paymenttype_voice" value="Post-Payment"  checked /> Is instruction for already paid (Post Payment)</label>
  	</div>
  </div>
  <div class="row mt-2 subprojectdiv" id="subprojectdiv">
		<div class="col-md-12">
			<label>Choose the subproject</label>
			<select class="form-control" id="postvoice_subproject"></select>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<button class="btn btn-sm btn-deepblue m-2 all-closedetailtag pull-right">Close All</button>
			<button class="btn btn-sm btn-deepblue m-2 all-opendetailtag pull-right">Open All</button>
		</div>
	</div>

	<details id="frmacdetails">
		<summary>Choose Bank</summary>
			<div class="row p-2">
		  	<div class="col-md-12">
	  			<div id="fromacdiv" class="p-1"></div>
		  	</div>
		  </div>
	</details>
	<details id="transdetails">
		<summary>Bank Transaction Details</summary>
	  <div class="row p-2">
	  	<div class="col-md-12">
	  		<label>Date of Payment <sup><i class="fa fa-star" style="font-size: 6px; color:red"></i></sup></label>
	  		<input type="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" style="line-height: 1.85;" id="ptransctiondate" />
	  	</div>
	  	<div class="col-md-12">
	  		<label>Bank Transaction No <sup><i class="fa fa-star" style="font-size: 6px; color:red"></i></sup><br/><span style="font-size: 0.8em; color: red;">(Atleast last 4 digits of transaction no)</span></label>	  		
	  		<input type="text" class="form-control" style="line-height: 1.85;" id="ptransctionno" placeholder="Enter bank transaction no" />
	  	</div>
	  	<div class="col-md-12">
	  		<label>Upload payment receipt (prefered)</label>
	  		<input type="file" id="paymentreceiptfile" class="form-control" accept="image/* capture=camera" />
	  	</div>			    			    	
	  </div>
	</details>
	<details id="paymentdetails">
		<summary>Amount</summary>
	  <div class="row pl-2 pr-2" style="background-image: linear-gradient(135deg, #fdfcfb 0%, #e2d1c3 100%);">
	  	<div class="col-md-12">
	  		<label>Amount Paid <sup><i class="fa fa-star" style="font-size: 6px; color:red"></i></sup></label>
	  		<input type="text" class="form-control" onkeypress="return isNumberKey(event)" id="ptamount" placeholder="Enter bank transfered amount" />
	  	</div>
	  	<div class="col-md-12">
	  		<label><?php echo $countryid=="104"?"Total GST":"Vat";  ?> Amount</label>
	  		<input type="text" class="form-control" onkeypress="return isNumberKey(event)" id="ptvatamount" placeholder="Enter vat/gst amount if applicable" />
	  	</div>
	  	<div class="col-md-12 mt-2" id="isuprcptgstdiv" style="display:none;">
	  		<label>Do you upload <?php echo $countryid=="104"?"gst":"vat";  ?> receipt </label><br/>
	  		<label><input type="radio" name="isuploadgstreceipt" value="now" /> Now</label>
	  		<label><input type="radio" name="isuploadgstreceipt" value="later" /> Later</label>
	  	</div>
	  	<div class="col-md-12 mt-2" id="vatreffiledivid" style="display:none;">
	  		<label><?php echo $countryid=="104"?"GST":"VAT";  ?> Invoice (prefered)</label>
	  		<input type="file" id="vatinvoicefile" class="form-control" accept="image/* capture=camera" />
	  	</div>
	  </div>
	</details>
	<details id="paytopluspurposedetails">
		<summary>Payto & Purpose Detial</summary>
	  <div class="row p-2">
	  	<div class="col-md-12">
	  		<label>Paid to Whom <sup><i class="fa fa-star" style="font-size: 6px; color:red"></i></sup></label>
	  		<input type="text" id="paytoname_pvoice" class="form-control" placeholder="type the name paid to whom" />
	  	</div>
	  	<div class="col-md-12">
	  		<label>Type the Purpose <sup><i class="fa fa-star" style="font-size: 6px; color:red"></i></sup></label>
	  		<input type="text" id="purpose_pvoice" class="form-control" placeholder="type the name of likely purpose name" />
	  	</div>
	  </div>
	  <div class="row p-2 bg-warning">
	  	<div class="col-md-12">
	  		<label>Description</label>
	  		<textarea id="description_pvoice" rows="2" class="form-control"></textarea>
	  	</div>
	  	<div class="col-md-12">
				<label>Voice Remark</label>
			</div>
			<div class="col-md-12">
					<button class="btn-success btn-sm recording-btn mr-2" id="startRecord">&#9899;</button>
			    <button class="btn-danger btn-sm recording-btn" id="stopRecord" disabled>&#11035;</button>
		  </div>
		  <div class="col-md-12">
		  	<div id="recordingTime" style="display: none;"><span id="timeElapsed">0</span> s</div>
		    <audio controlsList="nodownload" id="audioPlayback" class="form-control audioPlayback" controls></audio>
	  	</div>
		  <div class="col-md-12">
		  		<label>If you have any other document (prefered)</label>
		  		<input type="file" id="otherdocument" class="form-control" accept="image/* capture=camera" />
		  </div>
	  </div>	  	
	</details>
	<div class="row mt-4 p-2">
	  	<div class="col-md-12">
	  		<label><input type="checkbox" name="checkallpt1info" id="checkallpt1info" /> You have checked the box. Previewing details now!</label>
	  	</div>
	  	<div class="col-md-12 mt-4 text-center">
	  		<button class="btn btn-info" id="savept1btn" data-istaxreceiptupload="" onclick="savetodb_postpaymententrypart1('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid','postvoice_subproject','paymenttype_voice','fromaccountradio','ptransctiondate','ptransctionno','paymentreceiptfile','ptamount','ptvatamount','vatinvoicefile','paytoname_pvoice','purpose_pvoice','otherdocument','description_pvoice','audioPlayback','savept1btn');">Save Post Payment Entry</button>
	  	</div>
	</div>
</div>
<?php 
if(isset($_GET['dz'])){
}else{
	require('../footer.php');
}	
?>
<script type="text/javascript">
	$("#ptransctionno").on("blur", function(){
		let trnsaccid = $('input[name="fromaccountradio"]:checked').val();
		let trnsno = $("#ptransctionno").val();
		checkTransactionnumberexist('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid', trnsaccid, trnsno);
	});

	$(document).on("input", "#ptvatamount", function(){
		if($(this).val().length>0){
			$("#isuprcptgstdiv").show();
		}else{
			$("#isuprcptgstdiv").hide();
			$("#vatreffiledivid").hide();
			$("#savept1btn").data("istaxreceiptupload", "");
			$("input[name='isuploadgstreceipt']").prop("checked", false);
		}
	});
	$(document).on("click", "input[name='isuploadgstreceipt']", function(){
		var isuploagstrpt=$(this).val();
		$("#savept1btn").data("istaxreceiptupload", isuploagstrpt);
		if(isuploagstrpt==='now'){
			$("#vatreffiledivid").show();
		}else{
			$("#vatreffiledivid").hide();
		}
	});

	subprojectdetails_callback("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid",function(res){
			if(res.length>0){
				if(res.length>1){
					$(".subprojectdiv").show();
					$("#postvoice_subproject").append("<option value='' disabled selected>Choose sub-project name</option>");
				}else{
					$(".subprojectdiv").hide();
				}
				for (var i = 0; i < res.length; i++) {
					$("#postvoice_subproject").append("<option value='"+res[i].subprojectid+"'>"+res[i].subprojectname+"</option>");
				}
			}
		});

	allaccountswithcallback('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid', function(res){
		console.log(res);
		if(res.length>0){
			for (var i =0; i<res.length; i++) {
				if(res.length==1){
					$("#frmacdetails").hide();
					$("#fromacdiv").append("<p class='mt-4'><label><input type='radio' name='fromaccountradio' value='"+res[i].accid+"' data-banknamewithacc='"+res[i].accname+" (***"+res[i].accno.slice(-4)+")' checked /> "+res[i].bankname+" (***"+res[i].accno.slice(-4)+")</label></p>");
					$('input[name="fromaccountradio"]').prop('checked', true);
				}else{
					$("#fromacdiv").append("<p class='mt-4'><label><input type='radio' name='fromaccountradio' data-banknamewithacc='"+res[i].accname+" (***"+res[i].accno.slice(-4)+")' value='"+res[i].accid+"' /> "+res[i].bankname+" (***"+res[i].accno.slice(-4)+")</label></p>");
				}
			}
		}
	});

	$("#checkallpt1info").on("click", function () {
    var containedivhtml = $("#postpaymentpart1div").html();
    if ($(this).is(":checked")) {
        $("#webpagebodyview_mdmodal").modal("show");
        $("#modalheadermd").html("Post Payment Part -1 Preview");

        var prvhtml = "";
        let lbl_bankwithaccname = $('input[name="fromaccountradio"]:checked').data("banknamewithacc")??'N/A';
        let lbl_isuploadgstreceipt = $('input[name="isuploadgstreceipt"]:checked').val()??'N/A';
        let lbl_dataofpayment = changeDateFormatYmd_dMY($('#ptransctiondate').val());
        let lbl_ptransctionno = $("#ptransctionno").val() || 'N/A';
        let lbl_ptamount = $("#ptamount").val() || '0';
        let lbl_ptvatamount = $("#ptvatamount").val() || '0';
        let lbl_paytoname_pvoice = $("#paytoname_pvoice").val() || 'N/A';
        let lbl_purpose_pvoice = $("#purpose_pvoice").val() || 'N/A';
        let lbl_description_pvoice = $("#description_pvoice").val() || 'N/A';

        prvhtml += `
        <details id="frmacdetails" style="background-color: #0093E9; background-image: linear-gradient(160deg, #0093E9 0%, #80D0C7 100%);" open>
            <summary style="background-image: linear-gradient(90deg, #e0c3fc 20%, #8ec5fc 70%);">Preview of Post Payment Details</summary>
            <div class="row" style="padding: 8px 64px;">
                <div class="col-md-12">
                    <label>Paid from Bank</label>
                    <h5>${lbl_bankwithaccname}</h5>
                </div>
                <div class="col-md-12">
                    <label>Date of Payment</label>
                    <h5>${lbl_dataofpayment}</h5>
                </div>
                <div class="col-md-12">
                    <label>Transaction No</label>
                    <h5>${lbl_ptransctionno}</h5>
                </div>
                <div class="col-md-12">
                    <label>Amount Paid</label>
                    <h5>${lbl_ptamount}</h5>
                </div>
                <div class="col-md-12">
                    <label><?php echo $countryid=="104"?"GS":"VAT";  ?> Amount</label>
                    <h5>${lbl_ptvatamount}</h5>
                </div>
        				<div class="col-md-12">
                    <label>Upload <?php echo $countryid=="104"?"GST":"VAT";  ?> Receipt Status</label>
                    <h5>${lbl_isuploadgstreceipt}</h5>
                </div>
                <div class="col-md-12">
                    <label>Paid to Whom</label>
                    <h5>${lbl_paytoname_pvoice}</h5>
                </div>
                <div class="col-md-12">
                    <label>Purpose</label>
                    <h5>${lbl_purpose_pvoice}</h5>
                </div>
                <div class="col-md-12">
                    <label>Description</label>
                    <h5>${lbl_description_pvoice}</h5>
                </div>
            </div>
        	<div class="col-md-12 mt-4 text-center">
        	<button class="btn btn-deepblue" onclick=\"closeModal('webpagebodyview_mdmodal')\">Go Back</button>
		  		<button class="btn btn-info" data-istaxreceiptupload="" onclick="savetodb_postpaymententrypart1('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid','postvoice_subproject','paymenttype_voice','fromaccountradio','ptransctiondate','ptransctionno','paymentreceiptfile','ptamount','ptvatamount','vatinvoicefile','paytoname_pvoice','purpose_pvoice','otherdocument','description_pvoice','audioPlayback','savept1btn');">Save Post Payment Entry</button>
		  	</div>
        </details>`;

        $("#webpagebodydivmd").html(prvhtml);
    } else {
        $("#webpagebodyview_mdmodal").modal("hide");
        $("#modalheadermd").html("");
        $("#webpagebodydivmd").html('');
    }
});

</script>