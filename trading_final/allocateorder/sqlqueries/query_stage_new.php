<?php
date_default_timezone_set("Asia/Kolkata");
require('../../../connection.php');

header('Content-Type: application/json; charset=utf-8');

$action    = $_GET['action'] ?? '';
$projectid = (int)($_POST['projectid'] ?? 0);

if ($projectid <= 0) {
    echo json_encode([]);
    exit;
}

if ($action === "pendinghistoryclient_stage1") {
    $productid = isset($_POST['productid']) ? $_POST['productid'] : ""; 
    $psono = isset($_POST['psono']) ? $_POST['psono'] : "";
    $stockorder_no = isset($_POST['stockorder_no']) ? $_POST['stockorder_no'] : "";
    $clientid = isset($_POST['clientid']) ? $_POST['clientid'] : ""; 
    $promiseddt = $_POST['promiseddt'] ?? null;
    $STAGE_PRIORITY = [
        'Trader Warehouse',
        'Clip Stick Removal',
        'Hardening',
        'Healing',
        'Grafting',
        'Polyhouse',
        'Germination',
        'Sowing'
    ];
    $sales = getSalesRows($krfconn, $projectid, $promiseddt,$productid ,$clientid,$psono ,$stockorder_no);
    $finalRows = [];
    $sno = 1; 
    $lastGroupKey = '';
    foreach ($sales as $order) {     
       $groupKey = $order['stockorder_no'] . '|' . $order['clientid'] . '|' . ($order['psono'] ?? '');
        if ($groupKey !== $lastGroupKey) {
            $currentSno = $sno++;
            $lastGroupKey = $groupKey;
        }
        $stages = getStockOrderStages(
            $krfconn,
            $projectid,
            $order['stockorder_no'],
            $order['product_id']
        );
        usort($stages, function ($a, $b) use ($STAGE_PRIORITY) {
            return array_search($a['stage'], $STAGE_PRIORITY)
                 <=> array_search($b['stage'], $STAGE_PRIORITY);
        });
        $first = true;
        if(count($stages)){
            foreach ($stages as $stage) {
                $finalRows[] = [
                    'sno'            => $first ? $currentSno : '',
                    'phoneno'   => $order['phoneno'],
                    'clientemail'   => $order['clientemail'],
                    'invoice_address'   => $order['invoice_address'],  
                    'clientid'      => $order['clientid'],
                    'product_id'    => $order['product_id'],
                    'psono'          => $order['psono'],
                    'client_name'    => $order['client_name'],
                    'productname'    => $order['productname'],
                    'order_qty'      => $order['qty'],
                    'promisedt'      => date('d-m-Y', strtotime($order['promisedt'])),
                    'stockorder_no'  => $order['stockorder_no'],
                    'stockorder_qty' => $order['stockorder_qty'],
                    'stage'          => $stage['stage'],
                    'stage_qty'      => $stage['readyqty'],
                    'available_date' => $stage['ready_ddformat']
                ];

                $first = false;
            }
        }else{
                $finalRows[] = [
                    'sno'            => $first ? $currentSno : '',
                    'phoneno'   => $order['phoneno'],
                    'clientemail'   => $order['clientemail'],
                    'invoice_address'   => $order['invoice_address'],  
                    'clientid'      => $order['clientid'],
                    'product_id'    => $order['product_id'],
                    'psono'          => $order['psono'],
                    'client_name'    => $order['client_name'],
                    'productname'    => $order['productname'],
                    'order_qty'      => $order['qty'],
                    'promisedt'      => date('d-m-Y', strtotime($order['promisedt'])),
                    'stockorder_no'  => $order['stockorder_no'],
                    'stockorder_qty' => $order['stockorder_qty'],
                    'stage'          => "Not Yet Started",
                    'stage_qty'      => $order['qty'],
                    'available_date' => ""
                ];

        }    
    }
    echo json_encode($finalRows, JSON_UNESCAPED_UNICODE);
    exit;
}
 
 function getStockOrderStages($conn, $projectid, $stockorder_no, $productid): array {
    $stages = [];
    $tw = chktradehousestage($conn, $projectid, $stockorder_no, $productid);
    if (!empty($tw)) $stages[] = $tw;
    foreach (chkpolyhousestage($conn, $projectid, $stockorder_no, $productid) as $p) {
        $stages[] = $p;
    }
    foreach (chksowingstage($conn, $projectid, $stockorder_no, $productid) as $s) {
        $stages[] = $s;
    }
    return $stages;
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
          AND stk.status IN ('Polyhouse','Grafting','Healing','Hardening','Clip Stick Removal') and stk.roottype='Root'
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
          AND stk.status IN ('Sowing','Germination') and stk.roottype='Root'
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
 function getSalesRows(
    $conn,
    $projectid,
    $promisedt,
    $productid = null,
    $clientid = null,
    $psono = null,
    $stockorder_no = null
): array {

    $rows = [];

    /* 🔹 FILTER CLAUSE */
    $whereOuter = "";
    if (!empty($clientid)) {
        $whereOuter = " WHERE t.clientid = '{$clientid}' ";
    }

    /* 🔹 DEFAULT SORT */
    $orderBy = "
        ORDER BY
            CASE
                WHEN t.promisedt BETWEEN '{$promisedt}'
                                     AND DATE_ADD('{$promisedt}', INTERVAL 10 DAY)
                    THEN 0
                WHEN t.promisedt < '{$promisedt}'
                    THEN 1
                ELSE 2
            END,
            t.promisedt
    ";

    /* 🔹 CLIENT-SPECIFIC SORT */
    if (!empty($clientid)) {
        $orderBy = "
        ORDER BY
            CASE
                WHEN t.psono = '{$psono}'
                     AND t.stockorder_no = '{$stockorder_no}'
                     AND t.product_id = {$productid}
                    THEN 0

                WHEN t.psono = '{$psono}'
                     AND t.promisedt = '{$promisedt}'
                     AND t.product_id = {$productid}
                    THEN 1

                WHEN t.psono = '{$psono}'
                     AND t.promisedt = '{$promisedt}'
                    THEN 2

                WHEN t.promisedt BETWEEN DATE_ADD('{$promisedt}', INTERVAL 1 DAY)
                                     AND DATE_ADD('{$promisedt}', INTERVAL 10 DAY)
                    THEN 3

                ELSE 4
            END,
            t.promisedt,
            t.psono,
            t.stockorder_no
        ";
    }

    /* 🔹 MAIN QUERY */
      $sql = "
    SELECT 
        t.client_name,
        t.phoneno,
        t.clientemail,
        t.invoice_address,
        t.productname,
        t.promisedt,
        t.stockorder_no,
        t.clientid,
        t.psono,
        t.product_id,
        t.qty,
        t.stockorder_qty
    FROM (
        /* 🔹 ACTUAL SALES */
        SELECT 
            clnt.client_name,
            IFNULL(clnt.phones,'') AS phoneno,
            IFNULL(clnt.client_email,'') AS clientemail,
            IFNULL(clnt.invoicing_address,'') AS invoice_address,
            p.ProductName AS productname,
            i.deliverydate AS promisedt,
            i.stockorder_no,
            i.clientid,
            IFNULL(i.psono,'') AS psono,
            i.product_id,
            SUM(i.quantity) AS qty,
            ps.quantity AS stockorder_qty
        FROM krf_sales_order_items i
        JOIN product_master p 
            ON p.ProductID = i.product_id
        JOIN krf_client_master clnt 
            ON clnt.client_id = i.clientid
        LEFT JOIN krf_production_stockorder ps
            ON ps.stockorder_no = i.stockorder_no
           AND ps.productid = i.product_id
        WHERE i.projectid = '{$projectid}'
          AND IFNULL(i.status,'') IN ('','Pending')
        GROUP BY i.stockorder_no, i.product_id, i.clientid

        UNION ALL

        /* 🔹 BALANCE FROM PRODUCTION */
        SELECT 
            clnt.client_name,
            IFNULL(clnt.phones,'') AS phoneno,
            IFNULL(clnt.client_email,'') AS clientemail,
            IFNULL(clnt.invoicing_address,'') AS invoice_address,
            pr_m.ProductName AS productname,
            p.expected_delivery_date AS promisedt,
            p.stockorder_no,
            p.clientid,
            IFNULL(p.psono,'') AS psono,
            p.productid AS product_id,
            (p.quantity - IFNULL(SUM(i.quantity), 0)) AS qty,
            p.quantity AS stockorder_qty
        FROM krf_production_stockorder p
        JOIN product_master pr_m 
            ON pr_m.ProductID = p.productid
        JOIN krf_client_master clnt 
            ON clnt.client_id = p.clientid
        LEFT JOIN krf_sales_order_items i
            ON i.stockorder_no = p.stockorder_no
           AND i.product_id = p.productid
           AND IFNULL(i.status,'') IN ('','Pending')
        WHERE p.projectid = '{$projectid}'
          AND IFNULL(p.status,'') IN ('','confirmed')
        GROUP BY p.stockorder_no, p.productid, p.clientid, p.quantity
        HAVING qty > 0
    ) t
    {$whereOuter}
    {$orderBy}
    ";

    $res = $conn->query($sql);
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }

    return $rows;
}


?>
