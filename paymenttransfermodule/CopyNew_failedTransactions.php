<?php
include('../header.php');
$mdid ="";
if(isset($_GET['mdid'])){
	$mdid = $_GET['mdid'];
}
$dddr = loginmodulesdetails_frommaster($authconn,$companyid,$subcompanyid,$projectid,"moduleid",$mdid);
$groupid ="";
$modulename ="";
if(count($dddr)>0){
	$groupid = $dddr[0]['groupid'];
	$modulename = $dddr[0]['modulename'];
}
?>

<div class="container">
	<div class="row">
		<div class="col-md-12 mt-1 mb-3 pt-3 pb-3 text-center">
			<h4><?php echo $modulename; ?></h4>
			<a href="<?php echo $rooturl; ?>admin_dashboard.php?k=<?php echo $key; ?>&grpid=<?php echo $groupid; ?>&mdid=<?php echo $mdid; ?>" class="btn btn-deepblue ml-2 mr-2 pull-right backtohome">Back to Home</a>
		</div>
	</div>
	<div class="card">
		<div class="card-body">
			<div class="row">
				<div class="col-md-12">
					<label>Make Copy the old reference and assign it a new reference number.</label>
					<input type="text" class="form-control" name="copytooldreferenceno" id="copytooldreferenceno" />
				</div>
				<div class="col-md-12 mt-4">
					<div id="previewrefdetailsdiv"></div>
				</div>
			</div>
			<div class="row mt-4" id="actiontorefdiv" style="display: none;">
				<div class="col-md-12 mt-2 d-none">
					<label>Why do you need to create a new reference if the details are the same?</label><br/>
					<label class="no-label" for="paymentFailed"><input type="radio" name="reasonradio" id="paymentFailed" value="paymentfailed" checked> Payment Failed</label>
					<label class="no-label" for="systemRestriction"><input type="radio" name="reasonradio" id="systemRestriction" value="systemrestriction"> System Restriction</label>
					<label class="no-label" for="auditTrail"><input type="radio" name="reasonradio" id="auditTrail" value="audittrail"> Audit Trail</label>
					<label class="no-label" for="auditTrail"><input type="radio" name="reasonradio" id="DuplicateEntryPrevention" value="duplicate"> Duplicate Entry Prevention</label>
				</div>
				<div class="col-md-12 mt-2">
					<label>Remarks</label>
					<textarea rows="2" class="form-control" id="remarksforcopy"></textarea>
				</div>
				<div class="col-md-12 mt-4 text-center">
					<button class="btn btn-deepblue" id="assignnewreferenceno" onclick="CopyReferenceDetailsWithNewReference('copytooldreferenceno','reasonradio','remarksforcopy','assignnewreferenceno');">Make a Duplicate</button>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="modal" id="removedtransactions_modal" tabindex="-1" role="dialog">
	  <div class="modal-dialog modal-lg" style="width: 98%;">
	    <div class="modal-content">
	    	<div class="modal-header bg-success">
	          <h5 class="modal-title">Cancel/Reject Payments</h5>
	          <button type="button" class="close" data-dismiss="modal" aria-label="Close">X</button>
	      </div>
	      <div class="modal-body">
			    <div class="row mt-2">
			    	<div class="col-md-12">
			    		<div class="table-responsive">
			    			<label>List of Cancel/Reject Payment</label>
			    			<table class="table-mobile table-bordered" id="removetransactions_tbl">
			    				<thead>
			    					<tr>
			    						<th>Reference No</th>
			    						<th>Payto</th>
			    						<th>Amount</th>
			    						<th style='display:none;'>Db Sno</th>
			    					</tr>
			    				</thead>
			    				<tbody></tbody>
			    				<tfoot>
			    					<tr>
			    						<th></th>
			    						<th>Total Amount (Rs.)</th>
			    						<th id="rmttlamttd"></th>
			    					</tr>
			    				</tfoot>
			    			</table>		    			
			    		</div>
			    	</div>
			    </div>
			    
			    <div class="row bg-info p-2">
			    	<div class="col-md-12">
			    		<label>Remark of Cancel/Reject Payment</label>
			    		<textarea id="removetransactions_remarks" class="form-control" rows="4"></textarea>
			    	</div>
			    </div>
			    <div class="row mt-4">
			    	<div class="col-md-12">
			    		<label><input type="checkbox" id="checktorejectpayment" /> Are you sure, want to cancel/reject payment?</label>
			    	</div>
			    	<div class="col-md-12 mt-4 text-center">
			    		<button class="btn btn-info" id="removedtransaction_btn" onclick="CancelPayments();">Cancel/Reject Payment</button>
			    	</div>
			    </div>
	      </div>
	    </div>
	  </div>
	</div>
<?php
include('../footer.php');
?>
<script type="text/javascript">
$(document).on("input", "#copytooldreferenceno", function(){
	var oldrefno = $("#copytooldreferenceno").val();
	if(oldrefno=="" || oldrefno==null || oldrefno=="undefined"){
		// alertmessage('warning', 'Reference number is missing for the record you want to preview.');
		return false;
	}
	$("#actiontorefdiv").hide();
	getReferenceNoDetails(oldrefno, function(res){
		$("#previewrefdetailsdiv").html('');
		if(res.length>0){
			$("#actiontorefdiv").show();
			for (var i = 0; i < res.length; i++) {
				$("#previewrefdetailsdiv").html("<div style='background-color: #fff; border: 1px solid black; border-radius: 14px; padding: 16px 32px; margin: 0 auto; width: 70%;'><b>Payto Name: </b> " + res[i].payto + "<br/><b>Amount: </b> " + res[i].book_amount + "<br/><b>Transaction Date: </b> " + res[i].transaction_date + "<br/><b>Transaction No: </b> " + res[i].transactionno + "<br/><b>Pay From Bank: </b> " + res[i].payfrombankname+ "-"+res[i].lastfourdigitfrmacno + "<br/><b>Purpose: </b> " + res[i].temppurposename + "<br/></div>");
			}
		}else{
			$("#previewrefdetailsdiv").html("<h5 class='text-danger text-center'>No data available given no "+oldrefno+"</h5>");
		}
	});
});

// $(document).on("click", "#assignnewreferenceno", function(){
// 	Swal.fire({
// 	    title: "Are you sure?",
// 	    text: "You are attempting to make a duplicate entry because the original transaction has failed. This duplication allows the transaction to be reprocessed with a new reference number while preserving the original details.",
// 	    icon: "warning",
// 	    showCancelButton: true,
// 	    confirmButtonColor: "#3085d6",
// 	    cancelButtonColor: "#d33",
// 	    confirmButtonText: "Yes, Proceed it!"
//   	}).then((result) => {
// 	    if (result.isConfirmed) {
// 	    	CopyReferenceDetailsWithNewReference('copytooldreferenceno','reasonradio','remarksforcopy','assignnewreferenceno');
// 	    }
// 	});
// });
</script>
