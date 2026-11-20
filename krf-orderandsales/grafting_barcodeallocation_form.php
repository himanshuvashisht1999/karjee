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

.swal2-radio {
  display: flex !important;
  flex-direction: column !important;
  align-items: flex-start !important;
  gap: 12px !important;
  margin-top: 10px !important;
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
          <div class="row">
            <div class="col-md-12 text-center">
              <h4>Grafted Plant QRcode Allocation</h4>
            </div>
          </div>
          <div class="row mb-3 mt-4">
              <div class="col-md-4">
                  <label for="locationlist" class="form-label getmissingbarcodedata">Location <span class="text-danger">*</span></label>
                  <select class="form-control" id="locationlist" name="locationid"></select>
              </div>
              <div class="col-md-4">
                  <label for="grafteddatelist" class="form-label">Grafted Date <span class="text-danger">*</span></label>
                  <select class="form-control getmissingbarcodedata" id="grafteddatelist" name="grafteddatelist"></select>
              </div>
              <div class="col-md-4">
                  <label for="graftedbylist" class="form-label">Grafted By <span class="text-danger">*</span></label>
                  <select class="form-control getmissingbarcodedata" id="graftedbylist" name="graftedbylist"></select>
              </div>
          </div>
          <div class="row">
            <div class="col-md-12 mt-1">
              <div class="table-responsive">
                <table class="table table-mobile table-bordered" id="missingbarcodeviewTable">
                  <thead class="table-light">
                    <tr>
                      <th data-label="Labour Name">Labour Name</th>
                      <th data-label="Product Name">Product Name</th>
                      <th data-label="Date & Time" style="width:200px;">Date & Time</th>
                      <th data-label="Target Trays" style="width:100px;">Trays</th>
                      <th data-label="Target Trays" style="width:150px;">Polyhouse</th>
                      <th data-label="Print Barcode" style="width:100px;">Action</th>
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

<div class="modal fade" id="askquestion_modal" tabindex="-1" role="dialog" aria-labelledby="askquestionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable" style="min-width: 90%;">
    <div class="modal-content">
      <!-- Header -->
      <div class="modal-header">
        <h5 class="modal-title" id="askquestionModalLabel">QRcode Printing Settings</h5>
        <button type="button" class="close" onclick="$('#askquestion_modal').modal('hide')">&times;</button>
      </div>
      <!-- Body -->
      <div class="modal-body">
        
        <div class="row g-3 px-4 py-3">
          <div class="col-md-6">
            <label for="barcodelengthinsheet" class="form-label fw-semibold">
              Total QRcode labels per sheet
            </label>
            <input type="number" id="barcodelengthinsheet" class="form-control" placeholder="e.g., 30" value="24" min="1">
          </div>

          <div class="col-md-6">
            <label for="barcodelengthinrow" class="form-label fw-semibold">
              Number of QRcode labels per row
            </label>
            <input type="number" id="barcodelengthinrow" class="form-control" placeholder="e.g., 5" value="4" min="1">
          </div>
        </div>

        <div class="row g-3 px-4">
          <div class="col-md-6">
            <label for="barcodeCount" class="form-label fw-semibold">
              Number of QRcodes to print
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
      <div class="modal-header">
        <h5 class="modal-title">QRCode For Print</h5>
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
            <button class="btn btn-warning" id="generatePdfBtnXX">Download QRcode PDF</button>
            <button class="btn btn-success" onclick="window.print()">Print QRcode Sheet</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="updatedetailsofgraftedplantsModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg modal-dialog-scrollable" style="min-width: 90%;">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="updatedetailsofgraftedplantsModalheader">Update Tray Details in Polyhouse for Grafted Plants</h5>
        <button type="button" class="close" onclick="$('#updatedetailsofgraftedplantsModal').modal('hide')">&times;</button>
      </div>
      <div class="modal-body">
        <div id="productdetailsdiv"></div>
        <div class="row">
          <div class='col-md-12 mt-2'>
            <label>Polihour Name</label>
            <!-- <button class='btn btn-sm openpolihousemodal float-end'><i class='fa fa-plus'></i></button> -->
            <!-- <button class='btn btn-sm float-end' onclick='GetPolihouseDetailsList("","","","polihouseid")'><i class='fa fa-sync'></i></button> -->
            <select id='polihouseid' class='form-control'></select>
          </div>
        </div>
        <div class="row">
          <div class='col-md-12 mt-2'>
            <div class="table-responsive">
              <table class="table table-bordered table-mobile" id="updatebarcodetotraytbl">
                <thead>
                  <tr>
                    <th style="text-align: center; width: 80px;">Select All<br/><input type="checkbox" class="updbarcodecheckall" id="updbarcodecheckall" /></th>
                    <th>Tray QRcode</th>
                    <th style="width:150px;">Bed Number</th>
                    <th style="width:150px;">Rack Number</th>
                    <th style="width:150px;">Floar Number</th>
                    <th>
                      Place of Bed/Rack<br/>
                      <small>(Start/Middle/End)</small>
                    </th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>            
          </div>
        </div>
        <div class="row mt-4">
          <div class='col-md-12 mt-4 text-center'>
            <button id='updatetraybarcodewithpolyhoousebtn' onclick="UpdateTrayDeailsForPolyhousewithBarcode('updatebarcodetotraytbl',$('#polihouseid').val(),'updatetraybarcodewithpolyhoousebtn')" class='btn btn-success'>Update Tray Details with QRcode</button>
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
  <!-- Include this script once in your page -->
<!-- jQuery -->
<!-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> -->

<!-- Bootstrap JS -->
<!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script> -->

<!-- QR Scanner (html5-qrcode) -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
$(document).ready(function(){
  // -----------------------------------------------------------------------------------
  ProductionLocationMastercallback("all","all",function(res){
    var locationlist = '<option value="">-- Select Location --</option>'; // default option
    $("#locationlist").empty();
    if(res.length > 0){
      for (var i = 0; i < res.length; i++) {
        locationlist += '<option value="' + res[i].LocationId + '">' + res[i].LocationName + '</option>';
      }
    }
    $("#locationlist").append(locationlist);
  });
  
  $(document).on("change", "#locationlist", function(){
    GraftingDatesList($("#locationlist").val(),'','grafteddatelist');
  });
  $(document).on("change", "#grafteddatelist", function(){
    GetGraftedByList($("#locationlist").val(),$("#grafteddatelist").val(),'','graftedbylist');
  });

   
  $(document).on("change", ".getmissingbarcodedata", function(){
    if($("#locationlist").val() && $("#grafteddatelist").val() && $("#graftedbylist").val()){
      GetPendingBarcodeForGraftedPlants($("#locationlist").val(), $("#grafteddatelist").val(), $("#graftedbylist").val(), function(res){
        // console.log(res);
        var lbwithloc="";
        $("#missingbarcodeviewTable tbody").empty();
        if(res.length>0){
          for (var i = 0; i < res.length; i++) {
            lbwithloc +=`<tr data-graftingid='${res[i].GraftingId}' data-graftingdate='${res[i].graftingdate}' data-labourid='${res[i].LabourId}' data-labourname="${res[i].LabourName}" data-productid="${res[i].ProductID}" data-productname="${res[i].ProductName}" data-allocationdate="${res[i].allocationdate}" data-allocationtime="${res[i].allocationtime}" data-submissiondatetime="${res[i].submissiondatetime}" data-targettraycount="${res[i].targettraycount}" data-graftedtraycount="${res[i].graftedtraycount}" data-polyhouseid="${res[i].polyhouseid}" data-polyhousename="${res[i].polyhousename}">
              <td data-label="Labour Name" class="labourname_gfrm" style="min-width:150px;">${res[i].LabourId} - ${res[i].LabourName}</td>
              <td data-label="Product Name"><input name="productid[]" class="form-control productid d-none"  value=${res[i].ProductID} required />${res[i].ProductName}</td>
              <td data-label="Date & Time"><span class="badge badge-pill badge-primary">Allocation : ${changeDateFormatYmd_dMY(res[i].allocationdate)} ${res[i].allocationtime}</span><br/><span class="badge badge-pill badge-success">Submission : ${res[i].submissiondatetime}</span></td>
              <td data-label="Trays"><span class="badge badge-pill badge-primary" style="margin:2px;">Target : ${res[i].targettraycount} Trays</span><br/><span class="badge badge-pill badge-success" style="margin:2px;">Grafted : ${res[i].graftedtraycount} Trays</span></td>
              <td data-label="Polyhouse">${res[i].polyhousename} <br/> <span class="badge badge-primary updatepolyhousebtn" style="cursor:pointer; margin:2px;">Update Polyhouse Details</span></td>
              <td data-label="Action"><h4><span class="badge badge-warning printbarcodebtn" style="cursor:pointer; margin:2px;" data-printtarget="${res[i].targettraycount}">Print QRcode</span></h4></td>
            </tr>`;
          }
          if(lbwithloc){
            $("#missingbarcodeviewTable tbody").append(lbwithloc);
          }
        }else{
          $("#missingbarcodeviewTable tbody").append('<tr><td colspan="5" style="text-align:center;">No data available</td></tr>');
        }
      });
    }
  });  
});

$(document).on("click", ".updatepolyhousebtn", function () {
  // 🔹 Get data attributes from the clicked row
  const row = $(this).closest("tr");
  const rowIndex = row.index();

  const graftingid = row.data("graftingid");
  const labourid = row.data("labourid");
  const labourname = row.data("labourname");
  const productid = row.data("productid");
  const productname = row.data("productname");
  const allocationdate = row.data("allocationdate");
  const allocationtime = row.data("allocationtime");
  const submissiondatetime = row.data("submissiondatetime");
  const graftedtraycount = row.data("graftedtraycount");
  const polyhouseid = row.data("polyhouseid");
  const projectid = $("body").data("projectid");

  // 🔹 Modal content
  const trddmd = `
    <div class='row'>
      <div class='col-md-6'>Labour Name: <strong>${labourname}</strong></div>
      <div class='col-md-6'>Product Name: <strong>${productname}</strong></div>
      <div class='col-md-6'>Allocation of Grafting: <strong>${changeDateFormatYmd_dMY(allocationdate)} ${allocationtime}</strong></div>
      <div class='col-md-6'>Submission of Grafted: <strong>${submissiondatetime}</strong></div>
      <div class='col-md-6'>Grafted Trays: <strong>${graftedtraycount}</strong></div>
    </div>
  `;
  $("#updatedetailsofgraftedplantsModal #productdetailsdiv").html(trddmd);

  // 🔹 Pass data to modal button
  $("#updatetraybarcodewithpolyhoousebtn")
    .data("graftingid", graftingid)
    .data("labourid", labourid)
    .data("productid", productid);

  // 🔹 Reset table content
  $("#updatebarcodetotraytbl tbody").empty();

  // 🔹 Generate barcode prefix
  let barcodelbl = `${projectid}${labourid}${productid}${allocationdate}`.replaceAll("-", "");
  let barcodehtmltr = "";
  let counter = 1;

  // 🔹 Fetch Grafted Plant Data
  GetGraftedPlantsByLabourForDatewithLocation(
    $("#locationlist").val(),
    $("#grafteddatelist").val(),
    $("#graftedbylist").val(),
    function (res) {
      $("#updatebarcodetotraytbl tbody").empty();
      if (res && res.length > 0) {
        res.forEach((item) => {
          const barcodetrnew = `${barcodelbl}${counter}`;
          const startchecked = item.tray_position=="start"?"checked":"";
          const middlechecked = item.tray_position=="middle"?"checked":"";
          const endchecked = item.tray_position=="end"?"checked":"";
          // alert(startchecked+"----"+item.tray_position);
          barcodehtmltr += `
            <tr data-polyhouseplantid="${item.polyhouseplantid}">
              <td class="barcodecheck_td text-center">
                <input type="checkbox" class="barcodecheckbox" />
              </td>
              <td class="barcodeno_td">
                <input type="text" class="form-control barcodeno" value="${item.barcode ?? barcodetrnew}" readonly />
              </td>
              <td class="bednumber_td">
                <input type="text" class="form-control bednumber" onblur="handleInputBlur(this)" value="${item.bed_no ?? ''}" placeholder="Bed number">
              </td>
              <td class="racknumber_td">
                <input type="text" class="form-control racknumber" onblur="handleInputBlur(this)" value="${item.rack_no ?? ''}" placeholder="Rack number">
              </td>
              <td class="floarnumber_td">
                <input type="text" class="form-control floarnumber" onblur="handleInputBlur(this)" value="${item.floar_no ?? ''}" placeholder="Floor number">
              </td>
              <td class="trayposition_td">
                <label><input type="radio" class=" trayposition" name="trayposition${item.polyhouseplantid}" value="start" ${startchecked}> Start</label>
                <label><input type="radio" class="trayposition" name="trayposition${item.polyhouseplantid}" value="middle"  ${middlechecked}> Middle</label>
                <label><input type="radio" class="trayposition" name="trayposition${item.polyhouseplantid}" value="end"  ${endchecked}> End</label>
              </td>
            </tr>`;
          counter++;
        });

        if (barcodehtmltr) {
          $("#updatebarcodetotraytbl tbody").html(barcodehtmltr);

          // ✅ Check the "select all" checkbox and trigger change event
          $("#updbarcodecheckall").prop("checked", true).trigger("change");
        }

        $("#graftedtraycount").val('');
        $("#updatedetailsofgraftedplantsModal").modal("show");

        // 🔹 Load polyhouse details list
        GetPolihouseDetailsList("", "", polyhouseid, "polihouseid");
      } else {
        Swal.fire("No data found", "No grafted plants available for update.", "info");
      }
    }
  );
});



$(document).ready(function () {
    handleMasterCheckboxAll("updbarcodecheckall", "barcodecheckbox");
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
    let labourid = $(this).closest("tr").data("labourid");
    let labourname = $(this).closest("tr").data("labourname");
    let productid = $(this).closest("tr").data("productid");
    let allocationdate = $(this).closest("tr").data("allocationdate");
    let allocationtime = $(this).closest("tr").data("allocationtime");
    let graftedtraycount  = $(this).closest("tr").data("graftedtraycount");
    let productname  = $(this).closest("tr").data("productname");
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
      $(`#locname_${s}`).text(locationName ? `, ${locationName}` : "");
      $(`#prdname_${s}`).text(productName || "");
      $(`#aldate_${s}`).text(allocationDate ? `, ${allocationDate}` : "");
      s++;
      j++;
    }
  }, 100);
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
        text: "Select where to apply this value",
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
</script>
