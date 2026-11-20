<?php
function editReconcileReservations($conn, array $original, $psono, $projectid, array $readyContributions) {
    $items = editQuery($conn, 'SELECT product_id, product_name, stockorder_no, SUM(quantity) AS qty FROM krf_provisional_sales_order_items WHERE psono=? AND projectid=? GROUP BY product_id, product_name, stockorder_no ORDER BY stockorder_no, product_id', [$psono, $projectid]);
    $oldByKey = [];
    foreach ($original['consumed'] as $row) $oldByKey[$row['stockorderno'] . '|' . $row['product_id']][] = $row;
    $kept = [];
    foreach ($items as $item) {
        $key = $item['stockorder_no'] . '|' . $item['product_id'];
        $stocks = editQuery($conn, 'SELECT * FROM krf_production_stockorder WHERE stockorder_no=? AND productid=? AND projectid=? FOR UPDATE', [$item['stockorder_no'], $item['product_id'], $projectid]);
        if (count($stocks) !== 1) throw new RuntimeException('Production stock identity is missing or ambiguous.');
        $stock = $stocks[0];
        $contribution = (float)($readyContributions[$key] ?? 0);
        if ($contribution > 0) editQuery($conn, 'UPDATE krf_production_stockorder SET Qty_from_readysale=COALESCE(Qty_from_readysale,0)+? WHERE id=? AND projectid=?', [$contribution, $stock['id'], $projectid]);
        $oldRows = $oldByKey[$key] ?? [];
        if ($oldRows) {
            $first = $oldRows[0];
            $wastage = array_sum(array_map(function ($row) { return (float)($row['wastage_qty'] ?? 0); }, $oldRows));
            editQuery($conn, 'UPDATE krf_productionorder_consumed SET consumed_qty=?, product_name=?, wastage_qty=? WHERE id=? AND psono=? AND projectid=?', [$item['qty'], $item['product_name'], $wastage, $first['id'], $psono, $projectid]);
            $kept[(string)$first['id']] = true;
        } else {
            editQuery($conn, 'INSERT INTO krf_productionorder_consumed (psono,stockorderno,product_id,product_name,consumed_qty,wastage_qty,projectid) VALUES (?,?,?,?,?,0,?)', [$psono, $item['stockorder_no'], $item['product_id'], $item['product_name'], $item['qty'], $projectid]);
        }
    }
    // Preserve recorded wastage even if its sales allocation is removed.
    foreach ($oldByKey as $oldRows) {
        $first = $oldRows[0];
        $wastage = array_sum(array_map(function ($row) { return (float)($row['wastage_qty'] ?? 0); }, $oldRows));
        if (!isset($kept[(string)$first['id']]) && $wastage > 0) {
            editQuery($conn, 'UPDATE krf_productionorder_consumed SET consumed_qty=0,wastage_qty=? WHERE id=? AND psono=? AND projectid=?', [$wastage, $first['id'], $psono, $projectid]);
            $kept[(string)$first['id']] = true;
        }
    }
    foreach ($original['consumed'] as $old) {
        if (!isset($kept[(string)$old['id']])) {
            editQuery($conn, 'DELETE FROM krf_productionorder_consumed WHERE id=? AND psono=? AND projectid=?', [$old['id'], $psono, $projectid]);
        }
    }
}


// Resolve ownership before PSO items are modified. Shared stock is excluded.
function editOwnedProductionStocks($conn, array $original, $psono, $projectid, $clientid) {
    $owned = [];
    foreach ($original['items'] as $item) {
        $number = (string)$item['stockorder_no'];
        $key = $number . '|' . $item['product_id'];
        if ($number === '' || isset($owned[$key])) continue;
        $stocks = editQuery($conn, 'SELECT * FROM krf_production_stockorder WHERE projectid=? AND stockorder_no=? AND productid=? FOR UPDATE', [$projectid,$number,$item['product_id']]);
        if (count($stocks) !== 1 || (string)$stocks[0]['clientid'] !== (string)$clientid) continue;
        $other = editQuery($conn, 'SELECT psono FROM krf_provisional_sales_order_items WHERE projectid=? AND stockorder_no=? AND psono<>? UNION SELECT psono FROM krf_productionorder_consumed WHERE projectid=? AND stockorderno=? AND psono<>? LIMIT 1', [$projectid,$number,$psono,$projectid,$number,$psono]);
        if (!$other) $owned[$key] = $stocks[0];
    }
    return $owned;
}

function editUpdateOwnedProduction($conn, array $original, array $owned, $psono, $projectid, $loginid) {
    foreach ($owned as $key => $stock) {
        $current = editQuery($conn, 'SELECT SUM(quantity) AS qty FROM krf_provisional_sales_order_items WHERE projectid=? AND psono=? AND stockorder_no=? AND product_id=?', [$projectid,$psono,$stock['stockorder_no'],$stock['productid']]);
        if ($current[0]['qty'] !== null) {
            $oldQty = 0;
            foreach ($original['items'] as $item) if ($item['stockorder_no'].'|'.$item['product_id'] === $key) $oldQty += (float)$item['quantity'];
            $newQty = (float)$current[0]['qty'];
            $staleSchedule = editQuery($conn, "SELECT sowingschedulid FROM krf_polyhouse_sowingschedule WHERE projectid=? AND stockorder_no=? AND productid=? AND psono=? AND status='pending' AND (COALESCE(orderqty,-1)<>? OR COALESCE(sowingqty,-1)<>CEIL(?*(1+COALESCE(failurebufferpercent,0)/100))) LIMIT 1 FOR UPDATE", [$projectid,$stock['stockorder_no'],$stock['productid'],$psono,$newQty,$newQty]);
            if ($newQty !== $oldQty || $staleSchedule) {
                if ((string)($stock['psono'] ?? '') !== (string)$psono) throw new RuntimeException('Stock '.$stock['stockorder_no'].' does not belong to this PSO; its production quantity cannot be changed.');
                $schedules = editQuery($conn, 'SELECT * FROM krf_polyhouse_sowingschedule WHERE projectid=? AND stockorder_no=? AND productid=? ORDER BY sowingschedulid FOR UPDATE', [$projectid,$stock['stockorder_no'],$stock['productid']]);
                $actual = editQuery($conn, "SELECT sowingplantid FROM krf_polyhouse_sowingplant_details WHERE projectid=? AND stockorder_no=? AND productid=? AND status NOT IN ('UnConfirmSowing','Removed') LIMIT 1 FOR UPDATE", [$projectid,$stock['stockorder_no'],$stock['productid']]);
                if ($actual) throw new RuntimeException('Sowing has already started for '.$stock['stockorder_no'].'. Production quantity cannot be changed through provisional editing.');
                foreach (['sowingstatus','scionsowingstatus'] as $field) {
                    if (!in_array(strtolower(trim((string)($stock[$field] ?? ''))), ['', 'pending'], true)) throw new RuntimeException('Sowing has already started for '.$stock['stockorder_no'].'.');
                }
                foreach ($schedules as $schedule) {
                    if (in_array($schedule['status'], ['delete','removed','notrequired'], true)) continue;
                    if ($schedule['status'] !== 'pending' || (string)$schedule['psono'] !== (string)$psono) throw new RuntimeException('The sowing schedule for '.$stock['stockorder_no'].' is shared or already in progress.');
                    $planned = (int)ceil($newQty * (1 + (float)$schedule['failurebufferpercent'] / 100));
                    $note = (new DateTimeImmutable('now', new DateTimeZone('Asia/Kolkata')))->format('d-m-Y H:i:s').' | Edited from provisional order | PSO: '.$psono.' | Stock: '.$stock['stockorder_no'].' | '.$schedule['roottype'].' | Order qty: '.$schedule['orderqty'].' -> '.$newQty.' | Planned sowing qty: '.$schedule['sowingqty'].' -> '.$planned.' | By: '.$loginid;
                    editQuery($conn, "UPDATE krf_polyhouse_sowingschedule SET orderqty=?,sowingqty=?,remarks=CONCAT(COALESCE(remarks,''),CASE WHEN COALESCE(remarks,'')='' THEN '' ELSE '\n' END,?),updated_by=?,updated_at=NOW() WHERE sowingschedulid=? AND projectid=? AND status='pending'", [$newQty,$planned,$note,$loginid,$schedule['sowingschedulid'],$projectid]);
                }
                $note = (new DateTimeImmutable('now', new DateTimeZone('Asia/Kolkata')))->format('d-m-Y H:i:s').' | Edited from provisional order | PSO: '.$psono.' | Quantity: '.$stock['quantity'].' -> '.$newQty.' | By: '.$loginid;
                editQuery($conn, "UPDATE krf_production_stockorder SET quantity=?,remarks=CONCAT(COALESCE(remarks,''),CASE WHEN COALESCE(remarks,'')='' THEN '' ELSE '\n' END,?),updated_at=NOW() WHERE id=? AND projectid=?", [$newQty,$note,$stock['id'],$projectid]);
            }
            continue;
        }
        // Retire exclusively owned unused production on replacement or item removal.
        $replacements = [];
        foreach ($original['items'] as $item) {
            if ($item['stockorder_no'].'|'.$item['product_id'] !== $key) continue;
            $rows = editQuery($conn, 'SELECT product_id,stockorder_no FROM krf_provisional_sales_order_items WHERE id=? AND psono=? AND projectid=?', [$item['id'],$psono,$projectid]);
            if ($rows && (string)$rows[0]['product_id'] !== (string)$stock['productid']) $replacements[] = $rows[0]['product_id'].' (stock '.$rows[0]['stockorder_no'].')';
        }
        if (!$replacements) $replacements[] = "removed from order";
        if ((string)($stock['psono'] ?? '') !== (string)$psono) throw new RuntimeException('Cannot cancel production stock owned by another order.');
        $identity=[$projectid,$stock['stockorder_no'],$stock['productid']];
        $actual=editQuery($conn, "SELECT sowingplantid FROM krf_polyhouse_sowingplant_details WHERE projectid=? AND stockorder_no=? AND productid=? AND status NOT IN ('UnConfirmSowing','Removed') LIMIT 1 FOR UPDATE", $identity);
        if ($actual) throw new RuntimeException('Sowing has already started for '.$stock['stockorder_no'].'. Product replacement requires an approved production adjustment.');
        $schedules=editQuery($conn, 'SELECT * FROM krf_polyhouse_sowingschedule WHERE projectid=? AND stockorder_no=? AND productid=? FOR UPDATE', $identity);
        foreach ($schedules as $row) {
            if (in_array($row['status'], ['delete','removed','notrequired'], true)) continue;
            if ($row['status'] !== 'pending' || (string)$row['psono'] !== (string)$psono) throw new RuntimeException('Cannot remove a shared or in-progress sowing schedule.');
        }

        $stamp = (new DateTimeImmutable('now', new DateTimeZone('Asia/Kolkata')))->format('d-m-Y H:i');
        $note = 'Cancelled on '.$stamp.' by '.$loginid.'; PSO '.$psono.': product '.$stock['productid'].' changed to '.implode(', ',array_unique($replacements)).'.';
        editQuery($conn, "UPDATE krf_production_stockorder SET status='Cancelled',sowingstatus='cancelled',scionsowingstatus='cancelled',remarks=CONCAT(COALESCE(remarks,''),CASE WHEN COALESCE(remarks,'')='' THEN '' ELSE '\n' END,?),updated_at=NOW() WHERE id=? AND projectid=?", [$note,$stock['id'],$projectid]);
        editQuery($conn, "UPDATE krf_polyhouse_sowingschedule SET status='removed',remarks=CONCAT(COALESCE(remarks,''),CHAR(10),?),updated_by=?,updated_at=NOW() WHERE projectid=? AND stockorder_no=? AND productid=? AND psono=? AND status='pending'", [$note,$loginid,$projectid,$stock['stockorder_no'],$stock['productid'],$psono]);
    }
}


function editProductionAuditSnapshot($conn, $psono, $projectid) {
    return [
        'production' => editQuery($conn, 'SELECT * FROM krf_production_stockorder WHERE projectid=? AND psono=? ORDER BY id', [$projectid,$psono]),
        'schedules' => editQuery($conn, 'SELECT * FROM krf_polyhouse_sowingschedule WHERE projectid=? AND psono=? ORDER BY sowingschedulid', [$projectid,$psono])
    ];
}

function editRecordProvisionalAudit($conn, array $before, array $after, $psono, $projectid, $actor, $actorName) {
    if (!editHasBusinessOrderChanges($before,$after)) return false;
    $oldItems=[];
    foreach ($before['items'] as $row) $oldItems[(string)$row['id']]=$row;
    $fields=['product_id','product_name','stockorder_no','quantity','rate','freight_charges','taxableamount','discount_percent','discount_amount','cgst_amount','sgst_amount','igst_amount','amount','expecteddeliverydate'];
    foreach ($after['items'] as $row) {
        $old=$oldItems[(string)$row['id']] ?? [];
        $changes=[];
        foreach ($fields as $field) {
            $previous=$old[$field] ?? null; $current=$row[$field] ?? null;
            if ((string)$previous !== (string)$current) $changes[$field]=['before'=>$previous,'after'=>$current];
        }
        if (!$changes) continue;
        $note=(new DateTimeImmutable('now', new DateTimeZone('Asia/Kolkata')))->format('d-m-Y H:i:s').' | Edited from provisional order | By: '.$actorName.' ('.$actor.') | Changes: '.json_encode($changes,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
        editQuery($conn, "UPDATE krf_provisional_sales_order_items SET remark=CONCAT(COALESCE(remark,''),CASE WHEN COALESCE(remark,'')='' THEN '' ELSE '\n' END,?) WHERE id=? AND projectid=? AND psono=?", [$note,$row['id'],$projectid,$psono]);
    }
    editQuery($conn, 'INSERT INTO krf_order_change_log (projectid,psono,sono,order_type,action,reason,before_json,after_json,created_by,created_by_name,created_at) VALUES (?,?,NULL,\'PSO\',?,?,?,?,?,?,NOW())', [$projectid,$psono,'Provisional Order Modified','',json_encode($before,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),json_encode($after,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),$actor,$actorName]);
}
