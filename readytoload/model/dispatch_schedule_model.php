<?php
/**
 * Dispatch Schedule by Client & Date - Backend Model & AJAX Controller
 * Table Source: `karjeefarm`.`krf_sales_order_items`
 * Path: /readytoload/model/dispatch_schedule_model.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(0);
ini_set('display_errors', '0');
date_default_timezone_set('Asia/Kolkata');

require_once(__DIR__ . '/../../connection.php');
header('Content-Type: application/json; charset=utf-8');

$loginid      = $_SESSION['loginid'] ?? $_SESSION['userid'] ?? 'admin';
$loginname    = $_SESSION['loginname'] ?? $_SESSION['username'] ?? 'Admin';
$loginroleid  = (int)($_SESSION['loginroleid'] ?? 4);
$companyid    = (int)($_SESSION['companyid'] ?? 1);
$subcompanyid = (int)($_SESSION['subcompanyid'] ?? 1);
$projectid    = (int)($_SESSION['projectid'] ?? 17);
if ($projectid <= 0) $projectid = 17;

$action = trim($_REQUEST['action'] ?? '');

function outJson($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Map polyhouse id to name
function getPolyhouseNameMap($krfconn) {
    static $polyMap = null;
    if ($polyMap !== null) return $polyMap;
    $polyMap = [];
    $res = $krfconn->query("SELECT polyhouseid, polyhousename, physicallocation FROM krf_polyhouse_master");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $name = trim($r['polyhousename'] ?: ('PH-' . $r['polyhouseid']));
            if (!empty($r['physicallocation'])) {
                $name .= ' (' . trim($r['physicallocation']) . ')';
            }
            $polyMap[$r['polyhouseid']] = $name;
            $polyMap[strval($r['polyhouseid'])] = $name;
        }
    }
    return $polyMap;
}

// Categorize item into one of the 3 specified status buckets
function getScheduleItemBucket($status, $actionstatus) {
    $st = strtolower(trim($status ?? ''));
    $act = strtolower(trim($actionstatus ?? ''));

    // 1. Dispatched Order
    if (in_array($st, ['dispatched', 'fulldispatch', 'final dispatch', 'finaldispatch']) ||
        in_array($act, ['dispatched', 'fulldispatch', 'final dispatch', 'finaldispatch'])) {
        return 'dispatched';
    }

    // 2. Ready to Dispatch Order (readytodispatch, loaded, partially loaded)
    if ($st === 'readytodispatch' || $st === 'ready to dispatch' || $act === 'readytodispatch' || $act === 'ready to dispatch' ||
        $st === 'loaded' || $act === 'loaded' || 
        $st === 'partially loaded' || $act === 'partially loaded' || $act === 'partial') {
        return 'readytodispatch';
    }

    // 3. Ready to Load Order (allocated, readytoload)
    return 'readytoload';
}

// -------------------------------------------------------------
// 0. ACTION: GET LOCATIONS & POLYHOUSES LIST
// -------------------------------------------------------------
if ($action === 'get_locations_and_polyhouses') {
    // 1. Fetch locations from hrmconn
    $locMap = [];
    if (isset($hrmconn) && $hrmconn) {
        $resLoc = $hrmconn->query("SELECT LocationId, LocationName FROM hrm_AttendanceLocationMaster ORDER BY LocationName ASC");
        if ($resLoc) {
            while ($r = $resLoc->fetch_assoc()) {
                $locMap[trim($r['LocationId'])] = trim($r['LocationName']);
            }
        }
    }

    // 2. Fetch active polyhouses from krfconn
    $resPh = $krfconn->query("SELECT polyhouseid, polyhousename, locationid FROM krf_polyhouse_master WHERE status = 'Active' ORDER BY polyhouseid ASC");
    $locations = [];
    while ($r = $resPh->fetch_assoc()) {
        $locId = trim($r['locationid'] ?: 'UNKNOWN');
        $locName = $locMap[$locId] ?? ($locId !== 'UNKNOWN' ? $locId : 'General Location');
        if (!isset($locations[$locId])) {
            $locations[$locId] = [
                'location_id'   => $locId,
                'location_name' => $locName,
                'polyhouses'    => []
            ];
        }
        $locations[$locId]['polyhouses'][] = [
            'polyhouse_id'   => (int)$r['polyhouseid'],
            'polyhouse_name' => trim($r['polyhousename'])
        ];
    }

    outJson([
        'status'    => 'success',
        'locations' => array_values($locations)
    ]);
}

// -------------------------------------------------------------
// 1. ACTION: GET DISPATCH SCHEDULE (Grouped by Date & Client)
// -------------------------------------------------------------
if ($action === 'get_dispatch_schedule') {
    $searchQuery   = trim($_REQUEST['search'] ?? '');
    $fromDate      = trim($_REQUEST['from_date'] ?? '');
    $toDate        = trim($_REQUEST['to_date'] ?? '');
    $scope         = trim($_REQUEST['scope'] ?? 'current_week'); // Default: current_week
    $statusFilter  = trim($_REQUEST['status'] ?? 'readytoload'); // Default: readytoload
    if ($statusFilter === '') $statusFilter = 'readytoload';

    $locationId    = trim($_REQUEST['location_id'] ?? '');
    $polyhouseId   = trim($_REQUEST['polyhouse_id'] ?? '');

    // Check if location or polyhouse is selected
    if (empty($locationId) && empty($polyhouseId)) {
        outJson([
            'status'  => 'prompt_selection',
            'message' => 'Please select a Location and Polyhouse from the filter bar above.',
            'summary' => [
                'readytoload'     => ['total_orders' => 0, 'total_items' => 0, 'total_plants' => 0, 'total_trays' => 0, 'total_clients' => 0, 'total_groups' => 0],
                'readytodispatch' => ['total_orders' => 0, 'total_items' => 0, 'total_plants' => 0, 'total_trays' => 0, 'total_clients' => 0, 'total_groups' => 0],
                'dispatched'      => ['total_orders' => 0, 'total_items' => 0, 'total_plants' => 0, 'total_trays' => 0, 'total_clients' => 0, 'total_groups' => 0],
                'all'             => ['total_orders' => 0, 'total_items' => 0, 'total_plants' => 0, 'total_trays' => 0, 'total_clients' => 0, 'total_groups' => 0]
            ],
            'data'    => [],
            'metrics' => [
                'active_status' => $statusFilter,
                'scope'         => $scope,
                'current_date'  => date('Y-m-d')
            ]
        ]);
    }

    $polyMap = getPolyhouseNameMap($krfconn);

    // Calculate current week date boundaries (Monday to Sunday)
    $nowObj = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
    $dayOfWeekN = (int)$nowObj->format('N'); // 1 (Mon) to 7 (Sun)
    $mondayThisWeek = (clone $nowObj)->modify('-' . ($dayOfWeekN - 1) . ' days')->format('Y-m-d');
    $sundayThisWeek = (clone $nowObj)->modify('+' . (7 - $dayOfWeekN) . ' days')->format('Y-m-d');
    $weekLabel = date('d M', strtotime($mondayThisWeek)) . ' - ' . date('d M Y', strtotime($sundayThisWeek));

    $whereClauses = [];
    $whereClauses[] = "soi.deliverydate IS NOT NULL AND soi.deliverydate != '0000-00-00'";
    // Only show items where trays are allocated
    $whereClauses[] = "(soi.allocate_ordqty IS NOT NULL AND soi.allocate_ordqty > 0)";
    // Exclude cancelled items from dispatch schedule
    $whereClauses[] = "LOWER(TRIM(COALESCE(soi.status, ''))) != 'cancelled'";

    // Scope Date Filter
    if (!empty($fromDate) || !empty($toDate)) {
        if (!empty($fromDate)) {
            $safeFrom = mysqli_real_escape_string($krfconn, $fromDate);
            $whereClauses[] = "soi.deliverydate >= '$safeFrom'";
        }
        if (!empty($toDate)) {
            $safeTo = mysqli_real_escape_string($krfconn, $toDate);
            $whereClauses[] = "soi.deliverydate <= '$safeTo'";
        }
    } else {
        if ($scope === 'current_week') {
            // Default current week (Monday to Sunday)
            $whereClauses[] = "soi.deliverydate BETWEEN '$mondayThisWeek' AND '$sundayThisWeek'";
        } elseif ($scope === 'present_to_past') {
            // Present date to past (CURDATE and earlier)
            $whereClauses[] = "soi.deliverydate <= CURDATE()";
        } elseif ($scope === 'today') {
            $whereClauses[] = "soi.deliverydate = CURDATE()";
        } elseif ($scope === 'upcoming') {
            $whereClauses[] = "soi.deliverydate > CURDATE()";
        }
        // If 'all', no date bounds
    }

    // Location & Polyhouse Filter
    $allowedPhIds = [];
    if (!empty($polyhouseId) && $polyhouseId !== 'all') {
        $allowedPhIds = [(int)$polyhouseId];
    } elseif (!empty($locationId) && $locationId !== 'all') {
        $safeLoc = mysqli_real_escape_string($krfconn, $locationId);
        $resPhs = $krfconn->query("SELECT polyhouseid FROM krf_polyhouse_master WHERE locationid = '$safeLoc'");
        if ($resPhs) {
            while ($r = $resPhs->fetch_assoc()) {
                $allowedPhIds[] = (int)$r['polyhouseid'];
            }
        }
    }

    if (!empty($allowedPhIds)) {
        $escPhList = implode(',', $allowedPhIds);
        $phWhereParts = [];
        $phWhereParts[] = "soi.psono IN (SELECT DISTINCT psono FROM krf_polyhouse_plant_details WHERE polyhouseid IN ($escPhList) AND psono IS NOT NULL AND psono != '')";
        $phWhereParts[] = "soi.stockorder_no IN (SELECT DISTINCT stockorder_no FROM krf_polyhouse_plant_details WHERE polyhouseid IN ($escPhList) AND stockorder_no IS NOT NULL AND stockorder_no != '')";

        $phNamesEsc = [];
        foreach ($allowedPhIds as $phId) {
            if (isset($polyMap[$phId])) {
                $pNameClean = mysqli_real_escape_string($krfconn, $polyMap[$phId]);
                $phNamesEsc[] = "'$pNameClean'";
                $phWhereParts[] = "soi.psono IN (SELECT DISTINCT psono FROM krf_readystock_allocation WHERE allocatedbed LIKE '%\"polyhouse_id\":$phId%' OR allocatedbed LIKE '%\"polyhouse_id\":\"$phId\"%' OR allocatedbed LIKE '%\"polyhouse_name\":\"$pNameClean\"%')";
            }
        }
        if (!empty($phNamesEsc)) {
            $phWhereParts[] = "soi.psono IN (SELECT DISTINCT psono FROM krf_readystock_allocation WHERE polyhouse IN (" . implode(',', $phNamesEsc) . "))";
        }

        $whereClauses[] = "(" . implode(" OR ", $phWhereParts) . ")";
    }

    // Search query: clientname, clientid, phone, psono, sono, product_name, district, stockorder_no
    if (!empty($searchQuery)) {
        $safeSearch = mysqli_real_escape_string($krfconn, $searchQuery);
        $whereClauses[] = "(
            soi.psono LIKE '%$safeSearch%' 
            OR soi.sono LIKE '%$safeSearch%' 
            OR soi.product_name LIKE '%$safeSearch%' 
            OR soi.stockorder_no LIKE '%$safeSearch%' 
            OR soi.clientid LIKE '%$safeSearch%'
            OR pso.clientname LIKE '%$safeSearch%' 
            OR cm.client_name LIKE '%$safeSearch%'
            OR pso.mobileno LIKE '%$safeSearch%'
            OR cm.phones LIKE '%$safeSearch%'
            OR pso.districtname LIKE '%$safeSearch%'
            OR pso.statename LIKE '%$safeSearch%'
        )";
    }

    $whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

    $sql = "
        SELECT 
            soi.id as item_id,
            soi.sono,
            soi.psono,
            soi.stockorder_no,
            soi.projectid,
            soi.clientid,
            soi.product_sku,
            soi.product_id,
            soi.product_name,
            soi.quantity,
            soi.rate,
            soi.taxableamount,
            soi.amount,
            soi.deliverydate,
            soi.status,
            soi.actionstatus,
            soi.reservedqty,
            soi.balancereservedqty,
            soi.allocate_ordqty,
            soi.shiftingqty,
            soi.isshifted,
            soi.healthyplant,
            soi.unhealthyplant,
            COALESCE(NULLIF(TRIM(pso.clientname), ''), NULLIF(TRIM(cm.client_name), ''), soi.clientid) AS display_client_name,
            COALESCE(NULLIF(TRIM(pso.mobileno), ''), NULLIF(TRIM(cm.phones), ''), '') AS client_phone,
            COALESCE(NULLIF(TRIM(pso.delivery_address), ''), NULLIF(TRIM(pso.address), ''), NULLIF(TRIM(cm.invoicing_address), ''), '') AS delivery_address,
            pso.districtname,
            pso.statename,
            pso.expecteddelivery
        FROM krf_sales_order_items soi
        LEFT JOIN krf_provisional_sales_order pso ON soi.psono = pso.psono
        LEFT JOIN krf_client_master cm ON soi.clientid = cm.client_id
        $whereSql
        ORDER BY soi.deliverydate DESC, display_client_name ASC, soi.psono ASC, soi.id ASC
    ";

    $res = $krfconn->query($sql);
    if (!$res) {
        outJson(['status' => 'error', 'message' => 'Database Query Error: ' . $krfconn->error]);
    }

    $todayDate = date('Y-m-d');

    // Structure for summary counters across all matching records for the active date & search scope
    $summary = [
        'readytoload' => [
            'total_orders'  => 0,
            'total_items'   => 0,
            'total_plants'  => 0,
            'total_trays'   => 0,
            'total_clients' => 0,
            'total_groups'  => 0,
            '_orders_map'   => [],
            '_clients_map'  => [],
            '_groups_map'   => []
        ],
        'readytodispatch' => [
            'total_orders'  => 0,
            'total_items'   => 0,
            'total_plants'  => 0,
            'total_trays'   => 0,
            'total_clients' => 0,
            'total_groups'  => 0,
            '_orders_map'   => [],
            '_clients_map'  => [],
            '_groups_map'   => []
        ],
        'dispatched' => [
            'total_orders'  => 0,
            'total_items'   => 0,
            'total_plants'  => 0,
            'total_trays'   => 0,
            'total_clients' => 0,
            'total_groups'  => 0,
            '_orders_map'   => [],
            '_clients_map'  => [],
            '_groups_map'   => []
        ],
        'all' => [
            'total_orders'  => 0,
            'total_items'   => 0,
            'total_plants'  => 0,
            'total_trays'   => 0,
            'total_clients' => 0,
            'total_groups'  => 0,
            '_orders_map'   => [],
            '_clients_map'  => [],
            '_groups_map'   => []
        ]
    ];

    $grouped = [];
    $rawRows = [];

    // First pass: Read all rows and calculate summary metrics
    while ($row = $res->fetch_assoc()) {
        $delivDate = trim($row['deliverydate']);
        $clientId  = trim($row['clientid'] ?: 'NO-CLIENT');
        $groupKey  = $delivDate . '__' . $clientId;
        $psono     = trim($row['psono'] ?: ('ITEM-' . $row['item_id']));

        $bucket = getScheduleItemBucket($row['status'], $row['actionstatus']);
        $qty = (int)$row['quantity'];
        $allocQty = ($row['allocate_ordqty'] !== null && $row['allocate_ordqty'] !== '') ? (int)$row['allocate_ordqty'] : 0;
        
        // Base tray estimate using 126 typical capacity
        $estTrays = (int)ceil($qty / 126);
        if ($estTrays < 1 && $qty > 0) $estTrays = 1;
        $allocTrays = ($allocQty > 0) ? (int)ceil($allocQty / 126) : 0;

        // Store bucket on row
        $row['bucket'] = $bucket;
        $row['calc_est_trays'] = $estTrays;
        $row['calc_alloc_trays'] = $allocTrays;
        $rawRows[] = $row;

        // Helper to update summary
        $updateSummaryBucket = function(&$bRef) use ($qty, $estTrays, $psono, $clientId, $groupKey) {
            $bRef['total_items']++;
            $bRef['total_plants'] += $qty;
            $bRef['total_trays']  += $estTrays;
            if (!empty($psono))    $bRef['_orders_map'][$psono] = true;
            if (!empty($clientId)) $bRef['_clients_map'][$clientId] = true;
            if (!empty($groupKey)) $bRef['_groups_map'][$groupKey] = true;
        };

        // Update 'all'
        $updateSummaryBucket($summary['all']);

        // Update specific bucket
        if (isset($summary[$bucket])) {
            $updateSummaryBucket($summary[$bucket]);
        }
    }

    // Finalize counts in summary
    foreach ($summary as $bKey => &$bData) {
        $bData['total_orders']  = count($bData['_orders_map']);
        $bData['total_clients'] = count($bData['_clients_map']);
        $bData['total_groups']  = count($bData['_groups_map']);
        unset($bData['_orders_map'], $bData['_clients_map'], $bData['_groups_map']);
    }
    unset($bData);

    // Second pass: Filter and build grouped display data based on active status filter
    foreach ($rawRows as $row) {
        $bucket = $row['bucket'];

        // If status filter is set (not 'all'), skip non-matching rows
        if ($statusFilter !== 'all' && $statusFilter !== '' && $bucket !== $statusFilter) {
            continue;
        }

        $delivDate  = trim($row['deliverydate']);
        $clientId   = trim($row['clientid'] ?: 'NO-CLIENT');
        $clientName = trim($row['display_client_name'] ?: 'Unknown Client');
        $groupKey   = $delivDate . '__' . $clientId;

        if (!isset($grouped[$groupKey])) {
            $targetTimestamp = strtotime($delivDate);
            $todayTimestamp = strtotime($todayDate);
            $diffDays = (int)round(($targetTimestamp - $todayTimestamp) / 86400);

            $relativeLabel = '';
            $relativeBadgeClass = '';
            if ($diffDays === 0) {
                $relativeLabel = 'Today';
                $relativeBadgeClass = 'rel-today';
            } elseif ($diffDays === -1) {
                $relativeLabel = 'Yesterday';
                $relativeBadgeClass = 'rel-yesterday';
            } elseif ($diffDays < -1) {
                $relativeLabel = abs($diffDays) . ' days ago';
                $relativeBadgeClass = 'rel-past';
            } elseif ($diffDays === 1) {
                $relativeLabel = 'Tomorrow';
                $relativeBadgeClass = 'rel-upcoming';
            } else {
                $relativeLabel = 'In ' . $diffDays . ' days';
                $relativeBadgeClass = 'rel-future';
            }

            $formattedDate = date('d M Y', $targetTimestamp);
            $dayOfWeek = date('l', $targetTimestamp);

            $grouped[$groupKey] = [
                'group_key'              => $groupKey,
                'delivery_date'          => $delivDate,
                'delivery_date_display'  => $formattedDate,
                'day_of_week'            => $dayOfWeek,
                'relative_label'         => $relativeLabel,
                'relative_class'         => $relativeBadgeClass,
                'diff_days'              => $diffDays,
                'client_id'              => $row['clientid'] ?: '-',
                'client_name'            => $clientName,
                'client_phone'           => $row['client_phone'],
                'delivery_address'       => $row['delivery_address'],
                'district'               => $row['districtname'] ?: '-',
                'state'                  => $row['statename'] ?: '-',
                'total_orders'           => 0,
                'total_order_plants'     => 0,
                'total_order_trays'      => 0,
                'total_allocated_plants' => 0,
                'total_allocated_trays'  => 0,
                'total_ready_to_sell_plants' => 0,
                'total_ready_to_sell_trays'  => 0,
                'orders_list'            => [],
                'items'                  => []
            ];
        }

        // Active bed locations for this item/PSO
        $activeBedList = [];
        $typicalCapacity = 126;
        if (!empty($row['psono'])) {
            $bedSql = "
                SELECT DISTINCT bed_no, polyhouseid, COUNT(*) as tray_count, COALESCE(qtypertray, 126) as capacity
                FROM krf_polyhouse_plant_details
                WHERE psono = ? 
                  AND (productname = ? OR ? = '')
                  AND status NOT IN ('Final Dispatch', 'Removed', 'Empty After Shifting')
                GROUP BY bed_no, polyhouseid
                LIMIT 5
            ";
            $stmtBed = $krfconn->prepare($bedSql);
            if ($stmtBed) {
                $pName = $row['product_name'];
                $psoParam = $row['psono'];
                $stmtBed->bind_param('sss', $psoParam, $pName, $pName);
                $stmtBed->execute();
                $bedRes = $stmtBed->get_result();
                while ($br = $bedRes->fetch_assoc()) {
                    $phName = $polyMap[$br['polyhouseid']] ?? ('PH ' . $br['polyhouseid']);
                    $activeBedList[] = [
                        'bed_no'     => $br['bed_no'] ?: 'Bed-?',
                        'polyhouse'  => $phName,
                        'tray_count' => (int)$br['tray_count']
                    ];
                    if ((int)$br['capacity'] > 0) {
                        $typicalCapacity = (int)$br['capacity'];
                    }
                }
                $stmtBed->close();
            }
        }

        $qty = (int)$row['quantity'];
        $estTrays = $typicalCapacity > 0 ? (int)ceil($qty / $typicalCapacity) : (int)ceil($qty / 126);
        if ($estTrays < 1 && $qty > 0) $estTrays = 1;

        $allocatedPlants = ($row['allocate_ordqty'] !== null && $row['allocate_ordqty'] !== '') ? (int)$row['allocate_ordqty'] : 0;
        $allocatedTrays = ($allocatedPlants > 0) ? (int)ceil($allocatedPlants / ($typicalCapacity > 0 ? $typicalCapacity : 126)) : 0;

        // Fetch Ready to Sell trays and plants count from krf_polyhouse_plant_details
        $readyToSellTrays = 0;
        $readyToSellPlants = 0;
        if (!empty($row['psono'])) {
            $rtsSql = "
                SELECT 
                    COUNT(*) as rts_trays,
                    COALESCE(SUM(qtypertray), 0) as rts_plants
                FROM krf_polyhouse_plant_details
                WHERE psono = ? 
                  AND (productname = ? OR ? = '')
                  AND (LOWER(TRIM(status)) = 'readytosale' OR LOWER(TRIM(status)) = 'ready to sale' OR LOWER(TRIM(status)) = 'ready to sell')
            ";
            $stmtRts = $krfconn->prepare($rtsSql);
            if ($stmtRts) {
                $pName = $row['product_name'];
                $psoParam = $row['psono'];
                $stmtRts->bind_param('sss', $psoParam, $pName, $pName);
                $stmtRts->execute();
                $rtsRes = $stmtRts->get_result()->fetch_assoc();
                if ($rtsRes) {
                    $readyToSellTrays = (int)$rtsRes['rts_trays'];
                    $readyToSellPlants = (int)$rtsRes['rts_plants'];
                }
                $stmtRts->close();
            }
        }

        $healthyPlant = ($row['healthyplant'] !== null && $row['healthyplant'] !== '') ? (int)$row['healthyplant'] : null;
        $unhealthyPlant = ($row['unhealthyplant'] !== null && $row['unhealthyplant'] !== '') ? (int)$row['unhealthyplant'] : null;
        $isShifted = strtolower(trim($row['isshifted'] ?? 'no'));
        $shiftingQty = ($row['shiftingqty'] !== null && $row['shiftingqty'] !== '') ? (int)$row['shiftingqty'] : 0;

        $itemData = [
            'item_id'              => (int)$row['item_id'],
            'psono'                => $row['psono'],
            'sono'                 => $row['sono'],
            'stockorder_no'        => $row['stockorder_no'] ?: '-',
            'product_sku'          => $row['product_sku'] ?: '-',
            'product_name'         => $row['product_name'],
            'quantity'             => $qty,
            'est_trays'            => $estTrays,
            'allocated_qty'        => $allocatedPlants,
            'allocated_trays'      => $allocatedTrays,
            'ready_to_sell_qty'    => $readyToSellPlants,
            'ready_to_sell_trays'  => $readyToSellTrays,
            'shiftingqty'          => $shiftingQty,
            'isshifted'            => $isShifted,
            'healthyplant'         => $healthyPlant,
            'unhealthyplant'       => $unhealthyPlant,
            'status'               => $row['status'] ?: 'Pending',
            'actionstatus'         => $row['actionstatus'] ?: '',
            'bucket'               => $bucket,
            'rate'                 => (float)$row['rate'],
            'amount'               => (float)$row['amount'],
            'active_beds'          => $activeBedList
        ];

        $grouped[$groupKey]['items'][] = $itemData;
        $grouped[$groupKey]['total_order_plants']          += $qty;
        $grouped[$groupKey]['total_order_trays']           += $estTrays;
        $grouped[$groupKey]['total_allocated_plants']      += $allocatedPlants;
        $grouped[$groupKey]['total_allocated_trays']       += $allocatedTrays;
        $grouped[$groupKey]['total_ready_to_sell_plants']  += $readyToSellPlants;
        $grouped[$groupKey]['total_ready_to_sell_trays']   += $readyToSellTrays;

        if (!empty($row['psono']) && !in_array($row['psono'], $grouped[$groupKey]['orders_list'])) {
            $grouped[$groupKey]['orders_list'][] = $row['psono'];
        }
    }

    // Set total_orders count for each group
    foreach ($grouped as $k => &$g) {
        $g['total_orders'] = count($g['orders_list']);
    }
    unset($g);

    outJson([
        'status'  => 'success',
        'data'    => array_values($grouped),
        'summary' => $summary,
        'metrics' => [
            'active_status' => $statusFilter,
            'scope'         => $scope,
            'week_range'    => [
                'start' => $mondayThisWeek,
                'end'   => $sundayThisWeek,
                'label' => $weekLabel
            ],
            'current_date'  => $todayDate
        ]
    ]);
}

// Default fallback
outJson(['status' => 'error', 'message' => 'Invalid action: ' . htmlspecialchars($action)]);
