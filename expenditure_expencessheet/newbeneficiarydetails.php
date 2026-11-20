<?php 
session_start();
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

require('../connection.php');
require('../functions/function_master.php');
$companyid = $_POST['companyid'];
$subcompanyid = $_POST['subcompanyid'];
$projectid = $_POST['projectid'];
$purposecategoryid = $_POST['purposecategoryid'];
$purposesubcategoryid = $_POST['purposesubcategoryid'];
$loginid = $_POST['loginid'];
$transactionamount = $_POST['transactionamount'];
$bnfdivcountno = $_POST['bnfdivcountno'] + 1;
$backdivid=($bnfdivcountno - 1);
$nextdivid=($bnfdivcountno + 1);
$addmorebnfcontainer="";
$widthdrawalamt="";
if($bnfdivcountno>1){
	$widthdrawalamt='';
}else{
	$widthdrawalamt=$transactionamount;
}
 
$backbutton="<img src=\"leftarrow.png\" onclick=\"previousdivshow('beneficiarycontainer_{$bnfdivcountno}','beneficiarycontainer_{$backdivid}');\" style=\"width: 30px; float: right; margin-right: 20px; cursor: pointer;\" />";
$nextdbutton="<button type=\"button\" class=\"btn btn-success btn-right btn-large float-right\"  onclick=\"nextdivshow('beneficiarycontainer_{$bnfdivcountno}','beneficiarycontainer_{$nextdivid}');\" style=\"display:none;\">Next <img src=\"rightarrow.png\" style=\"width: 30px;\" /></button>";	
 
$issupportingdocument = $_POST['issupportingdocument'];
if(strtolower($issupportingdocument)=="withdocument"){
	$addmorebnfcontainer="<button type='button' class='btn btn-deepblue float-right admorevouchercontainer' id='admorevouchercontainer_{$bnfdivcountno}' onclick=\"newbeneficiarydetails_html('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','purposecategory','purposesubcategory','hidden_loginid','transactionamount','beneficiarycontainer','withdocument','expense_payandbeneficiarydiv','admorevouchercontainer_{$bnfdivcountno}');\">Add More Voucher</button>";
?>
<div class="beneficiarycontainer mt-4" id="beneficiarycontainer_<?php echo $bnfdivcountno; ?>">
	<div class="main-container bg-info">
	    <div class="row p-2">
	      <div class="col-md-12">
	        <?php echo $backbutton; ?>
					<h5 class="uppercase bold">DETAIL OF BENEFICIARY FOR <b style="color:blue;">VOUCHER NO <?php echo $bnfdivcountno; ?></b></h5>
	      </div>
	    </div>
  	</div>
	<div class="main-container bg-white p-2 pt-4 pb-4">
	    <div class="row">
	      <div class="col-md-6">
	      	<p name="attach">Supporting Document:</p>
	        <!-- <input id="graph" type='file' onchange="readURL_viewiniframe(this,'attachments_<?php //echo $bnfdivcountno; ?>');"/> -->
	        <span class="text-danger" id="messageviewid_<?php echo $bnfdivcountno; ?>"></span>
	        <input type='file' id="uploadinput_<?php echo $bnfdivcountno; ?>" onchange="uploadfile_onchangefileinput('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','uploadinput_<?php echo $bnfdivcountno; ?>','attachments_<?php echo $bnfdivcountno; ?>','messageviewid_<?php echo $bnfdivcountno; ?>');"/>
	        <p class="w3-text-gold w3-margin-left">File size should be less than 900 KB, <a class="w3-text-lime" href="https://pdfcompressor.com/" target="_blank">click to compress online</a></p>
	        <a target="_blank" href="bnf_inoice_attachment">
			<iframe src="" id="attachments_<?php echo $bnfdivcountno; ?>" name="attachments_<?php echo $bnfdivcountno; ?>" style="width:100%;height:555px;"></iframe></a>
			<input type="hidden" id="attachmentsval_<?php echo $bnfdivcountno; ?>" name="attachmentsval_<?php echo $bnfdivcountno; ?>" />
			<input type="hidden" id="docsno_<?php echo $bnfdivcountno; ?>" name="docsno_<?php echo $bnfdivcountno; ?>" />
	      </div>
	      <div class="col-md-6">
	        <div class="row p-2">
	          <div class="col-md-12">
	            <label class="lbl-text lbl-full"><input type="checkbox" class="paytoasbeneficiarychkbox" id="paytoassamebeneficiary_<?php echo $bnfdivcountno; ?>" /> Is payto name same as beneficiary?</label>
	          </div>
	        </div>
	        <div class="row p-2">
	          <div class="col-md-12">
	            <label class="lbl-text lbl-full">Beneficiary name :</label>
	            <span class="float-right m-2 refeshpaytolist" onclick='request_beneficiaryfrommaster("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","beneficiaryname_<?php echo $bnfdivcountno; ?>");' style="cursor: pointer; color: green;">  <i class="fa fa-sync"></i> Refresh</span>
	            <i class="fa fa-plus-circle opennewbeneficiarymodal" style="cursor: pointer; height: 28px;"></i>
		          <script type="text/javascript">
	            request_beneficiaryfrommaster("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","beneficiaryname_<?php echo $bnfdivcountno; ?>");
	            </script>
	            <select class="form-control select2manual beneficiaryname_exp" style="width: 100%;" id="beneficiaryname_<?php echo $bnfdivcountno; ?>">
	            </select>
	          </div>
	        </div>
	        <div class="row p-2">
	          <div class="col-md-12">
	            <p class="lbl-text lbl-full">Amount</p>
	            <input type="text" id="expenseamount_<?php echo $bnfdivcountno; ?>" value="<?php echo $widthdrawalamt; ?>" class="form-control expenseamount invoiceamount"  onkeypress="return isNumberKey(event)" oninput="copyText('expenseamount_<?php echo $bnfdivcountno; ?>','taxableamount_<?php echo $bnfdivcountno; ?>');" placeholder='Enter transaction amount'/>
	          </div>
	        </div>
	        <div class="row p-2">
	          <div class="col-md-12 d-none">
	            <p class="lbl-text lbl-full">Sub category (optional)</p>
	            <select class="form-control select2 purposesubcategoryslt" style="width: 100%;" id="purposesubcategory_<?php echo $bnfdivcountno; ?>" onchange="request_purposecodefrommaster('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','<?php echo $purposecategoryid; ?>',$(this).val(),'','purposecode_<?php echo $bnfdivcountno; ?>');"></select>
	            <script type="text/javascript">
	            	// request_purposesubcategoryfrommaster('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','<?php echo $purposecategoryid; ?>','<?php echo $purposesubcategoryid; ?>','purposesubcategory_<?php echo $bnfdivcountno; ?>');
	            	purposelist_withcategoryid('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid','<?php echo $purposecategoryid; ?>','','purposecode_<?php echo $bnfdivcountno; ?>');
	            </script>
	          </div>
	        </div>
	        <div class="row p-2">
	          <div class="col-md-12">
	            <p class="lbl-text lbl-full">Purpose</p>
	            <select class="form-control select2 purposecodeslt" style="width: 100%;" id="purposecode_<?php echo $bnfdivcountno; ?>"></select>
	          </div>
	        </div>
	        <div class="row mt-2 d-none" id="isponodiv_<?php echo $bnfdivcountno; ?>">
						<div class="col-md-12">
							<label>Enter the PO number(If available)</label><br/>
							<input type="text" class="form-control" name="ponumber" id="ponumber_<?php echo $bnfdivcountno; ?>" />
						</div>
					</div>
	        <div class="row mt-2">
						<div class="col-md-12">
							<label>Invoice/Voucher Type</label>
							<select class="form-control invoicetype" id="invoicetype_<?php echo $bnfdivcountno; ?>">
								<?php 
								$invl=getinvoicetype($mconn);
								if(count($invl)>0){
									if(count($invl)>1){
										$invlicelist.='<option value="" selected disabled>Choose Invoice Type</option>';
									}
									foreach ($invl as $invkey) {			
										$invlicelist.='<option value="'.$invkey['invoicetype'].'">'.$invkey['invoicetype'].'</option>';	
									}
								}else{
									$invlicelist.='<option value="" style="color:red;">Invoice type not available</option>';
								}
								echo $invlicelist;
								?>
							</select>
						</div>
					</div>
					<div class="row mt-2 dohavegstinvoicediv" id="dohavegstinvoicediv_<?php echo $bnfdivcountno; ?>" style="display:none;">
						<div class="col-md-12">
							<label>Do you have taxable/purchase invoce?</label><br/>
							<label><input type="radio" name="dohavegstinvoice" class="dohavegstinvoice" value="yes" /> YES</label>
							<label><input type="radio" name="dohavegstinvoice" class="dohavegstinvoice" value="no" /> NO</label>
						</div>
					</div>
					<div class="row p-2">
	          <div class="col-md-12">
	            <label>Taxable Amount <b style="color:blue;">(if Available)</b></label>
	            <input type="text" class="form-control taxableamount" onkeypress="return isFloatNumber(event, this)" id="taxableamount_<?php echo $bnfdivcountno; ?>" value="" placeholder='Enter taxable amount'/>
	          </div>
	        </div>
	        <div class="row p-2">
	          <div class="col-md-12">
	            <label>GST Amount <b style="color:blue;">(if Available)</b></label>
	            <input type="text" class="form-control gstamount" onkeypress="return isFloatNumber(event, this)" id="gstamount_<?php echo $bnfdivcountno; ?>" value="" placeholder='Enter GST amount'/>
	          </div>
	        </div>
	        <div class="row p-2">
	          <div class="col-md-12">
	            <p class="lbl-text lbl-full">Invoice/Voucher No against <b style="color:blue;">(if Available)</b></p>
	            <input type="text" id="voucherno_<?php echo $bnfdivcountno; ?>" value="" class="form-control" placeholder='Enter transaction amount'/>
	          </div>
	        </div>
	        <div class="row p-2">
	          <div class="col-md-12">
	            <p class="lbl-text lbl-full">Invoice/Voucher Date <b style="color:blue;">(if Available)</b></p>
	            <input type="date" class="form-control invoicedate" id="invoicedate_<?php echo $bnfdivcountno; ?>" value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" placeholder='Enter transaction amount'/>
	          </div>
	        </div>
	        <div class="row p-2">
	          <div class="col-md-12">
	            <label class="lbl-text lbl-full">Remarks : </label>
	            <textarea rows="4" id="description_<?php echo $bnfdivcountno; ?>" style="resize: none;" class="form-control"></textarea>
	          </div>
	        </div>
	      </div>
	    </div>
	</div>
	<div class="row text-center p-2">
    	<div class="col-md-12 text-center">
      		<?php echo $nextdbutton; ?>
      		<?php echo $addmorebnfcontainer; ?>
    	</div>
  	</div>
</div>
<?php 
}else if(strtolower($issupportingdocument)=="withoutdocument"){
	$addmorebnfcontainer="<button type='button' class='btn btn-deepblue float-right admorevouchercontainer' id='admorevouchercontainer_{$bnfdivcountno}' onclick=\"newbeneficiarydetails_html('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','purposecategory','purposesubcategory','hidden_loginid','transactionamount','beneficiarycontainer','withoutdocument','expense_payandbeneficiarydiv','admorevouchercontainer_{$bnfdivcountno}');\">Add more beneficiary details</button>";
?>
<div class="beneficiarycontainer" id="beneficiarycontainer_<?php echo $bnfdivcountno; ?>">
	<div class="main-container bg-info">
	    <div class="row p-2">
	      <div class="col-md-12">
	      	<!-- <img src="leftarrow.png" onclick="previousdivshow('expense_payandbeneficiarydiv','expense_documentdiv');" style="width: 30px; float: right; margin-right: 20px; cursor: pointer;" /> -->
	      	<?php //echo $backbutton; ?>
	        <h5 class="uppercase bold">DETAIL OF BENEFICIARY</h5>
	      </div>
	    </div>
  	</div>
	<div class="main-container bg-white p-2 pt-4 pb-4">
	    <div class="row" style="display: none;">
	      	<div class="col-md-6">
		      	<p name="attach">Supporting Document:</p>
		        <input id="graph" type='file' onchange="readURL_viewiniframe(this,'attachments_<?php echo $bnfdivcountno; ?>');"/>
		        <p class="w3-text-gold w3-margin-left">File size should be less than 900 KB, <a class="w3-text-lime" href="https://pdfcompressor.com/" target="_blank">click to compress online</a></p>
		    </div>
		    <div class="col-md-6">
		        <a target="_blank" href="bnf_inoice_attachment">
						<iframe id="attachments_<?php echo $bnfdivcountno; ?>" name="attachments_<?php echo $bnfdivcountno; ?>" style="width:100%;height:555px;"></iframe></a>
				<input type="hidden" id="attachmentsval_<?php echo $bnfdivcountno; ?>" name="attachmentsval_<?php echo $bnfdivcountno; ?>" />
				<input type="hidden" id="docsno_<?php echo $bnfdivcountno; ?>" name="docsno_<?php echo $bnfdivcountno; ?>" />
		    </div>
		</div>
        <div class="row p-2">
          <div class="col-md-12">
            <p class="lbl-text lbl-full"><input type="checkbox" class="paytoasbeneficiarychkbox" id="paytoassamebeneficiary_<?php echo $bnfdivcountno; ?>" /> Is payto name same as beneficiary?</p>
          </div>
        </div>
        <div class="row p-2">
          <div class="col-md-12">
            <label class="lbl-text lbl-full">Beneficiary name :</label>
            <span class="float-right m-2 refeshpaytolist" onclick='request_beneficiaryfrommaster("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","beneficiaryname_<?php echo $bnfdivcountno; ?>");' style="cursor: pointer; color: green;"> <i class="fa fa-sync"></i> Refresh</span>
            
            <i class="fa fa-plus-circle opennewbeneficiarymodal" style="cursor: pointer; height: 28px;"></i>
            <script type="text/javascript">
            request_beneficiaryfrommaster("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","beneficiaryname_<?php echo $bnfdivcountno; ?>");
            </script>
            <select class="form-control select2" style="width: 100%;" id="beneficiaryname_<?php echo $bnfdivcountno; ?>"></select>
          </div>
        </div>
        
        <div class="row p-2">
          <div class="col-md-6">
            <p class="lbl-text lbl-full">Amount</p>
            <input type="text" id="expenseamount_<?php echo $bnfdivcountno; ?>" value=""  onkeypress="return isNumberKey(event)" class="form-control expenseamount" oninput="copyText('expenseamount_<?php echo $bnfdivcountno; ?>','taxableamount_<?php echo $bnfdivcountno; ?>');" placeholder='Enter transaction amount'/>
          </div>
          <div class="col-md-6">
            <p class="lbl-text lbl-full">Purpose</p>
            <select class="form-control select2 purposecodeslt" style="width: 100%;" id="purposecode_<?php echo $bnfdivcountno; ?>"></select>
          </div>
        </div>
        
        <div class="row p-2">
          <div class="col-md-12 d-none">
            <p class="lbl-text lbl-full">Sub category</p>
            <select class="form-control select2 purposesubcategoryslt" style="width: 100%;" id="purposesubcategory_<?php echo $bnfdivcountno; ?>" onchange="request_purposecodefrommaster('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','<?php echo $purposecategoryid; ?>',$(this).val(),'','purposecode_<?php echo $bnfdivcountno; ?>');"></select>
            <script type="text/javascript">
            	purposelist_withcategoryid('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid','<?php echo $purposecategoryid; ?>','','purposecode_<?php echo $bnfdivcountno; ?>');
            </script>
          </div>
        </div>
        <div class="row p-2 d-none" id="isponodiv_<?php echo $bnfdivcountno; ?>">
					<div class="col-md-12">
						<p class="lbl-text lbl-full">Enter the PO number(If available)</p>
						<input type="text" class="form-control" name="ponumber" id="ponumber_<?php echo $bnfdivcountno; ?>" />
					</div>
				</div>
        <div class="row mt-2" style="display:none;">
					<div class="col-md-12">
						<p class="lbl-text lbl-full">Invoice/Voucher Type</p>
						<select class="form-control invoicetype" id="invoicetype_<?php echo $bnfdivcountno; ?>">
							<?php
							$invl=getinvoicetype($mconn);
							$invlicelist="";
							if(count($invl)>0){
								if(count($invl)>1){
									$invlicelist.='<option value="" selected disabled>Choose Invoice Type</option>';
								}
								foreach ($invl as $invkey) {			
									$invlicelist.='<option value="'.$invkey['invoicetype'].'">'.$invkey['invoicetype'].'</option>';	
								}
							}else{
								$invlicelist.='<option value="" style="color:red;">Invoice type not available</option>';
							}
							echo $invlicelist;
							?>
						</select>
					</div>
				</div>
				<div class="row p-2" style="display:none;">
          <div class="col-md-12">
            <p class="lbl-text lbl-full">Taxable Amount <b style="color:blue;">(if Available)</b></p>
            <input type="text" class="form-control taxableamount" onkeypress="return isFloatNumber(event, this)" id="taxableamount_<?php echo $bnfdivcountno; ?>" value="" placeholder='Enter taxable amount'/>
          </div>
        </div>
        <div class="row p-2" style="display:none;">
          <div class="col-md-12">
            <p class="lbl-text lbl-full">GST Amount <b style="color:blue;">(if Available)</b></p>
            <input type="text" class="form-control gstamount" onkeypress="return isFloatNumber(event, this)" id="gstamount_<?php echo $bnfdivcountno; ?>" value="" placeholder='Enter GST amount'/>
          </div>
        </div>
        <div class="row p-2" style="display:none;">
          <div class="col-md-12">
            <p class="lbl-text lbl-full">Invoice/Voucher No against <b style="color:blue;">(if Available)</b></p>
            <input type="text" id="voucherno_<?php echo $bnfdivcountno; ?>" value="" class="form-control" placeholder='Enter transaction amount'/>
          </div>
        </div>
        <div class="row p-2" style="display:none;">
          <div class="col-md-12">
            <p class="lbl-text lbl-full">Invoice/Voucher Date <b style="color:blue;">(if Available)</b></p>
            <input type="date" class="form-control invoicedate" id="invoicedate_<?php echo $bnfdivcountno; ?>" value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" placeholder='Enter transaction amount'/>
          </div>
        </div>
        <div class="row p-2">
          <div class="col-md-12">
            <label class="lbl-text lbl-full">Remarks : </label>
            <textarea rows="4" id="description_<?php echo $bnfdivcountno; ?>" style="resize: none;" class="form-control"></textarea>
          </div>
        </div>
	</div>
</div>
<div class="row text-center p-2">
	<div class="col-md-12 text-center">
  		<?php echo $nextdbutton; ?>
  		<?php echo $addmorebnfcontainer; ?>
	</div>
</div>
<?php
}
?>
