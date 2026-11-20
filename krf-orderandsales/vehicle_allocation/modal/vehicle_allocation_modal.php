<?php
/**
 * Vehicle Allocation API Endpoint & Model Controller
 */
error_reporting(E_ALL);
require_once(__DIR__ . '/../../../connection.php');
if (isset($krfconn) && $krfconn) {
    @$krfconn->set_charset("utf8mb4");
}
require_once(__DIR__ . '/../../../loginCell/functions/login_functions.php');
require_once(__DIR__ . '/../../functions/vehicle_functions.php');
date_default_timezone_set("Asia/Calcutta");

/**
 * VehicleAllocationManager
 * Model Class for Vehicle Allocation & Dispatch Logistics:
 * - Order queries with production, vehicle, and delivery location joins
 * - Vehicle fleet master lookup
 * - Vehicle trip & travel history tracking (krf_vehicletrip, krf_vehicle_travel_history)
 * - Complete synchronization with krf_deliveryaddress_location
 */

class VehicleAllocationManager {
    private $db;
    private $authDb;

    public function __construct($db, $authDb = null) {
        $this->db = $db;
        $this->authDb = $authDb;
    }

    /**
     * Get active registered vehicles fleet for dropdown
     */
    public function getActiveVehicles($projectid = 0) {
        $sql = "SELECT vehicleid, projectid, vehicleno, vehiclename, vehicleownername, traycapacity, plantscapacity 
                FROM krf_vehicle_master 
                WHERE status = 'Active'";
        if ($projectid > 0) {
            $sql .= " AND (projectid = ? OR projectid = 0)";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $projectid);
            $stmt->execute();
            $res = $stmt->get_result();
        } else {
            $res = $this->db->query($sql);
        }
        $vehicles = [];
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $vehicles[] = $r;
            }
        }
        return $vehicles;
    }

    /**
     * Fetch unique delivery/dispatch dates from sales order items and stock orders
     */
    public function getActiveDates($projectid = 0) {
        $sql = "
            SELECT DISTINCT soi.deliverydate AS dispatch_date
            FROM krf_sales_order_items soi
            JOIN krf_sales_order so ON soi.sono = so.sono
            WHERE (soi.projectid = ? OR so.projectid = ?)
              AND soi.deliverydate IS NOT NULL 
              AND soi.deliverydate != '0000-00-00'
            UNION
            SELECT DISTINCT pso.expected_delivery_date AS dispatch_date
            FROM krf_production_stockorder pso
            WHERE pso.projectid = ?
              AND pso.expected_delivery_date IS NOT NULL 
              AND pso.expected_delivery_date != '0000-00-00'
            ORDER BY dispatch_date ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iii", $projectid, $projectid, $projectid);
        $stmt->execute();
        $res = $stmt->get_result();
        $dates = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                if (!empty($row['dispatch_date'])) {
                    $dates[] = ["dispatch_date" => $row['dispatch_date']];
                }
            }
        }
        return $dates;
    }

    /**
     * Get auto-complete suggestions for Vehicles, Drivers, and Transporters
     */
    public function getAllSuggestions($projectid = 0) {
        // 1. Registered Vehicles from krf_vehicle_master
        $vehicles = [];
        $vSql = "SELECT vehicleid, vehicleno, vehiclename, vehicleownername, plantscapacity, traycapacity 
                 FROM krf_vehicle_master 
                 WHERE (status = 'Active' OR status IS NULL) AND vehicleno IS NOT NULL AND vehicleno != ''
                 ORDER BY vehicleno ASC";
        $vRes = $this->db->query($vSql);
        if ($vRes) {
            while ($r = $vRes->fetch_assoc()) {
                $vehicles[] = [
                    'vehicleid' => intval($r['vehicleid']),
                    'vehicleno' => strtoupper(trim($r['vehicleno'])),
                    'vehiclename' => trim($r['vehiclename'] ?? ''),
                    'vehicleownername' => trim($r['vehicleownername'] ?? ''),
                    'plantscapacity' => intval($r['plantscapacity'] ?? 0),
                    'traycapacity' => intval($r['traycapacity'] ?? 0)
                ];
            }
        }

        // 2. Drivers from krf_vehicle_driver_master + recent trips
        $drivers = [];
        $seenDrivers = [];
        $dSql = "SELECT driverid, drivername, drivermobileno 
                 FROM krf_vehicle_driver_master 
                 WHERE (status = 'Active' OR status IS NULL) AND drivername IS NOT NULL AND drivername != ''
                 ORDER BY drivername ASC";
        $dRes = $this->db->query($dSql);
        if ($dRes) {
            while ($r = $dRes->fetch_assoc()) {
                $dName = trim($r['drivername']);
                $seenDrivers[strtolower($dName)] = true;
                $drivers[] = [
                    'driverid' => intval($r['driverid']),
                    'drivername' => $dName,
                    'drivermobileno' => trim($r['drivermobileno'] ?? '')
                ];
            }
        }

        $tripDrvSql = "SELECT DISTINCT driver_name, driver_mobile_no 
                       FROM krf_vehicletrip 
                       WHERE driver_name IS NOT NULL AND driver_name != '' 
                       ORDER BY trip_id DESC LIMIT 50";
        $tRes = $this->db->query($tripDrvSql);
        if ($tRes) {
            while ($r = $tRes->fetch_assoc()) {
                $dName = trim($r['driver_name']);
                if (!empty($dName) && !isset($seenDrivers[strtolower($dName)])) {
                    $seenDrivers[strtolower($dName)] = true;
                    $drivers[] = [
                        'driverid' => 0,
                        'drivername' => $dName,
                        'drivermobileno' => trim($r['driver_mobile_no'] ?? '')
                    ];
                }
            }
        }

        // 3. Transporters / Owners from krf_vehicle_vendor_master & krf_vehicle_master
        $transporters = [];
        $seenTransporters = [];
        $vVenSql = "SELECT vendor_name, company_name FROM krf_vehicle_vendor_master WHERE vendor_name IS NOT NULL AND vendor_name != ''";
        $vVenRes = $this->db->query($vVenSql);
        if ($vVenRes) {
            while ($r = $vVenRes->fetch_assoc()) {
                $vName = trim($r['company_name'] ?: $r['vendor_name']);
                if (!empty($vName) && !isset($seenTransporters[strtolower($vName)])) {
                    $seenTransporters[strtolower($vName)] = true;
                    $transporters[] = $vName;
                }
            }
        }
        $vOwnSql = "SELECT DISTINCT vehicleownername FROM krf_vehicle_master WHERE vehicleownername IS NOT NULL AND vehicleownername != ''";
        $vOwnRes = $this->db->query($vOwnSql);
        if ($vOwnRes) {
            while ($r = $vOwnRes->fetch_assoc()) {
                $oName = trim($r['vehicleownername']);
                if (!empty($oName) && !isset($seenTransporters[strtolower($oName)])) {
                    $seenTransporters[strtolower($oName)] = true;
                    $transporters[] = $oName;
                }
            }
        }
        sort($transporters);

        // 4. Vehicle Types from krf_vehicletype_master
        $vehicleTypes = [];
        $vtSql = "SELECT id, vehicletypename, plantscapacity, traycapacity 
                  FROM krf_vehicletype_master 
                  WHERE (status = 'Active' OR status IS NULL) 
                    AND vehicletypename IS NOT NULL AND vehicletypename != ''
                  ORDER BY vehicletypename ASC";
        $vtRes = $this->db->query($vtSql);
        if ($vtRes && $vtRes->num_rows > 0) {
            while ($r = $vtRes->fetch_assoc()) {
                $vehicleTypes[] = [
                    'id' => intval($r['id']),
                    'vehicletypename' => trim($r['vehicletypename']),
                    'vehiclename' => trim($r['vehicletypename']), // Maintain compatibility with frontend
                    'plantscapacity' => intval($r['plantscapacity'] ?? 0),
                    'traycapacity' => intval($r['traycapacity'] ?? 0)
                ];
            }
        }

        // Fallback: If krf_vehicletype_master has no records, fallback to krf_vehicle_master
        if (empty($vehicleTypes)) {
            $vtFallbackSql = "SELECT vehiclename, MAX(plantscapacity) AS plantscapacity, MAX(traycapacity) AS traycapacity 
                              FROM krf_vehicle_master 
                              WHERE (status = 'Active' OR status IS NULL) AND vehiclename IS NOT NULL AND vehiclename != ''
                              GROUP BY vehiclename
                              ORDER BY vehiclename ASC";
            $vtFallbackRes = $this->db->query($vtFallbackSql);
            if ($vtFallbackRes) {
                while ($r = $vtFallbackRes->fetch_assoc()) {
                    $vehicleTypes[] = [
                        'id' => 0,
                        'vehicletypename' => trim($r['vehiclename']),
                        'vehiclename' => trim($r['vehiclename']),
                        'plantscapacity' => intval($r['plantscapacity'] ?? 0),
                        'traycapacity' => intval($r['traycapacity'] ?? 0)
                    ];
                }
            }
        }

        return [
            'vehicle_types' => $vehicleTypes,
            'vehicles' => $vehicles,
            'drivers' => $drivers,
            'transporters' => $transporters
        ];
    }

    /**
     * Search Drivers from krf_vehicle_driver_master
     */
    public function searchDrivers($term, $projectid = 0) {
        $term = trim($term);
        $like = "%{$term}%";
        $stmt = $this->db->prepare("
            SELECT driverid, drivername, drivermobileno 
            FROM krf_vehicle_driver_master 
            WHERE (status = 'Active' OR status IS NULL)
              AND (drivername LIKE ? OR drivermobileno LIKE ?)
            ORDER BY drivername ASC 
            LIMIT 25
        ");
        $stmt->bind_param("ss", $like, $like);
        $stmt->execute();
        $res = $stmt->get_result();
        $drivers = [];
        while ($r = $res->fetch_assoc()) {
            $drivers[] = [
                'driverid' => intval($r['driverid']),
                'drivername' => trim($r['drivername']),
                'drivermobileno' => trim($r['drivermobileno'] ?? '')
            ];
        }
        $stmt->close();
        return $drivers;
    }

    /**
     * Insert Driver into krf_vehicle_driver_master if not existing, or update mobile
     */
    public function saveDriverQuick($drivername, $drivermobileno = '', $projectid = 0) {
        $drivername = trim($drivername);
        $drivermobileno = trim($drivermobileno);
        $projectid = intval($projectid);
        if (empty($drivername)) {
            return ['status' => 'error', 'message' => 'Driver name is required.'];
        }

        $chk = $this->db->prepare("SELECT driverid, drivermobileno FROM krf_vehicle_driver_master WHERE LOWER(TRIM(drivername)) = LOWER(TRIM(?)) LIMIT 1");
        $chk->bind_param("s", $drivername);
        $chk->execute();
        $row = $chk->get_result()->fetch_assoc();
        $chk->close();

        if (!$row) {
            $ins = $this->db->prepare("INSERT INTO krf_vehicle_driver_master (projectid, drivername, drivermobileno, status, created_at, updated_at) VALUES (?, ?, ?, 'Active', NOW(), NOW())");
            $ins->bind_param("iss", $projectid, $drivername, $drivermobileno);
            $ins->execute();
            $newId = $ins->insert_id;
            $ins->close();
            return [
                'status' => 'success',
                'action' => 'inserted',
                'driverid' => $newId,
                'drivername' => $drivername,
                'drivermobileno' => $drivermobileno
            ];
        } else {
            $dId = intval($row['driverid']);
            if (!empty($drivermobileno) && (empty($row['drivermobileno']) || $row['drivermobileno'] !== $drivermobileno)) {
                $upd = $this->db->prepare("UPDATE krf_vehicle_driver_master SET drivermobileno = ?, updated_at = NOW() WHERE driverid = ?");
                $upd->bind_param("si", $drivermobileno, $dId);
                $upd->execute();
                $upd->close();
            }
            return [
                'status' => 'success',
                'action' => 'exists',
                'driverid' => $dId,
                'drivername' => $drivername,
                'drivermobileno' => !empty($drivermobileno) ? $drivermobileno : trim($row['drivermobileno'] ?? '')
            ];
        }
    }

    /**
     * Quick save/insert new Vehicle into krf_vehicle_master if not existing
     * Strips -, /, and whitespace from registration number
     */
    public function saveVehicleQuick($vehicleno, $vehiclename = '', $vehicleownername = '', $plantscapacity = 0, $projectid = 0) {
        $cleanVehNo = preg_replace('/[\s\-\/]+/', '', strtoupper(trim($vehicleno)));
        $vehiclename = trim($vehiclename);
        $vehicleownername = trim($vehicleownername);
        $plantscapacity = intval($plantscapacity);
        $projectid = intval($projectid);

        if (empty($cleanVehNo)) {
            return ['status' => 'error', 'message' => 'Vehicle registration number is required.'];
        }

        $chk = $this->db->prepare("SELECT vehicleid, vehicleno, vehiclename, vehicleownername, plantscapacity FROM krf_vehicle_master WHERE REPLACE(REPLACE(REPLACE(UPPER(TRIM(vehicleno)), '-', ''), '/', ''), ' ', '') = ? LIMIT 1");
        $chk->bind_param("s", $cleanVehNo);
        $chk->execute();
        $row = $chk->get_result()->fetch_assoc();
        $chk->close();

        if (!$row) {
            $defName = !empty($vehiclename) ? $vehiclename : 'Vehicle';
            $ins = $this->db->prepare("
                INSERT INTO krf_vehicle_master (
                    projectid, vehicleno, vehiclename, vehicleownername, plantscapacity, status, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, 'Active', NOW(), NOW())
            ");
            $ins->bind_param("isssi", $projectid, $cleanVehNo, $defName, $vehicleownername, $plantscapacity);
            $ins->execute();
            $newId = $ins->insert_id;
            $ins->close();

            return [
                'status' => 'success',
                'action' => 'inserted',
                'vehicleid' => $newId,
                'vehicleno' => $cleanVehNo,
                'vehiclename' => $defName,
                'vehicleownername' => $vehicleownername,
                'plantscapacity' => $plantscapacity
            ];
        } else {
            $vId = intval($row['vehicleid']);
            $updates = [];
            $types = "";
            $vals = [];

            if (!empty($vehiclename) && empty($row['vehiclename'])) {
                $updates[] = "vehiclename = ?"; $types .= "s"; $vals[] = $vehiclename;
            }
            if (!empty($vehicleownername) && empty($row['vehicleownername'])) {
                $updates[] = "vehicleownername = ?"; $types .= "s"; $vals[] = $vehicleownername;
            }
            if ($plantscapacity > 0 && intval($row['plantscapacity']) <= 0) {
                $updates[] = "plantscapacity = ?"; $types .= "i"; $vals[] = $plantscapacity;
            }
            if (!empty($updates)) {
                $types .= "i"; $vals[] = $vId;
                $upd = $this->db->prepare("UPDATE krf_vehicle_master SET " . implode(", ", $updates) . ", updated_at = NOW() WHERE vehicleid = ?");
                $upd->bind_param($types, ...$vals);
                $upd->execute();
                $upd->close();
            }

            return [
                'status' => 'success',
                'action' => 'exists',
                'vehicleid' => $vId,
                'vehicleno' => $row['vehicleno'],
                'vehiclename' => !empty($row['vehiclename']) ? $row['vehiclename'] : $vehiclename,
                'vehicleownername' => !empty($row['vehicleownername']) ? $row['vehicleownername'] : $vehicleownername,
                'plantscapacity' => intval($row['plantscapacity'] ?: $plantscapacity)
            ];
        }
    }

    /**
     * Check Vehicle Capacity & Already Allocated Plants for a specific delivery date
     */
    public function checkVehicleDateCapacity($vehicleno, $deliverydate, $exclude_sono = '', $exclude_psono = '') {
        $vehicleno = preg_replace('/[\s\-\/]+/', '', strtoupper(trim($vehicleno)));
        if (empty($deliverydate) || $deliverydate === '0000-00-00') {
            $deliverydate = date('Y-m-d');
        }

        // 1. Vehicle info from master
        $vehStmt = $this->db->prepare("SELECT vehicleid, vehicleno, vehiclename, vehicleownername, plantscapacity, traycapacity FROM krf_vehicle_master WHERE REPLACE(REPLACE(REPLACE(UPPER(TRIM(vehicleno)), '-', ''), '/', ''), ' ', '') = ? LIMIT 1");
        $vehStmt->bind_param("s", $vehicleno);
        $vehStmt->execute();
        $vehRow = $vehStmt->get_result()->fetch_assoc();
        $vehStmt->close();

        $plantscapacity = intval($vehRow['plantscapacity'] ?? 0);
        $traycapacity = intval($vehRow['traycapacity'] ?? 0);

        // 2. Fetch all active (non-cancelled) trips for this vehicle on this delivery date from krf_vehicletrip
        $vtSql = "SELECT trip_id, trip_no, status, delivery_date, move_for 
                  FROM krf_vehicletrip 
                  WHERE UPPER(TRIM(vehicle_no)) = ? 
                    AND (delivery_date = ? OR move_for = ?)
                    AND (status IS NULL OR status NOT IN ('Cancelled'))";
        $vtStmt = $this->db->prepare($vtSql);
        $vtStmt->bind_param("sss", $vehicleno, $deliverydate, $deliverydate);
        $vtStmt->execute();
        $vtRes = $vtStmt->get_result();
        $tripKeys = [];
        $tripNos = [];
        while ($r = $vtRes->fetch_assoc()) {
            if (!empty($r['trip_id'])) $tripKeys[] = strval($r['trip_id']);
            if (!empty($r['trip_no'])) {
                $tripKeys[] = strval($r['trip_no']);
                $tripNos[] = strval($r['trip_no']);
            }
        }
        $vtStmt->close();

        // 3. Query krf_vehicle_travel_history matching the trips OR vehicle_no on that delivery date
        // Check send_totalplantcount to determine if vehicle is already scheduled and total scheduled plants
        $tripWhere = "";
        if (!empty($tripKeys)) {
            $escapedKeys = array_map([$this->db, 'real_escape_string'], array_unique($tripKeys));
            $tripWhere = " OR trip_id IN ('" . implode("','", $escapedKeys) . "')";
        }

        $hSql = "
            SELECT 
                IFNULL(SUM(send_totalplantcount), 0) AS total_plants_allocated,
                COUNT(id) AS allocated_orders_count,
                GROUP_CONCAT(DISTINCT COALESCE(NULLIF(TRIM(trip_id),''), '') SEPARATOR ', ') AS hist_trip_ids
            FROM krf_vehicle_travel_history
            WHERE (
                (UPPER(TRIM(vehicle_no)) = ? AND deliverydate = ?)
                {$tripWhere}
            )
            AND (status IS NULL OR status NOT IN ('Cancelled'))
        ";
        if (!empty($exclude_sono)) {
            $hSql .= " AND (stockorder_no != '" . $this->db->real_escape_string($exclude_sono) . "' OR stockorder_no IS NULL)";
        }
        if (!empty($exclude_psono)) {
            $hSql .= " AND (psono != '" . $this->db->real_escape_string($exclude_psono) . "' OR psono IS NULL)";
        }

        $hStmt = $this->db->prepare($hSql);
        $hStmt->bind_param("ss", $vehicleno, $deliverydate);
        $hStmt->execute();
        $hRow = $hStmt->get_result()->fetch_assoc();
        $hStmt->close();

        $alreadyAllocated = intval($hRow['total_plants_allocated'] ?? 0);
        $allocatedOrdersCount = intval($hRow['allocated_orders_count'] ?? 0);

        if (!empty($hRow['hist_trip_ids'])) {
            $histIds = explode(',', $hRow['hist_trip_ids']);
            foreach ($histIds as $hid) {
                $hid = trim($hid);
                if ($hid && !in_array($hid, $tripNos)) {
                    $tripNos[] = $hid;
                }
            }
        }

        $allTripNosStr = implode(', ', array_unique($tripNos));
        $isAlreadyScheduled = ($alreadyAllocated > 0);

        return [
            'status' => 'success',
            'vehicleno' => $vehicleno,
            'is_registered' => !empty($vehRow),
            'vehicleid' => intval($vehRow['vehicleid'] ?? 0),
            'vehiclename' => $vehRow['vehiclename'] ?? '',
            'vehicleownername' => $vehRow['vehicleownername'] ?? '',
            'plantscapacity' => $plantscapacity,
            'traycapacity' => $traycapacity,
            'deliverydate' => $deliverydate,
            'already_allocated' => $alreadyAllocated,
            'allocated_orders_count' => $allocatedOrdersCount,
            'trips_count' => count(array_unique($tripNos)),
            'trip_nos' => $allTripNosStr,
            'is_already_scheduled' => $isAlreadyScheduled
        ];
    }

    /**
     * Fetch orders list with vehicle allocation status & delivery details
     */
    public function getOrders($projectid = 0, $dateFrom = '', $dateTo = '', $filterStatus = 'all', $searchTerm = '') {
        $sql = "
            SELECT 
                so.id AS so_id,
                so.sono,
                so.psono,
                so.projectid,
                so.clientid,
                so.clientname,
                so.mobileno,
                so.delivery_address,
                so.districtname,
                so.statename,
                so.invoicetype,
                so.dinvoiceno,
                MIN(soi.deliverydate) AS deliverydate,
                COUNT(soi.id) AS item_count,
                SUM(COALESCE(poc.consumed_qty, soi.allocate_ordqty, soi.reservedqty, soi.quantity, 0)) AS total_order_qty,
                SUM(GREATEST(COALESCE(soi.dispatchedqty, 0), COALESCE(poc.dispatchedqty, 0))) AS total_dispatched_qty,
                SUM(
                    GREATEST(0, 
                        COALESCE(poc.consumed_qty, soi.allocate_ordqty, soi.reservedqty, soi.quantity, 0) 
                        - GREATEST(COALESCE(soi.dispatchedqty, 0), COALESCE(poc.dispatchedqty, 0))
                    )
                ) AS total_billable_qty,
                GROUP_CONCAT(DISTINCT soi.product_name SEPARATOR ' || ') AS product_names,
                GROUP_CONCAT(DISTINCT COALESCE(poc.stockorderno, soi.stockorder_no) SEPARATOR ', ') AS stock_order_nos,
                GROUP_CONCAT(
                    DISTINCT CONCAT_WS(':::',
                        soi.id,
                        soi.product_name,
                        COALESCE(poc.consumed_qty, soi.allocate_ordqty, soi.reservedqty, soi.quantity, 0),
                        GREATEST(COALESCE(soi.dispatchedqty, 0), COALESCE(poc.dispatchedqty, 0))
                    )
                    SEPARATOR ' ||| '
                ) AS product_details_concat,

                vth.id AS allocation_id,
                COALESCE(vth.vehicle_id, vt.vehicle_id, 0) AS vehicle_id,
                COALESCE(vth.vehicle_no, vt.vehicle_no, '') AS vehicleno,
                COALESCE(vm.vehiclename, '') AS vehiclename,
                COALESCE(vm.vehicleownername, '') AS vehicleownername,
                COALESCE(vth.driverid, vt.driver_id, '') AS driver_id,
                COALESCE(vth.driver_name, vt.driver_name, '') AS drivername,
                COALESCE(vth.driver_mobileno, vt.driver_mobile_no, '') AS drivermobileno,
                COALESCE(vth.deliverydate, vt.delivery_date) AS scheduled_dispatch_date,
                COALESCE(vm.plantscapacity, 0) AS plantscapacity,
                'vehicle_page' AS allocation_source,
                COALESCE(vt.status, 'Allocated') AS allocation_status,
                COALESCE(vth.send_remark, vt.remarks, '') AS vehicle_remarks,
                COALESCE(vth.created_by, vt.created_by, '') AS allocated_by,
                DATE_FORMAT(COALESCE(vth.created_at, vt.created_at), '%d-%b-%Y %h:%i %p') AS allocated_at_formatted,
                COALESCE(vt.trip_no, vth.trip_id, '') AS trip_id,

                dl.pickuptype AS dl_pickuptype,
                dl.deliveryaddress AS dl_deliveryaddress,
                dl.dest_state AS dl_dest_state,
                dl.dest_district AS dl_dest_district,
                dl.stateid AS dl_stateid,
                dl.districtid AS dl_districtid,
                dl.dest_city AS dl_dest_city,
                dl.dest_pincode AS dl_dest_pincode,
                dl.dest_latitude AS dl_dest_latitude,
                dl.dest_longitude AS dl_dest_longitude,
                dl.totaldistance AS dl_totaldistance,
                dl.freedistance AS dl_freedistance,
                dl.distance AS dl_distance,
                dl.freightamt AS dl_freightamt,
                dl.deliverycharges AS dl_deliverycharges,
                dl.pickuppoint AS dl_pickuppoint,
                dl.remark AS dl_remark,
                dl.boxperpack AS dl_boxperpack,
                dl.chargesperbox AS dl_chargesperbox,
                dl.noofbox AS dl_noofbox,
                dl.totalamount AS dl_totalamount,
                dl.vehicleno AS dl_vehicleno,
                dl.drivername AS dl_drivername,
                dl.drivercontactno AS dl_drivercontactno,

                fc.payable_freightamount AS fc_payable_freight,
                fc.freight_amount AS fc_base_freight,
                fc.freight_discountamount AS fc_discount_amount,
                fc.freight_discountpercent AS fc_discount_percent

            FROM krf_sales_order so
            JOIN krf_sales_order_items soi ON soi.sono = so.sono
            LEFT JOIN krf_productionorder_consumed poc ON poc.id = (
                SELECT poc2.id 
                FROM krf_productionorder_consumed poc2 
                WHERE (poc2.psono = soi.psono OR (poc2.sono = soi.sono AND soi.sono != ''))
                  AND poc2.product_id = soi.product_id
                ORDER BY 
                  CASE WHEN NULLIF(TRIM(soi.stockorder_no), '') IS NOT NULL AND poc2.stockorderno = soi.stockorder_no AND ROUND(poc2.consumed_qty, 0) = ROUND(soi.quantity, 0) THEN 1
                       WHEN NULLIF(TRIM(soi.stockorder_no), '') IS NOT NULL AND poc2.stockorderno = soi.stockorder_no THEN 2
                       WHEN ROUND(poc2.consumed_qty, 0) = ROUND(soi.quantity, 0) THEN 3
                       ELSE 4 END,
                  CASE WHEN poc2.status = 'allocated' THEN 1 WHEN poc2.status = 'dispatched' THEN 2 ELSE 3 END,
                  poc2.id DESC 
                LIMIT 1
            )
            LEFT JOIN krf_vehicle_travel_history vth ON vth.id = (
                SELECT vth2.id 
                FROM krf_vehicle_travel_history vth2 
                WHERE (
                    (COALESCE(vth2.stockorder_no, '') != '' AND (vth2.stockorder_no = so.sono OR vth2.stockorder_no = soi.stockorder_no)) OR 
                    (COALESCE(vth2.psono, '') != '' AND vth2.psono = so.psono)
                )
                ORDER BY vth2.id DESC 
                LIMIT 1
            )
            LEFT JOIN krf_vehicletrip vt ON (vt.trip_no = vth.trip_id OR vt.trip_id = vth.trip_id)
            LEFT JOIN krf_vehicle_master vm ON (vm.vehicleid = COALESCE(vth.vehicle_id, vt.vehicle_id) AND vm.vehicleid > 0)
            LEFT JOIN krf_deliveryaddress_location dl ON dl.id = (
                SELECT d2.id FROM krf_deliveryaddress_location d2 
                WHERE (d2.projectid = so.projectid OR d2.projectid = ?)
                  AND ((COALESCE(d2.psono,'') != '' AND d2.psono = so.psono) OR (COALESCE(d2.sono,'') != '' AND d2.sono = so.sono))
                ORDER BY d2.id DESC LIMIT 1
            )
            LEFT JOIN krf_freightcharges fc ON fc.id = (
                SELECT fc2.id FROM krf_freightcharges fc2 
                WHERE (fc2.projectid = so.projectid OR fc2.projectid = 0)
                  AND ((COALESCE(fc2.psono, '') != '' AND fc2.psono = so.psono) OR (COALESCE(fc2.sono, '') != '' AND fc2.sono = so.sono))
                ORDER BY fc2.id DESC LIMIT 1
            )
            WHERE (so.projectid = ? OR ? = 0)
              AND LOWER(REPLACE(IFNULL(soi.status, ''), ' ', '')) IN ('allocated', 'partialdispatch')
        ";

        $params = [$projectid, $projectid, $projectid];
        $types = "iii";

        if (!empty($dateFrom) && !empty($dateTo)) {
            $sql .= " AND soi.deliverydate BETWEEN ? AND ?";
            $params[] = $dateFrom;
            $params[] = $dateTo;
            $types .= "ss";
        }

        if (!empty($searchTerm)) {
            $term = "%{$searchTerm}%";
            $sql .= " AND (so.sono LIKE ? OR so.psono LIKE ? OR so.clientname LIKE ? OR so.districtname LIKE ? OR vth.vehicle_no LIKE ? OR dl.vehicleno LIKE ?)";
            $params[] = $term; $params[] = $term; $params[] = $term; $params[] = $term; $params[] = $term; $params[] = $term;
            $types .= "ssssss";
        }

        $sql .= " GROUP BY so.id, so.sono, so.psono ";

        $havingClauses = ["total_order_qty > 0"];
        if ($filterStatus === 'pending_vehicle') {
            $havingClauses[] = "(COALESCE(vth.vehicle_no, '') = '' AND COALESCE(dl.vehicleno, '') = '')";
        } else if ($filterStatus === 'allocated_vehicle') {
            $havingClauses[] = "(COALESCE(vth.vehicle_no, '') != '' OR COALESCE(dl.vehicleno, '') != '')";
        }
        if (!empty($havingClauses)) {
            $sql .= " HAVING " . implode(" AND ", $havingClauses);
        }

        $sql .= " ORDER BY deliverydate ASC, so.id DESC ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();

        $orders = [];
        $totalPending = 0;
        $totalAllocated = 0;
        $allocatedUserIds = [];

        while ($row = $res->fetch_assoc()) {
            $row['total_order_qty'] = intval($row['total_order_qty'] ?? 0);
            $row['total_dispatched_qty'] = intval($row['total_dispatched_qty'] ?? 0);
            $row['total_billable_qty'] = intval($row['total_billable_qty'] ?? 0);
            if ($row['total_billable_qty'] <= 0 && $row['total_order_qty'] > 0) {
                $row['total_billable_qty'] = $row['total_order_qty'];
            }
            $row['remaining_plant_qty'] = $row['total_billable_qty'];
            $row['effective_vehicleno'] = !empty($row['vehicleno']) ? $row['vehicleno'] : ($row['dl_vehicleno'] ?? '');
            $row['effective_vehiclename'] = !empty($row['vehiclename']) ? $row['vehiclename'] : '';
            $row['effective_drivername'] = !empty($row['drivername']) ? $row['drivername'] : ($row['dl_drivername'] ?? '');
            $row['effective_drivermobile'] = !empty($row['drivermobileno']) ? $row['drivermobileno'] : ($row['dl_drivercontactno'] ?? '');
            $row['is_vehicle_allocated'] = !empty($row['effective_vehicleno']);
            $row['formatted_delivery_date'] = !empty($row['deliverydate']) ? date('d-M-Y', strtotime($row['deliverydate'])) : '-';

            $rawProd = $row['product_names'] ?? '';
            $prods = array_filter(array_map('trim', explode(' || ', $rawProd)));
            $cleanProds = [];
            $product_items = [];
            $hasGrafted = false;

            $rawProdDetails = $row['product_details_concat'] ?? '';
            if (!empty($rawProdDetails)) {
                $itemTokens = explode(' ||| ', $rawProdDetails);
                $seenItemIds = [];
                foreach ($itemTokens as $token) {
                    $parts = explode(':::', $token);
                    if (count($parts) >= 2) {
                        $itemId = intval($parts[0]);
                        if (isset($seenItemIds[$itemId])) continue;
                        $seenItemIds[$itemId] = true;

                        $p = trim($parts[1]);
                        $orderQty = intval(round(floatval($parts[2] ?? 0)));
                        $dispQty = intval(round(floatval($parts[3] ?? 0)));
                        $remainingPlants = max(0, $orderQty - $dispQty);

                        $isG = (stripos($p, 'grafted') !== false && stripos($p, 'nongrafted') === false && stripos($p, 'non-grafted') === false);
                        if ($isG) $hasGrafted = true;
                        $cleanP = preg_replace('/^(Non\s*-?\s*Grafted|Grafted)\s*[-:]?\s*/i', '', $p);
                        $cleanProds[] = $cleanP;
                        $product_items[] = [
                            'item_id' => $itemId,
                            'name' => $p,
                            'clean_name' => $cleanP,
                            'is_grafted' => $isG ? 1 : 0,
                            'order_qty' => $orderQty,
                            'dispatched_qty' => $dispQty,
                            'remaining_plants' => $remainingPlants
                        ];
                    }
                }
            }

            if (empty($product_items)) {
                foreach ($prods as $p) {
                    $isG = (stripos($p, 'grafted') !== false && stripos($p, 'nongrafted') === false && stripos($p, 'non-grafted') === false);
                    if ($isG) $hasGrafted = true;
                    $cleanP = preg_replace('/^(Non\s*-?\s*Grafted|Grafted)\s*[-:]?\s*/i', '', $p);
                    $cleanProds[] = $cleanP;
                    $product_items[] = [
                        'item_id' => 0,
                        'name' => $p,
                        'clean_name' => $cleanP,
                        'is_grafted' => $isG ? 1 : 0,
                        'order_qty' => $row['total_order_qty'],
                        'dispatched_qty' => $row['total_dispatched_qty'],
                        'remaining_plants' => $row['total_billable_qty']
                    ];
                }
            }

            $row['product_names'] = implode(', ', !empty($cleanProds) ? array_unique($cleanProds) : $prods);
            $row['clean_product_name'] = implode(', ', !empty($cleanProds) ? array_unique($cleanProds) : $prods);
            $row['is_grafted'] = $hasGrafted ? 1 : 0;
            $row['product_items'] = $product_items;

            // Freight Financials & Discounts (matching Prepared Dispatch Invoice Step 2)
            $delCharges = floatval($row['dl_deliverycharges'] ?? 0);
            $freightAmt = floatval($row['dl_freightamt'] ?? 0);
            $fcPayable = floatval($row['fc_payable_freight'] ?? 0);
            $fcBase = floatval($row['fc_base_freight'] ?? 0);
            $fcDisc = floatval($row['fc_discount_amount'] ?? 0);
            $fcDiscPct = floatval($row['fc_discount_percent'] ?? 0);

            $baseFreight = ($delCharges > 0) ? $delCharges : (($freightAmt > 0) ? $freightAmt : (($fcBase > 0) ? $fcBase : (($fcPayable > 0) ? $fcPayable : 0)));
            $payableFreight = ($freightAmt > 0) ? $freightAmt : (($delCharges > 0) ? $delCharges : (($fcPayable > 0) ? $fcPayable : $baseFreight));

            // Effective Distance (KM)
            $dist = floatval($row['dl_distance'] ?? 0);
            if ($dist <= 0) {
                $dist = floatval($row['dl_totaldistance'] ?? 0);
            }
            $row['dl_effective_distance'] = $dist;

            $rawType = trim($row['dl_pickuptype'] ?? '');
            $normType = strtolower(preg_replace('/[^a-z]/', '', $rawType));
            $isPickup = in_array($normType, ['pickup', 'selfpickup', 'pickbyclient']);

            // Auto-compute freight if zero and distance > 0 for Farm Delivery
            if ($baseFreight <= 0 && $dist > 0 && !$isPickup) {
                $calcCharge = 0;
                $vId = intval($row['vehicle_id'] ?? 0);
                $vProj = intval($row['projectid'] ?? 0);
                if ($vId > 0) {
                    $qFm = $this->db->query("SELECT freight_charge, calculationtype FROM krf_freight_master WHERE vehicleid = $vId AND $dist BETWEEN dist_min AND dist_max AND (projectid = $vProj OR projectid = 0) LIMIT 1");
                    if ($qFm && $fmR = $qFm->fetch_assoc()) {
                        $calcCharge = floatval($fmR['freight_charge']);
                        if (($fmR['calculationtype'] ?? '') === 'rate') {
                            $calcCharge = round($calcCharge * $dist * 2, 2);
                        }
                    }
                }
                if ($calcCharge <= 0 && !empty($row['vehiclename'])) {
                    $vNameEsc = $this->db->real_escape_string(trim($row['vehiclename']));
                    $qFm2 = $this->db->query("
                        SELECT fm.freight_charge, fm.calculationtype 
                        FROM krf_freight_master fm
                        JOIN krf_vehicle_master vm ON vm.vehicleid = fm.vehicleid
                        WHERE LOWER(TRIM(vm.vehiclename)) = LOWER('$vNameEsc')
                          AND $dist BETWEEN fm.dist_min AND fm.dist_max 
                          AND (fm.projectid = $vProj OR fm.projectid = 0)
                          AND fm.freight_charge > 0
                        LIMIT 1
                    ");
                    if ($qFm2 && $fmR2 = $qFm2->fetch_assoc()) {
                        $calcCharge = floatval($fmR2['freight_charge']);
                        if (($fmR2['calculationtype'] ?? '') === 'rate') {
                            $calcCharge = round($calcCharge * $dist * 2, 2);
                        }
                    }
                }
                if ($calcCharge <= 0) {
                    @include_once __DIR__ . '/../../functions/vehicle_functions.php';
                    if (function_exists('getFreightSuggestion')) {
                        $fSugg = getFreightSuggestion($this->db, $vProj, $dist, floatval($row['total_billable_qty'] ?? 0));
                        if (!empty($fSugg['recommended_charge']) && floatval($fSugg['recommended_charge']) > 0) {
                            $calcCharge = floatval($fSugg['recommended_charge']);
                        }
                    }
                }
                if ($calcCharge > 0) {
                    $baseFreight = $calcCharge;
                    $payableFreight = $calcCharge;
                    // Persist to krf_deliveryaddress_location if currently zero
                    $escPsono = $this->db->real_escape_string($row['psono'] ?? '');
                    $escSono = $this->db->real_escape_string($row['sono'] ?? '');
                    if (!empty($escPsono) || !empty($escSono)) {
                        $this->db->query("
                            UPDATE krf_deliveryaddress_location 
                            SET deliverycharges = CASE WHEN IFNULL(deliverycharges,0) = 0 THEN {$calcCharge} ELSE deliverycharges END, 
                                freightamt = CASE WHEN IFNULL(freightamt,0) = 0 THEN {$calcCharge} ELSE freightamt END 
                            WHERE (psono = '{$escPsono}' AND '{$escPsono}' != '') OR (sono = '{$escSono}' AND '{$escSono}' != '')
                        ");
                    }
                }
            }

            $discountAmt = max(0, round($baseFreight - $payableFreight, 2));
            if ($discountAmt <= 0 && $fcDisc > 0) {
                $discountAmt = $fcDisc;
            }
            $discountPct = ($baseFreight > 0 && $discountAmt > 0) ? round(($discountAmt / $baseFreight) * 100, 2) : ($fcDiscPct > 0 ? $fcDiscPct : 0);

            $row['dl_base_freight'] = $baseFreight;
            $row['dl_payable_freight'] = $payableFreight;
            $row['dl_discount_amount'] = $discountAmt;
            $row['dl_discount_percent'] = $discountPct;
            $row['dl_effective_distance'] = $dist;

            if (!empty($row['allocated_by'])) {
                $allocatedUserIds[] = $row['allocated_by'];
            }

            if ($row['is_vehicle_allocated']) {
                $totalAllocated++;
            } else {
                $totalPending++;
            }

            $orders[] = $row;
        }

        // Resolve allocated_by user names
        $userMap = [];
        if (!empty($allocatedUserIds) && $this->authDb) {
            $allocatedUserIds = array_values(array_unique($allocatedUserIds));
            $placeholders = implode(',', array_fill(0, count($allocatedUserIds), '?'));
            $uSql = "SELECT loginid, mobileno, loginname FROM login_user_master WHERE loginid IN ($placeholders) OR mobileno IN ($placeholders)";
            $uStmt = $this->authDb->prepare($uSql);
            if ($uStmt) {
                $types = str_repeat('s', count($allocatedUserIds) * 2);
                $bindParams = array_merge($allocatedUserIds, $allocatedUserIds);
                $uStmt->bind_param($types, ...$bindParams);
                $uStmt->execute();
                $uRes = $uStmt->get_result();
                while ($uRow = $uRes->fetch_assoc()) {
                    if (!empty($uRow['loginid'])) $userMap[$uRow['loginid']] = $uRow['loginname'];
                    if (!empty($uRow['mobileno'])) $userMap[$uRow['mobileno']] = $uRow['loginname'];
                }
            }
        }

        // Fetch all allocated vehicles for these orders from krf_vehicle_travel_history
        $orderSonos = [];
        $orderPsonos = [];
        foreach ($orders as $ord) {
            if (!empty($ord['sono'])) $orderSonos[] = $ord['sono'];
            if (!empty($ord['psono'])) $orderPsonos[] = $ord['psono'];
        }
        $orderSonos = array_values(array_unique($orderSonos));
        $orderPsonos = array_values(array_unique($orderPsonos));

        $tripsByOrder = [];
        if (!empty($orderSonos) || !empty($orderPsonos)) {
            $whereParts = [];
            $vParams = [];
            $vTypes = "";
            if (!empty($orderSonos)) {
                $whereParts[] = "vth.stockorder_no IN (" . implode(',', array_fill(0, count($orderSonos), '?')) . ")";
                foreach ($orderSonos as $s) { $vParams[] = $s; $vTypes .= "s"; }
            }
            if (!empty($orderPsonos)) {
                $whereParts[] = "vth.psono IN (" . implode(',', array_fill(0, count($orderPsonos), '?')) . ")";
                foreach ($orderPsonos as $p) { $vParams[] = $p; $vTypes .= "s"; }
            }
            $vthSql = "
                SELECT vth.id, vth.stockorder_no, vth.psono, vth.vehicle_id, vth.vehicle_no, 
                       vth.driver_name, vth.driver_mobileno, vth.deliverydate, vth.send_totalplantcount,
                       COALESCE(vm.vehiclename, '') AS vehiclename,
                       COALESCE(vm.vehicleownername, '') AS vehicleownername,
                       COALESCE(vm.plantscapacity, 0) AS plantscapacity
                FROM krf_vehicle_travel_history vth
                LEFT JOIN krf_vehicle_master vm ON (vm.vehicleid = vth.vehicle_id OR UPPER(TRIM(vm.vehicleno)) = UPPER(TRIM(vth.vehicle_no)))
                WHERE (" . implode(" OR ", $whereParts) . ")
                  AND (vth.status IS NULL OR vth.status NOT IN ('Cancelled'))
                ORDER BY vth.id DESC
            ";
            $vthStmt = $this->db->prepare($vthSql);
            if ($vthStmt) {
                $vthStmt->bind_param($vTypes, ...$vParams);
                $vthStmt->execute();
                $vthRes = $vthStmt->get_result();
                $seenOrderVehicles = [];
                while ($vRow = $vthRes->fetch_assoc()) {
                    $item = [
                        'id' => intval($vRow['id']),
                        'vehicle_id' => intval($vRow['vehicle_id']),
                        'vehicleno' => $vRow['vehicle_no'],
                        'vehiclename' => $vRow['vehiclename'],
                        'vehicleownername' => $vRow['vehicleownername'],
                        'plantscapacity' => intval($vRow['plantscapacity']),
                        'drivername' => $vRow['driver_name'],
                        'drivermobileno' => $vRow['driver_mobileno'],
                        'deliverydate' => $vRow['deliverydate'],
                        'allocated_plants' => intval($vRow['send_totalplantcount'])
                    ];
                    $cleanPlate = preg_replace('/[^A-Za-z0-9]/', '', strtoupper(trim($vRow['vehicle_no'] ?? '')));

                    if (!empty($vRow['stockorder_no'])) {
                        $sKey = 'sono_' . $vRow['stockorder_no'];
                        $dedupKey = $sKey . '_' . $cleanPlate;
                        if (!isset($seenOrderVehicles[$dedupKey])) {
                            $seenOrderVehicles[$dedupKey] = true;
                            $tripsByOrder[$sKey][] = $item;
                        }
                    }
                    if (!empty($vRow['psono'])) {
                        $pKey = 'psono_' . $vRow['psono'];
                        $dedupKey = $pKey . '_' . $cleanPlate;
                        if (!isset($seenOrderVehicles[$dedupKey])) {
                            $seenOrderVehicles[$dedupKey] = true;
                            $tripsByOrder[$pKey][] = $item;
                        }
                    }
                }
                $vthStmt->close();
            }
        }

        foreach ($orders as &$ord) {
            $allocBy = $ord['allocated_by'] ?? '';
            $ord['allocated_by_name'] = $userMap[$allocBy] ?? $allocBy;

            $sKey = !empty($ord['sono']) ? ('sono_' . $ord['sono']) : '';
            $pKey = !empty($ord['psono']) ? ('psono_' . $ord['psono']) : '';
            $vehicles = $tripsByOrder[$sKey] ?? ($tripsByOrder[$pKey] ?? []);

            $ord['allocated_vehicles'] = $vehicles;
            if (!empty($vehicles)) {
                $plates = array_unique(array_filter(array_column($vehicles, 'vehicleno')));
                $ord['effective_vehicleno'] = implode(', ', $plates);
                $ord['is_vehicle_allocated'] = !empty($plates);
            }
        }
        unset($ord);

        return [
            'orders' => $orders,
            'total' => count($orders),
            'pending' => $totalPending,
            'allocated' => $totalAllocated
        ];
    }

    /**
     * Allocate Vehicles to Order (Supports Multiple Vehicle Groups):
     * 1. Clear previous planned travel history for order
     * 2. Insert record for each vehicle into krf_vehicle_travel_history & link krf_vehicletrip
     * 3. Synchronize krf_deliveryaddress_location
     */
    public function allocateVehicle($params) {
        $projectid = intval($params['projectid'] ?? 0);
        $sono = trim($params['sono'] ?? '');
        $psono = trim($params['psono'] ?? '');
        $clientid = trim($params['clientid'] ?? '');
        $scheduled_dispatch_date = !empty($params['scheduled_dispatch_date']) ? trim($params['scheduled_dispatch_date']) : date('Y-m-d');
        $remarks = trim($params['remarks'] ?? '');
        $loginid = trim($params['loginid'] ?? 'Admin');
        $total_order_plants = intval($params['total_allocated_plants'] ?? 0);

        if (empty($sono) && empty($psono)) {
            return ['status' => 'error', 'message' => 'Sales order number is required.'];
        }

        // Parse vehicles array
        $vehicles = [];
        if (!empty($params['vehicles'])) {
            $vehicles = is_string($params['vehicles']) ? json_decode($params['vehicles'], true) : $params['vehicles'];
        }

        // Fallback for single vehicle if vehicles array was not passed
        if (empty($vehicles) && !empty($params['vehicleno'])) {
            $vehicles = [[
                'vehicleno' => $params['vehicleno'],
                'vehiclename' => $params['vehiclename'] ?? '',
                'vehicle_capacity' => $params['vehicle_capacity'] ?? 0,
                'vehicleownername' => $params['vehicleownername'] ?? '',
                'drivername' => $params['drivername'] ?? '',
                'drivermobileno' => $params['drivermobileno'] ?? '',
                'allocated_plants' => $total_order_plants
            ]];
        }

        if (empty($vehicles)) {
            return ['status' => 'error', 'message' => 'At least one vehicle must be allocated.'];
        }

        $existing_trip_id = trim($params['existing_trip_id'] ?? '');

        // Fetch existing travel history records for this order to UPDATE in-place without deleting
        $existingVthList = [];
        $qVth = $this->db->prepare("
            SELECT id, trip_id, vehicle_id, vehicle_no, driver_name, driver_mobileno 
            FROM krf_vehicle_travel_history 
            WHERE ((stockorder_no = ? AND ? != '') OR (psono = ? AND ? != '')) 
              AND (status NOT IN ('Dispatched', 'Delivered') OR status IS NULL OR status = '')
            ORDER BY id ASC
        ");
        if ($qVth) {
            $qVth->bind_param("ssss", $sono, $sono, $psono, $psono);
            $qVth->execute();
            $rVth = $qVth->get_result();
            while ($rowVth = $rVth->fetch_assoc()) {
                $existingVthList[] = $rowVth;
                if (empty($existing_trip_id) && !empty($rowVth['trip_id'])) {
                    $existing_trip_id = trim($rowVth['trip_id']);
                }
            }
            $qVth->close();
        }

        $allPlates = [];
        $allDrivers = [];
        $allMobiles = [];
        $sumPlants = 0;
        $primaryVehicleName = '';

        foreach ($vehicles as $vIdx => $vItem) {
            $vehNo = preg_replace('/[\s\-\/]+/', '', strtoupper(trim($vItem['vehicleno'] ?? '')));
            $vehName = trim($vItem['vehiclename'] ?? '');
            $vehCap = intval($vItem['vehicle_capacity'] ?? 0);
            $vehOwner = trim($vItem['vehicleownername'] ?? '');
            $drvName = trim($vItem['drivername'] ?? '');
            $drvMobile = trim($vItem['drivermobileno'] ?? '');
            $allocPlants = intval($vItem['allocated_plants'] ?? 0);

            if (empty($vehNo)) continue;

            $allPlates[] = $vehNo;
            if (!empty($drvName)) $allDrivers[] = $drvName;
            if (!empty($drvMobile)) $allMobiles[] = $drvMobile;
            $sumPlants += $allocPlants;
            if (empty($primaryVehicleName) && !empty($vehName)) $primaryVehicleName = $vehName;

            // Master vehicle lookup & update/insert
            $vehId = 0;
            $chkVeh = $this->db->prepare("SELECT vehicleid, vehiclename, vehicleownername, plantscapacity FROM krf_vehicle_master WHERE REPLACE(REPLACE(REPLACE(UPPER(TRIM(vehicleno)), '-', ''), '/', ''), ' ', '') = ? LIMIT 1");
            $chkVeh->bind_param("s", $vehNo);
            $chkVeh->execute();
            $vehMasterRow = $chkVeh->get_result()->fetch_assoc();
            $chkVeh->close();

            if (!$vehMasterRow) {
                $insVeh = $this->db->prepare("
                    INSERT INTO krf_vehicle_master (
                        projectid, vehicleno, vehiclename, vehicleownername, plantscapacity, status, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, 'Active', NOW(), NOW())
                ");
                $defName = !empty($vehName) ? $vehName : $vehNo;
                $insVeh->bind_param("isssi", $projectid, $vehNo, $defName, $vehOwner, $vehCap);
                $insVeh->execute();
                $vehId = $insVeh->insert_id;
                $insVeh->close();

                // If this vehicle belongs to a vehicle type that has freight slabs, copy slabs to the new vehicle
                if ($vehId > 0 && !empty($defName)) {
                    $cpFm = $this->db->prepare("
                        INSERT INTO krf_freight_master (projectid, dist_min, dist_max, vehicleid, freight_charge, calculationtype)
                        SELECT fm.projectid, fm.dist_min, fm.dist_max, ?, fm.freight_charge, fm.calculationtype
                        FROM krf_freight_master fm
                        JOIN krf_vehicle_master vm ON vm.vehicleid = fm.vehicleid
                        WHERE LOWER(TRIM(vm.vehiclename)) = LOWER(TRIM(?))
                          AND (fm.projectid = ? OR ? = 0)
                          AND fm.freight_charge > 0
                        ON DUPLICATE KEY UPDATE freight_charge = VALUES(freight_charge)
                    ");
                    if ($cpFm) {
                        $cpFm->bind_param("isii", $vehId, $defName, $projectid, $projectid);
                        $cpFm->execute();
                        $cpFm->close();
                    }
                }
            } else {
                $vehId = intval($vehMasterRow['vehicleid']);
                if (empty($vehName) && !empty($vehMasterRow['vehiclename'])) $vehName = $vehMasterRow['vehiclename'];
                if (empty($vehOwner) && !empty($vehMasterRow['vehicleownername'])) $vehOwner = $vehMasterRow['vehicleownername'];

                $updates = [];
                $upTypes = "";
                $upVals = [];
                if ($vehCap > 0 && intval($vehMasterRow['plantscapacity']) <= 0) {
                    $updates[] = "plantscapacity = ?"; $upTypes .= "i"; $upVals[] = $vehCap;
                }
                if (!empty($vehName)) {
                    $updates[] = "vehiclename = ?"; $upTypes .= "s"; $upVals[] = $vehName;
                }
                if (!empty($vehOwner)) {
                    $updates[] = "vehicleownername = ?"; $upTypes .= "s"; $upVals[] = $vehOwner;
                }
                if (!empty($updates)) {
                    $upTypes .= "i"; $upVals[] = $vehId;
                    $updSql = "UPDATE krf_vehicle_master SET " . implode(", ", $updates) . ", updated_at = NOW() WHERE vehicleid = ?";
                    $updStmt = $this->db->prepare($updSql);
                    $updStmt->bind_param($upTypes, ...$upVals);
                    $updStmt->execute();
                    $updStmt->close();
                }
            }

            // Driver master lookup & update/insert
            if (!empty($drvName)) {
                $chkDrv = $this->db->prepare("SELECT driverid, drivermobileno FROM krf_vehicle_driver_master WHERE LOWER(TRIM(drivername)) = LOWER(TRIM(?)) LIMIT 1");
                $chkDrv->bind_param("s", $drvName);
                $chkDrv->execute();
                $drvRow = $chkDrv->get_result()->fetch_assoc();
                $chkDrv->close();

                if (!$drvRow) {
                    $insDrv = $this->db->prepare("
                        INSERT INTO krf_vehicle_driver_master (
                            projectid, drivername, drivermobileno, status, created_at, updated_at
                        ) VALUES (?, ?, ?, 'Active', NOW(), NOW())
                    ");
                    $insDrv->bind_param("iss", $projectid, $drvName, $drvMobile);
                    $insDrv->execute();
                    $insDrv->close();
                } else if (!empty($drvMobile)) {
                    $updDrv = $this->db->prepare("UPDATE krf_vehicle_driver_master SET drivermobileno = ?, updated_at = NOW() WHERE driverid = ?");
                    $updDrv->bind_param("si", $drvMobile, $drvRow['driverid']);
                    $updDrv->execute();
                    $updDrv->close();
                }
            }

            // Link existing trip (UPDATE in-place) or create initial trip if fresh allocation
            $trip_id = '';
            if (!empty($existing_trip_id)) {
                $chkVt = $this->db->prepare("SELECT trip_id, trip_no FROM krf_vehicletrip WHERE trip_no = ? OR trip_id = ? LIMIT 1");
                $chkVt->bind_param("ss", $existing_trip_id, $existing_trip_id);
                $chkVt->execute();
                $vtRow = $chkVt->get_result()->fetch_assoc();
                $chkVt->close();
                if ($vtRow) {
                    $trip_id = $vtRow['trip_no'];
                    // UPDATE existing trip in krf_vehicletrip with new vehicle and driver
                    $updVt = $this->db->prepare("
                        UPDATE krf_vehicletrip SET
                            vehicle_id = ?,
                            vehicle_no = ?,
                            driver_name = ?,
                            driver_mobile_no = ?,
                            delivery_date = ?,
                            move_for = ?,
                            remarks = ?,
                            updated_at = NOW()
                        WHERE trip_no = ?
                    ");
                    if ($updVt) {
                        $updVt->bind_param(
                            "isssssss",
                            $vehId,
                            $vehNo,
                            $drvName,
                            $drvMobile,
                            $scheduled_dispatch_date,
                            $scheduled_dispatch_date,
                            $remarks,
                            $trip_id
                        );
                        $updVt->execute();
                        $updVt->close();
                    }
                }
            }

            if (empty($trip_id)) {
                $trip_id = $this->getOrCreateTripNo($projectid, $vehId, $vehNo, $drvName, $drvMobile, $scheduled_dispatch_date, $remarks, $loginid);
            }

            // Lookup distance if not provided
            $distance = floatval($params['distance'] ?? 0);
            if ($distance <= 0) {
                $qDist = $this->db->prepare("SELECT distance, totaldistance FROM krf_deliveryaddress_location WHERE (psono = ? AND ? != '') OR (sono = ? AND ? != '') ORDER BY id DESC LIMIT 1");
                if ($qDist) {
                    $qDist->bind_param("ssss", $psono, $psono, $sono, $sono);
                    $qDist->execute();
                    $rDist = $qDist->get_result()->fetch_assoc();
                    if ($rDist) {
                        $distance = floatval($rDist['distance'] ?? $rDist['totaldistance'] ?? 0);
                    }
                    $qDist->close();
                }
            }

            // Update or insert travel history record for this vehicle (with freight for primary vehicle)
            $vthBaseFreight = ($sumPlants == $allocPlants) ? floatval($params['base_freight'] ?? 0) : 0;
            $vthDiscPct = ($sumPlants == $allocPlants) ? floatval($params['discount_percent'] ?? 0) : 0;
            $vthPayFreight = ($sumPlants == $allocPlants) ? floatval($params['payable_freight'] ?? 0) : 0;

            $existingVthId = isset($existingVthList[$vIdx]) ? intval($existingVthList[$vIdx]['id']) : 0;
            if ($existingVthId > 0) {
                // UPDATE existing travel history record in-place (DO NOT DELETE)
                $updVth = $this->db->prepare("
                    UPDATE krf_vehicle_travel_history SET
                        projectid = ?,
                        trip_id = ?,
                        vehicle_id = ?,
                        vehicle_no = ?,
                        driver_name = ?,
                        driver_mobileno = ?,
                        deliverydate = ?,
                        send_totalplantcount = ?,
                        distance_km = ?,
                        freight_amount = ?,
                        freight_discountpercent = ?,
                        payable_freightamount = ?,
                        send_remark = ?,
                        updated_by = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                if ($updVth) {
                    $updVth->bind_param(
                        "isissssiddddssi",
                        $projectid,
                        $trip_id,
                        $vehId,
                        $vehNo,
                        $drvName,
                        $drvMobile,
                        $scheduled_dispatch_date,
                        $allocPlants,
                        $distance,
                        $vthBaseFreight,
                        $vthDiscPct,
                        $vthPayFreight,
                        $remarks,
                        $loginid,
                        $existingVthId
                    );
                    $updVth->execute();
                    $updVth->close();
                }
            } else {
                // Fresh allocation: Insert initial travel history record
                $insVth = $this->db->prepare("
                    INSERT INTO krf_vehicle_travel_history (
                        projectid, trip_id, psono, stockorder_no, vehicle_id, vehicle_no,
                        driver_name, driver_mobileno, deliverydate, send_totalplantcount,
                        distance_km, freight_amount, freight_discountpercent, payable_freightamount,
                        send_remark, status, created_by, created_at, updated_at
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, 'Planned', ?, NOW(), NOW()
                    )
                ");
                $insVth->bind_param(
                    "isssissssiddddss",
                    $projectid,
                    $trip_id,
                    $psono,
                    $sono,
                    $vehId,
                    $vehNo,
                    $drvName,
                    $drvMobile,
                    $scheduled_dispatch_date,
                    $allocPlants,
                    $distance,
                    $vthBaseFreight,
                    $vthDiscPct,
                    $vthPayFreight,
                    $remarks,
                    $loginid
                );
                $insVth->execute();
                $insVth->close();
            }
        }

        // Update overall trip freight and distance in krf_vehicletrip
        $baseF = floatval($params['base_freight'] ?? 0);
        $payF = floatval($params['payable_freight'] ?? 0);
        $discPct = floatval($params['discount_percent'] ?? 0);
        $discAmt = floatval($params['discount_amount'] ?? 0);

        if (!empty($trip_id)) {
            $updVt = $this->db->prepare("
                UPDATE krf_vehicletrip SET
                    freight_amount = CASE WHEN ? > 0 THEN ? ELSE freight_amount END,
                    freight_discount_percent = ?,
                    payable_freight_amount = CASE WHEN ? > 0 THEN ? ELSE payable_freight_amount END,
                    distance_km = CASE WHEN ? > 0 THEN ? ELSE distance_km END,
                    remarks = CASE WHEN ? != '' THEN ? ELSE remarks END,
                    updated_at = NOW()
                WHERE trip_no = ?
            ");
            if ($updVt) {
                $updVt->bind_param("dddddddsss", $baseF, $baseF, $discPct, $payF, $payF, $distance, $distance, $remarks, $remarks, $trip_id);
                $updVt->execute();
                $updVt->close();
            }
        }

        // 2. Synchronize krf_deliveryaddress_location
        $this->syncDeliveryAddressLocation([
            'projectid' => $projectid,
            'sono' => $sono,
            'psono' => $psono,
            'clientid' => $clientid,
            'vehicleno' => implode(', ', array_unique($allPlates)),
            'vehiclename' => $primaryVehicleName,
            'drivername' => implode(', ', array_unique($allDrivers)),
            'drivermobileno' => implode(', ', array_unique($allMobiles)),
            'scheduled_dispatch_date' => $scheduled_dispatch_date,
            'total_allocated_plants' => $sumPlants > 0 ? $sumPlants : $total_order_plants,
            'remarks' => $remarks,
            'loginid' => $loginid,
            'distance' => $distance,
            'base_freight' => $baseF,
            'payable_freight' => $payF,
            'pickuptype' => trim($params['pickuptype'] ?? '')
        ]);

        // 3. Synchronize krf_freightcharges
        $vPlatesStr = implode(', ', array_unique($allPlates));
        $vDriversStr = implode(', ', array_unique($allDrivers));
        $vMobilesStr = implode(', ', array_unique($allMobiles));
        $firstVehId = !empty($vehicles[0]['vehicle_id']) ? intval($vehicles[0]['vehicle_id']) : $vehId;

        $chkFc = $this->db->prepare("SELECT id FROM krf_freightcharges WHERE (psono = ? AND ? != '') OR (sono = ? AND ? != '') ORDER BY id DESC LIMIT 1");
        if ($chkFc) {
            $chkFc->bind_param("ssss", $psono, $psono, $sono, $sono);
            $chkFc->execute();
            $fcRow = $chkFc->get_result()->fetch_assoc();
            $chkFc->close();

            if ($fcRow && !empty($fcRow['id'])) {
                $updFc = $this->db->prepare("
                    UPDATE krf_freightcharges SET
                        trip_id = ?,
                        vehicleid = CASE WHEN ? > 0 THEN ? ELSE vehicleid END,
                        vehicle_no = CASE WHEN ? != '' THEN ? ELSE vehicle_no END,
                        driver_name = CASE WHEN ? != '' THEN ? ELSE driver_name END,
                        driver_mobile = CASE WHEN ? != '' THEN ? ELSE driver_mobile END,
                        distance_km = CASE WHEN ? > 0 THEN ? ELSE distance_km END,
                        freight_amount = CASE WHEN ? > 0 THEN ? ELSE freight_amount END,
                        freight_discountpercent = ?,
                        freight_discountamount = ?,
                        payable_freightamount = CASE WHEN ? > 0 THEN ? ELSE payable_freightamount END,
                        remarks = CASE WHEN ? != '' THEN ? ELSE remarks END,
                        status = 'Active',
                        updated_at = NOW()
                    WHERE id = ?
                ");
                if ($updFc) {
                    $updFc->bind_param(
                        "siissssssddddddddssi",
                        $trip_id,
                        $firstVehId, $firstVehId,
                        $vPlatesStr, $vPlatesStr,
                        $vDriversStr, $vDriversStr,
                        $vMobilesStr, $vMobilesStr,
                        $distance, $distance,
                        $baseF, $baseF,
                        $discPct,
                        $discAmt,
                        $payF, $payF,
                        $remarks, $remarks,
                        $fcRow['id']
                    );
                    $updFc->execute();
                    $updFc->close();
                }
            } else {
                $insFc = $this->db->prepare("
                    INSERT INTO krf_freightcharges (
                        projectid, psono, sono, clientid, trip_id, vehicleid, vehicle_no,
                        driver_name, driver_mobile, distance_km, freight_amount,
                        freight_discountpercent, freight_discountamount, payable_freightamount,
                        remarks, status, created_by, created_at, updated_at
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, ?,
                        ?, 'Active', ?, NOW(), NOW()
                    )
                ");
                if ($insFc) {
                    $insFc->bind_param(
                        "issssisssdddddss",
                        $projectid, $psono, $sono, $clientid, $trip_id, $firstVehId, $vPlatesStr,
                        $vDriversStr, $vMobilesStr, $distance, $baseF,
                        $discPct, $discAmt, $payF,
                        $remarks, $loginid
                    );
                    $insFc->execute();
                    $insFc->close();
                }
            }
        }

        $cnt = count(array_unique($allPlates));
        return [
            'status' => 'success',
            'message' => "Successfully allocated {$cnt} vehicle" . ($cnt > 1 ? 's' : '') . " (" . implode(', ', array_unique($allPlates)) . ") to Order {$sono}.",
            'trip_id' => $trip_id,
            'freight_amount' => $baseF,
            'payable_freight' => $payF,
            'vehicles' => $vehicles
        ];
    }

    /**
     * Find existing or create planned trip in krf_vehicletrip
     */
    public function getOrCreateTripNo($projectid, $vehicle_id, $vehicleno, $drivername, $drivermobileno, $deliverydate, $remarks, $loginid) {
        $chkTrip = $this->db->prepare("
            SELECT trip_no FROM krf_vehicletrip 
            WHERE ((vehicle_id = ? AND ? > 0) OR (vehicle_no = ? AND ? != ''))
              AND delivery_date = ?
              AND status NOT IN ('Cancelled', 'Delivered')
            ORDER BY trip_id DESC LIMIT 1
        ");
        $chkTrip->bind_param("iisss", $vehicle_id, $vehicle_id, $vehicleno, $vehicleno, $deliverydate);
        $chkTrip->execute();
        $tRow = $chkTrip->get_result()->fetch_assoc();
        $chkTrip->close();

        if ($tRow && !empty($tRow['trip_no'])) {
            return $tRow['trip_no'];
        }

        $datePart = date('Ymd', strtotime($deliverydate));
        $cleanVeh = preg_replace('/[^A-Za-z0-9]/', '', $vehicleno);
        $vehSuffix = !empty($cleanVeh) ? substr($cleanVeh, -4) : 'VEH';
        $trip_id = "TRIP-{$datePart}-{$vehSuffix}-" . rand(100, 999);

        $insVt = $this->db->prepare("
            INSERT INTO krf_vehicletrip (
                trip_no, project_id, vehicle_id, vehicle_no, driver_name, driver_mobile_no,
                delivery_date, move_for, status, remarks, created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Planned', ?, ?, NOW())
        ");
        $insVt->bind_param(
            "siisssssss",
            $trip_id,
            $projectid,
            $vehicle_id,
            $vehicleno,
            $drivername,
            $drivermobileno,
            $deliverydate,
            $deliverydate,
            $remarks,
            $loginid
        );
        $insVt->execute();
        $insVt->close();

        return $trip_id;
    }

    /**
     * Remove Vehicle Allocation:
     * 1. Remove trip history
     * 2. Cancel planned trip if empty
     * 3. Clear vehicle columns in krf_deliveryaddress_location
     */
    public function removeAllocation($params) {
        $projectid = intval($params['projectid'] ?? 0);
        $sono = trim($params['sono'] ?? '');
        $psono = trim($params['psono'] ?? '');
        $loginid = trim($params['loginid'] ?? '');

        // 1. Find trip_id from krf_vehicle_travel_history
        $getTrip = $this->db->prepare("SELECT DISTINCT trip_id FROM krf_vehicle_travel_history WHERE (stockorder_no = ? AND ? != '') OR (psono = ? AND ? != '')");
        $getTrip->bind_param("ssss", $sono, $sono, $psono, $psono);
        $getTrip->execute();
        $tripRes = $getTrip->get_result();
        $affectedTrips = [];
        while ($tr = $tripRes->fetch_assoc()) {
            if (!empty($tr['trip_id'])) {
                $affectedTrips[] = $tr['trip_id'];
            }
        }
        $getTrip->close();

        // 2. Delete from krf_vehicle_travel_history
        $del = $this->db->prepare("DELETE FROM krf_vehicle_travel_history WHERE (stockorder_no = ? AND ? != '') OR (psono = ? AND ? != '')");
        $del->bind_param("ssss", $sono, $sono, $psono, $psono);
        $del->execute();
        $del->close();

        // 3. Cancel planned trip if no orders remain on it
        foreach ($affectedTrips as $tripNo) {
            $chkRemain = $this->db->prepare("SELECT COUNT(id) AS cnt FROM krf_vehicle_travel_history WHERE trip_id = ?");
            $chkRemain->bind_param("s", $tripNo);
            $chkRemain->execute();
            $cntRow = $chkRemain->get_result()->fetch_assoc();
            $chkRemain->close();
            if ($cntRow && intval($cntRow['cnt']) === 0) {
                $updVt = $this->db->prepare("UPDATE krf_vehicletrip SET status = 'Cancelled', updated_at = NOW() WHERE trip_no = ? AND status = 'Planned'");
                $updVt->bind_param("s", $tripNo);
                $updVt->execute();
                $updVt->close();
            }
        }

        // 4. Clear in krf_deliveryaddress_location
        $updDl = $this->db->prepare("
            UPDATE krf_deliveryaddress_location SET
                vehicleno = NULL,
                drivername = NULL,
                drivercontactno = NULL,
                transporttype = NULL,
                updatedby = ?,
                updated_by = ?,
                updateddate = CURDATE(),
                updated_at = NOW()
            WHERE (psono = ? AND ? != '') OR (sono = ? AND ? != '')
        ");
        $updDl->bind_param("ssssss", $loginid, $loginid, $psono, $psono, $sono, $sono);
        $updDl->execute();
        $updDl->close();

        return [
            'status' => 'success',
            'message' => "Vehicle allocation removed."
        ];
    }

    /**
     * Sync krf_deliveryaddress_location table with allocated vehicle details
     */
    public function syncDeliveryAddressLocation($params) {
        $projectid = intval($params['projectid'] ?? 0);
        $sono = trim($params['sono'] ?? '');
        $psono = trim($params['psono'] ?? '');
        $clientid = trim($params['clientid'] ?? '');
        $vehicleno = trim($params['vehicleno'] ?? '');
        $vehiclename = trim($params['vehiclename'] ?? '');
        $drivername = trim($params['drivername'] ?? '');
        $drivermobileno = trim($params['drivermobileno'] ?? '');
        $scheduled_dispatch_date = $params['scheduled_dispatch_date'] ?? null;
        $total_allocated_plants = intval($params['total_allocated_plants'] ?? 0);
        $remarks = trim($params['remarks'] ?? '');
        $loginid = trim($params['loginid'] ?? '');

        $dispDt = !empty($scheduled_dispatch_date) ? ($scheduled_dispatch_date . ' 00:00:00') : null;

        $baseFreight = floatval($params['base_freight'] ?? 0);
        $payableFreight = floatval($params['payable_freight'] ?? 0);
        $pickuptype = trim($params['pickuptype'] ?? '');
        $distance = floatval($params['distance'] ?? 0);

        $chkDl = $this->db->prepare("
            SELECT id FROM krf_deliveryaddress_location 
            WHERE (psono = ? AND ? != '') OR (sono = ? AND ? != '') 
            ORDER BY id DESC LIMIT 1
        ");
        $chkDl->bind_param("ssss", $psono, $psono, $sono, $sono);
        $chkDl->execute();
        $dlRow = $chkDl->get_result()->fetch_assoc();
        $chkDl->close();

        if ($dlRow && !empty($dlRow['id'])) {
            $updDl = $this->db->prepare("
                UPDATE krf_deliveryaddress_location SET
                    vehicleno = ?,
                    drivername = ?,
                    drivercontactno = ?,
                    transporttype = COALESCE(NULLIF(?, ''), transporttype),
                    dispatch_datetime = COALESCE(?, dispatch_datetime),
                    deliveryqty = CASE WHEN ? > 0 THEN ? ELSE deliveryqty END,
                    remark = CASE WHEN ? != '' THEN ? ELSE remark END,
                    deliverycharges = CASE WHEN ? > 0 THEN ? ELSE deliverycharges END,
                    freightamt = CASE WHEN ? > 0 THEN ? ELSE freightamt END,
                    distance = CASE WHEN ? > 0 THEN ? ELSE distance END,
                    totaldistance = CASE WHEN ? > 0 THEN ? ELSE totaldistance END,
                    pickuptype = CASE WHEN ? != '' THEN ? ELSE pickuptype END,
                    updatedby = ?,
                    updated_by = ?,
                    updateddate = CURDATE(),
                    updated_at = NOW()
                WHERE id = ?
            ");
            $updDl->bind_param(
                "sssssiissddddddddssssi",
                $vehicleno,
                $drivername,
                $drivermobileno,
                $vehiclename,
                $dispDt,
                $total_allocated_plants,
                $total_allocated_plants,
                $remarks,
                $remarks,
                $baseFreight,
                $baseFreight,
                $payableFreight,
                $payableFreight,
                $distance,
                $distance,
                $distance,
                $distance,
                $pickuptype,
                $pickuptype,
                $loginid,
                $loginid,
                $dlRow['id']
            );
            $updDl->execute();
            $updDl->close();
        } else {
            // Fallback insert if row does not exist
            $cId = !empty($clientid) ? $clientid : '';
            $delAddr = '';
            $stateName = '';
            $distName = '';
            $prodId = '';

            $soStmt = $this->db->prepare("
                SELECT clientid, clientname, delivery_address, statename, districtname 
                FROM krf_sales_order 
                WHERE (psono = ? AND ? != '') OR (sono = ? AND ? != '') 
                ORDER BY id DESC LIMIT 1
            ");
            if ($soStmt) {
                $soStmt->bind_param("ssss", $psono, $psono, $sono, $sono);
                $soStmt->execute();
                $soRow = $soStmt->get_result()->fetch_assoc();
                $soStmt->close();
                if ($soRow) {
                    if (empty($cId)) $cId = $soRow['clientid'] ?? '';
                    $delAddr = $soRow['delivery_address'] ?? '';
                    $stateName = $soRow['statename'] ?? '';
                    $distName = $soRow['districtname'] ?? '';
                }
            }

            // Lookup productid from krf_sales_order_items if needed
            if (!empty($sono)) {
                $pStmt = $this->db->prepare("SELECT productid FROM krf_sales_order_items WHERE sono = ? LIMIT 1");
                if ($pStmt) {
                    $pStmt->bind_param("s", $sono);
                    $pStmt->execute();
                    $pRow = $pStmt->get_result()->fetch_assoc();
                    $pStmt->close();
                    if ($pRow && !empty($pRow['productid'])) {
                        $prodId = $pRow['productid'];
                    }
                }
            }

            $insDl = $this->db->prepare("
                INSERT INTO krf_deliveryaddress_location (
                    projectid, psono, sono, clientid, productid, deliveryqty,
                    deliveryaddress, dest_state, dest_district, transporttype,
                    vehicleno, drivername, drivercontactno, dispatch_datetime,
                    distance, totaldistance, deliverycharges, freightamt, pickuptype,
                    remark, delivery_status, status, created_by, updatedby, updated_by,
                    created_at, updated_at, updateddate
                ) VALUES (
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, 'Pending', 'Pending', ?, ?, ?,
                    NOW(), NOW(), CURDATE()
                )
            ");
            $insDl->bind_param(
                "issssissssssssddddsssss",
                $projectid,
                $psono,
                $sono,
                $cId,
                $prodId,
                $total_allocated_plants,
                $delAddr,
                $stateName,
                $distName,
                $vehiclename,
                $vehicleno,
                $drivername,
                $drivermobileno,
                $dispDt,
                $distance,
                $distance,
                $baseFreight,
                $payableFreight,
                $pickuptype,
                $remarks,
                $loginid,
                $loginid,
                $loginid
            );
            $insDl->execute();
            $insDl->close();
        }
    }

    /**
     * Helper to upsert trip and travel history
     */
    public function saveOrUpdateVehicleTripAndHistory($params) {
        $projectid = intval($params['projectid'] ?? 0);
        $sono = trim($params['sono'] ?? '');
        $psono = trim($params['psono'] ?? '');
        $vehicle_id = intval($params['vehicle_id'] ?? 0);
        $vehicleno = trim($params['vehicleno'] ?? '');
        $driver_id = trim($params['driver_id'] ?? '');
        $drivername = trim($params['drivername'] ?? '');
        $drivermobileno = trim($params['drivermobileno'] ?? '');
        $deliverydate = trim($params['deliverydate'] ?? date('Y-m-d'));
        if (empty($deliverydate) || $deliverydate === '0000-00-00') {
            $deliverydate = date('Y-m-d');
        }
        $total_plants = intval($params['total_allocated_plants'] ?? 0);
        $remarks = trim($params['remarks'] ?? '');
        $trip_id = trim($params['trip_id'] ?? '');
        $loginid = trim($params['loginid'] ?? 'Admin');
        $status = trim($params['status'] ?? 'Planned');

        if (empty($trip_id) && preg_match('/\[Trip:\s*([^\]]+)\]/', $remarks, $m)) {
            $trip_id = trim($m[1]);
        }

        if (empty($trip_id)) {
            if (!empty($vehicleno) || $vehicle_id > 0) {
                $chkTrip = $this->db->prepare("
                    SELECT trip_no FROM krf_vehicletrip 
                    WHERE ((vehicle_id = ? AND ? > 0) OR (vehicle_no = ? AND ? != ''))
                      AND delivery_date = ?
                      AND status NOT IN ('Cancelled', 'Delivered')
                    ORDER BY trip_id DESC LIMIT 1
                ");
                $chkTrip->bind_param("iisss", $vehicle_id, $vehicle_id, $vehicleno, $vehicleno, $deliverydate);
                $chkTrip->execute();
                $tRow = $chkTrip->get_result()->fetch_assoc();
                $chkTrip->close();
                if ($tRow && !empty($tRow['trip_no'])) {
                    $trip_id = $tRow['trip_no'];
                }
            }
        }

        if (empty($trip_id)) {
            $datePart = date('Ymd', strtotime($deliverydate));
            $cleanVeh = preg_replace('/[^A-Za-z0-9]/', '', $vehicleno);
            $vehSuffix = !empty($cleanVeh) ? substr($cleanVeh, -4) : 'VEH';
            $trip_id = "TRIP-{$datePart}-{$vehSuffix}-" . rand(100, 999);
        }

        // Upsert krf_vehicletrip
        $chkVt = $this->db->prepare("SELECT trip_id FROM krf_vehicletrip WHERE trip_no = ? LIMIT 1");
        $chkVt->bind_param("s", $trip_id);
        $chkVt->execute();
        $vtRes = $chkVt->get_result()->fetch_assoc();
        $chkVt->close();

        $trip_status = ($status === 'Dispatched') ? 'Dispatched' : 'Planned';

        if ($vtRes && !empty($vtRes['trip_id'])) {
            $updVt = $this->db->prepare("
                UPDATE krf_vehicletrip SET
                    project_id = COALESCE(NULLIF(?, 0), project_id),
                    vehicle_id = IF(? > 0, ?, vehicle_id),
                    vehicle_no = COALESCE(NULLIF(?, ''), vehicle_no),
                    driver_id = COALESCE(NULLIF(?, ''), driver_id),
                    driver_name = COALESCE(NULLIF(?, ''), driver_name),
                    driver_mobile_no = COALESCE(NULLIF(?, ''), driver_mobile_no),
                    delivery_date = COALESCE(NULLIF(?, ''), delivery_date),
                    move_for = COALESCE(NULLIF(?, ''), move_for),
                    status = IF(? = 'Dispatched', 'Dispatched', status),
                    remarks = COALESCE(NULLIF(?, ''), remarks),
                    updated_at = NOW()
                WHERE trip_id = ?
            ");
            $updVt->bind_param(
                "iiissssssssi",
                $projectid,
                $vehicle_id, $vehicle_id,
                $vehicleno,
                $driver_id,
                $drivername,
                $drivermobileno,
                $deliverydate,
                $deliverydate,
                $status,
                $remarks,
                $vtRes['trip_id']
            );
            $updVt->execute();
            $updVt->close();
        } else {
            $insVt = $this->db->prepare("
                INSERT INTO krf_vehicletrip (
                    trip_no, project_id, vehicle_id, vehicle_no, driver_id, driver_name, driver_mobile_no,
                    delivery_date, move_for, status, remarks, created_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $insVt->bind_param(
                "siisssssssss",
                $trip_id,
                $projectid,
                $vehicle_id,
                $vehicleno,
                $driver_id,
                $drivername,
                $drivermobileno,
                $deliverydate,
                $deliverydate,
                $trip_status,
                $remarks,
                $loginid
            );
            $insVt->execute();
            $insVt->close();
        }

        // Upsert krf_vehicle_travel_history
        $chkVth = $this->db->prepare("
            SELECT id FROM krf_vehicle_travel_history 
            WHERE ((stockorder_no = ? AND ? != '') OR (psono = ? AND ? != '')) 
            LIMIT 1
        ");
        $chkVth->bind_param("ssss", $sono, $sono, $psono, $psono);
        $chkVth->execute();
        $vthRes = $chkVth->get_result()->fetch_assoc();
        $chkVth->close();

        $history_status = ($status === 'Dispatched') ? 'Dispatched' : null;

        if ($vthRes && !empty($vthRes['id'])) {
            $updVth = $this->db->prepare("
                UPDATE krf_vehicle_travel_history SET
                    trip_id = ?,
                    projectid = COALESCE(NULLIF(?, 0), projectid),
                    psono = COALESCE(NULLIF(?, ''), psono),
                    stockorder_no = COALESCE(NULLIF(?, ''), stockorder_no),
                    vehicle_id = IF(? > 0, ?, vehicle_id),
                    vehicle_no = COALESCE(NULLIF(?, ''), vehicle_no),
                    driverid = COALESCE(NULLIF(?, ''), driverid),
                    driver_name = COALESCE(NULLIF(?, ''), driver_name),
                    driver_mobileno = COALESCE(NULLIF(?, ''), driver_mobileno),
                    send_totalplantcount = IF(? > 0, ?, send_totalplantcount),
                    deliverydate = COALESCE(NULLIF(?, ''), deliverydate),
                    status = IF(? = 'Dispatched', 'Dispatched', status),
                    send_remark = COALESCE(NULLIF(?, ''), send_remark),
                    updated_at = NOW()
                WHERE id = ?
            ");
            $updVth->bind_param(
                "sissiissssiisssi",
                $trip_id,
                $projectid,
                $psono,
                $sono,
                $vehicle_id, $vehicle_id,
                $vehicleno,
                $driver_id,
                $drivername,
                $drivermobileno,
                $total_plants, $total_plants,
                $deliverydate,
                $status,
                $remarks,
                $vthRes['id']
            );
            $updVth->execute();
            $updVth->close();
        } else {
            $insVth = $this->db->prepare("
                INSERT INTO krf_vehicle_travel_history (
                    trip_id, projectid, psono, stockorder_no, vehicle_id, vehicle_no,
                    driverid, driver_name, driver_mobileno, send_totalplantcount, deliverydate,
                    status, send_remark, created_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $insVth->bind_param(
                "sississssissss",
                $trip_id,
                $projectid,
                $psono,
                $sono,
                $vehicle_id,
                $vehicleno,
                $driver_id,
                $drivername,
                $drivermobileno,
                $total_plants,
                $deliverydate,
                $history_status,
                $remarks,
                $loginid
            );
            $insVth->execute();
            $insVth->close();
        }

        return $trip_id;
    }
}


// --------------------------------------------------------------------------
// Request Routing & Controller Handlers
// --------------------------------------------------------------------------
header('Content-Type: application/json; charset=utf-8');

try {
    $manager = new VehicleAllocationManager($krfconn, $authconn ?? null);
    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    if ($action == "GetActiveFinalDispatchDates" || $action == "GetActiveDates") {
        $projectid = intval($_POST['projectid'] ?? 0);
        $dates = $manager->getActiveDates($projectid);
        echo json_encode($dates);
        exit;

    } else if ($action == "GetVehicleAllocationOrders") {
        $projectid = intval($_POST['projectid'] ?? 0);
        $dateFrom = trim($_POST['date_from'] ?? '');
        $dateTo = trim($_POST['date_to'] ?? '');
        $filterStatus = trim($_POST['filter_status'] ?? 'all');
        $searchTerm = trim($_POST['search'] ?? '');

        $result = $manager->getOrders($projectid, $dateFrom, $dateTo, $filterStatus, $searchTerm);
        echo json_encode([
            "status" => "success",
            "data" => $result['orders'],
            "counts" => [
                "total" => $result['total'],
                "pending" => $result['pending'],
                "allocated" => $result['allocated']
            ]
        ]);
        exit;

    } else if ($action == "GetVehicleAllocationSuggestions" || $action == "GetAllSuggestions") {
        $projectid = intval($_POST['projectid'] ?? $_GET['projectid'] ?? 0);
        $res = $manager->getAllSuggestions($projectid);
        echo json_encode(["status" => "success", "data" => $res]);
        exit;

    } else if ($action == "SearchDrivers") {
        $term = trim($_POST['term'] ?? $_GET['term'] ?? '');
        $projectid = intval($_POST['projectid'] ?? $_GET['projectid'] ?? 0);
        $res = $manager->searchDrivers($term, $projectid);
        echo json_encode(["status" => "success", "data" => $res]);
        exit;

    } else if ($action == "SaveDriverQuick") {
        $drivername = trim($_POST['drivername'] ?? '');
        $drivermobileno = trim($_POST['drivermobileno'] ?? '');
        $projectid = intval($_POST['projectid'] ?? 0);
        $res = $manager->saveDriverQuick($drivername, $drivermobileno, $projectid);
        echo json_encode($res);
        exit;

    } else if ($action == "SaveVehicleQuick") {
        $vehicleno = trim($_POST['vehicleno'] ?? '');
        $vehiclename = trim($_POST['vehiclename'] ?? '');
        $vehicleownername = trim($_POST['vehicleownername'] ?? '');
        $plantscapacity = intval($_POST['plantscapacity'] ?? 0);
        $projectid = intval($_POST['projectid'] ?? 0);
        $res = $manager->saveVehicleQuick($vehicleno, $vehiclename, $vehicleownername, $plantscapacity, $projectid);
        echo json_encode($res);
        exit;

    } else if ($action == "CheckVehicleDateCapacity") {
        $vehicleno = trim($_POST['vehicleno'] ?? $_GET['vehicleno'] ?? '');
        $deliverydate = trim($_POST['deliverydate'] ?? $_GET['deliverydate'] ?? '');
        $exclude_sono = trim($_POST['exclude_sono'] ?? $_GET['exclude_sono'] ?? '');
        $exclude_psono = trim($_POST['exclude_psono'] ?? $_GET['exclude_psono'] ?? '');

        $res = $manager->checkVehicleDateCapacity($vehicleno, $deliverydate, $exclude_sono, $exclude_psono);
        echo json_encode($res);
        exit;

    } else if ($action == "GetActiveVehiclesMaster") {
        $projectid = intval($_POST['projectid'] ?? 0);
        $vehicles = $manager->getActiveVehicles($projectid);
        echo json_encode(["status" => "success", "data" => $vehicles]);
        exit;

    } else if ($action == "SaveOrderVehicleAllocation") {
        $res = $manager->allocateVehicle($_POST);
        echo json_encode($res);
        exit;

    } else if ($action == "GetFreightByVehicleType") {
        $vId = intval($_POST['vehicle_type_id'] ?? $_GET['vehicle_type_id'] ?? 0);
        $vName = trim($_POST['vehicle_type_name'] ?? $_GET['vehicle_type_name'] ?? '');
        $dist = floatval($_POST['distance'] ?? $_GET['distance'] ?? 0);
        $proj = intval($_POST['projectid'] ?? $_GET['projectid'] ?? 0);
        $psono = trim($_POST['psono'] ?? $_GET['psono'] ?? '');
        $sono = trim($_POST['sono'] ?? $_GET['sono'] ?? '');
        $totalQty = floatval($_POST['total_qty'] ?? $_GET['total_qty'] ?? 0);
        $pickupType = strtolower(trim($_POST['pickuptype'] ?? $_GET['pickuptype'] ?? 'delivered'));
        $isDelivered = ($pickupType === 'delivered' || $pickupType === 'farmdelivery' || $pickupType === 'farm delivery' || empty($pickupType));

        // Auto-resolve projectid if missing/zero
        if ($proj <= 0) {
            if (!empty($sono) || !empty($psono)) {
                $qSo = $krfconn->prepare("SELECT projectid FROM krf_sales_order WHERE (sono = ? AND ? != '') OR (psono = ? AND ? != '') LIMIT 1");
                if ($qSo) {
                    $qSo->bind_param("ssss", $sono, $sono, $psono, $psono);
                    $qSo->execute();
                    $rSo = $qSo->get_result()->fetch_assoc();
                    $qSo->close();
                    if ($rSo && intval($rSo['projectid']) > 0) {
                        $proj = intval($rSo['projectid']);
                    }
                }
            }
            if ($proj <= 0 && isset($_SESSION['projectid']) && intval($_SESSION['projectid']) > 0) {
                $proj = intval($_SESSION['projectid']);
            }
            if ($proj <= 0) {
                $proj = 17; // Standard default project
            }
        }

        // 1. If vId is 0, attempt lookup by vehicle type name from master tables
        $plantsCapacity = 0;
        if ($vId <= 0 && !empty($vName)) {
            $qVt = $krfconn->prepare("SELECT id, plantscapacity FROM krf_vehicletype_master WHERE LOWER(TRIM(vehicletypename)) = LOWER(TRIM(?)) OR ? LIKE CONCAT('%', vehicletypename, '%') ORDER BY (LOWER(TRIM(vehicletypename)) = LOWER(TRIM(?))) DESC LIMIT 1");
            if ($qVt) {
                $qVt->bind_param("sss", $vName, $vName, $vName);
                $qVt->execute();
                $rVt = $qVt->get_result()->fetch_assoc();
                if ($rVt && !empty($rVt['id'])) {
                    $vId = intval($rVt['id']);
                    $plantsCapacity = floatval($rVt['plantscapacity'] ?? 0);
                }
                $qVt->close();
            }
        }
        if ($vId <= 0 && !empty($vName)) {
            $qVm = $krfconn->prepare("SELECT vehicleid, plantscapacity FROM krf_vehicle_master WHERE LOWER(TRIM(vehiclename)) = LOWER(TRIM(?)) OR ? LIKE CONCAT('%', vehiclename, '%') ORDER BY (LOWER(TRIM(vehiclename)) = LOWER(TRIM(?))) DESC LIMIT 1");
            if ($qVm) {
                $qVm->bind_param("sss", $vName, $vName, $vName);
                $qVm->execute();
                $rVm = $qVm->get_result()->fetch_assoc();
                if ($rVm && !empty($rVm['vehicleid'])) {
                    $vId = intval($rVm['vehicleid']);
                    if ($plantsCapacity <= 0) {
                        $plantsCapacity = floatval($rVm['plantscapacity'] ?? 0);
                    }
                }
                $qVm->close();
            }
        }

        // Get plantscapacity if not yet resolved
        if ($plantsCapacity <= 0 && $vId > 0) {
            $capStmt = $krfconn->prepare("
                SELECT plantscapacity FROM krf_vehicle_master WHERE vehicleid = ? 
                UNION 
                SELECT plantscapacity FROM krf_vehicletype_master WHERE id = ?
                LIMIT 1
            ");
            if ($capStmt) {
                $capStmt->bind_param("ii", $vId, $vId);
                $capStmt->execute();
                $rCap = $capStmt->get_result()->fetch_assoc();
                if ($rCap) {
                    $plantsCapacity = floatval($rCap['plantscapacity'] ?? 0);
                }
                $capStmt->close();
            }
        }

        $baseFreight = 0;
        $calcType = 'fixed';
        $chargeId = 0;
        $distMin = 0;
        $distMax = 0;
        $rawCharge = 0;
        $isReturnJourney = false;
        $tripsNeeded = 1;

        if ($vId > 0 && $dist > 0) {
            // Find slab in krf_freight_master
            $stmt = $krfconn->prepare("
                SELECT chargeid, freight_charge, calculationtype, dist_min, dist_max 
                FROM krf_freight_master 
                WHERE vehicleid = ? 
                  AND ? BETWEEN dist_min AND dist_max 
                  AND (projectid = ? OR ? = 0 OR projectid = 17 OR projectid = 0)
                ORDER BY (projectid = ?) DESC, chargeid DESC
                LIMIT 1
            ");
            if ($stmt) {
                $stmt->bind_param("idiii", $vId, $dist, $proj, $proj, $proj);
                $stmt->execute();
                $fmRow = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($fmRow) {
                    $chargeId = intval($fmRow['chargeid']);
                    $calcType = strtolower($fmRow['calculationtype'] ?? 'fixed');
                    $rawCharge = floatval($fmRow['freight_charge']);
                    $distMin = intval($fmRow['dist_min']);
                    $distMax = intval($fmRow['dist_max']);
                }
            }

            // Fallback for distance > highest slab
            if ($rawCharge <= 0) {
                $stmt2 = $krfconn->prepare("
                    SELECT chargeid, freight_charge, calculationtype, dist_min, dist_max 
                    FROM krf_freight_master 
                    WHERE vehicleid = ? 
                      AND ? >= dist_min 
                      AND (projectid = ? OR ? = 0 OR projectid = 17 OR projectid = 0)
                    ORDER BY (projectid = ?) DESC, dist_max DESC, chargeid DESC LIMIT 1
                ");
                if ($stmt2) {
                    $stmt2->bind_param("idiii", $vId, $dist, $proj, $proj, $proj);
                    $stmt2->execute();
                    $fmRow2 = $stmt2->get_result()->fetch_assoc();
                    $stmt2->close();

                    if ($fmRow2) {
                        $chargeId = intval($fmRow2['chargeid']);
                        $calcType = strtolower($fmRow2['calculationtype'] ?? 'fixed');
                        $rawCharge = floatval($fmRow2['freight_charge']);
                        $distMin = intval($fmRow2['dist_min']);
                        $distMax = intval($fmRow2['dist_max']);
                    }
                }
            }

            // Fallback by vehicle name if specific vehicle registration has no direct slab
            if ($rawCharge <= 0 && !empty($vName)) {
                $fbStmt = $krfconn->prepare("
                    SELECT f2.chargeid, f2.freight_charge, f2.calculationtype, f2.dist_min, f2.dist_max, vm2.plantscapacity
                    FROM krf_freight_master f2
                    JOIN krf_vehicle_master vm2 ON f2.vehicleid = vm2.vehicleid
                    WHERE (LOWER(TRIM(vm2.vehiclename)) = LOWER(TRIM(?)) OR ? LIKE CONCAT('%', vm2.vehiclename, '%'))
                      AND ? BETWEEN f2.dist_min AND f2.dist_max 
                      AND (f2.projectid = ? OR ? = 0 OR f2.projectid = 17 OR f2.projectid = 0) AND f2.freight_charge > 0
                    ORDER BY (f2.projectid = ?) DESC, f2.chargeid DESC
                    LIMIT 1
                ");
                if ($fbStmt) {
                    $fbStmt->bind_param("ssdiii", $vName, $vName, $dist, $proj, $proj, $proj);
                    $fbStmt->execute();
                    $fbRes = $fbStmt->get_result()->fetch_assoc();
                    $fbStmt->close();
                    if ($fbRes) {
                        $chargeId = intval($fbRes['chargeid']);
                        $calcType = strtolower($fbRes['calculationtype'] ?? 'fixed');
                        $rawCharge = floatval($fbRes['freight_charge']);
                        $distMin = intval($fbRes['dist_min']);
                        $distMax = intval($fbRes['dist_max']);
                        if ($plantsCapacity <= 0) {
                            $plantsCapacity = floatval($fbRes['plantscapacity'] ?? 0);
                        }
                    }
                }
            }

            // Calculation matching addNewProvisionalsaleorder.php:
            // If rate and farm delivery / delivered, double KM for onward + return journeys
            if ($calcType === 'rate') {
                if ($isDelivered) {
                    $baseFreight = round($rawCharge * $dist * 2, 2);
                    $isReturnJourney = true;
                } else {
                    $baseFreight = round($rawCharge * $dist, 2);
                }
            } else {
                $baseFreight = $rawCharge;
            }

            // Plant capacity check for multiple trips needed
            if ($totalQty > 0 && $plantsCapacity > 0 && $totalQty > $plantsCapacity) {
                $tripsNeeded = (int)ceil($totalQty / $plantsCapacity);
                $baseFreight = round($baseFreight * $tripsNeeded, 2);
            }
        } else if ($dist > 0 && function_exists('getFreightSuggestion')) {
            // Auto suggestion fallback when no vehicle selected yet
            $sug = getFreightSuggestion($krfconn, $proj, $dist, $totalQty);
            if ($sug && !empty($sug['best_vehicle'])) {
                $bv = $sug['best_vehicle'];
                $vId = intval($bv['vehicleid']);
                $vName = $bv['vehiclename'];
                $calcType = strtolower($bv['calculationtype'] ?? 'fixed');
                $rawCharge = floatval($bv['raw_charge'] ?? 0);
                $baseFreight = floatval($bv['charge'] ?? 0);
                $plantsCapacity = floatval($bv['plantscapacity'] ?? 0);
                $tripsNeeded = intval($sug['trips_needed'] ?? 1);

                if ($calcType === 'rate' && $isDelivered) {
                    $baseFreight = round($baseFreight * 2, 2);
                    $isReturnJourney = true;
                }
            }
        }

        // Check if there is an existing discount percentage in krf_freightcharges
        $discPct = 0;
        $discAmt = 0;
        if (!empty($psono) || !empty($sono)) {
            $chkDisc = $krfconn->prepare("SELECT freight_discountpercent FROM krf_freightcharges WHERE (psono = ? AND ? != '') OR (sono = ? AND ? != '') ORDER BY id DESC LIMIT 1");
            if ($chkDisc) {
                $chkDisc->bind_param("ssss", $psono, $psono, $sono, $sono);
                $chkDisc->execute();
                $rDisc = $chkDisc->get_result()->fetch_assoc();
                $chkDisc->close();
                if ($rDisc && floatval($rDisc['freight_discountpercent']) > 0) {
                    $discPct = floatval($rDisc['freight_discountpercent']);
                }
            }
        }

        if ($discPct > 0 && $baseFreight > 0) {
            $discAmt = round($baseFreight * ($discPct / 100), 2);
        }
        $payableFreight = max(0, round($baseFreight - $discAmt, 2));

        echo json_encode([
            "status" => "success",
            "vehicle_type_id" => $vId,
            "vehicle_type_name" => $vName,
            "distance" => $dist,
            "chargeid" => $chargeId,
            "raw_charge" => $rawCharge,
            "base_freight" => $baseFreight,
            "discount_percent" => $discPct,
            "discount_amount" => $discAmt,
            "payable_freight" => $payableFreight,
            "calculationtype" => $calcType,
            "is_return_journey" => $isReturnJourney,
            "trips_needed" => $tripsNeeded,
            "plantscapacity" => $plantsCapacity,
            "dist_min" => $distMin,
            "dist_max" => $distMax
        ]);
        exit;

    } else if ($action == "DeleteOrderVehicleAllocation") {
        $res = $manager->removeAllocation($_POST);
        echo json_encode($res);
        exit;

    } else if ($action == "GetMasterStates") {
        $sql = "SELECT stateid, statename FROM master_states WHERE (status = 'Active' OR IFNULL(status, '') = '') ORDER BY statename ASC";
        $res = $krfconn->query($sql);
        $states = [];
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $states[] = $r;
            }
        }
        echo json_encode(["status" => "success", "data" => $states]);
        exit;

    } else if ($action == "GetMasterDistricts") {
        $state = trim($_POST['state'] ?? $_GET['state'] ?? '');
        $districts = [];

        if (!empty($state)) {
            $stmt = $krfconn->prepare("
                SELECT d.districtid, d.districtname, d.stateid 
                FROM master_districts d
                JOIN master_states s ON d.stateid = s.stateid
                WHERE (LOWER(TRIM(s.statename)) = LOWER(?) OR s.stateid = ?)
                  AND (d.status = 'Active' OR IFNULL(d.status, '') = '')
                ORDER BY d.districtname ASC
            ");
            $stmt->bind_param("ss", $state, $state);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res) {
                while ($r = $res->fetch_assoc()) {
                    $districts[] = $r;
                }
            }
            $stmt->close();
        } else {
            $res = $krfconn->query("SELECT districtid, districtname, stateid FROM master_districts WHERE (status = 'Active' OR IFNULL(status, '') = '') ORDER BY districtname ASC");
            if ($res) {
                while ($r = $res->fetch_assoc()) {
                    $districts[] = $r;
                }
            }
        }
        echo json_encode(["status" => "success", "data" => $districts]);
        exit;

    } else if ($action == "SaveManualDeliveryAddress") {
        $sono = trim($_POST['sono'] ?? '');
        $psono = trim($_POST['psono'] ?? $_POST['orderid'] ?? '');
        $clientid = trim($_POST['clientid'] ?? '');
        $projectid = intval($_POST['projectid'] ?? 0);
        $state = trim($_POST['state'] ?? '');
        $district = trim($_POST['district'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $block = trim($_POST['block'] ?? '');
        $pincode = trim($_POST['pincode'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $distance_km = floatval($_POST['distance_km'] ?? 0);
        $remarks = trim($_POST['remarks'] ?? '');
        $latitude = !empty($_POST['latitude']) ? floatval($_POST['latitude']) : null;
        $longitude = !empty($_POST['longitude']) ? floatval($_POST['longitude']) : null;
        $loginid = trim($_SESSION['loginname'] ?? $_SESSION['username'] ?? 'Admin');

        if (empty($sono) && !empty($psono)) {
            $qSo = $krfconn->query("SELECT sono, clientid, projectid FROM krf_sales_order WHERE psono = '" . $krfconn->real_escape_string($psono) . "' LIMIT 1");
            if ($qSo && $rSo = $qSo->fetch_assoc()) {
                $sono = $rSo['sono'] ?? '';
                if (empty($clientid)) $clientid = $rSo['clientid'] ?? '';
                if (empty($projectid)) $projectid = intval($rSo['projectid'] ?? 0);
            }
        }

        if (empty($sono) && empty($psono)) {
            echo json_encode(["status" => "error", "message" => "Sales order number is required."]);
            exit;
        }

        $full_address = !empty($address) ? $address : implode(', ', array_filter([$city, $block, $district, $state, !empty($pincode) ? "PIN: {$pincode}" : '']));

        // Check if record exists in krf_deliveryaddress_location
        $chkStmt = $krfconn->prepare("SELECT id FROM krf_deliveryaddress_location WHERE (psono = ? AND ? != '') OR (sono = ? AND ? != '') ORDER BY id DESC LIMIT 1");
        $chkStmt->bind_param("ssss", $psono, $psono, $sono, $sono);
        $chkStmt->execute();
        $dlRow = $chkStmt->get_result()->fetch_assoc();
        $chkStmt->close();

        if ($dlRow && !empty($dlRow['id'])) {
            $locId = intval($dlRow['id']);
            $updLoc = $krfconn->prepare("
                UPDATE krf_deliveryaddress_location 
                SET dest_state = ?, dest_district = ?, dest_city = ?, dest_pincode = ?, 
                    deliveryaddress = ?, distance = ?, totaldistance = ?, 
                    dest_latitude = COALESCE(?, dest_latitude), dest_longitude = COALESCE(?, dest_longitude),
                    remark = CASE WHEN ? != '' THEN ? ELSE remark END,
                    pickuptype = 'delivered',
                    updated_at = NOW(), updated_by = ?
                WHERE id = ?
            ");
            $updLoc->bind_param("sssssddddsssi", $state, $district, $city, $pincode, $full_address, $distance_km, $distance_km, $latitude, $longitude, $remarks, $remarks, $loginid, $locId);
            $updLoc->execute();
            $updLoc->close();
        } else {
            $insLoc = $krfconn->prepare("
                INSERT INTO krf_deliveryaddress_location 
                (projectid, sono, psono, clientid, dest_state, dest_district, dest_city, dest_pincode, deliveryaddress, distance, totaldistance, freedistance, dest_latitude, dest_longitude, remark, pickuptype, status, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, 'delivered', 'Pending', ?, NOW())
            ");
            $insLoc->bind_param("issssssssddddss", $projectid, $sono, $psono, $clientid, $state, $district, $city, $pincode, $full_address, $distance_km, $distance_km, $latitude, $longitude, $remarks, $loginid);
            $insLoc->execute();
            $insLoc->close();
        }

        // Calculate and update freight charges if distance > 0 and currently zero
        $calcFreight = 0;
        if ($distance_km > 0) {
            @include_once __DIR__ . '/../../functions/vehicle_functions.php';
            if (function_exists('getFreightSuggestion')) {
                $freightRes = getFreightSuggestion($krfconn, $projectid, $distance_km, 0);
                if (!empty($freightRes['recommended_charge']) && floatval($freightRes['recommended_charge']) > 0) {
                    $calcFreight = floatval($freightRes['recommended_charge']);
                    $escPsono = $krfconn->real_escape_string($psono);
                    $escSono = $krfconn->real_escape_string($sono);
                    $krfconn->query("UPDATE krf_deliveryaddress_location SET deliverycharges = CASE WHEN IFNULL(deliverycharges,0) = 0 THEN {$calcFreight} ELSE deliverycharges END, freightamt = CASE WHEN IFNULL(freightamt,0) = 0 THEN {$calcFreight} ELSE freightamt END WHERE (psono = '{$escPsono}' AND '{$escPsono}' != '') OR (sono = '{$escSono}' AND '{$escSono}' != '')");
                }
            }
        }

        // Update sales order table delivery address
        $updSo = $krfconn->prepare("UPDATE krf_sales_order SET delivery_address = ?, districtname = ?, statename = ? WHERE (psono = ? AND ? != '') OR (sono = ? AND ? != '')");
        if ($updSo) {
            $updSo->bind_param("sssssss", $full_address, $district, $state, $psono, $psono, $sono, $sono);
            $updSo->execute();
            $updSo->close();
        }

        echo json_encode([
            "status" => "success", 
            "message" => "Delivery address updated successfully!",
            "data" => [
                "dest_city" => $city,
                "dest_district" => $district,
                "dest_state" => $state,
                "dest_pincode" => $pincode,
                "deliveryaddress" => $full_address,
                "distance" => $distance_km,
                "dest_latitude" => $latitude,
                "dest_longitude" => $longitude,
                "remark" => $remarks,
                "base_freight" => $calcFreight,
                "payable_freight" => $calcFreight
            ]
        ]);
        exit;

    } else if ($action == "UpdateDeliveryType") {
        $sono = trim($_POST['sono'] ?? '');
        $psono = trim($_POST['psono'] ?? '');
        $clientid = trim($_POST['clientid'] ?? '');
        $projectid = intval($_POST['projectid'] ?? 0);
        $pickuptype = trim($_POST['pickuptype'] ?? 'delivered');
        $loginid = trim($_SESSION['loginname'] ?? $_SESSION['username'] ?? 'Admin');

        // Check if row exists in krf_deliveryaddress_location
        $chk = $krfconn->prepare("SELECT id FROM krf_deliveryaddress_location WHERE (psono = ? AND ? != '') OR (sono = ? AND ? != '') ORDER BY id DESC LIMIT 1");
        $chk->bind_param("ssss", $psono, $psono, $sono, $sono);
        $chk->execute();
        $dlRow = $chk->get_result()->fetch_assoc();
        $chk->close();

        if ($dlRow && !empty($dlRow['id'])) {
            $upd = $krfconn->prepare("UPDATE krf_deliveryaddress_location SET pickuptype = ?, updated_at = NOW(), updated_by = ? WHERE id = ?");
            $upd->bind_param("ssi", $pickuptype, $loginid, $dlRow['id']);
            $upd->execute();
            $upd->close();
        } else {
            $ins = $krfconn->prepare("INSERT INTO krf_deliveryaddress_location (projectid, sono, psono, clientid, pickuptype, status, created_by, created_at) VALUES (?, ?, ?, ?, ?, 'Pending', ?, NOW())");
            $ins->bind_param("isssss", $projectid, $sono, $psono, $clientid, $pickuptype, $loginid);
            $ins->execute();
            $ins->close();
        }
        echo json_encode(["status" => "success", "message" => "Delivery mode updated to {$pickuptype}"]);
        exit;

    } else if (!empty($action)) {
        echo json_encode(["status" => "error", "message" => "Invalid action specified."]);
        exit;
    }
} catch (\Throwable $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    exit;
}