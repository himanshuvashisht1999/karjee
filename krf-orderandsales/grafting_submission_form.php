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
<!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css"> -->
<style>
.print-area {
  width: 210mm; /* A4 width */
  min-height: 297mm; /* A4 height */
  padding: 1mm;
  margin: auto;
  background: #fff;
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-start;
  align-items: flex-start;
  box-sizing: border-box;
}



.barcode-box {
  border: 1px solid #ccc;
  margin: 4px 2px;
  display: inline-block;
  vertical-align: top;
  box-sizing: border-box;
  overflow: hidden;

  text-align: left;
  padding: 4px;
  height: 80px;
  /*display: flex;*/
  flex-direction: column;
  align-items: center;
  justify-content: space-between;
  background: #fff;
  font-size: 12px;
  page-break-inside: avoid;
  cursor: move;
  margin: 2px !important;/* Reset default margins */
}

.barcode-box svg {
  min-width: 100% !important;            /* Fill the box width */
  height: auto !important;
}

.barcode-box label {
  margin: 2px 0;
  font-size: 12px;
}

@media print {
  body * {
    visibility: hidden;
  }
  .print-area, .print-area * {
    visibility: visible;
  }
  .print-area {
    position: absolute;
    left: 0;
    top: 0;
    background: #fff;
  }
}
</style>
<div class="container-main">
  <div class="row <?php echo isset($_GET['dz'])?'d-none':'' ?>">
    <div class="col-md-12">
      <a href="grafting_allocation_form.php?k=<?php echo $key; ?>&grpid=<?php echo $groupid; ?>&mdid=<?php echo $mdid; ?>" class="btn btn-sm btn-deepblue m-2 float-right">Grafting Allocation</a>
      <button class="btn btn-sm btn-deepblue m-2 float-right" onclick="location.reload();">Refresh</a>
    </div>
  </div>
  <div class="card mt-1">
    <div class="card-body">
      <!-- <form id="submissionofdailyGraftingForm" method="POST" enctype="multipart/form-data"> -->
          <div class="row">
            <div class="col-md-12 text-center">
              <h4>End-of-Day Grafting Submission</h4>
            </div>
          </div>
          <div class="row mb-3 mt-4">
              <div class="col-md-6">
                  <label for="graftingdate" class="form-label">Grafting Date <span class="text-danger">*</span></label>
                  <input type="date" class="form-control" id="graftingdate" name="graftingdate" value="<?php echo date('Y-m-d'); ?>" />
              </div>
              <div class="col-md-6">
                  <label for="locationlist" class="form-label">Location <span class="text-danger">*</span></label>
                  <select class="form-control" id="locationlist" name="locationid" required></select>
              </div>
          </div>
          <div class="row">
            <div class="col-md-12 mt-1">
              <button id="refreshPendingGraftingdata" class="refreshPendingGraftingdata btn btn-primary float-end mb-2 d-none"> Refresh Data</button>
              <div class="table-responsive mt-2">
                <table class="table table-mobile table-bordered" id="submissionofdailyGraftingTable">
                  <thead class="table-light">
                    <tr>
                      <th style="width:50px;">Select</th>
                      <th data-label="Labour Name">Labour Name</th>
                      <th data-label="Product Name">Product Name</th>
                      <th data-label="Allocation Time" style="width:200px;">Allocation Date &Time</th>
                      <th data-label="Grafted Count">Grafted Tray Count</th>
                      <th data-label="Target Trays" style="width:200px;">Target Trays With Name</th>
                      <th data-label="Print QRCode" style="width:100px; display: none;">Print QRCode</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td colspan="7" style="text-align: center;">No data available</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <!-- Hidden Inputs -->
          <input type="hidden" name="companyid" value="<?php echo $companyid; ?>">
          <input type="hidden" name="subcompanyid" value="<?php echo $subcompanyid; ?>">
          <input type="hidden" name="projectid" value="<?php echo $projectid; ?>">
          <input type="hidden" name="loginid" value="<?php echo $loginid; ?>">
          <div class="row">
            <div class="col-md-12">
              <svg id="svgid"></svg>
            </div>
          </div>
          <!-- <div class="form-group mb-3 text-center">
              <button type="submit" id="updategraftingdetailsbtn" class="btn btn-deepblue d-none">Save Daily Grafting Details</button>
          </div> -->
      <!-- </form> -->
    </div>
  </div>
</div>

<div class="modal fade" id="submissionofgraftingModal">
  <div class="modal-dialog modal-lg modal-dialog-scrollable" style="min-width: 90%;">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="submissionofgraftingModalheader">Submission of Grafting Details</h5>
        <button type="button" class="close" onclick="$('#submissionofgraftingModal').modal('hide')">&times;</button>
      </div>
      <div class="modal-body">
        <div id="productdetailsdiv"></div>
        <div class="row">
          <div class='col-md-12 mt-2 d-none'>
            <label>Source Type of Planting</label>
            <select id='plantingsourcetype' class='form-control'></select>
          </div>
        </div>
        <div class="row">
          <div class="col-md-12">
            <span class="badge badge-warning printbarcodebtn float-end" style="cursor:pointer; margin:2px;" data-printtarget="$('#graftedtraycount').val()">Print Barcode</span>
          </div>
        </div>
        <div class="row mt-2">
          <div class='col-md-4'>
            <label>Polihour Name</label>
            <button class='btn btn-sm openpolihousemodal float-end'><i class='fa fa-plus'></i></button>
            <button class='btn btn-sm float-end' onclick='GetPolihouseDetailsList("","","","polihouseid")'><i class='fa fa-sync'></i></button>
            <select id='polihouseid' class='form-control'></select>
          </div>
          <div class='col-md-4'>
            <label>How many tray are grafted?</label>
            <input type='text'  onkeypress="return isNumberKey(event)" id='graftedtraycount' autocomplete="off" class='form-control' placeholder='Trays count like 10' />
          </div>
          <div class="col-md-4">
            <label class="form-label" for="activestatus">Status</label>
            <select class="form-select" id="activestatus" name="activestatus">
              <option value="" selected disabled>Select</option>
              <option value="InProcess">InProcess To Day</option>
              <option value="Completed">Completed To Day</option>
            </select>
          </div>
        </div>
        <div class="row d-none">
          <div class='col-md-12 mt-2'>
            <div class="table-responsive">
              <table class="table table-bordered table-mobile" id="graftingtraydetails">
                <thead>
                  <tr>
                    <th>Tray QR Code</th>
                    <th>Bed Number</th>
                    <th>Rack Number</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td class="barcodeno_td">
                      <input type="text" class="form-control barcodeno" placeholder="Enter barcode">
                    </td>
                    <td class="bednumber_td">
                      <input type="text" class="form-control bednumber" placeholder="Bed number">
                    </td>
                    <td class="racknumber_td">
                      <input type="text" class="form-control racknumber" placeholder="Rack number">
                    </td>
                    <td>
                      <button type="button" class="btn btn-sm btn-success m-1 addbarcodeRow">
                        <i class="fa fa-plus-square"></i>
                      </button>
                      <button type="button" class="btn btn-sm btn-danger m-1 removebarcodeRow">
                        <i class="fa fa-trash"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>            
          </div>
        </div>
        <div class="row mt-4">
          <div class='col-md-12 mt-4 text-center'>
            <button id='savesubmissionofgrafting' onclick="UpdateSubmissionGraftingDeails('savesubmissionofgrafting', 'graftedtraycount', 'polihouseid','activestatus')" class='btn btn-success'>Update Grafting Submission</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="polyhouseModal" tabindex="-1" role="dialog" aria-labelledby="polyhouseModalheader" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="polyhouseModalheader">Polyhouse Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <form id="polyhouseForm" class="container py-3" method="post" action="#" novalidate>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="polyhouselocationid">Location of Polyhouse</label>
              <select class="form-select" id="polyhouselocationid" name="polyhouselocationid"></select>
            </div>
            <!-- Polyhouse Name -->
            <div class="col-md-6">
              <label class="form-label" for="polyhouse_name">Polyhouse Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="polyhouse_name" name="polyhouse_name" placeholder="Enter polyhouse name" 
                required>
            </div>

            <!-- Location -->
            <div class="col-md-12">
              <label class="form-label" for="physicallocation">Physical Location of the Polyhouse</label>
              <input type="text" class="form-control" id="physicallocation" name="physicallocation" placeholder="Enter physical location or plot name">
            </div>

            <!-- Area -->
            <div class="col-md-6">
              <label class="form-label" for="area_sqft">Area (sqft)</label>
              <input  type="number" step="0.01" class="form-control" id="area_sqft" name="area_sqft" placeholder="e.g. 2500">
            </div>

            <!-- Type -->
            <div class="col-md-6">
              <label class="form-label" for="type">Type</label>
              <select class="form-select" id="type" name="type">
                <option value="Poly Film">Poly Film</option>
                <option value="Net House">Net House</option>
                <option value="Tunnel">Tunnel</option>
                <option value="Shade House">Shade House</option>
              </select>
            </div>

             <!-- Bed & Rack Count -->
            <div class="col-md-6">
              <label for="total_bed_count" class="form-label">Total Beds</label>
              <input type="number" class="form-control" id="total_bed_count" name="total_bed_count" value="0" min="0">
            </div>

            <div class="col-md-6">
              <label for="total_rack_count" class="form-label">Total Racks</label>
              <input type="number" class="form-control" id="total_rack_count" name="total_rack_count" value="0" min="0">
            </div>

            

            <!-- Capacity -->
            <div class="col-md-3 d-none">
              <label class="form-label" for="capacity">Capacity</label>
              <input type="number" class="form-control" id="capacity" name="capacity" placeholder="e.g. 500 plants">
            </div>

            <!-- Status (Hidden) -->
            <div class="col-md-3">
              <label class="form-label" for="status">Status</label>
              <select class="form-select" id="status" name="status">
                <option value="InProcess">InProcess To Day</option>
                <option value="Completed">Completed To Day</option>
              </select>
            </div>

            <!-- Description -->
            <div class="col-12">
              <label class="form-label" for="description">Description</label>
              <textarea 
                class="form-control" 
                id="description" 
                name="description" 
                rows="3"
                placeholder="Enter additional details about this polyhouse"></textarea>
            </div>
          </div>

          <!-- Buttons -->
          <div class="row mt-4">
            <div class="col-md-12 text-center">
              <!-- Hidden Fields -->
              <input type="hidden" id="polyhouseid" name="polyhouseid">
              <input type="hidden" name="ProjectID" id="ProjectID" value="<?php echo $projectid; ?>">
              <input type="hidden" name="loginid" value="<?php echo $loginid; ?>">

              <button type="submit" class="btn btn-primary px-4">Save Polyhouse Details</button>
              <button type="reset" class="btn btn-outline-secondary ms-2">Reset</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="prdordotherModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="prdordotherModalheader"></h5>
        <button type="button" class="close" onclick="$('#prdordotherModal').modal('hide')">&times;</button>
      </div>
      <div class="modal-body" id="prdordotherModalContent">
        <!-- Dynamic form content via AJAX -->
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="askquestion_modal" tabindex="-2" role="dialog" aria-labelledby="askquestionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable" style="min-width: 90%;">
    <div class="modal-content">
      <!-- Header -->
      <div class="modal-header">
        <h5 class="modal-title" id="askquestionModalLabel">QR Code Printing Settings</h5>
        <button type="button" class="close" onclick="$('#askquestion_modal').modal('hide')">&times;</button>
      </div>
      <!-- Body -->
      <div class="modal-body">
        
        <div class="row g-3 px-4 py-3">
          <div class="col-md-6">
            <label for="barcodelengthinsheet" class="form-label fw-semibold">
              Total QR Code labels per sheet
            </label>
            <input type="number" id="barcodelengthinsheet" class="form-control" placeholder="e.g., 30" value="24" min="1">
          </div>

          <div class="col-md-6">
            <label for="barcodelengthinrow" class="form-label fw-semibold">
              Number of QR Code labels per row
            </label>
            <input type="number" id="barcodelengthinrow" class="form-control" placeholder="e.g., 5" value="4" min="1">
          </div>
        </div>

        <div class="row g-3 px-4">
          <div class="col-md-6">
            <label for="barcodeCount" class="form-label fw-semibold">
              Number of QR Code to print
            </label>
            <input type="number" id="barcodeCount" class="form-control" placeholder="Enter total number" min="1">
          </div>

          <div class="col-md-6">
            <label for="startBox" class="form-label fw-semibold">
              Starting box number for printing
            </label>
            <input type="number" id="startBox" class="form-control" placeholder="e.g., 1" value="1" min="1">
          </div>
        </div>
        <div class="text-center mt-4 mb-2">
          <button class="btn btn-primary px-4" id="printbarcodefrom">
            <i class="bi bi-printer"></i> Proceed to Print
          </button>
        </div>

      </div>      
    </div>
  </div>
</div>

<div class="modal fade" id="viewbarcodelistforadjt_modal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" style="max-width: 98%;">
    <div class="modal-content">
      <div class="modal-header bg-success">
        <h5 class="modal-title">QR Code For Print</h5>
        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-12">
            <div id="ViewBarcodeDiv" class="print-area" style="width:100%; text-align:center;"></div>
          </div>
          <div class="col-md-12 mt-4 text-center">
            <button class="btn btn-warning" id="generatePdfBtnXX">Download QR Code PDF</button>
            <button class="btn btn-success" onclick="window.print()">Print QR Code Sheet</button>
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
<!-- Bootstrap JS + dependencies -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>

  <!-- QR Scanner (html5-qrcode) -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
$(document).ready(function(){  
  ProductionLocationMastercallback("all","all",function(res){
    var locationlist = '<option value="">-- Select Location --</option>'; // default option
    $("#locationlist").empty();
    $("#polyhouselocationid").empty();    
    if(res.length > 0){
      for (var i = 0; i < res.length; i++) {
        locationlist += '<option value="' + res[i].LocationId + '">' + res[i].LocationName + '</option>';
      }
    }
    $("#locationlist").append(locationlist);
    $("#polyhouselocationid").append(locationlist);

  });

  $(document).on("change", "#locationlist", function(){
    // $("#refreshPendingGraftingdata").removeClass("d-none");
    PendingForSubmissionGrafting($("#graftingdate").val(),$("#locationlist").val(),'', function(res){
      // console.log(res);
      var lbwithloc="";
      $("#submissionofdailyGraftingTable tbody").html('');
      if(res.length>0){
        $("#updategraftingdetailsbtn").removeClass("d-none");
        for (var i = 0; i < res.length; i++) {
          lbwithloc +=`<tr data-graftingid='${res[i].GraftingId}' data-labourid='${res[i].LabourId}' data-labourname="${res[i].LabourName}" data-productid="${res[i].ProductID}" data-productname="${res[i].ProductName}" data-allocationdate="${res[i].allocationdate}" data-allocationtime="${res[i].allocationtime}" data-targettraycount="${res[i].targettraycount}" data-graftedtraycount="${res[i].graftedtraycount}" data-polyhouseplantdata="${res[i].polyhouseplantdata}">
            <td data-label="Select"><button class="btn btn-sm btn-primary viewGraftingTraystd">View Grafting Tray</button></td>
            <td data-label="Labour Name" class="labourname_gfrm" style="min-width:150px;">${res[i].LabourId} - ${res[i].LabourName}</td>
            <td data-label="Product Name"><input name="productid[]" class="form-control productid d-none"  value=${res[i].ProductID} required />${res[i].ProductName}</td>
            <td data-label="Allocation Date & Time">${changeDateFormatYmd_dMY(res[i].allocationdate)} ${res[i].allocationtime}</td>
            <td data-label="Grafted Count" class="graftedcount_gfrm" style="min-width:150px;">${res[i].graftedtraycount}</td>
            <td data-label="Target Trays With Name">${res[i].targettraycount} - (${res[i].trayname})</td>
            <td data-label="Print Barcode" style="display:none;"><button class="btn btn-sm btn-warning printbarcodebtn" data-printtarget="${res[i].targettraycount}">Print Barcode</button></td>
          </tr>`;
        }
        if(lbwithloc){
          $("#submissionofdailyGraftingTable tbody").append(lbwithloc);
        }
      }else{
        $("#updategraftingdetailsbtn").addClass("d-none");
        $("#submissionofdailyGraftingTable tbody").append('<tr><td colspan="5" style="text-align:center;">No data available</td></tr>');
      }
    });
  });
  
  $(document).on("click", "#refreshPendingGraftingdata", function(){
    PendingForSubmissionGrafting($("#graftingdate").val(),$("#locationlist").val(),'', function(res){
      // console.log(res);
      var lbwithloc="";
      $("#submissionofdailyGraftingTable tbody").html('');
      if(res.length>0){
        $("#updategraftingdetailsbtn").removeClass("d-none");
        for (var i = 0; i < res.length; i++) {
          lbwithloc +=`<tr data-graftingid='${res[i].GraftingId}' data-labourid='${res[i].LabourId}' data-labourname="${res[i].LabourName}" data-productid="${res[i].ProductID}" data-productname="${res[i].ProductName}" data-allocationdate="${res[i].allocationdate}" data-allocationtime="${res[i].allocationtime}" data-targettraycount="${res[i].targettraycount}" data-graftedtraycount="${res[i].graftedtraycount}" data-polyhouseplantdata="${res[i].polyhouseplantdata}">
            <td data-label="Select"><button class="btn btn-sm btn-primary viewGraftingTraystd">View Grafting Tray</button></td>
            <td data-label="Labour Name" class="labourname_gfrm" style="min-width:150px;">${res[i].LabourId} - ${res[i].LabourName}</td>
            <td data-label="Product Name"><input name="productid[]" class="form-control productid d-none"  value=${res[i].ProductID} required />${res[i].ProductName}</td>
            <td data-label="Allocation Date & Time">${changeDateFormatYmd_dMY(res[i].allocationdate)} ${res[i].allocationtime}</td>
            <td data-label="Grafted Count" class="graftedcount_gfrm" style="min-width:150px;">${res[i].graftedtraycount}</td>
            <td data-label="Target Trays With Name">${res[i].targettraycount} - (${res[i].trayname})</td>
            <td data-label="Print QRcode" style="display:none;"><button class="btn btn-sm btn-warning printbarcodebtn" data-printtarget="${res[i].targettraycount}">Print QRcode</button></td>
          </tr>`;
        }
        if(lbwithloc){
          $("#submissionofdailyGraftingTable tbody").append(lbwithloc);
        }
      }else{
        $("#updategraftingdetailsbtn").addClass("d-none");
        $("#submissionofdailyGraftingTable tbody").append('<tr><td colspan="5" style="text-align:center;">No data available</td></tr>');
      }
    });
  });

  $(document).on("click", ".viewGraftingTraystd", function () {
    // Get data attributes from the row
    var row = $(this).closest("tr");
    var rowIndex = row.index(); 
    var graftingid = row.data("graftingid");
    var labourid = row.data("labourid");
    var labourname = row.data("labourname");
    var productid = row.data("productid");
    var productname = row.data("productname");
    var allocationdate = row.data("allocationdate");
    var allocationtime = row.data("allocationtime");
    var targettraycount = row.data("targettraycount");
    var graftedtraycount = row.data("graftedtraycount");
    const polyhouseplantdata = row.data("polyhouseplantdata");
    console.log(polyhouseplantdata);
    // Modal content
    var trddmd = `
      <div class='row' id='traysubmissiondiv' data-divgraftingid='${graftingid}' data-divlabourid='${labourid}' data-divlabourname='${labourname}' data-divproductid='${productid}' data-divproductname='${productname}' data-divallocationdate='${allocationdate}' data-divallocationtime='${allocationtime}' data-graftedtraycount='${graftedtraycount}'>
        <div class='col-md-6'>Labour Name: <strong>${labourname}</strong></div>
        <div class='col-md-6'>Allocation Date & Time: <strong>${allocationdate} ${allocationtime}</strong></div>
        <div class='col-md-6'>Product Name: <strong>${productname}</strong></div>
        <div class='col-md-6'>Target (Trays): <strong>${targettraycount}</strong></div>
        <div class='col-md-6'></div>
        <div class='col-md-6'>Grafted (Trays): <strong>${graftedtraycount}</strong></div>
      </div>
    `;
    // alert(graftingid+"--"+labourid+"--"+productid);
    // Replace old content instead of appending
    $("#submissionofgraftingModal #productdetailsdiv").html(trddmd);
    $("#savesubmissionofgrafting").data("graftingid",graftingid);
    $("#savesubmissionofgrafting").data("labourid",labourid);
    $("#savesubmissionofgrafting").data("productid",productid);
    $("#savesubmissionofgrafting").data("tablerowindex",rowIndex);
    $("#graftedtraycount").val('');
    // Show modal
    $("#submissionofgraftingModal").modal("show");
    GetPolihouseDetailsList("","","","polihouseid");
    GetPlantingSourceList("3","plantingsourcetype");
  });

  $("#submissionofdailyGraftingForm").on("submit", function(e) {
    e.preventDefault();
      var form_data = new FormData();
      var companyid = $("body").data("companyid");
      var subcompanyid = $("body").data("subcompanyid");
      var projectid = $("body").data("projectid");
      var loginid = $("body").data("loginid");

      form_data.append("companyid", companyid);
      form_data.append("subcompanyid", subcompanyid);
      form_data.append("projectid", projectid);
      form_data.append("loginid", loginid);

      let graftingdate = $("#graftingdate").val();
      form_data.append("graftingdate", graftingdate);

      let locationid = $("#locationlist").val();
      form_data.append("locationid", locationid);

      // Collect labour rows
      let labourDataArray = [];
      $("#submissionofdailyGraftingTable tbody tr").each(function () {
          let labourid = $(this).data("labourid"); // <tr data-labourid="L001">
          let productid = $(this).find(".productid").val();
          let graftetarget = $(this).find(".graftetarget").val();
          let graftedcount = $(this).find(".graftedcount").val();
          let polyhousename = $(this).find(".polyhousename").val() || "";

          if (labourid && productid) {
              labourDataArray.push({
                  productid: productid,
                  labourid: labourid,
                  graftetarget: graftetarget,
                  graftedcount: graftedcount,
                  polyhousename: polyhousename
              });
          }
      });

      form_data.append("labourgraftedData", JSON.stringify(labourDataArray));

      // AJAX
      $.ajax({
          url: "modal/grafting_modal.php?action=UpdateDailyGraftingSubmissionForm",
          type: "POST",
          data: form_data,
          contentType: false,
          processData: false,
          cache: false,
          beforeSend: function () {
              $("#updategraftingdetailsbtn").prop("disabled", true).text("Saving...");
          },
          success: function (res) {
              // console.log("Raw Response:", res);
              try {
                  // ✅ Convert string to JSON object
                  let result = typeof res === "string" ? JSON.parse(res) : res;

                  if (result.status === "success") {
                      Swal.fire({
                          text: result.message,
                          icon: "success",
                          showDenyButton: true,
                          confirmButtonColor: "#3085d6",
                          confirmButtonText: "Refresh",
                          allowOutsideClick: true,
                          allowEscapeKey: true
                      }).then((swalResult) => {
                          if (swalResult.isConfirmed) {
                              window.location.reload();
                          }
                      });
                  } else {
                      Swal.fire({
                          text: result.message || "Something went wrong!",
                          icon: "warning"
                      });
                  }
              } catch (err) {
                  console.error("JSON Parse Error:", err);
                  Swal.fire({
                      text: "Invalid server response!",
                      icon: "error"
                  });
              }
          },
          error: function (xhr, status, error) {
              console.error("AJAX Error:", error);
              Swal.fire({
                  text: "❌ Something went wrong while saving!",
                  icon: "error"
              });
          },
          complete: function () {
              $("#updategraftingdetailsbtn").prop("disabled", false).text("Save Daily Grafting Details");
          }
      });
  });

});



// ✅ Add new row dynamically
$(document).on("click", ".addbarcodeRow", function () {
  var newRow = $(this).closest("tr").clone(); // clone current row
  newRow.find("input").val(""); // clear input values
  $("#graftingtraydetails tbody").append(newRow); // append to table
});

// ✅ Remove row dynamically
$(document).on("click", ".removebarcodeRow", function () {
  var rowCount = $("#graftingtraydetails tbody tr").length;
  if (rowCount > 1) {
    $(this).closest("tr").remove();
  } else {
    alert("At least one row is required!");
  }
});

// open polihouse modal
$(document).on("click", ".openpolihousemodal", function(){
  $("#polyhouseModal").modal("show");
});

// save polihouse details
$("#polyhouseForm").on("submit", function(e) {
      e.preventDefault();
      const formData = $(this).serialize();
      $.ajax({
        // url: "ajax/save_polyhouse_master.php", // PHP handler file
         url: "modal/polihouse_modal.php?action=saveupdatePolihouseDetails",
        type: "POST",
        data: formData,
        dataType: "json",
        beforeSend: function() {
          $(".btn-primary").prop("disabled", true).text("Saving...");
        },
        success: function(res) {
          $(".btn-primary").prop("disabled", false).text("Save Polyhouse Details");

          if (res.status === "success") {
            alertmessage("success", res.message);
            $("#polyhouseForm")[0].reset();
            $("#polyhouseModal").modal("hide");

            // Optional: Refresh polyhouse list if available
            if (typeof loadPolyhouseList === "function") loadPolyhouseList();
          } else {
            alertmessage("warning", res.message || "Unable to save data.");
          }
        },
        error: function(xhr, status, error) {
          $(".btn-primary").prop("disabled", false).text("Save Polyhouse Details");
          alertmessage("error", "Error: " + error);
        }
      });
});

// Optional reset handler
$("#polyhouseForm").on("reset", function() {
  $("#polyhouseid").val("");
  $("#polyhouseModalLabel").text("Add New Polyhouse");
});


document.getElementById("generatePdfBtnXX").addEventListener("click", async function () {
    try {
      const element = document.getElementById("ViewBarcodeDiv");
      if (!element) throw new Error("ViewBarcodeDiv not found");

      // render the element to canvas
      const canvas = await html2canvas(element, {
        scale: 2,
        useCORS: true,
        logging: false
      });

      const { jsPDF } = window.jspdf;
      const pdf = new jsPDF("p", "pt", "a4");

      const pageWidthPt = pdf.internal.pageSize.getWidth(); // points
      const pageHeightPt = pdf.internal.pageSize.getHeight();

      // canvas dimensions in pixels
      const canvasWidthPx = canvas.width;
      const canvasHeightPx = canvas.height;

      // ratio: how many source pixels correspond to 1 PDF point
      const pxPerPt = canvasWidthPx / pageWidthPt;

      // height in source pixels that corresponds to one PDF page
      let pageSliceHeightPx = Math.floor(pageHeightPt * pxPerPt);
      if (pageSliceHeightPx <= 0) pageSliceHeightPx = canvasHeightPx; // fallback

      const pageCount = Math.ceil(canvasHeightPx / pageSliceHeightPx);

      for (let i = 0; i < pageCount; i++) {
        // calculate slice height for this page (may be smaller on last page)
        const sy = i * pageSliceHeightPx;
        const sh = Math.min(pageSliceHeightPx, canvasHeightPx - sy);

        if (sh <= 0) continue; // nothing to draw

        // create a temporary canvas to hold the slice
        const pageCanvas = document.createElement("canvas");
        pageCanvas.width = canvasWidthPx;
        pageCanvas.height = sh; // integer > 0

        const ctx = pageCanvas.getContext("2d");
        // draw portion of original canvas onto pageCanvas
        ctx.drawImage(
          canvas,
          0,            // sx
          sy,           // sy
          canvasWidthPx,// sw
          sh,           // sh
          0,            // dx
          0,            // dy
          canvasWidthPx,// dw
          sh            // dh
        );

        // convert to JPEG (more tolerant than PNG for large canvases)
        const imgData = pageCanvas.toDataURL("image/jpeg", 0.95);

        // add a new page for pages after the first
        if (i > 0) pdf.addPage();

        // compute rendered image height in PDF points (maintains aspect ratio)
        const imgHeightPt = (sh / pxPerPt);

        pdf.addImage(imgData, "JPEG", 0, 0, pageWidthPt, imgHeightPt);
      }

      // save
      pdf.save("barcodes.pdf");

      // optional: open in new tab
      const blob = pdf.output("blob");
      const fileURL = URL.createObjectURL(blob);
      window.open(fileURL);

    } catch (err) {
      console.error("PDF generation failed:", err);
      alert("Failed to generate PDF. See console for details.");
    }
});

$(document).on("click", ".printbarcodebtn", function(){
    $("#printbarcodefrom").data("barcodestring", '');
    let projectid = $("body").data("projectid");
    let labourid = $("#traysubmissiondiv").data("divlabourid");
    let labourname = $("#traysubmissiondiv").data("divlabourname");
    let productid = $("#traysubmissiondiv").data("divproductid");
    let allocationdate = $("#traysubmissiondiv").data("divallocationdate");
    let allocationtime = $("#traysubmissiondiv").data("divallocationtime");
    let graftedtraycount  = $("#traysubmissiondiv").data("graftedtraycount");
    let productname  = $("#traysubmissiondiv").data("divproductname");
    // alert(allocationdate+'--'+productname+'--'+labourname+'--'+allocationtime);
    var barcodelbl = projectid + "" + labourid + "" + productid + "" + allocationdate;
    barcodelbl = barcodelbl.replaceAll("-", "");
    $("#printbarcodefrom").data("barcodestring", barcodelbl);
    $("#printbarcodefrom").data("allocationdate", allocationdate);
    $("#printbarcodefrom").data("allocationtime", allocationtime);
    $("#printbarcodefrom").data("labourname", labourname);
    $("#printbarcodefrom").data("productname", productname);
    $("#askquestion_modal").modal("show");
    $("#barcodeCount").val(graftedtraycount);
});

 
$(document).on("click", "#printbarcodefrom", function () {
  let barcodelengthinsheet = parseInt($("#barcodelengthinsheet").val()) || 0;
  let barcodelengthinrow = parseInt($("#barcodelengthinrow").val()) || 0;
  let totalnoprint = parseInt($("#barcodeCount").val()) || 0;
  let starting = parseInt($("#startBox").val()) || 0;

  if (totalnoprint < 1) {
      alert("Please enter how many QRcodes you want to print.");
      $("#barcodeCount").focus();
      return false;
  }
  if (starting < 1) {
      alert("Please enter the box number from which you want to start printing QRcodes.");
      $("#startBox").focus();
      return false;
  }

  let availableLabel = barcodelengthinsheet - (starting - 1);
  if (totalnoprint > availableLabel) {
      let totalBoxesNeeded = totalnoprint + (starting - 1);
      makeBoxLayoutForQRcode(totalBoxesNeeded, barcodelengthinrow);
  } else {
      makeBoxLayoutForQRcode(barcodelengthinsheet, barcodelengthinrow);
  }

  let barcodestring = $("#printbarcodefrom").data("barcodestring") || "";
  let graftingby = $("#printbarcodefrom").data("labourname") || "";
  let productname = $("#printbarcodefrom").data("productname") || "";
  let locationname = $("#locationlist option:selected").text();
  let allocationdate = $("#printbarcodefrom").data("allocationdate") || "";

  printSameQRCode(barcodestring, starting, totalnoprint, graftingby, productname, locationname, allocationdate);

  $("#askquestion_modal").modal("hide");
  $("#viewbarcodelistforadjt_modal").modal("show");
}); 

function makeBoxLayoutForQRcode(qrCount, columnsInRow) {
  $("#ViewBarcodeDiv").empty();

  qrCount = parseInt(qrCount) || 0;
  columnsInRow = parseInt(columnsInRow) || 1;
  if (qrCount < 1) return;

  const labelWidth = (100 / columnsInRow) - 1; // % width for each box
  let j = 1;

  for (let i = 0; i < qrCount; i++) {
    // const qrDiv = `
    //   <div class="barcode-box"
    //        style="width:${labelWidth}%; display:inline-block; vertical-align:top; 
    //               padding:6px; box-sizing:border-box; height:auto; overflow:hidden; 
    //               margin:4px 2px; text-align:center;">
    //     <div><label id="gby_${j}" style="font-size:1.25em;"></label></div>
    //     <div id="layoutqr_${j}" style="width:100%; height:auto; display:flex; justify-content:center;"></div>
    //     <div style="margin-top:4px;">
    //       <label id="prdname_${j}" style="font-size:1.25em;"></label> 
    //       <label id="locname_${j}" style="font-size:1.25em;"></label>
    //       <label id="aldate_${j}" style="font-size:1.25em;"></label>
    //     </div>
    //   </div>`;
    const qrDiv = `
      <div class="barcode-box"
           style="width:${labelWidth}%; display:inline-block; vertical-align:top; 
                  padding:6px; box-sizing:border-box; height:auto; overflow:hidden; 
                  margin:4px 2px;">

        <div style="display:flex; width:100%; align-items:center;">

          <!-- LEFT SIDE : QR CODE (40%) -->
          <div style="width:40%; display:flex; justify-content:center; align-items:center;">
            <div id="layoutqr_${j}" style="width:100%; height:auto;"></div>
          </div>

          <!-- RIGHT SIDE : TEXT (60%) -->
          <div style="width:60%; padding-left:4px; text-align:left;">
            <div><label id="gby_${j}" style="font-size:1.1em; font-weight:bold;"></label></div>
            <div><label id="prdname_${j}" style="font-size:0.8em;"></label></div>
            <div><label id="locname_${j}" style="font-size:0.8em;"></label></div>
            <div><label id="aldate_${j}" style="font-size:0.8em;"></label></div>
          </div>

        </div>

      </div>`;
    $("#ViewBarcodeDiv").append(qrDiv);
    j++;
  }
}

function generateQRCode(code, elementId) {
  const container = document.getElementById(elementId);
  if (!container) return;

  // Clear old QR if already exists
  container.innerHTML = "";

  const parent = container.closest('.barcode-box');
  const size = parent ? Math.max(80, parent.clientWidth * 0.6) : 100;

  new QRCode(container, {
    text: code,
    width: size,
    height: size,
    colorDark: "#000000",
    colorLight: "#ffffff",
    correctLevel: QRCode.CorrectLevel.H
  });
}

function printSameQRCode(baseCode, starting, noOfQRCode, graftingBy, productName, locationName, allocationDate) {
  let s = parseInt(starting);
  const n = parseInt(noOfQRCode);

  setTimeout(() => {
    let j = 1;
    for (let i = 0; i < n; i++) {
      const qrCodeValue = baseCode + j;
      const cid = `layoutqr_${s}`;
      generateQRCode(qrCodeValue, cid);
      $(`#gby_${s}`).text(graftingBy || "");
      $(`#locname_${s}`).text(locationName ? ` ${locationName}` : "");
      $(`#prdname_${s}`).text(productName || "");
      $(`#aldate_${s}`).text(allocationDate ? ` ${allocationDate}` : "");
      s++;
      j++;
    }
  }, 100);
}

async function handleInputBlur(input) {
    let $input = $(input);
    let newValue = $input.val();
    
    // 👉 Skip processing if input is empty
    if (newValue === "") return;

    // Get table, row, and cell (column)
    let $table = $input.closest("table");
    let tableId = $table.attr("id");
    let rowIndex = $input.closest("tr").index();
    let cellIndex = $input.closest("td, th").index();

    // SweetAlert2 radio list options
    const inputOptions = {
        all: "Apply to All",
        after: "Apply After Also",
        before: "Apply Before Also",
        onlythis: "Only This"
    };

    // Show SweetAlert2 prompt
    const { value: applyOption } = await Swal.fire({
        title: "Select where to apply this value",
        input: "radio",
        inputOptions: inputOptions,
        inputValidator: (value) => {
            if (!value) {
                return "Please select one option!";
            }
        },
        customClass: {
            input: 'swal2-radio-list' // optional custom class
        },
        confirmButtonText: "Apply",
        showCancelButton: true,
        allowOutsideClick: false,
        allowEscapeKey: false
    });

    if (!applyOption) return; // user cancelled or closed dialog

    // ✅ Apply value as per selected option
    if (applyOption === "all") {
        $table.find("tr").each(function() {
          $(this).find("td").eq(cellIndex).find("input").val(newValue);
          $(this).find("td").eq(cellIndex).find("input").val(newValue).css("background-color", "#fff3cd").animate({ backgroundColor: "#ffffff" }, 12000);
        });
    } else if (applyOption === "after") {
        $table.find("tr").each(function(i) {
            if (i > rowIndex) {
              // $(this).find("td").eq(cellIndex).find("input").val(newValue);
              $(this).find("td").eq(cellIndex).find("input").val(newValue).css("background-color", "#fff3cd").animate({ backgroundColor: "#ffffff" }, 12000);
            }
        });
    } else if (applyOption === "before") {
        $table.find("tr").each(function(i) {
            if (i <= rowIndex) {
                // $(this).find("td").eq(cellIndex).find("input").val(newValue);
                $(this).find("td").eq(cellIndex).find("input").val(newValue).css("background-color", "#fff3cd").animate({ backgroundColor: "#ffffff" }, 12000);
            }
        });
    } else if (applyOption === "onlythis") {
        // already updated current input, no extra action
    }
}

/*
$(document).on("click", ".printbarcodebtn", function(){
    $("#printbarcodefrom").data("barcodestring", '');
    let projectid = $("body").data("projectid");
    let labourid = $(this).closest("tr").data("labourid");
    let labourname = $(this).closest("tr").data("labourname");
    let productid = $(this).closest("tr").data("productid");
    let allocationdate = $(this).closest("tr").data("allocationdate");
    let allocationtime = $(this).closest("tr").data("allocationtime");
    let targettraycount  = $(this).closest("tr").data("targettraycount");
    let productname  = $(this).closest("tr").data("productname");

    // var barcodelbl =projectid+""+labourid+""+productid+""+allocationdate+""+allocationtime;
    var barcodelbl = projectid + "" + labourid + "" + productid + "" + allocationdate;
    barcodelbl = barcodelbl.replaceAll("-", "");

    // var barcodelbl =projectid+""+labourid+""+productid;
    // generateBarcode(barcodelbl, 'svgid');
    $("#printbarcodefrom").data("barcodestring", barcodelbl);
    $("#printbarcodefrom").data("allocationdate", allocationdate);
    $("#printbarcodefrom").data("allocationtime", allocationtime);
    $("#printbarcodefrom").data("labourname", labourname);
    $("#printbarcodefrom").data("productname", productname);
    $("#askquestion_modal").modal("show");
    $("#barcodeCount").val(targettraycount);
});

 
$(document).on("click", "#printbarcodefrom", function () {
  let barcodelengthinsheet = parseInt($("#barcodelengthinsheet").val()) || 0;
  let barcodelengthinrow = parseInt($("#barcodelengthinrow").val()) || 0;
  let totalnoprint = parseInt($("#barcodeCount").val()) || 0;
  let starting = parseInt($("#startBox").val()) || 0;

  if (totalnoprint < 1) {
      alert("Please enter how many barcodes you want to print.");
      $("#barcodeCount").focus();
      return false;
  }
  if (starting < 1) {
      alert("Please enter the box number from which you want to start printing barcodes.");
      $("#startBox").focus();
      return false;
  }

  let availableLabel = barcodelengthinsheet - (starting - 1);
  if (totalnoprint > availableLabel) {
      let totalBoxesNeeded = totalnoprint + (starting - 1);
      makeBoxLayout(totalBoxesNeeded, barcodelengthinrow);
  } else {
      makeBoxLayout(barcodelengthinsheet, barcodelengthinrow);
  }

  let barcodestring = $("#printbarcodefrom").data("barcodestring") || "";
  let graftingby = $("#printbarcodefrom").data("labourname") || "";
  let productname = $("#printbarcodefrom").data("productname") || "";
  let locationname = $("#locationlist option:selected").text();
  let allocationdate = $("#printbarcodefrom").data("allocationdate") || "";

  printSameBarcode(barcodestring, starting, totalnoprint, graftingby, productname, locationname, allocationdate);

  $("#askquestion_modal").modal("hide");
  $("#viewbarcodelistforadjt_modal").modal("show");
}); 

function makeBoxLayout(barcodeCount, columnsInRow) {
  $("#ViewBarcodeDiv").empty();

  barcodeCount = parseInt(barcodeCount) || 0;
  columnsInRow = parseInt(columnsInRow) || 1;
  if (barcodeCount < 1) return;

  const labelWidth = (100 / columnsInRow) - 1; // % width for each box
  let j = 1;

  for (let i = 0; i < barcodeCount; i++) {
    const barcodeDiv = `
      <div class="barcode-box" 
           style="width:${labelWidth}%; display:inline-block; vertical-align:top; 
                  padding:6px; box-sizing:border-box; height:auto; overflow:hidden; 
                  margin:4px 2px;">
        <div><label id="gby_${j}" style="font-size:1.25em;"></label></div>
        <svg id="layoutbarcode_${j}"></svg>
        <div style="margin-top:4px;">
          <label id="prdname_${j}" style="font-size:1.25em;"></label> 
          <label id="locname_${j}" style="font-size:1.25em;"></label>
          <label id="aldate_${j}" style="font-size:1.25em;"></label>
        </div>
      </div>`;
    $("#ViewBarcodeDiv").append(barcodeDiv);
    j++;
  }
}

function generateBarcode(code, elementId) {
  const svgElement = document.getElementById(elementId);
  if (!svgElement) return;

  const renderBarcode = () => {
    const parent = svgElement.closest('.barcode-box');
    if (!parent) return;

    const parentWidth = parent.clientWidth || 200;
    const parentHeight = parent.clientHeight || 100;

    // ✅ Use 25% of box height for barcode
    const barcodeHeight = Math.max(25, parentHeight * 0.25);
    const fontSize = Math.max(8, parentWidth / 50);

    JsBarcode(`#${elementId}`, code, {
      format: "CODE128",
      width: Math.max(1, parentWidth / 250), // responsive bar thickness
      height: barcodeHeight,                 // 25% of box height
      displayValue: true,
      font: "Arial",
      fontOptions: "bold",
      fontSize: fontSize,
      textAlign: "center",
      textMargin: 2,
      margin: 0,
      lineColor: "#000000",
      background: "#ffffff"
    });

    svgElement.setAttribute("width", "100%");
    svgElement.setAttribute("height", "100%");
    svgElement.style.display = "block";
    svgElement.style.objectFit = "contain";
  };

  renderBarcode();

  if (!svgElement._resizeBound) {
    window.addEventListener("resize", renderBarcode);
    svgElement._resizeBound = true;
  }
}

function printSameBarcode(barcode, starting, noOfBarcode, graftingBy, productName, locationName, allocationDate) {
  let s = parseInt(starting);
  const n = parseInt(noOfBarcode);
  setTimeout(() => {
    let j=1;
    for (let i = 0; i < n; i++) {
      var barcodenew="";
      barcodenew=barcode+j;
      const cid = `layoutbarcode_${s}`;
      generateBarcode(barcodenew, cid);
      $(`#gby_${s}`).text(graftingBy || "");
      $(`#locname_${s}`).text(locationName ? `, ${locationName}` : "");
      $(`#prdname_${s}`).text(productName || "");
      $(`#aldate_${s}`).text(allocationDate ? `, ${allocationDate}` : "");
      s++;
      j++;
    }
  }, 50);
}
*/
</script>
