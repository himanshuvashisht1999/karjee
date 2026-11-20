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
<div class="row mt-2 subprojectdiv" id="subprojectdiv">
	<div class="col-md-12">
		<label><?php echo $subprojectid!=""?"Sub-project name":"Choose the Sub-project"; ?></label>
		<select class="form-control" id="subproject"></select>
	</div>
</div>

<div class="row">
	<div class="col-md-4 mt-2">
		<label>TDS deducted Month</label>
		<select class="form-control" style="width: 100%;" id="tdsdeductedmonth" onchange="tdsdeducted_type('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid','subproject','tdsdeductedmonth','tdsdeductedcateory');"></select>
	</div>
	<div class="col-md-4 mt-2">
		<label>TDS category</label>
		<select class="form-control" style="width: 100%;" id="tdsdeductedcateory" onchange="tdsdeducted_percent('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid','subproject','tdsdeductedmonth','tdsdeductedcateory','tddeductedpercent')"></select>
	</div>
	<div class="col-md-4 mt-2">
		<label>TDS(%)</label>
		<select class="form-control" style="width: 100%;" id="tddeductedpercent"></select>
	</div>
</div>
<div class="row mt-4">
	<div class="col-md-12 text-center">
		<button class="btn btn-info" style="display:none;" id="gettdsdatabtn" onclick="gettdsdeducted_data('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid','subproject','tdsdeductedmonth','tdsdeductedcateory','tddeductedpercent','hidden_loginname','gettdsdatabtn','tdsdataview');">Get Deducted TDS Details</button>
	</div>
</div>
<div class="row mt-2" id="tdsotherinfodiv" style="display:none;">
  <div class="col-md-4 mt-2">
    <label>Purpose Name</label>
    <select class="form-control purposecodeslt" style="width: 100%;" id="tds_purposecode"></select>
  </div>
	<div class="col-md-4 mt-2">
		<label>Mode of Transfer</label>
		<select class="form-control"  style="width: 100%;" id="tds_modeofpayment"></select>
	</div>
  	<div class="col-md-4 mt-2">
  		<label>Payment Due Date</label>
  		<input type="date" class="form-control" name="tds_paymentduedate" id="tds_paymentduedate" max="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" />
  	</div>
</div>

<div class="row mt-2" style="display:none;">
	<div class="col-md-4 mt-2">
		<p><label>Late fee is applicable for these tds request</label></p>
		<label><input type="radio" name="latefeeapplicatble" value="yes" /> YES</label>
		<label><input type="radio" name="latefeeapplicatble" value="no" /> NO</label>
	</div>
	<div class="col-md-4 mt-2 latefeehidediv" style="display:none;"><br/>
		<label>Late Fee (In %)</label>
		<input type="text" class="form-control" oninput="isFloatNumber(event, this);" name="latefee_percent" id="latefee_percent" />
	</div>
	<div class="col-md-4 mt-2 latefeehidediv" style="display:none;"><br/>
		<label>Late Fee Amount (approx)</label>
		<input type="text" class="form-control" oninput="isFloatNumber(event, this);" name="latefee_amount" id="latefee_amount" />
	</div>
</div>

<div class="row mt-2">
	<div class="col-md-12">
		<div id="tdsdataview"></div>
	</div>
</div>
<script type="text/javascript">


$("input[name='latefeeapplicatble']").on('click', function(){
	if($(this).val()=="yes"){
		$(".latefeehidediv").show();
	}else{
		$(".latefeehidediv").hide();
		$("#latefee_percent").val('');
		$("#latefee_amount").val('');
	}
});
$("#latefee_percent").blur(function(){
	var percentval = $(this).val();
	var ttlrqstamount = $("#total_emp_contribution").text();
	var ttllatefee = parseInt(ttlrqstamount)*parseInt(percentval)/100;
	$("#latefee_amount").val(ttllatefee);
});

var now = new Date().getTime();
var microseconds = now;
var lastFourDigits = microseconds.toString().slice(-4);

var subprjid = "<?php echo $subprojectid; ?>";
subprojectdetails_callback("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid",function(res){
		if(res.length>0){
			if(res.length>1){
				$(".subprojectdiv").show();
				$("#subproject").append("<option value='' disabled selected>Choose sub-project name</option>");
			}else{
				$(".subprojectdiv").hide();
			}
			for (var i = 0; i < res.length; i++) {
				$("#subproject").append("<option value='"+res[i].subprojectid+"'>"+res[i].subprojectname+"</option>");
			}
			if(subprjid && res.length>1){
				$("#subproject").val(subprjid).trigger("change");
			}
		}
});
setTimeout(function() {
        // Your code here
	tdsdeducted_durationlist("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid","subproject","monthlist","","tdsdeductedmonth");
}, 1000); // 2000 milliseconds = 2 seconds


request_purposecodefrommaster('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','<?php echo $ppcatid; ?>','only_tds_subcateory','','tds_purposecode');

modeoftransferfrommaster_todropdown("hidden_baseurl","Online","tds_modeofpayment");

$(document).on("click","#savetodbtdsrequestbtn", function(){
	savetodbtdsdeducted_request("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid",'subproject','tdsdeductedmonth','tdsdeductedcateory','tddeductedpercent','tds_purposecode','tds_modeofpayment','tds_paymentduedate','hidden_loginname','savetodbtdsrequestbtn');
});
</script>