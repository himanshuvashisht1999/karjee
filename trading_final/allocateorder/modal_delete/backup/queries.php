<?php 
date_default_timezone_set("Asia/Kolkata");
require('../../../connection.php');  
header('Content-Type: application/json; charset=utf-8');
$action = isset($_GET['action']) ? $_GET['action'] : ''; 
$projectid = isset($_POST['projectid']) ? trim($_POST['projectid']) : '';
if ($projectid === '') {
    echo json_encode([]);
    exit;
} 
if ($action === "tradingquestion") {
    $sql = "SELECT id, Question FROM krf_tradingquestion WHERE projectid = $projectid";
    $res = $krfconn->query($sql);
    $data = [];
    while($row = $res->fetch_assoc()){
        $data[] = $row;
    }

    echo json_encode($data);
}else if ($action === "advancepayment") {
    $sql = "SELECT id, Question FROM advance_payment_transactions WHERE projectid = $projectid";
    $res = $krfconn->query($sql);
    $data = [];
    while($row = $res->fetch_assoc()){
        $data[] = $row;
    }

    echo json_encode($data);
}/*else if($action==="sumarray_requestdate"){
    $returnarray = []; 
    $promiseddt = isset($_POST['promiseddt']) && $_POST['promiseddt'] !== '' 
        ? $_POST['promiseddt']  : null;        
    $baseDate = $promiseddt ? $promiseddt : date('Y-m-d');
    $projectid = (int)$projectid;  
    $productid =  $_POST['productid'];  
    $orderno = $_POST['orderno'];   
    $clientid = $_POST['clientid']; 

 $q = "SELECT  clnt.client_name, p.ProductName AS productname, p.IsGrafted,  soi.psono, so.clientid, soi.product_id, soi.deliverydate, soi.quantity, DATE_FORMAT(soi.deliverydate, '%d-%m-%Y') AS ddfrmt,   IFNULL((
                SELECT SUM(s.qtypertray)
                FROM krf_readytosale_stocks s
                WHERE s.productid = soi.product_id and IFNULL(s.finalclientid, '')=''
                  AND date(s.created_at) <= date(soi.deliverydate)
                  AND s.psono = soi.psono
            ), 0) AS ready_upto_delivery  

             FROM krf_sales_order_items soi
        JOIN krf_sales_order so ON so.psono = soi.psono
        JOIN product_master p ON p.ProductID = soi.product_id
        JOIN krf_client_master clnt ON clnt.client_id = so.clientid

        WHERE so.projectid = ?
          AND so.clientid = ?
          AND (IFNULL(so.status,'') = '' OR IFNULL(so.status,'') = 'Pending')

        ORDER BY 
          CASE 
            WHEN soi.product_id = ? AND soi.psono = ? AND DATEDIFF(soi.deliverydate, ?) = 0 THEN 0
            WHEN DATEDIFF(soi.deliverydate, ?) = 0 THEN 1 
            WHEN DATEDIFF(soi.deliverydate, ?) < 0 THEN 2
            WHEN DATEDIFF(soi.deliverydate, ?) BETWEEN 1 AND 10 THEN 3
            ELSE 4
          END,
          soi.deliverydate";
   $stmt = $krfconn->prepare($q); 
$stmt->bind_param(
    "iiisssss",
    $projectid,
    $clientid,
    $productid,
    $psono,
    $baseDate,
    $baseDate,
    $baseDate,
    $baseDate
);
    $stmt->execute();
    $res = $stmt->get_result();

    $response = [];
    while ($row = $res->fetch_assoc()) {
        $response[] = $row;
    }

    echo json_encode($response);

}*/else if ($action === "summaryorders") {
    $returnarray = []; 
    $promiseddt = isset($_POST['promiseddt']) && $_POST['promiseddt'] !== '' 
        ? $_POST['promiseddt']  : null;
        
    $baseDate = $promiseddt ? $promiseddt : date('Y-m-d');
    $projectid = (int)$projectid;  
    $productid =  $_POST['productid'];  
    $orderno = $_POST['orderno'];   
    $clientid = $_POST['clientid']; 
    $q = "SELECT  clnt.client_name, p.ProductName AS productname, p.IsGrafted,
            soi.psono, so.clientid, soi.product_id, soi.deliverydate, soi.quantity,
            DATE_FORMAT(soi.deliverydate, '%d-%m-%Y') AS ddfrmt, 

            IFNULL((
                SELECT SUM(s.qtypertray)
                FROM krf_readytosale_stocks s
                WHERE s.productid = soi.product_id and IFNULL(s.finalclientid, '')=''
                  AND date(s.created_at) <= date(soi.deliverydate)
                  AND s.psono = soi.psono
            ), 0) AS ready_upto_delivery ,

            IFNULL((
                SELECT SUM(s.qtypertray)
                FROM krf_readytosale_stocks s
                WHERE s.productid = soi.product_id and IFNULL(s.finalclientid, '')=''
                  AND date(s.created_at) > date(soi.deliverydate)
                  AND s.psono = soi.psono
            ), 0) AS ready_after_delivery,

            (soi.quantity
             - IFNULL((
                SELECT SUM(s2.qtypertray)
                FROM krf_readytosale_stocks s2
                WHERE s2.productid = soi.product_id and IFNULL(s2.finalclientid, '')=''
                  AND date(s2.created_at) <= date(soi.deliverydate)
                  AND s2.psono = soi.psono
             ), 0)
            ) AS shortage_on_delivery,

            (SELECT MAX(s3.created_at)
             FROM krf_readytosale_stocks s3
             WHERE s3.productid = soi.product_id
               AND s3.psono = soi.psono and IFNULL(s3.finalclientid, '')=''
            ) AS max_created_at,

            IFNULL((
                SELECT SUM(s4.qtypertray)
                FROM krf_readytosale_stocks s4
                WHERE s4.productid = soi.product_id and IFNULL(s4.finalclientid, '')=''
                  AND s4.psono = soi.psono
            ), 0) AS total_readyqty

        FROM krf_sales_order_items soi
        JOIN krf_sales_order so ON so.psono = soi.psono
        JOIN product_master p ON p.ProductID = soi.product_id
        JOIN krf_client_master clnt ON clnt.client_id = so.clientid

        WHERE so.projectid = ?
          AND so.clientid = ?
          AND (IFNULL(so.status,'') = '' OR IFNULL(so.status,'') = 'Pending')

        ORDER BY 
          CASE 
            WHEN soi.product_id = ? AND soi.psono = ? AND DATEDIFF(soi.deliverydate, ?) = 0 THEN 0
            WHEN DATEDIFF(soi.deliverydate, ?) = 0 THEN 1 
            WHEN DATEDIFF(soi.deliverydate, ?) < 0 THEN 2
            WHEN DATEDIFF(soi.deliverydate, ?) BETWEEN 1 AND 10 THEN 3
            ELSE 4
          END,
          soi.deliverydate";
   $stmt = $krfconn->prepare($q); 
$stmt->bind_param(
    "iiisssss",
    $projectid,
    $clientid,
    $productid,
    $psono,
    $baseDate,
    $baseDate,
    $baseDate,
    $baseDate
);
    $stmt->execute();
    $res = $stmt->get_result();

    $response = [];
    while ($row = $res->fetch_assoc()) {
        $response[] = $row;
    }

    echo json_encode($response);
}else if ($action === "allocateorders") { 
    $promiseddt = isset($_POST['promiseddt']) ? trim($_POST['promiseddt']) : '';
    $subqry="";
     $baseDateExpr = "CURDATE()";
    if ($promiseddt){
        //$subqry=" and i.deliverydate = '".$promiseddt."' ";
        $baseDateExpr = "DATE('{$promiseddt}')";
    }
 
    $returnarray = []; 
    $qry = "SELECT date_format(s.created_at,'%d-%m-%Y') as bookingdt ,provision.pickupfrom ,s.status, ifnull(clnt.invoicing_address,'') as del_address, ifnull(clnt.phones,'') as phoneno, ifnull(clnt.client_email,'') as clientemail ,clnt.client_name, p.ProductName AS productname,  p.IsGrafted,  s.clientid,  s.sono, s.psono, i.product_id,  i.quantity as quantity,  i.deliverydate,  DATE_FORMAT(i.deliverydate,'%d-%m-%Y') AS ddfrmt,  DATEDIFF(i.deliverydate, CURDATE()) AS days_to_dispatch  FROM krf_sales_order s JOIN krf_sales_order_items i ON i.sono = s.sono  JOIN product_master p ON p.ProductID = i.product_id JOIN krf_client_master clnt ON clnt.client_id = s.clientid  left join krf_provisional_sales_order provision on provision.clientid = s.clientid and provision.psono= s.psono WHERE s.projectid = {$projectid}  AND (IFNULL(s.status,'') = '' OR IFNULL(s.status,'') = 'Pending') ". $subqry."  ORDER BY  CASE WHEN DATEDIFF(date(i.deliverydate), {$baseDateExpr}) BETWEEN 0 AND 10 THEN 0   WHEN DATEDIFF(date(i.deliverydate), {$baseDateExpr}) <0 then 1 ELSE 2 END,  i.deliverydate  ";
    $result = $krfconn->query($qry);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // $pickuptype =$row['pickupfrom'] ?? '';
            // $pickupadd="";

            $returnarray[] = [
                'status' => $row['status'],
                'client_name' => $row['client_name'],
                'productname' => $row['productname'],
                'IsGrafted' => $row['IsGrafted'],
                'clientid' => $row['clientid'],
                'sono' => $row['sono'],
                'psono' => $row['psono'],
                'product_id' => $row['product_id'],
                'quantity' => (int)$row['quantity'],
                'deliverydate' => $row['deliverydate'] ?? '',
                'ddfrmt' => $row['ddfrmt'] ?? '',
                'days_to_dispatch' => isset($row['days_to_dispatch']) ? (int)$row['days_to_dispatch'] : null,
                'phoneno'  => $row['phoneno'] ?? '',
                'clientemail' => $row['clientemail'] ?? '',
                'pickupfrom'  => $row['pickupfrom'] ?? '',
                'del_address'  => $row['del_address'] ?? '',
                'bookingdt'  => $row['bookingdt'] ?? '',
            ];
        }
        $result->free();
    } 

    // set JSON header and output
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($returnarray, JSON_UNESCAPED_UNICODE); 
}else if ($action === "getreadystock") {
    $returnarray = []; 
    $productid = isset($_POST['productid']) ? trim($_POST['productid']) : '';
     $orderqty= isset($_POST['orderqty']) ? trim($_POST['orderqty']) : '';
    $deliverydate= isset($_POST['deliverydate']) ? trim($_POST['deliverydate']) : '';
    $orderno = isset($_POST['orderno']) ? trim($_POST['orderno']) : ''; 
    $qry = " SELECT  saleord.sono, clnt.client_name, stk.psono,GROUP_CONCAT(stk.id) AS dbsno,  GROUP_CONCAT(stk.qtypertray) AS qtypertray, stk.productid, SUM(stk.qtypertray) AS totalqty, DATE_FORMAT(stk.created_at, '%d-%m-%Y') AS ddformat, DATE(stk.created_at) AS readydate FROM krf_readytosale_stocks stk JOIN krf_client_master clnt ON clnt.client_id = stk.assigntoclientid  join krf_sales_order saleord on saleord.psono = stk.psono  and saleord.projectid=stk.projectid  WHERE IFNULL(stk.finalclientid, '') = ''  AND stk.productid = {$productid} AND stk.projectid = {$projectid} GROUP BY DATE(stk.created_at) ,stk.assigntoclientid, stk.psono   ORDER BY CASE  when stk.psono = '{$orderno}' then 0 WHEN DATEDIFF(DATE(stk.created_at), DATE('{$deliverydate}')) BETWEEN 0 AND 10 THEN 1 ELSE 2 END, (SUM(stk.qtypertray) < {$orderqty}),  ABS(SUM(stk.qtypertray) - {$orderqty}) ASC ";
    $result = $krfconn->query($qry);
    if ($result && $result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
        // $location_Data = getlocation($hrmconn, $projectid, $row['locationid']);
        $returnarray[] = [
            'productid'       => $row['productid'],
            'dbsno'            => $row['dbsno'],
            'totalqty'            => $row['totalqty'],
            'ddformat'            => $row['ddformat'],
            'readydate'            => $row['readydate'],
            'qtypertray'            => $row['qtypertray'],
            'client_name'            => $row['client_name'],
            'psono'            => $row['psono'],
            'sono'            => $row['sono'],
            // 'locationid'       => $row['locationid'],            
            // 'trayid'           => $row['trayid'],
            // 'qtypertray'       => $row['qtypertray'],
            // 'location'         => $row['locationid'],
            // 'psono'            => $row['psono'],
            // 'locationame'      => $location_Data['locationname'],
            // 'locationaddress'  => $location_Data['locationaddress'],
            // 'trayname'  => $row['tray_name'],
        ];
      }
        $result->free();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($returnarray, JSON_UNESCAPED_UNICODE);
}
function getlocation($hrmplusconn, $projectid, $locationid) {
  $sqlqry = "SELECT locationName, LocationAddress   FROM  hrm_AttendanceLocationMaster   WHERE LocationId = '{$locationid}'   AND projectid = {$projectid}";
  $loc_result = $hrmplusconn->query($sqlqry);
  if ($loc_result && $loc_result->num_rows > 0) {
      $row = $loc_result->fetch_assoc();
      $loc_result->free();
      return [
          'locationid'       => $locationid,
          'locationname'     => $row['locationName'],
          'locationaddress'  => $row['LocationAddress']
      ];
  } else {
      return [
          'locationid'       => $locationid,
          'locationname'     => '',
          'locationaddress'  => ''
      ];
  }
} 
?>
