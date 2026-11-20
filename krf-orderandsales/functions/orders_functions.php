<?php
function getAdvancePaymentTransactions($conn, $companyid, $subcompanyid, $projectid, $shortby = "all", $shortbyvalue = "all") {
    $data = [];
    $params = [$companyid, $subcompanyid, $projectid];
    $types = "iii";
    $where = " WHERE t.companyid = ? AND t.subcompanyid = ? AND t.projectid = ? AND (t.tedit IS NULL OR t.tedit = '')";

    // Existing shortby logic
    if ($shortby != "all") {
        if($shortby == "unallocatedpayments") {
            $where .= " AND (t.invoiceno IS NULL OR t.invoiceno = '')";
        } else {
            $where .= " AND t.$shortby = ?";
            $params[] = $shortbyvalue;
            $types .= "s";
        }
    }

    // New Advanced Filters from GET/POST
    if (isset($_GET['filter_client']) && !empty($_GET['filter_client'])) {
        $where .= " AND TRIM(t.clientname) LIKE ?";
        $params[] = "%" . trim($_GET['filter_client']) . "%";
        $types .= "s";
    }

    if (isset($_GET['filter_utr']) && !empty($_GET['filter_utr'])) {
        $where .= " AND t.utrnumber LIKE ?";
        $params[] = "%" . trim($_GET['filter_utr']) . "%";
        $types .= "s";
    }

    if (isset($_GET['filter_status']) && !empty($_GET['filter_status'])) {
        if ($_GET['filter_status'] == 'allocated') {
            $where .= " AND (t.invoiceno IS NOT NULL AND t.invoiceno <> '')";
        } else if ($_GET['filter_status'] == 'unallocated') {
            $where .= " AND (t.invoiceno IS NULL OR t.invoiceno = '')";
        }
    }

    if (isset($_GET['filter_date_start']) && !empty($_GET['filter_date_start'])) {
        $where .= " AND t.paymentdate >= ?";
        $params[] = $_GET['filter_date_start'];
        $types .= "s";
    }

    if (isset($_GET['filter_date_end']) && !empty($_GET['filter_date_end'])) {
        $where .= " AND t.paymentdate <= ?";
        $params[] = $_GET['filter_date_end'];
        $types .= "s";
    }

    $sql = "SELECT t.*, p.expecteddelivery
            FROM advance_payment_transactions t
            LEFT JOIN krf_provisional_sales_order p ON t.invoiceno = p.psono
            $where
            GROUP BY t.sno
            ORDER BY t.paymentdate DESC, t.allocated_datetime DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();
    return $data;
}

function GetAccountDetailsFromMasterlist($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue) {
    $baseQuery = "SELECT sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, 
        uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername, 
        branchname, branchaddress, paytoid 
        FROM tbl_master_account 
        WHERE (tedit IS NULL OR tedit = '')";

    $conditions = [];
    $params = [];
    $types = "";

    // Helper to build IN placeholders
    $buildIn = function($column, $values) use (&$params, &$types) {
        $arr = array_filter(array_map('trim', explode(",", $values)));
        if (empty($arr)) return "";
        $placeholders = implode(",", array_fill(0, count($arr), "?"));
        $params = array_merge($params, $arr);
        $types .= str_repeat("s", count($arr));
        return "$column IN ($placeholders)";
    };

    // Common filters
    if (!empty($companyid)) {
        $conditions[] = "cmp_id = ?";
        $params[] = $companyid;
        $types .= "s";
    }
    if (!empty($subcompanyid)) {
        $cond = $buildIn("subcmp_id", $subcompanyid);
        if ($cond) $conditions[] = $cond;
    }
    if (!empty($projectid)) {
        $cond = $buildIn("projectid", $projectid);
        if ($cond) $conditions[] = $cond;
    }

    // Specific filters
    switch ($shortby) {
        case "projectid":
            $conditions[] = "account_type = 'Bank'";
            break;
        case "accountid":
            $cond = $buildIn("accountid", $shortbyvalue);
            if ($cond) $conditions[] = $cond;
            break;
        case "paytoid":
            $cond = $buildIn("paytoid", $shortbyvalue);
            if ($cond) $conditions[] = $cond;
            break;
        case "accountno":
            $cond = $buildIn("accountno", $shortbyvalue);
            if ($cond) $conditions[] = $cond;
            break;
        case "accountnowithloanaccount":
            $conditions[] = "LOWER(account_type) = 'loan'";
            $cond = $buildIn("accountno", $shortbyvalue);
            if ($cond) $conditions[] = $cond;
            break;
    }

    if (!empty($conditions)) {
        $baseQuery .= " AND " . implode(" AND ", $conditions);
    }

    $stmt = $conn->prepare($baseQuery);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error . "\nQuery: " . $baseQuery); // Debugging
    }

    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $accountlist = [];
    while ($row = $result->fetch_assoc()) {
        $accountlist[] = [
            'sno' => $row['sno'],
            'countryid' => $row['countryid'],
            'cmp_id' => $row['cmp_id'],
            'subcmp_id' => $row['subcmp_id'],
            'projectid' => $row['projectid'],
            'accountid' => $row['accountid'],
            'bankid' => $row['bankid'],
            'account_type' => $row['account_type'],
            'uploadsheet_formatid' => $row['uploadsheet_formatid'],
            'accountname' => $row['accountname'],
            'paytoname' => $row['accountname'],
            'accountno' => $row['accountno'],
            'ifsccode' => $row['ifsccode'] ?: "N/A",
            'bankname' => $row['bankname'],
            'accountholdername' => $row['accountholdername'],
            'branchname' => $row['branchname'] ?: "N/A",
            'branchaddress' => $row['branchaddress'] ?: "N/A",
            'paytoid' => $row['paytoid'],
        ];
    }

    $stmt->close();
    return $accountlist;
}


function getNewProvisionalSalesOrderNo($conn, $companyid, $subcompanyid, $projectid) {
    $year = date('Y'); // Current year
    $prefix = "KF-PS-";
    $newNumber = 1;

    try {
        // Fetch last PSO number for this Project and Year
        $sql = "SELECT psono 
                FROM krf_provisional_sales_order 
                WHERE psono LIKE CONCAT(?, '%') 
                  AND companyid = ? 
                  AND subcompanyid = ? 
                  AND projectid = ?
                  AND YEAR(created_at) = ?  -- assuming you have a created_at or order_date column
                ORDER BY psono DESC 
                LIMIT 1";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL Prepare failed: " . $conn->error);
        }

        // Bind parameters (siii + i for year)
        $stmt->bind_param("siiii", $prefix, $companyid, $subcompanyid, $projectid, $year);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $lastNo = $row['psono'];
            // Extract numeric part after the last dash
            $lastDigits = intval(substr($lastNo, strrpos($lastNo, '-') + 1));
            $newNumber = $lastDigits + 1;
        }

        $stmt->close();
    } catch (Exception $e) {
        error_log("Error generating PSO number: " . $e->getMessage());
        return null;
    }

    // Format to 5 digits with leading zeros
    $formattedNumber = str_pad($newNumber, 5, "0", STR_PAD_LEFT);

    // Final PSO number
    return $prefix . $formattedNumber;
}

/**
 * Helper to get 2-digit Indian Financial Year string (e.g. '26-27' for April 2026 - March 2027)
 */
if (!function_exists('getDispatchInvoiceFinancialYear')) {
    function getDispatchInvoiceFinancialYear($date = null) {
        if (empty($date)) {
            $date = date('Y-m-d');
        }
        $time = strtotime($date);
        if (!$time) {
            $time = time();
        }
        $year = intval(date('Y', $time));
        $month = intval(date('n', $time));

        if ($month >= 4) {
            $startYear = $year;
            $endYear = $year + 1;
        } else {
            $startYear = $year - 1;
            $endYear = $year;
        }

        $startY2 = date('y', strtotime($startYear . '-01-01'));
        $endY2 = date('y', strtotime($endYear . '-01-01'));

        return [
            'fy_str' => $startY2 . '-' . $endY2,
            'start_year' => $startYear,
            'end_year' => $endYear
        ];
    }
}

/**
 * Fetch or auto-initialize serial settings record from krf_invoice_serial_settings table
 */
if (!function_exists('getInvoiceSerialConfig')) {
    function getInvoiceSerialConfig($conn, $invoiceType = 'dispatch', $date = null, $projectid = 1, $companyid = 1) {
        $fyInfo = getDispatchInvoiceFinancialYear($date);
        $currectfinancialyear = $fyInfo['fy_str'];

        // Normalize invoice type
        $normType = strtolower(trim($invoiceType));
        if ($normType === 'dispatchinvoice') $normType = 'dispatch';

        $stmt = $conn->prepare("
            SELECT * FROM krf_invoice_serial_settings 
            WHERE invoicetype = ? AND financial_year = ? AND projectid = ? AND companyid = ? AND status = 'active'
            LIMIT 1
        ");
        if ($stmt) {
            $stmt->bind_param("ssii", $normType, $currectfinancialyear, $projectid, $companyid);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row) {
                return $row;
            }
        }

        // Auto-seed default configuration if not found
        $defaultPrefix = ($normType === 'sale') ? 'KF/SALE/' : 'KF/';
        $defaultStart = 1;
        $defaultCurrent = 0;
        $desc = ucfirst($normType) . " Invoice series (e.g. {$defaultPrefix}{$currectfinancialyear}/1)";

        // For dispatch, scan max existing sequence to prevent backward jumping
        if ($normType === 'dispatch') {
            $escFy = $conn->real_escape_string($currectfinancialyear);
            $res = $conn->query("
                SELECT dinvoiceno FROM krf_sales_order WHERE dinvoiceno LIKE 'KF/{$escFy}/%'
                UNION
                SELECT invoiceno AS dinvoiceno FROM krf_sales_order_items WHERE invoiceno LIKE 'KF/{$escFy}/%'
            ");
            if ($res) {
                while ($r = $res->fetch_assoc()) {
                    if (preg_match('/^KF\/' . preg_quote($currectfinancialyear, '/') . '\/(\d+)$/i', trim($r['dinvoiceno'] ?? ''), $m)) {
                        $defaultCurrent = max($defaultCurrent, intval($m[1]));
                    }
                }
            }
        }

        $ins = $conn->prepare("
            INSERT INTO krf_invoice_serial_settings 
                (companyid, projectid, invoicetype, financial_year, prefix, starting_serial_no, current_serial_no, padding_digits, description)
            VALUES 
                (?, ?, ?, ?, ?, ?, ?, 0, ?)
            ON DUPLICATE KEY UPDATE updated_at = NOW()
        ");
        if ($ins) {
            $ins->bind_param("iisssiis", $companyid, $projectid, $normType, $currectfinancialyear, $defaultPrefix, $defaultStart, $defaultCurrent, $desc);
            $ins->execute();
            $newId = $ins->insert_id;
            $ins->close();

            return [
                'id' => $newId,
                'companyid' => $companyid,
                'projectid' => $projectid,
                'invoicetype' => $normType,
                'financial_year' => $currectfinancialyear,
                'prefix' => $defaultPrefix,
                'starting_serial_no' => $defaultStart,
                'current_serial_no' => $defaultCurrent,
                'padding_digits' => 0,
                'status' => 'active',
                'description' => $desc
            ];
        }

        return null;
    }
}

/**
 * Update current_serial_no in krf_invoice_serial_settings table after generating and saving invoice
 */
if (!function_exists('updateInvoiceCurrentSerialNo')) {
    function updateInvoiceCurrentSerialNo($conn, $invoiceType, $allocatedSerialNo, $date = null, $projectid = 1, $companyid = 1, $updatedBy = 'admin') {
        $fyInfo = getDispatchInvoiceFinancialYear($date);
        $currectfinancialyear = $fyInfo['fy_str'];
        $normType = strtolower(trim($invoiceType));
        if ($normType === 'dispatchinvoice') $normType = 'dispatch';
        $allocatedSeq = intval($allocatedSerialNo);

        $upd = $conn->prepare("
            UPDATE krf_invoice_serial_settings 
            SET current_serial_no = GREATEST(current_serial_no, ?),
                updated_at = NOW(),
                updated_by = ?
            WHERE invoicetype = ? AND financial_year = ? AND projectid = ? AND companyid = ?
        ");
        if ($upd) {
            $upd->bind_param("isssii", $allocatedSeq, $updatedBy, $normType, $currectfinancialyear, $projectid, $companyid);
            $upd->execute();
            $upd->close();
            return true;
        }
        return false;
    }
}

/**
 * Generate duplicate-proof sequential Invoice Number configured by invoicetype from krf_invoice_serial_settings table
 */
if (!function_exists('generateInvoiceNumberByType')) {
    function generateInvoiceNumberByType($conn, $invoiceType = 'dispatch', $psono = '', $sono = '', $date = null, $projectid = 1, $companyid = 1, $forceNew = false) {
        $fyInfo = getDispatchInvoiceFinancialYear($date);
        $currectfinancialyear = $fyInfo['fy_str'];
        $normType = strtolower(trim($invoiceType));
        if ($normType === 'dispatchinvoice') $normType = 'dispatch';

        // 1. If order already has an assigned dinvoiceno matching this prefix, reuse it ONLY if not forcing new and NOT already used in sales_invoice
        if (!$forceNew && (!empty($psono) || !empty($sono))) {
            $prefixPattern = ($normType === 'sale') ? "KF/SALE/" . $currectfinancialyear . "/%" : "KF/" . $currectfinancialyear . "/%";
            $chkSql = "SELECT dinvoiceno FROM krf_sales_order WHERE ((psono = ? AND ? != '') OR (sono = ? AND ? != '')) AND dinvoiceno LIKE ? LIMIT 1";
            $chkStmt = $conn->prepare($chkSql);
            if ($chkStmt) {
                $chkStmt->bind_param("sssss", $psono, $psono, $sono, $sono, $prefixPattern);
                $chkStmt->execute();
                $cRow = $chkStmt->get_result()->fetch_assoc();
                $chkStmt->close();
                if ($cRow && !empty($cRow['dinvoiceno'])) {
                    $foundInv = trim($cRow['dinvoiceno']);
                    $escInv = $conn->real_escape_string($foundInv);
                    $siChk = $conn->query("SELECT 1 FROM sales_invoice WHERE invoiceno = '{$escInv}' AND LOWER(IFNULL(status, '')) != 'cancelled' LIMIT 1");
                    if (!$siChk || $siChk->num_rows === 0) {
                        return $foundInv;
                    }
                }
            }
        }

        // 2. Load serial settings from krf_invoice_serial_settings
        $cfg = getInvoiceSerialConfig($conn, $normType, $date, $projectid, $companyid);
        $prefix = !empty($cfg['prefix']) ? $cfg['prefix'] : (($normType === 'sale') ? 'KF/SALE/' : 'KF/');
        $fullPrefix = $prefix . $currectfinancialyear . "/";
        $startNo = intval($cfg['starting_serial_no'] ?? 1);
        $currNo = intval($cfg['current_serial_no'] ?? 0);
        $padding = intval($cfg['padding_digits'] ?? 0);

        // 3. Scan existing records in DB to make sure we never collide with existing numbers
        $maxSeq = max($startNo - 1, $currNo);
        $escPrefix = $conn->real_escape_string($fullPrefix);
        $res = $conn->query("
            SELECT dinvoiceno FROM krf_sales_order WHERE dinvoiceno LIKE '{$escPrefix}%'
            UNION
            SELECT invoiceno AS dinvoiceno FROM krf_sales_order_items WHERE invoiceno LIKE '{$escPrefix}%'
            UNION
            SELECT invoiceno AS dinvoiceno FROM sales_invoice WHERE invoiceno LIKE '{$escPrefix}%'
        ");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $inv = trim($row['dinvoiceno'] ?? '');
                if (preg_match('/^' . preg_quote($fullPrefix, '/') . '(\d+)$/i', $inv, $m)) {
                    $num = intval($m[1]);
                    if ($num > $maxSeq) {
                        $maxSeq = $num;
                    }
                }
            }
        }

        $candidateCount = $maxSeq + 1;
        if ($candidateCount < $startNo) {
            $candidateCount = $startNo;
        }

        // 4. Collision check loop
        while (true) {
            $formattedNum = ($padding > 0) ? sprintf('%0' . $padding . 'd', $candidateCount) : $candidateCount;
            $candidateInv = $fullPrefix . $formattedNum;
            $escCandidate = $conn->real_escape_string($candidateInv);
            $chkExist = $conn->query("
                SELECT 1 FROM krf_sales_order WHERE dinvoiceno = '{$escCandidate}'
                UNION
                SELECT 1 FROM krf_sales_order_items WHERE invoiceno = '{$escCandidate}'
                UNION
                SELECT 1 FROM sales_invoice WHERE invoiceno = '{$escCandidate}'
                LIMIT 1
            ");
            if ($chkExist && $chkExist->num_rows > 0) {
                $candidateCount++; // Collision detected -> increment
            } else {
                return $candidateInv;
            }
        }
    }
}

/**
 * Generate duplicate-proof sequential Dispatch Invoice Number using krf_invoice_serial_settings
 * Format: KF/26-27/1, KF/26-27/2, etc.
 */
if (!function_exists('generateDispatchInvoiceNo')) {
    function generateDispatchInvoiceNo($conn, $psono = '', $sono = '', $date = null, $forceNew = false) {
        return generateInvoiceNumberByType($conn, 'dispatch', $psono, $sono, $date, 1, 1, $forceNew);
    }
}

/**
 * Generate duplicate-proof sequential Sale Order Invoice Number using krf_invoice_serial_settings
 */
if (!function_exists('generateSaleInvoiceNo')) {
    function generateSaleInvoiceNo($conn, $psono = '', $sono = '', $date = null, $forceNew = false) {
        return generateInvoiceNumberByType($conn, 'sale', $psono, $sono, $date, 1, 1, $forceNew);
    }
}

/**
 * Backward compatibility alias for getNewDispatchInvoiceNumber
 */
if (!function_exists('getNewDispatchInvoiceNumber')) {
    function getNewDispatchInvoiceNumber($conn, $psono = '', $sono = '', $forceNew = false) {
        return generateDispatchInvoiceNo($conn, $psono, $sono, null, $forceNew);
    }
}

function getNewProductionStockOrderNo($conn, $companyid, $projectid) {
    $maxAttempts = 5; // Avoid infinite loops
    $attempt = 0;
    $newPrdStON ="";
    while ($attempt < $maxAttempts) {
        // Generate new stock order no
        $newPrdStON = 'KFP' . rand(1000, 9999);
        // Check if it already exists
        $sql = "SELECT stockorder_no 
                FROM krf_production_stockorder
                WHERE companyid = ?
                  AND projectid = ?
                  AND stockorder_no = ?
                LIMIT 1";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("SQL Prepare Failed: " . $conn->error);
            return null;
        }
        $stmt->bind_param("iis", $companyid, $projectid, $newPrdStON);
        $stmt->execute();
        $stmt->store_result();
        // If NOT found → it's unique → return it
        if ($stmt->num_rows === 0) {
            $stmt->close();
            return $newPrdStON;
        }
        // If exists → retry
        $stmt->close();
        $attempt++;
    }
    // If too many attempts failed, return null (duplicate issue)
    return $newPrdStON;
}

function getProvisionalSalesOrders($conn, $companyid, $subcompanyid, $projectid, $shortby = "all", $shortbyvalue = "all") {
    global $authconn;
    $data = [];
    try {
        // Build user map from authconn login_user_master to resolve created_by ID to user name
        $userMap = [];
        if (isset($authconn) && $authconn) {
            $uRes = @$authconn->query("SELECT sno, loginid, loginname, mobileno FROM login_user_master");
            if ($uRes) {
                while ($uRow = $uRes->fetch_assoc()) {
                    $name = trim($uRow['loginname'] ?? '');
                    if (empty($name)) continue;

                    $sno = trim((string)$uRow['sno']);
                    $loginid = trim((string)$uRow['loginid']);
                    $mobile = trim((string)$uRow['mobileno']);

                    if ($sno !== '') $userMap[$sno] = $name;
                    if ($loginid !== '') {
                        $userMap[$loginid] = $name;
                        $userMap[strtolower($loginid)] = $name;
                    }
                    if ($mobile !== '') {
                        $userMap[$mobile] = $name;
                    }

                    $cleanMobile = preg_replace('/\D/', '', $mobile);
                    if (strlen($cleanMobile) >= 10) {
                        $userMap[substr($cleanMobile, -10)] = $name;
                    }

                    $cleanLogin = preg_replace('/\D/', '', $loginid);
                    if (strlen($cleanLogin) >= 10) {
                        $userMap[substr($cleanLogin, -10)] = $name;
                    }
                }
            }
        }

        // Base query with LEFT JOINs to fetch provisional orders, freight charges, and already paid advance payments
        $baseQuery = "
            SELECT 
                o.id, 
                o.psono, 
                o.companyid, 
                o.subcompanyid, 
                o.projectid, 
                o.clientid, 
                o.clientname, 
                o.emailid, 
                o.delivery_address, 
                o.districtname, 
                o.statename, 
                o.mobileno, 
                o.payment_terms, 
                o.remarks, 
                o.status, 
                o.pickupfrom,
                o.created_by, 
                o.created_at, 
                o.updated_at,
                IFNULL(p.paid_amount, 0) AS paid_amount,
                IFNULL(f.payable_freightamount, 0) AS freight_amount
            FROM krf_provisional_sales_order o
            LEFT JOIN (
                SELECT invoiceno, SUM(advanceamount) AS paid_amount 
                FROM advance_payment_transactions 
                WHERE invoiceno IS NOT NULL AND invoiceno != '' AND (tedit IS NULL OR tedit = '')
                GROUP BY invoiceno
            ) p ON o.psono = p.invoiceno
            LEFT JOIN (
                SELECT psono, SUM(payable_freightamount) AS payable_freightamount 
                FROM krf_freightcharges 
                WHERE psono IS NOT NULL AND psono != ''
                GROUP BY psono
            ) f ON o.psono = f.psono
            WHERE (o.status='Pending Approval' or o.status='Confirmed' or o.status='Draft' or o.status='Emergency Sowing' or o.status='' or o.status is null or o.status='Cancelled') 
              AND o.companyid = '".$conn->real_escape_string($companyid)."'
              AND o.subcompanyid = '".$conn->real_escape_string($subcompanyid)."'
              AND o.projectid = '".$conn->real_escape_string($projectid)."'";

        // Conditions
        if ($shortby == "psono") {
            $baseQuery .= " AND o.psono = '".$conn->real_escape_string($shortbyvalue)."'";
        } else if ($shortby == "id" || $shortby == "sno") {
            $baseQuery .= " AND o.id = '".$conn->real_escape_string($shortbyvalue)."'";
        } elseif ($shortby == "status") {
            $baseQuery .= " AND o.status = '".$conn->real_escape_string($shortbyvalue)."'";
        } elseif ($shortby == "likelyclientname") {
            $baseQuery .= " AND o.clientname like '%".$conn->real_escape_string($shortbyvalue)."%'";
        } elseif ($shortby == "clientname") {
            $baseQuery .= " AND o.clientname = '".$conn->real_escape_string($shortbyvalue)."'";
        } elseif ($shortby == "clientid") {
            $baseQuery .= " AND o.clientid = '".$conn->real_escape_string($shortbyvalue)."'";
        } elseif ($shortby == "onlyconfirmedWithclientname") {
            $baseQuery .= " AND o.status='Confirmed' AND o.clientname = '".$conn->real_escape_string($shortbyvalue)."'";
        } elseif ($shortby == "onlyconfirmedWithclientid") {
            $baseQuery .= " AND o.status='Confirmed' AND o.clientid = '".$conn->real_escape_string($shortbyvalue)."'";
        } elseif ($shortby == "onlydraftWithclientname") {
            $baseQuery .= " AND o.status='Draft' AND o.clientname = '".$conn->real_escape_string($shortbyvalue)."'";
        } elseif ($shortby == "onlydraftWithclientid") {
            $baseQuery .= " AND o.status='Draft' AND o.clientid = '".$conn->real_escape_string($shortbyvalue)."'";
        } elseif ($shortby == "onlyconfirmedWithmobileno") {
            $baseQuery .= " AND o.status='Confirmed' AND o.mobileno = '".$conn->real_escape_string($shortbyvalue)."'";
        } elseif ($shortby == "onlydraftWithmobileno") {
            $baseQuery .= " AND o.status='Draft' AND o.mobileno = '".$conn->real_escape_string($shortbyvalue)."'";
        } elseif ($shortby == "mobileno") {
            $baseQuery .= " AND o.mobileno = '".$conn->real_escape_string($shortbyvalue)."'";
        }
        $baseQuery .= " ORDER BY o.created_at DESC";

        $result = $conn->query($baseQuery);
        if ($result && $result->num_rows > 0) {
            $psonos = [];
            $orders = [];
            while ($row = $result->fetch_assoc()) {
                $row['items'] = [];
                $row['items_amount'] = 0;
                $row['total_amount'] = (float)$row['freight_amount'];
                $row['total_quantity'] = 0;
                $row['product_name'] = '';
                $row['quantity'] = 0;
                $row['expecteddeliverydate'] = '';
                $orders[$row['psono']] = $row;
                $psonos[] = "'" . $conn->real_escape_string($row['psono']) . "'";
            }

            // Fetch all line items for the matched provisional orders
            if (!empty($psonos)) {
                $itemSql = "SELECT * FROM krf_provisional_sales_order_items WHERE psono IN (" . implode(',', $psonos) . ") ORDER BY id ASC";
                $itemRes = $conn->query($itemSql);
                if ($itemRes && $itemRes->num_rows > 0) {
                    while ($iRow = $itemRes->fetch_assoc()) {
                        $p = $iRow['psono'];
                        if (isset($orders[$p])) {
                            $orders[$p]['items'][] = $iRow;
                            $orders[$p]['items_amount'] += (float)$iRow['amount'];
                            $orders[$p]['total_quantity'] += (float)$iRow['quantity'];
                            if (empty($orders[$p]['product_name'])) {
                                $orders[$p]['product_name'] = $iRow['product_name'];
                                $orders[$p]['quantity'] = $iRow['quantity'];
                                $orders[$p]['expecteddeliverydate'] = $iRow['expecteddeliverydate'];
                            }
                        }
                    }
                }
            }

            // Initialize payments array for each order
            foreach ($orders as $p => $ord) {
                $orders[$p]['payments'] = [];
            }

            // Fetch all payment transactions for the matched provisional orders
            if (!empty($psonos)) {
                $paySql = "SELECT sno, invoiceno, advanceamount, paymentdate, sourceofpayment, utrnumber, remarks, purposeofpayment, receivedby, receivedlocation, verification 
                           FROM advance_payment_transactions 
                           WHERE invoiceno IN (" . implode(',', $psonos) . ") AND (tedit IS NULL OR tedit = '')
                           ORDER BY paymentdate ASC, sno ASC";
                $payRes = $conn->query($paySql);
                if ($payRes && $payRes->num_rows > 0) {
                    while ($payRow = $payRes->fetch_assoc()) {
                        $p = $payRow['invoiceno'];
                        if (isset($orders[$p])) {
                            $orders[$p]['payments'][] = $payRow;
                        }
                    }
                }
            }

            // Fetch delivery location & box packing charges for the matched provisional orders
            $locData = [];
            if (!empty($psonos)) {
                $locSql = "SELECT psono, pickuptype, noofbox, boxperpack, chargesperbox, deliverycharges, freightamt, totalamount, pickuppoint, deliveryaddress, dest_city, dest_district, dest_state 
                           FROM krf_deliveryaddress_location 
                           WHERE psono IN (" . implode(',', $psonos) . ") AND (tedit IS NULL OR tedit = '') 
                           ORDER BY id ASC";
                $locRes = $conn->query($locSql);
                if ($locRes && $locRes->num_rows > 0) {
                    while ($lRow = $locRes->fetch_assoc()) {
                        $p = $lRow['psono'];
                        if (!isset($locData[$p])) {
                            $locData[$p] = [
                                'box_packing_amount' => 0.0,
                                'delivered_freight' => 0.0,
                                'boxes' => 0,
                                'chargesperbox' => 0.0,
                                'boxperpack' => 0,
                                'pickuppoint' => '',
                                'deliveryaddress' => '',
                                'pickuptypes' => []
                            ];
                        }
                        $ptype = strtolower(trim($lRow['pickuptype'] ?? ''));
                        if ($ptype) {
                            $locData[$p]['pickuptypes'][] = $ptype;
                        }
                        if ($ptype === 'boxpacking') {
                            $bAmt = 0.0;
                            if ((float)($lRow['freightamt'] ?? 0) > 0) {
                                $bAmt = (float)$lRow['freightamt'];
                            } elseif ((float)($lRow['deliverycharges'] ?? 0) > 0) {
                                $bAmt = (float)$lRow['deliverycharges'];
                            } elseif ((int)($lRow['noofbox'] ?? 0) > 0 && (float)($lRow['chargesperbox'] ?? 0) > 0) {
                                $bAmt = (int)$lRow['noofbox'] * (float)$lRow['chargesperbox'];
                            }
                            $locData[$p]['box_packing_amount'] += $bAmt;
                            $locData[$p]['boxes'] += (int)($lRow['noofbox'] ?? 0);
                            if ($locData[$p]['chargesperbox'] <= 0 && (float)($lRow['chargesperbox'] ?? 0) > 0) {
                                $locData[$p]['chargesperbox'] = (float)$lRow['chargesperbox'];
                            }
                            if ($locData[$p]['boxperpack'] <= 0 && (int)($lRow['boxperpack'] ?? 0) > 0) {
                                $locData[$p]['boxperpack'] = (int)$lRow['boxperpack'];
                            }
                            if (empty($locData[$p]['pickuppoint']) && !empty($lRow['pickuppoint'])) {
                                $locData[$p]['pickuppoint'] = trim($lRow['pickuppoint']);
                            }
                            if (empty($locData[$p]['deliveryaddress'])) {
                                $addr = trim($lRow['deliveryaddress'] ?? '');
                                if (empty($addr)) {
                                    $parts = array_filter([$lRow['dest_city'] ?? '', $lRow['dest_district'] ?? '', $lRow['dest_state'] ?? '']);
                                    $addr = implode(', ', $parts);
                                }
                                $locData[$p]['deliveryaddress'] = $addr;
                            }
                        } elseif ($ptype === 'delivered') {
                            $dAmt = 0.0;
                            if ((float)($lRow['freightamt'] ?? 0) > 0) {
                                $dAmt = (float)$lRow['freightamt'];
                            } elseif ((float)($lRow['deliverycharges'] ?? 0) > 0) {
                                $dAmt = (float)$lRow['deliverycharges'];
                            }
                            $locData[$p]['delivered_freight'] += $dAmt;
                        }
                    }
                }
            }

            // Total amount = items total amount + freight charges amount + box packing charges amount
            foreach ($orders as $p => $ord) {
                $rawCreatedBy = trim((string)($orders[$p]['created_by'] ?? ''));
                $resolvedCreatedBy = $userMap[$rawCreatedBy] ?? ($userMap[strtolower($rawCreatedBy)] ?? '');
                if (empty($resolvedCreatedBy)) {
                    $cleanCreated = preg_replace('/\D/', '', $rawCreatedBy);
                    if (strlen($cleanCreated) >= 10 && isset($userMap[substr($cleanCreated, -10)])) {
                        $resolvedCreatedBy = $userMap[substr($cleanCreated, -10)];
                    } else if (!empty($rawCreatedBy)) {
                        $resolvedCreatedBy = $rawCreatedBy;
                    } else {
                        $resolvedCreatedBy = 'Admin';
                    }
                }
                $orders[$p]['created_by_raw'] = $rawCreatedBy;
                $orders[$p]['created_by'] = $resolvedCreatedBy;
                $orders[$p]['created_by_name'] = $resolvedCreatedBy;

                $pLoc = $locData[$p] ?? null;
                $boxAmt = $pLoc ? (float)$pLoc['box_packing_amount'] : 0.0;
                $hasBox = ($boxAmt > 0 || strtolower(trim($orders[$p]['pickupfrom'] ?? '')) === 'boxpacking');

                // If freight_amount from krf_freightcharges is 0, check delivered_freight from location
                if ((float)$orders[$p]['freight_amount'] <= 0 && $pLoc && (float)$pLoc['delivered_freight'] > 0) {
                    $orders[$p]['freight_amount'] = (float)$pLoc['delivered_freight'];
                }

                $orders[$p]['box_packing_amount'] = round($boxAmt, 2);
                $orders[$p]['has_box_packing'] = $hasBox;
                $orders[$p]['box_packing_details'] = [
                    'amount' => round($boxAmt, 2),
                    'noofbox' => $pLoc ? (int)$pLoc['boxes'] : 0,
                    'chargesperbox' => $pLoc ? (float)$pLoc['chargesperbox'] : 0.0,
                    'boxperpack' => $pLoc ? (int)$pLoc['boxperpack'] : 0,
                    'pickuppoint' => $pLoc ? $pLoc['pickuppoint'] : '',
                    'deliveryaddress' => $pLoc ? $pLoc['deliveryaddress'] : ''
                ];

                $orders[$p]['total_charges'] = round((float)$orders[$p]['freight_amount'] + $boxAmt, 2);
                $orders[$p]['total_amount'] = round((float)$orders[$p]['items_amount'] + (float)$orders[$p]['freight_amount'] + $boxAmt, 2);
                $orders[$p]['pending_balance'] = round(max(0, (float)$orders[$p]['total_amount'] - (float)$orders[$p]['paid_amount']), 2);
                $data[] = $orders[$p];
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching provisional sales orders: " . $e->getMessage());
        return [];
    }
    return $data;
} 

function getProvisionalSalesOrdersDetailed(
    $conn,
    $authconn,
    $companyid,
    $subcompanyid,
    $projectid,
    $shortby = "all",
    $shortbyvalue = "all",
    $loginroleid = 0,
    $loginid = ""
) {
    $data = [];

    try {
        $companyid    = trim((string)$companyid);
        $subcompanyid = trim((string)$subcompanyid);
        $projectid    = (int)$projectid;
        $loginroleid  = (int)$loginroleid;
        $loginid      = trim((string)$loginid);

        if ( $companyid === '' || $subcompanyid === '' ||  $projectid <= 0
        ) {
            throw new Exception(
                "Company, subcompany and project are required."
            );
        }

        /*
         * Items remain as individual rows.
         * Order and advance totals are calculated separately.
         */
        $baseQuery = "
            SELECT
                o.id,
                o.psono,
                o.companyid,
                o.subcompanyid,
                o.projectid,
                o.clientid,
                o.clientname,
                o.emailid,
                o.delivery_address,
                o.districtname,
                o.statename,
                o.mobileno,
                o.payment_terms,
                o.remarks,
                o.status,
                o.pickupfrom,
                o.created_by,
                o.created_at,
                o.updated_at,

                i.product_name,
                i.quantity,
                i.expecteddeliverydate,
                COALESCE(i.amount, 0) AS item_amount,

                COALESCE(
                    item_total.orderamount,
                    0
                ) AS orderamount,

                COALESCE(
                    advance_total.advanceamt,
                    0
                ) AS advanceamt,

                (
                    COALESCE(
                        item_total.orderamount,
                        0
                    )
                    -
                    COALESCE(
                        advance_total.advanceamt,
                        0
                    )
                ) AS balanceamt

            FROM krf_provisional_sales_order AS o

            LEFT JOIN krf_provisional_sales_order_items AS i
                ON i.psono = o.psono

            LEFT JOIN
            (
                SELECT
                    psono,
                    SUM(
                        COALESCE(amount, 0)
                    ) AS orderamount
                FROM krf_provisional_sales_order_items
                WHERE LOWER(TRIM(COALESCE(status, ''))) NOT IN ('cancelled', 'cancel', 'remove', 'removed')
                GROUP BY psono
            ) AS item_total
                ON item_total.psono = o.psono

            LEFT JOIN
            (
                SELECT
                    invoiceno,
                    clientid,
                    SUM(
                        COALESCE(advanceamount, 0)
                    ) AS advanceamt
                FROM advance_payment_transactions
                WHERE tedit IS NULL
                   OR tedit = ''
                GROUP BY
                    invoiceno,
                    clientid
            ) AS advance_total
                ON advance_total.invoiceno = o.psono
               AND advance_total.clientid = o.clientid

            WHERE (
                    o.status IN (
                        'Confirmed',
                        'Draft',
						'Pending Approval',
                        'Emergency Sowing',
                        ''
                    )
                    OR o.status IS NULL
                )
              AND o.companyid = ?
              AND o.subcompanyid = ?
              AND o.projectid = ?
        ";

        $types = "ssi";

        $params = [
            $companyid,
            $subcompanyid,
            $projectid
        ];

        /*
         * Role restriction.
         */
        if ($loginroleid > 3) {
            if ($loginid === '') {
                return [];
            }

            $baseQuery .= "
                AND o.created_by = ?
            ";

            $types .= "s";
            $params[] = $loginid;
        }

        /*
         * Additional filters.
         */
        switch ($shortby) {
            case "psono":
                $baseQuery .= " AND o.psono = ?";
                $types .= "s";
                $params[] = (string)$shortbyvalue;
                break;

            case "id":
            case "sno":
                $baseQuery .= " AND o.id = ?";
                $types .= "i";
                $params[] = (int)$shortbyvalue;
                break;

            case "status":
                $baseQuery .= " AND o.status = ?";
                $types .= "s";
                $params[] = (string)$shortbyvalue;
                break;

            case "likelyclientname":
                $baseQuery .= " AND o.clientname LIKE ?";
                $types .= "s";
                $params[] = "%" . $shortbyvalue . "%";
                break;

            case "clientname":
                $baseQuery .= " AND o.clientname = ?";
                $types .= "s";
                $params[] = (string)$shortbyvalue;
                break;

            case "onlyconfirmedWithclientname":
                $baseQuery .= "
                    AND o.status = 'Confirmed'
                    AND o.clientname = ?
                ";
                $types .= "s";
                $params[] = (string)$shortbyvalue;
                break;

            case "onlyconfirmedWithclientid":
                $baseQuery .= "
                    AND o.status = 'Confirmed'
                    AND o.clientid = ?
                ";
                $types .= "s";
                $params[] = (string)$shortbyvalue;
                break;

            case "onlydraftWithclientname":
                $baseQuery .= "
                    AND o.status = 'Draft'
                    AND o.clientname = ?
                ";
                $types .= "s";
                $params[] = (string)$shortbyvalue;
                break;

            case "onlydraftWithclientid":
                $baseQuery .= "
                    AND o.status = 'Draft'
                    AND o.clientid = ?
                ";
                $types .= "s";
                $params[] = (string)$shortbyvalue;
                break;

            case "onlyconfirmedWithmobileno":
                $baseQuery .= "
                    AND o.status = 'Confirmed'
                    AND o.mobileno = ?
                ";
                $types .= "s";
                $params[] = (string)$shortbyvalue;
                break;

            case "onlydraftWithmobileno":
                $baseQuery .= "
                    AND o.status = 'Draft'
                    AND o.mobileno = ?
                ";
                $types .= "s";
                $params[] = (string)$shortbyvalue;
                break;

            case "mobileno":
                $baseQuery .= " AND o.mobileno = ?";
                $types .= "s";
                $params[] = (string)$shortbyvalue;
                break;
        }

        $baseQuery .= "
            ORDER BY
                o.created_at ASC,
                i.expecteddeliverydate ASC,
                o.psono ASC
        ";

        $stmt = $conn->prepare($baseQuery);

        if (!$stmt) {
            throw new Exception(
                "Main query prepare failed: " .
                $conn->error
            );
        }

        $stmt->bind_param(
            $types,
            ...$params
        );

        if (!$stmt->execute()) {
            throw new Exception(
                "Main query execution failed: " .
                $stmt->error
            );
        }

        $result = $stmt->get_result();
        $createdByIds = [];

        while ($row = $result->fetch_assoc()) {
            $createdBy = trim(
                (string)($row["created_by"] ?? "")
            );

            $row["loginname"]   = "";
            $row["loginroleid"] = "";

            if ($createdBy !== "") {
                $createdByIds[$createdBy] = $createdBy;
            }

            $data[] = $row;
        }

        $stmt->close();

        /*
         * Return main data when there are no creator IDs.
         */
        if (empty($data) || empty($createdByIds)) {
            return $data;
        }

        /*
         * Fetch all required users in one auth query.
         */
        $createdByIds = array_values($createdByIds);

        $placeholders = implode(
            ",",
            array_fill(
                0,
                count($createdByIds),
                "?"
            )
        );

        $authQuery = "
            SELECT
                loginid,
                IFNULL(loginname, '') AS loginname,
                loginroleid
            FROM login_user_master
            WHERE loginid IN ($placeholders)
        ";

        $authStmt = $authconn->prepare($authQuery);

        if (!$authStmt) {
            throw new Exception(
                "Authentication query prepare failed: " .
                $authconn->error
            );
        }

        $authTypes = str_repeat(
            "s",
            count($createdByIds)
        );

        $authStmt->bind_param(
            $authTypes,
            ...$createdByIds
        );

        if (!$authStmt->execute()) {
            throw new Exception(
                "Authentication query execution failed: " .
                $authStmt->error
            );
        }

        $authResult = $authStmt->get_result();
        $loginUsers = [];

        while ($user = $authResult->fetch_assoc()) {
            $userLoginId = trim(
                (string)$user["loginid"]
            );

            $loginUsers[$userLoginId] = [
                "loginname" =>
                    $user["loginname"] ?? "",

                "loginroleid" =>
                    $user["loginroleid"] ?? ""
            ];
        }

        $authStmt->close();

        /*
         * Merge login details into order rows.
         */
        foreach ($data as &$row) {
            $createdBy = trim(
                (string)($row["created_by"] ?? "")
            );

            if (!isset($loginUsers[$createdBy])) {
                continue;
            }

            $row["loginname"] =
                $loginUsers[$createdBy]["loginname"];

            $row["loginroleid"] =
                $loginUsers[$createdBy]["loginroleid"];
        }

        unset($row);

        return $data;

    } catch (Throwable $e) {
        error_log(
            "getProvisionalSalesOrdersDetailed error: " .
            $e->getMessage()
        );

        return [];
    }
}
function getPendingProvisionalSalesOrdersDetailed(
    $conn,
    $authconn,
    $companyid,
    $subcompanyid,
    $projectid,
    $shortby = "all",
    $shortbyvalue = "all",
    $loginroleid = 0,
    $loginid = ""
) {
    $data = [];

    try {
        $companyid    = trim((string)$companyid);
        $subcompanyid = trim((string)$subcompanyid);
        $projectid    = (int)$projectid;
        $loginroleid  = (int)$loginroleid;
        $loginid      = trim((string)$loginid);

        if ( $companyid === '' || $subcompanyid === '' ||  $projectid <= 0
        ) {
            throw new Exception(
                "Company, subcompany and project are required."
            );
        }

        /*
         * Items remain as individual rows.
         * Order and advance totals are calculated separately.
         */
        $baseQuery = "
            SELECT
                o.id,
                o.psono,
                o.companyid,
                o.subcompanyid,
                o.projectid,
                o.clientid,
                o.clientname,
                o.emailid,
                o.delivery_address,
                o.districtname,
                o.statename,
                o.mobileno,
                o.payment_terms,
                o.remarks,
                o.status,
                o.pickupfrom,
                o.created_by,
                o.created_at,
                o.updated_by,
                o.updated_at,

                i.id AS item_id,
                i.product_id,
                i.stockorder_no,
                i.status AS item_status,
                i.remark AS item_remark,
                i.product_name,
                i.quantity,
                i.expecteddeliverydate,
                COALESCE(i.amount, 0) AS item_amount,

                (
                    COALESCE(item_total.orderamount, 0)
                    +
                    GREATEST(COALESCE(fc_total.freight_charges, 0), COALESCE(loc_total.loc_charges, 0))
                ) AS orderamount,

                COALESCE(
                    advance_total.advanceamt,
                    0
                ) AS advanceamt,

                (
                    (
                        COALESCE(item_total.orderamount, 0)
                        +
                        GREATEST(COALESCE(fc_total.freight_charges, 0), COALESCE(loc_total.loc_charges, 0))
                    )
                    -
                    COALESCE(
                        advance_total.advanceamt,
                        0
                    )
                ) AS balanceamt,
                o.mobileno

            FROM krf_provisional_sales_order AS o

            LEFT JOIN krf_provisional_sales_order_items AS i
                ON i.psono = o.psono


            LEFT JOIN
            (
                SELECT
                    psono,
                    SUM(
                        COALESCE(amount, 0)
                    ) AS orderamount
                FROM krf_provisional_sales_order_items
                WHERE LOWER(TRIM(COALESCE(status, ''))) NOT IN ('cancelled', 'cancel', 'remove', 'removed')
                GROUP BY psono
            ) AS item_total
                ON item_total.psono = o.psono

            LEFT JOIN
            (
                SELECT
                    psono,
                    SUM(CASE 
                        WHEN pickuptype = 'boxpacking' THEN (CASE WHEN freightamt > 0 THEN freightamt WHEN deliverycharges > 0 THEN deliverycharges ELSE (noofbox * chargesperbox) END)
                        ELSE (CASE WHEN freightamt > 0 THEN freightamt ELSE deliverycharges END)
                    END) AS loc_charges
                FROM krf_deliveryaddress_location
                WHERE (tedit IS NULL OR tedit = '')
                GROUP BY psono
            ) AS loc_total
                ON loc_total.psono = o.psono

            LEFT JOIN
            (
                SELECT
                    psono,
                    SUM(COALESCE(payable_freightamount, 0)) AS freight_charges
                FROM krf_freightcharges
                WHERE psono IS NOT NULL AND psono != ''
                GROUP BY psono
            ) AS fc_total
                ON fc_total.psono = o.psono

            LEFT JOIN
            (
                SELECT
                    invoiceno,
                    clientid,
                    SUM(
                        COALESCE(advanceamount, 0)
                    ) AS advanceamt
                FROM advance_payment_transactions
                WHERE tedit IS NULL
                   OR tedit = ''
                GROUP BY
                    invoiceno,
                    clientid
            ) AS advance_total
                ON advance_total.invoiceno = o.psono
               AND advance_total.clientid = o.clientid

            WHERE (
                    o.status IN ( 
                        'Draft',
						'Pending Approval',
                        'Emergency Sowing',
                        'Confirmed',
                        'Cancelled',
                        ''
                    )
                    OR o.status IS NULL
                )
              AND o.companyid = ?
              AND o.subcompanyid = ?
              AND o.projectid = ?

        ";

        $types = "ssi";

        $params = [
            $companyid,
            $subcompanyid,
            $projectid
        ];

        /*
         * Role restriction.
         */
        // if ($loginroleid > 3) {
        //     if ($loginid === '') {
        //         return [];
        //     }

        //     $baseQuery .= "
        //         AND o.created_by = ?
        //     ";

        //     $types .= "s";
        //     $params[] = $loginid;
        // }

        /*
         * Additional filters.
         */
        switch ($shortby) {
            case "psono":
                $baseQuery .= " AND o.psono = ?";
                $types .= "s";
                $params[] = (string)$shortbyvalue;
                break;

            case "id":
            case "sno":
                $baseQuery .= " AND o.id = ?";
                $types .= "i";
                $params[] = (int)$shortbyvalue;
                break;

            case "status":
                $baseQuery .= " AND o.status = ?";
                $types .= "s";
                $params[] = (string)$shortbyvalue;
                break;

            case "likelyclientname":
                $baseQuery .= " AND o.clientname LIKE ?";
                $types .= "s";
                $params[] = "%" . $shortbyvalue . "%";
                break;

            case "clientname":
                $baseQuery .= " AND o.clientname = ?";
                $types .= "s";
                $params[] = (string)$shortbyvalue;
                break;

            case "onlyconfirmedWithclientname":
                $baseQuery .= "
                    AND o.status = 'Confirmed'
                    AND o.clientname = ?
                ";
                $types .= "s";
                $params[] = (string)$shortbyvalue;
                break;

            case "onlyconfirmedWithclientid":
                $baseQuery .= "
                    AND o.status = 'Confirmed'
                    AND o.clientid = ?
                ";
                $types .= "s";
                $params[] = (string)$shortbyvalue;
                break;

            case "onlydraftWithclientname":
                $baseQuery .= "
                    AND o.status = 'Draft'
                    AND o.clientname = ?
                ";
                $types .= "s";
                $params[] = (string)$shortbyvalue;
                break;

            case "onlydraftWithclientid":
                $baseQuery .= "
                    AND o.status = 'Draft'
                    AND o.clientid = ?
                ";
                $types .= "s";
                $params[] = (string)$shortbyvalue;
                break;

            case "onlyconfirmedWithmobileno":
                $baseQuery .= "
                    AND o.status = 'Confirmed'
                    AND o.mobileno = ?
                ";
                $types .= "s";
                $params[] = (string)$shortbyvalue;
                break;

            case "onlydraftWithmobileno":
                $baseQuery .= "
                    AND o.status = 'Draft'
                    AND o.mobileno = ?
                ";
                $types .= "s";
                $params[] = (string)$shortbyvalue;
                break;

            case "mobileno":
                $baseQuery .= " AND o.mobileno = ?";
                $types .= "s";
                $params[] = (string)$shortbyvalue;
                break;
        }

        $baseQuery .= "
             ORDER BY
                o.created_at DESC,
                o.psono DESC,
                i.expecteddeliverydate ASC
        ";

        $stmt = $conn->prepare($baseQuery);

        if (!$stmt) {
            throw new Exception(
                "Main query prepare failed: " .
                $conn->error
            );
        }

        $stmt->bind_param(
            $types,
            ...$params
        );

        if (!$stmt->execute()) {
            throw new Exception(
                "Main query execution failed: " .
                $stmt->error
            );
        }

        $result = $stmt->get_result();
        $userLookupIds = [];

        while ($row = $result->fetch_assoc()) {
            $createdBy = trim((string)($row["created_by"] ?? ""));
            $updatedBy = trim((string)($row["updated_by"] ?? ""));

            $row["loginname"]      = $createdBy;
            $row["loginroleid"]    = "";
            $row["updatedby_name"] = $updatedBy;

            if ($createdBy !== "") {
                $userLookupIds[$createdBy] = $createdBy;
            }
            if ($updatedBy !== "") {
                $userLookupIds[$updatedBy] = $updatedBy;
            }

            $data[] = $row;
        }

        $stmt->close();

        /*
         * Return main data when there are no user IDs.
         */
        if (empty($data) || empty($userLookupIds)) {
            return $data;
        }

        /*
         * Fetch all required users in one auth query.
         */
        $userLookupIds = array_values($userLookupIds);

        $placeholders = implode(
            ",",
            array_fill(
                0,
                count($userLookupIds),
                "?"
            )
        );

        $authQuery = "
            SELECT
                loginid,
                IFNULL(loginname, '') AS loginname,
                loginroleid
            FROM login_user_master
            WHERE loginid IN ($placeholders)
        ";

        $authStmt = $authconn->prepare($authQuery);

        if ($authStmt) {
            $authTypes = str_repeat("s", count($userLookupIds));
            $authStmt->bind_param($authTypes, ...$userLookupIds);

            if ($authStmt->execute()) {
                $authResult = $authStmt->get_result();
                $loginUsers = [];

                while ($user = $authResult->fetch_assoc()) {
                    $userLoginId = trim((string)$user["loginid"]);
                    $loginUsers[$userLoginId] = [
                        "loginname"   => $user["loginname"] ?? "",
                        "loginroleid" => $user["loginroleid"] ?? ""
                    ];
                }

                /*
                 * Merge login details into order rows.
                 */
                foreach ($data as &$row) {
                    $createdBy = trim((string)($row["created_by"] ?? ""));
                    $updatedBy = trim((string)($row["updated_by"] ?? ""));

                    if (isset($loginUsers[$createdBy]) && $loginUsers[$createdBy]["loginname"] !== '') {
                        $row["loginname"]   = $loginUsers[$createdBy]["loginname"];
                        $row["loginroleid"] = $loginUsers[$createdBy]["loginroleid"];
                    }

                    if (isset($loginUsers[$updatedBy]) && $loginUsers[$updatedBy]["loginname"] !== '') {
                        $row["updatedby_name"] = $loginUsers[$updatedBy]["loginname"];
                    }
                }
                unset($row);
            }
            $authStmt->close();
        }

        return $data;

    } catch (Throwable $e) {
        error_log(
            "getProvisionalSalesOrdersDetailed error: " .
            $e->getMessage()
        );

        return [];
    }
}
function getProvisionalSalesOrderItems($conn, $psono) {
    $items = [];
    try {
        $stmt = $conn->prepare("SELECT * FROM krf_provisional_sales_order_items WHERE psono = ? AND LOWER(TRIM(COALESCE(status,''))) NOT IN ('cancelled', 'cancel') AND (tedit IS NULL OR tedit = '') AND quantity > 0");
        if (!$stmt) {
            throw new Exception("SQL Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("s", $psono);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }

        $stmt->close();
    } catch (Exception $e) {
        error_log("Error fetching order items: " . $e->getMessage());
        return [];
    }

    return $items;
}

function getSalesInvoiceWithItems($conn, $projectid, $invoiceno) {
    // 1. Fetch from sales_invoice
    $stmt = $conn->prepare("SELECT * FROM sales_invoice WHERE status='active' AND (projectid = ? OR ? = 0) AND invoiceno = ?");
    $stmt->bind_param("iis", $projectid, $projectid, $invoiceno);
    $stmt->execute();
    $invoiceResult = $stmt->get_result();
    $invoice = $invoiceResult->fetch_assoc();
    $stmt->close();

    if ($invoice) {
        $stmt = $conn->prepare("SELECT * FROM sales_invoice_items WHERE status='active' AND (projectid = ? OR ? = 0) AND invoiceno = ?");
        $stmt->bind_param("iis", $projectid, $projectid, $invoiceno);
        $stmt->execute();
        $itemsResult = $stmt->get_result();
        $items = [];
        while ($row = $itemsResult->fetch_assoc()) {
            $items[] = $row;
        }
        $stmt->close();

        return [
            "invoice" => $invoice,
            "items" => $items
        ];
    }

    // 2. Fallback: Fetch from krf_sales_order & krf_sales_order_items by dinvoiceno, invoiceno, sono, or psono
    $soStmt = $conn->prepare("
        SELECT *, 
            COALESCE(NULLIF(dinvoiceno,''), NULLIF(invoiceno,''), sono) AS invoiceno, 
            COALESCE(NULLIF(invoicedate,''), DATE(created_at), CURDATE()) AS invoicedate 
        FROM krf_sales_order 
        WHERE dinvoiceno = ? OR invoiceno = ? OR sono = ? OR psono = ?
        LIMIT 1
    ");
    $soStmt->bind_param("ssss", $invoiceno, $invoiceno, $invoiceno, $invoiceno);
    $soStmt->execute();
    $soRow = $soStmt->get_result()->fetch_assoc();
    $soStmt->close();

    if ($soRow) {
        $soiStmt = $conn->prepare("
            SELECT *, 
                product_name AS productname, 
                COALESCE(NULLIF(allocate_ordqty, 0), quantity) AS quantity, 
                COALESCE(discount_amount, 0) AS discount,
                COALESCE(taxableamount, amount, 0) AS amount
            FROM krf_sales_order_items 
            WHERE sono = ? OR psono = ? OR (invoiceno = ? AND ? != '')
        ");
        $soiStmt->bind_param("ssss", $soRow['sono'], $soRow['psono'], $invoiceno, $invoiceno);
        $soiStmt->execute();
        $soiRes = $soiStmt->get_result();
        $items = [];
        while ($r = $soiRes->fetch_assoc()) {
            $items[] = $r;
        }
        $soiStmt->close();

        return [
            "invoice" => $soRow,
            "items" => $items
        ];
    }

    return null;
}

function getSalesInvoiceDetails($conn, $projectid) {
    // Fetch invoices for a given project
    $stmt = $conn->prepare("SELECT * FROM sales_invoice WHERE status='active' AND projectid = ?");
    $stmt->bind_param("i", $projectid); // only one parameter (integer projectid)
    $stmt->execute();
    $invoiceResult = $stmt->get_result();
    
    $invoices = [];
    while ($row = $invoiceResult->fetch_assoc()) {
        $invoices[] = $row;
    }

    $stmt->close();

    return $invoices; // return array of invoices (empty if none found)
}

function generateUniqueInvoiceNo($conn, $projectid = 0, $prefix = "INV") {
    $datePart = date("Ymd"); // YYYYMMDD
    $uniqueFound = false;
    $counter = 1;

    do {
        // Format invoice number: INV-YYYYMMDD-001
        $numberPart = str_pad($counter, 3, "0", STR_PAD_LEFT);
        $invoiceNo = $prefix . "-" . $datePart . "-" . $numberPart;

        // Check if invoice already exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM sales_invoice WHERE invoiceno = ? AND status='active'");
        if ($projectid > 0) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM sales_invoice WHERE invoiceno = ? AND projectid = ? AND status='active'");
            $stmt->bind_param("si", $invoiceNo, $projectid);
        } else {
            $stmt->bind_param("s", $invoiceNo);
        }

        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count == 0) {
            $uniqueFound = true; // invoice number is unique
        } else {
            $counter++; // try next number
        }
    } while (!$uniqueFound);

    return $invoiceNo;
}

function createSalesOrderFromProvisional($conn, $psono, $created_by) {
    try {
        if (isset($conn) && $conn) {
            @$conn->set_charset("utf8mb4");
        }
        // Check for existing sales order
        $checkSql = "SELECT COUNT(*) FROM krf_sales_order WHERE psono = ?";
        $stmt = $conn->prepare($checkSql);
        $stmt->bind_param('s', $psono);
        $stmt->execute();
        $existingCount = 0;
        $stmt->bind_result($existingCount);
        $stmt->fetch();
        $stmt->close();

        if ($existingCount > 0) {
            // If Sales Order already exists, ensure krf_sales_order_items roundoffamount is updated from krf_provisional_sales_order_items
            $updItemsSql = "UPDATE krf_sales_order_items soi
                INNER JOIN krf_provisional_sales_order_items poi 
                    ON soi.psono = poi.psono 
                    AND (soi.stockorder_no = poi.stockorder_no OR (soi.product_id = poi.product_id AND soi.quantity = poi.quantity))
                SET soi.roundoffamount = poi.roundoffamount
                WHERE soi.psono = ?";
            if ($upStmt = $conn->prepare($updItemsSql)) {
                $upStmt->bind_param('s', $psono);
                $upStmt->execute();
                $upStmt->close();
            }
            return ["status" => "error", "message" => "Sales Order already exists for Provisional Order No: $psono"];
        }

        // Start Transaction
        $conn->begin_transaction();

        // Generate Sales Order Number
        $sono = 'KF-CS-' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

        // Insert into krf_sales_order
        $insertOrderSql = "INSERT INTO krf_sales_order
        (sono, psono, companyid, subcompanyid, projectid, clientid, clientname, emailid, address, delivery_address, districtname, statename, mobileno, payment_terms, remarks, status, created_by, created_at)
        SELECT ?, psono, companyid, subcompanyid, projectid, clientid, clientname, emailid, address, delivery_address, districtname, statename, mobileno, payment_terms, remarks, 'Confirmed', ?, NOW()
        FROM krf_provisional_sales_order
        WHERE psono = ?";

        $stmt = $conn->prepare($insertOrderSql);
        $stmt->bind_param('sss', $sono, $created_by, $psono);
        $stmt->execute();

        if ($stmt->affected_rows == 0) {
            // throw new Exception("Provisional Order not found.");
            return ["status" => "error", "message" => "Provisional Order not found."];
        }
        $stmt->close();

        // Copy items from provisional to final sales order including roundoffamount, isshifted, and shiftingqty (1 bonus plant per 126 tray = CEIL(quantity / 125))
        $insertItemsSql = "INSERT INTO krf_sales_order_items
        (sono, psono, projectid, clientid, product_sku, product_id, product_name, quantity, rate, taxableamount, cgst_percent, cgst_amount, sgst_percent, sgst_amount, igst_percent, igst_amount, discount_percent, discount_amount, amount, roundoffamount, deliverydate, created_by, status, stockorder_no, isshifted, shiftingqty)
        SELECT ?, psono, projectid, clientid, product_sku, product_id, product_name, quantity, rate ,taxableamount, cgst_percent, cgst_amount, sgst_percent, sgst_amount, igst_percent, igst_amount, discount_percent, discount_amount, amount, roundoffamount, expecteddeliverydate, ?, status, stockorder_no, 'yes', CEIL(quantity / 125)
        FROM krf_provisional_sales_order_items
        WHERE psono = ?";

        $stmt = $conn->prepare($insertItemsSql);
        $stmt->bind_param('sss', $sono, $created_by, $psono);
        $stmt->execute();

        if ($stmt->affected_rows == 0) {
            // throw new Exception("No provisional items found for the given psono.");
            return ["status" => "error", "message" => "No provisional items found for the given psono."];
        }
        $stmt->close();

        // Calculate totals
        $totalSql = "SELECT SUM(amount) AS total_amount FROM krf_sales_order_items WHERE sono = ?";
        $stmt = $conn->prepare($totalSql);
        $stmt->bind_param('s', $sono);
        $stmt->execute();
        $total_amount = 0;
        $stmt->bind_result($total_amount);
        $stmt->fetch();
        $stmt->close();

        $tax_rate = 0;
        $tax_amount = round($total_amount * $tax_rate, 2);
        $grand_total = round($total_amount + $tax_amount, 2);

        // Update tax_amount per item
        $updateItemsSql = "UPDATE krf_sales_order_items SET tax_amount = ROUND(amount * ?, 2) WHERE sono = ?";
        $stmt = $conn->prepare($updateItemsSql);
        $stmt->bind_param('ds', $tax_rate, $sono);
        $stmt->execute();
        $stmt->close();

        // Update sales order totals
        $updateOrderSql = "UPDATE krf_sales_order SET total_amount = ?, tax_amount = ?, grand_total = ? WHERE sono = ?";
        $stmt = $conn->prepare($updateOrderSql);
        $stmt->bind_param('ddds', $total_amount, $tax_amount, $grand_total, $sono);
        $stmt->execute();
        $stmt->close();

        $updateProvisionalSql = "UPDATE krf_provisional_sales_order SET status = 'Confirmed' WHERE psono = ?";
        $stmt = $conn->prepare($updateProvisionalSql);
        $stmt->bind_param('s', $psono);
        $stmt->execute();
        $stmt->close();

        $updateProvisionalItemsSql = "UPDATE krf_provisional_sales_order_items SET status = 'Confirmed' WHERE psono = ?";
        $stmt = $conn->prepare($updateProvisionalItemsSql);
        $stmt->bind_param('s', $psono);
        $stmt->execute();
        $stmt->close();

        // --- Link Freight Charges to Final Sales Order ---
        $updateFreightSql = "UPDATE krf_freightcharges SET sono = ? WHERE psono = ?";
        $stmtF = $conn->prepare($updateFreightSql);
        $stmtF->bind_param('ss', $sono, $psono);
        $stmtF->execute();
        $stmtF->close();

        // Mark provisional order as Confirmed
        $updPrdctStockSql = "UPDATE krf_production_stockorder SET status = 'Confirmed' WHERE psono = ?";
        $stmt = $conn->prepare($updPrdctStockSql);
        $stmt->bind_param('s', $psono);
        $stmt->execute();
        $stmt->close();

        // Mark consumed as Confirmed
        $updconsSql = "UPDATE krf_productionorder_consumed SET confirmstatus = 'Confirmed' WHERE psono = ?";
        $stmtc = $conn->prepare($updconsSql);
        $stmtc->bind_param('s', $psono);
        $stmtc->execute();
        $stmtc->close();
        // Commit transaction
        $conn->commit();

        return ["status" => "success", "message"=>"Sale order created successfully", "psono" => $psono, "sono" => $sono];

    } catch (Exception $e) {
        $conn->rollback();
        return ["status" => "error", "message" => $e->getMessage()];
    }
}


function getFinalSalesOrders($conn, $companyid, $subcompanyid, $projectid, $shortby = "all", $shortbyvalue = "all") {
    $data = [];

    try {
        $params = [];
        $types = '';
        $where = " WHERE o.companyid = ? AND o.subcompanyid = ? AND o.projectid = ?";
        $params[] = $companyid;
        $params[] = $subcompanyid;
        $params[] = $projectid;
        $types .= 'iii';

        // Dynamic filtering
        if ($shortby != "all" && $shortbyvalue != "all") {
            switch ($shortby) {
                case "sono":
                    $where .= " AND o.sono = ?";
                    $params[] = $shortbyvalue;
                    $types .= 's';
                    break;

                case "psono":
                    $where .= " AND o.psono = ?";
                    $params[] = $shortbyvalue;
                    $types .= 's';
                    break;

                case "id":
                    $where .= " AND o.id = ?";
                    $params[] = $shortbyvalue;
                    $types .= 'i';
                    break;

                case "status":
                    $where .= " AND o.status = ?";
                    $params[] = $shortbyvalue;
                    $types .= 's';
                    break;

                case "clientname":
                    $where .= " AND o.clientname = ?";
                    $params[] = $shortbyvalue;
                    $types .= 's';
                    break;
                case "mobileno":
                    $where .= " AND o.mobileno = ?";
                    $params[] = $shortbyvalue;
                    $types .= 's';
                    break;
            }
        }

        // Final Query
        $sql = "
            SELECT 
                o.id,
                o.sono,
                o.psono,
                o.companyid,
                o.subcompanyid,
                o.projectid,
                o.clientid,
                o.clientname,
                o.emailid,
                o.delivery_address,
                o.districtname,
                o.statename,
                o.mobileno,
                o.payment_terms,
                o.remarks,
                o.total_amount,
                o.tax_amount,
                o.grand_total,
                o.status,
                o.created_by,
                o.created_at,
                o.updated_at,
                IFNULL(SUM(i.amount), 0) AS items_total_amount
            FROM krf_sales_order o
            LEFT JOIN krf_sales_order_items i ON o.sono = i.sono
            {$where}
            GROUP BY o.id, o.sono
            ORDER BY o.created_at DESC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        $stmt->close();
    } catch (Exception $e) {
        error_log("Error fetching sales orders: " . $e->getMessage());
    }

    return $data;
}

function getConfirmedOrderProducts($conn, $projectid, $shortby = null, $shortbyvalue = null) {
    $sql = "
        SELECT 
            -- Items table fields (filtered)
            i.id AS item_id,
            i.product_sku,
            i.product_id,
            i.product_name,
            i.quantity,
            i.rate,
            i.taxableamount,
            i.cgst_percent,
            i.cgst_amount,
            i.sgst_percent,
            i.sgst_amount,
            i.igst_percent,
            i.igst_amount,
            i.discount_percent,
            i.discount_amount,
            i.amount,
            i.expecteddeliverydate,
            i.created_by AS item_created_by,
            i.status AS item_status,

            -- Orders table fields (filtered)
            o.id AS order_id,
            o.psono,
            o.clientname,
            o.emailid,
            o.delivery_address,
            o.districtname,
            o.statename,
            o.mobileno,
            o.expecteddelivery,
            o.remarks,
            o.status AS order_status,
            o.created_at,
            o.updated_at
        FROM krf_provisional_sales_order_items i
        RIGHT JOIN krf_provisional_sales_order o
            ON i.psono = o.psono
        WHERE o.projectid = ?
          AND o.status = 'Confirmed'
    ";

    $params = [$projectid];
    $types  = "i";

    // Add shortby filter if provided
    if (!empty($shortby) && !empty($shortbyvalue)) {
        $sql .= " AND o.$shortby = ?"; 
        $params[] = $shortbyvalue;
        $types   .= "s";
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("SQL Error: " . $conn->error);
    }

    $stmt->bind_param($types, ...$params);

    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    $stmt->close();
    return $data;
}

/*function GetFinalSalesOrdersWithProductDetails($conn, $projectid, $shortby = null, $shortbyvalue = null) {
    $sql = "
        SELECT 
            -- Items table fields
            i.id AS item_id,
            i.product_sku,
            i.product_id,
            i.product_name,
            i.quantity,
            i.rate,
            i.taxableamount,
            i.cgst_percent,
            i.cgst_amount,
            i.sgst_percent,
            i.sgst_amount,
            i.igst_percent,
            i.igst_amount,
            i.discount_percent,
            i.discount_amount,
            i.amount,
            i.deliverydate,
            i.created_by AS item_created_by,
            i.status AS item_status,

            -- Orders table fields
            o.id AS order_id,
            o.sono,
            o.psono,
            o.clientid,
            o.clientname,
            o.emailid,
            o.address,
            o.delivery_address,
            o.districtname,
            o.statename,
            o.mobileno,
            o.payment_terms,
            o.remarks,
            o.total_amount,
            o.grand_total,
            o.status AS order_status,
            o.created_by AS order_created_by,
            o.created_at,
            o.updated_at,
            p.created_at AS psodate,
            p.pickupfrom
        FROM krf_sales_order_items i
        RIGHT JOIN krf_sales_order o
            ON i.psono = o.psono
        LEFT JOIN krf_provisional_sales_order p
            ON o.psono = p.psono
        WHERE o.projectid = ?";

    $params = [$projectid];
    $types  = "i";

    // Add shortby filter if provided
    if (!empty($shortby) && !empty($shortbyvalue)) {
        $sql .= " AND o.$shortby = ?"; 
        $params[] = $shortbyvalue;
        $types   .= "s";
    }
    $sql .= " ORDER BY i.deliverydate ASC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("SQL Error: " . $conn->error);
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    $stmt->close();
    return $data;
}*/

function GetFinalSalesOrdersWithProductDetails(
    $conn,
    $authconn,
    $projectid,
    $shortby = null,
    $shortbyvalue = null,
    $loginroleid = null,
    $loginid = null
) {
    $data = [];

    try {
        $projectid  = (int)$projectid;
        $loginroleid = (int)$loginroleid;
        $loginid    = trim((string)$loginid);

        if ($projectid <= 0) {
            throw new Exception('Invalid Project ID.');
        }

        /*
         * Main database query.
         */
        $sql = "
            SELECT
                i.id AS item_id,
                i.product_sku,
                i.product_id,
                i.product_name,
                i.quantity,
                i.rate,
                i.taxableamount,
                i.cgst_percent,
                i.cgst_amount,
                i.sgst_percent,
                i.sgst_amount,
                i.igst_percent,
                i.igst_amount,
                i.discount_percent,
                i.discount_amount,
                i.amount,
                i.deliverydate,
                i.created_by AS item_created_by,
                i.status AS item_status,

                o.id AS order_id,
                o.sono,
                o.psono,
                o.clientid,
                o.clientname,
                o.emailid,
                o.address,
                o.delivery_address,
                o.districtname,
                o.statename,
                o.mobileno,
                o.payment_terms,
                o.remarks,
                o.total_amount,
                o.grand_total,
                o.status AS order_status,
                o.created_by AS order_created_by,
                o.updated_by AS order_updated_by,
                o.created_at,
                o.updated_at,

                p.created_at AS psodate,
                p.pickupfrom,
                COALESCE(  item_total.orderamount,  0  ) AS orderamount,
                COALESCE( advance_total.advanceamt,  0 ) AS advanceamt,
                    (  COALESCE(  item_total.orderamount,  0  )  -
                        COALESCE( advance_total.advanceamt,  0  )
                    ) AS balanceamt,
                    o.mobileno

            FROM krf_sales_order AS o
            LEFT JOIN krf_sales_order_items AS i
                ON i.psono = o.psono
            LEFT JOIN krf_provisional_sales_order AS p
                ON p.psono = o.psono

           LEFT JOIN
            ( SELECT  psono,  SUM( COALESCE(amount, 0)  ) AS orderamount
                FROM krf_provisional_sales_order_items
                WHERE LOWER(TRIM(COALESCE(status, ''))) NOT IN ('cancelled', 'cancel', 'remove', 'removed')
                GROUP BY psono
            ) AS item_total
                ON item_total.psono = o.psono
            LEFT JOIN
            (
                SELECT  invoiceno, clientid,  SUM(  COALESCE(advanceamount, 0) ) AS advanceamt
                FROM advance_payment_transactions
                WHERE (tedit IS NULL  OR tedit = '')
                GROUP BY
                    invoiceno,
                    clientid
            ) AS advance_total
                ON advance_total.invoiceno = o.psono
               AND advance_total.clientid = o.clientid

            WHERE o.projectid = ? and  i.quantity>0
        ";

        $params = [$projectid];
        $types  = "i";

        /*
         * Users having role ID greater than 3 can only
         * see orders created by their own login ID.
         */
        // if ($loginroleid > 3) {
        //     if ($loginid === '') {
        //         return [];
        //     }

        //     $sql .= " AND o.created_by = ?";

        //     $types   .= "s";
        //     $params[] = $loginid;
        // }

        /*
         * Allowed filtering columns.
         *
         * Add more columns here when required.
         */
        $allowedFilters = [
            'id'           => ['column' => 'o.id',       'type' => 'i'],
            'order_id'     => ['column' => 'o.id',       'type' => 'i'],
            'sono'         => ['column' => 'o.sono',     'type' => 's'],
            'psono'        => ['column' => 'o.psono',    'type' => 's'],
            'clientid'     => ['column' => 'o.clientid', 'type' => 's'],
            'clientname'   => ['column' => 'o.clientname', 'type' => 's'],
            'mobileno'     => ['column' => 'o.mobileno', 'type' => 's'],
            'status'       => ['column' => 'o.status',    'type' => 's'],
            'order_status' => ['column' => 'o.status',    'type' => 's'],
            'product_id'   => ['column' => 'i.product_id', 'type' => 's'],
            'item_status'  => ['column' => 'i.status',    'type' => 's']
        ];

        if (
            $shortby !== null &&
            $shortby !== '' &&
            $shortbyvalue !== null &&
            $shortbyvalue !== ''
        ) {
            if (!isset($allowedFilters[$shortby])) {
                throw new Exception(
                    'Invalid filter column: ' . $shortby
                );
            }

            $filter = $allowedFilters[$shortby];

            $sql .= " AND {$filter['column']} = ?";

            $types .= $filter['type'];

            $params[] = $filter['type'] === 'i'
                ? (int)$shortbyvalue
                : (string)$shortbyvalue;
        }

        $sql .= "
            ORDER BY
                i.deliverydate ASC,
                o.created_at ASC,
                o.sono ASC
        ";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception(
                'Sales order prepare failed: ' .
                $conn->error
            );
        }

        $stmt->bind_param(
            $types,
            ...$params
        );

        if (!$stmt->execute()) {
            throw new Exception(
                'Sales order query failed: ' .
                $stmt->error
            );
        }

        $result = $stmt->get_result();

        $userLookupIds = [];

        while ($row = $result->fetch_assoc()) {
            $createdBy = trim(
                (string)($row['order_created_by'] ?? '')
            );
            $updatedBy = trim(
                (string)($row['order_updated_by'] ?? '')
            );

            if ($createdBy !== '') {
                $userLookupIds[$createdBy] = $createdBy;
            }
            if ($updatedBy !== '') {
                $userLookupIds[$updatedBy] = $updatedBy;
            }

            /*
             * These will be filled from authconn.
             */
            $row['loginname']      = $createdBy;
            $row['loginroleid']    = '';
            $row['updatedby_name'] = $updatedBy;

            $data[] = $row;
        }

        $stmt->close();

        if (empty($data) || empty($userLookupIds)) {
            return $data;
        }

        /*
         * Fetch login details from authentication database.
         */
        $userLookupIds = array_values($userLookupIds);

        $placeholders = implode(
            ',',
            array_fill(
                0,
                count($userLookupIds),
                '?'
            )
        );

        $authSql = "SELECT loginid, IFNULL(loginname, '') AS loginname, loginroleid FROM login_user_master WHERE loginid IN ($placeholders)";
        $authStmt = $authconn->prepare($authSql);
        if (!$authStmt) {
            throw new Exception(
                'Login query prepare failed: ' .
                $authconn->error
            );
        }

        $authTypes = str_repeat(
            's',
            count($userLookupIds)
        );

        $authStmt->bind_param(
            $authTypes,
            ...$userLookupIds
        );

        if (!$authStmt->execute()) {
            throw new Exception(
                'Login query failed: ' .
                $authStmt->error
            );
        }

        $authResult = $authStmt->get_result();

        $loginUsers = [];

        while ($loginRow = $authResult->fetch_assoc()) {
            $authLoginId = trim(
                (string)$loginRow['loginid']
            );

            $loginUsers[$authLoginId] = [
                'loginname' => $loginRow['loginname'] ?? '',
                'loginroleid' => $loginRow['loginroleid'] ?? ''
            ];
        }

        $authStmt->close();

        /*
         * Merge login details into sales-order data.
         */
        foreach ($data as &$row) {
            $createdBy = trim(
                (string)($row['order_created_by'] ?? '')
            );
            $updatedBy = trim(
                (string)($row['order_updated_by'] ?? '')
            );

            if (isset($loginUsers[$createdBy])) {
                $row['loginname'] =
                    $loginUsers[$createdBy]['loginname'];

                $row['loginroleid'] =
                    $loginUsers[$createdBy]['loginroleid'];
            }
            if (isset($loginUsers[$updatedBy])) {
                $row['updatedby_name'] =
                    $loginUsers[$updatedBy]['loginname'];
            }
        }

        unset($row);

        return $data;

    } catch (Throwable $e) {
        error_log(
            'GetFinalSalesOrdersWithProductDetails error: ' .
            $e->getMessage()
        );

        return [];
    }
}


function getFinalSalesOrderItems($conn, $sono) {
    $items = [];
    try {
        $stmt = $conn->prepare("SELECT * FROM krf_sales_order_items WHERE sono = ? AND LOWER(TRIM(COALESCE(status,''))) NOT IN ('cancelled', 'cancel') AND quantity > 0");
        if (!$stmt) {
            throw new Exception("SQL Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("s", $sono);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }

        $stmt->close();
    } catch (Exception $e) {
        error_log("Error fetching order items: " . $e->getMessage());
        return [];
    }

    return $items;
}


function saveProductionOrderConsumed($conn, $data) {
    try {
        // Extract values (with defaults)
        $id            = $data['id'] ?? 0; // if > 0 => update
        $psono         = $data['psono'] ?? null;
        $sono          = $data['sono'] ?? null;
        $stockorderno  = $data['stockorderno'] ?? null;
        $product_id    = $data['product_id'];
        $product_name  = $data['product_name'];
        $consumed_qty  = $data['consumed_qty'] ?? 0.000;
        $wastage_qty   = $data['wastage_qty'] ?? 0.000;
        $projectid     = $data['projectid'] ?? null;
        $created_by    = $data['created_by'] ?? null;

        if ($id > 0) {
            // --- UPDATE ---
            $sql = "UPDATE krf_productionorder_consumed
                       SET psono = ?, sono = ?, stockorderno = ?,
                           product_id = ?, product_name = ?,
                           consumed_qty = ?, wastage_qty = ?,
                           projectid = ?, created_by = ?
                     WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "sssisdissi",
                $psono, $sono, $stockorderno,
                $product_id, $product_name,
                $consumed_qty, $wastage_qty,
                $projectid, $created_by,
                $id
            );
        } else {
            // --- INSERT ---
            $sql = "INSERT INTO krf_productionorder_consumed
                        (psono, sono, stockorderno,
                         product_id, product_name,
                         consumed_qty, wastage_qty,
                         projectid, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "sssisdiss",
                $psono, $sono, $stockorderno,
                $product_id, $product_name,
                $consumed_qty, $wastage_qty,
                $projectid, $created_by
            );
        }

        if ($stmt->execute()) {
            return [
                "status"  => "success",
                "message" => ($id > 0 ? "Record updated" : "Record inserted"),
                "id"      => ($id > 0 ? $id : $stmt->insert_id)
            ];
        } else {
            return [
                "status"  => "error",
                "message" => $stmt->error
            ];
        }
    } catch (Exception $e) {
        return [
            "status"  => "error",
            "message" => $e->getMessage()
        ];
    }
}

function getProductionOrderConsumed($conn, $filters = []) {
    try {
        $sql = "SELECT id, psono, sono, stockorderno,
                       product_id, product_name,
                       consumed_qty, wastage_qty,
                       projectid, created_by,
                       created_at, updated_at
                FROM krf_productionorder_consumed
                WHERE 1=1 AND IFNULL(tedit, '') = ''";
        $params = [];
        $types  = "";

        // Optional filters
        if (!empty($filters['id'])) {
            $sql .= " AND id = ?";
            $params[] = $filters['id'];
            $types   .= "i";
        }
        if (!empty($filters['psono'])) {
            $sql .= " AND psono = ?";
            $params[] = $filters['psono'];
            $types   .= "s";
        }
        if (!empty($filters['sono'])) {
            $sql .= " AND sono = ?";
            $params[] = $filters['sono'];
            $types   .= "s";
        }
        if (!empty($filters['stockorderno'])) {
            $sql .= " AND stockorderno = ?";
            $params[] = $filters['stockorderno'];
            $types   .= "s";
        }
        if (!empty($filters['product_id'])) {
            $sql .= " AND product_id = ?";
            $params[] = $filters['product_id'];
            $types   .= "i";
        }

        // Optional ordering
        if (!empty($filters['orderby'])) {
            $allowed = ["id","created_at","updated_at","product_id"];
            if (in_array($filters['orderby'], $allowed)) {
                $sql .= " ORDER BY " . $filters['orderby'];
                $sql .= (!empty($filters['ordertype']) && strtoupper($filters['ordertype']) === "DESC") ? " DESC" : " ASC";
            }
        } else {
            $sql .= " ORDER BY id DESC";
        }

        $stmt = $conn->prepare($sql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        return  $rows;

    } catch (Exception $e) {
        return  $e->getMessage();
    }
}

/**
 * Save a single production stock order row into krf_production_stockorder.
 */
// function saveProductionStockOrder($conn, array $data, bool $commit = false): array {
//     try {
//         $stockorder_no          = $data['stockorder_no'] ?? '';
//         $projectid              = (int)($data['projectid'] ?? 0);
//         $companyid              = (int)($data['companyid'] ?? 0);
//         $subcompanyid           = (int)($data['subcompanyid'] ?? 0);
//         $clientid               = $data['clientid'] ?? '';
//         $productid              = $data['productid'] ?? '';
//         $product_name           = $data['product_name'] ?? '';
//         $quantity               = (float)($data['quantity'] ?? 0);
//         $expected_delivery_date = $data['expected_delivery_date'] ?? '';
//         $sowingdate             = $data['sowingdate'] ?? '';
//         $remarks                = $data['remarks'] ?? '';
//         $status                 = $data['status'] ?? '';
//         $created_by             = $data['created_by'] ?? '';

//         if (empty($stockorder_no))
//             return ['status' => 'error', 'message' => 'Stock order number is required.'];
//         if (empty($productid))
//             return ['status' => 'error', 'message' => 'Product ID is required.'];
//         if ($quantity <= 0)
//             return ['status' => 'error', 'message' => 'Quantity must be greater than zero.'];

//         $sql = "INSERT INTO krf_production_stockorder
//                     (stockorder_no, projectid, companyid, subcompanyid, clientid,
//                      productid, product_name, quantity, expected_delivery_date, sowingdate,
//                      remarks, status, created_by, created_at)
//                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

//         $stmt = $conn->prepare($sql);
//         if (!$stmt)
//             return ['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error];

//         $stmt->bind_param(
//             "siiisssdsssss",
//             $stockorder_no, $projectid, $companyid, $subcompanyid,
//             $clientid, $productid, $product_name, $quantity,
//             $expected_delivery_date, $sowingdate, $remarks, $status, $created_by
//         );

//         if (!$stmt->execute())
//             return ['status' => 'error', 'message' => 'Execute failed: ' . $stmt->error];

//         $stmt->close();
//         if ($commit) $conn->commit();

//         return ['status' => 'success', 'message' => 'Saved.', 'stockorder_no' => $stockorder_no];

//     } catch (Exception $e) {
//         return ['status' => 'error', 'message' => $e->getMessage()];
//     }
// }

function saveProductionStockOrder($krfconn, $data, $commit = true)
{
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        if ($commit) $krfconn->begin_transaction();

        // Extract fields
        $id = $data['id'] ?? null;
        $stockorder_no = $data['stockorder_no'] ?? '';
        $projectid = ($data['projectid'] ?? 0);
        $companyid = ($data['companyid'] ?? 0);
        $subcompanyid = ($data['subcompanyid'] ?? 0);
        $clientid = $data['clientid'] ?? null;
        $productid = $data['productid'] ?? null;
        $product_name = $data['product_name'] ?? null;
        $quantity = ($data['quantity'] ?? 0);
        $expected_delivery_date = $data['expected_delivery_date'] ?? null;
        $remarks = $data['remarks'] ?? null;
        $status = $data['status'] ?? 'Draft';
        $created_by = $data['created_by'] ?? null;

        // -- Calculate sowing date and full cycle days --
        $sowingdate = !empty($data['sowingdate']) ? $data['sowingdate'] : null;
        // $scionsowingdate = !empty($data['scionsowingdate']) ? $data['scionsowingdate'] : null;
        $scionsowingdate = '';
        $fullcycledays = 0;

        if ($expected_delivery_date && $productid > 0) {

            $swgdate = getSowingDatesWithSessionCorrection($krfconn, $expected_delivery_date, $projectid, $productid);
            if ($swgdate && is_array($swgdate)) {
                $rootGrowingDays = $swgdate['RootSeedId']['growingdays'] ?? 0;
                $aftergraftingdays = $swgdate['RootSeedId']['aftergraftingdays'] ?? 0;
                $rootSeason = $swgdate['RootSeedId']['season'] ?? '';
                $rootSessionDays = $swgdate['RootSeedId']['sessiondays'] ?? 0;
                $rootIsGrafted = $swgdate['RootSeedId']['isgrafted'] ?? '';
                $rootSowingDate = $swgdate['RootSeedId']['sowingdate'] ?? '';

                if (empty($sowingdate)) {
                    $sowingdate = $rootSowingDate;
                }
                $fullcycledays = ($swgdate['ScionSeedId']['growingdays'] ?? 0) + $aftergraftingdays + $rootSessionDays;

                $scionSeedId = $swgdate['ScionSeedId']['seedid'] ?? '';
                $scionGrowingDays = $swgdate['ScionSeedId']['growingdays'] ?? 0;
                $scionSeason = $swgdate['ScionSeedId']['season'] ?? '';
                $scionSessionDays = $swgdate['ScionSeedId']['sessiondays'] ?? 0;
                $scionIsGrafted = $swgdate['ScionSeedId']['isgrafted'] ?? '';
                if (empty($scionsowingdate)) {
                    $scionsowingdate = $swgdate['ScionSeedId']['sowingdate'] ?? $sowingdate;
                }
            }

            // For NonGrafted products, sowingdate and scionsowingdate must be identical
            $pcd_chk = function_exists('getProductData') ? getProductData($krfconn, $projectid, "ProductID", $productid) : [];
            $isGraft_chk = false;
            if (!empty($pcd_chk) && isset($pcd_chk[0])) {
                $gVal_chk = $pcd_chk[0]['IsGrafted'] ?? 0;
                $isGraft_chk = ($gVal_chk == 1 || strtolower((string)$gVal_chk) === 'yes' || strtolower((string)$gVal_chk) === 'grafted');
            }
            if (!$isGraft_chk) {
                if (!empty($sowingdate)) {
                    $scionsowingdate = $sowingdate;
                } else if (!empty($scionsowingdate)) {
                    $sowingdate = $scionsowingdate;
                }
            }

            // -----------------------
            // VALIDATIONS
            // -----------------------
            if (!empty($sowingdate) && !empty($expected_delivery_date)) {
                $today = strtotime(date("Y-m-d"));
                $swg = strtotime($sowingdate);
                $edd = strtotime($expected_delivery_date);

                // Sowing date cannot be strictly in the past
                if ($swg < $today) {
                    return [
                        "status" => "warning",
                        "message" => "Sowing date ($sowingdate) cannot be in the past."
                    ];
                }

                // Expected delivery date must be AFTER sowing date
                if ($edd <= $swg) {
                    return [
                        "status" => "warning",
                        "message" => "Expected delivery date ($expected_delivery_date) must be later than sowing date ($sowingdate)."
                    ];
                }
            }
        }
        // echo 'expd: '.$expected_delivery_date.', rtd: '.$sowingdate.', scd: '.$scionsowingdate;
        // die();

        // UPDATE
        if ($id) {
            $sql = "UPDATE krf_production_stockorder SET
                        stockorder_no = ?, 
                        projectid = ?, 
                        companyid = ?, 
                        subcompanyid = ?, 
                        clientid = ?, 
                        productid = ?, 
                        product_name = ?, 
                        quantity = ?, 
                        expected_delivery_date = ?, 
                        sowingdate = ?, 
                        scionsowingdate = ?, 
                        remarks = ?, 
                        status = ?, 
                        created_by = ?, 
                        updated_at = NOW()
                    WHERE id = ?";

            $stmt = $krfconn->prepare($sql);
            $stmt->bind_param(
                "sssssssssssssss",
                $stockorder_no,
                $projectid,
                $companyid,
                $subcompanyid,
                $clientid,
                $productid,
                $product_name,
                $quantity,
                $expected_delivery_date,
                $sowingdate,
                $scionsowingdate,
                $remarks,
                $status,
                $created_by,
                $id
            );
        } else {
            // Duplicate check
            $checkSql = "SELECT COUNT(*) FROM krf_production_stockorder 
                         WHERE projectid = ? AND clientid = ? AND productid = ? 
                           AND quantity = ? AND DATE(expected_delivery_date) = ? AND status NOT IN ('Cancelled', 'removed', 'remove', 'delete', 'deleted')";

            $checkStmt = $krfconn->prepare($checkSql);
            $checkStmt->bind_param(
                "sssss",
                $projectid,
                $clientid,
                $productid,
                $quantity,
                $expected_delivery_date
            );
            $checkStmt->execute();
            $checkStmt->bind_result($cnt);
            $checkStmt->fetch();
            $checkStmt->close();

            if ($cnt > 0) {
                $krfconn->rollback();
                return [
                    "status" => "error",
                    "message" => "Duplicate stock order exists!"
                ];
            }
            // sowingdate,sowingstatus
            // scionsowingdate,scionsowingstatus

            $sql = "INSERT INTO krf_production_stockorder 
                    (stockorder_no, projectid, companyid, subcompanyid, clientid, 
                     productid, product_name, quantity, expected_delivery_date, sowingdate, scionsowingdate, 
                     remarks, status, created_by, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

            $stmt = $krfconn->prepare($sql);
            $stmt->bind_param(
                "ssssssssssssss",
                $stockorder_no,
                $projectid,
                $companyid,
                $subcompanyid,
                $clientid,
                $productid,
                $product_name,
                $quantity,
                $expected_delivery_date,
                $sowingdate,
                $scionsowingdate,
                $remarks,
                $status,
                $created_by
            );
        }

        // Execute (Insert or Update)
        $stmt->execute();

        $newId = $id ?: $stmt->insert_id;
        if (empty($id)) {
            createSowingScheduleForNewStockOrder(
                $krfconn,
                $projectid,
                $stockorder_no,
                $productid,
                $product_name,
                $quantity,
                $sowingdate,
                $scionsowingdate,
                $created_by
            );
        }

        if ($commit) $krfconn->commit();
        return [
            "status" => "success",
            "message" => $id ? "Stock Order updated successfully"
                : "Stock Order inserted successfully",
            "id" => $newId,
            "stockorderno"=>$stockorder_no,
            "stockorderdate"=>date('Y-m-d'),
            "stockordeqty"=>$quantity,
            "deliverydate"=>$expected_delivery_date
        ];

    } catch (Exception $e) {
        if ($commit) $krfconn->rollback();
        return [
            "status" => "error",
            "message" => $e->getMessage()
        ];
    }
}

function EditsaveProductionStockOrder($conn, array $data, bool $commit = false): array {
    try {
        $stockorder_no          = $data['stockorder_no'] ?? '';
        $projectid              = (int)($data['projectid'] ?? 0);
        $companyid              = (int)($data['companyid'] ?? 0);
        $subcompanyid           = (int)($data['subcompanyid'] ?? 0);
        $clientid               = $data['clientid'] ?? '';
        $productid              = $data['productid'] ?? '';
        $product_name           = $data['product_name'] ?? '';
        $quantity               = (float)($data['quantity'] ?? 0);
        $expected_delivery_date = $data['expected_delivery_date'] ?? '';
        $sowingdate             = $data['sowingdate'] ?? '';
        $remarks                = $data['remarks'] ?? '';
        $status                 = $data['status'] ?? '';
        $created_by             = $data['created_by'] ?? '';

        if (empty($stockorder_no))
            return ['status' => 'error', 'message' => 'Stock order number is required.'];
        if (empty($productid))
            return ['status' => 'error', 'message' => 'Product ID is required.'];
        if ($quantity <= 0)
            return ['status' => 'error', 'message' => 'Quantity must be greater than zero.'];

        // Step 1: Check for existing record
        $check_sql = "SELECT id FROM krf_production_stockorder WHERE stockorder_no = ? AND projectid = ? AND productid = ? LIMIT 1";
        $stmt_check = $conn->prepare($check_sql);
        $stmt_check->bind_param("sis", $stockorder_no, $projectid, $productid);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();
        $existing_row = $res_check->fetch_assoc();
        $stmt_check->close();

        if ($existing_row) {
            // --- UPDATE existing ---
            $sql = "UPDATE krf_production_stockorder 
                    SET companyid = ?, subcompanyid = ?, clientid = ?, 
                        product_name = ?, quantity = ?, expected_delivery_date = ?, 
                        sowingdate = ?, remarks = ?, status = ?, updated_at = NOW() 
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt)
                return ['status' => 'error', 'message' => 'Update prepare failed: ' . $conn->error];

            $stmt->bind_param(
                "iisssssssi",
                $companyid, $subcompanyid, $clientid, 
                $product_name, $quantity, $expected_delivery_date, 
                $sowingdate, $remarks, $status, $existing_row['id']
            );
        } else {
            // --- INSERT new ---
            $sql = "INSERT INTO krf_production_stockorder
                        (stockorder_no, projectid, companyid, subcompanyid, clientid,
                         productid, product_name, quantity, expected_delivery_date, sowingdate,
                         remarks, status, created_by, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            if (!$stmt)
                return ['status' => 'error', 'message' => 'Insert prepare failed: ' . $conn->error];

            $stmt->bind_param(
                "siiisssdsssss",
                $stockorder_no, $projectid, $companyid, $subcompanyid,
                $clientid, $productid, $product_name, $quantity,
                $expected_delivery_date, $sowingdate, $remarks, $status, $created_by
            );
        }

        if (!$stmt->execute())
            return ['status' => 'error', 'message' => 'Execute failed: ' . $stmt->error];

        $stmt->close();
        if ($commit) $conn->commit();

        return ['status' => 'success', 'message' => ($existing_row ? 'Updated.' : 'Created.'), 'stockorder_no' => $stockorder_no];

    } catch (Exception $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

function saveProductionStockOrderForResowing($krfconn, $data){
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        $krfconn->begin_transaction();

        // Extract fields
        $projectid = ($data['projectid'] ?? 0);
        $companyid = ($data['companyid'] ?? 0);
        $subcompanyid = ($data['subcompanyid'] ?? 0);
        $clientid = $data['clientid'] ?? null;
        $productid = $data['productid'] ?? null;
        $product_name = $data['product_name'] ?? null;
        $roottype = $data['roottype'] ?? null;
        $seedid = $data['seedid'] ?? null;
        $quantity = ($data['quantity'] ?? 0);
        $expected_delivery_date = $data['expected_delivery_date'] ?? null;
        $remarks = $data['remarks'] ?? null;
        $roottype = $data['roottype'] ?? null;
        $seedid = $data['seedid'] ?? null;
        $status = $data['status'] ?? 'Draft';
        $created_by = $data['created_by'] ?? null;
        $resowingagainst_stockorderno = $data['resowingagainst_stockorderno'] ?? null;
        $stockorder_no = $data['newstockorderno'] ?? null;

        // -- Calculate sowing date and full cycle days --
        $sowingdate = null;
        $fullcycledays = 0;

        if ($expected_delivery_date && $productid > 0) {
            $swgdate = getSowingDatesWithSessionCorrection($krfconn, $expected_delivery_date, $projectid, $productid);
            // print_r($swgdate);

            $rootGrowingDays = $swgdate['RootSeedId']['growingdays'];
            $aftergraftingdays = $swgdate['RootSeedId']['aftergraftingdays'];
            $rootSeason = $swgdate['RootSeedId']['season'];
            $rootSessionDays = $swgdate['RootSeedId']['sessiondays'];
            $rootIsGrafted = $swgdate['RootSeedId']['isgrafted'];
            $rootSowingDate = $swgdate['RootSeedId']['sowingdate'];

            $sowingdate = $rootSowingDate;
            $fullcycledays = $swgdate['RootSeedId']['growingdays'] + $aftergraftingdays + $rootSessionDays;

            $scionSeedId = $swgdate['ScionSeedId']['seedid'];
            $scionGrowingDays = $swgdate['ScionSeedId']['growingdays'];
            $scionSeason = $swgdate['ScionSeedId']['season'];
            $scionSessionDays = $swgdate['ScionSeedId']['sessiondays'];
            $scionIsGrafted = $swgdate['ScionSeedId']['isgrafted'];
            $scionsowingdate = $swgdate['ScionSeedId']['sowingdate'];

            // -----------------------
            // VALIDATIONS
            // -----------------------

            // Convert to timestamps for accurate comparison
            $today = strtotime(date("Y-m-d"));
            $swg = strtotime($sowingdate);
            $edd = strtotime($expected_delivery_date);

            // 1?? Sowing date must be > today
            if ($swg <= $today) {
                return [
                    "status" => "warning",
                    "message" => "Sowing date ($sowingdate) must be later than today. This crop requires a full cycle of $fullcycledays days."
                ];
            }

            // 2?? Cycle days must be > 0
            if ($fullcycledays <= 0) {
                return [
                    "status" => "warning",
                    "message" => "Invalid full cycle duration. Total full cycle days calculated is $fullcycledays, which is not valid."
                ];
            }

            // 3?? Expected delivery date must be AFTER sowing date
            if ($edd <= $swg) {
                return [
                    "status" => "warning",
                    "message" => "Expected delivery date must be later than sowing date. Full cycle requires $fullcycledays days between sowing and delivery."
                ];
            }
        }

        // UPDATE
        if ($id) {
            $sql = "UPDATE krf_production_stockorder SET
                        stockorder_no = ?, 
                        projectid = ?, 
                        companyid = ?, 
                        subcompanyid = ?, 
                        clientid = ?, 
                        productid = ?, 
                        product_name = ?, 
                        quantity = ?, 
                        expected_delivery_date = ?, 
                        sowingdate = ?, 
                        scionsowingdate = ?, 
                        remarks = ?, 
                        status = ?, 
                        created_by = ?, 
                        updated_at = NOW()
                    WHERE id = ?";

            $stmt = $krfconn->prepare($sql);
            $stmt->bind_param(
                "sssssssssssssssi",
                $stockorder_no,
                $projectid,
                $companyid,
                $subcompanyid,
                $clientid,
                $productid,
                $product_name,
                $quantity,
                $expected_delivery_date,
                $sowingdate,
                $scionsowingdate,
                $remarks,
                $status,
                $created_by,
                $id
            );
        } else {
            // Check if stock order with same stockorder_no already exists
            $checkSql = "SELECT id, quantity FROM krf_production_stockorder WHERE stockorder_no = ?";
            $checkStmt = $krfconn->prepare($checkSql);
            $checkStmt->bind_param("s", $stockorder_no);
            $checkStmt->execute();
            $checkStmt->store_result();
            
            if ($checkStmt->num_rows > 0) {
                $existingId = null;
                $existingQty = 0;
                $checkStmt->bind_result($existingId, $existingQty);
                $checkStmt->fetch();
                $checkStmt->close();

                // Order already exists - Update quantity and other details
                $newQuantity = $existingQty + $quantity;
                
                $sql = "UPDATE krf_production_stockorder SET
                            quantity = ?, 
                            expected_delivery_date = ?, 
                            sowingdate = ?, 
                            scionsowingdate = ?, 
                            remarks = ?, 
                            status = ?, 
                            created_by = ?, 
                            updated_at = NOW()
                        WHERE id = ?";

                $updStmt = $krfconn->prepare($sql);
                $updStmt->bind_param(
                    "sssssssi",
                    $newQuantity,
                    $expected_delivery_date,
                    $sowingdate,
                    $scionsowingdate,
                    $remarks,
                    $status,
                    $created_by,
                    $existingId
                );
                $updStmt->execute();
                $updStmt->close();

                // Insert into resowing tracking table for record keeping
                $resowSql = "INSERT INTO krf_resowing_production_order (projectid, production_stockorder_id, resowingagainst_stockorderno, newstockorderno, roottype, seedid, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
                $resowStmt = $krfconn->prepare($resowSql);
                if ($resowStmt) {
                    $resowStmt->bind_param('sissss', $projectid, $existingId, $resowingagainst_stockorderno, $stockorder_no, $roottype, $seedid);
                    $resowStmt->execute();
                    $resowStmt->close();
                }

                $krfconn->commit();

                return [
                    "status" => "success",
                    "message" => "Production Order found for $stockorder_no. Quantity updated to $newQuantity.",
                    "id" => $existingId
                ];
            }
            $checkStmt->close();
            // sowingdate,sowingstatus
            // scionsowingdate,scionsowingstatus

            $sql = "INSERT INTO krf_production_stockorder 
                    (stockorder_no, projectid, companyid, subcompanyid, clientid, 
                     productid, product_name, quantity, expected_delivery_date, sowingdate, scionsowingdate, 
                     remarks, status, created_by, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

            $stmt = $krfconn->prepare($sql);
            $stmt->bind_param(
                "ssssssssssssss",
                $stockorder_no,
                $projectid,
                $companyid,
                $subcompanyid,
                $clientid,
                $productid,
                $product_name,
                $quantity,
                $expected_delivery_date,
                $sowingdate,
                $scionsowingdate,
                $remarks,
                $status,
                $created_by
            );
        }

        // Execute (Insert or Update)
        $stmt->execute();

        $newId = $id ?: $stmt->insert_id;
        // Insert into resowing tracking table
        $resowSql = "INSERT INTO krf_resowing_production_order (projectid, production_stockorder_id, resowingagainst_stockorderno, newstockorderno, roottype, seedid, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $resowStmt = $krfconn->prepare($resowSql);
        if ($resowStmt) {
            $resowStmt->bind_param('sissss', $projectid, $newId, $resowingagainst_stockorderno, $stockorder_no, $roottype, $seedid);
            $resowStmt->execute();
            $resowStmt->close();
        }
        $krfconn->commit();

        return [
            "status" => "success",
            "message" => $id ? "Stock Order updated successfully"
                : "Stock Order inserted successfully",
            "id" => $newId
        ];

    } catch (Exception $e) {
        $krfconn->rollback();
        return [
            "status" => "error",
            "message" => $e->getMessage()
        ];
    }
}


function getGerminationFailureSummary($conn, $stockorder_no, $roottype = null)
{
    $data = [];

    // 🔹 Base Query
    $sql = "
        SELECT 
            p.stockorder_no,
            p.roottype,

            -- 🔹 Tray level
            COUNT(p.polyhouseplantid) as total_trays,
            SUM(CASE WHEN (NULLIF(TRIM(p.bed_verify),'') IS NOT NULL AND p.bed_verify != '0000-00-00 00:00:00') OR (NULLIF(TRIM(p.bed_verifyat),'') IS NOT NULL AND p.bed_verifyat != '0000-00-00 00:00:00') THEN 1 ELSE 0 END) as verified_trays,
            SUM(CASE WHEN p.health_status = 'Germination Failure' AND IFNULL(p.failurecount,0) > 0 THEN 1 ELSE 0 END) as failed_trays,
            
            -- 🔹 Plant level
            SUM(CAST(IFNULL(p.qtypertray,0) AS UNSIGNED)) as total_plants,
            SUM(CASE WHEN (NULLIF(TRIM(p.bed_verify),'') IS NOT NULL AND p.bed_verify != '0000-00-00 00:00:00') OR (NULLIF(TRIM(p.bed_verifyat),'') IS NOT NULL AND p.bed_verifyat != '0000-00-00 00:00:00') THEN CAST(IFNULL(p.qtypertray,0) AS UNSIGNED) ELSE 0 END) as germinated_plants,
            SUM(CASE WHEN p.health_status = 'Germination Failure' THEN IFNULL(p.failurecount,0) ELSE 0 END) as failed_plants,

            -- 🔹 Buffer
            IFNULL(sp.failurebufferpercent,0) as failurebufferpercent

        FROM krf_polyhouse_plant_details p

        LEFT JOIN (
            SELECT 
                stockorder_no,
                sowingplantid,
                MAX(IFNULL(failurebufferpercent,0)) as failurebufferpercent
            FROM krf_polyhouse_sowingplant_details
            GROUP BY stockorder_no, sowingplantid
        ) sp ON sp.stockorder_no = p.stockorder_no AND sp.sowingplantid = p.sowingplantid

        WHERE 
            p.stockorder_no = ?
    ";

    $params = [$stockorder_no];
    $types = "s";

    if (!empty($roottype)) {
        $sql .= " AND p.roottype = ? ";
        $params[] = $roottype;
        $types .= "s";
    }

    $sql .= " GROUP BY p.stockorder_no";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();

    if (!$res || $res->num_rows == 0) {
        $stmt->close();
        return [];
    }

    while ($row = $res->fetch_assoc()) {
        $total_trays = (int) $row['total_trays'];
        $verified_trays = (int) $row['verified_trays'];
        $unverified_trays = max(0, $total_trays - $verified_trays);
        $failed_trays = (int) $row['failed_trays'];

        $total_plants = (int) $row['total_plants'];
        $germinated_plants = (int) $row['germinated_plants'];
        $failed_plants = (int) $row['failed_plants'];
        $buffer = (float) $row['failurebufferpercent'];

        // Germination Process Completion %
        $germination_process_percent = $total_trays > 0 ? round(($verified_trays / $total_trays) * 100, 2) : 0.00;

        // 10% Tray Germination Process Complete Rule
        $is_applicable = ($germination_process_percent >= 10.0);

        if ($is_applicable) {
            $tray_failure_percent = $verified_trays > 0 ? round(($failed_trays / $verified_trays) * 100, 2) : 0.00;
            $plant_failure_percent = $germinated_plants > 0 ? round(($failed_plants / $germinated_plants) * 100, 2) : 0.00;
            $resowing_qty = ($failed_plants > 0) ? (int) ceil($failed_plants * (1 + ($buffer / 100))) : 0;
        } else {
            $tray_failure_percent = 0.00;
            $plant_failure_percent = 0.00;
            $resowing_qty = 0;
        }

        $data[] = [
            "stockorder_no"                => $row['stockorder_no'],
            "roottype"                     => $row['roottype'] ?? '',

            // Process Completion
            "total_trays"                  => $total_trays,
            "verified_trays"               => $verified_trays,
            "unverified_trays"             => $unverified_trays,
            "germination_process_percent"  => $germination_process_percent,
            "is_applicable"                => $is_applicable,

            // Tray level
            "failed_trays"                 => $failed_trays,
            "tray_failure_percent"         => $tray_failure_percent,

            // Plant level
            "total_plants"                 => $total_plants,
            "germinated_plants"            => $germinated_plants,
            "failed_plants"                => $failed_plants,
            "failure_percent"              => $plant_failure_percent,
            "plant_failure_percent"        => $plant_failure_percent,

            // Buffer + action
            "failure_buffer"               => $buffer,
            "resowing_qty"                 => $resowing_qty
        ];
    }
    $stmt->close();

    return $data;
}

function getTotalConsumedQtyForPSO($conn, $projectid, $stockorderno, $productid) {
    if (!$stockorderno || !$productid) return 0;
    
    $sql = "SELECT SUM(consumed_qty) as total FROM krf_productionorder_consumed WHERE projectid = ? AND stockorderno = ? AND product_id = ? AND IFNULL(tedit, '') = ''";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isi", $projectid, $stockorderno, $productid);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return $res['total'] ?? 0;
}

/**
 * 🔄 Process Production Orders to Generate Purchase Demand Alerts
 * Logic:
 * 1. Scans confirmed production orders with balance quantity.
 * 2. Identifies Root and Scion seeds required.
 * 3. Inserts/Updates demand rows in 'krf_purchase_demand_alerts'.
 */
/**
 * Process Inventory Alerts & Purchase Orders
 * Sowing schedule seed-wise demand within 7-day target window.
 */
function processInventoryAlertsAndPO($conn, $created_by = 'system') {
    try {
        // Ensure WhatsApp functions are available
        $whatsapp_func_path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'whatsappnotification' . DIRECTORY_SEPARATOR . 'whatsappsmsfunctions.php';
        if (file_exists($whatsapp_func_path)) {
            include_once($whatsapp_func_path);
        }

        $today = date('Y-m-d');
        // Scan target window: Sowing dates from today up to 7 days in the future
        $targetDate = date('Y-m-d', strtotime('+7 days'));

        // Clear existing un-ordered pending alerts to refresh with latest seed-wise consolidated demand
        $conn->query("DELETE FROM inventory_purchase_demand_alerts WHERE status = 'Pending' AND (po_id IS NULL OR po_id = 0)");

        // 1. Fetch upcoming sowing demands from krf_polyhouse_sowingschedule aggregated SEED-WISE
        $sql = "SELECT 
                    ss.projectid,
                    COALESCE(NULLIF(ss.seedid, ''), IF(ss.roottype = 'Scion', pm.ScionSeedId, pm.SeedId)) as resolved_seedid,
                    GROUP_CONCAT(DISTINCT COALESCE(NULLIF(ss.productname, ''), pm.ProductName) ORDER BY ss.productname SEPARATOR ', ') as productnames,
                    GROUP_CONCAT(DISTINCT ss.productid ORDER BY ss.productid SEPARATOR ', ') as productids,
                    MIN(ss.sowing_date) as min_sowing_date,
                    MAX(ss.sowing_date) as max_sowing_date,
                    SUM(GREATEST(0, IFNULL(ss.sowingqty, ss.orderqty * (1 + IFNULL(ss.failurebufferpercent, 30.00)/100)))) as total_sowingqty,
                    SUM(ss.orderqty) as total_orderqty,
                    COUNT(DISTINCT ss.stockorder_no) as order_count,
                    GROUP_CONCAT(DISTINCT ss.stockorder_no ORDER BY ss.stockorder_no SEPARATOR ', ') as stockorder_nos,
                    GROUP_CONCAT(DISTINCT ss.psono ORDER BY ss.psono SEPARATOR ', ') as pso_nos,
                    sm.SeedName,
                    sm.CountPerPackate,
                    sm.Price as seed_price,
                    sm.VendorID,
                    vm.VendorName,
                    IFNULL(inv.current_quantity, 0) as current_stock
                FROM krf_polyhouse_sowingschedule ss
                LEFT JOIN product_master pm ON (ss.productid = pm.ProductID OR ss.productid = pm.sku)
                LEFT JOIN seed_master sm ON (COALESCE(NULLIF(ss.seedid, ''), IF(ss.roottype = 'Scion', pm.ScionSeedId, pm.SeedId)) = sm.SeedID 
                                            AND (sm.ProjectId = ss.projectid OR sm.ProjectId = 0 OR sm.ProjectId IS NULL))
                LEFT JOIN vendor_master vm ON (sm.VendorID = vm.VendorID)
                LEFT JOIN inventory_stock inv ON (COALESCE(NULLIF(ss.seedid, ''), IF(ss.roottype = 'Scion', pm.ScionSeedId, pm.SeedId)) = inv.seed_id)
                WHERE LCASE(IFNULL(ss.status, 'pending')) IN ('pending', 'inprogress', 'in progress', 'edited')
                  AND ss.sowing_date IS NOT NULL 
                  AND ss.sowing_date != '0000-00-00'
                  AND DATE(ss.sowing_date) >= ? 
                  AND DATE(ss.sowing_date) <= ?
                GROUP BY ss.projectid, resolved_seedid
                ORDER BY min_sowing_date ASC, sm.SeedName ASC";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return ["status" => "error", "message" => "Failed to prepare query for sowing schedule: " . $conn->error];
        }

        $stmt->bind_param("ss", $today, $targetDate);
        $stmt->execute();
        $res = $stmt->get_result();

        $scannedSeedsCount = 0;
        $insertedAlertsCount = 0;
        $sufficientStockCount = 0;
        $urgentDemands = [];

        while ($row = $res->fetch_assoc()) {
            $scannedSeedsCount++;
            $projectid = (int)$row['projectid'];
            $seedid = (int)($row['resolved_seedid'] ?? 0);
            $seedname = !empty($row['SeedName']) ? $row['SeedName'] : ('Seed #' . $seedid);
            $productnames = !empty($row['productnames']) ? $row['productnames'] : 'Multiple Products';
            $vendor_id = $row['VendorID'] ?? '';
            $unit_price = (float)($row['seed_price'] ?? 0);
            $total_sowingqty = (float)$row['total_sowingqty'];
            $currentStock = (float)($row['current_stock'] ?? 0);
            $countPerPacket = (float)($row['CountPerPackate'] ?: 1);
            $packetsNeeded = (int)ceil($total_sowingqty / max(1.0, $countPerPacket));
            $target_sowing_date = $row['min_sowing_date'];
            $stockorder_nos = substr($row['stockorder_nos'] ?? '', 0, 100);
            $order_count = (int)$row['order_count'];
            $order_date = $today;

            if (empty($seedid) || $total_sowingqty <= 0) {
                continue;
            }

            // Check if inventory stock is already sufficient
            if ($currentStock >= $total_sowingqty) {
                $sufficientStockCount++;
                continue;
            }

            // Insert or Update Demand Alert for this seed
            $insertSql = "INSERT INTO inventory_purchase_demand_alerts 
                            (projectid, stockorder_no, productname, seedid, seedname, vendor_id, required_qty, packets_needed, unit_price, order_date, sowing_date, status, remarks)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?)
                          ON DUPLICATE KEY UPDATE 
                            productname = VALUES(productname),
                            seedname = VALUES(seedname),
                            vendor_id = VALUES(vendor_id),
                            required_qty = VALUES(required_qty),
                            packets_needed = VALUES(packets_needed),
                            unit_price = VALUES(unit_price),
                            order_date = VALUES(order_date),
                            sowing_date = VALUES(sowing_date),
                            status = 'Pending',
                            remarks = VALUES(remarks)";
            
            $istmt = $conn->prepare($insertSql);
            if ($istmt) {
                $remarks = "Seed-wise requirement for $order_count order(s): " . ($row['stockorder_nos'] ?? '');
                $istmt->bind_param("isssssdddsss", 
                    $projectid, 
                    $stockorder_nos, 
                    $productnames, 
                    $seedid, 
                    $seedname, 
                    $vendor_id,
                    $total_sowingqty, 
                    $packetsNeeded, 
                    $unit_price,
                    $order_date, 
                    $target_sowing_date,
                    $remarks
                );
                
                if ($istmt->execute()) {
                    $insertedAlertsCount++;
                    $urgentDemands[] = [
                        'stockorder_no' => $stockorder_nos,
                        'productname' => $productnames,
                        'seedname' => $seedname,
                        'required_qty' => $total_sowingqty,
                        'packets_needed' => $packetsNeeded,
                        'sowing_date' => $target_sowing_date
                    ];
                }
                $istmt->close();
            }
        }
        $stmt->close();

        // Send WhatsApp Notification if urgent demands exist
        if (!empty($urgentDemands) && function_exists('sendPurchaseDemandWhatsApp')) {
            sendPurchaseDemandWhatsApp($conn, $urgentDemands);
        }

        // Return accurate status and message
        if ($scannedSeedsCount === 0) {
            return [
                "status" => "info",
                "message" => "No sowing schedules found within the 7-day target window ($today to $targetDate).",
                "scanned_seeds" => 0,
                "inserted_alerts" => 0,
                "sufficient_stock" => 0
            ];
        }

        if ($insertedAlertsCount > 0) {
            return [
                "status" => "success",
                "message" => "Successfully processed $insertedAlertsCount seed-wise purchase demand alert(s) across $scannedSeedsCount seed(s) for the next 7 days.",
                "scanned_seeds" => $scannedSeedsCount,
                "inserted_alerts" => $insertedAlertsCount,
                "sufficient_stock" => $sufficientStockCount
            ];
        } else {
            return [
                "status" => "info",
                "message" => "Scanned $scannedSeedsCount seed(s). Current inventory stock is sufficient ($sufficientStockCount covered by stock).",
                "scanned_seeds" => $scannedSeedsCount,
                "inserted_alerts" => 0,
                "sufficient_stock" => $sufficientStockCount
            ];
        }
    } catch (Exception $e) {
        return ["status" => "error", "message" => "Error processing inventory alerts: " . $e->getMessage()];
    }
}

function createSowingScheduleForNewStockOrder($krfconn, $projectid, $stockorder_no, $productid, $product_name, $quantity, $sowingdate = null, $scionsowingdate = null, $created_by = null, $locationid = null, $trayid = null, $roottype_filter = null, $psono = null)
{
    if (empty($stockorder_no) || empty($productid) || $quantity <= 0) {
        return;
    }

    try {
        // 1. Fetch seed details from product_master using getProductData
        $rootSeedId = '';
        $scionSeedId = '';
        $isGrafted = false;
        $rootTrayId = !empty($trayid) ? (int)$trayid : null;
        $scionTrayId = !empty($trayid) ? (int)$trayid : null;
        $rootTrayCavity = 126;
        $scionTrayCavity = 126;

        if (function_exists('getProductData')) {
            $pcd = getProductData($krfconn, $projectid, "ProductID", $productid);
            if (!empty($pcd) && isset($pcd[0])) {
                $pRow = $pcd[0];
                $rootSeedId = !empty($pRow['SeedId']) ? trim($pRow['SeedId']) : '';
                $scionSeedId = !empty($pRow['ScionSeedId']) ? trim($pRow['ScionSeedId']) : $rootSeedId;
                $gVal = $pRow['IsGrafted'] ?? 0;
                $isGrafted = ($gVal == 1 || strtolower((string)$gVal) === 'yes' || strtolower((string)$gVal) === 'grafted');

                if (empty($rootTrayId) && !empty($pRow['RootTrayId'])) $rootTrayId = (int)$pRow['RootTrayId'];
                if (empty($scionTrayId) && !empty($pRow['ScionTrayId'])) $scionTrayId = (int)$pRow['ScionTrayId'];
                if (empty($rootTrayId) && !empty($pRow['TrayId'])) $rootTrayId = (int)$pRow['TrayId'];
            }
        }

        // Direct fallback query if getProductData returned empty
        if (empty($rootSeedId) && empty($scionSeedId)) {
            $stmt_pm = $krfconn->prepare("SELECT ProductID, ProductName, SeedId, ScionSeedId, IsGrafted FROM product_master WHERE (ProductID = ? OR sku = ?) LIMIT 1");
            if ($stmt_pm) {
                $stmt_pm->bind_param("ss", $productid, $productid);
                $stmt_pm->execute();
                $pm = $stmt_pm->get_result()->fetch_assoc();
                $stmt_pm->close();

                if ($pm) {
                    $rootSeedId = !empty($pm['SeedId']) ? trim($pm['SeedId']) : '';
                    $scionSeedId = !empty($pm['ScionSeedId']) ? trim($pm['ScionSeedId']) : $rootSeedId;
                    $gVal = $pm['IsGrafted'] ?? 0;
                    $isGrafted = ($gVal == 1 || strtolower((string)$gVal) === 'yes' || strtolower((string)$gVal) === 'grafted');
                }
            }
        }

        // 2. Setup dates - strictly use the actual dates mentioned in krf_production_stockorder
        $todayStr = date('Y-m-d');
        if (empty($sowingdate) || $sowingdate === '0000-00-00') {
            $sowingdate = $todayStr;
        }
        if (empty($scionsowingdate) || $scionsowingdate === '0000-00-00') {
            $scionsowingdate = $sowingdate;
        }

        // 3. Quantity math with 30% germination buffer
        $orderqty = (int)$quantity;
        $bufferPercent = 30.00;
        $sowingqty = (int)ceil($orderqty * 1.30);
        $status = 'pending';
        $remarks = 'Created with new production stock order';
        $effective_psono = !empty($psono) ? $psono : $stockorder_no;

        $ins_sql = "INSERT INTO krf_polyhouse_sowingschedule 
                    (projectid, stockorder_no, psono, productid, productname, sowing_date, orderqty, failurebufferpercent, sowingqty, locationid, roottype, seedid, trayid, traycavity, status, remarks, created_at, created_by, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, NOW())";

        $stmt_ins = $krfconn->prepare($ins_sql);
        if ($stmt_ins) {
            if (!empty($roottype_filter)) {
                // Specific roottype single entry creation
                $rt_str = (strtolower($roottype_filter) === 'scion') ? 'Scion' : 'Root';
                $s_id = ($rt_str === 'Scion') ? (!empty($scionSeedId) ? $scionSeedId : $rootSeedId) : (!empty($rootSeedId) ? $rootSeedId : $scionSeedId);
                $t_id = ($rt_str === 'Scion') ? $scionTrayId : $rootTrayId;
                $t_cav = ($rt_str === 'Scion') ? $scionTrayCavity : $rootTrayCavity;
                $s_date = ($rt_str === 'Scion') ? $scionsowingdate : $sowingdate;

                $stmt_ins->bind_param("ssssssidisssiisss", 
                    $projectid, $stockorder_no, $effective_psono, $productid, $product_name, 
                    $s_date, $orderqty, $bufferPercent, $sowingqty, $locationid,
                    $rt_str, $s_id, $t_id, $t_cav, 
                    $status, $remarks, $created_by
                );
                $stmt_ins->execute();
            } else if ($isGrafted) {
                // Grafted Product -> Add Root AND Scion rows
                
                // 1) Root Row
                $roottype_root = 'Root';
                $seed_root = !empty($rootSeedId) ? $rootSeedId : $scionSeedId;
                $stmt_ins->bind_param("ssssssidisssiisss", 
                    $projectid, $stockorder_no, $effective_psono, $productid, $product_name, 
                    $sowingdate, $orderqty, $bufferPercent, $sowingqty, $locationid,
                    $roottype_root, $seed_root, $rootTrayId, $rootTrayCavity, 
                    $status, $remarks, $created_by
                );
                $stmt_ins->execute();

                // 2) Scion Row
                $roottype_scion = 'Scion';
                $seed_scion = !empty($scionSeedId) ? $scionSeedId : $rootSeedId;
                $stmt_ins->bind_param("ssssssidisssiisss", 
                    $projectid, $stockorder_no, $effective_psono, $productid, $product_name, 
                    $scionsowingdate, $orderqty, $bufferPercent, $sowingqty, $locationid,
                    $roottype_scion, $seed_scion, $scionTrayId, $scionTrayCavity, 
                    $status, $remarks, $created_by
                );
                $stmt_ins->execute();
            } else {
                // Non-Grafted Product -> roottype is Scion (not Root), sowingdate and scionsowingdate same, seedid and scionseedid same
                $roottype_nongrafted = 'Scion';
                $seed_nongrafted = !empty($scionSeedId) ? $scionSeedId : $rootSeedId;
                $eff_trayid = !empty($scionTrayId) ? $scionTrayId : $rootTrayId;
                $eff_cavity = !empty($scionTrayCavity) ? $scionTrayCavity : $rootTrayCavity;
                $eff_sowingdate = !empty($sowingdate) ? $sowingdate : $scionsowingdate;

                $stmt_ins->bind_param("ssssssidisssiisss", 
                    $projectid, $stockorder_no, $effective_psono, $productid, $product_name, 
                    $eff_sowingdate, $orderqty, $bufferPercent, $sowingqty, $locationid,
                    $roottype_nongrafted, $seed_nongrafted, $eff_trayid, $eff_cavity, 
                    $status, $remarks, $created_by
                );
                $stmt_ins->execute();
            }
            $stmt_ins->close();
        }
    } catch (Exception $e) {
        error_log("Error in createSowingScheduleForNewStockOrder: " . $e->getMessage());
    }
}

/**
 * Auto-cancel draft provisional orders that have remained in Draft status for more than 48 hours.
 * Sets status to 'Cancelled' and appends 'auto canceled' in remarks across all associated tables.
 *
 * @param mysqli $krfconn
 * @param string|null $singlePsono Optional specific psono to auto-cancel if expired
 * @return array ['cancelled_count' => int, 'psono_list' => array]
 */
function autoCancelExpiredDraftOrders($krfconn, $singlePsono = null) {
    if (!$krfconn) {
        return ['cancelled_count' => 0, 'psono_list' => []];
    }

    $whereExtra = "";
    if (!empty($singlePsono)) {
        $esc = mysqli_real_escape_string($krfconn, trim($singlePsono));
        $whereExtra = " AND psono = '{$esc}'";
    }

    // 1. Identify all Draft orders older than 48 hours
    $query = "
        SELECT psono 
        FROM krf_provisional_sales_order 
        WHERE status = 'Draft' 
          AND created_at <= (NOW() - INTERVAL 48 HOUR)
          {$whereExtra}
    ";

    $res = $krfconn->query($query);
    if (!$res || $res->num_rows === 0) {
        return ['cancelled_count' => 0, 'psono_list' => []];
    }

    $expiredPsos = [];
    while ($row = $res->fetch_assoc()) {
        $p = trim($row['psono'] ?? '');
        if (!empty($p)) {
            $expiredPsos[] = $p;
        }
    }

    if (empty($expiredPsos)) {
        return ['cancelled_count' => 0, 'psono_list' => []];
    }

    $curdate = date('Y-m-d');
    $now = date('Y-m-d H:i:s');
    $userForUpdate = 'System Auto Cancel';
    $remarkText = 'auto canceled';
    $teditText = "Auto Cancelled on " . $now . " - 48h draft expired";

    // Process in batches of 100 to prevent oversized SQL queries
    $batches = array_chunk($expiredPsos, 100);
    foreach ($batches as $batch) {
        $escaped = array_map(function($p) use ($krfconn) {
            return "'" . mysqli_real_escape_string($krfconn, $p) . "'";
        }, $batch);
        $inClause = implode(',', $escaped);

        // 1. Update krf_provisional_sales_order
        $krfconn->query("
            UPDATE krf_provisional_sales_order 
            SET status = 'Cancelled', 
                remarks = IF(remarks IS NULL OR remarks = '', '{$remarkText}', CONCAT(remarks, '\n', '{$remarkText}')), 
                updated_by = '{$userForUpdate}', 
                updated_at = NOW() 
            WHERE psono IN ({$inClause}) AND status = 'Draft'
        ");

        // 2. Update krf_provisional_sales_order_items
        $krfconn->query("
            UPDATE krf_provisional_sales_order_items 
            SET status = 'Cancelled', 
                tedit = '{$teditText}', 
                remark = IF(remark IS NULL OR remark = '', '{$remarkText}', CONCAT(remark, ' | ', '{$remarkText}')), 
                updatedat = '{$curdate}', 
                updatedby = '{$userForUpdate}' 
            WHERE psono IN ({$inClause}) AND (status IS NULL OR status = '' OR status = 'Draft' OR status != 'Cancelled')
        ");

        // 3. Update krf_productionorder_consumed to cancel stock/tray reservations
        $krfconn->query("
            UPDATE krf_productionorder_consumed 
            SET confirmstatus = 'cancelled', 
                status = 'Cancelled', 
                remark = IF(remark IS NULL OR remark = '', '{$remarkText}', CONCAT(remark, ' | ', '{$remarkText}')), 
                updated_at = NOW() 
            WHERE psono IN ({$inClause}) AND (confirmstatus IS NULL OR confirmstatus != 'cancelled')
        ");

        // 4. Update krf_production_stockorder
        $krfconn->query("
            UPDATE krf_production_stockorder 
            SET status = 'Cancelled', 
                workingstatus = 'Cancelled', 
                editedremark = IF(editedremark IS NULL OR editedremark = '', '{$remarkText}', CONCAT(editedremark, ' | ', '{$remarkText}')), 
                editedby = '{$userForUpdate}', 
                editedon = '{$curdate}', 
                updated_at = NOW() 
            WHERE psono IN ({$inClause}) AND (status IS NULL OR status != 'Cancelled')
        ");

        // 5. Update krf_sales_order (if any provisional draft has legacy entry)
        $krfconn->query("
            UPDATE krf_sales_order 
            SET status = 'Cancelled', 
                remarks = IF(remarks IS NULL OR remarks = '', '{$remarkText}', CONCAT(remarks, '\n', '{$remarkText}')), 
                updated_by = '{$userForUpdate}', 
                updated_at = NOW() 
            WHERE psono IN ({$inClause}) AND (status IS NULL OR status != 'Cancelled')
        ");

        // 6. Update krf_sales_order_items
        $krfconn->query("
            UPDATE krf_sales_order_items 
            SET status = 'Cancelled', 
                remark = IF(remark IS NULL OR remark = '', '{$remarkText}', CONCAT(remark, ' | ', '{$remarkText}')), 
                ammendedby = '{$userForUpdate}', 
                updateamended = NOW() 
            WHERE psono IN ({$inClause}) AND (status IS NULL OR status != 'Cancelled')
        ");

        // 7. Update krf_polyhouse_sowingschedule
        $krfconn->query("
            UPDATE krf_polyhouse_sowingschedule 
            SET status = 'removed', 
                remarks = IF(remarks IS NULL OR remarks = '', '{$remarkText}', CONCAT(remarks, ' | ', '{$remarkText}')), 
                updated_by = '{$userForUpdate}', 
                updated_at = NOW() 
            WHERE psono IN ({$inClause}) AND (status IS NULL OR status != 'removed')
        ");
    }

    return ['cancelled_count' => count($expiredPsos), 'psono_list' => $expiredPsos];
}

