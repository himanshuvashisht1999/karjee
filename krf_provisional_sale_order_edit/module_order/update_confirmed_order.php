<?php
require_once __DIR__.'/followup_amendment.php';
require_once __DIR__.'/../../whatsappnotification/order_modification_notifications.php';
ini_set('display_errors','0');error_reporting(E_ALL);date_default_timezone_set('Asia/Kolkata');
require __DIR__.'/confirmed_access.php';
require __DIR__.'/confirmed_helpers.php';
header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $scope=confirmedAjaxContext();
    if(!hash_equals((string)($_SESSION['confirmed_edit_csrf']??''),(string)($_POST['csrf']??''))||empty($_SESSION['confirmed_edit_csrf']))throw new RuntimeException('Invalid save token. Reload the editor.');
    require_once __DIR__.'/../functions/orders_functions.php';
    require_once __DIR__.'/../functions/product_functions.php';
    require_once __DIR__.'/../functions/client_functions.php';
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
$d_productids = ($_POST['pickupfrom'] ?? '') === 'boxpacking' ? ($_POST['box_productid'] ?? []) : ($_POST['delivery_productid'] ?? []);
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
$psono = trim((string)($_POST['edit_psono'] ?? ''));
$editItemIds = $_POST['edit_item_id'] ?? [];


    if([(string)$companyid,(string)$subcompanyid,(string)$projectid]!==array_slice($scope,0,3))throw new RuntimeException('Project context does not match your session.');
    $loginid=$scope[3];$changeReason=''; // Modification reason is no longer collected.
    if(!$psono||!$clientname||!$mobileno||!is_array($productlist)||!count($productlist))throw new RuntimeException('Required order fields are missing.');
    $krfconn->set_charset('utf8mb4');$krfconn->begin_transaction();
    $original=ceSnapshot($krfconn,$psono,$projectid,$companyid,$subcompanyid,true);$sono=$original['sale']['sono'];ceAssertEditableItemStatuses($original);
    if(abs((float)($original['sale']['tax_amount']??0))>.001)throw new RuntimeException('This order uses an additional header tax. Its calculation must be mapped before editing.');
    if(!hash_equals(ceToken($original),(string)($_POST['snapshot_token']??'')))throw new RuntimeException('Order changed since you opened it. Reload before saving.');
    if($clientid!==(string)$original['header']['clientid'])throw new RuntimeException('Changing the customer is not supported for confirmed orders.');
    $oldItemsById=[];foreach($original['items'] as $r)$oldItemsById[(string)$r['id']]=$r;
    $salesByPso=[];foreach($original['items'] as $r)$salesByPso[(string)$r['id']]=ceSalesItem($original,$r);
    $response=saveClient($krfconn,$companyid,$subcompanyid,$projectid,$clientid,$clientname,$delivery_address,$mobileno,$emailid,$districtname,$statename,true,$cityname,$stateid,$districtid,$tahseel);
    if(($response['status']??'')!=='success')throw new RuntimeException($response['message']??'Unable to update client.');
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
            $payable = isset($f_payables[$i]) && is_numeric($f_payables[$i]) 
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

    $preserveItemFreight = count($productlist) === count($original['items']) && $pickupfrom === $original['header']['pickupfrom'];
    $oldFreight = $original['freight'][0] ?? [];
    $preserveItemFreight = $preserveItemFreight && abs((float)($oldFreight['freight_amount'] ?? 0) - $total_f_amount) < 0.01 && abs((float)($oldFreight['payable_freightamount'] ?? 0) - $total_f_payable) < 0.01;
    foreach ($productlist as $checkIndex => $checkProduct) {
        $oldCheck = $oldItemsById[(string)($editItemIds[$checkIndex] ?? '')] ?? null;
        if (!$oldCheck || (string)$oldCheck['product_id'] !== (string)$checkProduct || abs((float)$oldCheck['quantity'] - (float)($quantities[$checkIndex] ?? 0)) > 0.001 || substr((string)$oldCheck['expecteddeliverydate'],0,10) !== (string)($expecteddelivery[$checkIndex] ?? '')) {
            $preserveItemFreight = false;
        }
    }
    if ($preserveItemFreight && ($oldFreight['zerocharge'] ?? '') === 'yes') $is_zero_charge = 'yes';

    // Calculate total order quantity across all items
    $total_order_qty = 0;
    for ($k = 0; $k < count($productlist); $k++) {
        $total_order_qty += (float)($quantities[$k] ?? 0);
    }

    $seen=[];$stockKeys=[];$itemChanges=[];
    foreach($productlist as $i=>$product){
        $productid=trim((string)$product);$qtyRaw=$quantities[$i]??null;
        if(!is_numeric($qtyRaw)||(float)$qtyRaw<=0||floor((float)$qtyRaw)!=(float)$qtyRaw)throw new RuntimeException('Enter a positive whole plant quantity.');
        $qty=(int)$qtyRaw;$delivery=(string)($expecteddelivery[$i]??'');$date=DateTimeImmutable::createFromFormat('!Y-m-d',$delivery);
        if(!$date||$date->format('Y-m-d')!==$delivery)throw new RuntimeException('Invalid requested delivery date.');
        $products=getProductData($krfconn,$projectid,'ProductID',$productid);if(!$products)throw new RuntimeException('Product not found.');$p=$products[0];
        $editId=(string)($editItemIds[$i]??'');$old=$oldItemsById[$editId]??null;$sale=$salesByPso[$editId]??null;
        if($editId!==''&&(!$old||isset($seen[$editId])))throw new RuntimeException('Invalid or duplicated existing item identity.');
        if($old)$seen[$editId]=true;
        $v=[];$fieldArrays=['rate'=>$rates,'taxableamount'=>$taxableamount,'cgst_percent'=>$cgstpercent,'cgst_amount'=>$cgstamount,'sgst_percent'=>$sgstpercent,'sgst_amount'=>$sgstamount,'igst_percent'=>$igstpercent,'igst_amount'=>$igstamount,'discount_percent'=>$discountpercent,'discount_amount'=>$discountamount,'amount'=>$amounts];
        foreach($fieldArrays as $key=>$values){
            if(!is_array($values)||!array_key_exists($i,$values))throw new RuntimeException('Missing calculation value: '.$key.' (item '.($i+1).')');
            $value=$values[$i];
            $optional=['cgst_percent','cgst_amount','sgst_percent','sgst_amount','igst_percent','igst_amount','discount_percent','discount_amount'];
            // Empty optional discount/tax controls mean no charge, not invalid input.
            if(is_string($value)&&trim($value)===''&&in_array($key,$optional,true))$value=0;
            if(!is_scalar($value)||!is_numeric($value)||!is_finite((float)$value)||(float)$value<0)throw new RuntimeException('Invalid calculation value: '.$key.' (item '.($i+1).')');
            $v[$key]=round((float)$value,2);
        }
        if (isset($roundoffamounts[$i]) && is_numeric($roundoffamounts[$i])) {
            $v['roundoffamount'] = round((float)$roundoffamounts[$i], 2);
        } else {
            $unrounded = ($v['rate'] * $qty) - $v['discount_amount'];
            $v['roundoffamount'] = round($v['amount'] - $unrounded, 2);
        }
        if($v['discount_percent']>100)throw new RuntimeException('Discount cannot exceed 100 percent.');
        // Same grouped, date-based proportional freight rule as provisional editing.
        $freight=0;$matched=null;foreach($group_freight_map as $g)if(!empty($g['date'])&&$g['date']===$delivery){$matched=$g;break;}
        if($total_f_payable>0){$groupQty=0;if($matched)foreach($quantities as $j=>$q)if(($expecteddelivery[$j]??'')===$delivery)$groupQty+=(float)$q;
            $freight=round($matched&&$groupQty>0?$qty/$groupQty*$matched['payable']:($total_order_qty>0?$qty/$total_order_qty*$total_f_payable:0),2);}
        if($preserveItemFreight&&$old)$freight=(float)$old['freight_charges'];$v['freight_charges']=$freight;
        $changed=!$old||(string)$old['product_id']!==$productid||(int)$old['quantity']!==$qty||substr((string)$old['expecteddeliverydate'],0,10)!==$delivery;
        if($sale){foreach($v as $key=>$value)if(abs((float)($sale[$key]??0)-$value)>.01)$changed=true;ceProtected($sale,$qty,$changed);}
        if($old&&(string)$old['product_id']!==$productid){
            $oldP=getProductData($krfconn,$projectid,'ProductID',$old['product_id'])[0]??null;if(!$oldP)throw new RuntimeException('Original product not found.');
            $oldStock=ceStock($krfconn,$projectid,$old['stockorder_no'],$old['product_id']);ceAllocation($krfconn,$original,$oldStock,$oldP,0,$loginid,$changeReason);
            ceRetireItem($krfconn,$original,$old,$sale,$loginid,$changeReason);$old=null;$sale=null;
        }
        $stock=null;
        if($old){
            $stock=ceStock($krfconn,$projectid,$old['stockorder_no'],$productid);
            if($qty>(int)$old['quantity'])throw new RuntimeException('Allocate the additional quantity using the stock selection modal before saving.');
        }else{
            $stockId=(int)($stockdbid[$i]??0);
            if($stockId){$r=editQuery($krfconn,'SELECT * FROM krf_production_stockorder WHERE id=? AND projectid=? AND companyid=? AND subcompanyid=? AND productid=? FOR UPDATE',[$stockId,$projectid,$companyid,$subcompanyid,$productid]);if(count($r)!==1)throw new RuntimeException('Selected stock does not match the product/project.');$stock=$r[0];if(strtolower($stock['status']??'')!=='confirmed')throw new RuntimeException('Select confirmed source stock.');}
            else $stock=ceNewProduction($krfconn,$original,$p,$qty,$delivery,$loginid,$changeReason,(string)($loginname??$loginid));
        }
        $allocationKey=$stock['stockorder_no'].'|'.$productid;if(isset($stockKeys[$allocationKey]))throw new RuntimeException('Keep one item per stock order and product. Combine duplicate allocations before saving.');$stockKeys[$allocationKey]=true;
        ceAllocation($krfconn,$original,$stock,$p,$qty,$loginid,$changeReason);
        cePersistItem($krfconn,$original,$old,$sale,$p,$qty,$stock['stockorder_no'],$v,$delivery,$loginid);
        if($old && $qty<=(int)$old['quantity'])ceReduceRemainingSowing($krfconn,$original,$stock,$p,$qty,$loginid,(string)($loginname??$loginid));
        if($changed)$itemChanges[]=['action'=>$editId!==''&&!$old?'Product Changed':(!$old?'Item Added':($qty<(int)$old['quantity']?'Quantity Decreased':'Item Updated')),'old'=>$oldItemsById[$editId]??null,'new'=>['product_id'=>$productid,'quantity'=>$qty,'stockorder_no'=>$stock['stockorder_no'],'deliverydate'=>$delivery,'values'=>$v]];
    }
    foreach($original['items'] as $old)if(!isset($seen[(string)$old['id']])){
        $sale=$salesByPso[(string)$old['id']];$p=getProductData($krfconn,$projectid,'ProductID',$old['product_id'])[0]??null;if(!$p)throw new RuntimeException('Original product not found.');
        $stock=ceStock($krfconn,$projectid,$old['stockorder_no'],$old['product_id']);ceAllocation($krfconn,$original,$stock,$p,0,$loginid,$changeReason);ceRetireItem($krfconn,$original,$old,$sale,$loginid,$changeReason);
        $itemChanges[]=['action'=>'Item Removed','old'=>$old,'new'=>['quantity'=>0,'allocation_status'=>'cancel']];
    }
    // Evaluate after all final allocations are saved so reused/shared stock stays active.
    $ownedProduction=editQuery($krfconn,"SELECT stockorder_no,productid AS product_id FROM krf_production_stockorder WHERE projectid=? AND companyid=? AND subcompanyid=? AND psono=? AND LOWER(TRIM(COALESCE(status,''))) NOT IN ('cancel','cancelled','deleted') ORDER BY stockorder_no,productid FOR UPDATE",[$projectid,$companyid,$subcompanyid,$psono]);
    foreach($ownedProduction as $item)ceCancelUnusedPendingProduction($krfconn,$original,$item,$loginid,$changeReason,(string)($loginname??$loginid));
    $header=['clientname'=>$clientname,'mobileno'=>$mobileno,'emailid'=>$emailid,'delivery_address'=>$delivery_address,'districtname'=>$districtname,'statename'=>$statename,'remarks'=>$remarks,'updated_by'=>$loginid,'updated_at'=>date('Y-m-d H:i:s')];
    // Both header addresses are editable; the existing form calls the field delivery_address.
    $header['address']=$delivery_address;
    ceWrite($krfconn,'krf_provisional_sales_order',$header+['pickupfrom'=>$pickupfrom],$original['header']['id']);ceWrite($krfconn,'krf_sales_order',$header,$original['sale']['id']);
    $oldDelivery=[];foreach($original['delivery'] as $r)$oldDelivery[(string)$r['id']]=$r;
    $locationIds=$pickupfrom==='boxpacking'?($_POST['box_location_id']??[]):($_POST['delivery_location_id']??[]);$savedLocations=[];$deliveryTotals=[];
    foreach($deliveryaddres as $i=>$row){
        $id=(string)($locationIds[$i]??'');$existing=$oldDelivery[$id]??null;
        if($id!==''&&(!$existing||isset($savedLocations[$id])))throw new RuntimeException('Invalid delivery identity.');
        if($existing&&(strtolower($existing['delivery_status']??'pending')!=='pending'||!empty($existing['dispatch_datetime'])))throw new RuntimeException('Dispatched delivery locations must be changed through dispatch management.');
        $productQty=(int)$row['delivery_qty'];if($productQty<=0)throw new RuntimeException('Delivery quantity must be positive.');
        $deliveryProduct=(string)$row['addressforproduct'];$deliveryTotals[$deliveryProduct]=($deliveryTotals[$deliveryProduct]??0)+$productQty;
        $numbers=editQuery($krfconn,"SELECT DISTINCT stockorder_no FROM krf_sales_order_items WHERE projectid=? AND sono=? AND product_id=? AND quantity>0 AND LOWER(COALESCE(status,''))<>'cancelled'",[$projectid,$sono,$row['addressforproduct']]);
        $values=['projectid'=>$projectid,'psono'=>$psono,'sono'=>$sono,'clientid'=>$clientid,'productid'=>$row['addressforproduct'],'stockorder_no'=>implode(',',array_column($numbers,'stockorder_no')),'deliveryqty'=>$productQty,'deliverycharges'=>$row['deliverycharges'],'deliveryaddress'=>$row['deliveryaddress'],'dest_latitude'=>$row['dest_latitude'],'dest_longitude'=>$row['dest_longitude'],'dest_state'=>$row['dest_state'],'dest_district'=>$row['dest_district'],'dest_city'=>$row['dest_city'],'dest_pincode'=>$row['dest_pincode'],'stateid'=>$row['dest_stateid'],'districtid'=>$row['dest_districtid'],'cityid'=>$row['dest_cityid'],'totaldistance'=>$row['totaldistance'],'freedistance'=>$row['freedistance'],'distance'=>$row['distance'],'remark'=>$row['remark'],'pickuppoint'=>$row['pickuppoint'],'pickuptype'=>$pickupfrom,'noofbox'=>$row['noofbox'],'boxperpack'=>$row['boxperpack'],'chargesperbox'=>$row['boxchargesperpack'],'freightamt'=>$row['boxtotalamt'],'updated_by'=>$loginid];
        if(!$existing)$values+=['created_by'=>$loginid,'delivery_status'=>'Pending'];
        ceWrite($krfconn,'krf_deliveryaddress_location',$values,$existing['id']??null);if($existing)$savedLocations[$id]=true;
    }
    foreach($deliveryTotals as $product=>$assigned){$ordered=editQuery($krfconn,"SELECT COALESCE(SUM(quantity),0) AS quantity FROM krf_sales_order_items WHERE projectid=? AND sono=? AND product_id=? AND quantity>0 AND LOWER(COALESCE(status,''))<>'cancelled'",[$projectid,$sono,$product])[0];if($assigned>(int)$ordered['quantity'])throw new RuntimeException('Delivery quantity exceeds the ordered product quantity.');}
    foreach($oldDelivery as $id=>$r)if(!isset($savedLocations[$id])){
        if(strtolower($r['delivery_status']??'pending')!=='pending'||!empty($r['dispatch_datetime']))throw new RuntimeException('An existing dispatched delivery cannot be removed.');
        ceWrite($krfconn,'krf_deliveryaddress_location',['delivery_status'=>'Cancelled','updated_by'=>$loginid],$r['id']);
    }
    $freights=$original['freight'];if(count($freights)>1)throw new RuntimeException('Multiple freight records need reconciliation before editing.');$f=$freights[0]??null;
    $freightChanged=!$f||(string)($f['zerocharge']??'no')!==(string)$is_zero_charge||abs((float)$f['freight_amount']-$total_f_amount)>.01||abs((float)$f['payable_freightamount']-$total_f_payable)>.01;
    if($f&&$freightChanged&&(!empty($f['freight_invoice_no'])||!empty($f['freightinvoicepath'])||!empty($f['trip_id'])))throw new RuntimeException('Adjust assigned-trip/invoiced freight through transport management.');
    if($freightChanged||!$f)ceWrite($krfconn,'krf_freightcharges',['projectid'=>$projectid,'psono'=>$psono,'sono'=>$sono,'clientid'=>$clientid,'freight_amount'=>$total_f_amount,'freight_discountpercent'=>$avg_discount_percent,'freight_discountamount'=>round($total_f_amount-$total_f_payable,2),'payable_freightamount'=>$total_f_payable,'zerocharge'=>$is_zero_charge,'remarks'=>implode(' | ',$all_f_remarks).' '.$remarks]+(!$f?['created_by'=>$loginid]:[]),$f['id']??null);
    $totals=editQuery($krfconn,"SELECT COALESCE(SUM(amount),0) AS amount FROM krf_sales_order_items WHERE projectid=? AND sono=? AND LOWER(COALESCE(status,''))<>'cancelled'",[$projectid,$sono])[0];
    ceWrite($krfconn,'krf_sales_order',['total_amount'=>$totals['amount'],'tax_amount'=>0,'grand_total'=>$totals['amount']],$original['sale']['id']);
    $after=ceSnapshot($krfconn,$psono,$projectid,$companyid,$subcompanyid);
    $businessChanged=editHasBusinessOrderChanges($original,$after);
    $notificationResult=null;
    if($businessChanged){
        // One complete before/after snapshot per save, not fragmented item logs.
        ceLog($krfconn,$original,$loginid,(string)($loginname??$loginid),$changeReason,'Confirmed Order Updated',$original,$after);
        try { $notificationResult=recordOrderModificationNotifications($krfconn,$original,$after,true,$loginid,(string)($loginname??$loginid),$changeReason); }
        catch(Throwable $notificationError){$notificationResult=['status'=>'Warning','message'=>'Order saved, but modification messages could not be queued: '.$notificationError->getMessage().' Please correct the issue and edit/save again.'];}
        krfRecordCompletedAmendment($krfconn, $projectid, $psono, $clientid, (string)$loginid, (string)($loginname??$loginid), 'Confirmed', $original, $after);
    }
    $krfconn->commit();echo json_encode(['status'=>'success','message'=>'Confirmed sales order updated successfully.','psono'=>$psono,'sono'=>$sono,'clientid'=>$clientid,'notification'=>$notificationResult]);
}catch(Throwable $e){if(isset($krfconn))$krfconn->rollback();http_response_code(400);echo json_encode(['status'=>'error','message'=>$e->getMessage()]);}
