    <?php
  include('../header.php');
  include('functions/product_functions.php');
  include('functions/vendor_functions.php');
  include('functions/seed_functions.php');

  date_default_timezone_set("Asia/Calcutta");
  $mdid ="";
  if(isset($_GET['mdid'])){
    $mdid = $_GET['mdid'];
  }
  $dddr = loginmodulesdetails_frommaster($authconn,$companyid,$subcompanyid,$projectid,"moduleid",$mdid);
  $groupid ="";
  if(count($dddr)>0){
    $groupid = $dddr[0]['groupid'];
    $modulename = $dddr[0]['modulename'];
  }

  // $polyhusetraydata = getPolyhouseTrayUsageDetails($krfconn, $reskey['polyhouseid'], $projectid);
  // print_r($polyhusetraydata);
?>
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
    #filterBox input {
        width: 200px;
    }
</style>
 <!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<!-- <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script> -->

<!-- ✅ QR Code Scanner Library -->
<script src="https://unpkg.com/html5-qrcode@2.3.7/html5-qrcode.min.js"></script>
<style>
    #AllocationReaderId {
      width: 100%;
      max-width: 300px;
      margin: 20px auto;
      border: 2px solid #0d6efd;
      border-radius: 10px;
      padding: 10px;
    }
    #FetchedTrayDetailsToPH {
      display: none;
    }

    .kpi-row {
        display: flex;
        gap: 10px;
        width: 100%;
    }

    .kpi-row .kpi-card {
        flex: 1;
        min-width: 0;
        text-align: center;
        padding: 12px 6px;
        border-radius: 10px;
        background: #fff;
        box-shadow: 0 4px 10px rgba(0,0,0,.08);
    }

    /* Mobile safe font scaling */
    .kpi-row small {
        font-size: 11px;
        color: #6c757d;
    }

    .kpi-row h4 {
        font-size: clamp(16px, 4vw, 22px);
        font-weight: 700;
        margin: 0;
        color: #2e7d32;
        white-space: nowrap;
    }

        .allocation-theme {
      border-radius: 12px;
    }

    .modal-header {
      background: linear-gradient(135deg,#2e7d32,#66bb6a);
    }

    .kpi-card {
      background:#fff;
      border-radius:10px;
      text-align:center;
      padding:14px;
      box-shadow:0 4px 12px rgba(0,0,0,.08);
    }
    .kpi-card h4 {
      margin:0;
      color:#2e7d32;
      font-weight:700;
    }

    .scanner-box {
      position:relative;
      min-height:230px;
      background:#f8f9fa;
      border-radius:12px;
      overflow:hidden;
      border:2px dashed #66bb6a;
    }

    /* SCAN PULSE */
    .scan-pulse::after {
      content:"";
      position:absolute;
      left:0;
      right:0;
      height:4px;
      background:rgba(76,175,80,.8);
      animation:scan 2s linear infinite;
    }
    @keyframes scan {
      0% { top:0; }
      100% { top:100%; }
    }

    /* SUCCESS GLOW */
    .scan-success {
      box-shadow:0 0 25px rgba(76,175,80,.9);
      border-color:#2e7d32;
    }
</style>
<div class="container1 my-4">
    <div id="printparameterdiv"></div>
    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col-md-6">
                    <h5>ALLOCATE OF TRAY OF GERMINATED BOXES TO POLYHOUSE</h5>
                </div>
                <div class="col-md-6">

                    <button class="btn btn-sm btn-deepblue mr-2 float-right" onclick="window.location.reload();"> <i class="fa fa-sync"></i> Refresh</button>
                </div>
            </div> 
        </div>
        <div class="card-body">
            <div class="row mt-2">
                <div class="col-md-4">

                </div>
                <div class="col-md-4">

                </div>
                <div class="col-md-4">
                    <label>Filter</label>
                    <input type="text" id='grmboxtrayallinput' oninput="filterTable('#grmboxtrayallinput', '#PendingAllocationTraytbl');" class="form-control" placeholder="Type for Searching ......">
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="PendingAllocationTraytbl">
                            <thead>
                                <tr>
                                    <th>Date of Open</th>
                                    <th>Product Name</th>
                                    <th>Stock Order Number</th>
                                    <th>Box QRCode No</th>
                                    <th>Trays in Boxes</th>
                                    <th>Allocated Trays</th>
                                    <th>Allocation of Green House</th>
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

<div class="modal fade" id="allocationTrayToPHModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-sm-down" style="max-width:98%;">
    <div class="modal-content allocation-theme">

      <!-- HEADER -->
      <div class="modal-header text-white">
        <div>
          <h5 class="modal-title fw-bold">Tray Allocate of Received Germinated Box</h5>
          <div class="small opacity-75">
            <b>Product:</b> <span id="productNamemdalc"></span> |
            <b>Order:</b> <span id="productOrderNoalc"></span>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <!-- BODY -->
      <div class="modal-body">

        <!-- KPI CARDS -->
        <div class="kpi-row mb-3">
            <div class="kpi-card">
                <small>Trays in Box</small>
                <h4 id="germTrayCountinbox">0</h4>
            </div>

            <div class="kpi-card">
                <small>Allocated Trays to Polyhouse</small>
                <h4 id="allocatedTrayCountofobox">0</h4>
            </div>
        </div>

        <!-- PROGRESS BAR -->
        <div class="mb-3">
          <label class="fw-semibold small">Allocation Progress</label>
          <div class="progress" style="height:10px;">
            <div id="allocationProgressBar" class="progress-bar bg-success" style="width:0%"></div>
          </div>
        </div>

        <!-- SCANNER -->
        <div class="card shadow-sm" id="allocationqrcodescannerdiv" style="display:none;">
          <div class="card-header fw-semibold bg-light">
            <i class="bi bi-qr-code-scan"></i> QR Code Scanner
          </div>
          <div class="card-body text-center">
            <div id="AllocationReaderId" class="scanner-box scan-pulse"></div>
            <div class="mt-3 d-flex justify-content-center gap-2 flex-wrap">
              <button id="AllocationstartScan" class="btn btn-success btn-sm">
                <i class="bi bi-play-circle"></i> Start
              </button>
              <button id="AllocationstopScan" class="btn btn-danger btn-sm" style="display:none;">
                <i class="bi bi-stop-circle"></i> Stop
              </button>
              <button id="ManualAllocationstartScan" class="btn btn-warning btn-sm">
                <i class="bi bi-keyboard"></i> Manual/Scan From External Device
              </button>
            </div>

          </div>
        </div>

        <!-- MANUAL QR -->
        <div id="manualQrCodeSearchDiv" class="card mt-3 shadow-sm">
            <div class="card-header bg-warning-subtle fw-semibold">
                <button class="btn btn-sm btn-warning float-right" id="QRcodeAllocationstartScan">🔓 Unlock Mobile Scanner</button>
                Manual QRCode Entry
            </div>
            <!-- <div class="card-body">
                <div class="input-group mt-2">
                    <span class="input-group-text"> <i class="fa fa-qrcode"></i></span>
                    <input type="text" id="manualTrayQrCode" autocomplete="off" class="form-control" placeholder="Enter or paste QR code">
                    <button id="TrayAllocationfetchManual" class="btn btn-success"><i class="bi bi-search"></i> Fetch</button>
                </div>
            </div> -->

            <div class="card-body">
                <label class="form-label fw-semibold"> <i class="fa fa-search"></i> Enter QR Code Entry </label>
                <div class="input-group mt-2">
                    <span class="input-group-text"> <i class="fa fa-qrcode"></i></span>
                    <input type="text" id="manualTrayQrCode" autocomplete="off" class="form-control" placeholder="Enter or paste QR code">
                    <button id="TrayAllocationfetchManual" class="btn btn-success"><i class="bi bi-search"></i> Fetch</button>
              </div>
            </div>
        </div>
        <!-- NO RESULT -->
        <div id="noResult" class="alert alert-warning text-center mt-3" style="display:none;">
          No matching germination box found
        </div>
        <!-- BOX DETAILS -->
        <div id="FetchedSowingTrayDetails" class="card mt-3 shadow-sm table-responsive" style="display:none;">
            <div class="row">
                <div class="col-md-12">
                    <div id="undoToast" class="alert alert-warning d-none">
                        Tray removed.
                        <button class="btn btn-sm btn-dark ms-2" id="undoRemoveBtn">Undo</button>
                    </div>
                    <div class="alert alert-info mt-2">
                        Total Trays Added: <b><span id="totalTrayCount">0</span></b>
                    </div>
                    <table class="table table-bordered" id="sowingtrayinformation">
                        <thead>
                            <tr>
                                <th>Stock Order No</th>
                                <th>Tray QR Code</th>
                                <th>Seed Lot No</th>
                                <th>Product Name</th>
                                <th>Tray Cavity</th>
                                <th>Tray Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                    
                </div>
            </div>
            <div class="row">
                <div class="col-md-3"></div>
                <div class="col-md-6 text-center">
                    <button class="btn btn-primary mt-3 d-none" id="saveTrayAllocation">
                        Save & Continue Allocation
                    </button>
                    <button class="btn btn-warning mt-3" id="updateTrayAllocation">
                        Update Allocation
                    </button>
                </div>
                <div class="col-md-3"></div>
            </div>
        </div>
        <div id="FetchedTrayDetailsToPH" class="card mt-3 shadow-sm" style="display:none;">
            <div class="card-body mt-4">
                <h5 class="card-title text-center">Allocation of Tray in Polyhouse</h5>
                <div class="row mt-4">
                    <div class="col-md-12 mb-3  alert alert-success">
                        <label class="font-weight-bold">Tray Germination Failure Status:</label>
                        <div class="d-flex flex-wrap gap-3 mt-2">
                            <label class="me-4">
                                <input type="radio" name="traygerfailurestatus" class="traygerfailurestatus" value="Healthy" checked>
                                <i class="fas fa-leaf text-success"></i> Healthy
                            </label>

                            <label class="me-4">
                                <input type="radio" name="traygerfailurestatus" class="traygerfailurestatus" value="Dead">
                                <i class="fas fa-skull-crossbones text-dark"></i> Dead
                            </label>

                            <label class="me-4 d-none">
                                <input type="radio" name="traygerfailurestatus" class="traygerfailurestatus" value="Under Observation">
                                <i class="fas fa-eye text-info"></i> Under Observation
                            </label>
                        </div>
                    </div>
                </div>
                <div class="row mt-1">
                    <div class="col-md-12">
                        <div id="polyhousecapacitydetailsdiv" class="mt-4"></div>
                        <div class="ph-box remaining">
                            <span>Ready to Update Trays</span>
                            <strong id="readytoupdatetraysspan"></strong>
                        </div>
                    </div>
                </div>
                <div class="row mt-1" id="trayallocationdivhtml">
                    <div class="col-md-6 alert alert-info">
                        <p><b>Which row want to put tray of Polyhouse:</b></p>
                        <select class="form-control" id="Polyhouserowno"></select>
                    </div>
                    <div class="col-md-6 alert alert-info">
                        <p><b>What is the position of Entered Row:</b></p>
                        <label><input type="radio" name="traypositioninrow" value="start" /> Start of the Row</label>
                        <label><input type="radio" name="traypositioninrow" value="middle" /> Middle of the Row</label>
                        <label><input type="radio" name="traypositioninrow" value="end" /> End of the Row</label>
                    </div>
                </div>
                <div class="row pb-5">                        
                    <div class="col-md-12 mt-4 text-center">
                        <button class="btn btn-success" data-totaltrayinbox="" data-allocatedrayfrombox="" data-polyhouseid="" data-gboxid="" data-stockorderno="" id="UpdateTrayallocationbtn" onclick="UpdateTrayAllocationToPolyhouse('traygerfailurestatus','Polyhouserowno','traypositioninrow','UpdateTrayallocationbtn','sowingtrayinformation')">Submit Tray Allocation</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ACTIONS -->
        <div id="BoxActionBtn" class="d-flex justify-content-between mt-3" style="display:none;">
          <button id="UnlockScannerTrayAllocation" class="btn btn-secondary btn-sm d-none">🔓 Unlock</button>
          <button id="toggleFlash" class="btn btn-warning btn-sm">🔦 Flash</button>
          <input type="hidden" id="gboxidhidden" />
        </div>

        

        <audio id="beepSound" preload="auto">
            <source src="https://actions.google.com/sounds/v1/ui/confirmation_tone.ogg" type="audio/ogg">
        </audio>

      </div>
    </div>
  </div>
</div>


<?php include('../footer.php'); ?>
<script type="text/javascript" src="<?php echo $prefix;?>krf-orderandsales/js/pjs_function.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<!-- QR Scanner (html5-qrcode) -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<!-- <script type="text/javascript" src="<?php echo $prefix;?>krf-orderandsales/js/qrcodegenrator.js"></script> -->

<script>
let qrReader = null;
let isScanning = false;
let scanLocked = false;
let torchOn = false;

let scannedQRSet = new Set();
let lastRemovedRow = null;
let undoTimer = null;

$(document).ready(function () {
    window.initScanner = function () {
        if (typeof Html5Qrcode === "undefined") {
            alert("QR Scanner library not loaded.");
            return;
        }
        if (isScanning) return;
        isScanning = true;
        if (qrReader) {
            try { qrReader.stop(); } catch (e) {}
        }
        qrReader = new Html5Qrcode("AllocationReaderId");
        qrReader.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: 250 },
            onScanSuccess,
            onScanFailure
        ).then(() => {
            console.log("📷 Camera started");
            $("#AllocationstartScan").hide();
            $("#AllocationstopScan").show();
            /* ✅ SAFE: access running track AFTER camera starts */
            try {
                const track = qrReader.getRunningTrack();
                if (track) {
                    const capabilities = track.getCapabilities();
                    console.log("📸 Camera capabilities:", capabilities);

                    // Optional: auto-hide flash button if not supported
                    if (!capabilities.torch) {
                        $("#toggleFlash").hide();
                    }
                }
            } catch (e) {
                console.warn("Could not read camera capabilities", e);
            }

        }).catch(err => {
            isScanning = false;
            Swal.fire('Permission Denied', 'Camera permission denied or unavailable.', 'warning');
            // console.error(err);
        });
    };
    window.stopScanner = function () {
        if (qrReader && isScanning) {
            qrReader.stop().then(() => {
                isScanning = false;
                $("#AllocationstartScan").show();
                $("#AllocationstopScan").hide();
            }).catch(() => {});

            try {
                const track = qrReader.getRunningTrack();
                if (track) {
                    track.applyConstraints({ advanced: [{ torch: false }] });
                }
            } catch (e) {}
        }
        torchOn = false;
    };

    function onScanSuccess(decodedText) {
        // 🔐 If locked, ignore scans
        if (scanLocked) {
            console.log("🔐 Scan locked");
            return;
        }
        console.log("✅ QR Scanned:", decodedText);
        // 🔐 LOCK AFTER FIRST SUCCESS
        scanLocked = true;
        // 🛑 Stop scanner
        if (qrReader) {
            qrReader.stop().then(() => {
                isScanning = false;
                $("#AllocationstartScan").prop("disabled", true);
                $("#AllocationstopScan").hide();
            });
        }

        // 🔊 Optional beep
        if (document.getElementById("beepSound")) {
            document.getElementById("beepSound").play();
        }

        // 📳 Optional vibration
        if (navigator.vibrate) {
            navigator.vibrate(200);
        }
        fetchGerminationBoxTrayWithQRCode(decodedText);
    }
    
    function onScanFailure(error) {
        // Normal behaviour – ignore
    }
    // on click ToggelFlash
    $("#toggleFlash").on("click", async function () {
        if (!qrReader || !isScanning) {
            alert("Scanner not running");
            return;
        }
        try {
            const track = qrReader.getRunningTrack();
            if (!track) {
                Swal.fire('Camera Issue', 'Camera track not available', 'warning');
                return;
            }
            const caps = track.getCapabilities ? track.getCapabilities() : {};
            if (!caps.torch) {
                Swal.fire('Flashlight Not Supported', 'This device does not support flashlight.', 'warning');
                return;
            }
            torchOn = !torchOn;
            await track.applyConstraints({
                advanced: [{ torch: torchOn }]
            });
            $(this)
                .toggleClass("btn-warning btn-danger")
                .text(torchOn ? "🔦 Flash OFF" : "🔦 Flash ON");

        } catch (err) {
            // 🔥 MOST IMPORTANT PART
            console.warn("Torch not allowed:", err);
            torchOn = false;
            // UX-friendly message
            Swal.fire('Flashlight Not Supported', 'Flashlight control is not supported on this device or browser.', 'warning');
        }
    });

    function fetchGerminationBoxTrayWithQRCode(qrCodeValue) {
        if (!qrCodeValue) {
            $("#noResult").show().text("Invalid QR Code.");
            return;
        }
        if (scannedQRSet.has(qrCodeValue)) {
            $("#noResult").show().text("⚠️ This QR is already added.");
            return;
        }
        let routurl      = $("body").data("routurl");
        let companyid    = $("body").data("companyid");
        let subcompanyid = $("body").data("subcompanyid");
        let projectid    = $("body").data("projectid");
        let gboxid = $("body").data("gboxid");
        $.ajax({
            url: routurl + "krf-orderandsales/modal/polihouse_modal.php?action=fetchGerminationBoxTrayWithQRCode",
            type: "POST",
            dataType: "json",
            data: { qrcode: qrCodeValue, companyid, subcompanyid, projectid },
            success: function (res) {
                console.log(res);
                if (!res || res.status === "error") {
                    $("#noResult").show().text("No Box found.");
                    return;
                }
                scannedQRSet.add(qrCodeValue);
                let rowHtml = `
                    <tr data-stockorderno="${res.stockorderno}" data-qrcode="${res.trayqrcode}" data-boxid="${gboxid}">
                        <td>${res.stockorderno}</td>
                        <td>${res.trayqrcode}</td>
                        <td>${res.seedlotno}</td>
                        <td>${res.productname}</td>
                        <td>${res.traycavity}</td>
                        <td>${res.traystatus}</td>                    
                        <td>
                            <button class="btn btn-sm btn-danger removeTrayRow">Remove</button>
                        </td>
                    </tr>
                `;
                $("#sowingtrayinformation tbody").append(rowHtml);
                updateTrayCount();
                $("#FetchedSowingTrayDetails").show();

                $("#manualTrayQrCode").val('');
                $("#manualTrayQrCode").focus();

                $("#noResult")
                    .removeClass("alert-warning")
                    .addClass("alert-success")
                    .text("✅ QR added successfully.")
                    .show();
            }
        });
    }

    // function updateTrayCount() {
    //     let total = 0;
    //     let totaltrayinbox = parseInt($("#germTrayCountinbox").html()) || 0;
    //     let allocatedtotaltrayinbod = parseInt($("#allocatedTrayCountofobox").html()) || 0;
    //     let resttoscan = parseInt(totaltrayinbox - allocatedtotaltrayinbod) 
    //     $("#sowingtrayinformation tbody tr").each(function () {
    //         total++;    
    //     });
    //     $("#totalTrayCount").text(total);
    //     if(resttoscan===total){
    //        $("#noResult").removeClass("alert-warning").addClass("alert-success").text("✅ All tray scanned save for allocation.").show();
    //     }
    // }

    
    function updateTrayCount() {
        let trayScanCompleted = false; // prevent multiple popups
        let totalScanned = 0;
        const totalTrayInBox =
            parseInt($("#germTrayCountinbox").text(), 10) || 0;
        const allocatedTrayCount =
            parseInt($("#allocatedTrayCountofobox").text(), 10) || 0;
        const restToScan = totalTrayInBox - allocatedTrayCount;
        $("#sowingtrayinformation tbody tr").each(function () {
            totalScanned++;
        });
        $("#totalTrayCount").text(totalScanned);
        // ✅ All trays scanned
        if (restToScan === totalScanned && totalTrayInBox > 0) {
            $("#noResult")
                .removeClass("alert-warning")
                .addClass("alert-success")
                .text("✅ All trays scanned. Ready for allocation update.")
                .show();

            // 🔔 SweetAlert popup (only once)
            if (!trayScanCompleted) {
                trayScanCompleted = true;
                Swal.fire({
                    icon: "success",
                    title: "Germination Box Completed",
                    text: "All trays have been scanned successfully. Please update allocation after scanning.",
                    confirmButtonText: "OK",
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        const target = document.getElementById('FetchedTrayDetailsToPH');
                        if (target) {
                            target.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                        }
                    }
                });
            }
        }
    }


    $(document).on("click", ".removeTrayRow", function () {
        let $row = $(this).closest("tr");
        let qr = $row.data("qrcode");
        lastRemovedRow = $row.clone(true);
        scannedQRSet.delete(qr);
        $row.remove();
        updateTrayCount();
        $("#undoToast").removeClass("d-none");
        clearTimeout(undoTimer);
        undoTimer = setTimeout(() => {
            lastRemovedRow = null;
            $("#undoToast").addClass("d-none");
        }, 5000);
        if ($("#sowingtrayinformation tbody tr").length === 0) {
            $("#FetchedSowingTrayDetails").hide();
        }
    });

    $("#undoRemoveBtn").on("click", function () {
        if (lastRemovedRow) {
            $("#sowingtrayinformation tbody").append(lastRemovedRow);
            scannedQRSet.add(lastRemovedRow.data("qrcode"));
            updateTrayCount();
        }
        lastRemovedRow = null;
        $("#undoToast").addClass("d-none");
    });

    $("#saveTrayAllocation").on("click", function () {
        let routurl      = $("body").data("routurl");
        let companyid    = $("body").data("companyid");
        let subcompanyid = $("body").data("subcompanyid");
        let projectid    = $("body").data("projectid");
        let loginid    = $("body").data("loginid");
        let gboxid = $("body").data("gboxid");
        let trayData = [];
        $("#sowingtrayinformation tbody tr").each(function () {
            trayData.push({
                qrcode: $(this).data("qrcode"),
                boxid: $(this).data("boxid"),
                stockorderno: $(this).data("stockorderno"),
            });
        });

        if (trayData.length === 0) {
            alertmessage("warning","No trays to save.");
            return;
        }
        $.ajax({
            url: routurl + "krf-orderandsales/modal/polihouse_modal.php?action=saveTrayAllocation",
            type: "POST",
            dataType: "json",
            data: { trayData: JSON.stringify(trayData), companyid: companyid, subcompanyid: subcompanyid, projectid: projectid, loginid: loginid, gboxid: gboxid},

            success: function (res) {
                if (res.status === "success") {
                    alertmessage("success","Saved successfully.");
                    let restulsdata = res.data;
                    $("#updateTrayAllocation").trigger("click");
                    // GetPolihouseDetailsList('polyhouseid',restulsdata.polyhouseid,'','Polyhouserowno');
                }else{
                    alertmessage("warning",res.message);
                }
            }
        });
    });

    
    $("#updateTrayAllocation").on("click", function(){
        // $("#FetchedSowingTrayDetails").hide();
        $("#UpdateTrayallocationbtn").prop("disabled", false).removeClass("btn-primary").addClass("btn-success");
        $("#FetchedTrayDetailsToPH").show();
        let polyhouseid = $("body").data("polyhouseid");
        let gboxid = $("body").data("gboxid");
        let stockorderno = $("body").data("stockorderno");
        $("#UpdateTrayallocationbtn").data("polyhouseid", polyhouseid);
        $("#UpdateTrayallocationbtn").data("gboxid", gboxid);
        $("#UpdateTrayallocationbtn").data("stockorderno", stockorderno);
        let readytouptrays = $("#sowingtrayinformation tbody tr").length;
        $("#readytoupdatetraysspan").html('Ready to Update Trays: '+readytouptrays);
        PolyhouseTrayUsageDetailsFromPolyhousePlant(polyhouseid, function (plyres) {
            if (!plyres || !plyres.polyhouseid) {
                $("#polyhousecapacitydetailsdiv").html(
                    `<div class="capacity-error">No data found</div>`
                );
                return;
            }
            renderPolyhouseCapacityDetails(plyres,'polyhousecapacitydetailsdiv');
        });
        loadPolyhouseDetails('polyhouseid', polyhouseid, function (res) {
            if (!res || !res.length || !res[0].beddetails) {
                console.warn("No bed details found");
                return;
            }
            let polybeddata = res[0].beddetails;
            if (typeof polybeddata === "string") {
                try {
                    polybeddata = JSON.parse(polybeddata);
                } catch (e) {
                    console.error("Invalid beddetails JSON", e);
                    polybeddata = [];
                }
            }
            let $rowSelect = $("#Polyhouserowno");
            $rowSelect.empty();
            $rowSelect.append(`<option value="">Select Bed / Rack</option>`);
            if (Array.isArray(polybeddata) && polybeddata.length > 0) {
                $.each(polybeddata, function (index, bed) {
                    if (!bed.bedname) return;
                    $rowSelect.append(`
                        <option value="${bed.bedname}"
                                data-layercount="${bed.layercount || 0}"
                                data-traycapacity="${bed.traycapacity || 0}">
                            ${bed.bedname}
                        </option>
                    `);
                });

            } else {
                $rowSelect.append(`<option value="">No Bed / Rack Available</option>`);
            }
        });

        setTimeout(() => {
            const target = document.getElementById('FetchedTrayDetailsToPH');
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }, 100);

    })

    // BUTTON EVENTS
    $("#AllocationstartScan").on("click", function () {
        window.initScanner();
        $("#qrcodescannerdiv").show();
        $("#manualQrCodeSearchDiv").hide();
    });
    

    $("#AllocationstopScan").on("click", function () {
        window.stopScanner();
    });

    $("#ManualAllocationstartScan").on("click", function () {
        // window.stopScanner();
        $("#allocationqrcodescannerdiv").hide();
        $("#manualQrCodeSearchDiv").show();
        $("#UnlockScannerTrayAllocation").show();
        $("#manualTrayQrCode").focus();
    }); 
    

    $("#QRcodeAllocationstartScan").on("click", function () {
        window.initScanner();
        $("#allocationqrcodescannerdiv").show();
        $("#manualQrCodeSearchDiv").hide();
    });   
    

    // $("#TrayAllocationfetchManual").on("click", function () {
    //     fetchGerminationBoxTrayWithQRCode($("#manualTrayQrCode").val().trim());
    // });

    $("#manualTrayQrCode").on("input", function () {
        const qrValue = $(this).val().trim();
        if (qrValue.length > 22) {
            fetchGerminationBoxTrayWithQRCode(qrValue);
            $("#manualTrayQrCode").val('');
        }
    });
    
    $("#TrayAllocationfetchManual").on("click", function () {
        const qrValue = $("#manualTrayQrCode").val().trim();
        fetchGerminationBoxTrayWithQRCode(qrValue);
        $("#manualTrayQrCode").val('');
    });


    $("#UnlockScannerTrayAllocation").on("click", function () {
        scanLocked = false;
        $("#AllocationstartScan").prop("disabled", false);
        $("#FetchedTrayDetailsToPH").html('').hide();
        $("#UnlockScannerTrayAllocation").hide();
        $("#noResult").html('').hide();
        $("#allocationqrcodescannerdiv").show();
        $("#manualQrCodeSearchDiv").hide();
        $("#manualTrayQrCode").val('');
        window.initScanner();
    });

    $("#manualTrayQrCode").on("keypress", function (e) {
        if (e.which === 13) $("#TrayAllocationfetchManual").click();
    });
});


$(document).ready(function () {
    let projectid = $("body").data("projectid");
    GetPendingTrayAllocationofGBoxes(function (res) {
        let prdotr = "";
        if (Array.isArray(res) && res.length > 0) {
            for (let i = 0; i < res.length; i++) {
                prdotr += `
                    <tr
                        data-polyhouseid="${res[i].polyhouseid}"
                        data-productionstockorderno="${res[i].stockorderno}"
                        data-boxid="${res[i].boxid}"
                        data-productid="${res[i].productid}"
                        data-allocatedtraycount="${res[i].allocated_traycount}"
                        data-traycountinbox="${res[i].traycountinbox}"
                        data-productname="${res[i].productname}"
                    >
                        <td>${changeDateFormatYmd_dMY(res[i].boxopeningdate)}</td>
                        <td>${res[i].productname}</td>
                        <td>${res[i].stockorderno}</td>
                        <td>${res[i].qrcode}</td>
                        <td>${res[i].traycountinbox}</td>
                        <td>${res[i].allocated_traycount}</td>
                        <td><button class="btn btn-sm btn-primary allocatedTraytoPhbtn"
                           data-boxid="${res[i].boxid}" data-traycountinbox="${res[i].traycountinbox}" data-allocatedtraycount="${res[i].allocated_traycount}">
                            Allocate Tray To Green House
                        </button></td>
                    </tr>
                `;
            }

        } else {
            prdotr = `
                <tr>
                    <td colspan="6" class="text-center text-muted">
                        No records found
                    </td>
                </tr>
            `;
        }

        $("#PendingAllocationTraytbl tbody").html(prdotr);
    });


    $(document).on("click", ".traygerfailurestatus", function(){
        $("#trayallocationdivhtml").hide();
        if($(this).val()=="Dead"){
            $("#trayallocationdivhtml").hide();
        }
    });

    $(document).on("click", ".allocatedTraytoPhbtn", function(){
        // window.initScanner();
        $("#allocationTrayToPHModal").modal("show");
        let stockorderno = $(this).closest("tr").data("productionstockorderno");
        let boxid = $(this).closest("tr").data("boxid");        
        let allocatedtraycount = $(this).closest("tr").data("allocatedtraycount");        
        let traycountinbox = $(this).closest("tr").data("traycountinbox");
        let productnamelb = $(this).closest("tr").data("productname");
        $("#productNamemdalc").html(productnamelb);
        $("#productOrderNoalc").html(stockorderno);
        $("#germTrayCountinbox").html(traycountinbox);
        $("#allocatedTrayCountofobox").html(allocatedtraycount);
        $("#UpdateTrayallocationbtn").data("totaltrayinbox", traycountinbox);
        $("#UpdateTrayallocationbtn").data("allocatedrayfrombox", allocatedtraycount);
        // alert(boxid);
        $("#gboxidhidden").val(boxid);
        $("body").data('gboxid', $(this).closest("tr").data("boxid"));
        $("body").data('stockorderno', $(this).closest("tr").data("productionstockorderno"));
        $("body").data('polyhouseid', $(this).closest("tr").data("polyhouseid"));        
        $('#allocationTrayToPHModal').on('shown.bs.modal', function () {
            $('#manualTrayQrCode').trigger('focus');
        });
        updateTrayAllocationProgress(allocatedtraycount, traycountinbox);
    });    
});

function onValidScanSuccess(current, total) {
  document.getElementById("beepSound").play();
  const box = document.getElementById("AllocationReaderId");
  box.classList.add("scan-success");
  setTimeout(()=>box.classList.remove("scan-success"),800);

  const percent = Math.min((current / total) * 100, 100);
  document.getElementById("allocationProgressBar").style.width = percent + "%";
}

function updateTrayAllocationProgress(allocated, total) {
    allocated = parseInt(allocated) || 0;
    total     = parseInt(total) || 0;

    let percent = 0;
    if (total > 0) {
        percent = Math.round((allocated / total) * 100);
    }

    $("#allocationProgressBar")
        .css("width", percent + "%")
        .attr("aria-valuenow", percent)
        .text(percent + "%");

    $("#allocatedCountTxt").text(allocated);
    $("#totalCountTxt").text(total);
}
</script>
