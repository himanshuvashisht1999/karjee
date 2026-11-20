<?php
require('../connection.php');
require('../functions/function_master.php');
include('functions/labour_functions.php');
include('functions/product_functions.php');

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$response = ['status' => 'error', 'message' => 'Invalid action'];

$companyid = $_POST['companyid'] ?? $_GET['companyid'] ?? '';
$subcompanyid = $_POST['subcompanyid'] ?? $_GET['subcompanyid'] ?? '';
$projectid = $_POST['projectid'] ?? $_GET['projectid'] ?? '';
$loginid = $_POST['loginid'] ?? $_GET['loginid'] ?? 'Auto';

if (empty($projectid)) {
    echo json_encode(['status' => 'error', 'message' => 'Project ID is required']);
    exit;
}else if ($action === 'getLocations') {
    $locations = getProductionLocationMaster($hrmconn, $companyid, $subcompanyid, $projectid, 'all', 'all');
    echo json_encode(['status' => 'success', 'data' => $locations]);
    exit;
}else if ($action === 'getPolyhouses') {
    $locationid = $_POST['locationid'] ?? '';
    if (empty($locationid)) {
        echo json_encode(['status' => 'error', 'message' => 'Location ID is required']);
        exit;
    }
    // Fetch polyhouses for this location
    $polyhouses = getPolyhouseData($krfconn, $projectid, 'locationid', $locationid);
    echo json_encode(['status' => 'success', 'data' => $polyhouses]);
    exit;
}else if ($action === 'getBeds') {
    $polyhouseid = $_POST['polyhouseid'] ?? '';
    if (empty($polyhouseid)) {
        echo json_encode(['status' => 'error', 'message' => 'Polyhouse ID is required']);
        exit;
    }
    $polydata = getPolyhouseData($krfconn, $projectid, 'polyhouseid', $polyhouseid);
    if (!empty($polydata) && isset($polydata[0]['beddetails'])) {
        $beddetails = $polydata[0]['beddetails'];
        // beddetails can be JSON string or array depending on helper parsing
        if (is_string($beddetails)) {
            $beddetails = json_decode($beddetails, true);
        }
        echo json_encode(['status' => 'success', 'data' => $beddetails]);
    } else {
        echo json_encode(['status' => 'success', 'data' => []]);
    }
    exit;
}else if ($action === 'getBedSummary') {
    $projectid = trim($_POST['projectid'] ?? '');
    $locationid = trim($_POST['locationid'] ?? '');
    $polyhouseid = intval($_POST['polyhouseid'] ?? 0);
    $bed_no = trim($_POST['bed_no'] ?? '');

    if ($polyhouseid <= 0 || empty($bed_no)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
        exit;
    }

    $groups = [];
    $totalTrays = 0;
    $totalVerified = 0;

    // 1. Query plant details (status = 'Polyhouse')
    $stmt1 = $krfconn->prepare("
        SELECT productid, productname, stockorder_no, roottype, sowinglotno_internal as batchno, 
               MAX(qtypertray) as qtypertray, MIN(sowingdate_internal) as sowingdate, 
               COUNT(*) as cnt,
               SUM(CASE WHEN bed_verify IS NOT NULL AND bed_verify <> '' THEN 1 ELSE 0 END) as verified_cnt
        FROM krf_polyhouse_plant_details 
        WHERE polyhouseid = ? AND bed_no = ? AND status = 'Polyhouse' AND projectid = ? AND locationid = ?
        GROUP BY projectid, productid, stockorder_no, roottype, sowinglotno_internal
    ");
    if ($stmt1) {
        $stmt1->bind_param("isis", $polyhouseid, $bed_no, $projectid, $locationid);
        $stmt1->execute();
        $res = $stmt1->get_result();
        while ($row = $res->fetch_assoc()) {
            $key = $row['productid'] . '_' . $row['stockorder_no'] . '_' . $row['roottype'] . '_' . $row['batchno'];
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'productname' => $row['productname'] ?? 'Unknown Product',
                    'stockorder_no' => $row['stockorder_no'] ?? 'N/A',
                    'roottype' => $row['roottype'] ?? 'N/A',
                    'batchno' => $row['batchno'] ?? 'N/A',
                    'qtypertray' => intval($row['qtypertray'] ?? 0),
                    'sowingdate' => $row['sowingdate'] ? date('d-M-Y', strtotime($row['sowingdate'])) : 'N/A',
                    'cnt' => 0,
                    'verifytrays' => 0
                ];
            }
            $groups[$key]['cnt'] += $row['cnt'];
            $groups[$key]['verifytrays'] += intval($row['verified_cnt'] ?? 0);
            $totalTrays += $row['cnt'];
            $totalVerified += intval($row['verified_cnt'] ?? 0);
        }
        $stmt1->close();
    }
    echo json_encode([
        'status' => 'success',
        'data' => [
            'groups' => array_values($groups),
            'total_trays' => $totalTrays,
            'total_verified' => $totalVerified
        ]
    ]);
    exit;
}else if ($action === 'getTrayDetails') {
    $barcode = preg_replace('/[\x00-\x1F\x7F\xA0\x{200B}-\x{200D}\x{FEFF}]/u', '', trim($_POST['barcode'] ?? ''));
    if (empty($barcode)) {
        echo json_encode(['status' => 'error', 'message' => 'Barcode is required']);
        exit;
    }

    $trayInfo = null;

    $stmt = $krfconn->prepare("
        SELECT p.polyhouseplantid as id, p.barcode, p.bed_no, p.bed_verify, p.bed_verifyby, p.bed_verifyat, p.polyhouseid, p.productname, p.batchno,
               ph.polyhousename, 'Grafted Tray' as tray_type, p.updated_by
        FROM krf_polyhouse_plant_details p
        LEFT JOIN krf_polyhouse_master ph ON CONVERT(p.polyhouseid USING utf8mb4) = CONVERT(ph.polyhouseid, CHAR)
        WHERE CONVERT(p.barcode USING utf8mb4) = ? AND p.projectid = ?
        LIMIT 1
    ");
    if ($stmt) {
        $stmt->bind_param("si", $barcode, $projectid);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($res) {
            $trayInfo = $res;
            $trayInfo['verified_by_name'] = 'N/A';
            if (!empty($res['bed_verify']) && $res['bed_verify'] !== '0000-00-00 00:00:00' && !empty($res['bed_verifyby'])) {
                $tableName = 'login_user_master';
                $checkTable = $authconn->query("SHOW TABLES LIKE 'login_authorized_master'");
                if ($checkTable && $checkTable->num_rows > 0) {
                    $tableName = 'login_authorized_master';
                }
                $userStmt = $authconn->prepare("SELECT loginname FROM $tableName WHERE loginid = ? LIMIT 1");
                if ($userStmt) {
                    $userStmt->bind_param("s", $res['bed_verifyby']);
                    $userStmt->execute();
                    $userRes = $userStmt->get_result()->fetch_assoc();
                    $userStmt->close();
                    if ($userRes) {
                        $trayInfo['verified_by_name'] = $userRes['loginname'];
                    }
                }
            }
        }
    }   


    if ($trayInfo) {
        echo json_encode(['status' => 'success', 'data' => $trayInfo]);
    } else {
        // Query krf_polyhouse_sowingplant_details
        $stmt_sowing = $krfconn->prepare("
            SELECT s.sowingplantid, s.barcode, s.status, s.workingstatus, s.productname, s.sowingbatchno as batchno, s.polyhouseid, ph.polyhousename
            FROM krf_polyhouse_sowingplant_details s
            LEFT JOIN krf_polyhouse_master ph ON CONVERT(s.polyhouseid USING utf8mb4) = CONVERT(ph.polyhouseid, CHAR)
            WHERE CONVERT(s.barcode USING utf8mb4) = ? AND s.projectid = ?
            LIMIT 1
        ");
        if ($stmt_sowing) {
            $stmt_sowing->bind_param("si", $barcode, $projectid);
            $stmt_sowing->execute();
            $sowing_res = $stmt_sowing->get_result()->fetch_assoc();
            $stmt_sowing->close();
            if ($sowing_res) {
                $actual_status = ($sowing_res['status'] ?? 'N/A') . ' / ' . ($sowing_res['workingstatus'] ?? 'N/A');
                echo json_encode([
                    'status' => 'not_found_moved',
                    'message' => 'Tray details did not found in Polyhouse Moved Plant list.',
                    'actual_status' => $actual_status,
                    'data' => [
                        'barcode' => $sowing_res['barcode'],
                        'productname' => $sowing_res['productname'] ?? 'Unknown Product',
                        'batchno' => $sowing_res['batchno'] ?? 'N/A',
                        'polyhouseid' => $sowing_res['polyhouseid'] ?? '',
                        'polyhousename' => $sowing_res['polyhousename'] ?? 'N/A',
                        'tray_type' => 'Grafted Tray',
                        'bed_no' => 'N/A'
                    ]
                ]);
                exit;
            }
        }

        echo json_encode([
            'status' => 'error', 
            'message' => 'Tray barcode not found in database, but you can still record it.'
        ]);
    }
    exit;
}else if ($action === 'updateTrays') {
    $barcodes = $_POST['barcodes'] ?? [];
    if (is_string($barcodes)) {
        $barcodes = [$barcodes];
    }
    $target_polyhouseid = intval($_POST['target_polyhouseid'] ?? 0);
    $target_bed_no = trim($_POST['target_bed_no'] ?? '');

    if (empty($barcodes) || !is_array($barcodes)) {
        echo json_encode(['status' => 'error', 'message' => 'No trays selected for update']);
        exit;
    }
    if ($target_polyhouseid <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Valid Polyhouse selection is required']);
        exit;
    }

    $krfconn->begin_transaction();
    try {
        $updatedCount = 0;

        // Get target location ID
        $target_locationid = '';
        $locStmt = $krfconn->prepare("SELECT locationid FROM krf_polyhouse_master WHERE polyhouseid = ?");
        if ($locStmt) {
            $locStmt->bind_param("i", $target_polyhouseid);
            $locStmt->execute();
            $locRes = $locStmt->get_result()->fetch_assoc();
            $target_locationid = $locRes['locationid'] ?? '';
            $locStmt->close();
        }

        $checkPlant = $krfconn->prepare("SELECT polyhouseplantid FROM krf_polyhouse_plant_details WHERE CONVERT(barcode USING utf8mb4) = ? AND projectid = ? LIMIT 1");
        $updatePlant = $krfconn->prepare("
            UPDATE krf_polyhouse_plant_details 
            SET polyhouseid = ?, bed_no = ?, bed_verify = NOW(), bed_verifyat = NOW(), bed_verifyby = ?
            ,   qrcode_request =null, qrcode_requestedby =null ,qr_printdate =null, qr_printby =null WHERE CONVERT(barcode USING utf8mb4) = ? AND projectid = ?
        ");
        $insertPlant = $krfconn->prepare("
            INSERT INTO krf_polyhouse_plant_details (
                psono, roottype, sowingplantid, stockorder_no, locationid,
                labourid, polyhouseid, projectid, lotno,
                batchno, barcode, productid, productname,
                tray_id, qtypertray, tray_position, bedrackname, bed_no,
                planted_date, workingstatus, status, remarks, sowing_date, sowingdate_internal, sowinglotno_internal, sowingbatchno, dispatchdate_internal, created_by,
                bed_verify, bed_verifyat, bed_verifyby
            )
            SELECT
                s.psono, s.roottype, s.sowingplantid, s.stockorder_no, ?,
                s.labourid, ?, s.projectid, s.lotno,
                s.sowingbatchno, s.barcode, s.productid,
                s.productname, s.trayid, s.traycavity,
                ?, ?, ?,
                NOW(), 'Polyhouse', 'Polyhouse', s.remarks, s.sowing_date, s.sowingdate_internal, s.sowinglotno_internal, s.sowingbatchno, s.dispatchdate_internal, ?,
                NOW(), NOW(), ?
            FROM krf_polyhouse_sowingplant_details s
            WHERE CONVERT(s.barcode USING utf8mb4) = ? AND s.projectid = ?
            LIMIT 1
        ");
        $updateSowing = $krfconn->prepare("
            UPDATE krf_polyhouse_sowingplant_details 
            SET status = 'Polyhouse', workingstatus = 'Polyhouse', locationid = ?, polyhouseid = ?, bed_no = ?, updated_at = NOW(), updated_by = ?
            WHERE CONVERT(barcode USING utf8mb4) = ? AND projectid = ?
        ");

        foreach ($barcodes as $barcode) {
            $barcode = preg_replace('/[\x00-\x1F\x7F\xA0\x{200B}-\x{200D}\x{FEFF}]/u', '', trim($barcode));
            if (empty($barcode)) continue;

            // 1. Check if barcode exists in krf_polyhouse_plant_details
            $exists = false;
            if ($checkPlant) {
                $checkPlant->bind_param("si", $barcode, $projectid);
                $checkPlant->execute();
                $exists = $checkPlant->get_result()->num_rows > 0;
            }

            if ($exists) {
                // Update plant details
                if ($updatePlant) {
                    $updatePlant->bind_param("sssss", $target_polyhouseid, $target_bed_no, $loginid, $barcode, $projectid);
                    $updatePlant->execute();
                    if ($updatePlant->affected_rows > 0) {
                        $updatedCount++;
                    }
                }
            } else {
                // Insert into plant details from sowing details
                if ($insertPlant) {
                    $insertPlant->bind_param("sssssssss", $target_locationid, $target_polyhouseid, $target_bed_no, $target_bed_no, $target_bed_no, $loginid, $loginid, $barcode, $projectid);
                    $insertPlant->execute();
                    if ($insertPlant->affected_rows > 0) {
                        $updatedCount++;
                    }
                }
            }

            // Always update sowing details if exists
            if ($updateSowing) {
                $updateSowing->bind_param("sisssi", $target_locationid, $target_polyhouseid, $target_bed_no, $loginid, $barcode, $projectid);
                $updateSowing->execute();
            }
        }

        if ($checkPlant) $checkPlant->close();
        if ($updatePlant) $updatePlant->close();
        if ($insertPlant) $insertPlant->close();
        if ($updateSowing) $updateSowing->close();

        $krfconn->commit();
        if ($updatedCount > 0) {
            echo json_encode([
                'status' => 'success', 
                'message' => "Successfully updated $updatedCount tray records in the database.",
                'updated_count' => $updatedCount,
                'verified_at' => date('d-M-Y H:i:s')
            ]);
        } else {
            echo json_encode([
                'status' => 'warning', 
                'message' => "No tray records were updated.",
                'updated_count' => 0
            ]);
        }

    } catch (Exception $e) {
        $krfconn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Transaction failed: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode($response);
