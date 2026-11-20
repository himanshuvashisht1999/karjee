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
<style>
/* =========================================================
   GRAFTING MODALS - DESKTOP + MOBILE
========================================================= */

/* Main allocation modal */
#labourdetails .modal-dialog {
    width: 90%;
    max-width: 1250px;
    height: calc(100vh - 30px);
    margin: 15px auto;
}

#labourdetails .modal-content {
    max-height: calc(100vh - 30px);
    overflow: hidden;
}

#labourdetails .modal-body {
    overflow-y: auto !important;
    overflow-x: hidden;
    min-height: 0;
}


/* Labour summary child modal */
#labourGraftingSummaryModal {
    z-index: 1070;
}

#labourGraftingSummaryModal .modal-dialog {
    width: 90%;
    max-width: 800px;
    max-height: calc(100vh - 40px);
    margin: 20px auto;
}

#labourGraftingSummaryModal .modal-content {
    max-height: calc(100vh - 40px);
    overflow: hidden;
}

#labourGraftingSummaryModal .modal-body {
    overflow-y: auto !important;
    overflow-x: auto;
    min-height: 0;
}


/* Child modal backdrop above parent modal */
#labourGraftingSummaryModal + .modal-backdrop {
    z-index: 1065;
}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 768px) {

    #labourdetails .modal-dialog {
        width: 96% !important;
        max-width: 96% !important;
        height: calc(100vh - 16px);
        margin: 8px auto;
    }

    #labourdetails .modal-content {
        max-height: calc(100vh - 16px);
    }

    #labourdetails .modal-header,
    #labourdetails .modal-footer {
        flex-shrink: 0;
    }

    #labourdetails .modal-body {
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch;
    }


    #labourGraftingSummaryModal .modal-dialog {
        width: 96% !important;
        max-width: 96% !important;
        max-height: calc(100vh - 16px);
        margin: 8px auto;
    }

    #labourGraftingSummaryModal .modal-content {
        max-height: calc(100vh - 16px);
    }

    #labourGraftingSummaryModal .modal-body {
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch;
    }

}
</style>
<div class="container-main">
  <div class="row <?php echo isset($_GET['dz'])?'d-none':'' ?>">
    <div class="col-md-12">
      <a href="grafting_submission_form.php?k=<?php echo $key; ?>&grpid=<?php echo $groupid; ?>&mdid=<?php echo $mdid; ?>" class="btn btn-sm btn-deepblue m-2 float-right">Grafting Submission </a>
      <button class="btn btn-sm btn-deepblue m-2 float-right" onclick="location.reload();">Refresh</a></button>
    </div>
  </div>
  <div class="card mt-1"> 
      <form id="dailyGraftingAllocationForm" method="POST" enctype="multipart/form-data">
          <div class="row">
            <div class="col-md-12 text-center">
              <h4>Start-of-Day Grafting Allocation</h4>
            </div>
          </div>
          <div class="row mt-2">
              <div class="col-md-4">
                  <label for="graftingdate" class="form-label">Grafting Date <span class="text-danger">*</span></label>
                  <input type="date" class="form-control" id="graftingdate" name="graftingdate" value="<?php echo date('Y-m-d'); ?>"  min  ="<?php echo date('Y-m-d'); ?>" max ="<?php echo date('Y-m-d'); ?>"/>
              </div>
              <div class="col-md-4">
                  <label for="locationid" class="form-label">Location <span class="text-danger">*</span></label>
                  <select class="form-control" id="locationid" name="locationid" required></select>
              </div>
           
          </div>

          <div class="row" id="graftingDetailsWindow">
              <div  class="table-responsive" style="height:60vh;overflow-y:auto;">
                  <table class="table table-hover table-bordered shadow-sm" id="tbldetails">
                      <thead class="sticky-top thead-dark">
                          <tr>
                              <th class="sticky-top text-center">
                                  S.No
                              </th>
                              <th class="sticky-top text-center">
                                  #
                              </th>
                              <th class="sticky-top text-start">
                                  Stock Order No.
                              </th>
                              <th class="sticky-top text-center">
                                  Product Name
                              </th>
                              <th class="sticky-top text-center">
                                  Total Root Tray
                              </th>
                              <th class="sticky-top text-center">
                                  Total Root Plant(S)
                              </th>
                              <th class="sticky-top text-center">
                                  Total Scion Tray
                              </th>
                              <th class="sticky-top text-center">
                                  Total Scion Plant(S)
                              </th>
                          </tr>
                      </thead>
                      <tbody></tbody>
                      <tfoot></tfoot>
                  </table>
              </div>
          </div> 
        <div class="modal fade"  id="labourdetails"  tabindex="-1"  aria-labelledby="labourdetailsLabel"  aria-hidden="true"  data-bs-backdrop="static" data-bs-keyboard="false"> 
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable"  style="width:90%; max-width:1250px;">
                <div class="modal-content">
                    <!-- ================= HEADER ================= -->
                    <div class="modal-header py-2">
                        <div>
                            <h5 class="modal-title mb-0"
                                id="labourdetailsLabel">
                                Grafting Labour Allocation
                            </h5>
                            <small class="text-muted">
                                Allocate labour and grafted tray targets for the selected stock order.
                            </small>
                        </div>
                        <button type="button"
                                class="btn-close"
                                id="btnCloseGraftingAllocation"
                                aria-label="Close">
                        </button>

                    </div> 
                    <div class="modal-body"> 
                        <div class="card border mb-3">
                            <div class="card-header py-2 bg-light">
                                <strong>
                                    <i class="fa fa-info-circle me-1"></i>
                                    Selected Grafting Order
                                </strong>
                            </div>
                            <div class="card-body py-2">
                                <div class="row g-2">
                                    <!-- Stock Order -->
                                    <div class="col-md-3">
                                        <small class="text-muted d-block">
                                            Stock Order
                                        </small>
                                        <span id="modal_stockorder"
                                              class="fw-bold">
                                            -
                                        </span>
                                    </div> 
                                    <!-- Product -->
                                    <div class="col-md-5">
                                        <small class="text-muted d-block">
                                            Product
                                        </small>

                                        <span id="modal_productname"
                                              class="fw-bold">
                                            -
                                        </span>
                                    </div>


                                    <!-- Location -->
                                    <div class="col-md-4">
                                        <small class="text-muted d-block">
                                            Location
                                        </small>

                                        <span id="modal_locationname"
                                              class="fw-bold">
                                            -
                                        </span>
                                    </div>

                                </div>


                                <hr class="my-2">


                                <div class="row g-2 text-center">

                                    <div class="col-md-3">
                                        <small class="text-muted d-block">
                                            Root Trays
                                        </small>

                                        <span id="modal_roottray"
                                              class="fw-bold fs-5">
                                            0
                                        </span>
                                    </div>


                                    <div class="col-md-3">
                                        <small class="text-muted d-block">
                                            Root Plants
                                        </small>

                                        <span id="modal_rootplants"
                                              class="fw-bold fs-5">
                                            0
                                        </span>
                                    </div>


                                    <div class="col-md-3">
                                        <small class="text-muted d-block">
                                            Scion Trays
                                        </small>

                                        <span id="modal_sciontray"
                                              class="fw-bold fs-5">
                                            0
                                        </span>
                                    </div>


                                    <div class="col-md-3">
                                        <small class="text-muted d-block">
                                            Scion Plants
                                        </small>

                                        <span id="modal_scionplants"
                                              class="fw-bold fs-5">
                                            0
                                        </span>
                                    </div>

                                </div>

                            </div>
                        </div>


                        <!-- =========================================
                             PRODUCTION DETAILS
                        ========================================== -->

                        <div id="showproductiondetails"
                             class="mb-2">
                        </div>


                        <!-- =========================================
                             GRAFTED TRAY TYPE
                        ========================================== -->

                        <div class="card border mb-3">

                            <div class="card-body py-2">

                                <div class="row align-items-center">

                                    <div class="col-md-2">

                                        <label for="graftedtraylist"
                                               class="form-label fw-bold mb-0">

                                            Grafted Tray Type

                                            <span class="text-danger">*</span>

                                        </label>

                                    </div>


                                    <div class="col-md-3">

                                        <select id="graftedtraylist"
                                                class="form-control">

                                            <option value="">
                                                -- Select Tray --
                                            </option>

                                        </select>
                                    </div>    
                                    <div class="col-md-7">
                                        <div id="graftedTrayInfo"
                                             class="form-text">
                                        </div>

                                    </div>

                                </div>

                            </div>
                        </div>


                        <!-- =========================================
                             LABOUR DETAILS
                        ========================================== -->

                        <div class="card border">

                            <div class="card-header py-2 bg-light">

                                <strong>
                                    <i class="fa fa-users me-1"></i>
                                    Labours For Grafting Allocation
                                </strong>

                            </div>


                            <div class="card-body p-2">

                                <div class="table-responsive"
                                     style="max-height:45vh; overflow-y:auto;">

                                    <table class="table table-bordered table-hover mb-0"
                                           id="allocationdailyGraftingTable">

                                        <thead class="table-light sticky-top">

                                            <!-- FILTER -->
                                            <tr>

                                                <th colspan="5">

                                                    <div class="row align-items-center">

                                                        <div class="col-md-2">
                                                            <label class="mb-0 fw-bold">
                                                                Filter
                                                            </label>
                                                        </div>

                                                        <div class="col-md-10">

                                                            <input type="text"
                                                                   id="allocationgrftinput"
                                                                   oninput="filterTable('#allocationgrftinput', '#allocationdailyGraftingTable');"
                                                                   class="form-control"
                                                                   placeholder="Type for Searching ......">

                                                        </div>

                                                    </div>

                                                </th>

                                            </tr>


                                            <!-- COLUMN HEADER -->
                                            <tr>

                                                <th style="padding:0.5rem;width:60px;"
                                                    class="text-center">
                                                    Select
                                                </th>

                                                <th style="padding:0.5rem;min-width:200px;">
                                                    Labour Name
                                                </th>
                                                <th style="padding:0.5rem;min-width:50px;">
                                                    Day Target
                                                </th>

                                                <th style="padding:0.5rem;width:130px;">
                                                    Allocation Time
                                                </th>


                                                <th style="padding:0.5rem;width:150px;">
                                                    Grafted Tray Count
                                                </th>

                                                <th style="padding:0.5rem;width:150px;">
                                                    Root Tray Assign
                                                </th>
                                                <th style="padding:0.5rem;width:150px;">
                                                    Scion Tray Assign
                                                </th>


                                                <th style="padding:0.5rem;width:110px;"
                                                    class="text-center">
                                                     
                                                </th>

                                            </tr>

                                        </thead>


                                        <tbody>

                                            <tr>
                                                <td colspan="5"
                                                    class="text-center text-muted py-3">

                                                    No data available

                                                </td>
                                            </tr>

                                        </tbody>
                                        <tfoot>
                                            <tr class="table-light fw-bold">

                                                <td colspan="2" class="text-end">
                                                    Grafting Tray Target
                                                </td>

                                                <td colspan="3">

                                                    Max:
                                                    <span id="ftMaxGraftedTray">0</span>

                                                    &nbsp; | &nbsp;

                                                    Allocated:
                                                    <span id="ftAllocatedGraftedTray">0</span>

                                                    &nbsp; | &nbsp;

                                                    Balance:
                                                    <span id="ftBalanceGraftedTray">0</span>

                                                </td>

                                            </tr>
                                        </tfoot>

                                    </table>

                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- ================= FOOTER ================= -->

                    <div class="modal-footer py-2">

                        <button type="button"
                                class="btn btn-secondary"
                                id="btnCancelGraftingAllocation">

                            <i class="fa fa-times me-1"></i>
                            Cancel

                        </button>
                        <button type="button"
                                id="btnPrintAllGraftingLabels"
                                class="btn btn-primary d-none">

                            <i class="fa fa-print me-1"></i>
                            Print All

                        </button>


                        <button type="button"
                                id="updategraftingallocationdetailsbtn"
                                class="btn btn-deepblue d-none">

                            <i class="fa fa-save me-1"></i>
                            Save Daily Grafting Allocation Details

                        </button>

                    </div>

                </div>

            </div>

        </div>
      </form>  
       <!-- =========================================================
     LABOUR DAILY GRAFTING SUMMARY MODAL
========================================================== -->
<div class="modal fade"
     id="labourGraftingSummaryModal"
     tabindex="-1"
     aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">

        <div class="modal-content">

            <div class="modal-header py-2">

                <div>
                    <h5 class="modal-title mb-0">
                        Labour Daily Grafting Summary
                    </h5>

                    <small class="text-muted"
                           id="graftingSummaryLabourName">
                    </small>
                </div>
 

                <button type="button"
                        class="btn btn-secondary"
                        onclick="closeLabourGraftingHistoryModal()">
                    Close
                </button>
            </div>


            <div class="modal-body">

                <!-- SUMMARY -->
                <div class="row g-2 mb-3">

                    <div class="col-md-4">
                        <div class="border rounded p-2 text-center">

                            <small class="text-muted d-block">
                                Day Target
                            </small>

                            <span class="fw-bold fs-5"
                                  id="summaryDayTarget">
                                0
                            </span>

                        </div>
                    </div>


                    <div class="col-md-4">
                        <div class="border rounded p-2 text-center">

                            <small class="text-muted d-block">
                                Total Assigned
                            </small>

                            <span class="fw-bold fs-5"
                                  id="summaryTotalAssigned">
                                0
                            </span>

                        </div>
                    </div>


                    <div class="col-md-4">
                        <div class="border rounded p-2 text-center">

                            <small class="text-muted d-block">
                                Balance
                            </small>

                            <span class="fw-bold fs-5"
                                  id="summaryBalance">
                                0
                            </span>

                        </div>
                    </div>

                </div>


                <!-- DETAILS -->
                <div class="table-responsive">

                    <table class="table table-bordered table-hover mb-0">

                        <thead class="table-light">

                            <tr>
                                <th>Product</th>
                                <th>Stock Order No.</th>
                                <th class="text-center">
                                    Assigned Qty
                                </th>
                                <th class="text-center">
                                    Root Assigned
                                </th>
                                <th class="text-center">
                                    Scion Assigned
                                </th>
                                <th class="text-center">
                                    Allocation Time
                                </th>
                            </tr>

                        </thead>

                        <tbody id="labourGraftingSummaryBody">

                            <tr>
                                <td colspan="6"
                                    class="text-center text-muted">
                                    No allocation found.
                                </td>
                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>


            <div class="modal-footer py-2">

                <button type="button"
                        class="btn btn-warning"
                        onclick="closeLabourGraftingHistoryModal()">
                    Close
                </button>

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
  let activeGraftingCheckbox = null;
let graftingAllocationSaved = false;

$(document).ready(function(){ 

  LocationMastercallback("Grafting",function(res){
        var locationlist = '<option value="">-- Select Location --</option>'; // default option
        $("#locationid").empty();
        if(res.status === "success"){
          if(res.data.length > 0){
            resnew=res.data;
            for (var i = 0; i < resnew.length; i++) {
              locationlist += '<option value="' + resnew[i].locationid + '">' + resnew[i].locationname + '</option>';
            }
          }
          $("#locationid").append(locationlist);
          cavitylist="";
          debugger;
          if(res.traycavity.length > 0){
            resnew=res.traycavity;
            for (var i = 0; i < resnew.length; i++) {
               cavitylist += `
                  <option
                      value="${resnew[i].tray_id}"
                      data-code="${resnew[i].tray_code || ''}"
                      data-name="${resnew[i].tray_name || ''}"
                      data-cavity="${resnew[i].cavity_count || 0}"
                      data-cavitysize="${resnew[i].cavity_size || ''}"
                      data-material="${resnew[i].material || ''}"
                      data-category="${resnew[i].category_name || ''}">
                      ${resnew[i].tray_name}
                      (${resnew[i].cavity_count} Cavity)
                  </option>
              `;
            }
            $("#graftedtraylist").append(cavitylist);

          }

        } 
    });
    function  LocationMastercallback(plantstatus,callback) {
        var form_data = new FormData(); 
        var companyid = $("body").data("companyid");
        var subcompanyid = $("body").data("subcompanyid");
        var projectid = $("body").data("projectid");

        var routurl = $("body").data("routurl");
        form_data.append("companyid", companyid);
        form_data.append("subcompanyid", subcompanyid);
        form_data.append("projectid", projectid); 
        form_data.append("plantstatus", plantstatus);
        $.ajax
            ({
                type: "POST", 

                url: `${routurl}krf-orderandsales/functions/grafting_view.php?action=getlocation`,
                data: form_data,
                contentType: false,
                processData: false,
                cache: false,
                dataType: 'JSON',
                success: function (res) {
                    callback(res);
                }
            });
    } 
    $(document).on("change", "#locationid", function () {
        const locationid = $(this).val();
        const projectid  = $("body").data("projectid");
        var routurl = $("body").data("routurl");
        $("#allocationdailyGraftingTable tbody").empty();
        if (!locationid) {
            return;
        } 
        $.ajax({
            type: "POST", 
              url: `${routurl}krf-orderandsales/functions/grafting_view.php?action=locationbaseddata`,
            data: {
                projectid: projectid,
                locationid: locationid
            },
            dataType: "json",
            beforeSend: function () {
                $("#tbldetails tbody").html(`
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                            <span class="ms-2">Loading...</span>
                        </td>
                    </tr>
                `);
            },
            success: function (res) {
                if (!res || res.status !== "success") {
                    $("#tbldetails tbody").html(`
                        <tr>
                            <td colspan="8" class="text-center text-danger py-4">
                                ${res && res.message ? res.message : "Unable to load data."}
                            </td>
                        </tr>
                    `);

                    return;
                }
                if (!Array.isArray(res.data) || res.data.length === 0) {
                    $("#tbldetails tbody").html(`
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                No grafting data found for this location.
                            </td>
                        </tr>
                    `);

                    return;
                }
                renderLocationBasedData(res.data);
                 var polylist = '<option value="">-- Select Polyhouse --</option>'; // default option
                $("#polyhouselist").empty(); 
                  if(res.polyhousedata.length > 0){
                    resnew=res.polyhousedata;
                    for (var i = 0; i < resnew.length; i++) {
                      polylist += '<option value="' + resnew[i].polyhouseid + '">' + resnew[i].polyhousename + '</option>';
                    }
                  }
                  $("#polyhouselist").append(polylist);                
            },
            error: function (xhr, status, error) {
                console.error("locationbaseddata error:", error);
                console.error("Response:", xhr.responseText);

                $("#tbldetails tbody").html(`
                    <tr>
                        <td colspan="8" class="text-center text-danger py-4">
                            Error while loading location data.
                        </td>
                    </tr>
                `);
            }
        });

    }); 

    function renderLocationBasedData(data) {
        const $tbody = $("#tbldetails tbody");
        if (!Array.isArray(data) || data.length === 0) {
            $tbody.html(`
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        No grafting data found.
                    </td>
                </tr>
            `);
            return;
        }

        let rows = "";

        for (let i = 0; i < data.length; i++) {
            const row = data[i]; 
            let checkboxClass = "grafting-checkbox"; 
            const totalTray = Number(row.totaltray || 0);
            const totalQty  = Number(row.totalqty || 0);
            rows += `
                <tr>
                    <td class="text-center align-middle">
                        ${i + 1}
                    </td>
                    <td class="text-center align-middle">
                        <input  type="checkbox" class = "grafting-checkbox"                            
                            data-index="${i}"
                            data-sowinglotno_internal="${row.sowinglotno_internal || ''}"
                            data-sowingbatchno="${row.sowingbatchno || ''}"
                            data-productid="${row.productid || ''}"
                            data-productname="${row.productname || ''}"
                            data-stockorder="${row.stockorder_no || ''}"
                            data-totalroottray="${row.totalroottray || ''}"
                            data-totalsciontray="${row.totalsciontray}"
                            data-totalrootqty="${row.totalrootqty}"
                            data-totalscionqty="${row.totalscionqty}"
                        >
                    </td> 
                    <td class="align-middle fw-semibold text-nowrap">
                        ${row.stockorder_no || "-"}
                    </td>
                    <td class="align-middle">
                        ${row.productname || "-"}
                    </td>
                    <td class="text-center align-middle fw-semibold">
                        ${row.totalroottray.toLocaleString("en-IN")}
                    </td>
                    <td class="text-center align-middle fw-bold">
                        ${row.totalrootqty.toLocaleString("en-IN")}
                    </td>
                    <td class="text-center align-middle fw-semibold">
                        ${row.totalsciontray.toLocaleString("en-IN")}
                    </td>
                    <td class="text-center align-middle fw-bold">
                        ${row.totalscionqty.toLocaleString("en-IN")}
                    </td>
                </tr>
            `;
        }

        $tbody.html(rows);
    }  
    
    $(document).on(  "change",  "#graftedtraylist",   function () {
        calculateMaxGraftedTrayQty();
    });
    function calculateMaxGraftedTrayQty() {

    // Selected order quantities already shown in modal
    const totalRootQty =
        Number(
            $("#modal_rootplants")
                .text()
                .replace(/,/g, "")
        ) || 0;

    const totalScionQty =
        Number(
            $("#modal_scionplants")
                .text()
                .replace(/,/g, "")
        ) || 0;


    // Selected grafted tray cavity
    const cavityCount =
        Number(
            $("#graftedtraylist option:selected")
                .data("cavity")
        ) || 0;


    // ---------------------------------------------
    // NO TRAY SELECTED
    // ---------------------------------------------
    if (cavityCount <= 0) {

        $("#graftedTrayInfo").html("");

        return;
    }


    // ---------------------------------------------
    // A grafted plant needs both:
    // 1 Root + 1 Scion
    // Therefore lower quantity controls production
    // ---------------------------------------------
    const availablePlantQty =
        Math.min(
            totalRootQty,
            totalScionQty
        );


    // ---------------------------------------------
    // NO AVAILABLE PLANTS
    // ---------------------------------------------
    if (availablePlantQty <= 0) {

        $("#graftedTrayInfo").html(`
            <div class="alert alert-warning mb-0 py-2">

                <div class="fw-bold mb-1">
                    Total Possible Grafted Trays:
                    <span class="text-danger">0</span>
                </div>

                <small>
                    Root Plants:
                    <strong>
                        ${totalRootQty.toLocaleString("en-IN")}
                    </strong>

                    &nbsp; | &nbsp;

                    Scion Plants:
                    <strong>
                        ${totalScionQty.toLocaleString("en-IN")}
                    </strong>
                </small>

            </div>
        `);

        return;
    }


    // ---------------------------------------------
    // LAST TRAY CAN BE PARTIALLY FILLED
    // ---------------------------------------------
    const totalGraftedTray =
        Math.ceil(
            availablePlantQty / cavityCount
        );
$("#labourdetails").data(
    "maxgraftedtrayqty",
    totalGraftedTray
);

$("#ftMaxGraftedTray").text(
    totalGraftedTray.toLocaleString("en-IN")
);
updateGraftingTargetFooter();

    // Plants in final tray
    const fullTrayCount =
        Math.floor(
            availablePlantQty / cavityCount
        );

    const balancePlants =
        availablePlantQty % cavityCount;


    // ---------------------------------------------
    // DISPLAY
    // ---------------------------------------------
    $("#graftedTrayInfo").html(`
        <div class="alert alert-info mb-0 py-2">

            <div class="d-flex
                        justify-content-between
                        align-items-center
                        flex-wrap">

                <div>

                    <span class="fw-bold">
                        Total Possible Grafted Trays:
                    </span>

                    <span class="fw-bold fs-5 text-primary">
                        ${totalGraftedTray.toLocaleString("en-IN")}
                    </span>

                </div>

                <div class="small">

                    Available Plants:
                    <strong>
                        ${availablePlantQty.toLocaleString("en-IN")}
                    </strong>

                    &nbsp; | &nbsp;

                    Tray Capacity:
                    <strong>
                        ${cavityCount.toLocaleString("en-IN")}
                    </strong>

                </div>

            </div>

            ${
                balancePlants > 0
                ? `
                    <div class="small mt-1 text-muted">
                        ${fullTrayCount.toLocaleString("en-IN")}
                        full tray${fullTrayCount === 1 ? "" : "s"}
                        +
                        1 partial tray
                        (${balancePlants.toLocaleString("en-IN")}
                        plants).
                    </div>
                  `
                : `
                    <div class="small mt-1 text-muted">
                        All ${totalGraftedTray.toLocaleString("en-IN")}
                        tray${totalGraftedTray === 1 ? "" : "s"}
                        can be completely filled.
                    </div>
                  `
            }

        </div>
    `);
}
$(document).on(  "click",
    "#btnCancelGraftingAllocation, #btnCloseGraftingAllocation",
    function () {

        closeGraftingAllocationModal();

        $(".grafting-checkbox").prop("checked", false);
        $("#tbldetails tbody tr").show();

        $("#allocationdailyGraftingTable tbody").empty();

        $("#updategraftingallocationdetailsbtn")
            .addClass("d-none");
    }
);
$(document).on(
    "change",
    ".selectforgrafting",
    function () {

        const maxQty =
            Number(
                $("#labourdetails")
                    .data("maxgraftedtrayqty")
            ) || 0;


        if (maxQty <= 0) {

            $(this).prop("checked", false);

            alertmessage(
                "warning",
                "Please select Grafted Tray Type first."
            );

            return;
        }


        distributeGraftingTarget();
    }
); 
/*$(document).on("change", ".grafting-checkbox", function () {

    const $checkbox = $(this);
    const $selectedRow = $checkbox.closest("tr");

    // ==========================================================
    // 1. CHECKBOX UNCHECKED
    // ==========================================================

    if (!$checkbox.is(":checked")) {

        $("#tbldetails tbody tr").show();

        if (activeGraftingCheckbox === this) {

            graftingAllocationSaved = false;

            closeGraftingAllocationModal();
        }

        return;
    }


    // ==========================================================
    // 2. ONLY ONE STOCK ORDER AT A TIME
    // ==========================================================

    $(".grafting-checkbox")
        .not($checkbox)
        .prop("checked", false);

    activeGraftingCheckbox = this;

    graftingAllocationSaved = false;


    // ==========================================================
    // 3. GET SELECTED STOCK ORDER DATA
    // ==========================================================

    const stockorder =
        $checkbox.data("stockorder") || "";

    const productname =
        $checkbox.data("productname") || "";

    const productid =
        $checkbox.data("productid") || "";

    const totalRootTray =
        Number($checkbox.data("totalroottray")) || 0;

    const totalScionTray =
        Number($checkbox.data("totalsciontray")) || 0;

    const totalRootQty =
        Number($checkbox.data("totalrootqty")) || 0;

    const totalScionQty =
        Number($checkbox.data("totalscionqty")) || 0;


    const sourceLocationId =
        $("#locationid").val() || "";

    const locationName =
        $("#locationid option:selected").text() || "-";


    // ==========================================================
    // 4. UPDATE MODAL HEADER
    // ==========================================================

    $("#modal_stockorder")
        .text(stockorder || "-");

    $("#modal_productname")
        .text(productname || "-");

    $("#modal_locationname")
        .text(locationName);


    $("#modal_roottray")
        .text(
            totalRootTray.toLocaleString("en-IN")
        );

    $("#modal_rootplants")
        .text(
            totalRootQty.toLocaleString("en-IN")
        );

    $("#modal_sciontray")
        .text(
            totalScionTray.toLocaleString("en-IN")
        );

    $("#modal_scionplants")
        .text(
            totalScionQty.toLocaleString("en-IN")
        );


    // ==========================================================
    // 5. RESET LABOUR FILTER / TABLE
    // ==========================================================

    $("#allocationgrftinput").val("");


    $("#allocationdailyGraftingTable tbody").html(`
        <tr>

            <td colspan="8"
                class="text-center py-3">

                <div
                    class="spinner-border spinner-border-sm text-primary"
                    role="status">
                </div>

                <span class="ms-2">
                    Loading labour details...
                </span>

            </td>

        </tr>
    `);


    $("#updategraftingallocationdetailsbtn")
        .addClass("d-none");


    // ==========================================================
    // 6. RESET GRAFTED TRAY DATA
    // ==========================================================

    $("#graftedtraylist").val("");

    $("#graftedTrayInfo").html("");

    $("#labourdetails")
        .data("maxgraftedtrayqty", 0);


    $("#ftMaxGraftedTray").text("0");

    $("#ftAllocatedGraftedTray").text("0");

    $("#ftBalanceGraftedTray").text("0");


    // ==========================================================
    // 7. SHOW ONLY SELECTED STOCK ORDER
    // ==========================================================

    $("#tbldetails tbody tr").each(function () {

        if (this === $selectedRow[0]) {

            $(this).show();

        } else {

            $(this).hide();
        }

    });


    // ==========================================================
    // 8. OPEN LABOUR MODAL
    // ==========================================================

    $("#labourdetails").modal({
        backdrop: "static",
        keyboard: false
    });


    $("#labourdetails").modal("show");


    // ==========================================================
    // 9. FETCH LABOUR DATA
    // ==========================================================

    GetlaboursWithLocationProductForGrafting(

        $("#graftingdate").val(),

        sourceLocationId,

        productid,

        function (res) {

            let lbwithloc = "";


            // ==================================================
            // NO LABOUR
            // ==================================================

            if (
                !Array.isArray(res) ||
                res.length === 0
            ) {

                $("#allocationdailyGraftingTable tbody").html(`
                    <tr>

                        <td colspan="8"
                            class="text-center text-muted py-3">

                            No labour available for allocation.

                        </td>

                    </tr>
                `);


                $("#updategraftingallocationdetailsbtn")
                    .addClass("d-none");


                return;
            }


            // ==================================================
            // BUILD LABOUR ROWS
            // ==================================================

            for (
                let i = 0;
                i < res.length;
                i++
            ) {

                const labour = res[i];


                // ==============================================
                // DAILY ASSIGNED QTY
                // ALL PRODUCTS
                // ==============================================

                const assignedQty =
                    Number(
                        labour.dailyassigned || 0
                    );


                // ==============================================
                // DAY TARGET
                // ==============================================

                const todayTarget =
                    Number(
                        labour.todaystarget || 0
                    );


                const targetSource =
                    labour.targetsource || "none";


                // ==============================================
                // TARGET BADGE
                // ==============================================

                let targetBadge = "";


                if (targetSource === "saved") {

                    targetBadge = `
                        <span class="badge bg-success mt-1">
                            Saved Target
                        </span>
                    `;

                } else if (
                    targetSource === "suggested"
                ) {

                    targetBadge = `
                        <span
                            class="badge bg-warning text-dark mt-1">

                            Management Suggested

                        </span>
                    `;

                } else {

                    targetBadge = `
                        <span
                            class="badge bg-secondary mt-1">

                            Today Target Not Assigned

                        </span>
                    `;
                }


                // ==============================================
                // ASSIGNED BADGE
                // ==============================================

                let assignedBadge = "";


                if (assignedQty > 0) {

                    assignedBadge = `
                        <button
                            type="button"
                            class="btn btn-link p-0 ms-1 btnLabourGraftingHistory"

                            data-labourid="${labour.LabourId}"

                            data-labourname="${labour.LabourName || ''}"

                            title="View today's allocation summary"
                        >

                            <span
                                class="badge bg-info text-dark">

                                Assigned:
                                ${assignedQty.toLocaleString("en-IN")}

                            </span>

                            <small class="ms-1">
                                View
                            </small>

                        </button>
                    `;

                } else {

                    assignedBadge = `
                        <span
                            class="badge bg-light text-dark border ms-1">

                            Assigned: 0

                        </span>
                    `;
                }


                // ==============================================
                // TARGET VALUE
                // ==============================================

                const targetValue =
                    todayTarget > 0
                        ? todayTarget
                        : "";


                // ==============================================
                // LABOUR ROW
                // ==============================================

                lbwithloc += `

                    <tr
                        data-labourid="${labour.LabourId || ''}"

                        data-labourname="${labour.LabourName || ''}"

                        data-trayid="${labour.trayid || ''}"

                        data-dailyassigned="${assignedQty}"

                        data-daytarget="${todayTarget}"

                        data-targetsource="${targetSource}"
                    >


                        <!-- ================================
                             SELECT
                        ================================= -->

                        <td class="text-center">

                            <input
                                type="checkbox"
                                class="selectforgrafting"
                            >

                        </td>


                        <!-- ================================
                             LABOUR
                        ================================= -->

                        <td
                            class="labourname_gfrm"
                            style="min-width:220px;"
                        >

                            <div class="fw-semibold">

                                ${labour.LabourId || ''}

                                -

                                ${labour.LabourName || ''}

                            </div>


                            <div class="mt-1">

                                ${assignedBadge}

                            </div>

                        </td>


                        <!-- ================================
                             DAY TARGET
                        ================================= -->

                        <td style="min-width:180px;">

                            <input
                                type="number"
                                name="commonDayTarget"
                                class="form-control commonDayTarget"

                                value="${targetValue}"

                                min="1"
                                step="1"
                            >


                            <div>

                                ${targetBadge}

                            </div>

                        </td>


                        <!-- ================================
                             ALLOCATION TIME
                        ================================= -->

                        <td
                            class="allocationtime_fgrm"
                            style="min-width:130px;"
                        >

                            <input
                                type="time"
                                class="form-control allocationtime"
                                name="allocationtime"

                                value="<?php echo date('H:i:s'); ?>"
                            >

                        </td>


                        <!-- ================================
                             NEW GRAFTED TRAY ALLOCATION
                        ================================= -->

                        <td style="min-width:150px;">

                            <input
                                type="number"
                                name="graftraytetarget"

                                class="form-control graftraytetarget"

                                value=""

                                min="1"
                                step="1"
                            >

                        </td>


                        <!-- ================================
                             ROOT TRAY
                        ================================= -->

                        <td style="min-width:110px;">

                            <input
                                type="number"

                                class="form-control text-center roottraytarget"

                                value="0"

                                readonly
                            >

                        </td>


                        <!-- ================================
                             SCION TRAY
                        ================================= -->

                        <td style="min-width:110px;">

                            <input
                                type="number"

                                class="form-control text-center sciontraytarget"

                                value="0"

                                readonly
                            >

                        </td>


                        <!-- ================================
                             SAVE
                        ================================= -->

                        <td
                            class="text-center text-nowrap"
                        >

                            <button
                                type="button"

                                class="btn btn-sm btn-success btnSaveLabourGrafting"

                                title="Save Allocation"
                            >

                                <i class="fa fa-save"></i>

                                Save

                            </button>
                            <button
                                type="button"
                                class="btn btn-sm btn-primary btnPrintGraftingLabel d-none ms-1"

                                data-labourid="${labour.LabourId || ''}"
                                data-labourname="${labour.LabourName || ''}"

                                title="Print Grafted Tray Barcode"
                            >
                                <i class="fa fa-print"></i>
                                Print
                            </button>

                        </td>

                    </tr>
                `;
            }


            // ==================================================
            // 10. DISPLAY LABOUR ROWS
            // ==================================================

            $("#allocationdailyGraftingTable tbody")
                .html(lbwithloc);


            // ==================================================
            // 11. SHOW SAVE ALL
            // ==================================================

            $("#updategraftingallocationdetailsbtn")
                .removeClass("d-none");


            // ==================================================
            // 12. UPDATE FOOTER
            // ==================================================

            updateGraftingTargetFooter();

    refreshGraftingPrintAvailability();

        }

    );

});
 */
$(document).on("change", ".grafting-checkbox", function () {

    const $checkbox = $(this);
    const $selectedRow = $checkbox.closest("tr");

    // ==========================================================
    // 1. CHECKBOX UNCHECKED
    // ==========================================================

    if (!$checkbox.is(":checked")) {

        $("#tbldetails tbody tr").show();

        if (activeGraftingCheckbox === this) {

            graftingAllocationSaved = false;

            closeGraftingAllocationModal();
        }

        return;
    }


    // ==========================================================
    // 2. ONLY ONE STOCK ORDER AT A TIME
    // ==========================================================

    $(".grafting-checkbox")
        .not($checkbox)
        .prop("checked", false);

    activeGraftingCheckbox = this;

    graftingAllocationSaved = false;


    // ==========================================================
    // 3. GET SELECTED STOCK ORDER DATA
    // ==========================================================

    const stockorder =
        String($checkbox.data("stockorder") || "").trim();

    const productname =
        String($checkbox.data("productname") || "").trim();

    const productid =
        String($checkbox.data("productid") || "").trim();

    const totalRootTray =
        Number($checkbox.data("totalroottray")) || 0;

    const totalScionTray =
        Number($checkbox.data("totalsciontray")) || 0;

    const totalRootQty =
        Number($checkbox.data("totalrootqty")) || 0;

    const totalScionQty =
        Number($checkbox.data("totalscionqty")) || 0;

    const sourceLocationId =
        String($("#locationid").val() || "").trim();

    const locationName =
        $("#locationid option:selected").text() || "-";

    const graftingdate =
        $("#graftingdate").val() || "";


    // ==========================================================
    // 4. STORE CURRENT MODAL CONTEXT
    // IMPORTANT FOR PRINT / PRINT ALL
    // ==========================================================

    $("#labourdetails")
        .data("stockorder", stockorder)
        .data("productid", productid)
        .data("productname", productname)
        .data("locationid", sourceLocationId)
        .data("graftingdate", graftingdate);


    // ==========================================================
    // 5. UPDATE MODAL HEADER
    // ==========================================================

    $("#modal_stockorder")
        .text(stockorder || "-");

    $("#modal_productname")
        .text(productname || "-");

    $("#modal_locationname")
        .text(locationName);

    $("#modal_roottray")
        .text(totalRootTray.toLocaleString("en-IN"));

    $("#modal_rootplants")
        .text(totalRootQty.toLocaleString("en-IN"));

    $("#modal_sciontray")
        .text(totalScionTray.toLocaleString("en-IN"));

    $("#modal_scionplants")
        .text(totalScionQty.toLocaleString("en-IN"));


    // ==========================================================
    // 6. RESET LABOUR FILTER / TABLE
    // ==========================================================

    $("#allocationgrftinput").val("");

    $("#allocationdailyGraftingTable tbody").html(`
        <tr>
            <td colspan="8"
                class="text-center py-3">

                <div
                    class="spinner-border spinner-border-sm text-primary"
                    role="status">
                </div>

                <span class="ms-2">
                    Loading labour details...
                </span>

            </td>
        </tr>
    `);


    // Hide buttons until data is loaded
    $("#updategraftingallocationdetailsbtn")
        .addClass("d-none");

    $("#btnPrintAllGraftingLabels")
        .addClass("d-none");


    // ==========================================================
    // 7. RESET GRAFTED TRAY DATA
    // ==========================================================

    $("#graftedtraylist").val("");

    $("#graftedTrayInfo").html("");

    $("#labourdetails")
        .data("maxgraftedtrayqty", 0);

    $("#ftMaxGraftedTray").text("0");

    $("#ftAllocatedGraftedTray").text("0");

    $("#ftBalanceGraftedTray").text("0");


    // ==========================================================
    // 8. SHOW ONLY SELECTED STOCK ORDER
    // ==========================================================

    $("#tbldetails tbody tr").each(function () {

        if (this === $selectedRow[0]) {

            $(this).show();

        } else {

            $(this).hide();
        }

    });


    // ==========================================================
    // 9. OPEN LABOUR MODAL
    // ==========================================================

    $("#labourdetails").modal({
        backdrop: "static",
        keyboard: false
    });

    $("#labourdetails").modal("show");


    // ==========================================================
    // 10. FETCH LABOUR DATA
    // ==========================================================

    GetlaboursWithLocationProductForGrafting(

        graftingdate,

        sourceLocationId,

        productid,

        function (res) {

            let lbwithloc = "";


            // ==================================================
            // NO LABOUR
            // ==================================================

            if (
                !Array.isArray(res) ||
                res.length === 0
            ) {

                $("#allocationdailyGraftingTable tbody").html(`
                    <tr>
                        <td colspan="8"
                            class="text-center text-muted py-3">

                            No labour available for allocation.

                        </td>
                    </tr>
                `);

                $("#updategraftingallocationdetailsbtn")
                    .addClass("d-none");

                $("#btnPrintAllGraftingLabels")
                    .addClass("d-none");

                return;
            }


            // ==================================================
            // 11. BUILD LABOUR ROWS
            // ==================================================

            for (let i = 0; i < res.length; i++) {

                const labour = res[i];

                const assignedQty =
                    Number(labour.dailyassigned || 0);

                const todayTarget =
                    Number(labour.todaystarget || 0);

                const targetSource =
                    labour.targetsource || "none";


                // ==============================================
                // TARGET BADGE
                // ==============================================

                let targetBadge = "";

                if (targetSource === "saved") {

                    targetBadge = `
                        <span class="badge bg-success mt-1">
                            Saved Target
                        </span>
                    `;

                } else if (targetSource === "suggested") {

                    targetBadge = `
                        <span class="badge bg-warning text-dark mt-1">
                            Management Suggested
                        </span>
                    `;

                } else {

                    targetBadge = `
                        <span class="badge bg-warning mt-1">
                            Today Target Not Assigned
                        </span>
                    `;
                }


                // ==============================================
                // ASSIGNED BADGE
                // ==============================================

                let assignedBadge = "";

                if (assignedQty > 0) {

                    assignedBadge = `
                        <button
                            type="button"
                            class="btn btn-link p-0 ms-1 btnLabourGraftingHistory"

                            data-labourid="${labour.LabourId || ''}"
                            data-labourname="${labour.LabourName || ''}"

                            title="View today's allocation summary"
                        >

                            <span class="badge bg-info text-dark">

                                Assigned:
                                ${assignedQty.toLocaleString("en-IN")}

                            </span>

                            <small class="ms-1">
                                View
                            </small>

                        </button>
                    `;

                } else {

                    assignedBadge = `
                        <span class="badge bg-light text-dark border ms-1">

                            Assigned: 0

                        </span>
                    `;
                }


                // ==============================================
                // DAY TARGET
                // ==============================================

                const targetValue =
                    todayTarget > 0
                        ? todayTarget
                        : "";


                // ==============================================
                // LABOUR ROW
                // ==============================================

                lbwithloc += `

                    <tr
                        data-labourid="${labour.LabourId || ''}"
                        data-labourname="${labour.LabourName || ''}"
                        data-trayid="${labour.trayid || ''}"
                        data-dailyassigned="${assignedQty}"
                        data-daytarget="${todayTarget}"
                        data-targetsource="${targetSource}"
                    >


                        <!-- SELECT -->

                        <td class="text-center">

                            <input
                                type="checkbox"
                                class="selectforgrafting"
                            >

                        </td>


                        <!-- LABOUR -->

                        <td
                            class="labourname_gfrm"
                            style="min-width:220px;"
                        >

                            <div class="fw-semibold">

                                ${labour.LabourId || ''}

                                -

                                ${labour.LabourName || ''}

                            </div>

                            <div class="mt-1 labourAssignedContainer">

                                ${assignedBadge}

                            </div>

                        </td>


                        <!-- DAY TARGET -->

                        <td style="min-width:180px;">

                            <input
                                type="number"
                                name="commonDayTarget"
                                class="form-control commonDayTarget"
                                value="${targetValue}"
                                min="1"
                                step="1"
                            >

                            <div class ="d-none">
                                ${targetBadge}
                            </div>
                     <div class="col-md-auto col-sm-6">

                        <button
                            type="button"
                            id="btnApplyTodayTargetAll"
                            class="btn btn-primary"
                        >
                            <i class="fa fa-check-double me-1"></i>
                            Apply to All Labours
                        </button>

                    </div>

                        </td>


                        <!-- ALLOCATION TIME -->

                        <td
                            class="allocationtime_fgrm"
                            style="min-width:130px;"
                        >

                            <input
                                type="time"
                                class="form-control allocationtime"
                                name="allocationtime"
                                value="<?php echo date('H:i:s'); ?>"
                            >

                        </td>


                        <!-- NEW GRAFTED TRAY ALLOCATION -->

                        <td style="min-width:150px;">

                            <input
                                type="number"
                                name="graftraytetarget"
                                class="form-control graftraytetarget"
                                value=""
                                min="1"
                                step="1"
                            >

                        </td>


                        <!-- ROOT TRAY -->

                        <td style="min-width:110px;">

                            <input
                                type="number"
                                class="form-control text-center roottraytarget"
                                value="0"
                                readonly
                            >

                        </td>


                        <!-- SCION TRAY -->

                        <td style="min-width:110px;">

                            <input
                                type="number"
                                class="form-control text-center sciontraytarget"
                                value="0"
                                readonly
                            >

                        </td>


                        <!-- SAVE + PRINT -->

                        <td class="text-center text-nowrap">

                            <button
                                type="button"
                                class="btn btn-sm btn-success btnSaveLabourGrafting"
                                title="Save Additional Allocation"
                            >

                                <i class="fa fa-save"></i>
                                Save

                            </button>


                            <button
                                type="button"
                                class="btn btn-sm btn-primary btnPrintGraftingLabel d-none ms-1"

                                data-labourid="${labour.LabourId || ''}"
                                data-labourname="${labour.LabourName || ''}"

                                title="Print Grafted Tray Barcode"
                            >

                                <i class="fa fa-print"></i>
                                Print

                            </button>

                        </td>

                    </tr>
                `;
            }


            // ==================================================
            // 12. DISPLAY LABOUR ROWS
            // ==================================================

            $("#allocationdailyGraftingTable tbody")
                .html(lbwithloc);


            // ==================================================
            // 13. SHOW SAVE ALL
            // ==================================================

            $("#updategraftingallocationdetailsbtn")
                .removeClass("d-none");


            // ==================================================
            // 14. UPDATE TARGET FOOTER
            // ==================================================

            updateGraftingTargetFooter();


            // ==================================================
            // 15. CHECK EXISTING PRINTABLE BARCODES
            // ==================================================

            refreshGraftingPrintAvailability();

        }

    );

});
$(document).on(
    "click",
    "#btnCancelGraftingAllocation, #btnCloseGraftingAllocation",
    function () {

        closeGraftingAllocationModal();

        $(".grafting-checkbox").prop("checked", false);
        $("#tbldetails tbody tr").show();

        $("#allocationdailyGraftingTable tbody").empty();

        $("#updategraftingallocationdetailsbtn")
            .addClass("d-none");
    }
);
/*function closeGraftingAllocationModal() {
    $("#labourdetails").modal("hide");
}*/
function closeGraftingAllocationModal() {

    $("#btnPrintAllGraftingLabels")
        .addClass("d-none")
        .removeAttr("data-barcode-count");

    $("#labourdetails")
        .removeData("stockorder")
        .removeData("productid")
        .removeData("productname")
        .removeData("locationid")
        .removeData("graftingdate");

    $("#labourdetails").modal("hide");
}
$("#labourdetails").on(
    "hidden.bs.modal",
       function () {

        closeGraftingAllocationModal();

        $(".grafting-checkbox").prop("checked", false);
        $("#tbldetails tbody tr").show();

        $("#allocationdailyGraftingTable tbody").empty();

        $("#updategraftingallocationdetailsbtn")
            .addClass("d-none");
    }
);
function updateGraftingTargetFooter() {

    const maxQty =
        Number(
            $("#labourdetails").data("maxgraftedtrayqty")
        ) || 0;

    let allocatedQty = 0;


    $("#allocationdailyGraftingTable tbody tr")
        .each(function () {

            const $row = $(this);

            const $checkbox =
                $row.find(".selectforgrafting");

            // Only currently selected pending labour
            if (
                $checkbox.length &&
                $checkbox.is(":checked")
            ) {

                allocatedQty +=
                    parseInt(
                        $row.find(".graftraytetarget").val()
                    ) || 0;
            }
        });


    const balanceQty =
        Math.max(0, maxQty - allocatedQty);


    $("#ftMaxGraftedTray").text(
        maxQty.toLocaleString("en-IN")
    );

    $("#ftAllocatedGraftedTray").text(
        allocatedQty.toLocaleString("en-IN")
    );

    $("#ftBalanceGraftedTray").text(
        balanceQty.toLocaleString("en-IN")
    );
}

 function distributeGraftingTarget() {

    // ==========================================================
    // 1. GET MAXIMUM POSSIBLE GRAFTED TRAY QTY
    // ==========================================================
    const maxQty =
        Number(
            $("#labourdetails").data("maxgraftedtrayqty")
        ) || 0;


    // ==========================================================
    // 2. GET ONLY SELECTED LABOUR ROWS
    // ==========================================================
    const $selectedRows =
        $("#allocationdailyGraftingTable tbody tr")
            .filter(function () {

                return $(this)
                    .find(".selectforgrafting")
                    .is(":checked");

            });


    const labourCount = $selectedRows.length;


    // ==========================================================
    // 3. NO LABOUR SELECTED
    // ==========================================================
    if (labourCount === 0) {

        $("#allocationdailyGraftingTable tbody tr")
            .each(function () {

                const $row = $(this);

                // Clear only pending labour rows
                if ($row.find(".selectforgrafting").length) {

                    $row.find(".graftraytetarget").val("");

                    $row.find(".roottraytarget").val(0);

                    $row.find(".sciontraytarget").val(0);
                }

            });


        updateGraftingTargetFooter();

        return;
    }


    // ==========================================================
    // 4. MAX GRAFTED TRAY NOT AVAILABLE
    // ==========================================================
    if (maxQty <= 0) {

        $selectedRows.each(function () {

            const $row = $(this);

            $row.find(".graftraytetarget").val("");

            $row.find(".roottraytarget").val(0);

            $row.find(".sciontraytarget").val(0);

        });


        updateGraftingTargetFooter();

        return;
    }


    // ==========================================================
    // 5. CALCULATE EQUAL DISTRIBUTION
    //
    // Example:
    // Max Qty = 5
    // Labour   = 2
    //
    // baseQty   = 2
    // remainder = 1
    //
    // Result = 2, 3
    // ==========================================================
    const baseQty =
        Math.floor(maxQty / labourCount);

    const remainder =
        maxQty % labourCount;


    // ==========================================================
    // 6. DISTRIBUTE TO SELECTED LABOURS
    // ==========================================================
    $selectedRows.each(function (index) {

        const $row = $(this);

        let qty = baseQty;


        // Remaining trays go to last labour(s)
        if (
            remainder > 0 &&
            index >= (labourCount - remainder)
        ) {

            qty++;
        }


        // ------------------------------------------------------
        // GRAFTED TRAY TARGET
        // ------------------------------------------------------
        $row
            .find(".graftraytetarget")
            .val(qty);


        // ------------------------------------------------------
        // ROOT TRAY ASSIGN - READ ONLY
        // ------------------------------------------------------
        $row
            .find(".roottraytarget")
            .val(qty);


        // ------------------------------------------------------
        // SCION TRAY ASSIGN - READ ONLY
        // ------------------------------------------------------
        $row
            .find(".sciontraytarget")
            .val(qty);

    });


    // ==========================================================
    // 7. CLEAR UNSELECTED PENDING LABOUR ROWS
    // ==========================================================
    $("#allocationdailyGraftingTable tbody tr")
        .each(function () {

            const $row = $(this);

            const $checkbox =
                $row.find(".selectforgrafting");


            // Only pending rows have selectforgrafting checkbox
            if (
                $checkbox.length &&
                !$checkbox.is(":checked")
            ) {

                $row
                    .find(".graftraytetarget")
                    .val("");

                $row
                    .find(".roottraytarget")
                    .val(0);

                $row
                    .find(".sciontraytarget")
                    .val(0);
            }

        });


    // ==========================================================
    // 8. UPDATE MAX / ALLOCATED / BALANCE FOOTER
    // ==========================================================
    updateGraftingTargetFooter();

} 
$(document).on("input", ".graftraytetarget", function () {

    const $currentInput = $(this);
    const $currentRow   = $currentInput.closest("tr");

    // ==========================================================
    // 1. GRAFTED TRAY TYPE MUST BE SELECTED
    // ==========================================================
    const graftedTrayId = $("#graftedtraylist").val();

    if (!graftedTrayId) {

        // Do not allow target entry
        $currentInput.val("");

        // Reset dependent values
        $currentRow.find(".roottraytarget").val(0);
        $currentRow.find(".sciontraytarget").val(0);

        updateGraftingTargetFooter();

        alertmessage(
            "warning",
            "Please select Grafted Tray Type before entering Grafted Tray Target."
        );

        return;
    }


    // ==========================================================
    // 2. GET MAXIMUM AVAILABLE GRAFTED TRAYS
    // ==========================================================
    const maxQty =
        parseInt(
            $("#labourdetails").data("maxgraftedtrayqty"),
            10
        ) || 0;


    if (maxQty <= 0) {

        $currentInput.val("");

        $currentRow.find(".roottraytarget").val(0);
        $currentRow.find(".sciontraytarget").val(0);
        refreshGraftingPrintAvailability();
        updateGraftingTargetFooter();

        alertmessage(
            "warning",
            "No grafted tray quantity is available for allocation."
        );

        return;
    }


    // ==========================================================
    // 3. CURRENT ENTERED QUANTITY
    // ==========================================================
    let enteredQty =
        parseInt($currentInput.val(), 10) || 0;

    if (enteredQty < 0) {
        enteredQty = 0;
    }


    // ==========================================================
    // 4. CALCULATE TARGET IN OTHER SELECTED LABOUR ROWS
    // ==========================================================
    let otherQty = 0;

    $("#allocationdailyGraftingTable tbody tr")
        .each(function () {

            const $row = $(this);

            if ($row.is($currentRow)) {
                return;
            }

            const $checkbox =
                $row.find(".selectforgrafting");

            // Count only selected pending labour rows
            if (
                $checkbox.length &&
                $checkbox.is(":checked")
            ) {

                otherQty +=
                    parseInt(
                        $row.find(".graftraytetarget").val(),
                        10
                    ) || 0;
            }

        });


    // ==========================================================
    // 5. BALANCE AVAILABLE FOR CURRENT LABOUR
    // ==========================================================
    const balanceAvailable =
        Math.max(0, maxQty - otherQty);


    // ==========================================================
    // 6. RESTRICT TARGET TO AVAILABLE BALANCE
    // ==========================================================
    if (enteredQty > balanceAvailable) {

        enteredQty = balanceAvailable;

        $currentInput.val(enteredQty);

        alertmessage(
            "warning",
            "Only " +
            balanceAvailable.toLocaleString("en-IN") +
            " tray(s) are available. Maximum total target is " +
            maxQty.toLocaleString("en-IN") +
            "."
        );

    } else {

        $currentInput.val(enteredQty);
    }


    // ==========================================================
    // 7. ROOT / SCION TRAY AUTO ASSIGN
    // ==========================================================
    const finalTargetQty =
        parseInt($currentInput.val(), 10) || 0;

    $currentRow
        .find(".roottraytarget")
        .val(finalTargetQty);

    $currentRow
        .find(".sciontraytarget")
        .val(finalTargetQty);


    // ==========================================================
    // 8. UPDATE FOOTER
    // ==========================================================
        refreshGraftingPrintAvailability();
    updateGraftingTargetFooter();

});

// $(document).on("click", "#btnApplyCommonDayTarget", function () {

//     const dayTarget =  parseInt($(".commonDayTarget").val(), 10) || 0;
// debugger
//     if (dayTarget <= 0) {

//         alertmessage(
//             "warning",
//             "Please enter a valid Day Target."
//         );

//         return;
//     }


//     const $selectedRows =
//         $("#allocationdailyGraftingTable tbody tr")
//         .filter(function () {

//             return $(this)
//                 .find(".selectforgrafting")
//                 .is(":checked");

//         });


//     if ($selectedRows.length === 0) {

//         alertmessage(
//             "warning",
//             "Please select at least one labour."
//         );

//         return;
//     }


//     $selectedRows.each(function () {

//         $(this)
//             .find(".commonDayTarget")
//             .val(dayTarget);

//     });


//     alertmessage(
//         "success",
//         "Day Target " +
//         dayTarget.toLocaleString("en-IN") +
//         " applied to " +
//         $selectedRows.length +
//         " selected labour(s)."
//     );

// });
$(document).on("click", "#btnApplyCommonDayTarget", function () {

    // ==========================================================
    // GET DAY TARGET FROM THE SAME ROW AS CLICKED BUTTON
    // ==========================================================
    const $currentRow = $(this).closest("tr");

    const dayTarget =
        parseInt(
            $currentRow.find(".commonDayTarget").val(),
            10
        ) || 0;


    // ==========================================================
    // VALIDATE
    // ==========================================================
    if (dayTarget <= 0) {

        alertmessage(
            "warning",
            "Please enter a valid Day Target."
        );

        return;
    }


    // ==========================================================
    // APPLY TO EVERY LABOUR ROW
    // NO OTHER CONDITION
    // ==========================================================
    $("#allocationdailyGraftingTable tbody tr")
        .each(function () {

            const $row = $(this);

            // If this is a labour row
            if ($row.find(".commonDayTarget").length) {

                $row.find(".commonDayTarget").val(dayTarget);

            }

        });


    // ==========================================================
    // SUCCESS
    // ==========================================================
    alertmessage(
        "success",
        "Day Target " +
        dayTarget.toLocaleString("en-IN") +
        " applied to all labour(s)."
    );

});

function getGraftingAllocationRowData($row) {

    const labourid =  String($row.data("labourid") || "").trim();

    const labourname =  String($row.data("labourname") || "").trim();

    const todaytarget =  parseInt(
            $row.find(".commonDayTarget").val(),
            10
        ) || 0;

    const graftraytetarget =
        parseInt(
            $row.find(".graftraytetarget").val(),
            10
        ) || 0;

    const allocationtime =
        $row.find(".allocationtime").val() || "";


    return {
        labourid: labourid,
        labourname: labourname,
        todaytarget: todaytarget,
        allocationtime: allocationtime,

        // Main planning quantity
        noofgraftedtry: graftraytetarget
    };
}

/*function saveGraftingAllocationRows(rows, $button, callback) {
debugger;
    if (!Array.isArray(rows) || rows.length === 0) {

        alertmessage(
            "warning",
            "No Grafting Tray Target is available to save."
        );

        return;
    }


    const $activeCheckbox =
        $(".grafting-checkbox:checked").first();


    if (!$activeCheckbox.length) {

        alertmessage(
            "warning",
            "Please select a Stock Order."
        );

        return;
    }


    const projectid =
        $("body").data("projectid");

    const loginid =
        $("body").data("loginid");

    const locationid =
        $("#locationid").val() || "";

    const graftingdate =
        $("#graftingdate").val() || "";

    const productid =
        String(
            $activeCheckbox.data("productid") || ""
        );

    const productname =
        String(
            $activeCheckbox.data("productname") || ""
        );

    const stockorder_no =
        String(
            $activeCheckbox.data("stockorder") || ""
        );

    const psono =
        String(
            $activeCheckbox.data("psono") || ""
        );

    const grafted_tray_id =
        parseInt(
            $("#graftedtraylist").val(),
            10
        ) || 0;


    // ----------------------------------------------------------
    // VALIDATION
    // ----------------------------------------------------------

    if (!graftingdate) {

        alertmessage(
            "warning",
            "Grafting Date is required."
        );

        return;
    }


    if (!locationid) {

        alertmessage(
            "warning",
            "Location is required."
        );

        return;
    }


    if (!grafted_tray_id) {

        alertmessage(
            "warning",
            "Please select Grafted Tray Type."
        );

        return;
    }


    if (!productid || !stockorder_no) {

        alertmessage(
            "warning",
            "Selected Stock Order information is incomplete."
        );

        return;
    }


    // ----------------------------------------------------------
    // FINAL TOTAL SAFETY CHECK
    // ----------------------------------------------------------

    const maxQty =
        parseInt(
            $("#labourdetails").data("maxgraftedtrayqty"),
            10
        ) || 0;


    let totalTarget = 0;

    rows.forEach(function (row) {

        totalTarget +=
            parseInt(row.noofgraftedtry, 10) || 0;

    });


    if (totalTarget > maxQty) {

        alertmessage(
            "warning",
            "Total Grafted Tray Target (" +
            totalTarget.toLocaleString("en-IN") +
            ") cannot exceed maximum available target (" +
            maxQty.toLocaleString("en-IN") +
            ")."
        );

        return;
    }


    // ----------------------------------------------------------
    // PREPARE REQUEST
    // ----------------------------------------------------------

    const formData = new FormData();

    formData.append("projectid", projectid);
    formData.append("loginid", loginid);
    formData.append("locationid", locationid);
    formData.append("graftingdate", graftingdate);

    formData.append("productid", productid);
    formData.append("productname", productname);
    formData.append("stockorder_no", stockorder_no);
    formData.append("psono", psono);

    formData.append(
        "grafted_tray_id",
        grafted_tray_id
    );

    formData.append(
        "allocationdata",
        JSON.stringify(rows)
    );


    const oldHtml =
        $button && $button.length
            ? $button.html()
            : "";
const routurl = $("body").data("routurl");

    $.ajax({

        // url:  "modal/grafting_modal.php?action=saveDailyGraftingAllocation",
        url: `${routurl}krf-orderandsales/functions/grafting_view.php?action=saveDailyGraftingAllocation`,

        type: "POST",

        data: formData,

        contentType: false,
        processData: false,
        cache: false,

        beforeSend: function () {

            if ($button && $button.length) {

                $button
                    .prop("disabled", true)
                    .html(
                        '<i class="fa fa-spinner fa-spin"></i> Saving...'
                    );
            }

        },

        success: function (res) {

            try {

                res =
                    typeof res === "string"
                        ? JSON.parse(res)
                        : res;

            } catch (e) {

                alertmessage(
                    "warning",
                    "Invalid response received from server."
                );

                return;
            }


            if (
                res &&
                res.status === "success"
            ) {

                if (
                    typeof callback === "function"
                ) {

                    callback(res);
                }

            }else {

                alertmessage(
                    "warning",
                    res.message ||
                    "Unable to save Grafting Allocation."
                );
            }

        },

        error: function (xhr) {

            console.error(
                "saveDailyGraftingAllocation:",
                xhr.responseText
            );

            alertmessage(
                "warning",
                "Something went wrong while saving Grafting Allocation."
            );

        },

        complete: function () {

            if ($button && $button.length) {

                $button
                    .prop("disabled", false)
                    .html(oldHtml);
            }

        }

    });

}*/
function saveGraftingAllocationRows(
    rows,
    $button,
    callback
) {

    // ==========================================================
    // CURRENT CONTEXT
    // ==========================================================

    const projectid =
        $("body").data("projectid") || "";

    const loginid =
        $("body").data("loginid") || "";

    const locationid =
        $("#labourdetails").data("locationid") ||
        $("#locationid").val() ||
        "";

    const graftingdate =
        $("#labourdetails").data("graftingdate") ||
        $("#graftingdate").val() ||
        "";

    const productid =
        $("#labourdetails").data("productid") ||
        "";

    const productname =
        $("#labourdetails").data("productname") ||
        "";

    const stockorder_no =
        $("#labourdetails").data("stockorder") ||
        "";

    const psono =
        activeGraftingCheckbox
            ? (
                $(activeGraftingCheckbox).data("psono") ||
                ""
              )
            : "";


    const grafted_tray_id =
        $("#graftedtraylist").val() || "";


    // ==========================================================
    // VALIDATION
    // ==========================================================

    if (!projectid) {

        alertmessage(
            "warning",
            "Project ID is missing."
        );

        return;
    }


    if (!locationid) {

        alertmessage(
            "warning",
            "Location is missing."
        );

        return;
    }


    if (!graftingdate) {

        alertmessage(
            "warning",
            "Grafting Date is missing."
        );

        return;
    }


    if (!stockorder_no) {

        alertmessage(
            "warning",
            "Stock Order No. is missing."
        );

        return;
    }


    if (!productid) {

        alertmessage(
            "warning",
            "Product is missing."
        );

        return;
    }


    if (!grafted_tray_id) {

        alertmessage(
            "warning",
            "Please select Grafted Tray Type."
        );

        return;
    }


    if (
        !Array.isArray(rows) ||
        rows.length === 0
    ) {

        alertmessage(
            "warning",
            "No labour allocation found to save."
        );

        return;
    }


    // ==========================================================
    // PREPARE REQUEST
    // ==========================================================

    const formData =
        new FormData();


    formData.append(
        "projectid",
        projectid
    );

    formData.append(
        "loginid",
        loginid
    );

    formData.append(
        "locationid",
        locationid
    );

    formData.append(
        "graftingdate",
        graftingdate
    );

    formData.append(
        "productid",
        productid
    );

    formData.append(
        "productname",
        productname
    );

    formData.append(
        "stockorder_no",
        stockorder_no
    );

    formData.append(
        "psono",
        psono
    );

    formData.append(
        "grafted_tray_id",
        grafted_tray_id
    );

    formData.append(
        "allocationdata",
        JSON.stringify(rows)
    );


    // ==========================================================
    // BUTTON STATE
    // ==========================================================

    const oldHtml =
        $button && $button.length
            ? $button.html()
            : "";


    const routurl =
        $("body").data("routurl");


    // ==========================================================
    // AJAX SAVE
    // ==========================================================

    $.ajax({

        url:
            `${routurl}krf-orderandsales/functions/grafting_view.php?action=saveDailyGraftingAllocation`,

        type: "POST",

        data: formData,

        contentType: false,
        processData: false,
        cache: false,

        dataType: "json",


        beforeSend: function () {

            if (
                $button &&
                $button.length
            ) {

                $button
                    .prop("disabled", true)
                    .html(
                        '<i class="fa fa-spinner fa-spin"></i> Saving...'
                    );
            }

        },


        success: function (res) {

            if (
                res &&
                res.status === "success"
            ) {

                // IMPORTANT:
                // Common function only reports success.
                // Single/All callback decides UI refresh.

                if (
                    typeof callback === "function"
                ) {

                    callback(res);

                } else {

                    alertmessage(
                        "success",
                        res.message ||
                        "Grafting allocation saved successfully."
                    );
                }

            } else {

                alertmessage(
                    "warning",
                    res && res.message
                        ? res.message
                        : "Unable to save Grafting Allocation."
                );
            }

        },


        error: function (xhr) {

            console.error(
                "saveDailyGraftingAllocation:",
                xhr.responseText
            );


            let message =
                "Something went wrong while saving Grafting Allocation.";


            // Try to show actual server error
            if (
                xhr.responseJSON &&
                xhr.responseJSON.message
            ) {

                message =
                    xhr.responseJSON.message;
            }


            alertmessage(
                "warning",
                message
            );

        },


        complete: function () {

            if (
                $button &&
                $button.length
            ) {

                $button
                    .prop("disabled", false)
                    .html(oldHtml);
            }

        }

    });
}
/*$(document).on( "click",  ".btnSaveLabourGrafting",function () {
        const $button =  $(this);
        const $row = $button.closest("tr");
        const rowData = getGraftingAllocationRowData($row);
        // VALIDATE LABOUR
        if (!rowData.labourid) {
            alertmessage(
                "warning",
                "Invalid labour."
            );

            return;
        }
        if (rowData.noofgraftedtry <= 0) {
            alertmessage(
                "warning",
                "Please enter Grafted Tray Target greater than 0."
            );

            return;
        }


        // SINGLE SAVE
        // ------------------------------------------------------

        saveGraftingAllocationRows(
            [rowData],
            $button,

            function (res) {

                // Row saved successfully

                $row.attr(
                    "data-allocation-saved",
                    "1"
                );

refreshGraftingAllocationModalData();
                // Enable Print Label if required
                $row
                    .find(".btnPrintGraftingLabel")
                    .prop("disabled", false);

            }
        );

    }
);*/
$(document).on(
    "click",
    ".btnSaveLabourGrafting",
    function () {

        const $button =  $(this);

        const $row = $button.closest("tr");
        const todayTarget =  parseInt( $row.find(".commonDayTarget").val(), 10 ) || 0;
        if (todayTarget <= 0) {

            alertmessage(
                "warning",
                "Please enter day target"
            );

            return;
        }


        const rowData =
            getGraftingAllocationRowData(
                $row
            );


        // ======================================================
        // VALIDATE LABOUR
        // ======================================================

        if (!rowData.labourid) {

            alertmessage(
                "warning",
                "Invalid labour."
            );

            return;
        }


        // ======================================================
        // VALIDATE NEW ALLOCATION
        // ======================================================

        if (
            Number(
                rowData.noofgraftedtry || 0
            ) <= 0
        ) {

            alertmessage(
                "warning",
                "Please enter Grafted Tray Count greater than 0."
            );

            return;
        }


        // ======================================================
        // SAVE THIS LABOUR
        // ======================================================

        saveGraftingAllocationRows(

            [rowData],

            $button,

            function (res) {

                // ==============================================
                // MARK CURRENT ROW SAVED
                // ==============================================

                $row.attr(
                    "data-allocation-saved",
                    "1"
                );


                // ==============================================
                // CLEAR ONLY NEW ALLOCATION
                //
                // IMPORTANT:
                // Labour stays in table so user can allocate
                // additional trays again.
                // ==============================================

                $row
                    .find(".graftraytetarget")
                    .val("");


                $row
                    .find(".roottraytarget")
                    .val(0);


                $row
                    .find(".sciontraytarget")
                    .val(0);


                $row
                    .find(".selectforgrafting")
                    .prop("checked", false);


                // ==============================================
                // UPDATE CURRENT FOOTER
                // ==============================================

                updateGraftingTargetFooter();


                // ==============================================
                // GET UPDATED ASSIGNED QTY FROM DATABASE
                // AND REFRESH PRINT AVAILABILITY
                // ==============================================

                refreshGraftingAllocationModalData();


                // ==============================================
                // SUCCESS MESSAGE
                // ==============================================

                const generated =
                    parseInt(
                        res &&
                        res.data &&
                        res.data.grafted_trays_generated
                            ? res.data.grafted_trays_generated
                            : 0,
                        10
                    ) || 0;


                if (generated > 0) {

                    alertmessage(
                        "success",
                        generated.toLocaleString("en-IN") +
                        " grafted tray(s) saved successfully. Barcode is ready for printing."
                    );

                } else {

                    alertmessage(
                        "success",
                        res.message ||
                        "Grafting allocation saved successfully."
                    );
                }

            }

        );

    }
);
// $(document).on(
//     "click",
//     "#updategraftingallocationdetailsbtn",
//     function (e) {

//         e.preventDefault();

//         const $button =
//             $(this);

//         const rows = [];


//         // ======================================================
//         // ALL LABOUR ROWS
//         //
//         // Checkbox DOES NOT matter.
//         //
//         // Only condition:
//         // graftraytetarget > 0
//         // ======================================================

//         $("#allocationdailyGraftingTable tbody tr")
//             .each(function () {

//                 const $row =
//                     $(this);


//                 // Ignore "No data" / loading rows
//                 if (!$row.data("labourid")) {
//                     return;
//                 }


//                 const rowData =
//                     getGraftingAllocationRowData($row);


//                 // Only target > 0 is eligible
//                 if (rowData.noofgraftedtry > 0) {

//                     rows.push(rowData);

//                 }

//             });


//         if (rows.length === 0) {

//             alertmessage(
//                 "warning",
//                 "Please enter Grafted Tray Target greater than 0 for at least one labour."
//             );

//             return;
//         }


//         saveGraftingAllocationRows(
//             rows,
//             $button,

//             function (res) {

//                 // Mark saved rows
//                 $("#allocationdailyGraftingTable tbody tr")
//                     .each(function () {

//                         const $row =
//                             $(this);

//                         const target =
//                             parseInt(
//                                 $row
//                                     .find(".graftraytetarget")
//                                     .val(),
//                                 10
//                             ) || 0;


//                         if (target > 0) {

//                             $row.attr(
//                                 "data-allocation-saved",
//                                 "1"
//                             );

//                             $row
//                                 .find(".btnPrintGraftingLabel")
//                                 .prop(
//                                     "disabled",
//                                     false
//                                 );
//                         }

//                     });

//             }
//         );

//     }
// );
$(document).on(
    "click",
    "#updategraftingallocationdetailsbtn",
    function () {

        const $button =
            $(this);


        const rows = [];

        const $rowsToClear =  $();
        let validationFailed = false;



        // ======================================================
        // COLLECT ALLOCATIONS
        // ======================================================

        $("#allocationdailyGraftingTable tbody tr")
            .each(function () {


                const $row =  $(this);

                


                // Must be actual labour row
                if (
                    !$row.find(".graftraytetarget").length
                ) {
                    return;
                }


                const rowData =
                    getGraftingAllocationRowData(
                        $row
                    );
                    const todayTarget =  parseInt(  $row.find(".commonDayTarget").val(),   10  ) || 0;


                const graftedQty =
                    Number(
                        rowData.noofgraftedtry || 0
                    );
                if (graftedQty > 0 && todayTarget <=0 ) {

                    validationFailed =true;
                    return
                }


                // Save only rows having NEW allocation
                if (graftedQty > 0 && todayTarget > 0) {

                    rows.push(rowData);
                }

            });


        // ======================================================
        // NOTHING TO SAVE
        // ======================================================
        if(validationFailed){
            alertmessage(
                "warning",
                "Please enter day target"
            );

            return;

        }

        if (rows.length === 0) {

            alertmessage(
                "warning",
                "Please enter Grafted Tray Count for at least one labour."
            );

            return;
        }


        // ======================================================
        // SAVE ALL
        // ======================================================

        saveGraftingAllocationRows(

            rows,

            $button,

            function (res) {

                // ==============================================
                // CLEAR ONLY ROWS THAT HAD NEW ALLOCATION
                // ==============================================

                $("#allocationdailyGraftingTable tbody tr")
                    .each(function () {

                        const $row =
                            $(this);


                        const target =
                            parseInt(
                                $row
                                    .find(".graftraytetarget")
                                    .val(),
                                10
                            ) || 0;


                        if (target > 0) {

                            $row.attr(
                                "data-allocation-saved",
                                "1"
                            );


                            $row
                                .find(".graftraytetarget")
                                .val("");


                            $row
                                .find(".roottraytarget")
                                .val(0);


                            $row
                                .find(".sciontraytarget")
                                .val(0);


                            $row
                                .find(".selectforgrafting")
                                .prop(
                                    "checked",
                                    false
                                );
                        }

                    });


                // ==============================================
                // RESET FOOTER
                // ==============================================

                updateGraftingTargetFooter();


                // ==============================================
                // REFRESH ASSIGNED + PRINT BUTTONS
                // ==============================================

                refreshGraftingAllocationModalData();


                // ==============================================
                // SUCCESS
                // ==============================================

                const generated =
                    parseInt(
                        res &&
                        res.data &&
                        res.data.grafted_trays_generated
                            ? res.data.grafted_trays_generated
                            : 0,
                        10
                    ) || 0;


                if (generated > 0) {

                    alertmessage(
                        "success",
                        generated.toLocaleString("en-IN") +
                        " grafted tray(s) saved successfully. Labels are ready for printing."
                    );

                } else {

                    alertmessage(
                        "success",
                        res.message ||
                        "Daily grafting allocation saved successfully."
                    );
                }

            }

        );

    }
);
$(document).on(
    "click",
    ".btnLabourGraftingHistory",
    function () {

        const $btn = $(this);

        const labourid =
            $btn.data("labourid") || "";

        const labourname =
            $btn.data("labourname") || "";

        const projectid =
            $("body").data("projectid");

        const locationid =
            $("#locationid").val();

        const graftingdate =
            $("#graftingdate").val();

        const routurl =
            $("body").data("routurl");


        // =====================================================
        // HEADER
        // =====================================================

        $("#graftingSummaryLabourName")
            .text(
                labourid + " - " + labourname +
                " | " + graftingdate
            );


        $("#summaryDayTarget").text("0");
        $("#summaryTotalAssigned").text("0");
        $("#summaryBalance").text("0");


        $("#labourGraftingSummaryBody").html(`
            <tr>
                <td colspan="6"
                    class="text-center py-4">

                    <div class="spinner-border
                                spinner-border-sm
                                text-primary">
                    </div>

                    <span class="ms-2">
                        Loading summary...
                    </span>

                </td>
            </tr>
        `);


        // =====================================================
        // OPEN MODAL
        // =====================================================

        $("#labourGraftingSummaryModal")
            .modal("show");


        // =====================================================
        // LOAD HISTORY
        // =====================================================

        $.ajax({

            type: "POST",

            url:
                `${routurl}krf-orderandsales/modal/grafting_modal.php` +
                `?action=GetLabourDailyGraftingSummary`,

            data: {
                projectid: projectid,
                locationid: locationid,
                labourid: labourid,
                graftingdate: graftingdate
            },

            dataType: "JSON",

            success: function (res) {

                if (
                    !res ||
                    res.status !== "success"
                ) {

                    $("#labourGraftingSummaryBody").html(`
                        <tr>
                            <td colspan="6"
                                class="text-center text-danger">

                                ${
                                    res && res.message
                                        ? res.message
                                        : "Unable to load summary."
                                }

                            </td>
                        </tr>
                    `);

                    return;
                }


                // =============================================
                // SUMMARY
                // =============================================

                const summary =
                    res.summary || {};


                $("#summaryDayTarget").text(
                    Number(
                        summary.daytarget || 0
                    ).toLocaleString("en-IN")
                );


                $("#summaryTotalAssigned").text(
                    Number(
                        summary.totalassigned || 0
                    ).toLocaleString("en-IN")
                );


                $("#summaryBalance").text(
                    Number(
                        summary.balance || 0
                    ).toLocaleString("en-IN")
                );


                // =============================================
                // DETAILS
                // =============================================

                const details =
                    Array.isArray(res.details)
                        ? res.details
                        : [];


                if (details.length === 0) {

                    $("#labourGraftingSummaryBody").html(`
                        <tr>
                            <td colspan="6"
                                class="text-center text-muted">

                                No allocation found.

                            </td>
                        </tr>
                    `);

                    return;
                }


                let rows = "";


                details.forEach(function (row) {

                    rows += `
                        <tr>

                            <td>
                                ${row.productname || "-"}
                            </td>

                            <td>
                                ${row.stockorder_no || "-"}
                            </td>

                            <td class="text-center fw-bold">

                                ${Number(
                                    row.assigned_qty || 0
                                ).toLocaleString("en-IN")}

                            </td>

                            <td class="text-center">

                                ${Number(
                                    row.root_assigned || 0
                                ).toLocaleString("en-IN")}

                            </td>

                            <td class="text-center">

                                ${Number(
                                    row.scion_assigned || 0
                                ).toLocaleString("en-IN")}

                            </td>

                            <td class="text-center">
                                ${row.allocationtime || "-"}
                            </td>

                        </tr>
                    `;
                });


                $("#labourGraftingSummaryBody")
                    .html(rows);
            },


            error: function (xhr) {

                console.error(
                    "Grafting summary error:",
                    xhr.responseText
                );


                $("#labourGraftingSummaryBody").html(`
                    <tr>
                        <td colspan="6"
                            class="text-center text-danger">

                            Error while loading summary.

                        </td>
                    </tr>
                `);
            }
        });
    }
);
// function refreshGraftingPrintAvailability() {

//     const projectid =
//         $("body").data("projectid");

//     const locationid =
//         $("#locationid").val();

//     const graftingdate =
//         $("#graftingdate").val();

//     const stockorder_no =
//         $("#labourdetails").data("stockorder") || "";

//     const productid =
//         $("#labourdetails").data("productid") || "";

//     const routurl =
//         $("body").data("routurl");


//     // ------------------------------------------
//     // RESET
//     // ------------------------------------------

//     $(".btnPrintGraftingLabel")
//         .addClass("d-none")
//         .removeAttr("data-barcode-count");


//     $("#btnPrintAllGraftingLabels")
//         .addClass("d-none")
//         .removeAttr("data-barcode-count");


//     if (
//         !projectid ||
//         !locationid ||
//         !graftingdate ||
//         !stockorder_no ||
//         !productid
//     ) {
//         return;
//     }


//     $.ajax({

//         url:
//             `${routurl}krf-orderandsales/functions/grafting_view.php?action=getGraftingPrintAvailability`,

//         type: "POST",

//         dataType: "json",

//         data: {
//             projectid: projectid,
//             locationid: locationid,
//             graftingdate: graftingdate,
//             stockorder_no: stockorder_no,
//             productid: productid
//         },


//         success: function (res) {

//             if (
//                 !res ||
//                 res.status !== "success"
//             ) {
//                 return;
//             }


//             const printData =
//                 Array.isArray(res.data)
//                     ? res.data
//                     : [];


//             // --------------------------------------
//             // LABOUR-WISE PRINT
//             // --------------------------------------

//             printData.forEach(function (item) {

//                 const labourid =
//                     String(item.labourid || "");

//                 const barcodeCount =
//                     parseInt(
//                         item.barcode_count,
//                         10
//                     ) || 0;


//                 if (
//                     !labourid ||
//                     barcodeCount <= 0
//                 ) {
//                     return;
//                 }


//                 $("#allocationdailyGraftingTable tbody tr")
//                     .filter(function () {

//                         return String(
//                             $(this).data("labourid") || ""
//                         ) === labourid;

//                     })
//                     .find(".btnPrintGraftingLabel")
//                     .removeClass("d-none")
//                     .attr(
//                         "data-barcode-count",
//                         barcodeCount
//                     );
//             });


//             // --------------------------------------
//             // PRINT ALL
//             // --------------------------------------

//             const totalBarcodes =
//                 parseInt(
//                     res.total_barcodes,
//                     10
//                 ) || 0;


//             if (totalBarcodes > 0) {

//                 $("#btnPrintAllGraftingLabels")
//                     .removeClass("d-none")
//                     .attr(
//                         "data-barcode-count",
//                         totalBarcodes
//                     );
//             }

//         },

//         error: function (xhr) {

//             console.error(
//                 "Print availability error:",
//                 xhr.responseText
//             );
//         }

//     });
// }
function refreshGraftingPrintAvailability() {

    const projectid =
        $("body").data("projectid");

    const locationid =
        String($("#locationid").val() || "").trim();

    const graftingdate =
        String($("#graftingdate").val() || "").trim();

    const stockorder_no =
        String(
            $("#labourdetails").data("stockorder") || ""
        ).trim();

    const productid =
        String(
            $("#labourdetails").data("productid") || ""
        ).trim();

    const routurl =
        $("body").data("routurl");


    // ==========================================================
    // RESET PRINT BUTTONS FIRST
    // ==========================================================

    $(".btnPrintGraftingLabel")
        .addClass("d-none")
        .removeAttr("data-barcode-count");

    $("#btnPrintAllGraftingLabels")
        .addClass("d-none")
        .removeAttr("data-barcode-count");


    // ==========================================================
    // VALIDATE CONTEXT
    // ==========================================================

    if (
        !projectid ||
        !locationid ||
        !graftingdate ||
        !stockorder_no ||
        !productid
    ) {

        console.warn(
            "Print availability skipped - incomplete context",
            {
                projectid: projectid,
                locationid: locationid,
                graftingdate: graftingdate,
                stockorder_no: stockorder_no,
                productid: productid
            }
        );

        return;
    }


    // ==========================================================
    // GET PRINTABLE BARCODE COUNTS
    // ==========================================================

    $.ajax({

        url:
            `${routurl}krf-orderandsales/functions/grafting_view.php?action=getGraftingPrintAvailability`,

        type: "POST",

        dataType: "json",

        data: {

            projectid: projectid,

            locationid: locationid,

            graftingdate: graftingdate,

            stockorder_no: stockorder_no,

            productid: productid
        },


        success: function (res) {

            console.log(
                "Grafting print availability:",
                res
            );


            if (
                !res ||
                res.status !== "success"
            ) {

                console.warn(
                    "Invalid print availability response:",
                    res
                );

                return;
            }


            const printData =
                Array.isArray(res.data)
                    ? res.data
                    : [];


            let totalPrintable = 0;


            // ==================================================
            // LABOUR-WISE PRINT BUTTON
            // ==================================================

            printData.forEach(function (item) {

                const labourid =
                    String(
                        item.labourid || ""
                    ).trim();

                const barcodeCount =
                    parseInt(
                        item.barcode_count,
                        10
                    ) || 0;


                if (
                    labourid === "" ||
                    barcodeCount <= 0
                ) {

                    return;
                }


                totalPrintable += barcodeCount;


                $("#allocationdailyGraftingTable tbody tr")
                    .each(function () {

                        const $row =
                            $(this);

                        const rowLabourId =
                            String(
                                $row.data("labourid") || ""
                            ).trim();


                        if (
                            rowLabourId === labourid
                        ) {

                            $row
                                .find(".btnPrintGraftingLabel")
                                .removeClass("d-none")
                                .prop("disabled", false)
                                .attr(
                                    "data-barcode-count",
                                    barcodeCount
                                );

                        }

                    });

            });


            // ==================================================
            // PRINT ALL
            // ==================================================

            const serverTotal =
                parseInt(
                    res.total_barcodes,
                    10
                ) || 0;


            const finalTotal =
                serverTotal > 0
                    ? serverTotal
                    : totalPrintable;


            if (finalTotal > 0) {

                $("#btnPrintAllGraftingLabels")
                    .removeClass("d-none")
                    .prop("disabled", false)
                    .attr(
                        "data-barcode-count",
                        finalTotal
                    );

            } else {

                $("#btnPrintAllGraftingLabels")
                    .addClass("d-none");

            }

        },


        error: function (
            xhr,
            status,
            error
        ) {

            console.error(
                "getGraftingPrintAvailability failed:",
                status,
                error,
                xhr.responseText
            );

        }

    });

}
$(document).on(
    "click",
    ".btnPrintGraftingLabel",
    function () {
debugger
        const labourid =
            $(this).data("labourid") || "";

        if (!labourid) {

            alertmessage(
                "warning",
                "Labour information not found."
            );

            return;
        }


        printGraftedTrayLabels(labourid);
    }
);
function printGraftedTrayLabels(labourid = "") {

    const projectid =
        $("body").data("projectid");

    const locationid =
        $("#locationid").val();

    const graftingdate =
        $("#graftingdate").val();

    const stockorder =
        $("#labourdetails").data("stockorder") || "";

    const productid =
        $("#labourdetails").data("productid") || "";

    const routurl =
        $("body").data("routurl");


    const formData =
        new FormData();


    formData.append(
        "projectid",
        projectid
    );

    formData.append(
        "locationid",
        locationid
    );

    formData.append(
        "graftingdate",
        graftingdate
    );

    formData.append(
        "stockorder",
        stockorder
    );

    formData.append(
        "productid",
        productid
    );


    if (labourid) {

        formData.append(
            "laborid",
            labourid
        );
    }


    $.ajax({

        url:
            `${routurl}krf-orderandsales/functions/qrcode_print_graftedtray.php`,

        type: "POST",

        data: formData,

        contentType: false,

        processData: false,

        success: function (tspl) {

            if (!tspl || !String(tspl).trim()) {

                alertmessage(
                    "warning",
                    "No barcode found for printing."
                );

                return;
            }


            /*
             * IMPORTANT:
             *
             * Put your EXISTING TSPL printer function here.
             *
             * Example:
             *
             * printTSPL(tspl);
             *
             * Use whatever function your project already uses
             * to send TSPL to the barcode printer.
             */

        },

        error: function (xhr) {

            console.error(
                "Barcode print error:",
                xhr.responseText
            );

            alertmessage(
                "warning",
                "Unable to generate barcode labels."
            );
        }

    });
}
$(document).on(
    "click",
    "#btnPrintAllGraftingLabels",
    function () {

        // Blank labour means ALL labour
        printGraftedTrayLabels("");

    }
);
 function refreshGraftingAllocationModalData() {

    const graftingdate =
        $("#labourdetails").data("graftingdate") ||
        $("#graftingdate").val() ||
        "";

    const locationid =
        $("#labourdetails").data("locationid") ||
        $("#locationid").val() ||
        "";

    const productid =
        $("#labourdetails").data("productid") ||
        "";

    if (
        !graftingdate ||
        !locationid ||
        !productid
    ) {
        return;
    }


    GetlaboursWithLocationProductForGrafting(
        graftingdate,
        locationid,
        productid,
        function (res) {

            if (!Array.isArray(res)) {
                return;
            }


            // =====================================================
            // UPDATE EACH EXISTING LABOUR ROW
            // DO NOT REBUILD THE TABLE
            // =====================================================

            res.forEach(function (labour) {

                const labourid =
                    String(labour.LabourId || "").trim();

                if (!labourid) {
                    return;
                }


                const $row =
                    $("#allocationdailyGraftingTable tbody tr")
                        .filter(function () {

                            return String(
                                $(this).data("labourid") || ""
                            ).trim() === labourid;

                        })
                        .first();


                if (!$row.length) {
                    return;
                }


                // =================================================
                // CURRENT TOTAL ASSIGNED
                // =================================================

                const assignedQty =
                    Number(
                        labour.dailyassigned || 0
                    );


                $row.attr(
                    "data-dailyassigned",
                    assignedQty
                );


                // =================================================
                // DAY TARGET
                // =================================================

                const todayTarget =
                    Number(
                        labour.todaystarget || 0
                    );

                const targetSource =
                    labour.targetsource || "none";


                $row.attr(
                    "data-daytarget",
                    todayTarget
                );

                $row.attr(
                    "data-targetsource",
                    targetSource
                );


                // =================================================
                // UPDATE DAY TARGET INPUT
                // ONLY WHEN SERVER HAS A TARGET
                // =================================================

                if (todayTarget > 0) {

                    $row
                        .find(".commonDayTarget")
                        .val(todayTarget);
                }


                // =================================================
                // UPDATE ASSIGNED BADGE
                // =================================================

                let assignedHtml = "";


                if (assignedQty > 0) {

                    assignedHtml = `

                        <button
                            type="button"
                            class="btn btn-link p-0 ms-1 btnLabourGraftingHistory"

                            data-labourid="${labour.LabourId || ''}"
                            data-labourname="${labour.LabourName || ''}"

                            title="View today's allocation summary"
                        >

                            <span class="badge bg-info text-dark">

                                Assigned:
                                ${assignedQty.toLocaleString("en-IN")}

                            </span>

                            <small class="ms-1">
                                View
                            </small>

                        </button>
                    `;

                } else {

                    assignedHtml = `

                        <span
                            class="badge bg-light text-dark border ms-1 assigned-zero-badge"
                        >
                            Assigned: 0
                        </span>
                    `;
                }


                $row
                    .find(".labourAssignedContainer")
                    .html(assignedHtml);

            });


            // =====================================================
            // REFRESH PRINT AVAILABILITY FROM DATABASE
            // =====================================================

            refreshGraftingPrintAvailability();

        }
    );
}
  $(document).on(
    "click",
    "#btnApplyTodayTargetAll",
    function () {

        const target =
            parseInt(
                $(".commonDayTarget").val(),
                10
            ) || 0;


        // ==============================================
        // VALIDATE
        // ==============================================

        if (target <= 0) {

            $(".commonDayTarget")
                .addClass("is-invalid")
                .focus();

            alertmessage(
                "warning",
                "Today Target cannot be empty or 0."
            );

            return;
        }


        $(".commonDayTarget")
            .removeClass("is-invalid");


        // ==============================================
        // APPLY TO ALL LABOUR ROWS
        // ==============================================

        $("#allocationdailyGraftingTable tbody tr")
            .each(function () {

                const $row = $(this);


                if (
                    !$row.find(".commonDayTarget").length
                ) {
                    return;
                }


                $row
                    .find(".commonDayTarget")
                    .val(target)
                    .removeClass("is-invalid");


                $row.attr(
                    "data-daytarget",
                    target
                );

            });


        alertmessage(
            "success",
            "Today Target " +
            target.toLocaleString("en-IN") +
            " applied to all labours."
        );

    }
);

window.closeLabourGraftingHistoryModal = function () {

    const $historyModal =
        $("#labourGraftingSummaryModal");

    if (!$historyModal.length) {
        console.error(
            "labourGraftingSummaryModal not found."
        );
        return;
    }

    // Close only child/history modal
    $historyModal.modal("hide");


    // Because main allocation modal is still open,
    // restore modal-open state after child closes
    setTimeout(function () {

        const $parentModal =
            $("#labourdetails");

        if (
            $parentModal.length &&
            $parentModal.hasClass("show")
        ) {
            $("body").addClass("modal-open");
        }

    }, 300);
};


});
</script>
