<?php
 require('../header.php');
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
<div class="container pt-4 mt-2" style="background-color: rgb(3, 26, 64, 0.5);">
	<div class="row mt-4 mb-4">
		<div class="col-md-12  text-center">
			<h3><b class="text-warning">VOICE INSTRUCTION TO PAY</b></h3>
			<h5 class="text-warning">One Payment - One Instruction</h5>
		</div>
		<div class="col-md-12">
	      <a href="<?php echo $rooturl; ?>admin_dashboard.php?k=<?php echo $key; ?>&grpid=<?php echo $groupid; ?>&mdid=<?php echo $mdid; ?>" class="btn btn-sm btn-deepblue mt-2 mb-2 pull-right backtohome">Back to Home</a>
	  </div>
	</div>
	<div class="row mt-2" style="background-color: #a8ab82;">
  	<div class="col-md-6 text-center">
  		<label class="m-2"><input type="radio" name="paymenttype_voice" value="Pre-Payment" checked /> Direction for Payment (voice instruction) </label><br/>
  	</div>
  	<div class="col-md-6 text-center">
  		<label class="m-2"><input type="radio" name="paymenttype_voice" value="Post-Payment" />Already paid (voice instruction)</label>
  	</div>
  </div>
	
  <div class="container mt-2">
    <div id="voiceinstructionviewform"></div>
  </div>
</div>
<?php 
require('../footer.php');
?>
<script type="text/javascript">
var companyid = $("#hidden_companyid").val();
var companyname = $("#hidden_companyname").val();
var subcompanyid = $("#hidden_subcompanyid").val();
var projectid = $("#hidden_projectid").val();
var loginid = $("#hidden_loginid").val();
var rooturl = $("#hidden_rooturl").val();
var key=$("#hidden_key").val();
$("#voiceinstructionviewform").load(rooturl+"expenditure_expencessheet/voiceinstruction_pre_request.php?k="+key+"&subaction=postpayment&dz=2&subprojectid=");
$(document).on("click", "input[name='paymenttype_voice']", function () {
	$("#voiceinstructionviewform").html();
	if($(this).val()=="Pre-Payment"){
		$("#voiceinstructionviewform").load(rooturl+"expenditure_expencessheet/voiceinstruction_pre_request.php?k="+key+"&subaction=postpayment&dz=2&subprojectid=");
	}else if($(this).val()=="Post-Payment"){
		$("#voiceinstructionviewform").load(rooturl+"expenditure_expencessheet/voiceinstruction_post_request.php?k="+key+"&subaction=postpayment&dz=2&subprojectid=");
	}
});
</script>