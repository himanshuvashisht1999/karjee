<?php
// Shared read/statement helpers. No transaction or HTTP response side effects.
function editQuery($conn, $sql, array $params = []) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new RuntimeException($conn->error);
    try {
        if ($params) $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        if (!$stmt->execute()) throw new RuntimeException($stmt->error);
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    } finally { $stmt->close(); }
}
function editSnapshot($conn, $psono, $projectid, $lock = false) {
    $suffix = $lock ? ' FOR UPDATE' : '';
    $params = [$psono, $projectid];
    $headers = editQuery($conn, 'SELECT * FROM krf_provisional_sales_order WHERE psono = ? AND projectid = ?' . $suffix, $params);
    if (count($headers) !== 1) throw new RuntimeException('PSO number not found in this project.');
    $snapshot = ['header' => $headers[0]];
    foreach (['items' => 'krf_provisional_sales_order_items', 'delivery' => 'krf_deliveryaddress_location', 'freight' => 'krf_freightcharges', 'consumed' => 'krf_productionorder_consumed'] as $name => $table) {
        $snapshot[$name] = editQuery($conn, "SELECT * FROM `$table` WHERE psono = ? AND projectid = ? ORDER BY id" . $suffix, $params);
    }
    return $snapshot;
}


/** Compare customer-visible order data only; ignore timestamps, IDs and audit metadata.
 * Rows are sorted by stable business identity so SQL ordering cannot create false changes.
 */
function editBusinessOrderState(array $snapshot): array {
    $sections = [
        'header' => ['clientid','clientname','mobileno','emailid','address','delivery_address','districtname','statename','cityname','tahseel','pickupfrom','remarks','status'],
        'sale' => ['clientid','clientname','mobileno','emailid','address','delivery_address','pickupfrom','remarks'],
        'items' => ['product_id','product_name','stockorder_no','quantity','rate','taxableamount','discount_percent','discount_amount','cgst_percent','cgst_amount','sgst_percent','sgst_amount','igst_percent','igst_amount','amount','freight_charges','expecteddeliverydate','status'],
        'sales_items' => ['product_id','product_name','stockorder_no','quantity','rate','taxableamount','discount_percent','discount_amount','cgst_percent','cgst_amount','sgst_percent','sgst_amount','igst_percent','igst_amount','amount','freight_charges','expecteddeliverydate','status'],
        'delivery' => ['productid','stockorder_no','deliveryqty','deliverycharges','deliveryaddress','dest_state','dest_district','dest_city','dest_pincode','stateid','districtid','cityid','totaldistance','freedistance','distance','remark','pickuppoint','pickuptype','noofbox','boxperpack','chargesperbox','freightamt','delivery_status'],
        'freight' => ['freight_amount','freight_discountpercent','freight_discountamount','payable_freightamount','zerocharge','remarks']
    ];
    $result=[];
    foreach($sections as $section=>$fields){
        if(!array_key_exists($section,$snapshot))continue;
        $rows=in_array($section,['header','sale'],true)?[$snapshot[$section]]:(array)$snapshot[$section];
        $normalized=[];
        foreach($rows as $row){
            if(!is_array($row))continue;
            if(in_array($section,['items','sales_items','delivery'],true) && in_array(strtolower(trim((string)($row['status']??$row['delivery_status']??''))),['cancel','cancelled'],true))continue;
            $values=[];
            foreach($fields as $field){
                if(!array_key_exists($field,$row))continue;
                $v=$row[$field];
                if($v===null)$v='';
                $v=trim((string)$v);
                if($field==='expecteddeliverydate' && $v!=='')$v=substr($v,0,10);
                if(in_array($field,['quantity','deliveryqty','noofbox','boxperpack'],true) && is_numeric($v))$v=(string)(float)$v;
                if(in_array($field,['rate','amount','taxableamount','freight_charges','discount_percent','discount_amount','cgst_percent','cgst_amount','sgst_percent','sgst_amount','igst_percent','igst_amount','deliverycharges','totaldistance','freedistance','distance','chargesperbox','freightamt','freight_amount','freight_discountpercent','freight_discountamount','payable_freightamount'],true) && is_numeric($v))$v=number_format((float)$v,2,'.','');
                $values[$field]=$v;
            }
            $normalized[]=json_encode($values,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        }
        sort($normalized,SORT_STRING);
        $result[$section]=$normalized;
    }
    return $result;
}
function editHasBusinessOrderChanges(array $before,array $after): bool {
    return editBusinessOrderState($before)!==editBusinessOrderState($after);
}
