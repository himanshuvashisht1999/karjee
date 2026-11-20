<?php 
$ppcatid = isset($_GET['ppcatid'])?$_GET['ppcatid']:'';
if(!$ppcatid){
  die('Purpose category not defined');
}
$dz="";
if(isset($_GET['dz'])){
	$dz=$_GET['dz'];
}

$ramount="";
if(isset($_GET['ramount'])){
	$ramount=$_GET['ramount'];
}
$rpaytoname="";
$rinstid="";
if(isset($_GET['rinstid'])){
	$rinstid=$_GET['rinstid'];
}

$subaction ="";
if(isset($_GET['subaction'])){
	$subaction = $_GET['subaction'];
}
$subprojectid="";
if(isset($_GET['subprojectid'])){
	$subprojectid=$_GET['subprojectid'];
}

$paytotype="";
if(isset($_GET['pyt'])){
	$paytotype=$_GET['pyt'];
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
?>
	<div class="row mt-2 subprojectdiv" id="subprojectdiv">
		<div class="col-md-12">
			<label><?php echo $subprojectid!=""?"Sub-project name":"Choose the Sub-project"; ?></label>
			<select class="form-control" id="inv_subproject"></select>
		</div>
	</div>
	<div id="contaninerdiv">
		<div class="row mt-2 amounttopaydiv">
			<div class="col-md-6">
				<label>Amount <sup><i class="fa fa-star" style="font-size: 6px; color:red"></i></sup></label>
				<?php
					if($ramount>0){
						echo "<i class='text-danger'>You can edit the amount if the difference between the requested amount and the transfer amount is greater than zero.</i>";
					}
				?>
				<input type="text" id="inv_amount" onkeypress="return isFloatNumber(event, this)" class="form-control invoiceamount" value="<?php echo $ramount;?>" />
			</div>
			<div class="col-md-6">
				<?php 
				if($paytotype=="splr"){
				?>
				<i class="fa fa-sync float-right" onclick="request_supplierpaytolist('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','inv_paytoname');" style="cursor: pointer; color: green; font-size: 20px; margin: 0px 8px;"></i>
				<label>Registered supplier name</label>
				<i class="fa fa-plus-circle" onclick="openModalForAddSupplierAccount('hidden_rooturl');" style="cursor: pointer; color: green; font-size: 20px; margin: 0px 8px;"></i>
				<?php
				}else{
				?>
				<label>Who is the Payto? <sup><i class="fa fa-star" style="font-size: 6px; color:red"></i></sup></label>
				<i class="fa fa-sync float-right m-2 refeshpaytolist" onclick='request_paytofrommaster_withpaytoid("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","inv_paytoname","","");' style="cursor: pointer; color: green;"></i>
				<?php  
				}
				?>
				<select id="inv_paytoname" class="form-control select2" style="width:100%;"></select>
			</div>
		</div>
		<div class='row'>
			<div class='col-md-12'>
				<label>GST/VAT number</label>
				<span class="warningmsgspan"></span>
				<input type='text' class='form-control' id='inv_gstnumber' value='' disabled />
			</div>
		</div>
		<div id="taxdetails"></div>
		<div class="row mt-2" style="display: <?php echo ($dz==1?"none":"block"); ?>;">
	  	<div class="col-md-12" style="">
		    <label>Choose the document?</label>
		    <div class='form-control'><input id="inv_importfile4" type='file' accept="image/*; capture=camera" /><button class="btn-sm pull-right btnshow" style="border-width: thin;" onclick="showPreview_document(document.getElementById('inv_importfile4'), 'blob');">Show Preview</button></div>
	   	</div>
		</div>
		<div class="row mt-2">
			<div class="col-md-12">
				<h5>Is tax invoice of raw material for production?</h5><br/>
				<label><input type="radio" name="isforproductionradio" value="yes" /> YES</label>
				<label><input type="radio" name="isforproductionradio" value="no" /> NO</label>
			</div>
		</div>

		<div class="row mt-2" id="ispurposediv" style="display:none">
			<div class="col-md-6">
				<label>Is this invoice have single purpose?</label><br/>
				<label><input type="radio" name="issinglepurpose" value="yes" /> Yes</label>
				<label><input type="radio" name="issinglepurpose" value="no" checked /> No</label>			
			</div>
			<div class="col-md-6" id="singlepurposediv" style="display:none;">
		    <label>Purpose name</label>
		    <i class="fa fa-sync float-right m-2 refeshpaytolist" onclick="request_purposecodefrommaster('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','<?php echo $ppcatid; ?>',$('#inv_purposesubcategory').val(),'','inv_purposecode');" style="cursor: pointer; color: green;"></i>
		    <select id="inv_purposecode" class="form-control select2 purposecodeslt" style="width:100%;"></select>
			</div>
		</div>
		<div class="mt-4 bg-dark-gray-red-p5" id="invoicecontainer">
			<div class="row">
	    	<div class="col-md-4"></div>
	    	<div class="col-md-4">
	    		<label><span class='invoicetypetxt'>Invoice</span> Number</label>
	    		<input type="text" name="inv_invoiceno" id="inv_invoiceno" placeholder="Enter invoice number" class="form-control" />
	    	</div>
	    	<div class="col-md-4">
	    		<label><span class='invoicetypetxt'>Invoice</span> Date</label>
	    		<input type="date" name="inv_invoicedate" id="inv_invoicedate" class="form-control" max="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" />
	    	</div>
	    </div>
	    <div class="row mt-2" id="req_invoiceparentdiv">
	    	<div class="col-md-12">
	      	<div class="table-responsive">
	      		<table class="table-bordered tabl-light table-striped" style="min-width: 1200px;" data-parentdivid="req_invoiceparentdiv" id="invoicerequestdetailstbl" style="font-size:12px;">
	      			<thead>
	        			<tr>
	        				<th style="text-align: center; width:50px;" rowspan="2">#</th>
	        				<th style="text-align: left; width:430px;" rowspan="2">Description as on Invoice</th>
	        				<th style="text-align: left; width:100px;" rowspan="2">Is for Stock</th>
	        				<th style="text-align: left; width:350px;" rowspan="2">Purpose</th>
	        				<th style="text-align: center; width:150px;" rowspan="2">HSN Code</th>
	        				<th style="text-align: center; width:100px; display: none;" rowspan="2">MRP</th>
	        				<th style="text-align: center; width:100px;" rowspan="2">Rate</th>
	        				<th style="text-align: center; width:100px;" rowspan="2">QTY</th>
	        				<th style="text-align: center; width:125px;" rowspan="2">Taxable Amount</th>
	        				<th style="text-align: center; width:100px;" colspan="1">CGST</th>
	        				<th style="text-align: center; width:100px;" colspan="1">SGST</th>
	        				<th style="text-align: center; width:100px;" colspan="1">IGST</th>
	        				<th style="text-align: center; width:150px;" colspan="2">Discount</th>
	        				<th style="text-align: center; width:125px;" rowspan="2">Amount</th>
	        			</tr>
	        			<tr>
	        				<th style="text-align: center; width:90px;">%</th>
	        				<!-- <th style="text-align: center; width:90px; display: none;">Amount</th> -->
	        				<th style="text-align: center; width:90px;">%</th>
	        				<!-- <th style="text-align: center; width:90px; display: none;">Amount</th> -->
	        				<th style="text-align: center; width:90px;">%</th>
	        				<!-- <th style="text-align: center; width:90px; display: none;">Amount</th> -->
	        				<th style="text-align: center; width:90px;">%</th>
	        				<th style="text-align: center; width:90px;">Amount</th>
	        			</tr>
	        		</thead>
	        		<tbody>
		      			<tr>
		      				<td style="padding: .02rem; text-align: center; width:50px;"><center><i class="fa fa-trash delete-btn" style="cursor:pointer; color: red;" onclick="deleteRowNew('invoicerequestdetailstbl');"></i></center></td>
		      				<td style="padding: .02rem; text-align: left; width:430px;"><input type="text" class="form-control description_td" /></td>
		      				<td style="padding: .02rem; text-align: center; width:100px;"><input type="checkbox" class="isforstock_td" /></td>
		      				<td style="padding: .02rem; text-align: left; width:350px;"><select class="form-control purpose_td"></select></td>
		      				<td style="padding: .02rem; text-align: center; width:150px;"><input type="text" class="form-control hsbn_td" /></td>
		      				<td style="padding: .02rem; text-align: center; width:100px; display: none;"><input type="text" class="form-control invrowcell_rm mrpcal mrp_td" onKeyPress="return isFloatNumber(event,this);" /></td>
		      				<td style="padding: .02rem; text-align: center; width:100px;"><input type="text" class="form-control invrowcell_rm ratecal rate_td" onKeyPress="return isFloatNumber(event,this);" /></td>
		      				<td style="padding: .02rem; text-align: center; width:100px;"><input type="text" class="form-control invrowcell_rm qtycal qty_td" onKeyPress="return isFloatNumber(event,this);" /></td>
		      				<td style="padding: .02rem; text-align: center; width:125px;"><input type="text" class="form-control txableamount_td" disabled /></td>
		      				<td style="padding: .02rem; text-align: center; width:50px;"><input type="text" class="form-control cgstcal cgstprt_td" onKeyPress="return isFloatNumber(event,this);" /></td>
		      				<td style="padding: .02rem; text-align: center; width:50px; display: none;"><input type="text" class="form-control cgstcal cgstamt_td" onKeyPress="return isFloatNumber(event,this);" /></td>
		      				<td style="padding: .02rem; text-align: center; width:50px;"><input type="text" class="form-control sgstcal sgstprt_td" onKeyPress="return isFloatNumber(event,this);" /></td>
		      				<td style="padding: .02rem; text-align: center; width:50px; display: none;"><input type="text" class="form-control sgstcal sgstamt_td" onKeyPress="return isFloatNumber(event,this);" /></td>
		      				<td style="padding: .02rem; text-align: center; width:50px;"><input type="text" class="form-control igstcal igstprt_td" onKeyPress="return isFloatNumber(event,this);" /></td>
		      				<td style="padding: .02rem; text-align: center; width:50px; display: none;"><input type="text" class="form-control igstcal igstamt_td" onKeyPress="return isFloatNumber(event,this);" /></td>
		      				<td style="padding: .02rem; text-align: center; width:90px;"><input type="text" class="form-control distcal distprt_td" onKeyPress="return isFloatNumber(event,this);" /></td>
		      				<td style="padding: .02rem; text-align: center; width:90px;"><input type="text" class="form-control distcal distamt_td" onKeyPress="return isFloatNumber(event,this);" /></td>
		      				<td style="padding: .02rem; text-align: center; width:125px;"><input type="text" class="form-control famount_td" onKeyPress="return isFloatNumber(event,this);" /></td>
		      			</tr>
	        		</tbody>
	        		<tfoot>
	        			<tr>
	        				<td colspan="9"></td>
	        				<td colspan="4">Total</td>
	        				<td id="totalamount"></td>
	        			</tr>
	        			<tr>
	        				<td colspan="9"></td>
	        				<td colspan="4">Total GST</td>
	        				<td id="gsttotalamount"></td>
	        			</tr>
	        			<tr style="display:none;">
	        				<td colspan="9"></td>
	        				<td colspan="4">Total Discount</td>
	        				<td id="ttldiscountamount"></td>
	        			</tr>
	        			<!-- <tr>
	        				<td colspan="9"></td>
	        				<td colspan="4">Total Other Discount</td>
	        				<td><input type="text" class="form-control" oninput="grandtotalgstamont('totalamount','gsttotalamount','ttldiscountamount','otherdiscounttotalamount','grandtotalamount','famount_td','cgstamt_td','sgstamt_td','igstamt_td','distamt_td','invoicerequestdetailstbl');"  id="otherdiscounttotalamount" /></td>
	        			</tr> -->
	        			<tr>
	        				<td colspan="9"></td>
	        				<td colspan="4">Grand Total</td>
	        				<td id="grandtotalamount"></td>
	        			</tr>
	        		</tfoot>
	      		</table>
	      	</div>
	      </div>
	    </div>
		</div>
		
		<div class="row mt-2 fromaccountdiv mdtdiv" style="display: <?php echo ($subaction=="postpayment"?"block":"none"); ?>;">
			<div class="col-md-12">
				<label>From Account<sup><i class="fa fa-star" style="font-size: 6px; color:red"></i></sup></label>
				<select id="inv_fromaccount" class="form-control"></select>
			</div>
		</div>
		<div class="row mt-2 paymentduedatediv" style="display: <?php echo ($subaction=="postpayment"?"none":"block"); ?>;">
	    	<div class="col-md-12">
	    		<label>Payment Due Date</label>
	    		<input type="date" class="form-control" name="inv_paymentduedate" id="inv_paymentduedate" min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" />
	    	</div>
	  </div>
		<div class="row mt-2 modeoftransferdiv">
			<div class="col-md-12">
				<label>Mode of Transfer</label>
				<select id="inv_modeofpayment" class="form-control"></select>
			</div>
		</div>
		<div class="row mt-2 transactionnodiv mdtdiv">
			<div class="col-md-12">
				<label><b id="chqnolbl">Transaction/Cheque number?</b></label>
				<input type="text" class="form-control" id="inv_transactionno" value="" />
			</div>
		</div>
		<div class="row mt-2 transactiondatediv mdtdiv">
			<div class="col-md-12">
				<label><b id="chqnolbl">Transaction/Cheque Date?</b></label>
				<input type="date" class="form-control" id="inv_transactiondate" max="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" />
			</div>
		</div>		
		<div class="row mt-2">
			<div class="col-md-12">
				<label>Voice Remark of Request</label>
			</div>
			<div class="col-md-12">
					<button class="btn-success btn-sm recording-btn mr-2" id="startRecord">&#9899;</button>
			    <button class="btn-danger btn-sm recording-btn" id="stopRecord" disabled>&#11035;</button>
		  </div>
		  <div class="col-md-12 mt-2">
		  	<div id="recordingTime" style="display: none;"><span id="timeElapsed">0</span> s</div>
		    <audio controlsList="nodownload" id="inv_audioPlayback" class="form-control inv_audioPlayback" controls></audio>
	  	</div>
	  </div>
		<div class="row mt-2">
			<div class="col-md-12">
				<label>Remark</label>
				<textarea id="inv_remark" rows="4" style="resize:none; width: 100%;"></textarea>
			</div>
		</div>
		<div class="row mt-2" style="display: <?php echo ($dz==1?"block":"none"); ?>;">
		  <div class="col-md-12">
			  <label>Do you update categorized attachement?</label>
					<label><input type='radio' name="updatecategorized" value="yes" checked /> YES</label>
					<label><input type='radio' name="updatecategorized" value="no" /> NO</label>
		  </div>
		</div>
		<div class="row mt-2" id="isponodiv" style="display:none;">
			<div class="col-md-12">
				<label>Enter the PO number(If available)</label><br/>
				<input type="text" class="form-control" name="ponumber" id="ponumber" />
			</div>
		</div>
		<div class="row mt-2 " style="display: none;">
			<div class="col-md-12">
				<label>Invoice/Voucher Type</label>
				<select id="inv_invoicetype" class="form-control invoicetype"></select>
			</div>
		</div>
		<div class="row mt-4">
			<div class="col-md-12 text-center">
				<span class="warningmsgspan"></span>
			</div>
			<div class="col-md-12 text-center">
				<button id="inv_savetodbbnt" data-isforproduction="" class="btn btn-info" value="<?php echo ($subaction=="postpayment"?"Save Book Entry":"Save Payment Request"); ?>"><?php echo ($subaction=="postpayment"?"Save Book Entry":"Save Payment Request"); ?></button>
			</div>
		</div>
	</div>
	<script type="text/javascript">
		var subprjid = "<?php echo $subprojectid; ?>";
		var alrd_transactionno = $("#htmlcontainerdiv").data("unrtransactionno");
		var alrd_transactiondate = $("#htmlcontainerdiv").data("unrtransactiondate");
		var alrd_transactionamount = $("#htmlcontainerdiv").data("unrtransactionamount");
		var alrd_likelypayto = $("#htmlcontainerdiv").data("unrlikelypayto");
		var alrd_bankdbsno = $("#htmlcontainerdiv").data("unrbankdbsno");
		var alrd_bankaccountid = $("#htmlcontainerdiv").data("unrbankaccountid"); //done
		var alrd_bankaccountno = $("#htmlcontainerdiv").data("unrbankaccountno");
		var alrd_bankaccounttype = $("#htmlcontainerdiv").data("unrbankaccounttype");
		var alrd_bankdescription = $("#htmlcontainerdiv").data("unrbankdescription");
		var alrd_bankname = $("#htmlcontainerdiv").data("unrbankname");
		$(document).on("change", "#inv_paytoname", function(){
			var pptid = $(this).val();
			$("#inv_paytoname").nextAll(".open_editpayto_modal").remove();
			ispaytodetailshave_callback("hidden_rooturl","hidden_companyid","hidden_subcompanyid",pptid,"taxdetails",function(res){
				// console.log(res);
				$("#inv_gstnumber").val('');
				$(".warningmsgspan").html('');
				$("#inv_savetodbbnt").show();
				if(res.length>0){
					for(var i=0; i<res.length; i++){
						$("#inv_paytoname").after('<span style="float:right; padding:4px; margin:4px; cursor:pointer;" class="open_editpayto_modal badge badge-pill badge-primary" data-epaytoid="'+res[i].paytoid+'"><i class="fa fa-user"></i> Edit</span>');
						if(res[i].status=="Error"){
							alertmessage('error',res[i].message);
							$(".warningmsgspan").html('<i class="text-danger">Payto tax details did not available, Click on edit and update GST/Vat Details before proceeding it.</i>');
							$("#inv_savetodbbnt").hide();
							return false;
						}else{
							$("#inv_gstnumber").val(res[i].taxid);
							$("#inv_savetodbbnt").show();
						}
					}
				}else{
					$(".warningmsgspan").html('<i class="text-danger">Payto tax details did not available, Click on edit and update GST/Vat Details before proceeding it.</i>');
					alertmessage('error','Payto tax details did not available');
					$("#inv_savetodbbnt").hide();
					return false;
				}
			});
		});

		$(document).on("change", "input[name='isforproductionradio']", function(){
			// $("#contaninerdiv").show();
			if($(this).val()=="yes"){
				$("#ispurposediv").hide();
				$("#inv_savetodbbnt").data("isforproduction", "yes");
			}else{
				// $("#ispurposediv").show();
				$("#inv_savetodbbnt").data("isforproduction", "");
				$(".purpose_td").prop("selectedIndex", 0).trigger("change");
				$("#inv_purposecode").prop("selectedIndex", 0).trigger("change");
			}
		});

		$(document).on("change", ".isforstock_td", function () {
      var row = $(this).closest("tr");
      var purposeSelect = row.find("select.purpose_td");
	    if ($(this).is(":checked")) {
	        if ($("input[name='isforproductionradio']:checked").val() === "yes") {
	            // Find the option ending with "Raw Material" and set it as selected
	            purposeSelect.find("option").each(function () {
	                if ($(this).text().trim().endsWith("Raw Material")) {
	                    purposeSelect.val($(this).val()).trigger("change");
	                    return false; // Exit loop
	                }
	            });
	        } else {
	            // Set the selected index to the first option
	            purposeSelect.prop("selectedIndex", 0).trigger("change");
	        }
	    } else {
          // Set the selected index to the first option
          purposeSelect.prop("selectedIndex", 0).trigger("change");
      }
		});

		//add new row if enter any cell of table
		addRowwithEnterCell("invoicerequestdetailstbl");

		if(alrd_likelypayto){
			 $('#inv_paytoname').before('<br/><span style="color:red;">We believe you have payto : '+alrd_likelypayto+'</span>');
		}
		var now = new Date().getTime();
		var microseconds = now;
		var lastFourDigits = microseconds.toString().slice(-4);
		
		// mdtdiv
		if(alrd_bankdbsno){
			$(".modeoftransferdiv").hide();
			if(alrd_transactionno){
				$("#inv_transactionno").val(alrd_transactionno);
				$(".transactionnodiv").hide();
			}else{
				$("#inv_transactionno").val("AG-"+lastFourDigits+"-"+alrd_transactiondate.replace(/-/g,''));
				$(".transactionnodiv").hide();
			}
			if(alrd_transactiondate){
				$("#inv_transactiondate").val(alrd_transactiondate.split(" ")[0]);
				$(".transactiondatediv").hide();
			}

			// $("#inv_amount").val(alrd_transactionamount);
			if(alrd_transactionamount){
				$("#inv_amount").val(alrd_transactionamount);
				$(".amounttopaydiv").hide();
			}
			// alert(alrd_bankaccountid);
			if(alrd_bankaccountid!=""){
				$(".fromaccountdiv").hide();
			}else{
				$(".fromaccountdiv").show();
			}
		}

		
		$("input[name='issinglepurpose']").on("click", function(){
			$("#inv_purposecode").prop("selectedIndex", 0);
			if($(this).val()=="yes"){
				$("#singlepurposediv").show();
			}else{
				$("#singlepurposediv").hide();
				$(".purpose_td").prop("selectedIndex", 0);
			}
		});
		
		subprojectdetails_callback("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid",function(res){
			if(res.length>0){
				if(res.length>1){
					$(".subprojectdiv").show();
					$("#inv_subproject").append("<option value='' disabled selected>Choose sub-project name</option>");
				}else{
					$(".subprojectdiv").hide();
				}
				for (var i = 0; i < res.length; i++) {
					$("#inv_subproject").append("<option value='"+res[i].subprojectid+"'>"+res[i].subprojectname+"</option>");
				}
				if(subprjid && res.length>1){
					$("#inv_subproject").val(subprjid).trigger("change");
				}
			}
		});

		if("<?php echo $paytotype; ?>"=="splr"){
			request_supplierpaytolist('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','inv_paytoname');
		}else{
			request_paytofrommaster_withpaytoid("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","inv_paytoname","",alrd_likelypayto);
		}


		getaccountdetailsofproject_frmaster("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","hidden_loginid","inv_fromaccount",alrd_bankaccountid);
		modeoftransferfrommaster_todropdown("hidden_baseurl","Online","inv_modeofpayment");	
		request_invoicetype("hidden_baseurl","Invoice","inv_invoicetype");
		// fill for single purpose dropdown
		getallpurposewithprojectidlist("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid",function(res){
				// console.log(res);
					if(res.length>0){
						$("#inv_purposecode").empty();
			    		if (res.length>1) 
		        	{
		            $("#inv_purposecode").append("<option value='' selected>Select purpose name</option>");
		        	}
	          	var substrings = ["loan", "imprest", "partial", "investment", "cash", "unknown", "reverse entry", "refund entry"]; // Substrings to check
							for (var i = 0; i < res.length; i++) {
							    // Check if the purpose contains any of the substrings
							    var containsAnySubstring = substrings.some(function (substring) {
							        let contains = res[i].purpose.toUpperCase().includes(substring.toUpperCase()); // Check if substring exists
							        // alert(substring + ": " + contains); // Alert the result for debugging
							        return contains; // Return the result for `some()`
							    });
							    // Append the option only if none of the substrings are found
							    if (!containsAnySubstring) {
							        $("#inv_purposecode").append(
							            "<option value='" + res[i].purposecode + "'>" +
							            res[i].provided_code + "-" + res[i].purpose +
							            "</option>"
							        );
							    }
								// If there's only one result, set the dropdown value and trigger change
								if (res.length === 1) {
								    $("#inv_purposecode").val(res[i].purposecode).trigger("change");
								}
							}
		        }
				});

		$(document).on("change", "#inv_purposecode", function(){
			let alreadyexistval = $("#inv_purposecode").val();
			if($("#inv_purposecode").val()){
				// $("#inv_purposecode").prop("selectedIndex", 0);
				getallpurposewithprojectidlist("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid",function(res){
					if(res.length>0){
							$(".purpose_td").empty();
			    		if (res.length>1) 
			        {
			          $(".purpose_td").append("<option value='' selected>Purpose name</option>");
			        }

			        var substrings = ["loan", "imprest", "partial", "investment", "cash", "unknown", "reverse entry", "refund entry"]; // Substrings to check
							for (var i = 0; i < res.length; i++) {
							    // Check if the purpose contains any of the substrings
							    var containsAnySubstring = substrings.some(function (substring) {
							        let contains = res[i].purpose.toUpperCase().includes(substring.toUpperCase()); // Check if substring exists
							        // alert(substring + ": " + contains); // Alert the result for debugging
							        return contains; // Return the result for `some()`
							    });
							    // Append the option only if none of the substrings are found
							    if (!containsAnySubstring) {
							        $(".purpose_td").append(
							            "<option value='" + res[i].purposecode + "'>" +
							            res[i].provided_code + "-" + res[i].purpose +
							            "</option>"
							        );
							    }
									// If there's only one result, set the dropdown value and trigger change
									if (res.length === 1) {
									    $(".purpose_td").val(res[i].purposecode).trigger("change");
									}
							}

	          	
	          	if(alreadyexistval){
	          		$(".purpose_td").val(alreadyexistval).trigger("change");
	          	}
	        }
				});
			}else{
				getallpurposewithprojectidlist("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid",function(res){
					if(res.length>0){
						$(".purpose_td").empty();
			    		if (res.length>1) 
		        	{
		            	$(".purpose_td").append("<option value='' selected>Purpose name</option>");
		        	}
	          	for (var i = 0; i < res.length; i++) {
			          $(".purpose_td").append("<option value='"+res[i].purposecode+"'>"+res[i].provided_code+"-"+res[i].purpose+"</option>");
			          if(res.length==1){
			          	$(".purpose_td").val(res[i].purposecode).trigger("change");
			          }
	          	}
		        }
				});
			}
		});

		// fill purpose dropdown in table cell as class
		getallpurposewithprojectidlist("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid",function(res){
			if(res.length>0){
				$(".purpose_td").empty();
	    		if (res.length>1) 
        	{
            	$(".purpose_td").append("<option value='' selected>purpose name</option>");
        	}
        	var substrings = ["loan", "inprest", "partial",  "investment", "cash"]; // Substrings to check
        	for (var i = 0; i < res.length; i++) {
        		var allExist = substrings.every(function(substring) {
						    if(!res[i].purpose.includes(substring)){
				          $(".purpose_td").append("<option value='"+res[i].purposecode+"'>"+res[i].provided_code+"-"+res[i].purpose+"</option>");
						    }
						});
	          if(res.length==1){
	          	$(".purpose_td").val(res[i].purposecode).trigger("change");
	          }
        	}
        }
		});

		$("#inv_savetodbbnt").on("click", function(){
			var paytoamount = $("#inv_amount").val();
			var paytoname = $("#inv_paytoname option:selected").text();
			var invgstnumber = $("#inv_gstnumber").val();
			let isconfirm=0;
			if(paytoamount=="" || paytoamount==null || paytoamount=="undefined"){
				alertmessage('error','Amount is missing');
				$("#inv_amount").focus();
				let isconfirm=0;
				return false;
			}else if(paytoname=="" || paytoname==null || paytoname=="undefined"){
				alertmessage('error','Payto name missing');
				let isconfirm=0;
				$("#inv_paytoname").focus();
				return false;
			}else if(invgstnumber=="" || invgstnumber==null || invgstnumber=="undefined"){
				// alertmessage('error','Payto/Suppliyer GST no missing');
				Swal.fire({
				  title: "Are you sure to continue without GST no?",
				  text: " GST number of "+paytoname+" did not update our master",
				  showDenyButton: true,
				  showCancelButton: true,
				  confirmButtonText: "YES",
				  denyButtonText: `NO`
				}).then((result) => {
				  /* Read more about isConfirmed, isDenied below */
				  if (result.isConfirmed) {
				    savetodb_requestfor_taxinvoicedata("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","inv_subproject","paymenttypeselection","inv_fromaccount","inv_paytoname","inv_gstnumber", "inv_amount","inv_invoicetype","inv_invoiceno","inv_invoicedate","invoicerequestdetailstbl", "inv_modeofpayment", "inv_transactionno", "inv_transactiondate", "inv_remark","inv_importfile4","inv_audioPlayback", "hidden_attpath_fromemail","hidden_attsno_fromemail","hidden_emailsno_fromemail","updatecategorized","hidden_instructionid","inv_paymentduedate","ponumber","inv_savetodbbnt");
				  } else if (result.isDenied) {
				    // Swal.fire("Changes are not saved", "", "info");
				    let isconfirm=0;
						return false;
				  }
				});
			}else{
				savetodb_requestfor_taxinvoicedata("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","inv_subproject","paymenttypeselection","inv_fromaccount","inv_paytoname","inv_gstnumber", "inv_amount","inv_invoicetype","inv_invoiceno","inv_invoicedate","invoicerequestdetailstbl", "inv_modeofpayment", "inv_transactionno", "inv_transactiondate", "inv_remark","inv_importfile4","inv_audioPlayback", "hidden_attpath_fromemail","hidden_attsno_fromemail","hidden_emailsno_fromemail","updatecategorized","hidden_instructionid","inv_paymentduedate","ponumber","inv_savetodbbnt");
			}
			
		});
		$('.select2').select2();
		$('.select2-selection').css('border-radius','0px');
		$('.select2-container').children().css('border-radius','0px');
		$('.select2-selection').css('height','36px');
	</script>