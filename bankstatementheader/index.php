<?php
include('../header.php');
?>

<style>
  .blinktext {
    animation: blinker 1s linear infinite;
  }
  @keyframes blinker {
    50% {
      opacity: 0;
    }
  }
</style>
<style>
       .disabled-row {
            opacity: 0.5; /* Reduce opacity */
            pointer-events: none; /* Disable interaction */
        }
    </style>

<div class="main-container">
	<div class="col-md-12 mt-1 mb-5 pt-3 pb-3 text-center" style="box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);">
		<span class="uppercase  bold" style="font-size: 1.75rem;text-shadow:2px 2px gray;">BANK STATEMENTS KEYS MASTER</span>
			<h3 class=""> <?php echo $companyname!=""?"Company Name : ".$companyname:""; ?> </h3>
	</div>
</div>
<div class="shade10 p-3" style="margin: 20px 10px;">
	<div id="div_project">
      <div class="row">
        <h5 class="text-black">Project Information </h5>
      </div>
      <div class="row shade4">

        <div class="responsive" style="margin: 15px 5px;">
          <div class="row">
              <div class="col-md-12 table-responsive">
              <table class="table table-striped table-bordered" id="projectinfo_tbl">
                <thead></thead>
                <tbody>
                	<tr style="display: none;">
                    <td style="width:500px; font-weight: bold;">Select Bank Name:</td>
                    <td style="width:500px;" colspan="3">
                      <select id="banklist" class="form-control" style="margin-top: 0px">
                      <option selected disabled>Select Bank</option>
                     </select>
                    </td>
                    
                  </tr>
                	
                  <tr>
                    <td style="width:500px; font-weight: bold;">Project Name:</td>
                    <td style="width:500px;" colspan="3">
                      <select id="projectlist" class="form-control" style="margin-top: 0px">
                      <option selected disabled>Select Project</option>
                     </select>
                    </td>
                    
                  </tr>
                  
                  <tr>
                    <td style="width:500px; font-weight: bold;">Select Account:</td>
                    <td style="width:500px;" colspan="3">
                      <select id="accountlist" class="form-control" style="margin-top: 0px">
                      <option selected disabled>Select Account</option>
                     </select>
                    </td>
                    
                  </tr>
                  <tr class="accountdetail" style="display:none;">
                    <td style="width:500px; font-weight: bold;">Account Details:</td>
                    <td style="width:500px;">
                      <label>Account Holder Name</label>
                      <input type="text" id="txt_accname" class="form-control">
                    </td>
                     <td style="width:500px;">
                      <label>Account No.</label>
                      <input type="text" id="txt_accountno" class="form-control">
                    </td>
                     <td style="width:500px;">
                      <label>Bank Name</label>
                      <input type="text" id="txt_bankname" class="form-control">
                    </td>
                    
                  </tr>
                  
                  
                </tbody>
              </table>

              </div>
          </div>
        </div>
      </div>
  </div>
</div>

<div class="shade10 p-3" id="div_getkey" style="margin: 20px 10px; display: none;">
	<div>
      <div class="row">
        <h5 class="text-black">Key Details </h5>
      </div>
      <div class="row shade4">

        <div class="responsive" style="margin: 15px 5px;">
          <div class="row">

              <div class="col-md-12 table-responsive">

              <table class="table table-striped table-bordered" id="key_tbl">
              
                <thead>
                   <tr>
                   	<th>Sno</th>
                    <th>Bank Name</th>
                    <th>Value Date</th>
                    <th style="display:none;">Post Date</th>
                    <th style="display:none;">Branch</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Cheque No</th>
                    <th>Transaction Details</th>
                    <th>Withdrawn Amount</th>
                    <th>Deposited Amount</th>
                    <th>Amount</th>
                    <th>CR/DR</th>
                    <th>Balance</th>
                    <th>Header Row</th>
                    <th>Edit</th>
                  </tr>
                </thead>
                <tbody>

                </tbody>
              </table>

              </div>
          </div>
        </div>
      </div>
  </div>
</div>

<div class="shade10 p-3" id="div_keys" style="margin: 20px 10px; display: none;">
	<div>
      <div class="row">
        <h5 class="text-black">Bank Statement keys </h5>
      </div>
      <div class="row shade4">
          <div class="row">
              <div class="col-md-12 table-responsive">

              <table class="table table-striped table-bordered" id="statementkey_tbl">
              
                <thead>
                  <tr>
                  	<th></th>
                  	<th style="width:500px;" colspan="3">Key Position</th>
                  	
                  </tr>
                </thead>
                <tbody>
                	<tr>
                		<td style="width:500px; font-weight: bold;font-size: 15px;">Position Of Header Row: <span class="m-2" style="color:red;">(Only Use Number between 1 to 50) </span></td>
                		<td style="width:500px;" colspan="6">
                      <input type="number" id="txt_headerrow" class="form-control hide_year " name="" style="width: 100%;">
                     
                    </td>
                	</tr>
                  <tr>
                    <td style="width:500px; font-weight: bold;font-size: 15px;">Position Of Date: <span class="m-2" style="color:red;">(Only Use Alphabet between A to Z) </span></td>
                    <td style="width:500px;" colspan="3">
                      <input type="text" id="txt_valuedateposition"  maxlength="1"  class="alphabetOnly form-control hide_year uppercaseTextbox" name="" style="width: 100%;">
                     
                    </td>
                    
                  </tr>
                  <tr style="display: none;">
                    <td style="width:500px; font-weight: bold;font-size: 15px;">Position Of Post Date: <span class="m-2" style="color:red;">(Only Use Alphabet between A to Z) </span></td>
                    <td style="width:500px;" colspan="3">
                      <input type="text" id="txt_postdateposition"  maxlength="1" class="alphabetOnly form-control hide_year uppercaseTextbox" name="" style="width: 100%;">
                     
                    </td>
                   
                  </tr>
                  <tr style="display: none;">
                    <td style="width:500px; font-weight: bold;font-size: 15px;">Position Of Branch: <span class="m-2" style="color:red;">(Only Use Alphabet between A to Z) </span></td>
                    <td style="width:500px;" colspan="3">
                      <input type="text" id="txt_branchposition"  maxlength="1" class="alphabetOnly form-control hide_year uppercaseTextbox" name="" style="width: 100%;">
                     
                    </td>
                   
                  </tr>
                  <tr>
                    <td style="width:500px; font-weight: bold;font-size: 15px;">Position Of Type(DD/SO/Other): <span class="m-2" style="color:red;">(Only Use Alphabet between A to Z) </span></td>
                    <td style="width:500px;" colspan="3">
                      <input type="text" id="txt_typeposition"  maxlength="1" class="alphabetOnly form-control hide_year uppercaseTextbox" name="" style="width: 100%;">
                     
                    </td>
                    
                  </tr>
                  <tr>
                    <td style="width:500px; font-weight: bold;font-size: 15px;">Position Of Description: <span class="m-2" style="color:red;">(Only Use Alphabet between A to Z) </span></td>
                    <td style="width:500px;" colspan="3">
                      <input type="text" id="txt_descriptionposition"  maxlength="1" class="alphabetOnly form-control hide_year uppercaseTextbox" name="" style="width: 100%;">
                     
                    </td>
                    
                  </tr>
                  <tr>
                    <td style="width:500px; font-weight: bold;font-size: 15px;">Position Of Cheque No: <span class="m-2" style="color:red;">(Only Use Alphabet between A to Z) </span></td>
                    <td style="width:500px;" colspan="3">
                      <input type="text" id="txt_chequeposition"  maxlength="1" class="alphabetOnly form-control hide_year uppercaseTextbox" name="" style="width: 100%;">
                     
                    </td>
                   
                  </tr>
                  <tr>
                    <td style="width:500px; font-weight: bold;font-size: 15px;">Position Of Transaction Details: <span class="m-2" style="color:red;">(Only Use Alphabet between A to Z) </span></td>
                    <td style="width:500px;" colspan="3">
                      <input type="text" id="txt_transactionposition"  maxlength="1" class="alphabetOnly form-control hide_year uppercaseTextbox" name="" style="width: 100%;">
                     
                    </td>
                   
                  </tr>
                  <tr>
                    <td style="width:500px; font-weight: bold;font-size: 15px;">Position Of Balance: <span class="m-2" style="color:red;">(Only Use Alphabet between A to Z) </span></td>
                    <td style="width:500px;" colspan="3">
                      <input type="text" id="txt_balanceposition"  maxlength="1" class="alphabetOnly form-control hide_year uppercaseTextbox" name="" style="width: 100%;">
                     
                    </td>
                    
                  </tr>
                  <tr>
                    <td>
                      <div class="row">
                        <div class="col-md-6">
                           <label style="width:500px; font-weight: bold; color:orangered; font-size: 14px;">Single Column of Withdrawn Amount  and Deposit Amount:</label>
                           
                        </div>
                        
                        
                      </div>

                    </td>
                    <td>
                      <div class="row">
                        
                        <div class="col-md-6">
                          <label style="font-weight: bold; color:orangered; font-size: 14px;">Yes</label>
                          <input type="radio"  class="typeWCA" name="typeWCA" value="AM" style="">
                        </div>
                       <div class="col-md-6">
                          <label style="font-weight: bold; color:orangered; font-size: 14px;">No</label>
                          <input type="radio"  class="typeWCA" name="typeWCA" value="WD" style="">
                        </div>
                        
                      </div>

                    </td>
                  </tr>


                  <tr class="cls_WD" style="display:none;">
                    <td style="width:500px; font-weight: bold;font-size: 15px;">Position Of Withdrawn Amount: <span class="m-2" style="color:red;">(Only Use Alphabet between A to Z) </span></td>
                    <td style="width:500px;" colspan="3">
                      <input type="text" id="txt_debitposition"  maxlength="1" class="alphabetOnly form-control hide_year uppercaseTextbox" name="" style="width: 100%;">
                     
                    </td>
                    
                  </tr>
                  <tr class="cls_WD" style="display:none;">
                    <td style="width:500px; font-weight: bold;font-size: 15px;">Position Of Deposited Amount: <span class="m-2" style="color:red;">(Only Use Alphabet between A to Z) </span></td>
                    <td style="width:500px;" colspan="3">
                      <input type="text" id="txt_creditposition"  maxlength="1" class="alphabetOnly form-control hide_year uppercaseTextbox" name="" style="width: 100%;">
                     
                    </td>
                    
                  </tr>
                  <tr class="cls_AM" style="display:none;">
                    <td style="width:500px; font-weight: bold;font-size: 15px;">Position Of Amount: <span class="m-2" style="color:red;">(Only Use Alphabet between A to Z) </span></td>
                    <td style="width:500px;" colspan="3">
                      <input type="text" id="txt_amountposition"  maxlength="1" class="alphabetOnly form-control hide_year uppercaseTextbox" name="" style="width: 100%;">
                     
                    </td>
                   
                  </tr>
                  <tr class="cls_AM" style="display:none;">
                    <td>
                      <div class="row">
                        <div class="col-md-6">
                           <label style="width:500px; font-weight: bold; color:orangered; font-size: 14px;">Do you have  CR/DR Column ?:</label>
                           
                        </div>
                        
                        
                      </div>

                    </td>
                    <td>
                      <div class="row">
                        
                        <div class="col-md-6">
                          <label style="font-weight: bold; color:orangered; font-size: 14px;">Yes</label>
                          <input type="radio"  class="typeCRDR" name="typeCRDR" value="CRDR" style="">
                        </div>
                       <div class="col-md-6">
                          <label style=" font-weight: bold; color:orangered; font-size: 14px;">No</label>
                          <input type="radio"  class="typeCRDR" name="typeCRDR" value="NP" style="">
                        </div>
                        
                      </div>

                    </td>
                  </tr>
                  <tr class="cls_CRDR" style="display:none;">
                    <td style="width:500px; font-weight: bold;font-size: 15px;">Position Of CR/DR: <span class="m-2" style="color:red;">(Only Use Alphabet between A to Z) </span></td>
                    <td style="width:500px;" colspan="3">
                      <input type="text" id="txt_crdrposition"  maxlength="1" class="alphabetOnly form-control hide_year uppercaseTextbox" name="" style="width: 100%;">
                     
                    </td>
                   
                  </tr>
                   <tr class="cls_NP" style="display:none;">
                    <td style="width:500px; font-weight: bold;font-size: 15px;">Negative(-) and Positive(+) Sign: <span class="m-2" style="color:red;">(Only Use Alphabet between A to Z) </span></td>
                    <td style="width:500px;" colspan="3">
                      <input type="text" id="txt_npposition"  maxlength="1" class="alphabetOnly form-control hide_year uppercaseTextbox" name="" style="width: 100%;">
                     
                    </td>
                   
                  </tr>
                  
                  <tr class="" style="display:none;">
                      <td style="width:500px; font-weight: bold;font-size: 15px;">Payer name: <span class="m-2" style="color:red;">(Only Use Alphabet between A to Z) </span></td>
                      <td style="width:500px;" colspan="3">
                        <input type="text" id="txt_payernameposition"  maxlength="1" class="alphabetOnly form-control hide_year uppercaseTextbox" name="" style="width: 100%;">
                       
                      </td>
                    
                  </tr>
                  <tr class="" style="display:none;">
                      <td style="width:500px; font-weight: bold;font-size: 15px;">Payto name: <span class="m-2" style="color:red;">(Only Use Alphabet between A to Z) </span></td>
                      <td style="width:500px;" colspan="3">
                        <input type="text" id="txt_paytonameposition"  maxlength="1" class="alphabetOnly form-control hide_year uppercaseTextbox" name="" style="width: 100%;">
                       
                      </td>
                    
                  </tr>
                  
                </tbody>
              </table>

              </div>
              <div class="row">
              	<div class="col-md-6"></div>
              	<div class="col-md-6">
              		 <input type="button" class="btn btn-deepblue text-center" id="btn_addnew" value="Save Key"/>
              	</div>
              	
              	
              </div>
             
          </div>
      </div>
	</div>
</div>
<div class="modal waitloader" id="waitloader">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-body">
				<div class="loader-container">
					<div class="loader"></div>
					<label class="loader-text">Processing...<br>Please Wait!</label>
				</div>
			</div>
		</div>
	</div>
</div>

<?php
include('../footer.php');
?>
<script>
rooturl= $("#hidden_rooturl").val();
companyid="<?php echo $companyid; ?>"
subcompanyid="<?php echo $subcompanyid; ?>"
var edit="";
	$(document).ready(function(){
		authorizedprojectlist_frommaster(rooturl,companyid,subcompanyid,'projectlist');
		getbanknamefrommaster(rooturl,companyid,'banklist');
		$("#projectlist").on('change',function(){
		var projectid=$("#projectlist").val();
		edit="";
		getaccountfrommasterwithcreditcard(rooturl,companyid,subcompanyid,projectid,'accountlist');
		
		});
    $('.uppercaseTextbox').on('input', function () {
        // Convert the current value to uppercase and set it back
        $(this).val($(this).val().toUpperCase());
    });
		$("#accountlist").on('change',function(){
			edit="";
			var accdataval=$('#accountlist').val();
      var acctext=$("#accountlist option:selected").text();
			var result = accdataval.split("|");
			var accountid=result[0];
			var bankid=result[1];
			var projectid=$("#projectlist").val();

      var result2 = acctext.split("|");
      var accname=result2[0];
      var accno=result2[1];
      var bankname=result2[2];
      $("#txt_accname").val(accname);
      $("#txt_accountno").val(accno);
      $("#txt_bankname").val(bankname);
      $(".accountdetail").show();

			getstatementkeydata(companyid,subcompanyid,projectid,accountid,bankid);
		});
		$('#btn_addnew').click(function(){
			var hrow="";
			var valuedateposition="";
			var valuedatetext="";
			var postdateposition="";
			var postdatetext="";
			var branchposition="";
			var branchtext="";
      var typeposition="";
			var descriptionposition="";
			var descriptiontext="";
			var chequeposition="";
			var chequetext="";
			var debitposition="";
			var debittext="";
			var creditposition="";
			var credittext="";
			var balanceposition="";
			var balancetext="";
			var transactionposition="";
			var transactiontext="";
			var crdrposition="";
			var crdrtext="";
			var amountposition="";
			var amounttext="";
			var accdataval=$('#accountlist').val();
			if (accdataval==null || accdataval=='') {
				alert('Please select account name.');
				$('#accountlist').focus();
				return false;
			}
			if (edit) {
				if (!chkvalidation())
				{
					return false;
				}
			}else if (edit==""){
				if (!chkvalidation())
				{
					return false;
				}
			}

       var chkdubli=chkdublicatevalue("statementkey_tbl");
        if (chkdubli) {
          alert(chkdubli);
          return false;

        }
			
			var bankname="";
			var projectid=$("#projectlist").val();
			var result = accdataval.split("|");
			var bankid=result[1];
			var accountid=result[0];
			
			var accinfo=$("#accountlist option:selected").text();
			var result2 = accinfo.split("|");
			var bankname=result2[2];

			
				 hrow=$('#txt_headerrow').val();
				 valuedateposition=$('#txt_valuedateposition').val();
				 
				 postdateposition=$('#txt_postdateposition').val();
				
				 branchposition=$('#txt_branchposition').val();
				 
         typeposition=$('#txt_typeposition').val();
				 descriptionposition=$('#txt_descriptionposition').val();
				 
				 chequeposition=$('#txt_chequeposition').val();
				
				 debitposition=$('#txt_debitposition').val();
				 
				 creditposition=$('#txt_creditposition').val();
				
				 balanceposition=$('#txt_balanceposition').val();
				
				 transactionposition=$('#txt_transactionposition').val();
				
				 crdrposition=$('#txt_crdrposition').val();

				 npposition=$('#txt_npposition').val();

         amountposition=$('#txt_amountposition').val();

         payernameposition=$('#txt_payernameposition').val();
         paytonameposition=$('#txt_paytonameposition').val();
				 
				
			

       if (valuedateposition!="") {
          valuedateposition=getAlphabetValue(valuedateposition);
        }
        if (postdateposition!="") {
          postdateposition=getAlphabetValue(postdateposition);
        }
        if (branchposition!="") {
          branchposition=getAlphabetValue(branchposition);
        }
        if (typeposition!="") {
          typeposition=getAlphabetValue(typeposition);
        }
        if (descriptionposition!="") {
          descriptionposition=getAlphabetValue(descriptionposition);
        }
        if (chequeposition!="") {
          chequeposition=getAlphabetValue(chequeposition);
        }
        if (debitposition!="") {
          debitposition=getAlphabetValue(debitposition);
        }
        if (creditposition!="") {
          creditposition=getAlphabetValue(creditposition);
        }
        if (balanceposition!="") {
          balanceposition=getAlphabetValue(balanceposition);
        }
        if (transactionposition!="") {
          transactionposition=getAlphabetValue(transactionposition);
        }
        if (crdrposition!="") {
          crdrposition=getAlphabetValue(crdrposition);
        }
        if (amountposition!="") {
          amountposition=getAlphabetValue(amountposition);
        }
        if (npposition!="") {
          npposition=getAlphabetValue(npposition);
        }
        if (payernameposition!="") {
          payernameposition=getAlphabetValue(payernameposition);
        }
        if (paytonameposition!="") {
          paytonameposition=getAlphabetValue(paytonameposition);
        }


			

			$.ajax({

      type: "POST", 

      url: "modal/modal.php?action=statementkey",

      data: {companyid: companyid,subcompanyid: subcompanyid,projectid: projectid,accountid:accountid,bankid: bankid,hrow:hrow,valuedateposition:valuedateposition,valuedatetext:valuedatetext,postdateposition:postdateposition,postdatetext:postdatetext,branchposition:branchposition,branchtext:branchtext,typeposition:typeposition,descriptionposition:descriptionposition,descriptiontext:descriptiontext,chequeposition:chequeposition,chequetext:chequetext,debitposition:debitposition,debittext:debittext,creditposition:creditposition,credittext:credittext,balanceposition:balanceposition,balancetext:balancetext,transactionposition:transactionposition,transactiontext:transactiontext,crdrposition:crdrposition,crdrtext:crdrtext,amountposition:amountposition,amounttext:amounttext,bankname:bankname,npposition:npposition,payernameposition:payernameposition,paytonameposition:paytonameposition,edit:edit},

       dataType:'JSON',

      success: function(res)

      {

        //console.log(res);
       $(".waitloader").hide();
        if(res.length>0){
        	
        	if (res[0].status=="Done") {
        		alert(res[0].message);
        		edit="";
        		$('#div_keys').hide();
        		clearfild();
        		getstatementkeydata(companyid,subcompanyid,projectid,accountid,bankid);

        	}else{
        		alert(res[0].message);
        		
        	}
        }

       

      },beforeSend(res){

        $(".waitloader").show();

      }
      });
		});

    $('input[name="typeWCA"]').change(function () {
        // Get the selected value
        let selectedValue = $('input[name="typeWCA"]:checked').val();
        
        if (selectedValue=="WD") {
          $(".cls_WD").show();
          $(".cls_AM").hide();
          $(".cls_BL").show();
          $(".cls_CRDR").hide();
          $(".cls_NP").hide();
          $("#txt_amountposition").val("");
          if (edit) {
          $("#txt_debitposition").val($("#txt_debitposition2").val());
          $("#txt_creditposition").val($("#txt_creditposition2").val());

          }
        }else if (selectedValue=="AM") {
          $(".cls_AM").show();
          $(".cls_WD").hide();
          //$(".cls_BL").show();
          $(".cls_CRDR").hide();
          $(".cls_NP").hide();
          $(".cls_BL").hide();
          $("#txt_debitposition").val("");
          $("#txt_creditposition").val("");
          if (edit) {
            $("#txt_amountposition").val($("#txt_amountposition2").val());
          }

        }
       
    });
    $('input[name="typeCRDR"]').change(function () {
        // Get the selected value
        let selectedValue = $('input[name="typeCRDR"]:checked').val();
        
        if (selectedValue=="CRDR") {
          $(".cls_CRDR").show();
          $(".cls_NP").hide();
          $(".cls_BL").show();
          if (edit) {
            $("#txt_crdrposition").val($("#txt_crdrposition2").val());
          }
        }else if (selectedValue=="NP") {
           $(".cls_CRDR").hide();
          // $(".cls_NP").show();
           $(".cls_BL").show();
           $("#txt_crdrposition").val("");

        }
       
    });

		$(document).on("click", ".iskey", function(){
			$('.hide_year').removeClass('disabled-row');
       edit=$('#txt_sno').val();
        $("#div_getkey").hide();
        $("#div_keys").show();

         $('#txt_headerrow').val($('#txt_headerrow2').val());
         $('#txt_valuedateposition').val( $('#txt_valuedateposition2').val());
         
         $('#txt_postdateposition').val($('#txt_postdateposition2').val());
        
         $('#txt_branchposition').val($('#txt_branchposition2').val());
         
         $('#txt_typeposition').val($('#txt_typeposition2').val());
         $('#txt_descriptionposition').val($('#txt_descriptionposition2').val());
         
         $('#txt_chequeposition').val($('#txt_chequeposition2').val());
        
         $('#txt_debitposition').val($('#txt_debitposition2').val());
         $('#txt_creditposition').val($('#txt_creditposition2').val());
         if ($('#txt_debitposition2').val()=="" && $('#txt_amountposition2').val()!="")
         {
            
             $('input[name="typeWCA"][value="AM"]').prop("checked", true).trigger("change");
         }
        
         $('#txt_balanceposition').val($('#txt_balanceposition2').val());
        
         $('#txt_transactionposition').val($('#txt_transactionposition2').val());
        
         $('#txt_crdrposition').val($('#txt_crdrposition2').val());
         if ($('#txt_crdrposition2').val()!="")
         {
            
             $('input[name="typeCRDR"][value="CRDR"]').prop("checked", true).trigger("change");
         }

         $('#txt_npposition').val($('#txt_npposition2').val());

         $('#txt_amountposition').val($('#txt_amountposition2').val());

         $('#txt_payernameposition').val($('#txt_payernameposition2').val());
        $('#txt_paytonameposition').val($('#txt_paytonameposition2').val());

		});
    
    $(document).on("click", ".iskeysave", function(){
      edit=$('#txt_sno').val();
      
      $('#btn_addnew').trigger('click');
    });

    $('.alphabetOnly').keypress(function (e) {
        if (!isAlphabetKey(e)) {
            e.preventDefault(); // Prevent non-alphabet input
        }
    });
		
	});

function clearfild(){
			edit="";
	    $('#txt_headerrow').val('');
			$('#txt_valuedateposition').val('');
			
			$('#txt_postdateposition').val('');
			
			$('#txt_branchposition').val('');
		
			$('#txt_descriptionposition').val('');
			
			$('#txt_chequeposition').val('');
			
			$('#txt_debitposition').val('');
			
			$('#txt_creditposition').val('');
			
			$('#txt_balanceposition').val('');
			
			$('#txt_transactionposition').val('');
			
			$('#txt_crdrposition').val('');
			
			$('#txt_amountposition').val('');
			
}

// function getalphabatvalue(alpabat){
//   var arrayalfabat=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');

// }

function getAlphabetValue(alphabet) {
    // Define the alphabet array
    var arrayAlphabet = [
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 
        'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'
    ];
    
    // Convert input to uppercase for a case-insensitive match
    alphabet = alphabet.toUpperCase();

    // Find the position using indexOf
    var position = arrayAlphabet.indexOf(alphabet);

    if (position !== -1) {
        console.log(`The position of '${alphabet}' is: ${position}`);
        return position; // Return the index
    } else {
        console.log(`'${alphabet}' is not a valid letter.`);
        return -1; // Return -1 if not found
    }
}

function getAlphaNumericValue(position) {
    // Define the alphabet array
    var arrayAlphabet = [
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 
        'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'
    ];

    // Validate the position (0 to 25)
    if (position >= 0 && position < arrayAlphabet.length) {
        return arrayAlphabet[position]; // Return the letter at the given position
    } else {
        return `Invalid position: ${position}. Position should be between 0 and 25.`;
    }
}



 function isAlphabetKey(e) {
    let charCode = e.which || e.keyCode; // Get ASCII code of key pressed
    if (
        (charCode >= 65 && charCode <= 90) ||  // Uppercase letters A-Z
        (charCode >= 97 && charCode <= 122)   // Lowercase letters a-z
    ) {
        return true; // Alphabet key pressed
    }
    return false; // Non-alphabet key pressed
}

function chkvalidation(){
 
  if ($('#txt_headerrow').val()=='') {
    alert('Header row is empty');
    $('#txt_headerrow').focus();
    return false;
  }else if ($('#txt_valuedateposition').val()=='') {
    alert('Value date is empty');
    $('#txt_valuedateposition').focus();
    return false;
  }else if ($('#txt_descriptionposition').val()=='') {
    alert('Description  is empty');
    $('#txt_descriptionposition').focus();
    return false;
  }else if ($('#txt_debitposition').val()!="") {
    if ($('#txt_amountposition').val()!="") {
      alert('If you enter debit amount and credit amount key so no neet to enter amount and CR/DR keys.');
      $('#txt_amountposition').focus();
      return false;
    }
  }
	
	
	return true;  
}

function chkvalidation2(){

	
  if ($('#txt_headerrow2').val()=='') {
    alert('Header row is empty');
    $('#txt_headerrow2').focus();
    return false;
  }else if ($('#txt_valuedateposition2').val()=='') {
    alert('Value date is empty');
    $('#txt_valuedateposition2').focus();
    return false;
  }else if ($('#txt_descriptionposition2').val()=='') {
    alert('Description  is empty');
    $('#txt_descriptionposition2').focus();
    return false;
  }else if ($('#txt_debitposition2').val()!="") {
		if ($('#txt_amountposition2').val()!="") {
			alert('If you enter debit amount and credit amount key so no neet to enter amount and CR/DR keys.');
			$('#txt_amountposition2').focus();
			return false;
		}
	}
	return true;  
}

function getstatementkeydata(companyid,subcompanyid,projectid,accountid,bankid) {

	$.ajax({

      type: "POST", 

      url: "modal/modal.php?action=getstatementkey",

      data: {companyid: companyid,subcompanyid: subcompanyid,projectid: projectid,accountid:accountid,bankid: bankid},

       dataType:'JSON',

      success: function(res)

      {

        console.log(res);
       
        if(res.length>0){
        	
        	$('#key_tbl tbody').empty();
        	var j=1;
        	$('#div_getkey').show();
        	$('#div_keys').hide();

        	for (var i = 0; i<res.length; i++) {

            if (res[i].valuedate) {
              res[i].valuedate=getAlphaNumericValue(res[i].valuedate);
            }
            if (res[i].postdate) {
              res[i].postdate=getAlphaNumericValue(res[i].postdate);
            }
            if (res[i].branch) {
              res[i].branch=getAlphaNumericValue(res[i].branch);
            }
            if (res[i].type) {
              res[i].type=getAlphaNumericValue(res[i].type);
            }
            if (res[i].description) {
              res[i].description=getAlphaNumericValue(res[i].description);
            }
            if (res[i].chequeno) {
              res[i].chequeno=getAlphaNumericValue(res[i].chequeno);
            }
            if (res[i].transactiondetails) {
              res[i].transactiondetails=getAlphaNumericValue(res[i].transactiondetails);
            }
            if (res[i].debitamount) {
              res[i].debitamount=getAlphaNumericValue(res[i].debitamount);
            }
            if (res[i].creditamount) {
              res[i].creditamount=getAlphaNumericValue(res[i].creditamount);
            }
            if (res[i].amount) {
              res[i].amount=getAlphaNumericValue(res[i].amount);
            }
            if (res[i].drcr) {
              res[i].drcr=getAlphaNumericValue(res[i].drcr);
            }
            if (res[i].balanceamount) {
              res[i].balanceamount=getAlphaNumericValue(res[i].balanceamount);
            }
            

        		
        			$("#key_tbl tbody").append("<tr><td style='text-align:center;' class='td_sno'>"+j+"</td><td style='text-align:left;'>"+res[i].bankname+"</td><td class='alphabetOnly' style='text-align:left;'><input type='text'  maxlength='1' id='txt_valuedateposition2' class='alphabetOnly form-control hide_year'  style='width: 100%;' value='"+res[i].valuedate+"'></td><td class='alphabetOnly' style='text-align:left; display:none;'> <input type='text' id='txt_postdateposition2' maxlength='1' class='alphabetOnly form-control hide_year'  style='width: 100%;' value='"+res[i].postdate+"'></td><td class='alphabetOnly'  style='text-align:left; display:none;'><input type='text' id='txt_branchposition2' maxlength='1' class='alphabetOnly form-control hide_year'  style='width: 100%;' value='"+res[i].branch+"'></td><td class='alphabetOnly' style='text-align:left;'><input type='text' id='txt_typeposition2' maxlength='1' class='alphabetOnly form-control hide_year'  style='width: 100%;' value='"+res[i].type+"'><td class='alphabetOnly' style='text-align:left;'><input type='text' id='txt_descriptionposition2' maxlength='1' class='alphabetOnly form-control hide_year'  style='width: 100%;' value='"+res[i].description+"'></td><td class='alphabetOnly' style='text-align:left;'><input type='text' id='txt_chequeposition2' maxlength='1' class='alphabetOnly form-control hide_year'  style='width: 100%;' value='"+res[i].chequeno+"'></td><td class='alphabetOnly' style='text-align:left;'><input type='text' id='txt_transactionposition2' maxlength='1' class='alphabetOnly form-control hide_year'  style='width: 100%;' value='"+res[i].transactiondetails+"'></td><td class='alphabetOnly' style='text-align:left;'><input type='text' id='txt_debitposition2' maxlength='1' class='alphabetOnly form-control hide_year'  style='width: 100%;' value='"+res[i].debitamount+"'></td><td class='alphabetOnly' style='text-align:left;'><input type='text' id='txt_creditposition2' maxlength='1'  class='alphabetOnly form-control hide_year'  style='width: 100%;' value='"+res[i].creditamount+"'></td><td class='alphabetOnly' style='text-align:left;'><input type='text' id='txt_amountposition2' maxlength='1' class='alphabetOnly form-control hide_year'  style='width: 100%;' value='"+res[i].amount+"'></td><td class='alphabetOnly' style='text-align:left;'><input type='text' id='txt_crdrposition2' maxlength='1' class='alphabetOnly form-control hide_year'  style='width: 100%;' value='"+res[i].drcr+"'></td><td class='alphabetOnly' style='text-align:left;'><input type='text' id='txt_balanceposition2' maxlength='1' class='alphabetOnly form-control hide_year'  style='width: 100%;' value='"+res[i].balanceamount+"'></td><td style='text-align:left;'><input type='number' id='txt_headerrow2' class='form-control hide_year'  style='width: 100%;' value='"+res[i].hrow+"'></td><td style='text-align:center;'><i class='fa fa-edit iskey' style='font-size: 28px; cursor:pointer;'></i><i class='fa fa-save iskeysave' style='font-size: 28px; cursor:pointer; display:none;'></i></td><td style='text-align:center;display:none;'><input type='number' id='txt_sno' class='form-control hide_year'  style='width: 100%;' value='"+res[i].sno+"'></td></tr>");
        		  j++;
        		}	
        	
             $('.hide_year').addClass('disabled-row');

        	}else{
        		$('#div_keys').show();
        		$('#key_tbl tbody').empty();
        	  $('#div_getkey').hide();
        		
        	}
      },beforeSend(res){}

        

      
      });
}

function chkdublicatevalue(tablename){
  var values = [];
  var duplicates = [];
  var returnval="";
$("#"+tablename+" tbody tr").each(function(row, tr){
      var value=$(tr).find('td:eq(1)').find('input').val().toUpperCase();
      if (value!="") {
        if (values.indexOf(value) !== -1) {
            // If the value is already in the array, it's a duplicate
            duplicates.push(value);
        } else {
            values.push(value); // Add the value to the array if not found
        }
      }
      
  });

    if (duplicates.length > 0) {
          //alert("Duplicate values found: " + duplicates.join(", "));
          returnval="Duplicate values found: " + duplicates.join(", ");

      } else {
          //alert("No duplicate values found.");
        returnval="";
      }

      return returnval;

}
	
	
</script>