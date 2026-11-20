<?php
$protocol=$_SERVER['PROTOCOL'] = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';
$url = basename( $_SERVER["PHP_SELF"]);
$rooturl = $protocol.'://'.$_SERVER['HTTP_HOST']."/";
$baseurl= $rooturl.str_replace($url, "", $_SERVER["PHP_SELF"]);
?>
<div class="beneficiarycontainer mt-4" id="beneficiarycontainer_<?php echo $bnfdivcountno; ?>" <?php echo $stylediv; ?> >
	<div class="main-container bg-info">
	    <div class="row p-2">
	      <div class="col-md-12">
	      	<?php echo $backbutton; ?>
	        <h5 class="uppercase bold">DETAIL OF BENEFICIARY FOR <b style="color:blue;">VOUCHER NO <?php echo ($index + 1); ?></b></h5>
	      </div>
	    </div>
  	</div>
	<div class="main-container bg-white p-2 pt-4 pb-4">
	    <div class="row">
	      <div class="col-md-6">
	      	<?php  
	      	if($bnfdivcountno==""){
	      	?>
	      	<p name="attach">Supporting Document:</p>
	        <input id="graph" type='file' onchange="readURL_viewiniframe(this,'attachments_<?php echo $bnfdivcountno; ?>');"/>
	        <p class="w3-text-gold w3-margin-left">File size should be less than 900 KB, <a class="w3-text-lime" href="https://pdfcompressor.com/" target="_blank">click to compress online</a></marquee></p>
	      	<?php
	      	}
	      	?>
	        <a target="_blank" href="bnf_inoice_attachment">
	        <iframe src="<?php echo $path; ?>" id="attachments_<?php echo $bnfdivcountno; ?>" name="attachments_<?php echo $bnfdivcountno; ?>" style="width:100%;height:555px;"></iframe></a>
	        <input type="hidden" id="attachmentsval_<?php echo $bnfdivcountno; ?>" name="attachmentsval_<?php echo $bnfdivcountno; ?>" />
	        <input type="hidden" id="docsno_<?php echo $bnfdivcountno; ?>" name="docsno_<?php echo $bnfdivcountno; ?>" />
	      </div>
	      <div class="col-md-6">
	        <div class="row p-2">
	          <div class="col-md-12">
	            <p class="lbl-text lbl-full"><input type="checkbox" class="paytoasbeneficiarychkbox" id="paytoassamebeneficiary_<?php echo $bnfdivcountno; ?>" /> Is payto name same as beneficiary?</p>
	          </div>
	        </div>
	        
	        <div class="row p-2">
	          <div class="col-md-12">
	            <p class="lbl-text lbl-full">Choose beneficiary name from master :</p>
	            <select class="form-control select2" style="width: 100%;" id="beneficiaryname_<?php echo $bnfdivcountno; ?>">
	               <?php
	                $bnflist = getbeneficiaryinfo($mconn);
	                if(count($bnflist)>1){
	                  echo "<option value='' selected disabled>Select Beneficiary Name</option>";
	                }
	                foreach ($bnflist as $bnfrow) {
	                  echo "<option value='".$bnfrow['beneficiaryname']."'>".$bnfrow['beneficiaryname']."</option>";
	                }
	                ?>
	            </select>
	          </div>
	        </div>
	        
	        <div class="row p-2">
	          <div class="col-md-12">
	            <p class="lbl-text lbl-full">Amount against  <b style="color:blue;">voucher no <?php echo ($index + 1); ?></b></p>
	            <input type="text" id="expenseamount_<?php echo $bnfdivcountno; ?>" value="" class="form-control" placeholder='Enter transaction amount'/>
	          </div>
	        </div>
	        <div class="row p-2">
	          <div class="col-md-12">
	            <p class="lbl-text lbl-full">Voucher No against <b style="color:blue;">(if Available)</b></p>
	            <input type="text" id="voucherno_<?php echo $bnfdivcountno; ?>" value="" class="form-control" placeholder='Enter transaction amount'/>
	          </div>
	        </div>
	        <div class="row p-2">
	          <div class="col-md-12">
	            <p class="lbl-text lbl-full">Purpose sub category (optional)</p>
	            <select class="form-control select2 purposesubcategoryslt" style="width: 100%;" id="purposesubcategory_<?php echo $bnfdivcountno; ?>" onchange="request_purposecodefrommaster('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','<?php echo $purposecategoryid; ?>',$(this).val(),'','purposecode_<?php echo $bnfdivcountno; ?>');"></select>
	            <script type="text/javascript">
	            	request_purposesubcategoryfrommaster('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','<?php echo $purposecategoryid; ?>','<?php echo $purposesubcategoryid; ?>','purposesubcategory_<?php echo $bnfdivcountno; ?>');
	            </script>
	          </div>
	        </div>
	        <div class="row p-2">
	          <div class="col-md-12">
	            <p class="lbl-text lbl-full">Purpose Against Receipt/Voucher (optional)</p>
	            <select class="form-control select2 purposecodeslt" style="width: 100%;" id="purposecode_<?php echo $bnfdivcountno; ?>"></select>
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
</div>