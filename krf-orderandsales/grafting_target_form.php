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
<div class="container-main">
  <div class="row <?php echo isset($_GET['dz'])?'d-none':'' ?>">
    <div class="col-md-4">

    </div>
    <div class="col-md-4 text-center">
      <h4>Grafting Target Details</h4>
    </div>
    <div class="col-md-4">
      <button class="btn btn-sm btn-deepblue m-2 float-right" onclick="location.reload();">Refresh</a>
    </div>
  </div>
  <div class="card mt-1">
    <div class="card-header">
      <button class="button btn-deepblue pull-right" id="openNewTargeModal"><i class="fa fa-plus-square"></i> New Target</button>
      <h4>Grafting Target Details</h4>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-4">
          <label>Target Month</label>
          <select class="form-control" id="targetmonthlist"></select>
        </div>
        <div class="col-md-4"></div>
        <div class="col-md-4">
          <label>Filter</label>
          <input type="text" id='gftrggrftinput' oninput="filterTable('#gftrggrftinput', '#tagetDetailstbl');" class="form-control" placeholder="Type for Searching ......">
        </div>
      </div>
      <div class="row mt-2">
        <div class="col-md-12">
          <div class="table-responsive">
            <table class="table table-bordered table-striped" id="tagetDetailstbl">
              <thead>
                <tr>
                  <th>Date</th>
                  <th class="d-none">Product Name</th>
                  <th >Location</th>
                  <th class="d-none">Plants Per Tray</th>
                  <th>Target Tray</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="GraftingTargetModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="GraftingTargetModalheader">New Grafting Target</h5>
        <button type="button" class="close" onclick="$('#GraftingTargetModal').modal('hide')">&times;</button>
      </div>
      <div class="modal-body" id="GraftingTargetModalContent">
        <form id="dailyGraftingTargetForm" method="POST" enctype="multipart/form-data">
         <!-- commit by ajay 16-09-2026
           <div class="row mb-3">
              <div class="col-md-12">
                  <label for="productlist" class="form-label">Product Name <span class="text-danger">*</span></label>
                  <select class="form-control" id="productlist" name="productlist" required></select>
              </div>
          </div> -->

          <div class="row mb-3">
              <div class="col-md-12">
                  <label for="locationlist" class="form-label">
                      Location <span class="text-danger">*</span> 
                      <small class="text-muted">(Choose location, or leave empty for all locations)</small>
                  </label>
                  <select class="form-control" id="locationlist" name="locationlist">
                      <option value="">-- All Locations --</option>
                      <!-- dynamically populate -->
                  </select>
              </div>
          </div>
          
          <div class="row mb-3">
              <div class="col-md-6">
                  <label for="start_date" class="form-label"   >Target Date </label>
                  <label class="float-right"><input type="checkbox" id="multiDateSwitch" /> Set for multiple dates</label>
                  <input type="date" id="start_date" name="start_date" class="form-control" 
                           min="<?= date('Y-m-d'); ?>" required>
              </div>
              <div class="col-md-6 d-none" id="endDateRow">
                  <label for="end_date" class="form-label">End Date</label>
                  <input type="date" id="end_date" name="end_date" class="form-control" min="<?= date('Y-m-d'); ?>">
              </div>
          </div>
          <!-- commit by ajay 16-09-2026
          <div class="row mb-3">
              <div class="col-md-12">
                  <label for="trayid" class="form-label">Which tray used for grafting </label>
                  <select class="form-control" id="trayid" name="trayid"></select>
              </div>
          </div>
        
          <div class="row">
              <div class="col-md-12">
                  <div class="mb-3">
                      <label for="plantcountpertray" class="form-label">Plants(/Tray)</label>
                      <input type="text" onkeypress="return isNumberKey(event)" id="plantcountpertray" name="plantcountpertray" class="form-control" required min="0">
                  </div>
              </div>
          </div> -->
          <div class="row">
              <div class="col-md-12">
                  <div class="mb-3">
                      <label for="target_traycount" class="form-label">Target Tray</label>
                      <input type="text" onkeypress="return isNumberKey(event)" id="target_traycount" name="target_traycount" class="form-control" required min="0">
                  </div>
              </div>
          </div>

          <!-- Hidden Inputs -->
          <input type="hidden" name="companyid" value="<?php echo $companyid; ?>">
          <input type="hidden" name="subcompanyid" value="<?php echo $subcompanyid; ?>">
          <input type="hidden" name="projectid" value="<?php echo $projectid; ?>">
          <input type="hidden" name="loginid" value="<?php echo $loginid; ?>">
          <input type="hidden" id="product_sku" name="product_sku" value="">

          <div class="form-group mb-3 text-center">
              <button type="submit" id="updategraftingtargetdetailsbtn" class="btn btn-deepblue">Save Daily Grafting Target</button>
          </div>
        </form>
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
document.addEventListener("DOMContentLoaded", function(){
    const switchBtn = document.getElementById("multiDateSwitch");
    const endDateRow = document.getElementById("endDateRow");
    const endDateInput = document.getElementById("end_date");
    const startDateInput = document.getElementById("start_date");

    switchBtn.addEventListener("change", function(){
        if(this.checked){
            endDateRow.classList.remove("d-none");
            endDateInput.value = ""; // empty by default
        } else {
            endDateRow.classList.add("d-none");
            endDateInput.value = startDateInput; // reset if switched back
        }
    });
});

$(document).ready(function(){
  GetTrayDataWithOption('trayid');

  $(document).on("change", "#trayid", function(){
    $("#plantcountpertray").val($("#trayid option:selected").data("cavitycount"));
  });

  GraftingTargetMonthYearList('<?php echo date('Y'); ?>','<?php echo date('Y-m'); ?>','targetmonthlist');

  $(document).on("change", "#targetmonthlist", function(){
    GraftingDetailsWithMonthYear($("#targetmonthlist").val(),'tagetDetailstbl');
  }); 
$(document).on("change", "#start_date, #multiDateSwitch", function () {

    const startDate = $("#start_date").val();
    const isMultiDate = $("#multiDateSwitch").is(":checked");

    if (startDate && isMultiDate) {

        const date = new Date(startDate + "T00:00:00");
        date.setDate(date.getDate() + 1);

        const endDate =
            date.getFullYear() + "-" +
            String(date.getMonth() + 1).padStart(2, "0") + "-" +
            String(date.getDate()).padStart(2, "0");

        $("#end_date")
            .val(endDate)
            .attr("min", endDate);

    } else {
        $("#end_date").val("");
    }
});



  ProductionLocationMastercallback("all","all",function(res){
    var locationlist = '<option value="all">All Location</option>';
    $("#locationlist").empty();
    if(res.length > 0){
      for (var i = 0; i < res.length; i++) {
        locationlist += '<option value="' + res[i].LocationId + '">' + res[i].LocationName + '</option>';
      }
    }
    $("#locationlist").append(locationlist);
  });

  GetProductListFrommaster_callback(function(res){
    var prdlist = "";
    if(res.length>0){
      prdlist = '<option value="" selected disabled>-- Select Product --</option>'; // default option
      for (var i = 0; i < res.length; i++) {
        var rawName = res[i].ProductName || '';
        var displayName = rawName.replace(/^(NonGrafted|Non-Grafted)\s*[-:]?\s*/i, '');
        prdlist +='<option value="'+res[i].ProductID+'" data-productsku="'+res[i].sku+'">'+displayName+'</option>';
      }
      if(prdlist){
        $("#productlist").append(prdlist);
      }
    }
  });
  $(document).on("change", "#productlist", function(){
    $("#product_sku").val($("#productlist option:selected").data("productsku"));
  });  
  
  $("#dailyGraftingTargetForm").submit(function(e) { 
    e.preventDefault();
    $.ajax({
        url: "modal/grafting_modal.php?action=SaveUpdatedailyGraftingTarget",
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

  $(document).on("click", "#openNewTargeModal", function(){
    $("#GraftingTargetModal").modal("show");
  });
});
</script>
