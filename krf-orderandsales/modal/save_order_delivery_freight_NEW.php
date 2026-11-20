<?php
declare(strict_types=1);

/*
 * Save endpoint for fetch_order_delivery_freight.php
 *
 * JavaScript URL:
 *   krf-orderandsales/modal/save_order_delivery_freight.php
 *
 * Change the connection include only when this file is placed somewhere other
 * than krf-orderandsales/modal/.
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../../connection.php';

if (!isset($krfconn) || !($krfconn instanceof mysqli)) {
    sendJsonResponse([
        'status' => 'error',
        'message' => 'Database connection is unavailable.'
    ], 500);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$krfconn->set_charset('utf8mb4');

function sendJsonResponse(array $payload, int $httpStatus = 200): void
{
    if (ob_get_length()) {
        ob_clean();
    }

    http_response_code($httpStatus);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit;
}

function postArray(string $key): array
{
    $value = $_POST[$key] ?? [];
    return is_array($value) ? array_values($value) : [];
}

function cleanString(mixed $value): string
{
    return trim((string) ($value ?? ''));
}

function floatValue(mixed $value): float
{
    return is_numeric($value) ? (float) $value : 0.0;
}

function intValue(mixed $value): int
{
    return is_numeric($value) ? (int) $value : 0;
}

/**
 * Execute one prepared statement without maintaining fragile, handwritten
 * bind_param type strings.
 *
 * Each parameter is: ['i'|'d'|'s', value]
 */
function executePrepared(
    mysqli $conn,
    string $sql,
    array $parameters
): mysqli_stmt {
    $statement = $conn->prepare($sql);

    if ($parameters) {
        $types = '';
        $values = [];

        foreach ($parameters as $parameter) {
            $types .= $parameter[0];
            $values[] = $parameter[1];
        }

        $bindArguments = [$types];
        foreach ($values as $index => &$value) {
            $bindArguments[] = &$value;
        }
        unset($value);

        call_user_func_array(
            [$statement, 'bind_param'],
            $bindArguments
        );
    }

    $statement->execute();
    return $statement;
}

/**
 * Stores complete previous versions without duplicating the changing schema of
 * krf_deliveryaddress_location. Run this before begin_transaction(), because
 * CREATE TABLE may cause an implicit commit in MySQL.
 */
function ensureDeliveryLocationHistoryTable(mysqli $conn): void
{
    $conn->query(
        "CREATE TABLE IF NOT EXISTS krf_deliveryaddress_location_history (
            history_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            projectid INT NOT NULL,
            psono VARCHAR(100) NOT NULL,
            version_no INT UNSIGNED NOT NULL,
            previous_pickuptype VARCHAR(100) DEFAULT NULL,
            changed_to_pickuptype VARCHAR(30) NOT NULL,
            snapshot_json LONGTEXT NOT NULL,
            changed_by VARCHAR(100) DEFAULT NULL,
            changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (history_id),
            UNIQUE KEY uq_delivery_location_history_version
                (projectid, psono, version_no),
            KEY idx_delivery_location_history_order (projectid, psono)
        ) ENGINE=InnoDB
          DEFAULT CHARSET=utf8mb4
          COLLATE=utf8mb4_unicode_ci"
    );
}

/**
 * Archives the complete active row-set as one version, then clears it so the
 * submitted mode becomes the only active version for this order.
 */
function archiveAndClearDeliveryLocations(
    mysqli $conn,
    int $projectId,
    string $psoNo,
    string $newPickupType,
    string $changedBy
): array {
    $selectStatement = executePrepared(
        $conn,
        "SELECT *
           FROM krf_deliveryaddress_location
          WHERE projectid = ? AND psono = ?
            AND (tedit IS NULL OR tedit = '')
          ORDER BY id
          FOR UPDATE",
        [
            ['i', $projectId],
            ['s', $psoNo]
        ]
    );

    $currentRows = $selectStatement->get_result()->fetch_all(MYSQLI_ASSOC);
    $selectStatement->close();

    if (!$currentRows) {
        return [
            'action' => 'no-previous-version',
            'archived_rows' => 0
        ];
    }

    $versionStatement = executePrepared(
        $conn,
        "SELECT COALESCE(MAX(version_no), 0) + 1 AS next_version
           FROM krf_deliveryaddress_location_history
          WHERE projectid = ? AND psono = ?",
        [
            ['i', $projectId],
            ['s', $psoNo]
        ]
    );
    $versionRow = $versionStatement->get_result()->fetch_assoc();
    $versionStatement->close();
    $versionNo = max(1, intValue($versionRow['next_version'] ?? 1));

    $previousTypes = [];
    foreach ($currentRows as $currentRow) {
        $type = cleanString($currentRow['pickuptype'] ?? '');
        if ($type !== '') {
            $previousTypes[$type] = true;
        }
    }
    $previousPickupType = implode(',', array_keys($previousTypes));

    $snapshotJson = json_encode(
        $currentRows,
        JSON_UNESCAPED_UNICODE |
        JSON_INVALID_UTF8_SUBSTITUTE |
        JSON_PRESERVE_ZERO_FRACTION
    );

    if ($snapshotJson === false) {
        throw new RuntimeException('Unable to create delivery history snapshot.');
    }

    $historyStatement = executePrepared(
        $conn,
        "INSERT INTO krf_deliveryaddress_location_history
            (projectid, psono, version_no, previous_pickuptype,
             changed_to_pickuptype, snapshot_json, changed_by, changed_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
        [
            ['i', $projectId],
            ['s', $psoNo],
            ['i', $versionNo],
            ['s', $previousPickupType],
            ['s', $newPickupType],
            ['s', $snapshotJson],
            ['s', $changedBy]
        ]
    );
    $historyId = $historyStatement->insert_id;
    $historyStatement->close();

    $deleteStatement = executePrepared(
        $conn,
        "DELETE FROM krf_deliveryaddress_location
          WHERE projectid = ? AND psono = ?
            AND (tedit IS NULL OR tedit = '')",
        [
            ['i', $projectId],
            ['s', $psoNo]
        ]
    );
    $deletedRows = $deleteStatement->affected_rows;
    $deleteStatement->close();

    return [
        'action' => 'archive-and-replace',
        'history_id' => $historyId,
        'version_no' => $versionNo,
        'previous_pickuptype' => $previousPickupType,
        'changed_to_pickuptype' => $newPickupType,
        'archived_rows' => count($currentRows),
        'deleted_active_rows' => $deletedRows
    ];
}

function splitCoordinates(string $coordinates): array
{
    if ($coordinates === '') {
        return ['', ''];
    }

    $parts = array_map('trim', explode(',', $coordinates, 2));
    return [$parts[0] ?? '', $parts[1] ?? ''];
}

/**
 * Fallback matching is used only when the page has not yet been updated to
 * submit delivery_location_id[]. If more than one row matches, no guess is
 * made and a new record is inserted.
 */
function findUnambiguousLocationId(
    mysqli $conn,
    int $projectId,
    string $psoNo,
    string $productId,
    string $pickupType
): int {
    if ($productId === '') {
        return 0;
    }

    $statement = executePrepared(
        $conn,
        "SELECT id
           FROM krf_deliveryaddress_location
          WHERE projectid = ?
            AND psono = ?
            AND productid = ?
            AND pickuptype = ?
            AND (tedit IS NULL OR tedit = '')
          ORDER BY id
          LIMIT 2",
        [
            ['i', $projectId],
            ['s', $psoNo],
            ['s', $productId],
            ['s', $pickupType]
        ]
    );

    $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();

    return count($rows) === 1 ? (int) $rows[0]['id'] : 0;
}

/*function upsertDeliveryLocation(mysqli $conn, array $row): array
{
    $id = intValue($row['id'] ?? 0);

    if ($id <= 0) {
        $id = findUnambiguousLocationId(
            $conn,
            intValue($row['projectid'] ?? 0),
            cleanString($row['psono'] ?? ''),
            cleanString($row['productid'] ?? ''),
            cleanString($row['pickuptype'] ?? '')
        );
    }

    $commonParameters = [
        ['i', intValue($row['projectid'] ?? 0)],
        ['s', cleanString($row['psono'] ?? '')],
        ['s', cleanString($row['sono'] ?? '')],
        ['s', cleanString($row['clientid'] ?? '')],
        ['i', intValue($row['nooftray'] ?? 0)],
        ['d', floatValue($row['deliverycharges'] ?? 0)],
        ['s', cleanString($row['deliveryaddress'] ?? '')],
        ['s', cleanString($row['productid'] ?? '')],
        ['s', cleanString($row['vehicleno'] ?? '')],
        ['s', cleanString($row['drivername'] ?? '')],
        ['s', cleanString($row['drivercontactno'] ?? '')],
        ['s', cleanString($row['status'] ?? 'Pending')],
        ['d', floatValue($row['distance'] ?? 0)],
        ['d', floatValue($row['deliveryqty'] ?? 0)],
        ['s', cleanString($row['dest_latitude'] ?? '')],
        ['s', cleanString($row['dest_longitude'] ?? '')],
        ['s', cleanString($row['dest_state'] ?? '')],
        ['s', cleanString($row['dest_district'] ?? '')],
        ['s', cleanString($row['dest_city'] ?? '')],
        ['s', cleanString($row['dest_pincode'] ?? '')],
        ['s', cleanString($row['pickuppoint'] ?? '')],
        ['s', cleanString($row['pickuptype'] ?? '')],
        ['i', intValue($row['noofbox'] ?? 0)],
        ['d', floatValue($row['freightamt'] ?? 0)],
        ['s', cleanString($row['stateid'] ?? '')],
        ['s', cleanString($row['districtid'] ?? '')],
        ['s', cleanString($row['cityid'] ?? '')],
        ['d', floatValue($row['boxperpack'] ?? 0)],
        ['d', floatValue($row['chargesperbox'] ?? 0)]
    ];

    if ($id > 0) {
        // The project/order condition prevents updating an unrelated row if a
        // wrong ID is posted from the browser.
        $parameters = $commonParameters;
        $parameters[] = ['i', $id];
        $parameters[] = ['i', intValue($row['projectid'] ?? 0)];
        $parameters[] = ['s', cleanString($row['psono'] ?? '')];

        $statement = executePrepared(
            $conn,
            "UPDATE krf_deliveryaddress_location
                SET projectid = ?, psono = ?, sono = ?, clientid = ?,
                    nooftray = ?, deliverycharges = ?, deliveryaddress = ?,
                    productid = ?, vehicleno = ?, drivername = ?,
                    drivercontactno = ?, status = ?, distance = ?,
                    deliveryqty = ?, dest_latitude = ?, dest_longitude = ?,
                    dest_state = ?, dest_district = ?, dest_city = ?,
                    dest_pincode = ?, pickuppoint = ?, pickuptype = ?,
                    noofbox = ?, freightamt = ?, stateid = ?, districtid = ?,
                    cityid = ?, boxperpack = ?, chargesperbox = ?,
                    tedit = '', updated_at = NOW()
              WHERE id = ? AND projectid = ? AND psono = ?",
            $parameters
        );

        $affectedRows = $statement->affected_rows;
        $statement->close();

        return [
            'id' => $id,
            'action' => 'update',
            'affected_rows' => $affectedRows
        ];
    }

    $insertParameters = $commonParameters;
    // created_by is positioned after status in the INSERT statement, so build
    // an INSERT-specific parameter list in the exact SQL order.
    array_splice(
        $insertParameters,
        12,
        0,
        [['s', cleanString($row['created_by'] ?? '')]]
    );

    $statement = executePrepared(
        $conn,
        "INSERT INTO krf_deliveryaddress_location
            (projectid, psono, sono, clientid, nooftray, deliverycharges,
             deliveryaddress, productid, vehicleno, drivername,
             drivercontactno, status, created_by, distance, deliveryqty,
             dest_latitude, dest_longitude, dest_state, dest_district,
             dest_city, dest_pincode, pickuppoint, pickuptype, noofbox,
             freightamt, stateid, districtid, cityid, boxperpack,
             chargesperbox, created_at, updated_at)
         VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
             ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
        $insertParameters
    );

    $insertId = $statement->insert_id;
    $statement->close();

    return [
        'id' => $insertId,
        'action' => 'insert'
    ];
}*/
function upsertDeliveryLocation(mysqli $conn, array $row): array
{
    $id = intValue($row['id'] ?? 0);

    if ($id <= 0) {
        $id = findUnambiguousLocationId(
            $conn,
            intValue($row['projectid'] ?? 0),
            cleanString($row['psono'] ?? ''),
            cleanString($row['productid'] ?? ''),
            cleanString($row['pickuptype'] ?? '')
        );
    }

    /*
     * Login ID
     *
     * At present your calling code sends login ID as created_by.
     * We use the same value for updatedby.
     */
      $loginId = cleanString(
        $row['updatedby']
        ?? $row['created_by']
        ?? ''
    ); 
    /*
     * Common parameters.
     *
     * IMPORTANT:
     * Order must remain exactly the same as UPDATE SQL.
     */
    $commonParameters = [
        ['i', intValue($row['projectid'] ?? 0)],
        ['s', cleanString($row['psono'] ?? '')],
        ['s', cleanString($row['sono'] ?? '')],
        ['s', cleanString($row['clientid'] ?? '')],
        ['i', intValue($row['nooftray'] ?? 0)],
        ['d', floatValue($row['deliverycharges'] ?? 0)],
        ['s', cleanString($row['deliveryaddress'] ?? '')],
        ['s', cleanString($row['productid'] ?? '')],
        ['s', cleanString($row['vehicleno'] ?? '')],
        ['s', cleanString($row['drivername'] ?? '')],
        ['s', cleanString($row['drivercontactno'] ?? '')],
        ['s', cleanString($row['status'] ?? 'Pending')],
        ['d', floatValue($row['totaldistance'] ?? 0)],
        ['d', floatValue($row['freedistance'] ?? 0)],
        ['d', floatValue($row['distance'] ?? 0)],
        ['s', cleanString($row['remark'] ?? '')],
        ['d', floatValue($row['deliveryqty'] ?? 0)],
        ['s', cleanString($row['dest_latitude'] ?? '')],
        ['s', cleanString($row['dest_longitude'] ?? '')],
        ['s', cleanString($row['dest_state'] ?? '')],
        ['s', cleanString($row['dest_district'] ?? '')],
        ['s', cleanString($row['dest_city'] ?? '')],
        ['s', cleanString($row['dest_pincode'] ?? '')],
        ['s', cleanString($row['pickuppoint'] ?? '')],
        ['s', cleanString($row['pickuptype'] ?? '')],
        ['i', intValue($row['noofbox'] ?? 0)],
        ['d', floatValue($row['freightamt'] ?? 0)],
        ['s', cleanString($row['stateid'] ?? '')],
        ['s', cleanString($row['districtid'] ?? '')],
        ['s', cleanString($row['cityid'] ?? '')],
        ['d', floatValue($row['boxperpack'] ?? 0)],
        ['d', floatValue($row['chargesperbox'] ?? 0)]
    ];


    /* ==========================================================
       UPDATE
       ========================================================== */

    if ($id > 0) {

        $parameters = $commonParameters;

        // updatedby = Login ID
        $parameters[] = [
            's',
            $loginId
        ];

        // WHERE id
        $parameters[] = [
            'i',
            $id
        ];

        // WHERE projectid
        $parameters[] = [
            'i',
            intValue($row['projectid'] ?? 0)
        ];

        // WHERE psono
        $parameters[] = [
            's',
            cleanString($row['psono'] ?? '')
        ];


        $statement = executePrepared(
            $conn,
            "
            UPDATE krf_deliveryaddress_location
            SET
                projectid = ?,
                psono = ?,
                sono = ?,
                clientid = ?,
                nooftray = ?,
                deliverycharges = ?,
                deliveryaddress = ?,
                productid = ?,
                vehicleno = ?,
                drivername = ?,
                drivercontactno = ?,
                status = ?,
                totaldistance = ?,
                freedistance = ?,
                distance = ?,
                remark = ?,
                deliveryqty = ?,
                dest_latitude = ?,
                dest_longitude = ?,
                dest_state = ?,
                dest_district = ?,
                dest_city = ?,
                dest_pincode = ?,
                pickuppoint = ?,
                pickuptype = ?,
                noofbox = ?,
                freightamt = ?,
                stateid = ?,
                districtid = ?,
                cityid = ?,
                boxperpack = ?,
                chargesperbox = ?,

                tedit = '',
                updated_at = NOW(),
                updated_by = ?

            WHERE id = ?
              AND projectid = ?
              AND psono = ?
            ",
            $parameters
        );

        $affectedRows = $statement->affected_rows;
        $statement->close();

        return [
            'id'            => $id,
            'action'        => 'update',
            'affected_rows' => $affectedRows
        ];
    }


    /* ==========================================================
       INSERT
       ========================================================== */

    $insertParameters = $commonParameters;

    /*
     * Insert created_by after status.
     *
     * commonParameters:
     * index 11 = status
     * index 12 = totaldistance
     *
     * So insert at index 12.
     */
    array_splice(
        $insertParameters,
        12,
        0,
        [
            ['s', $loginId]
        ]
    );

    /*
     * updatedby is at the END of the ? parameters because
     * we place updatedby after chargesperbox in INSERT SQL.
     */
    $insertParameters[] = [
        's',
        $loginId
    ];


    $statement = executePrepared(
        $conn,
        "
        INSERT INTO krf_deliveryaddress_location
        (
            projectid,
            psono,
            sono,
            clientid,
            nooftray,
            deliverycharges,
            deliveryaddress,
            productid,
            vehicleno,
            drivername,
            drivercontactno,
            status,
            created_by,
            totaldistance,
            freedistance,
            distance,
            remark,
            deliveryqty,
            dest_latitude,
            dest_longitude,
            dest_state,
            dest_district,
            dest_city,
            dest_pincode,
            pickuppoint,
            pickuptype,
            noofbox,
            freightamt,
            stateid,
            districtid,
            cityid,
            boxperpack,
            chargesperbox,
            updated_by,
            created_at,
            updated_at
        )
        VALUES
        (
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?,

            NOW(),
            NOW()
        )
        ",
        $insertParameters
    );

    $insertId = $statement->insert_id;
    $statement->close();

    return [
        'id'     => $insertId,
        'action' => 'insert'
    ];
}

function saveMultipleDeliveryLocations(mysqli $conn, array $rows): array
{
    $results = [];

    foreach ($rows as $row) {
        $results[] = upsertDeliveryLocation($conn, $row);
    }

    return $results;
}

/*function updateProductExpectedDate(
    mysqli $conn,
    int $projectId,
    string $psoNo,
    string $productId,
    string $expectedDate
): void {
    if ($productId === '' || $expectedDate === '') {
        return;
    }

    $statement = executePrepared(
        $conn,
        "UPDATE krf_provisional_sales_order_items
            SET expecteddeliverydate = ?
          WHERE projectid = ? AND psono = ? AND product_id = ?",
        [
            ['s', $expectedDate],
            ['i', $projectId],
            ['s', $psoNo],
            ['s', $productId]
        ]
    );
    $statement->close();
}*/

function updatepickup_order(
    mysqli $conn,
    int $projectId,
    string $psoNo,
    string $pickupfrom,
    string $updatedBy = ''
): void {
    if ($projectId <= 0 || $psoNo === '') {
        return;
    }

    if (!empty($updatedBy)) {
        $statement = executePrepared(
            $conn,
            "UPDATE krf_provisional_sales_order
                SET pickupfrom = ?,
                    updated_by = ?,
                    updated_at = NOW()
              WHERE projectid = ? AND psono = ? ",
            [
                ['s', $pickupfrom],
                ['s', $updatedBy],
                ['i', $projectId],
                ['s', $psoNo]
            ]
        );
    } else {
        $statement = executePrepared(
            $conn,
            "UPDATE krf_provisional_sales_order
                SET pickupfrom = ?,
                    updated_at = NOW()
              WHERE projectid = ? AND psono = ? ",
            [
                ['s', $pickupfrom],
                ['i', $projectId],
                ['s', $psoNo]
            ]
        );
    }
    $statement->close();
}
function savePickupMode(
    mysqli $conn,
    int $projectId,
    string $psoNo,
    string $sono,
    string $clientId,
    string $createdBy
): array {
    $statement = executePrepared(
        $conn,
        "UPDATE krf_deliveryaddress_location
            SET pickuptype = 'pickup', updated_at = NOW()
          WHERE projectid = ? AND psono = ?
            AND (tedit IS NULL OR tedit = '')",
        [
            ['i', $projectId],
            ['s', $psoNo]
        ]
    );

    $affectedRows = $statement->affected_rows;
    $statement->close();

    if ($affectedRows > 0) {
        return [
            'action' => 'update-pickup-mode',
            'affected_rows' => $affectedRows
        ];
    }

    return upsertDeliveryLocation($conn, [
        'projectid' => $projectId,
        'psono' => $psoNo,
        'sono' => $sono,
        'clientid' => $clientId,
        'pickuptype' => 'pickup',
        'status' => 'Pending',
        'created_by' => $createdBy
    ]);
}

function saveFreightCharges(
    mysqli $conn,
    int $projectId,
    string $psoNo,
    string $sono,
    string $clientId,
    float $freightAmount,
    float $discountPercent,
    float $payableAmount,
    string $remarks,
    string $createdBy,
    string $zeroCharge
): array {
    $zeroCharge = strtolower($zeroCharge) === 'yes' ? 'yes' : 'no';

    $checkStatement = executePrepared(
        $conn,
        "SELECT id
           FROM krf_freightcharges
          WHERE projectid = ?
            AND (psono = ? OR (sono = ? AND IFNULL(sono, '') <> ''))
          ORDER BY id
          LIMIT 1",
        [
            ['i', $projectId],
            ['s', $psoNo],
            ['s', $sono]
        ]
    );

    $existing = $checkStatement->get_result()->fetch_assoc();
    $checkStatement->close();

    if ($existing) {
        $id = (int) $existing['id'];
        $statement = executePrepared(
            $conn,
            "UPDATE krf_freightcharges
                SET psono = ?, sono = ?, clientid = ?, freight_amount = ?,
                    freight_discountpercent = ?, payable_freightamount = ?,
                    remarks = ?, status = 'Active', created_by = ?,
                    zerocharge = ?, updated_at = NOW()
              WHERE id = ? AND projectid = ?",
            [
                ['s', $psoNo],
                ['s', $sono],
                ['s', $clientId],
                ['d', $freightAmount],
                ['d', $discountPercent],
                ['d', $payableAmount],
                ['s', $remarks],
                ['s', $createdBy],
                ['s', $zeroCharge],
                ['i', $id],
                ['i', $projectId]
            ]
        );

        $affectedRows = $statement->affected_rows;
        $statement->close();

        return [
            'id' => $id,
            'action' => 'update',
            'affected_rows' => $affectedRows
        ];
    }

    $statement = executePrepared(
        $conn,
        "INSERT INTO krf_freightcharges
            (projectid, psono, sono, clientid, freight_amount,
             freight_discountpercent, payable_freightamount, remarks,
             status, created_by, zerocharge, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?, ?, NOW(), NOW())",
        [
            ['i', $projectId],
            ['s', $psoNo],
            ['s', $sono],
            ['s', $clientId],
            ['d', $freightAmount],
            ['d', $discountPercent],
            ['d', $payableAmount],
            ['s', $remarks],
            ['s', $createdBy],
            ['s', $zeroCharge]
        ]
    );

    $insertId = $statement->insert_id;
    $statement->close();

    return [
        'id' => $insertId,
        'action' => 'insert'
    ];
}

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        throw new RuntimeException('POST request required.');
    }

    $projectId = intValue($_POST['projectid'] ?? 0);
    $psoNo = cleanString($_POST['orderid'] ?? $_POST['psono'] ?? '');
    $sono = cleanString($_POST['sono'] ?? '');
    $clientId = cleanString($_POST['clientid'] ?? '');
    $pickupType = strtolower(cleanString($_POST['pickupfrom'] ?? ''));
    $remarks = cleanString($_POST['remarks'] ?? '');
    $createdBy = cleanString(
        $_POST['loginid'] ?? $_SESSION['loginid'] ?? ''
    );


    if ($projectId <= 0 || $psoNo === '' || $clientId === '') {
        sendJsonResponse([
            'status' => 'error',
            'message' => 'Project ID, order number and client ID are required.'
        ], 422);
    }

    if (!in_array($pickupType, ['delivered', 'pickup', 'boxpacking'], true)) {
        sendJsonResponse([
            'status' => 'error',
            'message' => 'Invalid delivery mode.'
        ], 422);
    }

    ensureDeliveryLocationHistoryTable($krfconn);

    // Start before updating expected delivery dates so every write is atomic.
    $krfconn->begin_transaction();

    $freightKeys = postArray('f_address_key');
    $freightAmounts = postArray('f_amount');
    $freightPayables = postArray('f_payable');
    $freightDiscounts = postArray('f_discount');
    $freightVehicles = postArray('f_vehicle');
    $freightZeroCharges = postArray('f_zero_charges');
    $freightTotalDistances = postArray('f_totaldistance');
    $freightFreeDistances = postArray('f_freedistance');
    $freightPayableDistances = postArray('f_payabledistance');
    $freightNarrations = postArray('f_narration');


        $is_zero_charge = 'no';
    if (!empty($_POST['f_zero_charges'])) {
        if (is_array($_POST['f_zero_charges'])) {
            foreach ($_POST['f_zero_charges'] as $val) {
                if ($val === 'yes' || $val === '1' || $val === 1 || $val === true) {
                    $is_zero_charge = 'yes';
                    break;
                }
            }
        } elseif ($_POST['f_zero_charges'] === 'yes' || $_POST['f_zero_charges'] === '1' || $_POST['f_zero_charges'] === 1) {
            $is_zero_charge = 'yes';
        }
    }
    if (!empty($_POST['zerocharge']) && ($_POST['zerocharge'] === 'yes' || $_POST['zerocharge'] === '1' || $_POST['zerocharge'] === 1)) {
        $is_zero_charge = 'yes';
    }

    $freightByKey = [];
    foreach ($freightKeys as $index => $key) {
        $freightByKey[(string) $key] = [
            'amount' => floatValue($freightAmounts[$index] ?? 0),
            'payable' => floatValue($freightPayables[$index] ?? 0),
            'discount' => floatValue($freightDiscounts[$index] ?? 0),
            'vehicle' => cleanString($freightVehicles[$index] ?? ''),
            'zerocharge' => cleanString($freightZeroCharges[$index] ?? 'no'),
            'totaldistance' => floatValue($freightTotalDistances[$index] ?? 0),
            'freedistance' => floatValue($freightFreeDistances[$index] ?? 0),
            'payabledistance' => floatValue($freightPayableDistances[$index] ?? 0),
            'narration' => cleanString($freightNarrations[$index] ?? '')
        ];
    }

    $locationRows = [];

    if ($pickupType === 'delivered') {
        $ids = postArray('delivery_location_id');
        $productIds = postArray('delivery_productid');
        $expectedDates = postArray('delivery_expected_delivery');
        $quantities = postArray('delivery_qty');
        $distances = postArray('delivery_distance');
        $coordinates = postArray('delivery_latlong');
        $stateIds = postArray('delivery_stateid');
        $stateNames = postArray('delivery_state');
        $districtIds = postArray('delivery_district_id');
        $districtNames = postArray('delivery_district');
        $cities = postArray('delivery_city');
        $pincodes = postArray('delivery_pincode');
        $addresses = postArray('delivery_fulladdress');

        foreach ($productIds as $index => $productIdValue) {
            $productId = cleanString($productIdValue);
            $expectedDate = cleanString($expectedDates[$index] ?? '');
            $city = cleanString($cities[$index] ?? '');
            $districtName = cleanString($districtNames[$index] ?? '');
            $address = cleanString($addresses[$index] ?? '');

            if ($productId === '' || floatValue($quantities[$index] ?? 0) <= 0) {
                throw new RuntimeException(
                    'Invalid product or delivery quantity at row ' . ($index + 1) . '.'
                );
            }

            [$latitude, $longitude] = splitCoordinates(
                cleanString($coordinates[$index] ?? '')
            );

            $groupKey = $city . '|' . $districtName . '|' . $address
                . '@@@' . $expectedDate;
            $groupFreight = $freightByKey[$groupKey] ?? [];

            $rowRawDist = floatValue($distances[$index] ?? 0);
            $groupTotalDist = floatValue($groupFreight['totaldistance'] ?? ($freightTotalDistances[$index] ?? 0));
            $groupFreeDist = floatValue($groupFreight['freedistance'] ?? ($freightFreeDistances[$index] ?? 0));
            $groupPayableDist = floatValue($groupFreight['payabledistance'] ?? ($freightPayableDistances[$index] ?? 0));
            $groupNarration = cleanString($groupFreight['narration'] ?? ($freightNarrations[$index] ?? ''));
            $rowAmount = floatValue($groupFreight['amount'] ?? ($freightAmounts[$index] ?? 0));
            $rowPayable = floatValue($groupFreight['payable'] ?? ($freightPayables[$index] ?? 0));
            $rowVehicle = cleanString($groupFreight['vehicle'] ?? ($freightVehicles[$index] ?? ''));

            $finalTotalDist = ($groupTotalDist > 0) ? $groupTotalDist : $rowRawDist;
            $finalPayableDist = ($groupPayableDist > 0 || $groupFreeDist > 0) ? $groupPayableDist : max(0.0, $finalTotalDist - $groupFreeDist);

            $locationRows[] = [
                'id' => intValue($ids[$index] ?? 0),
                'projectid' => $projectId,
                'psono' => $psoNo,
                'sono' => $sono,
                'clientid' => $clientId,
                'productid' => $productId,
                'deliveryqty' => floatValue($quantities[$index] ?? 0),
                'deliverycharges' => $rowAmount,
                'freightamt' => $rowPayable,
                'deliveryaddress' => $address,
                'dest_latitude' => $latitude,
                'dest_longitude' => $longitude,
                'dest_state' => cleanString($stateNames[$index] ?? ''),
                'dest_district' => $districtName,
                'dest_city' => $city,
                'dest_pincode' => cleanString($pincodes[$index] ?? ''),
                'totaldistance' => $finalTotalDist,
                'freedistance' => $groupFreeDist,
                'distance' => $finalPayableDist,
                'remark' => $groupNarration,
                'vehicleno' => $rowVehicle,
                'stateid' => cleanString($stateIds[$index] ?? ''),
                'districtid' => cleanString($districtIds[$index] ?? ''),
                'cityid' => '',
                'pickuptype' => 'delivered',
                'status' => 'Pending',
                'created_by' => $createdBy
            ];

            // updateProductExpectedDate(
            //     $krfconn,
            //     $projectId,
            //     $psoNo,
            //     $productId,
            //     $expectedDate
            // );
        }
    }

    if ($pickupType === 'boxpacking') {
        $ids = postArray('box_location_id');
        $productIds = postArray('box_productid');
        $expectedDates = postArray('box_expected_delivery');
        $quantities = postArray('box_delivery_qty');
        $stateIds = postArray('box_state');
        $stateNames = postArray('box_state_name');
        $districtIds = postArray('box_district');
        $districtNames = postArray('box_district_name');
        $cities = postArray('box_city');
        $boxesPerPack = postArray('box_per_pack');
        $boxCounts = postArray('box_count');
        $pickupPoints = postArray('box_pickup_point');
        $chargesPerPack = postArray('box_charge_per_pack');
        $boxAmounts = postArray('box_amount');

        foreach ($productIds as $index => $productIdValue) {
            $productId = cleanString($productIdValue);
            $expectedDate = cleanString($expectedDates[$index] ?? '');
            $quantity = floatValue($quantities[$index] ?? 0);

            if ($productId === '' || $quantity <= 0) {
                throw new RuntimeException(
                    'Invalid product or box quantity at row ' . ($index + 1) . '.'
                );
            }

            $locationRows[] = [
                'id' => intValue($ids[$index] ?? 0),
                'projectid' => $projectId,
                'psono' => $psoNo,
                'sono' => $sono,
                'clientid' => $clientId,
                'productid' => $productId,
                'deliveryqty' => $quantity,
                'deliverycharges' => floatValue($chargesPerPack[$index] ?? 0),
                'freightamt' => floatValue($boxAmounts[$index] ?? 0),
                'deliveryaddress' => cleanString($cities[$index] ?? ''),
                'dest_state' => cleanString($stateNames[$index] ?? ''),
                'dest_district' => cleanString($districtNames[$index] ?? ''),
                'dest_city' => cleanString($cities[$index] ?? ''),
                'stateid' => cleanString($stateIds[$index] ?? ''),
                'districtid' => cleanString($districtIds[$index] ?? ''),
                'pickuppoint' => cleanString($pickupPoints[$index] ?? ''),
                'pickuptype' => 'boxpacking',
                'noofbox' => intValue($boxCounts[$index] ?? 0),
                'boxperpack' => floatValue($boxesPerPack[$index] ?? 0),
                'chargesperbox' => floatValue($chargesPerPack[$index] ?? 0),
                'status' => 'Pending',
                'created_by' => $createdBy
            ];


        }
    }

    $historyResult = archiveAndClearDeliveryLocations(
        $krfconn,
        $projectId,
        $psoNo,
        $pickupType,
        $createdBy
    );

    // Previous active rows have been archived and removed. Do not reuse their
    // browser-posted IDs; this submitted row-set is the new active version.
    foreach ($locationRows as &$locationRow) {
        $locationRow['id'] = 0;
    }
    unset($locationRow);

    updatepickup_order(
        $krfconn,
        $projectId,
        $psoNo,
        $pickupType,
        $createdBy
    );

    $totalFreight = array_sum(
        array_map('floatValue', $freightAmounts)
    );
    $totalPayable = array_sum(
        array_map('floatValue', $freightPayables)
    );

    // One freight header row stores an effective overall discount. Individual
    // row discounts remain represented by their summed payable amounts.
    $effectiveDiscount = $totalFreight > 0
        ? round((($totalFreight - $totalPayable) / $totalFreight) * 100, 4)
        : 0.0;

    //$zeroCharge =  $totalPayable <= 0.0001 ? 'yes' : 'no';

    if ($pickupType === 'pickup') {
        $locationResult = [savePickupMode(
            $krfconn,
            $projectId,
            $psoNo,
            $sono,
            $clientId,
            $createdBy
        )];
    } else {
        if (!$locationRows) {
            throw new RuntimeException('No delivery rows were submitted.');
        }
        $locationResult = saveMultipleDeliveryLocations(
            $krfconn,
            $locationRows
        );
    }
 

    $freightResult = saveFreightCharges(
        $krfconn,
        $projectId,
        $psoNo,
        $sono,
        $clientId,
        $totalFreight,
        $effectiveDiscount,
        $totalPayable,
        $remarks,
        $createdBy,
        $is_zero_charge
    );

     

    $krfconn->commit();

    sendJsonResponse([
        'status' => 'success',
        'message' => 'Delivery address and freight details saved successfully.',
        'delivery_mode' => $pickupType,
        'history_result' => $historyResult,
        'location_results' => $locationResult,
        'freight_result' => $freightResult,
        'totals' => [
            'freight_amount' => round($totalFreight, 2),
            'payable_freight' => round($totalPayable, 2),
            'effective_discount_percent' => $effectiveDiscount
        ]
    ]);
} catch (Throwable $error) {
    if (isset($krfconn) && $krfconn instanceof mysqli) {
        try {
            $krfconn->rollback();
        } catch (Throwable) {
            // Preserve the original exception.
        }
    }

    error_log('Save delivery/freight error: ' . $error->getMessage());

    sendJsonResponse([
        'status' => 'error',
        'message' => $error->getMessage()
    ], 500);
}
