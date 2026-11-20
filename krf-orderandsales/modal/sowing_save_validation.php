<?php
/** Caller must begin a transaction and retain these locks until save commits. */
function validateSowingSaveAgainstCurrentPlan($db, array $data, $trayCavity) {
    $read=function($sql,$params)use($db){
        $stmt=$db->prepare($sql);
        if(!$stmt)throw new RuntimeException('Cannot validate the current sowing plan.');
        $stmt->bind_param(str_repeat('s',count($params)),...$params);
        if(!$stmt->execute())throw new RuntimeException('Cannot validate the current sowing plan.');
        $rows=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);$stmt->close();return $rows;
    };
    $project=(string)$data['projectid'];$stock=(string)$data['stockorder_no'];$product=(string)$data['productid'];
    $type=ucfirst(strtolower(trim((string)$data['roottype'])));
    if(!in_array($type,['Root','Scion'],true)||$trayCavity<=0)throw new RuntimeException('Invalid sowing type or tray capacity.');
    // Stock lock serializes this save with order editing and other sowing saves.
    $stocks=$read('SELECT * FROM krf_production_stockorder WHERE projectid=? AND stockorder_no=? AND productid=? FOR UPDATE',[$project,$stock,$product]);
    if(count($stocks)!==1)throw new RuntimeException('Production stock identity needs checking. Please refresh.');
    $production=$stocks[0];
    if(in_array(strtolower(trim((string)$production['status'])),['cancel','cancelled','removed','deleted'],true))throw new RuntimeException('This production order is cancelled. Please refresh.');
    $plans=$read("SELECT * FROM krf_polyhouse_sowingschedule WHERE projectid=? AND stockorder_no=? AND productid=? AND roottype=? AND status NOT IN ('delete','removed','notrequired') FOR UPDATE",[$project,$stock,$product,$type]);
    if(count($plans)!==1)throw new RuntimeException('The current sowing schedule needs checking. Please refresh.');
    $plan=$plans[0];$planned=(int)$plan['sowingqty'];
    $rounded=(int)(ceil($planned/$trayCavity)*$trayCavity);
    $submitted=(float)($data['totalsowingqty']??-1);
    if((float)($data['orderqty']??-1)!==(float)$plan['orderqty'] || abs((float)($data['failurebufferpercent']??-1)-(float)$plan['failurebufferpercent'])>0.00001 || ($submitted!==(float)$planned && $submitted!==(float)$rounded)){
        throw new RuntimeException('This sowing plan was changed by another user. Please refresh and try again.');
    }
    $editId=(int)($data['sowingplantid']??0);
    if(!$editId && !in_array($plan['status'],['pending','inprogress','edited'],true))throw new RuntimeException('This sowing plan is already completed. Please refresh.');
    // All trays count across PSOs for this production identity; retired/dead trays are excluded.
    $trays=$read("SELECT sowingplantid,traycavity,status,roottype FROM krf_polyhouse_sowingplant_details WHERE projectid=? AND stockorder_no=? AND productid=? AND roottype=? FOR UPDATE",[$project,$stock,$product,$type]);
    $actual=0;$found=false;
    foreach($trays as $tray){
        if($editId===(int)$tray['sowingplantid']){
            $found=true;
            if(!in_array($tray['status'],['Sowing','Germination'],true))throw new RuntimeException('This tray has moved to another stage and cannot be edited here.');
            continue;
        }
        if(in_array($tray['status'],['Sowing','Polyhouse','Germination','Tray Received At Germination','Tray Allocated To Polyhouse'],true))$actual+=max(0,(int)$tray['traycavity']);
    }
    if($editId && !$found)throw new RuntimeException('The selected tray no longer belongs to this sowing plan. Please refresh.');
    $count=$editId?1:filter_var($data['traycount']??null,FILTER_VALIDATE_INT);
    if($count===false||$count<1)throw new RuntimeException('Please enter a positive whole number of trays.');
    $requested=$count*$trayCavity;
    if($actual+$requested>$rounded)throw new RuntimeException('Sowing quantity exceeds the current remaining balance of '.max(0,$rounded-$actual).' plants. Please refresh and try again.');
    return ['orderqty'=>(int)$plan['orderqty'],'failurebufferpercent'=>(float)$plan['failurebufferpercent'],'totalsowingqty'=>$rounded,'actual_sown'=>$actual,'remaining'=>max(0,$rounded-$actual)];
}
