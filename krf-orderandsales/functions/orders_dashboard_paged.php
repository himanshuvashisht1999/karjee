<?php
/** Separate, server-paginated PSO dashboard reader. No changes to original functions. */
function getPendingProvisionalSalesOrdersPage250($conn,$authconn,$companyid,$subcompanyid,$projectid,$filters=[],$page=1,$perPage=250) {
    $perPage=max(1,min(1000,(int)$perPage)); $page=max(1,(int)$page);
    $where="o.companyid = ? AND o.subcompanyid = ? AND o.projectid = ? AND (o.status IN ('Draft','Pending Approval','Emergency Sowing','Confirmed','Cancelled','') OR o.status IS NULL)";
    $types='ssi'; $args=[(string)$companyid,(string)$subcompanyid,(int)$projectid];
    $addIn=function($column,$values) use (&$where,&$types,&$args) {
        if(!is_array($values)) $values=[$values];
        $values=array_values(array_filter(array_map('trim',$values),static fn($v)=>$v!==''));
        if(!$values) return;
        $where.=" AND $column IN (".implode(',',array_fill(0,count($values),'?')).")";
        $types.=str_repeat('s',count($values)); array_push($args,...$values);
    };
    $addIn('DATE(o.created_at)',$filters['order_date']??[]);
    $addIn('o.clientname',$filters['client']??[]);
    $addIn('o.status',$filters['status']??[]);
    $addIn('o.created_by',$filters['created_by']??[]);
    $dates=$filters['delivery_date']??[];
    if(!is_array($dates)) $dates=[$dates]; $dates=array_values(array_filter($dates));
    if($dates) {
        $where.=' AND EXISTS (SELECT 1 FROM krf_provisional_sales_order_items di WHERE di.psono=o.psono AND di.expecteddeliverydate IN ('.implode(',',array_fill(0,count($dates),'?')).'))';
        $types.=str_repeat('s',count($dates)); array_push($args,...$dates);
    }
    $search=trim((string)($filters['search']??''));
    if($search!=='') {
        $words=preg_split('/\\s+/u',$search);
        foreach(array_slice($words,0,8) as $word) {
            $where.=' AND (o.psono LIKE ? OR o.clientname LIKE ? OR o.mobileno LIKE ? OR EXISTS (SELECT 1 FROM krf_provisional_sales_order_items si WHERE si.psono=o.psono AND si.product_name LIKE ?))';
            $types.='ssss'; $like='%'.$word.'%'; array_push($args,$like,$like,$like,$like);
        }
    }
    $run=function($sql,$types,$args) use ($conn) {
        $stmt=$conn->prepare($sql); if(!$stmt) throw new RuntimeException($conn->error);
        if($types!=='') $stmt->bind_param($types,...$args);
        if(!$stmt->execute()) throw new RuntimeException($stmt->error);
        $res=$stmt->get_result(); $rows=$res->fetch_all(MYSQLI_ASSOC); $stmt->close(); return $rows;
    };
    try {
        $countRows=$run('SELECT COUNT(*) AS n FROM krf_provisional_sales_order o WHERE '.$where,$types,$args);
        $total=(int)($countRows[0]['n']??0); $pages=max(1,(int)ceil($total/$perPage)); $page=min($page,$pages); $offset=($page-1)*$perPage;
        $ids=$run('SELECT o.psono FROM krf_provisional_sales_order o WHERE '.$where.' ORDER BY o.created_at DESC,o.psono DESC LIMIT ? OFFSET ?', $types.'ii',array_merge($args,[$perPage,$offset]));
        $psonos=array_column($ids,'psono'); if(!$psonos) return ['rows'=>[],'total'=>$total,'page'=>$page,'pages'=>$pages,'per_page'=>$perPage];
        $placeholders=implode(',',array_fill(0,count($psonos),'?'));
        // Totals are correlated to each page's PSO rather than aggregated across the entire database.
        $sql="SELECT o.id,o.psono,o.companyid,o.subcompanyid,o.projectid,o.clientid,o.clientname,o.emailid,o.delivery_address,o.districtname,o.statename,o.mobileno,o.payment_terms,o.remarks,o.status,o.pickupfrom,o.created_by,o.created_at,o.updated_by,o.updated_at,
            i.product_name,i.quantity,i.expecteddeliverydate,COALESCE(i.amount,0) AS item_amount,
            COALESCE((SELECT SUM(t.amount) FROM krf_provisional_sales_order_items t WHERE t.psono=o.psono),0) AS orderamount,
            COALESCE((SELECT SUM(a.advanceamount) FROM advance_payment_transactions a WHERE a.invoiceno=o.psono AND a.clientid=o.clientid AND (a.tedit IS NULL OR a.tedit='')),0) AS advanceamt
            FROM krf_provisional_sales_order o LEFT JOIN krf_provisional_sales_order_items i ON i.psono=o.psono
            WHERE o.psono IN ($placeholders) AND o.companyid = ? AND o.subcompanyid = ? AND o.projectid = ? ORDER BY o.created_at DESC,o.psono DESC,i.expecteddeliverydate ASC";
        $rows=$run($sql,str_repeat('s',count($psonos)).'ssi',array_merge($psonos,[(string)$companyid,(string)$subcompanyid,(int)$projectid]));
        $ids=[]; foreach($rows as &$r) { $r['balanceamt']=(float)$r['orderamount']-(float)$r['advanceamt']; $r['loginname']=(string)($r['created_by']??''); $r['loginroleid']=''; $r['updatedby_name']=(string)($r['updated_by']??''); foreach(['created_by','updated_by'] as $k) if(!empty($r[$k])) $ids[(string)$r[$k]]=(string)$r[$k]; } unset($r);
        if($ids) {
            $idvals=array_values($ids); $ph=implode(',',array_fill(0,count($idvals),'?'));
            $stmt=$authconn->prepare("SELECT loginid,loginname,loginroleid FROM login_user_master WHERE loginid IN ($ph)");
            if($stmt) { $stmt->bind_param(str_repeat('s',count($idvals)),...$idvals); $stmt->execute(); $result=$stmt->get_result(); $users=[]; while($u=$result->fetch_assoc()) $users[(string)$u['loginid']]=$u; $stmt->close();
                foreach($rows as &$r) { $creator=$users[(string)($r['created_by']??'')]??null; $updater=$users[(string)($r['updated_by']??'')]??null; if($creator){$r['loginname']=$creator['loginname'];$r['loginroleid']=$creator['loginroleid'];} if($updater) $r['updatedby_name']=$updater['loginname']; } unset($r);
            }
        }
        return ['rows'=>$rows,'total'=>$total,'page'=>$page,'pages'=>$pages,'per_page'=>$perPage];
    } catch(Throwable $e) { error_log('KRF paginated dashboard: '.$e->getMessage()); return ['rows'=>[],'total'=>0,'page'=>1,'pages'=>1,'per_page'=>$perPage,'error'=>true]; }
}
