<?php
$frmmail_emailsno="";
$frmmail_attsno="";
$frmmail_attpath="";
if(isset($_GET['dz'])){
  require('../connection.php');
  require('../functions/function_master.php');
  $key=$_GET['k'];
  $key_decoded = base64url_decode($key);
  $key_parts = explode("|",$key_decoded);
  $checkkey = base64url_decode($key_parts[count($key_parts)-1]);
  $actkey = "";
  for($i=0;$i<count($key_parts)-1 ;$i++){
    $actkey = $actkey?$actkey."|".$key_parts[$i]:$key_parts[$i];
  }
  if($actkey!=$checkkey){
    echo"<div class=\"col-md-12 text-center black\"><h3>OOPS!!Something went wrong. Please Try after sometime.</h3></div>";
    die;
  }
  $loginname ="";
  $loginid = $key_parts[0];
  $loginroleid = $key_parts[1];
  $companyid = $key_parts[2];
  $subcompanyid = $key_parts[3];
  $projectid = $key_parts[4];
  $logintype = $key_parts[5];
  $newkey = "x|x|".$key_parts[2]."|".$key_parts[3]."|".$key_parts[4]."|x";
  $encodednewkey = base64url_encode($newkey);
  $keycompany=base64url_encode($newkey."|".$encodednewkey);
  $cmpinfo=getcompanydetails($mconn,"companyid",$companyid);
   $companyname="";
   if(count($cmpinfo)>0){
    foreach ($cmpinfo as $cmphkey) {
      $companyname=$cmphkey['company_name'];
      $countryid=$cmphkey['countryid'];
    }
  }

  $subcmpinfo=getsubcompanydetails($mconn,$companyid,$subcompanyid,"subcompanyid",$subcompanyid);
  $subcompanyname="";
  if(count($subcmpinfo)>0){
    foreach ($subcmpinfo as $subcmphkey) {
      $subcompanyname=$subcmphkey['subcompanyname'];
    }
  }

  //projectinfo
  $prj=getprojectdetails($conn,$companyid,$subcompanyid,"projectid",$projectid);
  // print_r($prj);
  $projectname ="";
  if(count($prj)>0){
    foreach ($prj as $pjkey) {
      $projectname = $pjkey['projectname'];
    }
  }

  $frmmail_emailsno=$_GET['emailid'];
  $frmmail_attsno=$_GET['attid'];
  $frmmail_attpath=$_GET['attpath'];
  $protocol=$_SERVER['PROTOCOL'] = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';
  $url = basename( $_SERVER["PHP_SELF"]);
  $rooturl = $protocol.'://'.$_SERVER['HTTP_HOST'];
  $baseurl= $rooturl.str_replace($url, "", $_SERVER["PHP_SELF"]);
  $countryid = "";
  if($_SERVER['HTTP_HOST']=="localhost"){
    $prefix=$rooturl."/krf_amanaccounting/";
  }else{
    $prefix=$rooturl."/";
  }
  $rooturl =$prefix;
  // echo "hello with if";
}else{
  // echo "hello with else";
  require('../header.php');
  $frmmail_emailsno="";
  $frmmail_attsno="";
  $frmmail_attpath="";
  $rooturl; 
}
$subaction="prepayment";
if(isset($_GET['subaction'])){
  $subaction = str_replace("-", "", strtolower($_GET['subaction']));
}

$insturl="";
$instid ="";
$proceedtodigitizebtn="";
$isvoiceinstrution="";
$fromaccountid = "";
$rpaytoname = "";
$ramount = "";
$subprojectid ="";
if(isset($_GET['insid']) && $_GET['insid']!=null && $_GET['insid']!="null" && $_GET['insid']!="undefined"){
  if(isset($_GET['insid'])){
    $instid = $_GET['insid'];
    $vvcinfo=voiceinstructiondetails($authconn,$conn,$companyid,$subcompanyid,$projectid,"instructionid",$instid);
    // print_r($vvcinfo);
    if(count($vvcinfo)>0){
      foreach ($vvcinfo as $iiv) {
        $fromaccountid = $iiv['from_accountid'];
        $rpaytoname = $iiv['payto'];
        $ramount = $iiv['requestamount'];
        $subprojectid = $iiv['subprojectid'];
      }
    }
  }
  $isvoiceinstrution="yes";
}
$dz="";
if(isset($_GET['dz'])){
  $dz=$_GET['dz'];
}
$mdid ="";
if(isset($_GET['mdid'])){
	$mdid = $_GET['mdid'];
}
$dddr = loginmodulesdetails_frommaster($authconn,$companyid,$subcompanyid,$projectid,"moduleid",$mdid);
if(count($dddr)>0){
	$groupid = $dddr[0]['groupid'];
}
?>
<input type="hidden" id="hidden_attpath_fromemail" value="<?php echo $frmmail_attpath; ?>" />
<input type="hidden" id="hidden_attsno_fromemail" value="<?php echo $frmmail_attsno; ?>" />
<input type="hidden" id="hidden_emailsno_fromemail" value="<?php echo $frmmail_emailsno; ?>" />
<input type="hidden" id="hidden_instructionid" value="<?php echo $instid; ?>" />
<div id="viewvoiceinstructionmsg"></div>
<?php echo $proceedtodigitizebtn; ?>
<div id="typeexpense_typediv" style="display: none;">
  <div class="main-container bg-info">
    <div class="row p-2">
      <div class="col-md-12">
        <h5 class="uppercase bold">TYPE OF DETAILS FOR EXPENSES</h5>
      </div>
    </div>
  </div>
  <div class="main-container bg-white p-2 pb-4">
    <div class="row p-2">
      <div class="col-md-12">
         <label class="mt-2"><input type="radio" name="paymenttypeselection" value="Pre-Payment" style="transform: scale(2);" /> Request for Payment </label>
         <label class="mt-2"><input type="radio" name="paymenttypeselection" value="Post-Payment"  style="transform: scale(2);" style="transform: scale(2);" /> Document of Payment Already Made</label>
      </div>
    </div>
  </div>
</div>
<!-- expense type -->
 <!-- style="padding: 16px 128px !important;" -->
<style type="text/css">
.container-manual {
    width: 90%;
    max-width: 1140px; /* Ensures it doesn't exceed 1140px on larger screens */
    position: relative;
    margin-left: auto;
    margin-right: auto;
}

/* Optional: Define a minimum width for smaller screens */
@media (max-width: 576px) {
    .container-manual {
        width: 100%; /* Adjusts the width for very small screens */
    }
}
</style>
<div class="container-manual1">
  <div id="expense_typediv" class="bg-dark-gray-red-p5" style="margin:8px 0px 16px 0px;">
    <div class="main-container p-2 pt-4">
      <div class="row">
        <div class="col-md-12 text-center">
          <?php 
          // if($subaction=="prepayment"){
          //   echo '<h5 class="uppercase bold" style="font-size: 1.6rem;">Make a Request for Payment</h5>';
          // }else{
          //   echo '<h5 class="uppercase bold" style="font-size: 1.6rem;">Post Payment Record (Already Paid)</h5>';
          // }
          ?>
          <!-- <button class="btn btn-sm btn-info pull-right" id="btnback">Back</button> -->
        </div>
        <?php 
        if($dz==""){
        ?>
        <div class="col-md-12 mt-2">
          <a href="<?php echo $rooturl; ?>admin_dashboard.php?k=<?php echo $key; ?>&grpid=<?php echo $groupid; ?>&mdid=<?php echo $mdid; ?>" class="btn btn-sm btn-deepblue pull-right backtohome">Back to Home</a>
        </div>
      </div>
      <?php
      }
      ?>
      <div id="allother1">
        <div class="row p-2">
          <div class="col-md-12 mt-2 <?php echo ($subaction == 'postpayment') ? '' : 'd-none'; ?>">
            <label class="m-2"><input type="radio" name="purposecategory" value="postpaymentpart1" data-detailheading="POST PAYMENT RECORD" data-rqtype="PART 1 – Quick Entry" style="transform: scale(2);" /> Post Payment PART 1 – Quick Entry</label>
          </div>
          <div class="col-md-12 mt-2 <?php echo ($subaction == 'postpayment') ? '' : 'd-none'; ?>">
            <label class="m-2"><input type="radio" name="purposecategory" value="postpaymentpart2" data-detailheading="POST PAYMENT RECORD" data-rqtype="PART 2 – Detailed Entry" style="transform: scale(2);" /> Post Payment PART 2 – Detailed Entry</label>
          </div>
          <div class="col-md-12 mt-2 d-none">
            <label class="m-2"><input type="radio" name="purposecategory" value="postpaymentpart3" data-detailheading="POST PAYMENT RECORD" data-rqtype="PART 3 – Credit Card Entry" style="transform: scale(2);" /> Post Payment PART 3 – Credit Card</label>
          </div>
        </div>    
      </div>

      <details id="humanresourcerequest" class="<?php echo ($subaction == 'prepayment') ? 'd-none' : 'd-none'; ?>">
        <summary>Human Resources (Salaries & wages) Payments</summary>
        <div class="row p-2">
          <div class="col-md-12 mt-2" >
            <label class="m-2"><input type="radio" name="purposecategory" data-detailheading="Human Resources (Salaries & wages)" data-rqtype="Upload Salary Details From Excel Sheet" value="excelsalary" style="transform: scale(2);" /> UPLOAD SALARY DETAILS FROM EXCEL SHEET</label>
          </div>
          <div class="col-md-12 mt-2" >
            <label class="m-2"><input type="radio" name="purposecategory" data-detailheading="Human Resources (Salaries & wages)" data-rqtype="Individual Manual Salary Request" value="manualsalary" style="transform: scale(2);" /> INDIVIDUAL MANUAL SALARY REQUEST</label>
          </div>
        </div>    
      </details>

      <details id="UtilitiesPayment" class="<?php echo ($subaction == 'prepayment') ? 'd-none' : 'd-none'; ?>">
        <summary>Utilities Payments</summary>
        <div class="row p-2">
          <?php
          // $billlist=billmasteruniversal($conn,$companyid,$subcompanyid,$projectid,"all","");
          // if(count($billlist)>0){
          // ?>
          // <div class="col-md-12 mt-2">
          //     <label class="m-2"><input type="radio" name="purposecategory" value="billrequest" data-detailheading="Utilities Payment" data-rqtype="Bill Payment Request" style="transform: scale(2);" /> Bill Payment Request</label>
          // </div>
          // <?php
          // }else{
          //   echo "<div class='col-md-12 mt-2'>";
          //   echo "Utilities bill not registerd";
          //   echo "</div>";
          // }
          ?>
        </div>    
      </details>

      <details id="expenditurePayment" class="<?php echo ($subaction == 'prepayment') ? '' : 'd-none'; ?>">
        <summary>Expenditure Payment</summary>
        <div class="row p-2">
          <div class="col-md-12 mt-2" >
            <label class="m-2"><input type="radio" name="purposecategory" value="4" data-detailheading="Expenditure Payment" data-rqtype="Request Part Payment (Just a Reminder)" style="transform: scale(2);" /> Request for Advance Payment (Just a Reminder)</label>
          </div>
          <div class="col-md-12 mt-2" >
            <label class="m-2"><input type="radio" name="purposecategory" value="9" data-detailheading="Expenditure Payment" data-rqtype="Request Investment Payment" style="transform: scale(2);" /> Request Investment Payment</label>
          </div>
          <div class="col-md-12 mt-2 d-none">
            <label class="m-2"><input type="radio" name="purposecategory" value="taxinvoice" data-detailheading="Expenditure Payment" data-rqtype="Request for Tax Invoice" style="transform: scale(2);" /> Request for Tax Invoice (Non-Supplier)</label>
          </div>
          <div class="col-md-12 mt-2">
            <label class="m-2"><input type="radio" name="purposecategory" value="1-1" data-detailheading="Expenditure Payment" data-rqtype="Voucher Single Purpose (Other Than Invoice)" style="transform: scale(2);" /> Voucher Single Purpose (Other Than Invoice)</label>
          </div>

          <div class="col-md-12 mt-2 <?php echo ($subaction == 'prepayment') ? '' : 'd-none'; ?>">
            <label class="m-2"><input type="radio" name="purposecategory" value="1" data-detailheading="Expenditure Payment" data-rqtype="Voucher Multiple Purpose (Other Than Invoice)" style="transform: scale(2);" /> Voucher Multiple Purpose (Other Than Invoice)</label>
          </div>
          <div class="col-md-12 mt-2 d-none">
              <label class="m-2"><input type="radio" name="purposecategory" data-detailheading="Expenditure Payment" data-rqtype="Upload Expense Other Than Salary" value="otherthansalary" style="transform: scale(2);" /> Upload Expense Other Than Salary</label>
          </div>

          <div class="col-md-12 mt-2 <?php echo ($subaction == 'prepayment') ? '' : 'd-none'; ?>">
              <label class="m-2"><input type="radio" name="purposecategory"  data-detailheading="Expenditure Payment" data-rqtype="VENDOR TDS REQUST" value="vendortds" style="transform: scale(2);" /> Vendor TDS Requst</label>
          </div>
          <div class="col-md-12 mt-2 <?php echo ($subaction == 'prepayment') ? '' : 'd-none'; ?>">
              <label class="m-2"><input type="radio" name="purposecategory"  data-detailheading="Expenditure Payment" data-rqtype="INCOME TAX REQUST" value="itaxrequest" style="transform: scale(2);" /> Income Tax Requst</label>
          </div>
        </div>    
      </details>

      <details id="reqularsupplier" class="<?php echo ($subaction == 'prepayment') ? '' : 'd-none'; ?>">
        <summary>Regular Suppliers</summary>
        <div class="row p-2">
          <div class="col-md-12 mt-2" >
            <label class="m-2"><input type="radio" name="purposecategory" value="12-0" data-detailheading="Regular Suppliers" data-rqtype="Request Payment on Account" style="transform: scale(2);" /> Request Payment on Account</label>
          </div>
          <div class="col-md-12 mt-2" >
            <label class="m-2"><input type="radio" name="purposecategory" value="12-1" data-detailheading="Regular Suppliers" data-rqtype="Request Payment Against Invoice" style="transform: scale(2);" /> Request Payment Against Invoice</label>
          </div>
          <div class="col-md-12 mt-2 d-none">
            <label class="m-2"><input type="radio" name="purposecategory" value="12-2" data-detailheading="Regular Suppliers" data-rqtype="Adjustment Against Advance Payment" style="transform: scale(2);" /> Adjustment Against Advance Payment</label>
          </div>
        </div>    
      </details>
      <details id="othetransfertoaccount" class="<?php echo ($subaction == 'prepayment') ? '' : 'd-none'; ?>">
        <summary>Other than Expenditure (Internal Transfer)</summary>
        <!-- <summary>Transfers to Other Accounts (Loans, Imprest, Cash, Credit Card Payments, Internal transfers within banks)</summary> -->
        <div class="row p-2">
          <div class="col-md-12 mt-2" >
            <label class="m-2"><input type="radio" name="purposecategory" value="2" data-detailheading="Other than Expenditure (Internal Transfer)" data-rqtype="Request Loan Payment" style="transform: scale(2);" /> Request For Loan Payment</label>
          </div>
          <div class="col-md-12 mt-2" >
            <label class="m-2"><input type="radio" name="purposecategory" value="3" data-detailheading="Other than Expenditure (Internal Transfer)" data-rqtype="Request Imprest Payment" style="transform: scale(2);" /> Request For Imprest Payment</label>
          </div>
          <div class="col-md-12 mt-2" >
            <label class="m-2"><input type="radio" name="purposecategory" value="5" data-detailheading="Other than Expenditure (Internal Transfer)" data-rqtype="Request Fixed Deposits (Savings)" style="transform: scale(2);" /> Request For Fixed Deposits (Savings)</label>
          </div>
          <div class="col-md-12 mt-2" >
            <label class="m-2"><input type="radio" name="purposecategory" value="6" data-detailheading="Other than Expenditure (Internal Transfer)" data-rqtype="Request Cash Payment" style="transform: scale(2);" /> Request For Cash Payment</label>
          </div>
          <div class="col-md-12 mt-2 d-none" >
            <label class="m-2"><input type="radio" name="purposecategory" value="11" data-detailheading="Recurring Deposit Scheme (RDS)" data-rqtype="Request Cash Payment" style="transform: scale(2);" /> Request For RDS Payment</label>
          </div>
          <div class="col-md-12 mt-2" >
            <label class="m-2"><input type="radio" name="purposecategory" value="7" data-detailheading="Other than Expenditure (Internal Transfer)" data-rqtype="Request Internal Transfer Within Banks of the Project" style="transform: scale(2);" /> Request Internal Transfer Within Banks of the Project</label>
          </div>
          <div class="col-md-12 mt-2" >
            <label class="m-2"><input type="radio" name="purposecategory" value="8" data-detailheading="Other than Expenditure (Internal Transfer)" data-rqtype="Request Credit Card Payment" style="transform: scale(2);" /> Request For Credit Card Payment</label>
          </div>
        </div>    
      </details>
      <div id="expensetypedivid" class="mt-2"></div>
    </div>
  </div>
  <div id="exphtmlcontainerdiv"></div>
  <div id="requstdatadiv" style="display:none; margin:8px 0px 16px 0px;">
    <div class="main-container bg-dark-gray-red-p1">
      <div class="row p-2">
        <div class="col-md-12">
           <button class="btn btn-deepblue btn-sm" style="float: right; margin-right: 20px;" onclick="previousdivshow('requstdatadiv','expense_typediv');"> BACK <img src="<?php echo $prefix; ?>expenditure_expencessheet/leftarrow.png" style="width: 30px;" /></button>
          <h5 class="uppercase bold " id="requestmodulehead"></h5>
        </div>
      </div>
    </div>
    <div class="main-container">
      <div class="card">
        <div class="card-header">
          <div id="headingofcontent"></div>
        </div>
        <div class="card-body">
          <div id="htmlcontainerdiv"></div>
        </div>
    </div>
  </div>
</div>
<?php 
if(isset($_GET['dz'])){
}else{
   require('../footer.php');
}
?>
<script type="text/javascript">
  $(document).ready(function() {
    var companyid = $("#hidden_companyid").val();
    var companyname = $("#hidden_companyname").val();
    var subcompanyid = $("#hidden_subcompanyid").val();
    var projectid = $("#hidden_projectid").val();
    var loginid = $("#hidden_loginid").val();
    var routurl = $("#hidden_rooturl").val();
    var subaction = "<?php echo $subaction; ?>";
    $("#cmpnamediv").text(companyname);
    // expensetypehtml('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid','expensetypedivid',subaction);
    var instid ='<?php echo $instid; ?>';
    $(document).on("click", "input[name='paymenttypeselection']",  function(){
      var tpexp = $("input[name='paymenttypeselection']:checked").val();
      $("#expense_typediv").show();
      $("#sltexptypehead").html("Selection of Expense Type for "+tpexp);
    });
    // alert("<?php echo $subaction; ?>");
    if("<?php echo $subaction; ?>"=="prepayment" && "<?php echo $isvoiceinstrution; ?>"==""){
      $("input[type='radio'][name='paymenttypeselection'][value='Pre-Payment']").prop("checked", true);
      $("#sltexptypehead").html("Selection of Expense Type for <i style='color:blue;'>Payment Request</i>");
    }else if("<?php echo $subaction; ?>"=="postpayment" && "<?php echo $isvoiceinstrution; ?>"==""){
      $("input[type='radio'][name='paymenttypeselection'][value='Post-Payment']").prop("checked", true);      
      $("#sltexptypehead").html("Selection of Expense Type for <i style='color:blue;'>Post Payment Entry</i>");
    }


    if("<?php echo $isvoiceinstrution; ?>"=="yes"){
      if("<?php echo $subaction; ?>"=="prepayment"){
        $("input[type='radio'][name='paymenttypeselection'][value='Pre-Payment']").prop("checked", true);
        $("#sltexptypehead").html("Selection of Expense Type for <i style='color:blue;'>Payment Request</i>");
        }else if("<?php echo $subaction; ?>"=="postpayment"){
          $("input[type='radio'][name='paymenttypeselection'][value='Post-Payment']").prop("checked", true);      
          $("#sltexptypehead").html("Selection of Expense Type for <i style='color:blue;'>Post Payment Entry</i>");
        }
    }

    
    $(document).on("click", "input[name='purposecategory']",  function(){
      $("#htmlcontainerdiv").html('');
      $("#requstdatadiv").hide();
      var headerofcoentent = $(this).data("detailheading");
      var reqheadertxt = $(this).data("rqtype");
       $("#requestmodulehead").html(headerofcoentent);
       $("#headingofcontent").html("<h4 class='text-center uppercase bold mt-4'>"+reqheadertxt+"</h4>");
      var ppcatid=$("input[name='purposecategory']:checked").val();
      if(ppcatid=="1" || ppcatid=="01"){
        $("#firstnextbtn").hide();
        $("#htmlcontainerdiv").load("<?php echo $rooturl; ?>expenditure_expencessheet/request_sep_multiple.php?cntryid=<?php echo $countryid; ?>&ppcatid="+ppcatid+"&dz=<?php echo $dz; ?>&subaction=<?php echo $subaction; ?>&ramount=<?php echo $ramount; ?>&rinstid=<?php echo $instid; ?>&subprojectid=<?php echo $subprojectid; ?>", function(){
           
            $("#requstdatadiv").show();
          nextdivshow('expense_typediv','requstdatadiv');
        });
      }else if(ppcatid=="1-1"){
        $("#firstnextbtn").hide();
        $("#htmlcontainerdiv").load("<?php echo $rooturl; ?>expenditure_expencessheet/request_spe.php?cntryid=<?php echo $countryid; ?>&ppcatid=1&dz=<?php echo $dz; ?>&subaction=<?php echo $subaction; ?>&ramount=<?php echo $ramount; ?>&rinstid=<?php echo $instid; ?>&subprojectid=<?php echo $subprojectid; ?>", function(){
          $('.select2manual').select2();
          $('.select2-selection').css('border-radius','0px');
          $('.select2-container').children().css('border-radius','0px');
          $('.select2-selection--single').css({'height':'42px  !important'});
          $('.select2manual').select2({
              allowClear: true,
              tags: true // Enable the "tags" feature
          });
          $("#requstdatadiv").show();
          nextdivshow('expense_typediv','requstdatadiv');
          var tpexp = $("input[name='paymenttypeselection']:checked").val();
          if(tpexp=="Pre-Payment"){
            $(".mdtdiv").hide();
          }
        });
      }else if(ppcatid=="2" || ppcatid=="02"){
        $("#firstnextbtn").hide();
        $("#htmlcontainerdiv").load("<?php echo $rooturl; ?>expenditure_expencessheet/request_loan.php?cntryid=<?php echo $countryid; ?>&ppcatid="+ppcatid+"&dz=<?php echo $dz; ?>&subaction=<?php echo $subaction; ?>&ramount=<?php echo $ramount; ?>&rinstid=<?php echo $instid; ?>&subprojectid=<?php echo $subprojectid; ?>", function(){
          $("#requstdatadiv").show();
          nextdivshow('expense_typediv','requstdatadiv');
          var tpexp = $("input[name='paymenttypeselection']:checked").val();
          if(tpexp=="Pre-Payment"){
            $(".mdtdiv").hide();
          }
        });
      }else if(ppcatid=="3" || ppcatid=="03"){
          $("#firstnextbtn").hide();
          // alert('<?php echo $rooturl; ?>');
          // console.log('<?php echo $rooturl; ?>');
          $("#htmlcontainerdiv").load("<?php echo $rooturl; ?>expenditure_expencessheet/request_imprest.php?cntryid=<?php echo $countryid; ?>&ppcatid="+ppcatid+"&dz=<?php echo $dz; ?>&subaction=<?php echo $subaction; ?>&ramount=<?php echo $ramount; ?>&rinstid=<?php echo $instid; ?>&subprojectid=<?php echo $subprojectid; ?>", function(){
              $("#requstdatadiv").show();
              nextdivshow('expense_typediv','requstdatadiv');
              var tpexp = $("input[name='paymenttypeselection']:checked").val();
              if(tpexp=="Pre-Payment"){ $(".mdtdiv").hide(); }
            });
      }else if(ppcatid=="4" || ppcatid=="04"){
        $("#firstnextbtn").hide();
        $("#htmlcontainerdiv").load("<?php echo $rooturl; ?>expenditure_expencessheet/request_partial.php?cntryid=<?php echo $countryid; ?>&ppcatid="+ppcatid+"&dz=<?php echo $dz; ?>&subaction=<?php echo $subaction; ?>&ramount=<?php echo $ramount; ?>&rinstid=<?php echo $instid; ?>&subprojectid=<?php echo $subprojectid; ?>", function(){
          $("#requstdatadiv").show();
          nextdivshow('expense_typediv','requstdatadiv');
          var tpexp = $("input[name='paymenttypeselection']:checked").val();
          if(tpexp=="Pre-Payment"){
            $(".mdtdiv").hide();
          }
        });
      }else if(ppcatid=="5" || ppcatid=="05"){
        $("#firstnextbtn").hide();
        $("#htmlcontainerdiv").load("<?php echo $rooturl; ?>expenditure_expencessheet/request_fixeddepostsaving.php?cntryid=<?php echo $countryid; ?>&ppcatid="+ppcatid+"&dz=<?php echo $dz; ?>&subaction=<?php echo $subaction; ?>&ramount=<?php echo $ramount; ?>&rinstid=<?php echo $instid; ?>&subprojectid=<?php echo $subprojectid; ?>", function(){
          $("#requstdatadiv").show();
          nextdivshow('expense_typediv','requstdatadiv');
          var tpexp = $("input[name='paymenttypeselection']:checked").val();
          if(tpexp=="Pre-Payment"){
            $(".mdtdiv").hide();
          }
        });
      }else if(ppcatid=="6" || ppcatid=="06"){
        $("#firstnextbtn").hide();
        $("#htmlcontainerdiv").load("<?php echo $rooturl; ?>expenditure_expencessheet/request_cashpayment.php?cntryid=<?php echo $countryid; ?>&ppcatid="+ppcatid+"&dz=<?php echo $dz; ?>&subaction=<?php echo $subaction; ?>&ramount=<?php echo $ramount; ?>&rinstid=<?php echo $instid; ?>&subprojectid=<?php echo $subprojectid; ?>", function(){
          $("#requstdatadiv").show();
          nextdivshow('expense_typediv','requstdatadiv');
          var tpexp = $("input[name='paymenttypeselection']:checked").val();
          if(tpexp=="Pre-Payment"){
            $(".mdtdiv").hide();
          }
        });
      }else if(ppcatid=="7" || ppcatid=="07"){
        $("#firstnextbtn").hide();
        $("#htmlcontainerdiv").load("<?php echo $rooturl; ?>expenditure_expencessheet/request_interbanktransfer.php?cntryid=<?php echo $countryid; ?>&ppcatid="+ppcatid+"&dz=<?php echo $dz; ?>&subaction=<?php echo $subaction; ?>&ramount=<?php echo $ramount; ?>&rinstid=<?php echo $instid; ?>&subprojectid=<?php echo $subprojectid; ?>", function(){
          $("#requstdatadiv").show();
          nextdivshow('expense_typediv','requstdatadiv');
          var tpexp = $("input[name='paymenttypeselection']:checked").val();
          if(tpexp=="Pre-Payment"){
            $(".mdtdiv").hide();
          }
        });
      }else if(ppcatid=="8" || ppcatid=="08"){
        $("#firstnextbtn").hide();
        $("#htmlcontainerdiv").load("<?php echo $rooturl; ?>expenditure_expencessheet/request_creditcardpayment.php?cntryid=<?php echo $countryid; ?>&ppcatid="+ppcatid+"&dz=<?php echo $dz; ?>&subaction=<?php echo $subaction; ?>&ramount=<?php echo $ramount; ?>&rinstid=<?php echo $instid; ?>&subprojectid=<?php echo $subprojectid; ?>", function(){
          $("#requstdatadiv").show();
          nextdivshow('expense_typediv','requstdatadiv');
          var tpexp = $("input[name='paymenttypeselection']:checked").val();
          if(tpexp=="Pre-Payment"){
            $(".mdtdiv").hide();
          }
        });
      }else if(ppcatid=="9" || ppcatid=="09"){
        $("#firstnextbtn").hide();
        $("#htmlcontainerdiv").load("<?php echo $rooturl; ?>expenditure_expencessheet/request_investmentotherpayment.php?cntryid=<?php echo $countryid; ?>&ppcatid="+ppcatid+"&dz=<?php echo $dz; ?>&subaction=<?php echo $subaction; ?>&ramount=<?php echo $ramount; ?>&rinstid=<?php echo $instid; ?>&subprojectid=<?php echo $subprojectid; ?>", function(){
          $("#requstdatadiv").show();
          nextdivshow('expense_typediv','requstdatadiv');
          var tpexp = $("input[name='paymenttypeselection']:checked").val();
          if(tpexp=="Pre-Payment"){
            $(".mdtdiv").hide();
          }
        });
      }else if(ppcatid=="12-0" || ppcatid=="12"){
        $("#firstnextbtn").hide();
        $("#htmlcontainerdiv").load("<?php echo $rooturl; ?>expenditure_expencessheet/request_supplierpayment.php?cntryid=<?php echo $countryid; ?>&ppcatid=1&dz=<?php echo $dz; ?>&subaction=<?php echo $subaction; ?>&ramount=<?php echo $ramount; ?>&rinstid=<?php echo $instid; ?>&subprojectid=<?php echo $subprojectid; ?>", function(){
          $("#requstdatadiv").show();
          nextdivshow('expense_typediv','requstdatadiv');
          var tpexp = $("input[name='paymenttypeselection']:checked").val();
          if(tpexp=="Pre-Payment"){
            $(".mdtdiv").hide();
          }
        });
      }else if(ppcatid=="12-1" || ppcatid=="12-1"){
        // alert(ppcatid);
        $("#firstnextbtn").hide();
        $("#htmlcontainerdiv").load("<?php echo $rooturl; ?>expenditure_expencessheet/request_invoicepurchase.php?cntryid=<?php echo $countryid; ?>&ppcatid=1&dz=<?php echo $dz; ?>&subaction=<?php echo $subaction; ?>&ramount=<?php echo $ramount; ?>&rinstid=<?php echo $instid; ?>&subprojectid=<?php echo $subprojectid; ?>&pyt=splr", function(){
          $("#requstdatadiv").show();
          nextdivshow('expense_typediv','requstdatadiv');
          var tpexp = $("input[name='paymenttypeselection']:checked").val();
          if(tpexp=="Pre-Payment"){
            $(".mdtdiv").hide();
          }
        });
      }else if(ppcatid=="12-2" || ppcatid=="12-2"){
        $("#firstnextbtn").hide();
        $("#htmlcontainerdiv").load("<?php echo $rooturl; ?>expenditure_expencessheet/request_invoicepurchase.php?cntryid=<?php echo $countryid; ?>&ppcatid=1&dz=<?php echo $dz; ?>&subaction=<?php echo $subaction; ?>&ramount=<?php echo $ramount; ?>&rinstid=<?php echo $instid; ?>&subprojectid=<?php echo $subprojectid; ?>&pyt=splr", function(){
          $("#requstdatadiv").show();
          nextdivshow('expense_typediv','requstdatadiv');
          var tpexp = $("input[name='paymenttypeselection']:checked").val();
          if(tpexp=="Pre-Payment"){
            $(".mdtdiv").hide();
          }
        });
      }else if(ppcatid=="taxinvoice"){
          $("#firstnextbtn").hide();
          $("#htmlcontainerdiv").load("<?php echo $rooturl; ?>expenditure_expencessheet/request_invoicepurchase.php?cntryid=<?php echo $countryid; ?>&ppcatid=1&dz=<?php echo $dz; ?>&subaction=<?php echo $subaction; ?>&ramount=<?php echo $ramount; ?>&rinstid=<?php echo $instid; ?>&subprojectid=<?php echo $subprojectid; ?>", function(){
            $("#requstdatadiv").show();
            nextdivshow('expense_typediv','requstdatadiv');
            var tpexp = $("input[name='paymenttypeselection']:checked").val();
            if(tpexp=="Pre-Payment"){
              $(".mdtdiv").hide();
            }
          });
      }else if(ppcatid=="excelsalary"){
        $("#firstnextbtn").hide();
        $("#htmlcontainerdiv").load("<?php echo $rooturl; ?>expenditure_expencessheet/request_salary.php?cntryid=<?php echo $countryid; ?>&ppcatid=1&dz=<?php echo $dz; ?>&subaction=<?php echo $subaction; ?>&ramount=<?php echo $ramount; ?>&rinstid=<?php echo $instid; ?>&subprojectid=<?php echo $subprojectid; ?>&sltyp=excelsalary",function(){ 
          $("#requstdatadiv").show();
          nextdivshow('expense_typediv','requstdatadiv');
          var tpexp = $("input[name='paymenttypeselection']:checked").val();
            if(tpexp=="Pre-Payment"){
              $(".mdtdiv").hide();
            }
          });
      }else if(ppcatid=="manualsalary"){
        $("#firstnextbtn").hide();
        $("#htmlcontainerdiv").load("<?php echo $rooturl; ?>expenditure_expencessheet/request_salary.php?cntryid=<?php echo $countryid; ?>&ppcatid=1&dz=<?php echo $dz; ?>&subaction=<?php echo $subaction; ?>&ramount=<?php echo $ramount; ?>&rinstid=<?php echo $instid; ?>&subprojectid=<?php echo $subprojectid; ?>&sltyp=manualsalary",function(){ 
          $("#requstdatadiv").show();
          nextdivshow('expense_typediv','requstdatadiv');
          var tpexp = $("input[name='paymenttypeselection']:checked").val();
            if(tpexp=="Pre-Payment"){
              $(".mdtdiv").hide();
            }
          });
      }else if(ppcatid=="otherthansalary"){
        $("#firstnextbtn").hide();
        $("#htmlcontainerdiv").load("<?php echo $rooturl; ?>expenditure_expencessheet/request_otherthansalary.php?cntryid=<?php echo $countryid; ?>&ppcatid=1&dz=<?php echo $dz; ?>&subaction=<?php echo $subaction; ?>&ramount=<?php echo $ramount; ?>&rinstid=<?php echo $instid; ?>&subprojectid=<?php echo $subprojectid; ?>",function(){ 
          $("#requstdatadiv").show();
          nextdivshow('expense_typediv','requstdatadiv');
          var tpexp = $("input[name='paymenttypeselection']:checked").val();
            if(tpexp=="Pre-Payment"){
              $(".mdtdiv").hide();
            }
          });
      }else if(ppcatid=="vendortds"){
          $("#firstnextbtn").hide();
          $("#htmlcontainerdiv").load("<?php echo $rooturl; ?>expenditure_expencessheet/request_vendor_tds.php?cntryid=<?php echo $countryid; ?>&ppcatid=1&dz=<?php echo $dz; ?>&subaction=<?php echo $subaction; ?>&ramount=<?php echo $ramount; ?>&rinstid=<?php echo $instid; ?>&subprojectid=<?php echo $subprojectid; ?>", function(){
            $("#requstdatadiv").show();
            nextdivshow('expense_typediv','requstdatadiv');
            var tpexp = $("input[name='paymenttypeselection']:checked").val();
            if(tpexp=="Pre-Payment"){
              $(".mdtdiv").hide();
            }
          });
      }else if(ppcatid=="itaxrequest"){
          $("#firstnextbtn").hide();
          $("#htmlcontainerdiv").load("<?php echo $rooturl; ?>expenditure_expencessheet/request_incometax.php?cntryid=<?php echo $countryid; ?>&ppcatid=11&dz=<?php echo $dz; ?>&subaction=<?php echo $subaction; ?>&ramount=<?php echo $ramount; ?>&rinstid=<?php echo $instid; ?>&subprojectid=<?php echo $subprojectid; ?>", function(){
            $("#requstdatadiv").show();
            nextdivshow('expense_typediv','requstdatadiv');
            var tpexp = $("input[name='paymenttypeselection']:checked").val();
            if(tpexp=="Pre-Payment"){
              $(".mdtdiv").hide();
            }
          });
      }else if(ppcatid=="billrequest"){
          $("#firstnextbtn").hide();
          $("#htmlcontainerdiv").load("<?php echo $rooturl; ?>expenditure_expencessheet/request_billpayment.php?cntryid=<?php echo $countryid; ?>&ppcatid="+ppcatid+"&dz=<?php echo $dz; ?>&subaction=<?php echo $subaction; ?>&ramount=<?php echo $ramount; ?>&rinstid=<?php echo $instid; ?>&subprojectid=<?php echo $subprojectid; ?>", function(){
            $("#requstdatadiv").show();
            nextdivshow('expense_typediv','requstdatadiv');
            var tpexp = $("input[name='paymenttypeselection']:checked").val();
            if(tpexp=="Pre-Payment"){
              $(".mdtdiv").hide();
            }
          });
      }else if(ppcatid=="purchaseforproduction"){
          $("#firstnextbtn").hide();
          $("#htmlcontainerdiv").load("<?php echo $rooturl; ?>expenditure_expencessheet/request_pforp.php?cntryid=<?php echo $countryid; ?>&ppcatid=1&dz=<?php echo $dz; ?>&subaction=<?php echo $subaction; ?>&ramount=<?php echo $ramount; ?>&rinstid=<?php echo $instid; ?>&subprojectid=<?php echo $subprojectid; ?>", function(){
            $("#requstdatadiv").show();
            nextdivshow('expense_typediv','requstdatadiv');
            var tpexp = $("input[name='paymenttypeselection']:checked").val();
            if(tpexp=="Pre-Payment"){
              $(".mdtdiv").hide();
            }
          });
      }else if(ppcatid=="postpaymentpart1"){
          $("#firstnextbtn").hide();
          $("#htmlcontainerdiv").load("<?php echo $rooturl; ?>expenditure_expencessheet/post_payment_part1.php?cntryid=<?php echo $countryid; ?>&ppcatid=1&dz=<?php echo $dz; ?>&subaction=<?php echo $subaction; ?>&ramount=<?php echo $ramount; ?>&rinstid=<?php echo $instid; ?>&subprojectid=<?php echo $subprojectid; ?>", function(){
            $("#requstdatadiv").show();
            nextdivshow('expense_typediv','requstdatadiv');
            var tpexp = $("input[name='paymenttypeselection']:checked").val();
            if(tpexp=="Pre-Payment"){
              $(".mdtdiv").hide();
            }
          });
      }else if(ppcatid=="postpaymentpart2"){
          $("#expenditurePayment").removeClass("d-none");
          $("#reqularsupplier").removeClass("d-none");
          $("#othetransfertoaccount").removeClass("d-none");
          // $("#allother").hide();
          // $("#btnback").show();
      }else if(ppcatid=="postpaymentpart3"){
          $("#firstnextbtn").hide();
          $("#htmlcontainerdiv").load("<?php echo $rooturl; ?>expencesinformation/creditcardexpense_request.php?cntryid=<?php echo $countryid; ?>&ppcatid=1&dz=<?php echo $dz; ?>&subaction=<?php echo $subaction; ?>&ramount=<?php echo $ramount; ?>&rinstid=<?php echo $instid; ?>&subprojectid=<?php echo $subprojectid; ?>&rqs=2", function(){
            $("#requstdatadiv").show();
            nextdivshow('expense_typediv','requstdatadiv');
            var tpexp = $("input[name='paymenttypeselection']:checked").val();
            if(tpexp=="Pre-Payment"){
              $(".mdtdiv").hide();
            }
          });
      }     
    });
  });
// alert('<?php echo $subaction; ?>');
// $("#btnback").on("click", function(){
//   $("details").addClass("d-none");
//   $("#allother").show();
//   $("#btnback").hide();
// });


function newbeneficiarydetails_html(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,purposecategory_ename,purposesubcategory_eid,loginid_eid,transactionamount_eid,bnfdivcontainerclass,issupportingdocument,viewdivid,admorebtnid){
  var baseurl = "<?php echo $rooturl; ?>expenditure_expencessheet/";//$("#"+baseurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  var projectid = $("#"+projectid_eid+"").val();
  var loginid = $("#"+loginid_eid+"").val();
  var transactionamount = $("#"+transactionamount_eid+"").val();
  var purposecategoryid = $("input[name='"+purposecategory_ename+"']:checked").val();
  var purposesubcategoryid = $("#"+purposesubcategory_eid+"").val();
  // $("."+bnfdivcontainerclass+"").hide();
  var bnfdivcountno = $("."+bnfdivcontainerclass+"").length;
  $.ajax
    ({
      type: "POST",
      url: baseurl+"newbeneficiarydetails.php",
      data: {companyid: companyid,subcompanyid: subcompanyid,projectid: projectid,purposecategoryid: purposecategoryid,purposesubcategoryid: purposesubcategoryid,loginid: loginid, transactionamount: transactionamount, bnfdivcountno: bnfdivcountno,issupportingdocument: issupportingdocument,baseurl: baseurl},
      cache: false,
      dataType: "html",
      success: function(res)
      {
        $("#"+admorebtnid+"").hide();
        $("#"+viewdivid+"").append(res);
        $('.select2').select2();
        $('.select2-selection').css('border-radius','0px');
        $('.select2-container').children().css('border-radius','0px');
        $('.select2-selection').css('height','38px');
      }
    });
}
function readURL_viewiniframe(input, iframeid) {
    // if((input.files[0].size/1024)<=900){
      if (input.files && input.files[0]) {
          var reader = new FileReader();
        reader.onload = function (e) {
              $('#'+iframeid+'')
                .attr('src', e.target.result)
            };
        reader.readAsDataURL(input.files[0]);
      }
    // }else{
    //   event.target.value = '';
    //   alert("Your file size is grater than 900 KB, Please compress first and It should be less than 900 KB.");
    // }
  }
</script>