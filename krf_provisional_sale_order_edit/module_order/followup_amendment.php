<?php
/** Append a completed amendment to the existing PO follow-up history.
 * Called inside the order update transaction; never changes follow-up dates.
 */
function krfRecordCompletedAmendment($conn, $projectid, $psono, $clientid, $actorId, $actorName, $kind='Provisional', $before=null, $after=null) {
    $stmt=$conn->prepare("SELECT id,reason,next_followup_date FROM krf_provisional_order_followup WHERE projectid=? AND psono=? ORDER BY id DESC LIMIT 1");
    if (!$stmt) throw new RuntimeException('Unable to check amendment follow-up: '.$conn->error);
    $stmt->bind_param('ss',$projectid,$psono);
    if (!$stmt->execute()) { $err=$stmt->error; $stmt->close(); throw new RuntimeException($err); }
    $last=$stmt->get_result()->fetch_assoc(); $stmt->close();
    // Record a genuine change regardless of whether a follow-up reason was previously saved.
    // Restrict comparison to order item business fields; ignore audit timestamps/IDs.
    $fields=['product_id','productid','quantity','rate','price','discount','discountpercent','discountamount','expecteddeliverydate','expected_delivery_date','deliverydate','amount'];
    $items=function($snapshot)use($fields){
        $out=[];
        foreach((array)($snapshot['items']??[]) as $item){
            $v=[];foreach($fields as $f)if(array_key_exists($f,$item))$v[$f]=(string)$item[$f];
            $out[]=json_encode($v);
        }
        sort($out);return $out;
    };
    if(!is_array($before)||!is_array($after)||$items($before)===$items($after))return false;
    $reason='Order Amended';
    $conversation=$kind.' sales order amended and saved successfully after changes to order items.';
    $next=$last['next_followup_date']??null;
    $stmt=$conn->prepare('INSERT INTO krf_provisional_order_followup (projectid,psono,clientid,reason,conversation,next_followup_date,created_by,created_by_name,created_at) VALUES (?,?,?,?,?,?,?,?,NOW())');
    if (!$stmt) throw new RuntimeException('Unable to prepare amendment history: '.$conn->error);
    $stmt->bind_param('ssssssss',$projectid,$psono,$clientid,$reason,$conversation,$next,$actorId,$actorName);
    if (!$stmt->execute()) { $err=$stmt->error; $stmt->close(); throw new RuntimeException('Unable to record amendment history: '.$err); }
    $stmt->close();
    return true;
}
