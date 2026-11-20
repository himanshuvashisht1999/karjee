<?php
$cid = "";
if(isset($_GET['cid'])){
	$cid = $_GET['cid'];
}
$sbcid = "";
if(isset($_GET['sbcid'])){
	$sbcid = $_GET['sbcid'];
}
$prjid = "";
if(isset($_GET['prjid'])){
	$prjid = $_GET['prjid'];
}

$excelfile="";
if(isset($_GET['excelfile'])){
	$excelfile=$_GET['excelfile'];
}
$pdffile="";
if(isset($_GET['pdffile'])){
	$pdffile=$_GET['pdffile'];
}
$excelfilpath ="";
if($excelfile){
	$excelfilpath ='sourcefile/'.$cid.'/'.$sbcid.'/'.$prjid.'/'.$excelfile;
}
$pdffilepath ="";
if($pdffile){
	$pdffilepath ='sourcefile/'.$cid.'/'.$sbcid.'/'.$prjid.'/'.$pdffile;
}

$filedbidval ="";
if(isset($_GET['filedbid'])){
	$filedbidval =$_GET['filedbid'];
}
// require('../header.php');
?>
<div class="row" style="margin-top:32px;">
  	<div class="col-md-12 text-center">
      <h4 class=" uppercase bold"><b>UPLOAD BANK STATEMENT</b></h4>
   </div>
</div>
<div class="row justify-content-center m-2">
	<div class="col-md-6">
		<select class="form-control" id="bankaccountname"></select>
	</div>
</div>
<div id="uploadcontainer" style="display:none;">
	<div class="row bg-light">
		<div class="col-md-12">
			<button class="btn btn-deepblue m-1 pull-right" id="bankkeymaster">Make Keys of Bank Statement If Missing</button>
		</div>
	</div>
	<?php if (!empty($excelfilpath) && file_exists($excelfilpath)): ?>
	<div class="row">
		<div class="col-md-12 mt-2">
				<label class="">Choose bank statement as Excel file</label>
	      <strong>Already uploaded:</strong>
	      <a href="<?php echo $excelfilpath; ?>" target="_blank">View / Download</a>
				<input type="file" name="files" id="bankstatementfile" name="bankstatementfile" class="form-control" multiple accept=".xlsx,.xls, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" value="" />
		</div>
	</div>
	<div class="row mt-4">
		<div class="col-md-12 text-center">
			<button class="btn btn-success" id="updbankstatementbtn">Upload Bank Statement</button>
		</div>
	</div>
	<?php endif; ?>
	<?php if (!empty($pdffilepath) && file_exists($pdffilepath)): ?>
	<div class="row">
		<div class="col-md-12 mt-2">
			<label class="">Choose bank statement in pdf</label>
      <strong>Already uploaded:</strong>
      <a href="<?php echo $pdffilepath; ?>" target="_blank">View / Download</a>
			<input type="file" name="pdffile" id="pdfbankstatementfileas" name="pdfbankstatementfileas" onchange="filepreview_Onchange(event, 'prvattid')" class="form-control" multiple accept=".pdf"/>
		</div>
	</div>
	<div class="row mt-2">
			<div class="col-md-6">
				<label class="">Start date of PDF</label>
				<input type="date" class="form-control" name="pdfstartdate" id="pdfstartdate" />
			</div>
			<div class="col-md-6">
				<label class="">End date of PDF</label>
				<input type="date" class="form-control" name="pdfenddate" id="pdfenddate" />
			</div>
	</div>
	<div class="row mt-4">
		<div class="col-md-12 text-center">
			<button class="btn btn-success" id="savepdfbtn">Upload PDF Statement</button>
		</div>
	</div>
	<?php endif; ?>
	<div class="mt-2" id="prvattid"></div>
</div>

<div class="modal" id="modelcheckbsuploaded" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 96%;" role="document">
      <div class="modal-content">
        <div class="modal-header bg-primary">
          <h4 class="modal-title">Check The Uploaded Bank Statement Block With PDF</h4>
        </div>
        <div class="modal-body bg-light" id="uncheckbankdata">
        	<div class="row">
        		<div class="col-md-12 text-danger">
        			<h4 class="font-weight-bold"> There is an unchecked block of the recently uploaded bank statement. Please check it. You cannot upload further any bank statements, until the block is left unchecked.</h4>
        		</div>
        	</div>
        	<div class="row">
        		<div class="col-md-6">
		        	<div class="row mt-2">
								<div class="col-md-12">
									<label class="text-dark">Opening Balance in Unchecked Block:</label>
									<label class="text-primary" name="excelopeningbalance" id="excelopeningbalance"> t1</label>
								</div>
							</div>
							<div class="row mt-2">
								<div class="col-md-12">
									<label class="text-dark">Date of Opening Balance in Unchecked Block:</label>
									<label class="text-primary" name="excelopeningbalancedate" id="excelopeningbalancedate"> t2</label>
								</div>
							</div>
							<div class="row mt-2">
								<div class="col-md-12">
									<label class="text-dark">Closing Balance in Unchecked Block:</label>
									<label class="text-primary" name="excelclosingbalance" id="excelclosingbalance">t3 </label>
								</div>
							</div>
							<div class="row mt-2">
								<div class="col-md-12">
									<label class="text-dark">Date of Closing Balance in Unchecked Block:</label>
									<label class="text-primary" name="excelclosingbalancedate" id="excelclosingbalancedate"> t4</label>
								</div>
							</div>
							<div class="row mt-2">
								<div class="col-md-12">
									<label class="text-dark">Opening Balance as in the pdf on <span id="excelopendate"><span> </label>
									<input type="number" class="form-control" name="openingbalance" id="openingbalance" />
								</div>
							</div>
							<div class="row mt-2">
								<div class="col-md-12">
									<label class="text-dark">Closing Balance as in the pdf on <span id="excelclosedate"><span> </label>
									<input type="number" class="form-control" name="closingbalance" id="closingbalance" />
								</div>
							</div>
							<div class="row mt-2">
								<div class="col-md-6">
									<button class="btn btn-success" id="btncheck">Check</button>
								</div>
								<div class="col-md-6 d-none">
									<button class="btn btn-success" id="btnexit">Exit Without Uploading</button>
								</div>
							</div>
						</div>
						<div class="col-md-6">
							<div class="mt-2" id="previewpdf"><iframe style="width:100%;height:80vh;"></iframe></div>
						</div>
					</div>
        </div>
      </div>
    </div>
</div>
<button id="uncheckdatabtn" class="btn d-none">uncheckdata</button>
<input type="hidden" id="hidden_filedbid" value="<?php echo $filedbidval; ?>">

<?php 
// require('../footer.php');
?>
<script type="text/javascript">
	
	var companyid = $("#hidden_companyid").val();
	var subcompanyid = $("#hidden_subcompanyid").val();
	var projectid = $("#hidden_projectid").val();
	var filedbid = $("#hidden_filedbid").val();

	getuncheckedbankdata("hidden_rooturl",companyid,subcompanyid,projectid, function(res){
		if(res.length>0){
			if(parseFloat(res[0].balance)){
				var exlopbal = Math.round((parseFloat(res[0].balance)+parseFloat(res[0].debitamount)-parseFloat(res[0].creditamount))*100)/100;
				var exclbal = res[res.length-1].balance;
			}
			else{
				var exlopbal = "UNKNOWN";
				var exclbal = "UNKNOWN";
			}

			$("#excelopeningbalance").text(exlopbal);
			$("#excelclosingbalance").text(exclbal);
			$("#excelopeningbalancedate").text(formatDate(res[0].value_date));
			$("#excelclosingbalancedate").text(formatDate(res[res.length-1].value_date));
			$("#excelopendate").text(formatDate(res[0].value_date));
			$("#excelclosedate").text(formatDate(res[res.length-1].value_date));
			$("#btncheck").data("uncheckdata",res);
			get_bankstatementpdf("hidden_rooturl",companyid,subcompanyid,projectid,res[0].account_id,res[0].value_date,res[res.length-1].value_date,function(res){
				if(res){
					var url = res;
					$("#previewpdf").find("iframe").attr("src",$("#hidden_rooturl").val()+"preview.php?f="+url);
				}
			});
			$("#modelcheckbsuploaded").modal('show');
		}
	});

	$(document).on("click","#uncheckdatabtn", function(){
		getuncheckedbankdata("hidden_rooturl",companyid,subcompanyid,projectid, function(res){
			if(res.length>0){
				if(parseFloat(res[0].balance)){
					var exlopbal = Math.round((parseFloat(res[0].balance)+parseFloat(res[0].debitamount)-parseFloat(res[0].creditamount))*100)/100;
					var exclbal = res[res.length-1].balance;
				}
				else{
					var exlopbal = "UNKNOWN";
					var exclbal = "UNKNOWN";
				}

				$("#excelopeningbalance").text(exlopbal);
				$("#excelclosingbalance").text(exclbal);
				$("#excelopeningbalancedate").text(formatDate(res[0].value_date));
				$("#excelclosingbalancedate").text(formatDate(res[res.length-1].value_date));
				$("#excelopendate").text(formatDate(res[0].value_date));
				$("#excelclosedate").text(formatDate(res[res.length-1].value_date));
				$("#btncheck").data("uncheckdata",res);
				get_bankstatementpdf("hidden_rooturl",companyid,subcompanyid,projectid,res[0].account_id,res[0].value_date,res[res.length-1].value_date,function(res){
					if(res){
						var url = res;
						$("#previewpdf").find("iframe").attr("src",$("#hidden_rooturl").val()+"preview.php?f="+url);
					}
				});
				$("#modelcheckbsuploaded").modal('show');
			}
		});
	});

	getaccinfobyprojectinfo("hidden_rooturl",companyid,subcompanyid,projectid, function(res){
		// console.log(res);
		$("#bankaccountname").append("<option value='' selected>Select Account</option>");
		for (var i = 0; i < res.length; i++) {
			$("#bankaccountname").append("<option value='"+res[i].accid+"|"+res[i].bankid+"'>"+res[i].accname+"-"+res[i].bankname+"-"+res[i].accno+"</option>");
		}
	});
	
	$("#bankkeymaster").on("click",function(){
		window.open($("#hidden_rooturl").val()+"bankstatementheader/?k="+$("#hidden_key").val(),"_blank");
	});

	$("#statementreport").on("click",function(){
			window.open($("#hidden_rooturl").val()+"webpages/uploadedbankstatemententries.php?k="+$("#hidden_key").val(),"_blank");
	});

	$("#btncheck").on('click', function(){
			var form_data =new FormData();
			var openingbalance = $("#openingbalance").val();
			var closingbalance = $("#closingbalance").val();
			var uncheckdata = $(this).data("uncheckdata");
			form_data.append("openingbalance",openingbalance);
			form_data.append("closingbalance",closingbalance);
			form_data.append("uncheckdata",JSON.stringify(uncheckdata));
			form_data.append("account_id",uncheckdata[0].account_id);
			$.ajax({
				type: "POST",	
				url: "../modal/request_modal.php?action=checkdata",
				data: form_data,
		    contentType: false,
		    processData: false,
		    cache: false,
		    dataType:'JSON',
				success: function(res){
					if(res.status=='Success'){
						var companyid = $("#hidden_companyid").val();
						var subcompanyid = $("#hidden_subcompanyid").val();
						var projectid = $("#hidden_projectid").val();
						var filedbid = $("#hidden_filedbid").val();
						//SweetAlertmessageFadein('success',res.message);
						if(filedbid){
							markdigitizedentry_syncfile(companyid, subcompanyid, projectid, filedbid)
						}
						alert(res.message);
						location.reload();
					}
					else if(res.status=='TransactionError'){
						SweetAlertmessageFadein('error',res.message);
					}
					else{
						SweetAlertmessageFadein('error',res.message+'\n\n\n The sheet will not be uploaded. Please re-check and upload the sheet again.');
						$("#btnexit").trigger("click");
					}
				}
		});
	});

	$("#btnexit").on('click', function(){
		var uncheckdata = $("#btncheck").data("uncheckdata");
		var form_data =new FormData();
		form_data.append("account_id",uncheckdata[0].account_id);
					$.ajax({
				type: "POST",	
				url: "../modal/request_modal.php?action=deletecheckdata",
				data: form_data,
		    contentType: false,
		    processData: false,
		    cache: false,
		    dataType:'JSON',
				success: function(res){
					if(res.status=='Success'){
						location.reload();
					}
					else if(res.status=='TransactionError'){
						SweetAlertmessageFadein('error',res.message);
					}
					else{
						SweetAlertmessageFadein('error',res.message);
					}
				}
		});
	});

	$("#updbankstatementbtn").on('click', function(){
		var accountselectval = $("#bankaccountname").val();
		if (accountselectval=='' || accountselectval==null){
			alertmessage('error','Please select bank account');
			return false;
		}
		const myArray = accountselectval.split("|");
		let word = myArray[1];
		bankid = myArray[1];
		var accountid = myArray[0];

		var accountselecttext = $("#bankaccountname").find("option:selected").text();
		var accountparts = accountselecttext.split("-");
		var accountno = accountparts[accountparts.length-1];
		var bankname = accountparts[accountparts.length-2];
		var companyid = $("#hidden_companyid").val();
		var subcompanyid = $("#hidden_subcompanyid").val();
		var projectid = $("#hidden_projectid").val();
		var loginname = $("#hidden_loginname").val();
		var rooturl = $("#hidden_rooturl").val();
		var excelfile = '<?php echo $excelfile; ?>';
		var attval =$("#bankstatementfile").val();
		if(!excelfile){
			if(attval==null || attval=="" || attval=="undefined"){
				alertmessage('error','Choose the bank statement as excel');
	            return false;
			}
		}
		var filedbid = $("#hidden_filedbid").val();
	  var form_data = new FormData();	   
		form_data.append("files", document.getElementById('bankstatementfile').files[0]);
		form_data.append('excelfile', '<?php echo $excelfile; ?>');
		form_data.append('filedbid', filedbid);
		form_data.append('companyid', companyid);
		form_data.append('subcompanyid', subcompanyid);
		form_data.append('projectid', projectid);
		form_data.append('accountid',accountid);
		form_data.append('accountno',accountno);
		form_data.append('bankid',bankid);
		form_data.append('bankname',bankname);
		form_data.append('loginname', loginname);

	   $.ajax({
			type: "POST",	
			url: "modal/excelimportofbank_modal.php?action=uploadbankstatementmanual",
			data: form_data,
		    contentType: false,
		    processData: false,
		    cache: false,
		    dataType:'JSON',
			success: function(res){
				 // console.log(res);
				$("#updbankstatementbtn").prop("disabled", false);
				if(res.status=='Success'){
					$("#uncheckdatabtn").trigger("click");
					alertmessage(res.status,res.message);
				}else{
					SweetAlertmessageFadein(res.status,res.message);
				}
			}
		});
	});

	$("#savepdfbtn").on('click', function(){
		var accountselectval = $("#bankaccountname").val();
		if (accountselectval=='' || accountselectval==null){
			alertmessage('error','Please select bank account');
			return false;
		}
		const myArray = accountselectval.split("|");
		let word = myArray[1];
		bankid = myArray[1];
		var accountid = myArray[0];
		var rooturl = $("#hidden_rooturl").val();
		var companyid = $("#hidden_companyid").val();
		var subcompanyid = $("#hidden_subcompanyid").val();
		var projectid = $("#hidden_projectid").val();
		var loginname = $("#hidden_loginname").val();
		var pdffilepath = '<?php echo $pdffilepath; ?>';
		var startdate = $("#pdfstartdate").val();
		var enddate = $("#pdfenddate").val();
		if(!startdate){
			alert("Please specify 'Start Date of PDF'.");
			$("#pdfstartdate").focus();
		}
		if(!enddate){
			alert("Please specify 'End Date of PDF'.");
			$("#pdfenddate").focus();
		}
		var filedbid = $("#hidden_filedbid").val();
	  var form_data = new FormData();	   
		form_data.append("pdffiles", document.getElementById('pdfbankstatementfileas').files[0]);
		form_data.append('pdffilepath', pdffilepath);
		form_data.append('filedbid', filedbid);
		form_data.append('companyid', companyid);
		form_data.append('subcompanyid', subcompanyid);
		form_data.append('projectid', projectid);
		form_data.append('startdate', startdate);
		form_data.append('enddate', enddate);
		form_data.append('accountid',accountid);
		form_data.append('loginname', loginname);

	  $.ajax({
			type: "POST",	
			url: "modal/excelimportofbank_modal.php?action=uploadbankstatementpdf",
			data: form_data,
		  contentType: false,
		  processData: false,
		  cache: false,
		  dataType:'JSON',
			success: function(res){
				// console.log(res);
				$("#savepdfbtn").prop("disabled", false);
				if(res.status=='success'){
					SweetAlertmessageFadein(res.status,res.message);
				}else{
					SweetAlertmessageFadein(res.status,res.message);
				}
			}
		});
	});

	$("#bankaccountname").on('change', function(){
		var accountselectval = $("#bankaccountname").val();
		if (accountselectval=='' || accountselectval==null){
			alertmessage('error','Please select bank account');
			return false;
		}
		else{
			$("#uploadcontainer").show();
		}
	});

	function markdigitizedentry_syncfile(companyid, subcompanyid, projectid, filedbid) {
    var form_data = new FormData();
    form_data.append('companyid', companyid);
    form_data.append('subcompanyid', subcompanyid);
    form_data.append('projectid', projectid);
    form_data.append('filedbid', filedbid);
    $.ajax({
        type: "POST",
        url: "modal/sync_modal.php?action=markdigitizedentry_syncfile",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'json',
        success: function (res) {
            
        }
    });
}
</script>