<?php 
date_default_timezone_set("Asia/Kolkata");
require('../../../connection.php');  
header('Content-Type: application/json; charset=utf-8');
$action = isset($_GET['action']) ? $_GET['action'] : ''; 
$projectid = isset($_POST['projectid']) ? trim($_POST['projectid']) : '';
$returnarray = []; 
if ($projectid === '') {
    echo json_encode([]);
    exit;
}else if ($action === "pendinghistoryclient") {
    $returnarray = []; 
    $promiseddt = isset($_POST['promiseddt']) && $_POST['promiseddt'] !== '' 
        ? $_POST['promiseddt']  : null;        
    $deliverydate = $promiseddt ? $promiseddt : date('Y-m-d');
    $projectid = (int)$projectid;  
    
    $productid = isset($_POST['productid']) ? $_POST['productid'] : ""; 
    $psono = isset($_POST['psono']) ? $_POST['psono'] : "";
    $stockorder_no = isset($_POST['stockorder_no']) ? $_POST['stockorder_no'] : "";
    $clientid = isset($_POST['clientid']) ? $_POST['clientid'] : "";
    $subqry="";
    $orderby ="";
    $baseDateExpr = "CURDATE()";
    if ($promiseddt){
        $baseDateExpr = "DATE('{$promiseddt}')";
    } 
    if($clientid){
        $subqry =" and i.clientid ='" .$clientid ."' ";
         $orderby = " ORDER BY CASE 
            WHEN s.psono = '{$psono}' AND i.stockorder_no ='{$stockorder_no}'  AND i.product_id = {$productid} THEN 0
            WHEN s.psono = '{$psono}' AND DATEDIFF(i.deliverydate, '{$deliverydate}') = 0  AND i.product_id = {$productid} THEN 1
            WHEN s.psono = '{$psono}' AND DATEDIFF(i.deliverydate, '{$deliverydate}') = 0  THEN 2
            WHEN DATEDIFF(i.deliverydate, '{$deliverydate}') BETWEEN 1 AND 10   THEN 3 
            ELSE 4 
        END " ;

    }else{
        $orderby =" ORDER BY  CASE WHEN DATEDIFF(date(i.deliverydate), {$baseDateExpr}) BETWEEN 0 AND 10 THEN 0   WHEN DATEDIFF(date(i.deliverydate), {$baseDateExpr}) <0 then 1 ELSE 2 END,  i.deliverydate  ";
    } 

      echo $qry = "SELECT IFNULL(SUM(c.consumed_qty),0) AS consumed_qty, clnt.client_name,ifnull(clnt.phones,'') as phoneno, ifnull(clnt.client_email,'') as clientemail , ifnull(clnt.invoicing_address,'') as invoice_address, ifnull(location.locationid,'') as locationid, ifnull(location.deliveryaddress ,'')  as locationaddress, ifnull(location.deliverycharges,'') as deliverycharges , ifnull(location.transporttype,'') as transporttype,
      provision.delivery_address as provisiondelivery_address,   provision.pickupfrom, 
             p.ProductName AS productname,   
        date_format(s.created_at,'%d-%m-%Y') as bookingdt ,  s.clientid,  s.sono,
        i.psono, i.stockorder_no ,ifnull(i.actionstatus,'') as actionstatus, ifnull(i.status,'') as orderstatus,

        i.product_id,  i.quantity as quantity,  i.deliverydate,  DATE_FORMAT(i.deliverydate,'%d-%m-%Y') AS ddfrmt,  DATEDIFF(i.deliverydate, CURDATE()) AS days_to_dispatch, i.quantity, i.rate, i.taxableamount, i.cgst_percent, i.cgst_amount, i.sgst_percent,   i.igst_percent, i.igst_amount, i.discount_percent, i.discount_amount,   i.tax_amount, i.amount  FROM krf_sales_order s JOIN krf_sales_order_items i ON i.sono = s.sono  JOIN product_master p ON p.ProductID = i.product_id JOIN krf_client_master clnt ON clnt.client_id = s.clientid  left join krf_provisional_sales_order provision on provision.clientid = s.clientid and provision.psono= s.psono 

          left join krf_deliveryaddress_location location on location.clientid = s.clientid and location.psono= s.psono and location.stockorder_no= i.stockorder_no and ifnull(location.productid ,'') =i.product_id

          LEFT JOIN krf_productionorder_consumed c
            ON c.product_id = i.product_id
           AND c.stockorderno =i.stockorder_no
        WHERE  s.projectid = {$projectid}  AND (IFNULL(i.status,'') = '' OR IFNULL(i.status,'') = 'Pending')  ".$subqry . " 
        GROUP BY i.product_id, i.stockorder_no ".$orderby;
    $result = $krfconn->query($qry);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $stkarray=getstockqty($krfconn, $projectid,  $row['clientid'], $row['psono'], $row['stockorder_no'],$row['product_id'], $row['deliverydate']);
            $trayarray=gettray_attributes($krfconn, $stkarray['trayid']) ;
            $advancearray=getadvancepaymet_agtorder($krfconn, $row['clientid'], $row['psono']);   
            $bankarray=getbankdetails($conn, $projectid,$row['clientid']) ; 
            $returnarray[] = [
                'locationid' => $row['locationid'] ?? '',
                'locationaddress' => $row['locationaddress'] ?? '',
                'deliverycharges' => $row['deliverycharges'] ?? '',
                'transporttype' => $row['transporttype'] ?? '',
                'provisiondelivery_address' => $row['provisiondelivery_address'] ?? '',
                'provisiontransporttype' => $row['pickupfrom'] ?? '', 

                'clientid' => $row['clientid'] ?? '',
                'client_name' => $row['client_name'] ?? '',  
                'phoneno'  => $row['phoneno'] ?? '',
                'clientemail' => $row['clientemail'] ?? '',         
                'invoice_address' => $row['invoice_address'] ?? '',

                'productname' => $row['productname'] ?? '', 
                'bookingdt'  => $row['bookingdt'] ?? '',
                'sono' => $row['sono'] ?? '',
                'psono' => $row['psono'] ?? '',
   

                'stockorder_no' => $row['stockorder_no'] ?? '',
                'product_id' => $row['product_id'] ?? '',
                'quantity' => (int)$row['quantity'] ?? '',
                'deliverydate' => $row['deliverydate'] ?? '',
                'ddfrmt' => $row['ddfrmt'] ?? '',
                'days_to_dispatch' => isset($row['days_to_dispatch']) ? (int)$row['days_to_dispatch'] : null, 
                'actionstatus' => $row['actionstatus'] ?? '',
                'orderstatus' => $row['orderstatus'] ?? '', 

                "readyqty"=>$stkarray['readyqty'] ?? '',
                "ready_date"=>$stkarray['readydate'] ?? '',
                "ready_ddformat"=>$stkarray['ready_ddformat'] ?? '',
                "polyhouseplantid"=>$stkarray['polyhouseplantid'] ?? '',

                "trayid"=>$stkarray['trayid'] ?? '',
                "trayqty"=>$stkarray['trayqty'] ?? '',
                "stage"=>$stkarray['stage'] ?? '',

                "tray_code" =>$trayarray['tray_code'] ??'',
                "tray_name" =>$trayarray['tray_name'] ??'',
                "cavity_count"  =>$trayarray['cavity_count'] ??'',
                "cavity_size"   =>$trayarray['cavity_size'] ??'',                
                "quantity"    =>$row['quantity'],
                "rate" =>$row['rate'],                
                "cgst_percent"    =>$row['cgst_percent']  ??'',
                "sgst_percent"    =>$row['sgst_percent']  ??'',
                "sgst_percent"    =>$row['sgst_percent']  ??'',
                "igst_percent"    =>$row['igst_percent']  ??'',
                "discount_percent"    =>$row['discount_percent']  ??'', 

                'bankname'    => $bankarray['bankname'] ??'',
                'accountno'    => $bankarray['accountno'] ??'',
                'ifsccode' => $bankarray['ifsccode'] ??'',
                'branch'  => $bankarray['branch'] ??'',
                'invoicedamount'  =>$advancearray['invoiced'] ??'',
                'receivedamount'    =>$advancearray['amountreceived'] ??'',
                'advanceamount'   =>$advancearray['advance'] ??'' 
    

            ]; 
        } 
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($returnarray, JSON_UNESCAPED_UNICODE);    
}/*else if ($action === "pendingdeliveryorders") { 
    $promiseddt = isset($_POST['promiseddt']) ? trim($_POST['promiseddt']) : '';
    $subqry="";
     $baseDateExpr = "CURDATE()";
    if ($promiseddt){
        //$subqry=" and i.deliverydate = '".$promiseddt."' ";
        $baseDateExpr = "DATE('{$promiseddt}')";
    } 
    $returnarray = [];  
    $qry = "SELECT ifnull(i.actionstatus,'') as actionstatus, ifnull(i.status,'') as orderstatus,  clnt.client_name,ifnull(clnt.phones,'') as phoneno, ifnull(clnt.client_email,'') as clientemail , ifnull(clnt.invoicing_address,'') as invoice_address,  p.ProductName AS productname,  p.IsGrafted, 
        date_format(s.created_at,'%d-%m-%Y') as bookingdt ,  s.clientid,  s.sono,
        i.psono, i.stockorder_no  ,i.product_id,  i.quantity as quantity,  i.deliverydate,  DATE_FORMAT(i.deliverydate,'%d-%m-%Y') AS ddfrmt,  DATEDIFF(i.deliverydate, CURDATE()) AS days_to_dispatch  FROM krf_sales_order s JOIN krf_sales_order_items i ON i.sono = s.sono  JOIN product_master p ON p.ProductID = i.product_id JOIN krf_client_master clnt ON clnt.client_id = s.clientid  WHERE s.projectid = {$projectid}  AND (IFNULL(i.status,'') = '' OR IFNULL(i.status,'') = 'Pending')   ORDER BY  CASE WHEN DATEDIFF(date(i.deliverydate), {$baseDateExpr}) BETWEEN 0 AND 10 THEN 0   WHEN DATEDIFF(date(i.deliverydate), {$baseDateExpr}) <0 then 1 ELSE 2 END,  i.deliverydate  ";
    $result = $krfconn->query($qry);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) { 
            $returnarray[] = [
                // 'delivery_address' => $row['delivery_address'] ?? '',
                // 'pickupfrom' => $row['pickupfrom'] ?? '',
                'client_name' => $row['client_name'] ?? '',  
                'phoneno'  => $row['phoneno'] ?? '',
                'clientemail' => $row['clientemail'] ?? '',         
                'invoice_address' => $row['invoice_address'] ?? '',
                'clientid' => $row['clientid'] ?? '',
                'productname' => $row['productname'] ?? '' ,
                'bookingdt'  => $row['bookingdt'] ?? '',
                'sono' => $row['sono'] ?? '',
                'psono' => $row['psono'] ?? '',
                'stockorder_no' => $row['stockorder_no'] ?? '',
                'product_id' => $row['product_id'] ?? '',
                'orderquantity' => (int)$row['quantity'] ?? '',
                'deliverydate' => $row['deliverydate'] ?? '',
                'ddfrmt' => $row['ddfrmt'] ?? '',
                'days_to_dispatch' => isset($row['days_to_dispatch']) ? (int)$row['days_to_dispatch'] : null,  
                'actionstatus' => $row['actionstatus'] ?? '',
                'orderstatus' => $row['orderstatus'] ?? '',
                
            ];
        }
        $result->free();
    } 
 
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($returnarray, JSON_UNESCAPED_UNICODE); 
}*/
elseif ($action === "getpickuplocation_delivery") {
    $productid     = $_POST['productid'] ?? 0;
    $psono         = $_POST['psono'] ?? '';
    $stockorder_no = $_POST['stockorder_no'] ?? '';
    $clientid = $_POST['clientid'] ?? '';      
    $returnarray = [];
    $sqlqry = "SELECT  ifnull(locationname,'NA') as locationname ,count(id) as nooftrays FROM krf_readytosale_stocks l WHERE projectid = '{$projectid}' and  assigntoclientid='{$clientid}' AND psono='{$psono}' AND stockorder_no='{$stockorder_no}' AND productid='{$productid}'  group by locationname ";
    $loc_result = $krfconn->query($sqlqry);
    if ($loc_result) {
        while ($row = $loc_result->fetch_assoc()) {
            $returnarray[] = $row;
        }
        $loc_result->free();
    }

    echo json_encode($returnarray);
}

else if ($action === "getwarehousestock") {
    $returnarray = []; 
    $productid = isset($_POST['productid']) ? trim($_POST['productid']) : '';
     // $orderqty= isset($_POST['orderqty']) ? trim($_POST['orderqty']) : '';
    $deliverydate= isset($_POST['deliverydate']) ? trim($_POST['deliverydate']) : '';
    $psono = isset($_POST['psono']) ? trim($_POST['psono']) : ''; 
    $stockorder_no = isset($_POST['stockorder_no']) ? trim($_POST['stockorder_no']) : ''; 
    $clientid =isset($_POST['clientid']) ? trim($_POST['clientid']) : ''; 
    $qry = " SELECT  saleord.sono, clnt.client_name, stk.psono,   stk.stockorder_no,   stk.productid, SUM(stk.qtypertray) AS totalqty, DATE_FORMAT(stk.created_at, '%d-%m-%Y') AS ddformat, DATE(stk.created_at) AS readydate FROM krf_readytosale_stocks stk JOIN krf_client_master clnt ON clnt.client_id = stk.assigntoclientid  join krf_sales_order saleord on saleord.psono = stk.psono  and saleord.projectid=stk.projectid  WHERE IFNULL(stk.finalclientid, '') = ''  AND stk.productid = {$productid} AND stk.projectid = {$projectid} and stk.psono='{$psono}' and stk.stockorder_no= '{$stockorder_no}' and stk.assigntoclientid= '{$clientid}' GROUP BY DATE(stk.created_at) ,stk.assigntoclientid, stk.psono ,stk.stockorder_no  ORDER BY CASE  when stk.psono = '{$psono}' and  stk.stockorder_no= '{$stockorder_no}' then 0 when stk.psono = '{$psono}' then 1 WHEN DATEDIFF(DATE(stk.created_at), DATE('{$deliverydate}')) BETWEEN 0 AND 10 THEN 3 ELSE 4 END";
    //, (SUM(stk.qtypertray) < {$orderqty}),  ABS(SUM(stk.qtypertray) - {$orderqty}) ASC 
    $result = $krfconn->query($qry);
    if ($result && $result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) { 
        $returnarray[] = [
            'productid'       => $row['productid'], 
            'totalqty'            => $row['totalqty'],
            'ddformat'            => $row['ddformat'],
            'readydate'            => $row['readydate'], 
            'client_name'            => $row['client_name'],
            'psono'            => $row['psono'],
            'sono'            => $row['sono'], 
            'stockorder_no' => $row['stockorder_no'], 
        ];
      }
        $result->free();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($returnarray, JSON_UNESCAPED_UNICODE);
}else if ($action === "pendinghistoryorders") {
    $returnarray = []; 
    $promiseddt = isset($_POST['promiseddt']) && $_POST['promiseddt'] !== '' 
        ? $_POST['promiseddt']  : null;        
    $deliverydate = $promiseddt ? $promiseddt : date('Y-m-d');
    $projectid = (int)$projectid;  
    $productid =  $_POST['productid'];  
    $psono = $_POST['psono'];    
    $stockorder_no = $_POST['stockorder_no'];    
    $clientid = $_POST['clientid'];  

      $qry = "SELECT ifnull(location.locationid,'') as locationid, ifnull(location.deliveryaddress ,'')  as locationaddress, ifnull(location.deliverycharges,'') as deliverycharges , ifnull(location.transporttype,'') as transporttype,
      provision.delivery_address as provisiondelivery_address,   provision.pickupfrom, 
             p.ProductName AS productname,   
        date_format(s.created_at,'%d-%m-%Y') as bookingdt ,  s.clientid,  s.sono,
        i.psono, i.stockorder_no ,ifnull(i.actionstatus,'') as actionstatus, ifnull(i.status,'') as orderstatus,

        i.product_id,  i.quantity as quantity,  i.deliverydate,  DATE_FORMAT(i.deliverydate,'%d-%m-%Y') AS ddfrmt,  DATEDIFF(i.deliverydate, CURDATE()) AS days_to_dispatch, i.quantity, i.rate, i.taxableamount, i.cgst_percent, i.cgst_amount, i.sgst_percent,   i.igst_percent, i.igst_amount, i.discount_percent, i.discount_amount,   i.tax_amount, i.amount  FROM krf_sales_order s JOIN krf_sales_order_items i ON i.sono = s.sono  JOIN product_master p ON p.ProductID = i.product_id JOIN krf_client_master clnt ON clnt.client_id = s.clientid  left join krf_provisional_sales_order provision on provision.clientid = s.clientid and provision.psono= s.psono 

          left join krf_deliveryaddress_location location on location.clientid = s.clientid and location.psono= s.psono and ifnull(location.productid ,'') =i.product_id
        WHERE s.projectid = {$projectid}  AND (IFNULL(i.status,'') = '' OR IFNULL(i.status,'') = 'Pending')  ORDER BY CASE 
            WHEN s.psono = '{$psono}' AND stockorder_no ='{$stockorder_no}'  AND i.product_id = {$productid} THEN 0
            WHEN s.psono = '{$psono}' AND DATEDIFF(i.deliverydate, '{$deliverydate}') = 0  AND i.product_id = {$productid} THEN 1
            WHEN s.psono = '{$psono}' AND DATEDIFF(i.deliverydate, '{$deliverydate}') = 0  THEN 2
            WHEN DATEDIFF(i.deliverydate, '{$deliverydate}') BETWEEN 1 AND 10   THEN 3 
            ELSE 4 
        END " ;
    $result = $krfconn->query($qry);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $stkarray=getstockqty($krfconn, $projectid, $clientid, $row['psono'], $row['stockorder_no'],$row['product_id'], $row['deliverydate']);
            $trayarray=gettray_attributes($krfconn, $stkarray['trayid']) ;
            $returnarray[] = [
                'locationid' => $row['locationid'] ?? '',
                'locationaddress' => $row['locationaddress'] ?? '',
                'deliverycharges' => $row['deliverycharges'] ?? '',
                'transporttype' => $row['transporttype'] ?? '',
                'provisiondelivery_address' => $row['provisiondelivery_address'] ?? '',
                'provisiontransporttype' => $row['pickupfrom'] ?? '', 

                'clientid' => $row['clientid'] ?? '',
                'productname' => $row['productname'] ?? '', 
                'bookingdt'  => $row['bookingdt'] ?? '',
                'sono' => $row['sono'] ?? '',
                'psono' => $row['psono'] ?? '',
                'stockorder_no' => $row['stockorder_no'] ?? '',
                'product_id' => $row['product_id'] ?? '',
                'quantity' => (int)$row['quantity'] ?? '',
                'deliverydate' => $row['deliverydate'] ?? '',
                'ddfrmt' => $row['ddfrmt'] ?? '',
                'days_to_dispatch' => isset($row['days_to_dispatch']) ? (int)$row['days_to_dispatch'] : null, 
                'actionstatus' => $row['actionstatus'] ?? '',
                'orderstatus' => $row['orderstatus'] ?? '', 

                "readyqty"=>$stkarray['readyqty'] ?? '',
                "ready_date"=>$stkarray['readydate'] ?? '',
                "ready_ddformat"=>$stkarray['ready_ddformat'] ?? '',
                "polyhouseplantid"=>$stkarray['polyhouseplantid'] ?? '',

                "trayid"=>$stkarray['trayid'] ?? '',
                "trayqty"=>$stkarray['trayqty'] ?? '',
                "stage"=>$stkarray['stage'] ?? '',

                "tray_code" =>$trayarray['tray_code'] ??'',
                "tray_name" =>$trayarray['tray_name'] ??'',
                "cavity_count"  =>$trayarray['cavity_count'] ??'',
                "cavity_size"   =>$trayarray['cavity_size'] ??'',                
                "quantity"    =>$row['quantity'],
                "rate" =>$row['rate'],                
                "cgst_percent"    =>$row['cgst_percent']  ??'',
                "sgst_percent"    =>$row['sgst_percent']  ??'',
                "sgst_percent"    =>$row['sgst_percent']  ??'',
                "igst_percent"    =>$row['igst_percent']  ??'',
                "discount_percent"    =>$row['discount_percent']  ??''
            ]; 
        } 
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($returnarray, JSON_UNESCAPED_UNICODE);    
} else if ($action === "getquestionanswer") {
    $returnarray = [];  
    $projectid = (int)$projectid;  
    $productid =  $_POST['productid'];  
    $psono = $_POST['psono'];    
    $stockorder_no = $_POST['stockorder_no'];    
    $clientid = $_POST['clientid']; 
    $questionno = $_POST['questionno']; 

      $sql = "SELECT productname, recipient_name, contact_no,message, communicationby, communication_type,communication_date,question,answer,saleqty FROM krf_tbl_communications   WHERE projectid = {$projectid}  AND productid  =  '{$productid}'  AND psono  =  '{$psono}' AND stockorderno  =  '{$stockorder_no}'  AND clientid  =  '{$clientid}'  and questionno ='{$questionno}' order by id desc limit 1";
    $result = $krfconn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $returnarray[] = $row;

    }    
    if ($result) {
        $result->free();
    }
    echo json_encode($returnarray, JSON_UNESCAPED_UNICODE);  

}/*elseif ($action === "getlocation_delivery") {
    $productid     = $_POST['productid'] ?? 0;
    $psono         = $_POST['psono'] ?? '';
    $stockorder_no = $_POST['stockorder_no'] ?? '';
    $locationid    = 'LOC68b02d33d3cef';//$_POST['locationid'] ?? '';
    $returnarray = [];
    $sqlqry = "SELECT l.LocationName AS locationname,l.LocationAddress AS locationaddress
        FROM hrm_AttendanceLocationMaster l WHERE l.projectid = '{$projectid}' AND l.locationid = '{$locationid}' ";
    $loc_result = $hrmconn->query($sqlqry);
    if ($loc_result) {
        while ($row = $loc_result->fetch_assoc()) {
            $returnarray[] = $row;
        }
        $loc_result->free();
    }

    echo json_encode($returnarray);
}*/








/*else if ($action === 'estimateamount') { 
    $items = json_decode($_POST['items'], true);
    $resultarray = [];
    $grand_total = 0; 
    foreach ($items as $row) {
        $psono        = $row['psono'];
        $product_id   = $row['product_id'];
        $clientid     = $row['clientid'];
        $qty          = (int)$row['qty'];
        $deliverydate = $row['deliverydate'];
        $sql = "SELECT ROUND((amount / quantity), 2) AS rate FROM krf_sales_order_items   WHERE clientid = '{$clientid}'   AND product_id = '{$product_id}'   AND psono = '{$psono}'    AND deliverydate = '{$deliverydate}'   LIMIT 1";
        $result = $krfconn->query($sql);
        $qry_res = $result->fetch_assoc();
        $rate   = (float)$qry_res['rate'];
        $amount = $rate * $qty;   
        $grand_total += $amount;
        $resultarray[] = [
            'psono'      => $psono,
            'clientid'   => $clientid,
            'product_id' => $product_id,
            'rate'       => $rate,
            'qty'        => $qty,
            'productamt' => $amount
        ];
    }
    $paymenthistory=paymenthistory($resultarray, $krfconn);
    $returnarray = [
        "currentbill"    => $resultarray,
        "grand_total"    => $grand_total,
        "paymenthistory" => $paymenthistory,
    ];
    echo json_encode($returnarray ); 
}else if ($action === "tradingquestion") {
      $sql = "SELECT  questionid as id, Question, controltype, controldata  FROM krf_tradingquestion WHERE projectid = $projectid and ifnull(tedit,'') =''" ;
    $res = $krfconn->query($sql);
    $data = [];
    while($row = $res->fetch_assoc()){
        $data[] = $row;
    }
    echo json_encode($data);
}else if ($action === "polyhouselocation") {
    // sanitize inputs 
     $polyhouseplantid = $_POST['polyhouseplantid'];

    $resultarray = []; 
    $sql = "SELECT  p.ProductName, ifnull(sum(stk. qtypertray),0) as stkquantity,poly.locationid,GROUP_CONCAT(DISTINCT stk.trayid) AS trayids,COUNT(stk.trayid) AS totaltrays, poly.polyhouseid FROM krf_readytosale_stocks stk JOIN krf_polyhouse_plant_details poly ON poly.polyhouseplantid = stk.polyhouseplantid  AND poly.productid = stk.productid JOIN product_master p ON p.ProductID = poly.productid  WHERE stk.projectid = {$projectid}  AND stk.polyhouseplantid in ( {$polyhouseplantid})  GROUP BY poly.locationid, poly.productid   ";
    $result = $krfconn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) { 
            $locationarray = getlocation($hrmconn, $projectid, $row['locationid']);
           // $locationaddress = trim($locationarray['locationname'] . ' ' . $locationarray['locationaddress']);
            $locationaddress = trim($locationarray['locationaddress']);
            $resultarray[] = [
                'locationid'      => $row['locationid'],
                'trayids'         => $row['trayids'],        
                'totaltrays'      => (int) $row['totaltrays'],
                'stkquantity'      => (int) $row['stkquantity'],
                'polyhouseid'     => $row['polyhouseid'],
                'ProductName'     => $row['ProductName'],
                'locationaddress' => $locationaddress
            ];
        }
        $result->free();
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($resultarray); 
}else if ($action === "consolidateorder") {
    $returnarray = []; 
    $promiseddt = isset($_POST['promiseddt']) && $_POST['promiseddt'] !== '' 
        ? $_POST['promiseddt']  : null;        
    $deliverydate = $promiseddt ? $promiseddt : date('Y-m-d');
    $projectid = (int)$projectid;  
    $productid =  $_POST['productid'];  
    $psono = $_POST['psono'];    
    $stockorder_no = $_POST['stockorder_no'];    
    $clientid = $_POST['clientid']; 

    $qry = "SELECT provision.delivery_address, provision.pickupfrom, 
            clnt.client_name,ifnull(clnt.phones,'') as phoneno, ifnull(clnt.client_email,'') as clientemail ,
            ifnull(clnt.invoicing_address,'') as invoice_address, 
             p.ProductName AS productname,  p.IsGrafted, 
        date_format(s.created_at,'%d-%m-%Y') as bookingdt ,  s.clientid,  s.sono,
        i.psono, i.stockorder_no ,ifnull(i.status,'') as status,i.product_id,  i.quantity as quantity,  i.deliverydate,  DATE_FORMAT(i.deliverydate,'%d-%m-%Y') AS ddfrmt,  DATEDIFF(i.deliverydate, CURDATE()) AS days_to_dispatch  FROM krf_sales_order s JOIN krf_sales_order_items i ON i.sono = s.sono  JOIN product_master p ON p.ProductID = i.product_id JOIN krf_client_master clnt ON clnt.client_id = s.clientid  left join krf_provisional_sales_order provision on provision.clientid = s.clientid and provision.psono= s.psono WHERE s.projectid = {$projectid}  AND (IFNULL(i.status,'') = '' OR IFNULL(i.status,'') = 'Pending')    AND (
                SELECT IFNULL(SUM(stk2.qtypertray), 0)
                FROM krf_readytosale_stocks stk2 WHERE stk2.productid=i.product_id AND stk2.stockorder_no=i.stockorder_no and stk2.assigntoclientid  =i.clientid    and stk2.psono   =i.psono    
              ) >= i.quantity  ORDER BY CASE 
            
            WHEN s.psono = '{$psono}' AND DATEDIFF(i.deliverydate, '{$deliverydate}') = 0  AND i.product_id = {$productid} THEN 1
            WHEN s.psono = '{$psono}' AND DATEDIFF(i.deliverydate, '{$deliverydate}') = 0  THEN 2
            WHEN DATEDIFF(i.deliverydate, '{$deliverydate}') BETWEEN 1 AND 10   THEN 3 
            ELSE 4 
        END " ;
    $result = $krfconn->query($qry);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $stkarray=getstockqty($krfconn, $projectid, $clientid, $row['psono'], $row['stockorder_no'],$row['product_id'], $row['deliverydate']);
            $trayarray=gettray_attributes($krfconn, $stkarray['trayid']) ;
            $returnarray[] = [
                'delivery_address' => $row['delivery_address'] ?? '',
                'pickupfrom' => $row['pickupfrom'] ?? '',

                'client_name' => $row['client_name'] ?? '',  
                'phoneno'  => $row['phoneno'] ?? '',
                'clientemail' => $row['clientemail'] ?? '',         
                'invoice_address' => $row['invoice_address'] ?? '',

                'clientid' => $row['clientid'] ?? '',

                'productname' => $row['productname'] ?? '',
                'IsGrafted' => $row['IsGrafted'] ?? '',

                'bookingdt'  => $row['bookingdt'] ?? '',
                'sono' => $row['sono'] ?? '',

                'psono' => $row['psono'] ?? '',
                'stockorder_no' => $row['stockorder_no'] ?? '',
                'product_id' => $row['product_id'] ?? '',
                'quantity' => (int)$row['quantity'] ?? '',
                'deliverydate' => $row['deliverydate'] ?? '',
                'ddfrmt' => $row['ddfrmt'] ?? '',
                'days_to_dispatch' => isset($row['days_to_dispatch']) ? (int)$row['days_to_dispatch'] : null, 
                'status' => $row['status'] ?? '',

                "readyqty"=>$stkarray['readyqty'] ?? '',
                "ready_date"=>$stkarray['readydate'] ?? '',
                "ready_ddformat"=>$stkarray['ready_ddformat'] ?? '',
                "polyhouseplantid"=>$stkarray['polyhouseplantid'] ?? '',

                "trayid"=>$stkarray['trayid'] ?? '',
                "trayqty"=>$stkarray['trayqty'] ?? '',

                "tray_code" =>$trayarray['tray_code'] ??'',
                "tray_name" =>$trayarray['tray_name'] ??'',
                "cavity_count"  =>$trayarray['cavity_count'] ??'',
                "cavity_size"   =>$trayarray['cavity_size'] ??'',
            ];

 
        } 
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($returnarray, JSON_UNESCAPED_UNICODE);    
}else if ($action === 'clientledger') { 
    $projectid = (int)$projectid;  
    $clientid =  $_POST['clientid']; 
    $returnarray = []; 
     $qry = "select  actuldt ,date, description, invoice_amount, received_amount ,saletype from (
    

    SELECT invoice_date as actuldt, DATE_FORMAT(invoice_date, '%d-%m-%Y') AS date, invoice_no as description, invoice_amount  ,'' as received_amount ,'Invoice' as saletype  FROM  krf_tbl_invoices  WHERE  projectid ='".$projectid."'  AND client_id ='".$clientid."'
    union all
     SELECT paymentdate as actuldt ,  DATE_FORMAT(paymentdate, '%d-%m-%Y') AS date, utrnumber as description,  '' as invoice_amount,  advanceamount as received_amount , 'Receiving' as saletype   FROM advance_payment_transactions   WHERE  clientid  = '{$clientid}' 

      union all
    union all
    SELECT  invoice_date as actuldt, DATE_FORMAT(invoice_date, '%d-%m-%Y') AS date, concat('Credit Note: ',i.invoice_no)  as description, '' as invoice_amount ,i.invoice_amount as received_amount  , 'Credit Note' as saletype  FROM  krf_tbl_creditinvoices  WHERE   projectid ='".$projectid."'  AND i.client_id ='".$clientid."'  
     ) tblder order by actuldt asc"; 

 
    $result = $krfconn->query($qry);
    while ($row = $result->fetch_assoc()) {         
        $returnarray[] = [
            
            'saletype'      => $row['saletype'],
            'invoice_date'      => $row['invoice_date'], 
            'date'       => $row['date'],
            'description'        => $row['description'],
            'invoice_amount' => $row['invoice_amount'],
            'received_amount' => $row['received_amount'],
        ];
    }
    echo json_encode($returnarray ); 
}







else if ($action === 'paymenthistory') { 
    $projectid = (int)$projectid;  
    $clientid =  $_POST['clientid']; 
    $returnarray = []; 
     $qry = "SELECT advanceamount as amount, utrnumber, paymentdate,invoiceno , date_format(paymentdate,'%d-%m-%Y') as ddpayment  FROM advance_payment_transactions   WHERE  clientid  = '{$clientid}'  order by   paymentdate desc";
        $result = $krfconn->query($qry);
     while ($row = $result->fetch_assoc()) {         
        $returnarray[] = [
            'psono'      => $row['invoiceno'],
            'clientid'   => $clientid, 
            'amount'       => $row['amount'],
            'utrnumber'        => $row['utrnumber'],
            'paymentdate' => $row['paymentdate'],
            'ddpayment' => $row['ddpayment'],
        ];
    }
    echo json_encode($returnarray ); 
}
function getstockqty($conn, $projectid, $clientid, $psono,$stockorder_no, $productid, $expecteddeliverydate) { 
    $expecteddeliverydate = date('Y-m-d', strtotime($expecteddeliverydate));
      $stocksql = "SELECT t.readyqty, t.readydt, t.polyhouseplantid, t.trayid, t.trayqty FROM (
      SELECT SUM(stk.qtypertray) AS readyqty, MAX(stk.created_at) AS readydt , GROUP_CONCAT(distinct stk.polyhouseplantid) as polyhouseplantid, GROUP_CONCAT(stk.trayid) as trayid, GROUP_CONCAT(stk.qtypertray) as trayqty FROM krf_readytosale_stocks stk WHERE stk.projectid = '{$projectid}'  AND stk.assigntoclientid  = '{$clientid}' AND stk.psono     = '{$psono}' AND stk.stockorder_no     = '{$stockorder_no}' AND stk.productid = '{$productid}' AND DATE(stk.deliverydate) = '{$expecteddeliverydate}' ) AS t WHERE IFNULL(t.readyqty, 0) > 0;";
    $stkresult = $conn->query($stocksql);
    if ($stkresult && $stkresult->num_rows > 0) {
        $row = $stkresult->fetch_assoc();
        $readydt = $row['readydt'] ?? null;
        return [
            "readyqty"       => $row['readyqty'] ?? 0,
            "readydate"      => $readydt,
            "ready_ddformat" => $readydt ? date("d-m-Y", strtotime($readydt)) : "",
            "polyhouseplantid" => $row['polyhouseplantid'] ?? "",
            "trayid" => $row['trayid'] ?? "",
            "trayqty" => $row['trayqty'] ?? "",
            "stage" =>  "Trader Warehouse",
        ];
    } 
       $sqlLatest = "SELECT t.stageqty, t.readydt, t.polyhouseplantid, t.trayid, t.trayqty FROM (SELECT GROUP_CONCAT(distinct polyhouseplantid) as polyhouseplantid , SUM(qtypertray) AS stageqty ,workingstatus, COALESCE(trade_warehousedate, DATE_ADD(CURDATE(), INTERVAL 20 DAY))  AS readydt  , GROUP_CONCAT(tray_id) as trayid, GROUP_CONCAT(qtypertray) as trayqty FROM krf_polyhouse_plant_details  WHERE projectid = '{$projectid}'    AND psono     = '{$psono}' AND stockorder_no     = '{$stockorder_no}' AND productid = '{$productid}' and ifnull(workingstatus,'') <> 'ReadyToSale' order by polyhouseplantid desc  LIMIT 1) AS t WHERE IFNULL(t.stageqty, 0) > 0;";
    $resLatest = $conn->query($sqlLatest);
    if ($resLatest && $resLatest->num_rows > 0) {
        $rowLatest = $resLatest->fetch_assoc();
        $expectedreadydt = $rowLatest['readydt'] ?? null;
        return [
            "readyqty"       => $rowLatest['stageqty'] ?? 0,
            "readydate"      => $expectedreadydt,
            "ready_ddformat" => $expectedreadydt ? date("d-m-Y", strtotime($expectedreadydt)) : "",
            "polyhouseplantid" => $rowLatest['polyhouseplantid'] ?? "",
            "trayid" => $rowLatest['trayid'] ?? "",
            "trayqty" => $rowLatest['trayqty'] ?? "",
            "stage" =>  $rowLatest['workingstatus'] ?? ""
        ];
    }
       $sqlLatest = "SELECT t.stageqty, t.readydt, '' as polyhouseplantid, t.trayid, t.stageqty FROM (SELECT SUM(traycavity) AS stageqty , 'Pre-sowing' as workingstatus, COALESCE(trade_warehousedate, DATE_ADD(CURDATE(), INTERVAL 20 DAY)) AS readydt , GROUP_CONCAT(trayid) as trayid, GROUP_CONCAT(traycavity) as trayqty FROM krf_polyhouse_sowingplant_details  WHERE     projectid = '{$projectid}'    AND psono     = '{$psono}' AND stockorder_no     = '{$stockorder_no}' AND productid = '{$productid}'   and ifnull(workingstatus,'') ='SowingInProcess'   LIMIT 1) AS t WHERE IFNULL(t.stageqty, 0) > 0;";
    $resLatest = $conn->query($sqlLatest);
    if ($resLatest && $resLatest->num_rows > 0) {
        $rowLatest = $resLatest->fetch_assoc();
        $expectedreadydt = $rowLatest['readydt'] ?? null;
        return [
            "readyqty"       => $rowLatest['stageqty'] ?? 0,
            "readydate"      => $expectedreadydt,
            "ready_ddformat" => $expectedreadydt ? date("d-m-Y", strtotime($expectedreadydt)) : "",
            "polyhouseplantid" => $rowLatest['polyhouseplantid'] ?? "",
            "trayid" => $rowLatest['trayid'] ?? "",
            "trayqty" => $rowLatest['trayqty'] ?? "",
            "stage" =>  $rowLatest['workingstatus'] ?? ""
        ];
    }  
    return [
        "readyqty"       => "",
        "readydate"      => "",
        "ready_ddformat" => "",
        "polyhouseplantid" => "",
        "trayid" =>  "",
        "trayqty" => "",
         "stage" => "Not yet started"         
    ];
} 

 


function paymenthistory($orderlist, $conn) { 
    // Make unique list of (psono, clientid)
    $unique = [];
    foreach ($orderlist as $row) {
        $key = $row['psono'] . '_' . $row['clientid'];
        if (!isset($unique[$key])) {
            $unique[$key] = [
                'psono'    => $row['psono'],
                'clientid' => $row['clientid']
            ];
        }
    }
    $advancepayment = [];
    foreach ($unique as $u) {
        $psono    = $u['psono'];
        $clientid = $u['clientid'];
        $qry = "SELECT adv.advanceamount, adv.utrnumber, adv.paymentdate FROM advance_payment_transactions adv  WHERE adv.invoiceno = '{$psono}'   AND adv.clientid  = '{$clientid}' ";
        $result = $conn->query($qry);
        if ($result && $result->num_rows > 0) { 
            while ($row = $result->fetch_assoc()) {
                $advancepayment[] = [
                    'psono'         => $psono,
                    'clientid'      => $clientid,
                    'amount' => $row['advanceamount'],
                    'utrnumber'     => $row['utrnumber'],
                    'paymentdate'   => $row['paymentdate']
                ];
            }
        }
    }    
    return $advancepayment;
}


function getlocation($hrmplusconn, $projectid, $locationid)
{
    // sanitize inputs for safety
    $projectid = intval($projectid);
    $locationid = $hrmplusconn->real_escape_string($locationid);

    $sqlqry = " SELECT locationName, LocationAddress FROM hrm_AttendanceLocationMaster  WHERE LocationId = '{$locationid}'  AND projectid = {$projectid}  LIMIT 1 ";
    $loc_result = $hrmplusconn->query($sqlqry);

    if ($loc_result && $loc_result->num_rows > 0) {
        $row = $loc_result->fetch_assoc();
        $loc_result->free();
        return [
            'locationid'      => $locationid,
            'locationname'    => $row['locationName'] ?? '',
            'locationaddress' => $row['LocationAddress'] ?? ''
        ];
    } else {
        return [
            'locationid'      => $locationid,
            'locationname'    => '',
            'locationaddress' => ''
        ];
    }
}
function gettray_attributes($conn, $trayid) {

    // Default return structure
    $default = [
        'tray_code'    => '',
        'tray_name'    => '',
        'cavity_count' => '',
        'cavity_size'  => ''
    ];

    if (empty($trayid)) {
        return $default;
    }

    // Sanitize tray IDs (convert string like "1,2,3" into safe integers only)
    $ids = array_filter(array_map('intval', explode(',', $trayid)));
    if (empty($ids)) {
        return $default;
    }

    $idList = implode(',', $ids);
    $sqlqry = "SELECT 
            GROUP_CONCAT(DISTINCT tray_code) AS tray_code,
            GROUP_CONCAT(DISTINCT tray_name) AS tray_name,
            GROUP_CONCAT(DISTINCT cavity_count) AS cavity_count,
            GROUP_CONCAT(DISTINCT cavity_size) AS cavity_size
        FROM krf_tray_master
        WHERE tray_id IN ($idList)";
    $result = $conn->query($sqlqry);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $result->free();
        return [
            'tray_code'    => $row['tray_code'] ?? '',
            'tray_name'    => $row['tray_name'] ?? '',
            'cavity_count' => $row['cavity_count'] ?? '',
            'cavity_size'  => $row['cavity_size'] ?? ''
        ];
    }

    return $default;
}

function getalllocation($hrmplusconn, $projectid) { 
    $projectid = intval($projectid); 
    $sqlqry = " SELECT locationName, LocationAddress,LocationId FROM hrm_AttendanceLocationMaster  WHERE  projectid = {$projectid}  LIMIT 1 ";
    $loc_result = $hrmplusconn->query($sqlqry);

    if ($loc_result && $loc_result->num_rows > 0) {
        $row = $loc_result->fetch_assoc();
        $loc_result->free();
        return [
            'locationid'      =>$row['LocationId'] ?? '',
            'locationname'    => $row['locationName'] ?? '',
            'locationaddress' => $row['LocationAddress'] ?? ''
        ];
    } else {
        return [
            'locationid'      => '',
            'locationname'    => '',
            'locationaddress' => ''
        ];
    }
}*/
function getstockqty($conn, $projectid, $clientid, $psono,$stockorder_no, $productid, $expecteddeliverydate) { 
    $expecteddeliverydate = date('Y-m-d', strtotime($expecteddeliverydate));
      $stocksql = "SELECT t.readyqty, date(t.readydt) as readydt , t.polyhouseplantid, t.trayid, t.trayqty FROM (
      SELECT SUM(stk.qtypertray) AS readyqty, MAX(stk.created_at) AS readydt , GROUP_CONCAT(distinct stk.polyhouseplantid) as polyhouseplantid, GROUP_CONCAT(stk.trayid) as trayid, GROUP_CONCAT(stk.qtypertray) as trayqty FROM krf_readytosale_stocks stk WHERE stk.projectid = '{$projectid}'  AND stk.assigntoclientid  = '{$clientid}' AND stk.psono     = '{$psono}' AND stk.stockorder_no     = '{$stockorder_no}' AND stk.productid = '{$productid}' AND DATE(stk.deliverydate) = '{$expecteddeliverydate}' ) AS t WHERE IFNULL(t.readyqty, 0) > 0;";
    $stkresult = $conn->query($stocksql);
    if ($stkresult && $stkresult->num_rows > 0) {
        $row = $stkresult->fetch_assoc();
        $readydt = $row['readydt'] ?? null;
        return [
            "readyqty"       => $row['readyqty'] ?? 0,
            "readydate"      => $readydt,
            "ready_ddformat" => $readydt ? date("d-m-Y", strtotime($readydt)) : "",
            "polyhouseplantid" => $row['polyhouseplantid'] ?? "",
            "trayid" => $row['trayid'] ?? "",
            "trayqty" => $row['trayqty'] ?? "",
            "stage" =>  "Trader Warehouse",
        ];
    } 
    /*$sqlLatest = "SELECT t.stageqty, t.readydt, t.polyhouseplantid, t.trayid, t.trayqty FROM (SELECT GROUP_CONCAT(distinct polyhouseplantid) as polyhouseplantid , SUM(qtypertray) AS stageqty ,workingstatus, COALESCE(trade_warehousedate, DATE_ADD(CURDATE(), INTERVAL 20 DAY))  AS readydt  , GROUP_CONCAT(tray_id) as trayid, GROUP_CONCAT(qtypertray) as trayqty FROM krf_polyhouse_plant_details  WHERE projectid = '{$projectid}'    AND psono     = '{$psono}' AND stockorder_no     = '{$stockorder_no}' AND productid = '{$productid}' and ifnull(workingstatus,'') <> 'ReadyToSale' order by polyhouseplantid desc  LIMIT 1) AS t WHERE IFNULL(t.stageqty, 0) > 0;";*/

    $sqlLatest = "SELECT t.stageqty, t.readydt, t.polyhouseplantid, t.trayid, t.trayqty ,t.workingstatus FROM (SELECT GROUP_CONCAT(distinct polyhouseplantid) as polyhouseplantid , SUM(qtypertray) AS stageqty ,workingstatus, COALESCE(trade_warehousedate, DATE_ADD(CURDATE(), INTERVAL 20 DAY))  AS readydt  , GROUP_CONCAT(tray_id) as trayid, GROUP_CONCAT(qtypertray) as trayqty FROM krf_polyhouse_plant_details  WHERE projectid = '{$projectid}'    AND psono     = '{$psono}' AND stockorder_no     = '{$stockorder_no}' AND productid = '{$productid}' and  health_status = 'Healthy' AND status IN ('Polyhouse','Grafting','Healing','Hardening','Clip Stick Removal')  order by polyhouseplantid desc  LIMIT 1) AS t WHERE IFNULL(t.stageqty, 0) > 0;";

    $resLatest = $conn->query($sqlLatest);
    if ($resLatest && $resLatest->num_rows > 0) {
        $rowLatest = $resLatest->fetch_assoc();
        $expectedreadydt = $rowLatest['readydt'] ?? null;
        return [
            "readyqty"       => $rowLatest['stageqty'] ?? 0,
            "readydate"      => $expectedreadydt,
            "ready_ddformat" => $expectedreadydt ? date("d-m-Y", strtotime($expectedreadydt)) : "",
            "polyhouseplantid" => $rowLatest['polyhouseplantid'] ?? "",
            "trayid" => $rowLatest['trayid'] ?? "",
            "trayqty" => $rowLatest['trayqty'] ?? "",
            "stage" =>  $rowLatest['workingstatus'] ?? ""
        ];
    }
   /*$sqlLatest = "SELECT t.stageqty, t.readydt, '' as polyhouseplantid, t.trayid, t.stageqty FROM (SELECT SUM(traycavity) AS stageqty , 'Pre-sowing' as workingstatus, COALESCE(trade_warehousedate, DATE_ADD(CURDATE(), INTERVAL 20 DAY)) AS readydt , GROUP_CONCAT(trayid) as trayid, GROUP_CONCAT(traycavity) as trayqty FROM krf_polyhouse_sowingplant_details  WHERE     projectid = '{$projectid}'    AND psono     = '{$psono}' AND stockorder_no     = '{$stockorder_no}' AND productid = '{$productid}'   and ifnull(workingstatus,'') ='SowingInProcess'   LIMIT 1) AS t WHERE IFNULL(t.stageqty, 0) > 0;";*/
     $sqlLatest = "SELECT t.stageqty, t.readydt, '' as polyhouseplantid, t.trayid, t.stageqty, t.workingstatus FROM (SELECT SUM(traycavity) AS stageqty ,   workingstatus, COALESCE(trade_warehousedate, DATE_ADD(CURDATE(), INTERVAL 20 DAY)) AS readydt , GROUP_CONCAT(trayid) as trayid, GROUP_CONCAT(traycavity) as trayqty FROM krf_polyhouse_sowingplant_details  WHERE     projectid = '{$projectid}'    AND psono     = '{$psono}' AND stockorder_no     = '{$stockorder_no}' AND productid = '{$productid}'   and health_status = 'Healthy'   AND status IN ('Sowing','Germination')    LIMIT 1) AS t WHERE IFNULL(t.stageqty, 0) > 0;";

    $resLatest = $conn->query($sqlLatest);
    if ($resLatest && $resLatest->num_rows > 0) {
        $rowLatest = $resLatest->fetch_assoc();
        $expectedreadydt = $rowLatest['readydt'] ?? null;
        return [
            "readyqty"       => $rowLatest['stageqty'] ?? 0,
            "readydate"      => $expectedreadydt,
            "ready_ddformat" => $expectedreadydt ? date("d-m-Y", strtotime($expectedreadydt)) : "",
            "polyhouseplantid" => $rowLatest['polyhouseplantid'] ?? "",
            "trayid" => $rowLatest['trayid'] ?? "",
            "trayqty" => $rowLatest['trayqty'] ?? "",
            "stage" =>  $rowLatest['workingstatus'] ?? ""
        ];
    }  
    return [
        "readyqty"       => "",
        "readydate"      => "",
        "ready_ddformat" => "",
        "polyhouseplantid" => "",
        "trayid" =>  "",
        "trayqty" => "",
         "stage" => "Not yet started"         
    ];
}
function gettray_attributes($conn, $trayid) { 
    $default = [
        'tray_code'    => '',
        'tray_name'    => '',
        'cavity_count' => '',
        'cavity_size'  => ''
    ];

    if (empty($trayid)) {
        return $default;
    }
    // Sanitize tray IDs (convert string like "1,2,3" into safe integers only)
    $ids = array_filter(array_map('intval', explode(',', $trayid)));
    if (empty($ids)) {
        return $default;
    }

    $idList = implode(',', $ids);
    $sqlqry = "SELECT 
            GROUP_CONCAT(DISTINCT tray_code) AS tray_code,
            GROUP_CONCAT(DISTINCT tray_name) AS tray_name,
            GROUP_CONCAT(DISTINCT cavity_count) AS cavity_count,
            GROUP_CONCAT(DISTINCT cavity_size) AS cavity_size
        FROM krf_tray_master
        WHERE tray_id IN ($idList)";
    $result = $conn->query($sqlqry);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $result->free();
        return [
            'tray_code'    => $row['tray_code'] ?? '',
            'tray_name'    => $row['tray_name'] ?? '',
            'cavity_count' => $row['cavity_count'] ?? '',
            'cavity_size'  => $row['cavity_size'] ?? ''
        ];
    }

    return $default;
} 
function getbankdetails($conn, $projectid,$clientid) { 
    $default = [
        'bankname'    => 'Chhattisgarh Rajya Gramin Bank',
        'accountno'    => '77054448500',
        'ifsccode' => 'CRGB0006002',
        'branch'  => 'AMBIKAPUR'
    ];
    

 
 return $default;
     
    // $sqlqry = "SELECT  *  FROM bankmaster  WHERE projectid ={$idList} and clientid='{clientid}'";
    // $result = $conn->query($sqlqry);
    // if ($result && $result->num_rows > 0) {
    //     $row = $result->fetch_assoc();
    //     $result->free();
    //     return [
    //         'bankname'    => $row['bankname'] ?? '',
    //         'accountno'    => $row['accountno'] ?? '',
    //         'ifsccode' => $row['ifsccode'] ?? '',
    //         'branch'  => $row['branch'] ?? ''
    //     ];
    // }

    // return $default;
}
function getadvancepaymet_agtorder($conn, $clientid, $psono){
    $clientid = $conn->real_escape_string($clientid);
    $psono    = $conn->real_escape_string($psono);
    $invoiceamount = 0.0;
    $receivedamt   = 0.0;
    /* -------- INVOICED AMOUNT -------- */
    $sql = "SELECT IFNULL(SUM(amount), 0) + IFNULL(SUM(freight_charges), 0) AS invoiced
        FROM krf_sales_order_items WHERE clientid = '{$clientid}' AND psono = '{$psono}'
          AND status = 'invoiced' AND IFNULL(invoiceno, '') <> ''  ";
    if ($res = $conn->query($sql)) {
        $row = $res->fetch_assoc();
        $invoiceamount = (float)$row['invoiced'];
        $res->free();
    }
    $sql = "SELECT IFNULL(SUM(advanceamount), 0) AS amountreceived FROM advance_payment_transactions WHERE clientid = '{$clientid}' AND invoiceno = '{$psono}' ";

    if ($res = $conn->query($sql)) {
        $row = $res->fetch_assoc();
         $receivedamt = (float)$row['amountreceived'];
        $res->free();
    } 
    $advance = $receivedamt-$invoiceamount ;
    return [
        'invoiced'        => $invoiceamount,
        'amountreceived' => $receivedamt,
        'advance'         => $advance
    ];
}


?>
