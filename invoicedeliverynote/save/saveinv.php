<?php 
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);

    date_default_timezone_set("Asia/Kolkata");
    require('../../connection.php');    
    include ('../invoicepdf/createfinalinv_pdf.php');
    include ('creategatepasspdf.php');

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
    if ($invoicetype === "gatepass") {
        $first_item = $saledetailproductwise[0] ?? [];
        $ddate = $_POST['deliverydate'] ?? $first_item['deliverydate'] ?? '';
        $gatepassResult = generate_gate_pass_pdf($krfconn, $projectid, $saledetailproductwise, $clientid, $clientData, $communicatedby, $psono, $psono, $drivername, $drivercontactno, $vehicleno, $ddate);
        $gatepasspath = str_replace("../../../", "", $gatepassResult['deliverypath']);
        $gatepasspath = str_replace("../../", "", $gatepasspath);
        
        $krfconn->begin_transaction();
        try {
            foreach ($saledetailproductwise as $item) {
                $pid = $item['product_id'] ?? $item['productid'] ?? 0;
                $qty = (int)($item['quantity'] ?? $item['saleqty'] ?? $item['totalqty'] ?? 0);
                $ddateString = $item['deliverydate'] ?? date('d-m-Y');
                $deliverydate = date('Y-m-d', strtotime($ddateString));
                $target_client = (int)($clientid ?: $item['clientid'] ?: 0);

                $stmt = $krfconn->prepare("INSERT INTO krf_invoice_gatepass (projectid, psono, clientid, productid, deliveryqty, deliverydate, filepath) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issiiss", $projectid, $psono, $target_client, $pid, $qty, $deliverydate, $gatepasspath);
                $stmt->execute();
                $newGatepassId = $krfconn->insert_id;
                $stmt->close();
                
                $stmt = $krfconn->prepare("UPDATE krf_vehicle_travel_history SET gatepass_id = ?, vehicle_no = ?, driver_name = ?, driver_mobileno = ?, freight_amount = ?, psono = ? WHERE stockorder_no = ? AND productid = ?");
                $target_stock = $item['stockorder_no'] ?? $psono;
                $stmt->bind_param("sssssssi", $newGatepassId, $vehicleno, $drivername, $drivercontactno, $freightcharges, $psono, $target_stock, $pid);
                $stmt->execute();
                $stmt->close();
            }
            $krfconn->commit();
        } catch (Exception $e) {
            $krfconn->rollback();
        }

        echo json_encode([
            'status'       => "success",
            'gatepass'     => $gatepasspath
        ]);
        exit;
    }

    if ($invoicetype === "deliverynote") {
        // Fetch Transport details from Travel History for consistency
        $stmt_fetch = $krfconn->prepare("SELECT vehicle_no, driver_name, driver_mobileno, freight_amount FROM krf_vehicle_travel_history WHERE psono = ? AND projectid = ? ORDER BY id DESC LIMIT 1");
        $stmt_fetch->bind_param("si", $psono, $projectid);
        $stmt_fetch->execute();
        $res_fetch = $stmt_fetch->get_result();
        if ($row_fetch = $res_fetch->fetch_assoc()) {
            $vehicleno       = $row_fetch['vehicle_no']       ?: $vehicleno;
            $drivername      = $row_fetch['driver_name']      ?: $drivername;
            $drivercontactno = $row_fetch['driver_mobileno'] ?: $drivercontactno;
            $freightcharges  = $row_fetch['freight_amount']  ?: $freightcharges;

            // Update vehiclename if vehicle_no was found in history
            if ($row_fetch['vehicle_no']) {
                $vstmt = $krfconn->prepare("SELECT vehiclename FROM krf_vehicle_master WHERE status = 'Active' AND projectid = ? AND vehicleno = ?");
                $vstmt->bind_param("is", $projectid, $row_fetch['vehicle_no']);
                $vstmt->execute();
                if ($vrow = $vstmt->get_result()->fetch_assoc()) {
                    $vehiclename = $vrow['vehiclename'];
                }
                $vstmt->close();
            }
        }
        $stmt_fetch->close();

        $deliverynotepath_arr = delivery_note($krfconn, $companyname, $projectid, $saledetailproductwise, $clientData, $psono, $vehicleno, $vehiclename, $drivername, $drivercontactno, $freightcharges, $communicatedby);
        $deliverynotepath = str_replace("../../", "", $deliverynotepath_arr['deliverypath']);
        
        $krfconn->begin_transaction();
        try {
            foreach ($saledetailproductwise as $item) {
                $pid = $item['product_id'] ?? $item['productid'] ?? 0;
                $qty = (int)($item['quantity'] ?? $item['saleqty'] ?? $item['totalqty'] ?? 0);
                $ddateString = $item['deliverydate'] ?? date('d-m-Y');
                $deliverydate = date('Y-m-d', strtotime($ddateString));
                $target_client = (int)($clientid ?: $item['clientid'] ?: 0);

                $stmt = $krfconn->prepare("INSERT INTO krf_invoice_deliverynote (projectid, psono, clientid, productid, deliveryqty, deliverydate, filepath) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isiiiss", $projectid, $psono, $target_client, $pid, $qty, $deliverydate, $deliverynotepath);
                $stmt->execute();
                $newDeliveryNoteId = $krfconn->insert_id;
                $stmt->close();
                
                $stmt = $krfconn->prepare("UPDATE krf_vehicle_travel_history SET deliverynote_id = ?, vehicle_no = ?, driver_name = ?, driver_mobileno = ?, freight_amount = ?, psono = ? WHERE stockorder_no = ? AND productid = ?");
                $target_stock = $item['stockorder_no'] ?? $psono;
                $stmt->bind_param("isssdssi", $newDeliveryNoteId, $vehicleno, $drivername, $drivercontactno, $freightcharges, $psono, $target_stock, $pid);
                $stmt->execute();
                $stmt->close();
            }
            $krfconn->commit();
        } catch (Exception $e) {
            $krfconn->rollback();
        }

        echo json_encode([
            'status'       => "success",
            'deliverynote' => $deliverynotepath
        ]);
        exit;
    }

    // if finalinvoice
    $proformaarray = createinvoicepdf($saledetailproductwise, $communicatedby, $clientData, $companyname, $totalAdvance, $bankdetails, $invoiceno, $invoicetype, $sono, $freightcharges);

    if (empty($proformaarray['proformainvno'])) {
        echo json_encode([
            'status'       => "Error",
            'message'      => "Error in generating pdf"
        ]);
        exit;
    }
    
    $profromainv = $proformaarray['proformainvno'];
    $proformasavepath = $proformaarray['proformasavepath']; 
    $inv_pth = str_replace("../../", "", $proformasavepath);

    $delivery_date = $_POST['deliverydate'] ?? '';
    $vehicle_id   = $_POST['vehicleid']   ?? '';
    $driver_id    = $_POST['driverid']    ?? '';

    if ($invoicetype === "tempinv") {
        $krfconn->begin_transaction();
        try {
            foreach ($saledetailproductwise as $item) {
                $pid = $item['product_id'] ?? $item['productid'] ?? 0;
                $pname = $item['productname'] ?? '';
                $qty = (int)($item['saleqty'] ?? $item['quantity'] ?? 0);
                $target_stock = $item['stockorder_no'] ?? $psono;
                $delivery_dt = !empty($delivery_date) ? date('Y-m-d', strtotime($delivery_date)) : date('Y-m-d');

                // Upsert into travel history
                $check = $krfconn->prepare("SELECT id FROM krf_vehicle_travel_history WHERE stockorder_no = ? AND productid = ?");
                $check->bind_param("si", $target_stock, $pid);
                $check->execute();
                $exists = $check->get_result()->fetch_assoc();
                $check->close();

                if ($exists) {
                    $stmt = $krfconn->prepare("UPDATE krf_vehicle_travel_history SET vehicle_id = ?, vehicle_no = ?, driver_name = ?, driver_mobileno = ?, freight_amount = ?, deliverydate = ?, send_totalplantcount = ?, psono = ? WHERE id = ?");
                    $stmt->bind_param("isssdsisi", $vehicle_id, $vehicleno, $drivername, $drivercontactno, $freightcharges, $delivery_dt, $qty, $psono, $exists['id']);
                } else {
                    $stmt = $krfconn->prepare("INSERT INTO krf_vehicle_travel_history (projectid, stockorder_no, psono, productid, vehicle_id, vehicle_no, driver_name, driver_mobileno, freight_amount, deliverydate, send_totalplantcount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("issiisssdsi", $projectid, $target_stock, $psono, $pid, $vehicle_id, $vehicleno, $drivername, $drivercontactno, $freightcharges, $delivery_dt, $qty);
                }
                $stmt->execute();
                $stmt->close();
            }
            $krfconn->commit();
        } catch (Exception $e) {
            $krfconn->rollback();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }

        // Generate Drafts
        $gatepassResult = generate_gate_pass_pdf($krfconn, $projectid, $saledetailproductwise, $clientid, $clientData, $communicatedby, $psono, "DRAFT-".$psono, $drivername, $drivercontactno, $vehicleno, $delivery_date);
        $gp_path = str_replace(["../../../", "../../"], "", $gatepassResult['deliverypath']);
        
        $dn_arr = delivery_note($krfconn, $companyname, $projectid, $saledetailproductwise, $clientData, $psono, $vehicleno, $vehiclename, $drivername, $drivercontactno, $freightcharges, $communicatedby, $delivery_date);
        $dn_path = str_replace("../../", "", $dn_arr['deliverypath']);

        echo json_encode([
            'status'       => "success",
            'file'         => $inv_pth,
            'gatepass'     => $gp_path,
            'deliverynote' => $dn_path
        ]);
        exit;
    }

    $krfconn->begin_transaction();
    try {  
        $curdt = date('Y-m-d');
        foreach ($saledetailproductwise as $row) {
            $productid = $row['productid'] ?? ''; 
            $psrno = $row['psono'] ?? '';
            $stockorder_no = $row['stockorder_no'] ?? ''; 
            $client_id = $row['clientid'] ?? '';
            $updateItem = "UPDATE krf_sales_order_items SET invoiceno='{$invoiceno}', invoicedate='{$curdt}', invoicefilepath='{$inv_pth}', status ='Invoiced' WHERE product_id = {$productid} AND psono = '{$psrno}' AND stockorder_no = '{$stockorder_no}' AND clientid = '{$client_id}' and (ifnull(status,'')= '' or status='pending')";                
            $krfconn->query($updateItem);
        } 
        $krfconn->commit();

        echo json_encode([
            'status'   => "success",
            'file'     => $inv_pth
        ]);
    } catch (Exception $e) {
        $krfconn->rollback();
        echo "Error: " . $e->getMessage();
    }  


/*
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
    if (!empty($proformasavepath) && file_exists($proformasavepath)) {
        $attach[] = $proformasavepath;
        $newpth = str_replace("../../..", "", $proformasavepath);
        $emailattach = $newpth;
    }

    if (!empty($freightsavepath) && file_exists($freightsavepath)) {
        $attach[] = $freightsavepath; 
        $newpth = str_replace("../../..", "", $freightsavepath);
        $emailattach .= ($emailattach !== '' ? ',' : '') . $newpth;
    }

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
} */

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
