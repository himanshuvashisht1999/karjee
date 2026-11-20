<?php 
session_start();
if($_POST){
$_SESSION['data']=$_POST;
}
else{
$_POST=$_SESSION['data'];
unset($_SESSION['data']);
}
require('../header.php');

?>
<style type="text/css">
.lbl-text{
  color: #581845;
  font-size: 1.5em;
  font-weight: bolder;  
}
input[type="radio"] {
  -ms-transform: scale(1.5); /* IE 9 */
  -webkit-transform: scale(1.5); /* Chrome, Safari, Opera */
  transform: scale(1.5);
  margin: 0px 8px;
}
 label {
  color: #30212D;
  font-size: 1em;
  font-weight: bolder;
}
.form-control {
    display: block;
    width: 100%;
    padding: 0.375rem 0.75rem;
    font-size: 1.1rem;
    line-height: 1.5;
    color: #0c0c0c;
    background-color: #fff;
    background-clip: padding-box;
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out;
}
</style>
<style type="text/css">
    table {
      border: 1px solid #ccc;
      border-collapse: collapse;
      margin: 0;
      padding: 0;
      width: 100%;
      table-layout: fixed;
    }

    table caption {
      font-size: 1.5em;
      margin: .5em 0 .75em;
    }

    table tr {
      background-color: #f8f8f8;
      color: #000;
      border: 1px solid #ddd;
      padding: .35em;
    }

    table th,
    table td {
      padding: .625em;
      text-align: center;
    }

    table th {
      font-size: .85em;
      letter-spacing: .1em;
      text-transform: uppercase;
    }

    @media screen and (max-width: 600px) {
      table {
        border: 0;
      }

      table caption {
        font-size: 1.3em;
      }
     
      table thead {
        border: none;
        clip: rect(0 0 0 0);
        height: 1px;
        margin: -1px;
        overflow: hidden;
        padding: 0;
        position: absolute;
        width: 1px;
      }
     
      table tr {
        border-bottom: 3px solid #ddd;
        display: block;
        margin-bottom: .625em;
      }
     
      table td {
        border-bottom: 1px solid #ddd;
        display: block;
        font-size: .8em;
        text-align: right;
      }
     
      table td::before {
        /*
        * aria-label has no advantage, it won't be read inside a table
        content: attr(aria-label);
        */
        content: attr(data-label);
        float: left;
        font-weight: bold;
        text-transform: uppercase;
      }
     
      table td:last-child {
        border-bottom: 0;
      }
    }
</style>
<div class="main-container" id="maincont">
		<div class="col-md-12 mt-1 mb-5 pt-3 pb-3 text-center" style="box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);">
      <h1 class="uppercase  bold"> <?php echo strtoupper($companyname); ?> </h1>
			<span class="uppercase  bold" style="border-bottom: 1px solid white;font-size: 1.5rem;">INFORMATION FOR EXPENSES SHEETS AND DEPOSIT SHEETS</span>

		</div>
</div>
<details class="alwaysopen" id="choosedetails" open>
<summary>CHOOSE THE OPTION</summary>
<div class="main-container bg-white">
	<div class="row p-2">
		<div class="col-md-6">
			<!-- <input type="radio" name="isexp" value="expenses"  /> <label>FOR EXPENSES</label>	 -->
     
       <input type="button" id="isexp" class="btn btn-success btn-right btn-large" value="FOR EXPENSES">
		</div>
		<div class="col-md-6">
			<!-- <input type="radio" name="isexp" value="deposit" /> <label>FOR DEPOSIT</label>	 -->
     <input type="button" id="isdeposit" class="btn btn-success btn-right btn-large" value="FOR DEPOSIT">
		</div>
				
	</div>
</div>
<div class="main-container bg-white" id="id_choosecashorcheque" style="display:none;">
  <div class="row p-2">
    <div class="col-md-6">
       <input type="button" id="idcash" class="btn btn-deepblue btn-right btn-large" value="FOR CASH">
     
      
    </div>
    <div class="col-md-6">
     <input type="button" id="idcheque" class="btn btn-deepblue btn-right btn-large" value="FOR CHEQUE">
     
    </div>
        
  </div>
</div>
</details>
<div id="index_exp" style="display: none;">
    <div class="main-container">
      <div class="col-md-12 mt-1 mb-5 pt-3 pb-3 text-center" style="box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);">
        <span class="uppercase  bold" style="border-bottom: 1px solid white;font-size: 1.5rem;">INFORMATION FOR EXPENSES SHEET</span>
      </div>
    </div>
    <div class="row p-2">
      <div class="col-md-6"></div>
      <div class="col-md-3"></div>
        <div class="col-md-3">
          <input type="button" id="saveinfo_btn2" class="btn btn-success btn-right btn-large" value="Save">
        </div>
      </div>
  <details class="alwaysopen" id="upload_details" open>
   <summary>Upload a document of transaction</summary>
    <div class="main-container bg-white">
      <div class="row p-2">
        <div class="col-md-12">
          <label class="lbl-text">Do you have any supporting document for this transaction?</label><br>       
          <!-- <input type="file" accept="image/*" id="docid" name="files[]" multiple> -->
          <input type="file" name="files[]" id="importfile" class="w3-input" multiple accept="image/*"/>
        </div>
      </div>
    </div>
   </details>
  <details class="alwaysopen" id="withdrawalbank_details" open>
    <summary>Withdrawal from your bank</summary>
    <div class="main-container bg-white">
      
      
      <div class="row p-2">
        <div class="col-md-12">
          <label class="lbl-text">Account Name : </label>
          <!-- <input type="text" id="banknameid" class="form-control" /> -->
          <select class="form-control w3-padding" id="account_slt">
          </select>

          <input type="hidden" id="accountid" name="accountid" value="">
          <input type="hidden" id="vdate" name="vdate" value="">
          <input type="hidden" id="banksno" name="vdate" value="">
        </div>
      </div>
      <div class="row p-2">
        <div class="col-md-12">         
          <label class="lbl-text">On Date : </label>
          <input type="date" id="dateid" class="form-control" />
        </div>
      </div>
      <!-- <div class="row p-2">
        <div class="col-md-12">
          <label class="lbl-text">Amount is : </label>
          <input type="number" id="withdrowamount" class="form-control" />
        </div>
      </div> -->
      <div class="row p-2">
        <div class="col-md-12">
          <label class="lbl-text">You have paid to</label>
          <input type="text" id="paytoname" class="form-control" />
        </div>
      </div>
      <div class="row p-2">
        <div class="col-md-12">
          <label class="lbl-text">This transaction type?</label><br>
          <input type="radio" name="ischeque" value="cheque" /> <label>CHEQUE</label>
          <input type="radio" name="ischeque" value="online" /> <label>ONLINE</label>
          
        </div>
      </div>
      <div class="row p-2">
        <div class="col-md-12">
          <label class="lbl-text">Cheque/Electronic transfer Reference number is : </label>
          <input type="text" id="chequenoid" class="form-control" />
        </div>
      </div>
      <div class="row p-2">
        <div class="col-md-12">
          <label class="lbl-text">Purpose Category</label>
          <select class="form-control" id="catpurpose_1">
           
          </select>
        </div>
      </div>
      <div class="row p-2">
        <div class="col-md-12">
          <label class="lbl-text">Purpose Sub Category</label>
          <select class="form-control" id="subcatpurpose_1">
             
          </select>
        </div>
      </div>
      <div class="row p-2" style="display:none;">
        <div class="col-md-12">
          <label class="lbl-text">The description on the Transaction : </label>
          <textarea rows="2" id="descriptionid" style="resize: none;" class="form-control"></textarea>
        </div>
      </div>

      <div class="row p-2">
        <div class="col-md-12">
          <label class="lbl-text">How many beneficiary? : </label>
          <input type="text" id="howmanybeneficiary" class="form-control" />
        </div>
      </div>
     

      <div class="row p-2">
        <div class="col-md-12">
          <div class="table-responsive">
              <table id="beneficery_tbl" class="table table-striped table-bordered">
                <thead>
                  <tr>
                    <th scope="col" style="">BENEFICIARY NAME</th>
                    <th scope="col" style="">AMOUNT</th>
                    <th scope="col" style="">PURPOSE</th>
                    <th scope="col">DESCRIPTION</th>
                    
                  </tr>
                </thead>
                <tbody>
                  <td scope="col" data-label="BENEFICIARY NAME"><input type="text" id="benef_1" class="form-control" /></td>
                  <td scope="col" data-label="AMOUNT"><input type="text" id="amount_1" class="form-control" /></td>
                  <td scope="col" data-label="PURPOSE" class="p-1"><select class="form-control" id="purpose_1"></select></td>
                  <td scope="col" data-label="DESCRIPTION"><input type="text" id="description_1" class="form-control" /></td>
                
                      
                </tbody>
                
              </table>
              <span class="rounded-circle bg-info addrows" style="padding: 8px; cursor: pointer;float:right;"><i class="fa fa-plus"></i></span>
          </div>
        </div>
      </div>

    </div>
  </details>

  <div class="w3-row w3-center w3-padding">
    <input type="button" id="saveinfo_btn" class="btn btn-success btn-right btn-large" value="Save">
  </div>
</div>
<div id="index_deposit" style="display: none;">
  <div class="main-container">
    <div class="col-md-12 mt-1 mb-5 pt-3 pb-3 text-center" style="box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);">
      <span class="uppercase  bold" style="border-bottom: 1px solid white;font-size: 1.5rem;">INFORMATION FOR DEPOSIT SHEET</span>
    </div>
    
  </div>
  <div class="row p-2">
      <div class="col-md-6"></div>
      <div class="col-md-3"></div>
        <div class="col-md-3">
          <input type="button" id="saveinfo_btn2_deposit" class="btn btn-success btn-right btn-large" value="Save">
        </div>
      </div>
  <details class="alwaysopen" id="upload_details" open>
  <summary>Upload a document of transaction</summary>
  <div class="main-container bg-white">
  <div class="row p-2">
    <div class="col-md-12">
      <label class="lbl-text">Do you have any supporting document for this transaction?</label><br>       

      <input type="file" name="files[]" id="importfile2" class="w3-input" multiple accept="image/*"/>
    </div>
        
        
  </div>
  </div>
  </details>
  <details class="alwaysopen" id="withdrawalbank_details" open>
    <summary>Deposit in your bank</summary>
    <div class="main-container bg-white">
      <!-- <div class="row p-2">
      <div class="col-md-6"></div>
      <div class="col-md-3"></div>
        <div class="col-md-3">
          <input type="button" id="saveinfo_btn2_deposit" class="btn btn-success btn-right btn-large" value="Save">
        </div>
      </div> -->
      
      <div class="row p-2">
        <div class="col-md-12">
          <label class="lbl-text">Account Name : </label>
          <!-- <input type="text" id="banknameid" class="form-control" /> -->
          <select class="form-control w3-padding" id="account_slt2">
          </select>

          <input type="hidden" id="accountid" name="accountid2" value="">
          <input type="hidden" id="vdate2" name="vdate" value="">
          <input type="hidden" id="banksno2" name="vdate" value="">
        </div>
      </div>
      <div class="row p-2">
        <div class="col-md-12">         
          <label class="lbl-text">On Date : </label>
          <input type="date" id="dateid2" class="form-control" />
        </div>
      </div>
      <div class="row p-2">
        <div class="col-md-12">
          <label class="lbl-text">Amount is : </label>
          <input type="number" id="withdrowamount2" class="form-control" />
        </div>
      </div>
      <div class="row p-2">
        <div class="col-md-12">
          <label class="lbl-text">Received from(Depositer Name)</label>
          <input type="text" id="paytoname2" class="form-control" />
        </div>
      </div>
      <div class="row p-2">
        <div class="col-md-12">
          <label class="lbl-text">This transaction type?</label><br>
          <input type="radio" name="ischeque2" value="cheque" /> <label>CHEQUE</label>
          <input type="radio" name="ischeque2" value="online" /> <label>ONLINE</label>
          <input type="radio" name="ischeque2" value="cash" id="rbt_cash2" /> <label>CASH</label>
        </div>
      </div>
      <div class="row p-2" id="id_chk2">
        <div class="col-md-12">
          <label class="lbl-text">Cheque/Electronic transfer Reference number is : </label>
          <input type="text" id="chequenoid2" class="form-control" />
        </div>
      </div>
      <div class="row p-2">
        <div class="col-md-12">
          <label class="lbl-text">Select Purpose Category : </label>
          <select class="form-control" id="categorypurpose_12"></select>
        </div>
      </div>
      <div class="row p-2">
        <div class="col-md-12">
          <label class="lbl-text">Select Purpose Sub Category : </label>
          <select class="form-control" id="subcategorypurpose_12"></select>
        </div>
      </div>
      <div class="row p-2">
        <div class="col-md-12">
          <label class="lbl-text">Select Purpose : </label>
          <select class="form-control" id="purpose_12"></select>
        </div>
      </div>
      <div class="row p-2">
        <div class="col-md-12">
          <label class="lbl-text">The description on the Transaction : </label>
          <textarea rows="2" id="descriptionid2" style="resize: none;" class="form-control"></textarea>
        </div>
      </div>
      

      
    </div>
  </details>
  <div class="w3-row w3-center w3-padding">
    <input type="button" id="saveinfo_btn_deposit" class="btn btn-success btn-right btn-large" value="Save">
  </div>
</div>

<div id="index_cash" style="display: none;">
    <div class="main-container">
      <div class="col-md-12 mt-1 mb-5 pt-3 pb-3 text-center" style="box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);">
        <span class="uppercase  bold" style="border-bottom: 1px solid white;font-size: 1.5rem;">INFORMATION FOR EXPENSES SHEET FOR CASH</span>
      </div>
    </div>
    <div class="row p-2">
      <div class="col-md-6"></div>
      <div class="col-md-3"></div>
        <div class="col-md-3">
          <input type="button" id="saveinfo_btn13" class="btn btn-success btn-right btn-large" value="Save">
        </div>
      </div>
  <details class="alwaysopen" open open2>
   <summary>Upload a document of transaction</summary>
    <div class="main-container bg-white">
      <div class="row p-2">
        <div class="col-md-12">
          <label class="lbl-text">Do you have any supporting document for this transaction?</label><br>       
          <!-- <input type="file" accept="image/*" id="docid" name="files[]" multiple> -->
          <input type="file" name="files[]" id="importfile3" class="w3-input" multiple accept="image/*"/>
        </div>
      </div>
    </div>
   </details>
  <details class="alwaysopen"  open open2>
    <summary>Withdrawal from your Cash Account</summary>
    <div class="main-container bg-white">
      <!-- <div class="row p-2">
      <div class="col-md-6"></div>
      <div class="col-md-3"></div>
        <div class="col-md-3">
          <input type="button" id="saveinfo_btn13" class="btn btn-success btn-right btn-large" value="Save">
        </div>
      </div> -->
      
      <div class="row p-2" style="display: none;">
        <div class="col-md-12">
          <label class="lbl-text">Account Name : </label>
          <!-- <input type="text" id="banknameid" class="form-control" /> -->
          <select class="form-control w3-padding" id="account_slt3">
          </select>
        </div>
      </div>
      <div class="row p-2">
        <div class="col-md-12">         
          <label class="lbl-text">On Date : </label>
          <input type="date" id="dateid3" class="form-control" />
        </div>
      </div>
     
      <div class="row p-2">
        <div class="col-md-12">
          <label class="lbl-text">You have paid to</label>
          <input type="text" id="paytoname3" class="form-control" />
        </div>
      </div>
     <div class="row p-2">
        <div class="col-md-12">
          <label class="lbl-text">Amount</label>
          <input type="text" id="amount_13" class="form-control" />
        </div>
      </div>
      <!-- <div class="row p-2">
        <div class="col-md-12">
          <label class="lbl-text">Purpose Category</label>
          <select class="form-control" id="catpurpose_13">
           
          </select>
        </div>
      </div> -->
      <div class="row p-2">
        <div class="col-md-12">
          <label class="lbl-text">Purpose Sub Category</label>
          <select class="form-control" id="subcatpurpose_13">
             <!-- <option selected>Select Sub Category</option> -->
          </select>
        </div>
      </div>

      <div class="row p-2">
        <div class="col-md-12">
          <label class="lbl-text">Purpose</label>
          <select class="form-control" id="purpose_13"></select>
        </div>
      </div>
      
      <div class="row p-2">
        <div class="col-md-12">
          <label class="lbl-text">The description on the Transaction : </label>
          <textarea rows="2" id="descriptionid3" style="resize: none;" class="form-control"></textarea>
        </div>
      </div>
      

      

    </div>
  </details>

  <div class="w3-row w3-center w3-padding">
    <input type="button" id="saveinfo_btn3" class="btn btn-success btn-right btn-large" value="Save">
  </div>
</div>

<div class="modal waitloader">
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


<?php require('../footer.php'); ?>
<script type="text/javascript">
  var projectid="<?php echo $projectid; ?>";
  var compid="<?php echo  $companyid; ?>";
  var subcmppanyid="<?php echo $subcompanyid; ?>";
  var loginid="<?php echo $loginid; ?>";
  var isptype="";
  var ispaytobnf="";
  var transactionmode="";
  var accountid="";
  var banktype="";
  var subcatid="";
$(document).ready(function(){
   $('#maincont').show();
    $('#choosedetails').show();
  // $(document).on("click", "input[name='isexp']", function(){
  //  var chooseoption = $("input[name='isexp']:checked").val();
  //  if (chooseoption=='expenses') 
  //  {
  //   $('#index_exp').show();
  //   $('#index_deposit').hide();
  //   $('#maincont').hide();
  //   $('#choosedetails').hide();
  //   getaccountdetailsofproject('account_slt',compid,subcmppanyid,projectid,loginid);
  //   getpurposemaster('purpose_1',compid,subcmppanyid,projectid,'');
  // // var qstring = loginid+"|"+loginid+"|"+compid+"|"+subcmppanyid+"|"+projectid;
	// // var enc_qstring = url_btoa(qstring);
	// // var key = qstring + "|" + enc_qstring;
	// // var enc_key = url_btoa(key);
	// // window.location.href="index_exp.php?k="+enc_key;

  //  }else if (chooseoption=='deposit') 
  //  {
  //   $('#index_exp').hide();
  //   $('#index_deposit').show();
  //    $('#maincont').hide();
  //   $('#choosedetails').hide();
  //   getaccountdetailsofproject('account_slt2',compid,subcmppanyid,projectid,loginid);
  //    getpurposemaster2('purpose_12',compid,subcmppanyid,projectid,'');

  // //  	var qstring = loginid+"|"+loginid+"|"+compid+"|"+subcmppanyid+"|"+projectid;
	// // var enc_qstring = url_btoa(qstring);
	// // var key = qstring + "|" + enc_qstring;
	// // var enc_key = url_btoa(key);
  // //  	window.location.href="index_deposit.php?k="+enc_key;
  //  }

  // });
});


jQuery(document).delegate("#isexp", "click", function(){
  $('#id_choosecashorcheque').show();
  $('#isdeposit').hide();
});
jQuery(document).delegate("#idcheque", "click", function(){
  $('#index_exp').show();
    $('#index_deposit').hide();
    $('#maincont').hide();
    $('#choosedetails').hide();
    getpurposemastercategory('catpurpose_1',compid,subcmppanyid,projectid);
    getaccountdetailsofproject('account_slt',compid,subcmppanyid,projectid,loginid);
    getpurposemaster('purpose_1',compid,subcmppanyid,projectid,'','all');

 
});

jQuery(document).delegate("#idcash", "click", function(){
  $('#index_cash').show();
    $('#index_deposit').hide();
    $('#maincont').hide();
    $('#choosedetails').hide();
    getaccountdetailsofprojectforcash('account_slt3',compid,subcmppanyid,projectid,loginid);
    //getpurposemastercategory('catpurpose_13',compid,subcmppanyid,projectid);
    //getpurposemaster('purpose_13',compid,subcmppanyid,projectid,'');
      var catid=1;
  getpurposemastersubcategory('subcatpurpose_13',compid,subcmppanyid,projectid,catid);

 
});

jQuery(document).delegate("#account_slt", "change", function(){
  accountid=$('#account_slt').val();
 
});
jQuery(document).delegate("#account_slt2", "change", function(){
  accountid=$('#account_slt2').val();
  
});
jQuery(document).delegate("#account_slt3", "change", function(){
  accountid=$('#account_slt3').val();
  
});

// jQuery(document).delegate("#catpurpose_13", "change", function(){
//   var catid=$('#catpurpose_13').val();
//   getpurposemastersubcategory('subcatpurpose_13',compid,subcmppanyid,projectid,catid);
 
// });
jQuery(document).delegate("#catpurpose_1", "change", function(){
  var catid=$('#catpurpose_1').val();
  getpurposemastersubcategory('subcatpurpose_1',compid,subcmppanyid,projectid,catid);
  $('#purpose_1').empty();
 
});
jQuery(document).delegate("#subcatpurpose_13", "change", function(){
   subcatid=$('#subcatpurpose_13').val();
  getpurposemaster('purpose_13',compid,subcmppanyid,projectid,subcatid,'subcategoryid');
 
});

jQuery(document).delegate("#subcatpurpose_1", "change", function(){
   subcatid=$('#subcatpurpose_1').val();
  getpurposemaster('purpose_1',compid,subcmppanyid,projectid,subcatid,'subcategoryid');
 
});

jQuery(document).delegate("#categorypurpose_12", "change", function(){
  var catid=$('#categorypurpose_12').val();
  getpurposemastersubcategorydeposit('subcategorypurpose_12',compid,subcmppanyid,projectid,catid,'category');
  $('#purpose_12').empty();
 
});

jQuery(document).delegate("#subcategorypurpose_12", "change", function(){
   subcatid=$('#subcategorypurpose_12').val();
   getpurposemaster2('purpose_12',compid,subcmppanyid,projectid,subcatid,'purposesubcategory');
  
  
 
});

jQuery(document).delegate("#isdeposit", "click", function(){
  $('#index_exp').hide();
    $('#index_deposit').show();
     $('#maincont').hide();
    $('#choosedetails').hide();
    getaccountdetailsofproject('account_slt2',compid,subcmppanyid,projectid,loginid);
    getpurposecaterymaster2('categorypurpose_12',compid,subcmppanyid,projectid);
    getpurposemaster2('purpose_12',compid,subcmppanyid,projectid,'','all');

});

jQuery(document).delegate("#saveinfo_btn2", "click", function(){
  $( "#saveinfo_btn" ).trigger( "click" );
});
jQuery(document).delegate("#saveinfo_btn2_deposit", "click", function(){
  $( "#saveinfo_btn_deposit" ).trigger( "click" );
});
jQuery(document).delegate("#saveinfo_btn13", "click", function(){
  $( "#saveinfo_btn3" ).trigger( "click" );
});
jQuery(document).delegate("#saveinfo_btn", "click", function(){
  var form_data = new FormData();
  var totalbeneficaryamount=0;
  // var accountid=$('#account_slt').val();
  var valuedate=$('#dateid').val();
  var bankdescription=$('#descriptionid').val();
  
  var bankcheque=$('#chequenoid').val();
  var paytoname=$('#paytoname').val();


  
  if (accountid==""||accountid=="undefined"||accountid==null) 
  {
    alert("Please Select Bank Account");
    $("#account_slt").focus();
    return false;

  }
  
  
  
  var combindstring1=new Array();
  var combindstring2=new Array();
  var combindstring3=new Array();
  var combindstring4=new Array();
  form_data.append("companyid", <?php echo  $companyid; ?>);
  form_data.append("subcmppanyid", <?php echo $subcompanyid; ?>);
  form_data.append("projectid", <?php echo $projectid; ?>);
  form_data.append("loginid", <?php echo $loginid; ?>);
  form_data.append("accountid", accountid);
  form_data.append("valuedate", valuedate);
  form_data.append("adddescription", bankdescription);
  form_data.append("bankcheque", bankcheque);
  form_data.append("paytoname", paytoname);
  form_data.append("transactionmode", transactionmode);
  
  

    
    var howmanybf=$('#beneficery_tbl tr').length;
    for (var i = 1; i <howmanybf; i++) {
      
      var b=$('#benef_'+ i).val();
      var m=$('#amount_'+ i).val();
      
      var p=$('#purpose_'+ i).val();
      var d=$('#description_'+ i).val();
      totalbeneficaryamount=parseInt(totalbeneficaryamount)+parseInt(m);
      combindstring1.push(b);
      combindstring2.push(m);
      combindstring3.push(p);
      combindstring4.push(d);

    }


    
      var ins = document.getElementById('importfile').files.length;
      if (ins>0) 
      {
        for (var x = 0; x < ins; x++) {
        
      form_data.append("files[]", document.getElementById('importfile').files[x]);

      }
      }else{
        form_data.append("files[]", "");
      }
    

    // alert(ins);
    form_data.append("filelength", ins);
    form_data.append("totalbeneficaryamount", totalbeneficaryamount);
    form_data.append("combindstring1", combindstring1);
    form_data.append("combindstring2", combindstring2);
    form_data.append("combindstring3", combindstring3);
    form_data.append("combindstring4", combindstring4);
    form_data.append("howmanybf", howmanybf);
  $.ajax
    ({
      type: "POST",
      url: "modal/reconstructionbook_modal.php?action=saveinfoforbookreconstrution",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      //dataType:'JSON',
      success: function(res)
      {
        console.log(res);
        $(".waitloader").hide();
        alert('Done');
        location.reload();

       

      },
      beforeSend(res)
      {
        $(".waitloader").show();
      }
    });
});

jQuery(document).delegate("#saveinfo_btn_deposit", "click", function(){
  var form_data = new FormData();
  var totalbeneficaryamount=0;
  var accountid=$('#account_slt2').val();
  var valuedate=$('#dateid2').val();
  var bankdescription=$('#descriptionid2').val();
  var totalbeneficaryamount=$('#withdrowamount2').val();
  var bankcheque=$('#chequenoid2').val();
  var paytoname=$('#paytoname2').val();
  var purposecode=$('#purpose_12').val();

  
  if (accountid==""||accountid=="undefined"||accountid==null) 
  {
    alert("Please Select Bank Account");
    $("#account_slt2").focus();
    return false;

  }
  
  
  
  
  form_data.append("companyid", <?php echo  $companyid; ?>);
  form_data.append("subcmppanyid", <?php echo $subcompanyid; ?>);
  form_data.append("projectid", <?php echo $projectid; ?>);
  form_data.append("loginid", <?php echo $loginid; ?>);
  form_data.append("accountid", accountid);
  form_data.append("valuedate", valuedate);
  form_data.append("adddescription", bankdescription);
  form_data.append("bankcheque", bankcheque);
  form_data.append("paytoname", paytoname);
  form_data.append("transactionmode", transactionmode);
  form_data.append("purposecode", purposecode);
  form_data.append("withdrowamount", totalbeneficaryamount);

    
    
    
      var ins = document.getElementById('importfile2').files.length;
      if (ins>0) 
      {
        for (var x = 0; x < ins; x++) {
        
      form_data.append("files[]", document.getElementById('importfile2').files[x]);

      }
      }else{
        form_data.append("files[]", "");
      }
    

    // alert(ins);
    form_data.append("filelength", ins);
    form_data.append("totalbeneficaryamount", totalbeneficaryamount);
    
    
  $.ajax
    ({
      type: "POST",
      url: "modal/reconstructionbook_modal.php?action=saveinfoforbookreconstrutiondeposit",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      //dataType:'JSON',
      success: function(res)
      {
        console.log(res);
        $(".waitloader").hide();
        alert('Done');
        location.reload();

       

      },
      beforeSend(res)
      {
        $(".waitloader").show();
      }
    });
});

jQuery(document).delegate("#saveinfo_btn3", "click", function(){
  var form_data = new FormData();
  var totalbeneficaryamount=0;
  // var accountid=$('#account_slt').val();
  var valuedate=$('#dateid3').val();
  var bankdescription=$('#descriptionid3').val();
  
  var bankcheque="";
  var paytoname=$('#paytoname3').val();


  
  if (accountid==""||accountid=="undefined"||accountid==null) 
  {
    alert("Please Select Bank Account");
    $("#account_slt").focus();
    return false;

  }
  
  
  
  var combindstring1=new Array();
  var combindstring2=new Array();
  var combindstring3=new Array();
  var combindstring4=new Array();
  form_data.append("companyid", <?php echo  $companyid; ?>);
  form_data.append("subcmppanyid", <?php echo $subcompanyid; ?>);
  form_data.append("projectid", <?php echo $projectid; ?>);
  form_data.append("loginid", <?php echo $loginid; ?>);
  form_data.append("accountid", accountid);
  form_data.append("valuedate", valuedate);
  form_data.append("adddescription", bankdescription);
  form_data.append("bankcheque", bankcheque);
  form_data.append("paytoname", paytoname);
  form_data.append("transactionmode", 'cash');
  
  

    
    var howmanybf=2;
    for (var i = 1; i <howmanybf; i++) {
      
      var b="";
      var m=$('#amount_13').val();
      
      var p=$('#purpose_13').val();
      var d="";
      totalbeneficaryamount=parseInt(totalbeneficaryamount)+parseInt(m);
      combindstring1.push(b);
      combindstring2.push(m);
      combindstring3.push(p);
      combindstring4.push(d);

    }


    
      var ins = document.getElementById('importfile3').files.length;
      if (ins>0) 
      {
        for (var x = 0; x < ins; x++) {
        
      form_data.append("files[]", document.getElementById('importfile3').files[x]);

      }
      }else{
        form_data.append("files[]", "");
      }
    

    // alert(ins);
    form_data.append("filelength", ins);
    form_data.append("totalbeneficaryamount", totalbeneficaryamount);
    form_data.append("combindstring1", combindstring1);
    form_data.append("combindstring2", combindstring2);
    form_data.append("combindstring3", combindstring3);
    form_data.append("combindstring4", combindstring4);
    form_data.append("howmanybf", howmanybf);
  $.ajax
    ({
      type: "POST",
      url: "modal/reconstructionbook_modal.php?action=saveinfoforbookreconstrution",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      //dataType:'JSON',
      success: function(res)
      {
        console.log(res);
        $(".waitloader").hide();
        alert('Done');
        location.reload();

       

      },
      beforeSend(res)
      {
        $(".waitloader").show();
      }
    });
});

// $("#howmanybeneficiary").on("input", function(){
//   if($(this).val()>0){
//     var catname=$("#catpurpose_1 option:selected").text();

//    if (catname.trim()=='Imprest' || catname.trim()=='Loan' || catname.trim()=='Investment')

//    {
//     //$("#howmanybeneficiary").val(1);
//     alert('You have selected purpose category is '+catname+'. So this category can not have more than one beneficiary');
//     return false;

//    }
//     $("#beneficery_tbl tbody").empty();
//     var howmanybnf=$("#howmanybeneficiary").val();
//     howmanybnf=parseInt(howmanybnf)+1;
//    for (var i = 2; i <= howmanybnf; i++) {
//     var b="benef_"+i;
//     var a="amount_"+i;
//     var p="purpose_"+i;
//     var d="description_"+i;
//     $("#beneficery_tbl").append('<tr><td scope="col" data-label="BENEFICIARY NAME"><input type="text" id='+b+' class="form-control" /></td><td scope="col" data-label="AMOUNT"><input type="text" id='+a+' class="form-control" /></td><td scope="col" data-label="PURPOSE" class="p-1"><select class="form-control" id='+p+'></select></td><td scope="col" data-label="DESCRIPTION"><input type="text" id='+d+' class="form-control" /></td></tr>');
//   if (subcatid=="") 
//   {
//      getpurposemaster(p,compid,subcmppanyid,projectid,'','all');
//   }else {
//      getpurposemaster(p,compid,subcmppanyid,projectid,subcatid,'subcategoryid');
//   }
    
//    }
//   }
// });

$(".addrowsXXX").click(function () {
  var bb2="";
  var aa2="";
  var pp2="";
  var dd2="";
 
  var rowCount = $('#beneficery_tbl tr').length;
  var rowCount2 = $('#beneficery_tbl tr').length-1;
  
  var b2="benef_"+rowCount2;
  var a2="amount_"+rowCount2;
  var p2="purpose_"+rowCount2;
  var d2="description_"+rowCount2;
   bb2=$("#"+b2).val();
   aa2=$("#"+a2).val();
   pp2=$("#"+p2).val();
   dd2=$("#"+d2).val();
   
   if (bb2==""  && aa2==""  && pp2=="") 
   {
    alert('Please enter at least one entry in row');
    return false;
   }

   var catname=$("#catpurpose_1 option:selected").text();
   
   // alert(catname);
   if (catname.trim()=='Imprest' || catname.trim()=='Loan' || catname.trim()=='Investment')

   {
    alert('You have selected purpose category is '+catname+'. So this category can not have more than one beneficiary');
    return false;
   }
 
  var b="benef_"+rowCount;
  var a="amount_"+rowCount;
  var p="purpose_"+rowCount;
  var d="description_"+rowCount;
  $("#beneficery_tbl").append('<tr><td scope="col" data-label="BENEFICIARY NAME"><input type="text" id='+b+' class="form-control" /></td><td scope="col" data-label="AMOUNT"><input type="text" id='+a+' class="form-control" /></td><td scope="col" data-label="PURPOSE" class="p-1"><select class="form-control" id='+p+'></select></td><td scope="col" data-label="DESCRIPTION"><input type="text" id='+d+' class="form-control" /></td></tr>');
  if (subcatid=="") 
  {
     getpurposemaster(p,compid,subcmppanyid,projectid,'','all');
  }else {
     getpurposemaster(p,compid,subcmppanyid,projectid,subcatid,'subcategoryid');
  }
});

$(".addrows").click(function () {
  var bb2="";
  var aa2="";
  var pp2="";
  var dd2="";
 
  var rowCount = $('#beneficery_tbl tr').length;
  var rowCount2 = $('#beneficery_tbl tr').length-1;
  var howmanybnf=$("#howmanybeneficiary").val();
  if (rowCount2==howmanybnf) 
  {
    //alert('Entered beneficiary value and ');
    return false;
  }
 
  var b2="benef_"+rowCount2;
  var a2="amount_"+rowCount2;
  var p2="purpose_"+rowCount2;
  var d2="description_"+rowCount2;
   bb2=$("#"+b2).val();
   aa2=$("#"+a2).val();
   pp2=$("#"+p2).val();
   dd2=$("#"+d2).val();
   
   if (bb2==""  && aa2==""  && pp2=="") 
   {
    alert('Please enter at least one entry in row');
    return false;
   }

   var catname=$("#catpurpose_1 option:selected").text();
   
   // alert(catname);
   if (catname.trim()=='Imprest' || catname.trim()=='Loan' || catname.trim()=='Investment')

   {
    alert('You have selected purpose category is '+catname+'. So this category can not have more than one beneficiary');
    return false;
   }
 
  var b="benef_"+rowCount;
  var a="amount_"+rowCount;
  var p="purpose_"+rowCount;
  var d="description_"+rowCount;
  $("#beneficery_tbl").append('<tr><td scope="col" data-label="BENEFICIARY NAME"><input type="text" id='+b+' class="form-control" /></td><td scope="col" data-label="AMOUNT"><input type="text" id='+a+' class="form-control" /></td><td scope="col" data-label="PURPOSE" class="p-1"><select class="form-control" id='+p+'></select></td><td scope="col" data-label="DESCRIPTION"><input type="text" id='+d+' class="form-control" /></td></tr>');
  if (subcatid=="") 
  {
     getpurposemaster(p,compid,subcmppanyid,projectid,'','all');
  }else {
     getpurposemaster(p,compid,subcmppanyid,projectid,subcatid,'subcategoryid');
  }

  
  
 
 
});
$(document).on("click", "input[name='ischeque']", function(){
   transactionmode = $("input[name='ischeque']:checked").val();
  
});
$(document).on("click", "input[name='ischeque2']", function(){
   transactionmode = $("input[name='ischeque2']:checked").val();
  
});
function getpurposemaster(p,compid,subcmppanyid,projectid,subcateid,shortby){

  var form_data = new FormData();
  form_data.append("companyid", compid);
  form_data.append("subcmppanyid", subcmppanyid);
  form_data.append("projectid", projectid);
  form_data.append("subcateid", subcateid);
  form_data.append("shortby", shortby);
  // alert(subcateid);
  $.ajax
    ({
      type: "POST",
      url: "modal/reconstructionbook_modal.php?action=getpurposemaster",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     dataType:'JSON',
      success: function(res)
      {
        console.log(res);
        $("#"+p).empty();
       if(res.length>0){
              
             
              for (var i = 0; i < res.length; i++) {
                  if (i==0) 
                  {
                     $("#"+p).append("<option value='' selected>Select Purpose</option>");
                  }
                    $("#"+p).append("<option value='"+res[i].purposecode+"'>"+res[i].purpose+"</option>");
                  

                  
                }
              }else{
                
              }
      }
    });
}

function getpurposemastercategory(p,compid,subcmppanyid,projectid){

  var form_data = new FormData();
  form_data.append("companyid", compid);
  form_data.append("subcmppanyid", subcmppanyid);
  form_data.append("projectid", projectid);
  
  $.ajax
    ({
      type: "POST",
      url: "modal/reconstructionbook_modal.php?action=getpurposemastercategory",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     dataType:'JSON',
      success: function(res)
      {
        console.log(res);
       if(res.length>0){
              
            for (var i = 0; i < res.length; i++) {
                  if (i==0) 
                  {
                     $("#"+p).append("<option value='' selected>Select Purpose Category</option>");
                  }
                    $("#"+p).append("<option value='"+res[i].pcategorycode+"'>"+res[i].pcategory+"</option>");
              }
          }
      }
    });
}
function getpurposemastersubcategory(p,compid,subcmppanyid,projectid,catid){

  var form_data = new FormData();
  form_data.append("companyid", compid);
  form_data.append("subcmppanyid", subcmppanyid);
  form_data.append("projectid", projectid);
  form_data.append("catid", catid);
  
  $.ajax
    ({
      type: "POST",
      url: "modal/reconstructionbook_modal.php?action=getpurposemastersubcategory",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     dataType:'JSON',
      success: function(res)
      {
        console.log(res);
        $("#"+p).empty();
       if(res.length>0){
              
             
              for (var i = 0; i < res.length; i++) {
                  if (i==0) 
                  {
                     $("#"+p).append("<option value='' selected>Select Purpose Category</option>");
                  }
                    $("#"+p).append("<option value='"+res[i].subpcategorycode+"'>"+res[i].subpcategory+"</option>");
                  

                  
                }
              }else{
                
              }
      }
    });
}

function getaccountdetailsofproject(dropdownid,companyid,subcompanyid,projectid,loginid){
    // alert(projectid);
    $.ajax
    ({
      type: "POST",
      url: "view/view.php?action=getauthorized_accountofproject",
      data: {companyid: companyid,subcompanyid: subcompanyid,projectid: projectid,loginid: loginid},
      cache: false,
      dataType: "json",
      success: function(res)
      {
         console.log(res);
        
        if(res.length>0){
        
            $("#"+dropdownid+"").append("<option value='' selected disabled>Select Account Name</option>");
          
          for(var i=0; i<res.length; i++){
            
            $("#"+dropdownid+"").append("<option value='"+res[i].accountid+"'>"+res[i].accountname+' ['+res[i].bankname+'] '+' ['+res[i].accountno+']'+"</option>");  
            
          }
        }
      }
    });
}
function getaccountdetailsofprojectforcash(dropdownid,companyid,subcompanyid,projectid,loginid){
    // alert(projectid);
    $.ajax
    ({
      type: "POST",
      url: "view/view.php?action=getauthorized_accountofprojectforcash",
      data: {companyid: companyid,subcompanyid: subcompanyid,projectid: projectid,loginid: loginid},
      cache: false,
      dataType: "json",
      success: function(res)
      {
         console.log(res);
        
        if(res.length>0){
        
            $("#"+dropdownid+"").append("<option value='' selected disabled>Select Account Name</option>");
          
          for(var i=0; i<res.length; i++){
            
            $("#"+dropdownid+"").append("<option value='"+res[i].accountid+"'>"+res[i].accountname+' ['+res[i].bankname+'] '+' ['+res[i].accountno+']'+"</option>");  
            
          }

          if (res.length==1) 
          {
            document.getElementById(dropdownid).selectedIndex = "1";
            $("#account_slt3").trigger("change");
          }
        }
      }
    });
}
function getpurposemaster2(p,compid,subcmppanyid,projectid,subcateid,shortby){

  var form_data = new FormData();
  form_data.append("companyid", compid);
  form_data.append("subcmppanyid", subcmppanyid);
  form_data.append("projectid", projectid);
  form_data.append("subcateid", subcateid);
  form_data.append("shortby", shortby);
  //alert(subcateid);
  $.ajax
    ({
      type: "POST",
      url: "modal/reconstructionbook_modal.php?action=getpurposemasterdeposit",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     dataType:'JSON',
      success: function(res)
      {
        console.log(res);
       if(res.length>0){
             
             
              for (var i = 0; i < res.length; i++) {
                  if (i==0) 
                  {
                     $("#"+p).append("<option value='' selected>Select Purpose</option>");
                  }
                    $("#"+p).append("<option value='"+res[i].purposecode+"'>"+res[i].purpose+"</option>");
                  

                  
                }
              }

              if (res.length==1) 
            {
             document.getElementById(p).selectedIndex = "1";
             $("#purpose_12").trigger("change");
          }
      }
    });
}

function getpurposecaterymaster2(p,compid,subcmppanyid,projectid){

  var form_data = new FormData();
  form_data.append("companyid", compid);
  form_data.append("subcmppanyid", subcmppanyid);
  form_data.append("projectid", projectid);
  //form_data.append("subcateid", subcateid);
  //alert(subcateid);
  $.ajax
    ({
      type: "POST",
      url: "modal/reconstructionbook_modal.php?action=getcatepurposemasterdeposit",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        console.log(res);
       if(res.length>0){
             
             
              for (var i = 0; i < res.length; i++) {
                  if (i==0) 
                  {
                     $("#"+p).append("<option value='' selected>Select Purpose Category</option>");
                  }
                    $("#"+p).append("<option value='"+res[i].pcategorycode+"'>"+res[i].pcategory+"</option>");
                  

                  
                }
              }

            
      }
    });
}

function getpurposemastersubcategorydeposit(p,compid,subcmppanyid,projectid,catid,shortby){

  var form_data = new FormData();
  form_data.append("companyid", compid);
  form_data.append("subcmppanyid", subcmppanyid);
  form_data.append("projectid", projectid);
  form_data.append("catid", catid);
  form_data.append("shortby", shortby);
  //alert(subcateid);
  $.ajax
    ({
      type: "POST",
      url: "modal/reconstructionbook_modal.php?action=getpurposemastersubcategorydeposit",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        console.log(res);
        $("#"+p).empty();
       if(res.length>0){
             
             
              for (var i = 0; i < res.length; i++) {
                  if (i==0) 
                  {
                     $("#"+p).append("<option value='' selected>Select Purpose Sub Category</option>");
                  }
                    $("#"+p).append("<option value='"+res[i].psubcategorycode+"'>"+res[i].psubcategory+"</option>");
                  

                  
                }
         }

         if (res.length==1) 
            {
             document.getElementById(p).selectedIndex = "1";
             $("#subcategorypurpose_12").trigger("change");
          }
      }
    });
}

function url_btoa(data)
{
  var b64 = window.btoa(data);
  if (!b64) {
    return false;
  }
  var url = b64.replace('+', '-').replace('/','_');
  return url.replace(/=/g,'');
}

function url_atob(data)
{
  var b64 = data.replace('-', '+').replace('_','/');
  return window.atob(b64);
}


</script>