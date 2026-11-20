<?php

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
date_default_timezone_set('Asia/Kolkata');

ob_start();

function sendJsonResponse(array $payload, int $httpStatus = 200): void
{
    if (ob_get_length()) {
        ob_clean();
    }

    http_response_code($httpStatus);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function fetchAllRows(mysqli $connection, string $sql, string $types = '', array $params = []): array
{
    $statement = $connection->prepare($sql);
    if (!$statement) {
        throw new RuntimeException('Database prepare failed: ' . $connection->error);
    }

    try {
        if ($types !== '') {
            $statement->bind_param($types, ...$params);
        }

        if (!$statement->execute()) {
            throw new RuntimeException('Database query failed: ' . $statement->error);
        }

        $result = $statement->get_result();
        if (!$result) {
            throw new RuntimeException('Unable to read database result: ' . $statement->error);
        }

        return $result->fetch_all(MYSQLI_ASSOC);
    } finally {
        $statement->close();
    }
}

function getStatesAndDistricts(mysqli $connection): array
{
    return fetchAllRows($connection, "SELECT s.stateid, s.statename, s.statename_hn,
        d.districtid, d.districtname, d.districtname_hn
        FROM master_states AS s
        INNER JOIN master_districts AS d ON d.stateid = s.stateid
        WHERE s.statename IS NOT NULL AND s.statename <> ''
        ORDER BY s.statename, d.districtname");
}

function getBoxFreightMaster(mysqli $connection, int $projectId): array
{
    return fetchAllRows($connection, "SELECT id, stateid, statename, districtid,
        districtname, location, plants_per_box, boxpacking_amount, airport_charges,
        bus_charges, railway_charges
        FROM krf_boxpacking_freight_master
        WHERE projectid = ?
        ORDER BY statename, districtname, location", 'i', [$projectId]);
}

function getProductDetails(mysqli $connection, int $projectId, string $psoNo): array
{
    return fetchAllRows($connection, "SELECT id, product_id, product_name, quantity,
        expecteddeliverydate
        FROM krf_provisional_sales_order_items
        WHERE projectid = ? AND psono = ?
        ORDER BY product_name", 'is', [$projectId, $psoNo]);
}

function getDeliveryLocations(mysqli $connection, int $projectId, string $psoNo): array
{
    return fetchAllRows($connection, "SELECT pickuptype, id, productid, stateid, districtid,
        locationid, dest_state, dest_district, dest_city, dest_pincode,
        deliveryaddress, totaldistance, freedistance, distance, dest_latitude, dest_longitude,
        noofbox,
        boxperpack, 
        chargesperbox,
        freightamt 
        FROM krf_deliveryaddress_location
        WHERE projectid = ? AND psono = ?
          AND (tedit IS NULL OR tedit = '')
        ORDER BY id", 'is', [$projectId, $psoNo]);
}

try {
    require_once __DIR__ . '/../../connection.php';

    if (!isset($krfconn) || !($krfconn instanceof mysqli)) {
        throw new RuntimeException('Database connection is unavailable.');
    }

    $krfconn->set_charset('utf8mb4');

    $action = trim($_GET['action'] ?? '');
    if ($action !== 'getdetails') {
        sendJsonResponse(['status' => 'error', 'message' => 'Invalid action.'], 400);
    }

    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        sendJsonResponse(['status' => 'error', 'message' => 'POST request required.'], 405);
    }

    $projectId = (int) ($_POST['projectid'] ?? 0);
    $psoNo = trim($_POST['psono'] ?? '');

    if ($projectId <= 0 || $psoNo === '') {
        sendJsonResponse([
            'status' => 'error',
            'message' => 'Project ID and PSO No are required.'
        ], 422);
    }

    $allStates = getStatesAndDistricts($krfconn);
    $boxFreightMaster = getBoxFreightMaster($krfconn, $projectId);
    $productDetails = getProductDetails($krfconn, $projectId, $psoNo);
    $deliveryDetails = getDeliveryLocations($krfconn, $projectId, $psoNo);

    sendJsonResponse([
        'status' => 'success',
        'allstates' => $allStates,
        'boxfreightmaster' => $boxFreightMaster,
        'productdetails' => $productDetails,
        'deliverydetails' => $deliveryDetails
    ]);
} catch (Throwable $error) {
    error_log('ordereditquery getdetails error: ' . $error->getMessage());
    sendJsonResponse(['status' => 'error', 'message' => $error->getMessage()], 500);
}
