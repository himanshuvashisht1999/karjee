<?php
date_default_timezone_set("Asia/Calcutta");
require('../connection.php');
require('../functions/function_master.php');
$action=$_GET['action'];
if ($action=="getunverifiedadvicelist") {
	$companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];    
    $clienttype = $_POST['clienttype'];
    $clientid = $_POST['clientid']; 
    $rrs=array();     
   $qry="select date_format(c.transactiondate,'%d-%m-%Y' ) as transactiondate, c.transactionno as compayremitno, c.bankreceivedamt, c.amount as receivedfromclient, c.debitamount, c.vatamount,  c.bankrefno,COALESCE(NULLIF(c.verified_status, ''), 'Pending')  as status, c.id, c.filename ,c.deposittype, IFNULL(
        CASE  WHEN c.clienttype = 'Intermediate' AND c.deposittype = 'Auto Deduction' THEN 
                (SELECT i.client_name FROM intermediatemaster i WHERE i.client_id = c.actualpayeeid and i.companyid ='".$companyid."' and i.subcompanyid='".$subcompanyid."' and i.projectid='".$projectid."')
            ELSE 
               (SELECT i.client_name FROM client_master i WHERE i.client_id = c.actualpayeeid and i.companyid ='".$companyid."' and i.subcompanyid='".$subcompanyid."' and i.projectid='".$projectid."')
        END, 
    '') as actualpayer  from tbl_creditadvice c where  c.companyid ='".$companyid."' and c.subcompanyid='".$subcompanyid."' and c.projectid='".$projectid."'  and c.clienttype='".$clienttype."' and c.clientid='".$clientid."'  and ifnull(c.id,'')<>''  order by c.transactiondate,c.sno ,c.id" ;
    	$resultcredit=$conn->query($qry);
    	if ($resultcredit->num_rows>0) {
            while ($rowlist=$resultcredit->fetch_assoc()) {
                $rrs[]=array( "transactiondate"=>$rowlist['transactiondate'],"actualpayer"=>$rowlist['actualpayer'], "compayremitno"=>$rowlist["compayremitno"], "bankreceivedamt"=>$rowlist["bankreceivedamt"], "receivedfromclient"=>$rowlist["receivedfromclient"], "debitamount"=>$rowlist["debitamount"], "vatamount"=>$rowlist["vatamount"], "bankrefno"=>$rowlist["bankrefno"], "status"=>$rowlist["status"], 'id'=>$rowlist["id"] ,'filename'=>$rowlist["filename"], 'deposittype'=>$rowlist["deposittype"]);
            }
    	} 
        echo json_encode($rrs);
}else if ($action=="getintermedetails") {
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];   
    $rrs=array();     
    $qry="select  client_id,client_name,invoicing_address,phones,client_email,JSON_OBJECT( 
        'vat', vat,
        'comp_website', comp_website,
        'bankname', bankname,
        'accountno', accountno,
        'ifsccode', ifsccode,
        'account_name', account_name,
        'sort_code', sort_code,
        'swift', swift,
        'iban', iban
    ) AS arraydata   from intermediatemaster    where  companyid ='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'   and ifnull(tedit,'')=''  order by  client_name " ;
        $resultcredit=$conn->query($qry);
        if ($resultcredit->num_rows>0) {
            while ($rowlist=$resultcredit->fetch_assoc()) {
                $rrs[]=array( "client_id"=>$rowlist['client_id'],"client_name"=>$rowlist['client_name'], "client_address"=>$rowlist["invoicing_address"], "client_phones"=>$rowlist["phones"], "client_email"=>$rowlist["client_email"] , "arraydata"=>$rowlist["arraydata"] );
            }
        }  
        echo json_encode($rrs);
}else if ($action=="getclientinfodetails") {
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];   
    $rrs=array();     
    $qry="select  client_id,client_name,invoicing_address,phones,client_email,JSON_OBJECT( 
        'vat', vat,
        'comp_website', comp_website,
        'bankname', bankname,
        'accountno', accountno,
        'ifsccode', ifsccode,
        'account_name', account_name,
        'sort_code', sort_code,
        'swift', swift,
        'iban', iban ,
        'name_forpo', name_forpo,
        'contact_forpo', contact_forpo,
        'email_forpo', email_forpo
    ) AS arraydata   from client_master    where  companyid ='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'   and ifnull(tedit,'')=''  order by  client_name " ;
        $resultcredit=$conn->query($qry);
        if ($resultcredit->num_rows>0) {
            while ($rowlist=$resultcredit->fetch_assoc()) {
                $rrs[]=array( "client_id"=>$rowlist['client_id'],"client_name"=>$rowlist['client_name'], "client_address"=>$rowlist["invoicing_address"], "client_phones"=>$rowlist["phones"], "client_email"=>$rowlist["client_email"] , "arraydata"=>$rowlist["arraydata"] );
 
            }
        }  
        echo json_encode($rrs);
}else if ($action=="getsaleinvoicetype") {
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];   
    $rrs=array();     
    $qry="select  id,invoicetype  from tbl_saleinvoicetypemaster    where  companyid ='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'   and ifnull(tedit,'')=''  order by  invoicetype " ;
        $resultcredit=$conn->query($qry);
        if ($resultcredit->num_rows>0) {
            while ($rowlist=$resultcredit->fetch_assoc()) {
                $rrs[]=array( "id"=>$rowlist['id'],"invoicetype"=>$rowlist['invoicetype'] );
 
            }
        }  
        echo json_encode($rrs);
}
?>