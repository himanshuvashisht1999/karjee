<?php
/** Read-only, date-wise physical stock verification detail report. */
if (session_status() === PHP_SESSION_NONE) session_start();
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');
function drEscape($s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function drFail(int $status, string $message): void { http_response_code($status); header('Content-Type: text/plain; charset=utf-8'); exit($message); }
function drDecode(string $s) { return preg_match('/^[A-Za-z0-9_-]+={0,2}$/D',$s) ? base64_decode(strtr($s,'-_','+/'),true) : false; }
function drRows(mysqli_stmt $stmt): array { $r=$stmt->get_result(); if (!$r) throw new RuntimeException('Unable to read query results.'); return $r->fetch_all(MYSQLI_ASSOC); }
if (empty($_SESSION['sessionid']) || !hash_equals((string)$_SESSION['sessionid'],session_id())) drFail(401,'Please log in through the Karjee Farms ERP.');
$key=(string)($_GET['k']??''); $decoded=drDecode($key); $parts=$decoded===false?[]:explode('|',$decoded);
if(count($parts)!==7 || drDecode($parts[6])===false || !hash_equals(implode('|',array_slice($parts,0,6)),(string)drDecode($parts[6]))) drFail(403,'Invalid ERP access key.');
[$loginid,$loginroleid,$companyid,$subcompanyid,$projectid,$logintype]=array_slice($parts,0,6);
if ($loginid==='' || $projectid==='') drFail(403,'Invalid user/project context.');
// Parse dates in the farm's local timezone, independently of the web server timezone.
$indiaTz = new DateTimeZone('Asia/Kolkata');
$todayIndia = new DateTimeImmutable('today', $indiaTz);
$rawDate = trim((string)($_GET['date'] ?? ''));
if ($rawDate === '') {
    $dt = $todayIndia;
} else {
    // Accept both report-facing DD-MM-YYYY and legacy ISO YYYY-MM-DD links.
    $dateFormat = preg_match('/^\d{2}-\d{2}-\d{4}$/D', $rawDate) ? '!d-m-Y' :
                  (preg_match('/^\d{4}-\d{2}-\d{2}$/D', $rawDate) ? '!Y-m-d' : null);
    $dt = $dateFormat ? DateTimeImmutable::createFromFormat($dateFormat, $rawDate, $indiaTz) : false;
    if (!$dt || $dt->format(substr($dateFormat, 1)) !== $rawDate || $dt > $todayIndia) {
        drFail(422, 'Enter a valid verification date in DD-MM-YYYY format, not later than today (India time).');
    }
}
$displayDate = $dt->format('d-m-Y');
$date = $dt->format('Y-m-d');
$export=(string)($_GET['export']??'');
if(!in_array($export,['','xlsx','pdf'],true)) drFail(400,'Invalid report format.');
require_once dirname(__DIR__).'/connection.php';
try {
 $s=$authconn->prepare('SELECT loginname FROM login_user_master WHERE loginid=? AND isActive=? LIMIT 1'); $yes='yes'; $s->bind_param('ss',$loginid,$yes); $s->execute(); $users=drRows($s); $s->close(); if(!$users) drFail(403,'Inactive ERP user.');
 // Cross-database joins are deliberately avoided: locations are on $hrmconn, stock on $krfconn.
 $s=$hrmconn->prepare('SELECT LocationId, LocationName FROM hrm_AttendanceLocationMaster WHERE ProjectID=?'); $s->bind_param('s',$projectid); $s->execute(); $locRows=drRows($s); $s->close();
 $locations=[]; foreach($locRows as $r) $locations[(string)$r['LocationId']]=(string)$r['LocationName'];
 $s=$krfconn->prepare('SELECT polyhouseid,polyhousename,locationid FROM krf_polyhouse_master WHERE projectid=?'); $s->bind_param('s',$projectid); $s->execute(); $phRows=drRows($s); $s->close();
 $polyhouses=[]; foreach($phRows as $r) if(isset($locations[(string)$r['locationid']])) $polyhouses[(int)$r['polyhouseid']]=['name'=>(string)$r['polyhousename'],'location'=>(string)$r['locationid']];
 $s=$krfconn->prepare('SELECT verified_datetime,location_id,polyhouse_id,bed_number,product_name,tray_name,cavity_count,no_of_trays,total_quantity_plants FROM Physically_Verified_Stock WHERE project_id=? AND verified_datetime>=? AND verified_datetime<DATE_ADD(?,INTERVAL 1 DAY) ORDER BY location_id,polyhouse_id,bed_number,product_name,verified_datetime');
 $s->bind_param('sss',$projectid,$date,$date); $s->execute(); $stock=drRows($s); $s->close();
 $records=[]; $traysTotal=0; $plantsTotal=0;
 foreach($stock as $r) {
   $phId=(int)$r['polyhouse_id']; $locId=(string)$r['location_id'];
   if(!isset($locations[$locId],$polyhouses[$phId]) || $polyhouses[$phId]['location']!==$locId) continue;
   $trays=(int)$r['no_of_trays']; $plants=(int)$r['total_quantity_plants'];
   $records[]=[ $displayDate,$locations[$locId],$polyhouses[$phId]['name'],(string)$r['bed_number'],(string)$r['product_name'],(string)($r['tray_name']??''),$trays,$plants ];
   $traysTotal+=$trays; $plantsTotal+=$plants;
 }
 $headers=['Date','Location Name','Polyhouse Name','Bed No.','Plant Name','Cavity Name','No. of Trays','Total Quantity'];
 if($export==='xlsx') {
  if(!class_exists('ZipArchive')) drFail(500,'PHP ZipArchive is required for Excel export.');
  $all=array_merge([['Physical Stock Verification — Date-wise Detail'],['Verification Date',$displayDate],['Total Entries',count($records)],[],$headers],$records,[['TOTAL','','','','','',$traysTotal,$plantsTotal]]);
  $xmlEsc=static fn($v)=>htmlspecialchars((string)$v,ENT_XML1|ENT_QUOTES,'UTF-8');
  $sheet='<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><cols><col min="1" max="1" width="16" customWidth="1"/><col min="2" max="4" width="24" customWidth="1"/><col min="5" max="5" width="42" customWidth="1"/><col min="6" max="6" width="24" customWidth="1"/><col min="7" max="8" width="20" customWidth="1"/></cols><sheetData>';
  foreach($all as $i=>$line) { $sheet.='<row r="'.($i+1).'">'; foreach($line as $j=>$value) { $ref=chr(65+$j).($i+1); if(is_int($value)) $sheet.='<c r="'.$ref.'"><v>'.$value.'</v></c>'; else $sheet.='<c r="'.$ref.'" t="inlineStr"><is><t>'.$xmlEsc($value).'</t></is></c>'; } $sheet.='</row>'; }
  $sheet.='</sheetData></worksheet>';
  $temp=tempnam(sys_get_temp_dir(),'krf_date_xlsx_'); $zip=new ZipArchive(); if($zip->open($temp,ZipArchive::OVERWRITE)!==true) drFail(500,'Unable to create Excel file.');
  $zip->addFromString('[Content_Types].xml','<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
  $zip->addFromString('_rels/.rels','<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
  $zip->addFromString('xl/workbook.xml','<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Date Wise Verification" sheetId="1" r:id="rId1"/></sheets></workbook>');
  $zip->addFromString('xl/_rels/workbook.xml.rels','<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
  $zip->addFromString('xl/worksheets/sheet1.xml',$sheet); $zip->close();
  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); header('Content-Disposition: attachment; filename="physical_verification_'.$displayDate.'.xlsx"'); header('Content-Length: '.filesize($temp)); readfile($temp); unlink($temp); exit;
 }
 if($export==='pdf') {
  // PDF uses landscape A3 for eight detail columns; built-in fonts transliterate non-ASCII text.
  $safe=static function($v): string { $s=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',(string)$v); return preg_replace('/[^\x20-\x7e]/','?', $s===false?'':$s); };
  $esc=static fn($s)=>str_replace(['\\','(',')'],['\\\\','\\(','\\)'],$s);
  $pages=[]; $chunks=array_chunk($records,34); if(!$chunks) $chunks=[[]];
  foreach($chunks as $pi=>$chunk) {
   $content='BT /F1 17 Tf 35 800 Td ('.$esc($safe('Karjee Farms - Date-wise Physical Verification')).") Tj ET\n";
   $content.='BT /F1 11 Tf 35 775 Td ('.$esc($safe('Date: '.$displayDate.'    Entries: '.count($records).'    Total Trays: '.$traysTotal.'    Total Quantity: '.$plantsTotal)).") Tj ET\n";
   $heads=sprintf('%-12s %-18s %-18s %-11s %-38s %-20s %10s %13s','Date','Location','Polyhouse','Bed','Plant Name','Cavity','Trays','Quantity');
   $content.='BT /F2 9 Tf 35 748 Td ('.$esc($heads).") Tj ET\n";
   $y=729;
   foreach($chunk as $r) { $line=sprintf('%-12.12s %-18.18s %-18.18s %-11.11s %-38.38s %-20.20s %10d %13d',$safe($r[0]),$safe($r[1]),$safe($r[2]),$safe($r[3]),$safe($r[4]),$safe($r[5]),$r[6],$r[7]); $content.='BT /F2 9 Tf 35 '.$y.' Td ('.$esc($line).") Tj ET\n"; $y-=19; }
   $content.='BT /F1 9 Tf 35 25 Td (Page '.($pi+1).' of '.count($chunks).') Tj ET'; $pages[]=$content;
  }
  $objects=[1=>'<< /Type /Catalog /Pages 2 0 R >>',3=>'<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',4=>'<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>']; $kids=[];
  foreach($pages as $i=>$content){$id=5+2*$i;$sid=$id+1;$kids[]="$id 0 R";$objects[$id]='<< /Type /Page /Parent 2 0 R /MediaBox [0 0 1191 842] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents '.$sid.' 0 R >>';$objects[$sid]='<< /Length '.strlen($content)." >>\nstream\n".$content."\nendstream";}
  $objects[2]='<< /Type /Pages /Kids ['.implode(' ',$kids).'] /Count '.count($pages).' >>'; ksort($objects);
  $pdf="%PDF-1.4\n"; $offset=[]; foreach($objects as $id=>$body){$offset[$id]=strlen($pdf);$pdf.="$id 0 obj\n$body\nendobj\n";} $xref=strlen($pdf);$max=max(array_keys($objects));$pdf.="xref\n0 ".($max+1)."\n0000000000 65535 f \n";for($i=1;$i<=$max;$i++)$pdf.=sprintf('%010d 00000 n ',$offset[$i]??0)."\n";$pdf.="trailer\n<< /Size ".($max+1)." /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
  header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="physical_verification_'.$displayDate.'.pdf"');header('Content-Length: '.strlen($pdf));echo $pdf;exit;
 }
} catch(Throwable $ex) { error_log('Physical verification date report: '.$ex->getMessage()); drFail(500,'Unable to load date-wise verification report. Check the PHP error log and cavity upgrade SQL.'); }
$base=['k'=>$key,'date'=>$displayDate];
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Date-wise Physical Verification Report</title><style>
:root{font-family:system-ui,Arial,sans-serif;color:#18392b;background:#f2f7f4}*{box-sizing:border-box}body{margin:0}.wrap{max-width:1300px;margin:auto;padding:22px}.card{background:#fff;border:1px solid #d8e5db;border-radius:14px;padding:18px;margin:15px 0}h1{font-size:25px}form{display:flex;align-items:end;gap:12px;flex-wrap:wrap}label{font-weight:650}.date-picker-wrap{display:flex;align-items:stretch;position:relative;margin-top:7px;width:235px}.date-picker-wrap .date-display{margin:0;min-width:0;width:185px;border-radius:8px 0 0 8px;background:#fff;cursor:pointer}.calendar-button{position:relative;display:flex;align-items:center;justify-content:center;width:50px;border:1px solid #b5c7bc;border-left:0;border-radius:0 8px 8px 0;background:#e8f4eb;color:#18392b;cursor:pointer}.calendar-button input[type=date]{position:absolute;inset:0;width:100%;height:100%;opacity:0;cursor:pointer;margin:0;padding:0;border:0}.calendar-button input[type=date]::-webkit-calendar-picker-indicator{position:absolute;inset:0;width:100%;height:100%;cursor:pointer}.calendar-button svg{pointer-events:none}input{display:block;padding:11px;border:1px solid #b5c7bc;border-radius:8px;font:inherit;margin-top:7px}button,.btn{background:#23754a;color:#fff;padding:12px 16px;border:0;border-radius:8px;font:inherit;font-weight:650;cursor:pointer;text-decoration:none;display:inline-block;margin:5px 7px 5px 0}.summary{display:flex;gap:14px;flex-wrap:wrap}.summary div{background:#e8f4eb;border-radius:9px;padding:14px;min-width:160px}.summary strong{display:block;font-size:22px}.scroll{overflow:auto}table{border-collapse:collapse;width:100%;min-width:1040px}th,td{padding:11px;border-bottom:1px solid #e4ebe5;text-align:left}th{background:#e8f4eb;position:sticky;top:0}td.num{text-align:right;font-variant-numeric:tabular-nums}tfoot{font-weight:700;background:#f0f6f1}.muted{color:#63776b}@media print{.controls{display:none}body{background:#fff}.card{border:0;padding:0}.wrap{max-width:none}th{position:static}}
</style></head><body><main class="wrap"><h1>Date-wise Physical Stock Verification</h1><div class="card controls"><form method="get"><input type="hidden" name="k" value="<?=drEscape($key)?>"><label for="verification_date">Verification Date
<span class="date-picker-wrap">
<input type="text" class="date-display" name="date" id="verification_date" value="<?=drEscape($displayDate)?>" readonly required aria-label="Verification date in DD-MM-YYYY format" title="Choose a date from the calendar">
<span class="calendar-button" title="Open calendar"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4M17 3v4M3 10h18"/></svg><input type="date" id="calendar_native" value="<?=drEscape($date)?>" max="<?=drEscape($todayIndia->format('Y-m-d'))?>" aria-label="Open verification date calendar" tabindex="0"></span>
</span></label><button type="submit">View Report</button></form><p><a class="btn" href="?<?=drEscape(http_build_query($base+['export'=>'xlsx']))?>">Download Excel</a><a class="btn" href="?<?=drEscape(http_build_query($base+['export'=>'pdf']))?>">Download PDF</a><a class="btn" href="index.php?k=<?=rawurlencode($key)?>">Back to Verification</a></p></div><div class="summary"><div>Date<strong><?=drEscape($displayDate)?></strong></div><div>Entries<strong><?=number_format(count($records))?></strong></div><div>Total Trays<strong><?=number_format($traysTotal)?></strong></div><div>Total Quantity<strong><?=number_format($plantsTotal)?></strong></div></div><div class="card scroll"><table><thead><tr><?php foreach($headers as $head): ?><th><?=drEscape($head)?></th><?php endforeach; ?></tr></thead><tbody><?php foreach($records as $r): ?><tr><?php foreach($r as $i=>$v): ?><td class="<?=$i>=6?'num':''?>"><?=drEscape($i>=6?number_format($v):$v)?></td><?php endforeach; ?></tr><?php endforeach; ?><?php if(!$records): ?><tr><td colspan="8" class="muted">No physical verification entries found for the selected date.</td></tr><?php endif; ?></tbody><tfoot><tr><td colspan="6">TOTAL</td><td class="num"><?=number_format($traysTotal)?></td><td class="num"><?=number_format($plantsTotal)?></td></tr></tfoot></table></div><p class="muted">Each row is one saved physical-count entry. Total Quantity uses the stored plant count. Historical entries without cavity information display a blank cavity name.</p></main><script>
(function () {
  'use strict';
  const picker = document.getElementById('calendar_native');
  const display = document.getElementById('verification_date');
  function syncDate() {
    const iso = picker.value;
    if (!/^\d{4}-\d{2}-\d{2}$/.test(iso)) return;
    const [year, month, day] = iso.split('-');
    display.value = `${day}-${month}-${year}`;
  }
  picker.addEventListener('change', syncDate);
  picker.addEventListener('input', syncDate);
  display.addEventListener('click', function () {
    try { picker.showPicker(); } catch (e) { picker.focus(); picker.click(); }
  });
})();
</script></body></html>
