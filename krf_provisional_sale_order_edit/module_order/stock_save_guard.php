<?php
require_once __DIR__.'/edit_order_data.php';
// Must be called inside the saving transaction. The stock row is the shared lock.
function orderLockAvailableStock($db,$project,$id,$product,$psono,$qty,&$requested,$allowReady=false){
    $stocks=editQuery($db,'SELECT * FROM krf_production_stockorder WHERE id=? AND projectid=? AND productid=? FOR UPDATE',[$id,$project,$product]);
    if(count($stocks)!==1)throw new RuntimeException('Selected stock no longer matches this project/product. Please select stock again.');
    $stock=$stocks[0];
    if(in_array(strtolower(trim($stock['status']??'')),['cancel','cancelled','deleted'],true))throw new RuntimeException('Selected stock is cancelled. Please select stock again.');
    $key=$stock['stockorder_no'].'|'.$product;
    // Locking reads see the latest committed allocations, even in REPEATABLE READ.
    $rows=editQuery($db,"SELECT psono,consumed_qty FROM krf_productionorder_consumed WHERE projectid=? AND stockorderno=? AND product_id=? AND LOWER(TRIM(COALESCE(confirmstatus,''))) NOT IN ('cancel','cancelled') AND LOWER(TRIM(COALESCE(status,''))) NOT IN ('cancel','cancelled') FOR UPDATE",[$project,$stock['stockorder_no'],$product]);
    $used=0;foreach($rows as $row)if((string)$row['psono']!==(string)$psono)$used+=(float)$row['consumed_qty'];
    // Cache capacity before this transaction adds its ready-stock contribution.
    if(!isset($requested[$key])){
        $capacity=(float)$stock['quantity']+(float)($stock['Qty_from_readysale']??0);
        if($allowReady){
            $ready=editQuery($db,"SELECT qtypertray FROM krf_readytosale_stocks WHERE projectid=? AND stockorder_no=? AND productid=? AND LOWER(REPLACE(TRIM(COALESCE(status,'')),' ',''))='readytosale' FOR UPDATE",[$project,$stock['stockorder_no'],$product]);
            $physical=0;foreach($ready as $tray)$physical+=(float)$tray['qtypertray'];
            $capacity=max($capacity,$physical);
        }
        $requested[$key]=['quantity'=>0,'capacity'=>$capacity];
    }
    $requested[$key]['quantity']+=(float)$qty;
    $available=max(0,$requested[$key]['capacity']-$used);
    if($requested[$key]['quantity']>$available)throw new RuntimeException('Stock availability has changed for '.$stock['stockorder_no'].'. Requested '.$requested[$key]['quantity'].' plants; only '.$available.' are available. Please select stock again.');
    return $stocks;
}
