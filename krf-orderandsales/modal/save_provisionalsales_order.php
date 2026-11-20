<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('../../connection.php');
if (isset($krfconn) && $krfconn) {
    @$krfconn->set_charset("utf8mb4");
}
require_once('../../loginCell/functions/login_functions.php');
include_once("../functions/orders_functions.php");
include_once("../functions/product_functions.php");
include_once("../functions/client_functions.php");
include_once("../functions/emailsend_functions.php");

$companyid        = $_POST['companyid'] ?? '';
$subcompanyid     = $_POST['subcompanyid'] ?? '';
$projectid        = $_POST['projectid'] ?? '';
$loginid          = $_POST['loginid'] ?? '';
$clientid        = trim($_POST['clientid'] ?? '');
$clientname      = trim($_POST['clientname'] ?? '');
$mobileno        = $_POST['mobileno'] ?? '';
$emailid         = $_POST['emailid'] ?? '';
$delivery_address = $_POST['delivery_address'] ?? '';
$districtname    = $_POST['districtname'] ?? '';
$statename       = $_POST['statename'] ?? '';
$cityname        = $_POST['cityname'] ?? '';
$tahseel         = $_POST['tahseel'] ?? '';
$stateid         = $_POST['stateid'] ?? '';
$districtid      = $_POST['districtid'] ?? '';
$freight_amount =$_POST['freight_amount'] ?? 0;
$discount_percent   =$_POST['discount_percent'] ?? 0;
$payablefreight_amount  =$_POST['payablefreight_amount'] ?? 0;
$vehiclename = $_POST['vehiclename'] ?? '';
$vehicleno = $_POST['vehicleno'] ?? '';
$distancekm = $_POST['distancekm'] ?? 0;

$remarks         = $_POST['remarks'] ?? '';
$productlist     = $_POST['productname'] ?? [];
$quantities      = $_POST['quantity'] ?? [];
$rates          = $_POST['rate'] ?? [];

$taxableamount = $_POST['taxableamount'] ?? [];
$cgstpercent = $_POST['cgstprt'] ?? [];
$cgstamount = $_POST['cgstamt'] ?? [];
$sgstpercent = $_POST['sgstprt'] ?? [];
$sgstamount = $_POST['sgstamt'] ?? [];
$igstpercent = $_POST['igstprt'] ?? [];
$igstamount = $_POST['igstamt'] ?? [];

$discountpercent = $_POST['distprt'] ?? [];
$discountamount = $_POST['distamt'] ?? [];
$amounts         = $_POST['amount'] ?? [];
$roundoffamounts = $_POST['roundoffamount'] ?? [];
$expecteddelivery = $_POST['expecteddelivery'] ?? [];
$order_item_ids   = $_POST['order_item_id'] ?? [];
$removed_item_ids = $_POST['removed_item_ids'] ?? [];

// --- NEW DETAILED DELIVERY DATA (Step 3) ---
$d_productids   = $_POST['delivery_productid'] ?? $_POST['box_productid']  ?? [];
$d_quantities   = $_POST['delivery_qty']   ?? [];
$d_distances    = $_POST['delivery_distance'] ?? [];
$d_latlongs     = $_POST['delivery_latlong'] ?? [];
$d_states       = $_POST['delivery_state']  ?? [];
$d_statesid       = $_POST['delivery_stateid']  ?? [];

$d_districts    = $_POST['delivery_district'] ??   [];
$d_districtsid   = $_POST['delivery_district_id'] ??   [];
$d_cities       = $_POST['delivery_city'] ??   [];
$d_pincodes     = $_POST['delivery_pincode'] ?? [];
$d_fulladdresses = $_POST['delivery_fulladdress'] ??  [];

$d_boxpickuppoint = $_POST['pickuppoint'] ?? []; 
$d_boxfreightamt =  $_POST['box_freightamount'] ?? [];

$d_boxstate       =   $_POST['box_state_name'] ?? [];
$d_boxstate_id       =   $_POST['box_state_id'] ?? [];
$d_boxdistricts   =   $_POST['box_district_name'] ?? [];
$d_boxdistrict_id    =   $_POST['box_district_id'] ?? [];
$d_boxcity       =   $_POST['box_city_name'] ?? [];
$d_boxcity_id       =   $_POST['box_city_id'] ?? [];
$d_boxperpack= $_POST['plants_per_box']?? [];
$d_noofbox= $_POST['no_of_box']?? [];
$d_boxchargesperpack= $_POST['box_freighcharges']?? [];
$d_boxtotalamt= $_POST['box_freightamount']?? []; 
$d_boxqty=$_POST['box_product_qty']?? []; 

// --- NEW GROUPED FREIGHT DATA (Step 4) ---
$f_amounts      = $_POST['f_amount'] ??   [];
$f_discounts    = $_POST['f_discount'] ?? [];
$f_payables     = $_POST['f_payable'] ?? [];
$f_vehicles     = $_POST['f_vehicle'] ?? [];
$f_distance_km  = $_POST['f_distance'] ?? [];
$f_address_keys = $_POST['f_address_key'] ?? [];
$f_totaldistances   = $_POST['f_totaldistance'] ?? [];
$f_freedistances    = $_POST['f_freedistance'] ?? [];
$f_payabledistances = $_POST['f_payabledistance'] ?? [];
$f_narrations       = $_POST['f_narration'] ?? [];
$isreadytosale       = $_POST['isreadytosale'] ?? [];


$stockdbid= $_POST['stockdbid'] ?? [];
$stockorderno="";
$pickupfrom= "";
 if (isset($_POST['pickupfrom'])) {
    $pickupfrom = $_POST['pickupfrom'];
}

$sono="";
$isConfirmedOrder = false;
$isEditMode = (($_POST['mode'] ?? '') === 'edit' || !empty($_POST['edit_psono']));
if ($isEditMode) {
    $psono = trim($_POST['edit_psono'] ?? $_POST['psono'] ?? '');
    if (empty($psono)) {
        echo json_encode(["status" => "error", "message" => "Order number (PSONO) is missing for update."]);
        exit;
    }
    // Check if sales order exists for this psono (Confirmed sales order)
    $chkSo = $krfconn->prepare("SELECT sono, id, status FROM krf_sales_order WHERE psono = ? AND LOWER(TRIM(COALESCE(status,''))) NOT IN ('cancel','cancelled') LIMIT 1");
    if ($chkSo) {
        $chkSo->bind_param("s", $psono);
        $chkSo->execute();
        $soRow = $chkSo->get_result()->fetch_assoc();
        $chkSo->close();
        if ($soRow && !empty($soRow['sono'])) {
            $sono = $soRow['sono'];
            $isConfirmedOrder = true;
        }
    }
    if (empty($sono)) {
        $chkSoi = $krfconn->prepare("SELECT DISTINCT sono FROM krf_sales_order_items WHERE psono = ? AND sono != '' AND LOWER(TRIM(COALESCE(status,''))) NOT IN ('cancel','cancelled') LIMIT 1");
        if ($chkSoi) {
            $chkSoi->bind_param("s", $psono);
            $chkSoi->execute();
            $soiRow = $chkSoi->get_result()->fetch_assoc();
            $chkSoi->close();
            if ($soiRow && !empty($soiRow['sono'])) {
                $sono = $soiRow['sono'];
                $isConfirmedOrder = true;
            }
        }
    }
} else {
    // Generate PSO number
    $psono = getNewProvisionalSalesOrderNo($krfconn, $companyid, $subcompanyid, $projectid);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    // Basic validation (same as yours)
    if (empty($clientname) || empty($mobileno) || count($productlist) == 0 || count($expecteddelivery) == 0) {
        echo json_encode(["status" => "error", "message" => "Required fields are missing."]);
        $krfconn->rollback();
        exit;
    }
    $krfconn->begin_transaction();
    // Save or update client in krf_client_master
    if (!$clientid) {
        $response = saveClient($krfconn,$companyid,$subcompanyid,$projectid,$clientid,$clientname,$delivery_address,$mobileno,$emailid,$districtname,$statename,false,$cityname,$stateid,$districtid,$tahseel);

        if ($response['status'] === "success") {
            $clientid = $response['client_id'];
        } else {
            throw new Exception("Failed to save client: " . ($response['message'] ?? 'Unknown error'));
        }
    } else {
        if ($isEditMode) {
            // While edit the details of client, update in client master ONLY address up to 500 characters
            $safeAddress = mb_substr(trim($delivery_address), 0, 500, 'UTF-8');
            $upClient = $krfconn->prepare("UPDATE krf_client_master SET invoicing_address = ? WHERE client_id = ?");
            if ($upClient) {
                $upClient->bind_param("ss", $safeAddress, $clientid);
                $upClient->execute();
                $upClient->close();
            }
        } else {
            saveClient($krfconn,$companyid,$subcompanyid,$projectid,$clientid,$clientname,$delivery_address,$mobileno,$emailid,$districtname,$statename,true,$cityname,$stateid,$districtid,$tahseel);
        }
    }

    // 🚚 Prepare detailed delivery locations (Step 3) with resolved $clientid
    $freightByKey = [];
    if (!empty($f_address_keys)) {
        foreach ($f_address_keys as $idx => $key) {
            $freightByKey[(string)$key] = [
                'amount' => isset($f_amounts[$idx]) ? (float)$f_amounts[$idx] : 0.0,
                'payable' => isset($f_payables[$idx]) ? (float)$f_payables[$idx] : 0.0,
                'discount' => isset($f_discounts[$idx]) ? (float)$f_discounts[$idx] : 0.0,
                'vehicle' => $f_vehicles[$idx] ?? '',
                'totaldistance' => isset($f_totaldistances[$idx]) ? (float)$f_totaldistances[$idx] : 0.0,
                'freedistance' => isset($f_freedistances[$idx]) ? (float)$f_freedistances[$idx] : 0.0,
                'payabledistance' => isset($f_payabledistances[$idx]) ? (float)$f_payabledistances[$idx] : 0.0,
                'narration' => $f_narrations[$idx] ?? ''
            ];
        }
    }

    $deliveryaddres = [];
    if($pickupfrom==="boxpacking" ||$pickupfrom==="delivered"){
        foreach ($d_productids as $i => $prd_id) { 
            $acc_fulladd=null;
            $pickuppoint=null;
            $noofbox=null; 
            $bx_freight=null;
            $boxstates=null;
            $boxstateid= null; 
            $boxdistrict=null;
            $boxdistrictid=null;

            $boxcity =null;
            $boxcityid =null;
            $boxperpack =null;
            $boxchargesperpack=null;
            $boxtotalamt =null;
            $productqty=null;
            $row_deliverycharges = 0.0;
            $row_vehicleno = '';
            $row_totaldistance = 0.0;
            $row_freedistance = 0.0;
            $row_payabledistance = 0.0;
            $row_remark = '';

            if ($pickupfrom==="boxpacking"){
                $pickuppoint =$d_boxpickuppoint[$i] ?? ''; 
                $boxstates=$d_boxstate[$i] ?? '';
                $boxstateid=$d_boxstate_id[$i] ?? '';
                $boxdistrict=$d_boxdistricts[$i] ?? ''; 
                $boxdistrictid=$d_boxdistrict_id[$i] ?? '';
                $boxcity=$d_boxcity[$i] ?? '';
                $boxcityid= $d_boxcity_id[$i] ?? '';
                $productqty= $d_boxqty[$i] ?? 0;
                
                $acc_fulladd = $pickuppoint . " ". $boxstates . " ". $boxdistrict . " ". $boxcity ;

                $boxperpack=$d_boxperpack[$i] ?? 0;
                $noofbox=$d_noofbox[$i] ?? 0;
                $boxchargesperpack=$d_boxchargesperpack[$i] ?? 0;
                $bx_freight=$d_boxtotalamt[$i] ?? 0; 
                $row_deliverycharges = $boxchargesperpack;
            }else{
                $acc_fulladd =$d_fulladdresses[$i] ?? '';
                $boxstates =$d_states[$i] ?? '';
                $boxstateid =$d_statesid[$i] ?? '';
                $boxdistrict=$d_districts[$i] ?? '';
                $boxdistrictid =$d_districtsid[$i] ?? ''; 
                $productqty=$d_quantities[$i] ?? 0;
                $boxcity=$d_cities[$i] ?? '';

                $exp_date = $expecteddelivery[$i] ?? '';
                $grpKey = $boxcity . '|' . $boxdistrict . '|' . $acc_fulladd . '@@@' . $exp_date;
                $grpFreight = $freightByKey[$grpKey] ?? null;

                $row_gross_amt = 0.0;
                $row_payable_amt = 0.0;

                if ($grpFreight) {
                    $row_gross_amt = $grpFreight['amount'];
                    $row_payable_amt = $grpFreight['payable'];
                    $row_vehicleno = $grpFreight['vehicle'];
                    $row_totaldistance = ($grpFreight['totaldistance'] > 0) ? $grpFreight['totaldistance'] : (float)($d_distances[$i] ?? 0);
                    $row_freedistance = $grpFreight['freedistance'];
                    $row_payabledistance = ($grpFreight['payabledistance'] > 0 || $grpFreight['freedistance'] > 0) ? $grpFreight['payabledistance'] : $row_totaldistance;
                    $row_remark = $grpFreight['narration'];
                } else {
                    $row_gross_amt = isset($f_amounts[$i]) ? (float)$f_amounts[$i] : 0.0;
                    $disc_val = isset($f_discounts[$i]) ? (float)$f_discounts[$i] : 0.0;
                    if (isset($f_payables[$i]) && is_numeric($f_payables[$i])) {
                        $row_payable_amt = (float)$f_payables[$i];
                    } else {
                        $row_payable_amt = max(0.0, $row_gross_amt - ($row_gross_amt * ($disc_val / 100)));
                    }
                    $row_vehicleno = $f_vehicles[$i] ?? '';
                    $row_totaldistance = isset($f_totaldistances[$i]) && is_numeric($f_totaldistances[$i]) ? (float)$f_totaldistances[$i] : (float)($d_distances[$i] ?? ($f_distance_km[$i] ?? 0));
                    $row_freedistance = isset($f_freedistances[$i]) && is_numeric($f_freedistances[$i]) ? (float)$f_freedistances[$i] : 0.0;
                    $row_payabledistance = isset($f_payabledistances[$i]) && is_numeric($f_payabledistances[$i]) ? (float)$f_payabledistances[$i] : max(0.0, $row_totaldistance - $row_freedistance);
                    if (!empty($f_narrations[$i])) {
                        $row_remark = trim((string)$f_narrations[$i]);
                    } elseif ($row_freedistance > 0 || $disc_val > 0) {
                        $row_remark = "Discount: " . $disc_val . "%";
                    } else {
                        $row_remark = '';
                    }
                }

                $row_deliverycharges = $row_gross_amt;
                $bx_freight = $row_payable_amt;
            }
            
            // Split Latitude and Longitude
            $latlong = $d_latlongs[$i] ?? '';
            $lat = null; 
            $long = null;
            if ($latlong && strpos($latlong, ',') !== false) {
                $parts = explode(',', $latlong);
                $lat = trim($parts[0]);
                $long = trim($parts[1]);
            }

            $deliveryaddres[] = array(
                'id' => '',
                'projectid' => $projectid,
                'psono' => $psono,
                'sono' => $sono,
                'clientid' => $clientid,
                'addressforproduct' => $prd_id,
                'nooftray' => 0, 
                'deliverycharges' => $row_deliverycharges, 
                'deliveryaddress' => $acc_fulladd,
                'gpslocation' => $latlong,
                'vehicleno' => $row_vehicleno, 
                'drivername' => '',
                'drivercontactno' => '',
                'totaldistance' => $row_totaldistance,
                'freedistance' => $row_freedistance,
                'distance' => $row_payabledistance,
                'remark' => $row_remark,
                'dest_state' => $boxstates ?? '',
                'dest_stateid' => $boxstateid ?? '',
                'dest_district' => $boxdistrict ?? '',
                'dest_districtid' => $boxdistrictid ?? '',
                'dest_city' => $boxcity ?? '',
                'dest_cityid' => $boxcityid ?? '',
                'dest_pincode' => $d_pincodes[$i] ?? '',
                'delivery_qty' => $productqty ?? 0,
                'dest_latitude' => $lat,
                'dest_longitude' => $long,
                'status' => 'Pending',
                'created_by' => $loginid,
                'pickuppoint' => $pickuppoint,
                'pickuptype' => $pickupfrom,
                'noofbox'   => $noofbox,
                'boxperpack'   =>$boxperpack,
                'boxchargesperpack'   =>$boxchargesperpack,
                'boxtotalamt'   =>$bx_freight,
            );
        }  
     }    
 
    // Save or Update order header
    if ($isEditMode) {
        $hdrSql = "UPDATE krf_provisional_sales_order SET 
            clientid = ?, clientname = ?, mobileno = ?, emailid = ?, delivery_address = ?, districtname = ?, statename = ?, remarks = ?, pickupfrom = ?, updated_by = ?, updated_at = NOW()
            WHERE psono = ? AND projectid = ?";
        $hdrStmt = $krfconn->prepare($hdrSql);
        $hdrStmt->bind_param(
            "sssssssssssi",
            $clientid,
            $clientname,
            $mobileno,
            $emailid,
            $delivery_address,
            $districtname,
            $statename,
            $remarks,
            $pickupfrom,
            $loginid,
            $psono,
            $projectid
        );
        if (!$hdrStmt->execute()) {
            throw new Exception("Failed to update order header: " . $hdrStmt->error);
        }
        $hdrStmt->close();

        // If order has confirmed sales order, sync header details
        if ($isConfirmedOrder && !empty($sono)) {
            $upSoHdr = $krfconn->prepare("UPDATE krf_sales_order SET 
                clientid = ?, clientname = ?, mobileno = ?, emailid = ?, delivery_address = ?, address = ?, districtname = ?, statename = ?, remarks = ?, updated_by = ?, updated_at = NOW()
                WHERE sono = ? AND projectid = ?");
            if ($upSoHdr) {
                $upSoHdr->bind_param("sssssssssssi", $clientid, $clientname, $mobileno, $emailid, $delivery_address, $delivery_address, $districtname, $statename, $remarks, $loginid, $sono, $projectid);
                $upSoHdr->execute();
                $upSoHdr->close();
            }
        }

        // Soft-delete removed items with auto remark containing login person name & date
        $allSubmittedItemIds = array_filter(array_map('intval', $order_item_ids));
        
        $existingItemsQ = $krfconn->prepare("SELECT id, stockorder_no, product_id FROM krf_provisional_sales_order_items WHERE psono = ? AND projectid = ? AND (tedit IS NULL OR tedit = '')");
        if ($existingItemsQ) {
            $existingItemsQ->bind_param("si", $psono, $projectid);
            $existingItemsQ->execute();
            $exRes = $existingItemsQ->get_result();
            $autoRemark = "Removed by " . ($loginid ?: 'User') . " on " . date('Y-m-d H:i:s');
            while ($exRow = $exRes->fetch_assoc()) {
                $exId = intval($exRow['id']);
                $exStockOrderNo = $exRow['stockorder_no'] ?? '';
                $exProductId = intval($exRow['product_id'] ?? 0);
                $isRemovedExplicitly = in_array($exId, array_map('intval', $removed_item_ids));
                $isOmitted = !in_array($exId, $allSubmittedItemIds);
                if ($isRemovedExplicitly || $isOmitted) {
                    // Check sowing schedule & stock order status before removing
                    if (!empty($exStockOrderNo)) {
                        $isExSelfStock = false;
                        $exSowingStarted = false;
                        $stkClientId = '';
                        $chkStk = $krfconn->prepare("SELECT psono, quantity, sowingstatus, workingstatus, clientid FROM krf_production_stockorder WHERE stockorder_no = ? AND projectid = ? LIMIT 1");
                        if ($chkStk) {
                            $chkStk->bind_param("si", $exStockOrderNo, $projectid);
                            $chkStk->execute();
                            $stkData = $chkStk->get_result()->fetch_assoc();
                            $chkStk->close();
                            if ($stkData) {
                                $isExSelfStock = (!empty($stkData['psono']) && strcasecmp(trim($stkData['psono']), $psono) === 0);
                                $stkClientId = trim($stkData['clientid'] ?? '');
                                $sw = strtolower(trim($stkData['sowingstatus'] ?? ''));
                                $ws = strtolower(trim($stkData['workingstatus'] ?? ''));
                                if (in_array($sw, ['start', 'sowed', 'done']) || in_array($ws, ['polyhouse', 'final dispatch'])) {
                                    $exSowingStarted = true;
                                }
                            }
                        }

                        // Determine if production order client is a trader
                        $isTraderStock = false;
                        if ($stkClientId === 'C00001') {
                            $isTraderStock = true;
                        } elseif (!empty($stkClientId)) {
                            $trQ = $krfconn->query("SELECT is_trader FROM krf_client_master WHERE client_id = '{$krfconn->real_escape_string($stkClientId)}' LIMIT 1");
                            if ($trQ && $trR = $trQ->fetch_assoc()) {
                                $isTraderStock = (intval($trR['is_trader'] ?? 0) === 1);
                            }
                        }

                        // Check if at least 1 single tray has been sown in krf_polyhouse_sowingplant_details
                        $hasSingleTraySown = false;
                        $chkSowTray = $krfconn->prepare("SELECT COUNT(*) as sowed_cnt FROM krf_polyhouse_sowingplant_details WHERE stockorder_no = ? OR (psono = ? AND productid = ?)");
                        if ($chkSowTray) {
                            $chkSowTray->bind_param("ssi", $exStockOrderNo, $psono, $exProductId);
                            $chkSowTray->execute();
                            $sowTrayRow = $chkSowTray->get_result()->fetch_assoc();
                            $chkSowTray->close();
                            if (!empty($sowTrayRow['sowed_cnt']) && (int)$sowTrayRow['sowed_cnt'] > 0) {
                                $hasSingleTraySown = true;
                            }
                        }

                        // Check dispatched quantity in krf_sales_order_items if order is confirmed
                        if (!empty($sono)) {
                            $chkDisp = $krfconn->prepare("SELECT dispatchedqty FROM krf_sales_order_items WHERE sono = ? AND (stockorder_no = ? OR (product_id = ? AND psono = ?)) AND LOWER(TRIM(COALESCE(status,''))) NOT IN ('cancel','cancelled') LIMIT 1");
                            if ($chkDisp) {
                                $chkDisp->bind_param("ssis", $sono, $exStockOrderNo, $exProductId, $psono);
                                $chkDisp->execute();
                                $dispRow = $chkDisp->get_result()->fetch_assoc();
                                $chkDisp->close();
                                if ($dispRow && (float)($dispRow['dispatchedqty'] ?? 0) > 0) {
                                    throw new Exception("Cannot remove item with stock order {$exStockOrderNo}: {$dispRow['dispatchedqty']} plants have already been dispatched against sales order {$sono}.");
                                }
                            }
                        }
                    }

                    // 1. Soft delete in krf_provisional_sales_order_items
                    $delStmt = $krfconn->prepare("UPDATE krf_provisional_sales_order_items SET tedit = ?, remark = CONCAT(IFNULL(remark,''), ' | ', ?), status = 'cancelled' WHERE id = ?");
                    if ($delStmt) {
                        $delStmt->bind_param("ssi", $autoRemark, $autoRemark, $exId);
                        $delStmt->execute();
                        $delStmt->close();
                    }

                    // 2. Cancel in krf_sales_order_items if order is confirmed or linked in sales items
                    if (!empty($sono) || !empty($psono)) {
                        $upSoiRem = $krfconn->prepare("UPDATE krf_sales_order_items SET 
                            status = 'cancelled', quantity = 0, amount = 0, taxableamount = 0, 
                            cgst_amount = 0, sgst_amount = 0, igst_amount = 0, roundoffamount = 0, 
                            freight_charges = 0, shiftingqty = 0, ammendedby = ?, updateamended = NOW(), 
                            remark = CONCAT(IFNULL(remark,''), ' | ', ?) 
                            WHERE ((sono != '' AND sono = ?) OR psono = ?) AND (stockorder_no = ? OR (product_id = ? AND psono = ?)) AND LOWER(TRIM(COALESCE(status,''))) NOT IN ('cancel','cancelled')");
                        if ($upSoiRem) {
                            $upSoiRem->bind_param("sssssis", $loginid, $autoRemark, $sono, $psono, $exStockOrderNo, $exProductId, $psono);
                            $upSoiRem->execute();
                            $upSoiRem->close();
                        }
                    }

                    if (!empty($exStockOrderNo)) {
                        // 3. Update krf_productionorder_consumed to zero consumed qty and cancelled
                        $upConsumed = $krfconn->prepare("UPDATE krf_productionorder_consumed SET consumed_qty = 0, status = 'cancelled', confirmstatus = 'cancelled', remark = CONCAT(IFNULL(remark,''), ' | ', ?) WHERE psono = ? AND stockorderno = ? AND projectid = ? AND product_id = ?");
                        if ($upConsumed) {
                            $upConsumed->bind_param("sssii", $autoRemark, $psono, $exStockOrderNo, $projectid, $exProductId);
                            $upConsumed->execute();
                            $upConsumed->close();
                        }

                        // 4. Update status and working status of krf_production_stockorder:
                        // If single tray sowing has started in krf_polyhouse_sowingplant_details,
                        // DO NOT mark cancel or remove in krf_production_stockorder!
                        // The production stock order remains active so the sown product will show in Open Stock for sale to another client.
                        if ($hasSingleTraySown) {
                            $upStkRem = $krfconn->prepare("UPDATE krf_production_stockorder SET editedremark = CONCAT(IFNULL(editedremark,''), ' | Item removed from client order {$psono}, kept active for open stock on ', NOW()) WHERE stockorder_no = ? AND projectid = ?");
                            if ($upStkRem) {
                                $upStkRem->bind_param("si", $exStockOrderNo, $projectid);
                                $upStkRem->execute();
                                $upStkRem->close();
                            }
                        } else {
                            // If no sowing has started at all, cancel/remove the production stock order and sowing schedule
                            if ($isExSelfStock) {
                                $upStkRem = $krfconn->prepare("UPDATE krf_production_stockorder SET status = 'remove', workingstatus = 'remove', editedremark = CONCAT(IFNULL(editedremark,''), ' | ', ?) WHERE stockorder_no = ? AND projectid = ?");
                                if ($upStkRem) {
                                    $upStkRem->bind_param("ssi", $autoRemark, $exStockOrderNo, $projectid);
                                    $upStkRem->execute();
                                    $upStkRem->close();
                                }

                                // 5. Update status of krf_polyhouse_sowingschedule
                                $upSchRem = $krfconn->prepare("UPDATE krf_polyhouse_sowingschedule SET status = 'removed', remarks = CONCAT(IFNULL(remarks,''), '\n', ?), updated_by = ?, updated_at = NOW() WHERE stockorder_no = ? AND projectid = ? AND status = 'pending'");
                                if ($upSchRem) {
                                    $upSchRem->bind_param("sssi", $autoRemark, $loginid, $exStockOrderNo, $projectid);
                                    $upSchRem->execute();
                                    $upSchRem->close();
                                }
                            }
                        }
                    }
                }
            }
            $existingItemsQ->close();
        }

        // Clear delivery locations to re-populate from current submission
        $delLoc = $krfconn->prepare("DELETE FROM krf_deliveryaddress_location WHERE psono = ? AND projectid = ?");
        if ($delLoc) {
            $delLoc->bind_param("si", $psono, $projectid);
            $delLoc->execute();
            $delLoc->close();
        }
    } else {
        $hdrSql = "INSERT INTO krf_provisional_sales_order 
          (psono, companyid, subcompanyid, projectid, clientid, clientname, mobileno, emailid, delivery_address, districtname, statename, remarks, created_by,pickupfrom)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)";
        $hdrStmt = $krfconn->prepare($hdrSql);

        $hdrStmt->bind_param( 
            "siiissssssssss",
            $psono,
            $companyid,
            $subcompanyid,
            $projectid,
            $clientid,
            $clientname,
            $mobileno,
            $emailid,
            $delivery_address,
            $districtname,
            $statename,
            $remarks,
            $loginid,
            $pickupfrom
        );

        if (!$hdrStmt->execute()) {
            throw new Exception("Failed to insert order header: " . $hdrStmt->error);
        }
        $hdrStmt->close();
    }
    

    // 🚚 Calculate Total Freight Charges & Map Group Payables (Step 4)
    $is_zero_charge = 'no';
    if (!empty($_POST['f_zero_charges'])) {
        if (is_array($_POST['f_zero_charges'])) {
            foreach ($_POST['f_zero_charges'] as $val) {
                if ($val === 'yes' || $val === '1' || $val === 1 || $val === true) {
                    $is_zero_charge = 'yes';
                    break;
                }
            }
        } elseif ($_POST['f_zero_charges'] === 'yes' || $_POST['f_zero_charges'] === '1' || $_POST['f_zero_charges'] === 1) {
            $is_zero_charge = 'yes';
        }
    }
    if (!empty($_POST['zerocharge']) && ($_POST['zerocharge'] === 'yes' || $_POST['zerocharge'] === '1' || $_POST['zerocharge'] === 1)) {
        $is_zero_charge = 'yes';
    }

    $total_f_amount = 0;
    $total_f_payable = 0;
    $all_f_remarks = [];
    $group_freight_map = [];

    if (!empty($f_amounts)) {
        foreach ($f_amounts as $i => $f_amt) {
            $amt = (float)$f_amt;

            $disc = (float)($f_discounts[$i] ?? 0);
            $payable = isset($f_payables[$i]) && (float)$f_payables[$i] > 0 
                ? (float)$f_payables[$i] 
                : round($amt - ($amt * ($disc / 100)), 2);

            $v_name = $f_vehicles[$i] ?? '';
            $v_dist = $f_distance_km[$i] ?? 0;
            $addr_key = $f_address_keys[$i] ?? ''; // city|district|address@@@date

            $total_f_amount += $amt;
            $total_f_payable += $payable;

            $g_date = '';
            if (strpos($addr_key, '@@@') !== false) {
                $parts = explode('@@@', $addr_key);
                $g_date = trim($parts[1] ?? '');
            }

            $group_freight_map[] = [
                'amt' => $amt,
                'payable' => $payable,
                'disc' => $disc,
                'date' => $g_date,
                'key' => $addr_key
            ];

            $tripZeroNote = ($is_zero_charge === 'yes' || $amt == 0) ? " [0 (Zero) Charges]" : "";
            $all_f_remarks[] = "Trip " . ($i + 1) . ": Amt Rs " . number_format($amt, 2) . ", Payable Rs " . number_format($payable, 2) . " ($v_name, $v_dist Km, Loc: $addr_key)$tripZeroNote";
        }
    } else {
        $total_f_amount = (float)($freight_amount ?? 0);
        $total_f_payable = (float)($payablefreight_amount ?? ($total_f_amount - ($total_f_amount * ((float)$discount_percent / 100))));
    }

    $avg_discount_percent = $total_f_amount > 0 
        ? round((($total_f_amount - $total_f_payable) / $total_f_amount) * 100, 2) 
        : (float)($discount_percent ?? 0);

    // Calculate total order quantity across all items
    $total_order_qty = 0;
    for ($k = 0; $k < count($productlist); $k++) {
        $total_order_qty += (float)($quantities[$k] ?? 0);
    }

    // Prepare item insert with freight_charges and roundoffamount columns
    $itemSql = "INSERT INTO krf_provisional_sales_order_items 
      (projectid, clientid, psono, product_sku, product_id, product_name, quantity, rate, freight_charges, taxableamount, cgst_percent, cgst_amount, sgst_percent, sgst_amount, igst_percent, igst_amount, discount_percent, discount_amount, amount, roundoffamount, expecteddeliverydate, created_by, stockorder_no)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $itemStmt = $krfconn->prepare($itemSql);

    $types = "isssssddddddddddddddsss";
    // Loop and insert each item  
    for ($i = 0; $i < count($productlist); $i++) {
        $prdin = getProductData($krfconn, $projectid, "ProductID", trim($productlist[$i]));
        if (empty($prdin)) {
            throw new Exception("Product not found for ID: " . $productlist[$i]);
        }

        $productsku       = $prdin[0]['sku'];
        $productid        = trim($productlist[$i]);
        $productName      = $prdin[0]['ProductName'];
        $qty              = (float) trim($quantities[$i]);
        $rate             = (float) $rates[$i];
        $taxableAmount  =   (float)$taxableamount[$i];
        $cgstPercent    =   (float)$cgstpercent[$i];
        $cgstAmount =   (float)$cgstamount[$i];
        $sgstPercent    =   (float)$sgstpercent[$i];
        $sgstAmount =   (float)$sgstamount[$i];
        $igstPercent    =   (float)$igstpercent[$i];
        $igstAmount =   (float)$igstamount[$i];
        $discountPercent  = (float) $discountpercent[$i];
        $discountAmount   = (float) $discountamount[$i];
        $amt              = (float) $amounts[$i];
        if (isset($roundoffamounts[$i]) && is_numeric($roundoffamounts[$i])) {
            $roundoffamount = (float) $roundoffamounts[$i];
        } else {
            $unrounded = ($rate * $qty) - $discountAmount;
            $roundoffamount = round($amt - $unrounded, 2);
        }
        $expdate          = $expecteddelivery[$i];
        $stock_id            = (float) $stockdbid[$i];

        $readytosale     =  (int)$isreadytosale[$i]; 
        // Calculate item's proportional freight charges based on product qty & group
        $item_freight_charge = 0.00;
        if ($qty > 0 && $total_f_payable > 0) {
            $matched_group = null;
            foreach ($group_freight_map as $g_info) {
                if (!empty($g_info['date']) && $g_info['date'] === $expdate) {
                    $matched_group = $g_info;
                    break;
                }
            }

            if ($matched_group && $matched_group['payable'] > 0) {
                $group_total_qty = 0;
                for ($j = 0; $j < count($productlist); $j++) {
                    if (($expecteddelivery[$j] ?? '') === $matched_group['date']) {
                        $group_total_qty += (float)($quantities[$j] ?? 0);
                    }
                }
                if ($group_total_qty > 0) {
                    $item_freight_charge = round(($qty / $group_total_qty) * $matched_group['payable'], 2);
                } else if ($total_order_qty > 0) {
                    $item_freight_charge = round(($qty / $total_order_qty) * $total_f_payable, 2);
                }
            } else {
                if ($total_order_qty > 0) {
                    $item_freight_charge = round(($qty / $total_order_qty) * $total_f_payable, 2);
                }
            }
        }

        $curr_item_id = intval($order_item_ids[$i] ?? 0);
        $existingItem = null;
        if ($isEditMode && $curr_item_id > 0) {
            $chkItem = $krfconn->prepare("SELECT id, stockorder_no, quantity, expecteddeliverydate FROM krf_provisional_sales_order_items WHERE id = ? AND psono = ? LIMIT 1");
            if ($chkItem) {
                $chkItem->bind_param("is", $curr_item_id, $psono);
                $chkItem->execute();
                $existingItem = $chkItem->get_result()->fetch_assoc();
                $chkItem->close();
            }
        }

        if ($existingItem && $curr_item_id > 0) {
            // Existing item: UPDATE ONLY, do not generate a new stock order or duplicate entry
            $stockorderno = $existingItem['stockorder_no'] ?? '';
            $prevQty = (float)($existingItem['quantity'] ?? 0);
            $prevExpDate = trim((string)($existingItem['expecteddeliverydate'] ?? ''));

            // Check if item has already been dispatched against confirmed sales order
            if (!empty($sono)) {
                $chkDisp = $krfconn->prepare("SELECT dispatchedqty FROM krf_sales_order_items WHERE sono = ? AND (stockorder_no = ? OR (product_id = ? AND psono = ?)) AND LOWER(TRIM(COALESCE(status,''))) NOT IN ('cancel','cancelled') LIMIT 1");
                if ($chkDisp) {
                    $chkDisp->bind_param("ssis", $sono, $stockorderno, $productid, $psono);
                    $chkDisp->execute();
                    $dispRow = $chkDisp->get_result()->fetch_assoc();
                    $chkDisp->close();
                    if ($dispRow && (float)($dispRow['dispatchedqty'] ?? 0) > 0 && $qty < (float)$dispRow['dispatchedqty']) {
                        throw new Exception("Cannot reduce quantity to {$qty} for '{$productName}': {$dispRow['dispatchedqty']} plants have already been dispatched against sales order {$sono}.");
                    }
                }
            }

            // Check if sowing schedule & stock order allows modification
            if (!empty($stockorderno)) {
                $isSelfStock = false;
                $sowingStarted = false;
                $sowingQty = 0;

                // 1. Check krf_production_stockorder
                $chkStk = $krfconn->prepare("SELECT psono, quantity, sowingstatus, workingstatus, clientid FROM krf_production_stockorder WHERE stockorder_no = ? AND projectid = ? LIMIT 1");
                $stkClientId = '';
                if ($chkStk) {
                    $chkStk->bind_param("si", $stockorderno, $projectid);
                    $chkStk->execute();
                    $stkData = $chkStk->get_result()->fetch_assoc();
                    $chkStk->close();
                    if ($stkData) {
                        $isSelfStock = (!empty($stkData['psono']) && strcasecmp(trim($stkData['psono']), $psono) === 0);
                        $stkClientId = trim($stkData['clientid'] ?? '');
                        $sowingQty = (int)($stkData['quantity'] ?? 0);
                        $sw = strtolower(trim($stkData['sowingstatus'] ?? ''));
                        $ws = strtolower(trim($stkData['workingstatus'] ?? ''));
                        if (in_array($sw, ['start', 'sowed', 'done']) || in_array($ws, ['polyhouse', 'final dispatch'])) {
                            $sowingStarted = true;
                        }
                    }
                }

                // Determine if production order client is a trader
                $isTraderStock = false;
                if ($stkClientId === 'C00001') {
                    $isTraderStock = true;
                } elseif (!empty($stkClientId)) {
                    $trQ = $krfconn->query("SELECT is_trader FROM krf_client_master WHERE client_id = '{$krfconn->real_escape_string($stkClientId)}' LIMIT 1");
                    if ($trQ && $trR = $trQ->fetch_assoc()) {
                        $isTraderStock = (intval($trR['is_trader'] ?? 0) === 1);
                    }
                }

                // 2. Check krf_polyhouse_sowingschedule
                $chkSch = $krfconn->prepare("SELECT status, sowingqty FROM krf_polyhouse_sowingschedule WHERE stockorder_no = ? AND projectid = ? AND LOWER(TRIM(status)) NOT IN ('delete','removed','notrequired')");
                if ($chkSch) {
                    $chkSch->bind_param("si", $stockorderno, $projectid);
                    $chkSch->execute();
                    $rSch = $chkSch->get_result();
                    while ($schRow = $rSch->fetch_assoc()) {
                        $st = strtolower(trim($schRow['status'] ?? ''));
                        if ($st !== 'pending') {
                            $sowingStarted = true;
                        }
                        if (!empty($schRow['sowingqty']) && (int)$schRow['sowingqty'] > 0) {
                            $sowingQty = max($sowingQty, (int)$schRow['sowingqty']);
                        }
                    }
                    $chkSch->close();
                }

                // 3. Check tray details in krf_polyhouse_sowingplant_details
                if (!$sowingStarted) {
                    $trStmt = $krfconn->prepare("SELECT COUNT(*) as sowed_cnt FROM krf_polyhouse_sowingplant_details WHERE stockorder_no = ? AND projectid = ? AND sowingdate_internal IS NOT NULL AND sowingdate_internal > '1970-01-01'");
                    if ($trStmt) {
                        $trStmt->bind_param("si", $stockorderno, $projectid);
                        $trStmt->execute();
                        $trRow = $trStmt->get_result()->fetch_assoc();
                        $trStmt->close();
                        if (!empty($trRow['sowed_cnt']) && (int)$trRow['sowed_cnt'] > 0) {
                            $sowingStarted = true;
                        }
                    }
                }

                if ($sowingQty <= 0) {
                    $sowingQty = (int)$prevQty;
                }

                if ($sowingStarted && !$isTraderStock) {
                    if ($isSelfStock) {
                        // Own production stock order with sowing started:
                        // 1. Delivery date cannot be changed once sowing has begun
                        if ($expdate !== $prevExpDate) {
                            throw new Exception("Cannot change expected delivery date for item '{$productName}' with stock order {$stockorderno}: Sowing has already started.");
                        }
                        // 2. Quantity rule: orderqty <= sowingqty (cannot exceed sown quantity without sowing department)
                        if ($qty > $sowingQty) {
                            throw new Exception("Order quantity ({$qty}) cannot exceed sowing quantity ({$sowingQty}) for item '{$productName}'. To increase order quantity above {$sowingQty}, please discuss and coordinate with the Sowing Department.");
                        }
                    } else {
                        // Stock order not owned by this order: strict lock if sowing is in progress
                        if (abs($qty - $prevQty) > 0.001 || $expdate !== $prevExpDate) {
                            throw new Exception("Cannot edit item '{$productName}' with stock order {$stockorderno}: Sowing schedule is in progress. Only items with pending schedule can be modified against {$psono}.");
                        }
                    }
                }
            }

            // Check if re-allocated to a different production order
            $newStockOrderNo = '';
            if ($stock_id > 0) {
                $newStockDetails = getFullProductionStockOrdersDetails($krfconn, $projectid, "id", $stock_id);
                $newStockOrderNo = $newStockDetails[0]['stockorder_no'] ?? '';
            }

            $isReallocated = (!empty($stockorderno) && !empty($newStockOrderNo) && $stockorderno !== $newStockOrderNo);
            $reallocTedit = null;
            $reallocRemark = '';

            if ($isReallocated) {
                // Check if old stock order was client's own production stock order (itself production order)
                $isSelfStock = false;
                $chkSelf = $krfconn->prepare("SELECT clientid FROM krf_production_stockorder WHERE stockorder_no = ? AND projectid = ? LIMIT 1");
                if ($chkSelf) {
                    $chkSelf->bind_param("si", $stockorderno, $projectid);
                    $chkSelf->execute();
                    $selfRow = $chkSelf->get_result()->fetch_assoc();
                    $chkSelf->close();
                    if ($selfRow && strtoupper(trim((string)$selfRow['clientid'])) === strtoupper(trim((string)$clientid))) {
                        $isSelfStock = true;
                    }
                }

                $selfRemarkText = $isSelfStock ? "itself production order" : "production order";
                $reallocTedit = "Reallocated from {$selfRemarkText} ({$stockorderno}) to ({$newStockOrderNo}) by " . ($loginid ?: 'User') . " on " . date('Y-m-d H:i:s');
                $reallocRemark = "Reallocated from {$selfRemarkText} ({$stockorderno}) to ({$newStockOrderNo})";

                // Item allocated to another production order:
                // 1. Update status and working status of OLD production stock order
                $upOldStock = $krfconn->prepare("UPDATE krf_production_stockorder SET 
                    status = 'remove', 
                    workingstatus = 'remove', 
                    editedremark = CONCAT(IFNULL(editedremark,''), ' | ', ?, ' by ', ?, ' on ', NOW()) 
                    WHERE stockorder_no = ? AND projectid = ?");
                if ($upOldStock) {
                    $upOldStock->bind_param("sssi", $reallocRemark, $loginid, $stockorderno, $projectid);
                    $upOldStock->execute();
                    $upOldStock->close();
                }

                // 2. Update status of krf_polyhouse_sowingschedule for OLD stock order
                $upOldSch = $krfconn->prepare("UPDATE krf_polyhouse_sowingschedule SET 
                    status = 'removed', 
                    remarks = CONCAT(IFNULL(remarks,''), '\n', ?, ' by ', ?, ' on ', NOW()), 
                    updated_by = ?, 
                    updated_at = NOW() 
                    WHERE stockorder_no = ? AND projectid = ? AND status = 'pending'");
                if ($upOldSch) {
                    $upOldSch->bind_param("ssssi", $reallocRemark, $loginid, $loginid, $stockorderno, $projectid);
                    $upOldSch->execute();
                    $upOldSch->close();
                }

                // 3. Update old consumed record for OLD stock order to cancelled
                $upOldCons = $krfconn->prepare("UPDATE krf_productionorder_consumed SET status = 'cancelled', confirmstatus = 'cancelled', remark = ? WHERE psono = ? AND stockorderno = ? AND projectid = ? AND product_id = ?");
                if ($upOldCons) {
                    $upOldCons->bind_param("sssii", $reallocRemark, $psono, $stockorderno, $projectid, $productid);
                    $upOldCons->execute();
                    $upOldCons->close();
                }

                // 4. Set current stock order to the new one
                $stockorderno = $newStockOrderNo;
            }

            if ($isReallocated) {
                $upItemSql = "UPDATE krf_provisional_sales_order_items SET 
                    quantity = ?, rate = ?, freight_charges = ?, taxableamount = ?, cgst_percent = ?, cgst_amount = ?, 
                    sgst_percent = ?, sgst_amount = ?, igst_percent = ?, igst_amount = ?, discount_percent = ?, discount_amount = ?, 
                    amount = ?, roundoffamount = ?, expecteddeliverydate = ?, stockorder_no = ?, tedit = ?, remark = CONCAT(IFNULL(remark,''), ' | ', ?)
                    WHERE id = ? AND psono = ? AND projectid = ?";
                $upItemStmt = $krfconn->prepare($upItemSql);
                if (!$upItemStmt) {
                    throw new Exception("Prepare failed for update item: " . $krfconn->error);
                }
                $upItemStmt->bind_param(
                    "ddddddddddddddssssisi",
                    $qty,
                    $rate,
                    $item_freight_charge,
                    $taxableAmount,
                    $cgstPercent,
                    $cgstAmount,
                    $sgstPercent,
                    $sgstAmount,
                    $igstPercent,
                    $igstAmount,
                    $discountPercent,
                    $discountAmount,
                    $amt,
                    $roundoffamount,
                    $expdate,
                    $stockorderno,
                    $reallocTedit,
                    $reallocRemark,
                    $curr_item_id,
                    $psono,
                    $projectid
                );
            } else {
                $upItemSql = "UPDATE krf_provisional_sales_order_items SET 
                    quantity = ?, rate = ?, freight_charges = ?, taxableamount = ?, cgst_percent = ?, cgst_amount = ?, 
                    sgst_percent = ?, sgst_amount = ?, igst_percent = ?, igst_amount = ?, discount_percent = ?, discount_amount = ?, 
                    amount = ?, roundoffamount = ?, expecteddeliverydate = ?, stockorder_no = ?
                    WHERE id = ? AND psono = ? AND projectid = ?";
                $upItemStmt = $krfconn->prepare($upItemSql);
                if (!$upItemStmt) {
                    throw new Exception("Prepare failed for update item: " . $krfconn->error);
                }
                $upItemStmt->bind_param(
                    "ddddddddddddddssisi",
                    $qty,
                    $rate,
                    $item_freight_charge,
                    $taxableAmount,
                    $cgstPercent,
                    $cgstAmount,
                    $sgstPercent,
                    $sgstAmount,
                    $igstPercent,
                    $igstAmount,
                    $discountPercent,
                    $discountAmount,
                    $amt,
                    $roundoffamount,
                    $expdate,
                    $stockorderno,
                    $curr_item_id,
                    $psono,
                    $projectid
                );
            }
            if (!$upItemStmt->execute()) {
                throw new Exception("Failed to update order item: " . $upItemStmt->error);
            }
            $upItemStmt->close();

            // If stock order exists and was NOT reallocated (same stock order), update quantity in stock order & sowing schedule or consumed record
            if (!empty($stockorderno) && !$isReallocated) {
                if (abs($qty - $prevQty) > 0.001) {
                    if ($isSelfStock) {
                        $upStock = $krfconn->prepare("UPDATE krf_production_stockorder SET 
                            quantity = ?, editedqty = ?, editedby = ?, editedon = NOW(), 
                            editedremark = CONCAT(IFNULL(editedremark,''), ' | Qty updated to ', ?, ' by ', ?, ' on ', NOW()) 
                            WHERE stockorder_no = ? AND projectid = ?");
                        if ($upStock) {
                            $qtyInt = intval($qty);
                            $qtyStr = strval($qty);
                            $upStock->bind_param("iissssi", $qtyInt, $qtyInt, $loginid, $qtyStr, $loginid, $stockorderno, $projectid);
                            $upStock->execute();
                            $upStock->close();
                        }

                        // Also update quantity in krf_polyhouse_sowingschedule
                        $chk_sch = $krfconn->prepare("SELECT sowingschedulid, failurebufferpercent, status FROM krf_polyhouse_sowingschedule WHERE stockorder_no = ? AND projectid = ?");
                        if ($chk_sch) {
                            $chk_sch->bind_param("ss", $stockorderno, $projectid);
                            $chk_sch->execute();
                            $res_sch = $chk_sch->get_result()->fetch_all(MYSQLI_ASSOC);
                            $chk_sch->close();
                            if (!empty($res_sch)) {
                                foreach ($res_sch as $sch) {
                                    $schSt = strtolower(trim($sch['status'] ?? ''));
                                    if ($schSt === 'pending') {
                                        $buf = (float)($sch['failurebufferpercent'] ?? 30.0);
                                        $newSowingQty = (int)ceil($qty * (1 + $buf / 100));
                                        $upSch = $krfconn->prepare("UPDATE krf_polyhouse_sowingschedule SET orderqty = ?, sowingqty = ?, remarks = CONCAT(IFNULL(remarks,''), '\nQty updated to ', ?, ' on ', NOW()), updated_by = ?, updated_at = NOW() WHERE sowingschedulid = ?");
                                        if ($upSch) {
                                            $qtyInt = (int)$qty;
                                            $upSch->bind_param("iisii", $qtyInt, $newSowingQty, $qtyInt, $loginid, $sch['sowingschedulid']);
                                            $upSch->execute();
                                            $upSch->close();
                                        }
                                    } else {
                                        // Sowing in progress or done: update orderqty without altering already sown qty
                                        $upSch = $krfconn->prepare("UPDATE krf_polyhouse_sowingschedule SET orderqty = ?, remarks = CONCAT(IFNULL(remarks,''), '\nOrder qty updated to ', ?, ' (Sown qty preserved) on ', NOW()), updated_by = ?, updated_at = NOW() WHERE sowingschedulid = ?");
                                        if ($upSch) {
                                            $qtyInt = (int)$qty;
                                            $upSch->bind_param("isii", $qtyInt, $qtyInt, $loginid, $sch['sowingschedulid']);
                                            $upSch->execute();
                                            $upSch->close();
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        // Consumed stock order: update consumed quantity in krf_productionorder_consumed
                        $upPoc = $krfconn->prepare("UPDATE krf_productionorder_consumed SET consumed_qty = ?, updated_at = NOW() WHERE psono = ? AND stockorderno = ? AND projectid = ? AND product_id = ? AND LOWER(TRIM(confirmstatus)) != 'cancelled'");
                        if ($upPoc) {
                            $upPoc->bind_param("dssii", $qty, $psono, $stockorderno, $projectid, $productid);
                            $upPoc->execute();
                            $upPoc->close();
                        }
                    }
                }

                // If order is confirmed, synchronize item with krf_sales_order_items
                if (!empty($sono)) {
                    $soiId = null;
                    if (!empty($stockorderno)) {
                        $chkSoi = $krfconn->prepare("SELECT id FROM krf_sales_order_items WHERE sono = ? AND stockorder_no = ? AND product_id = ? AND LOWER(TRIM(COALESCE(status,''))) NOT IN ('cancel','cancelled') LIMIT 1");
                        if ($chkSoi) {
                            $chkSoi->bind_param("ssi", $sono, $stockorderno, $productid);
                            $chkSoi->execute();
                            $rSoi = $chkSoi->get_result()->fetch_assoc();
                            $chkSoi->close();
                            if ($rSoi) $soiId = $rSoi['id'];
                        }
                    }
                    if (!$soiId) {
                        $chkSoi2 = $krfconn->prepare("SELECT id FROM krf_sales_order_items WHERE (sono = ? OR psono = ?) AND product_id = ? AND LOWER(TRIM(COALESCE(status,''))) NOT IN ('cancel','cancelled') LIMIT 1");
                        if ($chkSoi2) {
                            $chkSoi2->bind_param("ssi", $sono, $psono, $productid);
                            $chkSoi2->execute();
                            $rSoi2 = $chkSoi2->get_result()->fetch_assoc();
                            $chkSoi2->close();
                            if ($rSoi2) $soiId = $rSoi2['id'];
                        }
                    }

                    $newShiftingQty = (int)ceil($qty / 125);
                    if ($soiId) {
                        $upSoi = $krfconn->prepare("UPDATE krf_sales_order_items SET 
                            stockorder_no = ?, product_id = ?, product_name = ?, product_sku = ?, 
                            quantity = ?, rate = ?, freight_charges = ?, taxableamount = ?, 
                            cgst_percent = ?, cgst_amount = ?, sgst_percent = ?, sgst_amount = ?, 
                            igst_percent = ?, igst_amount = ?, discount_percent = ?, discount_amount = ?, 
                            amount = ?, roundoffamount = ?, deliverydate = ?, shiftingqty = ?, 
                            ammendedby = ?, updateamended = NOW() 
                            WHERE id = ?");
                        if ($upSoi) {
                            $upSoi->bind_param(
                                "ssssddddddddddddddsisi",
                                $stockorderno, $productid, $productName, $productsku,
                                $qty, $rate, $item_freight_charge, $taxableAmount,
                                $cgstPercent, $cgstAmount, $sgstPercent, $sgstAmount,
                                $igstPercent, $igstAmount, $discountPercent, $discountAmount,
                                $amt, $roundoffamount, $expdate, $newShiftingQty,
                                $loginid, $soiId
                            );
                            $upSoi->execute();
                            $upSoi->close();
                        }
                    } else {
                        $insSoi = $krfconn->prepare("INSERT INTO krf_sales_order_items (
                            sono, psono, stockorder_no, projectid, clientid, product_sku, product_id, product_name,
                            quantity, rate, taxableamount, cgst_percent, cgst_amount, sgst_percent, sgst_amount,
                            igst_percent, igst_amount, discount_percent, discount_amount, freight_charges,
                            tax_amount, amount, roundoffamount, deliverydate, created_by, status, shiftingqty,
                            ammendedby, updateamended
                        ) VALUES (
                            ?, ?, ?, ?, ?, ?, ?, ?,
                            ?, ?, ?, ?, ?, ?, ?,
                            ?, ?, ?, ?, ?,
                            0, ?, ?, ?, ?, 'Confirmed', ?,
                            ?, NOW()
                        )");
                        if ($insSoi) {
                            $insSoi->bind_param(
                                "sssissisddddddddddddddssis",
                                $sono, $psono, $stockorderno, $projectid, $clientid, $productsku, $productid, $productName,
                                $qty, $rate, $taxableAmount, $cgstPercent, $cgstAmount, $sgstPercent, $sgstAmount,
                                $igstPercent, $igstAmount, $discountPercent, $discountAmount, $item_freight_charge,
                                $amt, $roundoffamount, $expdate, $loginid, $newShiftingQty, $loginid
                            );
                            $insSoi->execute();
                            $insSoi->close();
                        }
                    }
                }

                // If delivery date was edited on a dedicated stock order, update dates in stock order and pending sowing schedules
                if (!empty($expdate) && $expdate !== $prevExpDate) {
                    $chkDed = $krfconn->prepare("SELECT psono FROM krf_production_stockorder WHERE stockorder_no = ? AND projectid = ? LIMIT 1");
                    if ($chkDed) {
                        $chkDed->bind_param("si", $stockorderno, $projectid);
                        $chkDed->execute();
                        $dedRow = $chkDed->get_result()->fetch_assoc();
                        $chkDed->close();
                        if ($dedRow && $dedRow['psono'] === $psono) {
                            $newSwg = getSowingDatesWithSessionCorrection($krfconn, $expdate, $projectid, $productid);
                            $newRootDate = $newSwg['RootSeedId']['sowingdate'] ?? null;
                            $newScionDate = $newSwg['ScionSeedId']['sowingdate'] ?? $newRootDate;
                            if (!empty($newRootDate)) {
                                $today = date('Y-m-d');
                                if ($newRootDate < $today || (!empty($newScionDate) && $newScionDate < $today)) {
                                    throw new Exception("Back-date sowing is not allowed for product '{$productName}'. Delivery date '{$expdate}' results in sowing date '{$newRootDate}', which is in the past (before {$today}).");
                                }
                                $updDates = $krfconn->prepare("UPDATE krf_production_stockorder SET expected_delivery_date = ?, sowingdate = ?, scionsowingdate = ?, updated_at = NOW() WHERE stockorder_no = ? AND projectid = ?");
                                if ($updDates) {
                                    $updDates->bind_param("ssssi", $expdate, $newRootDate, $newScionDate, $stockorderno, $projectid);
                                    $updDates->execute();
                                    $updDates->close();
                                }
                                $updSchRoot = $krfconn->prepare("UPDATE krf_polyhouse_sowingschedule SET sowing_date = ?, updated_by = ?, updated_at = NOW() WHERE stockorder_no = ? AND projectid = ? AND roottype = 'Root' AND status = 'pending'");
                                if ($updSchRoot) {
                                    $updSchRoot->bind_param("sssi", $newRootDate, $loginid, $stockorderno, $projectid);
                                    $updSchRoot->execute();
                                    $updSchRoot->close();
                                }
                                $updSchScion = $krfconn->prepare("UPDATE krf_polyhouse_sowingschedule SET sowing_date = ?, updated_by = ?, updated_at = NOW() WHERE stockorder_no = ? AND projectid = ? AND roottype = 'Scion' AND status = 'pending'");
                                if ($updSchScion) {
                                    $updSchScion->bind_param("sssi", $newScionDate, $loginid, $stockorderno, $projectid);
                                    $updSchScion->execute();
                                    $updSchScion->close();
                                }
                            }
                        }
                    }
                }

                // Ensure ALL required sowing schedule records (Root AND Scion for grafted) exist in krf_polyhouse_sowingschedule ONLY IF item is self-stock (own production order)
                if ($isSelfStock) {
                $chk_sch_exist = $krfconn->prepare("SELECT sowingschedulid, roottype FROM krf_polyhouse_sowingschedule WHERE stockorder_no = ? AND projectid = ? AND status NOT IN ('delete','removed','notrequired')");
                if ($chk_sch_exist) {
                    $chk_sch_exist->bind_param("ss", $stockorderno, $projectid);
                    $chk_sch_exist->execute();
                    $res_sch_rows = $chk_sch_exist->get_result()->fetch_all(MYSQLI_ASSOC);
                    $chk_sch_exist->close();

                    $pData = getProductData($krfconn, $projectid, "ProductID", $productid);
                    $isGraftedProd = false;
                    if (!empty($pData) && isset($pData[0])) {
                        $g = $pData[0]['IsGrafted'] ?? 0;
                        $isGraftedProd = ($g == 1 || strtolower((string)$g) === 'yes' || strtolower((string)$g) === 'grafted');
                    }

                    $stk_sd = null;
                    $stk_ssd = null;
                    $stkStmt = $krfconn->prepare("SELECT sowingdate, scionsowingdate FROM krf_production_stockorder WHERE stockorder_no = ? AND projectid = ? LIMIT 1");
                    if ($stkStmt) {
                        $stkStmt->bind_param("si", $stockorderno, $projectid);
                        $stkStmt->execute();
                        $stkRow = $stkStmt->get_result()->fetch_assoc();
                        $stkStmt->close();
                        $stk_sd = $stkRow['sowingdate'] ?? null;
                        $stk_ssd = $stkRow['scionsowingdate'] ?? null;
                    }
                    if (empty($stk_sd) || $stk_sd === '0000-00-00') {
                        $calc_dates = getSowingDatesWithSessionCorrection($krfconn, $expdate, $projectid, $productid);
                        $stk_sd = $calc_dates['RootSeedId']['sowingdate'] ?? null;
                        $stk_ssd = $calc_dates['ScionSeedId']['sowingdate'] ?? $stk_sd;
                    }

                    if (empty($res_sch_rows)) {
                        createSowingScheduleForNewStockOrder($krfconn, $projectid, $stockorderno, $productid, $productName, $qty, $stk_sd, $stk_ssd, $loginid, null, null, null, $psono);
                    } elseif ($isGraftedProd) {
                        $typesPresent = array_map(function($r) { return strtolower(trim($r['roottype'] ?? '')); }, $res_sch_rows);
                        if (!in_array('root', $typesPresent)) {
                            createSowingScheduleForNewStockOrder($krfconn, $projectid, $stockorderno, $productid, $productName, $qty, $stk_sd, $stk_ssd, $loginid, null, null, 'Root', $psono);
                        }
                        if (!in_array('scion', $typesPresent)) {
                            createSowingScheduleForNewStockOrder($krfconn, $projectid, $stockorderno, $productid, $productName, $qty, $stk_sd, $stk_ssd, $loginid, null, null, 'Scion', $psono);
                        }
                    }
                }
                }

                // Update or ensure consumed stock record
                $chkCons = $krfconn->prepare("SELECT id FROM krf_productionorder_consumed WHERE psono = ? AND stockorderno = ? AND projectid = ? LIMIT 1");
                if ($chkCons) {
                    $chkCons->bind_param("ssi", $psono, $stockorderno, $projectid);
                    $chkCons->execute();
                    $consRow = $chkCons->get_result()->fetch_assoc();
                    $chkCons->close();
                    if ($consRow) {
                        if (!empty($sono)) {
                            $upCons = $krfconn->prepare("UPDATE krf_productionorder_consumed SET consumed_qty = ?, product_name = ?, sono = ?, confirmstatus = 'Confirmed' WHERE id = ?");
                            if ($upCons) {
                                $upCons->bind_param("dssi", $qty, $productName, $sono, $consRow['id']);
                                $upCons->execute();
                                $upCons->close();
                            }
                        } else {
                            $upCons = $krfconn->prepare("UPDATE krf_productionorder_consumed SET consumed_qty = ?, product_name = ? WHERE id = ?");
                            if ($upCons) {
                                $upCons->bind_param("dsi", $qty, $productName, $consRow['id']);
                                $upCons->execute();
                                $upCons->close();
                            }
                        }
                    } else {
                        $cStatus = !empty($sono) ? 'Confirmed' : 'pending';
                        $insCons = $krfconn->prepare("INSERT INTO krf_productionorder_consumed (psono, sono, stockorderno, product_id, product_name, consumed_qty, wastage_qty, projectid, confirmstatus, status, created_by) VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, 'pending', ?)");
                        if ($insCons) {
                            $insCons->bind_param("sssisdiss", $psono, $sono, $stockorderno, $productid, $productName, $qty, $projectid, $cStatus, $loginid);
                            $insCons->execute();
                            $insCons->close();
                        }
                    }
                }
            } elseif ($isReallocated && !empty($stockorderno)) {
                // Ensure consumed stock record for newly re-allocated stock order
                $cStatus = !empty($sono) ? 'Confirmed' : 'pending';
                $insCons = $krfconn->prepare("INSERT INTO krf_productionorder_consumed (psono, sono, stockorderno, product_id, product_name, consumed_qty, wastage_qty, projectid, confirmstatus, status, created_by) VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, 'pending', ?)");
                if ($insCons) {
                    $insCons->bind_param("sssisdiss", $psono, $sono, $stockorderno, $productid, $productName, $qty, $projectid, $cStatus, $loginid);
                    $insCons->execute();
                    $insCons->close();
                }

                // Reallocated to existing production stock order: no sowing schedule or tray details needed
            }
        } else {
            // New item row: Insert and allocate/create stock order
            $stockorderno ="";
            if($stock_id){
                $stockordno_new = getFullProductionStockOrdersDetails($krfconn, $projectid, "id", $stock_id); 
                $stockorderno = $stockordno_new[0]['stockorder_no'] ?? '';
                if (empty($stockorderno)) {
                    $qStk = $krfconn->query("SELECT stockorder_no FROM krf_production_stockorder WHERE id = " . intval($stock_id));
                    if ($qStk && $rStk = $qStk->fetch_assoc()) {
                        $stockorderno = $rStk['stockorder_no'];
                    }
                }
            }else{
                $stockorderno = getNewProductionStockOrderNo($krfconn, $companyid, $projectid);
            }
            if ($productName && $expdate && $qty > 0) {
                // bind current iteration values
                $itemStmt->bind_param(
                    $types,
                    $projectid,
                    $clientid,
                    $psono,
                    $productsku,
                    $productid,
                    $productName,
                    $qty,
                    $rate,
                    $item_freight_charge,
                    $taxableAmount,
                    $cgstPercent,
                    $cgstAmount,
                    $sgstPercent,
                    $sgstAmount,
                    $igstPercent,
                    $igstAmount,
                    $discountPercent,
                    $discountAmount,
                    $amt,
                    $roundoffamount,
                    $expdate,
                    $loginid,
                    $stockorderno
                ); 
                if ($itemStmt->execute()) {
                    if($stock_id){
                        $stockordno = getFullProductionStockOrdersDetails($krfconn, $projectid, "id", $stock_id);
                        $stockorderno = $stockordno[0]['stockorder_no'] ?? '';
                        if (empty($stockorderno)) {
                            $qStk = $krfconn->query("SELECT stockorder_no FROM krf_production_stockorder WHERE id = " . intval($stock_id));
                            if ($qStk && $rStk = $qStk->fetch_assoc()) {
                                $stockorderno = $rStk['stockorder_no'];
                            }
                        }

                        $wastage_qty=0;
                        $cStatus = !empty($sono) ? "Confirmed" : "pending";
                        $sql_consumed = "INSERT INTO krf_productionorder_consumed(psono, sono, stockorderno, product_id, product_name, consumed_qty, wastage_qty, projectid, confirmstatus, status, created_by) select '".$psono."', '".(!empty($sono) ? $sono : "")."', stockorder_no, '".$productid."' , '".$productName."' , '".$qty."', '".$wastage_qty."','".$projectid."', '".$cStatus."', 'pending', '".$loginid."' from krf_production_stockorder where id ='".$stock_id."'";
                        $krfconn->query($sql_consumed);
                        // Product assigned against existing production order:
                        // No update to Qty_from_readysale in krf_production_stockorder
                        // No insert into krf_polyhouse_sowingschedule or krf_polyhouse_sowingplant_details

                    } else {
                        $swgdate = getSowingDatesWithSessionCorrection($krfconn,$expdate,$projectid,$productid);
                        $rootGrowingDays = $swgdate['RootSeedId']['growingdays'] ?? 0;
                        $aftergraftingdays =$swgdate['RootSeedId']['aftergraftingdays'] ?? 0;
                        $rootSeason      = $swgdate['RootSeedId']['season'] ?? '';
                        $rootSessionDays = $swgdate['RootSeedId']['sessiondays'] ?? 0;
                        $rootIsGrafted   = $swgdate['RootSeedId']['isgrafted'] ?? 0;
                        $rootSowingDate  = $swgdate['RootSeedId']['sowingdate'] ?? '';

                        $sowingdate    = $rootSowingDate;
                        $fullcycledays = ($swgdate['sowingdate'] ?? 0) + $aftergraftingdays + $rootSessionDays;

                        $scionSeedId      = $swgdate['ScionSeedId']['seedid'] ?? '';
                        $scionGrowingDays = $swgdate['ScionSeedId']['growingdays'] ?? 0;
                        $scionSeason      = $swgdate['ScionSeedId']['season'] ?? '';
                        $scionSessionDays = $swgdate['ScionSeedId']['sessiondays'] ?? 0;
                        $scionIsGrafted   = $swgdate['ScionSeedId']['isgrafted'] ?? 0;
                        $scionsowingdate  = $swgdate['ScionSeedId']['sowingdate'] ?? $sowingdate;
                        $today = date('Y-m-d');
                        if (!empty($sowingdate) && ($sowingdate < $today || (!empty($scionsowingdate) && $scionsowingdate < $today))) {
                            throw new Exception("Back-date sowing is not allowed for product '{$productName}'. Delivery date '{$expdate}' results in sowing date '{$sowingdate}', which is in the past (before {$today}).");
                        }

                        $status = !empty($sono) ? "Confirmed" : "unconfirmed";
                        $sql_stock = "INSERT INTO krf_production_stockorder  (psono, stockorder_no, projectid, companyid, subcompanyid, clientid,  productid, product_Name, quantity, expected_delivery_date,  remarks, status, created_by, sowingdate, scionsowingdate, created_at, updated_at) VALUES (  '{$psono}',  '{$stockorderno}',  {$projectid},  {$companyid},  {$subcompanyid},  '{$clientid}',  {$productid}, '".addslashes($productName)."' , {$qty}, '{$expdate}', '".addslashes($remarks)."',  '{$status}', '{$loginid}', '{$sowingdate}', '{$scionsowingdate}', NOW(), NOW())";
                        $krfconn->query($sql_stock);

                        $wastage_qty=0;
                        $cStatus = !empty($sono) ? "Confirmed" : "pending";
                        $sql_consumed = "INSERT INTO krf_productionorder_consumed(psono, sono, stockorderno, product_id, product_name, consumed_qty, wastage_qty, projectid, confirmstatus, status, created_by) select '".$psono."', '".(!empty($sono) ? $sono : "")."', stockorder_no, '".$productid."' , '".$productName."' , '".$qty."', '".$wastage_qty."','".$projectid."', '".$cStatus."', 'pending', '".$loginid."' from krf_production_stockorder where stockorder_no ='".$stockorderno."'";
                        $krfconn->query($sql_consumed);

// Fresh production order: Qty_from_readysale update not required

                        // Check if data is inserted in krf_polyhouse_sowingschedule; if not, insert with actual correct sowing date as mentioned in krf_production_stockorder
                        if (!empty($stockorderno)) {
                            $chk_sch = $krfconn->prepare("SELECT sowingschedulid FROM krf_polyhouse_sowingschedule WHERE stockorder_no = ? AND projectid = ? LIMIT 1");
                            if ($chk_sch) {
                                $chk_sch->bind_param("ss", $stockorderno, $projectid);
                                $chk_sch->execute();
                                $res_sch = $chk_sch->get_result()->fetch_assoc();
                                $chk_sch->close();
                                if (!$res_sch) {
                                    createSowingScheduleForNewStockOrder($krfconn, $projectid, $stockorderno, $productid, $productName, $qty, $sowingdate, $scionsowingdate, $loginid, null, null, null, $psono);
                                }
                            }
                        }
                    }

                    // If order has confirmed sales order, insert newly added item into krf_sales_order_items
                    if (!empty($sono)) {
                        $newShiftingQty = (int)ceil($qty / 125);
                        $insSoi = $krfconn->prepare("INSERT INTO krf_sales_order_items (
                            sono, psono, stockorder_no, projectid, clientid, product_sku, product_id, product_name,
                            quantity, rate, taxableamount, cgst_percent, cgst_amount, sgst_percent, sgst_amount,
                            igst_percent, igst_amount, discount_percent, discount_amount, freight_charges,
                            tax_amount, amount, roundoffamount, deliverydate, created_by, status, shiftingqty,
                            ammendedby, updateamended
                        ) VALUES (
                            ?, ?, ?, ?, ?, ?, ?, ?,
                            ?, ?, ?, ?, ?, ?, ?,
                            ?, ?, ?, ?, ?,
                            0, ?, ?, ?, ?, 'Confirmed', ?,
                            ?, NOW()
                        )");
                        if ($insSoi) {
                            $insSoi->bind_param(
                                "sssissisddddddddddddddssis",
                                $sono, $psono, $stockorderno, $projectid, $clientid, $productsku, $productid, $productName,
                                $qty, $rate, $taxableAmount, $cgstPercent, $cgstAmount, $sgstPercent, $sgstAmount,
                                $igstPercent, $igstAmount, $discountPercent, $discountAmount, $item_freight_charge,
                                $amt, $roundoffamount, $expdate, $loginid, $newShiftingQty, $loginid
                            );
                            $insSoi->execute();
                            $insSoi->close();
                        }
                    }
                } else {
                    throw new Exception("Failed to insert order item: " . $itemStmt->error);
                    echo json_encode(["status" => "error", "message" => "Failed to insert order item, Somthing wrong"]);
                    $krfconn->rollback();
                    exit;
                }
            }
        }
    }

    $itemStmt->close();

    // 🚚 Save consolidated multi-row freight charges to krf_freightcharges
    if ($total_f_payable > 0 || $total_f_amount > 0 || $is_zero_charge === 'yes' || !empty($f_amounts)) {
        $combined_f_remarks = implode(" | ", $all_f_remarks);
        if (!empty($remarks)) {
            $combined_f_remarks .= ($combined_f_remarks ? " - " : "") . $remarks;
        }

        $respFreight = saveFreightCharges(
            $krfconn,
            $projectid,
            $psono,
            $sono,
            $clientid,
            $total_f_amount,
            $avg_discount_percent,
            $combined_f_remarks,
            $loginid,
            $is_zero_charge
        );

        if ($respFreight['status'] !== "success") {
            throw new Exception("Failed to save freight charges: " . $respFreight['message']);
        }
    }

    $respAddr = saveMultipleDeliveryLocations($krfconn, $deliveryaddres);
    if ($respAddr['status'] !== "success") {
        throw new Exception("Failed to save delivery addresses: " . $respAddr['message']);
    }

    // 🚨 Emergency status follows the confirmed checkbox, never an automatic date heuristic.
    $is_emergency_flag = (
        (isset($_POST['is_emergency_sowing']) && in_array(strtolower((string)$_POST['is_emergency_sowing']), ['yes', '1', 'true'], true)) ||
        (isset($_POST['emergency_sowing']) && in_array(strtolower((string)$_POST['emergency_sowing']), ['yes', '1', 'true'], true))
    );
    if ($is_emergency_flag) {
        // User explicitly checked and confirmed emergency sowing
        $updStatusSql = "UPDATE krf_provisional_sales_order SET status = 'Emergency Sowing', updated_at = NOW() WHERE psono = ? AND projectid = ?";
        $updStmt = $krfconn->prepare($updStatusSql);
        if ($updStmt) {
            $updStmt->bind_param("si", $psono, $projectid);
            $updStmt->execute();
            $updStmt->close();
        }
    }

    // If sales order exists for this psono, sync sales order totals
    if ($isEditMode && !empty($sono)) {
        $soTotQ = $krfconn->prepare("
            SELECT 
                COALESCE(SUM(taxableamount), 0) AS total_taxable,
                COALESCE(SUM(cgst_amount + sgst_amount + igst_amount), 0) AS total_tax,
                COALESCE(SUM(roundoffamount), 0) AS total_roundoff,
                COALESCE(SUM(amount), 0) AS total_amount
            FROM krf_sales_order_items 
            WHERE sono = ? AND (status IS NULL OR LOWER(TRIM(status)) NOT IN ('cancel', 'cancelled'))
        ");
        if ($soTotQ) {
            $soTotQ->bind_param("s", $sono);
            $soTotQ->execute();
            $soTotRes = $soTotQ->get_result()->fetch_assoc();
            $soTotQ->close();
            $soTotalAmount = (float)($soTotRes['total_amount'] ?? 0);
            $soTaxAmount = (float)($soTotRes['total_tax'] ?? 0);
            $soRoundoff = (float)($soTotRes['total_roundoff'] ?? 0);
            $soGrandTotal = $soTotalAmount;

            $upSoTot = $krfconn->prepare("UPDATE krf_sales_order SET total_amount = ?, tax_amount = ?, roundoffamount = ?, grand_total = ?, updated_by = ?, updated_at = NOW() WHERE sono = ?");
            if ($upSoTot) {
                $upSoTot->bind_param("ddddss", $soTotalAmount, $soTaxAmount, $soRoundoff, $soGrandTotal, $loginid, $sono);
                $upSoTot->execute();
                $upSoTot->close();
            }
        }
    }

    // Everything ok -> commit
    $krfconn->commit();
     echo json_encode([
        "status" => "success",
        "message" => $isEditMode ? "Provisional sales order updated successfully" : "Provisional sales order saved successfully",
        "psono"  => $psono,
        "clientid"  => $clientid,
        "mode"   => $isEditMode ? "edit" : "add"
    ]);
} catch (Exception $e) {
    // rollback and return error
    if ($krfconn) {
        $krfconn->rollback();
    }
    echo json_encode([
        "status" => "error",
        "message" => "Transaction failed: " . $e->getMessage()
    ]);
}
$krfconn->close();

?>