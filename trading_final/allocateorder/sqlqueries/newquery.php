<?php 
date_default_timezone_set("Asia/Kolkata");
require('../../../connection.php');  
header('Content-Type: application/json; charset=utf-8');
$action = isset($_GET['action']) ? $_GET['action'] : ''; 
$projectid = isset($_POST['projectid']) ? trim($_POST['projectid']) : '';
$dataarray = []; 
if ($projectid === '') {
    echo json_encode([]);
    exit;
}
else{
   $dataarray = getOrderReportData($krfconn, $projectid, $_POST);
}

echo json_encode($dataarray);


function getOrderReportData($conn, $projectid, $postData)
{
    $returnarray = [];

    $promiseddt = !empty($postData['promiseddt']) ? $postData['promiseddt'] : null;
    $deliverydate = $promiseddt ?: date('Y-m-d');

    $productid = $postData['productid'] ?? '';
    $psono = $postData['psono'] ?? '';
    $stockorder_no = $postData['stockorder_no'] ?? '';
    $clientid = $postData['clientid'] ?? '';

    $baseDateExpr = $promiseddt ? "DATE('$promiseddt')" : "CURDATE()";

    $subqry = "";
    $orderby = "";

    if ($clientid) {
        $subqry = " AND i.clientid = '{$clientid}' ";
        $orderby = "
            ORDER BY CASE 
                WHEN s.psono = '{$psono}' 
                     AND i.stockorder_no = '{$stockorder_no}'
                     AND i.product_id = {$productid} THEN 0
                WHEN s.psono = '{$psono}' 
                     AND DATEDIFF(i.deliverydate, '{$deliverydate}') = 0 THEN 1
                WHEN DATEDIFF(i.deliverydate, '{$deliverydate}') BETWEEN 1 AND 10 THEN 2
                ELSE 3
            END
        ";
    } else {
        $orderby = "
            ORDER BY 
            CASE 
                WHEN DATEDIFF(DATE(i.deliverydate), {$baseDateExpr}) BETWEEN 0 AND 10 THEN 0
                WHEN DATEDIFF(DATE(i.deliverydate), {$baseDateExpr}) < 0 THEN 1
                ELSE 2
            END,
            i.deliverydate
        ";
    }

    /* ======================= MAIN QUERY ======================= */

    $qry = "
    SELECT 
        clnt.client_name,
        clnt.phones,
        clnt.client_email,
        clnt.invoicing_address,

        i.clientid,
        i.product_id,
        p.ProductName AS productname,
        s.sono,
        i.psono,
        i.stockorder_no,
        i.quantity,
        i.deliverydate,
        DATE_FORMAT(i.deliverydate,'%d-%m-%Y') AS ddfrmt,
        DATEDIFF(i.deliverydate, CURDATE()) AS days_to_dispatch,
        i.rate,
        i.status AS orderstatus,

        -- Production & consumption
        IFNULL(stock.production_qty,0) AS production_qty,
        IFNULL(stock.consumed_qty,0) AS consumed_qty,
        (IFNULL(stock.production_qty,0) - IFNULL(stock.consumed_qty,0)) AS available_qty,

        -- Stage
        IFNULL(stage.stage,'Not Started') AS stage

    FROM krf_sales_order s
    JOIN krf_sales_order_items i ON i.sono = s.sono
    JOIN product_master p ON p.ProductID = i.product_id
    JOIN krf_client_master clnt ON clnt.client_id = s.clientid

    /* Production + Consumed */
    LEFT JOIN (
        SELECT 
            p.productid,
            p.stockorder_no,
            SUM(p.quantity) AS production_qty,
            IFNULL(SUM(c.consumed_qty),0) AS consumed_qty
        FROM krf_production_stockorder p
        LEFT JOIN krf_productionorder_consumed c
            ON c.product_id = p.productid
           AND c.stockorderno = p.stockorder_no
        GROUP BY p.productid, p.stockorder_no
    ) stock ON stock.productid = i.product_id 
           AND stock.stockorder_no = i.stockorder_no

    /* Stage info */
    LEFT JOIN (
        SELECT 
            productid,
            stockorder_no,
            MAX(
                FIELD(
                    status,
                    'Sowing','Germination','Polyhouse',
                    'Grafting','Healing','Hardening',
                    'Clip Stick Removal','ReadyToSale'
                )
            ) AS stage_rank,
            MAX(status) AS stage
        FROM krf_polyhouse_sowingplant_details
        GROUP BY productid, stockorder_no
    ) stage ON stage.productid = i.product_id 
           AND stage.stockorder_no = i.stockorder_no

    WHERE  s.projectid = {$projectid}
      AND (IFNULL(i.status,'') = '' OR i.status = 'Pending')
      {$subqry}
      {$orderby}
    ";

    $result = $conn->query($qry);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {

            $stkarray = getstockqty(
                $conn,
                $projectid,
                $row['clientid'],
                $row['psono'],
                $row['stockorder_no'],
                $row['product_id'],
                $row['deliverydate']
            );

            $trayarray = gettray_attributes($conn, $stkarray['trayid'] ?? '');
            $bankarray = getbankdetails($conn, $projectid, $row['clientid']);
            $advancearray = getadvancepaymet_agtorder($conn, $row['clientid'], $row['psono']);

            $returnarray[] = [
                'clientid' => $row['clientid'],
                'client_name' => $row['client_name'],
                'productname' => $row['productname'],
                'sono' => $row['sono'],
                'psono' => $row['psono'],
                'stockorder_no' => $row['stockorder_no'],
                'quantity' => $row['quantity'],
                'deliverydate' => $row['deliverydate'],
                'ddfrmt' => $row['ddfrmt'],
                'days_to_dispatch' => $row['days_to_dispatch'],

                'production_qty' => $row['production_qty'],
                'consumed_qty' => $row['consumed_qty'],
                'available_qty' => $row['available_qty'],
                'stage' => $row['stage'],

                'readyqty' => $stkarray['readyqty'] ?? '',
                'readydate' => $stkarray['readydate'] ?? '',
                'trayid' => $stkarray['trayid'] ?? '',
                'trayqty' => $stkarray['trayqty'] ?? '',

                'bankname' => $bankarray['bankname'],
                'accountno' => $bankarray['accountno'],
                'ifsccode' => $bankarray['ifsccode'],
                'branch' => $bankarray['branch'],

                'advanceamount' => $advancearray['advance']
            ];
        }
    }

    return $returnarray;
}



?>
