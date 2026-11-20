<?php
date_default_timezone_set('Asia/Kolkata');
require('../../header.php');
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
 <!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

<!-- ✅ QR Code Scanner Library -->
<script src="https://unpkg.com/html5-qrcode@2.3.7/html5-qrcode.min.js"></script>
<style>
    #reader {
      width: 100%;
      max-width: 400px;
      margin: 20px auto;
      border: 2px solid #0d6efd;
      border-radius: 10px;
      padding: 10px;
    }
    #trayInfo {
      display: none;
    }
  </style>
<div class="container-main">
  <div class="row <?php echo isset($_GET['dz'])?'d-none':'' ?>">
    <h3 class="mb-4 text-center">Allocation Against invoice</h3>
    <div class="col-md-12">
      <a href="../../admin_dashboard.php?k=<?php echo $key; ?>&grpid=<?php echo $groupid; ?>&mdid=<?php echo $mdid; ?>" class="btn btn-sm btn-deepblue m-2">Back</a>
      <button class="btn btn-sm btn-deepblue m-2 float-right" onclick="location.reload();">Refresh</button>
    </div>
  </div>
  <div class="card mt-1">
    <div class="card-body">
      <div class="row mb-3 mt-4 mainheading"> 
          <div class="col-md-6">
            <label for="clientid" class="form-label">Client Name <span class="text-danger">*</span></label>
            <select class="form-control" id="clientid" name="clientid" required></select>
          </div>
          <div class="col-md-6">
            <label for="invoiceno" class="form-label">Invoice No <span class="text-danger">*</span></label>
            <select class="form-control" id="invoiceno" name="invoiceno" required></select>
          </div>
      </div> 
      <div class="row mainPrdetails" id="mainPrdetails">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <button type="button" class="btn btn-success" id="btnmoveprev">
                &lt;&lt; Prev
            </button>
            <span id ="lblcnt" style="color: blue;"> </span>
            <button type="button" class="btn btn-success" id="btnmovenext">
                Next &gt;&gt;
            </button>
        </div>
        <div class="col-12 mt-2">
            <div class="row divproductdetails" id="divproductdetails"></div>
        </div>
      </div>      
      <div class="scannerdiv d-none">    
        <div class="row" >
          <div class="col-md-12 text-center"> 
            <div id="reader"></div>
            <div class="mt-2">
              <button id="startScan" class="btn btn-primary">Start Scanner</button>
              <button id="stopScan" class="btn btn-danger" style="display:none;">Stop Scanner</button> 
            </div>
          </div>

        </div>
        <div class="col-md-12 mt-2">
          <label for="manualQrCode" class="form-label">Enter QR Code Manually</label>
          <div class="input-group mt-2">
            <input type="text" id="manualQrCode" class="form-control" placeholder="Enter QR Code">
            <button id="fetchManual" class="btn btn-success">Fetch Data</button>
          </div>
        </div> 
      </div>  
      <div id="noResult" class="alert alert-warning mt-4 text-center" style="display:none;"></div> 
      <div class ="mt-2 d-none" id="allocatedivcal"> </div>      
    </div> 
    <details id="truckdetails">
        <summary>Truck Details</summary>

        <!-- First Row: Truck No and Driver Name -->
        <div class="row mt-2">
          <div class="col-12 col-md-6 mb-2">
            <label for="truckno" class="form-label">
              Truck No <span class="text-danger">*</span>
            </label>
            <input type="text" class="form-control" id="truckno" required>
          </div>
          <div class="col-12 col-md-6 mb-2">
            <label for="drivername" class="form-label">
              Driver Name <span class="text-danger">*</span>
            </label>
            <input type="text" class="form-control" id="drivername" required>
          </div>
        </div>

        <!-- Second Row: Driver Mobile No -->
        <div class="row mt-2">
          <div class="col-12 col-md-6 mb-2">
            <label for="drivermobileno" class="form-label">
              Driver Mobile No <span class="text-danger">*</span>
            </label>
            <input type="text" class="form-control" id="drivermobileno"   maxlength="10" inputmode="numeric"  placeholder="Enter mobile number" required>
          </div>
        </div>

        <!-- Third Row: Next Button on the Right -->
        <div class="row mt-2">
          <div class="col-12 text-end">
            <button type="button" class="btn btn-success" id="btnnext">
              Next
            </button>
          </div>
        </div>
      </details>
    <details id="allocateddata">
      <summary>Allocate Data</summary>
        <div class="row tray_barcodedetails" id="tray_barcodedetails"> 
          <div class="col-md-12">
            <div class="card shadow-sm">
              <div class="card-body p-2">  
                  <div class="table-responsive"> 
                      <table class="table table-bordered" id="tblbarcodedetail">
                          <colgroup>  
                              <col style="width:15px;">
                              <col style="width:30px;"> 
                              <col style="width:35px;">
                          </colgroup>
                          <thead class="table-light">                             
                              <tr> 
                                <th class="d-none" style="text-align: center;"></th> 
                                <th style="text-align: center;">QR Code No</th> 
                                <th style="text-align: center;">Product Name</th> 
                                <th style="text-align: center;"> Qty</th>  
                              </tr>
                          </thead>
                          <tbody></tbody>
                          <tfoot  class="d-none"></tfoot>
                      </table>
                  </div>
              </div>
            </div>        
          </div>
        </div>
      </details> 
    <!--  -->
  </div>


</div>
<div class="modal" id="msgModal" tabindex="-1" role="dialog" aria-labelledby="partialQtyPermissionTitle" data-backdrop="static" data-keyboard="false"> 
    <div class="modal-dialog modal-lg modal-dialog-scrollable" style="max-width: 50%;">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="msgTitle">
                    Permission Required
                </h5>                     
            </div>
            <div class="modal-body">
                <p id="textMessage" class="mb-0"></p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-success mx-2" id="btnyes">
                    <span class="btn-text">Proceed Anyway</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                </button>
                <button type="button" class="btn btn-info mx-2" id="btnno">
                    Seek Permission
                </button>
                <button type="button" class="btn btn-secondary mx-2" data-dismiss="modal">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>
<?php
  include('../../footer.php');
?>
<script>
   let qrReader; 
   window.serverCurrentDate = "<?= date('Y-m-d'); ?>";
   let invoicearray=[];
   let   arrayindex=0;
</script>
<script type="text/javascript" src="js/control.js"></script>
<script type="text/javascript" src="js/function.js"></script>

<script>
/*$(document).on("click", ".openupdatestatus", function () {
  $("#updateTrayQc_modal").modal("show");
  let polyhouseplantid = $(this).data('polyhouseplantid');
  $("#saveQcStatusBtn").data("polyhouseplantid", polyhouseplantid);
});*/

$(document).ready(function() {
   getcustomer_invoice() 
  // initScanner();
});
</script>
