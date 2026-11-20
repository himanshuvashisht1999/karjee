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
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome & Bootstrap Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

<!-- ✅ QR Code Scanner Library -->
<script src="https://unpkg.com/html5-qrcode@2.3.7/html5-qrcode.min.js"></script>
<style>
  .container-main {
      max-width: 900px;
      margin: 0 auto;
      padding: 10px 15px 3.5rem;
  }
  .card {
      margin-bottom: 2.5rem !important;
  }
  @media (max-width: 768px) {
      .card-body {
          padding: 8px !important;
      }
      .scanner-wrapper {
          padding: 1rem 0.25rem !important;
          border-radius: 20px !important;
          margin-left: 0 !important;
          margin-right: 0 !important;
      }
      .reader-container {
          max-width: 100% !important;
      }
      .container-main {
          padding: 5px 8px 3rem !important;
      }
  }

  /* --- Scanner Interface --- */
  .scanner-wrapper {
      background: #f8fafc;
      border-radius: 30px;
      padding: 2rem;
      border: 2px solid #e2e8f0;
      text-align: center;
      margin-bottom: 1.5rem;
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: visible; /* Changed from hidden to prevent buttons/content from being cut off */
  }

  .scanner-active {
      border-color: #0d6efd;
      background: #fff;
      box-shadow: 0 20px 50px rgba(13, 110, 253, 0.1);
  }

  .reader-container {
      position: relative;
      width: 100%;
      max-width: 320px;
      margin: 0 auto;
      border-radius: 24px;
      overflow: hidden;
      background: #000;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
  }

  #reader {
      width: 100% !important;
      border: none !important;
  }

  #reader video {
      border-radius: 24px;
      object-fit: cover;
  }

  /* Custom Scanner Overlay */
  .scanner-overlay {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      z-index: 5;
      pointer-events: none;
      display: flex;
      align-items: center;
      justify-content: center;
  }

  .scan-region-highlight {
      position: absolute;
      width: 250px;
      height: 250px;
      border: 2px solid rgba(255, 255, 255, 0.4);
      border-radius: 24px;
      box-shadow: 0 0 0 4000px rgba(0, 0, 0, 0.5); /* Scrim effect */
  }

  .scan-line {
      position: absolute;
      width: 100%;
      height: 4px;
      background: linear-gradient(to right, transparent, #0d6efd, transparent);
      box-shadow: 0 0 15px #0d6efd;
      top: 0;
      z-index: 10;
      animation: scanMove 2.5s ease-in-out infinite;
  }

  @keyframes scanMove {
      0% { top: 10%; opacity: 0; }
      20% { opacity: 1; }
      80% { opacity: 1; }
      100% { top: 90%; opacity: 0; }
  }

  .scanner-corner {
      position: absolute;
      width: 30px;
      height: 30px;
      border: 4px solid #0d6efd;
      z-index: 11;
  }

  .corner-tl { top: 0; left: 0; border-right: none; border-bottom: none; border-top-left-radius: 12px; }
  .corner-tr { top: 0; right: 0; border-left: none; border-bottom: none; border-top-right-radius: 12px; }
  .corner-bl { bottom: 0; left: 0; border-right: none; border-top: none; border-bottom-left-radius: 12px; }
  .corner-br { bottom: 0; right: 0; border-left: none; border-top: none; border-bottom-right-radius: 12px; }

  .scan-success-flash {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(34, 197, 94, 0.3);
      z-index: 20;
      display: none;
      animation: flashAnim 0.5s ease-out;
  }

  @keyframes flashAnim {
      0% { opacity: 1; }
      100% { opacity: 0; }
  }

  /* Dynamic Scanner State Borders */
  .scanner-wrapper.scanner-not-captured {
      border-color: #ef4444 !important; /* Vivid Red */
      box-shadow: 0 20px 50px rgba(239, 68, 68, 0.15) !important;
  }
  .scanner-wrapper.scanner-not-captured .scanner-corner {
      border-color: #ef4444 !important;
  }
  .scanner-wrapper.scanner-not-captured .scan-line {
      background: linear-gradient(to right, transparent, #ef4444, transparent) !important;
      box-shadow: 0 0 15px #ef4444 !important;
  }

  .scanner-wrapper.scanner-captured {
      border-color: #10b981 !important; /* Emerald Green */
      box-shadow: 0 20px 50px rgba(16, 185, 129, 0.2) !important;
      background: #f0fdf4 !important;
  }
  .scanner-wrapper.scanner-captured .scanner-corner {
      border-color: #10b981 !important;
  }
  .scanner-wrapper.scanner-captured .scan-line {
      background: linear-gradient(to right, transparent, #10b981, transparent) !important;
      box-shadow: 0 0 15px #10b981 !important;
  }

  #traydetailsformark {
    display: none;
  }
  .box-gerinfo-card {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 16px 20px;
    border-left: 4px solid #0dcaf0;
  }

  .box-gerinfo-card .gerinfo-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 0;
    border-bottom: 1px dashed #ddd;
    font-size: 14px;
  }

  .box-gerinfo-card .gerinfo-row:last-child {
    border-bottom: none;
  }

  .box-gerinfo-card .gerinfo-row span {
    color: #6c757d;
    font-weight: 500;
    min-width: 160px;
  }

  .box-gerinfo-card .gerinfo-row strong,
  .box-gerinfo-card .gerinfo-row div {
    color: #212529;
    font-weight: 600;
    text-align: right;
    word-break: break-word;
  }

  /* Mobile / Tablet */
  @media (max-width: 991px) {
    .box-gerinfo-card .gerinfo-row {
      flex-direction: column;
      align-items: flex-start;
    }

    .box-gerinfo-card .gerinfo-row span {
      font-size: 13px;
      margin-bottom: 2px;
    }

    .box-gerinfo-card .gerinfo-row strong,
    .box-gerinfo-card .gerinfo-row div {
      text-align: left;
      font-size: 15px;
    }
  }
</style>
<div class="container-main">
  <div class="row <?php echo isset($_GET['dz'])?'d-none':'' ?>">
    <div class="col-md-12">
      <button class="btn btn-sm btn-deepblue m-2 float-end" onclick="location.reload();">Refresh</button>
    </div>
  </div>
  <div class="card mt-1">
    <div class="card-body pb-4" style="margin-bottom: 1.5rem;">
      <div class="d-flex justify-content-between align-items-center mb-3 position-relative">
        <h4 class="text-center w-100 mb-0">🌿 Grafting Tray Monitoring</h4>
        <button class="btn btn-sm btn-outline-primary position-absolute end-0 me-2" onclick="location.reload();" style="border-radius: 8px;">
          <i class="fas fa-sync-alt me-1"></i> Refresh
        </button>
      </div>
      
      <div id="Grafqrcodescannerdiv" class="scanner-wrapper">
        <div class="reader-container">
          <div id="reader"></div>
          <div class="scanner-overlay" id="scannerOverlay" style="display:none;">
            <div class="scan-region-highlight">
              <div class="scan-line"></div>
              <div class="scanner-corner corner-tl"></div>
              <div class="scanner-corner corner-tr"></div>
              <div class="scanner-corner corner-bl"></div>
              <div class="scanner-corner corner-br"></div>
            </div>
            <div class="scan-success-flash" id="successFlash"></div>
          </div>
        </div>

        <!-- Sleek Camera Selection Dropdown -->
        <div id="cameraSelectDiv" class="mt-3 px-3 mx-auto" style="max-width: 320px; display: none;">
          <label class="filter-label text-start mb-1" style="font-size: 0.75rem; font-weight: 600; color: #64748b;">
            <i class="bi bi-camera-fill me-1 text-primary"></i> Choose Camera
          </label>
          <select id="cameraSelection" class="form-select border-primary-subtle shadow-sm" style="border-radius: 12px; height: 45px; font-size: 0.9rem; font-weight: 500;"></select>
        </div>

        <!-- Sleek Real-Time Scanner Status Message -->
        <div id="scannerStatusDiv" class="mt-3 text-center" style="display:none;">
          <span id="scannerStatus" class="badge bg-light text-secondary shadow-sm border py-2 px-3" style="border-radius: 20px; font-size: 0.85rem; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
            <i class="bi bi-broadcast text-danger pulse"></i><span>Scanner Active: Looking for QR Code...</span>
          </span>
        </div>

        <!-- Google Lens Mode Shutter Button -->
        <div id="captureScanBtnDiv" class="mt-3 px-3 mx-auto" style="max-width: 320px; display: none;">
          <button id="captureScanBtn" class="btn btn-success w-100 py-3 fw-bold shadow-sm" style="border-radius: 16px; border: none; background: linear-gradient(135deg, #10b981 0%, #059669 100%); font-size: 0.95rem; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
            <i class="bi bi-camera-fill fs-5"></i>Capture & Scan (Google Lens Mode)
          </button>
        </div>

        <div class="mt-4 d-flex justify-content-center gap-2 flex-wrap">
          <button id="startScan" class="btn btn-primary px-4 py-2" style="border-radius: 12px;">Start</button>
          <button id="stopScan" class="btn btn-danger px-4 py-2" style="display:none; border-radius: 12px;">Stop</button>
          <button id="toggleFlash" class="btn btn-warning px-4 py-2" style="border-radius: 12px;"><i class="bi bi-lightning-fill"></i></button>
          <button id="GrafManualStartScan" class="btn btn-outline-secondary px-4 py-2" style="border-radius: 12px;">Manual</button>
          <button class="btn btn-outline-info px-4 py-2" onclick="window.location.reload();" style="border-radius: 12px;">
            <i class="fa fa-sync"></i>
          </button>
        </div>
      </div>

      <div id="GrafManualQrCodeSearchDiv" style="display:none">
        <div class="card-header bg-warning-subtle fw-semibold">
          <i class="bi bi-qr-code-scan"></i> Manual QR Code
          <button class="btn btn-sm btn-warning float-right" id="GrafQRStartScan">🔓 Unlock Scanner</button>
        </div>
        <div class="row">
          <div class="col-md-12 mt-2">
            <label for="manualQrCode" class="form-label">Or Enter QR Code Manually</label>
            <div class="input-group mt-2">
              <input type="text" id="manualQrCode" class="form-control" placeholder="Enter QR Code">
              <button id="fetchManual" class="btn btn-success">Fetch Data</button>
            </div>
          </div>
        </div>
      <div id="traydetailsformark">
        <div id="trayInfo" class="card mt-4"></div>
        <div class="row mt-4" id="trayactiondivid">
          <div class="col-md-12 mb-3">
            <div class="alert alert-info">
              <i class="fas fa-info-circle"></i>
              If any plant in this tray is <strong>not healthy</strong>, replace the unhealthy plants with <strong>healthy plants</strong> from trays marked as <strong>"Use for Correction"</strong>.
            </div>
          </div>

          <div class="col-md-12 mb-3">
            <label class="font-weight-bold">Do you want to replace any plant?</label>
            <div class="d-flex mt-2">
              <label class="mr-4">
                <input type="radio" name="needReplacement" class="needReplacement" value="Yes"> Yes
              </label>
              <label>
                <input type="radio" name="needReplacement" class="needReplacement" value="No"> No
              </label>
            </div>
          </div>

          <div class="col-md-12 mb-3 d-none" id="CorrectionTraySelection">
            <label class="font-weight-bold">Select Healthy Tray for Replacement:</label>
            <select id="CorrectionTrayList" class="form-control">
              <option value="">-- Select Healthy Tray --</option>
            </select>
          </div>

          <div class="col-md-12 mb-3 d-none" id="replacementCountSection">
            <label class="font-weight-bold">How many plants do you want to replace?</label>
            <input type="number" id="replacePlantCount" class="form-control" min="1" placeholder="Enter number of plants to replace">
          </div>

          <div class="col-md-12 mb-3">
            <label class="font-weight-bold">Select Tray Monitoring Status:</label>
            <div class="d-flex flex-wrap gap-3 mt-2">

                <label class="me-4">
                    <input type="radio" name="trayqcstatus" class="trayqcstatus" value="Healthy">
                    <i class="fas fa-leaf text-success"></i> Healthy
                </label>

                <label class="me-4">
                    <input type="radio" name="trayqcstatus" class="trayqcstatus" value="Dead">
                    <i class="fas fa-skull-crossbones text-dark"></i> Dead
                </label>

                <label class="me-4">
                    <input type="radio" name="trayqcstatus" class="trayqcstatus" value="Use For Correction">
                    <i class="fas fa-tools text-warning"></i> Mark For Correction
                </label>

                <label class="me-4">
                    <input type="radio" name="trayqcstatus" class="trayqcstatus" value="Under Observation">
                    <i class="fas fa-eye text-info"></i> Under Observation
                </label>

            </div>
          </div>

          <div class="col-md-12 mb-3 alert alert-success" id="readyToSaleContainer" style="display:none;">
              <label class="font-weight-bold">Ready To Sale After Monitoring Completed:</label>
              <div class="d-flex flex-wrap gap-3 mt-2">
                  <label class="me-4">
                      <input type="checkbox" name="readytosalestatus" id="readytosalestatus" class="readytosalestatus" value="ReadyToSale">
                      <i class="fas fa-check-circle text-success"></i> Is Ready To Sale ?
                  </label>
              </div>
          </div>

          <div class="col-md-12 mb-3">
            <label class="font-weight-bold">Remarks:</label>
            <textarea id="trayRemark" class="form-control" rows="2" placeholder="Enter remarks or additional notes..."></textarea>
          </div>

          <div class="col-md-12 text-center mt-4">
            <button class="btn btn-info btn-lg px-4" id="saveQcStatusBtn"
              onclick="UpdateGraftingQCStatus('trayqcstatus','readytosalestatus', 'trayRemark', 'CorrectionTrayList', 'replacePlantCount', 'saveQcStatusBtn');">
              <i class="fas fa-save"></i> Save Tray Monitoring Status
            </button>
          </div>
        </div>
        <div id="noResult" class="alert alert-warning mt-4 text-center" style="display:none;"></div>
      </div>
    </div>
  </div>
</div>
<!-- Grafting Monitoring Modal -->
<!-- <div class="modal fade" id="updateTrayQc_modal" tabindex="-1" role="dialog" aria-labelledby="updateTrayQcLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" style="max-width: 95%;">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">
          <i class="fas fa-seedling"></i> Tray Monitoring Status
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" onclick="$('#updateTrayQc_modal').modal('hide')">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <div class="row">
          <div class="col-md-12 mb-3">
            <div class="alert alert-info">
              <i class="fas fa-info-circle"></i>
              If any plant in this tray is <strong>not healthy</strong>, replace the unhealthy plants with <strong>healthy plants</strong> from trays marked as <strong>"Use for Correction"</strong>.
            </div>
          </div>

          <div class="col-md-12 mb-3">
            <label class="font-weight-bold">Do you want to replace any plant?</label>
            <div class="d-flex mt-2">
              <label class="mr-4">
                <input type="radio" name="needReplacement" class="needReplacement" value="Yes"> Yes
              </label>
              <label>
                <input type="radio" name="needReplacement" class="needReplacement" value="No"> No
              </label>
            </div>
          </div>

          <div class="col-md-12 mb-3 d-none" id="CorrectionTraySelection">
            <label class="font-weight-bold">Select Healthy Tray for Replacement:</label>
            <select id="CorrectionTrayList" class="form-control">
              <option value="">-- Select Healthy Tray --</option>
            </select>
          </div>

          <div class="col-md-12 mb-3 d-none" id="replacementCountSection">
            <label class="font-weight-bold">How many plants do you want to replace?</label>
            <input type="number" id="replacePlantCount" class="form-control" min="1" placeholder="Enter number of plants to replace">
          </div>

          <div class="col-md-12 mb-3">
            <label class="font-weight-bold">Select Tray Monitoring Status:</label>
            <div class="d-flex flex-wrap gap-3 mt-2">

                <label class="me-4">
                    <input type="radio" name="trayqcstatus" class="trayqcstatus" value="Healthy">
                    <i class="fas fa-leaf text-success"></i> Healthy
                </label>

                <label class="me-4">
                    <input type="radio" name="trayqcstatus" class="trayqcstatus" value="Dead">
                    <i class="fas fa-skull-crossbones text-dark"></i> Dead
                </label>

                <label class="me-4">
                    <input type="radio" name="trayqcstatus" class="trayqcstatus" value="Use For Correction">
                    <i class="fas fa-tools text-warning"></i> Mark For Correction
                </label>

                <label class="me-4">
                    <input type="radio" name="trayqcstatus" class="trayqcstatus" value="Under Observation">
                    <i class="fas fa-eye text-info"></i> Under Observation
                </label>

            </div>
          </div>

          <div class="col-md-12 mb-3 alert alert-success" id="readyToSaleContainer" style="display:none;">
              <label class="font-weight-bold">Ready To Sale After Monitoring Completed:</label>
              <div class="d-flex flex-wrap gap-3 mt-2">
                  <label class="me-4">
                      <input type="checkbox" name="readytosalestatus" id="readytosalestatus" class="readytosalestatus" value="ReadyToSale">
                      <i class="fas fa-check-circle text-success"></i> Is Ready To Sale ?
                  </label>
              </div>
          </div>

          <div class="col-md-12 mb-3">
            <label class="font-weight-bold">Remarks:</label>
            <textarea id="trayRemark" class="form-control" rows="2" placeholder="Enter remarks or additional notes..."></textarea>
          </div>

          <div class="col-md-12 text-center mt-4">
            <button class="btn btn-info btn-lg px-4" id="saveQcStatusBtn"
              onclick="UpdateGraftingQCStatus('trayqcstatus','readytosalestatus', 'trayRemark', 'CorrectionTrayList', 'replacePlantCount', 'saveQcStatusBtn');">
              <i class="fas fa-save"></i> Save Tray Monitoring Status
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div> -->

<div class="modal fade" id="QcHistory_modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" style="max-width: 95%;">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fas fa-seedling"></i> Monitoring History</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" onclick="$('#QcHistory_modal').modal('hide')">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-3 mb-3"> Product Name</div>
          <div class="col-md-9 mb-3"><span id="qch_productname"></span></div>
        </div>
        <div class="row">
          <div class="col-md-3 mb-3"> Tray Name</div>
          <div class="col-md-9 mb-3"><span id="qch_trayname"></span></div>
        </div>
        <div class="row">
          <div class="col-md-3 mb-3"> Grafting By/Date</div>
          <div class="col-md-9 mb-3"><span id="qch_graftedbydate"></span></div>
        </div>
        <div class="row">
          <div class="col-md-12 mb-3">
            <div class="table-responsive">
              <table class="table table-bordered table-mobile" id="qchistoryviewtbl">
                <thead>
                  <tr>
                    <th>S.No</th>
                    <th>Monitoring Date</th>
                    <th>Monitoring Status</th>
                    <th>Remark</th>
                    <th>MonitoringC Don By</th>
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
</div>

<div class="modal fade" id="TodayQcDone_modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" style="max-width: 95%;">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fas fa-seedling"></i> Today Completed Grafting Monitoring</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" onclick="$('#TodayQcDone_modal').modal('hide')">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-12 mb-3">
            <div class="table-responsive">
              <table class="table table-bordered table-mobile" id="qcstatusviewtbl">
                <thead>
                  <tr>
                    <th>S.No</th>
                    <th>Tray Name</th>
                    <th>Product Name</th>
                    <th>Grafted Date</th>
                    <th>Last Monitoring Date</th>
                    <th>Monitoring Status</th>
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
</div>
<?php
include('../footer.php');
?>
<script type="text/javascript" src="<?php echo $prefix;?>krf-orderandsales/js/pjs_function.js"></script>
<script>
// $(document).on("click", ".openupdatestatus", function () {
//   $("#updateTrayQc_modal").modal("show");
//   let polyhouseplantid = $(this).data('polyhouseplantid');
//   $("#saveQcStatusBtn").data("polyhouseplantid", polyhouseplantid);
// });

$(document).ready(function() {
  $(document).on("change", ".trayqcstatus", function () {
      let status = $(this).val();
      if (status === "Healthy") {
          $("#readyToSaleContainer").slideDown();
      } else {
          $("#readyToSaleContainer").slideUp();
          $(".readytosalestatus").prop("checked", false);
      }
  });

  $(document).on('change', 'input[name="needReplacement"]', function () {
    if ($(this).val() === 'Yes') {
      $('#CorrectionTraySelection').removeClass('d-none');
      $("input[name='trayqcstatus']").prop("disabled", true);
      $("input[name='trayqcstatus'][value='Under Observation']").prop("disabled", false);
      $("input[name='trayqcstatus'][value='Under Observation']").prop("checked", true).trigger("change");
      GetQCCorrectionTray(function(res){
        $('#CorrectionTrayList').empty();
        
        $('#CorrectionTrayList').append(`<option value="" selected disabled>Select</option>`);
        var crtrlist="";
        for (var i = 0; i < res.length; i++) {
          crtrlist +=`<option value='${res[i].polyhouseplantid}' data-polyhouseplantid='${res[i].polyhouseplantid}'>${res[i].trayname} - ${res[i].productname}</option>`;
        }
        if(crtrlist){
          $('#CorrectionTrayList').append(crtrlist);
        }else{
          $('#CorrectionTrayList').append(`<option value='' data-polyhouseplantid=''>No any tray available for correction</option>`);
        }
      }); // load trays dynamically
    } else {
      $("input[name='trayqcstatus']").prop("disabled", false);
      $("input[name='trayqcstatus']").prop("checked", false);
      $('#CorrectionTraySelection, #replacementCountSection').addClass('d-none');
      $('#CorrectionTrayList').val('');
      $('#replacePlantCount').val('');
    }
  });

  // Show count input after selecting a tray
  $(document).on('change', '#CorrectionTrayList', function () {
    if ($(this).val()) {
      $('#replacementCountSection').removeClass('d-none');
    } else {
      $('#replacementCountSection').addClass('d-none');
      $('#replacePlantCount').val('');
    }
  });

  GetGraftedQCDoneToday(function(res){
    // qcstatusviewtbl
    $("#qcstatusviewtbl tbody").empty();
    if(res.length>0){
      let qctdata="";
      let s=1;
      for (var i = 0; i < res.length; i++) {
        qctdata += `<tr><td>${s++}</td><td>${res[i].trayname}</td><td>${res[i].productname}</td><td>${res[i].graftingdatedsp}</td><td>${res[i].last_inspection_date}</td><td>${res[i].growthstage}</td></tr>`;
      }
      if(qctdata){
        $("#qcstatusviewtbl tbody").append(qctdata);
        $("#viewQCCompletedData").show();
      }else{
        $("#viewQCCompletedData").hide();
      }
    }
  });
  $(document).on("click", ".openuqchistory", function(){
    GetGraftedQCHistory($(this).data('polyhouseplantid'),function(response){
      $("#qch_productname").html(response.productname);
      $("#qch_trayname").html(response.trayname);
      $("#qch_graftedbydate").html(response.graftingdate);
      // qchistoryviewtbl
      let res = response.qcdata;
      $("#qchistoryviewtbl tbody").empty();
      if(res.length>0){
        let qctdata="";
        let s=1;
        for (var i = 0; i < res.length; i++) {
          qctdata += `<tr><td>${s++}</td><td>${res[i].qcdate}</td><td>${res[i].qcstatus}</td><td>${res[i].qcremark}</td><td>${res[i].qcdoneby}</td></tr>`;
        }
        if(qctdata){
          $("#qchistoryviewtbl tbody").append(qctdata);
          $("#QcHistory_modal").modal("show");
        }else{
          $("#QcHistory_modal").modal("hide");
          
        }
      }
    });
  });

  $("#viewQCCompletedData").on("click", function() { 
      $("#TodayQcDone_modal").modal("show");
  });

  let qrReader = null;
  let isScanning = false;
  let lastScannedCode = null;
  let scanLockTimeout = null;
  let autoScanInterval = null;
  let torchOn = false;
  let isCameraListLoaded = false;

  let globalAudioCtx = null;
  function initAudioContext() {
      try {
          if (!globalAudioCtx) {
              globalAudioCtx = new (window.AudioContext || window.webkitAudioContext)();
          }
          if (globalAudioCtx && globalAudioCtx.state === 'suspended') {
              globalAudioCtx.resume();
          }
      } catch (e) { console.warn("Failed to initialize AudioContext:", e); }
  }

  $(document).on("click touchstart", function () {
      initAudioContext();
  });

  function playBeep() {
      try {
          initAudioContext();
          if (!globalAudioCtx) return;
          
          const oscillator = globalAudioCtx.createOscillator();
          const gainNode = globalAudioCtx.createGain();
          oscillator.connect(gainNode);
          gainNode.connect(globalAudioCtx.destination);
          oscillator.type = 'sine';
          oscillator.frequency.setValueAtTime(880, globalAudioCtx.currentTime);
          gainNode.gain.setValueAtTime(0.1, globalAudioCtx.currentTime);
          oscillator.start();
          oscillator.stop(globalAudioCtx.currentTime + 0.12);
      } catch (e) { console.warn("Audio feedback failed:", e); }
  }

  function loadCameras(activeTrackLabel = null) {
      if (isCameraListLoaded) {
          if (activeTrackLabel) {
              Html5Qrcode.getCameras().then(cameras => {
                  const activeCam = cameras.find(c => c.label === activeTrackLabel);
                  if (activeCam) {
                      $("#cameraSelection").val(activeCam.id);
                  }
              }).catch(() => {});
          }
          return Promise.resolve();
      }

      return new Promise((resolve, reject) => {
          if (typeof Html5Qrcode === "undefined") {
              reject("Html5Qrcode not defined");
              return;
          }
          Html5Qrcode.getCameras().then(cameras => {
              if (cameras && cameras.length > 0) {
                  let options = '';
                  cameras.forEach((camera, index) => {
                      let label = camera.label || `Camera ${index + 1}`;
                      if (label.toLowerCase().includes('back') || label.toLowerCase().includes('environment') || label.toLowerCase().includes('rear')) {
                          label = `Rear Camera ${index + 1} 📷`;
                      } else if (label.toLowerCase().includes('front') || label.toLowerCase().includes('user')) {
                          label = `Front Camera ${index + 1} 🤳`;
                      }
                      options += `<option value="${camera.id}">${label}</option>`;
                  });
                  $("#cameraSelection").html(options);
                  isCameraListLoaded = true;
                  
                  if (cameras.length > 1) {
                      $("#cameraSelectDiv").slideDown();
                  }
                  
                  let activeCam = null;
                  if (activeTrackLabel) {
                      activeCam = cameras.find(c => c.label === activeTrackLabel);
                  }
                  
                  if (!activeCam) {
                      activeCam = cameras.find(c => {
                          const lbl = (c.label || '').toLowerCase();
                          return lbl.includes('back') || lbl.includes('environment') || lbl.includes('rear');
                      });
                  }
                  
                  if (!activeCam) {
                      activeCam = cameras.find(c => {
                          const lbl = (c.label || '').toLowerCase();
                          return !lbl.includes('front') && !lbl.includes('user');
                      });
                  }
                  
                  if (!activeCam && cameras.length > 0) {
                      activeCam = cameras[0];
                  }
                  
                  if (activeCam) {
                      $("#cameraSelection").val(activeCam.id);
                  }
                  resolve(cameras);
              } else {
                  resolve([]);
              }
          }).catch(err => {
              console.warn("Unable to query cameras:", err);
              resolve([]);
          });
      });
  }

  function initScanner(cameraId = null) {
      if (typeof Html5Qrcode === "undefined") return;
      if (isScanning) return;
      
      if (!qrReader) {
          qrReader = new Html5Qrcode("reader");
      }
      isScanning = true;

      let cameraTarget = { facingMode: "environment" };
      let videoConstraintsObj = {
          facingMode: "environment",
          width: { ideal: 1920 },
          height: { ideal: 1080 }
      };

      if (cameraId) {
          cameraTarget = cameraId;
          videoConstraintsObj = {
              deviceId: cameraId,
              width: { ideal: 1920 },
              height: { ideal: 1080 }
          };
      } else {
          const selectedCameraId = $("#cameraSelection").val();
          if (selectedCameraId) {
              cameraTarget = selectedCameraId;
              videoConstraintsObj = {
                  deviceId: selectedCameraId,
                  width: { ideal: 1920 },
                  height: { ideal: 1080 }
              };
          }
      }
      
      const config = { 
          fps: 15,
          formatsToSupport: [ 
              typeof Html5QrcodeSupportedFormats !== "undefined" 
                  ? Html5QrcodeSupportedFormats.QR_CODE 
                  : 0 
          ],
          experimentalFeatures: {
              useBarCodeDetectorIfSupported: true
          },
          videoConstraints: videoConstraintsObj
      };

      qrReader.start(
          cameraTarget,
          config,
          onScanSuccess,
          onScanFailure
      ).then(() => {
          $("#startScan").hide();
          $("#stopScan").show();
          $("#Grafqrcodescannerdiv").addClass('scanner-active scanner-not-captured').removeClass('scanner-captured');
          $("#scannerOverlay").fadeIn();
          $("#scannerStatusDiv").slideDown();
          $("#captureScanBtnDiv").slideDown();
          $("#scannerStatus").html('<i class="bi bi-broadcast text-danger pulse"></i><span>Scanner Active: Looking for QR Code...</span>').removeClass('bg-success text-white bg-warning text-dark bg-danger text-white bg-info text-white').addClass('bg-light text-secondary');
          
          const readerWidth = $("#reader").width();
          const boxSize = Math.floor(readerWidth * 0.75);
          $(".scan-region-highlight").css({
              width: boxSize + "px",
              height: boxSize + "px"
          });

          let activeTrackLabel = null;
          try {
              const track = qrReader.getRunningTrack();
              if (track) {
                  activeTrackLabel = track.label;
                  
                  const capabilities = track.getCapabilities ? track.getCapabilities() : {};
                  const constraints = { advanced: [] };
                  
                  let appliedAny = false;
                  if (capabilities.focusMode && capabilities.focusMode.includes("continuous")) {
                      constraints.advanced.push({ focusMode: "continuous" });
                      appliedAny = true;
                  }
                  
                  if (capabilities.zoom) {
                      let minZoom = capabilities.zoom.min || 1;
                      let maxZoom = capabilities.zoom.max || 1;
                      let idealZoom = Math.min(Math.max(1.8, minZoom), maxZoom);
                      constraints.advanced.push({ zoom: idealZoom });
                      appliedAny = true;
                  }
                  
                  if (appliedAny && track.applyConstraints) {
                      track.applyConstraints(constraints);
                  }
              }
          } catch (e) { console.warn("Failed to apply track capabilities:", e); }

          loadCameras(activeTrackLabel);
          startBackgroundNativeScan();

      }).catch(err => { 
          isScanning = false;
          console.error("Scanner Error:", err);
          
          if (cameraId) {
              console.log("Selected camera failed. Retrying with default environment camera...");
              initScanner(null);
          } else {
              alert("Could not access high-quality camera.");
          }
      });
  }

  window.stopScanner = function () {
      if (autoScanInterval) {
          clearInterval(autoScanInterval);
          autoScanInterval = null;
      }
      if (qrReader && isScanning) {
          return qrReader.stop().then(() => {
              isScanning = false;
              $("#startScan").show();
              $("#stopScan").hide();
              $("#Grafqrcodescannerdiv").removeClass('scanner-active scanner-not-captured scanner-captured');
              $("#scannerOverlay").hide();
              $("#scannerStatusDiv").slideUp();
              $("#captureScanBtnDiv").slideUp();
          }).catch((err) => { 
              console.error("Stop failed:", err);
              isScanning = false;
          });
      }
      return Promise.resolve();
  };

  function onScanSuccess(decodedText) {
      if (decodedText === lastScannedCode) return;
      lastScannedCode = decodedText;
      clearTimeout(scanLockTimeout);
      scanLockTimeout = setTimeout(() => { lastScannedCode = null; }, 2000);

      $("#scannerStatus")
          .html('<i class="bi bi-qr-code text-white"></i><span>QR Code Read: ' + decodedText + '</span>')
          .removeClass('bg-light text-secondary bg-warning text-dark bg-danger text-white bg-info text-white')
          .addClass('bg-success text-white');

      $("#Grafqrcodescannerdiv").removeClass('scanner-not-captured').addClass('scanner-captured');

      playBeep();
      $("#successFlash").show().fadeOut(400);
      
      if (navigator.vibrate) navigator.vibrate(100);

      stopScanner().then(() => {
          $("#Grafqrcodescannerdiv").hide();
          $("#GrafManualQrCodeSearchDiv").show();
          $("#manualQrCode").val(decodedText);
          fetchTrayDataforGermination(decodedText);
      });
  }

  function onScanFailure(error) { }

  $("#toggleFlash").on("click", async function () {
      if (!qrReader || !isScanning) return;
      try {
          const track = qrReader.getRunningTrack();
          if (track) {
              torchOn = !torchOn;
              await track.applyConstraints({ advanced: [{ torch: torchOn }] });
          }
      } catch (err) { }
  });

  $("#trayactiondivid").show();
  
  function fetchTrayDataforGermination(qrCodeValue) {
    if (!qrCodeValue) {
      $("#noResult").show().text("Please enter or scan a valid QR code.");
      return;
    }

    var routurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    $("#trayactiondivid").show();
    $.ajax({
      url: routurl + "krf-orderandsales/modal/grafting_qc_modal.php?action=GetTrayDataWithQRCode",
      method: "POST",
      dataType: "json",
      data: {
        qr_code: qrCodeValue,
        companyid: companyid,
        subcompanyid: subcompanyid,
        projectid: projectid
      },
      beforeSend: function() {
        $("#traydetailsformark").hide();
        $("#noResult").hide();
      },
      success: function(res) {
        if (res.status === "success" && res.data.length > 0) {
          const d = res.data[0];
          $("#saveQcStatusBtn").data("polyhouseplantid", d.polyhouseplantid);
          $("#saveQcStatusBtn").data("stockorder_no", d.stockorder_no);
          $("#traydetailsformark").show();
          $("#noResult").hide();
          $("#trayInfo").html('');
          var traydata="";
          var phsbtn = "d-none";
          if(d.growth_stage=="ReadyToSale"){
            $("#trayactiondivid").hide();
          }
          if(d.qchistorybtn){
            phsbtn="";
          }
          traydata += `<div class="box-gerinfo-card">
            <h5 class="card-title text-center mb-3">
              <i class="fas fa-box"></i> Grafted Tray Details
            </h5>

            <div class="row">
              <div class="col-md-6">
                <div class="gerinfo-row">
                  <span>Tray Name</span>
                  <strong class="tray_name">${d.trayname}</strong>
                </div>
              </div>

              <div class="col-md-6">
                <div class="gerinfo-row">
                  <span>Product Name</span>
                  <strong class="product_name">${d.productname}</strong>
                </div>
              </div>

              <div class="col-md-6">
                <div class="gerinfo-row">
                  <span>Grafted Date</span>
                  <strong class="grafting_date">${d.graftingdatedsp}</strong>
                </div>
              </div>

              <div class="col-md-6 d-none">
                <div class="gerinfo-row ${phsbtn}">
                  <span>Monitoring History</span>
                  <div>${d.qchistorybtn ?? ''}</div>
                </div>
              </div>

              <div class="col-md-6 d-none">
                <div class="gerinfo-row">
                  <span>Last Monitoring Remarks</span>
                  <strong id="inspection_remark">${d.last_inspection_remark ?? ''}</strong>
                </div>
              </div>

              <div class="col-md-6 d-none">
                <div class="gerinfo-row">
                  <span>Last Monitoring Date</span>
                  <strong class="qc_status">${d.last_inspection_date ?? ''}</strong>
                </div>
              </div>
              <div class="col-md-12 text-center mt-4">
                  ${d.growthstage ?? ''}
              </div>
            </div>
          </div>`;
          $("#trayInfo").html(traydata);
        } else {
          $("#noResult").show().text(res.message || "No tray found for this QR code.");
        }
      },
      error: function() {
        $("#noResult").show().text("Server error, please try again.");
      }
    });
  }

  $("#startScan").on("click", initScanner);
  $("#stopScan").on("click", stopScanner);

  $("#GrafManualStartScan").on("click", function () {
      stopScanner();
      $("#Grafqrcodescannerdiv").hide();
      $("#GrafManualQrCodeSearchDiv").show().find("#manualQrCode").focus();
  });

  $("#GrafQRStartScan").on("click", function () {
      initScanner();
      $("#trayInfo").html('');
      $("#manualQrCode").val('');
      $("#Grafqrcodescannerdiv").show();
      $("#GrafManualQrCodeSearchDiv").hide();
      $("input[name='needReplacement']").val("No").trigger("change");
      $("#replacePlantCount").val('');
      $("input[name='trayqcstatus'][value='Under Observation']").prop("checked", true).trigger("change");
  });

  $("#fetchManual").on("click", function() {
    const qrCodeValue = $("#manualQrCode").val().trim();
    fetchTrayDataforGermination(qrCodeValue);
  });

  $("#manualQrCode").on("keypress", function(e) {
    if (e.which === 13) {
      $("#fetchManual").click();
    }
  });

  window.captureAndScanFrame = function () {
      if (!qrReader || !isScanning) return;

      try {
          const video = document.querySelector("#reader video");
          if (!video) {
              alert("Camera video stream not found.");
              return;
          }

          $("#scannerStatus")
              .html('<i class="fa fa-spinner fa-spin text-white"></i><span>Analyzing Captured Frame...</span>')
              .removeClass('bg-light text-secondary bg-warning text-dark bg-danger text-white bg-info text-white')
              .addClass('bg-primary text-white');

          const canvas = document.createElement("canvas");
          canvas.width = video.videoWidth || 1920;
          canvas.height = video.videoHeight || 1080;
          const ctx = canvas.getContext("2d");
          ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

          if (typeof BarcodeDetector !== "undefined") {
              const detector = new BarcodeDetector({ formats: ['qr_code'] });
              detector.detect(canvas)
                  .then(barcodes => {
                      if (barcodes && barcodes.length > 0) {
                          const decodedText = barcodes[0].rawValue;
                          onScanSuccess(decodedText);
                      } else {
                          runScanFileFallback(canvas);
                      }
                  })
                  .catch(err => {
                      runScanFileFallback(canvas);
                  });
          } else {
              runScanFileFallback(canvas);
          }

      } catch (e) {
          console.error("Capture scan failed:", e);
      }
  };

  function runScanFileFallback(canvas) {
      let targetWidth = canvas.width;
      let targetHeight = canvas.height;
      if (targetWidth > 960) {
          const ratio = 960 / targetWidth;
          targetWidth = 960;
          targetHeight = Math.floor(targetHeight * ratio);
      }

      const downscaleCanvas = document.createElement("canvas");
      downscaleCanvas.width = targetWidth;
      downscaleCanvas.height = targetHeight;
      const downscaleCtx = downscaleCanvas.getContext("2d");
      downscaleCtx.drawImage(canvas, 0, 0, targetWidth, targetHeight);

      downscaleCanvas.toBlob((blob) => {
          if (!blob) {
              handleScanError();
              return;
          }
          const file = new File([blob], "captured-frame.png", { type: "image/png" });

          qrReader.scanFile(file, false)
              .then(decodedText => {
                  onScanSuccess(decodedText);
              })
              .catch(err => {
                  handleScanError();
              });
      }, "image/png");
  }

  function handleScanError() {
      alert("Scan failed. Move closer, adjust lighting and try again.");
      $("#scannerStatus")
          .html('<i class="bi bi-broadcast text-danger pulse"></i><span>Scanner Active: Looking for QR Code...</span>')
          .removeClass('bg-primary text-white')
          .addClass('bg-light text-secondary');
  }

  function startBackgroundNativeScan() {
      if (autoScanInterval) {
          clearInterval(autoScanInterval);
          autoScanInterval = null;
      }

      if (typeof BarcodeDetector === "undefined") {
          return;
      }

      let retries = 0;
      const findVideoAndStart = setInterval(() => {
          const video = document.querySelector("#reader video");
          if (video) {
              clearInterval(findVideoAndStart);
              
              const detector = new BarcodeDetector({ formats: ['qr_code'] });
              autoScanInterval = setInterval(() => {
                  if (isScanning && !lastScannedCode) {
                      detector.detect(video)
                          .then(barcodes => {
                              if (barcodes && barcodes.length > 0 && isScanning && !lastScannedCode) {
                                  const decodedText = barcodes[0].rawValue;
                                  onScanSuccess(decodedText);
                              }
                          })
                          .catch(err => { });
                  }
              }, 250);
          } else {
              retries++;
              if (retries > 20) {
                  clearInterval(findVideoAndStart);
              }
          }
      }, 200);
  }

  $("#captureScanBtn").off("click").on("click", function () {
      window.captureAndScanFrame();
  });

  $("#reader").off("click").on("click", function () {
      if (qrReader && isScanning) {
          try {
              const track = qrReader.getRunningTrack();
              if (track && track.applyConstraints) {
                  track.applyConstraints({
                      advanced: [{ focusMode: "continuous" }]
                  });
              }
          } catch (e) { }

          setTimeout(() => {
              if (isScanning) {
                  window.captureAndScanFrame();
              }
          }, 350);
      }
  });

  $("#cameraSelection").off("change").on("change", function () {
      const selectedId = $(this).val();
      if (isScanning && selectedId) {
          stopScanner().then(() => {
              initScanner(selectedId);
          });
      } else if (selectedId) {
          initScanner(selectedId);
      }
  });

  initScanner(null);
});
</script>
