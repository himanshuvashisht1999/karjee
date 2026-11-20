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
  
  @media (max-width: 768px) {
    #labourdetails .modal-dialog {
        width: 96% !important;
        max-width: 96% !important;
        margin: 10px auto;
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
 /*  $(document).on("change", ".grafting-checkbox", function () { 
        const $checkbox = $(this);
        if (!$checkbox.is(":checked")) {
          $("#labourdetails").addClass("d-none");
            return;
        }
        //$(".grafting-checkbox").prop("checked", false);
        $("#labourdetails").removeClass("d-none");
        const stockorder = $checkbox.data("stockorder") || "";
        const productname = $checkbox.data("productname") || "";
        const productid = $checkbox.data("productid") || "";
        const totalRootTray = Number($checkbox.data("totalroottray")) || 0;
        const totalScionTray = Number($checkbox.data("totalsciontray")) || 0;
        const totalRootQty = Number($checkbox.data("totalrootqty")) || 0;
        const totalScionQty = Number($checkbox.data("totalscionqty")) || 0;
        const sourceLocationId = $("#locationid").val() || "";
        debugger;
      GetlaboursWithLocationProductForGrafting($("#graftingdate").val(), $("#locationid").val(), productid, function(res){
        // console.log(res);
        var lbwithloc="";
        if(res.length>0){
          let pndallc=0;
          for (var i = 0; i < res.length; i++) {
            if(res[i].allocationstatus=="yes"){
              lbwithloc +=`<tr data-labourid='${res[i].LabourId}' data-trayid='${res[i].trayid}'>
                <td style="text-align:center;"><i class='fa fa-check'></i></td>
                <td class="labourname_gfrm" style="min-width:200px;">${res[i].LabourId} - ${res[i].LabourName}</td>
                <td class="allocationtime_fgrm" style="min-width:100px;"><input type="time" class="form-control allocationtime" name="allocationtime" value='${res[i].allocationtime}' readonly /></td>
                <td class="plantspertray" style="min-width:100px;">${res[i].plantpertray||""}</td>
                <td style="min-width:150px;"><input type="text" name="graftraytetarget" class="form-control graftraytetarget" value="${res[i].graftraytetarget||""}" onKeyPress="return isFloatNumber(event,this);" disabled required /></td>
              </tr>`;
            }else{
              pndallc++;
              lbwithloc +=`<tr data-labourid='${res[i].LabourId}' data-trayid='${res[i].trayid}'>
                <td style="text-align:center;"><input type="checkbox" class="selectforgrafting"></td>
                <td class="labourname_gfrm" style="min-width:200px;">${res[i].LabourId} - ${res[i].LabourName}</td>
              <td class="allocationtime_fgrm" style="min-width:100px;"><input type="time" class="form-control allocationtime" name="allocationtime" value="<?php echo date('H:i:s'); ?>" /></td>
                <td class="plantspertray" style="min-width:100px;">${res[i].plantpertray ||""}</td>
                <td style="min-width:150px;"><input type="text" name="graftraytetarget" class="form-control graftraytetarget" value="${res[i].graftraytetarget ||""}" onKeyPress="return isFloatNumber(event,this);" required /></td>
              </tr>`;
            }
          }
          if(lbwithloc){
            $("#allocationdailyGraftingTable tbody").append(lbwithloc);
            if(pndallc>0){
              $("#updategraftingallocationdetailsbtn").removeClass("d-none");
            }
          }
        }else{
          $("#updategraftingallocationdetailsbtn").addClass("d-none");
          $("#allocationdailyGraftingTable tbody").append('<tr><td colspan="5" style="text-align:center;">No data available</td></tr>');
        }
      }); 
    });*/

 $(document).on("change", ".grafting-checkbox", function () {
    const $checkbox = $(this);
    const $selectedRow = $checkbox.closest("tr");
    // ==========================================================
    // UNCHECK
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
    // ONLY ONE GRAFTING ORDER
    // ==========================================================

    $(".grafting-checkbox")
        .not($checkbox)
        .prop("checked", false);

    activeGraftingCheckbox = this;
    graftingAllocationSaved = false;


    // ==========================================================
    // HIDE OTHER STOCK ORDER ROWS
    // ==========================================================

    $("#tbldetails tbody tr")
        .not($selectedRow)
        .hide();


    // ==========================================================
    // GET SELECTED ORDER DATA
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


    // ==========================================================
    // LOCATION NAME
    // ==========================================================

    const locationName =
        $("#locationid option:selected").text() || "-";


    // ==========================================================
    // POPULATE MODAL SUMMARY
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
    // RESET MODAL LABOUR DATA
    // ==========================================================

    $("#allocationgrftinput").val("");

    $("#allocationdailyGraftingTable tbody").html(`
        <tr>
            <td colspan="5"
                class="text-center py-3">

                <div class="spinner-border spinner-border-sm text-primary"
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
    // OPEN MODAL
    // ==========================================================

   $("#labourdetails").modal({
    backdrop: "static",
    keyboard: false
});

$("#labourdetails").modal("show");
debugger

    // ==========================================================
    // LOAD LABOURS
    // ==========================================================

    GetlaboursWithLocationProductForGrafting(
        $("#graftingdate").val(),
        $("#locationid").val(),
        productid,

        function (res) {

            let lbwithloc = "";
            let pndallc = 0;


            if (Array.isArray(res) && res.length > 0) {

                for (let i = 0; i < res.length; i++) {


                    // ==================================================
                    // ALREADY ALLOCATED
                    // ==================================================

                    if (res[i].allocationstatus == "yes") {

                        lbwithloc += `
                            <tr
                                data-labourid="${res[i].LabourId}"
                                data-trayid="${res[i].trayid}"
                                data-labourname="${res[i].LabourName || ''}"
                            > 
                                <td class="text-center">

                                    <i class="fa fa-check text-success"></i>

                                </td>


                                <td class="labourname_gfrm"
                                    style="min-width:200px;">
                                    ${res[i].LabourId}
                                    -
                                    ${res[i].LabourName}

                                </td>

                                <td style="min-width:150px;">

                                    <input
                                        type="text"
                                        name="commonDayTarget"
                                        class="form-control commonDayTarget"

                                        value="${res[i].todaystarget || ""}" 
                                        min="1"
                                        step="1"
                                        required
                                    />
                                     <button type="button"
                                            class="btn btn-outline-primary"
                                            id="btnApplyCommonDayTarget">

                                        <i class="fa fa-check"></i>
                                        Apply to All

                                    </button>

                                </td>


                                <td class="allocationtime_fgrm"
                                    style="min-width:100px;">

                                    <input
                                        type="time"
                                        class="form-control allocationtime"
                                        name="allocationtime"
                                        value="${res[i].allocationtime || ''}"
                                        readonly
                                    />

                                </td>



                                <td style="min-width:150px;">

                                    <input
                                        type="text"
                                        name="graftraytetarget"
                                        class="form-control graftraytetarget"
                                        
                                        disabled
                                        required
                                    />

                                </td>

                                  <td style="min-width:110px;">
                                    <input type="number"
                                           class="form-control text-center roottraytarget"
                                           value="0"
                                           readonly>
                                </td>

                                <td style="min-width:110px;">
                                    <input type="number"
                                           class="form-control text-center sciontraytarget"
                                           value="0"
                                           readonly>
                                </td>

                          <td class="text-center text-nowrap">

                            <button type="button"
                                    class="btn btn-sm btn-success btnSaveLabourGrafting"
                                    title="Save Allocation">

                                <i class="fa fa-save"></i>
                                Save

                            </button>

                            <button type="button"
                                    class="btn btn-sm btn-primary btnPrintGraftingLabel"
                                    title="Print Label"
                                    disabled>

                                <i class="fa fa-print"></i>
                                Print Label

                            </button>

                        </td>


                            </tr>
                        `;


                    } else {


                        // ==================================================
                        // PENDING ALLOCATION
                        // ==================================================

                        pndallc++;


                        lbwithloc += `
                            <tr
                                data-labourid="${res[i].LabourId}"
                                 data-labourname="${res[i].LabourName || ''}"
                                data-trayid="${res[i].trayid}"
                            >

                                <td class="text-center">

                                    <input
                                        type="checkbox"
                                        class="selectforgrafting"
                                    >

                                </td>


                                <td class="labourname_gfrm"
                                    style="min-width:200px;">

                                    ${res[i].LabourId}
                                    -
                                    ${res[i].LabourName}

                                </td>
                                <td style="min-width:150px;">

                                    <input
                                        type="text"
                                        name="commonDayTarget"
                                        class="form-control commonDayTarget"
                                         
                                        value="${res[i].todaystarget || ""}" 
                                        onKeyPress="return isFloatNumber(event,this);"
                                        min="1"
                                        step="1"
                                        required
                                    />
                                  <button type="button"
                                        class="btn btn-outline-primary"
                                        id="btnApplyCommonDayTarget">

                                    <i class="fa fa-check"></i>
                                    Apply to All

                                </button>

                                </td>


                                <td class="allocationtime_fgrm"
                                    style="min-width:100px;">

                                    <input
                                        type="time"
                                        class="form-control allocationtime"
                                        name="allocationtime"
                                        value="<?php echo date('H:i:s'); ?>"
                                    />

                                </td>

                                <td style="min-width:150px;">

                                    <input
                                        type="text"
                                        name="graftraytetarget"
                                        class="form-control graftraytetarget"
                                        
                                        onKeyPress="return isFloatNumber(event,this);"
                                        required
                                    />

                                </td>
                                  <td style="min-width:110px;">
                                    <input type="number"
                                           class="form-control text-center roottraytarget"
                                           value="0"
                                           readonly>
                                </td>

                                <td style="min-width:110px;">
                                    <input type="number"
                                           class="form-control text-center sciontraytarget"
                                           value="0"
                                           readonly>
                                </td>




                          <td class="text-center text-nowrap">

                              <button type="button"
                                      class="btn btn-sm btn-success btnSaveLabourGrafting"
                                      title="Save Allocation">

                                  <i class="fa fa-save"></i>
                                  Save

                              </button>

                              <button type="button"
                                      class="btn btn-sm btn-primary btnPrintGraftingLabel"
                                      title="Print Label"
                                      disabled>

                                  <i class="fa fa-print"></i>
                                  Print Label

                              </button>

                          </td>


                                

                            </tr>
                        `;
                    }
                }


                // ==========================================================
                // DISPLAY LABOUR DATA
                // ==========================================================

                $("#allocationdailyGraftingTable tbody")
                    .html(lbwithloc);


                if (pndallc > 0) {

                    $("#updategraftingallocationdetailsbtn")
                        .removeClass("d-none");

                } else {

                    $("#updategraftingallocationdetailsbtn")
                        .addClass("d-none");
                }


            } else {


                // ==========================================================
                // NO LABOURS
                // ==========================================================

                $("#allocationdailyGraftingTable tbody").html(`
                    <tr>
                        <td colspan="5"
                            class="text-center text-muted py-3">

                            No labour available for allocation.

                        </td>
                    </tr>
                `);


                $("#updategraftingallocationdetailsbtn")
                    .addClass("d-none");
            }
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
function closeGraftingAllocationModal() {
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
//  $(document).on("input", ".graftraytetarget", function () {

//     const $currentInput = $(this);

//     const maxQty =
//         parseInt(
//             $("#labourdetails").data("maxgraftedtrayqty"),
//             10
//         ) || 0;

//     if (maxQty <= 0) {
//         $currentInput.val("");
//         updateGraftingTargetFooter();
//         return;
//     }


//     // -----------------------------------------
//     // Current entered quantity
//     // -----------------------------------------
//     let enteredQty =
//         parseInt($currentInput.val(), 10) || 0;

//     if (enteredQty < 0) {
//         enteredQty = 0;
//     }


//     // -----------------------------------------
//     // Calculate quantity entered in OTHER rows
//     // -----------------------------------------
//     let otherQty = 0;

//     $("#allocationdailyGraftingTable .graftraytetarget")
//         .not($currentInput)
//         .each(function () {

//             otherQty +=
//                 parseInt($(this).val(), 10) || 0;

//         });


//     // -----------------------------------------
//     // Balance available for CURRENT row
//     // -----------------------------------------
//     const balanceAvailable =
//         Math.max(0, maxQty - otherQty);


//     // -----------------------------------------
//     // Restrict entered value to balance
//     // -----------------------------------------
//     if (enteredQty > balanceAvailable) {

//         $currentInput.val(balanceAvailable);

//         alertmessage(
//             "warning",
//             "Only " +
//             balanceAvailable.toLocaleString("en-IN") +
//             " tray(s) are available. Maximum total target is " +
//             maxQty.toLocaleString("en-IN") +
//             "."
//         );

//     } else {

//         $currentInput.val(enteredQty);
//     }


//     updateGraftingTargetFooter();

// });
// $(document).on("input", ".graftraytetarget", function () {

//     const $currentInput = $(this);
//     const $currentRow   = $currentInput.closest("tr");

//     const maxQty =
//         parseInt(
//             $("#labourdetails").data("maxgraftedtrayqty"),
//             10
//         ) || 0;

//     if (maxQty <= 0) {

//         $currentInput.val("");

//         $currentRow.find(".roottraytarget").val(0);
//         $currentRow.find(".sciontraytarget").val(0);

//         updateGraftingTargetFooter();

//         return;
//     }


//     // Current entered quantity
//     let enteredQty =
//         parseInt($currentInput.val(), 10) || 0;

//     if (enteredQty < 0) {
//         enteredQty = 0;
//     }


//     // Calculate target quantity in OTHER rows
//     let otherQty = 0;

//     $("#allocationdailyGraftingTable .graftraytetarget")
//         .not($currentInput)
//         .each(function () {

//             otherQty +=
//                 parseInt($(this).val(), 10) || 0;

//         });


//     // Maximum balance available for this labour
//     const balanceAvailable =
//         Math.max(0, maxQty - otherQty);


//     // Restrict entered target
//     if (enteredQty > balanceAvailable) {

//         enteredQty = balanceAvailable;

//         $currentInput.val(enteredQty);

//         alertmessage(
//             "warning",
//             "Only " +
//             balanceAvailable.toLocaleString("en-IN") +
//             " tray(s) are available. Maximum total target is " +
//             maxQty.toLocaleString("en-IN") +
//             "."
//         );

//     } else {

//         $currentInput.val(enteredQty);
//     }


//     // =====================================================
//     // ROOT / SCION TRAYS - READ ONLY AUTO CALCULATION
//     // =====================================================

//     const finalTargetQty =
//         parseInt($currentInput.val(), 10) || 0;

//     $currentRow
//         .find(".roottraytarget")
//         .val(finalTargetQty);

//     $currentRow
//         .find(".sciontraytarget")
//         .val(finalTargetQty);


//     // Update Max / Allocated / Balance
//     updateGraftingTargetFooter();

// });
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

function saveGraftingAllocationRows(rows, $button, callback) {
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


            if (res && res.status === "success") {

                alertmessage(
                    "success",
                    res.message ||
                    "Grafting allocation saved successfully."
                );


                if (typeof callback === "function") {

                    callback(res);

                }

            } else {

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

}

$(document).on(
    "click",
    ".btnSaveLabourGrafting",
    function () {

        const $button =
            $(this);

        const $row =
            $button.closest("tr");


        // ------------------------------------------------------
        // GET THIS LABOUR ONLY
        // ------------------------------------------------------

        const rowData = getGraftingAllocationRowData($row);


        // ------------------------------------------------------
        // VALIDATE LABOUR
        // ------------------------------------------------------

        if (!rowData.labourid) {

            alertmessage(
                "warning",
                "Invalid labour."
            );

            return;
        }


        // ------------------------------------------------------
        // TARGET MUST BE > 0
        // ------------------------------------------------------

        if (rowData.noofgraftedtry <= 0) {

            alertmessage(
                "warning",
                "Please enter Grafted Tray Target greater than 0."
            );

            return;
        }


        // ------------------------------------------------------
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


                // Enable Print Label if required
                $row
                    .find(".btnPrintGraftingLabel")
                    .prop("disabled", false);

            }
        );

    }
);

$(document).on(
    "click",
    "#updategraftingallocationdetailsbtn",
    function (e) {

        e.preventDefault();

        const $button =
            $(this);

        const rows = [];


        // ======================================================
        // ALL LABOUR ROWS
        //
        // Checkbox DOES NOT matter.
        //
        // Only condition:
        // graftraytetarget > 0
        // ======================================================

        $("#allocationdailyGraftingTable tbody tr")
            .each(function () {

                const $row =
                    $(this);


                // Ignore "No data" / loading rows
                if (!$row.data("labourid")) {
                    return;
                }


                const rowData =
                    getGraftingAllocationRowData($row);


                // Only target > 0 is eligible
                if (rowData.noofgraftedtry > 0) {

                    rows.push(rowData);

                }

            });


        if (rows.length === 0) {

            alertmessage(
                "warning",
                "Please enter Grafted Tray Target greater than 0 for at least one labour."
            );

            return;
        }


        saveGraftingAllocationRows(
            rows,
            $button,

            function (res) {

                // Mark saved rows
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
                                .find(".btnPrintGraftingLabel")
                                .prop(
                                    "disabled",
                                    false
                                );
                        }

                    });

            }
        );

    }
);
 /* $("#updategraftingallocationdetailsbtn").on("click", function(e) {
    e.preventDefault();
      var $btn = $(this);
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

      let locationid = $("#locationid").val();
      form_data.append("locationid", locationid);

      let productid = $("#productid").val();
      form_data.append("productid", productid);

      let psono = $("#psnonohidden").val();
      form_data.append("psono", psono);

      let stockorder_no = $("#pstockonohidden").val();
      form_data.append("stockorder_no", stockorder_no);

      // Collect labour rows
      let labourDataArray = [];
      let ttltrlength = $("#allocationdailyGraftingTable tbody tr").length;
      let ttl_pnd_trlength = $("#allocationdailyGraftingTable tbody tr").find("input[type='checkbox'].selectforgrafting").length;
      // alert(ttltrlength+'--'+ttl_pnd_trlength);
      $("#allocationdailyGraftingTable tbody tr").each(function() {
          let $row = $(this);
          if ($row.find("input[type='checkbox'].selectforgrafting").is(":checked")) {
              let labourid = $row.data("labourid");
              let trayid = $row.data("trayid");
              let graftraytetarget = parseInt($row.find(".graftraytetarget").val()) || 0;
              var allocationtime = $row.find(".allocationtime").val() || '';
              let plantspertray = parseInt($row.find(".plantspertray").text()) || 0;
              if (labourid && graftraytetarget > 0) {
                  labourDataArray.push({
                      labourid: labourid,
                      allocationtime: allocationtime,
                      plantspertray: plantspertray,
                      graftraytetarget: graftraytetarget,
                      trayid: trayid,
                      psono: psono,
                      stockorder_no: stockorder_no
                  });
              }
          }
      });
      if (labourDataArray.length === 0) {
          alertmessage("warning", "❌ Please select at least one labour with target greater than 0.");
          return;
      }

      form_data.append("allocationgraftedData", JSON.stringify(labourDataArray));
      // return false;
      // AJAX
      $.ajax({
          url: "modal/grafting_modal.php?action=SaveUpdatedailyGraftingAllocationForm",
          type: "POST",
          data: form_data,
          contentType: false,
          processData: false,
          cache: false,
          beforeSend: function() {
              $btn.prop("disabled", true).text("Saving...");
          },
          success: function(res) {
            //console.log(res);
              // Ensure res is parsed JSON
              try { res = (typeof res === "string") ? JSON.parse(res) : res; } catch(e) {}

              if (res && res.status === "success") {
                $("#allocationdailyGraftingTable tbody tr").each(function() {
                  let $row = $(this);
                  if ($row.find("input[type='checkbox'].selectforgrafting").is(":checked")) {
                    $row.remove();
                  }
                });
                let rstpnd_trlength = $("#allocationdailyGraftingTable tbody tr").find("input[type='checkbox'].selectforgrafting").length;
                if(rstpnd_trlength>0){
                  Swal.fire({
                      text: res.message,
                      title: "Do you want to update more?",
                      icon: "success",
                      showDenyButton: false,
                      showCancelButton: true,
                      confirmButtonColor: "#3085d6",
                      confirmButtonText: "Yes, Update Now",
                      cancelButtonText: "No, Refresh Now",
                      allowOutsideClick: false,
                      allowEscapeKey: true
                  }).then((result) => {
                      if (result.isConfirmed) {                         
                        
                      }else{
                        // window.location.reload();
                        $("#graftingdate").val(graftingdate).trigger("change");
                      }
                  });
                }else{
                    Swal.fire({
                      position: "top-end",
                      icon: "success",
                      title: res.message,
                      showConfirmButton: false,
                      timer: 500
                    });
                    $("#graftingdate").val(graftingdate).trigger("change");
                    
                }
              } else {
                  alertmessage("warning", res.message || "❌ Something went wrong while saving!");
              }
          },
          error: function(xhr, status, error) {
              console.error("AJAX Error:", error);
              alertmessage("warning", "❌ Something went wrong while saving!");
          },
          complete: function() {
              $btn.prop("disabled", false).text("Save Daily Grafting Allocation Details");
          }
      });
  });*/

});
</script>
