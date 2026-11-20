<?php
require('../../connection.php');

$action = $_GET['action'] ?? '';

if ($action === 'GetProductionOrderTrackingList') {
    $projectid = $_POST['projectid'] ?? 0;
    $datefrom = $_POST['datefrom'] ?? '';
    $dateto = $_POST['dateto'] ?? '';

    if (empty($datefrom) || empty($dateto)) {
        echo json_encode(['status' => 'error', 'message' => 'Date range is required']);
        exit;
    }

    $dt_from = $datefrom . ' 00:00:00';
    $dt_to = $dateto . ' 23:59:59';

    $sql = "
        SELECT 
            p.stockorder_no, 
            COALESCE(NULLIF(TRIM(p.psono), ''), NULLIF(TRIM(soi.psono), ''), NULLIF(TRIM(poc.psono), ''), '') as psono, 
            p.product_name, 
            p.quantity, 
            p.expected_delivery_date, 
            p.created_at,
            p.status,
            p.clientid,
            p.sowingdate,
            cm.client_name,
            COALESCE(sow.totalsowingqty, 0) as totalsowingqty,
            COALESCE(sow.sowing_trays, 0) as sowing_trays,
            COALESCE(sow.sowing_qty, 0) as sowing_qty,
            COALESCE(pm.IsGrafted, 'Non-Grafted') as graftstatus,
            COALESCE(sm_root.SeedName, '') as root_seed_name,
            COALESCE(sm_scion.SeedName, '') as scion_seed_name
        FROM krf_production_stockorder p
        LEFT JOIN krf_client_master cm
            ON cm.client_id = p.clientid
            AND cm.ProjectId = p.projectid
        LEFT JOIN (
            SELECT stockorder_no, MAX(psono) as psono
            FROM krf_sales_order_items
            WHERE NULLIF(TRIM(psono), '') IS NOT NULL
            GROUP BY stockorder_no
        ) soi ON soi.stockorder_no = p.stockorder_no
        LEFT JOIN (
            SELECT stockorderno, MAX(psono) as psono
            FROM krf_productionorder_consumed
            WHERE NULLIF(TRIM(psono), '') IS NOT NULL
            GROUP BY stockorderno
        ) poc ON poc.stockorderno = p.stockorder_no
        LEFT JOIN (
            SELECT 
                stockorder_no, 
                MAX(totalsowingqty) as totalsowingqty, 
                COUNT(sowingplantid) as sowing_trays, 
                SUM(traycavity) as sowing_qty
            FROM krf_polyhouse_sowingplant_details
            WHERE projectid = ?
            GROUP BY stockorder_no
        ) sow ON sow.stockorder_no = p.stockorder_no
        LEFT JOIN product_master pm
            ON pm.ProductID = p.productid
            AND pm.ProjectId = ?
            AND pm.Status = 'Active'
        LEFT JOIN seed_master sm_root
            ON sm_root.SeedID = pm.SeedId
        LEFT JOIN seed_master sm_scion
            ON sm_scion.SeedID = pm.ScionSeedId
        WHERE p.projectid = ? AND p.status = 'Confirmed'
          AND p.created_at >= ? AND p.created_at <= ?
        ORDER BY p.id DESC
    ";

    $stmt = $krfconn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => $krfconn->error]);
        exit;
    }
    $stmt->bind_param("iiiss", $projectid, $projectid, $projectid, $dt_from, $dt_to);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();
    echo json_encode($data);
    exit;

} else if ($action === 'GetProductionOrderTrackingDetails') {
    $projectid = $_POST['projectid'] ?? 0;
    $stockorder_no = $_POST['stockorder_no'] ?? '';

    if (empty($stockorder_no)) {
        echo json_encode(['status' => 'error', 'message' => 'Stock Order No is required']);
        exit;
    }

    $sql = "
        SELECT 
            s.sowinglotno_internal,
            s.sowingdate_internal,
            s.roottype,
            s.sowinglocationid,
            s.productid,
            s.lotno as seed_lot_no,
            s.stockorder_no,
            COUNT(s.sowingplantid) as tray_qty,
            SUM(s.traycavity) as product_qty,
            (SELECT MIN(boxpackingdate) FROM krf_polyhouse_germinated_boxes WHERE stockorderno = s.stockorder_no AND productid = s.productid AND roottype = s.roottype AND COALESCE(sowinglotno_internal, '') = COALESCE(s.sowinglotno_internal, '') AND projectid = s.projectid LIMIT 1) as boxpackingdate,
            (SELECT MAX(boxopeningdate) FROM krf_polyhouse_germinated_boxes WHERE stockorderno = s.stockorder_no AND productid = s.productid AND roottype = s.roottype AND COALESCE(sowinglotno_internal, '') = COALESCE(s.sowinglotno_internal, '') AND projectid = s.projectid LIMIT 1) as boxopeningdate
        FROM krf_polyhouse_sowingplant_details s
        WHERE s.stockorder_no = ? AND s.projectid = ?
        GROUP BY s.sowinglotno_internal, s.roottype, s.productid
        ORDER BY s.sowingdate_internal DESC
    ";

    $stmt = $krfconn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => $krfconn->error]);
        exit;
    }
    $stmt->bind_param("si", $stockorder_no, $projectid);
    $stmt->execute();
    $result = $stmt->get_result();
    $sowings = [];
    while ($row = $result->fetch_assoc()) {
        // Resolve Location Name from hrm_AttendanceLocationMaster
        $locName = 'N/A';
        if (!empty($row['sowinglocationid'])) {
            $locStmt = $hrmconn->prepare("SELECT LocationName FROM hrm_AttendanceLocationMaster WHERE LocationId = ? AND ProjectID = ? LIMIT 1");
            if ($locStmt) {
                $locStmt->bind_param("si", $row['sowinglocationid'], $projectid);
                $locStmt->execute();
                $locRes = $locStmt->get_result()->fetch_assoc();
                if ($locRes) {
                    $locName = $locRes['LocationName'];
                }
                $locStmt->close();
            }
        }
        $row['sowing_location_name'] = $locName;

        // Resolve Sowing Box Count from krf_polyhouse_germinated_boxes
        $boxCount = 0;
        $boxSql = "
            SELECT COUNT(boxid) as box_count 
            FROM krf_polyhouse_germinated_boxes 
            WHERE stockorderno = ? AND productid = ? AND roottype = ? AND COALESCE(sowinglotno_internal, '') = COALESCE(?, '') AND projectid = ?
        ";
        $boxStmt = $krfconn->prepare($boxSql);
        if ($boxStmt) {
            $boxStmt->bind_param("ssssi", $row['stockorder_no'], $row['productid'], $row['roottype'], $row['sowinglotno_internal'], $projectid);
            $boxStmt->execute();
            $boxRes = $boxStmt->get_result()->fetch_assoc();
            if ($boxRes) {
                $boxCount = $boxRes['box_count'] ?? 0;
            }
            $boxStmt->close();
        }
        $row['germ_box_count'] = $boxCount;

        $sowings[] = $row;
    }
    $stmt->close();

    echo json_encode([
        'status' => 'success',
        'sowings' => $sowings
    ]);
    exit;
} else if ($action === 'GetSowingBatchBedDetails') {
    $projectid = $_POST['projectid'] ?? 0;
    $sowinglotno_internal = trim($_POST['sowinglotno_internal'] ?? '');
    $roottype = trim($_POST['roottype'] ?? '');
    $stockorder_no = trim($_POST['stockorder_no'] ?? '');

    if (empty($sowinglotno_internal)) {
        echo json_encode(['status' => 'error', 'message' => 'Sowing lot number is required']);
        exit;
    }

    $sql = "
        SELECT 
            p.polyhouseid,
            p.locationid,
            p.bed_no,
            ph.polyhousename,
            COUNT(p.polyhouseplantid) as qty_of_trays,
            SUM(CAST(COALESCE(p.qtypertray, 0) AS UNSIGNED)) as total_plants,
            SUM(CASE WHEN p.health_status = 'Germination Failure' THEN IFNULL(p.failurecount, 0) ELSE 0 END) as failed_plants,
            SUM(CASE WHEN NULLIF(TRIM(p.bed_verify),'') IS NOT NULL OR NULLIF(TRIM(p.bed_verifyat),'') IS NOT NULL THEN 1 ELSE 0 END) as verified_trays,
            SUM(CASE WHEN NULLIF(TRIM(p.bed_verify),'') IS NULL AND NULLIF(TRIM(p.bed_verifyat),'') IS NULL THEN 1 ELSE 0 END) as unverified_trays,
            MAX(NULLIF(TRIM(p.bed_verifyby), '')) as verified_by,
            MAX(COALESCE(NULLIF(TRIM(p.bed_verifyat),''), NULLIF(TRIM(p.bed_verify),''))) as verified_date,
            SUM(CASE WHEN p.health_status = 'Germination Failure' THEN 1 ELSE 0 END) as failure_trays,
            MAX(CASE WHEN p.health_status IN ('Diseased', 'Infested') THEN 'Yes' ELSE 'No' END) as disease_detected,
            MAX(p.dispatchdate_internal) as dispatch_date,
            MAX(p.status) as status,
            MAX(p.workingstatus) as workingstatus
        FROM krf_polyhouse_plant_details p
        LEFT JOIN krf_polyhouse_master ph ON p.polyhouseid = ph.polyhouseid AND p.projectid = ph.projectid
        WHERE (? = '' OR TRIM(p.stockorder_no) = TRIM(?))
          AND COALESCE(TRIM(p.sowinglotno_internal), '') = COALESCE(TRIM(?), '')
          AND (p.roottype = ? OR (? = 'Scion' AND (p.roottype IS NULL OR p.roottype = '' OR p.roottype = 'Scion')))
          AND p.projectid = ?
        GROUP BY p.polyhouseid, p.locationid, p.bed_no
        ORDER BY p.locationid, ph.polyhousename, CAST(REPLACE(REPLACE(COALESCE(p.bed_no, '0'), 'Bed-', ''), 'Bed ', '') AS UNSIGNED), p.bed_no
    ";

    $stmt = $krfconn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => $krfconn->error]);
        exit;
    }
    $stmt->bind_param("sssssi", $stockorder_no, $stockorder_no, $sowinglotno_internal, $roottype, $roottype, $projectid);
    $stmt->execute();
    $result = $stmt->get_result();
    $details = [];
    while ($row = $result->fetch_assoc()) {
        // Resolve Location Name from hrm_AttendanceLocationMaster
        $locName = 'N/A';
        if (!empty($row['locationid'])) {
            $locStmt = $hrmconn->prepare("SELECT LocationName FROM hrm_AttendanceLocationMaster WHERE LocationId = ? AND ProjectID = ? LIMIT 1");
            if ($locStmt) {
                $locStmt->bind_param("si", $row['locationid'], $projectid);
                $locStmt->execute();
                $locRes = $locStmt->get_result()->fetch_assoc();
                if ($locRes) {
                    $locName = $locRes['LocationName'];
                }
                $locStmt->close();
            }
        }
        $row['location_name'] = $locName;

        // Resolve Verified By Name from auth master
        $verifierName = '';
        if (!empty($row['verified_by'])) {
            $uStmt = $authconn->prepare("SELECT loginname FROM login_user_master WHERE loginid = ? OR mobileno = ? LIMIT 1");
            if ($uStmt) {
                $uStmt->bind_param("ss", $row['verified_by'], $row['verified_by']);
                $uStmt->execute();
                $uRes = $uStmt->get_result()->fetch_assoc();
                if ($uRes && !empty($uRes['loginname'])) {
                    $verifierName = $uRes['loginname'];
                } else {
                    $verifierName = $row['verified_by'];
                }
                $uStmt->close();
            }
        }
        $row['verified_by_name'] = $verifierName;

        // Calculate Germination Failure Percentage (sum of plants failed / sum of plants in trays * 100)
        $total_plants = floatval($row['total_plants']);
        $failed_plants = floatval($row['failed_plants']);
        $row['failure_percentage'] = $total_plants > 0 ? round(($failed_plants / $total_plants) * 100, 2) : 0;

        // Determine internal dispatch status
        $dispatch_date = $row['dispatch_date'];
        $status = $row['status'];
        $workingstatus = $row['workingstatus'];

        $is_dispatched = false;
        if (in_array(strtolower($status), ['internal dispatch', 'final dispatch', 'dispatched', 'delivered']) || 
            in_array(strtolower($workingstatus), ['internal dispatch', 'dispatched', 'delivered'])) {
            $is_dispatched = true;
        }

        $date_passed = false;
        if (!empty($dispatch_date) && strtotime($dispatch_date) < strtotime(date('Y-m-d'))) {
            $date_passed = true;
        }

        $dispatch_status = 'Pending';
        if ($is_dispatched) {
            $dispatch_status = 'Dispatched';
        } else if ($date_passed) {
            $dispatch_status = 'Not Dispatched';
        } else {
            $dispatch_status = 'Pending';
        }

        $row['dispatch_status'] = $dispatch_status;
        $row['date_passed'] = $date_passed;

        $details[] = $row;
    }
    $stmt->close();

    echo json_encode([
        'status' => 'success',
        'details' => $details
    ]);
    exit;
} else if ($action === 'GetPolyhouseDailyReport') {
    $projectid = (int)($_POST['projectid'] ?? 0);
    $reportdate = $_POST['reportdate'] ?? date('Y-m-d');
    $locationid = $_POST['locationid'] ?? '';
    $polyhouseid = $_POST['polyhouseid'] ?? '';
    // Build conditional where clause for location and polyhouse selection
    $cond = "";
    $params = [$projectid, $reportdate,$locationid, (int)$polyhouseid];
    $types = "issi";

    // Query to pull the daily report information grouped by Bed No, product, type, sowing plant and tray type to get aggregate trays/plants
    $sql = "
        SELECT 
            MAX(p.bedrackname) as bedrackname,
            p.bed_no,
            MAX(s.sowingdate_internal) as sowingdate_internal,
            MAX(s.sowinglocationid) as sowinglocationid,
            MAX(p.productname) as productname,
            MAX(so.clientid) as clientid,
            MAX(cm.client_name) as client_name,
            p.roottype,
            MAX(p.tray_id) as tray_id,
            MAX(t.cavity_count) as cavity_count,
            COUNT(p.polyhouseplantid) as no_of_trays,
            SUM(CAST(IFNULL(p.qtypertray, 0) as UNSIGNED)) as total_plants,
            SUM(CASE WHEN p.bed_verify IS NOT NULL AND p.bed_verify <> '' AND p.bed_verify <> '0000-00-00 00:00:00' THEN 1 ELSE 0 END) as verified_trays_count,
            MAX(p.dispatchdate_internal) as dispatchdate_internal,
            MAX(p.health_status) as health_status,
            MAX(p.sowingplantid) as sowingplantid,
            MAX(p.sowing_date) as sowing_date,
            MAX(pm.IsGrafted) as IsGrafted,
            MAX(p.status) as status,
            MAX(p.workingstatus) as workingstatus,
            MAX(p.remarks) as remarks,
            MAX(gb.received_by) as received_by_id,
            p.productid,
            p.stockorder_no,
            p.sowinglotno_internal
        FROM krf_polyhouse_plant_details p
        LEFT JOIN krf_polyhouse_sowingplant_details s ON p.sowingplantid = s.sowingplantid AND p.projectid = s.projectid
        LEFT JOIN krf_production_stockorder so ON p.stockorder_no = so.stockorder_no AND p.projectid = so.projectid
        LEFT JOIN krf_client_master cm ON so.clientid = cm.client_id AND so.projectid = cm.ProjectId
        LEFT JOIN product_master pm ON p.productid = pm.ProductID AND p.projectid = pm.ProjectId
        LEFT JOIN krf_tray_master t ON p.tray_id = t.tray_id
        LEFT JOIN krf_polyhouse_germinated_boxes gb ON s.germinationboxid = gb.boxid AND s.projectid = gb.projectid
        WHERE p.projectid = ? AND DATE(p.planted_date) = ?  AND p.locationid = ?  AND p.polyhouseid = ?
        GROUP BY p.projectid, p.productid, p.stockorder_no, p.roottype, p.bed_no, p.sowinglotno_internal
        ORDER BY MAX(p.polyhouseid) ASC, MAX(p.bed_no) ASC
    ";

    $stmt = $krfconn->prepare($sql);
    $details = [];
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $sn = 1;
        while ($row = $result->fetch_assoc()) {
            // Sowing location name
            $sowLocName = 'N/A';
            if (!empty($row['sowinglocationid'])) {
                $locStmt = $hrmconn->prepare("SELECT LocationName FROM hrm_AttendanceLocationMaster WHERE LocationId = ? AND ProjectID = ? LIMIT 1");
                if ($locStmt) {
                    $locStmt->bind_param("si", $row['sowinglocationid'], $projectid);
                    $locStmt->execute();
                    $locRes = $locStmt->get_result()->fetch_assoc();
                    if ($locRes) {
                        $sowLocName = $locRes['LocationName'];
                    }
                    $locStmt->close();
                }
            }
            // Calculation of germination failure: check health_status in the batch
            $failPercent = 0;
            if ($row['sowingplantid'] > 0) {
                // Count total trays in the sowing batch vs total failed trays in the same sowing batch
                $stmtBatch = $krfconn->prepare("
                    SELECT 
                        COUNT(polyhouseplantid) as total_trays,
                        SUM(CASE WHEN health_status = 'Germination Failure' THEN 1 ELSE 0 END) as failure_trays
                    FROM krf_polyhouse_plant_details 
                    WHERE sowingplantid = ? AND projectid = ?
                ");
                if ($stmtBatch) {
                    $stmtBatch->bind_param("ii", $row['sowingplantid'], $projectid);
                    $stmtBatch->execute();
                    $batchRes = $stmtBatch->get_result()->fetch_assoc();
                    $total_trays = intval($batchRes['total_trays'] ?? 0);
                    $failure_trays = intval($batchRes['failure_trays'] ?? 0);
                    if ($total_trays > 0) {
                        $failPercent = round(($failure_trays / $total_trays) * 100, 2);
                    }
                    $stmtBatch->close();
                }
            }

            // Dispatch status logic
            $dispatch_date = $row['dispatchdate_internal'];
            $status = $row['status'];
            $workingstatus = $row['workingstatus'];
            $is_dispatched = false;
            if (in_array(strtolower($status), ['internal dispatch', 'final dispatch', 'dispatched', 'delivered']) || 
                in_array(strtolower($workingstatus), ['internal dispatch', 'dispatched', 'delivered'])) {
                $is_dispatched = true;
            }
            $date_passed = false;
            if (!empty($dispatch_date) && strtotime($dispatch_date) < strtotime(date('Y-m-d'))) {
                $date_passed = true;
            }
            $dispatch_status = 'Pending';
            if ($is_dispatched) {
                $dispatch_status = 'Dispatched';
            } else if ($date_passed) {
                $dispatch_status = 'Not Dispatched';
            }

            // Physical verification label: show counts verified
            $verification_text = $row['verified_trays_count'] . " Trays Verified";

            // Query user name inside loop to prevent cross-database connection error
            $box_received_by = 'N/A';
            if (!empty($row['received_by_id'])) {
                $userStmt = $authconn->prepare("SELECT loginname FROM login_user_master WHERE loginid = ? LIMIT 1");
                if ($userStmt) {
                    $userStmt->bind_param("s", $row['received_by_id']);
                    $userStmt->execute();
                    $userRes = $userStmt->get_result()->fetch_assoc();
                    if ($userRes && !empty($userRes['loginname'])) {
                        $box_received_by = $userRes['loginname'];
                    }
                    $userStmt->close();
                }
            }

            $details[] = [
                'sn' => $sn++,
                'bed_no' => $row['bed_no'] ? $row['bed_no'] : 'N/A',
                'sowing_date' => $row['sowingdate_internal'] ? date('d-M-Y', strtotime($row['sowingdate_internal'])) : ($row['sowing_date'] ? date('d-M-Y', strtotime($row['sowing_date'])) : 'N/A'),
                'sowing_location' => $sowLocName,
                'product_name' => $row['productname'] ? $row['productname'] : 'N/A',
                'client_name' => $row['client_name'] ? $row['client_name'] : 'N/A',
                'type' => $row['roottype'] ? $row['roottype'] : 'Scion', // Default Scion/Root status
                'tray_type' => $row['cavity_count'] ? $row['cavity_count'] : 'N/A',
                'no_of_trays' => intval($row['no_of_trays']),
                'no_of_plants' => intval($row['total_plants']),
                'physical_verification' => $verification_text,
                'germination_failure' => $failPercent . '%',
                'dispatch_date' => $dispatch_date ? date('d-M-Y', strtotime($dispatch_date)) : 'N/A',
                'dispatch_status' => $dispatch_status,
                'remarks' => $row['remarks'] ? $row['remarks'] : '',
                'box_received_by' => $box_received_by,
                'product_id' => $row['productid'],
                'stockorder_no' => $row['stockorder_no'],
                'sowinglotno_internal' => $row['sowinglotno_internal']
            ];
        }
        $stmt->close();
    }

    echo json_encode([
        'status' => 'success',
        'details' => $details
    ]);
    exit;
} else if ($action === 'UpdateBedTrays') {
    $projectid = (int)($_POST['projectid'] ?? 0);
    $reportdate = $_POST['reportdate'] ?? '';
    $polyhouseid = (int)($_POST['polyhouseid'] ?? 0);
    $stockorder_no = $_POST['stockorder_no'] ?? '';
    $productid = (int)($_POST['productid'] ?? 0);
    $roottype = $_POST['roottype'] ?? '';
    $sowinglotno_internal = $_POST['sowinglotno_internal'] ?? '';
    $beds_json = $_POST['beds'] ?? '[]';
    $beds = [];
    try {
        $beds = json_decode($beds_json, true);
    } catch (Exception $e) {
        $beds = [];
    }

    if (empty($reportdate) || empty($stockorder_no)) {
        echo json_encode(['status' => 'error', 'message' => 'Required parameters missing.']);
        exit;
    }

    // Start transaction
    $krfconn->begin_transaction();

    // 1. Clear bed_no for all matching trays (set to empty string)
    $clearSql = "UPDATE krf_polyhouse_plant_details 
                 SET bed_no = '' 
                 WHERE projectid = ? 
                   AND DATE(planted_date) = ? 
                   AND polyhouseid = ? 
                   AND stockorder_no = ? 
                   AND productid = ? 
                   AND roottype = ? 
                   AND sowinglotno_internal = ?";
    $clearStmt = $krfconn->prepare($clearSql);
    if (!$clearStmt) {
        $krfconn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Database error preparing clear query: ' . $krfconn->error]);
        exit;
    }
    $clearStmt->bind_param("isisiss", $projectid, $reportdate, $polyhouseid, $stockorder_no, $productid, $roottype, $sowinglotno_internal);
    if (!$clearStmt->execute()) {
        $krfconn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Database error executing clear query: ' . $clearStmt->error]);
        exit;
    }
    $clearStmt->close();

    // 2. Assign bed_no for each bed input
    $updateSql = "UPDATE krf_polyhouse_plant_details 
                  SET bed_no = ? 
                  WHERE projectid = ? 
                    AND DATE(planted_date) = ? 
                    AND polyhouseid = ? 
                    AND stockorder_no = ? 
                    AND productid = ? 
                    AND roottype = ? 
                    AND sowinglotno_internal = ? 
                    AND (bed_no = '' OR bed_no IS NULL) 
                  LIMIT ?";
    $updateStmt = $krfconn->prepare($updateSql);
    if (!$updateStmt) {
        $krfconn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Database error preparing update query: ' . $krfconn->error]);
        exit;
    }

    foreach ($beds as $bed) {
        $bed_no = trim($bed['bed_no'] ?? '');
        $qty = (int)($bed['qty'] ?? 0);
        if (empty($bed_no) || $qty <= 0) {
            continue;
        }

        $updateStmt->bind_param("sisisissi", $bed_no, $projectid, $reportdate, $polyhouseid, $stockorder_no, $productid, $roottype, $sowinglotno_internal, $qty);
        if (!$updateStmt->execute()) {
            $krfconn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'Database error updating bed ' . $bed_no . ': ' . $updateStmt->error]);
            exit;
        }
    }
    $updateStmt->close();

    $krfconn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Bed trays updated successfully!']);
    exit;
} else if ($action === 'GetGerminationFilterOptions') {
    $projectid = (int)($_POST['projectid'] ?? 0);
    $companyid = (int)($_POST['companyid'] ?? 0);
    $subcompanyid = (int)($_POST['subcompanyid'] ?? 0);

    // 1. Years from krf_polyhouse_plant_details (sowingdate_internal)
    $years = [];
    $stmtYears = $krfconn->prepare("
        SELECT DISTINCT YEAR(sowingdate_internal) as yr 
        FROM krf_polyhouse_plant_details 
        WHERE projectid = ? AND sowingdate_internal IS NOT NULL AND sowingdate_internal != '' AND sowingdate_internal != '0000-00-00' 
        ORDER BY yr DESC
    ");
    if ($stmtYears) {
        $stmtYears->bind_param("i", $projectid);
        $stmtYears->execute();
        $resYears = $stmtYears->get_result();
        while ($yrow = $resYears->fetch_assoc()) {
            if (!empty($yrow['yr'])) {
                $years[] = (int)$yrow['yr'];
            }
        }
        $stmtYears->close();
    }
    if (empty($years)) {
        $years[] = (int)date('Y');
    }

    // 2. Locations from hrm_AttendanceLocationMaster
    $locations = [];
    $locSql = "
        SELECT DISTINCT lm.LocationId, lm.LocationName 
        FROM hrm_AttendanceLocationMaster lm
        WHERE lm.ProjectID = ?
        ORDER BY lm.LocationName ASC
    ";
    $stmtLoc = $hrmconn->prepare($locSql);
    if ($stmtLoc) {
        $stmtLoc->bind_param("i", $projectid);
        $stmtLoc->execute();
        $resLoc = $stmtLoc->get_result();
        while ($lrow = $resLoc->fetch_assoc()) {
            $locations[] = [
                'LocationId' => $lrow['LocationId'],
                'LocationName' => $lrow['LocationName']
            ];
        }
        $stmtLoc->close();
    }

    // 3. Polyhouses & Beds from krf_polyhouse_master
    $polyhouses = [];
    $stmtPh = $krfconn->prepare("
        SELECT polyhouseid, polyhousename, locationid, beddetails 
        FROM krf_polyhouse_master 
        WHERE projectid = ? 
        ORDER BY polyhouseid ASC
    ");
    if ($stmtPh) {
        $stmtPh->bind_param("i", $projectid);
        $stmtPh->execute();
        $resPh = $stmtPh->get_result();
        while ($phrow = $resPh->fetch_assoc()) {
            $bedList = [];
            if (!empty($phrow['beddetails'])) {
                $decoded = json_decode($phrow['beddetails'], true);
                if (is_array($decoded)) {
                    foreach ($decoded as $bitem) {
                        if (!empty($bitem['bedname'])) {
                            $bedList[] = $bitem['bedname'];
                        }
                    }
                }
            }
            $polyhouses[] = [
                'polyhouseid' => $phrow['polyhouseid'],
                'polyhousename' => $phrow['polyhousename'],
                'locationid' => $phrow['locationid'] ?? '',
                'beds' => $bedList
            ];
        }
        $stmtPh->close();
    }

    echo json_encode([
        'status' => 'success',
        'years' => $years,
        'locations' => $locations,
        'polyhouses' => $polyhouses
    ]);
    exit;
} else if ($action === 'GetSowingGerminationFailureList') {
    $projectid = (int)($_POST['projectid'] ?? 0);
    $year = $_POST['year'] ?? '';
    $locationid = $_POST['locationid'] ?? '';
    $polyhouseid = $_POST['polyhouseid'] ?? '';
    $bed_no = $_POST['bed_no'] ?? '';
    $datefrom = $_POST['datefrom'] ?? '';
    $dateto = $_POST['dateto'] ?? '';

    $sql = "
        SELECT 
            so.stockorder_no,
            so.product_name,
            so.quantity as order_qty,
            DATE(so.created_at) as production_date,
            so.expected_delivery_date,
            COUNT(DISTINCT p.polyhouseplantid) as total_trays,
            SUM(CASE WHEN p.health_status = 'Germination Failure' THEN 1 ELSE 0 END) as failed_trays,
            SUM(CAST(IFNULL(t.cavity_count, p.qtypertray) AS UNSIGNED)) as total_plants,
            SUM(CASE WHEN p.health_status = 'Germination Failure' THEN IFNULL(p.failurecount, 0) ELSE 0 END) as failed_plants,
            SUM(CASE WHEN p.health_status = 'Germination Failure' THEN CAST(IFNULL(t.cavity_count, p.qtypertray) AS UNSIGNED) ELSE 0 END) as fail_trays_capacity,
            MIN(p.sowingdate_internal) as min_sowing_date,
            MAX(p.sowingdate_internal) as max_sowing_date
        FROM krf_polyhouse_plant_details p
        JOIN krf_production_stockorder so ON p.stockorder_no = so.stockorder_no AND p.projectid = so.projectid
        LEFT JOIN krf_polyhouse_master ph ON p.polyhouseid = ph.polyhouseid AND p.projectid = ph.projectid
        LEFT JOIN krf_tray_master t ON p.tray_id = t.tray_id
        WHERE p.projectid = ? AND p.bed_verify IS NOT NULL AND p.bed_verify != ''
    ";
    
    $params = [$projectid];
    $types = "i";

    if (!empty($year)) {
        $sql .= " AND YEAR(p.sowingdate_internal) = ? ";
        $params[] = (int)$year;
        $types .= "i";
    }

    if (!empty($locationid)) {
        $sql .= " AND ph.locationid = ? ";
        $params[] = $locationid;
        $types .= "s";
    }

    if (!empty($polyhouseid)) {
        $sql .= " AND p.polyhouseid = ? ";
        $params[] = $polyhouseid;
        $types .= "s";
    }

    if (!empty($bed_no)) {
        $sql .= " AND (p.bed_no = ? OR p.bedrackname = ?) ";
        $params[] = $bed_no;
        $params[] = $bed_no;
        $types .= "ss";
    }

    if (!empty($datefrom) && !empty($dateto)) {
        $sql .= " AND p.sowingdate_internal BETWEEN ? AND ? ";
        $params[] = $datefrom;
        $params[] = $dateto;
        $types .= "ss";
    }

    $sql .= " GROUP BY so.stockorder_no, so.product_name, so.quantity, so.created_at, so.expected_delivery_date ORDER BY so.id DESC ";

    $stmt = $krfconn->prepare($sql);
    $details = [];
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $sn = 1;
        while ($row = $result->fetch_assoc()) {
            $total_plants = intval($row['total_plants']);
            $failed_plants = intval($row['failed_plants']);
            $total_trays = intval($row['total_trays']);
            $failed_trays = intval($row['failed_trays']);
            $fail_trays_capacity = intval($row['fail_trays_capacity']);

            // Total failure % of failed trays plants
            $fail_percent = 0;
            if ($fail_trays_capacity > 0) {
                $fail_percent = round(($failed_plants / $fail_trays_capacity) * 100, 2);
            }

            $details[] = [
                'sn' => $sn++,
                'production_date' => $row['production_date'] ? date('d-M-Y', strtotime($row['production_date'])) : 'N/A',
                'stockorder_no' => $row['stockorder_no'],
                'product_name' => $row['product_name'] ?? 'N/A',
                'order_qty' => intval($row['order_qty']),
                'total_sowing_qty' => $total_plants,
                'total_trays' => $total_trays,
                'failed_trays' => $failed_trays,
                'fail_trays_capacity' => $fail_trays_capacity,
                'expected_delivery_date' => $row['expected_delivery_date'] ? date('d-M-Y', strtotime($row['expected_delivery_date'])) : 'N/A',
                'failed_plants' => $failed_plants,
                'total_plants' => $total_plants,
                'fail_percent' => $fail_percent
            ];
        }
        $stmt->close();
    }
    echo json_encode(['status' => 'success', 'details' => $details]);
    exit;
} else if ($action === 'GetSowingGerminationFailureDetails') {
    $projectid = (int)($_POST['projectid'] ?? 0);
    $stockorder_no = $_POST['stockorder_no'] ?? '';
    $year = $_POST['year'] ?? '';
    $locationid = $_POST['locationid'] ?? '';
    $polyhouseid = $_POST['polyhouseid'] ?? '';
    $bed_no = $_POST['bed_no'] ?? '';
    $datefrom = $_POST['datefrom'] ?? '';
    $dateto = $_POST['dateto'] ?? '';

    $sql = "
        SELECT 
            p.lotno,
            p.sowinglotno_internal,
            p.sowingdate_internal,
            p.roottype,
            p.polyhouseid,
            ph.polyhousename,
            p.bed_no,
            COUNT(DISTINCT p.polyhouseplantid) as total_trays,
            SUM(CASE WHEN p.health_status = 'Germination Failure' THEN 1 ELSE 0 END) as failed_trays,
            SUM(CAST(IFNULL(t.cavity_count, p.qtypertray) AS UNSIGNED)) as total_plants,
            SUM(CASE WHEN p.health_status = 'Germination Failure' THEN IFNULL(p.failurecount, 0) ELSE 0 END) as failed_plants,
            SUM(CASE WHEN p.health_status = 'Germination Failure' THEN CAST(IFNULL(t.cavity_count, p.qtypertray) AS UNSIGNED) ELSE 0 END) as fail_trays_capacity
        FROM krf_polyhouse_plant_details p
        LEFT JOIN krf_polyhouse_master ph ON p.polyhouseid = ph.polyhouseid AND p.projectid = ph.projectid
        LEFT JOIN krf_tray_master t ON p.tray_id = t.tray_id
        WHERE p.projectid = ? AND p.stockorder_no = ? AND p.bed_verify IS NOT NULL AND p.bed_verify != ''
    ";

    $params = [$projectid, $stockorder_no];
    $types = "is";

    if (!empty($year)) {
        $sql .= " AND YEAR(p.sowingdate_internal) = ? ";
        $params[] = (int)$year;
        $types .= "i";
    }

    if (!empty($locationid)) {
        $sql .= " AND ph.locationid = ? ";
        $params[] = $locationid;
        $types .= "s";
    }

    if (!empty($polyhouseid)) {
        $sql .= " AND p.polyhouseid = ? ";
        $params[] = $polyhouseid;
        $types .= "s";
    }

    if (!empty($bed_no)) {
        $sql .= " AND (p.bed_no = ? OR p.bedrackname = ?) ";
        $params[] = $bed_no;
        $params[] = $bed_no;
        $types .= "ss";
    }

    if (!empty($datefrom) && !empty($dateto)) {
        $sql .= " AND p.sowingdate_internal BETWEEN ? AND ? ";
        $params[] = $datefrom;
        $params[] = $dateto;
        $types .= "ss";
    }

    $sql .= " GROUP BY p.lotno, p.sowinglotno_internal, p.sowingdate_internal, p.roottype, p.polyhouseid, p.bed_no ORDER BY p.sowingdate_internal DESC, p.polyhouseid ASC, p.bed_no ASC ";

    $stmt = $krfconn->prepare($sql);
    $details = [];
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $total_plants = intval($row['total_plants']);
            $failed_plants = intval($row['failed_plants']);
            $fail_trays_capacity = intval($row['fail_trays_capacity']);
            $fail_percent = 0;
            if ($fail_trays_capacity > 0) {
                $fail_percent = round(($failed_plants / $fail_trays_capacity) * 100, 2);
            }

            $details[] = [
                'lotno' => $row['lotno'] ? $row['lotno'] : '',
                'sowinglotno_internal' => $row['sowinglotno_internal'] ? $row['sowinglotno_internal'] : 'N/A',
                'sowingdate_internal' => $row['sowingdate_internal'] ? date('d-M-Y', strtotime($row['sowingdate_internal'])) : 'N/A',
                'roottype' => $row['roottype'] ? $row['roottype'] : 'N/A',
                'polyhousename' => $row['polyhousename'] ? $row['polyhousename'] : 'PH ' . $row['polyhouseid'],
                'bed_no' => $row['bed_no'] ? $row['bed_no'] : 'N/A',
                'total_trays' => intval($row['total_trays']),
                'failed_trays' => intval($row['failed_trays']),
                'total_plants' => $total_plants,
                'failed_plants' => $failed_plants,
                'fail_trays_capacity' => $fail_trays_capacity,
                'fail_percent' => $fail_percent
            ];
        }
        $stmt->close();
    }
    echo json_encode(['status' => 'success', 'details' => $details]);
    exit;
}
?>
