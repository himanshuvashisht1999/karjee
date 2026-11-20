<?php
include('../header.php');
date_default_timezone_set("Asia/Calcutta");

$type = $_GET['type'] ?? 'client';
$id = ($type == 'internal') ? ($_GET['stockorder'] ?? '') : ($_GET['pso'] ?? '');

$id_array = explode(',', $id);
$first_id = $id_array[0] ?? '';

// For internal transfers, parse stockorder and dates
$stockorder_dates = [];
$unique_stockorders = [];
if ($type == 'internal') {
    $stockorders_temp = [];
    foreach ($id_array as $item) {
        $parts = explode('|', $item);
        $so = $parts[0];
        $date = $parts[1] ?? '';
        $stockorders_temp[] = $so;
        if ($date !== '') {
            $stockorder_dates[$so][] = $date;
        }
    }
    $unique_stockorders = array_unique($stockorders_temp);
    $first_id_parsed = $unique_stockorders[0] ?? '';
} else {
    $first_id_parsed = $first_id;
    $unique_stockorders = $id_array;
}

if (!$id) {
    $back_url = ($type == 'internal') ? "internaldispatch.php" : "index.php";
    echo "<div class='container p-5 text-center'><h3>Invalid Request</h3><a href='{$back_url}?k=$key" . ((isset($_GET['iframe']) && $_GET['iframe'] == 'true') ? "&iframe=true" : "") . "' class='btn btn-primary'>Back to Dashboard</a></div>";
    include('../footer.php');
    exit;
}

$ref_label = ($type == 'internal') ? "Production Order" : "PSO";
// Check for existing assignment/trip for autofill (only load active, uncompleted trips to edit)
$existing_trip = null;
$existing_items = [];
$check_col = ($type == 'internal') ? 'stockorder_no' : 'psono';
$trip_check = $krfconn->query("SELECT * FROM krf_vehicle_travel_history WHERE $check_col = '$first_id_parsed' AND status NOT IN ('Delivered', 'Cancelled') ORDER BY id DESC LIMIT 1");
if ($trip_check && $trip_check->num_rows > 0) {
    $existing_trip = $trip_check->fetch_assoc();
    
    // Fetch all stockorders/psono associated with this trip to make sure we load all items in this trip
    $trip_id_check = $existing_trip['trip_id'] ?? '';
    if (!empty($trip_id_check)) {
        $trip_orders_res = $krfconn->query("SELECT DISTINCT $check_col FROM krf_vehicle_travel_history WHERE trip_id = '$trip_id_check' AND $check_col IS NOT NULL AND $check_col != ''");
        if ($trip_orders_res && $trip_orders_res->num_rows > 0) {
            $trip_orders = [];
            while ($to_row = $trip_orders_res->fetch_assoc()) {
                $trip_orders[] = $to_row[$check_col];
            }
            $unique_stockorders = array_unique(array_merge($unique_stockorders, $trip_orders));
        }
    }
    
    // Attempt to find driver_id if it's not in the table
    if (!isset($existing_trip['driver_id']) && isset($existing_trip['driver_name'])) {
        $d_name = $existing_trip['driver_name'];
        $d_lookup = $krfconn->query("SELECT driverid FROM krf_vehicle_driver_master WHERE drivername = '$d_name' LIMIT 1");
        if ($d_lookup && $d_lookup->num_rows > 0) {
            $existing_trip['driver_id'] = $d_lookup->fetch_assoc()['driverid'];
        }
    }

    // Check if documents are already generated
    $trip_id = $existing_trip['trip_id'] ?? '';
    $gp_exists = file_exists("../attachementfile/dispatch/gate_pass_$trip_id.pdf");
    $dn_exists = file_exists("../attachementfile/dispatch/delivery_note_$trip_id.pdf");

    // Fetch all items for this trip
    $items_check = $krfconn->query("SELECT * FROM krf_vehicle_travel_history WHERE trip_id = '$trip_id'");
    if ($items_check) {
        while ($item_row = $items_check->fetch_assoc()) {
            $key_str = ($type == 'internal') ? ($item_row['productid'] . '_' . $item_row['stockorder_no']) : ($item_row['productid'] . '_' . $item_row['psono']);
            $existing_items[$key_str] = $item_row;
        }
    }
}

$items = [];
$header_details = [];

if ($type == 'client') {
    // Fetch PSO details
    $pso_res = $krfconn->query("SELECT * FROM krf_provisional_sales_order WHERE psono = '$first_id_parsed'");
    $header_details = $pso_res ? $pso_res->fetch_assoc() : [];
    
    $total_trays = 0;
    $total_plants = 0;
    
    // Split client dispatches
    $escaped_ids = array_map(function($val) use ($krfconn) { return "'" . $krfconn->real_escape_string($val) . "'"; }, $unique_stockorders);
    $ids_str = implode(',', $escaped_ids);

    // Fetch items
    $items_res = $krfconn->query("SELECT * FROM krf_provisional_sales_order_items WHERE psono IN ($ids_str)");
    if ($items_res) {
        while($row = $items_res->fetch_assoc()) {
            $pid = $row['product_id'];
            $item_psono = $row['psono'];
            // Fetch actual trays count from polyhouse plant details if assigned
            $details_res = $krfconn->query("SELECT COUNT(*) as total_trays, SUM(qtypertray) as total_plants FROM krf_polyhouse_plant_details WHERE CONVERT(psono USING utf8mb4) COLLATE utf8mb4_unicode_ci = '$item_psono' AND CONVERT(productid USING utf8mb4) COLLATE utf8mb4_unicode_ci = '$pid'");
            $details = $details_res ? $details_res->fetch_assoc() : null;
            $po_trays = $details ? ($details['total_trays'] ?? 0) : 0;
            $po_plants = $details ? ($details['total_plants'] ?? 0) : 0;
            
            $exclude_sql = "";
            if ($existing_trip && isset($existing_trip['trip_id'])) {
                $exclude_sql = " AND trip_id != '" . $krfconn->real_escape_string($existing_trip['trip_id']) . "'";
            }
            
            $disp_res = $krfconn->query("SELECT SUM(send_traycount) as disp_trays, SUM(send_totalplantcount) as disp_plants 
                                         FROM krf_vehicle_travel_history 
                                         WHERE psono = '$item_psono' AND productid = '$pid'
                                           AND status IN ('Dispatched', 'In Transit', 'In-Transit', 'Delivered')
                                           $exclude_sql");
            $disp = $disp_res ? $disp_res->fetch_assoc() : null;
            $disp_trays = $disp ? ($disp['disp_trays'] ?? 0) : 0;
            $disp_plants = $disp ? ($disp['disp_plants'] ?? 0) : 0;
            
            $pending_trays = max(0, $po_trays - $disp_trays);
            $pending_plants = max(0, $row['quantity'] - $disp_plants);
            
            $total_trays += $pending_trays;
            $total_plants += $pending_plants;
            
            $qty_per_tray = 0;
            if ($po_trays > 0) {
                $qty_per_tray = round($po_plants / $po_trays, 2);
            } else {
                $fb_res = $krfconn->query("SELECT qtypertray FROM krf_polyhouse_plant_details WHERE productid = '$pid' LIMIT 1");
                if ($fb_res && $fb_res->num_rows > 0) {
                    $qty_per_tray = floatval($fb_res->fetch_assoc()['qtypertray']);
                }
            }

            $items[] = [
                'id' => $row['id'],
                'name' => $row['product_name'] . " (" . $item_psono . ")",
                'sku' => $row['product_sku'] ?? 'N/A',
                'qty' => $pending_plants,
                'trays' => $pending_trays,
                'productid' => $row['product_id'],
                'qty_per_tray' => $qty_per_tray,
                'psono' => $item_psono
            ];
        }
    }
} else {
    // Split internal stock orders
    $escaped_ids = array_map(function($val) use ($krfconn) { return "'" . $krfconn->real_escape_string($val) . "'"; }, $unique_stockorders);
    $ids_str = implode(',', $escaped_ids);
    
    // Fetch Production Order details
    $po_res = $krfconn->query("SELECT * FROM krf_production_stockorder WHERE stockorder_no IN ($ids_str)");
    
    $total_trays = 0;
    $total_plants = 0;
    
    if ($po_res) {
        while ($po_row = $po_res->fetch_assoc()) {
            $po_no = $po_row['stockorder_no'];
            
            $date_filter = "";
            if (isset($stockorder_dates[$po_no]) && !empty($stockorder_dates[$po_no])) {
                $escaped_dates = array_map(function($d) use ($krfconn) { return "'" . $krfconn->real_escape_string($d) . "'"; }, $stockorder_dates[$po_no]);
                $date_filter = " AND IFNULL(dispatchdate_internal, '') IN (" . implode(',', $escaped_dates) . ")";
            }
            
            // Fetch actual trays & plants count from plant details
            $details_res = $krfconn->query("SELECT COUNT(*) as total_trays, SUM(qtypertray) as total_plants FROM krf_polyhouse_plant_details WHERE CONVERT(stockorder_no USING utf8mb4) COLLATE utf8mb4_unicode_ci = '$po_no' $date_filter");
            $details = $details_res ? $details_res->fetch_assoc() : null;
            $po_trays = $details ? ($details['total_trays'] ?? 0) : 0;
            $po_plants = $details ? ($details['total_plants'] ?? 0) : 0;
            
            $exclude_sql = "";
            if ($existing_trip && isset($existing_trip['trip_id'])) {
                $exclude_sql = " AND trip_id != '" . $krfconn->real_escape_string($existing_trip['trip_id']) . "'";
            }
            
            $disp_res = $krfconn->query("SELECT SUM(send_traycount) as disp_trays, SUM(send_totalplantcount) as disp_plants 
                                         FROM krf_vehicle_travel_history 
                                         WHERE stockorder_no = '$po_no' AND productid = '{$po_row['productid']}'
                                           AND status IN ('Dispatched', 'In Transit', 'In-Transit', 'Delivered')
                                           $exclude_sql");
            $disp = $disp_res ? $disp_res->fetch_assoc() : null;
            $disp_trays = $disp ? ($disp['disp_trays'] ?? 0) : 0;
            $disp_plants = $disp ? ($disp['disp_plants'] ?? 0) : 0;
            
            $pending_trays = max(0, $po_trays - $disp_trays);
            $pending_plants = max(0, ($po_plants > 0 ? $po_plants : ($po_row['quantity'] ?? 0)) - $disp_plants);
            
            $total_trays += $pending_trays;
            $total_plants += $pending_plants;
            
            $qty_per_tray = 0;
            if ($po_trays > 0) {
                $qty_per_tray = round($po_plants / $po_trays, 2);
            } else {
                $fb_res = $krfconn->query("SELECT qtypertray FROM krf_polyhouse_plant_details WHERE stockorder_no = '$po_no' LIMIT 1");
                if ($fb_res && $fb_res->num_rows > 0) {
                    $qty_per_tray = floatval($fb_res->fetch_assoc()['qtypertray']);
                }
            }

            $items[] = [
                'id' => $po_row['id'],
                'name' => ($po_row['product_name'] ?? 'Production Batch') . " (" . $po_no . ")",
                'sku' => $po_row['tray_type'] ?? 'N/A',
                'qty' => $pending_plants,
                'trays' => $pending_trays,
                'productid' => $po_row['productid'],
                'stockorder_no' => $po_no,
                'qty_per_tray' => $qty_per_tray
            ];
        }
    }
}

// Fetch predefined freight charges if any
$predefined_freight = null;
if ($type == 'client') {
    $f_res = $krfconn->query("SELECT * FROM krf_freightcharges WHERE psono = '$id' LIMIT 1");
    if ($f_res && $f_res->num_rows > 0) {
        $predefined_freight = $f_res->fetch_assoc();
    }
}

// Fetch default source/destination details if not editing an existing trip
$source_lat = '';
$source_lng = '';
$source_location_id = '';
$source_location_name = '';

$dest_lat = '';
$dest_lng = '';
$dest_location_id = '';
$dest_location_name = '';

if ($type == 'internal') {
    $first_date_filter = "";
    if (isset($stockorder_dates[$first_id_parsed]) && !empty($stockorder_dates[$first_id_parsed])) {
        $escaped_dates = array_map(function($d) use ($krfconn) { return "'" . $krfconn->real_escape_string($d) . "'"; }, $stockorder_dates[$first_id_parsed]);
        $first_date_filter = " AND IFNULL(d.dispatchdate_internal, '') IN (" . implode(',', $escaped_dates) . ")";
    }
    $src_res = $krfconn->query("
        SELECT loc.LocationId, loc.LocationName, loc.OfficeLat, loc.OfficeLng 
        FROM krf_polyhouse_plant_details d
        JOIN krf_polyhouse_master ph ON d.polyhouseid = ph.polyhouseid
        JOIN hrmplus.hrm_AttendanceLocationMaster loc ON ph.locationid = loc.LocationId
        WHERE CONVERT(d.stockorder_no USING utf8mb4) COLLATE utf8mb4_unicode_ci = '$first_id_parsed'
        $first_date_filter
        LIMIT 1
    ");
    if ($src_res && $src_res->num_rows > 0) {
        $src_data = $src_res->fetch_assoc();
        $source_lat = $src_data['OfficeLat'];
        $source_lng = $src_data['OfficeLng'];
        $source_location_id = $src_data['LocationId'];
        $source_location_name = $src_data['LocationName'];
    }
} else {
    // For client dispatches, destination is from krf_deliveryaddress_location
    $dest_res = $krfconn->query("
        SELECT loc.LocationId, loc.LocationName, loc.OfficeLat, loc.OfficeLng 
        FROM krf_deliveryaddress_location dl
        JOIN hrmplus.hrm_AttendanceLocationMaster loc ON dl.locationid = loc.LocationId
        WHERE dl.psono = '$id'
        LIMIT 1
    ");
    if ($dest_res && $dest_res->num_rows > 0) {
        $dest_data = $dest_res->fetch_assoc();
        $dest_lat = $dest_data['OfficeLat'];
        $dest_lng = $dest_data['OfficeLng'];
        $dest_location_id = $dest_data['LocationId'];
        $dest_location_name = $dest_data['LocationName'];
    }

    // Source is the nursery/farm where plants are located
    $src_res = $krfconn->query("
        SELECT loc.LocationId, loc.LocationName, loc.OfficeLat, loc.OfficeLng 
        FROM krf_polyhouse_plant_details d
        JOIN krf_polyhouse_master ph ON d.polyhouseid = ph.polyhouseid
        JOIN hrmplus.hrm_AttendanceLocationMaster loc ON ph.locationid = loc.LocationId
        WHERE CONVERT(d.psono USING utf8mb4) COLLATE utf8mb4_unicode_ci = '$id'
        LIMIT 1
    ");
    if ($src_res && $src_res->num_rows > 0) {
        $src_data = $src_res->fetch_assoc();
        $source_lat = $src_data['OfficeLat'];
        $source_lng = $src_data['OfficeLng'];
        $source_location_id = $src_data['LocationId'];
        $source_location_name = $src_data['LocationName'];
    }
}

// Overwrite with existing trip values if editing
if ($existing_trip) {
    $source_location_id = $existing_trip['from_location_id'] ?? $source_location_id;
    $source_location_name = $existing_trip['from_location_name'] ?? $source_location_name;
    $source_lat = $existing_trip['from_lat'] ?? $source_lat;
    $source_lng = $existing_trip['from_lon'] ?? $source_lng;

    $dest_location_id = $existing_trip['to_location_id'] ?? $dest_location_id;
    $dest_location_name = $existing_trip['to_location_name'] ?? $dest_location_name;
    $dest_lat = $existing_trip['to_lat'] ?? $dest_lat;
    $dest_lng = $existing_trip['to_lon'] ?? $dest_lng;
}
?>

<style>
    :root {
        --dispatch-primary: #4f46e5;
        --dispatch-secondary: #7c3aed;
        --glass-bg: rgba(255, 255, 255, 0.9);
    }

    .container-assign {
        padding: 24px;
        background: #f8fafc;
        min-height: calc(100vh - 120px);
    }

    .glass-card {
        background: var(--glass-bg);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.5);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    .form-label {
        font-weight: 600;
        color: #475569;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }

    .form-control, .form-select {
        border-radius: 12px;
        padding: 12px 16px;
        border: 1px solid #e2e8f0;
        transition: all 0.2s;
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--dispatch-primary);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }

    .pso-badge {
        background: #e0f2fe;
        color: #0369a1;
        padding: 4px 12px;
        border-radius: 50px;
        font-weight: 700;
        font-size: 0.9rem;
    }

    .btn-save {
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 12px 30px;
        font-weight: 600;
        transition: all 0.2s;
        box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.4);
    }

    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.5);
        color: white;
    }

    .btn-save:disabled {
        background: #cbd5e1 !important;
        color: #94a3b8 !important;
        box-shadow: none !important;
        cursor: not-allowed;
        transform: none !important;
        pointer-events: none;
    }

    .item-row {
        background: white;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 10px;
        border: 1px solid #f1f5f9;
        transition: border-color 0.2s;
    }

    .item-row:hover {
        border-color: var(--dispatch-primary);
    }

    .type-badge {
        font-size: 0.7rem;
        text-transform: uppercase;
        padding: 2px 8px;
        border-radius: 4px;
        margin-left: 10px;
    }

    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: white;
        color: #475569;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 8px 16px;
        font-weight: 600;
        font-size: 0.875rem;
        transition: all 0.2s;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        text-decoration: none;
    }

    .btn-back:hover {
        background: #f8fafc;
        color: var(--dispatch-primary);
        border-color: #cbd5e1;
        text-decoration: none;
        transform: translateX(-2px);
    }
</style>

<div class="container-assign">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo ($type == 'internal') ? 'internaldispatch.php' : 'index.php'; ?>?k=<?php echo $key; ?><?php echo (isset($_GET['iframe']) && $_GET['iframe'] == 'true') ? '&iframe=true' : ''; ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active">Assignment</li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <h2 class="fw-bold text-slate-900 mb-0">Vehicle & Driver Assignment</h2>
                <a href="<?php echo ($type == 'internal') ? 'internaldispatch.php' : 'index.php'; ?>?k=<?php echo $key; ?><?php echo (isset($_GET['iframe']) && $_GET['iframe'] == 'true') ? '&iframe=true' : ''; ?>" class="btn-back">
                    <i class="fa fa-arrow-left"></i>
                    <span>Back to Dashboard</span>
                </a>
            </div>
            <?php if ($gp_exists || $dn_exists): ?>
                <div class="alert alert-warning border-0 shadow-sm rounded-16 d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2" style="background: #fffbeb; border: 1px solid #fef3c7;">
                    <div class="d-flex align-items-center">
                        <i class="fa fa-file-pdf fa-2x me-3 text-warning"></i>
                        <div>
                            <div class="fw-bold text-amber-900">Documents Already Generated</div>
                            <div class="small text-amber-700">The Gate Pass or Delivery Note has already been created for Trip <span class="fw-bold"><?php echo $trip_id; ?></span>. Updating these details may require re-generating the documents.</div>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <?php if ($gp_exists): ?>
                            <a href="../attachementfile/dispatch/gate_pass_<?php echo $trip_id; ?>.pdf" target="_blank" class="btn btn-sm btn-warning text-white rounded-pill px-3"><i class="fa fa-download me-1"></i> Download Gate Pass</a>
                        <?php endif; ?>
                        <?php if ($dn_exists): ?>
                            <a href="../attachementfile/dispatch/delivery_note_<?php echo $trip_id; ?>.pdf" target="_blank" class="btn btn-sm btn-warning text-white rounded-pill px-3"><i class="fa fa-download me-1"></i> Download Delivery Note</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            <p class="text-slate-500">Step 2: Assign resources for <?php echo $ref_label; ?> <span class="pso-badge"><?php echo htmlspecialchars(implode(', ', $unique_stockorders)); ?></span> 
            <?php if ($type == 'internal'): ?>
                <span class="type-badge bg-indigo-100 text-indigo-700">Internal Move</span>
            <?php else: ?>
                <span class="type-badge bg-blue-100 text-blue-700">Client Delivery</span>
            <?php endif; ?>
            </p>
        </div>
    </div>

    <form id="frm_assignment">
        <input type="hidden" name="ref_id" value="<?php echo $id; ?>">
        <input type="hidden" name="ref_type" value="<?php echo $type; ?>">
        <input type="hidden" name="projectid" value="<?php echo $projectid; ?>">
        <input type="hidden" name="action" value="save_assignment">
        <?php if ($existing_trip): ?>
            <input type="hidden" name="trip_id" value="<?php echo htmlspecialchars($existing_trip['trip_id']); ?>">
            <input type="hidden" name="is_edit" value="1">
        <?php endif; ?>
        <input type="hidden" id="source_lat" name="from_lat" value="<?php echo $source_lat; ?>">
        <input type="hidden" id="source_lng" name="from_lon" value="<?php echo $source_lng; ?>">
        <input type="hidden" id="from_location_name" name="from_location_name" value="<?php echo $source_location_name; ?>">
        <input type="hidden" id="distance_km" name="distance_km" value="<?php echo $existing_trip['distance_km'] ?? '0'; ?>">
        <input type="hidden" id="to_location_name" name="to_location_name" value="<?php echo $dest_location_name; ?>">
        <input type="hidden" id="to_lat" name="to_lat" value="<?php echo $dest_lat; ?>">
        <input type="hidden" id="to_lon" name="to_lon" value="<?php echo $dest_lng; ?>">
        <?php if ($type == 'client'): ?>
            <input type="hidden" id="to_location_id" name="to_location_id" value="<?php echo $dest_location_id; ?>">
        <?php endif; ?>
        <?php if (isset($existing_trip['trip_id'])): ?>
            <input type="hidden" name="trip_id" value="<?php echo $existing_trip['trip_id']; ?>">
        <?php endif; ?>

        <div class="row g-4">
            <!-- Resource Selection -->
            <div class="col-lg-5">
                <div class="glass-card p-4">
                    <h5 class="fw-bold mb-4 text-slate-800"><i class="fa fa-truck me-2 text-primary"></i>Resource Details</h5>
                    
                    <!-- Where From (Source Location) -->
                    <div class="mb-3">
                        <label class="form-label">Where From (Source Location)</label>
                        <select class="form-select form-control" name="from_location_id" id="from_location_id" required>
                            <option value="">-- Choose Source --</option>
                            <?php
                            $loc_res = $hrmconn->query("SELECT LocationId, LocationName, OfficeLat, OfficeLng FROM hrm_AttendanceLocationMaster WHERE ProjectID = '$projectid' AND LocationName IS NOT NULL AND LocationName != ''");
                            while ($loc = $loc_res->fetch_assoc()) {
                                $selected = '';
                                if (isset($existing_trip['from_location_id']) && $existing_trip['from_location_id'] !== '0' && $existing_trip['from_location_id'] !== '') {
                                    $selected = ($existing_trip['from_location_id'] == $loc['LocationId']) ? 'selected' : '';
                                } else {
                                    $selected = ($source_location_id == $loc['LocationId']) ? 'selected' : '';
                                }
                                echo "<option value='{$loc['LocationId']}' data-name='{$loc['LocationName']}' data-lat='{$loc['OfficeLat']}' data-lng='{$loc['OfficeLng']}' $selected>{$loc['LocationName']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Where to Move (Destination Location) -->
                    <?php if ($type == 'internal'): ?>
                    <div class="mb-3">
                        <label class="form-label">Where to Move (Destination Location)</label>
                        <select class="form-select form-control" name="to_location_id" id="to_location_id" required>
                            <option value="">-- Choose Destination --</option>
                            <?php
                            $loc_res = $hrmconn->query("SELECT LocationId, LocationName, OfficeLat, OfficeLng FROM hrm_AttendanceLocationMaster WHERE ProjectID = '$projectid' AND LocationName IS NOT NULL AND LocationName != ''");
                            while ($loc = $loc_res->fetch_assoc()) {
                                $selected = '';
                                if (isset($existing_trip['to_location_id']) && $existing_trip['to_location_id'] !== '0' && $existing_trip['to_location_id'] !== '') {
                                    $selected = ($existing_trip['to_location_id'] == $loc['LocationId']) ? 'selected' : '';
                                } else {
                                    $selected = ($dest_location_id == $loc['LocationId']) ? 'selected' : '';
                                }
                                echo "<option value='{$loc['LocationId']}' data-name='{$loc['LocationName']}' data-lat='{$loc['OfficeLat']}' data-lng='{$loc['OfficeLng']}' $selected>{$loc['LocationName']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div class="mb-4">
                        <label class="form-label">Dispatch Date & Time</label>
                        <input type="datetime-local" class="form-control" name="dispatch_time" value="<?php echo isset($existing_trip['dispatch_time']) ? date('Y-m-d\TH:i', strtotime($existing_trip['dispatch_time'])) : date('Y-m-d\TH:i'); ?>" required>
                    </div>
                    
                    <!-- Calculated Distance -->
                    <?php if ($type == 'internal'): ?>
                    <div class="mb-3" id="distance_info_container">
                        <span class="small text-slate-500">Calculated Distance: </span>
                        <span class="fw-bold text-slate-800" id="distance_helper"><?php echo isset($existing_trip['distance_km']) ? $existing_trip['distance_km'] . ' km' : '0 km'; ?></span>
                    </div>
                    <?php endif; ?>

                    <!-- Select Vehicle -->
                    <div class="mb-3">
                        <label class="form-label">Select Vehicle</label>
                        <select class="form-select form-control" name="vehicle_id" id="vehicle_id" required>
                            <option value="">-- Choose Vehicle --</option>
                            <?php
                            $v_res = $krfconn->query("SELECT * FROM krf_vehicle_master WHERE status = 'Active'");
                            while ($v = $v_res->fetch_assoc()) {
                                $selected = (isset($existing_trip['vehicle_id']) && $existing_trip['vehicle_id'] == $v['vehicleid']) ? 'selected' : '';
                                echo "<option value='{$v['vehicleid']}' data-no='{$v['vehicleno']}' $selected>{$v['vehiclename']} ({$v['vehicleno']})</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Vehicle Capacity Info Box -->
                    <div id="vehicle_capacity_container" class="mb-3 p-3 rounded-16 border d-none" style="background: rgba(79, 70, 229, 0.04); border-color: rgba(79, 70, 229, 0.15); backdrop-filter: blur(8px);">
                        <div class="row text-center g-0">
                            <div class="col-4 border-end">
                                <div class="x-small text-slate-500 text-uppercase fw-bold" style="font-size: 0.7rem;">Capacity</div>
                                <div class="fw-bold text-slate-800 mt-1" id="v_cap_display" style="font-size: 0.95rem;">0 / 0</div>
                                <div class="x-small text-slate-400" style="font-size: 0.65rem;">Trays / Plants</div>
                            </div>
                            <div class="col-4 border-end">
                                <div class="x-small text-slate-500 text-uppercase fw-bold" style="font-size: 0.7rem;">Assigned</div>
                                <div class="fw-bold text-warning mt-1" id="v_assigned_display" style="font-size: 0.95rem;">0 / 0</div>
                                <div class="x-small text-slate-400" style="font-size: 0.65rem;">Active Trips</div>
                            </div>
                            <div class="col-4">
                                <div class="x-small text-slate-500 text-uppercase fw-bold" style="font-size: 0.7rem;">Allocatable</div>
                                <div class="fw-bold text-success mt-1" id="v_allocatable_display" style="font-size: 0.95rem;">0 / 0</div>
                                <div class="x-small text-slate-400" style="font-size: 0.65rem;">Remaining</div>
                            </div>
                        </div>
                        <div id="v_capacity_warning" class="mt-2 text-danger fw-bold text-center d-none" style="font-size: 0.75rem;">
                            <i class="fa fa-exclamation-triangle me-1"></i> Loaded qty exceeds vehicle capacity!
                        </div>
                    </div>

                    <!-- Today's Trips for Vehicle Info Box -->
                    <div id="today_trips_container" class="mb-3 p-3 rounded-16 border d-none" style="background: rgba(14, 165, 233, 0.04); border-color: rgba(14, 165, 233, 0.15);">
                        <div class="fw-bold text-info mb-2 small"><i class="fa fa-route me-1"></i> Today's Assigned Trips:</div>
                        <div id="today_trips_list" style="font-size: 0.75rem;"></div>
                    </div>

                    <!-- Select Driver -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label mb-0">Select Driver</label>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-link p-0 text-primary" id="btn_add_driver" title="Add New Driver" style="text-decoration: none; font-size: 0.8rem; font-weight: 600;">
                                    <i class="fa fa-plus"></i> Add Driver
                                </button>
                                <button type="button" class="btn btn-sm btn-link p-0 text-secondary" id="btn_refresh_drivers" title="Refresh List" style="text-decoration: none; font-size: 0.8rem; font-weight: 600;">
                                    <i class="fa fa-refresh"></i> Refresh
                                </button>
                            </div>
                        </div>
                        <select class="form-select form-control" name="driver_id" id="driver_id" required>
                            <option value="">-- Choose Driver --</option>
                            <?php
                            $d_res = $krfconn->query("SELECT * FROM krf_vehicle_driver_master WHERE status = 'Active'");
                            while ($d = $d_res->fetch_assoc()) {
                                $selected = (isset($existing_trip['driver_id']) && $existing_trip['driver_id'] == $d['driverid']) ? 'selected' : '';
                                echo "<option value='{$d['driverid']}' data-name='{$d['drivername']}' data-mobile='{$d['drivermobileno']}' $selected>{$d['drivername']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    

                    

                    <div class="mb-3">
                        <label class="form-label">Purpose to Move</label>
                        <select class="form-select form-control" name="purpose_to_move" id="purpose_to_move" required>
                            <option value="">-- Choose Purpose --</option>
                            <?php
                            $purposes = ['Grafting', 'Shifting to Polyhouse', 'Hardning', 'Clip Removal', 'Ready to Sale'];
                            $selected_purpose = $existing_trip['purpose_to_move'] ?? '';
                            foreach ($purposes as $p) {
                                $sel = ($selected_purpose == $p) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($p) . "' $sel>" . htmlspecialchars($p) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Dispatch Remarks</label>
                        <textarea class="form-control" name="remarks" rows="2" placeholder="Enter dispatch remarks..."><?php 
                            if (isset($existing_trip['send_remark'])) {
                                echo htmlspecialchars($existing_trip['send_remark']);
                            }
                        ?></textarea>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Freight Amount (INR)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white">₹</span>
                                <input type="number" step="0.01" class="form-control freight-calc" name="freight_amount" id="freight_amount" 
                                    value="<?php echo ($existing_trip['freight_amount'] > 0) ? $existing_trip['freight_amount'] : ($predefined_freight['freight_amount'] ?? '0.00'); ?>" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Discount (%)</label>
                            <div class="input-group">
                                <input type="number" step="0.1" class="form-control freight-calc" name="freight_discountpercent" id="freight_discountpercent" 
                                    value="<?php echo ($existing_trip['freight_discountpercent'] > 0) ? $existing_trip['freight_discountpercent'] : ($predefined_freight['freight_discountpercent'] ?? '0.0'); ?>" placeholder="0.0">
                                <span class="input-group-text bg-white">%</span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Payable Freight Amount</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">₹</span>
                            <input type="number" step="0.01" class="form-control bg-light" name="payable_freightamount" id="payable_freightamount" 
                                value="<?php echo ($existing_trip['payable_freightamount'] > 0) ? $existing_trip['payable_freightamount'] : ($predefined_freight['payable_freightamount'] ?? '0.00'); ?>" readonly>
                        </div>
                    </div>

                    <div class="text-center pt-3">
                        <button type="submit" id="btn_submit_assignment" class="btn btn-save w-100">
                            <i class="fa fa-check-circle me-2"></i>Generate Trip & Continue
                        </button>
                    </div>
                </div>
            </div>

            <!-- Item Selection / Verification -->
            <div class="col-lg-7">
                <div class="glass-card p-4 h-100">
                    <h5 class="fw-bold mb-4 text-slate-800"><i class="fa fa-boxes me-2 text-primary"></i>Item Verification</h5>
                    <p class="small text-slate-500 mb-4">Confirm the quantities being loaded for this trip.</p>                    
                    <div id="items_container">
                        <?php
                        if (!empty($items)) {
                            foreach ($items as $item) {
                                ?>
                                <div class="item-row" data-qty-per-tray="<?php echo ($item['trays'] > 0) ? round($item['qty'] / $item['trays'], 2) : 0; ?>" data-max-trays="<?php echo $item['trays']; ?>" data-max-plants="<?php echo $item['qty']; ?>">
                                    <div class="row align-items-center">
                                        <div class="col-md-5">
                                            <div class="fw-bold"><?php echo $item['name']; ?></div>
                                            <div class="small text-muted">Ref: <?php echo $item['sku']; ?></div>
                                        </div>
                                        <div class="col-md-3 col-sm-6">
                                            <div class="small text-slate-400">Target / Available</div>
                                            <div class="fw-semibold text-slate-700"><?php echo $item['trays']; ?> Trays</div>
                                            <div class="fw-semibold text-indigo-600"><?php echo number_format($item['qty']); ?> Plants</div>
                                        </div>
                                        <div class="col-md-2 col-sm-3">
                                            <div class="small text-slate-400">Loading Trays</div>
                                            <input type="number" class="form-control form-control-sm loading-trays-input" name="items[<?php echo $item['id']; ?>][tray_count]" min="0" max="<?php echo $item['trays']; ?>" value="<?php 
                                                $item_key = ($type == 'internal') ? ($item['productid'] . '_' . ($item['stockorder_no'] ?? '')) : ($item['productid'] . '_' . ($item['psono'] ?? $id));
                                                $existing_item = $existing_items[$item_key] ?? null;
                                                if ($existing_item) {
                                                    echo $existing_item['send_traycount'];
                                                } else {
                                                    echo $item['trays']; 
                                                }
                                            ?>">
                                        </div>
                                        <div class="col-md-2 col-sm-3">
                                            <div class="small text-slate-400">Loading Plants</div>
                                            <input type="number" class="form-control form-control-sm loading-plants-input" name="items[<?php echo $item['id']; ?>][qty]" min="0" max="<?php echo $item['qty']; ?>" value="<?php 
                                                if ($existing_item) {
                                                    echo $existing_item['send_totalplantcount'];
                                                } else {
                                                    echo $item['qty']; 
                                                }
                                            ?>">
                                            <input type="hidden" name="items[<?php echo $item['id']; ?>][productid]" value="<?php echo $item['productid']; ?>">
                                            <?php if ($type == 'internal'): ?>
                                                <input type="hidden" name="items[<?php echo $item['id']; ?>][stockorder_no]" value="<?php echo htmlspecialchars($item['stockorder_no'] ?? ''); ?>">
                                            <?php else: ?>
                                                <input type="hidden" name="items[<?php echo $item['id']; ?>][psono]" value="<?php echo htmlspecialchars($item['psono'] ?? ''); ?>">
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                            ?>
                             <!-- Summary Total Loading Row -->
                             <div class="item-row bg-slate-50 border-top mt-3 pt-3 fw-bold" style="border-radius: 0 0 12px 12px; border-top: 2px dashed #cbd5e1 !important;">
                                 <div class="row align-items-center">
                                     <div class="col-md-5 text-end text-sm-start">
                                         <span class="text-uppercase small text-slate-500 fw-bold me-2">Total Loading Summary:</span>
                                     </div>
                                     <div class="col-md-3 col-sm-6">
                                         <div class="small text-slate-400">Total Target Available</div>
                                         <div class="fw-semibold text-slate-700"><?php echo $total_trays; ?> Trays</div>
                                         <div class="fw-semibold text-indigo-600"><?php echo number_format($total_plants); ?> Plants</div>
                                     </div>
                                     <div class="col-md-2 col-sm-3">
                                         <div class="small text-slate-400">Total Loading Trays</div>
                                         <span id="total_loading_trays" class="text-indigo-600 h5 mb-0 fw-bold">0</span> <span class="small text-slate-400">Trays</span>
                                     </div>
                                     <div class="col-md-2 col-sm-3">
                                         <div class="small text-slate-400">Total Loading Plants</div>
                                         <span id="total_loading_plants" class="text-indigo-600 h5 mb-0 fw-bold">0</span> <span class="small text-slate-400">Plants</span>
                                     </div>
                                 </div>
                             </div>
                             <?php
                        } else {
                            echo "<div class='text-center py-5 text-muted'>No items found for this record.</div>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Modal for Adding New Driver -->
<div class="modal fade" id="addDriverModal" tabindex="-1" role="dialog" aria-labelledby="addDriverModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
            <div class="modal-header bg-light" style="border-bottom: 1px solid #f1f5f9; border-top-left-radius: 16px; border-top-right-radius: 16px; padding: 16px 24px;">
                <h5 class="modal-title fw-bold text-slate-800" id="addDriverModalLabel">
                    <i class="fa fa-user-plus me-2 text-indigo-600"></i>Add New Driver Details
                </h5>
                <button type="button" class="close btn" data-dismiss="modal" aria-label="Close" style="font-size: 1.25rem; font-weight: 700; color: #94a3b8; outline: none; box-shadow: none;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="form_add_driver">
                <div class="modal-body" style="padding: 24px;">
                    <div class="mb-3">
                        <label for="modal_driver_name" class="form-label" style="font-weight: 600; color: #475569; font-size: 0.85rem; text-transform: uppercase;">Driver Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="modal_driver_name" name="drivername" required placeholder="Enter driver name" style="border-radius: 12px; padding: 10px 16px;">
                    </div>
                    <div class="mb-3">
                        <label for="modal_driver_mobile" class="form-label" style="font-weight: 600; color: #475569; font-size: 0.85rem; text-transform: uppercase;">Mobile Number</label>
                        <input type="text" class="form-control" id="modal_driver_mobile" name="drivermobileno" placeholder="Enter mobile number" style="border-radius: 12px; padding: 10px 16px;">
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #f1f5f9; padding: 16px 24px; display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" style="border-radius: 12px; padding: 8px 16px; font-weight: 600;">Close</button>
                    <button type="submit" class="btn btn-save" style="border-radius: 12px; padding: 8px 24px; font-weight: 600; background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: white; border: none; box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.4);">
                        <i class="fa fa-save me-1"></i> Save Driver
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include('../footer.php'); ?>

<script>
$(document).ready(function() {
    let allocatableTraysGlobal = 0;
    let allocatablePlantsGlobal = 0;
    let vehicleCapacityTraysGlobal = 0;
    let vehicleCapacityPlantsGlobal = 0;
    let activeTripIdGlobal = '';

    // Initial warning/total calculation
    checkCapacityWarning();

    // Auto-calculate payable amount
    $(document).on('input', '.freight-calc', function() {
        let amount = parseFloat($('#freight_amount').val()) || 0;
        let discount = parseFloat($('#freight_discountpercent').val()) || 0;
        let payable = amount - (amount * (discount / 100));
        $('#payable_freightamount').val(payable.toFixed(2));
    });

    // Calculate Haversine Distance
    function calculateHaversineDistance(lat1, lon1, lat2, lon2) {
        if (!lat1 || !lon1 || !lat2 || !lon2) return 0;
        const R = 6371; // Radius of the earth in km
        const dLat = deg2rad(lat2 - lat1);
        const dLon = deg2rad(lon2 - lon1);
        const a = 
            Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) * 
            Math.sin(dLon / 2) * Math.sin(dLon / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    }

    function deg2rad(deg) {
        return deg * (Math.PI / 180);
    }

    function updateDistanceAndFreight() {
        activeTripIdGlobal = '';
        let from_opt = $('#from_location_id option:selected');
        let from_name = from_opt.data('name') || '';
        let from_lat = parseFloat(from_opt.data('lat')) || 0;
        let from_lng = parseFloat(from_opt.data('lng')) || 0;
        
        $('#from_location_name').val(from_name);
        $('#source_lat').val(from_lat || '');
        $('#source_lng').val(from_lng || '');
        
        let to_lat = 0;
        let to_lng = 0;
        let to_location_id_val = $('#to_location_id').val();
        
        if ($('#to_location_id').is('select') && to_location_id_val) {
            let selectedOpt = $('#to_location_id option:selected');
            let to_name = selectedOpt.data('name') || '';
            to_lat = parseFloat(selectedOpt.data('lat')) || 0;
            to_lng = parseFloat(selectedOpt.data('lng')) || 0;
            
            $('#to_location_name').val(to_name);
            $('#to_lat').val(to_lat || '');
            $('#to_lon').val(to_lng || '');
        } else {
            to_lat = parseFloat($('#to_lat').val()) || 0;
            to_lng = parseFloat($('#to_lon').val()) || 0;
        }
        
        let distance = 0;
        if (from_lat && from_lng && to_lat && to_lng) {
            distance = calculateHaversineDistance(from_lat, from_lng, to_lat, to_lng);
            distance = Math.round(distance * 100) / 100; // round to 2 decimals
        }
        
        $('#distance_km').val(distance);
        $('#distance_helper').text(distance > 0 ? distance + ' km' : '0 km');
        
        let vehicle_id = $('#vehicle_id').val();
        if (!vehicle_id) {
            $('#vehicle_capacity_container').slideUp(300, function() {
                $(this).addClass('d-none');
            });
            $('#today_trips_container').slideUp(300, function() {
                $(this).addClass('d-none');
            });
            return;
        }

        // Fetch predefined/calculated freight
        $.ajax({
            url: 'ajax/handle_pso.php',
            type: 'POST',
            data: { 
                action: 'get_freight_details', 
                psono: '<?php echo $id; ?>',
                vehicle_id: vehicle_id,
                distance: distance,
                trip_id: '<?php echo $existing_trip['trip_id'] ?? ''; ?>'
            },
            dataType: 'json',
            success: function(res) {
                if (res.status == 'success' && res.data) {
                    $('#freight_amount').val(res.data.freight_amount);
                    $('#freight_discountpercent').val(res.data.freight_discountpercent);
                    $('#payable_freightamount').val(res.data.payable_freightamount);
                    
                    // Trigger calculation trigger
                    $('.freight-calc').first().trigger('input');

                    // Show vehicle capacity details
                    let cap_trays = parseInt(res.data.tray_capacity) || 0;
                    let cap_plants = parseInt(res.data.plant_capacity) || 0;
                    let assigned_trays = parseInt(res.data.active_trays) || 0;
                    let assigned_plants = parseInt(res.data.active_plants) || 0;
                    
                    let total_assigned_trays = parseInt(res.data.total_assigned_trays) || 0;
                    let total_assigned_plants = parseInt(res.data.total_assigned_plants) || 0;

                    vehicleCapacityTraysGlobal = cap_trays;
                    vehicleCapacityPlantsGlobal = cap_plants;
                    
                    allocatableTraysGlobal = Math.max(0, cap_trays - assigned_trays);
                    allocatablePlantsGlobal = Math.max(0, cap_plants - assigned_plants);

                    $('#v_cap_display').text(cap_trays + ' / ' + cap_plants);
                    $('#v_assigned_display').text(total_assigned_trays + ' / ' + total_assigned_plants);
                    
                    // Highlight remaining capacity
                    let remaining_trays = Math.max(0, cap_trays - total_assigned_trays);
                    let remaining_plants = Math.max(0, cap_plants - total_assigned_plants);
                    $('#v_allocatable_display').text(remaining_trays + ' / ' + remaining_plants);
                    
                    $('#vehicle_capacity_container').removeClass('d-none').hide().slideDown(300);
                    
                    // Handle Today's Trips
                    let trips_html = '';
                    let activeTripId = '';
                    if (res.data.today_trips && res.data.today_trips.length > 0) {
                        res.data.today_trips.forEach(function(t) {
                            let statusClass = 'text-warning';
                            if (t.status === 'Delivered') statusClass = 'text-success';
                            else if (t.status === 'Cancelled') statusClass = 'text-danger';
                            
                            if ((t.status === 'Dispatched' || t.status === 'In Transit' || t.status === 'In-Transit') && !activeTripId) {
                                activeTripId = t.trip_id;
                            }
                            
                            trips_html += `<div class="mb-2 pb-2 border-bottom">
                                <div class="d-flex justify-content-between font-weight-bold">
                                    <span>Trip: ${t.trip_id} <span class="badge badge-light border">${t.time}</span></span>
                                    <span class="${statusClass}">${t.status}</span>
                                </div>
                                <div class="text-muted" style="font-size: 0.7rem;">
                                    Route: ${t.from} &rarr; ${t.to} <br>
                                    Qty: ${t.trays} Trays (${parseInt(t.plants).toLocaleString()} Plants) | Driver: ${t.driver_name}
                                </div>
                            </div>`;
                        });
                        $('#today_trips_list').html(trips_html);
                        $('#today_trips_container').removeClass('d-none').hide().slideDown(300);
                        
                        // Auto-select driver if assigned for today
                        if (res.data.today_driver_id) {
                            $('#driver_id').val(res.data.today_driver_id).trigger('change');
                        }
                    } else {
                        $('#today_trips_container').slideUp(300, function() {
                            $(this).addClass('d-none');
                        });
                    }
                    activeTripIdGlobal = activeTripId;

                    // Check warnings
                    checkCapacityWarning();
                } else {
                    console.log('Freight lookup skip or no result:', res.message);
                }
            }
        });
    }

    function checkCapacityWarning() {
        let totalLoadedTrays = 0;
        let totalLoadedPlants = 0;
        
        $('.loading-trays-input').each(function() {
            totalLoadedTrays += parseInt($(this).val()) || 0;
        });
        $('.loading-plants-input').each(function() {
            totalLoadedPlants += parseInt($(this).val()) || 0;
        });
        
        // Update total loading display in verification card footer
        $('#total_loading_trays').text(totalLoadedTrays);
        $('#total_loading_plants').text(totalLoadedPlants.toLocaleString());
        
        let vehicle_selected = $('#vehicle_id').val();
        if (!vehicle_selected) {
            $('#v_capacity_warning').addClass('d-none');
            $('#btn_submit_assignment').prop('disabled', false);
            return;
        }

        let exceedsTrays = (vehicleCapacityTraysGlobal > 0 && totalLoadedTrays > allocatableTraysGlobal);
        let exceedsPlants = (vehicleCapacityPlantsGlobal > 0 && totalLoadedPlants > allocatablePlantsGlobal);

        if (exceedsTrays || exceedsPlants) {
            $('#v_capacity_warning').removeClass('d-none');
            $('#v_allocatable_display').removeClass('text-success').addClass('text-danger');
            $('#btn_submit_assignment').prop('disabled', true);
        } else {
            $('#v_capacity_warning').addClass('d-none');
            $('#v_allocatable_display').removeClass('text-danger').addClass('text-success');
            $('#btn_submit_assignment').prop('disabled', false);
        }
    }

    $(document).on('input', '.loading-trays-input', function() {
        let $row = $(this).closest('.item-row');
        let qtyPerTray = parseFloat($row.data('qty-per-tray')) || 0;
        let maxTrays = parseInt($row.data('max-trays')) || 0;
        let maxPlants = parseInt($row.data('max-plants')) || 0;
        
        let trayVal = parseInt($(this).val()) || 0;
        if (trayVal > maxTrays) {
            trayVal = maxTrays;
            $(this).val(trayVal);
        }
        if (trayVal < 0) {
            trayVal = 0;
            $(this).val(0);
        }

        if (qtyPerTray > 0) {
            let calculatedPlants = Math.round(trayVal * qtyPerTray);
            if (calculatedPlants > maxPlants) {
                calculatedPlants = maxPlants;
            }
            $row.find('.loading-plants-input').val(calculatedPlants);
        }
        checkCapacityWarning();
    });

    $(document).on('input', '.loading-plants-input', function() {
        let $row = $(this).closest('.item-row');
        let qtyPerTray = parseFloat($row.data('qty-per-tray')) || 0;
        let maxPlants = parseInt($row.data('max-plants')) || 0;
        let maxTrays = parseInt($row.data('max-trays')) || 0;

        let plantVal = parseInt($(this).val()) || 0;
        if (plantVal > maxPlants) {
            plantVal = maxPlants;
            $(this).val(plantVal);
        }
        if (plantVal < 0) {
            plantVal = 0;
            $(this).val(0);
        }

        if (qtyPerTray > 0) {
            let calculatedTrays = Math.round(plantVal / qtyPerTray);
            if (calculatedTrays > maxTrays) {
                calculatedTrays = maxTrays;
            }
            $row.find('.loading-trays-input').val(calculatedTrays);
        }
        checkCapacityWarning();
    });

    // Auto-fill freight and distance when vehicle, source, or destination changes
    $('#vehicle_id, #from_location_id, #to_location_id').on('change', function() {
        updateDistanceAndFreight();
    });

    // Run on load if vehicle is already selected
    if ($('#vehicle_id').val()) {
        updateDistanceAndFreight();
    }

    function submitAssignmentForm(formData) {
        $.ajax({
            url: 'ajax/handle_assignment.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                if (res.status == 'success') {
                    Swal.fire('Success!', 'Trip generated: ' + res.trip_id, 'success').then(() => {
                        window.location.href = 'dispatch_docs.php?k=<?php echo $key; ?>&trip=' + res.trip_id + '<?php echo (isset($_GET['iframe']) && $_GET['iframe'] == 'true') ? '&iframe=true' : ''; ?>';
                    });
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            }
        });
    }

    $('#frm_assignment').on('submit', function(e) {
        e.preventDefault();
        
        let formData = $(this).serialize();
        
        // Add extracted data for convenience
        let v_no = $('#vehicle_id option:selected').data('no');
        let d_name = $('#driver_id option:selected').data('name');
        let d_mobile = $('#driver_id option:selected').data('mobile');
        
        formData += '&vehicle_no=' + encodeURIComponent(v_no);
        formData += '&driver_name=' + encodeURIComponent(d_name);
        formData += '&driver_mobile=' + encodeURIComponent(d_mobile);

        let isEditMode = $('input[name="is_edit"]').val() === '1';

        if (isEditMode) {
            Swal.fire({
                title: 'Update Trip Details?',
                text: "This will save your updates for this trip.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#4f46e5',
                confirmButtonText: 'Confirm & Save'
            }).then((result) => {
                if (result.isConfirmed) {
                    submitAssignmentForm(formData);
                }
            });
        } else if (activeTripIdGlobal) {
            Swal.fire({
                title: 'Active Trip Detected',
                text: "An active, uncompleted trip (" + activeTripIdGlobal + ") is already generated for this vehicle today. Would you like to allocate these items to that existing trip, or generate a new trip ID?",
                icon: 'warning',
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonColor: '#4f46e5',
                denyButtonColor: '#10b981',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Allocate to Today\'s Trip',
                denyButtonText: 'Create New Trip',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    submitAssignmentForm(formData + '&trip_id=' + encodeURIComponent(activeTripIdGlobal));
                } else if (result.isDenied) {
                    submitAssignmentForm(formData);
                }
            });
        } else {
            Swal.fire({
                title: 'Create Trip?',
                text: "This will generate a unique Trip ID and transition the dispatch to 'Dispatched'.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#4f46e5',
                confirmButtonText: 'Confirm & Generate'
            }).then((result) => {
                if (result.isConfirmed) {
                    submitAssignmentForm(formData);
                }
            });
        }
    });

    // Trigger Modal Open
    $('#btn_add_driver').on('click', function() {
        $('#addDriverModal').modal('show');
    });

    // Add Driver form submission
    $('#form_add_driver').on('submit', function(e) {
        e.preventDefault();
        let drivername = $('#modal_driver_name').val().trim();
        let drivermobileno = $('#modal_driver_mobile').val().trim();
        let projectid = $('input[name="projectid"]').val() || 0;

        if (!drivername) {
            Swal.fire('Error', 'Driver name is required.', 'error');
            return;
        }

        $.ajax({
            url: 'ajax/handle_driver.php',
            type: 'POST',
            data: {
                action: 'add_driver',
                drivername: drivername,
                drivermobileno: drivermobileno,
                projectid: projectid
            },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success' && res.driver) {
                    Swal.fire('Saved!', 'Driver added successfully.', 'success');
                    
                    // Add new option to dropdown and select it
                    let opt = $('<option>')
                        .val(res.driver.driverid)
                        .text(res.driver.drivername)
                        .attr('data-name', res.driver.drivername)
                        .attr('data-mobile', res.driver.drivermobileno)
                        .prop('selected', true);
                    
                    $('#driver_id').append(opt).trigger('change');
                    
                    // Clear input form & Close Modal
                    $('#form_add_driver')[0].reset();
                    $('#addDriverModal').modal('hide');
                } else {
                    Swal.fire('Error', res.message || 'Failed to save driver.', 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Something went wrong.', 'error');
            }
        });
    });

    // Refresh Driver list
    $('#btn_refresh_drivers').on('click', function() {
        let $btn = $(this);
        $btn.prop('disabled', true).find('i').addClass('fa-spin');
        
        let selectedVal = $('#driver_id').val();

        $.ajax({
            url: 'ajax/handle_driver.php',
            type: 'POST',
            data: { action: 'get_active_drivers' },
            dataType: 'json',
            success: function(res) {
                $btn.prop('disabled', false).find('i').removeClass('fa-spin');
                if (res.status === 'success' && res.drivers) {
                    let currentSelect = $('#driver_id');
                    currentSelect.empty();
                    currentSelect.append('<option value="">-- Choose Driver --</option>');
                    
                    res.drivers.forEach(function(d) {
                        let selectedAttr = (d.driverid == selectedVal) ? 'selected' : '';
                        currentSelect.append(`<option value="${d.driverid}" data-name="${d.drivername}" data-mobile="${d.drivermobileno}" ${selectedAttr}>${d.drivername}</option>`);
                    });
                    
                    currentSelect.trigger('change');
                    
                    Swal.fire({
                        title: 'Refreshed!',
                        text: 'Driver list updated successfully.',
                        icon: 'success',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000
                    });
                } else {
                    Swal.fire('Error', 'Failed to reload driver list.', 'error');
                }
            },
            error: function() {
                $btn.prop('disabled', false).find('i').removeClass('fa-spin');
                Swal.fire('Error', 'Something went wrong.', 'error');
            }
        });
    });
});
</script>
