<?php
ini_set('display_errors','0');
require_once dirname(__DIR__).'/krf_provisional_sale_order_edit/module_order/confirmed_access.php';
require_once dirname(__DIR__).'/krf_enquiry_followup/helpers.php';
header('Content-Type: application/json; charset=utf-8');
try {
 mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
 $scope=confirmedAjaxContext(false);
 $allowed=(string)$loginroleid==='0';
 if(!$allowed){$mods=OnlyProject_loginmodulesdetails_frommaster($authconn,$companyid,$subcompanyid,$projectid,'allwithauth',allauthorizedmoduleid($authconn,$companyid,$subcompanyid,$projectid,$loginid));foreach((array)$mods as $m){$path=trim(str_replace('\\','/',explode('?',(string)($m['modulefoldername']??''))[0]),'/');if(in_array($path,['krf_order_followup/shared_order_followup.php','krf_order_followup/provisional_order_followup.php','krf_order_followup/provisional_order_followup_admin.php'],true))$allowed=true;}}
 if(!$allowed)throw new RuntimeException('Order follow-up access required.');
 if(empty($_SESSION['order_followup_csrf'])||!hash_equals($_SESSION['order_followup_csrf'],(string)($_POST['csrf']??'')))throw new RuntimeException('Session expired.');
 $action=$_GET['action']??'list';$params=array_slice($scope,0,3);
 if($action==='list'){
  $data=efQuery($krfconn,"SELECT e.id,e.enquiry_no,e.clientname,e.product_name,e.status,f.psono,f.next_followup_date,f.reason,f.created_at FROM krf_product_enquiries e JOIN krf_product_enquiry_followup c ON c.id=(SELECT MIN(h.id) FROM krf_product_enquiry_followup h WHERE h.enquiry_id=e.id AND h.projectid=e.projectid AND h.reason='Converted' AND h.psono IS NOT NULL AND h.psono<>'') LEFT JOIN krf_product_enquiry_followup f ON f.id=(SELECT MAX(h.id) FROM krf_product_enquiry_followup h WHERE h.enquiry_id=e.id AND h.projectid=e.projectid) WHERE e.companyid=? AND e.subcompanyid=? AND e.projectid=? AND e.status='Converted' ORDER BY e.id DESC",$params);
  foreach($data as &$r){$r['psono']=$r['psono']?:efQuery($krfconn,"SELECT psono FROM krf_product_enquiry_followup WHERE enquiry_id=? AND projectid=? AND reason='Converted' ORDER BY id DESC LIMIT 1",[$r['id'],$scope[2]])[0]['psono']??'';}unset($r);
 }elseif($action==='history'||$action==='save'){
  $id=(int)($_POST['id']??0);if(!$id)throw new RuntimeException('Invalid enquiry ID.');
  $row=efQuery($krfconn,"SELECT e.*,c.psono FROM krf_product_enquiries e JOIN krf_product_enquiry_followup c ON c.id=(SELECT MIN(h.id) FROM krf_product_enquiry_followup h WHERE h.enquiry_id=e.id AND h.projectid=e.projectid AND h.reason='Converted' AND h.psono IS NOT NULL AND h.psono<>'') WHERE e.id=? AND e.companyid=? AND e.subcompanyid=? AND e.projectid=? AND e.status='Converted'",array_merge([$id],$params));if(!$row)throw new RuntimeException('Converted enquiry not found.');$r=$row[0];
  if($action==='history')$data=efQuery($krfconn,'SELECT reason,conversation,next_followup_date,created_by_name,created_at,psono FROM krf_product_enquiry_followup WHERE enquiry_id=? AND companyid=? AND subcompanyid=? AND projectid=? ORDER BY id DESC LIMIT 200',array_merge([$id],$params));
  else {$reason=(string)($_POST['reason']??'');if(!in_array($reason,['Call Me Later','Completed'],true))throw new RuntimeException('Invalid outcome.');$remarks=trim((string)($_POST['remarks']??''));if($remarks==='')throw new RuntimeException('Remarks required.');$next=null;if($reason==='Call Me Later'){$value=str_replace('T',' ',(string)($_POST['next']??''));$dt=DateTimeImmutable::createFromFormat('!Y-m-d H:i',$value,new DateTimeZone('Asia/Kolkata'));if(!$dt||$dt->format('Y-m-d H:i')!==$value||$dt<(new DateTimeImmutable('now',new DateTimeZone('Asia/Kolkata')))->modify('+15 minutes'))throw new RuntimeException('Next follow-up must be at least 15 minutes from now.');$next=$dt->format('Y-m-d H:i:s');}efLog($krfconn,$r,$scope,(string)($loginname??$loginid),$reason,$remarks,$next,$r['psono']);$data=['saved'=>true];}
 }else throw new RuntimeException('Invalid action.');
 echo json_encode(['status'=>'success','data'=>$data]);
}catch(Throwable $e){http_response_code(400);echo json_encode(['status'=>'error','message'=>$e->getMessage()]);}
