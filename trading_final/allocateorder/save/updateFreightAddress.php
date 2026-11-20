<?php 
date_default_timezone_set("Asia/Calcutta");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); 
function updateslocation($krfconn, $productarray, $communicationby, $projectid, $actionstatus){ 
    $arraydelivered = [];
    $arraypickup    = [];  
    foreach ($productarray as $item) {
        $clientid      = $krfconn->real_escape_string($item['clientid']);
        $productid     = (int)($item['productid'] ?? 0);
        $psono         = $krfconn->real_escape_string($item['psono']);
        $sono          = $krfconn->real_escape_string($item['sono']);
        $stockorder_no = $krfconn->real_escape_string($item['stockorder_no']);
        $transporttype = $item['deliveryOption'] ?? ''; 
        $saleqty       = (int)($item['saleqty'] ?? 0);
        $traycount     = (int)($item['traycount'] ?? 1);
        $deliveryaddress = '';
        $freightcharges  = 0;
        if ($transporttype === "delivered") {
            $deliveryaddress = $krfconn->real_escape_string($item['deliveryaddress'] ?? '');
            $freightcharges  = (float)($item['freightcharges'] ?? 0);
        }
        $nooftray = ($traycount > 0 && $saleqty > 0) ? (int) ceil($saleqty / $traycount)  : 0; 
        /* -------- CHECK EXISTING -------- */
        $qry = "SELECT id FROM krf_deliveryaddress_location WHERE productid = {$productid}  AND psono = '{$psono}'  AND stockorder_no = '{$stockorder_no}'  AND clientid = '{$clientid}'  ORDER BY id DESC  LIMIT 1  ";
        $result = $krfconn->query($qry);
        if (!$result || $result->num_rows === 0) {
            $insertqry = "INSERT INTO krf_deliveryaddress_location  (projectid, psono, clientid, nooftray, deliverycharges,  deliveryaddress, status, transporttype, created_by,  productid, stockorder_no)
                VALUES  ('{$projectid}', '{$psono}', '{$clientid}', {$nooftray}, {$freightcharges}, '{$deliveryaddress}', 'Pending', '{$transporttype}', '{$communicationby}', {$productid}, '{$stockorder_no}') ";
            $krfconn->query($insertqry);
        } else {
            $updateLoc = " UPDATE krf_deliveryaddress_location SET deliveryaddress = '{$deliveryaddress}',  deliverycharges = {$freightcharges},  nooftray = {$nooftray}, transporttype = '{$transporttype}' WHERE productid = {$productid} AND psono = '{$psono}' AND stockorder_no = '{$stockorder_no}'  AND clientid = '{$clientid}' ";
            $krfconn->query($updateLoc);
        }
        /* -------- UPDATE SALES ORDER -------- */
        $updateItem = " UPDATE krf_sales_order_items  SET freight_charges = {$freightcharges}, actionstatus = '{$actionstatus}'  WHERE product_id = {$productid}  AND psono = '{$psono}'  AND stockorder_no = '{$stockorder_no}'  AND clientid = '{$clientid}'   ";
        $krfconn->query($updateItem);
        $entry = [
            'productname'     => $item['productname'] ?? '',
            'psono'           => $psono,
            'sono'            => $sono,
            'stockorder_no'   => $stockorder_no,
            'saleqty'         => $saleqty,
            'locationaddress' => $deliveryaddress,
            'transporttype'   => $transporttype,
            'freight_charges' => $freightcharges,
            'nooftray'        => $nooftray,
            'productid'       => $productid
        ];
        if ($transporttype === "delivered") {
            $arraydelivered[] = $entry;
        } else {
            $arraypickup[] = $entry;
        }
    }
    return [
        'arraydelivered' => $arraydelivered,
        'arraypickup'    => $arraypickup
    ];
}


?>
 
 