<?php
date_default_timezone_set("Asia/Kolkata");
require('../../../connection.php');

header('Content-Type: application/json; charset=utf-8');

$action    = $_GET['action'] ?? '';
$projectid = (int)($_POST['projectid'] ?? 0);

if ($projectid <= 0  ) {
    echo json_encode([]);
    exit;
}else if ($action === "pendinghistoryclient_stage1") {

    $promiseddt   = $_POST['promiseddt'] ?? null;
    $deliverydate = $promiseddt ?: date('Y-m-d');

    $subqry  = "";
    $orderby = "
        ORDER BY
        CASE
            WHEN DATEDIFF(i.deliverydate,CURDATE()) BETWEEN 0 AND 10 THEN 0
            WHEN DATEDIFF(i.deliverydate,CURDATE()) < 0 THEN 1
            ELSE 2
        END,
        i.deliverydate
    ";

    /* ===========================
       INIT REQUIRED ARRAYS
    =========================== */

    $stockPool         = [];
    $stockQtyMap       = [];   // ✅ INIT
    $totalAllocatedMap = [];   // ✅ INIT
    $returnarray       = [];

    /* ===========================
       BUILD STOCK POOL + STOCK QTY
    =========================== */

    $stkqry = "
        SELECT distinct clnt.client_name,p.ProductName AS productname,
            i.stockorder_no,
            i.product_id,
            stk.quantity AS stockqty
        FROM krf_sales_order s
        JOIN krf_sales_order_items i 
            ON i.sono = s.sono
        JOIN krf_production_stockorder stk 
            ON stk.stockorder_no = i.stockorder_no
           AND stk.productid = i.product_id
           JOIN product_master p ON p.ProductID = i.product_id
            JOIN krf_client_master clnt ON clnt.client_id = stk.clientid
        WHERE s.projectid = {$projectid}
          AND IFNULL(i.status,'') IN ('','Pending')
        {$subqry}
    ";

    $res = $krfconn->query($stkqry);

    if ($res) {
        while ($r = $res->fetch_assoc()) {

            // ✅ STORE ORIGINAL STOCK QTY
            $stockQtyMap
                [$r['stockorder_no']]
                [$r['product_id']] = (int)$r['stockqty'];

                 $stockClientMap
            [$r['stockorder_no']]
            [$r['product_id']] = $r['client_name'];


            $productNameMap
            [$r['product_id']] = $r['productname'];   
             
            $stages = getstockqty(
                $krfconn,
                $projectid,
                $r['stockorder_no'],
                (int)$r['product_id']
            );

            foreach ($stages as $stg) {
                $stockPool
                    [$r['stockorder_no']]
                    [$r['product_id']]
                    [$stg['stage']] = [
                        'totalqty'     => (int)$stg['readyqty'],
                        'availableqty' => (int)$stg['readyqty'],
                        'readydate'    => $stg['readydate']
                    ];
            }
        }
    }

    /* ===========================
       FETCH ORDERS & ALLOCATE
    =========================== */

    $qry = "
        SELECT 
            clnt.client_name,
            p.ProductName AS productname,
            s.clientid,
            s.sono,
            i.psono,
            i.deliverydate,
            DATE_FORMAT(i.deliverydate,'%d-%m-%Y') AS ddfrmt,
            i.stockorder_no,
            i.product_id,
            i.quantity
        FROM krf_sales_order s
        JOIN krf_sales_order_items i ON i.sono = s.sono
        JOIN product_master p ON p.ProductID = i.product_id
        JOIN krf_client_master clnt ON clnt.client_id = s.clientid
        WHERE s.projectid = {$projectid}
          AND IFNULL(i.status,'') IN ('','Pending')
        {$subqry}
        {$orderby}
    ";

    $res = $krfconn->query($qry);

    if ($res) {
        while ($row = $res->fetch_assoc()) {

            $allocation = allocateStock(
                $stockPool,
                $row['stockorder_no'],
                (int)$row['product_id'],
                (int)$row['quantity']
            );

            // ✅ TRACK TOTAL ALLOCATED
            $totalAllocatedMap
                [$row['stockorder_no']]
                [$row['product_id']] =
                ($totalAllocatedMap[$row['stockorder_no']][$row['product_id']] ?? 0)
                + $allocation['allocatedqty'];

            $returnarray[] = [
                'rowtype'              => 'order',
                'clientid'             => $row['clientid'],
                'client_name'          => $row['client_name'],
                'productname'          => $row['productname'],
                'sono'                 => $row['sono'],
                'psono'                => $row['psono'],
                'stockorder_no'        => $row['stockorder_no'],
                'deliverydate'         => $row['deliverydate'],
                'ddfrmt'               => $row['ddfrmt'],
                'product_id'           => $row['product_id'],
                'orderqty'             => (int)$row['quantity'],
                'allocatedqty'         => $allocation['allocatedqty'],
                'restqty'              => $allocation['restqty'],
                'display_status'       => $allocation['display_status'],
                'allocation_breakdown' => $allocation['breakdown']
            ];
        }
    }
 

 

foreach ($stockQtyMap as $stkNo => $products) {
    foreach ($products as $pid => $stockQty) {

        $allocated = $totalAllocatedMap[$stkNo][$pid] ?? 0;
        $unallocatedQty = max(0, $stockQty - $allocated);

        if ($unallocatedQty <= 0) continue;

        $returnarray[] = [
            'rowtype'        => 'summary',
            'label'          => 'Unallocated Stock',
            'stockorder_no'  => $stkNo,
            'product_id'     => $pid,
            'productname'    => $productNameMap[$pid] ?? '',
            'client_name'    => $stockClientMap[$stkNo][$pid] ?? 'Trader',
            'qty'            => $unallocatedQty
        ];
    }
}




    echo json_encode($returnarray, JSON_UNESCAPED_UNICODE);
    exit;
}



/* ======================================================
   FUNCTIONS
====================================================== */

/*function allocateStock(
    array &$stockPool,
    string $stockorder_no,
    int $productid,
    int $orderqty
): array {

    $priority = [
        "Trader Warehouse",
        "Clip Stick Removal",
        "Hardening",
        "Healing",
        "Grafting",
        "Polyhouse",
        "Germination",
        "Sowing"
    ];

    $allocatedqty = 0;
    $breakdown = [];
    $firstStageUsed = null;

    foreach ($priority as $stage) {

        if (
            empty($stockPool[$stockorder_no][$productid][$stage]) ||
            $stockPool[$stockorder_no][$productid][$stage]['availableqty'] <= 0
        ) {
            continue;
        }

        $available = $stockPool[$stockorder_no][$productid][$stage]['availableqty'];
        $need = $orderqty - $allocatedqty;

        if ($need <= 0) break;

        $use = min($available, $need);

        if ($use > 0 && $firstStageUsed === null) {
            $firstStageUsed = $stage;
        }
 
        $breakdown[] = [
            'stage'        => $stage,
            'allocatedqty' => $use,
            'readydate'    => !empty($stockPool[$stockorder_no][$productid][$stage]['readydate'])
                ? date('d-m-Y', strtotime($stockPool[$stockorder_no][$productid][$stage]['readydate']))
                : ''
        ];


        $stockPool[$stockorder_no][$productid][$stage]['availableqty'] -= $use;
        $allocatedqty += $use;
    }

    // Allocation result
    if ($allocatedqty <= 0) {
        $status = 'Not Allocated';
    } elseif ($allocatedqty >= $orderqty) {
        $status = 'Allocated';
    } else {
        $status = 'Partial';
    }

    // UI-friendly combined text
    $displayStatus = $status;
    if ($firstStageUsed !== null) {
        $displayStatus .= ' – ' . $firstStageUsed .allocatedqty;
        
    }

    return [
        'allocatedqty'   => $allocatedqty,
        'restqty'        => max(0, $orderqty - $allocatedqty),
        'status'         => $status,          // logical state
        'first_stage'    => $firstStageUsed,  // metadata
        'display_status' => $displayStatus,   // UI text
        'breakdown'      => $breakdown
    ];
}*/

function allocateStock(
    array &$stockPool,
    string $stockorder_no,
    int $productid,
    int $orderqty
): array {

    $priority = [
        "Trader Warehouse",
        "Clip Stick Removal",
        "Hardening",
        "Healing",
        "Grafting",
        "Polyhouse",
        "Germination",
        "Sowing"
    ];

    $allocatedqty = 0;
    $breakdown = [];
    $firstStageUsed = null;
        $firstStageAllocatedQty=0;
    foreach ($priority as $stage) {

        if (
            empty($stockPool[$stockorder_no][$productid][$stage]) ||
            $stockPool[$stockorder_no][$productid][$stage]['availableqty'] <= 0
        ) {
            continue;
        }

        $available = $stockPool[$stockorder_no][$productid][$stage]['availableqty'];
        $need = $orderqty - $allocatedqty;

        if ($need <= 0) break;

        $use = min($available, $need);

        if ($use > 0 && $firstStageUsed === null) {
            $firstStageUsed = $stage;
             $firstStageAllocatedQty = $use;
        }

        $breakdown[] = [
            'stage'        => $stage,
            'allocatedqty' => $use,
            'readydate'    => !empty($stockPool[$stockorder_no][$productid][$stage]['readydate'])
                ? date('d-m-Y', strtotime($stockPool[$stockorder_no][$productid][$stage]['readydate']))
                : ''
        ];

        $stockPool[$stockorder_no][$productid][$stage]['availableqty'] -= $use;
        $allocatedqty += $use;
    }

    // Logical allocation status
    if ($allocatedqty <= 0) {
        $status = 'Not Allocated';
    } elseif ($allocatedqty >= $orderqty) {
        $status = 'Allocated';
    } else {
        $status = 'Partial';
    }

    // ✅ FINAL UI DISPLAY STATUS
    if ($firstStageUsed !== null) {
        $displayStatus = $firstStageUsed . ' – ' . $firstStageAllocatedQty;
    } else {
        $displayStatus = 'Not Yet started';
    }

    return [
        'allocatedqty'   => $allocatedqty,
        'restqty'        => max(0, $orderqty - $allocatedqty),
        'status'         => $status,          // backend logic
        'first_stage'    => $firstStageUsed,  // metadata
        'display_status' => $displayStatus,   // UI string
        'breakdown'      => $breakdown
    ];
}



 

 function getstockqty($conn, $projectid,  $stockorder_no, $productid) {

    $stagestatus = [];

    // Trader warehouse (single stage)
    $trade = chktradehousestage($conn, $projectid, $stockorder_no, $productid);
    if (!empty($trade)) {
        $stagestatus[] = $trade;
    }

    // Polyhouse stages (multiple)
    $polyStages = chkpolyhousestage($conn, $projectid, $stockorder_no, $productid);
    if (!empty($polyStages)) {
        $stagestatus = array_merge($stagestatus, $polyStages);
    }

    // Sowing stages (multiple)
    $sowingStages = chksowingstage($conn, $projectid, $stockorder_no, $productid);
    if (!empty($sowingStages)) {
        $stagestatus = array_merge($stagestatus, $sowingStages);
    }

    if (!empty($stagestatus)) {
        return $stagestatus;
    }

    // Default fallback
    return [[
        "readyqty"        => 0,
        "readydate"       => null,
        "ready_ddformat"  => "",
        "trayqty"         => "",
        "stage"           => "Not yet started"
    ]];
}

function chktradehousestage($conn, $projectid, $stockorder_no, $productid) {
    $sql = "SELECT
            SUM(stk.qtypertray) AS readyqty,
            MAX(stk.created_at) AS readydt,
            GROUP_CONCAT(DISTINCT stk.qtypertray) AS trayqty
        FROM krf_readytosale_stocks stk
        WHERE stk.projectid = ?
          AND stk.stockorder_no = ?
          AND stk.productid = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isi", $projectid, $stockorder_no, $productid);
    $stmt->execute();

    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row && (int)$row['readyqty'] > 0) {
        return [
            "stage"           => "Trader Warehouse",
            "readyqty"        => (int)$row['readyqty'],
            "readydate"       => $row['readydt'],
            "ready_ddformat"  => $row['readydt'] ? date("d-m-Y", strtotime($row['readydt'])) : "",
            "trayqty"         => $row['trayqty'] ?? "", 
        ];
    }

    return [];
}
function chkpolyhousestage($conn, $projectid, $stockorder_no, $productid) {

    $sql = "
        SELECT
            stk.status,
            SUM(stk.qtypertray) AS readyqty,
            MAX(stk.created_at) AS readydt,
            GROUP_CONCAT(DISTINCT stk.qtypertray) AS trayqty
        FROM krf_polyhouse_plant_details stk
        WHERE stk.projectid = ?
          AND stk.stockorder_no = ?
          AND stk.productid = ?
          AND stk.health_status = 'Healthy'
          AND stk.status IN ('Polyhouse','Grafting','Healing','Hardening','Clip Stick Removal')
        GROUP BY stk.status
        ORDER BY MAX(stk.created_at) DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isi", $projectid, $stockorder_no, $productid);
    $stmt->execute();

    $result = $stmt->get_result();
    $stages = [];

    while ($row = $result->fetch_assoc()) {
        if ((int)$row['readyqty'] > 0) {
            $stages[] = [
                "stage"           => $row['status'],
                "readyqty"        => (int)$row['readyqty'],
                "readydate"       => $row['readydt'],
                "ready_ddformat"  => $row['readydt'] ? date("d-m-Y", strtotime($row['readydt'])) : "",
                "trayqty"         => $row['trayqty'] ?? ""
            ];
        }
    }

    $stmt->close();
    return $stages;
}
function chksowingstage($conn, $projectid, $stockorder_no, $productid) {

    $sql = "SELECT stk.status,
            SUM(stk.traycavity) AS readyqty,
            MAX(stk.created_at) AS readydt,
            GROUP_CONCAT(DISTINCT stk.traycavity) AS trayqty
        FROM krf_polyhouse_sowingplant_details stk
        WHERE stk.projectid = ?
          AND stk.stockorder_no = ?
          AND stk.productid = ?
          AND stk.health_status = 'Healthy'
          AND stk.status IN ('Sowing','Germination')
        GROUP BY stk.status
        ORDER BY MAX(stk.created_at) DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isi", $projectid, $stockorder_no, $productid);
    $stmt->execute();

    $result = $stmt->get_result();
    $stages = [];

    while ($row = $result->fetch_assoc()) {
        if ((int)$row['readyqty'] > 0) {
            $stages[] = [
                "stage"           => $row['status'],
                "readyqty"        => (int)$row['readyqty'],
                "readydate"       => $row['readydt'],
                "ready_ddformat"  => $row['readydt'] ? date("d-m-Y", strtotime($row['readydt'])) : "",
                "trayqty"         => $row['trayqty'] ?? ""
            ];
        }
    }

    $stmt->close();
    return $stages;
}

?>
