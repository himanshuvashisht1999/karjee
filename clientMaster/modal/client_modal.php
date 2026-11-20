<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

function respond(string $status, string $message = '', array $data = [], int $httpCode = 200): void
{
    http_response_code($httpCode);
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data,
        'api_version' => 'client-master-sequential-id-v13'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function postText(string $key, int $maxLength = 500): string
{
    $value = trim((string)($_POST[$key] ?? ''));
    return mb_substr($value, 0, $maxLength);
}

function normalizeMobileNumbers(string $phones): array
{
    $numbers = [];
    $parts = preg_split('/[,;\/\r\n]+/', $phones) ?: [];

    foreach ($parts as $part) {
        $digits = preg_replace('/\D+/', '', $part);
        if ($digits === null || $digits === '') continue;

        // Treat Indian country-code and leading-zero versions as the same mobile.
        if (strlen($digits) === 12 && substr($digits, 0, 2) === '91') {
            $digits = substr($digits, -10);
        } elseif (strlen($digits) === 11 && substr($digits, 0, 1) === '0') {
            $digits = substr($digits, -10);
        }

        if (strlen($digits) >= 7) $numbers[$digits] = true;
    }

    return array_keys($numbers);
}

function normalizeSingleMobile(string $phone): ?string
{
    if (preg_match('/[,;\/\r\n]/', $phone)) return null;

    $digits = preg_replace('/\D+/', '', $phone);
    if ($digits === null || $digits === '') return null;

    if (strlen($digits) === 12 && substr($digits, 0, 2) === '91') {
        $digits = substr($digits, -10);
    } elseif (strlen($digits) === 11 && substr($digits, 0, 1) === '0') {
        $digits = substr($digits, -10);
    }

    return strlen($digits) === 10 ? $digits : null;
}

function findMobileConflict(
    mysqli $conn,
    string $phones,
    int $excludeSno
): ?array {
    $requestedMobile = normalizeSingleMobile($phones);
    if ($requestedMobile === null) return null;

    $sql = "SELECT sno, client_id, client_name, phones
            FROM krf_client_master
            WHERE sno <> ?
              AND RIGHT(
                    REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
                      TRIM(phones), ' ', ''), '-', ''), '(', ''), ')', ''), '+', ''), '.', ''),
                    10
                  ) = ?
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new RuntimeException($conn->error);
    $stmt->bind_param('is', $excludeSno, $requestedMobile);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) return null;
    return [
        'sno' => (int)$row['sno'],
        'client_id' => (string)$row['client_id'],
        'client_name' => (string)$row['client_name'],
        'mobile' => $requestedMobile
    ];
}

function findNameConflict(mysqli $conn, string $clientName, int $excludeSno): ?array
{
    $normalizedName = mb_strtolower((string)preg_replace('/\s+/u', '', trim($clientName)));
    if ($normalizedName === '') return null;

    $sql = "SELECT sno, client_id, client_name, phones
            FROM krf_client_master
            WHERE sno <> ?
              AND LOWER(REPLACE(TRIM(client_name), ' ', '')) = ?
              AND IFNULL(tedit, '') = ''
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new RuntimeException($conn->error);
    $stmt->bind_param('is', $excludeSno, $normalizedName);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) return null;
    return [
        'sno' => (int)$row['sno'],
        'client_id' => (string)$row['client_id'],
        'client_name' => (string)$row['client_name'],
        'mobile' => normalizeSingleMobile((string)$row['phones']) ?? (string)$row['phones']
    ];
}

function findExactClientConflict(
    mysqli $conn,
    string $clientName,
    string $phones,
    int $excludeSno
): ?array {
    $normalizedName = mb_strtolower((string)preg_replace('/\s+/u', '', trim($clientName)));
    $normalizedMobile = normalizeSingleMobile($phones);
    if ($normalizedName === '' || $normalizedMobile === null) return null;

    $sql = "SELECT sno, client_id, client_name, phones
            FROM krf_client_master
            WHERE sno <> ?
              AND LOWER(REPLACE(TRIM(client_name), ' ', '')) = ?
              AND RIGHT(
                    REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
                      TRIM(phones), ' ', ''), '-', ''), '(', ''), ')', ''), '+', ''), '.', ''),
                    10
                  ) = ?
              AND IFNULL(tedit, '') = ''
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new RuntimeException($conn->error);
    $stmt->bind_param('iss', $excludeSno, $normalizedName, $normalizedMobile);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) return null;
    return [
        'sno' => (int)$row['sno'],
        'client_id' => (string)$row['client_id'],
        'client_name' => (string)$row['client_name'],
        'mobile' => $normalizedMobile
    ];
}

try {
    $connectionFiles = [__DIR__ . '/../../connection.php', __DIR__ . '/../connection.php'];
    $connectionLoaded = false;
    foreach ($connectionFiles as $file) {
        if (is_file($file)) {
            require_once $file;
            $connectionLoaded = true;
            break;
        }
    }
    if (!$connectionLoaded || !isset($krfconn) || !($krfconn instanceof mysqli)) {
        throw new RuntimeException('Database connection is not available.');
    }

    $action = postText('action', 50);

    if ($action === 'getStates') {
        $rows = [];
        $result = $krfconn->query("SELECT stateid, statename FROM master_states WHERE statename IS NOT NULL AND TRIM(statename) <> '' ORDER BY statename");
        if (!$result) throw new RuntimeException('Unable to load states: ' . $krfconn->error);
        while ($row = $result->fetch_assoc()) $rows[] = $row;
        respond('success', '', $rows);
    }

    if ($action === 'getDistricts') {
        $stateid = postText('stateid', 45);
        if ($stateid === '') respond('success', '', []);

        $stmt = $krfconn->prepare("SELECT districtid, districtname FROM master_districts WHERE stateid = ? AND districtname IS NOT NULL AND TRIM(districtname) <> '' ORDER BY districtname");
        if (!$stmt) throw new RuntimeException('Unable to prepare district query: ' . $krfconn->error);
        $stmt->bind_param('s', $stateid);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) $rows[] = $row;
        $stmt->close();
        respond('success', '', $rows);
    }

    if ($action === 'getClient') {
        $clientId = postText('clientid', 45);
        $projectid = postText('projectid', 45);

        if ($clientId === '' || $projectid === '') {
            respond('error', 'Client ID and Project ID are required.', [], 422);
        }

        $sql = "SELECT sno, companyid, subcompanyid, projectid, client_id,
                       client_name, invoicing_address, phones, client_email,
                       stateid, statename, districtid, districtname, cityname,
                       is_trader
                FROM krf_client_master
                WHERE client_id = ? AND projectid = ?
                  AND IFNULL(tedit, '') = ''
                LIMIT 1";
        $stmt = $krfconn->prepare($sql);
        if (!$stmt) throw new RuntimeException('Unable to prepare client query: ' . $krfconn->error);
        $stmt->bind_param('ss', $clientId, $projectid);
        $stmt->execute();
        $client = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$client) respond('error', 'Client record was not found.', [], 404);
        respond('success', '', $client);
    }

    if ($action === 'checkMobileUnique' || $action === 'checkClientUnique') {
        $sno = (int)($_POST['sno'] ?? 0);
        $phones = postText('phones', 500);
        $clientName = postText('client_name', 100);

        if ($phones === '') {
            respond('success', '', ['unique' => true]);
        }

        $normalizedMobile = $phones !== '' ? normalizeSingleMobile($phones) : null;
        if ($phones !== '' && $normalizedMobile === null) {
            respond('success', 'Enter one valid 10-digit mobile number.', [
                'unique' => false,
                'valid' => false
            ]);
        }

        $exactConflict = ($normalizedMobile !== null && $clientName !== '')
            ? findExactClientConflict($krfconn, $clientName, $normalizedMobile, $sno)
            : null;

        if ($exactConflict) {
            respond('success', 'The same client name and mobile number already exist.', [
                'unique' => false,
                'exact_duplicate' => true,
                'field' => 'client',
                'conflict' => $exactConflict
            ]);
        }

        $mobileConflict = $normalizedMobile !== null ? findMobileConflict($krfconn, $normalizedMobile, $sno) : null;

        if ($mobileConflict) {
            respond('success', 'This mobile number is already used by ' . $mobileConflict['client_name'] . '.', [
                'unique' => false,
                'exact_duplicate' => false,
                'field' => 'mobile',
                'conflict' => $mobileConflict
            ]);
        }

        respond('success', '', ['unique' => true]);
    }

    if ($action === 'checkNameExists') {
        $sno = (int)($_POST['sno'] ?? 0);
        $clientName = postText('client_name', 100);

        if ($clientName === '') respond('success', '', ['exists' => false]);

        $nameConflict = findNameConflict($krfconn, $clientName, $sno);
        if ($nameConflict) {
            respond(
                'success',
                'Client name ' . $clientName . ' is already used by ' . $nameConflict['client_name'] .
                    ($nameConflict['mobile'] !== '' ? ' (mobile ' . $nameConflict['mobile'] . ').' : '.'),
                ['exists' => true, 'conflict' => $nameConflict]
            );
        }

        respond('success', '', ['exists' => false]);
    }

    if ($action === 'saveUpdateClient') {
        $sno = (int)($_POST['sno'] ?? 0);
        $companyid = postText('companyid', 45);
        $subcompanyid = postText('subcompanyid', 45);
        $projectid = postText('projectid', 45);
        $clientName = postText('client_name', 100);
        $invoicingAddress = postText('invoicing_address', 500);
        $phones = postText('phones', 500);
        $email = postText('client_email', 500);
        $stateid = postText('stateid', 45);
        $districtid = postText('districtid', 45);
        $cityname = postText('cityname', 45);
        $isTrader = isset($_POST['is_trader']) && (int)$_POST['is_trader'] === 1 ? 1 : 0;
        $allowDuplicateMobile = isset($_POST['allow_duplicate_mobile']) && (int)$_POST['allow_duplicate_mobile'] === 1;

        if ($companyid === '' || $subcompanyid === '' || $projectid === '') respond('error', 'Company, subcompany, and project are required.', [], 422);
        if ($clientName === '') respond('error', 'Client name is required.', [], 422);
        if ($phones === '') respond('error', 'Phone number is required.', [], 422);
        $normalizedMobile = normalizeSingleMobile($phones);
        if ($normalizedMobile === null) respond('error', 'Enter one valid 10-digit mobile number.', [], 422);
        $phones = $normalizedMobile;
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) respond('error', 'Please enter a valid email address.', [], 422);
        $mobileConflict = findMobileConflict($krfconn, $phones, $sno);
        $exactConflict = findExactClientConflict($krfconn, $clientName, $phones, $sno);

        if ($exactConflict) {
            respond(
                'error',
                'The same client name and mobile number already exist. Duplicate entry cannot be saved.',
                ['reason' => 'duplicate_client', 'conflict' => $exactConflict],
                409
            );
        }

        if ($mobileConflict && !$allowDuplicateMobile) {
            respond(
                'error',
                'Mobile number ' . $mobileConflict['mobile'] . ' is already used by ' . $mobileConflict['client_name'] . '.',
                ['reason' => 'duplicate_mobile', 'conflict' => $mobileConflict],
                409
            );
        }

        $statename = '';
        $districtname = '';

        if ($stateid !== '') {
            $stmt = $krfconn->prepare('SELECT statename FROM master_states WHERE stateid = ? LIMIT 1');
            if (!$stmt) throw new RuntimeException($krfconn->error);
            $stmt->bind_param('s', $stateid);
            $stmt->execute();
            $state = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$state) respond('error', 'Selected state is invalid.', [], 422);
            $statename = (string)$state['statename'];
        }

        if ($districtid !== '') {
            if ($stateid === '') respond('error', 'Select a state before selecting a district.', [], 422);
            $stmt = $krfconn->prepare('SELECT districtname FROM master_districts WHERE districtid = ? AND stateid = ? LIMIT 1');
            if (!$stmt) throw new RuntimeException($krfconn->error);
            $stmt->bind_param('ss', $districtid, $stateid);
            $stmt->execute();
            $district = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$district) respond('error', 'Selected district does not belong to the selected state.', [], 422);
            $districtname = (string)$district['districtname'];
        }

        $krfconn->begin_transaction();
        try {
            if ($sno > 0) {
                $sql = "UPDATE krf_client_master SET client_name = ?, invoicing_address = ?, phones = ?, client_email = ?,
                            stateid = ?, statename = ?, districtid = ?, districtname = ?, cityname = ?, is_trader = ?
                        WHERE sno = ? AND companyid = ? AND subcompanyid = ? AND projectid = ? AND IFNULL(tedit, '') = ''";
                $stmt = $krfconn->prepare($sql);
                if (!$stmt) throw new RuntimeException($krfconn->error);
                $stmt->bind_param('sssssssssiisss', $clientName, $invoicingAddress, $phones, $email, $stateid, $statename, $districtid, $districtname, $cityname, $isTrader, $sno, $companyid, $subcompanyid, $projectid);
                $stmt->execute();
                if ($stmt->affected_rows === 0) {
                    $check = $krfconn->prepare("SELECT sno FROM krf_client_master WHERE sno = ? AND companyid = ? AND subcompanyid = ? AND projectid = ? AND IFNULL(tedit, '') = ''");
                    $check->bind_param('isss', $sno, $companyid, $subcompanyid, $projectid);
                    $check->execute();
                    if (!$check->get_result()->fetch_assoc()) throw new RuntimeException('Client record was not found.');
                    $check->close();
                }
                $stmt->close();
                $message = 'Client details updated successfully.';
            } else {
                // Lock the latest numeric C-series row so concurrent inserts
                // cannot calculate the same next client number.
                $seriesSql = "SELECT client_id
                              FROM krf_client_master
                              WHERE client_id REGEXP '^C[0-9]+$'
                              ORDER BY CAST(SUBSTRING(client_id, 2) AS UNSIGNED) DESC
                              LIMIT 1
                              FOR UPDATE";
                $seriesResult = $krfconn->query($seriesSql);
                if (!$seriesResult) throw new RuntimeException($krfconn->error);
                $lastSeriesRow = $seriesResult->fetch_assoc();
                $seriesResult->free();
                $nextClientNumber = $lastSeriesRow
                    ? ((int)substr((string)$lastSeriesRow['client_id'], 1) + 1)
                    : 1;

                do {
                    $clientId = 'C' . str_pad((string)$nextClientNumber, 5, '0', STR_PAD_LEFT);
                    $check = $krfconn->prepare('SELECT sno FROM krf_client_master WHERE client_id = ? LIMIT 1');
                    if (!$check) throw new RuntimeException($krfconn->error);
                    $check->bind_param('s', $clientId);
                    $check->execute();
                    $exists = (bool)$check->get_result()->fetch_assoc();
                    $check->close();
                    $nextClientNumber++;
                } while ($exists);

                $sql = "INSERT INTO krf_client_master
                        (companyid, subcompanyid, projectid, client_id, client_name, invoicing_address, phones,
                         client_email, stateid, statename, districtid, districtname, cityname, tedit, is_trader)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '', ?)";
                $stmt = $krfconn->prepare($sql);
                if (!$stmt) throw new RuntimeException($krfconn->error);
                $stmt->bind_param('sssssssssssssi', $companyid, $subcompanyid, $projectid, $clientId, $clientName, $invoicingAddress, $phones, $email, $stateid, $statename, $districtid, $districtname, $cityname, $isTrader);
                $stmt->execute();
                $sno = $stmt->insert_id;
                $stmt->close();
                $message = 'New client created successfully.';
            }
            $krfconn->commit();
            respond('success', $message, ['sno' => $sno]);
        } catch (Throwable $e) {
            $krfconn->rollback();
            throw $e;
        }
    }

    if ($action === 'deactivateClient' || $action === 'deleteClient') {
        $sno = (int)($_POST['sno'] ?? 0);
        $companyid = postText('companyid', 45);
        $subcompanyid = postText('subcompanyid', 45);
        $projectid = postText('projectid', 45);
        if ($sno <= 0) respond('error', 'Invalid client record.', [], 422);

        // Resolve the active client first. The caller does not need to send client_id.
        $stmt = $krfconn->prepare("SELECT client_id
                                   FROM krf_client_master
                                   WHERE sno = ? AND companyid = ? AND subcompanyid = ? AND projectid = ?
                                     AND IFNULL(tedit, '') = ''
                                   LIMIT 1");
        if (!$stmt) throw new RuntimeException($krfconn->error);
        $stmt->bind_param('isss', $sno, $companyid, $subcompanyid, $projectid);
        $stmt->execute();
        $client = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$client) {
            respond('error', 'Client record was not found or was already inactive.', [], 404);
        }

        $clientId = (string)$client['client_id'];

        // A client used in any provisional sales order must remain active.
        $stmt = $krfconn->prepare("SELECT 1
                                   FROM krf_provisional_sales_order
                                   WHERE clientid = ?
                                   LIMIT 1");
        if (!$stmt) throw new RuntimeException($krfconn->error);
        $stmt->bind_param('s', $clientId);
        $stmt->execute();
        $hasProvisionalOrder = (bool)$stmt->get_result()->fetch_row();
        $stmt->close();

        if ($hasProvisionalOrder) {
            respond(
                'error',
                'This client cannot be deactivated because it is used in a provisional sales order.',
                ['reason' => 'client_in_provisional_sales_order'],
                409
            );
        }

        $stmt = $krfconn->prepare("UPDATE krf_client_master SET tedit = 'inactive' WHERE sno = ? AND companyid = ? AND subcompanyid = ? AND projectid = ? AND IFNULL(tedit, '') = ''");
        if (!$stmt) throw new RuntimeException($krfconn->error);
        $stmt->bind_param('isss', $sno, $companyid, $subcompanyid, $projectid);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        if ($affected < 1) respond('error', 'Client record was not found or was already inactive.', [], 404);
        respond('success', 'Client deactivated successfully. The database record has been retained.');
    }

    respond('error', 'Invalid action.', [], 400);
} catch (Throwable $e) {
    error_log('client_modal.php: ' . $e->getMessage());
    respond('error', 'Unable to process the client request. Please try again.', [], 500);
}
