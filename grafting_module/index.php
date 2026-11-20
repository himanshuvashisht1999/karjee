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
 #tbldetails {
    width: 100%;
    table-layout: auto;
}

#tbldetails th,
#tbldetails td {
    vertical-align: middle;
}

#tbldetails th:nth-child(1),
#tbldetails td:nth-child(1) {
    width: 55px;
    text-align: center;
}

#tbldetails th:nth-child(2),
#tbldetails td:nth-child(2) {
    width: 45px;
    text-align: center;
}

#tbldetails th:nth-child(3),
#tbldetails td:nth-child(3) {
    width: 150px;
}

#tbldetails th:nth-child(4),
#tbldetails td:nth-child(4) {
    min-width: 280px;
}

#tbldetails th:nth-child(5),
#tbldetails th:nth-child(6),
#tbldetails th:nth-child(7),
#tbldetails th:nth-child(8) {
    width: 130px;
    text-align: center;
}

#graftingMainTabs {
    border-bottom: 1px solid #dee2e6;
}

#graftingMainTabs .nav-link {
    color: #495057;
    font-size: 13px;
    padding: 11px 18px;
    border-radius: 6px 6px 0 0;
}

#graftingMainTabs .nav-link:hover {
    background: #f7f8fa;
}

#graftingMainTabs .nav-link.active {
    color: #0d6efd;
    font-weight: 700;
    background: #fff;
    border-bottom-color: #fff;
}
</style>
<div class="container-main">

    <div class="row <?php echo isset($_GET['dz']) ? 'd-none' : '' ?>">
        <div class="col-md-12">

            <button
                class="btn btn-sm btn-deepblue m-2 float-end"
                onclick="location.reload();">
                Refresh
            </button>

        </div>
    </div>


    <div class="card mt-1">

        <!-- =====================================================
             TAB HEADER
        ====================================================== -->
        <div class="card-header bg-white pb-0">

            <ul class="nav nav-tabs"
                id="graftingMainTabs"
                role="tablist">

                <!-- TAB 1 -->
                <li class="nav-item" role="presentation">

                    <button
                        class="nav-link active fw-semibold"
                        id="destroy-graft-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#destroy-graft-pane"
                        type="button"
                        role="tab"
                        aria-controls="destroy-graft-pane"
                        aria-selected="true">

                        <i class="bi bi-arrow-repeat me-1"></i>

                        Destroy Tray & Generate Grafted Tray

                    </button>

                </li>


                <!-- TAB 2 -->
                <li class="nav-item" role="presentation">

                    <button
                        class="nav-link fw-semibold"
                        id="generate-barcode-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#generate-barcode-pane"
                        type="button"
                        role="tab"
                        aria-controls="generate-barcode-pane"
                        aria-selected="false">

                        <i class="bi bi-upc-scan me-1"></i>

                        Generate Barcode

                    </button>

                </li>

            </ul>

        </div>


        <!-- =====================================================
             TAB CONTENT
        ====================================================== -->
        <div class="card-body">

            <div class="tab-content" id="graftingMainTabContent">


                <!-- =================================================
                     TAB 1
                     DESTROY OLD TRAY + GENERATE GRAFTED TRAY
                ================================================== -->
                <div
                    class="tab-pane fade show active"
                    id="destroy-graft-pane"
                    role="tabpanel"
                    aria-labelledby="destroy-graft-tab">


                    <div class="row mb-3">

                        <div class="col-md-12 text-center">

                            <h5 class="fw-bold mb-1">
                                Destroy Scion/Root Tray & Generate Grafted Tray
                            </h5>

                            <small class="text-muted">
                                Select Root and Scion trays to generate new grafted trays.
                            </small>

                        </div>

                    </div>


                    <!-- LOCATION -->
                    <div class="row mb-3 mt-3">

                        <div class="col-md-4">

                            <label
                                for="locationlist"
                                class="form-label fw-semibold">

                                Location

                                <span class="text-danger">*</span>

                            </label>

                            <select
                                class="form-control"
                                id="locationlist"
                                name="locationid">
                            </select>

                        </div>

                    </div>


                    <!-- CURRENT TABLE -->
                    <div class="row" id="details">

                        <div
                            class="table-responsive"
                            style="height:60vh;overflow-y:auto;">

                            <table
                                class="table table-hover table-bordered shadow-sm"
                                id="tbldetails">

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

                </div>


                <!-- =================================================
                     TAB 2
                     GENERATE BARCODE
                ================================================== -->
                <div
                    class="tab-pane fade"
                    id="generate-barcode-pane"
                    role="tabpanel"
                    aria-labelledby="generate-barcode-tab">


                    <div class="row mb-4">

                        <div class="col-md-12 text-center">

                            <h5 class="fw-bold mb-1">
                                Generate Grafted Tray Barcode
                            </h5>

                            <small class="text-muted">
                                Generate and print barcodes for grafted trays.
                            </small>

                        </div>

                    </div>


                    <div class="row justify-content-center">

                        <div class="col-lg-8">

                            <div class="card border">

                                <div class="card-body">

                                    <div class="row g-3">


                                        <div class="col-md-6">

                                            <label
                                                for="barcodeLocationList"
                                                class="form-label fw-semibold">

                                                Location
                                                <span class="text-danger">*</span>

                                            </label>

                                            <select
                                                id="barcodeLocationList"
                                                class="form-select">

                                                <option value="">
                                                    -- Select Location --
                                                </option>

                                            </select>

                                        </div>


                                        <div class="col-md-6">

                                            <label
                                                for="cmborderno"
                                                class="form-label fw-semibold">

                                                Stock Order No.

                                            </label>

                                            
                                            <select
                                                id="cmborderno"
                                                class="form-select">

                                                <option value="">
                                                    -- Select Order --
                                                </option>

                                            </select>

                                        </div>


                                        <div class="col-md-6">

                                            <label
                                                for="cmbproduct"
                                                class="form-label fw-semibold">

                                                Product

                                            </label>

                                            
                                            <select
                                                id="cmbproduct"
                                                class="form-select">

                                                <option value="">
                                                    -- Select Product --
                                                </option>

                                            </select>

                                        </div>
                                        <div class="col-md-6">
                                            <label for="barcodeLabourList" class="form-label fw-bold">
                                                Select the labour who performed the grafting
                                                <span class="text-danger">*</span>
                                            </label>

                                            <select class="form-control"
                                                    id="barcodeLabourList"
                                                    name="barcodeLabourList">
                                                <option value="">-- Select Labour who performed the grafting --</option>
                                            </select>
 
                                        </div>


                                        <div class="col-md-4 d-none">

                                            <label
                                                for="barcodeTrayQty"
                                                class="form-label fw-semibold">

                                                No. of Barcodes
                                                <span class="text-danger">*</span>

                                            </label>

                                            <input
                                                type="number"
                                                id="barcodeTrayQty"
                                                class="form-control"
                                                min="1"
                                                step="1"
                                                autocomplete="off">

                                        </div>


                                    </div>


                                    <div class="text-center mt-4">

                                        <button
                                            type="button"
                                            class="btn btn-primary px-4"
                                            id="btnGenerateGraftedBarcode">

                                            <i class="bi bi-upc-scan me-1"></i>

                                            Generate Barcode

                                        </button>

                                    </div>


                                    <!-- GENERATED BARCODE AREA -->
                                    <div
                                        id="generatedBarcodeArea"
                                        class="mt-4 d-none">

                                        <hr>

                                        <div
                                            id="generatedBarcodeList"
                                            class="row g-3">
                                        </div>

                                    </div>


                                </div>

                            </div>

                        </div>

                    </div>


                </div>

            </div>

        </div>

    </div>

</div>

 <div class="modal fade" id="askquestion_modal"  tabindex="-1" aria-labelledby="askquestionModalLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold" id="askquestionModalLabel">
                        Grafting – Generate New Tray
                    </h5>
                    <small class="text-muted">
                        Root and Scion trays will be consumed for generating the new grafted tray.
                    </small>
                </div>
                <button type="button"  class="btn-close"  data-bs-dismiss="modal"  aria-label="Close">
                </button>
            </div>

            <div class="modal-body">
                <!-- Order / Product -->
                <div class="card border-0 bg-light mb-3">
                    <div class="card-body py-3">

                        <div class="row g-3">

                            <div class="col-md-4">
                                <small class="text-muted d-block">
                                    Stock Order No.
                                </small>

                                <strong id="lblorderno">-</strong>
                            </div>

                            <div class="col-md-8">
                                <small class="text-muted d-block">
                                    Product
                                </small>

                                <strong id="lblproductname">-</strong>
                            </div>

                        </div>

                    </div>
                </div>


                <!-- Root / Scion Summary -->
                <div class="row g-3 mb-4">

                    <div class="col-md-6">

                        <div class="card h-100 border-success">

                            <div class="card-header bg-success text-white fw-bold">
                                Root Details
                            </div>

                            <div class="card-body">

                                <div class="row">

                                    <div class="col-6">
                                        <small class="text-muted d-block">
                                            Total Tray
                                        </small>

                                        <strong id="lblroottotaltray">0</strong>
                                    </div>

                                    <div class="col-6">
                                        <small class="text-muted d-block">
                                            Total Plant Qty
                                        </small>

                                        <strong id="lblroottotalplant">0</strong>
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    <div class="col-md-6">

                        <div class="card h-100 border-primary">

                            <div class="card-header bg-primary text-white fw-bold">
                                Scion Details
                            </div>

                            <div class="card-body">

                                <div class="row">

                                    <div class="col-6">
                                        <small class="text-muted d-block">
                                            Total Tray
                                        </small>

                                        <strong id="lblsciontotaltray">0</strong>
                                    </div>

                                    <div class="col-6">
                                        <small class="text-muted d-block">
                                            Total Plant Qty
                                        </small>

                                        <strong id="lblsciontotalplant">0</strong>
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                <!-- =========================================================
                         GRAFTING ASSIGNMENT DETAILS
                    ========================================================= -->
                    <div class="card border-info mb-4">

                        <div class="card-header bg-light fw-bold">
                            <i class="fa fa-info-circle me-1"></i>
                            Grafting Assignment Details
                        </div>

                        <div class="card-body">

                            <div class="row g-3">

                                <!-- LOCATION -->
                                <div class="col-md-4">

                                    <label
                                        for="graftedlocationlist"
                                        class="form-label fw-bold">

                                        Grafted Tray Location
                                        <span class="text-danger">*</span>

                                    </label>

                                    <select
                                        id="graftedlocationlist"
                                        class="form-control">

                                        <option value="">
                                            -- Select Location --
                                        </option>

                                    </select>

                                    <div class="form-text">
                                        Location where the new Grafted trays will be created.
                                    </div>

                                </div>


                                <!-- LABOUR -->
                                <div class="col-md-4">

                                    <label
                                        for="graftinglabourlist"
                                        class="form-label fw-bold">

                                        Grafting Labour
                                        <span class="text-danger">*</span>

                                    </label>

                                    <select
                                        id="graftinglabourlist"
                                        class="form-control">

                                        <option value="">
                                            -- Select Labour --
                                        </option>

                                    </select>

                                    <div class="form-text">
                                        Only active labour with grafting skill is shown.
                                    </div>

                                </div>


                                <!-- TRAY -->
                                <div class="col-md-4">

                                    <label
                                        for="graftedtraylist"
                                        class="form-label fw-bold">

                                        Grafted Tray Type
                                        <span class="text-danger">*</span>

                                    </label>

                                    <select
                                        id="graftedtraylist"
                                        class="form-control">

                                        <option value="">
                                            -- Select Tray --
                                        </option>

                                    </select>

                                    <div
                                        id="graftedTrayInfo"
                                        class="form-text">
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                <!-- New Tray Qty -->
                <div class="row justify-content-center">

                    <div class="col-md-6">

                        <label for="newtrayqty"
                               class="form-label fw-bold">

                            New Tray Qty
                            <span class="text-danger">*</span>

                        </label>

                        <input type="number"
                               id="newtrayqty"
                               class="form-control"
                               min="1"
                               step="1"
                               autocomplete="off">

                        <div class="form-text">
                            Maximum allowed:
                            <strong id="lblmaxnewtrayqty">0</strong>
                            plants
                        </div>

                    </div>

                </div>


                <!-- Poly House -->
                <div class="row justify-content-center mt-4 d-none">

                    <div class="col-md-6">

                        <label for="polyhouselist"
                               class="form-label fw-bold">

                            Poly House
                            <span class="text-danger">*</span>

                        </label>

                        <select class="form-control"
                                id="polyhouselist"
                                name="polyhouselist">
                        </select>

                    </div>

                </div>


                <div class="text-center mt-4">

                    <button type="button"
                            class="btn btn-primary px-4"
                            id="savegraftedtray">
                           Save  Grafted tray

                    </button>

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
  
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
$(document).ready(function(){ 
  debugger;
    LocationMastercallback("Grafting",function(res){
        var locationlist = '<option value="">-- Select Location --</option>'; // default option
        $("#locationlist").empty();
        if(res.status === "success"){
          if(res.data.length > 0){
            resnew=res.data;
            for (var i = 0; i < resnew.length; i++) {
              locationlist += '<option value="' + resnew[i].locationid + '">' + resnew[i].locationname + '</option>';
            }
          }
          $("#locationlist").append(locationlist);
        } 
    });
  
    function  LocationMastercallback(plantstatus,callback) {
        var form_data = new FormData(); 
        var companyid = $("body").data("companyid");
        var subcompanyid = $("body").data("subcompanyid");
        var projectid = $("body").data("projectid");
        form_data.append("companyid", companyid);
        form_data.append("subcompanyid", subcompanyid);
        form_data.append("projectid", projectid); 
        form_data.append("plantstatus", plantstatus);
        $.ajax
            ({
                type: "POST",
                url: "modal/view.php?action=getlocation",
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
    $(document).on("change", "#locationlist", function () {
        const locationid = $(this).val();
        const projectid  = $("body").data("projectid");
        $("#tbldetails tbody").empty();
        if (!locationid) {
            return;
        }
        $("#polyhouselist").empty();
        $.ajax({
            type: "POST",
            url: "modal/view.php?action=locationbaseddata",
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
   $(document).on("change", ".grafting-checkbox", function () {
        debugger
        const $checkbox = $(this);

        if (!$checkbox.is(":checked")) {
            return;
        }

        // Keep only current row selected
        $(".grafting-checkbox").prop("checked", false);

        const stockorder = $checkbox.data("stockorder") || "";
        const productname = $checkbox.data("productname") || "";
        const productid = $checkbox.data("productid") || "";

        const totalRootTray = Number($checkbox.data("totalroottray")) || 0;
        const totalScionTray = Number($checkbox.data("totalsciontray")) || 0;

        const totalRootQty = Number($checkbox.data("totalrootqty")) || 0;
        const totalScionQty = Number($checkbox.data("totalscionqty")) || 0;

        const sourceLocationId = $("#locationlist").val() || "";

        // Fill heading/details
        $("#lblorderno").text(stockorder);
        $("#lblproductname").text(productname);

        $("#lblroottotaltray").text(
            totalRootTray.toLocaleString("en-IN")
        );

        $("#lblsciontotaltray").text(
            totalScionTray.toLocaleString("en-IN")
        );

        $("#lblroottotalplant").text(
            totalRootQty.toLocaleString("en-IN")
        );

        $("#lblsciontotalplant").text(
            totalScionQty.toLocaleString("en-IN")
        );


        /*
         * IMPORTANT:
         * Store Root / Scion PLANT quantities in modal.
         *
         * calculateMaxGraftedTrayQty() will use these
         * after user selects Grafted Tray Type.
         */
        $("#askquestion_modal")
            .data("stockorder", stockorder)
            .data("productid", productid)
            .data("productname", productname)
            .data(
                "sowinglotno_internal",
                $checkbox.data("sowinglotno_internal")
            )
            .data(
                "sowingbatchno",
                $checkbox.data("sowingbatchno")
            )
            .data("totalroottray", totalRootTray)
            .data("totalsciontray", totalScionTray)
            .data("totalrootqty", totalRootQty)
            .data("totalscionqty", totalScionQty)
            .data("maxnewtrayqty", 0);


        // Reset tray qty until Grafted Tray Type is selected
        $("#lblmaxnewtrayqty").text("0");

        $("#newtrayqty")
            .val("")
            .attr("min", 1)
            .attr("max", 0)
            .prop("disabled", true)
            .attr(
                "placeholder",
                "Select Grafted Tray Type first"
            );


        // Load Locations, Labour and Tray Type
        loadGraftingModalMasters(sourceLocationId);


        // Open modal
        const modalElement =
            document.getElementById("askquestion_modal");

        const modal =
            bootstrap.Modal.getOrCreateInstance(modalElement);

        modal.show();
    });
    $(document).on("input", "#newtrayqty", function () {
        const maxQty = parseInt($(this).attr("max"), 10) || 0;
        let value = $(this).val();
        if (value === "") {
            return;
        }
        // Whole number only
        value = parseInt(value, 10);
        if (isNaN(value) || value <= 0) {
            $(this).val("");
            return;
        }
        // Do not allow more than calculated maximum
        if (value > maxQty) {
            value = maxQty;
        }

        $(this).val(value);
    });
    // $(document).on("click", "#savegraftedtray", function () {
    //     debugger;
    //     const $modal = $("#askquestion_modal");
    //     const newtrayqty = parseInt($("#newtrayqty").val(), 10) || 0;
    //     const maxtrayqty = parseInt($modal.data("maxnewtrayqty"), 10) || 0;
    //     const stockorder_no = $modal.data("stockorder") || "";
    //     const productid     = $modal.data("productid") || "";
    //     const productname   = $modal.data("productname") || "";
    //     const locationid    = $("#locationlist").val() || "";
    //     const projectid     = $("body").data("projectid");
    //     // const polyhouseid   = $("#polyhouselist").val() || "";
    //     if (newtrayqty <= 0) {
    //         alert("Please enter New Tray Qty.");
    //         $("#newtrayqty").focus();
    //         return;
    //     }
    //     if (newtrayqty > maxtrayqty) {
    //         alert( "Maximum allowed New Tray Qty is " +  maxtrayqty.toLocaleString("en-IN") + "."  );
    //         $("#newtrayqty").focus();
    //         return;
    //     }
    //     if (!locationid) {
    //         alert("Location is required.");
    //         return;
    //     }
    //     // if (!polyhouseid) {
    //     //     alert("Please select Poly House.");
    //     //     $("#polyhouselist").focus();
    //     //     return;
    //     // }


    //     if (!confirm(
    //         "We are going to destroy " +
    //         newtrayqty + " Root tray(s) and " +
    //         newtrayqty + " Scion tray(s), and generate " +
    //         newtrayqty + " new Grafted tray(s).\n\nContinue?"
    //     )) {
    //         return;
    //     }
    //     const $btn = $(this);
    //     $btn.prop("disabled", true).html(`<span class="spinner-border spinner-border-sm me-1"></span>
    //             Processing... `);

    //     $.ajax({
    //         type: "POST",
    //         url: "modal/view.php?action=savegrafting",
    //         data: {
    //             projectid: projectid,
    //             locationid: locationid,               
    //             stockorder_no: stockorder_no,
    //             productid: productid,
    //             productname: productname,
    //             newtrayqty: newtrayqty
    //         },
    //         dataType: "json",
    //         success: function (res) {
    //             if (!res || res.status !== "success") {
    //                 alert(
    //                     res && res.message
    //                         ? res.message
    //                         : "Unable to save grafting."
    //                 );

    //                 return;
    //             }


    //             alert(
    //                 newtrayqty +
    //                 " new Grafted tray(s) generated successfully."
    //             );


    //             // Close modal
    //             const modalElement =
    //                 document.getElementById("askquestion_modal");

    //             const modal =
    //                 bootstrap.Modal.getInstance(modalElement);

    //             if (modal) {
    //                 modal.hide();
    //             }


    //             // Reload selected location data
    //             $("#locationlist").trigger("change");
    //         },

    //         error: function (xhr) {

    //             console.error(xhr.responseText);

    //             alert("Error while saving grafting.");
    //         },

    //         complete: function () {

    //             $btn.prop("disabled", false)
    //                 .html("Save & Generate Barcode");
    //         }
    //     });
    // });

    $(document).on("click", "#savegraftedtray", function () {
        debugger;
        const $modal = $("#askquestion_modal");
        const newtrayqty = parseInt($("#newtrayqty").val(), 10) || 0;
        const maxtrayqty = parseInt($modal.data("maxnewtrayqty"), 10) || 0;
        const stockorder_no = $modal.data("stockorder") || "";
        const productid = $modal.data("productid") || "";
        const productname = $modal.data("productname") || "";
        const locationid = $("#locationlist").val() || "";
        // New Grafted tray location
        const grafted_locationid = $("#graftedlocationlist").val() || "";
        // Selected Grafting Labour
        const labourid = $("#graftinglabourlist").val() || "";
        // Selected Grafted Tray Type
        const tray_id = parseInt($("#graftedtraylist").val(), 10) || 0;
        const cavityCount =   Number(  $("#graftedtraylist option:selected").data("cavity")  ) || 0;
        const projectid = parseInt($("body").data("projectid"), 10) || 0;
        if (projectid <= 0) {
            alert("Invalid Project.");
            return;
        }
        if (!locationid) {
            alert("Source Grafting Location is required.");
            $("#locationlist").focus();
            return;
        }
        if (!grafted_locationid) {
            alert("Please select Grafted Tray Location.");
            $("#graftedlocationlist").focus();
            return;
        }
        if (!labourid) {
            alert("Please select Grafting Labour.");
            $("#graftinglabourlist").focus();
            return;
        }
        if (tray_id <= 0) {
            alert("Please select Grafted Tray Type.");
            $("#graftedtraylist").focus();
            return;
        }
        if (cavityCount <= 0) {
            alert("Invalid cavity count for selected Grafted Tray.");
            $("#graftedtraylist").focus();
            return;
        }
        if (!stockorder_no) {
            alert("Stock Order No. is required.");
            return;
        }
        if (!productid) {
            alert("Product is required.");
            return;
        }
        if (newtrayqty <= 0) {
            alert("Please enter New Tray Qty.");
            $("#newtrayqty").focus();
            return;
        }
        if (maxtrayqty <= 0) {
            alert("No Grafted tray can be generated with available Root / Scion quantity.");
            return;
        }
        if (newtrayqty > maxtrayqty) {
            alert(
                "Maximum allowed New Tray Qty is " +
                maxtrayqty.toLocaleString("en-IN") +
                "."
            );

            $("#newtrayqty").val(maxtrayqty).focus();
            return;
        }


        /* =========================================================
           CALCULATE EXPECTED PLANT QTY FOR CONFIRMATION ONLY

           Final calculation will again happen on PHP/server.
        ========================================================= */

        const totalRootQty = Number($modal.data("totalrootqty")) || 0;

        const totalScionQty =  Number($modal.data("totalscionqty")) || 0;

        const availablePlantQty =  Math.min(totalRootQty,totalScionQty);

        const requestedPlantQty = newtrayqty * cavityCount;

        const expectedGraftingQty = Math.min( requestedPlantQty,  availablePlantQty  ); 

        const trayName =  $("#graftedtraylist option:selected").text().trim();
        const labourName =   $("#graftinglabourlist option:selected").text().trim();
        const locationName =  $("#graftedlocationlist option:selected").text().trim();
        const confirmMessage =
            "Grafting Details\n\n" +
            "Stock Order: " + stockorder_no + "\n" +
            "Product: " + productname + "\n" +
            "Grafted Tray Type: " + trayName + "\n" +
            "Tray Cavity: " + cavityCount.toLocaleString("en-IN") + "\n" +
            "New Grafted Trays: " + newtrayqty.toLocaleString("en-IN") + "\n" +
            "Plants to be Grafted: " + expectedGraftingQty.toLocaleString("en-IN") + "\n" +
            "Grafted Location: " + locationName + "\n" +
            "Grafting Labour: " + labourName + "\n\n" +
            "Root and Scion trays will be consumed based on plant quantity.\n" +
            "Fully consumed source trays will be marked as Destroy After grafted.\n" +
            "Partially consumed trays will retain their remaining quantity.\n\n" +
            "Continue?";
        if (!confirm(confirmMessage)) {
            return;
        }


        /* =========================================================
           BUTTON PROCESSING STATE
        ========================================================= */

        const $btn = $(this);

        $btn.prop("disabled", true).html(
            `<span class="spinner-border spinner-border-sm me-1"></span> Processing...`
        );


        /* =========================================================
           SAVE
        ========================================================= */

        $.ajax({

            type: "POST",

            url: "modal/view.php?action=savegrafting",

            data: {
                projectid: projectid,

                // Source Root / Scion location
                locationid: locationid,

                // New Grafted location
                grafted_locationid: grafted_locationid,

                // Selected skilled labour
                labourid: labourid,

                // Selected krf_tray_master tray_id
                tray_id: tray_id,

                stockorder_no: stockorder_no,
                productid: productid,
                productname: productname,
                newtrayqty: newtrayqty 
            },

            dataType: "json",


            success: function (res) {

                if (!res || res.status !== "success") {

                    alert(
                        res && res.message
                            ? res.message
                            : "Unable to save grafting."
                    );

                    return;
                }


                const data = res.data || {};


                /* =====================================================
                   SUCCESS MESSAGE
                ===================================================== */

                let message =
                    (data.new_trays || newtrayqty) +
                    " new Grafted tray(s) generated successfully.";


                if (data.grafted_plant_qty) {
                    message +=
                        "\n\nGrafted Plant Qty: " +
                        Number(data.grafted_plant_qty)
                            .toLocaleString("en-IN");
                }


                if (
                    data.root_trays_destroyed !== undefined
                ) {

                    message +=
                        "\nRoot Tray(s) Fully Consumed: " +
                        Number(data.root_trays_destroyed)
                            .toLocaleString("en-IN");
                }


                if (
                    data.root_partial_trays !== undefined &&
                    Number(data.root_partial_trays) > 0
                ) {

                    message +=
                        "\nRoot Partial Tray(s): " +
                        Number(data.root_partial_trays)
                            .toLocaleString("en-IN");
                }


                if (
                    data.scion_trays_destroyed !== undefined
                ) {

                    message +=
                        "\nScion Tray(s) Fully Consumed: " +
                        Number(data.scion_trays_destroyed)
                            .toLocaleString("en-IN");
                }


                if (
                    data.scion_partial_trays !== undefined &&
                    Number(data.scion_partial_trays) > 0
                ) {

                    message +=
                        "\nScion Partial Tray(s): " +
                        Number(data.scion_partial_trays)
                            .toLocaleString("en-IN");
                }


                alert(message);


                /* =====================================================
                   CLOSE MODAL
                ===================================================== */

                const modalElement =
                    document.getElementById("askquestion_modal");

                if (modalElement) {

                    const modal =
                        bootstrap.Modal.getInstance(modalElement);

                    if (modal) {
                        modal.hide();
                    }
                }


                /* =====================================================
                   RELOAD SOURCE LOCATION
                ===================================================== */

                $("#locationlist").trigger("change");
            },


            error: function (xhr) {

                console.error(xhr.responseText);

                let errorMessage =
                    "Error while saving grafting.";

                try {

                    const res =
                        JSON.parse(xhr.responseText);

                    if (res.message) {
                        errorMessage = res.message;
                    }

                }
                catch (e) {
                    // Keep default error message
                }

                alert(errorMessage);
            },


            complete: function () {

                $btn.prop("disabled", false)
                    .html("Save & Generate Barcode");
            }
        });
    });
    $(document).on( "shown.bs.tab", '#graftingMainTabs button[data-bs-toggle="tab"]', function (e) {
        const activeTab = $(e.target).attr("data-bs-target");
        /*if (activeTab === "#destroy-graft-pane") {

        } */
        if (activeTab === "#generate-barcode-pane") {
            $("#barcodeLocationList").empty();
            $("#cmborderno").empty();
            $("#cmbproduct").empty();
            $("#barcodeLabourList").empty();           
            LocationMastercallback("Grafted",function(res){
                var locationlist = '<option value="">-- Select Location --</option>'; // default option 
                if(res.status === "success"){
                  if(res.data.length > 0){
                    resnew=res.data;
                    for (var i = 0; i < resnew.length; i++) {
                      locationlist += '<option value="' + resnew[i].locationid + '">' + resnew[i].locationname + '</option>';
                    }
                  }
                  $("#barcodeLocationList").append(locationlist);

                  if(res.data.length==1){
                    $("#cmborderno").val(resnew[0].locationid).trigger("change");
                  }
                } 
            });
        }
    });

    $(document).on("change", "#barcodeLocationList", function () {
        $("#cmborderno").empty();
        $("#cmbproduct").empty();
        $("#barcodeLabourList").empty();
        let locationid =$(this).val();
        if(!locationid){
            return false;
        }
        var form_data = new FormData(); 
        var companyid = $("body").data("companyid");
        var subcompanyid = $("body").data("subcompanyid");
        var projectid = $("body").data("projectid");
        form_data.append("companyid", companyid);
        form_data.append("subcompanyid", subcompanyid);
        form_data.append("projectid", projectid); 
        form_data.append("locationid", locationid);
        $.ajax
            ({
                type: "POST",
                url: "modal/view.php?action=locationbasedorder",
                data: form_data,
                contentType: false,
                processData: false,
                cache: false,
                dataType: 'JSON',
                success: function (res) {
                    if(res.status === "success"){
                         var stlist = '<option value="">-- Select Stock Order No --</option>'; // default 
                      if(res.data.length > 0){
                        resnew=res.data;
                        for (var i = 0; i < resnew.length; i++) {
                          stlist += '<option value="' + resnew[i].stockorder_no + '">' + resnew[i].stockorder_no + '</option>';
                        }
                      }
                      $("#cmborderno").append(stlist);
                      if(res.data.length==1){
                        $("#cmborderno").val(resnew[0].stockorder_no).trigger("change");
                      }
                    } 
                     
                }
            });    
    });
    $(document).on("change", "#cmborderno", function () { 
        $("#cmbproduct").empty();
        $("#barcodeLabourList").empty();
        let stockorder =$(this).val();
        let locationid =$("#barcodeLocationList").val();
        if(!locationid){
            return false;
        }
        if(!stockorder){
            return false;
        }
        var form_data = new FormData(); 
        var companyid = $("body").data("companyid");
        var subcompanyid = $("body").data("subcompanyid");
        var projectid = $("body").data("projectid");
        form_data.append("companyid", companyid);
        form_data.append("subcompanyid", subcompanyid);
        form_data.append("projectid", projectid); 
        form_data.append("locationid", locationid);
        form_data.append("stockorder", stockorder);
        $.ajax
            ({
                type: "POST",
                url: "modal/view.php?action=location_product",
                data: form_data,
                contentType: false,
                processData: false,
                cache: false,
                dataType: 'JSON',
                success: function (res) {
                    if(res.status === "success"){
                         var stlist = '<option value="">-- Select productname --</option>'; // default 
                      if(res.data.length > 0){
                        resnew=res.data;
                        for (var i = 0; i < resnew.length; i++) {
                          stlist += '<option value="' + resnew[i].productid + '">' + resnew[i].productname + '</option>';
                        }
                      }
                      $("#cmbproduct").append(stlist);
                      if(res.data.length==1){
                        $("#cmbproduct").val(resnew[0].productid).trigger("change");
                      }
                    } 
                }
            });    
    });

    $(document).on("change", "#cmbproduct", function () { 
        $("#barcodeLabourList").empty();
        const locationid = $("#barcodeLocationList").val() || "";
        const orderno = $("#cmborderno").val() || "";
        const productid = $(this).val() || "";
        const projectid = parseInt($("body").data("projectid"), 10) || 0;
        if (!locationid) {
            alert("Select locationid")
            return;
        } if (!orderno) {
            alert("Select Order No")
            return;
        } if (!productid) {
            alert("Select product Name")
            return;
        }

        // Your existing location based order code continues here
        var form_data = new FormData();

        var companyid = $("body").data("companyid");
        var subcompanyid = $("body").data("subcompanyid");

        form_data.append("companyid", companyid);
        form_data.append("subcompanyid", subcompanyid);
        form_data.append("projectid", projectid);
        form_data.append("locationid", locationid);
        form_data.append("stockorder", orderno);
        form_data.append("productid", productid);

        $.ajax({
            type: "POST",
            url: "modal/view.php?action=location_graftedlabor",
            data: form_data,
            contentType: false,
            processData: false,
            dataType: "json",

            success: function (res) { 
                if(res.status === "success"){
                     var stlist = '<option value="">-- Select labour who performed the grafting --</option>'; // default 
                  if(res.data.length > 0){
                    resnew=res.data;
                    for (var i = 0; i < resnew.length; i++) {
                      stlist += '<option value="' + resnew[i].labourid + '">' + resnew[i].labourname + '</option>';
                    }
                  }
                  $("#barcodeLabourList").append(stlist);
                  if(res.data.length==1){
                    $("#barcodeLabourList").val(resnew[0].labourid).trigger("change");
                  }
                } 
            },

            error: function (xhr) {
                console.error(xhr.responseText);
            }
        });
    });

  
    $(document).on("click", "#btnGenerateGraftedBarcode", function () {
        const stockorder = $("#cmborderno").val();
        const productid  = $("#cmbproduct").val();
        const locationid = $("#barcodeLocationList").val();
        const laborid = $("#barcodeLabourList").val()||"";
        if (!locationid) {
            alert("Select location");
            return;
        }
        if (!stockorder) {
            alert("Select Order");
            return;
        }
        if (!productid) {
            alert("Select product name");
            return;
        }

        const form_data = new FormData();
        form_data.append("projectid",$("body").data("projectid"));
        form_data.append("locationid", locationid);
        form_data.append("stockorder", stockorder);
        form_data.append("productid", productid);
        form_data.append("laborid",laborid);
        $.ajax({
            type: "POST",
            url: "modal/qrcode_print_graftedtray.php",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: "text",
            success: function (tspl) {
                console.log(tspl);
                // Here send TSPL to QZ Tray / local print service

            },
            error: function (xhr) {
                console.error(xhr.responseText);

                alert("Unable to generate barcode.");

            }

        });

    });

    function loadGraftingModalMasters(defaultLocationId) {
        const projectid =$("body").data("projectid");
        $.ajax({
            type: "POST",
            url:"modal/view.php?action=getgraftingmodalmasters",
            data: {
                projectid: projectid,
                locationid: defaultLocationId
            },
            dataType: "json",
            success: function (res) {
                if (
                    !res ||
                    res.status !== "success"
                ) {

                    alert(
                        res && res.message
                            ? res.message
                            : "Unable to load grafting masters."
                    );

                    return;
                }


                /* =========================================
                   LOCATION
                ========================================= */

                let locationOptions =
                    `<option value="">
                        -- Select Location --
                     </option>`;


                (res.data.locations || [])
                    .forEach(function (item) {

                        const selected =
                            String(item.locationid) ===
                            String(defaultLocationId)
                                ? "selected"
                                : "";

                        locationOptions += `
                            <option
                                value="${item.locationid}"
                                ${selected}>
                                ${item.locationname}
                            </option>
                        `;
                    });


                $("#graftedlocationlist").html(locationOptions);
                /* ========= LABOUR ====== */
                fillGraftingLabourDropdown(res.data.labours || []);
                /* =============== TRAY======= */
                let trayOptions =
                    `<option value="">
                        -- Select Tray --
                     </option>`;
                (res.data.trays || [])
                    .forEach(function (item) {
                        trayOptions += `
                            <option
                                value="${item.tray_id}"
                                data-code="${item.tray_code || ''}"
                                data-name="${item.tray_name || ''}"
                                data-cavity="${item.cavity_count || 0}"
                                data-cavitysize="${item.cavity_size || ''}"
                                data-material="${item.material || ''}"
                                data-category="${item.category_name || ''}">
                                ${item.tray_name}
                                (${item.cavity_count} Cavity)
                            </option>
                        `;
                    });
                $("#graftedtraylist").html(trayOptions);
                $("#graftedTrayInfo").text("");
                $("#graftedtraylist").html(trayOptions);
                // Default to latest tray previously used for this product
                const productid = $("#askquestion_modal").data("productid") || "";

                if (productid) {
                    selectLatestGraftedTray(productid);
                }

            },

            error: function (xhr) {

                console.error(xhr.responseText);

                alert(
                    "Error while loading Grafting Assignment Details."
                );
            }

        });
    }

    function fillGraftingLabourDropdown(data) {
        let options =
            `<option value="">
                -- Select Labour --
             </option>`;


        if (
            Array.isArray(data) &&
            data.length > 0
        ) {

            data.forEach(function (item) {

                options += `
                    <option value="${item.EmployeeID}">
                        ${item.lbrname}
                    </option>
                `;
            });

        } else {

            options += `
                <option value="" disabled>
                    No skilled grafting labour found
                </option>
            `;
        }


        $("#graftinglabourlist")
            .html(options);
    }
    $(document).on(  "change",  "#graftedtraylist",   function () {
        calculateMaxGraftedTrayQty();
    });
    $(document).on("change", "#graftedlocationlist", function () {
        const locationid = $(this).val() || "";
        const projectid = parseInt($("body").data("projectid"), 10) || 0;

        // Reset labour dropdown first
        $("#graftinglabourlist").html(
            '<option value="">-- Select Labour --</option>'
        );

        if (!locationid) {
            return;
        }
        debugger

        $.ajax({
            type: "POST",
            url: "modal/view.php?action=getgraftinglabour",
            data: {
                projectid: projectid,
                locationid: locationid
            },
            dataType: "json",

            beforeSend: function () {
                $("#graftinglabourlist").html(
                    '<option value="">Loading...</option>'
                );
            },

            success: function (res) {

                if (!res || res.status !== "success") {

                    $("#graftinglabourlist").html(
                        '<option value="">-- Select Labour --</option>'
                    );

                    alert(
                        res && res.message
                            ? res.message
                            : "Unable to load grafting labour."
                    );

                    return;
                }

                fillGraftingLabourDropdown(
                    res.data || []
                );
            },

            error: function (xhr) {

                console.error(xhr.responseText);

                $("#graftinglabourlist").html(
                    '<option value="">-- Select Labour --</option>'
                );

                alert("Unable to load grafting labour.");
            }
        });
    });
    function fillGraftingLabourDropdown(data) {

        let options =
            '<option value="">-- Select Labour --</option>';

        if (
            Array.isArray(data) &&
            data.length > 0
        ) {

            data.forEach(function (item) {

                options +=
                    '<option value="' +
                    item.EmployeeID +
                    '">' +
                    item.lbrname +
                    '</option>';
            });

        } else {

            options +=
                '<option value="" disabled>' +
                'No skilled grafting labour found' +
                '</option>';
        }

        $("#graftinglabourlist").html(options);
    }
    function calculateMaxGraftedTrayQty() {
        debugger
        const $modal = $("#askquestion_modal");
        const totalRootQty =
            Number($modal.data("totalrootqty")) || 0;

        const totalScionQty =
            Number($modal.data("totalscionqty")) || 0;

        const cavityCount =
            Number(
                $("#graftedtraylist option:selected").data("cavity")
            ) || 0;


        console.log({
            totalRootQty: totalRootQty,
            totalScionQty: totalScionQty,
            cavityCount: cavityCount
        });


        // No tray selected
        if (cavityCount <= 0) {

            $("#lblmaxnewtrayqty").text("0");

            $("#newtrayqty")
                .val("")
                .attr("max", 0)
                .prop("disabled", true)
                .attr(
                    "placeholder",
                    "Select Grafted Tray Type first"
                );

            $modal.data("maxnewtrayqty", 0);

            $("#graftedTrayInfo").html("");

            return;
        }


        // Lower of Root/Scion PLANT quantity
        const availablePlantQty =
            Math.min(
                totalRootQty,
                totalScionQty
            );


        if (availablePlantQty <= 0) {

            $("#lblmaxnewtrayqty").text("0");

            $("#newtrayqty")
                .val("")
                .attr("max", 0)
                .prop("disabled", true)
                .attr(
                    "placeholder",
                    "No tray can be generated"
                );

            $modal.data("maxnewtrayqty", 0);

            $("#graftedTrayInfo").html(
                `Available Root Plants: <strong>${totalRootQty.toLocaleString("en-IN")}</strong>
                 &nbsp; | &nbsp;
                 Available Scion Plants: <strong>${totalScionQty.toLocaleString("en-IN")}</strong>`
            );

            return;
        }


        /*
         * Last Grafted tray may be partially filled
         *
         * Example:
         * 882 available plants
         * 209 cavity
         *
         * 882 / 209 = 4.22
         * CEIL = 5 trays
         */
        const maxTrayQty =
            Math.ceil(
                availablePlantQty / cavityCount
            );


        $("#lblmaxnewtrayqty").text(
            maxTrayQty.toLocaleString("en-IN")
        );


        $("#newtrayqty")
            .val("")
            .attr("min", 1)
            .attr("max", maxTrayQty)
            .attr("step", 1)
            .prop("disabled", false)
            .attr(
                "placeholder",
                "Enter qty up to " + maxTrayQty
            );


        $modal.data(
            "maxnewtrayqty",
            maxTrayQty
        );


        $("#graftedTrayInfo").html(
            `Available Root Plants:
             <strong>${totalRootQty.toLocaleString("en-IN")}</strong>
             &nbsp; | &nbsp;

             Available Scion Plants:
             <strong>${totalScionQty.toLocaleString("en-IN")}</strong>
             &nbsp; | &nbsp;

             Selected Cavity:
             <strong>${cavityCount.toLocaleString("en-IN")}</strong>
             &nbsp; | &nbsp;

             Max New Trays:
             <strong>${maxTrayQty.toLocaleString("en-IN")}</strong>`
        );
    }
    function selectLatestGraftedTray(productid) {
        const projectid = parseInt($("body").data("projectid"), 10) || 0;
        if (projectid <= 0 || !productid) {
            return;
        }
        $.ajax({
            type: "POST",
            url: "modal/view.php?action=getlatestgraftedtray",
            data: {
                projectid: projectid,
                productid: productid
            },
            dataType: "json",

            success: function (res) {

                if (
                    res &&
                    res.status === "success" &&
                    res.data &&
                    res.data.tray_id
                ) {
                    const trayId = String(res.data.tray_id);

                    // Make sure this tray exists in current dropdown
                    if (
                        $("#graftedtraylist option[value='" + trayId + "']").length
                    ) {
                        $("#graftedtraylist")
                            .val(trayId)
                            .trigger("change");
                    }
                }
            },

            error: function (xhr) {
                console.error(
                    "Unable to load latest Grafted tray:",
                    xhr.responseText
                );
            }
        });
    }

});
 
</script>
