<?php
require('../header.php');
?>

<style>
/* =========================================
   QR PRINTING MODULE - MAIN LAYOUT
========================================= */

.qrp,
.qrp *,
#qrpDetailModal,
#qrpDetailModal * {
    box-sizing: border-box;
}

.qrp {
    font-size: 13px;
    color: #26364c;
    min-width: 0;
    width: 100%;
}

.qrp .qrp-main {
    border: 1px solid #dce5f0;
    border-radius: 10px;
    overflow: hidden;
}

.qrp .qrp-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 8px;
    padding-bottom: 12px;
    border-bottom: 1px solid #e7edf5;
    margin-bottom: 14px;
}

.qrp .qrp-heading h4 {
    font-size: 19px;
    font-weight: 700;
    margin: 0;
}

.qrp .qrp-heading p {
    margin: 4px 0 0;
    font-size: 12px;
}

.qrp .qrp-section-title {
    font-size: 14px;
    font-weight: 700;
    margin: 0 0 12px;
    color: #234675;
}

/* =========================================
   FILTERS - MAIN PAGE AND MODAL
========================================= */

.qrp .qrp-filter,
#qrpDetailModal .qrp-filter {
    background: #f7f9fd;
    border: 1px solid #dde5f0;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 14px;
}

.qrp .qrp-filter .row > [class*="col-"],
#qrpDetailModal .qrp-filter .row > [class*="col-"] {
    min-width: 0;
}

.qrp .form-label,
#qrpDetailModal .form-label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 5px;
    white-space: normal;
}

.qrp .qrp-filter input,
.qrp .qrp-filter select,
#qrpDetailModal .qrp-filter input,
#qrpDetailModal .qrp-filter select {
    display: block;
    width: 100%;
    min-width: 0;
    max-width: 100%;
    height: 38px;
    padding: 6px 10px;
    font-size: 13px;
    border: 1px solid #ccd7e5;
    border-radius: 6px;
    background-color: #fff;
    color: #25364d;
}

.qrp .qrp-filter input:focus,
.qrp .qrp-filter select:focus,
#qrpDetailModal .qrp-filter input:focus,
#qrpDetailModal .qrp-filter select:focus {
    outline: 0;
    border-color: #4c83c7;
    box-shadow: 0 0 0 3px rgba(76,131,199,.12);
}

.qrp #qrpLocation {
    width: 100% !important;
    min-width: 0 !important;
    height: 38px !important;
    appearance: auto !important;
    padding-right: 8px !important;
}

/* =========================================
   BUTTONS
========================================= */

.qrp .qrp-actions,
#qrpDetailModal .qrp-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
}

.qrp .btn,
#qrpDetailModal .btn {
    font-size: 12px;
    white-space: normal;
    min-height: 33px;
}

/* =========================================
   TABLES
========================================= */

.qrp .qrp-tablewrap,
#qrpDetailModal .qrp-tablewrap {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    overflow-x: auto;
    border: 1px solid #e0e7f0;
    border-radius: 7px;
}

.qrp .table,
#qrpDetailModal .table {
    margin: 0;
    font-size: 12px;
}

.qrp .table th,
#qrpDetailModal .table th {
    background: #eaf2ff;
    color: #274468;
    white-space: nowrap;
    font-weight: 700;
    border-color: #dce5f0;
    padding: 9px 8px;
}

.qrp .table td,
#qrpDetailModal .table td {
    vertical-align: middle;
    border-color: #e4eaf2;
    padding: 8px;
    overflow-wrap: anywhere;
}

.qrp .table tbody tr:hover,
#qrpDetailModal .table tbody tr:hover {
    background: #f8fbff;
}

.qrp .qrp-print-status,
#qrpDetailModal .qrp-print-status {
    white-space: nowrap;
}

/* =========================================
   FULL SCREEN QR MODAL
========================================= */

#qrpDetailModal {
    position: fixed !important;
    inset: 0;
    width: 100%;
    height: 100%;
    padding: 0 !important;
    overflow: hidden !important;
    z-index: 1060;
    font-size: 13px;
    color: #26364c;
}

/* Prevent dashboard/container dimensions
   from restricting the modal. */

#qrpDetailModal .modal-dialog {
    position: relative;
    width: 100%;
    max-width: 100%;
    height: 100%;
    margin: 0 !important;
    padding: 0;
    transform: none;
}

#qrpDetailModal .modal-content {
    display: flex;
    flex-direction: column;
    width: 100%;
    height: 100%;
    max-height: 100%;
    min-height: 0;
    margin: 0;
    padding: 0;
    border: 0;
    border-radius: 0;
    overflow: hidden;
    background: #fff;
}

/* HEADER */

#qrpDetailModal .modal-header {
    display: flex;
    align-items: center;
    flex: 0 0 auto;
    padding: 12px 16px;
    margin: 0;
    background: #f3f7ff;
    border-bottom: 1px solid #dce5f0;
}

#qrpDetailModal .modal-title {
    font-size: 17px;
    font-weight: 700;
    color: #234675;
    margin: 0;
}

/* BODY - ONLY THIS AREA SCROLLS */

#qrpDetailModal .modal-body {
    position: relative;
    display: block;
    flex: 1 1 auto;
    min-height: 0;
    height: auto;
    padding: 14px 16px;
    margin: 0;
    overflow-x: hidden;
    overflow-y: auto;
    overscroll-behavior: contain;
}

#qrpDetailModal #qrpInfo {
    margin-bottom: 12px;
    overflow-wrap: anywhere;
}

/* MODAL TABLE */

#qrpDetailModal .qrp-tablewrap {
    max-height: none;
    overflow-x: auto;
    overflow-y: visible;
}

#qrpDetailModal .qrp-tablewrap thead {
    position: sticky;
    top: 0;
    z-index: 2;
}

/* FOOTER - NO EXTRA HEIGHT BELOW BUTTONS */

#qrpDetailModal .modal-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex: 0 0 auto;
    width: 100%;
    min-height: 55px;
    height: auto;
    margin: 0 !important;
    padding: 9px 16px;
    gap: 8px;
    flex-wrap: wrap;
    background: #fff;
    border-top: 1px solid #dce5f0;
    box-shadow: 0 -2px 8px rgba(0,0,0,.035);
}

#qrpDetailModal .modal-footer > * {
    margin: 0;
}

#qrpDetailModal .modal-footer > span.small {
    font-size: 11px;
    color: #64748b;
}

#qrpDetailModal .modal-footer .btn {
    flex-shrink: 0;
}

/* =========================================
   MOBILE RESPONSIVE
========================================= */

@media (max-width: 767px) {

    .qrp {
        font-size: 12px;
    }

    .qrp .card-body {
        padding: 11px;
    }

    .qrp .qrp-heading h4 {
        font-size: 17px;
    }

    .qrp .qrp-filter,
    #qrpDetailModal .qrp-filter {
        padding: 10px;
    }

    .qrp .qrp-actions .btn {
        flex: 1 1 auto;
    }

    /* Responsive table cards */

    .qrp .qrp-tablewrap,
    #qrpDetailModal .qrp-tablewrap {
        overflow: visible;
        max-height: none;
        border: 0;
        border-radius: 0;
    }

    .qrp .qrp-responsive,
    .qrp .qrp-responsive tbody,
    .qrp .qrp-responsive tr,
    .qrp .qrp-responsive td,
    #qrpDetailModal .qrp-responsive,
    #qrpDetailModal .qrp-responsive tbody,
    #qrpDetailModal .qrp-responsive tr,
    #qrpDetailModal .qrp-responsive td {
        display: block;
        width: 100%;
    }

    .qrp .qrp-responsive thead,
    #qrpDetailModal .qrp-responsive thead {
        display: none;
    }

    .qrp .qrp-responsive tr,
    #qrpDetailModal .qrp-responsive tr {
        border: 1px solid #dce3ed;
        border-radius: 8px;
        margin-bottom: 10px;
        padding: 5px 8px;
        background: #fff;
        box-shadow: 0 1px 3px rgba(0,0,0,.035);
    }

    .qrp .qrp-responsive td,
    #qrpDetailModal .qrp-responsive td {
        border: 0;
        border-bottom: 1px solid #edf0f5;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        text-align: right !important;
        padding: 8px 3px;
        min-height: 34px;
    }

    .qrp .qrp-responsive td::before,
    #qrpDetailModal .qrp-responsive td::before {
        content: attr(data-label);
        font-weight: 700;
        text-align: left;
        flex: 0 0 42%;
        color: #45546c;
    }

    .qrp .qrp-responsive td:last-child,
    #qrpDetailModal .qrp-responsive td:last-child {
        border-bottom: 0;
    }

    .qrp .qrp-responsive td[colspan],
    #qrpDetailModal .qrp-responsive td[colspan] {
        display: block;
        text-align: center !important;
        padding: 15px;
    }

    .qrp .qrp-responsive td[colspan]::before,
    #qrpDetailModal .qrp-responsive td[colspan]::before {
        content: none;
    }

    /* Mobile modal */

    #qrpDetailModal .modal-header {
        padding: 10px 12px;
    }

    #qrpDetailModal .modal-title {
        font-size: 15px;
    }

    #qrpDetailModal .modal-body {
        padding: 10px;
    }

    #qrpDetailModal .modal-footer {
        padding: 9px 12px;
        gap: 8px;
        justify-content: space-between;
    }

    #qrpDetailModal .modal-footer > span.small {
        flex: 1 1 100%;
        order: 3;
        font-size: 11px;
    }

    #qrpDetailModal .modal-footer > .qrp-actions {
        display: flex;
        width: 100%;
        order: 2;
    }

    #qrpDetailModal .modal-footer > .qrp-actions .btn {
        flex: 1 1 110px;
    }
}

@media (max-width: 380px) {

    .qrp .qrp-responsive td::before,
    #qrpDetailModal .qrp-responsive td::before {
        flex-basis: 39%;
    }

    .qrp .qrp-filter .col-6,
    #qrpDetailModal .qrp-filter .col-6 {
        width: 100%;
    }
}
.qrp .select2-container,#qrpDetailModal .select2-container{width:100% !important;min-width:0}
.qrp .select2-selection--single,#qrpDetailModal .select2-selection--single{height:38px;border:1px solid #ccd7e5;border-radius:6px}
.qrp .select2-selection__rendered,#qrpDetailModal .select2-selection__rendered{line-height:36px !important;font-size:13px}
.qrp .select2-selection__arrow,#qrpDetailModal .select2-selection__arrow{height:36px !important}
#qrpDetailModal .select2-container--open{z-index:1070}
/* Keep native selects hidden once Select2 owns their visible control.
   The filter display:block rule must not expose the original select. */
.qrp .qrp-filter select.select2-hidden-accessible,
#qrpDetailModal .qrp-filter select.select2-hidden-accessible {
    display: none !important;
}
.qrp .qrp-filter select.qrp-native-select,
#qrpDetailModal .qrp-filter select.qrp-native-select {
    display:none !important;
    height:0 !important;
    width:0 !important;
    min-height:0 !important;
    padding:0 !important;
    border:0 !important;
}
#qrpDetails .qrp-select-all{display:inline-flex;align-items:center;gap:6px;margin:0;cursor:pointer;white-space:nowrap}
#qrpDetails #qrpAll{position:static;margin:0;width:16px;height:16px;flex:none}
@media(max-width:767px){
 #qrpDetailModal #qrpDetails thead{display:block;position:static}
 #qrpDetailModal #qrpDetails thead tr{display:block;margin-bottom:8px;padding:0;border:0;box-shadow:none}
 #qrpDetailModal #qrpDetails thead th{display:none}
 #qrpDetailModal #qrpDetails thead th:first-child{display:block;border:0;border-radius:6px}
}
#qrpDetailModal #qrpDetails th:first-child{width:125px;min-width:125px;padding:10px 12px;vertical-align:middle}
#qrpDetailModal #qrpDetails .qrp-select-all{display:flex !important;align-items:center;gap:9px !important;position:static !important;margin:0 !important;padding:0 !important;line-height:20px;white-space:nowrap}
#qrpDetailModal #qrpDetails .qrp-select-all::before,
#qrpDetailModal #qrpDetails .qrp-select-all::after{content:none !important;display:none !important}
#qrpDetailModal #qrpDetails #qrpAll,
#qrpDetailModal #qrpDetails .qrpCheck{appearance:auto !important;position:static !important;display:inline-block !important;transform:none !important;zoom:1 !important;float:none !important;width:18px !important;height:18px !important;min-width:18px !important;min-height:18px !important;margin:0 !important;padding:0 !important;flex:0 0 18px !important;vertical-align:middle;opacity:1 !important;accent-color:#0d6efd}
@media(min-width:768px){#qrpDetailModal #qrpDetails td:first-child{text-align:center}}
@media(max-width:767px){#qrpDetailModal #qrpDetails th:first-child{width:100%;min-width:0}}
</style>

<!-- ==========================================
     REQUESTED QR CODE PRINTING - MAIN PAGE
========================================== -->

<div class="container-fluid qrp py-2">

    <div class="card shadow-sm qrp-main">
        <div class="card-body">

            <!-- PAGE HEADING -->
            <div class="qrp-heading">
                <div>
                    <h4>Requested QR Code Printing</h4>
                    <p class="text-muted d-none">
                        Review QR requests and print individual
                        or selected tray labels.
                    </p>
                </div>

                <span class="badge bg-primary d-none">
                    QR Request Register
                </span>
            </div>

            <!-- SEARCH FILTERS -->
            <div class="qrp-filter mb-1">

                <h6 class="qrp-section-title">
                    1. Search Requested QR Codes
                </h6>

                <div class="row g-2 align-items-end">

                    <div class="col-12 col-md-3">
                        <label class="form-label" for="qrpLocation">
                            Production Location
                        </label>

                        <select id="qrpLocation" class="form-select select2">
                            <option value="">All Locations</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label" for="qrpClient">
                            Client Name
                        </label>

                        <select id="qrpClient" class="form-select select2">
                            <option value="">All Clients</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label" for="qrpStock">
                            Stock Order No.
                        </label>

                        <select id="qrpStock" class="form-select">
                            <option value="">All Stock Orders</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">

                        <button
                            type="button"
                            id="qrpReset"
                            class="btn btn-outline-secondary btn-sm">
                            Reset Filters
                        </button>
                    </div>    
                     

                    <!-- FILTER ACTIONS -->
                    <div class="col-12 qrp-actions d-none">

                        <button
                            type="button"
                            id="qrpSearch"
                            class="btn btn-primary btn-sm">
                            Search Requests
                        </button>


                    </div>
                </div>
            </div>

            <!-- SUMMARY TABLE -->
            <!-- <section aria-labelledby="qrpSummaryTitle"> -->
                <h6 id="qrpSummaryTitle" class="qrp-section-title">
                    2. Requested QR Code Summary
                </h6>
                <div class="qrp-tablewrap">
                    <table  id="qrpSummary" class="table table-bordered table-sm qrp-responsive">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Location Name</th>
                                <th scope="col">Client Name</th>
                                <th scope="col">Stock Order No.</th>
                                <th scope="col">Sowing Date</th>
                                <th scope="col">Requested Date</th>
                                <th scope="col">Requested Tray Qty</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td colspan="8" class="text-center py-3">
                                    Loading requested QR codes...
                                </td>
                            </tr>
                        </tbody>

                    </table>
                </div>
            <!-- </section> -->

        </div>
    </div>

</div>
<!-- END MAIN PAGE -->


<!-- ==========================================
     QR PRINTING DETAILS - FULL SCREEN MODAL
     IMPORTANT: OUTSIDE .qrp CONTAINER
========================================== -->

<div
    class="modal fade qrp-modal"
    id="qrpDetailModal"
    tabindex="-1"
    data-bs-backdrop="static"
    data-bs-keyboard="false"
    aria-labelledby="qrpDetailTitle"
    aria-hidden="true">

    <div class="modal-dialog">

        <div class="modal-content">

            <!-- MODAL HEADER -->
            <div class="modal-header">

                <h5 class="modal-title" id="qrpDetailTitle">
                    QR Code Printing — Tray Details
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close">
                </button>

            </div>

            <!-- MODAL BODY -->
            <div class="modal-body">

                <!-- REQUEST INFORMATION -->
                <div
                    id="qrpInfo"
                    class="alert alert-light border py-2">
                </div>

                <!-- DETAIL FILTERS -->
                <div class="qrp-filter mb-2"> 

                    <div class="row g-2 align-items-end">

                        <div class="col-12 col-md-4">

                            <label
                                for="qrpBarcode"
                                class="form-label">
                                Barcode / Product Name
                            </label>

                            <input
                                type="text"
                                id="qrpBarcode"
                                class="form-control"
                                placeholder="Enter barcode or product">

                        </div>

                        <div class="col-6 col-md-3">

                            <label
                                for="qrpPoly"
                                class="form-label">
                                Polyhouse Name
                            </label>

                            <select
                                id="qrpPoly"
                                class="form-select">

                                <option value="">
                                    All Polyhouses
                                </option>

                            </select>

                        </div>

                        <div class="col-6 col-md-2">

                            <label
                                for="qrpRack"
                                class="form-label">
                                Rack Name
                            </label>

                            <select
                                id="qrpRack"
                                class="form-select">

                                <option value="">
                                    All Racks
                                </option>

                            </select>

                        </div>

                        <div class="col-6 col-md-2 d-none">

                            <label
                                for="qrpBed"
                                class="form-label">
                                Bed No.
                            </label>

                            <input
                                type="text"
                                id="qrpBed"
                                class="form-control"
                                placeholder="Enter bed no.">

                        </div>

                        <div class="col-6 col-md-2">

                            <div class="qrp-actions">

                                <button
                                    type="button"
                                    id="qrpDetailSearch"
                                    class="btn btn-primary btn-sm d-none">
                                    Search
                                </button>

                                <button
                                    type="button"
                                    id="qrpDetailReset"
                                    class="btn btn-outline-secondary btn-sm">
                                    Reset
                                </button>

                            </div>

                        </div>

                    </div>
                </div>

                <!-- SELECTION BAR -->
                <div
                    class="d-flex justify-content-between
                           align-items-center gap-2 flex-wrap mb-2 d-none">


                    <span id="qrpVisible" class="d-none">
                        0 barcodes displayed
                    </span>

                </div>

                <!-- BARCODE DETAILS TABLE -->
                <div class="qrp-tablewrap">

                    <table
                        id="qrpDetails"
                        class="table table-bordered table-sm qrp-responsive">

                        <thead>
                            <tr>
                                <th scope="col"><label class="qrp-select-all" for="qrpAll"><input type="checkbox" id="qrpAll"><span>Select All</span></label></th>
                                <th scope="col">Barcode No.</th>
                                <th scope="col">Product Name</th>
                                <th scope="col">Polyhouse Name</th>
                                <th scope="col">Rack Name</th>
                                <th scope="col">Bed No.</th>
                                <th scope="col">Tray Cavity</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            <!-- Populated by existing JavaScript -->
                        </tbody>

                    </table>
                </div>

            </div>
            <!-- END MODAL BODY -->


            <!-- FIXED MODAL FOOTER -->
            <div class="modal-footer">

                <span id="qrpSelected">
                    Selected: 0
                </span>

                <span class="small text-muted d-none">
                    Printing is recorded only after you confirm
                    that the labels were physically printed.
                </span>

                <div class="qrp-actions">

                    <button
                        type="button"
                        id="qrpPrintSelected"
                        class="btn btn-success btn-sm">
                        Print Selected / All
                    </button>

                     

                    <button
                        type="button"
                        class="btn btn-secondary btn-sm"
                        data-bs-dismiss="modal">
                        Close
                    </button>

                </div>

            </div>
            <!-- END MODAL FOOTER -->

        </div>
    </div>
</div>
<!-- END QR MODAL -->

<?php if(!isset($_GET['dz'])) include('../footer.php'); ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/qz-tray.js"></script>

<script>
(function($){'use strict';
 const api='modal/qr_request_print_api.php',esc=v=>$('<div>').text(v??'').html().replace(/"/g,'&quot;').replace(/'/g,'&#39;');
 let summary=[],details=[],visible=[],active=null,locationNames={},lastPrintBarcodes=[];
  let summarySeq=0, detailSeq=0, printBusy=false;
 function project(){return String($('#hidden_projectid').val()||'');}
 function notify(msg){
    alertmessage('warning',msg);
}
 function syncSelect(sel) {
   if (typeof $.fn.select2 !== 'function') return;
   $(sel).each(function(){
     const $sel=$(this);
     if (!$sel.data('qrpSelectReady')) {
       if ($sel.data('select2')) $sel.select2('destroy');
       // Remove stale generated controls before initializing this filter once.
       $sel.siblings('span.select2-container').remove();
       $sel.select2({
         width:'100%',ajax:null,multiple:false,tags:false,
         dataAdapter:null,selectionAdapter:null,dropdownAdapter:null,resultsAdapter:null,
         dropdownParent:$sel.closest('#qrpDetailModal').length ? $('#qrpDetailModal') : $(document.body),
         templateResult:function(item){return item.text;},
         templateSelection:function(item){return item.text;}
       });
       $sel.data('qrpSelectReady',true).addClass('qrp-native-select');
     }
     $sel.trigger('change.select2');
   });
 }
 function initSelects(){syncSelect('#qrpLocation,#qrpClient,#qrpStock,#qrpPoly,#qrpRack');}
 $(initSelects);
 $('#qrpDetailModal').on('shown.bs.modal',function(){syncSelect('#qrpPoly,#qrpRack');});
 function populate(sel,values,label){$(sel).html('<option value="">All '+label+'</option>');[...new Set(values.filter(Boolean))].sort((a,b)=>String(a).localeCompare(String(b))).forEach(v=>$(sel).append($('<option>').val(v).text(v)));syncSelect(sel);}
 let optionsSeq=0;
 function selectedFilters(){return {
   projectid:project(),locationid:$('#qrpLocation').val()||'',
   client:$('#qrpClient').val()||'',stock:$('#qrpStock').val()||'' 
 };}
 function refreshOptions(){
   const seq=++optionsSeq, filters=selectedFilters();
   $.post(api,{action:'filter_options',...filters},null,'json').done(r=>{
     if(seq!==optionsSeq)return;
     if(!r.status){notify(r.message||'Unable to load filters');return;}
     [['#qrpClient',r.data.clients||[],'All Clients'],['#qrpStock',r.data.stocks||[],'All Stock Orders']]
       .forEach(([id,items,label])=>{
         const sel=$(id), old=sel.val()||'';
         sel.empty().append($('<option>').val('').text(label));
         items.forEach(v=>{const value=v && typeof v==='object' ? (v.value ?? v.stockorder_no ?? v.client_name ?? v.id) : v;if(value==null)return;const text=typeof v==='object' ? (v.text ?? v.label ?? v.stockorder_no ?? v.client_name ?? v.value ?? v.id) : v;sel.append($('<option>').val(value).text(text));});
         // Preserve the chosen value while the other dropdown is narrowed.
         if(old && !items.some(v=>String(v && typeof v==='object' ? (v.value ?? v.stockorder_no ?? v.client_name ?? v.id) : v)===String(old))){
           sel.append($('<option>').val(old).text(old));
         }
         sel.val(old);syncSelect(sel);
       });
   }).fail(x=>{if(seq!==optionsSeq)return;console.error(x.responseText);notify('Unable to load filter options');});
 }
 function initFilters(){
   if(!project()){notify('Project ID is missing');return;}
   $.post(api,{action:'locations',projectid:project()},null,'json').done(r=>{
     if(!r.status){notify(r.message||'Unable to load requested locations');return;}
     locationNames={};const sel=$('#qrpLocation').empty().append($('<option>').val('').text('All Requested Locations'));
     (r.data||[]).forEach(v=>{locationNames[String(v.locationid)]=v.location_name;sel.append($('<option>').val(v.locationid).text(v.location_name));});
     syncSelect(sel);refreshOptions();load();
   }).fail(x=>{console.error(x.responseText);notify('Unable to load requested locations');refreshOptions();load();});
 }
 initFilters();
 function load(){const seq=++summarySeq;if(!project()){notify('Project ID is missing');return;}if($('#qrpFrom').val()&&$('#qrpTo').val()&&$('#qrpFrom').val()>$('#qrpTo').val()){notify('Sowing Date From cannot be after To');return;}
 $('#qrpSummary tbody').html('<tr><td colspan="8">Loading requests...</td></tr>');
 $.post(api,{action:'summary',projectid:project(),locationid:$('#qrpLocation').val(),client:String($('#qrpClient').val()||'').trim(),stock:String($('#qrpStock').val()||'').trim(),date_from:$('#qrpFrom').val(),date_to:$('#qrpTo').val()},null,'json').done(r=>{if(seq!==summarySeq)return;if(!r.status){$('#qrpSummary tbody').html('<tr><td colspan="8" class="text-danger">'+esc(r.message||'Unable to load')+'</td></tr>');notify(r.message||'Unable to load');return;}summary=r.data||[];$('#qrpSummary tbody').html(summary.length?summary.map((v,i)=>`<tr><td data-label="#">${i+1}</td><td data-label="Location Name">${esc(v.location_name||locationNames[String(v.locationid)]||v.locationid)}</td><td data-label="Client Name">${esc(v.client_name)}</td><td data-label="Stock Order No.">${esc(v.stockorder_no)}</td><td data-label="Sowing Date">${esc(v.sowing_display)}</td><td data-label="Requested Date">${esc(v.requested_display)}</td><td data-label="Requested Tray Qty"><b>${Number(v.requested_qty)||0}</b></td><td data-label="Action"><button class="btn btn-primary btn-sm qrpOpen" data-index="${i}">Print QR Code</button></td></tr>`).join(''):'<tr><td colspan="8" class="text-center">No requested QR codes found.</td></tr>');}).fail(x=>{if(seq!==summarySeq)return;console.error(x.responseText);$('#qrpSummary tbody').html('<tr><td colspan="8" class="text-danger">Unable to load requests.</td></tr>');});}
 $('#qrpSearch').on('click',()=>{refreshOptions();load();});
 $('#qrpLocation,#qrpClient,#qrpStock,#qrpFrom,#qrpTo').on('change',()=>{refreshOptions();load();});
 $('#qrpReset').on('click',()=>{$('#qrpLocation,#qrpClient,#qrpStock,#qrpFrom,#qrpTo').val('');$('#qrpLocation,#qrpClient,#qrpStock').each(function(){syncSelect(this);});refreshOptions();load();});

 $(document).on('click', '.qrpOpen', async function () {

  const selected = summary[Number(this.dataset.index)];
  if (!selected) return;

  active = selected;
  const seq = ++detailSeq;

  details = [];
  visible = [];
  lastPrintBarcodes = [];

  const locationName =
    selected.location_name ||
    locationNames[String(selected.locationid)] ||
    selected.locationid;

  $('#qrpInfo').text(
    `Location: ${locationName} | ` +
    `Client: ${selected.client_name || '—'} | ` +
    `Stock Order: ${selected.stockorder_no} | ` +
    `Sowing: ${selected.sowing_display} | ` +
    `Requested: ${selected.requested_display}`
  );

  $('#qrpBarcode,#qrpBed,#qrpPoly,#qrpRack').val('');
  $('#qrpPoly,#qrpRack').each(function(){syncSelect(this);});
  $('#qrpConfirmPrinted').prop('disabled', true);
  $('#qrpDetails tbody').html(
    '<tr><td colspan="8">Loading barcode details...</td></tr>'
  );

  bootstrap.Modal
    .getOrCreateInstance(document.getElementById('qrpDetailModal'))
    .show();

  try {
    const response = await $.ajax({
      url: api,
      type: 'POST',
      dataType: 'json',
      data: {
        action: 'details',
        projectid: project(),
        locationid: selected.locationid,
        stockorder_no: selected.stockorder_no,
        sowinglotno_internal: selected.sowinglotno_internal,
        sowingdate_internal: selected.sowingdate_internal,
        requested_date: selected.requested_date
      }
    });

    // Ignore a response if another row was opened while this request ran.
    if (seq !== detailSeq) return; 
    if (!response.status) {
      const message = response.message || 'Unable to load details';
      $('#qrpDetails tbody').html(
        `<tr><td colspan="8" class="text-danger">${esc(message)}</td></tr>`
      );
      notify(message);
      return;
    }

    details = (response.data || []).map((row, index) => ({
      ...row,
      _id: index,
      _checked: false
    }));

    populate('#qrpPoly', details.map(row => row.polyhousename), 'Polyhouses');
    populate('#qrpRack', details.map(row => row.bedrackname), 'Racks');
    filterDetails();
  } catch (error) {
    if (seq !== detailSeq) return;

    console.error(error.responseText || error);
    $('#qrpDetails tbody').html(
      '<tr><td colspan="8" class="text-danger">Unable to load barcode details.</td></tr>'
    );
    notify('Unable to load barcode details');
  }
});
 function count(){let n=details.filter(d=>d._checked).length;$('#qrpSelected').text('Selected: '+n);$('#qrpAll').prop('checked',visible.length>0&&visible.every(d=>d._checked));}
  function filterDetails() {
  const term = $('#qrpBarcode').val().trim().toLowerCase();
  const poly = $('#qrpPoly').val();
  const rack = $('#qrpRack').val();
  const bed = $('#qrpBed').val().trim().toLowerCase();

  visible = details.filter(d => {
    if (poly && d.polyhousename !== poly) return false;
    if (rack && d.bedrackname !== rack) return false;
    if (bed && !String(d.bed_no ?? '').toLowerCase().includes(bed)) return false;

    return !term ||
      String(d.barcode ?? '').toLowerCase().includes(term) ||
      String(d.productname ?? '').toLowerCase().includes(term);
  });

  $('#qrpVisible').text(
    `${visible.length} of ${details.length} barcode(s) displayed`
  );
 
  const html = visible.length
    ? visible.map(d => `
       <tr
            data-lotno="${esc(d.lotno)}"
            data-seedname="${esc(d.seedname)}"
            data-countlotno="${esc(d.countlotno)}"
            data-qtypertray="${esc(d.qtypertray)}"
            data-dispatchdate_internal="${esc(d.dispatchdate_internal)}">
          <td data-label="Select">
            <input
              class="form-check-input qrpCheck"
              type="checkbox"
              data-id="${d._id}"
              ${d._checked ? 'checked' : ''}
              aria-label="Select barcode ${esc(d.barcode)}">
          </td>
          <td data-label="Barcode No.">${esc(d.barcode)}</td>
          <td data-label="Product Name">${esc(d.productname)}</td>
          <td data-label="Polyhouse Name">${esc(d.polyhousename)}</td>
          <td data-label="Rack Name">${esc(d.bedrackname)}</td>
          <td data-label="Bed No.">${esc(d.bed_no)}</td>
          <td data-label="Plants / Tray">${esc(d.qtypertray)}</td>
          <td data-label="Action">
            <button class="btn btn-outline-primary btn-sm qrpPrintOne"
                    data-id="${d._id}">Print QR Code</button>
          </td>
        </tr>
      `).join('')
    : '<tr><td colspan="8" class="text-center">No matching barcodes.</td></tr>';

  $('#qrpDetails tbody').html(html);
  count();
}
 $('#qrpPoly,#qrpRack').on('change',filterDetails);
 $('#qrpDetailSearch').on('click',filterDetails);$('#qrpDetailReset').on('click',()=>{$('#qrpBarcode,#qrpPoly,#qrpRack,#qrpBed').val('');$('#qrpPoly,#qrpRack').each(function(){syncSelect(this);});filterDetails();});$('#qrpBarcode,#qrpBed').on('keydown',e=>{if(e.key==='Enter'){e.preventDefault();filterDetails();}});
 $(document).on('change','.qrpCheck',function(){details[Number($(this).attr('data-id'))]._checked=this.checked;count();});$('#qrpAll').on('change',function(){visible.forEach(d=>d._checked=this.checked);filterDetails();});
 // Direct TSPL printing requires an installed/local printer bridge.
 // Supply window.sendTSPLToPrinter(tspl), returning a Promise that resolves
 // only when the printer bridge accepts the job. No preview or file download.
  



 async function printLabels(rows) {

  if (!rows?.length) {
    notify('Select at least one barcode');
    return;
  }
  if (rows.length > 500) {
    notify('Print in batches of up to 500 labels');
    return;
  }
  

  if (printBusy) {notify('A print job is already being submitted.');return;}
  if (!active) {notify('Open a QR request first.');return;}
  const first = rows[0];
  const labelFields = [
    'lotno',
    'seedname',
    'countlotno',
    'qtypertray',
    'dispatchdate_internal'
  ];

  const mixed = rows.some(row =>
    labelFields.some(field => String(row[field] ?? '') !== String(first[field] ?? ''))
  );

  if (mixed) {
    notify('Select barcodes with the same lot, seed, tray details, and dispatch date.');
    return;
  }
  const barcodes = rows.map(row => String(row.barcode));
  const $printButtons = $('#qrpPrintSelected,.qrpPrintOne');

  printBusy = true;
  lastPrintBarcodes = [];
  $('#qrpConfirmPrinted').prop('disabled', true);
  $printButtons.prop('disabled', true);

  try {
    const tspl = await $.ajax({
        url: 'qrcode_print_sowingtray.php',
        type: 'POST',
        dataType: 'text',
        data: {
          projectid: project(),
          productionorderno: active.stockorder_no,
          seedname: first.seedname,
          lotno: first.lotno,
          traycountinlot: first.countlotno,
          sowindateinternal: active.sowingdate_internal,
          dispatchdateinternal: first.dispatchdate_internal,
          traycavity: first.qtypertray,
          grcodelist: JSON.stringify(
            rows.map(row => ({ qrcode: String(row.barcode) }))
          )
        }
    });

    if (!tspl?.trim()) {
      throw new Error('Server returned empty TSPL commands.');
    }

   // await window.sendTSPLToPrinter(tspl);
            await printLabel_new(tspl);


    lastPrintBarcodes = barcodes;
    $('#qrpConfirmPrinted').prop('disabled', false);
   // notify('Print job submitted. Confirm only after labels have physically printed.');
  } catch (error) {
    console.error('Label print failed:', error.responseText || error);
    notify(
      error.status
        ? 'Unable to generate TSPL print commands.'
        : 'Printer rejected the job. No print status was changed.'
    );
  } finally {
    printBusy = false;
    $printButtons.prop('disabled', false);
  }
}

async function printLabel_new(tsplData) {
    if (typeof qz === 'undefined') {
        Swal.fire({
            icon: 'error',
            title: 'Print Library Missing',
            text: 'The QZ Tray library was not loaded properly. Please refresh the page or check your internet connection.'
        });
        throw new Error('QZ Tray library is not loaded.');
    }

    try {
        // Robust connection check using helper
        await ensureConnected();

        // Get all printers to find the best match (TSC or LP 46 Neo)
        let printer = null;
        const printerSearchTerms = ["TSC", "LP 46", "LP46", "LP", "Neo", "Thermal"];
        
        for (const term of printerSearchTerms) {
            try {
                let found = await qz.printers.find(term);
                if (Array.isArray(found) && found.length > 0) {
                    printer = found[0];
                    console.log(`Found printer matching '${term}':`, printer);
                    break;
                }
            } catch (err) {
                console.warn(`Error searching for printer term '${term}':`, err);
            }
        }

        if (!printer) {
            printer = await qz.printers.getDefault();
            console.log("No specific printer found matching terms, using default:", printer);
        }

        if (!printer) {
            throw new Error("No printer found. Please check your printer connection and QZ Tray.");
        }

        let config = qz.configs.create(printer, {
            encoding: 'ISO-8859-1',
            forceRaw: true
        });

        let data = [{
            type: 'raw',
            format: 'command',
            data: tsplData
        }];

        await qz.print(config, data);
        console.log("Print job sent successfully to " + printer);

        // Optionally show a small toast instead of alert for better UX
        iziToast.success({
            title: 'Success',
            message: 'Print job sent to ' + printer,
            position: 'topRight'
        });

    } catch (e) {
        console.error("Printing failed", e);
        
        if (e.message && e.message.includes("Unable to establish connection with QZ Tray")) {
            let isHttps = window.location.protocol === 'https:';
            let secureInstruction = '';
            if (isHttps) {
                secureInstruction = `
                    <div style="text-align: left; margin-top: 15px; font-size: 13px; background: #fff8f8; border-left: 4px solid #e53e3e; padding: 10px; border-radius: 4px; color: #2d3748;">
                        <strong style="color: #c53030;">🔒 HTTPS Secure Warning Bypass:</strong><br/>
                        Your browser might be blocking the connection because of local security certificates. To fix this:
                        <ul style="padding-left: 15px; margin-top: 5px; margin-bottom: 5px; line-height: 1.4;">
                            <li>Click <a href="https://localhost:8181" target="_blank" style="color: #3182ce; text-decoration: underline; font-weight: bold;">Link 1 (localhost)</a> or <a href="https://localhost.qz.io:8181" target="_blank" style="color: #3182ce; text-decoration: underline; font-weight: bold;">Link 2 (qz.io)</a>.</li>
                            <li>If you see a "Your connection is not private" warning, click <strong>"Advanced"</strong> and then click <strong>"Proceed to localhost"</strong>.</li>
                            <li>After bypassing, return to this tab and try printing again!</li>
                        </ul>
                    </div>
                `;
            }

            Swal.fire({
                icon: 'error',
                title: 'QZ Tray is not connected',
                html: `
                    <div style="text-align: left; font-size: 14px; line-height: 1.5; color: #2d3748;">
                        <p>We could not establish a connection to QZ Tray. Please ensure that:</p>
                        <ol style="padding-left: 20px; margin-bottom: 10px;">
                            <li>The <strong>QZ Tray Desktop App</strong> is installed and running on your computer (look for the green printer icon in your Windows system tray near the clock).</li>
                            <li>If it is not installed, download it from <a href="https://qz.io/download/" target="_blank" style="color: #3182ce; text-decoration: underline; font-weight: bold;">qz.io/download</a> and install it.</li>
                        </ol>
                        ${secureInstruction}
                    </div>
                `,
                confirmButtonText: 'I have started QZ Tray - Retry Printing',
                footer: '<a href="https://qz.io/wiki/faq" target="_blank" style="color: #3182ce; text-decoration: underline;">Need more help troubleshooting?</a>'
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Printing Failed',
                text: e.message || "Printer is not accepting the job. Please check if it's online and ready.",
                footer: 'Check QZ Tray status and printer queue.'
            });
        }
        throw e; // Rethrow to let calling buttons handle the final loading state
    }
    // Removed disconnect() to prevent socket closing before job handover
}

$(document).on('click', '.qrpPrintOne', function () {
  const row = details[Number(this.dataset.id)];
  if (row) printLabels([row]);
});

$('#qrpPrintSelected').on('click', function () {
  printLabels(details.filter(row => row._checked));
});
  


async function ensureConnected() {
    // Setup secure signing if available
    if (typeof qz !== 'undefined' && !qz.security.certHandler) {
        const routurl = $("body").data("routurl") || "";
        qz.security.setCertificatePromise(function(resolve, reject) {
            $.ajax({ 
                url: routurl + "krf-orderandsales/qz_certificate.txt", 
                cache: false, 
                dataType: "text" 
            }).then(resolve, reject);
        });

        qz.security.setSignaturePromise(function(toSign) {
            return function(resolve, reject) {
                $.post(routurl + "krf-orderandsales/qz_signer.php", { request: toSign })
                 .then(resolve, reject);
            };
        });
    }

    let connectOpts = {
        usingSecure: window.location.protocol === 'https:',
        port: {
            secure: [8181],
            insecure: [8182]
        }
    };

    if (!qz.websocket.isActive()) {
        try {
            await qz.websocket.connect(connectOpts);
        } catch (err) {
            throw new Error("Unable to establish connection with QZ Tray. Please ensure QZ Tray is running on your computer.");
        }
        return;
    }

    try {
        qz.websocket.getConnectionInfo();
    } catch (e) {
        let retries = 10;
        let success = false;
        while (retries > 0) {
            await new Promise(resolve => setTimeout(resolve, 500));
            try {
                if (!qz.websocket.isActive()) {
                    await qz.websocket.connect(connectOpts);
                    success = true;
                    break;
                }
                qz.websocket.getConnectionInfo();
                success = true;
                break;
            } catch (retryErr) {
                // Keep waiting
            }
            retries--;
        }
        if (!success) {
            // Force reset and connect
            try {
                await qz.websocket.disconnect();
            } catch (disErr) {}
            try {
                await qz.websocket.connect(connectOpts);
            } catch (err) {
                throw new Error("Unable to establish connection with QZ Tray. Please ensure QZ Tray is running on your computer.");
            }
        }
    }
}

 function confirmPrinted(){
  if(!active||!lastPrintBarcodes.length){notify('Submit labels to the printer first.');return;}
  if(!confirm('Confirm that '+lastPrintBarcodes.length+' QR label(s) have actually been printed successfully?\nThis will update QR Print Date and Printed By.'))return;
  const btn=$('#qrpConfirmPrinted').prop('disabled',true).text('Updating...');
  $.post(api,{action:'mark_printed',projectid:project(),locationid:active.locationid,stockorder_no:active.stockorder_no,sowinglotno_internal:active.sowinglotno_internal,sowingdate_internal:active.sowingdate_internal,requested_date:active.requested_date,barcodes:JSON.stringify(lastPrintBarcodes),printedby:String($('#hidden_loginname').val()||$('#hidden_loginid').val()||'').trim()},null,'json')
   .done(r=>{if(!r.status){notify(r.message||'Unable to record printing');return;}notify(r.message||'Print recorded');lastPrintBarcodes=[];details.forEach(d=>{if((r.barcodes||[]).includes(String(d.barcode))){d.qr_printdate=r.printdate;d.qr_printby=r.printedby;}});filterDetails();load();})
   .fail(x=>{console.error(x.responseText);notify((x.responseJSON&&x.responseJSON.message)||'Unable to record printing. Check server response.');})
   .always(()=>btn.prop('disabled',lastPrintBarcodes.length===0).text('Confirm Printed Labels'));
 }
 $('#qrpConfirmPrinted').on('click',confirmPrinted);
  $('#qrpDetailModal').on('hidden.bs.modal',function(){++detailSeq;active=null;lastPrintBarcodes=[];$('#qrpConfirmPrinted').prop('disabled',true);});
 })(jQuery);
</script>
