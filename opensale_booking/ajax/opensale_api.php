<?php
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
ob_start();
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

function os_out(bool $ok, string $message = '', array $extra = []): void {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['status' => $ok ? 'success' : 'error', 'message' => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Robust Open Sale Location SQL normalizer for list_stock and order booking.
 * Correctly maps Sargwan/Sargawa (LOC696b280faf104), Karjee (LOC6905e0fd5ab05),
 * and Mansoon Agro (LOC6a4f396f08173), preventing empty string leaks.
 */
function os_normalize_location_sql(mysqli $krfconn, string $locString, string $tableAlias = 's'): string {
    $loc = trim($locString);
    if ($loc === '') {
        return '';
    }
    $locLower = strtolower($loc);
    $prefix = !empty($tableAlias) ? "{$tableAlias}." : "";

    // 1. Sargawa / Sargwan center (LOC696b280faf104 / Polyhouse ID 19)
    if (strpos($locLower, 'sargw') !== false || strpos($locLower, 'sargaw') !== false || strpos($locLower, 'loc696b280faf104') !== false || $loc === '19') {
        return "({$prefix}open_sale_location = 'Sargawa' OR {$prefix}opensale_locationid = 'LOC696b280faf104')";
    }

    // 2. Karjee center (LOC6905e0fd5ab05 / Polyhouse ID 17)
    if (strpos($locLower, 'karjee') !== false || strpos($locLower, 'loc6905e0fd5ab05') !== false || $loc === '17') {
        return "({$prefix}open_sale_location = 'Karjee' OR {$prefix}opensale_locationid = 'LOC6905e0fd5ab05')";
    }

    // 3. Mansoon Agro center (LOC6a4f396f08173 / Polyhouse ID 18)
    if (strpos($locLower, 'mansoon') !== false || strpos($locLower, 'loc6a4f396f08173') !== false || $loc === '18') {
        return "({$prefix}open_sale_location = 'Mansoon' OR {$prefix}opensale_locationid = 'LOC6a4f396f08173')";
    }

    // 4. Safe fallback match: non-empty exact match or non-empty substring
    $esc = $krfconn->real_escape_string($loc);
    return "({$prefix}open_sale_location = '{$esc}' OR {$prefix}opensale_locationid = '{$esc}' OR ({$prefix}open_sale_location != '' AND {$prefix}open_sale_location LIKE '%{$esc}%'))";
}

register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => 'PHP fatal error: ' . $e['message'] . ' in ' . basename($e['file']) . ':' . $e['line']], JSON_UNESCAPED_UNICODE);
    }
});

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $root = dirname(__DIR__, 2);
    $connFile = $root . '/connection.php';
    if (!is_file($connFile)) {
        throw new RuntimeException('connection.php not found.');
    }
    require_once $connFile;

    if (!isset($krfconn) || !($krfconn instanceof mysqli)) {
        throw new RuntimeException('Database connection failed.');
    }

    $action  = (string)($_POST['action'] ?? $_GET['action'] ?? '');
    $project = (int)($_POST['projectid'] ?? $_GET['projectid'] ?? 17);
    if ($project <= 0) $project = 17;

    if (empty($action)) {
        os_out(false, 'Action is required.');
    }

    // -------------------------------------------------------------
    // 1. LIST AVAILABLE OPEN SALE STOCK
    // -------------------------------------------------------------
    if ($action === 'list_stock') {
        $loc = trim((string)($_POST['location'] ?? $_GET['location'] ?? ''));
        $search = trim((string)($_POST['search'] ?? $_GET['search'] ?? ''));

        $where = [
            "(s.projectid = '{$project}' OR '{$project}' = '0' OR s.projectid = '17')",
            "s.status = 'OpenSale'",
            "s.actual_plant_count > 0"
        ];

        if (!empty($loc)) {
            $locClause = os_normalize_location_sql($krfconn, $loc, 's');
            if (!empty($locClause)) {
                $where[] = $locClause;
            }
        }

        if (!empty($search)) {
            $sEsc = $krfconn->real_escape_string($search);
            $where[] = "(s.barcode LIKE '%{$sEsc}%' 
                         OR s.productname LIKE '%{$sEsc}%' 
                         OR s.variety LIKE '%{$sEsc}%' 
                         OR s.stockorder_no LIKE '%{$sEsc}%' 
                         OR s.from_polyhousename LIKE '%{$sEsc}%' 
                         OR s.from_bed_no LIKE '%{$sEsc}%' 
                         OR s.open_sale_location LIKE '%{$sEsc}%'
                         OR s.opensale_locationid LIKE '%{$sEsc}%')";
        }

        $whereSql = implode(' AND ', $where);

        $sql = "SELECT s.id, s.polyhouseplantid, s.barcode, s.stockorder_no, s.psono, s.productid, s.productname, s.variety,
                       s.trayid, s.tray_code, s.qtypertray, s.actual_plant_count, s.from_locationid, s.from_locationname,
                       s.from_polyhouseid, s.from_polyhousename, s.from_bed_no, s.open_sale_location, s.opensale_locationid, s.movement_reason,
                       s.status, s.remarks, s.created_by, s.created_at,
                       IFNULL(pm.PriceOfProduct, 0) AS standard_rate,
                       IFNULL(s.shiftingstatus, 'nonshifted') AS shiftingstatus
                FROM krf_opensale_stocks s
                LEFT JOIN product_master pm ON pm.ProductID = s.productid
                WHERE {$whereSql}
                GROUP BY s.id
                ORDER BY s.id ASC";

        $res = $krfconn->query($sql);
        $trays = [];
        $productsGrouped = [];

        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $pCount = (int)$r['actual_plant_count'];
                $r['created_at_fmt'] = !empty($r['created_at']) ? date('d-M-Y', strtotime($r['created_at'])) : '-';
                $r['standard_rate'] = (float)$r['standard_rate'];
                $r['shiftingstatus'] = (string)($r['shiftingstatus'] ?? 'nonshifted');
                $trays[] = $r;

                // Group strictly by productid with available quantity
                $pId = (string)($r['productid'] ?: ('P_' . md5($r['productname'])));
                $grpKey = $pId;
                if (!isset($productsGrouped[$grpKey])) {
                    $productsGrouped[$grpKey] = [
                        'group_key'          => $grpKey,
                        'productid'          => $r['productid'],
                        'productname'        => $r['productname'],
                        'variety'            => $r['variety'],
                        'open_sale_location' => $r['open_sale_location'],
                        'rate'               => (float)$r['standard_rate'],
                        'total_plants'       => 0,
                        'total_trays'        => 0,
                        'stockorders'        => [],
                        'trays'              => []
                    ];
                }
                $productsGrouped[$grpKey]['total_plants'] += $pCount;
                $productsGrouped[$grpKey]['total_trays']++;
                if (!empty($r['stockorder_no']) && !in_array($r['stockorder_no'], $productsGrouped[$grpKey]['stockorders'])) {
                    $productsGrouped[$grpKey]['stockorders'][] = $r['stockorder_no'];
                }
                $productsGrouped[$grpKey]['trays'][] = [
                    'id'                 => $r['id'],
                    'barcode'            => $r['barcode'],
                    'stockorder_no'      => $r['stockorder_no'],
                    'plants'             => $pCount,
                    'bed_no'             => $r['from_bed_no'],
                    'polyhousename'      => $r['from_polyhousename'],
                    'open_sale_location' => $r['open_sale_location']
                ];
            }
        }

        // Sort grouped products alphabetically by productname
        $groupedList = array_values($productsGrouped);
        usort($groupedList, function ($a, $b) {
            return strcasecmp($a['productname'] ?? '', $b['productname'] ?? '');
        });

        // Fetch active Open Sale Centers from krf_polyhouse_master
        $centers = [];
        $cRes = $krfconn->query("SELECT polyhouseid, polyhousename, locationid 
                                 FROM krf_polyhouse_master 
                                 WHERE (projectid = '{$project}' OR '{$project}' = '0' OR projectid = '17') 
                                   AND (type = 'OpenSale' OR structure_type = 'OpenSale' OR polyhousename LIKE '%Open%Sale%') 
                                   AND (status = 'Active' OR status IS NULL) 
                                 ORDER BY polyhouseid ASC");
        if ($cRes) {
            while ($cRow = $cRes->fetch_assoc()) {
                $centers[] = $cRow;
            }
        }

        os_out(true, '', [
            'trays'                  => $trays,
            'grouped_products'       => $groupedList,
            'opensale_centers'       => $centers,
            'total_available_trays'  => count($trays),
            'total_available_plants' => array_sum(array_column($trays, 'actual_plant_count'))
        ]);
    }

    // -------------------------------------------------------------
    // LIST OPEN SALE CENTERS
    // -------------------------------------------------------------
    if ($action === 'list_opensale_centers') {
        $cRes = $krfconn->query("SELECT polyhouseid, polyhousename, locationid 
                                 FROM krf_polyhouse_master 
                                 WHERE (projectid = '{$project}' OR '{$project}' = '0' OR projectid = '17') 
                                   AND (type = 'OpenSale' OR structure_type = 'OpenSale' OR polyhousename LIKE '%Open%Sale%') 
                                   AND (status = 'Active' OR status IS NULL) 
                                 ORDER BY polyhouseid ASC");
        $centers = [];
        if ($cRes) {
            while ($cRow = $cRes->fetch_assoc()) {
                $centers[] = $cRow;
            }
        }
        os_out(true, '', ['centers' => $centers]);
    }

    // -------------------------------------------------------------
    // 2. SEARCH CLIENTS
    // -------------------------------------------------------------
    if ($action === 'search_clients') {
        $q = trim((string)($_POST['q'] ?? $_GET['q'] ?? ''));
        $qEsc = $krfconn->real_escape_string($q);
        
        $sql = "SELECT client_id, client_name, phones, client_email, invoicing_address, districtname, statename
                FROM krf_client_master
                WHERE (projectid = '{$project}' OR projectid = '0' OR projectid = '17')
                  AND IFNULL(tedit, '') = ''
                  " . (!empty($q) ? "AND (client_name LIKE '%{$qEsc}%' OR phones LIKE '%{$qEsc}%' OR client_id LIKE '%{$qEsc}%')" : "") . "
                ORDER BY client_name ASC
                LIMIT 50";

        $res = $krfconn->query($sql);
        $clients = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $clients[] = [
                    'id'        => $row['client_id'],
                    'name'      => $row['client_name'],
                    'phone'     => $row['phones'],
                    'email'     => $row['client_email'],
                    'address'   => $row['invoicing_address'],
                    'district'  => $row['districtname'],
                    'state'     => $row['statename']
                ];
            }
        }
        os_out(true, '', ['clients' => $clients]);
    }

    // -------------------------------------------------------------
    // 3. GET STATES & DISTRICTS
    // -------------------------------------------------------------
    if ($action === 'states') {
        $st = $krfconn->prepare("SELECT stateid, statename FROM master_states ORDER BY statename");
        $st->execute();
        $data = $st->get_result()->fetch_all(MYSQLI_ASSOC);
        $st->close();
        os_out(true, '', ['states' => $data]);
    }

    if ($action === 'districts') {
        $stateid = trim((string)($_POST['stateid'] ?? ''));
        if ($stateid === '') os_out(true, '', ['districts' => []]);
        $st = $krfconn->prepare("SELECT districtid, districtname FROM master_districts WHERE stateid=? ORDER BY districtname");
        $st->bind_param('s', $stateid);
        $st->execute();
        $data = $st->get_result()->fetch_all(MYSQLI_ASSOC);
        $st->close();
        os_out(true, '', ['districts' => $data]);
    }

    // -------------------------------------------------------------
    // 4. CREATE NEW CLIENT
    // -------------------------------------------------------------
    if ($action === 'create_client') {
        $company    = isset($_SESSION['companyid']) && intval($_SESSION['companyid']) > 0 ? (string)$_SESSION['companyid'] : ((isset($_POST['companyid']) && intval($_POST['companyid']) > 0 && intval($_POST['companyid']) != 1) ? (string)$_POST['companyid'] : '19');
        $subcompany = isset($_SESSION['subcompanyid']) && intval($_SESSION['subcompanyid']) > 0 ? (string)$_SESSION['subcompanyid'] : ((isset($_POST['subcompanyid']) && intval($_POST['subcompanyid']) > 0 && intval($_POST['subcompanyid']) != 1) ? (string)$_POST['subcompanyid'] : '12');
        $name       = trim((string)($_POST['client_name'] ?? ''));
        $phone      = preg_replace('/\D+/', '', (string)($_POST['phone'] ?? ''));
        $email      = trim((string)($_POST['email'] ?? ''));
        $address    = trim((string)($_POST['address'] ?? ''));
        $statename  = trim((string)($_POST['statename'] ?? ''));
        $districtname = trim((string)($_POST['districtname'] ?? ''));
        $stateid    = trim((string)($_POST['stateid'] ?? ''));
        $districtid = trim((string)($_POST['districtid'] ?? ''));
        $tahseel    = trim((string)($_POST['tahseel'] ?? ''));
        $cityname   = trim((string)($_POST['cityname'] ?? ''));

        if (empty($name) || empty($phone)) {
            os_out(false, 'Client Name and Mobile Number are required.');
        }

        // Check for existing client with same mobile number
        $dup = $krfconn->prepare("SELECT client_id, client_name, invoicing_address, statename, districtname, tahseel, cityname FROM krf_client_master WHERE phones = ? AND (tedit IS NULL OR tedit = '' OR tedit = '0') LIMIT 1");
        if ($dup) {
            $dup->bind_param("s", $phone);
            $dup->execute();
            $dupRes = $dup->get_result()->fetch_assoc();
            $dup->close();
            if ($dupRes) {
                os_out(true, "Client with phone {$phone} already exists (ID: {$dupRes['client_id']}). Selected automatically.", [
                    'client_id' => $dupRes['client_id'],
                    'client' => [
                        'id'       => $dupRes['client_id'],
                        'name'     => $dupRes['client_name'],
                        'phone'    => $phone,
                        'email'    => $email,
                        'address'  => $dupRes['invoicing_address'] ?: $address,
                        'district' => $dupRes['districtname'] ?: $districtname,
                        'state'    => $dupRes['statename'] ?: $statename,
                        'tahseel'  => $dupRes['tahseel'] ?: $tahseel,
                        'city'     => $dupRes['cityname'] ?: $cityname
                    ]
                ]);
            }
        }

        // Generate next standardized client ID (e.g. C02432)
        $resq = $krfconn->query("SELECT IFNULL(MAX(CAST(SUBSTRING(client_id, 2) AS UNSIGNED)), 0) + 1 AS next_id FROM krf_client_master WHERE client_id LIKE 'C%'");
        $next_id = ($resq ? (int)$resq->fetch_assoc()['next_id'] : 1);
        $cid = 'C' . str_pad($next_id, 5, '0', STR_PAD_LEFT);

        $stmt = $krfconn->prepare("
            INSERT INTO krf_client_master 
            (client_id, client_name, phones, client_email, invoicing_address, 
             stateid, statename, districtid, districtname, tahseel, cityname,
             projectid, companyid, subcompanyid, date_time, tedit)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), '')
        ");

        if (!$stmt) {
            throw new Exception("Client statement prepare failed: " . $krfconn->error);
        }

        $stmt->bind_param('ssssssssssssss',
            $cid, $name, $phone, $email, $address,
            $stateid, $statename, $districtid, $districtname, $tahseel, $cityname,
            $project, $company, $subcompany
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to insert client into krf_client_master: " . $stmt->error);
        }
        $stmt->close();

        os_out(true, 'Client created successfully in krf_client_master.', [
            'client_id' => $cid,
            'client' => [
                'id'       => $cid,
                'name'     => $name,
                'phone'    => $phone,
                'email'    => $email,
                'address'  => $address,
                'district' => $districtname,
                'state'    => $statename,
                'tahseel'  => $tahseel,
                'city'     => $cityname
            ]
        ]);
    }

    // -------------------------------------------------------------
    // 5. GET CLIENT PENDING ORDERS FOR PAYMENT
    // -------------------------------------------------------------
    if ($action === 'get_client_pending_orders') {
        $clientId = trim((string)($_POST['clientid'] ?? $_GET['clientid'] ?? ''));
        if (empty($clientId)) {
            os_out(false, 'Client ID is required.');
        }

        $stmt = $krfconn->prepare("
            SELECT 
                pso.psono,
                pso.companyid,
                pso.subcompanyid,
                pso.projectid,
                pso.status AS pso_status,
                IFNULL(so.sono, '') AS sono,
                GROUP_CONCAT(DISTINCT psoi.product_name SEPARATOR ', ') AS product_name,
                SUM(psoi.quantity) AS qty,
                MIN(psoi.expecteddeliverydate) AS expecteddeliverydate,
                ROUND(SUM(psoi.amount), 2) AS order_amount,
                IFNULL(pay.total_paid, 0) AS paid_amount,
                IFNULL(pay.total_discount, 0) AS discount_amount,
                ROUND(SUM(psoi.amount) - IFNULL(pay.total_paid, 0) - IFNULL(pay.total_discount, 0), 2) AS balance_amount
            FROM krf_provisional_sales_order pso
            LEFT JOIN krf_sales_order so ON so.psono = pso.psono
            JOIN krf_provisional_sales_order_items psoi ON psoi.psono = pso.psono
            LEFT JOIN (
                SELECT 
                    invoiceno,
                    SUM(advanceamount) AS total_paid,
                    SUM(otherdiscount) AS total_discount
                FROM advance_payment_transactions
                WHERE (tedit IS NULL OR tedit = '')
                GROUP BY invoiceno
            ) pay ON (pay.invoiceno = pso.psono OR (so.sono IS NOT NULL AND so.sono != '' AND pay.invoiceno = so.sono))
            WHERE pso.clientid = ?
              AND pso.status != 'Cancelled'
            GROUP BY pso.psono
            HAVING balance_amount > 0.01
            ORDER BY pso.id DESC
        ");

        if (!$stmt) {
            os_out(false, 'Failed to prepare query: ' . $krfconn->error);
        }

        $stmt->bind_param('s', $clientId);
        $stmt->execute();
        $res = $stmt->get_result();
        $orders = [];
        while ($r = $res->fetch_assoc()) {
            $orders[] = [
                'psono'                 => $r['psono'],
                'sono'                  => $r['sono'],
                'pso_status'            => $r['pso_status'] ?: 'Draft',
                'companyid'             => intval($r['companyid'] ?? 0),
                'subcompanyid'          => intval($r['subcompanyid'] ?? 0),
                'projectid'             => intval($r['projectid'] ?? 0),
                'product_name'          => $r['product_name'] ?: 'General Order Plants',
                'qty'                   => (int)$r['qty'],
                'expecteddeliverydate'  => !empty($r['expecteddeliverydate']) ? date('d-M-Y', strtotime($r['expecteddeliverydate'])) : '-',
                'expecteddeliverydate_raw' => $r['expecteddeliverydate'] ?: '',
                'order_amount'          => (float)$r['order_amount'],
                'paid_amount'           => (float)$r['paid_amount'],
                'discount_amount'       => (float)$r['discount_amount'],
                'balance_amount'        => (float)$r['balance_amount']
            ];
        }
        $stmt->close();

        os_out(true, '', ['orders' => $orders]);
    }

    // -------------------------------------------------------------
    // 6. GET PRODUCT STANDARD RATE
    // -------------------------------------------------------------
    if ($action === 'product_rate') {
        $pid = trim((string)($_POST['productid'] ?? $_GET['productid'] ?? ''));
        if (empty($pid)) os_out(false, 'Product ID is required.');

        $rate = 0.0;
        $st = $krfconn->prepare("SELECT PriceOfProduct FROM product_master WHERE ProductID = ? LIMIT 1");
        if ($st) {
            $st->bind_param('s', $pid);
            $st->execute();
            $row = $st->get_result()->fetch_assoc();
            $st->close();
            if ($row && !empty($row['PriceOfProduct'])) {
                $rate = (float)$row['PriceOfProduct'];
            }
        }
        os_out(true, '', ['rate' => $rate]);
    }

    // -------------------------------------------------------------
    // 6.5. CHECK DUPLICATE TRANSACTION / UTR NUMBER
    // -------------------------------------------------------------
    if ($action === 'check_duplicate_utr') {
        $utr = trim((string)($_POST['utr'] ?? $_GET['utr'] ?? ''));
        if (empty($utr)) {
            os_out(true, 'Empty transaction reference.', ['is_duplicate' => false]);
        }

        $stmt = $krfconn->prepare("
            SELECT sno, invoiceno, advanceamount, paymentdate, purposeofpayment, sourceofpayment, clientname
            FROM advance_payment_transactions
            WHERE utrnumber = ? AND utrnumber != ''
            ORDER BY sno DESC
            LIMIT 1
        ");
        if (!$stmt) {
            os_out(false, 'Database error while checking UTR: ' . $krfconn->error);
        }
        $stmt->bind_param('s', $utr);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($row = $res->fetch_assoc()) {
            os_out(true, 'Duplicate transaction reference found in database records.', [
                'is_duplicate' => true,
                'match' => [
                    'sno'        => (int)$row['sno'],
                    'invoiceno'  => (string)$row['invoiceno'],
                    'amount'     => (float)$row['advanceamount'],
                    'date'       => (string)$row['paymentdate'],
                    'purpose'    => (string)$row['purposeofpayment'],
                    'mode'       => (string)$row['sourceofpayment'],
                    'clientname' => (string)$row['clientname']
                ]
            ]);
        } else {
            os_out(true, 'Transaction reference is unique.', ['is_duplicate' => false]);
        }
        $stmt->close();
    }

    // -------------------------------------------------------------
    // Helper function to process uploaded payment receipt/proof document
    // -------------------------------------------------------------
    function handlePaymentReceiptUpload(string $inputKey, int $company, int $subcompany, int $project): string {
        if (!isset($_FILES[$inputKey]) || !is_array($_FILES[$inputKey])) {
            return '';
        }
        $f = $_FILES[$inputKey];
        if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return '';
        }
        $origName = (string)($f['name'] ?? '');
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf'];
        if (!in_array($ext, $allowed, true)) {
            return '';
        }
        if (($f['size'] ?? 0) > 15 * 1024 * 1024) {
            return '';
        }
        $cDate = date('Y-m-d');
        $root = dirname(__DIR__, 2);
        $targetDir = $root . "/attachementfile/{$company}/{$subcompany}/{$project}/OrderPaymentReceipt/{$cDate}/";
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0777, true);
        }
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $origName);
        $filename = time() . '_' . mt_rand(1000, 9999) . '_' . $safeName;
        $targetPath = $targetDir . $filename;
        if (move_uploaded_file($f['tmp_name'], $targetPath)) {
            return "attachementfile/{$company}/{$subcompany}/{$project}/OrderPaymentReceipt/{$cDate}/{$filename}";
        }
        return '';
    }

    // -------------------------------------------------------------
    // 7. BOOK ORDER, GENERATE DRAFT PSO, RECORD PAYMENT, CONFIRM SO,
    //    RECORD CONSUMPTION, READY STOCK ALLOCATION & DISPATCH INVOICE
    // -------------------------------------------------------------
    if ($action === 'book_opensale_order') {
        $company    = isset($_SESSION['companyid']) && intval($_SESSION['companyid']) > 0 ? intval($_SESSION['companyid']) : ((isset($_POST['companyid']) && intval($_POST['companyid']) > 0 && intval($_POST['companyid']) != 1) ? intval($_POST['companyid']) : 19);
        $subcompany = isset($_SESSION['subcompanyid']) && intval($_SESSION['subcompanyid']) > 0 ? intval($_SESSION['subcompanyid']) : ((isset($_POST['subcompanyid']) && intval($_POST['subcompanyid']) > 0 && intval($_POST['subcompanyid']) != 1) ? intval($_POST['subcompanyid']) : 12);
        $project    = isset($_SESSION['projectid']) && intval($_SESSION['projectid']) > 0 ? intval($_SESSION['projectid']) : (isset($_POST['projectid']) && intval($_POST['projectid']) > 0 ? intval($_POST['projectid']) : 17);
        $login      = trim((string)($_POST['loginid'] ?? ($_SESSION['loginid'] ?? 'Admin')));
        $client_id  = trim((string)($_POST['clientid'] ?? ''));
        $open_sale_loc = trim((string)($_POST['open_sale_location'] ?? 'Karjee'));
        $pickupfrom = trim((string)($_POST['pickupfrom'] ?? 'pickupbyclient'));
        $vehicleno  = trim((string)($_POST['vehicleno'] ?? ''));
        $drivername = trim((string)($_POST['drivername'] ?? ''));
        $driverphone= trim((string)($_POST['driverphone'] ?? ''));
        $orderRemarks = trim((string)($_POST['order_remarks'] ?? ''));

        // Payment info
        $amountPaid   = (float)($_POST['amount_paid'] ?? 0);
        $specialDiscount = (float)($_POST['special_discount'] ?? 0);
        $paymentMode  = trim((string)($_POST['payment_mode'] ?? 'Cash'));
        $utrNumber    = trim((string)($_POST['utr_number'] ?? ''));
        $payRemarks   = trim((string)($_POST['payment_remarks'] ?? ''));

        // Payment splits JSON (when multiple payment modes are used)
        $paymentSplitsJson = (string)($_POST['payment_splits'] ?? '[]');
        $paymentSplits = json_decode($paymentSplitsJson, true);
        if (!is_array($paymentSplits) || empty($paymentSplits)) {
            $paymentSplits = [];
            if ($amountPaid > 0) {
                $paymentSplits[] = [
                    'mode'   => $paymentMode ?: 'Cash',
                    'amount' => $amountPaid,
                    'utr'    => $utrNumber
                ];
            }
        }

        // Pending orders payments JSON
        $pendingPaymentsJson = (string)($_POST['pending_orders_payments'] ?? '[]');
        $pendingPayments = json_decode($pendingPaymentsJson, true);
        if (!is_array($pendingPayments)) {
            $pendingPayments = [];
        }

        // Items JSON
        $itemsJson = (string)($_POST['items'] ?? '[]');
        $items = json_decode($itemsJson, true);

        if (empty($client_id)) {
            os_out(false, 'Please select or add a client.');
        }
        if (!is_array($items) || count($items) === 0) {
            os_out(false, 'Please select at least one open sale plant / tray.');
        }

        // Fetch client details from krf_client_master (Sale Client)
        $cs = $krfconn->prepare("SELECT client_id, client_name, phones, client_email, invoicing_address, districtname, statename, companyid, subcompanyid
                                 FROM krf_client_master 
                                 WHERE client_id = ? LIMIT 1");
        $cs->bind_param('s', $client_id);
        $cs->execute();
        $client = $cs->get_result()->fetch_assoc();
        $cs->close();

        if (!$client) {
            os_out(false, 'Selected client not found in master records.');
        }

        $client['client_name']       = (string)($client['client_name'] ?? '');
        $client['phones']            = (string)($client['phones'] ?? '');
        $client['client_email']      = (string)($client['client_email'] ?? '');
        $client['invoicing_address'] = (string)($client['invoicing_address'] ?? '');
        $client['districtname']      = (string)($client['districtname'] ?? '');
        $client['statename']         = (string)($client['statename'] ?? '');

        // Inherit company and subcompany from the selected client master record if present
        if (!empty($client['companyid']) && intval($client['companyid']) > 0) {
            $company = intval($client['companyid']);
        }
        if (!empty($client['subcompanyid']) && intval($client['subcompanyid']) > 0) {
            $subcompany = intval($client['subcompanyid']);
        }

        // Begin Atomic Transaction
        $krfconn->begin_transaction();

        try {
            // 1. Generate sequential order numbers
            // 1.1 Generate unique standard PSO Number (e.g. KF-PS-01405)
            $resPso = $krfconn->query("SELECT psono FROM krf_provisional_sales_order WHERE psono LIKE 'KF-PS-%' ORDER BY id DESC LIMIT 1");
            $nextPsoNum = 1;
            if ($resPso && ($rowP = $resPso->fetch_assoc())) {
                $lastPso = $rowP['psono'];
                $digitsP = intval(substr($lastPso, strrpos($lastPso, '-') + 1));
                if ($digitsP > 0) $nextPsoNum = $digitsP + 1;
            }
            do {
                $psonoCandidate = 'KF-PS-' . str_pad($nextPsoNum, 5, '0', STR_PAD_LEFT);
                $chkPso = $krfconn->query("SELECT id FROM krf_provisional_sales_order WHERE psono = '{$psonoCandidate}' LIMIT 1");
                if (!$chkPso || $chkPso->num_rows === 0) {
                    $psono = $psonoCandidate;
                    break;
                }
                $nextPsoNum++;
            } while (true);

            // 1.2 Generate unique standard Sales Order Number (e.g. KF-CS-1234)
            do {
                $randSono = mt_rand(1000, 9999);
                $sonoCandidate = 'KF-CS-' . str_pad($randSono, 4, '0', STR_PAD_LEFT);
                $chkSo = $krfconn->query("SELECT id FROM krf_sales_order WHERE sono = '{$sonoCandidate}' LIMIT 1");
                if (!$chkSo || $chkSo->num_rows === 0) {
                    $sono = $sonoCandidate;
                    break;
                }
            } while (true);

            // 1.3 Generate Sales Invoice Serial & No
            $resSerial = $krfconn->query("SELECT IFNULL(MAX(serialno), 0) + 1 AS next_serial FROM sales_invoice WHERE projectid = " . intval($project));
            $invoiceSerialNo = ($resSerial && ($rSer = $resSerial->fetch_assoc())) ? (int)$rSer['next_serial'] : 1;
            $dateCode = date('ymd');
            $randHex = strtoupper(substr(uniqid(), -4));
            $invoiceno = "KF-OS-{$dateCode}-{$randHex}";

            // 1.4 Generate Gate Pass Number (e.g. GP-58291)
            do {
                $rand5 = mt_rand(10000, 99999);
                $gatepassNo = 'GP-' . $rand5;
                $chkExists = $krfconn->query("SELECT id FROM krf_gatepass_tracking WHERE gatepass_no = '{$gatepassNo}' LIMIT 1");
                if (!$chkExists || $chkExists->num_rows === 0) break;
            } while (true);

            $totalQty = 0;
            $totalTaxable = 0.0;
            $totalTax = 0.0;
            $totalDiscount = 0.0;
            $processedItems = [];
            $stockTraysToUpdate = [];

            foreach ($items as $idx => $it) {
                $mode        = $it['mode'] ?? 'tray';
                $productId   = trim($it['productid'] ?? '');
                $productName = trim($it['productname'] ?? '');
                $variety     = trim($it['variety'] ?? '');
                $qty         = (int)($it['quantity'] ?? 0);
                $foc         = max(0, (int)($it['foc'] ?? 0));
                if ($foc >= $qty && $qty > 0) {
                    $foc = max(0, $qty - 1);
                }
                $billableQty = max(0, $qty - $foc);
                $rate        = (float)($it['rate'] ?? 0);
                $discPercent = (float)($it['discount_percent'] ?? 0);
                $taxPercent  = (float)($it['tax_percent'] ?? 0);
                $trayBarcode = trim($it['barcode'] ?? '');

                if ($qty <= 0) {
                    throw new Exception("Invalid quantity for line item " . ($idx + 1));
                }

                // If productName is missing, numeric, or '0', resolve from product_master / opensale stock
                if (empty($productName) || is_numeric($productName) || $productName === '0') {
                    if (!empty($productId)) {
                        $pmQ = $krfconn->query("SELECT ProductName, Variety FROM product_master WHERE ProductID = '{$krfconn->real_escape_string($productId)}' LIMIT 1");
                        if ($pmQ && ($pmR = $pmQ->fetch_assoc())) {
                            $productName = (string)($pmR['ProductName'] ?? '');
                            if (empty($variety)) $variety = (string)($pmR['Variety'] ?? '');
                        }
                    }
                    if ((empty($productName) || $productName === '0') && !empty($trayBarcode)) {
                        $stkQ = $krfconn->query("SELECT productname, variety FROM krf_opensale_stocks WHERE barcode = '{$krfconn->real_escape_string($trayBarcode)}' LIMIT 1");
                        if ($stkQ && ($stkR = $stkQ->fetch_assoc())) {
                            $productName = (string)($stkR['productname'] ?? '');
                            if (empty($variety)) $variety = (string)($stkR['variety'] ?? '');
                        }
                    }
                }

                // Calculation on billable plants (FOC plants are free of payment)
                $gross = round($billableQty * $rate, 2);
                $discAmt = round($gross * ($discPercent / 100), 2);
                $taxable = round($gross - $discAmt, 2);
                $taxAmt = round($taxable * ($taxPercent / 100), 2);
                $itemTotal = round($taxable + $taxAmt, 2);

                $totalQty += $qty;
                $totalTaxable += $taxable;
                $totalTax += $taxAmt;
                $totalDiscount += $discAmt;

                // Fetch SKU from product_master if available
                $productSku = '';
                if (!empty($productId)) {
                    $skuRes = $krfconn->query("SELECT sku FROM product_master WHERE ProductID = '{$krfconn->real_escape_string($productId)}' LIMIT 1");
                    if ($skuRes && ($skuRow = $skuRes->fetch_assoc())) {
                        $productSku = (string)($skuRow['sku'] ?? '');
                    }
                }

                // Stock allocation
                $stockOrderNo = 'OPEN_STOCK';
                if ($mode === 'tray' && !empty($trayBarcode)) {
                    $stkRes = $krfconn->query("SELECT id, actual_plant_count, stockorder_no, barcode FROM krf_opensale_stocks WHERE barcode = '{$krfconn->real_escape_string($trayBarcode)}' AND status = 'OpenSale' FOR UPDATE");
                    $stkRow = $stkRes ? $stkRes->fetch_assoc() : null;
                    if (!$stkRow || (int)$stkRow['actual_plant_count'] < $qty) {
                        throw new Exception("Tray {$trayBarcode} is no longer available in open sale stock.");
                    }
                    $stockOrderNo = $stkRow['stockorder_no'] ?: 'OPEN_STOCK';
                    $stockTraysToUpdate[] = [
                        'id' => (int)$stkRow['id'],
                        'barcode' => $trayBarcode,
                        'deduct_qty' => $qty,
                        'available' => (int)$stkRow['actual_plant_count']
                    ];
                } else {
                    $itemLoc = !empty($it['open_sale_location']) ? trim($it['open_sale_location']) : $open_sale_loc;
                    $locSql = os_normalize_location_sql($krfconn, $itemLoc, '');
                    $locCond = !empty($locSql) ? "AND {$locSql}" : "";
                    $stkRes = $krfconn->query("
                        SELECT id, actual_plant_count, stockorder_no, barcode 
                        FROM krf_opensale_stocks 
                        WHERE productid = '{$krfconn->real_escape_string($productId)}' 
                          AND status = 'OpenSale' 
                          AND actual_plant_count > 0 
                          {$locCond}
                        ORDER BY id ASC 
                        FOR UPDATE
                    ");
                    $needed = $qty;
                    while ($stkRow = $stkRes->fetch_assoc()) {
                        if ($needed <= 0) break;
                        $availInTray = (int)$stkRow['actual_plant_count'];
                        $take = min($needed, $availInTray);
                        $stockTraysToUpdate[] = [
                            'id' => (int)$stkRow['id'],
                            'barcode' => $stkRow['barcode'],
                            'deduct_qty' => $take,
                            'available' => $availInTray
                        ];
                        $needed -= $take;
                        if ($stockOrderNo === 'OPEN_STOCK' && !empty($stkRow['stockorder_no'])) {
                            $stockOrderNo = $stkRow['stockorder_no'];
                        }
                    }
                    if ($needed > 0) {
                        $locMsg = !empty($itemLoc) ? " in {$itemLoc}" : "";
                        throw new Exception("Insufficient open sale stock for product '{$productName}'{$locMsg}. Missing {$needed} plants.");
                    }
                }

                $processedItems[] = [
                    'productid'        => $productId,
                    'product_sku'      => $productSku,
                    'productname'      => $productName,
                    'variety'          => $variety,
                    'stockorder_no'    => $stockOrderNo,
                    'quantity'         => $qty,
                    'foc'              => $foc,
                    'billable_qty'     => $billableQty,
                    'rate'             => $rate,
                    'discount_percent' => $discPercent,
                    'discount_amount'  => $discAmt,
                    'taxableamount'    => $taxable,
                    'cgst_percent'     => 0.0,
                    'cgst_amount'      => 0.0,
                    'sgst_percent'     => 0.0,
                    'sgst_amount'      => 0.0,
                    'igst_percent'     => $taxPercent,
                    'igst_amount'      => $taxAmt,
                    'tax_amount'       => $taxAmt,
                    'amount'           => $itemTotal
                ];
            }

            // Box Packing Charges (Billable Line Item)
            $isBoxPacking = !empty($_POST['is_box_packing']);
            $boxCount = $isBoxPacking ? max(0, (int)($_POST['box_count'] ?? 0)) : 0;
            $boxRate = (float)($_POST['box_rate'] ?? 100.0);
            $boxTotal = $isBoxPacking ? round($boxCount * $boxRate, 2) : 0.0;

            if ($isBoxPacking && $boxCount > 0 && $boxTotal > 0) {
                $totalTaxable += $boxTotal;
                $processedItems[] = [
                    'productid'        => 'BOX_PACKING',
                    'product_sku'      => 'BOX',
                    'productname'      => "Box Packing Charges ({$boxCount} Boxes @ Rs " . number_format($boxRate, 2, '.', '') . ")",
                    'variety'          => 'Box Packing',
                    'stockorder_no'    => 'BOX_PACKING',
                    'quantity'         => $boxCount,
                    'foc'              => 0,
                    'billable_qty'     => $boxCount,
                    'rate'             => $boxRate,
                    'discount_percent' => 0.0,
                    'discount_amount'  => 0.0,
                    'taxableamount'    => $boxTotal,
                    'cgst_percent'     => 0.0,
                    'cgst_amount'      => 0.0,
                    'sgst_percent'     => 0.0,
                    'sgst_amount'      => 0.0,
                    'igst_percent'     => 0.0,
                    'igst_amount'      => 0.0,
                    'tax_amount'       => 0.0,
                    'amount'           => $boxTotal,
                    'is_box_packing'   => 1
                ];
            }

            $grossInvoice = $totalTaxable + $totalTax;
            $roundOff = round(round($grossInvoice) - $grossInvoice, 2);
            $finalInvoiceAmount = round($grossInvoice + $roundOff, 2);
            $netPayableAmount = max(0.0, round($finalInvoiceAmount - $specialDiscount, 2));
            $finalPaid = min($netPayableAmount, $amountPaid);
            $finalBalance = max(0.0, round($netPayableAmount - $finalPaid, 2));

            $curDate = date('Y-m-d');
            $fullRemarks = "[Open Sale Direct Booking | {$open_sale_loc} Farm] " . $orderRemarks;

            // -------------------------------------------------------------
            // STEP 1: Insert into krf_provisional_sales_order & items as 'Draft'
            // -------------------------------------------------------------
            $psoH = $krfconn->prepare("
                INSERT INTO krf_provisional_sales_order (
                    psono, companyid, subcompanyid, projectid, clientid, clientname,
                    mobileno, emailid, delivery_address, districtname, statename,
                    remarks, created_by, pickupfrom, status, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, 'Draft', NOW()
                )
            ");
            if (!$psoH) {
                throw new Exception("Failed to prepare krf_provisional_sales_order: " . $krfconn->error);
            }
            $psoH->bind_param(
                'siiissssssssss',
                $psono, $company, $subcompany, $project, $client_id, $client['client_name'],
                $client['phones'], $client['client_email'], $client['invoicing_address'], $client['districtname'], $client['statename'],
                $fullRemarks, $login, $pickupfrom
            );
            if (!$psoH->execute()) {
                throw new Exception("Failed to save Provisional Sales Order in krf_provisional_sales_order: " . $psoH->error);
            }
            $psoH->close();

            $psoI = $krfconn->prepare("
                INSERT INTO krf_provisional_sales_order_items (
                    projectid, clientid, psono, stockorder_no, product_sku, product_id, product_name,
                    quantity, rate, taxableamount, cgst_percent, cgst_amount, sgst_percent, sgst_amount,
                    igst_percent, igst_amount, discount_percent, discount_amount, amount, roundoffamount,
                    expecteddeliverydate, created_by, status
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, 'Draft'
                )
            ");
            if (!$psoI) {
                throw new Exception("Failed to prepare krf_provisional_sales_order_items: " . $krfconn->error);
            }

            foreach ($processedItems as $it) {
                $psoI->bind_param(
                    'issssssdddddddddddddss',
                    $project, $client_id, $psono, $it['stockorder_no'], $it['product_sku'], $it['productid'], $it['productname'],
                    $it['billable_qty'], $it['rate'], $it['taxableamount'], $it['cgst_percent'], $it['cgst_amount'],
                    $it['sgst_percent'], $it['sgst_amount'], $it['igst_percent'], $it['igst_amount'],
                    $it['discount_percent'], $it['discount_amount'], $it['amount'], $roundOff,
                    $curDate, $login
                );
                if (!$psoI->execute()) {
                    throw new Exception("Failed to insert item into krf_provisional_sales_order_items: " . $psoI->error);
                }
            }
            $psoI->close();

            // -------------------------------------------------------------
            // STEP 2: Save payment details against PSO & Confirm Provisional Order
            // -------------------------------------------------------------
            $primaryReceiptPath = '';
            if ($finalPaid > 0 || $specialDiscount > 0) {
                $payStmt = $krfconn->prepare("
                    INSERT INTO advance_payment_transactions (
                        companyid, subcompanyid, projectid, clientid, clientname,
                        advanceamount, roundoffamount, otherdiscount, utrnumber, paymentdate,
                        purposeofpayment, invoiceno, sourceofpayment, receivedby,
                        receivedlocation, receiptpath, remarks, transaction_type, verification,
                        allocated_datetime, allocatedby
                    ) VALUES (
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        'Open Sale Order Payment', ?, ?, ?,
                        ?, ?, ?, 'Receipt', 'verified',
                        NOW(), ?
                    )
                ");
                if (!$payStmt) {
                    throw new Exception("Failed to prepare advance_payment_transactions: " . $krfconn->error);
                }
                $payLoc = "{$open_sale_loc} Farm";

                // Filter splits with positive amount
                $validSplits = array_values(array_filter($paymentSplits, function($s) {
                    return (float)($s['amount'] ?? 0) > 0;
                }));

                // Pre-validate all UTRs against duplicate submission and existing DB records
                $seenUtrs = [];
                foreach ($validSplits as $sp) {
                    $spUtr = trim($sp['utr'] ?? '');
                    if (!empty($spUtr)) {
                        if (in_array(strtolower($spUtr), $seenUtrs, true)) {
                            throw new Exception("Duplicate Transaction Reference '{$spUtr}' detected! The same transaction number cannot be entered multiple times.");
                        }
                        $seenUtrs[] = strtolower($spUtr);

                        $dupQ = $krfconn->prepare("SELECT sno, invoiceno FROM advance_payment_transactions WHERE utrnumber = ? AND utrnumber != '' LIMIT 1");
                        if ($dupQ) {
                            $dupQ->bind_param('s', $spUtr);
                            $dupQ->execute();
                            $dupRes = $dupQ->get_result();
                            if ($rowDup = $dupRes->fetch_assoc()) {
                                throw new Exception("Transaction No. '{$spUtr}' has already been recorded in previous records (Invoice: {$rowDup['invoiceno']}). Duplicate transaction numbers are not allowed.");
                            }
                            $dupQ->close();
                        }
                    }
                }

                if (count($validSplits) > 1) {
                    // Multi-payment mode splits: record each active mode line
                    $totalSplitSum = array_sum(array_column($validSplits, 'amount'));
                    $isFirst = true;
                    foreach ($validSplits as $spIdx => $sp) {
                        $spMode = trim($sp['mode'] ?: 'Cash');
                        $spUtr = trim($sp['utr'] ?: '');
                        $spId = trim($sp['id'] ?? '');
                        $spAmt = ($totalSplitSum > 0) ? round(($sp['amount'] / $totalSplitSum) * $finalPaid, 2) : 0;
                        if ($spAmt <= 0 && $finalPaid > 0) continue;

                        $spRound = $isFirst ? $roundOff : 0.0;
                        $spDisc = $isFirst ? $specialDiscount : 0.0;
                        $spNote = "Payment received for Open Sale PSO {$psono} via {$spMode}" . (!empty($spUtr) ? " (Ref: {$spUtr})" : "") . (!empty($payRemarks) ? " | Note: {$payRemarks}" : "");

                        // Upload document for this specific mode/split line
                        $spReceipt = '';
                        if (!empty($spId)) {
                            $spReceipt = handlePaymentReceiptUpload('payment_document_' . $spId, $company, $subcompany, $project);
                        }
                        if (empty($spReceipt)) {
                            $spReceipt = handlePaymentReceiptUpload('payment_document_' . $spIdx, $company, $subcompany, $project);
                        }
                        if (empty($spReceipt)) {
                            $spReceipt = handlePaymentReceiptUpload('payment_document_' . $spMode, $company, $subcompany, $project);
                        }
                        if (empty($spReceipt) && isset($_FILES['payment_document_' . strtolower($spMode)])) {
                            $spReceipt = handlePaymentReceiptUpload('payment_document_' . strtolower($spMode), $company, $subcompany, $project);
                        }
                        if (!empty($spReceipt) && empty($primaryReceiptPath)) {
                            $primaryReceiptPath = $spReceipt;
                        }

                        $payStmt->bind_param(
                            'iiissdddsssssssss',
                            $company, $subcompany, $project, $client_id, $client['client_name'],
                            $spAmt, $spRound, $spDisc, $spUtr, $curDate,
                            $psono, $spMode, $login,
                            $payLoc, $spReceipt, $spNote, $login
                        );
                        if (!$payStmt->execute()) {
                            throw new Exception("Failed to record payment in advance_payment_transactions: " . $payStmt->error);
                        }
                        $isFirst = false;
                    }
                } else {
                    $firstMode = !empty($validSplits) ? $validSplits[0]['mode'] : $paymentMode;
                    $firstUtr = !empty($validSplits) ? $validSplits[0]['utr'] : $utrNumber;
                    $firstId = !empty($validSplits) ? ($validSplits[0]['id'] ?? '') : '';

                    if (!empty($firstUtr) && empty($validSplits)) {
                        $dupQ = $krfconn->prepare("SELECT sno, invoiceno FROM advance_payment_transactions WHERE utrnumber = ? AND utrnumber != '' LIMIT 1");
                        if ($dupQ) {
                            $dupQ->bind_param('s', $firstUtr);
                            $dupQ->execute();
                            $dupRes = $dupQ->get_result();
                            if ($rowDup = $dupRes->fetch_assoc()) {
                                throw new Exception("Transaction No. '{$firstUtr}' has already been recorded in previous records (Invoice: {$rowDup['invoiceno']}). Duplicate transaction numbers are not allowed.");
                            }
                            $dupQ->close();
                        }
                    }

                    $payNote = "Payment received for Open Sale PSO {$psono} via {$firstMode}" . (!empty($firstUtr) ? " (Ref: {$firstUtr})" : "") . (!empty($payRemarks) ? " | Note: {$payRemarks}" : "");

                    // Single mode document attachment
                    $receiptPath = '';
                    if (!empty($firstId)) {
                        $receiptPath = handlePaymentReceiptUpload('payment_document_' . $firstId, $company, $subcompany, $project);
                    }
                    if (empty($receiptPath)) {
                        $receiptPath = handlePaymentReceiptUpload('payment_document_0', $company, $subcompany, $project);
                    }
                    if (empty($receiptPath)) {
                        $receiptPath = handlePaymentReceiptUpload('single_payment_document', $company, $subcompany, $project);
                    }
                    if (empty($receiptPath)) {
                        $receiptPath = handlePaymentReceiptUpload('payment_document_' . $firstMode, $company, $subcompany, $project);
                    }
                    if (empty($receiptPath)) {
                        $receiptPath = handlePaymentReceiptUpload('payment_document', $company, $subcompany, $project);
                    }
                    $primaryReceiptPath = $receiptPath;

                    $payStmt->bind_param(
                        'iiissdddsssssssss',
                        $company, $subcompany, $project, $client_id, $client['client_name'],
                        $finalPaid, $roundOff, $specialDiscount, $firstUtr, $curDate,
                        $psono, $firstMode, $login,
                        $payLoc, $receiptPath, $payNote, $login
                    );
                    if (!$payStmt->execute()) {
                        throw new Exception("Failed to record payment in advance_payment_transactions: " . $payStmt->error);
                    }
                }
                $payStmt->close();
            }

            // Provisional order is now confirmed
            $updPsoH = $krfconn->prepare("UPDATE krf_provisional_sales_order SET status = 'Confirmed', updated_at = NOW() WHERE psono = ?");
            if (!$updPsoH) {
                throw new Exception("Failed to prepare update for krf_provisional_sales_order: " . $krfconn->error);
            }
            $updPsoH->bind_param('s', $psono);
            if (!$updPsoH->execute()) {
                throw new Exception("Failed to confirm provisional order in krf_provisional_sales_order: " . $updPsoH->error);
            }
            $updPsoH->close();

            $updPsoI = $krfconn->prepare("UPDATE krf_provisional_sales_order_items SET status = 'Confirmed' WHERE psono = ?");
            if (!$updPsoI) {
                throw new Exception("Failed to prepare update for krf_provisional_sales_order_items: " . $krfconn->error);
            }
            $updPsoI->bind_param('s', $psono);
            if (!$updPsoI->execute()) {
                throw new Exception("Failed to confirm provisional order items in krf_provisional_sales_order_items: " . $updPsoI->error);
            }
            $updPsoI->close();

            // -------------------------------------------------------------
            // STEP 3: Save details into krf_sales_order and krf_sales_order_items
            // -------------------------------------------------------------
            $soH = $krfconn->prepare("
                INSERT INTO krf_sales_order (
                    sono, psono, companyid, subcompanyid, projectid, clientid, clientname,
                    emailid, address, delivery_address, districtname, statename, mobileno,
                    payment_terms, remarks, total_amount, tax_amount, roundoffamount,
                    otherdiscount, grand_total, status, created_by, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, 'Confirmed', ?, NOW()
                )
            ");
            if (!$soH) {
                throw new Exception("Failed to prepare krf_sales_order: " . $krfconn->error);
            }
            $paymentTerms = "Immediate ({$paymentMode})";
            $soH->bind_param(
                'ssiiissssssssssddddds',
                $sono, $psono, $company, $subcompany, $project, $client_id, $client['client_name'],
                $client['client_email'], $client['invoicing_address'], $client['invoicing_address'], $client['districtname'], $client['statename'], $client['phones'],
                $paymentTerms, $fullRemarks, $totalTaxable, $totalTax, $roundOff,
                $totalDiscount, $finalInvoiceAmount, $login
            );
            if (!$soH->execute()) {
                throw new Exception("Failed to insert Confirmed Sales Order in krf_sales_order: " . $soH->error);
            }
            $soH->close();

            $soI = $krfconn->prepare("
                INSERT INTO krf_sales_order_items (
                    sono, psono, projectid, clientid, stockorder_no, product_sku, product_id, product_name,
                    quantity, rate, taxableamount, cgst_percent, cgst_amount, sgst_percent, sgst_amount,
                    igst_percent, igst_amount, discount_percent, discount_amount, tax_amount,
                    amount, roundoffamount, deliverydate, created_by, status
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, 'Confirmed'
                )
            ");
            if (!$soI) {
                throw new Exception("Failed to prepare krf_sales_order_items: " . $krfconn->error);
            }

            foreach ($processedItems as $it) {
                $soI->bind_param(
                    'ssisssssddddddddddddddss',
                    $sono, $psono, $project, $client_id, $it['stockorder_no'], $it['product_sku'], $it['productid'], $it['productname'],
                    $it['billable_qty'], $it['rate'], $it['taxableamount'], $it['cgst_percent'], $it['cgst_amount'],
                    $it['sgst_percent'], $it['sgst_amount'], $it['igst_percent'], $it['igst_amount'],
                    $it['discount_percent'], $it['discount_amount'], $it['tax_amount'],
                    $it['amount'], $roundOff, $curDate, $login
                );
                if (!$soI->execute()) {
                    throw new Exception("Failed to insert Sales Order item in krf_sales_order_items: " . $soI->error);
                }
            }
            $soI->close();

            // -------------------------------------------------------------
            // STEP 4: Save consumption in krf_productionorder_consumed
            // -------------------------------------------------------------
            $pocStmt = $krfconn->prepare("
                INSERT INTO krf_productionorder_consumed (
                    psono, sono, stockorderno, original_stockorder_no, original_orderqty,
                    allocated_stockorderno, allocated_psono, product_id, product_name,
                    consumed_qty, wastage_qty, projectid,
                    allocate_healthyplant, allocate_unhealthyplant,
                    dispatchedqty, allocatedby, allocatedat, dispatchedby, dispatchedat,
                    created_by, created_at, confirmstatus, status, remark
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, 0.000, ?,
                    ?, 0,
                    ?, ?, NOW(), ?, NOW(),
                    ?, NOW(), 'Confirmed', 'dispatched', ?
                )
            ");
            if (!$pocStmt) {
                throw new Exception("Failed to prepare krf_productionorder_consumed: " . $krfconn->error);
            }
            foreach ($processedItems as $it) {
                if (!empty($it['is_box_packing'])) continue;
                $pidInt = (int)$it['productid'];
                $consumedQty = (float)$it['quantity'];
                $pocRemark = "Direct Open Sale Dispatch under Inv: {$invoiceno} | GP: {$gatepassNo} to Client: {$client['client_name']} ({$client_id})";
                $pocStmt->bind_param(
                    'ssssissisdiisssss',
                    $psono, $sono, $it['stockorder_no'], $it['stockorder_no'], $it['quantity'],
                    $it['stockorder_no'], $psono, $pidInt, $it['productname'],
                    $consumedQty, $project,
                    $it['quantity'], $it['quantity'],
                    $login, $login, $login, $pocRemark
                );
                if (!$pocStmt->execute()) {
                    throw new Exception("Failed to save consumption in krf_productionorder_consumed: " . $pocStmt->error);
                }
            }
            $pocStmt->close();

            // -------------------------------------------------------------
            // STEP 5: Automatic stock allocation in krf_readystock_allocation
            // -------------------------------------------------------------
            $rsaStmt = $krfconn->prepare("
                INSERT INTO krf_readystock_allocation (
                    projectid, stockorder_no, allocate_stockorder_no, clientid,
                    productid, productname, allocate_ordqty, healthyplant,
                    dispatchqty, unhealthyplant, invoice_number, invoice_date, created_at, created_by, status
                ) VALUES (
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, 0, ?, ?, NOW(), ?, 'dispatched'
                )
                ON DUPLICATE KEY UPDATE
                    clientid = VALUES(clientid),
                    productname = VALUES(productname),
                    allocate_ordqty = allocate_ordqty + VALUES(allocate_ordqty),
                    healthyplant = healthyplant + VALUES(healthyplant),
                    dispatchqty = dispatchqty + VALUES(dispatchqty),
                    invoice_number = VALUES(invoice_number),
                    invoice_date = VALUES(invoice_date),
                    updated_at = NOW(),
                    updated_by = VALUES(created_by),
                    status = 'dispatched'
            ");
            if (!$rsaStmt) {
                throw new Exception("Failed to prepare krf_readystock_allocation: " . $krfconn->error);
            }
            foreach ($processedItems as $it) {
                if (!empty($it['is_box_packing'])) continue;
                $rsaStmt->bind_param(
                    'isssssiiisss',
                    $project, $it['stockorder_no'], $it['stockorder_no'], $client_id,
                    $it['productid'], $it['productname'], $it['quantity'], $it['quantity'],
                    $it['quantity'], $invoiceno, $curDate, $login
                );
                if (!$rsaStmt->execute()) {
                    throw new Exception("Failed to save stock allocation in krf_readystock_allocation: " . $rsaStmt->error);
                }
            }
            $rsaStmt->close();

            // -------------------------------------------------------------
            // STEP 6: Save final commercial invoice in sales_invoice & items
            // -------------------------------------------------------------
            $siStmt = $krfconn->prepare("
                INSERT INTO sales_invoice (
                    companyid, subcompanyid, projectid, psono, sono, invoicetype, invoiceno, serialno,
                    invoicedate, clientid, clientname, client_type, mobileno, emailid, invoicing_address,
                    delivery_address, districtname, statename, taxableamount, tax_amount,
                    otherdiscount, roundoffamount, invoiceamount, payableamount, paidamount,
                    balanceamount, pickuptype, vehicleno, drivername, drivermobileno,
                    scheduled_dispatch_date, remarks, status, created_by, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, 'dispatchinvoice', ?, ?,
                    ?, ?, ?, 'Farmer', ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, 'dispatched', ?, NOW(), NOW()
                )
            ");
            if (!$siStmt) {
                throw new Exception("Failed to prepare sales_invoice: " . $krfconn->error);
            }

            $totalCombinedDiscount = round($totalDiscount + $specialDiscount, 2);
            $siStmt->bind_param(
                'iiisssisssssssssddddddddsssssss',
                $company, $subcompany, $project, $psono, $sono, $invoiceno, $invoiceSerialNo,
                $curDate, $client_id, $client['client_name'], $client['phones'], $client['client_email'], $client['invoicing_address'],
                $client['invoicing_address'], $client['districtname'], $client['statename'], $totalTaxable, $totalTax,
                $totalCombinedDiscount, $roundOff, $finalInvoiceAmount, $netPayableAmount, $finalPaid,
                $finalBalance, $pickupfrom, $vehicleno, $drivername, $driverphone,
                $curDate, $fullRemarks, $login
            );
            if (!$siStmt->execute()) {
                throw new Exception("Failed to generate commercial Sales Invoice in sales_invoice: " . $siStmt->error);
            }
            $invoiceId = $krfconn->insert_id;
            $siStmt->close();

            $siiStmt = $krfconn->prepare("
                INSERT INTO sales_invoice_items (
                    invoice_id, projectid, clientid, psono, sono, invoiceno,
                    stockorder_no, productid, product_sku, productname, quantity, healthyqty, total_dispatch_plants,
                    rate, discount_percent, discount_amount, taxableamount, tax_amount,
                    amount, deliverydate, status, remarks, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, 'dispatched', ?, NOW(), NOW()
                )
            ");
            if (!$siiStmt) {
                throw new Exception("Failed to prepare sales_invoice_items: " . $krfconn->error);
            }

            foreach ($processedItems as $it) {
                $itemRem = "Open Sale Item ({$it['variety']})" . ($it['foc'] > 0 ? " | FOC: {$it['foc']} plant(s) (Dispatched: {$it['quantity']}, Billed: {$it['billable_qty']})" : "");
                $siiStmt->bind_param(
                    'iissssssssiiiddddddss',
                    $invoiceId, $project, $client_id, $psono, $sono, $invoiceno,
                    $it['stockorder_no'], $it['productid'], $it['product_sku'], $it['productname'],
                    $it['billable_qty'], $it['quantity'], $it['quantity'],
                    $it['rate'], $it['discount_percent'], $it['discount_amount'], $it['taxableamount'], $it['tax_amount'],
                    $it['amount'], $curDate, $itemRem
                );
                if (!$siiStmt->execute()) {
                    throw new Exception("Failed to insert item into sales_invoice_items: " . $siiStmt->error);
                }
            }
            $siiStmt->close();

            // -------------------------------------------------------------
            // STEP 7: Save Dispatch Invoice Gate Pass in krf_gatepass_tracking & items
            // -------------------------------------------------------------
            $vehNumberValid = !empty($vehicleno) ? $vehicleno : 'Direct Client Pickup';
            $driverNameValid = !empty($drivername) ? $drivername : 'Self';
            $driverPhoneValid = !empty($driverphone) ? $driverphone : '-';
            $mainCavity = '126 Cavity';
            $calcTotalTrays = (int)ceil($totalQty / 126);

            $gpStmt = $krfconn->prepare("
                INSERT INTO krf_gatepass_tracking (
                    gatepass_no, projectid, psono, sono, invoiceno, invoice_date,
                    clientid, clientname, clientphone, delivery_address, districtname,
                    statename, vehicleno, transporter_name, drivername, drivermobile,
                    total_trays, total_plants, tray_cavity, remarks, status,
                    gate_status, created_by, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, 'Direct Delivery', ?, ?,
                    ?, ?, ?, ?, 'generated',
                    'pending', ?, NOW()
                )
            ");
            if (!$gpStmt) {
                throw new Exception("Failed to prepare Dispatch Gate Pass in krf_gatepass_tracking: " . $krfconn->error);
            }
            $gpStmt->bind_param(
                'sisssssssssssssiisss',
                $gatepassNo, $project, $psono, $sono, $invoiceno, $curDate,
                $client_id, $client['client_name'], $client['phones'], $client['invoicing_address'], $client['districtname'],
                $client['statename'], $vehNumberValid, $driverNameValid, $driverPhoneValid,
                $calcTotalTrays, $totalQty, $mainCavity, $fullRemarks, $login
            );
            if (!$gpStmt->execute()) {
                throw new Exception("Failed to insert Dispatch Gate Pass in krf_gatepass_tracking: " . $gpStmt->error);
            }
            $gatepassId = $krfconn->insert_id;
            $gpStmt->close();

            $gpiStmt = $krfconn->prepare("
                INSERT INTO krf_gatepass_items (
                    gatepass_id, gatepass_no, psono, product_id, product_name,
                    stockorderno, tray_cavity, trays_count, plant_qty, status, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, 'dispatched', NOW()
                )
            ");
            if (!$gpiStmt) {
                throw new Exception("Failed to prepare Gate Pass items in krf_gatepass_items: " . $krfconn->error);
            }
            foreach ($processedItems as $it) {
                if (!empty($it['is_box_packing'])) continue;
                $prodIdInt = (int)$it['productid'];
                $itemTrays = (int)ceil($it['quantity'] / 126);
                $gpiStmt->bind_param(
                    'ississsii',
                    $gatepassId, $gatepassNo, $psono, $prodIdInt, $it['productname'],
                    $it['stockorder_no'], $mainCavity, $itemTrays, $it['quantity']
                );
                if (!$gpiStmt->execute()) {
                    throw new Exception("Failed to insert Gate Pass item in krf_gatepass_items: " . $gpiStmt->error);
                }
            }
            $gpiStmt->close();

            // -------------------------------------------------------------
            // STEP 8: Update krf_opensale_stocks and krf_polyhouse_plant_details
            // -------------------------------------------------------------
            $updStk = $krfconn->prepare("
                UPDATE krf_opensale_stocks 
                SET actual_plant_count = GREATEST(0, actual_plant_count - ?),
                    sold_qty = sold_qty + ?,
                    status = IF(actual_plant_count - ? <= 0, 'Sold', 'OpenSale'),
                    invoiceno = ?,
                    clientid = ?,
                    sold_at = NOW(),
                    remarks = CONCAT(COALESCE(remarks,''), '\n[Sold ', ?, ' plant(s) to ', ?, ' under Inv: ', ?, ' | PSO: ', ?, ' | GP: ', ?, ']')
                WHERE id = ?
            ");
            if (!$updStk) {
                throw new Exception("Failed to prepare stock update for krf_opensale_stocks: " . $krfconn->error);
            }

            $updPlant = $krfconn->prepare("
                UPDATE krf_polyhouse_plant_details
                SET status = 'Final Dispatch',
                    workingstatus = 'Final Dispatch',
                    updated_at = NOW(),
                    updated_by = ?,
                    movementremark = CONCAT(COALESCE(movementremark,''), '\n[Open Sale Sold under Inv: ', ?, ' | GP: ', ?, ' to Client: ', ?, ' | PSO: ', ?, ']')
                WHERE barcode = ?
            ");
            if (!$updPlant) {
                throw new Exception("Failed to prepare polyhouse plant update for krf_polyhouse_plant_details: " . $krfconn->error);
            }

            foreach ($stockTraysToUpdate as $stk) {
                $deduct = $stk['deduct_qty'];
                $stkId = $stk['id'];
                $bCode = $stk['barcode'];

                $updStk->bind_param(
                    'iiisssssssi',
                    $deduct, $deduct, $deduct,
                    $invoiceno, $client_id,
                    $deduct, $client['client_name'], $invoiceno, $psono, $gatepassNo,
                    $stkId
                );
                if (!$updStk->execute()) {
                    throw new Exception("Failed to deduct stock for Open Sale Tray ID {$stkId}: " . $updStk->error);
                }

                if (!empty($bCode)) {
                    $updPlant->bind_param('ssssss', $login, $invoiceno, $gatepassNo, $client['client_name'], $psono, $bCode);
                    if (!$updPlant->execute()) {
                        throw new Exception("Failed to update polyhouse plant status for Barcode {$bCode}: " . $updPlant->error);
                    }
                }
            }
            $updStk->close();
            $updPlant->close();

            // -------------------------------------------------------------
            // STEP 8.5: Insert payment details against checked pending order PSONOs
            // -------------------------------------------------------------
            $insertedPendingPayments = [];
            if (!empty($pendingPayments)) {
                $payPendingStmt = $krfconn->prepare("
                    INSERT INTO advance_payment_transactions (
                        companyid, subcompanyid, projectid, clientid, clientname,
                        advanceamount, roundoffamount, otherdiscount, utrnumber, paymentdate,
                        purposeofpayment, invoiceno, sourceofpayment, receivedby,
                        receivedlocation, receiptpath, remarks, transaction_type, verification,
                        allocated_datetime, allocatedby
                    ) VALUES (
                        ?, ?, ?, ?, ?,
                        ?, 0, 0, ?, ?,
                        'Open Sale Multi-Order Payment', ?, ?, ?,
                        ?, ?, ?, 'Receipt', 'verified',
                        NOW(), ?
                    )
                ");

                if (!$payPendingStmt) {
                    throw new Exception("Failed to prepare advance_payment_transactions for pending orders: " . $krfconn->error);
                }

                $payLoc = "{$open_sale_loc} Farm";

                // Resolve payment mode summary and UTR for pending orders
                $validSplits = array_values(array_filter($paymentSplits, function($s) {
                    return (float)($s['amount'] ?? 0) > 0;
                }));
                $pendingPayMode = !empty($validSplits) ? implode(', ', array_unique(array_column($validSplits, 'mode'))) : $paymentMode;
                $pendingUtrList = [];
                foreach ($validSplits as $vSp) {
                    if (!empty($vSp['utr'])) {
                        $pendingUtrList[] = $vSp['mode'] . ': ' . $vSp['utr'];
                    }
                }
                $pendingUtr = !empty($pendingUtrList) ? implode('; ', $pendingUtrList) : $utrNumber;
                $pendingReceipt = $primaryReceiptPath ?? '';

                foreach ($pendingPayments as $pItem) {
                    $targetPsono = trim((string)($pItem['psono'] ?? ''));
                    $targetPayAmt = (float)($pItem['pay_amount'] ?? 0);

                    if (empty($targetPsono) || $targetPayAmt <= 0) {
                        continue;
                    }

                    $pNote = "Payment against Order {$targetPsono} collected via Open Sale counter (Inv: {$invoiceno}, PSO: {$psono}) via [{$pendingPayMode}]" . (!empty($payRemarks) ? " | Note: {$payRemarks}" : "");

                    $payPendingStmt->bind_param(
                        'iiissdsssssssss',
                        $company, $subcompany, $project, $client_id, $client['client_name'],
                        $targetPayAmt, $pendingUtr, $curDate,
                        $targetPsono, $pendingPayMode, $login,
                        $payLoc, $pendingReceipt, $pNote, $login
                    );

                    if (!$payPendingStmt->execute()) {
                        throw new Exception("Failed to record payment for pending order {$targetPsono}: " . $payPendingStmt->error);
                    }

                    // Also update sales_invoice if it exists for this target psono
                    $updSi = $krfconn->prepare("
                        UPDATE sales_invoice 
                        SET balanceamount = GREATEST(0, balanceamount - ?),
                            paidamount = paidamount + ?,
                            updated_at = NOW()
                        WHERE psono = ?
                    ");
                    if ($updSi) {
                        $updSi->bind_param('dds', $targetPayAmt, $targetPayAmt, $targetPsono);
                        $updSi->execute();
                        $updSi->close();
                    }

                    $insertedPendingPayments[] = [
                        'psono'      => $targetPsono,
                        'pay_amount' => $targetPayAmt
                    ];
                }
                $payPendingStmt->close();
            }

            // -------------------------------------------------------------
            // STEP 9: Commit transaction atomically
            // -------------------------------------------------------------
            $krfconn->commit();

            os_out(true, "Open Sale Order, Commercial Invoice & Dispatch Gate Pass generated successfully!", [
                'invoice_id'          => $invoiceId,
                'invoiceno'           => $invoiceno,
                'gatepass_no'         => $gatepassNo,
                'psono'               => $psono,
                'sono'                => $sono,
                'client_name'         => $client['client_name'],
                'total_plants'        => $totalQty,
                'invoice_amount'      => $finalInvoiceAmount,
                'special_discount'    => $specialDiscount,
                'payable_amount'      => $netPayableAmount,
                'paid_amount'         => $finalPaid,
                'balance_amount'      => $finalBalance,
                'pending_orders_paid' => $insertedPendingPayments,
                'print_url'           => "../krf-orderandsales/modal/genrateInvoicePDF.php?invno=" . urlencode($invoiceno) . "&companyid=" . intval($company) . "&subcompanyid=" . intval($subcompany) . "&projectid=" . intval($project) . "&mode=I",
                'gatepass_url'        => "../krf-orderandsales/modal/gatepass_modal.php?action=view_pdf&gatepass_no=" . urlencode($gatepassNo) . "&psono=" . urlencode($psono)
            ]);

        } catch (Throwable $e) {
            // Roll back all changes if any error occurred anywhere
            if (isset($krfconn) && $krfconn instanceof mysqli) {
                try { $krfconn->rollback(); } catch (Throwable $ign) {}
            }
            os_out(false, "Order booking failed and all database changes were rolled back. Reason: " . $e->getMessage(), [
                'error_detail' => $e->getMessage(),
                'file'         => basename($e->getFile()),
                'line'         => $e->getLine()
            ]);
        }
    }

    // -------------------------------------------------------------
    // 7. LIST BOOKED OPEN SALE INVOICES
    // -------------------------------------------------------------
    if ($action === 'list_invoices') {
        $filterLoc = trim((string)($_POST['location'] ?? $_GET['location'] ?? ''));
        $search = trim((string)($_POST['search'] ?? $_GET['search'] ?? ''));

        $where = [
            "(projectid = '{$project}' OR projectid = '0' OR projectid = '17')",
            "(invoiceno LIKE 'KF-OS-%' OR invoicetype = 'opensale_invoice' OR psono LIKE 'OS-ORD-%')"
        ];

        if (!empty($search)) {
            $sEsc = $krfconn->real_escape_string($search);
            $where[] = "(invoiceno LIKE '%{$sEsc}%' OR clientname LIKE '%{$sEsc}%' OR mobileno LIKE '%{$sEsc}%' OR psono LIKE '%{$sEsc}%')";
        }

        $whereSql = implode(' AND ', $where);

        $sql = "SELECT id, invoiceno, psono, invoicedate, clientid, clientname, mobileno,
                       taxableamount, tax_amount, invoiceamount, paidamount, balanceamount,
                       pickuptype, vehicleno, remarks, status, created_by, created_at
                FROM sales_invoice
                WHERE {$whereSql}
                ORDER BY id DESC
                LIMIT 100";

        $res = $krfconn->query($sql);
        $invoices = [];

        if ($res) {
            $today = date('Y-m-d');
            while ($r = $res->fetch_assoc()) {
                $r['invoicedate_fmt'] = !empty($r['invoicedate']) ? date('d-M-Y', strtotime($r['invoicedate'])) : '-';
                $r['created_at_fmt'] = !empty($r['created_at']) ? date('d-M-Y h:i A', strtotime($r['created_at'])) : '-';
                $r['invoiceamount'] = (float)$r['invoiceamount'];
                $r['paidamount'] = (float)$r['paidamount'];
                $r['balanceamount'] = (float)$r['balanceamount'];
                $r['is_today'] = ($r['invoicedate'] === $today);
                $invoices[] = $r;
            }
        }

        os_out(true, '', ['invoices' => $invoices]);
    }

    // -------------------------------------------------------------
    // 8. COLLECT BALANCE PAYMENT
    // -------------------------------------------------------------
    if ($action === 'collect_payment') {
        $invNo    = trim((string)($_POST['invoiceno'] ?? ''));
        $payAmt   = (float)($_POST['amount'] ?? 0);
        $payMode  = trim((string)($_POST['payment_mode'] ?? 'Cash'));
        $utr      = trim((string)($_POST['utr_number'] ?? ''));
        $note     = trim((string)($_POST['remarks'] ?? ''));
        $login    = trim((string)($_POST['loginid'] ?? 'Admin'));
        $company  = (int)($_POST['companyid'] ?? 1);
        $sub      = (int)($_POST['subcompanyid'] ?? 1);

        if (empty($invNo) || $payAmt <= 0) {
            os_out(false, 'Valid Invoice Number and Payment Amount are required.');
        }

        $chk = $krfconn->prepare("SELECT id, clientid, clientname, invoiceamount, paidamount, balanceamount FROM sales_invoice WHERE invoiceno = ? LIMIT 1");
        $chk->bind_param('s', $invNo);
        $chk->execute();
        $inv = $chk->get_result()->fetch_assoc();
        $chk->close();

        if (!$inv) {
            os_out(false, 'Invoice not found.');
        }

        $curBal = (float)$inv['balanceamount'];
        if ($payAmt > $curBal) {
            os_out(false, "Payment amount (₹{$payAmt}) cannot exceed current balance (₹{$curBal}).");
        }

        $krfconn->begin_transaction();

        $curDate = date('Y-m-d');
        $payStmt = $krfconn->prepare("
            INSERT INTO advance_payment_transactions (
                companyid, subcompanyid, projectid, clientid, clientname,
                advanceamount, roundoffamount, otherdiscount, utrnumber, paymentdate,
                purposeofpayment, invoiceno, sourceofpayment, receivedby,
                receivedlocation, remarks, transaction_type, verification,
                allocated_datetime, allocatedby
            ) VALUES (
                ?, ?, ?, ?, ?,
                ?, 0, 0, ?, ?,
                'Open Sale Balance Payment', ?, ?, ?,
                'Open Sale Counter', ?, 'Receipt', 'verified',
                NOW(), ?
            )
        ");

        $fullNote = "Balance payment against {$invNo}" . (!empty($note) ? " | {$note}" : "");
        $payStmt->bind_param(
            'iiissdssssss',
            $company, $sub, $project, $inv['clientid'], $inv['clientname'],
            $payAmt, $utr, $curDate,
            $invNo, $payMode, $login,
            $fullNote, $login
        );
        $payStmt->execute();
        $payStmt->close();

        // Update sales_invoice
        $newPaid = round((float)$inv['paidamount'] + $payAmt, 2);
        $newBal  = round(max(0.0, (float)$inv['invoiceamount'] - $newPaid), 2);

        $upd = $krfconn->prepare("UPDATE sales_invoice SET paidamount = ?, balanceamount = ?, updated_at = NOW() WHERE invoiceno = ?");
        $upd->bind_param('dds', $newPaid, $newBal, $invNo);
        $upd->execute();
        $upd->close();

        $krfconn->commit();

        os_out(true, "Payment of ₹" . number_format($payAmt, 2) . " recorded successfully!", [
            'invoiceno'      => $invNo,
            'paid_amount'    => $newPaid,
            'balance_amount' => $newBal
        ]);
    }

    // -------------------------------------------------------------
    // 9. GET INVOICE DETAILS FOR EDIT / CANCEL
    // -------------------------------------------------------------
    if ($action === 'get_invoice_details') {
        $invNo = trim((string)($_POST['invoiceno'] ?? $_GET['invoiceno'] ?? ''));
        if (empty($invNo)) os_out(false, 'Invoice number is required.');

        $st = $krfconn->prepare("SELECT id, invoiceno, psono, sono, invoicedate, clientid, clientname, mobileno,
                                        invoicing_address, taxableamount, tax_amount, otherdiscount, invoiceamount,
                                        payableamount, paidamount, balanceamount, remarks, status
                                 FROM sales_invoice WHERE invoiceno = ? LIMIT 1");
        $st->bind_param('s', $invNo);
        $st->execute();
        $inv = $st->get_result()->fetch_assoc();
        $st->close();

        if (!$inv) os_out(false, 'Invoice not found.');

        // Line items
        $iSt = $krfconn->prepare("SELECT id, invoiceno, psono, stockorder_no, productid, productname,
                                         quantity, rate, discount_percent, discount_amount,
                                         taxableamount, tax_amount, amount
                                  FROM sales_invoice_items WHERE invoiceno = ?");
        $iSt->bind_param('s', $invNo);
        $iSt->execute();
        $items = $iSt->get_result()->fetch_all(MYSQLI_ASSOC);
        $iSt->close();

        $today = date('Y-m-d');
        $inv['is_today'] = ($inv['invoicedate'] === $today);
        $inv['items'] = $items;

        os_out(true, '', ['invoice' => $inv]);
    }

    // -------------------------------------------------------------
    // 10. EDIT OPEN SALE INVOICE (SAME-DAY ONLY)
    // -------------------------------------------------------------
    if ($action === 'edit_opensale_invoice') {
        $invNo     = trim((string)($_POST['invoiceno'] ?? ''));
        $login     = trim((string)($_POST['loginid'] ?? 'Admin'));
        $editItems = json_decode((string)($_POST['items'] ?? '[]'), true);
        $editReason= trim((string)($_POST['reason'] ?? 'Same day invoice qty revision'));

        if (empty($invNo) || !is_array($editItems) || empty($editItems)) {
            os_out(false, 'Valid invoice number and revised items are required.');
        }

        // Fetch invoice
        $chk = $krfconn->prepare("SELECT id, invoiceno, psono, sono, invoicedate, clientid, clientname,
                                         invoiceamount, paidamount, balanceamount, status
                                  FROM sales_invoice WHERE invoiceno = ? LIMIT 1");
        $chk->bind_param('s', $invNo);
        $chk->execute();
        $inv = $chk->get_result()->fetch_assoc();
        $chk->close();

        if (!$inv) os_out(false, 'Invoice not found.');
        if ($inv['status'] === 'cancelled') os_out(false, 'Cancelled invoices cannot be edited.');

        // Enforce same-day rule
        $today = date('Y-m-d');
        if ($inv['invoicedate'] !== $today) {
            os_out(false, 'Invoices can only be edited on the same day they were issued (' . date('d-M-Y', strtotime($inv['invoicedate'])) . '). For past dates, please cancel the invoice.');
        }

        $krfconn->begin_transaction();

        // Map existing items
        $iRes = $krfconn->query("SELECT id, stockorder_no, productid, productname, quantity, rate, discount_percent, discount_amount, taxableamount, tax_amount, amount FROM sales_invoice_items WHERE invoiceno = '{$krfconn->real_escape_string($invNo)}'");
        $oldItemsMap = [];
        while ($row = $iRes->fetch_assoc()) {
            $oldItemsMap[(int)$row['id']] = $row;
        }

        $newTotalQty = 0;
        $newTaxable = 0.0;
        $newTax = 0.0;
        $newTotalAmount = 0.0;

        foreach ($editItems as $eIt) {
            $itemId = (int)($eIt['id'] ?? 0);
            $newQty = max(0, (int)($eIt['quantity'] ?? 0));
            $newRate = max(0.0, (float)($eIt['rate'] ?? 0));

            if (!isset($oldItemsMap[$itemId])) continue;
            $oldItem = $oldItemsMap[$itemId];
            $oldQty  = (int)$oldItem['quantity'];
            $stkNo   = (string)$oldItem['stockorder_no'];
            $pId     = (string)$oldItem['productid'];

            $qtyDiff = $oldQty - $newQty; // positive: returned to open sale tray, negative: additional plants sold

            if ($qtyDiff > 0) {
                // Quantity reduced: RESTORE plants back to open sale tray!
                $updOs = $krfconn->query("
                    UPDATE krf_opensale_stocks 
                    SET actual_plant_count = actual_plant_count + {$qtyDiff},
                        sold_qty = GREATEST(0, sold_qty - {$qtyDiff}),
                        status = 'OpenSale',
                        updated_at = NOW(),
                        remarks = CONCAT(COALESCE(remarks,''), '\n[Restored ', {$qtyDiff}, ' plant(s) from edited Inv: ', '{$krfconn->real_escape_string($invNo)}', ']')
                    WHERE (invoiceno = '{$krfconn->real_escape_string($invNo)}' OR stockorder_no = '{$krfconn->real_escape_string($stkNo)}')
                      AND productid = '{$krfconn->real_escape_string($pId)}'
                    LIMIT 1
                ");

                // Also revert polyhouse plant status to active open sale
                $krfconn->query("
                    UPDATE krf_polyhouse_plant_details 
                    SET status = 'Move for Opensale',
                        workingstatus = 'Move for Opensale',
                        updated_at = NOW(),
                        updated_by = '{$krfconn->real_escape_string($login)}',
                        movementremark = CONCAT(COALESCE(movementremark,''), '\n[Restored to open sale from edited Inv: {$krfconn->real_escape_string($invNo)}]')
                    WHERE (stockorder_no = '{$krfconn->real_escape_string($stkNo)}' OR barcode IN (
                        SELECT barcode FROM krf_opensale_stocks WHERE invoiceno = '{$krfconn->real_escape_string($invNo)}'
                    ))
                    LIMIT 1
                ");
            } elseif ($qtyDiff < 0) {
                // Quantity increased: deduct additional plants
                $moreNeeded = abs($qtyDiff);
                $chkAvail = $krfconn->query("SELECT id, actual_plant_count FROM krf_opensale_stocks WHERE (invoiceno = '{$krfconn->real_escape_string($invNo)}' OR stockorder_no = '{$krfconn->real_escape_string($stkNo)}') AND productid = '{$krfconn->real_escape_string($pId)}' AND status = 'OpenSale' FOR UPDATE");
                $avRow = $chkAvail ? $chkAvail->fetch_assoc() : null;
                if (!$avRow || (int)$avRow['actual_plant_count'] < $moreNeeded) {
                    throw new Exception("Cannot increase quantity. Insufficient open sale plants available in tray.");
                }
                $krfconn->query("UPDATE krf_opensale_stocks SET actual_plant_count = actual_plant_count - {$moreNeeded}, sold_qty = sold_qty + {$moreNeeded}, updated_at = NOW() WHERE id = {$avRow['id']}");
            }

            // Recalculate line totals
            $lineGross = round($newQty * $newRate, 2);
            $lineDisc  = round($lineGross * (((float)$oldItem['discount_percent']) / 100), 2);
            $lineTaxable = round($lineGross - $lineDisc, 2);
            $lineTax   = round($lineTaxable * 0, 2);
            $lineTotal = round($lineTaxable + $lineTax, 2);

            $newTotalQty    += $newQty;
            $newTaxable     += $lineTaxable;
            $newTax         += $lineTax;
            $newTotalAmount += $lineTotal;

            // Update sales_invoice_items
            $krfconn->query("
                UPDATE sales_invoice_items
                SET quantity = {$newQty},
                    rate = {$newRate},
                    taxableamount = {$lineTaxable},
                    amount = {$lineTotal},
                    updated_at = NOW(),
                    teditremark = 'Edited on {$today} by {$krfconn->real_escape_string($login)}: {$krfconn->real_escape_string($editReason)}'
                WHERE id = {$itemId}
            ");
        }

        // Adjust sales_invoice
        $oldPaid = (float)$inv['paidamount'];
        $newBal  = round(max(0.0, $newTotalAmount - $oldPaid), 2);
        $newPaid = round(min($newTotalAmount, $oldPaid), 2);

        $krfconn->query("
            UPDATE sales_invoice
            SET taxableamount = {$newTaxable},
                invoiceamount = {$newTotalAmount},
                payableamount = {$newTotalAmount},
                paidamount = {$newPaid},
                balanceamount = {$newBal},
                updated_at = NOW(),
                teditremark = 'Edited on {$today} by {$krfconn->real_escape_string($login)}: {$krfconn->real_escape_string($editReason)}'
            WHERE invoiceno = '{$krfconn->real_escape_string($invNo)}'
        ");

        // Update sales_order & provisional_sales_order
        if (!empty($inv['psono'])) {
            $psEsc = $krfconn->real_escape_string($inv['psono']);
            $krfconn->query("UPDATE krf_provisional_sales_order SET netamount = {$newTotalAmount}, paidamount = {$newPaid}, balanceamount = {$newBal}, updated_at = NOW() WHERE psono = '{$psEsc}'");
            $krfconn->query("UPDATE krf_sales_order SET netamount = {$newTotalAmount}, updated_at = NOW() WHERE psono = '{$psEsc}'");
            $krfconn->query("UPDATE krf_provisional_sales_order_items SET quantity = {$newTotalQty}, amount = {$newTotalAmount} WHERE psono = '{$psEsc}'");
            $krfconn->query("UPDATE krf_sales_order_items SET quantity = {$newTotalQty}, amount = {$newTotalAmount} WHERE psono = '{$psEsc}'");
            $krfconn->query("UPDATE krf_productionorder_consumed SET consumed_qty = {$newTotalQty} WHERE psono = '{$psEsc}'");
            $krfconn->query("UPDATE krf_readystock_allocation SET allocate_ordqty = {$newTotalQty} WHERE stockorder_no IN (SELECT stockorder_no FROM sales_invoice_items WHERE invoiceno = '{$krfconn->real_escape_string($invNo)}')");
            $krfconn->query("UPDATE krf_gatepass_tracking SET total_plants = {$newTotalQty}, total_trays = CEIL({$newTotalQty}/126) WHERE invoiceno = '{$krfconn->real_escape_string($invNo)}'");
            $krfconn->query("UPDATE krf_gatepass_items SET plant_qty = {$newTotalQty}, trays_count = CEIL({$newTotalQty}/126) WHERE psono = '{$psEsc}'");
        }

        $krfconn->commit();

        os_out(true, "Invoice {$invNo} successfully updated. Returned plants are immediately restored to Open Sale stock for resale!", [
            'invoiceno'      => $invNo,
            'invoice_amount' => $newTotalAmount,
            'paid_amount'    => $newPaid,
            'balance_amount' => $newBal
        ]);
    }

    // -------------------------------------------------------------
    // 11. CANCEL OPEN SALE INVOICE & RESTORE STOCK / CREATE CREDIT NOTE OR CASH REFUND
    // -------------------------------------------------------------
    if ($action === 'cancel_opensale_invoice') {
        $invNo        = trim((string)($_POST['invoiceno'] ?? ''));
        $refundOption = trim((string)($_POST['refund_option'] ?? 'credit_note')); // 'credit_note' or 'cash_refund'
        $cancelReason = trim((string)($_POST['cancel_reason'] ?? 'Customer Cancellation'));
        $login        = trim((string)($_POST['loginid'] ?? 'Admin'));
        $company      = (int)($_POST['companyid'] ?? 19);
        $subcompany   = (int)($_POST['subcompanyid'] ?? 12);
        $project      = (int)($_POST['projectid'] ?? 17);

        if (empty($invNo)) os_out(false, 'Invoice number is required.');

        $chk = $krfconn->prepare("SELECT id, invoiceno, psono, sono, invoicedate, clientid, clientname,
                                         invoiceamount, paidamount, balanceamount, status
                                  FROM sales_invoice WHERE invoiceno = ? LIMIT 1");
        $chk->bind_param('s', $invNo);
        $chk->execute();
        $inv = $chk->get_result()->fetch_assoc();
        $chk->close();

        if (!$inv) os_out(false, 'Invoice not found.');
        if ($inv['status'] === 'cancelled') os_out(false, 'This invoice is already cancelled.');

        $krfconn->begin_transaction();

        $psono = (string)($inv['psono'] ?? '');
        $sono  = (string)($inv['sono'] ?? '');
        $clId  = (string)($inv['clientid'] ?? '');
        $clName= (string)($inv['clientname'] ?? '');
        $paidAmount = (float)($inv['paidamount'] ?? 0);

        // 1. Restore all plants in krf_opensale_stocks
        $itemsRes = $krfconn->query("SELECT productid, productname, stockorder_no, quantity FROM sales_invoice_items WHERE invoiceno = '{$krfconn->real_escape_string($invNo)}'");
        while ($it = $itemsRes->fetch_assoc()) {
            $pId = $it['productid'];
            $stkNo = $it['stockorder_no'];
            $qty = (int)$it['quantity'];

            $krfconn->query("
                UPDATE krf_opensale_stocks
                SET actual_plant_count = actual_plant_count + {$qty},
                    sold_qty = GREATEST(0, sold_qty - {$qty}),
                    status = 'OpenSale',
                    invoiceno = NULL,
                    updated_at = NOW(),
                    remarks = CONCAT(COALESCE(remarks,''), '\n[Restored {$qty} plant(s) due to Cancelled Inv: {$krfconn->real_escape_string($invNo)}]')
                WHERE (invoiceno = '{$krfconn->real_escape_string($invNo)}' OR stockorder_no = '{$krfconn->real_escape_string($stkNo)}')
                  AND productid = '{$krfconn->real_escape_string($pId)}'
                LIMIT 1
            ");
        }

        // 2. Revert krf_polyhouse_plant_details
        $krfconn->query("
            UPDATE krf_polyhouse_plant_details
            SET status = 'Move for Opensale',
                workingstatus = 'Move for Opensale',
                updated_at = NOW(),
                updated_by = '{$krfconn->real_escape_string($login)}',
                movementremark = CONCAT(COALESCE(movementremark,''), '\n[Restored to Open Sale - Cancelled Inv: {$krfconn->real_escape_string($invNo)}]')
            WHERE barcode IN (SELECT barcode FROM krf_opensale_stocks WHERE invoiceno = '{$krfconn->real_escape_string($invNo)}')
               OR stockorder_no IN (SELECT stockorder_no FROM sales_invoice_items WHERE invoiceno = '{$krfconn->real_escape_string($invNo)}')
        ");

        // 3. Update sales_invoice to 'cancelled'
        $krfconn->query("
            UPDATE sales_invoice
            SET status = 'cancelled',
                balanceamount = 0.00,
                updated_at = NOW(),
                teditremark = 'Cancelled by {$krfconn->real_escape_string($login)}. Reason: {$krfconn->real_escape_string($cancelReason)}'
            WHERE invoiceno = '{$krfconn->real_escape_string($invNo)}'
        ");
        $krfconn->query("UPDATE sales_invoice_items SET status = 'cancelled' WHERE invoiceno = '{$krfconn->real_escape_string($invNo)}'");

        // 4. Update gate pass to cancelled
        $krfconn->query("UPDATE krf_gatepass_tracking SET status = 'cancelled', gate_status = 'cancelled' WHERE invoiceno = '{$krfconn->real_escape_string($invNo)}'");
        $krfconn->query("UPDATE krf_gatepass_items SET status = 'cancelled' WHERE psono = '{$krfconn->real_escape_string($psono)}'");

        // 5. Update production order consumed & ready stock allocation
        $krfconn->query("UPDATE krf_productionorder_consumed SET status = 'cancelled', consumed_qty = 0 WHERE psono = '{$krfconn->real_escape_string($psono)}'");
        $krfconn->query("UPDATE krf_readystock_allocation SET status = 'cancelled', allocate_ordqty = 0 WHERE clientid = '{$krfconn->real_escape_string($clId)}' AND productid IN (SELECT productid FROM sales_invoice_items WHERE invoiceno = '{$krfconn->real_escape_string($invNo)}')");

        // 6. Update provisional order & sales order to cancelled
        if (!empty($psono)) {
            $krfconn->query("UPDATE krf_provisional_sales_order SET status = 'Cancelled', balanceamount = 0 WHERE psono = '{$krfconn->real_escape_string($psono)}'");
            $krfconn->query("UPDATE krf_sales_order SET status = 'Cancelled' WHERE psono = '{$krfconn->real_escape_string($psono)}'");
        }

        // 7. Finance: Credit Note OR Cash Refund
        $creditNoteNo = '';
        if ($paidAmount > 0) {
            if ($refundOption === 'cash_refund') {
                // Record negative cash refund in advance_payment_transactions
                $curDate = date('Y-m-d');
                $refundAmt = -$paidAmount;
                $refNote = "Cash Refund for Cancelled Invoice {$invNo}. Reason: {$cancelReason}";
                $stRef = $krfconn->prepare("
                    INSERT INTO advance_payment_transactions (
                        companyid, subcompanyid, projectid, clientid, clientname,
                        advanceamount, roundoffamount, otherdiscount, utrnumber, paymentdate,
                        purposeofpayment, invoiceno, sourceofpayment, receivedby,
                        receivedlocation, remarks, transaction_type, verification,
                        allocated_datetime, allocatedby
                    ) VALUES (
                        ?, ?, ?, ?, ?,
                        ?, 0, 0, '', ?,
                        'Open Sale Invoice Cash Refund', ?, 'Cash', ?,
                        'Open Sale Counter', ?, 'Refund', 'verified',
                        NOW(), ?
                    )
                ");
                $stRef->bind_param('iiissdsssss',
                    $company, $subcompany, $project, $clId, $clName,
                    $refundAmt, $curDate,
                    $invNo, $login, $refNote, $login
                );
                $stRef->execute();
                $stRef->close();
            } else {
                // Default: Generate Credit Note in krf_creditnote
                $cnRes = $krfconn->query("SELECT MAX(creditnote_id) AS max_id FROM krf_creditnote");
                $nextId = 1;
                if ($cnRes && $cnRow = $cnRes->fetch_assoc()) {
                    $nextId = intval($cnRow['max_id'] ?? 0) + 1;
                }
                $creditNoteNo = "CN-" . date('Y') . "-" . str_pad((string)$nextId, 5, '0', STR_PAD_LEFT);
                $cnNarration = "Credit Note of Rs. " . number_format($paidAmount, 2) . " against Cancelled Open Sale Invoice {$invNo}. Reason: {$cancelReason}";

                $stmtCn = $krfconn->prepare("
                    INSERT INTO krf_creditnote (
                        creditnote_no, creditnote_date, projectid, companyid, subcompanyid,
                        clientid, clientname, psono, sono, amount_paid, credit_amount,
                        narration, reason, status, created_by, created_at, updated_at
                    ) VALUES (
                        ?, CURDATE(), ?, ?, ?,
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, 'Active', ?, NOW(), NOW()
                    )
                ");
                if ($stmtCn) {
                    $stmtCn->bind_param('siiissssddsss',
                        $creditNoteNo, $project, $company, $subcompany,
                        $clId, $clName, $psono, $sono,
                        $paidAmount, $paidAmount,
                        $cnNarration, $cancelReason, $login
                    );
                    $stmtCn->execute();
                    $stmtCn->close();
                }

                // Insert client statement credit
                $stmtStmt = $krfconn->prepare("
                    INSERT INTO krf_tbl_client_statement (
                        project_id, client_id, txn_date, txn_type, txn_no,
                        description, debit_amount, credit_amount, running_balance, created_at
                    ) VALUES (
                        ?, ?, CURDATE(), 'CREDIT_NOTE', ?,
                        ?, 0.00, ?, 0.00, NOW()
                    )
                ");
                if ($stmtStmt) {
                    $stmtStmt->bind_param('isssd', $project, $clId, $creditNoteNo, $cnNarration, $paidAmount);
                    $stmtStmt->execute();
                    $stmtStmt->close();
                }
            }
        }

        $krfconn->commit();

        $msg = "Invoice {$invNo} has been cancelled successfully. All plants have been restored to Open Sale stock for immediate resale!";
        if ($creditNoteNo) {
            $msg .= " Credit Note {$creditNoteNo} of ₹" . number_format($paidAmount, 2) . " has been credited to client ledger.";
        } elseif ($refundOption === 'cash_refund' && $paidAmount > 0) {
            $msg .= " Cash refund voucher of ₹" . number_format($paidAmount, 2) . " has been recorded.";
        }

        os_out(true, $msg, [
            'invoiceno'       => $invNo,
            'refund_option'   => $refundOption,
            'creditnote_no'   => $creditNoteNo,
            'refunded_amount' => $paidAmount
        ]);
    }

    os_out(false, 'Invalid action.');
} catch (Throwable $e) {
    if (isset($krfconn) && $krfconn instanceof mysqli) {
        try { $krfconn->rollback(); } catch (Throwable $ign) {}
    }
    os_out(false, "System Error: " . $e->getMessage());
}
