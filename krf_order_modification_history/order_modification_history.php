<?php
/* KRF Order Modification History. Read-only. Deploy folder under project root. */
ob_start();
include '../header.php';
$project = (int)($projectid ?? 0);
if ($project <= 0 || !isset($krfconn) || !($krfconn instanceof mysqli)) { http_response_code(403); exit('Project session unavailable.'); }
// v20: generate a real PDF response (not an HTML download).
if (($_GET['action'] ?? '') === 'download_pdf' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  if (ob_get_level()) ob_end_clean();
  $base = dirname(__DIR__);
  $tcpdfPath = $base . '/pdfroutines/tcpdf/tcpdf.php';
  if (!is_file($tcpdfPath)) {
    http_response_code(503); header('Content-Type: text/plain; charset=utf-8');
    exit('TCPDF not found at pdfroutines/tcpdf/tcpdf.php');
  }
  $raw = (string)($_POST['log_html'] ?? '');
  $order = preg_replace('/[^A-Za-z0-9_-]/', '_', (string)($_POST['order_no'] ?? 'Order'));
  if ($raw === '' || strlen($raw) > 500000) {
    http_response_code(400); exit('Invalid or oversized log content.');
  }
  // Keep only simple formatting supported by TCPDF; the report itself is read-only.
  $safe = strip_tags($raw, '<h1><h2><h3><p><div><span><strong><b><em><i><br><ul><ol><li>');
  $safe = preg_replace('/\s(?:style|class|id|on\w+)\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $safe);
  require_once $tcpdfPath;
  $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
  $pdf->SetCreator('Karjee Farms');
  $pdf->SetTitle('Order Modification Log - ' . $order);
  $pdf->SetMargins(14, 15, 14);
  $pdf->SetAutoPageBreak(true, 16);
  $pdf->setPrintHeader(false);
  $pdf->setPrintFooter(false);
  $pdf->AddPage();
  $pdf->SetFont('dejavusans', '', 9);
  $pdf->writeHTML('<h2>Order Modification Log - ' . htmlspecialchars($order, ENT_QUOTES, 'UTF-8') . '</h2>' . $safe, true, false, true, false, '');
  $pdf->Output('Order_Modification_Log_' . $order . '.pdf', 'D');
  exit;
}
function omhDisplayDate($v){
  if (!$v) return '';
  $ts = strtotime((string)$v);
  return $ts === false ? (string)$v : date('d-m-Y H:i:s', $ts);
}
/* Classify historical entries conservatively; SO number alone does not establish edit stage. */
function omhModificationStage($action,$type=null){
  if($type==='SO')return 'Confirmed Order';
  if($type==='PSO')return 'Provisional Order';
  $action=strtolower(trim((string)$action));
  if(strpos($action,'provisional')!==false || strpos($action,'pso ')!==false) return 'Provisional Order';
  if(strpos($action,'confirmed')!==false || strpos($action,'sales order')!==false || strpos($action,'so ')===0) return 'Confirmed Order';
  return 'Unknown';
}
function omhEsc($v){return htmlspecialchars((string)($v??''),ENT_QUOTES,'UTF-8');}
function omhJson($s){$v=json_decode((string)$s,true);return json_last_error()===JSON_ERROR_NONE?$v:['_raw'=>(string)$s];}
function omhFlatten($value,$prefix='',$depth=0){
  if($depth>12)return [$prefix=>'[Nested data]'];
  if(!is_array($value))return [$prefix===''?'Value':$prefix=>$value];
  if(!$value)return [$prefix===''?'Value':$prefix=>'[]'];
  $out=[];
  foreach($value as $key=>$v){$path=$prefix===''?(string)$key:$prefix.'.'.$key;
    if(is_array($v)){$out+=omhFlatten($v,$path,$depth+1);}else{$out[$path]=$v;}}
  return $out;
}
function omhFormat($v){if(is_array($v))return json_encode($v,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);if($v===null)return '—';if(is_bool($v))return $v?'Yes':'No';return (string)$v;}
function omhItems($snapshot){
  if(!is_array($snapshot))return [];
  $items=$snapshot['items']??null;
  if(!is_array($items))return [];
  $out=[];
  foreach($items as $index=>$item){
    if(!is_array($item))continue;
    $key=!empty($item['id'])?'id:'.$item['id']:'product:'.($item['product_id']??$item['productid']??$item['product_name']??$index).':'.($item['stockorder_no']??'');
    $out[$key]=['name'=>(string)($item['product_name']??$item['productname']??$item['product_id']??'Unknown product'),
      'stock'=>(string)($item['stockorder_no']??$item['stockorderno']??''),
      'qty'=>(float)($item['quantity']??$item['qty']??0),
      'date'=>(string)($item['expecteddeliverydate']??$item['deliverydate']??'')];
  }
  return $out;
}
function omhItemDiff($before,$after){
  $old=omhItems($before);$new=omhItems($after);$changes=[];
  foreach($old as $key=>$item){
    if(!isset($new[$key])){$changes[]=['kind'=>'removed','name'=>$item['name'],'old'=>$item,'new'=>null];continue;}
    $updated=$new[$key];
    if($item['qty']!=$updated['qty'])$changes[]=['kind'=>'quantity','name'=>$updated['name'],'old'=>$item,'new'=>$updated];
    if($item['date']!==$updated['date'])$changes[]=['kind'=>'delivery','name'=>$updated['name'],'old'=>$item,'new'=>$updated];
    if($item['name']!==$updated['name'])$changes[]=['kind'=>'product','name'=>$updated['name'],'old'=>$item,'new'=>$updated];
  }
  foreach($new as $key=>$item)if(!isset($old[$key]))$changes[]=['kind'=>'added','name'=>$item['name'],'old'=>null,'new'=>$item];
  return ['original'=>array_values($old),'revised'=>array_values($new),'changes'=>$changes];
}
function omhOtherChanges($before,$after){
  $old=is_array($before)?$before:[];$new=is_array($after)?$after:[];
  unset($old['items'],$new['items']);
  $a=omhFlatten($old);$b=omhFlatten($new);$result=[];
  $ignore=['header.updated_at','header.updated_by','header.id','header.created_at'];
  foreach(array_unique(array_merge(array_keys($a),array_keys($b))) as $key){
    if(in_array($key,$ignore,true))continue;
    $x=$a[$key]??null;$y=$b[$key]??null;
    if(json_encode($x)!==json_encode($y))$result[]=['field'=>$key,'before'=>omhFormat($x),'after'=>omhFormat($y)];
  }
  return $result;
}
if(($_GET['action']??'')==='detail'){
  ob_end_clean();header('Content-Type: application/json; charset=utf-8');
  $p=(int)($_GET['projectid']??$project);$psono=trim((string)($_GET['psono']??''));
  if($p!==$project||$psono===''){http_response_code(400);echo json_encode(['error'=>'Invalid order.']);exit;}
  $stmt=$krfconn->prepare('SELECT id,psono,sono,order_type,action,reason,before_json,after_json,created_by,created_by_name,created_at FROM krf_order_change_log WHERE projectid=? AND psono=? ORDER BY created_at ASC,id ASC LIMIT 500');
  $stmt->bind_param('is',$project,$psono);$stmt->execute();$result=$stmt->get_result();$events=[];
  while($r=$result->fetch_assoc()){
    $before=omhJson($r['before_json']);$after=omhJson($r['after_json']);
    $full=isset($before['items'])&&isset($after['items']);
    $comparison=$full?omhItemDiff($before,$after):null;
    $fields=omhOtherChanges($before,$after);
    // Partial item events are legitimate audit records: show the values actually stored.
    // Never claim that a partial item is a complete order snapshot.
    $partialBefore=$full?[]:omhFlatten($before);
    $partialAfter=$full?[]:omhFlatten($after);
    $events[]=['id'=>(int)$r['id'],'date'=>$r['created_at'],'action'=>$r['action'],
      'reason'=>$r['reason'],'psono'=>$r['psono'],'sono'=>$r['sono'],'stage'=>omhModificationStage($r['action'],$r['order_type']),'by'=>$r['created_by_name']?:$r['created_by'],
      'full'=>$full,'comparison'=>$comparison,'fields'=>$fields,'before_data'=>$before,'after_data'=>$after,'partial_before'=>$partialBefore,'partial_after'=>$partialAfter,
      'note'=>$full?'':'Item-level audit record (not a complete order snapshot).' ];
  }
  echo json_encode(['events'=>$events,'limited'=>count($events)>=500],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit;
}
ob_end_flush();
/* Display all available audit history; no date-range restriction. */
/* Pre-aggregate orders before joining; avoid product-line multiplication. */
$sql="SELECT h.psono,h.sono,h.last_at,h.last_by,h.last_action,h.last_order_type,h.event_count,
 COALESCE(NULLIF(p.clientname,''),NULLIF(s.clientname,''),'—') AS client_name,
 COALESCE(NULLIF(p.mobileno,''),NULLIF(s.mobileno,''),'') AS phone,
 COALESCE(p.created_at,s.created_at) AS order_created,
 COALESCE(NULLIF(p.created_by,''),s.created_by,'') AS order_created_by
 FROM (
 SELECT x.projectid,x.psono,MAX(NULLIF(x.sono,'')) AS sono,COUNT(*) AS event_count,
 MAX(x.created_at) AS last_at,
 SUBSTRING_INDEX(GROUP_CONCAT(COALESCE(NULLIF(x.created_by_name,''),x.created_by) ORDER BY x.created_at DESC,x.id DESC SEPARATOR '|||'),'|||',1) AS last_by,
 SUBSTRING_INDEX(GROUP_CONCAT(x.action ORDER BY x.created_at DESC,x.id DESC SEPARATOR '|||'),'|||',1) AS last_action,
 SUBSTRING_INDEX(GROUP_CONCAT(COALESCE(x.order_type,'') ORDER BY x.created_at DESC,x.id DESC SEPARATOR '|||'),'|||',1) AS last_order_type
 FROM krf_order_change_log x WHERE x.projectid=?
 GROUP BY x.projectid,x.psono
 ) h
 LEFT JOIN (SELECT projectid,psono,MAX(id) id FROM krf_provisional_sales_order GROUP BY projectid,psono) pi ON pi.projectid=h.projectid AND pi.psono=h.psono
 LEFT JOIN krf_provisional_sales_order p ON p.id=pi.id
 LEFT JOIN (SELECT projectid,psono,MAX(id) id FROM krf_sales_order GROUP BY projectid,psono) si ON si.projectid=h.projectid AND si.psono=h.psono
 LEFT JOIN krf_sales_order s ON s.id=si.id
 ORDER BY h.last_at DESC,h.psono DESC LIMIT 5000";
$stmt=$krfconn->prepare($sql);$stmt->bind_param('i',$project);$stmt->execute();$rows=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<style>
.omh{padding:12px;max-width:100%;margin:auto;color:#243448}.omh .panel{background:#fff;border:1px solid #dbe3ec;border-radius:12px;padding:18px;margin-bottom:15px;box-shadow:0 2px 8px #2030400c}.omh h2{margin:0 0 6px;font-size:24px}.omh .muted{color:#657487;font-size:13px}.omh .controls{display:flex;gap:12px;flex-wrap:wrap;align-items:end}.omh label{display:block;font-size:12px;font-weight:700;margin-bottom:4px}.omh input,.omh select{border:1px solid #c7d3df;border-radius:6px;padding:9px;max-width:100%}.omh button,.omh .action{border:0;border-radius:6px;padding:9px 13px;background:#155bd4;color:#fff;cursor:pointer}.omh .secondary{background:#edf2f8;color:#18314d}.omh .tablewrap{overflow:auto;max-height:70vh;width:100%}.omh table{width:100%;min-width:1180px;table-layout:fixed;border-collapse:separate;border-spacing:0;font-size:13px}.omh th{position:sticky;top:0;background:#e8eef8;z-index:2;white-space:nowrap;text-align:left}.omh td,.omh th{border-bottom:1px solid #e2e8f0;padding:8px 6px;vertical-align:top;overflow-wrap:anywhere}.omh tbody tr:hover{background:#f5f8fc}.omh .filters th{top:37px;background:#f6f8fb}.omh .filters select{padding:5px 2px;width:100%;min-width:0;font-size:11px}.omh .nowrap{white-space:nowrap}.omh .overlay{position:fixed;inset:0;background:#0008;z-index:99990;display:none;align-items:center;justify-content:center;padding:15px}.omh .dialog{background:#fff;border-radius:12px;width:min(1100px,100%);max-height:92vh;display:flex;flex-direction:column;overflow:hidden}.omh .dialoghead{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid #dbe3ec}.omh .dialogbody{padding:15px;overflow:auto}.omh .event{border:1px solid #d8e2ed;border-radius:8px;margin:0 0 15px;overflow:hidden}.omh .eventhead{padding:11px;background:#f1f5fa;font-weight:600}.omh .event table{min-width:0;table-layout:auto}.omh .event td{white-space:normal;overflow-wrap:anywhere}.omh .event{padding-bottom:6px}.omh .eventhead{display:flex;flex-wrap:wrap;gap:10px}.omh .change-list{padding:10px 16px}.omh .change-list li{margin:8px 0}.omh .snapshot{margin:12px}.omh .snapshot h4{margin:8px 0}.omh .event .snapshot table{width:100%}.omh .summary{display:flex;gap:20px;flex-wrap:wrap;margin-top:12px;font-weight:600}.omh .empty{text-align:center;padding:25px}.omh .pager{display:flex;gap:10px;align-items:center;justify-content:flex-end;padding:10px 0}@media print{header,nav,aside,.omh .controls,.omh .filters,.omh .action,.omh .pager{display:none!important}.omh .tablewrap{max-height:none;overflow:visible}.omh .panel{box-shadow:none}}

/* Dedicated audit-grid sizing: prevent header and filter overlap in dashboard iframe. */
#omhApp .tablewrap {width:100%;max-width:100%;overflow-x:auto;overflow-y:auto;scrollbar-gutter:stable;}
#omhApp #omhTable {width:100%;min-width:1498px;table-layout:fixed;border-collapse:separate;border-spacing:0;}
#omhApp #omhTable th,#omhApp #omhTable td {box-sizing:border-box;min-width:0;max-width:none;white-space:normal;overflow-wrap:normal;word-break:normal;line-height:1.45;padding:10px 8px;vertical-align:middle;border-right:1px solid #dfe7f0;}
#omhApp #omhTable thead tr:first-child th {height:48px;top:0;z-index:5;white-space:normal;overflow-wrap:break-word;font-size:12px;line-height:1.3;text-align:left;}
#omhApp #omhTable thead tr.filters th {top:48px;z-index:4;height:48px;padding:6px 5px;background:#f5f8fc;}
#omhApp #omhTable thead tr.filters select {display:block;box-sizing:border-box;width:100%;min-width:0;max-width:100%;height:32px;padding:5px 20px 5px 7px;font-size:12px;line-height:1.2;background-color:#fff;}
#omhApp #omhTable tbody td {white-space:normal;overflow-wrap:anywhere;}
#omhApp #omhTable tbody td:nth-child(6),#omhApp #omhTable tbody td:nth-child(8) {white-space:nowrap;}
#omhApp #omhTable .omhView {white-space:normal;min-width:96px;}
#omhApp .event table {min-width:0;width:100%;table-layout:auto;}
@media print {#omhApp #omhTable {min-width:0;font-size:10px;}#omhApp #omhTable th,#omhApp #omhTable td {padding:4px;}}

/* Scoped audit cards: avoid collisions with dashboard/global .event styles. */
#omhApp .dialogbody .omh-audit-event{display:block!important;position:static!important;float:none!important;clear:both!important;min-height:0!important;height:auto!important;max-height:none!important;margin:0 0 12px!important;padding:0 0 10px!important;border:1px solid #d8e2ed;border-radius:8px;background:#fff;overflow:visible;}
#omhApp .dialogbody .omh-audit-event-head{display:block!important;position:static!important;min-height:0!important;height:auto!important;padding:10px 12px!important;margin:0 0 8px!important;background:#eef3fa;font-size:13px;font-weight:700;}
#omhApp .dialogbody .omh-audit-event .snapshot{margin:10px 12px;}
#omhApp .dialogbody .omh-audit-event .change-list{padding:8px 12px;}
#omhApp .dialogbody .omh-audit-event .omh-partial-note{padding:0 12px;margin:7px 0;font-size:13px;color:#52647b;}
#omhApp .dialogbody .omh-audit-event table{width:calc(100% - 24px);min-width:0;table-layout:auto;margin:8px 12px;border-collapse:collapse;font-size:12px;}
#omhApp .dialogbody .omh-audit-event table th{position:static!important;top:auto!important;background:#e7eef8;white-space:normal;}
#omhApp .dialogbody .omh-audit-event table td,#omhApp .dialogbody .omh-audit-event table th{padding:7px;border:1px solid #dfe5ed;overflow-wrap:anywhere;}
#omhApp .dialogbody .omh-audit-event details{margin:10px 12px;}
#omhApp .dialogbody .omh-audit-event details summary{cursor:pointer;padding:10px;background:#edf2fa;}

/* v4: native per-column search controls avoid dashboard Select2 auto-init */
#omhApp #omhTable thead tr.filters input.omh-column-search {
  display:block!important; width:100%!important; min-width:0!important;
  height:32px!important; padding:5px 7px!important; margin:0!important;
  box-sizing:border-box!important; border:1px solid #c8d5e4!important;
  border-radius:5px!important; background:#fff!important; font:12px Arial,sans-serif!important;
  appearance:none!important; box-shadow:none!important;
}
#omhApp #omhTable thead tr.filters th {height:46px!important;padding:5px 6px!important;}
#omhApp #omhTable {width:1658px!important; min-width:1658px!important;max-width:none!important;}
#omhApp .tablewrap {overflow-x:auto!important;overscroll-behavior-x:contain;}
/* Fixed-height modal with a single, independently scrolling content pane */
#omhApp #omhOverlay {position:fixed!important;inset:0!important;z-index:2147483000!important;
  overflow:hidden!important;align-items:center!important;justify-content:center!important;}
#omhApp #omhOverlay .dialog {display:flex!important;flex-direction:column!important;
  width:min(1100px,calc(100vw - 30px))!important;height:min(760px,calc(100dvh - 36px))!important;
  max-height:calc(100dvh - 36px)!important;min-height:0!important;overflow:hidden!important;}
#omhApp #omhOverlay .dialoghead {flex:0 0 auto!important;min-height:56px!important;}
#omhApp #omhOverlay .dialogbody {display:block!important;flex:1 1 auto!important;
  min-height:0!important;height:auto!important;max-height:none!important;
  overflow-y:scroll!important;overflow-x:hidden!important;overscroll-behavior:contain;
  scrollbar-width:auto;scrollbar-color:#9ba9b9 #f1f5f9;}
#omhApp #omhOverlay .dialogbody .omh-audit-event {display:flow-root!important;
  min-height:0!important;height:auto!important;margin:0 0 12px!important;}
#omhApp #omhOverlay .dialogbody .omh-audit-event-head {margin:0!important;}
#omhApp #omhOverlay .dialogbody .omh-audit-event > :first-child {margin-top:0!important;}
#omhApp #omhOverlay .dialogbody .omh-audit-event > :last-child {margin-bottom:0!important;}
@media (max-width:700px){#omhApp #omhOverlay {padding:6px!important;}
 #omhApp #omhOverlay .dialog {width:100%!important;height:calc(100dvh - 12px)!important;max-height:calc(100dvh - 12px)!important;}}

/* v5: column filters outside table layout; unaffected by dashboard table/filter CSS. */
#omhApp .omh-filterbar{display:block!important;box-sizing:border-box!important;width:100%!important;margin:0 0 12px;padding:12px;background:#f5f8fc;border:1px solid #dce5f0;border-radius:8px;}
#omhApp .omh-filterbar-title{font-size:12px;font-weight:700;margin-bottom:8px;color:#263b57;}
#omhApp .omh-filter-fields{display:grid!important;grid-template-columns:repeat(3,minmax(0,1fr))!important;gap:10px!important;align-items:start!important;}
#omhApp .omh-filter-field{display:flex!important;flex-direction:column!important;gap:4px!important;min-width:0!important;margin:0!important;font-size:11px!important;color:#334155;}
#omhApp .omh-filter-field span{display:block!important;white-space:normal!important;}
#omhApp .omh-filter-field input{display:block!important;position:static!important;box-sizing:border-box!important;width:100%!important;min-width:0!important;max-width:none!important;height:34px!important;padding:6px 9px!important;border:1px solid #bfcddd!important;border-radius:5px!important;background:#fff!important;font-size:13px!important;line-height:20px!important;appearance:none!important;}
#omhApp #omhTable thead th{position:sticky!important;top:0!important;}
#omhApp #omhOverlay{box-sizing:border-box!important;height:100vh!important;height:100dvh!important;}
#omhApp #omhOverlay .dialog{box-sizing:border-box!important;flex:0 1 auto!important;height:calc(100vh - 30px)!important;height:calc(100dvh - 30px)!important;}
#omhApp #omhOverlay .dialogbody{box-sizing:border-box!important;flex:1 1 0!important;min-height:0!important;overflow-y:auto!important;overflow-x:hidden!important;position:relative!important;}
#omhApp #omhOverlay .omh-audit-event{height:auto!important;min-height:0!important;}
@media(max-width:750px){#omhApp .omh-filter-fields{grid-template-columns:repeat(2,minmax(0,1fr))!important;}}
@media(max-width:450px){#omhApp .omh-filter-fields{grid-template-columns:minmax(0,1fr)!important;}}

/* v6: detached dialog. Styles no longer depend on #omhApp ancestor. */
#omhOverlay {position:fixed!important;inset:0!important;z-index:2147483000!important;box-sizing:border-box!important;display:none;align-items:center!important;justify-content:center!important;padding:15px!important;background:rgba(0,0,0,.55)!important;overflow:hidden!important;}
#omhOverlay .dialog {box-sizing:border-box!important;display:flex!important;flex-direction:column!important;flex:0 1 auto!important;width:min(1250px,100%)!important;height:calc(100vh - 30px)!important;height:calc(100dvh - 30px)!important;min-height:0!important;max-height:none!important;overflow:hidden!important;background:#fff!important;border-radius:12px!important;}
#omhOverlay .dialoghead {box-sizing:border-box!important;flex:0 0 auto!important;display:flex!important;align-items:center!important;justify-content:space-between!important;min-height:56px!important;padding:12px 18px!important;border-bottom:1px solid #dbe3ec!important;}
#omhOverlay .dialogbody {box-sizing:border-box!important;display:block!important;flex:1 1 0!important;height:0!important;min-height:0!important;max-height:none!important;overflow-y:auto!important;overflow-x:hidden!important;overscroll-behavior:contain!important;padding:15px!important;scrollbar-width:auto;}
#omhOverlay .omh-audit-event {display:flow-root!important;position:static!important;height:auto!important;min-height:0!important;max-height:none!important;margin:0 0 12px!important;padding:0 0 10px!important;border:1px solid #d8e2ed!important;border-radius:8px!important;background:#fff!important;}
#omhOverlay .omh-audit-event-head {display:block!important;padding:10px 12px!important;background:#eef3fa!important;font-weight:700!important;}
#omhOverlay .snapshot {margin:10px 12px!important;}
#omhOverlay .omh-audit-event table {width:calc(100% - 24px)!important;min-width:0!important;table-layout:auto!important;margin:8px 12px!important;border-collapse:collapse!important;font-size:12px!important;}
#omhOverlay .omh-audit-event table th {position:static!important;top:auto!important;background:#e7eef8!important;}
#omhOverlay .omh-audit-event table td,#omhOverlay .omh-audit-event table th {padding:7px!important;border:1px solid #dfe5ed!important;white-space:normal!important;overflow-wrap:anywhere!important;}
#omhOverlay .change-list {padding:8px 12px!important;}
#omhOverlay .omh-partial-note {padding:0 12px!important;}
#omhOverlay .omh-audit-event details {margin:10px 12px!important;}
#omhOverlay .omh-audit-event details summary {cursor:pointer!important;padding:10px!important;background:#edf2fa!important;color:#19334f!important;}
#omhOverlay button {border:0;border-radius:6px;padding:9px 13px;background:#edf2f8;color:#18314d;cursor:pointer;}

#omhOverlay .omh-audit-event .change-list{padding:12px 14px!important;font-size:14px;line-height:1.55;}
#omhOverlay .omh-audit-event details{margin:10px 14px!important;}
#omhOverlay .omh-audit-event details summary{font-weight:600!important;}

/* Compact tracking-report layout and Excel-style filters */
#omhApp .omh-date-form{display:grid;grid-template-columns:minmax(180px,1fr) minmax(180px,1fr) auto auto;gap:12px;align-items:end;margin-top:14px}
#omhApp .omh-date-form input{width:100%;height:42px}
#omhApp .omh-report-tools{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:12px}
#omhApp .omh-tool-buttons{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}
#omhApp .omh-global-search{margin:8px 0 14px;max-width:620px}
#omhApp .omh-global-search input{width:100%;height:40px}
#omhApp #omhTable{min-width:1658px!important;width:1658px!important}
#omhApp #omhTable thead th{position:sticky!important;top:0!important;z-index:4!important;vertical-align:middle!important;padding:9px 6px!important;white-space:normal!important}
#omhApp #omhTable thead th .omh-filter-trigger{display:inline-flex!important;float:right!important;align-items:center;justify-content:center;min-width:23px!important;width:23px!important;height:25px!important;margin:0 0 0 5px!important;padding:0!important;border:1px solid #b7c9dc!important;border-radius:4px!important;background:#fff!important;color:#284563!important;font-size:14px!important;line-height:1!important}
#omhApp #omhTable thead th .omh-filter-trigger.omh-filter-active{background:#d4e8ff!important;color:#084aa8!important;border-color:#3b82f6!important}
.omh-excel-popup{position:fixed!important;z-index:2147483600!important;box-sizing:border-box!important;width:280px!important;max-width:calc(100vw - 16px)!important;padding:12px!important;border:1px solid #b9cce0!important;border-radius:8px!important;background:#fff!important;box-shadow:0 10px 35px #0003!important;color:#23344c!important;font:13px Arial,sans-serif!important}
.omh-excel-popup[hidden]{display:none!important}
.omh-excel-popup strong{display:block;margin-bottom:9px}
.omh-excel-popup .omh-excel-search{width:100%;height:35px;box-sizing:border-box;margin-bottom:10px;border:1px solid #bbcbdc;border-radius:5px;padding:7px}
.omh-excel-popup label{display:block!important;padding:5px 0!important;cursor:pointer!important;font-weight:400!important;font-size:12px!important}
.omh-excel-popup input[type=checkbox]{width:15px!important;height:15px!important;vertical-align:middle!important}
.omh-excel-popup .omh-excel-values{max-height:165px;overflow-y:auto;border-top:1px solid #e4ebf3;border-bottom:1px solid #e4ebf3}
.omh-excel-popup .omh-excel-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:10px}
.omh-excel-popup .omh-excel-actions button{padding:7px 12px;background:#155bd4;color:white;border:0;border-radius:5px;cursor:pointer}
.omh-excel-popup .omh-excel-actions .omh-excel-clear{background:#edf2f8;color:#18314d}
@media(max-width:800px){#omhApp .omh-date-form{grid-template-columns:repeat(2,minmax(0,1fr))}#omhApp .omh-report-tools{align-items:flex-start}}
@media print{.omh-excel-popup,#omhApp .omh-filter-trigger,#omhApp .omh-global-search,#omhApp .omh-report-tools{display:none!important}}
#omhOverlay .omh-simple-table{width:100%!important;margin:0!important;table-layout:auto!important;border-collapse:collapse!important;font-size:13px!important}
#omhOverlay .omh-simple-table th{position:sticky!important;top:0!important;background:#e7eef8!important;z-index:2!important}
#omhOverlay .omh-simple-table td{vertical-align:top!important;white-space:normal!important;overflow-wrap:anywhere!important;min-width:100px!important}
#omhOverlay .omh-simple-table td:nth-child(1){min-width:125px!important}
#omhOverlay .omh-simple-table td:nth-child(2){min-width:120px!important}

/* Plain-language chronological modification log (no detail table). */
#omhOverlay .omh-log-entry{border:1px solid #dce5ef;border-radius:9px;background:#fff;margin:0 0 14px;padding:15px 18px;font-size:13px;line-height:1.65;overflow-wrap:anywhere}
#omhOverlay .omh-log-meta{display:flex;gap:6px 24px;flex-wrap:wrap;color:#314258;margin-bottom:10px}
#omhOverlay .omh-log-heading{font-weight:700;color:#172b4d;margin:8px 0}
#omhOverlay .omh-log-lines{display:grid;gap:7px}
#omhOverlay .omh-log-line{display:flex;align-items:baseline;gap:9px;padding-left:3px}
#omhOverlay .omh-log-symbol{font-weight:800;min-width:15px}
#omhOverlay .omh-log-line.added .omh-log-symbol{color:#17844a}
#omhOverlay .omh-log-line.deleted .omh-log-symbol{color:#b43b3b}
#omhOverlay .omh-log-line.edited .omh-log-symbol{color:#235db6}
#omhOverlay .omh-log-user{border-top:1px solid #e7edf5;margin-top:12px;padding-top:8px;color:#63748b;font-size:12px}

/* v15 printable text audit */
#omhOverlay .omh-log-actions{display:flex;flex-wrap:wrap;gap:7px;position:relative;z-index:5}#omhOverlay .omh-log-actions button{pointer-events:auto!important;cursor:pointer!important}
#omhOverlay .omh-log-actions button{padding:7px 10px}
#omhOverlay .omh-original{margin:12px 0 20px;padding:14px;border:1px solid #dce5ee;border-radius:8px;background:#fafcff}
#omhOverlay .omh-original h3{font-size:15px;margin:0 0 10px}
#omhOverlay .omh-original-list{display:grid;gap:6px}
#omhOverlay .omh-original-row{padding:5px 0;border-bottom:1px solid #e6edf3}
#omhOverlay .omh-original-note{font-size:12px;color:#657487;margin:8px 0}
@media print{
 body.omh-print-log > *{display:none!important}
 body.omh-print-log > #omhOverlay{display:block!important;position:static!important;inset:auto!important;background:white!important;padding:0!important;overflow:visible!important}
 body.omh-print-log #omhOverlay .dialog{display:block!important;position:static!important;width:100%!important;max-width:none!important;height:auto!important;max-height:none!important;overflow:visible!important;border:0!important;box-shadow:none!important}
 body.omh-print-log #omhOverlay .dialoghead{padding:0 0 12px!important}
 body.omh-print-log #omhOverlay .dialogbody{display:block!important;overflow:visible!important;height:auto!important;max-height:none!important;padding:0!important}
 body.omh-print-log #omhOverlay .omh-log-actions{display:none!important}
 body.omh-print-log #omhOverlay .omh-log-entry{break-inside:avoid;page-break-inside:avoid}
}

/* v20: modal actions and independent scrolling */
#omhOverlay .dialogbody {overflow-y:scroll!important;overflow-x:hidden!important;overscroll-behavior-y:contain!important;touch-action:pan-y!important;-webkit-overflow-scrolling:touch!important;scrollbar-width:auto!important;}
#omhOverlay .dialoghead {gap:12px!important;flex-wrap:wrap!important;}
#omhOverlay .omh-log-actions {display:flex!important;align-items:center!important;justify-content:flex-end!important;gap:8px!important;flex-wrap:wrap!important;}
#omhOverlay .omh-log-actions button {display:inline-flex!important;align-items:center!important;justify-content:center!important;padding:9px 14px!important;border-radius:6px!important;border:1px solid transparent!important;font-size:13px!important;font-weight:600!important;cursor:pointer!important;opacity:1!important;box-shadow:none!important;}
#omhOverlay #omhDownloadLog {background:#167347!important;color:#fff!important;border-color:#11633b!important;}
#omhOverlay #omhPrintLog {background:#155bd4!important;color:#fff!important;border-color:#0e4fbf!important;}
#omhOverlay #omhClose {background:#e8edf3!important;color:#26374b!important;border-color:#c8d1dd!important;}
#omhOverlay #omhDownloadLog:hover {background:#105e39!important;}
#omhOverlay #omhPrintLog:hover {background:#104ab1!important;}
#omhOverlay #omhClose:hover {background:#d6e0eb!important;}
#omhOverlay .dialogbody::-webkit-scrollbar {width:12px;}
#omhOverlay .dialogbody::-webkit-scrollbar-thumb {background:#97a7ba;border:3px solid #f2f5f9;border-radius:10px;}
@media(max-width:600px){#omhOverlay .dialoghead{align-items:flex-start!important;}#omhOverlay .omh-log-actions{justify-content:flex-start!important;}#omhOverlay .omh-log-actions button{padding:7px 10px!important;font-size:12px!important;}}
/* Search and clear filters share one responsive row. */
#omhApp .omh-global-search{max-width:820px!important;width:100%!important;}
#omhApp .omh-search-row{display:flex!important;align-items:stretch!important;gap:10px!important;width:100%!important;}
#omhApp .omh-search-row #omhSearch{flex:1 1 auto!important;width:auto!important;min-width:0!important;height:40px!important;}
#omhApp .omh-search-row #omhClear{flex:0 0 auto!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;white-space:nowrap!important;min-height:40px!important;padding:8px 16px!important;background:#d97706!important;color:#fff!important;border:1px solid #b45309!important;border-radius:6px!important;font-weight:600!important;cursor:pointer!important;}
#omhApp .omh-search-row #omhClear:hover{background:#b45309!important;}
@media(max-width:480px){#omhApp .omh-search-row{flex-wrap:wrap!important;}#omhApp .omh-search-row #omhSearch{flex-basis:100%!important;}#omhApp .omh-search-row #omhClear{width:100%!important;}}

/* v24: compact client details in modal and printable PDF */
#omhOverlay .omh-client-details{background:#f3f8ff!important;margin-bottom:12px!important;}
#omhOverlay .omh-client-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:7px 18px;font-size:13px;line-height:1.55;}
#omhOverlay .omh-client-field{overflow-wrap:anywhere;border-bottom:1px solid #e2eaf4;padding:4px 0;}
@media(max-width:600px){#omhOverlay .omh-client-grid{grid-template-columns:1fr;}}
</style>
<div class="omh" id="omhApp">
 <div class="panel"><h2>Order Modification History</h2><div class="muted d-none">Provisional &amp; Confirmed Sales Orders · Audit Trail · All recorded modifications · Latest first</div>
 
 <div class="summary d-none"><span>Orders: <b id="omhOrders">0</b></span><span>Audit entries: <b id="omhEvents">0</b></span><span class="muted">Showing up to 5,000 most recently modified orders</span></div></div>
 <div class="panel omh-report-panel">
 <div class="omh-report-tools"><div class="omh-countline d-none">Report: <strong>Order Modification History</strong> <span id="omhVisibleCount"></span></div><div class="omh-tool-buttons"><button id="omhCsv" type="button">Export to Excel (CSV)</button><button id="omhPdf" type="button">Export to PDF</button><button id="omhPrint" type="button" class="secondary">Print Report</button></div></div>
 <div class="omh-global-search"><label for="omhSearch">Search all orders</label><div class="omh-search-row"><input id="omhSearch" placeholder="Search client, phone, PSO, SO or user" autocomplete="off"><button id="omhClear" type="button" class="omh-clear-btn">Clear Grid Filters</button></div></div>
 <div class="tablewrap"><table id="omhTable"><colgroup><col style="width:48px"><col style="width:160px"><col style="width:135px"><col style="width:160px"><col style="width:150px"><col style="width:185px"><col style="width:155px"><col style="width:185px"><col style="width:185px"><col style="width:135px"><col style="width:160px"></colgroup><thead><tr><th>#</th><th>Client Name<button type="button" class="omh-filter-trigger" data-filter-col="1" aria-label="Filter Client Name" title="Filter Client Name">▾</button></th><th>Phone No.<button type="button" class="omh-filter-trigger" data-filter-col="2" aria-label="Filter Phone No." title="Filter Phone No.">▾</button></th><th>PSO Order No.<button type="button" class="omh-filter-trigger" data-filter-col="3" aria-label="Filter PSO Order No." title="Filter PSO Order No.">▾</button></th><th>SO Order No.<button type="button" class="omh-filter-trigger" data-filter-col="4" aria-label="Filter SO Order No." title="Filter SO Order No.">▾</button></th><th>Order Created Date<button type="button" class="omh-filter-trigger" data-filter-col="5" aria-label="Filter Order Created Date" title="Filter Order Created Date">▾</button></th><th>Last Updated By<button type="button" class="omh-filter-trigger" data-filter-col="6" aria-label="Filter Last Updated By" title="Filter Last Updated By">▾</button></th><th>Last Modified Date<button type="button" class="omh-filter-trigger" data-filter-col="7" aria-label="Filter Last Modified Date" title="Filter Last Modified Date">▾</button></th><th>Last Action<button type="button" class="omh-filter-trigger" data-filter-col="8" aria-label="Filter Last Action" title="Filter Last Action">▾</button></th><th>Modification Stage<button type="button" class="omh-filter-trigger" data-filter-col="9" aria-label="Filter Modification Stage" title="Filter Modification Stage">▾</button></th><th>History</th></tr></thead><tbody>
 <?php foreach($rows as $i=>$r):?><tr data-event-count="<?=intval($r['event_count'])?>"><td class="seq"><?=$i+1?></td><td><?=omhEsc($r['client_name'])?></td><td><?=omhEsc($r['phone'])?></td><td><?=omhEsc($r['psono'])?></td><td><?=omhEsc($r['sono'])?></td><td class="nowrap"><?=omhEsc(omhDisplayDate($r['order_created']))?></td><td><?=omhEsc($r['last_by'])?></td><td class="nowrap"><?=omhEsc(omhDisplayDate($r['last_at']))?></td><td><?=omhEsc($r['last_action'])?></td><td><?=omhEsc(omhModificationStage($r['last_action'],$r['last_order_type']))?></td><td><button class="action omhView" type="button" data-psono="<?=omhEsc($r['psono'])?>" data-sono="<?=omhEsc($r['sono'])?>" data-client-name="<?=omhEsc($r['client_name'])?>" data-client-mobile="<?=omhEsc($r['phone'])?>">View History</button></td></tr><?php endforeach;?></tbody></table><div class="empty" id="omhNoRows" hidden>No matching modification records.</div></div><div class="pager"><button class="secondary" type="button" id="omhPrev">Previous</button><span id="omhPage"></span><button class="secondary" type="button" id="omhNext">Next</button></div></div>
 <div class="overlay" id="omhOverlay" role="dialog" aria-modal="true" aria-label="Order change details"><div class="dialog"><div class="dialoghead"><strong id="omhDetailTitle">Modification History</strong><div class="omh-log-actions"><button type="button" id="omhDownloadLog">Download PDF</button><button type="button" id="omhPrintLog">Print / Save PDF</button><button type="button" class="secondary" id="omhClose">Close ✕</button></div></div><div class="dialogbody" id="omhDetailBody" tabindex="0"></div></div></div>
</div>
<script>
(function(){'use strict';const app=document.getElementById('omhApp');const table=app.querySelector('#omhTable'),body=table.tBodies[0],rows=[...body.rows],search=app.querySelector('#omhSearch');const columnFilters=new Map();let page=1,filtered=[],size=50;
const norm=v=>String(v??'').toLocaleLowerCase().trim();const cell=(r,n)=>r.cells[n].textContent.trim();

function render(){const q=norm(search.value);filtered=rows.filter(r=>(!q||[...r.cells].slice(1,10).some(c=>norm(c.textContent).includes(q)))&&[...columnFilters.entries()].every(([col,values])=>values.has(cell(r,col))));const totalPages=Math.max(1,Math.ceil(filtered.length/size));page=Math.min(page,totalPages);rows.forEach(r=>r.style.display='none');filtered.slice((page-1)*size,page*size).forEach((r,i)=>{r.style.display='';r.querySelector('.seq').textContent=(page-1)*size+i+1;});app.querySelector('#omhOrders').textContent=filtered.length;app.querySelector('#omhVisibleCount').textContent=filtered.length+' order(s)';app.querySelector('#omhEvents').textContent=filtered.reduce((n,r)=>n+Number(r.dataset.eventCount||0),0);app.querySelector('#omhPage').textContent='Page '+page+' of '+totalPages;app.querySelector('#omhPrev').disabled=page===1;app.querySelector('#omhNext').disabled=page>=totalPages;app.querySelector('#omhNoRows').hidden=filtered.length!==0;}
// Excel-style filters: use a detached popover so the dashboard cannot clip it.
const popup=document.createElement('div');popup.className='omh-excel-popup';popup.hidden=true;document.body.appendChild(popup);let currentTrigger=null;
function closeFilter(){popup.hidden=true;currentTrigger=null;}
function updateFilterButtons(){app.querySelectorAll('[data-filter-col]').forEach(b=>b.classList.toggle('omh-filter-active',columnFilters.has(Number(b.dataset.filterCol))));}
app.querySelectorAll('[data-filter-col]').forEach(btn=>btn.addEventListener('click',e=>{
 e.preventDefault();e.stopPropagation();if(currentTrigger===btn&&!popup.hidden){closeFilter();return;}
 const col=Number(btn.dataset.filterCol), values=[...new Set(rows.map(r=>cell(r,col)))].sort((a,b)=>a.localeCompare(b,undefined,{numeric:true}));
 const selected=columnFilters.get(col);const chosen=new Set(selected?selected:values);
 popup.replaceChildren();const heading=document.createElement('strong');heading.textContent='Filter: '+btn.closest('th').childNodes[0].textContent.trim();popup.append(heading);
 const input=document.createElement('input');input.type='search';input.placeholder='Search values';input.className='omh-excel-search';popup.append(input);
 const selectAll=document.createElement('label');selectAll.className='omh-excel-all';const allCheck=document.createElement('input');allCheck.type='checkbox';allCheck.checked=chosen.size===values.length;selectAll.append(allCheck,document.createTextNode(' Select all visible'));popup.append(selectAll);
 const list=document.createElement('div');list.className='omh-excel-values';popup.append(list);
 function draw(){list.replaceChildren();const q=norm(input.value);values.filter(v=>norm(v).includes(q)).forEach(v=>{const l=document.createElement('label'),cb=document.createElement('input');cb.type='checkbox';cb.checked=chosen.has(v);cb.addEventListener('change',()=>{cb.checked?chosen.add(v):chosen.delete(v);allCheck.checked=values.every(x=>chosen.has(x));});l.append(cb,document.createTextNode(' '+(v||'(Blank)')));list.append(l);});}
 input.addEventListener('input',draw);allCheck.addEventListener('change',()=>{values.filter(v=>norm(v).includes(norm(input.value))).forEach(v=>allCheck.checked?chosen.add(v):chosen.delete(v));draw();});draw();
 const actions=document.createElement('div');actions.className='omh-excel-actions';const apply=document.createElement('button');apply.textContent='Apply';apply.type='button';apply.onclick=()=>{if(chosen.size===values.length)columnFilters.delete(col);else columnFilters.set(col,new Set(chosen));page=1;render();updateFilterButtons();closeFilter();};const clear=document.createElement('button');clear.textContent='Clear';clear.type='button';clear.className='omh-excel-clear';clear.onclick=()=>{columnFilters.delete(col);page=1;render();updateFilterButtons();closeFilter();};actions.append(clear,apply);popup.append(actions);
 popup.hidden=false;currentTrigger=btn;const rect=btn.getBoundingClientRect();const left=Math.min(Math.max(8,rect.left-110),window.innerWidth-288);popup.style.left=left+'px';popup.style.top=Math.min(rect.bottom+5,window.innerHeight-340)+'px';input.focus();
}));
document.addEventListener('pointerdown',e=>{if(!popup.hidden&&!popup.contains(e.target)&&!e.target.closest('[data-filter-col]'))closeFilter();});document.addEventListener('keydown',e=>{if(e.key==='Escape')closeFilter();});window.addEventListener('scroll',closeFilter,true);
search.addEventListener('input',()=>{page=1;render();});app.querySelector('#omhClear').onclick=()=>{search.value='';columnFilters.clear();updateFilterButtons();page=1;render();};app.querySelector('#omhPrev').onclick=()=>{page--;render();};app.querySelector('#omhNext').onclick=()=>{page++;render();};app.querySelector('#omhPdf').onclick=()=>{app.querySelector('#omhPrint').click();};app.querySelector('#omhPrint').onclick=()=>{const old=page;size=Math.max(rows.length,1);page=1;render();window.print();size=50;page=old;render();};
app.querySelector('#omhCsv').onclick=()=>{const data=[['Client Name','Phone No.','PSO Order No.','SO Order No.','Order Created Date','Last Updated By','Last Modified Date','Last Action','Modification Stage'],...filtered.map(r=>[...Array(9)].map((_,i)=>cell(r,i+1)))];const csv='\ufeff'+data.map(a=>a.map(v=>'"'+String(v).replace(/"/g,'""')+'"').join(',')).join('\r\n');const blob=new Blob([csv],{type:'text/csv;charset=utf-8'});const u=URL.createObjectURL(blob);const a=document.createElement('a');a.href=u;a.download='Order_Modification_History_All.csv';a.click();setTimeout(()=>URL.revokeObjectURL(u),1000);};
const overlay=app.querySelector('#omhOverlay'); document.body.appendChild(overlay); const details=overlay.querySelector('#omhDetailBody');overlay.querySelector('.dialog').addEventListener('click',e=>e.stopPropagation());const el=(tag,txt,cls)=>{const e=document.createElement(tag);if(txt!==undefined)e.textContent=txt;if(cls)e.className=cls;return e;};function close(){overlay.style.display='none';}overlay.querySelector('#omhClose').addEventListener('click',e=>{e.preventDefault();e.stopPropagation();close();});overlay.addEventListener('click',e=>e.stopPropagation()); // Close only with Close button; never on backdrop
// Dashboard scripts may block native wheel scrolling. Route wheel movement to
// the independent history pane before it reaches dashboard-level handlers.
function omhWheelScroll(e){
  if(overlay.style.display==='none'||getComputedStyle(overlay).display==='none')return;
  if(!overlay.contains(e.target))return;
  if(e.target.closest('.dialoghead'))return;
  const delta=e.deltaMode===1?e.deltaY*20:e.deltaMode===2?e.deltaY*details.clientHeight:e.deltaY;
  if(delta===0)return;
  details.scrollTop=Math.max(0,Math.min(details.scrollHeight-details.clientHeight,details.scrollTop+delta));
  if(e.cancelable)e.preventDefault();
  e.stopImmediatePropagation();
}
window.addEventListener('wheel',omhWheelScroll,{capture:true,passive:false});
// Keyboard navigation works even when the dashboard disables page scrolling.
overlay.addEventListener('keydown',e=>{
  if(['INPUT','TEXTAREA','SELECT'].includes(e.target.tagName))return;
  const step=e.key==='ArrowDown'?55:e.key==='ArrowUp'?-55:e.key==='PageDown'?details.clientHeight*.85:e.key==='PageUp'?-details.clientHeight*.85:e.key==='Home'?-details.scrollHeight:e.key==='End'?details.scrollHeight:0;
  if(!step)return;
  details.scrollTop+=step;e.preventDefault();e.stopPropagation();
});
// Escape must not dismiss the history while interacting with its controls.
document.addEventListener('click',async e=>{const btn=e.target.closest('.omhView');if(!btn)return;e.preventDefault();e.stopPropagation();const psono=btn.dataset.psono;const clientRow={name:btn.dataset.clientName||'',mobile:btn.dataset.clientMobile||'',sono:btn.dataset.sono||''};overlay.style.display='flex';details.scrollTop=0;overlay.querySelector('#omhDetailTitle').textContent='Modification History · '+psono;details.replaceChildren(el('p','Loading history...'));
try{const url=new URL(location.href);url.searchParams.set('action','detail');url.searchParams.set('psono',psono);const res=await fetch(url.toString(),{credentials:'same-origin'});if(!res.ok)throw Error('HTTP '+res.status);const data=await res.json();if(data.error)throw Error(data.error);details.replaceChildren();if(!data.events.length){details.append(el('p','No changes found.'));return;}if(data.limited)details.append(el('p','Only the latest 500 log entries are displayed.'));
 const clean=v=>v===null||v===undefined||v===''?'—':String(v);
 const displayDate=v=>{const m=String(v||'').match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/);return m?`${m[3]}-${m[2]}-${m[1]} ${m[4]}:${m[5]}`:clean(v);};
 const product=o=>o?.product_name||o?.productname||o?.stock?.product_name||('Product '+(o?.product_id||o?.productid||''));
 const stock=o=>o?.stockorder_no||o?.stockorderno||o?.stock?.stockorder_no||'';
 const itemLabel=o=>[product(o),stock(o)].filter(Boolean).join(' / ');
 // Original order comes from the earliest complete BEFORE snapshot, never the current revised order.
 const originalEvent=data.events.find(ev=>ev.full&&Array.isArray(ev.before_data?.items)); // chronological API order: earliest complete original snapshot
 const original=el('section',undefined,'omh-original');
 original.append(el('h3','Original Order Details'));
 const origin=originalEvent?.before_data;
 if(origin){
   const h=origin.header||{};
   const info=el('div',undefined,'omh-original-list');
   const addOriginal=(label,value)=>{if(value!==null&&value!==undefined&&String(value)!==''){const d=el('div',label+': '+String(value),'omh-original-row');info.append(d);}};
   addOriginal('Order No.',originalEvent.sono||originalEvent.psono||psono);
   addOriginal('Client',h.clientname||h.client_name||'');
   addOriginal('Order Date',h.orderdate||h.order_date||h.created_at||'');
   original.append(info);
   const products=el('div',undefined,'omh-original-list');
   (origin.items||[]).forEach(item=>{
     const label=[item.product_name||item.productname||('Product '+(item.productid||item.product_id||'')),item.stockorder_no||item.stockorderno||''].filter(Boolean).join(' / ');
     const qty=item.quantity??item.qty??'—';
     const date=item.expecteddeliverydate||item.deliverydate||'';
     products.append(el('div',label+' — '+qty+' Qty'+(date?' — Delivery: '+date:''),'omh-original-row'));
   });
   original.append(el('h3','Original Products'),products);
 }else{
   original.append(el('p','Original order details are not available in the saved audit records. The current order is not substituted for the original.','omh-original-note'));
 }
  // Client details use the selected order row for current contact information.
  // Address and client ID are shown only if captured in the earliest complete snapshot.
  const clientDetails=el('section',undefined,'omh-original omh-client-details');
  clientDetails.append(el('h3','Client Details'));
  const clientInfo=el('div',undefined,'omh-client-grid');
  const clientField=(label,value)=>{const v=value===null||value===undefined?'':String(value).trim();if(v&&v!=='—'){const d=el('div',undefined,'omh-client-field');d.append(el('strong',label+': '),document.createTextNode(v));clientInfo.append(d);}};
  const originalHeader=origin?.header||{};
  const deliveryInfo=origin?.delivery||{};
  const addressValue=(v)=>typeof v==='string'?v:(Array.isArray(v)?'':(v&&typeof v==='object'?(v.deliveryaddress||v.address||v.full_address||''):''));
  clientField('Client Name',clientRow.name||originalHeader.clientname||originalHeader.client_name);
  clientField('Client No.',originalHeader.clientid||originalHeader.client_id||originalHeader.clientno||originalHeader.client_no);
  clientField('Mobile No.',clientRow.mobile||originalHeader.mobileno||originalHeader.mobile_no||originalHeader.phone);
  clientField('Address',originalHeader.delivery_address||originalHeader.deliveryaddress||originalHeader.address||addressValue(deliveryInfo));
  clientField('PSO No.',psono);
  clientField('SO No.',clientRow.sono||originalEvent?.sono);
  clientDetails.append(clientInfo);
  details.append(clientDetails,original);
 const entries=[];
 const technical=/(^|\.)(id|created_at|updated_at|updated_by|created_by|remark|remarks|updateamended|ammendedby|editing_allowed|editing_warning|created_by_name)$/i;
 const record=(ev,changes)=>{if(changes.length)entries.push({ev,changes});};
 data.events.forEach(ev=>{
   const before=ev.before_data||{},after=ev.after_data||{},changes=[];
   const add=(type,message)=>{if(message&&message.trim())changes.push({type,message});};
   if(ev.full&&ev.comparison){
     for(const c of ev.comparison.changes||[]){
       const n=c.name||'Product',st=c.new?.stock||c.old?.stock||'',tag=n+(st?' ('+st+')':'');
       if(c.kind==='added')add('added','Added '+tag+' — '+clean(c.new?.qty)+' Qty');
       else if(c.kind==='removed')add('deleted','Deleted '+tag+' — '+clean(c.old?.qty)+' Qty');
       else if(c.kind==='quantity')add('edited','Modified '+tag+' — Qty '+clean(c.old?.qty)+' → '+clean(c.new?.qty));
       else if(c.kind==='delivery')add('edited','Modified '+tag+' — Delivery Date '+clean(c.old?.date)+' → '+clean(c.new?.date));
       else if(c.kind==='product')add('edited','Modified Product '+clean(c.old?.name)+' → '+clean(c.new?.name));
     }
     for(const f of ev.fields||[]){
       if(technical.test(f.field))continue;
       if(!/^header\.(clientname|mobileno|address|delivery_address|districtname|statename|payment_terms|remarks|pickupfrom|status|expecteddelivery)$/i.test(f.field))continue;
       add('edited','Modified '+f.field.split('.').pop().replace(/_/g,' ')+' — '+clean(f.before)+' → '+clean(f.after));
     }
   }else if(/production cancelled/i.test(ev.action)){
     const o=before.stock||{};add('deleted','Production cancelled: '+itemLabel(o)+' — '+clean(o.quantity)+' Qty (customer order not deleted)');
   }else if(/sowing reduced|sowing quantity|remaining sowing/i.test(ev.action)){
     const o=before.stock||{};
     for(const c of after.changes||[]){
       if(String(c.before?.orderqty??'')!==String(c.new_orderqty??''))add('edited','Modified production '+itemLabel(o)+' — '+(c.roottype||'Production')+' Qty '+clean(c.before?.orderqty)+' → '+clean(c.new_orderqty));
       if(String(c.before?.sowingqty??'')!==String(c.new_sowingqty??''))add('edited','Modified sowing '+itemLabel(o)+' — '+(c.roottype||'Sowing')+' Qty '+clean(c.before?.sowingqty)+' → '+clean(c.new_sowingqty));
     }
   }else if(/item added|item removed|item deleted|item updated/i.test(ev.action)){
     const o=after.item||after.product||after,old=before.item||before.product||before;
     const name=itemLabel(o)||itemLabel(old),oldQty=old.quantity??old.qty,newQty=o.quantity??o.qty;
     if(/added/i.test(ev.action))add('added','Added '+name+' — '+clean(newQty)+' Qty');
     else if(/removed|deleted/i.test(ev.action))add('deleted','Deleted '+name+' — '+clean(oldQty)+' Qty');
     else if(String(oldQty)!==String(newQty))add('edited','Modified '+name+' — Qty '+clean(oldQty)+' → '+clean(newQty));
   }
   record(ev,changes);
 });
 if(!entries.length){details.append(el('p','No additions, deletions or modifications recorded.'));return;}
 entries.slice().reverse().forEach(({ev,changes})=>{
   const card=el('section',undefined,'omh-log-entry');
   const meta=el('div',undefined,'omh-log-meta');
   const line=(label,value)=>{const d=el('div');d.append(el('strong',label+': '),document.createTextNode(clean(value)));meta.append(d);};
   line('Date',displayDate(ev.date));line('Modification Stage',ev.stage);
   line('Original Order',ev.sono||ev.psono||psono);
   card.append(meta,el('div','Modification Order','omh-log-heading'));
   const log=el('div',undefined,'omh-log-lines');
   changes.forEach(c=>{const d=el('div',undefined,'omh-log-line '+c.type);d.append(el('span',c.type==='added'?'+':c.type==='deleted'?'−':'✎','omh-log-symbol'),document.createTextNode(c.message));log.append(d);});
   card.append(log,el('div','Modified By: '+clean(ev.by),'omh-log-user'));details.append(card);
 });
 }catch(err){details.replaceChildren(el('p','Unable to load history: '+err.message));}});
// Print the selected log in the current tab; browser Print dialog supports Save as PDF.
const logPrint=overlay.querySelector('#omhPrintLog'),logDownload=overlay.querySelector('#omhDownloadLog');
logPrint.addEventListener('click',e=>{e.preventDefault();e.stopPropagation();document.body.classList.add('omh-print-log');try{window.print();}finally{setTimeout(()=>document.body.classList.remove('omh-print-log'),500);}});
window.addEventListener('afterprint',()=>document.body.classList.remove('omh-print-log'));
logDownload.addEventListener('click',async e=>{
 e.preventDefault();e.stopPropagation();
 const btn=e.currentTarget, oldText=btn.textContent;
 const title=overlay.querySelector('#omhDetailTitle').textContent;
 const order=(title.split('·').pop()||'Order').trim();
 const params=new URLSearchParams();
 params.set('order_no',order);
 params.set('log_html',details.innerHTML);
 btn.disabled=true;btn.textContent='Generating PDF...';
 try {
   const url=new URL(location.href);url.searchParams.set('action','download_pdf');
   const response=await fetch(url.toString(),{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},body:params.toString()});
   if(!response.ok)throw new Error((await response.text()).slice(0,250)||'PDF generation failed');
   const blob=await response.blob();
   if(!blob.type.includes('pdf'))throw new Error('Server did not return a PDF.');
   const fileUrl=URL.createObjectURL(blob),link=document.createElement('a');
   link.href=fileUrl;link.download='Order_Modification_Log_'+order.replace(/[^a-zA-Z0-9_-]/g,'_')+'.pdf';
   document.body.appendChild(link);link.click();link.remove();setTimeout(()=>URL.revokeObjectURL(fileUrl),15000);
 }catch(err){alert('PDF download failed: '+err.message);}
 finally{btn.disabled=false;btn.textContent=oldText;}
});
render();})();
</script>
<?php include '../footer.php'; ?>
