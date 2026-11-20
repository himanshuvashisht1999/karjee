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
$mdid ="";
if(isset($_GET['mdid'])){
	$mdid = $_GET['mdid'];
}
$dddr = loginmodulesdetails_frommaster($authconn,$companyid,$subcompanyid,$projectid,"moduleid",$mdid);
if(count($dddr)>0){
	$groupid = $dddr[0]['groupid'];
}
?>
<div class="container p-2 mt-2 bg-light">
	<div class="row mt-4 mb-4">
		<div class="col-md-12">
	  		<a href="<?php echo $rooturl; ?>admin_dashboard.php?k=<?php echo $key; ?>&grpid=<?php echo $groupid; ?>&mdid=<?php echo $mdid; ?>" class="btn btn-sm btn-deepblue mt-2 mb-2 pull-right backtohome">Back to Home</a>
		</div>
		<div class="col-md-12 text-center">
			<h4><b class="uppercase">Already Paid - Payment Instructions </b></h4>
			<h6>One Payment - One Instruction</h6>
		</div>
		
	</div>
	<div class="row mt-4 d-none" style="background-color: #a8ab82;">
		<div class="col-md-6 text-center">
			<label class="m-2"><input type="radio" name="paymenttype_voice" value="Pre-Payment" /> Direction for Payment (voice instruction) </label><br/>
		</div>
		<div class="col-md-6 text-center">
			<label class="m-2"><input type="radio" name="paymenttype_voice" value="Post-Payment" checked /> Already paid (voice instruction)</label>
		</div>
	</div>
	<div class="row mt-4 subprojectdiv" id="subprojectdiv">
		<div class="col-md-12">
			<label class="">Choose the subproject</label>
			<select class="form-control" id="postvoice_subproject"></select>
		</div>
	</div>
	<div class="p-4 mt-4" style="background-color: #c7d3bf">
		<div class="row">
			<div class="col-md-12">
				<label>Voice Recording</label>
				<div class='container mt-2 voicerecorder' id='postinstrecording'> </div>
			</div>
			
			<!-- <div class="col-md-12">
					<button class="btn-success btn-sm recording-btn mr-2" id="startRecord">&#9899;</button>
			    <button class="btn-danger btn-sm recording-btn" id="stopRecord" disabled>&#11035;</button>
		  </div>
		  <div class="col-md-12 mt-2 ">
		  	<div id="recordingTime" style="display: none;"><span id="timeElapsedd">0</span> s</div>
		    <audio controlsList="nodownload" id="audioPlayback" class="form-control audioPlayback" controls></audio>
			</div> -->
		</div>
	</div>
	<div class="p-4 mt-4" style="background-color: #e0dab8;">
		<div class="row">
			<div class="col-md-12">
				<label class="">Amount Paid <sup><i class="fa fa-star" style="font-size: 6px; color:red"></i></sup></label>
				<input type="text" class="form-control" onkeypress="return isNumberKey(event)" style="line-height: 1.85;" id="postvoice_requestamount" />
			</div>
		</div>
		<div class="row mt-4">
			<div class="col-md-12 mt-2">
				<label><input type="radio" name="accountselectionvoice" value="accountfrommaster" checked /> Select from master</label>
				<i class="fa fa-plus-circle" onclick="openModalForAddNewBank('hidden_rooturl');" style="font-size: 18px; margin:2px; color:#1A5319; cursor:pointer;"></i>

				<span onclick="request_interbankpayto('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','account_voice');" style="margin:2px; color:#1A5319; cursor:pointer; float:right;"> <i class="fa fa-sync"></i> Refresh</span>

				<label><input type="radio" name="accountselectionvoice" value="accountmanual" /> Write the Bank Name</label>
			</div>
			<div class="col-md-12 mt-2">
				<select id="account_voice" class="form-control select2"></select>
				<input type="text" id="account_voice_manual" class="form-control"  style="display:none;" />
			</div>
		</div>
	</div>
	<div class="p-4 mt-4" style="background-color: #7c9a92;">
		<div class="row">
			<div class="col-md-12 mt-2" id="paytofrommasterdiv">
				<label><input type="radio" name="paytoselectionvoice" value="paytofrommaster"  checked /> Select To Whom Paid From Master </label>
				<i class="fa fa-plus-circle" onclick="openModalForAddNewPayto('hidden_rooturl');" style="font-size: 18px; margin:2px; color:gold; cursor:pointer;"></i>
				<span onclick='request_paytofrommaster_withpaytoid("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","paytoname_voice","","");' style="margin:2px; color:gold; cursor:pointer; float:right;"> <i class="fa fa-sync"></i> Refresh</span>
				
				<label><input type="radio" name="paytoselectionvoice" value="paytomanual" />Write to Whom Paid</label>

			</div>
			<div class="col-md-12 mt-2">
				<select id="paytoname_voice" class="form-control select2"></select>
				<input type="text" id="paytoname_voice_manual" class="form-control"  style="display:none;" />
			</div>
		</div>
	</div>
	
	<div class="p-4 mt-4" style="background-color: #e0dab8;">
		<div class="row">
			<div class="col-md-12 mt-2">		
				<label><input type="radio" name="purposeselectionvoice" value="purposefrommaster" checked /> Select Purpose From Master</label>
				<label><input type="radio" name="purposeselectionvoice" value="purposemanual" /> Write the Purpose Name</label>
			</div>
		</div>
		<div class="row mt-2">
			<div class="col-md-12 mt-2">
				<select id="purpose_voice" class="form-control select2"></select>
				<input type="text" id="purpose_voice_manual" class="form-control" style="display:none;" />
			</div>
		</div>
	</div>
	<div class="p-4 mt-4" style="background-color: #c7d3bf;">
		<div class="row">
			<div class="col-md-6 mt-2">
				<label class="">Take Upload Photo of document (optional)</label>
				<input type="file" id="importfile" class="form-control" accept="image/* capture=camera" />
			</div>
			<div class="col-md-6 mt-2">
				<label class="">Take Upload the Payment Recipt (optional)</label>
				<input type="file" id="importscreenshotfile" class="form-control" accept="image/* capture=camera" />
			</div>			    			    	
		</div>
		<div class="row mt-2">
			<div class="col-md-6">
				<label class="">Transaction Number <sup><i class="fa fa-star" style="font-size: 6px; color:red"></i></sup></label>
				<input type="text" class="form-control" onkeypress="return isNumberKey(event)" style="line-height: 1.85;" id="ptransctionno" />
			</div>
			<div class="col-md-6">
				<label class="">Transaction Date <sup><i class="fa fa-star" style="font-size: 6px; color:red"></i></sup></label>
				<input type="date" class="form-control" style="line-height: 1.85;" id="ptransctiondate" value="" />
			</div>
		</div>
	</div>
	<div class="p-4 mt-4" style="background-color: #e0dab8;">
		<div class="row">
			<div class="col-md-12">
				<label>Remarks <sup><i class="fa fa-star" style="font-size: 6px; color:red"></i></sup></label>
				<textarea rows="3" style="width:100%" id="remarkofinstruction"></textarea>
			</div>
		</div>
	</div>
	
	<div class="row mt-4 mb-4">
		<div class="col-md-12 text-center">
			<button class="btn btn-info savevoicebtn" id="savevoiceinstructionpostbtn" onclick="upload_voicerecording_post('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid','postvoice_subproject','paymenttype_voice','postinstrecording','importfile','importscreenshotfile','postvoice_requestamount','paytoname_voice','paytoname_voice_manual','purpose_voice','purpose_voice_manual','account_voice','account_voice_manual','ptransctionno','ptransctiondate','remarkofinstruction','hidden_loginid','savevoiceinstructionpostbtn');">Save This Transaction</button>
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

	allaccountswithcallback('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid', function(res)
	{
		// console.log(res);
		if(res.length>0){
			$("#account_voice").empty();
			for (var i =0; i<res.length; i++) {
				if(res.length>1){
					$("#account_voice").append("<option value='' disabled selected>Choose bank name</option>");
				}
				for (var i = 0; i < res.length; i++) {
					$("#account_voice").append("<option value='"+res[i].accid+"'>"+res[i].accname+" - "+res[i].accno+"</option>");
					if(res.length==1){
						$("#account_voice").val(res[i].accid).trigger("change");
					}
				}
			}
		}
	});

	getallpurposewithprojectidlist('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid',function(res)
	{
			if(res.length>0){
				if(res.length>1){
					$("#purpose_voice").append("<option value='' disabled selected>Choose purpose name</option>");
				}
				for (var i = 0; i < res.length; i++) {
					$("#purpose_voice").append("<option value='"+res[i].purposecode+"'>"+res[i].purpose+"</option>");
				}
			}
	});
	
	request_paytofrommaster_withpaytoid("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","paytoname_voice","","");

	$(document).on("click", "input[name='accountselectionvoice']", function () {
	    const selectedVal = $("input[name='accountselectionvoice']:checked").val();
	    $("#account_voice").val(null).trigger("change").prop("disabled", true);
	    $("#account_voice_manual").val('').prop("disabled", true);
	    $("#account_voice").next(".select2-container").hide(); // Hides select2 UI
	    $("#account_voice_manual").hide();
	    if (selectedVal === "accountmanual") {
	        $("#account_voice_manual").prop('disabled', false).show();
	    } else if (selectedVal === "accountfrommaster") {
	        $("#account_voice").prop('disabled', false);
	        $("#account_voice").next(".select2-container").show();
	    }
	});

	$(document).on("click", "input[name='paytoselectionvoice']", function () {
	    const selectedVal = $("input[name='paytoselectionvoice']:checked").val();
	    $("#paytoname_voice").val(null).trigger("change").prop("disabled", true);
	    $("#paytoname_voice_manual").val('').prop("disabled", true);
	    $("#paytoname_voice").next(".select2-container").hide(); // Hides select2 UI
	    $("#paytoname_voice_manual").hide();
	    if (selectedVal === "paytomanual") {
	        $("#paytoname_voice_manual").prop('disabled', false).show();
	    } else if (selectedVal === "paytofrommaster") {
	        $("#paytoname_voice").prop('disabled', false);
	        $("#paytoname_voice").next(".select2-container").show();
	    }
	});



	$(document).on("click", "input[name='purposeselectionvoice']", function () {
	    const selectedVal = $("input[name='purposeselectionvoice']:checked").val();
	    $("#purpose_voice").val(null).trigger("change").prop("disabled", true);
	    $("#purpose_voice_manual").val('').prop("disabled", true);
	    $("#purpose_voice").next(".select2-container").hide(); // Hides select2 UI
	    $("#purpose_voice_manual").hide();
	    if (selectedVal === "purposemanual") {
	        $("#purpose_voice_manual").prop('disabled', false).show();
	    } else if (selectedVal === "purposefrommaster") {
	        $("#purpose_voice").prop('disabled', false);
	        $("#purpose_voice").next(".select2-container").show();
	    }
	});
	$('.select2').select2();
	$('.select2-selection').css('border-radius','0px');
	$('.select2-container').children().css('border-radius','0px');
	$('.select2-selection').css('height','42px');
</script>