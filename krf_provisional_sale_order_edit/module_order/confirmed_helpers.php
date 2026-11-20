<?php
require_once __DIR__.'/edit_order_data.php';
function ceWrite($db,$table,array $values,$id=null){
    $allowed=['krf_provisional_sales_order','krf_sales_order','krf_provisional_sales_order_items','krf_sales_order_items','krf_production_stockorder','krf_productionorder_consumed','krf_deliveryaddress_location','krf_freightcharges','krf_order_change_log'];
    if(!in_array($table,$allowed,true))throw new RuntimeException('Invalid update table.');
    $columns=array_keys($values);foreach($columns as $c)if(!preg_match('/^[a-z_]+$/i',$c))throw new RuntimeException('Invalid field.');
    if($id!==null){$sql='UPDATE `'.$table.'` SET '.implode(',',array_map(fn($c)=>'`'.$c.'`=?',$columns)).' WHERE id=?';$params=array_values($values);$params[]=$id;editQuery($db,$sql,$params);return $id;}
    $sql='INSERT INTO `'.$table.'` (`'.implode('`,`',$columns).'`) VALUES ('.implode(',',array_fill(0,count($columns),'?')).')';editQuery($db,$sql,array_values($values));return $db->insert_id;
}
function ceAssertEditableItemStatuses($snapshot){
    foreach($snapshot['sales_items'] as $item){
        $status=strtolower(trim((string)($item['status']??'')));
        if($status==='cancelled'||$status==='cancel')continue;
        if(!in_array($status,['','pending','confirmed'],true))throw new RuntimeException('Editing and cancellation are disabled. Item '.$item['product_name'].' has status '.$item['status'].'. Please refresh the page.');
    }
}
function ceSnapshot($db,$psono,$project,$company,$sub,$lock=false){
    $s=editSnapshot($db,$psono,$project,$lock);$h=$s['header'];
    if((string)$h['companyid']!==(string)$company||(string)$h['subcompanyid']!==(string)$sub||strtolower($h['status'])!=='confirmed')throw new RuntimeException('Only a confirmed PSO in your project can be edited.');
    $suffix=$lock?' FOR UPDATE':'';
    $sale=editQuery($db,'SELECT * FROM krf_sales_order WHERE psono=? AND projectid=? AND companyid=? AND subcompanyid=?'.$suffix,[$psono,$project,$company,$sub]);
    if(count($sale)!==1)throw new RuntimeException('A unique sales order is required.');
    if(in_array(strtolower($sale[0]['status']),['cancelled','delivered','completed'],true))throw new RuntimeException('This sales order is closed or delivered.');
    $s['sale']=$sale[0];$s['sales_items']=editQuery($db,'SELECT * FROM krf_sales_order_items WHERE sono=? AND projectid=? ORDER BY id'.$suffix,[$sale[0]['sono'],$project]);
    $s['editing_allowed']=true;$s['editing_warning']='';try{ceAssertEditableItemStatuses($s);}catch(RuntimeException $e){$s['editing_allowed']=false;$s['editing_warning']=$e->getMessage();}
    $s['items']=array_values(array_filter($s['items'],fn($i)=>strtolower(trim($i['status']??''))!=='cancelled'&&(int)$i['quantity']>0));
    $s['delivery']=array_values(array_filter($s['delivery'],fn($i)=>strtolower($i['delivery_status']??'')!=='cancelled'));
    return $s;
}
function ceToken($s){return hash('sha256',json_encode($s,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));}
function ceLog($db,$s,$actor,$name,$reason,$action,$old,$new){
    ceWrite($db,'krf_order_change_log',['projectid'=>$s['header']['projectid'],'psono'=>$s['header']['psono'],'sono'=>$s['sale']['sono'],'order_type'=>'SO','action'=>$action,'reason'=>$reason,'before_json'=>json_encode($old,JSON_UNESCAPED_UNICODE),'after_json'=>json_encode($new,JSON_UNESCAPED_UNICODE),'created_by'=>$actor,'created_by_name'=>$name,'created_at'=>date('Y-m-d H:i:s')]);
}
function ceSalesItem($s,$item){
    // The same product/stock order can occur on multiple delivery dates.
    // Match the ORIGINAL date, not a newly edited date from the form.
    $rows=array_values(array_filter($s['sales_items'],fn($r)=>
        !in_array(strtolower(trim((string)($r['status']??''))),['cancel','cancelled'],true)
        && (string)$r['product_id']===(string)$item['product_id']
        && (string)$r['stockorder_no']===(string)$item['stockorder_no']
    ));
    $psoDate=substr(trim((string)($item['expecteddeliverydate']??'')),0,10);
    if(count($rows)>1){
        if($psoDate==='')throw new RuntimeException('Delivery date missing for '.$item['product_name'].'. Cannot identify its Sales Order row safely.');
        $rows=array_values(array_filter($rows,fn($r)=>
            substr(trim((string)($r['deliverydate']??'')),0,10)===$psoDate
        ));
    }
    if(count($rows)!==1)throw new RuntimeException(
        'Sales/PSO item mapping is ambiguous for '.$item['product_name'].' (delivery '.$psoDate.'). '.
        'A unique item reference is required when product, stock order and delivery date are identical. No changes saved.'
    );
    if((int)$rows[0]['quantity']!==(int)$item['quantity'])throw new RuntimeException('Sales and provisional quantities differ. Reconcile this item before editing.');
    return $rows[0];
}
function ceProtected($sale,$qty,$changed){
    $status=strtolower(trim((string)($sale['status']??'')));
    if($changed&&!in_array($status,['','pending','confirmed'],true))throw new RuntimeException('The item status no longer allows editing or cancellation. Please refresh the page.');
    $reserved=max((int)($sale['reservedqty']??0),(int)($sale['allocate_ordqty']??0),(int)($sale['dispatchedqty']??0),(int)($sale['shiftingqty']??0));
    if($qty<$reserved)throw new RuntimeException('Release existing reservations/allocations before decreasing this quantity.');
    if($changed&&(!empty($sale['invoiceno'])||!empty($sale['invoicedate'])||!empty($sale['ledgerstatus'])||!empty($sale['invoicefilepath'])||!empty($sale['proformainvno'])||!empty($sale['proformainvpath'])||(int)($sale['dispatchedqty']??0)>0))throw new RuntimeException('This item is invoiced or dispatched. Use the invoice/dispatch adjustment routine first.');
}
function ceStock($db,$project,$number,$product){
    // Existing confirmed order stock references are mandatory. Do not silently
    // create a replacement stock order or borrow another item's stock.
    $number=trim((string)$number);
    if($number==='')throw new RuntimeException(
        'Confirmed order data mismatch: the saved PSO item has no Stock Order No. '.
        '(project '.(string)$project.', product '.(string)$product.'). No changes saved.'
    );
    $r=editQuery($db,'SELECT * FROM krf_production_stockorder WHERE projectid=? AND stockorder_no=? AND productid=? FOR UPDATE',[$project,$number,$product]);
    if(count($r)===0){
        // Read-only diagnostics to distinguish a missing production record from
        // an incorrect project or product association.
        $sameNumber=editQuery($db,'SELECT projectid,productid FROM krf_production_stockorder WHERE stockorder_no=? LIMIT 5',[$number]);
        $hint=$sameNumber?'Stock number exists, but its project/product does not match.':'No production stock record has this stock number.';
        throw new RuntimeException('Production stock not found for project '.(string)$project.
            ', stock '. $number.', product '.(string)$product.'. '.$hint.' No changes saved.');
    }
    if(count($r)>1)throw new RuntimeException('Duplicate production stock records for project '.(string)$project.
        ', stock '.$number.', product '.(string)$product.'. No changes saved.');
    if(in_array(strtolower(trim($r[0]['status']??'')),['cancel','cancelled','deleted'],true))throw new RuntimeException('Selected stock is cancelled: '.$number.'. Please select stock again.');
    return $r[0];
}

function ceSourceCapacity($db,$stock,$lock=false){
    $base=(float)$stock['quantity']+(float)($stock['Qty_from_readysale']??0);
    $client=editQuery($db,'SELECT is_trader FROM krf_client_master WHERE projectid=? AND client_id=? LIMIT 1',[$stock['projectid'],$stock['clientid']]);
    if((int)($client[0]['is_trader']??0)!==1)return $base;
    $ready=editQuery($db,"SELECT qtypertray FROM krf_readytosale_stocks WHERE projectid=? AND stockorder_no=? AND productid=? AND LOWER(REPLACE(TRIM(COALESCE(status,'')),' ',''))='readytosale'".($lock?' FOR UPDATE':''),[$stock['projectid'],$stock['stockorder_no'],$stock['productid']]);
    $physical=0;foreach($ready as $tray)$physical+=(float)$tray['qtypertray'];
    return max($base,$physical);
}
function ceAllocation($db,$s,$stock,$product,$qty,$actor,$reason){
    $project=$s['header']['projectid'];$psono=$s['header']['psono'];
    $rows=editQuery($db,"SELECT * FROM krf_productionorder_consumed WHERE projectid=? AND psono=? AND stockorderno=? AND product_id=? AND LOWER(COALESCE(confirmstatus,'')) NOT IN ('cancel','cancelled') ORDER BY id FOR UPDATE",[$project,$psono,$stock['stockorder_no'],$product['ProductID']]);
    // Lock all competing allocations before checking source-stock availability.
    $all=editQuery($db,"SELECT * FROM krf_productionorder_consumed WHERE projectid=? AND stockorderno=? AND product_id=? AND LOWER(COALESCE(confirmstatus,'')) NOT IN ('cancel','cancelled') ORDER BY id FOR UPDATE",[$project,$stock['stockorder_no'],$product['ProductID']]);
    $other=0;foreach($all as $r)if((string)$r['psono']!==(string)$psono)$other+=(float)$r['consumed_qty'];
    $available=max(0,ceSourceCapacity($db,$stock,true)-$other);
    if($qty>$available)throw new RuntimeException('Stock availability has changed for '.$stock['stockorder_no'].'. Requested '.$qty.' plants; only '.$available.' are available. Please select stock again.');
    if(count($rows)>1)throw new RuntimeException('Multiple consumed entries need reconciliation for this item.');
    if($rows){
        $r=$rows[0];
        $reserved=max((int)$r['allocate_healthyplant']+(int)$r['allocate_unhealthyplant'],(int)($r['dispatchedqty']??0));
        if($qty<$reserved)throw new RuntimeException('Release the production allocation before reducing this item.');
        $note=date('Y-m-d H:i:s').' | By '.$actor.' | Stock '.$stock['stockorder_no'].' | Quantity '.$r['consumed_qty'].' -> '.$qty.' | '.$reason;
        $values=['consumed_qty'=>$qty,'confirmstatus'=>$qty>0?'Confirmed':'cancel','remark'=>trim(($r['remark']??'')."\n".$note),'updated_at'=>date('Y-m-d H:i:s')];
        if($qty===0)$values['status']='Cancelled';
        ceWrite($db,'krf_productionorder_consumed',$values,$r['id']);
    }
    elseif($qty>0)ceWrite($db,'krf_productionorder_consumed',['projectid'=>$project,'psono'=>$psono,'sono'=>$s['sale']['sono'],'stockorderno'=>$stock['stockorder_no'],'product_id'=>$product['ProductID'],'product_name'=>$product['ProductName'],'consumed_qty'=>$qty,'wastage_qty'=>0,'confirmstatus'=>'Confirmed','status'=>'pending','extraqtyforshifting'=>0,'created_by'=>$actor,'remark'=>$reason]);
}
function ceNewProduction($db,$s,$p,$qty,$delivery,$actor,$reason,$actorName=''){
    $h=$s['header'];$number=getNewProductionStockOrderNo($db,$h['companyid'],$h['projectid']);
    $dates=getSowingDatesWithSessionCorrection($db,$delivery,$h['projectid'],$p['ProductID']);
    $root=$dates['RootSeedId']['sowingdate']??null;$scion=$dates['ScionSeedId']['sowingdate']??$root;
    if(!$root||$root<date('Y-m-d')||!$scion||$scion<date('Y-m-d'))throw new RuntimeException('Requested delivery does not allow a valid new sowing date. Choose a later delivery date.');
    $id=ceWrite($db,'krf_production_stockorder',['psono'=>$h['psono'],'stockorder_no'=>$number,'projectid'=>$h['projectid'],'companyid'=>$h['companyid'],'subcompanyid'=>$h['subcompanyid'],'clientid'=>$h['clientid'],'productid'=>$p['ProductID'],'product_name'=>$p['ProductName'],'quantity'=>$qty,'expected_delivery_date'=>$delivery,'sowingdate'=>$root,'scionsowingdate'=>$scion,'status'=>'Confirmed','created_by'=>$actor,'remarks'=>$reason]);
    // Existing helper retains Root/Scion rules and 30% buffer. Pass the real PSO explicitly.
    createSowingScheduleForNewStockOrder($db,$h['projectid'],$number,$p['ProductID'],$p['ProductName'],$qty,$root,$scion,$actor,null,null,null,$h['psono'],'edit_confirmed',$actorName,$reason);
    $schedules=editQuery($db,'SELECT * FROM krf_polyhouse_sowingschedule WHERE projectid=? AND stockorder_no=?',[$h['projectid'],$number]);
    $g=strtolower(trim((string)($p['IsGrafted']??'')));$expectedTypes=in_array($g,['1','yes','grafted'],true)?['Root','Scion']:['Scion'];$expected=count($expectedTypes);
    if(count($schedules)!==$expected)throw new RuntimeException('Sowing schedule could not be created completely.');
    $actualTypes=array_column($schedules,'roottype');sort($actualTypes);sort($expectedTypes);
    if($actualTypes!==$expectedTypes)throw new RuntimeException('Incorrect Root/Scion sowing schedules. No changes saved.');
    foreach($schedules as $r)if((string)$r['psono']!==$h['psono']||$r['status']!=='pending'||(int)$r['sowingqty']!==(int)ceil($qty*1.30))throw new RuntimeException('Invalid new sowing schedule.');
    return ceStock($db,$h['projectid'],$number,$p['ProductID']);
}
function cePersistItem($db,$s,$old,$sale,$p,$qty,$stock,$v,$delivery,$actor){
    $h=$s['header'];$base=['projectid'=>$h['projectid'],'clientid'=>$h['clientid'],'psono'=>$h['psono'],'product_sku'=>$p['sku'],'product_id'=>$p['ProductID'],'product_name'=>$p['ProductName'],'quantity'=>$qty,'stockorder_no'=>$stock,'status'=>'Confirmed']+$v;
    $pv=$base+['expecteddeliverydate'=>$delivery];if(!$old)$pv['created_by']=$actor;
    $id=ceWrite($db,'krf_provisional_sales_order_items',$pv,$old['id']??null);
    $sv=$base+['sono'=>$s['sale']['sono'],'deliverydate'=>$delivery,'tax_amount'=>0,'ammendedby'=>$actor,'updateamended'=>date('Y-m-d H:i:s')];if(!$sale)$sv['created_by']=$actor;
    ceWrite($db,'krf_sales_order_items',$sv,$sale['id']??null);
    return $id;
}
// Preserve financial values in a readable note before clearing the cancelled line.
function ceCancelledItemValues($item,$actor,$reason){
    $fields=['quantity','rate','freight_charges','taxableamount','cgst_percent','cgst_amount','sgst_percent','sgst_amount','igst_percent','igst_amount','discount_percent','discount_amount','amount','roundoffamount','tax_amount'];
    $values=['status'=>'Cancelled'];$original=[];
    foreach($fields as $field)if(array_key_exists($field,$item)){$original[]=$field.'='.($item[$field]??'NULL');$values[$field]=0;}
    $note=date('Y-m-d H:i:s').' | Cancelled by '.$actor.' | Stock '.($item['stockorder_no']??'').' | '.$reason.' | Previous values: '.implode(', ',$original);
    $values['remark']=trim(($item['remark']??'')."\n".$note);
    return $values;
}
function ceRetireItem($db,$s,$old,$sale,$actor,$reason){
    ceProtected($sale,0,true);
    ceWrite($db,'krf_provisional_sales_order_items',ceCancelledItemValues($old,$actor,$reason),$old['id']);
    ceWrite($db,'krf_sales_order_items',ceCancelledItemValues($sale,$actor,$reason)+['ammendedby'=>$actor,'updateamended'=>date('Y-m-d H:i:s')],$sale['id']);
}

// Cancel only unused, exclusively owned production before actual sowing.
function ceCancelUnusedPendingProduction($db,$s,$item,$actor,$reason,$actorName){
    $h=$s['header'];$stock=ceStock($db,$h['projectid'],$item['stockorder_no'],$item['product_id']);
    if(trim((string)($stock['psono']??''))!==$h['psono'])return;
    if((string)$stock['companyid']!==(string)$h['companyid']||(string)$stock['subcompanyid']!==(string)$h['subcompanyid'])return;
    $identity=[$h['projectid'],$stock['stockorder_no'],$item['product_id']];
    // Current reads include this order's replacement items as well as other orders.
    $consumed=editQuery($db,"SELECT id FROM krf_productionorder_consumed WHERE projectid=? AND stockorderno=? AND product_id=? AND consumed_qty>0 AND LOWER(TRIM(COALESCE(confirmstatus,''))) NOT IN ('cancel','cancelled') AND LOWER(TRIM(COALESCE(status,''))) NOT IN ('cancel','cancelled') FOR UPDATE",$identity);
    $psoItems=editQuery($db,"SELECT id FROM krf_provisional_sales_order_items WHERE projectid=? AND stockorder_no=? AND product_id=? AND quantity>0 AND LOWER(TRIM(COALESCE(status,''))) NOT IN ('cancel','cancelled') FOR UPDATE",$identity);
    $soItems=editQuery($db,"SELECT id FROM krf_sales_order_items WHERE projectid=? AND stockorder_no=? AND product_id=? AND quantity>0 AND LOWER(TRIM(COALESCE(status,''))) NOT IN ('cancel','cancelled') FOR UPDATE",$identity);
    if($consumed||$psoItems||$soItems)return;
    foreach(['sowingstatus','scionsowingstatus'] as $field)if(!in_array(strtolower(trim((string)($stock[$field]??''))),['','pending'],true))return;
    $actual=editQuery($db,"SELECT sowingplantid FROM krf_polyhouse_sowingplant_details WHERE projectid=? AND stockorder_no=? AND productid=? AND status NOT IN ('UnConfirmSowing','Removed') FOR UPDATE",$identity);
    if($actual)return;
    $schedules=editQuery($db,"SELECT * FROM krf_polyhouse_sowingschedule WHERE projectid=? AND stockorder_no=? AND productid=? AND LOWER(TRIM(status)) NOT IN ('delete','cancel','cancelled','removed','notrequired') FOR UPDATE",$identity);
    foreach($schedules as $schedule)if($schedule['status']!=='pending'||trim((string)$schedule['psono'])!==$h['psono'])return;
    $note=date('Y-m-d H:i:s').' | Cancelled before sowing by '.$actor.' | PSO '.$h['psono'].' | Stock '.$stock['stockorder_no'].' | Previous production quantity '.$stock['quantity'].' | '.$reason;
    // Use the existing removed schedule status for cancelled unused production.
    editQuery($db,"UPDATE krf_polyhouse_sowingschedule SET status='removed',remarks=CONCAT(COALESCE(remarks,''),CHAR(10),?),updated_by=?,updated_at=NOW() WHERE projectid=? AND stockorder_no=? AND productid=? AND status='pending'",array_merge([$note,$actor],$identity));
    ceWrite($db,'krf_production_stockorder',['status'=>'Cancelled','sowingdate'=>null,'scionsowingdate'=>null,'sowingstatus'=>'cancelled','scionsowingstatus'=>'cancelled','workingstatus'=>null,'remarks'=>trim(($stock['remarks']??'')."\n".$note),'updated_at'=>date('Y-m-d H:i:s')],$stock['id']);
    ceLog($db,$s,$actor,$actorName,$reason,'Production Cancelled Before Sowing',['stock'=>$stock,'schedules'=>$schedules],['stockorder_no'=>$stock['stockorder_no'],'production_status'=>'Cancelled','schedule_status'=>'removed']);
}

// Revise only unsown balance of exclusively owned production; never rewrite actual trays.
function ceReduceRemainingSowing($db,$snapshot,$stock,$product,$newQty,$actor,$actorName){
    $h=$snapshot['header'];
    if ((string)($stock['psono']??'')!==(string)$h['psono']) return;
    $identity=[$h['projectid'],$stock['stockorder_no'],$product['ProductID']];
    foreach (['krf_provisional_sales_order_items'=>'stockorder_no','krf_sales_order_items'=>'stockorder_no','krf_productionorder_consumed'=>'stockorderno'] as $table=>$column) {
        $quantity=$table==='krf_productionorder_consumed'?'consumed_qty':'quantity';
        $other=editQuery($db,"SELECT id FROM `$table` WHERE projectid=? AND `$column`=? AND ".($table==='krf_productionorder_consumed'?'product_id':'product_id')."=? AND COALESCE(psono,'')<>? AND `$quantity`>0 AND LOWER(TRIM(COALESCE(status,''))) NOT IN ('cancel','cancelled') LIMIT 1 FOR UPDATE",array_merge($identity,[$h['psono']]));
        if($other)return;
    }
    $g=strtolower(trim((string)$product['IsGrafted']));$types=in_array($g,['1','yes','grafted'],true)?['Root','Scion']:['Scion'];
    $schedules=editQuery($db,"SELECT * FROM krf_polyhouse_sowingschedule WHERE projectid=? AND stockorder_no=? AND productid=? AND psono=? AND status NOT IN ('delete','removed','notrequired') FOR UPDATE",array_merge($identity,[$h['psono']]));
    $trays=editQuery($db,"SELECT * FROM krf_polyhouse_sowingplant_details WHERE projectid=? AND stockorder_no=? AND productid=? AND psono=? AND status IN ('Sowing','Polyhouse','Germination','Tray Received At Germination','Tray Allocated To Polyhouse') FOR UPDATE",array_merge($identity,[$h['psono']]));
    $actual=[];foreach($trays as $tray)$actual[$tray['roottype']]=($actual[$tray['roottype']]??0)+max(0,(int)$tray['traycavity']);
    // Grafted production needs matching Root and Scion plans: retain the larger requirement.
    $commonTarget=0;
    foreach($types as $type){
        $matching=array_values(array_filter($schedules,fn($r)=>$r['roottype']===$type));
        if(count($matching)!==1)throw new RuntimeException('Check the '.$type.' sowing schedule for '.$stock['stockorder_no'].' before reducing quantity.');
        $buffer=(float)$matching[0]['failurebufferpercent'];
        if(!is_finite($buffer)||$buffer<0)throw new RuntimeException('Invalid sowing buffer for '.$stock['stockorder_no'].'.');
        $factor=10000+(int)round($buffer*100);
        $commonTarget=max($commonTarget,$actual[$type]??0,(int)ceil($newQty*$factor/10000));
        if(!in_array($matching[0]['status'],['pending','inprogress','edited'],true))$commonTarget=max($commonTarget,(int)$matching[0]['sowingqty']);
    }
    $changes=[];$productionBases=[];
    foreach($types as $type){
        $rows=array_values(array_filter($schedules,fn($r)=>$r['roottype']===$type));
        if(count($rows)!==1)throw new RuntimeException('Check the '.$type.' sowing schedule for '.$stock['stockorder_no'].' before reducing quantity.');
        $row=$rows[0];$sown=$actual[$type]??0;$planned=(int)$row['sowingqty'];
        if($sown>=$planned || !in_array($row['status'],['pending','inprogress','edited'],true)) {
            $productionBases[]=(int)$row['orderqty'];
            continue; // preserve fully sown or completed schedules
        }
        $buffer=(float)$row['failurebufferpercent'];
        if(!is_finite($buffer)||$buffer<0)throw new RuntimeException('Invalid sowing buffer for '.$stock['stockorder_no'].'.');
        // Integer ratios avoid floating-point ceil errors at exact boundaries.
        $factor=10000+(int)round($buffer*100);
        $target=$commonTarget;
        $retainedBase=max((int)$newQty,(int)ceil($target*10000/$factor));
        if($target>$planned && count($types)===1) { $productionBases[]=(int)$row['orderqty']; continue; }
        $productionBases[]=$retainedBase;
        if($target===$planned && $retainedBase===(int)$row['orderqty'])continue;
        $note=date('d-m-Y H:i:s').' | Confirmed order quantity decreased | PSO '.$h['psono'].' | '.$type.' | Base quantity '.$row['orderqty'].' -> '.$retainedBase.' | Planned '.$planned.' -> '.$target.' | Actual sown '.$sown.' | Remaining '.max(0,$target-$sown).' | By '.$actor;
        editQuery($db,"UPDATE krf_polyhouse_sowingschedule SET orderqty=?,sowingqty=?,remarks=CONCAT(COALESCE(remarks,''),CHAR(10),?),updated_by=?,updated_at=NOW() WHERE sowingschedulid=? AND projectid=?",[$retainedBase,$target,$note,$actor,$row['sowingschedulid'],$h['projectid']]);
        // Synchronize repeated plan metadata only; preserve every actual tray and its lifecycle.
        $trayChanges=[];
        foreach($trays as $tray){
            if($tray['roottype']!==$type)continue;
            editQuery($db,"UPDATE krf_polyhouse_sowingplant_details SET orderqty=?,totalsowingqty=?,remarks=CONCAT(COALESCE(remarks,''),CHAR(10),?),updated_by=?,updated_at=NOW() WHERE sowingplantid=? AND projectid=? AND psono=? AND stockorder_no=? AND productid=? AND roottype=?",[$retainedBase,$target,$note,$actor,$tray['sowingplantid'],$h['projectid'],$h['psono'],$stock['stockorder_no'],$product['ProductID'],$type]);
            $trayChanges[]=['sowingplantid'=>$tray['sowingplantid'],'before_orderqty'=>$tray['orderqty'],'before_totalsowingqty'=>$tray['totalsowingqty'],'traycavity'=>$tray['traycavity']];
        }
        $changes[]=['roottype'=>$type,'before'=>$row,'new_orderqty'=>$retainedBase,'client_quantity'=>$newQty,'new_sowingqty'=>$target,'actual_sown'=>$sown,'tray_metadata_changes'=>$trayChanges];
    }
    if($changes){
        $productionQty=max($productionBases);
        $note=date('d-m-Y H:i:s').' | Confirmed decrease | Client qty '.$newQty.' | Retained production base '.$productionQty.' | Previous production qty '.$stock['quantity'].' | Actual sowing preserved | By '.$actor;
        ceWrite($db,'krf_production_stockorder',['quantity'=>$productionQty,'remarks'=>trim(($stock['remarks']??'')."\n".$note),'updated_at'=>date('Y-m-d H:i:s')],$stock['id']);
        ceLog($db,$snapshot,$actor,$actorName,'','Remaining Sowing Reduced',['stock'=>$stock,'schedules'=>$schedules],['production_quantity'=>$productionQty,'client_quantity'=>$newQty,'changes'=>$changes]);
    }
}
