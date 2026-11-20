<?php
include('../../connection.php');
include('../../functions/function_master.php');

$action = $_POST['action'] ?? '';

if ($action == 'fetch_dispatch_list') {
    $mode = $_POST['mode'] ?? 'client';
    $key = $_POST['k'] ?? $_GET['k'] ?? '';
    $html = '';

    if ($mode == 'client') {
        $filter = $_POST['filter'] ?? 'pending';
        if ($filter == 'pending') {
            // Fetch confirmed PSOs for customers that have remaining items to dispatch and have ReadytoSale items
            $query = "SELECT p.*, 
                      (SELECT COUNT(*) FROM krf_provisional_sales_order_items WHERE psono = p.psono) as item_count,
                      (SELECT trip_id FROM krf_vehicle_travel_history WHERE psono = CONVERT(p.psono USING utf8mb4) ORDER BY id DESC LIMIT 1) as existing_trip 
                      FROM krf_provisional_sales_order p 
                      LEFT JOIN (
                          SELECT psono, SUM(send_totalplantcount) as disp_plants
                          FROM krf_vehicle_travel_history
                          WHERE status IN ('Dispatched', 'In Transit', 'In-Transit', 'Delivered')
                          GROUP BY psono
                      ) disp ON CONVERT(p.psono USING utf8mb4) = disp.psono
                      WHERE p.status = 'Confirmed'
                        AND EXISTS (
                            SELECT 1 FROM krf_polyhouse_plant_details pd
                            WHERE CONVERT(pd.psono USING utf8mb4) COLLATE utf8mb4_unicode_ci = p.psono
                              AND (LOWER(pd.status) = 'readytosale' OR LOWER(pd.workingstatus) = 'readytosale')
                        )
                        AND (
                            disp.disp_plants IS NULL OR disp.disp_plants < (
                                SELECT SUM(quantity) FROM krf_provisional_sales_order_items WHERE psono = p.psono
                            )
                        )
                      ORDER BY p.expecteddelivery ASC";
            
            $result = $krfconn->query($query);

            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $html .= '<tr>';
                    $html .= '<td><span class="fw-bold text-primary">' . $row['psono'] . '</span></td>';
                    $html .= '<td>' . htmlspecialchars($row['clientname']) . '</td>';
                    $html .= '<td><span class="badge bg-slate-100 text-slate-600">' . $row['item_count'] . ' Items</span></td>';
                    $html .= '<td>' . (!empty($row['expecteddelivery']) ? date('d M, Y', strtotime($row['expecteddelivery'])) : 'N/A') . '</td>';
                    $html .= '<td><span class="badge-status badge-confirmed">Confirmed</span></td>';
                    $actionBtn = '<button class="btn-initiate" onclick="initiateDispatch(\'' . $row['psono'] . '\', \'client\')"><i class="fa fa-paper-plane me-1"></i> Initiate</button>';
                    if (!empty($row['existing_trip'])) {
                        $actionBtn = '<button class="btn-initiate" style="background: #10b981;" onclick="initiateDispatch(\'' . $row['psono'] . '\', \'client\', true)">
                                        <i class="fa fa-edit me-1"></i> View Dispatch
                                      </button>';
                    }

                    $html .= '<td class="text-center">' . $actionBtn . '</td>';
                    $html .= '</tr>';
                }
            } else {
                $html = '<tr><td colspan="6" class="text-center py-4 text-muted">No pending customer orders found.</td></tr>';
            }
        } elseif ($filter == 'transit') {
            // Fetch In-Transit Trips for Customers
            $query = "SELECT t.trip_id, t.psono, t.vehicle_no, t.driver_name, t.from_location_name, t.to_location_name, t.dispatch_time, t.status,
                             SUM(t.send_traycount) as total_trays, SUM(t.send_totalplantcount) as total_plants,
                             p.clientname,
                             GROUP_CONCAT(DISTINCT pi.product_name SEPARATOR ', ') as product_names
                      FROM krf_vehicle_travel_history t
                      LEFT JOIN krf_provisional_sales_order p ON CONVERT(t.psono USING utf8mb4) = p.psono
                      LEFT JOIN krf_provisional_sales_order_items pi ON CONVERT(t.psono USING utf8mb4) = pi.psono AND CONVERT(t.productid USING utf8mb4) = pi.product_id
                      WHERE t.psono IS NOT NULL AND t.psono != ''
                        AND t.status IN ('Dispatched', 'In Transit', 'In-Transit')
                      GROUP BY t.trip_id
                      ORDER BY t.created_at DESC";
            
            $result = $krfconn->query($query);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $qtyText = $row['total_trays'] . ' Trays (' . number_format($row['total_plants']) . ' Plants)';
                    $route = htmlspecialchars($row['from_location_name'] ?? 'N/A') . ' &rarr; ' . htmlspecialchars($row['to_location_name'] ?? 'N/A');
                    
                    $actionBtn = '<a href="complete_dispatch.php?k=' . $key . '&trip=' . $row['trip_id'] . (isset($_REQUEST['iframe']) && $_REQUEST['iframe'] == 'true' ? '&iframe=true' : '') . '" class="btn btn-sm btn-outline-primary rounded-pill me-2 px-3"><i class="fa fa-check-circle me-1"></i> Complete</a>';
                    $actionBtn .= '<button class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="initiateDispatch(\'' . $row['psono'] . '\', \'client\', true)"><i class="fa fa-edit me-1"></i> Edit/View</button>';
                    
                    $html .= '<tr>';
                    $html .= '<td class="text-center">' . $actionBtn . '</td>';
                    $html .= '<td><span class="badge bg-purple-100 text-purple-800 fw-bold">' . $row['trip_id'] . '</span></td>';
                    $html .= '<td>' . htmlspecialchars($row['clientname'] ?? 'Unknown Client') . '</td>';
                    $html .= '<td><span class="badge bg-indigo-50 text-indigo-600">' . $qtyText . '</span></td>';
                    $html .= '<td><span class="small fw-semibold text-slate-600">' . $route . '</span></td>';
                    $html .= '<td>' . date('d M Y, H:i', strtotime($row['dispatch_time'])) . '</td>';
                    $html .= '</tr>';
                }
            } else {
                $html = '<tr><td colspan="6" class="text-center py-4 text-muted">No in-transit customer orders found.</td></tr>';
            }
        } elseif ($filter == 'completed') {
            // Fetch Completed Trips for Customers
            $query = "SELECT t.trip_id, t.psono, t.vehicle_no, t.driver_name, t.from_location_name, t.to_location_name, t.delivered_time, t.status,
                             SUM(t.send_traycount) as total_trays, SUM(t.send_totalplantcount) as total_plants,
                             p.clientname,
                             GROUP_CONCAT(DISTINCT pi.product_name SEPARATOR ', ') as product_names
                      FROM krf_vehicle_travel_history t
                      LEFT JOIN krf_provisional_sales_order p ON CONVERT(t.psono USING utf8mb4) = p.psono
                      LEFT JOIN krf_provisional_sales_order_items pi ON CONVERT(t.psono USING utf8mb4) = pi.psono AND CONVERT(t.productid USING utf8mb4) = pi.product_id
                      WHERE t.psono IS NOT NULL AND t.psono != ''
                        AND t.status = 'Delivered'
                      GROUP BY t.trip_id
                      ORDER BY t.delivered_time DESC";
            
            $result = $krfconn->query($query);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $qtyText = $row['total_trays'] . ' Trays (' . number_format($row['total_plants']) . ' Plants)';
                    $route = htmlspecialchars($row['from_location_name'] ?? 'N/A') . ' &rarr; ' . htmlspecialchars($row['to_location_name'] ?? 'N/A');
                    
                    $actionBtn = '<a href="dispatch_docs.php?k=' . $key . '&trip=' . $row['trip_id'] . (isset($_REQUEST['iframe']) && $_REQUEST['iframe'] == 'true' ? '&iframe=true' : '') . '" class="btn btn-sm btn-outline-success rounded-pill px-3"><i class="fa fa-file-pdf me-1"></i> View Docs</a>';
                    
                    $html .= '<tr>';
                    $html .= '<td class="text-center">' . $actionBtn . '</td>';
                    $html .= '<td><span class="badge bg-success-100 text-success-800 fw-bold">' . $row['trip_id'] . '</span></td>';
                    $html .= '<td>' . htmlspecialchars($row['clientname'] ?? 'Unknown Client') . '</td>';
                    $html .= '<td><span class="badge bg-indigo-50 text-indigo-600">' . $qtyText . '</span></td>';
                    $html .= '<td><span class="small fw-semibold text-slate-600">' . $route . '</span></td>';
                    $html .= '<td>' . date('d M Y, H:i', strtotime($row['delivered_time'])) . '</td>';
                    $html .= '</tr>';
                }
            } else {
                $html = '<tr><td colspan="6" class="text-center py-4 text-muted">No completed customer orders found.</td></tr>';
            }
        } elseif ($filter == 'total') {
            // Fetch MTD Trips (Total Dispatched list with status) for Customers
            $query = "SELECT t.trip_id, t.psono, t.vehicle_no, t.driver_name, t.from_location_name, t.to_location_name, t.dispatch_time, t.status,
                             SUM(t.send_traycount) as total_trays, SUM(t.send_totalplantcount) as total_plants,
                             p.clientname,
                             GROUP_CONCAT(DISTINCT pi.product_name SEPARATOR ', ') as product_names
                      FROM krf_vehicle_travel_history t
                      LEFT JOIN krf_provisional_sales_order p ON CONVERT(t.psono USING utf8mb4) = p.psono
                      LEFT JOIN krf_provisional_sales_order_items pi ON CONVERT(t.psono USING utf8mb4) = pi.psono AND CONVERT(t.productid USING utf8mb4) = pi.product_id
                      WHERE t.psono IS NOT NULL AND t.psono != ''
                        AND MONTH(t.created_at) = MONTH(CURDATE()) AND YEAR(t.created_at) = YEAR(CURDATE())
                      GROUP BY t.trip_id
                      ORDER BY t.created_at DESC";
            
            $result = $krfconn->query($query);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $qtyText = $row['total_trays'] . ' Trays (' . number_format($row['total_plants']) . ' Plants)';
                    $route = htmlspecialchars($row['from_location_name'] ?? 'N/A') . ' &rarr; ' . htmlspecialchars($row['to_location_name'] ?? 'N/A');
                    
                    $actionBtn = '';
                    if ($row['status'] == 'Delivered') {
                        $actionBtn = '<a href="dispatch_docs.php?k=' . $key . '&trip=' . $row['trip_id'] . (isset($_REQUEST['iframe']) && $_REQUEST['iframe'] == 'true' ? '&iframe=true' : '') . '" class="btn btn-sm btn-outline-success rounded-pill px-3"><i class="fa fa-file-pdf me-1"></i> View Docs</a>';
                    } else {
                        $actionBtn = '<a href="complete_dispatch.php?k=' . $key . '&trip=' . $row['trip_id'] . (isset($_REQUEST['iframe']) && $_REQUEST['iframe'] == 'true' ? '&iframe=true' : '') . '" class="btn btn-sm btn-outline-primary rounded-pill me-2 px-3"><i class="fa fa-check-circle me-1"></i> Complete</a>';
                        $actionBtn .= '<button class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="initiateDispatch(\'' . $row['psono'] . '\', \'client\', true)"><i class="fa fa-edit me-1"></i> Edit/View</button>';
                    }
                    
                    $statusClass = 'badge-confirmed';
                    if ($row['status'] == 'Delivered') {
                        $statusClass = 'badge-delivered';
                    } elseif (in_array($row['status'], ['Dispatched', 'In Transit', 'In-Transit'])) {
                        $statusClass = 'badge-transit';
                    }
                    
                    $html .= '<tr>';
                    $html .= '<td class="text-center">' . $actionBtn . '</td>';
                    $html .= '<td><span class="badge bg-purple-100 text-purple-800 fw-bold">' . $row['trip_id'] . '</span></td>';
                    $html .= '<td>' . htmlspecialchars($row['clientname'] ?? 'Unknown Client') . '</td>';
                    $html .= '<td><span class="badge bg-indigo-50 text-indigo-600">' . $qtyText . '</span></td>';
                    $html .= '<td><span class="small fw-semibold text-slate-600">' . $route . '</span></td>';
                    $html .= '<td><span class="badge-status ' . $statusClass . '">' . htmlspecialchars($row['status']) . '</span></td>';
                    $html .= '</tr>';
                }
            } else {
                $html = '<tr><td colspan="6" class="text-center py-4 text-muted">No trips found for this month.</td></tr>';
            }
        }
    } else {
        $filter = $_POST['filter'] ?? 'pending';

        if ($filter == 'pending') {
            // Fetch Location Names from HRM database to avoid cross-database join permissions error on production
            $locationMap = [];
            $loc_res = $hrmconn->query("SELECT LocationId, LocationName FROM hrm_AttendanceLocationMaster");
            if ($loc_res) {
                while ($loc_row = $loc_res->fetch_assoc()) {
                    $locationMap[$loc_row['LocationId']] = $loc_row['LocationName'];
                }
            }

            // Fetch Internal Movements (Production Orders)
            // Aggregating polyhouse names for location summary
            $query = "SELECT 
                        p.id,
                        p.stockorder_no,
                        d.productid,
                        d.roottype,
                        d.locationid,
                        d.polyhouseid,
                        p.companyid,
                        p.subcompanyid,
                        p.clientid,
                        p.status,
                        p.quantity,
                        d.dispatchdate_internal,
                        d.productname as seed_name,
                        COUNT(d.polyhouseplantid) as total_trays,
                        SUM(CAST(IFNULL(d.qtypertray, 0) AS UNSIGNED)) as total_plants,
                        COALESCE(NULLIF(NULLIF(d.locationid, '0'), ''), ph.locationid) as resolved_location_id,
                        ph.polyhousename,
                        (SELECT trip_id FROM krf_vehicle_travel_history WHERE stockorder_no = p.stockorder_no COLLATE utf8mb4_general_ci ORDER BY id DESC LIMIT 1) as existing_trip
                      FROM krf_production_stockorder p
                      JOIN krf_polyhouse_plant_details d ON CONVERT(d.stockorder_no USING utf8mb4) COLLATE utf8mb4_unicode_ci = p.stockorder_no
                      LEFT JOIN krf_polyhouse_master ph ON d.polyhouseid = ph.polyhouseid AND d.projectid = ph.projectid
                      WHERE p.status != 'Completed' AND p.status != 'Cancelled'
                      GROUP BY 
                          p.stockorder_no, 
                          d.productid, 
                          d.roottype, 
                          d.locationid, 
                          d.polyhouseid, 
                          d.dispatchdate_internal
                      ORDER BY d.dispatchdate_internal ASC";

            // Fetch total dispatched trays and plants per stockorder/product to deduct sequentially in PHP
            $disp_map = [];
            $disp_res = $krfconn->query("
                SELECT stockorder_no, productid, SUM(send_traycount) as disp_trays, SUM(send_totalplantcount) as disp_plants
                FROM krf_vehicle_travel_history
                WHERE status IN ('Dispatched', 'In Transit', 'In-Transit', 'Delivered')
                  AND stockorder_no IS NOT NULL AND stockorder_no != ''
                GROUP BY stockorder_no, productid
            ");
            if ($disp_res) {
                while ($disp_row = $disp_res->fetch_assoc()) {
                    $key_str = $disp_row['stockorder_no'] . '_' . $disp_row['productid'];
                    $disp_map[$key_str] = [
                        'trays' => (int)$disp_row['disp_trays'],
                        'plants' => (int)$disp_row['disp_plants']
                    ];
                }
            }
            
            $result = $krfconn->query($query);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $key_str = $row['stockorder_no'] . '_' . $row['productid'];
                    
                    $total_trays = (int)$row['total_trays'];
                    $total_plants = (int)$row['total_plants'];
                    
                    if (isset($disp_map[$key_str])) {
                        $disp_trays = $disp_map[$key_str]['trays'];
                        $disp_plants = $disp_map[$key_str]['plants'];
                        
                        $deduct_trays = min($total_trays, $disp_trays);
                        $deduct_plants = min($total_plants, $disp_plants);
                        
                        $total_trays -= $deduct_trays;
                        $total_plants -= $deduct_plants;
                        
                        $disp_map[$key_str]['trays'] -= $deduct_trays;
                        $disp_map[$key_str]['plants'] -= $deduct_plants;
                    }
                    
                    if ($total_trays <= 0) {
                        continue;
                    }
                    
                    $resolved_loc_id = $row['resolved_location_id'] ?? '';
                    $location_name = (!empty($resolved_loc_id) && isset($locationMap[$resolved_loc_id])) ? $locationMap[$resolved_loc_id] : 'Location Missing';
                    
                    $locations = ($location_name !== 'Location Missing') 
                        ? ($location_name . (!empty($row['polyhousename']) ? ' - ' . $row['polyhousename'] : '')) 
                        : (!empty($row['polyhousename']) ? $row['polyhousename'] : 'Not Assigned');
                    $html .= '<tr>';
                    $chkValue = $row['stockorder_no'] . '|' . ($row['dispatchdate_internal'] ?? '');
                    $actionBtn = '<div class="d-flex align-items-center justify-content-center gap-2">
                                    <input type="checkbox" class="order-select-chk" value="' . htmlspecialchars($chkValue) . '" data-existing="' . (!empty($row['existing_trip']) ? 'true' : 'false') . '" style="width: 16px; height: 16px; margin: 0; padding: 0; cursor: pointer; vertical-align: middle; accent-color: var(--dispatch-primary);">
                                    ' . (!empty($row['existing_trip']) ? 
                                        '<button class="btn btn-sm btn-outline-success px-2 py-1 rounded-pill ms-2" onclick="initiateDispatch(\'' . htmlspecialchars($chkValue) . '\', \'internal\', true)"><i class="fa fa-edit"></i> Edit</button>' : 
                                        '<span class="text-slate-400 small ms-2"></span>') . '
                                  </div>';

                    $html .= '<td class="text-center">' . $actionBtn . '</td>';
                    $html .= '<td><span class="fw-bold text-indigo-600">' . $row['stockorder_no'] . '</span></td>';
                    
                    $prodName = !empty($row['seed_name']) ? $row['seed_name'] : ($row['product_name'] ?? 'Stage Transition');
                    $html .= '<td>' . htmlspecialchars($prodName) . '</td>';
                    
                    $qtyText = $total_trays . ' Trays (' . number_format($total_plants) . ' Plants)';
                    $html .= '<td><span class="badge bg-indigo-50 text-indigo-600">' . $qtyText . '</span></td>';
                    
                    $displayDate = $row['dispatchdate_internal'] ?? '';
                    $html .= '<td>' . (!empty($displayDate) ? date('d M, Y', strtotime($displayDate)) : '<span class="text-danger small fw-bold">dispatchdate missing</span>') . '</td>';
                    $html .= '<td>
                                <a href="javascript:void(0)" class="location-badge" onclick="viewLocationDetails(\'' . $row['stockorder_no'] . '\')">
                                    <i class="fa fa-map-marker-alt me-1"></i> ' . $locations . '
                                </a>
                              </td>';
                    $html .= '</tr>';
                }
            } else {
                $html = '<tr><td colspan="6" class="text-center py-4 text-muted">No pending internal movements found.</td></tr>';
            }
        } elseif ($filter == 'transit') {
            // Fetch In-Transit Trips for Internal Moves
            $query = "SELECT t.trip_id, t.stockorder_no, t.vehicle_no, t.driver_name, t.from_location_name, t.to_location_name, t.dispatch_time, t.status,
                             SUM(t.send_traycount) as total_trays, SUM(t.send_totalplantcount) as total_plants,
                             GROUP_CONCAT(DISTINCT p.product_name SEPARATOR ', ') as product_names
                      FROM krf_vehicle_travel_history t
                      LEFT JOIN krf_production_stockorder p ON t.stockorder_no = p.stockorder_no COLLATE utf8mb4_general_ci
                      WHERE t.stockorder_no IS NOT NULL AND t.stockorder_no != ''
                        AND t.status IN ('Dispatched', 'In Transit', 'In-Transit')
                      GROUP BY t.trip_id
                      ORDER BY t.created_at DESC";
            
            $result = $krfconn->query($query);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $qtyText = $row['total_trays'] . ' Trays (' . number_format($row['total_plants']) . ' Plants)';
                    $route = htmlspecialchars($row['from_location_name'] ?? 'N/A') . ' &rarr; ' . htmlspecialchars($row['to_location_name'] ?? 'N/A');
                    
                    $actionBtn = '<a href="complete_dispatch.php?k=' . $key . '&trip=' . $row['trip_id'] . (isset($_REQUEST['iframe']) && $_REQUEST['iframe'] == 'true' ? '&iframe=true' : '') . '" class="btn btn-sm btn-outline-primary rounded-pill me-2 px-3"><i class="fa fa-check-circle me-1"></i> Complete</a>';
                    $actionBtn .= '<button class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="initiateDispatch(\'' . $row['stockorder_no'] . '\', \'internal\', true)"><i class="fa fa-edit me-1"></i> Edit/View</button>';
                    
                    $html .= '<tr>';
                    $html .= '<td class="text-center">' . $actionBtn . '</td>';
                    $html .= '<td><span class="badge bg-purple-100 text-purple-800 fw-bold">' . $row['trip_id'] . '</span></td>';
                    $html .= '<td>' . htmlspecialchars($row['vehicle_no']) . ' (' . htmlspecialchars($row['driver_name']) . ')</td>';
                    $html .= '<td><span class="badge bg-indigo-50 text-indigo-600">' . $qtyText . '</span></td>';
                    $html .= '<td><span class="small fw-semibold text-slate-600">' . $route . '</span></td>';
                    $html .= '<td>' . date('d M Y, H:i', strtotime($row['dispatch_time'])) . '</td>';
                    $html .= '</tr>';
                }
            } else {
                $html = '<tr><td colspan="6" class="text-center py-4 text-muted">No in-transit internal movements found.</td></tr>';
            }
        } elseif ($filter == 'completed') {
            // Fetch Completed Trips for Internal Moves
            $query = "SELECT t.trip_id, t.stockorder_no, t.vehicle_no, t.driver_name, t.from_location_name, t.to_location_name, t.delivered_time, t.status,
                             SUM(t.send_traycount) as total_trays, SUM(t.send_totalplantcount) as total_plants,
                             GROUP_CONCAT(DISTINCT p.product_name SEPARATOR ', ') as product_names
                      FROM krf_vehicle_travel_history t
                      LEFT JOIN krf_production_stockorder p ON t.stockorder_no = p.stockorder_no COLLATE utf8mb4_general_ci
                      WHERE t.stockorder_no IS NOT NULL AND t.stockorder_no != ''
                        AND t.status = 'Delivered'
                      GROUP BY t.trip_id
                      ORDER BY t.delivered_time DESC";
            
            $result = $krfconn->query($query);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $qtyText = $row['total_trays'] . ' Trays (' . number_format($row['total_plants']) . ' Plants)';
                    $route = htmlspecialchars($row['from_location_name'] ?? 'N/A') . ' &rarr; ' . htmlspecialchars($row['to_location_name'] ?? 'N/A');
                    
                    $actionBtn = '<a href="dispatch_docs.php?k=' . $key . '&trip=' . $row['trip_id'] . (isset($_REQUEST['iframe']) && $_REQUEST['iframe'] == 'true' ? '&iframe=true' : '') . '" class="btn btn-sm btn-outline-success rounded-pill px-3"><i class="fa fa-file-pdf me-1"></i> View Docs</a>';
                    
                    $html .= '<tr>';
                    $html .= '<td class="text-center">' . $actionBtn . '</td>';
                    $html .= '<td><span class="badge bg-success-100 text-success-800 fw-bold">' . $row['trip_id'] . '</span></td>';
                    $html .= '<td>' . htmlspecialchars($row['vehicle_no']) . ' (' . htmlspecialchars($row['driver_name']) . ')</td>';
                    $html .= '<td><span class="badge bg-indigo-50 text-indigo-600">' . $qtyText . '</span></td>';
                    $html .= '<td><span class="small fw-semibold text-slate-600">' . $route . '</span></td>';
                    $html .= '<td>' . date('d M Y, H:i', strtotime($row['delivered_time'])) . '</td>';
                    $html .= '</tr>';
                }
            } else {
                $html = '<tr><td colspan="6" class="text-center py-4 text-muted">No completed internal movements found.</td></tr>';
            }
        } elseif ($filter == 'total') {
            // Fetch MTD Trips (Total Dispatched list with status) for Internal Moves
            $query = "SELECT t.trip_id, t.stockorder_no, t.vehicle_no, t.driver_name, t.from_location_name, t.to_location_name, t.dispatch_time, t.status,
                             SUM(t.send_traycount) as total_trays, SUM(t.send_totalplantcount) as total_plants,
                             GROUP_CONCAT(DISTINCT p.product_name SEPARATOR ', ') as product_names
                      FROM krf_vehicle_travel_history t
                      LEFT JOIN krf_production_stockorder p ON t.stockorder_no = p.stockorder_no COLLATE utf8mb4_general_ci
                      WHERE t.stockorder_no IS NOT NULL AND t.stockorder_no != ''
                        AND MONTH(t.created_at) = MONTH(CURDATE()) AND YEAR(t.created_at) = YEAR(CURDATE())
                      GROUP BY t.trip_id
                      ORDER BY t.created_at DESC";
            
            $result = $krfconn->query($query);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $qtyText = $row['total_trays'] . ' Trays (' . number_format($row['total_plants']) . ' Plants)';
                    $route = htmlspecialchars($row['from_location_name'] ?? 'N/A') . ' &rarr; ' . htmlspecialchars($row['to_location_name'] ?? 'N/A');
                    
                    $actionBtn = '';
                    if ($row['status'] == 'Delivered') {
                        $actionBtn = '<a href="dispatch_docs.php?k=' . $key . '&trip=' . $row['trip_id'] . (isset($_REQUEST['iframe']) && $_REQUEST['iframe'] == 'true' ? '&iframe=true' : '') . '" class="btn btn-sm btn-outline-success rounded-pill px-3"><i class="fa fa-file-pdf me-1"></i> View Docs</a>';
                    } else {
                        $actionBtn = '<a href="complete_dispatch.php?k=' . $key . '&trip=' . $row['trip_id'] . (isset($_REQUEST['iframe']) && $_REQUEST['iframe'] == 'true' ? '&iframe=true' : '') . '" class="btn btn-sm btn-outline-primary rounded-pill me-2 px-3"><i class="fa fa-check-circle me-1"></i> Complete</a>';
                        $actionBtn .= '<button class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="initiateDispatch(\'' . $row['stockorder_no'] . '\', \'internal\', true)"><i class="fa fa-edit me-1"></i> Edit/View</button>';
                    }
                    
                    $statusClass = 'badge-confirmed';
                    if ($row['status'] == 'Delivered') {
                        $statusClass = 'badge-delivered';
                    } elseif (in_array($row['status'], ['Dispatched', 'In Transit', 'In-Transit'])) {
                        $statusClass = 'badge-transit';
                    }
                    
                    $html .= '<tr>';
                    $html .= '<td class="text-center">' . $actionBtn . '</td>';
                    $html .= '<td><span class="badge bg-purple-100 text-purple-800 fw-bold">' . $row['trip_id'] . '</span></td>';
                    $html .= '<td>' . htmlspecialchars($row['vehicle_no']) . ' (' . htmlspecialchars($row['driver_name']) . ')</td>';
                    $html .= '<td><span class="badge bg-indigo-50 text-indigo-600">' . $qtyText . '</span></td>';
                    $html .= '<td><span class="small fw-semibold text-slate-600">' . $route . '</span></td>';
                    $html .= '<td><span class="badge-status ' . $statusClass . '">' . htmlspecialchars($row['status']) . '</span></td>';
                    $html .= '</tr>';
                }
            } else {
                $html = '<tr><td colspan="6" class="text-center py-4 text-muted">No internal movements found for this month.</td></tr>';
            }
        }
    }
    echo $html;
}

if ($action == 'fetch_dashboard_stats') {
    $mode = $_POST['mode'] ?? 'client';
    $stats = [
        'pending' => 0,
        'transit' => 0,
        'delivered' => 0,
        'total_mtd' => 0
    ];

    if ($mode == 'internal') {
        // Pending production stock orders
        $disp_map = [];
        $disp_res = $krfconn->query("
            SELECT stockorder_no, productid, SUM(send_traycount) as disp_trays
            FROM krf_vehicle_travel_history
            WHERE status IN ('Dispatched', 'In Transit', 'In-Transit', 'Delivered')
              AND stockorder_no IS NOT NULL AND stockorder_no != ''
            GROUP BY stockorder_no, productid
        ");
        if ($disp_res) {
            while ($disp_row = $disp_res->fetch_assoc()) {
                $key_str = $disp_row['stockorder_no'] . '_' . $disp_row['productid'];
                $disp_map[$key_str] = (int)$disp_row['disp_trays'];
            }
        }

        $res_internal_groups = $krfconn->query("
            SELECT p.stockorder_no, d.productid, COUNT(d.polyhouseplantid) as total_trays
            FROM krf_production_stockorder p 
            JOIN krf_polyhouse_plant_details d ON CONVERT(d.stockorder_no USING utf8mb4) COLLATE utf8mb4_unicode_ci = p.stockorder_no
            WHERE p.status != 'Completed' AND p.status != 'Cancelled'
            GROUP BY 
                p.stockorder_no, 
                d.productid, 
                d.roottype, 
                d.locationid, 
                d.polyhouseid, 
                d.dispatchdate_internal
        ");
        
        $pending_count = 0;
        if ($res_internal_groups) {
            while ($row = $res_internal_groups->fetch_assoc()) {
                $key_str = $row['stockorder_no'] . '_' . $row['productid'];
                $total_trays = (int)$row['total_trays'];
                
                if (isset($disp_map[$key_str])) {
                    $disp_trays = $disp_map[$key_str];
                    $deduct = min($total_trays, $disp_trays);
                    $total_trays -= $deduct;
                    $disp_map[$key_str] -= $deduct;
                }
                
                if ($total_trays > 0) {
                    $pending_count++;
                }
            }
        }
        $stats['pending'] = $pending_count;

        // In-transit trips for stock orders
        $res_t = $krfconn->query("SELECT COUNT(DISTINCT trip_id) as cnt FROM krf_vehicle_travel_history WHERE status IN ('Dispatched', 'In Transit', 'In-Transit') AND stockorder_no IS NOT NULL AND stockorder_no != ''");
        if($res_t) $stats['transit'] = $res_t->fetch_assoc()['cnt'];

        // Completed internal trips today
        $res_d = $krfconn->query("SELECT COUNT(DISTINCT trip_id) as cnt FROM krf_vehicle_travel_history WHERE status = 'Delivered' AND DATE(delivered_time) = CURDATE() AND stockorder_no IS NOT NULL AND stockorder_no != ''");
        if($res_d) $stats['delivered'] = $res_d->fetch_assoc()['cnt'];

        // Total MTD internal trips
        $res_m = $krfconn->query("SELECT COUNT(DISTINCT trip_id) as cnt FROM krf_vehicle_travel_history WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) AND stockorder_no IS NOT NULL AND stockorder_no != ''");
        if($res_m) $stats['total_mtd'] = $res_m->fetch_assoc()['cnt'];
    } else {
        $res_p = $krfconn->query("
            SELECT COUNT(*) as cnt 
            FROM krf_provisional_sales_order p 
            LEFT JOIN (
                SELECT psono, SUM(send_totalplantcount) as disp_plants
                FROM krf_vehicle_travel_history
                WHERE status IN ('Dispatched', 'In Transit', 'In-Transit', 'Delivered')
                GROUP BY psono
            ) disp ON CONVERT(p.psono USING utf8mb4) = disp.psono
            WHERE p.status = 'Confirmed'
              AND EXISTS (
                  SELECT 1 FROM krf_polyhouse_plant_details pd
                  WHERE CONVERT(pd.psono USING utf8mb4) COLLATE utf8mb4_unicode_ci = p.psono
                    AND (LOWER(pd.status) = 'readytosale' OR LOWER(pd.workingstatus) = 'readytosale')
              )
              AND (
                  disp.disp_plants IS NULL OR disp.disp_plants < (
                      SELECT SUM(quantity) FROM krf_provisional_sales_order_items WHERE psono = p.psono
                  )
              )
        ");
        if($res_p) $stats['pending'] += $res_p->fetch_assoc()['cnt'];
        
        $disp_map = [];
        $disp_res = $krfconn->query("
            SELECT stockorder_no, productid, SUM(send_traycount) as disp_trays
            FROM krf_vehicle_travel_history
            WHERE status IN ('Dispatched', 'In Transit', 'In-Transit', 'Delivered')
              AND stockorder_no IS NOT NULL AND stockorder_no != ''
            GROUP BY stockorder_no, productid
        ");
        if ($disp_res) {
            while ($disp_row = $disp_res->fetch_assoc()) {
                $key_str = $disp_row['stockorder_no'] . '_' . $disp_row['productid'];
                $disp_map[$key_str] = (int)$disp_row['disp_trays'];
            }
        }

        $res_internal_groups = $krfconn->query("
            SELECT p.stockorder_no, d.productid, COUNT(d.polyhouseplantid) as total_trays
            FROM krf_production_stockorder p 
            JOIN krf_polyhouse_plant_details d ON CONVERT(d.stockorder_no USING utf8mb4) COLLATE utf8mb4_unicode_ci = p.stockorder_no
            WHERE p.status != 'Completed' AND p.status != 'Cancelled'
            GROUP BY 
                p.stockorder_no, 
                d.productid, 
                d.roottype, 
                d.locationid, 
                d.polyhouseid, 
                d.dispatchdate_internal
        ");
        
        $pending_count = 0;
        if ($res_internal_groups) {
            while ($row = $res_internal_groups->fetch_assoc()) {
                $key_str = $row['stockorder_no'] . '_' . $row['productid'];
                $total_trays = (int)$row['total_trays'];
                
                if (isset($disp_map[$key_str])) {
                    $disp_trays = $disp_map[$key_str];
                    $deduct = min($total_trays, $disp_trays);
                    $total_trays -= $deduct;
                    $disp_map[$key_str] -= $deduct;
                }
                
                if ($total_trays > 0) {
                    $pending_count++;
                }
            }
        }
        $stats['pending'] += $pending_count;

        $res_t = $krfconn->query("SELECT COUNT(DISTINCT trip_id) as cnt FROM krf_vehicle_travel_history WHERE status IN ('Dispatched', 'In Transit', 'In-Transit')");
        if($res_t) $stats['transit'] = $res_t->fetch_assoc()['cnt'];

        $res_d = $krfconn->query("SELECT COUNT(DISTINCT trip_id) as cnt FROM krf_vehicle_travel_history WHERE status = 'Delivered' AND DATE(delivered_time) = CURDATE()");
        if($res_d) $stats['delivered'] = $res_d->fetch_assoc()['cnt'];

        $res_m = $krfconn->query("SELECT COUNT(DISTINCT trip_id) as cnt FROM krf_vehicle_travel_history WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
        if($res_m) $stats['total_mtd'] = $res_m->fetch_assoc()['cnt'];
    }

    echo json_encode($stats);
}

if ($action == 'fetch_active_vehicles') {
    $query = "SELECT DISTINCT trip_id, vehicle_no, driver_name, status, dispatch_time 
              FROM krf_vehicle_travel_history 
              WHERE status IN ('Dispatched', 'In Transit', 'In-Transit') 
              ORDER BY created_at DESC 
              LIMIT 5";
    $result = $krfconn->query($query);
    $html = '';
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $html .= '<div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">';
            $html .= '  <div>';
            $html .= '    <div class="fw-bold text-slate-800 small">' . htmlspecialchars($row['vehicle_no']) . ' (' . htmlspecialchars($row['driver_name']) . ')</div>';
            $html .= '    <div class="text-slate-500 x-small">' . date('d M, H:i', strtotime($row['dispatch_time'])) . '</div>';
            $html .= '  </div>';
            $html .= '  <span class="badge bg-warning text-dark px-2 py-1" style="font-size: 0.65rem;">' . htmlspecialchars($row['status']) . '</span>';
            $html .= '</div>';
        }
    } else {
        $html = '<p class="text-muted small">No active vehicles on road.</p>';
    }
    echo $html;
    exit;
}

if ($action == 'fetch_order_location') {
    $stockorder_no = $_POST['stockorder_no'] ?? '';
    
    $query = "SELECT 
                COALESCE(NULLIF(ph.polyhousename, ''), 'Not Assigned') as polyhousename, 
                d.bed_no, 
                d.tray_position, 
                COUNT(d.polyhouseplantid) as tray_count, 
                SUM(CAST(IFNULL(d.qtypertray, 0) AS UNSIGNED)) as total_qty
              FROM krf_polyhouse_plant_details d 
              LEFT JOIN krf_polyhouse_master ph ON d.polyhouseid = ph.polyhouseid AND d.projectid = ph.projectid
              WHERE d.stockorder_no = '$stockorder_no' 
              GROUP BY COALESCE(NULLIF(ph.polyhousename, ''), 'Not Assigned'), d.bed_no, d.tray_position
              ORDER BY polyhousename, d.bed_no, d.tray_position";
    
    $res = $krfconn->query($query);
    $html = '<div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Polyhouse</th>
                            <th>Bed No</th>
                            <th>Position</th>
                            <th class="text-center">No. of Trays</th>
                            <th class="text-end">Total Plants</th>
                        </tr>
                    </thead>
                    <tbody>';
    
    $total_trays = 0;
    $total_plants = 0;
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $trays = (int)$row['tray_count'];
            $qty = (int)$row['total_qty'];
            $position = !empty($row['tray_position']) ? htmlspecialchars($row['tray_position']) : 'N/A';
            $html .= '<tr>
                        <td>' . htmlspecialchars($row['polyhousename']) . '</td>
                        <td>' . htmlspecialchars($row['bed_no']) . '</td>
                        <td><span class="badge bg-light text-dark border">' . $position . '</span></td>
                        <td class="text-center">' . $trays . '</td>
                        <td class="text-end fw-bold">' . number_format($qty) . '</td>
                      </tr>';
            $total_trays += $trays;
            $total_plants += $qty;
        }
        $html .= '<tr class="bg-light fw-bold">
                    <td colspan="3" class="text-end">Total Available:</td>
                    <td class="text-center">' . $total_trays . ' Trays</td>
                    <td class="text-end">' . number_format($total_plants) . ' Plants</td>
                  </tr>';
    } else {
        $html .= '<tr><td colspan="5" class="text-center text-muted">No location tracking data found for this order.</td></tr>';
    }
    
    $html .= '</tbody></table></div>';
    echo $html;
    exit;
}

if ($action == 'get_freight_details') {
    $psono = $_POST['psono'] ?? '';
    if (strpos($psono, '|') !== false) {
        $psono = explode('|', $psono)[0];
    }
    $vehicle_id = $_POST['vehicle_id'] ?? '';
    $current_trip_id = $_POST['trip_id'] ?? '';
    
    // Fetch vehicle capacities
    $tray_capacity = 0;
    $plant_capacity = 0;
    if ($vehicle_id) {
        $v_query = "SELECT traycapacity, plantscapacity FROM krf_vehicle_master WHERE vehicleid = '$vehicle_id' LIMIT 1";
        $v_res = $krfconn->query($v_query);
        if ($v_res && $v_res->num_rows > 0) {
            $v_row = $v_res->fetch_assoc();
            $tray_capacity = (int)($v_row['traycapacity'] ?? 0);
            $plant_capacity = (int)($v_row['plantscapacity'] ?? 0);
        }
    }
    
    // Calculate active assignments (excluding current trip if editing)
    $active_trays = 0;
    $active_plants = 0;
    if ($vehicle_id) {
        $exclude_sql = "";
        if (!empty($current_trip_id)) {
            $exclude_sql = " AND trip_id != '" . $krfconn->real_escape_string($current_trip_id) . "'";
        }
        $act_query = "SELECT SUM(send_traycount) as active_trays, SUM(send_totalplantcount) as active_plants 
                      FROM krf_vehicle_travel_history 
                      WHERE vehicle_id = '$vehicle_id' 
                        AND status IN ('Dispatched', 'In Transit', 'In-Transit') 
                        $exclude_sql";
        $act_res = $krfconn->query($act_query);
        if ($act_res && $act_res->num_rows > 0) {
            $act_row = $act_res->fetch_assoc();
            $active_trays = (int)($act_row['active_trays'] ?? 0);
            $active_plants = (int)($act_row['active_plants'] ?? 0);
        }
    }

    // Calculate current trip's saved quantities
    $this_trip_trays = 0;
    $this_trip_plants = 0;
    if ($vehicle_id && !empty($current_trip_id)) {
        $this_res = $krfconn->query("SELECT SUM(send_traycount) as this_trays, SUM(send_totalplantcount) as this_plants FROM krf_vehicle_travel_history WHERE trip_id = '" . $krfconn->real_escape_string($current_trip_id) . "'");
        if ($this_res && $this_res->num_rows > 0) {
            $this_row = $this_res->fetch_assoc();
            $this_trip_trays = (int)($this_row['this_trays'] ?? 0);
            $this_trip_plants = (int)($this_row['this_plants'] ?? 0);
        }
    }
    $total_assigned_trays = $active_trays + $this_trip_trays;
    $total_assigned_plants = $active_plants + $this_trip_plants;

    // 1. Check if there's a specific predefined freight in krf_freightcharges
    $f_query = "SELECT freight_amount, freight_discountpercent, payable_freightamount FROM krf_freightcharges WHERE psono = '$psono' LIMIT 1";
    $f_res = $krfconn->query($f_query);
    $predefined = ($f_res && $f_res->num_rows > 0) ? $f_res->fetch_assoc() : null;

    $distance = 0;
    $found = false;
    $amount = 0;
    $discount = 0;
    $payable = 0;
    
    // Prefer distance from POST
    if (isset($_POST['distance']) && $_POST['distance'] > 0) {
        $distance = floatval($_POST['distance']);
    }
    
    if ($predefined && $predefined['freight_amount'] > 0) {
        $amount = $predefined['freight_amount'];
        $discount = $predefined['freight_discountpercent'] ?? 0;
        $payable = $predefined['payable_freightamount'] ?? $amount;
        $found = true;
    } else {
        // Recalculate based on Vehicle + Distance if distance not passed in POST
        if ($distance <= 0) {
            if (strpos($psono, 'PRD') !== false || strpos($psono, 'PSO-KRF') !== false) {
                $d_query = "SELECT distance_km FROM krf_vehicle_travel_history WHERE stockorder_no = '$psono' ORDER BY id DESC LIMIT 1";
                $d_res = $krfconn->query($d_query);
                if ($d_res && $d_res->num_rows > 0) {
                    $distance = floatval($d_res->fetch_assoc()['distance_km'] ?? 0);
                }
            } else {
                $d_query = "SELECT distance FROM krf_deliveryaddress_location WHERE psono = '$psono' LIMIT 1";
                $d_res = $krfconn->query($d_query);
                if ($d_res && $d_res->num_rows > 0) {
                    $distance = floatval($d_res->fetch_assoc()['distance'] ?? 0);
                }
            }
        }
        
        $m_query = "SELECT freight_charge FROM krf_freight_master 
                  WHERE vehicleid = '$vehicle_id' 
                  AND '$distance' BETWEEN dist_min AND dist_max LIMIT 1";
        $m_res = $krfconn->query($m_query);
        if ($m_res && $m_res->num_rows > 0) {
            $amount = $m_res->fetch_assoc()['freight_charge'];
            $payable = $amount;
            $found = true;
        }
    }
    
    // Fetch today's trips and driver for this vehicle
    $today_trips = [];
    $today_driver_id = '';
    $today_driver_name = '';
    if ($vehicle_id) {
        $trips_res = $krfconn->query("
            SELECT trip_id, from_location_name, to_location_name, driver_name, status, send_traycount, send_totalplantcount, DATE_FORMAT(dispatch_time, '%H:%i') as d_time 
            FROM krf_vehicle_travel_history 
            WHERE vehicle_id = '$vehicle_id' 
              AND DATE(dispatch_time) = CURDATE()
            ORDER BY dispatch_time DESC
        ");
        if ($trips_res) {
            while ($t_row = $trips_res->fetch_assoc()) {
                $today_trips[] = [
                    'trip_id' => $t_row['trip_id'],
                    'from' => $t_row['from_location_name'],
                    'to' => $t_row['to_location_name'],
                    'driver_name' => $t_row['driver_name'],
                    'status' => $t_row['status'],
                    'trays' => $t_row['send_traycount'],
                    'plants' => $t_row['send_totalplantcount'],
                    'time' => $t_row['d_time']
                ];
                // Get the driver_id by looking up the driver name in krf_vehicle_driver_master
                if (empty($today_driver_id) && !empty($t_row['driver_name'])) {
                    $d_name = $krfconn->real_escape_string($t_row['driver_name']);
                    $d_lookup = $krfconn->query("SELECT driverid FROM krf_vehicle_driver_master WHERE drivername = '$d_name' LIMIT 1");
                    if ($d_lookup && $d_lookup->num_rows > 0) {
                        $today_driver_id = $d_lookup->fetch_assoc()['driverid'];
                    }
                    $today_driver_name = $t_row['driver_name'];
                }
            }
        }
    }

    // Always return success status to ensure vehicle capacities are received by the frontend
    echo json_encode([
        'status' => 'success', 
        'data' => [
            'freight_amount' => $amount,
            'freight_discountpercent' => $discount,
            'payable_freightamount' => $payable,
            'tray_capacity' => $tray_capacity,
            'plant_capacity' => $plant_capacity,
            'active_trays' => $active_trays,
            'active_plants' => $active_plants,
            'total_assigned_trays' => $total_assigned_trays,
            'total_assigned_plants' => $total_assigned_plants,
            'freight_found' => $found,
            'today_trips' => $today_trips,
            'today_driver_id' => $today_driver_id,
            'today_driver_name' => $today_driver_name
        ]
    ]);
}
?>
