<?php
// Call within the allocation/removal transaction before modifying this payment.
function preventCancellationPaymentReallocation($db,$paymentId){
    $stmt=$db->prepare('SELECT * FROM advance_payment_transactions WHERE sno=? FOR UPDATE');
    $stmt->bind_param('i',$paymentId);$stmt->execute();$row=$stmt->get_result()->fetch_assoc();$stmt->close();
    if(!$row)throw new RuntimeException('Payment record not found.');
    $type=$row['transaction_type']??'Receipt';
    if(!in_array($type,['Receipt','Retained Advance'],true)||(float)$row['advanceamount']<=0)throw new RuntimeException('This payment is pending refund or refunded and cannot be allocated or unallocated.');
    if($type==='Retained Advance'&&$row['verification']!=='verified')throw new RuntimeException('The retained advance must be verified before allocation.');
    // Keep earlier deployed settlements protected until explicitly migrated.
    $exists=$db->query("SHOW TABLES LIKE 'krf_order_cancellation_payment'");
    if($exists&&$exists->num_rows){$stmt=$db->prepare('SELECT id FROM krf_order_cancellation_payment WHERE payment_sno=?');$stmt->bind_param('i',$paymentId);$stmt->execute();$legacy=$stmt->get_result()->fetch_assoc();$stmt->close();if($legacy)throw new RuntimeException('This payment belongs to an earlier settlement. Reconcile it before reuse.');}
    return $row;
}
