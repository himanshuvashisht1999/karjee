<?php 
  
date_default_timezone_set("Asia/Kolkata");

require('../../../connection.php');
require('savelogcommunication.php');

if (!isset($_POST['allocationData'])) {
    echo "Invalid request";
    exit;
}

$allocationData = json_decode($_POST['allocationData'], true);

if (!is_array($allocationData) || empty($allocationData)) {
    echo "No allocation data received";
    exit;
}

$projectid           = trim($_POST['projectid'] ?? '');
$recipientname       = trim($_POST['recipientname'] ?? '');
$contactno           = trim($_POST['contactno'] ?? '');
$communicationby     = trim($_POST['communicationby'] ?? '');
$communication_date  = trim($_POST['communication_date'] ?? date('Y-m-d'));
$questionno          = trim($_POST['questionno'] ?? '');
$question            = trim($_POST['question'] ?? '');
$questiontype        = '';

$trader = gettraderid($krfconn, $projectid);
if (!$trader['client_id']) {
    echo "Trader not found";
    exit;
}

$krfconn->begin_transaction();

try {

    foreach ($allocationData as $row) {

        $productid      = (int)$row['productid'];
        $productname    = trim($row['productname']);
        $psono          = trim($row['psono']);
        $sono           = trim($row['sono']);
        $stockorder_no  = trim($row['stockorder_no']);
        $clientid       = trim($row['clientid']);
        $allocationtype = trim($row['allocationtype']);
        $orderqty       = (int)$row['orderqty'];
        $readyqty       = (int)$row['readyqty'];
        $saleqty        = (int)$row['saleqty'];

        /* ---------- MODIFY SALE ORDER ---------- */
        $communicationmessage = "On " . date('d-m-Y') . ", during a phone conversation with " . $recipientname . ", it was discussed that the order quantity for " . $productname . " (Ordered Qty: " . $orderqty . ", Available Qty: " . $readyqty . ", Agreed Sale Qty: " . $saleqty. " )"  ;
        if($allocationtype!="seek"){            
            $ok = modify_saleorder($krfconn,$productid,$psono,$stockorder_no, $saleqty, $communicationby, $allocationtype);
            if (!$ok) {
                throw new Exception("Failed to update sale order for product {$productname}");
                exit;
            }
            if(intval($readyqty)> intval($saleqty)){
                $ok =accessStockAdjust($krfconn, $projectid, $clientid, $psono, $stockorder_no, $productid,$orderqty,$trader['client_id']  );
                if (!$ok) {
                    throw new Exception("Failed to stock adjust for sale order for product {$productname}");
                    exit;
                }  
                $communicationmessage .= ", extra plant (" . (intval($readyqty) - intval($saleqty)) . ") allocated to {$trader['client_name']}";

            }
        }

        /* ---------- STOCK + COMMUNICATION ---------- */     

        if($allocationtype=="agreeextraqty"){
          $questiontype ="Agree for extra qty" ;
             
        }else if($allocationtype=="seek"){//cancel and allocate stock available to karjee farms 
            $questiontype ="Seek Permission" ;
            $communicationmessage = "On " . date('d-m-Y') . ", during a phone conversation with " . $recipientname . ", it was discussed that the order quantity for " . $productname . " (Ordered Qty: " . $orderqty . ", Available Qty: " . $readyqty . "). The client wants a partial quantity of " . $saleqty . ", so permission from the administrator is required."; 
        }else if ($allocationtype ==='agreeorderqty') {
             $questiontype ="Accept Order qty" ;             

        }  elseif ($allocationtype === 'partially') {
            $questiontype ="Agree For partially qty" ;

        } elseif ($allocationtype === 'cancel') { 

            $questiontype ="Cancelled" ;
            $communicationmessage = "On " . date('d-m-Y') . ", in a phone conversation with {$recipientname}, " . "the order for {$productname} was cancelled. " .  "Cancelled quantity allocated to {$trader['client_name']}.";
        } 
        savelog_phone($krfconn, $projectid,$clientid, $stockorder_no,$psono,$productid,$productname,$recipientname,$contactno,$communicationmessage,$communicationby,$questiontype,$communication_date,$questionno,$question,$allocationtype,$saleqty );
    }

    $krfconn->commit();
    echo "done";

} catch (Exception $e) {
    $krfconn->rollback();
    echo "Error: " . $e->getMessage();
}

    function accessStockAdjust($conn, $projectid, $clientid, $psono, $stockorder_no,$productid, $orderqty, $assigntraderid) { 
        $stocksql = " SELECT id, qtypertray  FROM krf_readytosale_stocks  WHERE projectid = '{$projectid}'  AND assigntoclientid  = '{$clientid}'  AND psono     = '{$psono}'  and stockorder_no ='{$stockorder_no}' AND productid = '{$productid}'  ORDER BY id ";
        $result = $conn->query($stocksql);
        if (!$result) {
            throw new Exception("Stock query failed: " . $conn->error);
        }
        while ($stk_row = $result->fetch_assoc()) { 
            if ($orderqty <= 0) {
                $updateqry = "  UPDATE krf_readytosale_stocks  SET actualclientid_before_amend = IFNULL(NULLIF(actualclientid_before_amend, ''), assigntoclientid), assigntoclientid = '{$assigntraderid}'   WHERE id = " . (int)$stk_row['id'];
                if (!$conn->query($updateqry)) {
                    throw new Exception("Update stock failed: " . $conn->error);
                    return false;
                }
            }
            $orderqty -= (int)$stk_row['qtypertray'];  
        }
        return true;
    }
    function gettraderid($conn, $projectid) {
        $default = [
            'client_id'    => '',
            'client_name'    => '' 
        ];
        $clntqry = "  SELECT client_id ,client_name FROM krf_client_master  WHERE IFNULL(is_trader,0) = 1   AND projectid = '{$projectid}'  LIMIT 1  ";
        $clntresult = $conn->query($clntqry);
        if ($clntresult && $clntresult->num_rows > 0) {
            $clntrow = $clntresult->fetch_assoc(); 
            return [
                'client_id'    => $clntrow['client_id'],
                'client_name'    => $clntrow['client_name'] 
            ];
        }
        return $default;  
    }
     



function modify_saleorder($krfconn, $productid, $psono, $stockorder_no, $ammendedqty,$communicationby,$allocationtype) {
    $productid = (int)$productid;
    $ammendedqty = (int)$ammendedqty;
    $psono = $krfconn->real_escape_string($psono);
    $stockorder_no = $krfconn->real_escape_string($stockorder_no); 
     $sql = "select   id AS last_id, projectid, sono, psono,stockorder_no,clientid,product_sku,product_id,product_name,deliverydate,rate, cgst_percent, sgst_percent,igst_percent,discount_percent,freight_charges,created_by from krf_sales_order_items WHERE product_id = {$productid} AND psono = '{$psono}'  AND stockorder_no = '{$stockorder_no}'   order by id desc ";
             
    $result = $krfconn->query($sql);
    $noofrows=$result->num_rows;
    if (!$result || $result->num_rows == 0) {
        return false;
    } else if ($noofrows>2){
        return false;
    }

    $row = $result->fetch_assoc();
    /*echo "\n ammendedqty ".$ammendedqty;
    echo "\nrate ".$row['rate'];*/
     $base_expr = ($row['rate'] * $ammendedqty);
  $discount_expr = ROUND(($row['discount_percent']  / 100) * $base_expr, 2);
    $taxable_expr = ROUND($base_expr - $discount_expr, 2);
    $cgst_amount =ROUND(($row['cgst_percent']  / 100) * ($base_expr - $discount_expr), 2);
    $sgst_amount =ROUND(($row['sgst_percent']  / 100) * ($base_expr - $discount_expr), 2);
    $igst_amount =ROUND(($row['igst_percent']  / 100) * ($base_expr - $discount_expr), 2);
    $taxamt=$cgst_amount +$sgst_amount +$igst_amount; 
     $finalamt=$taxable_expr+$taxamt;
    if($noofrows==1){ 
        if($allocationtype!="cancel"){   
              $qry ="insert into krf_sales_order_items (sono ,psono ,stockorder_no ,projectid ,clientid ,product_sku ,product_id ,product_name ,quantity ,rate ,taxableamount ,cgst_percent ,cgst_amount ,sgst_percent ,sgst_amount ,igst_percent ,igst_amount ,discount_percent ,discount_amount ,freight_charges ,tax_amount ,amount ,deliverydate ,created_by ,status   ,ammendedby ,actionstatus) values( '{$row['sono']}','{$row['psono']}' ,'{$row['stockorder_no']}','{$row['projectid']}','{$row['clientid']}','{$row['product_sku']}','{$row['product_id']}','{$row['product_name']}','{$ammendedqty}','{$row['rate']}', '{$taxable_expr}' ,'{$row['cgst_percent']}','{$cgst_amount}','{$row['sgst_percent']}','{$sgst_amount}','{$row['igst_percent']}','{$igst_amount}' ,'{$row['discount_percent']}','{$discount_expr}' , '{$row['freight_charges']}' ,'{$taxamt}' ,'{$finalamt}','{$row['deliverydate']}' ,'{$row['created_by']}' ,'pending' ,'{$communicationby}',1)";
            $krfconn->query($qry);
        }     
          $updateqry =" update krf_sales_order_items set status='cancel' ,ammendedby ='{$communicationby}'  WHERE product_id = {$productid} AND psono = '{$psono}'  AND stockorder_no = '{$stockorder_no}' and id= '{$row['last_id']}'";
        $krfconn->query($updateqry);
    }else{
       if($allocationtype!="cancel"){  
            $updateqry =" update krf_sales_order_items set  quantity ='{$ammendedqty}' ,taxableamount = '{$taxable_expr}' ,cgst_amount ='{$cgst_amount}', sgst_amount ='{$sgst_amount}' ,igst_amount ='{$igst_amount}' ,discount_amount ='{$discount_expr}' ,freight_charges = '{$row['freight_charges']}' ,tax_amount ='{$taxamt}' ,amount ='{$finalamt}', ammendedby ='{$communicationby}'   WHERE product_id = {$productid} AND psono = '{$psono}'  AND stockorder_no = '{$stockorder_no}' and id= '{$row['last_id']}'";
        }else{
            $updateqry =" update krf_sales_order_items set status='cancel' ,ammendedby ='{$communicationby}'  WHERE product_id = {$productid} AND psono = '{$psono}'  AND stockorder_no = '{$stockorder_no}' and id= '{$row['last_id']}'";
           

        }
        $krfconn->query($updateqry);        
    } 
    return true;
}

?>
