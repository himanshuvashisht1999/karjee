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
    .dispatch-tabs {
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
        <div class="col-md-8">
            <h1 class="fw-bold text-slate-900 mb-2">Dispatch Management System</h1>
            <p class="text-slate-500">Streamline your logistics from farm to customer with real-time tracking and consolidated documentation.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <button class="btn btn-outline-primary glass-card px-4 py-2" onclick="location.reload()">
                <i class="fa fa-sync-alt me-2"></i>Refresh Dashboard
            </button>
            <button class="btn btn-outline-secondary glass-card px-4 py-2 ms-2" onclick="location.href='internaldispatch.php?k=<?php echo $key; ?><?php echo $iframe_param; ?>'">
                <i class="fa fa-exchange-alt me-2"></i>Internal Dispatch
            </button>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="stat-card primary shadow-lg card-clickable card-active" id="card_pending">
                <div class="small">Pending Dispatches</div>
                <div class="h2 fw-bold mb-0" id="stat_pending">0</div>
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
        <div class="col-md-3">
            <div class="stat-card accent shadow-lg card-clickable" id="card_delivered">
                <div class="small">Delivered Today</div>
                <div class="h2 fw-bold mb-0" id="stat_delivered">0</div>
                <i class="fa fa-check-circle"></i>
            </div>
        </div>
        <div class="col-md-3">
            <div class="glass-card p-4 h-100 d-flex align-items-center justify-content-center card-clickable" id="card_total_trips">
                <div class="text-center">
                    <div class="text-slate-400 small uppercase fw-bold mb-1">Total Trips (MTD)</div>
                    <div class="h3 fw-bold text-slate-800 mb-0" id="stat_total_trips">0</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Selection -->
    <div class="text-center">
            <div class="dispatch-tabs">
                <button class="tab-btn active" data-mode="client" onclick="switchMode('client')">
                    <i class="fa fa-user-tag me-2"></i>Customer Shipments
                </button>
            </div>
    </div>

    <!-- Main Content Grid -->
    <div class="row g-4">
        <!-- Dispatch Listing -->
        <div class="col-lg-12">
            <div class="glass-card mb-4">
                <div class="card-header bg-transparent border-0 p-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-slate-800" id="list_title"><i class="fa fa-list-alt me-2 text-primary"></i>Pick Orders for Dispatch</h5>
                    <div class="d-flex gap-2">
                        <input type="text" id="search_dispatch" class="form-control form-control-sm rounded-pill px-3" placeholder="Search...">
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table dms-table mb-0" id="tbl_dispatch_list">
                            <thead>
                                <tr id="tbl_header">
                                    <th>Ref #</th>
                                    <th>Entity</th>
                                    <th>Items</th>
                                    <th>Delivery Date</th>
                                    <th>Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Loaded via AJAX -->
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="sr-only">Loading...</span>
                                        </div>
                                        <p class="mt-2 text-muted">Fetching latest data...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$iframe_param = (isset($_GET['iframe']) && $_GET['iframe'] == 'true') ? '&iframe=true' : '';
include('../footer.php'); 
?>

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

<script>
let currentMode = 'client';
let currentFilter = 'pending';

$(document).ready(function() {
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
});

function switchFilter(filter) {
    currentFilter = filter;

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
        titleHtml = '<i class="fa fa-list-alt me-2 text-primary"></i>Pick Customer Orders';
        headerHtml = '<th>Ref #</th><th>Client</th><th>Items</th><th>Delivery Date</th><th>Status</th><th class="text-center">Action</th>';
    } else if (filter === 'transit') {
        titleHtml = '<i class="fa fa-truck-moving me-2 text-primary"></i>In‑Transit Dispatch List';
        headerHtml = '<th class="text-center" style="width: 150px;">Action</th><th>Trip ID</th><th>Client</th><th>Quantity</th><th>Route (From &rarr; To)</th><th>Dispatched Time</th>';
    } else if (filter === 'completed') {
        titleHtml = '<i class="fa fa-check-circle me-2 text-primary"></i>Completed Dispatch List';
        headerHtml = '<th class="text-center" style="width: 120px;">Action</th><th>Trip ID</th><th>Client</th><th>Quantity</th><th>Route (From &rarr; To)</th><th>Delivered Time</th>';
    } else if (filter === 'total') {
        titleHtml = '<i class="fa fa-history me-2 text-primary"></i>Total Customer Dispatches (MTD)';
        headerHtml = '<th class="text-center" style="width: 150px;">Action</th><th>Trip ID</th><th>Client</th><th>Quantity</th><th>Route (From &rarr; To)</th><th>Status</th>';
    }
    
    $('#list_title').html(titleHtml);
    $('#tbl_header').html(headerHtml);
}

function switchMode(mode) {
    currentMode = mode;
    $('.tab-btn').removeClass('active');
    $(`.tab-btn[data-mode="${mode}"]`).addClass('active');
    
    // Update labels and headers
    if (mode === 'client') {
        switchFilter('pending');
    } else {
        $('#list_title').html('<i class="fa fa-exchange-alt me-2 text-primary"></i>Internal Production Transfers');
        $('#step1_desc').text('Pick a Production Order to move');
        $('#tbl_header').html('<th>Stock Order #</th><th>Product Name</th><th>Quantity</th><th>Delivery Date</th><th>Current Location</th><th class="text-center">Action</th>');
        loadDispatchList(mode, currentFilter);
    }
}

function loadDispatchList(mode, filter = 'pending') {
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
        success: function(response) {
            $('#tbl_dispatch_list tbody').html(response);
        },
        error: function() {
            $('#tbl_dispatch_list tbody').html('<tr><td colspan="6" class="text-center text-danger">Failed to load data.</td></tr>');
        }
    });
}

function loadStats() {
    $.ajax({
        url: 'ajax/handle_pso.php',
        type: 'POST',
        data: { action: 'fetch_dashboard_stats' },
        dataType: 'json',
        success: function(data) {
            $('#stat_pending').text(data.pending);
            $('#stat_transit').text(data.transit);
            $('#stat_delivered').text(data.delivered);
            $('#stat_total_trips').text(data.total_mtd);
        }
    });
}

function initiateDispatch(id, mode, isExisting = false) {
    debugger
    let title = (mode === 'internal') ? "Initiate Internal Move?" : "Initiate Dispatch?";
    let refLabel = (mode === 'internal') ? "Production Order: " : "PSO: ";
    let icon = 'info';
    let text = `Moving to Vehicle & Driver Assignment for ${refLabel}${id}`;
    
    if (isExisting) {
        title = "Dispatch Already Initiated";
        text = `An allocation already exists for ${refLabel}${id}. Would you like to view/edit existing details?`;
        icon = 'success';
    }
    
    Swal.fire({
        title: title,
        text: text,
        icon: icon,
        showCancelButton: true,
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#d33',
        confirmButtonText: isExisting ? 'Yes, Edit/View' : 'Yes, Proceed'
    }).then((result) => {
        if (result.isConfirmed) {
            let param = (mode === 'internal') ? 'stockorder=' + id : 'pso=' + id;
            window.location.href = 'assign_vehicle.php?k=<?php echo $key; ?>&' + param + '&type=' + mode + '<?php echo $iframe_param; ?>';
        }
    });
}

function viewLocationDetails(orderNo) { 
    $('#loc_modal_order_no').text(orderNo);
    $('#loc_modal_content').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 small text-muted">Checking Polyhouses...</p></div>');
    $('#modal_location_details').modal('show');

    $.ajax({
        url: 'ajax/handle_pso.php',
        type: 'POST',
        data: { action: 'fetch_order_location', stockorder_no: orderNo },
        success: function(res) {
            $('#loc_modal_content').html(res);
        },
        error: function() {
            $('#loc_modal_content').html('<div class="alert alert-danger">Failed to fetch tracking data.</div>');
        }
    });
}

// Global defensive fix for "Cannot stop, scanner is not running or paused" error
(function() {
    const originalError = console.error;
    console.error = function() {
        if (arguments[0] && typeof arguments[0] === 'string' && arguments[0].includes('Cannot stop, scanner is not running or paused')) {
            return; // Suppress this specific error
        }
        originalError.apply(console, arguments);
    };

    // Monkey-patch Html5Qrcode if it exists
    const checkAndPatch = () => {
        if (typeof Html5Qrcode !== 'undefined' && !Html5Qrcode._is_patched) {
            const originalStop = Html5Qrcode.prototype.stop;
            Html5Qrcode.prototype.stop = function() {
                try {
                    // Check if scanner is actually running before stopping
                    if (this.getState() === 2) { // 2 = Html5QrcodeScannerState.SCANNING
                        return originalStop.apply(this, arguments);
                    } else {
                        return Promise.resolve();
                    }
                } catch (e) {
                    return Promise.resolve();
                }
            };
            Html5Qrcode._is_patched = true;
            console.log("Dispatch Scanner Defense: Html5Qrcode.stop() patched.");
        }
    };

    // Run immediately and also on a interval for dynamic loading
    checkAndPatch();
    let patchInterval = setInterval(() => {
        checkAndPatch();
        if (typeof Html5Qrcode !== 'undefined' && Html5Qrcode._is_patched) {
            clearInterval(patchInterval);
        }
    }, 1000);
})();
</script>
