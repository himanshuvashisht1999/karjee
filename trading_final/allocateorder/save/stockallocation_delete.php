<?php 
    date_default_timezone_set("Asia/Kolkata");
    require('../../../connection.php');   
    include ('savelogcommunication.php');
   // $action = isset($_GET['action']) ? $_GET['action'] : ''; 
    $projectid = isset($_POST['projectid']) ? trim($_POST['projectid']) : '';
    $psono = isset($_POST['psono']) ? trim($_POST['psono']) : '';
    $stockorder_no = isset($_POST['stockorder_no']) ? trim($_POST['stockorder_no']) : '';
    $productid = isset($_POST['productid']) ? trim($_POST['productid']) : '';
    $productname= isset($_POST['productname']) ? trim($_POST['productname']) : '';    
    $allocationtype= isset($_POST['allocationtype']) ? trim($_POST['allocationtype']) : '';
    $stockqty= isset($_POST['stockqty']) ? trim($_POST['stockqty']) : '';
    $clientid= isset($_POST['clientid']) ? trim($_POST['clientid']) : '';
    $orderqty= isset($_POST['orderqty']) ? trim($_POST['orderqty']) : '';
    $saleqty= isset($_POST['saleqty']) ? trim($_POST['saleqty']) : '';
    $recipientname= isset($_POST['recipientname']) ? trim($_POST['recipientname']) : '';
    $contactno= isset($_POST['contactno']) ? trim($_POST['contactno']) : '';
    $communicationby= isset($_POST['communicationby']) ? trim($_POST['communicationby']) : '';
    $communication_date= isset($_POST['communication_date']) ? trim($_POST['communication_date']) : '';
    $questionno=isset($_POST['questionno']) ? trim($_POST['questionno']) : '';
    $question=isset($_POST['question']) ? trim($_POST['question']) : '';
    $questiontype="";

    $traderarray=gettraderid($krfconn,$projectid); 
    $traderid= $traderarray['client_id'];
    $tradername= $traderarray['client_name']; 

    if(!$traderid){
        echo "Trader id not found";
        exit;
    }
    $krfconn->begin_transaction();
    try{
        $modifystatus=modify_saleorder($krfconn,$productid,$psono,$stockorder_no,$saleqty, $communicationby,$allocationtype); 
        if(!$modifystatus){
            $krfconn->rollback();
            echo "Error: " . $e->getMessage();
            exit;
        }
        if($allocationtype=="exact qty"){ 
            $communicationmessage =  "On " . date('d-m-Y') . ", in a phone conversation with " . $recipientname . ", the order quantity for " . $productname .   " was revised from " . $orderqty . " to " . $stockqty . ".";
            
            $communicationst=savelog_phone($krfconn,$projectid,$clientid,$stockorder_no,$psono,$productid, $productname,$recipientname,$contactno,$communicationmessage,$communicationby,$questiontype,$communication_date,$questionno,$question,$allocationtype,$saleqty) ;         
            if(!$communicationst){
                $krfconn->rollback();
                echo "Error: " . $e->getMessage();
                exit;
            }
        }else if($allocationtype=="yes"){ 
            $communicationmessage =  "On " . date('d-m-Y') . ", in a phone conversation with " . $recipientname . ", the order quantity for " . $productname .   " was revised from " . $orderqty . " to " . $stockqty . ".";
            
            $communicationst=savelog_phone($krfconn,$projectid,$clientid,$stockorder_no,$psono,$productid, $productname,$recipientname,$contactno,$communicationmessage,$communicationby,$questiontype,$communication_date,$questionno,$question,$allocationtype,$saleqty) ;         
            if(!$communicationst){
                $krfconn->rollback();
                echo "Error: " . $e->getMessage();
                exit;
            }
        }else if($allocationtype=="no"){//allocate stock available to karjee farms
            $stkstatus= accessStockAdjust($krfconn, $projectid,$clientid,$psono,$stockorder_no,$productid,$orderqty, $traderid );           
            if(!$stkstatus){
                $krfconn->rollback();
                echo "Error: " . $e->getMessage();
                exit;
            }

             $communicationmessage =  "On " . date('d-m-Y') . ", in a phone conversation with " . $recipientname .   ", the order quantity for " . $productname . " (Order Qty: " . $orderqty .   ")  and Available Quantity: ".$stockqty." was discussed. They did not want the extra plant, and the remaining quantity will be allocated to " .   $tradername . ".";  

            $communicationst=savelog_phone($krfconn,$projectid,$clientid,$stockorder_no,$psono,$productid, $productname,$recipientname,$contactno,$communicationmessage,$communicationby,$questiontype,$communication_date,$questionno,$question,$allocationtype,$saleqty) ;  
            if(!$communicationst){
                $krfconn->rollback();
                echo "Error: " . $e->getMessage();
                exit;
            }

        }else if($allocationtype=="partially"){//allocate stock available to karjee farms 
            $stkstatus=accessStockAdjust($krfconn, $projectid,$clientid,$psono,$stockorder_no,$productid,$saleqty, $traderid );
            if(!$stkstatus){
                $krfconn->rollback();
                echo "Error: " . $e->getMessage();
                exit;
            }
            $communicationmessage =   "On " . date('d-m-Y') . ", in a phone conversation with " . $recipientname . ", the order quantity for " . $productname . " (Order Qty: " . $orderqty . ", Available Qty: " . $stockqty . ") was discussed. They accepted a partial quantity; " . "the order quantity for " . $productname . " was revised from " . $orderqty .  " to " . $stockqty . ", and the remaining quantity will be allocated to " . $tradername . ".";
                 
            $communicationst=savelog_phone($krfconn,$projectid,$clientid,$stockorder_no,$psono,$productid, $productname,$recipientname,$contactno,$communicationmessage,$communicationby,$questiontype,$communication_date,$questionno,$question,$allocationtype,$saleqty) ;  
            if(!$communicationst){
                $krfconn->rollback();
                echo "Error: " . $e->getMessage();
                exit;
            }
        }else if($allocationtype=="cancel"){//cancel and allocate stock available to karjee farms 
             accessStockAdjust($krfconn, $projectid,$clientid,$psono,$stockorder_no,$productid,$orderqty, $traderid );
             $communicationmessage = "On " . date('d-m-Y') . ", during a phone conversation with " . $recipientname . ", the order quantity for " . $productname . " (Order Qty: " . $orderqty . ", Available Qty: " . $stockqty . ") was cancelled. The recipient did not wish to proceed with the plant, and the cancelled quantity will be allocated to " . $tradername . "."; 

            $communicationst=savelog_phone($krfconn,$projectid,$clientid,$stockorder_no,$psono,$productid, $productname,$recipientname,$contactno,$communicationmessage,$communicationby,$questiontype,$communication_date,$questionno,$question,$allocationtype,$saleqty) ;  
            if(!$communicationst){
                $krfconn->rollback();
                echo "Error: " . $e->getMessage();
                exit;
            }
        } else if($allocationtype=="seek"){//cancel and allocate stock available to karjee farms 
              
            $communicationmessage = "On " . date('d-m-Y') . ", during a phone conversation with " . $recipientname . ", it was discussed that the order quantity for " . $productname . " (Ordered Qty: " . $orderqty . ", Available Qty: " . $stockqty . "). The client wants a partial quantity of " . $saleqty . ", so permission from the administrator is required."; 

            $communicationst=savelog_phone($krfconn,$projectid,$clientid,$stockorder_no,$psono,$productid, $productname,$recipientname,$contactno,$communicationmessage,$communicationby,"seek permission",$communication_date,$questionno,$question,$allocationtype,$saleqty) ;  
            if(!$communicationst){
                $krfconn->rollback();
                echo "Error: " . $e->getMessage();
                exit;
            }
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
