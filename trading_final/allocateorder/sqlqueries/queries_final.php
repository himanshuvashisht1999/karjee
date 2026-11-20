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

      $qry = "SELECT distinct clnt.client_name,ifnull(clnt.phones,'') as phoneno, ifnull(clnt.client_email,'') as clientemail , ifnull(clnt.invoicing_address,'') as invoice_address, ifnull(location.locationid,'') as locationid, ifnull(location.deliveryaddress ,'')  as locationaddress, ifnull(location.deliverycharges,'') as deliverycharges , ifnull(location.transporttype,'') as transporttype,
      provision.delivery_address as provisiondelivery_address,   provision.pickupfrom, 
             p.ProductName AS productname,   
        date_format(s.created_at,'%d-%m-%Y') as bookingdt ,  s.clientid,  s.sono,
        i.psono, i.stockorder_no ,ifnull(i.actionstatus,'') as actionstatus, ifnull(i.status,'') as orderstatus,

        i.product_id,  i.quantity as quantity,  i.deliverydate,  DATE_FORMAT(i.deliverydate,'%d-%m-%Y') AS ddfrmt,  DATEDIFF(i.deliverydate, CURDATE()) AS days_to_dispatch, i.quantity, i.rate, i.taxableamount, i.cgst_percent, i.cgst_amount, i.sgst_percent,   i.igst_percent, i.igst_amount, i.discount_percent, i.discount_amount,   i.tax_amount, i.amount  FROM krf_sales_order s JOIN krf_sales_order_items i ON i.sono = s.sono  JOIN product_master p ON p.ProductID = i.product_id JOIN krf_client_master clnt ON clnt.client_id = s.clientid  left join krf_provisional_sales_order provision on provision.clientid = s.clientid and provision.psono= s.psono 

          left join krf_deliveryaddress_location location on location.clientid = s.clientid and location.psono= s.psono and location.stockorder_no= i.stockorder_no and ifnull(location.productid ,'') =i.product_id
        WHERE  s.projectid = {$projectid}  AND (IFNULL(i.status,'') = '' OR IFNULL(i.status,'') = 'Pending')  ".$subqry . " ".$orderby;
    $result = $krfconn->query($qry);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $stkarray=getstockqty($krfconn, $projectid,  $row['clientid'], $row['psono'], $row['stockorder_no'],$row['product_id'], $row['deliverydate']);
           // $trayarray=gettray_attributes($krfconn, $stkarray['trayid']) ;
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

                "tray_code" =>'',//$trayarray['tray_code'] ??'',
                "tray_name" =>'',//$trayarray['tray_name'] ??'',
                "cavity_count"  =>'',//$trayarray['cavity_count'] ??'',
                "cavity_size"   =>'',//$trayarray['cavity_size'] ??'',                
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
} 
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
            //$trayarray=gettray_attributes($krfconn, $stkarray['trayid']) ;
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

                "tray_code" =>'',//$trayarray['tray_code'] ??'',
                "tray_name" =>'',//$trayarray['tray_name'] ??'',
                "cavity_count"  =>'',//$trayarray['cavity_count'] ??'',
                "cavity_size"   =>'',//$trayarray['cavity_size'] ??'',                
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

} 
function getstockqty($conn, $projectid, $clientid, $psono,$stockorder_no, $productid, $expecteddeliverydate) { 
    $consumedothe=0;//consumedqty_otherorder($conn, $stockorder_no, $productid, $psono);

    $stagestatus=chktradehousestage($conn, $projectid,$stockorder_no, $productid, $psono,$consumedothe);
    if(count($stagestatus)>0) {
        return $stagestatus;
    }


    $stagestatus=chkpolyhousestage($conn, $projectid, $stockorder_no, $productid, $psono, $consumedothe);
    if(count($stagestatus)>0) {
        return $stagestatus;
    }

    $stagestatus=chksowingstage($conn, $projectid, $stockorder_no, $productid, $psono, $consumedothe) ;
    if(count($stagestatus)>0) {
        return $stagestatus;
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
function consumedqty_otherorder($conn, $stockorder_no, $productid, $psono) {
    $consumedqty = 0;
    $sql = "
        SELECT IFNULL(SUM(consumed_qty), 0) AS consumed
        FROM krf_productionorder_consumed
        WHERE stockorderno = ?
          AND psono <> ?
          AND product_id = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $stockorder_no, $psono, $productid);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $row = $res->fetch_assoc()) {
        $consumedqty = (int)$row['consumed'];
    }
    return $consumedqty;
}
function chktradehousestage($conn, $projectid,$stockorder_no, $productid, $psono,$consumedothe){

    $sql = " SELECT   (SUM(stk.qtypertray) - ?) AS readyqty, MAX(stk.created_at) AS readydt,    GROUP_CONCAT(distinct stk.qtypertray) AS trayqty
        FROM krf_readytosale_stocks stk
        WHERE stk.projectid = ?
          AND stk.stockorder_no = ?
          AND stk.productid = ? ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "isii",
        $consumedothe,
        $projectid,
        $stockorder_no,
        $productid
    );

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
        if ((int)$row['readyqty'] > 0) {
            $readydt = $row['readydt'] ?? null;

            return [
                "readyqty"       => (int)$row['readyqty'],
                "readydate"      => $readydt,
                "ready_ddformat" => $readydt ? date("d-m-Y", strtotime($readydt)) : "",
                "polyhouseplantid" => "",
                "trayid" => "",//$row['trayid'] ?? "",
                "trayqty" => $row['trayqty'] ?? "",
                "stage" => "Trader Warehouse"
            ];
        }
    }
     return [];


}
function chkpolyhousestage($conn, $projectid, $stockorder_no, $productid, $psono, $consumedothe) {
    $sql = " SELECT  stk.status,
            (SUM(stk.qtypertray) - ?) AS readyqty,
            MAX(stk.created_at) AS readydt,
            GROUP_CONCAT(stk.qtypertray) AS trayqty
        FROM krf_polyhouse_plant_details stk
        WHERE stk.projectid = ?
          AND stk.stockorder_no = ?
          AND stk.productid = ?
          AND stk.health_status = 'Healthy'
          AND stk.status IN ('Polyhouse','Grafting','Healing','Hardening','Clip Stick Removal')   and stk.roottype='Root'
        GROUP BY stk.status
        ORDER BY MAX(stk.polyhouseplantid) DESC
        LIMIT 1   ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "isii",
        $consumedothe,
        $projectid,
        $stockorder_no,
        $productid
    );

    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {

        if ((int)$row['readyqty'] > 0) {
            $readydt = $row['readydt'] ?? null;

            return [
                "readyqty" => (int)$row['readyqty'],
                "readydate" => $readydt,
                "ready_ddformat" => $readydt ? date("d-m-Y", strtotime($readydt)) : "",
                "polyhouseplantid" => "",
                "trayid" => "",
                "trayqty" => $row['trayqty'] ?? "",
                "stage" => $row['status'] ?? ""
            ];
        }
    }

     return [];
}

function chksowingstage($conn, $projectid, $stockorder_no, $productid, $psono, $consumedothe) {
      $sql = " SELECT  stk.status,
            (SUM(stk.traycavity) - ?) AS readyqty,
            MAX(stk.created_at) AS readydt,
             (stk.traycavity) AS trayqty
        FROM krf_polyhouse_sowingplant_details stk
        WHERE stk.projectid = ?
          AND stk.stockorder_no = ?
          AND stk.productid = ?
          AND stk.health_status = 'Healthy'
          AND stk.status IN ('Sowing','Germination')  and stk.roottype='Root'
        GROUP BY stk.status
        ORDER BY MAX(stk.sowingplantid) DESC
        LIMIT 1   ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "isii",
        $consumedothe,
        $projectid,
        $stockorder_no,
        $productid
    );

    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {

        if ((int)$row['readyqty'] > 0) {
            $readydt = $row['readydt'] ?? null;

            return [
                "readyqty" => (int)$row['readyqty'],
                "readydate" => $readydt,
                "ready_ddformat" => $readydt ? date("d-m-Y", strtotime($readydt)) : "",
                "polyhouseplantid" => "",
                "trayid" => "",
                "trayqty" => $row['trayqty'] ?? "",
                "stage" => $row['status'] ?? ""
            ];
        }
    }

    return [
        "readyqty" => 0,
        "readydate" => "",
        "ready_ddformat" => "",
        "polyhouseplantid" => "",
        "trayid" => "",
        "trayqty" => "",
        "stage" => "Not yet started"
    ];
}

 



 


?>
