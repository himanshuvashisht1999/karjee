<?php
include('../../connection.php');
$action = $_POST['action'] ?? '';
header('Content-Type: application/json; charset=utf-8');

 if ($action == 'fetch_dashboard_stats') {
    $stats = [
        'transit' => 0,
        'transit_totalplants' => 0,
        'loading' => 0,
        'loading_totalplants' => 0,
    ];
    $res_t = $krfconn->query("
        SELECT
            COUNT(DISTINCT trip_id) AS cnt,
            SUM(send_totalplantcount) AS totalplants
        FROM krf_vehicle_travel_history
        WHERE TRIM(LCASE(status)) = 'in transit'
    ");
    if ($res_t) {
        $row = $res_t->fetch_assoc();
        $stats['transit'] = (int)$row['cnt'];
        $stats['transit_totalplants'] = (int)$row['totalplants'];
    }

    $res_t = $krfconn->query("
        SELECT
            COUNT(DISTINCT trip_id) AS cnt,
            SUM(send_totalplantcount) AS totalplants
        FROM krf_vehicle_travel_history
        WHERE TRIM(LCASE(status)) = 'dispatched'
    ");

    if ($res_t) {
        $row = $res_t->fetch_assoc();
        $stats['loading'] = (int)$row['cnt'];
        $stats['loading_totalplants'] = (int)$row['totalplants'];
    }

    echo json_encode($stats);
}else if ($action === 'update_trip_intransit') {
    header('Content-Type: application/json');
    $tripid = trim($_POST['tripid'] ?? '');
    if ($tripid === '') {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Trip ID is required.'
        ]);
        exit;
    } 
    $status = 'In Transit';
    $stmt = $krfconn->prepare("
        UPDATE krf_vehicle_travel_history
        SET status = ?
        WHERE trip_id = ?
          AND status = 'Dispatched'
    ");
    if (!$stmt) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Query preparation failed: ' . $krfconn->error
        ]);
        exit;
    }
    $stmt->bind_param('ss', $status, $tripid);
    if (!$stmt->execute()) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Unable to update trip: ' . $stmt->error
        ]);
        $stmt->close();
        exit;
    }

    if ($stmt->affected_rows > 0) {
        echo json_encode([
            'status'  => 'success',
            'message' => "Trip ID {$tripid} has been marked as In Transit."
        ]);
    } else {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Trip was not found, is already In Transit, or is not in Dispatched status.'
        ]);
    }

    $stmt->close();
    exit;
}

 
else if ($action === 'fetch_vehicleinloading') {
    $html = '';
        $query = "
        SELECT
            h.from_location_name,
            h.from_location_id,
            h.deliverydate,
            h.dispatch_time,
            h.status,
            h.trip_id,
            h.vehicle_no,
            h.driver_name,
            h.driver_mobileno,
            h.vehicle_id,
            SUM(COALESCE(h.send_traycount, 0)) AS totaltray,
            SUM(COALESCE(h.send_totalplantcount, 0)) AS totalplants,            
            m.plantscapacity AS vehicleplantcapacity
        FROM krf_vehicle_travel_history h
        INNER JOIN krf_vehicle_master m
            ON m.vehicleid = h.vehicle_id
        WHERE lcase(h.status) = 'dispatched'
        GROUP BY
            h.trip_id,
            h.from_location_name,
            h.from_location_id,
            h.vehicle_id
        ORDER BY h.trip_id DESC
    ";
    $result = $krfconn->query($query);
    if (!$result) {
        echo '<tr>
                <td colspan="8" class="text-center text-danger">
                    Query failed: ' . htmlspecialchars($krfconn->error) . '
                </td>
              </tr>';
        exit;
    }
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $tripId = htmlspecialchars(
                (string) $row['trip_id'],
                ENT_QUOTES,
                'UTF-8'
            );
            $vehicleno = htmlspecialchars(
                (string) $row['vehicle_no'],
                ENT_QUOTES,
                'UTF-8'
            );
            $moveActionBtn = '
                <button type="button"
                        class="btn btn-sm btn-success"
                        onclick="finalmove(\'' . $tripId . '\' ,\'' . $vehicleno . '\')">
                    <i class="fa fa-truck-moving me-1"></i>
                    Confirm Ready To Move
                </button>';
            $transitActionBtn = '
                <button type="button"
                        class="btn btn-sm btn-primary"
                        onclick="edittripdetails(\'' . $tripId . '\')">
                    <i class="fa fa-edit me-1"></i>
                    Edit
                </button>';
            $html .= '<tr>';
            $html .= '<td class="text-center">' .
                        $moveActionBtn .
                     '</td>';
            $html .= '<td class="text-center">' .
                        $transitActionBtn .
                     '</td>';
            $html .= '<td>' .
                        htmlspecialchars(
                            (string) $row['from_location_name']
                        ) .
                     '</td>';
            $html .= '<td>' .
                        htmlspecialchars(
                            (string) $row['vehicle_no']
                        ) .
                     '</td>';
            $html .= '<td>' .
                        htmlspecialchars(
                            (string) $row['driver_name']
                        ) .
                     '</td>';
            $html .= '<td>' .
                        htmlspecialchars(
                            (string) $row['driver_mobileno']
                        ) .
                     '</td>';
            $html .= '<td class="text-end">' .
                        number_format(
                            (float) $row['vehicleplantcapacity']
                        ) .
                     '</td>';
            $html .= '<td class="text-end">' .
                        number_format(
                            (float) $row['totalplants']
                        ) .
                     '</td>';
            $html .= '</tr>';
        }

    } else {
        $html = '
            <tr>
                <td colspan="8"
                    class="text-center text-muted py-4">
                    No vehicles currently in loading or transit.
                </td>
            </tr>';
    }
    $orderlist =orderlist($krfconn,$hrmconn);
  
     echo json_encode([
            'success' => true,
            'loading' => $html,
            'orderlist' => $orderlist
        ]);
    exit;
} else  if ($action === 'loadingmasters') {
    $existingtrip=[];
    $response = [
        'status'  => false,
        'message' => '',
        'data'    => [
            'locations' => [],
            'vehicles'  => [],
            'drivers'   => [],
            'purposes'  => [],
            'existingdata' =>[],
        ]
    ];

    try {
        $projectid = filter_input(
            INPUT_POST,
            'projectid',
            FILTER_VALIDATE_INT
        );

        $selectedDriverId = '';//trim( (string) ($_POST['selected_driver_id'] ?? '') );

        if (!$projectid || $projectid <= 0) {
            throw new InvalidArgumentException(
                'Valid project ID is required.'
            );
        }

         $tripid = trim((string) ($_POST['tripid'] ?? '')  );
        if($tripid){
            $existingtrip=edit_tripid($krfconn, $projectid, $tripid );
        }
        $response['data'] = [
            'locations' => location_master(
                $hrmconn,
                $projectid
            ),

            'vehicles' => vehicle_master(
                $krfconn,
                $projectid
            ),

            'drivers' => driver_master(
                $krfconn,
                $projectid,
                $selectedDriverId
            ),

            'purposes' => purpose_master(),
            'existingtrip' =>$existingtrip
        ];

        $response['status'] = true;
        $response['message'] = 'Master data loaded successfully.';

    } catch (InvalidArgumentException $e) {
        http_response_code(422);

        $response['message'] = $e->getMessage();

    } catch (Throwable $e) {
        http_response_code(500);

        $response['message'] = 'Unable to load master data.';

        // Log the actual technical error instead of showing it to users.
        error_log(
            'loadingmasters error: ' . $e->getMessage()
        );
    }

    echo json_encode(
        $response,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}else if ($action === 'get_freight_details') { 
    try { 
        $vehicleId = (int) ($_POST['vehicle_id'] ?? 0);
        $currentTripId = trim((string) ($_POST['trip_id'] ?? ''));
        $sourceLocationId = trim((string) ($_POST['sourcelocation'] ?? ''));
        $distance = (float) ($_POST['distance'] ?? 0);
        if ($vehicleId <= 0) {
            throw new InvalidArgumentException('Please select a valid vehicle.');
        }
        $trayCapacity = 0;
        $plantCapacity = 0;

        $activeTrays = 0;
        $activePlants = 0;

        $thisTripTrays = 0;
        $thisTripPlants = 0;

        $amount = 0.00;
        $discount = 0.00;
        $payable = 0.00;
        $freightFound = false;

        $todayTrips = [];
        $todayDriverId = '';
        $todayDriverName = '';

        /*
        | Vehicle capacity and current active trip*/
        

        $vehicleSql = "
            SELECT
                m.vehicleid,
                m.vehicleno,
                m.vehiclename,
                COALESCE(m.traycapacity, 0) AS traycapacity,
                COALESCE(m.plantscapacity, 0) AS plantscapacity,
                vh.trip_id,
                vh.from_location_id,
                vh.from_location_name,
                vh.status
            FROM krf_vehicle_master m
            LEFT JOIN krf_vehicle_travel_history vh
                ON vh.vehicle_id = m.vehicleid
               AND vh.status  in  ('Dispatched' ,'In Transit')
            WHERE m.vehicleid = ?
              AND m.status = 'Active'
            ORDER BY vh.id DESC
            LIMIT 1     ";
        $vehicleStmt = $krfconn->prepare($vehicleSql);
        if (!$vehicleStmt) {
            throw new RuntimeException(
                'Vehicle query preparation failed: ' . $krfconn->error
            );
        }
        $vehicleStmt->bind_param('i', $vehicleId);

        if (!$vehicleStmt->execute()) {
            throw new RuntimeException(
                'Vehicle query execution failed: ' . $vehicleStmt->error
            );
        }
        $vehicleResult = $vehicleStmt->get_result();
        $vehicleRow = $vehicleResult->fetch_assoc();

        $vehicleStmt->close();

        if (!$vehicleRow) {
            throw new RuntimeException('Selected vehicle was not found or is inactive.');
        }

        $trayCapacity = (int) ($vehicleRow['traycapacity'] ?? 0);
        $plantCapacity = (int) ($vehicleRow['plantscapacity'] ?? 0);

        /*
        |--------------------------------------------------------------------------
        | Check vehicle active dispatch location
        |--------------------------------------------------------------------------
        */

        if ($vehicleRow['trip_id'] && $vehicleRow['trip_id'] !=$currentTripId) {
            
                echo json_encode([
                    'status' => 'Plants are currently loading',
                    'message' =>
                        'The selected vehicle is currently loading from location: ' .
                        ($vehicleRow['from_location_name'] ?? 'Unknown location')
                ]);

                exit;
            

        }

        /*
        |--------------------------------------------------------------------------
        | Active quantities excluding current trip
        |--------------------------------------------------------------------------
        */

         $activeSql = "
            SELECT
                COALESCE(SUM(send_traycount), 0) AS active_trays,
                COALESCE(SUM(send_totalplantcount), 0) AS active_plants
            FROM krf_vehicle_travel_history
            WHERE vehicle_id = ?
              AND status IN ('Dispatched')
              AND (? = '' OR trip_id <> ?)
        ";

        $activeStmt = $krfconn->prepare($activeSql);

        if (!$activeStmt) {
            throw new RuntimeException(
                'Active quantity query preparation failed: ' .
                $krfconn->error
            );
        }

        $activeStmt->bind_param(
            'iss',
            $vehicleId,
            $currentTripId,
            $currentTripId
        );

        if (!$activeStmt->execute()) {
            throw new RuntimeException(
                'Active quantity query execution failed: ' .
                $activeStmt->error
            );
        }

        $activeRow = $activeStmt
            ->get_result()
            ->fetch_assoc();

        $activeStmt->close();

        $activeTrays = (int) ($activeRow['active_trays'] ?? 0);
        $activePlants = (int) ($activeRow['active_plants'] ?? 0);

        /*
        |--------------------------------------------------------------------------
        | Current trip quantities
        |--------------------------------------------------------------------------
        */

        if ($currentTripId !== '') {
             $tripQuantitySql = "
                SELECT
                    COALESCE(SUM(send_traycount), 0) AS this_trays,
                    COALESCE(SUM(send_totalplantcount), 0) AS this_plants
                FROM krf_vehicle_travel_history
                WHERE trip_id = ?
            ";

            $tripQuantityStmt = $krfconn->prepare($tripQuantitySql);

            if (!$tripQuantityStmt) {
                throw new RuntimeException(
                    'Trip quantity query preparation failed: ' .
                    $krfconn->error
                );
            }

            $tripQuantityStmt->bind_param('s', $currentTripId);

            if (!$tripQuantityStmt->execute()) {
                throw new RuntimeException(
                    'Trip quantity query execution failed: ' .
                    $tripQuantityStmt->error
                );
            }

            $tripQuantityRow = $tripQuantityStmt
                ->get_result()
                ->fetch_assoc();

            $tripQuantityStmt->close();

            $thisTripTrays = (int) (
                $tripQuantityRow['this_trays'] ?? 0
            );

            $thisTripPlants = (int) (
                $tripQuantityRow['this_plants'] ?? 0
            );
        }

        $totalAssignedTrays = $activeTrays + $thisTripTrays;
        $totalAssignedPlants = $activePlants + $thisTripPlants;
 
 

        if (!$freightFound && $distance > 0) {
             $masterFreightSql = "
                SELECT COALESCE(freight_charge, 0) AS freight_charge, COALESCE(calculationtype, 'fixed') AS calculationtype
                FROM krf_freight_master
                WHERE vehicleid = ?
                  AND ? BETWEEN dist_min AND dist_max
                ORDER BY dist_min ASC
                LIMIT 1
            ";

            $masterFreightStmt = $krfconn->prepare(
                $masterFreightSql
            );

            if (!$masterFreightStmt) {
                throw new RuntimeException(
                    'Freight master query preparation failed: ' .
                    $krfconn->error
                );
            }

            $masterFreightStmt->bind_param(
                'id',
                $vehicleId,
                $distance
            );

            if (!$masterFreightStmt->execute()) {
                throw new RuntimeException(
                    'Freight master query execution failed: ' .
                    $masterFreightStmt->error
                );
            }

            $masterFreightRow = $masterFreightStmt
                ->get_result()
                ->fetch_assoc();

            $masterFreightStmt->close();

            if ($masterFreightRow) {
                $amount = (float) (
                    $masterFreightRow['freight_charge'] ?? 0
                );
                if (($masterFreightRow['calculationtype'] ?? 'fixed') === 'rate') {
                    $amount = $amount * (float) $distance;
                }

                $discount = 0;
                $payable = $amount;
                $freightFound = $amount > 0;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Today's trips with driver ID
        |--------------------------------------------------------------------------
        |
        | Driver lookup is handled through LEFT JOIN, avoiding a query
        | inside the while loop.
        |--------------------------------------------------------------------------
        */

         $todayTripsSql = "
            SELECT
                vh.trip_id,
                vh.from_location_name,
                vh.to_location_name,
                vh.driver_name,
                vh.status,
                COALESCE(SUM(vh.send_traycount), 0) AS trays,
                COALESCE(SUM(vh.send_totalplantcount), 0) AS plants,
                DATE_FORMAT(
                    MIN(vh.dispatch_time),
                    '%H:%i'
                ) AS d_time,
                MAX(dm.driverid) AS driver_id
            FROM krf_vehicle_travel_history vh
            LEFT JOIN krf_vehicle_driver_master dm
                ON TRIM(dm.drivername) = TRIM(vh.driver_name)
            WHERE vh.vehicle_id = ?  and vh.status  IN ('Dispatched' )
            GROUP BY
                vh.trip_id,
                vh.from_location_name,
                vh.to_location_name,
                vh.driver_name,
                vh.status 
        ";

        $todayTripsStmt = $krfconn->prepare($todayTripsSql);

        if (!$todayTripsStmt) {
            throw new RuntimeException(
                'Today trips query preparation failed: ' .
                $krfconn->error
            );
        }

        $todayTripsStmt->bind_param('i', $vehicleId);

        if (!$todayTripsStmt->execute()) {
            throw new RuntimeException(
                'Today trips query execution failed: ' .
                $todayTripsStmt->error
            );
        }

        $todayTripsResult = $todayTripsStmt->get_result();

        while ($tripRow = $todayTripsResult->fetch_assoc()) {
            $todayTrips[] = [
                'trip_id' => $tripRow['trip_id'],
                'from' => $tripRow['from_location_name'],
                'to' => $tripRow['to_location_name'],
                'driver_name' => $tripRow['driver_name'],
                'status' => $tripRow['status'],
                'trays' => (int) $tripRow['trays'],
                'plants' => (int) $tripRow['plants'],
                'time' => $tripRow['d_time']
            ];

            if (
                $todayDriverId === '' &&
                !empty($tripRow['driver_id'])
            ) {
                $todayDriverId = (string) $tripRow['driver_id'];
                $todayDriverName = (string) (
                    $tripRow['driver_name'] ?? ''
                );
            }
        }

        $todayTripsStmt->close();

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        echo json_encode([
            'status' => 'success',
            'message' => 'Freight details loaded successfully.',
            'data' => [
                'current_trip_id' => $currentTripId,
                'distance' => round($distance, 2),

                'freight_amount' => round($amount, 2),
                'freight_discountpercent' => round($discount, 2),
                'payable_freightamount' => round($payable, 2),
                'freight_found' => $freightFound,

                'tray_capacity' => $trayCapacity,
                'plant_capacity' => $plantCapacity,

                'active_trays' => $activeTrays,
                'active_plants' => $activePlants,

                'this_trip_trays' => $thisTripTrays,
                'this_trip_plants' => $thisTripPlants,

                'total_assigned_trays' => $totalAssignedTrays,
                'total_assigned_plants' => $totalAssignedPlants,

                'remaining_trays' => max(
                    0,
                    $trayCapacity - $totalAssignedTrays
                ),

                'remaining_plants' => max(
                    0,
                    $plantCapacity - $totalAssignedPlants
                ),

                'today_trips' => $todayTrips,
                'today_driver_id' => $todayDriverId,
                'today_driver_name' => $todayDriverName
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    } catch (InvalidArgumentException $e) {
        http_response_code(422);

        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage(),
            'data' => []
        ]);

    } catch (Throwable $e) {
        http_response_code(500);

        error_log(
            'get_freight_details error: ' . $e->getMessage()
        );

        echo json_encode([
            'status' => 'error',
            'message' => 'Unable to load freight details.',
            'data' => []
        ]);
    }

    exit;
}else if ($action === 'fetchingqrcodedata') {

    header('Content-Type: application/json; charset=utf-8');

    $response = [
        'status'  => false,
        'code'    => '',
        'message' => '',
        'data'    => []
    ];

    try {
        $projectid = (int) ($_POST['projectid'] ?? 0);
        $locationid = trim((string) ($_POST['locationid'] ?? ''));
        $barcode = trim((string) ($_POST['qrcode'] ?? ''));
        $stkorder = trim((string) ($_POST['stockorder'] ?? ''));
        $stockOrders = array_filter(
        array_map(
                static function ($value) {
                    return trim($value, " \t\n\r\0\x0B'\"");
                },
                explode(',', $stkorder)
            )
        );
        $stockOrderPlaceholders = implode(
            ',',
            array_fill(0, count($stockOrders), '?')
        );

        /*
        |--------------------------------------------------------------------------
        | Input validation
        |--------------------------------------------------------------------------
        */

        if ($projectid <= 0) {
            throw new InvalidArgumentException(
                'Valid project ID is required.'
            );
        }

        if ($locationid === '') {
            throw new InvalidArgumentException(
                'Please select the source location first.'
            );
        }

        if ($barcode === '') {
            throw new InvalidArgumentException(
                'QR code is required.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Find barcode and its current dispatch status
        |--------------------------------------------------------------------------
        |
        | Do not filter by selected location here because we need to know
        | whether the barcode exists at another location.
        |--------------------------------------------------------------------------
        */

          $sql = "
            SELECT
                date_format(d.sowingdate_internal,'%d-%m-%Y') as sowingdate_internal,
                d.productname as product_name,
                d.productid,
                d.roottype,
                d.locationid,
                d.stockorder_no,
                d.qtypertray,
                d.polyhouseid,
                d.sowinglotno_internal ,

                ph.polyhousename,
                loc.LocationName AS barcode_location_name,

                h.trip_id AS active_trip_id,
                h.vehicle_id AS active_vehicle_id,
                h.vehicle_no AS active_vehicle_no,
                h.driver_name AS active_driver_name,
                h.from_location_id AS dispatched_from_location_id,
                h.from_location_name AS dispatched_from_location_name,
                h.to_location_id AS dispatched_to_location_id,
                h.to_location_name AS dispatched_to_location_name,
                h.status AS dispatch_status,
                h.dispatch_time,
                h.depatchlocationid

            FROM krf_polyhouse_plant_details d

            LEFT JOIN krf_polyhouse_master ph
                ON ph.polyhouseid = d.polyhouseid
               AND ph.projectid = d.projectid

            LEFT JOIN hrmplus.hrm_AttendanceLocationMaster loc
                ON loc.LocationId = d.LocationId

            LEFT JOIN krf_vehicle_travel_history h
                ON  h.barcode = d.barcode
                      AND h.status   in ('Dispatched' ,'In Transit')
            WHERE d.projectid = ?
              AND d.barcode = ?
              AND d.locationid =? 
              AND d.stockorder_no IN ($stockOrderPlaceholders)

            LIMIT 1
        ";

        $stmt = $krfconn->prepare($sql);

        if (!$stmt) {
            throw new RuntimeException(
                'QR lookup preparation failed: ' . $krfconn->error
            );
        }

        try {
            /*$stmt->bind_param(
                'isss',
                $projectid,
                $barcode,
                $locationid,
                $stkorder
            );

            if (!$stmt->execute()) {
                throw new RuntimeException(
                    'QR lookup execution failed: ' . $stmt->error
                );
            }*/
            $params = array_merge(
                [$projectid, $barcode, $locationid],
                $stockOrders
            );

            $types = 'iss' . str_repeat('s', count($stockOrders));

            $stmt->bind_param($types, ...$params);

            if (!$stmt->execute()) {
                throw new RuntimeException(
                    'QR lookup execution failed: ' . $stmt->error
                );
            }

            $result = $stmt->get_result();
            $row = $result ? $result->fetch_assoc() : null;

        } finally {
            $stmt->close();
        }

        /*
        |--------------------------------------------------------------------------
        | Reason 1: Barcode does not exist
        |--------------------------------------------------------------------------
        */

        if (!$row) {
            $response = [
                'status'  => false,
                'code'    => 'BARCODE_NOT_FOUND',
                'message' => 'This QR code was not found in plant tray records.',
                'data'    => [
                    'barcode' => $barcode,
                    'projectid' => $projectid,
                    'locationid' => $locationid,
                    'stkorder' => $stkorder
                ]
            ];

            echo json_encode(
                $response,
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            );

            exit;
        }

        /*
        |--------------------------------------------------------------------------
        | Reason 2: Barcode already dispatched/loaded
        |--------------------------------------------------------------------------
        */ 
        if (!empty($row['dispatch_status'])) {
            $vehicleNo = trim(
                (string) ($row['active_vehicle_no'] ?? '')
            );

            $tripId = trim(
                (string) ($row['active_trip_id'] ?? '')
            );

            $dispatchStatus = trim(
                (string) ($row['dispatch_status'] ?? 'Dispatched')
            );

            $message =
                'This tray is already in loading/dispatch';

            if ($vehicleNo !== '') {
                $message .= ' against vehicle ' . $vehicleNo;
            }

            if ($tripId !== '') {
                $message .= ' under Trip ID ' . $tripId;
            }

            $message .= '. Current status: ' . $dispatchStatus . '.';

            $response = [
                'status'  => false,
                'code'    => 'ALREADY_DISPATCHED',
                'message' => $message,
                'data'    => [
                    'barcode'            => $barcode,
                    'trip_id'            => $tripId,
                    'vehicle_id'         => $row['active_vehicle_id'] ?? '',
                    'vehicle_no'         => $vehicleNo,
                    'driver_name'        => $row['active_driver_name'] ?? '',
                    'dispatch_status'     => $dispatchStatus,
                    'dispatch_time'       => $row['dispatch_time'] ?? '',
                    'from_location_id'    => $row['dispatched_from_location_id'] ?? '',
                    'from_location_name'  => $row['dispatched_from_location_name'] ?? '',
                    'to_location_id'      => $row['dispatched_to_location_id'] ?? '',
                    'to_location_name'    => $row['dispatched_to_location_name'] ?? ''
                ]
            ];

            echo json_encode(
                $response,
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            );

            exit;
        }

        /*
        |--------------------------------------------------------------------------
        | Reason 3: Barcode belongs to another source location
        |--------------------------------------------------------------------------
        */

          $barcodeLocationId = trim(
            (string) ($row['locationid'] ?? '')
        ); 

        if ($barcodeLocationId !== $locationid) {
            $barcodeLocationName = trim(
                (string) (
                    $row['barcode_location_name'] ??
                    $row['LocationName'] ??
                    ''
                )
            );

            $message =
                'This tray does not belong to the selected source location.';

            if ($barcodeLocationName !== '') {
                $message .=
                    ' It is currently available at ' .
                    $barcodeLocationName . '.';
            } else {
                $message .=
                    ' Its location ID is ' .
                    $barcodeLocationId . '.';
            }

            $response = [
                'status'  => false,
                'code'    => 'DIFFERENT_LOCATION',
                'message' => $message,
                'data'    => [
                    'barcode'               => $barcode,
                    'selected_location_id'   => $locationid,
                    'actual_location_id'     => $barcodeLocationId,
                    'actual_location_name'   => $barcodeLocationName
                ]
            ];

            echo json_encode(
                $response,
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            );

            exit;
        }

        /*
        |--------------------------------------------------------------------------
        | Success: barcode exists at selected location and is not dispatched
        |--------------------------------------------------------------------------
        */

        $response = [
            'status'  => true,
            'code'    => 'BARCODE_VERIFIED',
            'message' => 'QR code verified successfully.',
            'data'    => $row
        ];

    } catch (InvalidArgumentException $e) {
        http_response_code(422);

        $response = [
            'status'  => false,
            'code'    => 'VALIDATION_ERROR',
            'message' => $e->getMessage(),
            'data'    => []
        ];

    } catch (Throwable $e) {
        http_response_code(500);

        error_log(
            'QR code verification error: ' .
            $e->getMessage()
        );

        $response = [
            'status'  => false,
            'code'    => 'SERVER_ERROR',
            'message' => 'Unable to verify the QR code.',
            'data'    => []
        ];
    }

    echo json_encode(
        $response,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}/*else if ($action == "fetchingqrcodedata") { 
    $response = [
        'status'  => false,
        'message' => '',
        'data'    => []
    ];
    try { 
        $projectid = (int) ($_POST['projectid'] ?? 0);
        $locationid = (string) ($_POST['locationid'] ?? 0);
        $barcode = trim((string) ($_POST['qrcode'] ?? ''));
        if ($projectid <= 0) {
            throw new InvalidArgumentException('Valid project ID is required.');
        }
        if ($locationid ==='') {
            throw new InvalidArgumentException('Valid location ID is required.');
        }

        if ($barcode === '') {
            throw new InvalidArgumentException('QR code is required.');
        }

        $sql = "
            SELECT
                d.*, ph.polyhousename
            FROM krf_polyhouse_plant_details d 
        left JOIN krf_polyhouse_master ph ON d.polyhouseid = ph.polyhouseid

            WHERE d.projectid = ?
              AND d.LocationId = ?
              AND d.barcode = ?

              AND NOT EXISTS (
                  SELECT 1
                  FROM krf_vehicle_travel_history h
                  WHERE h.barcode = d.barcode
                    AND h.status IN (
                        'Dispatched'
                    )
              )
            LIMIT 1
        ";

        $stmt = $krfconn->prepare($sql);

        if (!$stmt) {
            throw new RuntimeException(
                'Query preparation failed: ' . $krfconn->error
            );
        }

        try {
            $stmt->bind_param(
                'iss',
                $projectid,
                $locationid,
                $barcode
            );

            if (!$stmt->execute()) {
                throw new RuntimeException(
                    'Query execution failed: ' . $stmt->error
                );
            }

            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();

                $response = [
                    'status'  => true,
                    'message' => 'QR code verified successfully.',
                    'data'    => $row
                ];
            } else {
                $response = [
                    'status'  => false,
                    'message' => 'No matching record found for this QR code and location.',
                    'data'    => []
                ];
            }
        } finally {
            $stmt->close();
        }

    } catch (InvalidArgumentException $e) {
        http_response_code(422);

        $response['message'] = $e->getMessage();

    } catch (Throwable $e) {
        http_response_code(500);

        error_log(
            'QR code verification error: ' . $e->getMessage()
        );

        $response['message'] = 'Unable to verify the QR code.';
    }

    echo json_encode(
        $response,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
    }*/


function location_master(mysqli $hrmconn, int $projectid): array
{
    $locations = [];

    $sql = "
        SELECT
            LocationId AS id,
            LocationName AS name,
            OfficeLat AS latitude,
            OfficeLng AS longitude
        FROM hrm_AttendanceLocationMaster
        WHERE ProjectID = ?
          AND LocationName IS NOT NULL
          AND TRIM(LocationName) <> ''
        ORDER BY LocationName
    ";

    $stmt = $hrmconn->prepare($sql);

    if (!$stmt) {
        throw new RuntimeException(
            'Location query preparation failed: ' . $hrmconn->error
        );
    }

    try {
        $stmt->bind_param('i', $projectid);

        if (!$stmt->execute()) {
            throw new RuntimeException(
                'Location query execution failed: ' . $stmt->error
            );
        }

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $locations[] = [
                'id'        => $row['id'],
                'name'      => $row['name'],
                'latitude'  => $row['latitude'],
                'longitude' => $row['longitude']
            ];
        }

        return $locations;

    } finally {
        $stmt->close();
    }
}


function vehicle_master(mysqli $krfconn, int $projectid): array
{
    $vehicles = [];

    $sql = "
        SELECT
            vehicleid AS id,
            vehicleno AS number,
            vehiclename AS name
        FROM krf_vehicle_master
        WHERE ProjectID = ?
          AND status = 'Active'
        ORDER BY vehiclename, vehicleno
    ";

    $stmt = $krfconn->prepare($sql);

    if (!$stmt) {
        throw new RuntimeException(
            'Vehicle query preparation failed: ' . $krfconn->error
        );
    }

    try {
        $stmt->bind_param('i', $projectid);

        if (!$stmt->execute()) {
            throw new RuntimeException(
                'Vehicle query execution failed: ' . $stmt->error
            );
        }

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $vehicleName = trim((string) $row['name']);
            $vehicleNo   = trim((string) $row['number']);

            $vehicles[] = [
                'id'     => $row['id'],
                'number' => $vehicleNo,
                'name'   => $vehicleName,
                'label'  => $vehicleName . (
                    $vehicleNo !== ''
                        ? ' (' . $vehicleNo . ')'
                        : ''
                )
            ];
        }

        return $vehicles;

    } finally {
        $stmt->close();
    }
}


function driver_master(mysqli $krfconn, int $projectid, string $selectedDriverId = ''
): array {
    $drivers = [];
    $sql = "SELECT driverid AS id, drivername AS name,  drivermobileno AS mobile
        FROM krf_vehicle_driver_master
        WHERE ProjectID = ?
          AND status = 'Active'
          AND drivername IS NOT NULL
          AND TRIM(drivername) <> ''
        ORDER BY drivername
    ";

    $stmt = $krfconn->prepare($sql);

    if (!$stmt) {
        throw new RuntimeException(
            'Driver query preparation failed: ' . $krfconn->error
        );
    }

    try {
        $stmt->bind_param('i', $projectid);

        if (!$stmt->execute()) {
            throw new RuntimeException(
                'Driver query execution failed: ' . $stmt->error
            );
        }

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $driverId = (string) $row['id'];

            $drivers[] = [
                'id'       => $row['id'],
                'name'     => trim((string) $row['name']),
                'mobile'   => trim((string) ($row['mobile'] ?? '')),
                'selected' => (
                    $selectedDriverId !== '' &&
                    $selectedDriverId === $driverId
                )
            ];
        }

        return $drivers;

    } finally {
        $stmt->close();
    }
}


function purpose_master(): array
{
    $purposes = [
        'Grafting',
        'Shifting to Polyhouse',
        'Hardening',
        'Clip Removal',
        'Ready to Sale'
    ];

    return array_map(
        static function (string $purpose): array {
            return [
                'id'   => $purpose,
                'name' => $purpose
            ];
        },
        $purposes
    );
}


function edit_tripid(mysqli $krfconn, int $projectid, string $tripid ): array {
    $editarray=[];
      $sql = "
            SELECT  date_format(d.sowingdate_internal,'%d-%m-%Y') as sowingdate_internal,
                d.productname as product_name,  d.productid,  d.roottype,  d.locationid,
                d.stockorder_no,  d.qtypertray AS qty_per_tray ,  d.polyhouseid, d.sowinglotno_internal ,
                ph.polyhousename,loc.LocationName AS barcode_location_name, h.trip_id AS active_trip_id, h.vehicle_id AS active_vehicle_id, h.vehicle_no AS active_vehicle_no, h.driver_name AS active_driver_name, 
                h.from_location_id AS dispatched_from_location_id,
                h.from_location_name AS dispatched_from_location_name,
                h.to_location_id AS lastlocation_id,
                h.to_location_name AS lastlocation_name,
                h.status AS dispatch_status, 
                h.barcode, h.driverid,
                
                h.driver_mobileno, 
                h.send_traycount, h.send_totalplantcount, h.send_remark, h.purpose_to_move, h.freight_amount, 
                    h.freight_discountpercent, h.payable_freightamount, 
                   h. dispatch_time,  
                    h.from_lat, h.from_lon,
                    h.to_lat, h.to_lon,
                    h.distance_km, h.distance_source, h.dispatchtype ,   h.depatchlocationid,h.despatchlocationname


            FROM krf_polyhouse_plant_details d
              JOIN krf_vehicle_travel_history h
                ON  h.barcode = d.barcode 
            AND h.projectid = d.projectid 

            LEFT JOIN krf_polyhouse_master ph
                ON ph.polyhouseid = d.polyhouseid
               AND ph.projectid = d.projectid

            LEFT JOIN hrmplus.hrm_AttendanceLocationMaster loc
                ON loc.LocationId = d.LocationId  
                             

            WHERE d.projectid = ?
              AND h.trip_id = ?
                AND h.status  ='Dispatched' 
        ";   

    $stmt = $krfconn->prepare($sql);

    if (!$stmt) {
        throw new RuntimeException(
            'Driver query preparation failed: ' . $krfconn->error
        );
    }

    try {
        $stmt->bind_param('is', $projectid,$tripid);

        if (!$stmt->execute()) {
            throw new RuntimeException(
                'Driver query execution failed: ' . $stmt->error
            );
        }

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $editarray[] = $row;
        }

        return $editarray;

    } finally {
        $stmt->close();
    }
}
function orderlist($krfconn,$hrmconn){

     $locationMap = [];
        $loc_res = $hrmconn->query("SELECT LocationId, LocationName FROM hrm_AttendanceLocationMaster");
        if ($loc_res) {
            while ($loc_row = $loc_res->fetch_assoc()) {
                $locationMap[$loc_row['LocationId']] = $loc_row['LocationName'];
            }
        }

    $html="" ;
    $query = "SELECT  d.sowinglotno_internal,
        ph.polyhousename,
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
        (SELECT concat_ws('~'  ,group_concat(trip_id),group_concat(send_traycount),group_concat(send_totalplantcount),group_concat(status)) FROM krf_vehicle_travel_history WHERE stockorder_no = p.stockorder_no COLLATE utf8mb4_general_ci  
       /* and productid =d.productid
        and sowinglotno_internal =d.sowinglotno_internal */
        and  from_location_id = d.locationid  
        and lcase(status) in ('dispatched' ,'in transit') 
        ) as existing_trip
        FROM krf_production_stockorder p
        JOIN krf_polyhouse_plant_details d ON CONVERT(d.stockorder_no USING utf8mb4) COLLATE utf8mb4_unicode_ci = p.stockorder_no

        LEFT JOIN krf_polyhouse_master ph ON d.polyhouseid = ph.polyhouseid AND d.projectid = ph.projectid
        WHERE  d.status in ( 'Polyhouse',  'Grafting', 'Healing', 'Hardening', 'Clip Stick Removal', 'Removed')
        GROUP BY 
        p.stockorder_no, 
        /* d.productid, 
        d.sowinglotno_internal, */
        d.locationid  
        ORDER BY d.dispatchdate_internal ASC";            
        $result = $krfconn->query($query);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $key_str = $row['stockorder_no'] . '_' . $row['productid'];
                $total_trays = (int)$row['total_trays'];
                $total_plants = (int)$row['total_plants'];
                $existing_trip = $row['existing_trip']??'';
                $tripid="";
                $movetray=0;
                $moveplant=0; 
                if (!empty($existing_trip)) {
                    if (stripos($existing_trip, 'dispatched') !== false) {
                           continue;
                    } 
                    $split_trip = explode("~", $existing_trip);
                    // Sum tray counts
                    if (!empty($split_trip[1])) {
                        $movetray = array_sum(array_map('intval', explode(",", $split_trip[1])));
                    }
                    // Sum plant counts
                    if (!empty($split_trip[2])) {
                        $moveplant = array_sum(array_map('intval', explode(",", $split_trip[2])));
                    }
                }
                if ($total_trays <= 0) {
                    continue;
                }
                $resolved_loc_id = $row['resolved_location_id'] ?? '';
                $location_name = (!empty($resolved_loc_id) && isset($locationMap[$resolved_loc_id])) ? $locationMap[$resolved_loc_id] : 'Location Missing';
                
                $locations = $location_name ?? 'Not Assigned';
                $html .= '<tr>';
                    $chkValue = $row['stockorder_no']  ?? '';
                    $sowinglotno_internal = $row['sowinglotno_internal'] ?? '';
                    $actionBtn = '<div class="d-flex align-items-center justify-content-center gap-2">
                        <input type="checkbox"
                               class="order-select-chk"
                               value="' . htmlspecialchars($chkValue) . '"
                               style="width:16px; height:16px; margin:0; padding:0; cursor:pointer; vertical-align:middle; accent-color:var(--dispatch-primary);">
                    </div>';
                $html .= '<td class="text-center">' . $actionBtn . '</td>';
                $html .= '<td><span class="fw-bold text-indigo-600">' . $row['stockorder_no'] . '</span></td>';                    
                $prodName = !empty($row['seed_name']) ? $row['seed_name'] : ($row['product_name'] ?? 'Stage Transition');
                $html .= '<td class ="d-none">' . htmlspecialchars($prodName) . '</td>';
                if ($movetray > 0) {
                    $pendtry = $total_trays - $movetray;
                    $ptree   = $total_plants - $moveplant;
                    $qtyText = 'Move ' . $movetray .
                               ' | Pending ' . $pendtry .
                               ' | Total ' . $total_trays . ' Trays' .
                               ' (Pending Plants: ' . number_format($ptree) .
                               ' / Total Plants: ' . number_format($total_plants) . ')';
                } else {
                    $qtyText = $total_trays . ' Trays (' . number_format($total_plants) . ' Plants)';
                }  
                $html .= '<td><span class="badge bg-indigo-50 text-indigo-600">' . $qtyText . '</span></td>';
                
                $displayDate = $row['dispatchdate_internal'] ?? '';
                $html .= '<td class ="d-none">' . (!empty($displayDate) ? date('d M, Y', strtotime($displayDate)) : '<span class="text-danger small fw-bold">dispatchdate missing</span>') . '</td>';
                //$polyname =$location_name;// $row['polyhousename'] ?? '';
                $html .= '<td  class="d-none">' . htmlspecialchars($location_name) . '</td>';

                $html .= '<td class ="sowinglotno_internal d-none">' . htmlspecialchars($sowinglotno_internal) . '</td>'; 
                $html .= '<td class="location-td"
                            data-stockorder="'.$row['stockorder_no'].'"
                            data-productid="'.$row['productid'].'"
                            data-roottype="'.$row['roottype'].'"
                            data-locationid="'.$row['locationid'].'"
                            data-polyhouseid="'.$row['polyhouseid'].'"
                            data-sowinglotno_internal="'.$row['sowinglotno_internal'].'"
                            data-existing_trip="'.$row['existing_trip'].'"
                            
                            >
                            <a href="javascript:void(0)"
                               class="location-badge"
                               onclick="viewLocationDetails(\''.$row['stockorder_no'].'\', \''.$row['productid'].'\',  \''.$row['locationid'].'\',  \''.$row['sowinglotno_internal'].'\')">
                                <i class="fa fa-map-marker-alt me-1"></i> '.$locations.'
                            </a>
                          </td>';
                $html .= '</tr>';                         
            }
        }  
    return $html;    
}
 


    

 
