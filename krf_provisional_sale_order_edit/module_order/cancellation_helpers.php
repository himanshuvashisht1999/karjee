<?php
require_once __DIR__.'/confirmed_helpers.php';
function caAuthorize(){
    global $authconn,$companyid,$subcompanyid,$projectid,$loginid,$loginroleid;
    if(empty($loginid)||empty($_SESSION['sessionid'])||$_SESSION['sessionid']!==session_id())throw new RuntimeException('Login is required.');
    if((string)$loginroleid==='0')return;
    $modules=OnlyProject_loginmodulesdetails_frommaster($authconn,$companyid,$subcompanyid,$projectid,'allwithauth',allauthorizedmoduleid($authconn,$companyid,$subcompanyid,$projectid,$loginid));
    foreach((array)$modules as $module){
        $path=trim(str_replace('\\','/',explode('?',(string)($module['modulefoldername']??''))[0]),'/');
        if($path==='krf_provisional_sale_order_edit/confirmed_order_cancellation_admin.php')return;
    }
    throw new RuntimeException('Administration cancellation access is required.');
}
function caMoney($value){
    $value=trim((string)$value);
    if(!preg_match('/^\d{1,12}(?:\.\d{1,2})?$/',$value))throw new RuntimeException('Enter a valid non-negative amount with up to two decimal places.');
    [$whole,$decimal]=array_pad(explode('.',$value,2),2,'');return (int)$whole*100+(int)str_pad($decimal,2,'0');
}
function caDecimal($cents){return sprintf('%d.%02d',intdiv($cents,100),$cents%100);}
function caPreview($db,$psono,$scope){
    $s=ceSnapshot($db,$psono,$scope[2],$scope[0],$scope[1],true);$h=$s['header'];
    $payments=editQuery($db,'SELECT * FROM advance_payment_transactions WHERE companyid=? AND subcompanyid=? AND projectid=? AND clientid=? AND invoiceno IN (?,?) ORDER BY sno FOR UPDATE',[$scope[0],$scope[1],$scope[2],$h['clientid'],$psono,$s['sale']['sono']]);
    $client=editQuery($db,'SELECT invoicing_address FROM krf_client_master WHERE client_id=? AND projectid=? LIMIT 1',[$h['clientid'],$scope[2]]);$s['invoice_address']=$client[0]['invoicing_address']??'';
    $s['payments']=$payments;$s['production']=[];
    foreach($s['items'] as $item){
        $id=[$scope[2],$item['stockorder_no'],$item['product_id']];
        $stock=ceStock($db,...$id);
        $schedules=editQuery($db,'SELECT * FROM krf_polyhouse_sowingschedule WHERE projectid=? AND stockorder_no=? AND productid=? ORDER BY sowingschedulid FOR UPDATE',$id);
        $trays=editQuery($db,"SELECT sowingplantid,roottype,traycavity,status FROM krf_polyhouse_sowingplant_details WHERE projectid=? AND stockorder_no=? AND productid=? AND status IN ('Sowing','Polyhouse','Germination','Tray Received At Germination','Tray Allocated To Polyhouse') ORDER BY sowingplantid FOR UPDATE",$id);
        $s['production'][]=['stock'=>$stock,'schedules'=>$schedules,'trays'=>$trays];
    }
    return $s;
}
function caCancel($db,$s,$split,$reason,$actor,$name){
    ceAssertEditableItemStatuses($s);
    if(trim($reason)==='')throw new RuntimeException('Please enter the cancellation reason.');
    $h=$s['header'];$total=0;$refund=0;$held=0;$details=[];
    foreach($s['payments'] as $p){
        $amount=caMoney($p['advanceamount']);if(!$amount)continue;
        if($p['verification']!=='verified')throw new RuntimeException('Payment '.$p['sno'].' is unverified. Resolve its verification before cancelling the order.');
        if(!empty($p['ledgerstatus']))throw new RuntimeException('Payment '.$p['sno'].' has a ledger status. Reconcile it with Accounts before cancellation.');
        if(($p['transaction_type']??'Receipt')!=='Receipt')throw new RuntimeException('This payment already has a cancellation/refund decision. Please refresh.');
        if(!isset($split[(string)$p['sno']]))throw new RuntimeException('Choose refund and retained advance amounts for every verified payment.');
        $r=caMoney($split[(string)$p['sno']]['refund']??'');$a=caMoney($split[(string)$p['sno']]['advance']??'');
        if($r>0 && $a>0)throw new RuntimeException('Choose either a full refund or full retained advance for each receipt.');
        if($r+$a!==$amount)throw new RuntimeException('Refund plus retained advance must equal payment '.$p['sno'].' amount.');
        $total+=$amount;$refund+=$r;$held+=$a;$details[]=[$p,caDecimal($r),caDecimal($a)];
    }
    foreach($s['delivery'] as $delivery)if(!in_array(strtolower(trim($delivery['delivery_status']??'')),['','pending','cancelled'],true)||!empty($delivery['trip_id'])||!empty($delivery['dispatch_datetime']))throw new RuntimeException('Release delivery/dispatch assignments before cancelling.');
    foreach($s['freight'] as $freight)if(!empty($freight['trip_id'])||!empty($freight['freight_invoice_no'])||!empty($freight['freightinvoicepath']))throw new RuntimeException('Release the trip or adjust its freight invoice before cancelling.');
    foreach($s['items'] as $item){
        $sale=ceSalesItem($s,$item);ceProtected($sale,0,true);
        $stock=ceStock($db,$h['projectid'],$item['stockorder_no'],$item['product_id']);
        ceAllocation($db,$s,$stock,['ProductID'=>$item['product_id']],0,$actor,$reason);
        ceRetireItem($db,$s,$item,$sale,$actor,$reason);
    }
    foreach($s['items'] as $item)ceCancelUnusedPendingProduction($db,$s,$item,$actor,$reason,$name);
    $note=date('d-m-Y H:i:s').' | Client order cancelled by '.$name.' ('.$actor.') | '.$reason.' | Actual sowing retained where production had started.';
    foreach($s['production'] as $production){$stock=$production['stock'];if((string)$stock['psono']===(string)$h['psono']&&$production['trays'])editQuery($db,"UPDATE krf_production_stockorder SET remarks=CONCAT(COALESCE(remarks,''),CHAR(10),?),updated_at=NOW() WHERE id=?",[$note,$stock['id']]);}
    foreach(['krf_provisional_sales_order'=>$h,'krf_sales_order'=>$s['sale']] as $table=>$header)ceWrite($db,$table,['status'=>'Cancelled','updated_by'=>$actor,'updated_at'=>date('Y-m-d H:i:s')],$header['id']);
    editQuery($db,"UPDATE krf_deliveryaddress_location SET delivery_status='Cancelled',remark=CONCAT(COALESCE(remark,''),CHAR(10),?),updated_by=?,updated_at=NOW() WHERE projectid=? AND psono=? AND delivery_status='Pending'",[$note,$actor,$h['projectid'],$h['psono']]);
    editQuery($db,"UPDATE krf_freightcharges SET status='Cancelled',remarks=CONCAT(COALESCE(remarks,''),CHAR(10),?),updated_at=NOW() WHERE projectid=? AND psono=?",[$note,$h['projectid'],$h['psono']]);
    foreach($details as [$p,$r,$a]){
        $type=caMoney($r)>0?'Refund Pending':'Retained Advance';
        $paymentNote=date('d-m-Y H:i:s').' | Cancelled PSO: '.$h['psono'].' | SO: '.$s['sale']['sono'].' | '.$type.' | Amount: '.$p['advanceamount'].' | By: '.$name.' ('.$actor.') | '.$reason;
        if($type==='Retained Advance')editQuery($db,"UPDATE advance_payment_transactions SET transaction_type=?,invoiceno='',allocated_datetime=NULL,allocatedby=NULL,remarks=CONCAT(COALESCE(remarks,''),CHAR(10),?),tedit=? WHERE sno=?",[$type,$paymentNote,$actor,$p['sno']]);
        else editQuery($db,"UPDATE advance_payment_transactions SET transaction_type=?,remarks=CONCAT(COALESCE(remarks,''),CHAR(10),?),tedit=? WHERE sno=?",[$type,$paymentNote,$actor,$p['sno']]);
    }
    ceLog($db,$s,$actor,$name,$reason,'Confirmed Order Cancelled',$s,['payment_table'=>'advance_payment_transactions','refund_pending'=>caDecimal($refund),'retained_advance'=>caDecimal($held)]);
    return $h['psono'];
}
