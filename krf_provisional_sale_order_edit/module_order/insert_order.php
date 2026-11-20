<?php
require_once __DIR__.'/stock_save_guard.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require __DIR__ . '/../../connection.php';
if (isset($krfconn) && $krfconn) {
    @$krfconn->set_charset("utf8mb4");
}
require __DIR__ . '/../../loginCell/functions/login_functions.php';
include __DIR__ . "/../functions/orders_functions.php";
include __DIR__ . "/../functions/product_functions.php";
include __DIR__ . "/../functions/client_functions.php";
include __DIR__ . "/../functions/emailsend_functions.php";

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
// Generate PSO number
$psono = getNewProvisionalSalesOrderNo($krfconn, $companyid, $subcompanyid, $projectid);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    // Basic validation (same as yours)
    if (empty($clientname) || empty($mobileno) || count($productlist) == 0 || count($expecteddelivery) == 0) {
        echo json_encode(["status" => "error", "message" => "Required fields are missing."]);
        $krfconn->rollback();
        exit;
    }
    $krfconn->begin_transaction();
    $enquiryRows=null;
    $enquiryToken=trim((string)($_POST['enquiry_token']??''));
    if($enquiryToken!=='' && !empty($_POST['enquiry_selected_ids'])){
        require_once dirname(__DIR__,2).'/krf_enquiry_followup/helpers.php';
        if(session_status()!==PHP_SESSION_ACTIVE)session_start();
        if(empty($_SESSION['sessionid'])||$_SESSION['sessionid']!==session_id())throw new RuntimeException('Session expired.');
        $enquiryRows=efLockConversion($krfconn,$enquiryToken,array_map('strval',[$companyid,$subcompanyid,$projectid,$loginid]));
        $selectedIds=array_values(array_unique(array_map('intval',(array)$_POST['enquiry_selected_ids'])));$allowedIds=array_map('intval',array_column($enquiryRows,'id'));if(array_diff($selectedIds,$allowedIds))throw new RuntimeException('Invalid enquiry selection.');$enquiryRows=array_values(array_filter($enquiryRows,fn($r)=>in_array((int)$r['id'],$selectedIds,true)));foreach($enquiryRows as $enquiry)if(efPhone($mobileno)!==efPhone($enquiry['mobileno']))throw new RuntimeException('The PO client changed. Remove selected enquiry items or restore the enquiry client before saving.');
    }

    $stockSaveRequests = [];
    // Save or update client in krf_client_master
    if (!$clientid) {
        $response = saveClient($krfconn,$companyid,$subcompanyid,$projectid,$clientid,$clientname,$delivery_address,$mobileno,$emailid,$districtname,$statename,false,$cityname,$stateid,$districtid,$tahseel);

        if ($response['status'] === "success") {
            $clientid = $response['client_id'];
        } else {
            throw new Exception("Failed to save client: " . ($response['message'] ?? 'Unknown error'));
        }
    } else {
        saveClient($krfconn,$companyid,$subcompanyid,$projectid,$clientid,$clientname,$delivery_address,$mobileno,$emailid,$districtname,$statename,true,$cityname,$stateid,$districtid,$tahseel);
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
 
    // Insert order header
    $hdrSql = "INSERT INTO krf_provisional_sales_order 
      (psono, companyid, subcompanyid, projectid, clientid, clientname, mobileno, emailid, delivery_address, districtname, statename, remarks, created_by,pickupfrom)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)";
    $hdrStmt = $krfconn->prepare($hdrSql);

    // types: s = psono, i = companyid, subcompanyid, projectid, clientid, then strings...
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

        $stockorderno ="";
        if($stock_id){
             $stockordno_new = orderLockAvailableStock($krfconn,$projectid,$stock_id,$productid,$psono,$qty,$stockSaveRequests,$readytosale>0); 
            $stockorderno = $stockordno_new[0]['stockorder_no'] ?? '';
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



                    $wastage_qty=0;
                    $sql_consumed = "INSERT INTO krf_productionorder_consumed(psono,  stockorderno, product_id, product_name, consumed_qty, wastage_qty, projectid) select '".$psono."',stockorder_no, '".$productid."' , '".$productName."' , '".$qty."', '".$wastage_qty."','".$projectid."' from krf_production_stockorder where id ='".$stock_id."'";
                    if(!$krfconn->query($sql_consumed))throw new RuntimeException('Unable to save stock allocation: '.$krfconn->error);
                    if ($readytosale > 0) { 
                        $krfconn->query("
                            UPDATE krf_production_stockorder
                            SET Qty_from_readysale = COALESCE(Qty_from_readysale, 0) + {$readytosale}
                            WHERE id = '".$stock_id."'
                        ");
                    }


                    // Check and ensure sowing schedule exists for the consumed stock order
                    if (!empty($stockorderno)) {
                        $chk_sch = $krfconn->prepare("SELECT sowingschedulid FROM krf_polyhouse_sowingschedule WHERE stockorder_no = ? AND projectid = ? LIMIT 1");
                        if ($chk_sch) {
                            $chk_sch->bind_param("ss", $stockorderno, $projectid);
                            $chk_sch->execute();
                            $res_sch = $chk_sch->get_result()->fetch_assoc();
                            $chk_sch->close();
                            if (!$res_sch) {
                                $stk_info = $stockordno[0] ?? [];
                                $stk_sd = $stk_info['sowingdate'] ?? null;
                                $stk_ssd = $stk_info['scionsowingdate'] ?? null;
                                $stk_qty = $stk_info['quantity'] ?? $qty;
                                createSowingScheduleForNewStockOrder($krfconn, $projectid, $stockorderno, $productid, $productName, $stk_qty, $stk_sd, $stk_ssd, $loginid, null, null, null, $psono, 'new_provisional', (string)($loginname??''), (string)($remarks??''));
                            }
                        }
                    }

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

                    $status="unconfirmed";
                    $sql_stock = "INSERT INTO krf_production_stockorder  (psono, stockorder_no, projectid, companyid, subcompanyid, clientid,  productid, product_Name, quantity, expected_delivery_date,  remarks, status, created_by, sowingdate, scionsowingdate, created_at, updated_at) VALUES (  '{$psono}',  '{$stockorderno}',  {$projectid},  {$companyid},  {$subcompanyid},  '{$clientid}',  {$productid}, '".addslashes($productName)."' , {$qty}, '{$expdate}', '".addslashes($remarks)."',  '{$status}', '{$loginid}', '{$sowingdate}', '{$scionsowingdate}', NOW(), NOW())";
                    $krfconn->query($sql_stock);

                    $wastage_qty=0;
                    $sql_consumed = "INSERT INTO krf_productionorder_consumed(psono,  stockorderno, product_id, product_name, consumed_qty, wastage_qty, projectid) select '".$psono."',stockorder_no, '".$productid."' , '".$productName."' , '".$qty."', '".$wastage_qty."','".$projectid."' from krf_production_stockorder where stockorder_no ='".$stockorderno."'";
                    if(!$krfconn->query($sql_consumed))throw new RuntimeException('Unable to save stock allocation: '.$krfconn->error);

                    if ($readytosale > 0) { 
                        $krfconn->query("
                            UPDATE krf_production_stockorder
                            SET Qty_from_readysale = COALESCE(Qty_from_readysale, 0) + {$readytosale}
                            WHERE stockorder_no = '".$stockorderno."'
                        ");
                    }

                    // Check if data is inserted in krf_polyhouse_sowingschedule; if not, insert with actual correct sowing date as mentioned in krf_production_stockorder
                    if (!empty($stockorderno)) {
                        $chk_sch = $krfconn->prepare("SELECT sowingschedulid FROM krf_polyhouse_sowingschedule WHERE stockorder_no = ? AND projectid = ? LIMIT 1");
                        if ($chk_sch) {
                            $chk_sch->bind_param("ss", $stockorderno, $projectid);
                            $chk_sch->execute();
                            $res_sch = $chk_sch->get_result()->fetch_assoc();
                            $chk_sch->close();
                            if (!$res_sch) {
                                createSowingScheduleForNewStockOrder($krfconn, $projectid, $stockorderno, $productid, $productName, $qty, $sowingdate, $scionsowingdate, $loginid, null, null, null, $psono, 'new_provisional', (string)($loginname??''), (string)($remarks??''));
                            }
                        }
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

    if ((string)($_POST['emergency_sowing'] ?? '0') === '1') {
        $updStmt = $krfconn->prepare("UPDATE krf_provisional_sales_order SET status='Emergency Sowing', updated_at=NOW() WHERE psono=? AND projectid=?");
        $updStmt->bind_param('si', $psono, $projectid);
        $updStmt->execute();
        $updStmt->close();
    }

    if($enquiryRows)efCompleteConversion($krfconn,$enquiryRows,array_map('strval',[$companyid,$subcompanyid,$projectid,$loginid]),$clientid,$psono);
    // Everything ok -> commit
    $krfconn->commit();
     echo json_encode([
        "status" => "success",
        "message" => "Provisional sales order saved successfully",
        "psono"  => $psono,
        "clientid"  => $clientid
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