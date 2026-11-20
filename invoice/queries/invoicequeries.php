<?php
date_default_timezone_set("Asia/Kolkata");
require('../../connection.php');

header('Content-Type: application/json; charset=utf-8');
$action = $_GET['action'];
$projectid = $_POST['projectid'];  
if (!$projectid  ) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request parameters.'
    ]);
    exit;
}
if($action == "getclientdata"){  
    $sql = "SELECT clnt.client_id, clnt.client_name, ifnull(clnt.invoicing_address ,'' ) as invoicing_address, ifnull(clnt.phones ,'' ) as phones, ifnull(clnt.client_email  ,'' ) as client_email, ifnull(clnt.statename ,'' ) as statename, ifnull(clnt.districtname  ,'' ) as  districtname FROM krf_client_master clnt join  krf_sales_order_items item on item.clientid = clnt.client_id  where (ifnull(item.status,'')  ='Pending' or ifnull(item.status,'')  ='' ) and ifnull(clnt.client_id,'') <>''   and ifnull(clnt.client_name,'') <>'' and ifnull(clnt.client_id,'') <>''   and ifnull(clnt.client_name,'') <>'' and item.projectid =? group by clnt.client_id  order by  clnt.client_name "; 
    $returnarray = []; 
    $stmt = $krfconn->prepare($sql);
    $stmt->bind_param("s", $projectid);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) { 
        while ($row = $result->fetch_assoc()) {
            $returnarray[] = $row;
        }

        echo json_encode([
            'status' => 'success', 
            'data' => $returnarray
        ]);
    }else{
        echo json_encode([
            'status' => 'nodata',
            'data' => $returnarray
        ]);

    }  
 
} else if($action == "getorderno_forinv"){
    $clientid = $_POST['clientid'];  
     //$sql="SELECT  main.sono, item.psono from  krf_sales_order main join  krf_sales_order_items item on item.psono=main.psono and item.clientid=main.clientid   where (ifnull(item.status,'')  ='Pending' or ifnull(item.status,'')  ='' )  and item.projectid =?  and item.clientid =? group by main.sono, item.psono order by item.deliverydate";
    $sql="SELECT  item.sono, item.psono from   krf_sales_order_items item  where (ifnull(item.status,'')  ='Pending' or ifnull(item.status,'')  ='' )  and item.projectid =?  and item.clientid =? group by item.sono, item.psono order by item.deliverydate";
    $returnarray = []; 
    $stmt = $krfconn->prepare($sql);
    $stmt->bind_param("ss", $projectid,$clientid);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) { 
        while ($row = $result->fetch_assoc()) {
            $returnarray[] = $row;
        }
        echo json_encode([
            'status' => 'success', 
            'data' => $returnarray
        ]);
    }else{
        echo json_encode([
            'status' => 'nodata',
            'data' => $returnarray
        ]);

    } 
} else if($action == "getproduct_forinv"){
    $clientid = $_POST['clientid'];  
    $psono = $_POST['psono'];
    $sono = $_POST['sono'];
    $sql="SELECT distinct ifnull(addl.deliveryaddress,'') as deliveryaddress, ifnull(addl.vehicleno,'') as vehicleno,ifnull(addl.drivername,'') as drivername,ifnull(addl.drivercontactno,'') as drivercontactno,ifnull(fr.freight_amount,'') as freight_amount,ifnull(fr.freight_discountpercent,'') as freight_discountpercent,ifnull(fr.payable_freightamount,'') as payable_freightamount ,
    IFNULL(av.availableqty, '') AS availableqty, p.productname, item.sono, item.psono,item.stockorder_no, item.product_id, item.quantity , item.rate, item.discount_percent , date_format(item.deliverydate,'%d-%m-%Y') as deliverydate from   krf_sales_order_items item   join product_master p on p.productid = item.product_id 
        LEFT JOIN ( 
            SELECT  productid, stockorder_no,  SUM(qtypertray) AS availableqty FROM krf_readytosale_stocks  GROUP BY productid, stockorder_no ) av 
        ON av.productid = item.product_id
       AND av.stockorder_no = item.stockorder_no left join krf_freightcharges fr on fr.clientid=item.clientid and fr.psono=item.psono left join krf_deliveryaddress_location addl on addl.clientid=item.clientid and addl.psono=item.psono
      where (ifnull(item.status,'')  ='Pending' or ifnull(item.status,'')  ='' )  and item.projectid =?  and item.clientid =? and item.sono = ? and  item.psono = ? order by item.deliverydate , p.productname"; 
    $returnarray = []; 
    $stmt = $krfconn->prepare($sql);
    $stmt->bind_param("ssss", $projectid,$clientid,$sono,$psono);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) { 
        while ($row = $result->fetch_assoc()) {
            $returnarray[] = $row;
        }
        echo json_encode([
            'status' => 'success', 
            'data' => $returnarray
        ]);
    }else{
        echo json_encode([
            'status' => 'nodata',
            'data' => $returnarray
        ]);

    } 
}


 

