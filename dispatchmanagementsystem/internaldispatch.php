<?php
include('../header.php');
date_default_timezone_set("Asia/Calcutta");
?>
<style>
    :root {
        --dispatch-primary: #4f46e5;
        --dispatch-secondary: #7c3aed;
        --dispatch-accent: #06b6d4;
        --dispatch-bg: #f8fafc;
        --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }
    .container-dms { padding: 24px; background: var(--dispatch-bg); min-height: calc(100vh - 120px); }
    .glass-card { background: rgba(255,255,255,0.9); backdrop-filter: blur(10px); border-radius: 20px; border: 1px solid rgba(255,255,255,0.5); box-shadow: var(--card-shadow); transition: transform 0.3s ease, box-shadow 0.3s ease; }
    .glass-card:hover { transform: translateY(-5px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); }
    .stat-card { padding: 20px; color: white; border-radius: 16px; position: relative; overflow: hidden; }
    .stat-card.primary { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); }
    .stat-card.secondary { background: linear-gradient(135deg, #a855f7 0%, #7c3aed 100%); }
    .stat-card.accent { background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); }
    .stat-card i { position: absolute; right: -10px; bottom: -10px; font-size: 5rem; opacity: 0.2; }
    .dms-table-container { border-radius: 16px; overflow: hidden; background: white; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
    .dms-table thead th { background: #f1f5f9; color: #475569; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; padding: 15px 20px; border: none; }
    .dms-table tbody td { padding: 15px 20px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; color: #1e293b; }
    .badge-status { padding: 6px 12px; border-radius: 50px; font-weight: 600; font-size: 0.75rem; }
    .badge-confirmed { background: #dcfce7; color: #166534; }
    .badge-transit { background: #fef9c3; color: #854d0e; }
    .badge-delivered { background: #e0f2fe; color: #075985; }
    .btn-initiate { background: var(--dispatch-primary); color: white; border-radius: 12px; padding: 8px 16px; font-weight: 600; border: none; transition: all 0.2s; }
    .btn-initiate:hover { background: var(--dispatch-secondary); transform: scale(1.05); color: white; }
    
    .workflow-step { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; }
    .step-icon { width: 40px; height: 40px; border-radius: 50%; background: var(--dispatch-primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; }
    .step-label { font-weight: 600; color: #475569; }
    
    .location-badge {
        display: inline-flex;
        align-items: center;
        background: #f1f5f9;
        color: #475569;
        padding: 4px 10px;
        border-radius: 8px;
        font-size: 0.8rem;
        font-weight: 600;
        text-decoration: none !important;
        transition: all 0.2s;
        border: 1px solid #e2e8f0;
    }
    .location-badge:hover {
        background: #e2e8f0;
        color: var(--dispatch-primary);
        transform: translateY(-1px);
    }
    .card-clickable {
        transition: transform 0.2s, box-shadow 0.2s;
        cursor: pointer;
    }
    .card-clickable:hover {
        transform: translateY(-4px);
        box-shadow: 0 15px 20px -5px rgba(0, 0, 0, 0.15) !important;
    }
    .card-active {
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.4) !important;
        border: 2px solid #ffffff !important;
    }
</style>
<div class="container-dms">
    <div class="row mb-5">
        <div class="col-md-8">
            <h1 class="fw-bold text-slate-900 mb-2">Internal Dispatch Management</h1>
            <p class="text-slate-500">Manage internal production transfers between polyhouses with real‑time tracking.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <button class="btn btn-outline-primary glass-card px-4 py-2" onclick="location.reload()">
                <i class="fa fa-sync-alt me-2"></i>Refresh
            </button>
            <button class="btn btn-outline-secondary glass-card px-4 py-2 ms-2" onclick="location.href='index.php?k=<?php echo $key; ?><?php echo $iframe_param; ?>'">
                <i class="fa fa-truck me-2"></i>Customer Shipments
            </button>
        </div>
    </div>
    <div class="row g-4 mb-5">
        <div class="col-md-3"><div class="stat-card primary shadow-lg card-clickable card-active" id="card_pending"><div class="small">Pending Transfers</div><div class="h2 fw-bold mb-0" id="stat_pending">0</div><i class="fa fa-clock"></i></div></div>
        <div class="col-md-3"><div class="stat-card secondary shadow-lg card-clickable" id="card_transit"><div class="small">In‑Transit</div><div class="h2 fw-bold mb-0" id="stat_transit">0</div><i class="fa fa-truck-moving"></i></div></div>
        <div class="col-md-3"><div class="stat-card accent shadow-lg card-clickable" id="card_delivered"><div class="small">Completed Today</div><div class="h2 fw-bold mb-0" id="stat_delivered">0</div><i class="fa fa-check-circle"></i></div></div>
        <div class="col-md-3"><div class="glass-card p-4 h-100 d-flex align-items-center justify-content-center card-clickable" id="card_total_trips"><div class="text-center"><div class="text-slate-400 small uppercase fw-bold mb-1">Total Moves (MTD)</div><div class="h3 fw-bold text-slate-800 mb-0" id="stat_total_trips">0</div></div></div></div>
    </div>
    <div class="row g-4">
        <div class="col-lg-12">
            <div class="glass-card mb-4">
                <div class="card-header bg-transparent border-0 p-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-slate-800" id="list_title"><i class="fa fa-exchange-alt me-2 text-primary"></i>Internal Production Transfers</h5>
                   <!--  <div class="d-flex gap-3 align-items-center">
                        <button id="btn_move_selected" class="btn btn-primary rounded-pill px-3 py-1 btn-sm shadow-sm" style="display: none; background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); border: none; font-weight: 600;">
                            <i class="fa fa-truck-moving me-2"></i>Initiate Move (<span id="selected_count">0</span>)
                        </button>
 

                        <input type="text" id="search_dispatch" class="form-control form-control-sm rounded-pill px-3" placeholder="Search...">
                    </div> -->

                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <!-- Left -->
                        <div>
                            <button id="btn_move_selected"
                                    class="btn btn-primary btn-sm rounded-pill px-3 py-1 shadow-sm"
                                    style="display:none; background:linear-gradient(135deg,#6366f1 0%,#4f46e5 100%); border:none; font-weight:600;">
                                <i class="fa fa-truck-moving me-2"></i>
                                Initiate Move (<span id="selected_count">0</span>)
                            </button>
                        </div>

                        <!-- Center -->
                        <!-- <div class="text-center flex-grow-1">
                            <button id="btn_transit"
                                    class="btn btn-warning btn-sm px-4 rounded-pill fw-semibold">
                                <i class="fa fa-truck me-2"></i>
                                In Loading / In Transit
                            </button>
                        </div> -->

                        <!-- Right -->
                        <div style="width:250px;">
                            <input type="text"
                                   id="search_dispatch"
                                   class="form-control form-control-sm rounded-pill px-3"
                                   placeholder="Search...">
                        </div>

                    </div>
                </div>

                <!-- Bootstrap 4 Tabs -->
                <ul class="nav nav-tabs mb-3" id="myTabs" role="tablist">


                    <li class="nav-item"> 
                        <a class="nav-link active"
                           id="dispatch-tab"
                           data-toggle="tab"
                           href="#dispatch_tab">
                            Dispatch Orders
                        </a>
                    </li>
                    <li class="nav-item"> 
                        <a class="nav-link"
                           id="transit-tab"
                           data-toggle="tab"
                           href="#transit_tab">
                            In Loading
                        </a>
                    </li>

                </ul>

                <div class="tab-content" id="myTabsContent">

                    <!-- Dispatch Orders Tab -->
                    <div class="tab-pane fade  show active"
                         id="dispatch_tab"
                         role="tabpanel"
                         aria-labelledby="dispatch-tab">

                        <div class="card-body p-0">
                            <div class="table-responsive">

                                <table class="table dms-table mb-0"
                                       id="tbl_dispatch_list">

                                    <thead>
                                        <tr id="tbl_header">
                                            <th class="text-center"
                                                style="white-space:nowrap; width:120px;">

                                                All<br>

                                                <input type="checkbox"
                                                       id="select_all_pending"
                                                       style="
                                                           width:16px;
                                                           height:16px;
                                                           cursor:pointer;
                                                           margin:0 0 0 8px;
                                                           padding:0;
                                                           vertical-align:middle;
                                                           accent-color:var(--dispatch-primary);
                                                       ">
                                            </th>

                                            <th>Stock Order #</th>
                                            <th>Variety of Plant</th>
                                            <th>Quantity</th>
                                            <th>Dispatch Date</th>
                                            <th class="d-none">Location</th>
                                            <th>Sowing Lot No</th>
                                            <th>Current Location</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <tr>
                                            <td colspan="8" class="text-center py-5">
                                                <div class="spinner-border text-primary"
                                                     role="status"></div>

                                                <p class="mt-2 text-muted">
                                                    Fetching latest data...
                                                </p>
                                            </td>
                                        </tr>
                                    </tbody>

                                </table>

                            </div>
                        </div>

                    </div>

                    <!-- Transit Tab -->
                    <div class="tab-pane fade"
                         id="transit_tab"
                         role="tabpanel"
                         aria-labelledby="transit-tab">

                        <div class="card-body p-0">
                            <div class="table-responsive">

                                <table class="table dms-table mb-0" id="tbl_transit">
                                    <thead>
                                        <tr id="tbl_transit_header">
                                            <th class="text-center"></th>
                                            <th class="text-center">Transit</th>
                                            <th>Location</th>
                                            <th>Truck No.</th>
                                            <th>Driver Name</th>
                                            <th>Mobile No.</th>
                                            <th class="text-end">Load Capacity</th>
                                            <th class="text-end">Load Qty</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <tr>
                                            <td colspan="8" class="text-center py-5">
                                                <div class="spinner-border text-primary"
                                                     role="status"></div>

                                                <p class="mt-2 text-muted">
                                                    Fetching latest data...
                                                </p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                            </div>
                        </div>

                    </div>


                </div>
                <!-- <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table dms-table mb-0" id="tbl_transit">
                            <thead>
                                <tr id="tbl_header">
                                    <th class="text-center d-none">Edit</th>
                                    <th class="text-center">Transit</th>
                                    <th>Location</th>
                                    <th>Truck No.</th>
                                    <th>Driver Name</th>
                                    <th>Mobile No.</th>
                                    <th class="text-end">Load Capacity</th>
                                    <th class="text-end">Load Qty</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <div class="spinner-border text-primary"
                                             role="status"></div>

                                        <p class="mt-2 text-muted">
                                            Fetching latest data...
                                        </p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div> -->

                <!-- <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table dms-table mb-0" id="tbl_dispatch_list">
                            <thead>
                                <tr id="tbl_header">
                                    <th class="text-center" style="white-space: nowrap; width: 120px;">All<br/><input type="checkbox" id="select_all_pending" style="width: 16px; height: 16px; cursor: pointer; margin: 0 0 0 8px; padding: 0; vertical-align: middle; accent-color: var(--dispatch-primary);"></th>
                                    <th>Stock Order #</th>
                                    <th>Variety of Plant</th>
                                    <th>Quantity</th>
                                    <th>Dispatch Date</th>
                                    <th class="d-none">Location</th>
                                    <th>Sowing Lot No</th>
                                    <th>Current Location</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="8" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Fetching latest data...</p></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div> -->
            </div>
        </div>
    </div>
</div>

<!-- Location Details Modal -->
<div class="modal fade" id="modal_location_details" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content glass-card border-0 overflow-hidden">
            <div class="modal-header border-0 text-white p-4" style="background: var(--dispatch-primary) !important;">
                <h5 class="modal-title fw-bold mb-0"><i class="fa fa-map-marked-alt me-2"></i>Location Breakdown</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 1; font-size: 1.75rem; line-height: 1; margin: -1rem -1rem -1rem auto; background: transparent; border: 0; outline: none; padding: 1rem;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-4">
                    <div class="small text-slate-400 uppercase fw-bold">Stock Order #</div>
                    <div class="h5 fw-bold text-slate-800" id="loc_modal_order_no">-</div>
                </div>
                <div id="loc_modal_content">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $iframe_param = (isset($_GET['iframe']) && $_GET['iframe'] == 'true') ? '&iframe=true' : ''; include('../footer.php'); ?>
<script>
let currentMode = 'internal';
let currentFilter = 'pending';

$(document).ready(function(){
    
    loadDispatchList(currentMode, currentFilter);
    loadStats();
    
    // Add search/filter functionality
    $('#search_dispatch').on('keyup', function() {
        let value = $(this).val().toLowerCase();
        $('#tbl_dispatch_list tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    // Stats cards click handlers
    $('#card_pending').on('click', function() {
        switchFilter('pending');
    });
    $('#card_transit').on('click', function() {
        switchFilter('transit');
    });
    $('#card_delivered').on('click', function() {
        switchFilter('completed');
    });
    $('#card_total_trips').on('click', function() {
        switchFilter('total');
    });

    // Checkbox and bulk move handlers
  /*  $(document).on('change', '.order-select-chk', function() {
        let checkedCount = $('.order-select-chk:checked').length;
        if (checkedCount > 0) {
            $('#selected_count').text(checkedCount);
            $('#btn_move_selected').fadeIn(200);
        } else {
            $('#btn_move_selected').fadeOut(200);
        }
    });*/
 $(document).on('change', '.order-select-chk', function () {
    let $currentCheckbox = $(this);
    let currentLocation = $currentCheckbox
        .closest('tr')
        .find('.location-td')
        .data('locationid');

    if ($currentCheckbox.is(':checked')) {

        // Find another checked checkbox, excluding the current one
        let $firstChecked = $('.order-select-chk:checked')
            .not($currentCheckbox)
            .first();

        if ($firstChecked.length > 0) {
            let selectedLocation = $firstChecked
                .closest('tr')
                .find('.location-td')
                .data('locationid');

            if (String(selectedLocation) !== String(currentLocation)) {
                $currentCheckbox.prop('checked', false);

                Swal.fire({
                    icon: 'warning',
                    title: 'Different Location',
                    text: 'Please select items from the same location only.'
                });

                return;
            }
        }
    }

    // Update selected count
    let checkedCount = $('.order-select-chk:checked').length;

    $('#selected_count').text(checkedCount);

    if (checkedCount > 0) {
        $('#btn_move_selected').fadeIn(200);
    } else {
        $('#btn_move_selected').fadeOut(200);
    }
});

//  $(document).on('change', '#select_all_pending', function () {
//     let isChecked = $(this).is(':checked');

//     let $visibleCheckboxes = $('#tbl_dispatch_list tbody tr:visible')
//         .find('.order-select-chk');

//     // Uncheck all visible rows
//     if (!isChecked) {
//         $visibleCheckboxes.prop('checked', false);

//         updateSelectedCount();
//         return;
//     }

//     let selectedLocation = null;

//     // First preference: location of an already selected row
//     let $alreadyChecked = $('.order-select-chk:checked')
//         .not($visibleCheckboxes)
//         .first();

//     // Also check visible selected rows, excluding select-all checkbox
//     if (!$alreadyChecked.length) {
//         $alreadyChecked = $visibleCheckboxes.filter(':checked').first();
//     }

//     if ($alreadyChecked.length) {
//         selectedLocation = $alreadyChecked
//             .closest('tr')
//             .find('.location-td')
//             .data('locationid');
//     }

//     // If nothing is selected, use first visible row location
//     if (selectedLocation === null || selectedLocation === undefined) {
//         let $firstRow = $('#tbl_dispatch_list tbody tr:visible')
//             .has('.order-select-chk')
//             .first();

//         selectedLocation = $firstRow
//             .find('.location-td')
//             .data('locationid');
//     }

//     let skippedCount = 0;

//     $visibleCheckboxes.each(function () {
//         let $checkbox = $(this);

//         let rowLocation = $checkbox
//             .closest('tr')
//             .find('.location-td')
//             .data('locationid');

//         if (String(rowLocation) === String(selectedLocation)) {
//             $checkbox.prop('checked', true);
//         } else {
//             $checkbox.prop('checked', false);
//             skippedCount++;
//         }
//     });

//     updateSelectedCount();

//     if (skippedCount > 0) {
//         Swal.fire({
//             icon: 'info',
//             title: 'Same Location Selected',
//             text: skippedCount +
//                 ' item(s) from other locations were not selected.'
//         });
//     }
// });

    $(document).on('click', '#btn_move_selected', function() {
        debugger;
        let selectedIds = [];
        /*$('.order-select-chk:checked').each(function() {
            selectedIds.push($(this).val());
        });*/
 
        $('.order-select-chk:checked').each(function () { //modified on 30-07-2026 ajay
            let row = $(this).closest('tr');
            if ( (row.find('.location-td').data('sowinglotno_internal') || '')){
                let newvar =
                    (row.find('.location-td').data('productid') || '') + "|" +
                    // (row.find('.location-td').data('roottype') || '') + "|" +
                    (row.find('.location-td').data('locationid') || '') + "|" +
                    // (row.find('.location-td').data('polyhouseid') || '') + "|" +
                    (row.find('.location-td').data('sowinglotno_internal') || '');
                    selectedIds.push($(this).val() + "|" + newvar);

            } else{
                  $(this).prop('checked', false);    
            }
        });
        if (selectedIds.length === 0) {
            alert ("You have not selected or sowing lot  no is empty");
            return;
        }    
        
        let idsStr = selectedIds.join(',');
        initiateDispatch(idsStr, 'internal');
    });

    // $(document).on('click', '#btn_transit', function (event) {
    //     event.preventDefault();
    //     const $tbody = $('#tbl_transit tbody');
    //     $tbody.html(`
    //         <tr>
    //             <td colspan="8" class="text-center py-5">
    //                 <div class="spinner-border text-primary" role="status"></div>
    //                 <p class="mt-2 text-muted">Fetching latest data...</p>
    //             </td>
    //         </tr>
    //     `);

    //     $.ajax({
    //         url: 'ajax/handle_pso.php',
    //         type: 'POST',
    //         dataType: 'html',
    //         data: {
    //             action: 'fetch_vehicleinloading',
    //         },

    //         success: function (response) {
    //             $tbody.html(response);
    //         },

    //         error: function (xhr) {
    //             console.error(xhr.responseText);

    //             $tbody.html(`
    //                 <tr>
    //                     <td colspan="8"
    //                         class="text-center text-danger py-4">
    //                         Failed to load transit data.
    //                     </td>
    //                 </tr>
    //             `);
    //         }
    //     });
    // });
    $(document).on('shown.bs.tab', 'a[data-toggle="tab"]', function (e) {
        let targetTab = $(e.target).attr('href');
        debugger

        if (targetTab === '#transit_tab') {
            loadTransitData();
        } else if (targetTab === '#dispatch_tab') {
            loadDispatchList(currentMode, currentFilter);
        }
    });
})
function switchFilter(filter) {
    currentFilter = filter;
    
    // Reset checkbox state
    $('#btn_move_selected').hide();
    $('.order-select-chk').prop('checked', false);
    if ($('#select_all_pending').length) {
        $('#select_all_pending').prop('checked', false);
    }

    $('.stat-card').removeClass('card-active');
    $('.glass-card').removeClass('card-active');
    if (filter === 'pending') {
        $('#card_pending').addClass('card-active');
    } else if (filter === 'transit') {
        $('#card_transit').addClass('card-active');
    } else if (filter === 'completed') {
        $('#card_delivered').addClass('card-active');
    } else if (filter === 'total') {
        $('#card_total_trips').addClass('card-active');
    }
    
    updateTableHeader(filter);
    loadDispatchList(currentMode, filter);
}

function updateTableHeader(filter) {
    let headerHtml = '';
    let titleHtml = '';
    
    if (filter === 'pending') {
        titleHtml = '<i class="fa fa-clock me-2 text-primary"></i>Pending Internal Transfers';
        headerHtml = `
            <th class="text-center" style="white-space: nowrap; width: 120px;">Action <input type="checkbox" id="select_all_pending" style="width: 16px; height: 16px; cursor: pointer; margin: 0 0 0 8px; padding: 0; vertical-align: middle; accent-color: var(--dispatch-primary);"></th>
            <th>Stock Order #</th>
            <th>Variety of Plant</th>
            <th>Quantity</th>
            <th>Dispatch Date</th>
            <th>Current Location</th>
        `;
    } else if (filter === 'transit') {
        titleHtml = '<i class="fa fa-truck-moving me-2 text-primary"></i>In‑Transit Dispatch List';
        headerHtml = `
            <th class="text-center" style="width: 150px;">Action</th>
            <th>Trip ID</th>
            <th>Vehicle & Driver</th>
            <th>Quantity</th>
            <th>Route (From &rarr; To)</th>
            <th>Dispatched Time</th>
        `;
    } else if (filter === 'completed') {
        titleHtml = '<i class="fa fa-check-circle me-2 text-primary"></i>Completed Dispatch List';
        headerHtml = `
            <th class="text-center" style="width: 120px;">Action</th>
            <th>Trip ID</th>
            <th>Vehicle & Driver</th>
            <th>Quantity</th>
            <th>Route (From &rarr; To)</th>
            <th>Delivered Time</th>
        `;
    } else if (filter === 'total') {
        titleHtml = '<i class="fa fa-history me-2 text-primary"></i>Total Internal Movements (MTD)';
        headerHtml = `
            <th class="text-center" style="width: 150px;">Action</th>
            <th>Trip ID</th>
            <th>Vehicle & Driver</th>
            <th>Quantity</th>
            <th>Route (From &rarr; To)</th>
            <th>Status</th>
        `;
    }
    
    $('#list_title').html(titleHtml);
    $('#tbl_header').html(headerHtml);
}

function loadDispatchList(mode, filter = 'pending'){
    $('#tbl_dispatch_list tbody').html('<tr><td colspan="6" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>');
    $.ajax({
        url: 'ajax/handle_pso.php',
        type: 'POST',
        data: { 
            action: 'fetch_dispatch_list', 
            mode: mode, 
            filter: filter, 
            k: '<?php echo $key; ?>',
            iframe: '<?php echo (isset($_GET['iframe']) && $_GET['iframe'] == 'true') ? 'true' : 'false'; ?>'
        },
        success: function(response){ $('#tbl_dispatch_list tbody').html(response); },
        error: function(){ $('#tbl_dispatch_list tbody').html('<tr><td colspan="6" class="text-center text-danger">Failed to load data.</td></tr>'); }
    });
}

function loadStats(){
    $.ajax({
        url: 'ajax/handle_pso.php',
        type: 'POST',
        data: { action: 'fetch_dashboard_stats', mode: 'internal' },
        dataType: 'json',
        success: function(data){ $('#stat_pending').text(data.pending); $('#stat_transit').text(data.transit); $('#stat_delivered').text(data.delivered); $('#stat_total_trips').text(data.total_mtd); }
    });
}

function initiateDispatch(id, mode, isExisting = false){
    let title = "Initiate Internal Move?";
    let refLabel = "Production Order: ";
    let icon = 'info';
    let text = `Moving to Vehicle & Driver Assignment for ${refLabel}${id}`;
    if(isExisting){ title = "Dispatch Already Initiated"; text = `An allocation already exists for ${refLabel}${id}. Would you like to view/edit existing details?`; icon = 'success'; }
    Swal.fire({title:title,text:text,icon:icon,showCancelButton:true,confirmButtonColor:'#4f46e5',cancelButtonColor:'#d33',confirmButtonText:isExisting?'Yes, Edit/View':'Yes, Proceed'}).then((result)=>{ if(result.isConfirmed){ let param='stockorder='+id; window.location.href='assign_vehicle.php?k=<?php echo $key; ?>&'+param+'&type='+mode+'<?php echo $iframe_param; ?>'; } });
}
function viewLocationDetails(orderNo,productid,locationid, sowinglotno_internal){    
         debugger;                 

    $('#loc_modal_order_no').text(orderNo);
    $('#loc_modal_content').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 small text-muted">Checking Polyhouses...</p></div>');
    $('#modal_location_details').modal('show');
    $.ajax({url:'ajax/handle_pso.php',type:'POST',
        data:{action:'fetch_order_location',
            stockorder_no:orderNo,
            productid:productid,
            
            locationid:locationid, 
            sowinglotno_internal:sowinglotno_internal 
        },success:function(res){$('#loc_modal_content').html(res);},error:function(){$('#loc_modal_content').html('<div class="alert alert-danger">Failed to fetch tracking data.</div>');}});
}

function edittripdetails(tripid) {
    initiateDispatch_edit(tripid, 'edittripid', true);
    initiateDispatch
} 
function finalmove(tripid,vehicleno) {
    tripid = String(tripid || '').trim();

    if (!tripid) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Trip',
            text: 'Trip ID is missing.'
        });
        return;
    }

    Swal.fire({
        title: 'Ready to Move?',
        text: `Do you want to mark vehicle no ${vehicleno} ( ${tripid} ) as In Transit ?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, Move Vehicle',
        cancelButtonText: 'Cancel',
        allowOutsideClick: false
    }).then(function (result) {
        if (!result.isConfirmed) {
            return;
        }

        Swal.fire({
            title: 'Updating...',
            text: 'Please wait while the trip status is updated.',
            allowOutsideClick: false,
            didOpen: function () {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: 'ajax/handle_pso.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'update_trip_intransit',
                tripid: tripid,
                status: 'InTransit'
            },

            success: function (res) {
                if (res.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Vehicle Moved',
                        text: res.message || `Trip ID ${tripid} is now In Transit.`,
                        confirmButtonColor: '#4f46e5'
                    }).then(function () {
                        loadTransitData();
                        loadStats();
                        // Open the Transit tab
                        $('#transit-tab').tab('show');
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Update Failed',
                        text: res.message || 'Unable to update the trip status.'
                    });
                }
            },

            error: function (xhr) {
                console.error(xhr.responseText);

                Swal.fire({
                    icon: 'error',
                    title: 'Server Error',
                    text: 'Something went wrong while updating the trip.'
                });
            }
        });
    });
}


function initiateDispatch_edit(id, mode, isExisting = false) {
    let title = "Initiate Internal Move?";
    let refLabel = "Trip Id: ";
    let icon = "info";
    let text = `Moving to Vehicle & Driver Assignment for ${refLabel}${id}`;

    if (isExisting) {
        title = "Dispatch Already Initiated";
        text = `An allocation already exists for ${refLabel}${id}. Would you like to view/edit existing details?`;
        icon = "success";
    }

    Swal.fire({
        title: title,
        text: text,
        icon: icon,
        showCancelButton: true,
        confirmButtonColor: "#4f46e5",
        cancelButtonColor: "#d33",
        confirmButtonText: isExisting ? "Yes, Edit/View" : "Yes, Proceed"
    }).then((result) => {
        if (result.isConfirmed) {

            const url = 'assign_vehicle.php?k=<?php echo $key; ?>&stockorder=' + id +
          '&type=' + mode +
          '<?php echo $iframe_param; ?>';

         

            window.location.href = url;
        }
    });
}
function updateSelectedCount() {
    let checkedCount = $('.order-select-chk:checked').length;

    $('#selected_count').text(checkedCount);

    if (checkedCount > 0) {
        $('#btn_move_selected').fadeIn(200);
    } else {
        $('#btn_move_selected').fadeOut(200);
    }
}
function loadTransitData(){
        const $tbody = $('#tbl_transit tbody');
        $tbody.html(`
            <tr>
                <td colspan="8" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Fetching latest data...</p>
                </td>
            </tr>
        `);

        $.ajax({
            url: 'ajax/handle_pso.php',
            type: 'POST',
            dataType: 'html',
            data: {
                action: 'fetch_vehicleinloading',
            },

            success: function (response) {
                $tbody.html(response);
            },

            error: function (xhr) {
                console.error(xhr.responseText);

                $tbody.html(`
                    <tr>
                        <td colspan="8"
                            class="text-center text-danger py-4">
                            Failed to load transit data.
                        </td>
                    </tr>
                `);
            }
        });

}
</script>
