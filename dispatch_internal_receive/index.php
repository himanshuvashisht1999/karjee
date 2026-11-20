<?php
include('../header.php');
date_default_timezone_set("Asia/Calcutta");

// Check if specific project context is passed via header or session
// (Usually handled in header.php)
?>
<style>
    :root {
        --dispatch-primary: #4f46e5;
        --dispatch-secondary: #7c3aed;
        --dispatch-accent: #06b6d4;
        --dispatch-bg: #f8fafc;
        --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    .container-dms {
        padding: 24px;
        background: var(--dispatch-bg);
        min-height: calc(100vh - 120px);
    }

    .glass-card {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.5);
        box-shadow: var(--card-shadow);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .glass-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }

    .stat-card {
        padding: 20px;
        color: white;
        border-radius: 16px;
        position: relative;
        overflow: hidden;
    }

    .stat-card.primary { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); }
    .stat-card.secondary { background: linear-gradient(135deg, #a855f7 0%, #7c3aed 100%); }
    .stat-card.accent { background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); }

    .stat-card i {
        position: absolute;
        right: -10px;
        bottom: -10px;
        font-size: 5rem;
        opacity: 0.2;
    }

    .dms-table-container {
        border-radius: 16px;
        overflow: hidden;
        background: white;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .dms-table thead th {
        background: #f1f5f9;
        color: #475569;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        padding: 15px 20px;
        border: none;
    }

    .dms-table tbody td {
        padding: 15px 20px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        color: #1e293b;
    }

    .badge-status {
        padding: 6px 12px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.75rem;
    }

    .badge-confirmed { background: #dcfce7; color: #166534; }
    .badge-transit { background: #fef9c3; color: #854d0e; }
    .badge-delivered { background: #e0f2fe; color: #075985; }

    .btn-initiate {
        background: var(--dispatch-primary);
        color: white;
        border-radius: 12px;
        padding: 8px 16px;
        font-weight: 600;
        border: none;
        transition: all 0.2s;
    }

    .btn-initiate:hover {
        background: var(--dispatch-secondary);
        transform: scale(1.05);
        color: white;
    }

    /* Segmented Control / Tabs */
    .loading-tabs {
        background: #edf2f7;
        padding: 6px;
        border-radius: 16px;
        display: inline-flex;
        margin-bottom: 24px;
        border: 1px solid #e2e8f0;
    }

    .tab-btn {
        border: none;
        background: transparent;
        padding: 8px 24px;
        border-radius: 12px;
        font-weight: 700;
        color: #64748b;
        transition: all 0.2s;
    }

    .tab-btn.active {
        background: white;
        color: var(--dispatch-primary);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .workflow-step {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 20px;
    }

    .step-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--dispatch-primary);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }

    .step-label {
        font-weight: 600;
        color: #475569;
    }

    /* Location Details Styling */
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
    <!-- Header Section -->
    <div class="row mb-5">
        <div class="col-md-12">
            <h1 class="fw-bold text-slate-900 mb-2">Despatch Internal Receive Management System</h1> 
        </div>
        <div class="col-md-4 text-md-end">
            <button class="btn btn-outline-primary glass-card px-4 py-2" onclick="location.reload()">
                <i class="fa fa-sync-alt me-2"></i>Refresh Dashboard
            </button> 
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row g-4 mb-5 d-none">
        <div class="col-md-3">
            <div class="stat-card primary shadow-lg card-clickable card-active" id="card_pending">
                <div class="small">Loading </div>
                <div class="h2 fw-bold mb-0" id="stat_loading">0</div>
                <i class="fa fa-clock"></i>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card secondary shadow-lg card-clickable" id="card_transit">
                <div class="small">In-Transit</div>
                <div class="h2 fw-bold mb-0" id="stat_transit">0</div>
                <i class="fa fa-truck-moving"></i>
            </div>
        </div>
        <div class="col-md-3 d-none">
            <div class="stat-card accent shadow-lg card-clickable" id="card_delivered">
                <div class="small">Delivered Today</div>
                <div class="h2 fw-bold mb-0" id="stat_delivered">0</div>
                <i class="fa fa-check-circle"></i>
            </div>
        </div>
        <div class="col-md-3 d-none">
            <div class="glass-card p-4 h-100 d-flex align-items-center justify-content-center card-clickable" id="card_total_trips">
                <div class="text-center">
                    <div class="text-slate-400 small uppercase fw-bold mb-1">Total Trips (MTD)</div>
                    <div class="h3 fw-bold text-slate-800 mb-0" id="stat_total_trips">0</div>
                </div>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs mb-3" id="myTabs" role="tablist">
        <li class="nav-item"> 
            <a class="nav-link active"
               id="loading-tab"
               data-toggle="tab"
               href="#loading_tab">
               In Transit
            </a>
        </li>
        <!-- <li class="nav-item"> 
            <a class="nav-link"
               id="transit-tab"
               data-toggle="tab"
               href="#transit_tab">
                In tansit
            </a>
        </li> -->
<!-- 
          <li class="nav-item mx-auto">
            <button type="button" class="btn btn-primary btn-sm"  id ="btn_newloading">
                <i class="fa fa-truck"></i> New Loading
            </button>
        </li> -->
    </ul>


    <div class="tab-content" id="myTabsContent">
        <div class="tab-pane fade  show active"
             id="loading_tab"
             role="tabpanel"
             aria-labelledby="loading-tab">
            <div class="card-body p-0">
                <div class="table-responsive">                    
                    <div class="d-flex gap-2">
                        <input type="text" id="search_internal" class="form-control form-control-sm rounded-pill px-3" placeholder="Search...">
                    </div>
                    <table class="table dms-table mb-0" id="tbl_loading">
                        <thead>
                            <tr id="tbl_loading_header">
                                <th class="text-center"></th>

                                <th class="text-center">Trip ID</th>
                                <th class="text-center">Date</th>
                                <th>Location</th>
                                <th>Truck No.</th>
                                <th>Driver Name</th>
                                <th>Mobile No.</th>
                                <th class="text-end">No. Of Trays</th>
                                <th class="text-end">No. Of Plant</th>
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
</div>

<?php 
$iframe_param = (isset($_GET['iframe']) && $_GET['iframe'] == 'true') ? '&iframe=true' : '';
include('../footer.php'); 
?>

 

<script>
let currentMode = 'client';
let currentFilter = 'pending';

$(document).ready(function() {
    fetchloadingList_receive();
     
    
    // Add search/filter functionality


    $('#search_internal').on('keyup', function() {
        let value = $(this).val().toLowerCase();
        $('#tbl_loading tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    // Stats cards click handlers
    // $('#card_pending').on('click', function() {
    //     switchFilter('pending');
    // });
    // $('#card_transit').on('click', function() {
    //     switchFilter('transit');
    // });
    // $('#card_delivered').on('click', function() {
    //     switchFilter('completed');
    // });
    // $('#card_total_trips').on('click', function() {
    //     switchFilter('total');
    // });


/*     $(document).on('click', '#btn_newloading', function() {
       
 
          
  
    let text = `Moving to Vehicle & Driver Assignment`;
    title ="Loading new vehicle"
     let icon = 'info';
    Swal.fire({
        title: title,
        text: text,
        icon: icon,
        showCancelButton: true,
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#d33',
        confirmButtonText:  'Yes, Proceed'
    }).then((result) => {
        if (result.isConfirmed) {
            
            window.location.href = 'assignment_view.php?k=<?php echo $key; ?><?php echo $iframe_param; ?>';
        }
    });
    });*/
});

 
 
 

 function fetchloadingList_receive() {
    $('#tbl_loading tbody').html(
        '<tr><td colspan="8" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>'
    );

    $.ajax({
        url: 'ajax/handle_receive.php',
        type: 'POST',
        dataType: 'html',

        data: {
            action: 'fetch_vehicleinloading'
        },

        beforeSend: function () {
            console.log('Sending request...');
        },

        success: function (response) {

            console.log('Success Response');
            console.log(response);

            $('#tbl_loading tbody').html(response);
        },

        error: function (xhr, textStatus, errorThrown) {

            console.error('AJAX Error');
            console.error('Status Code :', xhr.status);
            console.error('Status      :', textStatus);
            console.error('Error       :', errorThrown);
            console.error('Response    :');
            console.error(xhr.responseText);

            $('#tbl_loading tbody').html(
                '<tr>' +
                '<td colspan="6" class="text-danger text-center">' +
                'Error : ' + xhr.status + '<br>' +
                errorThrown +
                '</td>' +
                '</tr>'
            );
        },

        complete: function () {
            console.log('AJAX Completed');
        }
    });
}

/*function loadStats() {
    $.ajax({
        url: 'ajax/handle_receive.php',
        type: 'POST',
        data: { action: 'fetch_dashboard_stats' },
        dataType: 'json',
        success: function(data) {
            $('#stat_loading').text(
                `Vehicle (${data.loading}) / Plants (${Number(data.loading_totalplants).toLocaleString()})`
            );

            $('#stat_transit').text(
                `Vehicle (${data.transit}) / Plants (${Number(data.transit_totalplants).toLocaleString()})`
            );
        }
    });
}*/
 
 

/*function edittripdetails(tripid) {
    initiateDispatch_edit(tripid, 'edittripid', true);
    
} */


function receivetransit(id, vehicleno, depatchlocationid,despatchlocationname) {
    let title = "Initiate Receive?"; 
    let icon = "info";
    let text = `Receiving Location ${despatchlocationname}  vide Vehicle no ${vehicleno}`;

  

 
    Swal.fire({
        title: title,
        text: text,
        icon: icon,
        showCancelButton: true,
        confirmButtonColor: "#4f46e5",
        cancelButtonColor: "#d33",
        confirmButtonText:   "Yes, Proceed"
    }).then((result) => {
        if (result.isConfirmed) {            
            const url = 'assignment_receive.php?k=<?php echo $key; ?>'
            + '&tripid=' + id
            + '&loc=' + encodeURIComponent(depatchlocationid)
            + '<?php echo $iframe_param; ?>';       

            window.location.href = url;
        }
    });
}

// function receivetransit(tripid,vehicleno,$depatchlocationid) {
//     tripid = String(tripid || '').trim();

//     if (!tripid) {
//         Swal.fire({
//             icon: 'error',
//             title: 'Invalid Trip',
//             text: 'Trip ID is missing.'
//         });
//         return;
//     }

//     Swal.fire({
//         title: 'Ready to Move?',
//         text: `Do you want to mark vehicle no ${vehicleno} ( ${tripid} ) as In Transit ?`,
//         icon: 'question',
//         showCancelButton: true,
//         confirmButtonColor: '#4f46e5',
//         cancelButtonColor: '#d33',
//         confirmButtonText: 'Yes, Move Vehicle',
//         cancelButtonText: 'Cancel',
//         allowOutsideClick: false
//     }).then(function (result) {
//         if (!result.isConfirmed) {
//             return;
//         }

//         Swal.fire({
//             title: 'Updating...',
//             text: 'Please wait while the trip status is updated.',
//             allowOutsideClick: false,
//             didOpen: function () {
//                 Swal.showLoading();
//             }
//         });

//         $.ajax({
//             url: 'ajax/handle_receive.php',
//             type: 'POST',
//             dataType: 'json',
//             data: {
//                 action: 'update_trip_intransit',
//                 tripid: tripid,
//                 status: 'InTransit'
//             },

//             success: function (res) {
//                 if (res.status === 'success') {
//                     Swal.fire({
//                         icon: 'success',
//                         title: 'Vehicle Moved',
//                         text: res.message || `Trip ID ${tripid} is now In Transit.`,
//                         confirmButtonColor: '#4f46e5'
//                     }).then(function () {

//                     window.location.href =
//                         'dispatch_docs.php?k=<?php echo $key; ?>' +
//                         '&trip=' + encodeURIComponent(tripid) +
//                         '<?php echo (isset($_GET['iframe']) && $_GET['iframe'] == 'true') ? '&iframe=true' : ''; ?>';
                         
//                     });
//                 } else {
//                     Swal.fire({
//                         icon: 'error',
//                         title: 'Update Failed',
//                         text: res.message || 'Unable to update the trip status.'
//                     });
//                 }
//             },

//             error: function (xhr) {
//                 console.error(xhr.responseText);

//                 Swal.fire({
//                     icon: 'error',
//                     title: 'Server Error',
//                     text: 'Something went wrong while updating the trip.'
//                 });
//             }
//         });
//     });
// }

</script>
