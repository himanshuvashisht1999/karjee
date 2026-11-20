<?php  
session_start();
require('header.php');
?>
	<div class="row" style="margin-top:64px;">
	  	<div class="col-md-12 text-center">
	      <h4 class=" uppercase bold"><b>UPLOAD EMPLOYEE ATTENDANCE DYAS OF MONTH</b></h4>
	   </div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<label class="">Choose attendance sheet</label>
			<!-- <span id="expsheetformatmsg" style="float:right; color:yellow;">Expense Sheet Format</span> -->
			<input type="file" name="files" id="expensefile" name="expensefile" class="form-control" multiple accept=".xlsx,.xls, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel"/>
		</div>
	</div>
	<div class="row mt-4">
		<div class="col-md-12 text-center">
			<button class="btn btn-success" id="updexpensesheetbtn">Upload to Attendance Sheet</button>
		</div>
	</div>
	<?php require('footer.php'); ?>
	<script type="text/javascript">
	// check_uploadexpensesheet_format('expsheetformatmsg');
	/*$("#updexpensesheetbtn").on('click', function(){
	   var form_data = new FormData();	   
	   // var ins = document.getElementById('expensefile').files.length;
		form_data.append("files", document.getElementById('expensefile').files[0]);
		var rooturl =  $("body").data("routurl");
		var companyid =  $("body").data("companyid");
		var subcompanyid =  $("body").data("subcompanyid");
		var projectid =  $("body").data("projectid");
		var loginid = $("#hidden_loginid").val();

		form_data.append('companyid', companyid);
		form_data.append('subcompanyid', subcompanyid);
		form_data.append('projectid', projectid);
		form_data.append('loginid', loginid);
	   $.ajax({
			type: "POST",	
			url: rooturl+"/modal/uploadexcelsheet_modal.php?action=uploadexpensesheet",
			data: form_data,
	      contentType: false,
	      processData: false,
	      cache: false,
	      dataType:'JSON',
			success: function(res)
			{
				// console.log(res);
				if(res.status=='Success'){
					alertmessage('success', res.message);
					window.location.reload();
				}else{
					alertmessage('error', res.message);
				}
			}
		});
	});*/
</script>