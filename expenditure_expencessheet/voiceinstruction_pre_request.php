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
<div class="container p-2 mt-2" style="background-color: rgb(3, 26, 64, 0.5);">
	<div class="row mt-4 mb-4">
		<div class="col-md-12  text-center">
			<h3><b class="uppercase text-warning">OWNER VOICE INSTRUCTION FOR PAYMENT (APPROVAL NOT REQUIRED)</b></h3>
			<h5 class="text-warning">One Payment - One Instruction</h5>
		</div>
		<div class="col-md-12">
	      <a href="<?php echo $rooturl; ?>admin_dashboard.php?k=<?php echo $key; ?>&grpid=<?php echo $groupid; ?>&mdid=<?php echo $mdid; ?>" class="btn btn-sm btn-deepblue mt-2 mb-2 pull-right backtohome">Back to Home</a>
	  </div>
	</div>
	<div class="row mt-2 d-none" style="background-color: #a8ab82;">
		<div class="col-md-6 text-center">
			<label class="m-2"><input type="radio" name="paymenttype_voice" value="Pre-Payment" checked /> Direction for Payment (voice instruction) </label><br/>
		</div>
		<div class="col-md-6 text-center">
			<label class="m-2"><input type="radio" name="paymenttype_voice" value="Post-Payment" />Already paid (voice instruction)</label>
		</div>
	</div>
	<div class="row mt-4 subprojectdiv" id="subprojectdiv">
		<div class="col-md-12">
			<label class="">Choose the subproject</label>
			<select class="form-control" id="prevoice_subproject"></select>
		</div>
	</div>
	<div class="row mt-4">
		<div class="col-md-12">
			<label class="">Voice Record Request</label>
		</div>
		<div class="col-md-12">
				<button class="btn-success btn-sm recording-btn mr-2" id="startRecord">&#9899;</button>
		    <button class="btn-danger btn-sm recording-btn" id="stopRecord" disabled>&#11035;</button>
	  </div>
	  <div class="col-md-12 mt-2 ">
	  	<div id="recordingTime" style="display: none;"><span id="timeElapsed">0</span> s</div>
	    <audio controlsList="nodownload" id="audioPlayback" class="form-control audioPlayback" controls></audio>
		</div>
	</div>
	<div class="row mt-2">
		<div class="col-md-6">
			<label class="">Amount To Pay <sup><i class="fa fa-star" style="font-size: 6px; color:red"></i></sup></label>
			<input type="text" class="form-control" onkeypress="return isNumberKey(event)" style="line-height: 1.85;" id="prevoice_requestamount" />
		</div>
		<div class="col-md-6 mt-2">
			<label class="">Take photo of document (optional)</label>
			<input type="file" id="importfile" class="form-control" accept="image/* capture=camera" />
		</div>
					    			    	
	</div>
	<div class="row mt-2">
		<div class="col-md-12 mt-2" id="paytofrommasterdiv">
			<label class="">Payto Name From Master</label>
			<label class=" text-sm text-warning"><input type="radio" name="paytoselectionvoice" value="paytomanual" /> <small>Enter manualy payto</small></label>
			<select id="paytoname_voice" class="form-control select2"></select>
		</div>			    			    	
		<div class="col-md-12 mt-2" id="paytomanualdiv" style="display:none;">
			<label class="">Payto Name Manual</label>
			<label class=" text-sm text-warning"><input type="radio" name="paytoselectionvoice" value="paytofrommaster" /> <small>choose from master payto</small></label>
			<input type="text" id="paytoname_voice_manual" class="form-control" />
		</div>
	</div>
	<div class="row mt-2">
		<div class="col-md-12 mt-2"  id="purposefrommasterdiv">		
			<label class="">Purpose From Master</label>
			<label class=" text-sm text-warning"><input type="radio" name="purposeselectionvoice" value="purposemanual" /> <small>Enter manualy purpose</small></label>
			<select id="purpose_voice" class="form-control select2"></select>
		</div>			    			    	
		<div class="col-md-12 mt-2"  id="purposemanualdiv" style="display:none;">
			<label class="">Purpose Manual</label>
			<label class=" text-warning"><input type="radio" name="purposeselectionvoice" value="purposefrommaster" /> <small>choose from master</small></label>
			<input type="text" id="purpose_voice_manual" class="form-control" />
		</div>
	</div>
	<div class="row mt-4 mb-4">
		<div class="col-md-12 text-center">
			<button class="btn btn-info savevoicebtn" id="savevoiceinstructionprebtn" onclick="upload_voicerecording('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid','prevoice_subproject','paymenttype_voice','audioPlayback','importfile','prevoice_requestamount','hidden_loginid','savevoiceinstructionprebtn','paytoname_voice','paytoname_voice_manual','purpose_voice','purpose_voice_manual');">Save & Send</button>
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
					$("#prevoice_subproject").append("<option value='' disabled selected>Choose sub-project name</option>");
				}else{
					$(".subprojectdiv").hide();
				}
				for (var i = 0; i < res.length; i++) {
					$("#prevoice_subproject").append("<option value='"+res[i].subprojectid+"'>"+res[i].subprojectname+"</option>");
				}
			}
		});

	allaccountswithcallback('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid', function(res){
		// console.log(res);
		if(res.length>0){
			$("#account_voice").empty();
			for (var i =0; i<res.length; i++) {
				if(res.length>1){
					$("#account_voice").append("<option value='' disabled selected>Choose purpose name</option>");
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

	getallpurposewithprojectidlist('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid',function(res){
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

$(document).on("click", "input[name='paytoselectionvoice']", function(){
	$("#paytoname_voice").prop("selectedIndex", 0);
	$("#paytoname_voice_manual").val('');
	if($("input[name='paytoselectionvoice']:checked").val()=="paytomanual"){
		$("#paytofrommasterdiv").hide();
		$("#paytomanualdiv").show();
	}else if($("input[name='paytoselectionvoice']:checked").val()=="paytofrommaster"){
		$("#paytofrommasterdiv").show();
		$("#paytomanualdiv").hide();
	}		
});

$(document).on("click", "input[name='purposeselectionvoice']", function(){
	$("#purpose_voice").prop("selectedIndex", 0);
	$("#purpose_voice_manual").val('');
	if($("input[name='purposeselectionvoice']:checked").val()=="purposemanual"){
		$("#purposefrommasterdiv").hide();
		$("#purposemanualdiv").show();
	}else if($("input[name='purposeselectionvoice']:checked").val()=="purposefrommaster"){
		$("#purposefrommasterdiv").show();
		$("#purposemanualdiv").hide();
	}		
});

$('.select2').select2();
	$('.select2-selection').css('border-radius','0px');
	$('.select2-container').children().css('border-radius','0px');
	$('.select2-selection').css('height','42px');
</script>