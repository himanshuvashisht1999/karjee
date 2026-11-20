<?php
include('../../connection.php');
$action = $_POST['action'] ?? '';
header('Content-Type: application/json; charset=utf-8');



if ($action === 'fetch_vehicleinloading') {
    $html = '';
        $query = "
        SELECT
            h.depatchlocationid as  depatchlocationid,
            h.despatchlocationname as despatchlocationname,
            date_format(h.dispatch_time,'%d-%m-%Y') as dispatch_time, 
            h.status,
            h.trip_id,
            h.vehicle_no,
            h.driver_name,
            h.driver_mobileno,
            h.vehicle_id,
            h.purpose_to_move,
            SUM(COALESCE(h.send_traycount, 0)) AS totaltray,
            SUM(COALESCE(h.send_totalplantcount, 0)) AS totalplants,            
            m.plantscapacity AS vehicleplantcapacity
        FROM krf_vehicle_travel_history h
        INNER JOIN krf_vehicle_master m
            ON m.vehicleid = h.vehicle_id
        WHERE lcase(h.status) = 'in transit'
        GROUP BY
            h.trip_id,
            h.depatchlocationid,
            h.despatchlocationname,
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
            $depatchlocationid = htmlspecialchars(
                (string) $row['depatchlocationid'],
                ENT_QUOTES,
                'UTF-8'
            );
            $despatchlocationname = htmlspecialchars(
                (string) $row['despatchlocationname'],
                ENT_QUOTES,
                'UTF-8'
            );
            $ActionBtn = '
                <button type="button"
                        class="btn btn-sm btn-success"
                        onclick="receivetransit(\'' . $tripId . '\' ,\'' . $vehicleno . '\',\'' . $depatchlocationid . '\',\'' . $despatchlocationname . '\')">
                    <i class="fa fa-truck-moving me-1"></i>
                    Confirm Receive
                </button>';
            ;
            $html .= '<tr>';
            $html .= '<td class="text-center">' .
                        $ActionBtn .
                     '</td>';
            $html .= '<td class="text-center">' .
                        htmlspecialchars(
                            (string) $row['trip_id']
                        )  .
                     '</td>';
            $html .= '<td class="text-center">' .
                        htmlspecialchars(
                            (string) $row['dispatch_time']
                        )  .
                     '</td>';
            $html .= '<td>' .
                        htmlspecialchars(
                            (string) $row['despatchlocationname']
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
                            (float) $row['totaltray']
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
  
    echo $html;
    exit;
} else  if ($action === 'loadingmasters_receive') {
    header('Content-Type: application/json; charset=utf-8');

    $response = [
        'status'  => false,
        'message' => '',
        'data'    => [
            'polyhouse'    => [],
            'existingtrip' => []
        ]
    ];

    try {
         $projectid = filter_input(
            INPUT_POST,
            'projectid',
            FILTER_VALIDATE_INT
        );

        $tripid = trim(
            (string) ($_POST['tripid'] ?? '')
        );

        $locationid = trim(
            (string) (
                $_POST['locationid'] ??
                $_POST['depatchlocationid'] ??
                ''
            )
        );

        if (!$projectid || $projectid <= 0) {
            throw new InvalidArgumentException(
                'Valid project ID is required.'
            );
        }

        if ($tripid === '') {
            throw new InvalidArgumentException(
                'Trip ID is required.'
            );
        }

        if ($locationid === '') {
            throw new InvalidArgumentException(
                'Receiving location is required.'
            );
        }

        $existingTrip = receiveTripIdDetails(
            $krfconn,
            $projectid,
            $tripid,
            $locationid
        );

        if (empty($existingTrip)) {
            throw new InvalidArgumentException(
                'No in-transit trip was found for the selected receiving location.'
            );
        }

        $response['data'] = [
            'polyhouse' => polyhouse_master(
                $krfconn,
                $projectid,
                $locationid
            ),

            'existingtrip' => $existingTrip
        ];

        $response['status'] = true;
        $response['message'] =
            'Receiving details loaded successfully.';

    } catch (InvalidArgumentException $e) {
        http_response_code(422);
        $response['message'] = $e->getMessage();

    } catch (Throwable $e) {
        http_response_code(500);

        error_log(
            'loadingmasters_receive error: ' .
            $e->getMessage()
        );

        $response['message'] =
            'Unable to load receiving details.';
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
               AND vh.status = 'Dispatched'
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
            WHERE vh.vehicle_id = ? 
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

        $despatchLocationId = trim(
            (string) ($_POST['despatchLocationId'] ?? '')
        );

        $barcode = trim(
            (string) ($_POST['qrcode'] ?? '')
        );

        $tripid = trim(
            (string) ($_POST['tripid'] ?? '')
        );

        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        if ($projectid <= 0) {
            throw new InvalidArgumentException(
                'Valid project ID is required.'
            );
        }

        if ($despatchLocationId === '') {
            throw new InvalidArgumentException(
                'Please select the dispatch location first.'
            );
        }

        if ($tripid === '') {
            throw new InvalidArgumentException(
                'Trip ID is required.'
            );
        }

        if ($barcode === '') {
            throw new InvalidArgumentException(
                'QR code is required.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Find plant barcode and matching in-transit trip
        |--------------------------------------------------------------------------
        */

        $sql = "
            SELECT
                d.barcode,

                DATE_FORMAT(
                    d.sowingdate_internal,
                    '%d-%m-%Y'
                ) AS sowingdate_internal,

                d.productname AS product_name,
                d.productid,
                d.roottype,
                d.locationid,
                d.stockorder_no,
                d.qtypertray,
                d.polyhouseid,
                d.sowinglotno_internal,

                ph.polyhousename,
                loc.LocationName AS barcode_location_name,

                h.trip_id AS active_trip_id,
                h.vehicle_id AS active_vehicle_id,
                h.vehicle_no AS active_vehicle_no,

                h.driverid AS active_driver_id,
                h.driver_name AS active_driver_name,
                h.driver_mobileno AS active_driver_mobile,

                h.from_location_id AS dispatched_from_location_id,
                h.from_location_name AS dispatched_from_location_name,

                h.to_location_id AS dispatched_to_location_id,
                h.to_location_name AS dispatched_to_location_name,

                h.depatchlocationid,
                h.despatchlocationname,

                h.status AS dispatch_status,
                h.dispatch_time,
                h.send_traycount,
                h.send_totalplantcount

            FROM krf_polyhouse_plant_details d

            LEFT JOIN krf_polyhouse_master ph
                ON ph.polyhouseid = d.polyhouseid
               AND ph.projectid = d.projectid

            LEFT JOIN hrmplus.hrm_AttendanceLocationMaster loc
                ON loc.LocationId = d.locationid

            LEFT JOIN krf_vehicle_travel_history h
                ON h.id = (
                    SELECT h2.id
                    FROM krf_vehicle_travel_history h2
                    WHERE h2.barcode = d.barcode
                      AND h2.trip_id = ?
                      AND LOWER(TRIM(h2.status)) IN (
                          'in transit',
                          'in-transit'
                      )
                    ORDER BY h2.id DESC
                    LIMIT 1
                )

            WHERE d.projectid = ?
              AND d.barcode = ?

            LIMIT 1
        ";

        $stmt = $krfconn->prepare($sql);

        if (!$stmt) {
            throw new RuntimeException(
                'QR lookup preparation failed: ' .
                $krfconn->error
            );
        }

        try {
            /*
             * Parameter order follows SQL:
             * 1. trip ID
             * 2. project ID
             * 3. barcode
             */
            $stmt->bind_param(
                'sis',
                $tripid,
                $projectid,
                $barcode
            );

            if (!$stmt->execute()) {
                throw new RuntimeException(
                    'QR lookup execution failed: ' .
                    $stmt->error
                );
            }

            $result = $stmt->get_result();

            $row = $result
                ? $result->fetch_assoc()
                : null;

        } finally {
            $stmt->close();
        }

        /*
        |--------------------------------------------------------------------------
        | Barcode does not exist in plant details
        |--------------------------------------------------------------------------
        */

        if (!$row) {
            $response = [
                'status'  => false,
                'code'    => 'BARCODE_NOT_FOUND',
                'message' =>
                    'This QR code was not found in plant tray records.',
                'data'    => [
                    'barcode' => $barcode
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
        | Barcode exists but is not part of the selected in-transit trip
        |--------------------------------------------------------------------------
        */

        if (empty($row['active_trip_id'])) {
            $response = [
                'status'  => false,
                'code'    => 'NOT_IN_SELECTED_TRIP',
                'message' =>
                    'This tray is not linked to the selected in-transit trip.',
                'data'    => [
                    'barcode'       => $barcode,
                    'selected_trip' => $tripid,
                    'product_name'  =>
                        $row['product_name'] ?? '',
                    'stockorder_no' =>
                        $row['stockorder_no'] ?? ''
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
        | Verify receiving/dispatch location
        |--------------------------------------------------------------------------
        */

        $actualDispatchLocationId = trim(
            (string) (
                $row['depatchlocationid'] ??
                $row['dispatched_to_location_id'] ??
                ''
            )
        );

        if (
            $actualDispatchLocationId === '' ||
            $actualDispatchLocationId !== $despatchLocationId
        ) {
            $actualDispatchLocationName = trim(
                (string) (
                    $row['despatchlocationname'] ??
                    $row['dispatched_to_location_name'] ??
                    ''
                )
            );

            $message =
                'This tray belongs to another receiving location.';

            if ($actualDispatchLocationName !== '') {
                $message .=
                    ' Assigned location: ' .
                    $actualDispatchLocationName . '.';
            }

            $response = [
                'status'  => false,
                'code'    => 'DIFFERENT_DESTINATION',
                'message' => $message,
                'data'    => [
                    'barcode' => $barcode,

                    'trip_id' =>
                        $row['active_trip_id'] ?? '',

                    'vehicle_no' =>
                        $row['active_vehicle_no'] ?? '',

                    'selected_location_id' =>
                        $despatchLocationId,

                    'actual_location_id' =>
                        $actualDispatchLocationId,

                    'actual_location_name' =>
                        $actualDispatchLocationName
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
        | Success
        |--------------------------------------------------------------------------
        */

        $response = [
            'status'  => true,
            'code'    => 'BARCODE_VERIFIED',
            'message' =>
                'QR code verified successfully.',
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

            // Temporarily show during development:
            'message' => $e->getMessage(),

            'data'    => []
        ];
    }

    echo json_encode(
        $response,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}





 


function polyhouse_master(mysqli $krfconn, int $projectid, string $locationid  
): array {
    $polyhousename = [];
    $sql = "SELECT polyhouseid  , polyhousename 
        FROM krf_polyhouse_master
        WHERE projectid = ?
          and locationid=?
        ORDER BY polyhousename
    ";

    $stmt = $krfconn->prepare($sql);

    if (!$stmt) {
        throw new RuntimeException(
            'Driver query preparation failed: ' . $krfconn->error
        );
    }

    try {
        $stmt->bind_param('is', $projectid,$locationid);

        if (!$stmt->execute()) {
            throw new RuntimeException(
                'Driver query execution failed: ' . $stmt->error
            );
        }

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $driverId = (string) $row['id'];

            $polyhousename[] = [
                'id'       => $row['polyhouseid'],
                'name'     => trim((string) $row['polyhousename']), 
                 
            ];
        }

        return $polyhousename;

    } finally {
        $stmt->close();
    }
}


 

function receiveTripIdDetails(mysqli $krfconn,int $projectid,string $tripid, string $locationid
): array {
    $rows = [];
    $sql = "
        SELECT
            h.trip_id AS active_trip_id,
            h.vehicle_id AS active_vehicle_id,
            h.vehicle_no AS active_vehicle_no,

            h.driverid,
            h.driver_name AS active_driver_name,
            h.driver_mobileno,

            h.from_location_id,
            h.from_location_name,

            h.depatchlocationid,
            h.despatchlocationname,

            h.purpose_to_move,
            h.send_remark,

            COALESCE(SUM(h.send_traycount), 0) AS traycount,
            COALESCE(SUM(h.send_totalplantcount), 0) AS plantsent,

            DATE_FORMAT(
                MIN(h.dispatch_time),
                '%Y-%m-%dT%H:%i'
            ) AS dispatch_time

        FROM krf_vehicle_travel_history h

        WHERE h.projectid = ?
          AND h.trip_id = ?
          AND h.depatchlocationid = ?
          AND LOWER(TRIM(h.status)) IN (
              'in transit',
              'in-transit'
          )

       
    ";

    $stmt = $krfconn->prepare($sql);

    if (!$stmt) {
        throw new RuntimeException(
            'Receive trip query preparation failed: ' .
            $krfconn->error
        );
    }

    try {
        $stmt->bind_param(
            'iss',
            $projectid,
            $tripid,
            $locationid
        );

        if (!$stmt->execute()) {
            throw new RuntimeException(
                'Receive trip query execution failed: ' .
                $stmt->error
            );
        }

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        return $rows;

    } finally {
        $stmt->close();
    }
}


    

 
