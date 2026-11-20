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
<div class="<?php echo isset($_GET['dz']) ? 'container-main' : 'container'; ?>">

    <!-- Page Header -->
    <div class="text-center mb-4 <?php echo isset($_GET['dz']) ? 'd-none' : ''; ?>">
        <h3 class="fw-bold mb-1">New Polyhouse Details</h3>
        <small class="text-muted">Manage and maintain polyhouse information</small>
    </div>

    <!-- Action Buttons -->
    <div class="row mb-3 <?php echo isset($_GET['dz']) ? 'd-none' : ''; ?>">
        <div class="col-md-12 d-flex justify-content-between">
            <a href="../admin_dashboard.php?k=<?php echo $key; ?>&grpid=<?php echo $groupid; ?>&mdid=<?php echo $mdid; ?>"
               class="btn btn-sm btn-outline-primary">
                ← Back
            </a>
            <button class="btn btn-sm btn-outline-secondary" onclick="location.reload();">
                ⟳ Refresh
            </button>
        </div>
    </div>

    <!-- Form Card -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">

            <form id="polyhouseForm" method="post" novalidate>

                <!-- Section: Basic Info -->
                <h6 class="fw-semibold text-primary mb-3">Basic Information</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label">Polyhouse Code</label>
                        <input type="text" class="form-control" id="polyhouse_code" name="polyhouse_code"
                               placeholder="Auto / Manual">
                    </div>

                    <div class="col-md-5">
                        <label class="form-label">Polyhouse Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="polyhouse_name" name="polyhouse_name" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Location</label>
                        <input type="text" class="form-control" id="location" name="location">
                    </div>
                </div>

                <!-- Section: Configuration -->
                <h6 class="fw-semibold text-primary mb-3">Configuration</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label">Area (sqft)</label>
                        <input type="number" step="0.01" class="form-control" id="area_sqft" name="area_sqft">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Type</label>
                        <select class="form-select form-control" id="type" name="type">
                            <option value="Poly Film">Poly Film</option>
                            <option value="Net House">Net House</option>
                            <option value="Tunnel">Tunnel</option>
                            <option value="Shade House">Shade House</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Capacity (Trays)</label>
                        <input type="number" class="form-control" id="capacity" name="capacity">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-select form-control" id="status" name="status">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <!-- Section: Description -->
                <h6 class="fw-semibold text-primary mb-3">Additional Details</h6>
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>

                <!-- Hidden Fields -->
                <input type="hidden" id="polyhouseid" name="polyhouseid">
                <input type="hidden" id="ProjectID" name="ProjectID" value="<?php echo $projectid; ?>">
                <input type="hidden" name="loginid" value="<?php echo $loginid; ?>">

                <!-- Form Actions -->
                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <button type="reset" class="btn btn-outline-secondary">
                        Reset
                    </button>
                    <button type="submit" class="btn btn-primary px-4">
                        Save Polyhouse
                    </button>
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
<script type="text/javascript" src="<?php echo $prefix;?>krf-orderandsales/js/pjs_function.js"></script>

<script>
$(document).ready(function () {
  $(document).on("submit", "#polyhouseForm", function(e){
    e.preventDefault();
    $.ajax({
      url: "modal/polihouse_modal.php?action=saveupdatePolihouseDetails",
      type: "POST",
      data: $(this).serialize(),
      dataType: "json",
      success: function(res){
        if(res.status === "success"){
          alert("Polyhouse saved successfully!");
          $("#polyhouseForm")[0].reset();
        } else {
          alert("Error: " + res.message);
        }
      },
      error: function(){
        alert("Server error occurred while saving polyhouse.");
      }
    });
  });
});
</script>