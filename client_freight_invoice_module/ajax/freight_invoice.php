<?php
/*
 * Shared Client Freight Invoice helper functions.
 * The calling endpoint is responsible for loading connection.php and sending
 * its response headers. Keeping this file functions-only prevents duplicate
 * createConnection() declarations when it is required by ajax/module.php.
 */

function cfi_json(bool $ok,string $message='',array $data=[],int $http=200):void{
    http_response_code($http);
    echo json_encode(['status'=>$ok,'message'=>$message,'data'=>$data],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}
function cfi_money($v):float{return round((float)$v,2);}
function cfi_cfg():array{
    $file=dirname(__DIR__).'/config_client_freight_invoice.php';
    $cfg=is_file($file)?include($file):[];
    return is_array($cfg)?$cfg:[];
}
function cfi_colmeta(mysqli $db,string $table):array{
    $res=$db->query("SHOW COLUMNS FROM `".$db->real_escape_string($table)."`");
    if(!$res) throw new RuntimeException("Unable to inspect $table: ".$db->error);
    $out=[]; while($r=$res->fetch_assoc())$out[strtolower($r['Field'])]=$r; return $out;
}
function cfi_pick(array $cols,array $names):?string{foreach($names as $n){$k=strtolower($n);if(isset($cols[$k]))return $cols[$k]['Field'];}return null;}
function cfi_insert_adaptive(mysqli $db,string $table,array $values,array $aliases):int{
    $meta=cfi_colmeta($db,$table);$row=[];
    foreach($values as $key=>$value){$col=cfi_pick($meta,$aliases[$key]??[$key]);if($col!==null)$row[$col]=$value;}
    $missing=[];
    foreach($meta as $m){$extra=strtolower((string)$m['Extra']);if(str_contains($extra,'auto_increment'))continue;if(strtoupper((string)$m['Null'])==='NO'&&$m['Default']===null&&!array_key_exists($m['Field'],$row))$missing[]=$m['Field'];}
    if($missing) throw new RuntimeException($table.' has required unmapped column(s): '.implode(', ',$missing).'.');
    if(!$row) throw new RuntimeException("No compatible columns found in $table.");
    $cols=array_keys($row);$vals=array_values($row);
    $sql='INSERT INTO `'.$table.'` (`'.implode('`,`',$cols).'`) VALUES ('.implode(',',array_fill(0,count($cols),'?')).')';
    $st=$db->prepare($sql);if(!$st)throw new RuntimeException("$table insert prepare failed: ".$db->error);
    $types=str_repeat('s',count($vals));$bind=[&$types];foreach($vals as $i=>$v){$vals[$i]=(string)$v;$bind[]=&$vals[$i];}
    call_user_func_array([$st,'bind_param'],$bind);
    if(!$st->execute()){$e=$st->error;$st->close();throw new RuntimeException("$table insert failed: $e");}
    $id=(int)$st->insert_id;$st->close();return $id;
}
function cfi_trip(mysqli $db,int $projectid,string $tripId):array{
    $st=$db->prepare("SELECT * FROM krf_transport_trip WHERE projectid=? AND trip_id=? AND transport_type='Client' LIMIT 1");
    if(!$st)throw new RuntimeException('Trip query prepare failed: '.$db->error);
    $st->bind_param('is',$projectid,$tripId);$st->execute();$r=$st->get_result()->fetch_assoc();$st->close();
    if(!$r)throw new InvalidArgumentException('Client Transport Trip not found.');return $r;
}
function cfi_client_freights(mysqli $db,int $projectid,string $tripId):array{
    /*
     * FAST PATH: invoice identity is stored in the Transport DB itself.
     * This avoids one accounting-DB query per client on every review.
     */
    $sql="SELECT clientid,
                 MAX(clientname) clientname,
                 SUM(COALESCE(payable_freight_amount,0)) original_freight,
                 COUNT(*) freight_rows,
                 MAX(COALESCE(freight_invoice_id,0)) freight_invoice_id,
                 MAX(COALESCE(freight_invoice_no,'')) freight_invoice_no,
                 MAX(freight_invoice_date) freight_invoice_date,
                 MAX(COALESCE(freight_invoice_finyr,'')) freight_invoice_finyr,
                 MAX(COALESCE(freight_invoice_path,'')) freight_invoice_path,
                 MAX(COALESCE(billing_discount_percent,0)) billing_discount_percent,
                 MAX(COALESCE(billing_discount_amount,0)) billing_discount_amount,
                 MAX(COALESCE(billing_adjustment_amount,0)) billing_adjustment_amount,
                 MAX(COALESCE(billing_taxable_amount,0)) billing_taxable_amount,
                 MAX(COALESCE(billing_invoice_amount,0)) billing_invoice_amount
          FROM krf_transport_trip_client_freight
          WHERE projectid=? AND trip_id=? AND COALESCE(freight_recoverable,0)=1
          GROUP BY clientid
          ORDER BY MAX(clientname),clientid";
    $st=$db->prepare($sql);
    if(!$st)throw new RuntimeException('Client freight query prepare failed: '.$db->error);
    $st->bind_param('is',$projectid,$tripId);
    $st->execute();
    $rs=$st->get_result();
    $rows=[];
    while($r=$rs->fetch_assoc()){
        $r['original_freight']=cfi_money($r['original_freight']);
        $r['invoice_id']=((int)($r['freight_invoice_id']??0)>0)?(string)(int)$r['freight_invoice_id']:'';
        $r['invoice_no']=(string)($r['freight_invoice_no']??'');
        $r['invoice_path']=(string)($r['freight_invoice_path']??'');
        $r['invoice_status']=($r['invoice_id']!=='' && $r['invoice_no']!=='')?'Invoiced':'Not Invoiced';
        $rows[]=$r;
    }
    $st->close();
    if(!$rows)throw new InvalidArgumentException('No recoverable finalized Client Freight is available for this Trip.');
    return $rows;
}

function cfi_sync_local_invoice(mysqli $db,int $projectid,string $tripId,string $clientid,array $x):void{
    $sql="UPDATE krf_transport_trip_client_freight
          SET freight_invoice_id=?,
              freight_invoice_no=?,
              freight_invoice_date=?,
              freight_invoice_finyr=?,
              freight_invoice_path=?,
              billing_discount_percent=?,
              billing_discount_amount=?,
              billing_adjustment_amount=?,
              billing_adjustment_reason=?,
              billing_taxable_amount=?,
              billing_cgst_amount=?,
              billing_sgst_amount=?,
              billing_igst_amount=?,
              billing_invoice_amount=?,
              freight_invoice_generated_at=?,
              freight_invoice_generated_by=?
          WHERE projectid=? AND trip_id=? AND clientid=?
            AND COALESCE(freight_recoverable,0)=1";
    $st=$db->prepare($sql);
    if(!$st)throw new RuntimeException('Local invoice link prepare failed: '.$db->error);
    $invoiceId=(int)($x['invoice_id']??0);
    $invoiceNo=(string)($x['invoice_no']??'');
    $invoiceDate=(string)($x['invoice_date']??'');
    $finyr=(string)($x['financial_year']??'');
    $invoicePath=(string)($x['invoice_path']??'');
    $discPct=cfi_money($x['discount_percent']??0);
    $discAmt=cfi_money($x['discount_amount']??0);
    $adj=cfi_money($x['adjustment_amount']??0);
    $reason=(string)($x['adjustment_reason']??'');
    $taxable=cfi_money($x['taxable_amount']??0);
    $cgstAmt=cfi_money($x['cgst_amount']??0);
    $sgstAmt=cfi_money($x['sgst_amount']??0);
    $igstAmt=cfi_money($x['igst_amount']??0);
    $invoiceAmount=cfi_money($x['invoice_amount']??0);
    $generatedAt=(string)($x['generated_at']??date('Y-m-d H:i:s'));
    $generatedBy=(string)($x['generated_by']??'System');
    $st->bind_param('issssdddsdddddssiss',
        $invoiceId,$invoiceNo,$invoiceDate,$finyr,$invoicePath,
        $discPct,$discAmt,$adj,$reason,$taxable,$cgstAmt,$sgstAmt,$igstAmt,$invoiceAmount,
        $generatedAt,$generatedBy,$projectid,$tripId,$clientid
    );
    if(!$st->execute()){$e=$st->error;$st->close();throw new RuntimeException('Local invoice link update failed: '.$e);}
    $st->close();
}

function cfi_reconcile_accounting_missing(mysqli $mconn,mysqli $krfconn,int $projectid,string $tripId,array &$clients):void{
    /*
     * Safety reconciliation for historical rows / interrupted saves.
     * ONE accounting query for all missing clients, never N+1 queries.
     */
    $missing=[];
    foreach($clients as $c){
        if(($c['invoice_status']??'Not Invoiced')!=='Invoiced')$missing[]=(string)$c['clientid'];
    }
    if(!$missing)return;

    $hcols=cfi_colmeta($mconn,'tbl_invoices');
    $hId=cfi_pick($hcols,['invoice_id','invoiceid']);
    $hCli=cfi_pick($hcols,['clientid','client_id','customerid','customer_id']);
    $hInv=cfi_pick($hcols,['invoiceno','invoice_no','invoice_number','billno','bill_no']);
    $hRef=cfi_pick($hcols,['refno']);
    $hType=cfi_pick($hcols,['invoice_type','invoicetype','type']);
    $hPath=cfi_pick($hcols,['invoicepath','invoice_path','invoicefilepath','invoice_file_path','filepath']);
    $hDate=cfi_pick($hcols,['invoice_date','invoicedate','date']);
    $hFy=cfi_pick($hcols,['financial_year','financialyear','finyr']);
    if(!$hId||!$hCli||!$hRef)return;

    $select=["`$hId` invoice_id","`$hCli` clientid"];
    $select[]=$hInv?"COALESCE(`$hInv`,'') invoice_no":"'' invoice_no";
    $select[]=$hPath?"COALESCE(`$hPath`,'') invoice_path":"'' invoice_path";
    $select[]=$hDate?"COALESCE(`$hDate`,'') invoice_date":"'' invoice_date";
    $select[]=$hFy?"COALESCE(`$hFy`,'') financial_year":"'' financial_year";
    $ph=implode(',',array_fill(0,count($missing),'?'));
    $sql='SELECT '.implode(',',$select)." FROM tbl_invoices WHERE `$hRef`=? AND `$hCli` IN ($ph)";
    if($hType)$sql.=" AND `$hType`='Freight Invoice'";
    $sql.=" ORDER BY `$hId` DESC";
    $st=$mconn->prepare($sql);
    if(!$st)throw new RuntimeException('Invoice reconciliation prepare failed: '.$mconn->error);
    $types=str_repeat('s',count($missing)+1);
    $params=array_merge([$tripId],$missing);
    $refs=[&$types];foreach($params as $i=>$v){$params[$i]=(string)$v;$refs[]=&$params[$i];}
    call_user_func_array([$st,'bind_param'],$refs);
    $st->execute();$rs=$st->get_result();$found=[];
    while($r=$rs->fetch_assoc()){
        $cid=(string)$r['clientid'];
        if(!isset($found[$cid]))$found[$cid]=$r; // latest invoice because ordered DESC
    }
    $st->close();
    if(!$found)return;

    foreach($clients as &$c){
        $cid=(string)$c['clientid'];
        if(!isset($found[$cid]) || ($c['invoice_status']??'')==='Invoiced')continue;
        $r=$found[$cid];
        cfi_sync_local_invoice($krfconn,$projectid,$tripId,$cid,[
            'invoice_id'=>(int)$r['invoice_id'],
            'invoice_no'=>(string)$r['invoice_no'],
            'invoice_date'=>(string)$r['invoice_date'],
            'financial_year'=>(string)$r['financial_year'],
            'invoice_path'=>(string)$r['invoice_path'],
            'generated_by'=>'Accounting reconciliation'
        ]);
        $c['invoice_status']='Invoiced';
        $c['invoice_id']=(string)$r['invoice_id'];
        $c['invoice_no']=(string)$r['invoice_no'];
        $c['invoice_path']=(string)$r['invoice_path'];
        $c['freight_invoice_date']=(string)$r['invoice_date'];
        $c['freight_invoice_finyr']=(string)$r['financial_year'];
    }
    unset($c);
}

function cfi_one_client_freight(mysqli $db,int $projectid,string $tripId,string $clientid):array{
    $st=$db->prepare("SELECT clientid,MAX(clientname) clientname,SUM(COALESCE(payable_freight_amount,0)) original_freight FROM krf_transport_trip_client_freight WHERE projectid=? AND trip_id=? AND clientid=? AND COALESCE(freight_recoverable,0)=1 GROUP BY clientid LIMIT 1");
    if(!$st)throw new RuntimeException('Client freight query prepare failed: '.$db->error);
    $st->bind_param('iss',$projectid,$tripId,$clientid);$st->execute();$r=$st->get_result()->fetch_assoc();$st->close();if(!$r)throw new InvalidArgumentException('Selected client freight is not available for this Trip.');
    $r['billing_address']='';
    $cm=$db->prepare("SELECT COALESCE(invoicing_address,'') invoicing_address FROM krf_client_master WHERE client_id=? LIMIT 1");
    if($cm){$cm->bind_param('s',$clientid);$cm->execute();$ca=$cm->get_result()->fetch_assoc();$cm->close();if($ca)$r['billing_address']=(string)($ca['invoicing_address']??'');}
    return $r;
}
function cfi_trip_date(array $t):string{$v=$t['actual_dispatch_at']??$t['dispatch_at']??$t['planned_dispatch_datetime']??'';return $v?substr((string)$v,0,10):'';}
function cfi_trip_distance(array $t):float{
    foreach(['total_distance_km','route_distance_km','trip_distance_km','distance_km','cumulative_distance_km'] as $k){
        if(array_key_exists($k,$t) && $t[$k]!==null && $t[$k]!==''){
            return cfi_money($t[$k]);
        }
    }
    return 0.0;
}

function cfi_client_stoppage(mysqli $db,int $projectid,string $tripId,string $clientid):array{
    /*
     * Client Freight Invoice route must use the ACTUAL trip stoppage,
     * not the client's billing address and not the generic "Final Stop".
     *
     * A client can theoretically occur at more than one stop.  For the compact
     * invoice route we use that client's last stop in the trip sequence.
     */
    $sql="SELECT
                ts.id AS trip_stop_id,
                ts.stop_sequence,
                COALESCE(NULLIF(TRIM(ts.destination_address),''),
                         NULLIF(TRIM(ts.destination_locationname),''),
                         '') AS stoppage_address
          FROM krf_transport_trip_stop ts
          WHERE ts.projectid=?
            AND ts.trip_id=?
            AND EXISTS (
                SELECT 1
                FROM krf_transport_trip_detail td
                WHERE td.trip_id=ts.trip_id
                  AND td.trip_stop_id=ts.id
                  AND td.transport_type='Client'
                  AND td.clientid=?
                  AND COALESCE(td.status,'')<>'Cancelled'
            )
          ORDER BY ts.stop_sequence DESC, ts.id DESC
          LIMIT 1";
    $st=$db->prepare($sql);
    if(!$st)throw new RuntimeException('Client stoppage query prepare failed: '.$db->error);
    $st->bind_param('iss',$projectid,$tripId,$clientid);
    $st->execute();
    $r=$st->get_result()->fetch_assoc();
    $st->close();
    return $r?:['trip_stop_id'=>0,'stop_sequence'=>0,'stoppage_address'=>''];
}
function cfi_trip_last_stoppage(mysqli $db,int $projectid,string $tripId):string{
    $sql="SELECT COALESCE(NULLIF(TRIM(destination_address),''),
                         NULLIF(TRIM(destination_locationname),''),
                         '') AS stoppage_address
          FROM krf_transport_trip_stop
          WHERE projectid=? AND trip_id=?
          ORDER BY stop_sequence DESC,id DESC
          LIMIT 1";
    $st=$db->prepare($sql);
    if(!$st)return '';
    $st->bind_param('is',$projectid,$tripId);
    $st->execute();
    $r=$st->get_result()->fetch_assoc();
    $st->close();
    return trim((string)($r['stoppage_address']??''));
}
function cfi_invoice_description(array $trip,string $tripId,string $clientName,string $userRemarks='',string $stoppageAddress=''):string{
    $tripDate=cfi_trip_date($trip);
    $km=cfi_trip_distance($trip);
    $source=trim((string)($trip['source_locationname']??''));
    $dest=trim($stoppageAddress)!==''?trim($stoppageAddress):trim((string)($trip['destination_locationname']??''));
    $parts=[
        'Freight Invoice',
        'Trip: '.$tripId,
        'Trip Date: '.($tripDate?:'N/A'),
        'Distance: '.number_format($km,2,'.','').' KM',
        'Vehicle: '.(trim((string)($trip['vehicle_no']??''))?:'N/A')
    ];
    if($source!=='' || $dest!==''){
        $parts[]='Route: '.($source?:'N/A').' to '.($dest?:'N/A');
    }
    $parts[]='Client: '.(trim($clientName)?:'N/A');
    if(trim($userRemarks)!==''){
        $parts[]='Remarks: '.trim($userRemarks);
    }
    return implode(' | ',$parts);
}
function cfi_trip_any(mysqli $db,int $projectid,string $tripId,string $transportType):array{
    $transportType=ucfirst(strtolower(trim($transportType)));
    if(!in_array($transportType,['Client','Internal'],true)){
        throw new InvalidArgumentException('Transport Type must be Client or Internal.');
    }
    $st=$db->prepare("SELECT * FROM krf_transport_trip WHERE projectid=? AND trip_id=? AND transport_type=? LIMIT 1");
    if(!$st)throw new RuntimeException('Trip query prepare failed: '.$db->error);
    $st->bind_param('iss',$projectid,$tripId,$transportType);
    $st->execute();
    $r=$st->get_result()->fetch_assoc();
    $st->close();
    if(!$r)throw new InvalidArgumentException($transportType.' Transport Trip not found.');
    return $r;
}
function cfi_proposal_data(mysqli $db,int $projectid,string $tripId,string $transportType):array{
    $trip=cfi_trip_any($db,$projectid,$tripId,$transportType);

    $actualLastStoppage=cfi_trip_last_stoppage($db,$projectid,$tripId);

    $data=[
        'trip_id'=>$tripId,
        'transport_type'=>$transportType,
        'transporter_name'=>(string)($trip['transporter_name']??''),
        'vehicle_no'=>(string)($trip['vehicle_no']??''),
        'driver_name'=>(string)($trip['driver_name']??''),
        'source_locationname'=>(string)($trip['source_locationname']??''),
        'destination_locationname'=>($actualLastStoppage!==''?$actualLastStoppage:(string)($trip['destination_locationname']??'')),
        'total_distance_km'=>cfi_money($trip['total_distance_km']??0),
        'both_way_applicable'=>((int)($trip['transporter_both_way_applicable']??0)===1?'Yes':'No'),
        'one_way_freight'=>cfi_money($trip['transporter_one_way_freight_amount']??0),
        'return_freight'=>cfi_money($trip['transporter_return_freight_amount']??0),
        'gross_freight'=>cfi_money($trip['master_freight_amount']??($trip['freight_amount']??0)),
        'discount_percent'=>cfi_money($trip['freight_discountpercent']??0),
        'discount_amount'=>cfi_money($trip['freight_discountamount']??0),
        'pay_to_transporter'=>cfi_money($trip['payable_freightamount']??0),
    ];

    /*
     * Internal Transport compatibility:
     * Older Internal trips may have transporter freight stored in
     * krf_freightcharges. This table is SELECT ONLY here.
     */
    if($transportType==='Internal' &&
       $data['gross_freight']<=0 &&
       $data['pay_to_transporter']<=0){
        $sql="SELECT distance_km,vehicle_no,freight_amount,
                    freight_discountpercent,freight_discountamount,
                    payable_freightamount
              FROM krf_freightcharges
              WHERE projectid=? AND trip_id=?
              ORDER BY id DESC LIMIT 1";
        $st=$db->prepare($sql);
        if($st){
            $st->bind_param('is',$projectid,$tripId);
            $st->execute();
            $f=$st->get_result()->fetch_assoc();
            $st->close();
            if($f){
                if($data['total_distance_km']<=0)$data['total_distance_km']=cfi_money($f['distance_km']??0);
                if(trim($data['vehicle_no'])==='')$data['vehicle_no']=(string)($f['vehicle_no']??'');
                $data['gross_freight']=cfi_money($f['freight_amount']??0);
                $data['discount_percent']=cfi_money($f['freight_discountpercent']??0);
                $data['discount_amount']=cfi_money($f['freight_discountamount']??0);
                $data['pay_to_transporter']=cfi_money($f['payable_freightamount']??0);

                // Internal legacy freight has a single calculated freight value.
                if($data['one_way_freight']<=0 && $data['return_freight']<=0){
                    $data['one_way_freight']=$data['gross_freight'];
                    $data['both_way_applicable']='No';
                }
            }
        }
    }
    return $data;
}
function cfi_finyr(string $date,int $startMonth):string{
    $ts=strtotime($date);if(!$ts)throw new InvalidArgumentException('Invalid Invoice Date.');$y=(int)date('Y',$ts);$m=(int)date('n',$ts);$startMonth=max(1,min(12,$startMonth));$sy=$startMonth===1?$y:($m>=$startMonth?$y:$y-1);return $sy.'-'.($sy+1);
}
function cfi_next_invoice_id(mysqli $db,string $finyr,array $hcols):int{
    $idCol=cfi_pick($hcols,['invoice_id','invoiceid']);
    if(!$idCol)return 0;
    $fyCol=cfi_pick($hcols,['financialyear','financial_year','finyr']);
    if($fyCol){$st=$db->prepare("SELECT COALESCE(MAX(`$idCol`),0)+1 n FROM tbl_invoices WHERE `$fyCol`=?");$st->bind_param('s',$finyr);}else{$st=$db->prepare("SELECT COALESCE(MAX(`$idCol`),0)+1 n FROM tbl_invoices");}
    $st->execute();$n=(int)($st->get_result()->fetch_assoc()['n']??1);$st->close();return max(1,$n);
}
function cfi_invoice_no(int $invoiceId,string $finyr,array $cfg):string{
    $prefix=trim((string)($cfg['invoice_prefix']??'KRF-FRT'));
    $format=(string)($cfg['invoice_number_format']??'{PREFIX}/{FY_SHORT}/{SEQ5}');
    $parts=explode('-',$finyr);$fyShort=(isset($parts[0],$parts[1])?substr($parts[0],-2).'-'.substr($parts[1],-2):$finyr);
    $r=['{PREFIX}'=>$prefix,'{FY}'=>$finyr,'{FY_SHORT}'=>$fyShort,'{SEQ}'=>(string)$invoiceId,'{SEQ4}'=>str_pad((string)$invoiceId,4,'0',STR_PAD_LEFT),'{SEQ5}'=>str_pad((string)$invoiceId,5,'0',STR_PAD_LEFT),'{SEQ6}'=>str_pad((string)$invoiceId,6,'0',STR_PAD_LEFT)];
    return strtr($format,$r);
}
function cfi_invoice_status(mysqli $mconn,string $tripId,string $clientid):array{
    /*
     * Fast status lookup remains header-based because tbl_invoices.refno + client
     * is the accounting invoice identity. tbl_invoices_breakdown is maintained
     * as the accounting detail row, while Transport also keeps a local link.
     */
    $hcols=cfi_colmeta($mconn,'tbl_invoices');
    $hId=cfi_pick($hcols,['invoice_id','invoiceid']);
    $hCli=cfi_pick($hcols,['clientid','client_id','customerid','customer_id']);
    $hInv=cfi_pick($hcols,['invoiceno','invoice_no','invoice_number','billno','bill_no']);
    $hRef=cfi_pick($hcols,['refno','referenceno','reference_no','reference','referencenos','reference_nos','trip_id']);
    $hType=cfi_pick($hcols,['invoice_type','invoicetype','type']);
    $hPath=cfi_pick($hcols,['invoicepath','invoice_path','invoicefilepath','invoice_file_path','filepath']);

    if(!$hId||!$hCli||!$hRef){
        return ['invoice_status'=>'Not Invoiced','invoice_no'=>'','invoice_id'=>'','invoice_path'=>''];
    }

    $select=["h.`$hId` invoice_id"];
    $select[]=$hInv?"h.`$hInv` invoice_no":"'' invoice_no";
    $select[]=$hPath?"COALESCE(h.`$hPath`,'') invoice_path":"'' invoice_path";

    $sql="SELECT ".implode(',',$select)." FROM tbl_invoices h WHERE h.`$hRef`=? AND h.`$hCli`=?";
    $types='ss';
    $params=[$tripId,$clientid];
    if($hType){
        $sql.=" AND h.`$hType`='Freight Invoice'";
    }
    $sql.=" ORDER BY h.`$hId` DESC LIMIT 1";

    $st=$mconn->prepare($sql);
    if(!$st) throw new RuntimeException('Invoice status query prepare failed: '.$mconn->error);
    $st->bind_param($types,$params[0],$params[1]);
    $st->execute();
    $r=$st->get_result()->fetch_assoc();
    $st->close();

    return $r
        ? [
            'invoice_status'=>'Invoiced',
            'invoice_no'=>(string)($r['invoice_no']??''),
            'invoice_id'=>(string)($r['invoice_id']??''),
            'invoice_path'=>(string)($r['invoice_path']??'')
          ]
        : ['invoice_status'=>'Not Invoiced','invoice_no'=>'','invoice_id'=>'','invoice_path'=>''];
}

function cfi_ensure_invoice_breakdown(mysqli $mconn,array $data):void{
    /*
     * Accounting detail rule:
     * Maintain ONE tbl_invoices_breakdown row per Client Freight Invoice / Trip.
     * invoice_id links the breakdown row to tbl_invoices.
     * Trip ID is stored in the available reference column (prefer referencenos).
     * No invoice/status field is changed here.
     */
    $bcols=cfi_colmeta($mconn,'tbl_invoices_breakdown');
    $bId=cfi_pick($bcols,['invoice_id','invoiceid']);
    if(!$bId) throw new RuntimeException('tbl_invoices_breakdown must contain invoice_id/invoiceid.');

    $bRef=cfi_pick($bcols,['referencenos','reference_nos','refno','reference_no','referenceno','trip_id']);
    $invoiceId=(int)($data['invoice_id']??0);
    $tripId=trim((string)($data['trip_id']??''));
    if($invoiceId<=0) throw new InvalidArgumentException('Valid Invoice ID is required for invoice breakdown.');

    // Fast idempotency check: do not create a second breakdown row for the same invoice/trip.
    if($bRef && $tripId!==''){
        $sql="SELECT 1 FROM tbl_invoices_breakdown WHERE `$bId`=? AND `$bRef`=? LIMIT 1";
        $st=$mconn->prepare($sql);
        if(!$st) throw new RuntimeException('Invoice breakdown duplicate-check prepare failed: '.$mconn->error);
        $st->bind_param('is',$invoiceId,$tripId);
    }else{
        $sql="SELECT 1 FROM tbl_invoices_breakdown WHERE `$bId`=? LIMIT 1";
        $st=$mconn->prepare($sql);
        if(!$st) throw new RuntimeException('Invoice breakdown duplicate-check prepare failed: '.$mconn->error);
        $st->bind_param('i',$invoiceId);
    }
    $st->execute();
    $exists=(bool)$st->get_result()->fetch_row();
    $st->close();
    if($exists) return;

    $aliases=[
        'invoiceid'=>['invoice_id','invoiceid'],
        'companyid'=>['companyid','company_id'],
        'subcompanyid'=>['subcompanyid','subcompany_id'],
        'projectid'=>['projectid','project_id'],
        'clientid'=>['clientid','client_id','customerid','customer_id'],
        'clientname'=>['clientname','client_name','customername','customer_name'],
        'invoicetype'=>['invoice_type','invoicetype','type'],
        'invoiceno'=>['invoice_no','invoiceno','invoice_number','billno','bill_no'],
        'invoicedate'=>['invoice_date','invoicedate','date'],
        'financialyear'=>['financial_year','financialyear','finyr'],
        'source'=>['source','source_table','datatype'],
        'referencenos'=>['referencenos','reference_nos','refno','reference_no','referenceno','trip_id'],
        'description'=>['description','particulars','item_description','other_description'],
        'original'=>['original_freight','original_amount','totalrowsamount','subtotal','amount'],
        'billing'=>['billing_freight','billing_amount','freight_amount','item_amount'],
        'billingmethod'=>['billing_method','calculationtype','calculation_type'],
        'travelbasis'=>['travel_basis','both_way_applicable'],
        'onewaykm'=>['one_way_km','distance_km'],
        'onewayrate'=>['one_way_rate','rate_per_km','freight_rate'],
        'returnkm'=>['return_km'],
        'returnrate'=>['return_rate'],
        'discountpct'=>['discount_percent','discountpercent'],
        'discount'=>['discount_amount','totalrowsdiscount','discount'],
        'adjustment'=>['adjustment','adjustment_amount','otherdiscount'],
        'taxable'=>['taxableamount','taxable_amount'],
        'gst'=>['invoicegstamount','gst_amount','totalgstamount'],
        'total'=>['totalpayableamount','invoice_amount','total_amount','payable_amount'],
        'cgstpct'=>['cgst_percent','cgstpercent'],
        'sgstpct'=>['sgst_percent','sgstpercent'],
        'igstpct'=>['igst_percent','igstpercent'],
        'cgstamt'=>['cgst_amount','cgstamount'],
        'sgstamt'=>['sgst_amount','sgstamount'],
        'igstamt'=>['igst_amount','igstamount'],
        'reason'=>['adjustment_reason','discount_reason','reason'],
        'remarks'=>['remarks','remark'],
        'createdby'=>['created_by','createdby','loginid','loginname'],
        'createdat'=>['created_at','createdon']
    ];

    $vals=[
        'invoiceid'=>$invoiceId,
        'companyid'=>(int)($data['companyid']??0),
        'subcompanyid'=>(int)($data['subcompanyid']??0),
        'projectid'=>(int)($data['projectid']??0),
        'clientid'=>(string)($data['clientid']??''),
        'clientname'=>(string)($data['clientname']??''),
        'invoicetype'=>(string)($data['invoice_type']??'Freight Invoice'),
        'invoiceno'=>(string)($data['invoice_no']??''),
        'invoicedate'=>(string)($data['invoice_date']??''),
        'financialyear'=>(string)($data['financial_year']??''),
        'source'=>'Transport Client Freight',
        'referencenos'=>$tripId,
        'description'=>(string)($data['description']??''),
        'original'=>cfi_money($data['original_freight']??0),
        'billing'=>cfi_money($data['billing_freight']??0),
        'billingmethod'=>'TRANSPORT',
        'travelbasis'=>'TRANSPORT_CLIENT_FREIGHT',
        'onewaykm'=>cfi_money($data['trip_distance_km']??0),
        'onewayrate'=>0,
        'returnkm'=>0,
        'returnrate'=>0,
        'discountpct'=>cfi_money($data['discount_percent']??0),
        'discount'=>cfi_money($data['discount_amount']??0),
        'adjustment'=>cfi_money($data['adjustment_amount']??0),
        'taxable'=>cfi_money($data['taxable_amount']??0),
        'gst'=>cfi_money($data['gst_amount']??0),
        'total'=>cfi_money($data['invoice_amount']??0),
        'cgstpct'=>cfi_money($data['cgst_percent']??0),
        'sgstpct'=>cfi_money($data['sgst_percent']??0),
        'igstpct'=>cfi_money($data['igst_percent']??0),
        'cgstamt'=>cfi_money($data['cgst_amount']??0),
        'sgstamt'=>cfi_money($data['sgst_amount']??0),
        'igstamt'=>cfi_money($data['igst_amount']??0),
        'reason'=>(string)($data['adjustment_reason']??''),
        'remarks'=>(string)($data['remarks']??''),
        'createdby'=>(string)($data['created_by']??'System'),
        'createdat'=>(string)($data['created_at']??date('Y-m-d H:i:s'))
    ];

    cfi_insert_adaptive($mconn,'tbl_invoices_breakdown',$vals,$aliases);
}

function cfi_generate_pdf_once(mysqli $mconn,array $headCols,int $invoiceId,string $invoiceNo,string $invoiceDate,string $finyr,array $f,array $trip,string $tripId,float $original,float $discount,float $adjustment,float $taxable,float $cgst,float $sgst,float $igst,float $cgstAmt,float $sgstAmt,float $igstAmt,float $invoiceAmount,string $reason,string $remarks,string $preparedBy,string $stoppageAddress=''):string{
    $pathCol=cfi_pick($headCols,['invoicepath','invoice_path','invoicefilepath','invoice_file_path','filepath']);
    $idCol=cfi_pick($headCols,['invoice_id','invoiceid']);
    if(!$pathCol||!$idCol) throw new RuntimeException('tbl_invoices must contain invoicepath (or invoice_path).');
    $st=$mconn->prepare("SELECT COALESCE(`$pathCol`,'') invoice_path FROM tbl_invoices WHERE `$idCol`=? LIMIT 1");
    if(!$st) throw new RuntimeException('Invoice path lookup prepare failed: '.$mconn->error);
    $st->bind_param('i',$invoiceId);$st->execute();$existing=(string)(($st->get_result()->fetch_assoc()['invoice_path']??''));$st->close();
    if(trim($existing)!=='') return $existing;
    $moduleRoot=dirname(__DIR__); require_once $moduleRoot.'/config_document_storage.php'; require_once $moduleRoot.'/TransportStoredPdfRenderer.php';
    $tcpdfPath=TRANSPORT_DOCUMENT_APP_ROOT.'/pdfroutines/tcpdf/tcpdf.php'; if(!is_file($tcpdfPath)) throw new RuntimeException('TCPDF library was not found.'); require_once $tcpdfPath;
    $relativeDir='attachementfile/client_freight_invoices/'.date('Y',strtotime($invoiceDate)).'/'.date('m',strtotime($invoiceDate));
    $physicalDir=rtrim(TRANSPORT_DOCUMENT_APP_ROOT,'/\\').DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$relativeDir);
    if(!is_dir($physicalDir)&&!mkdir($physicalDir,0755,true)&&!is_dir($physicalDir)) throw new RuntimeException('Unable to create Client Freight Invoice PDF folder.');
    $safeNo=preg_replace('/[^A-Za-z0-9_-]+/','_',$invoiceNo)?:('FREIGHT_'.$invoiceId); $fileName=$safeNo.'.pdf'; $relativePath=$relativeDir.'/'.$fileName; $physicalPath=$physicalDir.DIRECTORY_SEPARATOR.$fileName;
    $pdf=new TCPDF(PDF_PAGE_ORIENTATION,PDF_UNIT,PDF_PAGE_FORMAT,true,'UTF-8',false); $pdf->SetCreator('Karjee Farms Transport System'); $pdf->SetAuthor('Karjee Farms'); $pdf->SetTitle($invoiceNo); $pdf->SetMargins(13,10,13); $pdf->SetAutoPageBreak(true,13); $pdf->setPrintHeader(false); $pdf->setPrintFooter(false); $pdf->AddPage();
    $renderer=new TransportStoredPdfRenderer($pdf,transport_document_brand());
    $renderer->renderClientFreightInvoice(['invoice_no'=>$invoiceNo,'invoice_date'=>$invoiceDate,'financial_year'=>$finyr,'client_id'=>(string)($f['clientid']??''),'client_name'=>(string)($f['clientname']??''),'billing_address'=>(string)($f['billing_address']??''),'trip_id'=>$tripId,'trip_date'=>cfi_trip_date($trip),'trip_distance_km'=>cfi_trip_distance($trip),'vehicle_no'=>(string)($trip['vehicle_no']??''),'source_locationname'=>(string)($trip['source_locationname']??''),'stoppage_address'=>$stoppageAddress,'original_freight'=>$original,'discount_percent'=>($original>0?round($discount/$original*100,2):0),'discount_amount'=>$discount,'adjustment'=>$adjustment,'taxable'=>$taxable,'cgst_percent'=>$cgst,'sgst_percent'=>$sgst,'igst_percent'=>$igst,'cgst_amount'=>$cgstAmt,'sgst_amount'=>$sgstAmt,'igst_amount'=>$igstAmt,'invoice_amount'=>$invoiceAmount,'reason'=>$reason,'remarks'=>$remarks,'prepared_by'=>$preparedBy]);
    $pdf->Output($physicalPath,'F'); if(!is_file($physicalPath)||filesize($physicalPath)<=0) throw new RuntimeException('Freight Invoice PDF could not be saved.');
    $st=$mconn->prepare("UPDATE tbl_invoices SET `$pathCol`=? WHERE `$idCol`=? AND COALESCE(`$pathCol`,'')=''"); if(!$st){@unlink($physicalPath);throw new RuntimeException('Invoice path update prepare failed: '.$mconn->error);} $st->bind_param('si',$relativePath,$invoiceId); if(!$st->execute()){$e=$st->error;$st->close();@unlink($physicalPath);throw new RuntimeException('Invoice path save failed: '.$e);} $st->close(); return $relativePath;
}
