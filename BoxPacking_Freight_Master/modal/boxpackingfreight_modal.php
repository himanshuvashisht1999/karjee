<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

include('../../connection.php');

function responseJson(bool $status, string $message = '', array $data = []): void
{
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

function cleanText($value): string
{
    return trim((string)$value);
}

function validNonNegativeNumber($value): bool
{
    return $value !== '' && is_numeric($value) && (float)$value >= 0;
}

$action = $_GET['action'] ?? '';

try {
    if (!isset($krfconn) || !($krfconn instanceof mysqli)) {
        throw new Exception('KRF database connection is not available.');
    }

    switch ($action) {

        case 'getStates':
            $sql = "SELECT stateid, statename
                    FROM master_states
                    WHERE COALESCE(status, 'Active') = 'Active'
                    ORDER BY statename";

            $result = $krfconn->query($sql);

            if (!$result) {
                throw new Exception($krfconn->error);
            }

            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }

            responseJson(true, '', $data);
            break;

        case 'getDistricts':
            $stateid = cleanText($_GET['stateid'] ?? '');

            if ($stateid === '') {
                responseJson(false, 'State is required.');
            }

            $sql = "SELECT districtid, districtname
                    FROM master_districts
                    WHERE stateid = ?
                      AND COALESCE(status, 'Active') = 'Active'
                    ORDER BY districtname";

            $stmt = $krfconn->prepare($sql);

            if (!$stmt) {
                throw new Exception($krfconn->error);
            }

            $stmt->bind_param('s', $stateid);
            $stmt->execute();

            $result = $stmt->get_result();
            $data = [];

            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }

            $stmt->close();

            responseJson(true, '', $data);
            break;

        case 'list':
            $projectid = (int)($_GET['projectid'] ?? 0);

            if ($projectid <= 0) {
                responseJson(false, 'Project ID is required.');
            }

            $sql = "SELECT
                        id,
                        projectid,
                        stateid,
                        statename,
                        districtid,
                        districtname,
                        location,
                        plants_per_box,
                        boxpacking_amount,
                        airport_charges,
                        bus_charges,
                        railway_charges
                    FROM krf_boxpacking_freight_master
                    WHERE projectid = ?
                    ORDER BY statename, districtname, location";

            $stmt = $krfconn->prepare($sql);

            if (!$stmt) {
                throw new Exception($krfconn->error);
            }

            $stmt->bind_param('i', $projectid);
            $stmt->execute();

            $result = $stmt->get_result();
            $data = [];

            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }

            $stmt->close();

            responseJson(true, '', $data);
            break;

        case 'save':
            $id = (int)($_POST['id'] ?? 0);
            $projectid = (int)($_POST['projectid'] ?? 0);

            $stateid = cleanText($_POST['stateid'] ?? '');
            $statename = cleanText($_POST['statename'] ?? '');

            $districtid = cleanText($_POST['districtid'] ?? '');
            $districtname = cleanText($_POST['districtname'] ?? '');

            $location = cleanText($_POST['location'] ?? '');
            $plantsPerBox = cleanText($_POST['plants_per_box'] ?? '500');
            $loginname = cleanText($_POST['loginname'] ?? '');

            $boxpackingAmount = cleanText($_POST['boxpacking_amount'] ?? '0');
            $airportCharges = cleanText($_POST['airport_charges'] ?? '0');
            $busCharges = cleanText($_POST['bus_charges'] ?? '0');
            $railwayCharges = cleanText($_POST['railway_charges'] ?? '0');

            if ($projectid <= 0) {
                responseJson(false, 'Project ID is required.');
            }

            if ($stateid === '' || $statename === '') {
                responseJson(false, 'Please select State.');
            }

            if ($districtid === '' || $districtname === '') {
                responseJson(false, 'Please select District.');
            }

            if ($location === '') {
                responseJson(false, 'Location is required.');
            }

            if (!ctype_digit($plantsPerBox) || (int)$plantsPerBox <= 0) {
                responseJson(false, 'No. of Plants Contained (1 Box) must be a positive whole number.');
            }
            $plantsPerBox = (int)$plantsPerBox;

            $numericFields = [
                'Amount' => $boxpackingAmount,
                'Airport Charges' => $airportCharges,
                'Bus Charges' => $busCharges,
                'Railway Charges' => $railwayCharges
            ];

            foreach ($numericFields as $label => $value) {
                if (!validNonNegativeNumber($value)) {
                    responseJson(false, $label . ' must be a non-negative numeric value.');
                }
            }

            // Revalidate state text against master table.
            $stateStmt = $krfconn->prepare(
                "SELECT statename
                 FROM master_states
                 WHERE stateid = ?
                 LIMIT 1"
            );

            if (!$stateStmt) {
                throw new Exception($krfconn->error);
            }

            $stateStmt->bind_param('s', $stateid);
            $stateStmt->execute();
            $stateResult = $stateStmt->get_result();
            $stateRow = $stateResult->fetch_assoc();
            $stateStmt->close();

            if (!$stateRow) {
                responseJson(false, 'Selected State does not exist.');
            }

            $statename = $stateRow['statename'];

            // Revalidate district and district text against selected state.
            $districtStmt = $krfconn->prepare(
                "SELECT districtname
                 FROM master_districts
                 WHERE stateid = ?
                   AND districtid = ?
                 LIMIT 1"
            );

            if (!$districtStmt) {
                throw new Exception($krfconn->error);
            }

            $districtStmt->bind_param('ss', $stateid, $districtid);
            $districtStmt->execute();
            $districtResult = $districtStmt->get_result();
            $districtRow = $districtResult->fetch_assoc();
            $districtStmt->close();

            if (!$districtRow) {
                responseJson(false, 'Selected District does not belong to the selected State.');
            }

            $districtname = $districtRow['districtname'];

            // Duplicate check: same project/state/district/location.
            if ($id > 0) {
                $dupSql = "SELECT id
                           FROM krf_boxpacking_freight_master
                           WHERE projectid = ?
                             AND stateid = ?
                             AND districtid = ?
                             AND LOWER(TRIM(location)) = LOWER(TRIM(?))
                             AND id <> ?
                           LIMIT 1";

                $dupStmt = $krfconn->prepare($dupSql);

                if (!$dupStmt) {
                    throw new Exception($krfconn->error);
                }

                $dupStmt->bind_param(
                    'isssi',
                    $projectid,
                    $stateid,
                    $districtid,
                    $location,
                    $id
                );
            } else {
                $dupSql = "SELECT id
                           FROM krf_boxpacking_freight_master
                           WHERE projectid = ?
                             AND stateid = ?
                             AND districtid = ?
                             AND LOWER(TRIM(location)) = LOWER(TRIM(?))
                           LIMIT 1";

                $dupStmt = $krfconn->prepare($dupSql);

                if (!$dupStmt) {
                    throw new Exception($krfconn->error);
                }

                $dupStmt->bind_param(
                    'isss',
                    $projectid,
                    $stateid,
                    $districtid,
                    $location
                );
            }

            $dupStmt->execute();
            $dupResult = $dupStmt->get_result();
            $duplicate = $dupResult->fetch_assoc();
            $dupStmt->close();

            if ($duplicate) {
                responseJson(
                    false,
                    'Box packing freight already exists for this State, District and Location.'
                );
            }

            $boxpackingAmount = (float)$boxpackingAmount;
            $airportCharges = (float)$airportCharges;
            $busCharges = (float)$busCharges;
            $railwayCharges = (float)$railwayCharges;

            if ($id > 0) {
                $sql = "UPDATE krf_boxpacking_freight_master
                        SET stateid = ?,
                            statename = ?,
                            districtid = ?,
                            districtname = ?,
                            location = ?,
                            plants_per_box = ?,
                            boxpacking_amount = ?,
                            airport_charges = ?,
                            bus_charges = ?,
                            railway_charges = ?,
                            updated_by = ?
                        WHERE id = ?
                          AND projectid = ?";

                $stmt = $krfconn->prepare($sql);

                if (!$stmt) {
                    throw new Exception($krfconn->error);
                }

                $stmt->bind_param(
                    'sssssiddddsii',
                    $stateid,
                    $statename,
                    $districtid,
                    $districtname,
                    $location,
                    $plantsPerBox,
                    $boxpackingAmount,
                    $airportCharges,
                    $busCharges,
                    $railwayCharges,
                    $loginname,
                    $id,
                    $projectid
                );

                if (!$stmt->execute()) {
                    throw new Exception($stmt->error);
                }

                $stmt->close();

                responseJson(true, 'Box packing freight updated successfully.');
            } else {
                $sql = "INSERT INTO krf_boxpacking_freight_master
                        (
                            projectid,
                            stateid,
                            statename,
                            districtid,
                            districtname,
                            location,
                            plants_per_box,
                            boxpacking_amount,
                            airport_charges,
                            bus_charges,
                            railway_charges,
                            created_by,
                            updated_by
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = $krfconn->prepare($sql);

                if (!$stmt) {
                    throw new Exception($krfconn->error);
                }

                $stmt->bind_param(
                    'isssssiddddss',
                    $projectid,
                    $stateid,
                    $statename,
                    $districtid,
                    $districtname,
                    $location,
                    $plantsPerBox,
                    $boxpackingAmount,
                    $airportCharges,
                    $busCharges,
                    $railwayCharges,
                    $loginname,
                    $loginname
                );

                if (!$stmt->execute()) {
                    throw new Exception($stmt->error);
                }

                $stmt->close();

                responseJson(true, 'Box packing freight saved successfully.');
            }

            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            $projectid = (int)($_POST['projectid'] ?? 0);

            if ($id <= 0 || $projectid <= 0) {
                responseJson(false, 'Invalid record.');
            }

            $stmt = $krfconn->prepare(
                "DELETE FROM krf_boxpacking_freight_master
                 WHERE id = ?
                   AND projectid = ?"
            );

            if (!$stmt) {
                throw new Exception($krfconn->error);
            }

            $stmt->bind_param('ii', $id, $projectid);

            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }

            $affected = $stmt->affected_rows;
            $stmt->close();

            if ($affected < 1) {
                responseJson(false, 'Record not found or already deleted.');
            }

            responseJson(true, 'Box packing freight deleted successfully.');
            break;

        default:
            responseJson(false, 'Invalid action.');
    }
} catch (Throwable $e) {
    responseJson(false, $e->getMessage());
}
