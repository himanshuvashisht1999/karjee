<?php
ini_set('display_errors','0');
require __DIR__.'/confirmed_access.php';
require __DIR__.'/confirmed_helpers.php';
header('Content-Type: application/json');
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
try{
 $scope=confirmedAjaxContext();$product=trim((string)($_POST['productid']??''));$psono=trim((string)($_POST['edit_psono']??''));
 ceSnapshot($krfconn,$psono,$scope[2],$scope[0],$scope[1]);
 $stocks=editQuery($krfconn,"SELECT s.*,p.ProductName AS productname FROM krf_production_stockorder s LEFT JOIN product_master p ON p.ProductID=s.productid WHERE s.projectid=? AND s.companyid=? AND s.subcompanyid=? AND s.productid=? AND LOWER(TRIM(s.status))='confirmed' ORDER BY s.expected_delivery_date,s.stockorder_no",[$scope[2],$scope[0],$scope[1],$product]);$out=[];
 foreach($stocks as $s){
  $alloc=editQuery($krfconn,"SELECT COALESCE(SUM(CASE WHEN LOWER(TRIM(confirmstatus))='confirmed' THEN consumed_qty ELSE 0 END),0) AS confirmed,COALESCE(SUM(CASE WHEN LOWER(TRIM(confirmstatus))<>'confirmed' OR confirmstatus IS NULL THEN consumed_qty ELSE 0 END),0) AS unconfirmed FROM krf_productionorder_consumed WHERE projectid=? AND stockorderno=? AND product_id=? AND LOWER(TRIM(COALESCE(confirmstatus,''))) NOT IN ('cancel','cancelled')",[$scope[2],$s['stockorder_no'],$product])[0];
  $capacity=ceSourceCapacity($krfconn,$s);$available=max(0,$capacity-(float)$alloc['confirmed']);if($available<=0)continue;
  $base=(float)$s['quantity']+(float)($s['Qty_from_readysale']??0);
  $out[]=['stksno'=>$s['id'],'stockorder_no'=>$s['stockorder_no'],'productid'=>$product,'productname'=>$s['productname'],'stkqty'=>$s['quantity'],'readytosale_qty'=>max(0,$capacity-$base),'confirmed_allocated'=>$alloc['confirmed'],'unconfirmed_allocated'=>$alloc['unconfirmed'],'availableqty'=>$available,'expected_delivery_date'=>$s['expected_delivery_date'],'displaydate'=>$s['expected_delivery_date']];
 }
 echo json_encode($out);
}catch(Throwable $e){http_response_code(400);echo json_encode(['status'=>'error','message'=>$e->getMessage()]);}
