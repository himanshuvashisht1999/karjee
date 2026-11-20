<?php
ini_set('display_errors','0');error_reporting(E_ALL);
require_once __DIR__.'/confirmed_access.php';require_once __DIR__.'/cancellation_helpers.php';
header('Content-Type: application/json; charset=utf-8');
$uploadPath=null;$clientNotification=null;
try{
    mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
    $scope=confirmedAjaxContext(false);caAuthorize();
    $action=$_GET['action']??'';
    if($_SERVER['REQUEST_METHOD']!=='POST')throw new RuntimeException('POST is required.');
    if(empty($_SESSION['ca_csrf'])||!hash_equals($_SESSION['ca_csrf'],(string)($_POST['csrf']??'')))throw new RuntimeException('Session expired. Please refresh.');
    $krfconn->begin_transaction();
    if($action==='list'){
        $data=editQuery($krfconn,"SELECT p.psono,p.clientname,p.mobileno,p.created_at,s.sono,NOT EXISTS (SELECT 1 FROM krf_sales_order_items i WHERE i.sono=s.sono AND i.projectid=s.projectid AND LOWER(TRIM(COALESCE(i.status,''))) NOT IN ('','pending','confirmed','cancel','cancelled')) AS editing_allowed FROM krf_provisional_sales_order p JOIN krf_sales_order s ON s.psono=p.psono AND s.projectid=p.projectid AND s.companyid=p.companyid AND s.subcompanyid=p.subcompanyid WHERE p.companyid=? AND p.subcompanyid=? AND p.projectid=? AND LOWER(p.status)='confirmed' AND LOWER(s.status) NOT IN ('cancelled','delivered','completed')
AND NOT EXISTS (SELECT 1 FROM krf_sales_order_items i WHERE i.sono=s.sono AND i.projectid=s.projectid AND LOWER(TRIM(COALESCE(i.status,''))) NOT IN ('','pending','confirmed','cancel','cancelled'))
AND NOT EXISTS (SELECT 1 FROM krf_sales_order_items i WHERE i.sono=s.sono AND i.projectid=s.projectid AND LOWER(TRIM(COALESCE(i.status,''))) NOT IN ('cancel','cancelled') AND (COALESCE(i.reservedqty,0)>0 OR COALESCE(i.allocate_ordqty,0)>0 OR COALESCE(i.dispatchedqty,0)>0 OR COALESCE(i.shiftingqty,0)>0 OR COALESCE(i.invoiceno,'')<>'' OR i.invoicedate IS NOT NULL OR COALESCE(i.ledgerstatus,'')<>'' OR COALESCE(i.invoicefilepath,'')<>'' OR COALESCE(i.proformainvno,'')<>'' OR COALESCE(i.proformainvpath,'')<>''))
AND NOT EXISTS (SELECT 1 FROM advance_payment_transactions t WHERE t.companyid=p.companyid AND t.subcompanyid=p.subcompanyid AND t.projectid=p.projectid AND t.clientid=p.clientid AND t.invoiceno IN (p.psono,s.sono) AND (t.advanceamount<0 OR (t.advanceamount>0 AND (t.verification<>'verified' OR COALESCE(t.ledgerstatus,'')<>'' OR t.transaction_type<>'Receipt'))))
AND NOT EXISTS (SELECT 1 FROM krf_deliveryaddress_location d WHERE d.projectid=p.projectid AND d.psono=p.psono AND (LOWER(TRIM(COALESCE(d.delivery_status,''))) NOT IN ('','pending','cancelled') OR d.dispatch_datetime IS NOT NULL))
AND NOT EXISTS (SELECT 1 FROM krf_freightcharges f WHERE f.projectid=p.projectid AND f.psono=p.psono AND (COALESCE(f.trip_id,'')<>'' OR COALESCE(f.freight_invoice_no,'')<>'' OR COALESCE(f.freightinvoicepath,'')<>''))
ORDER BY p.id DESC",array_slice($scope,0,3));
    }elseif($action==='preview'||$action==='cancel'){
        $s=caPreview($krfconn,trim((string)($_POST['psono']??'')),$scope);
        $token=ceToken($s);
        if($action==='preview')$data=['snapshot'=>$s,'token'=>$token];
        else{
            if(!hash_equals($token,(string)($_POST['token']??'')))throw new RuntimeException('Order, sowing or payment details changed. Please reopen the cancellation review.');
            $split=json_decode((string)($_POST['split']??''),true);if(!is_array($split))throw new RuntimeException('Payment settlement details are required.');
             $data=['psono'=>caCancel($krfconn,$s,$split,trim((string)($_POST['reason']??'')),$scope[3],(string)($loginname??$scope[3]))];
            $refundTotal=0;$advanceTotal=0;foreach($s['payments'] as $payment){$decision=$split[(string)$payment['sno']]??[];$refundTotal+=caMoney($decision['refund']??'0');$advanceTotal+=caMoney($decision['advance']??'0');}
            $clientNotification=['event'=>'cancel','clientid'=>$s['header']['clientid'],'clientname'=>$s['header']['clientname'],'mobile'=>$s['header']['mobileno'],'psono'=>$s['header']['psono'],'sono'=>$s['sale']['sono'],'refund'=>caDecimal($refundTotal),'advance'=>caDecimal($advanceTotal)];
        }
    }elseif($action==='settlements'){
        $data=editQuery($krfconn,"SELECT t.sno AS id,COALESCE(s.psono,t.invoiceno) AS psono,s.sono,t.clientid,t.clientname,t.sno AS payment_sno,t.remarks,CASE WHEN t.transaction_type='Retained Advance' THEN 0 ELSE t.advanceamount END AS refund_amount,CASE WHEN t.transaction_type='Retained Advance' THEN t.advanceamount ELSE 0 END AS retained_amount,CASE WHEN t.transaction_type='Refund Pending' THEN 'Pending' WHEN t.transaction_type='Refunded Receipt' THEN 'Completed' ELSE 'Not Required' END AS refund_status,r.utrnumber AS refund_utr,r.paymentdate AS refund_date,r.receiptpath AS refund_document FROM advance_payment_transactions t LEFT JOIN krf_sales_order s ON s.projectid=t.projectid AND s.companyid=t.companyid AND s.subcompanyid=t.subcompanyid AND (s.psono=t.invoiceno OR s.sono=t.invoiceno) LEFT JOIN advance_payment_transactions r ON r.companyid=t.companyid AND r.subcompanyid=t.subcompanyid AND r.projectid=t.projectid AND r.transaction_type='Refund' AND r.paymentref_no=CONCAT('CANCEL-REFUND:',t.sno) WHERE t.companyid=? AND t.subcompanyid=? AND t.projectid=? AND t.transaction_type IN ('Refund Pending','Refunded Receipt','Retained Advance') ORDER BY t.sno DESC",array_slice($scope,0,3));
        foreach($data as &$entry)if(empty($entry['psono'])&&preg_match('/Cancelled PSO: ([^ |\r\n]+)/',(string)$entry['remarks'],$match))$entry['psono']=$match[1];unset($entry);
        $addresses=editQuery($krfconn,'SELECT psono,clientid,mobileno FROM krf_provisional_sales_order WHERE companyid=? AND subcompanyid=? AND projectid=?',array_slice($scope,0,3));
        $byOrder=[];foreach($addresses as $address)$byOrder[$address['psono']]=$address;
        $clients=editQuery($krfconn,'SELECT client_id,invoicing_address FROM krf_client_master WHERE projectid=?',[$scope[2]]);$byClient=[];foreach($clients as $client)$byClient[$client['client_id']]=$client['invoicing_address'];
        foreach($data as &$entry){$address=$byOrder[$entry['psono']]??[];$entry['mobileno']=$address['mobileno']??'';$entry['invoice_address']=$byClient[$entry['clientid']]??'';}unset($entry);

    }elseif($action==='refund'||$action==='document'){
        $rows=editQuery($krfconn,'SELECT * FROM advance_payment_transactions WHERE sno=? AND companyid=? AND subcompanyid=? AND projectid=? FOR UPDATE',array_merge([$_POST['detail_id']??0],array_slice($scope,0,3)));
        if(count($rows)!==1)throw new RuntimeException('Payment not found.');$row=$rows[0];$reference='CANCEL-REFUND:'.$row['sno'];
        $refunds=editQuery($krfconn,"SELECT * FROM advance_payment_transactions WHERE companyid=? AND subcompanyid=? AND projectid=? AND transaction_type='Refund' AND paymentref_no=? FOR UPDATE",array_merge(array_slice($scope,0,3),[$reference]));
        if($action==='document'){
            if(count($refunds)!==1)throw new RuntimeException('Refund document not found.');
            $file=basename((string)$refunds[0]['receiptpath']);$path=dirname(__DIR__).'/cancellation_documents/'.$file;
            if(!$file||!is_file($path))throw new RuntimeException('Document not found.');
            $krfconn->commit();header('Content-Type: application/octet-stream');header('Content-Disposition: attachment; filename="'.$file.'"');header('X-Content-Type-Options: nosniff');readfile($path);exit;
        }
        if($row['transaction_type']!=='Refund Pending'||caMoney($row['advanceamount'])<=0||$refunds)throw new RuntimeException('This refund is not pending or was already recorded. Please refresh.');
        $utr=trim((string)($_POST['utr']??''));$date=(string)($_POST['transaction_date']??'');$d=DateTimeImmutable::createFromFormat('!Y-m-d',$date);
        if($utr==='')throw new RuntimeException('Please enter the UTR / transaction number.');
        if(strlen($utr)>45)throw new RuntimeException('UTR / transaction number must not exceed 45 characters.');
        if(!$d||$d->format('Y-m-d')!==$date)throw new RuntimeException('Please enter a valid refund transaction date.');
        $today=(new DateTimeImmutable('now',new DateTimeZone('Asia/Kolkata')))->format('Y-m-d');
        if($date>$today)throw new RuntimeException('Refund transaction date cannot be later than today ('.date('d-M-Y',strtotime($today)).').');
        if(caMoney($_POST['amount']??'')!==caMoney($row['advanceamount']))throw new RuntimeException('The paid refund amount must match the pending amount.');
        $file=$_FILES['proof']??null;
        if(!$file||$file['error']!==UPLOAD_ERR_OK||$file['size']>5*1024*1024)throw new RuntimeException('Upload a PDF, JPG or PNG document up to 5 MB.');
        $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);$extensions=['application/pdf'=>'pdf','image/jpeg'=>'jpg','image/png'=>'png'];
        if(!isset($extensions[$mime]))throw new RuntimeException('Only PDF, JPG or PNG proof is allowed.');
        $directory=dirname(__DIR__).'/cancellation_documents';if(!is_dir($directory)&&!mkdir($directory,0750,true))throw new RuntimeException('Cannot store the document.');
        $filename=bin2hex(random_bytes(20)).'.'.$extensions[$mime];$uploadPath=$directory.'/'.$filename;
        if(!move_uploaded_file($file['tmp_name'],$uploadPath))throw new RuntimeException('Cannot store the document.');
        $note=date('d-m-Y H:i:s').' | Refund completed for original payment '.$row['sno'].' | Order '.$row['invoiceno'].' | UTR '.$utr.' | By '.($loginname??$scope[3]);
        editQuery($krfconn,"INSERT INTO advance_payment_transactions(companyid,subcompanyid,projectid,clientid,clientname,advanceamount,utrnumber,paymentdate,purposeofpayment,invoiceno,sourceofpayment,paymentref_no,receivedby,receiptpath,remarks,tedit,verification,verificationby,verificationat,transaction_type) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),'Refund')",[$scope[0],$scope[1],$scope[2],$row['clientid'],$row['clientname'],'-'.caDecimal(caMoney($row['advanceamount'])),$utr,$date,'Cancelled order refund',$row['invoiceno'],'Refund',$reference,$scope[3],$filename,$note,$scope[3],'verified',$scope[3]]);
        $refundId=$krfconn->insert_id;
        editQuery($krfconn,"UPDATE advance_payment_transactions SET transaction_type='Refunded Receipt',remarks=CONCAT(COALESCE(remarks,''),CHAR(10),?),tedit=? WHERE sno=?",[$note,$scope[3],$row['sno']]);
        $audit=['header'=>['projectid'=>$scope[2],'psono'=>$row['invoiceno']],'sale'=>['sono'=>'']];
        ceLog($krfconn,$audit,$scope[3],(string)($loginname??$scope[3]),'','Cancellation Refund Recorded',$row,['original_payment_sno'=>$row['sno'],'refund_payment_sno'=>$refundId,'utr'=>$utr,'date'=>$date,'amount'=>'-'.caDecimal(caMoney($row['advanceamount'])),'document'=>$filename]);
        $data=['detail_id'=>$row['sno'],'refund_payment_sno'=>$refundId];
        $orders=editQuery($krfconn,'SELECT p.psono,p.clientname,p.mobileno,s.sono FROM krf_provisional_sales_order p JOIN krf_sales_order s ON s.psono=p.psono AND s.companyid=p.companyid AND s.subcompanyid=p.subcompanyid AND s.projectid=p.projectid WHERE p.companyid=? AND p.subcompanyid=? AND p.projectid=? AND p.clientid=? AND (p.psono=? OR s.sono=?)',[$scope[0],$scope[1],$scope[2],$row['clientid'],$row['invoiceno'],$row['invoiceno']]);
        $order=$orders[0]??[];$clientNotification=['event'=>'refund','clientid'=>$row['clientid'],'clientname'=>$row['clientname'],'mobile'=>$order['mobileno']??'','psono'=>$order['psono']??$row['invoiceno'],'sono'=>$order['sono']??'','amount'=>$row['advanceamount'],'utr'=>$utr,'date'=>$date];
    }else throw new RuntimeException('Invalid action.');
    $krfconn->commit();$notification=null;
    if($clientNotification){try{require_once dirname(__DIR__,2).'/whatsappnotification/order_cancellation_notifications.php';$notification=queueClientCancellationWhatsApp($krfconn,$clientNotification);}catch(Throwable $notificationError){error_log('Cancellation/refund WhatsApp: '.$notificationError->getMessage());$notification=['status'=>'failed','message'=>'Saved successfully, but client WhatsApp could not be queued. '.$notificationError->getMessage()];}}
    echo json_encode(['status'=>'success','data'=>$data,'notification'=>$notification],JSON_UNESCAPED_UNICODE);
}catch(Throwable $e){if(isset($krfconn))try{$krfconn->rollback();}catch(Throwable $ignored){}if($uploadPath&&is_file($uploadPath))unlink($uploadPath);http_response_code(400);echo json_encode(['status'=>'error','message'=>$e->getMessage()],JSON_UNESCAPED_UNICODE);}
