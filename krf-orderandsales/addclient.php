<?php
$posno ="";
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

// get provisiotn order details
$psono = "";
$psodate = "";
$clientname = "";
$mobileno = "";
$emailid = "";
$address = "";
$deliverydate = "";
$psoamount = "";
$districtname ="";
$statename ="";
if(isset($_GET['psono'])){
  $psono = $_GET['psono'];
  $posnoinfo= getProvisionalSalesOrders($krfconn, $companyid, $subcompanyid, $projectid, "psono", $psono);
  $psodate = date('d-M-Y', strtotime($posnoinfo[0]['created_at']));
  $clientname = $posnoinfo[0]['clientname'];
  $mobileno = $posnoinfo[0]['mobileno'];
  $emailid = $posnoinfo[0]['emailid'];
  $address = $posnoinfo[0]['address'];
  $deliverydate = $posnoinfo[0]['expecteddelivery'];
  $psoamount =  $posnoinfo[0]['total_amount'];
  $districtname = $posnoinfo[0]['districtname'];
  $statename = $posnoinfo[0]['statename'];
}
?>
<div id="paymentadvicemanauladiv" class="<?php echo isset($_GET['dz'])?'d-none':'' ?>">
  <div class="main-container">
    <div class="row p-2">
      <div class="col-md-12 text-center">
        <h5 class="uppercase bold">Add New Client</h5>
      </div>
    </div>
  </div>
</div>
<div class="container">
  <div class="row <?php echo isset($_GET['dz'])?'d-none':''; ?>">
    <div class="col-md-12">

      <button class="btn btn-sm btn-deepblue m-2 float-right" onclick="location.reload();">Refresh</a>
    </div>
  </div>
  <div class="card <?php echo $psono!=""?'':'d-none'; ?>">
    <div class="card-header">
      <h4>Provisional Oreder Details</h4>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6 mb-3">
          <p class="mb-1">Provisional Order No: <strong><?php echo $psono; ?></strong></p>
          <p class="mb-1">Provisional Order Date: <strong><?php echo $psodate; ?></strong></p>
        </div>
        <div class="col-md-6 mb-3">
          <p class="mb-1">Client Name: <strong><?php echo $clientname; ?></strong></p>
          <p class="mb-1">Client Contact: <strong><?php echo $mobileno; ?></strong></p>
        </div>
      </div>
    </div>
  </div>

  <form id="addClientForm" method="POST">
  <!-- Basic Information -->
  <fieldset class="container border mt-3">
    <legend style="width:auto;">Basic Information</legend>
    
    <div class="form-group mb-3">
      <label class="form-label">Client Name<span class="required">*</span></label>
      <input type="text" name="client_name" id="client_name" placeholder="Enter Name" autocomplete="off" class="form-control" value="<?php echo $clientname; ?>" required>
    </div>
    <div class="form-group mb-3">
      <label class="form-label">Client's Phones</label>
      <input type="text" name="phone" id="phone" value="<?php echo $mobileno; ?>" class="form-control" placeholder="Enter Phone Numbers" maxlength="12" autocomplete="off">
    </div>
    <div class="form-group mb-3">
      <label class="form-label">Client's Email</label>
      <input type="email" name="email" id="email" value="<?php echo $emailid; ?>" class="form-control" placeholder="Enter Email ID" autocomplete="off">
    </div>
    <div class="form-group mb-3">
      <label class="form-label">Invoicing Address <span class="required">*</span></label>
      <input type="text" name="invoicing_address" id="invoicing_address" value="<?php echo $address; ?>" placeholder="Enter Invoicing Address" autocomplete="off" class="form-control" required>
    </div>
    <div class="form-group mb-3">
      <label class="form-label">District Name</label>
      <input type="text" name="districtname" id="districtname" value="<?php echo $districtname; ?>" placeholder="Enter district name of  client address" autocomplete="off" class="form-control" required>
    </div>
    
    <div class="form-group mb-3">
      <label class="form-label">State Name</label>
      <input type="text" name="statename" id="statename" value="<?php echo $statename; ?>" placeholder="Enter state name of client address" autocomplete="off" class="form-control" required>
    </div>
  </fieldset>

  <div class="mt-3">
    <input type="hidden" name="companyid" value="<?php echo $companyid; ?>">
    <input type="hidden" name="subcompanyid" value="<?php echo $subcompanyid; ?>">
    <input type="hidden" name="projectid" value="<?php echo $projectid; ?>">
    <div class="col-lg-12 mb-3 text-center">
      <button type="submit" class="btn btn-primary">Save New Client Details</button>
    </div>
  </div>
</form>
</div>
<?php
if(isset($_GET['dz'])){

}else{
  include('../footer.php');
}
?>
<script>
  // Validate phone numbers
  function isNumberKey(evt) {
    var charCode = evt.which ? evt.which : evt.keyCode;
    return charCode >= 48 && charCode <= 57 || charCode <= 31;
  }

  document.getElementById('addClientForm').addEventListener('submit', function(e) {
    e.preventDefault();

    let clientName = document.getElementById('client_name').value.trim();
    let invoicingAddress = document.getElementById('invoicing_address').value.trim();
    let email = document.getElementById('email').value.trim();
    let phone = document.getElementById('phone').value.trim();

    if(clientName === '') { alert('Client Name is required'); return; }
    if(invoicingAddress === '') { alert('Invoicing Address is required'); return; }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if(email && !emailRegex.test(email)) { alert('Invalid Client Email'); return; }
    if(phone && !/^\d+$/.test(phone)) { alert('Phone number must contain only digits'); return; }

    // AJAX to server to check duplicate and insert
    const formData = new FormData(this);
    fetch('modal/client_modal.php?action=newclientsavetodb', { method: 'POST', body: formData })
      .then(res => res.json())
      .then(data => {
        alert(data[0].message);
        if(data[0].status === 'Success') {
          this.reset();
        }
      })
      .catch(err => alert('Error: ' + err));
  });
</script>