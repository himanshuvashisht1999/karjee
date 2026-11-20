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
<div class="container">
  <div class="row <?php echo isset($_GET['dz'])?'d-none':'' ?>">
    <div class="col-md-12">
      <button class="btn btn-sm btn-deepblue m-2 float-right" onclick="location.reload();">Refresh</a>
    </div>
  </div>
  <div class="card mt-2">
    <div class="card-header">
      <h4>Root Stock Details</h4>
    </div>
    <div class="card-body">
      <form id="newRootStockFrom" method="POST" enctype="multipart/form-data">
        <!-- Root Stock Name -->
        <div class="form-group mb-3">
          <label for="RootStockName" class="form-label">Root Stock Name</label>
          <input type="text" class="form-control" id="RootStockName" name="RootStockName" oninput="GetSuggestedRootStockSKU(this.value, 'RootStocksku')" />
        </div>

        <div class="form-group mb-3">
          <label for="RootStocksku" class="form-label">Suggested SKU</label>
          <input type="text" class="form-control" id="RootStocksku" name="RootStocksku" />
        </div>
        <input type="hidden" name="companyid" value="<?php echo $companyid; ?>">
        <input type="hidden" name="subcompanyid" value="<?php echo $subcompanyid; ?>">
        <input type="hidden" name="projectid" value="<?php echo $projectid; ?>">
        <input type="hidden" name="loginid" value="<?php echo $loginid; ?>">
        <input type="hidden" name="RootStockId" value="" />
        <div class="form-group mb-3 text-center">
          <button type="submit" class="btn btn-deepblue">Save Root Stock Details</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="pdrotherModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="pdrotherModalheader"></h5>
        <button type="button" class="close" onclick="$('#pdrotherModal').modal('hide')">&times;</button>
      </div>
      <div class="modal-body" id="pdrotherModalContent">
        <!-- Dynamic form content via AJAX -->
      </div>
    </div>
  </div>
</div>
<?php
if(isset($_GET['dz'])){

}else{
  include('../footer.php');
}
?>
<script type="text/javascript" src="<?php echo $prefix;?>krf-orderandsales/js/pjs_function.js"></script>

<script>
$(document).ready(function(){
  $("#newRootStockFrom").submit(function(e) {
    e.preventDefault();
    $.ajax({
        url: "modal/rootstock_modal.php?action=saveupdateRootstockdetails",
        type: "POST",
        data: $(this).serialize(),
        dataType: "json",
        success: function(response) {
          // console.log(response);
          if(response.status === 'success') {
            alertmessage("success",response.message);
            location.reload();
          } else {
              alertmessage("error","Error: " + response.message);
          }
        }
    });
  });
});
</script>
