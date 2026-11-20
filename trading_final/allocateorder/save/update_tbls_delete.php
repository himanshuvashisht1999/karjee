<?php 
    date_default_timezone_set("Asia/Kolkata");
    require('../../../connection.php');   
    include ('savelogcommunication.php');
    $allocationData = json_decode($_POST['items'], true);
    if (!is_array($allocationData) || empty($allocationData)) {
        echo "No data found for updation of address";
        exit;
    }
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    $projectid          = $_POST['projectid']          ?? '';

    $krfconn->begin_transaction();
    try{  
        foreach ($allocationData as $row) {
            $productid          = $row['productid']          ?? '';
            $productname        = $row['productname']        ?? '';
            $psono              = $row['psono']              ?? '';
            $stockorder_no      = $row['stockorder_no']      ?? '';
            $transporttype     = $row['deliveryType']     ?? '';
            $clientid           = $row['clientid']           ?? '';
            $recipientname      = $row['recipientname']      ?? '';
            $contactno          = $row['contactno']          ?? '';
            $communicationby    = $row['communicationby']    ?? '';
            $communication_date = $row['communication_date'] ?? '';
            $question           = $row['question']           ?? '';
            $questionno         = $row['questionno']         ?? '';
            $deliveryaddress    = $row['deliveryaddress']    ?? '';
            $freightcharges     = $row['freightcharges']     ?? '';
            $questiontype="";
            $orderqty   = $row['orderqty']     ?? '';
            $nooftray  = $row['nooftray']     ?? ''; 
            $newactionstus = $row['newactionstus']     ?? ''; 
             
            $modifystatus=update_attributessaleorder($krfconn,$clientid,$productid,$psono,$stockorder_no,$communicationby,$transporttype,$freightcharges, $nooftray,$deliveryaddress,$newactionstus,$projectid);
            if(!$modifystatus){ 
                $krfconn->rollback();
                echo "Error update_attributessaleorder "  ;
                exit;
            } 
            if($transporttype=="pickup"){  
                $communicationmessage = "On " . date('d-m-Y') . ", in a phone conversation with " . $recipientname . ", it was confirmed that the quantity for " . $productname . " (sale Qty is ".$orderqty ." and no of trays " .$nooftray.") will be picked up by them directly from the polyhouse.";          
                $communicationst=savelog_phone($krfconn,$projectid,$clientid,$stockorder_no,$psono,$productid, $productname,$recipientname,$contactno,$communicationmessage,$communicationby,$questiontype,$communication_date,$questionno,$question,$transporttype,$orderqty) ;         
                if(!$communicationst){
                    $krfconn->rollback();
                    echo "Error: " . $e->getMessage();
                    exit;
                }
            }else if($transporttype=="delivered"){  

                
                $communicationmessage = "On " . date('d-m-Y') . ", in a phone conversation with " . $recipientname . ", it was confirmed that the quantity for " . $productname . " (Qty is ".$orderqty ." and no of trays " .$nooftray.")  will be arranged by the firm and delivered to the client's location.<br>Delivery Address:<br>" . $deliveryaddress . "<br>Freight charges will be Rs " . $freightcharges . ".";
                 $communicationst=savelog_phone($krfconn,$projectid,$clientid,$stockorder_no,$psono,$productid, $productname,$recipientname,$contactno,$communicationmessage,$communicationby,$questiontype,$communication_date,$questionno,$question,$transporttype,$orderqty) ; 
                if(!$communicationst){
                    $krfconn->rollback();
                    echo "Error: " . $e->getMessage();
                    exit;
                }
            } 
        }  
        $krfconn->commit();
        echo "done";
    } catch (Exception $e) {
        $krfconn->rollback();
        echo "Error: " . $e->getMessage();
    } 
    function update_attributessaleorder($krfconn,$clientid,$productid,$psono,$stockorder_no,$communicationby,$transporttype,$freightcharges, $nooftray,$deliveryaddress,$newactionstus,$projectid ){
        $productid=$productid;
        $nooftray=(int)$nooftray;
        $freightcharges=($freightcharges===''?0:(float)$freightcharges);
        $psono=$krfconn->real_escape_string($psono);
        $stockorder_no=$krfconn->real_escape_string($stockorder_no);
        $communicationby=$krfconn->real_escape_string($communicationby);
        $transporttype=$krfconn->real_escape_string($transporttype);

        $sql="SELECT id,projectid,clientid FROM krf_sales_order_items WHERE product_id='{$productid}' AND psono='{$psono}' AND stockorder_no='{$stockorder_no}' ORDER BY id DESC LIMIT 1";
        $result=$krfconn->query($sql);
        if(!$result||$result->num_rows===0){
            return false;
        }
        $row=$result->fetch_assoc(); ;
        $clientid=$krfconn->real_escape_string($row['clientid']);

        $updateItem="UPDATE krf_sales_order_items SET freight_charges={$freightcharges},ammendedby='{$communicationby}' ,actionstatus = IF(IFNULL(actionstatus, '') = '' OR actionstatus < '{$newactionstus}','{$newactionstus}', actionstatus)  WHERE id={$row['id']}";
        $krfconn->query($updateItem);

        $sql="SELECT id FROM krf_deliveryaddress_location WHERE productid={$productid} AND psono='{$psono}' and   stockorder_no ='{$stockorder_no}' and clientid ='{$clientid}'ORDER BY id DESC LIMIT 1";
        $result=$krfconn->query($sql);
        if(!$result||$result->num_rows===0){ 
            $insertqry ="insert into krf_deliveryaddress_location (projectid, psono, clientid , nooftray ,deliverycharges ,deliveryaddress,status, transporttype ,created_by,productid,stockorder_no ) values ('{$projectid}', '{$psono}', '{$clientid}'' , $nooftray, '{$freightcharges}'   ,'{$deliveryaddress}','Pending',   '{$transporttype}' ,'{$communicationby}' ,'{$productid}'  , '{$stockorder_no}')"; 
            $krfconn->query($insertqry); 
        }else{
            $loc=$result->fetch_assoc();
            $loc_id=(int)$loc['id'];
            $updateLoc="UPDATE krf_deliveryaddress_location SET deliverycharges={$freightcharges},nooftray={$nooftray},transporttype='{$transporttype}'  WHERE id={$loc_id}";
            $krfconn->query($updateLoc)
            ;
        }
        return true;
    }
?>
