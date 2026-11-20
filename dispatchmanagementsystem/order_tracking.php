<?php
include('../header.php');
date_default_timezone_set("Asia/Calcutta");

$trip_id = $_GET['trip'] ?? '';
$back_url = "index.php"; // default
$trip_data = null;

if ($trip_id) {
    // Fetch Trip details
    $trip_res = $krfconn->query("SELECT * FROM krf_vehicle_travel_history WHERE trip_id = '$trip_id'");
    $trip_data = $trip_res ? $trip_res->fetch_assoc() : null;
    if ($trip_data && !empty($trip_data['stockorder_no'])) {
        $back_url = "internaldispatch.php";
    }
}

if (!$trip_id || !$trip_data) {
    echo "<div class='container p-5 text-center'><h3>Invalid Trip ID</h3><a href='{$back_url}?k=$key" . ((isset($_GET['iframe']) && $_GET['iframe'] == 'true') ? "&iframe=true" : "") . "' class='btn btn-primary'>Back to Dashboard</a></div>";
    include('../footer.php');
    exit;
}
?>

<style>
    :root {
        --dispatch-primary: #4f46e5;
        --tracking-accent: #f59e0b;
    }

    .container-tracking {
        padding: 24px;
        background: #f1f5f9;
        min-height: calc(100vh - 120px);
    }

    .glass-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.5);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    #map_sim {
        width: 100%;
        height: 400px;
        background: #e5e7eb url('../img/map_placeholder.png') center/cover;
        border-radius: 16px;
        position: relative;
        overflow: hidden;
    }

    .truck-marker {
        position: absolute;
        top: 50%;
        left: 20%;
        width: 30px;
        height: 30px;
        background: var(--dispatch-primary);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 0 0 8px rgba(79, 70, 229, 0.2);
        animation: pulse-marker 2s infinite, move-marker 10s linear infinite;
    }

    @keyframes pulse-marker {
        0% { box-shadow: 0 0 0 0 rgba(79, 70, 229, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(79, 70, 229, 0); }
        100% { box-shadow: 0 0 0 0 rgba(79, 70, 229, 0); }
    }

    @keyframes move-marker {
        0% { left: 20%; top: 50%; }
        25% { left: 40%; top: 45%; }
        50% { left: 60%; top: 55%; }
        75% { left: 80%; top: 50%; }
        100% { left: 20%; top: 50%; }
    }

    .status-timeline {
        position: relative;
        padding-left: 30px;
    }

    .status-timeline::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #e2e8f0;
    }

    .timeline-item {
        position: relative;
        margin-bottom: 25px;
    }

    .timeline-item::before {
        content: '';
        position: absolute;
        left: -34px;
        top: 0;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: var(--dispatch-primary);
    }
</style>

<div class="container-tracking">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2 class="fw-bold text-slate-900">Live Trip Tracking</h2>
            <p class="text-slate-500">Trip ID: <span class="fw-bold text-primary"><?php echo $trip_id; ?></span> | Status: <span class="badge bg-warning text-dark">In Transit</span></p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Map Simulation -->
        <div class="col-lg-8">
            <div class="glass-card p-4">
                <div id="map_sim">
                    <div class="truck-marker"><i class="fa fa-truck"></i></div>
                    <div class="position-absolute bottom-0 start-0 p-3 w-100 bg-dark bg-opacity-75 text-white small">
                        <i class="fa fa-location-arrow me-2 text-warning"></i> Current Position: 23.1272° N, 83.1973° E | Speed: 42 km/h
                    </div>
                </div>
            </div>
        </div>

        <!-- Details & Timeline -->
        <div class="col-lg-4">
            <div class="glass-card p-4 mb-4">
                <h6 class="fw-bold text-slate-800 mb-4 border-bottom pb-3">Trip Progress</h6>
                <div class="status-timeline">
                    <div class="timeline-item">
                        <div class="fw-bold small">Order Initiated</div>
                        <div class="text-slate-500 small"><?php echo date('d M, H:i', strtotime($trip_data['created_at'])); ?></div>
                    </div>
                    <div class="timeline-item">
                        <div class="fw-bold small">Vehicle Assigned & Out for Delivery</div>
                        <div class="text-slate-500 small"><?php echo date('d M, H:i', strtotime($trip_data['dispatch_time'])); ?></div>
                    </div>
                    <div class="timeline-item active" style="opacity: 0.6;">
                        <div class="fw-bold small">In Transit</div>
                        <div class="text-slate-500 small">Estimated arrival in 1h 20m</div>
                    </div>
                </div>
            </div>

            <!-- Vehicle Summary -->
            <div class="glass-card p-4">
                <h6 class="fw-bold text-slate-800 mb-3">Vehicle Summary</h6>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-slate-500 small">Vehicle No</span>
                    <span class="fw-bold small"><?php echo $trip_data['vehicle_no']; ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-slate-500 small">Driver</span>
                    <span class="fw-bold small"><?php echo $trip_data['driver_name']; ?></span>
                </div>
                <div class="d-flex justify-content-between mb-4">
                    <span class="text-slate-500 small">Total Plants</span>
                    <span class="fw-bold small"><?php echo $trip_data['send_totalplantcount']; ?></span>
                </div>

                <a href="complete_dispatch.php?k=<?php echo $key; ?>&trip=<?php echo $trip_id; ?><?php echo (isset($_GET['iframe']) && $_GET['iframe'] == 'true') ? '&iframe=true' : ''; ?>" class="btn btn-success w-100 rounded-pill fw-bold">
                    <i class="fa fa-check-double me-2"></i>Mark as Delivered
                </a>
            </div>
        </div>
    </div>
</div>

<?php include('../footer.php'); ?>
