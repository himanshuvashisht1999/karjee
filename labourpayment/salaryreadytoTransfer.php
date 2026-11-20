<?php
include('../header.php');
$mdid ="";
if(isset($_GET['mdid'])){
	$mdid = $_GET['mdid'];
}
$dddr = loginmodulesdetails_frommaster($authconn,$companyid,$subcompanyid,$projectid,"moduleid",$mdid);
if(count($dddr)>0){
	$groupid = $dddr[0]['groupid'];
}
?>
<div class="container">
	<div class="col-md-12 text-center">
		<h5> Payments Waiting to be Transferred </h5>
	</div>
	<div class="col-md-12">
	</div>
	<div id="voiceinstructionhtmlview" class="mt-4 mb-4"></div>
	<details id="pendingfortransferdetails" open>
    	<summary>Payments Waiting to be Transferred</summary>
		<div class="row">
			<div class="col-md-12"><button class="btn btn-warning ml-2 mr-2 btn-sm" onclick="history.back()">History Back</button>
				<a href="<?php echo $rooturl; ?>webpages/transactionreport.php?k=<?php echo $key; ?>&sourcetype=newtab" target="_blank" class="btn btn-sm pull-right btn-deepblue ml-2 mr-2">Get Transfered Report</a>
				<button class="btn btn-sm btn-info d-none" id="selectallsalary">Select All Salary</button>
			</div>
			<div class="col-md-12">
				<div class="table-responsive bg-light">
					<table class="table-mobile table-hover" style="font-size: .9em; width:100%" id="elctransferselft_tbl">
							<thead>
								<tr>
									<th colspan="6">
										<label class="d-none"><input type="radio" name="groupbytbldata" value="payto" checked /> Group by Payto</label>
										<label class="d-none"><input type="radio" name="groupbytbldata" value="purpose" /> Group by Purpose</label>
										<!-- <label><input type="radio" name="groupbytbldata" value="none" /> None</label> -->
									</th>
									<th colspan="2">
										 <input type="search" class="form-control pull-right" name="filterinpututrup" id="filterinpututrup" onclick="filterTable('#filterinpututrup', '#elctransferselft_tbl');" placeholder="Type to Search ......"  />
									</th>
								</tr>
								<tr>
									<th style="text-align: center;">S.No.</th>
									<th style="text-align: center;">Selct</th>
									<th>Refrence No*</th>
									<th>Book Date</th>
									<th>Payment Due Date</th>
									<th style="display:none;">From Account**</th>
									<th>PayTo Name</th>
									<th style="display:none;">PayTo A/C</th>
									<th style="display:none;">PayTo Bank</th>
									<th style="display:none;">PayTo IFSC</th>
									<th>Purpose of Transaction</th>
									<th>Request Amt.</th>
									<th style='display: none;'>Txn/utr No</th>
									<th style='display: none;'>Txn Date</th>
									<th style='display: none;'>Action</th>
									<th style='display: none;'>bank account id</th>
								</tr>
							</thead>
							<tbody></tbody>
					</table>
				</div>
			</div>
		</div>
		<div class="row mt-4">
			<div class="col-md-12 text-center">
				<button style=" margin: 4px; display: none; min-width: 150px" class="btn btn-danger" id="removedentrybtn"><i class="fa fa-trash" aria-hidden="true"></i> Cancel/Reject Payment</button>
				<button style=" margin: 4px; display: none; min-width: 150px;" class="btn btn-success" id="updutrmultiplebtn" data-toggle="modal" data-target="#updutr_formultipleentry_modal"><i class='fa fa-save'></i> Enter UTR for Multiple Entry</button>
			</div>
		</div>
	</details>
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

<div class="modal" id="updutr_formultipleentry_modal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" style="width: 98%;">
    <div class="modal-content">
    	<div class="modal-header bg-success">
          <h5 class="modal-title">REGULAR PAYMENT WINDOW</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">X</button>
      </div>
      <div class="modal-body">
		    <div class="row mt-2">
		    	<div class="col-md-12">
		    		<div class="table-responsive">
		    			<label>Transfer Amount Details</label>
		    			<table class="table-mobile table-bordered" id="utrformultipleentry_tbl">
		    				<thead>
		    					<tr>
		    						<th>#</th>
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
		    						<th></th>
		    						<th>Total Amount (Rs.)</th>
		    						<th id="ttlamttd"></th>
		    					</tr>
		    				</tfoot>
		    			</table>		    			
		    		</div>
		    	</div>
		    	<div class="col-md-12 mt-2" id="paytobankinfodiv">
		    		<div class="table-responsive">
		    			<label>Payto Bank Details</label>
		    			<i class="fa fa-edit pull-right" id="editpaytobank" style="cursor: pointer; color: blue;"></i>
		    			<table class="table table-bordered table-sm">
  								<colgroup>
									<col style="width: 30%;">
									<col style="width: 70%;">
								</colgroup>
  								<tbody>
    								<tr><td>Payto Bank Name</td> <td id="paytobanknametd"></td></tr>
    								<tr><td>Payto Bank A/C No</td> <td id="paytobankaccountnotd"></td></tr>
    								<tr><td>Payto Bank IFSC Code</td> <td id="bankifsccodetd"></td></tr>
    							</tbody>
    						</table>
		    		</div>
		    	</div>
		    </div>
		    <div class="row mt-2">
		    	<div class="col-md-12">
		    		<label>Pay From Bank</label>
		    		<span id="payfrmlbl" class="text-info float-right"></span>
		    		<select class="form-control" id="payfrombank_utr"></select>
		    	</div>
		    </div>
		    <div class="col-md-12 mt-2">
	    		<label>Transaction Type</label><br/>
	    		<label><input type="radio" name="transactiontyperadio" class="mt-2" value="online" checked />Online</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	    		<label><input type="radio" name="transactiontyperadio" class="mt-2" value="cheque" />Cheque</label><br/>
	    	</div>
		    	
		    <div class="row mt-4 bg-info p-2" id="itisonlysingleentry">
		    	<div class="col-md-6">
		    		<label class="text-danger text-sm"><input type="checkbox" id="isedittramount" /> Do you want to edit amount?</label>
		    	</div>
		    	<div class="col-md-6">
		    		<label class="text-danger text-sm"><input type="checkbox" id="isroundoffamount" checked /> Do you round off of amount?</label>
		    	</div>
		    </div>
		    <div class="row mt-4 bg-info p-2" id="transactionamountdivrow">
		    	<div class="col-md-6">
		    		<label id="actualtransactionamountlbl">Transfer amount</label>
		    		<input type="number" name="txtutramount" id="txtutramount" class="form-control" value="" disabled />
		    	</div>
		    	<div class="col-md-6">
		    		<label>Upload Transfer Receipt(If available)</label>
		    		<input type="file" name="transferreceipt" id="transferreceipt" class="form-control" />
		    	</div>
		    </div>
		    <div class="row bg-info p-2">
		    	<div class="col-md-6">
		    		<label id="actualtransactionnolbl">Enter the actual transaction no</label>
		    		<input type="text" name="txtutrno" id="txtutrno" class="form-control" />
		    	</div>
		    	<div class="col-md-6">
		    		<label id="actualtransactiondatelbl">Enter the actual transaction date</label>
		    		<input type="date" name="txtutrdate" id="txtutrdate" class="form-control" value="" />
		    	</div>
		    </div>
		    <div class="row mt-4">
		    	<div class="col-md-12 text-center">
		    		<button class="btn btn-info" id="updateutrno_mltentry_1" onclick="updateUTRDetails();">SAVE TRANSACTION DETAILS</button>
		    	</div>
		    </div>
      </div>
    </div>
  </div>
</div>

<div class="modal" id="breakdownref_modal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" style="width: 98%;">
    <div class="modal-content">
    	<div class="modal-header bg-success">
          <h5 class="modal-title" id="refhead">Breakdown Detail of Refrence No</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">X</button>
      </div>
      <div class="modal-body">
		    <div class="row mt-2">
		    	<div class="col-md-12">
		    		<div class="table-responsive">
		    			<table class="table table-light table-bordered" id="mainbreakdowntbls">
		    				<thead>
		    					<tr>
		    						<th>Beneficiary Name</th>
		    						<th>Amount</th>
		    						<th>Purpose</th>
		    						<th>Description</th>
		    						<th>Document</th>
		    					</tr>
		    				</thead>
		    				<tbody></tbody>
		    			</table>		    			
		    		</div>
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
var loginid = "<?php echo $loginid; ?>";
var loginroleid = "<?php echo $loginroleid; ?>";
var companyid = "<?php echo $companyid; ?>";
var subcompanyid = "<?php echo $subcompanyid; ?>";
var projectid = "<?php echo $projectid; ?>";
var loginrolename = "<?php echo $loginrolename; ?>";
var rooturl = $("#hidden_rooturl").val();
var accesslg = "<?php echo $accesslg; ?>";

$(document).on("click", "input[name='transactiontyperadio']", function(){
	var rrdval = $("input[name='transactiontyperadio']:checked").val();
	if(rrdval=="online"){
		$("#paytobankinfodiv").show();
		$("#itisonlysingleentry").show();
		$("#transactionamountdivrow").show();
		$("#actualtransactionamountlbl").html("Transfer amount");
		$("#actualtransactionnolbl").html("Enter the actual transaction no");
		$("#actualtransactiondatelbl").html("Enter the actual transaction date");
	}else if(rrdval=="cheque"){
		$("#paytobankinfodiv").hide();
		$("#itisonlysingleentry").hide();
		$("#transactionamountdivrow").hide();
		$("#actualtransactionamountlbl").html("cheque amount");
		$("#actualtransactionnolbl").html("Enter the actual cheque no");
		$("#actualtransactiondatelbl").html("Enter the actual cheque date");
	}
});

$(document).on("click", "#removedentrybtn", function(){
	$("#removedtransactions_modal").modal("show");
	$("#removetransactions_tbl tbody").empty();
	let ttltramountrm=0;
	$("#elctransferselft_tbl tbody tr").each(function(index, row) {
		var $row = $(row);
		if ($row.closest("tr").find("input[name='checkforupdutr']").is(":checked")) {
	        $("#removetransactions_tbl tbody").append("<tr><td>"+$row.closest("tr").find('td.refno_td').text()+"</td><td class='payto_ut'>"+$row.closest("tr").find('td.paytoname_td').text()+"</td><td class='bookamount_ut'>"+$row.closest("tr").find('td.bookamount_td').text()+"</td><td class='dbsno_ut' style='display:none;'>"+$row.closest("tr").find('input.dbsnot_td').val()+"</td></tr>");
	        
	        ttltramountrm = parseFloat(ttltramountrm) + parseFloat($row.closest("tr").find('td.bookamount_td').text());
		}
	});
	$("#rmttlamttd").html(ttltramountrm);
});

$(document).on("click", "#editpaytobank", function(){
	var epaytoname = $("#editpaytobank").data('epaytoname');
	var epaytoid = $("#editpaytobank").data('epaytoid');
	var epaytobankname = $("#editpaytobank").data('epaytobankname');
	var epaytoaccountno = $("#editpaytobank").data('epaytoaccountno');
	var epaytoifsccode = $("#editpaytobank").data('epaytoifsccode');
	var ereferenceno = $("#editpaytobank").data('ereferenceno');
	// alert(epaytobankname);
	// erequestno

 	$("#newpaytodivid").load("<?php echo $rooturl; ?>expenditure_expencessheet/newpayto_authorization.php", function(){
		$("#newpaytodetails_modal").modal("show");
		$("#newpaytodetails_modal").css("z-index","9999");
		$("#new_paytoname").val(epaytoname);
		if(epaytoaccountno && epaytobankname && epaytoifsccode){
			$("#new_paytobankname").val(epaytobankname);
			$("#new_paytobankaccountno").val(epaytoaccountno);
			$("#new_paytobankifsccode").val(epaytoifsccode);
		}else{
			var epaytoid = $("#editpaytobank").data('epaytoid');
			if(epaytoid){
				paytobankdetailsfrommaster_callback("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid",epaytoid,function(res){
					// console.log(res);
					if(res.length>0){
						for(var i=0; i<res.length; i++){
							if(res[i].toaccountno){
								$("#new_paytobankname").val(res[i].tobankname);
								$("#new_paytobankaccountno").val(res[i].toaccountno);
								$("#new_paytobankifsccode").val(res[i].tobankifsc);
							}else{
								// alertmessage('warning','Payto bank details did not update in master table');
							}
						}
					}else{
						// alertmessage('warning','Payto bank details did not update in master table');
					}
				});
			}
		}
		$("#wanttofill_paytobank").trigger("click");
		$("#newpaytosavebtn").data('requestno', $("#editpaytobank").data('erequestno'));
		$("#newpaytosavebtn").data('referenceno', $("#editpaytobank").data('ereferenceno'));
		$("#newpaytosavebtn").data('oldbanknametxtid', 'paytobanknametd');
		$("#newpaytosavebtn").data('oldaccountnotxtid', 'paytobankaccountnotd');
		$("#newpaytosavebtn").data('oldifscodetxtid', 'bankifsccodetd');		
  });
});
$('#newpaytodetails_modal').on('hidden.bs.modal', function () {
	$('body').addClass('modal-open');
});

readytotransfersalarylist("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid","elctransferselft_tbl");

allaccountswithcallback("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid",function(res){
	// console.log(res);
	if(res.length>0){
		if(res.length>1){
			$("#payfrombank_utr").append("<option value='' disabled selected>Choose payfrom Bank</option>");
		}
		for (var i = 0; i < res.length; i++) {
				$("#payfrombank_utr").append("<option value='"+res[i].accid+"'>"+res[i].accname+' ('+res[i].bankname+'-****'+res[i].accno.slice(-4)+")</option>");
		}
	}
});

$(document).on("click", ".refno_td", function(){
	$("#refhead").html("Breakdown details of refrence no "+$(this).text());
	maintransactionbreakdowndetails("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid",$(this).text(),function(res){
		console.log(res);
		var brktbl="";
		if(res.length>0){
			$("#breakdownref_modal").modal("show");
			$('#breakdownref_modal').on('hidden.bs.modal', function () {
				$('body').addClass('modal-open');
			});
			$("#mainbreakdowntbls tbody").empty();
			for(var i=0; i<res.length; i++){
				var documentpath="N/A";
				if(res[i].documentation_path!=""){
					// alert(rooturl+res[i].documentation_path);
					// documentpath = "<button class='btn btn-info btn-sm' onclick='prevedocment(\""+res[i].documentation_path+"\");'>View document</button>";
					 documentpath = "<button class='btn btn-info btn-sm' onclick='showPreview_document(\""+rooturl+res[i].documentation_path+"\");'>View document</button>";
				}
				$("#mainbreakdowntbls tbody").append("<tr><td>"+res[i].beneficiary_name+"</td><td>"+res[i].amount+"</td><td>"+res[i].purposewithcode+"</td><td>"+res[i].description+"</td><td>"+documentpath+"</td></tr>");
			}
		}else{
			alertmessage('error','Breakdown details did not available');
			return false;
		}
	});
});

$(document).on("click", ".checkforupdutr", function(){
	var grpvalue = $("input[name='groupbytbldata']:checked").val();
	var checklength = $("input[name='checkforupdutr']:checked").length;
	// alert(checklength);
	const checkboxes = document.querySelectorAll("input[name='checkforupdutr']");
	var paytoname = $(this).closest("tr").find('td.paytoname_td').text().toLowerCase();
	var purposename = $(this).closest("tr").find('td.tempurpose_td').text().toLowerCase();
	$("#utrformultipleentry_tbl tbody").empty();
	// $("#elctransferselft_tbl tbody").empty();
	$("#elctransferselft_tbl tr").show();
	$("#voiceinstrqlist").removeAttr("open");

	if(grpvalue=="purpose"){
		$("#paytobankinfodiv").hide();
		$("#paytobanknametd").html("Multiple Payto of Salary");
		$("#paytobankaccountnotd").html("Multiple Payto of Salary");
		$("#bankifsccodetd").html("Multiple Payto of Salary");
		$("#editpaytobank").data("epaytoname", "");
		$("#editpaytobank").data("epaytoid", "");
	  $("#editpaytobank").data("epaytobankname", "");
	  $("#editpaytobank").data("epaytoaccountno", "");
	  $("#editpaytobank").data("epaytoifsccode", "");
	}

	var refno = $(this).closest("tr").find('td.refno_td').text();
		maintransactionowndetails("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid",refno,function(res){
			// console.log(res);
			$("#paytobanknametd").html('');
			$("#paytobankaccountnotd").html('');
			$("#bankifsccodetd").html('');
			if(res.length>0){
				for (var i = 0; i < res.length; i++) {
					// alert(res[i].paytoid);
					$("#paytobanknametd").html(res[i].paytobankname);
					$("#paytobankaccountnotd").html(res[i].paytoaccountno);
					$("#bankifsccodetd").html(res[i].paytoifsc);
					$("#editpaytobank").data("epaytoname", res[i].payto);
					$("#editpaytobank").data("epaytoid", res[i].paytoid);
	        $("#editpaytobank").data("epaytobankname", res[i].paytobankname);
	        $("#editpaytobank").data("epaytoaccountno", res[i].paytoaccountno);
	        $("#editpaytobank").data("epaytoifsccode", res[i].paytoifsc);
				}
			}
		});
	
	var ttltramount =0;
	var payfromaccid="";
	let pyfrmcount=0;
	let matchedrow=0;
	if(checklength>=1){
		if(checklength==1){
			$("#itisonlysingleentry").show();
			$("#updutrmultiplebtn").html("<i class='fa fa-save'></i> Proceed to update transaction");
			$("#removedentrybtn").html("<i class='fa fa-trash'></i> Cancel/Reject Payment");
		}else{
			$("#itisonlysingleentry").hide();
			$("#updutrmultiplebtn").html("<i class='fa fa-save'></i>Proceed to update "+checklength+" transactions");
			$("#removedentrybtn").html("<i class='fa fa-trash'></i> Cancel/Reject "+checklength+" Payments");
		}
		$("#updutrmultiplebtn").show();
		$("#removedentrybtn").show();
		var checkedref="";
		var checkedreq="";
		$("#elctransferselft_tbl tbody tr").each(function(index, row) {
			var $row = $(row);
			if(paytoname==$row.closest("tr").find('td.paytoname_td').text().toLowerCase() && grpvalue=="payto"){
				$row.show();
				matchedrow++;
				if ($row.closest("tr").find("input[name='checkforupdutr']").is(":checked")) {
	        // Get the values from the closest row
	        if(payfromaccid != $row.closest("tr").find("td.frmaccountid_td").text()){
	        	payfromaccid = $row.closest("tr").find("td.frmaccountid_td").text();
	        	pyfrmcount++;
	        }
	        $("#utrformultipleentry_tbl tbody").append("<tr><td><input type='checkbox' class='updutrcheckbox' checked /></td><td class='refno_td' style='color:blue; cursor:pointer;'>"+$row.closest("tr").find('td.refno_td').text()+"</td><td class='payto_ut'>"+$row.closest("tr").find('td.paytoname_td').text()+"</td><td class='bookamount_ut'>"+$row.closest("tr").find('td.bookamount_td').text()+"</td><td class='dbsno_ut' style='display:none;'>"+$row.closest("tr").find('input.dbsnot_td').val()+"</td></tr>");
	        if(checkedref){
		        checkedref+=","+$row.closest("tr").find('td.refno_td').text();
	        }else{
		        checkedref=$row.closest("tr").find('td.refno_td').text();
	        }
	        ttltramount = parseFloat(ttltramount) + parseFloat($row.closest("tr").find('td.bookamount_td').text());
	    	}
	    }else if(purposename==$row.closest("tr").find('td.tempurpose_td').text().toLowerCase() && grpvalue=="purpose"){
				$row.show();
				matchedrow++;
				if ($row.closest("tr").find("input[name='checkforupdutr']").is(":checked")) {
	        // Get the values from the closest row
	        if(payfromaccid != $row.closest("tr").find("td.frmaccountid_td").text()){
	        	payfromaccid = $row.closest("tr").find("td.frmaccountid_td").text();
	        	pyfrmcount++;
	        }
	        $("#utrformultipleentry_tbl tbody").append("<tr><td><input type='checkbox' class='updutrcheckbox' checked /></td><td class='refno_td' style='color:blue; cursor:pointer;'>"+$row.closest("tr").find('td.refno_td').text()+"</td><td class='payto_ut'>"+$row.closest("tr").find('td.paytoname_td').text()+"</td><td class='bookamount_ut'>"+$row.closest("tr").find('td.bookamount_td').text()+"</td><td class='dbsno_ut' style='display:none;'>"+$row.closest("tr").find('input.dbsnot_td').val()+"</td></tr>");
	        if(checkedref){
		        checkedref+=","+$row.closest("tr").find('td.refno_td').text();
	        }else{
		        checkedref=$row.closest("tr").find('td.refno_td').text();
	        }
	        ttltramount = parseFloat(ttltramount) + parseFloat($row.closest("tr").find('td.bookamount_td').text());
	    	}
	    }else{
	    	$row.hide();
	    }
		});
		$("#editpaytobank").data("erequestno", checkedreq);
	  $("#editpaytobank").data("ereferenceno", checkedref);
		// alert(checkedref);
		$("#ttlamttd").html(ttltramount);
		$("#txtutramount").val(Math.round(ttltramount));
		if(pyfrmcount==1){
			$("#payfrombank_utr").val(payfromaccid).trigger("change");
		}else{
			$("#payfrombank_utr").val('').trigger("change");
		}
		if(matchedrow==1){
			// $("#updutrmultiplebtn").trigger("click");
			$("#updutrmultiplebtn").show();
			$("#removedentrybtn").show();
		}else{
			$("#updutrmultiplebtn").show();
			$("#removedentrybtn").show();

		}
	}else{
		$("#elctransferselft_tbl tr").show();
		$("#updutrmultiplebtn").hide();
		$("#removedentrybtn").hide();
	}
});

$(document).on("click", ".updutrcheckbox", function(){
	let ttltramountt=0;
	$("#utrformultipleentry_tbl tbody tr").each(function(index, row) {
		var $row = $(row);
		if($row.closest("tr").find('.updutrcheckbox').is(":checked")){
			ttltramountt = parseFloat(ttltramountt) + parseFloat($row.closest("tr").find('td.bookamount_ut').text());
		}
	});	
	$("#ttlamttd").html(ttltramountt);
	$("#txtutramount").val(Math.round(ttltramountt));
});

$(document).on("click", "input[name='groupbytbldata']", function(){
	$("input[name='checkforupdutr']").prop("checked", false);
	$("#elctransferselft_tbl tbody tr").show();
	$("#updutrmultiplebtn").hide();
});

// only salary entries
$(document).on("click", "#selectallsalary", function(){
	$("#utrformultipleentry_tbl tbody").empty();
	$("#elctransferselft_tbl tr").show();
	$("#voiceinstrqlist").removeAttr("open");
	$("#paytobankinfodiv").hide();

	var ttltramount =0;
	var payfromaccid="";
	let pyfrmcount=0;
	let matchedrow=0;
	$("#updutrmultiplebtn").html("Proceed to update transaction");
	$("#updutrmultiplebtn").show();
	$("#removedentrybtn").show();
	var checkedref="";
	var checkedreq="";
	$("#paytobanknametd").html("Multiple Payto of Salary");
	$("#paytobankaccountnotd").html("Multiple Payto of Salary");
	$("#bankifsccodetd").html("Multiple Payto of Salary");
	$("#editpaytobank").data("epaytoname", "");
	$("#editpaytobank").data("epaytoid", "");
  $("#editpaytobank").data("epaytobankname", "");
  $("#editpaytobank").data("epaytoaccountno", "");
  $("#editpaytobank").data("epaytoifsccode", "");

	$("#elctransferselft_tbl tbody tr").each(function(index, row) {
			var $row = $(row);
			// if($row.closest("tr").find('td.tempurpose_td').text().toLowerCase().includes(' salary')){
			let value = $row.closest("tr").data('tempsubcateid');
			if (['2', '8', '12'].includes(String(value))) {
				// alert('mainString');
				$row.show();
				$row.closest("tr").find("input[name='checkforupdutr']").prop("checked", true);
				// Get the values from the closest row
		        if(payfromaccid != $row.closest("tr").find("td.frmaccountid_td").text()){
		        	payfromaccid = $row.closest("tr").find("td.frmaccountid_td").text();
		        	pyfrmcount++;
		        }
				matchedrow++;
		        $("#utrformultipleentry_tbl tbody").append("<tr><td><input type='checkbox' class='updutrcheckbox' checked /></td><td class='refno_td' style='color:blue; cursor:pointer;'>"+$row.closest("tr").find('td.refno_td').text()+"</td><td class='payto_ut'>"+$row.closest("tr").find('td.paytoname_td').text()+"</td><td class='bookamount_ut'>"+$row.closest("tr").find('td.bookamount_td').text()+"</td><td class='dbsno_ut' style='display:none;'>"+$row.closest("tr").find('input.dbsnot_td').val()+"</td></tr>");
		        if(checkedref){
			        checkedref+=","+$row.closest("tr").find('td.refno_td').text();
		        }else{
			        checkedref=$row.closest("tr").find('td.refno_td').text();
		        }
		        ttltramount = parseFloat(ttltramount) + parseFloat($row.closest("tr").find('td.bookamount_td').text());		    	
		    }else{
		    	$row.hide();
		    }
		});

		$("#editpaytobank").data("erequestno", checkedreq);
	  	$("#editpaytobank").data("ereferenceno", checkedref);
		$("#ttlamttd").html(ttltramount);
		$("#txtutramount").val(Math.round(ttltramount));
		if(pyfrmcount==1){
			$("#payfrombank_utr").val(payfromaccid).trigger("change");
		}else{
			$("#payfrombank_utr").val('').trigger("change");
		}
		
		if(matchedrow==1){
			// $("#updutrmultiplebtn").trigger("click");
			$("#updutrmultiplebtn").show();
		}else if(matchedrow==0){
			$("#updutrmultiplebtn").hide();
			SweetAlertmessageFadein('error','Salary entry not available');
		}else{
			$("#updutrmultiplebtn").show();
			$("#removedentrybtn").show();
		}
	// }else{
	// 	$("#elctransferselft_tbl tr").show();
	// 	$("#updutrmultiplebtn").hide();
	// }
});



$(document).on("click", "#isedittramount", function(){
	if($(this).is(":checked")){
		$("#txtutramount").prop("disabled", false);
	}else{
		$("#txtutramount").prop("disabled", true);
	}
});

$(document).on("click", "#isroundoffamount", function(){
	let ttrf = $("#ttlamttd").text();
	if($(this).is(":checked")){
		$("#txtutramount").val(Math.round(ttrf));
	}else{
		$("#txtutramount").val(ttrf);
	}
});
</script>
<script type="text/javascript">
  $("#voiceinstructionhtmlview").load(rooturl+"voiceinstructionlist_html.php");
</script>