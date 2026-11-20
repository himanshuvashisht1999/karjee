<?php 
    date_default_timezone_set("Asia/Kolkata");
    require('../../../connection.php');   
    include ('savelogcommunication.php');
    include ('updateFreightAddress.php');
    include ('../proformainvoice/attachmentfunction_emailfunction.php');

    $saledetailproductwise = json_decode($_POST['saledetailproductwise'], true);
    if (!is_array($saledetailproductwise) || empty($saledetailproductwise)) {
        echo "No data found product ";
        exit;
    }
    $clientData = json_decode($_POST['clientData'], true);
    if (!is_array($clientData) || empty($clientData)) {
        echo "No data found for client detaisl";
        exit;
    }
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    $projectid          = $_POST['projectid']          ?? ''; 
    $toemail=$_POST['toemail'];
    $ccemail=$_POST['ccemail'];
    $emailbody=$_POST['emailbody'];
    $emailbodyHtml = nl2br(htmlspecialchars($emailbody));
    $communicatedby=$_POST['communicatedby'];
    $clientid=$_POST['clientid'] ?? "";
    $companyname=$_POST['companyname'] ?? "";
    $actionstatus=$_POST['actionstatus'] ?? "4"; 
    $addresstypearray=updateslocation($krfconn,$saledetailproductwise,$communicatedby,$projectid,$actionstatus);

    if (!is_array($addresstypearray)) {
        echo "Issue in updating Address";
        exit;
    }
    $arraydelivered = $addresstypearray['arraydelivered'] ?? [];
    $arraypickup    = $addresstypearray['arraypickup'] ?? [];
    $totalAdvance = 0;
    $seenPsono = [];
    foreach ($saledetailproductwise as $row) {
        if (empty($row['psono'])) {
            continue;
        }
        $psono = $row['psono'];
        // Count advance ONLY first time this psono appears
        if (!isset($seenPsono[$psono])) {
            $totalAdvance += (float)($row['advanceamount'] ?? 0);
            // Mark this psono as already counted
            $seenPsono[$psono] = true;
        }
    }
    $firstRow = reset($saledetailproductwise);
    $bankdetails = [ 
            'Bank Name'  => $firstRow['bankname']  ?? '',
            'Account No' => $firstRow['accountno'] ?? '',
            'IFSC Code'  => $firstRow['ifsccode']  ?? '',
            'Branch'     => $firstRow['branch']     ?? '', 
    ];

    $proformaarray=createproformainvoice_new($saledetailproductwise,$communicatedby,$clientData,$companyname,$totalAdvance,$bankdetails );
    if (!empty($proformainvno)) {
        exit;
    }

  
    $profromainv= $proformaarray['proformainvno'];
    $proformasavepath= $proformaarray['proformasavepath']; 

    $freigntinvno="";
    $freightsavepath="";
     if (!empty($arraydelivered) && is_array($arraydelivered)) {
        $freightarray=  freight_proforma($krfconn,$profromainv,$arraydelivered,$communicatedby,$clientData,$companyname,$bankdetails) ; 
        if (empty($freightarray) || !is_array($freightarray)) {
            exit; // exit only on failure
        }    
        $freigntinvno= $freightarray['freigntinvno'];
        $freightsavepath= $freightarray['freightsavepath']; 
    }   
 
    $pickupsavepath='';
    if (!empty($arraypickup) && is_array($arraypickup)) { 
        $pickuparray=locationwise_detail($krfconn,$hrmconn,$projectid,$profromainv,$arraypickup,$communicatedby,$clientData,$companyname,$bankdetails,$clientid)  ;
        if (empty($pickuparray) || !is_array($pickuparray)) {
            exit; // exit only on failure
        }
        $pickupsavepath= $pickuparray['pickupsavepath']; 
    } 
    $krfconn->begin_transaction();
    try{  
        foreach ($saledetailproductwise as $row) {
            $productid          = $row['productid']          ?? ''; 
            $psono              = $row['psono']              ?? '';
            $stockorder_no      = $row['stockorder_no']      ?? ''; 
            $clientid           = $row['clientid']           ?? '';
            $updateItem="UPDATE krf_sales_order_items SET proformainvno='{$profromainv}',proformainvpath='{$proformasavepath}' , freightfilepath='{$freightsavepath}' ,pickupfilepath='{$pickupsavepath}'   WHERE product_id = {$productid}  AND psono = '{$psono}'  AND stockorder_no = '{$stockorder_no}'  AND clientid = '{$clientid}' and (ifnull(status,'')= '' or status='pending')";
                $krfconn->query($updateItem);
        }  

         
        $communicationmgs=$profromainv; 

        $krfconn->commit();$emailattach = '';
        $attach = [];
        $emailattach="";
        /* Proforma */
        if (!empty($proformasavepath) && file_exists($proformasavepath)) {
            $attach[] = $proformasavepath;
            $newpth = str_replace("../../..", "", $proformasavepath);
            $emailattach = $newpth;
        }

        /* Freight */
        if (!empty($freightsavepath) && file_exists($freightsavepath)) {
            $attach[] = $freightsavepath; 
            $newpth = str_replace("../../..", "", $freightsavepath);
            $emailattach .= ($emailattach !== '' ? ',' : '') . $newpth;
        }

        /* Pickup */
        if (!empty($pickupsavepath) && file_exists($pickupsavepath)) {
            $attach[] = $pickupsavepath;
            $newpth = str_replace("../../..", "", $pickupsavepath); 
            $emailattach .= ($emailattach !== '' ? ',' : '') . $newpth;
        }
        $sendto   = [$toemail];
        $sendcc   = !empty($ccemail) ? array_map('trim', explode(',', $ccemail)) : [];
        $sendbcc = [];
        $subject = "Revised Proforma Invoice No".$profromainv;
        $attach  = $attach;   // PDF path
        $msg ="Sent email for prorma invoic no ".$profromainv;
        savelog_phone( $krfconn,$projectid,$clientid, "", "","","", "", "",$msg, $communicatedby,"email", date('Y-m-d H:i:s'), "","", "", $toemail,$ccemail,$subject, $emailattach);
         $emailstatus='';
        if (sendemailfrommailer($sendto, $sendcc, $sendbcc, $subject, $emailbodyHtml, $attach)) {
               $emailstatus= 'successfully';     
        } else {
            $emailstatus= 'failure';
        } 

       echo json_encode([
            'status'       => "Update successfully",
            'emailstauts'  => "Email sent " . $emailstatus
        ]);

        
    } catch (Exception $e) {
        $krfconn->rollback();
        echo "Error: " . $e->getMessage();
    } 
    function update_attributessaleorder($krfconn,$clientid,$productid,$psono,$stockorder_no,$communicationby,$transporttype,$freightcharges, $nooftray,$deliveryaddress,$newactionstus ){
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

        $sql="SELECT id FROM krf_deliveryaddress_location WHERE productid={$productid} AND psono='{$psono}' ORDER BY id DESC LIMIT 1";
        $result=$krfconn->query($sql);
        if(!$result||$result->num_rows===0){ 
            $insertqry ="insert into krf_deliveryaddress_location (projectid, psono, clientid , nooftray ,deliverycharges ,deliveryaddress,status, actual_address,transporttype ,created_by,productid ) select projectid, psono, clientid , $nooftray, '{$freightcharges}'   ,'{$deliveryaddress}','Pending', deliveryaddress,   '{$transporttype}' ,'{$communicationby}' ,'{$productid}' from krf_deliveryaddress_location where psono ='{$psono}' and clientid ='{$clientid}' order by id limit 1 "; 
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
