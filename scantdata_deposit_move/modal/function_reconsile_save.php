<?php
function saveto_t2c_creditadvice_new($conn,$companyid, $subcompanyid, $projectid,$paidby, $paidby_id, $actualpayee, $actualpayee_id, $typeofclient, $dateofreceipt, $cr_referenceno, $descriptionbyagent, $bank_sno, $bankid, $accountid, $accountname, $bank_date, $bank_descript, $bankamount, $purposecode,  $amountwithouttax,$vatamount, $igstamount, $cgst, $sgst, $reconcilefrom, $reconciletype ,$cr_uniqueid,$autodebitamt,$cr_totalamount,$accountno,$cr_bankrefenceno,$deposittype,$documentationpath,$loginid  ){  

    $uniquesno=maxidforanytable($conn,"id","t2c_creditadvice" );
    if(!$uniquesno){
        echo $uniquesno."Error In get max snobbb";
        exit;
    }    
	$ref_no = 'REF-'.$companyid.'-'.$subcompanyid.'-'.$projectid.'-'.substr($accountno, -4).'-'.$uniquesno;
    if ($deposittype=="Deposit" ){
          $sql="insert into t2c_creditadvice (id,referenceno_unique ,companyid ,subcompanyid ,projectid ,paidby ,paidby_id ,actualpayee ,actualpayee_id ,typeofclient ,dateofreceipt ,agentreferenceno ,descriptionbyagent ,bank_sno ,bankid ,accountid ,accountname ,bank_date ,descriptionbybank ,amount ,purposecode ,reconciliation,amountwithouttax, vatamount, igstamount, cgst, sgst,reconcilefrom,reconciletype,documentpath,amountbeforeautodeduction) VALUES('".$uniquesno."' ,'".$ref_no."' ,'".$companyid."', '".$subcompanyid."', '".$projectid."','".$paidby."', '".$paidby_id."', '".$actualpayee."', '".$actualpayee_id."', '".$typeofclient."', '".$dateofreceipt."', '".$cr_referenceno."', '".$descriptionbyagent."', '".$bank_sno."', '".$bankid."' , '".$accountid."', '".$accountname."', '".$bank_date."', '".$bank_descript."', '".$bankamount."', '".$purposecode."' ,'Complete' , '".$amountwithouttax."','".$vatamount."', '".$igstamount."', '".$cgst."', '".$sgst."', '".$reconcilefrom."', '".$reconciletype."', '".$documentationpath."', '".$cr_totalamount."') ";    
        $conn->query($sql);

          $sql="update tbl_closetobook set reference_no ='".$ref_no."' ,book_status ='".$reconciletype."' , reconciliation ='Completed' where amounttype = 'deposit' AND company_id = '" . $companyid . "' AND subcompany_id = '".$subcompanyid. "' AND project_id = '".$projectid."' and banksno = '".$bank_sno."' AND IFNULL(reference_no, '') = '' ";
        $conn->query($sql); 
    }
    if(($reconcilefrom=="Credit Advice"  || $reconcilefrom=="Automatic Credit Advice") && $deposittype=="Auto Deduction"  && floatval($autodebitamt)>0)  {
    	$taxamount=floatval($vatamount)+floatval($igstamount)+floatval($cgst)+floatval($sgst);
    	$transferamount=floatval($autodebitamt)+floatval($taxamount) ;  
    	$transactionno="NIL";
    	$descriptionbank=$cr_bankrefenceno;
    	$reconciliedstatus="Done";
    	$paymentmode='NIL';
    	$loginname=$loginid;
    	 $response=InsertAutodeduction_all($conn,$companyid,$subcompanyid,$projectid,$subprojectid,$accountid,$paymentmode,$transactionno,$dateofreceipt,$transferamount,$paidby_id,$paidby,$actualpayee,$purposecode,$descriptionbyagent,$descriptionbank,$autodebitamt,$taxamount,$reconciliedstatus,$bank_sno,$loginname,$documentationpath); 
    	if ($response['status'] !== 'Success') {
	        echo "Error In Saving InsertAutodeduction";
	        exit;
    	} else  if($reconcilefrom=="Credit Advice" || $reconcilefrom=="Automatic Credit Advice"){ 
             $sql="update tbl_creditadvice set reconcile_refno ='".$ref_no."' where  companyid = '" . $companyid . "' AND subcompanyid = '".$subcompanyid. "' AND projectid = '".$projectid."' and transactionno = '".$cr_referenceno."' and id = '".$cr_uniqueid."'   AND IFNULL(reconcile_refno, '') = '' and filename ='".$documentationpath."' and deposittype ='Auto Deduction' ";
             $conn->query($sql);
        }
    }   
       
    if($reconcilefrom=="Sale Invoice" || $reconcilefrom=="Left Over Deposit"){ 
        $subqry=" and actionfrom ='sale invoice' ";
        if($reconcilefrom!="Sale Invoice"){
            $subqry=" and ifnull(actionfrom,'') !='sale invoice' ";
        }
        $sql="update tbl_creditadvice_c1 set reconcile_refno ='".$ref_no."' where  companyid = '" . $companyid . "' AND subcompanyid = '".$subcompanyid. "' AND projectid = '".$projectid."' and invoiceno = '".$cr_referenceno."' and id = '".$cr_uniqueid."'   AND IFNULL(reconcile_refno, '') = '' ". $subqry;  
        $conn->query($sql);
    }else  if($reconcilefrom=="Credit Advice" || $reconcilefrom=="Automatic Credit Advice"){
        $sql="update tbl_creditadvice set reconcile_refno ='".$ref_no."' where  companyid = '" . $companyid . "' AND subcompanyid = '".$subcompanyid. "' AND projectid = '".$projectid."' and transactionno = '".$cr_referenceno."' and id = '".$cr_uniqueid."'   AND IFNULL(reconcile_refno, '') = '' and filename ='".$documentationpath."' and deposittype ='Deposit' ";           // and bankreceivedamt ='".$adviceamount."'   
        $conn->query($sql); 
    }     
}

function maxidforanytable($conn,$fldname,$tblname ){
    $maxuniqueid=""; 
    $sql = "SELECT COALESCE(MAX($fldname), 0) + 1 AS max_id FROM $tblname";   
    $result   = $conn->query($sql);
    if($result->num_rows>0){
        $row = $result->fetch_assoc();
        $maxuniqueid = $row['max_id'] ;
    }
    return $maxuniqueid;
}
function chkduplicate_history_credit($conn,$companyid ,$subcompanyid ,$projectid ,$accountid ,$description ,$purposecode ,$payerid ,$paidonbehalfid){
    $retval="";
    $sql = "SELECT id from tbreconstructedhistory_deposit where companyid='".$companyid."' 
        and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'  and accountid='".$accountid."' and description='".addslashes($description)."'   and purposecode='".$purposecode."' and payerid='".$payerid."' and paidonbehalfid='".$paidonbehalfid."'";
    $result   = $conn->query($sql);
    if($result->num_rows>0){
        $retval="yes";
    }
    return $retval;
}
function getaccounname($conn,$companyid ,$subcompanyid ,$projectid ,$accountid ,$bankid  ){
    $retval="";
    $sql = "SELECT accountname from tbl_master_account where cmp_id='".$companyid."' 
        and subcmp_id='".$subcompanyid."' and projectid='".$projectid."'  and accountid='".$accountid."' and bankid='".$bankid."'   ";
        $result   = $conn->query($sql);
    if($result->num_rows>0){
          $row = $result->fetch_assoc();
          $retval=$row['accountname'] ;
    }
    return $retval;
}
?>