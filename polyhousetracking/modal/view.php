<?php
/** @var mysqli $krfconn */
/** @var mysqli $hrmconn */
error_reporting(1);
ini_set('display_errors', '0');
require_once(__DIR__ . '/../../connection.php');
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Kolkata');

$action = trim($_GET['action'] ?? '');

function outJson($data) {
    if (ob_get_length()) ob_clean();
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
} 
if ($action === 'getlocation') {
    $projectid = (int)($_POST['projectid'] ?? $_GET['projectid'] ?? 0);
    if ($projectid <= 0) $projectid = 17;

    $sql = "SELECT locationid, locationname
            FROM hrm_AttendanceLocationMaster
            WHERE projectid = ?
            ORDER BY locationname";
    $stmt = $hrmconn->prepare($sql);
    if (!$stmt) {
        $res = $hrmconn->query("SELECT locationid, locationname FROM hrm_AttendanceLocationMaster ORDER BY locationname");
        $data = [];
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $data[] = ['locationid' => $r['locationid'], 'locationname' => $r['locationname']];
            }
        }
        outJson(['status' => 'success', 'data' => $data]);
    }
    $pidStr = (string)$projectid;
    $stmt->bind_param('s', $pidStr);
    $stmt->execute();
    $rs = $stmt->get_result();
    $data = [];
    while ($r = $rs->fetch_assoc()) {
        $data[] = ['locationid' => $r['locationid'], 'locationname' => $r['locationname']];
    }
    $stmt->close();
    outJson(['status' => 'success', 'data' => $data]);
}

if ($action === 'polyhousemaster') {
    $projectid = (int)($_POST['projectid'] ?? 0);
    $locationid = trim($_POST['locationid'] ?? '');
    if ($projectid <= 0) outJson(['status'=>'error','message'=>'Invalid project.']);
    if ($locationid === '') outJson(['status'=>'error','message'=>'Location is required.']);

    $sql = "SELECT polyhouseid, polyhousename, beddetails
            FROM krf_polyhouse_master
            WHERE locationid = ? AND projectid = ? AND status='Active' AND (tedit IS NULL OR tedit = '')
            ORDER BY polyhousename";
    $stmt = $krfconn->prepare($sql);
    if (!$stmt) outJson(['status'=>'error','message'=>'Query prepare failed: '.$krfconn->error]);
    $stmt->bind_param('si', $locationid, $projectid);
    $stmt->execute();
    $rs=$stmt->get_result(); $data=[];
    while($r=$rs->fetch_assoc()) {
        $beds = [];
        $decoded = json_decode($r['beddetails'] ?? '[]', true);
        if (is_array($decoded)) {
            foreach ($decoded as $bed) {
                $name = trim($bed['bedname'] ?? '');
                if ($name !== '') $beds[] = $name;
            }
        }
        $r['beds'] = $beds;
        $data[] = $r;
    }
    $stmt->close();
    outJson(['status'=>'success','data'=>$data]);
}

if ($action === 'bedwisereport') {
    $projectid = (int)($_POST['projectid'] ?? 0);
    $locationid = trim($_POST['locationid'] ?? '');
    $polyhouseidRaw = trim($_POST['polyhouseid'] ?? '');
    $isAllPolyhouses = ($polyhouseidRaw === 'all');
    $polyhouseid = (int)$polyhouseidRaw;
    if ($projectid <= 0) outJson(['status'=>'error','message'=>'Invalid project.']);
    if ($locationid === '') outJson(['status'=>'error','message'=>'Location is required.']);
    if (!$isAllPolyhouses && $polyhouseid <= 0) outJson(['status'=>'error','message'=>'Polyhouse is required.']);

    $polyhouseMap = [];
    $bedConfig = [];
    $totalBeds = 0;

    if ($isAllPolyhouses) {
        $sql = "SELECT polyhouseid, polyhousename, beddetails
                FROM krf_polyhouse_master
                WHERE projectid=? AND locationid=? AND status='Active' AND (tedit IS NULL OR tedit = '')
                ORDER BY polyhousename";
        $stmt = $krfconn->prepare($sql);
        if (!$stmt) outJson(['status'=>'error','message'=>'Polyhouse query failed: '.$krfconn->error]);
        $stmt->bind_param('is', $projectid, $locationid);
        $stmt->execute();
        $resMaster = $stmt->get_result();
        while ($m = $resMaster->fetch_assoc()) {
            $phId = (string)$m['polyhouseid'];
            $polyhouseMap[$phId] = $m['polyhousename'];
            $decoded = json_decode($m['beddetails'] ?? '[]', true);
            if (is_array($decoded)) {
                foreach ($decoded as $bed) {
                    $name = trim($bed['bedname'] ?? '');
                    if ($name === '') continue;
                    $bedConfig[$phId][$name] = ['traycapacity' => $bed['traycapacity'] ?? []];
                    $totalBeds++;
                }
            }
        }
        $stmt->close();
        if (empty($polyhouseMap)) outJson(['status'=>'error','message'=>'No active polyhouses found for this location.']);
        $master = ['polyhouseid' => 'all', 'polyhousename' => 'All Polyhouses'];
    } else {
        $sql = "SELECT polyhouseid, polyhousename, beddetails
                FROM krf_polyhouse_master
                WHERE projectid=? AND locationid=? AND polyhouseid=? AND status='Active' AND (tedit IS NULL OR tedit = '')
                LIMIT 1";
        $stmt = $krfconn->prepare($sql);
        if (!$stmt) outJson(['status'=>'error','message'=>'Polyhouse query failed: '.$krfconn->error]);
        $stmt->bind_param('isi', $projectid, $locationid, $polyhouseid);
        $stmt->execute();
        $master = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$master) outJson(['status'=>'error','message'=>'Polyhouse not found.']);

        $phId = (string)$master['polyhouseid'];
        $polyhouseMap[$phId] = $master['polyhousename'];
        $decoded = json_decode($master['beddetails'] ?? '[]', true);
        if (is_array($decoded)) {
            foreach ($decoded as $bed) {
                $name = trim($bed['bedname'] ?? '');
                if ($name === '') continue;
                $bedConfig[$phId][$name] = ['traycapacity' => $bed['traycapacity'] ?? []];
                $totalBeds++;
            }
        }
    }

    $locationName = $locationid;
    $stmt = $hrmconn->prepare("SELECT locationname FROM hrm_AttendanceLocationMaster WHERE projectid=? AND locationid=? LIMIT 1");
    if ($stmt) {
        $pid = (string)$projectid;
        $stmt->bind_param('ss', $pid, $locationid);
        $stmt->execute();
        $lr = $stmt->get_result()->fetch_assoc();
        if ($lr) $locationName = $lr['locationname'];
        $stmt->close();
    }

    $polyhouseidStr = (string)$polyhouseid;
    $wherePh = $isAllPolyhouses ? "" : " AND (p.polyhouseid=? OR p.polyhouseid=?) ";
    $sql = "SELECT
                p.polyhouseid,
                p.bed_no,
                p.tray_id,
                p.stockorder_no,
                p.psono,
                p.productid,
                MAX(p.productname) AS productname,
                MAX(p.dispatchdate_internal) AS dispatchdate_internal,
                MAX(COALESCE(p.sowingdate_internal,p.sowing_date)) AS sowingdate,
                MAX(ord.expected_delivery_date) AS expected_delivery_date,
                MAX(ord.clientid) AS clientid,
                COUNT(*) AS total_trays,
                SUM(CASE 
                    WHEN p.status IN ('Polyhouse', 'Readytosale', 'Ready to sale', 'ReadyToSale', 'OpenStock', 'Open Stock')
                     AND p.status NOT IN ('Internal Dispatch', 'Final Dispatch', 'Grafting', 'Move for Grafting', 'Healing', 'Hardening', 'Clip Stick Removal', 'Removed', 'Grafted', 'Empty After Shifting', 'Empty After Grafting')
                    THEN 1 ELSE 0 END
                ) AS occupied_trays,
                SUM(CASE 
                    WHEN (p.status IN ('Readytosale', 'Ready to sale', 'ReadyToSale') OR (p.growth_stage = 'ReadyToSale' AND p.status IN ('Polyhouse', 'OpenStock', 'Open Stock')))
                     AND p.status NOT IN ('Internal Dispatch', 'Final Dispatch', 'Grafting', 'Move for Grafting', 'Healing', 'Hardening', 'Clip Stick Removal', 'Removed', 'Grafted', 'Empty After Shifting', 'Empty After Grafting')
                     AND (p.health_status = 'Healthy' OR p.health_status IS NULL OR p.health_status = '')
                    THEN 1 ELSE 0 END
                ) AS ready_to_sale_trays,
                SUM(CASE 
                    WHEN p.status IN ('Polyhouse', 'Readytosale', 'Ready to sale', 'ReadyToSale', 'OpenStock', 'Open Stock')
                     AND p.status NOT IN ('Internal Dispatch', 'Final Dispatch', 'Grafting', 'Move for Grafting', 'Healing', 'Hardening', 'Clip Stick Removal', 'Removed', 'Grafted', 'Empty After Shifting', 'Empty After Grafting')
                     AND NULLIF(TRIM(p.bed_verify),'') IS NOT NULL
                    THEN 1 ELSE 0 END
                ) AS verified_trays,
                SUM(CASE 
                    WHEN p.status IN ('Polyhouse', 'Readytosale', 'Ready to sale', 'ReadyToSale', 'OpenStock', 'Open Stock')
                     AND p.status NOT IN ('Internal Dispatch', 'Final Dispatch', 'Grafting', 'Move for Grafting', 'Healing', 'Hardening', 'Clip Stick Removal', 'Removed', 'Grafted', 'Empty After Shifting', 'Empty After Grafting')
                     AND NULLIF(TRIM(p.bed_verify),'') IS NULL
                    THEN 1 ELSE 0 END
                ) AS unverified_trays,
                MAX(p.bed_verifyat) AS verification_date,
                SUM(CASE 
                    WHEN p.status IN ('Polyhouse', 'Readytosale', 'Ready to sale', 'ReadyToSale', 'OpenStock', 'Open Stock')
                     AND p.status NOT IN ('Internal Dispatch', 'Final Dispatch', 'Grafting', 'Move for Grafting', 'Healing', 'Hardening', 'Clip Stick Removal', 'Removed', 'Grafted', 'Empty After Shifting', 'Empty After Grafting')
                     AND p.health_status='Germination Failure'
                    THEN 1 ELSE 0 END
                ) AS germination_failure_trays,
                COALESCE(SUM(CASE 
                    WHEN p.status IN ('Polyhouse', 'Readytosale', 'Ready to sale', 'ReadyToSale', 'OpenStock', 'Open Stock')
                     AND p.status NOT IN ('Internal Dispatch', 'Final Dispatch', 'Grafting', 'Move for Grafting', 'Healing', 'Hardening', 'Clip Stick Removal', 'Removed', 'Grafted', 'Empty After Shifting', 'Empty After Grafting')
                    THEN p.failurecount ELSE 0 END
                ),0) AS germination_failure_qty,
                MAX(p.shiftingstatus) AS shiftingstatus,
                MAX(p.failerpercent) AS failerpercent,
                SUM(CASE 
                    WHEN p.status IN ('Polyhouse', 'Readytosale', 'Ready to sale', 'ReadyToSale', 'OpenStock', 'Open Stock')
                     AND p.status NOT IN ('Internal Dispatch', 'Final Dispatch', 'Grafting', 'Move for Grafting', 'Healing', 'Hardening', 'Clip Stick Removal', 'Removed', 'Grafted', 'Empty After Shifting', 'Empty After Grafting')
                     AND p.shiftingstatus = 'shifted'
                    THEN 1 ELSE 0 END
                ) AS shifted_trays,
                SUM(CASE 
                    WHEN p.status IN ('Polyhouse', 'Readytosale', 'Ready to sale', 'ReadyToSale', 'OpenStock', 'Open Stock')
                     AND p.status NOT IN ('Internal Dispatch', 'Final Dispatch', 'Grafting', 'Move for Grafting', 'Healing', 'Hardening', 'Clip Stick Removal', 'Removed', 'Grafted', 'Empty After Shifting', 'Empty After Grafting')
                     AND (p.shiftingstatus = 'nonshifted' OR p.shiftingstatus IS NULL OR p.shiftingstatus = '')
                    THEN 1 ELSE 0 END
                ) AS nonshifted_trays,
                SUM(CASE 
                    WHEN p.status IN ('Polyhouse', 'Readytosale', 'Ready to sale', 'ReadyToSale', 'OpenStock', 'Open Stock')
                     AND p.status NOT IN ('Internal Dispatch', 'Final Dispatch', 'Grafting', 'Move for Grafting', 'Healing', 'Hardening', 'Clip Stick Removal', 'Removed', 'Grafted', 'Empty After Shifting', 'Empty After Grafting')
                     AND (p.health_status = 'Healthy' OR p.health_status IS NULL OR p.health_status = '')
                    THEN 1 ELSE 0 END
                ) AS healthy_trays,
                SUM(CASE 
                    WHEN p.status IN ('Polyhouse', 'Readytosale', 'Ready to sale', 'ReadyToSale', 'OpenStock', 'Open Stock')
                     AND p.status NOT IN ('Internal Dispatch', 'Final Dispatch', 'Grafting', 'Move for Grafting', 'Healing', 'Hardening', 'Clip Stick Removal', 'Removed', 'Grafted', 'Empty After Shifting', 'Empty After Grafting')
                     AND p.health_status IN ('Unhealthy','Weak','Diseased','Dead','Infested')
                    THEN 1 ELSE 0 END
                ) AS unhealthy_trays,
                SUM(CASE WHEN p.status = 'Polyhouse' THEN 1 ELSE 0 END) AS count_polyhouse,
                SUM(CASE 
                    WHEN (p.status IN ('Readytosale', 'Ready to sale', 'ReadyToSale') OR (p.growth_stage = 'ReadyToSale' AND p.status IN ('Polyhouse', 'OpenStock', 'Open Stock')))
                     AND p.status NOT IN ('Internal Dispatch', 'Final Dispatch', 'Grafting', 'Move for Grafting', 'Healing', 'Hardening', 'Clip Stick Removal', 'Removed', 'Grafted', 'Empty After Shifting', 'Empty After Grafting')
                     AND (p.health_status = 'Healthy' OR p.health_status IS NULL OR p.health_status = '')
                    THEN 1 ELSE 0 END
                ) AS count_readytosale,
                SUM(CASE WHEN p.status IN ('OpenStock', 'Open Stock') THEN 1 ELSE 0 END) AS count_openstock,
                SUM(CASE WHEN p.status = 'Internal Dispatch' THEN 1 ELSE 0 END) AS count_internal_dispatch,
                SUM(CASE WHEN p.status IN ('Loaded', 'Loading') THEN 1 ELSE 0 END) AS count_loaded,
                SUM(CASE WHEN p.status = 'Final Dispatch' THEN 1 ELSE 0 END) AS count_final_dispatch,
                SUM(CASE WHEN p.status = 'Move for Opensale' THEN 1 ELSE 0 END) AS count_move_for_opensale,
                SUM(CASE WHEN p.status = 'Move for Grafting' THEN 1 ELSE 0 END) AS count_move_for_grafting,
                SUM(CASE WHEN p.status = 'Grafting' THEN 1 ELSE 0 END) AS count_grafting,
                SUM(CASE WHEN p.status = 'Healing' THEN 1 ELSE 0 END) AS count_healing,
                SUM(CASE WHEN p.status = 'Hardening' THEN 1 ELSE 0 END) AS count_hardening,
                SUM(CASE WHEN p.status = 'Clip Stick Removal' THEN 1 ELSE 0 END) AS count_clip_stick_removal,
                SUM(CASE WHEN p.status = 'Empty After Shifting' THEN 1 ELSE 0 END) AS count_empty_after_shifting,
                SUM(CASE WHEN p.status = 'Empty After Grafting' THEN 1 ELSE 0 END) AS count_empty_after_grafting,
                SUM(CASE WHEN p.status = 'Removed' THEN 1 ELSE 0 END) AS count_removed,
                SUM(CASE WHEN p.status = 'Grafted' THEN 1 ELSE 0 END) AS count_grafted
            FROM krf_polyhouse_plant_details p
            LEFT JOIN krf_production_stockorder ord
              ON ord.projectid=p.projectid
             AND ord.stockorder_no=p.stockorder_no
             AND ord.productid=p.productid
             AND (ord.tedit IS NULL OR ord.tedit = '')
            WHERE p.projectid=?
              AND p.locationid=?
              {$wherePh}
              AND (p.tedit IS NULL OR p.tedit = '')
              AND p.bed_no IS NOT NULL
              AND TRIM(p.bed_no)<>''
              AND p.status != 'Removed'
              AND p.workingstatus != 'Removed'
              AND (p.status IN ('Polyhouse','Grafted','Grafting','Move for Grafting','Healing','Hardening','Clip Stick Removal','Readytosale','Ready to sale','ReadyToSale','OpenStock','Open Stock','Internal Dispatch','Final Dispatch','Loaded','Loading','ReadyToDispatch','Empty After Shifting','Empty After Grafting','Move for Opensale') OR p.workingstatus = 'Polyhouse')
            GROUP BY p.polyhouseid, p.bed_no, p.tray_id, p.stockorder_no, p.psono, p.productid
            HAVING occupied_trays > 0
            ORDER BY " . ($isAllPolyhouses ? "p.polyhouseid, " : "") . "CAST(REPLACE(REPLACE(p.bed_no,'Bed-',''),'Bed ','') AS UNSIGNED), p.stockorder_no, productname";
    $stmt = $krfconn->prepare($sql);
    if (!$stmt) outJson(['status'=>'error','message'=>'Report query failed: '.$krfconn->error]);
    if ($isAllPolyhouses) {
        $stmt->bind_param('is', $projectid, $locationid);
    } else {
        $stmt->bind_param('isss', $projectid, $locationid, $polyhouseidStr, $polyhouseid);
    }
    $stmt->execute();
    $rs = $stmt->get_result();

    $beds = [];

    // Polyhouse snapshot summary.
    // Total beds comes from the physical Polyhouse master (beddetails), not from stock rows.
    $occupiedBedNos = [];
    $productIds = [];
    $totalTrays = 0;
    $totalOccupiedTrays = 0;
    $readyToSaleTrays = 0;
    $verifiedTrays = 0;
    $unverifiedTrays = 0;
    $germinationFailureTrays = 0;
    $totalHealthyTrays = 0;
    $totalUnhealthyTrays = 0;
    $totalShiftedTrays = 0;
    $totalNonShiftedTrays = 0;

    $rawRows = [];
    $clientIds = [];
    $psos = [];
    $missingSos = [];

    while($r = $rs->fetch_assoc()){
        $rawRows[] = $r;
        $cid = trim((string)($r['clientid'] ?? ''));
        if ($cid !== '') {
            $clientIds[$cid] = true;
        } else {
            $so = trim((string)($r['stockorder_no'] ?? ''));
            if ($so !== '') $missingSos[$so] = true;
        }
        $pso = trim((string)($r['psono'] ?? ''));
        if ($pso !== '') $psos[$pso] = true;
    }

    $clientMap = [];
    if (!empty($clientIds)) {
        $cidsIn = "'" . implode("','", array_map([$krfconn, 'real_escape_string'], array_keys($clientIds))) . "'";
        $resCl = $krfconn->query("SELECT client_id, client_name FROM krf_client_master WHERE client_id IN ($cidsIn)");
        if ($resCl) {
            while ($c = $resCl->fetch_assoc()) {
                $clientMap[$c['client_id']] = $c['client_name'];
            }
        }
    }

    $psoClientMap = [];
    if (!empty($psos)) {
        $psosIn = "'" . implode("','", array_map([$krfconn, 'real_escape_string'], array_keys($psos))) . "'";
        $resPso = $krfconn->query("SELECT psono, clientname FROM krf_provisional_sales_order WHERE psono IN ($psosIn) AND (tedit IS NULL OR tedit = '')");
        if ($resPso) {
            while ($p = $resPso->fetch_assoc()) {
                $psoClientMap[$p['psono']] = $p['clientname'];
            }
        }
    }

    $soClientMap = [];
    if (!empty($missingSos)) {
        $cleanSos = [];
        foreach (array_keys($missingSos) as $s) {
            $cleanSos[] = $s;
            $cleanSos[] = preg_replace('/^(RSO|OS)-/i', '', $s);
        }
        $sosIn = "'" . implode("','", array_map([$krfconn, 'real_escape_string'], array_unique($cleanSos))) . "'";
        $resStk = $krfconn->query("SELECT stk.stockorder_no, stk.clientid, cl.client_name 
                                   FROM krf_production_stockorder stk
                                   LEFT JOIN krf_client_master cl ON cl.projectid = stk.projectid AND cl.client_id = stk.clientid
                                   WHERE stk.stockorder_no IN ($sosIn)");
        if ($resStk) {
            while ($st = $resStk->fetch_assoc()) {
                $soClientMap[$st['stockorder_no']] = $st['client_name'] ?: $st['clientid'];
            }
        }
    }

    foreach($rawRows as $r){
        $phId = (string)$r['polyhouseid'];
        $bedNo = trim($r['bed_no']);
        $trayId = (string)$r['tray_id'];
        $occTrays = (int)$r['occupied_trays'];
        if ($occTrays <= 0) continue;
        $rtsTrays = min((int)$r['ready_to_sale_trays'], $occTrays);
        $hlthTrays = min((int)($r['healthy_trays'] ?? 0), $occTrays);
        $unhlthTrays = min((int)($r['unhealthy_trays'] ?? 0), $occTrays);
        $shftTrays = min((int)($r['shifted_trays'] ?? 0), $occTrays);
        $nonShftTrays = min((int)($r['nonshifted_trays'] ?? 0), $occTrays);
        $verTrays = min((int)$r['verified_trays'], $occTrays);
        $unverTrays = min((int)$r['unverified_trays'], $occTrays);
        $germTrays = min((int)$r['germination_failure_trays'], $occTrays);

        // Summary values are calculated from the complete selected Polyhouse snapshot.
        // A physical bed is counted as occupied only if it physically contains active occupied trays.
        $occBedKey = $phId . '|' . $bedNo;
        if ($bedNo !== '' && $occTrays > 0) $occupiedBedNos[$occBedKey] = true;
        $productIdKey = trim((string)($r['productid'] ?? ''));
        if ($productIdKey !== '' && $occTrays > 0) $productIds[$productIdKey] = true;
        $totalTrays += (int)$r['total_trays'];
        $totalOccupiedTrays += $occTrays;
        $readyToSaleTrays += $rtsTrays;
        $verifiedTrays += $verTrays;
        $unverifiedTrays += $unverTrays;
        $germinationFailureTrays += $germTrays;
        $totalHealthyTrays += $hlthTrays;
        $totalUnhealthyTrays += $unhlthTrays;
        $totalShiftedTrays += $shftTrays;
        $totalNonShiftedTrays += $nonShftTrays;

        $cfg = $bedConfig[$phId][$bedNo]['traycapacity'][$trayId] ?? [];
        $trayName = $cfg['tray_name'] ?? ('Tray '.$trayId);
        $capacity = (int)($cfg['capacity'] ?? 0);
        $capacityFound = isset($cfg['capacity']);
        $key = $phId . '|' . $bedNo . '|' . $trayId;
        if(!isset($beds[$key])) $beds[$key]=[
            'polyhouseid'=>$phId,
            'polyhousename'=>$polyhouseMap[$phId] ?? ('Polyhouse ' . $phId),
            'bed_no'=>$bedNo,
            'tray_id'=>$r['tray_id'],
            'tray_name'=>$trayName,
            'capacity'=>$capacity,
            'capacity_found'=>$capacityFound,
            'orders'=>[]
        ];

        $cid = trim((string)($r['clientid'] ?? ''));
        $cName = $clientMap[$cid] ?? ($psoClientMap[$r['psono']] ?? ($soClientMap[$r['stockorder_no']] ?? ''));
        if (empty($cName) && !empty($cid)) {
            $cName = $cid;
        }

        $beds[$key]['orders'][]=[
            'stockorder_no'=>$r['stockorder_no'],
            'psono'=>$r['psono'],
            'clientid'=>$cid,
            'client_name'=>$cName,
            'productid'=>$r['productid'],
            'productname'=>$r['productname'],
            'dispatchdate_internal'=>$r['dispatchdate_internal'],
            'sowingdate'=>$r['sowingdate'],
            'expected_delivery_date'=>$r['expected_delivery_date'],
            'total_trays'=>(int)$r['total_trays'],
            'occupied_trays'=>$occTrays,
            'ready_to_sale_trays'=>$rtsTrays,
            'verified_trays'=>$verTrays,
            'unverified_trays'=>$unverTrays,
            'verification_date'=>$r['verification_date'],
            'germination_failure_trays'=>$germTrays,
            'germination_failure_qty'=>($occTrays > 0 ? (int)$r['germination_failure_qty'] : 0),
            'shifted_trays'=>$shftTrays,
            'nonshifted_trays'=>$nonShftTrays,
            'shiftingstatus'=>$r['shiftingstatus'] ?: 'nonshifted',
            'failerpercent'=>(float)($r['failerpercent'] ?? 0),
            'healthy_trays'=>$hlthTrays,
            'unhealthy_trays'=>$unhlthTrays,
            'status_counts'=>[
                'Polyhouse' => min((int)$r['count_polyhouse'], $occTrays),
                'Ready to Sale' => $rtsTrays,
                'Open Stock' => min((int)$r['count_openstock'], $occTrays),
                'Internal Dispatch' => (int)$r['count_internal_dispatch'],
                'Loaded' => (int)$r['count_loaded'],
                'Final Dispatch' => (int)$r['count_final_dispatch'],
                'Move for Opensale' => (int)$r['count_move_for_opensale'],
                'Move for Grafting' => (int)$r['count_move_for_grafting'],
                'Grafting' => (int)$r['count_grafting'],
                'Healing' => (int)$r['count_healing'],
                'Hardening' => (int)$r['count_hardening'],
                'Clip Stick Removal' => (int)$r['count_clip_stick_removal'],
                'Empty After Shifting' => (int)$r['count_empty_after_shifting'],
                'Empty After Grafting' => (int)$r['count_empty_after_grafting'],
                'Removed' => (int)$r['count_removed'],
                'Grafted' => (int)$r['count_grafted']
            ]
        ];
    }
    $stmt->close();

    $occupiedBeds = count($occupiedBedNos);
    $unoccupiedBeds = max(0, $totalBeds - $occupiedBeds);

    outJson([
        'status'=>'success',
        // Snapshot timestamp: represents when the displayed report data was fetched.
        // The UI/Excel/PDF reuse this value until the user refreshes or reloads the report.
        'generated_on'=>date('d-M-Y h:i A'),
        'location'=>['locationid'=>$locationid,'locationname'=>$locationName],
        'polyhouse'=>['polyhouseid'=>$master['polyhouseid'],'polyhousename'=>$master['polyhousename']],
        'summary'=>[
            'total_beds'=>$totalBeds,
            'occupied_beds'=>$occupiedBeds,
            'unoccupied_beds'=>$unoccupiedBeds,
            // Plant Count means distinct Product IDs physically present in the selected Polyhouse snapshot.
            'plant_count'=>count($productIds),
            'total_trays'=>$totalTrays,
            'occupied_trays'=>$totalOccupiedTrays,
            'ready_to_sale_trays'=>$readyToSaleTrays,
            'verified_trays'=>$verifiedTrays,
            'unverified_trays'=>$unverifiedTrays,
            'germination_failure_trays'=>$germinationFailureTrays,
            'healthy_trays'=>$totalHealthyTrays,
            'unhealthy_trays'=>$totalUnhealthyTrays,
            'shifted_trays'=>$totalShiftedTrays,
            'nonshifted_trays'=>$totalNonShiftedTrays
        ],
        'beds'=>array_values($beds)
    ]);
}

if ($action === 'getreadystockmasters') {
    $projectid = (int)($_POST['projectid'] ?? 0);
    if ($projectid <= 0) outJson(['status'=>'error','message'=>'Invalid project.']);

    // Locations
    $pidStr = (string)$projectid;
    $locStmt = $hrmconn->prepare("SELECT locationid, locationname FROM hrm_AttendanceLocationMaster WHERE projectid = ? ORDER BY locationname");
    $locations = [];
    if ($locStmt) {
        $locStmt->bind_param('s', $pidStr);
        $locStmt->execute();
        $locRes = $locStmt->get_result();
        while ($r = $locRes->fetch_assoc()) $locations[] = $r;
        $locStmt->close();
    }

    // Products (active for this project)
    $prodStmt = $krfconn->prepare("SELECT ProductID, ProductName, IsGrafted, sku FROM product_master WHERE (Status = 'Active' OR Status = 'active') AND ProjectId = ? ORDER BY ProductName");
    $products = [];
    if ($prodStmt) {
        $prodStmt->bind_param('s', $pidStr);
        $prodStmt->execute();
        $prodRes = $prodStmt->get_result();
        while ($r = $prodRes->fetch_assoc()) $products[] = $r;
        $prodStmt->close();
    }

    // Trays
    $trayStmt = $krfconn->prepare("SELECT tray_id, tray_code, tray_name, cavity_count FROM krf_tray_master WHERE status = 'Active' OR status = '1' OR status = '' OR status IS NULL ORDER BY cavity_count ASC, tray_name ASC");
    $trays = [];
    if ($trayStmt) {
        $trayStmt->execute();
        $trayRes = $trayStmt->get_result();
        while ($r = $trayRes->fetch_assoc()) $trays[] = $r;
        $trayStmt->close();
    }

    outJson([
        'status' => 'success',
        'locations' => $locations,
        'products' => $products,
        'trays' => $trays
    ]);
}

if ($action === 'getpolyhouses') {
    $projectid = (int)($_POST['projectid'] ?? 0);
    $locationid = trim($_POST['locationid'] ?? '');
    if ($projectid <= 0) outJson(['status'=>'error','message'=>'Invalid project.']);
    if ($locationid === '') outJson(['status'=>'error','message'=>'Location is required.']);

    $sql = "SELECT polyhouseid, polyhousename, beddetails
            FROM krf_polyhouse_master
            WHERE locationid = ? AND projectid = ? AND status='Active'
            ORDER BY polyhousename";
    $stmt = $krfconn->prepare($sql);
    if (!$stmt) outJson(['status'=>'error','message'=>'Query prepare failed: '.$krfconn->error]);
    $stmt->bind_param('si', $locationid, $projectid);
    $stmt->execute();
    $rs = $stmt->get_result();
    $data = [];
    while ($r = $rs->fetch_assoc()) {
        $beds = [];
        $decoded = json_decode($r['beddetails'] ?? '[]', true);
        if (is_array($decoded)) {
            foreach ($decoded as $bed) {
                $name = trim($bed['bedname'] ?? '');
                if ($name !== '') $beds[] = $name;
            }
        }
        $r['beds'] = $beds;
        $data[] = $r;
    }
    $stmt->close();
    outJson(['status'=>'success', 'data'=>$data]);
}

if ($action === 'checkbillno') {
    try {
        $projectid = (int)($_POST['projectid'] ?? $_SESSION['projectid'] ?? 0);
        if ($projectid <= 0) $projectid = 17;
        $bill_no = trim($_POST['bill_no'] ?? '');
        $stock_type = trim($_POST['stock_type'] ?? 'readystock');

        if ($bill_no === '') {
            outJson(['status' => 'error', 'message' => 'Please enter a bill or stock number.']);
        }

        $cleanBillNo = preg_replace('/^(RSO|OS)-/i', '', $bill_no);
        $prefix = ($stock_type === 'openstock') ? 'OS-' : 'RSO-';
        $stockorder_no = $prefix . $cleanBillNo;
        $psono = $stockorder_no;

        // Check 1: krf_production_stockorder
        $sql = "SELECT id, psono, stockorder_no 
                FROM krf_production_stockorder 
                WHERE projectid = ? 
                  AND (po_number = ? OR po_number = ? OR psono = ? OR stockorder_no = ? OR psono = ? OR stockorder_no = ?)
                  AND (tedit IS NULL OR tedit = '')
                LIMIT 1";
        $stmt = $krfconn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('issssss', $projectid, $bill_no, $cleanBillNo, $psono, $stockorder_no, $bill_no, $cleanBillNo);
            $stmt->execute();
            $soRes = $stmt->get_result();
            $soData = $soRes ? $soRes->fetch_assoc() : null;
            $stmt->close();

            if ($soData) {
                $foundOrderNo = !empty($soData['stockorder_no']) ? $soData['stockorder_no'] : $stockorder_no;
                outJson([
                    'status' => 'success',
                    'exists' => true,
                    'available' => false,
                    'stockorder_no' => $foundOrderNo,
                    'message' => "Stock Number '{$bill_no}' ({$foundOrderNo}) already exists! Not available."
                ]);
            }
        }

        // Check 2: krf_polyhouse_plant_details
        $pSql = "SELECT polyhouseplantid, stockorder_no, psono 
                 FROM krf_polyhouse_plant_details
                 WHERE projectid = ? 
                   AND (stockorder_no = ? OR stockorder_no = ? OR psono = ? OR psono = ? OR lotno = ? OR lotno = ? OR batchno = ? OR sowingbatchno = ?)
                   AND (tedit IS NULL OR tedit = '')
                 LIMIT 1";
        $pStmt = $krfconn->prepare($pSql);
        if ($pStmt) {
            $pStmt->bind_param('issssssss', $projectid, $stockorder_no, $cleanBillNo, $psono, $cleanBillNo, $bill_no, $cleanBillNo, $bill_no, $bill_no);
            $pStmt->execute();
            $pRes = $pStmt->get_result();
            $pData = $pRes ? $pRes->fetch_assoc() : null;
            $pStmt->close();
            if ($pData) {
                $foundOrderNo = !empty($pData['stockorder_no']) ? $pData['stockorder_no'] : $stockorder_no;
                outJson([
                    'status' => 'success',
                    'exists' => true,
                    'available' => false,
                    'stockorder_no' => $foundOrderNo,
                    'message' => "Stock Number '{$bill_no}' ({$foundOrderNo}) already exists in polyhouse beds! Not available."
                ]);
            }
        }

        // Check 3: krf_readytosale_stocks (column is `id`, NOT `readystockid`)
        $rSql = "SELECT id, stockorder_no 
                 FROM krf_readytosale_stocks 
                 WHERE projectid = ? 
                   AND (stockorder_no = ? OR stockorder_no = ? OR psono = ?)
                   AND (tedit IS NULL OR tedit = '')
                 LIMIT 1";
        $rStmt = $krfconn->prepare($rSql);
        if ($rStmt) {
            $rStmt->bind_param('isss', $projectid, $stockorder_no, $cleanBillNo, $psono);
            $rStmt->execute();
            $rRes = $rStmt->get_result();
            $rData = $rRes ? $rRes->fetch_assoc() : null;
            $rStmt->close();
            if ($rData) {
                $foundOrderNo = !empty($rData['stockorder_no']) ? $rData['stockorder_no'] : $stockorder_no;
                outJson([
                    'status' => 'success',
                    'exists' => true,
                    'available' => false,
                    'stockorder_no' => $foundOrderNo,
                    'message' => "Stock Number '{$bill_no}' ({$foundOrderNo}) already exists in ready to sale stocks! Not available."
                ]);
            }
        }

        // Check 4: krf_opensale_stocks
        $osSql = "SELECT id, stockorder_no 
                  FROM krf_opensale_stocks 
                  WHERE projectid = ? 
                    AND (stockorder_no = ? OR stockorder_no = ? OR psono = ?)
                    AND (tedit IS NULL OR tedit = '')
                  LIMIT 1";
        $osStmt = $krfconn->prepare($osSql);
        if ($osStmt) {
            $osStmt->bind_param('isss', $projectid, $stockorder_no, $cleanBillNo, $psono);
            $osStmt->execute();
            $osRes = $osStmt->get_result();
            $osData = $osRes ? $osRes->fetch_assoc() : null;
            $osStmt->close();
            if ($osData) {
                $foundOrderNo = !empty($osData['stockorder_no']) ? $osData['stockorder_no'] : $stockorder_no;
                outJson([
                    'status' => 'success',
                    'exists' => true,
                    'available' => false,
                    'stockorder_no' => $foundOrderNo,
                    'message' => "Stock Number '{$bill_no}' ({$foundOrderNo}) already exists in open sale stocks! Not available."
                ]);
            }
        }

        // Stock number is completely available
        outJson([
            'status' => 'success',
            'exists' => false,
            'available' => true,
            'stockorder_no' => $stockorder_no,
            'message' => "Stock Number '{$bill_no}' is available."
        ]);
    } catch (Throwable $e) {
        outJson([
            'status' => 'error',
            'message' => 'Error checking stock availability: ' . $e->getMessage()
        ]);
    }
}

if ($action === 'addreadystock') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $projectid = (int)($_POST['projectid'] ?? 0);
    $locationid = trim($_POST['locationid'] ?? '');
    $polyhouseid = (int)($_POST['polyhouseid'] ?? 0);
    $bed_no = trim($_POST['bed_no'] ?? '');
    $productid = (int)($_POST['productid'] ?? 0);
    $bill_no = trim($_POST['bill_no'] ?? '');
    $dispatch_date = trim($_POST['dispatch_date'] ?? '');
    $tray_position = trim($_POST['tray_position'] ?? 'Start');
    $stock_type = trim($_POST['stock_type'] ?? 'readystock');
    $created_by = trim($_POST['loginid'] ?? $_SESSION['loginname'] ?? $_SESSION['loginid'] ?? 'System');
    $loginroleid = (int)($_SESSION['loginroleid'] ?? $_POST['loginroleid'] ?? 0);
    if ($loginroleid > 3) {
        outJson(['status'=>'error', 'message'=>'Permission denied. Only authorized roles can add ready stock.']);
    }

    if ($projectid <= 0) outJson(['status'=>'error', 'message'=>'Invalid project ID.']);
    if ($locationid === '') outJson(['status'=>'error', 'message'=>'Please select a location.']);
    if ($polyhouseid <= 0) outJson(['status'=>'error', 'message'=>'Please select a polyhouse.']);
    if ($bed_no === '') outJson(['status'=>'error', 'message'=>'Please select a bed.']);
    if ($productid <= 0) outJson(['status'=>'error', 'message'=>'Please select a product.']);
    if ($dispatch_date === '') outJson(['status'=>'error', 'message'=>'Please enter Dispatched / Expected Delivery Date.']);

    // Fetch Company & Subcompany
    $companyid = (int)($_POST['companyid'] ?? $_SESSION['companyid'] ?? 0);
    $subcompanyid = (int)($_POST['subcompanyid'] ?? $_SESSION['subcompanyid'] ?? 0);
    if ($companyid <= 0 || $subcompanyid <= 0) {
        $locCompStmt = $hrmconn->prepare("SELECT CompanyID, SubCompanyID FROM hrm_AttendanceLocationMaster WHERE ProjectID = ? AND CompanyID > 0 LIMIT 1");
        if ($locCompStmt) {
            $pidStr = (string)$projectid;
            $locCompStmt->bind_param('s', $pidStr);
            $locCompStmt->execute();
            $compRow = $locCompStmt->get_result()->fetch_assoc();
            if ($compRow) {
                if ($companyid <= 0) $companyid = (int)($compRow['CompanyID'] ?? 0);
                if ($subcompanyid <= 0) $subcompanyid = (int)($compRow['SubCompanyID'] ?? 0);
            }
            $locCompStmt->close();
        }
    }
    if ($companyid <= 0 || $subcompanyid <= 0) {
        $soCompStmt = $krfconn->prepare("SELECT companyid, subcompanyid FROM krf_provisional_sales_order WHERE projectid = ? AND companyid > 0 LIMIT 1");
        if ($soCompStmt) {
            $soCompStmt->bind_param('i', $projectid);
            $soCompStmt->execute();
            $soCompRow = $soCompStmt->get_result()->fetch_assoc();
            if ($soCompRow) {
                if ($companyid <= 0) $companyid = (int)($soCompRow['companyid'] ?? 0);
                if ($subcompanyid <= 0) $subcompanyid = (int)($soCompRow['subcompanyid'] ?? 0);
            }
            $soCompStmt->close();
        }
    }

    // Fetch Product Details
    $pStmt = $krfconn->prepare("SELECT ProductID, ProductName, IsGrafted, sku FROM product_master WHERE ProductID = ? LIMIT 1");
    $pStmt->bind_param('i', $productid);
    $pStmt->execute();
    $product = $pStmt->get_result()->fetch_assoc();
    $pStmt->close();
    if (!$product) outJson(['status'=>'error', 'message'=>'Selected product not found.']);

    $isNonGrafted = (strcasecmp($product['IsGrafted'] ?? '', 'NonGrafted') === 0);
    $productName = trim($product['ProductName'] ?? '');
    $variety = $productName;

    // Fetch Location Name
    $locationName = $locationid;
    $locStmt = $hrmconn->prepare("SELECT locationname FROM hrm_AttendanceLocationMaster WHERE projectid = ? AND locationid = ? LIMIT 1");
    if ($locStmt) {
        $pidStr = (string)$projectid;
        $locStmt->bind_param('ss', $pidStr, $locationid);
        $locStmt->execute();
        $lr = $locStmt->get_result()->fetch_assoc();
        if ($lr && !empty($lr['locationname'])) $locationName = $lr['locationname'];
        $locStmt->close();
    }

    // Determine Client ID
    $soClient = ($stock_type === 'openstock') ? 'OpenStock' : 'ReadyStock';
    $traderStmt = $krfconn->prepare("SELECT client_id, client_name, CompanyId, SubCompanyId FROM krf_client_master WHERE is_trader = 1 AND ProjectId = ? LIMIT 1");
    if ($traderStmt) {
        $traderStmt->bind_param('i', $projectid);
        $traderStmt->execute();
        $traderRes = $traderStmt->get_result()->fetch_assoc();
        if ($traderRes && !empty($traderRes['client_id'])) {
            $soClient = $traderRes['client_id'];
            if ($companyid <= 0 && !empty($traderRes['CompanyId'])) $companyid = (int)$traderRes['CompanyId'];
            if ($subcompanyid <= 0 && !empty($traderRes['SubCompanyId'])) $subcompanyid = (int)$traderRes['SubCompanyId'];
        }
        $traderStmt->close();
    }

    $datePrefix = date('ymd');

    // =========================================================================
    // UNIFIED STOCK PROCESSING (READY STOCK & OPEN STOCK)
    // =========================================================================
    $sowing_date = date('Y-m-d'); // Auto-set to today (field removed from form)
    $seed_lot_no = ''; // Auto-set to empty (field removed from form)
    $no_of_trays = (int)($_POST['no_of_trays'] ?? $_POST['root_no_of_trays'] ?? 0);
    $tray_id = (int)($_POST['tray_id'] ?? $_POST['root_tray_id'] ?? 0);

    if ($bill_no === '') {
        if ($stock_type === 'openstock') {
            $bill_no = 'OS-' . date('YmdHis');
        } else {
            outJson(['status'=>'error', 'message'=>'Please enter Bill No.']);
        }
    }

    if ($no_of_trays <= 0) outJson(['status'=>'error', 'message'=>'No of Trays must be at least 1.']);
    if ($tray_id <= 0) outJson(['status'=>'error', 'message'=>'Please select a Tray Cavity / Type.']);

    $sourceType = $isNonGrafted ? 'Seed' : 'Graft';

    // Fetch Tray Details
    $tStmt = $krfconn->prepare("SELECT tray_id, tray_code, tray_name, cavity_count FROM krf_tray_master WHERE tray_id = ? LIMIT 1");
    $tStmt->bind_param('i', $tray_id);
    $tStmt->execute();
    $tray = $tStmt->get_result()->fetch_assoc();
    $tStmt->close();
    if (!$tray) outJson(['status'=>'error', 'message'=>'Selected tray type not found.']);

    $trayCode = trim($tray['tray_code'] ?? '');
    $qtyPerTray = (int)($tray['cavity_count'] ?? 0);
    if ($qtyPerTray <= 0) $qtyPerTray = 1;

    $krfconn->begin_transaction();
    try {
        $cleanBillNo = preg_replace('/^(RSO|OS)-/i', '', $bill_no);
        $prefix = ($stock_type === 'openstock') ? 'OS-' : 'RSO-';
        $stockOrderNo = $prefix . $cleanBillNo;
        $psono = $stockOrderNo;
        $totalPlants = $no_of_trays * $qtyPerTray;

        // 1. Strict Check: Ensure stock order number does not already exist
        $chkSoStmt = $krfconn->prepare("SELECT id, stockorder_no, psono FROM krf_production_stockorder WHERE projectid = ? AND (po_number = ? OR po_number = ? OR psono = ? OR stockorder_no = ? OR psono = ? OR stockorder_no = ?) AND (tedit IS NULL OR tedit = '') LIMIT 1");
        $chkSoStmt->bind_param('issssss', $projectid, $bill_no, $cleanBillNo, $psono, $stockOrderNo, $bill_no, $cleanBillNo);
        $chkSoStmt->execute();
        $existingSo = $chkSoStmt->get_result()->fetch_assoc();
        $chkSoStmt->close();

        if ($existingSo) {
            $existingNo = !empty($existingSo['stockorder_no']) ? $existingSo['stockorder_no'] : $stockOrderNo;
            throw new Exception("Stock Order Number '{$bill_no}' ({$existingNo}) already exists! Adding ready stock with an already existing stock number is strictly not allowed.");
        }

        // Also check if trays exist in plant details for this stockorder_no or bill_no
        $chkPlStmt = $krfconn->prepare("SELECT polyhouseplantid, stockorder_no FROM krf_polyhouse_plant_details WHERE projectid = ? AND (stockorder_no = ? OR stockorder_no = ? OR psono = ? OR psono = ? OR lotno = ? OR lotno = ? OR batchno = ? OR sowingbatchno = ?) AND (tedit IS NULL OR tedit = '') LIMIT 1");
        $chkPlStmt->bind_param('issssssss', $projectid, $stockOrderNo, $cleanBillNo, $psono, $cleanBillNo, $bill_no, $cleanBillNo, $bill_no, $bill_no);
        $chkPlStmt->execute();
        $existingPl = $chkPlStmt->get_result()->fetch_assoc();
        $chkPlStmt->close();
        if ($existingPl) {
            $existingNo = !empty($existingPl['stockorder_no']) ? $existingPl['stockorder_no'] : $stockOrderNo;
            throw new Exception("Stock Number '{$bill_no}' ({$existingNo}) has already been allocated to trays in Polyhouse! Please use a unique stock number.");
        }

        // Check in ready to sale stocks
        $chkRsStmt = $krfconn->prepare("SELECT stockorder_no FROM krf_readytosale_stocks WHERE projectid = ? AND (stockorder_no = ? OR stockorder_no = ? OR psono = ?) LIMIT 1");
        $chkRsStmt->bind_param('isss', $projectid, $stockOrderNo, $cleanBillNo, $psono);
        $chkRsStmt->execute();
        $existingRs = $chkRsStmt->get_result()->fetch_assoc();
        $chkRsStmt->close();
        if ($existingRs) {
            $existingNo = !empty($existingRs['stockorder_no']) ? $existingRs['stockorder_no'] : $stockOrderNo;
            throw new Exception("Stock Number '{$bill_no}' ({$existingNo}) already exists in Ready Stock records! Please use a unique stock number.");
        }

        // Create new production stock order
        $soRemarks = ($stock_type === 'openstock') ? ('Open Stock (Ref: ' . $bill_no . ')') : ('Ready Stock (Bill No: ' . $bill_no . ')');
        $soStatus = 'Confirmed';
        $sowStatus = 'sowed';
        $workStatus = 'Polyhouse';

        $inSoStmt = $krfconn->prepare("INSERT INTO krf_production_stockorder (
            psono, stockorder_no, projectid, companyid, subcompanyid,
            clientid, productid, product_name, quantity, expected_delivery_date,
            sowingdate, scionsowingdate, sowingstatus, scionsowingstatus, workingstatus,
            status, po_number, remarks, sowinglocationid, roottrayid,
            sowingallotedqty, sowingbalqty, germinationfailureqty, created_by, created_at, updated_at
        ) VALUES (
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, 0, 0, ?, NOW(), NOW()
        )");
        if (!$inSoStmt) throw new Exception("Prepare production stock order insert failed: " . $krfconn->error);

        $inSoStmt->bind_param(
            'ssiiisssissssssssssiis',
            $psono,
            $stockOrderNo,
            $projectid,
            $companyid,
            $subcompanyid,
            $soClient,
            $productid,
            $productName,
            $totalPlants,
            $dispatch_date,
            $sowing_date,
            $sowing_date,
            $sowStatus,
            $sowStatus,
            $workStatus,
            $soStatus,
            $bill_no,
            $soRemarks,
            $locationid,
            $tray_id,
            $totalPlants,
            $created_by
        );
        if (!$inSoStmt->execute()) {
            throw new Exception("Error creating production stock order: " . $inSoStmt->error);
        }
        $inSoStmt->close();

        // 2. Prepare Plant Details inserts
        $plantStage = ($stock_type === 'openstock') ? 'OpenStock' : 'ReadyToSale';
        $plantStatus = ($stock_type === 'openstock') ? 'OpenStock' : 'Readytosale';
        $barcodePrefix = ($stock_type === 'openstock') ? 'OS' : 'RS';

        $plantSql = "INSERT INTO krf_polyhouse_plant_details (
            planted_date, sowing_date, sowingdate_internal, sowinglotno_internal,
            sowingbatchno, stockorder_no, psono, roottype,
            locationid, polyhouseid, projectid, lotno, batchno, barcode,
            productid, productname, variety, source_type, cultivation_type,
            tray_id, tray_code, qtypertray, tray_position, bedrackname, bed_no,
            growth_stage, health_status, status, workingstatus,
            dispatchdate_internal, expected_harvest_date,
            bedqrcode,
            created_at, created_by, updated_at, updated_by
        ) VALUES (
            ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, 'Healthy', ?, 'Polyhouse',
            ?, ?,
            ?,
            NOW(), ?, NOW(), ?
        )";

        $plantStmt = $krfconn->prepare($plantSql);
        if (!$plantStmt) throw new Exception("Prepare plant insert failed: " . $krfconn->error);

        // 3. Prepare Ready Stock insert ONLY IF stock_type === 'readystock'
        $readyStmt = null;
        if ($stock_type === 'readystock') {
            $readySql = "INSERT INTO krf_readytosale_stocks (
                stockorder_no, psono, projectid, productid, productname,
                trayid, qtypertray, locationid, locationname, polyhouseplantid, barcode,
                deliverydate, remarks, status, created_by, created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, 'Readytosale', ?, NOW(), NOW()
            )";
            $readyStmt = $krfconn->prepare($readySql);
            if (!$readyStmt) throw new Exception("Prepare ready stock insert failed: " . $krfconn->error);
        }

        $insertedCount = 0;

        for ($i = 1; $i <= $no_of_trays; $i++) {
            $barcode = $barcodePrefix . $datePrefix . strtoupper(substr(uniqid(), -4)) . str_pad((string)$i, 3, '0', STR_PAD_LEFT);
            $roottype = 'Root';
            $cultivationType = 'Tray';
            $sowingLotInternal = $isNonGrafted ? ($seed_lot_no ?: $bill_no) : $bill_no;
            $sowingBatchNo = $bill_no;
            $remarks = ($stock_type === 'openstock') ? ('Open Stock Added (Ref: ' . $bill_no . ')') : ('Ready Stock Added (Bill No: ' . $bill_no . ')');

            $plantStmt->bind_param(
                'ssssssssssisssissssisissssssssss',
                $sowing_date,
                $sowing_date,
                $sowing_date,
                $sowingLotInternal,
                $sowingBatchNo,
                $stockOrderNo,
                $psono,
                $roottype,
                $locationid,
                $polyhouseid,
                $projectid,
                $bill_no,
                $bill_no,
                $barcode,
                $productid,
                $productName,
                $variety,
                $sourceType,
                $cultivationType,
                $tray_id,
                $trayCode,
                $qtyPerTray,
                $tray_position,
                $bed_no,
                $bed_no,
                $plantStage,
                $plantStatus,
                $dispatch_date,
                $dispatch_date,
                $stockOrderNo,
                $created_by,
                $created_by
            );

            if (!$plantStmt->execute()) {
                throw new Exception("Error inserting plant record #{$i}: " . $plantStmt->error);
            }

            $polyhouseplantid = (string)$plantStmt->insert_id;

            if ($stock_type === 'readystock' && $readyStmt) {
                $readyStmt->bind_param(
                    'ssiisiisssssss',
                    $stockOrderNo,
                    $psono,
                    $projectid,
                    $productid,
                    $productName,
                    $tray_id,
                    $qtyPerTray,
                    $locationid,
                    $locationName,
                    $polyhouseplantid,
                    $barcode,
                    $dispatch_date,
                    $remarks,
                    $created_by
                );

                if (!$readyStmt->execute()) {
                    throw new Exception("Error inserting ready stock record: " . $readyStmt->error);
                }
            }

            $insertedCount++;
        }

        $plantStmt->close();
        if ($readyStmt) $readyStmt->close();
        $krfconn->commit();

        $stockTypeLabel = ($stock_type === 'openstock') ? 'Open Stock' : 'Ready Stock';
        $msg = "Successfully saved {$stockTypeLabel} ({$stockOrderNo}) with {$insertedCount} tray(s) (" . ($insertedCount * $qtyPerTray) . " plants) in Bed {$bed_no}!";

        outJson([
            'status' => 'success',
            'message' => $msg,
            'stock_type' => $stock_type,
            'stockorder_no' => $stockOrderNo,
            'inserted_trays' => $insertedCount,
            'total_plants' => $totalPlants,
            'locationid' => $locationid,
            'polyhouseid' => $polyhouseid,
            'bed_no' => $bed_no
        ]);

    } catch (Throwable $e) {
        $krfconn->rollback();
        outJson(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

if ($action === 'get_duplicate_rsos') {
    $projectid = (int)($_POST['projectid'] ?? 0);
    if ($projectid <= 0) $projectid = 17;

    $suggestBedRsoFunc = function($baseBillNo, $bedNo) use ($krfconn, $projectid) {
        $cleanBase = preg_replace('/^(RSO|OS)-/i', '', trim($baseBillNo));
        $cleanBed = preg_replace('/[\s_]+/', '-', trim($bedNo));
        $cleanBed = preg_replace('/[^0-9a-zA-Z\-]/', '', $cleanBed);
        
        $baseCandidate = "{$cleanBase}-{$cleanBed}";
        for ($i = 0; $i <= 10; $i++) {
            $candBill = ($i === 0) ? $baseCandidate : "{$baseCandidate}-{$i}";
            $candRso = 'RSO-' . $candBill;

            // Fast indexed check on production_stockorder
            $ch1 = $krfconn->query("SELECT id FROM krf_production_stockorder WHERE stockorder_no = '{$candRso}' LIMIT 1");
            if ($ch1 && $ch1->num_rows > 0) continue;

            // Fast indexed check on polyhouse_plant_details
            $ch2 = $krfconn->query("SELECT polyhouseplantid FROM krf_polyhouse_plant_details WHERE stockorder_no = '{$candRso}' LIMIT 1");
            if ($ch2 && $ch2->num_rows > 0) continue;

            return ['suggested_rso' => $candRso, 'suggested_bill_no' => $candBill];
        }
        $uniq = strtoupper(substr(uniqid(), -3));
        return ['suggested_rso' => "RSO-{$baseCandidate}-{$uniq}", 'suggested_bill_no' => "{$baseCandidate}-{$uniq}"];
    };

    // 1. Product conflicts: Same RSO having COUNT(DISTINCT productid) > 1, broken down bed-wise
    $prodConflicts = [];
    $pSql = "SELECT stockorder_no, COUNT(DISTINCT productid) as prod_count, COUNT(*) as total_trays, MIN(created_at) as first_created
             FROM krf_polyhouse_plant_details
             WHERE stockorder_no LIKE 'RSO-%' AND projectid = ? AND (tedit IS NULL OR tedit = '')
             GROUP BY stockorder_no
             HAVING prod_count > 1
             ORDER BY first_created DESC";
    $pStmt = $krfconn->prepare($pSql);
    if ($pStmt) {
        $pStmt->bind_param('i', $projectid);
        $pStmt->execute();
        $pRes = $pStmt->get_result();
        while ($pRow = $pRes->fetch_assoc()) {
            $soNo = $pRow['stockorder_no'];
            $soEsc = $krfconn->real_escape_string($soNo);
            
            $subQ = $krfconn->query("
                SELECT 
                    productid, 
                    productname, 
                    bed_no,
                    polyhouseid,
                    locationid,
                    tray_id, 
                    tray_code, 
                    qtypertray, 
                    COUNT(*) as tray_count, 
                    (COUNT(*) * qtypertray) as plant_count,
                    MIN(created_at) as earliest_entry
                FROM krf_polyhouse_plant_details
                WHERE stockorder_no = '{$soEsc}' AND projectid = {$projectid} AND (tedit IS NULL OR tedit = '')
                GROUP BY productid, bed_no
                ORDER BY earliest_entry ASC, productid ASC, bed_no ASC
            ");

            $rowsByProd = [];
            $earliestProdId = null;
            while ($r = $subQ->fetch_assoc()) {
                $pid = (int)$r['productid'];
                if ($earliestProdId === null) {
                    $earliestProdId = $pid;
                }
                $rowsByProd[$pid][] = $r;
            }

            $entries = [];

            // Primary product: preserved as original entry keeping original RSO
            if ($earliestProdId !== null && isset($rowsByProd[$earliestProdId])) {
                $pRows = $rowsByProd[$earliestProdId];
                $firstP = $pRows[0];
                $totalTrays = 0;
                $totalPlants = 0;
                $beds = [];
                foreach ($pRows as $pr) {
                    $totalTrays += (int)$pr['tray_count'];
                    $totalPlants += (int)$pr['plant_count'];
                    if ($pr['bed_no'] && !in_array($pr['bed_no'], $beds)) {
                        $beds[] = $pr['bed_no'];
                    }
                }
                $entries[] = [
                    'productid' => $firstP['productid'],
                    'productname' => $firstP['productname'],
                    'bed_no' => implode(', ', $beds),
                    'polyhouseid' => $firstP['polyhouseid'],
                    'locationid' => $firstP['locationid'],
                    'tray_id' => $firstP['tray_id'],
                    'tray_code' => $firstP['tray_code'],
                    'qtypertray' => $firstP['qtypertray'],
                    'tray_count' => $totalTrays,
                    'plant_count' => $totalPlants,
                    'is_original' => true,
                    'conflict_type' => 'original',
                    'suggested' => null
                ];
            }

            // Conflicting products: each bed is an individual row with bed-specific unique RSO
            foreach ($rowsByProd as $pid => $pRows) {
                if ($pid === $earliestProdId) continue;
                foreach ($pRows as $pr) {
                    $suggested = $suggestBedRsoFunc($soNo, $pr['bed_no']);
                    $entries[] = [
                        'productid' => $pr['productid'],
                        'productname' => $pr['productname'],
                        'bed_no' => $pr['bed_no'],
                        'polyhouseid' => $pr['polyhouseid'],
                        'locationid' => $pr['locationid'],
                        'tray_id' => $pr['tray_id'],
                        'tray_code' => $pr['tray_code'],
                        'qtypertray' => $pr['qtypertray'],
                        'tray_count' => (int)$pr['tray_count'],
                        'plant_count' => (int)$pr['plant_count'],
                        'is_original' => false,
                        'conflict_type' => 'product_conflict',
                        'suggested' => $suggested
                    ];
                }
            }

            $prodConflicts[] = [
                'stockorder_no' => $soNo,
                'total_trays' => (int)$pRow['total_trays'],
                'product_count' => (int)$pRow['prod_count'],
                'entries' => $entries
            ];
        }
        $pStmt->close();
    }

    // 2. Bed conflicts: Same RSO having COUNT(DISTINCT bed_no) > 1 AND COUNT(DISTINCT productid) = 1
    $bedConflicts = [];
    $bSql = "SELECT stockorder_no, productid, productname, COUNT(DISTINCT bed_no) as bed_count, COUNT(*) as total_trays, MIN(created_at) as first_created
             FROM krf_polyhouse_plant_details
             WHERE stockorder_no LIKE 'RSO-%' AND projectid = ? AND (tedit IS NULL OR tedit = '')
             GROUP BY stockorder_no
             HAVING bed_count > 1 AND COUNT(DISTINCT productid) = 1
             ORDER BY first_created DESC
             LIMIT 15";
    $bStmt = $krfconn->prepare($bSql);
    if ($bStmt) {
        $bStmt->bind_param('i', $projectid);
        $bStmt->execute();
        $bRes = $bStmt->get_result();
        while ($bRow = $bRes->fetch_assoc()) {
            $soNo = $bRow['stockorder_no'];
            $soEsc = $krfconn->real_escape_string($soNo);
            $subQ = $krfconn->query("
                SELECT 
                    bed_no, 
                    polyhouseid, 
                    locationid, 
                    productid, 
                    productname, 
                    tray_id, 
                    qtypertray, 
                    COUNT(*) as tray_count, 
                    (COUNT(*) * qtypertray) as plant_count,
                    MIN(created_at) as earliest_entry
                FROM krf_polyhouse_plant_details
                WHERE stockorder_no = '{$soEsc}' AND projectid = {$projectid} AND (tedit IS NULL OR tedit = '')
                GROUP BY bed_no
                ORDER BY earliest_entry ASC, bed_no ASC
            ");
            $beds = [];
            $bIdx = 0;
            while ($subRow = $subQ->fetch_assoc()) {
                $subRow['is_original'] = ($bIdx === 0);
                $subRow['suggested'] = $subRow['is_original'] ? null : $suggestBedRsoFunc($soNo, $subRow['bed_no']);
                $beds[] = $subRow;
                $bIdx++;
            }
            $bedConflicts[] = [
                'stockorder_no' => $soNo,
                'productid' => $bRow['productid'],
                'productname' => $bRow['productname'],
                'bed_count' => (int)$bRow['bed_count'],
                'total_trays' => (int)$bRow['total_trays'],
                'beds' => $beds
            ];
        }
        $bStmt->close();
    }

    outJson([
        'status' => 'success',
        'product_conflicts' => $prodConflicts,
        'bed_conflicts' => $bedConflicts,
        'total_conflicts' => count($prodConflicts)
    ]);
}

if ($action === 'get_suggested_rso') {
    $projectid = (int)($_POST['projectid'] ?? 0);
    if ($projectid <= 0) $projectid = 17;
    $base = trim($_POST['base_rso'] ?? '');
    $bedNo = trim($_POST['bed_no'] ?? '');
    if ($base === '') $base = 'RSO-STOCK';

    $cleanBase = preg_replace('/^(RSO|OS)-/i', '', $base);
    if ($bedNo !== '') {
        $cleanBed = preg_replace('/[\s_]+/', '-', trim($bedNo));
        $cleanBed = preg_replace('/[^0-9a-zA-Z\-]/', '', $cleanBed);
        $baseCandidate = "{$cleanBase}-{$cleanBed}";
    } else {
        $baseCandidate = $cleanBase;
    }

    $suggested = null;
    $suggestedBill = null;

    for ($i = 0; $i <= 10; $i++) {
        $candBill = ($i === 0 && $bedNo !== '') ? $baseCandidate : "{$baseCandidate}-{$i}";
        $candRso = 'RSO-' . $candBill;

        $ch1 = $krfconn->query("SELECT id FROM krf_production_stockorder WHERE stockorder_no = '{$candRso}' LIMIT 1");
        if ($ch1 && $ch1->num_rows > 0) continue;

        $ch2 = $krfconn->query("SELECT polyhouseplantid FROM krf_polyhouse_plant_details WHERE stockorder_no = '{$candRso}' LIMIT 1");
        if ($ch2 && $ch2->num_rows > 0) continue;

        $suggested = $candRso;
        $suggestedBill = $candBill;
        break;
    }

    if (!$suggested) {
        $uniq = strtoupper(substr(uniqid(), -3));
        $suggested = "RSO-{$baseCandidate}-{$uniq}";
        $suggestedBill = "{$baseCandidate}-{$uniq}";
    }

    outJson([
        'status' => 'success',
        'suggested_rso' => $suggested,
        'suggested_bill_no' => $suggestedBill
    ]);
}

if ($action === 'resolve_duplicate_rso') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $projectid = (int)($_POST['projectid'] ?? 0);
    if ($projectid <= 0) $projectid = 17;
    $original_rso = trim($_POST['original_rso'] ?? '');
    $new_rso = trim($_POST['new_rso'] ?? '');
    $target_productid = (int)($_POST['target_productid'] ?? 0);
    $target_bed_no = trim($_POST['target_bed_no'] ?? '');
    $loginid = trim($_POST['loginid'] ?? $_SESSION['loginname'] ?? $_SESSION['loginid'] ?? 'Admin');

    if ($original_rso === '') {
        outJson(['status' => 'error', 'message' => 'Original RSO number is required.']);
    }
    if ($new_rso === '') {
        outJson(['status' => 'error', 'message' => 'New RSO number is required.']);
    }

    if (!preg_match('/^(RSO|OS)-/i', $new_rso)) {
        $new_rso = 'RSO-' . $new_rso;
    }
    $cleanNewBillNo = preg_replace('/^(RSO|OS)-/i', '', $new_rso);

    if (strcasecmp($original_rso, $new_rso) === 0) {
        outJson(['status' => 'error', 'message' => 'New RSO number must be different from the original RSO number.']);
    }

    $krfconn->begin_transaction();
    try {
        // 1. Strict check: ensure new_rso does not already exist
        $chk1 = $krfconn->prepare("SELECT id, stockorder_no FROM krf_production_stockorder WHERE projectid = ? AND (po_number = ? OR psono = ? OR stockorder_no = ?) AND (tedit IS NULL OR tedit = '') LIMIT 1");
        $chk1->bind_param('isss', $projectid, $cleanNewBillNo, $new_rso, $new_rso);
        $chk1->execute();
        $c1 = $chk1->get_result()->fetch_assoc();
        $chk1->close();
        if ($c1) {
            throw new Exception("New RSO number '{$new_rso}' already exists in production orders. Please choose another unique number.");
        }

        $chk2 = $krfconn->prepare("SELECT polyhouseplantid, stockorder_no FROM krf_polyhouse_plant_details WHERE projectid = ? AND (stockorder_no = ? OR psono = ? OR lotno = ? OR batchno = ?) AND (tedit IS NULL OR tedit = '') LIMIT 1");
        $chk2->bind_param('issss', $projectid, $new_rso, $new_rso, $cleanNewBillNo, $cleanNewBillNo);
        $chk2->execute();
        $c2 = $chk2->get_result()->fetch_assoc();
        $chk2->close();
        if ($c2) {
            throw new Exception("New RSO number '{$new_rso}' already exists in polyhouse plant records. Please choose another unique number.");
        }

        $chk3 = $krfconn->prepare("SELECT id, stockorder_no FROM krf_readytosale_stocks WHERE projectid = ? AND (stockorder_no = ? OR psono = ?) LIMIT 1");
        $chk3->bind_param('iss', $projectid, $new_rso, $new_rso);
        $chk3->execute();
        $c3 = $chk3->get_result()->fetch_assoc();
        $chk3->close();
        if ($c3) {
            throw new Exception("New RSO number '{$new_rso}' already exists in ready to sale stocks. Please choose another unique number.");
        }

        // 2. Fetch all matching plant details records to be moved
        $whereParts = ["stockorder_no = ?", "projectid = ?", "(tedit IS NULL OR tedit = '')"];
        $bindTypes = "si";
        $bindParams = [$original_rso, $projectid];

        if ($target_productid > 0) {
            $whereParts[] = "productid = ?";
            $bindTypes .= "i";
            $bindParams[] = $target_productid;
        }
        if ($target_bed_no !== '') {
            $whereParts[] = "bed_no = ?";
            $bindTypes .= "s";
            $bindParams[] = $target_bed_no;
        }

        $fetchSql = "SELECT polyhouseplantid, productid, productname, variety, source_type, cultivation_type, tray_id, tray_code, qtypertray, bed_no, locationid, polyhouseid, sowing_date, dispatchdate_internal, expected_harvest_date, status, workingstatus 
                     FROM krf_polyhouse_plant_details 
                     WHERE " . implode(' AND ', $whereParts);
        $fetchStmt = $krfconn->prepare($fetchSql);
        $fetchStmt->bind_param($bindTypes, ...$bindParams);
        $fetchStmt->execute();
        $plantRows = $fetchStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $fetchStmt->close();

        if (empty($plantRows)) {
            throw new Exception("No matching plant tray records found for {$original_rso} with specified criteria.");
        }

        $plantIds = array_column($plantRows, 'polyhouseplantid');
        $movedTrays = count($plantRows);
        $movedPlants = 0;
        foreach ($plantRows as $pr) {
            $movedPlants += (int)($pr['qtypertray'] ?? 1);
        }

        $firstRow = $plantRows[0];
        $targetProdId = (int)$firstRow['productid'];
        $targetProdName = $firstRow['productname'];
        $targetTrayId = (int)$firstRow['tray_id'];
        $targetLocId = $firstRow['locationid'];
        $targetPhId = (int)$firstRow['polyhouseid'];
        $targetBedNo = $firstRow['bed_no'];
        $targetDispDate = $firstRow['dispatchdate_internal'] ?: date('Y-m-d');
        $targetSowDate = $firstRow['sowing_date'] ?: date('Y-m-d');

        // 3. Safety check: ensure none are already marked Dispatched
        $inClause = implode(',', array_map('intval', $plantIds));
        $disCheck = $krfconn->query("SELECT COUNT(*) as dis_cnt FROM krf_readytosale_stocks WHERE polyhouseplantid IN ({$inClause}) AND status = 'Dispatched'");
        if ($disCheck && ($dc = $disCheck->fetch_assoc())) {
            if ((int)$dc['dis_cnt'] > 0) {
                throw new Exception("Cannot reassign: " . $dc['dis_cnt'] . " tray(s) in this group have already been dispatched.");
            }
        }

        // 4. Update / Deduct Production Stock Order
        $origSoStmt = $krfconn->prepare("SELECT id, quantity, sowingallotedqty, companyid, subcompanyid, clientid, productid, product_name, sowingdate, expected_delivery_date, sowinglocationid, roottrayid 
                                          FROM krf_production_stockorder 
                                          WHERE stockorder_no = ? AND projectid = ? AND (tedit IS NULL OR tedit = '') 
                                          ORDER BY id DESC LIMIT 1");
        $origSoStmt->bind_param('si', $original_rso, $projectid);
        $origSoStmt->execute();
        $origSo = $origSoStmt->get_result()->fetch_assoc();
        $origSoStmt->close();

        $companyId = $origSo ? (int)$origSo['companyid'] : 0;
        $subCompanyId = $origSo ? (int)$origSo['subcompanyid'] : 0;
        $clientId = $origSo ? $origSo['clientid'] : 'ReadyStock';

        if ($origSo) {
            // Deduct moved plants from original order
            $deductStmt = $krfconn->prepare("UPDATE krf_production_stockorder 
                                              SET quantity = GREATEST(0, quantity - ?), 
                                                  sowingallotedqty = GREATEST(0, sowingallotedqty - ?), 
                                                  updated_at = NOW() 
                                              WHERE id = ?");
            $deductStmt->bind_param('iii', $movedPlants, $movedPlants, $origSo['id']);
            $deductStmt->execute();
            $deductStmt->close();
        }

        // Insert new dedicated production stock order for the new RSO
        $newSoStmt = $krfconn->prepare("INSERT INTO krf_production_stockorder (
            psono, stockorder_no, projectid, companyid, subcompanyid,
            clientid, productid, product_name, quantity, expected_delivery_date,
            sowingdate, scionsowingdate, sowingstatus, scionsowingstatus, workingstatus,
            status, po_number, remarks, sowinglocationid, roottrayid,
            sowingallotedqty, sowingbalqty, germinationfailureqty, created_by, created_at, updated_at
        ) VALUES (
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, 'sowed', 'sowed', 'Polyhouse',
            'Confirmed', ?, ?, ?, ?,
            ?, 0, 0, ?, NOW(), NOW()
        )");
        $soRemarks = "Ready Stock (Reassigned from {$original_rso} to {$new_rso})";
        $newSoStmt->bind_param(
            'ssiiisisissssssiis',
            $new_rso,
            $new_rso,
            $projectid,
            $companyId,
            $subCompanyId,
            $clientId,
            $targetProdId,
            $targetProdName,
            $movedPlants,
            $targetDispDate,
            $targetSowDate,
            $targetSowDate,
            $cleanNewBillNo,
            $soRemarks,
            $targetLocId,
            $targetTrayId,
            $movedPlants,
            $loginid
        );
        if (!$newSoStmt->execute()) {
            throw new Exception("Failed to create new production stock order: " . $newSoStmt->error);
        }
        $newSoStmt->close();

        // 5. Update Plant Details records
        $updPlStmt = $krfconn->prepare("UPDATE krf_polyhouse_plant_details 
                                        SET stockorder_no = ?, 
                                            psono = ?, 
                                            bedqrcode = ?, 
                                            lotno = ?, 
                                            batchno = ?, 
                                            sowingbatchno = ?, 
                                            updated_at = NOW(), 
                                            updated_by = ? 
                                        WHERE polyhouseplantid IN ({$inClause})");
        $updPlStmt->bind_param('sssssss', $new_rso, $new_rso, $new_rso, $cleanNewBillNo, $cleanNewBillNo, $cleanNewBillNo, $loginid);
        if (!$updPlStmt->execute()) {
            throw new Exception("Failed to update polyhouse plant records: " . $updPlStmt->error);
        }
        $updPlStmt->close();

        // 6. Update Ready to Sale records
        $rsRemarks = "Ready Stock (Reassigned from {$original_rso} to {$new_rso})";
        $updRsStmt = $krfconn->prepare("UPDATE krf_readytosale_stocks 
                                        SET stockorder_no = ?, 
                                            psono = ?, 
                                            remarks = ?, 
                                            updated_at = NOW() 
                                        WHERE polyhouseplantid IN ({$inClause})");
        $updRsStmt->bind_param('sss', $new_rso, $new_rso, $rsRemarks);
        if (!$updRsStmt->execute()) {
            throw new Exception("Failed to update ready stock records: " . $updRsStmt->error);
        }
        $updRsStmt->close();

        // 7. Update Open Sale records (if any trays were moved to open sale)
        $osRemarks = "Reassigned from {$original_rso} to {$new_rso}";
        $updOsStmt = $krfconn->prepare("UPDATE krf_opensale_stocks 
                                        SET stockorder_no = ?, 
                                            psono = ?, 
                                            remarks = CONCAT(COALESCE(remarks, ''), IF(COALESCE(remarks, '') = '', '', ' | '), ?), 
                                            updated_at = NOW() 
                                        WHERE polyhouseplantid IN ({$inClause})");
        if ($updOsStmt) {
            $updOsStmt->bind_param('sss', $new_rso, $new_rso, $osRemarks);
            if (!$updOsStmt->execute()) {
                throw new Exception("Failed to update open sale stock records: " . $updOsStmt->error);
            }
            $updOsStmt->close();
        }

        $krfconn->commit();

        outJson([
            'status' => 'success',
            'message' => "Successfully reassigned {$movedTrays} trays ({$movedPlants} plants) to {$new_rso}!",
            'new_rso' => $new_rso,
            'old_rso' => $original_rso,
            'moved_trays' => $movedTrays,
            'total_plants' => $movedPlants,
            'bed_no' => $targetBedNo,
            'productid' => $targetProdId,
            'productname' => $targetProdName,
            'polyhouseid' => $targetPhId,
            'tray_id' => $targetTrayId
        ]);

    } catch (Throwable $e) {
        $krfconn->rollback();
        outJson(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

if ($action === 'getorderqrcodes') {
    try {
        // Auto-heal missing bedqrcode column if not exists on server database
        try {
            @$krfconn->query("ALTER TABLE krf_polyhouse_plant_details ADD COLUMN IF NOT EXISTS bedqrcode VARCHAR(100) NULL AFTER expected_harvest_date");
        } catch (Throwable $ignore) {}

        $projectid     = (int)($_POST['projectid'] ?? 0);
        $locationid    = trim($_POST['locationid'] ?? '');
        $polyhouseid   = (int)($_POST['polyhouseid'] ?? 0);
        $bed_no        = trim($_POST['bed_no'] ?? '');
        $stockorder_no = trim($_POST['stockorder_no'] ?? '');
        $psono         = trim($_POST['psono'] ?? '');
        $productid     = trim($_POST['productid'] ?? '');

        if ($projectid <= 0) $projectid = 17;

        $whereConditions = ["p.projectid = '{$projectid}'"];
        if (!empty($locationid)) {
            $locEsc = $krfconn->real_escape_string($locationid);
            $whereConditions[] = "p.locationid = '{$locEsc}'";
        }
        if ($polyhouseid > 0) {
            $whereConditions[] = "CAST(p.polyhouseid AS UNSIGNED) = {$polyhouseid}";
        }
        if (!empty($bed_no)) {
            $bedEsc = $krfconn->real_escape_string($bed_no);
            $cleanBed = preg_replace('/^Bed[-\s]*/i', '', $bed_no);
            $cleanBedEsc = $krfconn->real_escape_string($cleanBed);
            $whereConditions[] = "(p.bed_no = '{$bedEsc}' OR p.bed_no = '{$cleanBedEsc}' OR p.bed_no = 'Bed-{$cleanBedEsc}')";
        }
        if (!empty($stockorder_no)) {
            $soEsc = $krfconn->real_escape_string($stockorder_no);
            $cleanSo = preg_replace('/^(RSO|OS)-/i', '', $stockorder_no);
            $cleanSoEsc = $krfconn->real_escape_string($cleanSo);
            $whereConditions[] = "(p.stockorder_no = '{$soEsc}' OR p.stockorder_no = '{$cleanSoEsc}' OR p.stockorder_no = 'RSO-{$cleanSoEsc}' OR p.stockorder_no = 'OS-{$cleanSoEsc}' OR p.psono = '{$soEsc}' OR p.psono = '{$cleanSoEsc}' OR p.bedqrcode = '{$soEsc}')";
        }
        if (!empty($productid)) {
            $prodEsc = $krfconn->real_escape_string($productid);
            $whereConditions[] = "p.productid = '{$prodEsc}'";
        }
        if (!empty($psono) && empty($stockorder_no)) {
            $psoEsc = $krfconn->real_escape_string($psono);
            $whereConditions[] = "p.psono = '{$psoEsc}'";
        }

        $whereConditions[] = "(p.tedit IS NULL OR p.tedit = '')";
        $whereConditions[] = "p.barcode IS NOT NULL AND TRIM(p.barcode) <> ''";
        $whereClause = implode(" AND ", $whereConditions);

        $sql = "
            SELECT 
                p.polyhouseplantid,
                p.barcode,
                p.bed_no,
                p.polyhouseid,
                p.locationid,
                p.stockorder_no,
                p.psono,
                p.productid,
                p.productname,
                p.variety,
                p.qtypertray,
                p.status,
                p.workingstatus,
                p.growth_stage,
                p.bedqrcode,
                COALESCE(NULLIF(TRIM(p.lotno),''), NULLIF(TRIM(p.sowinglotno_internal),''), NULLIF(TRIM(p.batchno),''), '') AS seed_lot_no,
                COALESCE(p.sowingdate_internal, p.sowing_date) AS sowing_date,
                p.dispatchdate_internal
            FROM krf_polyhouse_plant_details p
            WHERE {$whereClause}
            ORDER BY p.polyhouseplantid ASC
        ";

        $res = @$krfconn->query($sql);
        $labels = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $sowDate = !empty($row['sowing_date']) ? date('d-M-Y', strtotime($row['sowing_date'])) : '';
                $dispDate = !empty($row['dispatchdate_internal']) ? date('d-M-Y', strtotime($row['dispatchdate_internal'])) : '';
                $labels[] = [
                    'id' => $row['polyhouseplantid'],
                    'barcode' => $row['barcode'],
                    'bed_no' => $row['bed_no'] ?: ($bed_no ?: 'Bed-?'),
                    'polyhouseid' => $row['polyhouseid'] ?: ($polyhouseid ?: 'PH-?'),
                    'locationid' => $row['locationid'] ?? '',
                    'stockorder_no' => $row['stockorder_no'] ?: ($stockorder_no ?: 'N/A'),
                    'psono' => $row['psono'] ?: ($psono ?: 'N/A'),
                    'productid' => $row['productid'],
                    'productname' => $row['productname'] ?: 'Plant',
                    'variety' => $row['variety'] ?: '',
                    'seed_lot_no' => $row['seed_lot_no'] ?: '',
                    'bedqrcode' => trim($row['bedqrcode'] ?? ''),
                    'qtypertray' => (int)($row['qtypertray'] ?: 209),
                    'sowing_date' => $sowDate,
                    'dispatch_date' => $dispDate,
                    'status' => $row['status']
                ];
            }
        }

        // Fallback: If no direct plant detail barcode rows matched, search production stock order or plant details loosely
        $fallbackRow = [];
        $searchKey = $stockorder_no ?: ($psono ?: '');
        $cleanSearchKey = preg_replace('/^(RSO|OS|PSO)-/i', '', $searchKey);
        $passedProdName = trim($_POST['product_name'] ?? '');

        if (empty($labels) || empty($firstRow['productname'])) {
            if (!empty($searchKey) || !empty($cleanSearchKey)) {
                $k1 = $searchKey;
                $k2 = $cleanSearchKey;
                $k3 = 'RSO-' . $cleanSearchKey;
                $k4 = 'OS-' . $cleanSearchKey;
                $k5 = 'PSO-' . $cleanSearchKey;

                // $fbSql = "SELECT so.*, p.polyhouseid AS ph_id, p.locationid AS loc_id, p.bed_no AS p_bed, p.variety AS p_variety, p.lotno AS p_lotno, p.bedqrcode AS p_bedqrcode
                //           FROM krf_production_stockorder so
                //           LEFT JOIN krf_polyhouse_plant_details p ON p.projectid = so.projectid AND (p.stockorder_no = so.stockorder_no OR p.psono = so.psono)
                //           WHERE so.projectid = ? AND 
                //               so.stockorder_no = '".$stockorder_no."' ORDER BY so.id DESC LIMIT 1";
                // $fbRes = @$krfconn->query($fbSql);
                // if ($fbRes && $r = $fbRes->fetch_assoc()) {
                //     $fallbackRow = $r;
                // }

                $fbSql = "SELECT so.*, p.polyhouseid AS ph_id, p.locationid AS loc_id, p.bed_no AS p_bed, p.variety AS p_variety, p.lotno AS p_lotno, p.bedqrcode AS p_bedqrcode FROM krf_production_stockorder so LEFT JOIN krf_polyhouse_plant_details p ON p.projectid = so.projectid AND (p.stockorder_no = so.stockorder_no OR p.psono = so.psono) AND (p.tedit IS NULL OR p.tedit = '') WHERE so.projectid = ? AND so.stockorder_no = ? AND (so.tedit IS NULL OR so.tedit = '') ORDER BY so.id DESC
                          LIMIT 1";
                $fallbackRow = null;
                $stmt = $krfconn->prepare($fbSql);
                if ($stmt) {
                    $stmt->bind_param("is", $projectid, $stockorder_no);
                    if ($stmt->execute()) {
                        $fbRes = $stmt->get_result();

                        if ($fbRes && ($r = $fbRes->fetch_assoc())) {
                            $fallbackRow = $r;
                        }
                    }

                    $stmt->close();
                }
            }
        }

        $firstRow = $labels[0] ?? [];
        $resolvedPolyhouseId = $polyhouseid > 0 ? $polyhouseid : (int)($firstRow['polyhouseid'] ?? ($fallbackRow['ph_id'] ?? 0));
        $resolvedLocationId  = !empty($locationid) ? $locationid : trim($firstRow['locationid'] ?? ($fallbackRow['loc_id'] ?? ($fallbackRow['sowinglocationid'] ?? '')));

        // Fetch Product Details from product_master if needed
        $resolvedProductId = !empty($productid) ? (int)$productid : (int)($firstRow['productid'] ?? ($fallbackRow['productid'] ?? 0));
        $dbProdName = '';
        if ($resolvedProductId > 0) {
            $pmRes = @$krfconn->query("SELECT ProductName, sku FROM product_master WHERE ProductID = {$resolvedProductId} LIMIT 1");
            if ($pmRes && $pmRow = $pmRes->fetch_assoc()) {
                $dbProdName = trim($pmRow['ProductName'] ?? '');
            }
        }

        // Fetch Polyhouse name
        $phName = "Polyhouse " . ($resolvedPolyhouseId ?: '1');
        if ($resolvedPolyhouseId > 0) {
            $phStmt = @$krfconn->prepare("SELECT polyhousename FROM krf_polyhouse_master WHERE polyhouseid = ? LIMIT 1");
            if ($phStmt) {
                $phStmt->bind_param("i", $resolvedPolyhouseId);
                $phStmt->execute();
                $phRes = $phStmt->get_result()->fetch_assoc();
                if ($phRes && !empty($phRes['polyhousename'])) {
                    $phName = $phRes['polyhousename'];
                }
                $phStmt->close();
            }
        }

        // Fetch Location name
        $locName = $resolvedLocationId ?: 'Nursery Location';
        if (!empty($resolvedLocationId) && isset($hrmconn) && $hrmconn instanceof mysqli) {
            $locStmt = @$hrmconn->prepare("SELECT locationname FROM hrm_AttendanceLocationMaster WHERE locationid = ? LIMIT 1");
            if ($locStmt) {
                $locStmt->bind_param("s", $resolvedLocationId);
                $locStmt->execute();
                $locRes = $locStmt->get_result()->fetch_assoc();
                if ($locRes && !empty($locRes['locationname'])) {
                    $locName = $locRes['locationname'];
                }
                $locStmt->close();
            }
        }

        // Bed QR Code is stockorder_no
        $bedQrCode = trim($firstRow['bedqrcode'] ?? ($fallbackRow['p_bedqrcode'] ?? ''));
        if (empty($bedQrCode)) {
            $bedQrCode = trim($firstRow['stockorder_no'] ?? ($fallbackRow['stockorder_no'] ?? '')) ?: ($stockorder_no ?: trim($firstRow['psono'] ?? ($fallbackRow['psono'] ?? '')));
        }

        if (!empty($bedQrCode) && !empty($whereClause) && !empty($labels)) {
            $escapedBedQr = $krfconn->real_escape_string($bedQrCode);
            $cleanWhere = str_replace('p.', '', $whereClause);
            $updSql = "UPDATE krf_polyhouse_plant_details SET bedqrcode = '{$escapedBedQr}' WHERE {$cleanWhere} AND (bedqrcode IS NULL OR bedqrcode = '' OR bedqrcode != '{$escapedBedQr}')";
            @$krfconn->query($updSql);
        }

        // Build Single Order Summary for A4 Normal Printer
        $rawProdName = trim($firstRow['productname'] ?? ($fallbackRow['product_name'] ?? ($dbProdName ?: $passedProdName)));
        $rawVariety = trim($firstRow['variety'] ?? ($fallbackRow['p_variety'] ?? ''));

        if (strpos($rawProdName, ',') !== false) {
            $parts = explode(',', $rawProdName, 2);
            $prodNameOnly = trim($parts[0]);
            $varietyOnly = trim($parts[1]);
        } else {
            $prodNameOnly = $rawProdName ?: 'Plant';
            $varietyOnly = (!empty($rawVariety) && $rawVariety !== $rawProdName) ? $rawVariety : '-';
        }
        if ((empty($varietyOnly) || $varietyOnly === '-') && !empty($rawVariety)) {
            $varietyOnly = $rawVariety;
        }

        $totalTrays = count($labels);
        $qtyPerTray = (int)($firstRow['qtypertray'] ?? 209);
        $orderQty = (int)($fallbackRow['quantity'] ?? ($fallbackRow['sowingallotedqty'] ?? 0));
        $totalPlants = $totalTrays > 0 ? ($totalTrays * $qtyPerTray) : ($orderQty > 0 ? $orderQty : $qtyPerTray);
        if ($totalTrays === 0 && $totalPlants > 0 && $qtyPerTray > 0) {
            $totalTrays = (int)ceil($totalPlants / $qtyPerTray);
        }

        $billNo = !empty($firstRow['psono']) && $firstRow['psono'] !== 'N/A' ? $firstRow['psono'] : (!empty($firstRow['stockorder_no']) ? $firstRow['stockorder_no'] : ($stockorder_no ?: (!empty($fallbackRow['psono']) ? $fallbackRow['psono'] : ($cleanSearchKey ?: 'N/A'))));
        $seedLotNo = !empty($firstRow['seed_lot_no']) ? $firstRow['seed_lot_no'] : (!empty($fallbackRow['p_lotno']) ? $fallbackRow['p_lotno'] : ($cleanSearchKey ?: '-'));
        $sowDate = !empty($firstRow['sowing_date']) ? $firstRow['sowing_date'] : (!empty($fallbackRow['sowingdate']) ? date('d-M-Y', strtotime($fallbackRow['sowingdate'])) : date('d-M-Y'));
        $dispDate = !empty($firstRow['dispatch_date']) ? $firstRow['dispatch_date'] : (!empty($fallbackRow['expected_delivery_date']) ? date('d-M-Y', strtotime($fallbackRow['expected_delivery_date'])) : '-');

        $orderSummary = [
            'productname' => $prodNameOnly,
            'variety' => $varietyOnly,
            'billno' => $billNo,
            'seed_lot_no' => $seedLotNo,
            'bedqrcode' => $bedQrCode,
            'sowing_date' => $sowDate,
            'dispatch_date' => $dispDate,
            'total_trays' => $totalTrays,
            'qtypertray' => $qtyPerTray,
            'total_plants' => $totalPlants,
            'bed_no' => $bed_no ?: ($firstRow['bed_no'] ?? ($fallbackRow['p_bed'] ?? '')),
            'polyhouseid' => $resolvedPolyhouseId,
            'polyhousename' => $phName,
            'locationid' => $resolvedLocationId,
            'locationname' => $locName,
            'stockorder_no' => $stockorder_no ?: ($firstRow['stockorder_no'] ?? ($fallbackRow['stockorder_no'] ?? '')),
            'psono' => $firstRow['psono'] ?? ($fallbackRow['psono'] ?? ''),
            'qr_text' => $bedQrCode
        ];

        outJson([
            'status' => 'success',
            'count' => count($labels),
            'bed_no' => $bed_no,
            'stockorder_no' => $stockorder_no,
            'productid' => $productid,
            'order_summary' => $orderSummary,
            'labels' => $labels
        ]);
    } catch (Throwable $e) {
        outJson(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

if ($action === 'getoccupiedtrays') {
    $projectid     = (int)($_POST['projectid'] ?? 0);
    $locationid    = trim($_POST['locationid'] ?? '');
    $polyhouseid   = (int)($_POST['polyhouseid'] ?? 0);
    $bed_no        = trim($_POST['bed_no'] ?? '');
    $stockorder_no = trim($_POST['stockorder_no'] ?? '');
    $psono         = trim($_POST['psono'] ?? '');
    $productid     = (int)($_POST['productid'] ?? 0);

    if ($projectid <= 0) $projectid = 17;
    if ($polyhouseid <= 0 || empty($bed_no) || empty($stockorder_no)) {
        outJson(['status' => 'error', 'message' => 'Missing required order details.']);
    }

    $cleanBed = preg_replace('/^Bed[-\s]*/i', '', $bed_no);
    $bedEsc = $krfconn->real_escape_string($bed_no);
    $cleanBedEsc = $krfconn->real_escape_string($cleanBed);
    $bedPrefEsc = $krfconn->real_escape_string('Bed-' . $cleanBed);

    $cleanSo = preg_replace('/^(RSO|OS|PSO)-/i', '', $stockorder_no);
    $soEsc = $krfconn->real_escape_string($stockorder_no);
    $cleanSoEsc = $krfconn->real_escape_string($cleanSo);
    $psoEsc = $krfconn->real_escape_string($psono);

    $where = [
        "p.projectid = '{$projectid}'",
        "CAST(p.polyhouseid AS UNSIGNED) = {$polyhouseid}",
        "(p.bed_no = '{$bedEsc}' OR p.bed_no = '{$cleanBedEsc}' OR p.bed_no = '{$bedPrefEsc}')",
        "(p.stockorder_no = '{$soEsc}' OR p.stockorder_no = '{$cleanSoEsc}' OR p.stockorder_no = 'RSO-{$cleanSoEsc}' OR p.stockorder_no = 'OS-{$cleanSoEsc}' OR p.psono = '{$soEsc}' OR p.psono = '{$cleanSoEsc}' OR p.psono = '{$psoEsc}' OR p.lotno = '{$cleanSoEsc}' OR p.batchno = '{$cleanSoEsc}' OR p.bedqrcode = '{$soEsc}')",
        "(p.tedit IS NULL OR p.tedit = '')",
        "p.status != 'Removed'",
        "p.workingstatus != 'Removed'",
        "(p.status IN ('Polyhouse', 'Readytosale', 'Ready to sale', 'ReadyToSale', 'OpenStock', 'Open Stock') OR p.workingstatus = 'Polyhouse')",
        "p.status NOT IN ('Internal Dispatch', 'Final Dispatch', 'Grafting', 'Move for Grafting', 'Healing', 'Hardening', 'Clip Stick Removal', 'Removed', 'Grafted', 'Empty After Shifting', 'Empty After Grafting')"
    ];
    if ($productid > 0) {
        $where[] = "p.productid = '{$productid}'";
    }

    $whereClause = implode(" AND ", $where);
    $sql = "SELECT p.polyhouseplantid, p.barcode, p.bedqrcode, p.bed_no, p.stockorder_no, p.psono, 
                   p.productid, p.productname, p.variety, p.status, p.workingstatus, p.health_status, 
                   p.shiftingstatus, p.qtypertray, p.growth_stage, p.sowing_date, p.dispatchdate_internal
            FROM krf_polyhouse_plant_details p
            WHERE {$whereClause}
            ORDER BY p.polyhouseplantid ASC";

    $res = $krfconn->query($sql);
    if (!$res) {
        outJson(['status' => 'error', 'message' => 'Query failed: ' . $krfconn->error]);
    }

    $trays = [];
    $qtyPerTray = 209;
    while ($row = $res->fetch_assoc()) {
        $qtyPerTray = (int)($row['qtypertray'] ?: 209);
        $trays[] = [
            'polyhouseplantid' => (int)$row['polyhouseplantid'],
            'barcode'          => trim($row['barcode'] ?: ('TRAY-' . $row['polyhouseplantid'])),
            'bedqrcode'        => trim($row['bedqrcode'] ?: ''),
            'status'           => $row['status'],
            'health_status'    => $row['health_status'] ?: 'Healthy',
            'shiftingstatus'   => $row['shiftingstatus'] ?: 'nonshifted',
            'qtypertray'       => $qtyPerTray
        ];
    }

    $totalOccupiedTrays = count($trays);
    $totalOccupiedPlants = $totalOccupiedTrays * $qtyPerTray;

    outJson([
        'status' => 'success',
        'data' => [
            'trays' => $trays,
            'total_trays' => $totalOccupiedTrays,
            'total_plants' => $totalOccupiedPlants,
            'qtypertray' => $qtyPerTray
        ]
    ]);
}

if ($action === 'removeselectedtrays') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $projectid     = (int)($_POST['projectid'] ?? 0);
    $locationid    = trim($_POST['locationid'] ?? '');
    $polyhouseid   = (int)($_POST['polyhouseid'] ?? 0);
    $bed_no        = trim($_POST['bed_no'] ?? '');
    $stockorder_no = trim($_POST['stockorder_no'] ?? '');
    $psono         = trim($_POST['psono'] ?? '');
    $productid     = (int)($_POST['productid'] ?? 0);
    $plant_ids_raw = $_POST['plant_ids'] ?? [];
    $updated_by     = trim($_POST['loginid'] ?? $_SESSION['loginname'] ?? $_SESSION['loginid'] ?? 'System');
    $removal_remark = trim($_POST['removal_remark'] ?? $_POST['remark'] ?? $_POST['teditremark'] ?? '');

    if ($projectid <= 0) $projectid = 17;
    if ($polyhouseid <= 0 || empty($bed_no) || empty($stockorder_no)) {
        outJson(['status' => 'error', 'message' => 'Missing required order details.']);
    }

    $plant_ids = [];
    if (is_array($plant_ids_raw)) {
        foreach ($plant_ids_raw as $id) {
            $id = (int)$id;
            if ($id > 0) $plant_ids[] = $id;
        }
    } elseif (!empty($plant_ids_raw)) {
        $id = (int)$plant_ids_raw;
        if ($id > 0) $plant_ids[] = $id;
    }

    if (empty($plant_ids)) {
        outJson(['status' => 'error', 'message' => 'Please select at least one tray to remove.']);
    }

    $cleanBed = preg_replace('/^Bed[-\s]*/i', '', $bed_no);
    $bedEsc = $krfconn->real_escape_string($bed_no);
    $cleanBedEsc = $krfconn->real_escape_string($cleanBed);
    $bedPrefEsc = $krfconn->real_escape_string('Bed-' . $cleanBed);

    $cleanSo = preg_replace('/^(RSO|OS|PSO)-/i', '', $stockorder_no);
    $soEsc = $krfconn->real_escape_string($stockorder_no);
    $cleanSoEsc = $krfconn->real_escape_string($cleanSo);
    $psoEsc = $krfconn->real_escape_string($psono);

    $krfconn->begin_transaction();
    try {
        $idListStr = implode(',', $plant_ids);
        
        // Verify these trays are active occupied trays for this order & bed
        $vSql = "SELECT polyhouseplantid, barcode, qtypertray, stockorder_no, psono, status 
                 FROM krf_polyhouse_plant_details 
                 WHERE polyhouseplantid IN ({$idListStr})
                   AND projectid = '{$projectid}'
                   AND CAST(polyhouseid AS UNSIGNED) = {$polyhouseid}
                   AND (bed_no = '{$bedEsc}' OR bed_no = '{$cleanBedEsc}' OR bed_no = '{$bedPrefEsc}')
                   AND (tedit IS NULL OR tedit = '')
                   AND status != 'Removed'
                   AND workingstatus != 'Removed'
                   AND (status IN ('Polyhouse', 'Readytosale', 'Ready to sale', 'ReadyToSale', 'OpenStock', 'Open Stock') OR workingstatus = 'Polyhouse')
                   AND status NOT IN ('Internal Dispatch', 'Final Dispatch', 'Grafting', 'Move for Grafting', 'Healing', 'Hardening', 'Clip Stick Removal', 'Removed', 'Grafted', 'Empty After Shifting', 'Empty After Grafting')";
        $vRes = $krfconn->query($vSql);
        if (!$vRes) throw new Exception("Verification query failed: " . $krfconn->error);

        $validRecords = [];
        $validIds = [];
        $barcodesRemoved = [];
        while ($row = $vRes->fetch_assoc()) {
            $validRecords[] = $row;
            $validIds[] = (int)$row['polyhouseplantid'];
            if (!empty($row['barcode'])) {
                $barcodesRemoved[] = $row['barcode'];
            }
        }

        if (empty($validRecords)) {
            throw new Exception("No valid active occupied trays found to remove with the provided IDs.");
        }

        $validIdListStr = implode(',', $validIds);
        $removedTraysCount = count($validRecords);
        $qtyPerTray = (int)($validRecords[0]['qtypertray'] ?: 209);
        if ($qtyPerTray <= 0) $qtyPerTray = 1;
        $totalPlantsRemoved = $removedTraysCount * $qtyPerTray;

        $soNo = $validRecords[0]['stockorder_no'] ?: $stockorder_no;
        $psoNo = $validRecords[0]['psono'] ?: ($psono ?: $soNo);

        $currentDateTime = date('Y-m-d H:i:s');
        $curDateOnly     = date('Y-m-d');
        $teditDateEsc    = $krfconn->real_escape_string($currentDateTime);
        $userRemarkEsc   = $krfconn->real_escape_string($removal_remark);
        $teditRemarkText = "Removed {$removedTraysCount} tray(s) from Bed {$bed_no} on date {$currentDateTime} by {$updated_by}" . (!empty($removal_remark) ? " - Reason: {$removal_remark}" : "");
        $teditRemarkEsc  = $krfconn->real_escape_string(substr($teditRemarkText, 0, 500));
        $reasonSuffix    = !empty($removal_remark) ? " Reason: {$userRemarkEsc}" : "";

        // 1. Update krf_readytosale_stocks
        $krfconn->query("UPDATE krf_readytosale_stocks 
                         SET status = 'Removed', 
                             tedit = '{$teditDateEsc}', 
                             teditremark = '{$teditRemarkEsc}', 
                             remarks = CONCAT(IFNULL(remarks,''), ' [Tray removed from Bed {$bedEsc}{$reasonSuffix}]'), 
                             updated_at = NOW() 
                         WHERE polyhouseplantid IN ({$validIdListStr})");

        // 2. Update krf_polyhouse_plant_details
        $krfconn->query("UPDATE krf_polyhouse_plant_details 
                         SET status = 'Removed', 
                             workingstatus = 'Removed', 
                             tedit = '{$teditDateEsc}', 
                             teditremark = '{$teditRemarkEsc}', 
                             remarks = CONCAT(IFNULL(remarks,''), ' [Tray removed from Bed {$bedEsc}{$reasonSuffix}]'), 
                             updated_at = NOW(), 
                             updated_by = '{$updated_by}' 
                         WHERE polyhouseplantid IN ({$validIdListStr})");

        // 3. Update krf_production_stockorder
        $soEscActual = $krfconn->real_escape_string($soNo);
        $psoEscActual = $krfconn->real_escape_string($psoNo);
        $krfconn->query("UPDATE krf_production_stockorder 
                         SET remarks = CONCAT(IFNULL(remarks,''), ' [{$removedTraysCount} Trays removed from Bed {$bedEsc}{$reasonSuffix}]'), 
                             quantity = GREATEST(0, quantity - {$totalPlantsRemoved}), 
                             sowingallotedqty = GREATEST(0, sowingallotedqty - {$totalPlantsRemoved}), 
                             editedby = '{$updated_by}', 
                             editedon = '{$curDateOnly}', 
                             updated_at = NOW() 
                         WHERE projectid = {$projectid} 
                           AND (stockorder_no = '{$soEscActual}' OR psono = '{$psoEscActual}' OR po_number = '{$cleanSoEsc}' OR stockorder_no = 'RSO-{$cleanSoEsc}' OR stockorder_no = 'OS-{$cleanSoEsc}')");

        // Count remaining active occupied trays for this order in this bed
        $cSql = "SELECT COUNT(*) as rem 
                 FROM krf_polyhouse_plant_details 
                 WHERE projectid = '{$projectid}'
                   AND CAST(polyhouseid AS UNSIGNED) = {$polyhouseid}
                   AND (bed_no = '{$bedEsc}' OR bed_no = '{$cleanBedEsc}' OR bed_no = '{$bedPrefEsc}')
                   AND (stockorder_no = '{$soEsc}' OR stockorder_no = '{$cleanSoEsc}' OR stockorder_no = 'RSO-{$cleanSoEsc}' OR stockorder_no = 'OS-{$cleanSoEsc}' OR psono = '{$soEsc}' OR psono = '{$cleanSoEsc}' OR psono = '{$psoEsc}' OR lotno = '{$cleanSoEsc}' OR batchno = '{$cleanSoEsc}' OR bedqrcode = '{$soEsc}')
                   AND (tedit IS NULL OR tedit = '')
                   AND status != 'Removed'
                   AND workingstatus != 'Removed'
                   AND (status IN ('Polyhouse', 'Readytosale', 'Ready to sale', 'ReadyToSale', 'OpenStock', 'Open Stock') OR workingstatus = 'Polyhouse')
                   AND status NOT IN ('Internal Dispatch', 'Final Dispatch', 'Grafting', 'Move for Grafting', 'Healing', 'Hardening', 'Clip Stick Removal', 'Removed', 'Grafted', 'Empty After Shifting', 'Empty After Grafting')";
        $cRes = $krfconn->query($cSql);
        $remTrays = $cRes ? (int)$cRes->fetch_assoc()['rem'] : 0;

        // If remaining is 0, update production stock order status and connected tables
        if ($remTrays === 0) {
            $krfconn->query("UPDATE krf_production_stockorder 
                             SET status = 'remove', 
                                 workingstatus = 'Removed', 
                                 tedit = '{$teditDateEsc}', 
                                 teditremark = '{$teditRemarkEsc}', 
                                 remarks = CONCAT(IFNULL(remarks,''), ' [All trays removed from Bed {$bedEsc}{$reasonSuffix}]'), 
                                 updated_at = NOW() 
                             WHERE projectid = {$projectid} 
                               AND (stockorder_no = '{$soEscActual}' OR psono = '{$psoEscActual}' OR po_number = '{$cleanSoEsc}' OR stockorder_no = 'RSO-{$cleanSoEsc}' OR stockorder_no = 'OS-{$cleanSoEsc}')");

            $krfconn->query("UPDATE krf_readystock_allocation 
                             SET status = 'cancel', 
                                 tedit = '{$teditDateEsc}', 
                                 teditremark = '{$teditRemarkEsc}', 
                                 updated_at = NOW(), 
                                 updated_by = '{$updated_by}' 
                             WHERE projectid = {$projectid} 
                               AND (stockorder_no = '{$soEscActual}' OR allocate_stockorder_no = '{$soEscActual}' OR psono = '{$psoEscActual}') 
                               AND (tedit IS NULL OR tedit = '')");

            $krfconn->query("UPDATE krf_ready_stock_reserved_qty 
                             SET tedit = '{$teditDateEsc}', 
                                 teditremark = '{$teditRemarkEsc}', 
                                 updated_at = NOW() 
                             WHERE projectid = {$projectid} 
                               AND stockorder_no = '{$soEscActual}' 
                               AND (tedit IS NULL OR tedit = '')");

            $krfconn->query("UPDATE krf_productionorder_consumed 
                             SET status = 'Cancelled', 
                                 confirmstatus = 'cancelled', 
                                 tedit = '{$teditDateEsc}', 
                                 teditremark = '{$teditRemarkEsc}', 
                                 remark = CONCAT(IFNULL(remark,''), ' | Cancelled on Bed Removal" . (!empty($removal_remark) ? " ({$userRemarkEsc})" : "") . "'), 
                                 updated_at = NOW() 
                             WHERE projectid = {$projectid} 
                               AND (stockorderno = '{$soEscActual}' OR allocated_stockorderno = '{$soEscActual}' OR psono = '{$psoEscActual}') 
                               AND (tedit IS NULL OR tedit = '')");

            $krfconn->query("UPDATE krf_provisional_sales_order_items 
                             SET status = 'cancelled', 
                                 tedit = '{$teditDateEsc}', 
                                 teditremark = '{$teditRemarkEsc}', 
                                 remark = CONCAT(IFNULL(remark,''), ' | Cancelled on Bed Removal" . (!empty($removal_remark) ? " ({$userRemarkEsc})" : "") . "'), 
                                 updatedat = '{$curDateOnly}', 
                                 updatedby = '{$updated_by}' 
                             WHERE projectid = {$projectid} 
                               AND (stockorder_no = '{$soEscActual}' OR psono = '{$psoEscActual}') 
                               AND (tedit IS NULL OR tedit = '')");

            $krfconn->query("UPDATE krf_sales_order_items 
                             SET status = 'Cancelled', 
                                 tedit = '{$teditDateEsc}', 
                                 teditremark = '{$teditRemarkEsc}', 
                                 remark = CONCAT(IFNULL(remark,''), ' | Cancelled on Bed Removal" . (!empty($removal_remark) ? " ({$userRemarkEsc})" : "") . "') 
                             WHERE projectid = {$projectid} 
                               AND (stockorder_no = '{$soEscActual}' OR psono = '{$psoEscActual}') 
                               AND (tedit IS NULL OR tedit = '')");
        }

        $krfconn->commit();

        outJson([
            'status' => 'success',
            'message' => "Successfully removed {$removedTraysCount} tray(s) (" . number_format($totalPlantsRemoved) . " plants) from {$bed_no}!",
            'removed_count' => $removedTraysCount,
            'removed_ids' => $validIds,
            'removed_barcodes' => $barcodesRemoved,
            'remaining_trays' => $remTrays,
            'remaining_plants' => $remTrays * $qtyPerTray
        ]);

    } catch (Throwable $e) {
        $krfconn->rollback();
        outJson(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

if ($action === 'addtrays') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $projectid      = (int)($_POST['projectid'] ?? 0);
    $locationid     = trim($_POST['locationid'] ?? '');
    $polyhouseid    = (int)($_POST['polyhouseid'] ?? 0);
    $bed_no         = trim($_POST['bed_no'] ?? '');
    $stockorder_no  = trim($_POST['stockorder_no'] ?? '');
    $psono          = trim($_POST['psono'] ?? '');
    $productid      = (int)($_POST['productid'] ?? 0);
    $tray_id        = (int)($_POST['tray_id'] ?? 0);
    $num_trays      = (int)($_POST['num_trays'] ?? 0);
    $tray_status    = trim($_POST['tray_status'] ?? '');
    $health_status  = trim($_POST['health_status'] ?? 'Healthy');
    $shiftingstatus = trim($_POST['shiftingstatus'] ?? 'nonshifted');
    $updated_by     = trim($_POST['loginid'] ?? $_SESSION['loginname'] ?? $_SESSION['loginid'] ?? 'System');

    if ($projectid <= 0) $projectid = 17;
    if ($polyhouseid <= 0 || empty($bed_no) || empty($stockorder_no)) {
        outJson(['status' => 'error', 'message' => 'Missing required order details.']);
    }
    if ($num_trays < 1) {
        outJson(['status' => 'error', 'message' => 'Number of trays to add must be at least 1.']);
    }

    $krfconn->begin_transaction();
    try {
        $cleanBed = preg_replace('/^Bed[-\s]*/i', '', $bed_no);
        $bedEsc = $krfconn->real_escape_string($bed_no);
        $cleanBedEsc = $krfconn->real_escape_string($cleanBed);
        $bedPrefEsc = $krfconn->real_escape_string('Bed-' . $cleanBed);

        $cleanSo = preg_replace('/^(RSO|OS|PSO)-/i', '', $stockorder_no);
        $soEsc = $krfconn->real_escape_string($stockorder_no);
        $cleanSoEsc = $krfconn->real_escape_string($cleanSo);
        $psoEsc = $krfconn->real_escape_string($psono);

        // Check if there is an existing active record in this bed
        $where = [
            "projectid = '{$projectid}'",
            "CAST(polyhouseid AS UNSIGNED) = {$polyhouseid}",
            "(bed_no = '{$bedEsc}' OR bed_no = '{$cleanBedEsc}' OR bed_no = '{$bedPrefEsc}')",
            "(stockorder_no = '{$soEsc}' OR stockorder_no = '{$cleanSoEsc}' OR stockorder_no = 'RSO-{$cleanSoEsc}' OR stockorder_no = 'OS-{$cleanSoEsc}' OR psono = '{$soEsc}' OR psono = '{$cleanSoEsc}' OR psono = '{$psoEsc}' OR lotno = '{$cleanSoEsc}' OR batchno = '{$cleanSoEsc}' OR bedqrcode = '{$soEsc}')",
            "status != 'Removed'",
            "workingstatus != 'Removed'"
        ];
        if ($productid > 0) {
            $where[] = "productid = '{$productid}'";
        }
        $where[] = "(tedit IS NULL OR tedit = '')";
        $tplRes = $krfconn->query("SELECT * FROM krf_polyhouse_plant_details WHERE " . implode(" AND ", $where) . " ORDER BY polyhouseplantid DESC LIMIT 1");
        $template = ($tplRes && $tplRes->num_rows > 0) ? $tplRes->fetch_assoc() : null;

        // If not found in bed, check any active record in polyhouse plant details for this order
        if (!$template) {
            $tplRes2 = $krfconn->query("SELECT * FROM krf_polyhouse_plant_details WHERE projectid = '{$projectid}' AND (stockorder_no = '{$soEsc}' OR stockorder_no = '{$cleanSoEsc}' OR stockorder_no = 'RSO-{$cleanSoEsc}' OR stockorder_no = 'OS-{$cleanSoEsc}' OR psono = '{$soEsc}' OR psono = '{$psoEsc}') AND (tedit IS NULL OR tedit = '') AND status != 'Removed' ORDER BY polyhouseplantid DESC LIMIT 1");
            $template = ($tplRes2 && $tplRes2->num_rows > 0) ? $tplRes2->fetch_assoc() : null;
        }

        // Fetch production stock order
        $chkSo = $krfconn->prepare("SELECT * FROM krf_production_stockorder WHERE projectid = ? AND (stockorder_no = ? OR psono = ? OR po_number = ?) AND (tedit IS NULL OR tedit = '') LIMIT 1");
        $chkSo->bind_param('isss', $projectid, $stockorder_no, $psono, $cleanSo);
        $chkSo->execute();
        $soData = $chkSo->get_result()->fetch_assoc();
        $chkSo->close();

        $pId = (int)($template['polyhouseid'] ?? $polyhouseid);
        $bNo = $bed_no;
        $soNo = $template['stockorder_no'] ?? ($soData['stockorder_no'] ?? $stockorder_no);
        $psoNo = $template['psono'] ?? ($soData['psono'] ?? ($psono ?: $soNo));
        $prodId = (int)($template['productid'] ?? ($soData['productid'] ?? $productid));
        $prodName = $template['productname'] ?? ($soData['product_name'] ?? 'Plant');
        $locId = $template['locationid'] ?? ($soData['sowinglocationid'] ?? $locationid);
        $trayId = (int)($template['tray_id'] ?? ($soData['roottrayid'] ?? ($tray_id ?: 1)));
        $sowDate = $template['sowing_date'] ?? ($template['sowingdate_internal'] ?? ($soData['sowingdate'] ?? date('Y-m-d')));
        $dispDate = $template['dispatchdate_internal'] ?? ($template['expected_harvest_date'] ?? ($soData['expected_delivery_date'] ?? date('Y-m-d', strtotime('+30 days'))));
        $variety = $template['variety'] ?? ($soData['product_name'] ?? $prodName);
        $poNumber = $template['lotno'] ?? ($soData['po_number'] ?? $cleanSo);
        $bedQr = $template['bedqrcode'] ?? $soNo;

        // Fetch tray master
        $tRes = $krfconn->query("SELECT tray_code, cavity_count FROM krf_tray_master WHERE tray_id = {$trayId} LIMIT 1");
        $trayRow = $tRes ? $tRes->fetch_assoc() : null;
        $qtyPerTray = (int)($template['qtypertray'] ?? ($trayRow['cavity_count'] ?? 209));
        if ($qtyPerTray <= 0) $qtyPerTray = 1;
        $trayCode = $template['tray_code'] ?? ($trayRow['tray_code'] ?? 'TRAY');

        // Check if product is grafted
        $sourceType = $template['source_type'] ?? 'Seed';
        if (empty($template['source_type']) && $prodId > 0) {
            $pmRes = $krfconn->query("SELECT IsGrafted FROM product_master WHERE ProductID = {$prodId} LIMIT 1");
            $pmRow = $pmRes ? $pmRes->fetch_assoc() : null;
            $isNonGrafted = (strcasecmp($pmRow['IsGrafted'] ?? '', 'NonGrafted') === 0);
            $sourceType = $isNonGrafted ? 'Seed' : 'Graft';
        }

        // Check if ready stock
        $chkRts = $krfconn->prepare("SELECT COUNT(*) AS c FROM krf_readytosale_stocks WHERE projectid = ? AND (stockorder_no = ? OR psono = ?)");
        $chkRts->bind_param('iss', $projectid, $soNo, $psoNo);
        $chkRts->execute();
        $rtsCount = (int)($chkRts->get_result()->fetch_assoc()['c'] ?? 0);
        $chkRts->close();
        
        $isReadyStock = ($rtsCount > 0 || stripos($soNo, 'RSO-') === 0 || ($template && stripos($template['status'], 'ready') !== false) || (!empty($tray_status) && stripos($tray_status, 'ready') !== false));

        if (empty($tray_status)) {
            $tray_status = $isReadyStock ? 'Readytosale' : ($template['status'] ?? 'Polyhouse');
        }
        $plantStage = $isReadyStock ? 'ReadyToSale' : ($template['growth_stage'] ?? 'Polyhouse');
        $plantStatus = $tray_status;
        if (empty($health_status)) $health_status = 'Healthy';
        if (!in_array($shiftingstatus, ['shifted', 'nonshifted'], true)) {
            $shiftingstatus = $template['shiftingstatus'] ?? 'nonshifted';
        }

        $datePrefix = date('ymd');
        $barcodePrefix = (stripos($soNo, 'OS-') === 0) ? 'OS' : ((stripos($soNo, 'RSO-') === 0) ? 'RS' : 'SO');

        $inPlantSql = "INSERT INTO krf_polyhouse_plant_details (
            planted_date, sowing_date, sowingdate_internal, sowinglotno_internal,
            sowingbatchno, stockorder_no, psono, roottype,
            locationid, polyhouseid, projectid, lotno, batchno, barcode,
            productid, productname, variety, source_type, cultivation_type,
            tray_id, tray_code, qtypertray, tray_position, bedrackname, bed_no,
            growth_stage, health_status, shiftingstatus, status, workingstatus,
            dispatchdate_internal, expected_harvest_date,
            bedqrcode,
            created_at, created_by, updated_at, updated_by
        ) VALUES (
            ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, 'Polyhouse',
            ?, ?,
            ?,
            NOW(), ?, NOW(), ?
        )";
        $inPlantStmt = $krfconn->prepare($inPlantSql);
        if (!$inPlantStmt) throw new Exception("Prepare plant insert failed: " . $krfconn->error);

        $inRtsStmt = null;
        if ($isReadyStock || $tray_status === 'Readytosale') {
            $rtsSql = "INSERT INTO krf_readytosale_stocks (
                stockorder_no, psono, projectid, productid, productname,
                trayid, qtypertray, locationid, locationname, polyhouseplantid, barcode,
                deliverydate, remarks, status, created_by, created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, 'Readytosale', ?, NOW(), NOW()
            )";
            $inRtsStmt = $krfconn->prepare($rtsSql);
            if (!$inRtsStmt) throw new Exception("Prepare ready to sale insert failed: " . $krfconn->error);
        }

        $newTrays = [];
        $addedPlants = $num_trays * $qtyPerTray;

        for ($i = 1; $i <= $num_trays; $i++) {
            // Collision-proof barcode generation
            do {
                $randHex = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));
                $seq = str_pad((string)$i, 3, '0', STR_PAD_LEFT);
                $barcode = $barcodePrefix . $datePrefix . $randHex . $seq;
                $chkBarcode = $krfconn->query("SELECT polyhouseplantid FROM krf_polyhouse_plant_details WHERE barcode = '{$barcode}' LIMIT 1");
            } while ($chkBarcode && $chkBarcode->num_rows > 0);

            $roottype = $template['roottype'] ?? 'Root';
            $cultivationType = $template['cultivation_type'] ?? 'Tray';
            $sowingLotInternal = $template['sowinglotno_internal'] ?? $poNumber;
            $sowingBatchNo = $template['sowingbatchno'] ?? $poNumber;
            $lotNo = $template['lotno'] ?? $poNumber;
            $batchNo = $template['batchno'] ?? $poNumber;
            $trayPos = $template['tray_position'] ?? 'Start';

            $inPlantStmt->bind_param(
                'sssssssssiisssissssisissssssssssss',
                $sowDate,
                $sowDate,
                $sowDate,
                $sowingLotInternal,
                $sowingBatchNo,
                $soNo,
                $psoNo,
                $roottype,
                $locId,
                $pId,
                $projectid,
                $lotNo,
                $batchNo,
                $barcode,
                $prodId,
                $prodName,
                $variety,
                $sourceType,
                $cultivationType,
                $trayId,
                $trayCode,
                $qtyPerTray,
                $trayPos,
                $bNo,
                $bNo,
                $plantStage,
                $health_status,
                $shiftingstatus,
                $plantStatus,
                $dispDate,
                $dispDate,
                $bedQr,
                $updated_by,
                $updated_by
            );

            if (!$inPlantStmt->execute()) {
                throw new Exception("Error inserting plant record: " . $inPlantStmt->error);
            }
            $newPlantId = (int)$inPlantStmt->insert_id;

            if ($inRtsStmt) {
                $locName = $locId;
                $rtsRemarks = "Ready Stock (Bill No: {$poNumber})";
                $inRtsStmt->bind_param(
                    'ssiisiisssssss',
                    $soNo,
                    $psoNo,
                    $projectid,
                    $prodId,
                    $prodName,
                    $trayId,
                    $qtyPerTray,
                    $locId,
                    $locName,
                    $newPlantId,
                    $barcode,
                    $dispDate,
                    $rtsRemarks,
                    $updated_by
                );
                $inRtsStmt->execute();
            }

            $newTrays[] = [
                'polyhouseplantid' => $newPlantId,
                'barcode'          => $barcode,
                'bedqrcode'        => $bedQr,
                'status'           => $plantStatus,
                'health_status'    => $health_status,
                'shiftingstatus'   => $shiftingstatus,
                'qtypertray'       => $qtyPerTray
            ];
        }

        $inPlantStmt->close();
        if ($inRtsStmt) $inRtsStmt->close();

        // Update production stockorder
        $soEscActual = $krfconn->real_escape_string($soNo);
        $psoEscActual = $krfconn->real_escape_string($psoNo);
        $krfconn->query("UPDATE krf_production_stockorder 
                         SET remarks = CONCAT(IFNULL(remarks,''), ' [{$num_trays} Trays added in Bed {$bedEsc}]'), 
                             quantity = quantity + {$addedPlants}, 
                             sowingallotedqty = sowingallotedqty + {$addedPlants}, 
                             status = IF(status = 'remove', 'Confirmed', status),
                             editedby = '{$updated_by}', 
                             editedon = NOW(), 
                             updated_at = NOW() 
                         WHERE projectid = {$projectid} 
                           AND (stockorder_no = '{$soEscActual}' OR psono = '{$psoEscActual}' OR po_number = '{$cleanSoEsc}' OR stockorder_no = 'RSO-{$cleanSoEsc}' OR stockorder_no = 'OS-{$cleanSoEsc}')");

        // Count total remaining active occupied trays for this order in this bed
        $cSql = "SELECT COUNT(*) as tot 
                 FROM krf_polyhouse_plant_details 
                 WHERE projectid = '{$projectid}'
                   AND CAST(polyhouseid AS UNSIGNED) = {$polyhouseid}
                   AND (bed_no = '{$bedEsc}' OR bed_no = '{$cleanBedEsc}' OR bed_no = '{$bedPrefEsc}')
                   AND (stockorder_no = '{$soEsc}' OR stockorder_no = '{$cleanSoEsc}' OR stockorder_no = 'RSO-{$cleanSoEsc}' OR stockorder_no = 'OS-{$cleanSoEsc}' OR psono = '{$soEsc}' OR psono = '{$cleanSoEsc}' OR psono = '{$psoEsc}' OR lotno = '{$cleanSoEsc}' OR batchno = '{$cleanSoEsc}' OR bedqrcode = '{$soEsc}')
                   AND (tedit IS NULL OR tedit = '')
                   AND status != 'Removed'
                   AND workingstatus != 'Removed'
                   AND (status IN ('Polyhouse', 'Readytosale', 'Ready to sale', 'ReadyToSale', 'OpenStock', 'Open Stock') OR workingstatus = 'Polyhouse')
                   AND status NOT IN ('Internal Dispatch', 'Final Dispatch', 'Grafting', 'Move for Grafting', 'Healing', 'Hardening', 'Clip Stick Removal', 'Removed', 'Grafted', 'Empty After Shifting', 'Empty After Grafting')";
        $cRes = $krfconn->query($cSql);
        $totTrays = $cRes ? (int)$cRes->fetch_assoc()['tot'] : count($newTrays);

        $krfconn->commit();

        outJson([
            'status' => 'success',
            'message' => "Successfully added {$num_trays} tray(s) (" . number_format($addedPlants) . " plants) to {$bed_no}!",
            'added_count' => $num_trays,
            'added_plants' => $addedPlants,
            'new_trays' => $newTrays,
            'total_trays' => $totTrays,
            'total_plants' => $totTrays * $qtyPerTray,
            'qtypertray' => $qtyPerTray
        ]);

    } catch (Throwable $e) {
        $krfconn->rollback();
        outJson(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

if ($action === 'editorderqty') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $projectid     = (int)($_POST['projectid'] ?? 0);
    $locationid    = trim($_POST['locationid'] ?? '');
    $polyhouseid   = (int)($_POST['polyhouseid'] ?? 0);
    $bed_no        = trim($_POST['bed_no'] ?? '');
    $stockorder_no = trim($_POST['stockorder_no'] ?? '');
    $psono         = trim($_POST['psono'] ?? '');
    $productid     = (int)($_POST['productid'] ?? 0);
    $tray_id       = (int)($_POST['tray_id'] ?? 0);
    $new_trays     = (int)($_POST['new_trays'] ?? 0);
    $updated_by     = trim($_POST['loginid'] ?? $_SESSION['loginname'] ?? $_SESSION['loginid'] ?? 'System');
    $removal_remark = trim($_POST['removal_remark'] ?? $_POST['remark'] ?? $_POST['teditremark'] ?? '');

    if ($projectid <= 0) $projectid = 17;
    if ($polyhouseid <= 0 || empty($bed_no) || empty($stockorder_no)) {
        outJson(['status' => 'error', 'message' => 'Missing required order details.']);
    }
    if ($new_trays < 1) {
        outJson(['status' => 'error', 'message' => 'New tray quantity must be at least 1 tray. If you want to remove the entire order from this bed, please click Remove.']);
    }

    $krfconn->begin_transaction();
    try {
        $cleanBed = preg_replace('/^Bed[-\s]*/i', '', $bed_no);
        $bedEsc = $krfconn->real_escape_string($bed_no);
        $cleanBedEsc = $krfconn->real_escape_string($cleanBed);
        $bedPrefEsc = $krfconn->real_escape_string('Bed-' . $cleanBed);

        $cleanSo = preg_replace('/^(RSO|OS|PSO)-/i', '', $stockorder_no);
        $soEsc = $krfconn->real_escape_string($stockorder_no);
        $cleanSoEsc = $krfconn->real_escape_string($cleanSo);
        $psoEsc = $krfconn->real_escape_string($psono);

        $where = [
            "projectid = '{$projectid}'",
            "CAST(polyhouseid AS UNSIGNED) = {$polyhouseid}",
            "(bed_no = '{$bedEsc}' OR bed_no = '{$cleanBedEsc}' OR bed_no = '{$bedPrefEsc}')",
            "(stockorder_no = '{$soEsc}' OR stockorder_no = '{$cleanSoEsc}' OR stockorder_no = 'RSO-{$cleanSoEsc}' OR stockorder_no = 'OS-{$cleanSoEsc}' OR psono = '{$soEsc}' OR psono = '{$cleanSoEsc}' OR psono = '{$psoEsc}' OR lotno = '{$cleanSoEsc}' OR batchno = '{$cleanSoEsc}' OR bedqrcode = '{$soEsc}')",
            "(tedit IS NULL OR tedit = '')",
            "status != 'Removed'",
            "workingstatus != 'Removed'"
        ];
        if ($productid > 0) {
            $where[] = "productid = '{$productid}'";
        }

        $whereClause = implode(" AND ", $where);
        $sql = "SELECT * FROM krf_polyhouse_plant_details WHERE {$whereClause} ORDER BY polyhouseplantid ASC";
        $res = $krfconn->query($sql);
        if (!$res) throw new Exception("Query failed: " . $krfconn->error);

        $existingRecords = [];
        while ($row = $res->fetch_assoc()) {
            $existingRecords[] = $row;
        }

        $currentCount = count($existingRecords);
        if ($currentCount === 0) {
            // Lookup from krf_production_stockorder
            $chkSo = $krfconn->prepare("SELECT * FROM krf_production_stockorder WHERE projectid = ? AND (stockorder_no = ? OR psono = ? OR po_number = ?) AND (tedit IS NULL OR tedit = '') LIMIT 1");
            $chkSo->bind_param('isss', $projectid, $stockorder_no, $psono, $cleanSo);
            $chkSo->execute();
            $soData = $chkSo->get_result()->fetch_assoc();
            $chkSo->close();

            if (!$soData) {
                throw new Exception("No production stock order found for {$stockorder_no}.");
            }

            $pId = $polyhouseid;
            $bNo = $bed_no;
            $soNo = $soData['stockorder_no'] ?: $stockorder_no;
            $psoNo = $soData['psono'] ?: ($psono ?: $soNo);
            $prodId = (int)($soData['productid'] ?: $productid);
            $prodName = $soData['product_name'];
            $locId = $soData['sowinglocationid'] ?: $locationid;
            $trayId = (int)($soData['roottrayid'] ?: $tray_id ?: 1);
            $sowDate = $soData['sowingdate'] ?: date('Y-m-d');
            $dispDate = $soData['expected_delivery_date'] ?: date('Y-m-d', strtotime('+30 days'));
            $variety = $prodName;
            $poNumber = $soData['po_number'] ?: $cleanSo;
            $bedQr = $soNo;

            // Fetch tray master
            $tRes = $krfconn->query("SELECT tray_code, cavity_count FROM krf_tray_master WHERE tray_id = {$trayId} LIMIT 1");
            $trayRow = $tRes ? $tRes->fetch_assoc() : null;
            $qtyPerTray = (int)($trayRow['cavity_count'] ?? 209);
            if ($qtyPerTray <= 0) $qtyPerTray = 1;
            $trayCode = $trayRow['tray_code'] ?? 'TRAY';

            // Check if product is grafted
            $pmRes = $krfconn->query("SELECT IsGrafted FROM product_master WHERE ProductID = {$prodId} LIMIT 1");
            $pmRow = $pmRes ? $pmRes->fetch_assoc() : null;
            $isNonGrafted = (strcasecmp($pmRow['IsGrafted'] ?? '', 'NonGrafted') === 0);
            $sourceType = $isNonGrafted ? 'Seed' : 'Graft';
            $isReadyStock = (stripos($soNo, 'RSO-') === 0 || stripos($soData['remarks'] ?? '', 'Ready Stock') !== false);

            $deltaTrays = $new_trays;
            $deltaPlants = $deltaTrays * $qtyPerTray;
            $datePrefix = date('ymd');
            $barcodePrefix = (stripos($soNo, 'OS-') === 0) ? 'OS' : ((stripos($soNo, 'RSO-') === 0) ? 'RS' : 'SO');

            $inPlantSql = "INSERT INTO krf_polyhouse_plant_details (
                planted_date, sowing_date, sowingdate_internal, sowinglotno_internal,
                sowingbatchno, stockorder_no, psono, roottype,
                locationid, polyhouseid, projectid, lotno, batchno, barcode,
                productid, productname, variety, source_type, cultivation_type,
                tray_id, tray_code, qtypertray, tray_position, bedrackname, bed_no,
                growth_stage, health_status, status, workingstatus,
                dispatchdate_internal, expected_harvest_date,
                bedqrcode,
                created_at, created_by, updated_at, updated_by
            ) VALUES (
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, 'Healthy', ?, 'Polyhouse',
                ?, ?,
                ?,
                NOW(), ?, NOW(), ?
            )";
            $inPlantStmt = $krfconn->prepare($inPlantSql);
            if (!$inPlantStmt) throw new Exception("Prepare plant insert failed: " . $krfconn->error);

            $inRtsStmt = null;
            if ($isReadyStock) {
                $rtsSql = "INSERT INTO krf_readytosale_stocks (
                    stockorder_no, psono, projectid, productid, productname,
                    trayid, qtypertray, locationid, locationname, polyhouseplantid, barcode,
                    deliverydate, remarks, status, created_by, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, 'Readytosale', ?, NOW(), NOW()
                )";
                $inRtsStmt = $krfconn->prepare($rtsSql);
            }

            $plantStage = $isReadyStock ? 'ReadyToSale' : 'Polyhouse';
            $plantStatus = $isReadyStock ? 'Readytosale' : 'Polyhouse';

            for ($i = 1; $i <= $deltaTrays; $i++) {
                $barcode = $barcodePrefix . $datePrefix . strtoupper(substr(uniqid(), -4)) . str_pad((string)$i, 3, '0', STR_PAD_LEFT);
                $roottype = 'Root';
                $cultivationType = 'Tray';
                $sowingLotInternal = $poNumber;
                $sowingBatchNo = $poNumber;
                $lotNo = $poNumber;
                $batchNo = $poNumber;
                $trayPos = 'Start';

                $inPlantStmt->bind_param(
                    'ssssssssssisssissssisissssssssss',
                    $sowDate,
                    $sowDate,
                    $sowDate,
                    $sowingLotInternal,
                    $sowingBatchNo,
                    $soNo,
                    $psoNo,
                    $roottype,
                    $locId,
                    $pId,
                    $projectid,
                    $lotNo,
                    $batchNo,
                    $barcode,
                    $prodId,
                    $prodName,
                    $variety,
                    $sourceType,
                    $cultivationType,
                    $trayId,
                    $trayCode,
                    $qtyPerTray,
                    $trayPos,
                    $bNo,
                    $bNo,
                    $plantStage,
                    $plantStatus,
                    $dispDate,
                    $dispDate,
                    $bedQr,
                    $updated_by,
                    $updated_by
                );
                if (!$inPlantStmt->execute()) {
                    throw new Exception("Error inserting plant record: " . $inPlantStmt->error);
                }
                $newPlantId = (string)$inPlantStmt->insert_id;

                if ($inRtsStmt) {
                    $locName = $locId;
                    $remarks = "Ready Stock (Bill No: {$poNumber})";
                    $inRtsStmt->bind_param(
                        'ssiisiisssssss',
                        $soNo,
                        $psoNo,
                        $projectid,
                        $prodId,
                        $prodName,
                        $trayId,
                        $qtyPerTray,
                        $locId,
                        $locName,
                        $newPlantId,
                        $barcode,
                        $dispDate,
                        $remarks,
                        $updated_by
                    );
                    $inRtsStmt->execute();
                }
            }
            $inPlantStmt->close();
            if ($inRtsStmt) $inRtsStmt->close();

            // Update production stock order
            $cleanSoEsc = $krfconn->real_escape_string($cleanSo);
            $soEsc = $krfconn->real_escape_string($soNo);
            $psoEsc = $krfconn->real_escape_string($psoNo);
            $krfconn->query("UPDATE krf_production_stockorder SET quantity = {$deltaPlants}, sowingallotedqty = {$deltaPlants}, status = 'Confirmed', editedby = '{$updated_by}', editedon = NOW(), updated_at = NOW() WHERE projectid = {$projectid} AND (stockorder_no = '{$soEsc}' OR psono = '{$psoEsc}' OR po_number = '{$cleanSoEsc}')");

            $krfconn->commit();
            outJson([
                'status' => 'success',
                'message' => "Order quantity set to {$new_trays} tray(s) (" . ($new_trays * $qtyPerTray) . " plants) for {$soNo} in {$bNo} successfully!",
                'old_trays' => 0,
                'new_trays' => $new_trays,
                'qtypertray' => $qtyPerTray,
                'stockorder_no' => $soNo,
                'bed_no' => $bNo
            ]);
        }

        $template = $existingRecords[0];
        $qtyPerTray = (int)($template['qtypertray'] ?: 209);
        if ($qtyPerTray <= 0) $qtyPerTray = 1;
        $locId = $template['locationid'] ?: $locationid;
        $pId = (int)($template['polyhouseid'] ?: $polyhouseid);
        $bNo = $template['bed_no'] ?: $bed_no;
        $soNo = $template['stockorder_no'] ?: $stockorder_no;
        $psoNo = $template['psono'] ?: ($psono ?: $soNo);
        $prodId = (int)($template['productid'] ?: $productid);
        $prodName = $template['productname'];
        $variety = $template['variety'];
        $trayId = (int)($template['tray_id'] ?: $tray_id);
        $trayCode = $template['tray_code'];
        $sowDate = $template['sowing_date'] ?: $template['sowingdate_internal'];
        $dispDate = $template['dispatchdate_internal'] ?: $template['expected_harvest_date'];
        $bedQr = $template['bedqrcode'] ?: $soNo;

        // Check if ready to sale records exist
        $chkRts = $krfconn->prepare("SELECT COUNT(*) AS c FROM krf_readytosale_stocks WHERE projectid = ? AND (stockorder_no = ? OR psono = ?)");
        $chkRts->bind_param('iss', $projectid, $soNo, $psoNo);
        $chkRts->execute();
        $rtsCount = (int)($chkRts->get_result()->fetch_assoc()['c'] ?? 0);
        $chkRts->close();
        $isReadyStock = ($rtsCount > 0 || stripos($template['status'], 'ready') !== false || stripos($soNo, 'RSO-') === 0);

        if ($new_trays > $currentCount) {
            throw new Exception("Adding trays is not allowed. You can only remove trays from currently occupied stock.");
        } else if ($new_trays < $currentCount) {
            // REDUCE TRAYS: Update status and remarks without deleting
            $deltaTrays = $currentCount - $new_trays;
            $deltaPlants = $deltaTrays * $qtyPerTray;

            // Pick the last $deltaTrays records to mark as Removed
            $recordsToUpdate = array_slice($existingRecords, -$deltaTrays);
            $plantIdsToUpdate = array_map(function($r) { return (int)$r['polyhouseplantid']; }, $recordsToUpdate);

            if (!empty($plantIdsToUpdate)) {
                $idListStr = implode(',', $plantIdsToUpdate);
                $krfconn->query("UPDATE krf_readytosale_stocks SET status = 'Removed', remarks = CONCAT(IFNULL(remarks,''), ' [Reduced from Bed {$bedEsc}]'), updated_at = NOW() WHERE polyhouseplantid IN ({$idListStr})");
                $krfconn->query("UPDATE krf_polyhouse_plant_details SET status = 'Removed', workingstatus = 'Removed', remarks = CONCAT(IFNULL(remarks,''), ' [Reduced from Bed {$bedEsc}]'), updated_at = NOW(), updated_by = '{$updated_by}' WHERE polyhouseplantid IN ({$idListStr})");
            }

            // Update krf_production_stockorder (decrease quantity)
            $soEsc = $krfconn->real_escape_string($soNo);
            $psoEsc = $krfconn->real_escape_string($psoNo);
            $cleanSoEsc = $krfconn->real_escape_string($cleanSo);
            $krfconn->query("UPDATE krf_production_stockorder SET remarks = CONCAT(IFNULL(remarks,''), ' [Trays reduced in Bed {$bedEsc}]'), quantity = GREATEST(0, quantity - {$deltaPlants}), sowingallotedqty = GREATEST(0, sowingallotedqty - {$deltaPlants}), editedby = '{$updated_by}', editedon = NOW(), updated_at = NOW() WHERE projectid = {$projectid} AND (stockorder_no = '{$soEsc}' OR psono = '{$psoEsc}' OR po_number = '{$cleanSoEsc}' OR stockorder_no = 'RSO-{$cleanSoEsc}' OR stockorder_no = 'OS-{$cleanSoEsc}')");
        }

        $krfconn->commit();
        outJson([
            'status' => 'success',
            'message' => "Order quantity updated to {$new_trays} tray(s) (" . ($new_trays * $qtyPerTray) . " plants) for {$soNo} in {$bNo} successfully!",
            'old_trays' => $currentCount,
            'new_trays' => $new_trays,
            'qtypertray' => $qtyPerTray,
            'stockorder_no' => $soNo,
            'bed_no' => $bNo
        ]);

    } catch (Throwable $e) {
        $krfconn->rollback();
        outJson(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

if ($action === 'removeorderfrombed') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $projectid     = (int)($_POST['projectid'] ?? 0);
    $locationid    = trim($_POST['locationid'] ?? '');
    $polyhouseid   = (int)($_POST['polyhouseid'] ?? 0);
    $bed_no        = trim($_POST['bed_no'] ?? '');
    $stockorder_no = trim($_POST['stockorder_no'] ?? '');
    $psono         = trim($_POST['psono'] ?? '');
    $productid     = (int)($_POST['productid'] ?? 0);
    $tray_id       = (int)($_POST['tray_id'] ?? 0);
    $updated_by     = trim($_POST['loginid'] ?? $_SESSION['loginname'] ?? $_SESSION['loginid'] ?? 'System');
    $removal_remark = trim($_POST['removal_remark'] ?? $_POST['remark'] ?? $_POST['teditremark'] ?? '');

    if ($projectid <= 0) $projectid = 17;
    if ($polyhouseid <= 0 || empty($bed_no) || empty($stockorder_no)) {
        outJson(['status' => 'error', 'message' => 'Missing required order details.']);
    }

    $krfconn->begin_transaction();
    try {
        $cleanBed = preg_replace('/^Bed[-\s]*/i', '', $bed_no);
        $bedEsc = $krfconn->real_escape_string($bed_no);
        $cleanBedEsc = $krfconn->real_escape_string($cleanBed);
        $bedPrefEsc = $krfconn->real_escape_string('Bed-' . $cleanBed);

        $cleanSo = preg_replace('/^(RSO|OS|PSO)-/i', '', $stockorder_no);
        $soEsc = $krfconn->real_escape_string($stockorder_no);
        $cleanSoEsc = $krfconn->real_escape_string($cleanSo);
        $psoEsc = $krfconn->real_escape_string($psono);

        $where = [
            "projectid = '{$projectid}'",
            "CAST(polyhouseid AS UNSIGNED) = {$polyhouseid}",
            "(bed_no = '{$bedEsc}' OR bed_no = '{$cleanBedEsc}' OR bed_no = '{$bedPrefEsc}')",
            "(stockorder_no = '{$soEsc}' OR stockorder_no = '{$cleanSoEsc}' OR stockorder_no = 'RSO-{$cleanSoEsc}' OR stockorder_no = 'OS-{$cleanSoEsc}' OR psono = '{$soEsc}' OR psono = '{$cleanSoEsc}' OR psono = '{$psoEsc}' OR lotno = '{$cleanSoEsc}' OR batchno = '{$cleanSoEsc}' OR bedqrcode = '{$soEsc}')",
            "(tedit IS NULL OR tedit = '')",
            "status != 'Removed'",
            "workingstatus != 'Removed'"
        ];
        if ($productid > 0) {
            $where[] = "productid = '{$productid}'";
        }

        $whereClause = implode(" AND ", $where);
        $sql = "SELECT polyhouseplantid, qtypertray, stockorder_no, psono FROM krf_polyhouse_plant_details WHERE {$whereClause} ORDER BY polyhouseplantid ASC";
        $res = $krfconn->query($sql);
        if (!$res) throw new Exception("Query failed: " . $krfconn->error);

        $existingRecords = [];
        while ($row = $res->fetch_assoc()) {
            $existingRecords[] = $row;
        }

        $removedCount = count($existingRecords);
        if ($removedCount === 0) {
            throw new Exception("No active plant records found to remove for {$stockorder_no} in {$bed_no}.");
        }

        $qtyPerTray = (int)($existingRecords[0]['qtypertray'] ?: 209);
        if ($qtyPerTray <= 0) $qtyPerTray = 1;
        $totalPlantsRemoved = $removedCount * $qtyPerTray;
        $soNo = $existingRecords[0]['stockorder_no'] ?: $stockorder_no;
        $psoNo = $existingRecords[0]['psono'] ?: ($psono ?: $soNo);

        $plantIdsToUpdate = array_map(function($r) { return (int)$r['polyhouseplantid']; }, $existingRecords);
        $idListStr = implode(',', $plantIdsToUpdate);

        $currentDateTime = date('Y-m-d H:i:s');
        $curDateOnly     = date('Y-m-d');
        $teditDateEsc    = $krfconn->real_escape_string($currentDateTime);
        $userRemarkEsc   = $krfconn->real_escape_string($removal_remark);
        $teditRemarkText = "Removed from Bed {$bed_no} on date {$currentDateTime} by {$updated_by}" . (!empty($removal_remark) ? " - Reason: {$removal_remark}" : "");
        $teditRemarkEsc  = $krfconn->real_escape_string(substr($teditRemarkText, 0, 500));

        $soEscActual    = $krfconn->real_escape_string($soNo);
        $psoEscActual   = $krfconn->real_escape_string($psoNo);
        $reasonSuffix   = !empty($removal_remark) ? " Reason: {$userRemarkEsc}" : "";

        // 1. Update status, workingstatus, tedit & teditremark in krf_polyhouse_plant_details
        $krfconn->query("UPDATE krf_polyhouse_plant_details 
                         SET status = 'Removed', 
                             workingstatus = 'Removed', 
                             tedit = '{$teditDateEsc}', 
                             teditremark = '{$teditRemarkEsc}', 
                             remarks = CONCAT(IFNULL(remarks,''), ' [Removed from Bed {$bedEsc}{$reasonSuffix}]'), 
                             updated_at = NOW(), 
                             updated_by = '{$updated_by}' 
                         WHERE polyhouseplantid IN ({$idListStr})");

        // 2. Update status, tedit & teditremark in krf_readytosale_stocks
        $krfconn->query("UPDATE krf_readytosale_stocks 
                         SET status = 'Removed', 
                             tedit = '{$teditDateEsc}', 
                             teditremark = '{$teditRemarkEsc}', 
                             remarks = CONCAT(IFNULL(remarks,''), ' [Removed from Bed {$bedEsc}{$reasonSuffix}]'), 
                             updated_at = NOW() 
                         WHERE polyhouseplantid IN ({$idListStr})
                            OR (projectid = {$projectid} AND (stockorder_no = '{$soEscActual}' OR psono = '{$psoEscActual}') AND (tedit IS NULL OR tedit = ''))");

        // 3. Update status, quantity, tedit & teditremark in krf_production_stockorder
        $krfconn->query("UPDATE krf_production_stockorder 
                         SET status = 'remove', 
                             workingstatus = 'Removed', 
                             tedit = '{$teditDateEsc}', 
                             teditremark = '{$teditRemarkEsc}', 
                             remarks = CONCAT(IFNULL(remarks,''), ' [Removed from Bed {$bedEsc}{$reasonSuffix}]'), 
                             quantity = GREATEST(0, quantity - {$totalPlantsRemoved}), 
                             sowingallotedqty = GREATEST(0, sowingallotedqty - {$totalPlantsRemoved}), 
                             editedby = '{$updated_by}', 
                             editedon = '{$curDateOnly}', 
                             updated_at = NOW() 
                         WHERE projectid = {$projectid} 
                           AND (stockorder_no = '{$soEscActual}' OR psono = '{$psoEscActual}' OR po_number = '{$cleanSoEsc}' OR stockorder_no = 'RSO-{$cleanSoEsc}' OR stockorder_no = 'OS-{$cleanSoEsc}')");

        // 4. Update status, tedit & teditremark in krf_readystock_allocation (if allocated)
        $krfconn->query("UPDATE krf_readystock_allocation 
                         SET status = 'cancel', 
                             tedit = '{$teditDateEsc}', 
                             teditremark = '{$teditRemarkEsc}', 
                             updated_at = NOW(), 
                             updated_by = '{$updated_by}' 
                         WHERE projectid = {$projectid} 
                           AND (stockorder_no = '{$soEscActual}' OR allocate_stockorder_no = '{$soEscActual}' OR psono = '{$psoEscActual}') 
                           AND (tedit IS NULL OR tedit = '')");

        // 5. Update tedit & teditremark in krf_ready_stock_reserved_qty (if reserved)
        $krfconn->query("UPDATE krf_ready_stock_reserved_qty 
                         SET tedit = '{$teditDateEsc}', 
                             teditremark = '{$teditRemarkEsc}', 
                             updated_at = NOW() 
                         WHERE projectid = {$projectid} 
                           AND stockorder_no = '{$soEscActual}' 
                           AND (tedit IS NULL OR tedit = '')");

        // 6. Update status, confirmstatus, tedit & teditremark in krf_productionorder_consumed
        $krfconn->query("UPDATE krf_productionorder_consumed 
                         SET status = 'Cancelled', 
                             confirmstatus = 'cancelled', 
                             tedit = '{$teditDateEsc}', 
                             teditremark = '{$teditRemarkEsc}', 
                             remark = CONCAT(IFNULL(remark,''), ' | Cancelled on Bed Removal" . (!empty($removal_remark) ? " ({$userRemarkEsc})" : "") . "'), 
                             updated_at = NOW() 
                         WHERE projectid = {$projectid} 
                           AND (stockorderno = '{$soEscActual}' OR allocated_stockorderno = '{$soEscActual}' OR psono = '{$psoEscActual}') 
                           AND (tedit IS NULL OR tedit = '')");

        // 7. Update status, tedit & teditremark in krf_provisional_sales_order_items (if sales order)
        $krfconn->query("UPDATE krf_provisional_sales_order_items 
                         SET status = 'cancelled', 
                             tedit = '{$teditDateEsc}', 
                             teditremark = '{$teditRemarkEsc}', 
                             remark = CONCAT(IFNULL(remark,''), ' | Cancelled on Bed Removal" . (!empty($removal_remark) ? " ({$userRemarkEsc})" : "") . "'), 
                             updatedat = '{$curDateOnly}', 
                             updatedby = '{$updated_by}' 
                         WHERE projectid = {$projectid} 
                           AND (stockorder_no = '{$soEscActual}' OR psono = '{$psoEscActual}') 
                           AND (tedit IS NULL OR tedit = '')");

        // 8. Update status, tedit & teditremark in krf_sales_order_items (if converted sales order)
        $krfconn->query("UPDATE krf_sales_order_items 
                         SET status = 'Cancelled', 
                             tedit = '{$teditDateEsc}', 
                             teditremark = '{$teditRemarkEsc}', 
                             remark = CONCAT(IFNULL(remark,''), ' | Cancelled on Bed Removal" . (!empty($removal_remark) ? " ({$userRemarkEsc})" : "") . "') 
                         WHERE projectid = {$projectid} 
                           AND (stockorder_no = '{$soEscActual}' OR psono = '{$psoEscActual}') 
                           AND (tedit IS NULL OR tedit = '')");

        $krfconn->commit();
        outJson([
            'status' => 'success',
            'message' => "Order {$soNo} ({$removedCount} trays, " . number_format($totalPlantsRemoved) . " plants) marked as Removed from {$bed_no} and Production Stock Order updated successfully!",
            'removed_trays' => $removedCount,
            'removed_plants' => $totalPlantsRemoved,
            'stockorder_no' => $soNo,
            'bed_no' => $bed_no
        ]);

    } catch (Throwable $e) {
        $krfconn->rollback();
        outJson(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

if ($action === 'gettraystatus') {
    $projectid     = (int)($_POST['projectid'] ?? 0);
    $locationid    = trim($_POST['locationid'] ?? '');
    $polyhouseid   = (int)($_POST['polyhouseid'] ?? 0);
    $bed_no        = trim($_POST['bed_no'] ?? '');
    $stockorder_no = trim($_POST['stockorder_no'] ?? '');
    $psono         = trim($_POST['psono'] ?? '');
    $productid     = (int)($_POST['productid'] ?? 0);

    if ($projectid <= 0) $projectid = 17;

    $cleanBed = preg_replace('/^Bed[-\s]*/i', '', $bed_no);
    $bedEsc = $krfconn->real_escape_string($bed_no);
    $cleanBedEsc = $krfconn->real_escape_string($cleanBed);
    $bedPrefEsc = $krfconn->real_escape_string('Bed-' . $cleanBed);

    $cleanSo = preg_replace('/^(RSO|OS|PSO)-/i', '', $stockorder_no);
    $soEsc = $krfconn->real_escape_string($stockorder_no);
    $cleanSoEsc = $krfconn->real_escape_string($cleanSo);
    $psoEsc = $krfconn->real_escape_string($psono);

    $where = [
        "projectid = '{$projectid}'",
        "(bed_no = '{$bedEsc}' OR bed_no = '{$cleanBedEsc}' OR bed_no = '{$bedPrefEsc}')",
        "(stockorder_no = '{$soEsc}' OR stockorder_no = '{$cleanSoEsc}' OR stockorder_no = 'RSO-{$cleanSoEsc}' OR stockorder_no = 'OS-{$cleanSoEsc}' OR psono = '{$soEsc}' OR psono = '{$cleanSoEsc}' OR psono = '{$psoEsc}' OR lotno = '{$cleanSoEsc}' OR batchno = '{$cleanSoEsc}' OR bedqrcode = '{$soEsc}')",
        "(tedit IS NULL OR tedit = '')",
        "status != 'Removed'",
        "workingstatus != 'Removed'",
        "(status IN ('Polyhouse', 'Readytosale', 'Ready to sale', 'ReadyToSale', 'OpenStock', 'Open Stock') OR workingstatus = 'Polyhouse')",
        "status NOT IN ('Internal Dispatch', 'Final Dispatch', 'Grafting', 'Move for Grafting', 'Healing', 'Hardening', 'Clip Stick Removal', 'Removed', 'Grafted', 'Empty After Shifting', 'Empty After Grafting', 'Destroy After grafted', 'Tray Free After Shifting')"
    ];
    if ($polyhouseid > 0) {
        $where[] = "CAST(polyhouseid AS UNSIGNED) = {$polyhouseid}";
    }
    if ($productid > 0) {
        $where[] = "productid = '{$productid}'";
    }

    $whereClause = implode(" AND ", $where);
    $sql = "SELECT 
                COUNT(*) as total_trays,
                SUM(CASE WHEN health_status IN ('Unhealthy','Weak','Diseased','Dead','Infested') THEN 1 ELSE 0 END) as unhealthy_trays,
                SUM(CASE WHEN health_status = 'Healthy' OR health_status IS NULL OR health_status = '' THEN 1 ELSE 0 END) as healthy_trays,
                MAX(shiftingstatus) as shiftingstatus,
                MAX(failerpercent) as failerpercent,
                MAX(qtypertray) as qtypertray
            FROM krf_polyhouse_plant_details 
            WHERE {$whereClause}";

    $res = $krfconn->query($sql);
    $data = $res ? $res->fetch_assoc() : null;
    outJson([
        'status' => 'success',
        'data' => [
            'total_trays' => (int)($data['total_trays'] ?? 0),
            'healthy_trays' => (int)($data['healthy_trays'] ?? 0),
            'unhealthy_trays' => (int)($data['unhealthy_trays'] ?? 0),
            'shiftingstatus' => $data['shiftingstatus'] ?: 'nonshifted',
            'failerpercent' => (float)($data['failerpercent'] ?? 0),
            'qtypertray' => (int)($data['qtypertray'] ?? 209)
        ]
    ]);
}

if ($action === 'updatetraystatus') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $projectid     = (int)($_POST['projectid'] ?? 0);
    $locationid    = trim($_POST['locationid'] ?? '');
    $polyhouseid   = (int)($_POST['polyhouseid'] ?? 0);
    $bed_no        = trim($_POST['bed_no'] ?? '');
    $stockorder_no = trim($_POST['stockorder_no'] ?? '');
    $psono         = trim($_POST['psono'] ?? '');
    $productid     = (int)($_POST['productid'] ?? 0);
    $tray_id       = (int)($_POST['tray_id'] ?? 0);

    $shiftingstatus = trim($_POST['shiftingstatus'] ?? 'nonshifted');
    if (!in_array($shiftingstatus, ['shifted', 'nonshifted'], true)) {
        $shiftingstatus = 'nonshifted';
    }

    $failerpercent = isset($_POST['failerpercent']) ? (float)$_POST['failerpercent'] : 0.0;
    if ($shiftingstatus === 'shifted') {
        $failerpercent = 0.0;
    }

    $healthy_trays   = max(0, (int)($_POST['healthy_trays'] ?? 0));
    $unhealthy_trays = max(0, (int)($_POST['unhealthy_trays'] ?? 0));

    $updated_by = trim($_POST['loginname'] ?? $_SESSION['loginname'] ?? $_POST['loginid'] ?? $_SESSION['loginid'] ?? 'System');

    if ($projectid <= 0) $projectid = 17;
    if (empty($bed_no) || empty($stockorder_no)) {
        outJson(['status' => 'error', 'message' => 'Missing bed or stock order details.']);
    }

    // Auto-heal schema if columns are not present
    try {
        @$krfconn->query("ALTER TABLE krf_polyhouse_plant_details ADD COLUMN IF NOT EXISTS shiftingstatus ENUM('nonshifted', 'shifted') NULL DEFAULT 'nonshifted' AFTER workingstatus");
        @$krfconn->query("ALTER TABLE krf_polyhouse_plant_details ADD COLUMN IF NOT EXISTS shiftingby VARCHAR(45) NULL AFTER shiftingstatus");
        @$krfconn->query("ALTER TABLE krf_polyhouse_plant_details ADD COLUMN IF NOT EXISTS shiftingat DATE NULL AFTER shiftingby");
        @$krfconn->query("ALTER TABLE krf_polyhouse_plant_details ADD COLUMN IF NOT EXISTS failerpercent FLOAT NULL AFTER failurecount");
    } catch (Throwable $ignore) {}

    $krfconn->begin_transaction();
    try {
        $cleanBed = preg_replace('/^Bed[-\s]*/i', '', $bed_no);
        $bedEsc = $krfconn->real_escape_string($bed_no);
        $cleanBedEsc = $krfconn->real_escape_string($cleanBed);
        $bedPrefEsc = $krfconn->real_escape_string('Bed-' . $cleanBed);

        $cleanSo = preg_replace('/^(RSO|OS|PSO)-/i', '', $stockorder_no);
        $soEsc = $krfconn->real_escape_string($stockorder_no);
        $cleanSoEsc = $krfconn->real_escape_string($cleanSo);
        $psoEsc = $krfconn->real_escape_string($psono);

        $where = [
            "projectid = '{$projectid}'",
            "(bed_no = '{$bedEsc}' OR bed_no = '{$cleanBedEsc}' OR bed_no = '{$bedPrefEsc}')",
            "(stockorder_no = '{$soEsc}' OR stockorder_no = '{$cleanSoEsc}' OR stockorder_no = 'RSO-{$cleanSoEsc}' OR stockorder_no = 'OS-{$cleanSoEsc}' OR psono = '{$soEsc}' OR psono = '{$cleanSoEsc}' OR psono = '{$psoEsc}' OR lotno = '{$cleanSoEsc}' OR batchno = '{$cleanSoEsc}' OR bedqrcode = '{$soEsc}')",
            "(tedit IS NULL OR tedit = '')",
            "status != 'Removed'",
            "workingstatus != 'Removed'",
            "(status IN ('Polyhouse', 'Readytosale', 'Ready to sale', 'ReadyToSale', 'OpenStock', 'Open Stock') OR workingstatus = 'Polyhouse')",
            "status NOT IN ('Internal Dispatch', 'Final Dispatch', 'Grafting', 'Move for Grafting', 'Healing', 'Hardening', 'Clip Stick Removal', 'Removed', 'Grafted', 'Empty After Shifting', 'Empty After Grafting', 'Destroy After grafted', 'Tray Free After Shifting')"
        ];
        if ($polyhouseid > 0) {
            $where[] = "CAST(polyhouseid AS UNSIGNED) = {$polyhouseid}";
        }
        if ($productid > 0) {
            $where[] = "productid = '{$productid}'";
        }

        $whereClause = implode(" AND ", $where);
        $sql = "SELECT polyhouseplantid, qtypertray, health_status, failurecount FROM krf_polyhouse_plant_details WHERE {$whereClause} ORDER BY polyhouseplantid ASC";
        $res = $krfconn->query($sql);
        if (!$res) throw new Exception("Query failed: " . $krfconn->error);

        $records = [];
        while ($r = $res->fetch_assoc()) {
            $records[] = $r;
        }

        $totalFound = count($records);
        if ($totalFound === 0) {
            throw new Exception("No active tray records found for {$stockorder_no} in {$bed_no}.");
        }

        $qtyPerTray = (int)($records[0]['qtypertray'] ?: 209);
        if ($qtyPerTray <= 0) $qtyPerTray = 209;

        // Calculate failed plants per unhealthy tray
        $failedPerTray = 0;
        if ($failerpercent > 0) {
            $failedPerTray = (int)round(($qtyPerTray * $failerpercent) / 100);
            if ($failedPerTray <= 0) $failedPerTray = 1;
            if ($failedPerTray > $qtyPerTray) $failedPerTray = $qtyPerTray;
        }

        // Bound unhealthy trays to totalFound
        if ($unhealthy_trays > $totalFound) {
            $unhealthy_trays = $totalFound;
            $healthy_trays = 0;
        } else {
            $healthy_trays = $totalFound - $unhealthy_trays;
        }

        $unhealthyIds = [];
        $healthyIds = [];

        for ($i = 0; $i < $totalFound; $i++) {
            $pId = (int)$records[$i]['polyhouseplantid'];
            if ($i < $unhealthy_trays) {
                $unhealthyIds[] = $pId;
            } else {
                $healthyIds[] = $pId;
            }
        }

        $curDate = date('Y-m-d');
        $shiftByEsc = $krfconn->real_escape_string($updated_by);
        $failByEsc = $krfconn->real_escape_string($updated_by);

        // Update Unhealthy trays (if any) - only update shifting & health columns, never touch status column
        if (!empty($unhealthyIds)) {
            $uIdList = implode(',', $unhealthyIds);
            $uSql = "UPDATE krf_polyhouse_plant_details SET
                        shiftingstatus = '{$shiftingstatus}',
                        shiftingby = '{$shiftByEsc}',
                        shiftingat = '{$curDate}',
                        health_status = 'Germination Failure',
                        failerpercent = {$failerpercent},
                        failurecount = {$failedPerTray},
                        failmarked_by = '{$failByEsc}',
                        failmarked_at = '{$curDate}',
                        updated_at = NOW(),
                        updated_by = '{$shiftByEsc}'
                     WHERE polyhouseplantid IN ({$uIdList})";
            if (!$krfconn->query($uSql)) {
                throw new Exception("Error updating unhealthy trays: " . $krfconn->error);
            }
        }

        // Update Healthy trays (if any) - only update shifting & health columns, never touch status column
        if (!empty($healthyIds)) {
            $hIdList = implode(',', $healthyIds);
            $hSql = "UPDATE krf_polyhouse_plant_details SET
                        shiftingstatus = '{$shiftingstatus}',
                        shiftingby = '{$shiftByEsc}',
                        shiftingat = '{$curDate}',
                        health_status = 'Healthy',
                        failerpercent = {$failerpercent},
                        failurecount = 0,
                        updated_at = NOW(),
                        updated_by = '{$shiftByEsc}'
                     WHERE polyhouseplantid IN ({$hIdList})";
            if (!$krfconn->query($hSql)) {
                throw new Exception("Error updating healthy trays: " . $krfconn->error);
            }
        }

        $krfconn->commit();

        $shiftingText = ($shiftingstatus === 'shifted') ? 'Shifted' : "Non-Shifted (Fail: {$failerpercent}%)";
        outJson([
            'status' => 'success',
            'message' => "Bed {$bed_no} - {$stockorder_no} status updated: {$shiftingText} • {$healthy_trays} Healthy Trays, {$unhealthy_trays} Unhealthy Trays.",
            'total_trays' => $totalFound,
            'healthy_trays' => $healthy_trays,
            'unhealthy_trays' => $unhealthy_trays,
            'shiftingstatus' => $shiftingstatus,
            'failerpercent' => $failerpercent
        ]);

    } catch (Throwable $e) {
        $krfconn->rollback();
        outJson(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

if ($action === 'getstatusbreakdowndetails') {
    $projectid     = (int)($_POST['projectid'] ?? 0);
    $locationid    = trim($_POST['locationid'] ?? '');
    $polyhouseid   = (int)($_POST['polyhouseid'] ?? 0);
    $bed_no        = trim($_POST['bed_no'] ?? '');
    $stockorder_no = trim($_POST['stockorder_no'] ?? '');
    $psono         = trim($_POST['psono'] ?? '');
    $productid     = (int)($_POST['productid'] ?? 0);
    $status_key    = trim($_POST['status_key'] ?? '');

    if ($projectid <= 0) $projectid = 17;

    $cleanBed = preg_replace('/^Bed[-\s]*/i', '', $bed_no);
    $bedEsc = $krfconn->real_escape_string($bed_no);
    $cleanBedEsc = $krfconn->real_escape_string($cleanBed);
    $bedPrefEsc = $krfconn->real_escape_string('Bed-' . $cleanBed);

    $cleanSo = preg_replace('/^(RSO|OS|PSO)-/i', '', $stockorder_no);
    $soEsc = $krfconn->real_escape_string($stockorder_no);
    $cleanSoEsc = $krfconn->real_escape_string($cleanSo);
    $psoEsc = $krfconn->real_escape_string($psono);

    $where = [
        "projectid = '{$projectid}'",
        "(bed_no = '{$bedEsc}' OR bed_no = '{$cleanBedEsc}' OR bed_no = '{$bedPrefEsc}')",
        "(stockorder_no = '{$soEsc}' OR stockorder_no = '{$cleanSoEsc}' OR stockorder_no = 'RSO-{$cleanSoEsc}' OR stockorder_no = 'OS-{$cleanSoEsc}' OR psono = '{$soEsc}' OR psono = '{$cleanSoEsc}' OR psono = '{$psoEsc}' OR lotno = '{$cleanSoEsc}' OR batchno = '{$cleanSoEsc}' OR bedqrcode = '{$soEsc}')",
        "(tedit IS NULL OR tedit = '')",
        "status != 'Removed'",
        "workingstatus != 'Removed'"
    ];

    if ($polyhouseid > 0) {
        $where[] = "CAST(polyhouseid AS UNSIGNED) = {$polyhouseid}";
    }
    if ($productid > 0) {
        $where[] = "productid = '{$productid}'";
    }

    $statusKeyClean = trim($status_key);
    if (strcasecmp($statusKeyClean, 'Ready to Sale') === 0 || strcasecmp($statusKeyClean, 'ReadytoSale') === 0) {
        $where[] = "(status IN ('Readytosale', 'Ready to sale', 'ReadyToSale') OR (growth_stage = 'ReadyToSale' AND status IN ('Polyhouse', 'OpenStock', 'Open Stock')))";
        $where[] = "status NOT IN ('Internal Dispatch', 'Final Dispatch', 'Grafting', 'Move for Grafting', 'Healing', 'Hardening', 'Clip Stick Removal', 'Removed', 'Grafted', 'Empty After Shifting', 'Empty After Grafting')";
        $where[] = "(health_status = 'Healthy' OR health_status IS NULL OR health_status = '')";
    } elseif (strcasecmp($statusKeyClean, 'Polyhouse') === 0) {
        $where[] = "status = 'Polyhouse'";
    } elseif (strcasecmp($statusKeyClean, 'Open Stock') === 0 || strcasecmp($statusKeyClean, 'OpenStock') === 0) {
        $where[] = "status IN ('OpenStock', 'Open Stock')";
    } elseif (strcasecmp($statusKeyClean, 'Loaded') === 0) {
        $where[] = "(status IN ('Loaded', 'Loading') OR workingstatus IN ('Loaded', 'Loading'))";
    } else {
        $stEsc = $krfconn->real_escape_string($statusKeyClean);
        $where[] = "(status = '{$stEsc}' OR workingstatus = '{$stEsc}')";
    }

    $whereClause = implode(" AND ", $where);
    $sql = "SELECT 
                polyhouseplantid,
                barcode,
                bedqrcode,
                lotno,
                batchno,
                status,
                workingstatus,
                health_status,
                shiftingstatus,
                shiftingby,
                shiftingat,
                failerpercent,
                failurecount,
                qtypertray,
                move_at,
                movetolocation,
                despatchlocation,
                dispatchat,
                dispatchby,
                movementremark,
                remarks,
                bed_verify,
                bed_verifyby,
                bed_verifyat,
                updated_at,
                updated_by
            FROM krf_polyhouse_plant_details
            WHERE {$whereClause}
            ORDER BY polyhouseplantid ASC";

    $res = $krfconn->query($sql);
    if (!$res) {
        outJson(['status' => 'error', 'message' => 'Query error: ' . $krfconn->error]);
    }

    $trays = [];
    $totalPlants = 0;
    while ($row = $res->fetch_assoc()) {
        $qty = (int)($row['qtypertray'] ?? 209);
        $totalPlants += $qty;

        $rawDispatchBy = trim($row['dispatchby'] ?? '');
        $rawMoveRemark = trim($row['movementremark'] ?? '');
        $rawRemarks    = trim($row['remarks'] ?? '');
        $rawUpdatedBy  = trim($row['updated_by'] ?? '');
        $rawShiftingBy = trim($row['shiftingby'] ?? '');

        // 1. Resolve Dispatch / Moved By
        $resolvedDispatchBy = $rawDispatchBy;
        if (empty($resolvedDispatchBy) && !empty($rawMoveRemark)) {
            if (preg_match('/by\s+([^|]+?)\s+on\s+/i', $rawMoveRemark, $m)) {
                $resolvedDispatchBy = trim($m[1]);
            }
        }
        if (empty($resolvedDispatchBy)) {
            $resolvedDispatchBy = $rawUpdatedBy ?: ($rawShiftingBy ?: '-');
        }

        // 2. Resolve Batch Trays moved
        $resolvedBatchTrays = '';
        if (!empty($rawMoveRemark)) {
            if (preg_match('/\|\s*(\d+\s*tray\(s\)?)/i', $rawMoveRemark, $m)) {
                $resolvedBatchTrays = trim($m[1]);
            }
        }

        // 3. Resolve Exact Movement DateTime
        $resolvedMovedDateTime = '';
        if (!empty($rawMoveRemark)) {
            if (preg_match('/on\s+([0-9]{1,2}-[A-Za-z]{3}-[0-9]{4}\s+[0-9]{1,2}:[0-9]{2}\s*(?:AM|PM)?)/i', $rawMoveRemark, $m)) {
                $resolvedMovedDateTime = trim($m[1]);
            }
        }
        if (empty($resolvedMovedDateTime)) {
            $resolvedMovedDateTime = $row['move_at'] ?: ($row['dispatchat'] ?: ($row['shiftingat'] ?: ($row['updated_at'] ?: '')));
        }

        // 4. Resolve Remark
        $resolvedRemark = '';
        if (!empty($rawMoveRemark)) {
            if (preg_match('/Note:\s*([^\]]+)/i', $rawMoveRemark, $m)) {
                $resolvedRemark = trim($m[1]);
            } else {
                $resolvedRemark = $rawMoveRemark;
            }
        }
        if (empty($resolvedRemark) && !empty($rawRemarks)) {
            $resolvedRemark = $rawRemarks;
        }
        if (empty($resolvedRemark)) {
            $resolvedRemark = '-';
        }

        $trays[] = [
            'id' => (int)$row['polyhouseplantid'],
            'barcode' => $row['barcode'] ?: ($row['bedqrcode'] ?: 'N/A'),
            'status' => $row['status'] ?: 'N/A',
            'workingstatus' => $row['workingstatus'] ?: '',
            'health_status' => $row['health_status'] ?: 'Healthy',
            'shiftingstatus' => $row['shiftingstatus'] ?: 'nonshifted',
            'qtypertray' => $qty,
            'move_at' => $row['move_at'] ?: '',
            'movetolocation' => $row['movetolocation'] ?: '',
            'despatchlocation' => $row['despatchlocation'] ?: '',
            'dispatchat' => $row['dispatchat'] ?: '',
            'dispatchby' => $row['dispatchby'] ?: '',
            'dispatch_by' => $resolvedDispatchBy,
            'batch_trays' => $resolvedBatchTrays,
            'moved_datetime' => $resolvedMovedDateTime,
            'remark' => $resolvedRemark,
            'full_movementremark' => $rawMoveRemark ?: $rawRemarks,
            'shiftingat' => $row['shiftingat'] ?: '',
            'shiftingby' => $row['shiftingby'] ?: '',
            'failerpercent' => (float)($row['failerpercent'] ?? 0),
            'bed_verify' => $row['bed_verify'] ?: '',
            'bed_verifyat' => $row['bed_verifyat'] ?: '',
            'updated_at' => $row['updated_at'] ?: ''
        ];
    }

    outJson([
        'status' => 'success',
        'status_key' => $status_key,
        'total_trays' => count($trays),
        'total_plants' => $totalPlants,
        'trays' => $trays
    ]);
}

outJson(['status'=>'error','message'=>'Invalid action.']);

