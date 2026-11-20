<?php
// Read-only summary scoped by authenticated project, stock order and product.
function confirmedSowingSummary($db,$snapshot,$products){
 $productMap=[];foreach($products as $product)$productMap[(string)$product['ProductID']]=$product;
 $result=[];$seen=[];$project=$snapshot['header']['projectid'];
 foreach($snapshot['items'] as $item){
  $key=$item['stockorder_no'].'|'.$item['product_id'];if(isset($seen[$key]))continue;$seen[$key]=true;
  $stockRows=editQuery($db,'SELECT * FROM krf_production_stockorder WHERE projectid=? AND stockorder_no=? AND productid=?',[$project,$item['stockorder_no'],$item['product_id']]);
  if(count($stockRows)>1)throw new RuntimeException('Duplicate production stock identity: '.$item['stockorder_no'].' / product '.$item['product_id'].'. Please reconcile the duplicate records.');
  $stock=$stockRows[0]??null;
  $stockMissing=($stock===null);
  $scheduled=editQuery($db,"SELECT roottype,COALESCE(SUM(sowingqty),0) AS planned FROM krf_polyhouse_sowingschedule WHERE projectid=? AND stockorder_no=? AND productid=? AND LOWER(TRIM(status)) NOT IN ('delete','cancel','cancelled','removed','notrequired') GROUP BY roottype",[$project,$item['stockorder_no'],$item['product_id']]);
  $actual=editQuery($db,"SELECT roottype,SUM(GREATEST(COALESCE(traycavity,0),0)) AS actual,MAX(CAST(COALESCE(NULLIF(totalsowingqty,''),'0') AS DECIMAL(15,2))) AS recorded_plan FROM krf_polyhouse_sowingplant_details WHERE projectid=? AND stockorder_no=? AND productid=? AND psono=? AND status IN ('Sowing','Polyhouse','Germination','Tray Received At Germination','Tray Allocated To Polyhouse') GROUP BY roottype",[$project,$item['stockorder_no'],$item['product_id'],$snapshot['header']['psono']]);
  $product=$productMap[(string)$item['product_id']]??null;
  if(!$product)throw new RuntimeException('Product master is missing for '.$item['product_name'].'.');
  $g=strtolower(trim((string)($product['IsGrafted']??'')));
  $expected=in_array($g,['1','yes','grafted'],true)?['Root','Scion']:['Scion'];
  $types=[];foreach($expected as $type)$types[$type]=['planned'=>0,'actual'=>0,'has_plan'=>false];
  $unexpected=[];
  foreach($scheduled as $r){
   $type=ucfirst(strtolower(trim((string)$r['roottype'])));
   if(!isset($types[$type])){$unexpected[$type]=true;continue;}
   $types[$type]['planned']+=(float)$r['planned'];$types[$type]['has_plan']=true;
  }
  foreach($actual as $r){
   $type=ucfirst(strtolower(trim((string)$r['roottype'])));
   if(!isset($types[$type])){$unexpected[$type]=true;continue;}
   if(!$types[$type]['has_plan'])$types[$type]['planned']=max($types[$type]['planned'],(float)$r['recorded_plan']);
   $types[$type]['actual']+=(float)$r['actual'];
  }
  $ordered=0;foreach($snapshot['items'] as $i)if($i['stockorder_no']===$item['stockorder_no']&&(string)$i['product_id']===(string)$item['product_id'])$ordered+=(int)$i['quantity'];
  foreach($types as $type=>$q){$status=$q['actual']<=0?($q['planned']>0?'Pending':'Not scheduled'):($q['planned']<=0?'Sowing recorded':($q['actual']>=$q['planned']?'Done':'Partially sown'));if($unexpected)$status.=' — Check unexpected '.implode('/',array_keys($unexpected)).' records';if($stockMissing)$status='Production stock missing — '.$status;$result[]=['stockorder_no'=>$item['stockorder_no'],'product_name'=>$item['product_name'],'stock_qty'=>$stockMissing?null:(int)$stock['quantity'],'order_qty'=>$ordered,'roottype'=>$type,'planned'=>$q['planned'],'actual'=>$q['actual'],'remaining'=>max(0,$q['planned']-$q['actual']),'status'=>$status];}
 }
 return $result;
}
