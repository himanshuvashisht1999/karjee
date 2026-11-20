<?php
$psodate ="";
$clientname ="";
$mobileno ="";
$emailid ="";
$deliverydate ="";
$psoamount ="";
if(isset($_GET['dz'])){
  require('../connection.php');
  require('../functions/function_master.php');
  include('functions/orders_functions.php');
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
}else{
  require('../header.php');
  include('functions/orders_functions.php');
  $rooturl; 
}
$mdid ="";
if(isset($_GET['mdid'])){
  $mdid = $_GET['mdid'];
}
$dddr = loginmodulesdetails_frommaster($authconn,$companyid,$subcompanyid,$projectid,"moduleid",$mdid);
$groupid ="";
if(count($dddr)>0){
  $groupid = $dddr[0]['groupid'];
}

?>
<div class="<?php echo isset($_GET['dz'])?'container-main':'container'; ?>">
  <!-- Header -->
  <div class="text-center mb-4 <?php echo isset($_GET['dz'])?'d-none':''; ?>">
    <h2 class="fw-bold">New Vendor Details</h2>
  </div>

  <div class="row <?php echo isset($_GET['dz'])?'d-none':''; ?>">
    <div class="col-md-12">
      <button class="btn btn-sm btn-deepblue m-2 float-right" onclick="location.reload();">Refresh</button>
    </div>
  </div>
  <div class="card">
    <div class="card-body">
      <form id="vendorForm" class="container py-3" method="post" action="#" novalidate>
        <div class="row g-3">
          <div class="col-md-6">
            <label for="VendorName" class="form-label">Supplyer Name of Seed <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="VendorName" name="VendorName" required>
            <div class="invalid-feedback">Vendor name is required.</div>
          </div>
          <div class="col-md-6">
            <label for="ContactPerson" class="form-label">Contact Person</label>
            <input type="text" class="form-control" id="ContactPerson" name="ContactPerson">
          </div>
        </div>
        <div class="row g-3">
          <div class="col-md-4">
            <label for="Email" class="form-label">Email</label>
            <input type="email" class="form-control" id="Email" name="Email" placeholder="name@company.com">
          </div>

          <div class="col-md-4">
            <label for="Phone" class="form-label">Phone</label>
            <input type="tel" class="form-control" id="Phone" name="Phone" minlength="7" maxlength="20"  onkeypress="return isNumberKey(event)" required>
          </div>

          <div class="col-md-4">
            <label for="AlternatePhone" class="form-label">Alternate Phone</label>
            <input type="tel" class="form-control" id="AlternatePhone" name="AlternatePhone" minlength="7" maxlength="20"  onkeypress="return isNumberKey(event)">
          </div>
        </div>
        <div class="row g-3">
          <div class="col-12">
            <label for="Address" class="form-label">Address</label>
            <textarea class="form-control" id="Address" name="Address" rows="2"></textarea>
          </div>
        </div>
        <div class="row g-3">
          <div class="col-md-3">
            <label for="City" class="form-label">City</label>
            <input type="text" class="form-control" id="City" name="City">
          </div>

          <div class="col-md-3">
            <label for="State" class="form-label">State</label>
            <input type="text" class="form-control" id="State" name="State">
          </div>

          <div class="col-md-3">
            <label for="Pincode" class="form-label">Pincode</label>
            <input type="text" class="form-control" id="Pincode" name="Pincode" maxlength="15"  onkeypress="return isNumberKey(event)">
          </div>

          <div class="col-md-3">
            <label for="Country" class="form-label">Country</label>
            <input type="text" class="form-control" id="Country" name="Country" value="India">
          </div>
        </div>
        <div class="row g-3">
          <div class="col-md-6">
            <label for="GSTNo" class="form-label">GST No</label>
            <input type="text" class="form-control" id="GSTNo" name="GSTNo" maxlength="30" placeholder="27ABCDE1234F1Z5" required>
          </div>

          <div class="col-md-6">
            <label for="PANNo" class="form-label">PAN No</label>
            <input type="text" class="form-control" id="PANNo" name="PANNo" maxlength="20" placeholder="ABCDE1234F" required>
          </div>
        </div>
        <div class="row g-3">
          <div class="col-md-4">
            <label for="BankName" class="form-label">Bank Name</label>
            <input type="text" class="form-control" id="BankName" name="BankName">
          </div>

          <div class="col-md-4">
            <label for="AccountNo" class="form-label">Account No</label>
            <input type="text" class="form-control" id="AccountNo" name="AccountNo" maxlength="50">
          </div>

          <div class="col-md-4">
            <label for="IFSCCode" class="form-label">IFSC Code</label>
            <input type="text" class="form-control" id="IFSCCode" name="IFSCCode" maxlength="20" placeholder="HDFC0001234">
          </div>
        </div>
        <div class="row mt-4">
          <div class="col-md-12 text-center">
            <!-- Hidden fields -->
            <input type="hidden" name="VendorID" id="VendorID" value=""> <!-- set value for update -->
            <input type="hidden" name="ProjectID" id="ProjectID" value="<?php echo $projectid; ?>">
            <input type="hidden" name="loginid" id="ProjectID" value="<?php echo $loginid; ?>">
            <button type="submit" class="btn btn-primary px-4" id="saveBtn">Save Vendor Details</button>
            <button type="reset" class="btn btn-outline-secondary ms-2">Reset</button>
          </div>
        </div>
      </form>
    </div>
  </div>  
</div>
<?php 
if(isset($_GET['dz'])){
}else{
  include("../footer.php");
}
?>
<script>
$(document).ready(function () {
    $("#vendorForm").on("submit", function (e) {
        e.preventDefault(); // stop default form submit
        $.ajax({
          url: "modal/vendorModal.php?action=saveupdatevendordetails",
            type: "POST",
            data: $(this).serialize(), // send all form fields
            dataType: "json",
            success: function (response) {
                if (response.status === "success") {
                    alertmessage("success","Vendor saved successfully. VendorID: " + response.VendorID);
                    $("#vendorForm")[0].reset();
                    // If you want to keep VendorID for updates, uncomment this
                    // $("#VendorID").val(response.VendorID);

                } else {
                    alertmessage("warning","Error: " + response.message);
                }
            },
            error: function (xhr, status, error) {
                alert("AJAX Error: " + error);
            }
        });
    });
});
</script>