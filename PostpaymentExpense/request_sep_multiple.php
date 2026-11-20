<?php 
$ppcatid = isset($_GET['ppcatid'])?$_GET['ppcatid']:'';
if(!$ppcatid){
  die('Purpose category not defined');
}
$dz="";
if(isset($_GET['dz'])){
  $dz=$_GET['dz'];
}

if(isset($_GET['subaction'])){
  $subaction=$_GET['subaction'];
}
$prepaymentnone="";
$prepaymentblock="";
if($subaction=="prepayment"){
  $prepaymentnone="style='display:none;'";
  $prepaymentblock="style='display:block;'";
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

$subprojectid="";
if(isset($_GET['subprojectid'])){
  $subprojectid=$_GET['subprojectid'];
}

?>
  <div id="expense_bankdiv" class="main-container bg-white detailsdivcontainer" style="display: none;">
    <div class="bg-white fromaccountdiv">
      <div class="row p-2">
        <div class="col-md-12">
          <label class="lbl-text lbl-full">Which bank's expense : </label>
          <select class="form-control select2" style="width: 100%;" id="expenseaccount"></select>
          <p id="ptest"></p>
        </div>
      </div>
    </div>
  </div>
  <div class="main-container bg-white">
    <div class="row p-2 subprojectdiv" id="subprojectdiv">
      <div class="col-md-12">
        <label><?php echo $subprojectid!=""?"Sub-project name":"Choose the Sub-project"; ?></label>
        <select class="form-control" id="exp_subproject"></select>
      </div>
    </div>
    
    <div class="row mt-2" id="isforimprestadjustmentdiv_11" style="display:none;">
      <div class="col-md-3"></div>
      <div class="col-md-6 text-center">
        <label>Is this for imprest adjustment?</label><br/>
        <label><input type="radio" name="isforimprestadjustment" onclick='imprestpaytowithloginid_todropdown("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","","paytoname_exp");' value="yes" /> Yes</label>
        <label><input type="radio" name="isforimprestadjustment" onclick='request_paytofrommaster_withpaytoid("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","paytoname_exp","","");' value="no" checked /> No</label>      
      </div>
      <div class="col-md-3"></div>
    </div>
    <div class="row p-2 amounttopaydiv">
      <div class="col-md-12">
        <p class="lbl-text lbl-full">Amount</p>
        <input class="form-control transactionamount" id="transactionamount" onkeypress="return isNumberKey(event)" oninput="balanceamount_inputvalfill('transactionamount','expenseamount','balanceamount_forexpense');" />
      </div>
    </div>
    <div class="row p-2">
      <div class="col-md-12">
        <label class="lbl-text lbl-full">Who is payto?</label>
        <i class="fa fa-plus-circle" id="opennewpaytomodal_forexp" style="cursor: pointer; height: 28px;"></i>
        <span class="float-right paytoaddrefresh refeshpaytolist" onclick='request_paytofrommaster_withpaytoid("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","paytoname_exp","","");' style="cursor: pointer; color: green;"> <i class="fa fa-sync"> </i> Refresh</span>
        <select id="paytoname_exp" class="form-control select2" style="width: 100%;"></select>
      </div>
    </div>
    <div class="row mt-2 paymentduedatediv" style="display: <?php echo ($subaction=="postpayment"?"none":"block"); ?>;">
      <div class="col-md-12">
        <label class="lbl-text lbl-full">Payment Due Date</label>
        <input type="date" class="form-control" name="exp_paymentduedate" id="exp_paymentduedate" value="<?php echo date('Y-m-d'); ?>" />
      </div>
    </div>
    <div class="row p-2 isforproductiondiv d-none" id="isforproductiondiv">
      <div class="col-md-12">
        <label class="lbl-text lbl-full">Is this purchase goinging into stock?</label><br/>
        <label><input type="radio" name="isforproductionradio" value="yes" /> Yes</label>
        <label><input type="radio" name="isforproductionradio" value="no" checked /> No</label>     
      </div>
    </div>
    <div class="row p-2 modeoftransferdiv"<?php echo $prepaymentnone; ?>>
      <div class="col-md-12">
        <p class="lbl-text lbl-full">What is the mode of transfer?</p>
        <label><input type="radio" name="modeoftransfer" onchange="changelabelonchange('modeoftransfer','chqnolbl');" value="cheque" style="transform: scale(2);" /> Cheque</label>
        <label><input type="radio" name="modeoftransfer"onchange="changelabelonchange('modeoftransfer','chqnolbl');" value="online" style="transform: scale(2);" checked /> Online</label>
      </div>
    </div>
    <div class="row p-2 transactionnodiv"<?php echo $prepaymentnone; ?>>
      <div class="col-md-12">
        <p class="lbl-text lbl-full">What is the <b id="chqnolbl">online transaction</b> number?</p>
        <input type="text" class="form-control"  id="transactionno" value="" />
      </div>
    </div>
    <div class="row p-2 transactiondatediv" <?php echo $prepaymentnone; ?> >
      <div class="col-md-12">
        <p class="lbl-text lbl-full">Transaction/Cheque Date</p>
        <input type="date" class="form-control" id="transactiondate" value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" />
      </div>
    </div>   
    
    <div class="row p-2" style="display: <?php echo ($dz==1?"none":"block"); ?>;">
      <div class="col-md-12">
        <p class="lbl-text lbl-full">Do you have any receipt/voucher?</p>
        <label><input type="radio" name="isreceipt" value="yes" style="transform: scale(2);" /> YES</label>
        <label><input type="radio" name="isreceipt" value="no" style="transform: scale(2);" /> NO</label>
      </div>
    </div>
    <div class="row p-2" id="attdocumentdiv" style="display:none;">
      <div class="col-md-12">
        <p class="lbl-text lbl-full">Do you have any receipt/voucher?</p>
        <input type="file" name="files[]" id="importfile3" class="form-control" multiple accept="image/*; capture=camera"/>
        <span class="text-danger">You can choose multiple receipt/voucher</span>
      </div>
    </div>
  </div>
</div>
<div class="parentdiv">
  <div id="expense_payandbeneficiarydiv" class="main-container bg-white" style="display: ; margin:4px 8px;"></div>
</div>
<div id="expense_savebtndiv" class="main-container bg-white" style="display: none; margin:2px 8px; display: none;">
  <div class="row">
    <div class="col-md-4"></div>
    <div class="col-md-4 text-center">
      <i id="dispbalanceamount"></i><br/>
      <i id="balanceamount_forexpense" style="display:none;"></i><br/>
      <button type='button' data-isforproduction="" data-categoryofrequest="Expenditure" class='btn btn-success btn-right btn-large mt-4' id='saveexpensedetails' onclick="otherthanrequest('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','exp_subproject','expenseaccount','paytoname_exp','transactionamount','transactionno','transactiondate','purposecategory','modeoftransfer','balanceamount_forexpense','beneficiarycontainer','hidden_loginname','hidden_attpath_fromemail','hidden_attsno_fromemail','hidden_emailsno_fromemail','updatecategorized','paymenttypeselection','hidden_instructionid','exp_paymentduedate','saveexpensedetails');">Save Expense Details</button>
    </div>
    <div class="col-md-4"></div>
</div>
<script type="text/javascript">
var subprjid = "<?php echo $subprojectid; ?>";
var alrd_transactionno = $("#htmlcontainerdiv").data("unrtransactionno");
var alrd_transactiondate = $("#htmlcontainerdiv").data("unrtransactiondate");
var alrd_transactionamount = $("#htmlcontainerdiv").data("unrtransactionamount");
var alrd_likelypayto = $("#htmlcontainerdiv").data("unrlikelypayto");
var alrd_bankdbsno = $("#htmlcontainerdiv").data("unrbankdbsno");
var alrd_bankaccountid = $("#htmlcontainerdiv").data("unrbankaccountid");
var alrd_bankaccountno = $("#htmlcontainerdiv").data("unrbankaccountno");
var alrd_bankaccounttype = $("#htmlcontainerdiv").data("unrbankaccounttype");
var alrd_bankdescription = $("#htmlcontainerdiv").data("unrbankdescription");
var alrd_bankname = $("#htmlcontainerdiv").data("unrbankname");
var subaction = "<?php echo $subaction; ?>";
if(subaction=="prepayment"){
  checkimprestauthorized("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid","loginid",$("body").data("loginid"),function(res){
      if(res.length>0){
        $("#isforimprestadjustmentdiv").show();
      }else{
        $("#isforimprestadjustmentdiv").hide();
      }
  });
}

$(document).on("input",".expenseamount, .transactionamount", function(){
  let reqamount = $("#transactionamount").val();
  let ttlexpamount=0;
  let balamount=0;
  $(".expenseamount").each(function(){
    ttlexpamount += parseInt($(this).val());
  });
  // alert(ttlexpamount+'--'+reqamount);
  balamount = parseInt(reqamount) - parseInt(ttlexpamount);
   $("#dispbalanceamount").html('');
  if(balamount>0){
    $("#dispbalanceamount").html("<i class='text-danger'>Balance amunt for submit details : "+balamount+"</i>");
    $(".admorevouchercontainer").show();
    $("#saveexpensedetails").hide();
  }else if(balamount<0){
    $("#dispbalanceamount").html("<i class='text-danger'>TThe total beneficiary amount of "+reqamount+" does not exceed the requested amount of "+ttlexpamount+"</i>");
    $("#saveexpensedetails").hide();
    $(".admorevouchercontainer").hide();
  }else{
    $("#saveexpensedetails").show();
    $(".admorevouchercontainer").hide();
  }
});

$(document).on("change", "input[name='isforimprestadjustment']", function(){
  if($(this).val()=="yes"){
    $(".paytoaddrefresh").hide();
    $(".fromaccountdiv").hide();
    $(".modeoftransferdiv").hide();
    $(".paymentduedatediv").hide();
    $("#saveexpensedetails").data("categoryofrequest", "Imprest Adjustment");
  }else{
    $(".paytoaddrefresh").show();
    $(".fromaccountdiv").show();
    $(".modeoftransferdiv").show();
    $(".paymentduedatediv").show();
    $("#saveexpensedetails").data("categoryofrequest", "Expenditure");
  }
});

$(document).on("change", "input[name='isforproductionradio']", function(){
  if($(this).val()=="yes"){
    $("#saveexpensedetails").data("isforproduction", "yes");
  }else{
    $("#saveexpensedetails").data("isforproduction", "");
  }
});
if(alrd_likelypayto){
   $('#paytoname_exp').before('<br/><span style="color:red;">We believe you have payto : '+alrd_likelypayto+'</span>');
}

var now = new Date().getTime();
var microseconds = now;
var lastFourDigits = microseconds.toString().slice(-4);

// mdtdiv
if(alrd_bankdbsno){
  $(".modeoftransferdiv").hide();
  if(alrd_transactionno){
    $("#transactionno").val(alrd_transactionno);
    $(".transactionnodiv").hide();
  }else{
    $("#transactionno").val("AG-"+lastFourDigits+"-"+alrd_transactiondate.replace(/-/g,''));
    $(".transactionnodiv").hide();
  }
  if(alrd_transactiondate){
    $("#transactiondate").val(alrd_transactiondate);
    $(".transactiondatediv").hide();
  }

  // $("#spe_amount").val(alrd_transactionamount);
  if(alrd_transactionamount){
    $("#transactionamount").val(alrd_transactionamount);
    $(".amounttopaydiv").hide();
  }

  if(alrd_bankaccountid!=""){
    $(".fromaccountdiv").hide();
  }else{
    $(".fromaccountdiv").show();
  }
}



  subprojectdetails_callback("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid",function(res){
    if(res.length>0){
      if(res.length>1){
        $(".subprojectdiv").show();
        $("#exp_subproject").append("<option value='' disabled selected>Choose sub-project name</option>");
      }else{
        $(".subprojectdiv").hide();
      }
      for (var i = 0; i < res.length; i++) {
        $("#exp_subproject").append("<option value='"+res[i].subprojectid+"'>"+res[i].subprojectname+"</option>");
      }
      if(subprjid && res.length>1){
        $("#exp_subproject").val(subprjid).trigger("change");
      }
    }
  });
  $(document).on("click", "#opennewpaytomodal_forexp", function(){
    $("#paytohead").html("New Payto Details");
     $("#newpaytodivid").load("<?php echo $rooturl; ?>expenditure_expencessheet/newpayto_authorization.php", function(){
      $("#newpaytodetails_modal").modal("show");
      $("#newpaytodetails_modal").css("z-index","9999");
      });
  });
  
  /*$(document).on("click", "#opennewpurposemodal_forexp", function(){  
     $("#newpurposedivid").load("<?php echo $rooturl; ?>expenditure_expencessheet/purpose_addnew.php", function(){
      $("#newpurposedetails_modal").modal("show");
      $("#newpurposedetails_modal").css("z-index","9999");
      });
  });*/
  // $("#purposesubcategorydiv").show();
  var ppcatid=$("input[name='purposecategory']:checked").val();
  request_paytofrommaster_withpaytoid("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","paytoname_exp","",alrd_likelypayto);

  $(document).on("change", "#paytoname_exp", function(){
    $(".paytoasbeneficiarychkbox").prop("checked", false);
    $(".beneficiaryname_exp").val('').trigger("change");
  });

  
  getaccountdetailsofproject_frmaster("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","hidden_loginid","expenseaccount",alrd_bankaccountid);

  // $("#expenseaccount").on("change", function(){
  //   nextdivshow('expense_bankdiv','expense_documentdiv');
  // });

  $("input[name='isreceipt']").on("click", function(){
    var purposecategoryid = $("input[name='purposecategory']:checked").val();
    var purposesubcategoryid = $("#purposesubcategory").val();    
    if($(this).val()=="no"){
      $("#expense_payandbeneficiarydiv").empty();
      $("#attdocumentdiv").hide();
      $("#importfile3").empty();
      // nextdivshow('expense_bankdiv','expense_payandbeneficiarydiv');
      newbeneficiarydetails_html("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","purposecategory","purposesubcategory","hidden_loginid","transactionamount","beneficiarycontainer",'withoutdocument','expense_payandbeneficiarydiv','');
    }else if($(this).val()=="yes"){
      $("#expense_payandbeneficiarydiv").empty();
      $("#attdocumentdiv").show();
    }
    $("#expense_savebtndiv").show();
  });

  var rrturl="<?php echo $rooturl; ?>";
  $("#importfile3").on("change", function(){
    var numFiles = $("#importfile3")[0].files.length;
    var form_data = new FormData();
    var companyid = $("#hidden_companyid").val();
    var subcompanyid = $("#hidden_subcompanyid").val();
    var projectid = $("#hidden_projectid").val();
    var loginid = $("#hidden_loginid").val();
    var purposecategoryid = $("input[name='purposecategory']:checked").val();
    var purposesubcategoryid = $("#purposesubcategory").val();
     form_data.append("companyid", companyid);
     form_data.append("subcompanyid", subcompanyid);
     form_data.append("projectid", projectid);
     form_data.append("purposecategoryid", purposecategoryid);
     form_data.append("purposesubcategoryid", purposesubcategoryid);
     form_data.append("loginid", loginid);
    // Read selected files
    var totalfiles = document.getElementById('importfile3').files.length;
    for (var index = 0; index < totalfiles; index++) {
        form_data.append("files[]", document.getElementById('importfile3').files[index]);
     }
     $("#expense_payandbeneficiarydiv").show();
     // AJAX request
     $.ajax({
       url: rrturl+'expenditure_expencessheet/newbeneficiarydetails_onchnagefile.php', 
       type: 'post',
       data: form_data,
       contentType: false,
       processData: false,
       dataType: 'html',
       success: function (response) {
          $("#expense_payandbeneficiarydiv").html('');
          $("#expense_payandbeneficiarydiv").append(response);
          // $('.select2').select2();
          // $('.select2-selection').css('border-radius','0px');
          // $('.select2-container').children().css('border-radius','0px');
          // $('.select2-selection').css('height','38px');
       },beforeSend: function(){
        $("#expense_payandbeneficiarydiv").html('<p style="color:red; text-align:center;">Please wait ....</p>');
      }
     });
  });
  $(document).on("click", "input[type='checkbox'][class='paytoasbeneficiarychkbox']", function(){
    var ptasbnfid = $(this).prop("id");
    var bnfinputid = ptasbnfid.replace("paytoassamebeneficiary", "beneficiaryname");
    if($("#"+ptasbnfid+"").is(":checked")){
      $("#"+bnfinputid+"").val($("#paytoname_exp option:selected").text()).trigger("change");
    }else{
      $("#"+bnfinputid+"").select2("val",$("#"+bnfinputid+" option:eq(1)").val());
    }
  });
  $('.select2').select2();
  $('.select2-selection').css('border-radius','0px');
  $('.select2-container').children().css('border-radius','0px');
  $('.select2-selection').css('height','42px');
</script>