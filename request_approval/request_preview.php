<?php
require('../header.php');
require('../functions/request_fn_approval.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);
$requestno = base64_decode($_GET['requestno']);
$mdid ="";
if(isset($_GET['mdid'])){
	$mdid = $_GET['mdid'];
}

// echo "-----".$_SERVER['HTTP_HOST'];
?>
<input type="hidden" id="currentfinyear" value="<?php echo getcurrentfinancialyear(date('Y-m-d'),$format="Y"); ?>" />
<div class="main-container">	
<?php
$reqdbsno =""; 
$chekinmain=check_maintransactionamount_ttlbreakdowntamount($conn,$companyid,$subcompanyid,$projectid,'requestno',$requestno);
if(count($chekinmain)>0){
	foreach ($chekinmain as $tchkey) {
		echo "<div class='text-center'>";
		if($tchkey['book_amount']!=$tchkey['ttlbreakdownamount']){
			echo "<label><input type='checkbox' id='isconfirmremovefrommain' /> Maintransaction Amount and Breakdown amount did not matched, Do you want to remove and procced it again.</label>";
			echo "<button class='btn btn-success' id='updateapproverequest'>Remove the details of request no ".$requestno." from</button>";
		}else{
			if(count($chekinmain)==1){
				$conn->query("update tbl_paymentrequest set approval_status='Payment Paid', status_by='".$loginname."',paid_for_ref='".$tchkey['reference_no']."', requestapprovedate='".date('Y-m-d h:i:s')."' where request_no='".$requestno."'");
				echo "<script>window.location.href='".$rooturl."request_approval/pending_approval.php?k=".$_GET['k']."&mdid=".$mdid."';</script>";
				
			}else{
				echo "There are multiple entry available in Maintransaction, Pleasae stop this process and contact to web administrator for check it.";
			}
		}
		echo "</div>";
	}
	die();
}

$rrrshortby='requestno';
$rrrshortbyvalue=$requestno;
$requestbreakdowninfo = getrequestbreakdowndetails($conn,$companyid,$subcompanyid,$projectid,$rrrshortby,$rrrshortbyvalue);
$rowscount=count($requestbreakdowninfo);
echo '<input type="hidden" id="invoicedivno" value="'.$rowscount.'">';
echo '<input type="hidden" id="brkcount" value="'.$rowscount.'">';
echo '<input type="hidden" id="reqno" value="'.$requestno.'">';
echo '<input type="hidden" id="catid" value="'.$requestbreakdowninfo[0]['purpose_categoryid'].'">';
if($rowscount>1){
	$hideshow="";
}else{
	$hideshow="d-none";
}

$limit = 1;
$paytobasicinfo = getrequestdetails($conn,$companyid,$subcompanyid,$projectid,'requestno',$requestno);
// print_r($paytobasicinfo);

$pytonametemp="";
$paymentduedate="";
$paytoid="";
foreach ($paytobasicinfo as $ptbrow) {
	$reqdbsno = $ptbrow['sno'];
	$pytonametemp = $ptbrow['payto'];
	$paytoid = $ptbrow['paytoid'];
	$paymentduedate = $ptbrow['paymentduedate'];
	if($ptbrow['approval_status']){
		if(strtolower($ptbrow['approval_status'])=="payment paid" || strtolower($ptbrow['approval_status'])=="request rejected"){
			echo "<script>window.location.href='".$rooturl."/request_approval/pending_approval.php?k=".$_GET['k']."&mdid=".$mdid."';</script>";
			die();
		}else if(strtolower($ptbrow['approval_status'])=="added in waiting"){
		?>
			<div class="row">
				<div class="col-md-12 text-center mt-4">
					<h3 class="text-warning bold"> REQUEST NUMBER :: <?php echo $requestno; ?> IS ADDED IN WAITING LIST</h3>
					<a class="btn btn-sm btn-deepblue float-right" href="pending_approval.php?k=<?php echo $key; ?>&mdid=<?php echo $mdid; ?>">Back</a>
				</div><br>
			</div>
		<?php
		}
	}else{
	?>
		<div class="row">
			<div class="col-md-12 text-center mt-4">
				<h3 class=" bold"> REQUEST NUMBER :: <?php echo $requestno; ?> </h3>
				<a class="btn btn-sm btn-deepblue float-right" href="pending_approval.php?k=<?php echo $key; ?>&mdid=<?php echo $mdid; ?>">Back</a>
			</div><br>
		</div>
		<div class="row mt-2">
			<div class="col-md-12">
			<?php
			$chkintbnk=checkPaytoIsInternalBak($conn, $companyid, $subcompanyid, $projectid, $ptbrow['paytoid']);
			$imprstss="";
			$partpayment="";
			$paymentONaccount="";
			$loanpayment="";
			$interbank = "";
			$internalbanktr ="";
			$cashbanktr="";
			if(count($chkintbnk)>0){
				foreach ($chkintbnk as $cckkey) {
					if(strtolower($cckkey['account_type'])=="bank" && (strtolower($cckkey['account_type'])=="expenditure" ||  strtolower($cckkey['account_type'])=="payment")){
						$interbank = "is a Internal Bank Account";
						$war_message = "<span style='color: red;'> <b>".$pytonametemp."</b> is a internal bank, So you can not procced this request as ".$ptbrow['purpose_type']."</span>";
						$war_action="<span class='badge badge-warning m-2' onclick=\"ChangeCategoryOfRequestModal('hidden_rooturl','','','')\"  style='cursor:pointer;'>Request converte as internal transfer within the project</span>";
						$internalbanktr = "<tr data-paytoid='".$ptbrow['paytoid']."' data-fromaccountid=''><td>Internal-Bank</td><td>".$war_message."</td><td>N/A</td><td>".$war_action."</td></tr>";  
					}else if(strtolower($cckkey['account_type'])=="bank"){
						$interbank = "is a Internal Bank Account";
						$war_message = "<span style='color: green;'> <b>".$pytonametemp."</b> is a internal bank for ".$ptbrow['purpose_type']."</span>";
						 $war_statementbtn="<span class='badge badge-primary m-2'>N/A</span>";
						$war_action="<span class='badge badge-warning m-2'>N/A</span>";
						$internalbanktr = "<tr data-paytoid='".$ptbrow['paytoid']."' data-fromaccountid=''><td>Internal-Bank</td><td>".$war_message."</td><td>".$war_statementbtn."</td><td>".$war_action."</td></tr>";  
					}
					$loanpayment = PaytoHaveLoan($conn,$companyid,$subcompanyid,$projectid,$ptbrow['paytoid'],$ptbrow['purpose_type'],$ptbrow['request_amount']);
					$imprstss = PaytoHaveImprestAccount($conn,$companyid,$subcompanyid,$projectid,$ptbrow['paytoid'],$ptbrow['purpose_type'],$ptbrow['request_amount']);
					$cashbanktr = PaytoHaveCashAccount($conn,$companyid,$subcompanyid,$projectid,$ptbrow['paytoid'],$ptbrow['purpose_type'],$ptbrow['request_amount']);
				}
			}
			$partpayment = PaytoHavePartPayment($conn,$companyid,$subcompanyid,$projectid,$ptbrow['paytoid'],$ptbrow['purpose_type'],$ptbrow['request_amount']);
			$paymentONaccount = PaytoHavePaymentOnAccount($conn,$companyid,$subcompanyid,$projectid,$ptbrow['paytoid'],$ptbrow['purpose_type'],$ptbrow['request_amount']);
			
			if($imprstss!="" || $partpayment!="" || $paymentONaccount!="" || $loanpayment!="" || $interbank!="" || $cashbanktr!=""){
				echo "<table class='table table-sm table-light'>
					<thead>
						<tr>
							<th colspan='4'>ACCOUNT'S OF ".strtoupper($pytonametemp)." ".$interbank."</th>
						</tr>
						<tr>
							<th>Account Type</th>
							<th>Message</th>
							<th>Statement</th>
							<th>Action</th>
						</tr>
					</thead>
					<tbody>
					".$imprstss."
					".$partpayment."
					".$loanpayment."
					".$internalbanktr."
					".$cashbanktr."
					".$paymentONaccount."
					</tbody>
				</table>";	
			}
			?>
			</div>
		</div>
	<?php 
	}
	if(count($requestbreakdowninfo)==1){
		$invicesno = 0;
		$prv = 0;
		foreach ($requestbreakdowninfo as $dtrow) {
			trim($prv++);			
			$beneficiaryname = $dtrow['beneficiery_name'];
			$invoiceamount = $dtrow['ammount'];
			$invoicetaxableamount=$dtrow['taxableamount'];
			$purpose = $dtrow['purpose'];
			$purposecode = $dtrow['purpose_code'];

			$ccppt=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposecode",$dtrow['purpose_code']);
			// print_r($ccppt);
			$clientpurposecategory=$dtrow['purpose_subcategory'];
			$clientpurposename=$dtrow['purpose'];
			if(count($ccppt)>0){
				$clientpurposecategory=$ccppt[0]['sub_category_client']!=""?$ccppt[0]['sub_category_client']:$dtrow['purpose_subcategory'];
				$clientpurposename = $ccppt[0]['purposewithprovidedcode'];
			}

			$adioview='';
			if($dtrow['audiofilepath']){
				$adioview='<p><b>Audio File</b><br><audio controlsList="nodownload" src="../'.$dtrow['audiofilepath'].'" class="form-control" controls></audio></p>';
			}
			$voiceinstview="";
			if($dtrow['voiceinstid']){
				$vvinfo=voiceinstructiondetails($authconn,$conn,$companyid,$subcompanyid,$projectid,"instructionid",$dtrow['voiceinstid']);
				if(count($vvinfo)>0){
					foreach ($vvinfo as $vvkey) {
						$voiceinstview='<button class="btn btn-sm vwInst" data-instructionid="'.$dtrow['voiceinstid'].'"">View instruction</button>';
					}
				}
			}
			$invicesno++;
		}
	?>
		<details id="basicdetailsdivc" open>
			<summary>REQUEST INFORMATION</summary>
			<div class="card">
				<div class="card-body">
					<div class="row">
						<div class="col-md-12 text-center">
							<?php
							if($dtrow['purpose_categoryid']==1){
								echo NotContainsKeywordforPaymentRequest($ptbrow['purpose_type'])!=""?"<h6 style='color:red;'>Please check if this request is valid for an Expense Request or another type of request.</h6>":"";
							} 
							?>
						</div>
						<div class="col-md-6">
							<p><b>Request Number : </b> <span id="requestno" class="text-primary"><?php echo strtoupper($ptbrow['request_no']); ?></span></p>
							<p><b>Request Date : </b> <?php echo date('d-M-Y', strtotime($ptbrow['request_date'])); ?></p>
							<p><b class="req_lbl_amount">Requested Amount : </b> <span id="ttl_requestedamont_span"><?php echo str_replace(",", "", $ptbrow['request_amount']); ?></span></p>
							<p><b class="req_lbl_paytoname">Payto Name : </b> <span id="paytoname"><?php echo $ptbrow['payto']; ?></span></p>

							<p style="display:none;"><b>Payto ID : </b> <span id="paytoid"><?php echo ucwords(strtolower($ptbrow['payto'])); ?></span></p>
							<p><b>Request Category : </b> <span id="purposetype" class="text-primary"><?php echo ucwords(strtolower($ptbrow['purpose_type'])); ?></span></p>
							<?php
							/*$subcc=$dtrow['purpose_categoryid'];
							$catlist=['3','4','5','6','7','8','9','10','11','12'];
							if (in_array($subcc, $catlist)) {
							?>
								<p><b>Request Category : </b> <span id="purposetype" class="text-primary"><?php echo ucwords(strtolower($ptbrow['purpose_type'])); ?></span></p>
							<?php 
							}else{
							?>
								<p><b>Request Category &nbsp;&nbsp;<sup><i class="fa fa-edit" style="color:blue; font-size:16px; cursor:pointer;" onclick="ChangeCategoryOfRequestModal('hidden_rooturl','<?php echo $ptbrow['request_no']; ?>','<?php echo $dtrow['purpose_categoryid']; ?>','<?php echo count($requestbreakdowninfo); ?>');"></i></sup> : </b> <span id="purposetype" class="text-primary"><?php echo ucwords(strtolower($ptbrow['purpose_type'])); ?></span></p>
							<?php
							}*/
							?>
							<input type="hidden" id="bsno_<?php echo $prv; ?>" value="<?php echo $dtrow['sno']; ?>" />
							<p class="<?php echo strtolower(trim($ptbrow['payto']))==strtolower(trim($beneficiaryname))?'d-none':''; ?>"><b>Beneficiary Name : </b> <span id="bnfname_<?php echo $prv; ?>"><?php echo $beneficiaryname; ?></span></p>

							<?php  
							if($dtrow['purpose_subcategoryid']==21){
							?>
								<p class="d-none"><b>Purpose Category Code : </b> <span id="purposecategorycode_<?php echo $prv; ?>"><?php echo removeZeroBeforeTen($dtrow['purpose_categoryid']); ?></span></p>

								<p class="d-none"><b>Purpose Category : </b> <span id="purposecategory_<?php echo $prv; ?>"><?php echo $dtrow['purpose_category']; ?></span></p>

								<p class="d-none"><b>Purpose Sub Category Id : </b> <span id="purposesubcoategorycode_<?php echo $prv; ?>"><?php echo removeZeroBeforeTen($dtrow['purpose_subcategoryid']); ?></span></p>
								
								<p><b>Purpose Category : </b> <span id="purposesubcoategory_<?php echo $prv; ?>"><?php echo $clientpurposecategory; ?></span></p>

								<p class="d-none"><b>Purpose Code : </b> <span id="purposecode_<?php echo $prv; ?>"><?php echo $dtrow['purpose_code']; ?></span></p>

								<p><b>Purpose Name : </b> <span id="purpose_<?php echo $prv; ?>"><?php echo $clientpurposename; ?></span></p>
								<i class="text-danger"><b>Warning:</b> This request is submitted under the general voucher category in accordance with your specified purpose.</i><br/>

								<label class="text-primary">"Do you think this request belongs to any of the request categories?</label></br/>
								<label><input type="radio" name="changetherequestcategory" value="yes" onclick="ChangeCategoryOfRequestModal('hidden_rooturl','<?php echo $ptbrow['request_no']; ?>','<?php echo $dtrow['purpose_categoryid']; ?>','<?php echo count($requestbreakdowninfo); ?>');"> YES</label>
								<label><input type="radio" name="changetherequestcategory" value="no" onclick="ChangeCategoryOfRequestModal('hidden_rooturl','','<?php echo $dtrow['purpose_categoryid']; ?>','<?php echo count($requestbreakdowninfo); ?>');" checked> NO</label>
								
								
							<?php  
							}else{
							?>
								<p class="d-none"><b>Purpose Category Code : </b> <span id="purposecategorycode_<?php echo $prv; ?>"><?php echo removeZeroBeforeTen($dtrow['purpose_categoryid']); ?></span></p>

								<p class="d-none"><b>Purpose Category : </b> <span id="purposecategory_<?php echo $prv; ?>"><?php echo $dtrow['purpose_category']; ?></span></p>

								<p class="d-none"><b>Purpose Sub Category Id : </b> <span id="purposesubcoategorycode_<?php echo $prv; ?>"><?php echo removeZeroBeforeTen($dtrow['purpose_subcategoryid']); ?></span></p>
								
								<p class="d-none"><b>Purpose Sub Category : </b> <span id="purposesubcoategory_<?php echo $prv; ?>"><?php echo $dtrow['purpose_subcategory']; ?></span></p>

								<p class="d-none"><b>Purpose Code : </b> <span id="purposecode_<?php echo $prv; ?>"><?php echo $dtrow['purpose_code']; ?></span></p>
								
								<p><b>Purpose Name : </b> <span id="purpose_<?php echo $prv; ?>"><?php echo $dtrow['purpose']; ?></span></p>
							<?php
							}
							?>
							
							
							<p class="<?php echo strtolower(trim($ptbrow['request_amount']))==strtolower(trim($invoiceamount))?'d-none':''; ?>"><b class="bnf_lbl_amount">Invoice/Voucher Amount : </b> <span id="requestedamount_<?php echo $prv; ?>"><?php echo $invoiceamount; ?></span></p>

							<p class="<?php echo strtolower(trim($ptbrow['request_amount']))==strtolower(trim($invoicetaxableamount))?'d-none':''; ?>"><b class="bnf-_lbl_taxableamount">Taxable Amount : </b> <span id="taxableamount_<?php echo $prv; ?>"><?php echo $invoicetaxableamount; ?></span></p>
							
							<?php
							if($dtrow['invoice_type']!="" && $dtrow['invoice_type']!="null"){
								echo '<p><b>Invoice Type : </b> <span id="invoicetype_'.$prv.'">'.$dtrow['invoice_type'].'</span></p>';
							 }
							if($dtrow['invoice_no']!=""){	
								echo '<p><b>Invoice No : </b> <span id="invoiceno_'.$prv.'">'.$dtrow['invoice_no'].'</span></p>';	
							}
							if($dtrow['invoice_date']!="" && $dtrow['invoice_date']!="0000-00-00"){	
								echo '<p><b>Invoice Date : </b> <span id="invoicedate_'.$prv.'">'.$dtrow['invoice_date'].'</span></p>';	
							}
							echo '<p><b>Description : </b> <span id="description_'.$prv.'">'.$dtrow['description'].'</span></p>';

							echo $adioview;
							 
							if($dtrow['purpose_categoryid']==1){
							?>
								<p><b>Does this amount include TDS?</b></p>
								<p>
									<label><input type="radio" id="tdsradioyes_<?php echo $prv; ?>" name="istdsapplicable_<?php echo $prv; ?>" class="istdsapplicable" value="yes">&nbsp;&nbsp; YES</label>
									<label>&nbsp;&nbsp;&nbsp;<input type="radio" id="tdsradiono_<?php echo $prv; ?>" name="istdsapplicable_<?php echo $prv; ?>" class="istdsapplicable" value="no">&nbsp;&nbsp;NO</label>
								</p>									
								<p class="coltdspara" id="tdscategorydiv_<?php echo $prv; ?>" style="display: none;">
									<label>TDS Category &nbsp;&nbsp;&nbsp;<span class=" text-primary text-right tdsviewspan" style="cursor: pointer;" id="tdsviewspan_<?php echo $prv; ?>"><i class="fa fa-book"></i>click to tds details</span></label>
									<span class=" text-danger pull-right bnftdshitory" style="cursor: pointer;" id="bnftdshitory_<?php echo $prv; ?>"><i class="fa fa-book"></i>click to tds history of beneficiary</span>
									<select class="form-control select2 tdscategory" style="width:100%" id="tdscategory_<?php echo $prv; ?>">
										<option value="" selected disabled>Select TDS Type</option>
										<?php
										$tdslist = getTdsTypesMasterData($conn, 'all','all');
										foreach ($tdslist as $tdkey) {
											echo "<option value='".$tdkey['id']."' data-tdsrate='".$tdkey['tds_rate']."' data-sectionwithpercentval='".$tdkey['section'].'|'.$tdkey['tds_rate']."'>".$tdkey['section'].' | '.$tdkey['tds_rate'].' ('.$tdkey['nature_of_payment'].")</option>";
										}
										?>
									</select>
								</p>
								<p class="tdspara" id="tdsdiv_<?php echo $prv; ?>" style="display: none;">
									<label>TDS (in %)</label>
									<input type="text" name="tdspercent" class="form-control tdspercent" id="tdspercent_<?php echo $prv; ?>" onkeyup="tdsdeduction(this.id,this.value);">
								</p>
							<?php 
							}
							?>
							<?php
							if($paymentduedate){
							 ?>
							<p class="coltext-warning"><b>Payment Due Date : </b> <span id="paymentduedate"><?php echo isValidDate($paymentduedate,'Y-m-d')==1?date('d-M-Y', strtotime($paymentduedate)):"N/A"; ?></span></p>
							<?php } ?>
							<?php
							if(strtolower($ptbrow['payment_mode'])=="electronic transfer" || strtolower($ptbrow['payment_mode'])=="online"){
								if($ptbrow['payto_accountno']){
								echo '<p class="d-none"><b>Payto Bank Name :</b> '.$ptbrow['payto_bankname'].'</p>';	
								echo '<p class="d-none"><b>Payto Account Number :</b> '.$ptbrow['payto_accountno'].'</p>';	
								echo '<p class="d-none"><b>Bank IFSC Code :</b> '.$ptbrow['payto_bankifsccode'].'</p>';
								}	
							}
							echo '<p class><b>Mode of Transfer</b> &nbsp;&nbsp;<sup><i class="fa fa-edit" style="color:blue; font-size:16px; cursor:pointer;" onclick="changemodeoftransfe(\'yes\')"></i></sup> : <span id="modeoftransfer">'.($ptbrow['payment_mode']!=""?$ptbrow['payment_mode']:"N/A").'</span></p>';					
							?>
							<p class="<?php echo strtolower(trim($ptbrow['request_amount']))==strtolower(trim($invoiceamount))?'d-none':''; ?>" id="amounttopayp_<?php echo $prv; ?>" style='display: block;'><b>Amount to be Pay: </b> <span class="amounttopayp" id="amounttopay_<?php echo $prv; ?>"><?php echo $invoiceamount; ?></span></p>				
						</div>
						<div class="col-md-6">
							<?php
							if(($dtrow['documenttype']=="" || $dtrow['documenttype']==null) && strlen($dtrow['filepath'])>20){
								if (file_exists("../".$dtrow['filepath'])) {
							?>
								<iframe src="<?php echo $dtrow['filepath']?$rooturl.$dtrow['filepath']:""; ?>"  id="attachment_<?php echo $prv; ?>"  style="width: 100%; height: 460px;"></iframe>
							<?php	    
								} else {
								    echo "<h2 style='margin-top:25%; margin-left:25%;'>The file does not exist</h2>";
								}
							?>
							<?php
							}else if(strlen($dtrow['filepath'])>20 && strtolower($dtrow['documenttype'])=="physical"){
								if (file_exists("../".$dtrow['filepath'])) {
							?>
								<iframe src="<?php echo $dtrow['filepath']?$rooturl.$dtrow['filepath']:""; ?>"  id="attachment_<?php echo $prv; ?>"  style="width: 100%; height: 460px;"></iframe>
							<?php	    
								} else {
								    echo "<h2 style='margin-top:25%; margin-left:25%;'>The file does not exist</h2>";
								}
							?>
							<?php
							}else{
								echo "<h2 style='margin-top:25%; margin-left:25%;'>NO Document</h2>";
							}
							?>
						</div>			
					</div>
					<div class="row">
					<div class="col-md-12">
						<div class="form-group text-center">
							<p id="hiddenbsno_<?php echo $prv; ?>" style="display: none;"><?php echo $dtrow['sno']; ?></p>
							<button type="button" name="editbutton" class="editbutton btn btn-deepblue btn-sm btn-round pull-right m-2" id="editbutton_<?php echo $prv; ?>" value="Edit of <?php echo $invicesno; ?>" onclick="editinvoiceinfo(this.id);" style="min-width: 100px;">Edit</button>
						</div>
					</div>
				</div>
				</div>
			</div>
		</details>
	<?php
	}else{
	?>
		<details id="basicdetailsdivc" open>
			<summary>REQUEST INFORMATION</summary>
			<div class="card">
				<div class="card-body">
					<div class="row">
						<div class="col-md-6">
							<p><b>Request Number : </b> <span id="requestno" class="text-primary"><?php echo strtoupper($ptbrow['request_no']); ?></span></p>
							<p><b>Request Date : </b> <?php echo date('d-M-Y', strtotime($ptbrow['request_date'])); ?></p>
							<p><b class="req_lbl_amount">Requested Amount : </b> <span id="ttl_requestedamont_span"><?php echo str_replace(",", "", $ptbrow['request_amount']); ?></span></p>
							
							<p><b class="req_lbl_paytoname">Payto Name : </b> <span id="paytoname"><?php echo ucwords(strtolower($ptbrow['payto'])); ?></span></p>

							<p style="display:none;"><b>Payto ID : </b> <span id="paytoid"><?php echo ucwords(strtolower($ptbrow['payto'])); ?></span></p>
						</div>
						<div class="col-md-6">
							<?php
							$subcc='1';
							$catlist=['3','4','5','6','7','8','9','10','11','12'];
							if (in_array($subcc, $catlist)) {
							?>
								<p><b>Request Category : </b> <span id="purposetype" class="text-primary"><?php echo ucwords(strtolower($ptbrow['purpose_type'])); ?></span></p>
							<?php 
							}else{
							?>
								<p><b>Request Category &nbsp;&nbsp;<sup><i class="fa fa-edit" style="color:blue; font-size:16px; cursor:pointer;" onclick="ChangeCategoryOfRequestModal('hidden_rooturl','<?php echo $ptbrow['request_no']; ?>','1','<?php echo count($requestbreakdowninfo); ?>');"></i></sup> : </b> <span id="purposetype" class="text-primary"><?php echo ucwords(strtolower($ptbrow['purpose_type'])); ?></span></p>
							<?php
							}
							?>
							
							<?php
							if(strtolower($ptbrow['payment_mode'])=="electronic transfer" || strtolower($ptbrow['payment_mode'])=="online"){
								if($ptbrow['payto_accountno']){
								echo '<p class="d-none"><b>Payto Bank Name :</b> '.$ptbrow['payto_bankname'].'</p>';	
								echo '<p class="d-none"><b>Payto Account Number :</b> '.$ptbrow['payto_accountno'].'</p>';	
								echo '<p class="d-none"><b>Bank IFSC Code :</b> '.$ptbrow['payto_bankifsccode'].'</p>';
								}	
							}
							echo '<p class><b>Mode of Transfer</b> &nbsp;&nbsp;<sup><i class="fa fa-edit" style="color:blue; font-size:16px; cursor:pointer;" onclick="changemodeoftransfe(\'yes\')"></i></sup> : <span id="modeoftransfer">'.($ptbrow['payment_mode']!=""?$ptbrow['payment_mode']:"N/A").'</span></p>';					
							?>

							<?php
							if($paymentduedate){
							 ?>
							<p class="coltext-warning"><b>Payment Due Date : </b> <span id="paymentduedate"><?php echo isValidDate($paymentduedate,'Y-m-d')==1?date('d-M-Y', strtotime($paymentduedate)):"N/A"; ?></span></p>
							<?php } ?>

						</div>			
					</div>
				</div>
			</div>
		</details>
		<?php
		$invicesno = 0;
		$prv = 0;
		foreach ($requestbreakdowninfo as $dtrow) {
			trim($prv++);
			$beneficiaryname = $dtrow['beneficiery_name'];
			$invoiceamount = $dtrow['ammount'];
			$invoicetaxableamount=$dtrow['taxableamount'];
			$purpose = $dtrow['purpose'];
			$purposecode = $dtrow['purpose_code'];
			$adioview='';
			if($dtrow['audiofilepath']){
				$adioview='<p class="col-lg-12"><b>Audio File</b><br><audio controlsList="nodownload" src="../'.$dtrow['audiofilepath'].'" class="form-control" controls></audio></p>';
			}
			$voiceinstview="";
			if($dtrow['voiceinstid']){
				$vvinfo=voiceinstructiondetails($authconn,$conn,$companyid,$subcompanyid,$projectid,"instructionid",$dtrow['voiceinstid']);
				if(count($vvinfo)>0){
					foreach ($vvinfo as $vvkey) {
						$voiceinstview='<button class="btn btn-sm vwInst" data-instructionid="'.$dtrow['voiceinstid'].'"">View instruction</button>';
					}
				}
			}

			$invicesno++;
			?>
			<details id="details_<?php echo $prv; ?>">
				<summary>INVOICE/VOUCHER <?php echo $invicesno; ?></summary>
				<div class="row bg-light">
					<div class="col-md-6 mt-2">
						<input type="hidden" id="bsno_<?php echo $prv; ?>" value="<?php echo $dtrow['sno']; ?>">
						<div class="form-group">
							<p><b>Beneficiary Name : </b> <label id="bnfname_<?php echo $prv; ?>"><?php echo $beneficiaryname; ?></label></p>
							<p><b class="bnf_lbl_amount">Invoice/Voucher Amount : </b> <label id="requestedamount_<?php echo $prv; ?>"><?php echo $invoiceamount; ?></label></p>
							<p class="<?php echo strtolower(trim($ptbrow['request_amount']))==strtolower(trim($invoicetaxableamount))?'d-none':''; ?>"><b class="bnf-_lbl_taxableamount">Taxable Amount : </b> <label id="taxableamount_<?php echo $prv; ?>"><?php echo $invoicetaxableamount; ?></label></p>
							
							<p style="display:none;"><b>Purpose Category Code : </b> <label id="purposecategorycode_<?php echo $prv; ?>"><?php echo removeZeroBeforeTen($dtrow['purpose_categoryid']); ?></label></p>

							<p class="d-none"><b>Purpose Category : </b> <label id="purposecategory_<?php echo $prv; ?>"><?php echo $dtrow['purpose_category']; ?></label></p>
							<p class="d-none" style="display:none;"><b>Purpose Sub Category Id : </b> <label id="purposesubcoategorycode_<?php echo $prv; ?>"><?php echo removeZeroBeforeTen($dtrow['purpose_subcategoryid']); ?></label></p>
							
							<p class="d-none"><b>Purpose Sub Category : </b> <label id="purposesubcoategory_<?php echo $prv; ?>"><?php echo $dtrow['purpose_subcategory']; ?></label></p>

							<p class="d-none" style="display: none;"><b>Purpose Code : </b> <label id="purposecode_<?php echo $prv; ?>"><?php echo $dtrow['purpose_code']; ?></label></p>
							
							<p><b>Purpose Name : </b> <label id="purpose_<?php echo $prv; ?>"><?php echo $dtrow['purpose']; ?></label></p>

							<p><b>Does this amount include TDS?</b></p>
							<p>
								<label><input type="radio" id="tdsradioyes_<?php echo $prv; ?>" name="istdsapplicable_<?php echo $prv; ?>" class="istdsapplicable" value="yes">&nbsp;&nbsp; YES</label>
								<label>&nbsp;&nbsp;&nbsp;<input type="radio" id="tdsradiono_<?php echo $prv; ?>" name="istdsapplicable_<?php echo $prv; ?>" class="istdsapplicable" value="no">&nbsp;&nbsp;NO</label>
							</p>
								
							<p class="tdspara" id="tdscategorydiv_<?php echo $prv; ?>" style="display: none;">
								<label>TDS Category &nbsp;&nbsp;&nbsp;<span class=" text-primary text-right tdsviewspan" style="cursor: pointer;" id="tdsviewspan_<?php echo $prv; ?>"><i class="fa fa-book"></i>click to tds details</span></label>
								<span class=" text-danger pull-right bnftdshitory" style="cursor: pointer;" id="bnftdshitory_<?php echo $prv; ?>"><i class="fa fa-book"></i>click to tds history of beneficiary</span>
								<select class="form-control select2 tdscategory" style="width:100%" id="tdscategory_<?php echo $prv; ?>">
									<option value="" selected disabled>Select TDS Type</option>
									<?php
									$tdslist = getTdsTypesMasterData($conn, 'all','all');
									foreach ($tdslist as $tdkey) {
										echo "<option value='".$tdkey['id']."' data-tdsrate='".$tdkey['tds_rate']."' data-sectionwithpercentval='".$tdkey['section'].'|'.$tdkey['tds_rate']."'>".$tdkey['section'].' | '.$tdkey['tds_rate'].' ('.$tdkey['nature_of_payment'].")</option>";
									}
									?>
								</select>
							</p>
							<p class="tdspara" id="tdsdiv_<?php echo $prv; ?>" style="display: none;">
								<label>TDS (in %)</label>
								<input type="text" name="tdspercent" class="form-control tdspercent" id="tdspercent_<?php echo $prv; ?>" onkeyup="tdsdeduction(this.id,this.value);">
							</p>

							
							<p class="<?php echo strtolower(trim($ptbrow['request_amount']))==strtolower(trim($invoiceamount))?'d-none':''; ?>" id="amounttopayp_<?php echo $prv; ?>" style='display: block;'><b>Amount to be Pay: </b> <label class="amounttopayp" id="amounttopay_<?php echo $prv; ?>"><?php echo $invoiceamount; ?></label></p>

							<?php
							if($dtrow['invoice_type']!="" && $dtrow['invoice_type']!="null"){
								echo '<p><b>Invoice Type : </b> <label id="invoicetype_'.$prv.'">'.$dtrow['invoice_type'].'</label></p>';
							 }
							if($dtrow['invoice_no']!=""){	
								echo '<p><b>Invoice No : </b> <label id="invoiceno_'.$prv.'">'.$dtrow['invoice_no'].'</label></p>';	
							}
							if($dtrow['invoice_date']!="" && $dtrow['invoice_date']!="0000-00-00"){	
								echo '<p><b>Invoice Date : </b> <label id="invoicedate_'.$prv.'">'.$dtrow['invoice_date'].'</label></p>';	
							}
							echo '<p><b>Description : </b> <label id="description_'.$prv.'">'.$dtrow['description'].'</label></p>';

							echo $adioview;
							?>
						</div>
					</div>
					<div class="col-md-6 mt-2">
						<?php
						if(($dtrow['documenttype']=="" || $dtrow['documenttype']==null) && strlen($dtrow['filepath'])>20){
							if (file_exists("../".$dtrow['filepath'])) {
						?>
							<iframe src="<?php echo $dtrow['filepath']?$rooturl.$dtrow['filepath']:""; ?>"  id="attachment_<?php echo $prv; ?>"  style="width: 100%; height: 500px;"></iframe>
						<?php	    
							} else {
							    echo "<h2 style='margin-top:25%; margin-left:25%;'>The file does not exist</h2>";
							}
						?>
						<?php
						}else if(strlen($dtrow['filepath'])>20 && strtolower($dtrow['documenttype'])=="physical"){
							// echo "bbbb: ".$rooturl.$dtrow['filepath'];
							if (file_exists("../".$dtrow['filepath'])) {
						?>
							<iframe src="<?php echo $dtrow['filepath']?$rooturl.$dtrow['filepath']:""; ?>"  id="attachment_<?php echo $prv; ?>"  style="width: 100%; height: 500px;"></iframe>
						<?php	    
							} else {
							    echo "<h2 style='margin-top:25%; margin-left:25%;'>The file does not exist</h2>";
							}
						?>
						<?php
						}else{
							echo "<h2 style='margin-top:25%; margin-left:25%;'>NO Document</h2>";
						}
						?>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<div class="form-group text-center">
							<p id="hiddenbsno_<?php echo $prv; ?>" style="display: none;"><?php echo $dtrow['sno']; ?></p>
							<button type="button" name="editbutton" class="editbutton btn btn-deepblue btn-sm btn-round pull-right m-2" id="editbutton_<?php echo $prv; ?>" value="Edit of <?php echo $invicesno; ?>" onclick="editinvoiceinfo(this.id);" style="min-width: 100px;">Edit of Invoice/Voucher <?php echo $invicesno; ?></button>

							<button type="button" name="deletebutton" class="deletebutton btn btn-danger btn-sm btn-round pull-right m-2 <?php echo $hideshow; ?>" id="deletebutton_<?php echo $prv; ?>" onclick="deleteinvoiceinfo(this.id);" style="min-width: 100px;">Remove of Invoice/Voucher <?php echo $invicesno; ?></button>
						</div>
					</div>
				</div>				
			</details>
		<?php
		}
		$itemstocklist=getstoocdetails($conn,$companyid,$subcompanyid,$projectid,"requestno",$requestno);
		if(count($itemstocklist)>0){
		?>
			<div class="shade2 p-2" style="margin: 50px 10px;">
				<div class="row">
					<div class="col-md-12">
						<h6 class=" ">PURCHASE ITEM DETAILS FOR STOCK <span onclick="document.getElementById('prv50').style.display='block'"  class="div_open_white"></span></h6>
					</div>
				</div>
				<div class="row bg-light" id="prv50">
					<div class="col-md-12">
						<p><span onclick="document.getElementById('prv50').style.display='none'"  class="div_collapse"></span></p>
						<div class="table-responsive">
							<table class="table table-bordered">
								<thead>
									<tr>
										<th>S.No</th>
										<th>Item Name</th>
										<th>Item Count</th>
										<th>Total Amount</th>
										<th>Purpose</th>
										<th>Beneficiary Name</th>
									</tr>
								</thead>
								<tbody>
								<?php
								$itemsno=1;
								foreach ($itemstocklist as $itemrow) {
									echo "<tr>
										<th>".$itemsno++."</th>
										<th>".$itemrow['item_name']."</th>
										<th>".$itemrow['quantity']."</th>
										<th>".$itemrow['itemamount']."</th>
										<th>".$itemrow['itempurpose']."</th>
										<th>".$itemrow['itembnfname']."</th>
									</tr>";
								}
								?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		<?php
		}
	}
	?>

	<details id="frombankdivc">
		<summary class="req_frmbank_head">PAY FROM BANK ACCOUNT DETAILS</summary>
		<div class="card">
			<div class="card-body">
				<div class="row">
					<div class="col-md-12">
						<?php
						$acclist= getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"accountid",$ptbrow['from_accountid']);
						$fromaccountname ="";
			    		$frombankname="";
			    		$fromaccountno ="";
			    		$frombankifsc ="";
				    	if(count($acclist)>0){
				    		foreach ($acclist as $faccrow) {
				    			$fromaccountname = $faccrow['accountname'];
				    			$frombankname = $faccrow['bankname'];
				    			$fromaccountno = $faccrow['accountno'];
				    			$frombankifsc = $faccrow['ifsccode'];

							echo '<p class="col-lg-12"><b class="frmacct_lbl_name">From Account Name : </b> '.$fromaccountname.'</p>';
							echo '<p class="col-lg-12"><b class="frmbn_lbl_name">From Bank Name : </b> '.$frombankname.'</p>';
							echo '<p class="col-lg-12"><b class="frmacct_lbl_no">From Account Number : </b> '.$fromaccountno.'</p>';	
							}
							echo '<span style="color:blue;">Do you want to change pay from bank details</span>
							<label><input type="radio" name="updpayfrombankrd" value="yes" /> YES</label>
							<label><input type="radio" name="updpayfrombankrd" value="no" /> NO</label>';
						}else{
						?>
							<span style="color:blue;">Pay From Bank Details Missing, Do you want to update bank details</span>
							<label><input type="radio" name="updpayfrombankrd" value="yes" /> YES</label>
							<label><input type="radio" name="updpayfrombankrd" value="no" /> NO</label>
						<?php
						}
						?>
					</div>			
					<div class="col-md-8 mt-2 changepayfrombankdiv" style="display:none;">
						<label class="pyfrom_lbl_acclist ">Pay From Account<sup><i class="fa fa-star" style="font-size: 6px; color:red"></i></sup></label>
						<select id="rqpr_fromaccount" class="form-control select2" style="width:100%;"></select>
					</div>
					<div class="col-md-4 mt-2 changepayfrombankdiv text-center pt-1" style="display:none;">
						<button id="uppayfrombankbtn" class="btn btn-success mt-4" onclick="updatepayfrombankdetails_inrequest('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','rqpr_fromaccount','<?php echo $reqdbsno; ?>','uppayfrombankbtn');">Update Bank Details</button>
					</div>			
				</div>
			</div>
		</div>
	</details>

	<div class="shade4 p-2" style="margin: 36px 4px;">
		<div class="row">
			<div class="col-md-12">
				<label class=""><input type="radio" name="requestaction" id="confirmtoapprove" /> Do you want to approve this request?</label>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<label class=""><input type="radio" name="requestaction" id="confirmtoreject" /> Do you want to reject this request?</label>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<label class=""><input type="radio" name="requestaction" id="addwaitinglist" /> Would you like to be added to the waiting list for approval?</label>
			</div>
		</div>
		<!-- <div class="row">
			<div class="col-md-12">
				<label class=""><input type="radio" name="requestaction" id="confirmtosendapproval" /> Do you want to send for approval to?</label>
			</div>
		</div> -->
		<!-- <div class="row">
			<div class="col-md-12">
				<label class=""><input type="radio" name="requestaction" id="confirmtonone" /> None</label>
			</div>
		</div> -->
		<div class="row" id="approvediv" style="display: none;">
			<div class="col-md-12 text-center">
				<input type="button" class="btn fixed-btn-left" id="rqs_approve_btn" value="REQUEST APPROVE" />
			</div>
		</div>
		<div class="row" id="rejectdiv" style="display: none; margin-top: 30px;">
			<div class="col-md-12">
				<label class="">Reject Remark</label>
				<textarea rows="4" style="resize: none;" id="rejectremark" class="form-control"></textarea>
				<div class="p-4 fixed-btn-container">
					<input type="button" class="btn fixed-btn-left" id="rqs_reject_btn" value="REQUEST REJECT" />
				</div>
			</div>		
		</div>
		<div class="row" id="addwaitlistdiv" style="display: none; margin-top: 30px;">
			<div class="col-md-12">
				<label class="">Remark for waitlist</label>
				<textarea rows="2" style="resize: none;" id="addwaitingremark" class="form-control"></textarea>
				<div class="p-4 fixed-btn-container">
					<input type="button" class="btn fixed-btn-left" id="rqs_addwaiting_btn" value="ADD IN WAITING LIST" />
				</div>
			</div>		
		</div>
	</div>
<?php 
}
?>
</div>
<div class="modal" id="change_paytobankdetails_modal">
	<div class="modal-dialog  modal-lg">
		<div class="modal-content">
			<div class="modal-header p-2 shade2">
		        <h4 class="modal-title ">Change Mode of Transfer</h4>
		        <button type="button" class="close" data-dismiss="modal" onclick="document.getElementById('change_paytobankdetails_modal').style.display='none'">&times;</button>
		    </div>
			<div class="modal-body p-4" style="margin-top: 10px;">
				<div class="row">
					<p><b>Pay to Name : </b> <span id="paytoname_change"></span></p>
				</div>
				<div class="row">
					<div class="col-md-12">
						<p><b>You want to change mode of transfer : </b>
						<label><input type="radio" name="modeoftransfer_change" value="online" /> Online</label>
					 	<label><input type="radio" name="modeoftransfer_change" value="cheque" /> Cheque</label></p>
					 </div>
				</div>
				<div class="row" id="bankdetailsdiv" style="display: none;">
					<div class="col-md-4">
						<label>Bank Name</label>
						<input type="text" name="pbankname" id="pbankname" value="" class="form-control" />
					</div>
					<div class="col-md-4">
						<label>Account Number</label>
						<input type="text" name="pbankaccountno" id="pbankaccountno" value="" class="form-control" />
					</div>
					<div class="col-md-4">
						<label>IFSC Code</label>
						<input type="text" name="pbankifsccode" id="pbankifsccode" value="" class="form-control" />
					</div>
				</div>
				<div class="row mt-4 mb-2">
					<div class="col-md-12">
						<p><label><input type="checkbox" name="confirm_changemode" id="confirm_changemode" onclick="showhidesavebtn(this.id,'savemodeoftransfer')" /> &nbsp;&nbsp;&nbsp;Are you confirm? Want to change mode of transfer</label></p>
					</div>
				</div>
				<div class="row mt-2">
					<div class="col-md-12 text-center">
						<button type="button" class="btn btn-success shade2  btn-round" name="savemodeoftransfer" id="savemodeoftransfer" value="Save Mode Of Transfer" style="display: none;">Save Mode Of Transfer</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="modal mb-4" id="editmodal" tabindex="-1" role="dialog">
	<div class="modal-dialog"  role="document" style="min-width: 80%;">
		<div class="modal-content">
			<div class="modal-header p-2 bg-primary">
				<h4 id="editheadermodal">INVOICE DETAILS FOR EDIT</h4>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">X</button>
			</div>
			<div class="modal-body" style="max-height: 500px; overflow-y: auto;">
				<div class="row">
					<div class="col-md-6">
						<div class="row mt-2">
							<div class="col-md-12">
								<label>Beneficiary Name</label>
								<span class="float-right m-2" onclick='request_beneficiaryfrommaster("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","ebeneficiaryname","");' style="cursor: pointer;">  <i class="fa fa-sync"></i> Refresh</span>
      							<span class="float-right opennewbeneficiarymodal m-2 text-success" style="cursor: pointer;"><i class="fa fa-plus-circle"></i> Add</span>
								<select id="ebeneficiaryname" class="form-control" style="width:100%;"></select>
								<input type="hidden" id="ehiddenbsno" value="" />
								<input type="hidden" id="eparentidno" value="" />	
							</div>
						</div>
						<div class="row mt-2">
							<div class="col-md-12">
								<label>Requested Amount</label>
								<input id="einvoiceamount" type="text" class="form-control">
							</div>
						</div>
						<!-- <div class="row mt-2">
							<div class="col-md-12">
								<label>Purpose Category</label>
								<select id="epcategory" class="form-control" style="width:100%;" onchange="request_purposesubcategoryfrommaster('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid',$(this).val(),$('#epsubepcategory').data('epsubcategoryidval'),'epsubepcategory');"></select>
							</div>
						</div> -->
						<!-- <div class="row mt-2" id="subepcategorydiv">
						  <div class="col-md-12">
						    <label>Purpose sub category</label>
						    <select id="epsubepcategory" class="form-control select2" style="width:100%;" onchange="request_purposecodefrommaster('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid',$('#epcategory').val(),$(this).val(),$('#epurposecode').data('eppurposecodeval'),'epurposecode');"></select>
						  </div>
						</div> -->
						<div class="row mt-2">
							<div class="col-md-12">
								<label>Purpose</label>
								<select id="epurposecode" class="select2 form-control" style="width:100%;"></select>
							</div>
						</div>
						<div class="row mt-2">
							<div class="col-md-12">
								<label>Invoice Type</label>
								<select id="einovoicetype" class="form-control">
									<option value="">Select inovice type</option>
									<?php
									$invoicelist = getinvoicetype($conn);
									foreach ($invoicelist as $intype) {
									echo "<option value='".$intype['invoicetype']."'>".$intype['invoicetype']."</option>";
									}
									?>
								</select>
							</div>
						</div>
						<div class="row mt-2">		
							<div class="col-md-12">
								<label>Invoice No</label>
								<input id="einvoiceno" type="text" class="form-control">
							</div>
						</div>
						<div class="row mt-2">		
							<div class="col-md-12">
								<label>Invoice Date</label>
								<input id="einvoicedate" type="date" class="form-control">
							</div>
						</div>
						<div class="row mt-2">
							<div class="col-md-12">
								<label>Description</label>
								<textarea id="edescription" type="text" style="width:100%; resize:none;" class="form-control" rows="4"></textarea>
							</div>
						</div>
					</div>
					<div class="col-md-6">
						<p class="p-2">
							<label>New Supporting Document</label>
							<input type="file" name="efile" id="efile" onchange="readURL(this);">
							<span class="text-danger ml-2">File size should be less than 900 KB, <a class="text-primary hidden" href="https://pdfcompressor.com/" target="_blank">click to compress online</a></span>
						</p>
						<iframe src="" id="eattachment"  style="width: 100%; height: 500px;"></iframe>
					</div>
				</div>
				<div class="row mt-2 mb-2" style="margin-top: 100px;">
					<div class="col-md-12">
						<label><input id="econform" type="checkbox" onclick="showhidesavebtn(this.id,'esavebutton')"> &nbsp;&nbsp; <b class="text-primary">Please confirm it, All information is corrected and want to save it.</b></label>
					</div>
				</div>
				<div class="row text-center" style="margin-bottom:100px;">
					<div class="col-md-12 text-center">
						<button  type="button" id="esavebutton" class="btn btn-success mt-4 mb-4" style="display: none;" value="SAVE EDITED DETAILS">SAVE EDITED DETAILS</button><br>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- confirm for remove invoice details -->
<div class="modal fade" id="removeinvoiceinfomodal" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-lg" style="max-width: 80%;">
		<div class="modal-content">
			<div class="modal-header bg-danger">
	            <h4 id="confirmheadermodal" class="">CONFIRMATION FOR REMOVE INVOICE DETAILS</h4>
	            <button type="button" class="close" data-dismiss="modal" aria-label="Close">X</button>
	        </div>
			<div class="modal-body">
				<div class="container">
					<div class="row">
						<div class="col-md-6">
							<div class="mt-2"><b>Beneficiary Name : </b> <span id="rbeneficiaryname"></span></div>
							<div class="mt-2"><b>Requested Amount : </b><span id="rinvoiceamount"></span></div>
							<div class="mt-2"><b>Invoice No : </b> <span id="rinvoiceno"></span></div>
							<div class="mt-2"><b>Description : </b> <span id="rdescription"></span></div>
						</div>
						<div class="col-md-6">
							<div class="col-md-12">
								<iframe src="" id="rattachment"  style="width: 100%; height: 300px;"></iframe>
							</div>
						</div>
					</div>
					<div class="row mt-4">
						<label><b>Rejecting Remark</b></label>
						<textarea rows="3" class="form-control" style="resize: none;" id="rremark"></textarea>
						<input type="hidden" id="rhiddenbsno">
						<input type="hidden" id="rhiddenparentidno">
					</div>
					<div class="row mt-4 mb-4">
						<div class="col-md-12">
							<label><input id="rconform" type="checkbox" onclick="showhidesavebtn(this.id,'rsavebutton')"> &nbsp;&nbsp; <b>Please confirm, You want to remove it.</b></label>
						</div>
					</div>
					<div class="row text-center">
						<div class="col-md-12">
							<input type="button" class="btn btn-danger" data-rhiddenbsno='' data-rhiddenparentidno='' id="rsavebutton" value="CLICK TO REMOVE" style="display: none;">
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- confirm for proceed request for pament -->
<div class="modal" id="proceetopaymentmodal">
	<div class="modal-dialog modal-dialog-scrollable modal-dialog-centered modal-lg" style="min-height: 110%;">
		<div class="modal-content" style="margin-bottom: 100px;">
			<header class="p-2 shade5">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">X</button>
				<h4 id="confirmpaymentheader" class="">CONFIRMATION FOR PAYMENT</h4>
			</header>
			<div class="modal-body">
				<div class="row">
					<div class="col-md-12">
						<div class="mt-2"><b class="req_lbl_paytoname">Payto Name : </b> <span id="cpaytoname"></span></div>
						<div class="mt-2"><b class="req_lbl_amount">Requested Amount : </b><span id="crequestedamount"></span></div>
						<div class="mt-2"><b class="forpaid_lbl_amount">Amount to be Pay : </b><span id="forpaidamount"></span></div>
					</div>
				</div>
				<div class="row mt-2">
					<div class="col-md-12">
						<label>Approval Remark</label>
						<textarea id="approvedremark" rows="2" style="resize: none;" class="form-control"></textarea>
					</div>
				</div>
				<div class="row mt-2 mb-2">
					<div class="col-md-12">
						<label><input id="fconform" type="checkbox" onclick="showhidesavebtn(this.id,'saveforpayment')"> &nbsp;&nbsp; Please confirm, All details is correct and proceed to payment.</label>
					</div>
				</div>
				<div class="row mt-2">
					<div class="col-md-12 text-center">
						<button type="button" class="btn btn-info mb-2" id="saveforpayment" value="CLICK TO PROCEED FOR PAYMENT" style="display: none;">CLICK TO PROCEED FOR PAYMENT</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- tds details view in tabular forma -->
<div class="modal" id="tdsdetailsviewmodal">
	<div class="modal-dialog modal-dialog-scrollable modal-dialog-centered modal-xl" style="min-height: 110%;">
		<div class="modal-content" style="margin-bottom: 100px;">
			<header class="p-2 shade5">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">X</button>
				<h4 class="">TDS DETAILS VIEW</h4>
			</header>
			<div class="modal-body">
				<div class="row">
					<div class="col-md-12">
						<table id="tdsdetailsviewtbl">
							<thead>
								<tr>
									<th>Select</th>
									<th>TDS Section</th>
									<th>Nature of Payment</th>
									<th>Deductor</th>
									<th>Deductee</th>
									<th>TDS Rate (as per FY 2024-25)</th>
									<th>Threshold Limit</th>
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

<div class="modal" id="tdshistoryviewmodal">
	<div class="modal-dialog modal-dialog-scrollable modal-dialog-centered modal-xl" style="min-height: 110%;">
		<div class="modal-content" style="margin-bottom: 100px;">
			<header class="p-2 shade5">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">X</button>
				<h4 class="">TDS HISTORY OF <b class="tdsbeneficiaryname"></b></h4>
			</header>
			<div class="modal-body">
				<div class="row">
					<div class="col-md-3"></div>
					<div class="col-md-3"></div>
					<div class="col-md-3">
						<label>Date From</label>
						<input type="date" class="form-control" name="tdsdatefrom" id="tdsdatefrom" value="<?php echo date('Y-03-01'); ?>" />
					</div>
					<div class="col-md-3">
						<label>Date To</label>
						<input type="date" class="form-control" name="tdsdatefrom" id="tdsdateto"  value="<?php echo date('Y-m-d'); ?>" />
					</div>
				</div>
				<div class="row mt-2">
					<div class="col-md-12">
						<table id="tdshistoryviewtbl">
							<thead>
								<tr>
									<th>TDS Deducted Date</th>
									<th>TDS Type</th>
									<th>Invoice/Voucher No</th>
									<th>Invoice/Voucher Amount</th>
									<th>TDS Amount</th>
								</tr>
							</thead>
							<tbody></tbody>
							<tfoot></tfoot>
						</table>					
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- <div class="modal" id="partialamount_modal" style="display: none;"> -->
<div class="modal" id="changerequesttype_modal" style="display: none;">
	<div class="modal-dialog modal-dialog-scrollable modal-dialog-centered modal-lg">
		<div class="modal-content" style="background-color: rgb(121, 111, 143, 0.9);">
			<div class="modal-header bg-lime">
				<h4 id="headermodal"></h4>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">X</button>
			</div>
			<div class="modal-body">
				<!-- <p id="partialamount_label" class=""></p> -->
				<p id="requesttypemessage_label" class=""></p>
				
				<div id="securitydeposit_yesno" style="display: none;"> 
				<input type="radio" name="sdradio" value="yes">&nbsp;&nbsp;Yes&nbsp;&nbsp;
				<input type="radio" name="sdradio" value="no" onclick="document.getElementById('changerequesttype_modal').style.display='none'">&nbsp;&nbsp;No
				</div>

				<div id="partialamount_yesno" style="display: none;"> 
				<input type="radio" name="partialadjustradio" value="yes">&nbsp;&nbsp;Yes&nbsp;&nbsp;
				<input type="radio" name="partialadjustradio" value="no" onclick="document.getElementById('changerequesttype_modal').style.display='none'">&nbsp;&nbsp;No
				</div>

				<div id="imprestadjust_yesno" style="display: none;"> 
				<input type="radio" name="imprestadjustradio" value="yes">&nbsp;&nbsp;Yes&nbsp;&nbsp;
				<input type="radio" name="imprestadjustradio" value="no" onclick="document.getElementById('changerequesttype_modal').style.display='none'">&nbsp;&nbsp;No
				</div>						
			</div>
			<p id="updatesp_test"></p>
		</div>
	</div>
</div>
<?php 
include("../footer.php");
?>
<style type="text/css">
	.fixed-btn-container{
		display: flex;
		justify-content: center;
  		align-items: center;
	}
	.fixed-btn-left{
		width: 250px;
	    height: auto;
	    margin: 10px;
	    border: 0px solid #d6d6d6;
	    border-radius: 50px;
	    z-index: 99;
	    padding: 16px 8px;
	    text-align: center;
	    background: orange;
	    font-weight: bold;
	    font-size-adjust: 2em;
	}
</style>
</html>
<script type="text/javascript">
$(document).ready(function(){
	$('.select2').select2();
	$('.select2-selection').css('border-radius','0px');
	$('.select2-container').children().css('border-radius','0px');
	$('.select2-selection').css('height','42px');
	var countryid ="<?php echo $countryid; ?>";
	var companyid ="<?php echo $companyid; ?>";
	var subcompanyid ="<?php echo $subcompanyid; ?>";
	var projectid ="<?php echo $projectid; ?>";
	var projectname ="<?php echo $projectname; ?>";
	var paytoname =$("#paytoname").text();

	var requesttype = $("#purposetype").text();;
	var routurl = $("#hidden_rooturl").val();
	var imprestpaytoid ="";
	
	$("#viewimpreststatement").on("click", function(){
		var imprestpaytoid = $(this).closest("tr").data("paytoid");
		$("#webpagebodyview_modal").modal("show");
		$("#modalheader").html("IMPREST STATEMENT OF PROJECT - "+projectname);
		var key = "<?php echo addslashes($key); ?>";
		var url = routurl + "webpages/statement_imprest.php?k=" + key + 
              "&pid=" + imprestpaytoid + 
              "&prjname=" + btoa(projectname) + 
              "&viewstatus=load";
	    $("#webpagebodydiv").load(url);

	});

	$("#viewloanstatement").on("click", function(){
		var paytoid = '<?php echo $paytoid; ?>';
		$("#webpagebodyview_modal").modal("show");
		$("#modalheader").html("LOAN STATEMENT OF "+paytoname+" FOR THE PROJECT - "+projectname);
		var key = "<?php echo addslashes($key); ?>";
		var url = routurl + "webpages/statement_loan.php?k=" + key + 
              "&pid=" + paytoid + 
              "&prjname=" + btoa(projectname) + 
              "&viewstatus=load";
	    $("#webpagebodydiv").load(url);

	});

	$("#viewcashstatement").on("click", function(){
		var paytoid = '<?php echo $paytoid; ?>';
		$("#webpagebodyview_modal").modal("show");
		$("#modalheader").html("CASH ACCOUNT STATEMENT OF "+paytoname+" FOR THE PROJECT - "+projectname);
		var key = "<?php echo addslashes($key); ?>";
		var url = routurl + "webpages/statement_cash.php?k=" + key + 
              "&pid=" + paytoid + 
              "&prjname=" + btoa(projectname) + 
              "&viewstatus=load";
	    $("#webpagebodydiv").load(url);
	});

	$(document).on("click", ".tdsviewspan", function(){
		let viewtdsspanid = $(this).attr("id");
		var viewtdsspanidexp=viewtdsspanid.split("_");
		var pdivno=viewtdsspanidexp[1];
		tdstypefrommaster_callback('hidden_rooturl','all','all',function(res){
			var trdata="";
			$("#tdsdetailsviewtbl tbody").html('');
			if (res.length > 0) {
			    let trdata = "";
			    for (let i = 0; i < res.length; i++) {
			    	let isPercent = res[i].tds_rate.includes('%');
			        let radioDisabled = isPercent ? "" : "disabled";

			        let percentval = res[i].tds_rate.includes('%') ? res[i].tds_rate.replace('%', '') : 0;

			        trdata += `
			            <tr>
			                <td><input type='radio' class='tdssltfrommastertd' data-tdsparentdivno="${pdivno}" data-tdspercentval="${percentval}" value="${res[i].id}" ${radioDisabled} /></td>
			                <td>${res[i].section}</td>
			                <td>${res[i].nature_of_payment}</td>
			                <td>${res[i].deductor}</td>
			                <td>${res[i].deductee}</td>
			                <td>${res[i].tds_rate}</td>
			                <td>${res[i].threshold_limit}</td>
			            </tr>`;
			    }

			    if (trdata) {
			        $("#tdsdetailsviewmodal").modal("show");
			        $("#tdsdetailsviewtbl tbody").html(trdata);
			    }
			}

		});
	});
	

	$(document).on("click", ".bnftdshitory", function(){
		let tdsdatefrom = $("#tdsdatefrom").val();
		let tdsdateto = $("#tdsdateto").val();
		let viewtdsspanid = $(this).attr("id");
		var viewtdsspanidexp=viewtdsspanid.split("_");
		var pdivno=viewtdsspanidexp[1];
		var tdsbeneficiaryname = $("#bnfname_"+pdivno).text();
		$("#tdsdatefrom").data("pdivno", pdivno);
		$("#tdsdateto").data("pdivno", pdivno);
		$(".tdsbeneficiaryname").html(tdsbeneficiaryname);
		tdshistoryofbeneficiary_callback(tdsdatefrom,tdsdateto,"beneficiarynamewithdates",tdsbeneficiaryname,function(res){
			var tdstrdata="";
			var tdsfootdata="";
			$("#tdshistoryviewtbl tbody").html('');
			$("#tdshistoryviewtbl tfoot").html('');
			if (res.length > 0) {
				 let ttlinvoiceamount = 0;
				 let ttltdsamount =0;
			    for (let i = 0; i < res.length; i++) {
			    	ttlinvoiceamount += parseInt(res[i].amount_paid) || 0;
			    	ttltdsamount += parseInt(res[i].tds_deducted) || 0;
			    	tdstrdata += `
			            <tr>
			                <td>${res[i].bookdatedsp}</td>
			                <td>${res[i].tds_type}</td>
			                <td>${res[i].bill_no}</td>
			                <td>${res[i].amount_paid}</td>
			                <td>${res[i].tds_deducted}</td>
			            </tr>`;
			    }

			    tdsfootdata += `
			            <tr>
			                <th colspan='3'>Total</th>
			                <th>${ttlinvoiceamount}</th>
			                <th>${ttltdsamount}</th>
			            </tr>`;
			    if (tdstrdata) {
			    	$("#tdshistoryviewmodal").modal("show");
			        $("#tdshistoryviewtbl tbody").html(tdstrdata);
			        $("#tdshistoryviewtbl tfoot").html(tdsfootdata);
			    }
			    
			}else{
				$("#tdshistoryviewmodal").modal("show");
				$("#tdshistoryviewtbl tbody").html("<tr><td colspan='5' class='text-center'>No data available</td></tr>");
			}
		});		
	});

	$(document).on("change", "#tdsdatefrom, #tdsdateto", function(){
		let tdsdatefrom = $("#tdsdatefrom").val();
		let tdsdateto = $("#tdsdateto").val();
		var pdivno=$("#tdsdateto").data("pdivno");
		var tdsbeneficiaryname = $("#bnfname_"+pdivno).text();
		$(".tdsbeneficiaryname").html(tdsbeneficiaryname);
		tdshistoryofbeneficiary_callback(tdsdatefrom,tdsdateto,"beneficiarynamewithdates",tdsbeneficiaryname,function(res){
			// console.log(res);
			var tdstrdata="";
			var tdsfootdata="";
			$("#tdshistoryviewtbl tbody").html('');
			$("#tdshistoryviewtbl tfoot").html('');
			if (res.length > 0) {
				 let ttlinvoiceamount = 0;
				 let ttltdsamount =0;
			    for (let i = 0; i < res.length; i++) {
			    	ttlinvoiceamount += parseInt(res[i].amount_paid) || 0;
			    	ttltdsamount += parseInt(res[i].tds_deducted) || 0;
			    	tdstrdata += `
			            <tr>
			                <td>${res[i].bookdatedsp}</td>
			                <td>${res[i].tds_type}</td>
			                <td>${res[i].bill_no}</td>
			                <td>${res[i].amount_paid}</td>
			                <td>${res[i].tds_deducted}</td>
			            </tr>`;
			    }

			    tdsfootdata += `
			            <tr>
			                <th colspan='3'>Total</th>
			                <th>${ttlinvoiceamount}</th>
			                <th>${ttltdsamount}</th>
			            </tr>`;
			    if (tdstrdata) {
			    	$("#tdshistoryviewmodal").modal("show");
			        $("#tdshistoryviewtbl tbody").html(tdstrdata);
			        $("#tdshistoryviewtbl tfoot").html(tdsfootdata);
			    }			    
			}else{
				$("#tdshistoryviewmodal").modal("show");
				$("#tdshistoryviewtbl tbody").html("<tr><td colspan='5' class='text-center'>No data available</td></tr>");
			}
		});		
	});

	$(document).on("click", ".tdssltfrommastertd", function(){
		let tdsid = $(this).val();
		let pdivno = $(this).data("tdsparentdivno");
		var tdsdropid = "tdscategory_"+pdivno;
		$("#"+tdsdropid+"").val(tdsid).trigger("change");
	});
	// alert(requesttype);
	if(requesttype=="Expenditure"){
		$(".req_lbl_amount").text("Requested Amount : ");
		$(".req_lbl_paytoname").text("Payto Name : ");
		$(".req_frmbank_head").text("REQUEST FROM BANK DETAILS");
		$(".frmacct_lbl_name").text("From Account Name : ");
		$(".frmbn_lbl_name").text("From Bank Name : ");
		$(".frmacct_lbl_no").text("From Account Number : ");
		$(".pyfrom_lbl_acclist").text("Pay From Account");
		$(".bnf_lbl_amount").text("Requested Amount : ");
		$("#rqs_approve_btn").text("REQUEST APPROVE");
		$("#confirmpaymentheader").text("CONFIRMATION FOR PAYMENT");
		$("#saveforpayment").val("CLICK TO PROCEED FOR PAYMENT");
		$("#saveforpayment").html("CLICK TO PROCEED FOR PAYMENT");

	}else if(requesttype=="Imprest Adjustment"){
		$(".req_lbl_amount").text("Imprest Adjustment Amount : ");
		$(".req_lbl_paytoname").text("Imprest Payto Name : ");
		$(".req_frmbank_head").text("IMPREST BANK DETAILS");
		$(".frmacct_lbl_name").text("Imprest Account Name : ");
		$(".frmbn_lbl_name").text("Imprest Bank Name : ");
		$(".frmacct_lbl_no").text("Imprest Account Number : ");
		$(".pyfrom_lbl_acclist").text("Choose Imprest Account");
		$(".bnf_lbl_amount").text("Imprest Adjustment Amount : ");
		$("#rqs_approve_btn").text("IMPREST ADJUSTMENT APPROVE");
		$("#confirmpaymentheader").text("CONFIRMATION FOR IMPREST ADJUSTMENT");
		$("#saveforpayment").val("CLICK TO PROCEED FOR IMPREST ADJUSTMENT");
		$("#saveforpayment").html("CLICK TO PROCEED FOR IMPREST ADJUSTMENT");		
	}else if(requesttype=="Partial Adjustment"){
		$(".req_lbl_amount").text("Partial Adjustment Amount : ");
		$(".req_lbl_paytoname").text("Partial Payto Name : ");
		$(".req_frmbank_head").text("PARTAIL BANK DETAILS");
		$(".frmacct_lbl_name").text("Partial Account Name : ");
		$(".frmbn_lbl_name").text("Payfrom Bank Name : ");
		$(".frmacct_lbl_no").text("Payfrom Account Number : ");
		$(".pyfrom_lbl_acclist").text("Choose Account");
		$(".bnf_lbl_amount").text("Partial Adjustment Amount : ");
		$("#rqs_approve_btn").text("PARTAIL ADJUSTMENT APPROVE");
		$("#confirmpaymentheader").text("CONFIRMATION FOR PARTAIL ADJUSTMENT");
		$("#saveforpayment").val("CLICK TO PROCEED FOR PARTAIL ADJUSTMENT");
		$("#saveforpayment").html("CLICK TO PROCEED FOR PARTAIL ADJUSTMENT");		
	}
	if(imprestpaytoid=="NA" || imprestpaytoid=="" || imprestpaytoid=="undefined" || imprestpaytoid==null){
		getonlyaccountlistfrommaster("rqpr_fromaccount");
	}else{
		imprestaccountdetails("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","rqpr_fromaccount",imprestpaytoid);
	}
	
	$(document).on("click", "input[name='updpayfrombankrd']", function(){
		if($(this).val()=="yes"){
			$(".changepayfrombankdiv").show();
		}else{
			$(".changepayfrombankdiv").hide();
		}
	});
	
	$("#savemodeoftransfer").on('click', function(){
		var requestno = "<?php echo $requestno; ?>";
		var paytoname=$("#paytoname_change").text();
		var modeoftransfer=$("input[name='modeoftransfer_change']:checked").val();
		if(modeoftransfer=="" || modeoftransfer==null || modeoftransfer=="undefined"){
			// alertmessage("error","Chose the mode of transfer");
			alertmessage("error","Chose the mode of transfer");
			return false;
		}
		var pbankname=$("#pbankname").val();
		var pbankaccountno=$("#pbankaccountno").val();
		var pbankifsccode=$("#pbankifsccode").val();
		if(modeoftransfer!="" && modeoftransfer=="online"){
			if(pbankname=="" || pbankname==null || pbankname=="undefined"){
				// alertmessage("error","Bank name is missing");
				alertmessage("error","Bank name is missing");
				return false;
			}
			if(pbankaccountno=="" || pbankaccountno==null || pbankaccountno=="undefined"){
				// alertmessage("error","Account number is missing");
				alertmessage("error","Account number is missing");
				return false;
			}
			if(pbankifsccode=="" || pbankifsccode==null || pbankifsccode=="undefined"){
				// alertmessage("error","IFSC Code is missing");
				alertmessage("error","IFSC Code is missing");
				return false;
			}
		}
		$.ajax
		({
			type: "POST",
			url: "modal/savetodb.php?action=updatemodeoftransfer",
			data: {requestno: requestno, paytoname: paytoname, modeoftransfer: modeoftransfer, pbankname: pbankname, pbankaccountno: pbankaccountno, pbankifsccode: pbankifsccode, countryid: countryid, companyid: companyid, subcompanyid: subcompanyid, projectid: projectid},
			cache: false,
			dataType:'JSON',
			success: function(res)
			{
				// console.log(res);
				$(".waitloaderfooter").modal("hide");
				for(var i=0; i<res.length; i++){
					if(res[i].status=='Success'){
						SweetAlertmessageFadein('success', res[i].message);
					}else{
						alertmessage("error",res[i].message);
					}
				}
			},beforeSend: function (res){
				$(".waitloaderfooter").modal("show");
			}
		});

	});

	
	
	$(".istdsapplicable").on("click", function(){
		var istdsapplicableval = $(this).val();
		var eleid = $(this).attr("id");
		var parentidexp=eleid.split("_");
		var parentidno=parentidexp[1];

		if(istdsapplicableval=="yes"){
			// $("#tdsdiv_"+parentidno+"").show();
			$("#tdscategorydiv_"+parentidno+"").show();
			$("#amounttopayp_"+parentidno+"").show();

		}else{
			$("#amounttopay_"+parentidno+"").text($("#requestedamount_"+parentidno+"").text());
			$("#tdsdiv_"+parentidno+"").hide();
			$("#tdscategorydiv_"+parentidno+"").hide();
			$("#amounttopayp_"+parentidno+"").hide();
			$("#tdspercent_"+parentidno+"").val('');
			$("#tdscategory_"+parentidno+"").prop("selectedIndex", 0);
		}
	});

	$(document).on("change", ".tdscategory", function(){
		var tdscatid = $(this).prop("id");
		var ptdscatid=tdscatid.split("_");
		var ptdscatid1=ptdscatid[1];
		var tdstypewithval = $(this).find("option:selected").data("sectionwithpercentval");
		var tdsrateval = $(this).find("option:selected").data("tdsrate");
		let isPercent = tdsrateval.includes('%');
		if(isPercent==true){
			var tdstypewithvalexp=tdstypewithval.split("|");
			var tdspercent = tdstypewithvalexp[1].replace('%', '');
			$("#tdsdiv_"+ptdscatid1).hide();
		}else{
			tdspercent=0;
			$("#tdsdiv_"+ptdscatid1).show()
		}
		var tttdid ="tdspercent_"+ptdscatid1; 
		$("#"+tttdid+"").val(tdspercent);
		tdsdeduction(tttdid,tdspercent);
	});

	
	//edit info save and update

	$("#esavebutton").on("click", function(){
		var form_data = new FormData();
		var companyid ="<?php echo $companyid; ?>";
		var subcompanyid ="<?php echo $subcompanyid; ?>";
		var projectid ="<?php echo $projectid; ?>";
		var requestno = "<?php echo $requestno; ?>";

		form_data.append("companyid", companyid);
		form_data.append("subcompanyid", subcompanyid);
		form_data.append("projectid", projectid);
		form_data.append("requestno", requestno);

		var parentidno=$("#eparentidno").val();
		form_data.append("parentidno", parentidno);
		
		var ehiddenbsno =$("#ehiddenbsno").val();
		form_data.append("ehiddenbsno", ehiddenbsno);

		var ebeneficiaryname=$("#ebeneficiaryname").val();
		form_data.append("ebeneficiaryname", ebeneficiaryname);
		if(ebeneficiaryname=="" || ebeneficiaryname==null || ebeneficiaryname=="null" || ebeneficiaryname=="undefined"){
			alertmessage('error','Beneficiary name missing, Please choose the beneficiary name.');
            return false;
		}
		var einvoiceamount=$("#einvoiceamount").val();
		form_data.append("einvoiceamount", einvoiceamount);
		if(einvoiceamount=="" || einvoiceamount==null || einvoiceamount=="null" || einvoiceamount=="undefined" || parseInt(einvoiceamount)<=0){
			alertmessage('error','Amount is missing, Please enter the valid amount.');
            return false;
		}
		var epurposecode=$("#epurposecode").val();
		form_data.append("epurposecode", epurposecode);
		if(epurposecode=="" || epurposecode==null || epurposecode=="null" || epurposecode=="undefined"){
			alertmessage('error','Purpose is missing, Please choose the purpose.');
            return false;
		}
		var einovoicetype=$("#einovoicetype").val();
		form_data.append("einovoicetype", einovoicetype);
		var einvoiceno=$("#einvoiceno").val();
		form_data.append("einvoiceno", einvoiceno);

		var einvoicedate=$("#einvoicedate").val();
		form_data.append("einvoicedate", einvoicedate);

		
		var edescription=$("#edescription").val();
		form_data.append("edescription", edescription);
		
		var eattachment = $("#efile")[0].files[0];
		form_data.append("eattachment", eattachment);
		// console.log(eattachment);

		$("#esavebutton").prop("disabled", true);
		var btnval=$("#esavebutton").val();
		$("#esavebutton").html('Please wait data are updating..');
		$.ajax
		({
			type: "POST",
			url: "modal/savetodb.php?action=updateeditedrequest",
			data: form_data,
	      	contentType: false,
	      	processData: false,
	      	cache: false,
			dataType:'JSON',
			success: function(res)
			{
				// console.log(res);
				$(".waitloaderfooter").modal("hide");
				for(var i=0; i<res.length; i++){
					if(res[i].status=='Success'){
						$("#esavebutton").prop("disabled", false);
						$("#esavebutton").val(btnval);
						$("#esavebutton").html(btnval);
						$("#editmodal").modal("hide");
						$("#ttl_requestedamont_span").text(res[i].total_rqamount);
						$("#bnfname_"+parentidno+"").text(ebeneficiaryname);
						$("#requestedamount_"+parentidno+"").text(einvoiceamount);
						$("#invoicetype_"+parentidno+"").text(einovoicetype);
						$("#invoiceno_"+parentidno+"").text(einvoiceno);
						$("#invoicedate_"+parentidno+"").text(einvoicedate);
						$("#description_"+parentidno+"").text(edescription);
						$("#purposecategory_"+parentidno+"").text(res[i].purpose_category);
						$("#purpose_"+parentidno+"").text(res[i].purpose);
						$("#purposecode_"+parentidno+"").text(res[i].purpose_code);
						// alertmessage("success",res[i].message);
						// setTimeout(function() {
						//     location.reload();
						// }, 1000);
						SweetAlertmessageFadein('success', res[i].message);
					}else{
						// alert(res[i].message);
						alertmessage("error",res[i].message);
					}
				}
			},beforeSend: function(res){
				$(".waitloaderfooter").modal("show");
			},error: function(xhr, textStatus, errorThrown) {
			    // Handle error
			    console.log(errorThrown);
			}
		});
	});

	//remove invoice entry
	$("#rsavebutton").on("click", function(){
		var countryid ="<?php echo $countryid; ?>";
		var companyid ="<?php echo $companyid; ?>";
		var subcompanyid ="<?php echo $subcompanyid; ?>";
		var projectid ="<?php echo $projectid; ?>";

		var rhiddenbsno =$("#rhiddenbsno").val();
		var parentidno=$("#rhiddenparentidno").val();
		var rdescription=$("#rremark").val();
		var loginname=$("#hidden_loginname").val();
		// alert(rhiddenbsno+'=='+rdescription);

		if(rdescription == "" || rdescription==null || rdescription=="undefined"){
			// alert("Please enter reject remark");
			alertmessage("error", "Please enter the reject remark");
			return false;
		}
		var requestno = "<?php echo $requestno; ?>";
		
		$.ajax
		({
			type: "POST",
			url: "modal/savetodb.php?action=removeinvoicedetails",
			data: {'requestno': requestno, 'rhiddenbsno': rhiddenbsno, 'rdescription': rdescription, 'countryid': countryid, 'companyid': companyid, 'subcompanyid': subcompanyid, 'projectid': projectid, 'loginname': loginname},
			cache: false,
			dataType:'JSON',
			success: function(res)
			{
				// console.log(res);
				$(".waitloaderfooter").modal("hide");
				for(var i=0; i<res.length; i++){
					if(res[i].status=='Success'){
						SweetAlertmessageFadein('success', res[i].message);
					}else{
						alertmessage("error",res[i].message);
					}
				}
			},beforeSend: function (res){
				$(".waitloaderfooter").modal("show");
			}
		});
	});

	$("input[name='requestaction']").on('click', function(){
		var actionradioid=$(this).prop('id');
		if(actionradioid=="confirmtoapprove"){
			$("#approvediv").show();
			$("#rejectdiv").hide();
			$("#addwaitlistdiv").hide();
			$("#rejectremark").val(' ');
			$("#addwaitingremark").val(' ');
		}else if(actionradioid=="confirmtoreject"){
			$("#approvediv").hide();
			$("#rejectdiv").show();
			$("#addwaitlistdiv").hide();
			$("#addwaitingremark").val(' ');
		}else if(actionradioid=="addwaitinglist"){
			$("#approvediv").hide();
			$("#rejectdiv").hide();
			$("#addwaitlistdiv").show();
		}else{
			$("#approvediv").hide();
			$("#rejectdiv").hide();
			$("#rejectremark").val(' ');
			$("#addwaitingremark").val(' ');
			$("#addwaitingremark").val(' ');
			$("#addwaitlistdiv").hide();
		}

		// addwaitinglist
		// addwaitlistdiv
	})

	//rejecte request
	$("#rqs_reject_btn").on("click", function(){
		var countryid ="<?php echo $countryid; ?>";
		var companyid ="<?php echo $companyid; ?>";
		var subcompanyid ="<?php echo $subcompanyid; ?>";
		var projectid ="<?php echo $projectid; ?>";
		var rejectremark=$("#rejectremark").val();
		if(rejectremark == "" || rejectremark==null || rejectremark=="undefined"){
			alertmessage("error","Please enter reject remark");
			return false;
		}
		var requestno = "<?php echo $requestno; ?>";
		var loginname = "<?php echo $loginname; ?>";
		$.ajax
		({
			type: "POST",
			url: "modal/savetodb.php?action=rejectrequest",
			data: {requestno: requestno, rejectremark: rejectremark, loginname: loginname, countryid: countryid, companyid: companyid, subcompanyid: subcompanyid, projectid: projectid},
			cache: false,
			dataType:'JSON',
			success: function(res)
			{
				// console.log(res);
				$(".waitloaderfooter").modal("hide");
				for(var i=0; i<res.length; i++){
					if(res[i].status=='Success'){
						SweetAlertmessageFadein('success', res[i].message);
					}else{
						alertmessage("res",res[i].message);
					}
				}
			},beforeSend: function (res){
				$(".waitloaderfooter").modal("show");
			}
		});
	});
	// add in waitin list	
	$("#rqs_addwaiting_btn").on("click", function(){
		var countryid ="<?php echo $countryid; ?>";
		var companyid ="<?php echo $companyid; ?>";
		var subcompanyid ="<?php echo $subcompanyid; ?>";
		var projectid ="<?php echo $projectid; ?>";
		var mdid ="<?php echo $mdid; ?>";
		var hiddenkey = $("#hidden_key").val();
		var hiddenrooturl = $("#hidden_rooturl").val();
		// ".$rooturl."/request_approval/pending_approval.php?k=".$_GET['k']."&mdid=".$mdid."
		var waitingremark=$("#addwaitingremark").val();
		if(waitingremark == "" || waitingremark==null || waitingremark=="undefined"){
			alertmessage("error","Your remark is required");			
			return false;
		}
		var requestno = "<?php echo $requestno; ?>";
		var loginname = "<?php echo $loginname; ?>";
		$.ajax
		({
			type: "POST",
			url: "modal/savetodb.php?action=requestaddinwaitinglist",
			data: {requestno: requestno, waitingremark: waitingremark, loginname: loginname, countryid: countryid, companyid: companyid, subcompanyid: subcompanyid, projectid: projectid},
			cache: false,
			dataType:'JSON',
			success: function(res)
			{
				// console.log(res);
				$(".waitloaderfooter").modal("hide");
				for(var i=0; i<res.length; i++){
					if(res[i].status=='Success'){
						SweetAlertmessageFadein('success', res[i].message);
						Swal.fire({
						  // title: "Are you sure?",
						  text: res[i].message,
						  icon: "success",
						  showCancelButton: false,
						  confirmButtonColor: "#3085d6",
						  // cancelButtonColor: "#d33",
						  confirmButtonText: "BACK TO REQUEST LIST"
						}).then((result) => {
						  if (result.isConfirmed) {
						    window.location.href=hiddenrooturl+"/request_approval/pending_approval.php?k="+hiddenkey+"&mdid="+mdid;
						  }
						});
					}else{
						// alert(res[i].message);
						alertmessage("res",res[i].message);
					}
				}
			},beforeSend: function (res){
				$(".waitloaderfooter").modal("show");
			}
		});
	});

	//proceed to payment
	$("#rqs_approve_btn").on("click", function(){
		var cpaytoname = $("#paytoname").text();
		$("#cpaytoname").text(cpaytoname);
		var cretuestedamount = $("#ttl_requestedamont_span").text();
		$("#crequestedamount").text(cretuestedamount);
		var amtpay = 0;
		$(".amounttopayp").each(function(){
		    // Parse the text content as an integer, use 0 as default if invalid
		    var amount = parseInt($(this).text()) || 0;
		    amtpay += amount;
		});
		if(parseInt(amtpay)>0){
			$("#forpaidamount").text(amtpay);
		}else{
			$("#forpaidamount").text(cretuestedamount);
		}
		$("#proceetopaymentmodal").modal("show");
	});

	$("#saveforpayment").on("click", function(){
		var noofinvoicediv = $("#invoicedivno").val();		
		var form_data = new FormData();
		var loginname = "<?php echo $loginname; ?>";
		form_data.append('loginname', loginname);
		var requestno = "<?php echo $requestno; ?>";
		form_data.append('requestno', requestno);
		var approvedremark=$("#approvedremark").val();		
		form_data.append('approvedremark', approvedremark);
		var projectid="<?php echo $projectid; ?>";
		form_data.append('projectid', projectid);
		var requesttype = $("#purposetype").text();
		form_data.append('requesttype', requesttype);
		var countryid ="<?php echo $countryid; ?>";
		var companyid ="<?php echo $companyid; ?>";
		var subcompanyid ="<?php echo $subcompanyid; ?>";

		form_data.append('countryid', countryid);
		form_data.append('companyid', companyid);
		form_data.append('subcompanyid', subcompanyid);

		var ArrayDataLIst = new Array();
		var divc = 1;
		for(var cs=0; cs<noofinvoicediv; cs++){
			var parentdiv_id = parseInt(cs) + parseInt(divc);
			var bsno=$("#bsno_"+parentdiv_id).val();
			var beneficiaryname=$("#bnfname_"+parentdiv_id).text();
			var requestedamount=$("#requestedamount_"+parentdiv_id).text();
			//var advancedeductamount=$("#advancedeductamount_"+parentdiv_id).val();
			var tdspercent=$("#tdspercent_"+parentdiv_id).val();
			var incometaxamount=0;			
			var tdscategory=$("#tdscategory_"+parentdiv_id).val();
			var amounttopay=$("#amounttopay_"+parentdiv_id).text();
			var purposecode=$("#purposecode_"+parentdiv_id).text();
			var invoicetype=$("#invoicetype_"+parentdiv_id).text();
			var invoiceno=$("#invoiceno_"+parentdiv_id).text();
			var description=$("#description_"+parentdiv_id).text();
			if(bsno!=""){
				ArrayDataLIst[cs]={
					"bsno" :bsno
					, "beneficiaryname" :beneficiaryname
					, "requestedamount" :requestedamount
					, "tdscategory" :tdscategory
					, "tdspercent" :tdspercent
					, "incometaxamount" :incometaxamount
					, "amounttopay" : amounttopay
					, "purposecode" : purposecode
					, "invoicetype" :invoicetype
					, "invoiceno" :invoiceno
					, "description" :description
				}
			}

		}
		ArrayDataLIst = ArrayDataLIst;
		ArrayDataLIst = JSON.stringify(ArrayDataLIst);
		form_data.append('invoiceinfo', ArrayDataLIst);

		// console.log(ArrayDataLIst);
		var btnval=$("#saveforpayment").html();
		$("#saveforpayment").prop('disabled', true);
		$("#saveforpayment").val('Please wait data is saving..');
		$.ajax
	    ({
	        type: "POST",
	        url: "modal/savetodb.php?action=proceedtopayment",
	        data: form_data,
	        contentType: false,
	        processData: false,
	        cache: false,
	        dataType:'JSON',
	        success: function(res)
	        {
	        	// console.log(res);
	        	$("#saveforpayment").prop('disabled', false);
	          	$("#saveforpayment").html(btnval);
	          	for (var j=0;j<res.length; j++){
	          		if(res[j].status=="Success"){
	          			sendemail_AfterApprovalRequestForPaymentUpdate(res[j].requestno);
	          			SweetAlertmessageFadein('success', res[j].message);
		          	}else{
		           		alertmessage("error",res[j].message);
		           		return false
		         	 }	
	          	}
	        }
	    });
	});
							
});

$("input[name='modeoftransfer_change']").on("change", function(){
	if($(this).val()=="online" || $(this).val()=="Electronic Transfer"){
		$("#bankdetailsdiv").show();			
	}else{
		$("#bankdetailsdiv").hide();
	}
	
});
function changemodeoftransfe(changemodevalue){
	var modeoftransfer = $("#modeoftransfer").text();
	$("input[name='modeoftransfer_change'][value='"+modeoftransfer+"']").prop("checked", true).trigger("change");
	if(changemodevalue=="yes"){
		$("#change_paytobankdetails_modal").show();
		var form_data = new FormData();
		
		form_data.append('companyid', $("#hidden_companyid").val());
		form_data.append('subcompanyid', $("#hidden_subcompanyid").val());
		form_data.append('projectid', $("#hidden_projectid").val());
		form_data.append('loginname', $("body").data("loginname"));

		var modeoftransfer = $("#modeoftransfer").text();
		var paytoname = $("#paytoname").text();
		form_data.append('paytoname', paytoname);
		$("#paytoname_change").text(paytoname);
		$.ajax
		({
			type: "POST",
			url: "modal/requestmodal.php?action=getpayto_bankdetails",
			// data: {paytoname: paytoname},
			data: form_data,
	    	contentType: false,
	    	processData: false,
	    	cache: false,
			dataType:'JSON',
			success: function(res)
			{
				// console.log(res);
				$(".waitloaderfooter").modal("hide");
				for(var i=0; i<res.length; i++){
					$("#pbankname").val(res[i].tobankname);
					$("#pbankaccountno").val(res[i].toaccountno);
					$("#pbankifsccode").val(res[i].tobankifsc);
				}
			},beforeSend: function (res){
				$(".waitloaderfooter").modal("show");
			}
		});
	}else{
		$("#change_paytobankdetails_modal").hide();
	}

}

function tdsdeduction(eleid,tdsval){
	var parentidno=eleid.split("_")[1];
	var tdspercentval=tdsval;
	var invoiceamount = $("#requestedamount_"+parentidno+"").text();
	var taxableamount = $("#taxableamount_"+parentidno+"").text();
	var payamount = Math.round(invoiceamount - (tdspercentval * taxableamount) / 100);
	$("#amounttopay_"+parentidno+"").text(payamount);
}

function editinvoiceinfo(btnid){
	$("#ehiddenbsno").val(' ');
	$("#ebeneficiaryname").prop('selectedIndex',0);
	$("#einvoiceamount").val(' ');
	$("#epurposecode").prop('selectedIndex',0);
	$("#einovoicetype").prop('selectedIndex',0);
	$("#einvoiceno").val(' ');
	$("#einvoicedate").val(' ');
	$("#edescription").val(' ');
	$("#eattachment").prop('src', '');
	var parentidno=btnid.split("_")[1];
	var hiddenbsno = $("#hiddenbsno_"+parentidno+"").text();
	var beneficiaryname = $("#bnfname_"+parentidno+"").text();
	// alert(beneficiaryname);
	request_beneficiaryfrommaster("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","ebeneficiaryname",beneficiaryname);
	var invoiceamount = $("#requestedamount_"+parentidno+"").text();
	var invoicetype = $("#invoicetype_"+parentidno+"").text();
	var invoiceno = $("#invoiceno_"+parentidno+"").text();
	var invoicedate = $("#invoicedate_"+parentidno+"").text();
	var description = $("#description_"+parentidno+"").text();	
	var purposecategorycode = $("#purposecategorycode_"+parentidno+"").text()
	var purposubsecategorycode = $("#purposesubcoategorycode_"+parentidno+"").text()
	var purposecode = $("#purposecode_"+parentidno+"").text();
	var purposecategory = $("#purposecategory_"+parentidno+"").text();
	var purpose = $("#purpose_"+parentidno+"").text();
	var attachment = $("#attachment_"+parentidno+"").prop('src');
	var advancedeductamount = $("#advancedeductamount_"+parentidno+"").val();	
	// request_purposecategoryfrommaster('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid',purposecategorycode,'epcategory');
	purposelist_withcategoryid('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid',purposecategorycode,purposecode,'epurposecode');
	
	// $("#epcategory").prop("disabled", true);
	$("#eparentidno").val(parentidno);
	$("#ehiddenbsno").val(hiddenbsno);
	$("#einvoiceamount").val(invoiceamount);
	// $("#epcategory").data('epcategoryidval',purposecategorycode);
	// $("#epsubepcategory").data('epsubcategoryidval', purposubsecategorycode);
	$("#epurposecode").data('eppurposecodeval',purposecode);
	$("#einovoicetype").val(invoicetype).trigger("change");
	$("#einvoiceno").val(invoiceno);
	$("#einvoicedate").val(invoicedate);
	$("#edescription").val(description);
	$("#eattachment").prop('src', attachment);
	$("#editmodal").modal("show");
}

function deleteinvoiceinfo(btnid){
	$("#rhiddenbsno").val('');
	$("#rhiddenparentidno").val('');
	$("#rbeneficiaryname").text('');
	$("#rinvoiceamount").text('');
	$("#rinvoiceno").text('');
	$("#rdescription").text('');
	$("#rattachment").prop('src','');

	var parentidno=btnid.split("_")[1];
	var hiddenbsno = $("#hiddenbsno_"+parentidno+"").text();
	var beneficiaryname = $("#bnfname_"+parentidno+"").text();
	var invoiceamount = $("#requestedamount_"+parentidno+"").text();
	var invoiceno = $("#invoiceno_"+parentidno+"").text();
	var description = $("#description_"+parentidno+"").text();
	var attachment = $("#attachment_"+parentidno+"").prop('src');	
	$("#rhiddenbsno").val(hiddenbsno);
	$("#rhiddenparentidno").val(parentidno);
	$("#rsavebutton").data("rhiddenbsno", hiddenbsno);
	$("#rsavebutton").data("rhiddenparentidno", parentidno);
	$("#rbeneficiaryname").text(beneficiaryname);
	$("#rinvoiceamount").text(invoiceamount);
	$("#rinvoiceno").text(invoiceno);
	$("#rdescription").text(description);
	$("#rattachment").prop('src', attachment);
	$("#removeinvoiceinfomodal").modal("show");
}

function showhidesavebtn(checkboxid,btnid){
	if ($("#"+checkboxid+"").is(':checked')){
		$("#"+btnid+"").show();
	}else{
		$("#"+btnid+"").hide();
	}
}


function readURL(input) {
	if((input.files[0].size/1024)<=900){
		if (input.files && input.files[0]) {
		    var reader = new FileReader();
			reader.onload = function (e) {
		        $('#eattachment')
	            .attr('src', e.target.result)
	        };
			reader.readAsDataURL(input.files[0]);
		}
	}else{
		event.target.value = '';
		alert("Your file size is grater than 900 KB, Please compress first and It should be less than 900 KB.");
	}
}

$(document).ready(function(){
	//change purpose type for partial bill adjustment
  	$(".change_purposetype_partpayment").click(function (){
	    var purpose_typee = $("#purposetype").text().trim().toLowerCase();
	    $("#headermodal").text('Status of Partial Payment Request');
	    if(purpose_typee=="partial adjustment")
	    {
	      $("#requesttypemessage_label").text("Do You Want to Adjust this Amount as Expenditure ?");
	    }else if(purpose_typee=="expenditure" || purpose_typee=="payment" || purpose_typee=="partial payment")
	    {
	      $("#requesttypemessage_label").text("Do You Want to Adjust this Amount Against Pending Partial Bills ?");
	    }
	    $("#partialamount_yesno").show();
	    $("#changerequesttype_modal").modal("show");
  	});
  
  	$("input[name='partialadjustradio']").click(function (){
    	var pradioval=$(this).val();
	    if(pradioval=='yes'){
		      var reqno = $('#requestno').text();
		      var purposetype=$("#purposetype").text();
		      var actionfor="partial_adjustment";
		    $.ajax
		    ({
		        type : "POST",
		        url : "update_purposetype_json.php",
		        data : {reqno: reqno, purposetype: purposetype, actionfor: actionfor},
		        cache : false,
		        beforeSend: function(html)
		        {
		          $("#updatesp_test").html("<p class='text-pink text-center'>Loading....</p>");
		        },
		        success: function(html)
		        {
		          $("#updatesp_test").html(html);
		          location.reload();
		        }
	      	});
	    }
 	});

  	//change purpose type for Imprest adjustment
  	$(".change_purposetype_imprest").click(function () {
	    var purposeType = $("#purposetype").text().trim().toLowerCase();
	    $("#headermodal").text('Change the Request Type');
	    if (purposeType === "imprest adjustment") {
	        $("#requesttypemessage_label").text("Do you want to pay this amount as Expenditure?");
	        $("#imprestadjust_yesno").show();
		    $("#changerequesttype_modal").modal("show");
	    } else if (purposeType === "expenditure" || purposeType === "payment") {
	        $("#requesttypemessage_label").text("Do you want to adjust this amount against pending Imprest?");
		    $("#imprestadjust_yesno").show();
		    $("#changerequesttype_modal").modal("show");
	    }
	});


  	$("input[name='imprestadjustradio']").click(function (){
    	var pradioval=$(this).val();
    	if(pradioval=='yes'){
      		var reqno = $('#requestno').text();
      		var purposetype=$("#purposetype").text();
      		var actionfor="imprest_adjustment";
	      	$.ajax
	      	({
		        type : "POST",
		        url : "update_purposetype_json.php",
		        data : {reqno: reqno, purposetype: purposetype, actionfor: actionfor},
		        cache : false,
		        beforeSend: function(html)
		        {
		          $("#updatesp_test").html("<p class='text-center'>Loading....</p>");  
		        },
		        success: function(html)
		        {
		          $("#updatesp_test").html(html);
		          location.reload();
		        }
	      	});
	    }
  	});
});

$(document).on("click", ".vwInst", function(){
	var instid=$(this).data('instructionid');
	var rrturl = $("#hidden_rooturl").val();
	voiceinstructiondertails_withshortby('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid','instructionid',instid, function(res){
        if(res.length>0){
          for(var i=0; i<res.length; i++){
            if(res[i].filepath){
              $("#voiceinstructionviewhtml").append('<div class="row"><div class="col-md-12"><audio id="readytorequest_instid" controlsList="nodownload" src="'+rrturl+res[i].filepath+'" class="form-control" class="audioPlayback" controls></audio></div></div>');
            }
          }

          for(var i=0; i<res.length; i++){
             if(res[i].filepath2){
              $("#voiceinstructionviewhtml").append('<div class="row"><div class="col-md-12 p-4"><img id="filepath2" src="'+rrturl+res[i].filepath2+'" class="attachfile" style="width: 100%; height:auto;" /></div></div>');
            }
          }
        }
      });
      $("#viewvoiceinstruction_modal").modal('show');
});
</script>