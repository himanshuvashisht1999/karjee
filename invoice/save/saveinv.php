<?php 
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);

    date_default_timezone_set("Asia/Kolkata");
    require('../../connection.php');    
    include ('../invoicepdf/createfinalinv_pdf.php');

    $saledetailproductwise = json_decode($_POST['saledetailproductwise'], true);
    if (!is_array($saledetailproductwise) || empty($saledetailproductwise)) {
        echo "No data found product ";
        exit;
    }
    $clientData = json_decode($_POST['clientData'], true);
    $drivername = $_POST['drivername'] ?? '';
    $drivercontactno = $_POST['drivercontactno'] ?? '';
    $vehicleno = $_POST['vehicleno'] ?? '';
    $freightcharges = $_POST['freightcharges'] ?? '';

    $stmt = $krfconn->prepare("SELECT vehicleno, vehiclename, plantscapacity FROM krf_vehicle_master WHERE status = 'Active' AND projectid = ? AND vehicleno = ?");
    $stmt->bind_param("ss", $projectid, $vehicleno);
    $stmt->execute();
    $result = $stmt->get_result();
    $vehiclename="";
    $plantscapacity="";
    while ($row = $result->fetch_assoc()) {
        $vehiclename = $row['vehiclename'];
        $plantscapacity = $row['plantscapacity'];
    }

    if (!is_array($clientData) || empty($clientData)) {
        echo json_encode([
            'status'       => "success",
            'message'  => "Client data not found"
        ]);
        exit;
    }
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    $companyname          = $_POST['companyname']          ?? ''; 
    $psono          = $_POST['psono']          ?? ''; 
    $sono          = $_POST['sono']          ?? ''; 
    $projectid          = $_POST['projectid']          ?? ''; 
    $companyid          = $_POST['companyid']          ?? ''; 
    
    $communicatedby=$_POST['communicatedby'];
    $clientid=$_POST['clientid'] ?? ""; 
    $invoicetype=$_POST['invoicetype'] ?? "tempinv";      
    $totalAdvance = 0;    
    $bankdetails=getbankdetails($krfconn, $projectid,$clientid) ; 

    $Advancearray=getadvancepaymet_agtorder($krfconn, $clientid, $psono);
     $totalAdvance= $Advancearray['advance'];
    $invoiceno  ="T-".date('d-m-Y');
    $max_invoiceno=""; 
    if($invoicetype!="tempinv"){
        $max_invoiceno= getinvoiceno($krfconn) ;
        if (!$max_invoiceno) {
            echo json_encode([
                'status'       => "success",
                'message'  => "Invoice No not found"
            ]);
            exit; 
        } 
        $invNoFormatted = str_pad($max_invoiceno, 3, "0", STR_PAD_LEFT);
        $invoiceno = "KRF-" . $invNoFormatted . "-" . date('Y');
        
    }
    $proformaarray=createinvoicepdf($saledetailproductwise,$communicatedby,$clientData,$companyname,$totalAdvance,$bankdetails ,$invoiceno, $invoicetype,$sono);

    if (!empty($proformainvno)) {
        echo json_encode([
            'status'       => "Error",
            'message'  => "Error in generating pdf"
        ]);
        exit;
    }
    $profromainv= $proformaarray['proformainvno'];
    $proformasavepath= $proformaarray['proformasavepath']; 
    $inv_pth = str_replace("../../", "", $proformasavepath);

    // echo "==".$projectid;
    // echo "==".$saledetailproductwise;
    // echo "==".$clientid;
    // echo "==".$clientData;
    // echo "==".$communicatedby;
    // echo "==".$profromainv;
    // echo "==".$companyname;
    // echo "==".$vehicleno;
    // echo "==".$vehiclename;
    // echo "==".$drivername;
    // echo "==".$drivercontactno;
    // echo "==".$freightcharges;
    // die();
    $deliverynotepath_arr= delivery_note($krfconn,$companyname,$projectid,$saledetailproductwise,$clientData,$profromainv,$vehicleno,$vehiclename,$drivername,$drivercontactno,$freightcharges,$communicatedby);

    


     $deliverynotepath= $deliverynotepath_arr['deliverypath']; 

    $deliverynotepath = str_replace("../../", "", $deliverynotepath);
    
    if($invoicetype==="tempinv"){
        echo json_encode([
            'status'       => "success",
            'file'  => $inv_pth,
            'deliverynote'  => $deliverynotepath
        ]);
        exit;
    }else {
        $krfconn->begin_transaction();
         try{  
            $curdt= date('Y-m-d');
            foreach ($saledetailproductwise as $row) {
                $productid          = $row['productid']          ?? ''; 
                $psono              = $row['psono']              ?? '';
                $stockorder_no      = $row['stockorder_no']      ?? ''; 
                $clientid           = $row['clientid']           ?? '';
                $updateItem="UPDATE krf_sales_order_items SET invoiceno='{$invoiceno}',invoicedate='{$curdt}' , invoicefilepath='{$inv_pth}' ,status ='Invoiced'   WHERE product_id = {$productid}  AND psono = '{$psono}'  AND stockorder_no = '{$stockorder_no}'  AND clientid = '{$clientid}' and (ifnull(status,'')= '' or status='pending')";                
                $krfconn->query($updateItem);
            } 
            $krfconn->commit();

            echo json_encode([
                'status'       => "success",
                'file'  => $inv_pth,
            'deliverynote'  => $deliverynotepath
            ]);

            
        } catch (Exception $e) {
            $krfconn->rollback();
            echo "Error: " . $e->getMessage();
        }  

    }    



exit;

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

        $krfconn->commit();

        $emailattach = '';
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

function getbankdetails($conn, $projectid, $clientid) {
    return [
        'Bank Name'   => 'Chhattisgarh Rajya Gramin Bank',
        'Account No'  => '77054448500',
        'IFSC Code'   => 'CRGB0006002',
        'Branch'     => 'AMBIKAPUR'
    ];
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
function getinvoiceno($conn) {
    $currentYear = date('Y');
    $nextNo = 1;
    $sql = " SELECT IFNULL(MAX(
                CAST(SUBSTRING_INDEX(
                        SUBSTRING_INDEX(invoiceno, '-', 2),
                        '-', -1
                    ) AS UNSIGNED
                )
            ), 0) + 1 AS next_no FROM krf_sales_order_items WHERE invoiceno LIKE CONCAT('%-', ?, '')  ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $currentYear);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $nextNo = (int)$row['next_no'];
    }
    $stmt->close();
    return $nextNo;
}


/*function getinvoiceno($conn) {
    $inv_no = 0;
    $currentYear = date('Y');
    $sql = "SELECT IFNULL(MAX(invoiceno), 0) + 1 AS newinvoiceno
        FROM krf_sales_order_items
        WHERE YEAR(invoicedate) = ? ";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $currentYear);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $inv_no = (int)$row['newinvoiceno'];
        }

        $stmt->close();
    }
    return $inv_no;
}*/
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
