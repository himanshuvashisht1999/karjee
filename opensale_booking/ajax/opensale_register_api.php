<?php
/**
 * Open Sale Tray Register & Return Management API
 * Path: /opensale_booking/ajax/opensale_register_api.php
 * Handles querying Open Sale trays, metrics, polyhouses & beds,
 * and executing tray returns back to Polyhouse.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Kolkata');
header('Content-Type: application/json; charset=utf-8');

// Authentication Check
if (!isset($_SESSION['sessionid']) || $_SESSION['sessionid'] !== session_id()) {
    echo json_encode(['status' => 'error', 'message' => 'Session expired or invalid. Please re-login.']);
    exit;
}

require_once(__DIR__ . '/../../connection.php');

if (!isset($krfconn) || !$krfconn) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
    exit;
}

$action = trim($_REQUEST['action'] ?? $_GET['action'] ?? $_POST['action'] ?? '');
$loginroleid = isset($_SESSION['loginroleid']) ? (int)$_SESSION['loginroleid'] : 4;
$loginname   = $_SESSION['loginname'] ?? $_SESSION['username'] ?? 'User';
$projectid   = isset($_SESSION['projectid']) && (int)$_SESSION['projectid'] > 0 ? (int)$_SESSION['projectid'] : 17;

function outJson($data) {
    echo json_encode($data);
    exit;
}

// -------------------------------------------------------------
// 1. ACTION: GET KPI METRICS
// -------------------------------------------------------------
if ($action === 'get_kpis') {
    $where = ["1=1"];
    if (!empty($_REQUEST['location'])) {
        $lEsc = $krfconn->real_escape_string(trim($_REQUEST['location']));
        $where[] = "(s.open_sale_location = '{$lEsc}' OR s.opensale_locationid = '{$lEsc}' OR s.open_sale_location LIKE '%{$lEsc}%' OR '{$lEsc}' LIKE CONCAT('%', s.open_sale_location, '%'))";
    }
    if (!empty($_REQUEST['date_from'])) {
        $df = $krfconn->real_escape_string(trim($_REQUEST['date_from']));
        $where[] = "DATE(s.created_at) >= '{$df}'";
    }
    if (!empty($_REQUEST['date_to'])) {
        $dt = $krfconn->real_escape_string(trim($_REQUEST['date_to']));
        $where[] = "DATE(s.created_at) <= '{$dt}'";
    }

    $whereSql = implode(" AND ", $where);

    $sql = "
        SELECT 
            COUNT(*) as total_moved_trays,
            COALESCE(SUM(s.qtypertray), 0) as total_moved_plants,
            
            -- Active in Open Sale
            COUNT(CASE WHEN s.status = 'OpenSale' THEN 1 END) as active_trays,
            COALESCE(SUM(CASE WHEN s.status = 'OpenSale' THEN s.actual_plant_count ELSE 0 END), 0) as active_plants,
            
            -- Returned back to Polyhouse
            COUNT(CASE WHEN s.status = 'Returned' THEN 1 END) as returned_trays,
            COALESCE(SUM(CASE WHEN s.status = 'Returned' THEN s.actual_plant_count ELSE 0 END), 0) as returned_plants,
            
            -- Sold via Open Sale POS
            COUNT(CASE WHEN s.status = 'Sold' THEN 1 END) as sold_trays,
            COALESCE(SUM(s.sold_qty), 0) as sold_plants,
            
            -- Open Sale Center Counts
            COUNT(CASE WHEN s.status = 'OpenSale' AND (s.open_sale_location = 'Karjee' OR s.opensale_locationid = 'LOC6905e0fd5ab05' OR s.remarks LIKE '%Open Sale Karjee%') THEN 1 END) as karjee_trays,
            COALESCE(SUM(CASE WHEN s.status = 'OpenSale' AND (s.open_sale_location = 'Karjee' OR s.opensale_locationid = 'LOC6905e0fd5ab05' OR s.remarks LIKE '%Open Sale Karjee%') THEN s.actual_plant_count ELSE 0 END), 0) as karjee_plants,
            
            COUNT(CASE WHEN s.status = 'OpenSale' AND (s.open_sale_location = 'Sargawa' OR s.opensale_locationid = 'LOC696b280faf104' OR s.remarks LIKE '%Open Sale Sargwan%' OR s.remarks LIKE '%Sargawa%') THEN 1 END) as sargawa_trays,
            COALESCE(SUM(CASE WHEN s.status = 'OpenSale' AND (s.open_sale_location = 'Sargawa' OR s.opensale_locationid = 'LOC696b280faf104' OR s.remarks LIKE '%Open Sale Sargwan%' OR s.remarks LIKE '%Sargawa%') THEN s.actual_plant_count ELSE 0 END), 0) as sargawa_plants,
            
            COUNT(CASE WHEN s.status = 'OpenSale' AND (s.open_sale_location = 'Mansoon' OR s.opensale_locationid = 'LOC6a4f396f08173' OR s.remarks LIKE '%Mansoon%') THEN 1 END) as mansoon_trays,
            COALESCE(SUM(CASE WHEN s.status = 'OpenSale' AND (s.open_sale_location = 'Mansoon' OR s.opensale_locationid = 'LOC6a4f396f08173' OR s.remarks LIKE '%Mansoon%') THEN s.actual_plant_count ELSE 0 END), 0) as mansoon_plants,

            -- Eligible for return (2-3 days old and still in OpenSale)
            COUNT(CASE WHEN s.status = 'OpenSale' AND DATEDIFF(NOW(), s.created_at) >= 2 AND DATEDIFF(NOW(), s.created_at) <= 3 THEN 1 END) as return_review_trays,
            COUNT(CASE WHEN s.status = 'OpenSale' AND DATEDIFF(NOW(), s.created_at) > 3 THEN 1 END) as overdue_trays
        FROM krf_opensale_stocks s
        WHERE {$whereSql}
    ";

    $res = $krfconn->query($sql);
    $data = [
        'total_moved_trays'   => 0,
        'total_moved_plants'  => 0,
        'active_trays'        => 0,
        'active_plants'       => 0,
        'returned_trays'      => 0,
        'returned_plants'     => 0,
        'sold_trays'          => 0,
        'sold_plants'         => 0,
        'karjee_trays'        => 0,
        'karjee_plants'       => 0,
        'sargawa_trays'       => 0,
        'sargawa_plants'      => 0,
        'mansoon_trays'       => 0,
        'mansoon_plants'      => 0,
        'return_review_trays' => 0,
        'overdue_trays'       => 0
    ];

    if ($res && $r = $res->fetch_assoc()) {
        foreach ($data as $k => $v) {
            $data[$k] = (int)($r[$k] ?? 0);
        }
    }

    outJson(['status' => 'success', 'data' => $data]);
}

// -------------------------------------------------------------
// 2. ACTION: GET REGISTER TRAYS LIST
// -------------------------------------------------------------
if ($action === 'get_trays') {
    $where = ["1=1"];

    // Search query
    $search = trim($_REQUEST['search'] ?? '');
    if (!empty($search)) {
        $sEsc = $krfconn->real_escape_string($search);
        $where[] = "(
            s.barcode LIKE '%{$sEsc}%' 
            OR s.productname LIKE '%{$sEsc}%' 
            OR s.variety LIKE '%{$sEsc}%' 
            OR s.stockorder_no LIKE '%{$sEsc}%' 
            OR s.psono LIKE '%{$sEsc}%' 
            OR s.from_bed_no LIKE '%{$sEsc}%' 
            OR s.from_polyhousename LIKE '%{$sEsc}%'
            OR s.created_by LIKE '%{$sEsc}%'
        )";
    }

    // Status filter
    $status = trim($_REQUEST['status'] ?? 'OpenSale');
    if ($status === 'OpenSale') {
        $where[] = "s.status = 'OpenSale'";
    } elseif ($status === 'Returned') {
        $where[] = "s.status = 'Returned'";
    } elseif ($status === 'Sold') {
        $where[] = "s.status = 'Sold'";
    } elseif ($status === 'All') {
        // All statuses
    } else {
        $where[] = "s.status = '" . $krfconn->real_escape_string($status) . "'";
    }

    // Location / Center filter
    $location = trim($_REQUEST['location'] ?? '');
    if (!empty($location)) {
        $lEsc = $krfconn->real_escape_string($location);
        $where[] = "(
            s.open_sale_location = '{$lEsc}' 
            OR s.opensale_locationid = '{$lEsc}' 
            OR s.open_sale_location LIKE '%{$lEsc}%' 
            OR s.remarks LIKE '%{$lEsc}%'
        )";
    }

    // Days in Open Sale Filter
    $daysFilter = trim($_REQUEST['days_filter'] ?? '');
    if ($daysFilter === 'today') {
        $where[] = "DATEDIFF(NOW(), s.created_at) = 0";
    } elseif ($daysFilter === '1_day') {
        $where[] = "DATEDIFF(NOW(), s.created_at) = 1";
    } elseif ($daysFilter === '2_3_days') {
        $where[] = "(DATEDIFF(NOW(), s.created_at) >= 2 AND DATEDIFF(NOW(), s.created_at) <= 3)";
    } elseif ($daysFilter === 'gt_3_days') {
        $where[] = "DATEDIFF(NOW(), s.created_at) > 3";
    }

    // Date range
    if (!empty($_REQUEST['date_from'])) {
        $df = $krfconn->real_escape_string(trim($_REQUEST['date_from']));
        $where[] = "DATE(s.created_at) >= '{$df}'";
    }
    if (!empty($_REQUEST['date_to'])) {
        $dt = $krfconn->real_escape_string(trim($_REQUEST['date_to']));
        $where[] = "DATE(s.created_at) <= '{$dt}'";
    }

    $whereSql = implode(" AND ", $where);

    // Total Count
    $totalCount = 0;
    $countRes = $krfconn->query("SELECT COUNT(*) as cnt FROM krf_opensale_stocks s WHERE {$whereSql}");
    if ($countRes && $crow = $countRes->fetch_assoc()) {
        $totalCount = (int)$crow['cnt'];
    }

    // Pagination
    $page = max(1, (int)($_REQUEST['page'] ?? 1));
    $limit = max(10, min(200, (int)($_REQUEST['limit'] ?? 50)));
    $offset = ($page - 1) * $limit;

    $sql = "
        SELECT 
            s.*,
            DATEDIFF(NOW(), s.created_at) as days_in_opensale,
            p.status as current_plant_status,
            p.polyhouseid as current_plant_polyhouseid,
            p.bed_no as current_plant_bed_no,
            pm.polyhousename as current_polyhousename
        FROM krf_opensale_stocks s
        LEFT JOIN krf_polyhouse_plant_details p ON s.polyhouseplantid = p.polyhouseplantid
        LEFT JOIN krf_polyhouse_master pm ON p.polyhouseid = pm.polyhouseid
        WHERE {$whereSql}
        ORDER BY s.id DESC
        LIMIT {$limit} OFFSET {$offset}
    ";

    $res = $krfconn->query($sql);
    $items = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            // Normalize Open Sale Center Name
            $centerName = $row['open_sale_location'];
            if (empty($centerName) || $centerName === 'Sargawa') {
                if ($row['opensale_locationid'] === 'LOC696b280faf104' || stripos($row['remarks'], 'Sargwan') !== false || stripos($row['remarks'], 'Sargawa') !== false) {
                    $centerName = 'Open Sale Sargwan';
                } elseif ($row['opensale_locationid'] === 'LOC6905e0fd5ab05' || stripos($row['remarks'], 'Karjee') !== false) {
                    $centerName = 'Open Sale Karjee';
                } elseif ($row['opensale_locationid'] === 'LOC6a4f396f08173' || stripos($row['remarks'], 'Mansoon') !== false) {
                    $centerName = 'Open Sale Mansoon Agro';
                } else {
                    $centerName = !empty($row['open_sale_location']) ? ('Open Sale ' . $row['open_sale_location']) : 'Open Sale';
                }
            } else {
                if (stripos($centerName, 'Open Sale') === false) {
                    $centerName = 'Open Sale ' . $centerName;
                }
            }
            $row['resolved_center_name'] = $centerName;

            // Formatted Date
            $row['formatted_created_at'] = !empty($row['created_at']) ? date('d-M-Y h:i A', strtotime($row['created_at'])) : '-';
            $row['formatted_updated_at'] = !empty($row['updated_at']) ? date('d-M-Y h:i A', strtotime($row['updated_at'])) : '-';
            
            $days = (int)($row['days_in_opensale'] ?? 0);
            if ($days === 0) {
                $row['age_label'] = 'Today (0d)';
                $row['age_badge_class'] = 'badge-age-fresh';
            } elseif ($days === 1) {
                $row['age_label'] = '1 Day Ago';
                $row['age_badge_class'] = 'badge-age-fresh';
            } elseif ($days >= 2 && $days <= 3) {
                $row['age_label'] = "{$days} Days (Due)";
                $row['age_badge_class'] = 'badge-age-due';
            } else {
                $row['age_label'] = "{$days} Days";
                $row['age_badge_class'] = 'badge-age-overdue';
            }

            $items[] = $row;
        }
    }

    outJson([
        'status'       => 'success',
        'data'         => $items,
        'total_count'  => $totalCount,
        'page'         => $page,
        'limit'        => $limit,
        'total_pages'  => ceil($totalCount / $limit)
    ]);
}

// -------------------------------------------------------------
// 3. ACTION: GET ACTIVE POLYHOUSES LIST (EXCLUDING OPENSALE)
// -------------------------------------------------------------
if ($action === 'get_polyhouses') {
    $list = [];
    $res = $krfconn->query("
        SELECT polyhouseid, polyhousename, locationid, type, structure_type 
        FROM krf_polyhouse_master 
        WHERE (status = 'Active' OR status IS NULL)
          AND (type != 'OpenSale' AND structure_type != 'OpenSale' AND polyhousename NOT LIKE '%Open%Sale%')
        ORDER BY polyhouseid ASC
    ");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $list[] = [
                'polyhouseid'   => (int)$row['polyhouseid'],
                'polyhousename' => $row['polyhousename'] ?: ('PH ' . $row['polyhouseid']),
                'locationid'    => $row['locationid']
            ];
        }
    }
    outJson(['status' => 'success', 'data' => $list]);
}

// -------------------------------------------------------------
// 4. ACTION: GET BEDS FOR A POLYHOUSE
// -------------------------------------------------------------
if ($action === 'get_polyhouse_beds') {
    $phId = trim($_REQUEST['polyhouseid'] ?? '');
    $beds = [];

    if (!empty($phId)) {
        // 1. From polyhouse master beddetails JSON
        $stmt = $krfconn->prepare("SELECT beddetails FROM krf_polyhouse_master WHERE polyhouseid = ? OR polyhousename = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('ss', $phId, $phId);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            if ($res && !empty($res['beddetails'])) {
                $decoded = is_array($res['beddetails']) ? $res['beddetails'] : json_decode($res['beddetails'], true);
                if (is_array($decoded)) {
                    foreach ($decoded as $b) {
                        $bName = trim($b['bedname'] ?? $b['name'] ?? $b['bed_no'] ?? '');
                        if (!empty($bName) && !in_array($bName, $beds)) {
                            $beds[] = $bName;
                        }
                    }
                }
            }
            $stmt->close();
        }

        // 2. From distinct plant records in krf_polyhouse_plant_details
        $stmt2 = $krfconn->prepare("SELECT DISTINCT bed_no FROM krf_polyhouse_plant_details WHERE polyhouseid = ? AND bed_no IS NOT NULL AND TRIM(bed_no) <> '' ORDER BY bed_no ASC LIMIT 50");
        if ($stmt2) {
            $stmt2->bind_param('s', $phId);
            $stmt2->execute();
            $res2 = $stmt2->get_result();
            while ($r2 = $res2->fetch_assoc()) {
                $bName2 = trim($r2['bed_no']);
                if (!empty($bName2) && !in_array($bName2, $beds)) {
                    $beds[] = $bName2;
                }
            }
            $stmt2->close();
        }
    }

    // Natural sort beds
    natsort($beds);
    $beds = array_values($beds);

    outJson(['status' => 'success', 'beds' => $beds]);
}

// -------------------------------------------------------------
// 5. ACTION: RETURN TRAYS TO POLYHOUSE (SINGLE OR BULK)
// -------------------------------------------------------------
if ($action === 'return_trays_to_polyhouse') {
    $rawBarcodes = $_REQUEST['barcodes'] ?? [];
    if (is_string($rawBarcodes)) {
        $rawBarcodes = json_decode($rawBarcodes, true) ?: array_filter(array_map('trim', explode(',', $rawBarcodes)));
    }

    if (!is_array($rawBarcodes) || empty($rawBarcodes)) {
        outJson(['status' => 'error', 'message' => 'Please select at least 1 tray to return.']);
    }

    $targetPhId   = trim($_REQUEST['target_polyhouseid'] ?? '');
    $targetBedNo  = trim($_REQUEST['target_bed_no'] ?? '');
    $targetStatus = trim($_REQUEST['target_status'] ?? 'Polyhouse');
    $returnNotes  = trim($_REQUEST['return_notes'] ?? '');

    if (empty($targetPhId)) {
        outJson(['status' => 'error', 'message' => 'Please select target Polyhouse.']);
    }
    if (empty($targetBedNo)) {
        outJson(['status' => 'error', 'message' => 'Please select or enter target Bed number.']);
    }

    // Resolve Polyhouse Name
    $targetPhName = "PH {$targetPhId}";
    $qPh = $krfconn->query("SELECT polyhousename, locationid FROM krf_polyhouse_master WHERE polyhouseid = '{$krfconn->real_escape_string($targetPhId)}' LIMIT 1");
    $targetLocId = '';
    if ($qPh && $rPh = $qPh->fetch_assoc()) {
        $targetPhName = $rPh['polyhousename'] ?: $targetPhName;
        $targetLocId  = $rPh['locationid'] ?: '';
    }

    $nowDt = date('d-M-Y h:i A');
    $curDate = date('Y-m-d');
    $curTime = date('H:i:s');
    $userEsc = $krfconn->real_escape_string($loginname);

    $escBarcodes = array_map([$krfconn, 'real_escape_string'], $rawBarcodes);
    $bcInList = "'" . implode("','", $escBarcodes) . "'";

    // Fetch existing open sale records
    $qExisting = $krfconn->query("
        SELECT id, polyhouseplantid, barcode, productname, variety, stockorder_no, psono,
               open_sale_location, opensale_locationid, actual_plant_count, from_polyhouseid, from_bed_no
        FROM krf_opensale_stocks 
        WHERE barcode IN ({$bcInList}) AND status = 'OpenSale'
    ");

    $rowsToReturn = [];
    if ($qExisting) {
        while ($er = $qExisting->fetch_assoc()) {
            $rowsToReturn[] = $er;
        }
    }

    if (empty($rowsToReturn)) {
        outJson([
            'status' => 'error',
            'message' => 'None of the selected tray(s) were found with active "OpenSale" status. They may have already been returned or sold.'
        ]);
    }

    $krfconn->begin_transaction();
    try {
        $returnedCount = 0;
        $targetPhEsc  = $krfconn->real_escape_string($targetPhId);
        $targetBedEsc = $krfconn->real_escape_string($targetBedNo);
        $targetStEsc  = $krfconn->real_escape_string($targetStatus);

        foreach ($rowsToReturn as $tray) {
            $osId     = (int)$tray['id'];
            $plantId  = (int)$tray['polyhouseplantid'];
            $barcode  = trim($tray['barcode']);
            $bcEsc    = $krfconn->real_escape_string($barcode);
            $osCenter = $tray['open_sale_location'] ?: 'Open Sale';
            $pName    = $tray['productname'] ?: 'Plant';
            $psono    = $tray['psono'] ?: '';
            $stkNo    = $tray['stockorder_no'] ?: '';
            $plants   = (int)$tray['actual_plant_count'];

            $remarkLine = "[Returned from Open Sale ({$osCenter}) to {$targetPhName}/{$targetBedNo} | {$plants} plant(s) by {$loginname} on {$nowDt}" . (!empty($returnNotes) ? " | Note: " . $returnNotes : "") . "]";
            $remarkEsc  = $krfconn->real_escape_string($remarkLine);

            // 1. Update krf_opensale_stocks
            $updOsSql = "
                UPDATE krf_opensale_stocks 
                SET 
                    status = 'Returned',
                    remarks = CONCAT(COALESCE(remarks, ''), IF(COALESCE(remarks, '') = '', '', '\n'), '{$remarkEsc}'),
                    updated_at = NOW()
                WHERE id = {$osId}
            ";
            if (!$krfconn->query($updOsSql)) {
                throw new Exception("Error updating krf_opensale_stocks for tray {$barcode}: " . $krfconn->error);
            }

            // 2. Update krf_polyhouse_plant_details
            $locUpdateSql = !empty($targetLocId) ? "locationid = '{$krfconn->real_escape_string($targetLocId)}'," : "";
            $updPlantSql = "
                UPDATE krf_polyhouse_plant_details
                SET 
                    status = '{$targetStEsc}',
                    workingstatus = '{$targetStEsc}',
                    growth_stage = '{$targetStEsc}',
                    polyhouseid = '{$targetPhEsc}',
                    bed_no = '{$targetBedEsc}',
                    bedrackname = '{$targetBedEsc}',
                    move_at = '{$curDate}',
                    movementremark = CONCAT(COALESCE(movementremark, ''), IF(COALESCE(movementremark, '') = '', '', '\n'), '{$remarkEsc}'),
                    {$locUpdateSql}
                    updated_at = NOW(),
                    updated_by = '{$userEsc}'
                WHERE polyhouseplantid = {$plantId} OR barcode = '{$bcEsc}'
            ";
            if (!$krfconn->query($updPlantSql)) {
                throw new Exception("Error updating krf_polyhouse_plant_details for tray {$barcode}: " . $krfconn->error);
            }

            // 3. Update krf_readytosale_stocks
            if ($targetStatus === 'Readytosale') {
                $updRtsSql = "
                    UPDATE krf_readytosale_stocks
                    SET 
                        status = 'Readytosale',
                        polyhouseid = '{$targetPhEsc}',
                        bed_no = '{$targetBedEsc}',
                        bedqrcode = '{$targetBedEsc}',
                        remarks = CONCAT(COALESCE(remarks, ''), IF(COALESCE(remarks, '') = '', '', ' | '), '{$remarkEsc}'),
                        updated_at = NOW()
                    WHERE barcode = '{$bcEsc}' OR polyhouseplantid = {$plantId}
                ";
                $krfconn->query($updRtsSql);
            } else {
                // Return to regular polyhouse: remove or reset ready to sale entry so it's not listed as ready for sale
                $delRtsSql = "DELETE FROM krf_readytosale_stocks WHERE barcode = '{$bcEsc}' OR polyhouseplantid = {$plantId}";
                $krfconn->query($delRtsSql);
            }

            // 4. Log audit in krf_traymovement_history
            $txNo = 'RET-OS-' . date('YmdHis') . '-' . rand(100, 999);
            $histSql = "
                INSERT INTO krf_traymovement_history (
                    transaction_no, projectid, psono, stockorder_no, productname,
                    activity_type, barcode, from_polyhouse_name, from_bed_no,
                    to_polyhouse_id, to_polyhouse_name, to_bed_no,
                    previous_status, current_status, growth_stage, health_status,
                    live_plant_count, reason_for_movement, movement_date, movement_time,
                    created_by, created_at
                ) VALUES (
                    '{$txNo}', {$projectid}, '{$krfconn->real_escape_string($psono)}', '{$krfconn->real_escape_string($stkNo)}', '{$krfconn->real_escape_string($pName)}',
                    'BED_SHIFTING', '{$bcEsc}', '{$krfconn->real_escape_string($osCenter)}', 'OpenSale',
                    '{$targetPhEsc}', '{$krfconn->real_escape_string($targetPhName)}', '{$targetBedEsc}',
                    'OpenSale', '{$targetStEsc}', '{$targetStEsc}', 'Healthy',
                    {$plants}, '{$remarkEsc}', '{$curDate}', '{$curTime}',
                    '{$userEsc}', NOW()
                )
            ";
            $krfconn->query($histSql);

            $returnedCount++;
        }

        $krfconn->commit();

        outJson([
            'status'         => 'success',
            'message'        => "Successfully returned {$returnedCount} tray(s) back to Polyhouse {$targetPhName}, Bed {$targetBedNo}!",
            'returned_count' => $returnedCount,
            'target_ph'      => $targetPhName,
            'target_bed'     => $targetBedNo,
            'target_status'  => $targetStatus
        ]);
    } catch (Exception $e) {
        $krfconn->rollback();
        outJson(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

outJson(['status' => 'error', 'message' => 'Invalid action specified.']);
