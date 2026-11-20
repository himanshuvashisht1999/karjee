<?php
declare(strict_types=1);
require_once '../../connection.php';

if (!isset($mconn) || !($mconn instanceof mysqli)) {
    http_response_code(500);
    exit('Accounting connection is not available.');
}

$invoiceId = (int)($_GET['invoice_id'] ?? 0);
$mode = strtolower(trim((string)($_GET['mode'] ?? 'inline')));
if ($invoiceId <= 0) {
    http_response_code(422);
    exit('Invalid invoice.');
}

function cfi_print_cols(mysqli $db,string $table):array {
    $r=$db->query("SHOW COLUMNS FROM `".$db->real_escape_string($table)."`");
    if(!$r) throw new RuntimeException('Unable to inspect '.$table.': '.$db->error);
    $o=[];while($x=$r->fetch_assoc())$o[strtolower($x['Field'])]=$x['Field'];return $o;
}
function cfi_print_pick(array $cols,array $names):?string {
    foreach($names as $name){$k=strtolower($name);if(isset($cols[$k]))return $cols[$k];}return null;
}

try {
    $cols=cfi_print_cols($mconn,'tbl_invoices');
    $idCol=cfi_print_pick($cols,['invoice_id','invoiceid']);
    $pathCol=cfi_print_pick($cols,['invoicepath','invoice_path','invoicefilepath','invoice_file_path','filepath']);
    $noCol=cfi_print_pick($cols,['invoiceno','invoice_no','invoice_number','billno','bill_no']);
    if(!$idCol||!$pathCol) throw new RuntimeException('tbl_invoices invoice_id and invoicepath columns are required.');

    $select="`$pathCol` invoice_path".($noCol?",`$noCol` invoice_no":"");
    $st=$mconn->prepare("SELECT $select FROM tbl_invoices WHERE `$idCol`=? LIMIT 1");
    if(!$st) throw new RuntimeException('Invoice lookup prepare failed: '.$mconn->error);
    $st->bind_param('i',$invoiceId);$st->execute();$row=$st->get_result()->fetch_assoc();$st->close();
    if(!$row) throw new RuntimeException('Invoice not found.');

    $relative=trim((string)($row['invoice_path']??''));
    if($relative==='') throw new RuntimeException('Invoice PDF has not been generated.');

    $appRoot=realpath(dirname(__DIR__).'/..') ?: dirname(dirname(__DIR__));
    $physical=rtrim($appRoot,'/\\').DIRECTORY_SEPARATOR.str_replace(['/', '\\'],DIRECTORY_SEPARATOR,ltrim($relative,'/\\'));
    if(!is_file($physical)) throw new RuntimeException('Saved invoice PDF file is missing: '.$relative);

    $fileName=basename($physical);
    if(!str_ends_with(strtolower($fileName),'.pdf')) $fileName.='.pdf';
    header('Content-Type: application/pdf');
    header('Content-Length: '.filesize($physical));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    header('Content-Disposition: '.($mode==='download'?'attachment':'inline').'; filename="'.addslashes($fileName).'"');
    readfile($physical);
    exit;
} catch(Throwable $e) {
    http_response_code(500);
    exit($e->getMessage());
}
