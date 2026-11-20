<?php
date_default_timezone_set("Asia/Calcutta");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
 // error_reporting(E_ALL);
require('../../connection.php');  
require('function_reconsile_save.php'); 
include('../../functions/function_savetodb.php');
include('../../functions/function_master.php');
$action=$_GET['action'];
$curnow = date("Y-m-d H:i:s");
$arr=array();  
$companyid=$_POST['companyid'];
$subcompanyid=$_POST['subcompanyid'];
$projectid=$_POST['projectid']; 
$loginid=$_POST['loginid'];  
if($action=="save_scantdata"  ){
    $reconcilefrom=$_POST['reconcilefrom']; 
    $credit_array=$_POST['credit_array'];
    $bank_array=$_POST['bank_array'];
    $transactiontype=$_POST['transactiontype'];
    $creditadvice = json_decode($credit_array,TRUE);
    $bankarray = json_decode($bank_array,TRUE); 
    $conn->begin_transaction(); 
    try{           
        $bank_sno="";
        foreach ($bankarray as $tbbank){             
            $bank_sno=$tbbank['banksno'];
        }
        foreach ($creditadvice as $tbkey){  
            $paidby=$tbkey['paid']; 
            $paytoid=$tbkey['paidid']; 
            $purposecode=$tbkey['purposecode']; 
            $uploadaudiofile=$tbkey['voiceinstruction'];
            $uploadFilename=$tbkey['documentpath'];  
            $purpose_master=$tbkey['purpose']; 
            $scantdatasno=$tbkey['scandatasno'];
            $remarks ="";
            $ref_no="";             
            if($paytoid && $purposecode){           
                insert_reconcile_reconstructed_tbl($conn,$companyid, $subcompanyid ,$projectid, $transactiontype, $paidby, $paytoid, $purposecode, $purpose_master, $scantdatasno,$bank_sno, $uploadaudiofile, $uploadFilename,$remarks ,$ref_no, "completed",$loginid,'yes'); 
            }else{                 
                 insert_reconcile_reconstructed_tbl($conn,$companyid, $subcompanyid ,$projectid, $transactiontype, $paidby, $paytoid, $purposecode, $purpose_master, $scantdatasno,$bank_sno, $uploadaudiofile, $uploadFilename,$remarks ,$ref_no, "incompleted",$loginid,'yes');             
            }
        } 
        $conn->commit();         
        echo "Reconcile Saved Successfully" ;    
    }    
    catch(mysqli_sql_exception $exception)
    { 
        $conn->rollback();        
        echo  $exception->getmessage();
    }   
} else if($action=="automatic_scantdata"){
    $transactiontype=$_POST['transactiontype'];
    $conn->begin_transaction(); 
    try{ 
        $qry = "SELECT c.id,c.sno,c.account_id, c.transactiondate, round(c.amount,2) as amount,c.transactionno, DATE_FORMAT(c.transactiondate,'%d-%m-%Y') as displaydate,c.remarks as bankdesciption ,c.clientid , c.purpose,c.purposecode,c.filename, c.voicepath,c.clientname ,  c.clienttype,   c.deposittype FROM  tbl_creditadvice c    WHERE   c.companyid = '".$companyid."'   AND c.subcompanyid = '".$subcompanyid."'   AND c.projectid ='".$projectid."'  and IFNULL(c.reconcile_refno, '') =''  and ifnull(c.tedit,'')=''  and ifnull(c.account_id,'')<>''  order by c.transactiondate,c.id, c.sno ";
        $result=$conn->query($qry);     
        while ($row=$result->fetch_assoc()) {
            $subqry = "";
            $subqry2 = "";
            $date = date('Y-m-d', strtotime($row['transactiondate'])); 
            $match_desc = strtolower(trim($row['bankdesciption']));
            $match_cheque = strtolower(trim($row['transactionno']));
            $match_desc_escaped = addslashes($match_desc);
            $match_cheque_escaped = addslashes($match_cheque);
            if ($match_desc && $match_cheque) {
                $subqry = " AND (lcase(c.description_bank) = '$match_desc_escaped' OR lcase(c.chequeno) = '$match_cheque_escaped')";
            } elseif ($match_desc) {
                $subqry = " AND lcase(c.description_bank) = '$match_desc_escaped'";
            } elseif ($match_cheque) {
                $subqry = " AND lcase(c.chequeno) = '$match_cheque_escaped'";
            }
            if (!empty($row['account_id'])) {
                $subqry2 = " AND c.account_id = '" . $row['account_id'] . "'";
            } 
            $final_subqry = "SELECT b.bankname, acc.accountname, acc.accountno, c.bankid, c.account_id,   DATE_FORMAT(c.date, '%d-%m-%Y') AS diplaydate, c.date AS date, c.total_amount, c.description_bank, IFNULL(c.chequeno,'') AS chequeno, c.banksno, c.sno
                FROM tbl_closetobook c  
                JOIN tbl_bank_master b ON b.id = c.bankid 
                JOIN tbl_master_account acc ON acc.accountid = c.account_id 
                WHERE lcase(c.amounttype) = 'deposit'  AND c.company_id = '$companyid'  AND c.subcompany_id = '$subcompanyid'  AND c.project_id = '$projectid'  AND IFNULL(c.reconciliation, '') = ''   AND ROUND(c.total_amount, 2) = '" . $row['amount'] . "'  AND DATE(c.date) = '$date'  $subqry $subqry2 ORDER BY c.date ASC";            
            $subresult=$conn->query($final_subqry); 
            if ($subresult->num_rows == 1) {
                $subrow=$subresult->fetch_assoc();
                $paidby=$row['clientname']; 
                $paytoid=$row['clientid'];  
                $purposecode=$row['purposecode']; 
                $purpose_master=$row['purpose'];  
                 
                $uploadaudiofile = $row['voicepath'] ?: "";
                $uploadFilename = $row['filename'] ?: "";
                $scantdatasno=$row['sno'];
                $remarks ="";
                $ref_no="";  
                $bank_sno =$subrow['sno'];          
                if($paytoid && $purposecode){           
                    insert_reconcile_reconstructed_tbl($conn,$companyid, $subcompanyid ,$projectid, $transactiontype, $paidby, $paytoid, $purposecode, $purpose_master, $scantdatasno,$bank_sno, $uploadaudiofile, $uploadFilename,$remarks ,$ref_no, "completed",$loginid); 
                }else{                 
                     insert_reconcile_reconstructed_tbl($conn,$companyid, $subcompanyid ,$projectid, $transactiontype, $paidby, $paytoid, $purposecode, $purpose_master, $scantdatasno,$bank_sno, $uploadaudiofile, $uploadFilename,$remarks ,$ref_no, "incompleted",$loginid);             
                }
            } 
        }
        $conn->commit();         
        echo "Auto Reconcile completed" ; 
    }    
    catch(mysqli_sql_exception $exception)
    { 
        $conn->rollback();        
        echo  $exception->getmessage();
    } 

}
function insert_reconcile_reconstructed_tbl($conn,$companyid, $subcompanyid ,$projectid, $transactiontype, $paid, $paidid, $purposecode, $purpose, $scantdatasno,$banksno,   $voiceinstruction, $documentfile,$remarkbyclient ,$referenceno_unique, $bookstatus,$loginid,$createhistory){
    $maxid=maxidforanytable_m3($conn,"id","reconcile_reconstructed_tbl" );
    $sql ="insert into reconcile_reconstructed_tbl (id, companyid, subcompanyid ,projectid, transactiontype, paid, paidid, purposecode, purpose, banksno, accountid, transactiondate, bankdescription, bankamount, voiceinstruction, remarkbyclient ,referenceno_unique, bookstatus,loginid,documentpath,history)  select '".$maxid ."', '".$companyid."', '". $subcompanyid ."', '".$projectid."', '". $transactiontype."', '". addslashes($paid)."', '". $paidid."', '". $purposecode."', '". addslashes($purpose)."',  banksno , account_id, date, description_bank, total_amount, '". $voiceinstruction."', '". addslashes($remarkbyclient) ."', '".$referenceno_unique."', '". $bookstatus."', '".$loginid."' , '".$documentfile."','".$createhistory."' from tbl_closetobook where sno ='".$banksno."'";
    $conn->query($sql);  
    $conn->query("UPDATE tbl_closetobook SET payto='".addslashes($paid)."', paytoid='".$paidid."', purpose ='".addslashes($purpose)."', purpose_code='".$purposecode."' ,reconciliation = '". $bookstatus."' WHERE sno='".$banksno."'");    
    $conn->query("UPDATE tbl_creditadvice SET  reconcile_refno = '".$banksno. "'  WHERE   sno = '" . $scantdatasno . "'  ");    
}
function maxidforanytable_m3($conn,$fldname,$tblname ){
    $maxuniqueid=""; 
    $sql = "SELECT COALESCE(MAX($fldname), 0) + 1 AS max_id FROM $tblname";   
    $result   = $conn->query($sql);
    if($result->num_rows>0){
        $row = $result->fetch_assoc();
        $maxuniqueid = $row['max_id'] ;
    }
    return $maxuniqueid;
} 
?>

