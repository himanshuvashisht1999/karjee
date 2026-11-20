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
?>
<div class="container1 p-2 mt-2" style="background-color: rgb(64,101,93);" >
	<div class="row mt-4 mb-4">
		<div class="col-md-12  text-center">
			<h3><b class="uppercase text-warning">Small Expenses Payment Details</b></h3>
			<!-- <h5 class="text-warning">Paid Through Credit Card</h5> -->
			<h4 class="uppercase bold mb-4">Company Name : <?php echo $projectname; ?></h4>
			<h6 class="uppercase bold mb-4">User Name : <?php echo $loginname; ?></h6>
		</div>
	</div>
</div>
<div class="container1 p-2 mt-2" style="background-color: rgb(64,101,93);">
	<div class="row mt-4 mb-4">
		<div class="col-md-6  text-center">
			<input type="radio" name="exptype" value="credit"> For Credit Card
		</div>
		<div class="col-md-6  text-center">
			<input type="radio" name="exptype" value="cash"> For Cash
		</div>
	</div>
</div>


<div class="container1 p-2 mt-2" id="div_credit" style="background-color: rgb(64,101,93); display: none;" >
	<div class="row mt-4 mb-4">
		<div class="col-md-12  text-center">
			<h3 class="h3class"><b class="uppercase text-warning">Credit Card Small Expenses Payment Details</b></h3>
			<!-- <h5 class="text-warning">Paid Through Credit Card</h5> -->
			<h4 class="uppercase bold mb-4">Company Name : <?php echo $projectname; ?></h4>
		</div>
	</div>
	<div class="row mt-2">
  	<div class="col-md-12" style="display:none;">
  		<p class=" ">Payment Type</p>
  		<label class=" "><input type="radio" name="paymenttype_crdp" value="Pre-Payment" /> Is instruction for request of payment (Pre Payment) </label><br/>
  		<label class=" "><input type="radio" name="paymenttype_crdp" value="Post-Payment"  checked /> Is instruction for already paid (Post Payment)</label>
  	</div>
  </div>
	<div class="row mt-2">
		<div class="col-md-12">
			<label class="classlable ">Credit Card User Name</label>
			<select id="creditcarduser_crdp" class="form-control"></select>
	  </div>
	</div>
	<div class="row mt-2" id="div_creditcard">
		<div class="col-md-12">
			<label class="">Credit Card</label>
			<select id="creditcardno_crdp" class="form-control"></select>
	  </div>
	</div>
	<!-- <div class="row mt-2" id="subprojectdiv" style="display:none;">
		<div class="col-md-12">
			<label class="">Sub Project Name</label>
			<select id="subprojectname_crdp" class="form-control"></select>
	  </div>
	</div> -->
	<div class="row mt-2 subprojectdiv" id="subprojectdiv">
    <div class="col-md-12">
        <label class="">Sub Project Name</label>
        <select class="form-control" id="subprojectname_crdp"></select>
    </div>
  </div>	
	<div class="row mt-2">
  	<div class="col-md-12">
  		<label class="">Date <sup><i class="fa fa-star" style="font-size: 6px; color:red"></i></sup></label>
  		<input type="date" class="form-control" style="line-height: 1.85;" id="requestdate_crdp" />
  	</div>
  </div>
  <div class="row mt-2">
  	<div class="col-md-12">
  		<label class="">Amount <sup><i class="fa fa-star" style="font-size: 6px; color:red"></i></sup></label>
  		<input type="text" class="form-control" onkeypress="return isNumberKey(event)" style="line-height: 1.85;" id="requestamount_crdp" />
  	</div>
  </div>
	<div class="row mt-2">
  	<div class="col-md-12">
  		<label class="">Attached document</label>
  		<input type="file" id="importfile_crdp" class="form-control" accept="image/* capture=camera" />
  	</div>
  </div>
  
  <div class="row mt-2" id="purposeradiodiv" style="display:none;">	    	
  	<div class="col-md-12">
  		<label class="">Purpose</label>
  		<div id="appenpurposeradio"></div>
  	</div>
  </div>
  <div class="row mt-2" id="purposedropdiv" style="display:none;">	    	
  	<div class="col-md-12">
  		<label class="">Purpose</label>
  		<select id="purposedrop_crdp" class="form-control select2" style="line-height:16px;"></select>
  	</div>
  </div>
  <div class="row mt-2" id="purposeinputdiv">
  	<div class="col-md-12">
  		<label class="">Purpose</label>
  		<input type="text" id="purpose_crdp" class="form-control" />
  	</div>
  </div>
  
  
  <div class="row mt-2" style="display:none;">
  	<div class="col-md-12">
  		<label class="">Paid to Whom?</label>
  		<input type="text" id="paytoname_crdp" class="form-control" />
  	</div>
  </div>
  <div class="row mt-4">
		<div class="col-md-12">
			<label class="">Voice Remark</label>
		</div>
		<div class="col-md-12">
				<button class="btn-success btn-sm recording-btn mr-2" id="startRecord">&#9899;</button>
		    <button class="btn-danger btn-sm recording-btn" id="stopRecord" disabled>&#11035;</button>
	  </div>
	  <div class="col-md-12 mt-2 ">
	  	<div id="recordingTime" style="display: none;"><span id="timeElapsed">0</span> s</div>
	    <audio controlsList="nodownload" id="audioPlayback_crdp" class="form-control audioPlayback" controls></audio>
  	</div>
  </div>
  <div class="row mt-2">	    	
  	<div class="col-md-12">
  		<label class="">Text Remark</label>
  		<textarea type="text" rows="3" id="textremark_crdp" class="form-control" style="width:100%;"></textarea>
  	</div>
  </div>
  <div class="row mt-4">
  	<div class="col-md-12 text-center">
  		<button class="btn btn-info" id="savesmallexpbtn" onclick="creditcardsmallexpense('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid','subprojectname_crdp','paymenttype_crdp','creditcarduser_crdp','creditcardno_crdp','requestamount_crdp','importfile_crdp','purpose_crdp','paytoname_crdp','audioPlayback_crdp','textremark_crdp','hidden_loginid','savesmallexpbtn','exptype','purposeradio','requestdate_crdp');" >Save & Send</button>
  		
  	</div>
  </div>
</div>
	
<?php 
require('../footer.php');
?>
<script type="text/javascript">
	var purposesearch="";
	$(document).on("change", "#purposedrop_crdp", function(){
		$("#purpose_crdp").val($(this).val());
	});
    subprojectdetails_callback("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid",function(res){
        if(res.length>0){
            if(res.length>1){
                $(".subprojectdiv").show();
                $("#subprojectname_crdp").append("<option value='' disabled selected>Choose sub-project name</option>");
            }else{
                $(".subprojectdiv").hide();
            }
            for (var i = 0; i < res.length; i++) {
                $("#subprojectname_crdp").append("<option value='"+res[i].subprojectid+"'>"+res[i].subprojectname+"</option>");
            }
           
        }
    });	
	$(document).on("click", "input[name='purposeradio']", function(){	
		if ($(this).val()=='') {			
			$('#purposedropdiv').show();
			purposelist_all("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid",'Credit Card',function(res){			
		$("#purposedrop_crdp").empty();	
		if(res.length>0){			
			$("#purposedropdiv").show();
			$("#purposedrop_crdp").append("<option value='' selected disabled>Choose purpose</option>");
			for(var i=0; i<res.length; i++){
					$("#purposedrop_crdp").append("<option data-purposename='"+res[i].purpose+"' value='"+res[i].purposecode+"'>"+res[i].purpose+"</option>");
			}
			if(res.length==1){
				$("#purposedrop_crdp").prop("selectedIndex", 1).trigger("change");
			}else{
				// $("#subprojectdiv").show();
			}			
		
		}


	});
		}else{
		
			$('#purposedropdiv').hide();
		}
		$("#purpose_crdp").val($(this).val());

		// var x=$("input[name='purposeradio']:checked").data("purposename");
		// alert(x);

	});
	$('input[name="exptype"]').change(function () {
		var selectedval = $('input[name="exptype"]:checked').val();
		if (selectedval=='credit') {
			$('#div_credit').show();
			$('.h3class').text('Credit Card Small Expenses Payment Details');
			$('.classlable').text('Credit Card User Name');
			$('#div_creditcard').show();
			purposesearch="Credit Card Small Expenses";
		}else if (selectedval=='cash') {
			$('#div_credit').show();
			$('.h3class').text('Cash Small Expenses Payment Details');
			$('.classlable').text('Cash User Name');
			$('#div_creditcard').hide();
			purposesearch="Small Expenses Refund";

		}
		purposelist_withlikelysubcategory("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid",purposesearch,function(res){
		// console.log(res);
		$("#purposedropdiv").hide();
		$("#purposeradiodiv").hide();
		$("#purposeinputdiv").show();
		$("#purposedrop_crdp").empty();
		$("#appenpurposeradio").empty('');
		if(res.length>0){			
			$("#purposeinputdiv").hide();
			if(res.length>1 && res.length<7){
				$("#purposeradiodiv").show();
				$("#purposedropdiv").hide();
				for(var i=0; i<res.length; i++){
						$("#appenpurposeradio").append("<label class='text-warning m-2'><input type='radio' name='purposeradio' data-purposename='"+res[i].purpose+"' value='"+res[i].purposecode+"' />"+res[i].purpose+" &nbsp;&nbsp;&nbsp;</label>&nbsp;&nbsp;<br/>");
				}
			}else{
				$("#purposeradiodiv").hide();
				$("#purposedropdiv").show();
				$("#purposedrop_crdp").append("<option value='' selected disabled>Choose purpose</option>");
				for(var i=0; i<res.length; i++){
						$("#purposedrop_crdp").append("<option data-purposename='"+res[i].purpose+"' value='"+res[i].purposecode+"'>"+res[i].purpose+"</option>");
				}
				if(res.length==1){
					$("#purposedrop_crdp").prop("selectedIndex", 1).trigger("change");
				}else{
					// $("#subprojectdiv").show();
				}
				$('.select2').select2();
				$('.select2-selection').css('border-radius','2px');
				$('.select2-container').children().css('border-radius','0px');
				$('.select2-selection').css('height','42px');
			}
		}else{
			$("#purposeinputdiv").show();
			// $("#purposedrop_crdp").append("<option value=''>Data not Available</option>");				
		}
		$("#appenpurposeradio").append("<label class='text-warning m-2'><input type='radio' name='purposeradio' data-purposename='' value='' />Other  &nbsp;&nbsp;&nbsp;</label>&nbsp;&nbsp;<br/>");
	});
	});

	

	// subprojectdetails_callback("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid",function(res){
	// 	$("#subprojectname_crdp").empty();
	// 	if(res.length>0){
	// 		$("#subprojectname_crdp").append("<option value='' selected disabled>Choose Sub Project</option>");
	// 		for(var i=0; i<res.length; i++){
	// 			$("#subprojectname_crdp").append("<option data-subprojectname='"+res[i].subprojectname+"' value='"+res[i].subprojectid+"'>"+res[i].subprojectname+"</option>");
	// 		}
	// 		if(res.length==1){
	// 			$("#subprojectname_crdp").prop("selectedIndex", 1).trigger("change");
	// 		}else{
	// 			$("#subprojectdiv").show();
	// 		}
	// 	}else{
	// 		$("#subprojectname_crdp").append("<option value=''>Data not Available</option>");				
	// 	}
	// });

	creditcarduser_callback("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid",function(res){
		// console.log(res);
		$("#creditcarduser_crdp").empty();
		if(res.length>0){
			$("#creditcarduser_crdp").append("<option value='' selected disabled>Credit Card User Name</option>");
			for(var i=0; i<res.length; i++){
				$("#creditcarduser_crdp").append("<option data-cardpersonname='"+res[i].username+"' value='"+res[i].userid+"'>"+res[i].username+"</option>");
			}
			// if(res.length==1){
			// 	$("#creditcarduser_crdp").prop("selectedIndex", 1).trigger("change");
			// }

		}else{
			$("#creditcarduser_crdp").append("<option value=''>Data not Available</option>");				
		}
	});

	$(document).on("change", "#creditcarduser_crdp", function(){
		creditcarddetial_callback("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid","creditcarduser_crdp",function(res){
			console.log(res);
			// creditcard
			$("#creditcardno_crdp").empty();
			if(res.length>0){
				$("#creditcardno_crdp").append("<option value='' selected disabled>Choose Credit Card</option>");
				for(var i=0; i<res.length; i++){
					// $("#creditcardno_crdp").append("<option data-cardbankname='"+res[i].bankname+"' data-cardvalidfrom='"+res[i].validfrom+"' data-cardvalidto='"+res[i].validto+"' value='"+res[i].cardnumber+"'>"+res[i].bankname+' ('+res[i].cardnumber+")</option>");
					$("#creditcardno_crdp").append("<option  value='"+res[i].cardnumber+"'>"+res[i].cardnumber+"</option>");
				}
				if(res.length==1){
					$("#creditcardno_crdp").prop("selectedIndex", 1).trigger("change");
				}

			}else{
				$("#creditcardno_crdp").append("<option value=''>Data not Available</option>");				
			}
		});
	});	
</script>