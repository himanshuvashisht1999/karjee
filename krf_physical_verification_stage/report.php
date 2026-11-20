<?php
/** Stage 5: polyhouse-wise verification summary, XLSX, PDF and final confirmation. */
if (session_status() === PHP_SESSION_NONE) session_start();
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');
if (empty($_SESSION['physical_verify_csrf'])) $_SESSION['physical_verify_csrf'] = bin2hex(random_bytes(32));
function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function failure(int $code, string $message): void { http_response_code($code); header('Content-Type: text/plain; charset=utf-8'); exit($message); }
function b64decode(string $s) { return preg_match('/^[A-Za-z0-9_-]+={0,2}$/D',$s) ? base64_decode(strtr($s,'-_','+/'),true) : false; }
function queryRows(mysqli_stmt $stmt): array { $result=$stmt->get_result(); if (!$result) throw new RuntimeException('Database result unavailable'); return $result->fetch_all(MYSQLI_ASSOC); }
if (empty($_SESSION['sessionid']) || !hash_equals((string)$_SESSION['sessionid'],session_id())) failure(401,'Please log in through Karjee Farms.');
$key=(string)($_GET['k']??$_POST['k']??''); $decoded=b64decode($key); $parts=$decoded===false?[]:explode('|',$decoded);
if (count($parts)!==7 || b64decode($parts[6])===false || !hash_equals(implode('|',array_slice($parts,0,6)),(string)b64decode($parts[6]))) failure(403,'Invalid ERP access key.');
[$loginid,$loginroleid,$companyid,$subcompanyid,$projectid,$logintype]=array_slice($parts,0,6);
if ($loginid==='' || $projectid==='') failure(403,'Invalid user or project.');
require_once dirname(__DIR__).'/connection.php';
try {
 $stmt=$authconn->prepare('SELECT loginname FROM login_user_master WHERE loginid=? AND isActive=? LIMIT 1'); $yes='yes'; $stmt->bind_param('ss',$loginid,$yes); $stmt->execute(); $user=queryRows($stmt); $stmt->close(); if (!$user) failure(403,'Inactive ERP user.');
 $location=(string)($_GET['locationid']??$_POST['locationid']??'');
 $polyhouse=filter_var($_GET['polyhouseid']??$_POST['polyhouseid']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
 $date=(string)($_GET['date']??$_POST['date']??date('Y-m-d'));
 if (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date) || date('Y-m-d',strtotime($date))!==$date || $date>date('Y-m-d')) failure(422,'Invalid verification date.');
 if ($location==='' || !$polyhouse) failure(422,'Location and polyhouse are required.');
 $stmt=$hrmconn->prepare('SELECT LocationName FROM hrm_AttendanceLocationMaster WHERE ProjectID=? AND LocationId=? LIMIT 1'); $stmt->bind_param('ss',$projectid,$location); $stmt->execute(); $lr=queryRows($stmt); $stmt->close(); if (!$lr) failure(403,'Location not authorized for this project.');
 $stmt=$krfconn->prepare('SELECT polyhousename,beddetails FROM krf_polyhouse_master WHERE projectid=? AND locationid=? AND polyhouseid=? AND status=? LIMIT 1'); $active='Active'; $stmt->bind_param('ssis',$projectid,$location,$polyhouse,$active); $stmt->execute(); $pr=queryRows($stmt); $stmt->close(); if (!$pr) failure(403,'Polyhouse not authorized for this location.');
 $configured=json_decode((string)$pr[0]['beddetails'],true); if (!is_array($configured)) failure(422,'Invalid beddetails JSON.');
 $bednames=[]; foreach($configured as $bed) { $name=is_array($bed)?trim((string)($bed['bedname']??'')):''; if($name!=='' && !in_array($name,$bednames,true)) $bednames[]=$name; }
 if (!$bednames) failure(422,'No beds configured in this polyhouse.');
 $stmt=$krfconn->prepare('SELECT bed_number,status,supervisor_user_id,updated_at FROM Physical_Verification_Bed_Status WHERE project_id=? AND location_id=? AND polyhouse_id=? AND verification_date=?'); $stmt->bind_param('ssis',$projectid,$location,$polyhouse,$date); $stmt->execute(); $statuses=queryRows($stmt); $stmt->close();
 $statusMap=[]; foreach($statuses as $r) $statusMap[$r['bed_number']]=$r;
 $stmt=$krfconn->prepare('SELECT bed_number,COUNT(*) AS product_entries,COALESCE(SUM(no_of_trays),0) AS trays,COALESCE(SUM(total_quantity_plants),0) AS plants,COUNT(DISTINCT product_id) AS distinct_products,MAX(verified_datetime) AS last_count FROM Physically_Verified_Stock WHERE project_id=? AND location_id=? AND polyhouse_id=? AND verified_datetime>=? AND verified_datetime<DATE_ADD(?,INTERVAL 1 DAY) GROUP BY bed_number'); $stmt->bind_param('ssiss',$projectid,$location,$polyhouse,$date,$date); $stmt->execute(); $counts=queryRows($stmt); $stmt->close();
 $countMap=[]; foreach($counts as $r) $countMap[$r['bed_number']]=$r;
 $stmt=$krfconn->prepare('SELECT confirmed_by,confirmed_at FROM Physical_Verification_Polyhouse_Confirmation WHERE project_id=? AND location_id=? AND polyhouse_id=? AND verification_date=? LIMIT 1'); $stmt->bind_param('ssis',$projectid,$location,$polyhouse,$date); $stmt->execute(); $conf=queryRows($stmt); $stmt->close();
 $data=[]; $totals=['Complete'=>0,'Empty'=>0,'In Progress'=>0,'Not Checked'=>0,'trays'=>0,'plants'=>0,'products'=>0];
 foreach($bednames as $bed) {
  $st=$statusMap[$bed]['status']??(isset($countMap[$bed])?'In Progress':'Not Checked');
  $r=['bed'=>$bed,'status'=>$st,'products'=>(int)($countMap[$bed]['distinct_products']??0),'entries'=>(int)($countMap[$bed]['product_entries']??0),'trays'=>(int)($countMap[$bed]['trays']??0),'plants'=>(int)($countMap[$bed]['plants']??0),'supervisor'=>(string)($statusMap[$bed]['supervisor_user_id']??''),'updated'=>(string)($statusMap[$bed]['updated_at']??$countMap[$bed]['last_count']??'')];
  $data[]=$r; $totals[$st]++; $totals['trays']+=$r['trays']; $totals['plants']+=$r['plants']; $totals['products']+=$r['products'];
 }
 $allDone=($totals['In Progress']===0 && $totals['Not Checked']===0 && ($totals['Complete']+$totals['Empty'])===count($bednames));
 $title='Karjee Farms - Physical Stock Verification'; $phName=(string)$pr[0]['polyhousename']; $locationName=(string)$lr[0]['LocationName'];
 $action=(string)($_GET['export']??'');
 if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!hash_equals((string)$_SESSION['physical_verify_csrf'],(string)($_POST['csrf']??''))) failure(403,'Invalid form token.');
  if (!$allDone || $conf) failure(409,'Cannot finalize: some beds are incomplete or verification is already finalized.');
  // Lock scope via confirmation record and re-check statuses within transaction.
  $krfconn->begin_transaction();
  try {
   $stmt=$krfconn->prepare('INSERT INTO Physical_Verification_Polyhouse_Confirmation (project_id,location_id,polyhouse_id,verification_date,confirmed_by) VALUES (?,?,?,?,?)'); $stmt->bind_param('ssiss',$projectid,$location,$polyhouse,$date,$loginid); $stmt->execute(); $stmt->close();
   $stmt=$krfconn->prepare('SELECT bed_number,status FROM Physical_Verification_Bed_Status WHERE project_id=? AND location_id=? AND polyhouse_id=? AND verification_date=? FOR UPDATE'); $stmt->bind_param('ssis',$projectid,$location,$polyhouse,$date); $stmt->execute(); $locked=queryRows($stmt); $stmt->close();
   $lockedMap=[]; foreach($locked as $r) $lockedMap[$r['bed_number']]=$r['status'];
   foreach($bednames as $name) if(!in_array($lockedMap[$name]??'',['Complete','Empty'],true)) throw new DomainException('Unverified beds remain.');
   $krfconn->commit();
  } catch(Throwable $ex) { $krfconn->rollback(); throw $ex; }
  header('Location: report.php?'.http_build_query(['k'=>$key,'locationid'=>$location,'polyhouseid'=>$polyhouse,'date'=>$date,'finalized'=>1])); exit;
 }
 if ($action==='xlsx') {
  if (!class_exists('ZipArchive')) failure(500,'PHP ZipArchive extension is required to generate XLSX.');
  $xmlEsc=static fn($v)=>htmlspecialchars((string)$v,ENT_XML1|ENT_QUOTES,'UTF-8');
  $lines=[['Bed','Status','Distinct Products','Product Entries','Trays','Plants','Supervisor ID','Last Updated']];
  foreach($data as $r) $lines[]=[$r['bed'],$r['status'],$r['products'],$r['entries'],$r['trays'],$r['plants'],$r['supervisor'],$r['updated']];
  $lines[]=['TOTAL','', $totals['products'],'',$totals['trays'],$totals['plants'],'',''];
  $sheet='<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"/></sheetViews><cols><col min="1" max="2" width="22" customWidth="1"/><col min="3" max="6" width="19" customWidth="1"/><col min="7" max="8" width="24" customWidth="1"/></cols><sheetData>';
  $metadata=[['Physical Stock Verification - Polyhouse Report'],['Location',$locationName],['Polyhouse',$phName],['Date',$date],['Total Beds',count($bednames)],['Complete',$totals['Complete']],['Empty',$totals['Empty']],['In Progress',$totals['In Progress']],['Not Checked',$totals['Not Checked']],['Finalized',$conf?'Yes':'No'],[]];
  $allRows=array_merge($metadata,$lines);
  foreach($allRows as $i=>$line) { $sheet.='<row r="'.($i+1).'">'; foreach($line as $j=>$value) { $col=''; $num=$j+1; while($num){$num--; $col=chr(65+$num%26).$col; $num=intdiv($num,26);} $ref=$col.($i+1); if(is_int($value)) $sheet.='<c r="'.$ref.'"><v>'.$value.'</v></c>'; else $sheet.='<c r="'.$ref.'" t="inlineStr"><is><t>'.$xmlEsc($value).'</t></is></c>'; } $sheet.='</row>'; }
  $sheet.='</sheetData></worksheet>';
  $temp=tempnam(sys_get_temp_dir(),'krf_xlsx_'); $zip=new ZipArchive(); if($zip->open($temp,ZipArchive::OVERWRITE)!==true) failure(500,'Unable to create Excel file.');
  $zip->addFromString('[Content_Types].xml','<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
  $zip->addFromString('_rels/.rels','<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
  $zip->addFromString('xl/workbook.xml','<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Bed Verification" sheetId="1" r:id="rId1"/></sheets></workbook>');
  $zip->addFromString('xl/_rels/workbook.xml.rels','<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
  $zip->addFromString('xl/worksheets/sheet1.xml',$sheet); $zip->close();
  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); header('Content-Disposition: attachment; filename="polyhouse_'.$polyhouse.'_'.$date.'.xlsx"'); header('Content-Length: '.filesize($temp)); readfile($temp); unlink($temp); exit;
 }
 if ($action==='pdf') {
  // Self-contained landscape A4 PDF generator; ASCII transliteration for built-in Helvetica.
  $safe=static function($value): string { $v=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',(string)$value); return preg_replace('/[^\x20-\x7e]/','?', $v===false?'':$v); };
  $esc=static fn($v)=>str_replace(['\\','(',')'],['\\\\','\\(','\\)'],$v);
  $pages=[]; $chunks=array_chunk($data,30); $totalPages=count($chunks);
  foreach($chunks as $p=>$chunk) {
   $content="BT /F1 15 Tf 36 550 Td (".$esc($safe($title)).") Tj ET\n";
   $content.="BT /F1 10 Tf 36 530 Td (".$esc($safe("Location: $locationName   Polyhouse: $phName   Date: $date")).") Tj ET\n";
   $content.="BT /F1 9 Tf 36 513 Td (".$esc($safe('Total: '.count($data).'   Complete: '.$totals['Complete'].'   Empty: '.$totals['Empty'].'   In Progress: '.$totals['In Progress'].'   Not Checked: '.$totals['Not Checked'].'   Finalized: '.($conf?'Yes':'No'))).") Tj ET\n";
   $content.="BT /F1 9 Tf 36 490 Td (Bed                     Status                    Products     Entries       Trays         Plants        Supervisor) Tj ET\n";
   $y=474;
   foreach($chunk as $r) { $line=sprintf('%-23.23s %-23.23s %8d %12d %12d %13d   %-20.20s',$safe($r['bed']),$safe($r['status']),$r['products'],$r['entries'],$r['trays'],$r['plants'],$safe($r['supervisor'])); $content.='BT /F2 8 Tf 36 '.$y.' Td ('.$esc($line).") Tj ET\n"; $y-=14; }
   $content.='BT /F1 9 Tf 36 24 Td (Page '.($p+1).' of '.$totalPages.') Tj ET'; $pages[]=$content;
  }
  $objects=[]; $objects[1]='<< /Type /Catalog /Pages 2 0 R >>'; $kids=[];
  foreach($pages as $i=>$content) { $pageId=5+$i*2; $streamId=$pageId+1; $kids[]=$pageId.' 0 R'; $objects[$pageId]='<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents '.$streamId.' 0 R >>'; $objects[$streamId]='<< /Length '.strlen($content).' >>' . "\nstream\n".$content."\nendstream"; }
  $objects[2]='<< /Type /Pages /Kids ['.implode(' ',$kids).'] /Count '.count($pages).' >>'; $objects[3]='<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>'; $objects[4]='<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>'; ksort($objects);
  $pdf="%PDF-1.4\n"; $offset=[0=>0]; foreach($objects as $id=>$body) { $offset[$id]=strlen($pdf); $pdf.="$id 0 obj\n$body\nendobj\n"; } $xref=strlen($pdf); $max=max(array_keys($objects)); $pdf.="xref\n0 ".($max+1)."\n0000000000 65535 f \n"; for($i=1;$i<=$max;$i++) $pdf.=sprintf('%010d 00000 n ', $offset[$i]??0)."\n"; $pdf.="trailer\n<< /Size ".($max+1)." /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
  header('Content-Type: application/pdf'); header('Content-Disposition: attachment; filename="polyhouse_'.$polyhouse.'_'.$date.'.pdf"'); header('Content-Length: '.strlen($pdf)); echo $pdf; exit;
 }
} catch(Throwable $ex) { error_log('Physical Verification Stage 5: '.$ex->getMessage()); failure(500,'Unable to generate the report or finalize verification. Check server error log and Stage 5 table installation.'); }
$base=['k'=>$key,'locationid'=>$location,'polyhouseid'=>$polyhouse,'date'=>$date];
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Polyhouse Verification Summary</title><style>
:root{font-family:system-ui,Arial,sans-serif;color:#173c2b;background:#f2f7f4}body{margin:0}.wrap{max-width:1150px;margin:auto;padding:20px}.card{background:white;padding:20px;border:1px solid #d6e7da;border-radius:14px;margin:15px 0}h1{margin:0 0 10px}a.btn,button{display:inline-block;background:#227448;color:white;border:0;border-radius:8px;padding:12px 16px;text-decoration:none;font-weight:700;cursor:pointer;margin:5px 5px 5px 0}button:disabled{background:#9eafa4}.grid{display:flex;gap:12px;flex-wrap:wrap}.stat{flex:1;min-width:130px;background:#eaf3ec;padding:14px;border-radius:10px}.stat b{display:block;font-size:25px}table{border-collapse:collapse;width:100%;min-width:730px}th,td{border-bottom:1px solid #e2eae4;padding:10px;text-align:left}th{background:#e8f3eb}td.num{text-align:right}.scroll{overflow-x:auto}.alert{color:#9e2c23;font-weight:700}.ok{color:#176d3e;font-weight:700}input{padding:10px;font:inherit}small{color:#586e60}</style></head><body><main class="wrap"><h1>Physical Stock Verification — Polyhouse Report</h1><div class="card"><b>Location:</b> <?=h($locationName)?> &nbsp; <b>Polyhouse:</b> <?=h($phName)?> &nbsp; <b>Date:</b> <?=h($date)?><p><b>Supervisor viewing:</b> <?=h($user[0]['loginname'])?> &nbsp; <b>Finalized:</b> <?= $conf ? h($conf[0]['confirmed_at'].' by '.$conf[0]['confirmed_by']) : 'No' ?></p><form method="get"><input type="hidden" name="k" value="<?=h($key)?>"><input type="hidden" name="locationid" value="<?=h($location)?>"><input type="hidden" name="polyhouseid" value="<?=h($polyhouse)?>"><label>Verification date <input type="date" name="date" max="<?=h(date('Y-m-d'))?>" value="<?=h($date)?>"></label><button type="submit">View Date</button></form><a class="btn" href="?<?=h(http_build_query($base+['export'=>'xlsx']))?>">Download Excel (.xlsx)</a><a class="btn" href="?<?=h(http_build_query($base+['export'=>'pdf']))?>">Download PDF</a><a class="btn" href="index.php?k=<?=rawurlencode($key)?>">Back to Verification</a></div>
<div class="grid"><?php foreach(['Beds'=>count($data),'Complete'=>$totals['Complete'],'Empty'=>$totals['Empty'],'In Progress'=>$totals['In Progress'],'Not Checked'=>$totals['Not Checked'],'Total Trays'=>$totals['trays'],'Total Plants'=>$totals['plants']] as $label=>$val): ?><div class="stat"><small><?=h($label)?></small><b><?=number_format($val)?></b></div><?php endforeach; ?></div>
<div class="card"><h2>Final Polyhouse Confirmation</h2><?php if($conf): ?><p class="ok">Finalized on <?=h($conf[0]['confirmed_at'])?> by <?=h($conf[0]['confirmed_by'])?>.</p><?php elseif(!$allDone): ?><p class="alert">Final confirmation blocked. Complete or mark Empty all <?=count($data)?> configured beds. Remaining: <?=$totals['Not Checked']?> Not Checked, <?=$totals['In Progress']?> In Progress.</p><?php else: ?><p class="ok">All beds verified. You may finalize this polyhouse for <?=h($date)?>.</p><form method="post" onsubmit="return confirm('Finalize verification for this polyhouse and date?');"><input type="hidden" name="k" value="<?=h($key)?>"><input type="hidden" name="locationid" value="<?=h($location)?>"><input type="hidden" name="polyhouseid" value="<?=h($polyhouse)?>"><input type="hidden" name="date" value="<?=h($date)?>"><input type="hidden" name="csrf" value="<?=h($_SESSION['physical_verify_csrf'])?>"><button type="submit">Confirm Final Verification</button></form><?php endif; ?></div>
<div class="card"><h2>Bed-wise Status</h2><div class="scroll"><table><thead><tr><th>Bed</th><th>Status</th><th>Products</th><th>Entries</th><th>Trays</th><th>Plants</th><th>Supervisor ID</th><th>Last Updated</th></tr></thead><tbody><?php foreach($data as $r): ?><tr><td><?=h($r['bed'])?></td><td><?=h($r['status'])?></td><td class="num"><?=$r['products']?></td><td class="num"><?=$r['entries']?></td><td class="num"><?=number_format($r['trays'])?></td><td class="num"><?=number_format($r['plants'])?></td><td><?=h($r['supervisor'])?></td><td><?=h($r['updated'])?></td></tr><?php endforeach; ?></tbody></table></div></div></main></body></html>
