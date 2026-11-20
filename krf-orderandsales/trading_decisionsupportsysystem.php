<?php
date_default_timezone_set('Asia/Kolkata');
require('../header.php');
include('functions/orders_functions.php');
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
  <div class="row">
    <div class="col-md-12">
      <a href="grafting_allocation_form.php?k=<?php echo $key; ?>&grpid=<?php echo $groupid; ?>&mdid=<?php echo $mdid; ?>" class="btn btn-sm btn-deepblue m-2 float-right">Grafting Allocation</a>
      <button class="btn btn-sm btn-deepblue m-2 float-right" onclick="location.reload();">Refresh</button>
    </div>
  </div>
  <div class="card mt-1">
    <div class="card-body">
      <div class="row">
        <div class="col-md-12 text-center">
          <h4>Decision Support System</h4>
        </div>
      </div>
      <div class="row mb-3 mt-4">
          <div class="col-md-6"></div>
          <div class="col-md-6">
              <label for="graftingdate" class="form-label">Date <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="graftingdate" name="graftingdate" value="<?php echo date('Y-m-d'); ?>" />
          </div>
      </div>
      <div class="row">
        <div class="col-md-12 mt-1">
          <div class="table-responsive">
            <table class="table table-mobile table-bordered" id="dssTable">
              <thead class="table-light">
                <tr>
                  <th data-label="Order">Order</th>
                  <th data-label="Delivery Date">Delivery Date</th>
                  <th data-label="Order Quantity">Order Quantity</th>
                  <th data-label="Required/Excess">Required/Excess</th>
                  <th data-label="Action">Action</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td colspan="5" style="text-align: center;">No data available</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
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

</script>