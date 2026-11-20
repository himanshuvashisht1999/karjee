<?php
error_reporting(E_ALL);
require('../../connection.php');
require('../../loginCell/functions/login_functions.php');
include("../../whatsappnotification/whatsappsmsfunctions.php");
include("../functions/orders_functions.php");
include("../functions/product_functions.php");
include("../functions/seed_functions.php");
date_default_timezone_set("Asia/Calcutta");
$action = $_GET['action'];

if ($action == "GetActiveDispatchDates") {
    $projectid = $_POST['projectid'] ?? 0;
    $sql = "SELECT DISTINCT p.dispatchdate_internal AS frsowingdate
            FROM krf_polyhouse_plant_details p
            WHERE p.projectid = ? AND p.dispatchdate_internal IS NOT NULL
              AND (p.workingstatus IS NULL OR p.workingstatus != 'Dispatched')
            ORDER BY p.dispatchdate_internal ASC";
    $stmt = $krfconn->prepare($sql);
    $stmt->bind_param("i", $projectid);
    $stmt->execute();
    $res = $stmt->get_result();
    $dates = [];
    while ($row = $res->fetch_assoc()) {
        $dates[] = $row;
    }
    echo json_encode($dates);
    exit;
} else if ($action == "GetGraftingDispatchData") {
    $projectid = $_POST['projectid'] ?? 0;
    $dateFrom = $_POST['datefrom'] ?? '';
    $dateTo = $_POST['dateto'] ?? '';        
    $sql = "
        SELECT 
            p.dispatchdate_internal AS dispatch_date,
            MAX(p.sowingdate_internal) AS sowing_date_internal,
            p.stockorder_no,
            p.productid,
            p.productname,
            p.roottype,
            p.polyhouseid,
            ph.polyhousename,
            COALESCE(NULLIF(NULLIF(p.locationid, '0'), ''), NULLIF(NULLIF(ph.locationid, '0'), '')) AS effective_location_id,
            SUM(CAST(IFNULL(p.qtypertray, 0) AS UNSIGNED)) AS total_qty,
            COUNT(p.polyhouseplantid) AS tray_count,
            IFNULL(so.quantity, 0) AS order_qty,
            MAX(p.status) AS status,
            MAX(p.workingstatus) AS workingstatus,
            IFNULL(
                CASE 
                    WHEN p.roottype = 'Scion' THEN scion_seed.SeedName
                    ELSE root_seed.SeedName
                END, 
                'N/A'
            ) AS seed_name
        FROM krf_polyhouse_plant_details p
        LEFT JOIN krf_polyhouse_master ph ON p.polyhouseid = ph.polyhouseid AND p.projectid = ph.projectid
        LEFT JOIN krf_production_stockorder so ON p.stockorder_no = so.stockorder_no AND p.projectid = so.projectid
        LEFT JOIN product_master pm ON p.productid = pm.ProductID AND p.projectid = pm.ProjectId
        LEFT JOIN seed_master root_seed ON pm.SeedId = root_seed.SeedID
        LEFT JOIN seed_master scion_seed ON pm.ScionSeedId = scion_seed.SeedID
        WHERE p.projectid = ? 
          AND p.dispatchdate_internal BETWEEN ? AND ?
          AND (p.workingstatus IS NULL OR p.workingstatus != 'Dispatched')
        GROUP BY 
            p.dispatchdate_internal,
            p.stockorder_no,
            p.productid,
            p.roottype,
            p.locationid,
            p.polyhouseid
        ORDER BY p.dispatchdate_internal ASC
    ";
    $stmt = $krfconn->prepare($sql);
    $stmt->bind_param("iss", $projectid, $dateFrom, $dateTo);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = [];
    $locIds = [];
    while ($row = $res->fetch_assoc()) {
        if (!empty($row['effective_location_id'])) $locIds[] = $row['effective_location_id'];
        $data[] = $row;
    }

    // Resolve Location Names from HRM DB
    $locMap = [];
    if (!empty($locIds)) {
        $locIds = array_values(array_unique(array_filter($locIds)));
        if (!empty($locIds)) {
            $placeholders = implode(',', array_fill(0, count($locIds), '?'));
            $targetHrmConn = (isset($hrmconn) && $hrmconn instanceof mysqli && @$hrmconn->ping()) ? $hrmconn : (isset($conn) ? $conn : $krfconn);
            $lStmt = $targetHrmConn->prepare("SELECT LocationId, LocationName FROM hrm_AttendanceLocationMaster WHERE LocationId IN ($placeholders)");
            if ($lStmt) {
                $types = str_repeat('s', count($locIds));
                $lStmt->bind_param($types, ...$locIds);
                $lStmt->execute();
                $lRes = $lStmt->get_result();
                while ($lRow = $lRes->fetch_assoc()) {
                    $locMap[(string)$lRow['LocationId']] = $lRow['LocationName'];
                }
                $lStmt->close();
            }
        }
    }

    foreach ($data as &$dItem) {
        $effLoc = (string)($dItem['effective_location_id'] ?? '');
        $dItem['location_name'] = !empty($effLoc) && isset($locMap[$effLoc]) ? $locMap[$effLoc] : (!empty($effLoc) ? $effLoc : 'Location Missing');
    }
    unset($dItem);

    echo json_encode($data);
    exit;
} else if ($action == "MarkSelectedDispatched") {
    $projectid = $_POST['projectid'] ?? 0;
    $loginid = $_POST['loginid'] ?? '';
    $items = $_POST['items'] ?? [];    
    if (empty($items)) {
        echo json_encode(["status" => "error", "message" => "No items selected."]);
        exit;
    }
    
    $krfconn->begin_transaction();
    try {
        $sql = "UPDATE krf_polyhouse_plant_details 
                SET workingstatus = 'Internal Dispatch', status = 'Internal Dispatch', updated_at = NOW(), updated_by = ?
                WHERE projectid = ? 
                  AND (stockorder_no = ? OR (stockorder_no IS NULL AND ? = ''))
                  AND (productid = ? OR (productid IS NULL AND ? = ''))
                  AND (roottype = ? OR (roottype IS NULL AND ? = ''))
                  AND (polyhouseid = ? OR (polyhouseid IS NULL AND ? = ''))
                  AND (dispatchdate_internal = ? OR (dispatchdate_internal IS NULL AND ? = ''))";
        $stmt = $krfconn->prepare($sql);
        
        foreach ($items as $item) {
            $stockorder_no = $item['stockorder_no'] ?? '';
            $productid = $item['productid'] ?? '';
            $roottype = $item['roottype'] ?? '';
            $polyhouseid = $item['polyhouseid'] ?? '';
            $dispatch_date = $item['dispatch_date'] ?? '';
            
            $types = "sissssssssss";
            $stmt->bind_param(
                $types, 
                $loginid, $projectid, 
                $stockorder_no, $stockorder_no, 
                $productid, $productid, 
                $roottype, $roottype, 
                $polyhouseid, $polyhouseid, 
                $dispatch_date, $dispatch_date
            );
            $stmt->execute();
        }
        $krfconn->commit();
        echo json_encode(["status" => "success", "message" => "Selected items marked as dispatched successfully."]);
    } catch (Exception $e) {
        $krfconn->rollback();
        echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
    }
    exit;
}else if ($action == "GetActiveGraftingDates") {
    $projectid = $_POST['projectid'] ?? 0;
    $sql = "SELECT DISTINCT p.dispatchdate_internal AS frsowingdate
            FROM krf_polyhouse_plant_details p
            WHERE p.projectid = ? AND p.dispatchdate_internal IS NOT NULL
              AND (p.workingstatus IS NULL OR p.workingstatus IN ('Dispatched', 'Polyhouse', 'Dilivered'))
            ORDER BY p.dispatchdate_internal ASC";
    $stmt = $krfconn->prepare($sql);
    $stmt->bind_param("i", $projectid);
    $stmt->execute();
    $res = $stmt->get_result();
    $dates = [];
    while ($row = $res->fetch_assoc()) {
        $dates[] = $row;
    }
    echo json_encode($dates);
    exit;
} else if ($action == "GetDataforGrafting") {
    $projectid = $_POST['projectid'] ?? 0;
    $dateFrom = $_POST['datefrom'] ?? '';
    $dateTo = $_POST['dateto'] ?? '';        
    $sql = "
        SELECT 
            p.dispatchdate_internal AS dispatch_date,
            MAX(p.sowingdate_internal) AS sowing_date_internal,
            p.stockorder_no,
            p.productid,
            p.productname,
            p.roottype,
            p.polyhouseid,
            ph.polyhousename,
            COALESCE(NULLIF(NULLIF(p.locationid, '0'), ''), NULLIF(NULLIF(ph.locationid, '0'), '')) AS effective_location_id,
            SUM(CAST(IFNULL(p.qtypertray, 0) AS UNSIGNED)) AS total_qty,
            COUNT(p.polyhouseplantid) AS tray_count,
            IFNULL(so.quantity, 0) AS order_qty,
            MAX(p.status) AS status,
            MAX(p.workingstatus) AS workingstatus,
            IFNULL(
                CASE 
                    WHEN p.roottype = 'Scion' THEN scion_seed.SeedName
                    ELSE root_seed.SeedName
                END, 
                'N/A'
            ) AS seed_name
        FROM krf_polyhouse_plant_details p
        LEFT JOIN krf_polyhouse_master ph ON p.polyhouseid = ph.polyhouseid AND p.projectid = ph.projectid
        LEFT JOIN krf_production_stockorder so ON p.stockorder_no = so.stockorder_no AND p.projectid = so.projectid
        LEFT JOIN product_master pm ON p.productid = pm.ProductID AND p.projectid = pm.ProjectId
        LEFT JOIN seed_master root_seed ON pm.SeedId = root_seed.SeedID
        LEFT JOIN seed_master scion_seed ON pm.ScionSeedId = scion_seed.SeedID
        WHERE p.projectid = ? 
          AND p.dispatchdate_internal BETWEEN ? AND ?
          AND (p.workingstatus IS NULL OR p.workingstatus != 'Dispatched')
        GROUP BY 
            p.dispatchdate_internal,
            p.stockorder_no,
            p.productid,
            p.roottype,
            p.locationid,
            p.polyhouseid
        ORDER BY p.dispatchdate_internal ASC
    ";
    $stmt = $krfconn->prepare($sql);
    $stmt->bind_param("iss", $projectid, $dateFrom, $dateTo);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = [];
    $locIds = [];
    while ($row = $res->fetch_assoc()) {
        if (!empty($row['effective_location_id'])) $locIds[] = $row['effective_location_id'];
        $data[] = $row;
    }

    // Resolve Location Names from HRM DB
    $locMap = [];
    if (!empty($locIds)) {
        $locIds = array_values(array_unique(array_filter($locIds)));
        if (!empty($locIds)) {
            $placeholders = implode(',', array_fill(0, count($locIds), '?'));
            $targetHrmConn = (isset($hrmconn) && $hrmconn instanceof mysqli && @$hrmconn->ping()) ? $hrmconn : (isset($conn) ? $conn : $krfconn);
            $lStmt = $targetHrmConn->prepare("SELECT LocationId, LocationName FROM hrm_AttendanceLocationMaster WHERE LocationId IN ($placeholders)");
            if ($lStmt) {
                $types = str_repeat('s', count($locIds));
                $lStmt->bind_param($types, ...$locIds);
                $lStmt->execute();
                $lRes = $lStmt->get_result();
                while ($lRow = $lRes->fetch_assoc()) {
                    $locMap[(string)$lRow['LocationId']] = $lRow['LocationName'];
                }
                $lStmt->close();
            }
        }
    }

    foreach ($data as &$dItem) {
        $effLoc = (string)($dItem['effective_location_id'] ?? '');
        $dItem['location_name'] = !empty($effLoc) && isset($locMap[$effLoc]) ? $locMap[$effLoc] : (!empty($effLoc) ? $effLoc : 'Location Missing');
    }
    unset($dItem);

    echo json_encode($data);
    exit;
} else if ($action == "GetActiveGerminationBoxOpeningDates") {
    $projectid = $_POST['projectid'] ?? 0;
    $sql = "SELECT DISTINCT gb.boxopeningdate AS boxopeningdate
            FROM krf_polyhouse_germinated_boxes gb
            WHERE gb.projectid = ? AND gb.boxopeningdate IS NOT NULL
            ORDER BY gb.boxopeningdate ASC";
    $stmt = $krfconn->prepare($sql);
    $stmt->bind_param("i", $projectid);
    $stmt->execute();
    $res = $stmt->get_result();
    $dates = [];
    while ($row = $res->fetch_assoc()) {
        $dates[] = $row;
    }
    echo json_encode($dates);
    exit;
} else if ($action == "GetGerminationBoxOpeningData") {
    $projectid = $_POST['projectid'] ?? 0;
    $dateFrom = $_POST['datefrom'] ?? '';
    $dateTo = $_POST['dateto'] ?? '';        
    $isOldPending = (isset($_POST['is_old_pending']) && $_POST['is_old_pending'] == '1') || (isset($_POST['fetch_type']) && $_POST['fetch_type'] == 'old_pending');

    // 1. Calculate Old Pending Box Count
    $oldPendingBoxes = 0;
    $oldPendingTrays = 0;
    if (!empty($dateFrom)) {
        $oldPendingSql = "
            SELECT 
                COUNT(boxid) AS old_pending_boxes,
                SUM(CAST(IFNULL(traycountinbox, 0) AS UNSIGNED)) AS old_pending_trays
            FROM krf_polyhouse_germinated_boxes
            WHERE projectid = ? 
              AND boxopeningdate < ? 
              AND LOWER(IFNULL(status, '')) != 'allocated'
        ";
        $opStmt = $krfconn->prepare($oldPendingSql);
        if ($opStmt) {
            $opStmt->bind_param("is", $projectid, $dateFrom);
            $opStmt->execute();
            $opRow = $opStmt->get_result()->fetch_assoc();
            $oldPendingBoxes = (int)($opRow['old_pending_boxes'] ?? 0);
            $oldPendingTrays = (int)($opRow['old_pending_trays'] ?? 0);
        }
    }

    // 2. Build Main Query
    $whereClause = "WHERE gb.projectid = ? AND gb.boxopeningdate BETWEEN ? AND ?";
    if ($isOldPending) {
        $whereClause = "WHERE gb.projectid = ? AND gb.boxopeningdate < ? AND LOWER(IFNULL(gb.status, '')) != 'allocated'";
    }

    $sql = "
        SELECT 
    gb.boxopeningdate AS opening_date,
    MAX(gb.boxpackingdate) AS packing_date,
    gb.stockorderno AS stockorder_no,
    gb.productid,
    gb.productname,
    gb.roottype,
    gb.polyhouseid,
    ph.polyhousename,
    COALESCE(
        (SELECT sch.locationid 
         FROM krf_polyhouse_sowingschedule sch 
         WHERE sch.stockorder_no = gb.stockorderno 
           AND sch.projectid = gb.projectid 
           AND (LOWER(IFNULL(sch.roottype, 'root')) = LOWER(IFNULL(gb.roottype, 'root')) OR sch.roottype IS NULL OR sch.roottype = '')
           AND NULLIF(NULLIF(sch.locationid, '0'), '') IS NOT NULL 
         ORDER BY sch.sowingschedulid DESC 
         LIMIT 1),
        (SELECT so.sowinglocationid 
         FROM krf_production_stockorder so 
         WHERE so.stockorder_no = gb.stockorderno 
           AND so.projectid = gb.projectid 
           AND NULLIF(NULLIF(so.sowinglocationid, '0'), '') IS NOT NULL 
         LIMIT 1),
        NULLIF(NULLIF(ph.locationid, '0'), '')
    ) AS effective_location_id,

    COUNT(gb.boxid) AS box_count,

    SUM(
        CAST(IFNULL(gb.traycountinbox, 0) AS UNSIGNED)
    ) AS total_trays,

    /* Allocated Boxes */
    SUM(
        CASE 
            WHEN LOWER(IFNULL(gb.status, '')) = 'allocated' 
            THEN 1 
            ELSE 0 
        END
    ) AS allocated_boxes,

    /* Received at Polyhouse Boxes */
    SUM(
        CASE 
            WHEN LOWER(IFNULL(gb.status, '')) = 'receivedatpolyhouse' 
            THEN 1 
            ELSE 0 
        END
    ) AS received_polyhouse_boxes,

    /* Pending Boxes */
    SUM(
        CASE 
            WHEN LOWER(IFNULL(gb.status, '')) IN ('pendingallocation', 'receivedaftersowing', 'pending')
                 OR gb.status IS NULL
                 OR LOWER(IFNULL(gb.status, '')) NOT IN ('allocated', 'receivedatpolyhouse')
            THEN 1 
            ELSE 0 
        END
    ) AS pending_boxes,

    IFNULL(so.quantity, 0) AS order_qty,

    IFNULL(
        (
            SELECT SUM(traycavity)
            FROM krf_polyhouse_sowingplant_details
            WHERE stockorder_no = gb.stockorderno 
              AND projectid = gb.projectid
        ), 
        0
    ) AS sowing_qty,

    MAX(gb.status) AS status,

    IFNULL(
        CASE 
            WHEN LOWER(gb.roottype) = 'scion' 
                THEN scion_seed.SeedName
            ELSE root_seed.SeedName
        END, 
        'N/A'
    ) AS seed_name,

    MAX(gb.created_by) AS allocated_by_id,
    MIN(gb.created_at) AS allocated_at,
    MAX(gb.received_by) AS received_by_id,
    MAX(gb.received_at) AS received_at

FROM krf_polyhouse_germinated_boxes gb

LEFT JOIN krf_polyhouse_master ph 
    ON gb.polyhouseid = ph.polyhouseid 
    AND gb.projectid = ph.projectid

LEFT JOIN krf_production_stockorder so 
    ON gb.stockorderno = so.stockorder_no 
    AND gb.projectid = so.projectid 
    AND LOWER(so.status) IN ('confirmed')

LEFT JOIN product_master pm 
    ON gb.productid = pm.ProductID 
    AND gb.projectid = pm.ProjectId

LEFT JOIN seed_master root_seed 
    ON pm.SeedId = root_seed.SeedID

LEFT JOIN seed_master scion_seed 
    ON pm.ScionSeedId = scion_seed.SeedID

{$whereClause}

GROUP BY 
    gb.boxopeningdate,
    gb.stockorderno,
    gb.productid,
    gb.roottype,
    gb.polyhouseid

ORDER BY 
    gb.boxopeningdate ASC
    ";

    $stmt = $krfconn->prepare($sql);
    if (!$stmt) {
        echo json_encode(["status" => "error", "message" => "Database query error: " . $krfconn->error]);
        exit;
    }
    if ($isOldPending) {
        $stmt->bind_param("is", $projectid, $dateFrom);
    } else {
        $stmt->bind_param("iss", $projectid, $dateFrom, $dateTo);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    
    $userIds = [];
    $locIds = [];
    $data = [];
    while ($row = $res->fetch_assoc()) {
        if (!empty($row['allocated_by_id'])) $userIds[] = $row['allocated_by_id'];
        if (!empty($row['received_by_id'])) $userIds[] = $row['received_by_id'];
        if (!empty($row['effective_location_id'])) $locIds[] = $row['effective_location_id'];
        $data[] = $row;
    }

    // Resolve Location Names from HRM DB
    $locMap = [];
    if (!empty($locIds)) {
        $locIds = array_values(array_unique(array_filter($locIds)));
        if (!empty($locIds)) {
            $placeholders = implode(',', array_fill(0, count($locIds), '?'));
            $targetHrmConn = (isset($hrmconn) && $hrmconn instanceof mysqli && @$hrmconn->ping()) ? $hrmconn : (isset($conn) ? $conn : $krfconn);
            $lStmt = $targetHrmConn->prepare("SELECT LocationId, LocationName FROM hrm_AttendanceLocationMaster WHERE LocationId IN ($placeholders)");
            if ($lStmt) {
                $types = str_repeat('s', count($locIds));
                $lStmt->bind_param($types, ...$locIds);
                $lStmt->execute();
                $lRes = $lStmt->get_result();
                while ($lRow = $lRes->fetch_assoc()) {
                    $locMap[(string)$lRow['LocationId']] = $lRow['LocationName'];
                }
                $lStmt->close();
            }
        }
    }

    // Resolve User Names from auth DB
    $userMap = [];
    if (!empty($userIds)) {
        $userIds = array_values(array_unique($userIds));
        $auth_db = $config['db_auth'];
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $uSql = "SELECT loginid, mobileno, loginname FROM {$auth_db}.login_user_master WHERE loginid IN ($placeholders) OR mobileno IN ($placeholders)";
        $uStmt = $authconn->prepare($uSql);
        if ($uStmt) {
            $types = str_repeat('s', count($userIds) * 2);
            $bindParams = array_merge($userIds, $userIds);
            $uStmt->bind_param($types, ...$bindParams);
            $uStmt->execute();
            $uRes = $uStmt->get_result();
            while ($uRow = $uRes->fetch_assoc()) {
                if (!empty($uRow['loginid'])) $userMap[$uRow['loginid']] = $uRow['loginname'];
                if (!empty($uRow['mobileno'])) $userMap[$uRow['mobileno']] = $uRow['loginname'];
            }
        }
    }

    // Helper to calculate human readable duration
    if (!function_exists('formatTimeDurationBoxes')) {
        function formatTimeDurationBoxes($startStr, $endStr) {
            if (empty($startStr) || empty($endStr)) return '-';
            $start = strtotime($startStr);
            $end = strtotime($endStr);
            if ($start === false || $end === false || $end < $start) return '-';
            $diffSec = $end - $start;
            $days = floor($diffSec / 86400);
            $hours = floor(($diffSec % 86400) / 3600);
            $mins = floor(($diffSec % 3600) / 60);
            
            $parts = [];
            if ($days > 0) $parts[] = "{$days}d";
            if ($hours > 0 || $days > 0) $parts[] = "{$hours}h";
            $parts[] = "{$mins}m";
            return implode(' ', $parts);
        }
    }

    foreach ($data as &$item) {
        $effLoc = (string)($item['effective_location_id'] ?? '');
        $item['location_name'] = !empty($effLoc) && isset($locMap[$effLoc]) ? $locMap[$effLoc] : (!empty($effLoc) ? $effLoc : 'Location Missing');
        $item['allocated_by'] = $userMap[$item['allocated_by_id']] ?? $item['allocated_by_id'] ?? '-';
        $item['received_by'] = $userMap[$item['received_by_id']] ?? $item['received_by_id'] ?? '-';
        $item['time_taken'] = formatTimeDurationBoxes($item['allocated_at'], $item['received_at']);
        
        // Format timestamps
        $item['formatted_allocated_at'] = !empty($item['allocated_at']) ? date('d-M-Y H:i', strtotime($item['allocated_at'])) : '-';
        $item['formatted_received_at'] = !empty($item['received_at']) ? date('d-M-Y H:i', strtotime($item['received_at'])) : '-';
    }
    unset($item);

    echo json_encode([
        "status" => "success",
        "data" => $data,
        "old_pending_boxes" => $oldPendingBoxes,
        "old_pending_trays" => $oldPendingTrays
    ]);
    exit;
}
?>